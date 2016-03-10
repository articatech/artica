#!/usr/bin/php -q
<?php
$GLOBALS["DEBUG"]=false;
$GLOBALS["HERLPER_LOADED_BY_SQUID"]=true;
$GLOBALS["DBPATH"]="/var/log/squid/QUOTA_SIZE.db";
//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");

$GLOBALS["MYPID"]=getmypid();
WLOG("Starting PID:{$GLOBALS["MYPID"]}");




$DCOUNT=0;
while (!feof(STDIN)) {
	$url = trim(fgets(STDIN));
	if($url==null){
		if($GLOBALS["DEBUG"]){WLOG("$DCOUNT] SIZE_QUOTA::URL is null [".__LINE__."]");}
		continue;
	}
	$DCOUNT++;
	
	
	
	try {
		$result = SIZE_QUOTA($url);
	}
	catch (Exception $e) {
		$error=$e->getMessage();
		WLOG("$DCOUNT] SIZE_QUOTA::FATAL ERROR $error");
		$result=false;
	}
	
	if(!$result){
		if($GLOBALS["DEBUG"]){WLOG("$DCOUNT] SIZE_QUOTA::ERR");}
		fwrite(STDOUT, "ERR\n");
		continue;
	}

	if($GLOBALS["DEBUG"]){WLOG("$DCOUNT] SIZE_QUOTA::OK");}
	fwrite(STDOUT, "OK\n");
	
	
}



WLOG("Stopping PID:{$GLOBALS["MYPID"]} After $DCOUNT event(s) SAVED {$GLOBALS["DATABASE_ITEMS"]} items in database");
	
	
function BUILD_EVENTS($line){
	
	$filename="/var/log/squid/sizequota.log";
	
	if (is_file($filename)) {
		$size=@filesize($filename);
		if($size>1000000){ unlink($filename); }
	}
	
	$f = @fopen($filename, 'a');
	@fwrite($f, time().";$line\n");
	@fclose($f);
	
}

function WLOG($text=null){
	$trace=@debug_backtrace();
	$filename="/var/log/squid/acl_sizequota.log";
	if(isset($trace[0])){$called=" called by ". basename($trace[0]["file"])." {$trace[0]["function"]}() line {$trace[0]["line"]}";}
	$date=@date("Y-m-d H:i:s");
	$mem=_get_memory_usage_158();
	
	
	if (is_file($filename)) {
		$size=@filesize($filename);
		if($size>1000000){ unlink($filename); }
	}
	
	
	$f = @fopen($filename, 'a');
	
	@fwrite($f, "$date [{$GLOBALS["MYPID"]}]: $text $called - process Memory:$mem\n");
	@fclose($f);
}

function _get_memory_usage_158() {
	$mem_usage = memory_get_usage(true);
	if ($mem_usage < 1024){return $mem_usage." bytes";}
	if ($mem_usage < 1048576){return round($mem_usage/1024,2)." kilobytes";}
	return round($mem_usage/1048576,2)." megabytes";
}

function LOADING_RULES($gpid){
	
	if(isset($GLOBALS["ACL_RULES"][$gpid])){return;}
	
	$file="/etc/squid3/acls/size_gpid{$gpid}.acl";
	if(!is_file($file)){
		WLOG("LOADING_RULES::$file no such file! [".__LINE__."]");
		$GLOBALS["ACL_RULES"]=array();
		return;
	}
	$array=unserialize(@file_get_contents($file));
	$c=0;
	foreach($array as $line){
		
		if(preg_match("#max_day:.*?([0-9]+)#i", $line,$re)){
			if($GLOBALS["DEBUG"]){WLOG("LOADING_RULES::$c Max time = {$re[1]} minutes [".__LINE__."]");}
			$GLOBALS["ACL_RULES"][$gpid]["DAY"]=$re[1];
		}
		if(preg_match("#max_hour:.*?([0-9]+)#i", $line,$re)){
			if($GLOBALS["DEBUG"]){WLOG("LOADING_RULES::$c WAIT time = {$re[1]} minutes [".__LINE__."]");}
			$GLOBALS["ACL_RULES"][$gpid]["HOUR"]=$re[1];
		}	
		if(preg_match("#max_week:.*?([0-9]+)#i", $line,$re)){
			if($GLOBALS["DEBUG"]){WLOG("LOADING_RULES::$c WAIT time = {$re[1]} minutes [".__LINE__."]");}
			$GLOBALS["ACL_RULES"][$gpid]["WEEK"]=$re[1];
		}
		if(preg_match("#member_week:.*?([0-9]+)#i", $line,$re)){
			if($GLOBALS["DEBUG"]){WLOG("LOADING_RULES::$c WAIT time = {$re[1]} minutes [".__LINE__."]");}
			$GLOBALS["ACL_RULES"][$gpid]["MEMBER_WEEK"]=$re[1];
		}
		if(preg_match("#member_day:.*?([0-9]+)#i", $line,$re)){
			if($GLOBALS["DEBUG"]){WLOG("LOADING_RULES::$c WAIT time = {$re[1]} minutes [".__LINE__."]");}
			$GLOBALS["ACL_RULES"][$gpid]["MEMBER_DAY"]=$re[1];
		}		
		if(preg_match("#member_hour:.*?([0-9]+)#i", $line,$re)){
			if($GLOBALS["DEBUG"]){WLOG("LOADING_RULES::$c WAIT time = {$re[1]} minutes [".__LINE__."]");}
			$GLOBALS["ACL_RULES"][$gpid]["MEMBER_HOUR"]=$re[1];
		}	

		if(preg_match("#website_week:.*?([0-9]+)#i", $line,$re)){
			if($GLOBALS["DEBUG"]){WLOG("LOADING_RULES::$c WAIT time = {$re[1]} minutes [".__LINE__."]");}
			$GLOBALS["ACL_RULES"][$gpid]["WEBSITE_WEEK"]=$re[1];
		}
		if(preg_match("#website_day:.*?([0-9]+)#i", $line,$re)){
			if($GLOBALS["DEBUG"]){WLOG("LOADING_RULES::$c WAIT time = {$re[1]} minutes [".__LINE__."]");}
			$GLOBALS["ACL_RULES"][$gpid]["WEBSITE_DAY"]=$re[1];
		}
		if(preg_match("#website_hour:.*?([0-9]+)#i", $line,$re)){
			if($GLOBALS["DEBUG"]){WLOG("LOADING_RULES::$c WAIT time = {$re[1]} minutes [".__LINE__."]");}
			$GLOBALS["ACL_RULES"][$gpid]["WEBSITE_HOUR"]=$re[1];
		}
		
		
		
		if(preg_match("#category_hour:(.+?):.*?([0-9]+)#i", $line,$re)){
			$GLOBALS["ACL_RULES"][$gpid]["CATEGORIES_HOUR"][trim(strtolower($re[1]))]=$re[2];
		}
		if(preg_match("#category_day:(.+?):.*?([0-9]+)#i", $line,$re)){
			$GLOBALS["ACL_RULES"][$gpid]["CATEGORIES_DAY"][trim(strtolower($re[1]))]=$re[2];
		}
		if(preg_match("#category_week:(.+?):.*?([0-9]+)#i", $line,$re)){
			$GLOBALS["ACL_RULES"][$gpid]["CATEGORIES_WEEK"][trim(strtolower($re[1]))]=$re[2];
		}		
		
		$c++;
	}
	
	if(!isset($GLOBALS["ACL_RULES"][$gpid]["WEEK"])){$GLOBALS["ACL_RULES"][$gpid]["WEEK"]=0;}
	if(!isset($GLOBALS["ACL_RULES"][$gpid]["HOUR"])){$GLOBALS["ACL_RULES"][$gpid]["HOUR"]=0;}
	if(!isset($GLOBALS["ACL_RULES"][$gpid]["DAY"])){$GLOBALS["ACL_RULES"][$gpid]["DAY"]=0;}

	if(!isset($GLOBALS["ACL_RULES"][$gpid]["MEMBER_HOUR"])){$GLOBALS["ACL_RULES"][$gpid]["MEMBER_HOUR"]=0;}
	if(!isset($GLOBALS["ACL_RULES"][$gpid]["MEMBER_WEEK"])){$GLOBALS["ACL_RULES"][$gpid]["MEMBER_WEEK"]=0;}
	if(!isset($GLOBALS["ACL_RULES"][$gpid]["MEMBER_DAY"])){$GLOBALS["ACL_RULES"][$gpid]["MEMBER_DAY"]=0;}
	
	if(!isset($GLOBALS["ACL_RULES"][$gpid]["WEBSITE_HOUR"])){$GLOBALS["ACL_RULES"][$gpid]["WEBSITE_HOUR"]=0;}
	if(!isset($GLOBALS["ACL_RULES"][$gpid]["WEBSITE_WEEK"])){$GLOBALS["ACL_RULES"][$gpid]["WEBSITE_WEEK"]=0;}
	if(!isset($GLOBALS["ACL_RULES"][$gpid]["WEBSITE_HOUR"])){$GLOBALS["ACL_RULES"][$gpid]["WEBSITE_HOUR"]=0;}
	
}
	
	
function SIZE_QUOTA($url){
	
	if(trim($url)==null){if($GLOBALS["DEBUG"]){WLOG("SIZE_QUOTA::URL is null [".__LINE__."]"); return false; }}
	
	
	//- Group75 administrateur 192.168.1.9 00:26:b9:78:8f:0a - ttvpsy.psychologies.com ttvpsy.psychologies.com 75
	$MAIN=explode(" ",$url);
	$EXT_LOG=$MAIN[0];
	$MYGROUP=$MAIN[1];
	$USERNAME=$MAIN[2];
	$IPADDR=$MAIN[3];
	$MAC=$MAIN[4];
	$XFORWARD=trim($MAIN[5]);
	$WWW=$MAIN[6];
	$WWW_SRC=$WWW;
	$gpid=$MAIN[7];
	
	if($IPADDR=="127.0.0.1"){return false;}
	if($XFORWARD=="-"){$XFORWARD=null;}

	
	
	if(strpos($USERNAME, '$')>0){
		if(substr($USERNAME, strlen($USERNAME)-1,1)=="$"){
			$USERNAME=null;
		}
	}

	

	
	$USERNAME=str_replace("%20", " ", $USERNAME);
	$USERNAME=str_replace("%25", "-", $USERNAME);
	
	$IPADDR=str_replace("%25", "-", $IPADDR);
	$MAC=str_replace("%25", "-", $MAC);
	$XFORWARD=str_replace("%25", "-", $XFORWARD);
	if($XFORWARD=="-"){$XFORWARD=null;}
	if($MAC=="00:00:00:00:00:00"){$MAC=null;}
	if($MAC=="-"){$MAC=null;}
	if($USERNAME=="-"){$USERNAME=null;}
	
	$IPCalls=new IP();
	
	if($IPCalls->isIPAddress($XFORWARD)){$IPADDR=$XFORWARD;}
	
	if(preg_match("#(.+?):[0-9]+#", $WWW,$re)){$WWW=$re[1];}
	if(preg_match("#^www\.(.+)#", $WWW,$re)){$WWW=$re[1];}
	if(!class_exists("squid_familysite")){include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");}
	$fam=new squid_familysite();
	$WWW=$fam->GetFamilySites($WWW);
	$LOG_PREFIX="$WWW";
	
	if($GLOBALS["DEBUG"]){WLOG("$LOG_PREFIX: $WWW_SRC::GROUPID:$gpid; USERNAME:$USERNAME;MAC:$MAC; IPADDR:$IPADDR [".__LINE__."]");}
	

	LOADING_RULES($gpid);
	if(!isset($GLOBALS["ACL_RULES"][$gpid]["WEEK"])){$GLOBALS["ACL_RULES"][$gpid]["WEEK"]=0;}
	if(!isset($GLOBALS["ACL_RULES"][$gpid]["HOUR"])){$GLOBALS["ACL_RULES"][$gpid]["HOUR"]=0;}
	if(!isset($GLOBALS["ACL_RULES"][$gpid]["DAY"])){$GLOBALS["ACL_RULES"][$gpid]["DAY"]=0;}

	if(!isset($GLOBALS["ACL_RULES"][$gpid]["MEMBER_HOUR"])){$GLOBALS["ACL_RULES"][$gpid]["MEMBER_HOUR"]=0;}
	if(!isset($GLOBALS["ACL_RULES"][$gpid]["MEMBER_WEEK"])){$GLOBALS["ACL_RULES"][$gpid]["MEMBER_WEEK"]=0;}
	if(!isset($GLOBALS["ACL_RULES"][$gpid]["MEMBER_DAY"])){$GLOBALS["ACL_RULES"][$gpid]["MEMBER_DAY"]=0;}
	
	if(!isset($GLOBALS["ACL_RULES"][$gpid]["WEBSITE_HOUR"])){$GLOBALS["ACL_RULES"][$gpid]["WEBSITE_HOUR"]=0;}
	if(!isset($GLOBALS["ACL_RULES"][$gpid]["WEBSITE_WEEK"])){$GLOBALS["ACL_RULES"][$gpid]["WEBSITE_WEEK"]=0;}
	if(!isset($GLOBALS["ACL_RULES"][$gpid]["WEBSITE_HOUR"])){$GLOBALS["ACL_RULES"][$gpid]["WEBSITE_HOUR"]=0;}
	
	
	$MaxPerDay=intval($GLOBALS["ACL_RULES"][$gpid]["DAY"]);
	$MaxPerHour=intval($GLOBALS["ACL_RULES"][$gpid]["HOUR"]);
	$MaxPerWeek=intval($GLOBALS["ACL_RULES"][$gpid]["WEEK"]);
	
	$MEMBER_HOUR=intval($GLOBALS["ACL_RULES"][$gpid]["MEMBER_HOUR"]);
	$MEMBER_DAY=intval($GLOBALS["ACL_RULES"][$gpid]["MEMBER_DAY"]);
	$MEMBER_WEEK=intval($GLOBALS["ACL_RULES"][$gpid]["MEMBER_WEEK"]);
	
	
	$WEBSITE_HOUR=intval($GLOBALS["ACL_RULES"][$gpid]["WEBSITE_HOUR"]);
	$WEBSITE_DAY=intval($GLOBALS["ACL_RULES"][$gpid]["WEBSITE_DAY"]);
	$WEBSITE_WEEK=intval($GLOBALS["ACL_RULES"][$gpid]["WEBSITE_WEEK"]);
	
	if(CHECK_WEBSITE($WWW,$WEBSITE_HOUR,$WEBSITE_DAY,$WEBSITE_WEEK)){
		WLOG("$LOG_PREFIX: $WWW match size");
		return true;
	}
	
	if(isset($GLOBALS["ACL_RULES"][$gpid]["CATEGORIES_HOUR"])){
		if(CHECK_CATEGORY_HOUR($WWW_SRC,$gpid)){
			WLOG("$LOG_PREFIX: $WWW Hourly Category match size");
			return true;
		}
	}else{
		WLOG("$LOG_PREFIX: $gpid CATEGORIES_HOUR not set");
	}
	if(isset($GLOBALS["ACL_RULES"][$gpid]["CATEGORIES_DAY"])){
		if(CHECK_CATEGORY_DAY($WWW_SRC,$gpid)){
			WLOG("$LOG_PREFIX: $WWW Daily Category match size");
			return true;
		}
	
	}	
	if(isset($GLOBALS["ACL_RULES"][$gpid]["CATEGORIES_WEEK"])){
		if(CHECK_CATEGORY_WEEK($WWW_SRC,$gpid)){
			WLOG("$LOG_PREFIX: $WWW Weekly Category match size");
			return true;
		}
	
	}	

	if($USERNAME<>null){
		$CHECK_USER=true;
		if(CHECK_UID($WWW,"UID/$USERNAME",$MaxPerHour,$MaxPerDay,$MaxPerWeek)){
			WLOG("$LOG_PREFIX: $USERNAME $WWW match size");
			return true;
		}
		
		if(CHECK_MEMBER("UID/$USERNAME",$MEMBER_HOUR,$MEMBER_DAY,$MEMBER_WEEK)){
			WLOG("$LOG_PREFIX: $USERNAME match size");
			return true;
		}
		
	}
	
	if(!$CHECK_USER){
		if($MAC<>null){
			$CHECK_USER=true;
			if(CHECK_UID($WWW,"MAC/$MAC",$MaxPerHour,$MaxPerDay,$MaxPerWeek)){
				WLOG("$LOG_PREFIX: $MAC $WWW match size");
				return true;
			}
			
			if(CHECK_MEMBER("UID/$MAC",$MEMBER_HOUR,$MEMBER_DAY,$MEMBER_WEEK)){
				WLOG("$LOG_PREFIX: $USERNAME match size");
				return true;
			}
			
		}
	}
	
	if(!$CHECK_USER){
		if($IPADDR<>null){
			if(CHECK_UID($WWW,"IPADDR/$IPADDR",$MaxPerHour,$MaxPerDay,$MaxPerWeek)){
				WLOG("$LOG_PREFIX: $IPADDR $WWW match size");
				return true;
			}	
			
			if(CHECK_MEMBER("UID/$IPADDR",$MEMBER_HOUR,$MEMBER_DAY,$MEMBER_WEEK)){
				WLOG("$LOG_PREFIX: $USERNAME match size");
				return true;
			}
			
		}
	}
	

	return false;
}

function CHECK_CATEGORY_HOUR($WWW,$gpid){
	if(!isset($GLOBALS["ACL_RULES"][$gpid]["CATEGORIES_HOUR"])){return;}
	
	if($GLOBALS["DEBUG"]){WLOG("CATEGORIES CACHE: ".count($GLOBALS["CATEGORIES"])." items");}
	
	
	if(count($GLOBALS["CATEGORIES"])>1000){$GLOBALS["CATEGORIES"]=array();}
	
	if(!isset($GLOBALS["CATEGORIES"][$WWW])){
		$q=new mysql_catz();
		$GLOBALS["CATEGORIES"][$WWW]=trim(strtolower($q->GET_CATEGORIES($WWW)));
	}
	if($GLOBALS["CATEGORIES"][$WWW]==null){return;}
	$CATEGORY=$GLOBALS["CATEGORIES"][$WWW];
	if(!isset($GLOBALS["ACL_RULES"][$gpid]["CATEGORIES_HOUR"][$CATEGORY])){return;}
	$MaxSize=$GLOBALS["ACL_RULES"][$gpid]["CATEGORIES_HOUR"][$CATEGORY];
	
	$YEAR=date("Y");
	$MONTH=date("m");
	$DAY=date("d");
	$HOUR=date("H");
	$WEEK=date("W");
	
	$CATEGORY_FOUND=$GLOBALS["CATEGORIES"][$WWW];
	$CATEGORY_FOUND=str_replace("/", "_", $CATEGORY_FOUND);
	$filename="/home/squid/rttsize/$YEAR/$MONTH/$WEEK/$DAY/$HOUR/CATS/$CATEGORY_FOUND";	
	
	if($GLOBALS["DEBUG"]){
		if(!is_file($filename)){WLOG("WARNING! $filename no such file");}
	}
	
	
	$size=intval(@file_get_contents($filename));
	if($size==0){return false;}
	$size=$size/1024;
	$size=$size/1024;
	if($GLOBALS["DEBUG"]){WLOG("$WWW: $CATEGORY = {$size}MB check if exceed {$MaxSize}MB");}
	if($size>=$MaxSize){return true;}
	
}

function CHECK_CATEGORY_DAY($WWW,$gpid){
	if(!isset($GLOBALS["ACL_RULES"][$gpid]["CATEGORIES_DAY"])){return;}
	if($GLOBALS["DEBUG"]){WLOG("CATEGORIES CACHE: ".count($GLOBALS["CATEGORIES"])." items");}
	if(count($GLOBALS["CATEGORIES"])>1000){$GLOBALS["CATEGORIES"]=array();}

	if(!isset($GLOBALS["CATEGORIES"][$WWW])){
		$q=new mysql_catz();
		$GLOBALS["CATEGORIES"][$WWW]=trim(strtolower($q->GET_CATEGORIES($WWW)));
	}
	if($GLOBALS["CATEGORIES"][$WWW]==null){return;}
	$CATEGORY=$GLOBALS["CATEGORIES"][$WWW];
	if(!isset($GLOBALS["ACL_RULES"][$gpid]["CATEGORIES_DAY"][$CATEGORY])){return;}
	$MaxSize=$GLOBALS["ACL_RULES"][$gpid]["CATEGORIES_DAY"][$CATEGORY];

	$YEAR=date("Y");
	$MONTH=date("m");
	$DAY=date("d");
	$HOUR=date("H");
	$WEEK=date("W");

	$CATEGORY_FOUND=$GLOBALS["CATEGORIES"][$WWW];
	$CATEGORY_FOUND=str_replace("/", "_", $CATEGORY_FOUND);
	$filename="/home/squid/rttsize/$YEAR/$MONTH/$WEEK/$DAY/CATS/$CATEGORY_FOUND";

	if($GLOBALS["DEBUG"]){
		if(!is_file($filename)){WLOG("WARNING! $filename no such file");}
	}
	
	
	$size=intval(@file_get_contents($filename));
	if($size==0){return false;}
	$size=$size/1024;
	$size=$size/1024;
	if($GLOBALS["DEBUG"]){WLOG("$WWW: $CATEGORY = {$size}MB check if exceed {$MaxSize}MB");}
	if($size>=$MaxSize){return true;}

}
function CHECK_CATEGORY_WEEK($WWW,$gpid){
	if(!isset($GLOBALS["ACL_RULES"][$gpid]["CATEGORIES_WEEK"])){return;}
	if($GLOBALS["DEBUG"]){WLOG("CATEGORIES CACHE: ".count($GLOBALS["CATEGORIES"])." items");}
	if(count($GLOBALS["CATEGORIES"])>1000){$GLOBALS["CATEGORIES"]=array();}

	if(!isset($GLOBALS["CATEGORIES"][$WWW])){
		$q=new mysql_catz();
		$GLOBALS["CATEGORIES"][$WWW]=trim(strtolower($q->GET_CATEGORIES($WWW)));
	}
	if($GLOBALS["CATEGORIES"][$WWW]==null){return;}
	$CATEGORY=$GLOBALS["CATEGORIES"][$WWW];
	if(!isset($GLOBALS["ACL_RULES"][$gpid]["CATEGORIES_WEEK"][$CATEGORY])){return;}
	$MaxSize=$GLOBALS["ACL_RULES"][$gpid]["CATEGORIES_WEEK"][$CATEGORY];

	$YEAR=date("Y");
	$MONTH=date("m");
	$DAY=date("d");
	$HOUR=date("H");
	$WEEK=date("W");

	$CATEGORY_FOUND=$GLOBALS["CATEGORIES"][$WWW];
	$CATEGORY_FOUND=str_replace("/", "_", $CATEGORY_FOUND);
	$filename="/home/squid/rttsize/$YEAR/$MONTH/$WEEK/CATS/$CATEGORY_FOUND";

	if($GLOBALS["DEBUG"]){
		if(!is_file($filename)){WLOG("WARNING! $filename no such file");}
	}


	$size=intval(@file_get_contents($filename));
	if($size==0){return false;}
	$size=$size/1024;
	$size=$size/1024;
	if($GLOBALS["DEBUG"]){WLOG("$WWW: $CATEGORY = {$size}MB check if exceed {$MaxSize}MB");}
	
	if($size>=$MaxSize){return true;}

}
function CHECK_MEMBER($SUBF,$MaxPerHour=0,$MaxPerDay=0,$MaxPerWeek=0){
	$YEAR=date("Y");
	$MONTH=date("m");
	$DAY=date("d");
	$HOUR=date("H");
	$WEEK=date("W");
	
	$base="/home/squid/rttsize/$YEAR/$MONTH";	
	
	if($MaxPerHour>0){
		$path="$base/$WEEK/$DAY/$HOUR/$SUBF/TOT";
		if(is_file($path)){
			$SIZE=intval(@file_get_contents($path));
			$SIZE=$SIZE/1024; //KB
			$SIZE=$SIZE/1024; //MB
			if($GLOBALS["DEBUG"]){WLOG("TOT/$SUBF == {$SIZE}MB Max Hour:{$MaxPerHour}MB");}
			if($SIZE>=$MaxPerHour){return true;}
		}else{
			if($GLOBALS["DEBUG"]){WLOG("WARNING! $path no such file");}
		}
		
	}
	
	if($MaxPerDay>0){
		$path="$base/$WEEK/$DAY/$SUBF/TOT";
		if(is_file($path)){
			$SIZE=intval(@file_get_contents($path));
			$SIZE=$SIZE/1024; //KB
			$SIZE=$SIZE/1024; //MB
			if($GLOBALS["DEBUG"]){WLOG("TOT/$SUBF == {$SIZE}MB Max Day:{$MaxPerDay}MB");}
			if($SIZE>=$MaxPerDay){return true;}
		}else{
			if($GLOBALS["DEBUG"]){WLOG("WARNING! $path no such file");}
		}
	
	}
	
	
	if($MaxPerWeek>0){
		$path="$base/$WEEK/$SUBF/TOT";
		if(is_file($path)){
			$SIZE=intval(@file_get_contents($path));
			$SIZE=$SIZE/1024; //KB
			$SIZE=$SIZE/1024; //MB
			if($GLOBALS["DEBUG"]){WLOG("TOT/$SUBF == {$SIZE}MB Max Day:{$MaxPerDay}MB");}
			if($SIZE>=$MaxPerWeek){return true;}
	
		}else{
			if($GLOBALS["DEBUG"]){WLOG("WARNING! $path no such file");}
		}
	
	}	
}
function CHECK_WEBSITE($WWW,$MaxPerHour=0,$MaxPerDay=0,$MaxPerWeek=0){
	$YEAR=date("Y");
	$MONTH=date("m");
	$DAY=date("d");
	$HOUR=date("H");
	$WEEK=date("W");

	$base="/home/squid/rttsize/$YEAR/$MONTH";

	if($MaxPerHour>0){
		$path="$base/$WEEK/$DAY/$HOUR/WEBS/$WWW";
		if(is_file($path)){
			$SIZE=intval(@file_get_contents($path));
			$SIZE=$SIZE/1024; //KB
			$SIZE=$SIZE/1024; //MB
			if($GLOBALS["DEBUG"]){WLOG("TOT/$WWW == {$SIZE}MB Max Hour:{$MaxPerHour}MB");}
			if($SIZE>=$MaxPerHour){return true;}
		}else{
			if($GLOBALS["DEBUG"]){WLOG("WARNING! $path no such file");}
		}

	}

	if($MaxPerDay>0){
		$path="$base/$WEEK/$DAY/WEBS/$WWW";
		if(is_file($path)){
			$SIZE=intval(@file_get_contents($path));
			$SIZE=$SIZE/1024; //KB
			$SIZE=$SIZE/1024; //MB
			if($GLOBALS["DEBUG"]){WLOG("TOT/$WWW == {$SIZE}MB Max Day:{$MaxPerDay}MB");}
			if($SIZE>=$MaxPerDay){return true;}
		}else{
			if($GLOBALS["DEBUG"]){WLOG("WARNING! $path no such file");}
		}

	}


	if($MaxPerWeek>0){
		$path="$base/$WEEK/WEBS/$WWW";
		if(is_file($path)){
			$SIZE=intval(@file_get_contents($path));
			$SIZE=$SIZE/1024; //KB
			$SIZE=$SIZE/1024; //MB
			if($GLOBALS["DEBUG"]){WLOG("TOT/$WWW == {$SIZE}MB Max Day:{$MaxPerDay}MB");}
			if($SIZE>=$MaxPerWeek){return true;}

		}else{
			if($GLOBALS["DEBUG"]){WLOG("WARNING! $path no such file");}
		}

	}
}
function CHECK_UID($WWW,$SUBF,$MaxPerHour=0,$MaxPerDay=0,$MaxPerWeek=0){
	
	$YEAR=date("Y");
	$MONTH=date("m");
	$DAY=date("d");
	$HOUR=date("H");
	$WEEK=date("W");
	
	$base="/home/squid/rttsize/$YEAR/$MONTH";
	
	if($MaxPerHour>0){
		$path="$base/$WEEK/$DAY/$HOUR/$SUBF/$WWW";
		if(is_file($path)){
			$SIZE=intval(@file_get_contents($path));
			$SIZE=$SIZE/1024; //KB
			$SIZE=$SIZE/1024; //MB
			if($GLOBALS["DEBUG"]){WLOG("$WWW/$SUBF == {$SIZE}MB Max Hour:{$MaxPerHour}MB");}
			if($SIZE>=$MaxPerHour){return true;}
		}else{
			if($GLOBALS["DEBUG"]){WLOG("WARNING! $path no such file");}
		}
	
	}
	
	
	if($MaxPerDay>0){
		$path="$base/$WEEK/$DAY/$SUBF/$WWW";
		if(is_file($path)){
			$SIZE=intval(@file_get_contents($path));
			$SIZE=$SIZE/1024; //KB
			$SIZE=$SIZE/1024; //MB
			if($GLOBALS["DEBUG"]){WLOG("$WWW/$SUBF == {$SIZE}MB Max Day:{$MaxPerDay}MB");}
			if($SIZE>=$MaxPerDay){return true;}
		}else{
			if($GLOBALS["DEBUG"]){WLOG("WARNING! $path no such file");}
		}
	
	}
	
	
	if($MaxPerWeek>0){
		$path="$base/$WEEK/$SUBF/$WWW";
		if(is_file($path)){
			$SIZE=intval(@file_get_contents($path));
			$SIZE=$SIZE/1024; //KB
			$SIZE=$SIZE/1024; //MB
			if($GLOBALS["DEBUG"]){WLOG("$WWW/$SUBF == {$SIZE}MB Max Day:{$MaxPerDay}MB");}
			if($SIZE>=$MaxPerWeek){return true;}
	
		}else{
			if($GLOBALS["DEBUG"]){WLOG("WARNING! $path no such file");}
		}
	
	}
}
