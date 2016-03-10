<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["SCHEDULE_ID"]=0;if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.samba.inc');
include_once(dirname(__FILE__).'/ressources/class.autofs.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.backup.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.freeweb.inc');
include_once(dirname(__FILE__).'/ressources/class.user.inc');
include_once(dirname(__FILE__).'/ressources/class.mount.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");



start();

function build_progress($text,$pourc){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);

}


function start(){
	$unix=new unix();
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/account.progress";
	$users=new usersMenus();
	$sock=new sockets();
	
	$DATA=base64_decode(@file_get_contents("/usr/share/artica-postfix/ressources/conf/upload/ChangeLDPSSET"));
	$POSTED=unserialize($DATA);
	
	$username=trim($POSTED["change_admin"]);
	$password=trim($POSTED["change_password"]);
	$EnableOpenLDAP=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableOpenLDAP"));
	
	
	
	$ldap_server=$POSTED["ldap_server"];
	$ldap_port=$POSTED["ldap_port"];
	$suffix=$POSTED["suffix"];
	if($ldap_server==null){$ldap_server="127.0.0.1";}
	if($ldap_port==null){$ldap_port="389";}
	if($suffix==null){$suffix="dc=nodomain";}
	$change_ldap_server_settings=$POSTED["change_ldap_server_settings"];
	if($change_ldap_server_settings<>'yes'){$change_ldap_server_settings="no";}
	

	
	echo "Posted...........: ".strlen($DATA)." bytes\n";
	echo "Username.........: $username\n";
	echo "Password.........: $password\n";
	echo "LDAP Server......: $ldap_server\n";
	echo "LDAP Port........: $ldap_port\n";
	echo "Suffix...........: $suffix\n";
	
	if(!is_array($POSTED)){
		build_progress("Nothing as been posted",110);
		return;
		}

	if($username==null){
		build_progress("Username is null",110);
		return;
	}
	
	if($password==null){
		build_progress("Password is null",110);
		return;
	}
	
	build_progress("{checking}",20);

	
	$php=$unix->LOCATE_PHP5_BIN();
	
	$md5=md5($username.$password);
	$ldap=new clladp();
	$md52=md5(trim($ldap->ldap_admin).trim($ldap->ldap_password));
	build_progress("Change credentials",30);
	
	$BASCONF="/etc/artica-postfix/ldap_settings";
	
	file_put_contents("$BASCONF/admin", $username);
	file_put_contents("$BASCONF/password", $password);
	file_put_contents("$BASCONF/port", $ldap_port);
	file_put_contents("$BASCONF/server", $ldap_server);
	file_put_contents("$BASCONF/suffix", $suffix);
	
	@unlink("/etc/artica-postfix/no-ldap-change");
	@chmod("/usr/share/artica-postfix/bin/artica-install", 0755);
	@chmod("/usr/share/artica-postfix/bin/process1", 0755);
	
	build_progress("{reconfigure} OpenLDAP",35);

	system("/usr/share/artica-postfix/bin/artica-install --slapdconf");
	build_progress("Refresh global settings",40);
	system('/usr/share/artica-postfix/bin/process1 --checkout --force --verbose '. time());
	
	if($EnableOpenLDAP==1){
		build_progress("Restarting LDAP server",45);
		shell_exec("/etc/init.d/slapd restart --framework=". basename(__FILE__));
	}
	build_progress("Update others services",50);
	
	system("$php /usr/share/artica-postfix/exec.change.password.php");
	
	build_progress("{checking}",60);
	sleep(3);
	$users=new usersMenus();
	$md53=md5(trim($ldap->ldap_admin).trim($ldap->ldap_password));
	if($md53<>$md52){
		build_progress("{failed}",100);
		return;
	}
	
	build_progress("{success}",100);
	
	
}
