<?php
if(is_file("/usr/bin/cgclassify")){if(is_dir("/cgroups/blkio/php")){shell_exec("/usr/bin/cgclassify -g cpu,cpuset,blkio:php ".getmypid());}}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__) . '/ressources/class.templates.inc');
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.ccurl.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');

if(isset($argv[1])){
	if($argv[1]=="--dump"){xdump();exit;}
}


xstart();


function xstart(){

	$curl=new ccurl();
	$unix=new unix();

	$Pidfile="/etc/artica-postfix/pids/exec.abuse-ch.pid";
	$PidTime="/etc/artica-postfix/pids/exec.abuse-ch.time";

	$pid=$unix->get_pid_from_file($Pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		if($GLOBALS["VERBOSE"]){echo "Aborting Task already running pid $pid ".__FUNCTION__."()\n";}
		return;
	}

	@file_put_contents($Pidfile, getmypid());

	if(!$GLOBALS["VERBOSE"]){
		$time=$unix->file_time_min($PidTime);
		if($time<10){echo "Only each 10mn\n";die();}
		@unlink($PidTime);
		@file_put_contents($PidTime, time());
	}
	
	
	$curl=new ccurl("http://articatech.net/WebfilterDBS/ransomwaretracker.txt");
	$tmpfile=$unix->TEMP_DIR();
	if(!$curl->GetFile("$tmpfile/ransomwaretracker.txt")){
		squid_admin_mysql(0, "ransomwaretracker.txt unable to get index file", $curl->error,__FILE__,__LINE__);
		return;
	}
	
	$array=unserialize(@file_get_contents("$tmpfile/ransomwaretracker.txt"));
	$TIME=$array["TIME"];
	if(!isset($array["MD5"])){
		squid_admin_mysql(0, "ransomwaretracker.txt corrupted file", $curl->error,__FILE__,__LINE__);
		return;
	}
	@unlink("$tmpfile/ransomwaretracker.txt");
	$CurrentMD5=@file_get_contents("/etc/artica-postfix/settings/Daemons/ransomwaretrackerMD5");
	if($CurrentMD5==$array["MD5"]){
		return;
	}
	
	$curl=new ccurl("http://articatech.net/WebfilterDBS/ransomwaretracker.gz");
	if(!$curl->GetFile("$tmpfile/ransomwaretracker.gz")){
		squid_admin_mysql(0, "ransomwaretracker.gz unable to get pattern file", $curl->error,__FILE__,__LINE__);
		return;
	}

	if(!$unix->uncompress("$tmpfile/ransomwaretracker.gz", "$tmpfile/ransomwaretracker.db")){
		squid_admin_mysql(0, "ransomwaretracker.gz unable to extract file", $curl->error,__FILE__,__LINE__);
		return;
		
	}
	
	
	
	$ARRAY=unserialize(@file_get_contents("$tmpfile/ransomwaretracker.db"));
	if(!isset($ARRAY["URIS"])){
		squid_admin_mysql(0, "ransomwaretracker.db corrupted database", $curl->error,__FILE__,__LINE__);
		return;
		
	}
	
	if(is_file("/etc/squid3/ransomwaretracker.db")){@unlink("/etc/squid3/ransomwaretracker.db");}
	@copy("$tmpfile/ransomwaretracker.db", "/etc/squid3/ransomwaretracker.db");
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/RansomwareReloaded")){
		squid_admin_mysql(1, "Reloading Proxy service for updating Ranswomware function", null,__FILE__,__LINE__);
		$squid=$unix->LOCATE_SQUID_BIN();
		shell_exec("$squid -f /etc/squid3/squid.conf -k reconfigure");
		@touch("/etc/artica-postfix/settings/Daemons/RansomwareReloaded");
	}
	
	
	squid_admin_mysql(2, "Success updating ranswomware database v{$TIME}", null,__FILE__,__LINE__);
	
	

}

function xdump(){
	$ARRAY=unserialize(@file_get_contents("/etc/squid3/ransomwaretracker.db"));
	print_r($ARRAY);
	
}




