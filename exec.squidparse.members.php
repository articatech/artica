#!/usr/bin/php -q
<?php
$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
if($EnableIntelCeleron==1){die("EnableIntelCeleron==1\n");}
ini_set('memory_limit','1000M');
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.realtime-buildsql.inc");
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.influx.inc");
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
$GLOBALS["LogFileDeamonLogDir"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/LogFileDeamonLogDir");
if($GLOBALS["LogFileDeamonLogDir"]==null){$GLOBALS["LogFileDeamonLogDir"]="/home/artica/squid/realtime-events";}
if(is_file("/usr/local/ArticaStats/bin/postgres")){
	$GLOBALS["LogFileDeamonLogDir"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/LogFileDeamonLogPostGresDir");
	if($GLOBALS["LogFileDeamonLogDir"]==null){$GLOBALS["LogFileDeamonLogDir"]="/home/artica/squid-postgres/realtime-events";}
}


if(preg_match("#--verbose#",implode(" ",$argv))){echo "VERBOSED....\n";$GLOBALS["VERBOSE"]=true;$GLOBALS["TRACE_INFLUX"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if($argv[1]=="--rtt"){RTT_TOMYSQL();}
if($argv[1]=="--hour"){HOUR_TOMYSQL();}


function GET_KEY($user,$mac,$ipaddr){
	if($user<>null){return $user;}
	if($mac=="00:00:00:00:00:00"){$mac=null;}
	if($mac<>null){return $mac;}
	return $ipaddr;
	
}

function HOUR_TOMYSQL(){
	$unix=new unix();
	$GLOBALS["MYHOSTNAME_PROXY"]=$unix->hostname_g();
	
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	$pid=$unix->get_pid_from_file($pidFile);
	if($unix->process_exists($pid)){events("A process, $pid Already exists...");return;}
	
	@file_put_contents($pidFile, getmypid());
	
	$time=$unix->file_time_min($pidtime);
	if(!$GLOBALS["VERBOSE"]){if($time<59){events("{$time}mn, require minimal 60mn");return;}}
	
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	
	$workfile="{$GLOBALS["LogFileDeamonLogDir"]}/USERS_LOG.work";
	if(!is_file($workfile)){
		@copy("{$GLOBALS["LogFileDeamonLogDir"]}/USERS_LOG", "{$GLOBALS["LogFileDeamonLogDir"]}/USERS_LOG.work");
		@unlink("{$GLOBALS["LogFileDeamonLogDir"]}/USERS_LOG");
	}
	
	if(is_file("$workfile.last")){$LastScannLine=intval(@file_get_contents("$workfile.last"));}
	
	$handle = @fopen("{$GLOBALS["LogFileDeamonLogDir"]}/USERS_LOG.work", "r");
	if(!$handle){return;}
	if($LastScannLine>0){fseek($handle, $LastScannLine, SEEK_SET);}
	
	//
	//$iSeek = ftell($handle);
	//@file_put_contents("$workfile.last", $iSeek);
	
	$FIRST_TIME=0;
	$CZ=0;
	while (!feof($handle)){
		$CZ++;
		$buffer =trim(fgets($handle));
		if($buffer==null){continue;}
		$MEM=array();
		$ARRAY=explode(":::",$buffer);
	
		$TIME=$ARRAY[0];
		$username=$ARRAY[1];
		$IPADDR=$ARRAY[2];
		$MAC=$ARRAY[3];
		$SIZE=$ARRAY[4];
		$RQS=$ARRAY[5];
		$ROUTER=$ARRAY[6];
		$HOURLY=date("Y-m-d H:00:00",$TIME);
		$KEY=md5(GET_KEY($username,$MAC,$IPADDR).$ROUTER.$HOURLY);
		
		
	
		if(!isset($GLOBALARRAY[$KEY])){
			$GLOBALARRAY[$KEY]["TIME"]=$HOURLY;
			$GLOBALARRAY[$KEY]["MAC"]=$MAC;
			$GLOBALARRAY[$KEY]["USERID"]=$username;
			$GLOBALARRAY[$KEY]["IPADDR"]=$IPADDR;
			$GLOBALARRAY[$KEY]["SIZE"]=intval($SIZE);
			$GLOBALARRAY[$KEY]["RQS"]=intval($RQS);
		}else{
			$GLOBALARRAY[$KEY]["TIME"]=$HOURLY;
			$GLOBALARRAY[$KEY]["MAC"]=$MAC;
			$GLOBALARRAY[$KEY]["USERID"]=$username;
			$GLOBALARRAY[$KEY]["IPADDR"]=$IPADDR;
			$GLOBALARRAY[$KEY]["SIZE"]=$GLOBALARRAY[$KEY]["SIZE"]+intval($SIZE);
			$GLOBALARRAY[$KEY]["RQS"]=$GLOBALARRAY[$KEY]["RQS"]+intval($RQS);
		}
	}
	@fclose($handle);
	@unlink($workfile);
	@unlink("$workfile.last");
	if(count($GLOBALARRAY)==0){return;}
	$q=new mysql_squid_builder();
	
	
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `dashboard_historyusers` (
			`zDate` DATETIME,
			`USER` VARCHAR(128),
			`MAC` VARCHAR(128),
			`IPADDR` VARCHAR(128),
			`SIZE` BIGINT UNSIGNED,
			`RQS` BIGINT UNSIGNED,
			KEY `USER` (`USER`),
			KEY `MAC` (`MAC`),
			KEY `IPADDR` (`IPADDR`),
			KEY `SIZE` (`SIZE`),
			KEY `RQS` (`RQS`)
			) ENGINE=MYISAM;"
	);
	
	
	while (list ($KEYMD5, $ARRAY) = each ($GLOBALARRAY)){
		$DATE=$ARRAY["TIME"];
		$ARRAY["USERID"]=mysql_escape_string2($ARRAY["USERID"]);
		$f[]="('$DATE','{$ARRAY["USERID"]}','{$ARRAY["IPADDR"]}','{$ARRAY["MAC"]}','{$ARRAY["RQS"]}','{$ARRAY["SIZE"]}')";
	
		if(count($f)>500){
			$sql="INSERT IGNORE INTO dashboard_historyusers (`zDate`,`USER`,`IPADDR`,`MAC`,`RQS`,`SIZE`) VALUES ".@implode(",", $f);
			$q->QUERY_SQL($sql);
			$f=array();
		}
	}
	
	if(count($f)>0){
		$sql="INSERT IGNORE INTO dashboard_historyusers (`zDate`,`USER`,`IPADDR`,`MAC`,`RQS`,`SIZE`) VALUES ".@implode(",", $f);
		$q->QUERY_SQL($sql);
		$f=array();
	}
		
	RTT_TOMYSQL(true);
	
}

function RTT_TOMYSQL($nopid=false){
	$unix=new unix();
	$GLOBALS["MYHOSTNAME_PROXY"]=$unix->hostname_g();
	
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if(!$nopid){
		$pid=$unix->get_pid_from_file($pidFile);
		if($unix->process_exists($pid)){events("A process, $pid Already exists...");return;}
		@file_put_contents($pidFile, getmypid());
		$time=$unix->file_time_min($pidtime);
		if(!$GLOBALS["VERBOSE"]){if($time<15){events("{$time}mn, require minimal 15mn");return;}}
	}
	
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	if(!is_file("{$GLOBALS["LogFileDeamonLogDir"]}/USERS_LOG")){return;}
	$handle = @fopen("{$GLOBALS["LogFileDeamonLogDir"]}/USERS_LOG", "r");
	if(!$handle){return;}
	$FIRST_TIME=0;
	$CZ=0;
	while (!feof($handle)){
		$CZ++;
		$buffer =trim(fgets($handle));
		if($buffer==null){continue;}
		$MEM=array();
		$ARRAY=explode(":::",$buffer);
		$TIME=$ARRAY[0];
		$username=$ARRAY[1];
		$IPADDR=$ARRAY[2];
		$MAC=$ARRAY[3];
		$SIZE=$ARRAY[4];
		$RQS=$ARRAY[5];
		$ROUTER=$ARRAY[6];
		$KEY=md5(GET_KEY($username,$MAC,$IPADDR).$ROUTER);
		
		if(!isset($GLOBALARRAY[$KEY])){
			$GLOBALARRAY[$KEY]["MAC"]=$MAC;
			$GLOBALARRAY[$KEY]["USERID"]=$username;
			$GLOBALARRAY[$KEY]["IPADDR"]=$IPADDR;
			$GLOBALARRAY[$KEY]["SIZE"]=intval($SIZE);
			$GLOBALARRAY[$KEY]["RQS"]=intval($RQS);
		}else{
			$GLOBALARRAY[$KEY]["MAC"]=$MAC;
			$GLOBALARRAY[$KEY]["USERID"]=$username;
			$GLOBALARRAY[$KEY]["IPADDR"]=$IPADDR;
			$GLOBALARRAY[$KEY]["SIZE"]=$GLOBALARRAY[$KEY]["SIZE"]+intval($SIZE);
			$GLOBALARRAY[$KEY]["RQS"]=$GLOBALARRAY[$KEY]["RQS"]+intval($RQS);
		}
	}
	@fclose($handle);
	if(count($GLOBALARRAY)==0){return;}
	$q=new mysql_squid_builder();
	
	
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `dashboard_currentusers` (
			`USER` VARCHAR(128),
			`MAC` VARCHAR(128),
			`IPADDR` VARCHAR(128),
			`SIZE` BIGINT UNSIGNED,
			`RQS` BIGINT UNSIGNED,
			KEY `USER` (`USER`),
			KEY `MAC` (`MAC`),
			KEY `IPADDR` (`IPADDR`),
			KEY `SIZE` (`SIZE`),
			KEY `RQS` (`RQS`)
			) ENGINE=MYISAM;"
	);
	
	$q->QUERY_SQL("TRUNCATE TABLE dashboard_currentusers");
	while (list ($KEYMD5, $ARRAY) = each ($GLOBALARRAY)){
		$ARRAY["USERID"]=mysql_escape_string2($ARRAY["USERID"]);
		$f[]="('{$ARRAY["USERID"]}','{$ARRAY["MAC"]}','{$ARRAY["IPADDR"]}','{$ARRAY["RQS"]}','{$ARRAY["SIZE"]}')";

		if(count($f)>500){
			$sql="INSERT IGNORE INTO dashboard_currentusers (`USER`,`MAC`,`IPADDR`,`RQS`,`SIZE`) VALUES ".@implode(",", $f);
			$q->QUERY_SQL($sql);
			if(!$q->ok){return;}
			$f=array();
		}
	}
	
	if(count($f)>0){
		$sql="INSERT IGNORE INTO dashboard_currentusers (`USER`,`MAC`,`IPADDR`,`RQS`,`SIZE`) VALUES ".@implode(",", $f);
		$q->QUERY_SQL($sql);
		$f=array();
	}	
	
	
}
function events($text=null){
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[0])){$file=basename($trace[0]["file"]);$function=$trace[0]["function"];$line=$trace[0]["line"];}
		if(isset($trace[1])){$file=basename($trace[1]["file"]);$function=$trace[1]["function"];$line=$trace[1]["line"];}
	}
	$logFile="/var/log/artica-parse.hourly.log";

	$suffix=date("Y-m-d H:i:s")." [".basename(__FILE__)."/$function/$line]:";
	if($GLOBALS["VERBOSE"]){echo "$suffix $text\n";}

	if (is_file($logFile)) {$size=filesize($logFile);if($size>1000000){@unlink($logFile);}}
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$suffix $text\n");
	@fclose($f);
}
