<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["NOPROGRESS"]=false;
$GLOBALS["PROGRESS"]=false;

if(preg_match("#--verbose#",implode(" ",$argv))){
		$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
//$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
		if(preg_match("#--progress#",implode(" ",$argv))){
			$GLOBALS["PROGRESS"]=true;}
		
		
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.squidguard.inc');
include_once(dirname(__FILE__)."/ressources/class.squid.hypercache.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.checks.inc");

if($argv[1]=="--checklic"){HyperCache_create_license();exit;}
if($argv[1]=="--wizard"){$GLOBALS["NOPROGRESS"]=true;}
if($argv[1]=="--urgency"){disable_urgency();exit;}
if($argv[1]=="--free"){echo ifHyperCacheFreeInsquid()."\n";die();}
if($argv[1]=="--rules"){echo rules();die();}

build_sequence();

function build_progress_urgency($pourc,$text){
	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/squid.urgency.hypercache.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	sleep(1);

}

function disable_urgency(){
	$unix=new unix();
	build_progress_urgency(10,"{disable_emergency}");
	@file_put_contents("/etc/artica-postfix/settings/Daemons/StoreIDUrgency", 0);
	@chmod("/etc/artica-postfix/settings/Daemons/StoreIDUrgency", 0777);
	build_progress_urgency(50,"{reconfigure_proxy_service}");
	$php=$unix->LOCATE_PHP5_BIN();
	system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
	build_progress_urgency(70,"{verify_the_license}");
	HyperCache_create_license();
	build_progress_urgency(100,"{success}");
	
}


function ifHyperCacheInsquid(){
	
	$f=explode("/n",@file_get_contents("/etc/squid3/squid.conf"));
	
	while (list ($num, $line) = each ($f)){
		$line=trim($line);
		if(preg_match("#include.*?StoreID\.conf", $line)){
			return true;
		}
		
	}
	
	return false;
	
}
function ifHyperCacheFreeInsquid(){
	if(!ifHyperCacheInsquid()){return false;}
	$f=explode("/n",@file_get_contents("/etc/squid3/StoreID.conf"));

	while (list ($num, $line) = each ($f)){
		$line=trim($line);
		if(preg_match("#store_id_program.*?storeid_file_rewrite#", $line)){
			return true;
		}
		echo "$line\n";
	}

	return false;

}

function build_sequence_plugin(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	if(!ifHyperCacheFreeInsquid()){
		build_progress(50,"{reconfigure_proxy_service}");
		system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
		if(!ifHyperCacheFreeInsquid()){
			build_progress(110,"{reconfigure_proxy_service} {failed}");
			return;
		}
	}
	
	$HyperCacheSquid=new HyperCacheSquid();
	$HyperCacheSquid->build();
	
	
	$squid_checks=new squid_checks();
	if(!$squid_checks->squid_parse()){
		build_progress("{reconfigure_proxy_service} {failed}",110);
		return;
	}
	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	system("$squidbin -k reconfigure");
	
	build_progress(100,"{reconfigure_proxy_service} {success}");
}


function rules(){
	$unix=new unix();
	include_once(dirname(__FILE__)."/ressources/class.squid.hypercache.inc");
	
	build_progress(20,"{reconfigure_proxy_service}");
	$hyper=new HyperCacheSquid();
	$hyper->build();
	
	$squid_checks=new squid_checks();
	if(!$squid_checks->squid_parse()){
		build_progress("{reconfigure_proxy_service} {failed}",110);
		return;
	}
	
	build_progress(80,"{reconfigure_proxy_service} {success}");
	$squidbin=$unix->LOCATE_SQUID_BIN();
	build_progress(90,"{reloading}");
	system("$squidbin -f /etc/squid3/squid.conf -k reconfigure");
	build_progress(100,"{reconfigure_proxy_service} {success}");
	
}

function build_sequence(){
	
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();	
	$sock=new sockets();
	$HyperCacheStoreID=intval($sock->GET_INFO("HyperCacheStoreID"));
	$HyperCacheLicensedMode=intval($sock->GET_INFO("HyperCacheLicensedMode"));
	

	if($HyperCacheLicensedMode==0){
		if($HyperCacheStoreID==1){
			build_progress(10,"{checking_plugin}");
			build_sequence_plugin();
			return;
		}
		
		
	}
	
	build_progress(10,"{checking_license_status}");
	if(!HyperCache()){
		
		if($HyperCacheStoreID==0){
			if(!verify_proxy_configuration()){
				build_progress(50,"{reconfigure_proxy_service}");
				system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
				build_progress(100,"{checking_license_status} {success} {disabled}");
				return;
				
			}
			build_progress(100,"{checking_license_status} {success} {disabled}");
			return;
		}
		
		build_progress(110,"{checking_license_status} {failed}");
		return;
	}
	build_progress(15,"{update_websites_list}");
	HyperCache_websites();
	build_progress(20,"{verify_the_license}");
	HyperCache_create_license();
	build_progress(30,"{verify_proxy_configuration}");
	if(!verify_proxy_configuration()){
		build_progress(50,"{reconfigure_proxy_service}");
		system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
		build_progress(70,"{verify_proxy_configuration}");
		if(!verify_proxy_configuration()){
			build_progress(110,"{verify_proxy_configuration} {failed}");
			return;
		}
		system("/etc/artica-postfix/artica-status restart --force");
	}
	
	$HyperCacheSquid=new HyperCacheSquid();
	$HyperCacheSquid->build();
	
	$squid_checks=new squid_checks();
	if(!$squid_checks->squid_parse()){
		build_progress("{reconfigure_proxy_service} {failed}",110);
		return;
	}
	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	system("$squidbin -k reconfigure");
	
	build_progress(100,"{verify_proxy_configuration} {success}");
	
}


function build_progress($pourc,$text){
	if(!$GLOBALS["PROGRESS"]){return;}
	if($GLOBALS["NOPROGRESS"]){return;}
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/squid.hypercache.progress", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/web/squid.hypercache.progress",0755);
	sleep(1);

}


function HyperCache(){
	$sock=new sockets();
	$HyperCacheStoreID=intval($sock->GET_INFO("HyperCacheStoreID"));
	if($HyperCacheStoreID==0){return;}
	$HyperCacheMakeId=HyperCacheMakeId();

	echo "Unique ID: $HyperCacheMakeId\n";
	$uri="https://svb.unveiltech.com/svbgetinfo.php";
	$array["uuid"]=$HyperCacheMakeId;
	build_progress(11,"{connecting}...");
	$curl=new ccurl($uri);
	$curl->parms["uuid"]=$HyperCacheMakeId;
	if(!$curl->get()){
		build_progress(110,"{connecting} {failed}");
		echo "HyperCache:: Check license FAILED\n";
		echo "HyperCache:: $curl->error\n";
		while (list ($num, $line) = each ($curl->errors)){
			echo "HyperCache:: $line\n";
		}
		
		return false;
	}
	
	build_progress(12,"{analyze}...");
	if(preg_match("#\{(.*?)\}#is", $curl->data,$re)){
		$array=json_decode("{{$re[1]}}");
		
		if($array->edate < strtotime("2010-01-01 00:00:00")){
			build_progress(110,"{protocol} {failed} on edate:{$array->edate} ". date("Y-m-d H:i:s",$array->edate));
			return false;
		}

		
		
		echo "expired: {$array->expired} -> $array->edate\n";
		$FULL["expired"]=$array->expired;
		$FULL["edate"]=$array->edate;
		build_progress(13,"{analyze} ". date("Y-m-d H:i:s",$array->edate)."...");
	}
	
	if(isset($FULL["expired"])){
		build_progress(12,"{expired}...");
		echo "HyperCache:: Check license Expired\n";
		@file_put_contents("/etc/artica-postfix/settings/Daemons/HyperCacheLicStatus", serialize($FULL));
		@chmod("/etc/artica-postfix/settings/Daemons/HyperCacheLicStatus",0755);
		echo "Update License status: Success\n";
		return true;
	}

	return false;

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


function HyperCache_websites(){
	$sock=new sockets();
	$HyperCacheStoreID=intval($sock->GET_INFO("HyperCacheStoreID"));
	if($HyperCacheStoreID==0){return;}

	$uri="https://svb.unveiltech.com/svbgetsites.php";
	$curl=new ccurl($uri);
	$curl->NoHTTP_POST=true;
	if(!$curl->GetFile("/etc/artica-postfix/settings/Daemons/HyperCacheWebsitesList")){echo "HyperCache:: Check Websites failed\n"; return false;}
	@chmod("/etc/artica-postfix/settings/Daemons/HyperCacheWebsitesList",0755);
	echo "Update Websites status: Success\n";
	
	
}

function HyperCache_create_license(){
	$sock=new sockets();
	$unix=new unix();
	$uuid=$unix->GetUniqueID();
	if($uuid==null){
		if($GLOBALS["VERBOSE"]){echo "No system ID !\n";}
		return;
	}
	$HyperCacheStoreID=intval($sock->GET_INFO("HyperCacheStoreID"));
	if($HyperCacheStoreID==0){return;}
	$HyperCacheStoreIDLicense=$sock->GET_INFO("HyperCacheStoreIDLicense");
	
	if($HyperCacheStoreIDLicense==null){
		echo "No license set..., continue in evalution mode\n";
		return;
	}
	
	$uri="https://svb.unveiltech.com/svblicenseaction.php?ma=86&license=$HyperCacheStoreIDLicense";
	$curl=new ccurl($uri);
	$curl->parms["ma"]=86;
	$curl->parms["license"]=$HyperCacheStoreIDLicense;
	$curl->parms["artid"]=$uuid;
	if(!$curl->get()){echo "HyperCache:: Check license failed\n"; return false;}
	
	echo "License status type:[$curl->data]\n";
	if(intval(trim($curl->data))==1){return true;}
	if(stripos($curl->data, "The Activation Code is not valid")>0){return false;}
	if(stripos($curl->data, "The Activation Code is already activated")>0){return true;}
	if(stripos($curl->data, "The Activation Code is already activated with another server")>0){return false;}
	
	


}

function verify_proxy_configuration(){
	$sock=new sockets();
	$f=explode("\n",@file_get_contents("/etc/squid3/StoreID.conf"));
	$HyperCacheStoreIDLicense=trim(strtoupper($sock->GET_INFO("HyperCacheStoreIDLicense")));
	$HyperCacheStoreID=intval($sock->GET_INFO("HyperCacheStoreID"));
	
	build_progress(35,"{checking_configuration}");
	if($HyperCacheStoreID==0){
		if(ifHyperCacheInsquid()){
			build_progress(36,"{disabled_feature}");
			return false;
		}
	}
	
	
	while (list ($num, $ligne) = each ($f) ){
		if(!preg_match("#store_id_program.*?hypercache-plugin(.+)#", $ligne,$re)){continue;}
		$xline=$re[1];
		if(preg_match("#-c (.+?)\s+#", $xline,$ri)){$CurrLicense=trim(strtoupper($ri[1]));}
		echo "Current License: $CurrLicense\n";
		if($CurrLicense<>$HyperCacheStoreIDLicense){return false;}
		return true;
		
	}
	
	return false;
}





