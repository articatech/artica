<?php
if(is_file("/usr/bin/cgclassify")){if(is_dir("/cgroups/blkio/php")){shell_exec("/usr/bin/cgclassify -g cpu,cpuset,blkio:php ".getmypid());}}
$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
if($EnableIntelCeleron==1){die("EnableIntelCeleron==1\n");}
$GLOBALS["BYPASS"]=true;
$GLOBALS["DEBUG_INFLUX_VERBOSE"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["DEBUG_MEM"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["NODHCP"]=true;
$GLOBALS["PROGRESS"]=false;
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
	if(preg_match("#--progress#",implode(" ",$argv))){$GLOBALS["PROGRESS"]=true;}
}
ini_set('display_errors', 1);
ini_set('html_errors',0);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);


if($GLOBALS["VERBOSE"]){"echo Loading...\n";}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.artica-meta.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.stats-appliance.inc');
include_once(dirname(__FILE__).'/ressources/class.parse.berekley.inc');
include_once(dirname(__FILE__)."/ressources/class.realtime-buildsql.inc");
include_once(dirname(__FILE__)."/ressources/class.influx.inc");
$date=date("YW");


$sock=new sockets();
$unix=new unix();
$squidbin=$unix->LOCATE_SQUID_BIN();
if(!is_file($squidbin)){die();}
$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
if($SQUIDEnable==0){die();}



$cache_manager=new cache_manager();
$data=$cache_manager->makeQuery("5min",true);
$hostname=$unix->hostname_g();

foreach ($data as $ligne){
	
	if(preg_match("#server\.http\.kbytes_in.*?([0-9\.]+)#", $ligne,$re)){
		$kbytes_in=$re[1];
		continue;
	}
	if(preg_match("#client_http\.kbytes_out.*?([0-9\.]+)#", $ligne,$re)){
		$kbytes_out=$re[1];
		continue;
	}
	
	
	
	if(preg_match("#client_http\.requests.*?([0-9\.]+)#", $ligne,$re)){
		$client_http_req=$re[1];
		continue;
	}
		
}


echo "Server download $kbytes_in/sec and sent to client $kbytes_out/sec $client_http_req/reqs\n";

		$q=new influx();
		$array["fields"]["REQS"]=$client_http_req;
		$array["fields"]["KBIN"]=$kbytes_in;
		$array["fields"]["KBOUT"]=$kbytes_out;
		$array["tags"]["proxyname"]=$hostname;
		$q->insert("httpreqs", $array);
		




