<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.mount.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
$GLOBALS["SERVICE_NAME"]="Barnyard IDS service";
$GLOBALS["DEBUG"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["LOGFILE"]="/usr/share/artica-postfix/ressources/logs/vmtools.debug";
if(preg_match("#--verbose#",implode(" ",$argv))){
	ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}

	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	$unix=new unix();
	if($unix->process_exists($pid,basename(__FILE__))){events("PID: $pid Already exists....");die();}


if($argv[1]=="--path"){@unlink($GLOBALS["LOGFILE"]);installapt($argv[2]);die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--version"){$GLOBALS["OUTPUT"]=true;echo barnyard2_version();die();}

function build_progress($text,$pourc){
	echo "{$pourc}% $text\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/suricata.install.progress";
	if(is_numeric($text)){
		$array["POURC"]=$text;
		$array["TEXT"]=$pourc;
	}else{
		$array["POURC"]=$pourc;
		$array["TEXT"]=$text;
	}
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}


	
	

function restart($nopid=false){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if(!$nopid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
	}
	@file_put_contents($pidfile, getmypid());
	if($GLOBALS["OUTPUT"]){echo "Restarting....: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Stopping service\n";}
	stop(true);
	if($GLOBALS["OUTPUT"]){echo "Restarting....: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Starting service\n";}
	start(true);
}

function start($nopid=false){
	$unix=new unix();
	$sock=new sockets();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	
	if(!$nopid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
	}

	$pid=barnyard_pid();
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already running since {$time}Mn...\n";}
		return;
	}

	$EnableSuricata=intval($sock->GET_INFO("EnableBarnyard2"));
	if($EnableSuricata==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Disabled ( see EnableBarnyard2 )...\n";}
		return;
	}


	$masterbin=$unix->find_program("barnyard2");
	if(!is_file($masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Not installed...\n";}
		return;
	}

	
	
	$barnyard2_version=barnyard2_version();
	barnyard_config();
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Starting service v{$barnyard2_version}\n";}
	@mkdir("/var/run/suricata",0755,true);
	@mkdir("/var/log/barnyard2",0755,true);
	@mkdir("/var/log/suricata",0755,true);
	
	if(!is_file("/var/log/suricata/suricata.waldo")){
		@touch("/var/log/suricata/suricata.waldo");
	}
	
	if ($handle = opendir("/var/log/suricata")) {
		while (false !== ($fileZ = readdir($handle))) {
			if($fileZ=="."){continue;}
			if($fileZ==".."){continue;}
			$path="/var/log/suricata/$fileZ";;
	
			if(preg_match("#unified2\.alert\.#", $fileZ)){
				if($unix->file_time_min($path)>30){
					if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} remove $path\n";}
					@unlink($path);}
				continue;
			}
				
		}
	}	
	
	
	
	$nohup=$unix->find_program("nohup");
	$s[]="$nohup $masterbin";
	$s[]="-c /etc/suricata/barnyard2.conf";
	$s[]="-d /var/log/suricata/";
	$s[]="-f unified2.alert";
	$s[]="-l /var/log/barnyard2";
	$s[]="-r 1";
	$s[]="-w /var/log/suricata/suricata.waldo";
	$s[]="--pid-path /var/run/suricata --create-pidfile";
	$s[]="-D >/dev/null 2>&1 &";
	
	
	$cmd=@implode(" ", $s);
	
	
	@unlink("/var/run/suricata/barnyard2.pid");
	barnyard_config();
	shell_exec($cmd);

	$c=1;
	for($i=0;$i<10;$i++){
		sleep(1);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Starting service waiting $c/10\n";}
		$pid=barnyard_pid();
		if($unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Success PID $pid\n";}
			break;
		}
		$c++;
	}

	$pid=barnyard_pid();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $cmd\n";}
	}

}
function stop(){
	$unix=new unix();
	$sock=new sockets();
	$masterbin=$unix->find_program("suricata");

	$pid=barnyard_pid();
	if(!is_file($masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Not installed\n";}
		return;

	}

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already stopped...\n";}
		return;
	}

	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=barnyard_pid();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";}
		unix_system_kill($pid);
		sleep(1);
	}
	

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Shutdown pid $pid...\n";}
	$pid=barnyard_pid();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} shutdown - force - pid $pid...\n";}

	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=barnyard_pid();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";}
		unix_system_kill_force($pid);
		sleep(1);
	}

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success stopped...\n";}
		@unlink("/var/run/suricata/suricata.pid");
		return;
	}else{
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} failed...\n";}
	}
}

function barnyard_pid(){
	$unix=new unix();
	$masterbin=$unix->find_program("barnyard2");
	$pid=$unix->get_pid_from_file('/var/run/suricata/barnyard2_NULL1.pid');
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF($masterbin);
}
function barnyard2_version(){
	$unix=new unix();
	if(isset($GLOBALS["barnyard2_version"])){return $GLOBALS["barnyard2_version"];}
	$squidbin=$unix->find_program("barnyard2");
	if(!is_file($squidbin)){return "0.0.0";}
	exec("$squidbin -V 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#Version\s+([0-9\.]+)#", $val,$re)){
			$GLOBALS["barnyard2_version"]=trim($re[1]);
			return $GLOBALS["barnyard2_version"];
		}
	}
}




function barnyard_config(){
	$q=new mysql();
	if(is_dir("/etc/suricata/suricata")){
		$unix=new unix();
		$cp=$unix->find_program("cp");
		$rm=$unix->find_program("rm");
		shell_exec("$cp -rf /etc/suricata/suricata/* /etc/suricata/");
		shell_exec("$rm -rf /etc/suricata/suricata");
		if($GLOBALS["OUTPUT"]){echo "Config........: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Fixing suricata config done...\n";}
	
	}
	
	$f[]="#";
	$f[]="#  Barnyard2 example configuration file";
	$f[]="";
	$f[]="# use UTC for timestamps";
	$f[]="#config utc";
	$f[]="";
	$f[]="# set the appropriate paths to the file(s) your Snort process is using.";
	$f[]="#";
	$f[]="config reference_file:     /etc/suricata/reference.config";
	$f[]="config classification_file:/etc/suricata/classification.config";
	$f[]="config gen_file:           /etc/suricata/rules/gen-msg.map";
	$f[]="config sid_file:           /etc/suricata/rules/sid-msg.map";
	
	$f[]="";
	$f[]="";
	$f[]="# Configure signature suppression at the spooler level see doc/README.sig_suppress";
	$f[]="#config sig_suppress: 1:10";
	$f[]="# Set the event cache size to defined max value before recycling of event occur.";
	$f[]="#config event_cache_size: 4096";
	$f[]="# define dedicated references similar to that of snort.";
	$f[]="#config reference: mybugs http://www.mybugs.com/?s=";
	$f[]="# define explicit classifications similar to that of snort.";
	$f[]="#config classification: shortname, short description, priority";
	$f[]="# set the directory for any output logging";
	$f[]="#config logdir: /tmp";
	$f[]="";
	$f[]="# to ensure that any plugins requiring some level of uniqueness in their output";
	$f[]="# the alert_with_interface_name, interface and hostname directives are provided.";
	$f[]="# An example of usage would be to configure them to the values of the associated";
	$f[]="# snort process whose unified files you are reading.";
	$f[]="#";
	$f[]="# Example:";
	$f[]="#   For a snort process as follows:";
	$f[]="#     snort -i eth0 -c /etc/snort.conf";
	$f[]="#";
	$f[]="#   Typical options would be:";
	$f[]="#     config hostname:  thor";
	$f[]="#     config interface: eth0";
	$f[]="#     config alert_with_interface_name";
	$f[]="#";
	$f[]="#config hostname:   thor";
	$f[]="#config interface:  eth0";
	$f[]="";
	$f[]="# enable printing of the interface name when alerting.";
	$f[]="#";
	$f[]="#config alert_with_interface_name";
	$f[]="";
	$f[]="# at times snort will alert on a packet within a stream and dump that stream to";
	$f[]="# the unified output. barnyard2 can generate output on each packet of that";
	$f[]="# stream or the first packet only.";
	$f[]="#";
	$f[]="#config alert_on_each_packet_in_stream";
	$f[]="";
	$f[]="# enable daemon mode";
	$f[]="#";
	$f[]="#config daemon";
	$f[]="";
	$f[]="# make barnyard2 process chroot to directory after initialisation.";
	$f[]="#";
	$f[]="#config chroot: /var/spool/barnyard2";
	$f[]="";
	$f[]="# specifiy the group or GID for barnyard2 to run as after initialisation.";
	$f[]="#";
	$f[]="#config set_gid: 999";
	$f[]="";
	$f[]="# specifiy the user or UID for barnyard2 to run as after initialisation.";
	$f[]="#";
	$f[]="#config set_uid: 999";
	$f[]="";

	$f[]="";
	$f[]="# enable decoding of the data link (or second level headers).";
	$f[]="#";
	$f[]="#config decode_data_link";
	$f[]="";
	$f[]="# dump the application data";
	$f[]="#";
	$f[]="#config dump_payload";
	$f[]="";
	$f[]="# dump the application data as chars only";
	$f[]="#";
	$f[]="#config dump_chars_only";
	$f[]="";
	$f[]="# enable verbose dumping of payload information in log style output plugins.";
	$f[]="#";
	$f[]="#config dump_payload_verbose";
	$f[]="";
	$f[]="# enable obfuscation of logged IP addresses.";
	$f[]="#";
	$f[]="#config obfuscate";
	$f[]="";
	$f[]="# enable the year being shown in timestamps";
	$f[]="#";
	$f[]="#config show_year";
	$f[]="";
	$f[]="# set the umask for all files created by the barnyard2 process (eg. log files).";
	$f[]="#";
	$f[]="#config umask: 066";
	$f[]="";
	$f[]="# enable verbose logging";
	$f[]="#";
	$f[]="#config verbose";
	$f[]="";
	$f[]="# quiet down some of the output";
	$f[]="#";
	$f[]="#config quiet";
	$f[]="";
	$f[]="config waldo_file: /var/log/suricata/suricata.waldo";
	$f[]="";
	$f[]="# specificy the maximum length of the MPLS label chain";
	$f[]="#";
	$f[]="#config max_mpls_labelchain_len: 64";
	$f[]="";
	$f[]="# specify the protocol (ie ipv4, ipv6, ethernet) that is encapsulated by MPLS.";
	$f[]="#";
	$f[]="#config mpls_payload_type: ipv4";
	$f[]="";
	$f[]="# set the reference network or homenet which is predominantly used by the";
	$f[]="# log_ascii plugin.";
	$f[]="#";
	$f[]="#config reference_net: 192.168.0.0/24";
	$f[]="";
	$f[]="#";
	$f[]="# CONTINOUS MODE";
	$f[]="#";
	$f[]="";
	$f[]="# set the archive directory for use with continous mode";
	$f[]="#";
	$f[]="#config archivedir: /tmp";
	$f[]="";
	$f[]="# when in operating in continous mode, only process new records and ignore any";
	$f[]="# existing unified files";
	$f[]="#";
	$f[]="#config process_new_records_only";
	$f[]="";
	$f[]="";
	$f[]="#";
	$f[]="# Step 2: setup the input plugins";
	$f[]="#";
	$f[]="";
	$f[]="# this is not hard, only unified2 is supported ;)";
	$f[]="input unified2";
	$f[]="";
	$f[]="";
	$f[]="#";
	$f[]="# Step 3: setup the output plugins";
	$f[]="#";
	$f[]="";
	$f[]="# alert_cef";
	$f[]="# ----------------------------------------------------------------------------";
	$f[]="#";
	$f[]="# Purpose:";
	$f[]="#  This output module provides the abilty to output alert information to a";
	$f[]="# remote network host as well as the local host using the open standard";
	$f[]="# Common Event Format (CEF).";
	$f[]="#";
	$f[]="# Arguments: host=hostname[:port], severity facility";
	$f[]="#            arguments should be comma delimited.";
	$f[]="#   host        - specify a remote hostname or IP with optional port number";
	$f[]="#                 this is only specific to WIN32 (and is not yet fully supported)";
	$f[]="#   severity    - as defined in RFC 3164 (eg. LOG_WARN, LOG_INFO)";
	$f[]="#   facility    - as defined in RFC 3164 (eg. LOG_AUTH, LOG_LOCAL0)";
	$f[]="#";
	$f[]="# Examples:";
	$f[]="#   output alert_cef";
	$f[]="#   output alert_cef: host=192.168.10.1";
	$f[]="#   output alert_cef: host=sysserver.com:1001";
	$f[]="#   output alert_cef: LOG_AUTH LOG_INFO";
	$f[]="#";
	$f[]="";
	$f[]="# alert_bro";
	$f[]="# ----------------------------------------------------------------------------";
	$f[]="#";
	$f[]="# Purpose: Send alerts to a Bro-IDS instance.";
	$f[]="#";
	$f[]="# Arguments: hostname:port";
	$f[]="#";
	$f[]="# Examples:";
	$f[]="#   output alert_bro: 127.0.0.1:47757";
	$f[]="";
	$f[]="# alert_fast";
	$f[]="# ----------------------------------------------------------------------------";
	$f[]="# Purpose: Converts data to an approximation of Snort's \"fast alert\" mode.";
	$f[]="#";
	$f[]="# Arguments: file <file>, stdout";
	$f[]="#            arguments should be comma delimited.";
	$f[]="#   file - specifiy alert file";
	$f[]="#   stdout - no alert file, just print to screen";
	$f[]="#";
	$f[]="# Examples:";
	$f[]="#   output alert_fast";
	$f[]="#   output alert_fast: stdout";
	$f[]="#";
	$f[]="output alert_fast: stdout";
	$f[]="";
	$f[]="";
	$f[]="# prelude: log to the Prelude Hybrid IDS system";
	$f[]="# ----------------------------------------------------------------------------";
	$f[]="#";
	$f[]="# Purpose:";
	$f[]="#  This output module provides logging to the Prelude Hybrid IDS system";
	$f[]="#";
	$f[]="# Arguments: profile=snort-profile";
	$f[]="#   snort-profile   - name of the Prelude profile to use (default is snort).";
	$f[]="#";
	$f[]="# Snort priority to IDMEF severity mappings:";
	$f[]="# high < medium < low < info";
	$f[]="#";
	$f[]="# These are the default mapped from classification.config:";
	$f[]="# info   = 4";
	$f[]="# low    = 3";
	$f[]="# medium = 2";
	$f[]="# high   = anything below medium";
	$f[]="#";
	$f[]="# Examples:";
	$f[]="#   output alert_prelude";
	$f[]="#   output alert_prelude: profile=snort-profile-name";
	$f[]="#";
	$f[]="";
	$f[]="";
	$f[]="# alert_syslog";
	$f[]="# ----------------------------------------------------------------------------";
	$f[]="#";
	$f[]="# Purpose:";
	$f[]="#  This output module provides the abilty to output alert information to local syslog";
	$f[]="#";
	$f[]="#   severity    - as defined in RFC 3164 (eg. LOG_WARN, LOG_INFO)";
	$f[]="#   facility    - as defined in RFC 3164 (eg. LOG_AUTH, LOG_LOCAL0)";
	$f[]="#";
	$f[]="# Examples:";
	$f[]="#   output alert_syslog";
	$f[]="#   output alert_syslog: LOG_AUTH LOG_INFO";
	$f[]="#";
	$f[]="";
	$f[]="# syslog_full";
	$f[]="#-------------------------------";
	$f[]="# Available as both a log and alert output plugin.  Used to output data via TCP/UDP or LOCAL ie(syslog())";
	$f[]="# Arguments:";
	$f[]="#      sensor_name \$sensor_name         - unique sensor name";
	$f[]="#      server \$server                   - server the device will report to";
	$f[]="#      local                            - if defined, ignore all remote information and use syslog() to send message.";
	$f[]="#      protocol \$protocol               - protocol device will report over (tcp/udp)";
	$f[]="#      port \$port                       - destination port device will report to (default: 514)";
	$f[]="#      delimiters \$delimiters           - define a character that will delimit message sections ex:  \"|\", will use | as message section delimiters. (default: |)";
	$f[]="#      separators \$separators           - define field separator included in each message ex: \" \" ,  will use space as field separator.             (default: [:space:])";
	$f[]="#      operation_mode \$operaion_mode    - default | complete : default mode is compatible with default snort syslog message, complete prints more information such as the raw packet (hexed)";
	$f[]="#      log_priority   \$log_priority     - used by local option for syslog priority call. (man syslog(3) for supported options) (default: LOG_INFO)";
	$f[]="#      log_facility  \$log_facility      - used by local option for syslog facility call. (man syslog(3) for supported options) (default: LOG_USER)";
	$f[]="#      payload_encoding                 - (default: hex)  support hex/ascii/base64 for log_syslog_full using operation_mode complete only.";
	$f[]="";
	$f[]="# Usage Examples:";
	$f[]="# output alert_syslog_full: sensor_name snortIds1-eth2, server xxx.xxx.xxx.xxx, protocol udp, port 514, operation_mode default";
	$f[]="# output alert_syslog_full: sensor_name snortIds1-eth2, server xxx.xxx.xxx.xxx, protocol udp, port 514, operation_mode complete";
	$f[]="# output log_syslog_full: sensor_name snortIds1-eth2, server xxx.xxx.xxx.xxx, protocol udp, port 514, operation_mode default";
	$f[]="# output log_syslog_full: sensor_name snortIds1-eth2, server xxx.xxx.xxx.xxx, protocol udp, port 514, operation_mode complete";
	$f[]="# output alert_syslog_full: sensor_name snortIds1-eth2, server xxx.xxx.xxx.xxx, protocol udp, port 514";
	$f[]="# output log_syslog_full: sensor_name snortIds1-eth2, server xxx.xxx.xxx.xxx, protocol udp, port 514";
	$f[]="# output alert_syslog_full: sensor_name snortIds1-eth2, local";
	$f[]="# output log_syslog_full: sensor_name snortIds1-eth2, local, log_priority LOG_CRIT,log_facility LOG_CRON";
	$f[]="";
	$f[]="# log_ascii";
	$f[]="# ----------------------------------------------------------------------------";
	$f[]="#";
	$f[]="# Purpose: This output module provides the default packet logging funtionality";
	$f[]="#";
	$f[]="# Arguments: None.";
	$f[]="#";
	$f[]="# Examples:";
	$f[]="#   output log_ascii";
	$f[]="#";
	$f[]="";
	$f[]="";
	$f[]="# log_tcpdump";
	$f[]="# ----------------------------------------------------------------------------";
	$f[]="#";
	$f[]="# Purpose";
	$f[]="#  This output module logs packets in binary tcpdump format";
	$f[]="#";
	$f[]="# Arguments:";
	$f[]="#   The only argument is the output file name.";
	$f[]="#";
	$f[]="# Examples:";
	$f[]="#   output log_tcpdump: tcpdump.log";
	$f[]="#";
	$f[]="";
	$f[]="";

	$mysql_pass=null;
	if($q->mysql_password<>null){
		$mysql_pass=" password=$q->mysql_password ";
	}
	if($q->mysql_server=="localhost"){
		$q->mysql_server="127.0.0.1";
	}
	
	$f[]="output database: log, mysql, user=$q->mysql_admin$mysql_pass dbname=ids host=$q->mysql_server";
	$f[]="output database: alert, mysql, user=$q->mysql_admin$mysql_pass dbname=ids host=$q->mysql_server";
	$f[]="#   output database: alert, postgresql, user=snort dbname=snort";
	$f[]="#   output database: log, odbc, user=snort dbname=snort";
	$f[]="#   output database: log, mssql, dbname=snort user=snort password=test";
	$f[]="#   output database: log, oracle, dbname=snort user=snort password=test";
	$f[]="#";
	$f[]="";
	$f[]="";
	$f[]="# alert_fwsam: allow blocking of IP's through remote services";
	$f[]="# ----------------------------------------------------------------------------";
	$f[]="# output alert_fwsam: <SnortSam Station>:<port>/<key>";
	$f[]="#";
	$f[]="#  <FW Mgmt Station>:  IP address or host name of the host running SnortSam.";
	$f[]="#  <port>:         Port the remote SnortSam service listens on (default 898).";
	$f[]="#  <key>:              Key used for authentication (encryption really)";
	$f[]="#              of the communication to the remote service.";
	$f[]="#";
	$f[]="# Examples:";
	$f[]="#";
	$f[]="# output alert_fwsam: snortsambox/idspassword";
	$f[]="# output alert_fwsam: fw1.domain.tld:898/mykey";
	$f[]="# output alert_fwsam: 192.168.0.1/borderfw  192.168.1.254/wanfw";
	$f[]="#";
	$f[]="";
	
	@file_put_contents("/etc/suricata/barnyard2.conf", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} /etc/suricata/barnyard2.conf done\n";}
	mysql_checks();
}

function mysql_checks(){
	
	$q=new mysql();
	if(!$q->DATABASE_EXISTS("ids")){
		$q->CREATE_DATABASE("ids");
		if(!$q->ok){
			if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} MySQL Error $q->mysql_error\n";}
			return;
		}
		
		if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} creating IDS database\n";}
	}
	
	if(!$q->TABLE_EXISTS("schema", "ids")){
	$q->QUERY_SQL("CREATE TABLE `schema` ( vseq        INT      UNSIGNED NOT NULL,
	ctime       DATETIME NOT NULL,
	PRIMARY KEY (vseq));","ids");
	$q->QUERY_SQL("INSERT INTO `schema`  (vseq, ctime) VALUES ('107', now());","ids");
	if(!$q->ok){
		if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} MySQL Error $q->mysql_error\n";}
		return;
	}
	if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} creating IDS/schema\n";}
	}
	
	
	if(!$q->TABLE_EXISTS("event", "ids")){
		$q->QUERY_SQL("CREATE TABLE event  ( sid 	  INT 	   UNSIGNED NOT NULL,
		cid 	  INT 	   UNSIGNED NOT NULL,
		signature   INT      UNSIGNED NOT NULL,
		timestamp 	   DATETIME NOT NULL,
		PRIMARY KEY (sid,cid),
		INDEX       sig (signature),
		INDEX       time (timestamp));","ids");
		if(!$q->ok){
			if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} MySQL Error $q->mysql_error\n";}
			return;
		}
		if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} creating IDS/event\n";}
	}
	
	
	if(!$q->TABLE_EXISTS("signature", "ids")){
	$q->QUERY_SQL("CREATE TABLE signature ( sig_id       INT          UNSIGNED NOT NULL AUTO_INCREMENT,
	sig_name     VARCHAR(255) NOT NULL,
	sig_class_id INT          UNSIGNED NOT NULL,
	sig_priority INT          UNSIGNED,
	sig_rev      INT          UNSIGNED,
	sig_sid      INT          UNSIGNED,
	sig_gid      INT          UNSIGNED,
	PRIMARY KEY (sig_id),
	INDEX   sign_idx (sig_name(20)),
	INDEX   sig_class_id_idx (sig_class_id));","ids");
	if(!$q->ok){
		if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} MySQL Error $q->mysql_error\n";}
		return;
	}
	if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} creating IDS/signature\n";}
	}
	
	if(!$q->TABLE_EXISTS("sig_reference", "ids")){
		$q->QUERY_SQL("CREATE TABLE sig_reference (sig_id  INT    UNSIGNED NOT NULL,
		ref_seq INT    UNSIGNED NOT NULL,
		ref_id  INT    UNSIGNED NOT NULL,
		PRIMARY KEY(sig_id, ref_seq));","ids");
		if(!$q->ok){
			if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} MySQL Error $q->mysql_error\n";}
			return;
		}
		if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} creating IDS/sig_reference\n";}
	}
	
	if(!$q->TABLE_EXISTS("reference", "ids")){
	$q->QUERY_SQL("CREATE TABLE reference (  ref_id        INT         UNSIGNED NOT NULL AUTO_INCREMENT,
	ref_system_id INT         UNSIGNED NOT NULL,
	ref_tag       TEXT NOT NULL,
	PRIMARY KEY (ref_id));","ids");
	if(!$q->ok){
		if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} MySQL Error $q->mysql_error\n";}
		return;
	}
		if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} creating IDS/reference\n";}
	}
	
	if(!$q->TABLE_EXISTS("reference_system", "ids")){
	$q->QUERY_SQL("CREATE TABLE reference_system ( ref_system_id   INT         UNSIGNED NOT NULL AUTO_INCREMENT,
	ref_system_name VARCHAR(20),
	PRIMARY KEY (ref_system_id));","ids");
		if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} creating IDS/reference_system\n";}
	}
	
	if(!$q->TABLE_EXISTS("sig_class", "ids")){
	$q->QUERY_SQL("CREATE TABLE sig_class ( sig_class_id        INT    UNSIGNED NOT NULL AUTO_INCREMENT,
	sig_class_name      VARCHAR(60) NOT NULL,
	PRIMARY KEY (sig_class_id),
	INDEX       (sig_class_id),
	INDEX       (sig_class_name));","ids");
	if(!$q->ok){
		if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} MySQL Error $q->mysql_error\n";}
		return;
	}
		if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} creating IDS/sig_class\n";}
	}
	
	# store info about the sensor supplying data
	if(!$q->TABLE_EXISTS("sensor", "ids")){
	$q->QUERY_SQL("CREATE TABLE sensor ( sid	  INT 	   UNSIGNED NOT NULL AUTO_INCREMENT,
	hostname    TEXT,
	interface   TEXT,
	filter	  TEXT,
	detail	  TINYINT,
	encoding	  TINYINT,
	last_cid    INT      UNSIGNED NOT NULL,
	PRIMARY KEY (sid));","ids");
	if(!$q->ok){
		if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} MySQL Error $q->mysql_error\n";}
		return;
	}
		if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} creating IDS/sensor\n";}
	}
	
	# All of the fields of an ip header
	if(!$q->TABLE_EXISTS("iphdr", "ids")){
	$q->QUERY_SQL("CREATE TABLE iphdr  ( sid 	  INT 	   UNSIGNED NOT NULL,
	cid 	  INT 	   UNSIGNED NOT NULL,
	ip_src      INT      UNSIGNED NOT NULL,
	ip_dst      INT      UNSIGNED NOT NULL,
	ip_ver      TINYINT  UNSIGNED,
	ip_hlen     TINYINT  UNSIGNED,
	ip_tos  	  TINYINT  UNSIGNED,
	ip_len 	  SMALLINT UNSIGNED,
	ip_id    	  SMALLINT UNSIGNED,
	ip_flags    TINYINT  UNSIGNED,
	ip_off      SMALLINT UNSIGNED,
	ip_ttl   	  TINYINT  UNSIGNED,
	ip_proto 	  TINYINT  UNSIGNED NOT NULL,
	ip_csum 	  SMALLINT UNSIGNED,
	PRIMARY KEY (sid,cid),
	INDEX ip_src (ip_src),
	INDEX ip_dst (ip_dst));","ids");
	if(!$q->ok){
		if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} MySQL Error $q->mysql_error\n";}
		return;
	}
		if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} creating IDS/iphdr\n";}
	}
	
	# All of the fields of a tcp header
	if(!$q->TABLE_EXISTS("tcphdr", "ids")){
	$q->QUERY_SQL("CREATE TABLE tcphdr(  sid 	  INT 	   UNSIGNED NOT NULL,
	cid 	  INT 	   UNSIGNED NOT NULL,
	tcp_sport   SMALLINT UNSIGNED NOT NULL,
	tcp_dport   SMALLINT UNSIGNED NOT NULL,
	tcp_seq     INT      UNSIGNED,
	tcp_ack     INT      UNSIGNED,
	tcp_off     TINYINT  UNSIGNED,
	tcp_res     TINYINT  UNSIGNED,
	tcp_flags   TINYINT  UNSIGNED NOT NULL,
	tcp_win     SMALLINT UNSIGNED,
	tcp_csum    SMALLINT UNSIGNED,
	tcp_urp     SMALLINT UNSIGNED,
	PRIMARY KEY (sid,cid),
	INDEX       tcp_sport (tcp_sport),
	INDEX       tcp_dport (tcp_dport),
	INDEX       tcp_flags (tcp_flags));","ids");
	if(!$q->ok){
		if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} MySQL Error $q->mysql_error\n";}
		return;
	}
		if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} creating IDS/tcphdr\n";}
	}
	
	# All of the fields of a udp header
	if(!$q->TABLE_EXISTS("udphdr", "ids")){
	$q->QUERY_SQL("CREATE TABLE udphdr(  sid 	  INT 	   UNSIGNED NOT NULL,
	cid 	  INT 	   UNSIGNED NOT NULL,
	udp_sport   SMALLINT UNSIGNED NOT NULL,
	udp_dport   SMALLINT UNSIGNED NOT NULL,
	udp_len     SMALLINT UNSIGNED,
	udp_csum    SMALLINT UNSIGNED,
	PRIMARY KEY (sid,cid),
	INDEX       udp_sport (udp_sport),
	INDEX       udp_dport (udp_dport));","ids");
	if(!$q->ok){
		if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} MySQL Error $q->mysql_error\n";}
		return;
	}
		if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} creating IDS/udphdr\n";}
	}
	
	# All of the fields of an icmp header
	if(!$q->TABLE_EXISTS("icmphdr", "ids")){
	$q->QUERY_SQL("CREATE TABLE icmphdr( sid 	  INT 	   UNSIGNED NOT NULL,
	cid 	  INT  	   UNSIGNED NOT NULL,
	icmp_type   TINYINT  UNSIGNED NOT NULL,
	icmp_code   TINYINT  UNSIGNED NOT NULL,
	icmp_csum   SMALLINT UNSIGNED,
	icmp_id     SMALLINT UNSIGNED,
	icmp_seq    SMALLINT UNSIGNED,
	PRIMARY KEY (sid,cid),
	INDEX       icmp_type (icmp_type));","ids");
		if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} creating IDS/icmphdr\n";}
	}
	
	# Protocol options
	if(!$q->TABLE_EXISTS("opt", "ids")){
	$q->QUERY_SQL("CREATE TABLE opt    ( sid         INT      UNSIGNED NOT NULL,
	cid         INT      UNSIGNED NOT NULL,
	optid       INT      UNSIGNED NOT NULL,
	opt_proto   TINYINT  UNSIGNED NOT NULL,
	opt_code    TINYINT  UNSIGNED NOT NULL,
	opt_len     SMALLINT,
	opt_data    TEXT,
	PRIMARY KEY (sid,cid,optid));","ids");
	if(!$q->ok){
		if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} MySQL Error $q->mysql_error\n";}
		return;
	}
		if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} creating IDS/opt\n";}
	}
	
	# Packet payload
	if(!$q->TABLE_EXISTS("data", "ids")){
	$q->QUERY_SQL("CREATE TABLE data   ( sid           INT      UNSIGNED NOT NULL,
	cid           INT      UNSIGNED NOT NULL,
	data_payload  TEXT,
	PRIMARY KEY (sid,cid));","ids");
		if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} creating IDS/data\n";}
	}
	
	# encoding is a lookup table for storing encoding types
	if(!$q->TABLE_EXISTS("encoding", "ids")){
	$q->QUERY_SQL("CREATE TABLE encoding(encoding_type TINYINT UNSIGNED NOT NULL,
	encoding_text TEXT NOT NULL,
	PRIMARY KEY (encoding_type));","ids");
	if(!$q->ok){
		if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} MySQL Error $q->mysql_error\n";}
		return;
	}
	$q->QUERY_SQL("INSERT INTO encoding (encoding_type, encoding_text) VALUES (0, 'hex');","ids");
	if(!$q->ok){
		if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} MySQL Error $q->mysql_error\n";}
		return;
	}
	$q->QUERY_SQL("INSERT INTO encoding (encoding_type, encoding_text) VALUES (1, 'base64');","ids");
	if(!$q->ok){
		if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} MySQL Error $q->mysql_error\n";}
		return;
	}
	$q->QUERY_SQL("INSERT INTO encoding (encoding_type, encoding_text) VALUES (2, 'ascii');","ids");
	if(!$q->ok){
		if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} MySQL Error $q->mysql_error\n";}
		return;
	}
	if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} creating IDS/encoding\n";}
	}

	
	# detail is a lookup table for storing different detail levels
	if(!$q->TABLE_EXISTS("detail", "ids")){
	$q->QUERY_SQL("CREATE TABLE detail  (detail_type TINYINT UNSIGNED NOT NULL,
	detail_text TEXT NOT NULL,
	PRIMARY KEY (detail_type));","ids");
	if(!$q->ok){
		if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} MySQL Error $q->mysql_error\n";}
		return;
	}
	
	$q->QUERY_SQL("INSERT INTO detail (detail_type, detail_text) VALUES (0, 'fast');","ids");
	if(!$q->ok){
		if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} MySQL Error $q->mysql_error\n";}
		return;
	}
	$q->QUERY_SQL("INSERT INTO detail (detail_type, detail_text) VALUES (1, 'full');","ids");
	if(!$q->ok){
		if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} MySQL Error $q->mysql_error\n";}
		return;
	}
	if($GLOBALS["OUTPUT"]){echo "Setting.......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} creating IDS/detail\n";}
	}
	
}








