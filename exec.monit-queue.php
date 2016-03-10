<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="Daemon Monitor";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.monit.inc');
include_once(dirname(__FILE__).'/ressources/class.mail.inc');
include_once(dirname(__FILE__).'/ressources/class.phpmailer.inc');
include_once(dirname(__FILE__).'/ressources/class.system-msmtp.inc');

xstart();


function xstart(){
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/exec.monit-queue.php.Watch.time";
	
	if(!$GLOBALS["FORCE"]){

		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			events("Already running PID: $pid since {$time}Mn",__FUNCTION__,__LINE__);
			return false;
		}
		@file_put_contents($pidfile, getmypid());
		$filetime=$unix->file_time_min($pidTime);
		if($filetime<5){
			events("{$filetime}Mn require 5mn",__FUNCTION__,__LINE__);
			return;
		}
	}
	
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	
	$q=new mysql();
	$Dirs=$unix->dirdir("/home/artica/system/perf-queue");
	
	$sql="CREATE TABLE IF NOT EXISTS `perfs_queue` (
			  `zDate` timestamp NOT NULL PRIMARY KEY,
			  `subject` varchar(90) NOT NULL,
			  `file` MEDIUMBLOB NOT NULL,
			   index `subject` (`subject`)
			) ENGINE=MYISAM; ";
	
	$q->QUERY_SQL($sql,"artica_events");
	
	
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	
	$subjects["LOAD_1"]="System exceed load average 1mn policy";
	$subjects["LOAD_5"]="System exceed load average 5mn policy";
	$subjects["LOAD_15"]="System exceed load average 15mn policy";
	
	$subjects["CPU_SYSTEM"]="System exceed CPU [system] policy";
	$subjects["CPU_USER"]="System exceed CPU [user] policy";
	$subjects["CPU_WAIT"]="System exceed CPU [wait] policy";
	$subjects["MEM"]="System exceed Memory policy";
	
	$units[null]=" unknown";
	$units["LOAD_1"]=" load";
	$units["LOAD_5"]=" load";
	$units["LOAD_15"]=" load";
	
	$units["CPU_SYSTEM"]="%";
	$units["CPU_USER"]="%";
	$units["CPU_WAIT"]="%";
	$units["MEM"]="%";
	

	while (list ($directory, $line) = each ($Dirs) ){
		$time=basename($directory);
		$fileTar="/home/artica/system/perf-queue/$time.tar.gz";
		$why2="?";
			if(!is_file("$directory/time.txt")){
				events("Removing $directory $directory/time.txt no such file",__FUNCTION__,__LINE__);
				system("$rm -rf $directory");
				continue;
			}
			
			if(is_file("$directory/why2.txt")){
				$why2=trim(@file_get_contents("$directory/why2.txt"));
			}
			
			$TimeDirectory=$unix->file_time_min("$directory/time.txt");
			if($TimeDirectory>480){
				events("Removing $directory {$TimeDirectory}Mn TTL exceed 480mn",__FUNCTION__,__LINE__);
				system("$rm -rf $directory");
				continue;
			}
			$why=trim(@file_get_contents("$directory/why.txt"));
			chdir($directory);
			system("cd $directory");
			if(is_file("$fileTar")){@unlink($fileTar);}
			shell_exec("$tar -czf $fileTar *");
			if(!is_file($fileTar)){continue;}
			
			
			$subject=$subjects[$why]." - $why2{$units[$why]} ";
			$date=date("Y-m-d H:i:s",$time);
			$sizedata=mysql_escape_string2(@file_get_contents($fileTar));
			xnotify($subject,$fileTar);
			@unlink($fileTar);
			$sql="INSERT IGNORE INTO `perfs_queue` (zDate,subject,file) VALUES ('$date','$subject','$sizedata');";
			$q->QUERY_SQL($sql,"artica_events");
			if(!$q->ok){
				events(substr($q->mysql_error,0,255)."...",__FUNCTION__,__LINE__);
				continue;
			}
			
			events("Removing $directory ($sizedata) Bytes",__FUNCTION__,__LINE__);
			system("$rm -rf $directory");
			
			
	}
	
	
}

function xnotify($subject,$file){
	$unix=new unix();
	$msmtp=new system_msmtp(null, null);
	if($msmtp->ENABLED==0){events("Send eMail disabled...",__FUNCTION__,__LINE__);return;}	
	$mail = new PHPMailer(true);
	$mail->IsSendmail();
	$mail->AddAddress($msmtp->recipient,$msmtp->recipient);
	$mail->AddReplyTo($msmtp->recipient,$msmtp->recipient);
	$mail->From=$msmtp->smtp_sender;
	$mail->FromName=$unix->hostname_g();
	$mail->Subject=$subject;
	$mail->Body= $subject;
	$mail->AddAttachment($file,basename($file));
	$content=$mail->Send(true);
	$msmtp=new system_msmtp(null, $content);
	if(!$msmtp->Send()){
		events("Send eMail Failed...",__FUNCTION__,__LINE__);
		squid_admin_mysql(0, "Fatal: Unable to send email notification", "Subject: $subject\n$msmtp->logs",__FILE__,__LINE__);
		return;
	}
	events("Send eMail success...",__FUNCTION__,__LINE__);
}

function events($text,$function=null,$line=0){
	if($GLOBALS["VERBOSE"]){echo "$function:: $text (L.$line)\n"; return; }
	$filename=basename(__FILE__);
	$unix=new unix();
	$unix->events("$filename $function:: $text (L.$line)","/var/log/perfs_queue.log");
}



?>