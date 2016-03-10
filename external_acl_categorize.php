#!/usr/bin/php -q
<?php
$GLOBALS["DEBUG"]=false;
$GLOBALS["HERLPER_LOADED_BY_SQUID"]=true;
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
$GLOBALS["MYPID"]=getmypid();
WLOG("Starting PID:{$GLOBALS["MYPID"]}");
$GLOBALS["XVFERTSZ"]=XVFERTSZ();



$fam=new squid_familysite();
$q=new mysql_catz();
$DCOUNT=0;
while (!feof(STDIN)) {
	$Buffer = trim(fgets(STDIN));
	if($Buffer==null){
		if($GLOBALS["DEBUG"]){WLOG("$DCOUNT] LOOP::URL `$Buffer` is null [".__LINE__."]");}
		continue;
	}
	
	if($GLOBALS["DEBUG"]){WLOG("$DCOUNT] LOOP::URL `$Buffer` [".__LINE__."]");}
	$MAIN=explode(" ",$Buffer);
	$prefix_channel=null;
	// administrateur 192.168.1.177 3c:a9:f4:13:9b:90 - www.google.fr 57
	
	
	if(is_numeric($MAIN[0])){
		$GLOBALS["CHANNEL"]=$MAIN[0];
		$GLOBALS["DOMAIN"]=trim($MAIN[1]);
		
	}else{
		$GLOBALS["DOMAIN"]=trim($MAIN[0]);
		$TAG=trim($MAIN[1]);
		
	}
	

	if(isset($GLOBALS["CHANNEL"])){
		if(is_numeric($GLOBALS["CHANNEL"])){
			if($GLOBALS["CHANNEL"]>0){
				$prefix_channel="{$GLOBALS["CHANNEL"]} ";
			}
		}
	}
	
	if($GLOBALS["DEBUG"]){WLOG("{$GLOBALS["DOMAIN"]}: LOOP domain = {$GLOBALS["DOMAIN"]}");}
	
	$DCOUNT++;
	
	if(!$GLOBALS["XVFERTSZ"]){
		$error=urlencode("License Error, please remove Artica categories objects in ACL");
		WLOG("{$GLOBALS["DOMAIN"]}: LOOP():: License Error ! [".__LINE__."]");
		categories_logs("ERROR;License error");
		fwrite(STDOUT, "{$prefix_channel}BH message=$error\n");
		continue;
	}	
	
	try {
		$sitename=$fam->GetFamilySites($GLOBALS["DOMAIN"]);
		$category=$q->GET_CATEGORIES($sitename);
		
	}
	catch (Exception $e) {
		$error=$e->getMessage();
		WLOG("{$GLOBALS["DOMAIN"]}: $DCOUNT] LOOP::FATAL ERROR $error");
		
	}
	
	if($category<>null){
		if($GLOBALS["DEBUG"]){WLOG("{$GLOBALS["DOMAIN"]}: LOOP domain = {$GLOBALS["DOMAIN"]} category=$category");}
		fwrite(STDOUT, "{$prefix_channel}OK tag=$category\n");
		continue;
	}
	fwrite(STDOUT, "{$prefix_channel}OK\n");
	continue;

	
	
}



WLOG("Stopping PID:{$GLOBALS["MYPID"]} After $DCOUNT event(s) SAVED {$GLOBALS["DATABASE_ITEMS"]} items in database");
	
	
function WLOG($text=null){
	$trace=@debug_backtrace();
	$filename="/var/log/squid/acl_categories.log";
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



function XVFERTSZ(){
	$F=base64_decode("L3Vzci9sb2NhbC9zaGFyZS9hcnRpY2EvLmxpYw==");

	if(!is_file($F)){
		WLOG("License check no such license");
		return false;}
		$D=trim(@file_get_contents($F));
		if(trim($D)=="TRUE"){return true;}
		WLOG("License check no such license content");
		return false;

}
?>