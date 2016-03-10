<?php
$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.urgency.disable.progress";
$GLOBALS["ARGVS"]=implode(" ",$argv);
$GLOBALS["META"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if(preg_match("#--meta#",implode(" ",$argv))){$GLOBALS["META"]=true;}
if(preg_match("#--progress-activedirectory=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["AD_PROGRESS"]=$re[1];}


include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');

if($argv[1]=="--ssl"){xstart_ssl();exit;}
if($argv[1]=="--ufdb-on"){ufdb_on();exit;}
if($argv[1]=="--ufdb-off"){ufdb_off();exit;}
if($argv[1]=="--ufdb-off"){ufdb_off();exit;}
if($argv[1]=="--ad-on"){ad_on();exit;}


xstart();



function build_progress($text,$pourc){
	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	sleep(1);

}

function ufdb_on(){
	$unix=new unix();
	build_progress("Stamp emerency to on",20);
	@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidUFDBUrgency", 1);
	@chmod("/etc/artica-postfix/settings/Daemons/SquidUFDBUrgency",0755);
	
	
	build_progress("{reconfiguring}",30);
	$php=$unix->LOCATE_PHP5_BIN();
	system("$php /usr/share/artica-postfix/exec.squid.php --build");
	if(is_file("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS")){@unlink("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS");}
	@unlink("/usr/share/artica-postfix/ressources/logs/web/ufdb.rules_toolbox_left.html");
	
	build_progress("{stopping} {webfiltering}",60);
	system("/etc/init.d/ufdb stop");
	
	
	build_progress("{done} {APP_SQUID}",100);
	if($GLOBALS["META"]){
		shell_exec("$php /usr/share/artica-postfix/exec.artica-meta-client.php --ping --force");
	}	
}
function ufdb_off(){
	$unix=new unix();
	$users=new usersMenus();
	build_progress("Stamp emerency to OFF",20);
	@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidUFDBUrgency", 0);
	@chmod("/etc/artica-postfix/settings/Daemons/SquidUFDBUrgency",0755);
	build_progress("{reconfiguring} {please_wait}",30);
	$php=$unix->LOCATE_PHP5_BIN();
	system("$php /usr/share/artica-postfix/exec.squid.php --build");
	if(is_file("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS")){@unlink("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS");}
	@unlink("/usr/share/artica-postfix/ressources/logs/web/ufdb.rules_toolbox_left.html");
	
	if($users->CORP_LICENSE){
		build_progress("{recompiling_personal_databases} {please_wait}",40);
		system("$php /usr/share/artica-postfix/exec.squidguard.php --compile-all-categories");
	}
	
	build_progress("{starting} {webfiltering}",60);
	system("/etc/init.d/ufdb start");
	
	build_progress("{build_status}",70);
	system("$php /usr/share/artica-postfix/exec.status.php --all-squid >/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS");
	
	build_progress("{done} {APP_SQUID}",100);
	if($GLOBALS["META"]){
		shell_exec("$php /usr/share/artica-postfix/exec.artica-meta-client.php --ping --force");
	}
}
function xstart_ssl(){
	$unix=new unix();
	build_progress("Stamp emergency to off",20);
	@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidSSLUrgency", 0);
	@chmod("/etc/artica-postfix/settings/Daemons/SquidUrgency",0755);
	build_progress("{reconfiguring}",30);
	$php=$unix->LOCATE_PHP5_BIN();
	system("$php /usr/share/artica-postfix/exec.squid.php --build");	
	if(is_file("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS")){@unlink("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS");}
	@unlink("/usr/share/artica-postfix/ressources/logs/web/ufdb.rules_toolbox_left.html");
	build_progress("{done} {APP_SQUID}",100);
	if($GLOBALS["META"]){
		shell_exec("$php /usr/share/artica-postfix/exec.artica-meta-client.php --ping --force");
	}
}

function ad_on(){
	$unix=new unix();
	build_progress("Stamp emergency to ON",20);
	@file_put_contents("/etc/artica-postfix/settings/Daemons/ActiveDirectoryEmergency", 1);
	build_progress("{reconfiguring}",30);
	$php=$unix->LOCATE_PHP5_BIN();
	system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
	if($GLOBALS["META"]){
		shell_exec("$php /usr/share/artica-postfix/exec.artica-meta-client.php --ping --force");
	}
	build_progress("{emergency} Active Directory {done}",100);
}


function xstart(){
	$unix=new unix();
	build_progress("Stamp emergencies to off",20);
	@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidUrgency", 0);
	@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidUFDBUrgency", 0);
	@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidSSLUrgency", 0);
	@file_put_contents("/etc/artica-postfix/settings/Daemons/LogsWarninStop", 0);
	@file_put_contents("/etc/artica-postfix/settings/Daemons/MacToUidUrgency", 0);
	@file_put_contents("/etc/artica-postfix/settings/Daemons/ActiveDirectoryEmergency", 0);
	@file_put_contents("/etc/artica-postfix/settings/Daemons/BasicAuthenticatorEmergency", 0);
	
	@chmod("/etc/artica-postfix/settings/Daemons/SquidUrgency",0755);
	build_progress("{reconfiguring}",30);
	$php=$unix->LOCATE_PHP5_BIN();
	system("$php /usr/share/artica-postfix/exec.squid.php --build --noreload");
	build_progress("{restarting} {APP_SQUID}",50);
	system("$php /usr/share/artica-postfix/exec.squid.watchdog.php --restart --force --urgency");
	
	build_progress("{starting} {webfiltering}",60);
	system("/etc/init.d/ufdb start");
	build_progress("{restarting} Status service",70);
	system("/etc/init.d/artica-status restart");
	if(is_file("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS")){@unlink("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS");}
	@unlink("/usr/share/artica-postfix/ressources/logs/web/ufdb.rules_toolbox_left.html");
	build_progress("{done} {APP_SQUID}",100);
	if($GLOBALS["META"]){
		shell_exec("$php /usr/share/artica-postfix/exec.artica-meta-client.php --ping --force");
	}
}