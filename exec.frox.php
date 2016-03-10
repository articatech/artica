<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SERV_NAME"]="FTP Proxy Frox";
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
	$pidfile="/etc/artica-postfix/pids/frox.pid";
	$SERV_NAME=$GLOBALS["SERV_NAME"];
	$pid=$unix->get_pid_from_file($pidfile);
	$sock=new sockets();
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Starting Task Already running PID $pid since {$time}mn\n";}
		return;
	}
		
	@file_put_contents($pidfile, getmypid());
	
	$daemonbin=$unix->find_program("frox");
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
	
	$cmdline="$nohup $daemonbin -f /etc/frox/conf.d/config.$ID 2>&1 &";
	
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
	$daemonbin=$unix->find_program("frox");
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
	if(mysql_num_rows($results)==0){remove_init_parent();return;}
	while ($ligne = mysql_fetch_assoc($results)) {
		$BindToDevice=null;
		$ID=$ligne["ID"];
		$port=intval($ligne["port"]);
		$eth=$ligne["nic"];	
		$WANPROXY_PORT=$ligne["WANPROXY_PORT"];
	
		if($eth<>null){$BindToDevice=$eth;$ipaddr=$GLOBALS["NETWORK_ALL_NICS"][$eth]["IPADDR"];}
		if($ipaddr==null){$ipaddr="0.0.0.0";}
	
		$f[]="logoutput: /var/log/squid/sockd.log";
		$f[]="internal: $ipaddr port = $port";
		$f[]="external: $BindToDevice";
		$f[]="user.notprivileged: squid";
		$f[]="clientmethod: none";
		$f[]="method: none";
		$f[]="";
		$f[]="# Send SIGHUP after editing and it will be reread. This will fail";
		$f[]="# completely if we are chrooted and the config file isn't within the";
		$f[]="# dir we are chrooted to, or if we have dropped priveleges and no";
		$f[]="# longer have permission to read it! We may also no longer have";
		$f[]="# permission to bind to device.";
		$f[]="";
		$f[]="# Address to listen on - default is 0.0.0.0";
		$f[]="#";
		$f[]="# Listen firewall.localnet";
		$f[]="Listen $ipaddr";
		$f[]="";
		$f[]="# Port to listen on. Must be supplied.";
		$f[]="#";
		$f[]="Port $port";
		$f[]="";
		$f[]="# If specified then bind to this device";
		$f[]="#";
		if($BindToDevice<>null){
			$f[]="BindToDevice $BindToDevice";
		}
		$f[]="";
		$f[]="# Specify ranges for local ports to use for outgoing connections and";
		$f[]="# for sending out in PORT commands. By default these are all between";
		$f[]="# 40000 and 50000, but you might want to split them up if you have";
		$f[]="# complicated firewalling rules.";
		$f[]="#";
		$f[]="# ControlPorts 40000-40999";
		$f[]="# PassivePorts 41000-41999";
		$f[]="# ActivePorts  42000-42999";
		$f[]="";
		$f[]="# Number of seconds of no activity before closing session";
		$f[]="# Defaults to 3600";
		$f[]="#";
		$f[]="Timeout 1800";
		$f[]="";
		$f[]="#Maximum number of processes to fork.";
		$f[]="#";
		$f[]="# MaxForks 0 # For debugging -- only one connection may be served.";
		$f[]="MaxForks 10";
		$f[]="";
		$f[]="# User and group to drop priveliges to. Default is not to drop.";
		$f[]="#";
		$f[]="User squid";
		$f[]="Group squid";
		$f[]="";
		$f[]="# Directory to chroot to. Default is not to chroot. Filenames for";
		$f[]="# other options should be within this directory, but specified";
		$f[]="# relative to /.";
		$f[]="#";
		$f[]="# Chroot /usr/local/lib/frox";
		$f[]="";
		$f[]="# Block PORT commands asking data to be sent to ports<1024 and";
		$f[]="# prevent incoming control stream connections from port 20 to ";
		$f[]="# help depend against ftp bounce attacks. Defaults to on.";
		$f[]="#";
		$f[]="BounceDefend yes";
		$f[]="";
		$f[]="# If true then only accept data connections from the hosts the control";
		$f[]="# connections are to. Breaks the rfc, and defaults to off.";
		$f[]="#";
		$f[]="#SameAddress on";
		$f[]="";
		$f[]="# Try to transparently proxy the data connections as well. Not";
		$f[]="# necessary for most clients, and does increase security risks. Read";
		$f[]="# README.transdata for details. Defaults to off.";
		$f[]="#";
		$f[]="# TransparentData yes";
		$f[]="";
		$f[]="# File to log to. Default is stderr";
		$f[]="#";
		$f[]="# LogFile /dev/null";
		$f[]="LogFile /var/log/squid/ftp.access.log";
		$f[]="WorkingDir /home/squid/frox";
		$f[]="";
		$f[]="# File to store PID in. Default is not to. If this file is not within";
		$f[]="# the Chroot directory then it cannot be deleted on exit, but will";
		$f[]="# otherwise work fine.";
		$f[]="#";
		$f[]="PidFile /var/run/frox/frox-ftp-$ID.pid";
		$f[]="";
		$f[]="# Caching options. There should be at most one CacheModule line, and";
		$f[]="# Cache lines to give the options for that caching module. CacheModule";
		$f[]="# is HTTP (rewrites ftp requests as HTTP and sends them to a HTTP";
		$f[]="# proxy like squid), or local (cache files locally). The relevant";
		$f[]="# module needs to have been compiled in at compile time. See";
		$f[]="# FAQ for details. If there are no CacheModule lines then no";
		$f[]="# caching will be done.";
		$f[]="#";
		$f[]="# CacheModule local";
		$f[]="# Cache Dir /usr/local/lib/frox/cache/";
		$f[]="# Cache CacheSize 400";
		$f[]="#";
		$f[]="CacheModule HTTP";
		$f[]="HTTPProxy 127.0.0.1:$WANPROXY_PORT";
		$f[]="MinCacheSize 65536";
		$f[]="";
		$f[]="# Active --> Passive conversion. If set then all outgoing connections";
		$f[]="# from the proxy will be passive FTP, regardless of the type of the";
		$f[]="# connection coming in. This makes firewalling a lot easier. Defaults";
		$f[]="# to no.";
		$f[]="#";
		$f[]="APConv yes";
		$f[]="";
		$f[]="# Allow non-transparent proxying support. The user can connect";
		$f[]="# directly to frox, and give his username as user@host:port or";
		$f[]="# user@host. Defaults to no";
		$f[]="#";
		$f[]="# DoNTP yes";
		$f[]="";
		$f[]="#########################";
		$f[]="# Access control lists. #";
		$f[]="#########################";
		$f[]="# The format is: \"ACL Allow|Deny SRC - DST [PORTS]\"";
		$f[]="";
		$f[]="# SRC and DST may be in the form x.x.x.x, x.x.x.x/yy, x.x.x.x/y.y.y.y,";
		$f[]="# a dns name, or * to match everything.";
		$f[]="#";
		$f[]="# PORTS is a list of ports. If specified then the rule will only match";
		$f[]="# if the destination port of the connection is in this list. This is";
		$f[]="# likely only relevant if you are allowing non-transparent proxying of";
		$f[]="# ftp connections (ie. DoNTP is enabled above). Specifying * is equivalent ";
		$f[]="# to not specifying anything - all ports will be matched";
		$f[]="#";
		$f[]="# Any connection that matches no rules will be denied. Since there are";
		$f[]="# no rules by default you'll need to add something to let any";
		$f[]="# connections happen at all (look at the last example if you are";
		$f[]="# feeling lazy/not bothered by security).";
		$f[]="#";
		$f[]="# # Examples:";
		$f[]="# # Allow local network to ftp to port 21 only, and block host ftp.evil";
		$f[]="# ACL Deny * - ftp.evil            ";
		$f[]="# ACL Allow 192.168.0.0/255.255.0.0 - * 21";
		$f[]="#";
		$f[]="# # Allow local network to ftp anywhere except certain dodgy ports. Network ";
		$f[]="# # admin's machine can ftp anywhere.";
		$f[]="# ACL Allow admin.localnet - *";
		$f[]="# ACL Deny * - * 1-20,22-1024,6000-6007,7100";
		$f[]="# ACL Allow 192.168.0.0/16 - * *";
		$f[]="#";
		$f[]="# # You don't really believe in this security stuff, and just want";
		$f[]="# # everything to work. ";
		$f[]="ACL Allow * - *";
		$f[]="";
		$f[]="";
		
		@mkdir("/etc/frox/conf.d",0755,true);
		@file_put_contents("/etc/frox/conf.d/config.{$ligne["ID"]}", @implode("\n", $f));
		$f=array();
		create_init($ID);
	}

}



function remove_init($ID){
	$INITD_PATH="/etc/init.d/froxftp-$ID";
	if(!is_file($INITD_PATH)){return;}
	$basename=basename($INITD_PATH);
	shell_exec("$INITD_PATH --stop --force");
	$unix=new unix();
	$rm=$unix->find_program("rm");
	if(is_file("/etc/frox/conf.d/config.$ID")){@unlink("/etc/frox/conf.d/config.$ID");}
	
	
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
	$daemonbin=$unix->find_program("frox");
	$daemonbinLog=basename($daemonbin);
	$INITD_PATH="/etc/init.d/froxftp-$ID";
	$php5script=basename(__FILE__);
	if(!is_file($daemonbin)){return;}


	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         $daemonbinLog";
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