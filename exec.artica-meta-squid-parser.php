<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["BYCRON"]=false;
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.meta_uuid.inc");

include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.realtime-buildsql.inc");
include_once(dirname(__FILE__)."/ressources/class.ocs.inc");
include_once(dirname(__FILE__)."/ressources/class.squidlogs.parser.inc");

//ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--bycron#",implode(" ",$argv))){$GLOBALS["BYCRON"]=true;}

$GLOBALS["LogFileDeamonLogDir"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/LogFileDeamonLogDir");
if($GLOBALS["LogFileDeamonLogDir"]==null){$GLOBALS["LogFileDeamonLogDir"]="/home/artica/squid/realtime-events";}
$GLOBALS["LogFileDeamonMaxInstances"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/LogFileDeamonMaxInstances"));
if($GLOBALS["LogFileDeamonMaxInstances"]==0){$GLOBALS["LogFileDeamonMaxInstances"]=3;}

if(is_file("/usr/local/ArticaStats/bin/postgres")){
	$GLOBALS["LogFileDeamonLogDir"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/LogFileDeamonLogPostGresDir");
	if($GLOBALS["LogFileDeamonLogDir"]==null){$GLOBALS["LogFileDeamonLogDir"]="/home/artica/squid-postgres/realtime-events";}
}

$GLOBALS["CLASS_UNIX"]=new unix();

if($argv[1]=="--daily"){daily_to_monthly($argv[2]);exit;}

ScanFiles();

function ScanFiles(){
	$GLOBALS["UUIDS"]=array();
	$pids=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN_ALL(basename(__FILE__));
	$MaxInstances=round($GLOBALS["LogFileDeamonMaxInstances"]*2);
	$GLOBALS["PREFIX_LOGSTATS"]="Instances ".count($pids)."/$MaxInstances";
	
	
	if(count($pids)>$MaxInstances){
		events("Running instances:".count($pids)." ".@implode(",", $pids) ." Too many instances agains {$GLOBALS["LogFileDeamonMaxInstances"]}*1.7=$MaxInstances SKIP TASK");
		while (list ($xpid, $ligne) = each ($pids) ){
			$cmdline=@file_get_contents("/proc/$xpid/cmdline");
			events("Running instance:$xpid $cmdline");
		}
		return;
	}
	

	$Files=$GLOBALS["CLASS_UNIX"]->DirFiles("/home/artica/squid/META_EVENTS_QUEUE");
	$MAX=count($Files);
	if($GLOBALS["VERBOSE"]){echo "$MAX files\n";}
	$c=0;
	while (list ($filename, $none) = each ($Files) ){
		$path="/home/artica/squid/META_EVENTS_QUEUE/$filename";
		$LOCK_FILE="/home/artica/squid/META_EVENTS_QUEUE/$filename.LCK";
		$Time=$GLOBALS["CLASS_UNIX"]->file_time_sec($path);
		$c++;
	
		if(preg_match("#\.LCK$#", $filename)){
			$filenameB=str_replace(".LCK", "", $filename);
			if(!is_file("/home/artica/squid/META_EVENTS_QUEUE/$filenameB")){@unlink($path);}
			continue;
		}
	
		if(isLocked($path)){continue;}
		@file_put_contents($LOCK_FILE,getmypid());
		meta_events("PARSING $path");
		if(!ParseFile($path)){
			meta_events("*** PARSE $path FAILED ****");
			if($GLOBALS["VERBOSE"]){echo "*** PARSE $path FAILED ****\n";}
			@unlink($LOCK_FILE);
			continue;
		}
		@unlink($path);
		@unlink($LOCK_FILE);
		
		
	}
	
	meta_events(count($GLOBALS["UUIDS"])." uuid to analyze");
	while (list ($uuid, $rows) = each ($GLOBALS["UUIDS"]) ){
		hourly_to_daily($uuid);
		daily_to_monthly($uuid);
	}
	
}


function ParseFile($tgz){
	$mysql=new mysql();
	if(!preg_match("#^(.+?)-[0-9]+-artica-php#", basename($tgz),$re)){
		meta_events("Unable to find uuid in $tgz");
		return false;
	}
	$uuid=$re[1];
	$f=array();
	$GLOBALS["UUIDS"][$uuid]=true;
	$q=new mysql_uuid_meta($uuid);
	
	$tmpfile=$GLOBALS["CLASS_UNIX"]->FILE_TEMP().".db";
	$unix=new unix();
	if(!$unix->uncompress($tgz, $tmpfile)){
		@unlink($tmpfile);
		meta_events("{$GLOBALS["UNCOMPRESSLOGS"]}");
		@unlink($tgz);
		return false;
	}
	
	$db_con = @dba_open($tmpfile, "r","db4");
	if(!$db_con){meta_events("Warning! DB open $tmpfile failed...");return false;}
	
	$mainkey=dba_firstkey($db_con);
	$c=0;
	$n=0;
	while($mainkey !=false){
		$data=dba_fetch($mainkey,$db_con);
		$Array=unserialize($data);
		if(!is_array($Array)){
			meta_events("$mainkey -> $data not an array...");
			$mainkey=dba_nextkey($db_con);
			continue;
		}
		
		$md5=md5(serialize($Array));
		$date=$Array["DATE"];
		$time=strtotime($date);
		$xtime=date("Y-m-d H:i:s",$time);
		$hits=$Array["HITS"];
		$size=$Array["SIZE"];
		$mac=$Array["MAC"];
		$uid=$Array["UID"];
		$ipaddr=$Array["IPADDR"];
		$website=$Array["website"];
		
		$tablename="squid_hourly_".date("YmdH",$time);
		$f[$tablename][]="('$md5','$xtime','$website','$mac','$uid','$ipaddr','$hits','$size')";
		
		if(count($f[$tablename])>2000){
			meta_events("$tablename -> ".count($f[$tablename]));
			$prefix="INSERT IGNORE INTO `$tablename` (`zdm5`,`zDate`,`sitename`,`mac`,`uid`,`ipaddr`,`hits`,`size`) VALUES ";
			if(!$q->create_squid_hourly($tablename)){return false;}
			$q->QUERY_SQL($prefix.@implode(",", $f[$tablename]));
			if(!$q->ok){
				meta_events($q->mysql_error);
				return false;}
			$f[$tablename]=array();
		}
		
		$mainkey=dba_nextkey($db_con);
		
	}
	
	if(count($f)>0){
		while (list ($tablename, $rows) = each ($f) ){
			meta_events("$tablename -> ".count($rows));
			$prefix="INSERT IGNORE INTO `$tablename` (`zmd5`,`zDate`,`sitename`,`mac`,`uid`,`ipaddr`,`hits`,`size`) VALUES ";
			if(!$q->create_squid_hourly($tablename)){return false;}
			$q->QUERY_SQL($prefix.@implode(",", $rows));
			if(!$q->ok){
				meta_events($q->mysql_error);
				return false;}
		}
		
	}
	
	
	return true;
	
	
}


function hourly_to_daily($uuid){
	
	$unix=new unix();
	$filetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".$uuid.time";
	if($unix->file_time_min($filetime)<60){return;}
	@unlink($filetime);
	@file_put_contents($filetime, time());
	
	
	$q=new mysql_uuid_meta($uuid);
	
	
	$LIST_TABLES_SQUID_HOURLY=$q->LIST_TABLES_SQUID_HOURLY();
	$CurrentTable="squid_hourly_".date("YmdH");
	
	if($GLOBALS["VERBOSE"]){echo "CURRENT TABLE = $CurrentTable\n";}
	
	while (list ($tablename, $rows) = each ($LIST_TABLES_SQUID_HOURLY) ){
		
		if($GLOBALS["VERBOSE"]){echo "CURRENT TABLE = $CurrentTable <> $tablename\n";}
		if($tablename==$CurrentTable){continue;}
		if(!_hourly_to_daily($tablename,$uuid)){continue;}
		$q->QUERY_SQL("DROP TABLE `$tablename`");
	}
	
}

function daily_to_monthly($uuid){
	$unix=new unix();
	$filetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".$uuid.time";
	if(!$GLOBALS["FORCE"]){if($unix->file_time_min($filetime)<240){return;}}
	@unlink($filetime);
	@file_put_contents($filetime, time());
	$q=new mysql_uuid_meta($uuid);
	
	
	$LIST_TABLES_SQUID_DAILY=$q->LIST_TABLES_SQUID_DAILY();
	meta_events(count($LIST_TABLES_SQUID_DAILY)." Daily tables...");
	$CurrentTable="squid_daily_".date("Ymd");
	if($GLOBALS["VERBOSE"]){echo "CURRENT TABLE = $CurrentTable\n";}
	while (list ($tablename, $rows) = each ($LIST_TABLES_SQUID_DAILY) ){
	
		if($GLOBALS["VERBOSE"]){echo "CURRENT TABLE = $CurrentTable <> $tablename\n";}
		if($tablename==$CurrentTable){continue;}
		if(!_daily_to_monthly($tablename,$uuid)){continue;}
		$q->QUERY_SQL("DROP TABLE `$tablename`");
	}
	
}

function _daily_to_monthly($tablename,$uuid){
	$q=new mysql_uuid_meta($uuid);
	$f=array();
	$sql="SELECT DATE_FORMAT(`zDate`,'%Y-%m-%d') as `zDate`,DATE_FORMAT(`zDate`,'%Y%m') as `NextDate`,`sitename`,
	`mac`,`uid`,`ipaddr`,SUM(`hits`) as `hits`,SUM(`size`) as `size` FROM $tablename
	GROUP BY `sitename`,`mac`,`uid`,`ipaddr`,DATE_FORMAT(`zDate`,'%Y-%m-%d'),DATE_FORMAT(`zDate`,'%Y%m%d')";

	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){


	$md5=md5(serialize($ligne));
	$date=$ligne["zDate"];
		$hits=$ligne["hits"];
		$size=$ligne["size"];
		$mac=$ligne["mac"];
		$uid=$ligne["uid"];
		$ipaddr=$ligne["ipaddr"];
		$sitename=$ligne["sitename"];
		$Nexttablename="squid_monthly_".$ligne["NextDate"];

		$f[$Nexttablename][]="('$md5','$date','$sitename','$mac','$uid','$ipaddr','$hits','$size')";
		if(count($f[$Nexttablename])>2000){
		$prefix="INSERT IGNORE INTO `$Nexttablename` (`zdm5`,`zDate`,`sitename`,`mac`,`uid`,`ipaddr`,`hits`,`size`) VALUES ";
				if(!$q->create_squid_hourly($Nexttablename)){return false;}
				if(!$q->TABLE_EXISTS($Nexttablename)){return false;}
				$q->QUERY_SQL($prefix.@implode(",", $f[$Nexttablename]));
				if(!$q->ok){
					meta_events($q->mysql_error);
					return false;
				}
				$f[$Nexttablename]=array();
	}
	}


	if(count($f)>0){
	while (list ($tablename, $rows) = each ($f) ){
	$prefix="INSERT IGNORE INTO `$tablename` (`zmd5`,`zDate`,`sitename`,`mac`,`uid`,`ipaddr`,`hits`,`size`) VALUES ";
			if(!$q->create_squid_hourly($tablename)){return false;}
			if(!$q->TABLE_EXISTS($tablename)){return false;}
			$q->QUERY_SQL($prefix.@implode(",", $rows));
			if(!$q->ok){return false;}
	}

	}

	return true;


}

function _hourly_to_daily($tablename,$uuid){
	$q=new mysql_uuid_meta($uuid);
	$f=array();
	$sql="SELECT DATE_FORMAT(`zDate`,'%Y-%m-%d') as `zDate`,DATE_FORMAT(`zDate`,'%Y%m%d') as `NextDate`,`sitename`,
	`mac`,`uid`,`ipaddr`,SUM(`hits`) as `hits`,SUM(`size`) as `size` FROM $tablename 
	GROUP BY `sitename`,`mac`,`uid`,`ipaddr`,DATE_FORMAT(`zDate`,'%Y-%m-%d'),DATE_FORMAT(`zDate`,'%Y%m%d')";
	
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		
		
		$md5=md5(serialize($ligne));
		$date=$ligne["zDate"];
		$hits=$ligne["hits"];
		$size=$ligne["size"];
		$mac=$ligne["mac"];
		$uid=$ligne["uid"];
		$ipaddr=$ligne["ipaddr"];
		$sitename=$ligne["sitename"];
		$Nexttablename="squid_daily_".$ligne["NextDate"];
		
		$f[$Nexttablename][]="('$md5','$date','$sitename','$mac','$uid','$ipaddr','$hits','$size')";
		if(count($f[$Nexttablename])>2000){
			$prefix="INSERT IGNORE INTO `$Nexttablename` (`zdm5`,`zDate`,`sitename`,`mac`,`uid`,`ipaddr`,`hits`,`size`) VALUES ";
			if(!$q->create_squid_hourly($Nexttablename)){return false;}
			if(!$q->TABLE_EXISTS($Nexttablename)){return false;}
			$q->QUERY_SQL($prefix.@implode(",", $f[$Nexttablename]));
			if(!$q->ok){return false;}
			$f[$Nexttablename]=array();
		}
	}
	
	
	if(count($f)>0){
		while (list ($tablename, $rows) = each ($f) ){
			$prefix="INSERT IGNORE INTO `$tablename` (`zmd5`,`zDate`,`sitename`,`mac`,`uid`,`ipaddr`,`hits`,`size`) VALUES ";
			if(!$q->create_squid_hourly($tablename)){return false;}
			if(!$q->TABLE_EXISTS($tablename)){return false;}
			$q->QUERY_SQL($prefix.@implode(",", $rows));
			if(!$q->ok){return false;}
		}
	
	}
	
	return true;
	
	
}



function events($text){
	
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();

		if(isset($trace[0])){
			$file=basename($trace[0]["file"]);
			$function=$trace[0]["function"];
			$line=$trace[0]["line"];
		}

		if(isset($trace[1])){
			$file=basename($trace[1]["file"]);
			$function=$trace[1]["function"];
			$line=$trace[1]["line"];
		}



	}
	
	
	
	$GLOBALS["CLASS_UNIX"]->events($text,"/var/log/artica-meta.log",false,$function,$line,$file);

}


function isLocked($path){
	$LOCK_FILE="$path.LCK";
	if(!is_file($LOCK_FILE)){return false;}
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	$timeexec=$GLOBALS["CLASS_UNIX"]->file_time_min($LOCK_FILE);
	if($timeexec<5){return true;}


	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($LOCK_FILE);
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){
		events("$LOCK_FILE still locked by pid $pid since {$timeexec}min");
		return true;
	}
	@unlink($LOCK_FILE);
	return false;
		


}

function meta_events($text){
	$unix=new unix();
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();

		if(isset($trace[0])){
			$file=basename($trace[0]["file"]);
			$function=$trace[0]["function"];
			$line=$trace[0]["line"];
		}

		if(isset($trace[1])){
			$file=basename($trace[1]["file"]);
			$function=$trace[1]["function"];
			$line=$trace[1]["line"];
		}



	}
	$unix->events($text,"/var/log/artica-meta.log",false,$function,$line,$file);

}