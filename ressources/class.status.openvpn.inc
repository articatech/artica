<?php


function openvpn_server_pid($Masterbin){

	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/openvpn/openvpn-server.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("$Masterbin --port.+?--dev");
}

function openvpn(){

	$l[]="[OPENVPN_SERVER]";
	$l[]="service_name=APP_OPENVPN";
	$l[]="service_cmd=openvpn";
	
	$unix=new unix();
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("openvpn");
	if(!is_file($bin_path)){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/OpenVPNInstalled", 0);
		$l[]="running=0\ninstalled=0";$l[]="";
		$cmd="{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.openvpn.install.php >/dev/null 2>&1 &";
		shell_exec2(trim($cmd));
		return @implode("\n", $l);
	}
	@file_put_contents("/etc/artica-postfix/settings/Daemons/OpenVPNInstalled", 1);
	$clientsDir=$GLOBALS["CLASS_UNIX"]->dirdir("/etc/artica-postfix/openvpn/clients");
	
	writelogs(count($clientsDir)." openvpn client session(s)",__FUNCTION__,__FILE__,__LINE__);
	if(count($clientsDir)>0){
		$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
		$cmd="$nohup {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.openvpn.php --wakeup-clients >/dev/null 2>&1 &";
		shell_exec2(trim($cmd));
	}

	
	$EnableOPenVPNServerMode=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableOPenVPNServerMode"));
	
	$pid_path="/var/run/openvpn/openvpn-server.pid";
	$l[]="[OPENVPN_SERVER]";
	$l[]="service_name=APP_OPENVPN";
	$l[]="service_cmd=/etc/init.d/openvpn-server";
	$l[]="master_version=".openvpn_version();
	$l[]="service_disabled=$EnableOPenVPNServerMode";
	//$l[]="remove_cmd=--pureftpd-remove";
	$l[]="family=vpn";
	$l[]="watchdog_features=1";
	if($EnableOPenVPNServerMode==0){return implode("\n",$l);return;}


	$master_pid=openvpn_server_pid();

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			system_admin_mysql(0, "Warning OpenVPN server not running [action=start]", null,__FILE__,__LINE__);
			$cmd=trim("{$GLOBALS["NICE"]} /etc/init.d/openvpn-server start");
			shell_exec2($cmd);
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		
		return implode("\n",$l);
		
	}

	$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
	$cmd="$nohup {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.openvpn.php --wakeup-server >/dev/null 2>&1 &";
	shell_exec2(trim($cmd));

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;


}

function openvpn_version(){
	$unix=new unix();
	if(isset($GLOBALS["openvpn_version"])){return $GLOBALS["openvpn_version"];}
	$bin_path=$unix->find_program("openvpn");
	exec("$bin_path --version 2>&1",$results);
	
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#OpenVPN\s+([0-9]+)\.([0-9]+)([a-z0-9\_\-\.]+)\s+#",$line,$re)){
			$GLOBALS["openvpn_version"]=$re[1].".{$re[2]}{$re[3]}";
			return $GLOBALS["openvpn_version"];
		}
	}
	
}


