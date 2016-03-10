<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="Bandwidthd Daemon";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.influx.inc');

if($argv[1]=="--dates"){checkdates($argv[2]);die();}
if($argv[1]=="--frontend"){frontend();die();}
if($argv[1]=="--rotate"){rotate();die();}
if($argv[1]=="--compress"){compress_tables();exit;}
if($argv[1]=="--clean-table"){CleanTable($argv[2]);exit;}

scan();


function TimeToInflux($time){
	$time=QueryToUTC($time);

	$microtime=microtime();
	preg_match("#^[0-9]+\.([0-9]+)\s+#", $microtime,$re);
	$ms=intval($re[1]);
	return date("Y-m-d",$time)."T".date("H:i:s",$time).".{$ms}Z";

}

function checkdates($workfile){
	$handle = @fopen($workfile, "r");
	if (!$handle) {events("Fopen failed on $workfile");return false;}
	while (!feof($handle)){
		$buffer =trim(fgets($handle));
		$t=explode(",",$buffer);
	
		if(intval($t[1])<100){continue;}
	
		$IPAddr=$t[0];
		$date=date("Y-m-d H:i:s",$t[1]);
		echo "$IPAddr : $date\n";
	}
	
}

function scan(){
	
	$unix=new unix();
	$pidTime="/etc/artica-postfix/pids/exec.bandwidthd.scan.php.scan.time";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		return;
	}
	@file_put_contents($pidfile, getmypid());
	$time=$unix->file_time_min($pidTime);
	if($time<5){return;}
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	if(system_is_overloaded(basename(__FILE__))){return;}
	
	
	//IP Address,Timestamp,Total Sent,
	//Icmp Sent,Udp Sent,Tcp Sent,Ftp Sent,Http Sent, P2P Sent,Total Received,Icmp Received,
	//Udp Received,Tcp Received,Ftp Received,Http Received, P2P Received 
	
	$unix=new unix();
	$hostname=$unix->hostname_g();
	
	$f=explode("\n",@file_get_contents("/usr/bandwidthd/log.1.0.cdf"));
	$workfile="/usr/bandwidthd/log.1.0.cdf";
	
	$handle = @fopen($workfile, "r");
	if (!$handle) {return false;}
	
	
	
	while (!feof($handle)){
		$buffer =trim(fgets($handle));
		$t=explode(",",$buffer);
		echo count($t)."\n";
		if(intval($t[1])<100){continue;}
		
		$IPAddr=$t[0];
		$date=date("Y-m-d H:i:00",$t[1]);
		$InfluxTime=TimeToInflux(strtotime($date));
		
		$TotalSent=$t[2];
		$ICMPSent=$t[3];
		$UDPSent=$t[4];
		$TCPSent=$t[5];
		$FTPSent=$t[6];
		$HTTPSent=$t[7];
		$P2PSent=$t[8];
		
		$Received=$t[9];
		$ICMPReceived=$t[10];
		$UDPReceived=$t[11];
		$TCPReceived=$t[12];
		$FTPReceived=$t[13];
		$HTTReceived=$t[14];
		$P2PReceived=$t[15];
		
		$key=md5("$IPAddr$date");
		
		if(!isset($ARRAY[$key])){
			$ARRAY[$key]["PROXYNAME"]=$hostname;
			$ARRAY[$key]["IPADDR"]=$IPAddr;
			$ARRAY[$key]["TIME"]=$InfluxTime;
			$ARRAY[$key]["TIMSQL"]=$date;
			$ARRAY[$key]["TotalSent"]=$TotalSent;
			$ARRAY[$key]["Received"]=$Received;
			
			
			
		}else{
			$ARRAY[$key]["IPAddr"]=$ARRAY[$key]["IPAddr"]+$IPAddr;
			$ARRAY[$key]["TotalSent"]=$ARRAY[$key]["TotalSent"]+$TotalSent;
			$ARRAY[$key]["Received"]=$ARRAY[$key]["Received"]+$Received;
		}
		
	}
	
	
	while (list ($KEYMD5, $subarray) = each ($ARRAY)){
		$FINAL_SQL[]="('{$subarray["TIMSQL"]}','{$subarray["IPADDR"]}','{$subarray["TotalSent"]}','{$subarray["Received"]}')";
			
	}
	
	if(count($FINAL_SQL)==0){return;}
	
	$q=new mysql_squid_builder();
	$table_name="bandwidthd_".strtotime(date("Y-m-d 00:00:00"));
	if(!$q->TABLE_EXISTS($table_name)){
	$sql="CREATE TABLE  IF NOT EXISTS `$table_name` (
	`zDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
	`ipaddr` VARCHAR( 60 ) NOT NULL ,
	`TotalS` INT UNSIGNED ,
	`TotalR` INT UNSIGNED ,
	 INDEX ( `zDate` , `TotalS` , `TotalR`),
	KEY `ipaddr` (`ipaddr`))
	";
	$q->QUERY_SQL($sql);
	}else{
		$q->QUERY_SQL("TRUNCATE TABLE $table_name");
	}
	
	$q->QUERY_SQL("INSERT INTO $table_name (zDate,ipaddr,TotalS,TotalR) VALUES ".@implode(",", $FINAL_SQL));
	frontend(true);
	compress_tables();
}


function frontend($nopid=false){
	$unix=new unix();
	$pidTime="/etc/artica-postfix/pids/exec.bandwidthd.scan.php.frontend.time";
	
	if(!$nopid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$time=$unix->file_time_min($pidTime);
	if($time<15){return;}
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	if(system_is_overloaded(basename(__FILE__))){return;}
	$table_name="bandwidthd_".strtotime(date("Y-m-d 00:00:00"));
	$sql="SELECT ipaddr,SUM(TotalS) AS TotalS, SUM(TotalR) AS TotalR FROM $table_name GROUP BY ipaddr";
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL($sql);
	$f=array();
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$f[]="('{$ligne["ipaddr"]}','{$ligne["TotalS"]}','{$ligne["TotalR"]}')";
		
	}
	
	if(count($f)==0){return;}
	$q=new mysql_squid_builder();
	$table_name="bandwidthd_today";
	if(!$q->TABLE_EXISTS($table_name)){
		$sql="CREATE TABLE  IF NOT EXISTS `$table_name` (
		`ipaddr` VARCHAR( 60 ) NOT NULL ,
		`TotalS` INT UNSIGNED ,
		`TotalR` INT UNSIGNED ,
		INDEX ( `TotalS` , `TotalR`),
		KEY `ipaddr` (`ipaddr`))
		";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;}
	}else{
		$q->QUERY_SQL("TRUNCATE TABLE $table_name");
	}	
	
	$q->QUERY_SQL("INSERT INTO $table_name (ipaddr,TotalS,TotalR) VALUES ".@implode(",", $f));
	if(!$q->ok){echo $q->mysql_error;}
}

function LIST_TABLES_DAYS(){
	$q=new mysql_squid_builder();
	$array=array();
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' 
			AND table_name LIKE 'bandwidthd_%' ORDER BY table_name";
	$results=$q->QUERY_SQL($sql);
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(preg_match("#bandwidthd_[0-9]+#", $ligne["c"])){
			$array[$ligne["c"]]=$ligne["c"];
			
		}
	}
	
	return $array;
}


function compress_tables(){
	$CurrentTable="bandwidthd_".strtotime(date("Y-m-d 00:00:00"));
	$q=new mysql_squid_builder();
	$LIST_TABLES_DAYS=LIST_TABLES_DAYS();
	
	
	
	while (list ($tablename, $fileSource) = each ($LIST_TABLES_DAYS) ){
		if($tablename==$CurrentTable){continue;}
		CleanTable($tablename);
	}
	
	
}

function CleanTable($tablename){
	$q=new mysql_squid_builder();
	
	
	if(!$q->TABLE_EXISTS("bandwidthd_days")){
		$sql="CREATE TABLE  IF NOT EXISTS `bandwidthd_days` (
		`zDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
		`ipaddr` VARCHAR( 60 ) NOT NULL ,
		`TotalS` INT UNSIGNED ,
		`TotalR` INT UNSIGNED ,
		INDEX ( `zDate` , `TotalS` , `TotalR`),
		KEY `ipaddr` (`ipaddr`))
		";
		$q->QUERY_SQL($sql);
		
	}
	$prefix="INSERT IGNORE INTO bandwidthd_days (zDate,ipaddr,TotalS,TotalR) VALUES ";
	$sql="SELECT DATE_FORMAT(zDate,'%Y-%m-%d') as zDate,ipaddr,SUM(TotalS) AS TotalS,SUM(TotalR) AS TotalR FROM `$tablename` GROUP BY DATE_FORMAT(zDate,'%Y-%m-%d'),ipaddr";
	$results=$q->QUERY_SQL($sql);
	
	$f=array();
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$f[]="('{$ligne["zDate"]}','{$ligne["ipaddr"]}','{$ligne["TotalS"]}','{$ligne["TotalR"]}')";
	}
	
	if($tablename=="bandwidthd_days"){
		$q->QUERY_SQL("TRUNCATE TABLE `bandwidthd_days`");
	}
	
	if(count($f)>0){
		$q->QUERY_SQL($prefix.@implode(",", $f));
		if(!$q->ok){return false;}
		
	}
	if($tablename<>"bandwidthd_days"){
		$q->QUERY_SQL("DROP TABLE `$tablename`");
	}
	
}

function rotate(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		system_admin_mysql(1, "Bandwidthd rotation already executed since {$time}mn", null,__FILE__,__LINE__);
		return;
	}
	
	
	@file_put_contents($pidfile, getmypid());
	
	$sock=new sockets();
	$mv=$unix->find_program("mv");
	
	$BackupMaxDaysDir=$sock->GET_INFO("BackupMaxDaysDir");
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
	
	
	$f[]="/usr/bandwidthd/log.1.0.cdf";  
	$f[]="/usr/bandwidthd/log.1.1.cdf";  
	$f[]="/usr/bandwidthd/log.1.2.cdf"; 	
	$f[]="/usr/bandwidthd/log.1.3.cdf";   
	$f[]="/usr/bandwidthd/log.2.0.cdf";   
	$f[]="/usr/bandwidthd/log.2.1.cdf";   
	$f[]="/usr/bandwidthd/log.3.0.cdf"; 
	$DESTS=array();
	
	while (list ($index, $fileSource) = each ($f) ){
		$destination="$fileSource.".time()."log";
		$DESTS[]=$destination;
		shell_exec("$mv $fileSource $destination");
	}
	
	system_admin_mysql(2, "Restart bandwidthd in order to rotate events", null,__FILE__,__LINE__);
	shell_exec("/etc/init.d/bandwidthd restart");
	reset($f);
	while (list ($index, $fileSource) = each ($DESTS) ){
		$basename=basename($fileSource);
		$rotated_source=$fileSource;
		if(!$unix->compress($rotated_source, "$BackupMaxDaysDir/$basename.gz")){continue;}
	}
	
	
}
