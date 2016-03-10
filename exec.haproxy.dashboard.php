<?php
if(is_file("/usr/bin/cgclassify")){if(is_dir("/cgroups/blkio/php")){shell_exec("/usr/bin/cgclassify -g cpu,cpuset,blkio:php ".getmypid());}}
$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
if($EnableIntelCeleron==1){die("EnableIntelCeleron==1\n");}
$GLOBALS["BYPASS"]=true;
$GLOBALS["DEBUG_INFLUX_VERBOSE"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["DEBUG_MEM"]=false;
$GLOBALS["NODHCP"]=true;
$GLOBALS["PROGRESS"]=false;
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
	if(preg_match("#--progress#",implode(" ",$argv))){$GLOBALS["PROGRESS"]=true;}
}
if($GLOBALS["VERBOSE"]){
		ini_set('display_errors', 1);	
		ini_set('html_errors',0);
		ini_set('display_errors', 1);
		ini_set('error_reporting', E_ALL);
}

if($GLOBALS["VERBOSE"]){"echo Loading...\n";}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.artica-meta.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.stats-appliance.inc');
include_once(dirname(__FILE__).'/ressources/class.parse.berekley.inc');
include_once(dirname(__FILE__)."/ressources/class.realtime-buildsql.inc");
include_once(dirname(__FILE__)."/ressources/class.influx.inc");
$date=date("YW");


if(systemMaxOverloaded()){
	events("FATAL! overloaded system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]} aborting task");
	squid_admin_mysql(0, "Overloaded system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]} aborting task", 
	null,__FILE__,__LINE__);
	die();
}


// --meta \"$TEMP_DIR/squidqsize.$uuid.db\" $uuid
if($argv[1]=="--meta"){parse_meta($argv[2],$argv[3]);exit;}
if($argv[1]=="--size"){die();}
if($argv[1]=="--stats-app"){parse_stats();exit;}
if($argv[1]=="--month"){die();}
if($argv[1]=="--cached"){exit;}
if($argv[1]=="--cache-or-not"){events("Running directly Cache or not (CRON)");CachedOrNot();exit;}
if($argv[1]=="--rqs"){die();exit;}
if($argv[1]=="--stats-apps-clients"){stats_apps_clients();exit;}
if($argv[1]=="--flux-rqs"){FLUX_RQS();exit;}
if($argv[1]=="--members-count"){$GLOBALS["OUTPUT"]=true;exit;}
if($argv[1]=="--usersagents"){USERAGENTS();exit;}
if($argv[1]=="--famsites"){FAMILY_SITES_DAY();exit;}
if($argv[1]=="--maxmin"){MAX_MIN();exit;}
if($argv[1]=="--webfilter"){WEBFILTERING();exit;}
if($argv[1]=="--flux-hour"){events("Running directly Hour flow (CRON)");FLUX_HOUR(true);exit;}
if($argv[1]=="--backup-size"){backup_size();exit;}
if($argv[1]=="--members-graph"){$GLOBALS["OUTPUT"]=true;exit;}
if($argv[1]=="--clean"){squidhour_clean();exit;}
if($argv[1]=="--dump-hour"){DUMP_HOUR();exit;}
if($argv[1]=="--dump-users"){FULL_USERS_DAY();exit;}
if($argv[1]=="--cache-avg"){CACHES_AVG();exit;}


parse();

function build_progress($text,$pourc){
	$cachefile="/usr/share/artica-postfix/ressources/logs/admin.refresh.progress";
	echo "{$pourc}% $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	events("{$pourc}% $text");
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

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
	$logFile="/var/log/artica-parse.hourly.log";
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];
	$suffix=date("Y-m-d H:i:s")." [".basename(__FILE__)."/$function/$line]:";


	if (is_file($logFile)) {
		$size=filesize($logFile);
		if($size>1000000){@unlink($logFile);}
	}
	$f = @fopen($logFile, 'a');
	if($GLOBALS["VERBOSE"]){echo "$suffix $text (system load:{$internal_load})\n";}
	@fwrite($f, "$suffix $text (system load:{$internal_load})\n");
	@fclose($f);
}


function parse(){
	$TimeFile="/usr/share/artica-postfix/ressources/interface-cache/HAPROXY_DAY";
	$pidfile="/etc/artica-postfix/pids/exec.haproxy.dashboard.php.pid";
	$unix=new unix();
	$sock=new sockets();
	
	
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["VERBOSE"]){echo "$pid already executed since {$timepid}Mn\n";}
		if(!$GLOBALS["FORCE"]){
			if($timepid<14){return;}
			$kill=$unix->find_program("kill");
			unix_system_kill_force($pid);
		}
	}
	
	@file_put_contents($pidfile, getmypid());
	if(!$GLOBALS["FORCE"]){
	if(!$GLOBALS["VERBOSE"]){
		$time=$unix->file_time_min($TimeFile);
		if($time<14){
			echo "Current {$time}Mn, require at least 14mn\n";
			return;
		}
	}}
	
	
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	$sock=new sockets();
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	
	
	events("Proxy performance set to $SquidPerformance");
	build_progress("{refresh_dashboard_values}",10);
	haproxy_courbe_day();
	build_progress("{refresh_dashboard_values}",20);
	haproxy_courbe_rqs_day();
	build_progress("{refresh_dashboard_values}",100);
	
	
	
	
	return;
	system_values();
	$php=$unix->LOCATE_PHP5_BIN();
	
	build_progress("{refresh_dashboard_values}",11);
	$dateint=InfluxQueryFromUTC(strtotime("-48 hours"));
	$date=date("Y-m-d H:00:00",$dateint);
	$qSimple=new mysql();
	$sql="SELECT COUNT(ID) as tcount FROM squid_admin_mysql WHERE severity=0 AND zDate>'$date'";
	$ligne=mysql_fetch_array($qSimple->QUERY_SQL($sql,"artica_events"));
	@file_put_contents("{$GLOBALS["BASEDIR"]}/WATCHDOG_COUNT_EVENTS", $ligne["tcount"]);
	@chmod("{$GLOBALS["BASEDIR"]}/WATCHDOG_COUNT_EVENTS", 0777);
	
	build_progress("{refresh_dashboard_values} (2)",11);
	APACHE_STATISTICS();
	NETWORK_INTERFACES_RXTX();
	COUNT_OF_SURICATA();
	
	
	
	$SQUIDEnable=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SQUIDEnable"));
	if($SQUIDEnable==0){
		build_progress("{done}",100);
		return;
	}
	
	if($SquidPerformance>1){
		if(is_file("/etc/cron.d/artica-stats-hourly")){
			@unlink("/etc/cron.d/artica-stats-hourly");
			system("/etc/init.d/cron reload");
		}
		build_progress("{statistics_are_disabled}",110);
		die();
	}
	
	
	if(!is_file("/etc/cron.d/artica-stats-hourly")){@unlink("/etc/cron.d/artica-stats-hourly");}

	
	
	@mkdir("/usr/share/artica-postfix/ressources/interface-cache",0755,true);
	$t1=time();
	
	$q=new mysql_squid_builder();
	$tables[]="dashboard_size_day";
	$tables[]="dashboard_countwebsite_day";
	$tables[]="dashboard_countuser_day";
	$tables[]="dashboard_user_day";
	$tables[]="dashboard_notcached";
	$tables[]="dashboard_cached";
	$tables[]="dashboard_blocked_day";
	
	
	while (list ($num, $table) = each ($tables) ){
		if(!$q->TABLE_EXISTS($table)){events("Table: $table is not yet ready...");continue;}
		$NUM=$q->COUNT_ROWS($table);
		events("Table: $table $NUM rows");
	}
	
	build_progress("{calculate_cache_rate}",12);
	CachedOrNot();
	squidhour_clean();
	$t1=time();
	
	
	$influx=new influx();
	$now=InfluxQueryFromUTC(strtotime("-24 hour"));
	
	build_progress("{refresh_dashboard_values}",13);
// -----------------------------------------------------------------------------------------------------	
	build_progress("{refresh_dashboard_values}",14);

// -----------------------------------------------------------------------------------------------------	
	build_progress("{cleaning_databases}",16);
	squidhour_clean();
	build_progress("{refresh_dashboard_values}",17);	
	FLUX_RQS();
	build_progress("{refresh_dashboard_values}",18);
	build_progress("{refresh_dashboard_values}",19);
	//USERAGENTS();
	build_progress("{calculate_dates}",20);
	MAX_MIN();
	backup_size();
	build_progress("{refresh_dashboard_values}",21);
	WEBFILTERING();
	build_progress("{refresh_dashboard_values}",22);
	$f=array();

// -----------------------------------------------------------------------------------------------------
	$q=new mysql_squid_builder();
	$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(*) as tcount FROM proxy_ports WHERE enabled=1 AND transparent=1 AND Tproxy=1"));
	if($q->ok){
		@file_put_contents("{$GLOBALS["BASEDIR"]}/COUNT_DE_TRANSPARENT",intval($ligne["tcount"]));
	}
// -----------------------------------------------------------------------------------------------------	
	
	build_progress("{refresh_dashboard_values}",51);
	$MAIN=array();
	$xdata=array();
	$ydata=array();
	$f=array();	
	
	// -----------------------------------------------------------------------------------------------------
	// Calcul des caches en cours.
	
	$SquidCacheLevel=$sock->GET_INFO("SquidCacheLevel");
	if(!is_numeric($SquidCacheLevel)){$SquidCacheLevel=4;}
	
	if($SquidCacheLevel==0){
		@file_put_contents("{$GLOBALS["BASEDIR"]}/COUNT_DE_CACHES",0);
	}
	
	build_progress("{refresh_dashboard_values}",52);
	$q=new mysql();
	$sql="SELECT cache_size,cache_type FROM squid_caches_center WHERE remove=0";
	$xsize=0;
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$cache_size=$ligne["cache_size"];
		$cache_type=$ligne["cache_type"];
		if($cache_type=="Cachenull"){continue;}
		$xsize=$xsize+$cache_size;
	}
	
	if($GLOBALS["VERBOSE"]){echo "COUNT_DE_CACHES: {$xsize}MB\n";}
	@file_put_contents("{$GLOBALS["BASEDIR"]}/COUNT_DE_CACHES",$xsize);
	
	
	if($GLOBALS["PROGRESS"]){
		build_progress("{refresh_dashboard_values}",90);
		system("$php /usr/share/artica-postfix/exec.status.php --all --verbose");
		
	}
	
	build_progress("{refresh_dashboard_values} {done}",100);
	
	// -----------------------------------------------------------------------------------------------------	
}

function haproxy_courbe_day(){
	$now=date("Y-m-d H:i:s",strtotime("-24 hour"));
	$q=new postgres_sql();
	$sql="select sum(size) as size, date_trunc('hour', zdate) as zdate FROM haproxy_log WHERE zdate > '$now' GROUP BY date_trunc('hour', zdate) ORDER BY zdate ASC;";
	if($GLOBALS["VERBOSE"]){echo "\n*****\n$sql\n******\n";}
	$MAIN=array();
	$xdata=array();
	$ydata=array();
	$results=$q->QUERY_SQL($sql);
	
	while($ligne=@pg_fetch_assoc($results)){
			
		$min=$ligne["zdate"];
		$size=intval($ligne["size"])/1024;
		$size=$size/1024;
		if(round($size)==0){continue;}
		echo "$min -> {$size}MB\n";
		$xdata[]=$min;
		$ydata[]=round($size);
	}
	$MAIN["xdata"]=$xdata;
	$MAIN["ydata"]=$ydata;
	@file_put_contents("{$GLOBALS["BASEDIR"]}/HAPROXY_DAY", serialize($MAIN));
	
	
}
function haproxy_courbe_rqs_day(){
	$now=date("Y-m-d H:i:s",strtotime("-24 hour"));
	$q=new postgres_sql();
	$sql="select sum(rqs) as rqs, date_trunc('hour', zdate) as zdate FROM haproxy_log WHERE zdate > '$now' GROUP BY date_trunc('hour', zdate) ORDER BY zdate ASC;";
	if($GLOBALS["VERBOSE"]){echo "\n*****\n$sql\n******\n";}
	$MAIN=array();
	$xdata=array();
	$ydata=array();
	$results=$q->QUERY_SQL($sql);

	while($ligne=@pg_fetch_assoc($results)){
			
		$min=$ligne["zdate"];
		$size=intval($ligne["rqs"]);
		if(round($size)==0){continue;}
		$xdata[]=$min;
		$ydata[]=round($size);
	}
	$MAIN["xdata"]=$xdata;
	$MAIN["ydata"]=$ydata;
	@file_put_contents("{$GLOBALS["BASEDIR"]}/HAPROXY_RQS_DAY", serialize($MAIN));
	if(count($xdata)<2){@unlink("{$GLOBALS["BASEDIR"]}/HAPROXY_RQS_DAY");}

}

function NETWORK_INTERFACES_RXTX(){
	$unix=new unix();
	$influx=new influx();
	$NETS=$unix->NETWORK_ALL_INTERFACES();
	$hostname=$unix->hostname_g();
	$now=date("Y-m-d H:i:s",strtotime("-24 hour"));
	$q=new postgres_sql();
	
	// -----------------------------------------------------------------------------------------------------
	while (list ($Interface, $array) = each ($NETS) ){
	
		$sql="SELECT SUM(RX) as size FROM ethrxtx,date_trunc('hour', zdate) as zdate 
		WHERE zdate > '{$now}'
		AND eth='$Interface' AND proxyname='$hostname' GROUP BY zdate ORDER BY zdate ASC";
	
		if($GLOBALS["VERBOSE"]){echo "\n*****\n$sql\n******\n";}
		$MAIN=array();
		$xdata=array();
		$ydata=array();
		$results=$q->QUERY_SQL($sql);
	
		while($ligne=@pg_fetch_assoc($results)){
			
			$min=$ligne["zdate"];
			$size=intval($ligne["size"])/1024;
			if($GLOBALS["VERBOSE"]){echo "($min): ethrxtx $Interface:RX: $min -> $size\n";}
	
			$size=$size/1024;
			if(round($size)==0){continue;}
			$xdata[]=$min;
			$ydata[]=round($size);
		}
		$MAIN["xdata"]=$xdata;
		$MAIN["ydata"]=$ydata;
		@file_put_contents("{$GLOBALS["BASEDIR"]}/FLUX_{$Interface}_RX", serialize($MAIN));
		if(count($xdata)<2){@unlink("{$GLOBALS["BASEDIR"]}/FLUX_{$Interface}_RX");}
	
	
		$sql="SELECT SUM(TX) as size,date_trunc('hour', zdate) as zdate FROM ethrxtx 
		WHERE zdate > '{$now}' AND eth='$Interface'  
		AND proxyname='$hostname' GROUP BY zdate ORDER BY zdate ASC";
	
		$MAIN=array();
		$xdata=array();
		$ydata=array();
		build_progress("{refresh_dashboard_values}",15);
		$results=$q->QUERY_SQL($sql);
	
		while($ligne=@pg_fetch_assoc($results)){
			$min=$ligne["zdate"];
			$size=intval($ligne["size"])/1024;
			if($GLOBALS["VERBOSE"]){echo "($min): ethrxtx $Interface:RX: $min -> $size\n";}
	
			$size=$size/1024;
			if(round($size)==0){continue;}
			$xdata[]=$min;
			$ydata[]=round($size);
		}
		$MAIN["xdata"]=$xdata;
		$MAIN["ydata"]=$ydata;
		@file_put_contents("{$GLOBALS["BASEDIR"]}/FLUX_{$Interface}_TX", serialize($MAIN));
		if(count($xdata)<2){@unlink("{$GLOBALS["BASEDIR"]}/FLUX_{$Interface}_TX");}
	
	}
}






function PUSH_STATS_FILE($filepath){
	$sock=new sockets();
	$unix=new unix();
	$q=new mysql_squid_builder();
	$EnableSquidRemoteMySQL=intval($sock->GET_INFO("EnableSquidRemoteMySQL"));
	events("PUSH_STATS_FILE: EnableSquidRemoteMySQL = $EnableSquidRemoteMySQL");
	
	$WizardStatsAppliance=unserialize(base64_decode($sock->GET_INFO("WizardStatsAppliance")));
	if(isset($WizardStatsAppliance["SERVER"])){if($WizardStatsAppliance["SERVER"]<>null){ $EnableSquidRemoteMySQL=1; } }
	
	
	$proto="http";
	if($WizardStatsAppliance["SSL"]==1){$proto="https";}
	$uri="$proto://{$WizardStatsAppliance["SERVER"]}:{$WizardStatsAppliance["PORT"]}/nodes.listener.php";
	if($EnableSquidRemoteMySQL==0){return false;}
	$size=@filesize($filepath);
	$filename=basename($filepath);
	$array=array(
			"SQUID_BEREKLEY"=>true,
			"UUID"=>$unix->GetUniqueID(),
			"HOSTNAME"=>$unix->hostname_g(),"SIZE"=>$size,"FILENAME"=>$filename);
	
	
	$curl=new ccurl($uri,false,null,true);
	$curl->x_www_form_urlencoded=false;
	
	if(!$curl->postFile(basename($filepath),$filepath,$array )){
		events("PUSH_STATS_FILE: Failed ".$curl->error);
		return false;
	}
	return true;
	
	
}

function parse_meta($path,$uuid){
	$md_path=md5($path);
	$TimeFile="/etc/artica-postfix/pids/exec.squid.interface-size.php.$uuid.$md_path.time";
	$pidfile="/etc/artica-postfix/pids/exec.squid.interface-size.php.$uuid.$md_path.pid";
	$unix=new unix();
	
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["VERBOSE"]){echo "$pid already executed since {$timepid}Mn\n";}
		if($timepid<10){
			xmeta_events("$pid already executed since {$timepid}Mn",__FUNCTION__,__FILE__,__LINE__);
			return;}
		$kill=$unix->find_program("kill");
		unix_system_kill_force($pid);
	}
	@file_put_contents($pidfile, getmypid());
	$time=$unix->file_time_min($TimeFile);
	if(!$GLOBALS["VERBOSE"]){
		if($time<10){
			xmeta_events("{$time}Mn require at least $time",__FUNCTION__,__FILE__,__LINE__);
			@unlink($path);
			return;
		}
	}
	
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	if($GLOBALS["VERBOSE"]){echo "ParseDB_FILE($path,$uuid,true)\n";}
	xmeta_events("Parsing $path",__FUNCTION__,__FILE__,__LINE__);
	ParseDB_FILE($path,$uuid,true);
	
	if($GLOBALS["VERBOSE"]){echo "Remove $path\n";}
	@unlink($path);
}



function xmeta_events($text,$function,$file,$line){
	$unix=new unix();
	$unix->events($text,"/var/log/artica-meta.log",false,$function,$line,$file);
	
}



function stats_apps_clients(){
	
	$TimeFile="/etc/artica-postfix/settings/Daemons/StatsApplianceReceivers";
	
	$unix=new unix();
	
	$TimExec=$unix->file_time_min($TimeFile);
	if(!$GLOBALS["FORCE"]){
		if($GLOBALS["VERBOSE"]){echo "$TimeFile = {$TimExec}mn\n";}
		if($TimExec<5){return;}
	}
	
	
	@unlink($TimeFile);
	$q=new mysql_squid_builder();
	
	
	if(!$q->TABLE_EXISTS("StatsApplianceReceiver")){
		@file_put_contents($TimeFile, 0);
		@chmod("$TimeFile",0755);
		if($GLOBALS["VERBOSE"]){echo "StatsApplianceReceiver No such table\n";}
		return;
	}
	$CountClients= $q->COUNT_ROWS("StatsApplianceReceiver");
	
	@file_put_contents($TimeFile, $CountClients);
	if($CountClients==0){
		@file_put_contents($TimeFile, 0);
		@chmod("$TimeFile",0755);
		if($GLOBALS["VERBOSE"]){echo "$CountClients Client(s)\n";}
		return;
	}
	@file_put_contents($TimeFile, $q->COUNT_ROWS("StatsApplianceReceiver"));
	@chmod("$TimeFile",0755);
}

function COUNT_OF_SURICATA(){
	$q=new postgres_sql();
	$sql="SELECT SUM(xcount) as xcount FROM suricata_events";
	$ligne=pg_fetch_assoc($q->QUERY_SQL($sql));
	@file_put_contents("{$GLOBALS["BASEDIR"]}/COUNT_OF_SURICATA", intval($ligne["xcount"]));
	
	
}



