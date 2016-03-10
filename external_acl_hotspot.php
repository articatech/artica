#!/usr/bin/php -q
<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["KAV4PROXY_NOSESSION"]=true;
if(!isset($GLOBALS["ARTICALOGDIR"])){
		$GLOBALS["ARTICALOGDIR"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaLogDir"); 
		if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } 
}
  ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);
  ini_set('error_reporting', E_ALL);
  ini_set("error_log", "/var/log/php.log");
  error_reporting(E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR);
  
  $GLOBALS["PID"]=getmypid();
  $GLOBALS["SPLASH_DEBUG"]=false;
  $GLOBALS["SPLASH"]=false;
  $GLOBALS["STARTIME"]=time();
  $GLOBALS["MACTUIDONLY"]=false;
  $GLOBALS["uriToHost"]=array();
  $GLOBALS["DEBUG_LEVEL"]=0;
  
  $max_execution_time=ini_get('max_execution_time'); 
  WLOG("Starting... Log level:{$GLOBALS["DEBUG_LEVEL"]};");
  ap_mysql_load_params();
  
  
while (!feof(STDIN)) {
 	$url = trim(fgets(STDIN));
 	if($url==null){continue;}
 	$clt_conn_tag=null;
	if($GLOBALS["DEBUG_LEVEL"]>0){WLOG($url);}
	
	if(preg_match("#([0-9a-z:]+)#", $url,$re)){$MAC=trim(strtolower($re[1]));}
	if($GLOBALS["DEBUG_LEVEL"]>0){WLOG("ASK: $MAC = ?");}
	if($MAC=="00:00:00:00:00:00"){
		fwrite(STDOUT, "OK\n");
		continue;
	}
	
	$uidArray=GetMacToUid($MAC);
	$uid=$uidArray["UID"];
	$ruleid=$uidArray["RULE"];
	if($GLOBALS["DEBUG_LEVEL"]>0){WLOG("ASK: $MAC = $uid");}
	if($uid==null){
		fwrite(STDOUT, "OK\n");
		continue;
	}
	
	if($uid<>null){
		$clt_conn_tag=" tag=HotspotRule$ruleid log=HotSpot,none";
		fwrite(STDOUT, "OK user=$uid{$clt_conn_tag}\n");
		continue;
	}
	
	fwrite(STDOUT, "OK\n");
	
	
}


$distanceInSeconds = round(abs(time() - $GLOBALS["STARTIME"]));
$distanceInMinutes = round($distanceInSeconds / 60);
WLOG("v1.0:". basename(__FILE__)." die after ({$distanceInSeconds}s/about {$distanceInMinutes}mn)");




function GetMacToUid($mac){
	if(isset($GLOBALS["GetMacToUid"][$mac])){return $GLOBALS["GetMacToUid"][$mac];}
	$sql="SELECT ruleid,uid FROM hotspot_sessions WHERE MAC='$mac'";
	if($GLOBALS["DEBUG_LEVEL"]>0){WLOG("GetMacToUid() $sql");}
	$ligne=@mysql_fetch_array(api_QUERY_SQL($sql));
	if($ligne["uid"]<>null){
		$ruleid=$ligne["ruleid"];
		$GLOBALS["GetMacToUid"][$mac]=array("UID"=>$ligne["uid"],"RULE"=>$ruleid);
	}
	if(!isset($GLOBALS["GetMacToUid"][$mac])){return array();}
	return $GLOBALS["GetMacToUid"][$mac];
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


function tool_time_min($timeFrom){
	$data1 = $timeFrom;
	$data2 = time();
	$difference = ($data2 - $data1);
	$results=intval(round($difference/60));
	if($results<0){$results=1;}
	return $results;
}



function WLOG($text=null){
	
	$trace=@debug_backtrace();
	if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
	$date=@date("Y-m-d H:i:s");
	$handle = @fopen("/var/log/squid/HotSpotToUid.log", 'a');
	
   	if (is_file("/var/log/squid/HotSpotToUid.log")) { 
   		$size=@filesize("/var/log/squid/HotSpotToUid.log");
   		if($size>1000000){
   			@fclose($handle);
   			unlink("/var/log/squid/HotSpotToUid.log");
   			$handle = @fopen("/var/log/squid/HotSpotToUid.log", 'a');
   		}
   		
   		
   	}
	
	
	@fwrite($handle, "$date ".basename(__FILE__)."[{$GLOBALS["PID"]}]: $text $called\n");
	@fclose($handle);
}


function GetComputerName($ip){return $ip;}

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
