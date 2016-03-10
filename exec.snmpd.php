<?php
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="SNMP Daemon";
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



$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;$GLOBALS["RECONFIGURE"]=true;build();die();}



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
	sleep(1);
	build();
	start(true);
	
}


function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("snmpd");

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

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		return;
	}
	$EnableSNMPD=$sock->GET_INFO("EnableSNMPD");
	if(!is_numeric($EnableSNMPD)){$EnableSNMPD=0;}
	

	if($EnableSNMPD==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableSNMPD)\n";}
		return;
	}
	
	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	$IPZ[]="127.0.0.1";
	$ips=$unix->NETWORK_ALL_INTERFACES(true);
	while (list ($ip, $line) = each ($ips) ){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} listen $ip\n";}
		$IPZ[]=$ip;
		
	}

	$cmd="$Masterbin -c /etc/snmp/snmpd.conf -Lsd -Lf /dev/null -u root -g root -I -smux -p /var/run/snmpd.pid";
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	build();
	shell_exec($cmd);

	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
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
	$filename=PID_PATH();
	$pid=trim(@file_get_contents($filename));
	$unix=new unix();
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF($unix->find_program("snmpd"));
}

function PID_PATH(){
	return "/var/run/snmpd.pid";
}


function build(){
	$unix=new unix();
	$sock=new sockets();
	$EnableSNMPD=$sock->GET_INFO("EnableSNMPD");
	$SNMPDNetwork=$sock->GET_INFO("SNMPDNetwork");
	if($SNMPDNetwork==null){$SNMPDNetwork="default";}
	if(!is_numeric($EnableSNMPD)){$EnableSNMPD=0;}
	$SNMPDCommunity=$sock->GET_INFO("SNMPDCommunity");
	if($SNMPDCommunity==null){$SNMPDCommunity="public";}
	$EnableProxyInSNMPD=intval($sock->GET_INFO("EnableProxyInSNMPD"));
	$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
	
	$organization=$WizardSavedSettings["organization"];
	$mail=$WizardSavedSettings["mail"];
	

$f[]="agentAddress 161";

$f[]="view   systemonly  included   .1.3.6.1.2.1.1";
$f[]="view   systemonly  included   .1.3.6.1.2.1.25.1";
$f[]="rwcommunity $SNMPDCommunity  $SNMPDNetwork";
$f[]="rouser   authOnlyUser";
$f[]="#rwuser   authPrivUser   priv";
$f[]="sysLocation    $organization";
$f[]="sysContact     Manager <$mail>";
$f[]="sysServices    72";
$f[]="proc  mountd";
$f[]="proc  ntalkd";


$apache=$unix->LOCATE_APACHE_BIN_PATH();
if($apache<>null){ $f[]="proc  $apache";}

$ufdbguardd=$unix->find_program("ufdbguardd");
if($ufdbguardd<>null){ $f[]="proc  $ufdbguardd";}


$f[]="proc  ".$unix->LOCATE_PHP5_BIN();

$mysqld=$unix->find_program("mysqld");
if($mysqld<>null){$f[]="proc  $mysqld";}
$lighttpd=$unix->find_program("lighttpd");
if($lighttpd<>null){$f[]="proc  $lighttpd";}
$clamd=$unix->find_program("clamd");
if($clamd<>null){$f[]="proc  $clamd";}

$f[]="disk       /     10000";
$f[]="disk       /var  5%";
$f[]="includeAllDisks  10%";
$f[]="load   12 10 5";
$f[]="iquerySecName   internalUser       ";
$f[]="rouser          internalUser";
$f[]="defaultMonitors          yes";


$unix=new unix();

if($EnableProxyInSNMPD==1){
	$squid=$unix->LOCATE_SQUID_BIN();
	if($squid<>null){
		$f[]="proc  $squid";
		$SquidSNMPPort=intval($sock->GET_INFO("SquidSNMPPort"));
		$SquidSNMPComunity=$sock->GET_INFO("SquidSNMPComunity");
		if($SquidSNMPPort==0){$SquidSNMPPort=intval($squid->snmp_port);}
		if($SquidSNMPPort==0){$SquidSNMPPort=3401;}
		
		if($SquidSNMPComunity==null){$SquidSNMPComunity=$squid->snmp_community;}
		if($SquidSNMPComunity==null){$SquidSNMPComunity="public";}
		if(is_file("/usr/share/squid3/mib.txt")){$moib=" -m /usr/share/squid3/mib.txt";}
		$f[]="proxy$moib -v 1 -c $SquidSNMPComunity 127.0.0.1:$SquidSNMPPort .1.3.6.1.4.1.3495.1";
	
	}
}




$f[]="master          agentx";
$f[]="#agentXSocket    tcp:localhost:705";

	@mkdir("/etc/snmp",0755,true);
	@file_put_contents("/etc/snmp/snmpd.conf", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/snmp/snmpd.conf done\n";}
	
}

?>