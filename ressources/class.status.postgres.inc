<?php


function postgres_pid(){
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/home/ArticaStatsDB/postmaster.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF("/usr/local/ArticaStats/bin/postgres");

}




function postgres(){
	$GLOBALS["INFLUXDBL"]=array();
	$POSTFIX_INSTALLED=false;
	$postconf=$GLOBALS["CLASS_UNIX"]->find_program("postconf");
	if(is_file($postconf)){$POSTFIX_INSTALLED=true;}
	
	$l[]="[APP_POSTGRES]";
	$l[]="service_name=APP_POSTGRES";
	$enabled=1;
	$l[]="service_cmd=/etc/init.d/artica-postgres";
	$l[]="family=proxy";
	$l[]="watchdog_features=1";

	if(!is_file("/usr/local/ArticaStats/bin/postgres")){
		$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.postgres.php --install");
		shell_exec2($cmd);
		$l[]="running=0\ninstalled=0";
		$l[]="";
		@file_put_contents("/etc/artica-postfix/settings/Daemons/PostgresInstalled", 1);
		@chmod("/etc/artica-postfix/settings/Daemons/PostgresInstalled",0777);
		return implode("\n",$l);
	}
	
	@file_put_contents("/etc/artica-postfix/settings/Daemons/PostgresInstalled", 0);
	@chmod("/etc/artica-postfix/settings/Daemons/PostgresInstalled",0777);
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableInfluxDB")){@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableInfluxDB", 1);}
	$SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
	$InfluxUseRemote=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxUseRemote"));
	$EnableInfluxDB=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableInfluxDB"));
	$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
	
	$LOG[]="SquidPerformance = $SquidPerformance";
	$LOG[]="InfluxUseRemote = $InfluxUseRemote";
	$LOG[]="EnableIntelCeleron = $EnableIntelCeleron";
	$LOG[]="EnableInfluxDB = $EnableInfluxDB";
	
	if(!$POSTFIX_INSTALLED){if($SquidPerformance>2){$enabled=0;}}
	if($InfluxUseRemote==1){$enabled=0;}
	if($EnableInfluxDB==0){$enabled=0;}
	
	if(is_file("/etc/artica-postfix/STATS_APPLIANCE")){$EnableInflux=1;$SquidPerformance=0;$EnableIntelCeleron=0;}
	if(!$POSTFIX_INSTALLED){if($EnableIntelCeleron==1){$enabled=0;}}

	$l[]="master_version=".postgres_version();
	$l[]="service_disabled=$enabled";



	$master_pid=postgres_pid();
	events("SquidPerformance:$SquidPerformance, enabled:$enabled, pid:$master_pid",__FUNCTION__,__LINE__);

	if($enabled==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			squid_admin_mysql(0, "Stopping BigData engine PID: $master_pid (must be disabled)", @implode("\n", $LOG),__FILE__,__LINE__);
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.postgres.php --stop");
			shell_exec2($cmd);
			$l[]="running=1";
				
		}
		postgres_events("PostGreSQL PID none is Disabled");
		$l[]="installed=1";
		$l[]="";
		return implode("\n",$l);
	}
	
	
	if(is_file("/opt/influxdb/influxd")){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			squid_admin_mysql(1, "Removing InfluxDB database [depreciated]", null,__FILE__,__LINE__);
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.postgres.php --remove-influx");
			shell_exec2($cmd);
		}
		
	}
	
	if(!is_file("/etc/init.d/artica-postgres")){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			postgres_events("Creating PostgresSQL startup scripts...");
			squid_admin_mysql(1, "Creating PostgresSQL startup scripts", null,__FILE__,__LINE__);
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.initslapd.php --postgres");
			shell_exec2($cmd);
		}		
		
	}



	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			squid_admin_mysql(0, "Warning BigData engine is stopped [action=start]", null,__FILE__,__LINE__);
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.postgres.php --start");
			shell_exec2($cmd);

		}
		
		
		$l[]="master_pid=$master_pid";
		$l[]="running=0";
		$l[]="installed=1";
		$l[]="";
		return implode("\n",$l);

	}else{
		if($enabled==0){
			squid_admin_mysql(0, "Stopping BigData engine PID: $master_pid (must be disabled)", null,__FILE__,__LINE__);
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/artica-postgres stop >/dev/null 2>&1 &");
			$l[]="running=0";
			$l[]="installed=1";
			$l[]="";
			return implode("\n",$l);
		}
	}
	
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/usr/share/artica-postfix/ressources/logs/web/InfluxDB.state");
	if($time_file>15){
		$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.postgres.php --dbsize >/dev/null 2>&1 &");
		shell_exec2($cmd);
	}

	$InfluxAdminDisabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxAdminDisabled"));
	$InfluxAdminPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxAdminPort"));
	

	$fp = @stream_socket_client("tcp://127.0.0.1:5432",
			$errno, $errstr,3, STREAM_CLIENT_CONNECT);
	if(!$fp){
		WriteToSyslogMail("PostgreSQL:tcp:5432 $errno $errstr",basename(__FILE__));
		squid_admin_mysql(0, "PostgreSQL: Statistics Engine Error:[$errstr] failed on port 5432 [action=restart]", "Influxdb:tcp:8086\n$errno $errstr",__FILE__,__LINE__);
		shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/artica-postgres restart >/dev/null 2>&1 &");
	}
	
	if(is_resource($fp)){socket_close($fp);}
	
	if(is_dir("/home/artica/squid/realtime-events")){
		$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/migrate_postgres.time");
		if($time_file>60){
			$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.influxToPostgres.php >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}else{
			postgres_events("Influx To PostGreSQL {$time_file}mn, require 60mn");
		}
	}
	
	
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/usr/local/ArticaStats/bin/vacuumdb.forced.time");
	if($time_file>10080){
		$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.postgres.php --vacuumdb >/dev/null 2>&1 &");
		shell_exec2($cmd);
	}
	
	
	include_once(dirname(__FILE__)."/class.postgres.inc");
	$postgres=new postgres_sql();
	


	postgres_events("PostGreSQL PID $master_pid is UP");
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);


}

function postgres_version(){
	if(isset($GLOBALS["postgres_version"])){return $GLOBALS["postgres_version"];}
	exec("/usr/local/ArticaStats/bin/postgres -V 2>&1",$results);
	while (list ($key, $value) = each ($results) ){
		if(preg_match("#([0-9\.]+)#", $value,$re)){
			$GLOBALS["postgres_version"]=$re[1];
			if($GLOBALS["VERBOSE"]){echo "VERSION: $value...\n";}
			return $GLOBALS["postgres_version"];
		}
	}
}

function postgres_events($text=null){
	$line=0;
	$function="none";
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		
		if(isset($trace[0])){
			if(isset($trace[0]["function"])){$function=$trace[0]["function"];}
			if(isset($trace[0]["line"])){$line=$trace[0]["line"];}
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