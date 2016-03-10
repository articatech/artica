<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["PROGRESS"]=false;
$GLOBALS["TITLENAME"]="Socks5 Transparent daemon";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--progress#",implode(" ",$argv),$re)){$GLOBALS["PROGRESS"]=true;$GLOBALS["OUTPUT"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');



$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--iptablesx"){$GLOBALS["OUTPUT"]=true;RemoveIptables();die();}



function build_progress($text,$pourc){
	if(!$GLOBALS["PROGRESS"]){return;}
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/redsocks.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
	if($GLOBALS["PROGRESS"]){sleep(1);}

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
	build_progress("{stopping_service}",5);
	stop(true);
	sleep(1);
	build_progress("{building_settings}",45);
	build_progress("{remove} {firewall_rules}", 46);
	RemoveIptables();
	build_progress("{building_settings}",47);
	buildconfig();
	build_progress("{starting_service}",50);
	if(!start(true)){
		build_progress("{starting_service} {failed}",110);
		return;
	}
	build_progress("{starting_service} {done}",100);
}


function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("ss5");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, arpd not installed\n";}
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
		if(!IS_IPTABLES()){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} running iptables rules\n";}
			
			build_progress("{starting_service} {firewall}",54);
			
			system("/bin/redsocks-iptables.sh");
		}
		return true;
	}
	
	$EnableSS5=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableSS5"));
	

	if($EnableSS5==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableSS5)\n";}
		return;
	}
	
	if(!is_file("/etc/redsocks.conf")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (Not configured)\n";}
		return;
	}
	
	if(!is_file("/usr/bin/redsocks")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (not installed)\n";}
		return;
	}

	
	$nohup=$unix->find_program("nohup");

	$cmd="$nohup /usr/bin/redsocks -c /etc/redsocks.conf -p /var/run/redsocks.pid >/dev/null 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	system($cmd);
	
	
	

	for($i=1;$i<5;$i++){
		
		build_progress("{waiting} $i/5",65);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		
		
		if(is_file("/bin/redsocks-iptables.sh")){
			if(!IS_IPTABLES()){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} running iptables rules\n";}
				build_progress("{starting_service} {firewall}",68);
				system("/bin/redsocks-iptables.sh");
			}
			
		}
		build_progress("{success}",70);
		
		return true;
		
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
	


	build_progress("{stopping_service}",10);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		build_progress("{stopping_service}",15);
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		build_progress("{stopping_service}",45);
		return;
	}

	build_progress("{stopping_service}",35);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}
	build_progress("{stopping_service}",45);
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}
	
}

function PID_NUM(){
	
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/redsocks.pid");
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF("/usr/bin/redsocks");
	
}

function buildconfig(){
	$sock=new sockets();
	$unix=new unix();
	$SS5_SOCKS_IPADDR="127.0.0.1";
	$q=new mysql_squid_builder();
	
	$EnableSS5=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableSS5"));
	$FireHolEnable=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/FireHolEnable"));
	
	if($EnableSS5==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableSS5)\n";}
		@unlink("/bin/redsocks-iptables.sh");
		return;
	}
	


	$SS5_SOCKS_PORT=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SS5_SOCKS_PORT"));
	$SS5_SOCKS_INTERFACE=@file_get_contents("/etc/artica-postfix/settings/Daemons/SS5_SOCKS_INTERFACE");
	if($SS5_SOCKS_INTERFACE<>null){
		$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
		$SS5_SOCKS_IPADDR=$NETWORK_ALL_INTERFACES[$SS5_SOCKS_INTERFACE]["IPADDR"];
	}
	if($SS5_SOCKS_IPADDR==null){$SS5_SOCKS_IPADDR="127.0.0.1";}
	$iptables=$unix->find_program("iptables");
	
	$f[]="base {";
	$f[]="	log_debug = off;";
	$f[]="	log_info = on;";
	$f[]="	log = \"syslog:daemon\";";
	$f[]="	daemon = on;";
	$f[]="	redirector = iptables;";
	$f[]="}";
	$f[]="";
	$f[]="redsocks {";
	$f[]="	local_ip = 0.0.0.0;";
	$f[]="	local_port = 31337;";
	$f[]="	listenq = 128; ";
	$f[]="	ip = $SS5_SOCKS_IPADDR;";
	$f[]="	port = $SS5_SOCKS_PORT;";
	$f[]="	type = socks5;";
	$f[]="}";
	$f[]="";
	
	if($FireHolEnable==1){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} FireHolEnable = 1; run Firehol\n";}
		@unlink("/bin/redsocks-iptables.sh");
		@file_put_contents("/etc/redsocks.conf", @implode("\n", $f));
		system("/etc/init.d/firehol restart");
		return;
	}
	
	$MARKLOG="-m comment --comment \"ArticaRedSocksTransparent\"";
	$sql="SELECT * FROM ss5_transparent WHERE enabled=1";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $q->mysql_error\n";}
		
		return;
	}
	$sh=array();
	$CountForules=mysql_num_rows($results);
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $CountForules rule(s)\n";}
	
	$SS5_SOCKS_IPADDR="192.168.1.221";
	
	if($CountForules>0){
		$sh[]="#!/bin/sh -e";
		while ($ligne = mysql_fetch_assoc($results)) {
			$ID=$ligne["ID"];
			
			$ligne["src_host"]=trim($ligne["src_host"]);
			if($ligne["src_host"]=="0.0.0.0"){$ligne["src_host"]=null;}
			if($ligne["src_host"]=="0.0.0.0/0"){$ligne["src_host"]=null;}
			
			$ligne["dst_host"]=trim($ligne["dst_host"]);
			if($ligne["dst_host"]=="0.0.0.0"){$ligne["dst_host"]=null;}
			if($ligne["dst_host"]=="0.0.0.0/0"){$ligne["dst_host"]=null;}
			
			$INTERFACE_TEXT=null;
			$SRC_TEXT=null;
			$DST_TEXT=null;
			$eth=trim($ligne["eth"]);
			$DSTPORT=$ligne["dst_port"];
			if($ligne["src_host"]<>null){$SRC_TEXT="-s {$ligne["src_host"]} ";}
			if($ligne["dst_host"]<>null){$DST_TEXT="-d {$ligne["dst_host"]} ";}
			
			if($eth<>null){$INTERFACE_TEXT="--in-interface $eth ";}
			
			$JREDIRECT_TEXT="-j REDIRECT --to-port 31337";
			$sh[]="echo \"Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} out_trsocks.$ID\"";
			$sh[]="$iptables -t nat -N out_trsocks.$ID || true";
			$sh[]="$iptables -t nat -N PRESOCKS.$ID || true";
			
			$NETWORK_ALL_INTERFACES["lo"]["IPADDR"]="127.0.0.1";
			reset($NETWORK_ALL_INTERFACES);
			while (list ($interface, $AR) = each ($NETWORK_ALL_INTERFACES) ){
				$IPADDR=trim($AR["IPADDR"]);
				if($IPADDR==null){continue;}
				if($IPADDR=="0.0.0.0"){continue;}
				$sh[]="$iptables -t nat -A PRESOCKS.$ID -s $IPADDR -j RETURN || true";
			
			}

			$sh[]="$iptables -t nat -A PRESOCKS.$ID -p tcp -j REDIRECT --to-ports 31337 || true";
			$sh[]="$iptables -t nat -A PREROUTING -p tcp --sport 1024:65535 {$SRC_TEXT}{$DST_TEXT} --dport $DSTPORT -j PRESOCKS.$ID || true";
			$sh[]="$iptables -t nat -A OUTPUT -p tcp --sport 1024:65000 {$DST_TEXT} --dport $DSTPORT -m owner \! --uid-owner squid -j out_trsocks.$ID|| true";
			$sh[]="$iptables -t nat -A out_trsocks.$ID -p tcp \! -d 127.0.0.1 -j REDIRECT --to-ports 31337 || true";
			
			
			
	
		
		}
		
		
		
		
		
		
		$sh[]="";
	}
	/*
	$f[]="redudp {";
	$f[]="	local_ip = 127.0.0.1;";
	$f[]="	local_port = 31338;";
	$f[]="";
	$f[]="	// `ip' and `port' of socks5 proxy server.";
	$f[]="	ip = 127.0.0.1;";
	$f[]="	port = 1080;";
	$f[]="	login = username;";
	$f[]="	password = pazzw0rd;";
	$f[]="	dest_ip = 8.8.8.8;";
	$f[]="	dest_port = 53;";
	$f[]="	udp_timeout = 30;";
	$f[]="	udp_timeout_stream = 180;";
	$f[]="}";
	$f[]="";
	$f[]="dnstc {";
	$f[]="	local_ip = 127.0.0.1;";
	$f[]="	local_port = 5300;";
	$f[]="}";
	$f[]="";
	*/
	
	@unlink("/bin/redsocks-iptables.sh");
	if(count($sh)>0){
		@file_put_contents("/etc/redsocks.conf", @implode("\n", $f));
		@file_put_contents("/bin/redsocks-iptables.sh", @implode("\n", $sh));
		@chmod("/bin/redsocks-iptables.sh", 0755);
		
	}
	buildinit();
	
	
}
function buildinit(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/redsocks";
	$php5script=basename(__FILE__);
	$daemonbinLog="Red Socks Transparent Proxy";
	$SS5_SOCKS_PORT=@file_get_contents("/etc/artica-postfix/settings/Daemons/SS5_SOCKS_PORT");
	$SS5_SOCKS_INTERFACE=@file_get_contents("/etc/artica-postfix/settings/Daemons/SS5_SOCKS_INTERFACE");


	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         redsocks";
	$f[]="# Required-Start:    \$local_fs \$syslog";
	$f[]="# Required-Stop:     \$local_fs \$syslog";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: $daemonbinLog";
	$f[]="# chkconfig: - 80 75";
	$f[]="# description: $daemonbinLog";
	$f[]="### END INIT INFO";

	
	
	
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --iptablesx \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reconfigure)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure|reload} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Writing $INITD_PATH with new config\n";}
	
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}

}
function RemoveIptables(){
	$unix=new unix();
	$iptables_save=$unix->find_program("iptables-save");
	$iptables_restore=$unix->find_program("iptables-restore");

	system("$iptables_save > /etc/artica-postfix/iptables.conf");
	$data=file_get_contents("/etc/artica-postfix/iptables.conf");
	$datas=explode("\n",$data);
	$pattern="#.+?ArticaRedSocksTransparent#";
	$d=0;
	while (list ($num, $ligne) = each ($datas) ){
		if($ligne==null){continue;}
		if(preg_match($pattern,$ligne)){$d++;continue;}
		if(preg_match("#out_trsocks#",$ligne)){$d++;continue;}
		$conf=$conf . $ligne."\n";
	}
	file_put_contents("/etc/artica-postfix/iptables.new.conf",$conf);
	system("$iptables_restore < /etc/artica-postfix/iptables.new.conf");
	

	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Removing $d iptables rule(s) done...\n";
}
function IS_IPTABLES(){
	$unix=new unix();
	$iptables_save=$unix->find_program("iptables-save");
	$iptables_restore=$unix->find_program("iptables-restore");
	
	system("$iptables_save > /etc/artica-postfix/iptables.conf");
	$data=file_get_contents("/etc/artica-postfix/iptables.conf");
	$datas=explode("\n",$data);
	$pattern="#.+?ArticaRedSocksTransparent#";
	$d=0;
	while (list ($num, $ligne) = each ($datas) ){
		if($ligne==null){continue;}
		if(preg_match($pattern,$ligne)){$d++;continue;}
		
		
	}
	
	if($d>0){return true;}
	
}



?>