#!/usr/bin/php -q
<?php
ini_set('memory_limit','1000M');
if (!defined('CURLOPTTYPE_OBJECTPOINT')){define('CURLOPTTYPE_OBJECTPOINT', 10000);}
if (!defined('CURLOPT_NOPROXY')){define('CURLOPT_NOPROXY', CURLOPTTYPE_OBJECTPOINT + 177);}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="InfluxDB Daemon";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["TRACE_INFLUX"]=true;
$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.influx.inc');
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.realtime-buildsql.inc");
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.access-log.tools.inc");

if($argv[1]=="--scandir"){scan_backup_dir();exit;}
if($argv[1]=="--upload"){upload_mysql($argv[2]);exit;}
if($argv[1]=="--delete"){delete_mysql($argv[2]);exit;}
if($argv[1]=="--run-mysql"){run_mysql();exit;}
if($argv[1]=="--test"){tests_import();exit;}
if($argv[1]=="--utctime"){utc_time();exit;}
if($argv[1]=="--deleteall"){delete_mysql_all();exit;}


$GLOBALS["PROGRESS"]=true;
Scan($argv[1]);




function delete_mysql($md5file){
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM import_srclogs WHERE md5file='$md5file'"));
	$path=$ligne["path"];
	@unlink($path);
	$q->QUERY_SQL("DELETE FROM import_srclogs WHERE  md5file='$md5file'");
}

function utc_time(){
	
	$time=time();
	$TimeToInflux=TimeToInflux($time);
	$InfluxToTime=InfluxToTime($TimeToInflux);
	
	echo "Today is: $time (".date("Y-m-d H:i:s").")\n";
	echo "Inserting time in influxdb in UTC : \"$TimeToInflux\"\n";
	echo "Query time in influxdb in UTC : \"$TimeToInflux\" == $InfluxToTime (".date("Y-m-d H:i:s",$InfluxToTime).")\n";
	
	
	
}
function delete_mysql_all(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("TRUNCATE TABLE import_srclogs");
}


function upload_mysql_zip($filename){
	
	$unix=new unix();
	$unzip=$unix->find_program("unzip");
	$filepath=dirname(__FILE__)."/ressources/conf/upload/$filename";
	
	if(!is_file($filepath)){
		build_progress_upload("$filepath, no such file",110);
		echo "$filepath, no such file\n";
		return false;
		
	}
	
	
	if(!is_file($unzip)){
		build_progress_upload("Unzip, no such binary",110);
		echo "Unzip, no such binary\n";
		@unlink($filepath);
		return false;
	}
	
	
	
	$time=time();
	$temp=$unix->TEMP_DIR()."/$time";
	
	@mkdir("$temp",666,true);
	
	build_progress_upload("Uncompress $filepath",15);
	
	$tmpunzip=$unix->TEMP_DIR()."/unzip-$time.txt";
	$cmd="$unzip -j -o $filepath -d $temp/ >$tmpunzip 2>&1";
	echo $cmd."\n";
	shell_exec($cmd);
	@unlink($filepath);
	$c=0;
	$filescan=$unix->DirFiles($temp);
	
	if(count($filescan)==0){
		echo @file_get_contents($tmpunzip);
		@unlink($tmpunzip);
		build_progress_upload("{uncompress} {failed}",110);
		return;
	}
	@unlink($tmpunzip);
	
	while (list ($num, $filename) = each ($filescan) ){
		$filepath="$temp/$filename";
		if(!is_file($filepath)){
			build_progress_upload("$filepath no such file",16);
			continue;
		}
		
		if(upload_mysql($filepath,true,false,true)){
			$c++;
		}
	}
	
	if($c==0){
		build_progress_upload("{failed}",110);
		return;
	}
	
	$rm=$unix->find_program("rm");
	build_progress_upload("remove directory",90);
	shell_exec("$rm -rf $temp");
	build_progress_upload("{done}",100);
	
}

function build_progress_scandir($text,$pourc){
	
	echo "{$pourc}% $text\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/squid.statistics.import.local.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	
}
function build_progress_upload($text,$pourc){

	echo "{$pourc}% $text\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/squid.statistics.import.upload.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}




function scan_backup_dir(){
	$unix=new unix();
	$sock=new sockets();
	$BackupMaxDaysDir=$sock->GET_INFO("BackupMaxDaysDir");
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
	echo "BackupMaxDaysDir: $BackupMaxDaysDir\n";
	
	build_progress_scandir("{scanning} $BackupMaxDaysDir",20);
	sleep(3);
	$c=0;
	$find=$unix->find_program("find");
	exec("$find $BackupMaxDaysDir 2>&1",$results);
	while (list ($num, $filename) = each ($results) ){
		if(!is_file($filename)){
			build_progress_scandir("{skip} $filename",30);
			continue;
		}
		$basename=basename($filename);
		if(preg_match("#^cache-#", $basename)){
			build_progress_scandir("{skip} $basename",30);
			continue;
		}
		if(!preg_match("#^access-tail#", $basename)){
			build_progress_scandir("{skip} $basename",30);
			continue;
		}
		build_progress_scandir("{importing} $basename",30);
		$c++;
		if(upload_mysql($filename,true)){
			$c++;
		}
	}
	
	$unix=new unix();
	$tempdir=$unix->TEMP_DIR();
	$destfile="$tempdir/current-access.log";
	if($unix->compress("/var/log/squid/access.log", $destfile)){
		build_progress_scandir("{importing} squid/access.log",90);
		upload_mysql($destfile,true,true);
		@unlink($destfile);
	}
	
	if($c==0){
		build_progress_scandir("{failed} 0 {files}",110);
		return;
	}
	build_progress_scandir("{done} $c {files}",100);
	
}



function upload_mysql($filename,$fulpath=false,$noremove=false,$no110=false){
	
	build_progress_upload("{checking} $filename",10);
	if(preg_match("#\.zip$#", $filename)){
		upload_mysql_zip($filename);
		return;
	}
	if(!$fulpath){$filepath=dirname(__FILE__)."/ressources/conf/upload/$filename";}else{
		$filepath=$filename;
		$filename=basename($filepath);
	}
	
	@mkdir("/home/logs_backup_queue",0755,true);
	if(!is_file($filepath)){
		if(!$no110){build_progress_upload("$filepath no such file",110);}
		echo "$filepath no such file\n";
		return false;
	}
	$md5file=md5_file($filepath);
	$DestFile="/home/logs_backup_queue/$filename";
	
	
	
	$q=new mysql_squid_builder();
	
	if(!$q->TABLE_EXISTS("import_srclogs")){
		$sql="CREATE TABLE IF NOT EXISTS `import_srclogs` (
		`md5file` varchar(90) NOT NULL,
		`path` varchar(255) NOT NULL,
		`size` INT UNSIGNED NOT NULL DEFAULT '0',
		`zDate` datetime NOT NULL,
		`percent` smallint(2) NOT NULL DEFAULT 0,
		`status` smallint(1) NOT NULL,
		`lastlog` varchar(255) NOT NULL,
		PRIMARY KEY (`md5file`),
		KEY `path` (`path`),
		KEY `size` (`size`),
		KEY `zDate` (`zDate`),
		KEY `percent` (`percent`),
		KEY `status` (`status`)
		) ENGINE=MYISAM;";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;@unlink($filepath);return;}
	
	}
	
	if(!$q->FIELD_EXISTS("import_srclogs", "pid")){
		$q->QUERY_SQL("ALTER TABLE `import_srclogs` ADD `pid` smallint(5)");
	}
	if(!$q->FIELD_EXISTS("import_srclogs", "first_time")){
		$q->QUERY_SQL("ALTER TABLE `import_srclogs` ADD `first_time` INT UNSIGNED");
	}
	if(!$q->FIELD_EXISTS("import_srclogs", "last_time")){
		$q->QUERY_SQL("ALTER TABLE `import_srclogs` ADD `last_time` INT UNSIGNED");
	}
	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM import_srclogs WHERE md5file='$md5file'"));
	if($ligne["size"]>0){
		if(!$no110){build_progress_upload("$filename: Already imported",100);}
		echo "Already imported\n";
		if(!$noremove){@unlink($filepath);}
		return true;
	}
	
	if(is_file($DestFile)){
		$md5file2=md5_file($DestFile);
		if($md5file==$DestFile){if(!$noremove){@unlink($filepath);}}else{@unlink($DestFile);}
	}
	
	if(!is_file($DestFile)){
		if(!@copy($filepath, $DestFile)){
			echo "Unable to move $filename\n";
			if(!$noremove){@unlink($filepath);}
			if(!$no110){build_progress_upload("$filename: Unable to move",110);}
			@unlink($DestFile);
			return;
		}
		if(!$noremove){@unlink($filepath);}
	}
	
	$size=@filesize($DestFile);
	if($size==0){
		echo "$DestFile == 0 Bytes!\n";
		if(!$no110){build_progress_upload("$filename: 0 Bytes!",110);}
		@unlink($DestFile);
		return;
	}
	$date=date("Y-m-d H:i:s");
	$sql="INSERT IGNORE INTO import_srclogs (md5file,`path`,`size`,`zDate`,`status`,`lastlog`) VALUES
	('$md5file','$DestFile','$size','$date',0,'Imported')";		
			
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo $q->mysql_error;
		if(!$no110){build_progress_upload("MySQL error",110);}
		@unlink($DestFile);
		return;
	}
	echo "$DestFile done..\n";
	if(!$no110){build_progress_upload("{done}",100);}
	return true;
	
	
}


function run_mysql(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,__FILE__)){return;}
	
	$q=new mysql_squid_builder();
	
	if(!$q->FIELD_EXISTS("import_srclogs", "pid")){
		$q->QUERY_SQL("ALTER TABLE `import_srclogs` ADD `pid` smallint(5)");
	}
	if(!$q->FIELD_EXISTS("import_srclogs", "first_time")){
		$q->QUERY_SQL("ALTER TABLE `import_srclogs` ADD `first_time` INT UNSIGNED");
	}
	if(!$q->FIELD_EXISTS("import_srclogs", "last_time")){
		$q->QUERY_SQL("ALTER TABLE `import_srclogs` ADD `last_time` INT UNSIGNED");
	}
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$sql="SELECT * FROM import_srclogs WHERE status=0 ORDER BY zDate";
	$results=$q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		$md5file=$ligne["md5file"];
		$path=$ligne["path"];
		mysql_progress($md5file,0,1,"Open..");
		$t1=time();
		if(Scan($path,$md5file)){
			$took=$unix->distanceOfTimeInWords($t1,time(),true);
			mysql_progress($md5file,100,2,"{done} {took} $took");
			@unlink($path);
		}
		
		shell_exec("$nohup $php ".__FILE__." --run-mysql >/dev/null 2>&1");
		die();
		
	}
	
	
}

function mysql_progress($md5file=null,$prc=0,$status=0,$text=null){
	if($GLOBALS["VERBOSE"]){echo "{$prc}% $text\n";}
	if($md5file==null){return;}
	$add=array();
	$q=new mysql_squid_builder();
	
	
	$add[]="`pid`='".getmypid()."'";
	
	if($prc>0){
		$add[]="`percent`='$prc'";
	}
	if($status>0){
		$add[]="`status`='$status'";
	}
	if($text<>null){
		$text=mysql_escape_string2($text);
		$add[]="`lastlog`='$text'";
	}
	
	if(count($add)==0){return;}
	
	$q->QUERY_SQL("UPDATE import_srclogs SET ".@implode(",", $add)." WHERE `md5file`='$md5file'");
	
	
}
function mysql_first_time($md5file=null,$time){
	if($md5file==null){return;}
	$add=array();
	$q=new mysql_squid_builder();
	
	$q->QUERY_SQL("UPDATE import_srclogs SET `first_time`='$time' WHERE `md5file`='$md5file'");
}
function mysql_last_time($md5file=null,$time){
	if($md5file==null){return;}
	$add=array();
	$q=new mysql_squid_builder();

	$q->QUERY_SQL("UPDATE import_srclogs SET `last_time`='$time' WHERE `md5file`='$md5file'");
}


function Scan($filepath,$md5file=null){
	$unix=new unix();
	if($filepath==null){
		echo "No path defined\n";
		return;
	}
	
	$pid=$unix->PIDOF_PATTERN(basename(__FILE__));
	$MyPid=getmypid();
	if($MyPid<>$pid){
		if($unix->process_exists($pid)){
			$timeFile=$unix->PROCESS_TIME_INT($pid);
			$pidCmdline=@file_get_contents("/proc/$pid/cmdline");
			if($timeFile<30){
				echo "Already PID $pid is running since {$timeFile}Mn\n";
				die();
			}
		}
	}
	
	$nextFile=null;
	if(!is_file($filepath)){
		if($md5file<>null){mysql_progress($md5file,100,3,"$filepath no such file");}
		echo "$filepath no such file";
	}
	
	@mkdir("/home/artica/import-temp",0755,true);
	
	$basename=basename($filepath);
	if(preg_match("#\.gz$#", $basename)){
		if($md5file<>null){mysql_progress($md5file,5,0,"Uncompress $filepath");}
		echo "Uncompress $basename";
		$nextFile=dirname($filepath)."/".str_replace(".gz", "", $basename);
		echo "Uncompress $basename to $nextFile\n";
		if(is_file($nextFile)){@unlink($nextFile);}
		if(!$unix->uncompress($filepath, $nextFile)){
			if($md5file<>null){mysql_progress($md5file,100,3,"Uncompress $basename failed");}
			echo "Uncompress $basename failed\n";
			return false;
		}
		$filepath=$nextFile;
	}
	
	
	if(!ExplodeFile($filepath,$md5file)){return false;}
	
	
	if($nextFile<>null){@unlink($nextFile);}
	return true;
	
}

function CachedSizeMem($time,$cached,$SIZE){
	if(intval(date("Y",$time))<2004){return;}
	
	$Hour=date("Y-m-d H:i:00",$time);
	
	$key=md5("$Hour$cached");
	
	if(!isset($GLOBALS["MEMORY_CACHE"][$key])){
		$GLOBALS["MEMORY_CACHE"][$key]["TIME"]=strtotime($Hour);
		$GLOBALS["MEMORY_CACHE"][$key]["SIZE"]=intval($SIZE);
		$GLOBALS["MEMORY_CACHE"][$key]["CACHED"]=$cached;
	}else{
		
		$GLOBALS["MEMORY_CACHE"][$key]["SIZE"]=$GLOBALS["MEMORY_CACHE"][$key]["SIZE"]+intval($SIZE);
	}
	
	if(count($GLOBALS["MEMORY_CACHE"])>1000){
		CachedSizeMem_dump();
	}
	
}

function CachedSizeMem_dump(){
	if(count($GLOBALS["MEMORY_CACHE"])==0){return;}

	
	if(!isset($GLOBALS["influx"])){$GLOBALS["influx"]=new influx();}
	if(!isset($GLOBALS["MYHOSTNAME"])){$unix=new unix();$GLOBALS["MYHOSTNAME"]=$unix->hostname_g();}
	
	
	while (list ($key, $MAIN) = each ($GLOBALS["MEMORY_CACHE"]) ){
	
		$time=$MAIN["TIME"];
		if(!isset($GLOBALS["TOUTC"][$time])){$GLOBALS["TOUTC"][$time]=QueryToUTC($time);}
		$SIZE=$MAIN["SIZE"];
		$cached=$MAIN["CACHED"];
		
		if($cached==0){
			$zArray["precision"]="s";
			$zArray["fields"]["time"]=$GLOBALS["TOUTC"][$time];
			$zArray["fields"]["SIZE"]=intval($SIZE);
			$zArray["tags"]["proxyname"]=$GLOBALS["MYHOSTNAME"];
			$GLOBALS["influx"]->writeToFile="/home/artica/import-temp/{$GLOBALS["SEQUENCE"]}.working.log";
			$GLOBALS["influx"]->insert("NO_CACHED", $zArray);
			
		}
		
		if($cached==1){
			$zArray["precision"]="s";
			$zArray["fields"]["time"]=$time;
			$zArray["fields"]["SIZE"]=intval($SIZE);
			$zArray["tags"]["proxyname"]=$GLOBALS["MYHOSTNAME"];
			$GLOBALS["influx"]->writeToFile="/home/artica/import-temp/{$GLOBALS["SEQUENCE"]}.working.log";
			$GLOBALS["influx"]->insert("CACHED", $zArray);
		}
		
		$zArray=array();
		$zArray["precision"]="s";
		$zArray["fields"]["time"]=$time;
		$zArray["fields"]["SIZE"]=intval($SIZE);
		$zArray["tags"]["proxyname"]=$GLOBALS["MYHOSTNAME"];
		$GLOBALS["influx"]->writeToFile="/home/artica/import-temp/{$GLOBALS["SEQUENCE"]}.working.log";
		$GLOBALS["influx"]->insert("MAIN_SIZE", $zArray);
	
	
	}
	
	$GLOBALS["MEMORY_CACHE"]=array();
	$GLOBALS["TOUTC"]=array();
}

function TimeToInflux($time){
	$time=QueryToUTC($time);
	
	$microtime=microtime();
	preg_match("#^[0-9]+\.([0-9]+)\s+#", $microtime,$re);
	$ms=intval($re[1]);
	return date("Y-m-d",$time)."T".date("H:i:s",$time).".{$ms}Z";
	
}





function CachedUserMem($time,$sitename,$SIZE,$mac,$uid,$ipaddr,$category,$familysite){
	
	if(intval(date("Y",$time))<2004){return;}
	
	if(is_numeric($familysite)){
		if($GLOBALS["VERBOSE"]){echo "familysite = $familysite ??? numeric ??? ".__LINE__."\n";}
	}
	
	if(intval($SIZE)==0){
		if($GLOBALS["VERBOSE"]){echo "$time,$sitename,$SIZE,$mac,$uid,$ipaddr,$category,$familysite size =0  !!".__LINE__."\n";}
		return;
	}
	
	$Hour=date("Y-m-d H:00:00",$time);
	
	$key=md5("$Hour$sitename$mac$uid$ipaddr$category$familysite");
	
	if(!isset($GLOBALS["MEMORY_LINE"][$key])){
		$GLOBALS["MEMORY_LINE"][$key]["TIME"]=strtotime($Hour);
		$GLOBALS["MEMORY_LINE"][$key]["CATEGORY"]=$category;
		$GLOBALS["MEMORY_LINE"][$key]["USERID"]=$uid;
		$GLOBALS["MEMORY_LINE"][$key]["IPADDR"]=$ipaddr;
		$GLOBALS["MEMORY_LINE"][$key]["SITE"]=$sitename;
		$GLOBALS["MEMORY_LINE"][$key]["FAMILYSITE"]=$familysite;
		$GLOBALS["MEMORY_LINE"][$key]["MAC"]=$mac;
		$GLOBALS["MEMORY_LINE"][$key]["SIZE"]=intval($SIZE);
		$GLOBALS["MEMORY_LINE"][$key]["RQS"]=1;
		$GLOBALS["MEMORY_LINE"][$key]["ZDATE"]=$time;
		$GLOBALS["MEMORY_LINE"][$key]["proxyname"]=$GLOBALS["MYHOSTNAME"];
	}else{
		$GLOBALS["MEMORY_LINE"][$key]["ZDATE"]=$time;
		$GLOBALS["MEMORY_LINE"][$key]["RQS"]++;
		$GLOBALS["MEMORY_LINE"][$key]["SIZE"]=$GLOBALS["MEMORY_LINE"][$key]["SIZE"]+intval($SIZE);
	}
	
	if(count($GLOBALS["MEMORY_LINE"])>1000){
		if($GLOBALS["VERBOSE"]){" MEMORY_LINE: ".count($GLOBALS["MEMORY_LINE"]);}
		CachedUserMem_dump();
	}
}
	
	
function CachedUserMem_dump(){	
	if(count($GLOBALS["MEMORY_LINE"])==0){return;}
	if(!isset($GLOBALS["influx"])){$GLOBALS["influx"]=new influx();}
	$LINES=array();
	while (list ($key, $MAIN) = each ($GLOBALS["MEMORY_LINE"]) ){
		$time=$MAIN["TIME"];
		if(!isset($GLOBALS["TOUTC"][$time])){$GLOBALS["TOUTC"][$time]=QueryToUTC($time);}
		$zArray["precision"]="s";
		
		if(!isset($MAIN["MAC"])){$MAIN["MAC"]="00:00:00:00:00:00";}
		if($MAIN["MAC"]==null){$MAIN["MAC"]="00:00:00:00:00:00";}
		if(!isset($MAIN["USERID"])){$MAIN["USERID"]="none";}
		if($MAIN["USERID"]==null){$MAIN["USERID"]="none";}
		
		$zArray["fields"]["time"]=$GLOBALS["TOUTC"][$time];
		$zArray["tags"]["CATEGORY"]=$MAIN["CATEGORY"];
		$zArray["tags"]["USERID"]=$MAIN["USERID"];;
		$zArray["tags"]["IPADDR"]=$MAIN["IPADDR"];
		$zArray["tags"]["MAC"]=$MAIN["MAC"];
		$zArray["fields"]["SIZE"]=intval($MAIN["SIZE"]);
		
		$zArray["tags"]["SITE"]=$MAIN["SITE"];
		$zArray["tags"]["FAMILYSITE"]=$MAIN["FAMILYSITE"];
		$zArray["fields"]["RQS"]=$MAIN["RQS"];
		$zArray["fields"]["ZDATE"]=$MAIN["ZDATE"];
		$zArray["tags"]["proxyname"]=$MAIN["proxyname"];
		$GLOBALS["influx"]->writeToFile="/home/artica/import-temp/{$GLOBALS["SEQUENCE"]}.working.log";
		$GLOBALS["influx"]->insert("access_log", $zArray);
	}
}



function tests_import(){
	
	$q=new influx();

	
	
	$array[]="access_log,CATEGORY=,USERID=,IPADDR=192.168.1.206,MAC=9c:02:98:8c:ee:b9,SITE=198.38.120.151,FAMILYSITE=198.38.120.151,proxyname=routeur.touzeau.biz SIZE=19724540,ZDATE=2015,RQS=186 ".time();
	
	
	$q->bulk_inject($array);
	
	
}




function ExplodeFile($filepath,$md5file=null){
	$unix=new unix();
	$LastScannLine=0;
	$GLOBALS["MYSQL_CATZ"]=new mysql_catz();
	$GLOBALS["SQUID_FAMILY_CLASS"]=new squid_familysite();
	if(!isset($GLOBALS["MYHOSTNAME"])){$unix=new unix();$GLOBALS["MYHOSTNAME"]=$unix->hostname_g();}
	$GLOBALS["SEQUENCE"]=md5_file($filepath);
	
	if(!is_file("$filepath.last")){
		if(is_file("/home/artica/import-temp/{$GLOBALS["SEQUENCE"]}.working.log")){
			$influx=new influx();
			if($influx->files_inject("/home/artica/import-temp/{$GLOBALS["SEQUENCE"]}.working.log")){
				@unlink("/home/artica/import-temp/{$GLOBALS["SEQUENCE"]}.working.log");
				return true;
			}
			
		}
	}
	
	
	
	
	$handle = @fopen($filepath, "r");
	if (!$handle) {
		echo "Fopen failed on $filepath\n";
		if($md5file<>null){mysql_progress($md5file,100,3,"Fopen {failed} on $filepath");}
		return false;
	}
	
	$countlines=0;
	if($md5file<>null){
		$countlines=$unix->COUNT_LINES_OF_FILE($filepath);
		if($md5file<>null){mysql_progress($md5file,10,0,"Parsing $countlines");}
	}
	
	if(is_file("$filepath.last")){
		$LastScannLine=intval(@file_get_contents("$filepath.last"));
	}
	
	$c=0;
	$d=0;
	$e=0;
	$prc=0;
	$prc_text=0;
	$mysql_first_time=0;
	
	if($LastScannLine>0){fseek($handle, $LastScannLine, SEEK_SET);}
	
	while (!feof($handle)){
		$c++;
		$d++;
		$e++;
		
		
		
		if($countlines>0){
			$prc=$c/$countlines;
			$prc=round($prc*100);
			
			
			if(!isset($GLOBALS["LAST_PRC"])){
				if($GLOBALS["PROGRESS"]){echo "{$prc}%\n";}
				$GLOBALS["LAST_PRC"]=$prc;
			}else{
				if($GLOBALS["LAST_PRC"]<>$prc){
					if($GLOBALS["PROGRESS"]){echo "{$prc}%\n";}
					$GLOBALS["LAST_PRC"]=$prc;
				}
			}
			
			
			if($prc>10){
				if($prc<99){
					if($prc>$prc_text){
						$array_load=sys_getloadavg();
						$internal_load=$array_load[0];
						$mem=round(((memory_get_usage()/1024)/1000),2);
						$prc_design=FormatNumber($c)."/".FormatNumber($countlines);
						if($md5file<>null){mysql_progress($md5file,$prc,1,"{parsing} $prc_design {load}:$internal_load {memory}:{$mem}MB");}
						$prc_text=$prc;
					}
				}
			}
		}
		
		if($d>50){
			$iSeek = ftell($handle);
			@file_put_contents("$filepath.last", $iSeek);
			if($GLOBALS["VERBOSE"]){
				$prc_design=FormatNumber($c)."/".FormatNumber($countlines);
				echo "{$prc}% $prc_design\n";
			}
			$d=0;
		}
		
		if($e>500){
			$mem=round(((memory_get_usage()/1024)/1000),2);
			$prc_design=FormatNumber($c)."/".FormatNumber($countlines);
			if($md5file<>null){mysql_progress($md5file,$prc,1,"{parsing} $prc_design {load}:$internal_load {memory}:{$mem}MB");}
			$e=0;
		}
		
		$buffer =trim(fgets($handle));
		if($buffer==null){continue;}
		$array=parseAccessLine($buffer);
		if(count($array)==0){continue;}
		
		if($mysql_first_time==0){
			if(date("Y",$array["TIME"])>2001){
				$mysql_first_time=$array["TIME"];
				mysql_first_time($md5file,$mysql_first_time);
			}
		}
		
		CachedSizeMem($array["TIME"],$array["CACHED"],$array["SIZE"]);
		if(intval($array["SIZE"])==0){
			if($GLOBALS["VERBOSE"]){
				echo "Size = 0 ". __LINE__."\n";
			}
		}
		CachedUserMem($array["TIME"],$array["SITENAME"],$array["SIZE"],null,$array["UID"],$array["IPADDR"],$array["CATEGORY"],$array["FAMILYSITE"]);
	}
	
	
	@unlink("$filepath.last");
	mysql_last_time($md5file,$array["TIME"]);
	CachedUserMem_dump();
	CachedSizeMem_dump();
	$influx=new influx();
	
	$size=filesize("/home/artica/import-temp/{$GLOBALS["SEQUENCE"]}.working.log");
	$size=$size/1024;
	$size=$size/1024;
	
	echo "Importing {$size}MB of data....\n";
	
	if(!$influx->files_inject("/home/artica/import-temp/{$GLOBALS["SEQUENCE"]}.working.log")){
		@unlink("/home/artica/import-temp/{$GLOBALS["SEQUENCE"]}.working.log");
		return false;
		
	}
	
	@unlink("/home/artica/import-temp/{$GLOBALS["SEQUENCE"]}.working.log");
	return true;
	
}


function SaveFile(){
	
	
	$pid=@getmypid();
	$date=@date("H:i:s");
	$logFile="/home/artica/influxdb/import_tmp/".time().".db";
	@mkdir(dirname($logFile),0755,true);
	
	

	$f = @fopen($logFile, 'a');
	while (list ($key, $line) = each ($GLOBALS["LINES"]) ){
		@fwrite($f, "$line\n");
	}
	
	@fclose($f);
	
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}

