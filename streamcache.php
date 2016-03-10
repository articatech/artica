#!/usr/bin/php -q
<?php
$StreamCacheDebug=0;
$GLOBALS["PID"]=getmypid();
$GLOBALS["DEBUG"]=false;
$GLOBALS["COUNTLOGS"]=0;
$GLOBALS["USERSDB"]=array();
stream_set_timeout(STDIN, 86400);
$UfdbGuardEnabled=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableUfdbGuard"));
$GLOBALS["SquidGuardApachePort"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidGuardApachePort"));
if(!is_numeric($GLOBALS["SquidGuardApachePort"])){$GLOBALS["SquidGuardApachePort"]=9020;}
$GLOBALS["SquidGuardServerName"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidGuardServerName"));
$StreamCacheUsePopen=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/StreamCacheUsePopen"));
$StreamCacheTTLUri=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/StreamCacheTTLUri"));
$StreamCacheDebugTXT=intval(trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/StreamCacheDebug")));
$GLOBALS["StreamCacheYoutubeEnable"]=intval(trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/StreamCacheYoutubeEnable")));
if(!is_numeric($StreamCacheDebugTXT)){$StreamCacheDebug=0;}
if($StreamCacheDebugTXT==1){$GLOBALS["DEBUG"]=true;$StreamCacheDebug=1;}
$GLOBALS["UFDBGCLIENT"]=UfdbClientCommandLine();
if(!is_file("/etc/squid3/usersMacs.db")){WLOG("/etc/squid3/usersMacs.db no such file");}
$usersMacsDB=@file_get_contents("/etc/squid3/usersMacs.db");
if(is_file("/etc/squid3/usersMacs.db")){$GLOBALS["USERSDB"]=unserialize($usersMacsDB);}
if(!is_numeric($StreamCacheTTLUri)){$StreamCacheTTLUri=30;}
if(!is_numeric($StreamCacheUsePopen)){$StreamCacheUsePopen=1;}
$GLOBALS["StreamCacheTTLUri"]=$StreamCacheTTLUri;
DebugMacDBS();



$GLOBALS["DEBUG"]=true;
$handleUfdbGuard=false;
WLOG("Start PID {$GLOBALS["PID"]} UsersDB:". count($GLOBALS["USERSDB"])." StreamCacheYoutubeEnable:{$GLOBALS["StreamCacheYoutubeEnable"]}, StreamCacheDebug: $StreamCacheDebug ($StreamCacheDebugTXT), EnableUfdbGuard: $UfdbGuardEnabled/\"{$GLOBALS["UFDBGCLIENT"]}\"");

if($UfdbGuardEnabled==1){
	$descriptorspec = array(0 => array("pipe", "r"),1 => array("pipe", "w"),2 => array("file", "/var/log/squid/pipe-error-output.txt", "a"));	
	if($StreamCacheUsePopen==1){
		$process = proc_open ($GLOBALS["UFDBGCLIENT"], $descriptorspec, $pipes);
		if (!is_resource($process)) {WLOG("Running pipe /usr/bin/ufdbgclient  failed");$handleUfdbGuard=false;}
		if(is_resource($process)){WLOG("PIPE:$process: Running pipe on /usr/bin/ufdbgclient success");$handleUfdbGuard=true;}
	}
}



while ( $input = @fgets(STDIN) ) {
  // Split the output (space delimited) from squid into an array.
 
  $Source=$input;
  
  $KEY=md5(trim($Source));
  
  $GLOBALS["COUNTLOGS"]=$GLOBALS["COUNTLOGS"]+1;
  if($GLOBALS["COUNTLOGS"]>=100){$mem=round(((memory_get_usage()/1024)/1000),2);WLOG("Cache ".count($GLOBALS["CACHE"])." entries {$mem}MB");$GLOBALS["COUNTLOGS"]=0;}
  
  $input=@explode(" ",$input);
  $UriRequested=$input[0];
 if($GLOBALS["DEBUG"]){WLOG("SCAN -> ".@implode("|", $input));}
  
  
  
  
  
   
  if(strpos(" $UriRequested", "cache_object://")>0){SetCache($KEY,"$UriRequested\n");}

  
 if(IsInCache($KEY)){
 	print "{$GLOBALS["CACHE"][$KEY]["URI"]}";
    flush();
 }else{
 	 $GLOBALS["ECHOED"]=false;
	 $stop=false;
	 if($UfdbGuardEnabled==1){
	 	$UriRequested=trim(ufdbgclient($Source,$handleUfdbGuard,$pipes));
	  	if($UriRequested==null){$UriRequested=$input[0];}
		 if($GLOBALS["DEBUG"]){WLOG("{$UriRequested} after ufdbguard...");}
	  	if((strpos($UriRequested,"ufdbguardd.php")>0)){
	  		if($GLOBALS["DEBUG"]){WLOG("BLOCK -> $UriRequested +CRLF");}
	  		SetCache($KEY,"$UriRequested\n");
	  		print $GLOBALS["CACHE"][$KEY]["URI"]; //URL of my web server
			flush();
			$GLOBALS["ECHOED"]=true;
	     	$stop=true;
	  	}
	 }

	if(trim($UriRequested)==null){$UriRequested=$input[0];}
  	
	 
	  	
	// Youtube..............................  	
	if(!$stop){$stop=Youtbube($UriRequested);}
	
	
   	
   	 
   		
   if(!$GLOBALS["ECHOED"]){     
   		$logg=$UriRequested;
		if($GLOBALS["DEBUG"]){if(strlen($logg)>255){$logg=substr($logg, 0,255)."...";}  WLOG("NONE -> PROXY: `{$logg}` + CRLF");}
		SetCache($KEY,"$UriRequested\n");
		print $GLOBALS["CACHE"][$KEY]["URI"]; //URL of my web server
		flush();
   }

 }
}
  
  
if($handleUfdbGuard){
	WLOG("Close UfdBguard client...");
	if(is_resource($process)){
		WLOG("PIPE:$process: Close pipes...");
		@fclose($pipes[0]);
		@fclose($pipes[1]);
		@fclose($pipes[2]);
		WLOG("PIPE:$process: Close Process...$process");
		@proc_close($process);
	}else{
		WLOG("\$process is not a ressource...");
	}
}



WLOG("Die() PID {$GLOBALS["PID"]}");

function WLOG($text=null){
	$logFile="/var/log/squid/streamcache.log";
	$date=@date("Y-m-d H:i:s");
   	if (is_file($logFile)) { 
   		$size=filesize($logFile);
   		if($size>1000000){unlink($logFile);}
   	}
	
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$date [{$GLOBALS["PID"]}]: $text\n");
	@fclose($f);
}


function Youtbube($UriRequested){
	if($GLOBALS["StreamCacheYoutubeEnable"]<>1){return false;}
	if($GLOBALS["SquidGuardServerName"]==null){return false;}	
	if(!preg_match("#youtube#",$UriRequested)){return false;}
	if(strpos($UriRequested, "&artica-time-stamp-")>0){return false;}
	if((!preg_match("#.*youtube.*\/videoplayback.*#",$UriRequested)) OR (preg_match("#google\.#",$UriRequested))){return false;}
	$UriRequested=urlencode($UriRequested);
	if(preg_match("#http:\/\/(.+?)\/videoplayback\?#", $UriRequested,$re));
	WLOG("Stream Webserver: {$re[1]} -> {$GLOBALS["SquidGuardServerName"]}:{$GLOBALS["SquidGuardApachePort"]}");
	SetCache($KEY,"http://{$GLOBALS["SquidGuardServerName"]}:{$GLOBALS["SquidGuardApachePort"]}/streamget.php?url={$UriRequested}\n");
	print $GLOBALS["CACHE"][$KEY]["URI"]; //URL of my web server
	$GLOBALS["ECHOED"]=true;
	flush();
    return true;	
}


function UfdbClientCommandLine(){
	$binary="/usr/bin/ufdbgclient";
	$log="-l /var/log/squid";
	if(!is_file("/etc/artica-postfix/settings/Daemons/ufdbguardConfig")){
		WLOG("ufdbgclient:: /etc/artica-postfix/settings/Daemons/ufdbguardConfig no such file");
	}
	
 	$datas=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/ufdbguardConfig")));	
 	
 	if(!is_array($datas)){WLOG("ufdbgclient:: ufdbguardConfig is not an array");}
 	
	if(!isset($datas["UseRemoteUfdbguardService"])){$datas["UseRemoteUfdbguardService"]=0;}
	if(!isset($datas["remote_port"])){$datas["remote_port"]=3977;}
	if(!isset($datas["remote_server"])){$datas["remote_server"]=null;}
	if(!isset($datas["listen_addr"])){$datas["listen_addr"]="127.0.0.1";}
	if(!isset($datas["listen_port"])){$datas["listen_port"]="3977";}
	if(!is_numeric($datas["listen_port"])){$datas["listen_port"]="3977";}
	if(!is_numeric($datas["tcpsockets"])){$datas["tcpsockets"]=0;}			
	if(!isset($datas["tcpsockets"])){$datas["tcpsockets"]=0;}
	if(!is_numeric($datas["UseRemoteUfdbguardService"])){$datas["UseRemoteUfdbguardService"]=0;}
	if(!is_numeric($datas["remote_port"])){$datas["remote_port"]=3977;}
	if($datas["remote_port"]==null){$datas["UseRemoteUfdbguardService"]=0;}
	if($datas["listen_addr"]==null){$datas["listen_addr"]="127.0.0.1";}
	if($datas["listen_addr"]=="all"){$datas["listen_addr"]="127.0.0.1";}
	if($datas["UseRemoteUfdbguardService"]==1){
		$address="-S {$datas["remote_server"]} -p {$datas["remote_port"]} ";	
		return "$binary $address $log";
	}
	if($datas["tcpsockets"]==1){
		$address="-S {$datas["listen_addr"]} -p {$datas["listen_port"]} ";	
		return "$binary $address $log";		
	}	
	return "$binary $log";	
	
}

function ufdbgclient($full,$handleUfdbGuard,$pipes=null){
	$KEY=md5($full);
	$SourceFull=UserDBTranslate($full);
	$full=trim($full);
	if(IsInCache($KEY)){return $GLOBALS["CACHE"][$KEY]["URI"];}
	if($handleUfdbGuard){
		if($GLOBALS["DEBUG"]){WLOG("ufdbgclient:: PIPE(): Write \"$SourceFull\"");}
		fwrite($pipes[0], $SourceFull); 
		$output=array();
		$get= fgets($pipes[1], 1024);
		if($GLOBALS["DEBUG"]){WLOG("ufdbgclient:: PIPE(): receive \"$get");}
		if(strlen($get)>0){SetCache($KEY,$get);return $get;}
  	}
	
	
		
	
	$cmd="echo \"$full\"|{$GLOBALS["UFDBGCLIENT"]} 2>&1";
	if($GLOBALS["DEBUG"]){WLOG("ufdbgclient::`$cmd`");}
	exec($cmd,$results);
	if($GLOBALS["DEBUG"]){WLOG("ufdbgclient:: {$results[0]}");}
	SetCache($KEY,$results[0]);
	return $results[0];
	
}

function UserDBTranslate($full){
	if(count($GLOBALS["USERSDB"])==0){if($GLOBALS["DEBUG"]){WLOG("UserDBTranslate:: no database..");return $full;}}	
	$array=explode(" ", $full);
	 
	$ip=$array[1];
	$slash=strpos($ip, '/');
	if($slash>0){$ip=substr($ip, 0,$slash);}
	$MAC=GetMacFromIP(trim($ip));
	if($GLOBALS["DEBUG"]){WLOG("UserDBTranslate:: $ip = $MAC");}
	if($MAC==null){return $full;}
	if(!isset($GLOBALS["USERSDB"]["MACS"][$MAC])){
		if($GLOBALS["DEBUG"]){WLOG("UserDBTranslate:: `$MAC` no translation");}	
		return $full;
	}
	$uid=trim($GLOBALS["USERSDB"]["MACS"][$MAC]["UID"]);
	if($uid==null){return $full;}
	
	if($GLOBALS["DEBUG"]){WLOG("UserDBTranslate:: `$MAC` = `$uid`");}
	
	$full=str_replace("/- -", "/- $uid", $full);
	if($GLOBALS["DEBUG"]){WLOG("UserDBTranslate:: return $full");}
	return $full;
}

function DebugMacDBS(){
	if(!$GLOBALS["DEBUG"]){return;}
	$ARRT=$GLOBALS["USERSDB"]["MACS"];
	WLOG("DebugMacDBS: -> $ARRT");
	while (list ($mac, $array) = each ($ARRT)){
		$uid=$array["UID"];
		WLOG("DebugMacDBS: $mac = $uid");
	}
}

function GetMacFromIP($ipaddr){
	$ipaddr=trim($ipaddr);
	$ttl=date('YmdH');
	if(count($GLOBALS["CACHEARP"])>3){unset($GLOBALS["CACHEARP"]);}
	if(isset($GLOBALS["CACHEARP"][$ttl][$ipaddr])){return $GLOBALS["CACHEARP"][$ttl][$ipaddr];}
	
	if(!is_file("/usr/sbin/arp")){return null;}
	exec("/usr/sbin/arp -n \"$ipaddr\" 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#^[0-9\.]+\s+.+?\s+([0-9a-z\:]+)#", $line,$re)){
			if($re[1]=="no"){continue;}
			$GLOBALS["CACHEARP"][$ttl][$ipaddr]=$re[1];
			return $GLOBALS["CACHEARP"][$ttl][$ipaddr];
		}else{
			if($GLOBALS["DEBUG"]){WLOG("`$line` no match");}
		}
		
	}
	
	if(!isset($GLOBALS["PINGEDHOSTS"][$ipaddr])){
		if($GLOBALS["DEBUG"]){WLOG("GetMacFromIP: not found for $ipaddr -> send ping");}
		shell_exec("nohup ping $ipaddr -c 3 >/dev/null 2>&1 &");
		$GLOBALS["PINGEDHOSTS"][$ipaddr]=true;
	}
		
	
}

function SetCache($KEY,$UriDest){
	if(trim($KEY)==null){return;}
	$t=time();
	$GLOBALS["CACHE"][$KEY]["URI"]=$UriDest;
	$GLOBALS["CACHE"][$KEY]["time"]=time();
	if($GLOBALS["DEBUG"]){WLOG("SetCache() $KEY = $t");}
	
}

function IsInCache($KEY){
	
	if(!is_numeric($GLOBALS["StreamCacheTTLUri"])){$GLOBALS["StreamCacheTTLUri"]=30;}
 	if(isset($GLOBALS["CACHE"][$KEY]["URI"])){if(trim($GLOBALS["CACHE"][$KEY]["URI"])==null){unset($GLOBALS["CACHE"][$KEY]["URI"]);}}
 	if(!isset($GLOBALS["CACHE"][$KEY]["time"])){if($GLOBALS["DEBUG"]){WLOG("IsInCache() $KEY = FALSE");}return false;}
	$data1 = $GLOBALS["CACHE"][$KEY]["time"];
	$data2 = time();
	$difference = ($data2 - $data1); 
	if($GLOBALS["DEBUG"]){WLOG("IsInCache() $KEY = {$difference}s/{$GLOBALS["StreamCacheTTLUri"]}s");}
	if($difference>$GLOBALS["StreamCacheTTLUri"]){
		if($GLOBALS["DEBUG"]){WLOG("IsInCache() $KEY = FALSE");}
		unset($GLOBALS["CACHE"][$KEY]);return false;}	
	if($GLOBALS["DEBUG"]){WLOG("IsInCache() $KEY = TRUE");} 	
	return true;
 	
	
}



?>