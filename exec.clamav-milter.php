<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="Clam Milter daemon";
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
include_once(dirname(__FILE__).'/ressources/class.main_cf.inc');

// Usage: /etc/init.d/clamav-daemon {start|stop|restart|force-reload|reload-log|reload-database|status}

$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();die();}
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
		build_progress_restart("{failed}",110);
		return;
	}
	
	
	
	@file_put_contents($pidfile, getmypid());
	
	build_progress_restart("{stopping_service}",15);
	if(!stop(true)){
		build_progress_restart("{failed}",110);
		return;
	}
	
	build_progress_restart("{reconfiguring}",30);
	build();
	sleep(1);
	build_progress_restart("{starting_service}",30);
	if(!start(true)){
		build_progress_restart("{failed}",110);
		
	}
	build_progress_restart("{reloading} Postfix",90);
	system("/etc/init.d/postfix reload");
	
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $php /usr/share/artica-postfix/exec.freshclam.php --execute >/dev/null 2>&1 &");
	build_progress_restart("{success}",100);
	

}
function build_progress_restart($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/APP_CLAMAV_MILTER.progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}

function reload($aspid=false){
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
	
	
	$ClamavMilterEnabled=intval($sock->GET_INFO("ClamavMilterEnabled"));
	if($ClamavMilterEnabled==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see ClamavMilterEnabled)\n";}
		return false;
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
	$Masterbin=$unix->find_program("clamav-milter");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, clamav-milter not installed\n";}
		return;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return false;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		return true;
	}
	
	$ClamavMilterEnabled=intval($sock->GET_INFO("ClamavMilterEnabled"));
	$MimeDefangClamav=intval($sock->GET_INFO("MimeDefangClamav"));
	$MimeDefangEnabled=intval($sock->GET_INFO("MimeDefangEnabled"));
	if($MimeDefangEnabled==0){$MimeDefangClamav=0;}
	if($MimeDefangClamav==1){$ClamavMilterEnabled=0;}
	
	
	if($ClamavMilterEnabled==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see ClamavMilterEnabled/MimeDefangEnabled/MimeDefangClamav)\n";}
		return false;
	}

	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	build_progress_restart("{starting_service}",31);
	$aa_complain=$unix->find_program('aa-complain');
	if(is_file($aa_complain)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} add clamd Profile to AppArmor..\n";}
		shell_exec("$aa_complain $Masterbin >/dev/null 2>&1");
	}
	
	
	@mkdir("/var/clamav",0755,true);
	@mkdir("/var/run/clamav",0755,true);
	@mkdir("/var/lib/clamav",0755,true);
	@mkdir("/var/log/clamav",0755,true);
	$ClamUser="clamav";
	$squidbin=$unix->LOCATE_SQUID_BIN();
	
	if(is_file($squidbin)){$ClamUser="squid";}
	$unix->chown_func("$ClamUser", "$ClamUser","/var/clamav");
	$unix->chown_func("$ClamUser", "$ClamUser","/var/run/clamav");
	$unix->chown_func("$ClamUser", "$ClamUser","/var/lib/clamav");
	$unix->chown_func("$ClamUser", "$ClamUser","/var/log/clamav");
	build_progress_restart("{starting_service}",32);
	$clamd_version=clamd_version();
	build();
	$cmd="$nohup $Masterbin --config-file=/etc/clamav/clamav-milter.conf >/dev/null 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service version $clamd_version\n";}
	
	build_progress_restart("{starting_service} (clamd) ",33);
	system("/etc/init.d/clamav-daemon start");
	shell_exec($cmd);




	for($i=1;$i<5;$i++){
		build_progress_restart("{starting_service}",35);
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
			build_progress_restart("{starting_service}",40);
			if($unix->is_socket("/var/spool/postfix/var/run/clamav/clamav-milter.ctl")){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Apply permissions on clamav-milter.ctl\n";}
				@chmod("/var/spool/postfix/var/run/clamav/clamav-milter.ctl", 0777);
				break;
			}else{
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting for socket... $i/10 clamav-milter.ctl\n";}
				sleep(1);
			}
		}
		
		if($unix->is_socket("/var/spool/postfix/var/run/clamav/clamav-milter.ctl")){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Apply permissions on clamav-milter.ctl\n";}
			@chmod("/var/spool/postfix/var/run/clamav/clamav-milter.ctl", 0777);
		}else{
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} socket failed\n";}
		}

	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
		build_progress_restart("{starting_service} {failed}",40);
		return;
	}
	
	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){
			echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed..\n";
		}
		build_progress_restart("{starting_service} {failed}",40);
		return;
	}
	if(!$unix->is_socket("/var/spool/postfix/var/run/clamav/clamav-milter.ctl")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} socket Failed..\n";}
	}
	return true;


}

function clamd_version($bin){
	$unix=new unix();
	if(isset($GLOBALS["clammilter_version"])){return $GLOBALS["clammilter_version"];}
	$bin=$unix->find_program("clamav-milter");
	exec("$bin -V 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^clamav-milter\s+([0-9a-z\.]+)#",$line,$re)){continue;}
		$GLOBALS["clammilter_version"]=$re[1];
	}

	return $GLOBALS["clammilter_version"];

}

function PID_NUM(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/spool/postfix/var/run/clamav/clamav-milter.pid");
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("clamav-milter");
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
		return true;
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
		return true;
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
		return false;
	}
return true;
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
	$ClamavTemporaryDirectory2=dirname($ClamavTemporaryDirectory);
	$dirs[]="/var/clamav";
	$dirs[]="/var/run/clamav";
	$dirs[]="/var/lib/clamav";
	$dirs[]="/var/log/clamav";
	$dirs[]=$ClamavTemporaryDirectory;
	$dirs[]="/var/spool/postfix/var/run/clamav";
	
	while (list ($i, $directory) = each ($dirs) ){
		@mkdir($directory,0755,true);
		@chmod($directory, 0755);
		@chown($directory, $ClamUser);
		@chgrp($directory, $ClamUser);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Permissions on $directory\n";}
		$unix->chown_func($ClamUser,$ClamUser, $directory."/*");
	
	}

	
	$f[]="MilterSocket /var/spool/postfix/var/run/clamav/clamav-milter.ctl";
	$f[]="MilterSocketGroup postfix";
	$f[]="MilterSocketMode 777";
	$f[]="FixStaleSocket yes";
	$f[]="User postfix";
	$f[]="AllowSupplementaryGroups yes";
	$f[]="ReadTimeout 300";
	$f[]="Foreground yes";
	$f[]="PidFile /var/spool/postfix/var/run/clamav/clamav-milter.pid";
	$f[]="TemporaryDirectory $ClamavTemporaryDirectory";
	$f[]="ClamdSocket unix:/var/run/clamav/clamav.sock";
	$f[]="";
	$f[]="#LocalNet local";
	$f[]="#LocalNet 192.168.0.0/24";
	$f[]="#LocalNet 1111:2222:3333::/48";
	$f[]="#Whitelist /etc/whitelisted_addresses";
	$f[]="#SkipAuthenticated ^(tom|dick|henry)$";
	$f[]="MaxFileSize {$ClamavMaxFileSize}M";
	$f[]="";
	$f[]="";
	$f[]="##";
	$f[]="## Actions";
	$f[]="##";
	$f[]="";
	$f[]="# The following group of options controls the delievery process under";
	$f[]="# different circumstances.";
	$f[]="# The following actions are available:";
	$f[]="# - Accept";
	$f[]="#   The message is accepted for delievery";
	$f[]="# - Reject";
	$f[]="#   Immediately refuse delievery (a 5xx error is returned to the peer)";
	$f[]="# - Defer";
	$f[]="#   Return a temporary failure message (4xx) to the peer";
	$f[]="# - Blackhole (not available for OnFail)";
	$f[]="#   Like Accept but the message is sent to oblivion";
	$f[]="# - Quarantine (not available for OnFail)";
	$f[]="#   Like Accept but message is quarantined instead of being delivered";
	$f[]="#";
	$f[]="# NOTE: In Sendmail the quarantine queue can be examined via mailq -qQ";
	$f[]="# For Postfix this causes the message to be placed on hold";
	$f[]="# ";
	$f[]="# Action to be performed on clean messages (mostly useful for testing)";
	$f[]="# Default: Accept";
	$f[]="OnClean Accept";
	$f[]="OnInfected Reject";
	$f[]="OnFail Accept";
	$f[]="RejectMsg rejected %v";
	$f[]="AddHeader Add";
	$f[]="#ReportHostname my.mail.server.name";
	$f[]="#VirusAction /usr/local/bin/my_infected_message_handler";
	$f[]="LogSyslog yes";
	$f[]="LogFacility LOG_MAIL";
	$f[]="LogVerbose no";
	$f[]="#LogRotate yes";
	$f[]="#LogInfected Basic";
	$f[]="#LogClean Basic";
	$f[]="#SupportMultipleRecipients yes";
	$f[]="";

	
	@file_put_contents("/etc/clamav/clamav-milter.conf", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/clamav/clamav-milter.conf done\n";}
	$main=new main_cf();
	$postfix_hash=$main->BuildMilters(true);
	$postconf=$unix->find_program("postconf");
	while (list ($key, $value) = each ($postfix_hash) ){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} adding Postfix parameter: $key for $value\n";}
		shell_exec("$postconf -e \"$key=$value\"");
	}
	
}
