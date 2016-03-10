<?php


function haproxy_pid(){
	$pid_path="/var/run/haproxy.pid";
	if(!is_file($pid_path)){
		$binpath=$GLOBALS["CLASS_UNIX"]->find_program("haproxy");
		return $GLOBALS["CLASS_UNIX"]->PIDOF($binpath); 
	}
	$pids=explode("\n",@file_get_contents($pid_path));
	while (list ($index, $line) = each ($pids)){
		$line=str_replace("\r", "", $line);
		$line=str_replace("\n", "", $line);
		if(!is_numeric(trim($line))){continue;}
		if($GLOBALS["VERBOSE"]){echo "$pid_path = $line\n";}
		if($GLOBALS["CLASS_UNIX"]->process_exists($line)){
			$PPID=$GLOBALS["CLASS_UNIX"]->PPID_OF($line);
			if($GLOBALS["VERBOSE"]){echo "$line ->running PPID:$PPID\n";}
			return $PPID;
		}
	}
	
}
function haproxy_version($binpath){

	if(isset($GLOBALS["haproxy_version"])){return $GLOBALS["haproxy_version"];}
	
	if(!is_file($binpath)){return;}
	exec("$binpath -v 2>&1",$results);
	while (list ($index, $line) = each ($results)){
		if(preg_match("#HA-Proxy version\s+([0-9\.\-a-z]+)\s+#", $line,$re)){$GLOBALS["haproxy_version"]=$re[1];break;}
	}
	return $GLOBALS["haproxy_version"];
}

function haproxy(){
	
	
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program("haproxy");
	
	$l[]="[APP_HAPROXY]";
	$l[]="service_name=APP_HAPROXY";
	
	if(!is_file($binpath)){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/HaProxyInstalled", 0);
		$l[]="running=0\ninstalled=0";
		return @implode("\n", $l);
		
	}
	@file_put_contents("/etc/artica-postfix/settings/Daemons/HaProxyInstalled", 1);
	$enabled=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableHaProxy"));
	$pid_path="/var/run/haproxy.pid";


	$HaproxyVersion=haproxy_version($binpath);
	@file_put_contents("/etc/artica-postfix/settings/Daemons/HaproxyVersion", $HaproxyVersion);
	$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
	
	$l[]="master_version=$HaproxyVersion";
	$l[]="service_cmd=/etc/init.d/haproxy";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=network";
	
	HaproxyCronStatistics();

	if($enabled==0){
		$l[]="";return implode("\n",$l);
	}
	$PPID=haproxy_pid();
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($PPID)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			squid_admin_mysql(0, "Load-balancer service not running [action=start]", null,__FILE__,__LINE__);
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.haproxy.php --start >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		$l[]="";
		return implode("\n",$l);

	}
	
	if(!$GLOBALS["DISABLE_WATCHDOG"]){
		$unix=new unix();
		if(!$unix->is_socket("/var/run/haproxy.stat")){
			squid_admin_mysql(0, "Load-balancer socket /var/run/haproxy.stat not running [action=restart]", null,__FILE__,__LINE__);
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.haproxy.php --restart >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
	}
	
	if($EnableIntelCeleron==0){
		$pidtime="/etc/artica-postfix/pids/exec.haproxy.events-parser.php.scan.time";
		$TimeExec=$GLOBALS["CLASS_UNIX"]->file_time_min($pidtime);
		if($TimeExec>5){
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.haproxy.events-parser.php >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		
		$pidtime="/usr/share/artica-postfix/ressources/interface-cache/HAPROXY_DAY";
		$TimeExec=$GLOBALS["CLASS_UNIX"]->file_time_min($pidtime);
		if($TimeExec>15){
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.haproxy.dashboard.php >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		
	}
	
	
	$l[]=GetMemoriesOf($PPID);
	$l[]="";
	

	return implode("\n",$l);return;
}

//========================================================================================================================================================
function HaproxyCronStatistics(){
	$ENABLED=true;
	$SquidPerformance=0;
	$SquidStatsMonthQueue=0;

	// if > 1 then no stats $SquidPerformance
	$InfluxSyslogRemote=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/InfluxSyslogRemote"));
	$InfluxUseRemote=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/InfluxUseRemote"));
	$EnableHaProxy=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableHaProxy"));
	if($InfluxUseRemote==0){$InfluxSyslogRemote=0;}

	if(is_file("/etc/artica-postfix/STATS_APPLIANCE")){
		$InfluxUseRemote=0;
		$SquidPerformance=0;

	}

	if($InfluxUseRemote==1){$ENABLED=false;}
	if($EnableHaProxy==0){$ENABLED=false;}

	if($ENABLED){
		if(!is_file("/etc/cron.d/haproxy-monthly")){
			$GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("haproxy-monthly","30 1 * * *","exec.haproxy.stats.compress.php");
			shell_exec2("/etc/init.d/cron reload");
		}
		if(!is_file("/etc/cron.d/haproxy-yearly")){
			$GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("haproxy-yearly","15 3 1 * *","exec.haproxy.stats.compress.php --compress-year");
			shell_exec2("/etc/init.d/cron reload");
		}


		HaproxyCronStatistics_run();
		HaproxyCronStatistics_year_run();
	}

	if(!$ENABLED){
		if(is_file("/etc/cron.d/haproxy-monthly")){
			@unlink("/etc/cron.d/haproxy-monthly");
			shell_exec2("/etc/init.d/cron reload");
		}
		if(is_file("/etc/cron.d/haproxy-yearly")){
			@unlink("/etc/cron.d/haproxy-yearly");
			shell_exec2("/etc/init.d/cron reload");
		}

	}

}
function HaproxyCronStatistics_run(){
	if(!is_file("/etc/artica-postfix/settings/Daemons/HaproxyStatsMonthQueue")){return;}
	$SquidStatsMonthQueue=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/HaproxyStatsMonthQueue"));
	if($SquidStatsMonthQueue==0){return;}
	if(system_is_overloaded()){return;}
	$pidfile="/etc/artica-postfix/pids/HaproxyStatsMonthQueue.pid";
	$pidTime="/etc/artica-postfix/pids/HaproxyStatsMonthQueue.time";
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pidfile);
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return;}
	$timeExec=$GLOBALS["CLASS_UNIX"]->file_time_min($pidTime);
	if($timeExec<15){return;}
	$cmd="{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.haproxy.stats.compress.php >/dev/null 2>&1 &";
	shell_exec2(trim($cmd));
}
function HaproxyCronStatistics_year_run(){

	$cmd="{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.haproxy.stats.compress.php --compress-year >/dev/null 2>&1 &";
	if(!is_file("/etc/artica-postfix/settings/Daemons/HaproxyStatsYearQueue")){return;}
	$HaproxyStatsYearQueue=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/HaproxyStatsYearQueue"));
	if($HaproxyStatsYearQueue==0){return;}
	if(system_is_overloaded()){return;}
	$pidfile="/etc/artica-postfix/pids/HaproxyStatsYearQueue.pid";
	$pidTime="/etc/artica-postfix/pids/HaproxyStatsYearQueue.time";
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pidfile);
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return;}
	$timeExec=$GLOBALS["CLASS_UNIX"]->file_time_min($pidTime);
	if($timeExec<15){return;}
	shell_exec2(trim($cmd));
}