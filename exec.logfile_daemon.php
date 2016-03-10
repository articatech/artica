#!/usr/bin/php -q
<?php
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(isset($argv[1])){if($argv[1]=="--bycron"){die();}}
register_shutdown_function('shutdown');
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.realtime-buildsql.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.influx.inc");
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
$GLOBALS["COUNT"]=0;
$GLOBALS["VERSION"]="18Janv2015";
$GLOBALS["UserAuthDB_path"]="/var/log/squid/UserAuthDB.db";
$GLOBALS["ACT_AS_REVERSE"]=false;
$GLOBALS["NO_DISK"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["DEBUG_USERAGENT"]=false;
$GLOBALS["KAV4PROXY_NOSESSION"]=true;
$GLOBALS["LOG_HOSTNAME"]=false;
$GLOBALS["REMOTE_PROXY_NAME"]=null;
$GLOBALS["DEBUG_USERS"]=false;
$GLOBALS["COUNT_WAKEUP"]=0;
$GLOBALS["COUNT_RQS"]["TIME"]=time();
$GLOBALS["DisableLogFileDaemonCategories"]=0;
$GLOBALS["ACCEPTED_REQUESTS"]=0;
$GLOBALS["DEBUG_CACHES"]=false;
$GLOBALS["REFUSED_REQUESTS"]=0;
$GLOBALS["DEBUG_MEM"]=false;
$GLOBALS["DEBUG_INFLUX"]=false;
$GLOBALS["COUNT_HASH_TABLE"]=0;
$GLOBALS["WAKEUP_LOGS"]=0;
$GLOBALS["KEYUSERS"]=array();
$GLOBALS["CACHE_SQL"]=array();
$timezones=@file_get_contents("/etc/artica-postfix/settings/Daemons/timezones");
$GLOBALS["LogFileDeamonLogDir"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/LogFileDeamonLogDir");
if($GLOBALS["LogFileDeamonLogDir"]==null){$GLOBALS["LogFileDeamonLogDir"]="/home/artica/squid/realtime-events";}

if(is_file("/usr/local/ArticaStats/bin/postgres")){
	$GLOBALS["LogFileDeamonLogDir"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/LogFileDeamonLogPostGresDir");
	if($GLOBALS["LogFileDeamonLogDir"]==null){$GLOBALS["LogFileDeamonLogDir"]="/home/artica/squid-postgres/realtime-events";}
}

$GLOBALS["ResolvIPStatistics"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/ResolvIPStatistics"));


$GLOBALS["EnableInfluxDB"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableInfluxDB"));
if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="{$GLOBALS["ARTICALOGDIR"]}"; } }
if(!isset($GLOBALS["NoCompressStatisticsByHour"])){$GLOBALS["NoCompressStatisticsByHour"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/NoCompressStatisticsByHour"));}




if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$pid=getmypid();
$pidfile="/etc/artica-postfix/pids/exec.logfile_daemon.php.pid";
@mkdir("/etc/artica-postfix/pids",0755,true);
@mkdir($GLOBALS["LogFileDeamonLogDir"],0755,true);
$pid=@file_get_contents($pidfile);


if($timezones<>null){@date_default_timezone_set($timezones);}
parseconfig();
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);


$logthis=array();
if($GLOBALS["VERBOSE"]){$logthis[]="Verbosed";}
if($GLOBALS["ACT_AS_REVERSE"]){$logthis[]=" Act as reverse...";}
$GLOBALS["MYPID"]=getmypid();
events("Starting PID: {$GLOBALS["MYPID"]} - TimeZone: $timezones,  version: {$GLOBALS["VERSION"]}, ".@implode(", ", $logthis));
if($GLOBALS["DisableLogFileDaemonCategories"]==1){events("Starting: WILL NOT USE Categories detection feature..."); }
if($GLOBALS["DisableLogFileDaemonCategories"]==0){events("Starting: USING Categories detection feature..."); }
if($GLOBALS["EnableArticaMetaClient"]==1){events("Starting: USING Meta Web management console..."); }
if($GLOBALS["EnableArticaMetaClient"]==1){events("Starting: Dump events each {$GLOBALS["LogFileDaemonMaxEvents"]} rows..."); }
$GLOBALS["MYSQL_CATZ"]=new mysql_catz();
$GLOBALS["SQUID_FAMILY_CLASS"]=new squid_familysite();
$GLOBALS["COUNT_RQS_TIME"]=0;
$GLOBALS["COUNT_RQS"]=0;
$GLOBALS["PURGED"]=0;


$unix=new unix();
$GLOBALS["MYHOSTNAME"]=$unix->hostname_g();
events("Starting PID: Resolv IP domains.................: {$GLOBALS["ResolvIPStatistics"]}");
events("Starting PID: waiting connections... Meta Client: {$GLOBALS["EnableArticaMetaClient"]}");
events("Starting PID: Compress statistics In realtime...: {$GLOBALS["NoCompressStatisticsByHour"]}");
$DCOUNT=0;
$GLOBALS["REQS"]=array();
@file_put_contents("/var/run/squid/exec.logfilefile_daemon.{$GLOBALS["MYPID"]}.pid", time());
@file_put_contents($pidfile, getmypid());

squid_admin_mysql(2, "Starting Squid Tail Daemon PID {$GLOBALS["MYPID"]}", null,__FILE__,__LINE__);

//$pipe = fopen("php://stdin", "r");
$buffer=null;
$pipe = fopen("php://stdin", "r");
while(!feof($pipe)){
	$buffer .= fgets($pipe, 4096);
	try {
		if($GLOBALS["VERBOSE"]){events(" - > `$buffer`");}
		Parseline($buffer);
	} catch (Exception $e) {
		events("Fatal error on buffer $buffer");
	}

	$buffer=null;
}

function Parseline($buffer){
	
	
	if(!isset($GLOBALS["CACHE_TAIL_TIME"])){$GLOBALS["CACHE_TAIL_TIME"]=time();}
	
	$cacheTailTime=tool_time_sec($GLOBALS["CACHE_TAIL_TIME"]);
	
	if($cacheTailTime>6){
		@unlink("/etc/artica-postfix/cache-tail.time");
		@file_put_contents("/etc/artica-postfix/cache-tail.time", time());
		$GLOBALS["CACHE_TAIL_TIME"]=time();
	}
	
	
	

	if($GLOBALS["COUNT_RQS"]==0){$GLOBALS["COUNT_RQS"]=1;}
	$ctrqs=intval($GLOBALS["COUNT_RQS"]);
	$ctrqs++;
	$GLOBALS["COUNT_RQS"]=$ctrqs;
	if($GLOBALS["COUNT_RQS_TIME"]==0){$GLOBALS["COUNT_RQS_TIME"]=time();}
	if($GLOBALS["COUNT_RQS_TIME"]>0){if(tool_time_sec($GLOBALS["COUNT_RQS_TIME"])>15){CleanReQsMin();} }
	if($GLOBALS["VERBOSE"]){events( __LINE__." {$GLOBALS["COUNT_RQS"]} connexions");}

	if(strpos($buffer, "TCP_DENIED/403")>0){
		if($GLOBALS["VERBOSE"]){ events("SKIP $buffer"); }
		$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;
		return true;
	}
	
	if(isset($GLOBALS["LOGACCESS_TIME"])){CachedUserMemDump();}
	if(strpos($buffer, ":::HEAD:::")>0){if($GLOBALS["VERBOSE"]){ events("SKIP $buffer"); }$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;return;}
	if(strpos($buffer, "NONE:HIER_NONE")>0){if($GLOBALS["VERBOSE"]){ events("SKIP $buffer"); }$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;return;}
	if(strpos($buffer, "error:invalid-request")>0){if($GLOBALS["VERBOSE"]){ events("SKIP $buffer"); }$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;return;}
	if(strpos("NONE error:", $buffer)>0){if($GLOBALS["VERBOSE"]){ events("SKIP $buffer"); }$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;return; }
	if(strpos($buffer, "GET cache_object")>0){if($GLOBALS["VERBOSE"]){ events("SKIP $buffer"); }$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;return true;}
	if(strpos($buffer, "cache_object://")>0){if($GLOBALS["VERBOSE"]){ events("SKIP $buffer"); }$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;return true;}
	
	$GLOBALS["WAKEUP_LOGS"]=$GLOBALS["WAKEUP_LOGS"]+1;
	$currentMin=date("Y-m-d H:i:00");
		if(!isset($GLOBALS["REQS"][date("YmdHi")])){
			$GLOBALS["REQS"][$currentMin]=1;
		}else{
			$GLOBALS["REQS"][$currentMin]=$GLOBALS["REQS"][$currentMin]+1;
		}
		if(count($GLOBALS["REQS"][$currentMin])>15){CleanReQsMin();}
		
		if( $GLOBALS["WAKEUP_LOGS"]>50 ){
			if(!isset($GLOBALS["BEREKLEY_MEMORY_STATS"])){$GLOBALS["BEREKLEY_MEMORY_STATS"]=0;}
			events("{$GLOBALS["REFUSED_REQUESTS"]} refused requests ".
			"- {$GLOBALS["ACCEPTED_REQUESTS"]} accepted requests ".
			"- {$GLOBALS["COUNT_RQS"]}".
			"- ".round($GLOBALS["BYTES_WRITE"]/1024,2)." Ko written in logs"
			);
			$GLOBALS["WAKEUP_LOGS"]=0;
		}		
		
		
		if(strpos($buffer, "TAG_NONE:HIER_NONE")>0){$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;return true;}
		if(strpos($buffer, "TCP_MISS/000")>0){$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;return true;}
		if(strpos($buffer, "TCP_DENIED:")>0){$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;return true;}
		if(strpos($buffer, "RELEASE -1")>0){$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;return true;}
		if(strpos($buffer, "RELEASE 00")>0){$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;return true;}
		if(strpos($buffer, "SWAPOUT 00")>0){$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;return true;}
		
		if($GLOBALS["VERBOSE"]){$time_start = microtime(true);}
		
		ParseSizeBuffer($buffer);
		if($GLOBALS["VERBOSE"]){$time_end = microtime(true);$time_calc = $time_end - $time_start;}
		if($GLOBALS["VERBOSE"]){events("ParseSizeBuffer = {$time_calc}ms");}
		$buffer=null;
		
		

}

events("Stopping PID:".getmypid()." After $DCOUNT event(s) berekley_memory_dump()");
if(!is_file("/var/run/squid/exec.logfilefile_daemon.{$GLOBALS["MYPID"]}.pid")){@unlink("/var/run/squid/exec.logfilefile_daemon.{$GLOBALS["MYPID"]}.pid");}
events("Stopping PID:".getmypid()." Stopped()");
die();


function CheckDirs(){
	$f[]="/var/log/squid/mysql-queue";
	$f[]="/var/log/squid/mysql-rttime";
	$f[]="/var/log/squid/mysql-rthash";
	$f[]="/var/log/squid/mysql-rtterrors";
	$f[]="/var/log/squid/mysql-squid-queue";
	$f[]="/var/log/squid/mysql-rtterrors";
	$f[]="/var/log/squid/mysql-UserAgents";
	$f[]="/var/log/squid/mysql-computers";
	$f[]="/var/log/squid/squid_admin_mysql";
	$f[]="/var/log/squid/cached-stats";
	
	while (list ($num, $directory) = each ($f)){
		if(!is_dir($directory)){@mkdir($directory,0755,true);}
		@chown($directory, "squid");
		@chgrp($directory, "squid");
		
	}
	
	
}

function CleanReQsMin(){
	if($GLOBALS["EnableInfluxDB"]==0){
		$GLOBALS["COUNT_RQS"]=0;
		$GLOBALS["COUNT_RQS_TIME"]=time();
		return;
	}
	
	if($GLOBALS["NoCompressStatisticsByHour"]==0){
		$GLOBALS["COUNT_RQS"]=0;
		$GLOBALS["COUNT_RQS_TIME"]=time();
		return;
	}
	$q=new influx();
	$array["fields"]["RQS"]=intval($GLOBALS["COUNT_RQS"]);
	$array["fields"]["ZDATE"]=time();
	$array["tags"]["proxyname"]=$GLOBALS["REMOTE_PROXY_NAME"];
	$q->insert("proxy_requests", $array);
	$GLOBALS["COUNT_RQS"]=0;
	$GLOBALS["COUNT_RQS_TIME"]=time();
}





function parseconfig(){
	
	$GLOBALS["ProxyUseArticaDB"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/ProxyUseArticaDB"));
	$GLOBALS["EnableSquidRemoteMySQL"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableSquidRemoteMySQL"));
	$GLOBALS["squidRemostatisticsServer"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/squidRemostatisticsServer"));
	$GLOBALS["squidRemostatisticsPort"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/squidRemostatisticsPort"));
	$GLOBALS["squidRemostatisticsUser"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/squidRemostatisticsUser"));
	$GLOBALS["squidRemostatisticsPassword"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/squidRemostatisticsPassword"));
	$GLOBALS["EnableArticaMetaClient"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaMetaClient"));
	$GLOBALS["LogFileDaemonMaxEvents"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/LogFileDaemonMaxEvents"));
	$GLOBALS["UserAgentsStatistics"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/UserAgentsStatistics"));
	
	if(!isset($GLOBALS["LogFileDaemonMaxMin"])){$GLOBALS["LogFileDaemonMaxMin"]=1;}
	
	if($GLOBALS["LogFileDaemonMaxEvents"]==0){$GLOBALS["LogFileDaemonMaxEvents"]=500;}
	if($GLOBALS["LogFileDaemonMaxMin"]==0){$GLOBALS["LogFileDaemonMaxMin"]=1;}
	
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/DisableLogFileDaemonMySQL")){
		$GLOBALS["DisableLogFileDaemonMySQL"]=1;
	}else{
		$GLOBALS["DisableLogFileDaemonMySQL"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/DisableLogFileDaemonMySQL"));
	}
	
	
	
	if(is_file("/etc/squid3/EnableArticaMetaClient_ON")){$GLOBALS["EnableArticaMetaClient"]=1;}
	ConfigUfdcat();
	
	
}














function ConfigUfdcat(){
	events("Starting: Using a remote category service: version 2014-12-24...");
	$GLOBALS["RemoteUfdbCat"]=0;
	$GLOBALS["DisableLogFileDaemonCategories"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/DisableLogFileDaemonCategories"));
	$GLOBALS["EnableLocalUfdbCatService"]=trim(intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableLocalUfdbCatService")));
	
	
	if($GLOBALS["EnableLocalUfdbCatService"]==1){
		events("Using the *** LOCAL *** Categories service");
		$GLOBALS["RemoteUfdbCat"]=0;
		$GLOBALS["DisableLogFileDaemonCategories"]=0;
		return;
	}
	
	
	if(is_file("/etc/artica-postfix/settings/Daemons/RemoteUfdbCat")){
		$GLOBALS["RemoteUfdbCat"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/RemoteUfdbCat"));
		events("Starting: Using a remote category service: {$GLOBALS["RemoteUfdbCat"]}");
	}
	
	
	if($GLOBALS["RemoteUfdbCat"]==1){
		$GLOBALS["ufdbCatInterface"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/ufdbCatInterface"));
		$GLOBALS["ufdbCatPort"]=intval(trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/ufdbCatPort")));
		$GLOBALS["DisableLogFileDaemonCategories"]=0;
		events("Starting: Using a remote category service: ufdbCatInterface:{$GLOBALS["ufdbCatInterface"]}:{$GLOBALS["ufdbCatPort"]}");
		
		if($GLOBALS["ufdbCatInterface"]==null){
			events("Warning: {$GLOBALS["ufdbCatInterface"]}:{$GLOBALS["ufdbCatPort"]} not a valid address");
			$GLOBALS["RemoteUfdbCat"]=0;
			$GLOBALS["DisableLogFileDaemonCategories"]=1;
			return ;
		}
		
		$GLOBALS["DisableLogFileDaemonCategories"]=0;
		return;
	}
	
	if(is_file("/etc/artica-postfix/settings/Daemons/SquidPerformance")){
		$GLOBALS["SquidPerformance"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidPerformance"));
		if($GLOBALS["SquidPerformance"]>0){$GLOBALS["DisableLogFileDaemonCategories"]=1;}
	}
	
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/SquidPerformance")){
		$GLOBALS["DisableLogFileDaemonCategories"]=1;
	}
	
}


function ParseSizeBuffer($buffer){
	$proxyname=null;
	if(!class_exists("class.logfile_daemon.inc")){include_once("/usr/share/artica-postfix/ressources/class.logfile_daemon.inc"); }
	$re=explode(":::", $buffer);
	if(preg_match("#^.*?\):\s+(.+)#", trim($re[0]),$rz)){$re[0]=$rz[1];}
	if($GLOBALS["VERBOSE"]){events($buffer);}
	if($GLOBALS["VERBOSE"]){events("ITEM: MAC......: {$re[0]} [".__LINE__."]");}
	$mac=trim(strtolower($re[0]));
	if($mac=="-"){$mac==null;}
	$mac=str_replace("-", ":", $mac);
	if($mac=="00:00:00:00:00:00"){$mac=null;}
	$ipaddr=trim($re[1]);
	if(!isset($GLOBALS["USER_MEM"])){$GLOBALS["USER_MEM"]=0;}
	

	// uid
	$uid=$re[2];
	$uid2=$re[3];
	if($uid=="-"){$uid=null;}
	if($uid2=="-"){$uid2=null;}
	if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $uid2)){$uid2=null;}
	if($uid==null){ if($uid2<>null){$uid=$uid2;} }
	
	
	
	$zdate=$re[4];
	$xtime=time();
	$SUFFIX_DATE=date("YmdH",$xtime);
	$logzdate=date("Y-m-d H:i:s",$xtime);
	
	
	$proto=$re[5];
	$uri=$re[6];
	$code_error=$re[8];
	$SIZE=$re[9];
	$SquidCode=$re[10];
	$UserAgent=urldecode($re[11]);
	$Forwarded=$re[12];
	$sitename=trim($re[13]);
	$hostname=trim($re[14]);
	$response_time=$re[15];
	$MimeType=trim($re[16]);
	$sni=trim($re[17]);
	$proxyname=trim($re[18]);
	$OUGROUP=trim($re[19]);
	
	$uid=trim(strtolower(str_replace("%20", " ", $uid)));
	$uid=str_replace("%25", "-", $uid);
	if($uid=="-"){$uid=null;}
	$Forwarded=str_replace("%25", "", $Forwarded);
	//events("MimeType: ......: $MimeType");
	if($sni=="-"){$sni=null;}
	

	
	if(strpos($uid, '$')>0){
		if(substr($uid, strlen($uid)-1,1)=="$"){
			$uid=null;
		}
	}
	
	if($sni<>null){
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $sitename)){$sitename=$sni;}
	}
	if($proxyname<>null){
		if(preg_match("#proxyname=(.+)#", $proxyname,$re)){
			$GLOBALS["REMOTE_PROXY_NAME"]=$re[1];
		}
	}else{
		$GLOBALS["REMOTE_PROXY_NAME"]=$GLOBALS["MYHOSTNAME"];
	}
	
	
	
	$GLOBALS["REMOTE_PROXY_NAME"]=str_replace("proxyname=", "", $GLOBALS["REMOTE_PROXY_NAME"]);
	if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $uid)){$uid=null;}
	if(!preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $ipaddr)){
		eventsfailed("***** WRONG LINE ipaddr:$ipaddr column 13 ". @implode(" | ", $re)."*****");
		return;
	}
	
	
	if($sitename=="-"){
		$h=parse_url($uri);
		if(isset($h["host"])){$sitename=$h["host"]; }
		
		if($sitename=="-"){
			eventsfailed("***** WRONG SITENAME \"$sitename\" column 13 ". @implode(" | ", $re)."*****");
			eventsfailed("$buffer");
			eventsfailed("*");
			$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;
			return;
		}
		if($sitename==null){
			eventsfailed("***** WRONG SITENAME \"$sitename\" column 13 ". @implode(" | ", $re)."*****");
			eventsfailed("$buffer");
			eventsfailed("*");
			$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;
			return;
		}
	}
	

	if(strpos($sitename, ":")>0){
		$XA=explode(":",$sitename);
		$sitename=$XA[0];
	}
	
	if($sitename=="127.0.0.1"){
		$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;
		if($GLOBALS["VERBOSE"]){ events("127.0.0.1 -> uid = null -> SKIP"); }
		return;
	}
	
	if($GLOBALS["ResolvIPStatistics"]==1){
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $sitename)){
			$sitename=xRESOLV($sitename);
		}
	}
	
	
	if($Forwarded=="unknown"){$Forwarded=null;}
	if($Forwarded=="-"){$Forwarded=null;}
	if($Forwarded=="0.0.0.0"){$Forwarded=null;}
	if($Forwarded=="255.255.255.255"){$Forwarded=null;}
	
	
	if(strlen($Forwarded)>4){
		$ipaddr=$Forwarded;
		$mac=null;
	}
	
	$ipaddr=str_replace("%25", "-", $ipaddr);
	$mac=str_replace("%25", "-", $mac);
	if($mac=="-"){$mac=null;}
	
	if(($ipaddr=="127.0.0.1") OR ($ipaddr=="::")){if($uid==null){
		$GLOBALS["REFUSED_REQUESTS"]=$GLOBALS["REFUSED_REQUESTS"]+1;
		if($GLOBALS["VERBOSE"]){ events("127.0.0.1 -> uid = null -> SKIP"); }
		return;
		}
	}
	if(preg_match("#([0-9:a-z]+)$#", $mac,$z)){$mac=$z[1];}
	
	
	if($SIZE==0){return;}
	
	if($GLOBALS["VERBOSE"]){
		
		events("ITEM: DATE......: $logzdate");
		events("ITEM: MAC.......: $mac");
		events("ITEM: IP........: $ipaddr");
		events("ITEM: Size......: $SIZE");
		events("ITEM: SQUID CODE: $SquidCode");
		events("ITEM: HTTP CODE.: $code_error");
		events("ITEM: uid.......: $uid");
		events("ITEM: uri.......: $uri");
		events("ITEM: UserAgent.: $UserAgent");
		events("ITEM: Forwarded.: $Forwarded");
		events("ITEM: SiteName..: $sitename");
		events("ITEM: ProxyName.: {$GLOBALS["REMOTE_PROXY_NAME"]}");
		
	}
	
	
	
	
	$GLOBALS["COUNT_HASH_TABLE"]=$GLOBALS["COUNT_HASH_TABLE"]+1;
	
	$arrayURI=parse_url($uri);
	$sitename=$arrayURI["host"];
	
	
	
	
	if(strpos($sitename, ":")){
		$xtr=explode(":",$sitename);
		$sitename=$xtr[0];
		if(preg_match("#^www\.(.+)#", $sitename,$rz)){$sitename=$rz[1];}
	}
	
//	$uid=UID_MEM_CACHE($uid,$mac,$ipaddr);
	
	$TimeCache=date("YmdH");
	$logfile_daemon=new logfile_daemon();
	$cached=$logfile_daemon->CACHEDORNOT($SquidCode);
	if($GLOBALS["DEBUG_MEM"]){events("RTT: $sitename - $SquidCode = $cached");}
	
		$GLOBALS["ACCEPTED_REQUESTS"]=$GLOBALS["ACCEPTED_REQUESTS"]+1;
	if(!isset($GLOBALS["CATEGORIES"][$sitename])){$GLOBALS["CATEGORIES"][$sitename]=$GLOBALS["MYSQL_CATZ"]->GET_CATEGORIES($sitename);}
	
	$MAIN["TIMESTAMP"]=time();
	$MAIN["URI"]=$uri;
	$MAIN["sitename"]=$sitename;
	$familysite=$GLOBALS["SQUID_FAMILY_CLASS"]->GetFamilySites($sitename);
	$category=$GLOBALS["CATEGORIES"][$sitename];
	
	$MAIN["SIZE"]=intval($SIZE);
	$MAIN["CACHED"]=$cached;
	
	if($GLOBALS["UserAgentsStatistics"]==1){UserAgentsStatistics($UserAgent,$mac,$uid,$SIZE);}else{
		if($GLOBALS["DEBUG_USERAGENT"]){events("UserAgentsStatistics is disabled...");}
	}
	
	CachedSizeMem($MAIN["CACHED"],$MAIN["SIZE"]);
	CachedUserMem($sitename,$SIZE,$mac,$uid,$ipaddr,$category,$familysite,$OUGROUP);
}



function writeCompresslogs($filename,$line){
	if($GLOBALS["EnableInfluxDB"]==0){return;}
	if(isset($GLOBALS["BYTES_WRITE"])){$GLOBALS["BYTES_WRITE"]=0;}
	$GLOBALS["BYTES_WRITE"]=intval($GLOBALS["BYTES_WRITE"])+strlen($line);
	$f = @fopen($filename, 'a');
	@fwrite($f, "$line\n");
	@fclose($f);	
}






function tool_time_sec($last_time){
	if($last_time==0){return 0;}
	$data1 = $last_time;
	$data2 = time();
	$difference = ($data2 - $data1);
	return $difference;
}

function UserAgentsStatisticsMemDump(){
	$time=$GLOBALS["CACHEDUserAgentsStatistics"]["TIME"];
	$xtime=tool_time_sec($GLOBALS["CACHEDUserAgentsStatistics"]["TIME"]);
	if($GLOBALS["DEBUG_MEM"]){events("CACHEDUserAgentsStatistics: {$xtime}s/10 ".count($GLOBALS["CACHEDUserAgentsStatistics"])." elemnt(s)");}
	if($xtime<10){return;}

	$MAIN=$GLOBALS["CACHEDUserAgentsStatistics"];
	$q=new influx();
	while (list ($KEYMD5, $ARRAY) = each ($MAIN)){
		if(!isset($GLOBALS["CACHEDUserAgentsStatistics"][$KEYMD5]["USERAGENT"])){continue;}
		$PROXYNAME=$GLOBALS["CACHEDUserAgentsStatistics"][$KEYMD5]["PROXYNAME"];
		$USERAGENT=$GLOBALS["CACHEDUserAgentsStatistics"][$KEYMD5]["USERAGENT"];
		$UID=$GLOBALS["CACHEDUserAgentsStatistics"][$KEYMD5]["UID"];
		$MAC=$GLOBALS["CACHEDUserAgentsStatistics"][$KEYMD5]["MAC"];
		$SIZE=intval($GLOBALS["CACHEDUserAgentsStatistics"][$KEYMD5]["SIZE"]);
		$RQS=intval($GLOBALS["CACHEDUserAgentsStatistics"][$KEYMD5]["RQS"]);
		$line=time().":::$USERAGENT:::$UID:::$MAC:::$SIZE:::$RQS:::$PROXYNAME";
		
		if($GLOBALS["NoCompressStatisticsByHour"]==0){
			writeCompresslogs("{$GLOBALS["LogFileDeamonLogDir"]}/USERAGENTS",$line);
			unset($GLOBALS["CACHEDUserAgentsStatistics"][$KEYMD5]);
			continue;
		}
		
		
		
		$zArray=array();
		$zArray["tags"]["USERAGENT"]=$USERAGENT;
		$zArray["tags"]["UID"]=$UID;
		$zArray["tags"]["MAC"]=$MAC;
		$zArray["fields"]["SIZE"]=$SIZE;
		$zArray["fields"]["RQS"]=$RQS;
		$zArray["tags"]["proxyname"]=$PROXYNAME;
		$zArray["fields"]["ZDATE"]=time();
		if($GLOBALS["DEBUG_MEM"]){events("INSERT - {$zArray["tags"]["USERAGENT"]} {$zArray["fields"]["SIZE"]}Bytes {$zArray["fields"]["RQS"]}rqs [".__LINE__."]");}
		if($GLOBALS["EnableInfluxDB"]==1){$q->insert("useragents", $zArray);}
		unset($GLOBALS["CACHEDUserAgentsStatistics"][$KEYMD5]);

	}
	$GLOBALS["CACHEDUserAgentsStatistics"]=array();
	$GLOBALS["CACHEDUserAgentsStatistics"]["TIME"]=time();


}


function UserAgentsStatistics($UserAgent,$mac,$uid,$SIZE){
	if(strlen(trim($UserAgent))<2){return;}
	if(intval($SIZE)==0){return;}
	$KEYMD5=md5("$UserAgent$mac$uid{$GLOBALS["REMOTE_PROXY_NAME"]}");
	if(!isset($GLOBALS["CACHEDUserAgentsStatistics"]["TIME"])){$GLOBALS["CACHEDUserAgentsStatistics"]["TIME"]=time();}
	
	
	if(!isset($GLOBALS["CACHEDUserAgentsStatistics"][$KEYMD5]["TIME"])){
		$GLOBALS["CACHEDUserAgentsStatistics"][$KEYMD5]["TIME"]=time();
		$GLOBALS["CACHEDUserAgentsStatistics"][$KEYMD5]["SIZE"]=intval($SIZE);
		$GLOBALS["CACHEDUserAgentsStatistics"][$KEYMD5]["UID"]=$uid;
		$GLOBALS["CACHEDUserAgentsStatistics"][$KEYMD5]["RQS"]=1;
		$GLOBALS["CACHEDUserAgentsStatistics"][$KEYMD5]["USERAGENT"]=$UserAgent;
		$GLOBALS["CACHEDUserAgentsStatistics"][$KEYMD5]["MAC"]=$mac;
		$GLOBALS["CACHEDUserAgentsStatistics"][$KEYMD5]["PROXYNAME"]=$GLOBALS["REMOTE_PROXY_NAME"];
		if($GLOBALS["DEBUG_USERAGENT"]){events("USERAGENT++: $UserAgent $SIZE [1]  [$KEYMD5]");}
		UserAgentsStatisticsMemDump();
		return;
	}
	
	$oldsize=intval($GLOBALS["CACHEDUserAgentsStatistics"][$KEYMD5]["SIZE"]);
	$oldsize=$oldsize+$SIZE;
	$GLOBALS["CACHEDUserAgentsStatistics"][$KEYMD5]["SIZE"]=$oldsize;
	
	$oldrqs=intval($GLOBALS["CACHEDUserAgentsStatistics"][$KEYMD5]["RQS"]);
	$oldrqs++;
	$GLOBALS["CACHEDUserAgentsStatistics"][$KEYMD5]["RQS"]=$oldrqs;
	if($GLOBALS["DEBUG_USERAGENT"]){events("USERAGENT: $UserAgent $oldsize [$oldrqs] [$KEYMD5]");}
	UserAgentsStatisticsMemDump();
	
	
}



function CachedUserMem($sitename,$SIZE,$mac,$uid,$ipaddr,$category,$familysite,$OUGROUP){
	if(!isset($GLOBALS["CACHEDUSersMemTime"]["TIME"])){$GLOBALS["CACHEDUSersMemTime"]["TIME"]=time();}
	$KEYMD5=md5("$sitename,$mac,$uid,$ipaddr,$familysite,{$GLOBALS["REMOTE_PROXY_NAME"]}");
	$ORGA=null;
	
	if($GLOBALS["DEBUG_MEM"]){events("[$KEYMD5]: $sitename,$SIZE,$mac,$uid,$ipaddr,$category,$familysite");}
	
	
	if($OUGROUP<>null){
		
		$OUGROUPTR=explode(",",$OUGROUP);
		$ADGROUP=$OUGROUPTR[0];
		if(isset($OUGROUPTR[1])){$ORGA=$OUGROUPTR[1];}
	}
	
	
	
	if(!isset($GLOBALS["LOGACCESS_TIME"])){
		$GLOBALS["LOGACCESS_TIME"]=time();
	}
	
	$KEYMD5_USER=md5("$mac$uid$ipaddr");
	

	
	if(!isset($GLOBALS["UserAutDB"][$KEYMD5_USER])){
		$GLOBALS["UserAutDB"][$KEYMD5_USER]="$mac;$uid;$ipaddr;";
		
	}
	
	
	if(!isset($GLOBALS["USERRTT"][$KEYMD5_USER]["TIME"])){
		$GLOBALS["USERRTT"][$KEYMD5_USER]["TIME"]=time();
		$GLOBALS["USERRTT"][$KEYMD5_USER]["USERID"]=$uid;
		$GLOBALS["USERRTT"][$KEYMD5_USER]["IPADDR"]=$ipaddr;
		$GLOBALS["USERRTT"][$KEYMD5_USER]["MAC"]=$mac;
		$GLOBALS["USERRTT"][$KEYMD5_USER]["PROXYNAME"]=$GLOBALS["REMOTE_PROXY_NAME"];
		$GLOBALS["USERRTT"][$KEYMD5_USER]["RQS"]=1;
		$GLOBALS["USERRTT"][$KEYMD5_USER]["GROUP"]=$ADGROUP;
		$GLOBALS["USERRTT"][$KEYMD5_USER]["ORG"]=$ORGA;
		$GLOBALS["USERRTT"][$KEYMD5_USER]["SIZE"]=intval($SIZE);
	}else{
		$GLOBALS["USERRTT"][$KEYMD5_USER]["RQS"]=$GLOBALS["USERRTT"][$KEYMD5_USER]["RQS"]+1;
		$GLOBALS["USERRTT"][$KEYMD5_USER]["SIZE"]=$GLOBALS["USERRTT"][$KEYMD5_USER]["SIZE"]+intval($SIZE);
		$GLOBALS["USERRTT"][$KEYMD5_USER]["GROUP"]=$ADGROUP;
		$GLOBALS["USERRTT"][$KEYMD5_USER]["ORG"]=$ORGA;
		$GLOBALS["USERRTT"][$KEYMD5_USER]["TIME"]=time();
	}
	
	
	if(!isset($GLOBALS["CACHEDUSersMem"][$KEYMD5]["TIME"])){
		$GLOBALS["CACHEDUSersMem"][$KEYMD5]["TIME"]=time();
		$GLOBALS["CACHEDUSersMem"][$KEYMD5]["CATEGORY"]=$category;
		$GLOBALS["CACHEDUSersMem"][$KEYMD5]["SITE"]=$sitename;
		$GLOBALS["CACHEDUSersMem"][$KEYMD5]["SIZE"]=intval($SIZE);
		$GLOBALS["CACHEDUSersMem"][$KEYMD5]["FAM"]=$familysite;
		$GLOBALS["CACHEDUSersMem"][$KEYMD5]["RQS"]=1;
		$GLOBALS["CACHEDUSersMem"][$KEYMD5]["USERID"]=$uid;
		$GLOBALS["CACHEDUSersMem"][$KEYMD5]["IPADDR"]=$ipaddr;
		$GLOBALS["CACHEDUSersMem"][$KEYMD5]["MAC"]=$mac;
		$GLOBALS["CACHEDUSersMem"][$KEYMD5]["GROUP"]=$ADGROUP;
		$GLOBALS["CACHEDUSersMem"][$KEYMD5]["ORG"]=$ORGA;
		$GLOBALS["CACHEDUSersMem"][$KEYMD5]["PROXYNAME"]=$GLOBALS["REMOTE_PROXY_NAME"];
		if($GLOBALS["DEBUG_MEM"]){events("[$KEYMD5]: $sitename/$ipaddr NEW {$SIZE}bytes  1rqs");}
		return;
	}
	
	$oldsize=intval($GLOBALS["CACHEDUSersMem"][$KEYMD5]["SIZE"]);
	$oldsize=$oldsize+$SIZE;
	$GLOBALS["CACHEDUSersMem"][$KEYMD5]["SIZE"]=$oldsize;
	$GLOBALS["CACHEDUSersMem"][$KEYMD5]["GROUP"]=$ADGROUP;
	$GLOBALS["CACHEDUSersMem"][$KEYMD5]["ORG"]=$ORGA;
	
	$oldrqs=intval($GLOBALS["CACHEDUSersMem"][$KEYMD5]["RQS"]);
	$oldrqs++;
	$GLOBALS["CACHEDUSersMem"][$KEYMD5]["RQS"]=$oldrqs;
	if($GLOBALS["DEBUG_MEM"]){events("[$KEYMD5]: $sitename/$ipaddr EDIT {$oldsize}bytes  {$oldrqs}rqs");}
	
	CachedUserMemDump();
	
}

function CachedUserMemDump(){
	
	
	$f = @fopen("{$GLOBALS["LogFileDeamonLogDir"]}/USERAUTDB.LOG", 'a');
	$xtime=tool_time_sec($GLOBALS["LOGACCESS_TIME"]);
	if($xtime<10){return;}
	while (list ($KEYMD5, $line) = each ($GLOBALS["UserAutDB"])){
		@fwrite($f, "$line\n");
		
	}
	$GLOBALS["UserAutDB"]=array();
	@fclose($f);
	
	
	
	$c=0;
	$MAIN=$GLOBALS["CACHEDUSersMem"];
	$q=new influx();
	$xRQS=0;
	while (list ($KEYMD5, $ARRAY) = each ($MAIN)){
		$zArray=array();
		$zArray2=array();
		if(!isset($GLOBALS["CACHEDUSersMem"][$KEYMD5]["SITE"])){
			unset($GLOBALS["CACHEDUSersMem"][$KEYMD5]);
			continue;
		}
		
		$CATEGORY=$GLOBALS["CACHEDUSersMem"][$KEYMD5]["CATEGORY"];
		$USERID=$GLOBALS["CACHEDUSersMem"][$KEYMD5]["USERID"];
		$IPADDR=$GLOBALS["CACHEDUSersMem"][$KEYMD5]["IPADDR"];
		$MAC=$GLOBALS["CACHEDUSersMem"][$KEYMD5]["MAC"];
		$SIZE=intval($GLOBALS["CACHEDUSersMem"][$KEYMD5]["SIZE"]);
		$SITE=$GLOBALS["CACHEDUSersMem"][$KEYMD5]["SITE"];
		$FAM=$GLOBALS["CACHEDUSersMem"][$KEYMD5]["FAM"];
		$RQS=$GLOBALS["CACHEDUSersMem"][$KEYMD5]["RQS"];
		$PROXYNAME=$GLOBALS["CACHEDUSersMem"][$KEYMD5]["PROXYNAME"];
		$GROUP=$GLOBALS["CACHEDUSersMem"][$KEYMD5]["GROUP"];
		$ORGA=$GLOBALS["CACHEDUSersMem"][$KEYMD5]["ORGA"];
		
		if($SIZE>0){
			FILL_DISK_SIZES($USERID,$IPADDR,$MAC,$SIZE,$CATEGORY,$FAM);
		}
		
		if($MAC==null){$MAC="00:00:00:00:00:00";}
		if($USERID==null){$USERID="none";}
		
		$xRQS=$xRQS+$RQS;
		$line=time().":::$CATEGORY:::$USERID:::$IPADDR:::$MAC:::$SIZE:::$SITE:::$FAM:::$RQS:::$PROXYNAME:::$GROUP:::$ORGA";
		$c++;
		
		writeCompresslogs("{$GLOBALS["LogFileDeamonLogDir"]}/ACCESS_LOG",$line);
		unset($GLOBALS["CACHEDUSersMem"][$KEYMD5]);
		
		
		
	}
	
	if(count($GLOBALS["USERRTT"])>0){
		while (list ($KEYMD5, $ARRAY) = each ($GLOBALS["USERRTT"])){
			if(!isset($GLOBALS["USERRTT"][$KEYMD5]["ORGA"])){$GLOBALS["USERRTT"][$KEYMD5]["ORGA"]=null;}
			$USERID=$GLOBALS["USERRTT"][$KEYMD5]["USERID"];
			$IPADDR=$GLOBALS["USERRTT"][$KEYMD5]["IPADDR"];
			$MAC=$GLOBALS["USERRTT"][$KEYMD5]["MAC"];
			$SIZE=intval($GLOBALS["USERRTT"][$KEYMD5]["SIZE"]);
			$RQS=$GLOBALS["USERRTT"][$KEYMD5]["RQS"];
			$PROXYNAME=$GLOBALS["USERRTT"][$KEYMD5]["PROXYNAME"];
			$GROUP=$GLOBALS["USERRTT"][$KEYMD5]["GROUP"];
			$ORGA=$GLOBALS["USERRTT"][$KEYMD5]["ORGA"];
			$line=time().":::$USERID:::$IPADDR:::$MAC:::$SIZE:::$RQS:::$PROXYNAME::$GROUP:::$ORGA";
			writeCompresslogs("{$GLOBALS["LogFileDeamonLogDir"]}/USERS_LOG",$line);
			unset($GLOBALS["USERRTT"][$KEYMD5]);
		}
	}
	
	events("CachedUserMemDump:: Saving $c/$xRQS requests time={$xtime}s");
	$GLOBALS["CACHEDUSersMemTime"]=array();
	$GLOBALS["USERRTT"]=array();
	$GLOBALS["LOGACCESS_TIME"]=time();

	
}




function CachedSizeMem($cached,$SIZE){
	
	
	$line=time().";{$GLOBALS["REMOTE_PROXY_NAME"]};$SIZE;";
	writeCompresslogs("{$GLOBALS["LogFileDeamonLogDir"]}/MAIN_SIZE",$line);
	
	if($cached==0){
		$line=time().";{$GLOBALS["REMOTE_PROXY_NAME"]};$SIZE;";
		writeCompresslogs("{$GLOBALS["LogFileDeamonLogDir"]}/NO_CACHED",$line);
		return;
	}
	
	$line=time().";{$GLOBALS["REMOTE_PROXY_NAME"]};$SIZE;";
	writeCompresslogs("{$GLOBALS["LogFileDeamonLogDir"]}/CACHED",$line);
	

}

function xRESOLV($sitename){
	if(!isset($GLOBALS["xRESOLV"])){$GLOBALS["xRESOLV"]=array();}
	if(count($GLOBALS["xRESOLV"])>20000){$GLOBALS["xRESOLV"]=array();}
	if(isset($GLOBALS["xRESOLV"][$sitename])){return $GLOBALS["xRESOLV"][$sitename];}
	
	$GLOBALS["xRESOLV"][$sitename]=gethostbyaddr($sitename);
	events("$sitename === {$GLOBALS["xRESOLV"][$sitename]}");
}



function tool_time_min($timeFrom){
	$data1 = $timeFrom;
	$data2 = time();
	$difference = ($data2 - $data1);
	$results=intval(round($difference/60));
	if($results<0){$results=1;}
	return $results;
}

function UID_SET_CACHE($uid=null,$mac=null,$ipaddr=null){
	$GLOBALS["IPADDR_TO_UID_MEM"][date("YmdH")][$ipaddr]=$uid;
	IPADDR_TO_UID_MEM_CLEAN();
	return $uid;
}

function UID_MEM_CACHE($uid=null,$mac=null,$ipaddr=null){
	$uid=trim(strtolower($uid));
	if($uid<>null){return UID_SET_CACHE($uid,null,$ipaddr);}
	if($ipaddr==null){return $uid;}
	
	if(isset($GLOBALS["IPADDR_TO_UID_MEM"][date("YmdH")][$ipaddr])){
		if($GLOBALS["IPADDR_TO_UID_MEM"][date("YmdH")][$ipaddr]<>null){return $GLOBALS["IPADDR_TO_UID_MEM"][date("YmdH")][$ipaddr];}
	}
	return $uid;
}

function IPADDR_TO_UID_MEM_CLEAN(){
	if(count($GLOBALS["IPADDR_TO_UID_MEM"])==1){return;}
	$currentKey=date("YmdH");
	$array=$GLOBALS["IPADDR_TO_UID_MEM"];
	while (list ($num, $line) = each ($array)){
		if($num==$currentKey){continue;}
		unset($GLOBALS["IPADDR_TO_UID_MEM"][$num]);
	}
}



function events($text){
	if(trim($text)==null){return;}
	if(!isset($GLOBALS["MYPID"])){$GLOBALS["MYPID"]=getmypid();}
	$pid=$GLOBALS["MYPID"];
	$date=@date("H:i:s");
	$logFile="/var/log/squid/logfile_daemon.debug";

	$size=@filesize($logFile);
	if($size>9000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$date:[REALTIME_LOGS] $pid `$text`\n");
	@fclose($f);
}
function events_youtube($text){
	if(trim($text)==null){return;}
	$pid=$GLOBALS["MYPID"];
	$date=@date("H:i:s");
	$logFile="/var/log/squid/logfile_daemon.youtube.debug";

	$size=@filesize($logFile);
	if($size>9000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$date:[".basename(__FILE__)."] $pid `$text`\n");
	@fclose($f);
}
function eventsfailed($text){
	if(trim($text)==null){return;}
	$pid=$GLOBALS["MYPID"];
	$date=@date("H:i:s");
	$logFile="/var/log/squid/logfile_daemon.failed.debug";
	
	$size=@filesize($logFile);
	if($size>9000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$date:[".basename(__FILE__)."] $pid `$text`\n");
	@fclose($f);	
}






function xmysql_escape_string2($line){
	
	$search=array("\\","\0","\n","\r","\x1a","'",'"');
	$replace=array("\\\\","\\0","\\n","\\r","\Z","\'",'\"');
	return str_replace($search,$replace,$line);
}

function microtime_float(){
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}


function rtt_microtime_float(){
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}



function rtt_microtime_ms($start){
	return  round(rtt_microtime_float() - $start,3);
}

function ufdbcat($sitename){

	if(isset($GLOBALS["CATEGORYOF"])){
		if(count($GLOBALS["CATEGORYOF"])>50000){$GLOBALS["CATEGORYOF"]=array();}
	}
	if(isset($GLOBALS["CATEGORYOF"][$sitename])){return $GLOBALS["CATEGORYOF"][$sitename];}
	
	$time_start = microtime_float();
	$s=new mysql_catz();
	$resp=$s->GET_CATEGORIES($sitename);
	$time_stop = microtime_float();
	$TimeExec = round($time_stop - $time_start,3);
	if($resp<>null){
		$GLOBALS["CATEGORYOF"][$sitename]=$resp;
		return $GLOBALS["CATEGORYOF"][$sitename];
	}
	
	

}
function _xTransArray(){

	$trans["category_society"]="society";
	$trans["category_association"]="associations";
	$trans["category_publicite"]="publicite";
	$trans["category_phishtank"]="phishtank";
	$trans["category_shopping"]="shopping";
	$trans["category_abortion"]="abortion";
	$trans["category_agressive"]="agressive";
	$trans["category_alcohol"]="alcohol";
	$trans["category_animals"]="animals";
	$trans["category_associations"]="associations";
	$trans["category_astrology"]="astrology";
	$trans["category_audio_video"]="audio-video";
	$trans["category_automobile_bikes"]="automobile/bikes";
	$trans["category_automobile_boats"]="automobile/boats";
	$trans["category_automobile_carpool"]="automobile/carpool";
	$trans["category_automobile_cars"]="automobile/cars";
	$trans["category_automobile_planes"]="automobile/planes";
	$trans["category_bicycle"]="bicycle";
	$trans["category_blog"]="blog";
	$trans["category_books"]="books";
	$trans["category_browsersplugins"]="browsersplugins";
	$trans["category_celebrity"]="celebrity";
	$trans["category_chat"]="chat";
	$trans["category_children"]="children";
	$trans["category_cleaning"]="cleaning";
	$trans["category_clothing"]="clothing";
	$trans["category_converters"]="converters";
	$trans["category_cosmetics"]="cosmetics";
	$trans["category_culture"]="culture";
	$trans["category_dangerous_material"]="dangerous_material";
	$trans["category_dating"]="dating";
	$trans["category_dictionaries"]="dictionaries";
	$trans["category_downloads"]="downloads";
	$trans["category_drugs"]="drugs";
	$trans["category_dynamic"]="dynamic";
	$trans["category_electricalapps"]="electricalapps";
	$trans["category_electronichouse"]="electronichouse";
	$trans["category_filehosting"]="filehosting";
	$trans["category_finance_banking"]="finance/banking";
	$trans["category_finance_insurance"]="finance/insurance";
	$trans["category_finance_moneylending"]="finance/moneylending";
	$trans["category_finance_other"]="finance/other";
	$trans["category_finance_realestate"]="finance/realestate";
	$trans["category_financial"]="financial";
	$trans["category_forums"]="forums";
	$trans["category_gamble"]="gamble";
	$trans["category_games"]="games";
	$trans["category_genealogy"]="genealogy";
	$trans["category_gifts"]="gifts";
	$trans["category_governements"]="governments";
	$trans["category_governments"]="governments";
	$trans["category_green"]="green";
	$trans["category_hacking"]="hacking";
	$trans["category_handicap"]="handicap";
	$trans["category_health"]="health";
	$trans["category_hobby_arts"]="hobby/arts";
	$trans["category_hobby_cooking"]="hobby/cooking";
	$trans["category_hobby_other"]="hobby/other";
	$trans["category_hobby_pets"]="hobby/pets";
	$trans["category_paytosurf"]="paytosurf";
	$trans["category_terrorism"]="terrorism";
	$trans["category_hobby_fishing"]="hobby/fishing";
	$trans["category_hospitals"]="hospitals";
	$trans["category_houseads"]="houseads";
	$trans["category_housing_accessories"]="housing/accessories";
	$trans["category_housing_doityourself"]="housing/doityourself";
	$trans["category_housing_builders"]="housing/builders";
	$trans["category_humanitarian"]="humanitarian";
	$trans["category_imagehosting"]="imagehosting";
	$trans["category_industry"]="industry";
	$trans["category_internal"]="internal";
	$trans["category_isp"]="isp";
	$trans["category_smalladds"]="smalladds";
	$trans["category_jobsearch"]="jobsearch";
	$trans["category_jobtraining"]="jobtraining";
	$trans["category_justice"]="justice";
	$trans["category_learning"]="learning";
	$trans["category_liste_bu"]="liste_bu";
	$trans["category_luxury"]="luxury";
	$trans["category_mailing"]="mailing";
	$trans["category_malware"]="malware";
	$trans["category_manga"]="manga";
	$trans["category_maps"]="maps";
	$trans["category_marketingware"]="marketingware";
	$trans["category_medical"]="medical";
	$trans["category_mixed_adult"]="mixed_adult";
	$trans["category_mobile_phone"]="mobile-phone";
	$trans["category_models"]="models";
	$trans["category_movies"]="movies";
	$trans["category_music"]="music";
	$trans["category_nature"]="nature";
	$trans["category_news"]="news";
	
	$trans["category_passwords"]="passwords";
	$trans["category_phishing"]="phishing";
	$trans["category_photo"]="photo";
	$trans["category_pictures"]="pictures";
	$trans["category_pictureslib"]="pictureslib";
	$trans["category_politic"]="politic";
	$trans["category_porn"]="porn";
	$trans["category_press"]="news";
	$trans["category_proxy"]="proxy";
	$trans["category_publicite"]="publicite";
	$trans["category_reaffected"]="reaffected";
	$trans["category_recreation_humor"]="recreation/humor";
	$trans["category_recreation_nightout"]="recreation/nightout";
	$trans["category_recreation_schools"]="recreation/schools";
	$trans["category_recreation_sports"]="recreation/sports";
	$array["category_getmarried"]="getmarried";
	$array["category_police"]="police";
	$trans["category_recreation_travel"]="recreation/travel";
	$trans["category_recreation_wellness"]="recreation/wellness";
	$trans["category_redirector"]="redirector";
	$trans["category_religion"]="religion";
	$trans["category_remote_control"]="remote-control";
	
	$trans["category_sciences"]="sciences";
	$trans["category_science_astronomy"]="science/astronomy";
	$trans["category_science_computing"]="science/computing";
	$trans["category_science_weather"]="science/weather";
	$trans["category_science_chemistry"]="science/chemistry";
	$trans["category_searchengines"]="searchengines";
	$trans["category_sect"]="sect";
	$trans["category_sexual_education"]="sexual_education";
	$trans["category_sex_lingerie"]="sex/lingerie";
	$trans["category_smallads"]="smallads";
	$trans["category_socialnet"]="socialnet";
	$trans["category_spyware"]="spyware";
	$trans["category_sslsites"]="sslsites";
	$trans["category_stockexchange"]="stockexchange";
	$trans["category_strict_redirector"]="redirector";
	$trans["category_strong_redirector"]="redirector";
	$trans["category_suspicious"]="suspicious";
	$trans["category_teens"]="teens";
	$trans["category_tobacco"]="tobacco";
	$trans["category_tracker"]="tracker";
	$trans["category_translator"]="translators";
	$trans["category_translators"]="translators";
	$trans["category_transport"]="transport";
	$trans["category_tricheur"]="tricheur";
	$trans["category_updatesites"]="updatesites";
	$trans["category_violence"]="violence";
	$trans["category_warez"]="warez";
	$trans["category_weapons"]="weapons";
	$trans["category_webapps"]="webapps";
	$trans["category_webmail"]="webmail";
	$trans["category_webphone"]="webphone";
	$trans["category_webplugins"]="webplugins";
	$trans["category_webradio"]="webradio";
	$trans["category_webtv"]="webtv";
	$trans["category_wine"]="wine";
	$trans["category_womanbrand"]="womanbrand";
	$trans["category_horses"]="horses";
	$trans["category_meetings"]="meetings";
	$trans["category_tattooing"]="tattooing";
	$trans["category_advertising"]="publicite";
	$trans["category_getmarried"]="getmarried";
	$trans["category_literature"]="literature";
	$trans["category_police"]="police";
	$trans["category_search"]="searchengines";
		
	return $trans;

}
function tablename_tocat($tablename){
	
	$trans=_xTransArray();
	if(!isset($trans[$tablename])){return $tablename;}
		
}


function shutdown() {
	$error = error_get_last();
	$type=trim($error["type"]);
	$message= trim($error["message"]);
	if($message==null){return;}
	$file = $error["file"];
	$line = $error["line"];
	if(function_exists("openlog")){openlog("squid-tail", LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog(true, "Fatal: Stopped with last error $type $message line $line");}
	if(function_exists("closelog")){closelog();}

}

function AccountDecode($path){
	if(strpos($path, "%")==0){return $path;}
	$path=str_replace("%C3%C2§","ç",$path);
	$path=str_replace("%5C","\\",$path);
	$path=str_replace("%20"," ",$path);
	$path=str_replace("%0A","\n",$path);
	$path=str_replace("%C2£","£",$path);
	$path=str_replace("%C2§","§",$path);
	$path=str_replace("%C3§","ç",$path);
	$path=str_replace("%E2%82%AC","€",$path);
	$path=str_replace("%C3%89","É",$path);
	$path=str_replace("%C3%A9","é",$path);
	$path=str_replace("%C3%A0","à",$path);
	$path=str_replace("%C3%AA","ê",$path);
	$path=str_replace("%C3%B9","ù",$path);
	$path=str_replace("%C3%A8","è",$path);
	$path=str_replace("%C3%A2","â",$path);
	$path=str_replace("%C3%B4","ô",$path);
	$path=str_replace("%C3%AE","î",$path);
	$path=str_replace("%E9","é",$path);
	$path=str_replace("%E0","à",$path);
	$path=str_replace("%F9","ù",$path);
	$path=str_replace("%20"," ",$path);
	$path=str_replace("%E8","è",$path);
	$path=str_replace("%E7","ç",$path);
	$path=str_replace("%26","&",$path);
	$path=str_replace("%FC","ü",$path);
	$path=str_replace("%2F","/",$path);
	$path=str_replace("%F6","ö",$path);
	$path=str_replace("%EB","ë",$path);
	$path=str_replace("%EF","ï",$path);
	$path=str_replace("%EE","î",$path);
	$path=str_replace("%EA","ê",$path);
	$path=str_replace("%E2","â",$path);
	$path=str_replace("%FB","û",$path);
	$path=str_replace("%u20AC","€",$path);
	$path=str_replace("%u2014","–",$path);
	$path=str_replace("%u2013","—",$path);
	$path=str_replace("%24","$",$path);
	$path=str_replace("%21","!",$path);
	$path=str_replace("%23","#",$path);
	$path=str_replace("%2C",",",$path);
	$path=str_replace("%7E",'~',$path);
	$path=str_replace("%22",'"',$path);
	$path=str_replace("%25",'%',$path);
	$path=str_replace("%27","'",$path);
	$path=str_replace("%F8","ø",$path);
	$path=str_replace("%2C",",",$path);
	$path=str_replace("%3A",":",$path);
	$path=str_replace("%A1","¡",$path);
	$path=str_replace("%A7","§",$path);
	$path=str_replace("%B2","²",$path);
	$path=str_replace("%3B",";",$path);
	$path=str_replace("%3C","<",$path);
	$path=str_replace("%3E",">",$path);
	$path=str_replace("%B5","µ",$path);
	$path=str_replace("%B0","°",$path);
	$path=str_replace("%7C","|",$path);
	$path=str_replace("%5E","^",$path);
	$path=str_replace("%60","`",$path);
	$path=str_replace("%25","%",$path);
	$path=str_replace("%A3","£",$path);
	$path=str_replace("%3D","=",$path);
	$path=str_replace("%3F","?",$path);
	$path=str_replace("%3F","€",$path);
	$path=str_replace("%28","(",$path);
	$path=str_replace("%29",")",$path);
	$path=str_replace("%5B","[",$path);
	$path=str_replace("%5D","]",$path);
	$path=str_replace("%7B","{",$path);
	$path=str_replace("%7D","}",$path);
	$path=str_replace("%2B","+",$path);
	$path=str_replace("%40","@",$path);
	$path=str_replace("%09","\t",$path);
	$path=str_replace("%u0430","а",$path);
	$path=str_replace("%u0431","б",$path);
	$path=str_replace("%u0432","в",$path);
	$path=str_replace("%u0433","г",$path);
	$path=str_replace("%u0434","д",$path);
	$path=str_replace("%u0435","е",$path);
	$path=str_replace("%u0451","ё",$path);
	$path=str_replace("%u0436","ж",$path);
	$path=str_replace("%u0437","з",$path);
	$path=str_replace("%u0438","и",$path);
	$path=str_replace("%u0439","й",$path);
	$path=str_replace("%u043A","к",$path);
	$path=str_replace("%u043B","л",$path);
	$path=str_replace("%u043C","м",$path);
	$path=str_replace("%u043D","н",$path);
	$path=str_replace("%u043E","о",$path);
	$path=str_replace("%u043F","п",$path);
	$path=str_replace("%u0440","р",$path);
	$path=str_replace("%u0441","с",$path);
	$path=str_replace("%u0442","т",$path);
	$path=str_replace("%u0443","у",$path);
	$path=str_replace("%u0444","ф",$path);
	$path=str_replace("%u0445","х",$path);
	$path=str_replace("%u0446","ц",$path);
	$path=str_replace("%u0447","ч",$path);
	$path=str_replace("%u0448","ш",$path);
	$path=str_replace("%u0449","щ",$path);
	$path=str_replace("%u044A","ъ",$path);
	$path=str_replace("%u044B","ы",$path);
	$path=str_replace("%u044C","ь",$path);
	$path=str_replace("%u044D","э",$path);
	$path=str_replace("%u044E","ю",$path);
	$path=str_replace("%u044F","я",$path);
	return $path;
}

function FILL_DISK_SIZES($USERID=null,$IPADDR,$MAC,$SIZE,$CATEGORY,$FAM){
	if($SIZE==0){return;}
	$base="/home/squid/rttsize";
	$YEAR=date("Y");
	$MONTH=date("m");
	$DAY=date("d");
	$HOUR=date("H");
	$WEEK=date("W");
	$baseHour="/home/squid/rttsize/$YEAR/$MONTH/$WEEK/$DAY/$HOUR";
	$baseDay="/home/squid/rttsize/$YEAR/$MONTH/$WEEK/$DAY";
	$baseWeek="/home/squid/rttsize/$YEAR/$MONTH/$WEEK";
	$baseMonth="/home/squid/rttsize/$YEAR/$MONTH";
	
	if(!is_dir("/home/squid/rttsize/$YEAR/$MONTH/$DAY")){
		@mkdir("/home/squid/rttsize/$YEAR/$MONTH/$DAY",0755,true);
		@chown("/home/squid/rttsize/$YEAR/$MONTH/$DAY","squid");
		@chgrp("/home/squid/rttsize/$YEAR/$MONTH/$DAY","squid");
	}
	
	if(!is_dir($baseHour)){
		@mkdir($baseHour,0755,true);
		@chown("$baseHour","squid");
		@chgrp("$baseHour","squid");
	}
	FILL_DISK_UID("$baseHour/UID",$USERID,$FAM,$CATEGORY,$SIZE);
	FILL_DISK_MAC("$baseHour/MAC",$MAC,$FAM,$CATEGORY,$SIZE);
	FILL_DISK_IPADDR("$baseHour/IPADDR",$IPADDR,$FAM,$CATEGORY,$SIZE);
	FILL_DISK_WEBS("$baseHour/WEBS",$FAM,$CATEGORY,$SIZE);
	FILL_DISK_CATS("$baseHour/CATS",$FAM,$CATEGORY,$SIZE);
	FILL_DISK_TOT("$baseHour",$FAM,$CATEGORY,$SIZE);
	
	FILL_DISK_UID("$baseDay/UID",$USERID,$FAM,$CATEGORY,$SIZE);
	FILL_DISK_MAC("$baseDay/MAC",$MAC,$FAM,$CATEGORY,$SIZE);
	FILL_DISK_IPADDR("$baseDay/IPADDR",$IPADDR,$FAM,$CATEGORY,$SIZE);
	FILL_DISK_WEBS("$baseDay/WEBS",$FAM,$CATEGORY,$SIZE);
	FILL_DISK_CATS("$baseDay/CATS",$FAM,$CATEGORY,$SIZE);
	FILL_DISK_TOT("$baseDay",$FAM,$CATEGORY,$SIZE);

	FILL_DISK_UID("$baseWeek/UID",$USERID,$FAM,$CATEGORY,$SIZE);
	FILL_DISK_MAC("$baseWeek/MAC",$MAC,$FAM,$CATEGORY,$SIZE);
	FILL_DISK_IPADDR("$baseWeek/IPADDR",$IPADDR,$FAM,$CATEGORY,$SIZE);
	FILL_DISK_WEBS("$baseWeek/WEBS",$FAM,$CATEGORY,$SIZE);
	FILL_DISK_CATS("$baseWeek/CATS",$FAM,$CATEGORY,$SIZE);
	FILL_DISK_TOT("$baseWeek",$FAM,$CATEGORY,$SIZE);
	
	FILL_DISK_UID("$baseMonth/UID",$USERID,$FAM,$CATEGORY,$SIZE);
	FILL_DISK_MAC("$baseMonth/MAC",$MAC,$FAM,$CATEGORY,$SIZE);
	FILL_DISK_IPADDR("$baseMonth/IPADDR",$IPADDR,$FAM,$CATEGORY,$SIZE);
	FILL_DISK_WEBS("$baseMonth/WEBS",$FAM,$CATEGORY,$SIZE);	
	FILL_DISK_CATS("$baseMonth/CATS",$FAM,$CATEGORY,$SIZE);	
	FILL_DISK_TOT("$baseMonth",$FAM,$CATEGORY,$SIZE);
}

function FILL_DISK_TOT($base,$FAM,$CATEGORY,$SIZE){
	if(!is_file("$base/TOT")){
		@file_put_contents("$base/TOT",$SIZE);
	}else{
		$USERID_FAM=intval(@file_get_contents("$base/TOT"));
		$USERID_FAM=$USERID_FAM+$SIZE;
		@file_put_contents("$base/TOT",$USERID_FAM);
	}
	
}

function FILL_DISK_WEBS($baseHour,$FAM,$CATEGORY,$SIZE){
	
	if(!is_dir($baseHour)){
		@mkdir("$baseHour",0755,true);
		@chown("$baseHour","squid");
		@chgrp("$baseHour","squid");
	}
	
	if(!is_file("$baseHour/$FAM")){
		@file_put_contents("$baseHour/$FAM",$SIZE);
	}else{
		$USERID_FAM=intval(@file_get_contents("$baseHour/$FAM"));
		$USERID_FAM=$USERID_FAM+$SIZE;
		@file_put_contents("$baseHour/$FAM",$USERID_FAM);
	}
	
}
function FILL_DISK_CATS($baseHour,$FAM,$CATEGORY,$SIZE){	
	$CATEGORY=str_replace("/", "_", $CATEGORY); $CATEGORY=str_replace(".", "_", $CATEGORY);
	if($CATEGORY==null){return;}
	
	if(!is_dir($baseHour)){
		@mkdir("$baseHour",0755,true);
		@chown("$baseHour","squid");
		@chgrp("$baseHour","squid");
	}
	
	if(!is_file("$baseHour/$CATEGORY")){
		@file_put_contents("$baseHour/$CATEGORY",$SIZE);
	}else{
		$USERID_FAM=intval(@file_get_contents("$baseHour/$CATEGORY"));
		$USERID_FAM=$USERID_FAM+$SIZE;
		@file_put_contents("$baseHour/$CATEGORY",$USERID_FAM);
	}
}

function FILL_DISK_UID($baseHour,$USERID=null,$FAM,$CATEGORY,$SIZE){
	if($USERID==null){return;}
	$CATEGORY=str_replace("/", "_", $CATEGORY); $CATEGORY=str_replace(".", "_", $CATEGORY);
	
	if(!is_dir("$baseHour/$USERID")){
		@mkdir("$baseHour/$USERID",0755,true);
		@chown("$baseHour/$USERID","squid");
		@chgrp("$baseHour/$USERID","squid");
	}
	
	
	if(!is_file("$baseHour/$USERID/$FAM")){
		@file_put_contents("$baseHour/$USERID/$FAM",$SIZE);
	}else{
		$USERID_FAM=intval(@file_get_contents("$baseHour/$USERID/$FAM"));
		$USERID_FAM=$USERID_FAM+$SIZE;
		@file_put_contents("$baseHour/$USERID/$FAM",$USERID_FAM);
	}
	
	if(!is_file("$baseHour/$USERID/TOT")){
		@file_put_contents("$baseHour/$USERID/TOT",$SIZE);
	}else{
		$USERID_FAM=intval(@file_get_contents("$baseHour/$USERID/TOT"));
		$USERID_FAM=$USERID_FAM+$SIZE;
		@file_put_contents("$baseHour/$USERID/TOT",$USERID_FAM);
	}
	
	if($CATEGORY==null){return;}
	
	
	if(!is_file("$baseHour/$USERID/$CATEGORY")){
			@file_put_contents("$baseHour/$USERID/$CATEGORY",$SIZE);
	}else{
		$USERID_FAM=intval(@file_get_contents("$baseHour/$USERID/$CATEGORY"));
		$USERID_FAM=$USERID_FAM+$SIZE;
		@file_put_contents("$baseHour/$USERID/$CATEGORY",$USERID_FAM);
	}	
			
}

function FILL_DISK_MAC($baseHour,$MAC=null,$FAM,$CATEGORY,$SIZE){
	if($MAC==null){return;}
	if($MAC=="00:00:00:00:00:00"){return;}
	$CATEGORY=str_replace("/", "_", $CATEGORY); $CATEGORY=str_replace(".", "_", $CATEGORY);
	
	if(!is_dir("$baseHour/$MAC")){
		@mkdir("$baseHour/$MAC",0755,true);
		@chown("$baseHour/$MAC","squid");
		@chgrp("$baseHour/$MAC","squid");
	}
	
	
	if(!is_file("$baseHour/$MAC/$FAM")){
		@file_put_contents("$baseHour/$MAC/$FAM",$SIZE);
	}else{
		$MAC_FAM=intval(@file_get_contents("$baseHour/$MAC/$FAM"));
		$MAC_FAM=$MAC_FAM+$SIZE;
		@file_put_contents("$baseHour/$MAC/$FAM",$MAC_FAM);
	}

	if(!is_file("$baseHour/$MAC/TOT")){
		@file_put_contents("$baseHour/$MAC/TOT",$SIZE);
	}else{
		$MAC_FAM=intval(@file_get_contents("$baseHour/$MAC/TOT"));
		$MAC_FAM=$MAC_FAM+$SIZE;
		@file_put_contents("$baseHour/$MAC/TOT",$MAC_FAM);
	}

	if($CATEGORY==null){return;}



	if(!is_file("$baseHour/$MAC/$CATEGORY")){
		@file_put_contents("$baseHour/$MAC/$CATEGORY",$SIZE);
	}else{
		$MAC_FAM=intval(@file_get_contents("$baseHour/$MAC/$CATEGORY"));
		$MAC_FAM=$MAC_FAM+$SIZE;
		@file_put_contents("$baseHour/$MAC/$CATEGORY",$MAC_FAM);
	}
		
}
function FILL_DISK_IPADDR($baseHour,$IPADDR=null,$FAM,$CATEGORY,$SIZE){
	if($IPADDR==null){return;}
	$CATEGORY=str_replace("/", "_", $CATEGORY); $CATEGORY=str_replace(".", "_", $CATEGORY);
	
	if(!is_dir("$baseHour/$IPADDR")){
		@mkdir("$baseHour/$IPADDR",0755,true);
		@chown("$baseHour/$IPADDR","squid");
		@chgrp("$baseHour/$IPADDR","squid");
	}
	
	if(!is_file("$baseHour/$IPADDR/$FAM")){
		@file_put_contents("$baseHour/$IPADDR/$FAM",$SIZE);
	}else{
		$IPADDR_FAM=intval(@file_get_contents("$baseHour/$IPADDR/$FAM"));
		$IPADDR_FAM=$IPADDR_FAM+$SIZE;
		@file_put_contents("$baseHour/$IPADDR/$FAM",$IPADDR_FAM);
	}

	if(!is_file("$baseHour/$IPADDR/TOT")){
		@file_put_contents("$baseHour/$IPADDR/TOT",$SIZE);
	}else{
		$IPADDR_FAM=intval(@file_get_contents("$baseHour/$IPADDR/TOT"));
		$IPADDR_FAM=$IPADDR_FAM+$SIZE;
		@file_put_contents("$baseHour/$IPADDR/TOT",$IPADDR_FAM);
	}

	if($CATEGORY==null){return;}


	if(!is_file("$baseHour/$IPADDR/$CATEGORY")){
		@file_put_contents("$baseHour/$IPADDR/$CATEGORY",$SIZE);
	}else{
		$IPADDR_FAM=intval(@file_get_contents("$baseHour/$IPADDR/$CATEGORY"));
		$IPADDR_FAM=$IPADDR_FAM+$SIZE;
		@file_put_contents("$baseHour/$IPADDR/$CATEGORY",$IPADDR_FAM);
	}
		
}
?>