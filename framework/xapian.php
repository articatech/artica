<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["exec-mysql"])){execute_mysql();exit;}
if(isset($_GET["httptrack"])){httptrack();exit;}
if(isset($_GET["httptrack-id"])){httptrack_simple();exit;}
if(isset($_GET["xapian-db-size"])){localdbsize();exit;}
if(isset($_GET["DeleteDatabasePath"])){DeleteDatabasePath();exit;}
if(isset($_GET["httptrack-progress"])){httptrack_progress();exit;}




writelogs_framework("Unable to understand the query ".@implode(" ",$_GET),__FUNCTION__,__FILE__,__LINE__);	




function execute_mysql(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.xapian.index.php --mysql-dirs >/dev/null &");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);
}
function httptrack(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.httptrack.php >/dev/null &");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);	
}
function httptrack_simple(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.httptrack.php --simple {$_GET["httptrack-id"]} >/dev/null &");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);
	writelogs_framework("Done",__FUNCTION__,__FILE__,__LINE__);	
}

function localdbsize(){
	$unix=new unix();
	$cachefile="/usr/share/artica-postfix/LocalDatabases/dbsize.xp";
	if(is_file($cachefile)){$time=$unix->file_time_min($cachefile);if($time>30){@unlink($cachefile);}}
	if(!is_file($cachefile)){
		$size=$unix->DIRSIZE_KO("/usr/share/artica-postfix/LocalDatabases");
		if($size>1000){@file_put_contents($cachefile, $size);}
	}else{
		$size=@file_get_contents($cachefile);
	}
	
	echo "<articadatascgi>$size</articadatascgi>";
	
}

function httptrack_progress(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/freewebs.HTTrack.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/freewebs.HTTrack.progress.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.httptrack.php --progress >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}


function DeleteDatabasePath(){
	$DeleteDatabasePath=base64_decode($_GET["DeleteDatabasePath"]);
	if($DeleteDatabasePath==null){return;}
	if(!is_dir($DeleteDatabasePath)){return;}
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$rm=$unix->find_program("rm");
	$cmd=trim("$rm -rf $DeleteDatabasePath >/dev/null &");
	@unlink("/usr/share/artica-postfix/LocalDatabases/dbsize.xp");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);		
}

function mailboxes_scan_ou(){
	
	
}