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
include_once(dirname(__FILE__).'/ressources/class.squid.bandwith.inc');

start();

function build_progress_bandwidth($text,$pourc){
	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/squid.bandwww.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);


}

function start($port_id){
	$unix=new unix();
	$squid=new squidbee();
	$q=new mysql_squid_builder();
	$INCLUDE=false;
	$GLOBALS["OUTPUT"]=true;
	
	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	build_progress_bandwidth("{limit_rate} {analyze}",20);
	while (list ($www, $line) = each ($f) ){
		if(!preg_match("#acls_bandwidth\.conf#", $line)){continue;}
		echo "Include OK\n";
		$INCLUDE=TRUE;
		break;
	}
	
	if(!$INCLUDE){
		echo "Include False, reconfigure\n";
		build_progress_bandwidth("{limit_rate} {reconfigure}",80);
		$php=$unix->LOCATE_PHP5_BIN();
		system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
		build_progress_bandwidth("{limit_rate} {done}",100);
		return;
	}
	
	$md51=md5_file("/etc/squid3/acls_bandwidth.conf");
	build_progress_bandwidth("{limit_rate} {reconfigure}",50);
	$band=new squid_bandwith_builder();
	if(!$band->compile()){
		build_progress_bandwidth("{limit_rate} {failed}",110);
		return;
	}
	
	$md52=md5_file("/etc/squid3/acls_bandwidth.conf");
	if($md51==$md52){
		build_progress_bandwidth("{limit_rate} {done} {unmodified}",100);
		return;
	}
	
	$squid_checks=new squid_checks();
	if(!$squid_checks->squid_parse()){
		build_progress_bandwidth("{limit_rate} {failed}",110);
		return;
	}
	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	build_progress_bandwidth("{limit_rate} {reloading}",97);
	system("$squidbin -k reconfigure");
	
	if(is_file("/root/squid-good.tgz")){@unlink("/root/squid-good.tgz");}
	chdir("/etc/squid3");
	shell_exec("cd /etc/squid3");
	shell_exec("tar -czf /root/squid-good.tgz *");
	chdir("/root");
	shell_exec("cd /root");
	
	
	
	build_progress_bandwidth("{limit_rate} {done} OK",100);
	// FATAL: No valid signing SSL certificate
	
	
}
