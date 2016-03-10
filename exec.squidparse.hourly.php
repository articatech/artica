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


if(preg_match("#--verbose#",implode(" ",$argv))){
		echo "VERBOSED....\n";
		$GLOBALS["VERBOSE"]=true;$GLOBALS["TRACE_INFLUX"]=true;
		$GLOBALS["OUTPUT"]=true;
		$GLOBALS["debug"]=true;
		ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
}

if($argv[1]=="--failed"){failedZ();exit;}
if($argv[1]=="--rotate"){rotate();exit;}
if($argv[1]=="--file"){ACCESS_LOG_HOURLY_BACKUP($argv[2]);}


scan();
function scan(){
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();
	$pid=$unix->get_pid_from_file($pidFile);
	if($unix->process_exists($pid)){ 
		events("A process, $pid Already exists...");
		return;
	}
	
	$GLOBALS["MYHOSTNAME_PROXY"]=$unix->hostname_g();
	
	@file_put_contents($pidFile, getmypid());
	$time=$unix->file_time_min($pidtime);
	if(!$GLOBALS["VERBOSE"]){
		if($time<5){
			events("{$time}mn, require minimal 5mn");
			return;
		}
	}
	
	@chmod("/home/artica/squid/realtime-events", 0755);
	@chown("/home/artica/squid/realtime-events","squid");
	@chgrp("/home/artica/squid/realtime-events", "squid");
	
	@file_put_contents($pidtime,time());
	
	
	events("Running MAIN_SIZE_BACKUP()");
	MAIN_SIZE_BACKUP();
	
	
	events("Running CACHED_BACKUP()");
	CACHED_BACKUP();
	
	events("Running NO_CACHED_BACKUP()");
	NO_CACHED_BACKUP();
	
	events("Running ACCESS_LOG_HOURLY_BACKUP()");
	
	if(is_file("/home/artica/squid/realtime-events/ACCESS_LOG")){
		ACCESS_LOG_HOURLY_BACKUP("/home/artica/squid/realtime-events/ACCESS_LOG");
	}
	
	
	ACCESS_LOG_HOURLY_BACKUP();
	
	events("Running UFDB_LOG_HOURLY_BACKUP()");
	UFDB_LOG_HOURLY_BACKUP();
	
	events("Running VOLUME_LOG_HOURLY_BACKUP()");
	VOLUME_LOG_HOURLY_BACKUP();
	
	events("Running ROTATE()");
	ROTATE();
	events("Running CLEAN_MYSQL()");
	CLEAN_MYSQL();
}


function  CLEAN_MYSQL(){
	$sock=new sockets();
	$MySQLStatisticsRetentionDays=intval($sock->GET_INFO("MySQLStatisticsRetentionDays"));
	if($MySQLStatisticsRetentionDays==0){$MySQLStatisticsRetentionDays=5;}
	
	$SUB="DATE_SUB(NOW(),INTERVAL $MySQLStatisticsRetentionDays DAY)";
	
	$q=new mysql_squid_builder();
	
	$TABLES[]="dashboard_cached";
	$TABLES[]="dashboard_notcached";
	$TABLES[]="dashboard_countwebsite_day";
	$TABLES[]="dashboard_memberwebsite_day";
	$TABLES[]="dashboard_countuser_day";
	$TABLES[]="dashboard_user_day";
	$TABLES[]="dashboard_size_day";
	$TABLES[]="dashboard_volume_day";
	$TABLES[]="dashboard_blocked_day";
	$TABLES[]="dashboard_historyusers";
	
	while (list ($dev, $TABLE) = each ($TABLES) ){
		if(!$q->TABLE_EXISTS($TABLE)){continue;}
		
		if($TABLE=="dashboard_historyusers"){
			$q->QUERY_SQL("DELETE FROM `$TABLE` WHERE `zDate` < $SUB");
			continue;
		}
		$q->QUERY_SQL("DELETE FROM `$TABLE` WHERE `TIME` < $SUB");
		
		
		
	}

}


function failedZ(){
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();
	$pid=$unix->get_pid_from_file($pidFile);
	if($unix->process_exists($pid)){ return;}
	@file_put_contents($pidFile, getmypid());
	$time=$unix->file_time_min($pidtime);
	if($time<5){return;}
	ACCESS_LOG_HOURLY_FAILED();
}
function events($text=null){
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
	
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];
	
	$logFile="/var/log/artica-parse.hourly.log";
	$mem=round(((memory_get_usage()/1024)/1000),2);
	
	$suffix=date("Y-m-d H:i:s")." [".basename(__FILE__)."/$function/$line]:";
	if($GLOBALS["VERBOSE"]){echo "$suffix $text memory {$mem}MB (system load $internal_load)\n";}
	
	if (is_file($logFile)) {
		$size=filesize($logFile);
		if($size>1000000){@unlink($logFile);}
	}
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$suffix $text\n");
	@fclose($f);
}


function ACCESS_LOG_HOURLY_FAILED(){
	$unix=new unix();
	$faildir="{$GLOBALS["LogFileDeamonLogDir"]}/access-failed";
	$backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/access-backup";
	FAILED_INJECT($faildir,$backupdir);
	
	$faildir="{$GLOBALS["LogFileDeamonLogDir"]}/cached-failed";
	$backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/cached-backup";
	FAILED_INJECT($faildir,$backupdir);
	
	$faildir="{$GLOBALS["LogFileDeamonLogDir"]}/websites-failed";
	$backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/websites-backup";
	FAILED_INJECT($faildir,$backupdir);	
	
	$faildir="{$GLOBALS["LogFileDeamonLogDir"]}/mainsize-failed";
	$backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/mainsize-backup";
	FAILED_INJECT($faildir,$backupdir);
	
	$faildir="{$GLOBALS["LogFileDeamonLogDir"]}/requests-failed";
	$backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/requests-backup";
	FAILED_INJECT($faildir,$backupdir);
	
	$backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/webfilter-backup";
	$faildir="{$GLOBALS["LogFileDeamonLogDir"]}/webfilter-failed";
	FAILED_INJECT($faildir,$backupdir);
	
	$backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/no-cached-backup";
	$faildir="{$GLOBALS["LogFileDeamonLogDir"]}/no-cached-failed";
	FAILED_INJECT($faildir,$backupdir);
	
	$backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/volume-backup";
	$faildir="{$GLOBALS["LogFileDeamonLogDir"]}/volume-failed";
	FAILED_INJECT($faildir,$backupdir);

	
	FAILED_INJECT_MSQL("{$GLOBALS["LogFileDeamonLogDir"]}/mysqlrqs-failed");
	FAILED_INJECT_MSQL("{$GLOBALS["LogFileDeamonLogDir"]}/mysqluser-failed");
	FAILED_INJECT_MSQL("{$GLOBALS["LogFileDeamonLogDir"]}/mysqlwebsite-failed");
	FAILED_INJECT_MSQL("{$GLOBALS["LogFileDeamonLogDir"]}/mysqluserC-failed");
	FAILED_INJECT_MSQL("{$GLOBALS["LogFileDeamonLogDir"]}/mysqblocked-failed");
	FAILED_INJECT_MSQL("{$GLOBALS["LogFileDeamonLogDir"]}/mysqlvolume-failed");
}

function FAILED_INJECT($faildir,$backupdir){
	$unix=new unix();
	$files=$unix->DirFiles($faildir);
	$backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/failed-backup-longtime";
	@mkdir($backupdir,0755,true);
	$q=new influx();
	while (list ($basename, $subarray) = each ($files)){
		
		if($unix->file_time_min("$faildir/$basename")>240){
			$unix->compress("$faildir/$basename", "$backupdir/".basename($faildir)."-$basename.gz");
			events("FAILED TO INJECT $faildir/$basename TIMEOUT save it to : $backupdir");
			@unlink("$faildir/$basename");
			continue;
		}
		
		$size=@filesize("$faildir/$basename");
		if($size==0){
			@unlink("$faildir/$basename");
			continue;
		}
		
		if(!$q->files_inject("$faildir/$basename")){
			events("FAILED TO INJECT $faildir/$basename (aborting) {$size}bytes");
			continue;
		}
		events("SUCCESS TO INJECT $faildir/$basename");
		@copy("$faildir/$basename","$backupdir/$basename");
		@unlink("$faildir/$basename");
	
	}	
	
}
function FAILED_INJECT_MSQL($faildir){
	$unix=new unix();
	$files=$unix->DirFiles($faildir);
	$q=new mysql_squid_builder();
	while (list ($basename, $subarray) = each ($files)){

		if($unix->file_time_min("$faildir/$basename")>240){
			@unlink("$faildir/$basename");
			continue;
		}
		
		$sql=@file_get_contents("$faildir/$basename");
		$q->QUERY_SQL($sql);
		
		$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `dashboard_user_day` (
			`TIME` DATETIME,
			`USER` VARCHAR(128),
			`SIZE` BIGINT UNSIGNED,
			`RQS` BIGINT UNSIGNED,
			KEY `USER` (`USER`),
			KEY `TIME` (`TIME`)
			) ENGINE=MYISAM;"
		);
		
		
		if(!$q->ok){continue;}
		events("SUCCESS TO INJECT $faildir/$basename");
		@unlink("$faildir/$basename");

	}

}
function MAIN_SIZE_BACKUP(){
	$unix=new unix();
	$sourcefile="{$GLOBALS["LogFileDeamonLogDir"]}/MAIN_SIZE";
	$Workpath="{$GLOBALS["LogFileDeamonLogDir"]}/mainsize-work";
	$backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/mainsize-backup";

	
	@mkdir($Workpath,0755,true);
	@mkdir($backupdir,0755,true);

	if(is_file($sourcefile)){
		$workfile=$Workpath."/".time().".log";
		if(is_file($workfile)){return;}
		if(!@copy($sourcefile, "$workfile")){return;}
		@unlink($sourcefile);
	}

	$files=$unix->DirFiles($Workpath);

	while (list ($basename, $subarray) = each ($files)){
		events("Scanning $Workpath/$basename");
		MAIN_SIZE("$Workpath/$basename");
	}
}
function MAIN_SIZE($workfile){

	$LastScannLine=0;
	$Workpath="{$GLOBALS["LogFileDeamonLogDir"]}/mainsize-work";
	$backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/mainsize-backup";


	if(is_file("$workfile.last")){
		$LastScannLine=intval(@file_get_contents("$workfile.last"));
	}

	$handle = @fopen($workfile, "r");
	if (!$handle) {events("Fopen failed on $workfile");return false;}

	if($LastScannLine>0){fseek($handle, $LastScannLine, SEEK_SET);}

	while (!feof($handle)){
		//1435616598;proxyname=routeur.touzeau.biz;242560;
		$buffer =trim(fgets($handle));
		if($buffer==null){continue;}
		$MEM=array();
		$ARRAY=explode(";",$buffer);
		$TIME=$ARRAY[0];
		$PROXYNAME=$ARRAY[1];
		$SIZE=$ARRAY[2];
		$PROXYNAME=str_replace("proxyname=", "", $PROXYNAME);
		$HOURTIME=date("Y-m-d H:00:00",$TIME);

		$MD5=md5("$HOURTIME$PROXYNAME");
		

		if(!isset($MEM[$MD5])){
			$MEM[$MD5]["TIME"]=QueryToUTC(strtotime($HOURTIME),true);
			$MEM[$MD5]["SIZE"]=intval($SIZE);
			$MEM[$MD5]["PROXYNAME"]=$PROXYNAME;
			$MEM[$MD5]["ZDATE"]=strtotime($HOURTIME);
		}else{
			$MEM[$MD5]["ZDATE"]=strtotime($HOURTIME);
			$MEM[$MD5]["SIZE"]=$MEM[$MD5]["SIZE"]+$SIZE;
		}

		if(count($MEM)>5000){
			events("Injecting ".count($MEM[$MD5]) ." items");
			$iSeek = ftell($handle);
			@file_put_contents("$workfile.last", $iSeek);
			if(!MAIN_SIZE_DUMP($MEM)){return;}
			$MEM=array();

		}

	}

	@unlink("$workfile.last");
	events("Injecting ".count($MEM) ." items");
	MAIN_SIZE_DUMP($MEM);
	@unlink($workfile);
}
function MAIN_SIZE_DUMP($MEM){
	if(count($MEM)==0){return true;}
	$q=new influx();
	$AS_POSTGRES=false;
	$suffix="influx";
	if(is_file("/usr/local/ArticaStats/bin/postgres")){ $AS_POSTGRES=true; $suffix="postgres";}
	$q=new postgres_sql();
	
	$backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/mainsize-backup";
	$faildir="{$GLOBALS["LogFileDeamonLogDir"]}/mainsize-failed";
	@mkdir($faildir,0755,true);
	$backupfile="$backupdir/".time().".$suffix.log";
	$failedPath="$faildir/".time().".$suffix.log";
	
	while (list ($KEYMD5, $subarray) = each ($MEM)){
		$zDate=date("Y-m-d H:i:s",$subarray["ZDATE"]);
		$FINAL[]="('$zDate','{$subarray["SIZE"]}','{$subarray["PROXYNAME"]}')";
		
		if(count($FINAL)>500){
			$sql="INSERT INTO MAIN_SIZE (zDate,SIZE,PROXYNAME) VALUES ".@implode(",", $FINAL);
			
			$q->QUERY_SQL($sql);
			if(!$q->ok){
				events("INJECTION Failed: backup to $failedPath ($q->curl_error)");
				@file_put_contents($failedPath, @implode("\n", $sql));
				return false;
			}
		}
			
	}


	if(count($FINAL)>0){
		$sql="INSERT INTO MAIN_SIZE (zDate,SIZE,PROXYNAME) VALUES ".@implode(",", $FINAL);
			
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			events("INJECTION Failed: backup to $failedPath ($q->curl_error)");
			@file_put_contents($failedPath, @implode("\n", $sql));
			return false;
		}
	}

	return true;
}

function ROTATE(){
	$unix=new unix();
	
	$backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/cached-backup";
	ROTATE_DIR($backupdir);
	
	$backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/mainsize-backup";
	ROTATE_DIR($backupdir);
	
	$backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/access-backup";
	ROTATE_DIR($backupdir);
	
	$backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/webfilter-backup";
	ROTATE_DIR($backupdir);
	
	$backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/no-cached-backup";
	ROTATE_DIR($backupdir);
		
	$backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/volume-backup";
	ROTATE_DIR($backupdir);
	
}
function ROTATE_DIR($backupdir){
	$unix=new unix();
	$cat=$unix->find_program("cat");
	$files=$unix->DirFiles($backupdir);
	$suffix="influx";
	if(is_file("/usr/local/ArticaStats/bin/postgres")){ $suffix="postgres";}
	
	
	$today=date("Y-m-d");
	while (list ($basename, $subarray) = each ($files)){
		
		if(preg_match("#^([0-9\-]+)\.gz$#", $basename,$re)){continue;}
		
		if(preg_match("#^([0-9\-]+)\.back$#", $basename,$re)){
			if($re[1]<>$today){
				if(!$unix->compress("$backupdir/$basename", "$backupdir/{$re[1]}.gz")){@unlink("$backupdir/{$re[1]}.gz");continue;}
				@unlink("$backupdir/$basename");
			}
			continue;
		}
		
		
		if(!preg_match("#^([0-9]+)\.$suffix\.log$#", $basename,$re)){
			echo "$basename no match...\n";
			continue;
		}
		$time=$re[1];
		$day=date("Y-m-d",$time);
		
		$handleOUT = @fopen("$backupdir/$basename", "r");
		$handleIN = @fopen("$backupdir/$day.back", "a");
		$c=0;
		while (!feof($handleOUT)){
			$line =trim(fgets($handleOUT, 4096));
			@fwrite($handleIN,"$line\n");
			$c++;
		}
		
		events("$backupdir/$basename $c line(s)");
		fclose($handleOUT);
		fclose($handleIN);
		@unlink("$backupdir/$basename");
	
	}
	
}

function CACHED_BACKUP(){
	$unix=new unix();
	$sourcefile="{$GLOBALS["LogFileDeamonLogDir"]}/CACHED";
	$dailypath="{$GLOBALS["LogFileDeamonLogDir"]}/cached-daily";
	$Workpath="{$GLOBALS["LogFileDeamonLogDir"]}/cached_work";
	$backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/cached-backup";

	@mkdir($dailypath,0755,true);
	@mkdir($Workpath,0755,true);
	@mkdir($backupdir,0755,true);

	if(is_file($sourcefile)){
		$workfile=$Workpath."/".time().".log";
		if(is_file($workfile)){return;}
		if(!@copy($sourcefile, "$workfile")){return;}
		@unlink($sourcefile);
	}

	$files=$unix->DirFiles($Workpath);

	while (list ($basename, $subarray) = each ($files)){
		CACHED("$Workpath/$basename");
	}
}
function NO_CACHED_BACKUP(){
	$unix=new unix();
	$sourcefile="{$GLOBALS["LogFileDeamonLogDir"]}/NO_CACHED";
	$Workpath="{$GLOBALS["LogFileDeamonLogDir"]}/no-cached_work";
	$backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/no-cached-backup";

	
	@mkdir($Workpath,0755,true);
	@mkdir($backupdir,0755,true);

	if(is_file($sourcefile)){
		$workfile=$Workpath."/".time().".log";
		if(is_file($workfile)){return;}
		if(!@copy($sourcefile, "$workfile")){return;}
		@unlink($sourcefile);
	}

	$files=$unix->DirFiles($Workpath);

	while (list ($basename, $subarray) = each ($files)){
		events("Parsing $Workpath/$basename");
		NO_CACHED("$Workpath/$basename");
	}
}

function NO_CACHED($workfile){
	
	$LastScannLine=0;
	
	
	if(is_file("$workfile.last")){
		$LastScannLine=intval(@file_get_contents("$workfile.last"));
	}
	
	$handle = @fopen($workfile, "r");
	if (!$handle) {events("Fopen failed on $workfile");return false;}
	
	if($LastScannLine>0){fseek($handle, $LastScannLine, SEEK_SET);}
	$CZ=0;
	while (!feof($handle)){
		$CZ++;
		$buffer =trim(fgets($handle));
		if($buffer==null){continue;}
		$MEM=array();
		$ARRAY=explode(";",$buffer);
		$TIME=$ARRAY[0];
		$PROXYNAME=$ARRAY[1];
		$SIZE=$ARRAY[2];
		$PROXYNAME=str_replace("proxyname=", "", $PROXYNAME);
		$HOURTIME=date("Y-m-d H:00:00",$TIME);
		
	
		$MD5=md5("$HOURTIME$PROXYNAME");
		$MD5SQL=md5("$HOURTIME$PROXYNAME");
		

		if(!isset($MEMSQL[$MD5SQL])){
			$MEMSQL[$MD5SQL]["TIME"]=$HOURTIME;
			$MEMSQL[$MD5SQL]["SIZE"]=intval($SIZE);
			$MEMSQL[$MD5SQL]["PROXYNAME"]=$PROXYNAME;
		}else{
			$MEMSQL[$MD5SQL]["ZDATE"]=strtotime($HOURTIME);
			$MEMSQL[$MD5SQL]["SIZE"]=$MEMSQL[$MD5SQL]["SIZE"]+$SIZE;
		}
		
		
		
		
		if(!isset($MEM[$MD5])){
			$MEM[$MD5]["TIME"]=QueryToUTC(strtotime($HOURTIME),true);
			$MEM[$MD5]["SIZE"]=intval($SIZE);
			$MEM[$MD5]["PROXYNAME"]=$PROXYNAME;
			$MEM[$MD5]["ZDATE"]=strtotime($HOURTIME);
		}else{
			$MEM[$MD5]["ZDATE"]=strtotime($HOURTIME);
			$MEM[$MD5]["SIZE"]=$MEM[$MD5]["SIZE"]+$SIZE;
		}
	
		if(count($MEM[$MD5])>10000){
			events("Not Cached content: $CZ dumped lines");
			$iSeek = ftell($handle);
			@file_put_contents("$workfile.last", $iSeek);
			NO_CACHED_DUMP_SQL($MEMSQL);
			if(!NO_CACHED_DUMP($MEM)){return;}
			
			
			$MEM=array();
			$MEMSQL=array();
		}
	
	}
	events("Not Cached content: $CZ dumped lines");
	@unlink("$workfile.last");
	NO_CACHED_DUMP_SQL($MEMSQL);
	CACHED_DUMP($MEM);
	@unlink($workfile);
}


function CACHED($workfile){
	
	$LastScannLine=0;
	$dailypath="{$GLOBALS["LogFileDeamonLogDir"]}/daily";
	$Workpath="{$GLOBALS["LogFileDeamonLogDir"]}access-work";
	$backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/access-backup";
	
	
	if(is_file("$workfile.last")){
		$LastScannLine=intval(@file_get_contents("$workfile.last"));
	}
	
	$handle = @fopen($workfile, "r");
	if (!$handle) {events("Fopen failed on $workfile");return false;}
	
	if($LastScannLine>0){fseek($handle, $LastScannLine, SEEK_SET);}
	$CZ=0;
	while (!feof($handle)){
		$CZ++;
		$buffer =trim(fgets($handle));
		if($buffer==null){continue;}
		$MEM=array();
		$ARRAY=explode(";",$buffer);
		$TIME=$ARRAY[0];
		$PROXYNAME=$ARRAY[1];
		$SIZE=$ARRAY[2];
		$PROXYNAME=str_replace("proxyname=", "", $PROXYNAME);
		$HOURTIME=date("Y-m-d H:00:00",$TIME);
		
	
		$MD5=md5("$HOURTIME$PROXYNAME");
		$MD5SQL=md5("$HOURTIME$PROXYNAME");
		

		if(!isset($MEMSQL[$MD5SQL])){
			$MEMSQL[$MD5SQL]["TIME"]=$HOURTIME;
			$MEMSQL[$MD5SQL]["SIZE"]=intval($SIZE);
			$MEMSQL[$MD5SQL]["PROXYNAME"]=$PROXYNAME;
		}else{
			$MEMSQL[$MD5SQL]["ZDATE"]=strtotime($HOURTIME);
			$MEMSQL[$MD5SQL]["SIZE"]=$MEMSQL[$MD5SQL]["SIZE"]+$SIZE;
		}
		
		
		
		
		if(!isset($MEM[$MD5])){
			$MEM[$MD5]["TIME"]=QueryToUTC(strtotime($HOURTIME),true);
			$MEM[$MD5]["SIZE"]=intval($SIZE);
			$MEM[$MD5]["PROXYNAME"]=$PROXYNAME;
			$MEM[$MD5]["ZDATE"]=strtotime($HOURTIME);
		}else{
			$MEM[$MD5]["ZDATE"]=strtotime($HOURTIME);
			$MEM[$MD5]["SIZE"]=$MEM[$MD5]["SIZE"]+$SIZE;
		}
	
		if(count($MEM[$MD5])>10000){
			events("Cached content: $CZ dumped lines");
			$iSeek = ftell($handle);
			@file_put_contents("$workfile.last", $iSeek);
			CACHED_DUMP_SQL($MEMSQL);
			if(!CACHED_DUMP($MEM)){return;}
			$MEM=array();
			$MEMSQL=array();
				
		}
	
	}
	events("Cached content: $CZ dumped lines");
	@unlink("$workfile.last");
	CACHED_DUMP($MEM);
	CACHED_DUMP_SQL($MEMSQL);
	@unlink($workfile);
}

function CACHED_DUMP_SQL($MEM){
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `dashboard_cached` (
			`TIME` DATETIME,
			`PROXYNAME` VARCHAR(128),
			`SIZE` BIGINT UNSIGNED,
			KEY `PROXYNAME` (`PROXYNAME`),
			KEY `TIME` (`TIME`)
			) ENGINE=MYISAM;"
	);
	
	while (list ($KEYMD5, $subarray) = each ($MEM)){
		$time=$subarray["TIME"];
		$PROXYNAME=$subarray["PROXYNAME"];
		$SIZE=$subarray["SIZE"];
		$f[]="('$time','$PROXYNAME','$SIZE')";
			
	}
	
	$faildir="{$GLOBALS["LogFileDeamonLogDir"]}/mysqlrqs-failed";
	@mkdir($faildir,0755,true);
	if(count($f)==0){return;}
	$sql="INSERT INTO `dashboard_cached` (`TIME`,`PROXYNAME`,`SIZE`) VALUES ".@implode(",", $f);
	$failedPath="$faildir/".time().".influx.sql";
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		events("INJECTION Failed: backup to $failedPath ($q->mysql_error)");
		@file_put_contents($failedPath,$sql);
	}
	
	
}
function NO_CACHED_DUMP_SQL($MEM){

	$q=new mysql_squid_builder();
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `dashboard_notcached` (
			`TIME` DATETIME,
			`PROXYNAME` VARCHAR(128),
			`SIZE` BIGINT UNSIGNED,
			KEY `PROXYNAME` (`PROXYNAME`),
			KEY `TIME` (`TIME`)
			) ENGINE=MYISAM;"
	);

	while (list ($KEYMD5, $subarray) = each ($MEM)){
		$time=$subarray["TIME"];
		$PROXYNAME=$subarray["PROXYNAME"];
		$SIZE=$subarray["SIZE"];
		$f[]="('$time','$PROXYNAME','$SIZE')";
			
	}

	$faildir="{$GLOBALS["LogFileDeamonLogDir"]}/mysqlrqs-failed";
	@mkdir($faildir,0755,true);
	if(count($f)==0){return;}
	$sql="INSERT INTO `dashboard_notcached` (`TIME`,`PROXYNAME`,`SIZE`) VALUES ".@implode(",", $f);
	$failedPath="$faildir/".time().".influx.sql";
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		events("INJECTION Failed: backup to $failedPath ($q->mysql_error)");
		@file_put_contents($failedPath,$sql);
	}
	

}

function CACHED_DUMP($MEM){
	if(count($MEM)==0){return true;}
	$q=new influx();
	$AS_POSTGRES=false;
	$suffix="influx";
	if(is_file("/usr/local/ArticaStats/bin/postgres")){ $AS_POSTGRES=true; $suffix="postgres";}
	
	while (list ($KEYMD5, $subarray) = each ($MEM)){
		$zArray["time"]=$subarray["TIME"];
		$zArray["fields"]["SIZE"]=intval($subarray["SIZE"]);
		$zArray["fields"]["ZDATE"]=$subarray["ZDATE"];
		$zArray["tags"]["proxyname"]=$subarray["PROXYNAME"];
		
		$zDate=date("Y-m-d H:i:s",$subarray["ZDATE"]);
		
		if($AS_POSTGRES){
			$FINAL[]="('$zDate','{$zArray["fields"]["SIZE"]}','{$subarray["PROXYNAME"]}')";
			continue;
		}
		
		
		$line=$q->prepare("CACHED", $zArray);
		if($GLOBALS["VERBOSE"]){echo "$line\n"; }
		$FINAL[]=$line;
			
	}
	
	
	if(count($FINAL)>0){
		$backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/cached-backup";
		$faildir="{$GLOBALS["LogFileDeamonLogDir"]}/cached-failed";
		@mkdir($faildir,0755,true);
		$backupfile="$backupdir/".time().".$suffix.log";
		$failedPath="$faildir/".time().".$suffix.log";
		
		if($AS_POSTGRES){
			$sql="INSERT INTO CACHED (zDate,SIZE,PROXYNAME) VALUES ".@implode(",", $FINAL);
			$q=new postgres_sql();
			$q->QUERY_SQL($sql);
			if(!$q->ok){
				events("INJECTION Failed: backup to $failedPath ($q->curl_error)");
				@file_put_contents($failedPath, @implode("\n", $sql));
				return false;
			}
		}
		
		
		if(!$AS_POSTGRES){
			if(!$q->bulk_inject($FINAL)){
				events("INJECTION Failed: backup to $failedPath");
				@file_put_contents($failedPath, @implode("\n", $FINAL));
				return false;
			}
		}
		events("INJECTION Success: backup to $backupfile");
		@file_put_contents($backupfile, @implode("\n", $FINAL));
		$FINAL=array();
	
	}
	return true;
}	
function NO_CACHED_DUMP($MEM){
	if(count($MEM)==0){return true;}
	$q=new influx();
	$AS_POSTGRES=false;
	$suffix="influx";
	if(is_file("/usr/local/ArticaStats/bin/postgres")){ $AS_POSTGRES=true; $suffix="postgres";}
	
	while (list ($KEYMD5, $subarray) = each ($MEM)){
		$zArray["time"]=$subarray["TIME"];
		$zArray["fields"]["SIZE"]=intval($subarray["SIZE"]);
		$zArray["fields"]["ZDATE"]=$subarray["ZDATE"];
		$zArray["tags"]["proxyname"]=$subarray["PROXYNAME"];
		
		$zDate=date("Y-m-d H:i:s",$subarray["ZDATE"]);
		
		if($AS_POSTGRES){
			$FINAL[]="('$zDate','{$zArray["fields"]["SIZE"]}','{$subarray["PROXYNAME"]}')";
			continue;
		}
		
		
		$line=$q->prepare("NO_CACHED", $zArray);
		if($GLOBALS["VERBOSE"]){echo "$line\n"; }
		$FINAL[]=$line;
			
	}
	
	
	if(count($FINAL)>0){
		$backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/no-cached-backup";
		$faildir="{$GLOBALS["LogFileDeamonLogDir"]}/no-cached-failed";
		@mkdir($faildir,0755,true);
		$backupfile="$backupdir/".time().".$suffix.log";
		$failedPath="$faildir/".time().".$suffix.log";
		
		
		if($AS_POSTGRES){
			$sql="INSERT INTO NO_CACHED (zDate,SIZE,PROXYNAME) VALUES ".@implode(",", $FINAL);
			$q=new postgres_sql();
			$q->QUERY_SQL($sql);
			if(!$q->ok){
				events("INJECTION Failed: backup to $failedPath ($q->curl_error)");
				@file_put_contents($failedPath, @implode("\n", $sql));
				return false;
			}
		}
		
		
		
		if(!$AS_POSTGRES){
			if(!$q->bulk_inject($FINAL)){
				events("INJECTION Failed: backup to $failedPath");
				@file_put_contents($failedPath, @implode("\n", $FINAL));
				return false;
			}
		}
		events("INJECTION Success: backup to $backupfile");
		@file_put_contents($backupfile, @implode("\n", $FINAL));
		$FINAL=array();
	
	}
	return true;
}	



function ACCESS_LOG_HOURLY_BACKUP($sourcefile=null){
	$unix=new unix();
	if($sourcefile==null){$sourcefile="{$GLOBALS["LogFileDeamonLogDir"]}/ACCESS_LOG";}
	$Workpath="{$GLOBALS["LogFileDeamonLogDir"]}/access-work";
	$backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/access-backup";
	
	@mkdir($Workpath,0755,true);
	@mkdir($backupdir,0755,true);
	events("Scanning $sourcefile");
	
	
	if(!is_file($sourcefile)){events("$sourcefile no such file");return;}

	if(is_file($sourcefile)){
		$workfile=$Workpath."/".time().".log";
		if(is_file($workfile)){return;}
		$size=@filesize($sourcefile);
		events("Copy $sourcefile (".FormatBytes($size/1024,TRUE).")");
		if(!@copy($sourcefile, "$workfile")){return;}
		@unlink($sourcefile);
	}
	
	$files=$unix->DirFiles($Workpath);
	
	while (list ($basename, $subarray) = each ($files)){
		events("Scanning $Workpath/$basename");
		ACCESS_LOG_HOURLY("$Workpath/$basename");
	}
	
	$files=$unix->DirFiles("{{$GLOBALS["LogFileDeamonLogDir"]}}/access-work");
	while (list ($basename, $subarray) = each ($files)){
		events("Scanning {$GLOBALS["LogFileDeamonLogDir"]}/access-work/$basename");
		ACCESS_LOG_HOURLY("{$GLOBALS["LogFileDeamonLogDir"]}/access-work/$basename");
	}
}



function ACCESS_LOG_HOURLY($workfile){
	$LastScannLine=0;
	
	$Workpath="{$GLOBALS["LogFileDeamonLogDir"]}access-work";
	$backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/access-backup";

	
	if(is_file("$workfile.last")){
		$LastScannLine=intval(@file_get_contents("$workfile.last"));
	}
	
	$handle = @fopen($workfile, "r");
	if (!$handle) {events("Fopen failed on $workfile");return false;}
	
	if($LastScannLine>0){fseek($handle, $LastScannLine, SEEK_SET);}
	$CZ=0;
	while (!feof($handle)){
		$CZ++;
		$buffer =trim(fgets($handle));
		if($buffer==null){continue;}
		$USER=null;
		$ARRAY=explode(":::",$buffer);
		$TIME=$ARRAY[0];
		$CATEGORY=$ARRAY[1];
		$USERID=$ARRAY[2];
		$IPADDR=$ARRAY[3];
		$MAC=$ARRAY[4];
		$SIZE=$ARRAY[5];
		$SITE=$ARRAY[6];
		$FAM=$ARRAY[7];
		$RQS=$ARRAY[8];
		$PROXYNAME=$ARRAY[9];
		$PROXYNAME=str_replace("proxyname=", "", $PROXYNAME);
		$HOURTIME=date("Y-m-d H:00:00",$TIME);
		$MIN=date("i",$TIME);
		$MINTIME=date("Y-m-d H:",$TIME).MINTOTEN($MIN).":00";
		
		
		if($USERID<>null){$USER=$USERID;}
		if($USER==null){if($MAC<>null){$USER=$MAC;}}
		if($USER==null){if($IPADDR<>null){$USER=$IPADDR;}}
		
		
		
		$KEYFAMUSER=md5($HOURTIME.$FAM.$USER);
		if(!isset($MSSQLFAMUSER[$KEYFAMUSER])){
			$MSSQLFAMUSER[$KEYFAMUSER]["FAM"]=$FAM;
			$MSSQLFAMUSER[$KEYFAMUSER]["RQS"]=$RQS;
			$MSSQLFAMUSER[$KEYFAMUSER]["SIZE"]=intval($SIZE);
			$MSSQLFAMUSER[$KEYFAMUSER]["TIME"]=$HOURTIME;
			$MSSQLFAMUSER[$KEYFAMUSER]["USER"]=$USER;
				
		}else{
			$MSSQLFAMUSER[$KEYFAMUSER]["RQS"]=$MSSQLFAMUSER[$KEYFAMUSER]["RQS"]+$RQS;
			$MSSQLFAMUSER[$KEYFAMUSER]["SIZE"]=$MSSQLFAMUSER[$KEYFAMUSER]["SIZE"]+intval($SIZE);
		}		
		
		
		
		$KEYFAM=md5($HOURTIME.$FAM);
		if(!isset($MSSQLFAM[$KEYFAM])){
			$MSSQLFAM[$KEYFAM]["FAM"]=$FAM;
			$MSSQLFAM[$KEYFAM]["RQS"]=$RQS;
			$MSSQLFAM[$KEYFAM]["SIZE"]=intval($SIZE);
			$MSSQLFAM[$KEYFAM]["TIME"]=$HOURTIME;
			
		}else{
			$MSSQLFAM[$KEYFAM]["RQS"]=$MSSQLFAM[$KEYFAM]["RQS"]+$RQS;
			$MSSQLFAM[$KEYFAM]["SIZE"]=$MSSQLFAM[$KEYFAM]["SIZE"]+intval($SIZE);
			
		}
		
		
		if($USER<>null){
			$COUNTOFUSERS_KEY=md5($MINTIME.$USER);
			if(!isset($COUNTOFUSERS[$COUNTOFUSERS_KEY])){
				$COUNTOFUSERS[$COUNTOFUSERS_KEY]["TIME"]=$MINTIME;
				$COUNTOFUSERS[$COUNTOFUSERS_KEY]["COUNT"]=1;
			}else{
				$COUNTOFUSERS[$COUNTOFUSERS_KEY]=$COUNTOFUSERS[$COUNTOFUSERS_KEY]["COUNT"]+1;
				
			}
			
			
			$MD5USERSQL=md5("$HOURTIME$USER");
			if(!isset($FRONTENDUSERSQL[$MD5USERSQL])){
				$FRONTENDUSERSQL[$MD5USERSQL]["USER"]=$USER;
				$FRONTENDUSERSQL[$MD5USERSQL]["RQS"]=$RQS;
				$FRONTENDUSERSQL[$MD5USERSQL]["SIZE"]=intval($SIZE);
				$FRONTENDUSERSQL[$MD5USERSQL]["TIME"]=$HOURTIME;
				
			}else{
				$FRONTENDUSERSQL[$MD5USERSQL]["RQS"]=$FRONTENDUSERSQL[$MD5USERSQL]["RQS"]+$RQS;
				$FRONTENDUSERSQL[$MD5USERSQL]["SIZE"]=$FRONTENDUSERSQL[$MD5USERSQL]["SIZE"]+intval($SIZE);
				
			}
		}
		
		
		
		$MD5=md5("$HOURTIME$CATEGORY$USERID$IPADDR$MAC$SITE$FAM$PROXYNAME");
		$MD5WEBSITES=md5("$HOURTIME$FAM$SIZE$PROXYNAME");
		$MD5RQS=md5("$HOURTIME$PROXYNAME");
		$MD5SQL=md5("$MINTIME$PROXYNAME");
		
		
		if(!isset($FRONTENDSQL[$MD5SQL])){
			$FRONTENDSQL[$MD5SQL]["TIME"]=$MINTIME;
			$FRONTENDSQL[$MD5SQL]["RQS"]=intval($RQS);
			$FRONTENDSQL[$MD5SQL]["PROXYNAME"]=$PROXYNAME;
			$FRONTENDSQL[$MD5SQL]["SIZE"]=intval($SIZE);
		}else{
			$FRONTENDSQL[$MD5SQL]["SIZE"]=$FRONTENDSQL[$MD5SQL]["SIZE"]+intval($SIZE);
			$FRONTENDSQL[$MD5SQL]["RQS"]=$FRONTENDSQL[$MD5SQL]["RQS"]+intval($RQS);
		}
		
				
		if(!isset($WEBSITES[$MD5WEBSITES])){
			$WEBSITES[$MD5WEBSITES]["TIME"]=QueryToUTC(strtotime($HOURTIME),true);
			$WEBSITES[$MD5WEBSITES]["FAM"]=$FAM;
			$WEBSITES[$MD5WEBSITES]["PROXYNAME"]=$PROXYNAME;
			$WEBSITES[$MD5WEBSITES]["SIZE"]=intval($SIZE);
			$WEBSITES[$MD5WEBSITES]["RQS"]=intval($RQS);
			$WEBSITES[$MD5WEBSITES]["ZDATE"]=strtotime($HOURTIME);
		}else{
			$WEBSITES[$MD5WEBSITES]["ZDATE"]=strtotime($HOURTIME);
			$WEBSITES[$MD5WEBSITES]["RQS"]=$WEBSITES[$MD5WEBSITES]["RQS"]+$RQS;
			$WEBSITES[$MD5WEBSITES]["SIZE"]=$WEBSITES[$MD5WEBSITES]["SIZE"]+$SIZE;
		}
		
		
		
		if(!isset($REQUESTS[$MD5RQS])){
			$REQUESTS[$MD5RQS]["FAM"]=$FAM;
			$REQUESTS[$MD5RQS]["SIZE"]=intval($SIZE);
			$REQUESTS[$MD5RQS]["PROXYNAME"]=$PROXYNAME;
			$REQUESTS[$MD5RQS]["TIME"]=QueryToUTC(strtotime($HOURTIME),true);
			$REQUESTS[$MD5RQS]["RQS"]=intval($RQS);
			$REQUESTS[$MD5RQS]["ZDATE"]=strtotime($HOURTIME);
			
		}else{
			$REQUESTS[$MD5RQS]["RQS"]=$REQUESTS[$MD5RQS]["RQS"]+$RQS;
		}
		
		if(!isset($MEM[$MD5])){
			$MEM[$MD5]["TIME"]=QueryToUTC(strtotime($HOURTIME),true);
			$MEM[$MD5]["CATEGORY"]=$CATEGORY;
			$MEM[$MD5]["USERID"]=$USERID;
			$MEM[$MD5]["IPADDR"]=$IPADDR;
			$MEM[$MD5]["MAC"]=$MAC;
			$MEM[$MD5]["SIZE"]=intval($SIZE);
			$MEM[$MD5]["SITE"]=$SITE;
			$MEM[$MD5]["FAM"]=$FAM;
			$MEM[$MD5]["PROXYNAME"]=$PROXYNAME;
			$MEM[$MD5]["ZDATE"]=strtotime($HOURTIME);
			$MEM[$MD5]["RQS"]=intval($RQS);
		}else{
			$MEM[$MD5]["ZDATE"]=strtotime($HOURTIME);
			$MEM[$MD5]["SIZE"]=$MEM[$MD5]["SIZE"]+$SIZE;
			$MEM[$MD5]["RQS"]=$MEM[$MD5]["RQS"]+$RQS;
		}
	
		if(count($MEM)>5000){
			$iSeek = ftell($handle);
			events("Continue to $CZ");
			@file_put_contents("$workfile.last", $iSeek);
			if(!WEBSITES_DUMP($WEBSITES)){return;}
			if(!REQUESTS_DUMP($REQUESTS)){return;}
			if(!ACCESS_LOG_HOURLY_DUMP($MEM)){return;}
			
			MSSQL_DUMP($FRONTENDSQL);
			MSSQL_DUMP_USER($FRONTENDUSERSQL);
			MSSQL_DUMP_USERCOUNT($COUNTOFUSERS);
			MSSQL_DUMP_FAMSITE($MSSQLFAM);
			MSSQL_DUMP_FAMSITE_USER($MSSQLFAMUSER);
			
			
			
			$MEM=array();
			$WEBSITES=array();
			$REQUESTS=array();
			$FRONTENDSQL=array();
			$FRONTENDUSERSQL=array();
			$COUNTOFUSERS=array();
			$MSSQLFAM=array();
			$MSSQLFAMUSER=array();
		}
	
	}	
	events("$workfile done $CZ lines parsed");
	@unlink("$workfile.last");
	ACCESS_LOG_HOURLY_DUMP($MEM);
	WEBSITES_DUMP($WEBSITES);
	REQUESTS_DUMP($REQUESTS);
	
	MSSQL_DUMP($FRONTENDSQL);
	MSSQL_DUMP_USER($FRONTENDUSERSQL);
	MSSQL_DUMP_USERCOUNT($COUNTOFUSERS);
	MSSQL_DUMP_FAMSITE($MSSQLFAM);
	MSSQL_DUMP_FAMSITE_USER($MSSQLFAMUSER);
	
	@unlink($workfile);
}
function TimeToInflux($time,$Nomilliseconds=false){
	$time=QueryToUTC($time);
	$milli=null;
	$microtime=microtime();
	preg_match("#^[0-9]+\.([0-9]+)\s+#", $microtime,$re);
	$ms=intval($re[1]);
	if(!$Nomilliseconds){$milli=".{$ms}";}
	return date("Y-m-d",$time)."T".date("H:i:s",$time)."{$milli}Z";
}


function MSSQL_DUMP_FAMSITE($MSSQLFAM){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `dashboard_countwebsite_day` ( `TIME` DATETIME, 
			`FAMILYSITE` VARCHAR(128), 
			`SIZE` BIGINT UNSIGNED, `RQS` BIGINT UNSIGNED,
			KEY `TIME` (`TIME`) ) ENGINE=MYISAM;");
	
	while (list ($KEY, $MAINARRAY) = each ($MSSQLFAM)){
		$MINTIME=$MAINARRAY["TIME"];
		$SIZE=$MAINARRAY["SIZE"];
		$FAM=mysql_escape_string2($MAINARRAY["FAM"]);
		$RQS=$MAINARRAY["RQS"];
		$f[]="('$MINTIME','$FAM','$SIZE','$RQS')";
	}
	
	$faildir="{$GLOBALS["LogFileDeamonLogDir"]}/mysqlwebsite-failed";
	@mkdir($faildir,0755,true);
	if(count($f)==0){return;}
	$sql="INSERT INTO `dashboard_countwebsite_day` (`TIME`,`FAMILYSITE`,`SIZE`,`RQS`) VALUES ".@implode(",", $f);
	$failedPath="$faildir/".time().".influx.sql";
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		events("INJECTION Failed: backup to $failedPath ($q->curl_error)");
		@file_put_contents($failedPath,$sql);
	}
	
	
}

function MSSQL_DUMP_FAMSITE_USER($MSSQLFAM){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `dashboard_memberwebsite_day` ( `TIME` DATETIME,
			`FAMILYSITE` VARCHAR(128),
			`USER` VARCHAR(128),
			`SIZE` BIGINT UNSIGNED, 
			`RQS` BIGINT UNSIGNED,
			KEY `TIME` (`TIME`), 
			KEY `FAMILYSITE` (`FAMILYSITE`),
			KEY `USER` (`USER`)
			
			) ENGINE=MYISAM;");
	
	while (list ($KEY, $MAINARRAY) = each ($MSSQLFAM)){
		$MINTIME=$MAINARRAY["TIME"];
		$SIZE=$MAINARRAY["SIZE"];
		$FAM=mysql_escape_string2($MAINARRAY["FAM"]);
		$USER=mysql_escape_string2($MAINARRAY["USER"]);
		$RQS=$MAINARRAY["RQS"];
		$f[]="('$MINTIME','$FAM','$USER','$SIZE','$RQS')";
	}
	
	$faildir="{$GLOBALS["LogFileDeamonLogDir"]}/mysqlwebsite-failed";
	@mkdir($faildir,0755,true);
	if(count($f)==0){return;}
	$sql="INSERT INTO `dashboard_memberwebsite_day` (`TIME`,`FAMILYSITE`,`USER`,`SIZE`,`RQS`) VALUES ".@implode(",", $f);
	$failedPath="$faildir/".time().".influx.sql";
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		events("INJECTION Failed: backup to $failedPath ($q->curl_error)");
		@file_put_contents($failedPath,$sql);
	}
	
		
	
	
	
}


function MSSQL_DUMP_USERCOUNT($COUNTOFUSERS){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `dashboard_countuser_day` ( `TIME` DATETIME, `USER` BIGINT UNSIGNED, KEY `TIME` (`TIME`) ) ENGINE=MYISAM;");
	
	while (list ($KEY, $MAINARRAY) = each ($COUNTOFUSERS)){
		$MINTIME=$MAINARRAY["TIME"];
		$COUNT=$MAINARRAY["COUNT"];
		
		$f[]="('$MINTIME','$COUNT')";
	}
	
	$faildir="{$GLOBALS["LogFileDeamonLogDir"]}/mysqluserC-failed";
	@mkdir($faildir,0755,true);
	if(count($f)==0){return;}
	$sql="INSERT INTO `dashboard_countuser_day` (`TIME`,`USER`) VALUES ".@implode(",", $f);
	$failedPath="$faildir/".time().".influx.sql";
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		events("INJECTION Failed: backup to $failedPath ($q->curl_error)");
		@file_put_contents($failedPath,$sql);
	}
	
	
	
}

function MSSQL_DUMP_USER($MEM){
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `dashboard_user_day` ( `TIME` DATETIME, `USER` VARCHAR(128), 
			`SIZE` BIGINT UNSIGNED, `RQS` BIGINT UNSIGNED, KEY `USER` (`USER`), KEY `TIME` (`TIME`) ) ENGINE=MYISAM;");

	while (list ($KEYMD5, $subarray) = each ($MEM)){
	
		$MINTIME=$subarray["TIME"];
		$RQS=$subarray["RQS"];
		$USER=$subarray["USER"];
		$SIZE=$subarray["SIZE"];
		$f[]="('$MINTIME','$RQS','$USER','$SIZE')";
	
	}
	
	$faildir="{$GLOBALS["LogFileDeamonLogDir"]}/mysqluser-failed";
	@mkdir($faildir,0755,true);
	if(count($f)==0){return;}
	$sql="INSERT INTO `dashboard_user_day` (`TIME`,`RQS`,`USER`,`SIZE`) VALUES ".@implode(",", $f);
	$failedPath="$faildir/".time().".influx.sql";
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		events("INJECTION Failed: backup to $failedPath ($q->curl_error)");
		@file_put_contents($failedPath,$sql);
	}


}

function MSSQL_DUMP($MEM){
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `dashboard_size_day` (
			`TIME` DATETIME,
			`PROXYNAME` VARCHAR(128),
			`SIZE` BIGINT UNSIGNED,
			`RQS` BIGINT UNSIGNED,
			KEY `PROXYNAME` (`PROXYNAME`),
			KEY `TIME` (`TIME`)
			) ENGINE=MYISAM;"
	);
	
	while (list ($KEYMD5, $subarray) = each ($MEM)){
	
		$MINTIME=$subarray["TIME"];
		$RQS=$subarray["RQS"];
		$PROXYNAME=$subarray["PROXYNAME"];
		$SIZE=$subarray["SIZE"];
		$f[]="('$MINTIME','$RQS','$PROXYNAME','$SIZE')";
	
	}
	
	$faildir="{$GLOBALS["LogFileDeamonLogDir"]}/mysqlrqs-failed";
	@mkdir($faildir,0755,true);
	if(count($f)==0){return;}
	$sql="INSERT INTO `dashboard_size_day` (`TIME`,`RQS`,`PROXYNAME`,`SIZE`) VALUES ".@implode(",", $f);
	$failedPath="$faildir/".time().".influx.sql";
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		events("INJECTION Failed: backup to $failedPath ($q->curl_error)");
		@file_put_contents($failedPath,$sql);
	}
	
	
}


function REQUESTS_DUMP($MEM){
if(count($MEM)==0){return true;}
$q=new influx();
$AS_POSTGRES=false;
$suffix="influx";
if(is_file("/usr/local/ArticaStats/bin/postgres")){ $AS_POSTGRES=true; $suffix="postgres";}


while (list ($KEYMD5, $subarray) = each ($MEM)){
	if(isset($subarray["RQS"])){continue;}
	$FAM=$subarray["FAM"];
	$RQS=$subarray["RQS"];
	if(intval($RQS)==0){continue;}
	$PROXYNAME=$subarray["PROXYNAME"];
	$SIZE=intval($subarray["SIZE"]);
	
	$zDate=date("Y-m-d H:i:s",$subarray["ZDATE"]);
	
	if($AS_POSTGRES){
		$FINAL[]="('$zDate','{$RQS}','{$subarray["PROXYNAME"]}')";
		continue;
	}
	
	
	$zArray["precision"]="s";
	$zArray["time"]=$subarray["TIME"];
	$zArray["fields"]["ZDATE"]=$subarray["ZDATE"];
	$zArray["fields"]["RQS"]=$RQS;
	$zArray["tags"]["proxyname"]=$PROXYNAME;
	$line=$q->prepare("proxy_requests", $zArray);
	$FINAL[]=$line;
}

if(count($FINAL)>0){
	$backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/requests-backup";
	$failedPath="{$GLOBALS["LogFileDeamonLogDir"]}/requests-failed";
	@mkdir($failedPath,0755,true);
	$backupfile="$backupdir/".time().".$suffix.log";
	$failedPath="$failedPath/".time().".$suffix.log";
	
	if($AS_POSTGRES){
		$sql="INSERT INTO proxy_requests (zDate,RQS,PROXYNAME) VALUES ".@implode(",", $FINAL);
		$q=new postgres_sql();
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			events("INJECTION Failed: backup to $failedPath ($q->curl_error)");
			@file_put_contents($failedPath, @implode("\n", $sql));
			return false;
		}
	}	
	
	if(!$AS_POSTGRES){
		if(!$q->bulk_inject($FINAL)){
			events("INJECTION Failed: backup to $failedPath ($q->curl_error)");
			@file_put_contents($failedPath, @implode("\n", $FINAL));
			return false;
		}
	}

	events("INJECTION Success: backup to $backupfile");
	@file_put_contents($backupfile, @implode("\n", $FINAL));
	$FINAL=array();

}

return true;
}

function WEBSITES_DUMP($MEM){
	if(count($MEM)==0){return true;}
	$q=new influx();
	$AS_POSTGRES=false;
	$suffix="influx";
	if(is_file("/usr/local/ArticaStats/bin/postgres")){ $AS_POSTGRES=true; $suffix="postgres";}
	
	
	while (list ($KEYMD5, $subarray) = each ($MEM)){
		$FAM=$subarray["FAM"];
		$RQS=$subarray["RQS"];
		$PROXYNAME=$subarray["PROXYNAME"];
		if($PROXYNAME==null){$PROXYNAME=$GLOBALS["MYHOSTNAME_PROXY"];}
		$SIZE=intval($subarray["SIZE"]);
		$zArray["precision"]="s";
		$zArray["time"]=$subarray["TIME"];
		$zArray["tags"]["FAMILYSITE"]=$FAM;
		$zArray["fields"]["ZDATE"]=$subarray["ZDATE"];
		$zArray["fields"]["RQS"]=$RQS;
		$zArray["tags"]["proxyname"]=$PROXYNAME;
		$zArray["fields"]["SIZE"]=$SIZE;
		
		
		$zDate=date("Y-m-d H:i:s",$subarray["ZDATE"]);
		
		
		if($AS_POSTGRES){
			$FINAL[]="('$zDate','$FAM','$RQS','$SIZE','$PROXYNAME')";
			continue;
		}
		
		$line=$q->prepare("websites", $zArray);
		$FINAL[]=$line;
	}
	
	if(count($FINAL)>0){
		$backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/websites-backup";
		$failedPath="{$GLOBALS["LogFileDeamonLogDir"]}/websites-failed";
		@mkdir($failedPath,0755,true);
		$backupfile="$backupdir/".time().".$suffix.log";
		$failedPath="$failedPath/".time().".$suffix.log";
		
		if($AS_POSTGRES){
			$sql="INSERT INTO websites (zDate,FAMILYSITE,RQS,SIZE,PROXYNAME) VALUES ".@implode(",", $FINAL);
			$q=new postgres_sql();
			$q->QUERY_SQL($sql);
			if(!$q->ok){
				events("INJECTION Failed: backup to $failedPath ($q->curl_error)");
				@file_put_contents($failedPath, @implode("\n", $sql));
				return false;
			}
		}
		
		if(!$AS_POSTGRES){
			if(!$q->bulk_inject($FINAL)){
				events("INJECTION Failed: backup to $failedPath ($q->curl_error)");
				@file_put_contents($failedPath, @implode("\n", $FINAL));
				return false;
			}
		}
	
		events("INJECTION Success: backup to $backupfile");
		@file_put_contents($backupfile, @implode("\n", $FINAL));
		$FINAL=array();
	
	}
	
	return true;	
}


function ACCESS_LOG_HOURLY_DUMP($MEM){
	if(count($MEM)==0){return true;}
	$q=new influx();
	$AS_POSTGRES=false;
	$suffix="influx";
	if(is_file("/usr/local/ArticaStats/bin/postgres")){ $AS_POSTGRES=true; $suffix="postgres";}
	$IPClass=new IP();
	while (list ($KEYMD5, $subarray) = each ($MEM)){
		$CATEGORY=$subarray["CATEGORY"];
		$USERID=$subarray["USERID"];
		$IPADDR=$subarray["IPADDR"];
		$MAC=$subarray["MAC"];
		$SIZE=intval($subarray["SIZE"]);
		$SITE=$subarray["SITE"];
		$FAM=$subarray["FAM"];
		$RQS=$subarray["RQS"];
		$PROXYNAME=$subarray["PROXYNAME"];
		if($MAC==null){$MAC="00:00:00:00:00:00";}
		
		if(!$IPClass->isValid($IPADDR)){continue;}
		if(!$IPClass->IsvalidMAC($MAC)){$MAC="00:00:00:00:00:00";}
		if($USERID==null){$USERID="none";}
		
		
		if(strlen($FAM)>128){$FAM=substr(0, 127,$FAM);}
		if(strlen($SITE)>128){$SITE=substr(0, 127,$SITE);}
		if(strlen($PROXYNAME)>128){$PROXYNAME=substr(0, 127,$PROXYNAME);}
				
		if($AS_POSTGRES){
			$zDate=date("Y-m-d H:i:s",$subarray["ZDATE"]);
			$FINAL[]="('$zDate','$MAC','$IPADDR','$CATEGORY','$FAM','$USERID','$SIZE','$RQS','$FAM','$PROXYNAME')";
			continue;
		}
		
		$zArray["precision"]="s";
		$zArray["time"]=$subarray["TIME"];
		$zArray["tags"]["CATEGORY"]=$CATEGORY;
		$zArray["tags"]["USERID"]=$USERID;
		$zArray["tags"]["IPADDR"]=$IPADDR;
		$zArray["tags"]["MAC"]=$MAC;
		$zArray["fields"]["SIZE"]=$SIZE;
		$zArray["tags"]["SITE"]=$SITE;
		$zArray["tags"]["FAMILYSITE"]=$FAM;
		$zArray["fields"]["ZDATE"]=$subarray["ZDATE"];
		$zArray["fields"]["RQS"]=$RQS;
		$zArray["tags"]["proxyname"]=$PROXYNAME;
		$line=$q->prepare("access_log", $zArray);
		
		
		$FINAL[]=$line;
			
	}
	
	
	if(count($FINAL)>0){
		$backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/access-backup";
		$failedPath="{$GLOBALS["LogFileDeamonLogDir"]}/access-failed";
		@mkdir($failedPath,0755,true);
		@mkdir($backupdir,0755,true);
		$backupfile="{$GLOBALS["LogFileDeamonLogDir"]}/access-backup/".time().".$suffix.log";
		$failedPath="{$GLOBALS["LogFileDeamonLogDir"]}/access-failed/".time().".$suffix.log";
		
		if($AS_POSTGRES){
			$sql="INSERT INTO access_log (zDate,MAC,IPADDR,CATEGORY,SITENAME,USERID,SIZE,RQS,familysite,PROXYNAME) VALUES ".@implode(",", $FINAL);
			$q=new postgres_sql();
			$q->QUERY_SQL($sql);
			if(!$q->ok){
				events("INJECTION Failed: backup to $failedPath ($q->curl_error)");
				@file_put_contents($failedPath, @implode("\n", $sql));
				return false;
			}
		}
		
		if(!$AS_POSTGRES){
			if(!$q->bulk_inject($FINAL)){
				events("INJECTION Failed: backup to $failedPath ($q->curl_error)");
				@file_put_contents($failedPath, @implode("\n", $FINAL));
				return false;
			}
		}
		
		events("INJECTION Success: backup to $backupfile");
		@file_put_contents($backupfile, @implode("\n", $FINAL));
		$FINAL=array();
		
	}
	
	return true;
	
}

function VOLUME_LOG_HOURLY_BACKUP(){
	$unix=new unix();
	$sourcefile="{$GLOBALS["LogFileDeamonLogDir"]}/VOLUME";
	$Workpath="{$GLOBALS["LogFileDeamonLogDir"]}/volume-work";
	$backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/volume-backup";
	
	@mkdir($Workpath,0755,true);
	@mkdir($backupdir,0755,true);

	if(is_file($sourcefile)){
		events("VOLUME_LOG_HOURLY_BACKUP:: Copy $sourcefile");
		$workfile=$Workpath."/".time().".log";
		if(is_file($workfile)){return;}
		if(!@copy($sourcefile, "$workfile")){return;}
		@unlink($sourcefile);
		@touch($sourcefile);
		@chmod($sourcefile, 0755);
		@chown($sourcefile, "squid");
		@chgrp($sourcefile, "squid");
	}else{
		events("VOLUME_LOG_HOURLY_BACKUP:: $sourcefile no such file...");
	}

	$files=$unix->DirFiles($Workpath);

	while (list ($basename, $subarray) = each ($files)){
		events("VOLUME_LOG_HOURLY_BACKUP:: Scanning $Workpath/$basename");
		VOLUME_LOG_HOURLY_SCAN("$Workpath/$basename");
	}
}

function VOLUME_LOG_HOURLY_SCAN($workfile){
	
	$unix=new unix();
	$LastScannLine=0;
	if(is_file("$workfile.last")){$LastScannLine=intval(@file_get_contents("$workfile.last"));}
	
	$handle = @fopen($workfile, "r");
	if (!$handle) {events("Fopen failed on $workfile");return false;}
	if($LastScannLine>0){fseek($handle, $LastScannLine, SEEK_SET);}
	
	$handle = @fopen($workfile, "r");
	if (!$handle) {events("Fopen failed on $workfile");return false;}
	
	if($LastScannLine>0){fseek($handle, $LastScannLine, SEEK_SET);}
	$MEM=array();
	$proxyname=$unix->hostname_g();
	$catz=new mysql_catz();
	$q=new mysql_squid_builder();
	while (!feof($handle)){
		$buffer =trim(fgets($handle));
		if($buffer==null){continue;}
		
		$ARRAY=explode(":::",$buffer);
		$TIME=$ARRAY[0];
		$USERID=$ARRAY[1];
		$MAC=$ARRAY[3];
		if($USERID==null){$USERID=$q->MacToUid($MAC);}
		
		$IPADDR=$ARRAY[2];
		if($IPADDR=="127.0.0.1"){continue;}
		
		$CONTENT_TYPE=$ARRAY[4];
		$FAMILYSITE=$ARRAY[5];
		$HITS=$ARRAY[6];
		$SIZE=$ARRAY[7];
		$HOURTIME=date("Y-m-d H:00:00",$TIME);
		$KEYMD5=md5("$HOURTIME$USERID$IPADDR$MAC$FAMILYSITE$CONTENT_TYPE");
		
		
		if(!isset($MEM[$KEYMD5])){
			if($GLOBALS["VERBOSE"]){echo "$KEYMD5] VOLUME_LOG_HOURLY_SCAN: $HOURTIME $USERID/$IPADDR/$MAC $FAMILYSITE $CONTENT_TYPE $HITS/$SIZE\n";}
			$MEM[$KEYMD5]["TIME"]=QueryToUTC(strtotime($HOURTIME),true);
			$MEM[$KEYMD5]["USERID"]=$USERID;
			$MEM[$KEYMD5]["IPADDR"]=$IPADDR;
			$MEM[$KEYMD5]["MAC"]=$MAC;
			$MEM[$KEYMD5]["FAMILYSITE"]=$FAMILYSITE;
			$MEM[$KEYMD5]["CATEGORY"]=$catz->GET_CATEGORIES($FAMILYSITE);
			$MEM[$KEYMD5]["CONTENT_TYPE"]=$CONTENT_TYPE;
			$MEM[$KEYMD5]["SIZE"]=$SIZE;
			$MEM[$KEYMD5]["ZDATE"]=strtotime($HOURTIME);
			$MEM[$KEYMD5]["PROXYNAME"]=$proxyname;
			$MEM[$KEYMD5]["RQS"]=$HITS;
		}else{
			$MEM[$KEYMD5]["RQS"]=$MEM[$KEYMD5]["RQS"]+$HITS;
			$MEM[$KEYMD5]["SIZE"]=$MEM[$KEYMD5]["SIZE"]+$SIZE;
			if($GLOBALS["VERBOSE"]){echo "$KEYMD5] VOLUME_LOG_HOURLY_SCAN: $HOURTIME $USERID/$IPADDR/$MAC $FAMILYSITE $CONTENT_TYPE {$MEM[$KEYMD5]["RQS"]}/{$MEM[$KEYMD5]["SIZE"]}\n";}
		}

		if(count($MEM)>5000){
			VOLUME_LOG_HOURLY_DUMP($MEM);
			VOLUME_LOG_HOURLY_MYSQL_DUMP($MEM);
			$MEM=array();
		}
	
	
	}
	VOLUME_LOG_HOURLY_MYSQL_DUMP($MEM);
	VOLUME_LOG_HOURLY_DUMP($MEM);
	@unlink($workfile);
	
}

function VOLUME_LOG_HOURLY_MYSQL_DUMP($MSSQLFAM){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `dashboard_volume_day` ( 
			`TIME` DATETIME,
			`FAMILYSITE` VARCHAR(128),
			`USERID` VARCHAR(64),
			`IPADDR` VARCHAR(64),
			`MAC` VARCHAR(64),
			`CATEGORY` VARCHAR(64),
			`CONTENT_TYPE` VARCHAR(64),
			`SIZE` BIGINT UNSIGNED, 
			`RQS` BIGINT UNSIGNED,
			KEY `TIME` (`TIME`),
			KEY `FAMILYSITE` (`FAMILYSITE`),
			KEY `USERID` (`USERID`),
			KEY `IPADDR` (`IPADDR`),
			KEY `MAC` (`MAC`),
			KEY `CONTENT_TYPE` (`CONTENT_TYPE`)
			
			) ENGINE=MYISAM;");
	
	if(!$q->FIELD_EXISTS("dashboard_volume_day","CATEGORY")){
		$sql="ALTER TABLE `dashboard_volume_day` ADD `CATEGORY` VARCHAR(64), ADD INDEX(`CATEGORY`)";
		$q->QUERY_SQL($sql,"artica_backup");
	}
	
	
	
	reset($MSSQLFAM);
	while (list ($KEY, $MAINARRAY) = each ($MSSQLFAM)){
		$HOURTIME=date("Y-m-d H:00:00",$MAINARRAY["ZDATE"]);
		$USERID=mysql_escape_string2($MAINARRAY["USERID"]);
		$IPADDR=$MAINARRAY["IPADDR"];
		$MAC=$MAINARRAY["MAC"];
		$CONTENT_TYPE=mysql_escape_string2($MAINARRAY["CONTENT_TYPE"]);
		$FAMILYSITE=$MAINARRAY["FAMILYSITE"];
		$CATEGORY=$MAINARRAY["CATEGORY"];
		$HITS=$MAINARRAY["RQS"];
		$SIZE=$MAINARRAY["SIZE"];
		$line="('$HOURTIME','$FAMILYSITE','$USERID','$IPADDR','$MAC','$CONTENT_TYPE','$SIZE','$HITS')";
		$f[]="('$HOURTIME','$FAMILYSITE','$USERID','$IPADDR','$MAC','$CONTENT_TYPE','$SIZE','$HITS')";
		
	}

	$faildir="{$GLOBALS["LogFileDeamonLogDir"]}/mysqlvolume-failed";
	@mkdir($faildir,0755,true);
	if(count($f)==0){return;}
	$sql="INSERT INTO `dashboard_volume_day` (`TIME`,`FAMILYSITE`,`USERID`,`IPADDR`,`MAC`,`CONTENT_TYPE`,`SIZE`,`RQS`) VALUES ".@implode(",", $f);
	$failedPath="$faildir/".time().".influx.sql";
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		events("INJECTION Failed: backup to $failedPath ($q->curl_error)");
		@file_put_contents($failedPath,$sql);
	}
	
}




function VOLUME_LOG_HOURLY_DUMP($MEM){
	return;
	$FINAL=array();
	$q=new influx();
	
	
	if(count($MEM)==0){events("No data sent by previous function ( feature should disabled )...");return;}
	
	while (list ($KEYMD5, $subarray) = each ($MEM)){
	
	
		$array["precision"]="s";
		$array["time"]=$subarray["TIME"];
		$array["fields"]["TIME"]=$subarray["ZDATE"];;
		$array["fields"]["RQS"]=$subarray["RQS"];
		$array["fields"]["SIZE"]=$subarray["SIZE"];
		
		$array["tags"]["uid"]=$subarray["USERID"];
		$array["tags"]["MAC"]=$subarray["MAC"];
		$array["tags"]["IPADDR"]=$subarray["IPADDR"];
		$array["tags"]["familysite"]=$subarray["FAMILYSITE"];;
		$array["tags"]["contenttype"]=$subarray["CONTENT_TYPE"];
		$array["tags"]["proxyname"]=$subarray["PROXYNAME"];;

		$line=$q->prepare("contenttype", $array);
		if($GLOBALS["VERBOSE"]){echo "$line\n"; }
		$FINAL[]=$line;
			
	}
	
	if(count($FINAL)==0){events("No data sent by previous function ( feature should disabled )...");return;}
	
	if(count($FINAL)>0){
		$backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/volume-backup";
		$faildir="{$GLOBALS["LogFileDeamonLogDir"]}/volume-failed";
		@mkdir($faildir,0755,true);
		@mkdir($backupdir,0755,true);
		$backupfile="$backupdir/".time().".influx.log";
		$failedPath="$faildir/".time().".influx.log";
		if(!$q->bulk_inject($FINAL)){
			events("INJECTION Failed: backup to $failedPath ($q->curl_error)");
			@file_put_contents($failedPath, @implode("\n", $FINAL));
			return false;
		}
	
		events("INJECTION Success: backup to $backupfile");
		@file_put_contents($backupfile, @implode("\n", $FINAL));
		$FINAL=array();
	
	}
	
	return true;	
	
	
}



function UFDB_LOG_HOURLY_BACKUP(){
	$unix=new unix();
	$sourcefile="/home/ufdb/relatime-events/ACCESS_LOG";
	$Workpath="{$GLOBALS["LogFileDeamonLogDir"]}/webfilter-work";
	$backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/webfilter-backup";

	@mkdir($Workpath,0755,true);
	@mkdir($backupdir,0755,true);

	if(is_file($sourcefile)){
		$workfile=$Workpath."/".time().".log";
		if(is_file($workfile)){return;}
		if(!@copy($sourcefile, "$workfile")){return;}
		@unlink($sourcefile);
	}

	$files=$unix->DirFiles($Workpath);

	while (list ($basename, $subarray) = each ($files)){
		events("WEBFILTERING Scanning $Workpath/$basename");
		UFDB_LOG_HOURLY_SCAN("$Workpath/$basename");
	}
}


function UFDB_LOG_HOURLY_SCAN($workfile){
	
	$LastScannLine=0;
	if(is_file("$workfile.last")){$LastScannLine=intval(@file_get_contents("$workfile.last"));}
	
	$handle = @fopen($workfile, "r");
	if (!$handle) {events("Fopen failed on $workfile");return false;}
	if($LastScannLine>0){fseek($handle, $LastScannLine, SEEK_SET);}	
	
	$handle = @fopen($workfile, "r");
	if (!$handle) {events("Fopen failed on $workfile");return false;}
	
	if($LastScannLine>0){fseek($handle, $LastScannLine, SEEK_SET);}
	$MEM=array();
	
	while (!feof($handle)){
		
		$buffer =trim(fgets($handle));
		if($buffer==null){continue;}
		
		$ARRAY=explode(":::",$buffer);
		$TIME=intval($ARRAY[0]);
		$USERID=$ARRAY[1];
		$CATEGORY=$ARRAY[2];
		$RULENAME=$ARRAY[3];
		$REMOTE_IP=$ARRAY[4];
		$BLOCK_TYPE=$ARRAY[5];
		$WHY=$ARRAY[6];
		$Clienthostname=$ARRAY[7];
		$WWW=$ARRAY[8];
		$LOCAL_IP=$ARRAY[9];
		if($USERID==null){$USERID=$Clienthostname;}
		if($USERID==null){$USERID=$LOCAL_IP;}
		
		$HOURTIME=date("Y-m-d H:00:00",$TIME);
		$MIN=date("i",$TIME);
		$MINTIME=date("Y-m-d H:",$TIME).MINTOTEN($MIN).":00";
		
		$KEYMD5=md5("$HOURTIME$USERID$CATEGORY$RULENAME$WWW$Clienthostname");
		
		$KEYSQL=md5("$HOURTIME$CATEGORY$RULENAME$WWW");
		
		if(!isset($MEMSQL[$KEYSQL])){
			$MEMSQL[$KEYSQL]["TIME"]=$HOURTIME;
			$MEMSQL[$KEYSQL]["CATEGORY"]=$CATEGORY;
			$MEMSQL[$KEYSQL]["RULENAME"]=$RULENAME;
			$MEMSQL[$KEYSQL]["WEBSITE"]=$WWW;
			$MEMSQL[$KEYSQL]["RQS"]=1;
		}else{
			$MEMSQL[$KEYSQL]["RQS"]=$MEMSQL[$KEYSQL]["RQS"]+1;
		}
		
		
		if(!isset($MEM[$KEYMD5])){
			$MEM[$KEYMD5]["TIME"]=QueryToUTC(strtotime($HOURTIME),true);
			$MEM[$KEYMD5]["uid"]=$USERID;
			$MEM[$KEYMD5]["category"]=$CATEGORY;
			$MEM[$KEYMD5]["rulename"]=$RULENAME;
			$MEM[$KEYMD5]["public_ip"]=$REMOTE_IP;
			$MEM[$KEYMD5]["blocktype"]=$BLOCK_TYPE;
			$MEM[$KEYMD5]["why"]=$WHY;
			$MEM[$KEYMD5]["hostname"]=$Clienthostname;
			$MEM[$KEYMD5]["website"]=$WWW;
			$MEM[$KEYMD5]["client"]=$LOCAL_IP;
			$MEM[$KEYMD5]["ZDATE"]=strtotime($HOURTIME);
			$MEM[$KEYMD5]["RQS"]=1;
		}else{
			$MEM[$KEYMD5]["RQS"]=$MEM[$KEYMD5]["RQS"]+1;
		}
		
	}
	@unlink($workfile);
	UFDB_LOG_HOURLY_DUMP($MEM);
	UFDB_LOG_HOURLY_MYSQL_DUMP($MEMSQL);
	
}
function UFDB_LOG_HOURLY_DUMP($MEM){
	
	$AS_POSTGRES=false;
	$suffix="influx";
	if(is_file("/usr/local/ArticaStats/bin/postgres")){ $AS_POSTGRES=true; $suffix="postgres";}
	
	events("Dumping ".count($MEM)." entries");
	$q=new influx();
	
	$PROXYNAME=$GLOBALS["MYHOSTNAME_PROXY"];
	$prefix_sql="(zDate,website,category,rulename,public_ip,blocktype,why,hostname,client,PROXYNAME,rqs)";
	while (list ($KEYMD5, $subarray) = each ($MEM)){
		
	

		
		$website=$subarray["website"];
		$category=$subarray["category"];
		$rulename=$subarray["rulename"];
		$public_ip=$subarray["public_ip"];
		$blocktype=$subarray["blocktype"];
		$why=$subarray["why"];
		$hostname=$subarray["hostname"];
		$client=$subarray["client"];
		$zDate=date("Y-m-d H:i:s",$subarray["ZDATE"]);
		$RQS=$subarray["RQS"];
		
		
		
		if($AS_POSTGRES){
			$FINAL[]="('$zDate','$website','$category','$rulename','$public_ip','$blocktype','$why','$hostname','$client','$PROXYNAME','$RQS')";
			continue;
		}
		
		$array["precision"]="s";
		$array["time"]=$subarray["TIME"];
		$array["tags"]["uid"]=$subarray["uid"];;
		$array["fields"]["TIME"]=$subarray["ZDATE"];;
		$array["fields"]["RQS"]=$subarray["RQS"];;;
		$array["tags"]["category"]=$subarray["category"];;
		$array["tags"]["rulename"]=$subarray["rulename"];
		$array["tags"]["public_ip"]=$subarray["public_ip"];;
		$array["tags"]["blocktype"]=$subarray["blocktype"];
		$array["tags"]["why"]=$subarray["why"];
		$array["tags"]["hostname"]=$subarray["hostname"];;
		$array["tags"]["website"]=$subarray["website"];;
		$array["tags"]["client"]=$subarray["client"];;

		$line=$q->prepare("webfilter", $array);
		if($GLOBALS["VERBOSE"]){echo "$line\n"; }
		$FINAL[]=$line;
			
	}
	
	if(count($FINAL)>0){
		$backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/webfilter-backup";
		$faildir="{$GLOBALS["LogFileDeamonLogDir"]}/webfilter-failed";
		@mkdir($faildir,0755,true);
		@mkdir($backupdir,0755,true);
		$backupfile="$backupdir/".time().".$suffix.log";
		$failedPath="$faildir/".time().".$suffix.log";
		
		if($AS_POSTGRES){
			$sql="INSERT INTO webfilter $prefix_sql VALUES ".@implode(",", $FINAL);
			$q=new postgres_sql();
			$q->QUERY_SQL($sql);
			if(!$q->ok){
				events("WEBFILTERING INJECTION Failed: backup to $failedPath ($q->mysql_error)");
				@file_put_contents($failedPath, @implode("\n", $sql));
				return false;
			}
		}
		
		if(!$AS_POSTGRES){
			if(!$q->bulk_inject($FINAL)){
				events("WEBFILTERING INJECTION Failed: backup to $failedPath ($q->curl_error)");
				@file_put_contents($failedPath, @implode("\n", $FINAL));
				return false;
			}
		}
	
		events("WEBFILTERING INJECTION Success: backup to $backupfile");
		@file_put_contents($backupfile, @implode("\n", $FINAL));
		$FINAL=array();
	
	}
	
	return true;
}

function UFDB_LOG_HOURLY_MYSQL_DUMP($MEM){
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `dashboard_blocked_day` (
			`TIME` DATETIME,
			`CATEGORY` VARCHAR(128),
			`RULENAME` VARCHAR(128),
			`WEBSITE` VARCHAR(128),
			`RQS` BIGINT UNSIGNED,
			KEY `CATEGORY` (`CATEGORY`),
			KEY `RULENAME` (`RULENAME`),
			KEY `WEBSITE` (`WEBSITE`),
			KEY `TIME` (`TIME`)
			) ENGINE=MYISAM;"
	);
	

	
	while (list ($KEYMD5, $subarray) = each ($MEM)){
		$TIME=$subarray['TIME'];
		$CATEGORY=mysql_escape_string2($subarray["CATEGORY"]);
		$RULENAME=mysql_escape_string2($subarray["RULENAME"]);
		$WEBSITE=mysql_escape_string2($subarray["WEBSITE"]);
		$RQS=$subarray["RQS"];
		
		$f[]="('$TIME','$RQS','$CATEGORY','$RULENAME','$WEBSITE')";
	
	}
	
	$faildir="{$GLOBALS["LogFileDeamonLogDir"]}/mysqblocked-failed";
	@mkdir($faildir,0755,true);
	if(count($f)==0){return;}
	$sql="INSERT INTO `dashboard_blocked_day` (`TIME`,`RQS`,`CATEGORY`,`RULENAME`,`WEBSITE`) VALUES ".@implode(",", $f);
	$failedPath="$faildir/".time().".influx.sql";
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		events("INJECTION Failed: backup to $failedPath ($q->curl_error)");
		@file_put_contents($failedPath,$sql);
	}
	
	
	
}



function MINTOTEN($MIN){
	$MA["00"]="00";
	$MA["01"]="00";
	$MA["02"]="00";
	$MA["03"]="00";
	$MA["04"]="00";
	$MA["05"]="00";
	$MA["06"]="00";
	$MA["07"]="00";
	$MA["08"]="00";
	$MA["09"]="00";
	$MA[10]=10;
	$MA[11]=10;
	$MA[12]=10;
	$MA[13]=10;
	$MA[14]=10;
	$MA[15]=10;
	$MA[16]=10;
	$MA[17]=10;
	$MA[18]=10;
	$MA[19]=10;
	$MA[20]=20;
	$MA[21]=20;
	$MA[22]=20;
	$MA[23]=20;
	$MA[24]=20;
	$MA[25]=20;
	$MA[26]=20;
	$MA[27]=20;
	$MA[28]=20;
	$MA[29]=20;
	$MA[30]=30;
	$MA[31]=30;
	$MA[32]=30;
	$MA[33]=30;
	$MA[34]=30;
	$MA[35]=30;
	$MA[36]=30;
	$MA[37]=30;
	$MA[38]=30;
	$MA[39]=30;
	$MA[40]=40;
	$MA[41]=40;
	$MA[42]=40;
	$MA[43]=40;
	$MA[44]=40;
	$MA[45]=40;
	$MA[46]=40;
	$MA[47]=40;
	$MA[48]=40;
	$MA[49]=40;
	$MA[50]=50;
	$MA[51]=50;
	$MA[52]=50;
	$MA[53]=50;
	$MA[54]=50;
	$MA[55]=50;
	$MA[56]=50;
	$MA[57]=50;
	$MA[58]=50;
	$MA[59]=50;
	return $MA[$MIN];


}
?>