<?php
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.compile.ufdbguard.inc');

if(system_is_overloaded(basename(__FILE__))){
	echo "Overloaded\n";
	die();
}

if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--monit#",implode(" ",$argv),$re)){$GLOBALS["MONIT"]=true;}
if(preg_match("#--watchdog#",implode(" ",$argv),$re)){$GLOBALS["WATCHDOG"]=true;$GLOBALS["FORCE"]=true;}
if(preg_match("#--ufdbtail#",implode(" ",$argv),$re)){$GLOBALS["UFDBTAIL"]=true;$GLOBALS["FORCE"]=true;}
if(preg_match("#--framework#",implode(" ",$argv),$re)){$GLOBALS["FRAMEWORK"]=true;$GLOBALS["FORCE"]=true;}
if(preg_match("#--noupdate#",implode(" ",$argv),$re)){$GLOBALS["NOUPDATE"]=true;}

if($argv[1]=="--meta"){meta();exit;}

xstart();

function xstart(){
	
	$unix=new unix();
	$sock=new sockets();
	$GLOBALS["CLASS_SOCKETS"]=$sock;
	$FORCED_TEXT=null;
	$NOTIFY=false;
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/usr/share/artica-postfix/ressources/logs/ARTICA_DBS_STATUS_FULL.db";
	$pid=$unix->get_pid_from_file($pidfile);
	$GLOBALS["CLASS_UNIX"]=$unix;
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		echo "Already executed\n";
		return;
	}
	
	

	
	
	if(!$GLOBALS["FORCE"]){
		if($unix->file_time_min($pidTime)<30){return;}
	}
	
	$GLOBALS["MAIN_ARRAY"]=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/ARTICA_DBS_STATUS.db"));
	ArticaWebFilter();
	ArticaUfdb();
	
	@mkdir("/usr/share/artica-postfix/ressources/logs",0755,true);
	@unlink($pidTime);
	@file_put_contents($pidTime, serialize($GLOBALS["MAIN_ARRAY"]));
	if($GLOBALS["VERBOSE"]){echo "Saving $pidTime\n";}
	@chmod($pidTime, 0755);
}



function ArticaWebFilter(){
	
	$STATUS=unserialize(@file_get_contents("/etc/artica-postfix/ARTICAUFDB_LAST_DOWNLOAD"));
	$GLOBALS["MAIN_ARRAY"]["CAT_ARTICA_SIZE"]=trim(@file_get_contents("/etc/artica-postfix/CAT_ARTICA_DB_SIZE"));
	$GLOBALS["MAIN_ARRAY"]["CAT_ARTICA_SINCE"]=$GLOBALS["CLASS_UNIX"]->distanceOfTimeInWords($STATUS["LAST_DOWNLOAD"]["TIME"],time());
	$GLOBALS["MAIN_ARRAY"]["CAT_ARTICA_LAST_CAT"]=$STATUS["LAST_DOWNLOAD"]["CATEGORY"];
	$GLOBALS["MAIN_ARRAY"]["CAT_ARTICA_LAST_SIZE"]=$STATUS["LAST_DOWNLOAD"]["SIZE"];
	$GLOBALS["MAIN_ARRAY"]["CAT_ARTICA_LAST_ERROR"]=$STATUS["LAST_DOWNLOAD"]["FAILED"];
	$GLOBALS["MAIN_ARRAY"]["CAT_ARTICA_LAST_CHECK"]=$STATUS["LAST_DOWNLOAD"]["LAST_CHECK"];
	$GLOBALS["MAIN_ARRAY"]["CAT_ARTICA_MAX"]=145;
	$GLOBALS["MAIN_ARRAY"]["CAT_ARTICA_COUNT"]=0;
	$GLOBALS["MAIN_ARRAY"]["CAT_ARTICA_PRC"]=0;
	$REMOTE_CACHE=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/artica-webfilter-db-index.txt")));
	
	$CATZ_ARRAY_FILE=CATZ_ARRAY();
	if($GLOBALS["VERBOSE"]){echo "CATZ_ARRAY_FILE...$CATZ_ARRAY_FILE\n";}
	$CATZ_ARRAY=unserialize(base64_decode(@file_get_contents(CATZ_ARRAY())));
	$q=new mysql_squid_builder();
	
	///etc/artica-postfix/ufdbartica.txt
	
	$GLOBALS["MAIN_ARRAY"]["ARTICA_DB_TIME"]=$CATZ_ARRAY["TIME"];
	
	$CountDecategories=0;
		if(is_array($CATZ_ARRAY)){
		
		while (list ($table, $items) = each ($CATZ_ARRAY) ){
			$CategoryName=$q->tablename_tocat($table);
			if(!is_file("/var/lib/ufdbartica/$table/domains.ufdb")){
				if($GLOBALS["VERBOSE"]){echo "$table no such db\n";}
				continue;
			}
			$items=intval($items);
			$GLOBALS["MAIN_ARRAY"]["CAT_ARTICAT_ARRAY"][$CategoryName]["ITEMS"]=$items;
			$GLOBALS["MAIN_ARRAY"]["CAT_ARTICAT_ARRAY"][$CategoryName]["SIZE"]=@filesize("/var/lib/ufdbartica/$table/domains.ufdb");
			$GLOBALS["MAIN_ARRAY"]["CAT_ARTICAT_ARRAY"][$CategoryName]["TIME"]=@filemtime("/var/lib/ufdbartica/$table/domains.ufdb");
			$CountDecategories=$CountDecategories+$items;
			if($GLOBALS["VERBOSE"]){echo "$table - $items = $CountDecategories\n";}
		}
	}
	
	$GLOBALS["MAIN_ARRAY"]["CAT_ARTICA_ITEMS_NUM"]=$CountDecategories;
	
	$WORKDIR="/var/lib/ufdbartica";
	$MAX=count($REMOTE_CACHE);
	$c=0;
	$prc=0;
	$GLOBALS["MAIN_ARRAY"]["CAT_ARTICA_MAX"]=$MAX;
	$GLOBALS["MAIN_ARRAY"]["CAT_ARTICA_COUNT"]=0;
	$GLOBALS["MAIN_ARRAY"]["CAT_ARTICA_PRC"]=0;
	while (list ($tablename, $size) = each ($REMOTE_CACHE) ){
		$destfile="$WORKDIR/$tablename/domains.ufdb";
		$size=@filesize($destfile);
		if($size<10){continue;}
		$c++;
		$prc=intval($c)/intval($MAX);
		$prc=round($prc*100);
		$GLOBALS["MAIN_ARRAY"]["CAT_ARTICA_COUNT"]=$c;
		$GLOBALS["MAIN_ARRAY"]["CAT_ARTICA_PRC"]=$prc;
		if($GLOBALS["VERBOSE"]){echo "ArticaWebFilter:: $destfile $c / $MAX = {$prc}% {$size}Bytes\n";}
	}

	if($GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/CAT_ARTICA_DB_SIZE")>60){
		$CAT_ARTICA_DB_SIZE=$GLOBALS["CLASS_UNIX"]->DIRSIZE_KO("/var/lib/ufdbartica");
		@unlink("/etc/artica-postfix/CAT_ARTICA_DB_SIZE");
		@file_put_contents("/etc/artica-postfix/CAT_ARTICA_DB_SIZE", $CAT_ARTICA_DB_SIZE);
	}



	$STATUS=unserialize(@file_get_contents("/etc/artica-postfix/ARTICAUFDB_LAST_DOWNLOAD"));
	$GLOBALS["MAIN_ARRAY"]["CAT_ARTICA_SIZE"]=trim(@file_get_contents("/etc/artica-postfix/CAT_ARTICA_DB_SIZE"));
	$GLOBALS["MAIN_ARRAY"]["CAT_ARTICA_SINCE"]=$GLOBALS["CLASS_UNIX"]->distanceOfTimeInWords($STATUS["LAST_DOWNLOAD"]["TIME"],time());
	$GLOBALS["MAIN_ARRAY"]["CAT_ARTICA_LAST_CAT"]=$STATUS["LAST_DOWNLOAD"]["CATEGORY"];
	$GLOBALS["MAIN_ARRAY"]["CAT_ARTICA_LAST_SIZE"]=$STATUS["LAST_DOWNLOAD"]["SIZE"];
	$GLOBALS["MAIN_ARRAY"]["CAT_ARTICA_LAST_ERROR"]=$STATUS["LAST_DOWNLOAD"]["FAILED"];
	$GLOBALS["MAIN_ARRAY"]["CAT_ARTICA_LAST_CHECK"]=$STATUS["LAST_DOWNLOAD"]["LAST_CHECK"];
	
}

function meta(){
	
	print_r(unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/artica-webfilter-db-index.txt"))));;
}

function CATZ_ARRAY(){
	
	$f[]="/usr/share/artica-postfix/ressources/logs/web/cache/CATZ_ARRAY";
	$f[]="/home/artica/categories_databases/CATZ_ARRAY";
	$f[]="/etc/artica-postfix/artica-webfilter-db-index.txt";
	
	while (list ($index, $line) = each ($f) ){
		if(is_file($line)){return $line;}
	}
	
	
	
	
}

function ArticaUfdb(){
	$DB=array();
	$unix=new unix();
	$FILEDBS=array();
	$prc=0;
	$GLOBALS["MAIN_ARRAY"]["TLSE_PRC"]=0;
	
	if($GLOBALS["CLASS_SOCKETS"]->EnableUfdbGuard()==0){
		if($GLOBALS["VERBOSE"]){echo "EnableUfdbGuard report false\n";}
		$GLOBALS["MAIN_ARRAY"]["TLSE_ENABLED"]=0;
		
	}

	
	
	if(is_file("/etc/artica-postfix/univtoulouse-global_usage")){
		$contentF=explode("\n",@file_get_contents("/etc/artica-postfix/univtoulouse-global_usage"));
		while (list ($index, $line) = each ($contentF) ){
			if(preg_match("#NAME:\s+(.+)#", $line,$re)){
				$DB[trim($re[1])]=trim($re[1]);
			}
		}
	}
	
	$Dirs=$unix->dirdir("/var/lib/ftpunivtlse1fr");
	while (list ($Dir, $line) = each ($Dirs) ){
		if(!is_file("$Dir/domains.ufdb")){continue;}
		$DB[basename($Dir)]=true;
	}
	
	
	
	
	if(count($DB)>0){
		$q=new mysql_squid_builder();
		$TLSE_CONVERTION=$q->TLSE_CONVERTION();
		while (list ($TLSE, $line) = each ($DB) ){
			$catzname=$TLSE_CONVERTION[$TLSE];
			if($catzname==null){
				if($GLOBALS["VERBOSE"]){echo "Unable to understand $TLSE\n";}
				continue;
			}
			$catzname=str_replace("/", "_", $catzname);
			$FILEDBS[$catzname]="/var/lib/ftpunivtlse1fr/$catzname/domains.ufdb";
			$FILEDBSC[]="/var/lib/ftpunivtlse1fr/$catzname/domains";
				
		}

	}
	if(count($FILEDBS)>0){
		$c=0;

		$MAX=count($FILEDBS);

		while (list ($table, $path) = each ($FILEDBS) ){
			if(!is_file($path)){
				echo "$path such file\n";continue;}
				$size=@filesize($path);
				if($size<10){continue;}
				$c++;
				$prc=intval($c)/intval($MAX);
				$prc=round($prc*100);
				$GLOBALS["MAIN_ARRAY"]["TLSE_COUNT"]=$c;
				$TLSE_COUNTZ[$table]["SIZE"]=$size;
				if($GLOBALS["VERBOSE"]){echo "COUNT OF $path\n";}
				$path_db1=dirname($path)."/"."domains";
				$TLSE_COUNTZ[$table]["ITEMS"]=$unix->COUNT_LINES_OF_FILE($path_db1);
				$TLSE_COUNTZ[$table]["TIME"]=filemtime($path);

		}

	}

	if($GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/UNIVTLSE_STAT_DB_SIZE")>60){
		$UNIVTLSE_STAT_DB_SIZE=$GLOBALS["CLASS_UNIX"]->DIRSIZE_KO("/var/lib/ftpunivtlse1fr");
		@unlink("/etc/artica-postfix/UNIVTLSE_STAT_DB_SIZE");
		@file_put_contents("/etc/artica-postfix/UNIVTLSE_STAT_DB_SIZE", $UNIVTLSE_STAT_DB_SIZE);
	}

	$C=0;
	if($GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/UNIVTLSE_STAT_DB_ITEMS")>60){
		while (list ($table, $path) = each ($FILEDBSC) ){
			$unix=new unix();
			$C=$C+intval($unix->COUNT_LINES_OF_FILE($path));
				
		}
		@unlink("/etc/artica-postfix/UNIVTLSE_STAT_DB_ITEMS");
		@file_put_contents("/etc/artica-postfix/UNIVTLSE_STAT_DB_ITEMS", $C);
	}


	$STATUS=unserialize(@file_get_contents("/etc/artica-postfix/TLSE_LAST_DOWNLOAD"));
	if(!isset($STATUS["LAST_DOWNLOAD"])){
		$STATUS["LAST_DOWNLOAD"]=array();
	}
	
	
	$GLOBALS["MAIN_ARRAY"]["TLSE_PRC"]=$prc;
	$GLOBALS["MAIN_ARRAY"]["TLSE_STAT_SIZE"]=trim(@file_get_contents("/etc/artica-postfix/UNIVTLSE_STAT_DB_SIZE"));
	$GLOBALS["MAIN_ARRAY"]["TLSE_STAT_ITEMS"]=trim(@file_get_contents("/etc/artica-postfix/UNIVTLSE_STAT_DB_ITEMS"));
	$GLOBALS["MAIN_ARRAY"]["TLSE_LAST_SINCE"]=$GLOBALS["CLASS_UNIX"]->distanceOfTimeInWords($STATUS["LAST_DOWNLOAD"]["TIME"],time());
	$GLOBALS["MAIN_ARRAY"]["TLSE_LAST_CAT"]=$STATUS["LAST_DOWNLOAD"]["CATEGORY"];
	$GLOBALS["MAIN_ARRAY"]["TLSE_LAST_SIZE"]=$STATUS["LAST_DOWNLOAD"]["SIZE"];
	$GLOBALS["MAIN_ARRAY"]["TLSE_LAST_CHECK"]=$STATUS["LAST_CHECK"];
	$GLOBALS["MAIN_ARRAY"]["TLSE_ARRAY"]=$TLSE_COUNTZ;
	if($GLOBALS["VERBOSE"]){print_r($GLOBALS["MAIN_ARRAY"]);}
	
}