<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["iscron"])){is_cron();exit;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["wizard"])){wizard();exit;}
if(isset($_GET["sync-freewebs"])){sync_freewebs();exit;}
if(isset($_GET["access-events"])){access_events();exit;}
if(isset($_GET["freshclam-services"])){freshclam_service();exit;}
if(isset($_GET["freshclam-status"])){freshclam_status();exit;}


while (list ($num, $line) = each ($_GET)){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die();


function is_cron(){
	
	if(!is_file("/etc/cron.d/UpdateUtility")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/UpdateUtilityIsCron", 0);
		
	}
	@file_put_contents("/etc/artica-postfix/settings/Daemons/UpdateUtilityIsCron", 1);
	@chmod("/etc/artica-postfix/settings/Daemons/UpdateUtilityIsCron", 0755);
}


function install(){
	
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/UpdateUtility.install.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/UpdateUtility.install.progress.txt";
	
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.updateutility.install.php >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}
function wizard(){

	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/UpdateUtility.wizard.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/UpdateUtility.wizard.progress.txt";

	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.updateutility.wizard.php >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function freshclam_service(){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/clamav.freshclam.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/clamav.freshclam.progress.txt";
	
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.freshclam.php --restart --force --progress >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}



function freshclam_status(){
	
	writelogs_framework("Starting" ,__FUNCTION__,__FILE__,__LINE__);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$php5 /usr/share/artica-postfix/exec.status.php --freshclam >/usr/share/artica-postfix/ressources/logs/web/freshclam.status";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function pattern(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.haarp.php --squid-pattern >/dev/null 2>&1");	
	
}
function access_events(){
	
	$filename="/var/log/squid/haarp.access.log";
	$search=$_GET["access-events"];
	$unix=new unix();
	$search=$unix->StringToGrep($search);
	$grep=$unix->find_program("grep");
	$tail=$unix->find_program("tail");
	$refixcmd="$tail -n 2500 $filename";
	if($search<>null){
		$refixcmd=$refixcmd."|$grep -i -E '$search'|$tail -n 500";
	}else{
		$refixcmd="$tail -n 500 $filename";
	}
	
	
	exec($refixcmd." 2>&1",$results);
	writelogs_framework($refixcmd." (".count($results).")",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
	
}
