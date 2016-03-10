<?php
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__)."/ressources/class.mysql.syslogs.inc");

if($GLOBALS["VERBOSE"]){echo "varlog()\n";}
varlog();



function varlog(){
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."()\n";}
	$sock=new sockets();
	$unix=new unix();
	$Pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$PidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	$pid=$unix->get_pid_from_file($Pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		if($GLOBALS["VERBOSE"]){echo "Aborting Task already running pid $pid ".__FUNCTION__."()\n";} 
		return;
	}
	
	@file_put_contents($Pidfile, getmypid());
	$time=$unix->file_time_min($PidTime);
	if($GLOBALS["VERBOSE"]){echo "$PidTime\nLast execution {$time}Mn\n";}
	
	if(!$GLOBALS["VERBOSE"]){
		
		if($time<15){echo "Only each 15mn\n";die();}
	}
	
	@unlink($PidTime);
	@file_put_contents($PidTime, time());
	
	$LogsRotateDefaultSizeRotation=$sock->GET_INFO("LogsRotateDefaultSizeRotation");
	if(!is_numeric($LogsRotateDefaultSizeRotation)){$LogsRotateDefaultSizeRotation=100;}
	$LogsDirectoryStorage=$sock->GET_INFO("LogsDirectoryStorage");
	if(trim($LogsDirectoryStorage)==null){$LogsDirectoryStorage="/home/logs-backup";}
	$echo=$unix->find_program("echo");

	$syslog[]="/var/log/syslog";
	$syslog[]="/var/log/messages";
	$syslog[]="/var/log/daemon.log";
	$syslog[]="/var/log/auth.log";
	$syslog[]="/var/log/kern.log";
	$syslog[]="/var/log/user.log";
	$syslog[]="/var/log/mail.err";  
	$syslog[]="/var/log/mail.info";  
	$syslog[]="/var/log/mail.warn";
	
	
	$other[]="/var/log/php.log";
	$other[]="/var/log/artica-postfix/framework.log";
	$other[]="/var/log/artica-postfix/logrotate.debug";
	$other[]="/var/log/ArticaProc.log";
	$other[]="/var/log/squid/ufdbgclient.debug";
	$other[]="/var/log/squid/HyperCache-access.log";
	$other[]="/var/log/squid/HyperCache-error.log";
	$other[]="/var/log/squid/ext_time_quota_acl.log";
	$other[]="/var/log/squid/cache-nat.log";
	$other[]="/var/log/squid/sockd.log";
	$other[]="/var/log/influxdb/influxd.log";
	$other[]="/var/log/wanproxy/wanproxy.log";
	$other[]="/var/log/lighttpd/access.log";
	$other[]="/var/log/lighttpd/squidguard-lighttpd-error.log";
	$other[]="/var/log/lighttpd/squidguard-lighttpd.log";
	$other[]="/var/log/lighttpd/squidguard-lighttpd.start";
	$other[]="/var/log/lighttpd/apache-access.log";
	$other[]="/var/log/lighttpd/apache-error.log";
	

	$checks=array();
	$RESTART_SYSLOG=false;
	while (list ($index,$filepath) = each ($syslog)){
		if(!is_file("$filepath")){continue;}
		$size=(@filesize($filepath)/1024)/1000;
		if($GLOBALS["VERBOSE"]){echo "$filepath {$size}MB <> {$LogsRotateDefaultSizeRotation}M\n";}
		if($size>$LogsRotateDefaultSizeRotation){
			shell_exec("$echo \"\" >$filepath");
			$RESTART_SYSLOG=true;
		}
	}
	
	if($RESTART_SYSLOG){
		squid_admin_mysql(1, "Restarting Syslog after a rotation", null,__FILE__,__LINE__);
		$unix->RESTART_SYSLOG();
	}


	while (list ($index,$filepath) = each ($other)){
		if(!is_file("$filepath")){continue;}
		$size=(@filesize($filepath)/1024)/1000;
		if($GLOBALS["VERBOSE"]){echo "$filepath {$size}MB <> {$LogsRotateDefaultSizeRotation}M\n";}
		if($size>50){
			shell_exec("$echo \"\" >$filepath");
		}
	}
	
}