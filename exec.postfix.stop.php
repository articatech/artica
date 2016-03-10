<?php
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.maincf.multi.inc');
include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
include_once(dirname(__FILE__) . '/ressources/class.main.hashtables.inc');
include_once(dirname(__FILE__) . '/ressources/class.postfix.externaldbs.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--pourc=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["POURC_START"]=$re[1];}

if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

start();

function build_progress($text,$pourc){
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/postfix.stop.progress";
	echo "{$pourc}% $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}

function start(){
	
	
	$EnableStopPostfix=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableStopPostfix"));
	
	
	if($EnableStopPostfix==1){
		build_progress("{stop_messaging} {restarting_watchdog}",15);
		system("/etc/init.d/artica-status restart --force");
		build_progress("{stop_messaging}",20);
		system("/etc/init.d/postfix stop");
		build_progress("{stop_messaging}",30);
		system("/etc/init.d/amavis stop");
		build_progress("{stop_messaging}",40);
		system("/etc/init.d/milter-greylist stop");
		build_progress("{stop_messaging} {success}",100);
		return;
	}
	build_progress("{start_messaging} {restarting_watchdog}",15);
	system("/etc/init.d/artica-status restart --force");
	build_progress("{start_messaging}",20);
	system("/etc/init.d/postfix start");
	build_progress("{start_messaging}",30);
	system("/etc/init.d/amavis start");
	build_progress("{start_messaging}",40);
	system("/etc/init.d/milter-greylist start");
	build_progress("{start_messaging} {success}",100);
	
	
	
}