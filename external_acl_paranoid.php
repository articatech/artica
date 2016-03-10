#!/usr/bin/php -q
<?php
//ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
error_reporting(0);
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
$GLOBALS["SplashScreenURI"]=null;
$GLOBALS["PID"]=getmypid();
$GLOBALS["STARTIME"]=time();
$GLOBALS["MACTUIDONLY"]=false;
$GLOBALS["uriToHost"]=array();
$GLOBALS["SESSION_TIME"]=array();
$GLOBALS["SQLCACHE"]["TIME"]=time();
$GLOBALS["DEBUG_LEVEL"]=2;
$GLOBALS["PARAMS"]=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidQuotasParams")));
if(!isset($GLOBALS["PARAMS"]["CACHE_TIME"])){$GLOBALS["PARAMS"]["CACHE_TIME"]=360;}
if(!is_numeric($GLOBALS["PARAMS"]["CACHE_TIME"])){$GLOBALS["PARAMS"]["CACHE_TIME"]=360;}
if(!is_numeric( $GLOBALS["DEBUG_LEVEL"])){ $GLOBALS["DEBUG_LEVEL"]=0;}
//$GLOBALS["DEBUG_LEVEL"]=2;
if($GLOBALS["DEBUG_LEVEL"]>0){WLOG("Initialize library");}
$max_execution_time=ini_get('max_execution_time'); 
WLOG("Starting... v1.1 Log level:{$GLOBALS["DEBUG_LEVEL"]}; max_execution_time:$max_execution_time argv[1]={$argv[1]} session-time={$GLOBALS["SESSION_TIME"]}");
if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("Starting loop...");}
  
  
while (!feof(STDIN)) {
	 $url = trim(fgets(STDIN));
	 
	 if($url==null){continue;}
	 
	 if($GLOBALS["DEBUG_LEVEL"]>1){WLOG($url);}
	 	
	 $array=parseURL($url);
	 if($GLOBALS["DEBUG_LEVEL"]>1){WLOG($url." str:".strlen($url)." bytes LOGIN:{$array["LOGIN"]},IPADDR:{$array["IPADDR"]} MAC:{$array["MAC"]} DOMAIN:{$array["DOMAIN"]}");}
	 	
	 if(isset($GLOBALS["CHANNEL"])){
	 	if(is_numeric($GLOBALS["CHANNEL"])){
	 		if($GLOBALS["CHANNEL"]>0){
	 			$prefix_channel="{$GLOBALS["CHANNEL"]} ";
	 		}
	 	}
	 }
	 	
	 	
	if($array["IPADDR"]=="127.0.0.1"){
		if(trim($array["LOGIN"])==null){
	 		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("LOOP: 127.0.0.1 return always true...");}
	 		fwrite(STDOUT, "{$prefix_channel}OK\n");
	 		continue;
		}
	}
		 

	if(!CheckRules($array)){
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("LOOP: Nothing to do ");}
		fwrite(STDOUT, "{$prefix_channel}ERR\n");
	 	continue;
	 }

	 if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("LOOP: OK [BLOCK]");}
	 fwrite(STDOUT, "{$prefix_channel}OK\n");
	 continue;
	
	
}


$distanceInSeconds = round(abs(time() - $GLOBALS["STARTIME"]));
$distanceInMinutes = round($distanceInSeconds / 60);
WLOG("v2013-28-09:". basename(__FILE__)." die after ({$distanceInSeconds}s/about {$distanceInMinutes}mn)");




function parseURL($url){
	$uri=null;
	if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("parseURL():: Analyze [$url]");}
	
	
	$MAIN=explode(" ",$url);
		
	if(is_numeric($MAIN[0])){
		$GLOBALS["CHANNEL"]=$MAIN[0];
		
		$LOGIN2=trim($MAIN[1]);
		$IPADDR=trim($MAIN[2]);
		$MAC=trim($MAIN[3]);
		$IPADDR2=trim($MAIN[4]);
		$DOMAIN=trim($MAIN[5]);
	}else{
		
		$LOGIN2=trim($MAIN[0]);
		$IPADDR=trim($MAIN[1]);
		$MAC=trim($MAIN[2]);
		$IPADDR2=trim($MAIN[3]);
		$DOMAIN=trim($MAIN[4]);
		
	}
	
	$LOGIN=null;
	if($LOGIN2=="-"){$LOGIN2=null;}
	if($IPADDR2=="-"){$IPADDR2=null;}
	if($MAC=="-"){$MAC=null;}
	
	
	if($LOGIN2<>null){
		$LOGIN=$LOGIN2;
	}
	
	if($IPADDR2<>null){$IPADDR=$IPADDR2;}
	if(preg_match("#(.+?):([0-9]+)#", $DOMAIN,$re)){$DOMAIN=$re[1];}
	
	$GLOBALS["USERID"]=$LOGIN;
	$GLOBALS["IPADDR"]=$IPADDR;
	$GLOBALS["MAC"]=$MAC;
	$GLOBALS["DOMAIN"]=$DOMAIN;

	$ARRAY["USERID"]=$LOGIN;
	$ARRAY["IPADDR"]=$IPADDR;
	$ARRAY["MAC"]=$MAC;
	$ARRAY["DOMAIN"]=trim(strtolower($DOMAIN));
	
	return $ARRAY;
	
}



function WLOG($text=null){
	
	$trace=@debug_backtrace();
	if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
	$date=@date("Y-m-d H:i:s");
   	if (is_file("/var/log/squid/paranoid.log")) { 
   		$size=@filesize("/var/log/squid/paranoid.log");
   		if($size>1000000){unlink("/var/log/squid/paranoid.log");}
   	}
	
   $F= @fopen("/var/log/squid/paranoid.log", 'a');
   @fwrite($F, "$date ".basename(__FILE__)."[{$GLOBALS["PID"]}]: $text $called\n");
   @fclose($F);
}
function CheckRules($ARRAY){
	
	$IPSRC=$ARRAY["IPADDR"];
	$DOMAIN=$ARRAY["DOMAIN"];
	$fam=new squid_familysite();
	$FAMILY=$fam->GetFamilySites($DOMAIN);
	if($FAMILY=="articatech.net"){return false;}
	if($FAMILY=="artica.fr"){return false;}
	if($IPSRC=="127.0.0.1"){return false;}
	
	$MAIN=LoadRules();
	
	if(isset($MAIN["IPSRC"])){
		if(isset($MAIN["IPSRC"][$IPSRC])){
			if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("CheckRules()::[BLOCK]: $IPSRC");}
			return true;
		}
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("CheckRules()::[SKIP]: IP:$IPSRC");}
		
	}
	
	if(isset($MAIN["DOMS"])){
		if(isset($MAIN["DOMS"][$DOMAIN])){
			if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("CheckRules()::[BLOCK]: $DOMAIN");}
			return true;
		}
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("CheckRules()::[SKIP]: DOM:$DOMAIN");}
		
		if(isset($MAIN["DOMS"][$FAMILY])){
			if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("CheckRules()::[BLOCK]: $FAMILY");}
			return true;
		}		
		
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("CheckRules()::[SKIP]: DOM:$FAMILY");}
	}
	
	
	if(isset($MAIN["IPDOM"])){
		
		
		
		if(!isset($MAIN["IPDOM"][$IPSRC])){
			if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("CheckRules()::[SKIP]: $IPSRC FOR $IPSRC/FAMILY");}
			return false;
		}
		
		
		if(!isset($MAIN["IPDOM"][$IPSRC][$FAMILY])){
			if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("CheckRules()::[SKIP]: $IPSRC FOR $IPSRC/$FAMILY");}
			return false;
			
		}
		
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("CheckRules()::[BLOCK]: $IPSRC/$FAMILY");}
		return true;
		
	}
	
	
}

function LoadRules(){
	if(!is_file("/etc/squid3/paranoid.db")){return array();}
	
	if(!isset($GLOBALS["PARANOID_RULES"])){
		WLOG("LoadRules()::Loading new rules /etc/squid3/paranoid.db [".__LINE__."]");
		$GLOBALS["PARANOID_RULES"]=unserialize(@file_get_contents("/etc/squid3/paranoid.db"));
		$GLOBALS["PARANOID_RULES"]["FFMTIMES"]=filemtime("/etc/squid3/paranoid.db");
		$GLOBALS["PARANOID_RULES"]["FFMTCOUNT"]=0;
		return $GLOBALS["PARANOID_RULES"];
	}
	
	if($GLOBALS["PARANOID_RULES"]["FFMTCOUNT"]>5){
		$ffm=filemtime("/etc/squid3/paranoid.db");
		if($ffm<>$GLOBALS["PARANOID_RULES"]["FFMTIMES"]){
			WLOG("LoadRules()::Loading new rules /etc/squid3/paranoid.db [".__LINE__."]");
			$GLOBALS["PARANOID_RULES"]=unserialize(@file_get_contents("/etc/squid3/paranoid.db"));
			$GLOBALS["PARANOID_RULES"]["FFMTIMES"]=filemtime("/etc/squid3/paranoid.db");
			$GLOBALS["PARANOID_RULES"]["FFMTCOUNT"]=0;
			return $GLOBALS["PARANOID_RULES"];
		}
		$GLOBALS["PARANOID_RULES"]["FFMTCOUNT"]=0;
	}
	$GLOBALS["PARANOID_RULES"]["FFMTCOUNT"]=$GLOBALS["PARANOID_RULES"]["FFMTCOUNT"]+1;
	return $GLOBALS["PARANOID_RULES"];
	
	
}

	
?>
