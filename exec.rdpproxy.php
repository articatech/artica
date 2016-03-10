<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="Remote Desktop Proxy";
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



$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--restart-progress"){$GLOBALS["OUTPUT"]=true;restart_progress();die();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;build();die();}


if($argv[1]=="--authhook-restart"){$GLOBALS["OUTPUT"]=true;AUTHHOOK_RESTART();die();}
if($argv[1]=="--authhook-start"){$GLOBALS["OUTPUT"]=true;AUTHHOOK_START();die();}
if($argv[1]=="--authhook-stop"){$GLOBALS["OUTPUT"]=true;AUTHHOOK_STOP();die();}


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
	build();
	sleep(1);
	start(true);
	
}

function build_progress($pourc,$text){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/squid.rdpproxy.progress", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/web/squid.rdpproxy.progress",0755);
	

}

function restart_progress(){
	
	build_progress(15, "{stopping_service}");
	AUTHHOOK_STOP(true);
	build_progress(20, "{stopping_service}");
	stop(true);
	build_progress(30, "{building_configuration}");
	build();
	build_progress(50, "{starting_service}");
	AUTHHOOK_START(true);
	build_progress(80, "{starting_service}");
	if(!start(true)){
		build_progress(110, "{starting_service} {failed}");
		return;
	}
	build_progress(100, "{starting_service} {success}");
}


function AUTHHOOK_RESTART(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	AUTHHOOK_STOP(true);
	build();
	sleep(1);
	AUTHHOOK_START(true);	
}


function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("rdpproxy");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, rdpproxy not installed\n";}
		return false;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return true;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		return true;
	}
	$EnableRDPProxy=$sock->GET_INFO("EnableRDPProxy");
	if(!is_numeric($EnableRDPProxy)){$EnableRDPProxy=0;}
	

	if($EnableRDPProxy==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableRDPProxy)\n";}
		return false;
	}

	$nohup=$unix->find_program("nohup");
	$kill=$unix->find_program("kill");
	$RDPProxyPort=$sock->GET_INFO("RDPProxyPort");
	if(!is_numeric($RDPProxyPort)){$RDPProxyPort=3389;}
	
	$PIDS=$unix->PIDOF_BY_PORT($RDPProxyPort);
	if(count($PIDS)==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} 0 PID listens $RDPProxyPort...\n";}
	}
	if(count($PIDS)>0){
		while (list ($pid, $b) = each ($PIDS) ){
			if($unix->process_exists($pid)){
				$cmdline=@file_get_contents("/proc/$pid/cmdline");
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} killing PID $pid that listens $RDPProxyPort TCP port\n";}
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Process: `$cmdline`\n";}
				unix_system_kill_force($pid);
			}
		}
		
	}
	

	
	
	@mkdir('/etc/rdpproxy/cert/rdp',0755,true);
	@mkdir("/var/rdpproxy/recorded",0755,true);
	@mkdir("/var/run/redemption",0755,true);
	@mkdir("/tmp/rdpproxy",0755,true);
	@mkdir("/home/rdpproxy/recorded",0755,true);
	
	foreach (glob("/usr/share/artica-postfix/img/rdpproxy/*") as $filename) {
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} \"".basename($filename)."\"\n";}
		@copy($filename, "/usr/local/share/rdpproxy/".basename($filename));
	}
	
	if(is_file("/var/run/redemption/rdpproxy.pid")){@unlink("/var/run/redemption/rdpproxy.pid");}
	$VERSION=VERSION();
	$cmd="$nohup $Masterbin >/dev/null 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service v.$VERSION\n";}
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
		if(!is_file("/var/run/redemption/rdpproxy.pid")){@file_put_contents("/var/run/redemption/rdpproxy.pid", $pid);}
		AUTHHOOK_START(true);
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
	$pid=$unix->get_pid_from_file("/var/run/redemption/rdpproxy.pid");
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("rdpproxy");
	return $unix->PIDOF($Masterbin);
	
}
function VERSION(){
	$unix=new unix();
	$Masterbin=$unix->find_program("rdpproxy");
	exec("$Masterbin --version 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(!preg_match("#Version\s+([0-9\.]+)#i", $ligne,$re)){continue;}
		return $re[1];
	}
}
function AUTHHOOK_PID_NUM(){
	$unix=new unix();
	$pgrep=$unix->find_program("pgrep");
	exec("$pgrep -l -f \"authhook.py\" 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#pgrep#", $ligne)){continue;}
		if(!preg_match("#^([0-9]+)\s+#", $ligne,$re)){continue;}
		return $re[1];	
	}
	
}

function AUTHHOOK_START($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("rdpproxy");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]}, rdpproxy not installed\n";}
		return;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=AUTHHOOK_PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		return;
	}
	$EnableRDPProxy=$sock->GET_INFO("EnableRDPProxy");
	if(!is_numeric($EnableRDPProxy)){$EnableRDPProxy=0;}


	if($EnableRDPProxy==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} service disabled (see EnableRDPProxy)\n";}
		return;
	}

	$nohup=$unix->find_program("nohup");
	$python=$unix->find_program("python");


	

	
	$cmd="$nohup $python /etc/rdpproxy/tools/tools/authhook.py >/tmp/authhook.start 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} service\n";}
	shell_exec($cmd);




	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=AUTHHOOK_PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=AUTHHOOK_PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} Success PID $pid\n";}

	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} $cmd\n";}
	}


}
function AUTHHOOK_STOP($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=AUTHHOOK_PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		return;
	}
	$pid=AUTHHOOK_PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=AUTHHOOK_PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=AUTHHOOK_PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=AUTHHOOK_PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}

}

function build(){
	$sock=new sockets();
	$unix=new unix();
	$RDPProxyListen=$sock->GET_INFO("RDPProxyListen");
	$RDPProxyPort=$sock->GET_INFO("RDPProxyPort");
	if($RDPProxyListen==null){$RDPProxyListen="0.0.0.0";}
	$RDPDisableGroups=$sock->GET_INFO("RDPDisableGroups");
	if(!is_numeric($RDPDisableGroups)){$RDPDisableGroups=1;}
	$q=new mysql_squid_builder();
	if($RDPProxyListen<>"0.0.0.0"){ if(!$unix->IS_IPADDR_EXISTS($RDPProxyListen)){$RDPProxyListen="0.0.0.0";} }
	if(!is_numeric($RDPProxyPort)){$RDPProxyPort=3389;}
	
	$f[]="[globals]";
	$f[]="bitmap_cache=yes";
	$f[]="bitmap_compression=yes";
	$f[]="port=$RDPProxyPort";
	$f[]="authip=127.0.0.1";
	$f[]="authport=3450";
	$f[]="dynamic_conf_path=/tmp/rdpproxy/";
	$f[]="internal_domain=no";
	$f[]="max_tick=30";
	$f[]="enable_file_encryption=no";
	$f[]="listen_address=$RDPProxyListen";
	$f[]="enable_ip_transparent=no";
	$f[]="";
	$f[]="[client]";
	$f[]="ignore_logon_password=no";
	$f[]="performance_flags_default=0x7";
	$f[]="performance_flags_force_present=0";
	$f[]="performance_flags_force_not_present=0";
	$f[]="tls_support=yes";
	$f[]="tls_fallback_legacy=no";
	$f[]="";
	$f[]="# If yes, enable RDP bulk compression in front side.";
	$f[]="rdp_compression=yes";
	$f[]="";
	$f[]="[video]";
	$f[]="l_bitrate=10000";
	$f[]="l_framerate=1";
	$f[]="l_height=480";
	$f[]="l_width=640";
	$f[]="l_qscale=28";
	$f[]="m_bitrate=20000";
	$f[]="m_framerate=1";
	$f[]="m_height=768";
	$f[]="m_width=1024";
	$f[]="m_qscale=14";
	$f[]="h_bitrate=30000";
	$f[]="h_framerate=5";
	$f[]="h_height=2048";
	$f[]="h_width=2048";
	$f[]="h_qscale=7";
	$f[]="replay_path=/tmp/";
	$f[]="capture_flags=15";
	$f[]="png_interval=20   # every 2 seconds";
	$f[]="frame_interval=20 # 5 images per second";
	$f[]="break_interval=60 # one wrm every minute";
	$f[]="";
	$f[]="[mod_rdp]";
	$f[]="# 0 - Cancels connection and reports error.";
	$f[]="# 1 - Replaces existing certificate and continues connection.";
	$f[]="certificate_change_action=1";
	$f[]="";
	$f[]="# If yes, enable RDP bulk compression in mod side.";
	$f[]="rdp_compression=yes";
	$f[]="";
	$f[]="[mod_vnc]";
	$f[]="# Sets the encoding types in which pixel data can be sent by the VNC server.";
	$f[]="# +------------------------+-------------------+";
	$f[]="# | Name                   | Number            |";
	$f[]="# +------------------------+-------------------+";
	$f[]="# | Raw                    | 0                 |";
	$f[]="# +------------------------+-------------------+";
	$f[]="# | CopyRect               | 1                 |";
	$f[]="# +------------------------+-------------------+";
	$f[]="# | RRE                    | 2                 |";
	$f[]="# +------------------------+-------------------+";
	$f[]="# | ZRLE                   | 16                |";
	$f[]="# +------------------------+-------------------+";
	$f[]="# | Cursor pseudo-encoding | -239 (0xFFFFFF11) |";
	$f[]="# +------------------------+-------------------+";
	$f[]="# encodings=2,0,1,-239";
	$f[]="";
	$f[]="[debug]";
	$f[]="front=0";
	$f[]="primary_orders=0";
	$f[]="secondary_orders=0";
	$f[]="session=0";
	$f[]="";
	@file_put_contents("/etc/rdpproxy/rdpproxy.ini", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success rdpproxy.ini\n";}
	$f=array();
	
	$f[]="#!/usr/bin/python";
	$f[]="# -*- coding: UTF-8 -*-";
	$f[]="";
	$f[]="import select";
	$f[]="import httplib, socket";
	$f[]="from socket import error";
	$f[]="from struct    import unpack";
	$f[]="from struct    import pack";
	$f[]="import datetime";
	$f[]="# import base64";
	$f[]="";
	
	$f[]="";
	$f[]="def cut_message(message, width = 75, in_cr = '\\n', out_cr = '<br>', margin = 6):";
	$f[]="    result = []";
	$f[]="    for line in message.split(in_cr):";
	$f[]="        while len(line) > width:";
	$f[]="            end = line[width:].split(' ')";
	$f[]="";
	$f[]="            if len(end[0]) <= margin:";
	$f[]="                result.append((line[:width] + end[0]).rstrip())";
	$f[]="                end = end[1:]";
	$f[]="            else:";
	$f[]="                result.append(line[:width] + end[0][:margin] + '-')";
	$f[]="                end[0] = '-' + end[0][margin:]";
	$f[]="";
	$f[]="            line = ' '.join(end)";
	$f[]="";
	$f[]="        result.append(line.rstrip())";
	$f[]="";
	$f[]="    return out_cr.join(result)";
	$f[]="";
	$f[]="";
	$f[]="MAGICASK = 'UNLIKELYVALUEMAGICASPICONSTANTS3141592926ISUSEDTONOTIFYTHEVALUEMUSTBEASKED'";
	$f[]="";
	$f[]="LOREM_IPSUM0 = \"Message<br>message\"";
	$f[]="LOREM_IPSUM1 = \"Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium <br>doloremque laudantium, totam rem aperiam, eaque<br> ipsa quae ab illo inventore veritatis et<br> quasi architecto beatae vitae dicta sunt explicabo.<br> Nemo enim ipsam voluptatem quia voluptas<br> sit aspernatur aut odit aut fugit, sed quia<br> consequuntur magni dolores eos qui<br> ratione voluptatem sequi nesciunt.<br> Neque porro quisquam est,<br> qui dolorem ipsum quia dolor sit amet,<br> consectetur, adipisci velit, sed<br> quia non numquam eius modi tempora<br> incidunt ut labore et dolore magnam<br> aliquam quaerat voluptatem. Ut enim<br> ad minima veniam, quis nostrum<br> exercitationem ullam corporis suscipit<br> laboriosam, nisi ut aliquid ex ea<br> commodi consequatur? Quis autem<br>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium <br>doloremque laudantium, totam rem aperiam, eaque<br> ipsa quae ab illo inventore veritatis et<br> quasi architecto beatae vitae dicta sunt explicabo.<br>\"";
	$f[]="LOREM_IPSUM2 = \"Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium <br>doloremque laudantium, totam rem aperiam, eaque<br> ipsa quae ab illo inventore veritatis et<br> quasi architecto beatae vitae dicta sunt explicabo.<br> Nemo enim ipsam voluptatem quia voluptas<br> sit aspernatur aut odit aut fugit, sed quia<br> consequuntur magni dolores eos qui<br> ratione voluptatem sequi nesciunt.<br> Neque porro quisquam est,<br> qui dolorem ipsum quia dolor sit amet,<br> consectetur, adipisci velit, sed<br> quia non numquam eius modi tempora<br> incidunt ut labore et dolore magnam<br> aliquam quaerat voluptatem. Ut enim<br> ad minima veniam, quis nostrum<br> exercitationem ullam corporis suscipit<br> laboriosam, nisi ut aliquid ex ea<br> commodi consequatur? Quis autem<br>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium <br>doloremque laudantium, totam rem aperiam, eaque<br> ipsa quae ab illo inventore veritatis et<br> quasi architecto beatae vitae dicta sunt explicabo.<br> Nemo enim ipsam voluptatem quia voluptas<br> sit aspernatur aut odit aut fugit, sed quia<br> consequuntur magni dolores eos qui<br> ratione voluptatem sequi nesciunt.<br> Neque porro quisquam est,<br> qui dolorem ipsum quia dolor sit amet,<br> consectetur, adipisci velit, sed<br> quia non numquam eius modi tempora<br> incidunt ut labore et dolore magnam<br> aliquam quaerat voluptatem. Ut enim<br> ad minima veniam, quis nostrum<br> exercitationem ullam corporis suscipit<br> laboriosam, nisi ut aliquid ex ea<br> commodi consequatur? Quis autem<br>\"";
	$f[]="";
	$f[]="LOREM_IPSUM = cut_message(\"\"\"INFORMATION DESTINEE A L'UTILISATEUR AVANT CONNEXION";
	$f[]="";
	$f[]="Dans un souci de sécurisation de l'accès et de l'utilisation des applications et des bases de données présentent sur le serveur, nous vous informons que l'intégralité de votre session de travail sera enregistrée dés votre clic OK du présent message.";
	$f[]="";
	$f[]="L'enregistrement ainsi réalisé sera archivé pendant un délai de 6 mois à compter de l'ouverture de la présente session.";
	$f[]="";
	$f[]="Conformément à la loi « informatique et libertés », le traitement des données qui sera effectué a fait l'objet d'une déclaration auprès de la CNIL.";
	$f[]="";
	$f[]="En outre, en cliquant sur OK, vous reconnaissez avoir pris connaissance";
	$f[]=" préalablement de la « Charte relative au bon usage des ressources";
	$f[]=" d'information et de communication au sein de la société » notamment disponible sur les panneaux d'affichages réservé à l'information du personnel dans l'entreprise, ou si vous êtes prestataire, à la « Charte d’accès au SI » \"\"\")";
	$f[]="";
	$f[]="class User(object):";
	$f[]="    def __init__(self, name, password, services = None, messages = None, rec_path = None):";
	$f[]="        self.name = name";
	$f[]="        self.password = password";
	$f[]="        if messages is None:";
	$f[]="            self.messages = []";
	$f[]="        self.rec_path = rec_path";
	$f[]="        self.services = services if services else []";
	$f[]="";
	$f[]="    def add(self, service):";
	$f[]="        self.services.append(service)";
	$f[]="";
	$f[]="    def get_service(self, dic):";
	$f[]="        answer = {}";
	$f[]="        if len(self.services) == 1:";
	$f[]="            # Authenticated user only has access to one service, connect to it immediately";
	$f[]="            service = self.services[0]";
	$f[]="            answer['target_device'] = service.device";
	$f[]="            answer['target_login'] = service.login";
	$f[]="            answer['target_password'] = service.password";
	$f[]="            answer['proto_dest'] = service.protocol";
	$f[]="            answer['target_port'] = service.port";
	$f[]="            answer['timeclose'] = str(service.timeclose)";
	$f[]="            answer['is_rec'] = service.is_rec";
	$f[]="            answer['rec_path'] = service.rec_path";
	$f[]="            if service.alternate_shell:";
	$f[]="                answer['alternate_shell'] = service.alternate_shell";
	$f[]="            if service.shell_working_directory:";
	$f[]="                answer['shell_working_directory'] = service.shell_working_directory";
	$f[]="            if service.display_message:";
	$f[]="                answer['display_message'] = service.display_message";
	$f[]="                answer['message'] = service.message";
	$f[]="                answer['target_device'] = 'test_card'";
	$f[]="";
	$f[]="        else:";
	$f[]="            _selector = dic.get('selector')";
	$f[]="            _device = dic.get('target_device')";
	$f[]="            _login = dic.get('target_login')";
	$f[]="            if (_device and _device != MAGICASK and _login and _login != MAGICASK):";
	$f[]="                for service in self.services:";
	$f[]="                    print(\"Testing target %s@%s in %s@%s\" % (_login, _device, service.login, service.device))";
	$f[]="                    if (service.login == _login and service.device == _device):";
	$f[]="                        print(\"Target found %s@%s\" % (_login, _device))";
	$f[]="                        answer['selector'] = 'false'";
	$f[]="                        answer['target_password'] = service.password";
	$f[]="                        answer['proto_dest'] = service.protocol";
	$f[]="                        answer['target_port'] = service.port";
	$f[]="                        answer['timeclose'] = str(service.timeclose)";
	$f[]="                        answer['is_rec'] = service.is_rec";
	$f[]="                        answer['rec_path'] = service.rec_path";
	$f[]="                        if service.alternate_shell:";
	$f[]="                            answer['alternate_shell'] = service.alternate_shell";
	$f[]="                        if service.shell_working_directory:";
	$f[]="                            answer['shell_working_directory'] = service.shell_working_directory";
	$f[]="                        if service.display_message:";
	$f[]="                            answer['display_message'] = service.display_message";
	$f[]="                            answer['message'] = service.message";
	$f[]="                            answer['target_device'] = 'test_card'";
	$f[]="                        break";
	$f[]="                else:";
	$f[]="                    if (_selector == MAGICASK):";
	$f[]="                        self.prepare_selector(answer, dic)";
	$f[]="                    else:";
	$f[]="                        answer['login'] = MAGICASK";
	$f[]="                        answer['password'] = MAGICASK";
	$f[]="                        answer['target_device'] = MAGICASK";
	$f[]="                        answer['target_login'] = MAGICASK";
	$f[]="            else:";
	$f[]="                self.prepare_selector(answer, dic)";
	$f[]="";
	$f[]="        return answer";
	$f[]="";
	$f[]="    def prepare_selector(self, answer, dic):";
	$f[]="        try:";
	$f[]="            _x = dic.get('selector_current_page', '1')";
	$f[]="            if _x.startswith('!'):";
	$f[]="                _x = _x[1:]";
	$f[]="            _current_page = int(_x) - 1";
	$f[]="        except:";
	$f[]="            _current_page = 0";
	$f[]="";
	$f[]="        try:";
	$f[]="            _x = dic.get('selector_lines_per_page', '10')";
	$f[]="            if _x.startswith('!'):";
	$f[]="                _x = _x[1:]";
	$f[]="            _lines_per_page = int(_x)";
	$f[]="        except:";
	$f[]="            _lines_per_page = 10";
	$f[]="";
	$f[]="        _group_filter = dic.get('selector_group_filter', '')";
	$f[]="        if _group_filter.startswith('!'):";
	$f[]="            _group_filter = _group_filter[1:]";
	$f[]="        _device_filter = dic.get('selector_device_filter', '')";
	$f[]="        if _device_filter.startswith('!'):";
	$f[]="            _device_filter = _device_filter[1:]";
	$f[]="        _proto_filter = dic.get('selector_proto_filter', '')";
	$f[]="        if _proto_filter.startswith('!'):";
	$f[]="            _proto_filter = _proto_filter[1:]";
	$f[]="        answer['selector'] = 'true'";
	$f[]="";
	$f[]="        all_services = []";
	$f[]="        all_groups = []";
	$f[]="        all_protos = []";
	$f[]="        all_endtimes = []";
	$f[]="        for service in self.services:";
	$f[]="            target = \"%s@%s\" %(service.login, service.device)";
	$f[]="            if target.find(_device_filter) == -1:";
	$f[]="                continue";
	$f[]="            if service.protocol.lower().find(_group_filter) == -1:";
	$f[]="                continue";
	$f[]="            if service.protocol.find(_proto_filter.upper()) == -1:";
	$f[]="                continue";
	$f[]="            # multiply number of entries by 15 to test pagination";
	$f[]="            all_services.append(target)";
	$f[]="            all_groups.append(service.protocol.lower() + service.protocol.lower() + service.protocol.lower())";
	$f[]="            all_protos.append(service.protocol)";
	$f[]="            all_endtimes.append(service.endtime)";
	$f[]="        _number_of_pages = 1";
	$f[]="        if _lines_per_page != 0:";
	$f[]="            _number_of_pages = 1 + (len(all_protos) - 1) / _lines_per_page";
	$f[]="        if _current_page >= _number_of_pages:";
	$f[]="            _current_page = _number_of_pages - 1";
	$f[]="        if _current_page < 0:";
	$f[]="            _current_page = 0";
	$f[]="        print \"lines per page = \",_lines_per_page";
	$f[]="        _start_of_page = _current_page * _lines_per_page";
	$f[]="        _end_of_page = _start_of_page + _lines_per_page";
	$f[]="        answer['proto_dest'] = \"\x01\".join(all_protos[_start_of_page:_end_of_page])";
	$f[]="        answer['end_time'] = \";\".join(all_endtimes[_start_of_page:_end_of_page])";
	$f[]="        answer['target_login'] = \"\x01\".join(all_groups[_start_of_page:_end_of_page])";
	$f[]="        answer['target_device'] = \"\x01\".join(all_services[_start_of_page:_end_of_page])";
	$f[]="        answer['selector_number_of_pages'] = str(_number_of_pages)";
	$f[]="        answer['selector_current_page'] = _current_page + 1";
	$f[]="";
	$f[]="";
	$f[]="class Service(object):";
	$f[]="    def __init__(self, name, device, login, password, protocol, port, is_rec = 'False', rec_path = '/tmp/testxxx.png', alive=720000, clipboard = 'true', file_encryption = 'true', alternate_shell = '', shell_working_directory = ''):";
	$f[]="        import time";
	$f[]="        import datetime";
	$f[]="        self.name = name";
	$f[]="        self.device = device";
	$f[]="        self.login = login";
	$f[]="        self.password = password";
	$f[]="        self.protocol = protocol";
	$f[]="        self.port = port";
	$f[]="        self.timeclose = int(time.time()+alive)";
	$f[]="        self.endtime = datetime.datetime.strftime(datetime.datetime.fromtimestamp(self.timeclose), \"%Y-%m-%d %H:%M:%S\")";
	$f[]="        self.is_rec = is_rec";
	$f[]="        self.rec_path = rec_path";
	$f[]="        self.display_message = None";
	$f[]="        self.message = None";
	$f[]="        self.clipboard = clipboard";
	$f[]="        self.file_encryption = file_encryption";
	$f[]="        self.alternate_shell = alternate_shell";
	$f[]="        self.shell_working_directory = shell_working_directory";
	$f[]="";
	$f[]="        if self.device == 'display_message':";
	$f[]="            self.display_message = MAGICASK";
	$f[]="            self.message = LOREM_IPSUM";
	$f[]="";
	$f[]="class Authentifier(object):";
	$f[]="    # we should just transmit some kind of salted hash to get something";
	$f[]="    # more secure for password transmission, but for now the authentication";
	$f[]="    # protocol is just supposed to be used locally on a secure system.";
	$f[]="    # It will certainly change to something stronger to avoid storing passwords";
	$f[]="    # at all. Comparing hashes is enough anyway.";
	$f[]="    def __init__(self, sck, users):";
	$f[]="        self.sck = sck";
	$f[]="        self.users = users";
	$f[]="        self.dic = {'login':MAGICASK, 'password':MAGICASK}";
	$f[]="        self.tries = 5";
	$f[]="";
	$f[]="    def read(self):";
	$f[]="        print(\"Reading\")";
	$f[]="        try:";
	$f[]="            _packet_size, = unpack(\">L\", self.sck.recv(4))";
	$f[]="            print(\"Received Data length : %s\" % _packet_size)";
	$f[]="            _data = self.sck.recv(int(_packet_size))";
	$f[]="        except Exception:";
	$f[]="            # It's quick and dirty, but we do as if all possible errors";
	$f[]="            # are authentifier socket was closed.";
	$f[]="            return False";
	$f[]="";
	$f[]="        p = iter(_data.split('\\n'))";
	$f[]="        _data = dict((x, y) for x, y in zip(p, p) if (x[:6] != 'trans_'))";
	$f[]="        for key in _data:";
	$f[]="            print(\"Receiving %s=%s\" % (key, _data[key]))";
	$f[]="            if (_data[key][:3] == 'ASK'):";
	$f[]="                _data[key] = MAGICASK";
	$f[]="            elif (_data[key][:1] == '!'):";
	$f[]="                # BASE64 TRY";
	$f[]="                # _data[key] = base64.b64decode(_data[key][1:])";
	$f[]="                _data[key] = _data[key][1:]";
	$f[]="            else:";
	$f[]="                # BASE64 TRY";
	$f[]="                # _data[key] = base64.b64decode(_data[key])";
	$f[]="                # _data[key] unchanged";
	$f[]="                pass";
	$f[]="        self.dic.update(_data)";
	$f[]="";
	$f[]="        answer = {'authenticated': 'false', 'clipboard' : 'true', 'file_encryption' : 'true', 'trans_cancel' : 'Cancel', 'trans_ok' : 'Ok', 'width' : '1280', 'height' : '1024', 'bpp' : '8'}";
	$f[]="        _login = self.dic.get('login')";
	$f[]="        if _login != MAGICASK:";
	$f[]="            for user in self.users:";
	$f[]="                if user.name == _login:";
	$f[]="                    _password = self.dic.get('password')";
	$f[]="                    if _password and user.password == _password:";
	$f[]="                        answer['authenticated'] = 'true'";
	$f[]="                        print(\"Password OK for user %s\" % user.name)";
	$f[]="                    else:";
	$f[]="                        answer['authenticated'] = 'false'";
	$f[]="                        answer['password'] = MAGICASK";
	$f[]="                        print(\"Wrong Password for user %s\" % user.name)";
	$f[]="                    break";
	$f[]="";
	$f[]="";
	$f[]="        if answer['authenticated'] == 'true':";
	$f[]="            self.tries = 5";
	$f[]="            answer.update(user.get_service(self.dic))";
	$f[]="        else:";
	$f[]="            self.tries = self.tries - 1";
	$f[]="            if self.tries == 0:";
	$f[]="                answer['rejected'] = \"Too many login failures\"";
	$f[]="";
	$f[]="        self.dic.update(answer)";
	$f[]="        self.send()";
	$f[]="        return True";
	$f[]="";
	$f[]="    def send(self):";
	$f[]="        self.dic['keepalive'] = 'true'";
	$f[]="        # BASE64 TRY";
	$f[]="        # _list = [\"%s\\n%s\\n\" % (key, (\"!%s\" % base64.b64encode((\"%s\" % value))) if value != MAGICASK else \"ASK\") for key, value in self.dic.iteritems()]";
	$f[]="        _list = [\"%s\\n%s\\n\" % (key, (\"!%s\" % value) if value != MAGICASK else \"ASK\") for key, value in self.dic.iteritems()]";
	$f[]="";
	$f[]="        for s in _list:";
	$f[]="            print(\"Sending %s=%s\" % tuple(s.split('\\n')[:2]))";
	$f[]="";
	$f[]="        _data = \"\".join(_list)";
	$f[]="        _len = len(_data)";
	$f[]="        print(\"len=\", _len,)";
	$f[]="        self.sck.send(pack(\">L\", _len))";
	$f[]="        self.sck.send(_data)";
	$f[]="";
	$f[]="";
	$f[]="";
	$f[]="server3450 = socket.socket(socket.AF_INET, socket.SOCK_STREAM)";
	$f[]="server3450.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)";
	$f[]="server3450.bind(('127.0.0.1', 3450))";
	$f[]="server3450.listen(5)";
	$f[]="";
	$f[]="servers = [server3450]";
	$f[]="wsockets = []";
	$f[]="manager = {}";
	$f[]="users = [";
	$f[]="";

	if($RDPDisableGroups==1){
		$f[]=Accounts();
	}else{
		$f[]=rdp_groups();
	}


	$f[]="]";
	$f[]="";
	$f[]="while 1:";
	$f[]="    rsockets = servers + manager.keys()";
	$f[]="    rfds, wfds, xfds = select.select(rsockets, [], [], 1)";
	$f[]="";
	$f[]="    for s in rfds:";
	$f[]="        if s in servers:";
	$f[]="            (sck, address) = s.accept()";
	$f[]="            rsockets.append(sck)";
	$f[]="            print(\"Accepting connection\\n\")";
	$f[]="            import os";
	$f[]="#            os.system(\"./replay_last.pl\")";
	$f[]="            manager[sck] = Authentifier(sck, users)";
	$f[]="        else:";
	$f[]="            if not manager[s].read():";
	$f[]="                del manager[s]\n";
		
	@mkdir("/etc/rdpproxy/tools/tools",0755,true);
	@file_put_contents("/etc/rdpproxy/tools/tools/authhook.py", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} Success Authenticator\n";}
	
	
	
}



function rdp_groups(){
	$q=new mysql();
	$sql="SELECT * FROM `rdpproxy_users` ORDER BY `username`";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} $q->mysql_error\n";}}
	
	$COUNTTR=0;
	while ($ligne = mysql_fetch_assoc($results)) {
		$sql="SELECT COUNT(*) as TCOUNT FROM `rdpproxy_items` WHERE userid={$ligne["ID"]}";
		$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));
		$totalC = $ligne2["TCOUNT"];
		if($totalC==0){continue;}
		$password=mysql_escape_string2($ligne["password"]);
		$username=mysql_escape_string2($ligne["username"]);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} building {$ligne["username"]}\n";}
	
		$f[]="\tUser('$username', '$password', [";
		$sql="SELECT * FROM `rdpproxy_items` WHERE userid={$ligne["ID"]} ORDER BY `service`";
		$items = $q->QUERY_SQL($sql);
		$settings=new settings_inc();
		$TR=array();
		while ($item = mysql_fetch_assoc($items)) {
				
			$is_rec_text=null;
			$service=mysql_escape_string2($item["service"]);
			$rhost=mysql_escape_string2($item["rhost"]);
			$username=mysql_escape_string2($item["username"]);
			$password=mysql_escape_string2($item["password"]);
			$servicetype=mysql_escape_string2($item["servicetype"]);
			$serviceport=mysql_escape_string2($item["serviceport"]);
			$alive=$item["alive"];
			if($alive<5){$alive=120;}
			$is_rec=$item["is_rec"];
				
			if($is_rec==1){$is_rec_text=",is_rec = 'True', rec_path='/home/rdpproxy/recorded'";}
			if($username<>null){$username="r'$username'";}else{$username="''";}
			$COUNTTR++;if(!$settings->CORP_LICENSE){if($COUNTTR>50){continue;}}
			$TR[]="Service('$service', '$rhost', $username, '$password', '$servicetype', '$serviceport',alive=$alive$is_rec_text)";
				
		}
	
		$f[]=@implode(",\n", $TR);
		$f[]="\t] ),";
	}	
	
	return @implode("\n", $f);
	
}

function Accounts(){
	$q=new mysql_squid_builder();
	$sql="SELECT COUNT(*) as TCOUNT FROM `rdpproxy_items` WHERE userid=0";
	$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));
	$totalC = $ligne2["TCOUNT"];
		
	$sql="SELECT * FROM `rdpproxy_items` WHERE userid=0 ORDER BY `service`";
	$items = $q->QUERY_SQL($sql);
	if(!$q->ok){echo "$q->mysql_error\n";}
	$settings=new settings_inc();
	$TR=array();
	$COUNTTR=0;
	$b=array();
	while ($item = mysql_fetch_assoc($items)) {
		$is_rec_text=null;
		$service=mysql_escape_string2($item["service"]);
		$rhost=mysql_escape_string2($item["rhost"]);
		$username=mysql_escape_string2($item["username"]);
		$password=mysql_escape_string2($item["password"]);
		$servicetype=mysql_escape_string2($item["servicetype"]);
		$serviceport=mysql_escape_string2($item["serviceport"]);
		$domain=mysql_escape_string2($item["domain"]);
		$alive=$item["alive"];
		if($alive<5){$alive=120;}
		$is_rec=$item["is_rec"];
		$t=array();
		$t[]="\tUser('$username/$service', '$password', [";
	
		if($is_rec==1){$is_rec_text=",is_rec = 'True', rec_path='/home/rdpproxy/recorded'";}
		if($username<>null){
			if($domain<>null){$username="$username@$domain";}
			$username="r'$username'";}else{$username="''";
			
			}
		$COUNTTR++;if(!$settings->CORP_LICENSE){if($COUNTTR>50){continue;}}
		$t[]="\t\tService('$service', '$rhost', $username, '$password', '$servicetype', '$serviceport',alive=$alive$is_rec_text)";
		$t[]="\t] )";
		$b[]=@implode("\n", $t);
	}
	
	
	
	
	
	return @implode(",\n", $b);	
	
}


?>