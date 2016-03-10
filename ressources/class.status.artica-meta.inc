<?php




function status_schedule_meta(){
	$prefixcmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]}";
	
	$EnableArticaMetaClient=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaMetaClient"));
	$EnableArticaMetaServer=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaMetaServer"));
	if($EnableArticaMetaServer==1){$EnableArticaMetaClient=0;}
	
	
	
	if($EnableArticaMetaClient==1){
		$ArticaMetaPooling=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaMetaPooling"));
		if($ArticaMetaPooling==0){$ArticaMetaPooling=15;}
		$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/var/run/artica-meta-client.run");
		if($time_file>$ArticaMetaPooling){
			$cmdline="$prefixcmd {$GLOBALS["BASE_ROOT"]}/exec.artica-meta-client.php --ping >/dev/null 2>&1 &";
			writelogs_meta("Launch Meta PING {$time_file}Mn \"$cmdline\"", __FUNCTION__, __FILE__, __LINE__);
			shell_exec2($cmdline);
		}
	}
	
	
	if($EnableArticaMetaServer==1){
		$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.artica-meta-server.php.execute.time");
		if($time_file>20){
			$cmdline="$prefixcmd {$GLOBALS["BASE_ROOT"]}/exec.artica-meta-server.php >/dev/null 2>&1 &";
			shell_exec2($cmdline);
		}
		
		$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/cleanArticaMetaRepos.time");
		if($time_file>620){
			$cmdline="$prefixcmd {$GLOBALS["BASE_ROOT"]}/exec.clean.logs.php --artica-meta >/dev/null 2>&1 &";
			shell_exec2($cmdline);
		}		
		
		
	}
	
	
	
}