<?php

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["FORCE"]=true;$GLOBALS["OUTPUT"]=true;
$GLOBALS["debug"]=true;
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
}
$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
if($EnableIntelCeleron==1){die();}

include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
include_once(dirname(__FILE__)."/ressources/class.resolv.conf.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.booster.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.watchdog.inc");
include_once(dirname(__FILE__)."/ressources/class.influx.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/externals/Net_DNS2/DNS2.php");
include_once(dirname(__FILE__)."/ressources/class.resolv.conf.inc");

if(system_is_overloaded()){die();}


if($argv[1]=="--stats"){GenerateGraph();exit;}

CHECK_DNS_SYSTEMS();

function CHECK_DNS_SYSTEMS(){

	
	
	$unix=new unix();
	include_once(dirname(__FILE__)."/ressources/class.influx.inc");

	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$BigTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".week.time";

	
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableDNSPerfs")){@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableDNSPerfs", 1);}
	$EnableDNSPerfs=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableDNSPerfs"));
	if($EnableDNSPerfs==0){
		echo "EnableDNSPerfs -> Disabled\n";
		die();}

	$unix=new unix();
	$GLOBALS["MYHOSTNAME"]=$unix->hostname_g();
	$pid=$unix->get_pid_from_file($pidFile);
	if($unix->process_exists($pid)){
		if($unix->PROCCESS_TIME_MIN($pid,10)<2){return;}
	}

	if($GLOBALS["VERBOSE"]){echo "pidtime =$pidtime\n";}

	@file_put_contents($pidFile, getmypid());
	$time=$unix->file_time_min($pidtime);
	if(!$GLOBALS["FORCE"]){
		if($time<5){
			Events("{$time}mn < 5mn ( use --force to bypass)");
			if($GLOBALS["VERBOSE"]){echo "{$time}mn < 5mn ( use --force to bypass)\n";}
			return;}
	}



	@unlink($pidtime);
	@file_put_contents($pidtime, time());

	$BigTimeEx=$unix->file_time_min($BigTime);
	

	$resolv=new resolv_conf();
	$q=new mysql_squid_builder();
	$sock=new sockets();




	if($resolv->MainArray["DNS1"]<>null){$DNS[]=$resolv->MainArray["DNS1"];}
	if($resolv->MainArray["DNS2"]<>null){$DNS[]=$resolv->MainArray["DNS2"];}
	if(isset($resolv->MainArray["DNS3"])){
		if($resolv->MainArray["DNS3"]<>null){$DNS[]=$resolv->MainArray["DNS3"];}
	}
	
	$sql="SELECT * FROM dns_servers";
	$q=new mysql_squid_builder();
	$results = $q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {$DNS[]=$ligne["dnsserver"];}
	
	
	
	
	$type="A";
	$ipClass=new IP();
	$minperf=$sock->GET_INFO("DNSPerfsPointer");
	if(!is_numeric($minperf)){$minperf=301450;}
	while (list ($index, $dnsA) = each ($DNS) ){$COMP[$dnsA]=true; }

	

	while (list ($dnsA,$none ) = each ($COMP) ){
		Events("Checks DNS $dnsA");
		if(!$ipClass->isIPAddress($dnsA)){continue;}
		if($GLOBALS["VERBOSE"]){echo "$dnsA\n";}
		$t['start'] = microtime(true);
		$rs = new Net_DNS2_Resolver(array('nameservers' => array($dnsA)));
		$rs->timeout=20;

		try {
			$date=date("Y-m-d H:i:s");
			$tA=rand("10000", "208460");
			$result = $rs->query("p4-cpsk2owex6nby-dwvedtapjla4ebei-$tA-i2-v6exp3-v4.metric.gstatic.com", "A");
			$t[$dnsA] = microtime(true);
			$time=mini_bench_to($t);
			$timeC=$time*10000;
		} catch(Net_DNS2_Exception $e) {
			$error=$e->getMessage();
			squid_admin_mysql(1, "DNS benchmark failed on $dnsA $error", $error,__FILE__,__LINE__);
			continue;
		}

		$perc=$minperf/$timeC;
		$perc=round($perc*100);

		Events("$dnsA Response Time:$time = $timeC/$minperf {$perc}%");


		foreach($result->answer as $record){
			if($ipClass->isIPAddress($record->address)){
				if($perc>100){$perc=100;}
				$zDate=date("Y-m-d H:i:s");
				$q=new postgres_sql();
				$q->QUERY_SQL("INSERT INTO dnsperfs (zDate,PROXYNAME,DNS,PERCENT,RESPONSE)
				VALUES ('$zDate','{$GLOBALS["MYHOSTNAME"]}','$dnsA','$perc','$time');");
				break;
			}
		}


	}
	
	GenerateGraph(true);
}

function Events($text){
	$unix=new unix();
	$unix->ToSyslog($text);
	
	
}
function mini_bench_to($arg_t, $arg_ra=false){
	$tttime=round((end($arg_t)-$arg_t['start'])*1000,4);
	if ($arg_ra) $ar_aff['total_time']=$tttime;
	else return $tttime;
	$prv_cle='start';
	$prv_val=$arg_t['start'];

	foreach ($arg_t as $cle=>$val)
	{
		if($cle!='start')
		{
			$prcnt_t=round(((round(($val-$prv_val)*1000,4)/$tttime)*100),1);
			if ($arg_ra) $ar_aff[$prv_cle.' -> '.$cle]=$prcnt_t;
			$aff.=$prv_cle.' -> '.$cle.' : '.$prcnt_t." %\n";
			$prv_val=$val;
			$prv_cle=$cle;
		}
	}
	if ($arg_ra) return $ar_aff;
	return $aff;
}
function GenerateGraph($nopid=false){
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableDNSPerfs")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableDNSPerfs", 1);
	}
	

	
	
	$unix=new unix();
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$time=$unix->file_time_min($pidtime);
	if(!$GLOBALS["FORCE"]){if($time<60){return;}}
	
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	$q=new mysql();
	$EnableDNSPerfs=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableDNSPerfs"));
	
	
	if($EnableDNSPerfs==0){
		$q->QUERY_SQL("DROP TABLE dashboard_dnsperf_day","artica_events");
		die();
	}
	
	$q=new mysql();
	$q->QUERY_SQL("DROP TABLE dashboard_dnsperf_day","artica_events");
	
	
	$q=new postgres_sql();
	$q->QUERY_SQL("DROP TABLE dashboard_dnsperf_day");
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS dashboard_dnsperf_day (time timestamp,DNS VARCHAR(128), percent FLOAT(5), response FLOAT(5) )");
	
	$q->create_index("dashboard_dnsperf_day", "ikey",array("time","dns","percent","response"));
	
	$hostname=$unix->hostname_g();
	$now=strtotime("-24 hour");
	$q=new postgres_sql();
	$sql="SELECT AVG(PERCENT) as PERCENT,AVG(RESPONSE) as RESPONSE,DNS FROM dnsperfs  where PROXYNAME='$hostname' and time > {$now}s GROUP BY DNS,time(10m) ORDER BY ASC";
	
	$results=$q->QUERY_SQL($sql);
	
	while($ligne=@pg_fetch_assoc($results)){
		$zDate=$ligne["zDate"];
		$PERCENT=$ligne["PERCENT"];
		if(!is_numeric($PERCENT)){continue;}
		
		$RESPONSE=$ligne["RESPONSE"];
		$DNS=$ligne["DNS"];
		$f[]="('$zDate','$DNS','$RESPONSE','$PERCENT')";
		
		
	}
	
	if(count($f)>0){
		print_r($f);
		$q->QUERY_SQL("INSERT INTO dashboard_dnsperf_day (time,dns,response,percent) 
				VALUES ".@implode(",", $f),"artica_events");
		
	}
	
	$hostname=$unix->hostname_g();
	$now=date("Y-m-d H:i:s",strtotime("-30 day"));
	$influx=new influx();
	$sql="SELECT MEAN(PERCENT) as PERCENT,MEAN(RESPONSE) as RESPONSE,
	DNS FROM dnsperfs  where PROXYNAME='$hostname' and time > '$now' GROUP BY DNS,time(1d) ORDER BY ASC";
	
	$main=$influx->QUERY_SQL($sql);
	$f=array();
	foreach ($main as $row) {
		$time=InfluxToTime($row->time);
		if(!is_numeric($row->PERCENT)){continue;}
		$PERCENT=$row->PERCENT;
		$RESPONSE=$row->RESPONSE;
		$DNS=$row->DNS;
		$zDate=date("Y-m-d",$time);
		$f[]="('$zDate','$DNS','$RESPONSE','$PERCENT')";
	
	
	}
	
	$sql="SELECT AVG(PERCENT) as PERCENT FROM dashboard_dnsperf_day";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	$sock=new sockets();
	$sock->SET_INFO("DashBoardDNSPerfsStats", $ligne["PERCENT"]);
	
		
	$q->QUERY_SQL("DROP TABLE dashboard_dnsperf_month","artica_events");
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS dashboard_dnsperf_month (`TIME` timestamp,`DNS` VARCHAR(128),`PERCENT` FLOAT(5),`RESPONSE` FLOAT(5)");	
	$q->create_index("dashboard_dnsperf_month","ikey",array("time","dns","percent","response"));
	
	if(count($f)>0){
		print_r($f);
		$q->QUERY_SQL("INSERT INTO dashboard_dnsperf_month (`TIME`,`DNS`,`RESPONSE`,`PERCENT`)
				VALUES ".@implode(",", $f),"artica_events");
	
	}
	
}

