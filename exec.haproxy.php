<?php
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["TITLENAME"]="Load-Balancer Daemon";
$GLOBALS["OUTPUT"]=false;
$GLOBALS["COMMANDLINE"]=implode(" ",$argv);
$GLOBALS["NOCONF"]=false;
if(strpos($GLOBALS["COMMANDLINE"],"--verbose")>0){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;$GLOBALS["DEBUG"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.dnsmasq.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/framework/class.settings.inc");
include_once(dirname(__FILE__)."/ressources/class.haproxy.inc");
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--noconf#",implode(" ",$argv))){$GLOBALS["NOCONF"]=true;}


if($argv[1]=="--build"){build();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();die();}
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--iptables-remove"){iptables_delete_all();die();}

function restart($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("haproxy");

	if(!is_file($Masterbin)){
		build_progress_restart(110,"{not_installed}");
		if($GLOBALS["OUTPUT"]){echo "ReStarting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, haproxy not installed\n";}
		return;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			build_progress_restart(110,"{already_running}");
			if($GLOBALS["OUTPUT"]){echo "ReStarting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
build_progress_restart(50,"{stopping_service}");
stop(true);
build_progress_restart(80,"{building}");
build();
build_progress_restart(90,"{starting_service}");
if(!start(true)){
	build_progress_restart(110,"{starting_service} {failed}");
	return;
}	
build_progress_restart(100,"{success}");	
}
function build_progress_restart($pourc,$text){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/haproxy.progress", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/web/haproxy.progress",0755);


}

function reload(){
	if(!$GLOBALS["NOCONF"]){
		build();
	}
	if(!isRunning()){start(true);return;}
	$unix=new unix();
	$HAPROXY=$unix->find_program("haproxy");
	$CONFIG="/etc/haproxy/haproxy.cfg";
	$PIDFILE="/var/run/haproxy.pid";
	$EXTRAOPTS=null;
	$pids=@implode(" ", pidsarr());
	
	$cmd="$HAPROXY -f \"$CONFIG\" -p $PIDFILE -D $EXTRAOPTS -sf $pids 2>&1";
	exec($cmd,$results);
	while (list ($num, $ligne) = each ($results) ){
		echo "Starting......: ".date("H:i:s")." {$GLOBALS["TITLENAME"]} $ligne\n";
	}
}

function isRunning(){
	$running=false;
	$unix=new unix();
	$f=pidsarr();
	while (list ($num, $pid) = each ($f) ){
		if($unix->process_exists($pid)){
			return true;
		}
	}
	
	return false;
}

function pidsarr(){
	$R=array();
	$f=file("/var/run/haproxy.pid");
	while (list ($num, $ligne) = each ($f) ){
		$ligne=trim($ligne);
		if(!is_numeric($ligne)){continue;}
		$R[]=$ligne;
	}	
	return $R;
}



function build(){
	$sock=new sockets();
	$DenyHaproxyConf=intval($sock->GET_INFO("DenyHaproxyConf"));
	if($DenyHaproxyConf==1){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, Configuration is blocked..\n";}
		return;
	}
	
	
	$hap=new haproxy();
	$conf=$hap->buildconf();
	@unlink("/etc/haproxy/haproxy.cfg");
	if(trim($conf)==null){return;}
	@mkdir("/etc/haproxy",0755,true);
	@file_put_contents("/etc/haproxy/haproxy.cfg", $conf);
	Transparents_modes();
	rsyslog_conf();
	
	
}

function UDPServerRun(){
	
	
	$f=explode("\n",@file_get_contents("/etc/rsyslog.conf"));
	
	
	while (list ($num, $ligne) = each ($f) ){
		$ligne=trim($ligne);
		if(substr($ligne, 0,1)=="#"){continue;}
		if(!preg_match("#UDPServerRun#", $ligne)){continue;}
		return true;
		
	}
	
	
	
}

function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("haproxy");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, haproxy not installed\n";}
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
	$EnableHaProxy=intval($sock->GET_INFO("EnableHaProxy"));
	
	if(!is_file("/etc/haproxy/haproxy.cfg")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/haproxy/haproxy.cfg no such file\n";}
		return;
	}
	
	if($EnableHaProxy==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableHaProxy)\n";}
		return;
	}

	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");


	if(!UDPServerRun()){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} syslog server is not ready, prepare it\n";}
		system("$php5 /usr/share/artica-postfix/exec.syslog-engine.php --buildconf");
		if(UDPServerRun()){
			system("/etc/init.d/rsyslog restart");
		}else{
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed to prepare syslog engine\n";}
		}
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} syslog server [OK]\n";}
	}

	$cmd="$nohup $Masterbin -f /etc/haproxy/haproxy.cfg -D -p /var/run/haproxy.pid  >/dev/null 2>&1 &";
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}

	shell_exec($cmd);




	for($i=1;$i<5;$i++){
		build_progress_restart(95,"{starting_service} $i/5");
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
		return false;
	}

	return true;

}



function PID_NUM(){

	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/haproxy.pid");
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("haproxy");
	return $unix->PIDOF($Masterbin);

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

function Transparents_modes(){
	iptables_delete_all();
	$unix=new unix();
	$iptables=$unix->find_program("iptables");	
	$sysctl=$unix->find_program("sysctl");	
	$sql="SELECT * FROM haproxy WHERE enabled=1 AND transparent=1";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." {$GLOBALS["TITLENAME"]} building configuration failed $q->mysql_error\n";return;}}
	if(mysql_num_rows($results)==0){
		echo "Starting......: ".date("H:i:s")." {$GLOBALS["TITLENAME"]} building configuration no transparent configurations...\n";
		return;
	}
	shell_exec("$sysctl -w net.ipv4.ip_forward=1 2>&1");
	shell_exec("$sysctl -w net.ipv4.conf.default.send_redirects=0 2>&1");
	shell_exec("$sysctl -w net.ipv4.conf.all.send_redirects=0 2>&1");
	shell_exec("$sysctl -w net.ipv4.conf.eth0.send_redirects=0 2>&1");		
	shell_exec("$iptables -P FORWARD ACCEPT");
	
	return;
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$listen_add="127.0.0.1";
		$next_port=$ligne["listen_port"];
		$listen_ip=$ligne["listen_ip"];
		$transparent_port=$ligne["transparentsrcport"];
		if($transparent_port<1){continue;}
		echo "Starting......: ".date("H:i:s")." {$GLOBALS["TITLENAME"]} building configuration transparent request from $listen_ip:$transparent_port and redirect to $listen_add:$next_port\n";

		shell_exec2("$iptables -t nat -A PREROUTING -i eth0 -p tcp --dport $transparent_port -j ACCEPT -m comment --comment \"ArticaHAProxy\"");
		shell_exec2("$iptables -t nat -A PREROUTING -p tcp --dport $transparent_port -j REDIRECT --to-ports $next_port -m comment --comment \"ArticaHAProxy\"");
		shell_exec2("$iptables -t nat -A POSTROUTING -j MASQUERADE -m comment --comment \"ArticaHAProxy\"");
		shell_exec2("$iptables -t mangle -A PREROUTING -p tcp --dport $next_port -j DROP -m comment --comment \"ArticaHAProxy\"");
	}	
	
}

function shell_exec2($cmd){
	echo "Starting......: ".date("H:i:s")." {$GLOBALS["TITLENAME"]} $cmd\n";
	shell_exec($cmd);
	
}

function rsyslog_conf(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.syslog-engine.php --rsylogd >/dev/null 2>&1 &");
	
	
	
}

function iptables_delete_all(){
	$unix=new unix();
	$iptables_save=$unix->find_program("iptables-save");
	$iptables_restore=$unix->find_program("iptables-restore");
	system("$iptables_save > /etc/artica-postfix/iptables.conf");
	$data=file_get_contents("/etc/artica-postfix/iptables.conf");
	$datas=explode("\n",$data);
	$pattern="#.+?ArticaHAProxy#";	
	while (list ($num, $ligne) = each ($datas) ){
			if($ligne==null){continue;}
			if(preg_match($pattern,$ligne)){continue;}
			$conf=$conf . $ligne."\n";
			}
	
	file_put_contents("/etc/artica-postfix/iptables.new.conf",$conf);
	system("$iptables_restore < /etc/artica-postfix/iptables.new.conf");
}

