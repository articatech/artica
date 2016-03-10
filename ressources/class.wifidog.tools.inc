<?php
$GLOBALS["CACHEDIR"]="/home/artica/hotspot/caches";


function generateToken($MAC=null) {
	if(isset($GLOBALS["generateToken"])){return $GLOBALS["generateToken"];}
	$xLOADED=false;
	$MAIN=array();
	if($MAC==null){
		$GLOBALS["generateToken"]= md5(uniqid(rand(), 1));
		if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("**** New Token: {$GLOBALS["generateToken"]}");}
		return $GLOBALS["generateToken"];
	}
		
	$Sourcecachefile="/usr/share/artica/postfix/ressources/logs/web/wifidog.status";
	$cachefile="{$GLOBALS["CACHEDIR"]}/wifidog.status";
	
	if(is_file($cachefile)){
		$file_time_min_wifidog=file_time_min_wifidog($cachefile);
		
		if($file_time_min_wifidog<1){
			$MAIN=unserialize(@file_get_contents($cachefile));
			$xLOADED=true;
		}
	}
	if(count($MAIN)==0){$xLOADED=false;}
	
	
	
	if(!$xLOADED){
		$MAIN=generateToken_retrieve();
	}
		
	if(isset($MAIN["SESSIONS"][$MAC])){
		$GLOBALS["generateToken"]=$MAIN["SESSIONS"][$MAC];
		if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("**** OLD TOKEN {$GLOBALS["generateToken"]}");}
		return $GLOBALS["generateToken"];
	}
	
}
function generateToken_retrieve(){
	$Sourcecachefile="/usr/share/artica/postfix/ressources/logs/web/wifidog.status";
	$cachefile="{$GLOBALS["CACHEDIR"]}/wifidog.status";
	
	
	if(!is_file("/home/artica/hotspot/caches/WifidogUri")){
		if($GLOBALS["VERBOSE"]){echo "/home/artica/hotspot/caches/WifidogUri, not such file<br>\n";}
		$sock=new sockets();
		$sock->getFrameWork("hotspot.php?wifidog-check-status=yes");
		if($GLOBALS["VERBOSE"]){echo "open $Sourcecachefile<br>\n";}
		$MAIN=unserialize(@file_get_contents($Sourcecachefile));
		@unlink($cachefile);
		@file_put_contents($cachefile, serialize($MAIN));
		return $MAIN;
	}
	return generateToken_getDirect();
}


function generateToken_getDirect(){
	$Sourcecachefile="/usr/share/artica/postfix/ressources/logs/web/wifidog.status";
	$cachefile="{$GLOBALS["CACHEDIR"]}/wifidog.status";
	
	include_once(dirname(__FILE__)."/class.ccurl.inc");
	$uri=@file_get_contents("/home/artica/hotspot/caches/WifidogUri");
	$IPADDR=@file_get_contents("/home/artica/hotspot/caches/HTTPClientIP");
	if($GLOBALS["VERBOSE"]){echo "open $uri<br>\n";}
	$curl=new ccurl($uri,true,$IPADDR,true);
	$curl->NoHTTP_POST=true;
	$curl->noproxyload=true;
	$curl->Timeout=2;
	$curl->interface=$IPADDR;
	if(!$curl->get()){return;}
	
	$ARRAY=array();
	$f=explode("\n",$curl->data);
	while (list ($num, $line) = each ($f) ){
		$line=trim($line);
		if($GLOBALS["VERBOSE"]){echo "Analyze ".htmlentities($line)."<br>\n";}
		
		if(preg_match("#IP:\s+([0-9\.]+)\s+MAC:\s+(.+)#", $line,$re)){
			
			$MAC=trim(strtolower($re[2]));
			$IP=trim($re[1]);
			if($GLOBALS["VERBOSE"]){echo "$MAC/$IP<br>\n";}
			continue;
		}
	
		if(preg_match("#Token:\s+(.+)#", $line,$re)){
			if($GLOBALS["VERBOSE"]){echo "$MAC == {$re[1]}<br>\n";}
			$ARRAY["SESSIONS"][$MAC]=trim($re[1]);
		}
	}
	
	@unlink($cachefile);
	@file_put_contents($cachefile, serialize($ARRAY));
	
	return $ARRAY;
}

function file_time_min_wifidog($path){
	if(!is_file($path)){
		if($GLOBALS["VERBOSE"]){echo "file_time_min() -> unable to stat $path\n";}
		return 100000;
	}
	$last_modified = filemtime($path);
	$data1 = $last_modified;
	$data2 = time();
	$difference = ($data2 - $data1);
	return round($difference/60);
}

function wifidog_build_uri(){
	reset($_REQUEST);
	
	if(isset($_REQUEST["ruleid"])){$_SESSION["WIFIDOG_RULES"]=$_REQUEST["ruleid"];}
	
	while (list ($num, $ligne) = each ($_REQUEST) ){
		if($num=="wifidog-login"){continue;}
		if($num=="wifidog-register"){continue;}
		if($num=="register-member"){continue;}
		if($num=="wifidog-recover"){continue;}
		if($num=="register-recover"){continue;}
		if($num=="wifidog-password"){continue;}
		if($num=="wifidog-password"){continue;}
		if($num=="confirm-password"){continue;}
		if($num=="register-sms"){continue;}
		if($num=="wifidog-terms"){continue;}
		if($num=="mobile"){continue;}
		if($num=="SMS_CODE"){continue;}
		if($num=="remove-sms"){continue;}
		$URIZ[]="$num=".urlencode($ligne);
		$inputz[]="<input type='hidden' id='$num' name='$num' value='$ligne'>";

	}
	
	
	if(!isset($_SESSION["WIFIDOG_RULES"])){
		$wifidog_templates=new wifidog_rules();
		$_SESSION["WIFIDOG_RULES"]=$wifidog_templates->ruleid;
	}
	$inputz[]="<input type='hidden' id='ruleid' name='ruleid' value='{$_SESSION["WIFIDOG_RULES"]}'>";
	$URIZ[]="ruleid={$_SESSION["WIFIDOG_RULES"]}";

	return array(@implode("&", $URIZ),@implode("\n", $inputz));

}

function wifidog_logs($text,$function=null,$line=0){
	
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
	
		if(isset($trace[0])){
			$file=basename($trace[0]["file"]);
			if($function==null){$function=$trace[0]["function"];}
			if($line==0){$line=$trace[0]["line"];}
		}
	

	
	
	}
	$date=date("Y-m-d H:i:s");
	$logFile="/var/log/artica-wifidog.log";
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
	if (is_file($logFile)) {
		$size=filesize($logFile);
		if($size>1000000){@unlink($logFile);}
	}
	$logFile=str_replace("//","/",$logFile);
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$date [$file/$function/$line]: $text\n");
	@fclose($f);
}