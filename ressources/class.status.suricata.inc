<?php

function suricata_pid(){
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("suricata");
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file('/var/run/suricata/v.pid');
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF($masterbin);
}
function suricata_version(){
	$unix=new unix();
	if(isset($GLOBALS["suricata_version"])){return $GLOBALS["suricata_version"];}
	$squidbin=$unix->find_program("suricata");
	if(!is_file($squidbin)){return "0.0.0";}
	exec("$squidbin -V 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#Suricata\s+version\s+([0-9\.]+)#i", $val,$re)){
			$GLOBALS["suricata_version"]=trim($re[1]);
			return $GLOBALS["suricata_version"];
		}
	}
}
function barnyard_pid(){
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("barnyard2");
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file('/var/run/suricata/barnyard2_NULL1.pid');
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF($masterbin);
}
function barnyard2_version(){
	
	if(isset($GLOBALS["barnyard2_version"])){return $GLOBALS["barnyard2_version"];}
	$squidbin=$GLOBALS["CLASS_UNIX"]->find_program("barnyard2");
	if(!is_file($squidbin)){return "0.0.0";}
	exec("$squidbin -V 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#Version\s+([0-9\.]+)#", $val,$re)){
			$GLOBALS["barnyard2_version"]=trim($re[1]);
			return $GLOBALS["barnyard2_version"];
		}
	}
}

function barnyard2_status(){
	$l[]="[barnyard2]";
	$l[]="service_name=barnyard2";
	$enabled=1;
	$l[]="service_cmd=/etc/init.d/barnyard";
	$l[]="family=proxy";
	$l[]="watchdog_features=1";
	$squidbin=$GLOBALS["CLASS_UNIX"]->find_program("barnyard2");

	if(!is_file($squidbin)){
		$l[]="running=0\ninstalled=0";
		$l[]="";
		return implode("\n",$l);
	}

	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableBarnyard2")){@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableBarnyard2", 0);}
	$EnableSuricata=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableBarnyard2"));
	$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
	//if($EnableIntelCeleron==1){$EnableSuricata=0;}
	$l[]="master_version=".barnyard2_version();
	$l[]="service_disabled=$EnableSuricata";

	$enabled=$EnableSuricata;
	$master_pid=barnyard_pid();

	if($enabled==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			system_admin_mysql(0, "Stopping barnyard2 engine PID: $master_pid (must be disabled)", @implode("\n", $LOG),__FILE__,__LINE__);
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.barnyard.php --stop");
			shell_exec2($cmd);
			$l[]="running=1";

		}
		$l[]="installed=1";
		$l[]="";
		return implode("\n",$l);
	}



	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			system_admin_mysql(0, "Warning barnyard2 (IDS) engine is stopped [action=start]", null,__FILE__,__LINE__);
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.barnyard.php --start");
			shell_exec2($cmd);
		}

		$l[]="master_pid=$master_pid";
		$l[]="running=0";
		$l[]="installed=1";
		$l[]="";
		return implode("\n",$l);

	}else{
		if($enabled==0){
			system_admin_mysql(0, "Stopping barnyard2 engine PID: $master_pid (must be disabled)", null,__FILE__,__LINE__);
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/barnyard stop >/dev/null 2>&1 &");
			$l[]="running=0";
			$l[]="installed=1";
			$l[]="";
			return implode("\n",$l);
		}
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);


}

function suricata_status(){
	$l[]="[IDS]";
	$l[]="service_name=IDS";
	$enabled=1;
	$l[]="service_cmd=/etc/init.d/suricata";
	$l[]="family=proxy";
	$l[]="watchdog_features=1";

	if(!is_file("/usr/bin/suricata")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/SuricataInstalled",0);
		$l[]="running=0\ninstalled=0";
		$l[]="";
		return implode("\n",$l);
	}
	@file_put_contents("/etc/artica-postfix/settings/Daemons/SuricataInstalled",1);
	$suricata_version=suricata_version();
	@file_put_contents("/etc/artica-postfix/settings/Daemons/SuricataVersion",$suricata_version);
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableSuricata")){@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableSuricata", 0);}
	$EnableSuricata=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableSuricata"));
	$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
	//if($EnableIntelCeleron==1){$EnableSuricata=0;}
	$l[]="master_version=$suricata_version";
	$l[]="service_disabled=$EnableSuricata";
	
	@file_put_contents("/etc/artica-postfix/settings/Daemons/SuricataVersion", $suricata_version);
	
	$enabled=$EnableSuricata;
	$master_pid=suricata_pid();

	if($enabled==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			system_admin_mysql(0, "Stopping Suricata engine PID: $master_pid (must be disabled)", @implode("\n", $LOG),__FILE__,__LINE__);
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.suricata.php --stop");
			shell_exec2($cmd);
			$l[]="running=1";
				
		}
		$l[]="installed=1";
		$l[]="";
		return implode("\n",$l);
	}



	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			system_admin_mysql(0, "Warning Suricata (IDS) engine is stopped [action=start]", null,__FILE__,__LINE__);
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.suricata.php --start");
			shell_exec2($cmd);
		}
		
		$l[]="master_pid=$master_pid";
		$l[]="running=0";
		$l[]="installed=1";
		$l[]="";
		return implode("\n",$l);

	}else{
		if($enabled==0){
			system_admin_mysql(0, "Stopping Suricata (IDS) engine PID: $master_pid (must be disabled)", null,__FILE__,__LINE__);
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/suricata stop >/dev/null 2>&1 &");
			$l[]="running=0";
			$l[]="installed=1";
			$l[]="";
			return implode("\n",$l);
		}
	}
	
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/CleanSuricataLogs.time");
	if($time_file>59){
		$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.clean.logs.php --suricata >/dev/null 2>&1 &");
		shell_exec2($cmd);
	}
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/usr/share/artica-postfix/ressources/interface-cache/suricata.dashboard");
	if($time_file>15){
		$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.suricata.php --dashboard >/dev/null 2>&1 &");
		shell_exec2($cmd);
	}

	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.suricata-fw.php.xstart.time");
	if($time_file>15){
		$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.suricata-fw.php --build >/dev/null 2>&1 &");
		shell_exec2($cmd);
	}	
	
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.suricata.updates.php.update.time");
	if($time_file>1439){
		$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.suricata.updates.php >/dev/null 2>&1 &");
		shell_exec2($cmd);
	}
	
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.suricata.hourly.purge.time");
	if($time_file>1439){
		$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.suricata.hourly.php --purge >/dev/null 2>&1 &");
		shell_exec2($cmd);
	}
	
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.suricata-fw.php.purge.pid");
	if($time_file>480){
		$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.suricata-fw.php --purge >/dev/null 2>&1 &");
		shell_exec2($cmd);
	}	
	
	
	
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);


}
