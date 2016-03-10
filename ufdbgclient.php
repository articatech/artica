#!/usr/bin/php
<?php
error_reporting(0);
include_once(dirname(__FILE__)."/ressources/class.ufdbguard-tools.inc");
include_once(dirname(__FILE__)."/ressources/class.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.ini.inc");
include_once(dirname(__FILE__)."/ressources/class.ufdbgclient.quotas.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");

$GLOBALS["time_loop_start"]=tool_microtime_float();
$GLOBALS["VERBOSE"]=false;
$GLOBALS["UFDB_SOCKET_ERROR"]=0;
$GLOBALS["VIDEOCACHE_DEBUG"]=false;
$GLOBALS["HyperCacheDebug"]=false;
$GLOBALS["DebugLoop"]=false;
$GLOBALS["GOOGLE_SAFE"]=false;
$GLOBALS["DEBUG_UNLOCKED"]=false;
$GLOBALS["DEBUG_PROTOCOL"]=false;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["MYPID"]=getmypid();
$GLOBALS["SquidGuardIPWeb"]=null;
$GLOBALS["CACHE"]=array();
$GLOBALS["AS_34"]=false;
$GLOBALS["DebugQuota"]=false;
$GLOBALS["DEBUG_OUTPUT"]=false;
$GLOBALS["DEBUG_WEBFILTERING"]=false;
$GLOBALS["DEBUG_ITCHART"]=false;
$GLOBALS["DEBUG_IN_MEM"]=false;
$GLOBALS["DEBUG_WHITELIST"]=false;
$GLOBALS["PHISHTANK"]=0;
$GLOBALS["DEBUG_BLACKLIST"]=false;
$GLOBALS["DEBUG_DNSBL"]=false;
$GLOBALS["DEBUG_HTTRACK"]=false;
$GLOBALS["DEBUG_RANSOMARE"]=false;

$GLOBALS["WindowsUpdateCaching"]=0;
$GLOBALS["WebFilteringRansomware"]=1;
$GLOBALS["HTTrackInSquid"]=0;



if($GLOBALS["VERBOSE"]){
	ini_set('display_errors', 1);
	ini_set("log_errors", 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
	ini_set("error_log", "/var/log/squid/ufdbgclient.debug");
}



$GLOBALS["UFDBVERS"]="1.1.9";
if(isset($argv)){
	if(count($argv)>0){
		$cmdline=@implode(" ", $argv);
	
		if(preg_match("#--black=(.+)#", $cmdline,$re)){
			$GLOBALS["time_loop_start"]=tool_microtime_float();
			$GLOBALS["VERBOSE"]=true;
			$GLOBALS["OUTPUT"]=true;
			BlacklistedBase($re[1]);
			die();
		}
		if(isset($argv[1])){
			if($argv[1]=="--quota"){
				$GLOBALS["time_loop_start"]=tool_microtime_float();
				$GLOBALS["VERBOSE"]=true;
				$GLOBALS["OUTPUT"]=true;
				$GLOBALS["DebugQuota"]=true;
				QuotaSize($argv[2],$argv[3],null,$argv[4]);
				die();
			}
			
			if($argv[1]=="--headers"){
				Curl_get_headers($argv[2]);
			}
		}
	}
}

$GLOBALS["SQUID_VERSION"]=@file_get_contents("/var/log/squid/ufdbgclient.version");
if(is_file("/var/log/squid/UFDB_SOCKET_ERROR")){@unlink("/var/log/squid/UFDB_SOCKET_ERROR");}

$SquidGuardIPWeb=unserialize(@file_get_contents("/var/log/squid/SquidGuardIPWeb"));
if(is_array($SquidGuardIPWeb)){$GLOBALS["SquidGuardIPWeb"]=$SquidGuardIPWeb["SquidGuardIPWeb"]; }

if(preg_match("#-S\s+([0-9\.]+)\s+-p\s+([0-9]+)#", $cmdline,$re)){$GLOBALS["UFDB_SERVER"]=$re[1]; $GLOBALS["UFDB_PORT"]=$re[2]; }
if(!isset($GLOBALS["UFDB_SERVER"])){$GLOBALS["UFDB_SERVER"]="127.0.0.1";}
if(!isset($GLOBALS["UFDB_PORT"])){$GLOBALS["UFDB_PORT"]="3977";}
ufdbconfig();

events("Web filtering service on {$GLOBALS["UFDB_SERVER"]}:{$GLOBALS["UFDB_PORT"]} Squid version = {$GLOBALS["SQUID_VERSION"]}");
events("Web filtering Page error on {$GLOBALS["SquidGuardIPWeb"]}");
events("url_rewrite_concurrency {$GLOBALS["url_rewrite_concurrency"]}");
events("Phishtank: {$GLOBALS["PHISHTANK"]}");




events("HyperCache enabled:{$GLOBALS["SquidEnforceRules"]} {$GLOBALS["HyperCacheMemEntries"]} Max entries");
events("HyperCache: redirect to {$GLOBALS["HyperCacheListenAddr"]}:{$GLOBALS["HyperCacheHTTPListenPort"]}");
events("Google Safe Browsing enabled:{$GLOBALS["EnableGoogleSafeBrowsing"]}");
events("Squid Version: {$GLOBALS["SQUID_VERSION"]}");
events("It Chart...............: {$GLOBALS["EnableITChart"]}");
events("DEBUG_PROTOCOL.........: {$GLOBALS["DEBUG_PROTOCOL"]}");
events("SquidGuardServerName...: {$GLOBALS["SquidGuardServerName"]}");
events("SquidGuardApachePort...: {$GLOBALS["SquidGuardApachePort"]}");
events("SquidGuardApacheSSLPort: {$GLOBALS["SquidGuardApacheSSLPort"]}");
events("WindowsUpdateCaching...: {$GLOBALS["WindowsUpdateCaching"]}");
events("WebCopy................: {$GLOBALS["HTTrackInSquid"]}");
events("UpdateUtilityForceProxy: {$GLOBALS["UpdateUtilityForceProxy"]}");


$temp = array();
stream_set_timeout(STDIN, 86400);

$descriptorspec = array(
		0 => array("pipe", "r"),  // stdin
		1 => array("pipe", "w"),  // stdout
		2 => array("pipe", "w") );



if(preg_match("#^3\.4#", $GLOBALS["SQUID_VERSION"])){$AS_34=true;}
if(preg_match("#^3\.5#", $GLOBALS["SQUID_VERSION"])){$AS_34=true;}
$GLOBALS["AS_34"]=$AS_34;

$IpClass=new IP();

while (!feof(STDIN)) {
	$XzLine = fgets(STDIN);
	$szLine=trim($XzLine);
	if (empty($szLine)) {continue;}
	$GLOBALS["CHANNEL"]=0;
	$GLOBALS["OUTPUT_CHANNEL"]=false;
	if($GLOBALS["DEBUG_OUTPUT"]){events("Receive \"$szLine\"");}
	if($GLOBALS["DEBUG_PROTOCOL"]){events("Receive \"$szLine\"");}
	$results=false;
	$GLOBALS["time_loop_start"]=tool_microtime_float();
	$array=explode(" ", $szLine);
	$extend_1=null;
	$extend_2=null;
	$scheme=null;
	$GLOBALS["LOG_AR"]["MAC"]=null;
	$GLOBALS["LOG_AR"]["SNI"]=null;
	$DEBUGHOSTNAME_PORT=0;
	$DEBUGHOSTNAME_PORT_EXT=null;
	
	if($GLOBALS["SquidUrgency"]==1){
		Output_results(null,__FUNCTION__,__LINE__);
		continue;
	}
	
	if(is_numeric($array[0])){
		if($GLOBALS["DEBUG_OUTPUT"]){events("Channel [{$array[0]}]");}
		$GLOBALS["CHANNEL"]=$array[0];
		$GLOBALS["OUTPUT_CHANNEL"]=true;
		$URI=$array[1];
		$GLOBALS["LOG_AR"]["ORGURI"]=$URI;
		$IP=$array[2];
		$userid=$array[3];
		$PROTO=$array[4];
		$myIP=$array[5];
		$myPort=$array[6];
		if(isset($array[7])){$extend_1=$array[7];}
		if(isset($array[8])){$extend_2=$array[8];}
		
	}else{
		$URI=$array[0];
		$GLOBALS["LOG_AR"]["ORGURI"]=$URI;
		$IP=$array[1];
		$userid=$array[2];
		$PROTO=$array[3];
		$myIP=$array[4];
		$myPort=$array[5];
		if(isset($array[6])){$extend_1=$array[6];}
		if(isset($array[7])){$extend_2=$array[7];}
		
	}
	if(preg_match("#^(.+?)\/#", $IP,$re)){$IPS=$re[1];}
	if($extend_1<>null){
		if(preg_match("#mac=(.+)#", $extend_1,$re)){$GLOBALS["LOG_AR"]["MAC"]=trim($re[1]);}
		if(preg_match("#sni=(.+)#", $extend_1,$re)){
			$GLOBALS["LOG_AR"]["SNI"]=trim($re[1]);
			if($GLOBALS["DEBUG_OUTPUT"]){events( "SNI: regex extend_1: '{$re[1]}' ".__LINE__);}
		}
	}
	if($extend_2<>null){
		if(preg_match("#mac=(.+)#", $extend_2,$re)){$GLOBALS["LOG_AR"]["MAC"]=trim($re[1]);}
		if(preg_match("#sni=(.+)#", $extend_2,$re)){
			$GLOBALS["LOG_AR"]["SNI"]=trim($re[1]);
			if($GLOBALS["DEBUG_OUTPUT"]){events( "SNI: regex extend_2: '{$re[1]}' ".__LINE__);}
		}
	}	
	
	$URI_ORG=$URI;
	$H=parse_url($URI);
	if(isset($H["scheme"])){$scheme=$H["scheme"];}
	$DEBUGHOSTNAME=$H["host"];
	
	if($GLOBALS["DEBUG_PROTOCOL"]){events( "Protocol: host=$DEBUGHOSTNAME ".__LINE__);}
	
	
	
	
	
	if(preg_match("#^(.+?):([0-9]+)#", $DEBUGHOSTNAME,$re)){
		$DEBUGHOSTNAME=$re[1];
		$DEBUGHOSTNAME_PORT=intval($re[2]);
		if($DEBUGHOSTNAME_PORT==80){$DEBUGHOSTNAME_PORT=0;}
		if($DEBUGHOSTNAME_PORT==443){$DEBUGHOSTNAME_PORT=0;}
	}
	
	
	if($GLOBALS["DEBUG_PROTOCOL"]){events( "Protocol: host=$DEBUGHOSTNAME ".__LINE__);}
	if($IpClass->isValid($DEBUGHOSTNAME)){
		$ASRESOL=false;
		$resolved=gethostbyaddrCached($DEBUGHOSTNAME);
		if($resolved<>null){if($resolved<>$DEBUGHOSTNAME){$ASRESOL=true;}}
		
		if($ASRESOL){
			$URI=str_replace($DEBUGHOSTNAME, $resolved, $URI);
		}else{
			$URI=str_replace($DEBUGHOSTNAME, ip2long($DEBUGHOSTNAME).".addr", $URI);
		}
		
		
		if($GLOBALS["DEBUG_PROTOCOL"]){events( "Protocol: host=$DEBUGHOSTNAME URI=$URI".__LINE__);}
	}
	
	
	if($DEBUGHOSTNAME_PORT>0){$DEBUGHOSTNAME_PORT_EXT=":{$DEBUGHOSTNAME_PORT}";}
	$GLOBALS["LOG_DOM"]=$DEBUGHOSTNAME;
	
	$GLOBALS["LOG_AR"]["URI"]=$URI;
	$GLOBALS["LOG_AR"]["IP"]=$IP;
	$GLOBALS["LOG_AR"]["userid"]=$userid;
	$GLOBALS["LOG_AR"]["PROTO"]=$PROTO;
	$GLOBALS["LOG_AR"]["myIP"]=$myIP;
	$GLOBALS["LOG_AR"]["myPort"]=$myPort;
	$GLOBALS["LOG_AR"]["host"]=$GLOBALS["LOG_DOM"];


	if($GLOBALS["DEBUG_OUTPUT"]){
		events( "SNI: extend_1: '$extend_1' ".__LINE__);
		events( "SNI: extend_2: '$extend_2' ".__LINE__);
		events("MAC: '{$GLOBALS["LOG_AR"]["MAC"]}'");
		events("SNI: '{$GLOBALS["LOG_AR"]["SNI"]}'");
	}
	if($GLOBALS["LOG_AR"]["MAC"]=="-"){$GLOBALS["LOG_AR"]["MAC"]=null;}
	if($GLOBALS["LOG_AR"]["SNI"]=="-"){$GLOBALS["LOG_AR"]["SNI"]=null;}
	
	
	if($GLOBALS["LOG_AR"]["SNI"]<>null){
		if($GLOBALS["DEBUG_OUTPUT"]){events("SNI: '{$GLOBALS["LOG_AR"]["SNI"]}'/'$DEBUGHOSTNAME'");}
		if($IpClass->isValid($DEBUGHOSTNAME)){
			if($GLOBALS["DEBUG_OUTPUT"]){events( "SNI: $URI -->: 'https://{$GLOBALS["LOG_AR"]["SNI"]}{$DEBUGHOSTNAME_PORT_EXT}' ".__LINE__);}
			$URI="https://{$GLOBALS["LOG_AR"]["SNI"]}{$DEBUGHOSTNAME_PORT_EXT}";
			$DEBUGHOSTNAME=$GLOBALS["LOG_AR"]["SNI"];
			$GLOBALS["LOG_DOM"]=$GLOBALS["LOG_AR"]["SNI"];
		}else{
			if($GLOBALS["DEBUG_OUTPUT"]){events( "SNI: '$DEBUGHOSTNAME' did not match ipaddr".__LINE__);}
		}
		
	}
	
	$ToUfdb="$URI $IP $userid $PROTO $myIP $myPort\n";
	if($GLOBALS["DEBUG_BLACKLIST"]){events("$ToUfdb");}
	if($GLOBALS["DEBUG_PROTOCOL"]){events("$PROTO $URI scheme: $scheme");}
	
	
	if($GLOBALS["WindowsUpdateCaching"]==1){
		if($PROTO=="GET"){
			if(!class_exists("UfdbWindowsCache")){include_once(dirname(__FILE__)."/ressources/class.ufdb.windowscache.inc"); }
			$UfdbWindowsCache=new UfdbWindowsCache($URI,$szLine);
			if($UfdbWindowsCache->MakeRedirect()){continue;}
		}
	}
	
	if($GLOBALS["UpdateUtilityForceProxy"]==1){
		if($PROTO=="GET"){
			if(!class_exists("UfdbUpdateUtility")){include_once(dirname(__FILE__)."/ressources/class.ufdb.updateutility.inc"); }
			$UfdbUpdateUtility=new UfdbUpdateUtility($URI,$szLine);
			if($UfdbUpdateUtility->MakeRedirect()){continue;}
		}
	}
	
	
	if($GLOBALS["HTTrackInSquid"]==1){
		if($GLOBALS["DEBUG_HTTRACK"]){events( "HTTrackInSquid: ENABLED\n");}
		if(!class_exists("HTTrackInSquid")){include_once(dirname(__FILE__)."/ressources/class.ufdb.HTTrackInSquid.inc"); }
		$HTTrackInSquid=new HTTrackInSquid($URI,$szLine);
		if($HTTrackInSquid->MakeRedirect()){continue;}
	}else{
		if($GLOBALS["DEBUG_HTTRACK"]){events( "HTTrackInSquid: DISABLED\n");}
		
	}
	
	
	if($GLOBALS["EnableITChart"]==1){
		if(ItCharted($GLOBALS["LOG_AR"])){
			continue;
		}
		
	}
	
	if($PROTO<>"CONNECT"){
		if($GLOBALS["DEBUG_PROTOCOL"]){events("$PROTO / scheme: $scheme");}
		if($scheme=="https"){
			$PROTO="CONNECT"; 
			if($GLOBALS["DEBUG_PROTOCOL"]){events("Change proto to CONNECT");}
		}
	}
	
	
	
	if($GLOBALS["EnableUfdbGuard"]==1){
		
		if($GLOBALS["DEBUG_RANSOMARE"]){events( "$DEBUGHOSTNAME: RANSOMARE = {$GLOBALS["WebFilteringRansomware"]} ".__LINE__);}
		if($GLOBALS["DebugLoop"]){events( "$DEBUGHOSTNAME: WhitelistedBase ? ".__LINE__);}
		if(WhitelistedBase($URI)){
			Output_results(null,__FUNCTION__,__LINE__);
			continue;
		}
		
		if($GLOBALS["WebFilteringRansomware"]==1){
			if(Ranswomare($GLOBALS["LOG_AR"])){
				continue;
			}
		}
		
		
		$ToUfdbKey=md5(serialize($GLOBALS["LOG_AR"]));
		if($GLOBALS["DebugLoop"]){events( "$DEBUGHOSTNAME Is Blacklisted [$ToUfdbKey]?".__LINE__);}
		if($GLOBALS["DEBUG_BLACKLIST"]){events("$URI,$IP,$userid,$ToUfdbKey");}
		if(BlacklistedBase($URI,$IP,$userid,$PROTO)){continue;}
		
		if($GLOBALS["DebugLoop"]){events( "$DEBUGHOSTNAME Is Unlocked ?".__LINE__);}
		if(Unlocked($URI,$IP,$userid)){
			continue;
		}
		
		if($GLOBALS["PHISHTANK"]==1){
			if($GLOBALS["DebugLoop"]){events( "$DEBUGHOSTNAME Is PHISHTANK ?".__LINE__);}
			if(Phistank($GLOBALS["LOG_AR"])){
				continue;
			}
				
		}
		if($GLOBALS["DEBUG_DNSBL"]){events( "$DEBUGHOSTNAME Is EnableHTTPSURBL Enabled:{$GLOBALS["EnableHTTPSURBL"]} ?".__LINE__);}
		if($GLOBALS["EnableHTTPSURBL"]==1){
			if($GLOBALS["DebugLoop"]){events( "$DEBUGHOSTNAME Is SURBL ?".__LINE__);}
			if(surblclient($GLOBALS["LOG_AR"])){
				continue;
			}
			
		}
		
		
		if($GLOBALS["DebugLoop"]){events( "Quota size ?".__LINE__);}
		if(QuotaSize($IP,$userid,$URI,$DEBUGHOSTNAME,$GLOBALS["LOG_AR"])){continue;}
		
		
		if($GLOBALS["DebugLoop"]){events( "To ufdb ?".__LINE__);}
		if(UfdbBlackList($ToUfdb,$GLOBALS["LOG_AR"],$ToUfdbKey)){continue;}
	}
	
	if($GLOBALS["DebugLoop"]){events( "GoogleSafeBrowsing ?".__LINE__);}
	if(GoogleSafeBrowsing($URI,$PROTO,$H["host"],$IP,$userid)){continue;}
	
	
	
	if($GLOBALS["DebugLoop"]){events( "HyperCacheRules ?".__LINE__);}
	if(HyperCacheRules($PROTO,$ToUfdb,$URI,$IP,$userid,$DEBUGHOSTNAME)){
		if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRules: SEND");}
		continue;
	}
	if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRules: DONE");}	
	
	
	
	$results=trim($results);
	if(trim($results)==null){
		if($GLOBALS["DebugLoop"]){events( "NOTHING -> NULL".__LINE__);}
		Output_results(null,__FUNCTION__,__LINE__);
		continue;
	}
	
	if($GLOBALS["DebugLoop"]){events( "END LOOP".__LINE__);}
	

}

events("Stopping Webfiltering client.");
HyperCacheCleanBuffer();
events("Die Webfiltering client.");
die();



function gethostbyaddrCached($ipaddr){
	if(isset($GLOBALS["gethostbyaddr"][$ipaddr])){return $GLOBALS["gethostbyaddr"][$ipaddr];}
	$GLOBALS["gethostbyaddr"][$ipaddr]=gethostbyaddr($ipaddr);
	if(count($GLOBALS["gethostbyaddr"])>1500){$GLOBALS["gethostbyaddr"]=array();}
	
}


$Ipconvert=gethostbyaddr($DEBUGHOSTNAME);

function QuotaSize($IP,$userid,$URI,$sitename,$PARAMS){
	if(preg_match("#([0-9\.]+)#", $IP,$re)){$IP=$re[1];}
	$CACHEKEY=md5("$IP,$userid,$URI,$sitename");
	$CONNECT=false;
	$PROTO=$PARAMS["PROTO"];
	if($PROTO=="CONNECT"){$CONNECT=true;}
	
	if(isset($GLOBALS["QuotaSizeResults"][$CACHEKEY])){
		$TimeExec=$GLOBALS["QuotaSizeResults"][$CACHEKEY]["TIME"];
		if(tool_time_sec($TimeExec)<30){
			if($GLOBALS["QuotaSizeResults"][$CACHEKEY]["RETURN"]<>null){
				Output_results($GLOBALS["QuotaSizeResults"][$CACHEKEY]["RETURN"],__FUNCTION__,__LINE__);
				return true;
			}
		}
		return false;
		
	}
	
	$quota=new ufdbgquota($CACHEKEY);

	if(count($GLOBALS["ARTICA_QUOTAS_RULES"])==0){
		if($GLOBALS["DebugQuota"]){QuotaEvent( "ARTICA_QUOTAS_RULES == 0 [".__LINE__);}
		return false;
	}
	
	if($quota->parse_rules($IP,$userid,$URI,$sitename)){
		QuotaEvent("Return true;");
		Output_results($quota->returned,__FUNCTION__,__LINE__);
		return true;
	}
	
	
	
}
function QuotaEvent($text,$line=0){
	if(trim($text)==null){return;}
	$pid=$GLOBALS["MYPID"];
	$date=@date("H:i:s");
	$logFile="/var/log/squid/ufdbgclient.quotas.debug";
	$time_end=tool_microtime_float();
	$tt = round($time_end - $GLOBALS["time_loop_start"],3);

	$size=@filesize($logFile);
	if($size>9000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');

	@fwrite($f, "$date:[".basename(__FILE__)."/{$GLOBALS["UFDBVERS"]} $pid [{$GLOBALS["LOG_DOM"]}]:$text - {$tt}ms $line\n");
	@fclose($f);
}
function HyperCacheRules($PROTO,$ToUfdb,$URI,$IP,$userid,$DEBUGHOSTNAME){
	
	
	if($GLOBALS["SquidEnforceRules"]==0){return;}
	
	
	if(preg_match("#([0-9\.]+)\/(.*)#", $IP,$re)){
		$IP=$re[1];
		$USER=$re[2];
	}
	if($IP=="127.0.0.1"){return;}
	
	
	
	
	if($PROTO<>"GET"){
		if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRules:$PROTO/$IP <> GET ABORT");}
		return false;
	}
	if($GLOBALS["SquidEnforceRules"]==0){
		if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRules:$IP Not enabled <> GET ABORT");}
		return;
	}
	
	if(HyperCacheRulesBlacklist($DEBUGHOSTNAME)){return;}
	
	$URI=HyperCacheCleanUri($URI);
	
	if($GLOBALS["HyperCacheOK"][md5($URI)]){
		Output_results($GLOBALS["HyperCacheOK"][md5($URI)],__FUNCTION__,__LINE__);
		if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRules:`$URI` -> REDIRECT MEM");}
		return true;
		
	}
	
	include_once(dirname(__FILE__)."/ressources/class.HyperCache.inc");
	include_once(dirname(__FILE__)."/ressources/class.hyperCache-central.inc");
	
	
	$HyperCache=new HyperCache();
	if($HyperCache->HyperCacheRulesMatchPattern($GLOBALS["HyperCacheListenAddr"], $URI)){return false;}
	
	if(HyperCacheRuleMirror($URI)){return true;}
	if(HyperCacheWhiteUri($URI)){return false;}
	
	
	$ID=$HyperCache->HyperCacheRulesMatches($URI);
	if($ID==0){
		if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRules: ID == $ID FALSE [".__LINE__."]");}
		return false;
	}
	
	if(HyperCacheRulesSave($URI,$ID)){
		return true;
	}
	
	if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRules:`$URI` no match");}
}

function HyperCacheRulesMatches($URI){
	$MAIN=$GLOBALS["HyperCacheRules"];
	if(count($MAIN)==0){return 0;}
	
	
	while (list ($ID, $ligne) = each ($MAIN) ){
		$sitename=$ligne["sitename"];
		if(preg_match("#regex:(.+)#", $sitename,$re)){ 
			$sitename=$re[1]; 
			if(preg_match("#$sitename#i", $URI)){return $ID;}
			continue;
		}
		
		$sitename=tool_string_to_regex($sitename);
		if(preg_match("#(^|\.)$sitename$#",$URI)){return $ID;}
			
	}
	
	return 0;
}


function HyperCacheRuleMirror($uri){
	
	if(isset($GLOBALS["HyperCacheOK"][md5($uri)])){
		Output_results($GLOBALS["HyperCacheOK"][md5($uri)],__FUNCTION__,__LINE__);
		return true;
	}
	
	$RULES=$GLOBALS["HyperCacheRulesMirror"];
	
	$HyperCache=new HyperCache();
	while (list ($sitename, $ligne) = each ($RULES) ){
		$sitename=$HyperCache->HyperCacheUriToHostname($sitename);
		if($HyperCache->HyperCacheRulesMatchPattern($sitename, $uri)){
			if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRuleMirror: $uri, matches $sitename");}
			$H=parse_url($uri);
			$path=$H["path"];
			$query=$H["query"];
			if($query<>null){$query="?$query";}
			if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRuleMirror: $sitename");}
			$link="http://{$GLOBALS["HyperCacheListenAddr"]}:{$GLOBALS["HyperCacheHTTPListenPort"]}/$sitename$path$query";
			if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRuleMirror: $link");}
			$GLOBALS["HyperCacheOK"][md5($uri)]=$link;
			Output_results($link,__FUNCTION__,__LINE__);
			return true;
			
		}
		
		
	}
	
}


function tool_string_to_regex($pattern){
	if(trim($pattern)==null){return null;}
	$pattern=str_replace("/", "\/", $pattern);
	$pattern=str_replace(".", "\.", $pattern);
	//$pattern=str_replace("-", "\-", $pattern);
	$pattern=str_replace("[", "\[", $pattern);
	$pattern=str_replace("]", "\]", $pattern);
	$pattern=str_replace("(", "\(", $pattern);
	$pattern=str_replace(")", "\)", $pattern);
	$pattern=str_replace("$", "\$", $pattern);
	$pattern=str_replace("?", "\?", $pattern);
	$pattern=str_replace("#", "\#", $pattern);
	$pattern=str_replace("{", "\{", $pattern);
	$pattern=str_replace("}", "\}", $pattern);
	$pattern=str_replace("^", "\^", $pattern);
	$pattern=str_replace("!", "\!", $pattern);
	$pattern=str_replace("+", "\+", $pattern);
	$pattern=str_replace("*", ".*?", $pattern);
	$pattern=str_replace("|", "\|", $pattern);
	return $pattern;

}


function HyperCacheRulesIsStored($uri){
	
	$familysite=tool_get_familysite($uri);
	$dbfile="/usr/share/squid3/HyperCache-$familysite-Retranslation.db";
	if(!is_file($dbfile)){
		if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRulesIsStored: $dbfile no such file");}
		return false;
	}
	$db_con = @dba_open($dbfile, "r","db4");
	if(!$db_con){
		events("HyperCacheRulesIsStored:: FATAL!!!::$dbfile, unable to open"); 
		return false; 
	}
	$md5=md5($uri);
	
	if(!@dba_exists($md5,$db_con)){
		@dba_close($db_con);
		if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRulesIsStored:`$uri` Not downloaded -> FALSE");}
		return false;
	}
		
	if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRulesIsStored:`$uri` Exists");}
	
	$fetch_content=@dba_fetch($md5,$db_con);$array=@unserialize($fetch_content);
	@dba_close($db_con);
	
	
	
	
	$MD5File=$array["MD5FILE"];
	$FileType=$array["MD5TYPE"]=$FileType;
	$extention=$array["EXT"];
	$TargetFile=$array["TARGET"];
	$basename=basename($TargetFile);
	if($extention==null){$extention="html";}
	if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRulesIsStored: $basename / $MD5File.$extention / content type:$FileType");}
	$link="http://{$GLOBALS["HyperCacheListenAddr"]}:{$GLOBALS["HyperCacheHTTPListenPort"]}/$MD5File.$extention";
	if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRulesIsStored: $link");}
	$GLOBALS["HyperCacheOK"][md5($uri)]=$link;
	Output_results($link,__FUNCTION__,__LINE__);
	return true;
}

function EnforceRules_extension($filename){
	$parts = explode('.',strtolower($filename));
	$last = count($parts) - 1;
	$ext = $parts[$last];
	return $ext;
}




function HyperCacheWhiteUri($URI){
	
	if(count($GLOBALS["HyperCacheRulesWhiteList"])==0){return false;}
	$HyperCache=new HyperCache();
	$HyperCacheRulesWhiteList=$GLOBALS["HyperCacheRulesWhiteList"];
	
	
	while (list ($pattern, $line) = each ($HyperCacheRulesWhiteList)){
		if($HyperCache->HyperCacheRulesMatchPattern($pattern,$URI)){return true;}
		
	}
}

function HyperCacheCleanUri($uri){
	
	if(preg_match("#\&rand=([0-9\.]+)#", $uri,$re)){
		$uri=str_replace("&rand={$re[1]}", "", $uri);
		return $uri;
	}
	
	if(preg_match("#\?__t=([0-9\.]+)#", $uri,$re)){
		$uri=str_replace("?__t={$re[1]}", "", $uri);
		return $uri;
	}
	
	if(preg_match("#[a-z0-9]+\.js\?(.+)#",$uri,$re)){
		$uri=str_replace("?{$re[1]}", "", $uri);
		return $uri;
	}
	
	if(preg_match("#\?dojo\.preventCache=([0-9\.]+)#", $uri,$re)){
		$uri=str_replace("?dojo.preventCache={$re[1]}", "", $uri);
		return $uri;
	}
	

	
	$uri=ifTracker($uri);
	
	return $uri;
}

function HyperCacheRules_get($URI,$ID){
	$uri_md5=md5($URI);
	$familysite=tool_get_familysite($URI);
	
	if(isset($GLOBALS["HyperCacheRulesSave"]["SAVED"][$uri_md5])){
		if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRules_get: IN QUEUE FROM MEM... [".__LINE__."]");}
		return true;
	}
	$dbfile="/usr/share/squid3/HyperCacheQueue-$familysite-$ID.db";
	
	if(!is_file($dbfile)){
		if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRules_get: /usr/share/squid3/HyperCacheQueue-$familysite-$ID.db no such file [".__LINE__."]");}
		return false;
	}
	$db_con = @dba_open($dbfile, "r","db4");
	if(!$db_con){events("HyperCacheRulesSave:: FATAL!!!::{$dbfile}, unable to open");return false; }
	if(@dba_exists($uri,$db_con)){
		@dba_close($db_con);
		if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRules_get: IN QUEUE FROM HyperCacheQueue-$familysite-$ID.db... [".__LINE__."]");}
		$GLOBALS["HyperCacheRulesSave"]["SAVED"][$uri_md5]=true;
		return true;
	}
}

function tool_create_berekley($db_path){
	if(is_file($db_path)){return true;}
	events("berekley_db_create:: Creating $db_path database");
	$db_desttmp = @dba_open($db_path, "c","db4");
	@dba_close($db_desttmp);
	if(is_file($db_path)){return true;}
}

function HyperCacheCleanBuffer(){
	if(!isset($GLOBALS["HYPER_CACHE_BUFFER"])){
		$GLOBALS["HYPER_CACHE_BUFFER_COUNT"]=0;
		$GLOBALS["HYPER_CACHE_BUFFER"]=array();
		return;
	}
	
	if(count($GLOBALS["HYPER_CACHE_BUFFER"])==0){
		$GLOBALS["HYPER_CACHE_BUFFER_COUNT"]=0;
		$GLOBALS["HYPER_CACHE_BUFFER"]=array();
		return;
	}
	
	while (list ($dbfile, $array) = each ($GLOBALS["HYPER_CACHE_BUFFER"]) ){
		if(!tool_create_berekley($dbfile)){return;}
		$db_con = @dba_open($dbfile, "c","db4");
		if(!$db_con){events("HyperCacheCleanBuffer:: FATAL!!!::{$dbfile}, unable to open");return false; }
		while (list ($index, $url) = each ($array) ){
			events("HyperCacheCleanBuffer:: Clean buffer $dbfile -> $url");
			if(!@dba_replace($url,"NONE",$db_con)){events("HyperCacheCleanBuffer:: FAILED SAVING *** $URI ***"); @dba_close($db_con); return false; }
		}
		$GLOBALS["HYPER_CACHE_BUFFER"][$dbfile]=array();
		@dba_close($db_con);
	}
	$GLOBALS["HYPER_CACHE_BUFFER_COUNT"]=0;
	$GLOBALS["HYPER_CACHE_BUFFER"]=array();
	return true;
	
}

function HyperCacheRules_set($URI,$ID){
	$uri_md5=md5($URI);
	$familysite=tool_get_familysite($URI);
	$HyperCacheBuffer=$GLOBALS["HyperCacheBuffer"];
	if(!isset($GLOBALS["HYPER_CACHE_BUFFER_COUNT"])){$GLOBALS["HYPER_CACHE_BUFFER_COUNT"]=0;}
	
	$dbfile="/usr/share/squid3/HyperCacheQueue-$familysite-$ID.db";
	$GLOBALS["HYPER_CACHE_BUFFER"][$dbfile][]=$URI;
	$GLOBALS["HYPER_CACHE_BUFFER_COUNT"]++;
	
	if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRules_set: add to buffer {$GLOBALS["HYPER_CACHE_BUFFER_COUNT"]}/$HyperCacheBuffer [".__LINE__."]");}
	
	if($GLOBALS["HYPER_CACHE_BUFFER_COUNT"]<$HyperCacheBuffer){return;}
	HyperCacheCleanBuffer();
	
}

function tool_get_familysite($uri){
	$parse_url=parse_url($uri);
	$sitename=$parse_url["host"];
	if(isset($GLOBALS["FAMILYSITES"][$sitename])){return $GLOBALS["FAMILYSITES"][$sitename];}
	$f=new familysite();
	$GLOBALS["FAMILYSITES"][$sitename]=$f->GetFamilySites($sitename);
	return $GLOBALS["FAMILYSITES"][$sitename];
	
}


function HyperCacheRulesSave($uri,$ID){
	$uri_md5=md5($uri);
	
	if(HyperCacheRulesIsStored($uri)){
		if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRulesSave: HyperCacheRulesIsStored(..) -> TRUE [".__LINE__."]");}
		return true;
	}
	
	if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRulesSave: HyperCacheRulesIsStored(..) -> FALSE [".__LINE__."]");}
	
	if(HyperCacheRules_get($uri,$ID)){
		return;
	}
	if($GLOBALS["HyperCacheDebug"]){events("HyperCacheRulesSave: HyperCacheRules_get(..) -> NONE -> ADD TO QUEUE BUFFER={$GLOBALS["HYPER_CACHE_BUFFER_COUNT"]} [".__LINE__."]");}
	HyperCacheRules_set($uri,$ID);
	
	
	
	
}




function GoogleSafeBrowsing($URI,$PROTO,$HOST,$IP,$userid){
	if($PROTO<>"GET"){return false;}
	if($GLOBALS["EnableGoogleSafeBrowsing"]==0){return false;}
	if(trim($GLOBALS["GoogleSafeBrowsingApiKey"])==null){return false;}
	if(preg_match("#(youtube|google|dropbox|apple)\.[a-z]+$#", $HOST)){return false;}
	$response=GoogleSafeBrowsingInCache($HOST);
	if($response==null){$response=GoogleSafeBrowsingGet($PROTO,$HOST);}
	
	if(!$response){return false;}
	if($response==null){return false;}
	
	//A BLOCKER safebrowsing.clients.google.com
	if($GLOBALS["GOOGLE_SAFE"]){events("GoogleSafeBrowsing: response: $HOST = `$response`");}
	$response=trim(strtolower($response));
	if(preg_match("#(malware|phishing)#is", $response) ){
		if($GLOBALS["GOOGLE_SAFE"]){events("GoogleSafeBrowsing: Saving: $HOST = `$response`");}
		GoogleSafeBrowsingOutCache($HOST,trim(strtolower($response)));
		if(preg_match("#([0-9\.]+)#", $IP,$re)){$IP=$re[1];}
		if($userid=="-"){$userid=null;}
		$urlenc=urlencode($URI);
		$returned="{$GLOBALS["SquidGuardIPWeb"]}?rule-id=0SquidGuardIPWeb=".
		base64_encode($GLOBALS["SquidGuardIPWeb"])."&clientaddr=$IP&clientname=$IP&clientuser=$userid".
		"&clientgroup=Default-$response&targetgroup=safebrowsing&url=$urlenc";
		ufdbgevents($response,"safebrowsing");
		Output_results($returned,__FUNCTION__,__LINE__);
		return true;
		
	}
	
	if($GLOBALS["GOOGLE_SAFE"]){events("GoogleSafeBrowsing: $HOST = `clean`");}
	GoogleSafeBrowsingOutCache($HOST,"clean");
	
	
	
}


function GoogleSafeBrowsingOutCache($servername,$response){
	$dbfile="/var/log/squid/GoogleSafeBrowsing.db";
	$MD5=md5($servername);
	$GLOBALS["GoogleSafeBrowsingMEMCache"][$MD5]=$response;
	
	tool_create_berekley($dbfile);
	if(!is_file($dbfile)){return null;}
	
	$db_con = @dba_open($dbfile, "c","db4");
	if(!$db_con){events("GoogleSafeBrowsingOutCache:: FATAL!!!::$dbfile, unable to open"); return null; }
	
	if(!@dba_exists("CREATED",$db_con)){
		$GLOBALS["GoogleSafeBrowsingCache_time"]=time();
		@dba_replace("CREATED",time(),$db_con);
	
	}
	
	@dba_replace($MD5,$response,$db_con);
	@dba_close($db_con);
	
}

function GoogleSafeBrowsingInCache($servername){
	$dbfile="/var/log/squid/GoogleSafeBrowsing.db";
	$GoogleSafeBrowsingCacheTime=$GLOBALS["GoogleSafeBrowsingCacheTime"];
	
	if(isset($GLOBALS["GoogleSafeBrowsingCache_time"])){
		$Since=tool_time_min($GLOBALS["GoogleSafeBrowsingCache_time"]);
		
		if($GLOBALS["GOOGLE_SAFE"]){events("GoogleSafeBrowsingInCache: $dbfile ({$Since}Mn/{$GoogleSafeBrowsingCacheTime}Mn");}
		
		if($Since>$GoogleSafeBrowsingCacheTime){
			$GLOBALS["GoogleSafeBrowsingMEMCache"]=array();
			@unlink($dbfile); 
		}
	}
	
	$MD5=md5($servername);
	if(isset($GLOBALS["GoogleSafeBrowsingMEMCache"][$MD5])){
		if(count($GLOBALS["GoogleSafeBrowsingMEMCache"])>64000){$GLOBALS["GoogleSafeBrowsingMEMCache"]=array();}
		return $GLOBALS["GoogleSafeBrowsingMEMCache"][$MD5];
	}
	
	tool_create_berekley($dbfile);
	if(!is_file($dbfile)){return null;}
	
	$db_con = @dba_open($dbfile, "r","db4");
	if(!$db_con){events("GoogleSafeBrowsingInCache:: FATAL!!!::$dbfile, unable to open"); return null; }
	
	if(!@dba_exists("CREATED",$db_con)){
		$GLOBALS["GoogleSafeBrowsingCache_time"]=time();
		@dba_replace("CREATED",time(),$db_con);
	
	}
	
	if(!isset($GLOBALS["GoogleSafeBrowsingCache_time"])){
		if(@dba_exists("CREATED",$db_con)){
			$GLOBALS["GoogleSafeBrowsingCache_time"]=dba_fetch("CREATED",$db_con);
		}
	}
	
	if(!@dba_exists($MD5,$db_con)){
		@dba_close($db_con);
		return null;
	}
	
	$result = dba_fetch($MD5,$db_con);
	$GLOBALS["GoogleSafeBrowsingMEMCache"][$MD5]=$result;
	@dba_close($db_con);
	return $result;
	
}

function tool_time_min($timeFrom){
	$data1 = $timeFrom;
	$data2 = time();
	$difference = ($data2 - $data1);
	$results=intval(round($difference/60));
	if($results<0){$results=1;}
	return $results;
}

function tool_time_sec($last_time){
	$data1 = $last_time;
	$data2 = time();
	$difference = ($data2 - $data1);
	return $difference;
}


function FileWatcher($uri){
	
	
	
	
}

function Curl_get_headers($uri){
	
	
	
	$curl = curl_init($uri);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($curl, CURLOPT_FAILONERROR, FALSE);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($curl, CURLOPT_FRESH_CONNECT, TRUE);
	curl_setopt($curl, CURLOPT_FORBID_REUSE, TRUE);
	curl_setopt($curl, CURLOPT_DNS_CACHE_TIMEOUT, 3600);
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
	curl_setopt($curl, CURLOPT_TIMEOUT, 10);
	curl_setopt($curl, CURLOPT_HEADER, TRUE);
	curl_setopt($curl, CURLOPT_FILETIME, TRUE);
	curl_setopt($curl, CURLOPT_NOBODY, TRUE);
	$header = curl_exec($curl);
	$info = curl_getinfo($curl);
	curl_close($curl);
}





function GoogleSafeBrowsingGet($PROTO,$servername){
	
	if(isset($GLOBALS["SafeBrowsingSTOP"])){
		if($GLOBALS["SafeBrowsingSTOP"]>0){
			if(tool_time_sec($GLOBALS["SafeBrowsingSTOP"])<300){return null;}
			
		}
	}
	
	
	$start_time = microtime(true);
	if(!isset($GLOBALS["PROXY"]["ArticaProxyServerEnabled"])){
		$GLOBALS["PROXY"]["ArticaProxyServerEnabled"]="no";
		$GLOBALS["PROXY"]["ArticaProxyServerName"]=null;
		$GLOBALS["PROXY"]["ArticaProxyServerPort"]=null;
		$GLOBALS["PROXY"]["ArticaProxyServerUsername"]=null;
		$GLOBALS["PROXY"]["ArticaProxyServerUserPassword"]=null;
	}

	$ArticaProxyServerEnabled=$GLOBALS["PROXY"]["ArticaProxyServerEnabled"];
	$ArticaProxyServerName=$GLOBALS["PROXY"]["ArticaProxyServerName"];
	$ArticaProxyServerPort=$GLOBALS["PROXY"]["ArticaProxyServerPort"];
	$ArticaProxyServerUsername=trim($GLOBALS["PROXY"]["ArticaProxyServerUsername"]);
	$ArticaProxyServerUserPassword=$GLOBALS["PROXY"]["ArticaProxyServerUserPassword"];

	
	$servername=urlencode("http://$servername/");
	
	$url="https://sb-ssl.google.com/safebrowsing/api/lookup?client=api&apikey={$GLOBALS["GoogleSafeBrowsingApiKey"]}&appver=1.5.2&pver=3.1&url=$servername";
	
	if($GLOBALS["GOOGLE_SAFE"]){
		events("GoogleSafeBrowsingGet: $url");
	}
	
	$curl = curl_init($url);
	
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($curl, CURLOPT_FAILONERROR, FALSE);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($curl, CURLOPT_FRESH_CONNECT, TRUE);
	curl_setopt($curl, CURLOPT_FORBID_REUSE, TRUE);
	curl_setopt($curl, CURLOPT_DNS_CACHE_TIMEOUT, 3600);
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
	curl_setopt($curl, CURLOPT_TIMEOUT, 10);
	if(trim($GLOBALS["GoogleSafeBrowsingDNS"])<>null){
			@curl_setopt($curl, CURLOPT_DNS_SERVERS,$GLOBALS["GoogleSafeBrowsingDNS"]); 
	}
	if($GLOBALS["GoogleSafeBrowsingInterface"]<>null){curl_setopt($curl, CURLOPT_INTERFACE,$GLOBALS["GoogleSafeBrowsingInterface"]); }

	if($ArticaProxyServerEnabled=="yes"){
		curl_setopt($curl,CURLOPT_HTTPPROXYTUNNEL,FALSE);
		curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
		curl_setopt($curl, CURLOPT_PROXY, $ArticaProxyServerName);
		curl_setopt($curl, CURLOPT_PROXYPORT, $ArticaProxyServerPort);
		if($ArticaProxyServerUsername<>null){
			curl_setopt($curl, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
			curl_setopt($curl, CURLOPT_PROXYUSERPWD, $ArticaProxyServerUsername.':'.$ArticaProxyServerUserPassword);
		}
	}

	$response = curl_exec($curl);
	$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	$end_time = microtime(true);
	$Infos= curl_getinfo($curl);
	$TimedSec=$end_time - $start_time;
	if($GLOBALS["GOOGLE_SAFE"]){events("GoogleSafeBrowsingGet: Connection {$TimedSec}ms"); }
	
	if(!$response){
		if($http_status==204){
			@curl_close($curl);
			return "clean";
		}
		$errno = curl_errno($curl);
		$error_message=curl_strerror($errno);
		
		if($errno==28){
			events("GoogleSafeBrowsingGet: DNS...: {$GLOBALS["GoogleSafeBrowsingDNS"]}, Interface \"{$GLOBALS["GoogleSafeBrowsingInterface"]}\"");
			ufdbg_admin_mysql(1, "PID {$GLOBALS["MYPID"]}: Google Safe Browsing Timed Out, skipping protection for 5mn", "Requested URL: $url\nSleeping during 5 minutes",__FILE__,__LINE__);
			$GLOBALS["SafeBrowsingSTOP"]=time();
		}
		curl_close($curl);
		if(isset($GLOBALS["SafeBrowsingERROR"])){
			if($GLOBALS["SafeBrowsingERROR"]>0){
				if(tool_time_sec($GLOBALS["SafeBrowsingERROR"])<180){return null;}
			}
		}
		ufdbg_admin_mysql(1, "PID {$GLOBALS["MYPID"]}: Google Safe Browsing HTTP Error code $errno ($error_message)", "Requested URL: $url\n",__FILE__,__LINE__);
		$GLOBALS["SafeBrowsingERROR"]=time();
		return null;
	}
	
	if(isset($GLOBALS["SafeBrowsingSTOP"])){
		if($GLOBALS["SafeBrowsingSTOP"]>0){
			ufdbg_admin_mysql(1, "PID {$GLOBALS["MYPID"]}: Google Safe Browsing relinked", "",__FILE__,__LINE__);
			$GLOBALS["SafeBrowsingSTOP"]=0;
		}
	}
	
	if(isset($GLOBALS["SafeBrowsingERROR"])){
		if($GLOBALS["SafeBrowsingERROR"]>0){
			ufdbg_admin_mysql(1, "PID {$GLOBALS["MYPID"]}: Google Safe Browsing relinked", "",__FILE__,__LINE__);
			$GLOBALS["SafeBrowsingERROR"]=0;
		}
	}
	
	
	
	curl_close($curl);
	return $response;
}

function tool_microtime_float(){
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}

function curl_strerror($errno){
	$error_codes=array(
			1 => 'CURLE_UNSUPPORTED_PROTOCOL',
			2 => 'CURLE_FAILED_INIT',
			3 => 'CURLE_URL_MALFORMAT',
			4 => 'CURLE_URL_MALFORMAT_USER',
			5 => 'CURLE_COULDNT_RESOLVE_PROXY',
			6 => 'CURLE_COULDNT_RESOLVE_HOST',
			7 => 'CURLE_COULDNT_CONNECT',
			8 => 'CURLE_FTP_WEIRD_SERVER_REPLY',
			9 => 'CURLE_REMOTE_ACCESS_DENIED',
			11 => 'CURLE_FTP_WEIRD_PASS_REPLY',
			13 => 'CURLE_FTP_WEIRD_PASV_REPLY',
			14=>'CURLE_FTP_WEIRD_227_FORMAT',
			15 => 'CURLE_FTP_CANT_GET_HOST',
			17 => 'CURLE_FTP_COULDNT_SET_TYPE',
			18 => 'CURLE_PARTIAL_FILE',
			19 => 'CURLE_FTP_COULDNT_RETR_FILE',
			21 => 'CURLE_QUOTE_ERROR',
			22 => 'CURLE_HTTP_RETURNED_ERROR',
			23 => 'CURLE_WRITE_ERROR',
			25 => 'CURLE_UPLOAD_FAILED',
			26 => 'CURLE_READ_ERROR',
			27 => 'CURLE_OUT_OF_MEMORY',
			28 => 'CURLE_OPERATION_TIMEDOUT',
			30 => 'CURLE_FTP_PORT_FAILED',
			31 => 'CURLE_FTP_COULDNT_USE_REST',
			33 => 'CURLE_RANGE_ERROR',
			34 => 'CURLE_HTTP_POST_ERROR',
			35 => 'CURLE_SSL_CONNECT_ERROR',
			36 => 'CURLE_BAD_DOWNLOAD_RESUME',
			37 => 'CURLE_FILE_COULDNT_READ_FILE',
			38 => 'CURLE_LDAP_CANNOT_BIND',
			39 => 'CURLE_LDAP_SEARCH_FAILED',
			41 => 'CURLE_FUNCTION_NOT_FOUND',
			42 => 'CURLE_ABORTED_BY_CALLBACK',
			43 => 'CURLE_BAD_FUNCTION_ARGUMENT',
			45 => 'CURLE_INTERFACE_FAILED',
			47 => 'CURLE_TOO_MANY_REDIRECTS',
			48 => 'CURLE_UNKNOWN_TELNET_OPTION',
			49 => 'CURLE_TELNET_OPTION_SYNTAX',
			51 => 'CURLE_PEER_FAILED_VERIFICATION',
			52 => 'CURLE_GOT_NOTHING',
			53 => 'CURLE_SSL_ENGINE_NOTFOUND',
			54 => 'CURLE_SSL_ENGINE_SETFAILED',
			55 => 'CURLE_SEND_ERROR',
			56 => 'CURLE_RECV_ERROR',
			58 => 'CURLE_SSL_CERTPROBLEM',
			59 => 'CURLE_SSL_CIPHER',
			60 => 'CURLE_SSL_CACERT',
			61 => 'CURLE_BAD_CONTENT_ENCODING',
			62 => 'CURLE_LDAP_INVALID_URL',
			63 => 'CURLE_FILESIZE_EXCEEDED',
			64 => 'CURLE_USE_SSL_FAILED',
			65 => 'CURLE_SEND_FAIL_REWIND',
			66 => 'CURLE_SSL_ENGINE_INITFAILED',
			67 => 'CURLE_LOGIN_DENIED',
			68 => 'CURLE_TFTP_NOTFOUND',
			69 => 'CURLE_TFTP_PERM',
			70 => 'CURLE_REMOTE_DISK_FULL',
			71 => 'CURLE_TFTP_ILLEGAL',
			72 => 'CURLE_TFTP_UNKNOWNID',
			73 => 'CURLE_REMOTE_FILE_EXISTS',
			74 => 'CURLE_TFTP_NOSUCHUSER',
			75 => 'CURLE_CONV_FAILED',
			76 => 'CURLE_CONV_REQD',
			77 => 'CURLE_SSL_CACERT_BADFILE',
			78 => 'CURLE_REMOTE_FILE_NOT_FOUND',
			79 => 'CURLE_SSH',
			80 => 'CURLE_SSL_SHUTDOWN_FAILED',
			81 => 'CURLE_AGAIN',
			82 => 'CURLE_SSL_CRL_BADFILE',
			83 => 'CURLE_SSL_ISSUER_ERROR',
			84 => 'CURLE_FTP_PRET_FAILED',
			84 => 'CURLE_FTP_PRET_FAILED',
			85 => 'CURLE_RTSP_CSEQ_ERROR',
			86 => 'CURLE_RTSP_SESSION_ERROR',
			87 => 'CURLE_FTP_BAD_FILE_LIST',
			88 => 'CURLE_CHUNK_FAILED'
			);
	
	return $error_codes[$errno];
}



function reset_memory(){
	if(!is_file("/var/log/squid/reload/{$GLOBALS["MYPID"]}.ufdbgclient.php")){return;}
	events("reset_memory: Reseting memory...");
	events("FREE MEMORY");
	unset($GLOBALS["WhitelistedBase"]);
	unset($GLOBALS["BlacklistedBase"]);
	unset($GLOBALS["CACHE"]);
	unset($GLOBALS["HyperCacheRulesSave"]);
	unset($GLOBALS["GoogleSafeBrowsingCache_time"]);
	unset($GLOBALS["GoogleSafeBrowsingMEMCache"]);
	unset($GLOBALS["NOTIFS"]);
	unset($GLOBALS["HyperCacheRules"]);
	unset($GLOBALS["HyperCacheOK"]);
	unset($GLOBALS["ARTICA_QUOTAS_RULES_CHECK_NO_FILE"]);
	unset($GLOBALS["ARTICA_QUOTAS_RULES_CHECK"]);
	HyperCacheRulesLoad();
	
	@unlink("/var/log/squid/reload/{$GLOBALS["MYPID"]}.ufdbgclient.php");
	
	ufdbconfig();
}

function HyperCacheRulesLoad(){
	if($GLOBALS["SquidEnforceRules"]==0){return;}
	$GLOBALS["HyperCacheRulesMirror"]=array();
	$GLOBALS["HyperCacheRules"]=array();
	$GLOBALS["HyperCacheRulesWhiteList"]=array();
	
	
	$dbfile="/usr/share/squid3/HyperCacheRules.db";
	if(!is_file($dbfile)){return;}
	
	$db_con = dba_open($dbfile, "r","db4");
	
	if(!$db_con){
		events("HyperCache: HyperCacheRulesLoad():: FATAL!!!::$dbfile, unable to open");
		return false;
	}
	
	$mainkey=trim(dba_firstkey($db_con));
	
	while($mainkey !=false){
		$array=unserialize(dba_fetch($mainkey,$db_con));
		
		$GLOBALS["HyperCacheRules"][$mainkey]=$array;
		events("HyperCache: HyperCacheRulesLoad():: Loading rule ID $mainkey");
		$mainkey=dba_nextkey($db_con);
		
	}
	
	dba_close($db_con);
	
	$dbfile="/usr/share/squid3/HyperCacheRules_wl.db";
	if(!is_file($dbfile)){return;}
	$db_con = dba_open($dbfile, "r","db4");
	
	if(!$db_con){
		events("HyperCache: HyperCacheRulesLoad():: FATAL!!!::$dbfile, unable to open");
		return false;
	}
	
	$mainkey=trim(dba_firstkey($db_con));
	
	while($mainkey !=false){
		
	
		$GLOBALS["HyperCacheRulesWhiteList"][$mainkey]=TRUE;
		events("HyperCache: HyperCacheRulesLoad():: Loading whitelist rule $mainkey");
		$mainkey=dba_nextkey($db_con);
	
	}
	
	dba_close($db_con);	
// -------------------------------------------------------------------------------------------------------	
	$dbfile="/usr/share/squid3/HyperCacheRules_mirror.db";
	if(!is_file($dbfile)){return;}
	$db_con = dba_open($dbfile, "r","db4");
	
	if(!$db_con){
		events("HyperCache: HyperCacheRulesLoad():: FATAL!!!::$dbfile, unable to open");
		return false;
	}
	
	$mainkey=trim(dba_firstkey($db_con));
	
	while($mainkey !=false){
		$GLOBALS["HyperCacheRulesMirror"][$mainkey]=TRUE;
		events("HyperCache: HyperCacheRulesLoad():: Loading Mirror rule $mainkey");
		$mainkey=dba_nextkey($db_con);
	
	}
	
	dba_close($db_con);
// -------------------------------------------------------------------------------------------------------	
	
}


function Unlocked($url,$IP,$userid){
	$dbfile="/var/log/squid/ufdbgclient.unlock.db";
	if(!is_file($dbfile)){
		if($GLOBALS["DEBUG_UNLOCKED"]){events("ufdbunlock: $dbfile no such file");}
		return false;
	}
	$H=parse_url($url);
	$domain=$H["host"];
	$hostname=null;
	if(preg_match("#([0-9\.]+)\/(.*)#", $IP,$re)){
		$hostname=$re[2];
		$IP=$re[1];
	}
	
	
	$db_con = dba_open($dbfile, "r","db4");
	
	if(!$db_con){
		if($GLOBALS["DEBUG_UNLOCKED"]){events("ufdbunlock: berekley_db_size:: FATAL!!!::$dbfile, unable to open");}
		return false;
	}
	
	$mainkey=trim(dba_firstkey($db_con));
	
	if($GLOBALS["DEBUG_UNLOCKED"]){events("ufdbunlock: mainkey: { $mainkey }");}
	
	if(strlen($mainkey)>3){
		if($GLOBALS["DEBUG_UNLOCKED"]){events("ufdbunlock: $mainkey");}
		$array=unserialize(dba_fetch($mainkey,$db_con));
		if(Unlocked_parse($array,$url,$IP,$userid)){
			dba_close($db_con);
			return true;
		}
	}
	
	while($mainkey !=false){
		$val=0;
	
		if($GLOBALS["DEBUG_UNLOCKED"]){events("ufdbunlock: { $mainkey }");}
		if(trim($mainkey)==null){
			$mainkey=dba_nextkey($db_con);
			continue;
		}
		$array=unserialize(dba_fetch($mainkey,$db_con));
		if(Unlocked_parse($array,$url,$IP,$userid)){
			dba_close($db_con);
			return true;
		}
		
	
		$mainkey=dba_nextkey($db_con);
	}
	
	
	dba_close($db_con);
	return false;
}

function Unlocked_parse($array,$url,$IP,$userid){
	$userid=trim(strtolower($userid));
	$uid=trim(strtolower($array["uid"]));
	$ipaddr=$array["ipaddr"];
	$www=$array["www"];
	$finaltime=$array["finaltime"];
	$www=str_replace(".", "\.", $www);
	if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}
	
	$H=parse_url($url);
	$domain=$H["host"];
	if(preg_match("#^www\.(.+)#", $domain,$re)){$domain=$re[1];}
	
	if($GLOBALS["DEBUG_UNLOCKED"]){events("ufdbunlock: $uid,$ipaddr,$www,$finaltime -> $domain,$IP,$userid");}
	
	if($finaltime<time()){
	if($GLOBALS["DEBUG_UNLOCKED"]){events("$finaltime -> EXPIRED");}
		return false;
	}
	
	
	if(!preg_match("#$www#i", $domain)){
		if($GLOBALS["DEBUG_UNLOCKED"]){events("ufdbunlock: '$domain'/$www -> DOMAIN MISMATCH");}
		return false;
	}
	
	if($IP==$ipaddr){
		if($GLOBALS["DEBUG_UNLOCKED"]){events("ufdbunlock: '$IP'/'$ipaddr' -> IP ADDRESS MATCH");}
		Output_results(null,__FUNCTION__,__LINE__);
		return true;
	
	}else{
		if($GLOBALS["DEBUG_UNLOCKED"]){events("ufdbunlock: '$IP'/'$ipaddr' -> IP ADDRESS MISSMATCH");}
	}
	
	
	if($userid<>null){
		if($uid<>null){
			if($userid<>$uid){
				if($GLOBALS["DEBUG_UNLOCKED"]){events("ufdbunlock: $userid -> NO MATCH");}
				return false;
				
			}
		}
		
	}

	
	if($GLOBALS["DEBUG_UNLOCKED"]){events("ufdbunlock: OK!");}
	Output_results(null,__FUNCTION__,__LINE__);
	return true;
	
	

	
}


function surblclient($ARRAY){
	$URI=$ARRAY["ORGURI"];
	if(isset($GLOBALS["URBL_SAFE"])){
		if(count($GLOBALS["URBL_SAFE"])>10000){$GLOBALS["URBL_SAFE"]=array();}
	}
	if(isset($GLOBALS["URBL_UNSAFE"])){
		if(count($GLOBALS["URBL_UNSAFE"])>10000){$GLOBALS["URBL_UNSAFE"]=array();}
	}	
	
	$H=parse_url($URI);
	$domain=$H["host"];
	$userid=$ARRAY["userid"];
	$PROTO=$ARRAY["PROTO"];
	$IP=$ARRAY["IP"];
	
	$MAC=$ARRAY["MAC"];
	
	if(preg_match("#([0-9\.]+)\/(.*)#", $IP,$re)){
		$hostname=$re[2];
		$IP=$re[1];
	}
	
	$SquidGuardIPWeb=$GLOBALS["SquidGuardIPWeb"];
	$CONNECT=false;
	$KEY=null;
	
	
	
	
	if(isset($GLOBALS["URBL_SAFE"][$domain])){
		if($GLOBALS["DEBUG_DNSBL"]){events( "$domain URBL_SAFE MEMORY ".__LINE__);}
		return false;}
	
	if(!class_exists("Blacklist")){
		include_once(dirname(__FILE__)."/ressources/surblclient.inc");
		
	}
	

	if(!isset($GLOBALS["URBL_UNSAFE"][$domain])){
		if($GLOBALS["DEBUG_DNSBL"]){events( "$domain: new Blacklist($URI) ".__LINE__);}
		$url_c = new Blacklist($URI);
		
		if(!$url_c->spam_check) {
			$GLOBALS["URBL_SAFE"][$domain]=false;
			return;
		}
	
	}else{
		if($GLOBALS["DEBUG_DNSBL"]){events( "$domain URBL_UNSAFE MEMORY ".__LINE__);}
	}
	
	
	$GLOBALS["URBL_UNSAFE"][$domain]=true;
	$urlenc=urlencode($URI);
	
	$returned="{$GLOBALS["SquidGuardIPWeb"]}?rule-id=0SquidGuardIPWeb=".
			base64_encode($GLOBALS["SquidGuardIPWeb"])."&clientaddr=$IP&clientname=$IP&clientuser=$userid".
			"&clientgroup=default&targetgroup=DNSBL&url=$urlenc";
	
	
	ufdbgevents("default","DNSBL");
	Output_results($returned,__FUNCTION__,__LINE__);
	return true;
	
}

function Ranswomare($ARRAY){

	$function=__FUNCTION__;
	$database="/etc/squid3/ransomwaretracker.db";
	if(!is_file($database)){
		if($GLOBALS["DEBUG_RANSOMARE"]){events("$database no such file");}
		return false;
	}
	$URI=$ARRAY["ORGURI"];
	$urlenc=urlencode($URI);
	$SquidGuardIPWeb=$GLOBALS["SquidGuardIPWeb"];
	$userid=$ARRAY["userid"];
	$PROTO=$ARRAY["PROTO"];
	$IP=$ARRAY["IP"];
	$IpClass=new IP();
	if(preg_match("#([0-9\.]+)\/(.*)#", $IP,$re)){$hostname=$re[2];$IP=$re[1];}
	
	
	if($GLOBALS["DEBUG_RANSOMARE"]){events( "$function: RANSOMARE_FTIME = {$GLOBALS["RANSOMARE_FTIME"]}");}
	
	if(!isset($GLOBALS["RANSOMARE_FTIME"])){
		$GLOBALS["RANSOMARE_FTIME"]=filemtime($database);
		$GLOBALS["RANSOMARE_DB"]=unserialize(@file_get_contents($database));
	}
	if(!isset($GLOBALS["RANSOMARE_DB"])){
		$GLOBALS["RANSOMARE_FTIME"]=filemtime($database);
		$GLOBALS["RANSOMARE_DB"]=unserialize(@file_get_contents($database));
	}
	
	$ftime=filemtime($database);
	if($ftime<>$GLOBALS["RANSOMARE_FTIME"]){
		$GLOBALS["RANSOMARE_FTIME"]=filemtime($database);
		$GLOBALS["RANSOMARE_DB"]=unserialize(@file_get_contents($database));
	}
	
	
	$MAIN=$GLOBALS["RANSOMARE_DB"];
	$H=parse_url($URI);
	$domain=$H["host"];
	if(strpos($domain, ":")>0){$xdomain=explode(":",$domain);$domain=$xdomain[0];}
	$domain=strtolower($domain);
	
	if(preg_match("#(\/|\.)(windowsupdate|microsoft|netflix|google|msftncsi|teamviewer|lastpass|steamusercontent|nflxvideo|kaspersky)\.[a-z]+#", $domain)){
		if($GLOBALS["DEBUG_RANSOMARE"]){events( "$function: $domain: \"SKIP\"");}
		return false;
	}

	
	if($GLOBALS["DEBUG_RANSOMARE"]){events( "$function: $domain: \"$URI\"");}
	
	
	$returned="{$GLOBALS["SquidGuardIPWeb"]}?rule-id=0SquidGuardIPWeb=".
			base64_encode($GLOBALS["SquidGuardIPWeb"])."&clientaddr=$IP&clientname=$IP&clientuser=$userid".
			"&clientgroup=default&targetgroup=ransomware&url=$urlenc";
	
	if($IpClass->isValid($domain)){
		if($GLOBALS["DEBUG_RANSOMARE"]){events( "$function: $domain: Check $domain in ".count($MAIN["IPS"])." IPS");}
		if(isset($MAIN["IPS"][$domain])){
			ufdbgevents("default","ransomware");
			if($GLOBALS["DEBUG_RANSOMARE"]){events( "$function: $domain: TRUE IP");}
			Output_results($returned,__FUNCTION__,__LINE__);
			return true;
		}
		if($GLOBALS["DEBUG_RANSOMARE"]){events( "$function: $domain: \"PASS [OK]\"");}
		return false;
	}
	
	
	$f=new squid_familysite();
	$familysite=$f->GetFamilySites($domain);

	if($GLOBALS["DEBUG_RANSOMARE"]){events( "$function: $domain: Check $domain in ".count($MAIN["DOMAINS"])." domains");}
	if(isset($MAIN["DOMAINS"][$domain])){
		if($GLOBALS["DEBUG_RANSOMARE"]){events( "$domain: TRUE DOMAIN");}
		ufdbgevents("default","ransomware");
		Output_results($returned,__FUNCTION__,__LINE__);
		return true;
		
	}
	
	if($GLOBALS["DEBUG_RANSOMARE"]){events( "$function: $domain: Check $familysite in ".count($MAIN["DOMAINS"])." domains");}
	
	if(isset($MAIN["DOMAINS"][$familysite])){
		if($GLOBALS["DEBUG_RANSOMARE"]){events( "$function: $domain: TRUE DOMAIN FAMILYSITE");}
		ufdbgevents("default","ransomware");
		Output_results($returned,__FUNCTION__,__LINE__);
		return true;
	}
	
	
	if($GLOBALS["DEBUG_RANSOMARE"]){events( "$function: $domain: Check $URI in ".count($MAIN["URIS"])." Urls");}
	if(isset($MAIN["URIS"][$URI])){
		if($GLOBALS["DEBUG_RANSOMARE"]){events( "$function: $domain: TRUE URL");}
		ufdbgevents("default","ransomware");
		Output_results($returned,__FUNCTION__,__LINE__);
		return true;
	}
	
	if($GLOBALS["DEBUG_RANSOMARE"]){events( "$function: $domain: \"PASS [OK]\"");}
	
}


function Phistank($ARRAY){
	$URI=$ARRAY["URI"];

	
	
	
	
	if(!is_file("/etc/squid3/phistank.db")){
		if($GLOBALS["VERBOSE"]){events("/etc/squid3/phistank.db no such file");}
		return false;
	}
	
	
	$CONNECT=false;
	$KEY=null;
	if(isset($ARRAY["MAC"])){$MAC=$ARRAY["MAC"];}
	

	
	$H=parse_url($URI);
	$domain=$H["host"];
	
	$md51=md5($domain);
	$md52=md5($URI);
	$md53=md5($URI."/");
	
	
	$userid=$ARRAY["userid"];
	$PROTO=$ARRAY["PROTO"];
	$IP=$ARRAY["IP"];
	
	if(isset($GLOBALS["PHISHTANK_QUEUE"])){
		if(count($GLOBALS["PHISHTANK_QUEUE"])>10000){$GLOBALS["PHISHTANK_QUEUE"]=array();}
	}
	
	if(preg_match("#([0-9\.]+)\/(.*)#", $IP,$re)){
		$hostname=$re[2];
		$IP=$re[1];
	}
	
	$SquidGuardIPWeb=$GLOBALS["SquidGuardIPWeb"];
	$CONNECT=false;
	$KEY=null;
	if(isset($ARRAY["MAC"])){$MAC=$ARRAY["MAC"];}
	$urlenc=urlencode($URI);
	
	$returned="{$GLOBALS["SquidGuardIPWeb"]}?rule-id=0SquidGuardIPWeb=".
			base64_encode($GLOBALS["SquidGuardIPWeb"])."&clientaddr=$IP&clientname=$IP&clientuser=$userid".
			"&clientgroup=default&targetgroup=phishtank&url=$urlenc";
	
	$md5Key=md5("$userid$IP$URI");
	if(isset($GLOBALS["PHISHTANK_QUEUE"][$md5Key])){
		ufdbgevents("default","phishtank");
		Output_results($GLOBALS["PHISHTANK_QUEUE"][$md5Key],__FUNCTION__,__LINE__);
		return true;
	}
	
	$db_con = @dba_open("/etc/squid3/phistank.db", "r","db4");
	if(!$db_con){
		if($GLOBALS["VERBOSE"]){events("Phistank: FATAL!!!::/etc/squid3/phistank.db, unable to open");}
		return false;
	}
	
	if(@dba_exists($md51,$db_con)){
		@dba_close($db_con);
		ufdbgevents("phishtank","phishtank");
		$GLOBALS["PHISHTANK_QUEUE"][$md5Key]=$returned;
		Output_results($returned,__FUNCTION__,__LINE__);
		return true;
	}
	if(@dba_exists($md52,$db_con)){
		@dba_close($db_con);
		ufdbgevents("default","phishtank");
		$GLOBALS["PHISHTANK_QUEUE"][$md5Key]=$returned;
		Output_results($returned,__FUNCTION__,__LINE__);
		return true;
	}
	if(@dba_exists($md53,$db_con)){
		@dba_close($db_con);
		ufdbgevents("phishtank","phishtank");
		$GLOBALS["PHISHTANK_QUEUE"][$md5Key]=$returned;
		Output_results($returned,__FUNCTION__,__LINE__);
		return true;
	}	
	
	return false;
	
	
	

	
}

function BlackListedBase($url,$IP,$userid,$PROTO){
	$db_path="/var/log/squid/ufdbgclient.black.db";
	$CONNECT=false;
	if(!is_file($db_path)){
		if($GLOBALS["DEBUG_BLACKLIST"]){events("$db_path -> no such file");}
		return false;
	}
	
	$H=parse_url($url);
	$domain=$H["host"];
	
	if($GLOBALS["DEBUG_BLACKLIST"]){events("$url -> $domain PROTO: $PROTO");}
	
	reset_memory();
	if($GLOBALS["SquidGuardIPWeb"]==null){
		if($GLOBALS["DEBUG_BLACKLIST"]){events("http://127.0.0.1/ufdbguardd.php");}
		$GLOBALS["SquidGuardIPWeb"]="http://127.0.0.1/ufdbguardd.php";
	}
	$urlenc=urlencode($url);
	if(preg_match("#([0-9\.]+)#", $IP,$re)){$IP=$re[1];}
	if($userid=="-"){$userid=null;}
	$returned="{$GLOBALS["SquidGuardIPWeb"]}?rule-id=0SquidGuardIPWeb=".
	base64_encode($GLOBALS["SquidGuardIPWeb"])."&clientaddr=$IP&clientname=$IP&clientuser=$userid".
	"&clientgroup=global-blacklist&targetgroup=blacklist&url=$urlenc";
	
	
	if($PROTO=="CONNECT"){
		$CONNECT=true;
		if($GLOBALS["SquidGuardWebUseExternalUri"]==1){$returned=$GLOBALS["SquidGuardWebExternalUriSSL"];}else{
		$returned="https://{$GLOBALS["SquidGuardServerName"]}:{$GLOBALS["SquidGuardApacheSSLPort"]}/ufdbguardd.php?rule-id=0SquidGuardIPWeb=".
				base64_encode("https://{$GLOBALS["SquidGuardServerName"]}:{$GLOBALS["SquidGuardApacheSSLPort"]}")."&clientaddr=$IP&clientname=$IP&clientuser=$userid".
				"&clientgroup=global-blacklist&targetgroup=blacklist&url=$urlenc";
		}
	}

	
	if(isset($GLOBALS["BlacklistedBase"][$domain])){
		if($GLOBALS["DEBUG_BLACKLIST"]){events("BlackListedBase: $domain -> IN MEMORY [OK]"); }
		if($GLOBALS["BlacklistedBase"][$domain]){
			if($GLOBALS["DEBUG_BLACKLIST"]){events("BlackListedBase: $domain -> MEM BLOCK"); }
			ufdbgevents("blacklist","global-blacklist");
			Output_results($returned,__FUNCTION__,__LINE__,$CONNECT);
			return true;
		}else{
			if($GLOBALS["DEBUG_BLACKLIST"]){events("BlackListedBase: $domain -> MEM [PASS]"); }
			return false;
		}
	}else{
		if($GLOBALS["DEBUG_BLACKLIST"]){events("$domain -> MEMORY NOT BLACKLISTED");}
		
	}
	
	
	$db_con = dba_open($db_path, "r","db4");
	if(!$db_con){return false;}
	$mainkey=trim(dba_firstkey($db_con));
	
	while($mainkey !=false){
		$val=0;
	
	
		if(trim($mainkey)==null){
			$mainkey=dba_nextkey($db_con);
			continue;
		}
		
		if($GLOBALS["DEBUG_BLACKLIST"]){events("BlackListedBase:Checking $mainkey -> $domain"); }
		if(preg_match("#$mainkey#", $domain)){
			$GLOBALS["BlacklistedBase"][$domain]=true;
			if($GLOBALS["DEBUG_BLACKLIST"]){events("BlackListedBase:  BLACKLIST MATCH $mainkey -> $domain"); }
			ufdbgevents("blacklist","global-blacklist");
			if($GLOBALS["DEBUG_BLACKLIST"]){events("Output_results($returned)"); }
			Output_results($returned,__FUNCTION__,__LINE__,$CONNECT);
			dba_close($db_con);
			return true;
		}
	
		$mainkey=dba_nextkey($db_con);
	
	}
	if($GLOBALS["DEBUG_BLACKLIST"]){events("$domain -> STAMP MEMORY TO FALSE"); }
	$GLOBALS["BlacklistedBase"][$domain]=false;
	dba_close($db_con);
	return false;	
	
	
}

function Output_results($results=null,$function=null,$line=null,$CONNECT=false){
	if($GLOBALS["DEBUG_PROTOCOL"]){events("Output_results::[".__LINE__."] ACCEPTING : [$results]");}
	$called=null;
	$prefix_channel=null;
	$suffix_channel_loging=null;
	$results_org=$results;
	$results=trim($results);
	$results=str_replace("POST  HTTP/1.1","",$results);
	$results=str_replace("HTTP/1.1","",$results);
	$results=str_replace("\n", "", $results);
	$GLOBALS["SquidGuardWebSSLCompatibility"]=1;
	$LineTOSend=null;
	$statusCode=0;
	$DonotChangeKey=false;
	$URI=null;
	$prefix_channel=null;
	$suffix_channel_loging=null;
	
	$key="url";
	
	$SquidGuardRedirectBehavior=trim($GLOBALS["SquidGuardRedirectBehavior"]);
	if($SquidGuardRedirectBehavior=="url-rewrite"){$SquidGuardRedirectBehavior="rewrite-url";}
	$results=str_replace("GET  HTTP/1.1","",$results);
	$results=trim($results);
	$results=str_replace("\n", "", $results);
	$results=str_replace("\r", "", $results);
	
	if(is_numeric($GLOBALS["CHANNEL"])){
		if($GLOBALS["CHANNEL"]>0){
			$prefix_channel="{$GLOBALS["CHANNEL"]} ";
			$suffix_channel_loging="Channel ID [{$GLOBALS["CHANNEL"]}]";
			if($GLOBALS["DEBUG_OUTPUT"]){ events("Output_results:$suffix_channel_loging");}
		}
	}
	
	if(preg_match("#^OK\s+(.+)#", $results,$re)){$results=$re[1];}	
	
	
	
	
	
	if($results==null){
		$LineTOSend="{$prefix_channel}OK";
		if($GLOBALS["DEBUG_OUTPUT"]){ events("Output_results: PASS \"$LineTOSend\" $suffix_channel_loging Line ".__LINE__);}
		events_output("Output_results::[".__LINE__."] [$LineTOSend]");
		print("$LineTOSend\n");
		return;	
	}
	
	events_output("PREPARE::[".__LINE__."] [$results]");
	

	
	
	if(strpos($results, " ")>0){
		$MAIN=explode(" ", $results);
		while (list ($index, $pattern) = each ($MAIN)){
			$pattern=trim($pattern);
			if($GLOBALS["DEBUG_OUTPUT"]){ events("Output_results: Analyze: <{$pattern}>");}
			
			
			if(preg_match("#^status=([0-9]+)#", $pattern,$re)){
				$statusCode=$re[1];
				continue;
			}
			if(preg_match("#^rewrite-url=\"(.*)\"#", $pattern,$re)){
				$key="rewrite-url";
				$URI=$re[1];
				$DonotChangeKey=true;
				continue;
			}
			
			if(preg_match("#^url=\"(.*)\"#", $pattern,$re)){
				if($GLOBALS["DEBUG_OUTPUT"]){ events("Output_results: Found URL: [{$re[1]}]");}
				$URI=$re[1];
				$key="url";
				$DonotChangeKey=true;
				continue;
			}
			
		}

	}else{
		if(preg_match("#^rewrite-url=\"(.*)\"#", $results,$re)){
			$URI=$re[1];
			$key="rewrite-url";
			$DonotChangeKey=true;
		}
		if(preg_match("#^url=\"(.*)\"#", $results,$re)){
			$URI=$re[1];
			$key="url";
			$DonotChangeKey=true;
		}
		
		
		if(!$DonotChangeKey){ $URI=$results; }
	}
	
	
	if(!$DonotChangeKey){
		if($GLOBALS["SquidGuardWebUseExternalUri"]==1){$URI=$GLOBALS["SquidGuardWebExternalUri"]; }
	}
	$GLOBALS["SquidGuardWebExternalUri"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidGuardWebExternalUri"));
	$GLOBALS["SquidGuardWebExternalUriSSL"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidGuardWebExternalUriSSL"));
	
	
	$statusCode=$GLOBALS["SquidGuardRedirectHTTPCode"];
	if(!is_numeric($statusCode)){$statusCode=302;}
	if($statusCode<300){$statusCode=302;}
	
	if($GLOBALS["DEBUG_PROTOCOL"]){
		events("Output_results: CONNECT=[$CONNECT] SquidGuardRedirectBehavior = $SquidGuardRedirectBehavior, key=$key, DonotChangeKey = $DonotChangeKey URI=[$URI]");
	}
	
	
	
	if(!$DonotChangeKey){$key=trim($GLOBALS["SquidGuardRedirectBehavior"]);}
	if($CONNECT){
		if($GLOBALS["SquidGuardWebUseExternalUri"]==1){$URI=$GLOBALS["SquidGuardWebExternalUriSSL"];}
		if(preg_match("#^https:\/\/(.*)#", $URI,$re)){$URI=$re[1];}
		
	}
	
	
	if($GLOBALS["DEBUG_PROTOCOL"]){events("Output_results::[".__LINE__."] URI=[$URI] KEY=[$key] ( SquidGuardWebSSLCompatibility={$GLOBALS["SquidGuardWebSSLCompatibility"]})");}
	
	if($key==null){$key="url";}
	if($key=="url-rewrite"){$key="rewrite-url";}
	
	
	if($key<>"rewrite-url"){
		if($SquidGuardRedirectBehavior<>null){
			if($key<>$SquidGuardRedirectBehavior){
				if($GLOBALS["DEBUG_PROTOCOL"]){events("Output_results: $key transformed to $SquidGuardRedirectBehavior");}
				$key=$SquidGuardRedirectBehavior;
			}
		}
	}
	
	$URI=trim($URI);
	if($URI==null){
		events("Output_results: URI = NULL ! \"$results_org\" Line ".__LINE__);
		$LineTOSend="{$prefix_channel}OK";
		events_output("Output_results::[".__LINE__."] [$LineTOSend]");
		print("$LineTOSend\n");
		if($GLOBALS["DEBUG_PROTOCOL"]){events_output("**");}
		return;
	}
	
	if($CONNECT){
		$key="rewrite-url";
		if($GLOBALS["DEBUG_PROTOCOL"]){events("Output_results::[".__LINE__."] CONNECT=TRUE: URI=[$URI] ( SquidGuardWebSSLCompatibility={$GLOBALS["SquidGuardWebSSLCompatibility"]})");}
	}
	
	
	if($key=="rewrite-url"){
		if($GLOBALS["DEBUG_PROTOCOL"]){events("Output_results: REWRITE URL METHOD Line ".__LINE__);}
		if($CONNECT){
			$URI=str_replace("http://", "https://", $URI);
			$URI=str_replace("https://", "", $URI);
			$LineTOSend="{$prefix_channel}OK {$key}=\"$URI\"";
			if($GLOBALS["DEBUG_PROTOCOL"]){events("Output_results::[".__LINE__."] FINAL SSL=[$LineTOSend]");}
			print("$LineTOSend\n");
			if($GLOBALS["DEBUG_PROTOCOL"]){events_output("**");}
			return;
		}
		
		if($GLOBALS["DEBUG_PROTOCOL"]){events("Output_results::[".__LINE__."] CONNECT FALSE URI=[$URI];");}
		$LineTOSend="{$prefix_channel}OK {$key}=\"$URI\"";
		print("$LineTOSend\n");
		if($GLOBALS["DEBUG_PROTOCOL"]){events_output("**");}
		return;
		
	}
		
	if(!preg_match("#^http:\/#", $URI)){$URI="http://$URI";}
		
	$LineTOSend="{$prefix_channel}OK status=$statusCode {$key}=\"$URI\"";
	
	
	if($GLOBALS["DEBUG_OUTPUT"]){ 
		if($GLOBALS["DebugQuota"]){QuotaEvent("Output_results: BLOCK \"$LineTOSend\" $suffix_channel_loging Line ".__LINE__);}
		events("Output_results: BLOCK [BY `$function`] \"$LineTOSend\" $suffix_channel_loging Line ".__LINE__);
	
	}
	if($GLOBALS["DEBUG_PROTOCOL"]){
		events("Output_results: BLOCK [BY `$function`] \"$LineTOSend\" $suffix_channel_loging Line ".__LINE__);
	}
	
	events_output("Output_results::[".__LINE__."] [$LineTOSend]");
	if($GLOBALS["DEBUG_BLACKLIST"]){events("BlackListedBase: $LineTOSend\\n"); }
	print("$LineTOSend\n");
}





function UfdbBlackList($ToUfdb,$PARAMS,$ToUfdbKey){
	
	$results=trim(ask_to_ufdb($ToUfdb,$PARAMS,$ToUfdbKey));
	if(preg_match("#SquidGuardIPWeb=(.+?)&#", $results,$re)){$GLOBALS["SquidGuardIPWeb"]=base64_decode($re[1]);}
	
	$length=strlen(trim($results));
	if($length<5){return false;}

	$CONNECT=false;
	$PROTO=$PARAMS["PROTO"];
	if($PROTO=="CONNECT"){$CONNECT=true;}
	if($GLOBALS["DEBUG_WEBFILTERING"]){events("UfdbBlackList:[{$GLOBALS["UFDB_SERVER"]}] $results");}
	Output_results($results,__FUNCTION__,__LINE__,$CONNECT);
	return true;
}

function WhitelistedBase_domain($domain){
	
	if(isset($GLOBALS["WhitelistedBase"][$domain])){
		
		if($GLOBALS["WhitelistedBase"][$domain]){
			if($GLOBALS["DEBUG_WHITELIST"]){events("WhitelistedBase MEM $domain WHITELISTED"); }
			return 1;
		}else{
			if($GLOBALS["DEBUG_WHITELIST"]){events("WhitelistedBase MEM $domain NOT WHITELISTED"); }
			return 2;
		}
		
	}
	
	return 0;
	
}

function WhitelistedBase($url){
	$db_path="/var/log/squid/ufdbgclient.white.db";
	$H=parse_url($url);
	$domain=$H["host"];
	
	$fam=new familysite();
	$familysite=$fam->GetFamilySites($domain);
	
	$WhitelistedBase_domain=WhitelistedBase_domain($domain);
	if($WhitelistedBase_domain==1){return true;}
	$WhitelistedBase_domain=WhitelistedBase_domain($familysite);
	if($WhitelistedBase_domain==1){return true;}
	if($WhitelistedBase_domain==2){return false;}
	
	if(!is_file($db_path)){
		if($GLOBALS["DEBUG_WHITELIST"]){events("WHITELIST:: $db_path -> no such file");}
		return false;
	}
	$db_con = dba_open($db_path, "r","db4");
	if(!$db_con){return false;}
	$mainkey=dba_firstkey($db_con);
	
	$domain_regex=str_replace(".", "\.", $domain);
	$family_regex=str_replace(".", "\.", $familysite);
	
	while($mainkey !=false){
		$val=0;
	
	
		if(trim($mainkey)==null){
			$mainkey=dba_nextkey($db_con);
			continue;
		}

		if($GLOBALS["DEBUG_WHITELIST"]){events("WHITELIST:: WhitelistedBase: Checking $mainkey -> $domain"); }
		if(preg_match("#$mainkey#", $domain)){
			if($GLOBALS["DEBUG_WHITELIST"]){events("WHITELIST:: WhitelistedBase $mainkey MATCH $domain"); }
			$GLOBALS["WhitelistedBase"][$domain]=true;
			dba_close($db_con);
			return true;
		}
		
		if($GLOBALS["DEBUG_WHITELIST"]){events("WHITELIST:: #$mainkey# NO MATCH $domain"); }
		
		if(preg_match("#$mainkey#", $familysite)){
			if($GLOBALS["DEBUG_WHITELIST"]){events("WHITELIST:: WhitelistedBase $mainkey MATCH $familysite"); }
			$GLOBALS["WhitelistedBase"][$familysite]=true;
			dba_close($db_con);
			return true;
		}
		if($GLOBALS["DEBUG_WHITELIST"]){events("WHITELIST:: #$mainkey# NO MATCH $domain"); }
		$mainkey=dba_nextkey($db_con);
	
	}
	dba_close($db_con);
	
	
	if($GLOBALS["DEBUG_WHITELIST"]){events("WHITELIST:: Assume $domain FALSE"); }
	if($GLOBALS["DEBUG_WHITELIST"]){events("WHITELIST:: Assume $familysite FALSE"); }
	if(!isset($GLOBALS["WhitelistedBase"])){$GLOBALS["WhitelistedBase"]=array();}
	
	$CountOf=count($GLOBALS["WhitelistedBase"]);
	if($GLOBALS["DEBUG_WHITELIST"]){events("WHITELIST:: $CountOf domains in memory"); }
	if($CountOf>5000){$GLOBALS["WhitelistedBase"]=array();}
	
	$GLOBALS["WhitelistedBase"][$domain]=false;
	$GLOBALS["WhitelistedBase"][$familysite]=false;
	
	return false;

}

function ask_to_videocache($datatosend){
	
	$array=explode(" ", $datatosend);
	$URI=$array[0];
	$IP=$array[1];
	$userid=$array[2];
	$PROTO=$array[3];
	$myIP=$array[4];
	$myPort=$array[5];
	if(!videocache_checker($URI)){
		if($GLOBALS["VIDEOCACHE_DEBUG"]){events("VIDEOCACHE videocache_checker return FALSE"); }
		return "\n";
	}
	
	if(!isset($GLOBALS["PROCESS_VIDEOCACHE"])){
		if($GLOBALS["VIDEOCACHE_DEBUG"]){events("VIDEOCACHE not a ressource"); }
		return;
	}
	if (!is_resource($GLOBALS["PROCESS_VIDEOCACHE"])) {
		if($GLOBALS["VIDEOCACHE_DEBUG"]){events("VIDEOCACHE not a ressource (2)"); }
		return;
	}
	$text=null;
	if($GLOBALS["VIDEOCACHE_DEBUG"]){events("Send \"$datatosend\""); }
	fwrite($GLOBALS["pipes_videocache"][0], $datatosend);
	$text= fgets($GLOBALS["pipes_videocache"][1], 2048);
	
	$strlen=strlen(trim($text));
	if($GLOBALS["VIDEOCACHE_DEBUG"]){events("VIDEOCACHE RESPONSE \"$text\""); }
	if($strlen>5){
		
		$text=str_replace("\r\n", "", $text);
		$text=str_replace("\r", "", $text);
		$text=str_replace("\n", "", $text);
		$text=trim($text);
		if($GLOBALS["VERBOSE"]){
			if($GLOBALS["VIDEOCACHE_DEBUG"]){events("VIDEOCACHE RESPONSE"); }
		}
	}
	
	return $text;

	
	
}

function videocache_checker($uri){
	
	
	$parse_url=parse_url($uri);
	$hosntame=$parse_url["host"];
	if(isset($GLOBALS["videocache_checker_false"][$hosntame])){return false;}
	
	$domzDeny[]="\.manifest\.youtube\.com";
	$domzDeny[]="\.manifest\.googlevideo\.com";
	$domzDeny[]="\.redirector\.googlevideo\.com";
	$domzDeny[]="\.redirector\.youtube\.com";
	
	while (list ($num, $pattern) = each ($domzDeny)){
		if(preg_match("#$pattern#i", $hosntame)){
			$GLOBALS["videocache_checker_false"][$hosntame]=false;
			return false;
		}
		
	}
			
			
	
	$vc_deny_url[]="crossdomain\.xml";
	$vc_deny_url[]="\.blip\.tv\/(.*?)filename \.hardsextube\.com\/videothumbs";
	$vc_deny_url[]="\.xtube\.com\/(.*)(Thumb|videowall)";
	
	$vc_deny_url[]="\.(youtube|googlevideo)\.com\/.*\/manifest";
	$vc_deny_url[]="\.(youtube|googlevideo)\.com\/videoplayback?.*?playerretry=[0-9]";
	
	while (list ($num, $pattern) = each ($domzDeny)){
		if(preg_match("#$pattern#i", $uri)){
			
			return false;}
	
	}
	
	$domz[]="\.stream\.aol\.com";
	$domz[]="\.5min\.com";
	$domz[]="\.msn\.com";
	$domz[]="\.blip\.tv";
	$domz[]="\.dmcdn\.net";
	$domz[]="\.break\.com";
	$domz[]="\.vimeo\.com";
	$domz[]="\.vimeocdn\.com ";
	$domz[]="video\.thestaticvube\.com";
	$domz[]="\.dailymotion\.com";
	$domz[]="\.c\.wrzuta\.pl";
	$domz[]="\.v\.imwx\.com";
	$domz[]="\.mccont\.com";
	$domz[]="\.myspacecdn\.com";
	$domz[]="video-http\.media-imdb\.com";
	$domz[]="fcache\.veoh\.com";
	$domz[]="\.hardsextube\.com";
	$domz[]="\.public\.extremetube\.phncdn\.com";
	$domz[]="\.redtubefiles\.com";
	$domz[]="\.video\.pornhub\.phncdn\.com";
	$domz[]="\.videos\.videobash\.com";
	$domz[]="\.public\.keezmovies\.com";
	$domz[]="\.public\.keezmovies\.phncdn\.com";
	$domz[]="\.slutload-media\.com";
	$domz[]="\.public\.spankwire\.com";
	$domz[]="\.xtube\.com";
	$domz[]="\.public\.youporn\.phncdn\.com";
	$domz[]="\.xvideos\.com";
	$domz[]="\.tube8\.com";
	$domz[]="\.public\.spankwire\.phncdn\.com";
	$domz[]="\.pornhub\.com";
	$domz[]="\.youtube\.com";
	$domz[]="\.googlevideo\.com";	
	$domza[]="msn\..*?\.(com|net)";
	$domza[]="msnbc\..*?\.(com|net)";
	$domza[]="video\..*?\.fbcdn\.net";
	$domza[]="myspacecdn\..*?\.footprint\.net";
	
	while (list ($num, $pattern) = each ($domz)){
		if(preg_match("#$pattern#i", $hosntame)){
			if($GLOBALS["VERBOSE"]){events("VIDEOCACHE(1) $hosntame ** detected ** $pattern..."); }
			return true;}
	
	}
	if($GLOBALS["VERBOSE"]){events("VIDEOCACHE(1) $hosntame not detected..."); }
	
	
	while (list ($num, $pattern) = each ($domza)){
		if(preg_match("#^$pattern#i", $hosntame)){
			if($GLOBALS["VERBOSE"]){events("VIDEOCACHE(2) $hosntame ** detected ** $pattern..."); }
			return true;}
	
	}
	if($GLOBALS["VERBOSE"]){events("VIDEOCACHE(2) $hosntame not detected..."); }
	
	$vc_url[]="\/youku\/[0-9A-Z]+\/[0-9A-Z\-]+\.(flv|mp4|avi|mkv|mp3|rm|rmvb|m4v|mov|wmv|3gp|mpg|mpeg)";
	$vc_url[]="\/(.*?)key=[a-z0-9]+(.*?)\.flv";
	$vc_url[]="\-xh\.clients\.cdn[0-9a-zA-Z]?[0-9a-zA-Z]?[0-9a-zA-Z]?\.com\/data\/(.*?)\.flv";
	$vc_url[]="\.(youtube|youtube-nocookie|googlevideo)\.com\/feeds\/api\/videos\/[0-9a-zA-Z_-]{11}\/";
	$vc_url[]="\.(youtube|youtube-nocookie|googlevideo)\.com\/(videoplayback|get_video|watch_popup|user_watch|stream_204|get_ad_tags|get_video_info|player_204|ptracking|set_awesome)\?";
	$vc_url[]="\.(youtube|youtube-nocookie|googlevideo)\.com\/(v|e|embed)\/[0-9a-zA-Z_-]{11}";
	$vc_url[]="\.youtube\.com\/s\? \.youtube\.com\/api\/stats\/(atr|delayplay|playback|watchtime)\?";
	$vc_url[]="\.(youtube|youtube-nocookie|googlevideo)\.com\/videoplayback\/id\/[0-9a-zA-Z_-]+\/";
	$vc_url[]="\.android\.clients\.google\.com\/market\/GetBinary\/";
	$vc_url[]="cs(.*?)\.vk\.me\/(.*?)/([a-zA-Z0-9.]+)\.(flv|mp4|avi|mkv|mp3|rm|rmvb|m4v|mov|wmv|3gp|mpg|mpeg)";
	$vc_url[]="video(.*?)\.rutube\.ru\/(.*?)/([a-zA-Z0-9.]+)\.(flv|mp4|avi|mkv|mp3|rm|rmvb|m4v|mov|wmv|3gp|mpg|mpeg)Seg[0-9]+-Frag[0-9]+";
	
	while (list ($num, $pattern) = each ($vc_url)){
		if(preg_match("#$pattern#i", $uri)){
			if($GLOBALS["VERBOSE"]){events("VIDEOCACHE(3) $hosntame ** detected ** $pattern..."); }
			return true;}
	
	}
	if($GLOBALS["VERBOSE"]){events("VIDEOCACHE(3) $hosntame not detected ..."); }
	
}

function ask_to_ufdb_cache_get($ToUfdbKey){
		
	if($GLOBALS["DEBUG_IN_MEM"]){events("ask_to_ufdb: MEMORY = ".count($GLOBALS["CACHE"])." items");}
	if(isset($GLOBALS["CACHE"][$ToUfdbKey])){return array(true,$GLOBALS["CACHE"][$ToUfdbKey]);}
	return array(false,null);
}
function ask_to_ufdb_cache_set($ToUfdbKey,$buf){
	$GLOBALS["CACHE"][$ToUfdbKey]=$buf;
	if(count($GLOBALS["CACHE"])>5000){$GLOBALS["CACHE"]=array();}
}

function ask_to_ufdb_time_sec($last_modified){
	$data1 = $last_modified;
	$data2 = time();
	$difference = ($data2 - $data1);
	return round($difference);
}

function ask_to_ufdb($datatosend,$PARAMS,$ToUfdbKey){
	
	if(!isset($GLOBALS["UFDB_SOCKET_ERROR"])){$GLOBALS["UFDB_SOCKET_ERROR"]=0;}
	if(!isset($GLOBALS["UfdbgclientMaxSockTimeOut"])){$GLOBALS["UfdbgclientMaxSockTimeOut"]=0;}
	if($GLOBALS["UfdbgclientMaxSockTimeOut"]==0){$GLOBALS["UfdbgclientMaxSockTimeOut"]=6;}
	
	if(intval($GLOBALS["UFDB_SOCKET_ERROR"])>intval($GLOBALS["UfdbgclientMaxSockTimeOut"])){
		if(!isset($GLOBALS["UFDB_SOCKET_ERROR_TIME"])){$GLOBALS["UFDB_SOCKET_ERROR_TIME"]=time();return false;}
		if($GLOBALS["UFDB_SOCKET_ERROR_TIME"]==0){$GLOBALS["UFDB_SOCKET_ERROR_TIME"]=time();return false;}
		$ask_to_ufdb_time_sec=ask_to_ufdb_time_sec($GLOBALS["UFDB_SOCKET_ERROR_TIME"]);
		
		if($ask_to_ufdb_time_sec==10){
			ufdbg_admin_mysql(1, "Web filtering Current 10 seconds to wait retry working with webfiltering in 80 seconds...", null,__FILE__,__LINE__);
		}
		
		if($ask_to_ufdb_time_sec==30){
			ufdbg_admin_mysql(1, "Web filtering Current 30 seconds to wait retry working with webfiltering in 60 seconds...", null,__FILE__,__LINE__);
		}
		
		if($ask_to_ufdb_time_sec==60){
			ufdbg_admin_mysql(1, "Web filtering Current 60 seconds to wait retry working with webfiltering in 30 seconds...", null,__FILE__,__LINE__);
		}
		
		if($ask_to_ufdb_time_sec==80){
			ufdbg_admin_mysql(1, "Web filtering Current 80 seconds to wait retry working with webfiltering in 10 seconds...", null,__FILE__,__LINE__);
		}		
		
		if($ask_to_ufdb_time_sec<89){
			return false;
		}
		ufdbg_admin_mysql(1, "Web filtering retry working with webfiltering", null,__FILE__,__LINE__);
		$GLOBALS["UFDB_SOCKET_ERROR"]=0;
		$GLOBALS["UFDB_SOCKET_ERROR_TIME"]=0;
	}
	
	
	$prefix="http";
	$sitename=$PARAMS["host"];
	$uri=$PARAMS["URI"];
	$IP=$PARAMS["IP"];
	$userid=$PARAMS["userid"];
	$PROTO=$PARAMS["PROTO"];
	if($PROTO=="CONNECT"){$prefix="https";}
	if($userid=="-"){$userid=null;}
	
	if(!preg_match("#^http#", $uri)){
		$uri="{$prefix}://$uri";
	}
	
	if(preg_match("#([0-9\.]+)\/(.*)#", $IP,$re)){
		$hostname=$re[2];
		$IP=$re[1];
	}
	
	$uri=urlencode($uri);
	
	
	if($GLOBALS["DEBUG_WEBFILTERING"]){events("ask_to_ufdb: [{$GLOBALS["UFDB_SERVER"]}] Sitename: $sitename uri=$uri,IP=$IP,userid=$userid,PROTO=$PROTO");}
	if($GLOBALS["DEBUG_WEBFILTERING"]){events("ask_to_ufdb: [{$GLOBALS["UFDB_SERVER"]}] Send \"$datatosend\"");}
	if($GLOBALS["DEBUG_PROTOCOL"]){events("[{$GLOBALS["UFDB_SERVER"]}] Send \"$datatosend\"");}
	$socket = @socket_create(AF_INET, SOCK_STREAM, 0);
	
	if (!is_resource($socket)) {
		$GLOBALS["UFDB_SOCKET_ERROR"]++;
		$error=@socket_strerror(socket_last_error());
		events("FATAL!!!: Web filtering socket error $error on {$GLOBALS["UFDB_SERVER"]}:{$GLOBALS["UFDB_PORT"]} [".__LINE__."]");
		ufdbg_admin_mysql(1, "Web filtering socket error $error on {$GLOBALS["UFDB_SERVER"]}:{$GLOBALS["UFDB_PORT"]} ", null,__FILE__,__LINE__);
		return false;
		
	}
	
	$ret = @socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $GLOBALS["UfdbgclientSockTimeOut"], 'usec' => 0));
	$ret = socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => $GLOBALS["UfdbgclientSockTimeOut"], 'usec' => 0));
	$ret = @socket_get_option($socket, SOL_SOCKET, SO_RCVTIMEO);
	if($ret === false){
		$error=socket_strerror(socket_last_error());
		$GLOBALS["UFDB_SOCKET_ERROR"]++;
		events("FATAL!!!: Web filtering socket error {$GLOBALS["UFDB_SOCKET_ERROR"]}/{$GLOBALS["UfdbgclientMaxSockTimeOut"]} socket_get_option SO_RCVTIMEO $error on {$GLOBALS["UFDB_SERVER"]}:{$GLOBALS["UFDB_PORT"]} [".__LINE__."]");
		ufdbg_admin_mysql(1, "Web filtering socket error {$GLOBALS["UFDB_SOCKET_ERROR"]}/{$GLOBALS["UfdbgclientMaxSockTimeOut"]} socket_get_option SO_RCVTIMEO $error on {$GLOBALS["UFDB_SERVER"]}:{$GLOBALS["UFDB_PORT"]} ", null,__FILE__,__LINE__);
		return false;
	}
	
	if(!@socket_connect($socket, $GLOBALS["UFDB_SERVER"], $GLOBALS["UFDB_PORT"])){
		$GLOBALS["UFDB_SOCKET_ERROR"]++;	
		$socket_last_error=socket_last_error($socket);
		$socketerror=socket_strerror(socket_last_error($socket));
		events("ask_to_ufdb: [{$GLOBALS["UFDB_SERVER"]}:{$GLOBALS["UFDB_PORT"]}] ");
		events("FATAL!!!: Web filtering socket error [$socket_last_error] {$GLOBALS["UFDB_SOCKET_ERROR"]}/{$GLOBALS["UfdbgclientMaxSockTimeOut"]} $socketerror on {$GLOBALS["UFDB_SERVER"]}:{$GLOBALS["UFDB_PORT"]}  [".__LINE__."]");
		ufdbg_admin_mysql(1, "Web filtering socket error [$socket_last_error] {$GLOBALS["UFDB_SOCKET_ERROR"]}/{$GLOBALS["UfdbgclientMaxSockTimeOut"]} $socketerror on {$GLOBALS["UFDB_SERVER"]}:{$GLOBALS["UFDB_PORT"]} ", null,__FILE__,__LINE__);
		@socket_close($socket);
		return false;
	}
	
	
	if($GLOBALS["DEBUG_PROTOCOL"]){events("ask_to_ufdb: * * * SEND * * * \"$datatosend\"",__LINE__);}
	@socket_write($socket, $datatosend, strlen($datatosend));
	
	
	
	$buf = @socket_read($socket, 1024);
	if(!$buf){
		$socketerror=socket_strerror(socket_last_error($socket));
		$GLOBALS["UFDB_SOCKET_ERROR"]++;
		events("ask_to_ufdb: [{$GLOBALS["UFDB_SERVER"]} Socket error:$socketerror");
		events("FATAL!!!: Web filtering socket error {$GLOBALS["UFDB_SOCKET_ERROR"]}/{$GLOBALS["UfdbgclientMaxSockTimeOut"]} $socketerror on {$GLOBALS["UFDB_SERVER"]}:{$GLOBALS["UFDB_PORT"]} [".__LINE__."]");
		ufdbg_admin_mysql(1, "Web filtering socket error {$GLOBALS["UFDB_SOCKET_ERROR"]}/{$GLOBALS["UfdbgclientMaxSockTimeOut"]} $socketerror on {$GLOBALS["UFDB_SERVER"]}:{$GLOBALS["UFDB_PORT"]} ", null,__FILE__,__LINE__);
		@socket_close($socket);
		return false;
	}
	

	
	
	@socket_close($socket);
	$GLOBALS["UFDB_SOCKET_ERROR"]=0;
	$buf=str_replace("\r\n", "", $buf);
	$buf=str_replace("\r", "", $buf);
	$buf=str_replace("\n", "", $buf);
	$buf=trim($buf);
	
	if($GLOBALS["DEBUG_PROTOCOL"]){events("[{$GLOBALS["UFDB_SERVER"]}] RECEIVE \"$buf\"");}
	
	if($GLOBALS["DEBUG_WEBFILTERING"]){events("ask_to_ufdb:[{$GLOBALS["UFDB_SERVER"]}");}
	if($GLOBALS["DEBUG_WEBFILTERING"]){events("ask_to_ufdb:[{$GLOBALS["UFDB_SERVER"]} **********************");}
	if($GLOBALS["DEBUG_WEBFILTERING"]){events("ask_to_ufdb:[{$GLOBALS["UFDB_SERVER"]} [proto=$PROTO]");}
	if($GLOBALS["DEBUG_WEBFILTERING"]){events("ask_to_ufdb:[{$GLOBALS["UFDB_SERVER"]} Receive \"$buf\"");}
	if($GLOBALS["DEBUG_WEBFILTERING"]){events("ask_to_ufdb:[{$GLOBALS["UFDB_SERVER"]} **********************");}
	if($GLOBALS["DEBUG_WEBFILTERING"]){events("ask_to_ufdb:[{$GLOBALS["UFDB_SERVER"]}");}
	
	if(strpos($buf, "loading-database")>0){return $buf;}
	if(strpos($buf, "fatalerror")>0){return $buf;}
	
	if($buf=="OK"){
		
		return $buf;
	}
	
	if($PROTO=="CONNECT"){
		$url_matched=ask_to_ufdb_parse_response($buf);
		if($url_matched<>null){
			//if(!preg_match("#^https:#", $url_matched)){$url_matched="https://$url_matched";}
			$url_matched=str_replace("&url=%u", "&url=$uri", $url_matched);
			$url_matched=str_replace("&clientaddr=%a", "&clientaddr=$IP", $url_matched);
			$url_matched=str_replace("&clientname=%n", "&clientname=$hostname", $url_matched);
			$url_matched=str_replace("&clientuser=%i", "&clientuser=$userid", $url_matched);
			$url_matched=str_replace("&clientgroup=%s&targetgroup=%t", "&rule-id=0&clientgroup=generic&targetgroup=https-locked", $url_matched);
			return $url_matched;
		}
	}
	
	
	
	
	if(strpos(" $buf", "GET")>0){
		events_failed("{$buf}");
	}
	
	if(strpos(" $buf", "HTTP/")>0){
		events_failed("{$buf}");
	}
	
	return $buf;
}

function ask_to_ufdb_parse_response($url){
	if(preg_match('#^GET\s+(.+)#',$url,$re)){$url=$re[1];}
	if(preg_match('#OK url="(.+?)"#', $url,$re)){return $re[1];}
	return $url;
}






function ufdbconfig(){
	
	$quota=new ufdbgquota();
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/WebFilteringRansomware")){
		$GLOBALS["WebFilteringRansomware"]=1;
		@file_put_contents("/etc/artica-postfix/settings/Daemons/WebFilteringRansomware", 1);
	}
	
	
	$GLOBALS["EnableITChart"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableITChart"));
	$GLOBALS["HTTrackInSquid"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/HTTrackInSquid"));
	
	$GLOBALS["GoogleSafeBrowsingApiKey"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/GoogleSafeBrowsingApiKey"));
	$GLOBALS["EnableGoogleSafeBrowsing"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableGoogleSafeBrowsing"));
	$GLOBALS["GoogleSafeBrowsingCacheTime"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/GoogleSafeBrowsingCacheTime"));
	
	$GLOBALS["HyperCacheStoragePath"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/HyperCacheStoragePath"));
	$GLOBALS["HyperCacheMemEntries"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/HyperCacheMemEntries"));
	$GLOBALS["HyperCacheBuffer"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/HyperCacheBuffer"));
	
	$GLOBALS["SquidUrgency"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidUrgency"));
	$GLOBALS["UpdateUtilityForceProxy"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/UpdateUtilityForceProxy"));
	
	$GLOBALS["HyperCacheHTTPListenPort"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/HyperCacheHTTPListenPort"));
	if($GLOBALS["HyperCacheHTTPListenPort"]==0){$GLOBALS["HyperCacheHTTPListenPort"]=8700;}
	
	$GLOBALS["HyperCacheListenAddr"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/HyperCacheListenAddr"));
	$GLOBALS["EnableHTTPSURBL"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableHTTPSURBL"));
	$GLOBALS["PHISHTANK"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableSquidPhishTank"));
	$GLOBALS["WindowsUpdateCaching"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateCaching"));
	$GLOBALS["WindowsUpdateCachingDir"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateCachingDir");
	if($GLOBALS["WindowsUpdateCachingDir"]==null){$GLOBALS["WindowsUpdateCachingDir"]="/home/squid/WindowsUpdate";}
	$GLOBALS["SquidGuardRedirectSSLBehavior"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidGuardRedirectSSLBehavior"));
	$GLOBALS["SquidGuardRedirectBehavior"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidGuardRedirectBehavior"));
	$GLOBALS["SquidGuardRedirectHTTPCode"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidGuardRedirectHTTPCode"));
	if($GLOBALS["SquidGuardRedirectBehavior"]==null){$GLOBALS["SquidGuardRedirectBehavior="]="url";}
	if($GLOBALS["SquidGuardRedirectSSLBehavior"]==null){$GLOBALS["SquidGuardRedirectSSLBehavior="]="url";}
	if(!is_numeric($GLOBALS["SquidGuardRedirectHTTPCode"])){$GLOBALS["SquidGuardRedirectHTTPCode"]=302;}
	
	
	$GLOBALS["SquidGuardWebUseExternalUri"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidGuardWebUseExternalUri"));
	$GLOBALS["SquidGuardWebExternalUri"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidGuardWebExternalUri"));
	$GLOBALS["SquidGuardWebExternalUriSSL"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidGuardWebExternalUriSSL"));
	$GLOBALS["SquidGuardWebSSLCompatibility"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidGuardWebSSLCompatibility"));
	
	
	$GLOBALS["SquidGuardServerName"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidGuardServerName");
	$GLOBALS["SquidGuardApachePort"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidGuardApachePort"));
	$GLOBALS["SquidGuardApacheSSLPort"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidGuardApacheSSLPort"));
	if($GLOBALS["SquidGuardApachePort"]==0){$GLOBALS["SquidGuardApachePort"]=9025;}
	if($GLOBALS["SquidGuardApacheSSLPort"]==0){$GLOBALS["SquidGuardApacheSSLPort"]=9020;}
	if($GLOBALS["SquidGuardServerName"]==null){$GLOBALS["SquidGuardServerName"]=php_uname("n");}
	
	
	
	if($GLOBALS["HyperCacheStoragePath"]==null){$GLOBALS["HyperCacheStoragePath"]="/home/artica/proxy-cache";}
	if($GLOBALS["GoogleSafeBrowsingCacheTime"]==0){$GLOBALS["GoogleSafeBrowsingCacheTime"]=10080;}
	$GLOBALS["GoogleSafeBrowsingDNS"]=trim(strtolower(@file_get_contents("/etc/artica-postfix/settings/Daemons/GoogleSafeBrowsingDNS")));
	$GLOBALS["GoogleSafeBrowsingInterface"]=trim(strtolower(@file_get_contents("/etc/artica-postfix/settings/Daemons/GoogleSafeBrowsingInterface")));
	
	
	if($GLOBALS["HyperCacheMemEntries"]==0){$GLOBALS["HyperCacheMemEntries"]=500000;}
	if($GLOBALS["HyperCacheBuffer"]==0){$GLOBALS["HyperCacheBuffer"]=50;}
	
	
	if($GLOBALS["GoogleSafeBrowsingDNS"]==null){$GLOBALS["GoogleSafeBrowsingDNS"]="8.8.8.8,4.4.4.4";}
	if($GLOBALS["GoogleSafeBrowsingDNS"]=="*"){$GLOBALS["GoogleSafeBrowsingDNS"]=null;}
	if($GLOBALS["GoogleSafeBrowsingDNS"]=="default"){$GLOBALS["GoogleSafeBrowsingDNS"]=null;}
	
	
	if($GLOBALS["GOOGLE_SAFE"]){
		events("ufdbconfig:: /etc/artica-postfix/settings/Daemons/EnableGoogleSafeBrowsing = {$GLOBALS["EnableGoogleSafeBrowsing"]}");
	}
	
	$GLOBALS["PROXY"]=array();
	$ini=new Bs_IniHandler();
	
	$datas=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaProxySettings");
	if(trim($datas)<>null){$ini->loadString($datas);}
	if(isset($ini->_params["PROXY"])){$GLOBALS["PROXY"]=$ini->_params["PROXY"];}
	
	$GLOBALS["SquidEnforceRules"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidEnforceRules"));
	
	$EnableUfdbGuard=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableUfdbGuard"));
	$UseRemoteUfdbguardService=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/UseRemoteUfdbguardService"));
	$EnableRemoteStatisticsAppliance=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableRemoteStatisticsAppliance"));
	$datas=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/ufdbguardConfig")));
	$RemoteStatisticsApplianceSettings=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/RemoteStatisticsApplianceSettings")));
	$SquidGuardIPWeb=unserialize(@file_get_contents("/var/log/squid/SquidGuardIPWeb"));
	if(is_array($SquidGuardIPWeb)){$GLOBALS["SquidGuardIPWeb"]=$SquidGuardIPWeb["SquidGuardIPWeb"];}
	$SquidGuardWebUseExternalUri=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidGuardWebUseExternalUri"));
	if($SquidGuardWebUseExternalUri==1){
		$GLOBALS["SquidGuardIPWeb"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidGuardWebExternalUri");
		$GLOBALS["SquidGuardIPWeb_SSL"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidGuardWebExternalUriSSL");
	}
	
	if($GLOBALS["SquidEnforceRules"]==1){
		HyperCacheRulesLoad();
	}
	
	if(!isset($datas["url_rewrite_children_concurrency"])){$datas["url_rewrite_children_concurrency"]=2;}
	$GLOBALS["url_rewrite_concurrency"]=$datas["url_rewrite_children_concurrency"];
	
	
	
	$GLOBALS["UfdbgclientSockTimeOut"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/UfdbgclientSockTimeOut"));
	if($GLOBALS["UfdbgclientSockTimeOut"]==0){$GLOBALS["UfdbgclientSockTimeOut"]=2;}
	$GLOBALS["UfdbgclientMaxSockTimeOut"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/UfdbgclientMaxSockTimeOut"));
	if($GLOBALS["UfdbgclientMaxSockTimeOut"]==0){$GLOBALS["UfdbgclientMaxSockTimeOut"]=5;}
	if(!isset($datas["remote_port"])){$datas["remote_port"]=3977;}
	if(!isset($datas["remote_server"])){$datas["remote_server"]="127.0.0.1";}
	
	if(!is_numeric($datas["remote_port"])){$datas["remote_port"]=3977;}
	if(trim($datas["remote_server"]==null)){$datas["remote_server"]="127.0.0.1";}
	if(!isset($datas["remote_server"])){$datas["remote_server"]=null;}
	if(!isset($datas["remote_port"])){$datas["remote_port"]=3977;}
	if($datas["remote_server"]=="all"){$datas["remote_server"]="127.0.0.1";}
	if($datas["remote_server"]==null){$datas["remote_server"]="127.0.0.1";}
	if(!is_numeric($datas["remote_port"])){$datas["remote_port"]=3977;}

	
	if($EnableRemoteStatisticsAppliance==1){
		$GLOBALS["EnableUfdbGuard"]=1;
		$datas["remote_server"]=$RemoteStatisticsApplianceSettings["SERVER"];
		$UseRemoteUfdbguardService=1;
		$GLOBALS["EnableUfdbGuard"]=1;
		$datas["remote_port"]=$datas["listen_port"];
		events("Using remote appliance {$RemoteStatisticsApplianceSettings["SERVER"]}:{$datas["listen_port"]} as Web filtering engine");
		$GLOBALS["UFDB_SERVER"]=$RemoteStatisticsApplianceSettings["SERVER"];
		$GLOBALS["UFDB_PORT"]=$datas["listen_port"];
		return;
	}

	
	if($UseRemoteUfdbguardService==1){
		$GLOBALS["EnableUfdbGuard"]=1;
		$GLOBALS["UFDB_SERVER"]=$datas["remote_server"];
		$GLOBALS["UFDB_PORT"]=$datas["remote_port"];
		if(!is_numeric($GLOBALS["UFDB_PORT"])){$GLOBALS["UFDB_PORT"]=3977;}
		if(trim($GLOBALS["UFDB_SERVER"]==null)){$GLOBALS["UFDB_SERVER"]="127.0.0.1";}
		if(!isset($GLOBALS["UFDB_SERVER"])){$GLOBALS["UFDB_SERVER"]=null;}
		if(!isset($GLOBALS["UFDB_PORT"])){$GLOBALS["UFDB_PORT"]=3977;}
		if($GLOBALS["UFDB_SERVER"]=="all"){$GLOBALS["UFDB_SERVER"]="127.0.0.1";}
		if($GLOBALS["UFDB_SERVER"]==null){$GLOBALS["UFDB_SERVER"]="127.0.0.1";}
		if(!is_numeric($GLOBALS["UFDB_PORT"])){$GLOBALS["UFDB_PORT"]=3977;}
		events("Using remote service {$datas["remote_server"]}:{$datas["remote_port"]} as Web filtering engine");
		
		return;
	}
	if($EnableUfdbGuard==0){
		events("Web filtering engine is disabled");
		$GLOBALS["EnableUfdbGuard"]=0;
		return;
	}
	$effective_port=ufdbguard_value("port");
	$interface=ufdbguard_value("interface");
	
	$GLOBALS["UFDB_SERVER"]=$interface;
	$GLOBALS["UFDB_PORT"]=$effective_port;
	$GLOBALS["EnableUfdbGuard"]=1;
	if(!is_numeric($GLOBALS["UFDB_PORT"])){$GLOBALS["UFDB_PORT"]=3977;}
	if(trim($GLOBALS["UFDB_SERVER"]==null)){$GLOBALS["UFDB_SERVER"]="127.0.0.1";}
	if(!isset($GLOBALS["UFDB_SERVER"])){$GLOBALS["UFDB_SERVER"]=null;}
	if(!isset($GLOBALS["UFDB_PORT"])){$GLOBALS["UFDB_PORT"]=3977;}
	if($GLOBALS["UFDB_SERVER"]=="all"){$GLOBALS["UFDB_SERVER"]="127.0.0.1";}
	if($GLOBALS["UFDB_SERVER"]==null){$GLOBALS["UFDB_SERVER"]="127.0.0.1";}
	if(!is_numeric($GLOBALS["UFDB_PORT"])){$GLOBALS["UFDB_PORT"]=3977;}
	events("Using local service {$interface}:{$effective_port} as Web filtering engine");
	events("Redirect engine:{$GLOBALS["SquidGuardRedirectBehavior"]} CODE {$GLOBALS["SquidGuardRedirectHTTPCode"]}");
	
	
	
	
}

function ufdbguard_value($key){
	if(!is_file("/etc/squid3/ufdbGuard.conf")){return null;}
	if(isset($GLOBALS[__FUNCTION__][$key])){return $GLOBALS[__FUNCTION__][$key];}
	if(!isset($GLOBALS["UFDGUARDDATAFILE"])){$GLOBALS["UFDGUARDDATAFILE"]=file("/etc/squid3/ufdbGuard.conf");}
	if(!is_array($GLOBALS["UFDGUARDDATAFILE"])){$GLOBALS["UFDGUARDDATAFILE"]=file("/etc/squid3/ufdbGuard.conf");}
	while (list ($num, $ligne) = each ($GLOBALS["UFDGUARDDATAFILE"]) ){
		if(preg_match("#^$key\s+(.*)#", $ligne,$re)){
			$GLOBALS[__FUNCTION__][$key]=$re[1];
			return $re[1];}
	}
	
	

}

function events($text,$line=0){
	if(trim($text)==null){return;}
	$pid=$GLOBALS["MYPID"];
	if(!isset($GLOBALS["LOG_DOM"])){$GLOBALS["LOG_DOM"]="None";}
	$date=@date("H:i:s");
	$logFile="/var/log/squid/ufdbgclient.debug";
	$time_end=tool_microtime_float();
	$tt = round($time_end - $GLOBALS["time_loop_start"],3);
	$size=@filesize($logFile);
	if($size>9000000){@unlink($logFile);}
	$line="$date:[".basename(__FILE__)."/{$GLOBALS["UFDBVERS"]} $pid [{$GLOBALS["LOG_DOM"]}]:$text - {$tt}ms $line";
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$line\n");
	@fclose($f);


}
function events_output($text,$line=0){
	if(trim($text)==null){return;}
	$pid=$GLOBALS["MYPID"];
	$date=@date("H:i:s");
	$logFile="/var/log/squid/ufdbgclient.OUT";
	

	$size=@filesize($logFile);
	if($size>9000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');

	@fwrite($f, "$date:$text L.$line\n");
	@fclose($f);
}


function events_failed($text,$line=0){
	if(trim($text)==null){return;}
	$pid=$GLOBALS["MYPID"];
	$date=@date("H:i:s");
	$logFile="/var/log/squid/ufdbgclient.error";
	$time_end=tool_microtime_float();
	$tt = round($time_end - $GLOBALS["time_loop_start"],3);

	$size=@filesize($logFile);
	if($size>9000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');

	@fwrite($f, "$date:[".basename(__FILE__)."/{$GLOBALS["UFDBVERS"]} $pid [{$GLOBALS["LOG_DOM"]}]:$text - {$tt}ms $line\n");
	@fclose($f);
}


function ufdbgevents($category,$rulename){
	$hostname=null;
	if(preg_match("#([0-9\.]+)\/(.*)#", $GLOBALS["LOG_AR"]["IP"],$re)){
		$hostname=$re[2];
		$GLOBALS["LOG_AR"]["IP"]=$re[1];
	}
	
	
	$time=time();
	ufdbguardd_log($category,$rulename);
	
	
		
	$user=$GLOBALS["LOG_AR"]["userid"];
	$www=$GLOBALS["LOG_AR"]["host"];
	$Clienthostname=gethostbyaddr($GLOBALS["LOG_AR"]["IP"]);
	$public_ip=gethostbyaddr($www);
	$local_ip=$GLOBALS["LOG_AR"]["IP"];
	if($Clienthostname==null){$Clienthostname=$local_ip;}
	$line="$time:::$user:::$category:::$rulename:::$public_ip:::blocked domain:::blocked domain:::$Clienthostname:::$www:::$local_ip";
	
	$file="/home/ufdb/relatime-events/ACCESS_LOG";
	
	
	$h = @fopen($file, 'a');
	@fwrite($h,$line."\n");
	@fclose($h);
	
	
	
	
	
	
}

function ufdbguardd_log($category,$rulename){
	$uid=$GLOBALS["LOG_AR"]["userid"];
	if($uid==null){$uid="-";}
	$line=date("Y-m-d H:i:s")." [".getmypid()."] BLOCK $uid    {$GLOBALS["LOG_AR"]["IP"]} $rulename $category {$GLOBALS["LOG_AR"]["URI"]} GET";
	$fZ2 = @fopen("/var/log/squid/ufdbguardd.log", 'a');
	@fwrite($fZ2, "$line\n");
	@fclose($fZ2);
}




function ufdbg_admin_mysql($severity,$subject,$text,$file=null,$line=0){
	if(!is_numeric($line)){$line=0;}
	
	$key=md5($subject);
	if(isset($GLOBALS["NOTIFS"][$key])){
		if(tool_time_min($GLOBALS["NOTIFS"][$key])<5){return;}
		
	}
	$GLOBALS["NOTIFS"][$key]=time();
	
	// 0 -> RED, 1 -> WARN, 2 -> INFO

	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			if($file==null){
				$file=basename($trace[1]["file"]);
			}
			$function=$trace[1]["function"];
			if($line==0){
				$line=$trace[1]["line"];
			}
		}
			
	}


	if(function_exists("syslog")){
		$file=basename($file);
		$LOG_SEV=LOG_INFO;
		openlog($file, LOG_PID , LOG_SYSLOG);
		syslog($LOG_SEV, $subject ." [$line]");
		closelog();
	}

	$GLOBALS["SCHEDULE_ID"]=0;
	$array["zdate"]=date("Y-m-d H:i:s");
	$array["subject"]=$subject;
	$array["text"]=$text;
	$array["severity"]=$severity;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["pid"]=getmypid();
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("/var/log/squid/squid_admin_mysql")){@mkdir("/var/log/squid/squid_admin_mysql",0755,true);}
	@file_put_contents("/var/log/squid/squid_admin_mysql/$md5.log", $serialize);
	

}

function ItCharted($ARRAY){
	$MAC=null;
	$IP=$ARRAY["IP"];
	
	
if(preg_match("#([0-9\.]+)\/(.*)#", $IP,$re)){
		$hostname=$re[2];
		$IP=$re[1];
	}
	
	
	$userid=$ARRAY["userid"];
	$PROTO=$ARRAY["PROTO"];
	$URI=$ARRAY["URI"];
	$SquidGuardIPWeb=$GLOBALS["SquidGuardIPWeb"];
	$CONNECT=false;
	$KEY=null;
	if(isset($ARRAY["MAC"])){$MAC=$ARRAY["MAC"];}
	if(!class_exists("mysql_squid_builder")){include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");}
	
	if(!isset($GLOBALS["SQUID_SQL"])){$GLOBALS["SQUID_SQL"]=new mysql_squid_builder();}
	
	if($GLOBALS["DEBUG_ITCHART"]){
		events("user:$userid $IP/$MAC");
	}
	
	
	$arrayNext["src"]=$URI;
	$arrayNext["LOGIN"]=$userid;
	$arrayNext["IPADDR"]=$IP;
	$arrayNext["MAC"]=$MAC;
	
	if($userid<>null){
		$FIELD="uid";
		$KEY=$userid;}
	if($KEY==null){
		if($MAC<>null){
			$FIELD="MAC";
			$KEY=$MAC;}
	}
	if($KEY==null){
		if($IP<>null){
			$FIELD="ipaddr";
			$KEY=$IP;
		}
	}	
	
	if($PROTO=="CONNECT"){$CONNECT=true;}
	$SquidGuardIPWeb=str_replace("ufdbguardd.php", "itchart.php", $SquidGuardIPWeb);
	
	if(!isset($GLOBALS["ITCHARTS_ENABLED"])){
		$GLOBALS["ITCHARTS_ENABLED"]=unserialize(@file_get_contents("/etc/squid3/itCharts.enabled.db"));
		if(count($GLOBALS["ITCHARTS_ENABLED"])==0){
			if($GLOBALS["DEBUG_ITCHART"]){events("/etc/squid3/itCharts.enabled.db = 0 entries");}
			return false;
		}
	}
	
	
	if($GLOBALS["DEBUG_ITCHART"]){events( count($GLOBALS["ITCHARTS_ENABLED"])." It Charts to query ($KEY)");}
	$ITCHARTS=$GLOBALS["ITCHARTS_ENABLED"];
	
	while (list ($ID, $title) = each ($ITCHARTS) ){
		if($GLOBALS["DEBUG_ITCHART"]){events("$ID - $title against $KEY");}
		
		if(isset($GLOBALS["IT_CHART_SUCCESS"][$ID][$KEY])){
			if($GLOBALS["DEBUG_ITCHART"]){events("$ID - $KEY MEMORY = TRUE");}
			continue;
		}
		
		
		$sql="SELECT ID FROM itchartlog WHERE `$FIELD`='$KEY' AND chartid='$ID'";
		if($GLOBALS["DEBUG_ITCHART"]){events("$sql");}
		$ligne=mysql_fetch_array($GLOBALS["SQUID_SQL"]->QUERY_SQL($sql));
		if(!$GLOBALS["SQUID_SQL"]){
			events("{$GLOBALS["SQUID_SQL"]->mysql_error}");
			return false;
		}
		$RESULT_ID=intval($ligne["ID"]);
		if($GLOBALS["DEBUG_ITCHART"]){events("$ID - $KEY = {$RESULT_ID}");}
		
		
		if($RESULT_ID>0){
			$GLOBALS["IT_CHART_SUCCESS"][$ID][$KEY]=true;
			if($GLOBALS["DEBUG_ITCHART"]){events("$ID - $KEY -> continue");}
			continue;
		}
		
		if($GLOBALS["DEBUG_ITCHART"]){events("$ID - $KEY NONE -> REDIRECT");}
		
		
		
		$arrayNext["ChartID"]=$ID;
		ufdbgevents($title,"itChart");
		$arrayNextEnc=base64_encode(serialize($arrayNext));
		$output="status=302 url=\"$SquidGuardIPWeb?request=$arrayNextEnc&xtime=".time()."\"";
		Output_results($output,__FUNCTION__,__LINE__,$CONNECT);
		return true;
		
	}
	
	
	
	
	if($GLOBALS["DEBUG_ITCHART"]){events("DONE $KEY");}
	
}



