<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="Clam AntiVirus userspace daemon";
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

// Usage: /etc/init.d/clamav-daemon {start|stop|restart|force-reload|reload-log|reload-database|status}

$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--reload-database"){$GLOBALS["OUTPUT"]=true;reload_database();die();}
if($argv[1]=="--reload-log"){$GLOBALS["OUTPUT"]=true;reload();die();}
if($argv[1]=="--force-reload"){$GLOBALS["OUTPUT"]=true;reload();die();}


function restart() {
	$unix=new unix();
	$sock=new sockets();
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
	
	$cicap=$unix->find_program("c-icap");
	if(is_file($cicap)){
		$CicapEnabled=intval($sock->GET_INFO("CicapEnabled"));
		if($CicapEnabled==1){system("/etc/init.d/c-icap reload");}
	}
	

}
function reload_database($aspid=false){
$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("clamd");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, clamd not installed\n";}
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
	$kill=$unix->find_program("kill");
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service reloading PID $pid running since {$timepid}Mn...\n";}
		shell_exec("$kill -USR2 $pid");
		return;
	}	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} not running\n";}
	
}
function reload($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("clamd");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, clamd not installed\n";}
		return;
	}
	
	$EnableClamavDaemon=$sock->EnableClamavDaemon();
	if($EnableClamavDaemon==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see sock->EnableClamavDaemon)\n";}
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
	$kill=$unix->find_program("kill");
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service reloading PID $pid running since {$timepid}Mn...\n";}
		unix_system_HUP($pid);
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} not running\n";}

}



function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("clamd");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, clamd not installed\n";}
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
	
	$EnableClamavDaemon=$sock->EnableClamavDaemon();
	
	

	if($EnableClamavDaemon==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see sock->EnableClamavDaemon)\n";}
		return;
	}

	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");

	$aa_complain=$unix->find_program('aa-complain');
	if(is_file($aa_complain)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} add clamd Profile to AppArmor..\n";}
		shell_exec("$aa_complain $Masterbin >/dev/null 2>&1");
	}
	
	
	@mkdir("/var/clamav",0755,true);
	@mkdir("/var/run/clamav",0755,true);
	@mkdir("/var/lib/clamav",0755,true);
	@mkdir("/var/log/clamav",0755,true);
	$ClamUser=$unix->ClamUser();
	
	
	
	$unix->chown_func("$ClamUser", "$ClamUser","/var/clamav");
	$unix->chown_func("$ClamUser", "$ClamUser","/var/run/clamav");
	$unix->chown_func("$ClamUser", "$ClamUser","/var/lib/clamav");
	$unix->chown_func("$ClamUser", "$ClamUser","/var/log/clamav");
	
	$clamd_version=clamd_version();
	build();
	$cmd="$nohup $Masterbin --config-file=/etc/clamav/clamd.conf >/dev/null 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service version $clamd_version\n";}
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
		sleep(1);
		for($i=1;$i<11;$i++){
			
			if($unix->is_socket("/var/run/clamav/clamav.sock")){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Apply permissions on clamav.sock\n";}
				@chmod("/var/run/clamav/clamav.sock", 0777);
				break;
			}else{
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting for socket... $i/10 /var/run/clamav/clamav.sock\n";}
				sleep(1);
			}
		}
		
		if($unix->is_socket("/var/run/clamav/clamav.sock")){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Apply permissions on clamav.sock\n";}
			@chmod("/var/run/clamav/clamav.sock", 0777);
			if(is_file("/usr/bin/c-icap")){
				shell_exec("$nohup /etc/init.d/c-icap reload >/dev/null 2>&1 &");
			}
		}else{
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} socket failed\n";}
		}

	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	}
	
	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed..\n";}}
	if(!$unix->is_socket("/var/run/clamav/clamav.sock")){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} socket Failed..\n";}}


}

function clamd_version(){
	if(isset( $GLOBALS["clamd_version"])){return  $GLOBALS["clamd_version"];}
	$unix=new unix();
	$Masterbin=$unix->find_program("clamd");
	exec("$Masterbin -V 2>&1",$results);
	while (list ($i, $line) = each ($results) ){
		if(preg_match("#ClamAV\s+([0-9\.]+)\/#i", $line,$re)){
			$GLOBALS["clamd_version"]=$re[1];
			return $GLOBALS["clamd_version"];
		}
	}
}

function PID_NUM(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/clamav/clamd.pid");
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("clamd");
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
	$chmod=$unix->find_program("chmod");



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

function build(){
	
	$unix=new unix();
	$sock=new sockets();
	$ClamavStreamMaxLength=$sock->GET_INFO("ClamavStreamMaxLength");
	$ClamavMaxRecursion=$sock->GET_INFO("ClamavMaxRecursion");
	$ClamavMaxFiles=$sock->GET_INFO("ClamavMaxFiles");
	$PhishingScanURLs=$sock->GET_INFO("PhishingScanURLs");
	$ClamavMaxScanSize=$sock->GET_INFO("ClamavMaxScanSize");
	$ClamavMaxFileSize=$sock->GET_INFO("ClamavMaxFileSize");
	$ClamavTemporaryDirectory=$sock->GET_INFO("ClamavTemporaryDirectory");
	if($ClamavTemporaryDirectory==null){$ClamavTemporaryDirectory="/home/clamav";}
	if(!is_numeric($ClamavStreamMaxLength)){$ClamavStreamMaxLength=12;}
	if(!is_numeric($ClamavMaxRecursion)){$ClamavMaxRecursion=5;}
	if(!is_numeric($ClamavMaxFiles)){$ClamavMaxFiles=10000;}
	if(!is_numeric($PhishingScanURLs)){$PhishingScanURLs=1;}
	if(!is_numeric($ClamavMaxScanSize)){$ClamavMaxScanSize=15;}
	if(!is_numeric($ClamavMaxFileSize)){$ClamavMaxFileSize=20;}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} MaxFileSize: {$ClamavMaxFileSize}M\n";}
	
	$ClamUser=$unix->ClamUser();
	
	$dirs[]="/var/clamav";
	$dirs[]="/var/run/clamav";
	$dirs[]="/var/lib/clamav";
	$dirs[]="/var/log/clamav";
	$dirs[]=$ClamavTemporaryDirectory;
	while (list ($i, $directory) = each ($dirs) ){
		@mkdir($directory,0755,true);
		@chmod($directory, 0755);
		@chown($directory, $ClamUser);
		@chgrp($directory, $ClamUser);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Permissions on $directory\n";}
		$unix->chown_func($ClamUser,$ClamUser, $directory."/*");
	
	}
	$ClamavTemporaryDirectory2=dirname($ClamavTemporaryDirectory);
	@chmod($ClamavTemporaryDirectory2,0755);
	
	
	$PhishingScanURLs_text="no";
	if($PhishingScanURLs==1){$PhishingScanURLs_text="yes";}
	$unix->SystemCreateUser("clamav","clamav");
	$f[]="LocalSocket /var/run/clamav/clamav.sock";
	$f[]="FixStaleSocket true";
	$f[]="User $ClamUser";
	$f[]="AllowSupplementaryGroups true";
	$f[]="ScanMail true";
	$f[]="ScanArchive true";
	$f[]="#ArchiveLimitMemoryUsage false (depreciated)";
	$f[]="ArchiveBlockEncrypted false";
	$f[]="MaxDirectoryRecursion 15";
	$f[]="FollowDirectorySymlinks false";
	$f[]="FollowFileSymlinks false";
	$f[]="ReadTimeout 180";
	$f[]="MaxThreads 50";
	$f[]="MaxConnectionQueueLength 15";
	$f[]="StreamMaxLength {$ClamavStreamMaxLength}M";
	$f[]="MaxFileSize {$ClamavMaxFileSize}M";
	$f[]="MaxScanSize {$ClamavMaxFileSize}M";
	$f[]="MaxFiles 10000";
	$f[]="MaxRecursion {$ClamavMaxRecursion}";
	$f[]="LogSyslog true";
	$f[]="LogFacility LOG_LOCAL6";
	$f[]="LogClean false";
	$f[]="LogVerbose false";
	$f[]="PidFile /var/run/clamav/clamd.pid";
	$f[]="TemporaryDirectory $ClamavTemporaryDirectory";
	$f[]="DatabaseDirectory /var/lib/clamav";
	$f[]="SelfCheck 3600";
	$f[]="Foreground false";
	$f[]="Debug false";
	$f[]="ScanPE true";
	$f[]="ScanOLE2 true";
	$f[]="ScanHTML true";
	$f[]="DetectBrokenExecutables false";
	$f[]="#MailFollowURLs false (depreciated)";
	$f[]="ExitOnOOM false";
	$f[]="LeaveTemporaryFiles false";
	$f[]="AlgorithmicDetection true";
	$f[]="ScanELF true";
	$f[]="IdleTimeout 30";
	$f[]="PhishingSignatures true";
	$f[]="PhishingScanURLs $PhishingScanURLs_text";
	$f[]="PhishingAlwaysBlockSSLMismatch false";
	$f[]="PhishingAlwaysBlockCloak false";
	$f[]="DetectPUA false";
	$f[]="ScanPartialMessages false";
	$f[]="HeuristicScanPrecedence false";
	$f[]="StructuredDataDetection false";
	$f[]="LogFile /var/log/clamav/clamd.log";
	$f[]="LogTime true";
	$f[]="LogFileUnlock false";
	$f[]="LogFileMaxSize 0";
	
	
	@file_put_contents("/etc/clamav/clamd.conf", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/clamav/clamd.conf done\n";}
	
	
	
}
