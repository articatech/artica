<?php


function firehol_watch_status(){
	
	if(!$GLOBALS["CLASS_UNIX"]->IS_FIREHOLE_ACTIVE()){return;}
	$iptables_save=$GLOBALS["CLASS_UNIX"]->find_program("iptables-save");
	if(!is_file($iptables_save)){return null;}
	
	exec("$iptables_save 2>&1",$results);
	
	
	while (list ($index, $line) = each ($results)){
		if(preg_match("#FIREHOL:INVALID IN#", $line)){
			_firehol_countriesdeny();
			return true;
		}
	}
	
	system_admin_mysql(0, "Firewall no active! [action=restart]", 
	"Firewall service will be restarted",__FILE__,__LINE__);
	
	if(is_file("/usr/sbin/squid")){
		squid_admin_mysql(0, "Firewall no active! [action=restart]",
		"Firewall service will be restarted",__FILE__,__LINE__);
	}

	shell_exec2("/etc/init.d/firehol restart >/dev/null 2>&1");
	
}


function _firehol_countriesdeny(){
	
	$EnableIpBlocks=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIpBlocks"));
	if($EnableIpBlocks==0){return;}
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.ipblock.php.update.time");
	if($filetime<720){return;}
	
	$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.ipblock.php >/dev/null 2>&1");
	shell_exec2($cmd);
	
}

