<?php
$GLOBALS["SCHEDULE_ID"]=0;
$GLOBALS["AD_PROGRESS"]=0;
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["ARGVS"]=implode(" ",$argv);
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if(preg_match("#--progress-activedirectory=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["AD_PROGRESS"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}


if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.templates.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.remote-stats-appliance.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.ini.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.squid.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::framework/class.unix.inc\n";}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::frame.class.inc\n";}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.acls.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.ecap.inc');
include_once(dirname(__FILE__).'/ressources/class.icap.inc');
start();

function build_progress_ecap($text,$pourc){
	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/squid.ecap.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}

function start(){
	$unix=new unix();
	$squid=new squidbee();
	$q=new mysql_squid_builder();
	$INCLUDE=false;
	
	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	build_progress_ecap("{ecap_plugins} {analyze}",20);
	while (list ($www, $line) = each ($f) ){
		if(!preg_match("#acls_ecap\.conf#", $line)){continue;}
		echo "Include OK\n";
		$INCLUDE=TRUE;
		break;
	}
	
	if(!$INCLUDE){
		echo "Include False, reconfigure\n";
		build_progress_ecap("{ecap_plugins} {reconfigure}",80);
		$php=$unix->LOCATE_PHP5_BIN();
		system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
		build_progress_ecap("{ecap_plugins} {done}",100);
		return;
	}
	
	$md51=md5_file("/etc/squid3/acls_ecap.conf");
	build_progress_ecap("{ecap_plugins} {reconfigure}",50);
	$band=new squid_ecap_builder();
	if(!$band->compile()){
		build_progress_ecap("{ecap_plugins} {failed}",110);
		return;
	}
	
	build_progress_ecap("{ecap_plugins} {reconfigure} ICAP services",80);
	$icap=new icap();
	$icap->build_services();
	build_progress_ecap("{ecap_plugins} ICAP services {done}",85);
	
	$md52=md5_file("/etc/squid3/acls_ecap.conf");
	if($md51==$md52){
		build_progress_ecap("{ecap_plugins} {done} {unmodified}",100);
		return;
	}
	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	build_progress_ecap("{ecap_plugins} {reloading}",97);
	
	$squid_checks=new squid_checks();
	if(!$squid_checks->squid_parse()){
		build_progress_ecap("{ecap_plugins} {failed}",110);
		return;
	}
	
	system("$squidbin -k reconfigure");
	
	build_progress_ecap("{ecap_plugins} {done} OK",100);
	// FATAL: No valid signing SSL certificate
	
	
}
