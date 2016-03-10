#!/usr/bin/php -q
<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["PROGRESS"]=true;
$GLOBALS["CLI"]=false;
$GLOBALS["TITLENAME"]="Clam AntiVirus virus database updater";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--progress#",implode(" ",$argv),$re)){$GLOBALS["PROGRESS"]=true;}
if(preg_match("#--cli#",implode(" ",$argv),$re)){$GLOBALS["CLI"]=true;}

$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');

// /etc/clamav/freshclam.conf

$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--reload-database"){$GLOBALS["OUTPUT"]=true;reload_database();die();}
if($argv[1]=="--reload-log"){$GLOBALS["OUTPUT"]=true;reload();die();}
if($argv[1]=="--force-reload"){$GLOBALS["OUTPUT"]=true;reload();die();}
if($argv[1]=="--execute"){$GLOBALS["OUTPUT"]=true;execute();die();}
if($argv[1]=="--exec"){$GLOBALS["OUTPUT"]=false;execute();die();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;build();die();}
if($argv[1]=="--updated"){$GLOBALS["OUTPUT"]=false;notify_updated();die();}
if($argv[1]=="--sigtool-ouput"){$GLOBALS["OUTPUT"]=false;sigtool_output();die();}





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
	build_progress(10, "{stopping} {APP_FRESHCLAM}");
	stop(true);
	build_progress(50, "{building_configuration}");
	build();
	sleep(1);
	build_progress(70, "{starting} {APP_FRESHCLAM}");
	if(start(true)){
		
		if($GLOBALS["PROGRESS"]){
			build_progress(95, "{restarting} {watchdog}");
			system("/etc/init.d/artica-status restart");
		}
		
		build_progress(100, "{done} {APP_FRESHCLAM}");
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

function execute(){
	$unix=new unix();
	@mkdir("/var/clamav",0755,true);
	@mkdir("/var/run/clamav",0755,true);
	@mkdir("/var/lib/clamav",0755,true);
	@mkdir("/var/log/clamav",0755,true);
	
	$ClamUser=$unix->ClamUser();
	
	
	$unix->chown_func("$ClamUser", "$ClamUser","/var/clamav");
	$unix->chown_func("$ClamUser", "$ClamUser","/var/run/clamav");
	$unix->chown_func("$ClamUser", "$ClamUser","/var/lib/clamav");
	$unix->chown_func("$ClamUser", "$ClamUser","/var/log/clamav");	
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/var/run/clamav/scheduled.time";
	
	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		build_progress("Already Executed since {$time}mn",110);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	
	
	
	@file_put_contents($pidfile, getmypid());
	if(!$GLOBALS["FORCE"]){
		$TimEx=$unix->file_time_min($pidTime);
		if($TimEx<120){
			build_progress("Only each 120mn, current is {$TimEx}mn",110);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Only each 120mn, current is {$TimEx}mn\n";}
			return;
		}
	}
	@unlink($pidTime);
	@file_put_contents("$pidTime", time());
	build_progress("{udate_clamav_databases}",10);
	$pid=PID_NUM();
	
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		build_progress("Service already started $pid since {$timepid}Mn",110);
		return;
	}

	$Masterbin=$unix->find_program("freshclam");
	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service freshclam not installed\n";}
		build_progress("Missing freshclam",110);
		return;
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Building settings\n";}
	build_progress("{building_configuration}",20);
	build();
	
	$verbose=null;
	$log="/var/log/clamav/freshclam.log";
	if($GLOBALS["PROGRESS"]){
		$log="/usr/share/artica-postfix/ressources/logs/web/clamav.update.progress.txt";
		$verbose=" --verbose";
	}
	
	$ClamUser=$unix->ClamUser();
	$nohup=$unix->find_program("nohup");
	@chmod("/usr/share/artica-postfix/ressources/logs/web", 0777);
	@chmod($log, 0777);
	
	if(is_file(dirname($Masterbin)."/freshexec")){@unlink(dirname($Masterbin)."/freshexec");}
		@copy($Masterbin, dirname($Masterbin)."/freshexec");
		@chmod(dirname($Masterbin)."/freshexec",0755);
		$Masterbin=dirname($Masterbin)."/freshexec";
		
	$cmd="$nohup $Masterbin --config-file=/etc/clamav/freshclam.conf --pid=/var/run/clamav/freshclam_manu.pid --user=$ClamUser --log=$log$verbose >/dev/null 2>&1 &";
	
	$Dirs=$unix->dirdir("/var/lib/clamav");
	$rm=$unix->find_program("rm");
	
	while (list ($directory, $MAIN) = each ($Dirs) ){
		echo "Checking $directory\n";
		if(!preg_match("#\.tmp$#", $directory)){continue;}
		echo "Remove directory $directory";
		shell_exec("$rm -rf $directory");
	}
	

	build_progress("{udate_clamav_databases}",50);
	echo $cmd;
	system($cmd);
	
	$PID=fresh_clam_manu_pid();
	$WAIT=true;
	
	while ($WAIT) {
		if(!$unix->process_exists($PID)){
			break;
		}
		$ttl=$unix->PROCCESS_TIME_MIN($PID);
		echo "PID: Running $PID since {$ttl}mn\n";
		build_progress("{udate_clamav_databases} {waiting} PID $PID {since} {$ttl}mn",80);
		sleep(2);
		$PID=fresh_clam_manu_pid();
	}
	
	
	
	
	build_progress("{done}",90);
	@unlink("/usr/share/artica-postfix/ressources/interface-cache/ClamAVBases");
	sigtool();
	
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $php /usr/share/artica-postfix/exec.clamav-milter.php --reload >/dev/null &");
	shell_exec("$nohup $php /usr/share/artica-postfix/exec.clamd.php --reload >/dev/null &");
	
	
	build_progress("{done}",100);
	
}

function fresh_clam_manu_pid(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/clamav/freshclam.pid");
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("freshclam");
	$Masterbin=dirname($Masterbin)."/freshexec";
	return $unix->PIDOF_PATTERN(basename($Masterbin).".*?freshclam_manu.pid");

}

function build_progress($text,$pourc){
	$echotext=$text;
	
	if(is_numeric($text)){
		$old=$pourc;
		$pourc=$text;
		$text=$old;
	}
	
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/clamav.update.progress";
	
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	
	$cachefile="/usr/share/artica-postfix/ressources/logs/clamav.freshclam.progress";
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);	
	if($GLOBALS["PROGRESS"]){sleep(1);}

}

function sigtool_output(){
	sigtool();
	$bases=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/interface-cache/ClamAVBases"));

	if(count($bases)==0){
		echo "No database !!!!";
		return;
	}
	while (list ($db, $MAIN) = each ($bases) ){
		$DBS[]=$db;
		$DBS[]="-------------------------------";
		$DBS[]="date: {$MAIN["zDate"]}";
		$DBS[]="version: {$MAIN["version"]}";
		$DBS[]="signatures: {$MAIN["signatures"]}";
		$DBS[]="";
	}
	
	echo @implode("\\n", $DBS);
	

}



function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("freshclam");

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
	
	$EnableFreshClam=$sock->GET_INFO("EnableFreshClam");
	
	
	if(!is_numeric($EnableFreshClam)){$EnableFreshClam=0;}
	
	if($EnableFreshClam==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableFreshClam/EnableClamavDaemon)\n";}
		build_progress(110, "{starting} {APP_FRESHCLAM} {disabled}");
		return;
	}

	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");

	$aa_complain=$unix->find_program('aa-complain');
	if(is_file($aa_complain)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} add $Masterbin Profile to AppArmor..\n";}
		shell_exec("$aa_complain $Masterbin >/dev/null 2>&1");
	}
	
	$ClamUser=$unix->ClamUser();
	@chmod("/usr/share/artica-postfix/ressources/logs/web", 0777);
	
	@mkdir("/var/clamav",0755,true);
	@mkdir("/var/run/clamav",0755,true);
	@mkdir("/var/lib/clamav",0755,true);
	@mkdir("/var/log/clamav",0755,true);
	
	$unix->chown_func("$ClamUser", "$ClamUser","/var/clamav");
	$unix->chown_func("$ClamUser", "$ClamUser","/var/run/clamav");
	$unix->chown_func("$ClamUser", "$ClamUser","/var/lib/clamav");
	$unix->chown_func("$ClamUser", "$ClamUser","/var/log/clamav");
	if(is_file("/var/log/clamav/freshclam.log")){
		$unix->chown_func("$ClamUser", "$ClamUser","/var/log/clamav/freshclam.log");
	}
	build_progress(71, "{starting} {APP_FRESHCLAM}");
	
	build();
	build_progress(72, "{starting} {APP_FRESHCLAM}");
	$cmd="$nohup $Masterbin --daemon  --config-file=/etc/clamav/freshclam.conf --pid=/var/run/clamav/freshclam.pid --user=$ClamUser --log=/var/log/clamav/freshclam.log --on-update-execute=/usr/share/artica-postfix/exec.freshclam.updated.php >/dev/null 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	shell_exec($cmd);

	for($i=1;$i<5;$i++){
		build_progress(72+$i, "{starting} {APP_FRESHCLAM}");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	build_progress(80, "{starting} {APP_FRESHCLAM}");
	$pid=PID_NUM();
	
	
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		return true;

	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
		build_progress(110, "{starting} {APP_FRESHCLAM} {failed}");
		return false;
	}
	
	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed..\n";}
		build_progress(110, "{starting} {APP_FRESHCLAM} {failed}");
	}
	


}

function PID_NUM(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/clamav/freshclam.pid");
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("freshclam");
	return $unix->PIDOF_PATTERN("$Masterbin.*?--on-update-execute=");

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

function notify_updated(){
	
	sigtool();
	$bases=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/interface-cache/ClamAVBases"));
	while (list ($db, $MAIN) = each ($bases) ){
		$DBS[]=$db;
		$DBS[]="-------------------------------";
		$DBS[]="date: {$MAIN["zDate"]}";
		$DBS[]="version: {$MAIN["version"]}";
		$DBS[]="signatures: {$MAIN["signatures"]}";
		$DBS[]="";
	}
	system_admin_mysql(2, "ClamAV pattern databases updated", @implode("\n", $DBS));
}


function build(){
	
	$sock=new sockets();
	$unix=new unix();
	
	$clamdscan=$unix->find_program("clamdscan");
	
	$FreshClamCheckDay=intval($sock->GET_INFO("FreshClamCheckDay"));
	$FreshClamMaxAttempts=intval($sock->GET_INFO("FreshClamMaxAttempts"));
	if($FreshClamCheckDay==0){$FreshClamCheckDay=16;}
	if($FreshClamMaxAttempts==0){$FreshClamMaxAttempts=16;}
	$ClamUser=$unix->ClamUser();
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} clamdscan = $clamdscan\n";}
	
	$f[]="DatabaseOwner $ClamUser";
	$f[]="UpdateLogFile /var/log/clamav/freshclam.log";
	$f[]="LogVerbose false";
	$f[]="LogSyslog true";
	$f[]="LogFacility LOG_LOCAL6";
	$f[]="LogFileMaxSize 0";
	$f[]="LogTime true";
	$f[]="Foreground false";
	$f[]="Debug false";
	$f[]="MaxAttempts $FreshClamMaxAttempts";
	$f[]="DatabaseDirectory /var/lib/clamav";
	
	$f[]="AllowSupplementaryGroups true";
	$f[]="NotifyClamd /etc/clamav/clamd.conf";
	$f[]="PidFile /var/run/clamav/freshclam.pid";
	$f[]="ConnectTimeout 30";
	$f[]="ReceiveTimeout 30";
	$f[]="TestDatabases yes";
	$f[]="ScriptedUpdates yes";
	$f[]="CompressLocalDatabase no";
	$f[]="Bytecode true";
	$f[]="# Check for new database $FreshClamCheckDay times a day";
	$f[]="Checks $FreshClamCheckDay";
	$f[]="DNSDatabaseInfo current.cvd.clamav.net";
	$f[]="DatabaseMirror db.local.clamav.net";
	$f[]="DatabaseMirror database.clamav.net";
	$f[]="OnUpdateExecute ".__FILE__." --updated";
	
	
	$HTTPProxyServer=$unix->GET_HTTP_PROXY_STRING();
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Proxy:$HTTPProxyServer\n";}
	
	
	if($HTTPProxyServer<>null){
		
		if(preg_match("#\/\/(.+?):([0-9]+)#", $HTTPProxyServer,$re)){
			$f[]="HTTPProxyServer {$re[1]}";
			$f[]="HTTPProxyPort {$re[2]}";
		}else{
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Proxy:$HTTPProxyServer no match\n";}
		}
		
		
	}
	
	
	
	
	
	@mkdir("/etc/clamav",0755,true);
	
	$SecuriteInfoCode=$sock->GET_INFO("SecuriteInfoCode");
	$EnableClamavUnofficial=intval($sock->GET_INFO("EnableClamavUnofficial"));
	
	if($SecuriteInfoCode<>null){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Enabled: securiteinfo\n";}
		$f[]="DatabaseCustomURL http://www.securiteinfo.com/get/signatures/$SecuriteInfoCode/securiteinfo.hdb";
		$f[]="DatabaseCustomURL http://www.securiteinfo.com/get/signatures/$SecuriteInfoCode/securiteinfo.ign2";
		$f[]="DatabaseCustomURL http://www.securiteinfo.com/get/signatures/$SecuriteInfoCode/javascript.ndb";
		$f[]="DatabaseCustomURL http://www.securiteinfo.com/get/signatures/$SecuriteInfoCode/spam_marketing.ndb";
		$f[]="DatabaseCustomURL http://www.securiteinfo.com/get/signatures/$SecuriteInfoCode/securiteinfohtml.hdb";
		$f[]="DatabaseCustomURL http://www.securiteinfo.com/get/signatures/$SecuriteInfoCode/securiteinfoascii.hdb";
	
	}
	
	$f[]="";
	$f[]="";
	@file_put_contents("/etc/clamav/freshclam.conf", @implode("\n", $f));
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Unofficial databases:$EnableClamavUnofficial\n";}
	
	if($EnableClamavUnofficial==1){
		if(!is_file("/etc/cron.d/clamav-unofficial-sigs-cron")){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Building /etc/cron.d/clamav-unofficial-sigs-cron\n";}
			$CRON[]="MAILTO=\"\"";
			$CRON[]="45 * * * * root /usr/share/artica-postfix/bin/clamav-unofficial-sigs.sh -c /etc/clamav-unofficial-sigs.conf >/dev/null 2>&1";
			$CRON[]="";
			file_put_contents("/etc/cron.d/clamav-unofficial-sigs-cron",@implode("\n", $CRON));
			$CRON=array();
			chmod("/etc/cron.d/clamav-unofficial-sigs-cron",0640);
			chown("/etc/cron.d/clamav-unofficial-sigs-cron","root");
			system("/etc/init.d/cron reload");
		}
	}else{
		if(is_file("/etc/cron.d/clamav-unofficial-sigs-cron")){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Removing /etc/cron.d/clamav-unofficial-sigs-cron\n";}
			@unlink("/etc/cron.d/clamav-unofficial-sigs-cron");
			system("/etc/init.d/cron reload");
		}
	}
		
	
	
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} freshclam.conf done\n";}

	$unix=new unix();
	$sock=new sockets();
	$CurlProxy=null;
	$squidbin=$unix->LOCATE_SQUID_BIN();
	if(is_file($squidbin)){
		$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
		if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
		if($SQUIDEnable==1){
			$port=$unix->squid_internal_port();
			$CurlProxy="-x 127.0.0.1:$port";
		}
		
	}
	
	if($CurlProxy==null){
		$ini=new Bs_IniHandler();
		$sock=new sockets();
		$datas=$sock->GET_INFO("ArticaProxySettings");
		if(trim($datas)<>null){
			$ini->loadString($datas);
			$ArticaProxyServerEnabled=$ini->_params["PROXY"]["ArticaProxyServerEnabled"];
			$ArticaProxyServerName=$ini->_params["PROXY"]["ArticaProxyServerName"];
			$ArticaProxyServerPort=$ini->_params["PROXY"]["ArticaProxyServerPort"];
			$ArticaProxyServerUsername=trim($ini->_params["PROXY"]["ArticaProxyServerUsername"]);
			$ArticaProxyServerUserPassword=$ini->_params["PROXY"]["ArticaProxyServerUserPassword"];
			if($ArticaProxyServerEnabled==1){$ArticaProxyServerEnabled="yes";}
		}
		
		if($ArticaProxyServerEnabled=="yes"){
			$CurlProxy="-x $ArticaProxyServerName:$ArticaProxyServerPort";
			if($ArticaProxyServerUsername<>null){
				$ArticaProxyServerUserPassword=$unix->shellEscapeChars($ArticaProxyServerUserPassword);
				$CurlProxy=$CurlProxy." -U $ArticaProxyServerUsername:$ArticaProxyServerUserPassword";
			}
			
		}
		
	}
	@mkdir("/var/log/clamav-unofficial-sigs",0755,true);
	@chmod("/usr/share/artica-postfix/exec.freshclam.updated.php", 0755);
	@chmod("/usr/share/artica-postfix/exec.freshclam.sansecurity.updated.php", 0755);
	@chmod("/usr/share/artica-postfix/bin/clamav-unofficial-sigs.sh", 0755);
	$SecuriteInfoCode=$sock->GET_INFO("SecuriteInfoCode");
	$MalwarePatrolCode=$sock->GET_INFO("MalwarePatrolCode");
	$f=array();
	$f[]="# This file contains user configuration settings for clamav-unofficial-sigs.sh";
	$f[]="###################";
	$f[]="# This is property of eXtremeSHOK.com";
	$f[]="# You are free to use, modify and distribute, however you may not remove this notice.";
	$f[]="# Copyright (c) Adrian Jon Kriel :: admin@extremeshok.com";
	$f[]="##################";
	$f[]="#";
	$f[]="# Script updates can be found at: https://github.com/extremeshok/clamav-unofficial-sigs";
	$f[]="# ";
	$f[]="# Originially based on: ";
	$f[]="# Script provide by Bill Landry (unofficialsigs@gmail.com).";
	$f[]="#";
	$f[]="# License: BSD (Berkeley Software Distribution)";
	$f[]="#";
	$f[]="##################";
	$f[]="#";
	$f[]="# NOT COMPATIBLE WITH VERSION 3.XX CONFIG ";
	$f[]="#";
	$f[]="################################################################################";
	$f[]="";
	$f[]="# Edit the quoted variables below to meet your own particular needs";
	$f[]="# and requirements, but do not remove the \"quote\" marks.";
	$f[]="";
	$f[]="# Set the appropriate ClamD user and group accounts for your system.";
	$f[]="# If you do not want the script to set user and group permissions on";
	$f[]="# files and directories, comment the next two variables.";
	$f[]="clam_user=\"$ClamUser\"";
	$f[]="clam_group=\"$ClamUser\"";
	$f[]="clam_dbs=\"/var/lib/clamav\"";
	$f[]="clamd_pid=\"/var/run/clamav/clamd.pid\"";
	$f[]="#reload_dbs=\"yes\"";
	$f[]="#reload_opt=\"$clamdscan --reload\"  # Default";

	$f[]="# owner: read, write";
	$f[]="# group: read";
	$f[]="# world: read";
	$f[]="#";
	$f[]="# as defined in the \"clam_dbs\" path variable below, then set the following";
	$f[]="# \"setmode\" variable to \"no\".";
	$f[]="setmode=\"yes\"";
	$f[]="";
	$f[]="# Set path to ClamAV database files location.  If unsure, check";
	$f[]="# your clamd.conf file for the \"DatabaseDirectory\" path setting.";
	$f[]="clam_dbs=\"/var/lib/clamav\"";
	$f[]="";
	$f[]="# Set path to clamd.pid file (see clamd.conf for path location).";
	$f[]="clamd_pid=\"/var/run/clamav/clamd.pid\"";
	$f[]="#clamd_pid=\"/var/run/clamd.pid\"";
	$f[]="";
	$f[]="# To enable \"ham\" (non-spam) directory scanning and removal of";
	$f[]="# signatures that trigger on ham messages, uncomment the following";
	$f[]="# variable and set it to the appropriate ham message directory.";
	$f[]="#ham_dir=\"/var/lib/clamav-unofficial-sigs/ham-test\"";
	$f[]="";
	$f[]="# If you would like to reload the clamd databases after an update,";
	$f[]="# change the following variable to \"yes\".";
	$f[]="reload_dbs=\"yes\"";
	$f[]="";
	$f[]="# Top level working directory, script will attempt to create them.";
	$f[]="work_dir=\"/var/lib/clamav-unofficial-sigs\"   #Top level working directory";
	$f[]="";
	$f[]="# Log update information to '\$log_file_path/\$log_file_name'.";
	$f[]="enable_logging=\"yes\"";
	$f[]="log_file_path=\"/var/log/clamav-unofficial-sigs\"";
	$f[]="log_file_name=\"clamav-unofficial-sigs.log\"";
	$f[]="";
	$f[]="";
	$f[]="# =========================";
	$f[]="# MalwarePatrol : https://www.malwarepatrol.net";
	$f[]="# MalwarePatrol 2015 free clamav signatures";
	$f[]="#";
	$f[]="# 1. Sign up for a free account : https://www.malwarepatrol.net/signup-free.shtml";
	$f[]="# 2. You will recieve an email containing your password/receipt number";
	$f[]="# 3. Enter the receipt number into the config: replacing YOUR-RECEIPT-NUMBER with your receipt number from the email";
	$f[]="";
	$f[]="malwarepatrol_receipt_code=\"$MalwarePatrolCode\"";
	$f[]="# Set to no to enable the commercial subscription url.";
	$f[]="malwarepatrol_free=\"yes\"";
	$f[]="";
	$f[]="# =========================";
	$f[]="# SecuriteInfo : https://www.SecuriteInfo.com";
	$f[]="# SecuriteInfo 2015 free clamav signatures";
	$f[]="#";
	$f[]="#Usage of SecuriteInfo 2015 free clamav signatures : https://www.securiteinfo.com";
	$f[]="# - 1. Sign up for a free account : https://www.securiteinfo.com/clients/customers/signup";
	$f[]="# - 2. You will recieve an email to activate your account and then a followup email with your login name";
	$f[]="# - 3. Login and navigate to your customer account : https://www.securiteinfo.com/clients/customers/account";
	$f[]="# - 4. Click on the Setup tab";
	$f[]="# - 5. You will need to get your unique identifier from one of the download links, they are individual for every user";
	$f[]="# - 5.1. The 128 character string is after the http://www.securiteinfo.com/get/signatures/ ";
	$f[]="# - 5.2. Example https://www.securiteinfo.com/get/signatures/your_unique_and_very_long_random_string_of_characters/securiteinfo.hdb";
	$f[]="#   Your 128 character authorisation signature would be : your_unique_and_very_long_random_string_of_characters";
	$f[]="# - 6. Enter the authorisation signature into the config securiteinfo_authorisation_signature: replacing YOUR-SIGNATURE-NUMBER with your authorisation signature from the link";
	$f[]="";
	$f[]="securiteinfo_authorisation_signature=\"$SecuriteInfoCode\"";
	$f[]="";
	$f[]="# ========================";
	$f[]="# Database provider update time";
	$f[]="# ========================";
	$f[]="# Since the database files are dynamically created, non default values can cause banning, change with caution";
	$f[]="";
	$f[]="securiteinfo_update_hours=\"4\"   # Default is 4 hours (6 downloads daily).";
	$f[]="linuxmalwaredetect_update_hours=\"6\"   # Default is 6 hours (4 downloads daily).";
	$f[]="malwarepatrol_update_hours=\"24\"   # Default is 24 hours (1 downloads daily).";
	$f[]="yararules_update_hours=\"24\"   # Default is 24 hours (1 downloads daily).";
	$f[]="";
	$f[]="# ========================";
	$f[]="# Enabled Databases";
	$f[]="# ========================";
	$f[]="# Set to no to disable an entire database.";
	if($SecuriteInfoCode<>null){
		$f[]="securiteinfo_enabled=\"yes\"   # SecuriteInfo ";
	}else{
		$f[]="securiteinfo_enabled=\"no\"   # SecuriteInfo ";
	}
	
	$f[]="sanesecurity_enabled=\"yes\"   # Sanesecurity";
	
	$f[]="linuxmalwaredetect_enabled=\"yes\"   # Linux Malware Detect";
	if($MalwarePatrolCode<>null){
	$f[]="malwarepatrol_enabled=\"yes\"   # Malware Patrol";
	}else{
		$f[]="malwarepatrol_enabled=\"no\"   # Malware Patrol";
	}
	$f[]="yararules_enabled=\"no\"   # Yara-Rule Project, requires clamAV 0.99+";
	$f[]="";
	$f[]="# ========================";
	$f[]="# Sanesecurity Database(s)";
	$f[]="# ========================";
	$f[]="# Add or remove database file names between quote marks as needed.  To";
	$f[]="# disable usage of any of the Sanesecurity distributed database files";
	$f[]="# shown, remove the database file name from the quoted section below.";
	$f[]="# Only databases defined as \"low\" risk have been enabled by default ";
	$f[]="# for additional information about the database ratings, see: ";
	$f[]="# http://www.sanesecurity.com/clamav/databases.htm";
	$f[]="# Only add signature databases here that are \"distributed\" by Sanesecuirty";
	$f[]="# as defined at the URL shown above.  Database distributed by others sources";
	$f[]="# (e.g., SecuriteInfo & MalewarePatrol, can be added to other sections of";
	$f[]="# this config file below).  Finally, make sure that the database names are";
	$f[]="# spelled correctly or you will experience issues when the script runs";
	$f[]="# (hint: all rsync servers will fail to download signature updates).";
	$f[]="";
	$f[]="sanesecurity_dbs=\" # BEGIN SANESECURITY DATABASE";
	$f[]="### SANESECURITY http://sanesecurity.com/usage/signatures/";
	$f[]="## REQUIRED, Do NOT disable";
	$f[]="sanesecurity.ftm  #REQUIRED Message file types, for best performance";
	$f[]="sigwhitelist.ign2 #REQUIRED Fast update file to whitelist any problem signatures";
	$f[]="## LOW";
	$f[]="junk.ndb  #LOW  General high hitting junk, containing spam/phishing/lottery/jobs/419s etc ";
	$f[]="jurlbl.ndb #LOW Junk Url based";
	$f[]="phish.ndb #LOW Phishing";
	$f[]="rogue.hdb  #LOW Malware, Rogue anti-virus software and Fake codecs etc.  Updated hourly to cover the latest malware threats  ";
	$f[]="scam.ndb #LOW Spam/scams  ";
	$f[]="spamimg.hdb #LOW Spam images ";
	$f[]="spamattach.hdb #LOW Spam Spammed attachments such as pdf/doc/rtf/zip ";
	$f[]="blurl.ndb  #LOW Blacklisted full urls over the last 7 days, covering malware/spam/phishing. URLs added only when main signatures have failed to detect but are known to be \"bad\"  ";
	$f[]="## MED";
	$f[]="spear.ndb  #MED Spear phishing email addresses (autogenerated from data here)";
	$f[]="lott.ndb  #MED Lottery  ";
	$f[]="spam.ldb  #MED Spam detected using the new Logical Signature type";
	$f[]="spearl.ndb  #MED Spear phishing urls (autogenerated from data here)   ";
	$f[]="jurlbla.ndb #MED Junk Url based autogenerated from various feeds";
	$f[]="badmacro.ndb #MED Detect dangerous macros";
	$f[]="";
	$f[]="### FOXHOLE http://sanesecurity.com/foxhole-databases/";
	$f[]="## LOW";
	$f[]="malwarehash.hsb  #LOW Malware hashes without known Size";
	$f[]="## MED";
	$f[]="#foxhole_generic.cdb #MED See Foxhole page for more details";
	$f[]="#foxhole_filename.cdb #MED See Foxhole page for more details";
	$f[]="## HIGH";
	$f[]="#foxhole_all.cdb  #HIGH See Foxhole page for more details  ";
	$f[]="";
	$f[]="### OITC http://www.oitc.com/winnow/clamsigs/index.html";
	$f[]="### Note: the two databases winnow_phish_complete.ndb and winnow_phish_complete_url.ndb should NOT be used together.  ";
	$f[]="# LOW";
	$f[]="winnow.attachments.hdb  #LOW Spammed attachments such as pdf/doc/rtf/zip";
	$f[]="winnow_malware.hdb  #LOW Current virus, trojan and other malware not yet detected by ClamAV.";
	$f[]="winnow_malware_links.ndb #LOW Links to malware";
	$f[]="winnow_extended_malware.hdb  #LOW contain hand generated signatures for malware ";
	$f[]="winnow_bad_cw.hdb #LOW md5 hashes of malware attachments acquired directly from a group of botnets";
	$f[]="# MED";
	$f[]="#winnow_phish_complete_url.ndb #Med Similar to winnow_phish_complete.ndb except that entire urls are used  ";
	$f[]="#winnow.complex.patterns.ldb  #MED contain hand generated signatures for malware and some egregious fraud  ";
	$f[]="#winnow_extended_malware_links.ndb #MED contain hand generated signatures for malware links ";
	$f[]="#winnow_spam_complete.ndb  #MED Signatures to detect fraud and other malicious spam";
	$f[]="# HIGH";
	$f[]="#winnow_phish_complete.ndb #HIGH Phishing and other malicious urls and compromised hosts **DO NOT USE WITH winnow_phish_complete_url**";
	$f[]="";
	$f[]="### SCAMNAILER http://www.scamnailer.info/";
	$f[]="# MED";
	$f[]="#scamnailer.ndb  #MED Spear phishing and other phishing emails";
	$f[]="";
	$f[]="### BOFHLAND http://clamav.bofhland.org/";
	$f[]="# LOW";
	$f[]="bofhland_cracked_URL.ndb  #LOW Spam URLs  ";
	$f[]="bofhland_malware_URL.ndb  #LOW Malware URLs ";
	$f[]="bofhland_phishing_URL.ndb #LOW Phishing URLs";
	$f[]="bofhland_malware_attach.hdb #LOW Malware Hashes";
	$f[]="";
	$f[]="###  RockSecurity http://rooksecurity.com/";
	$f[]="#LOW";
	$f[]="hackingteam.hsb #LOW Hacking Team hashes";
	$f[]="";
	$f[]="### CRDF https://threatcenter.crdf.fr/";
	$f[]="# LOW";
	$f[]="crdfam.clamav.hdb #LOW List of new threats detected by CRDF Anti Malware  ";
	$f[]="";
	$f[]="### Porcupine";
	$f[]="# LOW";
	$f[]="porcupine.ndb  #LOW Brazilian e-mail phishing and malware signatures ";
	$f[]="phishtank.ndb  #LOW Online and valid phishing urls from phishtank.com data feed ";
	$f[]="";
	$f[]="### Sanesecurity YARA Format rules";
	$f[]="### Note: Yara signatures require ClamAV 0.99 or newer to work";
	$f[]="#Sanesecurity_sigtest.yara #LOW Sanesecurity test signatures ";
	$f[]="#Sanesecurity_spam.yara #LOW detect spam ";
	$f[]="";
	$f[]="\" # END SANESECURITY DATABASES";
	$f[]="";
	$f[]="# ========================";
	$f[]="# SecuriteInfo Database(s)";
	$f[]="# ========================";
	$f[]="# Only active when you set your securiteinfo_authorisation_signature";
	$f[]="# Add or remove database file names between quote marks as needed.  To";
	$f[]="# disable any SecuriteInfo database downloads, remove the appropriate";
	$f[]="# lines below.";
	$f[]="securiteinfo_dbs=\"";
	$f[]="### Securiteinfo https://www.securiteinfo.com/services/improve-detection-rate-of-zero-day-malwares-for-clamav.shtml";
	$f[]="## REQUIRED, Do NOT disable";
	$f[]="securiteinfo.ign2";
	$f[]="# LOW";
	$f[]="securiteinfo.hdb #LOW Malwares in the Wild";
	$f[]="javascript.ndb  #LOW Malwares Javascript ";
	$f[]="securiteinfohtml.hdb  #LOW Malwares HTML ";
	$f[]="securiteinfoascii.hdb  #LOW Text file malwares (Perl or shell scripts, bat files, exploits, ...)";
	$f[]="securiteinfopdf.hdb #LOW Malwares PDF ";
	$f[]="# HIGH";
	$f[]="#spam_marketing.ndb #HIGH Spam Marketing /  spammer blacklist";
	$f[]="\" #END SECURITEINFO DATABASES";
	$f[]="";
	$f[]="# ========================";
	$f[]="# Linux Malware Detect Database(s)";
	$f[]="# ========================";
	$f[]="# Add or remove database file names between quote marks as needed.  To";
	$f[]="# disable any SecuriteInfo database downloads, remove the appropriate";
	$f[]="# lines below.";
	$f[]="linuxmalwaredetect_dbs=\"";
	$f[]="### Linux Malware Detect https://www.rfxn.com/projects/linux-malware-detect/";
	$f[]="# LOW";
	$f[]="rfxn.ndb #LOW HEX Malware detection signatures";
	$f[]="rfxn.hdb  #LOW MD5 malware detection signatures";
	$f[]="\" #END LINUXMALWAREDETECT DATABASES";
	$f[]="";
	$f[]="# =========================";
	$f[]="# MalwarePatrol Database ";
	$f[]="# =========================";
	$f[]="# Only active when you set your malwarepatrol_receipt_code";
	$f[]="## REQUIRED, Do NOT disable";
	$f[]="malwarepatrol_db=\"malwarepatrol.db\" #LOW URLs containing of Viruses, Trojans, Worms, or Malware  ";
	$f[]="";
	$f[]="# ========================";
	$f[]="# Yara Rules Project Database(s)";
	$f[]="# ========================";
	$f[]="# Add or remove database file names between quote marks as needed.  To";
	$f[]="# disable any Yara Rule database downloads, remove the appropriate";
	$f[]="# lines below.";
	$f[]="yararules_dbs=\"";
	$f[]="### Yara Rules https://github.com/Yara-Rules/rules";
	$f[]="# LOW";
	$f[]="antidebug.yar #LOW anti debug and anti virtualization techniques used by malware ";
	$f[]="malicious_document.yar #LOW documents with malicious code";
	$f[]="# MED";
	$f[]="#packer.yar #MED well-known sofware packers";
	$f[]="# HIGH";
	$f[]="#crypto.yar #HIGH detect the existence of cryptographic algoritms";
	$f[]="\" #END YARARULES DATABASES";
	$f[]="";
	$f[]="";
	$f[]="# =========================";
	$f[]="# Additional signature databases";
	$f[]="# =========================";
	$f[]="# Additional signature databases can be specified here in the following";
	$f[]="# format: PROTOCOL://URL-or-IP/PATH/TO/FILE-NAME (use a trailing \"/\" in";
	$f[]="# place of the \"FILE-NAME\" to download all files from specified location,";
	$f[]="# but this *ONLY* works for files downloaded via rsync).  For non-rsync";
	$f[]="# downloads, curl is used.  For download protocols supported by curl, see";
	$f[]="# \"man curl\".  This also works well for locations that have many ClamAV";
	$f[]="# servers that use 3rd party signature databases, as only one server need";
	$f[]="# download the remote databases, and all others can update from the local";
	$f[]="# mirrors copy.  See format examples below.  To use, remove the comments";
	$f[]="# and examples shown and add your own sites between the quote marks.";
	$f[]="#add_dbs=\"";
	$f[]="#   rsync://192.168.1.50/new-db/sigs.hdb";
	$f[]="#   rsync://rsync.example.com/all-dbs/";
	$f[]="#   ftp://ftp.example.net/pub/sigs.ndb";
	$f[]="#   http://www.example.org/sigs.ldb";
	$f[]="#\" #END ADDITIONAL DATABASES";
	$f[]="";
	$f[]="";
	$f[]="";
	$f[]="";
	$f[]="# ==================================================";
	$f[]="# ==================================================";
	$f[]="# A D V A N C E D   O P T I O N S";
	$f[]="# ==================================================";
	$f[]="# ==================================================";
	$f[]="";
	$f[]="# Enable or disable download time randomization.  This allows the script to";
	$f[]="# be executed via cron, but the actual database file checking will pause";
	$f[]="# for a random number of seconds between the \"min\" and \"max\" time settings";
	$f[]="# specified below.  This helps to more evenly distribute load on the host";
	$f[]="# download sites.  To disable, set the following variable to \"no\".";
	$f[]="enable_random=\"yes\"";
	$f[]="";
	$f[]="# If download time randomization is enabled above (enable_random=\"yes\"),";
	$f[]="# then set the min and max radomization time intervals (in seconds).";
	$f[]="min_sleep_time=\"60\"    # Default minimum is 60 seconds (1 minute).";
	$f[]="max_sleep_time=\"600\"   # Default maximum is 600 seconds (10 minutes).";
	$f[]="";
	$f[]="# Set the clamd_restart_opt if the \"reload_dbs\" variable above is set";
	$f[]="# Command to do a full clamd service stop/start";
	$f[]="clamd_restart_opt=\"/etc/init.d/clamd restart\"";
	$f[]="";
	$f[]="# If running clamd in \"LocalSocket\" mode (*NOT* in TCP/IP mode), and";
	$f[]="# either \"SOcket Cat\" (socat) or the \"IO::Socket::UNIX\" perl module";
	$f[]="# are installed on the system, and you want to report whether clamd";
	$f[]="# is running or not, uncomment the \"clamd_socket\" variable below (you";
	$f[]="# will be warned if neither socat nor IO::Socket::UNIX are found, but";
	$f[]="# the script will still run).  You will also need to set the correct";
	$f[]="# path to your clamd socket file (if unsure of the path, check the";
	$f[]="# \"LocalSocket\" setting in your clamd.conf file for socket location).";
	$f[]="#clamd_socket=\"/tmp/clamd.socket\"";
	$f[]="#clamd_socket=\"/var/run/clamd.socket\"";
	$f[]="";
	$f[]="# If you would like to attempt to restart ClamD if detected not running,";
	$f[]="# uncomment the next 2 lines.  Enter the clamd service stop and  start command";
	$f[]="# for your particular distro for the \"start_clamd\" \"stop_clamd\" variables";
	$f[]="# (the sample start command shown below should work for most linux distros).";
	$f[]="# NOTE: these 2 variables are dependant on the \"clamd_socket\" variable";
	$f[]="# shown above - if not enabled, then the following 2 variables will be";
	$f[]="# ignored, whether enabled or not.";
	$f[]="#clamd_start=\"service clamd start\"";
	$f[]="#clamd_stop=\"service clamd stop\"";
	$f[]="";
	$f[]="# Set rsync connection and data transfer timeout limits in seconds.";
	$f[]="# The defaults settings here are reasonable, only change if you are";
	$f[]="# experiencing timeout issues.";
	$f[]="rsync_connect_timeout=\"30\"";
	$f[]="rsync_max_time=\"90\"";
	$f[]="";
	$f[]="# Set curl connection and data transfer timeout limits in seconds.";
	$f[]="# The defaults settings here are reasonable, only change if you are";
	$f[]="# experiencing timeout issues.";
	$f[]="curl_connect_timeout=\"30\"";
	$f[]="curl_max_time=\"90\"";
	$f[]="";
	$f[]="# Set working directory paths (edit to meet your own needs). If these";
	$f[]="# directories do not exist, the script will attempt to create them.";
	$f[]="# Sub-directory names:";
	$f[]="sanesecurity_dir=\"\$work_dir/dbs-ss\"        # Sanesecurity sub-directory";
	$f[]="securiteinfo_dir=\"\$work_dir/dbs-si\"        # SecuriteInfo sub-directory ";
	$f[]="linuxmalwaredetect_dir=\"\$work_dir/dbs-lmd\"      # Linux Malware Detect sub-directory ";
	$f[]="malwarepatrol_dir=\"\$work_dir/dbs-mbl\"      # MalwarePatrol sub-directory ";
	$f[]="yararules_dir=\"\$work_dir/dbs-yara\"      # Yara-Rules sub-directory ";
	$f[]="config_dir=\"\$work_dir/configs\"   # Script configs sub-directory";
	$f[]="gpg_dir=\"\$work_dir/gpg-key\"      # Sanesecurity GPG Key sub-directory";
	$f[]="add_dir=\"\$work_dir/dbs-add\"      # User defined databases sub-directory";
	$f[]="";
	$f[]="# If you would like to make a backup copy of the current running database";
	$f[]="# file before updating, leave the following variable set to \"yes\" and a";
	$f[]="# backup copy of the file will be created in the production directory";
	$f[]="# with -bak appended to the file name.";
	$f[]="keep_db_backup=\"no\"";
	$f[]="";
	$f[]="# If you want to silence the information reported by curl, rsync, gpg";
	$f[]="# or the general script comments, change the following variables to";
	$f[]="# \"yes\".  If all variables are set to \"yes\", the script will output";
	$f[]="# nothing except error conditions.";
	$f[]="silence_ssl=\"yes\" # Default is \"yes\" ignore ssl errors and warnings";
	$f[]="curl_silence=\"no\"      # Default is \"no\" to report curl statistics";
	$f[]="rsync_silence=\"no\"     # Default is \"no\" to report rsync statistics";
	$f[]="gpg_silence=\"no\"       # Default is \"no\" to report gpg signature status";
	$f[]="comment_silence=\"no\"   # Default is \"no\" to report script comments";
	$f[]="";
	$f[]="# If necessary to proxy database downloads, define the rsync and/or curl";
	$f[]="# proxy settings here.  For rsync, the proxy must support connections to";
	$f[]="# port 873.  Both curl and rsync proxy setting need to be defined in the";
	$f[]="# format of \"hostname:port\".  For curl, also note the -x and -U flags,";
	$f[]="# which must be set as \"-x hostname:port\" and \"-U username:password\".";
	$f[]="rsync_proxy=\"\"";
	$f[]="curl_proxy=\"$CurlProxy\"";
	$f[]="user_configuration_complete=\"yes\"";
	$f[]="";
	$f[]="# ========================";
	$f[]="# Database provider URLs, do not edit.";
	$f[]="sanesecurity_url=\"rsync.sanesecurity.net\"";
	$f[]="sanesecurity_gpg_url=\"http://www.sanesecurity.net/publickey.gpg\"";
	$f[]="securiteinfo_url=\"https://www.securiteinfo.com/get/signatures/\"";
	$f[]="linuxmalwaredetect_url=\"http://cdn.rfxn.com/downloads/\"";
	$f[]="malwarepatrol_free_url=\"https://lists.malwarepatrol.net/cgi/getfile?product=8&list=clamav_basic\"";
	$f[]="malwarepatrol_subscription_url=\"https://lists.malwarepatrol.net/cgi/getfile?product=15&list=clamav_basic\"";
	$f[]="yararules_url=\"https://raw.githubusercontent.com/Yara-Rules/rules/master/\"";
	$f[]="";
	$f[]="# ========================";
	$f[]="# do not edit";
	$f[]="config_version=\"53\"";
	$f[]="";	
	
	@file_put_contents("/etc/clamav-unofficial-sigs.conf", @implode("\n", $f)); 
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} clamav-unofficial-sigs.conf done\n";}
	$f=array();
}

function sigtool(){
		$unix=new unix();
		$sigtool=$unix->find_program("sigtool");
		if(strlen($sigtool)<5){return;;}
		if(is_file("/usr/share/artica-postfix/ressources/interface-cache/ClamAVBases")){
			$ttim=$unix->file_time_min("/usr/share/artica-postfix/ressources/interface-cache/ClamAVBases");
			if($ttim<30){return;}
		}
	
		$baseDir="/var/lib/clamav";
	
		$patnz=$unix->DirFiles($baseDir,"\.(cvd|cld|hdb|ign2|ndb)$");
	
		while (list ($path, $none) = each ($patnz) ){
			$patterns[basename($path)]=true;
		}
	
		while (list ($pattern, $none) = each ($patterns) ){
			if(!is_file("$baseDir/$pattern")){continue;}
			$results=array();
			exec("$sigtool --info=$baseDir/$pattern 2>&1",$results);
			while (list ($index, $line) = each ($results) ){
	
				if(preg_match("#Build time:\s+(.+)#", $line,$re)){
					$time=strtotime($re[1]);
					$MAIN[$pattern]["zDate"]=date("Y-m-d H:i:s");
					continue;
				}
	
				if(preg_match("#Version:\s+([0-9]+)#",$line,$re)){
					$MAIN[$pattern]["version"]=$re[1];
					continue;
				}
	
				if(preg_match("#Signatures:\s+([0-9]+)#",$line,$re)){
					$MAIN[$pattern]["signatures"]=$re[1];
					continue;
				}
			}
	
			if(!isset($MAIN[$pattern]["zDate"])){
				$time=filemtime("$baseDir/$pattern");
				$MAIN[$pattern]["zDate"]=date("Y-m-d H:i:s",$time);
	
				if(!isset($MAIN[$pattern]["version"])){
					$MAIN[$pattern]["version"]=date("YmdHi",$time);
				}
	
			}
			if(!isset($MAIN[$pattern]["signatures"])){
				$MAIN[$pattern]["signatures"]=$unix->COUNT_LINES_OF_FILE("$baseDir/$pattern");
			}
	
		}
		if(count($MAIN)==0){return;}
		@file_put_contents("/usr/share/artica-postfix/ressources/interface-cache/ClamAVBases", serialize($MAIN));
	
	}

