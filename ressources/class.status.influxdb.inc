<?php


function InfluxDB_pid(){
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/influxdb.pid");
	$GLOBALS["INFLUXDBL"][]="PIDF:$pid";
	
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	while (list ($num, $x) = each ($GLOBALS["PROCES_EXISTS_DEBUG"])){
		$GLOBALS["INFLUXDBL"][]=$x;
	}
	
	
	$Masterbin="/opt/influxdb/influxd";
	$GLOBALS["INFLUXDBL"][]="PIDS:$Masterbin";
	return $GLOBALS["CLASS_UNIX"]->PIDOF($Masterbin);

}


function InfluxDB(){
	$GLOBALS["INFLUXDBL"]=array();
	$l[]="[APP_INFLUXDB]";
	$l[]="service_name=APP_INFLUXDB";
	$enabled=1;
	$l[]="service_cmd=/etc/init.d/artica-postgres";
	$l[]="family=proxy";
	$l[]="watchdog_features=1";

	if(!is_file("/opt/influxdb/influxd")){
		$l[]="running=0\ninstalled=0";
		$l[]="";
		return implode("\n",$l);
	}
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableInfluxDB")){@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableInfluxDB", 1);}
	$SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
	$InfluxUseRemote=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxUseRemote"));
	$EnableInfluxDB=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableInfluxDB"));
	$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
	
	$LOG[]="SquidPerformance = $SquidPerformance";
	$LOG[]="InfluxUseRemote = $InfluxUseRemote";
	$LOG[]="EnableIntelCeleron = $EnableIntelCeleron";
	$LOG[]="EnableInfluxDB = $EnableInfluxDB";
	
	if($SquidPerformance>2){$enabled=0;}
	if($InfluxUseRemote==1){$enabled=0;}
	if($EnableInfluxDB==0){$enabled=0;}
	
	if(is_file("/etc/artica-postfix/STATS_APPLIANCE")){$EnableInflux=1;$SquidPerformance=0;$EnableIntelCeleron=0;}
	if($EnableIntelCeleron==1){$enabled=0;}

	$l[]="master_version=".influxdb_version();
	$l[]="service_disabled=$enabled";



	$master_pid=InfluxDB_pid();
	events("SquidPerformance:$SquidPerformance, enabled:$enabled, pid:$master_pid",__FUNCTION__,__LINE__);

	if($enabled==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			squid_admin_mysql(0, "Stopping BigData engine PID: $master_pid (must be disabled)", @implode("\n", $LOG),__FILE__,__LINE__);
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.influxdb.php --stop");
			shell_exec2($cmd);
			$l[]="running=1";
				
		}
		$l[]="installed=1";
		$l[]="";
		return implode("\n",$l);
	}



	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			squid_admin_mysql(0, "Warning BigData engine is stopped [action=start]", null,__FILE__,__LINE__);
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.influxdb.php --start");
			shell_exec2($cmd);

		}
		
		while (list ($num, $x) = each ($GLOBALS["INFLUXDBL"])){
			$l[]="LOG{$num}=$x";
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
		$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.influxdb.php --InfluxDbSize >/dev/null 2>&1 &");
		shell_exec2($cmd);
	}

	$InfluxAdminDisabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxAdminDisabled"));
	$InfluxAdminPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxAdminPort"));
	if($InfluxAdminPort==0){$InfluxAdminPort=8083;}

	$fp = @stream_socket_client("tcp://127.0.0.1:8086",
			$errno, $errstr,3, STREAM_CLIENT_CONNECT);
	if(!$fp){
		WriteToSyslogMail("Influxdb:tcp:8086 $errno $errstr",basename(__FILE__));
		squid_admin_mysql(0, "Statistics Engine Error:[$errstr] failed on port 8086 [action=restart]", "Influxdb:tcp:8086\n$errno $errstr",__FILE__,__LINE__);
		shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/artica-postgres restart >/dev/null 2>&1 &");
	}
	
	if(is_resource($fp)){socket_close($fp);}
	



	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);


}

function influxdb_version(){
	if(isset($GLOBALS["influxdb_version"])){return $GLOBALS["influxdb_version"];}
	exec("/opt/influxdb/influxd version 2>&1",$results);
	while (list ($key, $value) = each ($results) ){
		if(preg_match("#InfluxDB v([0-9\-\.a-z]+)#", $value,$re)){
			$GLOBALS["influxdb_version"]=$re[1];
			if($GLOBALS["VERBOSE"]){echo "VERSION: $value...\n";}
			return $GLOBALS["influxdb_version"];
		}
	}
	if($GLOBALS["VERBOSE"]){echo "VERSION: TRY 0.8?\n";}
	exec("/opt/influxdb/influxd -v 2>&1",$results2);
	while (list ($key, $value) = each ($results2) ){
		if(preg_match("#InfluxDB\s+v([0-9\-\.a-z]+)#", $value,$re)){
			$GLOBALS["influxdb_version"]=$re[1];
			if($GLOBALS["VERBOSE"]){echo "VERSION 0.8x: $value...\n";}
			return $GLOBALS["influxdb_version"];
		}
	}

}