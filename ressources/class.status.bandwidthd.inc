<?php


function bandwidthd_pid(){
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/bandwidthd.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF("/usr/bandwidthd/bandwidthd");
	
}

function bandwidthd(){
	$l[]="[APP_BANDWIDTHD]";
	$l[]="service_name=APP_BANDWIDTHD";
	
	if(!is_file("/usr/bandwidthd/bandwidthd")){
		$l[]="running=0\ninstalled=0";
		@file_put_contents("/etc/artica-postfix/settings/Daemons/BandwidthdInstalled",0);
		return @implode("\n", $l);
	}else{
		@file_put_contents("/etc/artica-postfix/settings/Daemons/BandwidthdInstalled",1);
	}
	$l[]="installed=1";
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableBandwidthd")){@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableBandwidthd",0);}
	
	$EnableBandwidthd=$GLOBALS["CLASS_SOCKETS"]->Bandwidthd_enabled();
	
	$l[]="master_version=2.0.1";
	$l[]="service_disabled=$EnableBandwidthd";
	
	$master_pid=bandwidthd_pid();
	if($EnableBandwidthd==0){
		$l[]="running=0";
		
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			if(!$GLOBALS["DISABLE_WATCHDOG"]){
				system_admin_mysql(0, "Bandwidthd Daemon running but is disabled [action=stop]", null,__FILE__,__LINE__);
				$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.bandwithd.php --stop");
				shell_exec2($cmd);
			}
		}
		bandwidthd_rotate(true);
		return @implode("\n", $l);
	}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$l[]="running=0";
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			system_admin_mysql(0, "Bandwidthd Daemon not running [action=start]", null,__FILE__,__LINE__);
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.bandwithd.php --start");
			shell_exec2($cmd);
		}
		return @implode("\n", $l);
	}
	
	
	
	bandwidthd_rotate();
	if(!system_is_overloaded(basename(__FILE__))){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$pidTime="/etc/artica-postfix/pids/exec.bandwidthd.scan.php.scan.time";
			$time=$GLOBALS["CLASS_UNIX"]->file_time_min($pidTime);
			if($time>4){
				$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.bandwidthd.scan.php");
				shell_exec2($cmd);
			}
		}
	}
	
	
	
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);
	
}

function bandwidthd_rotate($remove=false){
		
	if($remove){ if(!is_file("/etc/cron.d/bandrotate")){return;} }
	if($remove){ if(is_file("/etc/cron.d/bandrotate")){ @unlink("/etc/cron.d/bandrotate"); shell_exec("/etc/init.d/cron reload"); return; } }
	if(!$remove){if(is_file("/etc/cron.d/bandrotate")){return;} }	
	
	if(!$remove){
		if(!is_file("/etc/cron.d/bandrotate")){
			Popuplate_cron_make("bandrotate","1 0 * * *","exec.bandwidthd.scan.php --rotate");
			shell_exec("/etc/init.d/cron reload");
		}
	}	
}

