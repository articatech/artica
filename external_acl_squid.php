#!/usr/bin/php -q
<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["KAV4PROXY_NOSESSION"]=true;
if(!isset($GLOBALS["ARTICALOGDIR"])){
		$GLOBALS["ARTICALOGDIR"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaLogDir"); 
		if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } 
}
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__) ."/framework/class.unix.inc");

  ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
  error_reporting(E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR);
  $GLOBALS["SplashScreenURI"]=null;
  
  $GLOBALS["PID"]=getmypid();
  $GLOBALS["SPLASH_DEBUG"]=false;
  $GLOBALS["SPLASH"]=false;
  $GLOBALS["STARTIME"]=time();
  $GLOBALS["MACTUIDONLY"]=false;
  $GLOBALS["uriToHost"]=array();
  $GLOBALS["SESSION_TIME"]=array();
  $GLOBALS["DEBUG_LEVEL"]=0;
  $GLOBALS["EnableArticaHotSpot"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaHotSpot"));
  if(!is_numeric($GLOBALS["EnableArticaHotSpot"])){$GLOBALS["EnableArticaHotSpot"]=0;}
  
  $GLOBALS["Q"]=new mysql_squid_builder();
  
  if(!is_numeric( $GLOBALS["DEBUG_LEVEL"])){ $GLOBALS["DEBUG_LEVEL"]=0;}
  if($GLOBALS["DEBUG_LEVEL"]>0){$GLOBALS["SPLASH_DEBUG"]=true;}
  $GLOBALS["F"] = @fopen("/var/log/squid/external-acl.log", 'a');
  
  
  $max_execution_time=ini_get('max_execution_time'); 
  if(is_file("/etc/artica-postfix/settings/Daemons/SplashScreenURI")){$GLOBALS["SplashScreenURI"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/SplashScreenURI");}
  if(preg_match("#session-time=([0-9]+)#", @implode(" ", $argv),$re)){$GLOBALS["SESSION_TIME"]=$re[1];}
  $GLOBALS["SESSIONS"]=unserialize(@file_get_contents("/etc/squid3/session.cache"));
  
  
  WLOG("Starting... Log level:{$GLOBALS["DEBUG_LEVEL"]};");
  if($argv[1]=="--mactouid"){$GLOBALS["MACTUIDONLY"]=true;}
  if($argv[1]=="--splash"){
  	$GLOBALS["SPLASH"]=true;
  	if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("Starting SPLASH engine , include class.mysql.squid.builder.php ");}
  	include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
  	$GLOBALS["Q"]=new mysql_squid_builder();
  	if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("[Q] initialised...");}
  }
  
  
  
  
  
while (!feof(STDIN)) {
 $url = trim(fgets(STDIN));
 
 if($url<>null){
 	if($GLOBALS["DEBUG_LEVEL"]>1){WLOG($url);}
 	$array=parseURL($url);
 	$SplashScreenURI=$GLOBALS["SplashScreenURI"];
 	if(!isset($GLOBALS["SPLASH_DEBUG"])){$GLOBALS["SPLASH_DEBUG"]=false;}
 	if($GLOBALS["DEBUG_LEVEL"]>1){WLOG($url." str:".strlen($url)." LOGIN:{$array["LOGIN"]},IPADDR:{$array["IPADDR"]} MAC:{$array["MAC"]} HOST:{$array["HOST"]} URI:{$array["URI"]}");}
 	
	if($GLOBALS["SPLASH_DEBUG"]){
		if(!$GLOBALS["SPLASH"]){
			WLOG("Splash screen is not enabled...");
		}
	}
 	
 	if($GLOBALS["SPLASH"]){
 			if(trim($array["LOGIN"])<>null){fwrite(STDOUT, "OK\n");continue;}
 			if($array["IPADDR"]=="127.0.0.1"){
 				fwrite(STDOUT, "OK\n");
 				continue;
 			}
	 		if($GLOBALS["SPLASH_DEBUG"]){WLOG("[{$array["IPADDR"]}]:{$array["RHOST"]} = $SplashScreenURI ??");}
			if($array["RHOST"]==uriToHost($SplashScreenURI)){
	 			fwrite(STDOUT, "OK\n");
	 			continue;
	 		}
	 		if($GLOBALS["SPLASH_DEBUG"]){WLOG("[{$array["IPADDR"]}]: -> SessionActive(array)");}
	 		$uid=trim($GLOBALS["Q"]->Hotspot_SessionActive($array));
	 		if($uid<>null){
	 			if($GLOBALS["SPLASH_DEBUG"]){WLOG("[{$array["IPADDR"]}]: -> SessionActive TRUE $uid");}
	 			fwrite(STDOUT, "OK user=$uid\n");
	 			continue;
	 		}else{
	 			if($GLOBALS["SPLASH_DEBUG"]){WLOG("[{$array["IPADDR"]}]: -> SessionActive FALSE");}
	 			$Message=base64_encode(serialize($array));
	 			fwrite(STDOUT, "ERR message=\"$Message\"\n");
	 		}
 		

 		continue;
 	}
 	
 	
 	if($GLOBALS["MACTUIDONLY"]){
 		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("ASK: {$array["MAC"]} = ?");}
 		$uid=GetMacToUid($array["MAC"]);
 		if($uid<>null){
 				fwrite(STDOUT, "OK user=$uid\n");
 				continue;
 		}
 		
 		$uid=trim(GetMacToUid($array["IPADDR"]));
 		if($uid==$array["IPADDR"]){$uid=null;}
 		
 		
 		if($uid<>null){
 			fwrite(STDOUT, "OK user=$uid\n");
 			continue;
 		}
 		fwrite(STDOUT, "OK\n");
 		continue;
 	} 	
 	
	
 	
  	if(CheckQuota($array)){fwrite(STDOUT, "OK\n");}else{WLOG("ERR \"Out of quota\"");fwrite(STDOUT, "ERR message=\"Out Of Quota\"\n");}
 }
}

CleanSessions();
$distanceInSeconds = round(abs(time() - $GLOBALS["STARTIME"]));
$distanceInMinutes = round($distanceInSeconds / 60);
WLOG("v1.0:". basename(__FILE__)." die after ({$distanceInSeconds}s/about {$distanceInMinutes}mn)");
if(isset($GLOBALS["F"])){@fclose($GLOBALS["F"]);}


function CleanSessions(){
	if(!isset($GLOBALS["SESSIONS"])){return;}
	if(!is_array($GLOBALS["SESSIONS"])){return;}
	$cachesSessions=unserialize(@file_get_contents("/etc/squid3/session.cache"));
	if(isset($cachesSessions)){
		if(is_array($cachesSessions)){
			while (list ($md5, $array) = each ($cachesSessions)){$GLOBALS["SESSIONS"][$md5]=$array;}
		}
	}
	@file_put_contents("/etc/squid3/session.cache", serialize($GLOBALS["SESSIONS"]));
}


function GetMacToUidHotSpot_MEM($MAC,$IPADDR,$MD5Key){
	if(!isset($GLOBALS["HOTSPOT"][$MD5Key])){return null;}
	if(!isset($GLOBALS["HOTSPOT"][$MD5Key]["UID"])){return null;}
	$timeSave=$GLOBALS["HOTSPOT"][$MD5Key]["TIME"];
	if($timeSave==0){return null;}
	if(tool_time_min($timeSave)>15){return null;}
	
	if(isset($GLOBALS["HOTSPOT"][$MD5Key][$MAC])){
		return $GLOBALS["HOTSPOT"][$MD5Key]["UID"];
	}
	
	
	if(isset($GLOBALS["HOTSPOT"][$MD5Key][$IPADDR])){
		return $GLOBALS["HOTSPOT"][$MD5Key]["UID"];
	}
	
	
	
}

function SetMacToUidHotSpot_MEM($MAC,$IPADDR,$MD5Key,$UID){
	if(count($GLOBALS["HOTSPOT"])>2500){$GLOBALS["HOTSPOT"]=array();}
	$GLOBALS["HOTSPOT"][$MD5Key][$MAC]=true;
	$GLOBALS["HOTSPOT"][$MD5Key]["UID"]=$UID;
	$GLOBALS["HOTSPOT"][$MD5Key]["TIME"]=time();
	$GLOBALS["HOTSPOT"][$MD5Key][$IPADDR]=true;
	return $UID;
}




function parseURL($url){
	$uri=null;
	if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("parseURL():: Analyze [$url]");}
	$md5=md5($url);
	
	// 10.0.0.32 00:1e:8c:a5:39:19 - crash-
	// 10.0.0.76 00:25:22:73:31:d5 -
	// 10.0.0.60 00:1d:92:70:96:70 - fbexternal-a.akamaihd.net:443
	
	if(preg_match("#([0-9\.]+)\s+([0-9\:a-z]+)\s+-(.+?):([0-9]+)$#", $url,$re)){
		$GLOBALS["CACHE_URI"][$md5]["LOGIN"]=null;
		$GLOBALS["CACHE_URI"][$md5]["IPADDR"]=$re[1];
		$GLOBALS["CACHE_URI"][$md5]["MAC"]=$re[2];
		$GLOBALS["CACHE_URI"][$md5]["HOST"]=GetComputerName($re[1]);
		$GLOBALS["CACHE_URI"][$md5]["URI"]=null;
		$GLOBALS["CACHE_URI"][$md5]["RHOST"]=$re[3];
		return $GLOBALS["CACHE_URI"][$md5];		
	}
	
	if(preg_match("#([0-9\.]+)\s+([0-9\:a-z]+)\s+-$#", $url,$re)){
		$GLOBALS["CACHE_URI"][$md5]["LOGIN"]=null;
		$GLOBALS["CACHE_URI"][$md5]["IPADDR"]=$re[1];
		$GLOBALS["CACHE_URI"][$md5]["MAC"]=$re[2];
		$GLOBALS["CACHE_URI"][$md5]["HOST"]=GetComputerName($re[1]);
		$GLOBALS["CACHE_URI"][$md5]["URI"]=null;
		$GLOBALS["CACHE_URI"][$md5]["RHOST"]=null;
		return $GLOBALS["CACHE_URI"][$md5];		
	}
	
	if(preg_match("#([0-9\.]+)\s+([0-9\:a-z]+)\s+-\s+([a-z]+)-$#", $url,$re)){
		$GLOBALS["CACHE_URI"][$md5]["LOGIN"]=null;
		$GLOBALS["CACHE_URI"][$md5]["IPADDR"]=$re[1];
		$GLOBALS["CACHE_URI"][$md5]["MAC"]=$re[2];
		$GLOBALS["CACHE_URI"][$md5]["HOST"]=GetComputerName($re[1]);
		$GLOBALS["CACHE_URI"][$md5]["URI"]=null;
		$GLOBALS["CACHE_URI"][$md5]["RHOST"]=$re[3];
		return $GLOBALS["CACHE_URI"][$md5];
	}	
	
	
	
		
	if(preg_match("#(http|ftp|https|ftps):\/\/(.*)#i", $url,$re)){
		$uri=$re[1]."://".$re[2];
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("found uri $uri");}
		$url=trim(str_replace($uri, "", $url));
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("Analyze $url");}
		
	}
	if($uri==null){
		if(preg_match("#([a-z0-9\.]+):([0-9]+)$#i", $url,$re)){
			$uri="http://".$re[1].":".$re[2];
			if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("found uri $uri");}
			$url=trim(str_replace($re[1].":".$re[2], "", $url));
			if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("Analyze \"$url\"");}
		}
	}
	if($uri<>null){
		$URLAR=parse_url($uri);
		if(isset($URLAR["host"])){$rhost=$URLAR["host"];}
	}
	
	
	
	
	if(isset($GLOBALS["CACHE_URI"][$md5])){return $GLOBALS["CACHE_URI"][$md5];}
	$tr=explode(" ", $url);
	if($GLOBALS["DEBUG_LEVEL"]>1){
		while (list ($index, $line) = each ($tr)){
			WLOG("tr[$index] = $line");	
		}
	}
	
	
	//max auth=4
	if(count($tr)==4){
		WLOG("count --> 4");
		$login=$tr[0];
		$ipaddr=$tr[1];
		$mac=$tr[2];
		$forwarded=$tr[3];
		if(isset($tr[4])){$uri=$tr[4];}
		if($mac=="00:00:00:00:00:00"){$mac=null;}
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $forwarded)){$ipaddr=$forwarded;}
		if($mac==null){$mac=GetMacFromIP($ipaddr);}
		
		if($mac=="00:00:00:00:00:00"){$mac=null;}
		$GLOBALS["CACHE_URI"][$md5]["LOGIN"]=$login;
		$GLOBALS["CACHE_URI"][$md5]["IPADDR"]=$ipaddr;
		$GLOBALS["CACHE_URI"][$md5]["MAC"]=$mac;
		$GLOBALS["CACHE_URI"][$md5]["HOST"]=GetComputerName($ipaddr);
		$GLOBALS["CACHE_URI"][$md5]["URI"]=$uri;
		$GLOBALS["CACHE_URI"][$md5]["RHOST"]=$rhost;
		return $GLOBALS["CACHE_URI"][$md5];
	}
	
	
	
	if(count($tr)==3){
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("count --> 3");}
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $tr[0])){
			//ip en premier donc mac=ok, pas de login
			$login=null;	
			$ipaddr=$tr[0];
			$mac=$tr[1];
			$forwarded=$tr[2];
			if(isset($tr[3])){$uri=$tr[3];}	
		}else{
			//login en premier donc mac=bad
			$login=$tr[0];
			$ipaddr=$tr[1];
			
			$forwarded=$tr[2];
			if(isset($tr[3])){$uri=$tr[3];}	
		}
		if($mac=="00:00:00:00:00:00"){$mac=null;}
		if(preg_match("#[0-9]+\[0-9]+\.[0-9]+\.[0-9]+#", $forwarded)){$ipaddr=$forwarded;}
		if($mac==null){$mac=GetMacFromIP($ipaddr);}
		if($mac=="00:00:00:00:00:00"){$mac=null;}
		$GLOBALS["CACHE_URI"][$md5]["LOGIN"]=$login;
		$GLOBALS["CACHE_URI"][$md5]["IPADDR"]=$ipaddr;
		$GLOBALS["CACHE_URI"][$md5]["MAC"]=$mac;
		$GLOBALS["CACHE_URI"][$md5]["HOST"]=GetComputerName($ipaddr);
		$GLOBALS["CACHE_URI"][$md5]["URI"]=$uri;	
		$GLOBALS["CACHE_URI"][$md5]["RHOST"]=$rhost;		
		return $GLOBALS["CACHE_URI"][$md5];		
		
	}
	
	
	
	if(count($tr)==2){
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("count --> 2");}
		//pas de login et pas de MAC;
		$login=null;	
		$ipaddr=$tr[0];
		$mac=null;
		$forwarded=$tr[1];
		if(isset($tr[2])){$uri=$tr[2];}	
		if(preg_match("#[0-9]+\[0-9]+\.[0-9]+\.[0-9]+#", $forwarded)){$ipaddr=$forwarded;}
		
	}
	if($mac==null){$mac=GetMacFromIP($ipaddr);}
	else{		
		if($mac=="00:00:00:00:00:00"){$mac=null;$mac=GetMacFromIP($ipaddr);}
	}
	if($mac=="00:00:00:00:00:00"){$mac=null;}
	$GLOBALS["CACHE_URI"][$md5]["LOGIN"]=$login;
	$GLOBALS["CACHE_URI"][$md5]["IPADDR"]=$ipaddr;
	$GLOBALS["CACHE_URI"][$md5]["MAC"]=$mac;
	$GLOBALS["CACHE_URI"][$md5]["HOST"]=GetComputerName($ipaddr);
	$GLOBALS["CACHE_URI"][$md5]["URI"]=$uri;	
	$GLOBALS["CACHE_URI"][$md5]["RHOST"]=$rhost;
	return $GLOBALS["CACHE_URI"][$md5];
	
	
}

function GetMacToUid($mac){
	if($mac==null){return;}
	$filereload="/var/log/squid/reload/{$GLOBALS["PID"]}.external_acl_squid.php";
	if(is_file("/var/log/squid/reload/{$GLOBALS["PID"]}.external_acl_squid.php")){
		WLOG("Flush memory...");
		unset($GLOBALS["GetMacToUidMD5"]);
		unset($GLOBALS["GetMacToUid"]);
		unset($GLOBALS["USERSDB"]);
		unset($GLOBALS["UID_FROM_MAC"]);
		unset($GLOBALS["UID_FROM_IP"]);
		@unlink($filereload);
	}
	if(isset($GLOBALS["GetMacToUidTIME"])){
		if(tool_time_min($GLOBALS["GetMacToUidTIME"])>5){
			unset($GLOBALS["GetMacToUidMD5"]);
			unset($GLOBALS["GetMacToUid"]);
			unset($GLOBALS["USERSDB"]);
			unset($GLOBALS["UID_FROM_MAC"]);
			unset($GLOBALS["UID_FROM_IP"]);
			$GLOBALS["GetMacToUidTIME"]=time();
		}
	}
	
	WLOG("Reloading MACToUid helper configuration");
	$uid=$GLOBALS["Q"]->MacToUid($mac);
	if($uid<>null){return $uid;}
	
	
	if($GLOBALS["EnableArticaHotSpot"]==1){
		$time=time();
		$q=new mysql_squid_builder();
		$sql="SELECT uid FROM hotspot_sessions WHERE MAC='$mac'";
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){ WLOG("Mysql Error line ".__LINE__); WLOG($sql); WLOG($q->mysql_error); }
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("$sql");}
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("$mac -> uid/mac: {$ligne["uid"]}");}
		if($ligne["uid"]<>null){return $ligne["uid"];}
		
		$sql="SELECT uid FROM hotspot_sessions WHERE ipaddr='$mac'";
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){ WLOG("Mysql Error line ".__LINE__); WLOG($sql); WLOG($q->mysql_error); }
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("$sql");}
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("$mac -> uid/ipaddr: {$ligne["uid"]}");}
		if($ligne["uid"]<>null){return $ligne["uid"];}
		
		$sql="SELECT uid FROM hotspot_networks WHERE pattern='$mac'";
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){ WLOG("Mysql Error line ".__LINE__); WLOG($sql); WLOG($q->mysql_error); }
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("$sql");}
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("$mac -> uid/ipaddr: {$ligne["uid"]}");}
		if($ligne["uid"]<>null){return $ligne["uid"];}
	}
	
		
	if(isset($GLOBALS["GetMacToUidMD5"])){
			$md5file=md5_file("/etc/squid3/MacToUid.ini");
			if($md5file<>$GLOBALS["GetMacToUidMD5"]){
				unset($GLOBALS["GetMacToUid"]);
			}
	}
	if(isset($GLOBALS["GetMacToUid"])){
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("MEM: $mac =`{$GLOBALS["GetMacToUid"][$mac]}`");}
		if(isset($GLOBALS["GetMacToUid"][$mac])){
				return $GLOBALS["GetMacToUid"][$mac];
			}
		return;
	}
	
	$GLOBALS["GetMacToUid"]=unserialize(@file_get_contents("/etc/squid3/MacToUid.ini"));
	$GLOBALS["GetMacToUidMD5"]=md5_file("/etc/squid3/MacToUid.ini");
	$GLOBALS["GetMacToUidTIME"]=time();
	
	if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("DISK: $mac =`{$GLOBALS["GetMacToUid"][$mac]}`");}
	if(isset($GLOBALS["GetMacToUid"][$mac])){return $GLOBALS["GetMacToUid"][$mac];}
}
function tool_time_min($timeFrom){
	$data1 = $timeFrom;
	$data2 = time();
	$difference = ($data2 - $data1);
	$results=intval(round($difference/60));
	if($results<0){$results=1;}
	return $results;
}




function CheckQuota($CPINFOS){
	$RULES=unserialize(@file_get_contents("/etc/squid3/squid.durations.ini"));
	if(!is_array($RULES)){return true;}
	if(count($RULES)==0){return true;}
	
	while (list ($duration, $array_duration) = each ($RULES)){
		while (list ($xtype, $array_type) = each ($array_duration)){
			while (list ($pattern, $quotaBytes) = each ($array_type)){
				WLOG("Check rule for duration:$duration type:$xtype ($pattern) $quotaBytes bytes");
				
				if($duration==1){
					if(CheckQuota_day($CPINFOS,$xtype,$pattern,$quotaBytes)){return false;}
					continue;
				}
				if($duration==2){
					if(CheckQuota_hour($CPINFOS,$xtype,$pattern,$quotaBytes)){return false;}
					continue;
				}
			}
		}
		
	}

return true;
	
	
}
function CheckQuota_day($infos,$xtype,$pattern,$quotaBytes){
	$IPADDR=$infos["IPADDR"];
	$MAC=$infos["MAC"];
	$HOST=$infos["HOST"];
	$LOGIN=$infos["LOGIN"];
	
	$array=unserialize(@file_get_contents("/etc/squid3/squid.quotasD.ini"));
	$pattern=str_replace(".", "\.", $pattern);
	$pattern=str_replace("*", ".*?", $pattern);	
	
	if($xtype=="ipaddr"){
		if($IPADDR==null){WLOG("$IPADDR is null");return false;}
		if(!preg_match("#$pattern#i", $IPADDR)){WLOG("$IPADDR did nor match rule $pattern");return false;}
		if(count($array["ipaddr"])==0){WLOG("ipaddr: not an array...");return false;}
		if(!isset($array["ipaddr"][$IPADDR])){WLOG("ipaddr[$IPADDR]: !isset");return false;}
		$CurrentQuota=$array["ipaddr"][$IPADDR];
		$CurrentQuotaM=($CurrentQuota/1024)/1000;
		$quotaBytesM=($quotaBytes/1024)/1000;
		if($CurrentQuota<$quotaBytes){WLOG("Current $CurrentQuota - $CurrentQuotaM MB did not match rule of $quotaBytes - $quotaBytesM MB");return false;}
		if($CurrentQuota>=$quotaBytes){WLOG("Current $CurrentQuota - $CurrentQuotaM MB match rule of $quotaBytesM MB");return true;}
	}

	if($xtype=="uid"){
		if($LOGIN==null){WLOG("LOGIN is null");return false;}
		if(!preg_match("#$pattern#i", $LOGIN)){WLOG("$LOGIN did nor match rule $pattern");return false;}
		if(count($array["uid"])==0){WLOG("uid: not an array...");return false;}
		if(!isset($array["uid"][$LOGIN])){WLOG("uid[$LOGIN]: !isset");return false;}
		$CurrentQuota=$array["uid"][$LOGIN];
		$CurrentQuotaM=($CurrentQuota/1024)/1000;
		$quotaBytesM=($quotaBytes/1024)/1000;
		if($CurrentQuota<$quotaBytes){WLOG("Current $CurrentQuota - $CurrentQuotaM MB did not match rule of $quotaBytes - $quotaBytesM MB");return false;}
		if($CurrentQuota>=$quotaBytes){WLOG("Current $CurrentQuota - $CurrentQuotaM MB match rule of $quotaBytesM MB");return true;}
	}	
	
	if($xtype=="hostname"){
		if($HOST==null){WLOG("HOST is null");return false;}
		if(!preg_match("#$pattern#i", $HOST)){WLOG("$HOST did nor match rule $pattern");return false;}
		if(count($array["hostname"])==0){WLOG("hostname: not an array...");return false;}
		if(!isset($array["hostname"][$HOST])){WLOG("hostname[$LOGIN]: !isset");return false;}
		$CurrentQuota=$array["hostname"][$HOST];
		$CurrentQuotaM=($CurrentQuota/1024)/1000;
		$quotaBytesM=($quotaBytes/1024)/1000;
		if($CurrentQuota<$quotaBytes){WLOG("Current $CurrentQuota - $CurrentQuotaM MB did not match rule of $quotaBytes - $quotaBytesM MB");return false;}
		if($CurrentQuota>=$quotaBytes){WLOG("Current $CurrentQuota - $CurrentQuotaM MB match rule of $quotaBytesM MB");return true;}
	}	
	
	if($xtype=="MAC"){
		if($MAC==null){WLOG("MAC is null");return false;}
		if(!preg_match("#$pattern#i", $MAC)){WLOG("$MAC did nor match rule $pattern");return false;}
		if(count($array["MAC"])==0){WLOG("MAC: not an array...");return false;}
		if(!isset($array["MAC"][$MAC])){WLOG("MAC[$MAC]: !isset");return false;}
		$CurrentQuota=$array["MAC"][$MAC];
		$CurrentQuotaM=($CurrentQuota/1024)/1000;
		$quotaBytesM=($quotaBytes/1024)/1000;
		if($CurrentQuota<$quotaBytes){WLOG("Current $CurrentQuota - $CurrentQuotaM MB did not match rule of $quotaBytes - $quotaBytesM MB");return false;}
		if($CurrentQuota>=$quotaBytes){WLOG("Current $CurrentQuota - $CurrentQuotaM MB match rule of $quotaBytesM MB");return true;}
	}		
	
	
}

function CheckQuota_hour($infos,$xtype,$pattern,$quotaBytes){
	
	$IPADDR=$infos["IPADDR"];
	$MAC=$infos["MAC"];
	$HOST=$infos["HOST"];
	$LOGIN=$infos["LOGIN"];
	
	
	$array=unserialize(@file_get_contents("/etc/squid3/squid.quotasH.ini"));
	$pattern=str_replace(".", "\.", $pattern);
	$pattern=str_replace("*", ".*?", $pattern);

	if($xtype=="ipaddr"){
		if($IPADDR==null){WLOG("IPADDR is null");return false;}
		if(!preg_match("#$pattern#i", $IPADDR)){WLOG("$IPADDR did nor match rule $pattern");return false;}
		if(count($array["ipaddr"])==0){WLOG("ipaddr: not an array...");return false;}
		if(!isset($array["ipaddr"][$IPADDR])){WLOG("ipaddr[$IPADDR]: !isset");return false;}
		$CurrentQuota=$array["ipaddr"][$IPADDR];
		$CurrentQuotaM=($CurrentQuota/1024)/1000;
		$quotaBytesM=($quotaBytes/1024)/1000;
		if($CurrentQuota<$quotaBytes){WLOG("Current $CurrentQuota - $CurrentQuotaM MB did not match rule of $quotaBytes - $quotaBytesM MB");return false;}
		if($CurrentQuota>=$quotaBytes){WLOG("Current $CurrentQuota - $CurrentQuotaM MB match rule of $quotaBytesM MB");return true;}
	}

	if($xtype=="uid"){
		if($LOGIN==null){WLOG("LOGIN is null");return false;}
		if(!preg_match("#$pattern#i", $LOGIN)){WLOG("$LOGIN did nor match rule $pattern");return false;}
		if(count($array["uid"])==0){WLOG("uid: not an array...");return false;}
		if(!isset($array["uid"][$LOGIN])){WLOG("uid[$LOGIN]: !isset");return false;}
		$CurrentQuota=$array["uid"][$LOGIN];
		$CurrentQuotaM=($CurrentQuota/1024)/1000;
		$quotaBytesM=($quotaBytes/1024)/1000;
		if($CurrentQuota<$quotaBytes){WLOG("Current $CurrentQuota - $CurrentQuotaM MB did not match rule of $quotaBytes - $quotaBytesM MB");return false;}
		if($CurrentQuota>=$quotaBytes){WLOG("Current $CurrentQuota - $CurrentQuotaM MB match rule of $quotaBytesM MB");return true;}
	}	
	
	if($xtype=="hostname"){
		if($HOST==null){WLOG("HOST is null");return false;}
		if(!preg_match("#$pattern#i", $HOST)){WLOG("$HOST did nor match rule $pattern");return false;}
		if(count($array["hostname"])==0){WLOG("hostname: not an array...");return false;}
		if(!isset($array["hostname"][$HOST])){WLOG("hostname[$LOGIN]: !isset");return false;}
		$CurrentQuota=$array["hostname"][$HOST];
		$CurrentQuotaM=($CurrentQuota/1024)/1000;
		$quotaBytesM=($quotaBytes/1024)/1000;
		if($CurrentQuota<$quotaBytes){WLOG("Current $CurrentQuota - $CurrentQuotaM MB did not match rule of $quotaBytes - $quotaBytesM MB");return false;}
		if($CurrentQuota>=$quotaBytes){WLOG("Current $CurrentQuota - $CurrentQuotaM MB match rule of $quotaBytesM MB");return true;}
	}	
	
	if($xtype=="MAC"){
		if($MAC==null){WLOG("MAC is null");return false;}
		if(!preg_match("#$pattern#i", $MAC)){WLOG("$MAC did nor match rule $pattern");return false;}
		if(count($array["MAC"])==0){WLOG("MAC: not an array...");return false;}
		if(!isset($array["MAC"][$MAC])){WLOG("MAC[$MAC]: !isset");return false;}
		$CurrentQuota=$array["MAC"][$MAC];
		$CurrentQuotaM=($CurrentQuota/1024)/1000;
		$quotaBytesM=($quotaBytes/1024)/1000;
		if($CurrentQuota<$quotaBytes){WLOG("Current $CurrentQuota - $CurrentQuotaM MB did not match rule of $quotaBytes - $quotaBytesM MB");return false;}
		if($CurrentQuota>=$quotaBytes){WLOG("Current $CurrentQuota - $CurrentQuotaM MB match rule of $quotaBytesM MB");return true;}
	}	

	
	
}

function SplasHCheckAuth($array){
	if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("curl_init()");}
	$ch = curl_init();
	
	$LOGIN=$array["LOGIN"];
	$IPADDR=$array["IPADDR"];
	$MAC=$array["MAC"];
	$HOST=$array["HOST"];
	$md5key=md5("$LOGIN$IPADDR$MAC$HOST");	
	
	$params="?checks=".base64_encode(serialize($array));
	curl_setopt($ch, CURLOPT_INTERFACE,"127.0.0.1");
	curl_setopt($ch, CURLOPT_URL, $GLOBALS["SplashScreenURI"].$params);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:10.0) Gecko/20100101 Firefox/10.0");
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Pragma: no-cache", "Cache-Control: no-cache",'Expect:'));
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	
	
	if($GLOBALS["DEBUG_LEVEL"]>2){WLOG("curl_exec()-> ".$GLOBALS["SplashScreenURI"].$params);}
	$data=curl_exec($ch);
	$errno=curl_errno($ch);
	
	if($errno>0){
		if($GLOBALS["DEBUG_LEVEL"]>0){WLOG("Error $errno");}
		return;
	}
	
	if($GLOBALS["DEBUG_LEVEL"]>0){WLOG("{$GLOBALS["SplashScreenURI"]} Error:$errno $data");}
	
	if(preg_match("#<OK>uid=(.*?)</OK>#is", $data,$re)){
		$time=time();
		WLOG("{$re[1]}: $md5key return {$re[1]} -> Cache time:$time");
		$GLOBALS["SESSIONS"][$md5key]["TIME"]=$time;
		$GLOBALS["SESSIONS"][$md5key]["uid"]=$re[1];
		return $re[1];
	}
	
	
	curl_close($ch);
}



function WLOG($text=null){
	if(!isset($GLOBALS["F"])){$GLOBALS["F"] = @fopen("/var/log/squid/external-acl.log", 'a');}
	$trace=@debug_backtrace();
	if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
	$date=@date("Y-m-d H:i:s");
   	if (is_file("/var/log/squid/external-acl.log")) { 
   		$size=@filesize("/var/log/squid/external-acl.log");
   		if($size>1000000){
   			@fclose($GLOBALS["F"]);
   			unlink("/var/log/squid/external-acl.log");
   			$GLOBALS["F"] = @fopen("/var/log/squid/external-acl.log", 'a');
   		}
   		
   		
   	}
	
	
	@fwrite($GLOBALS["F"], "$date ".basename(__FILE__)."[{$GLOBALS["PID"]}]: $text $called\n");
}

function uriToHost($uri){
	if(count($GLOBALS["uriToHost"])>20000){$GLOBALS["uriToHost"]=array();}
	if(isset($GLOBALS["uriToHost"][$uri])){return $GLOBALS["uriToHost"][$uri];}
	$URLAR=parse_url($uri);
	if(isset($URLAR["host"])){$sitename=$URLAR["host"];}
	if(preg_match("#^www\.(.*?)#", $sitename,$re)){$sitename=$re[1];}
	if(preg_match("#(.*?):[0-9]+#", $sitename)){$sitename=$re[1];}
	$GLOBALS["uriToHost"][$uri]=$sitename;
	return $sitename;
	
}
function GetComputerName($ip){
		
		return $ip;
		}
function GetMacFromIP($ipaddr){
		$ipaddr=trim($ipaddr);
		$ttl=date('YmdH');
		if(count($GLOBALS["CACHEARP"])>3){unset($GLOBALS["CACHEARP"]);}
		if(isset($GLOBALS["CACHEARP"][$ttl][$ipaddr])){return $GLOBALS["CACHEARP"][$ttl][$ipaddr];}
		if(!isset($GLOBALS["SBIN_ARP"])){$GLOBALS["SBIN_ARP"]=find_program("arp");}
		if(!isset($GLOBALS["SBIN_ARPING"])){$GLOBALS["SBIN_ARPING"]=find_program("arping");}
		
		if(strlen($GLOBALS["SBIN_ARPING"])>3){
			$cmd="{$GLOBALS["SBIN_ARPING"]} $ipaddr -c 1 -r 2>&1";
			exec($cmd,$results);
			while (list ($num, $line) = each ($results)){
				if(preg_match("#^([0-9a-zA-Z\:]+)#", $line,$re)){
					$GLOBALS["CACHEARP"][$ttl][$ipaddr]=$re[1];
					return $GLOBALS["CACHEARP"][$ttl][$ipaddr];
				}
			}
		}
		
		
		$results=array();
			
		if(strlen($GLOBALS["SBIN_ARP"])<4){return;}
		if(!isset($GLOBALS["SBIN_PING"])){$GLOBALS["SBIN_PING"]=find_program("ping");}
		if(!isset($GLOBALS["SBIN_NOHUP"])){$GLOBALS["SBIN_NOHUP"]=find_program("nohup");}
		
		$cmd="{$GLOBALS["SBIN_ARP"]} -n \"$ipaddr\" 2>&1";
		WLOG($cmd);
		exec($cmd,$results);
		while (list ($num, $line) = each ($results)){
			if(preg_match("#^[0-9\.]+\s+.+?\s+([0-9a-z\:]+)#", $line,$re)){
				if($re[1]=="no"){continue;}
				$GLOBALS["CACHEARP"][$ttl][$ipaddr]=$re[1];
				return $GLOBALS["CACHEARP"][$ttl][$ipaddr];
			}
			
		}
		
		if(!isset($GLOBALS["PINGEDHOSTS"][$ipaddr])){
			shell_exec("{$GLOBALS["SBIN_NOHUP"]} {$GLOBALS["SBIN_PING"]} $ipaddr -c 3 >/dev/null 2>&1 &");
			$GLOBALS["PINGEDHOSTS"][$ipaddr]=true;
		}
			
		
	}
function find_program($strProgram) {
	  $key=md5($strProgram);
	  if(isset($GLOBALS["find_program"][$key])){return $GLOBALS["find_program"][$key];}
	  $value=trim(internal_find_program($strProgram));
	  $GLOBALS["find_program"][$key]=$value;
      return $value;
}
function internal_find_program($strProgram){
	  global $addpaths;	
	  $arrPath = array('/bin', '/sbin', '/usr/bin', '/usr/sbin', '/usr/local/bin', 
	  '/usr/local/sbin',
	  '/usr/kerberos/bin',
	  
	  );
	  
	  if (function_exists("is_executable")) {
	    foreach($arrPath as $strPath) {
	      $strProgrammpath = $strPath . "/" . $strProgram;
	      if (is_executable($strProgrammpath)) {
	      	  return $strProgrammpath;
	      }
	    }
	  } else {
	   	return strpos($strProgram, '.exe');
	  }
	}	
?>
