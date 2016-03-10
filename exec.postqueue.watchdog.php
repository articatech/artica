<?php
$GLOBALS["FORCE"]=false;
$GLOBALS["ULIMITED"]=false;
$GLOBALS["VERBOSE2"]=false;
$GLOBALS["VERBOSE"]=false;
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.postfix.builder.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/framework/class.ini-frame.inc");

if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--unlimit#",implode(" ",$argv))){$GLOBALS["ULIMITED"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--verb2#",implode(" ",$argv))){$GLOBALS["VERBOSE2"]=true;}

if($argv[1]=="--tests"){$GLOBALS["VERBOSE"]=true;tests();die();}
scan();




function scan(){
	$q=new mysql();
	$unix=new unix();
	
	$TimeFile="/etc/artica-postfix/pids/exec.postqueue.watchdog.php.time";
	$pidfile="/etc/artica-postfix/pids/exec.postqueue.watchdog.php.pid";
	$unix=new unix();
	
	
	if(system_is_overloaded(basename(__FILE__))){die();}
	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["VERBOSE"]){echo "$pid already executed since {$timepid}Mn\n";}
		if(!$GLOBALS["FORCE"]){
			if($timepid<14){return;}
				$kill=$unix->find_program("kill");
				unix_system_kill_force($pid);
			}
		}
	
	@file_put_contents($pidfile, getmypid());
	if(!$GLOBALS["FORCE"]){
		if(!$GLOBALS["VERBOSE"]){
			$time=$unix->file_time_min($TimeFile);
			if($time<5){echo "Current {$time}Mn, require at least 5mn\n";return;}
		}
	}
	
		
	
	
	
	$postconf=$unix->find_program("postconf");
	if(!is_file($postconf)){return;}
	$nice=EXEC_NICE();
	$kill=$unix->find_program("kill");
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$SendMailCache="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".mail";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($time>10){
			unix_system_kill_force($pid);
		}else{
			return;
		}
	}
	
	
	
	$results=array();
	$sock=new sockets();
	
	$PostfixSMTPNotifs=unserialize(base64_decode($sock->GET_INFO("PostfixSMTPNotifs")));
	if(!isset($PostfixSMTPNotifs["ENABLED_WATCHDOG"])){$PostfixSMTPNotifs["ENABLED_WATCHDOG"]=0;}
	
	$sql="CREATE TABLE  IF NOT EXISTS `artica_events`.`postqueuep` (
	`zDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
	`servername` VARCHAR( 255 ) NOT NULL ,
	`queuesize` bigint(255) unsigned NOT NULL ,
	`queuenum` bigint(255) unsigned NOT NULL ,
	INDEX ( `zDate` , `queuesize` , `queuenum`),
	KEY `servername` (`servername`)
	) ENGINE=MyISAM;
	";
	$q->QUERY_SQL($sql,"artica_events");	
	if(!$q->ok){echo $q->mysql_error;}
	$hostname=$unix->hostname_g();
	$max_messages=$PostfixSMTPNotifs["max_messages"];


	$postqueue=$unix->find_program("postqueue");
	if($GLOBALS["VERBOSE"]){echo "$nice $postqueue -p|grep -E \"[0-9]+\s+Requests\" 2>&1\n";}
	exec(trim("$nice $postqueue -p|grep -E \"[0-9]+\s+Requests\" 2>&1"),$results);
	
	if($GLOBALS["VERBOSE"]){echo "found ". count($results)." lines.\n";}
	
	while (list ($num, $ligne) = each ($results) ){
		if(!preg_match("#([0-9]+)\s+(.*?)\s+in\s+([0-9]+)\s+Requests#", $ligne,$re)){
			if($GLOBALS["VERBOSE"]){echo "$ligne no match\n";}
			continue;}
		if($re[2]<>"Kbytes"){system_admin_events("{$re[2]} Unable to understand this format",__FUNCTION__,__FILE__,__LINE__);}
		$size=$re[1];
		$kbytes=$re[2];
		$requests=$re[3];
		$time=date("Y-m-d H:i:s");
		
		if($GLOBALS["VERBOSE"]){echo "{$requests} requests/$max_messages\n";}
		
		if($requests>=$max_messages){
			if($PostfixSMTPNotifs["ENABLED_WATCHDOG"]==1){
				$time=$unix->file_time_min($SendMailCache);
				if($time>15){
					if($GLOBALS["VERBOSE"]){echo "Queue exceed $requests/$max_messages -> Send Mail\n";}
					$unix->SendEmailConfigured($PostfixSMTPNotifs,"Queue exceed $requests/$max_messages","Your server reach the max messages in it's queue, come on!");
				}
			}
		}
		$sql="INSERT IGNORE INTO postqueuep (zDate,servername,queuesize,queuenum) VALUES ('$time','$hostname','$size','$requests')";
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){
			@mkdir("/var/log/artica-postfix/postqueuep_error",0755,true);
			@file_put_contents("/var/log/artica-postfix/postqueuep_error/".md5($sql), $sql);
		}
	}
	
}

function tests(){
	$unix=new unix();
	$sock=new sockets();
	$PostfixSMTPNotifs=unserialize(base64_decode($sock->GET_INFO("PostfixSMTPNotifs")));
	if($unix->SendEmailConfigured($PostfixSMTPNotifs,"Queue exceed xxx/yyy","Your server reach the max messages in it's queue, come on!")){
		echo "SUCCESS\n";
		return;
	}
	echo "FAILED\n";
	
}

