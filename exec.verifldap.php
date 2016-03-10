<?php
$GLOBALS["FORCE"]=false;$GLOBALS["REINSTALL"]=false;
$GLOBALS["NO_HTTPD_CONF"]=false;
$GLOBALS["NO_HTTPD_RELOAD"]=false;
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--reinstall#",implode(" ",$argv))){$GLOBALS["REINSTALL"]=true;}
	if(preg_match("#--no-httpd-conf#",implode(" ",$argv))){$GLOBALS["NO_HTTPD_CONF"]=true;}
	if(preg_match("#--noreload#",implode(" ",$argv))){$GLOBALS["NO_HTTPD_RELOAD"]=true;}
	if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["posix_getuid"]=0;
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');

if($argv[1]=="--ou"){verif_organization();exit;}



function verif_organization(){
	$unix=new unix();
	$EnableOpenLDAP=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableOpenLDAP"));
	if($EnableOpenLDAP==0){return;}
	if(!isset($GLOBALS["SQUID_INSTALLED"])){
		$squidbin=$unix->LOCATE_SQUID_BIN();
		if(is_file($squidbin)){$GLOBALS["SQUID_INSTALLED"]=true;}else{$GLOBALS["SQUID_INSTALLED"]=false;}
	}
	$timeStamp="/etc/artica-postfix/pids/exec.verifldap.php.verif_organization.time";
	
	if($GLOBALS["VERBOSE"]){ echo "$timeStamp\n";}
	$pidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidFile);
	if($unix->process_exists($pid,basename(__FILE__))){
		return;
	}
	
	
	
	@unlink($pidFile);
	@file_put_contents($pidFile, getmypid());
	
	if(!$GLOBALS["FORCE"]){
		$TimeEx=$unix->file_time_min($timeStamp);
		if($TimeEx<240){return;}
	}
	@unlink($timeStamp);
	@file_put_contents($timeStamp, time());
	$sock=new sockets();
	if($sock->EnableIntelCeleron==1){die();exit;}
	
	$WizardSavedSettings=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/WizardSavedSettings")));
	if(!isset($WizardSavedSettings["organization"])){return;}
	$organization=$WizardSavedSettings["organization"];
	if($organization==null){return;}
	
	$ldap=new clladp();
	if($GLOBALS["VERBOSE"]){ echo "Loading LDAP\n";}
	
	
	
	if($ldap->ldapFailed){
		if($GLOBALS["VERBOSE"]){echo "Unable to connect to the LDAP server $ldap->ldap_host!\n";}
		if($ldap->ldap_host=="127.0.0.1"){
			$unix->ToSyslog("LDAP error $ldap->ldap_last_error",false,basename(__FILE__));
			if($GLOBALS["SQUID_INSTALLED"]){squid_admin_mysql(1, "Connecting to local LDAP server failed [action=restart LDAP]", 
			"Error: $ldap->ldap_last_error\nLdap host:$ldap->ldap_host",__FILE__,__LINE__);}
			system_admin_events("Error, Connecting to local LDAP server failed [action=restart LDAP]",__FUNCTION__,__FILE__,__LINE__);
			shell_exec("/etc/init.d/slapd restart --framework=". basename(__FILE__));
			$ldap=new clladp();
			if($GLOBALS["VERBOSE"]){ echo "Loading LDAP\n";}
			if($ldap->ldapFailed){echo $unix->ToSyslog("Unable to connect to the LDAP server $ldap->ldap_host! -> Abort..",false,__FILE__);;return;}
		
		}else{
			
			return;
		}
		
	}
	
	$hash=$ldap->hash_get_ou(false);
	$CountDeOU=count($hash);
	if($GLOBALS["VERBOSE"]){ echo "$CountDeOU Organization(s)\n";}
	if(count($hash)>0){return;}
	

	system_admin_events("Error, no organization found, create the first one $organization",__FUNCTION__,__FILE__,__LINE__);
	if(!$ldap->AddOrganization($organization)){
		system_admin_events("Error, unable to create first organization $organization\n$ldap->ldap_last_error",__FUNCTION__,__FILE__,__LINE__);
		if($GLOBALS["SQUID_INSTALLED"]){squid_admin_mysql(0, "Error, unable to create first organization $organization", $ldap->ldap_last_error,__FILE__,__LINE__);}
	}

	
	
}