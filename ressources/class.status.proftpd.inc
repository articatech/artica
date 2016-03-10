<?php

function proftpd_pid($Masterbin){
	$unix=new unix();
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/proftpd.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF($Masterbin);
}
function proftpd_version($Masterbin){
	
	exec("$Masterbin -v 2>&1",$results);

	while (list ($num, $val) = each ($results)){
		if(preg_match("#ProFTPD Version\s+([0-9\.]+)#", $val,$re)){
			$GLOBALS["proftpd_version"]=trim($re[1]);
			return $GLOBALS["proftpd_version"];
		}
	}

}


function proftpd_status(){
	$l[]="[APP_PROFTPD]";
	$l[]="service_name=APP_PROFTPD";
	$enabled=1;
	$l[]="service_cmd=/etc/init.d/proftpd";
	$l[]="family=proxy";
	$l[]="watchdog_features=1";
	$proftpd=$GLOBALS["CLASS_UNIX"]->find_program("proftpd");

	if(!is_file($proftpd)){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/ProFTPDInstalled", 0);
		$l[]="running=0\ninstalled=0";
		$l[]="";
		return implode("\n",$l);
	}
	@file_put_contents("/etc/artica-postfix/settings/Daemons/ProFTPDInstalled",1);
	$EnableProFTPD=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableProFTPD"));
	
	
	$proftpd_version=proftpd_version($proftpd);
	$l[]="master_version=$proftpd_version";
	@file_put_contents("/etc/artica-postfix/settings/Daemons/ProFTPDVersion", $proftpd_version);
	
	
	$l[]="service_disabled=$EnableProFTPD";

	$enabled=$EnableProFTPD;
	$master_pid=proftpd_pid($proftpd);

	if($enabled==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			system_admin_mysql(0, "Stopping ProFTPD engine PID: $master_pid (must be disabled)", @implode("\n", $LOG),__FILE__,__LINE__);
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.proftpd.php --stop");
			shell_exec2($cmd);
			$l[]="running=1";

		}
		$l[]="installed=1";
		$l[]="";
		return implode("\n",$l);
	}



	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			system_admin_mysql(0, "Warning ProFTPD (FTP) engine is stopped [action=start]", null,__FILE__,__LINE__);
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.proftpd.php --start");
			shell_exec2($cmd);
		}

		$l[]="master_pid=$master_pid";
		$l[]="running=0";
		$l[]="installed=1";
		$l[]="";
		return implode("\n",$l);

	}else{
		if($enabled==0){
			system_admin_mysql(0, "Stopping ProFTPD engine PID: $master_pid (must be disabled)", null,__FILE__,__LINE__);
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/proftpd stop >/dev/null 2>&1 &");
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

