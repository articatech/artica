<?php
$GLOBALS["COMMANDLINE"]=implode(" ",$argv);
if(strpos($GLOBALS["COMMANDLINE"],"--verbose")>0){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;$GLOBALS["DEBUG"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/framework/class.settings.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.syslog.inc");
include_once(dirname(__FILE__)."/ressources/class.familysites.inc");
include_once(dirname(__FILE__).'/ressources/class.mount.inc');
$GLOBALS["FORCE"]=false;
$GLOBALS["EXECUTED_AS_ROOT"]=true;
$GLOBALS["RUN_AS_DAEMON"]=false;
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["DISABLE_WATCHDOG"]=false;
if(preg_match("#--nowachdog#",$GLOBALS["COMMANDLINE"])){$GLOBALS["DISABLE_WATCHDOG"]=true;}
if(preg_match("#--force#",$GLOBALS["COMMANDLINE"])){$GLOBALS["FORCE"]=true;}
if(preg_match("#--verbose#",$GLOBALS["COMMANDLINE"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}




xrun();


function build_progress($text,$pourc){
	$echotext=$text;
	if(is_numeric($text)){$old=$pourc;$pourc=$text;$text=$old;}
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/postfix.events.search.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}

function xrun(){
	
	build_progress("{scanning} {files}",20);
	
	$BaseWorkDir="/home/postfix/logrotate";
	if (!$handle = opendir($BaseWorkDir)) {echo "Failed open $BaseWorkDir\n";return;}
	$MAIN=array();
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetfile="$BaseWorkDir/$filename";
		if(strpos($filename, ".gz")==0){continue;}
		$fileKey=str_replace(".gz", "", $filename);
		$MAIN[$fileKey]=$targetfile;
		
		
	}
	
	if(count($MAIN)==0){
		echo "No files to scan....\n";
		build_progress("{scanning} {files} {failed}",110);
		return;
	}
	
	ksort($MAIN);
	$sock=new sockets();
	$PostfixHistorySearch=$sock->GET_INFO("PostfixHistorySearch");
	if($PostfixHistorySearch==null){
		echo "No search pattern, aborting...\n";
		build_progress("{scanning} {files} {failed}",110);
		return;
	}
	
	if(!preg_match("#regex\s+(.+)#", $PostfixHistorySearch,$re)){
		
		$PostfixHistorySearch=str_replace(".", "\.", $PostfixHistorySearch);
		$PostfixHistorySearch=str_replace("*", ".*?", $PostfixHistorySearch);
		$PostfixHistorySearch=str_replace("[", "\[", $PostfixHistorySearch);
		$PostfixHistorySearch=str_replace("]", "\]", $PostfixHistorySearch);
		$PostfixHistorySearch=str_replace("(", "\(", $PostfixHistorySearch);
		$PostfixHistorySearch=str_replace(")", "\)", $PostfixHistorySearch);
		$PostfixHistorySearch=str_replace("/", "\/", $PostfixHistorySearch);
		
	}else{
		$PostfixHistorySearch=$re[1];
	}
	
	
	$unix=new unix();
	$zcat=$unix->find_program("zcat");
	$grep=$unix->find_program("grep");
	if(is_file("/usr/share/artica-postfix/ressources/logs/web/mail-history.log")){
		@unlink("/usr/share/artica-postfix/ressources/logs/web/mail-history.log");
	}
	$perc=20;
	while (list ($zDate, $filepath) = each ($MAIN) ){
		$perc=$perc+1;
		if($perc>95){$perc=95;}
		$size=@filesize("/usr/share/artica-postfix/ressources/logs/web/mail-history.log");
		$size=$size/1024;
		$size=round($size,2);
		echo "Scanning $filepath\n";
		build_progress("{scanning} $zDate for $PostfixHistorySearch ( {$size}KB )",$perc);
		$cmd="$zcat $filepath | $grep -E '$PostfixHistorySearch' >>/usr/share/artica-postfix/ressources/logs/web/mail-history.log 2>&1";
		echo "$cmd\n";
		shell_exec($cmd);
		
		
	}
	
	$countlines=$unix->COUNT_LINES_OF_FILE("/usr/share/artica-postfix/ressources/logs/web/mail-history.log");
	$size=@filesize("/usr/share/artica-postfix/ressources/logs/web/mail-history.log");
	$size=$size/1024;
	$size=round($size,2);
	build_progress("{scanning} {done} $countlines {lines} {$size}KB",100);
	@chmod("/usr/share/artica-postfix/ressources/logs/web/mail-history.log",0755);
	
}