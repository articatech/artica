<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SERV_NAME"]="Dante Socks Proxy";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;
$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');

if($argv[1]=="--build-squid"){$GLOBALS["OUTPUT"]=true;build_squid();die();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;build();die();}
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop($argv[2]);die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start($argv[2]);die();}

function start($ID){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/sockd.pid";
	$SERV_NAME=$GLOBALS["SERV_NAME"];
	$pid=$unix->get_pid_from_file($pidfile);
	$sock=new sockets();
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Starting Task Already running PID $pid since {$time}mn\n";}
		return;
	}
		
	@file_put_contents($pidfile, getmypid());
	
	$daemonbin=$unix->find_program("sockd");
	if(!is_file($daemonbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]:$SERV_NAME is not installed...\n";}
		return;
	}	
	
	$pid=GET_PID($ID);
	
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME already running pid $pid since {$time}mn\n";}
		return;
	}	
	

	
	$nohup=$unix->find_program("nohup");
	
	$cmdline="$nohup $daemonbin -D -N 5 -f /etc/dante/conf.d/config.$ID -p /var/run/dante/$ID.pid 2>&1 &";
	
	if($GLOBALS["VERBOSE"]){echo $cmdline."\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Starting $SERV_NAME\n";}
	shell_exec("$cmdline");
	sleep(1);
	for($i=0;$i<10;$i++){
		$pid=GET_PID($ID);
		if($unix->process_exists($pid)){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME started pid .$pid..\n";}break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME wait $i/10\n";}
		sleep(1);
	}	
	sleep(1);
	$pid=GET_PID($ID);
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME failed to start\n";}
		$f=explode("\n",@file_get_contents($TMP));
		while (list ($num, $ligne) = each ($TMP) ){
			if(trim($ligne)==null){continue;}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $ligne\n";}
		}
	
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME success\n";}
		
		
	}
	if(!$unix->process_exists($pid)){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $cmdline\n";}}
	
}


function stop($ID){
	

	$SERV_NAME=$GLOBALS["SERV_NAME"];
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Already task running PID $pid since {$time}mn\n";}
		return;
	}

	$pid=GET_PID($ID);
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: $SERV_NAME already stopped...\n";}
		return;
	}	
	
	$kill=$unix->find_program("kill");
	$time=$unix->PROCCESS_TIME_MIN($pid);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Stopping $SERV_NAME with a ttl of {$time}mn\n";}
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Stopping $SERV_NAME smoothly...\n";}
	$cmd="$kill $pid >/dev/null";
	shell_exec($cmd);

	$pid=GET_PID($ID);
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: $SERV_NAME success...\n";}
		return;
	}	
	
	
	for($i=0;$i<10;$i++){
		$pid=GET_PID($ID);
		if($unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: $SERV_NAME kill pid $pid..\n";}
			unix_system_kill_force($pid);
		}else{
			break;
		}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME wait $i/10\n";}
		sleep(1);
	}	
	$pid=GET_PID($ID);
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: $SERV_NAME success...\n";}
		return;
	}	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: $SERV_NAME Failed...\n";}
}

function GET_PID($ID=null){
	$unix=new unix();
	
	$pid=$unix->get_pid_from_file("/var/run/dante/$ID.pid");
	if($unix->process_exists($pid)){return $pid;}
	
	$daemonbin=$unix->find_program("sockd");
	$daemonbin=basename($daemonbin);
	$conffile=str_replace(".", "\.", $conffile);
	return $unix->PIDOF_PATTERN("$daemonbin.*?config\.$ID");
}



function restart(){
	
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php5 ".__FILE__." --stop");
	shell_exec("$php5 ".__FILE__." --start");
	
}

function build_squid(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: Already task running PID $pid since {$time}mn\n";}
		return;
	}
	
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: Build services\n";}
	build_services();
	

	
	
}

function build(){build_squid();}



function build_services(){
	
	$q=new mysql_squid_builder();
	$unix=new unix();
	
	if(!isset($GLOBALS["NETWORK_ALL_INTERFACES"])){
		$unix=new unix();
		$GLOBALS["NETWORK_ALL_INTERFACES"]=$unix->NETWORK_ALL_INTERFACES();
	}
	
	if(!isset($GLOBALS["NETWORK_ALL_NICS"])){
		$unix=new unix();
		$GLOBALS["NETWORK_ALL_NICS"]=$unix->NETWORK_ALL_INTERFACES();
	}
	
	@mkdir("/home/squid/dante",0755,true);
	@mkdir("/var/run/dante",0755,true);
	@chown("/home/squid/dante","squid");
	@chgrp("/home/squid/dante", "squid");
	
	@chgrp("/var/run/dante", "squid");
	@chgrp("/var/run/dante", "squid");
	
	$sql="SELECT * FROM proxy_ports WHERE SOCKS=1 AND enabled=1";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){return;}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Starting ". mysql_num_rows($results)." service(s)\n";}
	
	if(mysql_num_rows($results)==0){remove_init_parent();return;}
	while ($ligne = mysql_fetch_assoc($results)) {
		$BindToDevice=null;
		$ID=$ligne["ID"];
		$port=intval($ligne["port"]);
		$eth=$ligne["nic"];	
		$WANPROXY_PORT=$ligne["WANPROXY_PORT"];
		$outgoing_addr=$ligne["outgoing_addr"];
	
		if($eth<>null){$BindToDevice=$eth;$ipaddr=$GLOBALS["NETWORK_ALL_NICS"][$eth]["IPADDR"];}
		if($ipaddr==null){$ipaddr="0.0.0.0";}
		if($BindToDevice==null){$BindToDevice="0.0.0.0";}
	
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Starting Listen $ipaddr port = $port and forward to $outgoing_addr\n";}
		$f[]="logoutput: /var/log/squid/sockd.log";
		$f[]="internal: $ipaddr port = $port";
		$f[]="external: $outgoing_addr";
		$f[]="user.notprivileged: squid";
		$f[]="debug: 1";
		$f[]="clientmethod: none";
		$f[]="socksmethod: none";
		$f[]="client pass {
			from:  0.0.0.0/0 port 1-65535 to: 0.0.0.0/0
			
		}";
		$f[]="socks pass {";
        $f[]="from: 0.0.0.0/0 to: 0.0.0.0/0";
       	$f[]=" protocol: tcp udp";
		$f[]="}";
		$f[]="";
		
		
		@mkdir("/etc/dante/conf.d",0755,true);
		@file_put_contents("/etc/dante/conf.d/config.{$ligne["ID"]}", @implode("\n", $f));
		$f=array();
		create_init($ID);
	}

}



function remove_init($ID){
	$INITD_PATH="/etc/init.d/dante-$ID";
	if(!is_file($INITD_PATH)){return;}
	$basename=basename($INITD_PATH);
	shell_exec("$INITD_PATH --stop --force");
	$unix=new unix();
	$rm=$unix->find_program("rm");
	if(is_file("/etc/dante/conf.d/config.$ID")){@unlink("/etc/dante/conf.d/config.$ID");}
	
	
	if($GLOBALS["OUTPUT"]){echo "Reconfigure...: ".date("H:i:s")." [INIT]: Remove $basename init\n";}
	
		if(is_file('/usr/sbin/update-rc.d')){
			shell_exec("/usr/sbin/update-rc.d -f $basename remove >/dev/null 2>&1");
		}
	
		if(is_file('/sbin/chkconfig')){
			shell_exec("/sbin/chkconfig --del $basename >/dev/null 2>&1");
		}
	
		if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
	
	
}

function create_init($ID){
	$unix=new unix();

	$php=$unix->LOCATE_PHP5_BIN();
	$daemonbin=$unix->find_program("sockd");
	$daemonbinLog=basename($daemonbin);
	$INITD_PATH="/etc/init.d/dante-$ID";
	$php5script=basename(__FILE__);
	if(!is_file($daemonbin)){return;}


	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         $daemonbinLog-$ID";
	$f[]="# Required-Start:    \$local_fs \$syslog \$network";
	$f[]="# Required-Stop:     \$local_fs \$syslog \$network";
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
	$f[]="    $php /usr/share/artica-postfix/$php5script --start $ID \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop $ID \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop $ID \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start $ID \$2 \$3";
	$f[]="    ;;";

	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop $ID \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start $ID \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: building $INITD_PATH done...\n";}

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



?>