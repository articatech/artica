<?php
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new settings_inc();}
	if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}

	
function SLAPD_VERSION(){
	$CACHE_FILE="/etc/artica-postfix/SLAPD_VERSION";
	if(isset($GLOBALS["SLAPD_VERSION"])){return $GLOBALS["SLAPD_VERSION"];}
	
	if(is_file($CACHE_FILE)){
		$CACHE_TIME=$GLOBALS["CLASS_UNIX"]->file_time_min($CACHE_FILE);
		if($CACHE_TIME>2880){
			$GLOBALS["SLAPD_VERSION"]= @file_get_contents($CACHE_FILE);
			return $GLOBALS["SLAPD_VERSION"];
		}
	}
	
	$bin_path=$GLOBALS["CLASS_UNIX"]->LOCATE_SLPAD_PATH();
	exec("$bin_path -VVV 2>&1",$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#openldap-([0-9\.]+)#", $line,$re)){
			$GLOBALS["SLAPD_VERSION"]=$re[1];
			@unlink($CACHE_FILE);
			@file_put_contents($CACHE_FILE, $GLOBALS["SLAPD_VERSION"]);
			return $GLOBALS["SLAPD_VERSION"];
		}
		
	}
	
}	
	
	
function openldap(){

	if(!$GLOBALS["CLASS_USERS"]->openldap_installed){return;}
	$pid_path=GetVersionOf("openldap-pid");
	$master_pid=trim(@file_get_contents($pid_path));
	$bin_path=$GLOBALS["CLASS_UNIX"]->LOCATE_SLPAD_PATH();
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableOpenLDAP")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableOpenLDAP", 1);
	}
	if(!is_file("/etc/artica-postfix/settings/Daemons/RestartOpenLDAP")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/RestartOpenLDAP", 1);
	}	
	
	$EnableOpenLDAP=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableOpenLDAP"));
	$RestartOpenLDAP=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/RestartOpenLDAP"));

	if($GLOBALS["VERBOSE"]){
		echo "pid_path = $pid_path\n";
		echo "master_pid = $master_pid\n";
		echo "bin_path = $bin_path\n";
	}


	$CACHE_TIME=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/SLAPD_VERSION");
	$SLAPD_VERSION=trim(@file_get_contents("/etc/artica-postfix/SLAPD_VERSION"));
	if($CACHE_TIME>11520){@unlink($CACHE_TIME);}


	$SLAPD_VERSION=SLAPD_VERSION();
	
	$squidbin=$GLOBALS["CLASS_UNIX"]->LOCATE_SQUID_BIN();
	$SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
	$EnableIntelCeleron=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableIntelCeleron"));

	$l[]="[LDAP]";
	$l[]="service_name=APP_LDAP";
	$l[]="master_version=$SLAPD_VERSION";
	$l[]="service_cmd=/etc/init.d/slapd";

	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	$l[]="bin_path=$bin_path";


	if(is_file($squidbin)){
		if($SquidPerformance>2){
			openldap_disable_cron();
			$l[]="service_disabled=0";
			return @implode("\n", $l);
		}
		if($EnableIntelCeleron==1){
			openldap_disable_cron();
			$l[]="service_disabled=0";
			return @implode("\n", $l);
		}
	}

	if($EnableOpenLDAP==0){
		openldap_disable_cron();
		$l[]="service_disabled=0";
		return @implode("\n", $l);
	}
	
	if($RestartOpenLDAP==1){openldap_enable_cron();}
	if($RestartOpenLDAP==0){openldap_disable_cron();}

	$l[]="service_disabled=1";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	$l[]="bin_path=$bin_path";

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=trim($GLOBALS["CLASS_UNIX"]->PIDOF($bin_path));
		if($master_pid>3){
			@file_put_contents($pid_path,$master_pid);
		}
	}


	$CACHE_TIME=$GLOBALS["CLASS_UNIX"]->file_time_min("/usr/share/artica-postfix/ressources/local_ldap.php");
	if($CACHE_TIME>60){
		$ARRAY=$GLOBALS["CLASS_UNIX"]->ldap_GET_CONFS();
		@file_put_contents("/usr/share/artica-postfix/ressources/local_ldap.php", "<?php \$GLOBALS[\"MAIN_LOCAL_LDAP_SETTINGS\"]=\"".base64_encode(serialize($ARRAY))."\";?>");
		@chmod("/usr/share/artica-postfix/ressources/local_ldap.php",0755);
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		system_admin_events("Fatal: Local LDAP service is not running [action=restart]",__FUNCTION__,__FILE__,__LINE__,"system");
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} /etc/init.d/slapd restart --framework=". basename(__FILE__)." >/dev/null 2>&1 &");
		$l[]="";return implode("\n",$l);
	}


	if(!$GLOBALS["CLASS_UNIX"]->is_socket("/var/run/slapd/slapd.sock")){
		system_admin_events("Fatal: No such  Unix socket /var/run/slapd/slapd.sock for LDAP service [action=restart]",__FUNCTION__,__FILE__,__LINE__,"system");
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} /etc/init.d/slapd restart --framework=". basename(__FILE__)." >/dev/null 2>&1 &");
	}

	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.verifldap.php.verif_organization.time");
	if($time_file>240){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.verifldap.php --ou >/dev/null 2>&1 &");
	}

	if($RestartOpenLDAP==0){
		$RestartLDAPEach=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/RestartLDAPEach"));
		if(!is_numeric($RestartLDAPEach)){$RestartLDAPEach=4320;}
		if($RestartLDAPEach>0){
			$ProcessTime=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($master_pid);
			if($ProcessTime>$RestartLDAPEach){
				system_admin_events("Local LDAP service TTL {$ProcessTime}mn exceed {$RestartLDAPEach}mn [action=restart]",__FUNCTION__,__FILE__,__LINE__,"system");
				shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} /etc/init.d/slapd restart --framework=". basename(__FILE__)." >/dev/null 2>&1 &");
			}
		}
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================
function openldap_disable_cron(){
	if(!is_file("/etc/cron.d/openldap-restart")){return;}
	@unlink("/etc/cron.d/openldap-restart");
	shell_exec("/etc/init.d/cron reload");
}

function openldap_enable_cron(){
	if(is_file("/etc/cron.d/openldap-restart")){return;}
	Popuplate_cron_make("openldap-restart","30 6,12,3 * * *","exec.initslapd.php --restart --force --framework=cron-daemon");
	shell_exec("/etc/init.d/cron reload");
}

