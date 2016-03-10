<?php
$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
if($EnableIntelCeleron==1){die("EnableIntelCeleron==1\n");}
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.influx.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_INFLUX"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

start_hour();

function start_hour(){
	
	
	$TimeFile="/etc/artica-postfix/pids/". basename(__FILE__).".time";
	$pidfile="/etc/artica-postfix/pids/". basename(__FILE__).".pid";
	$unix=new unix();
	$sock=new sockets();
	
	if(system_is_overloaded(basename(__FILE__))){return;}
	
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["VERBOSE"]){echo "$pid already executed since {$timepid}Mn\n";}
		if(!$GLOBALS["FORCE"]){
			if($timepid<14){return;}
			$kill=$unix->find_program("kill");
			unix_system_kill_force($pid);
		}
	}
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	
	
	$hostname=$unix->hostname_g();
	
	$today=date("Y-m-d")." 00:00:00";
	$postgres=new postgres_sql();
	$results=$postgres->QUERY_SQL("select date_trunc('H',zdate) + (round(extract('minute' from zdate)/15)*15) * '1 minute'::interval as time, sum(rx) as rx, sum(tx) as tx,eth from access_log WHERE proxyname='$hostname' AND zdate >'$today' group by time,eth");
	$c=0;
	$f=array();
	while($ligne=@pg_fetch_assoc($results)){
		$time=$ligne["time"];
		$ETH=$ligne["eth"];
		if($ETH=="lo"){continue;}
		$RX=$ligne["rx"];
		$TX=$ligne["tx"];
		$f[]="('$time','$ETH','$RX','$TX')";
		
		
	}
	
	if(count($f)==0){return;}
	
	$q=new mysql();
	if($q->TABLE_EXISTS("RXTX_HOUR", "artica_events")){
		$q->QUERY_SQL("TRUNCATE TABLE `RXTX_HOUR`","artica_events");
	}
	
	$sql="CREATE TABLE IF NOT EXISTS `RXTX_HOUR`
	(`ZDATE` DATETIME,
	`RX` INT UNSIGNED NOT NULL DEFAULT 1,
	`TX` INT UNSIGNED NOT NULL DEFAULT 1,
	`ETH` VARCHAR(60),
	KEY `ZDATE`(`ZDATE`),
	KEY `RX`(`RX`),
	KEY `TX`(`TX`),
	KEY `ETH`(`ETH`) )  ENGINE = MYISAM;";
	
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){return;}
	$q->QUERY_SQL("INSERT IGNORE INTO RXTX_HOUR (ZDATE,ETH,RX,TX) VALUES ".@implode(",", $f),"artica_events");
	start_week();
		
}


function start_week(){
	$unix=new unix();
	$hostname=$unix->hostname_g();
	$now=InfluxQueryFromUTC(strtotime("-7 day"));	
	$today=date("Y-m-d",$now)." 00:00:00";
	$sql="SELECT SUM(TX) as TX, SUM(RX) as RX,ETH FROM ethrxtx WHERE proxyname='$hostname' AND time >'$today' group by time(4h),ETH";

	$influx=new influx();
	echo "$sql\n";
	$main=$influx->QUERY_SQL($sql);
	$c=0;
	$f=array();
	foreach ($main as $row) {
	$time=date("Y-m-d H:i:s",InfluxToTime($row->time));
	$ETH=$row->ETH;
	if($ETH=="lo"){continue;}
	$RX=$row->RX;
	$TX=$row->TX;
	$f[]="('$time','$ETH','$RX','$TX')";
	
	
	}
	
	if(count($f)==0){return;}
	
	$q=new mysql();
	if($q->TABLE_EXISTS("RXTX_WEEK", "artica_events")){
		$q->QUERY_SQL("TRUNCATE TABLE `RXTX_WEEK`","artica_events");
	}
	
	$sql="CREATE TABLE IF NOT EXISTS `RXTX_WEEK`
	(`ZDATE` DATETIME,
	`RX` INT UNSIGNED NOT NULL DEFAULT 1,
	`TX` INT UNSIGNED NOT NULL DEFAULT 1,
	`ETH` VARCHAR(60),
	KEY `ZDATE`(`ZDATE`),
	KEY `RX`(`RX`),
	KEY `TX`(`TX`),
	KEY `ETH`(`ETH`) )  ENGINE = MYISAM;";
	
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){return;}
	$q->QUERY_SQL("INSERT IGNORE INTO RXTX_WEEK (ZDATE,ETH,RX,TX) VALUES ".@implode(",", $f),"artica_events");
	

}
