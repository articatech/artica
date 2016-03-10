<?php
if(!isset($GLOBALS["BASE_ROOT"])){$GLOBALS["BASE_ROOT"]="{$GLOBALS["BASE_ROOT"]}";}

class status_scheduler{
	
	
	function status_scheduler(){
		if(!function_exists("system_is_overloaded")){include_once("/usr/share/artica-postfix/class.os.system.inc");}
	if(!isset($GLOBALS["CLASS_UNIX"])){
		$GLOBALS["CLASS_SOCKETS"]=new sockets();
		$GLOBALS["CLASS_USERS"]=new settings_inc();
		$GLOBALS["CLASS_UNIX"]=new unix();
	}
	
	if(!is_dir("{$GLOBALS["BASE_ROOT"]}/ressources/logs")){
		@mkdir("{$GLOBALS["BASE_ROOT"]}/ressources/logs",0755,true);
	}
	
	$prefixcmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]}";
	
	$GLOBALS["SquidPerformance"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidPerformance"));
	$WebCommunityUpdatePool=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WebCommunityUpdatePool"));
	$GLOBALS["LMSensorsEnable"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/LMSensorsEnable"));
	$LMSensorsEnable_cachefile="/usr/share/artica-postfix/ressources/logs/web/sensors.array";
	$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
	$system_is_overloaded=system_is_overloaded();
	if($WebCommunityUpdatePool==0){$WebCommunityUpdatePool=360;}
	
	$this->events("LMSensorsEnable = {$GLOBALS["LMSensorsEnable"]}",__FUNCTION__,__LINE__);
	
	if($GLOBALS["LMSensorsEnable"]==0){
		if(is_file($LMSensorsEnable_cachefile)){@unlink($LMSensorsEnable_cachefile);}
	}
	if($GLOBALS["LMSensorsEnable"]==1){
		$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.lm-sensors.php.time");
		$this->events("exec.lm-sensors.php.time = $time_file/14min",__FUNCTION__,__LINE__);
		if($time_file>14){
			shell_exec2("$prefixcmd {$GLOBALS["BASE_ROOT"]}/exec.lm-sensors.php --test >/dev/null 2>&1 &");
		}else{
			if(!is_file("/usr/share/artica-postfix/ressources/logs/web/sensors.array")){
				shell_exec2("$prefixcmd {$GLOBALS["BASE_ROOT"]}/exec.lm-sensors.php --test >/dev/null 2>&1 &");
			}
		
		}
		
		
	}
	
	if(!is_file("/usr/share/artica-postfix/ressources/usb.scan.serialize")){
		shell_exec2("$prefixcmd {$GLOBALS["BASE_ROOT"]}/exec.usb.scan.write.php >/dev/null 2>&1 &");
	}else{
		$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/usr/share/artica-postfix/ressources/usb.scan.serialize");
		$this->events("exec.lm-sensors.php.time = $time_file/14min",__FUNCTION__,__LINE__);
		if($time_file>50){
			shell_exec2("$prefixcmd {$GLOBALS["BASE_ROOT"]}/exec.usb.scan.write.php >/dev/null 2>&1 &");
		}
	}
	
	
	
	if($GLOBALS["SquidPerformance"]<3){
		if($GLOBALS["NMAP_INSTALLED"]){
			$ComputersAllowNmap=$GLOBALS["CLASS_SOCKETS"]->GET_INFO('ComputersAllowNmap');
			if(!is_numeric($ComputersAllowNmap)){$ComputersAllowNmap=1;}
			$EnableScanComputersNet=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableScanComputersNet"));
			if($ComputersAllowNmap==1){
				$NmapRotateMinutes=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NmapRotateMinutes"));
				if($NmapRotateMinutes<5){$NmapRotateMinutes=60;}
				$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.nmapscan.php.time");
				if($time_file>$NmapRotateMinutes){
					shell_exec2("$prefixcmd {$GLOBALS["BASE_ROOT"]}/exec.nmapscan.php --scan-nets >/dev/null 2>&1 &");
				}
			}
				$unix=new unix();
				
			if($EnableScanComputersNet==1){
				$EnableScanComputersNetSchedule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableScanComputersNetSchedule"));
				if($EnableScanComputersNetSchedule<5){$EnableScanComputersNetSchedule=15;}
				$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.nmapscan.php.nmap_scan_period.time");
				if($time_file>$EnableScanComputersNetSchedule){
					$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("{$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.nmapscan.php --scan-period");
					
				}
				
			}
		}
	}
	
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.mysql.clean.php.clean_tmd.time");
	if($time_file>240){
		shell_exec2("$prefixcmd {$GLOBALS["BASE_ROOT"]}/exec.mysql.clean.php --clean-tmd >/dev/null 2>&1 &");
	}	
	
	if(!$system_is_overloaded){
		$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.clean.logs.php.sessions_clean.time");
		if($time_file>180){
			shell_exec2("$prefixcmd {$GLOBALS["BASE_ROOT"]}/exec.clean.logs.php --clean-sessions >/dev/null 2>&1 &");
		}
	}
	
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.web-community-filter.php.MAIN.time");
	if($time_file>$WebCommunityUpdatePool){
		shell_exec2("$prefixcmd {$GLOBALS["BASE_ROOT"]}/exec.web-community-filter.php >/dev/null 2>&1 &");
	}
	
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/croned.1/cron.notifs.php.time");
	if($time_file>$WebCommunityUpdatePool){
		shell_exec2("$prefixcmd {$GLOBALS["BASE_ROOT"]}/cron.notifs.php >/dev/null 2>&1 &");
	}
	
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.clean-logs-backup.php.xscan.time");
	writelogs_framework("exec.clean-logs-backup.php {$time_file}Mn/20Mn");
	if($time_file>20){
		shell_exec2("$prefixcmd {$GLOBALS["BASE_ROOT"]}/exec.clean-logs-backup.php >/dev/null 2>&1 &");
	}
	
	
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.clean.varlog.php.varlog.time");
	writelogs_framework("exec.clean.varlog.php {$time_file}Mn/20Mn");
	if($time_file>15){
		shell_exec2("$prefixcmd {$GLOBALS["BASE_ROOT"]}/exec.clean.varlog.php >/dev/null 2>&1 &");
	}
	
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.checkfolder-permissions.php.MAIN.time");
	writelogs_framework("exec.checkfolder-permissions.php {$time_file}Mn/240Mn");
	if($time_file>240){
		if(!$system_is_overloaded){
			shell_exec2("$prefixcmd {$GLOBALS["BASE_ROOT"]}/exec.checkfolder-permissions.php >/dev/null 2>&1 &");
		}
	}
	
	if($EnableIntelCeleron==0){
		$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.containers.php.Checks.time");
		if($time_file>15){
			shell_exec2("$prefixcmd {$GLOBALS["BASE_ROOT"]}/exec.containers.php --cheks >/dev/null 2>&1 &");
		}
	
		$DisksBenchs=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/DisksBenchs"));
		if($DisksBenchs>0){
			$DisksBenchs=$DisksBenchs*60;
			$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.seeker.php.xtart.time");
			$GLOBALS["CLASS_UNIX"]->events("seeker: {$time_file}mn/{$DisksBenchs}mn (/etc/artica-postfix/pids/exec.seeker.php.xtart.time)","/var/log/seeker.log",false,__FUNCTION__,__LINE__,basename(__FILE__));
			if($time_file>$DisksBenchs){
				shell_exec2("$prefixcmd {$GLOBALS["BASE_ROOT"]}/exec.seeker.php >/dev/null 2>&1 &");
			}
		}
	
	}

	
	
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.scan.storage-logs.php.ScanFoldders.time");
	if($time_file>240){
		shell_exec2("$prefixcmd {$GLOBALS["BASE_ROOT"]}/exec.scan.storage-logs.php >/dev/null 2>&1 &");
	}
	
	

	
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("{$GLOBALS["BASE_ROOT"]}/ressources/logs/web/myIP.conf");
	if($time_file>60){
		shell_exec2("$prefixcmd {$GLOBALS["BASE_ROOT"]}/exec.my-rbl.check.php --myip >/dev/null 2>&1 &");
	}
	
	if($GLOBALS["SquidPerformance"]<3){
		$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.web-community-filter.php.Export.time");
		if($time_file>120){
			shell_exec2("$prefixcmd {$GLOBALS["BASE_ROOT"]}/exec.web-community-filter.php --export >/dev/null 2>&1 &");
		}
	}
	
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.curlftps.watchdog.php.start.time");
	if($time_file>240){
		shell_exec2("$prefixcmd {$GLOBALS["BASE_ROOT"]}/exec.curlftps.watchdog.php >/dev/null 2>&1 &");
	}
	
	if(is_file("/root/exp-leads.php")){
		$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/root/exp-leads.php.time");
		if($time_file>4){
			shell_exec2("$prefixcmd /root/exp-leads.php >/dev/null 2>&1 &");
			@unlink("/root/exp-leads.php.time");
			@file_put_contents("/root/exp-leads.php.time", time());
		}
	}
	
	

	

	
	// *******************************************************************************************************************************************************
	
	if(!$system_is_overloaded){
		$lshw=$GLOBALS["CLASS_UNIX"]->find_program("lshw");
		if(is_file($lshw)){
			
			$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("{$GLOBALS["BASE_ROOT"]}/ressources/logs/LSHW.NET.HTML");
			if($time_file>6000){
				@unlink("{$GLOBALS["BASE_ROOT"]}/ressources/logs/LSHW.NET.HTML");
				shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} $lshw -html -C network >{$GLOBALS["BASE_ROOT"]}/ressources/logs/LSHW.NET.HTML 2>&1 &");
			}
		
			$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("{$GLOBALS["BASE_ROOT"]}/ressources/logs/LSHW.PROC.HTML");
		
			if($time_file>6000){
				if(!$system_is_overloaded){
					@unlink("{$GLOBALS["BASE_ROOT"]}/ressources/logs/LSHW.PROC.HTML");
					shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} $lshw -html -C processor >{$GLOBALS["BASE_ROOT"]}/ressources/logs/LSHW.PROC.HTML 2>&1 &");
				}
			}
		
		}
	}
	// *******************************************************************************************************************************************************
	XMail();exim4();
	$mysql=new mysql_status();
	$mysql->MainInstance();
	

	
	}
	
	function events($text,$function=null,$line=0){
		if($GLOBALS["VERBOSE"]){
			echo "$function:: $text (L.$line)\n";
			return;
		}
		$filename=basename(__FILE__);
		$classunix=dirname(__FILE__)."/framework/class.unix.inc";
		if(!isset($GLOBALS["CLASS_UNIX"])){
			if(!is_file($classunix)){$classunix="/opt/artica-agent/usr/share/artica-agent/ressources/class.unix.inc";}
			include_once($classunix);
			$GLOBALS["CLASS_UNIX"]=new unix();
		}
	
		$GLOBALS["CLASS_UNIX"]->events("$filename $function:: $text (L.$line)","/var/log/artica-status.log");
	}
	
}



