<?php
if(is_file("/usr/bin/cgclassify")){if(is_dir("/cgroups/blkio/php")){shell_exec("/usr/bin/cgclassify -g cpu,cpuset,blkio:php ".getmypid());}}
$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
if($EnableIntelCeleron==1){die("EnableIntelCeleron==1\n");}
$GLOBALS["BYPASS"]=true;
$GLOBALS["DEBUG_INFLUX_VERBOSE"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["DEBUG_MEM"]=false;
$GLOBALS["NODHCP"]=true;
$GLOBALS["PROGRESS"]=false;
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
	if(preg_match("#--progress#",implode(" ",$argv))){$GLOBALS["PROGRESS"]=true;}
}
if($GLOBALS["VERBOSE"]){
		ini_set('display_errors', 1);	
		ini_set('html_errors',0);
		ini_set('display_errors', 1);
		ini_set('error_reporting', E_ALL);
}

if($GLOBALS["VERBOSE"]){"echo Loading...\n";}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.artica-meta.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.stats-appliance.inc');
include_once(dirname(__FILE__).'/ressources/class.parse.berekley.inc');
include_once(dirname(__FILE__)."/ressources/class.realtime-buildsql.inc");
include_once(dirname(__FILE__)."/ressources/class.influx.inc");
$date=date("YW");


if(systemMaxOverloaded()){
	events("FATAL! overloaded system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]} aborting task");
	squid_admin_mysql(0, "Overloaded system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]} aborting task", 
	null,__FILE__,__LINE__);
	die();
}


// --meta \"$TEMP_DIR/squidqsize.$uuid.db\" $uuid
if($argv[1]=="--meta"){parse_meta($argv[2],$argv[3]);exit;}
if($argv[1]=="--size"){die();}
if($argv[1]=="--stats-app"){parse_stats();exit;}
if($argv[1]=="--month"){die();}
if($argv[1]=="--cached"){exit;}
if($argv[1]=="--cache-or-not"){events("Running directly Cache or not (CRON)");CachedOrNot();exit;}
if($argv[1]=="--rqs"){die();exit;}
if($argv[1]=="--stats-apps-clients"){stats_apps_clients();exit;}
if($argv[1]=="--flux-rqs"){FLUX_RQS();exit;}
if($argv[1]=="--members-count"){$GLOBALS["OUTPUT"]=true;exit;}
if($argv[1]=="--usersagents"){USERAGENTS();exit;}
if($argv[1]=="--famsites"){FAMILY_SITES_DAY();exit;}
if($argv[1]=="--maxmin"){MAX_MIN();exit;}
if($argv[1]=="--webfilter"){WEBFILTERING();exit;}
if($argv[1]=="--flux-hour"){events("Running directly Hour flow (CRON)");FLUX_HOUR(true);exit;}
if($argv[1]=="--backup-size"){backup_size();exit;}
if($argv[1]=="--members-graph"){$GLOBALS["OUTPUT"]=true;exit;}
if($argv[1]=="--clean"){squidhour_clean();exit;}
if($argv[1]=="--dump-hour"){DUMP_HOUR();exit;}
if($argv[1]=="--dump-users"){FULL_USERS_DAY();exit;}
if($argv[1]=="--cache-avg"){CACHES_AVG();exit;}


parse();

function build_progress($text,$pourc){
	$cachefile="/usr/share/artica-postfix/ressources/logs/admin.refresh.progress";
	echo "{$pourc}% $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	events("{$pourc}% $text");
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}

function system_values(){
	$unix=new unix();
	$CPU_NUMBER=$unix->CPU_NUMBER();
	@file_put_contents("/usr/share/artica-postfix/ressources/interface-cache/CPU_NUMBER", $CPU_NUMBER);
	
	events("CPU_NUMBER: $CPU_NUMBER");
	@chmod("/usr/share/artica-postfix/ressources/interface-cache/CPU_NUMBER",0755);
	$influxdb_version=influxdb_version();
	events("InfluxDB version: $influxdb_version");
	@file_put_contents("{$GLOBALS["BASEDIR"]}/influxdb_version", $influxdb_version);
	@chmod("{$GLOBALS["BASEDIR"]}/influxdb_version", 0777);
}

function MAX_MIN(){
	$unix=new unix();
	$q=new influx();
	$sock=new sockets();
	
	if(!function_exists("pg_fetch_assoc")){return;}

	
	$timefile=$unix->file_time_min("{$GLOBALS["BASEDIR"]}/DATE_START");
	if($timefile<1440){
		events("{$timefile}mn, need to wait 1440mn");
		return;
	}
	
	$q=new postgres_sql();
	$ligne=pg_fetch_assoc($q->QUERY_SQL("SELECT MAX(zDate) as MAX, MIN(zDate) as MIN from access_log"));
	
	
	events("Time: Minimal: {$ligne["min"]} Maximal: {$ligne["max"]}");
	
	$date_start=strtotime($ligne["min"]);
	$date_end=strtotime($ligne["max"]);
	
	
    if($GLOBALS["VERBOSE"]){echo "* * *\n";}
	if($GLOBALS["VERBOSE"]){echo "* * * START FROM {$date_start} ". date("Y-m-d H:i:s",$date_start)."\n";}
	if($GLOBALS["VERBOSE"]){echo "* * * END TO {$date_end} ". date("Y-m-d H:i:s",$date_end)."\n";}
	@file_put_contents("{$GLOBALS["BASEDIR"]}/DATE_START",$date_start);
	@file_put_contents("{$GLOBALS["BASEDIR"]}/DATE_END",$date_end);
}

function NOT_CATEGORIZED(){
	
	$q=new postgres_sql();
	$ligne=pg_fetch_assoc($q->QUERY_SQL("SELECT COUNT(familysite) as tcount FROM not_categorized"));
	@file_put_contents("{$GLOBALS["BASEDIR"]}/NOT_CATEGORIZED",$ligne["tcount"]);
	
}

function MAX_FILE_CACHE(){
	
	
	
}

function APACHE_STATISTICS(){
	
	$q=new mysql_squid_builder();
	$sql="SELECT SUM(SIZE) AS SIZE, SUM(RQS) as RQS FROM dashboard_apache_sizes";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$SIZE=intval($ligne["SIZE"]);
	@file_put_contents("{$GLOBALS["BASEDIR"]}/APACHEDSIZE", $ligne["SIZE"]);
	@file_put_contents("{$GLOBALS["BASEDIR"]}/APACHEDRQS", $ligne["RQS"]);
	
	
	
	$sql="SELECT COUNT(*) as tcount FROM reverse_www WHERE enabled=1";
	@file_put_contents("{$GLOBALS["BASEDIR"]}/REVERSE_COUNT", $ligne["tcount"]);
	if($SIZE>0){
		
		$sql="SELECT SUM(SIZE) as SIZE,TIME FROM dashboard_apache_sizes GROUP BY TIME ORDER BY TIME";
		$results=$q->QUERY_SQL($sql);
		
		events("$sql -> ".mysql_num_rows($results)." items");
		if(!$q->ok){events($q->mysql_error);}
		
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$SIZEA=$ligne["SIZE"];
			$SIZEA=$SIZEA/1024;
			$SIZEA=round($SIZEA/1024,2);
			$xdata[]=$ligne["TIME"];
			$ydata[]=$SIZEA;
			
			
		}
		
		$MAIN["xdata"]=$xdata;
		$MAIN["ydata"]=$ydata;
		@file_put_contents("{$GLOBALS["BASEDIR"]}/NGINX_FLUX_HOUR", serialize($MAIN));
		if(count($xdata)<2){@unlink("{$GLOBALS["BASEDIR"]}/NGINX_FLUX_HOUR");}
		
	}
	
	$dateint=InfluxQueryFromUTC(strtotime("-48 hours"));
	$date=date("Y-m-d H:00:00",$dateint);
	$qSimple=new mysql();
	$sql="SELECT COUNT(ID) as tcount FROM apache_admin_mysql WHERE severity=0 AND zDate>'$date'";
	$ligne=mysql_fetch_array($qSimple->QUERY_SQL($sql,"artica_events"));
	@file_put_contents("{$GLOBALS["BASEDIR"]}/APACHE_WATCHDOG_COUNT_EVENTS", $ligne["tcount"]);
	@chmod("{$GLOBALS["BASEDIR"]}/APACHE_WATCHDOG_COUNT_EVENTS", 0777);
	
	
}

function backup_size(){
	$unix=new unix();
	$sock=new sockets();
	$InFluxBackupDatabaseDir=$sock->GET_INFO("InFluxBackupDatabaseDir");
	if($InFluxBackupDatabaseDir==null){$InFluxBackupDatabaseDir="/home/artica/influx/backup";}
	$size=$unix->DIRSIZE_BYTES_NOCACHE($InFluxBackupDatabaseDir);
	
	if($GLOBALS["VERBOSE"]){
		echo "$InFluxBackupDatabaseDir = $size (".FormatBytes($size/1024).")\n";
		
	}
	
	@file_put_contents("{$GLOBALS["BASEDIR"]}/influxdb_snapshotsize", $size);
	
}

function FLUX_HOUR_POSTGRES(){
	
	
	$now=date("Y-m-d H:i:s",strtotime("-24 hour"));
	$q=new postgres_sql();
	$TimeGroup="date_trunc('hour', zdate) as zdate";
	$sql="SELECT SUM(size) as SIZE,$TimeGroup FROM access_log WHERE zdate>='$now' GROUP BY zdate ORDER BY zdate ASC";
	echo "FLUX_HOUR:: POSTGRES: ******************\n $sql\n **********************\n";
	$results=$q->QUERY_SQL($sql);
	
	events("$sql -> ".pg_num_rows($results)." items");
	if(!$q->ok){events($q->mysql_error);}
	
	
	while($ligne=@pg_fetch_assoc($results)){
			$size=intval($ligne["size"])/1024;
			$size=$size/1024;
			$time=strtotime($ligne["zdate"]);
			$min=date("l H:i:00",$time);
			echo "FLUX_HOUR: $min = $size\n";
			$xdata[]=$min;
			$ydata[]=$size;
	}
	
	build_progress("{refresh_dashboard_values} FLUX HOUR ".count($xdata)." items",16);
	$MAIN["xdata"]=$xdata;
	$MAIN["ydata"]=$ydata;
	@file_put_contents("{$GLOBALS["BASEDIR"]}/FLUX_HOUR", serialize($MAIN));
	if(count($xdata)<2){@unlink("{$GLOBALS["BASEDIR"]}/FLUX_HOUR");}
	
	
}

function FLUX_HOUR($astimeout=false){
	$TimeFile="/etc/artica-postfix/pids/exec.squid.interface-size.php.FLUX_HOUR.time";
	
	if($GLOBALS["VERBOSE"]){$astimeout=false;}
	if($GLOBALS["FORCE"]){$astimeout=false;}
	
	if($astimeout){
		$unix=new unix();
		if($unix->file_time_min($TimeFile)<5){
			events("Aborting, require 5mn minimal");
			return;
		}
	}
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	
	$os=new os_system();
	$UPTIME=$os->uptime_int();
	build_progress("{uptime} $UPTIME",15);
	@file_put_contents("{$GLOBALS["BASEDIR"]}/UPTIME",$UPTIME);
	CACHES_AVG();
	
	$now=strtotime("-24 hour");
	$MAIN=array();
	$xdata=array();
	$ydata=array();
	$MAIN=array();
	$xdata=array();
	$ydata=array();
	$influx=new influx();
	$sock=new sockets();
	
	echo "# # # # # # # # # # # # # # # # # # # # #\n";
	echo "# # # # # # # # FLUX_HOUR # # # # # # # #\n";
	echo "# # # # # # # # # # # # # # # # # # # # #\n";
	
	
	$q=new mysql_squid_builder();
	$q2=new postgres_sql();
	if(!$q->TABLE_EXISTS("dashboard_size_day")){
		if($q2->TABLE_EXISTS("access_log")){
			FLUX_HOUR_POSTGRES();
			return;
			
		}
		
		return;
	}
	
	
	
	if($q->TABLE_EXISTS("dashboard_size_day")){
		build_progress("{refresh_dashboard_values} FLUX HOUR",16);
		$sql="SELECT SUM(SIZE) as SIZE,TIME FROM dashboard_size_day GROUP BY TIME ORDER BY TIME ASC";
		echo "FLUX_HOUR:: MySQL ****************** $sql **********************\n";
		$results=$q->QUERY_SQL($sql);
		
		events("$sql -> ".mysql_num_rows($results)." items");
		if(!$q->ok){events($q->mysql_error);}
		
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$size=intval($ligne["SIZE"])/1024;
			$size=$size/1024;
			$time=strtotime($ligne["TIME"]);
			$min=date("l H:i:00",$time);
			echo "FLUX_HOUR: $min = $size\n";
			$xdata[]=$min;
			$ydata[]=$size;
		}
		
		build_progress("{refresh_dashboard_values} FLUX HOUR ".count($xdata)." items",16);
		$MAIN["xdata"]=$xdata;
		$MAIN["ydata"]=$ydata;
		
		
		echo "# # # FLUX_HOUR:: ".count($xdata)." ITEMS # # #\n";
		
		@file_put_contents("{$GLOBALS["BASEDIR"]}/FLUX_HOUR", serialize($MAIN));
		if(count($xdata)<2){@unlink("{$GLOBALS["BASEDIR"]}/FLUX_HOUR");FLUX_HOUR_POSTGRES();}
	}
	
	
	// -----------------------------------------------------------------------------------------------------
	
	if($q->TABLE_EXISTS("dashboard_countwebsite_day")){
		$sql="SELECT FAMILYSITE, SUM(SIZE) as SIZE FROM dashboard_countwebsite_day GROUP BY FAMILYSITE ORDER BY SIZE DESC LIMIT 1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		@unlink("{$GLOBALS["BASEDIR"]}/TOP_WEBSITE");
		if($ligne["SIZE"]>0){
			@file_put_contents("{$GLOBALS["BASEDIR"]}/TOP_WEBSITE", serialize(array($ligne["SIZE"],$ligne["FAMILYSITE"])));
			
		}
	}
	// -----------------------------------------------------------------------------------------------------	
	
	if($q->TABLE_EXISTS("dashboard_user_day")){
		$sql="SELECT USER, SUM(SIZE) as SIZE FROM dashboard_user_day GROUP BY USER ORDER BY SIZE DESC LIMIT 1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		events("TOP USER: {$ligne["SIZE"]} : {$ligne["USER"]}");
		@unlink("{$GLOBALS["BASEDIR"]}/TOP_USER");
		if($ligne["SIZE"]>0){
			echo "TOP USER: saving {$GLOBALS["BASEDIR"]}/TOP_USER\n";
			@file_put_contents("{$GLOBALS["BASEDIR"]}/TOP_USER", serialize(array($ligne["SIZE"],$ligne["USER"])));
		
		}
	}	
	// -----------------------------------------------------------------------------------------------------
	if($q->TABLE_EXISTS("dashboard_blocked_day")){	
		build_progress("{refresh_dashboard_values} TOP_BLOCKED",16);
		@unlink("{$GLOBALS["BASEDIR"]}/TOP_BLOCKED");
		$sql="SELECT WEBSITE, SUM(RQS) as RQS FROM dashboard_blocked_day GROUP BY WEBSITE ORDER BY RQS DESC LIMIT 1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		events("TOP_BLOCKED: {$ligne["RQS"]} : {$ligne["WEBSITE"]}");
		@unlink("{$GLOBALS["BASEDIR"]}/TOP_BLOCKED");
		if($ligne["RQS"]>0){
			@file_put_contents("{$GLOBALS["BASEDIR"]}/TOP_BLOCKED", serialize(array($ligne["RQS"],$ligne["WEBSITE"])));
		
		}
	}
	// -----------------------------------------------------------------------------------------------------	
	$now=InfluxQueryFromUTC(strtotime("-24 hour"));
	$MAIN=array();
	$xdata=array();
	$ydata=array();
	$influx=new influx();
	$sock=new sockets();
	$ipClass=new IP();
	$q=new mysql_squid_builder();
	
	@unlink("{$GLOBALS["BASEDIR"]}/MEMBERS_GRAPH");
	$q=new mysql_squid_builder();
	
	
	if($q->TABLE_EXISTS("dashboard_countuser_day")){
		build_progress("{refresh_dashboard_values}",50);
		$sql="SELECT COUNT(USER) AS TCOUNT,TIME FROM dashboard_user_day GROUP BY TIME ORDER BY TIME ASC";
		echo "MEMBERS_GRAPH:: ****************** $sql **********************\n";
		$results=$q->QUERY_SQL($sql);
		$CountDedashboard_countuser_day=mysql_num_rows($results);
		events("$sql -> $CountDedashboard_countuser_day items");
		
		if(!$q->ok){events($q->mysql_error);}
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				
				$xdata[]=$ligne["TIME"];
				$ydata[]=$ligne["TCOUNT"];
		}
		
		$MAIN["xdata"]=$xdata;
		$MAIN["ydata"]=$ydata;
		if(count($ydata)>1){@file_put_contents("{$GLOBALS["BASEDIR"]}/MEMBERS_GRAPH", serialize($MAIN));}
	}
	build_progress("{done} FLUX MEMBERS_GRAPH",100);
	// -----------------------------------------------------------------------------------------------------
}

function FLUX_RQS_POSTGRES(){
	$now=date("Y-m-d H:i:s",strtotime("-24 hour"));
	$q=new postgres_sql();
	
	$TimeGroup="date_trunc('hour', zdate) as zdate";
	$sql="SELECT SUM(RQS) as RQS,$TimeGroup FROM access_log 
	WHERE zdate>='$now' GROUP BY zdate ORDER BY zdate ASC";
	
	
	
	echo "FLUX_RQS_POSTGRES:: POSTGRES: ******************\n $sql\n **********************\n";
	$results=$q->QUERY_SQL($sql);
	
	events("$sql -> ".pg_num_rows($results)." items");
	if(!$q->ok){events($q->mysql_error);}
	while($ligne=@pg_fetch_assoc($results)){
		$min=date("l H:i",strtotime($ligne["zdate"]));
		echo "FLUX_RQS: $min = {$ligne["rqs"]}\n";
		$xdata[]=$min;
		$ydata[]=$ligne["rqs"];
	}
	$MAIN["xdata"]=$xdata;
	$MAIN["ydata"]=$ydata;
	@file_put_contents("{$GLOBALS["BASEDIR"]}/FLUX_RQS", serialize($MAIN));
	if(count($xdata)<2){@unlink("{$GLOBALS["BASEDIR"]}/FLUX_RQS");}
	
	
}

function FLUX_RQS(){
	
	$MAIN=array();
	$xdata=array();
	$ydata=array();
	
	
	
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS("dashboard_size_day")){DUMP_HOUR();return;}
	$sql="SELECT SUM(RQS) as RQS,TIME FROM dashboard_size_day GROUP BY TIME ORDER BY TIME ASC";
	echo "FLUX_RQS:: dashboard_size_day ****************** $sql **********************\n";
	$results=$q->QUERY_SQL($sql);
	events("$sql -> ".mysql_num_rows($results)." items");
	if(!$q->ok){events($q->mysql_error);}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$time=strtotime($ligne["TIME"]);
		$min=date("l H:i",$time);
		echo "FLUX_RQS: $min = {$ligne["RQS"]}\n";
		$xdata[]=$min;
		$ydata[]=$ligne["RQS"];
	}
	$MAIN["xdata"]=$xdata;
	$MAIN["ydata"]=$ydata;
	@file_put_contents("{$GLOBALS["BASEDIR"]}/FLUX_RQS", serialize($MAIN));
	if(count($xdata)<2){@unlink("{$GLOBALS["BASEDIR"]}/FLUX_RQS");FLUX_RQS_POSTGRES();}
	// -----------------------------------------------------------------------------------------------------
	DUMP_HOUR();
	
}

function DUMP_HOUR_PROGRESS($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/DUMP_HOUR_PROGRESS";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	}

function DUMP_HOUR(){
	
	$TimeFile="/etc/artica-postfix/pids/exec.squid.interface-size.php.DUMP_HOUR.time";
	$unix=new unix();
	$xtime=$unix->file_time_min($TimeFile);
	if(!$GLOBALS["FORCE"]){
		if(!$GLOBALS["VERBOSE"]){
			if($xtime<119){events("Aborting current {$xtime}mn, require 2h minimal");return;}
		}
	}
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	
	
	$sock=new sockets();
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
	
	if(!$q->ok){
		DUMP_HOUR_PROGRESS("{mysql_error} CREATE TABLE",110);
		echo $q->mysql_error."\n";
		events("FATAL: $q->mysql_error");
		return;
	}
	
	
	
	
	$MySQLStatisticsRetentionDays=intval($sock->GET_INFO("MySQLStatisticsRetentionDays"));
	if($MySQLStatisticsRetentionDays==0){$MySQLStatisticsRetentionDays=5;}
	
	$postgres=new postgres_sql();
	events("MySQL Statistics Retention Days:$MySQLStatisticsRetentionDays");
	$c=0;
	$TRUNCATE=false;
	$prefix="INSERT IGNORE INTO `dashboard_volume_day` (`TIME`,`FAMILYSITE`,`USERID`,`IPADDR`,`MAC`,`CATEGORY`,`SIZE`,`RQS`) VALUES ";
	
	
	$timeQuery=time();
	$TimeGroup="date_trunc('hour', zdate) as zdate";
	$timeQuery=date("Y-m-d H:i:s",strtotime("-$MySQLStatisticsRetentionDays day"));
		
	$sql="SELECT SUM(size) as size, SUM(RQS) as RQS,
	familysite,ipaddr,mac,userid,category,
	$TimeGroup	FROM access_log 
	WHERE zdate > '$timeQuery'
	GROUP BY zdate,familysite,ipaddr,mac,userid,category ORDER by zdate";
	
	 
	echo "******************************\n$sql\n******************************\n";
	
	
	DUMP_HOUR_PROGRESS("{query}...",50);
	$results=$postgres->QUERY_SQL($sql);
	if(!$postgres->ok){
		DUMP_HOUR_PROGRESS("{mysql_error} QUERY",110);
		echo $postgres->mysql_error."\n";
		events("FATAL: $q->mysql_error");
		return;
		
	}	
	
	
		
		$d=0;
		while($ligne=@pg_fetch_assoc($results)){
			$CATEGORY=null;
			$zDate=$ligne["zdate"];
			
			$FAMILYSITE=mysql_escape_string2($ligne["familysite"]);
			$IPADDR=mysql_escape_string2($ligne["ipaddr"]);
			$USERID=mysql_escape_string2($ligne["userid"]);
			$MAC=mysql_escape_string2($ligne["mac"]);
			$RQS=mysql_escape_string2($ligne["rqs"]);
			$SIZE=mysql_escape_string2($ligne["size"]);
			$CATEGORY=mysql_escape_string2($ligne["category"]);
			$RSQL[]="('$zDate','$FAMILYSITE','$USERID','$IPADDR','$MAC','$CATEGORY','$SIZE','$RQS')";
			$c++;
			$d++;
			if(count($RSQL)>500){
				echo "$c...\n";
				if(!$TRUNCATE){events("dashboard_volume_day:TRUNCATE TABLE");$q->QUERY_SQL("TRUNCATE TABLE `dashboard_volume_day`");$TRUNCATE=TRUE;}
				$q->QUERY_SQL($prefix.@implode(",", $RSQL));
				if(!$q->ok){events("FATAL! $q->mysql_error");
					DUMP_HOUR_PROGRESS("{mysql_error} at $c",110);
					return;
				}
				$RSQL=array();
			}
			
		}
			
		if(count($RSQL)>0){
			if(!$TRUNCATE){events("dashboard_volume_day:TRUNCATE TABLE");$q->QUERY_SQL("TRUNCATE TABLE `dashboard_volume_day`");$TRUNCATE=TRUE;}
			$q->QUERY_SQL($prefix.@implode(",", $RSQL));
			if(!$q->ok){events("FATAL! $q->mysql_error");
			DUMP_HOUR_PROGRESS("{mysql_error} at $c",110);
			return;}
			$RSQL=array();
		}
		
	DUMP_HOUR_PROGRESS("Total $c inserted rows",80);
	events("dashboard_volume_day: Total $c inserted rows");
	echo "******************************\nFAMILY_SITES_DAY();\n******************************\n";
	
	echo "******************************\nFULL_USERS_DAY();\n******************************\n";
	DUMP_HOUR_PROGRESS("FULL_USERS_DAY",90);
	FULL_USERS_DAY();
	DUMP_HOUR_PROGRESS("{done}",100);
}


function FULL_USERS_DAY(){
	
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS("dashboard_user_day")){return;}
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `FULL_USERS_DAY` (
			`user` varchar(128) NOT NULL,
			`hits` BIGINT UNSIGNED NOT NULL,
			`size` BIGINT UNSIGNED NOT NULL,
			PRIMARY KEY `user` (`user`),
			KEY `hits` (`hits`),
			KEY `size` (`size`)
	) ENGINE=MYISAM;");
	
	
	$sql="SELECT SUM(SIZE) as size,SUM(RQS) AS RQS,USER FROM dashboard_user_day GROUP BY USER";
	$f=array();
	$results=$q->QUERY_SQL($sql);
	events("dashboard_user_day ".mysql_num_rows($results)." items");
	if(!$q->ok){events($q->mysql_error);return;}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
		$size=$ligne["size"];
		$hits=$ligne["RQS"];
		$user=mysql_escape_string2($ligne["USER"]);
		$f[]="('$user','$size','$hits')";
	}
	
	if(count($f)>0){
		$q->QUERY_SQL("TRUNCATE TABLE `FULL_USERS_DAY`");
		$sql="INSERT INTO `FULL_USERS_DAY` (`user`,`size`,`hits`) VALUES ".@implode(",", $f);
		$q->QUERY_SQL($sql);
		if(!$q->ok){events("FATAL[".__LINE__."]! $q->mysql_error");}
	}
	
	$USERS=$q->COUNT_ROWS("FULL_USERS_DAY");
	if($USERS>0){@file_put_contents("{$GLOBALS["BASEDIR"]}/MEMBERS_COUNT", $USERS);}
	
	
}


function FAMILY_SITES_DAY(){
	$q=new mysql_squid_builder();	
	if(!$q->TABLE_EXISTS("dashboard_countwebsite_day")){return;}

	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `FAMILY_SITES_DAY` (
			`familysite` varchar(128) NOT NULL,
			`hits` BIGINT UNSIGNED NOT NULL,
			`size` BIGINT UNSIGNED NOT NULL,
			PRIMARY KEY `familysite` (`familysite`),
			KEY `hits` (`hits`),
			KEY `size` (`size`)
	) ENGINE=MYISAM;");	
	
	$f=array();
	$sql="SELECT SUM(SIZE) as SIZE,SUM(RQS) as HITS,FAMILYSITE FROM dashboard_countwebsite_day GROUP BY FAMILYSITE";
	
	
	$results=$q->QUERY_SQL($sql);
	$CountFoRows=mysql_num_rows($results);
	if($CountFoRows==0){return;}
	events("dashboard_countwebsite_day -> ".mysql_num_rows($results)." items");
	
	$c=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
	
		$FAMILYSITE=$ligne["FAMILYSITE"];
		$SIZE=$ligne["SIZE"];
		$HITS=$ligne["HITS"];
		$f[]="('$FAMILYSITE','$HITS','$SIZE')";
	
	}
	
	if(count($f)>0){
		@file_put_contents("{$GLOBALS["BASEDIR"]}/SUM_FAMILYSITES", count($f));
		$q->QUERY_SQL("TRUNCATE TABLE FAMILY_SITES_DAY");
		@unlink("{$GLOBALS["BASEDIR"]}/TOP_FAMILYSITES_GRAPH");
		@unlink("{$GLOBALS["BASEDIR"]}/SUM_FAMILYSITES");
		
		
		$q->QUERY_SQL("INSERT IGNORE INTO FAMILY_SITES_DAY (familysite,hits,size) VALUES ".@implode(",", $f));
		$sql="SELECT size,familysite FROM FAMILY_SITES_DAY ORDER BY size DESC LIMIT 0,10";
		$SUM_FAMILYSITES=$q->COUNT_ROWS("FAMILY_SITES_DAY");
		$results=$q->QUERY_SQL($sql);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$MAIN[$ligne["familysite"]]=$ligne["size"];
		}
	
		@file_put_contents("{$GLOBALS["BASEDIR"]}/SUM_FAMILYSITES", $SUM_FAMILYSITES);
		@file_put_contents("{$GLOBALS["BASEDIR"]}/TOP_FAMILYSITES_GRAPH", serialize($MAIN));
	}
	
	// -----------------------------------------------------------------------------------------------------	
	
}

function USERAGENTS(){
	echo __FUNCTION__."\n";
	$now=InfluxQueryFromUTC(strtotime("-4 hour"));
	$MAIN=array();
	$xdata=array();
	$ydata=array();
	$influx=new influx();
	$sock=new sockets();
	$UserAgentsStatistics=intval($sock->GET_INFO("UserAgentsStatistics"));
	if($UserAgentsStatistics==0){return;}
	$sql="SELECT MAC,RQS,SIZE,UID,USERAGENT FROM useragents WHERE time>{$now}s";
	echo __FUNCTION__.": QUERY\n";
	$main=$influx->QUERY_SQL($sql);
	echo __FUNCTION__.": PARSING\n";
	foreach ($main as $row) {
		
		$SIZE=intval($row->SIZE);
		$RQS=intval($row->RQS);
		$UID=$row->UID;
		$MAC=$row->MAC;
		$USERAGENT=$row->USERAGENT;
		if($MAC==null){if($UID==null){continue;}}
		
		
		$md5=md5("$UID$USERAGENT$MAC");
		if(!isset($TMAIN[$md5])){
			$TMAIN[$md5]["UID"]=$UID;
			$TMAIN[$md5]["USERAGENT"]=$USERAGENT;
			$TMAIN[$md5]["MAC"]=$MAC;
			$TMAIN[$md5]["RQS"]=$RQS;
			$TMAIN[$md5]["SIZE"]=$SIZE;
		}else{
			$TMAIN[$md5]["SIZE"]=$TMAIN[$md5]["SIZE"]+$SIZE;
			$TMAIN[$md5]["RQS"]=$TMAIN[$md5]["RQS"]+$RQS;
		}
		
	}

	while (list ($md5, $array) = each ($TMAIN) ){
		$USERAGENT=trim($array["USERAGENT"]);
		$MAC=$array["MAC"];
		$RQS=$array["RQS"];
		$SIZE=$array["SIZE"];
		$UID=$array["UID"];
		$USERAGENT=mysql_escape_string2($USERAGENT);
		if($GLOBALS["VERBOSE"]){echo "('$USERAGENT','$SIZE','$RQS','$MAC','$UID')\n";}
		$f[]="('$USERAGENT','$SIZE','$RQS','$MAC','$UID')";
	}
	
	if(count($f)>0){
		$q=new mysql_squid_builder();
		$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS USERAGENTS4H (
				`hits` BIGINT UNSIGNED, 
				`size` BIGINT UNSIGNED,
				`USERAGENT` VARCHAR(128) NOT NULL ,
				`UID` VARCHAR(128) NOT NULL,
				`MAC` VARCHAR(128) NOT NULL,
				KEY `hits` (`hits`), 
				KEY `size` (`size`),
				KEY `UID` (`UID`),
				KEY `MAC` (`MAC`),
				KEY `USERAGENT` (`USERAGENT`)
				) ENGINE=MYISAM");
		$q->QUERY_SQL("TRUNCATE TABLE USERAGENTS4H");
		$q->QUERY_SQL("INSERT IGNORE INTO USERAGENTS4H (USERAGENT,size,hits,MAC,UID) VALUES ".@implode(",", $f));
	}
	
}

function squidhour_clean(){
	$array=array();
	$q=new mysql_squid_builder();
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs'"; 
		
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){writelogs("Fatal Error: $q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
	if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(preg_match("#squidhour_[0-9]+#", $ligne["c"])){
			$array[$ligne["c"]]=$ligne["c"];
			continue;
		}
		if(preg_match("#searchwordsD_[0-9]+#", $ligne["c"])){
			$array[$ligne["c"]]=$ligne["c"];
			continue;
		}
		if(preg_match("#searchwords_[0-9]+#", $ligne["c"])){
			$array[$ligne["c"]]=$ligne["c"];
			continue;
		}
		if(preg_match("#[0-9]+_gsize#", $ligne["c"])){
			$array[$ligne["c"]]=$ligne["c"];
			continue;
		}
		if(preg_match("#[0-9]+_dcache#", $ligne["c"])){
			$array[$ligne["c"]]=$ligne["c"];
			continue;
		}
		if(preg_match("#[0-9]+_users#", $ligne["c"])){
			$array[$ligne["c"]]=$ligne["c"];
			continue;
		}
		
	
		
	}
		
	if(count($array)>0){
		while (list ($tablename, $value) = each ($array) ){
			$q->QUERY_SQL("DROP TABLE $tablename");
		}
	}	
		
	
	
}


function WEBFILTERING(){
	$q=new postgres_sql();
	$now=date("Y-m-d H:i:s",strtotime("-4 hour"));
	
	$t1=time();
	$date=date("YW");
	// -----------------------------------------------------------------------------------------------------
	$MAIN=array();
	$xdata=array();
	$ydata=array();
	
	@unlink("{$GLOBALS["BASEDIR"]}/BLOCKED_HOUR");
	@unlink("{$GLOBALS["BASEDIR"]}/BLOCKED_CHART1");
	@unlink("{$GLOBALS["BASEDIR"]}/BLOCKED_CHART2");
	$sql="SELECT count(*),date_trunc('hour', zDate) as zDate FROM webfilter WHERE zDate >'{$now}' GROUP BY zDate";
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n";}
	$c=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$min=$ligne["stime"];
		$size=$ligne["tcount"];
		if($size==0){continue;}
		if($GLOBALS["VERBOSE"]){echo "$row->time: $min -> $size<bR>\n";}
		$xdata[]=$min;
		$ydata[]=$size;
		$c++;
	}
	$MAIN["xdata"]=$xdata;
	$MAIN["ydata"]=$ydata;
	if($c>1){
		@file_put_contents("{$GLOBALS["BASEDIR"]}/BLOCKED_HOUR", serialize($MAIN));
	}
	
	
	
	$MAIN=array();
	$xdata=array();
	$ydata=array();
	
	$c=0;
	$results=$q->QUERY_SQL("SELECT COUNT(*) as tcount,rulename FROM webfilter  WHERE zDate >'{$now}' GROUP BY rulename ORDER BY tcount DESC LIMIT 10");
	if(!$q->ok){echo $q->mysql_error."\n";}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["tcount"];
		$rule=$ligne["rulename"];
		if($GLOBALS["VERBOSE"]){echo "$rule -> $size<bR>\n";}
		$MAIN[$rule]=$size;
		$c++;
	}
	
	if($c>0){
		@file_put_contents("{$GLOBALS["BASEDIR"]}/BLOCKED_CHART1", serialize($MAIN));
	}
	
	
	$sql="SELECT COUNT(website) as tcount,website FROM webfilter WHERE zDate >'{$now}' GROUP BY website ORDER BY tcount DESC LIMIT 10";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n";}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($GLOBALS["VERBOSE"]){echo "{$ligne["website"]} -> {$ligne["tcount"]}<bR>\n";}
		$MAIN[$ligne["website"]]=$ligne["tcount"];
	}
	
	@file_put_contents("{$GLOBALS["BASEDIR"]}/BLOCKED_CHART2", serialize($MAIN));
	
		
	
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
	$logFile="/var/log/artica-parse.hourly.log";
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];
	$suffix=date("Y-m-d H:i:s")." [".basename(__FILE__)."/$function/$line]:";


	if (is_file($logFile)) {
		$size=filesize($logFile);
		if($size>1000000){@unlink($logFile);}
	}
	$f = @fopen($logFile, 'a');
	if($GLOBALS["VERBOSE"]){echo "$suffix $text (system load:{$internal_load})\n";}
	@fwrite($f, "$suffix $text (system load:{$internal_load})\n");
	@fclose($f);
}


function parse(){
	$TimeFile="/etc/artica-postfix/pids/exec.squid.interface-size.php.time";
	$pidfile="/etc/artica-postfix/pids/exec.squid.interface-size.php.pid";
	$unix=new unix();
	$sock=new sockets();
	$EnableKerbAuth=intval($sock->GET_INFO("EnableKerbAuth"));
	$ActiveDirectoryEmergency=intval($sock->GET_INFO("ActiveDirectoryEmergency"));
	if(!is_file("/etc/artica-postfix/settings/Daemons/SQUIDEnable")){@file_put_contents("/etc/artica-postfix/settings/Daemons/SQUIDEnable", 1);}
	if($ActiveDirectoryEmergency==1){$EnableKerbAuth=0;}
	
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["VERBOSE"]){echo "$pid already executed since {$timepid}Mn\n";}
		if(!$GLOBALS["FORCE"]){
			if($timepid<14){return;}
			$kill=$unix->find_program("kill");
			unix_system_kill_force($pid);
		}
	}
	
	@file_put_contents($pidfile, getmypid());
	if(!$GLOBALS["FORCE"]){
	if(!$GLOBALS["VERBOSE"]){
		$time=$unix->file_time_min($TimeFile);
		if($time<14){
			echo "Current {$time}Mn, require at least 14mn\n";
			return;
		}
	}}
	
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	$sock=new sockets();
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	events("Proxy performance set to $SquidPerformance");
	build_progress("{refresh_dashboard_values}",10);
	system_values();
	$php=$unix->LOCATE_PHP5_BIN();
	
	build_progress("{refresh_dashboard_values}",11);
	$dateint=InfluxQueryFromUTC(strtotime("-48 hours"));
	$date=date("Y-m-d H:00:00",$dateint);
	$qSimple=new mysql();
	$sql="SELECT COUNT(ID) as tcount FROM squid_admin_mysql WHERE severity=0 AND zDate>'$date'";
	$ligne=mysql_fetch_array($qSimple->QUERY_SQL($sql,"artica_events"));
	@file_put_contents("{$GLOBALS["BASEDIR"]}/WATCHDOG_COUNT_EVENTS", $ligne["tcount"]);
	@chmod("{$GLOBALS["BASEDIR"]}/WATCHDOG_COUNT_EVENTS", 0777);
	
	build_progress("{refresh_dashboard_values} APACHE_STATISTICS (2)",11);
	APACHE_STATISTICS();
	build_progress("{refresh_dashboard_values} NETWORK_INTERFACES_RXTX (3)",11);
	NETWORK_INTERFACES_RXTX();
	build_progress("{refresh_dashboard_values} COUNT_OF_SURICATA (4)",11);
	COUNT_OF_SURICATA();
	build_progress("{refresh_dashboard_values} NOT_CATEGORIZED (5)",11);
	NOT_CATEGORIZED();
	
	
	
	$SQUIDEnable=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SQUIDEnable"));
	if($SQUIDEnable==0){
		build_progress("{done}",100);
		return;
	}
	
	if($SquidPerformance>1){
		if(is_file("/etc/cron.d/artica-stats-hourly")){
			@unlink("/etc/cron.d/artica-stats-hourly");
			system("/etc/init.d/cron reload");
		}
		build_progress("{statistics_are_disabled}",110);
		die();
	}
	
	
	if(!is_file("/etc/cron.d/artica-stats-hourly")){@unlink("/etc/cron.d/artica-stats-hourly");}

	
	
	@mkdir("/usr/share/artica-postfix/ressources/interface-cache",0755,true);
	$t1=time();
	
	$q=new mysql_squid_builder();
	$tables[]="dashboard_size_day";
	$tables[]="dashboard_countwebsite_day";
	$tables[]="dashboard_countuser_day";
	$tables[]="dashboard_user_day";
	$tables[]="dashboard_notcached";
	$tables[]="dashboard_cached";
	$tables[]="dashboard_blocked_day";
	
	
	while (list ($num, $table) = each ($tables) ){
		if(!$q->TABLE_EXISTS($table)){events("Table: $table is not yet ready...");continue;}
		$NUM=$q->COUNT_ROWS($table);
		events("Table: $table $NUM rows");
	}
	
	build_progress("{calculate_cache_rate}",12);
	CachedOrNot();
	squidhour_clean();
	$t1=time();
	
	
	$influx=new influx();
	$now=InfluxQueryFromUTC(strtotime("-24 hour"));
	
	build_progress("{refresh_dashboard_values}",13);
// -----------------------------------------------------------------------------------------------------	
	build_progress("{refresh_dashboard_values}",14);

// -----------------------------------------------------------------------------------------------------	
	build_progress("{cleaning_databases}",16);
	squidhour_clean();
	build_progress("{refresh_dashboard_values}",17);	
	FLUX_RQS();
	build_progress("{refresh_dashboard_values}",18);
	build_progress("{refresh_dashboard_values}",19);
	//USERAGENTS();
	build_progress("{calculate_dates}",20);
	MAX_MIN();
	backup_size();
	build_progress("{refresh_dashboard_values}",21);
	WEBFILTERING();
	build_progress("{refresh_dashboard_values}",22);
	$f=array();

// -----------------------------------------------------------------------------------------------------
	$q=new mysql_squid_builder();
	$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(*) as tcount FROM proxy_ports WHERE enabled=1 AND transparent=1 AND Tproxy=1"));
	if($q->ok){
		@file_put_contents("{$GLOBALS["BASEDIR"]}/COUNT_DE_TRANSPARENT",intval($ligne["tcount"]));
	}
// -----------------------------------------------------------------------------------------------------	
	
	build_progress("{refresh_dashboard_values}",51);
	$MAIN=array();
	$xdata=array();
	$ydata=array();
	$f=array();	
	
	// -----------------------------------------------------------------------------------------------------
	// Calcul des caches en cours.
	
	$SquidCacheLevel=$sock->GET_INFO("SquidCacheLevel");
	if(!is_numeric($SquidCacheLevel)){$SquidCacheLevel=4;}
	
	if($SquidCacheLevel==0){
		@file_put_contents("{$GLOBALS["BASEDIR"]}/COUNT_DE_CACHES",0);
	}
	
	build_progress("{refresh_dashboard_values}",52);
	$q=new mysql();
	$sql="SELECT cache_size,cache_type FROM squid_caches_center WHERE remove=0";
	$xsize=0;
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$cache_size=$ligne["cache_size"];
		$cache_type=$ligne["cache_type"];
		if($cache_type=="Cachenull"){continue;}
		$xsize=$xsize+$cache_size;
	}
	
	if($GLOBALS["VERBOSE"]){echo "COUNT_DE_CACHES: {$xsize}MB\n";}
	@file_put_contents("{$GLOBALS["BASEDIR"]}/COUNT_DE_CACHES",$xsize);
	
	
	if($GLOBALS["PROGRESS"]){
		build_progress("{refresh_dashboard_values}",90);
		system("$php /usr/share/artica-postfix/exec.status.php --all --verbose");
		
	}
	
	build_progress("{refresh_dashboard_values} {done}",100);
	
	// -----------------------------------------------------------------------------------------------------	
}


function NETWORK_INTERFACES_RXTX(){
	$unix=new unix();
	$influx=new influx();
	$NETS=$unix->NETWORK_ALL_INTERFACES();
	$hostname=$unix->hostname_g();
	$now=date("Y-m-d H:i:s",strtotime("-24 hour"));
	$q=new postgres_sql();
	
	// -----------------------------------------------------------------------------------------------------
	while (list ($Interface, $array) = each ($NETS) ){
	
		$sql="SELECT SUM(RX) as size FROM ethrxtx,date_trunc('hour', zdate) as zdate 
		WHERE zdate > '{$now}'
		AND eth='$Interface' AND proxyname='$hostname' GROUP BY zdate ORDER BY zdate ASC";
	
		if($GLOBALS["VERBOSE"]){echo "\n*****\n$sql\n******\n";}
		$MAIN=array();
		$xdata=array();
		$ydata=array();
		$results=$q->QUERY_SQL($sql);
	
		while($ligne=@pg_fetch_assoc($results)){
			
			$min=$ligne["zdate"];
			$size=intval($ligne["size"])/1024;
			if($GLOBALS["VERBOSE"]){echo "($min): ethrxtx $Interface:RX: $min -> $size\n";}
	
			$size=$size/1024;
			if(round($size)==0){continue;}
			$xdata[]=$min;
			$ydata[]=round($size);
		}
		$MAIN["xdata"]=$xdata;
		$MAIN["ydata"]=$ydata;
		@file_put_contents("{$GLOBALS["BASEDIR"]}/FLUX_{$Interface}_RX", serialize($MAIN));
		if(count($xdata)<2){@unlink("{$GLOBALS["BASEDIR"]}/FLUX_{$Interface}_RX");}
	
	
		$sql="SELECT SUM(TX) as size,date_trunc('hour', zdate) as zdate FROM ethrxtx 
		WHERE zdate > '{$now}' AND eth='$Interface'  
		AND proxyname='$hostname' GROUP BY zdate ORDER BY zdate ASC";
	
		$MAIN=array();
		$xdata=array();
		$ydata=array();
		build_progress("{refresh_dashboard_values}",15);
		$results=$q->QUERY_SQL($sql);
	
		while($ligne=@pg_fetch_assoc($results)){
			$min=$ligne["zdate"];
			$size=intval($ligne["size"])/1024;
			if($GLOBALS["VERBOSE"]){echo "($min): ethrxtx $Interface:RX: $min -> $size\n";}
	
			$size=$size/1024;
			if(round($size)==0){continue;}
			$xdata[]=$min;
			$ydata[]=round($size);
		}
		$MAIN["xdata"]=$xdata;
		$MAIN["ydata"]=$ydata;
		@file_put_contents("{$GLOBALS["BASEDIR"]}/FLUX_{$Interface}_TX", serialize($MAIN));
		if(count($xdata)<2){@unlink("{$GLOBALS["BASEDIR"]}/FLUX_{$Interface}_TX");}
	
	}
}



function parse_stats(){
	events("parse_stats(): starting");
	$pidfile="/etc/artica-postfix/pids/exec.squid.interface-size.php.parse_stats.pid";
	$unix=new unix();
	
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["VERBOSE"]){echo "$pid already executed since {$timepid}Mn\n";}
		if($timepid<10){
			events("parse_stats(): $pid already executed since {$timepid}Mn");
			return;
		}
		$kill=$unix->find_program("kill");
		unix_system_kill_force($pid);
	}
	
	
	@mkdir("/home/artica-postfix/squid/StatsApplicance/BEREKLEY",0755,true);
	
	
	
	
	if(!is_dir("/usr/share/artica-postfix/ressources/conf/upload/BEREKLEY")){return;}
	if (!$handle = opendir("/usr/share/artica-postfix/ressources/conf/upload/BEREKLEY")) {return;}
	
		while (false !== ($fileZ = readdir($handle))) {
			if($fileZ=="."){continue;}
			if($fileZ==".."){continue;}
			events("parse_stats(): Scanning upload/BEREKLEY/$fileZ");
			$path="/usr/share/artica-postfix/ressources/conf/upload/BEREKLEY/$fileZ";
			
			if(@copy($path, "/home/artica-postfix/squid/StatsApplicance/BEREKLEY/$fileZ")){
				events("parse_stats(): Move $path to /home/artica-postfix/squid/StatsApplicance/BEREKLEY");
				@unlink($path);
				continue;
			}
			
			/*if(preg_match("#^(.+?)-UserAuthDB\.db#", $fileZ,$re)){
				
				//parse_userauthdb($path,$re[1]);
				@unlink($path);
				}
				continue;
				
			}
		
			if(preg_match("#^(.+?)-[0-9]+_QUOTASIZE\.db#", $fileZ,$re)){
				//ParseDB_FILE($path,$re[1]);
				@unlink($path);
				continue;
			}*/	
		}
}


function PUSH_STATS_FILE($filepath){
	$sock=new sockets();
	$unix=new unix();
	$q=new mysql_squid_builder();
	$EnableSquidRemoteMySQL=intval($sock->GET_INFO("EnableSquidRemoteMySQL"));
	events("PUSH_STATS_FILE: EnableSquidRemoteMySQL = $EnableSquidRemoteMySQL");
	
	$WizardStatsAppliance=unserialize(base64_decode($sock->GET_INFO("WizardStatsAppliance")));
	if(isset($WizardStatsAppliance["SERVER"])){if($WizardStatsAppliance["SERVER"]<>null){ $EnableSquidRemoteMySQL=1; } }
	
	
	$proto="http";
	if($WizardStatsAppliance["SSL"]==1){$proto="https";}
	$uri="$proto://{$WizardStatsAppliance["SERVER"]}:{$WizardStatsAppliance["PORT"]}/nodes.listener.php";
	if($EnableSquidRemoteMySQL==0){return false;}
	$size=@filesize($filepath);
	$filename=basename($filepath);
	$array=array(
			"SQUID_BEREKLEY"=>true,
			"UUID"=>$unix->GetUniqueID(),
			"HOSTNAME"=>$unix->hostname_g(),"SIZE"=>$size,"FILENAME"=>$filename);
	
	
	$curl=new ccurl($uri,false,null,true);
	$curl->x_www_form_urlencoded=false;
	
	if(!$curl->postFile(basename($filepath),$filepath,$array )){
		events("PUSH_STATS_FILE: Failed ".$curl->error);
		return false;
	}
	return true;
	
	
}

function parse_meta($path,$uuid){
	$md_path=md5($path);
	$TimeFile="/etc/artica-postfix/pids/exec.squid.interface-size.php.$uuid.$md_path.time";
	$pidfile="/etc/artica-postfix/pids/exec.squid.interface-size.php.$uuid.$md_path.pid";
	$unix=new unix();
	
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["VERBOSE"]){echo "$pid already executed since {$timepid}Mn\n";}
		if($timepid<10){
			xmeta_events("$pid already executed since {$timepid}Mn",__FUNCTION__,__FILE__,__LINE__);
			return;}
		$kill=$unix->find_program("kill");
		unix_system_kill_force($pid);
	}
	@file_put_contents($pidfile, getmypid());
	$time=$unix->file_time_min($TimeFile);
	if(!$GLOBALS["VERBOSE"]){
		if($time<10){
			xmeta_events("{$time}Mn require at least $time",__FUNCTION__,__FILE__,__LINE__);
			@unlink($path);
			return;
		}
	}
	
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	if($GLOBALS["VERBOSE"]){echo "ParseDB_FILE($path,$uuid,true)\n";}
	xmeta_events("Parsing $path",__FUNCTION__,__FILE__,__LINE__);
	ParseDB_FILE($path,$uuid,true);
	
	if($GLOBALS["VERBOSE"]){echo "Remove $path\n";}
	@unlink($path);
}



function xmeta_events($text,$function,$file,$line){
	$unix=new unix();
	$unix->events($text,"/var/log/artica-meta.log",false,$function,$line,$file);
	
}



function CACHES_AVG(){
	
	$unix=new unix();
	$cache_manager=new cache_manager();
	$data=$cache_manager->makeQuery("storedir",true);
	
	$StoreDir=null;
	foreach ($data as $ligne){
		
		
		if(preg_match("#Current Capacity.*?:\s+(.+?)% used#", $ligne,$re)){
			@file_put_contents("{$GLOBALS["BASEDIR"]}/CACHES_AVG", $re[1]);
			@chmod("{$GLOBALS["BASEDIR"]}/CACHES_AVG",0777);
		}
		
		
		if(preg_match("#Store Directory.*?:(.+)#", $ligne,$re)){
			$StoreDir=trim($re[1]);
			continue;
		}
		
		if(preg_match("#Percent Used:\s+([0-9\.]+)%#", $ligne,$re)){
			if($StoreDir==null){continue;}
			$dats[$StoreDir]["PERC"]=$re[1];
			continue;
		}
		
		if(preg_match("#Maximum Size:\s+([0-9\.]+)#", $ligne,$re)){
			if($StoreDir==null){continue;}
			$dats[$StoreDir]["SIZE"]=$re[1];
			continue;
		}
					
		if(preg_match("#Current Size:\s+([0-9\.]+)#", $ligne,$re)){
			if(isset($dats[$StoreDir]["USED"])){continue;}
			if($GLOBALS["OUTPUT"]){echo "* * * * * * * * *\nDISK \"$ligne\"\nDISK $StoreDir Current Size: {$re[1]}\n* * * * * * * * *\n";}
			$dats[$StoreDir]["USED"]=$re[1];
			continue;
		}
		
		if(preg_match("#Current entries:\s+([0-9\.]+)\s+([0-9\.]+)%#",$ligne,$re)){
			if($GLOBALS["OUTPUT"]){echo "* * * * * * * * *\nDISK \"$ligne\"\nDISK $StoreDir Current entries:{$re[1]} {$re[2]}%\n* * * * * * * * *\n";}
			if($StoreDir==null){continue;}
			$dats[$StoreDir]["ENTRIES"]=$re[1];
			$dats[$StoreDir]["PERC"]=$re[2];
			continue;}
		
				
		if(preg_match("#Filesystem Space in use:\s+([0-9]+)\/#",$ligne,$re)){
			if($StoreDir==null){continue;}
			if(isset($dats[$StoreDir]["USED"])){continue;}
			if($GLOBALS["OUTPUT"]){echo "* * * * * * * * *\nDISK \"$ligne\"\nDISK (2) $StoreDir Current Size: {$re[1]}\n* * * * * * * * *\n";}
			$dats[$StoreDir]["USED"]=$re[1];
		}		
		
		
	}

	$q=new mysql();
	while (list($directory,$arrayStore)=each($dats)){
		$arrayStore["USED"]=intval($arrayStore["USED"]);
		$arrayStore["PERC"]=intval($arrayStore["PERC"]);
	
		if($directory=="MEM"){continue;}
		if($arrayStore["USED"]==0){continue;}
		
		$PERC=$arrayStore["PERC"];
		$USED=$arrayStore["USED"];
	
	
	
		if(preg_match("#\/home\/squid\/cache\/MemBooster([0-9]+)#", $directory,$re)){
			$sql="UPDATE squid_caches_center SET percentcache='$PERC',percenttext='$PERC', `usedcache`='$USED' WHERE ID={$re[1]}";
			echo $sql."\n";
			$q->QUERY_SQL($sql,"artica_backup");
			continue;
		}
	
	
		if($GLOBALS["VERBOSE"]){echo "$directory -> $USED / {$PERC}%\n";}
		$sql="UPDATE squid_caches_center SET percentcache='$PERC',percenttext='$PERC', `usedcache`='$USED' WHERE `cache_dir`='$directory'";
		echo $sql."\n";
		
		$q->QUERY_SQL($sql,"artica_backup");
	}	
	
	
}




function CachedOrNot(){
	events("Running Cache or not....");
	$TimeFile="/etc/artica-postfix/pids/exec.squid.interface-size.php.CachedOrNot.time";

	$unix=new unix();
	
	$TimExec=$unix->file_time_min($TimeFile);
	if(!$GLOBALS["FORCE"]){
		if($GLOBALS["VERBOSE"]){echo "$TimeFile = {$TimExec}mn\n";}
		if(!$GLOBALS["VERBOSE"]){if($TimExec<5){
			events("{$TimExec}mn, require 5mn minimal");
			return;
		}
		}
	}
	
	
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	
	
	$q=new mysql_squid_builder();
	
	$sql="SELECT SUM(SIZE) as SIZE FROM dashboard_notcached";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$size_no_cached=$ligne["SIZE"];
	events("dashboard_notcached: $size_no_cached bytes");
	
	
	$sql="SELECT SUM(SIZE) as SIZE FROM dashboard_cached";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$size_cached=$ligne["SIZE"];
	events("dashboard_cached: $size_no_cached bytes");
	
	$TOTAL=$size_no_cached+$size_cached;
	if($TOTAL>0){
		$CACHED_AVG=($size_cached/$TOTAL)*100;
	}
	
	events("Cached AVG Rate: $CACHED_AVG");

	$CACHES_RATES["TOTALS_NOT_CACHED"]=$size_no_cached;
	$CACHES_RATES["TOTALS_CACHED"]=$size_cached;
	
	if($q->TABLE_EXISTS("dashboard_size_day")){
		$sql="SELECT SUM(RQS) as RQS FROM dashboard_size_day";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$proxy_requests=$ligne["RQS"];
		events("Requests: ".intval($proxy_requests));
	}
	
	if($q->TABLE_EXISTS("dashboard_blocked_day")){
		$sql="SELECT SUM(RQS) as RQS FROM dashboard_blocked_day";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$COUNT_DE_BLOCKED=$ligne["RQS"];
		events("Blocked Requests: ".intval($COUNT_DE_BLOCKED));
	}
	
	@file_put_contents("{$GLOBALS["BASEDIR"]}/COUNT_DE_BLOCKED", intval($COUNT_DE_BLOCKED));
	@file_put_contents("{$GLOBALS["BASEDIR"]}/CACHED_AVG", $CACHED_AVG);
	@file_put_contents("{$GLOBALS["BASEDIR"]}/PROXY_REQUESTS_NUMBER", intval($proxy_requests));
	@file_put_contents("{$GLOBALS["BASEDIR"]}/TOTAL_CACHED", $size_no_cached);
	@file_put_contents("{$GLOBALS["BASEDIR"]}/TOTAL_CACHED_ARRAY", serialize($CACHES_RATES));
	
	@chmod("{$GLOBALS["BASEDIR"]}/TOTAL_CACHED",0777);
	@chmod("{$GLOBALS["BASEDIR"]}/PROXY_REQUESTS_NUMBER",0777);
	
	@chmod("{$GLOBALS["BASEDIR"]}/CACHED_AVG",0777);
	

}


function influxdb_version(){
	if(isset($GLOBALS["influxdb_version"])){return $GLOBALS["influxdb_version"];}
	exec("/opt/influxdb/influxd version 2>&1",$results);
	while (list ($key, $value) = each ($results) ){
		if(preg_match("#InfluxDB v([0-9\-\.a-z]+)#", $value,$re)){
			$GLOBALS["influxdb_version"]=$re[1];
			if($GLOBALS["VERBOSE"]){echo "VERSION: $value...\n";}
			return $GLOBALS["influxdb_version"];
		}
	}
	if($GLOBALS["VERBOSE"]){echo "VERSION: TRY 0.8?\n";}
	exec("/opt/influxdb/influxd -v 2>&1",$results2);
	while (list ($key, $value) = each ($results2) ){
		if(preg_match("#InfluxDB\s+v([0-9\-\.a-z]+)#", $value,$re)){
			$GLOBALS["influxdb_version"]=$re[1];
			if($GLOBALS["VERBOSE"]){echo "VERSION 0.8x: $value...\n";}
			return $GLOBALS["influxdb_version"];
		}
	}

}

function stats_apps_clients(){
	
	$TimeFile="/etc/artica-postfix/settings/Daemons/StatsApplianceReceivers";
	
	$unix=new unix();
	
	$TimExec=$unix->file_time_min($TimeFile);
	if(!$GLOBALS["FORCE"]){
		if($GLOBALS["VERBOSE"]){echo "$TimeFile = {$TimExec}mn\n";}
		if($TimExec<5){return;}
	}
	
	
	@unlink($TimeFile);
	$q=new mysql_squid_builder();
	
	
	if(!$q->TABLE_EXISTS("StatsApplianceReceiver")){
		@file_put_contents($TimeFile, 0);
		@chmod("$TimeFile",0755);
		if($GLOBALS["VERBOSE"]){echo "StatsApplianceReceiver No such table\n";}
		return;
	}
	$CountClients= $q->COUNT_ROWS("StatsApplianceReceiver");
	
	@file_put_contents($TimeFile, $CountClients);
	if($CountClients==0){
		@file_put_contents($TimeFile, 0);
		@chmod("$TimeFile",0755);
		if($GLOBALS["VERBOSE"]){echo "$CountClients Client(s)\n";}
		return;
	}
	@file_put_contents($TimeFile, $q->COUNT_ROWS("StatsApplianceReceiver"));
	@chmod("$TimeFile",0755);
}

function COUNT_OF_SURICATA(){
	$q=new postgres_sql();
	$sql="SELECT SUM(xcount) as xcount FROM suricata_events";
	$ligne=pg_fetch_assoc($q->QUERY_SQL($sql));
	@file_put_contents("{$GLOBALS["BASEDIR"]}/COUNT_OF_SURICATA", intval($ligne["xcount"]));
	
	
}



