#!/usr/bin/php -q
<?php
$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
if($EnableIntelCeleron==1){die("EnableIntelCeleron==1\n");}
ini_set('memory_limit','1000M');
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.realtime-buildsql.inc");
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');


if(preg_match("#--verbose#",implode(" ",$argv))){
	echo "VERBOSED....\n";
	$GLOBALS["VERBOSE"]=true;$GLOBALS["TRACE_INFLUX"]=true;
	$GLOBALS["OUTPUT"]=true;
	$GLOBALS["debug"]=true;
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
}




scan();
function scan(){
	$pidtime="/etc/artica-postfix/pids/exec.haproxy.events-parser.php.scan.time";
	$pidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();
	$pid=$unix->get_pid_from_file($pidFile);
	if($unix->process_exists($pid)){
		events("A process, $pid Already exists...");
		return;
	}

	$GLOBALS["MYHOSTNAME_PROXY"]=$unix->hostname_g();
	
	@file_put_contents($pidFile, getmypid());
	
	if(!$GLOBALS["VERBOSE"]){
		$time=$unix->file_time_min($pidtime);
		if(!$GLOBALS["VERBOSE"]){
			if($time<5){
				events("{$time}mn, require minimal 5mn");
				return;
			}
		}
	}
	$GLOBALS["LogFileDeamonLogDir"]="/home/artica/haproxy-postgres/realtime-events";
	$Workpath="{$GLOBALS["LogFileDeamonLogDir"]}/access-work";

	@file_put_contents($pidtime,time());
	@mkdir($GLOBALS["LogFileDeamonLogDir"],0755,true);	
	
	$postgres=new postgres_sql();
	$postgres->CREATE_TABLES();
	
	if(!$postgres->TABLE_EXISTS("haproxy_log")){
		events("haproxy_log, not such table");
		die();
		
	}
	
	
	if(is_file("{$GLOBALS["LogFileDeamonLogDir"]}/HAPROXY.LOG")){
		HAPROXY_LOG("{$GLOBALS["LogFileDeamonLogDir"]}/HAPROXY.LOG");
	}
	HAPROXY_LOG_SCAN($Workpath);
	
	
}


function HAPROXY_LOG($sourcefile){
	
	$unix=new unix();
	if($sourcefile==null){$sourcefile="{$GLOBALS["LogFileDeamonLogDir"]}/HAPROXY.LOG";}
	$Workpath="{$GLOBALS["LogFileDeamonLogDir"]}/access-work";
	$backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/access-backup";
	
	@mkdir($Workpath,0755,true);
	@mkdir($backupdir,0755,true);
	events("Scanning $sourcefile");
	
	
	if(!is_file($sourcefile)){
		events("$sourcefile no such file");
		HAPROXY_LOG_SCAN($Workpath);
		return;
	}
	
	if(is_file($sourcefile)){
		$workfile=$Workpath."/".time().".log";
		if(is_file($workfile)){return;}
		$size=@filesize($sourcefile);
		events("Copy $sourcefile (".FormatBytes($size/1024,TRUE).")");
		if(!@copy($sourcefile, "$workfile")){return;}
		@unlink($sourcefile);
	}
	
	
	
}
function HAPROXY_LOG_SCAN($Workpath){
	$unix=new unix();
	events("Scanning $Workpath");
	$files=$unix->DirFiles($Workpath);
	
	while (list ($basename, $subarray) = each ($files)){
		events("Scanning $Workpath/$basename");
		HAPROXY_LOG_PARSE("$Workpath/$basename");
	}
	
}

function HAPROXY_LOG_PARSE($workfile){
	$backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/access-backup";
	$faildir="{$GLOBALS["LogFileDeamonLogDir"]}/access-failed";
	
	@mkdir($backupdir,0755,true);
	@mkdir($faildir,0755,true);
	
	$handle = @fopen($workfile, "r");
	if (!$handle) {events("Fopen failed on $workfile");return false;}
	
	//if($LastScannLine>0){fseek($handle, $LastScannLine, SEEK_SET);}
	$CZ=0;
	while (!feof($handle)){
		$CZ++;
		$buffer =trim(fgets($handle));
		if($buffer==null){continue;}
		$array=unserialize($buffer);
		$zDate=date("Y-m-d H:i:00",$array["TIME"]);
		$ipsrc=$array["ipsrc"];
		$servicename=$array["servicename"];
		$size=$array["BYTES"];
		$remote_host=$array["remote_host"];
		if($remote_host=="-"){$remote_host="0.0.0.0";}
		$HTTP_CODE=$array["HTTP_CODE"];
		$TS=$array["TS"];
		
		$md5=md5("$zDate$ipsrc$servicename$remote_host$HTTP_CODE$TS");
		if(!isset($MAIN[$md5])){
			$MAIN[$md5]["zDate"]=$zDate;
			$MAIN[$md5]["ipsrc"]=$ipsrc;
			$MAIN[$md5]["rqs"]=1;
			$MAIN[$md5]["servicename"]=$servicename;
			$MAIN[$md5]["size"]=$size;
			$MAIN[$md5]["remote_host"]=$remote_host;
			$MAIN[$md5]["http_code"]=$HTTP_CODE;
			$MAIN[$md5]["ts"]=$TS;
		}else{
			$MAIN[$md5]["size"]=$MAIN[$md5]["size"]+$size;
			$MAIN[$md5]["rqs"]=$MAIN[$md5]["rqs"]+1;
		}
		
		
		
		
	}
	
	
	
	$prefix="INSERT INTO haproxy_log (zDate,ipsrc,servicename,backend,http_code,rqs,size,ts,proxyname) VALUES ";
	$q=new postgres_sql();
	
	events("Analyze ".count($MAIN)." events");
	while (list ($md5, $subarray) = each ($MAIN)){
		$zDate=$subarray["zDate"];
		$ipsrc=$subarray["ipsrc"];
		$rqs=$subarray["rqs"];
		$servicename=$subarray["servicename"];
		$size=$subarray["size"];
		$remote_host=$subarray["remote_host"];
		$http_code=$subarray["http_code"];
		$ts=$subarray["ts"];
		
		$f[]="('$zDate','$ipsrc','$servicename','$remote_host','$http_code','$rqs','$size','$ts','{$GLOBALS["MYHOSTNAME_PROXY"]}')";
		
		if(count($f)>500){
			events("Injecting 501 elements");
			$q->QUERY_SQL("$prefix ".@implode(",", $f));
			if(!$q->ok){
				events("$q->mysql_error");
				@copy($workfile, $faildir."/".basename($workfile));
				@unlink($workfile);
				return false;
			}
			
			$f=array();
		}
		
		
	}
	
	if(count($f)>0){
		events("Injecting ".count($f)." elements");
		$q->QUERY_SQL("$prefix ".@implode(",", $f));
		if(!$q->ok){
			events("$q->mysql_error");
			@copy($workfile, $faildir."/".basename($workfile));
			@unlink($workfile);
			return false;
		}
	}
	events("Move $workfile to $backupdir");
	@copy($workfile, $backupdir."/".basename($workfile));
	@unlink($workfile);
	
	
}
function events($text=null){
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();

		if(isset($trace[0])){
			$file=basename($trace[0]["file"]);
			$function=$trace[0]["function"];
			$line=$trace[0]["line"];
		}

		if(isset($trace[1])){
			$file=basename($trace[1]["file"]);
			$function=$trace[1]["function"];
			$line=$trace[1]["line"];
		}



	}

	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];

	$logFile="/var/log/artica-parse.hourly.log";
	$mem=round(((memory_get_usage()/1024)/1000),2);

	$suffix=date("Y-m-d H:i:s")." [".basename(__FILE__)."/$function/$line]:";
	if($GLOBALS["VERBOSE"]){echo "$suffix $text memory {$mem}MB (system load $internal_load)\n";}

	if (is_file($logFile)) {
		$size=filesize($logFile);
		if($size>1000000){@unlink($logFile);}
	}
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$suffix $text\n");
	@fclose($f);
}


