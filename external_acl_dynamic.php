#!/usr/bin/php -q
<?php
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
$GLOBALS["HERLPER_LOADED_BY_SQUID"]=true;
$GLOBALS["LOG_FILE_NAME"]="/var/log/squid/external-acl-dynamic.log";
//ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
  error_reporting(0);
 
  $GLOBALS["PID"]=getmypid();
  $GLOBALS["STARTIME"]=time();
  $GLOBALS["uriToHost"]=array();
  $GLOBALS["ITCHART"]=false;
  $GLOBALS["SESSION_TIME"]=array();
  $GLOBALS["VERBOSE"]=false;
  $GLOBALS["XVFERTSZ"]=XVFERTSZ();
  $GLOBALS["CATZ-EXTRN"]=0;
  $GLOBALS["DEBUG_LEVEL"]=4;
  $GLOBALS["UNBLOCK"]=false;
  if(!is_numeric($GLOBALS["DEBUG_LEVEL"])){$GLOBALS["DEBUG_LEVEL"]=1;}
  $GLOBALS["RULE_ID"]=0;
  WLOG("Starting ACLs dynamic with debug level:{$GLOBALS["DEBUG_LEVEL"]} - License: {$GLOBALS["XVFERTSZ"]}");
  openLogs();
  ap_mysql_load_params();
 
  
  if($GLOBALS["DEBUG_LEVEL"]>0){
  	if($GLOBALS["DEBUG_LEVEL"]>1){error_reporting(1);}
  	WLOG("Starting ACLs dynamic with debug level:{$GLOBALS["DEBUG_LEVEL"]}");
  	
  	
  }

//----------------- LOOP ------------------------------------  
  
	while (!feof(STDIN)) {
		$ID=0;
	 	$url = trim(fgets(STDIN));
	 	if($url==null){if($GLOBALS["DEBUG_LEVEL"]>3){WLOG("LOOP::URL is NULL");}continue;}
	 	if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("LOOP()::str ".$url ." [".__LINE__."]");}
		$array=parseURL($url);
	 	if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("LOOP()::str:".strlen($url)." Array(".count($array).") Rule ID:{$GLOBALS["RULE_ID"]}; LOGIN:{$array["LOGIN"]}; IPADDR:{$array["IPADDR"]}; MAC:{$array["MAC"]}; HOST:{$array["HOST"]}; RHOST:{$array["RHOST"]}; URI:{$array["URI"]}");}
	
			 if(isset($array["LOGIN"])){
			 		if($GLOBALS["DEBUG_LEVEL"]>3){WLOG("LOOP()::{$GLOBALS["RULE_ID"]} ->{$array["LOGIN"]} type 2");}
			 		$ID=CheckPattern($array["LOGIN"],$GLOBALS["RULE_ID"],2);
			 }
			 if($ID==0){
				if(isset($array["HOST"])){
					if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $array["HOST"])){$array["HOST"]=gethostbyaddr($array["HOST"]);}
				 	if($GLOBALS["DEBUG_LEVEL"]>3){WLOG("LOOP()::{$GLOBALS["RULE_ID"]} ->{$array["HOST"]} type 3");}
				 	$ID=CheckPattern($array["HOST"],$GLOBALS["RULE_ID"],3);
				 	}
			 	}	
			
			 		
			 	if($ID==0){
			 		if(isset($array["IPADDR"])){
			 			if($GLOBALS["DEBUG_LEVEL"]>3){WLOG("LOOP()::{$GLOBALS["RULE_ID"]} ->{$array["HOST"]} type 1");}
			 			$ID=CheckPattern($array["IPADDR"],$GLOBALS["RULE_ID"],1);
			 		}
			 	}
			 	if($ID==0){
			 		if(isset($array["DOMAIN"])){
			 			if($GLOBALS["DEBUG_LEVEL"]>3){WLOG("LOOP()::{$GLOBALS["RULE_ID"]} ->{$array["DOMAIN"]} type 4");}
			 			$ID=CheckPattern($array["DOMAIN"],$GLOBALS["RULE_ID"],4);
			 		}
			 	}
			 	
			 		
			 	if($ID==0){
			 		if(isset($array["MAC"])){
			 			if($GLOBALS["DEBUG_LEVEL"]>3){WLOG("LOOP()::{$GLOBALS["RULE_ID"]} ->{$array["MAC"]} type 0");}
			 			$ID=CheckPattern($array["MAC"],$GLOBALS["RULE_ID"],0);
			 		}
			 	} 		
			 		
			 		
			 	if($ID>0){
			 		WLOG("LOOP()::Rule:{$GLOBALS["RULE_ID"]} ({$array["MAC"]}/{$array["LOGIN"]}/{$array["MAC"]}/{$array["IPADDR"]}/{$array["HOST"]}/{$array["RHOST"]}) MATCH;");
			 		fwrite(STDOUT, "OK message=\"Group:{$GLOBALS["RULE_ID"]}: Rule:$ID\"\n");
			 		continue;
			 	} 		
			 	if($GLOBALS["DEBUG_LEVEL"]>3){WLOG("LOOP()::Rule:{$GLOBALS["RULE_ID"]} ({$array["LOGIN"]}/{$array["MAC"]}/{$array["IPADDR"]}/{$array["HOST"]}/{$array["RHOST"]}) nothing match");}
				fwrite(STDOUT, "ERR\n");
			 	continue;
		 	
	}

//-----------------------------------------------------
$distanceInSeconds = round(abs(time() - $GLOBALS["STARTIME"]));
$distanceInMinutes = round($distanceInSeconds / 60);
WLOG("Dynamic ACL: v1.0a: die after ({$distanceInSeconds}s/about {$distanceInMinutes}mn)");



function parseURL($url){
	$uri=null;
	if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("parseURL():: Analyze [$url]");}
	//%LOGIN %SRC %SRCEUI48 %>ha{X-Forwarded-For} %DST

	$MAIN=explode(" ",$url);

	if(is_numeric($MAIN[0])){
		$GLOBALS["CHANNEL"]=$MAIN[0];

		$LOGIN2=trim($MAIN[1]);
		$IPADDR=trim($MAIN[2]);
		$MAC=trim($MAIN[3]);
		$IPADDR2=trim($MAIN[4]);
		$DOMAIN=trim($MAIN[5]);
		$ID=trim($MAIN[6]);
	}else{

		$LOGIN2=trim($MAIN[0]);
		$IPADDR=trim($MAIN[1]);
		$MAC=trim($MAIN[2]);
		$IPADDR2=trim($MAIN[3]);
		$DOMAIN=trim($MAIN[4]);
		$ID=trim($MAIN[5]);
	}
	
	if(preg_match("#ID([0-9]+)#", $ID,$re)){$GLOBALS["RULE_ID"]=$re[1];}
	
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
	
	
	if(count($GLOBALS["CACHE_HOSTS"])>500){$GLOBALS["CACHE_HOSTS"]=array();}
	
	if(isset($GLOBALS["CACHE_HOSTS"][$IPADDR])){
		$GLOBALS["CACHE_HOSTS"][$IPADDR]=gethostbyaddr($IPADDR);
	}

	$ARRAY["USERID"]=$LOGIN;
	$ARRAY["IPADDR"]=$IPADDR;
	$ARRAY["MAC"]=$MAC;
	$ARRAY["HOST"]=$GLOBALS["CACHE_HOSTS"][$IPADDR];
	$ARRAY["DOMAIN"]=trim(strtolower($DOMAIN));
	return $ARRAY;

}



function CheckPattern($name,$gpid,$type){
	if(!is_numeric($gpid)){return;}
	if(!is_numeric($type)){return;}
	if(!isset($GLOBALS["CACHE_CLEAN_MYSQL"])){$GLOBALS["CACHE_CLEAN_MYSQL"]=time();}
	
	if($type==3){
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $name)){
			return 0;
		}
	}
	
	try{
		
	
	$sql="SELECT ID,`value`  FROM webfilter_aclsdynamic WHERE gpid=$gpid AND `type`=$type";
	$results = api_QUERY_SQL($sql);
	
	if(mysql_num_rows($results)==0){
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("CheckPattern()::$sql -> NO ROW");}
		return 0;
	}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$value=$ligne["value"];
		if(preg_match("#re:(.+)#", $value,$re)){
			if(preg_match("#{$re[1]}#i", $name)){return $ligne["ID"];}
			continue;
		}
		
		$value=string_to_regex($value);
		if($GLOBALS["DEBUG_LEVEL"]>3){WLOG("CheckPattern()::Checks `$value` with `$name`");}
		if(preg_match("#$value#i", $name)){
			if($GLOBALS["DEBUG_LEVEL"]>3){WLOG("CheckPattern()::Match `$value` with `$name` = {$ligne["ID"]}");}
			return $ligne["ID"];
		}
	}
	
	if(DistanceInMns($GLOBALS["CACHE_CLEAN_MYSQL"])>5){
		$GLOBALS["CACHE_CLEAN_MYSQL"]=time();
		if($GLOBALS["DEBUG_LEVEL"]>3){WLOG("CheckPattern():: Clean old rules...");}
		$sql="DELETE FROM `webfilter_aclsdynamic` WHERE `duration`>0 AND `maxtime`>".time();
		api_QUERY_SQL($sql);
		
	}
	
	
	}
	catch (Exception $e) {
		WLOG($e->getMessage());
		return 0;
	}
	
	return 0;
}





function DistanceInMns($time){
	$data1 = $time;
	$data2 = time();
	$difference = ($data2 - $data1);
	return round($difference/60);
}

function openLogs($force=false){
	$ACLS_OPTIONS=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/AclsOptions")));
	if(!is_numeric($ACLS_OPTIONS["DYN_LOG_LEVEL"])){$ACLS_OPTIONS["DYN_LOG_LEVEL"]=0;}
	if($ACLS_OPTIONS["DYN_LOG_LEVEL"]>$GLOBALS["DEBUG_LEVEL"]){
		$GLOBALS["DEBUG_LEVEL"]=$ACLS_OPTIONS["DYN_LOG_LEVEL"];	
	}
	if(!isset($GLOBALS["PID"])){$GLOBALS["PID"]=getmypid();}
}
function _get_memory_usage_158() {
	$mem_usage = memory_get_usage(true);
	if ($mem_usage < 1024){return $mem_usage." bytes";}
	if ($mem_usage < 1048576){return round($mem_usage/1024,2)." kilobytes";}
	return round($mem_usage/1048576,2)." megabytes";
}

function WLOG($text=null){
	$trace=@debug_backtrace();
	$filename=$GLOBALS["LOG_FILE_NAME"];
	if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
	$date=@date("Y-m-d H:i:s");
	$mem=_get_memory_usage_158();
	$f = @fopen($GLOBALS["LOG_FILE_NAME"], 'a'); 
	
	
   	if (is_file($GLOBALS["LOG_FILE_NAME"])) { 
   		$size=@filesize($GLOBALS["LOG_FILE_NAME"]);
   		if($size>1000000){
   			@fclose($f);
   			unlink($GLOBALS["LOG_FILE_NAME"]);
   			openLogs(true);
   		}
	}
	
	
	if($GLOBALS["VERBOSE"]){echo "$date [{$GLOBALS["PID"]}]: $text $called\n";}
	@fwrite($f, "$date [{$GLOBALS["PID"]}]: $text $called - process Memory:$mem\n");
	@fclose($f);
}

function ap_mysql_load_params(){
	$GLOBALS["MYSQL_SOCKET"]=null;
	$GLOBALS["MYSQL_PASSWORD"]=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/database_password"));
	if($GLOBALS["MYSQL_PASSWORD"]=="!nil"){$GLOBALS["MYSQL_PASSWORD"]=null;}
	$GLOBALS["MYSQL_PASSWORD"]=stripslashes($GLOBALS["MYSQL_PASSWORD"]);
	$GLOBALS["MYSQL_USERNAME"]=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/database_admin"));
	$GLOBALS["MYSQL_SERVER"]=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/mysql_server"));
	$GLOBALS["MYSQL_PORT"]=intval(@file_get_contents("/etc/artica-postfix/settings/Mysql/port"));
	if($GLOBALS["MYSQL_PORT"]==0){$GLOBALS["MYSQL_PORT"]=3306;}
	if($GLOBALS["MYSQL_SERVER"]==null){$GLOBALS["MYSQL_SERVER"]="127.0.0.1";}
	$GLOBALS["MYSQL_USERNAME"]=str_replace("\r", "", $GLOBALS["MYSQL_USERNAME"]);
	$GLOBALS["MYSQL_USERNAME"]=trim($GLOBALS["MYSQL_USERNAME"]);
	$GLOBALS["MYSQL_PASSWORD"]=str_replace("\r", "", $GLOBALS["MYSQL_PASSWORD"]);
	$GLOBALS["MYSQL_PASSWORD"]=trim($GLOBALS["MYSQL_PASSWORD"]);

	if($GLOBALS["MYSQL_USERNAME"]==null){$GLOBALS["MYSQL_USERNAME"]="root";}
	if($GLOBALS["MYSQL_SERVER"]=="localhost"){$GLOBALS["MYSQL_SERVER"]="127.0.0.1";}
	if($GLOBALS["MYSQL_SERVER"]=="127.0.0.1"){$GLOBALS["MYSQL_SOCKET"]="/var/run/mysqld/squid-db.sock";}
}
function api_mysql_connect(){

	if($GLOBALS["MYSQL_SOCKET"]<>null){
		$bd=@mysql_connect(":{$GLOBALS["MYSQL_SOCKET"]}",$GLOBALS["MYSQL_USERNAME"],$GLOBALS["MYSQL_PASSWORD"]);
	}else{
		$bd=@mysql_connect("{$GLOBALS["MYSQL_SERVER"]}:{$GLOBALS["MYSQL_PORT"]}","{$GLOBALS["MYSQL_USERNAME"]}","{$GLOBALS["MYSQL_PASSWORD"]}");
	}

	if($bd){return $bd;}
	$des=@mysql_error();
	$errnum=@mysql_errno();
	WLOG("api_mysql_connect() failed (N:$errnum) \"$des\"");
	return false;
}
function api_QUERY_SQL($sql){
	if($GLOBALS["DEBUG_LEVEL"]){WLOG("api_QUERY_SQL::Call api_mysql_connect");}
	$mysql_connection=api_mysql_connect();
	if(!$mysql_connection){return false;}

	if($GLOBALS["DEBUG_LEVEL"]>0){WLOG("api_QUERY_SQL::Call mysql_select_db");}
	$ok=@mysql_select_db("squidlogs",$mysql_connection);
	if(!$ok){
		$errnum=@mysql_errno($mysql_connection);
		$des=@mysql_error($mysql_connection);
		@mysql_close($mysql_connection);
		WLOG("mysql_select_db() failed (N:$errnum) \"$des\"");
		return false;
	}

	$mysql_unbuffered_query_log=null;
	if(preg_match("#^(UPDATE|DELETE)#i", $sql)){
		$mysql_unbuffered_query_log="mysql_unbuffered_query";
		$results=@mysql_unbuffered_query($sql,$mysql_connection);
			
	}else{
		$mysql_unbuffered_query_log="mysql_query";
		$results=@mysql_query($sql,$mysql_connection);
	}

	if(!$results){
		$errnum=@mysql_errno($mysql_connection);
		$des=@mysql_error($mysql_connection);
		@mysql_close($mysql_connection);
		WLOG("$mysql_unbuffered_query_log() failed (N:$errnum) \"$des\"");
		return false;
	}
	@mysql_close($mysql_connection);
	return $results;


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
		$time=time("Ymh");
		if(count($GLOBALS["resvip"])>5){unset($GLOBALS["resvip"]);}
		if(isset($GLOBALS["resvip"][$time][$ip])){return $GLOBALS["resvip"][$time][$ip];}
		$name=gethostbyaddr($ip);
		$GLOBALS["resvip"][$time]=$name;
		return $name;
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
		$this->events($cmd);
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
function string_to_regex($pattern){
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
?>
