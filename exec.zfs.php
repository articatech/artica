#!/usr/bin/php -q
<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="InfluxDB Daemon";
$GLOBALS["PROGRESS"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;
$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
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
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');


$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--clean"){clean();die();}
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--dump-db"){$GLOBALS["OUTPUT"]=true;dump_dbs();die();}
if($argv[1]=="--create-db"){$GLOBALS["OUTPUT"]=true;create_db();die();}
if($argv[1]=="--interface"){$GLOBALS["OUTPUT"]=true;InterfaceSize();$GLOBALS["DEBUG_INFLUX"]=true;die();}
if($argv[1]=="--InfluxDbSize"){$GLOBALS["OUTPUT"]=true;InfluxDbSize();$GLOBALS["DEBUG_INFLUX"]=true;die();}
if($argv[1]=="--install"){$GLOBALS["OUTPUT"]=true;install();die();}
if($argv[1]=="--remove-db"){$GLOBALS["OUTPUT"]=true;remove_db();die();}
if($argv[1]=="--install-progress"){$GLOBALS["OUTPUT"]=true;$GLOBALS["PROGRESS"]=true;install();die();}





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
	start(true);
	
}

function dump_dbs(){
	$influx=new influx();
	$influx->ROOT_DUMP_ALL_DATABASES();
	
}
function DebianVersion(){

	$ver=trim(@file_get_contents("/etc/debian_version"));
	preg_match("#^([0-9]+)\.#",$ver,$re);
	if(preg_match("#squeeze\/sid#",$ver)){return 6;}
	return $re[1];

}

function install(){
	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
	$unix=new unix();
	$DebianVersion=DebianVersion();
	if($DebianVersion<7){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, zfs Debian version incompatible!\n";}
		build_progress_idb("Incompatible system!",110);
		die();
	}
	
	
	
	if(!is_file("/etc/apt/sources.list.d/zfsonlinux.list")){
		$filename="zfsonlinux_6_all.deb";
		$curl=new ccurl("http://archive.zfsonlinux.org/debian/pool/main/z/zfsonlinux/zfsonlinux_6_all.deb");
		$tmpdir=$unix->TEMP_DIR();
		build_progress_idb("{downloading} zfsonlinux_6_all",1);
	
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Downloading zfsonlinux_6_all\n";}
		if(!$curl->GetFile("$tmpdir/$filename")){
			
			build_progress_idb("$curl->error",110);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $curl->error\n";}
			
			while (list ($key, $value) = each ($curl->errors) ){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $value\n";}	
			}
			
			
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, ZFS unable to install....\n";}
			@unlink("$tmpdir/$filename");
			return;
		}
		
		build_progress_idb("{extracting}",10);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, extracting....\n";}
		$dpkg=$unix->find_program("dpkg");
		system("$dpkg --force-all -i $tmpdir/$filename");
	
	}
	
	if(!is_file("/etc/apt/sources.list.d/zfsonlinux.list")){
		echo "zfsonlinux.list no such file\n";
		build_progress_idb("{failed_to_install}",110);
		return;
	}
	
	$zfs=$unix->find_program("zfs");
	
	if(!is_file("/sbin/zfs")){
		$aptget=$unix->find_program("apt-get");
		build_progress_idb("{updating_repository}",15);
		$tmpf=$unix->FILE_TEMP();
		$cmd="DEBIAN_FRONTEND=noninteractive $aptget --force-yes update";
		system($cmd);
		build_progress_idb("{install_zfs}/{compiling_kernel}",50);
		$cmd="DEBIAN_FRONTEND=noninteractive $aptget --force-yes -fuy -o Dpkg::Options::=\"--force-confnew\"  install debian-zfs";
		system($cmd);
	}
	
	build_progress_idb("{install_zfs}/{checking}",50);
	if(!is_file("/sbin/zpool")){
		build_progress_idb("{failed_to_install}",110);
		return;
	}
	
	build_progress_idb("{success}",95);
	build_progress_idb("{done}",100);
	
	
}
function download_progress( $download_size, $downloaded_size, $upload_size, $uploaded_size ){
	if(!isset($GLOBALS["previousProgress"])){$GLOBALS["previousProgress"]= 0;}

	if ( $download_size == 0 ){
		$progress = 0;
	}else{
		$progress = round( $downloaded_size * 100 / $download_size );
	}

	if ( $progress > $GLOBALS["previousProgress"]){
			if($progress<95){
				build_progress_idb("{downloading}",$progress);
			}
			$GLOBALS["previousProgress"]=$progress;
			
	}
}


function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin="/opt/influxdb/influxd";
	
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

	if(!is_file($Masterbin)){
		Install();
		if(!is_file($Masterbin)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, influxdb not installed\n";}
			return;
		}
		
	}

	
	
	$pid=PID_NUM();
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	

	@mkdir("/home/artica/squid/InfluxDB",0755,true);

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($SquidPerformance>2){stop(true);}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		return;
	}

	if($SquidPerformance>2){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Perfomance is set to no statistics\n";}
		return;
	}
	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	
	if(!is_dir("/var/log/influxdb")){@mkdir("/var/log/influxdb",0755,true);}
	$influxdb_version=influxdb_version();	
	
	Config($influxdb_version);
	if(preg_match("#^0\.9#", $influxdb_version)){
		$f[]="$Masterbin run -config=/etc/opt/influxdb/influxdb.conf";
		$f[]="-pidfile=/var/run/influxdb.pid";
	}
	if(preg_match("#^0\.8#", $influxdb_version)){
		$f[]="$Masterbin -config=/etc/opt/influxdb/influxdb.conf";
		$f[]="-pidfile=/var/run/influxdb.pid";
	}	
	
	
	
	
	$cmd=@implode(" ", $f) ." >/dev/null 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} v$influxdb_version service\n";}
	
	shell_exec($cmd);
	
	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success v$influxdb_version PID $pid\n";}
		
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
		squid_admin_mysql(0, "Failed to start Statistics Engine",__FILE__,__LINE__);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}

}


function Config($influxdb_version){
	
$sock=new sockets();	
@mkdir("/etc/opt/influxdb",0755,true);
@mkdir("/home/artica/squid/InfluxDB/logs",0755,true);
@mkdir("/home/artica/squid/InfluxDB/raft",0755,true);
@mkdir("/home/artica/squid/InfluxDB/db",0755,true);

if(preg_match("#^0\.8#", $influxdb_version)){
	Config_08();
	return;
}

$InfluxAdminDisabled=intval($sock->GET_INFO("InfluxAdminDisabled"));
$InfluxAdminPort=intval($sock->GET_INFO("InfluxAdminPort"));
if($InfluxAdminPort==0){$InfluxAdminPort=8083;}


$f[]="# # Welcome to the InfluxDB configuration file.";
$f[]="";
$f[]="# If hostname (on the OS) doesn't return a name that can be resolved by the other";
$f[]="# systems in the cluster, you'll have to set the hostname to an IP or something";
$f[]="# that can be resolved here.";
$f[]="# hostname = \"\"";
$f[]="bind-address = \"0.0.0.0\"";
$f[]="";
$f[]="# Once every 24 hours InfluxDB will report anonymous data to m.influxdb.com";
$f[]="# The data includes raft id (random 8 bytes), os, arch and version";
$f[]="# We don't track ip addresses of servers reporting. This is only used";
$f[]="# to track the number of instances running and the versions, which";
$f[]="# is very helpful for us.";
$f[]="# Change this option to true to disable reporting.";
$f[]="reporting-disabled = true";
$f[]="";
$f[]="# Controls settings for initial start-up. Once a node a successfully started,";
$f[]="# these settings are ignored.";
$f[]="[initialization]";
$f[]="join-urls = \"\" # Comma-delimited URLs, in the form http://host:port, for joining another cluster. ";
$f[]="";
$f[]="# Control authentication";
$f[]="# If not set authetication is DISABLED. Be sure to explicitly set this flag to";
$f[]="# true if you want authentication.";
$f[]="[authentication]";
$f[]="enabled = false";
$f[]="";
$f[]="# Configure the admin server";
$f[]="[admin]";
if($InfluxAdminDisabled==0){
	$f[]="enabled = true";
	$f[]="port = $InfluxAdminPort";
}else{
	$f[]="enabled = false";
	$f[]="#port = $InfluxAdminPort";	
}
$f[]="";
$f[]="# Configure the HTTP API endpoint. All time-series data and queries uses this endpoint.";
$f[]="[api]";
$f[]="# ssl-port = 8087    # SSL support is enabled if you set a port and cert";
$f[]="# ssl-cert = \"/path/to/cert.pem\"";
$f[]="";
$f[]="# Configure the Graphite plugins.";
$f[]="[[graphite]] # 1 or more of these sections may be present.";
$f[]="enabled = false";
$f[]="# protocol = \"\" # Set to \"tcp\" or \"udp\"";
$f[]="# address = \"0.0.0.0\" # If not set, is actually set to bind-address.";
$f[]="# port = 2003";
$f[]="# name-position = \"last\"";
$f[]="# name-separator = \"-\"";
$f[]="# database = \"\"  # store graphite data in this database";
$f[]="";
$f[]="# Configure the collectd input.";
$f[]="[collectd]";
$f[]="enabled = false";
$f[]="#address = \"0.0.0.0\" # If not set, is actually set to bind-address.";
$f[]="#port = 25827";
$f[]="#database = \"collectd_database\"";
$f[]="#typesdb = \"types.db\"";
$f[]="";
$f[]="# Input plugin configuration.";
$f[]="[input_plugins]";
$f[]="  # Configure the udp api";
$f[]="  [input_plugins.udp]";
$f[]="  enabled = false";
$f[]="  # port = 4444";
$f[]="  # database = \"\"";
$f[]="";
$f[]="  # Configure multiple udp apis each can write to separate db.  Just";
$f[]="  # repeat the following section to enable multiple udp apis on";
$f[]="  # different ports.";
$f[]="  [[input_plugins.udp_servers]] # array of tables";
$f[]="  enabled = false";
$f[]="  # port = 5551";
$f[]="  # database = \"db1\"";
$f[]="";
$f[]="# Broker configuration. Brokers are nodes which participate in distributed";
$f[]="# consensus.";
$f[]="[broker]";
$f[]="# Where the Raft logs are stored. The user running InfluxDB will need read/write access.";
$f[]="dir  = \"/home/artica/squid/InfluxDB/raft\"";
$f[]="port = 8086";
$f[]="";
$f[]="# Data node configuration. Data nodes are where the time-series data, in the form of";
$f[]="# shards, is stored.";
$f[]="[data]";
$f[]="  dir = \"/home/artica/squid/InfluxDB/db\"";
$f[]="  port = 8086";
$f[]="  retention-check-enabled = true";
$f[]="  retention-check-period = \"10m\"";
$f[]="";
$f[]="[cluster]";
$f[]="";
$f[]="dir = \"/home/artica/squid/InfluxDB/state\"";
$f[]="";
$f[]="[logging]";
$f[]="file   = \"/var/log/influxdb/influxd.log\" # Leave blank to redirect logs to stderr.";
$f[]="";
if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Saving settings in 0.9x mode\n";}
@file_put_contents("/etc/opt/influxdb/influxdb.conf", @implode("\n", $f));
		
}

function Config_08(){
	
	$sock=new sockets();
	$InfluxAdminDisabled=intval($sock->GET_INFO("InfluxAdminDisabled"));
	$InfluxAdminPort=intval($sock->GET_INFO("InfluxAdminPort"));
	if($InfluxAdminPort==0){$InfluxAdminPort=8083;}
	
	$conf[]="# Welcome to the InfluxDB configuration file.";
	$conf[]="";
	$conf[]="# If hostname (on the OS) doesn't return a name that can be resolved by the other";
	$conf[]="# systems in the cluster, you'll have to set the hostname to an IP or something";
	$conf[]="# that can be resolved here.";
	$conf[]="# hostname = \"\"";
	$conf[]="";
	$conf[]="bind-address = \"0.0.0.0\"";
	$conf[]="";
	$conf[]="# Once every 24 hours InfluxDB will report anonymous data to m.influxdb.com";
	$conf[]="# The data includes raft name (random 8 bytes), os, arch and version";
	$conf[]="# We don't track ip addresses of servers reporting. This is only used";
	$conf[]="# to track the number of instances running and the versions which";
	$conf[]="# is very helpful for us.";
	$conf[]="# Change this option to true to disable reporting.";
	$conf[]="reporting-disabled = true";
	$conf[]="";
	$conf[]="[logging]";
	$conf[]="# logging level can be one of \"fine\", \"debug\", \"info\", \"warn\" or \"error\"";
	$conf[]="level  = \"info\"";
	$conf[]="file   = \"/var/log/influxdb/influxd.log\"         # stdout to log to standard out, or syslog facility";
	$conf[]="";
	$conf[]="# Configure the admin server";
	

	
	$conf[]="[admin]";
	if($InfluxAdminDisabled==0){
		$conf[]="port = $InfluxAdminPort";
	}else{
		$conf[]="#port = $InfluxAdminPort";
	}
	$conf[]="";
	$conf[]="# Configure the http api";
	$conf[]="[api]";
	$conf[]="port     = 8086    # binding is disabled if the port isn't set";
	$conf[]="# ssl-port = 8087    # Ssl support is enabled if you set a port and cert";
	$conf[]="# ssl-cert = /path/to/cert.pem";
	$conf[]="";
	$conf[]="# connections will timeout after this amount of time. Ensures that clients that misbehave";
	$conf[]="# and keep alive connections they don't use won't end up connection a million times.";
	$conf[]="# However, if a request is taking longer than this to complete, could be a problem.";
	$conf[]="read-timeout = \"5s\"";
	$conf[]="";
	$conf[]="[input_plugins]";
	$conf[]="";
	$conf[]="  # Configure the graphite api";
	$conf[]="  [input_plugins.graphite]";
	$conf[]="  enabled = false";
	$conf[]="  # address = \"0.0.0.0\" # If not set, is actually set to bind-address.";
	$conf[]="  # port = 2003";
	$conf[]="  # database = \"\"  # store graphite data in this database";
	$conf[]="  # udp_enabled = true # enable udp interface on the same port as the tcp interface";
	$conf[]="";
	$conf[]="  # Configure the collectd api";
	$conf[]="  [input_plugins.collectd]";
	$conf[]="  enabled = false";
	$conf[]="  # address = \"0.0.0.0\" # If not set, is actually set to bind-address.";
	$conf[]="  # port = 25826";
	$conf[]="  # database = \"\"";
	$conf[]="  # types.db can be found in a collectd installation or on github:";
	$conf[]="  # https://github.com/collectd/collectd/blob/master/src/types.db";
	$conf[]="  # typesdb = \"/usr/share/collectd/types.db\" # The path to the collectd types.db file";
	$conf[]="";
	$conf[]="  # Configure the udp api";
	$conf[]="  [input_plugins.udp]";
	$conf[]="  enabled = false";
	$conf[]="  # port = 4444";
	$conf[]="  # database = \"\"";
	$conf[]="";
	$conf[]="  # Configure multiple udp apis each can write to separate db.  Just";
	$conf[]="  # repeat the following section to enable multiple udp apis on";
	$conf[]="  # different ports.";
	$conf[]="  [[input_plugins.udp_servers]] # array of tables";
	$conf[]="  enabled = false";
	$conf[]="  # port = 5551";
	$conf[]="  # database = \"db1\"";
	$conf[]="";
	$conf[]="# Raft configuration";
	$conf[]="[raft]";
	$conf[]="# The raft port should be open between all servers in a cluster.";
	$conf[]="# However, this port shouldn't be accessible from the internet.";
	$conf[]="";
	$conf[]="port = 8090";
	$conf[]="";
	$conf[]="# Where the raft logs are stored. The user running InfluxDB will need read/write access.";
	$conf[]="dir  = \"/home/artica/squid/InfluxDB/raft\"";
	$conf[]="";
	$conf[]="debug = false";
	$conf[]="";
	$conf[]="# election-timeout = \"1s\"";
	$conf[]="";
	$conf[]="[storage]";
	$conf[]="";
	$conf[]="dir = \"/home/artica/squid/InfluxDB/db\"";
	$conf[]="# How many requests to potentially buffer in memory. If the buffer gets filled then writes";
	$conf[]="# will still be logged and once the local storage has caught up (or compacted) the writes";
	$conf[]="# will be replayed from the WAL";
	$conf[]="write-buffer-size = 10000";
	$conf[]="";
	$conf[]="# the engine to use for new shards, old shards will continue to use the same engine";
	$conf[]="default-engine = \"leveldb\"";
	$conf[]="";
	$conf[]="# The default setting on this is 0, which means unlimited. Set this to something if you want to";
	$conf[]="# limit the max number of open files. max-open-files is per shard so this * that will be max.";
	$conf[]="max-open-shards = 0";
	$conf[]="";
	$conf[]="# The default setting is 100. This option tells how many points will be fetched from LevelDb before";
	$conf[]="# they get flushed into backend.";
	$conf[]="point-batch-size = 200";
	$conf[]="";
	$conf[]="# The number of points to batch in memory before writing them to leveldb. Lowering this number will";
	$conf[]="# reduce the memory usage, but will result in slower writes.";
	$conf[]="write-batch-size = 6000000";
	$conf[]="";
	$conf[]="# The server will check this often for shards that have expired that should be cleared.";
	$conf[]="retention-sweep-period = \"10m\"";
	$conf[]="";
	$conf[]="[storage.engines.leveldb]";
	$conf[]="";
	$conf[]="# Maximum mmap open files, this will affect the virtual memory used by";
	$conf[]="# the process";
	$conf[]="max-open-files = 1000";
	$conf[]="";
	$conf[]="# LRU cache size, LRU is used by leveldb to store contents of the";
	$conf[]="# uncompressed sstables. You can use `m` or `g` prefix for megabytes";
	$conf[]="# and gigabytes, respectively.";
	$conf[]="lru-cache-size = \"200m\"";
	$conf[]="";
	$conf[]="[storage.engines.rocksdb]";
	$conf[]="";
	$conf[]="# Maximum mmap open files, this will affect the virtual memory used by";
	$conf[]="# the process";
	$conf[]="max-open-files = 1000";
	$conf[]="";
	$conf[]="# LRU cache size, LRU is used by rocksdb to store contents of the";
	$conf[]="# uncompressed sstables. You can use `m` or `g` prefix for megabytes";
	$conf[]="# and gigabytes, respectively.";
	$conf[]="lru-cache-size = \"200m\"";
	$conf[]="";
	$conf[]="[storage.engines.hyperleveldb]";
	$conf[]="";
	$conf[]="# Maximum mmap open files, this will affect the virtual memory used by";
	$conf[]="# the process";
	$conf[]="max-open-files = 1000";
	$conf[]="";
	$conf[]="# LRU cache size, LRU is used by rocksdb to store contents of the";
	$conf[]="# uncompressed sstables. You can use `m` or `g` prefix for megabytes";
	$conf[]="# and gigabytes, respectively.";
	$conf[]="lru-cache-size = \"200m\"";
	$conf[]="";
	$conf[]="[storage.engines.lmdb]";
	$conf[]="";
	$conf[]="map-size = \"100g\"";
	$conf[]="";
	$conf[]="[cluster]";
	$conf[]="# A comma separated list of servers to seed";
	$conf[]="# this server. this is only relevant when the";
	$conf[]="# server is joining a new cluster. Otherwise";
	$conf[]="# the server will use the list of known servers";
	$conf[]="# prior to shutting down. Any server can be pointed to";
	$conf[]="# as a seed. It will find the Raft leader automatically.";
	$conf[]="";
	$conf[]="# Here's an example. Note that the port on the host is the same as the raft port.";
	$conf[]="# seed-servers = [\"hosta:8090\",\"hostb:8090\"]";
	$conf[]="";
	$conf[]="# Replication happens over a TCP connection with a Protobuf protocol.";
	$conf[]="# This port should be reachable between all servers in a cluster.";
	$conf[]="# However, this port shouldn't be accessible from the internet.";
	$conf[]="";
	$conf[]="protobuf_port = 8099";
	$conf[]="protobuf_timeout = \"2s\" # the write timeout on the protobuf conn any duration parseable by time.ParseDuration";
	$conf[]="protobuf_heartbeat = \"200ms\" # the heartbeat interval between the servers. must be parseable by time.ParseDuration";
	$conf[]="protobuf_min_backoff = \"1s\" # the minimum backoff after a failed heartbeat attempt";
	$conf[]="protobuf_max_backoff = \"10s\" # the maxmimum backoff after a failed heartbeat attempt";
	$conf[]="";
	$conf[]="# How many write requests to potentially buffer in memory per server. If the buffer gets filled then writes";
	$conf[]="# will still be logged and once the server has caught up (or come back online) the writes";
	$conf[]="# will be replayed from the WAL";
	$conf[]="write-buffer-size = 1000";
	$conf[]="";
	$conf[]="# the maximum number of responses to buffer from remote nodes, if the";
	$conf[]="# expected number of responses exceed this number then querying will";
	$conf[]="# happen sequentially and the buffer size will be limited to this";
	$conf[]="# number";
	$conf[]="max-response-buffer-size = 100";
	$conf[]="";
	$conf[]="# When queries get distributed out to shards, they go in parallel. This means that results can get buffered";
	$conf[]="# in memory since results will come in any order, but have to be processed in the correct time order.";
	$conf[]="# Setting this higher will give better performance, but you'll need more memory. Setting this to 1 will ensure";
	$conf[]="# that you don't need to buffer in memory, but you won't get the best performance.";
	$conf[]="concurrent-shard-query-limit = 10";
	$conf[]="";
	$conf[]="[wal]";
	$conf[]="";
	$conf[]="dir   = \"/home/artica/squid/InfluxDB/wal\"";
	$conf[]="flush-after = 1000 # the number of writes after which wal will be flushed, 0 for flushing on every write";
	$conf[]="bookmark-after = 1000 # the number of writes after which a bookmark will be created";
	$conf[]="";
	$conf[]="# the number of writes after which an index entry is created pointing";
	$conf[]="# to the offset of the first request, default to 1k";
	$conf[]="index-after = 1000";
	$conf[]="";
	$conf[]="# the number of requests per one log file, if new requests came in a";
	$conf[]="# new log file will be created";
	$conf[]="requests-per-logfile = 10000";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Saving settings in 0.8x mode\n";}
	@file_put_contents("/etc/opt/influxdb/influxdb.conf", @implode("\n", $conf));
	
}

function InterfaceSize(){
	
	$influx=new influx();
	$sql="select sum(SIZE) as size from MAIN_SIZE group by time(1m) where time > now() - 1h";
	$main=$influx->QUERY_SQL($sql);
	
	var_dump($main);
	
	
	foreach ($main as $row) {
		$time=$row->time;
		$min=date("i",$time);
		$size=$row->size/1024;
		$size=$size/1024;
		$xdata[]=$min;
		$ydata[]=$size;
	}	
	
}


function create_db(){
	$GLOBALS["DEBUG_INFLUX"]=true;
	$GLOBALS["VERBOSE"]=true;
	$influx=new influx();
	
}


function query_influx($sql){
	$GLOBALS["VERBOSE"]=true;
	$GLOBALS["DEBUG_INFLUX_VERBOSE"]=true;
	$influx=new influx();
	
	$influx->ROOT_DUMP_ALL_DATABASES();
	
	
	
	$main=$influx->QUERY_SQL($sql);
	
	foreach ($main as $row) {
		
		echo "TIME:  ".date("Y-m-d H:i:s",$row->time)."\n";
		echo "SIZE:  $row->size\n";
		var_dump($row, $row->time);
	}
	
	echo "today is ".strtotime(date("Y-m-d H:i:s"))."\n";
	
	
}

function PID_NUM(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/influxdb.pid");
	$Masterbin="/opt/influxdb/influxd";
	return $unix->PIDOF($Masterbin);
}

function influxdb_version(){
	if(isset($GLOBALS["influxdb_version"])){return $GLOBALS["influxdb_version"];}
	exec("/opt/influxdb/influxd version 2>&1",$results);
	while (list ($key, $value) = each ($results) ){
		if(preg_match("#InfluxDB v([0-9\-\.a-z]+)#", $value,$re)){
			$GLOBALS["influxdb_version"]=$re[1];
			if($GLOBALS["VERBOSE"]){echo "VERSION: $value...\n";}
			return $GLOBALS["influxdb_version"];
		}
	}
	if($GLOBALS["VERBOSE"]){echo "VERSION: TRY 0.8?\n";}
	exec("/opt/influxdb/influxd -v 2>&1",$results2);
	while (list ($key, $value) = each ($results2) ){
		if(preg_match("#InfluxDB\s+v([0-9\-\.a-z]+)#", $value,$re)){
			$GLOBALS["influxdb_version"]=$re[1];
			if($GLOBALS["VERBOSE"]){echo "VERSION 0.8x: $value...\n";}
			return $GLOBALS["influxdb_version"];
		}
	}
	
}

function InfluxDbSize(){
	$dir="/home/artica/squid/InfluxDB";
	$unix=new unix();
	$size=$unix->DIRSIZE_KO($dir);
	$partition=$unix->DIRPART_INFO($dir);
	
	$TOT=$partition["TOT"];
	$percent=($size/$TOT)*100;
	$percent=round($percent,3);
	
	
	if($GLOBALS["VERBOSE"]){echo "$dir: $size Partition $TOT\n";}
	
	$ARRAY["PERCENTAGE"]=$percent;
	$ARRAY["SIZEKB"]=$size;
	$ARRAY["PART"]=$TOT;
	
	if($GLOBALS["VERBOSE"]){print_r($ARRAY);};
	@unlink("/usr/share/artica-postfix/ressources/logs/web/InfluxDB.state");
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/InfluxDB.state", serialize($ARRAY));
	
}

function clean(){
	$influx=new influx();
	$sock=new sockets();
	$tables["access_log"]=true;
	$InfluxAdminRetentionTime=intval($sock->GET_INFO("InfluxAdminRetentionTime"));
	if($InfluxAdminRetentionTime==0){$InfluxAdminRetentionTime=365;}
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){$InfluxAdminRetentionTime=7;}
	
	$time=strtotime("-$InfluxAdminRetentionTime days");
	$date=date("Y-m-d",$time);
	squid_admin_mysql(1, "[CLEANING] Remove statistics before $date", null,__FILE__,__LINE__);
	
	while (list ($tablename, $value) = each ($tables) ){
		$sql="DELETE FROM $tablename WHERE time < {$time}s";
		//$influx->QUERY_SQL($sql);
		
	}
	
	
}

function remove_db(){
	$unix=new unix();
	$rm=$unix->find_program("rm");
	build_progress_rdb(15,"Remove databases files...");
	shell_exec("$rm -rf /home/artica/squid/InfluxDB");
	@mkdir("/home/artica/squid/InfluxDB",0755,true);
	build_progress_rdb(20,"Stopping service");
	stop(true);
	build_progress_rdb(50,"Starting service");
	start(true);
	shell_exec("$rm -rf /etc/artica-postfix/DIRSIZE_MB_CACHE/*");
	InfluxDbSize();
	system("/etc/init.d/squid-tail restart");
	build_progress_rdb(100,"{done}");
	
	
}


function build_progress_rdb($pourc,$text){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/influxdb.remove.progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}
function build_progress_idb($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/zfs.install.progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}

?>