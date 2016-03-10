<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["STAMP_MAX_RESTART"]="/etc/artica-postfix/SQUID_STAMP_RESTART";

include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
$unix=new unix();
$GLOBALS["TAIL_STARTUP"]=$unix->LOCATE_PHP5_BIN().' /usr/share/artica-postfix/exec.apache-tail.php';
$GLOBALS["log_path"]="/var/log/apache2/common-access.log";
$GLOBALS["PID_TAIL_PATH"]="/etc/artica-postfix/exec.apache-tail.php.pid";


	$GLOBALS["ARGVS"]=implode(" ",$argv);
	if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
	if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
	if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}

	
	
function GETPID(){
	$unix=new unix();
	if(is_file($GLOBALS["PID_TAIL_PATH"])){
		$pid=@file_get_contents($GLOBALS["PID_TAIL_PATH"]);
	}
	if($unix->process_exists($pid)){return $pid;}
	
	
	$pid=$unix->PIDOF_PATTERN("exec.apache-tail.php");
	if($unix->process_exists($pid)){return $pid;}
	$pid=$unix->PIDOF("/bin/apache-tail");
	if($unix->process_exists($pid)){return $pid;}
	
}

function restart(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: access-tail, Already task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	stop(true);
	start(true);
}

function start($aspid=false){
	$GLOBALS["LOGS"]=array();
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$sock=new sockets();
	$reconfigure=false;
	$Enabled=0;
	$EnableNginx=$sock->GET_INFO("EnableNginx");
	$EnableFreeWeb=intval($sock->GET_INFO("EnableFreeWeb"));
	$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableInfluxDB")){@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableInfluxDB", 1);}
	$EnableInfluxDB=intval($sock->GET_INFO("EnableInfluxDB"));

	if($EnableNginx==1){$Enabled=1;}
	if($EnableFreeWeb==1){$Enabled=1;}
	
	if($EnableIntelCeleron==1){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: apache-tail, Intel Celeron is enabled, aborting...\n";}
		$Enabled=0;
	}
	
	
	
	$kill=$unix->find_program("kill");	
	if($Enabled==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: apache-tail, Feature is disabled...\n";}
		return;
	}


	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: apache-tail, Already task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}	

	$pid=GETPID();

	
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: apache-tail, already running since {$time}Mn\n";}
		return;
	}
	$tail=$unix->find_program("tail");
	if(!is_file("/bin/apache-tail")){@copy($tail, "/bin/apache-tail");}
	@chmod("/bin/apache-tail",0755);
	
	$size=@filesize($GLOBALS["log_path"]);
	if($size==0){@unlink($GLOBALS["log_path"]);}
	
	if(!is_file($GLOBALS["log_path"])){
		if(is_file("/var/log/apache2/access-common.log")){
			$GLOBALS["log_path"]="/var/log/apache2/access-common.log";
		}
	}
	
	
	$pid=$unix->PIDOF_PATTERN("/bin/apache-tail -F -n 0 {$GLOBALS["log_path"]}");
	if($unix->process_exists($pid)){
		for($i=0;$i<20;$i++){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: killing old process pid $pid\n";}
			unix_system_kill_force($pid);
			usleep(800);
			$pid=$unix->PIDOF_PATTERN("/bin/apache-tail -F -n 0 {$GLOBALS["log_path"]}");
			if(!$unix->process_exists($pid)){break;}
			unix_system_kill_force($pid);
		}
		
	}
	
	$cmd="/bin/apache-tail --follow=name --retry --max-unchanged-stats=50 -n 0 {$GLOBALS["log_path"]}|{$GLOBALS["TAIL_STARTUP"]} >>/var/log/artica-postfix/squid-logger-start.log 2>&1 &";
	shell_exec($cmd);
	for($i=0;$i<6;$i++){
		$pid=GETPID();
		if($unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: apache-tail, waiting $i/5\n";}
		sleep(1);
	}
	$pid=GETPID();
	if($unix->process_exists($pid)){
		events("apache-tail success to start daemon PID:$pid...");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: apache-tail, success PID:$pid\n";}
	}else{
		events("apache-tail failed to start daemon...");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: apache-tail, failed\n";}
	}
}

function stop($aspid=false){
	
	$unix=new unix();
	$kill=$unix->find_program("kill");
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: apache-tail, Already task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	
	$pid=GETPID();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: apache-tail, already stopped\n";}
		return;
	}
	
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: apache-tail, stopping pid: $pid\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<6;$i++){
		$pid=GETPID();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: apache-tail, waiting pid: $pid $i/5\n";}
		unix_system_kill_force($pid);
		sleep(1);
	}	
	
	$tail=$unix->find_program("tail");
	$pid=$unix->PIDOF("/bin/apache-tail");
	if($unix->process_exists($pid)){
		for($i=0;$i<20;$i++){
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: killing old process pid $pid\n";}
			unix_system_kill_force($pid);
			usleep(800);
			$pid=$unix->PIDOF("/bin/apache-tail");
			if(!$unix->process_exists($pid)){break;}
			unix_system_kill_force($pid);
		}
	
	}	
	
	
	$pid=GETPID();
	if(!$unix->process_exists($pid)){
		events("apache-tail success to stop daemon...");
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: apache-tail, success\n";}
	}else{
		events("apache-tail failed to stop daemon...");
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: apache-tail, failed\n";}
	}	
	
}
function events($text){
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}

	}

	$unix=new unix();
	$unix->events($text,"/var/log/apache.watchdog.log",false,$sourcefunction,$sourceline);
}