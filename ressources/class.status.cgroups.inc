<?php


function cgroups(){
	
	$cgrulesengd=$GLOBALS["CLASS_UNIX"]->find_program("cgrulesengd");
	if(!is_file($cgrulesengd)){@file_put_contents("/etc/artica-postfix/settings/Daemons/CGROUPS_INSTALLED", 0);return;}
	$cgroupsEnabled=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/cgroupsEnabled"));
	$cgroupsServiceEnabled=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/cgroupsServiceEnabled"));
	@file_put_contents("/etc/artica-postfix/settings/Daemons/CGROUPS_INSTALLED", 1);

	$l[]="[APP_CGROUPS]";
	$l[]="service_name=APP_CGROUPS";
	$l[]="master_version=0.0";
	$l[]="service_disabled=$cgroupsServiceEnabled";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	$l[]="service_cmd=/etc/init.d/cgred";
	
	if($cgroupsEnabled==1){
		if(!class_exists("unix_cgroups")){
			include_once(dirname(__FILE__)."/class.unix.cgroups.inc");
		}
		$c=new unix_cgroups();
		$c->mount_structure();
		$c->create_service_structure("kasupt");
		$c->create_service_structure("php");
	}
	
	
	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($cgrulesengd);
	if($cgroupsServiceEnabled==0){
		if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.cgroups.php --stop");
		}
		
		$l[]="";
		return implode("\n",$l);
	}
	

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.cgroups.php --cgred-start >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;
}
