<?php
$GLOBALS["BYCRON"]=false;
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
$GLOBALS["FORCE"]=false;
$GLOBALS["OUTPUT"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--bycron#",implode(" ",$argv))){$GLOBALS["BYCRON"]=true;}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--output#",implode(" ",$argv),$re)){$GLOBALS["OUTPUT"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.squidguard.inc');
include_once(dirname(__FILE__).'/ressources/class.compile.ufdbguard.inc');
include_once(dirname(__FILE__).'/ressources/class.artica-meta.inc');

$unix=new unix();
if(is_file("/etc/artica-postfix/FROM_ISO")){
	if($unix->file_time_min("/etc/artica-postfix/FROM_ISO")<1){return;}
}


$GLOBALS["MYPID"]=getmypid();
if($argv[1]=="--ufdbcheck"){CoherenceRepertoiresUfdb();die();}
if($argv[1]=="--mysqlcheck"){CoherenceBase();die();}
if($argv[1]=="--localcheck"){CoherenceOffiels();die();}
if($argv[1]=="--compile"){compile();die();}
if($argv[1]=="--status"){BuildDatabaseStatus();die();}
if($argv[1]=="--refresh-index"){GET_MD5S_REMOTE();die();}
if($argv[1]=="--ptime"){ptime();die();}



Execute();

function ptime(){
	$unix=new unix();
	echo $unix->IsProductionTime()."\n";
}

function build_progress($text,$pourc){
	ufdbevents("{$pourc}% $text");
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/toulouse-unversity.progress";
	WriteMyLogs("{$pourc}% $text",__FUNCTION__,__FILE__,__LINE__);
	
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
	if($GLOBALS["OUTPUT"]){
		echo "[{$pourc}%] $text\n";
		sleep(2);}


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

	if($GLOBALS["OUTPUT"]){echo "$text [$line]\n";}

	$unix->events($text,"/var/log/artica-ufdb.log",false,$function,$line,$file);
}

function Execute(){
	
	build_progress("Executing",5);
	
	if(!ifMustBeExecuted()){ 
		if($GLOBALS["VERBOSE"]){
			echo "No make sense to execute this script...\n";
		}
		
		while (list ($filename,$line) = each ($GLOBALS["ifMustBeExecuted"]) ){
			ufdbevents("ifMustBeExecuted:: $line");
		}
		build_progress("No make sense to execute this script",110);
		die(); 
	}
	$timeFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();
	$StandardTime=240;
	$sock=new sockets();
	$kill=$unix->find_program("kill");
	
	
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,__FILE__)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($time>240){unix_system_kill_force($pid);}
	}
	if($unix->process_exists($pid,__FILE__)){return;}
	@file_put_contents($pidfile, getmypid());
	
	$CategoriesDatabasesByCron=$sock->GET_INFO("CategoriesDatabaseByCron");
	if(!is_numeric($CategoriesDatabasesByCron)){$CategoriesDatabasesByCron=1;}
	$CategoriesDatabasesUpdatesAllTimes=intval($sock->GET_INFO("CategoriesDatabasesUpdatesAllTimes"));
	
	if(!$GLOBALS["FORCE"]){
		if($CategoriesDatabasesByCron==1){
			if($GLOBALS["VERBOSE"]){echo "Execute():: Only bycron, aborting...\n";}
			if(!$GLOBALS["BYCRON"]){ 
				build_progress("Not executed by CRON.. Aborting",110);
				return; 
			}
		}
		
		if(!$GLOBALS["BYCRON"]){
			$timeFile=$unix->file_time_min($timeFile);
			if($timeFile<$StandardTime){
				build_progress("{$timeFile}mn < {$StandardTime}Mn, aborting...use --force ",110);
				if($GLOBALS["VERBOSE"]){echo "Execute():: {$timeFile}mn < {$StandardTime}Mn, aborting...use --force to bypass\n";}
				return;
			}
		}
		
		if($CategoriesDatabasesUpdatesAllTimes==0){
			if($unix->IsProductionTime()){
				webupdate_admin_mysql(2, "Update aborted, only allowed outside the production time", null,__FILE__,__LINE__);
				build_progress("Error: Only outside production time",110);
				return;
			}
		}
		
		
	}

	
	@unlink($timeFile);
	@file_put_contents($timeFile, time());
	

	$sock=new sockets();
	$EnableArticaMetaClient=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaMetaClient"));
	$EnableArticaMetaServer=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaMetaServer"));
	if($EnableArticaMetaServer==1){$EnableArticaMetaClient=0;}
	
	if($EnableArticaMetaClient==1){
		build_progress("Using Meta Server server",10);
		return artica_meta_client();
	}
	
	
	$BASE_URI="ftp://ftp.univ-tlse1.fr/pub/reseau/cache/squidguard_contrib";
	
	
	$myFile=basename(__FILE__);
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$cachetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".{$GLOBALS["SCHEDULE_ID"]}.time";
	$unix=new unix();	
	$ufdbGenTable=$unix->find_program("ufdbGenTable");
	$kill=$unix->find_program("kill");
	$pid=@file_get_contents($pidfile);
	$getmypid=$GLOBALS["MYPID"];
	if(!$GLOBALS["FORCE"]){
		if($unix->process_exists($pid,$myFile)){
			$timePid=$unix->PROCCESS_TIME_MIN($pid);
			if($timePid<60){
				build_progress("Already running PID $pid",110);
				die();
			}else{
				unix_system_kill_force($pid);
			}
		}
		
	}
	@file_put_contents($pidfile,$getmypid);
	
	if($GLOBALS["VERBOSE"]){echo "Executed pid $getmypid\n";}
	if($GLOBALS["VERBOSE"]){echo "ufdbGenTable:$ufdbGenTable\n";}
	$sock=new sockets();
	$SquidDatabasesUtlseEnable=$sock->GET_INFO("SquidDatabasesUtlseEnable");
	if(!is_numeric($SquidDatabasesUtlseEnable)){$SquidDatabasesUtlseEnable=1;}	
	
	if($SquidDatabasesUtlseEnable==0){
		build_progress("{database_disabled}",110);
		update_progress(100,"{database_disabled}");
		echo "Toulouse university is disabled\n";
		artica_update_event(2, "Toulouse university is disabled, aborting", null,__FILE__,__LINE__);
	}
	if(!$GLOBALS["FORCE"]){
		$time=$unix->file_time_min($cachetime);
		if($time<120){
			$q=new mysql_squid_builder();
			if($q->COUNT_ROWS("univtlse1fr")==0){BuildDatabaseStatus();}
			ufdbevents("$cachetime: {$time}Mn need 120Mn");
			echo "$cachetime: {$time}Mn need 120Mn\n";
			die();
		}
	}
	@unlink($cachetime);
	@file_put_contents($cachetime, time());
	
	
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL("SELECT * FROM ftpunivtlse1fr");
	if(!$q->ok){
		if(strpos($q->mysql_error, "doesn't exist")>0){$q->CheckTables();
		$results=$q->QUERY_SQL("SELECT * FROM ftpunivtlse1fr");}
	}
	
	
	if(!$q->ok){
		ufdbevents("Fatal: $q->mysql_error");
		ufdbguard_admin_events("Fatal: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"Toulouse DB");

	}
	
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		
		$ARRAYSUM_LOCALE[$ligne["filename"]]=$ligne["zmd5"];
		
	}
	
	$STATUS=unserialize(@file_get_contents("/etc/artica-postfix/TLSE_LAST_DOWNLOAD"));
	$STATUS["LAST_CHECK"]=time();
	@file_put_contents("/etc/artica-postfix/TLSE_LAST_DOWNLOAD", serialize($STATUS));
	
	if(!isset($GLOBALS["UFDB_COUNT_OF_DOWNLOADED"])){$GLOBALS["UFDB_COUNT_OF_DOWNLOADED"]=0;}
	build_progress("Check MD5",10);
	$ARRAYSUM_REMOTE=GET_MD5S_REMOTE();
	$TOT=count($ARRAYSUM_REMOTE);
	
	$c=0;
	$start=15;
	
	while (list ($filename,$md5) = each ($ARRAYSUM_REMOTE) ){
		$c++;
		$prc=round(($c/$TOT)*100);
		
		update_progress($c,$filename);
		if(!isset($ARRAYSUM_LOCALE[$filename])){$ARRAYSUM_LOCALE[$filename]=null;}
		if($ARRAYSUM_LOCALE[$filename]<>$md5){
			$size=FormatBytes($GLOBALS["UFDB_SIZE"]/1024);
			if($prc<15){ build_progress("Downloading $filename ($size)",15); $prclog=15;}
			if($prc>15){
				if($prc<80){ build_progress("Downloading $filename ($size)",$prc);$prclog=$prc; }
				if($prc>79){ build_progress("Downloading $filename ($size)",79);$prclog=79; }
			}
			update_remote_file($BASE_URI,$filename,$md5,$prclog);
		}
	}
	
	if(count($GLOBALS["squid_admin_mysql"])){
		$UFDB_SIZE=FormatBytes($GLOBALS["UFDB_SIZE"]/1024);
		build_progress(count($GLOBALS["squid_admin_mysql"])." downloaded items - $UFDB_SIZE",80);
		webupdate_admin_mysql(2, count($GLOBALS["squid_admin_mysql"])." downloaded items - $UFDB_SIZE - Webfiltering Toulouse Databases updated",
		@implode("\n", $GLOBALS["squid_admin_mysql"]),__FILE__,__LINE__);
		unset($GLOBALS["squid_admin_mysql"]);
	}
	
	build_progress("{done}",85);
	update_progress(100,"{done}");
	
	build_progress("CoherenceOffiels()",85);
	CoherenceOffiels();
	build_progress("CoherenceRepertoiresUfdb()",90);
	CoherenceRepertoiresUfdb();
	build_progress("BuildDatabaseStatus()",95);
	BuildDatabaseStatus();
	build_progress("remove_bad_files()",98);
	remove_bad_files();
	
	build_progress("{finish}",100);
	if($GLOBALS["UFDB_COUNT_OF_DOWNLOADED"]>0){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/ToulouseUniversityLastCheck", time());
		@chmod("/etc/artica-postfix/settings/Daemons/ToulouseUniversityLastCheck",0755);
		artica_meta_server(true);
		system("/etc/init.d/ufdbcat restart --force");
	
	}
	else{
		artica_meta_server();
	}
	
	
	
	$php5=$unix->LOCATE_PHP5_BIN();
	$ufdbConvertDB=$unix->find_program("ufdbConvertDB");
	if(is_file($ufdbConvertDB)){shell_exec("$ufdbConvertDB /var/lib/ftpunivtlse1fr");}
	
	if(is_dir("/var/lib/ftpunivtlse1fr")){
		$chown=$unix->find_program("chown");
		shell_exec("$chown squid:squid /var/lib/ftpunivtlse1fr");
		shell_exec("$chown -R squid:squid /var/lib/ftpunivtlse1fr/");
	
	}

	
	$unix->THREAD_COMMAND_SET("$php5 /usr/share/artica-postfix/exec.squidguard.php --disks");
	
	
	
}

function artica_meta_client($force=false){
	$unix=new unix();
	$WORKDIR="/var/lib/ftpunivtlse1fr";
	@mkdir($WORKDIR,0755,true);
	@chmod($WORKDIR, 0755);
	$tmpdir=$unix->TEMP_DIR();
	
	$myVersion=intval(trim(@file_get_contents("/etc/artica-postfix/ftpunivtlse1fr.txt")));
	$tmpdir=$unix->TEMP_DIR();
	$meta=new artica_meta();
	
	
	
	$curl=$meta->buildCurl("/meta-updates/webfiltering/ftpunivtlse1fr.txt");
	if(!$curl->GetFile("$tmpdir/ftpunivtlse1fr.txt")){
		
		artica_update_event(0, "Failed Downloading webfiltering/ftpunivtlse1fr.txt", @implode("\n",$curl->errors),__FILE__,__LINE__);
		$meta->events($curl->errors, __FUNCTION__,__FILE__,__LINE__);
		meta_admin_mysql(0, "Failed Downloading webfiltering/ftpunivtlse1fr.txt", @implode("\n",$curl->errors),__FILE__,__LINE__);
		return false;
	}

	
	$Remote_version=intval(trim(@file_get_contents("$tmpdir/ftpunivtlse1fr.txt")));
	@unlink("$tmpdir/ftpunivtlse1fr.txt");
	echo "Current............: $myVersion\n";
	echo "Available..........: $Remote_version\n";
	$datev=date("Y-m-d H:i:s",$myVersion);
	
	$STATUS=unserialize(@file_get_contents("/etc/artica-postfix/TLSE_LAST_DOWNLOAD"));
	$STATUS["LAST_CHECK"]=time();
	@file_put_contents("/etc/artica-postfix/TLSE_LAST_DOWNLOAD", serialize($STATUS));
	
	if($myVersion>$Remote_version){
			echo "My version $myVersion is newest than $Remote_version, aborting\n";
			build_progress("{version-up-to-date} $datev",100);
			
			return;}
	if($myVersion==$Remote_version){
		build_progress("{version-up-to-date} $datev",100);
		echo "My version $myVersion is the same than $Remote_version, aborting\n";
		return;
	}
	
	$curl=$meta->buildCurl("/meta-updates/webfiltering/ftpunivtlse1fr.tgz");
	$curl->Timeout=120;
	if(!$curl->GetFile("$tmpdir/ftpunivtlse1fr.tgz")){
		artica_update_event(0, "Failed Downloading webfiltering/ftpunivtlse1fr.tgz", @implode("\n",$curl->errors),__FILE__,__LINE__);
		$meta->events($curl->errors, __FUNCTION__,__FILE__,__LINE__);
		meta_admin_mysql(0, "Failed Downloading webfiltering/ftpunivtlse1fr.tgz", @implode("\n",$curl->errors),__FILE__,__LINE__);
		@unlink("$tmpdir/ftpunivtlse1fr.tgz");
		return false;
	}
	
	if(!$unix->TARGZ_TEST_CONTAINER("$tmpdir/ftpunivtlse1fr.tgz")){
		artica_update_event(0, "Failed $tmpdir/ftpunivtlse1fr.tgz corrupted package", @implode("\n",$curl->errors),__FILE__,__LINE__);
		meta_admin_mysql(0, "Failed $tmpdir/ftpunivtlse1fr.tgz corrupted package", @implode("\n",$curl->errors),__FILE__,__LINE__);
		@unlink("$tmpdir/ftpunivtlse1fr.tgz");
		return false;
	}
	
	$tar=$unix->find_program("tar");
	shell_exec("$tar -xf $tmpdir/ftpunivtlse1fr.tgz -C $WORKDIR/");
	@unlink("$tmpdir/ftpunivtlse1fr.tgz");
	artica_update_event(0, "Success update categories statistics v.$Remote_version", @implode("\n",$curl->errors),__FILE__,__LINE__);
	meta_admin_mysql(0, "Success update categories statistics v.$Remote_version", @implode("\n",$curl->errors),__FILE__,__LINE__);	
	@file_put_contents("/etc/artica-postfix/ftpunivtlse1fr.txt", $Remote_version);
	build_progress("Using Meta Server server {done}",100);
	CoherenceOffiels();
	CoherenceRepertoiresUfdb();
	BuildDatabaseStatus();
	remove_bad_files();
	
}


function artica_meta_server($force=false){
	$WORKDIR="/var/lib/ftpunivtlse1fr";
	$sock=new sockets();
	$unix=new unix();
	$EnableArticaMetaServer=intval($sock->GET_INFO("EnableArticaMetaServer"));
	if($EnableArticaMetaServer==0){return;}
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	@mkdir("$ArticaMetaStorage/nightlys",0755,true);
	@mkdir("$ArticaMetaStorage/releases",0755,true);
	@mkdir("$ArticaMetaStorage/webfiltering",0755,true);
	$srcdir=$WORKDIR;
	$destfile="$ArticaMetaStorage/webfiltering/ftpunivtlse1fr.tgz";
	if(is_file($destfile)){if(!$force){return;}}
	$tar=$unix->find_program("tar");
	@unlink($destfile);
	chdir($srcdir);
	shell_exec("$tar czf $destfile *");
	@unlink("$ArticaMetaStorage/webfiltering/ftpunivtlse1fr.txt");
	@file_put_contents("$ArticaMetaStorage/webfiltering/ftpunivtlse1fr.txt", time());
	artica_update_event(2, "Toulouse University categories: Success update Meta Server webfiltering repository", @implode("\n", $GLOBALS["EVENTS"]),__FILE__,__LINE__);
	meta_admin_mysql(2, "Success update Toulouse University categories webfiltering repository", null,__FILE__,__LINE__);
}

function update_progress($num,$text){
	$array["POURC"]=$num;
	$array["TEXT"]=$text." ".date("Y-m-d H:i:s");
	if($GLOBALS["VERBOSE"]){echo "{$num}% $text\n";}
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/cache/toulouse.progress", serialize($array));
}

function BuildDatabaseStatus(){
	
	if(!ifMustBeExecuted()){
		
		WriteMyLogs("No make sense to execute this script...",__FUNCTION__,__FILE__,__LINE__);
		if($GLOBALS["VERBOSE"]){echo "No make sense to execute this script...\n";}die();
	}
	
	$q=new mysql_squid_builder();
	$unix=new unix();
	$dirs=$unix->dirdir("/var/lib/ftpunivtlse1fr");
	
	$TLSE_CONVERTION=$q->TLSE_CONVERTION();
	while (list ($directory, $line) = each ($dirs) ){
		
		$catzname=$TLSE_CONVERTION[basename($directory)];
		if($catzname==null){continue;}
		if(!is_file("$directory/domains")){
			if($GLOBALS["VERBOSE"]){echo "$catzname=0\n";}
			$f[$catzname]["F"]=0;
			$f[$catzname]["D"]="0000-00-00 00:00:00";
			$f[$catzname]["FF"]="$directory/domains";
		}else{
			$f[$catzname]["F"]=$unix->COUNT_LINES_OF_FILE("$directory/domains");
			if($GLOBALS["VERBOSE"]){echo "$catzname={$f[$catzname]["F"]}\n";}
			$f[$catzname]["D"]=date("Y-m-d H:i:s",filemtime("$directory/domains"));
			$f[$catzname]["FF"]="$directory/domains";
		}
		
		
		
	}
	
	$LastC=trim(@file_get_contents("/etc/artica-postfix/ftpunivtlse1frCount"));
	$c=0;
	while (list ($cat, $array) = each ($f) ){
		$count=$array["F"];
		$date=$array["D"];
		$c=$c+$count;
		$sql[]="('$cat','$count','$date')";
	}
	
	if(count($sql)>0){
		$q->QUERY_SQL("TRUNCATE TABLE univtlse1fr");
		$q->QUERY_SQL("INSERT IGNORE INTO univtlse1fr (category,websitesnum,zDate) VALUES ".@implode(",", $sql));
		if(!$q->ok){
			artica_update_event(2,"Fatal $q->mysql_error",null,__FILE__,__LINE__);
			return;
		}
		if($c<>$LastC){
			//artica_update_event("Toulouse University status: $c items in database",__FUNCTION__,__FILE__,__LINE__);
			@file_put_contents("/etc/artica-postfix/ftpunivtlse1frCount", $c);
		}
		
	}
	//univtlse1fr
	
	
	
}



function GET_MD5S_REMOTE(){
	
	if(!ifMustBeExecuted()){
		$GLOBALS["LOGS"][]="ifMustBeExecuted():: No make sense to execute this script.";
		WriteMyLogs("ifMustBeExecuted():: No make sense to execute this script...",__FUNCTION__,__FILE__,__LINE__);
		if($GLOBALS["VERBOSE"]){echo "No make sense to execute this script...\n";}die();
	}
	
	$unix=new unix();
	$BASE_URI="ftp://ftp.univ-tlse1.fr/pub/reseau/cache/squidguard_contrib";
	$indexuri="$BASE_URI/MD5SUM.LST";
	$cache_temp=$unix->FILE_TEMP();	
	$curl=new ccurl($indexuri);
	build_progress("Downloading $indexuri",11);
	WriteMyLogs("Downloading $indexuri",__FUNCTION__,__FILE__,__LINE__);
	$curl->Timeout=320;
	if(!$curl->GetFile($cache_temp)){
		build_progress("{failed} $curl->error",110);
		$errorDetails=@implode("\n", $GLOBALS["CURLDEBUG"]);
		WriteMyLogs("Fatal error downloading $indexuri $curl->error\n$errorDetails",__FUNCTION__,__FILE__,__LINE__);
		artica_update_event(0, "Web filtering databases, unable to download index file", "Fatal error downloading $indexuri $curl->error\n$errorDetails",__FILE__,__LINE__);
		die();
	}
	
	$indexuri="$BASE_URI/global_usage";
	$curl=new ccurl($indexuri);
	build_progress("Downloading $indexuri",12);
	if(!$curl->GetFile("/etc/artica-postfix/univtoulouse-global_usage")){
		$errorDetails=@implode("\n", $GLOBALS["CURLDEBUG"]);
		WriteMyLogs("Fatal error downloading $indexuri $curl->error\n$errorDetails",__FUNCTION__,__FILE__,__LINE__);
		artica_update_event(0, "Web filtering databases, unable to download global_usage file", "Fatal error downloading $indexuri $curl->error\n$errorDetails",__FILE__,__LINE__);
		
	}
	
	$f=explode("\n",@file_get_contents($cache_temp));
	while (list ($index, $line) = each ($f) ){
		if(trim($line)==null){continue;}
		if(!preg_match("#^([a-z0-9]+)\s+([a-z0-9\.]+)$#", $line,$re)){continue;}
		$md5=$re[1];
		$filename=$re[2];
		if($filename=="blacklists.tar.gz"){continue;}
		if($filename=="domains.tar.gz"){continue;}
		if($filename=="MD5SUM.LST"){continue;}
		$ARRAYSUM_REMOTE[$filename]=$md5;
	}	
	return $ARRAYSUM_REMOTE;
}


function update_remote_file($BASE_URI,$filename,$md5,$prc){
	if(!isset($GLOBALS["UFDB_SIZE"])){$GLOBALS["UFDB_SIZE"]=0;}
	WriteMyLogs("update_remote_file($BASE_URI,$filename,$md5)",__FUNCTION__,__FILE__,__LINE__);
	$STATUS=unserialize(@file_get_contents("/etc/artica-postfix/TLSE_LAST_DOWNLOAD"));
	$indexuri="$BASE_URI/$filename";
	$unix=new unix();
	$q=new mysql_squid_builder();
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$ln=$unix->find_program("ln");
	$ufdbGenTable=$unix->find_program("ufdbGenTable");
	$Conversion=$q->TLSE_CONVERTION();
	$ufdb=new compile_ufdbguard();
	$curl=new ccurl($indexuri);
	$curl->Timeout=360;
	
	
	
	echo "Downloading $indexuri\n";
	$cache_temp="/tmp/$filename";
	
	
	
	if(!$curl->GetFile($cache_temp)){
		build_progress("Fatal error downloading $indexuri $curl->error",$prc);
		echo "Fatal error downloading $indexuri $curl->error\n";
		$errorDetails=@implode("\n", $GLOBALS["CURLDEBUG"]);
		
		webupdate_admin_mysql(0, "Web filtering databases, unable to download $indexuri", "Fatal error downloading $indexuri $curl->error\n$errorDetails",__FILE__,__LINE__);
		artica_update_event(0, "Web filtering databases, unable to download $indexuri", "Fatal error downloading $indexuri $curl->error\n$errorDetails",__FILE__,__LINE__);
		webupdate_admin_mysql(1, "Unable to download $indexuri", "Fatal error downloading $indexuri $curl->error\n$errorDetails",__FILE__,__LINE__);
		return;
	}
	
	$filesize=$unix->file_size($cache_temp);
	$GLOBALS["UFDB_SIZE"]=$GLOBALS["UFDB_SIZE"]+$filesize;
	
	
	@mkdir("/var/lib/ftpunivtlse1fr",755,true);
	$categoryname=str_replace(".tar.gz", "", $filename);
	$categoryDISK=$categoryname;
	if(isset($Conversion[$categoryname])){$categoryDISK=$Conversion[$categoryname];}
	$STATUS["LAST_DOWNLOAD"]["TIME"]=time();
	$STATUS["LAST_DOWNLOAD"]["CATEGORY"]=$categoryname;
	$STATUS["LAST_DOWNLOAD"]["SIZE"]=($GLOBALS["CURL_LAST_SIZE_DOWNLOAD"]/1024);
	@file_put_contents("/etc/artica-postfix/TLSE_LAST_DOWNLOAD", serialize($STATUS));
	
	$categoryDISK=str_replace("/", "_", $categoryDISK);
	
	if(is_link("/var/lib/ftpunivtlse1fr/$categoryname")){
		if($GLOBALS["VERBOSE"]){echo "/var/lib/ftpunivtlse1fr/$categoryname is a link of ". @readlink("/var/lib/ftpunivtlse1fr/$categoryname")."\n";}
		if($GLOBALS["VERBOSE"]){echo "Removing  /var/lib/ftpunivtlse1fr/$categoryname/\n";}
		shell_exec("$rm -rf /var/lib/ftpunivtlse1fr/$categoryname");
	}
	
	
	if(is_dir("/var/lib/ftpunivtlse1fr/$categoryname")){
		if($GLOBALS["VERBOSE"]){echo "Removing  /var/lib/ftpunivtlse1fr/$categoryname/\n";}
		shell_exec("$rm -rf /var/lib/ftpunivtlse1fr/$categoryname");
	}
	if($GLOBALS["VERBOSE"]){echo "Creating  /var/lib/ftpunivtlse1fr/$categoryname/\n";}
	@mkdir("/var/lib/ftpunivtlse1fr/$categoryname",0755,true);
	
	if($GLOBALS["VERBOSE"]){echo "Extracting $cache_temp to  /var/lib/ftpunivtlse1fr/\n";}
	
	shell_exec("$tar -xf $cache_temp -C /var/lib/ftpunivtlse1fr/");
	if(!is_file("/var/lib/ftpunivtlse1fr/$categoryname/domains")){
		build_progress("Fatal!!: $categoryname/domains no such file",$prc);
		webupdate_admin_mysql(1, "$categoryname/domains no such file", null,__FILE__,__LINE__);
		ufdbevents("Fatal!!: /var/lib/ftpunivtlse1fr/$categoryname/domains no such file",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	$CountDeSitesFile=CountDeSitesFile("/var/lib/ftpunivtlse1fr/$categoryname/domains");
	if($GLOBALS["VERBOSE"]){echo "/var/lib/ftpunivtlse1fr/$categoryname/domains -> $CountDeSitesFile websites\n";}
	if($CountDeSitesFile==0){
		build_progress("Fatal!!: $categoryname/domains corrupted, no website",$prc);
		webupdate_admin_mysql(1, "$categoryname/domains corrupted, no website", null,__FILE__,__LINE__);
		ufdbevents("Fatal!!: /var/lib/ftpunivtlse1fr/$categoryname/domains corrupted, no website",__FUNCTION__,__FILE__,__LINE__,"Toulouse DB");
		shell_exec("$rm -rf /var/lib/ftpunivtlse1fr/$categoryname");
		return;		
	}
	
	if(trim(strtolower($categoryDISK))<>trim(strtolower($categoryname))){
		if(is_dir("/var/lib/ftpunivtlse1fr/$categoryDISK")){shell_exec("$rm -rf /var/lib/ftpunivtlse1fr/$categoryDISK");}
		shell_exec("ln -sf /var/lib/ftpunivtlse1fr/$categoryDISK /var/lib/ftpunivtlse1fr/$categoryname");
	}
	
	
	$q->QUERY_SQL("DELETE FROM ftpunivtlse1fr WHERE filename='$filename'");
	if(!$q->ok){
		webupdate_admin_mysql(1, "MySQL error", $q->mysql_error,__FILE__,__LINE__);
		ufdbevents("Fatal!!: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"Toulouse DB");
		return;
	}
	$q->QUERY_SQL("INSERT INTO ftpunivtlse1fr (`filename`,`zmd5`,`websitesnum`) VALUES ('$filename','$md5','$CountDeSitesFile')");
	if(!$q->ok){
		ufdbevents("Fatal!!: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"Toulouse DB");
		webupdate_admin_mysql(1, "MySQL error", $q->mysql_error,__FILE__,__LINE__);
		return;
	}
	
	
	$GLOBALS["UFDB_COUNT_OF_DOWNLOADED"]=$GLOBALS["UFDB_COUNT_OF_DOWNLOADED"]+1;
	build_progress("$categoryname $CountDeSitesFile websites",$prc);
	$filesizeK=FormatBytes($filesize/1024);
	$GLOBALS["squid_admin_mysql"][]="Success updating category `$categoryname` with $CountDeSitesFile websites $filesizeK";
	if($GLOBALS["VERBOSE"]){echo "ufdbGenTable=$ufdbGenTable\n";}
	webupdate_admin_mysql(2, "Success downloading $categoryname $CountDeSitesFile websites $filesizeK", null,__FILE__,__LINE__);

	
	
	
	
	if(is_file($ufdbGenTable)){
		$t=time();
		ufdbevents("Compiling /var/lib/ftpunivtlse1fr/$categoryname");
		build_progress("$categoryname Compiling....",$prc);
		$ufdb->UfdbGenTable("/var/lib/ftpunivtlse1fr/$categoryname",$categoryname);
	}
	
	
}

function remove_bad_files(){

	$unix=new unix();

	$dirs=$unix->dirdir("/var/lib/ftpunivtlse1fr");
	while (list ($directory, $b) = each ($dirs)){
		$dirname=basename($directory);
		if(is_link("$directory/$dirname")){
			echo "Starting......: ".date("H:i:s")." UfdBguard removing $dirname/$dirname bad file\n";
			@unlink("$directory/$dirname");
		}
	}


	echo "Starting......: ".date("H:i:s")." UfdBguard removing bad files done...\n";
}

function compile(){
	
	if(!ifMustBeExecuted()){
		
		WriteMyLogs("No make sense to execute this script...",__FUNCTION__,__FILE__,__LINE__);
		if($GLOBALS["VERBOSE"]){echo "No make sense to execute this script...\n";}die();
	}
	
	$ufdb=new compile_ufdbguard();
	$q=new mysql_squid_builder();
	$unix=new unix();
	
	$t=time();
	$myFile=basename(__FILE__);
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();	
	$ufdbGenTable=$unix->find_program("ufdbGenTable");
	if(!is_file($ufdbGenTable)){return;}
	
	
	$pid=@file_get_contents($pidfile);
	$getmypid=$GLOBALS["MYPID"];
	if(!$GLOBALS["FORCE"]){
		if($unix->process_exists($pid,$myFile)){
			$timePid=$unix->PROCCESS_TIME_MIN($pid);
			ufdbguard_admin_events("Already executed PID:$pid, since {$timePid}Mn die() ",__FUNCTION__,__FILE__,__LINE__,"Toulouse DB");
			die();
		}
	}	
	
	
	$Conversion=$q->TLSE_CONVERTION();
	$workdir="/var/lib/ftpunivtlse1fr";
	$c=0;
	while (list ($directory, $line) = each ($Conversion) ){
		$c++;
		$ufdb->UfdbGenTable("$workdir/$directory",$directory);	
		$unix->chown_func("squid", "squid","$workdir/$directory");
	}
	
	ufdbguard_admin_events("Compiling $c databases done, took:".$unix->distanceOfTimeInWords($t,time(),true),__FUNCTION__,__FILE__,__LINE__,"Toulouse DB");
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php5 /usr/share/artica-postfix/exec.squidguard.php --build schedule-id={$GLOBALS["SCHEDULE_ID"]}");
}

function CoherenceRepertoiresUfdb(){
	
	if(!ifMustBeExecuted()){
		
		WriteMyLogs("No make sense to execute this script...",__FUNCTION__,__FILE__,__LINE__);
		if($GLOBALS["VERBOSE"]){echo "No make sense to execute this script...\n";}die();
	}
	
	$unix=new unix();
	$q=new mysql_squid_builder();
	$BASE_URI="ftp://ftp.univ-tlse1.fr/pub/reseau/cache/squidguard_contrib";
	$ufdbGenTable=$unix->find_program("ufdbGenTable");
	if(!is_file($ufdbGenTable)){return;}
	$ufdb=new compile_ufdbguard();
	$rm=$unix->find_program("rm");
	$dirs=$unix->dirdir("/var/lib/ftpunivtlse1fr");
	$Conversion=$q->TLSE_CONVERTION();
	while (list ($directory, $line) = each ($dirs) ){
		$database=basename($directory);
		if(isset($Conversion[$database])){
		if(!is_file("$directory/domains")){echo "$directory has no domains\n";shell_exec("$rm -rf $directory");continue;}
		
		if(is_file("$directory/domains.ufdb")){
			if($GLOBALS["VERBOSE"]){echo "$directory/domains.ufdb OK\n";}
			continue;
		}
			$ufdb->UfdbGenTable("$directory",basename($directory));	
		}
	}
	
	
}

function CoherenceOffiels(){
	
	if(!ifMustBeExecuted()){
		WriteMyLogs("No make sense to execute this script...",__FUNCTION__,__FILE__,__LINE__);
		if($GLOBALS["VERBOSE"]){echo "No make sense to execute this script...\n";}die();
	}
	
	$workdir="/var/lib/ftpunivtlse1fr";
	$unix=new unix();
	$BASE_URI="ftp://ftp.univ-tlse1.fr/pub/reseau/cache/squidguard_contrib";
	$q=new mysql_squid_builder();
	$table=$q->TLSE_CONVERTION(true);
	$ARRAYSUM_REMOTE=GET_MD5S_REMOTE();
	while (list ($database, $articacat) = each ($table) ){
		$directory=str_replace("/", "_", $articacat);
		$targetdir=$workdir."/$database";
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":: Checking $targetdir/domains\n";}
		if(!is_file("$targetdir/domains")){
			ufdbguard_admin_events("$database is not in disk... download it..",__FUNCTION__,__FILE__,__LINE__,"Toulouse DB");
			update_remote_file($BASE_URI,"$database.tar.gz",$ARRAYSUM_REMOTE["$database.tar.gz"],0);
		}
	}
	
	reset($table);
	while (list ($database, $articacat) = each ($table) ){
		$directory=str_replace("/", "_", $articacat);
		
		
		$targetdir=$workdir."/$directory";
		$sourcedir=$workdir."/$database";
		@chmod($sourcedir, 0755);
		$unix->chown_func("squid", "squid",$sourcedir);
		if(!is_dir($targetdir)){
			if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":: Checking $targetdir no such directory make symbolic to $sourcedir\n";}
			shell_exec("ln -sf $sourcedir $targetdir");
		}
	}
	
	if(count($GLOBALS["squid_admin_mysql"])){
		squid_admin_mysql(2,count($GLOBALS["squid_admin_mysql"])." Toulouse Databases updated",@implode("\n", $GLOBALS["squid_admin_mysql"]));
		unset($GLOBALS["squid_admin_mysql"]);
	}
	
	
}



function CoherenceBase(){
	$unix=new unix();
	$myFile=basename(__FILE__);
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$cachetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$unix=new unix();	
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,$myFile)){WriteMyLogs("Already executed PID:$pid, die()",__FUNCTION__,__FILE__,__LINE__);die();}	
	
	$q=new mysql_squid_builder();
	$unix=new unix();
	$results=$q->QUERY_SQL("SELECT * FROM ftpunivtlse1fr");
	if(!$q->ok){if(strpos($q->mysql_error, "doesn't exist")>0){$q->CheckTables();$results=$q->QUERY_SQL("SELECT * FROM ftpunivtlse1fr");}}
	if(!$q->ok){ufdbguard_admin_events("Fatal: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"Toulouse DB");die();}
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){if($GLOBALS["VERBOSE"]){echo "ftpunivtlse1fr: {$ligne["filename"]} -> {$ligne["zmd5"]}\n";}$ARRAYSUM_LOCALE[$ligne["filename"]]=$ligne["zmd5"];}
	
	$dirs=$unix->dirdir("/var/lib/ftpunivtlse1fr");
	while (list ($directory, $line) = each ($dirs) ){
		if(!is_file("$directory/domains")){echo "$directory has no domains\n";shell_exec("$rm -rf $directory");continue;}
		$virtualFilename=basename($directory).".tar.gz";
		if(!isset($ARRAYSUM_LOCALE[$virtualFilename])){
			$CountDeSitesFile=CountDeSitesFile("$directory/domains");
			$md5=md5($virtualFilename);
			echo "Add virtual filename $virtualFilename with $CountDeSitesFile domains";
			$q->QUERY_SQL("INSERT INTO ftpunivtlse1fr (`filename`,`zmd5`,`websitesnum`) VALUES ('$virtualFilename','$md5','$CountDeSitesFile')");
		}else{
			if($GLOBALS["VERBOSE"]){echo "LOCAL: $virtualFilename -> $directory OK\n";}
		}
	}
}



function CountDeSitesFile($filename){
	$unix=new unix();
	$wc=$unix->find_program("wc");
	exec("$wc $filename 2>&1",$results);
	$txt=trim(@implode("", $results));
	if(preg_match("#^([0-9]+)#", $txt,$re)){return $re[1];}
	if($GLOBALS["VERBOSE"]){echo "$wc $filename 2>&1 -> `$txt` no match\n";}
	return 0;
}




function ifMustBeExecuted(){
	$sock=new sockets();
	
	
	$DisableCategoriesDatabasesUpdates=intval($sock->GET_INFO("DisableCategoriesDatabasesUpdates"));
	$GLOBALS["ifMustBeExecuted"][]="DisableCategoriesDatabasesUpdates = $DisableCategoriesDatabasesUpdates";
	if($GLOBALS["OUTPUT"]){echo "DisableCategoriesDatabasesUpdates: $DisableCategoriesDatabasesUpdates\n";}
	if($DisableCategoriesDatabasesUpdates==1){return false;}
	$q=new mysql_squid_builder();
	return $q->ifSquidUpdatesMustBeExecuted();
	
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