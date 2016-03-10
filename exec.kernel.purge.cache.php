#!/usr/bin/php -q
<?php

include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);


$filecache="/etc/artica-postfix/pids/".basename(__FILE__);
$unix=new unix();

$sock=new sockets();

$MonitMemPurgeCacheLevelCycles=intval($sock->GET_INFO("MonitMemPurgeCacheLevelCycles"));
if($MonitMemPurgeCacheLevelCycles==0){$MonitMemPurgeCacheLevelCycles=15;}


$filetime=$unix->file_time_min($filecache);

if($filetime<$MonitMemPurgeCacheLevelCycles){
	echo "Current {$filetime}mn, need {$MonitMemPurgeCacheLevelCycles}mn minimal\n";
	die();
}
@unlink($filecache);
@file_put_contents($filecache, time());
		
$TOTAL_MEM_POURCENT_USED=$unix->TOTAL_MEM_POURCENT_USED();
$ps_mem_report=$unix->ps_mem_report();
$GLOBALS["SYNCBIN"]=$unix->find_program("sync");
$GLOBALS["ECHOBIN"]=$unix->find_program("echo");
$GLOBALS["RMBIN"]=$unix->find_program("rm");



$tmpfile=$unix->FILE_TEMP();
$SH[]="#!/bin/sh";
$SH[]="{$GLOBALS["SYNCBIN"]}";
$SH[]="{$GLOBALS["ECHOBIN"]} 3 > /proc/sys/vm/drop_caches";
$SH[]="{$GLOBALS["SYNCBIN"]}";
$SH[]="{$GLOBALS["SYNCBIN"]}";
$SH[]="/etc/init.d/mysql restart >/dev/null 2>&1";
$SH[]="/etc/init.d/artica-framework restart  >/dev/null 2>&1";

$squidbin=$unix->LOCATE_SQUID_BIN();
if(is_file($squidbin)){
	$SH[]="/etc/init.d/proxy-db restart  >/dev/null 2>&1";
	$SH[]="$squidbin -k reconfigure  >/dev/null 2>&1";
}

$SH[]="{$GLOBALS["RMBIN"]} -f $tmpfile.sh";
$SH[]="";

squid_admin_mysql(0,"System memory Free caches kernel memory and restart some services",null,__FILE__,__LINE__);

@file_put_contents("$tmpfile.sh", @implode("\n", $SH));
@chmod("$tmpfile.sh",0755);
shell_exec("$tmpfile.sh >/dev/null 2>&1 &");

$TOTAL_MEM_POURCENT_USED2=$unix->TOTAL_MEM_POURCENT_USED();
if($TOTAL_MEM_POURCENT_USED<$TOTAL_MEM_POURCENT_USED2){
	squid_admin_mysql(1,
	"System memory {$TOTAL_MEM_POURCENT_USED}% reduced to {$TOTAL_MEM_POURCENT_USED2}% - Free caches kernel memory",
	"Timeout {$filetime}Mn\nYou will find here a snapshot of current tasks\n".
	$ps_mem_report,__FILE__,__LINE__);			
}
	



?>