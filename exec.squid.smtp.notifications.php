<?php
if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }
$BASEDIR="/usr/share/artica-postfix";
$GLOBALS["PROGRESS"]=false;
include_once($BASEDIR. '/ressources/class.users.menus.inc');
include_once($BASEDIR. '/ressources/class.sockets.inc');
include_once($BASEDIR. '/framework/class.unix.inc');
include_once($BASEDIR. '/framework/frame.class.inc');
include_once($BASEDIR. '/ressources/class.iptables-chains.inc');
include_once($BASEDIR. '/ressources/class.mysql.haproxy.builder.php');
include_once($BASEDIR. "/ressources/class.mysql.squid.builder.php");
include_once($BASEDIR. "/ressources/class.mysql.builder.inc");
include_once($BASEDIR. "/ressources/smtp/class.phpmailer.inc");
include_once($BASEDIR. '/ressources/class.mail.inc');

if($argv[1]=="--test-notif"){test_notif();exit;}


squid_admin_notifs_check();

function squid_admin_notifs_check($nopid=false){
	$f=array();
	$unix=new unix();
	$sock=new sockets();
	
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){$time=$unix->PROCCESS_TIME_MIN($pid);return;}
	@file_put_contents($pidfile, getmypid());

	$BaseWorkDir="{$GLOBALS["ARTICALOGDIR"]}/squid_admin_notifs";
	if(!is_dir($BaseWorkDir)){return;}
	if (!$handle = opendir($BaseWorkDir)) {return;}

	
	$UfdbguardSMTPNotifs=unserialize(base64_decode($sock->GET_INFO("UfdbguardSMTPNotifs")));
	if(!isset($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"])){$UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]=0;}
	if(!is_numeric($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"])){$UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]=0;}
	if($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]==0){removeall();return;}
	


	if(!isset($UfdbguardSMTPNotifs["smtp_dest"])){return;}
	if(!isset($UfdbguardSMTPNotifs["smtp_sender"])){$UfdbguardSMTPNotifs["smtp_sender"]=null;}




	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		if($unix->file_time_min($targetFile)>240){@unlink($targetFile);continue;}


		$array=unserialize(@file_get_contents($targetFile));

		if(!is_array($array)){@unlink($targetFile);continue;}
		if(!is_numeric($array["TASKID"])){$array["TASKID"]=0;}


		$content=$array["text"];
		$content_array=explode("\n",$content);
		if(count($content_array)>0){
			for($i=0;$i<count($content_array);$i++){
				if(trim($content_array[$i])==null){continue;}
				if($GLOBALS["VERBOSE"]){echo "Strip `{$content_array[$i]}` line ".__LINE__."\n";}
				$subject=substr($content_array[$i],0,75)."...";
				break;
			}
			$content=@implode("\r\n", $content_array);
		}else{
			if($GLOBALS["VERBOSE"]){echo "Strip `{$content}`\n";}
			$subject=substr($content,0,75)."...";
		}

		unset($array["text"]);
		$content=$content."\r\n------------------------------------------\r\n";
		while (list ($key, $value) = each ($array) ){
			$content=$content."$key.....: $value\r\n";
				
		}


		$subject="[".$unix->hostname_g()."]: $subject";
		if(!SendMessage($subject,$content,$UfdbguardSMTPNotifs)){continue;}
		@unlink($targetFile);

	}
}

function removeall(){
	$BaseWorkDir="{$GLOBALS["ARTICALOGDIR"]}/squid_admin_notifs";
	if (!$handle = opendir($BaseWorkDir)) {return;}
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		@unlink($targetFile);
	}
}


function test_notif(){
	
	build_progress(15,"Send a test message...");
	$sock=new sockets();
	$users=new usersMenus();
	$UfdbguardSMTPNotifs=unserialize(base64_decode($sock->GET_INFO("UfdbguardSMTPNotifs")));
	
	$GLOBALS["PROGRESS"]=true;
	SendMessage("This is a test message ","This is the body message",$UfdbguardSMTPNotifs);
	
}

function build_progress($pourc,$text){
	if(!$GLOBALS["PROGRESS"]){return;}
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.watchdpg.smtp.progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	sleep(1);

}

function SendMessage($subject,$content,$UfdbguardSMTPNotifs){
	
	$smtp_dest=$UfdbguardSMTPNotifs["smtp_dest"];
	$smtp_sender=$UfdbguardSMTPNotifs["smtp_sender"];
	
	build_progress(15,"From $smtp_sender");
	build_progress(20,"To $smtp_dest");
	
	if($smtp_dest==null){
		build_progress(110,"To !!! {failed}");
		return true;}
	if($smtp_sender==null){
		$unix=new unix();
		$smtp_sender="proxy@".$unix->hostname_g();
	}
	
	
	
	$mail = new PHPMailer(true);
	$mail->IsSMTP();
	$mail->AddAddress($smtp_dest,$smtp_dest);
	$mail->AddReplyTo($smtp_sender,$smtp_sender);
	$mail->From=$smtp_sender;
	$mail->FromName=$smtp_sender;
	$mail->Subject=$subject;
	$mail->Body=$content;
	$mail->Host=$UfdbguardSMTPNotifs["smtp_server_name"];
	$mail->Port=$UfdbguardSMTPNotifs["smtp_server_port"];
	
	if(($UfdbguardSMTPNotifs["smtp_auth_user"]<>null) && ($UfdbguardSMTPNotifs["smtp_auth_passwd"]<>null)){
		build_progress(30,"Authenticate as {$UfdbguardSMTPNotifs["smtp_auth_user"]}");
		$mail->SMTPAuth=true;
		$mail->Username=$UfdbguardSMTPNotifs["smtp_auth_user"];
		$mail->Password=$UfdbguardSMTPNotifs["smtp_auth_passwd"];
		if($UfdbguardSMTPNotifs["tls_enabled"]==1){$mail->SMTPSecure = 'tls';}
		if($UfdbguardSMTPNotifs["ssl_enabled"]==1){$mail->SMTPSecure = 'ssl';}
	}	
	build_progress(40,"{sending_message}");
	if(!$mail->Send()){
		build_progress(110,"{failed}");
		$function=__FUNCTION__;
		$line=__LINE__;
		if(!function_exists("syslog")){return false;}
		$file=basename(__FILE__);
		$LOG_SEV=LOG_INFO;
		openlog($file, LOG_PID , LOG_SYSLOG);
		syslog($LOG_SEV, "**** FATAL SMTP FAILED *** [$function/$line] $mail->MyFile");
		closelog();
		return false;
	}
	build_progress(100,"{success}");
	
	
}


