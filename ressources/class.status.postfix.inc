<?php
if(!isset($GLOBALS["BASE_ROOT"])){$GLOBALS["BASE_ROOT"]="/usr/share/artica-postfix";}




function postfix_increment_func($array){
	if(!isset($GLOBALS["CLASS_SOCKETS"])){ if(function_exists("LoadIncludes")){LoadIncludes();}else{return $array;} }
	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	$EnableStopPostfix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStopPostfix"));
	if($DisableMessaging==1){$EnableStopPostfix=1;$EnablePostfixMultiInstance=0;}
	if($DisableMessaging==1){return $array;}
	if($EnableStopPostfix==1){return $array;}
	
	$array[]="postfix";
	$array[]="iredmail";
	$array[]="clammilter";
	$array[]="postfix_logger";
	$array[]="opendkim";
	$array[]="milter_greylist";
	$array[]="milter_greylist_updates";
	$array[]="postfix_schedules";
	$array[]="policyd_weight";
	$array[]="milter_regex";
	$array[]="spamassassin_milter";
	$array[]="spamassassin";
	$array[]="roundcube";
	//$array[]="valvulad";
	$array[]="mimedefang";
	$array[]="cleanClode";
	
	return $array;
}

function postfix_version(){

	return $GLOBALS["CLASS_UNIX"]->POSTCONF_GET("mail_version");
}

function clammilter_version($bin){
	if(isset($GLOBALS["clammilter_version"])){return $GLOBALS["clammilter_version"];}
	
	exec("$bin -V 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^clamav-milter\s+([0-9a-z\.]+)#",$line,$re)){continue;}
		$GLOBALS["clammilter_version"]=$re[1];
	}
	
	return $GLOBALS["clammilter_version"];
	
}

function posfix_pid(){
	$master_pid=0;
	$postfix_path=$GLOBALS["CLASS_UNIX"]->find_program("postfix");
	$master_pid=$GLOBALS["CLASS_UNIX"]->POSTFIX_PID();
	if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){return $master_pid;}
	exec("$postfix_path status 2>&1",$status_results);
	
	while (list ($num, $line) = each ($status_results) ){
		if(preg_match("#PID:.+?([0-9]+)#", $line,$re)){
			$GLOBALS["DEBUG_LOGS"][]="postfix status: $line";
			return intval($re[1]);
		}
	}
	
	$master_bin_path=$GLOBALS["CLASS_UNIX"]->POSTFIX_MASTER_BIN_PATH();
	return($GLOBALS["CLASS_UNIX"]->PIDOF($master_bin_path,true));
			
	
	
}

function postfix(){
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("postconf");
	if($bin_path==null){return null;}
	if(is_file("/etc/artica-postfix/DO_NOT_DETECT_POSTFIX")){return;}
	$EnablePostfixMultiInstance=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnablePostfixMultiInstance"));
	$EnableStopPostfix=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableStopPostfix"));
	$EnablePostfixAutoBlock=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnablePostfixAutoBlock"));
	$DisableMessaging=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/DisableMessaging"));
	
	if(is_file("/etc/cron.d/amavisd-new")){@unlink("/etc/cron.d/amavisd-new");}
	
	if($GLOBALS["VERBOSE"]){echo "EnablePostfixMultiInstance=\"$EnablePostfixMultiInstance\"\n";}
	
	if($DisableMessaging==1){$EnableStopPostfix=1;$EnablePostfixMultiInstance=0;}
	if($EnablePostfixMultiInstance==1){$l[]=postfix_multi_status();}
	$master_pid=posfix_pid();
	
	if($EnableStopPostfix==1){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			events("watchdog-postfix:$EnableStopPostfix is enabled, stopping postfix",__FUNCTION__,__LINE__);
			$cmd="{$GLOBALS["nohup"]} /etc/init.d/postfix stop >/dev/null 2>&1 &";
			events("watchdog-postfix:$EnableStopPostfix is enabled, stopping postfix -> $cmd" ,__FUNCTION__,__LINE__);
			shell_exec2($cmd);
		}
	}

	if($EnableStopPostfix==0){
		$sendmail_pid_path=$GLOBALS["CLASS_UNIX"]->LOCATE_SENDMAIL_PID_PATH();
		if(strlen($sendmail_pid_path)>3){
			$sendmail_pid=file_get_contents($sendmail_pid_path);
			if(is_numeric($sendmail_pid)){
				events("watchdog-postfix:Sendmail pid detected $sendmail_pid_path ($sendmail_pid)",__FUNCTION__,__LINE__);
				if($GLOBALS["CLASS_UNIX"]->process_exists($sendmail_pid)){
					$kill=$GLOBALS["CLASS_UNIX"]->find_program("kill");
					$postfix=$GLOBALS["CLASS_UNIX"]->find_program("postfix");
					$GLOBALS["CLASS_UNIX"]->KILL_PROCESS($sendmail_pid,9);
					@unlink($sendmail_pid);
					$GLOBALS["CLASS_UNIX"]->send_email_events("SendMail (pid $sendmail_pid) is running, kill it !!","This action has been performed to avoid ports conflicts","smtp");
					shell_exec2("$postfix start  >/dev/null 2>&1 &");
				}
			}
		}
	}



	$l[]="[POSTFIX]";
	$l[]="service_name=APP_POSTFIX";
	$l[]="master_version=".postfix_version();
	$l[]="service_cmd=/etc/init.d/postfix";
	$l[]="service_disabled=1";
	$l[]="remove_cmd=--postfix-remove";
	$l[]="family=postfix";
	$l[]="watchdog_features=1";
	if($GLOBALS["ArticaWatchDogList"]["APP_POSTFIX"]==null){$GLOBALS["ArticaWatchDogList"]["APP_POSTFIX"]=1;}
	if($EnableStopPostfix==0){
		if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			if(!$GLOBALS["DISABLE_WATCHDOG"]){
				$GLOBALS["DEBUG_LOGS"][]="$master_pid does not exists";
				$postfix_path=$GLOBALS["CLASS_UNIX"]->find_program("postfix");
				$GLOBALS["DEBUG_LOGS"][]="Postfix bin = $postfix_path";
				exec("$postfix_path start -v 2>&1",$pstfix_start);
				postfix_admin_mysql(0, "Postfix MTA Stopped [action=start]", @implode("\n",$pstfix_start)."\n".@implode("\n", $GLOBALS["DEBUG_LOGS"]),__FILE__,__LINE__);
				unset($GLOBALS["DEBUG_LOGS"]);
			}
					
		$l[]="";return implode("\n",$l);
		}
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	
	if($EnableStopPostfix==1){
		if(is_file("/etc/cron.d/pflogsumm-stats")){
			@unlink("/etc/cron.d/pflogsumm-stats");
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/cron reload");
			return;
		}
		
		return implode("\n",$l);
	}
	if(system_is_overloaded()){return implode("\n",$l);}
	
	//$cmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.postfix.iptables.php --export-drop >/dev/null 2>&1 &";
	//shell_exec2($cmd);
	
	//$cmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.smtp-senderadv.php >/dev/null 2>&1 &";
	//shell_exec2($cmd);

	
	$timefile="/etc/artica-postfix/pids/exec.postqueue.watchdog.php.pid";
	$exTime=$GLOBALS["CLASS_UNIX"]->file_time_min($timefile);
	if($exTime>5){
		$cmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.postqueue.watchdog.php >/dev/null 2>&1 &";
		shell_exec2($cmd);
	}
	
	$timefile="/etc/artica-postfix/pids/exec.pflogsumm.php.stats_total.time";
	
	if(!is_file("/etc/cron.d/pflogsumm-stats")){
		Popuplate_cron_make("pflogsumm-stats","0,5,10,15,20,25,30,35,40,45,50,55 * * * *","exec.pflogsumm.php --stats");
		shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/cron reload");
		return;
	}
	
	$exTime=$GLOBALS["CLASS_UNIX"]->file_time_min($timefile);
	if($exTime>5){
		$cmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.pflogsumm.php --stats >/dev/null 2>&1 &";
		shell_exec2($cmd);
	}

	$timefile="/etc/artica-postfix/pids/postqueue.clean.time";
	$exTime=$GLOBALS["CLASS_UNIX"]->file_time_min($timefile);
	if($exTime>5){
		$cmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.postfix-logger.php --postqueue-clean >/dev/null 2>&1 &";
		shell_exec2($cmd);
	}

	$timefile="/etc/artica-postfix/POSTFIX_COMPRESS_CLEAN.time";
	$exTime=$GLOBALS["CLASS_UNIX"]->file_time_min($timefile);
	if($exTime>1800){
		$cmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.logrotate.postfix.php --compress-clean >/dev/null 2>&1 &";
		shell_exec2($cmd);

	}

	return implode("\n",$l);

}

function clammilter(){
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("clamav-milter");
	
	$l[]="[CLAMAV_MILTER]";
	$l[]="service_name=APP_CLAMAV_MILTER";
	
	if(!is_file($bin_path)){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/ClamAVMilterInstalled", 0);
		$l[]="running=0\ninstalled=0";
		return @implode("\n", $l);
		
	}
	@file_put_contents("/etc/artica-postfix/settings/Daemons/ClamAVMilterInstalled", 1);
	
	
	$ClamavMilterEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamavMilterEnabled");
	if($ClamavMilterEnabled==null){$ClamavMilterEnabled=0;}
	$MimeDefangClamav=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangClamav"));
	$MimeDefangEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangEnabled"));
	if($MimeDefangEnabled==0){$MimeDefangClamav=0;}
	if($MimeDefangClamav==1){$ClamavMilterEnabled=0;}
	
	
	if($bin_path==null){return null;}
	$pid_path="/var/spool/postfix/var/run/clamav/clamav-milter.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$clammilter_version=clammilter_version($bin_path);
	@file_put_contents("/etc/artica-postfix/settings/Daemons/ClamAVMilterVersion", $clammilter_version);

	
	$l[]="master_version=$clammilter_version";
	$l[]="service_cmd=/etc/init.d/clamav-milter";
	$l[]="service_disabled=$ClamavMilterEnabled";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=postfix";

	if($ClamavMilterEnabled==0){$l[]="";$l[]="";return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.milter-clamav.php --start >/dev/null 2>&1 &");
		$l[]="";return implode("\n",$l);
		return;
	}


	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================


function postfix_schedules(){
	$ASPOST=true;
	$CRON_RELOAD=false;
	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	$EnableStopPostfix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStopPostfix"));
	if($DisableMessaging==1){$ASPOST=false;}
	if($EnableStopPostfix==1){$ASPOST=false;}
	
	if($ASPOST){
		if(!is_file("/etc/cron.d/artica-pflogsumm-hourly")){
			Popuplate_cron_make("artica-pflogsumm-hourly","59 * * * *","exec.pflogsumm.php");
			$CRON_RELOAD=true;
		}
		
		if(!is_file("/etc/cron.d/artica-rotate-postfix")){
			Popuplate_cron_make("artica-rotate-postfix","2 0 * * *","exec.logrotate.postfix.php");
			$CRON_RELOAD=true;
		}
		
		if(is_file("/etc/cron.d/artica-logcheck-postfix")){@unlink("/etc/cron.d/artica-logcheck-postfix");}
		

		if(!is_file("/etc/cron.d/artica-compresscl-postfix")){
			Popuplate_cron_make("artica-compresscl-postfix","3 30 * * *","exec.logrotate.postfix.php --compress-clean");
			$CRON_RELOAD=true;
		}

		if(!is_file("/etc/cron.d/artica-postgres-postfix")){
			Popuplate_cron_make("artica-postgres-postfix","5 30 * * *","exec.logrotate.postfix.php --convert-all");
			$CRON_RELOAD=true;
		}
		
		
	}else{
		if(is_file("/etc/cron.d/artica-pflogsumm-hourly")){
			@unlink("/etc/cron.d/artica-pflogsumm-hourly");
			$CRON_RELOAD=true;
		}
		if(is_file("/etc/cron.d/artica-postgres-postfix")){
			@unlink("/etc/cron.d/artica-postgres-postfix");
			$CRON_RELOAD=true;
		}
		if(is_file("/etc/cron.d/artica-logcheck-postfix")){
			@unlink("/etc/cron.d/artica-logcheck-postfix");
			$CRON_RELOAD=true;
		}		
		if(is_file("/etc/cron.d/artica-rotate-postfix")){
			@unlink("/etc/cron.d/artica-rotate-postfix");
			$CRON_RELOAD=true;
		}
		if(is_file("/etc/cron.d/artica-compresscl-postfix")){
			@unlink("/etc/cron.d/artica-compresscl-postfix");
			$CRON_RELOAD=true;
		}		
	}
	
	if($CRON_RELOAD){shell_exec("/etc/init.d/cron reload");}
	
	
	
	
	
}

function iredmail_pid(){
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/iredapd.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF("python.*?/opt/iRedAPD/iredapd");
}

function milter_greylist_updates(){
	$EnableBandwithCalculation=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableMilterGreylistExternalDB"));
	$BandwithCalculationSchedule=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/MilterGreylistExternalDBSchedule"));

	if($EnableBandwithCalculation==0){
		if(is_file("/etc/cron.d/artica-miltergreylist")){
			@unlink("/etc/cron.d/artica-miltergreylist");
			shell_exec("/etc/init.d/cron reload");
			return;
		}
	}

	if($BandwithCalculationSchedule==0){$BandwithCalculationSchedule=8;}
	$schedules[1]="0 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22 * * *";
	$schedules[2]="0 2,4,6,8,10,12,14,16,18,20,22 * * *";
	$schedules[4]="0 4,8,12,16,20 * * *";
	$schedules[8]="0 8,16 * * *";
	$schedules[24]="0 1 * * *";
	$schedule=$schedules[$BandwithCalculationSchedule];

	
	
	$GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("artica-miltergreylist",$schedule,"exec.milter-greylist.update.php");
	shell_exec("/etc/init.d/cron reload");
}

//========================================================================================================================================================
function policyd_weight(){
	if(!is_file("/usr/share/artica-postfix/bin/artica-install")){return;}
	$EnablePolicydWeight=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePolicydWeight"));
	if($EnablePolicydWeight==null){$EnablePolicydWeight=0;}

	$l[]="[POLICYD_WEIGHT]";
	$l[]="service_name=APP_POLICYD_WEIGHT";
	$l[]="service_cmd=policydw";
	$l[]="family=postfix";
	$l[]="master_version=".GetVersionOf("policydw");
	$l[]="service_disabled=$EnablePolicydWeight";
	$l[]="watchdog_features=1";
	//$l[]="remove_cmd=--pureftpd-remove";

	if($EnablePolicydWeight==0){
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$pid_path=$GLOBALS["CLASS_UNIX"]->POLICYD_WEIGHT_GET("PIDFILE");
	
	events("PID Path: $pid_path",__FUNCTION__,__LINE__);
	
	
	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);
	events("master_pid: $master_pid",__FUNCTION__,__LINE__);

	$l[]="watchdog_features=1";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$unix=new unix();
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("policyd-weight \(");
		events("PIDOF_PATTERN: $master_pid",__FUNCTION__,__LINE__);
	}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		events("Not running: $master_pid",__FUNCTION__,__LINE__);
		
		if(!is_file("/etc/init.d/policyd-weight")){
			shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.initslapd.php --policyd-weight");
			
		}
		
		shell_exec2("/etc/init.d/policyd-weight start");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}
//========================================================================================================================================================



function _milter_greylist_enabled(){
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/MilterGreyListEnabled")){@file_put_contents("/etc/artica-postfix/settings/Daemons/MilterGreyListEnabled", 1); }
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableMilterGreylistExternalDB")){@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableMilterGreylistExternalDB", 1); }
	
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("milter-greylist");
	if(!is_file($bin)){return 0;}
	
	
	$EnablePostfixMultiInstance=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfixMultiInstance");
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MilterGreyListEnabled");
	$EnableASSP=$GLOBALS["CLASS_SOCKETS"]->GET_INFO('EnableASSP');
	if($enabled==null){$enabled=0;}
	if($EnablePostfixMultiInstance==1){$enabled=0;}
	if($EnableASSP==1){$enabled=0;}
	if($GLOBALS["VERBOSE"]){echo "DEBUG: EnablePostfixMultiInstance: $EnablePostfixMultiInstance\n";}
	if($GLOBALS["VERBOSE"]){echo "DEBUG: EnableASSP: $EnableASSP\n";}
	if($GLOBALS["VERBOSE"]){echo "DEBUG: enabled: $enabled\n";}
	
	return $enabled;
}

function milter_greylist_version(){
	if(isset($GLOBALS["milter_greylist_version"])){return $GLOBALS["milter_greylist_version"];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("milter-greylist");
	exec("$bin -r 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^milter-greylist-([0-9a-z\.]+)#",$line,$re)){continue;}
		$GLOBALS["milter_greylist_version"]=$re[1];
	}
	
	return $GLOBALS["milter_greylist_version"];
	
}

function milter_greylist(){
	$EnableStopPostfix=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStopPostfix");
	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	$EnablePostfixMultiInstance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfixMultiInstance"));
	if(!is_numeric($EnableStopPostfix)){$EnableStopPostfix=0;}
	if($DisableMessaging==1){return;}
	if($EnableStopPostfix==1){return;}
	$enabled=_milter_greylist_enabled();
	$pid_path="/var/run/milter-greylist/milter-greylist.pid";
	if($GLOBALS["VERBOSE"]){echo "DEBUG: pid path: $pid_path\n";}

	if($pid_path==null){
		$pid_path=$GLOBALS["CLASS_UNIX"]->LOCATE_MILTER_GREYLIST_PID();
		if($GLOBALS["VERBOSE"]){echo "DEBUG: ->LOCATE_MILTER_GREYLIST_PID()= pid path: $pid_path\n";}
	}
	$master_pid=trim(@file_get_contents($pid_path));
	if($GLOBALS["VERBOSE"]){echo "DEBUG: ->LOCATE_MILTER_GREYLIST_PID()= master pid: $master_pid\n";}
	$l[]="[MILTER_GREYLIST]";
	$l[]="service_name=APP_MILTERGREYLIST";
	$l[]="master_version=".milter_greylist_version();
	$l[]="service_cmd=/etc/init.d/milter-greylist";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="remove_cmd=--milter-grelist-remove";
	$l[]="family=postfix";
	
	
	
	@chown("/var/run/milter-greylist/milter-greylist.sock","postfix");
	@chgrp("/var/run/milter-greylist/milter-greylist.sock", "postfix");
	@chmod("/var/run/milter-greylist/milter-greylist.sock", 0777);

	$dirname=dirname(__FILE__);
	if($EnablePostfixMultiInstance==1){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $dirname/exec.milter-greylist.php --startall >/dev/null 2>&1 &");
		return;
	}

	if($enabled==0){return implode("\n",$l);return;}
	
	if(!is_file("/etc/init.d/milter-greylist")){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $dirname/exec.initslapd.php --milter-greylist >/dev/null 2>&1 &");
	}
	

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $dirname/exec.milter-greylist.php --start-single >/dev/null 2>&1 &");
		}
		$l[]="running=0";
		$l[]="installed=1\n";
		return implode("\n",$l);

	}
	
	$TimeFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".database.time";
	$TimeExec=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($TimeFile);
	if($TimeExec>5){
		@unlink($TimeFile);
		@file_put_contents($TimeFile, time());
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.milter-greylist.php --database >/dev/null 2>&1 &");
	}
	
	
	
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}



function milter_regex_pid(){
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/milter-regex.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program('milter-regex');
	return $GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
}

function milter_regex(){
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program('milter-regex');
	if($binpath==null){if($GLOBALS["VERBOSE"]){echo "postconf no such binary.\n";}return;}
	
	$EnableMilterRegex=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMilterRegex");
	if(!is_numeric($EnableMilterRegex)){$EnableMilterRegex=1;}

	$l[]="[milter_regex]";
	$l[]="service_name=milter_regex";
	$l[]="service_cmd=/etc/init.d/milter-regex";
	$l[]="master_version=2.0.0";
	$l[]="service_disabled=$EnableMilterRegex";
	$l[]="family=network";
	$l[]="watchdog_features=1";
	if($EnableMilterRegex==0){return implode("\n",$l);return;}
	$master_pid=milter_regex_pid();
	ToSyslog("Scanning Postfix Milter-regex watchdog PID: $master_pid");
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			if(!is_file("/etc/init.d/milter-regex")){
				shell_exec2("{$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.initslapd.php --milter-regex >/dev/null 2>&1");
			}
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} /etc/init.d/milter-regex start >/dev/null 2>&1 &");

		}
	}
	
	@chmod("/var/run/milter-regex/milter-regex.sock",0755);
	@chown("/var/run/milter-regex/milter-regex.sock","postfix");
	@chgrp("/var/run/milter-regex/milter-regex.sock","postfix");
	
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}

function iredmail(){
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program('postconf');
	if($binpath==null){if($GLOBALS["VERBOSE"]){echo "postconf no such binary.\n";}return;}
	$SquidAsMasterPeer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAsMasterPeer"));
	$EnableiredMail=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableiredMail");
	if(!is_numeric($EnableiredMail)){$EnableiredMail=1;}
	
	$l[]="[APP_IREDMAIL]";
	$l[]="service_name=APP_IREDMAIL";
	$l[]="service_cmd=/etc/init.d/iredmail";
	$l[]="master_version=1.4.2";
	$l[]="service_disabled=$EnableiredMail";
	$l[]="family=network";
	$l[]="watchdog_features=1";
	if($EnableiredMail==0){return implode("\n",$l);return;}	
	$master_pid=iredmail_pid();
	ToSyslog("Scanning Postfix iRedMail watchdog PID: $master_pid");
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			if(!is_file("/etc/init.d/iredmail")){
				shell_exec2("{$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}/exec.initslapd.php --iredmail >/dev/null 2>&1");
			}
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} /etc/init.d/iredmail start >/dev/null 2>&1 &");
		
		}
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
	
}




function postfix_logger_pid(){
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file('/etc/artica-postfix/exec.maillog.php.pid');
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}

	$pid=$GLOBALS["CLASS_UNIX"]->PIDOF("/usr/sbin/postfix-logger");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	
	return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("/usr/share/artica-postfix/exec.maillog.php");
}

//========================================================================================================================================================
function postfix_logger(){
	if(!is_file("/etc/artica-postfix/DO_NOT_DETECT_POSTFIX")){return;}
	$ActAsSMTPGatewayStatistics=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActAsSMTPGatewayStatistics");
	if(!is_numeric($ActAsSMTPGatewayStatistics)){$ActAsSMTPGatewayStatistics=0;}
	if($ActAsSMTPGatewayStatistics==0){
		$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("postconf");
		if($bin_path==null){return null;}
	}
	
	$master_pid=postfix_logger_pid();
	ToSyslog("Scanning Postfix mail.log watchdog PID: $master_pid");

	$l[]="[ARTICA_MYSQMAIL]";
	$l[]="service_name=APP_ARTICA_MYSQMAIL";
	$l[]="master_version=".trim(@file_get_contents("/usr/share/artica-postfix/VERSION"));
	$l[]="service_cmd=/etc/init.d/postfix-logger";
	$l[]="service_disabled=1";
	$l[]="pid_path=/etc/artica-postfix/exec.maillog.php.pid";
	$l[]="watchdog_features=1";
	$l[]="family=postfix";
	$l[]="installed=1";

	


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		ToSyslog("Fatal, Postfix-logger did not running, start it...");
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.service.postfix-logger.php --start >/dev/null 2>&1 &");
			$l[]="running=0\ninstalled=1";$l[]="";
			return implode("\n",$l);
		}
	}

	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	$time=file_time_min("/etc/artica-postfix/pids/postfix-compress-logs.time");
	if($time>1440){
		@unlink("/etc/artica-postfix/pids/postfix-compress-logs.time");
		@file_get_contents("/etc/artica-postfix/pids/postfix-compress-logs.time",time());
		shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.logrotate.postfix.php --compress-clean >/dev/null 2>&1 &");
	}

	return implode("\n",$l);return;

}
//========================================================================================================================================================
function opendkim(){
	$debugFunc=false;
	if($debugFunc){ToSyslog("opendkim() -> EXECUTED");}
	if(!$GLOBALS["CLASS_USERS"]->OPENDKIM_INSTALLED){
		if($debugFunc){ToSyslog("opendkim() -> Not installed...`OPENDKIM_INSTALLED`");}
		
		return;}
	$EnableDKFilter=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDKFilter");
	if(!is_numeric($EnableDKFilter)){$EnableDKFilter=0;}
	$DisconnectDKFilter=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisconnectDKFilter");
	if(!is_numeric($DisconnectDKFilter)){$DisconnectDKFilter=0;}


	$pid_path="/var/run/opendkim/opendkim.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	if($DisableMessaging==1){$EnableDKFilter=0;}

	$l[]="[APP_OPENDKIM]";
	$l[]="service_name=APP_OPENDKIM";
	$l[]="master_version=".opendkim_version();
	$l[]="service_cmd=/etc/init.d/opendkim";
	$l[]="service_disabled=$EnableDKFilter";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=postfix";
	
	if($debugFunc){ToSyslog("opendkim() -> EnableDKFilter..... = $EnableDKFilter");}
	if($debugFunc){ToSyslog("opendkim() -> DisconnectDKFilter  = $DisconnectDKFilter");}

	if($EnableDKFilter==0){$l[]="";
	if($debugFunc){ToSyslog("opendkim() -> EnableDKFilter  = $EnableDKFilter -> ABORT");}
		return implode("\n",$l);
		
	}

	if($debugFunc){ToSyslog("opendkim() -> master_pid  = $master_pid -> ?");}
	if($debugFunc){ToSyslog("opendkim() -> master_pid  = $master_pid -> Not running");}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if($DisconnectDKFilter==0){
			if(!$GLOBALS["DISABLE_WATCHDOG"]){
				$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
				if(!is_file("/etc/init.d/opendkim")){
					shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.initslapd.php --opendkim >/dev/null 2>&1 &");
				}
				ToSyslog("opendkim() -> master_pid  = $master_pid -> Not running -> /etc/init.d/opendkim start");
				shell_exec2("$nohup {$GLOBALS["NICE"]} /etc/init.d/opendkim start >/dev/null 2>&1 &");
					
			}
		}
		$l[]="";
		return implode("\n",$l);
		
	}
	if($debugFunc){ToSyslog("opendkim() -> master_pid  = $master_pid -> running OK");}
	
	if(!$GLOBALS["CLASS_UNIX"]->is_socket("/var/run/opendkim/opendkim.sock")){
		ToSyslog("Fatal, opendkim: /var/run/opendkim/opendkim.sock not available -> restart");
		shell_exec2("$nohup {$GLOBALS["NICE"]} /etc/init.d/opendkim restart >/dev/null 2>&1 &");
	}else{
		if($debugFunc){ToSyslog("opendkim() -> /var/run/opendkim/opendkim.sock OK");}
	}
	
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	ToSyslog("opendkim() -> check done");
	return implode("\n",$l);return;

}
//========================================================================================================================================================

function spamassassin_milter_version(){
	if(isset($GLOBALS["spamassassin_milter_version"])){return $GLOBALS["spamassassin_milter_version"];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("spamass-milter");
	exec("$bin -h 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#Version\s+([0-9\.]+)#",$line,$re)){continue;}
		$GLOBALS["spamassassin_milter_version"]=$re[1];
	}
	
	return $GLOBALS["spamassassin_milter_version"];	
	
}

function spamassassin_milter(){
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("spamass-milter");
	$SpamAssMilterEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SpamAssMilterEnabled"));
	
	if($GLOBALS["CLASS_USERS"]->AMAVIS_INSTALLED){
		$EnableAmavisDaemon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAmavisDaemon"));
		if($EnableAmavisDaemon==1){$SpamAssMilterEnabled=0;}
	}
	
	$MimeDefangEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangEnabled"));
	$MimeDefangSpamAssassin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangSpamAssassin"));
	if($MimeDefangEnabled==0){$MimeDefangSpamAssassin=0;}
	if($MimeDefangSpamAssassin==1){$SpamAssMilterEnabled=0;}
	
	
	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	if($DisableMessaging==1){$SpamAssMilterEnabled=0;}
	
	
	if($bin_path==null){return null;}
	$pid_path="/var/run/spamass/spamass.pid";
	$master_pid=trim(@file_get_contents($pid_path));


	$l[]="[SPAMASS_MILTER]";
	$l[]="service_name=APP_SPAMASS_MILTER";
	$l[]="master_version=".spamassassin_milter_version();
	$l[]="service_cmd=/etc/init.d/spamass-milter";
	$l[]="service_disabled=$SpamAssMilterEnabled";
	$l[]="pid_path=$pid_path";
	$l[]="family=postfix";

	$mem=$GLOBALS["CLASS_UNIX"]->TOTAL_MEMORY_MB();
	

	if($SpamAssMilterEnabled==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/spamass-milter stop");}
		return implode("\n",$l);
	}



	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			postfix_admin_mysql(0, "Spamassassin Milter service not running action=Start", null,__FILE__,__LINE__);
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} /etc/init.d/spamass-milter start >/dev/null 2>&1 &");
			
		}
		
		$l[]="";return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================
function spamassassin_version(){
	if(isset($GLOBALS["spamassassin_version"])){return $GLOBALS["spamassassin_version"];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("spamd");
	if(is_file("/usr/local/bin/spamd")){$bin="/usr/local/bin/spamd";}
	exec("$bin -h 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#SpamAssassin Server version\s+([0-9\.]+)#",$line,$re)){continue;}
		$GLOBALS["spamassassin_version"]=$re[1];
	}

	return $GLOBALS["spamassassin_version"];

}
//========================================================================================================================================================
function valvulad_version($binary){
	if(isset($GLOBALS["valvulad_version"])){return $GLOBALS["valvulad_version"];}
	exec("$binary --version 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^([0-9\.]+)#",$line,$re)){continue;}
		$GLOBALS["valvulad_version"]=$re[1];
		break;
	}
	
	return $GLOBALS["valvulad_version"];
	
	
}

function valvulad(){
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("valvulad");
	
	$l[]="[APP_VALVUAD]";
	$l[]="service_name=APP_VALVUAD";
	
	if(!is_file($bin_path)){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/ValvuladInstalled", 0);
		$l[]="running=0\ninstalled=0";
		return @implode("\n", $l);
	}
	
	
	@file_put_contents("/etc/artica-postfix/settings/Daemons/ValvuladInstalled", 1);
	if(!is_file("/etc/artica-postfix/settings/Daemons/ValvuladEnabled")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/ValvuladEnabled", 1);
	}
	
	
	$ValvuladEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ValvuladEnabled"));
	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	if($DisableMessaging==1){$ValvuladEnabled=0;}
	$valvulad_version=valvulad_version($bin_path);
	@file_put_contents("/etc/artica-postfix/settings/Daemons/ValvuladVersion", $valvulad_version);
	
	$pid_path="/var/run/valvulad.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="master_version=$valvulad_version";
	$l[]="service_cmd=/etc/init.d/valvulad";
	$l[]="service_disabled=$ValvuladEnabled";
	$l[]="pid_path=$pid_path";
	$l[]="family=postfix";

	


	if($ValvuladEnabled==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/valvulad stop");}
		return implode("\n",$l);
	}



	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			postfix_admin_mysql(0, "Valvulad Milter service not running [action=start]", null,__FILE__,__LINE__);
			if(!is_file("/etc/init.d/valvuad")){
				shell_exec("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.initslapd.php --valvuad");
			}
			
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} /etc/init.d/valvulad start >/dev/null 2>&1 &");
				
		}

		$l[]="";return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}


function spamassassin(){
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("spamd");
	if(is_file("/usr/local/bin/spamd")){$bin_path="/usr/local/bin/spamd";}
	$SpamAssMilterEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SpamAssMilterEnabled"));

	if($GLOBALS["CLASS_USERS"]->AMAVIS_INSTALLED){
		$EnableAmavisDaemon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAmavisDaemon"));
		if($EnableAmavisDaemon==1){$SpamAssMilterEnabled=0;}
	}

	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	if($DisableMessaging==1){$SpamAssMilterEnabled=0;}

	
	if($bin_path==null){return null;}
	$pid_path="/var/run/spamd.pid";
	$master_pid=trim(@file_get_contents($pid_path));


	$l[]="[SPAMASSASSIN]";
	$l[]="service_name=APP_SPAMASSASSIN";
	$l[]="master_version=".spamassassin_version();
	$l[]="service_cmd=/etc/init.d/spamassassin";
	$l[]="service_disabled=$SpamAssMilterEnabled";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=postfix";
	
	
	if($SpamAssMilterEnabled==1){
		if(!is_file("/etc/cron.d/artica-sa-update")){
			Popuplate_cron_make("artica-sa-update","15 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22 * * *","exec.spamassassin.php --sa-update-check");
			shell_exec("/etc/init.d/cron reload");
			return;
		}
	}else{
		if(is_file("/etc/cron.d/artica-sa-update")){
			@unlink("/etc/cron.d/artica-sa-update");
			shell_exec("/etc/init.d/cron reload");
		}
	}
	
	

	if($SpamAssMilterEnabled==0){$l[]="";return implode("\n",$l);return;}
	sa_update_directory();
	
	$SpamAssassinUpdateDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SpamAssassinUpdateDir");
	if(!is_dir($SpamAssassinUpdateDir)){
		postfix_admin_mysql(0, "Spamassassin $SpamAssassinUpdateDir empty, running sa-update",null,__FILE__,__LINE__);
		shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} exec.spamassassin.php --sa-update >/dev/null 2>&1 &");
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
		postfix_admin_mysql(0, "Spamassassin Daemon service not running action=Start", null,__FILE__,__LINE__);
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} /etc/init.d/spamassassin start >/dev/null 2>&1 &");
		}
		$l[]="";
		return implode("\n",$l);
		
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================

function sa_update_directory(){
	$saupdate=$GLOBALS["CLASS_UNIX"]->find_program("sa-update");
	if(is_file("/usr/local/bin/sa-update")){$saupdate="/usr/local/bin/sa-update";}
	
	$f=explode("\n",@file_get_contents($saupdate));
	while (list ($num, $ligne) = each ($f) ){
		if(preg_match("#VERSION.*?=>.*?([0-9\.]+)#", $ligne,$re)){
			$UpdateDir="/var/lib/spamassassin/{$re[1]}";
			@file_put_contents("/etc/artica-postfix/settings/Daemons/SpamAssassinUpdateDir", $UpdateDir);
			break;
		}
		
	}
	
}



function roundcube_pid(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file('/var/run/roundcube-apache/apache.pid');
	if($unix->process_exists($pid)){return $pid;}
	$apache2ctl=$unix->LOCATE_APACHE_CTL();
	return $unix->PIDOF_PATTERN($apache2ctl." -f.*?apache-roundcube.conf");
}
function roundcube_version(){

	if(isset($GLOBALS["RC_VERSION"])){$GLOBALS["RC_VERSION"];}
	$f=explode("\n",@file_get_contents("/usr/share/roundcube/program/include/iniset.php"));
	while (list ($num, $ligne) = each ($f) ){
		$ligne=trim($ligne);
		if(!preg_match("#RCMAIL_VERSION.*?([0-9\.]+)#", $ligne,$re)){continue;}

		$GLOBALS["RC_VERSION"]= trim($re[1]);
		return $GLOBALS["RC_VERSION"];
	}

}

function roundcube_folders(){
	if(is_file('/usr/share/roundcubemail/index.php')){return '/usr/share/roundcubemail';}
	if(is_file('/usr/share/roundcube/index.php')){return '/usr/share/roundcube';}
	if(is_file('/var/lib/roundcube/index.php')){return '/var/lib/roundcube';}
}

//========================================================================================================================================================
function roundcube(){
	
	$INSTALLED=FALSE;
	$folder=roundcube_folders();
	if(is_dir($folder)){
		if(is_file("$folder/index.php")){$INSTALLED=true;}
	}
	
	
	if(!$INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}
		$l[]="";
		$l[]="[ROUNDCUBE]";
		$l[]="service_name=APP_ROUNDCUBE";
		$l[]="installed=0";
		$l[]="service_disabled=0";
		return implode("\n",$l);
	}

	$users=new settings_inc();
	if(!$GLOBALS["CLASS_USERS"]->POSTFIX_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." postfix not installed\n";}
		$l[]="";
		$l[]="[ROUNDCUBE]";
		$l[]="service_name=APP_ROUNDCUBE";
		$l[]="installed=0";
		$l[]="service_disabled=0";
		return implode("\n",$l);
	}
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/RoundCubeHTTPEngineEnabled")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/RoundCubeHTTPEngineEnabled", 1);
	}

	if(!is_file("/etc/artica-postfix/settings/Daemons/RoundCubeAutoCreateuser")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/RoundCubeAutoCreateuser", 1);
	}
	
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RoundCubeHTTPEngineEnabled");
	if(!is_numeric($enabled)){$enabled=0;}
	$pid_path="/var/run/roundcube-apache/apache.pid";
	$master_pid=roundcube_pid();


	$l[]="";
	$l[]="[ROUNDCUBE]";
	$l[]="service_name=APP_ROUNDCUBE";
	$l[]="master_version=".roundcube_version();
	$l[]="service_cmd=/etc/init.d/roundcube";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="installed=1";
	$l[]="family=mailbox";
	//$l[]="remove_cmd=--samba-remove";



	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if($GLOBALS["VERBOSE"]){echo "PID $master_pid Not exists\n";}
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.roundcube.php --restart >/dev/null 2>&1 &");
		$l[]="running=0\ninstalled=1";
		$l[]="";
		return implode("\n",$l);
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================
function mimedefang(){
	
	$l[]="[APP_MIMEDEFANG]";
	$l[]="service_name=APP_MIMEDEFANG";
	$MasterBin=$GLOBALS["CLASS_UNIX"]->find_program("mimedefang");
	if(is_file("/usr/local/bin/mimedefang")){$MasterBin="/usr/local/bin/mimedefang";}

	
	if(!is_file($MasterBin)){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/MimeDefangInstalled", 0);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/MimeDefangEnabled", 0);
		$l[]="running=0\ninstalled=0";
		return @implode("\n", $l);
	}
	
	
	@file_put_contents("/etc/artica-postfix/settings/Daemons/MimeDefangInstalled", 1);
	$MimeDefangEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO('MimeDefangEnabled'));
	

	if($GLOBALS["VERBOSE"]){echo "DEBUG: MimeDefangEnabled..: $MimeDefangEnabled\n";}
	$pid_path="/var/spool/MIMEDefang/mimedefang.pid";
	if($GLOBALS["VERBOSE"]){echo "DEBUG: pid path....: $pid_path\n";}
	$master_pid=trim(@file_get_contents($pid_path));
	if($GLOBALS["VERBOSE"]){echo "DEBUG: master pid..: $master_pid\n";}

	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	if($DisableMessaging==1){$MimeDefangEnabled=0;}


	$l[]="master_version=".mimedefang_version($MasterBin);
	$l[]="service_cmd=/etc/init.d/mimedefang";
	$l[]="service_disabled=$MimeDefangEnabled";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=postfix";
	
	if(!is_file("/etc/init.d/mimedefang")){
		shell_exec("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.mimedefang.php --init");
		
	}
	
	

	$mem=$GLOBALS["CLASS_UNIX"]->TOTAL_MEMORY_MB();
	if($mem<1500){
		$MimeDefangEnabled=0;
		postfix_admin_mysql(0, "Server Memory {$mem}MB < 1500, disable MimeDefang Service", null,__FILE__,__LINE__);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/MimeDefangEnabled", 0);
	}

	if($MimeDefangEnabled==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			postfix_admin_mysql(0, "Stopping MimeDefang service...", null,__FILE__,__LINE__);
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/mimedefang stop");
		}
		return implode("\n",$l)."\n".mimedefangmx();
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		postfix_admin_mysql(0, "Starting MimeDefang service (not running)..", null,__FILE__,__LINE__);
		$l[]="running=0";
		$l[]="installed=1\n";
		return implode("\n",$l)."\n".mimedefangmx();
		
	}
	
	
	if(!$GLOBALS["CLASS_UNIX"]->is_socket('/var/spool/MIMEDefang/mimedefang.sock')){
		postfix_admin_mysql(0, "mimedefang.sock No such socket [action=restart]", null,__FILE__,__LINE__);
		shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/mimedefang restart");
	}
	
	
	
	$MimeDefangArchiver=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/MimeDefangArchiver"));
	$MilterGreyListEnabled=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/MilterGreyListEnabled"));
	$MimeDefangAutoWhiteList=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/MimeDefangAutoWhiteList"));
	$MimeDefangSpamAssassin=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/MimeDefangSpamAssassin"));
	
	if($MimeDefangArchiver==1){
		$pidtime="/etc/artica-postfix/pids/exec.mimedefang.backup.php.start.time";
		$execTime=$GLOBALS["CLASS_UNIX"]->file_time_min($pidtime);
		if($execTime>5){
			shell_exec("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.mimedefang.backup.php >/dev/null 2>&1 &");
		}
	}
	
	if($MimeDefangSpamAssassin==1){
		$pidtime="/etc/artica-postfix/pids/exec.mimedefang.quarantine.php.start.time";
		$execTime=$GLOBALS["CLASS_UNIX"]->file_time_min($pidtime);
		if($execTime>5){
			shell_exec("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.mimedefang.quarantine.php >/dev/null 2>&1 &");
		}
	}
	
	
	if($MilterGreyListEnabled==1){
		if($MimeDefangAutoWhiteList==1){
			$pidtime="/etc/artica-postfix/pids/exec.milter-greylist.php.SingleInstance_whitelist.pid";
			$execTime=$GLOBALS["CLASS_UNIX"]->file_time_min($pidtime);
			if($execTime>10){
				shell_exec("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.milter-greylist.php --AutoWhiteList >/dev/null 2>&1 &");
			}
		}
	}
	
	
	$pidtime="/etc/artica-postfix/pids/exec.mimedefangToPostGresql.php.xrun.time";
	$execTime=$GLOBALS["CLASS_UNIX"]->file_time_min($pidtime);
	if($execTime>2){
		shell_exec("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.mimedefangToPostGresql.php >/dev/null 2>&1 &");
	}
	
	
	
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l)."\n".mimedefangmx();

}
//========================================================================================================================================================
function mimedefang_version($MasterBin){
	if(isset($GLOBALS["mimedefang_version"])){return $GLOBALS["mimedefang_version"];}
	
	$string=exec("$MasterBin -v 2>&1");
	if(preg_match("#version\s+([0-9\.]+)#",$string,$re)){
		$GLOBALS["mimedefang_version"]=$re[1];
		@file_put_contents("/etc/artica-postfix/settings/Daemons/MimeDefangVersion", $re[1]);
		return $re[1];
	}

}
//========================================================================================================================================================
function mimedefangmx(){
	
	$pid_path="/var/spool/MIMEDefang/mimedefang-multiplexor.pid";
	if($GLOBALS["VERBOSE"]){echo "DEBUG: pid path....: $pid_path\n";}
	$master_pid=trim(@file_get_contents($pid_path));
	if($GLOBALS["VERBOSE"]){echo "DEBUG: master pid..: $master_pid\n";}

	$binpath=$GLOBALS["CLASS_UNIX"]->find_program("mimedefang-multiplexor");
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		
		$masterpid=$GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			@file_put_contents($pid_path, $master_pid);
		}
	}



	$MimeDefangEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO('MimeDefangEnabled'));
	$l[]="[APP_MIMEDEFANGX]";
	$l[]="service_name=APP_MIMEDEFANGX";
	$l[]="master_version=".mimedefang_version($binpath);
	$l[]="service_cmd=/etc/init.d/mimedefang";
	$l[]="service_disabled=$MimeDefangEnabled";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=postfix";

	$mem=$GLOBALS["CLASS_UNIX"]->TOTAL_MEMORY_MB();
	if($mem<1500){
		$MimeDefangEnabled=0;
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("MimeDefangEnabled","0");
	}

	if($MimeDefangEnabled==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/mimedefang stop");}
		return implode("\n",$l);
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/mimedefang start");
		$l[]="running=0";
		$l[]="installed=1\n";
		return implode("\n",$l);
		return;
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================
function cleanClode(){
	
	$f[]="/usr/share/artica-postfix/exec.mailarchiver.php";
	$f[]="/usr/share/artica-postfix/miniadmin.postfix.archive.php";
	$f[]="/usr/share/artica-postfix/postfix.archiver.php";
	$f[]="/usr/share/artica-postfix/postfix.backup.fly.php";
	$f[]="/usr/share/artica-postfix/bin/milter_archiver.pl";
	
	while (list ($index,$filename) = each ($f) ){
		if(is_file($filename)){@unlink($filename);}
	}
	
	
}
