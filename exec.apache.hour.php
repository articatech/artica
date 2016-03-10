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

if(preg_match("#--verbose#",implode(" ",$argv))){
		echo "VERBOSED....\n";
		$GLOBALS["VERBOSE"]=true;$GLOBALS["TRACE_INFLUX"]=true;
		$GLOBALS["OUTPUT"]=true;
		$GLOBALS["debug"]=true;
		ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
}

if($argv[1]=="--clean"){clean_tables();exit;}

scan();

function scan(){
	
	if(system_is_overloaded(basename(__FILE__))){
		apache_admin_mysql(0, "Overloaded system, retry next time....", null,__FILE__,__LINE__);
		return;
		
	}
	
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
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `dashboard_apache_sizes` ( `TIME` DATETIME,
			`zmd5` VARCHAR(90) NOT NULL PRIMARY KEY,
			`SITENAME` VARCHAR(128),
			`SIZE` BIGINT UNSIGNED, `RQS` BIGINT UNSIGNED,
			KEY `TIME` (`TIME`),
			KEY `SIZE` (`SIZE`),
			KEY `RQS` (`RQS`)
			) ENGINE=MYISAM;");
	
	if(!$q->ok){
		apache_admin_mysql(0, "Fatal MySQL error", $q->mysql_error,__FILE__,__LINE__);
		return;
	}
	
	
	if(!is_file("/home/apache/artica-stats/requests.log")){
		echo "/home/apache/artica-stats/requests.log no such file...\n";
		return;}
	
	
	@mkdir("/home/apache/artica-stats/works",0755,true);
	if(is_file("/home/apache/artica-stats/works/apache.log")){
		echo "Parse /home/apache/artica-stats/works/apache.log\n";
		Parse("/home/apache/artica-stats/works/apache.log");
		return;
		
	}
	
	if(!@copy("/home/apache/artica-stats/requests.log", "/home/apache/artica-stats/works/apache.log")){
		echo "Copy failed\n";
		return;
	}
	
	if(!is_file("/home/apache/artica-stats/works/apache.log")){
		echo "/home/apache/artica-stats/works/apache.log no such file...\n";
		return;
	}
	
	@unlink("/home/apache/artica-stats/requests.log");
	echo "Parse /home/apache/artica-stats/works/apache.log\n";
	Parse("/home/apache/artica-stats/works/apache.log");
	CLEAN_MYSQL();
	
}


function Parse($filename){
	$t1=time();
	$unix=new unix();
	$workfile=$filename;
	$stampfile="$filename.last";
	
	if(is_file($stampfile)){
		$LastScannLine=intval(@file_get_contents($stampfile));
	}
	
	$handle = @fopen($workfile, "r");
	if (!$handle) {events("Fopen failed on $workfile");return false;}
	
	if($LastScannLine>0){fseek($handle, $LastScannLine, SEEK_SET);}
	
	while (!feof($handle)){
		//1444514181;www.safe-demo.com;46.4.32.75;200;1;42354
		$buffer =trim(fgets($handle));
		if($buffer==null){continue;}
	
		$ARRAY=explode(";",$buffer);
		$TIME=$ARRAY[0];
		$SITENAME=$ARRAY[1];
		$IPADDR=$ARRAY[2];
		
		if($IPADDR=="127.0.0.1"){continue;}
		$HTTP_CODE=$ARRAY[3];
		$RQS=$ARRAY[4];
		$SIZE=$ARRAY[5];
		$TIME_HOUR=date("Y-m-d H:00:00",$TIME);
		$KEYMD5=md5("$TIME_HOUR$SITENAME");
		$KEYMD5FULL=md5("$TIME_HOUR$SITENAME$IPADDR$HTTP_CODE");
		
		if(!isset($GENERIC[$KEYMD5]["SIZE"])){
			$GENERIC[$KEYMD5]["DATE"]=$TIME_HOUR;
			$GENERIC[$KEYMD5]["INFLUX_TIME"]=TimeToInflux($TIME);
			$GENERIC[$KEYMD5]["SIZE"]=intval($SIZE);
			$GENERIC[$KEYMD5]["RQS"]=intval($RQS);
			$GENERIC[$KEYMD5]["SITENAME"]=$SITENAME;
		}else{
			$GENERIC[$KEYMD5]["SIZE"]=$GENERIC[$KEYMD5]["SIZE"]+$SIZE;
			$GENERIC[$KEYMD5]["RQS"]=$GENERIC[$KEYMD5]["RQS"]+$RQS;
			
		}
		
		if(!isset($FULL[$KEYMD5FULL]["SIZE"])){
			$FULL[$KEYMD5FULL]["DATE"]=$TIME_HOUR;
			$FULL[$KEYMD5FULL]["INFLUX_TIME"]=QueryToUTC(strtotime($TIME_HOUR),true);
			$FULL[$KEYMD5FULL]["SIZE"]=intval($SIZE);
			$FULL[$KEYMD5FULL]["RQS"]=intval($RQS);
			$FULL[$KEYMD5FULL]["SITENAME"]=$SITENAME;
			$FULL[$KEYMD5FULL]["IPADDR"]=$IPADDR;
			$FULL[$KEYMD5FULL]["HTTP_CODE"]=$HTTP_CODE;
		}else{
			$FULL[$KEYMD5FULL]["SIZE"]=$GENERIC[$KEYMD5]["SIZE"]+$SIZE;
			$FULL[$KEYMD5FULL]["RQS"]=$GENERIC[$KEYMD5]["RQS"]+$RQS;
				
		}

		
		if(count($GENERIC)>500){if(!DUMP_GENERIC($GENERIC)){return;}$GENERIC=array();}
		if(count($FULL)>500){if(!DUMP_FULL($FULL)){return;}$FULL=array();}
		
	}
	
	if(count($GENERIC)>0){if(!DUMP_GENERIC($GENERIC)){return;}}	
	if(count($FULL)>0){if(!DUMP_FULL($FULL)){return;}$FULL=array();}
	
	$took=$unix->distanceOfTimeInWords($t1,time(),true);
	apache_admin_mysql(2, "Success injecting data from ". basename($workfile)." took:$took", null,__FILE__,__LINE__);
	@unlink($workfile);
	
}

function DUMP_GENERIC($MAIN){
	
	$q=new mysql_squid_builder();
	
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `dashboard_apache_sizes` ( `TIME` DATETIME,
			`zmd5` VARCHAR(90) NOT NULL PRIMARY KEY,
			`SITENAME` VARCHAR(128),
			`SIZE` BIGINT UNSIGNED, `RQS` BIGINT UNSIGNED,
			KEY `TIME` (`TIME`),
			KEY `SIZE` (`SIZE`),
			KEY `RQS` (`RQS`)
			) ENGINE=MYISAM;");
	
	
	while (list ($MD5, $ARRAY) = each ($MAIN) ){
		$SIZE=$ARRAY["SIZE"];
		$RQS=$ARRAY["RQS"];
		$SITENAME=$ARRAY["SITENAME"];
		$DATE=$ARRAY["DATE"];
		$INFLUX_TIME=$ARRAY["INFLUX_TIME"];
		
		$f[]="('$MD5','$DATE','$SITENAME','$SIZE','$RQS')";
	}
	
	$sql="INSERT IGNORE INTO `dashboard_apache_sizes` (`zmd5`,`TIME`,`SITENAME`,`SIZE`,`RQS`) VALUES ".@implode(",", $f);
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		apache_admin_mysql(0, "Fatal MySQL error", $q->mysql_error,__FILE__,__LINE__);
		return false;
	}
	
	return true;
	
}

function DUMP_FULL($MAIN){

	$backupdir="/home/apache/artica-stats/works/backup";
	$failedPath="/home/apache/artica-stats/works/failed";
	@mkdir($backupdir,0755,true);
	@mkdir($failedPath,0755,true);
	$PROXYNAME=$GLOBALS["MYHOSTNAME_PROXY"];
	$AS_POSTGRES=false;
	$suffix="influx";
	if(is_file("/usr/local/ArticaStats/bin/postgres")){ $AS_POSTGRES=true; $suffix="postgres";}
	$prefix="(zDate,IPADDR,SITENAME,HTTP_CODE,RQS,SIZE,PROXYNAME)";
	
	$q=new influx();
	$FINAL=array();
	while (list ($MD5, $ARRAY) = each ($MAIN) ){
		$SIZE=$ARRAY["SIZE"];
		$RQS=$ARRAY["RQS"];
		$SITENAME=$ARRAY["SITENAME"];
		$DATE=$ARRAY["DATE"];
		$INFLUX_TIME=$ARRAY["INFLUX_TIME"];
		$IPADDR=$ARRAY["IPADDR"];
		$HTTP_CODE=$ARRAY["HTTP_CODE"];
		$zDate=$ARRAY["DATE"];
		
		
		if($AS_POSTGRES){
			$FINAL[]="('$zDate','$IPADDR','$SITENAME','$HTTP_CODE','$RQS','$SIZE','$PROXYNAME')";
			continue;
		}
		
		
		$zArray["precision"]="s";
		$zArray["time"]=$INFLUX_TIME;
		$zArray["fields"]["RQS"]=$RQS;
		$zArray["fields"]["SIZE"]=$SIZE;
		$zArray["fields"]["HTTP_CODE"]=$HTTP_CODE;
		$zArray["tags"]["SITENAME"]=$SITENAME;
		$zArray["tags"]["IPADDR"]=$IPADDR;
		$line=$q->prepare("apache_size", $zArray);
		$FINAL[]=$line;

	}
	
	
	if(count($FINAL)>0){
		$backupfile="$backupdir/apache.".time().".$suffix.log";
		$failedPath="$failedPath/apache.".time().".$suffix.log";
		
		if($AS_POSTGRES){
			$sql="INSERT INTO apache_size $prefix VALUES ".@implode(",", $FINAL);
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
				apache_admin_mysql(0,"INJECTION Failed ($q->curl_error)",": backup to $failedPath",__FILE__,__LINE__);
				@file_put_contents($failedPath, @implode("\n", $FINAL));
				sleep(1);
				return true;
			}
		}		
	
		events("INJECTION Success: backup to $backupfile");
		@file_put_contents($backupfile, @implode("\n", $FINAL));
		$FINAL=array();
	
	}
	sleep(1);
	return true;	
	
}
function  CLEAN_MYSQL(){
	$sock=new sockets();
	$MySQLStatisticsRetentionDays=intval($sock->GET_INFO("MySQLStatisticsRetentionDays"));
	if($MySQLStatisticsRetentionDays==0){$MySQLStatisticsRetentionDays=5;}

	$SUB="DATE_SUB(NOW(),INTERVAL $MySQLStatisticsRetentionDays DAY)";

	$q=new mysql_squid_builder();

	$TABLES[]="dashboard_apache_sizes";


	while (list ($dev, $TABLE) = each ($TABLES) ){
		if(!$q->TABLE_EXISTS($TABLE)){continue;}
		$q->QUERY_SQL("DELETE FROM `$TABLE` WHERE `TIME` < $SUB");

	}
	
	CLEAN_TABLES_CACHEHOURS();

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

	$suffix=date("Y-m-d H:i:s")." [".basename(__FILE__)."/$function/$line]:";
	if($GLOBALS["VERBOSE"]){echo "$suffix $text\n";}

	if (is_file($logFile)) {
		$size=filesize($logFile);
		if($size>1000000){@unlink($logFile);}
	}
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$suffix $text\n");
	@fclose($f);
}

function clean_tables(){
	CLEAN_TABLES_CACHEHOURS();
	
}

function CLEAN_TABLES_CACHEHOURS(){
	$q=new mysql_squid_builder();
	if(isset($GLOBALS["LIST_TABLES_CACHEHOURS"])){return $GLOBALS["LIST_TABLES_CACHEHOURS"];}
	$array=array();
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' 
			AND table_name LIKE 'cachehour_%'";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){return array();}
	

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(preg_match("#cachehour_[0-9]+#", $ligne["c"])){
			$TableName=$ligne["c"];
			$q->QUERY_SQL("DROP TABLE `$TableName`");
		}
	}
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs'
			AND table_name LIKE 'sizehour_%'";
	$results=$q->QUERY_SQL($sql);
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(preg_match("#sizehour_[0-9]+#", $ligne["c"])){
			$TableName=$ligne["c"];
			$q->QUERY_SQL("DROP TABLE `$TableName`");
		}
	}
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs'
			AND table_name LIKE 'dansguardian_events_%'";
	$results=$q->QUERY_SQL($sql);
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(preg_match("#dansguardian_events_[0-9]+#", $ligne["c"])){
			$TableName=$ligne["c"];
			$q->QUERY_SQL("DROP TABLE `$TableName`");
		}
	}	
	
	
	
	
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs'
			AND table_name LIKE '%_week'";
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(preg_match("#^[0-9]+_week$#", $ligne["c"])){
			$TableName=$ligne["c"];
			$q->QUERY_SQL("DROP TABLE `$TableName`");
		}
	}
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs'
			AND table_name LIKE '%_catfam'";
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(preg_match("#^[0-9]+_catfam$#", $ligne["c"])){
			$TableName=$ligne["c"];
			$q->QUERY_SQL("DROP TABLE `$TableName`");
		}
	}	
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs'
			AND table_name LIKE '%_day'";
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(preg_match("#^[0-9]+_day$#", $ligne["c"])){
			$TableName=$ligne["c"];
			$q->QUERY_SQL("DROP TABLE `$TableName`");
		}
	}	
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs'
			AND table_name LIKE '%_blocked_days'";
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(preg_match("#^[0-9]+_blocked_days$#", $ligne["c"])){
			$TableName=$ligne["c"];
			$q->QUERY_SQL("DROP TABLE `$TableName`");
		}
	}	
	
	
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