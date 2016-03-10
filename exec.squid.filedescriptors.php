<?php
ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
$GLOBALS["SCHEDULE_ID"]=0;
$GLOBALS["AD_PROGRESS"]=0;
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["ARGVS"]=implode(" ",$argv);
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if(preg_match("#--progress-activedirectory=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["AD_PROGRESS"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}

include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.acls.inc');

if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

xfiledesc();


function xfiledesc(){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$unix=new unix();
	$pid=@file_get_contents($pidfile);
	
	if($unix->process_exists($pid,basename(__FILE__))){
		squid_admin_mysql(0, "Cannot change file descriptors (PID $pid already executed)", null,__FILE__,__LINE__);
		die();
	}
	
	@file_put_contents($pidfile,getmypid());
	
	$TimePid=$unix->file_time_min($pidTime);
	if($TimePid<5){
		squid_admin_mysql(0, "Cannot change file descriptors ( require 5mn, current {$TimePid}mn)", null,__FILE__,__LINE__);
		die();
	}
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
		
	$squid=new squidbee();
	$sock=new sockets();
	$sysctl=$unix->find_program("sysctl");
	$t=time();
	if(!is_numeric($squid->max_filedesc)){$squid->max_filedesc=8192;}
	exec("$sysctl -n fs.file-max",$results);
	
	$file_max=intval(trim(@implode("",$results)));	
	$file_max_org=$file_max;
	$max_filedesc=intval($squid->max_filedesc);
	if($max_filedesc==0){$max_filedesc=8192;}
	
	$new_max_filedesc=$max_filedesc+1000;
	
	echo "Current System: $file_max, Proxy $max_filedesc\n";
	
	if($new_max_filedesc>$file_max-100){
		$file_max=$file_max+1000;
		shell_exec("$sysctl -w fs.file-max=$file_max");
		$unix->sysctl("fs.file-max",$file_max);
	}
	
	
	$squid->max_filedesc=$new_max_filedesc;
	$squid->SaveToLdap(true);
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php /usr/share/artica-postfix/exec.squid.php --build --force");
	squid_admin_mysql(0, "Restarting Proxy service to increase file descriptors from $max_filedesc/$file_max_org to $new_max_filedesc/$file_max", null,__FILE__,__LINE__);
	shell_exec("/etc/init.d/squid restart --force");

}