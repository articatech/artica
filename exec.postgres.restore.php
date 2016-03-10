#!/usr/bin/php -q
<?php
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="InfluxDB Daemon";
$GLOBALS["PROGRESS"]=false;
$GLOBALS["MIGRATION"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;
$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--migration#",implode(" ",$argv),$re)){$GLOBALS["MIGRATION"]=true;}

$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.influx.inc');

$GLOBALS["ARGVS"]=implode(" ",$argv);
restore($argv[1]);


function restore($targetFilename){
	build_progress_idb("{restore_database}",20);
	$unix=new unix();
	
	if(is_file("/tmp/postgressql-restore.running")){
		$pid=$unix->PIDOF("/usr/local/ArticaStats/bin/psql");
		if($unix->process_exists($pid)){
			$timepid=$unix->PROCCESS_TIME_MIN($pid);
			echo "Already running $pid since {$timepid}mn\n";
			return;}
		@unlink("/tmp/postgressql-restore.running");
	}
	if(is_file("/tmp/postgressql-restore.running")){return;}
	
	
	if(!is_file($targetFilename)){
		echo "targetFilename: $targetFilename no such file\n";
		build_progress_idb("{restore_database} {failed}",110);
		return;
	}
	
	$basename=basename($targetFilename);
	if(!preg_match("#\.gz$#", $basename)){
		echo "targetFilename: $basename do in gzip format\n";
		build_progress_idb("{restore_database} {failed}",110);
		return;
	}
	
	$UnCompressFileName=$targetFilename.".sql";
	$su=$unix->find_program("su");
	$gunzip=$unix->find_program("gunzip");
	
	build_progress_idb("{restore_database} {uncompress}",50);
	if(!$unix->uncompress($targetFilename, $UnCompressFileName)){
		echo "Unable to uncompress $targetFilename\n";
		build_progress_idb("{restore_database} {failed}",110);
		return;
		
	}
	
	$psql="/usr/local/ArticaStats/bin/psql -f $UnCompressFileName  -h /var/run/ArticaStats -U ArticaStats -w proxydb";
	$f[]="#!/bin/sh";
	$f[]=". /lib/init/vars.sh";
	$f[]=". /lib/lsb/init-functions";
	$f[]="LANG=en_US.UTF-8";
	$f[]="HOME=/home/ArticaStats";
	$f[]="PATH=/usr/local/bin:/usr/bin:/bin:/usr/local/games:/usr/games";
	$f[]="rm /tmp/postgressql-restore.sh";
	$f[]="if [ -f \"/tmp/postgressql-restore.running\" ]; then";
	$f[]="exit";
	$f[]="fi";
	$f[]="touch /tmp/postgressql-restore.running\n";
	$cmdline="$psql";
	$f[]="$cmdline";
	$f[]="echo \"OK FINISH\"";
	$f[]="rm /tmp/postgressql-restore.running";
	$f[]="touch /tmp/postgressql-restore.OK\n";
	
	if(is_file("/tmp/postgressql-restore.OK")){@unlink("/tmp/postgressql-restore.OK");}
	@file_put_contents("/tmp/postgressql-restore.sh", @implode("\n", $f));
	@chmod("/tmp/postgressql-restore.sh",0755);
	echo $cmdline;
	build_progress_idb("{restore_database} {run}",50);

	$text="{please_wait}";
	$i=0;
	$prc=60;
	while (true) {
		$i++;
		if(is_file("/tmp/postgressql-restore.OK")){break;}
		build_progress_idb("{restore_database} $text ($i)",$prc);
		sleep(3);
		if(is_file("/tmp/postgressql-restore.running")){
			$pid=$unix->PIDOF("/usr/local/ArticaStats/bin/psql");
			$timepid=$unix->PROCCESS_TIME_MIN($pid);
			$size=$unix->DIRSIZE_BYTES_NOCACHE("/home/ArticaStatsDB");
			$array_load=sys_getloadavg();
			$internal_load=$array_load[0];
			$text="{running} load:{$internal_load} $pid {since} {$timepid}mn ".FormatBytes($size/1024);
			$prc=70;
		}
	}
	
	
	build_progress_idb("{restore_database} {done}",90);
	
	sleep(5);
	InfluxDbSize();
	build_progress_idb("{restore_database} {success}",100);
}




function InfluxDbSize(){
	$dir="/home/ArticaStatsDB";
	$unix=new unix();
	$size=$unix->DIRSIZE_KO($dir);
	$partition=$unix->DIRPART_INFO($dir);
	
	$TOT=$partition["TOT"];
	$percent=($size/$TOT)*100;
	$percent=round($percent,3);
	
	
	if($GLOBALS["VERBOSE"]){echo "$dir: $size Partition $TOT\n";}
	
	$ARRAY["PERCENTAGE"]=$percent;
	$ARRAY["SIZEKB"]=$size;
	$ARRAY["PART"]=$TOT;
	
	if($GLOBALS["VERBOSE"]){print_r($ARRAY);};
	@unlink("/usr/share/artica-postfix/ressources/logs/web/InfluxDB.state");
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/InfluxDB.state", serialize($ARRAY));
	
}
function build_progress_idb($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/postgres.restore.progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}	
?>