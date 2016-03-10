#!/usr/bin/php -q
<?php
$GLOBALS["DEBUG"]=false;
//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
error_reporting(0);
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__) ."/framework/class.unix.inc");



$GLOBALS["MYPID"]=getmypid();

WLOG("Starting PID:{$GLOBALS["MYPID"]}");
$c=0;
$DCOUNT=0;
$fam=new squid_familysite();
while (!feof(STDIN)) {
	$ARRAY=array();
	$data = trim(fgets(STDIN));
	if($data==null){continue;}
	if(strpos($data, "cache_object://")>0){fwrite(STDOUT, "ERR\n");continue;}
	
	$result=false;
	$tr=explode(" ",$data);
	
	while (list($index,$value)=each($tr)){
		if($index==5){continue;}
		$tr[$index]=trim($value);
		$tr[$index]=str_replace("%25", "", $tr[$index]);
		$tr[$index]=str_replace("%20", "", $tr[$index]);
		if($tr[$index]=="-"){$tr[$index]=null;}
	}
	if(intval($tr[8])==0){fwrite(STDOUT, "ERR\n");continue;}

	$member=trim($tr[0]);
	$EXT_USER=trim($tr[1]);
	$EXT_USER=str_replace("%20", " ", $EXT_USER);
	
	
	$ARRAY["UID"]=$member;
	$EXT_USER=$tr[1];
	$ARRAY["IPADDR"]=$tr[2];
	$ARRAY["MAC"]=$tr[3];
	$X_FORWARDED=$tr[4];
	$URI=$tr[5];
	$ARRAY["SIZE"]=intval($tr[8]);
	if($ARRAY["MAC"]=="00:00:00:00:00:00"){$ARRAY["MAC"]=null;}
	if($ARRAY["UID"]==null){$ARRAY["UID"]=$EXT_USER;}
	
	if(preg_match("#^(.+?);#", $tr[6],$re)){$tr[6]=$re[1];}
	
	
	$ARRAY["CONTENT_TYPE"]=$tr[6];
	$ARRAY["CONTENT_DISPOSITION"]=$tr[7];

	
	$MAIN_WEB=parse_url($URI);
	if($MAIN_WEB["host"]==null){fwrite(STDOUT, "ERR\n");continue;}
	
	
	$ARRAY["HOST"]=$fam->GetFamilySites($MAIN_WEB["host"]);
	$ARRAY["path"]=$MAIN_WEB["path"];
	$ARRAY["query"]=$MAIN_WEB["query"];

	if($ARRAY["path"]=="/ufdbguardd.php"){fwrite(STDOUT, "ERR\n");continue;}
	
	SEND_LOGS($ARRAY);

	//WLOG("{$ARRAY["UID"]} {$ARRAY["MAC"]} {$ARRAY["IPADDR"]} {$ARRAY["HOST"]} {$ARRAY["CONTENT_TYPE"]} {$ARRAY["CONTENT_DISPOSITION"]} {$ARRAY["SIZE"]}");
	
	$c++;
	$DCOUNT++;
	
	
	
	
	if($c>500){
		WLOG("$DCOUNT requests...");
		$c=0;
	}
	
	if(!$result){fwrite(STDOUT, "ERR\n");continue;}
	fwrite(STDOUT, "OK\n");
	
	
}
CHUNK();


WLOG("Stopping PID:{$GLOBALS["MYPID"]} After $DCOUNT events");
	
function SEND_LOGS($ARRAY){
	
	if(!isset($GLOBALS["XTIMECACHE"])){$GLOBALS["XTIMECACHE"]=time();}
	
	$UID=$ARRAY["UID"];
	$IPADDR=$ARRAY["IPADDR"];
	$MAC=$ARRAY["MAC"];
	$CONTENT_TYPE=$ARRAY["CONTENT_TYPE"];
	$HOST=$ARRAY["HOST"];
	$SIZE=$ARRAY["SIZE"];
	$keyMD5=md5("$UID$IPADDR$MAC$CONTENT_TYPE$HOST");
	if(tool_time_sec($GLOBALS["XTIMECACHE"])>10){CHUNK();}
	
	if(!isset($GLOBALS["LOGS"][$keyMD5])){
		$GLOBALS["LOGS"][$keyMD5]["TIME"]=time();
		$GLOBALS["LOGS"][$keyMD5]["CONTENT_TYPE"]=$CONTENT_TYPE;
		$GLOBALS["LOGS"][$keyMD5]["HOST"]=$HOST;
		$GLOBALS["LOGS"][$keyMD5]["MAC"]=$MAC;
		$GLOBALS["LOGS"][$keyMD5]["IPADDR"]=$IPADDR;
		$GLOBALS["LOGS"][$keyMD5]["UID"]=$UID;
		$GLOBALS["LOGS"][$keyMD5]["HIT"]=1;
		$GLOBALS["LOGS"][$keyMD5]["SIZE"]=intval($SIZE);
		return;
	}
	
	
	$GLOBALS["LOGS"][$keyMD5]["SIZE"]=$GLOBALS["LOGS"][$keyMD5]["SIZE"]+$SIZE;
	$GLOBALS["LOGS"][$keyMD5]["HIT"]=$GLOBALS["LOGS"][$keyMD5]["HIT"]+1;
	$GLOBALS["LOGS"][$keyMD5]["TIME"]=time();
	
	
	
	
	
	
}

function CHUNK(){
	
	$f = @fopen("/home/artica/squid/realtime-events/VOLUME", 'a');
	
	
	while (list($index,$ARRAY)=each($GLOBALS["LOGS"])){
		$UID=$ARRAY["UID"];
		$IPADDR=$ARRAY["IPADDR"];
		$MAC=$ARRAY["MAC"];
		$CONTENT_TYPE=$ARRAY["CONTENT_TYPE"];
		$HOST=$ARRAY["HOST"];
		$SIZE=intval($ARRAY["SIZE"]);
		$HITS=intval($ARRAY["HIT"]);
		$TIME=$ARRAY["TIME"];
		@fwrite($f, "$TIME:::$UID:::$IPADDR:::$MAC:::$CONTENT_TYPE:::$HOST:::$HITS:::$SIZE\n");
		
		
	}
	
	$GLOBALS["LOGS"]=array();
	$GLOBALS["XTIMECACHE"]=time();
	@fclose($f);
	
	
}



function tool_time_sec($last_time){
	if($last_time==0){return 0;}
	$data1 = $last_time;
	$data2 = time();
	$difference = ($data2 - $data1);
	return $difference;
}
	
function WLOG($text=null){
	$trace=@debug_backtrace();
	$filename="/var/log/squid/acl_response.log";
	if(isset($trace[0])){$called=" called by ". basename($trace[0]["file"])." {$trace[0]["function"]}() line {$trace[0]["line"]}";}
	$date=@date("Y-m-d H:i:s");
	$mem=_get_memory_usage_159();
	
	
	if (is_file($filename)) {
		$size=@filesize($filename);
		if($size>1000000){ unlink($filename); }
	}
	
	
	$f = @fopen($filename, 'a');
	
	@fwrite($f, "$date [{$GLOBALS["MYPID"]}]: $text $called - process Memory:$mem\n");
	@fclose($f);
}

function _get_memory_usage_159() {
	$mem_usage = memory_get_usage(true);
	if ($mem_usage < 1024){return $mem_usage." bytes";}
	if ($mem_usage < 1048576){return round($mem_usage/1024,2)." kilobytes";}
	return round($mem_usage/1048576,2)." megabytes";
}

function isMatches($sitename){
	$sitename=trim(strtolower($sitename));
	if(isset($GLOBALS["isMatches"][$sitename])){return $GLOBALS["isMatches"][$sitename];}
	reset($GLOBALS["RULES"]);
	while (list($regex,$none)=each($GLOBALS["RULES"])){
		if(preg_match("#$regex#i", $sitename)){
			WLOG("isMatches '$sitename' -> $regex [".__LINE__."]");
			$GLOBALS["isMatches"][$sitename]=true;
			return true;
		}
		
	}
	$GLOBALS["isMatches"][$sitename]=false;
	return false;
	
}	
	
function LOADRULES($id){
	$f=explode("\n",@file_get_contents("/etc/squid3/acls/container_{$id}.txt"));
	while (list($num,$val)=each($f)){
		$f=trim(strtolower($val));
		if($val==null){continue;}
		if(is_regex($val)){
			
			$GLOBALS["RULES"][$val]=true;continue;}
		$val=str_replace(".", "\.", $val);
		$val=str_replace("*", ".*?", $val);
		$GLOBALS["RULES"][$val]=true;
	}
	
	WLOG("Starting Group Number:{$id} ".count($GLOBALS["RULES"])." rules");
	
}

function is_regex($pattern){
	$f[]="{";
	$f[]="[";
	$f[]="+";
	$f[]="\\";
	$f[]="?";
	$f[]="$";
	$f[]=".*";
	
	while (list ($key, $val) = each ($f) ){
		if(strpos(" $pattern", $val)>0){return true;}
	}
	
	
}

function time_passed_min($StartTime=0,$EndTime=0){
	$difference = ($EndTime - $StartTime);
	$results=intval(round($difference/60));
	if($results<0){$results=1;}
	return $results;
}
