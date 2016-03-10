<?php


function privoxy_pid(){
	$pid_path="/var/run/privoxy.pid";
	if(!is_file($pid_path)){
		$binpath=$GLOBALS["CLASS_UNIX"]->find_program("privoxy");
		return $GLOBALS["CLASS_UNIX"]->PIDOF($binpath); 
	}
	$pids=explode("\n",@file_get_contents($pid_path));
	while (list ($index, $line) = each ($pids)){
		$line=str_replace("\r", "", $line);
		$line=str_replace("\n", "", $line);
		if(!is_numeric(trim($line))){continue;}
		if($GLOBALS["VERBOSE"]){echo "$pid_path = $line\n";}
		if($GLOBALS["CLASS_UNIX"]->process_exists($line)){
			$PPID=$GLOBALS["CLASS_UNIX"]->PPID_OF($line);
			if($GLOBALS["VERBOSE"]){echo "$line ->running PPID:$PPID\n";}
			return $PPID;
		}
	}
	
}
function privoxy_version($bin){
	if(isset($GLOBALS["privoxy_version"])){return $GLOBALS["privoxy_version"];}
	exec("$bin --version 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^Privoxy version\s+([0-9a-z\.]+)#",$line,$re)){continue;}
		$GLOBALS["privoxy_version"]=$re[1];
	}

	return $GLOBALS["privoxy_version"];

}

function privoxy(){
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program("privoxy");
	$l[]="[APP_PRIVOXY]";
	$l[]="service_name=APP_PRIVOXY";
	
	if(!is_file($binpath)){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/PrivoxyInstalled", 0);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/PrivoxyEnabled", 0);
		$l[]="running=0\ninstalled=0";
		return @implode("\n", $l);
		
	}
	
	
	@file_put_contents("/etc/artica-postfix/settings/Daemons/PrivoxyInstalled", 1);
	if(!is_file("/etc/artica-postfix/settings/Daemons/PrivoxyEnabled")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/PrivoxyEnabled", 0);
	}
	$enabled=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/PrivoxyEnabled"));
	$pid_path="/var/run/privoxy.pid";


	$PrivoxyVersion=privoxy_version($binpath);
	@file_put_contents("/etc/artica-postfix/settings/Daemons/PrivoxyVersion", $PrivoxyVersion);
	
	if(!is_file("/etc/init.d/privoxy")){
		$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.initslapd.php --privoxy");
		shell_exec2($cmd);
	}
	
	$l[]="master_version=$PrivoxyVersion";
	$l[]="service_cmd=/etc/init.d/privoxy";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=network";
	
	

	if($enabled==0){
		$l[]="";
		return implode("\n",$l);
	}
	$PPID=privoxy_pid();
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($PPID)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			squid_admin_mysql(0, "AdsBlocker service not running [action=start]", null,__FILE__,__LINE__);
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.privoxy.php --start >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		$l[]="";
		return implode("\n",$l);

	}
	
	
	$l[]=GetMemoriesOf($PPID);
	$l[]="";
	

	return implode("\n",$l);return;
}

