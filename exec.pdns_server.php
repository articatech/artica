<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="PDNS server";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.resolv.conf.inc');



$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();die();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;$GLOBALS["RECONFIGURE"]=true;build();die();}


function pdns_recursor_pid(){
	$unix=new unix();
	$pid=trim(@file_get_contents("/var/run/pdns/pdns_recursor.pid"));
	if($unix->process_exists($pid)){return $pid;}
	$recursorbin=$unix->find_program("pdns_recursor");
	return $unix->PIDOF($recursorbin);

}


function reload(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());	
	
	build();
	$recursor_pid=pdns_recursor_pid();
	$pdns_pid=PID_NUM();
	$pdns_control=$unix->find_program('pdns_control');
	$rec_control=$unix->find_program('rec_control');
	
	shell_exec("$pdns_control --config-dir=/etc/powerdns reload >/dev/null 2>&1");
	shell_exec("$rec_control --config-dir=/etc/powerdns --socket-dir=/var/run/pdns reload >/dev/null 2>&1");
}


function restart() {
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
		
	stop(true);
	$php5=$unix->LOCATE_PHP5_BIN();
	build();
	shell_exec("$php5 /usr/share/artica-postfix/exec.pdns.php --mysql");
	sleep(1);
	start(true);
	
}
function MODULES_DIR(){
	$f[]="/usr/lib/pdns/libldapbackend.so";
	$f[]="/usr/lib/powerdns/libldapbackend.so";
	$f[]="/usr/lib64/powerdns/libldapbackend.so";
	$f[]="/usr/lib64/pdns/libldapbackend.so";
	
	$f[]="/usr/lib/pdns/libgmysqlbackend.so";
	$f[]="/usr/lib/powerdns/libgmysqlbackend.so";
	$f[]="/usr/lib64/powerdns/libgmysqlbackend.so";
	$f[]="/usr/lib64/pdns/libgmysqlbackend.so";	
	
	
	while (list ($field, $filepath) = each ($f) ){
		if(is_file($filepath)){return dirname($filepath);}
		
	}
}


function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("pdns_server");
	$PowerDNSLogLevel=$sock->GET_INFO("PowerDNSLogLevel");
	$PowerDNSDNSSEC=$sock->GET_INFO("PowerDNSDNSSEC");
	$PowerDNSLogsQueries=$sock->GET_INFO("PowerDNSLogsQueries");
	
	if(!is_numeric($PowerDNSLogLevel)){$PowerDNSLogLevel=0;}
	if(!is_numeric($PowerDNSDNSSEC)){$PowerDNSDNSSEC=0;}
	if(!is_numeric($PowerDNSLogsQueries)){$PowerDNSLogsQueries=0;}
	
	
	
	
	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, not installed\n";}
		return;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	
	$EnablePDNS=$sock->GET_INFO("EnablePDNS");
	if(!is_numeric($EnablePDNS)){$EnablePDNS=0;}
	

	
	if($EnablePDNS==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnablePDNS)\n";}
		stop(true);
		return;
	}
	
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		return;
	}
		
	
	if(!is_file("/usr/lib/libboost_serialization.so.1.49.0")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Please wait, installing libboost-serialization1.49.0\n";}
		$unix->DEBIAN_INSTALL_PACKAGE("libboost-serialization1.49.0");
		
	}
	
	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	$dnsmasq_bin=$unix->find_program("dnsmasq");
	$kill=$unix->find_program("kill");
	
	
	if(is_file($dnsmasq_bin)){
		$dnsmasq_pid=$unix->PIDOF($dnsmasq_bin);
		if($unix->process_exists($dnsmasq_pid)){
			unix_system_kill_force($dnsmasq_pid);
			
		}
	}
	
	@mkdir("/var/run/pdns",0755,true);
	
	$t=explode("\n",@file_get_contents("/etc/powerdns/pdns.conf"));
	while (list ($index, $ligne) = each ($t) ){
		if(preg_match("#^recursor=(.+)$#", $ligne,$re)){
			$recursor=trim($re[1]);
			break;
		}
		
	}
	
	$PowerDNSPerfs=unserialize(base64_encode($sock->GET_INFO("PowerDNSPerfs")));
	if(!isset($PowerDNSPerfs["cache-ttl"])){$PowerDNSPerfs["cache-ttl"]=3600;}
	if(!isset($PowerDNSPerfs["negquery-cache-ttl"])){$PowerDNSPerfs["negquery-cache-ttl"]=7200;}
	if(!isset($PowerDNSPerfs["query-cache-ttl"])){$PowerDNSPerfs["query-cache-ttl"]=300;}
	if(!isset($PowerDNSPerfs["recursive-cache-ttl"])){$PowerDNSPerfs["recursive-cache-ttl"]=7200;}
	
	if(!is_numeric($PowerDNSPerfs["negquery-cache-ttl"])){$PowerDNSPerfs["negquery-cache-ttl"]=7200;}
	if(!is_numeric($PowerDNSPerfs["query-cache-ttl"])){$PowerDNSPerfs["query-cache-ttl"]=300;}
	if(!is_numeric($PowerDNSPerfs["recursive-cache-ttl"])){$PowerDNSPerfs["recursive-cache-ttl"]=7200;}
	if(!is_numeric($PowerDNSPerfs["cache-ttl"])){$PowerDNSPerfs["cache-ttl"]=3600;}	
	
	
	$cmds[]=$Masterbin;
	$cmds[]="--daemon --guardian=yes"; 
	$cmds[]="--recursor=$recursor"; 
	$cmds[]="--config-dir=/etc/powerdns";
	$cmds[]="--cache-ttl={$PowerDNSPerfs["cache-ttl"]}";
	$cmds[]="--negquery-cache-ttl={$PowerDNSPerfs["negquery-cache-ttl"]}";
	$cmds[]="--query-cache-ttl={$PowerDNSPerfs["query-cache-ttl"]}";
	$cmds[]="--recursive-cache-ttl={$PowerDNSPerfs["recursive-cache-ttl"]}";

	if($PowerDNSLogLevel>8){
		$cmds[]="--log-dns-details --loglevel=$PowerDNSLogLevel";
	}else{
		if($PowerDNSLogsQueries==1){
			$cmds[]="--log-dns-details";
		}
		
		if($PowerDNSLogLevel>0){
			$cmds[]="--loglevel=$PowerDNSLogLevel";
		}
	}	
	
	$cmd=@implode(" ", $cmds);
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	
	shell_exec($cmd);

	for($i=1;$i<11;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		if($PowerDNSDNSSEC==1){shell_exec("$php5 /usr/share/artica-postfix/exec.pdns.php --dnssec");}
		
		
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	}


}

function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		return;
	}
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	



	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}

}

function PID_NUM(){
	
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/pdns/pdns.pid");
	if($unix->process_exists($pid)){return $pid;}
	$bin=$unix->find_program("pdns_server");
	return $unix->PIDOF($bin);
	
	
}




function build(){
	$sock=new sockets();
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$q=new mysql();
	
	$DisablePowerDnsManagement=$sock->GET_INFO("DisablePowerDnsManagement");
	if(!is_numeric($DisablePowerDnsManagement)){$DisablePowerDnsManagement=0;}
	if($DisablePowerDnsManagement==1){return;}
	@mkdir("/etc/powerdns/pdns.d",0755,true);
			
	$PowerDNSLogLevel=$sock->GET_INFO("PowerDNSLogLevel");
	$PowerDNSMySQLEngine=$sock->GET_INFO("PowerDNSMySQLEngine");
	$PowerUseGreenSQL=$sock->GET_INFO("PowerUseGreenSQL");
	$PowerDisableDisplayVersion=$sock->GET_INFO("PowerDisableDisplayVersion");
	$PowerChroot=$sock->GET_INFO("PowerChroot");
	
	
	$PowerActHasMaster=$sock->GET_INFO("PowerActHasMaster");
	
	$PowerDNSDNSSEC=$sock->GET_INFO("PowerDNSDNSSEC");
	$PowerDNSDisableLDAP=$sock->GET_INFO("PowerDNSDisableLDAP");
	$PowerDNSPublicMode=$sock->GET_INFO("PowerDNSPublicMode");
	
	$PowerActAsSlave=$sock->GET_INFO("PowerActAsSlave");
	$PdnsNoWriteConf=$sock->GET_INFO("PdnsNoWriteConf");
	$PowerSkipCname=$sock->GET_INFO("PowerSkipCname");
	$PDSNInUfdb=$sock->GET_INFO("PDSNInUfdb");
	$SquidActHasReverse=$sock->GET_INFO("SquidActHasReverse");
	$EnableUfdbGuard=$sock->GET_INFO("EnableUfdbGuard");
	$PowerDNSMySQLType=$sock->GET_INFO("PowerDNSMySQLType");
	$PowerDNSMySQLRemotePort=$sock->GET_INFO("PowerDNSMySQLRemotePort");
	$EnablePDNSRecurseRestrict=$sock->GET_INFO("EnablePDNSRecurseRestrict");
	$PdnsHotSpot=$sock->GET_INFO("PdnsHotSpot");
	
	$PowerDNSMySQLRemoteServer=$sock->GET_INFO("PowerDNSMySQLRemoteServer");
	$PdnsHotSpot=$sock->GET_INFO("PdnsHotSpot");
	$PowerDNSMySQLRemoteAdmin=$sock->GET_INFO("PowerDNSMySQLRemoteAdmin");
	$PowerDNSMySQLRemotePassw=$sock->GET_INFO("PowerDNSMySQLRemotePassw");
	
	if(!is_numeric($PowerDNSLogLevel)){$PowerDNSLogLevel=1;}
	if(!is_numeric($PowerDNSMySQLEngine)){$PowerDNSMySQLEngine=1;}
	if(!is_numeric($PowerUseGreenSQL)){$PowerUseGreenSQL=0;}
	if(!is_numeric($PowerDisableDisplayVersion)){$PowerDisableDisplayVersion=0;}
	if(!is_numeric($PowerChroot)){$PowerChroot=0;}
	if(!is_numeric($PowerActHasMaster)){$PowerActHasMaster=0;}
	if(!is_numeric($PowerDNSDNSSEC)){$PowerDNSDNSSEC=0;}
	if(!is_numeric($PowerDNSDisableLDAP)){$PowerDNSDisableLDAP=1;}
	if(!is_numeric($PowerDNSPublicMode)){$PowerDNSPublicMode=0;}
	if(!is_numeric($PowerActAsSlave)){$PowerActAsSlave=0;}
	if(!is_numeric($PdnsNoWriteConf)){$PdnsNoWriteConf=0;}
	if(!is_numeric($PowerSkipCname)){$PowerSkipCname=0;}
	if(!is_numeric($PDSNInUfdb)){$PDSNInUfdb=0;}
	if(!is_numeric($SquidActHasReverse)){$SquidActHasReverse=0;}
	if(!is_numeric($EnableUfdbGuard)){$EnableUfdbGuard=0;}
	if(!is_numeric($PowerDNSMySQLType)){$PowerDNSMySQLType=1;}
	if(!is_numeric($PowerDNSMySQLRemotePort)){$PowerDNSMySQLRemotePort=3306;}
	if(!is_numeric($EnablePDNSRecurseRestrict)){$EnablePDNSRecurseRestrict=0;}
	$LaunchPipe=0;
	$pipe='';
	
	if($SquidActHasReverse==1){$PDSNInUfdb=0;}
	if($EnableUfdbGuard==0){$PDSNInUfdb=0;}
	if(!is_file("/usr/bin/ufdbgclient")){$PDSNInUfdb=0;}
	$q=new mysql();
	
	$database_admin=$q->mysql_admin;
	$database_password=$q->mysql_password;
	if($database_admin==null){$database_admin='root';}
	
	if($PDSNInUfdb==1){$LaunchPipe=1;}
	if($PdnsHotSpot==1){$LaunchPipe=1;}
	if($LaunchPipe==1){$launch[]='pipe';}
	
	$recursor="127.0.0.1:1553";
	
	$recursor_bin=$unix->find_program("pdns_recursor");
	if(!is_file($recursor_bin)){
		$resolv=new resolv_conf();
		$IpClass=new IP();
		$rr=array();
		if($resolv->MainArray["DNS1"]=="127.0.0.1"){$resolv->MainArray["DNS1"]=null;}
		if($resolv->MainArray["DNS2"]=="127.0.0.1"){$resolv->MainArray["DNS2"]=null;}
		if($resolv->MainArray["DNS3"]=="127.0.0.1"){$resolv->MainArray["DNS3"]=null;}
		
		if(!$IpClass->isValid($resolv->MainArray["DNS1"])){$resolv->MainArray["DNS1"]=null;}
		if(!$IpClass->isValid($resolv->MainArray["DNS2"])){$resolv->MainArray["DNS2"]=null;}
		if(!$IpClass->isValid($resolv->MainArray["DNS3"])){$resolv->MainArray["DNS3"]=null;}
		
		if($resolv->MainArray["DNS1"]<>null){$rr[]=$resolv->MainArray["DNS1"];}
		if($resolv->MainArray["DNS2"]<>null){$rr[]=$resolv->MainArray["DNS2"];}
		if($resolv->MainArray["DNS3"]<>null){$rr[]=$resolv->MainArray["DNS3"];}
		$recursor=$rr[0];
	}
	
	
// PowerSkipCname: Do not perform CNAME indirection for each query
	
	if($PdnsNoWriteConf==1){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}  PdnsNoWriteConf is enabled, skip the config and aborting pdns.conf\n";}
		return;
	}
	$pdnssec_bin=$unix->find_program("pdnssec");
	if(!is_file($pdnssec_bin)){$PowerDNSDNSSEC=0;}
	
	$PowerDNSListenAddrDefault=true;
	
	$cdirlist[]='127.0.0.0/8';
	$cdirlist[]='127.0.0.1';	
	

	$PowerDNSListenAddr=$sock->getFrameWork("PowerDNSListenAddr");
	$t=array();
	$ipA=explode("\n", $PowerDNSListenAddr);
	while (list ($line2,$ip) = each ($ipA) ){
		if(trim($ip)==null){continue;}
		if(!$unix->isIPAddress($ip)){continue;}
		$t[$ip]=$ip;
	}
	
	if(count($t)==0){
		$ips=new networking();
		$ipz=$ips->ALL_IPS_GET_ARRAY();
		while (list ($ip, $line2) = each ($ipz) ){$t[$ip]=$ip;}
	}
	
	$LOCAL_ADDRESSES=array();
	unset($t["127.0.0.1"]);
	while (list ($a,$b) = each ($t) ){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} listen: $a\n";}
		$LOCAL_ADDRESSES[]=$a;
		$cdirlist[]=$a;
	}	

$iplistV6=array();
$RecursoriplistV6=array();
$RecursoripAllowFrom=array();

$PowerDNSListenAddrV6=explode("\n",$sock->GET_INFO("PowerDNSListenAddrV6"));
while (list ($field, $value) = each ($PowerDNSListenAddrV6) ){
	if(trim($value)==null){continue;}
	$iplistV6[]=$value;
	$RecursoriplistV6[]="[".$value."]";
	$RecursoripAllowFrom[]=$value;
}
$launch[]='gmysql';

if($PowerDNSDisableLDAP==0){
	$launch[]='ldap';
}
	

$f=array();

if($EnablePDNSRecurseRestrict==1){
	$t=array();
	$sql="SELECT * FROM pdns_restricts";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		$addr=trim($ligne["address"]);
		if($addr==null){continue;}
		$t[]=$addr;
	}
	if(count($t)>0){$f[]="allow-recursion=".@implode(",", $t);}
}
$f[]="#allow-recursion=0.0.0.0/0 ";
$f[]="#allow-recursion-override=on";
$f[]="cache-ttl=20";
if($PowerChroot==1){
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} is chrooted\n";}
	$f[]="chroot=./";
}

	
if($PowerActHasMaster==1){
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Act has master\n";}
	$f[]="master=yes";
}
   

if($PowerActAsSlave==1){
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Act has Slave\n";}
	$f[]="slave=yes";
}
	
	$f[]="config-dir=/etc/powerdns";
	$f[]="# config-name=";
	$f[]="# control-console=no";
	$f[]="daemon=yes";
	$f[]="# default-soa-name=a.misconfigured.powerdns.server";
	$f[]="disable-axfr=no";
	$f[]="# disable-tcp=no";
	$f[]="# distributor-threads=3";
	$f[]="# fancy-records=no";
	$f[]="guardian=yes";
	$f[]="launch=".@implode(",", $launch);

if($PDSNInUfdb==1){
    @chmod("/usr/share/artica-postfix/exec.pdns.pipe.php",0777);
	$f[]="pipe-command=/usr/share/artica-postfix/exec.pdns.pipe.php";
	$f[]="pipebackend-abi-version=2";
	$f[]="distributor-threads=2";
}
	
if($PdnsHotSpot==1){
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} HotSpot engine...\n";}
   	@chmod("/usr/share/artica-postfix/exec.pdns.pipe.php",0777);
	$f[]="pipe-command=/usr/share/artica-postfix/exec.pdns.pipe.php";
	$f[]="pipebackend-abi-version=2";
	$f[]="distributor-threads=2";
}
	//$f[]="lazy-recursion=yes";
	$f[]="#local-address=0.0.0.0";
	$f[]="local-address=".@implode(",",$LOCAL_ADDRESSES);
	
	if(count($iplistV6)>0){
		$iplistV6[]="::1";
		$f[]="local-ipv6=".@implode(",", $iplistV6);
	}
	

	//$f[]="query-local-address6=::1";
	$f[]="local-port=53";
	$f[]="log-dns-details=on";
	
	//$f[]="logfile=/var/log/pdns.log";
	$f[]="# logging-facility=";
	$f[]="loglevel=$PowerDNSLogLevel";
	$f[]="# max-queue-length=5000";
	$f[]="# max-tcp-connections=10";
	$MODULES_DIR=MODULES_DIR();
	if($MODULES_DIR<>null){
		$f[]="module-dir=$MODULES_DIR";
	}
	$f[]="# negquery-cache-ttl=60";
	$f[]="out-of-zone-additional-processing=yes";
	$f[]="# query-cache-ttl=20";
	$f[]="query-logging=yes";
	$f[]="# queue-limit=1500";
	$f[]="# receiver-threads=1";
	$f[]="# recursive-cache-ttl=10";
	$f[]="recursor=$recursor";        //
	$f[]="#setgid=pdns";
	$f[]="#setuid=pdns";
	//$f[]="skip-cname=yes";
	$f[]="# slave-cycle-interval=60";
	$f[]="# smtpredirector=a.misconfigured.powerdns.smtp.server";
	$f[]="# soa-minimum-ttl=3600";
	$f[]="# soa-refresh-default=10800";
	$f[]="# soa-retry-default=3600";
	$f[]="# soa-expire-default=604800";
	$f[]="# soa-serial-offset=0";
	$f[]="socket-dir=/var/run/pdns";
	$f[]="# strict-rfc-axfrs=no";
	$f[]="# urlredirector=127.0.0.1";
	//$f[]="use-logfile=yes";
	$f[]="webserver=yes";
	$f[]="webserver-address=127.0.0.1";
	$f[]="webserver-password=";
	$f[]="webserver-port=8081";
	$f[]="webserver-print-arguments=no";
	//if PowerSkipCname=0 then $f[]="skip-cname=no') else $f[]="skip-cname=yes";
	$f[]="# wildcard-url=no";
	$f[]="# wildcards=";
	if($PowerDisableDisplayVersion==0){
		$f[]="version-string=powerdns";
	}else{
		$f[]="version-string=nope";
	}
	
	
	if($PowerDNSMySQLType==1){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} MySQL /var/run/mysqld/mysqld.sock@$database_admin\n";}
		$G[]="gmysql-socket=/var/run/mysqld/mysqld.sock";
		$G[]="gmysql-user=$database_admin";
		if($database_password<>null){$G[]="gmysql-password=$database_password";}
		$G[]="gmysql-dbname=powerdns";
		shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.pdns.php --mysql >/dev/null 2>&1 &");
		
		
		
		
	}

	if($PowerDNSMySQLType==2){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} MySQL $PowerDNSMySQLRemoteServer@$PowerDNSMySQLRemoteAdmin\n";}
		$G[]="gmysql-host=$PowerDNSMySQLRemoteServer";
   		$G[]="gmysql-port=$PowerDNSMySQLRemotePort";
		$G[]="gmysql-user=$PowerDNSMySQLRemoteAdmin";
		if ($PowerDNSMySQLRemotePassw<>null){$G[]="gmysql-password=$PowerDNSMySQLRemotePassw";}
   		$G[]="gmysql-dbname=powerdns";
	}
	
	if($PowerDNSMySQLType==3){
   		$mysql_server=$q->mysql_server;
   		if( ($mysql_server=='localhost') OR ($mysql_server=="127.0.0.1")){$mysql_server="127.0.0.1";}
   		$mysql_port=$q->mysql_port;
   		if($PowerUseGreenSQL==1){
   			shell_exec("$php5 /usr/share/artica-postfix/exec.greensql.php --sets");
   			$GreenPort=@file_get_contents("/etc/artica-postfix/settings/Mysql/GreenPort");
   			if(!is_numeric($GreenPort)){$GreenPort=3305;}
   			$mysql_port=$GreenPort;
   		}
   		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} MySQL $mysql_server:$mysql_port@$database_admin\n";}
   		$G[]="gmysql-host=$mysql_server";
   		$G[]="gmysql-port=$mysql_port";
   		$G[]="gmysql-user=$database_admin";
   		if ($database_password<>null){$G[]="gmysql-password=$database_password";}
   		$G[]="gmysql-dbname=powerdns";
   }
   
   $f[]=@implode("\n", $G);
	
  
	if($PowerDNSDNSSEC==1){$f[]="gmysql-dnssec";}
	
	if($PowerDNSDisableLDAP==0){
		$ldap=new clladp();
		$f[]="ldap-host=$ldap->ldap_host:$ldap->ldap_port";
		$f[]="ldap-basedn=ou=dns,$ldap->suffix";
		$f[]="ldap-binddn=cn=$ldap->ldap_admin,$ldap->suffix";
		$f[]="ldap-secret=$ldap->ldap_password";
		$f[]="ldap-method=simple";
	}
		
	@mkdir("/etc/powerdns/pdns.d",0755,true);
	
	if(is_file("/etc/pdns/pdns.conf")){@file_put_contents("/etc/pdns/pdns.conf", @implode("\n", $f));}
	@file_put_contents("/etc/powerdns/pdns.conf", @implode("\n", $f));
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} pdns.conf done...\n";}
	
	$f[]=array();
	if($PowerDNSDisableLDAP==0){
		$f[]="ldap-host=$ldap->ldap_host:$ldap->ldap_port";
		$f[]="ldap-basedn=ou=dns,$ldap->suffix";
		$f[]="ldap-binddn=cn=$ldap->ldap_admin,$ldap->suffix";
		$f[]="ldap-secret=$ldap->ldap_password";
		$f[]="ldap-method=simple";
	}
	
	$f[]="recursor=$recursor";
	$f[]=@implode("\n", $G);
	@file_put_contents("/etc/powerdns/pdns.d/pdns.local", @implode("\n", $f));	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} pdns.local done...\n";}

	$f=array();
	$CDIR=$cdirlist;
	if(count($RecursoripAllowFrom)>0){
		while (list ($field, $value) = each ($RecursoripAllowFrom) ){$CDIR[]=$value;}
	}

	while (list ($field, $value) = each ($CDIR) ){$AA[$value]=$value;}
	while (list ($field, $value) = each ($AA) ){$ALLOW[]=$field;}
	
		
	If(is_file('/etc/powerdns/forward-zones-file')){$f[]="forward-zones-file=/etc/powerdns/forward-zones-file";}
	If(is_file('/etc/powerdns/forward-zones-recurse')){$f[]="forward-zones-recurse=".trim(@file_get_contents('/etc/powerdns/forward-zones-recurse'));}
	

	$f[]="local-address=127.0.0.1";
	$f[]="quiet=no";
	$f[]="config-dir=/etc/powerdns/";
	$f[]="daemon=yes";
	$f[]="local-port=1553";
	$f[]="log-common-errors=yes";
	$f[]="allow-from=".@implode(",", $ALLOW);
	$f[]="socket-dir=/var/run/pdns";
	//$f[]="query-local-address6=";
	@file_put_contents("/etc/powerdns/recursor.conf", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} recursor.conf done...\n";}

	
	if($PowerDNSDNSSEC==1){
		shell_exec("$php5 /usr/share/artica-postfix/exec.pdns.php --dnsseck");
	}

}



