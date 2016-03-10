<?php
function ftp_proxy(){
	$files=$GLOBALS["CLASS_UNIX"]->DirFiles('/etc/init.d',"ftp-proxy-[0-9]+");
	while (list ($basename, $value) = each ($files) ){
		
		if(!preg_match("#ftp-proxy-([0-9]+)#", $basename,$re)){continue;}
		$ID=$re[1];
		ftp_proxy_instance($ID);
	}
}

function ftp_proxy_pid($ID){
	
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/ftp-proxy.$ID.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	$daemonbin=$GLOBALS["CLASS_UNIX"]->find_program("ftp-proxy");
	$cmdline=basename($daemonbin).".*?ftp-proxy-$ID.conf";
	return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN($cmdline);
}


//========================================================================================================================================================
function ftp_proxy_instance($ID){
	$unix=new unix();
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("ftp-proxy");
	if(!is_file($bin)){return;}

	$master_pid=ftp_proxy_pid($ID);
	


	$l[]="[APP_FTP_PROXY_$ID]";
	$l[]="service_name=APP_FTP_PROXY_$ID";
	$l[]="service_cmd=/etc/init.d/ftp-proxy-$ID";
	$l[]="master_version=".ftp_proxy_version();
	$l[]="service_disabled=1";
	$l[]="pid_path=/var/run/ftp-proxy.$ID.pid";
	$l[]="watchdog_features=1";
	$l[]="family=network";


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			squid_admin_mysql(0, "FTP Proxy service ID $ID not running [action=start]", null,__FILE__,__LINE__);
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.ftpproxy.php --start $ID >/dev/null 2>&1 &");
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

//========================================================================================================================================================
function ftp_proxy_version(){
	if(isset($GLOBALS["ftp_proxy_version"])){return $GLOBALS["ftp_proxy_version"];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("ftp-proxy");
	exec("$bin -V 2>&1",$array);
	while (list ($pid, $line) = each ($array) ){
		if(preg_match("#version\s+([0-9\.\-]+)#i", $line,$re)){
			$GLOBALS["ftp_proxy_version"]=$re[1];
			return $re[1];}
		if($GLOBALS['VERBOSE']){echo "ftp_proxy_version(), $line, not found \n";}
	}
}