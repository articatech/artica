<?php
$GLOBALS["FULL"]=false;
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.squidguard.inc');
include_once(dirname(__FILE__).'/ressources/class.artica-meta.inc');
$GLOBALS["working_directory"]="/opt/artica/proxy";
$GLOBALS["WORKDIR_LOCAL"]="/var/lib/ufdbartica";
$GLOBALS["MAILLOG"]=array();
$GLOBALS["CHECKTIME"]=false;
$GLOBALS["NOTIME"]=false;
$GLOBALS["BYCRON"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["BYCRON"]=false;
$GLOBALS["NOCHECKTIME"]=false;
$GLOBALS["NOLOGS"]=false;
$GLOBALS["NOISO"]=false;
$GLOBALS["NODELETE"]=false;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["MYPID"]=getmypid();
$GLOBALS["CMDLINE"]=@implode(" ", $argv);
if(preg_match("#--notime#",implode(" ",$argv))){$GLOBALS["NOTIME"]=true;}
if(preg_match("#--nodelete#",implode(" ",$argv))){$GLOBALS["NODELETE"]=true;}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--checktime#",implode(" ",$argv))){$GLOBALS["CHECKTIME"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--nologs#",implode(" ",$argv))){$GLOBALS["NOLOGS"]=true;}
if(preg_match("#--noiso#",implode(" ",$argv))){$GLOBALS["NOISO"]=true;}
if(preg_match("#--bycron#",implode(" ",$argv))){$GLOBALS["BYCRON"]=true;$GLOBALS["CHECKTIME"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}


$unix=new unix();
$mypid=getmypid();
$me_basename=basename(__FILE__);
$pgrep=$unix->find_program("pgrep");
exec("$pgrep -l -f \"$me_basename\" 2>&1",$results);
while (list ($key, $value) = each ($results) ){
	$value=trim($value);
	if($value==null){continue;}
	if(preg_match("#pgrep#", $value)){continue;}
	if(!preg_match("#^([0-9]+)\s+#", $value,$re)){continue;}
	$pid=$re[1];
	$cmdline=$unix->PID_CMDLINE($pid);
	$unix->events("[$mypid] Found PID $pid","/var/log/artica-ufdb.log",false,"main()",__LINE__,$me_basename);
	$unix->events("[$mypid] $pid cmdline: [$cmdline]","/var/log/artica-ufdb.log",false,"main()",__LINE__,$me_basename);
	if(preg_match("#\/sh\s+-c#",$cmdline)){continue;}
	echo "[$mypid] Found PID $pid\n";
	if($mypid==$pid){continue;}
	updatev2_progress(110,"Already running PID $pid L.".__LINE__);
	die();

}
$unix->events("[$mypid] OK, pass process barrier...","/var/log/artica-ufdb.log",false,"main()",__LINE__,$me_basename);



if($argv[1]=="--tests-pub"){tests_pub($argv[2]);exit;}
if($argv[1]=="--meta"){die();exit;}


if($argv[1]=="--phistank"){ufdb_phistank();exit;}
if($argv[1]=="--phistank2"){ufdb_phistank_createdb();exit;}

if($argv[1]=="--ufdb-check"){updatev2_checkversions();die();}
if($argv[1]=="--ufdb"){ufdbtables();die();}
if($argv[1]=="--ufdbsum"){calculate_categorized_websites();die();}
if($argv[1]=="--ufdbmaster"){
	$GLOBALS["FORCE"]=true;
	updatev2();
	if($GLOBALS["VERBOSE"]){echo " **************** META_MASTER_UFDBTABLES ***************\n";}
	META_MASTER_UFDBTABLES();
	die();
}



WriteMyLogs("Executed: {$GLOBALS["CMDLINE"]} task:{$GLOBALS["SCHEDULE_ID"]}",__FUNCTION__,__FILE__,__LINE__);
$sock=new sockets();
$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
$DisableCategoriesDatabasesUpdates=intval($sock->GET_INFO("DisableCategoriesDatabasesUpdates"));

if($DisableCategoriesDatabasesUpdates==1){
	ufdbevents("DisableCategoriesDatabasesUpdates IS ACTIVE ,ABORTING TASK",__FUNCTION__,__FILE__,__LINE__);
	die();	
	
}
	
if($EnableRemoteStatisticsAppliance==1){
	ufdbevents("EnableRemoteStatisticsAppliance IS ACTIVE ,ABORTING TASK",__FUNCTION__,__FILE__,__LINE__);
	WriteMyLogs("EnableRemoteStatisticsAppliance ACTIVE ,ABORTING TASK",__FUNCTION__,__FILE__,__LINE__);
	die();
}

if(is_file("/etc/artica-postfix/PROXYTINY_APPLIANCE")){
	ufdbevents("PROXYTINY_APPLIANCE IS ACTIVE ,ABORTING TASK",__FUNCTION__,__FILE__,__LINE__);
	die();
}





if($GLOBALS["FORCE"]){$GLOBALS["BYCRON"]=true;}

if($argv[1]=="--export"){export_table($argv[2]);die();}
if($argv[1]=="--export-all"){export_all_tables();die();}
if($argv[1]=="--merge-table"){merge_table($argv[2],$argv[3]);die();}
if(!ifMustBeExecuted2()){die("Not a squid service....") ;}
if($argv[1]=="--update"){updatev2();die();}
if($argv[1]=="--downloads"){die();}
if($argv[1]=="--inject"){die();}
if($argv[1]=="--reprocess-database"){updatev2($argv[2]);die();}
if($argv[1]=="--fullupdate"){updatev2();die();}
if($argv[1]=="--schedule-maintenance"){schedulemaintenance();die();}
if($argv[1]=="--categorize-delete"){categorize_delete();die();}
if($argv[1]=="--v2"){updatev2();die();}


if($argv[1]=="--cicap"){C_ICAP_TABLES();die();}
if($argv[1]=="--ufdb-first"){ufdbFirst();die();}

if($argv[1]=="--get-version"){updatev2_checkversions();die();}
if($argv[1]=="--adblock"){updatev2_adblock();die();}
if($argv[1]=="--cicap-dbs"){die();}
if($argv[1]=="--ufdbmeta"){ufdbtables_artica_meta();die();}
if($argv[1]=="--get-tlseversion"){updatev2_checktlse_version();die();}
if($argv[1]=="--tlse"){tlsetables();die();}
if($argv[1]=="--tlse-convert"){TLSE_CONVERTION_DISK();die();}


updatev2();


function ufdbFirst(){
	if(!is_file("/etc/artica-postfix/ufdbfirst")){
		@file_put_contents("/etc/artica-postfix/ufdbfirst", time());
		ufdbtables();
		
		C_ICAP_TABLES(true);
	}
}


function C_ICAP_TABLES($nopid=false){
	$unix=new unix();
	$size=$unix->DIRSIZE_BYTES("/home/artica/categories_databases");
	if($size>2000){
		$rm=$unix->find_program("rm");
		shell_exec("$rm -rf  /home/artica/categories_databases/*");
	}
	
	return;

}

function compile_cicap_download_v2($tablename){
	$URIBASE=$GLOBALS["MIRROR"];
	$tmpdir=$GLOBALS["CICAP_TMPDIR"];
	$curl=new ccurl("$URIBASE{$GLOBALS["CICAP_SUFFIX_URI"]}/$tablename.db.gz");
	if(is_file("$tmpdir/$tablename.db.gz")){@unlink("$tmpdir/$tablename.db.gz");}
	if($curl->GetFile("$tmpdir/$tablename.db.gz")){return true;}
	if($GLOBALS["CICAP_SUFFIX_URI"]<>null){return false;}
	$GLOBALS["CICAP_SUFFIX_URI"]="/ufdb";
	if($GLOBALS["VERBOSE"]){echo "Trying with suffix /ufdb...\n";}
	return compile_cicap_download_v2($tablename);
	
	
}


function ufdb_phistank(){
	$unix=new unix();
	$sock=new sockets();
	$EnableSquidPhishTank=intval($sock->GET_INFO("EnableSquidPhishTank"));
	$PhishTankApiKey=$sock->GET_INFO("PhishTankApiKey");
	if($EnableSquidPhishTank==0){
		updatev2_progress(80,"Phishtank {disabled}...");
		return;
	}
	if($PhishTankApiKey==null){
		updatev2_progress(80,"Phishtank {disabled}...");
		echo "Warning, API Key is not set, aborting...\n";
		return;
	}
	
	$path="/home/artica/squid/phishtank/online-valid.php_serialized";
	$FinalPath="/home/artica/squid/phishtank";
	$uri="http://data.phishtank.com/data/$PhishTankApiKey/online-valid.php_serialized";
	$curl=new ccurl($uri);
	@mkdir(dirname($path),0755,true);
	
	
	
	$heads=$curl->getHeaders();
	if(!isset($heads["http_code"])){
		updatev2_progress(80,"Phishtank {failed}...");
		echo "phishtank: Unable to get header information\n";
		squid_admin_mysql(1, "phishtank: Unable to get header information", null,__FILE__,__LINE__);
		return;
	}
	
	$http_code=intval($heads["http_code"]);
	if($http_code<>200){
		updatev2_progress(80,"Phishtank {failed}...");
		echo "Unable to get header information error code $http_code\n";
		squid_admin_mysql(1, "phishtank: Unable to get header information error code $http_code", null,__FILE__,__LINE__);
		return;
	}
	
	
	$Time=$heads["Last-Modified"];
	$MyDate=$sock->GET_INFO("PhishTankLastDate");
	
	echo "Last date: $Time\n";
	if(!is_file($path)){$MyDate=null;}
	if($Time==$MyDate){
		updatev2_progress(80,"Phishtank {no_update}...");
		echo "No new update...\n";
		return;
	}
	
	$tmpfile=$unix->FILE_TEMP();
	
	if(!$curl->GetFile($tmpfile)){
		updatev2_progress(80,"Phishtank {failed}...");
		echo "Unable to download online-valid.php_serialized\n";
		squid_admin_mysql(1, "phishtank: Unable to download online-valid.php_serialized, $curl->error",null,__FILE__,__LINE__);
		@unlink($tmpfile);
		return;
	}
	
	@unlink($path);
	if(!@copy($tmpfile, $path)){
		updatev2_progress(80,"Phishtank {failed}...");
		echo "Unable to copy online-valid.php_serialized\n";
		squid_admin_mysql(1, "phishtank: Unable to copy online-valid.php_serialized",null,__FILE__,__LINE__);
		@unlink($tmpfile);
		return;
	}
	
	@unlink($tmpfile);
	$size=@filesize($path);
	updatev2_progress(80,"Phishtank {compiling}...");
	squid_admin_mysql(2, "phishtank: Success to download new phishtank database (".FormatBytes($size/1024).")",null,__FILE__,__LINE__);
	$sock->SET_INFO("PhishTankLastDate", $Time);
	ufdb_phistank_createdb();
}


function ufdb_phistank_createdb(){
	ini_set('memory_limit','1000M');
	$sock=new sockets();
	$database="/etc/squid3/phistank.db";
	$path="/home/artica/squid/phishtank/online-valid.php_serialized";
	$q=new mysql_squid_builder();
	if(!is_file($path)){
		echo "$path no such file\n";
		return;}
	$data=unserialize(@file_get_contents($path));
	if(count($data)<10){
		updatev2_progress(90,"Phishtank {failed}...");
		echo "Corrupted source file\n";
		squid_admin_mysql(1, "phishtank: Corrupted source file",null,__FILE__,__LINE__);
		@unlink($path);
		return;
	}
	if(is_file($database)){@unlink($database);}
	if(!berekley_db_create($database)){
		updatev2_progress(90,"Phishtank {failed}...");
		echo "phishtank: Failed to create DB\n";
		squid_admin_mysql(1, "phishtank: Failed to create DB",null,__FILE__,__LINE__);
		return false;
	}
	
	$db_con = @dba_open($database, "w","db4");
	if(!$db_con){
		updatev2_progress(90,"Phishtank {failed}...");
		echo "phishtank: Failed to open DB\n";
		squid_admin_mysql(1, "phishtank: Failed to open DB",null,__FILE__,__LINE__);
		@dba_close($db_con);
		return;
	}
	
	$c=0;
	updatev2_progress(90,"Phishtank {compiling}...");
	while (list ($key, $value) = each ($data) ){
		$c++;
		$url=$value["url"];
		$array=parse_url($url);
		if(isset($array["path"])){
			if($array["path"]=="/"){
				if(!isset($array["query"])){
				echo $array["host"]."\n";
				$md5=md5($array["host"]);
				@dba_replace($md5,1,$db_con);
				continue;
				}
			}
		}
		$md5=md5($url);
		@dba_replace($md5,1,$db_con);
		continue;
		
	}
	updatev2_progress(90,"Phishtank {done} $c urls...");
	$data=array();
	@dba_close($db_con);
	echo "$c urls\n";
	$sock->SET_INFO("PhishTankLastCount", $c);
	
	
	
	
}

function berekley_db_create($db_path){
	if(is_file($db_path)){return true;}
	$db_desttmp = @dba_open($db_path, "c","db4");
	@dba_close($db_desttmp);
	if(!is_file($db_path)){return false;}
	return true;

}


function compile_cicap_download($tablename){
	return true;
}

function ufdbtables_artica_meta(){
	$unix=new unix();
	$sock=new sockets();
	$WORKDIR="/home/artica-meta-client/work/webfiltering";
	$tmpdir=$unix->TEMP_DIR();
	$ArticaMetaHost=$sock->GET_INFO("ArticaMetaHost");
	$ArticaMetaPort=intval($sock->GET_INFO("ArticaMetaPort"));
	if($ArticaMetaPort==0){$ArticaMetaPort=9000;}
	echo "ArticaMetaHost: $ArticaMetaHost:$ArticaMetaPort\n";
	
	
	$URI_PREFIX="https://$ArticaMetaHost:$ArticaMetaPort/meta-updates";
	$rm=$unix->find_program("rm");
	$tar=$unix->find_program("tar");
	$curl=new ccurl("$URI_PREFIX/webfiltering2/metaindex.txt");
	if(!$curl->GetFile("$tmpdir/metaindex.txt")){
		echo "$URI_PREFIX/webfiltering2/metaindex.txt\n";
		echo "$curl->error ($curl->error_num)\n";
		squid_admin_mysql(1, "Failed to download meta index file for Webfiltering databases", 
		@implode("\n", $curl->errors),__FILE__,__LINE__);
		@unlink("$tmpdir/metaindex.txt");
		updatev2_progress(110,"Meta console failed to download index file");
		return false;
	}
	@mkdir($WORKDIR,0755,true);
	
	
	$MAIN=unserialize(@file_get_contents("$tmpdir/metaindex.txt"));
	$TIME=$MAIN["TIME"];
	echo "Time: $TIME";
	$MyTime=intval($sock->GET_INFO("ArticaMetaWebFilteringTime"));
	
	if($MyTime>=$TIME){
		echo "No update available...\n";
		updatev2_progress(100,"up-to-date....");
		return;
	}
	
	$FILES=$MAIN["FILES"];
	
	$Max=count($FILES);
	$c=0;
	while (list ($uripath, $md5) = each ($FILES) ){
		$filename=basename($uripath);
		$localfilepath="$WORKDIR/$filename";
		$c++;
		$prc=$c/$Max;
		$prc=round($prc*100);
		
		if($prc<10){$prc=10;}
		if($prc>90){$prc=90;}
		updatev2_progress($prc,"$uripath");
		if(is_file($localfilepath)){
			$localmd5=md5_file($localfilepath);
			if($localmd5==$md5){
				$IMPLODE[]=$localfilepath;
				continue;
			}
			@unlink($localfilepath);
		}
		
		$curl=new ccurl("$URI_PREFIX/$uripath");
		if(!$curl->GetFile($localfilepath)){
			updatev2_progress(110,"$uripath {failed} $curl->error");
			@unlink($localfilepath);
			return;
		}
		$localmd5=md5_file($localfilepath);
		if($localmd5==$md5){
			$IMPLODE[]=$localfilepath;
			continue;
		}
		@unlink($localfilepath);
		updatev2_progress(110,"$uripath {corrupted}");
		
	}
	
	
	$cat=$unix->find_program("cat");
	
	if(count($IMPLODE)>0){
		$cmd="$cat $WORKDIR/*.tgz.* >$tmpdir/webfiltering.tgz";
		echo $cmd."\n";
		system($cmd);
		$md5big=md5_file("$tmpdir/webfiltering.tgz");
		if($md5big<>$MAIN["MD5"]){
			updatev2_progress(110,"webfiltering.tgz {corrupted}");
			shell_exec("$rm $WORKDIR/*");
			@unlink("$tmpdir/webfiltering.tgz");
			return;
		}
		shell_exec("$rm $WORKDIR/*");
		updatev2_progress(95,"Extracting databases");
		system("$tar -xvf $tmpdir/webfiltering.tgz -C /");
		updatev2_progress(95,"Stamp file to $TIME");
		$sock->SET_INFO("ArticaMetaWebFilteringTime",$TIME);
		squid_admin_mysql(2, "Reloading Web filtering services after updates via Meta", null,__FILE__,__LINE__);
		updatev2_progress(95,"Reloading Web filtering service");
		shell_exec("/etc/init.d/ufdb reload");
		updatev2_progress(95,"Reloading Categories database service");
		shell_exec("/etc/init.d/ufdbcat reload");
		
	}
	
	updatev2_progress(100,"{done}");
	
	
}


function META_MASTER_UFDBTABLES_ufdbartica_txt(){
	$sock=new sockets();
	$unix=new unix();
	$EnableArticaMetaServer=intval($sock->GET_INFO("EnableArticaMetaServer"));
	if($EnableArticaMetaServer==0){return;}
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	$destfile="$ArticaMetaStorage/webfiltering/ufdbartica.tgz";
	$destDir="$ArticaMetaStorage/webfiltering/ufdbartica";
	if(!is_file($destfile)){
		echo "$destfile no such file\n";
		return;
	}
	
	
	
	
	$filename="$ArticaMetaStorage/webfiltering/ufdbartica.txt";
	if(is_file($filename)){
		if($GLOBALS["VERBOSE"]){echo "$filename OK\n";}
		return;
	}	
	$filemtime=filemtime($destfile);
	if($GLOBALS["VERBOSE"]){echo "$destfile = $filemtime\n";}
	file_put_contents("$ArticaMetaStorage/webfiltering/ufdbartica.txt",$filemtime);
	if(is_file("/etc/artica-postfix/artica-webfilter-db-index.txt")){
		@unlink("$ArticaMetaStorage/webfiltering/index.txt");
		@copy("/etc/artica-postfix/artica-webfilter-db-index.txt","$ArticaMetaStorage/webfiltering/index.txt");
	}
}

function META_MASTER_UFDBTABLES($force=false){
	$sock=new sockets();
	$unix=new unix();
	$sourcefile="/usr/share/artica-postfix/ressources/logs/web/cache/CATZ_ARRAY";
	$EnableArticaMetaServer=intval($sock->GET_INFO("EnableArticaMetaServer"));
	if($EnableArticaMetaServer==0){return;}
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	@mkdir("$ArticaMetaStorage/nightlys",0755,true);
	@mkdir("$ArticaMetaStorage/releases",0755,true);
	@mkdir("$ArticaMetaStorage/webfiltering",0755,true);
	$srcdir=$GLOBALS["WORKDIR_LOCAL"];
	$destfile="$ArticaMetaStorage/webfiltering/ufdbartica.tgz";
	$destdir="$ArticaMetaStorage/webfiltering/ufdbartica";
	META_MASTER_UFDBTABLES_ufdbartica_txt();
	$rm=$unix->find_program("rm");
	
	$CATZ_ARRAY=unserialize(base64_decode(@file_get_contents($sourcefile)));
	$DATABASES_VERSION=$CATZ_ARRAY["TIME"];
	
	
	$STATUS=unserialize(@file_get_contents("/etc/artica-postfix/ARTICAUFDB_LAST_DOWNLOAD"));
	$LAST_DOWNLOAD=$STATUS["LAST_DOWNLOAD"]["TIME"];
	if($GLOBALS["VERBOSE"]){echo "LAST_DOWNLOAD = $LAST_DOWNLOAD\n";}
	
	$STATUS_STORAGE=unserialize(@file_get_contents("$ArticaMetaStorage/webfiltering/ARTICAUFDB_LAST_DOWNLOAD"));
	$LAST_DOWNLOAD_STORAGE=$STATUS_STORAGE["LAST_DOWNLOAD"]["TIME"];
	if($GLOBALS["VERBOSE"]){echo "LAST_DOWNLOAD_STORAGE = $LAST_DOWNLOAD_STORAGE\n";}
	
	if(is_file("$ArticaMetaStorage/webfiltering/ufdbartica.txt")){
		if($LAST_DOWNLOAD_STORAGE==$LAST_DOWNLOAD){
			if(is_file($destfile)){
				$timeFile="/etc/artica-postfix/pids/exec.artica-meta-server.php.checkufdb.time";
				$time=$unix->file_time_min($timeFile);
				if($GLOBALS["VERBOSE"]){echo "META_MASTER_UFDBTABLES: $timeFile = $time\n";}
				if($time<1440){return;}
			}
		}
	}
	$tar=$unix->find_program("tar");
	$split=$unix->find_program("split");
	if($GLOBALS["VERBOSE"]){echo "REMOVE $destfile\n";}
	@unlink($destfile);
	if($GLOBALS["VERBOSE"]){echo "CD $srcdir\n";}
	chdir($srcdir);
	if($GLOBALS["VERBOSE"]){echo "$tar czf $destfile *\n";}
	shell_exec("$tar czf $destfile *");
	if(is_dir($destdir)){shell_exec("$rm -rf $destdir"); }
	
	@mkdir("$destdir",0755,true);
	chdir("$destdir");
	system("cd $destdir");
	if($GLOBALS["VERBOSE"]){echo "META_MASTER_UFDBTABLES: $destfile -> $destdir/ufdbartica.tgz\n";}
	@copy($destfile, "$destdir/ufdbartica.tgz");
	echo "Split...ufdbartica.tgz\n";
	shell_exec("$split -a 3 -b 1m -d ufdbartica.tgz ufdbartica.tgz.");
	@unlink("$destdir/ufdbartica.tgz");
	
	$files=$unix->DirFiles("$destdir");
	while (list ($num, $ligne) = each ($files) ){
		$Splited_md5=md5_file("$destdir/$num");
		$ARRAY["$num"]=$Splited_md5;
	}
	if($GLOBALS["VERBOSE"]){echo "META_MASTER_UFDBTABLES: $destdir/ufdbartica.txt OK\n";}
	@file_put_contents("$destdir/ufdbartica.txt", serialize($ARRAY));
	
	
	@unlink("$ArticaMetaStorage/webfiltering/ufdbartica.txt");
	@unlink("$ArticaMetaStorage/webfiltering/ARTICAUFDB_LAST_DOWNLOAD");
	@copy("/etc/artica-postfix/ARTICAUFDB_LAST_DOWNLOAD","$ArticaMetaStorage/webfiltering/ARTICAUFDB_LAST_DOWNLOAD");
	@file_put_contents("$ArticaMetaStorage/webfiltering/ufdbartica.txt",$DATABASES_VERSION);
	
	if(is_file("/etc/artica-postfix/artica-webfilter-db-index.txt")){
		@unlink("$ArticaMetaStorage/webfiltering/index.txt");
		@copy("/etc/artica-postfix/artica-webfilter-db-index.txt","$ArticaMetaStorage/webfiltering/index.txt");
	}
	
	if(is_file("/etc/artica-postfix/ufdbcounts.txt")){
		@unlink("$ArticaMetaStorage/webfiltering/ufdbcounts.txt");
		@copy("/etc/artica-postfix/ufdbcounts.txt","$ArticaMetaStorage/webfiltering/ufdbcounts.txt");
	}
	
	calculate_categorized_websites(true);
	artica_update_event(2, "Artica Webfiltering databases: Success update Meta Server webfiltering repository", @implode("\n", $GLOBALS["EVENTS"]),__FILE__,__LINE__);
	meta_admin_mysql(2, "Success update webfiltering repository with Webfiltering databases", null,__FILE__,__LINE__);

}

function calculate_categorized_websites($nopid=false){
	if(!$GLOBALS["FORCE"]){
	if(!$nopid){
		$unix=new unix();
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
		if($GLOBALS["VERBOSE"]){echo "pidTime $pidTime\n";}
		$nohup=$unix->find_program("nohup");
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid,__FILE__)){
			$timepid=$unix->PROCCESS_TIME_MIN($pid);
			if(!$GLOBALS["NOLOGS"]){
				// ufdbguard_admin_events("UFDB::Warning: Task already executed PID: $pid since {$timepid}Mn",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
				return;
			}
		}
		@file_put_contents($pidfile, getmypid());
		
		$pidTimeEx=$unix->file_time_min($pidTime);
		if($pidTimeEx<60){return;}
		
	}
	}
	
	if(!is_file("/etc/artica-postfix/ufdbcounts.txt")){
		echo "/etc/artica-postfix/ufdbcounts.txt No such file\n";
	}
	$arrayCount=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/ufdbcounts.txt")));
	$array=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/artica-webfilter-db-index.txt")));
	
	$WORKDIR=$GLOBALS["WORKDIR_LOCAL"];
	if(is_link($WORKDIR)){$WORKDIR=readlink($WORKDIR);}
	
	$c=0;
	$d=0;
	while (list ($path, $count) = each ($array) ){
		if(!is_file("$WORKDIR/$path/domains.ufdb")){
			
			if($GLOBALS["VERBOSE"]){echo "$WORKDIR/$path/domains.ufdb no such file\n";}
			continue;
		}
		$c=$c+intval($count);
		if($GLOBALS["VERBOSE"]){echo "$WORKDIR/$path - ".wFormatNumber($count) ."= ".wFormatNumber($c)."\n";}
		$UFDB_ARTICA_CATZ[$path]=@filesize("$WORKDIR/$path/domains.ufdb");
		$UFDB_ARTICA_COUNTZ[$path]=$arrayCount[$path];
		$d++;
	}
	
	if($GLOBALS["VERBOSE"]){echo "------------------------- ".wFormatNumber($c)."\n";}
	@file_put_contents("/usr/share/artica-postfix/ressources/UFDB_ARTICA_COUNT", $c);
	@file_put_contents("/usr/share/artica-postfix/ressources/UFDB_ARTICA_DBS", $d);
	@file_put_contents("/usr/share/artica-postfix/ressources/UFDB_ARTICA_CATZ", serialize($UFDB_ARTICA_CATZ));
	@file_put_contents("/usr/share/artica-postfix/ressources/UFDB_ARTICA_COUNTZ", serialize($UFDB_ARTICA_COUNTZ));
	
	
}

function wFormatNumber($number, $decimals = 0, $thousand_separator = ' ', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}

function ufdbversions(){
}
function ufdbevents($text=null){
	$unix=new unix();
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
	
		if(isset($trace[0])){
			$file=basename($trace[0]["file"]);
			$function=$trace[0]["function"];
			$line=$trace[0]["line"];
		}
	}
	
	if($GLOBALS["OUPUT"]){echo "$text [$line]\n";}
	$unix->events($text,"/var/log/artica-ufdb.log",false,$function,$line,$file);
}



function tlsetables($nopid=false){
	$unix=new unix();
	$sock=new sockets();
	$users=new usersMenus();
	if(!$nopid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$nohup=$unix->find_program("nohup");
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid,__FILE__)){
			$timepid=$unix->PROCCESS_TIME_MIN($pid);
			updatev2_progress(10,"Error, $timepid already running [".__LINE__."]");
			return;
			
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$EnableArticaMetaClient=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaMetaClient"));
	$EnableArticaMetaServer=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaMetaServer"));
	if($EnableArticaMetaServer==1){$EnableArticaMetaClient=0;}
	
	if($EnableArticaMetaClient==1){return false;}
	$CACHE_FILE="/etc/artica-postfix/settings/Daemons/CurrentTLSEDbCloud";
	$InterfaceFile="/etc/artica-postfix/settings/Daemons/TLSEDbCloud";
	updatev2_checktlse_version(true);
	$tmpdir=$unix->TEMP_DIR();
	$URIBASE="http://mirror.articatech.net/webfilters-tlse";
	$WORKDIR="/var/lib/ftpunivtlse1fr";
	if(is_link($WORKDIR)){$WORKDIR=readlink($WORKDIR);}
	$MAIN_ARRAY=unserialize(base64_decode(@file_get_contents($InterfaceFile)));
	$LOCAL_ARRAY=unserialize(@file_get_contents($CACHE_FILE));
	
	$countDecategories=count($MAIN_ARRAY);
	updatev2_progress(10,"$countDecategories {categories}");
	$c=0;
	$FAILED=0;
	$updated=0;
	$GLOBALS["DOWNLOADED_SIZE"]=0;
	while (list ($categoryname, $MAIN) = each ($MAIN_ARRAY) ){
		if($categoryname=="adult"){continue;}
		if($categoryname=="aggressive"){continue;}
		if($categoryname=="agressif"){continue;}
		if($categoryname=="redirector"){continue;}
		if($categoryname=="ads"){continue;}
		if($categoryname=="drogue"){continue;}
		$ROWS=$MAIN["ROWS"];
		$MD5SRC=$MAIN["MD5SRC"];
		$MD5GZ=$MAIN["MD5GZ"];
		$TIME=$MAIN["TIME"];
		$SIZE=$MAIN["SIZE"];
		$increment_text=" [$c/$countDecategories]";
		$c++;
		$prc=$c/$countDecategories;
		$prc=round($prc*100);
		if($prc>10){
			if($prc<80){
				updatev2_progress($prc,"TLSE: {checking} $categoryname ($ROWS {items}$increment_text");
			}
		}
		if($prc==0){$prc=10;}
		
		$CurrentFile="$WORKDIR/$categoryname/domains.ufdb";
		if(!is_file($CurrentFile)){
			echo "! $CurrentFile No such file...\n";
		}
		
		
		$CURRENT_MD5=md5_file($CurrentFile);
		if($CURRENT_MD5==$MD5SRC){
			updatev2_progress($prc,"TLSE: {updated} $categoryname (skip)");
			$LOCAL_ARRAY[$categoryname]["ROWS"]=$ROWS;
			$LOCAL_ARRAY[$categoryname]["TIME"]=$TIME;
			$LOCAL_ARRAY[$categoryname]["SUCCESS"]=true;
			$LOCAL_ARRAY[$categoryname]["MD5SRC"]=$CURRENT_MD5;
			$LOCAL_ARRAY[$categoryname]["SIZE"]=@filesize($CurrentFile);
			@file_put_contents($CACHE_FILE, serialize($LOCAL_ARRAY));			
			continue;
		}
			
		echo "$CURRENT_MD5 <> $MD5SRC\n";
		
		
		if(!update_category_tlse("$URIBASE/$categoryname.gz",$categoryname,$MD5GZ,$MD5SRC,$prc,$increment_text)){
				updatev2_progress($prc,"TLSE: {failed} $categoryname (skip)$increment_text");
				continue;
		}
		
		
		$DOWNLOADED_SIZE=FormatBytes($GLOBALS["DOWNLOADED_SIZE"]/1024);
		updatev2_progress($prc,"{success} $categoryname ($DOWNLOADED_SIZE)");
		$LOCAL_ARRAY[$categoryname]["ROWS"]=$ROWS;
		$LOCAL_ARRAY[$categoryname]["TIME"]=$TIME;
		$LOCAL_ARRAY[$categoryname]["UPDATED"]=time();
		$LOCAL_ARRAY[$categoryname]["SUCCESS"]=true;
		$LOCAL_ARRAY[$categoryname]["MD5SRC"]=md5_file($CurrentFile);
		$LOCAL_ARRAY[$categoryname]["SIZE"]=@filesize($CurrentFile);
		@file_put_contents($CACHE_FILE, serialize($LOCAL_ARRAY));
		$updated++;
		
		
		
	}
	
	if($updated>0){
		artica_update_event(2, "Success update $updated Free Webfiltering databases", null,__FILE__,__LINE__);
		squid_admin_mysql(2, "Success update $updated Free Webfiltering databases", null,__FILE__,__LINE__);
	}
	
	TLSE_CONVERTION_DISK();
	@file_put_contents($CACHE_FILE, serialize($LOCAL_ARRAY));
	updatev2_progress(80,"{done}");
	
}


function TLSE_CONVERTION_DISK(){
	$unix=new unix();
	$CACHE_FILE="/etc/artica-postfix/settings/Daemons/CurrentTLSEDbCloud";
	$WORKDIR="/var/lib/ftpunivtlse1fr";
	$q=new mysql_squid_builder();
	$rm=$unix->find_program("rm");
	$ln=$unix->find_program("ln");
	$LOCAL_ARRAY=unserialize(@file_get_contents($CACHE_FILE));
	
	$countDecategories=count($LOCAL_ARRAY);
	while (list ($categoryname, $MAIN) = each ($LOCAL_ARRAY) ){
		$Conversion=$q->TLSE_CONVERTION();
		$categoryDISK=$categoryname;
		if(isset($Conversion[$categoryname])){$categoryDISK=$Conversion[$categoryname];}
		$categoryDISK=str_replace("/", "_", $categoryDISK);
		
		
		if(is_link("/var/lib/ftpunivtlse1fr/$categoryname")){
			echo "Source dir already linked $categoryname -> ".@readlink("/var/lib/ftpunivtlse1fr/$categoryname")."\n";
			continue;
			
		}
		
		
		if(trim(strtolower($categoryDISK))==trim(strtolower($categoryname))){continue;}
		echo "/var/lib/ftpunivtlse1fr/$categoryDISK must be a symbolik link to /var/lib/ftpunivtlse1fr/$categoryname\n";
			
			if(is_link("/var/lib/ftpunivtlse1fr/$categoryDISK")){
				$tmpdir=@readlink("/var/lib/ftpunivtlse1fr/$categoryDISK");
				echo "$categoryDISK is already linked to $tmpdir\n";
				if($tmpdir<>"/var/lib/ftpunivtlse1fr/$categoryname"){
					echo "remove -> $tmpdir\n";
					shell_exec("$rm -f /var/lib/ftpunivtlse1fr/$categoryDISK");
				}
			}
			
			if(!is_link("/var/lib/ftpunivtlse1fr/$categoryDISK")){
				
				if(!is_dir("/var/lib/ftpunivtlse1fr/$categoryname")){
					echo "*** failed /var/lib/ftpunivtlse1fr/$categoryname no such directory\n";
					continue;
				}
				
				echo "Linking $categoryDISK -> /var/lib/ftpunivtlse1fr/$categoryname\n";
				shell_exec("$rm -rf /var/lib/ftpunivtlse1fr/$categoryDISK");
				shell_exec("$ln -sf /var/lib/ftpunivtlse1fr/$categoryname /var/lib/ftpunivtlse1fr/$categoryDISK");
			}
			
		
		
		
	
	}
	
}


function ufdbtables($nopid=false){
	$unix=new unix();
	$sock=new sockets();
	$users=new usersMenus();
	
	if(!$users->CORP_LICENSE){
		updatev2_checkversions();
		updatev2_progress(10,"{license_error}");
		return;
	}
	
	if(!$nopid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$nohup=$unix->find_program("nohup");
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid,__FILE__)){
			$timepid=$unix->PROCCESS_TIME_MIN($pid);
			updatev2_progress(110,"Error, $timepid already running [".__LINE__."]");
			if(!$GLOBALS["NOLOGS"]){
				// ufdbguard_admin_events("UFDB::Warning: Task already executed PID: $pid since {$timepid}Mn",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
				return;
			}
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$EnableArticaMetaClient=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaMetaClient"));
	$EnableArticaMetaServer=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaMetaServer"));
	if($EnableArticaMetaServer==1){$EnableArticaMetaClient=0;}
	
	if($EnableArticaMetaClient==1){
		updatev2_progress(10,"Using Meta console [".__LINE__."]");
		return ufdbtables_artica_meta();
	}
	
	$GLOBALS["EVENTS"]=array();
	$CACHE_FILE="/etc/artica-postfix/settings/Daemons/CurrentArticaDbCloud";
	$InterfaceFile="/etc/artica-postfix/settings/Daemons/ArticaDbCloud";
	updatev2_checkversion();
	
	
	$UseRemoteUfdbguardService=$sock->GET_INFO('UseRemoteUfdbguardService');
	if(!is_numeric($UseRemoteUfdbguardService)){$UseRemoteUfdbguardService=0;}
	
	if($UseRemoteUfdbguardService==1){
		updatev2_progress(10,"UseRemoteUfdbguardService = TRUE - aborting [".__LINE__."]");
		return;
	}
	
	if($GLOBALS["MIRROR"]==null){ 
		updatev2_progress(10,"MIRROR is null #1 [".__LINE__."]");
		unset($GLOBALS["updatev2_checkversion"]);
		updatev2_checkversion();
	}
	
	if($GLOBALS["MIRROR"]==null){
		updatev2_progress(10,"MIRROR is null #2 [".__LINE__."]");
		webupdate_admin_mysql(0, "Unable to find a suitable mirror", null,__FILE__,__LINE__);
		return;
	}
	
	
	$tmpdir=$unix->TEMP_DIR();
	$URIBASE=$GLOBALS["MIRROR"];
	$WORKDIR=$GLOBALS["WORKDIR_LOCAL"];
	if(is_link($WORKDIR)){$WORKDIR=readlink($WORKDIR);}
	

	
	$CategoriesDatabasesByCron=intval($sock->GET_INFO("CategoriesDatabaseByCron"));
	
	
	if(!$GLOBALS["FORCE"]){
		if($CategoriesDatabasesByCron==1){
			if(!$GLOBALS["BYCRON"]){
				updatev2_progress(110,"{done} CategoriesDatabasesByCron [".__LINE__."]");
				return;}
		}
	}
	
	$MAIN_ARRAY=unserialize(base64_decode(@file_get_contents($InterfaceFile)));
	$LOCAL_ARRAY=unserialize(@file_get_contents($CACHE_FILE));
	
	$countDecategories=count($MAIN_ARRAY);
	updatev2_progress(10,"$countDecategories {categories}");
	$c=0;
	$FAILED=0;
	$updated=0;
	$GLOBALS["DOWNLOADED_SIZE"]=0;
	@mkdir("/var/lib/ufdbartica",0755,true);
	$CountOfPackages=count($MAIN_ARRAY);
	while (list ($tablename, $MAIN) = each ($MAIN_ARRAY) ){
		$c++;
		$increment_text=" [$c/$CountOfPackages]";
		$DOWNLOADED_SIZE=FormatBytes($GLOBALS["DOWNLOADED_SIZE"]/1024);
		$ROWS=$MAIN["ROWS"];
		$ROWS_TEXT=FormatNumber($ROWS);
		$TIME=$MAIN["TIME"];
		$MD5SRC=$MAIN["MD5SRC"];
		$MD5GZ=$MAIN["MD5GZ"];
		$CurrentFile="/var/lib/ufdbartica/$tablename/domains.ufdb";
		$CURRENT_MD5=md5_file($CurrentFile);
		$prc=$c/$countDecategories;
		$prc=round($prc*100);
		if($prc>10){
			if($prc<95){
				updatev2_progress($prc,"{checking} $tablename ($ROWS_TEXT {items})$increment_text");
			}
		}
		if($CURRENT_MD5==$MD5SRC){
			updatev2_progress($prc,"{skipping} $tablename ($ROWS_TEXT {items})$increment_text");
			$LOCAL_ARRAY[$tablename]["ROWS"]=$ROWS;
			$LOCAL_ARRAY[$tablename]["TIME"]=$TIME;
			$LOCAL_ARRAY[$tablename]["SUCCESS"]=true;
			$LOCAL_ARRAY[$tablename]["MD5SRC"]=$CURRENT_MD5;
			$LOCAL_ARRAY[$tablename]["SIZE"]=@filesize($CurrentFile);
			@file_put_contents($CACHE_FILE, serialize($LOCAL_ARRAY));			
			continue;
		}
			
		updatev2_progress($prc,"{updating} $tablename ($DOWNLOADED_SIZE) ($ROWS_TEXT {items})$increment_text");
		if(!update_category("$URIBASE/$tablename.gz",$tablename,$MD5GZ,$MD5SRC,$prc,$increment_text)){
			updatev2_progress($prc,"{update_failed} $tablename");
			$LOCAL_ARRAY[$tablename]["SUCCESS"]=false;
			$FAILED++;
			if($FAILED>5){
				updatev2_progress(110,"{too_many_errors} {aborting}");
				return;
			}
			continue;
		}
		

		
		$DOWNLOADED_SIZE=FormatBytes($GLOBALS["DOWNLOADED_SIZE"]/1024);
		updatev2_progress($prc,"{success} $tablename ($DOWNLOADED_SIZE)");
		$LOCAL_ARRAY[$tablename]["ROWS"]=$ROWS;
		$LOCAL_ARRAY[$tablename]["TIME"]=$TIME;
		$LOCAL_ARRAY[$tablename]["UPDATED"]=time();
		$LOCAL_ARRAY[$tablename]["SUCCESS"]=true;
		$LOCAL_ARRAY[$tablename]["MD5SRC"]=md5_file($CurrentFile);
		$LOCAL_ARRAY[$tablename]["SIZE"]=@filesize($CurrentFile);
		@file_put_contents($CACHE_FILE, serialize($LOCAL_ARRAY));
		$updated++;
	}
	
	if($updated>0){
		artica_update_event(2, "Success update $updated Licensed Webfiltering databases", null,__FILE__,__LINE__);
		squid_admin_mysql(2, "Success update $updated Licensed Webfiltering databases", null,__FILE__,__LINE__);
		squid_admin_mysql(2, "Reloading Web filtering services", null,__FILE__,__LINE__);
		shell_exec("/etc/init.d/ufdb reload");
		shell_exec("/etc/init.d/ufdbcat reload");
		
	}
	
	
	updatev2_progress(96,"{verify}");
	reset($MAIN_ARRAY);
	while (list ($tablename, $MAIN) = each ($MAIN_ARRAY) ){

		
		
		
		$ROWS=$MAIN["ROWS"];
		$ROWS_TEXT=FormatNumber($ROWS);
		$TIME=$MAIN["TIME"];
		$MD5SRC=$MAIN["MD5SRC"];
		$MD5GZ=$MAIN["MD5GZ"];
		$CurrentFile="/var/lib/ufdbartica/$tablename/domains.ufdb";
		echo "$tablename\n";
		$unix->events("Verify: $tablename [$MD5SRC]","/var/log/artica-ufdb.log",false,__FUNCTION__,__LINE__,__FILE__);
		
		
		if(!is_file($CurrentFile)){
			$unix->events("Verifiy: $tablename $CurrentFile no such file","/var/log/artica-ufdb.log",false,__FUNCTION__,__LINE__,__FILE__);
			echo "$CurrentFile no such file\n";
			$LOCAL_ARRAY[$tablename]["SUCCESS"]=false;
			updatev2_progress(96,"$tablename {failed}");
			continue;
		}
			
		$md5db=md5_file($CurrentFile);
		if($md5db<>$MD5SRC){
			$unix->events("Verifiy: $tablename $CurrentFile MD5 differ $md5db<>$MD5SRC","/var/log/artica-ufdb.log",false,__FUNCTION__,__LINE__,__FILE__);
			echo "$CurrentFile MD5 differ\n";
			$LOCAL_ARRAY[$tablename]["SUCCESS"]=false;
			updatev2_progress(96,"$tablename {failed}");
			continue;
		}
		
		$unix->events("Verifiy: $tablename success...","/var/log/artica-ufdb.log",false,__FUNCTION__,__LINE__,__FILE__);
		$LOCAL_ARRAY[$tablename]["SUCCESS"]=true;
		$LOCAL_ARRAY[$tablename]["MD5SRC"]=$md5db;
		$LOCAL_ARRAY[$tablename]["SIZE"]=@filesize($CurrentFile);
		$LOCAL_ARRAY[$tablename]["ROWS"]=$ROWS;
		$LOCAL_ARRAY[$tablename]["TIME"]=$TIME;
		$LOCAL_ARRAY[$tablename]["UPDATED"]=time();
		
		}
		
		
		
	
	
	
		

	@file_put_contents($CACHE_FILE, serialize($LOCAL_ARRAY));
	updatev2_progress(99,"{done}");
	
	
}

function TranslateToMetaServer(){
	$sock=new sockets();
	$unix=new unix();
	$CREATE_PACKAGE=false;
	$EnableArticaMetaServer=intval($sock->GET_INFO("EnableArticaMetaServer"));
	if($EnableArticaMetaServer==0){return;}
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	$WebFilteringDir="$ArticaMetaStorage/webfiltering2";
	if(!is_dir($WebFilteringDir)){@mkdir($WebFilteringDir,0755,true);}
	$LOCAL_CACHE_FILE="/etc/artica-postfix/settings/Daemons/CurrentArticaDbCloud";
	$InterfaceFile="/etc/artica-postfix/settings/Daemons/ArticaDbCloud";
	
	$CACHE_FILE="/etc/artica-postfix/settings/Daemons/CurrentArticaDbCloud";
	if(!is_file($LOCAL_CACHE_FILE)){
		if(is_file($InterfaceFile)){$LOCAL_CACHE_FILE=$InterfaceFile;}
	}
	
	$TLSE_LOCAL_CACHE_FILE="/etc/artica-postfix/settings/Daemons/CurrentTLSEDbCloud";
	
	
	$ArticaMetaWebFilterDirsMD5=$sock->GET_INFO("ArticaMetaWebFilterDirsMD5");
	
	$md51=$unix->md5_dir("/var/lib/ufdbartica");
	$md52=$unix->md5_dir("/var/lib/ftpunivtlse1fr");
	
	echo "/var/lib/ufdbartica....: $md51\n";
	echo "/var/lib/ftpunivtlse1fr: $md52\n";
	$md5All=md5("$md51$md52");
	
	
	
	$tar=$unix->find_program("tar");
	$cd=$unix->find_program("cd");
	$split=$unix->find_program("split");
	$rm=$unix->find_program("rm");

	if($md5All<>$ArticaMetaWebFilterDirsMD5){$CREATE_PACKAGE=true; }
	if(!is_file("$WebFilteringDir/metaindex.txt")){$CREATE_PACKAGE=true;}
	if(!$CREATE_PACKAGE){
		echo "Nothing to do: $md5All\n";
		return;}
	
	if(is_dir("/var/lib/ufdbartica")){$T[]="/var/lib/ufdbartica";}
	if(is_dir("/var/lib/ftpunivtlse1fr")){$T[]="/var/lib/ftpunivtlse1fr";}
	if(is_file($TLSE_LOCAL_CACHE_FILE)){$T[]="$TLSE_LOCAL_CACHE_FILE";}
	if(is_file($LOCAL_CACHE_FILE)){$T[]="$LOCAL_CACHE_FILE";}
	
	shell_exec("$rm -f $WebFilteringDir/*");
	$cmd="$tar czvf $WebFilteringDir/webfiltering.tgz ".@implode(" ", $T);
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
	shell_exec($cmd);
	
	if(!is_file("$WebFilteringDir/webfiltering.tgz")){
		echo "$WebFilteringDir/webfiltering.tgz no such file\n";
		return false;
	}
	
	$MAIN_ARRAY["MD5"]=md5_file("$WebFilteringDir/webfiltering.tgz");
	
	chdir("$WebFilteringDir");
	if(is_file($cd)){
		echo "$cd $WebFilteringDir\n";
		system("$cd $WebFilteringDir");
	}
	
	
	echo "Split...Webfiltering.tar.gz\n";
	$cmd="$split -a 3 -b 1m -d webfiltering.tgz webfiltering.tgz.";
	echo "$cmd\n";
	system($cmd);
	$time=time();
	@file_put_contents("/etc/artica-postfix/settings/Daemons/MetaWebfilteringTime", $time);
	$MAIN_ARRAY["TIME"]=$time;
	@unlink("$WebFilteringDir/webfiltering.tgz");
	
	$files=$unix->DirFiles("$WebFilteringDir","webfiltering\.tgz\.[0-9]+");
	
	
	while (list ($num, $ligne) = each ($files) ){
		$Splited_path=basename($WebFilteringDir)."/$num";
		$SrcFilename="$WebFilteringDir/$num";
		$SrcFilenameMD5=md5_file($SrcFilename);
		if($GLOBALS["VERBOSE"]){echo $SrcFilename." / $Splited_path / $SrcFilenameMD5\n";}
		$MAIN_ARRAY["FILES"][$Splited_path]=$SrcFilenameMD5;
	}
	
	if(count($MAIN_ARRAY["FILES"])>1){
		@file_put_contents("$WebFilteringDir/metaindex.txt", serialize($MAIN_ARRAY));
		@file_put_contents("/etc/artica-postfix/settings/Daemons/WebFilteringMeta", serialize($MAIN_ARRAY));
		$sock->SET_INFO("ArticaMetaWebFilterDirsMD5", $md5All);
	}
	
	
	
}


function update_category_tlse($URI,$tablename,$MD5GZ,$MD5SRC,$prc,$increment_text){
	$GLOBALS["previousProgress"]=0;
	$GLOBALS["CURRENT_DB"]=$tablename;
	$GLOBALS["CURRENT_PRC"]=$prc;
	$unix=new unix();
	$curl=new ccurl($URI);
	$curl->NoHTTP_POST=true;
	$curl->Timeout=360;
	$curl->WriteProgress=true;
	$curl->ProgressFunction="download_progress";
	$tmpdir=$unix->TEMP_DIR();
	$tmpfile="$tmpdir/$tablename.gz";
	$dbtemp="$tmpdir/$tablename.ufdb";
	$GLOBALS["increment_text"]=$increment_text;
	
	
	$finaldirectory="/var/lib/ftpunivtlse1fr/$tablename";
	$finaldestination="$finaldirectory/domains.ufdb";
	if(is_link($finaldirectory)){$finaldirectory=@readlink($finaldirectory);}
	if(!is_dir(dirname("$finaldestination"))){@mkdir(dirname("$finaldestination"),0755,true);}
	if(is_file($tmpfile)){@unlink($tmpfile);}
	
	
	
	
	if(is_file($finaldestination)){
		$md5db=md5_file($finaldestination);
		if($md5db==$MD5SRC){
			echo "Success, Already updated\n";
			return true;
		}
	}
	
	
	updatev2_progress($prc,"TLSE: {downloading} $tablename");
	if(!$curl->GetFile($tmpfile)){
		if(is_file($tmpfile)){@unlink($tmpfile);}
		echo "Downloading $tablename.gz Failed: $curl->error\n";
		while (list ($a, $b) = each ($curl->errors) ){echo "Report: $b\n";}
		squid_admin_mysql(1,"TLSE: Unable to download blacklist $tablename.gz file $curl->error",@implode("\n", $curl->errors),__FUNCTION__,__LINE__);
		return false;
	
	}
	
		$md5gz=md5_file($tmpfile);
		if($md5gz<>$MD5GZ){
		echo "Failed: Corrupted download $md5gz<>$MD5GZ\n";
		squid_admin_mysql(1,"TLSE: Unable to update blacklist $tablename.gz MD5 differ","$md5gz<>$MD5GZ",__FUNCTION__,__LINE__);
		if(is_file($tmpfile)){@unlink($tmpfile);}
		return false;
		}
	
		$GLOBALS["DOWNLOADED_SIZE"]=$GLOBALS["DOWNLOADED_SIZE"]+@filesize($tmpfile);
	
		updatev2_progress($prc,"TLSE: {uncompress} $tablename");
		$unix->uncompress($tmpfile, $dbtemp);
		if(is_file($tmpfile)){@unlink($tmpfile);}
		$md5db=md5_file($dbtemp);
		if($md5db<>$MD5SRC){
		echo "Failed: Corrupted uncompress $md5gz<>$MD5GZ\n";
		squid_admin_mysql(1,"TLSE: Unable to uncompress blacklist $tablename.gz MD5 differ","$md5db<>$MD5SRC",__FUNCTION__,__LINE__);
		if(is_file($dbtemp)){@unlink($dbtemp);}
		return false;
		}
	
		$finaldestination_name=basename($finaldestination);
	
		if(is_file($finaldestination)){
			if(is_file("$tmpdir/or-$finaldestination_name")){@unlink("$tmpdir/or-$finaldestination_name");}
			if(!@copy($finaldestination,"$tmpdir/or-$finaldestination_name")){
				echo "Failed: Backup original $finaldestination_name\n";
				if(is_file("$tmpdir/or-$finaldestination_name")){@unlink("$tmpdir/or-$finaldestination_name");}
				if(is_file($dbtemp)){@unlink($dbtemp);}
				return false;
			}
	
			@unlink($finaldestination);
	
		}
	
	updatev2_progress($prc,"TLSE: {installing} $tablename$increment_text");
	
	echo "$dbtemp -> $finaldestination\n";
	
	if(!@copy($dbtemp,$finaldestination)){
		echo "Failed: moved to original $finaldestination\n";
		if(is_file("$tmpdir/or-$finaldestination_name")){
		@copy("$tmpdir/or-$finaldestination_name",$finaldestination);
		@unlink("$tmpdir/or-$finaldestination_name");
	}
	return false;
	
	}
	
	if(is_file("$tmpdir/or-$finaldestination_name")){@unlink("$tmpdir/or-$finaldestination_name");}
	if(is_file($dbtemp)){@unlink($dbtemp);}
	if(is_file($tmpfile)){@unlink($tmpfile);}
	$GLOBALS["DOWNLOADED_INSTALLED"]=$GLOBALS["DOWNLOADED_INSTALLED"]+1;
	return true;
	
		
	
	
}


function update_category($URI,$tablename,$MD5GZ,$MD5SRC,$prc,$increment_text=null){
	$GLOBALS["previousProgress"]=0;
	$GLOBALS["CURRENT_DB"]=$tablename;
	$GLOBALS["CURRENT_PRC"]=$prc;
	$unix=new unix();
	$curl=new ccurl($URI);
	$curl->NoHTTP_POST=true;
	$curl->Timeout=360;
	$curl->WriteProgress=true;
	$curl->ProgressFunction="download_progress";
	$tmpdir=$unix->TEMP_DIR();
	$tmpfile="$tmpdir/$tablename.gz";
	$dbtemp="$tmpdir/$tablename.ufdb";
	$GLOBALS["increment_text"]=$increment_text;
	$finaldestination="/var/lib/ufdbartica/$tablename/domains.ufdb";
	if(!is_dir(dirname("$finaldestination"))){@mkdir(dirname("$finaldestination"),0755,true);}
	if(is_file($tmpfile)){@unlink($tmpfile);}
	
	if(is_file($finaldestination)){
		$md5db=md5_file($finaldestination);
		if($md5db==$MD5SRC){
			echo "Success, Already updated\n";
			return true;
		}
	}
	
	
	updatev2_progress($prc,"{downloading} $tablename$increment_text");
	if(!$curl->GetFile($tmpfile)){
		if(is_file($tmpfile)){@unlink($tmpfile);}
		$unix->events("Downloading $tablename.gz Failed: $curl->error","/var/log/artica-ufdb.log",false,__FUNCTION__,__LINE__,__FILE__);
		echo "Downloading $tablename.gz Failed: $curl->error\n";
		while (list ($a, $b) = each ($curl->errors) ){echo "Report: $b\n";}
		squid_admin_mysql(1,"Unable to download blacklist $tablename.gz file $curl->error",@implode("\n", $curl->errors),__FUNCTION__,__LINE__);
		return false;
		
	}
	
	$md5gz=md5_file($tmpfile);
	if($md5gz<>$MD5GZ){
		echo "Failed: Corrupted download $md5gz<>$MD5GZ\n";
		$unix->events("Downloading $tablename.gz Failed: Corrupted download $md5gz<>$MD5GZ","/var/log/artica-ufdb.log",false,__FUNCTION__,__LINE__,__FILE__);
		squid_admin_mysql(1,"Unable to update blacklist $tablename.gz MD5 differ","$md5gz<>$MD5GZ",__FUNCTION__,__LINE__);
		if(is_file($tmpfile)){@unlink($tmpfile);}
		return false;
	}
	
	$GLOBALS["DOWNLOADED_SIZE"]=$GLOBALS["DOWNLOADED_SIZE"]+@filesize($tmpfile);
	
	updatev2_progress($prc,"{uncompress} $tablename$increment_text");
	$unix->uncompress($tmpfile, $dbtemp);
	if(is_file($tmpfile)){@unlink($tmpfile);}
	$md5db=md5_file($dbtemp);
	if($md5db<>$MD5SRC){
		$unix->events("Downloading $tablename.gz Failed: Corrupted download $md5gz<>$MD5GZ","/var/log/artica-ufdb.log",false,__FUNCTION__,__LINE__,__FILE__);
		echo "Failed: Corrupted uncompress $md5db<>$MD5GZ\n";
		squid_admin_mysql(1,"Unable to uncompress blacklist $tablename.gz MD5 differ","$md5db<>$MD5SRC",__FUNCTION__,__LINE__);
		if(is_file($dbtemp)){@unlink($dbtemp);}
		return false;
	}	
	
	$finaldestination_name=basename($finaldestination);
	
	if(is_file($finaldestination)){
		if(is_file("$tmpdir/or-$finaldestination_name")){@unlink("$tmpdir/or-$finaldestination_name");}

		if(!@copy($finaldestination,"$tmpdir/or-$finaldestination_name")){
			$unix->events("Failed: Backup original $finaldestination_name","/var/log/artica-ufdb.log",false,__FUNCTION__,__LINE__,__FILE__);
			echo "Failed: Backup original $finaldestination_name\n";
			if(is_file("$tmpdir/or-$finaldestination_name")){@unlink("$tmpdir/or-$finaldestination_name");}
			if(is_file($dbtemp)){@unlink($dbtemp);}
			return false;
		}
		
		@unlink($finaldestination);
		
	}
	
	updatev2_progress($prc,"{installing} $tablename$increment_text");
	if(!@copy($dbtemp,$finaldestination)){
		echo "Failed: moved to original $finaldestination\n";
		if(is_file("$tmpdir/or-$finaldestination_name")){
			@copy("$tmpdir/or-$finaldestination_name",$finaldestination);
			@unlink("$tmpdir/or-$finaldestination_name");
		}
		return false;
		
	}
	
	if(is_file("$tmpdir/or-$finaldestination_name")){@unlink("$tmpdir/or-$finaldestination_name");}
	if(is_file($dbtemp)){@unlink($dbtemp);}
	if(is_file($tmpfile)){@unlink($tmpfile);}
	$GLOBALS["DOWNLOADED_INSTALLED"]=$GLOBALS["DOWNLOADED_INSTALLED"]+1;
	return true;
	
	
}

function download_progress( $download_size, $downloaded_size, $upload_size, $uploaded_size ){
	if(!isset($GLOBALS["previousProgress"])){$GLOBALS["previousProgress"]= 0;}

	if ( $download_size == 0 ){
		$progress = 0;
	}else{
		$progress = round( $downloaded_size * 100 / $download_size );
	}

	if ( $progress > $GLOBALS["previousProgress"]){
		if($progress<95){
			updatev2_progress($GLOBALS["CURRENT_PRC"],"{downloading} {$GLOBALS["CURRENT_DB"]} {$progress}%{$GLOBALS["increment_text"]}");
		}
		$GLOBALS["previousProgress"]=$progress;
			
	}
}



function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}





function RegisterSupport(){

}


	
	

function updatev2_checkversions($progress=true){
	updatev2_checkversion($progress);
	updatev2_checktlse_version($progress);
	
}

function updatev2_checktlse_version($progress=true){
	if(isset($GLOBALS["updatev2_checktlse_version"])){return null;}
	$GLOBALS["updatev2_checktlse_version"]=true;
	$GLOBALS["MIRROR"]=null;
	$unix=new unix();
	$tmpdir=$unix->TEMP_DIR();

	$unix=new unix();
	$URIBASE=$unix->MAIN_URI();
	$sock=new sockets();
	$ArticaDbReplicate=$sock->GET_INFO("ArticaDbReplicate");
	if(!is_numeric($ArticaDbReplicate)){$ArticaDbReplicate=0;}


	$mirror="http://mirror.articatech.net/webfilters-tlse";
	$NewUri="$mirror/index.txt";
	$uri=$NewUri;
	
	$InterfaceFile="/etc/artica-postfix/settings/Daemons/TLSEDbCloud";
	@mkdir("/usr/share/artica-postfix/ressources/logs/web/cache",0755);

	$unix=new unix();

	$ZORDERS=array();
	if($progress){updatev2_progress(10,"{checking} {repositories} [".__LINE__."]");}
	if($progress){updatev2_progress(10,"{checking} $uri [".__LINE__."]");}
	$tmpfile="/$tmpdir/articatechdb.".md5($NewUri).".tmp";
	$curl=new ccurl($NewUri,false,null,true);



	$curl->Timeout=60;
	if(!$curl->GetFile($tmpfile)){
		webupdate_admin_mysql(1, "$NewUri repository failed", $curl->error,__FILE__,__LINE__);
		$GLOBALS["EVENTS"][]="$NewUri: Failed with error $curl->error";
		return;
	}


	if(!is_file($tmpfile)){
		$GLOBALS["EVENTS"][]="$NewUri: Failed with error No such file";
		return;
	}
	$array=unserialize(base64_decode(@file_get_contents($tmpfile)));
	if(!is_array($array)){
		webupdate_admin_mysql(1, "$NewUri: Failed with error No Array", null,__FILE__,__LINE__);
		$GLOBALS["EVENTS"][]="$NewUri: Failed with error No Array";
		@unlink($tmpfile);
		return;
	}


	$TIME=0;
	while (list ($table,$MAIN) = each ($array) ){
		$xTIME=$MAIN["TIME"];
		if($xTIME>$TIME){$TIME=$xTIME;}
	}

	if($TIME>0){
		echo "Time version = $TIME ".date("Y-m-d H:i:s",$TIME)."\n";
		$array["NEXT_VERSION"]=$TIME;
	}

	if(!isset($array["NEXT_VERSION"])){
		webupdate_admin_mysql(1, "$NewUri: Failed with error No TIME CODE", null,__FILE__,__LINE__);
		$GLOBALS["EVENTS"][]="$NewUri: Failed with error No TIME CODE";
		@unlink($tmpfile);
		return;
	}


	$xdate=date("l d F Y H:i:s",$TIME);
	if($progress){updatev2_progress(10,"$uri: Available version: $xdate - {$array["NEXT_VERSION"]} [".__LINE__."]");}
	$GLOBALS["EVENTS"][]="$uri: Available version: $xdate - {$array["NEXT_VERSION"]}/". date("Y-m-d H:i:s",$array["NEXT_VERSION"]);
	echo "[".__LINE__."]: Available version: $xdate - {$array["NEXT_VERSION"]}/". date("Y-m-d H:i:s",$array["NEXT_VERSION"])."\n";

	$GLOBALS["MIRROR"]=$mirror;

	if($GLOBALS["MIRROR"]==null){
		if($progress){updatev2_progress(110,"Error, unable to find a suitable {repositories} [".__LINE__."]");}
		ufdbevents("error, unable to find a suitable mirror");
		squid_admin_mysql(0,"Unable to find a suitable mirror",null,__FUNCTION__,__LINE__);
		artica_update_event(0,"Unable to find a suitable mirror",null,__FUNCTION__,__LINE__);
		return null;
	}


	
	@copy($tmpfile,$InterfaceFile);
	@chmod($InterfaceFile,0755);
	if($progress){updatev2_progress(10,"{done} [".__LINE__."]");}

}


function updatev2_checkversion($progress=true){
	if(isset($GLOBALS["updatev2_checkversion"])){return null;}
	
	$GLOBALS["MIRROR"]=null;
	$unix=new unix();
	$tmpdir=$unix->TEMP_DIR();
	
	$unix=new unix();
	$URIBASE=$unix->MAIN_URI();
	$sock=new sockets();
	$ArticaDbReplicate=$sock->GET_INFO("ArticaDbReplicate");
	if(!is_numeric($ArticaDbReplicate)){$ArticaDbReplicate=0;}
	
	
	$mirror="http://mirror.articatech.net/webfilters-databases";
	$NewUri="$mirror/index.txt";
	$uri=$NewUri;
	$destinationfile="/usr/share/artica-postfix/ressources/logs/web/cache/CATZ_ARRAY";
	$InterfaceFile="/etc/artica-postfix/settings/Daemons/ArticaDbCloud";
	@mkdir("/usr/share/artica-postfix/ressources/logs/web/cache",0755);
	
	$unix=new unix();
	
	$ZORDERS=array();
	if($progress){updatev2_progress(10,"{checking} {repositories} [".__LINE__."]");}
	if($progress){updatev2_progress(10,"{checking} $uri [".__LINE__."]");}
	$tmpfile="/$tmpdir/articatechdb.".md5($NewUri).".tmp";
	$curl=new ccurl($NewUri,false,null,true);
	
	
	
	$curl->Timeout=60;
	if(!$curl->GetFile($tmpfile)){
		webupdate_admin_mysql(1, "$NewUri repository failed", $curl->error,__FILE__,__LINE__);
		$GLOBALS["EVENTS"][]="$NewUri: Failed with error $curl->error";
		return;
		}
		
		
	if(!is_file($tmpfile)){
		$GLOBALS["EVENTS"][]="$NewUri: Failed with error No such file";
		return;
	}
	$array=unserialize(base64_decode(@file_get_contents($tmpfile)));
	if(!is_array($array)){
			webupdate_admin_mysql(1, "$NewUri: Failed with error No Array", null,__FILE__,__LINE__);
			$GLOBALS["EVENTS"][]="$NewUri: Failed with error No Array";
			@unlink($tmpfile);
			return;
		}
		
		
	$TIME=0;
	while (list ($table,$MAIN) = each ($array) ){	
		$xTIME=$MAIN["TIME"];
		if($xTIME>$TIME){$TIME=$xTIME;}
	}
	
	if($TIME>0){
		echo "Time version = $TIME ".date("Y-m-d H:i:s",$TIME)."\n";
		$array["NEXT_VERSION"]=$TIME;
	}
		
	if(!isset($array["NEXT_VERSION"])){
		webupdate_admin_mysql(1, "$NewUri: Failed with error No TIME CODE", null,__FILE__,__LINE__);
		$GLOBALS["EVENTS"][]="$NewUri: Failed with error No TIME CODE";
		@unlink($tmpfile);
		return;
	}
		
		
	$xdate=date("l d F Y H:i:s",$TIME);
	if($progress){updatev2_progress(10,"$uri: Available version: $xdate - {$array["NEXT_VERSION"]} [".__LINE__."]");}
	$GLOBALS["EVENTS"][]="$uri: Available version: $xdate - {$array["NEXT_VERSION"]}/". date("Y-m-d H:i:s",$array["NEXT_VERSION"]);
	echo "[".__LINE__."]: Available version: $xdate - {$array["NEXT_VERSION"]}/". date("Y-m-d H:i:s",$array["NEXT_VERSION"])."\n";
		
	$GLOBALS["MIRROR"]=$mirror;
	
	if($GLOBALS["MIRROR"]==null){
		if($progress){updatev2_progress(110,"Error, unable to find a suitable {repositories} [".__LINE__."]");}
		ufdbevents("error, unable to find a suitable mirror");
		squid_admin_mysql(0,"Unable to find a suitable mirror",null,__FUNCTION__,__LINE__);
		artica_update_event(0,"Unable to find a suitable mirror",null,__FUNCTION__,__LINE__);
		return null;
	}
	
	
	@unlink($destinationfile);
	@copy($tmpfile, $destinationfile);
	@copy($tmpfile,$InterfaceFile);
	@chmod($destinationfile,0755);
	
	if($ArticaDbReplicate){@file_put_contents($tmpfile, "/home/articatechdb.version");}
	if($progress){updatev2_progress(10,"{done} [".__LINE__."]");}
	
}

function updatev2_progress($num,$text){
	

	if($GLOBALS["VERBOSE"]){echo "{$num}% $text\n";}
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
	
	$line=intval($line);
	if($line>0){
		$text=$text. " ($function/$line)";
	}else{
		$text=$text. " ($function)";
	}
	
	
	$array["POURC"]=$num;
	$array["TEXT"]=$text." ".date("Y-m-d H:i:s");
	
	build_progress($text, $num);
	
	
	$unix->events($text,"/var/log/artica-ufdb.log",false,$function,$line,$file);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/cache/articatechdb.progress", serialize($array));
}
function updatev2_progress2($num,$text){
	$array["POURC"]=$num;
	$array["TEXT"]=$text." ".date("Y-m-d H:i:s");
	if($GLOBALS["VERBOSE"]){echo "{$num}% $text\n";}
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
	
	$unix->events($text,"/var/log/artica-ufdb.log",false,$function,$line,$file);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/cache/webfilter-artica.progress", serialize($array));
}
function tests_pub($pattern){
	$main_artica_path=$GLOBALS["WORKDIR_LOCAL"];
	$pubfinal="$main_artica_path/category_publicite/expressions";
	$f=explode("\n",@file_get_contents($pubfinal));
	while (list ($category_table, $num) = each ($f) ){
		if(preg_match("#$num#", $pattern)){
			echo $num." matches\n";
			break;
		}
		
	}
	
}

function updatev2_adblock(){
	return;
	if(isset($GLOBALS[__FUNCTION__])){return;}
	$GLOBALS[__FUNCTION__]=true;
	$timeFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$main_artica_path=$GLOBALS["WORKDIR_LOCAL"];
	$unix=new unix();
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){
		if($GLOBALS["VERBOSE"]){echo "License error...\n";}
		return;
	}
	
	if(!$GLOBALS["FORCE"]){
		$TimeMn=$unix->file_time_min($timeFile);
		if($TimeMn<60){
			if($GLOBALS["VERBOSE"]){echo "{$TimeMn}Mn require 60mn minimal (use --force if necessary)\n";}
			return;
		}
	}
	
	@unlink($timeFile);
	@file_put_contents($timeFile, time());
	
	updatev2_checkversion();
	$reload=false;
	
	$trackergzip="$main_artica_path/category_tracker/tracker_expressions.gz";
	$trackerfinal="$main_artica_path/category_tracker/expressions";
	
	$malwaregzip="$main_artica_path/category_malware/categoryuris_malware.gz";
	$malwarecsv="$main_artica_path/category_malware/categoryuris_malware.csv";
	
	
	$pubgzip="$main_artica_path/category_publicite/publicite_expressions.gz";
	$pubfinal="$main_artica_path/category_publicite/expressions";
	
	$phishgzip="$main_artica_path/category_phishing/categoryuris_phishing.gz";
	$phishcsv="$main_artica_path/category_phishing/categoryuris_phishing.csv";
	
	if($GLOBALS["MIRROR"]==null){return;}

	@unlink("$pubgzip");
	$curl=new ccurl("{$GLOBALS["MIRROR"]}/publicite_expressions.gz");
	if(!$curl->GetFile($pubgzip)){
		if($GLOBALS["VERBOSE"]){echo "$pubgzip failed to download $curl->error\n";}
		// ufdbguard_admin_events("UFDB::Fatal: $pubgzip failed to download $curl->error",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
		@unlink("$pubgzip");
		
	}
	
	@unlink($trackergzip);
	$curl=new ccurl("{$GLOBALS["MIRROR"]}/tracker_expressions.gz");
	if(!$curl->GetFile($trackergzip)){
		if($GLOBALS["VERBOSE"]){echo "$trackergzip failed to download $curl->error\n";}
		// ufdbguard_admin_events("UFDB::Fatal: $trackergzip failed to download $curl->error",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
		@unlink($trackergzip);
	}

	@unlink($malwaregzip);
	$curl=new ccurl("{$GLOBALS["MIRROR"]}/categoryuris_malware.gz");
	if(!$curl->GetFile($malwaregzip)){
		// ufdbguard_admin_events("UFDB::Fatal: $malwaregzip failed to download $curl->error",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
		if($GLOBALS["VERBOSE"]){echo "$malwaregzip failed to download $curl->error\n";}
		@unlink($malwaregzip);
	}	
	
	@unlink($phishgzip);
	$curl=new ccurl("{$GLOBALS["MIRROR"]}/categoryuris_phishing.gz");
	if(!$curl->GetFile($phishgzip)){
		// ufdbguard_admin_events("UFDB::Fatal: $phishgzip failed to download $curl->error",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
		if($GLOBALS["VERBOSE"]){echo "$phishgzip failed to download $curl->error\n";}
		@unlink($phishgzip);
	}	
	
	
	
	

	
	$mdfile1=md5_file($pubfinal);
	if($GLOBALS["VERBOSE"]){echo "$pubfinal($mdfile1)\n";}
	
	if(is_file($pubgzip)){
		$unix->uncompress($pubgzip, $pubfinal);
		$mdfile2=md5_file($pubfinal);
		if($GLOBALS["VERBOSE"]){echo "$pubfinal($mdfile2)\n";}
		if($mdfile2<>$mdfile1){$reload=true;}
	}else{
		if($GLOBALS["VERBOSE"]){echo "$pubgzip no such file\n";}
	}
	
	$mdfile1=md5_file($trackerfinal);
	if($GLOBALS["VERBOSE"]){echo "$trackerfinal -1- ($mdfile1)\n";}
	
	if(is_file($trackergzip)){
		$unix->uncompress($trackergzip, $trackerfinal);
		$mdfile2=md5_file($trackerfinal);
		if($GLOBALS["VERBOSE"]){echo "$trackerfinal -2- ($mdfile2)\n";}
		if($mdfile1<>$mdfile2){$reload=true;}
	}else{
		if($GLOBALS["VERBOSE"]){echo "$trackergzip no such file\n";}
	}

	
	if(is_file($malwaregzip)){
		$uris=array();
		$q=new mysql_squid_builder();
		$unix->uncompress($malwaregzip, $malwarecsv);
		$handle = @fopen($malwarecsv, "r");
		$q->CreateCategoryUrisTable("malware");
		if ($handle) {
			$line=@fgets($handle);
			$line=trim($line);
			if($line==null){continue;}
			$md5=md5($line);
			$date=date("Y-m-d H:i:s");
			$url=mysql_escape_string2($line);
			$uris[]="('$md5','$date','$url',1)";
		}
		
		if(count($uris)>0){
			$sql="INSERT IGNORE INTO categoryuris_malware
			(zmd5,zDate,pattern,enabled) VALUES ".@implode(",", $uris);
			$q->QUERY_SQL($sql);
		}
		
	}
	
	if(is_file($phishgzip)){
		$uris=array();
		$q=new mysql_squid_builder();
		$unix->uncompress($phishgzip, $phishcsv);
		$handle = @fopen($phishcsv, "r");
		$q->CreateCategoryUrisTable("phishing");
		if ($handle) {
			$line=@fgets($handle);
			$line=trim($line);
			if($line==null){continue;}
			$md5=md5($line);
			$date=date("Y-m-d H:i:s");
			$url=mysql_escape_string2($line);
			$uris[]="('$md5','$date','$url',1)";
		}
	
		if(count($uris)>0){
			$sql="INSERT IGNORE INTO categoryuris_phishing
			(zmd5,zDate,pattern,enabled) VALUES ".@implode(",", $uris);
			$q->QUERY_SQL($sql);
		}
	
	}	
	
	if($reload){
		
		squid_admin_mysql(2, "Ask to reload the Web filtering service","");
		shell_exec("/etc/init.d/ufdb reload");}
	
}

function updatev2_NAS(){
	$sock=new sockets();
	$unix=new unix();
	$t=time();
	$GLOBALS["TEMP_PATH"]=$unix->TEMP_DIR();
	$mount_point="{$GLOBALS["TEMP_PATH"]}/$t";
	$ArticaDBPath=$sock->GET_INFO("ArticaDBPath");
	if($ArticaDBPath==null){$ArticaDBPath="/opt/articatech";}
	$ArticaDBNasUpdt=unserialize(base64_decode($sock->GET_INFO("ArticaDBNasUpdt")));
	include_once(dirname(__FILE__)."/ressources/class.mount.inc");
	$mount=new mount();
	updatev2_progress(30,"{mouting} {$ArticaDBNasUpdt["hostname"]}...");
	$umount=$unix->find_program("umount");
	if(!$mount->smb_mount($mount_point, $ArticaDBNasUpdt["hostname"], $ArticaDBNasUpdt["username"], $ArticaDBNasUpdt["password"], $ArticaDBNasUpdt["folder"])){
		updatev2_progress(100,"{failed} to update Artica categories database trough NAS");
		squid_admin_mysql(1, "Unable to mount on {$ArticaDBNasUpdt["hostname"]}", @implode("\n", $GLOBALS["MOUNT_EVENTS"]));
		return false;
	}
	
	$filename=$ArticaDBNasUpdt["filename"];
	if(!is_file("$mount_point/$filename")){
		updatev2_progress(100,"{failed} {$ArticaDBNasUpdt["hostname"]}/{$ArticaDBNasUpdt["folder"]}/$filename no such file");
		squid_admin_mysql(1, "{failed} to update Artica categories database trough NAS","{$ArticaDBNasUpdt["hostname"]}/{$ArticaDBNasUpdt["folder"]}/$filename no such file");
		shell_exec("$umount -l $mount_point");
		return false;
	}
	
	$tar=$unix->find_program("tar");
	updatev2_progress(40,"{installing}...");
	if($GLOBALS["VERBOSE"]){echo "uncompressing $mount_point/$filename\n";}
	@mkdir($ArticaDBPath,0755,true);	
	updatev2_progress(50,"{stopping_service}...");
	shell_exec("/etc/init.d/artica-postfix stop articadb");
	updatev2_progress(60,"{extracting_package}...");
	shell_exec("$tar -xf  $mount_point/$filename -C $ArticaDBPath/");
	updatev2_progress(70,"{cleaning}...");
	$sock->SET_INFO("ManualArticaDBPathNAS", "0");
	shell_exec("$umount -l $mount_point");
	updatev2_progress(75,"{starting_service}...");
	if($GLOBALS["VERBOSE"]){echo "starting Articadb\n";}
	shell_exec("/etc/init.d/artica-postfix start articadb");
	updatev2_progress(80,"{checking}");
	$q=new mysql();
	if(!$q->DATABASE_EXISTS("catz")){
		updatev2_progress(85,"Removing old database catz");
		$q->DELETE_DATABASE("catz");
	}
	
	updatev2_progress(90,"{finish}");
	$took=$unix->distanceOfTimeInWords($t,time());
	$LOCAL_VERSION=@file_get_contents("$ArticaDBPath/VERSION");
	squid_admin_mysql(2, "New Artica Database statistics $LOCAL_VERSION updated took:$took","");
	artica_update_event(2,"New Artica Database statistics $LOCAL_VERSION updated took:$took",null,__FILE__,__LINE__,"ufbd-artica");
	updatev2_progress(100,"{done}");
	
	if($q->TABLE_EXISTS("catztemp")){$q->QUERY_SQL("DROP TABLE `catztemp`");}
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	
	
	return true;
	
}


function updatev2_manu(){
	$sock=new sockets();
	$unix=new unix();
	$t=time();
	$GLOBALS["TEMP_PATH"]=$unix->TEMP_DIR();	
	$ManualArticaDBPath=$sock->GET_INFO("ManualArticaDBPath");
	$ArticaDbReplicate=$sock->GET_INFO("ArticaDbReplicate");
	if($ManualArticaDBPath==null){$ManualArticaDBPath="/home/manualupdate/articadb.tar.gz";}
	$ArticaDBPath=$sock->GET_INFO("ArticaDBPath");
	if($ArticaDBPath==null){$ArticaDBPath="/opt/articatech";}
	if(!is_numeric($ArticaDbReplicate)){$ArticaDbReplicate=0;}
	
	$tar=$unix->find_program("tar");
	updatev2_progress(80,"{installing}...");
	if($GLOBALS["VERBOSE"]){echo "uncompressing $ManualArticaDBPath\n";}
	@mkdir($ArticaDBPath,0755,true);
	updatev2_progress(85,"{stopping_service}...");
	shell_exec("/etc/init.d/artica-postfix stop articadb");
	updatev2_progress(95,"{extracting_package}...");
	shell_exec("$tar -xf $ManualArticaDBPath -C $ArticaDBPath/");
	updatev2_progress(96,"{cleaning}...");
	if($ArticaDbReplicate==1){
		@copy("/usr/share/artica-postfix/ressources/logs/web/cache/CATZ_ARRAY","/home/articatechdb.version");
		@copy("$ManualArticaDBPath", "/home/articadb.tar.gz");
		@chmod("/home/articadb.tar.gz",0755);
		@chmod("/home/articatechdb.version",0755);
	}
	@unlink($ManualArticaDBPath);
	updatev2_progress(89,"{starting_service}...");
	if($GLOBALS["VERBOSE"]){echo "starting Articadb\n";}
	
	shell_exec("/etc/init.d/artica-postfix start articadb");
	updatev2_progress(90,"{checking}");
	
	$q=new mysql();
	if(!$q->DATABASE_EXISTS("catz")){
		updatev2_progress(95,"Removing old database catz");
		$q->DELETE_DATABASE("catz");
	}
	
	updatev2_progress(99,"{finish}");
	$took=$unix->distanceOfTimeInWords($t,time());
	$LOCAL_VERSION=@file_get_contents("$ArticaDBPath/VERSION");
	squid_admin_mysql(2, "New Artica Database statistics $LOCAL_VERSION updated took:$took","");
	// ufdbguard_admin_events("New Artica Database statistics $LOCAL_VERSION updated took:$took.",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
	updatev2_progress(100,"{done}");
	if($q->TABLE_EXISTS("catztemp")){
		$q->QUERY_SQL("DROP TABLE `catztemp`");
	}
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();

	
	return true;
}



function updatev2(){
	$sock=new sockets();
	$unix=new unix();
	$GLOBALS["TEMP_PATH"]=$unix->TEMP_DIR();
	updatev2_progress(10,"{checking} [".__LINE__."]");
	
	$timeFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$ArticaDbReplicate=$sock->GET_INFO("ArticaDbReplicate");
	$CategoriesDatabasesByCron=$sock->GET_INFO("CategoriesDatabaseByCron");
	if(!is_numeric($CategoriesDatabasesByCron)){$CategoriesDatabasesByCron=1;}
	
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	$CategoriesDatabasesUpdatesAllTimes=intval($sock->GET_INFO("CategoriesDatabasesUpdatesAllTimes"));
	$ArticaDBPath=$sock->GET_INFO("ArticaDBPath");
	if($ArticaDBPath==null){$ArticaDBPath="/opt/articatech";}
	$ManualArticaDBPath=$sock->GET_INFO("ManualArticaDBPath");
	if($ManualArticaDBPath==null){$ManualArticaDBPath="/home/manualupdate/articadb.tar.gz";}
	$ManualArticaDBPathNAS=$sock->GET_INFO("ManualArticaDBPathNAS");
	$EnableArticaMetaServer=intval($sock->GET_INFO("EnableArticaMetaServer"));
	$datas=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
	updatev2_progress(10,"{checking} [".__LINE__."]");
	if(!is_numeric($ManualArticaDBPathNAS)){$ManualArticaDBPathNAS=0;}
	
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	if(!is_numeric($ArticaDbReplicate)){$ArticaDbReplicate=0;}
	$WizardStatsAppliance=unserialize(base64_decode($sock->GET_INFO("WizardStatsAppliance")));
	if(!isset($WizardStatsAppliance["SERVER"])){$WizardStatsAppliance["SERVER"]=null;}
	
	if($EnableArticaMetaServer==0){
		if($DisableArticaProxyStatistics==1){
			updatev2_progress(110,"Error: Artica statistics are disabled");
		}
	}
	
	
	if($EnableArticaMetaServer==0){
		if($datas["UseRemoteUfdbguardService"]==1){
			updatev2_progress(110,"Error: - UseRemoteUfdbguardService -  Only used by {$WizardStatsAppliance["SERVER"]}");
			return;
		}
	}
	
	$CHECKTIME=$unix->file_time_min($timeFile);
	ufdbevents(" **");
	ufdbevents(" **");
	ufdbevents("$timeFile = {$CHECKTIME}Mn");
	ufdbevents(" **");
	ufdbevents(" **");
	
	
	if(!$GLOBALS["FORCE"]){
		if($CategoriesDatabasesByCron==1){
			if($EnableArticaMetaServer==0){
				if(!$GLOBALS["BYCRON"]){
					updatev2_progress(110,"Error: Only executed by schedule [".__LINE__."]");
					if($CHECKTIME>60){updatev2_checkversions();}
					return;
				}
			}
		}
		
		if($CategoriesDatabasesUpdatesAllTimes==0){
			if($EnableArticaMetaServer==0){
				if($unix->IsProductionTime()){
					webupdate_admin_mysql(2, "Update aborted, only allowed outside the production time", null,__FILE__,__LINE__);
					updatev2_progress(110,"Error: Only outside production time");
					if($CHECKTIME>60){updatev2_checkversions();}
					return;
				}
			}
		}
	}
	
	if($GLOBALS["FORCE"]){
			ufdbevents("***** Force enabled ***** ");
			ufdbevents("*****");
			ufdbevents("*****");
			ufdbevents("Executed as {$GLOBALS["CMDLINE"]}");
			ufdbevents("*****");
			ufdbevents("*****");
	
	}
	

	
	$MaxCheckTime=240;
	if($EnableArticaMetaServer==1){$MaxCheckTime=60;}
	
	if(!$GLOBALS["FORCE"]){
		if($CHECKTIME<240){
			updatev2_progress(110,"STOP: current {$CHECKTIME}Mn, require {$MaxCheckTime}mn");
			if($CHECKTIME>60){updatev2_checkversions();}
			return;
		}
	}
	
	updatev2_progress(10,"{checking} [".__LINE__."]");
	$pid=@file_get_contents($pidfile);
	
		if($unix->process_exists($pid,__FILE__)){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($time<10200){
				updatev2_progress(110,"Error: already running pid $pid since {$time}Mn");
				return;
			}
			else{
				$kill=$unix->find_program("kill");
				unix_system_kill_force($pid);
				if($GLOBALS["SCHEDULE_ID"]>0){
					artica_update_event(1, "Warning: Old task pid $pid since {$time}Mn has been killed, (reach 7200mn)", null,__FILE__,__LINE__);
				}
		}
	}
	
	updatev2_progress(10,"{checking} [".__LINE__."]");
	ufdbevents("Stamp $timeFile");
	@unlink($timeFile);
	$tlse_force_token=null;
	@file_put_contents($timeFile, time());	
	@file_put_contents($pidfile, getmypid());
	$tlse_token=null;
	if($GLOBALS["BYCRON"]){$tlse_token==" --bycron --force";}
	if($GLOBALS["FORCE"]){$tlse_force_token=" --force";}
	
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$PrivoxyEnabled=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/PrivoxyEnabled"));
	
	if($PrivoxyEnabled==1){
		@chmod("/usr/share/artica-postfix/bin/privoxy-blocklist.sh", 0755);
		system("$nohup /usr/share/artica-postfix/bin/privoxy-blocklist.sh >/dev/null 2>&1 &");
		
	}
	
	system("$nohup $php /usr/share/artica-postfix/exec.infected.urls.php >/dev/null 2>&1 &");
	
	$EnableArticaMetaClient=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaMetaClient"));
	$EnableArticaMetaServer=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaMetaServer"));
	if($EnableArticaMetaServer==1){$EnableArticaMetaClient=0;}
	
	if($EnableArticaMetaClient==1){
		updatev2_progress(10,"Using Meta console [".__LINE__."]");
		return ufdbtables_artica_meta();
	}
	
	

	
	
	updatev2_progress(10,"{checking} [".__LINE__."]");
	updatev2_checkversions();
	updatev2_progress(12,"{running} [".__LINE__."]");
	$GLOBALS["DOWNLOADED_INSTALLED"]=0;
	ufdbtables(true);
	tlsetables(true);
	ufdb_phistank();
	TranslateToMetaServer();
	
	if($GLOBALS["DOWNLOADED_INSTALLED"]>0){
		updatev2_progress(96,"{restarting_webfiltering_service} [".__LINE__."]");
		squid_admin_mysql(2,"{$GLOBALS["DOWNLOADED_INSTALLED"]} updated blacklists databases [action=restart]", __FILE__,__LINE__);
		system("/etc/init.d/ufdb restart --updater");
		system("/etc/init.d/ufdbcat restart --updater");
		$squidbin=$unix->LOCATE_SQUID_BIN();
		squid_admin_mysql(1,"Reloading proxy service after updating Web filtering databases", __FILE__,__LINE__);
		system("$squidbin -f /etc/squid3/squid.conf -k reconfigure");
	}
	
	if($GLOBALS["VERBOSE"]){echo " **************** C_ICAP_TABLES ***************\n";}
	C_ICAP_TABLES(true);
	if($GLOBALS["VERBOSE"]){echo " **************** schedulemaintenance ***************\n";}
	schedulemaintenance();
	if($GLOBALS["VERBOSE"]){echo " **************** EXECUTE_BLACK_INSTANCE ***************\n";}
	EXECUTE_BLACK_INSTANCE();
	if($GLOBALS["VERBOSE"]){echo " **************** FINISH ***************\n";}
	updatev2_progress(100,"{done}");
}

function events($text){
	$pid=@getmypid();
	$filename=basename(__FILE__);
	$date=@date("H:i:s");
	$logFile="/var/log/artica-postfix/updatev2.debug";
	$size=@filesize($logFile);
	if($size>1000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$pid [$date] ".basename(__FILE__)." $text\n");
	@fclose($f);

}

function schedulemaintenance(){
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__." in verbose mode\n";}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		WriteMyLogs("Warning: Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);
		return;
	}	
	
	if(!$GLOBALS["VERBOSE"]){
		$cachetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
		$time=$unix->file_time_min($cachetime);
		if($time<20){WriteMyLogs("$cachetime: {$time}Mn need 20Mn",__FUNCTION__,__FILE__,__LINE__);	return;}
		@unlink($cachetime);
		@file_put_contents($cachetime, time());
		
	}
	
	
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if($EnableRemoteStatisticsAppliance==1){writelogsBLKS("EnableRemoteStatisticsAppliance ACTIVE ,ABORTING TASK",__FUNCTION__,__FILE__,__LINE__);return;}	
	
	$t1=time();
	
	$q=new mysql_squid_builder();
	$badDomains["com"]=true;
	$badDomains["fr"]=true;
	$badDomains["de"]=true;
	$badDomains["nl"]=true;
	$badDomains["org"]=true;
	$badDomains["co"]=true;
	$badDomains["cz"]=true;
	$badDomains["de"]=true;
	$badDomains["net"]=true;
	$badDomains["us"]=true;
	$badDomains["name"]=true;
	$badDomains["il"]=true;
		
	$tables=$q->LIST_TABLES_CATEGORIES();
	while (list ($table,$none0) = each ($tables) ){
		if($table==null){continue;}
		if(strpos($table, ",")>0){$q->QUERY_SQL("DROP table `$table`"); continue;}
		if(blacklisted_tables($table)){continue;}
		if(!$q->TABLE_EXISTS($table)){continue;}
		
		reset($badDomains);
		while (list ($extensions,$none) = each ($badDomains) ){
		$q->QUERY_SQL("DELETE FROM $table WHERE pattern='$extensions'");	
		}
	}	
}


function blacklisted_tables($tablename){
	$array["category_stockexchnage"]=true;
	$array["category_association"]=true;
	$array["category_smalladds"]=true;
	$array["category_housing_reale_state"]=true;
	if(strpos($tablename, ",")>0){return true;}
	if(isset($array[$tablename])){return true;}
	
}
function build_progress($text,$pourc){
	WriteMyLogs("{$pourc}% $text",__FUNCTION__,__FILE__,__LINE__);
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/artica-webfilterdb.progress";
	echo "[{$pourc}%] $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
	if($GLOBALS["OUTPUT"]){usleep(500);}


}

function EXECUTE_BLACK_INSTANCE(){
	$unix=new unix();
	$schedule=null;
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	if(isset($GLOBALS["SCHEDULE_ID"])){
		if($GLOBALS["SCHEDULE_ID"]>0){
			$schedule="--schedule-id={$GLOBALS["SCHEDULE_ID"]} ";
		}
	}
	
	
	
	if($GLOBALS["BYCRON"]){
		$schedule=$schedule."--bycron ";
	}
	
	
	
}






function categorize_delete(){
	$unix=new unix();
	$URIBASE=$unix->MAIN_URI();
	$tmpdir=$unix->TEMP_DIR();
	if(!is_file("$tmpdir/categorize_delete.sql")){
	$curl=new ccurl("$URIBASE/blacklist/categorize_delete.gz");
	if(!$curl->GetFile("$tmpdir/categorize_delete.gz")){
		// ufdbguard_admin_events("Fatal: unable to download categorize_delete.gz file $curl->error",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
		return;
	}

	if(!extractGZ("$tmpdir/categorize_delete.gz","$tmpdir/categorize_delete.sql")){
			// ufdbguard_admin_events("Fatal: unable to extract $tmpdir/categorize_delete.gz",__FUNCTION__,__FILE__,__LINE__,"ufbd-artica");
			return;
		}
		
	}
	$q=new mysql_squid_builder();
	$datas=explode("\n",@file_get_contents("$tmpdir/categorize_delete.sql"));
	while (list ($index, $row) = each ($datas) ){
		if(trim($row)==null){continue;}
		$ligne=unserialize($row);
		$category=$ligne["category"];
		$pattern=$ligne["sitename"];
		$tablename="category_".$q->category_transform_name($category);
		if(!$q->TABLE_EXISTS($tablename)){$q->CreateCategoryTable($category);}
		$q->QUERY_SQL("UPDATE $tablename SET enabled=0 WHERE `pattern`='$pattern'");
		if(!$q->ok){
			echo $q->mysql_error."\n";
		}
	}
	
	
	@unlink("$tmpdir/categorize_delete.sql");
}

function GetLastUpdateDate(){
	$q=new mysql();
	$sql="SELECT zDate FROM updates_categories WHERE categories='settings'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	return $ligne["zDate"];
}



function ifMustBeExecuted2(){
	$users=new usersMenus();
	$sock=new sockets();
	$update=true;
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$CategoriesRepositoryEnable=$sock->GET_INFO("CategoriesRepositoryEnable");
	$AsCategoriesAppliance=intval($sock->GET_INFO("AsCategoriesAppliance"));
	if(!is_numeric($CategoriesRepositoryEnable)){$CategoriesRepositoryEnable=0;}
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	
	
	if(is_file("/etc/artica-postfix/STATS_APPLIANCE")){$AsCategoriesAppliance=1;}
	
	
	if($GLOBALS["VERBOSE"]){echo "AsCategoriesAppliance.......: $AsCategoriesAppliance\n";}
	if($GLOBALS["VERBOSE"]){echo "CategoriesRepositoryEnable..: $CategoriesRepositoryEnable\n";}
	if($GLOBALS["VERBOSE"]){echo "EnableWebProxyStatsAppliance: $EnableWebProxyStatsAppliance\n";}
	if($GLOBALS["VERBOSE"]){echo "SQUID_INSTALLED.............: $users->SQUID_INSTALLED\n";}
	
	if($AsCategoriesAppliance==1){return true;}

	if($EnableWebProxyStatsAppliance==1){return true;}	
	if($CategoriesRepositoryEnable==1){return true;}
	if(!$users->SQUID_INSTALLED){$update=false;}
	return $update;
}


function CheckTargetFile($filename,$requiredsize){
	if(!is_file($filename)){return false;}
	$size=filesize($filename);
	if($size<>$requiredsize){return false;}
	return true;
}

function writelogsBLKS($text,$function,$file,$line){
	WriteMyLogs($text,$function,$file,$line);
	// ufdbguard_admin_events($text,$function,basename(__FILE__),$line);
	}



function extractGZ($srcName, $dstName){
    $sfp = gzopen($srcName, "rb");
    $fp = fopen($dstName, "w");

    while ($string = gzread($sfp, 4096)) {
        fwrite($fp, $string, strlen($string));
    }
    gzclose($sfp);
    fclose($fp);
    $size=@filesize($dstName);
    if($size>0){
    	WriteMyLogs("TASK:{$GLOBALS["SCHEDULE_ID"]} -> extractGZ($srcName, $dstName) = $size bytes OK",__FUNCTION__,__FILE__,__LINE__);
    	return true;
    }
    WriteMyLogs("TASK:{$GLOBALS["SCHEDULE_ID"]} -> extractGZ($srcName, $dstName) = $size bytes FAILED",__FUNCTION__,__FILE__,__LINE__);
    return false;
}

function getSystemLoad(){
	$array_load=sys_getloadavg();
	return $array_load[0];
	
}



function CategoriesCountCache(){return;}

function export_table($tablename){
	if($GLOBALS["VERBOSE"]){echo "Exporting $tablename\n";}
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM $tablename";
	$results=$q->QUERY_SQL($sql);
	$unix=new unix();
	$tmpdir=$unix->TEMP_DIR();
	$fh = fopen("$tmpdir/$tablename.sql", 'w+');
	
	$c=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if($ligne["category"]==null){continue;}
			if($ligne["pattern"]==null){continue;}
			if($ligne["zmd5"]==null){continue;}
			$c++;
			$line="('{$ligne["zmd5"]}','{$ligne["zDate"]}','{$ligne["category"]}','{$ligne["pattern"]}','{$ligne["uuid"]}',1,1)";
			fwrite($fh, $line."\n");
		}
		
		echo "close $tmpdir/$tablename.sql $c rows\n";
		fwrite($fh, @implode(",",$f));
		fclose($fh);	
	
	
}

function export_all_tables(){
	$q=new mysql_squid_builder();
	$tables=$q->LIST_TABLES_CATEGORIES();
	while (list ($table, $row) = each ($tables) ){
		export_table($table);
	}
}

function merge_table($fromtable,$totable){
	
	$sock=new sockets();
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	if($DisableArticaProxyStatistics==1){die();}	
	
	$prefix="INSERT IGNORE INTO $totable (zmd5,zDate,category,pattern,uuid,sended,enabled) VALUES ";	
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM $fromtable";
	$results=$q->QUERY_SQL($sql);
	$c=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$line="('{$ligne["zmd5"]}','{$ligne["zDate"]}','{$ligne["category"]}','{$ligne["pattern"]}','{$ligne["uuid"]}',1,1)";
		$f[]=$line;
		if(count($f)>500){
			$c=$c+count($f);
			echo "Inserted $c elements\n";
			$sql="$prefix".@implode(",",$f);
			$f=array();
			$q->QUERY_SQL($sql);
			if(!$q->ok){echo $q->mysql_error."\n";return;}
		}
	}
	
		if(count($f)>0){
			$c=$c+count($f);
			echo "Inserted $c elements\n";
			$sql="$prefix".@implode(",",$f);
			$f=array();
			$q->QUERY_SQL($sql);
			if(!$q->ok){echo $q->mysql_error."\n";return;}
		}	
		
	echo "Finish\n";
	$sql="DROP TABLE $fromtable";
	$q->QUERY_SQL($sql);
	
}

function __GetMemory(){
	$mem=round(((memory_get_usage()/1024)/1000),2);
	return $mem;
}


function WriteMyLogs($text,$function,$file,$line){
	$GLOBALS["MAILLOG"][]=$line.") $text";
	$mem=__GetMemory();
	writelogs("Task:{$GLOBALS["SCHEDULE_ID"]}::$text",$function,__FILE__,$line);
	$logFile="/var/log/webfiltering-update.log";
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
   	if (is_file($logFile)) { 
   		$size=filesize($logFile);
   		if($size>9000000){unlink($logFile);}
   	}
   	$date=date('m-d H:i:s');
	$logFile=str_replace("//","/",$logFile);
	$f = @fopen($logFile, 'a');
	if($GLOBALS["VERBOSE"]){echo "$date [{$GLOBALS["MYPID"]}][{$mem}MB]: [$function::$line] $text\n";}
	@fwrite($f, "$date [{$GLOBALS["MYPID"]}][{$mem}MB]: [$function::$line] $text\n");
	@fclose($f);
}



function ufdbguard_admin_memory($text,$function,$file,$line,$none){
	if($GLOBALS["VERBOSE"]){echo "$function:: $text in line: $line\n";}
	$GLOBALS["ufdbguard_admin_memory"][]="$function:: $text in line: $line";
	
}

function ufdbguard_admin_compile(){
	return @implode("\n", $GLOBALS["ufdbguard_admin_memory"]);
	$GLOBALS["ufdbguard_admin_memory"]=array();
}
?>				