<?php
function watchdog_me(){
	if(!isset($GLOBALS["CLASS_UNIX"])){
		$GLOBALS["CLASS_SOCKETS"]=new sockets();
		$GLOBALS["CLASS_USERS"]=new settings_inc();
		$GLOBALS["CLASS_UNIX"]=new unix();
	}
	$BASENAME=dirname(__FILE__);
	$time=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.checkfolder-permissions.php.MAIN.time");
	if($time>240){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.checkfolder-permissions.php >/dev/null 2>&1 &");
	}

	if($GLOBALS["TOTAL_MEMORY_MB"]<400){
		events("watchdog_me: {$GLOBALS["TOTAL_MEMORY_MB"]}M installed on this computer, aborting",__FUNCTION__,__LINE__);
		$cmd=trim($GLOBALS["nohup"]." {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.parse-orders.php >/dev/null 2>&1 &");
		shell_exec2($cmd);
		$cmd=trim($GLOBALS["nohup"]." {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".__FILE__." --all >/dev/null 2>&1 &");
		shell_exec2($cmd);
		$cmd=trim($GLOBALS["nohup"]." {$GLOBALS["NICE"]}/etc/init.d/artica-postfix restart fcron >/dev/null 2>&1 &");
		shell_exec2($cmd);
		return;
	}

	$DisableArticaStatusService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableArticaStatusService"));
	$EnableArticaMirror=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaMirror"));

	if($EnableArticaMirror==1){
		$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.artica.mirror.php.start.time");
		if($time_file>25){
			$cmd=trim($GLOBALS["nohup"]." {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} $BASENAME/exec.artica.mirror.php >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}

	}

	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."()::".__LINE__." DisableArticaStatusService=$DisableArticaStatusService\n";}

	if($DisableArticaStatusService==1){
		$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min($GLOBALS["MY-POINTER"]);
		events("Pointer: {$GLOBALS["MY-POINTER"]} = {$time_file}Mn",__FUNCTION__,__LINE__);
		if($time_file>3){
			events("Pointer: start artica-status !!!",__FUNCTION__,__LINE__);
			$cmd=trim($GLOBALS["nohup"]." {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".__FILE__." --all >/dev/null 2>&1 &");
			shell_exec2($cmd);
			$cmd=trim($GLOBALS["nohup"]." {$GLOBALS["NICE"]}/etc/init.d/artica-postfix restart fcron >/dev/null 2>&1 &");
			shell_exec2($cmd);
				
		}
		Scheduler();
		return;
	}


	Scheduler();

	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min($GLOBALS["MY-POINTER"]);
	events("Pointer: {$GLOBALS["MY-POINTER"]} = {$time_file}Mn",__FUNCTION__,__LINE__);
	if($time_file>5){
		events("Pointer: restart artica-status !!!",__FUNCTION__,__LINE__);
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} /etc/init.d/artica-status reload >/dev/null 2>&1 &");

	}



}

function watchdog_me_load(){
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];
	events("System load: $internal_load",__FUNCTION__,__LINE__);
	if($internal_load>2){
		if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
		$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
		exec("$pgrep -l -f \"/usr/bin/du -bs\" 2>&1",$results);
		if(is_array($results)){
			while (list ($num, $ligne) = each ($results) ){
				if(preg_match("#pgrep#",$ligne,$re)){continue;}
				if(!preg_match("#([0-9]+)\s+(.+)#",$ligne,$re)){continue;}
				$unix=new unix();
				events("System load: $internal_load killing du -bs process pid {$re[1]}",__FUNCTION__,__LINE__);
				$GLOBALS["CLASS_UNIX"]->KILL_PROCESS($re[1],9);
				
			}
		}
		
		
	}
	
	
}
