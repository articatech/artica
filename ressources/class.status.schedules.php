<?php
function load_stats(){
	events("************************ SCHEDULE ****************************",__FUNCTION__,__LINE__);
	
	if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
	if(!isset($GLOBALS["CLASS_UNIX"])){$unix=new unix();}else{$unix=$GLOBALS["CLASS_UNIX"];}
	
	$time=time();
	$BASEDIR="/usr/share/artica-postfix";
	$hash_mem=array();
	@mkdir("/var/log/artica-postfix/sys_alerts",0755,true);
	
	
	$NtpdateAD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NtpdateAD"));
	$NTPDClientEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDClientEnabled"));
	$SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
	if($NtpdateAD==1){$NTPDClientEnabled=1;}
	
	
	
// NTP CLIENT *****************************************************************************
if($NTPDClientEnabled==1){
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squid.watchdog.php.start_watchdog.ntp.time");
	$NTPDClientPool=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDClientPool"));
	if($NTPDClientPool==0){$NTPDClientPool=120;}
	if($time_file>$NTPDClientPool){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.ntpdate.php >/dev/null 2>&1 &");
	}
}
// ****************************************************************************************
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.syslog-engine.php.load_stats.time");
	events("exec.syslog-engine.php --load-stats = {$time_file}/5mn");
	if($time_file>5){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.syslog-engine.php --load-stats >/dev/null 2>&1 &");
	}
	

	


	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/croned.1/cron.notifs.php.time");
	if($time_file>5){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/cron.notifs.php >/dev/null 2>&1 &");
	}



	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.cleanfiles.php.time");
	if($time_file>120){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.cleanfiles.php >/dev/null 2>&1 &");
	}
	
	$timefile=$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.clean.logs.php.CleanLogs.time");
	if($time_file>240){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.clean.logs.php --clean-tmp >/dev/null 2>&1 &");
	}	



	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squid.watchdog.php.CHECK_DNS_SYSTEMS.time");
	events("CHECK_DNS_SYSTEMS: {$time_file}mn",__FUNCTION__,__LINE__);

	
	
	
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.clean.logs.php.clean_space.time");
	events("clean_space: {$time_file}mn",__FUNCTION__,__LINE__);
	if($time_file>240){
		$cmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.clean.logs.php --clean-space >/dev/null 2>&1 &";
		events($cmd,__FUNCTION__,__LINE__);
		shell_exec2("$cmd");
	}


	$kill=$GLOBALS["CLASS_UNIX"]->find_program("kill");
	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
	exec("pgrep -l -f \"exec.schedules.php --run\" 2>&1",$results);

	while (list ($index,$line) = each ($results)){
		if(preg_match("#pgrep#", $line)){continue;}
		if(!preg_match("#^([0-9]+)\s+#", $line,$re)){continue;}
		$pid=$re[1];
		$TTL=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($pid);
		events("$line -> {$TTL}Mn");
		if($TTL<420){continue;}
		ToSyslog("Killing exec.schedules.php PID $pid");
		unix_system_kill_force($pid);
	}



	events("************************ SCHEDULE ****************************",__FUNCTION__,__LINE__);
}