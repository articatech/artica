<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
$GLOBALS["VERBOSE"]=false;
$GLOBALS["FORCE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}

$unix=new unix();

$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
$unix=new unix();
if($unix->process_exists(@file_get_contents("$pidfile"))){
	echo "Already process exists\n";
	return;
}

@file_put_contents($pidfile, getmypid());
$TimeExec=$unix->file_time_min($pidtime);
if(!$GLOBALS["FORCE"]){
	if($TimeExec==0){die();}
}
@unlink($pidtime);
@file_put_contents($pidtime, time());
$EnableMsftncsi=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableMsftncsi"));
$msftncsiBindIpAddress=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/msftncsiBindIpAddress"));
if($EnableMsftncsi==0){die();}
$interface=null;

$curl=$unix->find_program("curl");
if(!is_file($curl)){die();}

if($msftncsiBindIpAddress<>null){
	$MAIN=$unix->NETWORK_ALL_INTERFACES(true);
	if(!isset($MAIN[$msftncsiBindIpAddress])){$msftncsiBindIpAddress=null;}
}

if($msftncsiBindIpAddress<>null){
	$interface="--interface $msftncsiBindIpAddress ";
}

$pid=$unix->PIDOF_PATTERN("curl.*?msftncsi\.com");
if($unix->process_exists($pid)){die();}


$tmp_path=$unix->TEMP_DIR();
$tmp_trace="$tmp_path/curl.trace.txt";
$tmp_file="$tmp_path/curl.file.txt";

@unlink($tmp_trace);
@unlink($tmp_file);
$cmd="$curl --max-time 5 --connect-timeout 5 {$interface}--trace-ascii $tmp_trace  http://www.msftncsi.com/ncsi.txt --output $tmp_file 2>&1";
echo $cmd."\n";
shell_exec($cmd);
$Content=trim(@file_get_contents($tmp_file));
$trace=trim(@file_get_contents($tmp_trace));

@unlink($tmp_trace);
@unlink($tmp_file);

if($Content<>"Microsoft NCSI"){
	squid_admin_mysql(0, "Alert: Access to Internet failed!", $trace,__FILE__,__LINE__);
	@file_put_contents("/etc/artica-postfix/settings/Daemons/msftncsiStatus", 3);
	die();
}

@file_put_contents("/etc/artica-postfix/settings/Daemons/msftncsiStatus", 1);