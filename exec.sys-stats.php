<?php
$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
if($EnableIntelCeleron==1){die("EnableIntelCeleron==1\n");}
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.influx.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_INFLUX"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if($argv[1]=="rxtx"){RXTX();exit;}

xtsart();


function xtsart(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".time";
	if($GLOBALS["VERBOSE"]){echo "TimeFile:$pidTime\n";}
	$unix=new unix();
	if(!$GLOBALS["VERBOSE"]){
		if($unix->file_time_min($pidTime)<10){die();}
		if($unix->process_exists(@file_get_contents($pidfile,basename(__FILE__)))){if($GLOBALS["VERBOSE"]){echo " --> Already executed.. ". @file_get_contents($pidfile). " aborting the process\n";}writelogs(basename(__FILE__).":Already executed.. aborting the process",basename(__FILE__),__FILE__,__LINE__);die();}
		@file_put_contents($pidfile, getmypid());
		@unlink($pidTime);
		@file_put_contents($pidTime, time());
	}
	
	
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];
	$time=time();
	$BASEDIR="/usr/share/artica-postfix";
	$hash_mem=array();
	@chmod("/usr/share/artica-postfix/ressources/mem.pl",0755);
	$datas=shell_exec(dirname(__FILE__)."/ressources/mem.pl");
	if(preg_match('#T=([0-9]+) U=([0-9]+)#',$datas,$re)){$ram_used=$re[2];}
	

	$cpuUsage=null;
	$ps=$unix->find_program("ps");
	exec("$ps -aux 2>&1", $processes);
	foreach($processes as $process){
		$cols = explode(' ', preg_replace('# +#', ' ', $process));
		if (strpos($cols[2], '.') > -1){
			$cpuUsage += floatval($cols[2]);
		}
	}
	
	if($GLOBALS["VERBOSE"]){echo "CPU: $cpuUsage, LOAD: $internal_load, MEM: $ram_used\n";}
	
	$q=new postgres_sql();
	$q->CREATE_TABLES();
	
	$proxyname=$unix->hostname_g();
	$time=date("Y-m-d H:i:s");
	$sql="INSERT INTO system (zdate,proxyname,load_avg,mem_stats,cpu_stats)
	VALUES('$time','$proxyname','$internal_load','".intval($ram_used)."','$cpuUsage')";
	
	if($GLOBALS["VERBOSE"]){echo $sql."\n";}
	$q->QUERY_SQL($sql);

	if(!$q->ok){echo $q->mysql_error."\n";}
	
	RXTX();
	
	if(system_is_overloaded(basename(__FILE__))){
		$date=time();
		@mkdir("/var/log/artica-postfix/sys_alerts",0755,true);
		if(!is_file("/var/log/artica-postfix/sys_alerts/$date")){
			$ps=$unix->find_program("ps");
			$nohup=$unix->find_program($nohup);
			$nice=$unix->EXEC_NICE();
			$load=$GLOBALS["SYSTEM_INTERNAL_LOAD"];
			if(!$unix->process_exists($unix->PIDOF_PATTERN("$ps"))){
				$cmd=trim("$nohup $nice $ps auxww >/var/log/artica-postfix/sys_alerts/$date-$load 2>&1");
				shell_exec($cmd);
			}
		}
	}

}

function RXTX(){
	$unix=new unix();
	$Cache=unserialize(@file_get_contents("/etc/artica-postfix/RXTX.array"));
	$ifconfig=$unix->find_program("ifconfig");
	
	exec("$ifconfig -a 2>&1",$results);
	foreach($results as $line){
		$line=trim($line);
		if($line==null){continue;}
		if(preg_match("#^([a-z0-9]+)\s+Link#", $line,$re)){
			$Interface=$re[1];
			continue;
		}
		
		if(preg_match("#RX bytes:([0-9]+).*?TX bytes:([0-9]+)#", $line,$re)){
			$ARRAY[$Interface]["RX"]=$re[1];
			$ARRAY[$Interface]["TX"]=$re[2];
			continue;
		}

		
		
	}
	
	$q=new influx();
	$q=new postgres_sql();
	$date=date("Y-m-d H:i:s");
	$hostname=$unix->hostname_g();
	while (list ($Interface, $array) = each ($ARRAY) ){
		$RX=$array["RX"];
		$TX=$array["TX"];
		
		$OLD_RX=intval($Cache[$Interface]["RX"]);
		$OLD_TX=intval($Cache[$Interface]["TX"]);
		if($OLD_RX>$RX){continue;}
		if($OLD_TX>$TX){continue;}
		
		$RX_NEW=$RX-$OLD_RX;
		$TX_NEW=$TX-$OLD_TX;
		
		if($GLOBALS["VERBOSE"]){
			echo "$Interface Rec:".xFormatBytes($RX_NEW/1024)." Trans:".xFormatBytes($TX_NEW/1024)."\n";
			
		}
		
		
		
		$q->QUERY_SQL("INSERT INTO ethrxtx (zdate,tx,rx,eth,proxyname) VALUES ('$date','$TX_NEW','$RX_NEW','$Interface','$hostname')");
	}
	
	@file_put_contents("/etc/artica-postfix/RXTX.array", serialize($ARRAY));
}
function xFormatBytes($kbytes,$nohtml=false){

	$spacer=" ";
	if($nohtml){$spacer=" ";}

	if($kbytes>1048576){
		$value=round($kbytes/1048576, 2);
		if($value>1000){
			$value=round($value/1000, 2);
			return "$value{$spacer}TB";
		}
		return "$value{$spacer}GB";
	}
	elseif ($kbytes>=1024){
		$value=round($kbytes/1024, 2);
		return "$value{$spacer}MB";
	}
	else{
		$value=round($kbytes, 2);
		return "$value{$spacer}KB";
	}
}
