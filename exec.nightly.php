<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/refresh.index.progress";
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');
$GLOBALS["SINGLE_DEBUG"]=false;
$GLOBALS["NOT_FORCE_PROXY"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["BYCRON"]=false;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["CHANGED"]=false;
$GLOBALS["FORCE_NIGHTLY"]=false;
$GLOBALS["MasterIndexFile"]="/usr/share/artica-postfix/ressources/index.ini";
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--force-nightly#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;$GLOBALS["FORCE"]=true;$GLOBALS["FORCE_NIGHTLY"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;

	$GLOBALS["VERBOSE"]=true;
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string'," Fatal..:");
	ini_set('error_append_string',"\n");
}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--bycron#",implode(" ",$argv))){$GLOBALS["BYCRON"]=true;}
if($argv[1]=="--refresh"){RefreshIndex(true);exit;}
if($argv[1]=="--restart-services"){RestartDedicatedServices(true);exit;}
if($argv[1]=="--meta-release"){ArticaMeta_release($argv[2]);exit;}
if($argv[1]=="--hypercache"){hypercache();exit;}

nightly();
hypercache();

function hypercachestoreid_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	exec("/lib/squid3/hypercache-plugin -v 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#Version:\s+([0-9\.]+)#", $line,$re)){$GLOBALS[__FUNCTION__]=$re[1];return $re[1];}
	}
	
	return 0;
}

function hypercache(){
	$timefile="/etc/artica-postfix/pids/exec.nightly.php.hypercache.time";
	$unix=new unix();
	
	if($unix->file_time_min($timefile)<1440){
		updater_events("Current {$timefile}Mn !== 1440",__FUNCTION__,__LINE__);
		return;
	}
	@unlink($timefile);
	@file_put_contents($timefile, time());
	
	$squiver=$unix->squid_version();
	if(!preg_match("#^3\.(5|6|7|8)\.#", $squiver)){return;}
	$uri="https://svb.unveiltech.com/box/articatech/getlatest.php";
	
	$curl=new ccurl($uri);
	$tmpfile=$unix->FILE_TEMP();
	if(!$curl->GetFile($tmpfile)){
		_artica_update_event(0,"Unable to download hypercache-plugin infos $curl->error_num, $curl->error",__FILE__,__LINE__);
		@unlink($tmpfile);
		return;
	}
	
	$array=json_decode(@file_get_contents($tmpfile));
	@unlink($tmpfile);
	$local_version=hypercachestoreid_version();
	$remote_version=$array->ver;
	$remote_uri=$array->url;
	$remote_date=$array->date;
	
	if($GLOBALS["VERBOSE"]){echo "$local_version / $remote_version - ". intval($remote_version) ." $remote_date\n";}
	if(intval($remote_version)==0){return;}
	if($remote_version==$local_version){return;}
	
	
	$curl=new ccurl($remote_uri);
	$tmpfile=$unix->FILE_TEMP();
		
	if(!$curl->GetFile($tmpfile)){
		_artica_update_event(0,"Unable to download hypercache-plugin infos $curl->error_num, $curl->error",__FILE__,__LINE__);
		@unlink($tmpfile);
		return;
	}
	
	$tempdir=$unix->TEMP_DIR()."/".time();
	@mkdir($tempdir,0755,true);
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	shell_exec("$tar xf $tmpfile -C $tempdir/");
	@unlink($tmpfile);
	if(!is_file("$tempdir/64bits/hypercache-plugin")){
		_artica_update_event(0,"Unable to extract hypercache-plugin version $remote_version", null,__FILE__,__LINE__);
		shell_exec("$rm -rf xf $tempdir");
	}
	
	@copy("/lib/squid3/hypercache-plugin","/lib/squid3/hypercache-plugin-$local_version");
	@unlink("/lib/squid3/hypercache-plugin");
	@copy("$tempdir/64bits/hypercache-plugin", "/lib/squid3/hypercache-plugin");
	shell_exec("$rm -rf xf $tempdir");
	@chmod("/lib/squid3/hypercache-plugin",0755);
	@chown("/lib/squid3/hypercache-plugin","squid");
	squid_admin_mysql(1, "Reloading proxy service in order to update Hypercache software version from $local_version to $remote_version", null,__FILE__,__LINE__);
	_artica_update_event(2,"Success update Hypercache software version from $local_version to $remote_version", null,__FILE__,__LINE__);
	shell_exec("/etc/init.d/squid reload --force --script=".basename(__FILE__)."/".__LINE__);
	
	
	
	
}

function ArticaMeta_release_progress($text,$pourc){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/artica-meta.update.php.progress", serialize($array));
	$unix=new unix();

	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}
			
	}
	if($GLOBALS["OUTPUT"]){echo "{$pourc}) $text\n";}
	$unix->events("{$pourc}) $text","/var/log/artica.updater.log",false,$sourcefunction,$sourceline,$sourcefile);
	@chmod("/usr/share/artica-postfix/ressources/logs/web/artica-meta.update.php.progress",0755);

}

function ArticaMeta_release($source_package){
	$sock=new sockets();
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$Todelete=false;
	$EnableArticaMetaServer=intval($sock->GET_INFO("EnableArticaMetaServer"));
	if($EnableArticaMetaServer==0){
		ArticaMeta_release_progress("{disabled}",110);
		echo "Starting......: ".date("H:i:s")." Checking Artica-meta repository - DISABLED -\n";
		_artica_update_event(2,"Checking Artica-meta repository - DISABLED -",null,__FILE__,__LINE__);
		return;
	}
	
	ArticaMeta_release_progress("Checking $source_package",5);
	
	$dirname=dirname($source_package);
	if($dirname=="/usr/share/artica-postfix/ressources/conf/upload"){
		$Todelete=true;
	}
	
	echo "Starting......: ".date("H:i:s")." Checking Artica-meta repository - ENABLED -\n";
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	ArticaMeta_events("Storage $ArticaMetaStorage",10);
	
	
	@mkdir("$ArticaMetaStorage/nightlys",0755,true);
	@mkdir("$ArticaMetaStorage/releases",0755,true);
	$basename=basename($source_package);
	if(!preg_match("#artica-[0-9\.]+\.tgz#", $basename)){
		 ArticaMeta_release_progress("$basename no match #artica-[0-9\.]+\.tgz",110);
		 ArticaMeta_events("$basename no match #artica-[0-9\.]+\.tgz#");
		_artica_update_event(1,"Checking Artica-meta repository - FAILED ( $basename not an artica package)",null,__FILE__,__LINE__);
		echo "Starting......: ".date("H:i:s")." Checking Artica-meta repository - FAILED ( not an artica package) -\n";
		return;
	}
	
	
	if(is_file("$ArticaMetaStorage/releases/$basename")){
		ArticaMeta_release_progress("Remove $ArticaMetaStorage/releases/$basename",15);
		@unlink("$ArticaMetaStorage/releases/$basename");}
	
	$md5source=md5_file($source_package);
	ArticaMeta_release_progress("Testing $source_package",15);
	if(!$unix->TARGZ_TEST_CONTAINER($source_package)){
		ArticaMeta_release_progress("Testing $source_package {failed}",110);
		return;
	}
	
	ArticaMeta_release_progress("Copy $source_package",15);
	ArticaMeta_events("Copy $source_package to $ArticaMetaStorage/releases/$basename");
	@copy($source_package, "$ArticaMetaStorage/releases/$basename");
	$md5Dest=md5_file("$ArticaMetaStorage/releases/$basename");
	
	if($md5source<>$md5Dest){
		ArticaMeta_release_progress("$md5source differ $md5Dest",110);
		ArticaMeta_events("$md5source differ $md5Dest");
		_artica_update_event(1,"Checking Artica-meta repository - FAILED source differ!");
		if($Todelete){@unlink($source_package);}
		return;
		
	}
	
	ArticaMeta_release_progress("Added $basename into official repository",20);
	_artica_update_event(2,"Added $basename into official repository",null,__FILE__,__LINE__);
	meta_admin_mysql(2, "Added $basename into official repository", null,__FILE__,__LINE__);
	if($Todelete){
		ArticaMeta_events("Removing $source_package");
		@unlink($source_package);
	}
	ArticaMeta_release_progress("Preparing package",20);
	ArticaMeta_events("Execute $php /usr/share/artica-postfix/exec.artica-meta-server.php --scan-repos --force ");
	system("$php /usr/share/artica-postfix/exec.artica-meta-server.php --scan-repos --force --output");
}

function ArticaMeta_events($subject){
	// 0 -> RED, 1 -> WARN, 2 -> INFO

	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$file=basename($trace[1]["file"]);
			$function=$trace[1]["function"];
			$line=$trace[1]["line"];
			
		}
			
	}
	if($GLOBALS["OUTPUT"]){echo "$subject\n";}

	$unix=new unix();
	$unix->events($subject,"/var/log/artica-metaserver-update.log",false,$function,$line,$file);
}

function ArticaMeta_nightly($source_package){
	$sock=new sockets();
	$EnableArticaMetaServer=intval($sock->GET_INFO("EnableArticaMetaServer"));
	if($EnableArticaMetaServer==0){
		echo "Starting......: ".date("H:i:s")." Checking Artica-meta repository - DISABLED -\n";
		_artica_update_event(2,"Checking Artica-meta repository - DISABLED -",null,__FILE__,__LINE__);
		return;
	}
	build_progress_manu("Checking Meta repository",32);
	echo "Starting......: ".date("H:i:s")." Checking Artica-meta repository - ENABLED -\n";
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	@mkdir("$ArticaMetaStorage/nightlys",0755,true);
	@mkdir("$ArticaMetaStorage/releases",0755,true);
	$basename=basename($source_package);
	if(!preg_match("#artica-[0-9\.]+\.tgz#", $basename)){
		_artica_update_event(1,"Checking Artica-meta repository - FAILED ( $basename not an artica package)",null,__FILE__,__LINE__);
		echo "Starting......: ".date("H:i:s")." Checking Artica-meta repository - FAILED ( not an artica package) -\n";
		return;
	}
	build_progress_manu("Checking Meta repository",33);
	if(is_file("$ArticaMetaStorage/nightlys/$basename")){@unlink("$ArticaMetaStorage/nightlys/$basename");}
	@copy($source_package, "$ArticaMetaStorage/nightlys/$basename");
	_artica_update_event(2,"Added $basename into nightly repository",null,__FILE__,__LINE__);
	meta_admin_mysql(2, "Added $basename into nightly repository", null,__FILE__,__LINE__);
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress_manu("Checking Meta repository",34);
	shell_exec("$php ".dirname(__FILE__)."/exec.artica-meta-server.php --force");
	
}


function RefreshIndex(){
	$unix=new unix();
	$nice=EXEC_NICE();
	$sock=new sockets();
	$users=new usersMenus();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$wget=$unix->find_program("wget");
	$SYSTEMID=$unix->GetUniqueID();
	if($SYSTEMID==null){
		build_progress("System ID is Null !!!",5);
		return;
	}
	
	
	build_progress_index("Register server...",10);
	build_progress("Register server...",10);
	shell_exec("$nohup $nice $php /usr/share/artica-postfix/exec.web-community-filter.php --register-lic >/dev/null 2>&1 &");
	
	if($SYSTEMID==null){
		build_progress_index("No system ID, force...",15);
		build_progress("No system ID, force",15);
		shell_exec("$nice /usr/share/artica-postfix/bin/artica-update -refresh-index --force >/dev/null 2>&1");
		return;
	}
	$xMEM_TOTAL_INSTALLEE=$users->MEM_TOTAL_INSTALLEE;
	$CPU_NUMBER=$users->CPU_NUMBER;
	$LinuxDistributionFullName=$users->LinuxDistriFullName;
	if($LinuxDistributionFullName==null){$LinuxDistributionFullName="Linux Default";}
	$ARTICA_VERSION=GetCurrentVersion();
	$hostname=$unix->hostname_g();
	$CheckUserCount=CheckUserCount();
	$RebootAfterArticaUpgrade=$sock->GET_INFO("RebootAfterArticaUpgrade");
	if(!is_numeric($RebootAfterArticaUpgrade)){$RebootAfterArticaUpgrade=0;}
	$EnableScheduleUpdates=$sock->GET_INFO("EnableScheduleUpdates");
	$ArticaUpdateIntervalAllways=intval($sock->GET_INFO("EnableScheduleUpdates"));
	if(!is_numeric($EnableScheduleUpdates)){$EnableScheduleUpdates=0;}
	$ArticaAutoUpateOfficial=$sock->GET_INFO("ArticaAutoUpateOfficial");
	$ArticaAutoUpateNightly=intval($sock->GET_INFO("ArticaAutoUpateNightly"));
	$ArticaUpdateIntervalAllways=intval($sock->GET_INFO("ArticaUpdateIntervalAllways"));
	$OfficialArticaUri=$sock->GET_INFO("OfficialArticaUri");
	if(!is_numeric($ArticaAutoUpateOfficial)){$ArticaAutoUpateOfficial=1;}
	if($OfficialArticaUri==null){$OfficialArticaUri="http://articatech.net/artica.update.php";}
	$uri=$OfficialArticaUri;
	
	$dmidecode=@file_get_contents("/etc/artica-postfix/dmidecode.cache.url");
	
	
	
	@unlink($GLOBALS["MasterIndexFile"]);
	$tarballs_file="/usr/share/artica-postfix/ressources/logs/web/tarballs.cache";
	echo "Starting......: ".date("H:i:s")." CPU NUMBER: $CPU_NUMBER\n";
	echo "Starting......: ".date("H:i:s")." Hostname..: $hostname\n";
	echo "Starting......: ".date("H:i:s")." Artica ver: $ARTICA_VERSION\n";
	echo "Starting......: ".date("H:i:s")." Users.....: $CheckUserCount\n";
	build_progress_index("Configuration done...",15);
	build_progress("Configuration done",15);
	
	$DATA["UUID"]=$SYSTEMID;
	$DATA["MEM"]=$xMEM_TOTAL_INSTALLEE;
	$DATA["CPU"]=$CPU_NUMBER;
	$DATA["LINUX"]=$LinuxDistributionFullName;
	$DATA["VERSION"]=$ARTICA_VERSION;
	$DATA["HOSTNAME"]=$hostname;
	$DATA["USERS"]=$CheckUserCount;
	$DATA["DMICODE"]=$dmidecode;
	
	build_progress_index("Check repositories...",20);
	build_progress("Check repositories",20);
	$MAIN_URI=$unix->MAIN_URI();
	echo "Starting......: ".date("H:i:s")." Main URI..: $MAIN_URI\n";
	$md5string=@md5_file($GLOBALS["MasterIndexFile"]);
	
	
	build_progress_index("Get Index...",25);
	build_progress("Get Index...",25);
	echo "Starting......: ".date("H:i:s")." Update index file..\n";
	$curl=new ccurl($OfficialArticaUri);
	$curl->parms["datas"]=base64_encode(serialize($DATA));
	if(!$curl->get()){
		echo "Last detected error: $curl->error\n";
		build_progress_index("Get Index...{failed}",110);
		build_progress("Get Index file !! FAILED !!",110);
		_artica_update_event(0,"Unable to download index file with error $curl->error_num, $curl->error",null,__FILE__,__LINE__);
		return false;
	}

	
	@unlink("/etc/artica-postfix/settings/Daemons/ArticaUpdateRepos");
	build_progress_index("Parse Index from repository",40);
	build_progress("Parse Index from repository",40);
	
	
	
	if(preg_match("#<ERROR>(.+?)</ERROR>#is", $curl->data,$re)){
		echo "{$re[1]} !! FAILED !!\n";
		build_progress_index("Corrupted index from repository !! FAILED !!",110);
		build_progress("Corrupted index from repository !! FAILED !!",110);
		return;
		
	}
	
	if(!preg_match("#<CONTENT>(.+?)</CONTENT>#is", $curl->data,$re)){
		echo "Corrupted index from repository !! FAILED !!\n";
		build_progress_index("Corrupted index from repository !! FAILED !!",110);
		build_progress("Corrupted index from repository !! FAILED !!",110);
		return;
	}
	
	$MAIN=unserialize(base64_decode($re[1]));
	if(!is_array($MAIN)){
		echo "It is not an array...\n";
		build_progress_index("Corrupted index from repository !! FAILED !!",110);
		build_progress("Corrupted index from repository !! FAILED !!",110);
		
	}
	
	build_progress_index("Retreive index from repository success",100);
	@file_put_contents("/etc/artica-postfix/settings/Daemons/ArticaUpdateRepos", serialize($MAIN));
	

	return true;
	
	
	
}

function _artica_update_event($severity,$subject,$text,$file=null,$line=0){
	if(!function_exists("artica_update_event")){return;}
	artica_update_event($severity,$subject,$text,$file,$line);
}

function build_progress_manu($text,$pourc){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/download_progress", serialize($array));
	$unix=new unix();
	
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}
			
	}
	
	$unix->events("{$pourc}) $text","/var/log/artica.updater.log",false,$sourcefunction,$sourceline,$sourcefile);
	@chmod($GLOBALS["PROGRESS_FILE"],0755);	
	
	
}


function build_progress($text,$pourc){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	$unix=new unix();
	
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}
			
	}
	
	$unix->events("{$pourc}) $text","/var/log/artica.updater.log",false,$sourcefunction,$sourceline,$sourcefile);
	@chmod($GLOBALS["PROGRESS_FILE"],0755);

}

function updater_events($text,$sourcefunction=null,$sourceline=0){
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			if($sourcefunction==null){$sourcefunction=$trace[1]["function"];}
			if($sourceline==0){$sourceline=$trace[1]["line"];}
		}
			
	}
	$unix=new unix();
	$unix->events("$text","/var/log/artica.updater.log",false,$sourcefunction,$sourceline,$sourcefile);
	
}

function build_progress_index($text,$pourc){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/refresh.index.progress", serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);

}
function CheckUserCount(){
	$unix=new unix();
	$cachefile="/etc/artica-postfix/UsersNumber";
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$nice=EXEC_NICE();
	if(!is_file($cachefile)){
			shell_exec("$nohup $nice $php /usr/share/artica-postfix/exec.samba.php --users >/dev/null 2>&1 &");
			return 0;
	}
	
	$usersN=@file_get_contents($cachefile);
	if($unix->file_time_min($cachefile)>3600){
		@unlink($cachefile);
		shell_exec("$nohup $nice $php /usr/share/artica-postfix/exec.samba.php --users >/dev/null 2>&1 &");
		return $usersN;		
	}
	return $usersN;
}
//#############################################################################

function update_find_latest_nightly(){

	$array=unserialize(@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaUpdateRepos"));
	$MAIN=$array["NIGHT"];
	$keyMain=0;
	while (list ($key, $ligne) = each ($MAIN)){
		$key=intval($key);
		if($key==0){continue;}
		if($key>$keyMain){$keyMain=$key;}
	}
	return $keyMain;
}

function update_find_latest(){
	
	$array=unserialize(@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaUpdateRepos"));
	$MAIN=$array["OFF"];
	$keyMain=0;
	while (list ($key, $ligne) = each ($MAIN)){
		$key=intval($key);
		if($key==0){continue;}
		if($key>$keyMain){$keyMain=$key;}
	}
	return $keyMain;		
}


function update_release(){
	$sock=new sockets();
	$unix=new unix();
	$autoinstall=true;
	
	
	$tmpdir=$unix->TEMP_DIR();
	if(!master_index()){return false;}
	$RebootAfterArticaUpgrade=$sock->GET_INFO("RebootAfterArticaUpgrade");
	if(!is_numeric($RebootAfterArticaUpgrade)){$RebootAfterArticaUpgrade=0;}
	$EnableScheduleUpdates=$sock->GET_INFO("EnableScheduleUpdates");
	$ArticaUpdateIntervalAllways=intval($sock->GET_INFO("EnableScheduleUpdates"));
	if(!is_numeric($EnableScheduleUpdates)){$EnableScheduleUpdates=0;}
	$ArticaAutoUpateOfficial=$sock->GET_INFO("ArticaAutoUpateOfficial");
	$ArticaAutoUpateNightly=intval($sock->GET_INFO("ArticaAutoUpateNightly"));
	$ArticaUpdateIntervalAllways=intval($sock->GET_INFO("ArticaUpdateIntervalAllways"));
	$OfficialArticaUri=$sock->GET_INFO("OfficialArticaUri");
	if(!is_numeric($ArticaAutoUpateOfficial)){$ArticaAutoUpateOfficial=1;}
	if($OfficialArticaUri==null){$OfficialArticaUri="http://articatech.net/artica.update.php";}
	$CheckEveryMinutes=60;
	$uri=$OfficialArticaUri;
	$MyCurrentVersion=GetCurrentVersion();

	echo "Starting......: ".date("H:i:s")." Retreve Index file from cloud...\n";
	
	if(!RefreshIndex()){
		echo "Starting......: ".date("H:i:s")." index file failed\n";
		updater_events("Index file failed");
		build_progress_manu("Index {failed}",110);
		die();
		
	}
	if($RebootAfterArticaUpgrade==1){echo "Starting......: ".date("H:i:s")." Reboot after upgrade is enabled\n";}
	$key=update_find_latest();
	$MyNextVersion=$key;
	$array=unserialize(@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaUpdateRepos"));
	$OFFICIALS=$array["OFF"];
	
	$Lastest=$OFFICIALS[$key]["VERSION"];
	$MAIN_URI=$OFFICIALS[$key]["URL"];
	$MAIN_MD5=$OFFICIALS[$key]["MD5"];
	$MAIN_FILENAME=$OFFICIALS[$key]["FILENAME"];
	$uri=$MAIN_URI;
	
	$nightly=trim(strtolower($ini->_params["NEXT"]["artica-nightly"]));
	
	$GLOBALS["lastest-nightly"]=$nightly;
	
	echo "Starting......: ".date("H:i:s")." Last Official release: \"$Lastest\"\n";
	echo "Starting......: ".date("H:i:s")." Last Official release: \"$MAIN_URI\"\n";
	echo "Starting......: ".date("H:i:s")." Last Official release: \"$MAIN_MD5\"\n";
	echo "Starting......: ".date("H:i:s")." Last Nightly release:. \"$nightly\"\n";
	
	echo "Starting......: ".date("H:i:s")." Official release Cur:$MyCurrentVersion, Next:$MyNextVersion\n";
	if($MyNextVersion==$MyCurrentVersion){
		echo "Starting......: ".date("H:i:s")." Official release $MyCurrentVersion/$MyNextVersion \"Up to date\"\n";
		updater_events("Official release $MyCurrentVersion/$MyNextVersion UP TO DATE");
		return;
	}
	if($MyCurrentVersion>$MyNextVersion){
		echo "Starting......: ".date("H:i:s")." Official release $MyCurrentVersion/$MyNextVersion \"Up to date\"\n";
		updater_events("Official release $MyCurrentVersion/$MyNextVersion UP TO DATE");
		return;
	}

	
	$t1=time();
	_artica_update_event(1,"New official release available version $Lastest",null,__FILE__,__LINE__);
	echo "Starting......: ".date("H:i:s")." Official release Downloading new version $Lastest, please wait\n";
	updater_events("Downloading new version $Lastest");
	
	
	$ArticaFileTemp="$tmpdir/$Lastest/$MAIN_FILENAME";
	@mkdir("$tmpdir/$Lastest",0755,true);
	$curl=new ccurl($uri);
	$curl->Timeout=2400;
	$curl->WriteProgress=true;
	$curl->ProgressFunction="nightly_progress";
	$t=time();
	echo "Starting......: ".date("H:i:s")." Official release Downloading $uri\n";
	
	if(!$curl->GetFile($ArticaFileTemp)){
		_artica_update_event(0,"Error: Official release Unable to download latest build with error $curl->error",null,__FILE__,__LINE__);
		updater_events("Unable to download latest build with error $curl->error");
		system_admin_events("Unable to download latest build with error $curl->error", __FUNCTION__, __FILE__, __LINE__, "artica-update");
		@unlink($ArticaFileTemp);
		return;
	}
	
	$size=@filesize($ArticaFileTemp)/1024;
	$md5_file=md5_file($ArticaFileTemp);
	if($md5_file<>$MAIN_MD5){
		events("Corrupted file $md5_file <> $MAIN_MD5");
		build_progress_manu("Corrupted {failed}",110);
		die();
	}
	
	
	echo "Starting......: ".date("H:i:s")." Official release size:{$size}KB\n";
	
	$took=$unix->distanceOfTimeInWords($t,time(),true);
	_artica_update_event(2,"$MAIN_FILENAME downloaded, took $took",null,__FILE__,__LINE__);
	system_admin_events("$MAIN_FILENAME downloaded, took $took", __FUNCTION__, __FILE__, __LINE__, "artica-update");
	ArticaMeta_release($ArticaFileTemp);
	events("$MAIN_FILENAME downloaded, took $took");
	
	echo "Starting......: ".date("H:i:s")." Checking Artica-meta repository\n";
	build_progress_manu("Checking Artica-meta repository",11);
	ArticaMeta_release($ArticaFileTemp);
	
	echo "Starting......: ".date("H:i:s")." Official release took $took\n";
	$size=@filesize($ArticaFileTemp);
	$size=FormatBytes($size/1024,true);
	build_progress_manu("{installing}",12);
	if(install_package($ArticaFileTemp,$Lastest)){return;}
	events("New Artica update v.$Lastest");
	_artica_update_event(1,"Nightly build: Artica v.$Lastest ($size)",null,__FILE__,__LINE__);
	system_admin_events("New Artica update v.$Lastest", __FUNCTION__, __FILE__, __LINE__, "artica-update");
	
}


function master_index(){
	
	@unlink($GLOBALS["MasterIndexFile"]);
	
	$ini=new iniFrameWork();
	$ini->loadFile('/etc/artica-postfix/artica-update.conf');
	if(trim($ini->_params["AUTOUPDATE"]["autoinstall"])==null){$ini->_params["AUTOUPDATE"]["autoinstall"]="yes";}
	if($ini->_params["AUTOUPDATE"]["autoinstall"]<>"yes"){$autoinstall=false;}
	$uri=$ini->_params["AUTOUPDATE"]["uri"];
	$arrayURI=parse_url($uri);
	$MAIN_URI="{$arrayURI["scheme"]}://{$arrayURI["host"]}";
	
	echo "Starting......: ".date("H:i:s")." Refreshing index file...\n";
	
	$curl=new ccurl("$uri?time=".time());
	if(!$curl->GetFile($GLOBALS["MasterIndexFile"])){
	_artica_update_event(0,"Error $curl->error",null,__FILE__,__LINE__);
	echo "Starting......: ".date("H:i:s")." Error $curl->error_num;$curl->error, Try direct\n";
	
	if(!$GLOBALS["NOT_FORCE_PROXY"]){
		echo "Starting......: ".date("H:i:s")." FATAL: Unable to download index file, try in direct mode\n";
		$GLOBALS["NOT_FORCE_PROXY"]=true;
		return master_index();
	}
	
	
	if($curl->error=="{CURLE_COULDNT_RESOLVE_HOST}"){
			if($arrayURI["host"]=="www.artica.fr"){
				if(!$GLOBALS["CHANGED"]){
					echo "Starting......: ".date("H:i:s")." trying www.articatech.net\n";
					$ini->_params["AUTOUPDATE"]["uri"]="http://www.articatech.net/auto.update.php";
					$ini->saveFile("/etc/artica-postfix/artica-update.conf");
					$GLOBALS["CHANGED"]=true;
					return master_index();
				}
			}
		}
		return;
	}
	
	
	if(!is_file($GLOBALS["MasterIndexFile"])){
		echo "Starting......: ".date("H:i:s")." {$GLOBALS["MasterIndexFile"]} no such file...\n";
		return;
	
	}
	
	return true;
}


function nightly(){
	@mkdir("/var/log/artica-postfix",0755,true);
	$GLOBALS["MasterIndexFile"]="/usr/share/artica-postfix/ressources/index.ini";
	$unix=new unix();
	$sock=new sockets();
	$autoinstall=true;
	$timefile="/etc/artica-postfix/croned.1/nightly";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$pid=@file_get_contents($pidfile);
	$kill=$unix->find_program("kill");
	$tmpdir=$unix->TEMP_DIR();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		echo "Starting......: ".date("H:i:s")." nightly build already executed PID: $pid since {$time}Mn\n";
		updater_events("Already executed PID: $pid since {$time}Mn");
		if($time<120){if(!$GLOBALS["FORCE"]){die();}}
		unix_system_kill_force($pid);
	}
	
	$mypid=getmypid();
	@file_put_contents($pidfile, $mypid);	
	updater_events("Running PID $mypid");
	
	$EnableScheduleUpdates=$sock->GET_INFO("EnableScheduleUpdates");
	$ArticaUpdateIntervalAllways=intval($sock->GET_INFO("EnableScheduleUpdates"));
	if(!is_numeric($EnableScheduleUpdates)){$EnableScheduleUpdates=0;}
	$ArticaAutoUpateOfficial=$sock->GET_INFO("ArticaAutoUpateOfficial");
	$ArticaAutoUpateNightly=intval($sock->GET_INFO("ArticaAutoUpateNightly"));
	$ArticaUpdateIntervalAllways=intval($sock->GET_INFO("ArticaUpdateIntervalAllways"));
	$OfficialArticaUri=$sock->GET_INFO("OfficialArticaUri");
	if(!is_numeric($ArticaAutoUpateOfficial)){$ArticaAutoUpateOfficial=1;}
	if($OfficialArticaUri==null){$OfficialArticaUri="http://articatech.net";}
	$CheckEveryMinutes=60;
	$uri=$OfficialArticaUri;
	
	if($GLOBALS["FORCE"]){
		_artica_update_event(1,"Update task pid $mypid is forced by an human.",null,__FILE__,__LINE__);
	}
	
	if($EnableScheduleUpdates==1){
		if(!$GLOBALS["FORCE"]){
			if(!$GLOBALS["BYCRON"]){
				updater_events("Operation must be only executed by scheduler");
				_artica_update_event(2,"Operation must be only executed by scheduler ( use --force to by pass)",null,__FILE__,__LINE__);
				build_progress_manu("must be only executed by scheduler", 110);
				
				return;
			}
		}
	}
	
	
	if($ArticaAutoUpateOfficial==0){
		updater_events("Artica Update feature is disabled");
		echo "Starting......: ".date("H:i:s")." Artica Update feature is disabled (enabled = $ArticaAutoUpateOfficial} )\n";
		return;
	}
		
	if(!$GLOBALS["FORCE"]){
		if($EnableScheduleUpdates==0){
			if($unix->file_time_min($timefile)<$CheckEveryMinutes){
				updater_events("too short time ({$timefile}Mn, require {$CheckEveryMinutes}mn)");
				echo "Starting......: ".date("H:i:s")." update feature (too short time, require {$CheckEveryMinutes}mn)\n";
				build_progress_manu("too short time", 110);
				
				return;
			}
		}
		
		if($ArticaUpdateIntervalAllways==0){
			if($unix->IsProductionTime()){
				updater_events("Update feature need to be run only during the non-production time");
				echo "Starting......: ".date("H:i:s")." update feature need to be run only during the non-production time \n";
				build_progress_manu("run only during the non-production time", 110);
			}
		}
	}
	
	@unlink($timefile);
	@file_put_contents($timefile, time());
	

// ----------------------- LANCEMENT ------------------------------------------------------------------------------

	$EnableArticaMetaClient=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaMetaClient"));
	$EnableArticaMetaServer=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaMetaServer"));
	if($EnableArticaMetaServer==1){$EnableArticaMetaClient=0;}
	
	if($EnableArticaMetaClient==1){
		updater_events("Nightly builds using Meta console");
		echo "Starting......: ".date("H:i:s")." Nightly builds using Meta console\n";
		system("$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-client.php --artica-updates >/dev/null 2>&1 &");
		die();
	}
	
	
	
	echo "Starting......: ".date("H:i:s")." Nightly builds checking an official release first\n";
	
	build_progress_manu("{starting}",10);
	
	
	if(update_release()){
		updater_events("update_release() return true, finish");
		return;
	}
	
	if($ArticaAutoUpateNightly==0){
		echo "Starting......: ".date("H:i:s")." Nightly builds feature is disabled\n";
		updater_events("Update to Nightly builds feature is disabled");
		build_progress_manu("Nightly is disabled",110);
		
		return;
		
	}

	
	
	
	$array=unserialize(@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaUpdateRepos"));
	$OFFICIALS=$array["NIGHT"];
	$key=update_find_latest_nightly();
	$MyNextVersion=$key;
	$Lastest=$OFFICIALS[$key]["VERSION"];
	$MAIN_URI=$OFFICIALS[$key]["URL"];
	$MAIN_MD5=$OFFICIALS[$key]["MD5"];
	$MAIN_FILENAME=$OFFICIALS[$key]["FILENAME"];
	$uri=$MAIN_URI;
	$Lastest=trim(strtolower($Lastest));
		
	$MyCurrentVersion=GetCurrentVersion();
	echo "Starting......: ".date("H:i:s")." Current version: $MyCurrentVersion\n";
	echo "Starting......: ".date("H:i:s")." Nightly builds version \"$Lastest\" on repository\n";
	echo "Starting......: ".date("H:i:s")." nightly builds Cur:$MyCurrentVersion, Next:$MyNextVersion\n";
	if($MyNextVersion==$MyCurrentVersion){
		echo "Starting......: ".date("H:i:s")." nightly builds $MyCurrentVersion/$MyNextVersion \"Up to date - Same version\"\n";
		build_progress_manu("Up to date - Same version",110);
		return;
	}
	if($MyCurrentVersion>$MyNextVersion){
		echo "Starting......: ".date("H:i:s")." nightly builds $MyCurrentVersion/$MyNextVersion \"Up to date - Most updated\"\n";
		build_progress_manu("Up to date - Same version",110);
		return;
	}
	
	$t1=time();
	_artica_update_event(2,"nightly builds Downloading new version $Lastest",null,__FILE__,__LINE__);
	echo "Starting......: ".date("H:i:s")." nightly builds Downloading new version $Lastest, please wait\n";
	events("Downloading new version $Lastest");
	
	
	$ArticaFileTemp="$tmpdir/$Lastest/artica-$Lastest.tgz";    
	@mkdir("$tmpdir/$Lastest",0755,true);
	$curl=new ccurl($uri);
	$curl->Timeout=2400;
	$curl->WriteProgress=true;
	$curl->ProgressFunction="nightly_progress";
	$t=time();
	build_progress_manu("{downloading}",30);
	
	
	if(!$curl->GetFile($ArticaFileTemp)){
		build_progress_manu("{downloading} {failed}",110);
		_artica_update_event(0,"nightly builds Unable to download latest nightly build $Lastest with error $curl->error",null,__FILE__,__LINE__);
		events("Unable to download latest nightly build with error $curl->error");
		system_admin_events("Unable to download latest nightly build with error $curl->error", __FUNCTION__, __FILE__, __LINE__, "artica-update");
		@unlink($ArticaFileTemp);
		return;
	}
	build_progress_manu("{downloading} {success}",31);
	$took=$unix->distanceOfTimeInWords($t,time(),true);
	_artica_update_event(2,"$MAIN_FILENAME download, took $took",null,__FILE__,__LINE__);
	
	$md5_file=md5_file($ArticaFileTemp);
	if($md5_file<>$MAIN_MD5){
		echo "$md5_file <> $MAIN_MD5\n";
		_artica_update_event(0,"nightly builds $MAIN_FILENAME: corrupted package",null,__FILE__,__LINE__);
		events("nightly builds $MAIN_FILENAME: corrupted package");
		system_admin_events("nightly builds $MAIN_FILENAME: corrupted package", __FUNCTION__, __FILE__, __LINE__, "artica-update");
		@unlink($ArticaFileTemp);
		return;
		
	}
	
	
	system_admin_events("$MAIN_FILENAME download, took $took", __FUNCTION__, __FILE__, __LINE__, "artica-update");
	events("artica-$Lastest.tgz download, took $took");
	$size=@filesize($ArticaFileTemp);
	$size=FormatBytes($size/1024,true);
	build_progress_manu("Checking Meta repository",31);
	ArticaMeta_nightly($ArticaFileTemp);
	echo "Starting......: ".date("H:i:s")." nightly builds took $took\n";
	
	events("Now, installing the newest version in $ArticaFileTemp package...");
	$size=@filesize($ArticaFileTemp);
	$size=FormatBytes($size/1024,true);
	
	if(!install_package($ArticaFileTemp,$Lastest)){
		events("Install package Failed...");
		return false;}
	 events("New Artica update v.$Lastest");
	_artica_update_event(1,"Nightly builds New Artica update v.$Lastest ($size)",null,__FILE__,__LINE__);
	system_admin_events("New Artica update v.$Lastest", __FUNCTION__, __FILE__, __LINE__, "artica-update");

}

function install_package($filename,$expected=null){
	$unix=new unix();
	$sock=new sockets();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$rm=$unix->find_program("rm");
	$squidbin=$unix->LOCATE_SQUID_BIN();
	$RebootAfterArticaUpgrade=$sock->GET_INFO("RebootAfterArticaUpgrade");
	if(!is_numeric($RebootAfterArticaUpgrade)){$RebootAfterArticaUpgrade=0;}
	events("Starting......: ".date("H:i:s")." install_package() Extracting package $filename, please wait... ");
	echo "Starting......: ".date("H:i:s")." install_package() Extracting package $filename, please wait... \n";
	
	
	
	$tarbin=$unix->find_program("tar");
	$killall=$unix->find_program("killall");
	echo "Starting......: ".date("H:i:s")." tar: $tarbin\n";
	echo "Starting......: ".date("H:i:s")." killall: $killall\n";
	
	build_progress_manu("Testing Package",50);
	
	events("Starting......: ".date("H:i:s")." install_package() Testing Package");
	echo "Starting......: ".date("H:i:s")." Testing Package ".basename($filename)."\n";
	
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string'," WARNING!!:");
	ini_set('error_append_string',"\n");
	echo "Starting......: ".date("H:i:s")." Testing Package Please wait....\n";

	
	
	if(!$unix->TARGZ_TEST_CONTAINER($filename,false,true)){
		echo "Starting......: ".date("H:i:s")." Testing Package ".basename($filename)." failed\n";
		_artica_update_event(0,"Compressed package seems corrupted",null,__FILE__,__LINE__);
		events("Fatal, Compressed package seems corrupted");
		events($GLOBALS["TARGZ_TEST_CONTAINER_ERROR"]);
		@unlink($filename);
		build_progress_manu("Corrupted!",110);
		
		return false;
	}
	echo "Starting......: ".date("H:i:s")." Purge directories\n";
	events("Starting......: ".date("H:i:s")." Purge directories...");
	build_progress_manu("Purge directories",55);
	

	if(is_dir("/usr/share/artica-postfix/ressources/conf/upload")){
		system("$rm -f /usr/share/artica-postfix/ressources/conf/upload/*");
	}
	
	if(is_dir("/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded")){
		system("$rm -f /usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/*");
	}
	
	events("Starting......: ".date("H:i:s")." Extracting...");
	exec("$tarbin xpf $filename -C /usr/share/ 2>&1",$results);
	
	
	if(is_file($squidbin)){
		$squidfiles=$unix->SquidPHPFiles();
		while (list ($fileSquid,$None123) = each ($squidfiles)){
			@chgrp("/usr/share/artica-postfix/$fileSquid", "squid");
			@chown("/usr/share/artica-postfix/$fileSquid", "squid");
			@chmod("/usr/share/artica-postfix/$fileSquid",0755);
		}
		
	}
	
	if(is_file("$killall")){shell_exec("$killall artica-install >/dev/null 2>&1");}
	@unlink($filename);
	shell_exec("$nohup $php ". dirname(__FILE__)."/exec.checkfolder-permissions.php --force >/dev/null 2>&1 &");
		
	$MyCurrentVersion=GetCurrentVersionString();
	if($expected<>null){
		if($MyCurrentVersion<>$expected){
			_artica_update_event(1,"install_package(): Expected version:$expected does not match $MyCurrentVersion",$results,__FILE__,__LINE__);
			return;
		}
	}
	_artica_update_event(2,"install_package(): Success updating to a new version v$MyCurrentVersion",$results,__FILE__,__LINE__);
	
	
	
	
	
	if($RebootAfterArticaUpgrade==1){
		build_progress_manu("{rebooting}",90);
		_artica_update_event(1,"install_package() Reboot the server in 10s...",null,__FILE__,__LINE__);
		events("Reboot the server in 10s...");
		system_admin_events("Warning: Reboot the server in 10s...", __FUNCTION__, __FILE__, __LINE__, "artica-update");
		$shutdown=$unix->find_program("shutdown");
		shell_exec("shutdown -r -t 10");
		sleep(5);
		build_progress_manu("{done}",100);
		return true;
	}	
	
	_artica_update_event(2,"install_package(): restart dedicated services...",null,__FILE__,__LINE__);
	system_admin_events("Warning: Restart Artica dedicated services after an upgrade...", __FUNCTION__, __FILE__, __LINE__, "artica-update");
	system("$php ". __FILE__." --restart-services");
	_artica_update_event(2,"install_package(): finish",null,__FILE__,__LINE__);
	build_progress_manu("{done}",100);
	return true;
	
	
}

function RestartDedicatedServices($aspid=false){
	$unix=new unix();
	
	if($aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		
		
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			echo "Starting......: ".date("H:i:s")." RestartDedicatedServices already executed PID: $pid since {$time}Mn\n";
			if($time<120){if(!$GLOBALS["FORCE"]){die();}}
			unix_system_kill_force($pid);
		}
		
		@file_put_contents($pidfile, getmypid());
	}
	
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	$unix->THREAD_COMMAND_SET("$php /usr/share/artica-postfix/exec.web-community-filter.php --register");
	events("Starting artica");
	echo "Starting......: ".date("H:i:s")." nightly builds starting artica...\n";
	build_progress_manu("{starting}",60);
	
	system("/etc/init.d/artica-postfix start");
	echo "Starting......: ".date("H:i:s")." nightly builds building init scripts\n";
	build_progress_manu("{starting}",61);
	
	system("$php /usr/share/artica-postfix/exec.initslapd.php --force >/dev/null 2>&1");
	echo "Starting......: ".date("H:i:s")." nightly builds updating network\n";
	build_progress_manu("{starting}",62);
	system("$php /usr/share/artica-postfix/exec.virtuals-ip.php >/dev/null 2>&1");
	build_progress_manu("{starting}",63);
	system("$php /usr/share/artica-postfix/exec.monit.php --build >/dev/null 2>&1");
	echo "Starting......: ".date("H:i:s")." nightly builds purge and clean....\n";
	build_progress_manu("{starting}",64);
	
	
	build_progress_manu("{starting}",65);
	shell_exec("$nohup /etc/init.d/artica-webconsole start >/dev/null 2>&1 &");
	
	shell_exec("$nohup /etc/init.d/slapd start >/dev/null 2>&1 &");
	build_progress_manu("{starting}",65);
	shell_exec("$nohup /etc/init.d/artica-webconsole start >/dev/null 2>&1 &");
	build_progress_manu("{starting}",66);
	if(is_file("/etc/init.d/nginx")){shell_exec("$nohup /etc/init.d/nginx reload >/dev/null 2>&1 &");}
	build_progress_manu("{starting}",67);
	shell_exec("$nohup /etc/init.d/auth-tail restart >/dev/null 2>&1 &");
	build_progress_manu("{starting}",68);
	shell_exec("$nohup /etc/init.d/artica-framework restart >/dev/null 2>&1 &");
	build_progress_manu("{starting}",69);
	shell_exec("$nohup /usr/share/artica-postfix/bin/process1 -perm >/dev/null 2>&1 &");
	build_progress_manu("{starting}",70);
	shell_exec("$nohup /usr/share/artica-postfix/bin/artica-make --empty-cache >/dev/null 2>&1 &");
	build_progress_manu("{starting}",71);
	shell_exec("$nohup /etc/init.d/monit restart >/dev/null 2>&1 &");
	build_progress_manu("{starting}",72);
	shell_exec("$nohup /etc/init.d/artica-status restart --force >/dev/null 2>&1 &");
	build_progress_manu("{starting}",73);
	shell_exec("$nohup $php /usr/share/artica-postfix/exec.squid.php --build-schedules >/dev/null 2>&1 &");
	build_progress_manu("{starting}",74);
	shell_exec("$nohup $php /usr/share/artica-postfix/exec.schedules.php --defaults >/dev/null 2>&1 &");
	build_progress_manu("{starting}",75);
	$articaver=GetCurrentVersionString();
	if(is_file($squidbin)){
		squid_admin_mysql(1, "Updated Artica v$articaver [action=reload]",null,__FILE__,__LINE__);
		$cmd="/etc/init.d/squid reload --script=".basename(__FILE__)." >/dev/null 2>&1 &";
		system($cmd);
	
	}
	events("done");
	build_progress_manu("{success} v$articaver",100);
	echo "Starting......: ".date("H:i:s")." Done you can close the screen....\n";	
	_artica_update_event(2,"RestartDedicatedServices(): finish",null,__FILE__,__LINE__);
}



function nightly_progress( $download_size, $downloaded_size, $upload_size, $uploaded_size ){
	if(!isset($GLOBALS["previousProgress"])){$GLOBALS["previousProgress"]= 0;}
    
    if ( $download_size == 0 ){
        $progress = 0;
    }else{
        $progress = round( $downloaded_size * 100 / $download_size );
    }
       
    if ( $progress > $GLOBALS["previousProgress"]){
    	echo "Downloading: ". $progress."%, please wait...\n";
    	$GLOBALS["previousProgress"]=$progress;
    }
}
function GetCurrentVersionString(){
	
	return trim(@file_get_contents("/usr/share/artica-postfix/VERSION"));
	
}

function GetCurrentVersion(){
   $result=0;
   $tmpstr=GetCurrentVersionString();
   $tmpstr=str_replace(".", "", $tmpstr);
   $result=intval($tmpstr);
   return $result;
}

function events($text=null){
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}
			
	}
	
	updater_events($text,$sourcefunction,$sourceline);
}
