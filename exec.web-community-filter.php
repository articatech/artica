<?php
$GLOBALS["SCRIPT_SUFFIX"]="--script=".basename(__FILE__);
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(is_file("/usr/bin/cgclassify")){if(is_dir("/cgroups/blkio/php")){shell_exec("/usr/bin/cgclassify -g cpu,cpuset,blkio:php ".getmypid());}}
$GLOBALS["BYCRON"]=false;
$GLOBALS["FORCE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){
		$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
//$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);

include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.squidguard.inc');


$unix=new unix();
if(is_file("/etc/artica-postfix/FROM_ISO")){
	if($unix->file_time_min("/etc/artica-postfix/FROM_ISO")<1){return;}
}


if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--bycron#",implode(" ",$argv))){$GLOBALS["BYCRON"]=true;}

if($argv[1]=="--hypercache"){HyperCache();die();}
if($argv[1]=="--hypercache-websites"){HyperCache_websites();die();}
if($argv[1]=="--register"){register();die();}
if($argv[1]=="--uuid"){uuid_check();die();}
if($argv[1]=="--register-lic"){register_lic();die();}
if($argv[1]=="--register-unveiltech"){register_lic_unveiltech();die();}
if($argv[1]=="--dump-unveiltech"){dump_lic_unveiltech();die();}
if($argv[1]=="--update-unveiltech"){update_unveiltech();die();}
if($argv[1]=="--squid-repo"){squid_updates_table();die();}
if($argv[1]=="--whatsnew"){others_update();die();}
if($argv[1]=="--updates"){others_update();die();}
if($argv[1]=="--force-updates"){$GLOBALS["FORCE"]=true;others_update();die();}



if($argv[1]=="--register-kaspersky"){register_lic_kaspersky();die();}
if($argv[1]=="--category-tickets"){category_tickets();exit;}



if($argv[1]=="--uuid"){$unix=new unix();echo $unix->GetUniqueID()."\n";die();}
if(!ifMustBeExecuted()){die();}
if($argv[1]=="--patterns"){die();}
if($argv[1]=="--sitesinfos"){die();}
if($argv[1]=="--groupby"){die();}
if($argv[1]=="--import"){import();die();}
if($argv[1]=="--export"){export(true);die();}
if($argv[1]=="--export-deleted"){export_deleted_categories(true);die();}
if($argv[1]=="--export-weighted"){Export_Weighted(true);die();}
if($argv[1]=="--export-perso-cats"){ExportPersonalCategories(true);die();}
if($argv[1]=="--export-not-categorized"){ExportNoCategorized(true);die();}
if($argv[1]=="--export-category-tickets"){category_tickets(true);die();}
if($argv[1]=="--drop-categorize"){drop_categorize(true);die();}




	$t=time();
	$sock=new sockets();
	$users=new usersMenus();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if($EnableRemoteStatisticsAppliance==1){if($GLOBALS["VERBOSE"]){echo "Use the Web statistics appliance aborting...\n";}die();}
	$EnableSquidRemoteMySQL=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidRemoteMySQL");
	if(!is_numeric($EnableSquidRemoteMySQL)){$EnableSquidRemoteMySQL=0;}
	if($EnableSquidRemoteMySQL==1){die();}
	
	
	$system_is_overloaded=system_is_overloaded();
	if($system_is_overloaded){
		$unix=new unix();
		WriteMyLogs("Overloaded system, [{$GLOBALS["SYSTEM_INTERNAL_LOAD"]}] Web filtering maintenance databases tasks aborted (general)","MAIN",__FILE__,__LINE__);
		die();
	}
	

	$WebCommunityUpdatePool=$sock->GET_INFO("WebCommunityUpdatePool");
	if(!is_numeric($WebCommunityUpdatePool)){
		$WebCommunityUpdatePool=120;
		$sock->SET_INFO("WebCommunityUpdatePool",120);
	}
	
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$cachetime="/etc/artica-postfix/pids/exec.web-community-filter.php.MAIN.time";
	$unix=new unix();
	$myFile=basename(__FILE__);	
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,$myFile)){
		WriteMyLogs("Already executed PID:$pid, die()",__FUNCTION__,__FILE__,__LINE__);
		die();
	}
	
	$filetime=file_time_min($cachetime);
	if(!$GLOBALS["FORCE"]){
		if($filetime<$WebCommunityUpdatePool){
			WriteMyLogs("{$filetime}Mn need {$WebCommunityUpdatePool}Mn, aborting...",
			__FUNCTION__,__FILE__,__LINE__);
			die();
		}
	}
	
	WriteMyLogs("-> EXECUTE....","MAIN",__FILE__,__LINE__);
	@mkdir(dirname($cachetime),0755,true);
	@unlink($cachetime);
	@file_put_contents($cachetime,time());
	
	$GLOBALS["MYPID"]=getmypid();
	@file_put_contents($pidfile,$GLOBALS["MYPID"]);
	HyperCache();
	WriteMyLogs("-> Export()","MAIN",null,__LINE__);
	Export();
	WriteMyLogs("-> category_tickets()","MAIN",null,__LINE__);
	category_tickets();
	
	WriteMyLogs("-> Import()","MAIN",null,__LINE__);
	import();
	

		
function HyperCache(){
	$sock=new sockets();
	$HyperCacheStoreID=intval($sock->GET_INFO("HyperCacheStoreID"));
	if($HyperCacheStoreID==0){return;}
	$HyperCacheMakeId=HyperCacheMakeId();
	
	
	$uri="https://svb.unveiltech.com/svbgetinfo.php";
	$array["uuid"]=$HyperCacheMakeId;
	
	$curl=new ccurl($uri);
	$curl->parms["uuid"]=$HyperCacheMakeId;
	if(!$curl->get()){echo "FAILED\n";}
	if(preg_match("#\{(.*?)\}#is", $curl->data,$re)){
		$array=json_decode("{{$re[1]}}");
		$FULL["expired"]=$array->expired;
		$FULL["edate"]=$array->edate;
	}
	print_r($FULL);
	if(isset($FULL["expired"])){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/HyperCacheLicStatus", serialize($FULL));
		@chmod("/etc/artica-postfix/settings/Daemons/HyperCacheLicStatus",0755);
	}
	
	
	
}	

function HyperCache_websites(){
	$sock=new sockets();
	$HyperCacheStoreID=intval($sock->GET_INFO("HyperCacheStoreID"));
	if($HyperCacheStoreID==0){return;}
	
	$uri="https://svb.unveiltech.com/svbgetsites.php?mode=serialize";
}



function HyperCacheMakeId(){
	$buffer = "000000000000";

	$handle = popen("ls /dev/disk/by-uuid/", "r");
	while(!feof($handle)) {
		$buffer .= fgets($handle);
	}
	pclose($handle);

	return md5($buffer);
}
	
function category_tickets($asPid=false){
	$unix=new unix();
	$sock=new sockets();
	
	
	if($asPid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$cachetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){WriteMyLogs("Already executed PID:$pid, die()",__FUNCTION__,__FILE__,__LINE__);die();}
		@file_put_contents($pidfile,getmypid());
	}	
	
	
	$q=new mysql_squid_builder();
	$URIBASE=$unix->MAIN_URI();$URIBASE=str_replace("articatech.net", "artica.fr", $URIBASE);
	if(!$q->TABLE_EXISTS("catztickets")){return;}
	$sql="SELECT *  FROM catztickets WHERE `status`=0 LIMIT 0,25";
	$results = $q->QUERY_SQL($sql);
	
	if(!$q->ok){return;}
	if(mysql_num_rows($results)==0){return;}
	$uuid=$unix->GetUniqueID();
	if($uuid==null){
		if($GLOBALS["VERBOSE"]){echo "No system ID !\n";}
		return;}
	$LicenseInfos=unserialize(base64_decode($sock->GET_INFO("LicenseInfos")));
	$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
	if($LicenseInfos["COMPANY"]==null){$LicenseInfos["COMPANY"]=$WizardSavedSettings["company_name"];}
	if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		
		$SITES[$ligne["sitename"]]=true;
		
		$array[]=array(
					"uuid"=>$uuid,
					"zDate"=>$ligne["zDate"],
					"sitename"=>$ligne["sitename"],
					"category"=>$ligne["category"],
					"company"=>$LicenseInfos["COMPANY"],
					"email"=>$LicenseInfos["EMAIL"]);
		
	}
	
	$curl=new ccurl("$URIBASE/shalla-orders.php",false,null);
	$curl->parms["CATEGORIZE_SUPPORT"]=base64_encode(serialize($array));
	if($GLOBALS["VERBOSE"]){$curl->parms["VERBOSE"]=true;}
	$curl->NoLocalProxy();
	$curl->get();
	echo $curl->data;	
	
	if(!preg_match("#GOOD#s", $curl->data)){return;}
	
	
	while (list ($www) = each ($SITES) ){
		$q->QUERY_SQL("UPDATE catztickets SET `status`=1 WHERE `sitename`='$www'");
	}
}


function whatsnew(){
	
	$unix=new unix();
	
	$filetmp=$unix->FILE_TEMP();
	$VERSION=trim(@file_get_contents("/usr/share/artica-postfix/VERSION"));
	$uri="http://articatech.net/v10-whatsnew/$VERSION.txt";
	if(is_file("/usr/share/artica-postfix/ressources/logs/web/$VERSION.txt")){return;}
	
	$curl=new ccurl($uri);
	if(!$curl->GetFile($filetmp)){
		@unlink($filetmp);
		return;
	}
	
	@copy($filetmp, "/usr/share/artica-postfix/ressources/logs/web/$VERSION.txt");
	@unlink($filetmp);
	return;
	
	
}





function build_progress_influx($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/influxdb.refresh.progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}


function others_update(){
	
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$cachetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){
			build_progress_influx("Already executed",110);
			WriteMyLogs("Already executed PID:$pid, die()",__FUNCTION__,__FILE__,__LINE__);
			return;
	}
	
	if(!$GLOBALS["FORCE"]){
		$TimeCache=$unix->file_time_min($cachetime);
		if($TimeCache<30){
			build_progress_influx("Need at least 30Mn ( current {$TimeCache}mn ) ",110);
			return;
		}
	}
	
	
	$TimeFile=$unix->file_time_min("/etc/artica-postfix/settings/Daemons/ArticaTechNetInfluxRepo");
	$tmpfile=$unix->TEMP_DIR()."/squid.update.db";
	if($GLOBALS["VERBOSE"]){$TimeFile=10000;}
	
	
	if(!$GLOBALS["FORCE"]){
		if($TimeFile<480){whatsnew();return;}
	}
	$sock=new sockets();
	
	build_progress_influx("{check_repository} (BigData Engine)",20);
	
	$curl=new ccurl("http://articatech.net/influx.php");

		if(!$curl->GetFile($tmpfile)){
			build_progress_influx("{check_repository} {failed}",20);
			echo $curl->error."\n";
			@unlink($tmpfile);
			squid_admin_mysql(1, "Unable to retreive BigData Engine available versions", $curl->error,__FILE__,__LINE__);
		}
	
	
		$DATA=@file_get_contents($tmpfile);
		$ARRAY=unserialize(base64_decode($DATA));
		if($GLOBALS["VERBOSE"]){print_r($ARRAY);}
	
		if(count($ARRAY)>0){
			build_progress_influx("{check_repository} (BigData Engine) {success}",25);
			@unlink("/etc/artica-postfix/settings/Daemons/ArticaTechNetInfluxRepo");
			@copy("$tmpfile", "/etc/artica-postfix/settings/Daemons/ArticaTechNetInfluxRepo");
			@chmod("/etc/artica-postfix/settings/Daemons/ArticaTechNetInfluxRepo",0755);
			build_progress_influx("{check_repository} {success}",100);
		}else{
			echo "**** NOT AN ARRAY ****\n";
			build_progress_influx("{check_repository} {failed}",20);
		}
	
		@unlink($tmpfile);
	
		build_progress_influx("{check_repository} (Suricata)",30);
		$curl=new ccurl("http://articatech.net/suricata.php");
		if(!$curl->GetFile($tmpfile)){
			build_progress_influx("{check_repository} (Suricata) {failed}",110);
			echo $curl->error."\n";
			@unlink($tmpfile);
			system_admin_mysql(1, "Unable to retreive Suricata available versions", $curl->error,__FILE__,__LINE__);
			squid_admin_mysql(1, "Unable to retreive Suricata available versions", $curl->error,__FILE__,__LINE__);
			
		}
		
		$DATA=@file_get_contents($tmpfile);
		$ARRAY=unserialize(base64_decode($DATA));
		if($GLOBALS["VERBOSE"]){print_r($ARRAY);}
	
		if(count($ARRAY)>0){
			build_progress_influx("{check_repository} (Suricata) {success}",35);
			@unlink("/etc/artica-postfix/settings/Daemons/ArticaTechNetSuricataRepo");
			@copy("$tmpfile", "/etc/artica-postfix/settings/Daemons/ArticaTechNetSuricataRepo");
			@chmod("/etc/artica-postfix/settings/Daemons/ArticaTechNetSuricataRepo",0755);
			build_progress_influx("{check_repository} (Suricata) {success}",40);
		}else{
			echo "**** NOT AN ARRAY ****\n";
			build_progress_influx("{check_repository} (Suricata) {failed}",35);
		}
		
		
//-------------------------------------------------------------------------------------------------------		
		@unlink($tmpfile);
		
		build_progress_influx("{check_repository} (HaProxy)",40);
		$curl=new ccurl("http://articatech.net/haproxy.php");
		if(!$curl->GetFile($tmpfile)){
			build_progress_influx("{check_repository} (HaProxy) {failed}",40);
			echo $curl->error."\n";
			@unlink($tmpfile);
			system_admin_mysql(1, "Unable to retreive HaProxy available versions", $curl->error,__FILE__,__LINE__);
			squid_admin_mysql(1, "Unable to retreive HaProxy available versions", $curl->error,__FILE__,__LINE__);
				
		}
		
		$DATA=@file_get_contents($tmpfile);
		$ARRAY=unserialize(base64_decode($DATA));
		if($GLOBALS["VERBOSE"]){print_r($ARRAY);}
		
		if(count($ARRAY)>0){
			build_progress_influx("{check_repository} (HaProxy) {success}",45);
			@unlink("/etc/artica-postfix/settings/Daemons/ArticaTechNetHaProxyRepo");
			@copy("$tmpfile", "/etc/artica-postfix/settings/Daemons/ArticaTechNetHaProxyRepo");
			@chmod("/etc/artica-postfix/settings/Daemons/ArticaTechNetHaProxyRepo",0755);
		}else{
			echo "**** NOT AN ARRAY ****\n";
			build_progress_influx("{check_repository} (HaProxy) {failed}",45);
		}		
//-------------------------------------------------------------------------------------------------------		
		@unlink($tmpfile);
		
		build_progress_influx("{check_repository} (ProFTPD)",50);
		$curl=new ccurl("http://articatech.net/proftpd.php");
		if(!$curl->GetFile($tmpfile)){
			build_progress_influx("{check_repository} (ProFTPD) {failed}",55);
			echo $curl->error."\n";
			@unlink($tmpfile);
			system_admin_mysql(1, "Unable to retreive ProFTPD available versions", $curl->error,__FILE__,__LINE__);
			squid_admin_mysql(1, "Unable to retreive ProFTPD available versions", $curl->error,__FILE__,__LINE__);
		
		}
		
		$DATA=@file_get_contents($tmpfile);
		$ARRAY=unserialize(base64_decode($DATA));
		if($GLOBALS["VERBOSE"]){print_r($ARRAY);}
		
		if(count($ARRAY)>0){
			build_progress_influx("{check_repository} (ProFTPD) {success}",55);
			@unlink("/etc/artica-postfix/settings/Daemons/ArticaTechNetProFTPDRepo");
			@copy("$tmpfile", "/etc/artica-postfix/settings/Daemons/ArticaTechNetProFTPDRepo");
			@chmod("/etc/artica-postfix/settings/Daemons/ArticaTechNetProFTPDRepo",0755);
		}else{
			echo "**** NOT AN ARRAY ****\n";
			build_progress_influx("{check_repository} (ProFTPD) {failed}",55);
		}
		
//-------------------------------------------------------------------------------------------------------		
		build_progress_influx("{check_repository} (MailSecurity)",50);
		$curl=new ccurl("http://articatech.net/mailsecurity.php");
		if(!$curl->GetFile($tmpfile)){
			build_progress_influx("{check_repository} (MailSecurity) {failed}",55);
			echo $curl->error."\n";
			@unlink($tmpfile);
			system_admin_mysql(1, "Unable to retreive MailSecurity available versions", $curl->error,__FILE__,__LINE__);
			squid_admin_mysql(1, "Unable to retreive MailSecurity available versions", $curl->error,__FILE__,__LINE__);
		
		}
		
		$DATA=@file_get_contents($tmpfile);
		$ARRAY=unserialize(base64_decode($DATA));
		if($GLOBALS["VERBOSE"]){print_r($ARRAY);}
		
		if(count($ARRAY)>0){
			build_progress_influx("{check_repository} (MailSecurity) {success}",55);
			@unlink("/etc/artica-postfix/settings/Daemons/ArticaTechNetMailSecurityRepo");
			@copy("$tmpfile", "/etc/artica-postfix/settings/Daemons/ArticaTechNetMailSecurityRepo");
			@chmod("/etc/artica-postfix/settings/Daemons/ArticaTechNetMailSecurityRepo",0755);
		}else{
			echo "**** NOT AN ARRAY ****\n";
			build_progress_influx("{check_repository} (MailSecurity) {failed}",55);
		}
		
		//-------------------------------------------------------------------------------------------------------
		
		
		
		
	build_progress_influx("{check_repository} Whatsnew",80);
	whatsnew();	
	build_progress_influx("{check_repository} {done}",100);
}


function squid_updates_table(){
	$unix=new unix();
	$sock=new sockets();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	$users=new usersMenus();
	if(!is_file($squidbin)){return;}
	
	
	if(system_is_overloaded(basename(__FILE__))){
		echo "Overloaded\n";
		die();
	}
	
	
	
	$tmpfile=$unix->TEMP_DIR()."/squid.update.db";
	$VERSION=@file_get_contents(dirname(__FILE__)."/VERSION");
	$UUID=$unix->GetUniqueID();
	$SQUID_VERSION=$unix->squid_version();
	$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
	$LICENSE=0;
	if($users->CORP_LICENSE==1){$LICENSE=1;}
	$WizardSavedSettings["LICENSE"]=$LICENSE;
	$WizardSavedSettings["UUID"]=trim($UUID);
	$WizardSavedSettings["SQUID_VERSION"]=trim($SQUID_VERSION);
	$WizardSavedSettings["ARTICA_VERSION"]=trim($VERSION);
	$content=urlencode(base64_encode(serialize($WizardSavedSettings)));
	
	
	$TimeFile=$unix->file_time_min("/etc/artica-postfix/settings/Daemons/ArticaTechNetSquidRepo");
	if($TimeFile>380){
		$curl=new ccurl("http://articatech.net/squid.update.php?content=$content");
		
		if(!$curl->GetFile($tmpfile)){
			@unlink($tmpfile);
			squid_admin_mysql(1, "Unable to retreive Proxy available versions", $curl->error,__FILE__,__LINE__);
			return;
		}
		
		$DATA=@file_get_contents($tmpfile);
		$ARRAY=unserialize(base64_decode($DATA));
		
		if(count($ARRAY)>1){
			@unlink("/etc/artica-postfix/settings/Daemons/ArticaTechNetSquidRepo");
			@copy("$tmpfile", "/etc/artica-postfix/settings/Daemons/ArticaTechNetSquidRepo");
			
		}
		
		@unlink($tmpfile);
	}
		

	
}

	
	
function register(){
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."() in line ". __LINE__."\n";}
	$sock=new sockets();
	$unix=new unix();
	$URIBASE=$unix->MAIN_URI();$URIBASE=str_replace("articatech.net", "artica.fr", $URIBASE);
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$cachetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){WriteMyLogs("Already executed PID:$pid, die()",__FUNCTION__,__FILE__,__LINE__);die();}	
	
	$sock=new sockets();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric("$EnableKerbAuth")){$EnableKerbAuth=0;}
	
	if($GLOBALS["VERBOSE"]){echo "Loading WizardSavedSettings ".__FUNCTION__."() in line ". __LINE__."\n";}
	$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
	$WizardSavedSettingsSend=$sock->GET_INFO("WizardSavedSettingsSend");
	
	
	
	
	if(count($WizardSavedSettings)<2){
		if($GLOBALS["VERBOSE"]){echo "WizardSavedSettings array is less than 2".__FUNCTION__."() in line ". __LINE__."\n";}
		return;
	}
	if(!isset($WizardSavedSettings["company_name"])){$WizardSavedSettings["company_name"]=null;}
	if($WizardSavedSettings["company_name"]==null){
		return;
	}
	
	if(!is_numeric($WizardSavedSettingsSend)){$WizardSavedSettingsSend=0;}
	if($WizardSavedSettingsSend==1){
		if(!$GLOBALS["FORCE"]){
			if($GLOBALS["VERBOSE"]){echo "WizardSavedSettingsSend == 1, aborting.. (use --force)".__FUNCTION__."() in line ". __LINE__."\n";}
		return;}
	}
	
	$uuid=$unix->GetUniqueID();
	if($uuid==null){
		if($GLOBALS["VERBOSE"]){echo "No system ID !\n";}
		return;
	}
	$WizardSavedSettings["ACTIVE_DIRECTORY"]=$EnableKerbAuth;
	
	if($EnableKerbAuth==1){
		include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
		$ldap=new external_ad_search();
		$NET_RPC_INFOS=$ldap->NET_RPC_INFOS();
		while (list ($a, $b) = each ($NET_RPC_INFOS)){$WizardSavedSettings[$a]=$b;}
	}
	
	$WizardSavedSettings["UUID"]=$uuid;
	$WizardSavedSettings["CPUS_NUMBER"]=$unix->CPU_NUMBER();
	$WizardSavedSettings["MEMORY"]=$unix->SYSTEM_GET_MEMORY_MB()."MB";
	$WizardSavedSettings["LINUX_DISTRI"]=$unix->LINUX_DISTRIBUTION();
	$WizardSavedSettings["ARTICAVERSION"]=@file_get_contents("/usr/share/artica-postfix/VERSION");
	$WizardSavedSettings["STATS_APPLIANCE"]=0;
	
	if(is_file("/etc/artica-postfix/STATS_APPLIANCE")){
		$WizardSavedSettings["APPLIANCE"]="Artica Stats Appliance";
		$WizardSavedSettings["STATS_APPLIANCE"]=1;
	}
	
	
	$zarafa_server=$unix->find_program("zarafa-server");
	if(is_file($zarafa_server)){$WizardSavedSettings["ZARAFA APPLIANCE"]="YES";}
	$squid=$unix->find_program("squid");
	if(is_file($squid)){$WizardSavedSettings["PROXY INSTALLED"]="YES";}
	if(is_file("/etc/artica-postfix/FROM_ISO")){$WizardSavedSettings["FROM ISO"]="YES";}
	if(is_file("/etc/artica-postfix/SQUID_APPLIANCE")){$WizardSavedSettings["APPLIANCE"]="Artica Proxy";$WizardSavedSettings["PROXY APPLIANCE"]="YES";}
	if(is_file("/etc/artica-postfix/SAMBA_APPLIANCE")){$WizardSavedSettings["APPLIANCE"]="Artica NAS";$WizardSavedSettings["N.A.S APPLIANCE"]="YES";}
	
	if(is_file("/etc/artica-postfix/artica-iso-first-reboot")){
		$zDate=filemtime("/etc/artica-postfix/artica-iso-first-reboot");
		$WizardSavedSettings["INSTALL_DATE"]=date("Y-m-d H:i:s",$zDate);
	}else{
		$zDate=filemtime("/etc/artica-postfix/.");
		$WizardSavedSettings["INSTALL_DATE"]=date("Y-m-d H:i:s",$zDate);
	}
	
	if(is_file("/etc/artica-postfix/dmidecode.cache.url")){
		$final_array=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/dmidecode.cache.url")));
		while (list ($a, $b) = each ($final_array)){
			$WizardSavedSettings[$a]=$b;
		}
	}
	
	@file_put_contents("/etc/artica-postfix/settings/Daemons/WizardSavedSettings", base64_encode(serialize($WizardSavedSettings)));
	
	if($GLOBALS["VERBOSE"]){echo "Send order to $URIBASE/shalla-orders.php ".__FUNCTION__."() in line ". __LINE__."\n";}
	$curl=new ccurl("$URIBASE/shalla-orders.php",false,null);
	$curl->parms["REGISTER"]=base64_encode(serialize($WizardSavedSettings));
	if($GLOBALS["VERBOSE"]){$curl->parms["VERBOSE"]=true;}
	$curl->NoLocalProxy();
	$curl->get();
	if($GLOBALS["VERBOSE"]){echo $curl->data;}
	
	if(preg_match("#GOOD#s", $curl->data)){
		$sock->SET_INFO("WizardSavedSettingsSend", 1);
	}
	
	
}	

function XZCPU_NUMBER(){
	$unix=new unix();
	$cat=$unix->find_program("cat");
	$grep=$unix->find_program("grep");
	$cut=$unix->find_program("cut");
	$wc=$unix->find_program("wc");
	$cmd="$cat /proc/cpuinfo |$grep \"model name\" |$cut -d: -f2|$wc -l 2>&1";
	$CPUNUM=exec($cmd);
	
	return $CPUNUM;
}

function uuid_check(){
	$unix=new unix();
	$uuid=$unix->GetUniqueID();
	echo $uuid."\n";
}

function CheckLic($array1=array(),$array2=array()){
	$WORKDIR=base64_decode("L3Vzci9sb2NhbC9zaGFyZS9hcnRpY2E=");
	$WORKFILE=base64_decode('LmxpYw==');
	$function_ghost=base64_decode("Y2hlY2tsaWNlbnNlLmJpbg==");
	$line_ghost=base64_decode("MTA4NDM=");
	$textGhost=base64_decode("Q29ycG9yYXRlIExpY2Vuc2UgZXhwaXJlZCAtIHJldHVybiBiYWNrIHRvIENvbW11bml0eSBsaWNlbnNl");
	$verifailed=base64_decode("Q29ycG9yYXRlIExpY2Vuc2UgZXhwaXJlZCAtIHJldHVybiBiYWNrIHRvIENvbW11bml0eSBsaWNlbnNl");
	$licexpir=base64_decode("e2xpY2Vuc2VfZXhwaXJlZH0=");
	$WORKPATH="$WORKDIR/$WORKFILE";
	$unix=new unix();
	$URIBASE=$unix->MAIN_URI();
	$URIBASE=str_replace("articatech.net", "artica.fr", $URIBASE);
	$sock=new sockets();	
	build_progress("{license_active}: Verify validation...",90);
	$curl=new ccurl("$URIBASE/shalla-orders.php",false,null);
	$curl->parms["REGISTER-LIC"]=base64_encode(serialize($array1));
	$curl->parms["REGISTER-OLD"]=base64_encode(serialize($array2));
	$curl->get();
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	
	$finaltime=0;
	$badmail=0;
	
	if(preg_match("#<FINALTIME>([0-9]+)</FINALTIME>#s", $curl->data,$re)){
		$finaltime=intval($re[1]);
	}
	if($finaltime>0){
		if(time()>$finaltime){
			if(is_file($WORKPATH)){
				@unlink($WORKPATH);
				build_progress($verifailed,110);
				$array1["license_status"]=$licexpir;
				$array1["license_number"]=null;
				$array1["UNLOCKLIC"]=null;
				$array1["TIME"]=time();
				if($finaltime>0){$LicenseInfos["FINAL_TIME"]=$finaltime;}
				squid_admin_mysql(0, $textGhost, null,$function_ghost,$line_ghost);
				shell_exec("/usr/share/artica-postfix/bin/process1 --force --verbose");
				if(is_file($squidbin)){
					shell_exec("$php /usr/share/artica-postfix/exec.squid.php --build");
					shell_exec("/etc/init.d/squid restart {$GLOBALS["SCRIPT_SUFFIX"]}");
				}
				$sock->SaveConfigFile(base64_encode(serialize($array1)), "LicenseInfos");
			}
			return;
			
		}
	}
	

	if(preg_match("#REGISTRATION_DELETE_NOW#s", $curl->data,$re)){
		@unlink($WORKPATH);
		build_progress("{license_invalid}: Verify validation failed...",110);
		$array1["license_status"]="{license_invalid}";
		$array1["license_number"]=null;
		$array1["UNLOCKLIC"]=null;
		$array1["TIME"]=time();
		$sock->SaveConfigFile(base64_encode(serialize($array1)), "LicenseInfos");
		return;
	}	
	
}

function dump_lic_unveiltech(){
	$sock=new sockets();
	$json=json_decode(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/UtDNSArticaUser")));
	$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
	$LicenseInfos=unserialize(base64_decode($sock->GET_INFO("LicenseInfos")));
	$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
	$smtp_domainname=$WizardSavedSettings["smtp_domainname"];
	$telephone=$WizardSavedSettings["telephone"];
	if($LicenseInfos["COMPANY"]==null){$LicenseInfos["COMPANY"]=$WizardSavedSettings["company_name"];}
	if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}
	$ldap=new clladp();
	$password=md5($ldap->ldap_password);
	echo "Is True: $json->success\n";
	echo "Is hash: $json->hash\n";
	echo "Is id: $json->id\n";
	echo "interface is https://myaccount.unveiltech.com/checklogin.php?email={$LicenseInfos["EMAIL"]}&pwd=$password&goto=https://utdns.unveiltech.com/dashboard.php?hash=$json->hash\n";
	
	
}

function update_unveiltech(){
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/UtDNSArticaUser")){return;}
	
	$sock=new sockets();
	$unix=new unix();
	$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
	$LicenseInfos=unserialize(base64_decode($sock->GET_INFO("LicenseInfos")));
	$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
	$smtp_domainname=$WizardSavedSettings["smtp_domainname"];
	$telephone=$WizardSavedSettings["telephone"];
	if($LicenseInfos["COMPANY"]==null){$LicenseInfos["COMPANY"]=$WizardSavedSettings["company_name"];}
	if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}
	if(!is_numeric($LicenseInfos["EMPLOYEES"])){$LicenseInfos["EMPLOYEES"]=$WizardSavedSettings["employees"];}
	
	
	
	$UtDNSIPAddr=$sock->GET_INFO("UtDNSIPAddr");
	if($UtDNSIPAddr==null){
		$nets=$unix->NETWORK_ALL_INTERFACES();
		$ipaddr=ip2long($nets["eth0"]["IPADDR"]);
	}else{
		$ipaddr=ip2long($UtDNSIPAddr);	
	}
	
	$json=json_decode(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/UtDNSArticaUser")));
	$ldap=new clladp();
	$password=md5($ldap->ldap_password);
	$uri="https://utdns.unveiltech.com/utdnsarticaupdate.php";
	
	
	$curl=new ccurl($uri);
	$curl->parms["fname"]=null;
	$curl->parms["lname"]=null;
	$curl->parms["email"]=$LicenseInfos["EMAIL"];
	$curl->parms["phone"]=$telephone;
	$curl->parms["company"]=$LicenseInfos["COMPANY"];
	$curl->parms["pwd"]=$password;
	$curl->parms["website"]=$smtp_domainname;
	$curl->parms["fakeip"]=$ipaddr;
	$curl->parms["users"]=$LicenseInfos["EMPLOYEES"];
	$curl->parms["hash"]=$json->hash;
	$curl->parms["id"]=$json->id;

	if(!$curl->get()){
		echo "Failed!\nFailed whith Internet error \"$curl->error\"\n";
		return;
	}
	
	
	$return=intval(trim($curl->data));
	if($return==1){echo "Success\n";}
	
	
}

function register_lic_unveiltech(){
	$sock=new sockets();
	$unix=new unix();
	$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
	$LicenseInfos=unserialize(base64_decode($sock->GET_INFO("LicenseInfos")));
	$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
	$smtp_domainname=$WizardSavedSettings["smtp_domainname"];
	$telephone=$WizardSavedSettings["telephone"];
	if($LicenseInfos["COMPANY"]==null){$LicenseInfos["COMPANY"]=$WizardSavedSettings["company_name"];}
	if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}
	if(!is_numeric($LicenseInfos["EMPLOYEES"])){$LicenseInfos["EMPLOYEES"]=$WizardSavedSettings["employees"];}
	
	$nets=$unix->NETWORK_ALL_INTERFACES();
	$ipaddr=ip2long($nets["eth0"]["IPADDR"]);
	
/*	Frederic Testa: fname: prenom
	Frederic Testa: lname: nom de famille
	Frederic Testa: email: adresse email
	Frederic Testa: phone: numero de tel.
	Frederic Testa: company: societe
	Frederic Testa: pwd: mot de passe en clair encapsulй dans un base64
	Frederic Testa: ex: base64_encode("toto");
	Frederic Testa: website: site web du gars
	Frederic Testa: ipv4l: ip public en numerique	
	
	*/
	
	$ldap=new clladp();
	$password=base64_encode($ldap->ldap_password);
	$uri="https://utdns.unveiltech.com/utdnsarticanewuser.php";
	
	$curl=new ccurl($uri);
	$curl->parms["fname"]=null;
	$curl->parms["lname"]=null;
	$curl->parms["email"]=$LicenseInfos["EMAIL"];
	$curl->parms["phone"]=$telephone;
	$curl->parms["company"]=$LicenseInfos["COMPANY"];
	$curl->parms["pwd"]=$password;
	$curl->parms["website"]=$smtp_domainname;
	$curl->parms["fakeip"]=$ipaddr;
	$curl->parms["users"]=$LicenseInfos["EMPLOYEES"];

	
	if(!$curl->get()){
		echo "Failed!\nFailed whith Internet error \"$curl->error\"\n";
		return;
	}
	
	
	if(!preg_match("#<ANSWER>(.+?)</ANSWER>#is", $curl->data,$re)){
		echo "Failed!\nProtocol error with the remote cloud server\n";
		return;
	}
	
	
	$json=json_decode(base64_decode($re[1]));
	if($json->success){
		echo "Success!\nThe remote cloud server as accepted the request\n";
		@file_put_contents("/etc/artica-postfix/settings/Daemons/UtDNSArticaUser", $re[1]);
		@chmod("/etc/artica-postfix/settings/Daemons/UtDNSArticaUser",0755);
	}else{
		echo "Failed!\nThe remote cloud server as refused the request\n";
	}
	
	
	
	
	//https://utdns.unveiltech.com/utdnsarticagetdns.php
	
	
	// {'success':'true/false', 'id':'XX', 'hash':'sdflsdkjflskjflsfl',prim:123, sec:456}
}


function register_lic_kaspersky(){
	$sock=new sockets();
	$unix=new unix();
	$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::".__LINE__."\n";}
	$uuid=$unix->GetUniqueID();
	if($GLOBALS["VERBOSE"]){echo " *************** \n\n UUID : $uuid \n\n ***************\n";}
	if($uuid==null){
		if($GLOBALS["VERBOSE"]){echo "No system ID !\n";}
		return;
	}
	$WizardSavedSettings["UUID"]=$uuid;
	$unix=new unix();
	$URIBASE=$unix->MAIN_URI();$URIBASE=str_replace("articatech.net", "artica.fr", $URIBASE);
	$verbosed=null;
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$KasperskyAskQuoteResults=$sock->GET_INFO("KasperskyAskQuoteResults");
	if($KasperskyAskQuoteResults=="KEY_OK"){return;}
	
	if(is_file("/etc/artica-postfix/artica-iso-first-reboot")){
		$zDate=filemtime("/etc/artica-postfix/artica-iso-first-reboot");
		$WizardSavedSettings["INSTALL_DATE"]=date("Y-m-d H:i:s",$zDate);
	}else{
		$zDate=filemtime("/etc/artica-postfix/.");
		$WizardSavedSettings["INSTALL_DATE"]=date("Y-m-d H:i:s",$zDate);
	}	
	
	
	$curl=new ccurl("$URIBASE/shalla-orders.php$verbosed",false,null);
	$curl->NoLocalProxy();
	if($GLOBALS["VERBOSE"]){$curl->parms["VERBOSE"]=yes;}
	$curl->parms["REGISTER_KAV4PROXY"]=base64_encode(serialize($WizardSavedSettings));
	$curl->get();
	if($GLOBALS["VERBOSE"]){echo "***** $curl->data ****\n";}
	
	if(preg_match("#INVALID_TEMP_LICENSE#is", $curl->data)){
		$sock->SET_INFO("KasperskyAskQuoteResults", "INVALID_TEMP_LICENSE");
		return false;
	}
	if(preg_match("#CLOUD_ERROR#is", $curl->data)){
		$sock->SET_INFO("KasperskyAskQuoteResults", "CLOUD_ERROR");
		return false;
	}	
	
	if(preg_match("#TEMP_LICENSE=\[(.+?)\]#is",$curl->data,$re)){
		$sock->SET_INFO("KasperskyAskQuoteResults", "waiting_order");
		$WizardSavedSettings["license_kav4Proxy_temp"]=$re[1];
		$sock->SaveConfigFile(base64_encode(serialize($WizardSavedSettings)), "WizardSavedSettings");
		return true;
	}
	
	if(preg_match("#<LICENSE_K>(.*?)</LICENSE_K>#is", $curl->data,$re)){
		$ldata=$re[1];
		@file_put_contents("/tmp/kl", $ldata);
		exec("/opt/kaspersky/kav4proxy/bin/kav4proxy-licensemanager -a /tmp/kl 2>&1",$results);
		@unlink("/tmp/kl");
		while (list ($table, $none) = each ($results) ){
			if(!preg_match("#successfully registered#", $none)){
				if($GLOBALS["VERBOSE"]){echo "$none\n";}
				continue;
			}
			$sock->SET_INFO("KasperskyAskQuoteResults", "KEY_OK");
			shell_exec("$php /usr/share/artica-postfix/exec.keepup2date.php --update");
			shell_exec("$nohup $php /usr/share/artica-postfix/exec.kav4proxy.php --license --force >/dev/null 2>&1 &");
			break;
		}
		
		
	}
	
	
	
	
	
}

function build_progress($text,$pourc){
	$cachefile="/usr/share/artica-postfix/ressources/logs/artica.license.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}


function register_lic(){
	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	$sock=new sockets();
	$unix=new unix();
	$ini=new Bs_IniHandler();
	$function_ghost=base64_decode("Y2hlY2tsaWNlbnNlLmJpbg==");
	$error1=base64_decode("e2dlbmVyaWNfaHR0cF9lcnJvcn0=");
	$textGhost=base64_decode("Q29ycG9yYXRlIExpY2Vuc2UgZXhwaXJlZCAtIHJldHVybiBiYWNrIHRvIENvbW11bml0eSBsaWNlbnNl");
	$verifailed=base64_decode("Q29ycG9yYXRlIExpY2Vuc2UgZXhwaXJlZCAtIHJldHVybiBiYWNrIHRvIENvbW11bml0eSBsaWNlbnNl");
	$licexpir=base64_decode("e2xpY2Vuc2VfZXhwaXJlZH0=");
	$line_ghost=base64_decode("MTA4NDM=");
	$datas=$sock->GET_INFO("ArticaProxySettings");
	$squidbin=$unix->LOCATE_SQUID_BIN();
	$php=$unix->LOCATE_PHP5_BIN();
	$ArticaProxyServerEnabled=null;
	
	if(trim($datas)<>null){
		$ini->loadString($datas);
		$ArticaProxyServerEnabled=$ini->_params["PROXY"]["ArticaProxyServerEnabled"];
		if($ArticaProxyServerEnabled==1){$ArticaProxyServerEnabled=="yes";}
	}
	
	echo "Use a Proxy server: $ArticaProxyServerEnabled\n";
	
	
	$WORKDIR=base64_decode("L3Vzci9sb2NhbC9zaGFyZS9hcnRpY2E=");
	$WORKFILE=base64_decode('LmxpYw==');
	$WORKPATH="$WORKDIR/$WORKFILE";
	$nohup=$unix->find_program("nohup");
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::".__LINE__."\n";}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$cachetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){
		build_progress("License information: Already executed PID:$pid, die()",100);
		die();
	}
	
	build_progress("Building informations...",10);
	
	
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	$cmdADD=null;
	if($EnableRemoteStatisticsAppliance==1){
		$cmdADD="$nohup ".$unix->LOCATE_PHP5_BIN()." ".dirname(__FILE__)."/exec.netagent.php >/dev/null 2>&1 &";
	}
	$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
	
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::".__LINE__."\n";}
	$LicenseInfos=unserialize(base64_decode($sock->GET_INFO("LicenseInfos")));
	if(!isset($LicenseInfos["COMPANY"])){$LicenseInfos["COMPANY"]=null;}
	if(!isset($LicenseInfos["GoldKey"])){$LicenseInfos["GoldKey"]=null;}
	if(!isset($LicenseInfos["REGISTER"])){$LicenseInfos["REGISTER"]=null;}
	if(!isset($LicenseInfos["GoldKey"])){$LicenseInfos["GoldKey"]=null;}
	if(!isset($WizardSavedSettings["GoldKey"])){$WizardSavedSettings["GoldKey"]=null;}
	$LicenseInfos["STATS_APPLIANCE"]=0;
	
	
	$LicenseInfos["COMPANY"]=str_replace("%uFFFD", "é", $LicenseInfos["COMPANY"]);
	if($WizardSavedSettings["GoldKey"]<>null){
		if($sock->IsGoldKey($WizardSavedSettings["GoldKey"])){
			if($LicenseInfos["GoldKey"]==null){
				echo "Gold Key: {$WizardSavedSettings["GoldKey"]}\n";
				$LicenseInfos["GoldKey"]=$WizardSavedSettings["GoldKey"];
			}
		}
	}
	
	echo "Registered for {$LicenseInfos["COMPANY"]}\n";
	build_progress("Check information {$LicenseInfos["COMPANY"]}",20);
	
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::".__LINE__."\n";}
	$uuid=$unix->GetUniqueID();
	
	build_progress("Check information $uuid",30);
	
	echo " *************** \n\n UUID : $uuid \n\n ***************\n";
	if($uuid==null){
		build_progress("No system ID !",110);
		return;
	}
	
	
	
	if(!is_numeric($LicenseInfos["REGISTER"])){
		echo "License information: server is not registered\n";
	}
	if($LicenseInfos["REGISTER"]<>1){
		echo "License information: server is not registered\n";
		register();
		build_progress("License information: server is not registered",110);
		echo "Please, restart again...";
		die();
	}	
	
	
	$LicenseInfos["UUID"]=$uuid;
	$LicenseInfos["ARTICAVERSION"]=@file_get_contents("/usr/share/artica-postfix/VERSION");
	if(is_file("/etc/artica-postfix/STATS_APPLIANCE")){$LicenseInfos["STATS_APPLIANCE"]=1;}
	echo "License information: Artica v{$LicenseInfos["ARTICAVERSION"]}\n";
	

	//if($GLOBALS["VERBOSE"]){$curl->parms["VERBOSE"]="yes";}
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::".__LINE__."\n";}
	if(!isset($LicenseInfos["license_number"])){$LicenseInfos["license_number"]=null;}
	if($LicenseInfos["license_number"]=="--"){$LicenseInfos["license_number"]=null;}
	if(strpos($LicenseInfos["license_number"], "(")>0){$LicenseInfos["license_number"]=null;}
	echo "License number:{$LicenseInfos["license_number"]}\n";
	
	@mkdir($WORKDIR,640,true);
	
	
	if(isset($LicenseInfos["UNLOCKLIC"])){
		if(strlen($LicenseInfos["UNLOCKLIC"])>4){
			if(isset($LicenseInfos["license_number"])){
				if(strlen($LicenseInfos["license_number"])>4){
					$manulic=aef00vh567($uuid)."-".aef00vh567($LicenseInfos["license_number"]);
					if($manulic==$LicenseInfos["UNLOCKLIC"]){
						@file_put_contents($WORKPATH, "TRUE");
						echo "License number: License is Active [OK] by the Unlock License\n";
						$LicenseInfos["license_status"]="{license_active}";
						$LicenseInfos["TIME"]=time();
						$sock->SaveConfigFile(base64_encode(serialize($LicenseInfos)), "LicenseInfos");
						if($cmdADD<>null){shell_exec($cmdADD);}
						build_progress("{license_active}",80);
						CheckLic($LicenseInfos,$WizardSavedSettings);
						build_progress("{done}",100);
						return;
					}else{
						echo "{$LicenseInfos["UNLOCKLIC"]} did not match license!\n";
					}
				}else{
					echo "license_number token not set..\n";
				}
			}
				
		}
	}else{
		echo "License number: UNLOCKLIC not set...\n";
	}	
	$unix=new unix();
	$URIBASE=$unix->MAIN_URI();
	$URIBASE=str_replace("articatech.net", "artica.fr", $URIBASE);
	
	$verbosed="?VERBOSE=yes&time=".time();
	echo "Checking license on the cloud server\n";
	build_progress("Checking license on the cloud server...",40);
	echo "Contacting $URIBASE\n";
	$curl=new ccurl("$URIBASE/shalla-orders.php",false,null);
	
	
	
	
	if($GLOBALS["VERBOSE"]){$curl->parms["VERBOSE"]=yes;}
	$curl->parms["REGISTER-LIC"]=base64_encode(serialize($LicenseInfos));
	$curl->parms["REGISTER-OLD"]=base64_encode(serialize($WizardSavedSettings));
	echo "Send request... please wait...\n";
	if(!$curl->get()){
		echo "**********************************\n";
		echo "*\n";
		echo "* Failed to contact cloud server..*\n";
		echo "*\n";
		echo "**********************************\n";
		build_progress("Failed to contact cloud server",110);
		echo "HTTP Error returned: ".$curl->error."\n";
		echo "**********************************\n";
		echo "*\n";
		echo @implode("\n", $curl->errors);
		echo "*\n";
		echo "**********************************\n";
		$LicenseInfos["TIME"]=time();
		$LicenseInfos["license_status"]="{registration_failed} $curl->error";
		$sock->SaveConfigFile(base64_encode(serialize($LicenseInfos)), "LicenseInfos");
		if(!is_file("/etc/artica-postfix/REGISTRATION_FAILED_TIME")){
			@file_put_contents("/etc/artica-postfix/REGISTRATION_FAILED_TIME", time());
			$sock->SaveConfigFile(base64_encode(serialize($LicenseInfos)), "LicenseInfos");
			return;
		}
		
		$LastTime=intval(@file_get_contents("/etc/artica-postfix/REGISTRATION_FAILED_TIME"));
		if($LastTime==0){
			
			@file_put_contents("/etc/artica-postfix/REGISTRATION_FAILED_TIME", time());
			$sock->SaveConfigFile(base64_encode(serialize($LicenseInfos)), "LicenseInfos");
			return;
		}
		$TimeMin=$unix->time_min($LastTime);
		if($TimeMin>10080){
			if(is_file($WORKPATH)){
				echo "Failed expired license ($error1)\n";
				@unlink($WORKPATH);
				squid_admin_mysql(0, base64_decode("Q2Fubm90IGNvbnRhY3QgY2xvdWQgc2VydmVyIHNpbmNlIDcgZGF5cywgdHVybiB0byBDb21tdW5pdHkgRWRpdGlvbg=="),$function_ghost,$line_ghost);
				$LicenseInfos["license_status"]=$error1;
				$LicenseInfos["license_number"]="";
				$LicenseInfos["TIME"]=time();
				$sock->SaveConfigFile(base64_encode(serialize($LicenseInfos)), "LicenseInfos");
				shell_exec("/usr/share/artica-postfix/bin/process1 --force --verbose ".time()." >/dev/null 2>&1");
			}
		}
		
		return;
		
	}
	
	
	
	@file_put_contents("/etc/artica-postfix/REGISTRATION_FAILED_TIME", 0);
	echo "OK the server as been contacted....\n";
	build_progress("{checkiccloud1}",50);
	//if($GLOBALS["VERBOSE"]){echo "***** $curl->data ****\n";}
	
	$finaltime=0;
	if(preg_match("#<FINALTIME>([0-9]+)</FINALTIME>#s", $curl->data,$re)){$finaltime=$re[1];}
	
	echo "OK the server as been contacted....[".__LINE__."]\n";
	
	if($finaltime>0){
		if(time()>$finaltime){
			if(is_file($WORKPATH)){
				@unlink($WORKPATH);
				build_progress($verifailed,110);
				echo "license_status: $licexpir.... (Expired) [".__LINE__."]\n";
				$array1["license_status"]=$licexpir;
				$array1["license_number"]=null;
				$array1["UNLOCKLIC"]=null;
				$array1["TIME"]=time();
				if($finaltime>0){$LicenseInfos["FINAL_TIME"]=$finaltime;}
				$sock->SaveConfigFile(base64_encode(serialize($LicenseInfos)), "LicenseInfos");
				squid_admin_mysql(0, $textGhost, null,$function_ghost,$line_ghost);
				shell_exec("/usr/share/artica-postfix/bin/process1 --force --verbose");
				if(is_file($squidbin)){
					shell_exec("$php /usr/share/artica-postfix/exec.squid.php --build");
					shell_exec("$php /usr/share/artica-postfix/exec.kerbauth.php --disconnect");
					shell_exec("/etc/init.d/squid restart {$GLOBALS["SCRIPT_SUFFIX"]}");
				}
				$sock->SaveConfigFile(base64_encode(serialize($array1)), "LicenseInfos");
			}
			return;
		}
	}	
	
	if(preg_match("#<BADMAIL>(0|1)</BADMAIL>#s", $curl->data,$re)){
		echo "***** EMAIL *****\n";
		$sock->SET_INFO("RegisterCloudBadEmail", $re[1]);		
	}
	
	echo "OK Analyze answer from the cloud server....[".__LINE__."]\n";
	
	if(preg_match("#REGISTRATION_OK:\[(.+?)\]#s", $curl->data,$re)){
			echo "OK Registration, waiting an active license....[".__LINE__."]\n";
			build_progress("{waiting_approval} {success}",100);
			$LicenseInfos["license_status"]="{waiting_approval}";
			$LicenseInfos["license_number"]=$re[1];
			$LicenseInfos["TIME"]=time();
			if($finaltime>0){$LicenseInfos["FINAL_TIME"]=$finaltime;}
			$sock->SaveConfigFile(base64_encode(serialize($LicenseInfos)), "LicenseInfos");
			@unlink($WORKPATH);
			if($cmdADD<>null){shell_exec($cmdADD);}
			shell_exec("/usr/share/artica-postfix/bin/process1 --force --verbose ".time()." >/dev/null 2>&1");
			return;
	}
	
	
	echo "Data Lenght: ".strlen($curl->data)." bytes\n";
	if(strlen($curl->data)==0){
		build_progress("{error} O Size byte",110);
		return;
	}
	
	
	if(preg_match("#LICENSE_OK:\[(.+?)\]#s", $curl->data,$re)){
			echo "**********************************************\n";
			echo "*\n";
			echo "*\n";
			echo "*              Congratulations\n";
			echo "*        Your License is  - Active -\n";
			echo "*\n";
			echo "*\n";
			echo "**********************************************\n";
			echo "OK License is Active....[".__LINE__."]\n";
			@file_put_contents($WORKPATH, "TRUE");
			if($finaltime>0){$LicenseInfos["FINAL_TIME"]=$finaltime;}
			$LicenseInfos["license_status"]="{license_active}";
			$LicenseInfos["TIME"]=time();
			$sock->SaveConfigFile(base64_encode(serialize($LicenseInfos)), "LicenseInfos");
			if($cmdADD<>null){shell_exec($cmdADD);}
			build_progress("{license_active} {refresh}",100);
			system("/usr/share/artica-postfix/bin/process1 --force --verbose --".time());
			build_progress("{license_active} {success}",100);
			return;
	}
	if(preg_match("#REGISTRATION_INVALID#s", $curl->data,$re)){
		@unlink($WORKPATH);
		echo "**********************************************\n";
		echo "*\n";
		echo "*\n";
		echo "*       ****    REGISTRATION_INVALID      ****\n";
		echo "*\n";
		echo "*\n";
		echo "**********************************************\n";
		echo "Invalid License.......[".__LINE__."]\n";
		$LicenseInfos["license_status"]="{community_license}";
		$LicenseInfos["license_number"]=null;
		$LicenseInfos["UNLOCKLIC"]=null;
		$LicenseInfos["TIME"]=time();
		if($finaltime>0){$LicenseInfos["FINAL_TIME"]=$finaltime;}
		build_progress("Community Edition - limited...",100);
		$sock->SaveConfigFile(base64_encode(serialize($LicenseInfos)), "LicenseInfos");
		if($cmdADD<>null){shell_exec($cmdADD);}
		$unix->Process1(true);
		return;
	}	

	if(preg_match("#REGISTRATION_DELETE_NOW#s", $curl->data,$re)){
		echo "***** REGISTRATION_DELETE_NOW ****\n";
		@unlink($WORKPATH);
		$LicenseInfos["license_status"]="Community Edition - limited";
		$LicenseInfos["license_number"]=null;
		$LicenseInfos["UNLOCKLIC"]=null;
		$LicenseInfos["TIME"]=time();
		if($finaltime>0){$LicenseInfos["FINAL_TIME"]=$finaltime;}
		$unix->Process1(true);
		$sock->SaveConfigFile(base64_encode(serialize($LicenseInfos)), "LicenseInfos");
		shell_exec("/usr/share/artica-postfix/bin/process1 --force --verbose ".time()." >/dev/null 2>&1");
		build_progress("Community Edition - limited",110);
		return;
	}	
		
	if($curl->error<>null){
		system_admin_events("License registration failed with error $curl->error", "GetLicense", "license", 0, "license");
		
	}
	
	build_progress("Unknown registration?",110);
	@unlink($WORKPATH);
	$LicenseInfos["license_status"]="Community Edition - limited";
	$LicenseInfos["license_number"]=null;
	$LicenseInfos["UNLOCKLIC"]=null;
	$LicenseInfos["TIME"]=time();
	if($finaltime>0){$LicenseInfos["FINAL_TIME"]=$finaltime;}
	echo "***** Registration_failed ****\n";
	$LicenseInfos["TIME"]=time();
	$unix->Process1(true);
	$sock->SaveConfigFile(base64_encode(serialize($LicenseInfos)), "LicenseInfos");
	shell_exec("/usr/share/artica-postfix/bin/process1 --force --verbose ".time()." >/dev/null 2>&1");
	if($cmdADD<>null){shell_exec($cmdADD);}
}
	
function ExportPersonalCategories($asPid=false){
	$unix=new unix();
	$restartProcess=false;
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$restart_cmd=trim("$nohup $php5 ".__FILE__." --export >/dev/null 2>&1 &");
	$sock=new sockets();
	$uuid=$unix->GetUniqueID();
	if($uuid==null){
		if($GLOBALS["VERBOSE"]){echo "No system ID !\n";}
		return;
	}
	
	if($asPid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$cachetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
		$unix=new unix();	
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){WriteMyLogs("Already executed PID:$pid, die()",__FUNCTION__,__FILE__,__LINE__);die();}	
		@file_put_contents($pidfile,getmypid());
	}

	$q=new mysql_squid_builder();
	$sql="SELECT * FROM personal_categories WHERE sended=0";
	$results=$q->QUERY_SQL($sql);
	if(mysql_num_rows($results)==0){return;}
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$PERSONALSCATS[$ligne["category"]]["DESC"]=$ligne["category_description"];
		$PERSONALSCATS[$ligne["category"]]["UUID"]=$uuid;
	}	
	
	WriteMyLogs("Exporting ". count($PERSONALSCATS)." personal category",__FUNCTION__,__FILE__,__LINE__);
	$f=base64_encode(serialize($PERSONALSCATS));
	$unix=new unix();
	$URIBASE=$unix->MAIN_URI();
	$URIBASE=str_replace("articatech.net", "artica.fr", $URIBASE);
	$curl=new ccurl("$URIBASE/shalla-orders.php",false,null);
	$curl->parms["PERSO_CAT_POST"]=$f;

	if(!$curl->get()){
		writelogs("Failed exporting ".count($PERSONALSCATS)." personal categories to Artica cloud repository servers",__FUNCTION__,__FILE__,__LINE__);
		$unix->send_email_events("Failed exporting ".count($PERSONALSCATS)." personal categories to Artica cloud repository servers",null,"proxy");
		writelogs_squid("Failed exporting ".count($PERSONALSCATS)." personal categories to Artica cloud repository servers \"$curl->error\"",__FUNCTION__,__FILE__,__LINE__,"export");
		return null;
	}

	if(preg_match("#<ANSWER>OK</ANSWER>#is",$curl->data)){
		WriteMyLogs("Exporting success ". count($PERSONALSCATS)." personal categories",__FUNCTION__,__FILE__,__LINE__);
		writelogs_squid("Success exporting ".count($PERSONALSCATS)." personal categories to Artica cloud repository servers",__FUNCTION__,__FILE__,__LINE__,"export");	
		$q->QUERY_SQL("UPDATE personal_categories SET sended=1 WHERE sended=0");
	}
	
	
}	

function export_deleted_categories($asPid=false){
return;
}

function ExportNoCategorized($asPid=false){

	$unix=new unix();
	
	if($asPid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$cachetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
		$unix=new unix();	
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){WriteMyLogs("Already executed PID:$pid, die()",__FUNCTION__,__FILE__,__LINE__);die();}	
		@file_put_contents($pidfile,getmypid());
	}	
	$array=array();
	$sock=new sockets();
	$uuid=$unix->GetUniqueID();
	$ArticaDBPath=$sock->GET_INFO("ArticaDBPath");
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	$WizardStatsAppliance=unserialize(base64_decode($sock->GET_INFO("WizardStatsAppliance")));
	if(!isset($WizardStatsAppliance["SERVER"])){$WizardStatsAppliance["SERVER"]=null;}
	
	if($ArticaDBPath==null){$ArticaDBPath="/opt/articatech";}
	if($DisableArticaProxyStatistics==1){
		if($GLOBALS["VERBOSE"]){echo "COMMUNITY_POST_VISITED = DisableArticaProxyStatistics=$DisableArticaProxyStatistics\n";}
		return;
	}
	
	if(!is_file("$ArticaDBPath/data/catz/category_porn.MYI")){
		if($GLOBALS["VERBOSE"]){echo "COMMUNITY_POST_VISITED = $ArticaDBPath/data/catz/category_porn.MYI no such file\n";}
	}
	
	if($WizardStatsAppliance["SERVER"]<>null){return;}
	
	if($uuid==null){
		if($GLOBALS["VERBOSE"]){echo "No system ID !\n";}
		return;
	}
	
	
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM visited_sites WHERE LENGTH(category)=0 AND NotVisitedSended=0 LIMIT 0,5000";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){if(strpos($q->mysql_error, "Unknown column 'NotVisitedSended'")>0){$q->CheckTables();}$results=$q->QUERY_SQL($sql);}
	if(mysql_num_rows($results)>0){
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$md5=md5("$uuid{$ligne["sitename"]}{$ligne["familysite"]}");
			$ligne["sitename"]=mysql_escape_string2($ligne["sitename"]);
			$ligne["familysite"]=mysql_escape_string2($ligne["familysite"]);
			$array[]="('$md5','$uuid','{$ligne["sitename"]}','{$ligne["HitsNumber"]}','{$ligne["familysite"]}')";
			
		}
	
	}
	
	$EnableArticaMetaServer=intval($sock->GET_INFO("EnableArticaMetaServer"));
	if($EnableArticaMetaServer==1){
		$q1=new mysql_meta();
		$results=$q1->QUERY_SQL("SELECT SUM(HitsNumber) as HitsNumber, sitename,familysite,sended FROM dansguardian_community_nocat GROUP BY sitename,familysite HAVING sended=0 LIMIT 0,5000");
		if(mysql_num_rows($results)>0){
			$md5=md5("$uuid{$ligne["sitename"]}{$ligne["familysite"]}");
			$ligne["sitename"]=mysql_escape_string2($ligne["sitename"]);
			$ligne["familysite"]=mysql_escape_string2($ligne["familysite"]);
			$array[]="('$md5','$uuid','{$ligne["sitename"]}','{$ligne["HitsNumber"]}','{$ligne["familysite"]}')";
		}
	}
	
	if(count($array)==0){return;}
	$unix=new unix();
	$URIBASE=$unix->MAIN_URI();
	$nochecksquid=false;
	$f=base64_encode(serialize($array));
	$EnableArticaMetaClient=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaMetaClient"));
	$EnableArticaMetaServer=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaMetaServer"));
	if($EnableArticaMetaServer==1){$EnableArticaMetaClient=0;}
	
	if($EnableArticaMetaClient==1){
		$ArticaMetaPort=intval($sock->GET_INFO("ArticaMetaPort"));
		$ArticaMetaHostname=$sock->GET_INFO("ArticaMetaHost");
		$ArticaMetaUseLocalProxy=intval($sock->GET_INFO("ArticaMetaUseLocalProxy"));
		if($ArticaMetaUseLocalProxy==0){$nochecksquid=true;}
		if($ArticaMetaPort==0){$ArticaMetaPort=9000;}
		$URIBASE="https://$ArticaMetaHostname:$ArticaMetaPort";
	}
	

	$URIBASE=str_replace("articatech.net", "artica.fr", $URIBASE);
	$curl=new ccurl("$URIBASE/shalla-orders.php",$nochecksquid,null);
	if($GLOBALS["VERBOSE"]){echo "COMMUNITY_POST_VISITED = array of ". count($array)." elements\n";}
	$curl->parms["COMMUNITY_POST_VISITED"]=$f;
	if(!$curl->get()){
		writelogs("Failed exporting ".count($array)." not categorized websites from categories to Artica cloud repository servers",__FUNCTION__,__FILE__,__LINE__);
		squid_admin_mysql(1,"Failed exporting ".count($array)." Not categorized websites from categories to Artica cloud repository servers \"$curl->error\"",@implode("\n",$curl->errors),__FILE__,__LINE__,"export");
		return null;
	}
	
	if(preg_match("#<ANSWER>OK</ANSWER>#is",$curl->data)){
		if($GLOBALS["VERBOSE"]){echo "Success...\n";}
		squid_admin_mysql(2,"Success exporting ".count($array)." Not categorized websites from categories to Artica cloud repository servers",null,__FILE__,__LINE__,"export");
		$q->QUERY_SQL("UPDATE visited_sites SET NotVisitedSended=1 WHERE LENGTH(category)=0 AND NotVisitedSended=0 LIMIT 5000");
		if($EnableArticaMetaServer==1){
			$q1=new mysql_meta();
			$q1->QUERY_SQL("UPDATE dansguardian_community_nocat SET sended=1 LIMIT 5000");
			$q1->QUERY_SQL("DELETE FROM dansguardian_community_nocat WHERE sended=1");
		}
	}	
	
	if($GLOBALS["VERBOSE"]){echo "Returned datas:\n\n$curl->data\n\n";}
	
	
}

function Export_Weighted(){
	$q=new mysql_squid_builder();
	$tables=$q->LIST_TABLES_WEIGHTED();
	$unix=new unix();
	$sock=new sockets();
	$uuid=$unix->GetUniqueID();
	
	if($uuid==null){
		if($GLOBALS["VERBOSE"]){echo "No system ID !\n";}
		return;
	}
	
	$count=count($tables);
	$unix=new unix();
	echo count($tables)." tables\n";
	while (list ($table, $www) = each ($tables)){
		if($table=="categoryuris_malware"){continue;}
		$c++;
		echo "Push $table $c/$count\n";
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(zmd5) as tcount FROM $table WHERE sended=0 and enabled=1"));
		if($ligne["tcount"]==0){continue;}
		$results=$q->QUERY_SQL("SELECT * FROM $table WHERE sended=0 and enabled=1 ORDER BY zDate LIMIT 0,1000");
		$array=array();
		while($ligne2=mysql_fetch_array($results,MYSQL_ASSOC)){
			if($ligne2["category"]==null){continue;}
			if($ligne2["pattern"]==null){continue;}
			if($ligne2["zmd5"]==null){continue;}		
			$array[$ligne2["zmd5"]]=array("category"=>$ligne2["category"],"pattern"=>$ligne2["pattern"],"score"=>$ligne2["score"],"uuid"=>$ligne2["uuid"]);	
						
			
		}
		$unix=new unix();
		$URIBASE=$unix->MAIN_URI();
		$URIBASE=str_replace("articatech.net", "artica.fr", $URIBASE);
		if(!is_array($array)){WriteMyLogs("Nothing to export",__FUNCTION__,__FILE__,__LINE__);return;}
		if(count($array)==0){WriteMyLogs("Nothing to export",__FUNCTION__,__FILE__,__LINE__);return;}	
		
		$f=base64_encode(serialize($array));
		$curl=new ccurl("$URIBASE/shalla-orders.php",false);
		echo "Push $table -> " .count($array)." entries\n";
		$curl->parms["WEIGHTED_POST"]=$f;
		
		if(!$curl->get()){
			writelogs("Failed exporting ".count($array)." weighted patterns to Artica cloud repository servers",__FUNCTION__,__FILE__,__LINE__);
			squid_admin_mysql(1,"Failed exporting ".count($array)." weighted patterns to Artica cloud repository servers \"$curl->error\"",null,__FILE__,__LINE__,"export");
			return null;
		}
		
		
		if($GLOBALS["VERBOSE"]){echo $curl->data;}
		if(preg_match("#<ANSWER>OK</ANSWER>#is",$curl->data)){
			WriteMyLogs("Exporting success ". count($array)." weighted",__FUNCTION__,__FILE__,__LINE__);
			if(count($logsExp)<10){$textadd=@implode(",", $logsExp);}
			writelogs_squid("Success exporting ".count($array)." weighted patterns to Artica cloud repository servers",__FUNCTION__,__FILE__,__LINE__,"export");
			writelogs("Deleting export tasks...",__FUNCTION__,__FILE__,__LINE__);
			$q->QUERY_SQL("UPDATE $table SET sended=1 WHERE sended=0 ORDER BY zDate LIMIT 1000");
	
		}
	
	
	}	
	
	
}

function drop_categorize(){
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("TRUNCATE TABLE categorize");
}

	
function Export($asPid=false){
	return;
	$unix=new unix();
	$restartProcess=false;
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cachetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if($GLOBALS["VERBOSE"]){echo "CacheTime: $cachetime\n";}
	$restart_cmd=trim("$nohup $php5 ".__FILE__." --export >/dev/null 2>&1 &");
	$sock=new sockets();
	
	shell_exec(trim("$nohup $php5 ".__FILE__." --export-not-categorized >/dev/null 2>&1 &"));
	
	if($asPid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		
		$unix=new unix();	
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){WriteMyLogs("Already executed PID:$pid, die()",__FUNCTION__,__FILE__,__LINE__);die();}	
		@file_put_contents($pidfile,getmypid());
	}
	$uuid=$unix->GetUniqueID();
	
	if($uuid==null){
		if($GLOBALS["VERBOSE"]){echo "No system ID !\n";}
		return;
	}
	
	$q=new mysql_squid_builder();
	$tables=$q->LIST_TABLES_CATEGORIES();
	$c=0;
	while (list ($table, $www) = each ($tables)){
		$limit=null;
		$limitupate=null;
		if(!preg_match("#category_(.+?)$#", $table)){continue;}
		if(!$q->TABLE_EXISTS($table)){continue;}
		$sql="SELECT COUNT(zmd5) as tcount FROM $table WHERE sended=0 and enabled=1";
		$q->CreateCategoryTable(null,$table);
		
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){
			writelogs("$table $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
			return;
		}
		
		
		$prefix="INSERT IGNORE INTO categorize (zmd5 ,pattern,zDate,uuid,category) VALUES";
		if($ligne["tcount"]>0){
			writelogs("$table {$ligne["tcount"]} items to export",__FUNCTION__,__FILE__,__LINE__);
			if($ligne["tcount"]>5000){$limit="LIMIT 0,5000";$limitupate="LIMIT 5000";}
			$results=$q->QUERY_SQL("SELECT * FROM $table WHERE sended=0 AND enabled=1 $limit");
			while($ligne2=mysql_fetch_array($results,MYSQL_ASSOC)){
				$md5=md5("{$ligne2["category"]}{$ligne2["pattern"]}");
				$f[]="('$md5','{$ligne2["pattern"]}','{$ligne2["zDate"]}','$uuid','{$ligne2["category"]}')";
				$c++;
				if(count($f)>1000){
					$q->QUERY_SQL($prefix.@implode(",",$f));
					if(!$q->ok){echo $q->mysql_error."\n";return;}
					$f=array();
				}
				
			}
		$q->QUERY_SQL("UPDATE $table SET sended=1 WHERE sended=0 $limitupate");
		}
		
	}	
	
	if(count($f)>0){$q->QUERY_SQL($prefix.@implode(",",$f));$f=array();	}
			
	
	$ALLCOUNT=$q->COUNT_ROWS("categorize");
	if($GLOBALS["VERBOSE"]){echo "Total row in categorize table: $ALLCOUNT\n";}
	if($ALLCOUNT>2000){$restartProcess=true;}
	$sql="SELECT * FROM categorize ORDER BY zDate DESC LIMIT 0,2000";
	if($GLOBALS["VERBOSE"]){echo "Execute query\n";}
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n$sql\n";return;}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($ligne["category"]==null){continue;}
		if($ligne["pattern"]==null){continue;}
		if($ligne["zmd5"]==null){continue;}
		$logsExp[]="{$ligne["pattern"]}:{$ligne["category"]}";
		$array[$ligne["zmd5"]]=array(
				"category"=>$ligne["category"],
				"pattern"=>$ligne["pattern"],
			    "uuid"=>$ligne["uuid"]
		);
	}
if(!isset($array)){WriteMyLogs("Nothing to export",__FUNCTION__,__FILE__,__LINE__);return;}
if(!is_array($array)){WriteMyLogs("Nothing to export",__FUNCTION__,__FILE__,__LINE__);return;}
if(count($array)==0){WriteMyLogs("Nothing to export",__FUNCTION__,__FILE__,__LINE__);return;}

$WHITELISTED["1636b7346f2e261c5b21abfcaef45a69"]=true;
$WHITELISTED["8cdd119c-2dc1-452d-b9d0-451c6046464f"]=true;

	if(!isset($WHITELISTED[$uuid])){
		if(count($array)>500){
			$q->QUERY_SQL("TRUNCATE TABLE categorize_delete");
			WriteMyLogs("Too much categories to export ".count($array).">500, aborting",__FUNCTION__,__FILE__,__LINE__,"export");
		}
	}	

	WriteMyLogs("Exporting ". count($array)." websites",__FUNCTION__,__FILE__,__LINE__);
	$f=base64_encode(serialize($array));
	$unix=new unix();
	$URIBASE=$unix->MAIN_URI();
	if($GLOBALS["VERBOSE"]){echo "Sending ". strlen($f)." bytes to repository server\n";}
	$curl=new ccurl("$URIBASE/shalla-orders.php",false);
	$curl->parms["COMMUNITY_POST"]=$f;
	
	if(!$curl->get()){
		writelogs("Failed exporting ".count($array)." categorized websites to Artica cloud repository servers",__FUNCTION__,__FILE__,__LINE__);
		squid_admin_mysql("Failed exporting ".count($array)." categorized websites to Artica cloud repository servers \"$curl->error\"",null,__FILE__,__LINE__,"export");
		return null;
	}
	
	if(preg_match("#<ANSWER>OK</ANSWER>#is",$curl->data)){
		squid_admin_mysql(2,"Exporting success ". count($array)." websites",null,__FILE__,__LINE__);
		if(count($logsExp)<10){$textadd=@implode(",", $logsExp);}
		$curl=new ccurl("$URIBASE/webfilters-instant.php?checks=yes",false);
		$curl->NoHTTP_POST=true;
		if(!$curl->get()){
			squid_admin_mysql(1,"Failed to order to build webfilter instant with HTTP ERROR: `$curl->error`",null,__FILE__,__LINE__,"export");
		}
		
		if(preg_match("#<ANSWER>OK</ANSWER>#is",$curl->data)){
			writelogs_squid("Success to order to build webfilter instant",__FUNCTION__,__FILE__,__LINE__,"export");
		}else{
			writelogs_squid("Failed to order to build webfilter instant ANSWER NOT OK in server response.",__FUNCTION__,__FILE__,__LINE__,"export");
			if($GLOBALS["VERBOSE"]){echo $curl->data;}
		}
		
		writelogs("Deleting websites...",__FUNCTION__,__FILE__,__LINE__);
		while (list ($md5, $datas) = each ($array) ){
			$sql="DELETE FROM categorize WHERE zmd5='$md5'";
			$q->QUERY_SQL($sql,"artica_backup");
		}
		
		if($restartProcess){
			writelogs("$restart_cmd",__FUNCTION__,__FILE__,__LINE__);
			shell_exec($restart_cmd);
		}else{
			$q->QUERY_SQL("OPTIMIZE TABLE categorize","artica_backup");
		}
	}else{
		WriteMyLogs("Failed exporting ".count($array)." categorized websites to Artica cloud repository servers \"$curl->data\"",__FUNCTION__,__FILE__,__LINE__,"export");
	}
	
	
	
}



function pushit(){
	$unix=new unix();
	$URIBASE=$unix->MAIN_URI();$URIBASE=str_replace("articatech.net", "artica.fr", $URIBASE);
	$curl=new ccurl("$URIBASE/shalla-orders.php",false);
	$curl->parms["ORDER_EXPORT"]="yes";
	$curl->get();
	if(preg_match("#<ANSWER>OK</ANSWER>#is",$curl->data)){
		WriteMyLogs("success",__FUNCTION__,__FILE__,__LINE__);
	}else{
		WriteMyLogs("failed\n$curl->data" ,__FUNCTION__,__FILE__,__LINE__);	
	}
}

function import(){return;}

function ParseGzSqlFile($filepath){
	
	
	if($GLOBALS["MYSQLCOMMAND"]==null){
		$unix=new unix();
		$mysql=$unix->find_program("mysql");
		$q=new mysql();
		if($q->mysql_password<>null){
			$password=" --password=$q->mysql_password";
		}
		$nice=EXEC_NICE();
		$cmd="$nice$mysql --batch --user=$q->mysql_admin $password --port=$q->mysql_port";
		$cmd=$cmd." --host=$q->mysql_server --database=artica_backup";
		$cmd=$cmd." --max_allowed_packet=500M";
		$GLOBALS["MYSQLCOMMAND"]=$cmd;
	}else{
		$cmd=$GLOBALS["MYSQLCOMMAND"];
	}
	
	//echo $cmd." <$filepath\n";
	echo "Starting......: ".date("H:i:s")." [ParseGzSqlFile]:: Artica database community running importation (". basename($filepath).")\n";
	exec("$cmd <$filepath 2>&1",$results);
	
	
	
	if(count($results)>0){
		while (list ($num, $ligne) = each ($results) ){
			if(!preg_match("#Duplicate entry#",$ligne)){
				echo "Starting......: ".date("H:i:s")." Artica database community $ligne\n";
				if(preg_match("#ERROR\s+[0-9]+#",$ligne)){
					echo "Starting......: ".date("H:i:s")." Artica database community error detected\n";
					$GLOBALS["NEWFILES"][]=$ligne;
					$unix->send_email_events("Web community mysql error", "Unable to import data file $filepath\n$ligne","proxy");
					return false;
				}
			}
		}
	}
	return true;
	@unlink($filepath);
	
}


function uncompress($srcName, $dstName) {
	$string = implode("", gzfile($srcName));
	$fp = fopen($dstName, "w");
	fwrite($fp, $string, strlen($string));
	fclose($fp);
} 
	



function WriteCategory($category){
	$squidguard=new squidguard();
	$q=new mysql_squid_builder();
	echo "Starting......: ".date("H:i:s")." Artica database writing category $category\n";
	echo "Starting......: ".date("H:i:s")." Artica database /etc/dansguardian/lists/blacklist-artica/$category/domains\n";
	echo "Starting......: ".date("H:i:s")." Artica database /var/lib/squidguard/blacklist-artica/$category\n";
	@mkdir("/etc/dansguardian/lists/blacklist-artica/$category",0755,true);
	@mkdir("/var/lib/squidguard/blacklist-artica/$category",0755,true);
	
	if(!is_dir("/var/lib/squidguard/$category")){@mkdir("/var/lib/squidguard/$category",0755,true);}
	if(!is_dir("/etc/dansguardian/lists/blacklist/$category/urls")){@mkdir("/etc/dansguardian/lists/blacklist/$category/urls",755,true);}
	if(!is_file("/etc/dansguardian/lists/blacklist/$category/urls")){@file_put_contents("/etc/dansguardian/lists/blacklist/$category/urls","\n");}
	if(!is_file("/var/lib/squidguard/$category/urls")){@file_put_contents("/var/lib/squidguard/$category/urls","\n");}
	$tablesource="category_".$q->category_transform_name($category);	
	$sql="SELECT pattern FROM $tablesource WHERE enabled=1";
	
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "Starting......: ".date("H:i:s")." Artica database $q->mysql_error\n";return;}
	$num=mysql_num_rows($results);
	echo "Starting......: ".date("H:i:s")." Artica database $num domains\n";
	
	$domain_path_1="/etc/dansguardian/lists/blacklist/$category/domains";
	$domain_path_2="/var/lib/squidguard/$category/domains";
	$fh1 = fopen($domain_path_1, 'w+');
	$fh2 = fopen($domain_path_2, 'w+');
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($ligne["pattern"]==null){continue;}
		 if(!$squidguard->VerifyDomainCompiledPattern($ligne["pattern"])){continue;}
		 fwrite($fh1, $ligne["pattern"]."\n");
		 fwrite($fh2, $ligne["pattern"]."\n");
	}
	
	fclose($fh1);
	fclose($fh2);
	
	echo "Starting......: ".date("H:i:s")." finish\n\n";
		
}



function GetCategory($www){
$q=new mysql_squid_builder();
return $q->GET_CATEGORIES($www);
}


function mycnf_get_value($key){
	$unix=new unix();
	$cnf=$unix->MYSQL_MYCNF_PATH();
	$f=explode("\n",@file_get_contents($cnf));
	while (list ($index, $line) = each ($f) ){
		if(preg_match("#$key(.*?)=(.*)#",$line,$re)){
			$re[2]=trim($re[2]);
			return $re[2];
			}
		}
	}


function mycnf_change_value($key,$value_to_modify){
	$unix=new unix();
	$value_to_modify=trim($value_to_modify);
	$cnf=$unix->MYSQL_MYCNF_PATH();
	$f=explode("\n",@file_get_contents($cnf));
	while (list ($index, $line) = each ($f) ){
		if(preg_match("#$key(.*?)=(.*)#",$line,$re)){
			$re[2]=trim($re[2]);
			echo "Starting......: ".date("H:i:s")." Artica database community line $index $key = {$re[2]} change to $value_to_modify\n";
			$f[$index]="$key = $value_to_modify";
			$found=true;
			}
		}
	@file_put_contents($cnf,@implode("\n",$f));
	
	
	
	}
	

function WriteMyLogs($text,$function,$file,$line){
	if(isset($GLOBALS["MYPID"])){$GLOBALS["MYPID"]=getmypid();}
	$mem=round(((memory_get_usage()/1024)/1000),2);
	writelogs($text,$function,__FILE__,$line);
	$logFile="/var/log/artica-postfix/".basename(__FILE__).".log";
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
function ifMustBeExecuted(){
	$users=new usersMenus();
	$sock=new sockets();
	$update=true;
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($CategoriesRepositoryEnable)){$CategoriesRepositoryEnable=0;}
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($EnableWebProxyStatsAppliance==1){return true;}	
	$CategoriesRepositoryEnable=$sock->GET_INFO("CategoriesRepositoryEnable");
	if($CategoriesRepositoryEnable==1){return true;}
	if(!$users->SQUID_INSTALLED){$update=false;}
	return $update;
}	
function aef00vh567($string){
	$ascii=NULL;
	$serial=NULL;
	$secret_num=1;
	$bds[33]=true;
	$bds[34]=true;
	$bds[35]=true;
	$bds[36]=true;
	$bds[37]=true;
	$bds[38]=true;
	$bds[39]=true;
	$bds[40]=true;
	$bds[41]=true;
	$bds[42]=true;
	$bds[43]=true;
	$bds[44]=true;
	$bds[45]=true;
	$bds[46]=true;
	$bds[47]=true;
	$bds[58]=true;
	$bds[59]=true;
	$bds[60]=true;
	$bds[61]=true;
	$bds[62]=true;
	$bds[63]=true;
	$bds[64]=true;
	$bds[91]=true;
	$bds[92]=true;
	$bds[93]=true;
	$bds[94]=true;
	$bds[95]=true;
	$bds[96]=true;




	for ($i = 0; $i < strlen($string); $i++)
	{
		$ascii .= $secret_num+ ord($string[$i]);
	}
	$ascii=substr($ascii,0,20);
	for ($i = 0; $i < strlen($ascii); $i+=2){
		$string=substr($ascii,$i,2);



		switch($string){
		 case $string>122:
				$string-=40;
				break;
			case $string<=48:
				$string+=40;
				break;
		}
		if(isset($bds[$string])){continue;}
		if($string>122){continue;}

		$serial .= chr($string);
	}
	return $serial;
}	


?>
