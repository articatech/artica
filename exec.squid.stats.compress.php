<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="vsFTPD Daemon";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__)."/ressources/class.postgres.inc");

if($argv[1]=="--tables"){create_tables();exit;}
if($argv[1]=="--compress-year"){compressToYear();exit;}
compressToMonth();



function create_tables(){
	$q=new postgres_sql();
	$q->CREATE_TABLES();
}

function compressToMonth(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/SquidStatsMonthQueue.pid";
	$pidTime="/etc/artica-postfix/pids/SquidStatsMonthQueue.time";
	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){die("Already executed");}
	
	@file_put_contents($pidfile, getmypid());
	$timeExec=$unix->file_time_min($pidTime);
	
	if($timeExec<15){die("Only Each 15mn");}
	@unlink($pidfile);
	@file_put_contents($pidfile, time());
	
	
	$q=new postgres_sql();
	if($q->isRemote){return;}
	
	@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidStatsMonthQueue", 0);
	
	if(system_is_overloaded()){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidStatsMonthQueue", 1);
		squid_admin_mysql(0, "Overloaded system {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting task", null,__FILE__,__LINE__);
		return;
	}
	
	$Curday=date("Y-m-d");
	$sql="SELECT date_trunc('day',zdate)  as zdate FROM access_log GROUP BY  date_trunc('day',zdate) ORDER BY zdate";
	$q=new postgres_sql();
	$q->CREATE_TABLES();
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){echo $q->mysql_error."\n";}
	
	$SOURCES_DAY=array();
	while($ligne=@pg_fetch_assoc($results)){
		$day=date("Y-m-d",strtotime($ligne["zdate"]));
		if($Curday==$day){continue;}
		$SOURCES_DAY[$day]=true;
		
	}
	
	$sql="SELECT date_trunc('day',zdate)  as zdate FROM access_month GROUP BY  date_trunc('day',zdate) ORDER BY zdate";
	$q=new postgres_sql();
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n";}
	
	$DEST_DAY=array();
	while($ligne=@pg_fetch_assoc($results)){
		$day=date("Y-m-d",strtotime($ligne["zdate"]));
		if($Curday==$day){continue;}
		$DEST_DAY[$day]=true;
	}	
	
	
	
	$CountOfSourcesDay=count($SOURCES_DAY);
	$c=0;
	$OverLoadedCount=0;
	$OverLoadedMax=round($OverLoadedCount/2);
	while (list ($Day, $ligne) = each ($SOURCES_DAY) ){
		if(isset($DEST_DAY[$Day])){
			echo "$Day already done\n";
			continue;	
		}
		
		echo "$Day not imported done\n";
		
		$c++;
		if(!compress_day($Day)){@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidStatsMonthQueue", 1);return;}
		if(system_is_overloaded()){
			$OverLoadedCount++;
			if($OverLoadedCount>$OverLoadedMax){
				squid_admin_mysql(0, "Overloaded system {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting tasks after compressing $c days", null,__FILE__,__LINE__);
				@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidStatsMonthQueue", 1);
				return;
			}
		}
		
	}
	
	if(!is_file("/etc/artica-postfix/pids/SquidStatsYearQueue.pid")){create_tables();compressToYear(true);}
	
}

function compress_day($day){
	
	$catz=new mysql_catz();
	$q=new postgres_sql();
	$day="$day 00:00:00";
	
	$sql="SELECT SUM(size) as size, SUM(rqs) as rqs,ipaddr,proxyname,category,familysite,userid,mac
	FROM access_log WHERE date_trunc('day',zdate)='$day' GROUP by ipaddr,proxyname,category,familysite,userid,mac";
	
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n";return;}
	$rows=pg_num_rows($results);
	echo "Compressing $day $rows rows\n";
	
	$pref="INSERT INTO access_month (zdate,size,rqs,familysite,category,userid,ipaddr,mac,proxyname) VALUES ";
	$f=array();
	$c=0;
	while($ligne=@pg_fetch_assoc($results)){
		$size=$ligne["size"];
		$rqs=$ligne["rqs"];
		$familysite=$ligne["familysite"];
		$category=$ligne["category"];
		$userid=$ligne["userid"];
		$ipaddr=$ligne["ipaddr"];
		$mac=$ligne["mac"];
		$proxyname=$ligne["proxyname"];
		if(preg_match("#,USERID=#", $category)){$category=null;}
		if($category==null){$category=$catz->GET_CATEGORIES($familysite);}
		if(preg_match("#IPADDR=#", $userid)){$userid=null;}
		$c++;
		$f[]="('$day','$size','$rqs','$familysite','$category','$userid','$ipaddr','$mac','$proxyname')";
		
		
		
		if(count($f)>800){
			$q->QUERY_SQL($pref.@implode(",", $f));
			if(!$q->ok){echo $q->mysql_error."\n";return;}
			$f=array();
			
		}
		
		
	}
	
	if(count($f)>0){
		$q->QUERY_SQL($pref.@implode(",", $f));
		if(!$q->ok){echo $q->mysql_error."\n";return;}
		$f=array();
			
	}
	squid_admin_mysql(2, "$day was compressed in table access_month with $c elements", null,__FILE__,__LINE__);
	return true;
	
}


function compressToYear($aspid=false){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/SquidStatsYearQueue.pid";
	$pidTime="/etc/artica-postfix/pids/SquidStatsYearQueue.time";
	
	if(!$aspid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){die("Already executed");}
		
		@file_put_contents($pidfile, getmypid());
		$timeExec=$unix->file_time_min($pidTime);
		
		if($timeExec<15){die("Only Each 15mn");}
		@unlink($pidfile);
		@file_put_contents($pidfile, time());
	}
	
	$q=new postgres_sql();
	if($q->isRemote){return;}
	
	
	@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidStatsYearQueue", 0);
	
	if(system_is_overloaded()){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidStatsYearQueue", 1);
		squid_admin_mysql(0, "Overloaded system {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting task", null,__FILE__,__LINE__);
		return;
	}
	
	
	$Curday=date("Y-m-d 00:00:00");
	$sql="SELECT date_trunc('month',zdate)  as zdate FROM access_month GROUP BY  date_trunc('month',zdate) ORDER BY zdate";
	$q=new postgres_sql();
	$q->CREATE_TABLES();
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){echo $q->mysql_error."\n";}
	
	$SOURCES_MONTH=array();
	while($ligne=@pg_fetch_assoc($results)){
		$day=$ligne["zdate"];
		if($Curday==$day){continue;}
		$SOURCES_MONTH[$day]=true;
	
	}
	
	$sql="SELECT date_trunc('month',zdate)  as zdate FROM access_year GROUP BY  date_trunc('month',zdate) ORDER BY zdate";
	$q=new postgres_sql();
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n";}
	
	$DEST_MONTH=array();
	while($ligne=@pg_fetch_assoc($results)){
		$day=$ligne["zdate"];
		if($Curday==$day){continue;}
		$DEST_MONTH[$day]=true;
	}
	
	
	
	$CountOfSourcesDay=count($SOURCES_MONTH);
	$c=0;
	$OverLoadedCount=0;
	$OverLoadedMax=round($OverLoadedCount/2);
	while (list ($Day, $ligne) = each ($SOURCES_MONTH) ){
		if(isset($DEST_MONTH[$Day])){echo "$Day already done\n";continue;}
		echo "$Day not imported done\n";
	
		$c++;
		if(!compress_month_perform($Day)){@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidStatsYearQueue", 1);return;}
		if(system_is_overloaded()){
			$OverLoadedCount++;
			if($OverLoadedCount>$OverLoadedMax){
			squid_admin_mysql(0, "Overloaded system {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting tasks after compressing $c days", null,__FILE__,__LINE__);
			@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidStatsYearQueue", 1);return;}
		}
	
	}
	
	
	
	
}

function compress_month_perform($day){

	$catz=new mysql_catz();
	$q=new postgres_sql();
	

	$sql="SELECT SUM(size) as size, SUM(rqs) as rqs,ipaddr,proxyname,category,familysite,userid,mac
	FROM access_month WHERE date_trunc('month',zdate)='$day' GROUP by ipaddr,proxyname,category,familysite,userid,mac";


	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n";return;}
	$rows=pg_num_rows($results);
	echo "Compressing $day $rows rows\n";

	$pref="INSERT INTO access_year (zdate,size,rqs,familysite,category,userid,ipaddr,mac,proxyname) VALUES ";
	$f=array();
	$c=0;
	while($ligne=@pg_fetch_assoc($results)){
		$size=$ligne["size"];
		$rqs=$ligne["rqs"];
		$familysite=$ligne["familysite"];
		$category=$ligne["category"];
		$userid=$ligne["userid"];
		$ipaddr=$ligne["ipaddr"];
		$mac=$ligne["mac"];
		$proxyname=$ligne["proxyname"];
		if(preg_match("#,USERID=#", $category)){$category=null;}
		if($category==null){$category=$catz->GET_CATEGORIES($familysite);}
		if(preg_match("#IPADDR=#", $userid)){$userid=null;}
		$c++;
		$f[]="('$day','$size','$rqs','$familysite','$category','$userid','$ipaddr','$mac','$proxyname')";



		if(count($f)>800){
			$q->QUERY_SQL($pref.@implode(",", $f));
			if(!$q->ok){echo $q->mysql_error."\n";return;}
			$f=array();
				
		}


	}

	if(count($f)>0){
		$q->QUERY_SQL($pref.@implode(",", $f));
		if(!$q->ok){echo $q->mysql_error."\n";return;}
		$f=array();
			
	}
	squid_admin_mysql(2, "$day was compressed in table access_month with $c elements", null,__FILE__,__LINE__);
	return true;

}

