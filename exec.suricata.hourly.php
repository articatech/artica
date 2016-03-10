<?php
if(preg_match("#--verbose#",implode(" ",$argv))){
	$GLOBALS["DEBUG_SQL"]=true;
	$GLOBALS["VERBOSE"]=true;
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
$GLOBALS["DEBUG"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;

if($argv[1]=="--migrate"){migrate();exit;}
if($argv[1]=="--purge"){purge();exit;}

function migrate(){
	$q=new mysql();
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidtime="/etc/artica-postfix/pids/exec.suricata.hourly.migrate.time";
	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";
		return;
	}
	
	@file_put_contents($pidfile, getmypid());
	
	$timeExec=$unix->file_time_min($pidtime);
	if($timeExec<60){return;}
	
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	
	$hostname=$unix->hostname_g();
	
	if(!$q->TABLE_EXISTS("suricata_events", "artica_events")){return;}
	
	$results=$q->QUERY_SQL("SELECT * FROM suricata_events","artica_events");
	$postgres=new postgres_sql();
	$postgres->suricata_tables();
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$src_ip=$ligne["src_ip"];
		$zDate=$ligne["zDate"];
		$dst_ip=$ligne["dst_ip"];
		$dst_port=$ligne["dst_port"];
		$proto=$ligne["proto"];
		$signature=$ligne["signature"];
		$xcount=$ligne["xcount"];
		$severity=$ligne["severity"];
		$f[]="('$zDate','$src_ip','$dst_ip','$proto','$dst_port','$signature','$severity','$xcount','$hostname')";
		
	}
	
	
	if(count($f)>0){
		$prefix="INSERT INTO suricata_events (zDate,src_ip,dst_ip,proto,dst_port,signature,severity,xcount,proxyname) VALUES ";
		$postgres->QUERY_SQL($prefix.@implode(",", $f));
		if(!$postgres->ok){return;}
		$q->QUERY_SQL("DROP TABLE suricata_events","artica_events");
	}
	

	
}

function purge(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidtime="/etc/artica-postfix/pids/exec.suricata.hourly.purge.time";
	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";
		return;
	}
	
	@file_put_contents($pidfile, getmypid());
	if(system_is_overloaded()){return;}
	
	$timeExec=$unix->file_time_min($pidtime);
	if($timeExec<1440){return;}
	
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	
	$q=new postgres_sql();
	$sock=new sockets();
	$SuricataPurge=intval($sock->GET_INFO("SuricataPurge"));
	if($SuricataPurge==0){$SuricataPurge=15;}
	$q->QUERY_SQL("DELETE FROM suricata_events WHERE zdate < NOW() - INTERVAL '$SuricataPurge days'");
}



