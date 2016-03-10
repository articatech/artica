<?php
if(is_file("/etc/artica-postfix/FROM_ISO")){if(is_file("/etc/init.d/artica-cd")){print "Starting......: ".date("H:i:s")." artica-". basename(__FILE__)." Waiting Artica-CD to finish\n";die();}}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}

$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["MONIT"]=false;
$GLOBALS["PAUSE"]=false;
$GLOBALS["SERVICE_NAME"]="Artica Web service";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--monit#",implode(" ",$argv),$re)){$GLOBALS["MONIT"]=true;}
if(preg_match("#--pause#",implode(" ",$argv),$re)){$GLOBALS["PAUSE"]=true;}

$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.apache.certificate.php');
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');

	$GLOBALS["ARGVS"]=implode(" ",$argv);
	if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
	if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
	if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
	if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();die();}
	if($argv[1]=="--status"){$GLOBALS["OUTPUT"]=true;status();die();}
	if($argv[1]=="--phpmyadmin"){$GLOBALS["OUTPUT"]=true;PHP_MYADMIN();die();}
	if($argv[1]=="--error500"){$GLOBALS["OUTPUT"]=true;islighttpd_error_500();die();}
	if($argv[1]=="--phpcgi"){$GLOBALS["OUTPUT"]=true;phpcgi();die();}
	if($argv[1]=="--tests"){$GLOBALS["OUTPUT"]=true;TESTS_SETTINGS();die();}
	if($argv[1]=="--apache-build"){$GLOBALS["OUTPUT"]=true;apache_config();die();}
	if($argv[1]=="--php-snmp"){$GLOBALS["OUTPUT"]=true;php_snmp();die();}


function restart($nopid=false){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if(!$nopid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
	}
	
	if($GLOBALS["PAUSE"]){sleep(5);}
	
	@file_put_contents($pidfile, getmypid());
	stop(true);
	
	start(true);	
}	

function reload(){
	$unix=new unix();
	$sock=new sockets();
	$EnableArticaFrontEndToNGninx=$sock->GET_INFO("EnableArticaFrontEndToNGninx");
	$EnableArticaFrontEndToApache=$sock->GET_INFO("EnableArticaFrontEndToApache");
	if(!is_numeric($EnableArticaFrontEndToNGninx)){$EnableArticaFrontEndToNGninx=0;}
	if(!is_numeric($EnableArticaFrontEndToApache)){$EnableArticaFrontEndToApache=0;}
	$EnableNginx=$sock->GET_INFO("EnableNginx");
	$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}
	if(!is_numeric($EnableNginx)){$EnableNginx=1;}
	if($EnableNginx==0){$EnableArticaFrontEndToNGninx=0;}
	
	if($EnableArticaFrontEndToApache==1){
		$apache2ctl=$unix->LOCATE_APACHE_CTL();
		apache_config();
		$cmd="$apache2ctl -f /etc/artica-postfix/httpd.conf -k restart";
		shell_exec($cmd);
	}
	
}


function TESTS_SETTINGS(){
	require("/usr/share/artica-postfix/ressources/settings.inc");
	echo "OK";
}

function apache_stop(){
	$GLOBALS["SERVICE_NAME"]="Artica Apache service";
	$unix=new unix();
	$pid=apache_pid();
	$sock=new sockets();
	$ArticaHttpsPort=$sock->GET_INFO("ArticaHttpsPort");
	if(!is_numeric($ArticaHttpsPort)){$ArticaHttpsPort="9000";}

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} already stopped...\n";}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} testing $ArticaHttpsPort port...\n";}
		fuser_port($ArticaHttpsPort);
		apache_kill_ipcs();
		return;
	}
	
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$lighttpd_bin=$unix->find_program("lighttpd");
	$kill=$unix->find_program("kill");
	$apache2ctl=$unix->LOCATE_APACHE_CTL();


	ToSyslog("ARTICA_STOP:: {$GLOBALS["SERVICE_NAME"]} Shutdown pid $pid");
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Shutdown pid $pid...\n";}
	shell_exec("$apache2ctl -f /etc/artica-postfix/httpd.conf -k stop");
	for($i=0;$i<5;$i++){
		$pid=apache_pid();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=apache_pid();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=apache_pid();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";}
		sleep(1);
	}
	

	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} testing $ArticaHttpsPort port...\n";}
	fuser_port($ArticaHttpsPort);
	apache_kill_ipcs();

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success...\n";}
		return;
	}else{
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} failed...\n";}
	}
}


function patchHardDrives(){
	
	
	exec("/sbin/blkid 2>&1",$results);
	
	while (list ($num, $val) = each ($results) ){
		$val=trim($val);
		if(preg_match("#^(.+?):.*?UUID=\"(.+?)\"\s+#", $val,$re)){
			$UUIDS[$re[2]]=$re[1];
		}
	
	}
	
	
	$f=explode("\n",@file_get_contents("/etc/fstab"));
	while (list ($num, $val) = each ($f) ){
		if(preg_match("#(.+?)\s+(.+?)\s+ext4\s+(.+?)\s+([0-9]+)\s+([0-9]+)#", $val,$re)){
			
			$dev=$re[1];
			
			if(preg_match("#UUID=(.+)#", $dev,$rz)){
				if(!isset($UUIDS[$rz[1]])){
					if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} EXT4 $dev ( Unknown )\n";}
					continue;}
				$dev=$UUIDS[$rz[1]];
			}
			
			
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} EXT4 $dev changed to dir_index,journal_data_writeback\n";}
			shell_exec("/sbin/tune2fs -o journal_data_writeback $dev");
			shell_exec("/sbin/tune2fs -O dir_index $dev");
		}
	}	
	
}


function apache_kill_ipcs(){
	$GLOBALS["CLASS_UNIX"]=new unix();
	$ipcs=$GLOBALS["CLASS_UNIX"]->find_program("ipcs");
	$ipcrm=$GLOBALS["CLASS_UNIX"]->find_program("ipcrm");
	$APACHE_SRC_ACCOUNT=$GLOBALS["CLASS_UNIX"]->APACHE_SRC_ACCOUNT();
	$APACHE_SRC_GROUP=$GLOBALS["CLASS_UNIX"]->APACHE_SRC_GROUP();
	$ipcsT=array();
	
	
if(!is_file($ipcs)){
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} ipcs, no such binary !!!\n";}
	return;
}
$cmd="$ipcs -s 2>&1";
if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} ipcs on $APACHE_SRC_ACCOUNT\n";}
exec("$cmd",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#[a-z0-9]+\s+([0-9]+)\s+$APACHE_SRC_ACCOUNT#", $ligne,$re)){$ipcsT[$re[1]]=true;}
	}
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} kill ". count($ipcsT)." semaphores created by $APACHE_SRC_ACCOUNT...\n";}
	
	while (list ($id, $ligne) = each ($ipcsT) ){
		shell_exec("$ipcrm sem $id");
	}
}


function fuser_port($port){
	$unix=new unix();
	$kill=$unix->find_program("kill");
	$PIDS=$unix->PIDOF_BY_PORT($port);
	if(count($PIDS)==0){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} 0 PID listens $port...\n";}
		
		return;}
	while (list ($pid, $b) = each ($PIDS) ){
		if($unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} killing PID $pid that listens $port\n";}
			unix_system_kill_force($pid);
		}
	}
}
	
function stop($aspid=false){
	
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$sock=new sockets();
	$EnableArticaFrontEndToNGninx=$sock->GET_INFO("EnableArticaFrontEndToNGninx");
	$EnableArticaFrontEndToApache=$sock->GET_INFO("EnableArticaFrontEndToApache");
	if(!is_numeric($EnableArticaFrontEndToNGninx)){$EnableArticaFrontEndToNGninx=0;}
	if(!is_numeric($EnableArticaFrontEndToApache)){$EnableArticaFrontEndToApache=0;}	
	
	
	if($EnableArticaFrontEndToApache==1){apache_stop();}
	$GLOBALS["SERVICE_NAME"]="Artica lighttpd service";
	$pid=LIGHTTPD_PID();
	
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} already stopped...\n";}
		return;
	}	
	
	$pid=LIGHTTPD_PID();
	if($GLOBALS["MONIT"]){
		@file_put_contents("/var/run/artica-apache/apache.pid",$pid);
		@file_put_contents("/var/run/lighttpd/lighttpd.pid",$pid);
		return;
	}
	
	
	
	
	
	
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$lighttpd_bin=$unix->find_program("lighttpd");
	$kill=$unix->find_program("kill");
	
	
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=LIGHTTPD_PID();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";}
		sleep(1);
	}	
	
	$pid=LIGHTTPD_PID();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success...\n";}
		killallphpcgi();
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=LIGHTTPD_PID();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";}
		sleep(1);
	}	
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success...\n";}
		killallphpcgi();
		return;
	}else{
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} failed...\n";}
	}	
}

function killallphpcgi(){
	
	$unix=new unix();
	$phpcgi=$unix->LIGHTTPD_PHP5_CGI_BIN_PATH();
	$kill=$unix->find_program("kill");
	$array=$unix->PIDOF_PATTERN_ALL($phpcgi);
	$userp=LIGHTTPD_GET_USER();
	
	if(preg_match("#^(.+?):#", $userp,$re)){$user=strtolower(trim($re[1]));}
	
	if(count($array)==0){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} No ghost processes...\n";}
		return;
	}
	$c=0;
	while (list ($pid, $line) = each ($array) ){
		$username=trim(strtolower($unix->PROCESS_GET_USER($pid)));
		if($username==null){continue;}
		if($username<>$user){continue;}
		$c++;
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Stopping ghots processes $pid\n";}
		unix_system_kill_force($pid);
	}
	
	if($c==0){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} No ghost processes...\n";}
	}
	
}

function status(){
	$unix=new unix();
	$phpcgi=$unix->LIGHTTPD_PHP5_CGI_BIN_PATH();	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pid=$unix->get_pid_from_file($pidfile);
	$nohup=$unix->find_program("nohup");
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	
	if(!$GLOBALS["VERBOSE"]){
		$timeExec=$unix->file_time_min($pidtime);
		if($timeExec<15){return;}
	}
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	@file_put_contents($pidfile, getmypid());	
	
	$pid=LIGHTTPD_PID();
	$unix=new unix();
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} running $pid since {$timepid}Mn...\n";}
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} stopped...\n";}
		start();
		return;
	}
	$MAIN_PID=$pid;
	$phpcgi=$unix->LIGHTTPD_PHP5_CGI_BIN_PATH();
	$kill=$unix->find_program("kill");
	$array=$unix->PIDOF_PATTERN_ALL($phpcgi);
	if(count($array)==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} no php-cgi processes...\n";}
		ToSyslog("{$GLOBALS["SERVICE_NAME"]} no php-cgi processes restarting PHP-FPM");
		shell_exec("$nohup /etc/init.d/php5-fpm restart >/dev/null 2>&1 &");
		return;
	}
	while (list ($pid, $line) = each ($array) ){
		$username=$unix->PROCESS_GET_USER($pid);
		if($username==null){continue;}
		if($username<>"root"){continue;}
		$time=$unix->PROCCESS_TIME_MIN($pid);
		$arrayPIDS[$pid]=$time;
		$ppid=$unix->PPID_OF($pid);
		if($time>20){
			if($ppid<>$MAIN_PID){
				if($GLOBALS["VERBOSE"]){echo "killing $pid {$time}mn ppid:$ppid/$MAIN_PID\n";}
				unix_system_kill_force($pid);
			}
		}
	}

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} ".count($arrayPIDS)." php-cgi processes...\n";}
	
}

function ToSyslog($text){

	$LOG_SEV=LOG_INFO;
	if(function_exists("openlog")){openlog(basename(__FILE__), LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
	if(function_exists("closelog")){closelog();}
}

function start($aspid=false){
	$unix=new unix();
	if(is_file("/etc/artica-postfix/FROM_ISO")){
		if($unix->file_time_min("/etc/artica-postfix/FROM_ISO")<1){return;}
	}
	
	$sock=new sockets();
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}	
	
	if(!is_file("/etc/modprobe.d/blacklist-floppy.conf")){
		@mkdir("/etc/modprobe.d",0755,true);
		$rmmod=$unix->find_program("rmmod");
		$update_initframs=$unix->find_program("update-initramfs");
		@file_put_contents("/etc/modprobe.d/blacklist-floppy.conf", "blacklist floppy\n");
		shell_exec("$rmmod floppy");
		shell_exec("$update_initframs -u >/dev/null 2>&1 &");
	}
	
	$EnableArticaFrontEndToNGninx=$sock->GET_INFO("EnableArticaFrontEndToNGninx");
	$EnableArticaFrontEndToApache=$sock->GET_INFO("EnableArticaFrontEndToApache");
	if(!is_numeric($EnableArticaFrontEndToNGninx)){$EnableArticaFrontEndToNGninx=0;}
	if(!is_numeric($EnableArticaFrontEndToApache)){$EnableArticaFrontEndToApache=0;}
	$EnableNginx=$sock->GET_INFO("EnableNginx");
	$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}
	if(!is_numeric($EnableNginx)){$EnableNginx=1;}
	if($EnableNginx==0){$EnableArticaFrontEndToNGninx=0;}
	$unix->CleanOldLibs();
	$chmod=$unix->find_program("chmod");
	@mkdir("/etc/artica-postfix/settings/Daemons",0755,true);
	shell_exec("$chmod -R 0755 /etc/artica-postfix/settings >/dev/null 2>&1");
	
	$sock=new sockets();
	$DisableForceFCK=intval($sock->GET_INFO("DisableForceFCK"));
	@unlink("/forcefsck");if($DisableForceFCK==0){@touch("/forcefsck"); }
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} EnableArticaFrontEndToNGninx:$EnableArticaFrontEndToNGninx\n";}
	
	$pid=LIGHTTPD_PID();
	if($EnableArticaFrontEndToNGninx==1){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} transfered to Nginx..\n";}
		if($unix->process_exists($pid)){
			ToSyslog("Stopping artica-webinterface service using lighttpd (transfered to Nginx)...");
			stop(true);
			apache_stop();
		}
		shell_exec("/etc/init.d/nginx start");
		return;
	}
	
	if($EnableArticaFrontEndToApache==1){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Transfered to Apache..\n";}
		if($unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Stopping Lighttpd PID $pid\n";}
			ToSyslog("Stopping artica-webinterface service using lighttpd (transfered to Apache)...");
			stop(true);
		}
		$apachebin=$unix->LOCATE_APACHE_BIN_PATH();
		if(is_file($apachebin)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Starting Apache Mode...\n";}
			apache_start();
		}
		return;
	}	
	
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} transfered to lighttpd..\n";}
	$GLOBALS["SERVICE_NAME"]="Artica lighttpd service";
	$pid=LIGHTTPD_PID();
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} {$GLOBALS["SERVICE_NAME"]} already started $pid since {$timepid}Mn...\n";}
		return;
	}
		
	ToSyslog("Starting artica-webinterface service using lighttpd...");
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$rm=$unix->find_program("rm");
	$lighttpd_bin=$unix->find_program("lighttpd");
	$LIGHTTPD_CONF_PATH=LIGHTTPD_CONF_PATH();
	
	@mkdir("/var/run/lighttpd",0755,true);
	@mkdir("/var/log/lighttpd",0755,true);
	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.web-community-filter.php --register-lic >/dev/null 2>&1 &";
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	shell_exec($cmd);
	buildConfig();
	$cmd="$lighttpd_bin -f $LIGHTTPD_CONF_PATH";
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	shell_exec($cmd);
	
	for($i=0;$i<8;$i++){
		$pid=LIGHTTPD_PID();
		if($unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting $i/8...\n";}
		sleep(1);
	}
	
	$pid=LIGHTTPD_PID();
	if($unix->process_exists($pid)){
		ToSyslog("{$GLOBALS["SERVICE_NAME"]} Success service started pid:$pid");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Success service started pid:$pid...\n";}
		shell_exec("$php5 /usr/share/artica-postfix/exec.apc.compile.php");
		if(!is_file('/etc/init.d/artica-memcache')){shell_exec("$php5 /usr/share/artica-postfix/exec.initslapd.php --memcache");}
		shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.initslapd.php --phppfm-restart-back >/dev/null 2>&1 &");
		shell_exec("$nohup /etc/init.d/artica-memcached start >/dev/null 2>&1 &");
		shell_exec("$nohup /etc/init.d/monit restart >/dev/null 2>&1 &");
		$APACHE_SRC_ACCOUNT=$unix->APACHE_SRC_ACCOUNT();
		$APACHE_SRC_GROUP=$unix->APACHE_SRC_GROUP();
		$unix->chown_func($APACHE_SRC_ACCOUNT,$APACHE_SRC_GROUP,"/var/lib/php5/*");
		
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} failed...\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $cmd\n";}
	}

}


//##############################################################################
function LIGHTTPD_PID(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file('/var/run/lighttpd/lighttpd.pid');
	if($unix->process_exists($pid)){return $pid;}
	$lighttpd_bin=$unix->find_program("lighttpd");
	$LIGHTTPD_CONF_PATH=LIGHTTPD_CONF_PATH();
	return $unix->PIDOF_PATTERN($lighttpd_bin." -f $LIGHTTPD_CONF_PATH");
}
//##############################################################################
function  LIGHTTPD_CONF_PATH(){
	if(isset($GLOBALS["LIGHTTPD_CONF_PATH"])){return $GLOBALS["LIGHTTPD_CONF_PATH"];}
	$f[]="/etc/lighttpd/lighttpd.conf";
 	$f[]="/etc/lighttpd/lighttpd.conf";
  	$f[]="/opt/artica/conf/lighttpd.conf";
 	$f[]="/usr/local/etc/lighttpd.conf";
 	while (list ($pid, $line) = each ($f) ){
 		if(is_file($line)){
 			$GLOBALS["LIGHTTPD_CONF_PATH"]=$line;
 			return $line;}
 	}
 	$GLOBALS["LIGHTTPD_CONF_PATH"]="/etc/lighttpd/lighttpd.conf";
 	return $GLOBALS["LIGHTTPD_CONF_PATH"];
}
//##############################################################################
function LIGHTTPD_MODULES_PATH(){
	$f[]="/usr/lib64/lighttpd/mod_alias.so";
	$f[]="/usr/local/lib64/lighttpd/mod_alias.so";
	$f[]="/usr/lib/lighttpd/mod_alias.so";
	$f[]="/usr/local/lib/lighttpd/mod_alias.so";
	while (list ($pid, $line) = each ($f) ){
		if(is_file($line)){return dirname($line);}
	}	
}
//##############################################################################
function AWSTATS_www_root(){
	$f[]="/usr/local/awstats/wwwroot/icon";
	$f[]="/usr/share/awstats/icon";
	$f[]="/var/www/awstats/icon";
	$f[]="/usr/share/awstats/wwwroot/icon";
	while (list ($pid, $line) = each ($f) ){
		if(is_dir($line)){return $line;}
	}
}
//##############################################################################
function isModule($modulename){
	$LOAD_MODULES=LOAD_MODULES();
	$modulename=trim(strtolower($modulename));
	if(isset($LOAD_MODULES[$modulename])){return true;}
	$libdir=LIGHTTPD_MODULES_PATH();
	if(is_file("$libdir/$modulename.so")){return true;}
	return false;
}
//##############################################################################   
function LOAD_MODULES(){
	
	if(isset($GLOBALS["LIGHTTPDMODS"])){return $GLOBALS["LIGHTTPDMODS"];}
	$unix=new unix();
	$lighttpd=$unix->find_program("lighttpd");
	if(!is_file($lighttpd)){return;}
	exec("$lighttpd -V 2>&1",$results);
	while (list ($pid, $line) = each ($results) ){
		if(preg_match('#\+\s+(.+?)\s+support#',$line,$re)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Available module.....: \"{$re[1]}\"\n";}
			$re[1]=trim(strtolower($re[1]));
			$GLOBALS["LIGHTTPDMODS"][$re[1]]=true;
			continue;
		}
			
	}
}	
//##############################################################################
function LIGHTTPD_GET_USER(){
	$sock=new sockets();
	$user=trim($sock->GET_INFO('LighttpdUserAndGroup'));
	
	if($user<>null){
		$user=str_replace("lighttpd:lighttpd:lighttpd", "lighttpd:lighttpd", $user);
		$user=str_replace("www-data:www-data:www-data", "www-data:www-data", $user);
		return $user;
	}
	
	$LIGHTTPD_CONF_PATH=LIGHTTPD_CONF_PATH();
	$f=explode("\n",@file_get_contents($LIGHTTPD_CONF_PATH));
	$mem=null;
	$gp=null;
	while (list ($pid, $line) = each ($f) ){
		if(preg_match('#^server\.username.+?"(.+?)"#', $line,$re)){$mem=$re[1];continue;}
		if(preg_match('#^server\.groupname.+?"(.+?)"#', $line,$re)){$gp=$re[1];continue;}	
		if( ($mem<>null) && ($gp<>null) ){
			$sock->SET_INFO("LighttpdUserAndGroup", "$mem:$gp");
			return "$mem:$gp";}
		}
		
		return "www-data:www-data";

}
//##############################################################################

function apache_pid(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file('/var/run/artica-apache/apache.pid');
	if($unix->process_exists($pid)){return $pid;}
	$apache2ctl=$unix->LOCATE_APACHE_CTL();
	return $unix->PIDOF_PATTERN($apache2ctl." -f /etc/artica-postfix/httpd.conf");
}


function apache_start(){
	$unix=new unix();
	$GLOBALS["SERVICE_NAME"]="Artica Apache service";
	$apachebin=$unix->LOCATE_APACHE_BIN_PATH();
	
	
	
	$pid=apache_pid();
	
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} already started $pid since {$timepid}Mn...\n";}
		return;
	}
	
	
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$rm=$unix->find_program("rm");
	$apache2ctl=$unix->LOCATE_APACHE_CTL();
	patchHardDrives();
	apache_config();
	

	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.web-community-filter.php --register-lic >/dev/null 2>&1 &";
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	shell_exec($cmd);
	
	$cmd="$apache2ctl -f /etc/artica-postfix/httpd.conf -k start";
	shell_exec($cmd);
	
	
	
	
	for($i=0;$i<6;$i++){
		$pid=apache_pid();
		if($unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting $i/6...\n";}
		sleep(1);
	}
	
	
	$pid=apache_pid();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Success service started pid:$pid...\n";}
		shell_exec("$php5 /usr/share/artica-postfix/exec.apc.compile.php");
		if(!is_file('/etc/init.d/artica-memcache')){shell_exec("$php5 /usr/share/artica-postfix/exec.initslapd.php --memcache");}
		shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.shm.php --SessionMem >/dev/null 2>&1 &");
		shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.shm.php --service-up >/dev/null 2>&1 &");
		shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.initslapd.php --phppfm-restart-back >/dev/null 2>&1 &");
		shell_exec("$nohup /etc/init.d/artica-memcached start >/dev/null 2>&1 &");
		shell_exec("$nohup /etc/init.d/monit restart >/dev/null 2>&1 &");
		shell_exec("$nohup $php5  /usr/share/artica-postfix/exec.usb.scan.write.php >/dev/null 2>&1 &");
	
	
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} failed...\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $cmd\n";}
		apache_troubleshoot();
		
	}	

	$APACHE_SRC_ACCOUNT=$unix->APACHE_SRC_ACCOUNT();
	$APACHE_SRC_GROUP=$unix->APACHE_SRC_GROUP();
	$unix->chown_func($APACHE_SRC_ACCOUNT,$APACHE_SRC_GROUP,"/var/lib/php5/*");
	
	
}

function apache_troubleshoot(){
	
	$f=explode("\n",@file_get_contents("/var/log/lighttpd/apache-error.log"));
	
	while (list ($index, $line) = each ($f) ){
		
		if(preg_match("#SSL Library Error#", $line)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} SSL certificate error, remove all certificates\n";}
			@unlink("/etc/ssl/certs/apache/server.crt");
			@unlink("/etc/ssl/certs/apache/server.key");
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} you should restart the service now...\n";}
			return;
		}
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $line...\n";}
	}
	
	
}





function apache_config(){
	$sock=new sockets();
	$unix=new unix();
	$EnablePHPFPM=0;
	@mkdir("/var/run/apache2",0755,true);
	@mkdir("/var/run/artica-apache",0755,true);
	@mkdir("/var/log/lighttpd",0755,true);
	$APACHE_SRC_ACCOUNT=$unix->APACHE_SRC_ACCOUNT();
	$APACHE_SRC_GROUP=$unix->APACHE_SRC_GROUP();
	$APACHE_MODULES_PATH=$unix->APACHE_MODULES_PATH();
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	$pydio_installed=false;
	if(is_file(" /etc/php5/cli/conf.d/ming.ini")){@unlink(" /etc/php5/cli/conf.d/ming.ini");}
	@unlink("/var/log/lighttpd/apache-error.log");
	@touch("/var/log/lighttpd/apache-error.log");
	@chmod("/var/log/lighttpd/apache-error.log",0755);
	$unix->chown_func($APACHE_SRC_ACCOUNT, $APACHE_SRC_GROUP,"/var/log/lighttpd/*");
	$unix->chown_func($APACHE_SRC_ACCOUNT, $APACHE_SRC_GROUP,"/usr/share/artica-postfix/ressources/logs/*");
	
	if(is_dir("/usr/share/artica-postfix/pydio")){$pydio_installed=true;}
	
	$ArticaHttpsPort=9000;
	$NoLDAPInLighttpdd=0;
	$ArticaHttpUseSSL=1;
	$EnableSquidGuardHTTPToArtica=intval($sock->GET_INFO("EnableSquidGuardHTTPToArtica"));
	$ArticaHttpsPort=$sock->GET_INFO("ArticaHttpsPort");
	$ArticaHttpUseSSL=$sock->GET_INFO("ArticaHttpUseSSL");
	if(!is_numeric($ArticaHttpUseSSL)){$ArticaHttpUseSSL=1;}
	if(!is_numeric($ArticaHttpsPort)){$ArticaHttpsPort="9000";}
	$LighttpdArticaListenIP=$sock->GET_INFO("LighttpdArticaListenIP");
	$InfluxDBAllowBrowse=intval($sock->GET_INFO("InfluxDBAllowBrowse"));
	$php5UploadMaxFileSize=intval($sock->GET_INFO("php5UploadMaxFileSize"));
	$php5PostMaxSize=intval($sock->GET_INFO("php5PostMaxSize"));
	if($php5UploadMaxFileSize==0){$php5UploadMaxFileSize=256;}
	if($php5PostMaxSize==0){$php5PostMaxSize=128;}
	$ArticaWebDAVHTTPPort=intval($sock->GET_INFO("ArticaWebDAVHTTPPort"));
	if($ArticaWebDAVHTTPPort==0){$ArticaWebDAVHTTPPort=9005;}
	$SquidReloadInpublic=intval($sock->GET_INFO("SquidReloadInpublic"));
	$php5SessionGCMaxlifeTime=intval($sock->GET_INFO("php5SessionGCMaxlifeTime"));
	
	$phpfpm=$unix->APACHE_LOCATE_PHP_FPM();
	$php=$unix->LOCATE_PHP5_BIN();
	$EnableArticaApachePHPFPM=$sock->GET_INFO("EnableArticaApachePHPFPM");
	if(!is_numeric($EnableArticaApachePHPFPM)){$EnableArticaApachePHPFPM=0;}
	if($php5SessionGCMaxlifeTime==0){$php5SessionGCMaxlifeTime=3600;}
	
	
	$EnablePHPFPM=intval($sock->GET_INFO("EnablePHPFPM"));
	$SquidGuardApachePort=intval($sock->GET_INFO("SquidGuardApachePort"));
	$SquidGuardApacheSSLPort=intval($sock->GET_INFO("SquidGuardApacheSSLPort"));
	$SquidGuardWebSSLCertificate=$sock->GET_INFO("SquidGuardWebSSLCertificate");
	$LighttpdArticaCertificateName=trim($sock->GET_INFO("LighttpdArticaCertificateName"));
	
	if(!is_file($phpfpm)){$EnableArticaApachePHPFPM=0;}
	if($EnablePHPFPM==0){$EnableArticaApachePHPFPM=0;}
	if($SquidGuardApachePort==0){$SquidGuardApachePort=9020;}
	if($SquidGuardApacheSSLPort==0){$SquidGuardApacheSSLPort=9025;}
	
	$unix->chown_func($APACHE_SRC_ACCOUNT, $APACHE_SRC_GROUP,"/var/run/artica-apache");
	$apache_LOCATE_MIME_TYPES=$unix->apache_LOCATE_MIME_TYPES();
	
	if($EnableArticaApachePHPFPM==1){
		if(!is_file("$APACHE_MODULES_PATH/mod_fastcgi.so")){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} mod_fastcgi.so is required to use PHP5-FPM\n";}
			$EnableArticaApachePHPFPM=0;
		}
	}
	
	if($APACHE_SRC_ACCOUNT==null){
		$APACHE_SRC_ACCOUNT="www-data";
		$APACHE_SRC_GROUP="www-data";
		$unix->CreateUnixUser($APACHE_SRC_ACCOUNT,$APACHE_SRC_GROUP,"Apache username");
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Run as $APACHE_SRC_ACCOUNT:$APACHE_SRC_GROUP\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} PHP-FPM: $EnablePHPFPM\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} PHP-FPM Enabled: $EnableArticaApachePHPFPM\n";}
	$f[]="LockFile /var/run/apache2/artica-accept.lock";
	$f[]="PidFile /var/run/artica-apache/apache.pid";
	$f[]="DocumentRoot /usr/share/artica-postfix";
	
	
	$open_basedir[]="/usr/share/artica-postfix";
	$open_basedir[]="/etc/artica-postfix";
	$open_basedir[]="/etc/artica-postfix/settings";
	$open_basedir[]="/var/log";
	$open_basedir[]="/var/run/mysqld";
	$open_basedir[]="/usr/share/php";
	$open_basedir[]="/usr/share/php5";
	$open_basedir[]="/var/lib/php5";
	$open_basedir[]="/var/lighttpd/upload";
	$open_basedir[]="/usr/share/artica-postfix/ressources";
	$open_basedir[]="/usr/share/artica-postfix/framework";
	$open_basedir[]="/etc/ssl/certs/mysql-client-download";
	$open_basedir[]="/var/run";
	$open_basedir[]="/bin";
	$open_basedir[]="/tmp";
	$open_basedir[]="/usr/sbin";
	$open_basedir[]="/home";

	$artica_webdav=apache_webdav();
	$apache_firewall=apache_firewall();
	
	//$f[]="php_value open_basedir \"".@implode(":", $open_basedir)."\"";
	//$f[]="php_value output_buffering Off";
	//$f[]="php_flag magic_quotes_gpc Off";
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Listen Port: $ArticaHttpsPort\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Listen IP: $LighttpdArticaListenIP\n";}
	

	if($LighttpdArticaListenIP<>null){
		$unix=new unix();
		$IPS=$unix->NETWORK_ALL_INTERFACES(true);
		if(!isset($IPS[$LighttpdArticaListenIP])){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} ERROR! Listen IP: $LighttpdArticaListenIP -> FALSE !!\n";}
			$LighttpdArticaListenIP=null;
		}
	}
	
	
	if($LighttpdArticaListenIP==null){$LighttpdArticaListenIP="*";}
	
	
	if($LighttpdArticaListenIP<>null){
		$ArticaHttpsPort="$LighttpdArticaListenIP:$ArticaHttpsPort";
	}
	
	$f[]="Listen $ArticaHttpsPort";
	if($EnableSquidGuardHTTPToArtica==1){
		$f[]="Listen $SquidGuardApachePort";
		$f[]="Listen $SquidGuardApacheSSLPort";
	}
	if($artica_webdav<>null){
		$f[]="Listen *:$ArticaWebDAVHTTPPort";
		
	}
	
	
	$MaxClients=20;
	$MinSpareServers=2;
	$MaxSpareServers=3;
	
	
	if($SquidPerformance>2){
		$MinSpareServers=1;
		$MaxSpareServers=2;
		$MaxClients=10;
	}
	
	
	if(is_file("/etc/cron.d/artica-caches-pages")){
		@unlink("/etc/cron.d/artica-caches-pages");
		system("/etc/init.d/cron reload");
	}
		
	$f[]="<IfModule mpm_prefork_module>";
	$f[]="\tStartServers 1";
	$f[]="\tMinSpareServers $MinSpareServers";
	$f[]="\tMaxSpareServers $MaxSpareServers";
	$f[]="\tMaxClients $MaxClients";
	$f[]="\tServerLimit $MaxClients";
	$f[]="\tMaxRequestsPerChild 100";
	$f[]="</IfModule>";
	$f[]="<IfModule mpm_worker_module>";
	$f[]="\tMinSpareThreads      25";
	$f[]="\tMaxSpareThreads      75 ";
	$f[]="\tThreadLimit          64";
	$f[]="\tThreadsPerChild      25";
	$f[]="</IfModule>";
	$f[]="<IfModule mpm_event_module>";
	$f[]="\tMinSpareThreads      25";
	$f[]="\tMaxSpareThreads      75 ";
	$f[]="\tThreadLimit          64";
	$f[]="\tThreadsPerChild      25";
	$f[]="</IfModule>";
	$f[]="AccessFileName .htaccess";
	$f[]="<Files ~ \"^\.ht\">";
	//$f[]="\tOrder allow,deny";
	//$f[]="\tDeny from all";
	//$f[]="\tSatisfy all";
	$f[]="</Files>";
	$f[]="DefaultType text/plain";
	$f[]="HostnameLookups Off";
	$f[]="User				   $APACHE_SRC_ACCOUNT";
	$f[]="Group				   $APACHE_SRC_GROUP";
	$f[]="Timeout              300";
	$f[]="KeepAlive            Off";
	$f[]="KeepAliveTimeout     15";
	$f[]="StartServers         1";
	$f[]="MaxClients           $MaxClients";
	$f[]="MinSpareServers      2";
	$f[]="MaxSpareServers      3";
	$f[]="MaxRequestsPerChild  100";
	$f[]="MaxKeepAliveRequests 100";
	$ServerName=$unix->hostname_g();
	if($ServerName==null){$ServerName="localhost.localdomain";}
	$f[]="AcceptMutex flock";
	$f[]="";
	$f[]="<IfModule mod_ssl.c>";
	$f[]="\tSSLRandomSeed connect builtin";
	$f[]="\tSSLRandomSeed connect file:/dev/urandom 256";
	$f[]="\tSSLRandomSeed startup file:/dev/urandom  256";
	$f[]="\tSSLPassPhraseDialog  builtin";
	$f[]="\tSSLSessionCache        shmcb:/var/run/apache2/ssl_scache-artica(512000)";
	$f[]="\tSSLSessionCacheTimeout  300";
	$f[]="\tSSLSessionCacheTimeout  300";
	$f[]="\tSSLCipherSuite HIGH:MEDIUM:!ADH";
	$f[]="\tSSLProtocol all -SSLv2";
	$f[]="</IfModule>";
	$f[]="";
	$f[]="\tAddType application/x-x509-ca-cert .crt";
	$f[]="\tAddType application/x-pkcs7-crl    .crl";
	

	$f[]="<IfModule mod_mime.c>";
	$f[]="\tTypesConfig /etc/mime.types";
	$f[]="\tAddType application/x-compress .Z";
	$f[]="\tAddType application/x-gzip .gz .tgz";
	$f[]="\tAddType application/x-bzip2 .bz2";
	$f[]="\tAddType application/x-httpd-php .php .phtml";
	$f[]="\tAddType application/x-httpd-php-source .phps";
	$f[]="\tAddType application/octet-stream .acl";
	$f[]="\tAddLanguage ca .ca";
	$f[]="\tAddLanguage cs .cz .cs";
	$f[]="\tAddLanguage da .dk";
	$f[]="\tAddLanguage de .de";
	$f[]="\tAddLanguage el .el";
	$f[]="\tAddLanguage en .en";
	$f[]="\tAddLanguage eo .eo";
	$f[]="\tRemoveType  es";
	$f[]="\tAddLanguage es .es";
	$f[]="\tAddLanguage et .et";
	$f[]="\tAddLanguage fr .fr";
	$f[]="\tAddLanguage he .he";
	$f[]="\tAddLanguage hr .hr";
	$f[]="\tAddLanguage it .it";
	$f[]="\tAddLanguage ja .ja";
	$f[]="\tAddLanguage ko .ko";
	$f[]="\tAddLanguage ltz .ltz";
	$f[]="\tAddLanguage nl .nl";
	$f[]="\tAddLanguage nn .nn";
	$f[]="\tAddLanguage no .no";
	$f[]="\tAddLanguage pl .po";
	$f[]="\tAddLanguage pt .pt";
	$f[]="\tAddLanguage pt-BR .pt-br";
	$f[]="\tAddLanguage ru .ru";
	$f[]="\tAddLanguage sv .sv";
	$f[]="\tRemoveType  tr";
	$f[]="\tAddLanguage tr .tr";
	$f[]="\tAddLanguage zh-CN .zh-cn";
	$f[]="\tAddLanguage zh-TW .zh-tw";
	$f[]="\tAddCharset us-ascii    .ascii .us-ascii";
	$f[]="\tAddCharset ISO-8859-1  .iso8859-1  .latin1";
	$f[]="\tAddCharset ISO-8859-2  .iso8859-2  .latin2 .cen";
	$f[]="\tAddCharset ISO-8859-3  .iso8859-3  .latin3";
	$f[]="\tAddCharset ISO-8859-4  .iso8859-4  .latin4";
	$f[]="\tAddCharset ISO-8859-5  .iso8859-5  .cyr .iso-ru";
	$f[]="\tAddCharset ISO-8859-6  .iso8859-6  .arb .arabic";
	$f[]="\tAddCharset ISO-8859-7  .iso8859-7  .grk .greek";
	$f[]="\tAddCharset ISO-8859-8  .iso8859-8  .heb .hebrew";
	$f[]="\tAddCharset ISO-8859-9  .iso8859-9  .latin5 .trk";
	$f[]="\tAddCharset ISO-8859-10  .iso8859-10  .latin6";
	$f[]="\tAddCharset ISO-8859-13  .iso8859-13";
	$f[]="\tAddCharset ISO-8859-14  .iso8859-14  .latin8";
	$f[]="\tAddCharset ISO-8859-15  .iso8859-15  .latin9";
	$f[]="\tAddCharset ISO-8859-16  .iso8859-16  .latin10";
	$f[]="\tAddCharset ISO-2022-JP .iso2022-jp .jis";
	$f[]="\tAddCharset ISO-2022-KR .iso2022-kr .kis";
	$f[]="\tAddCharset ISO-2022-CN .iso2022-cn .cis";
	$f[]="\tAddCharset Big5        .Big5       .big5 .b5";
	$f[]="\tAddCharset cn-Big5     .cn-big5";
	$f[]="\t# For russian, more than one charset is used (depends on client, mostly):";
	$f[]="\tAddCharset WINDOWS-1251 .cp-1251   .win-1251";
	$f[]="\tAddCharset CP866       .cp866";
	$f[]="\tAddCharset KOI8      .koi8";
	$f[]="\tAddCharset KOI8-E      .koi8-e";
	$f[]="\tAddCharset KOI8-r      .koi8-r .koi8-ru";
	$f[]="\tAddCharset KOI8-U      .koi8-u";
	$f[]="\tAddCharset KOI8-ru     .koi8-uk .ua";
	$f[]="\tAddCharset ISO-10646-UCS-2 .ucs2";
	$f[]="\tAddCharset ISO-10646-UCS-4 .ucs4";
	$f[]="\tAddCharset UTF-7       .utf7";
	$f[]="\tAddCharset UTF-8       .utf8";
	$f[]="\tAddCharset UTF-16      .utf16";
	$f[]="\tAddCharset UTF-16BE    .utf16be";
	$f[]="\tAddCharset UTF-16LE    .utf16le";
	$f[]="\tAddCharset UTF-32      .utf32";
	$f[]="\tAddCharset UTF-32BE    .utf32be";
	$f[]="\tAddCharset UTF-32LE    .utf32le";
	$f[]="\tAddCharset euc-cn      .euc-cn";
	$f[]="\tAddCharset euc-gb      .euc-gb";
	$f[]="\tAddCharset euc-jp      .euc-jp";
	$f[]="\tAddCharset euc-kr      .euc-kr";
	$f[]="\tAddCharset EUC-TW      .euc-tw";
	$f[]="\tAddCharset gb2312      .gb2312 .gb";
	$f[]="\tAddCharset iso-10646-ucs-2 .ucs-2 .iso-10646-ucs-2";
	$f[]="\tAddCharset iso-10646-ucs-4 .ucs-4 .iso-10646-ucs-4";
	$f[]="\tAddCharset shift_jis   .shift_jis .sjis";
	$f[]="\tAddType text/html .shtml";
	$f[]="\tAddOutputFilter INCLUDES .shtml";
	$f[]="</IfModule>";
	
	$f[]="";
	$f[]="<VirtualHost $ArticaHttpsPort>";	
	$f[]="\tServerName $ServerName";
	$f[]="\tAddType application/x-httpd-php .php";
	if($EnablePHPFPM==0){
		$f[]="\tphp_value post_max_size {$php5PostMaxSize}M";
		$f[]="\tphp_value upload_max_filesize {$php5UploadMaxFileSize}M";
		$f[]="\tphp_value session.gc_maxlifetime {$php5SessionGCMaxlifeTime}";
		$f[]="\tphp_value session.gc_divisor 1";
		$f[]="\tphp_value session.gc_probabilit 1";
		$f[]="\tphp_value session.cookie_lifetime 0";
		
	}
	
	
	if($ArticaHttpUseSSL==1){
		$mknod=$unix->find_program("mknod");
		$f[]="\tSSLEngine on";
		if($LighttpdArticaCertificateName==null){
			$f[]="\tSSLCertificateFile \"/etc/ssl/certs/apache/server.crt\"";
			$f[]="\tSSLCertificateKeyFile \"/etc/ssl/certs/apache/server.key\"";

			if(!is_file("/etc/ssl/certs/apache/server.crt")){shell_exec("/usr/share/artica-postfix/bin/artica-install --apache-ssl-cert");}
		}else{
			$cert=new apache_certificate($LighttpdArticaCertificateName);
			$f[]=$cert->build();
			
		}
		$f[]="\tSSLVerifyClient none";
		$f[]="\tServerSignature Off";
		shell_exec("$mknod /dev/random c 1 9 >/dev/null 2>&1");
	}
	
	
	

	
	
	if($EnableArticaApachePHPFPM==0){
		$f[]="\tphp_value error_log \"/var/log/php.log\"";
	}
	
	@chown("/var/log/php.log", $APACHE_SRC_ACCOUNT);
	
	$f[]="<IfModule mod_fcgid.c>";
	$f[]="	PHP_Fix_Pathinfo_Enable 1";
	$f[]="</IfModule>";
	
	$f[]="<IfModule mod_php5.c>";
	$f[]="    <FilesMatch \"\.ph(p3?|tml)$\">";
	$f[]="	SetHandler application/x-httpd-php";
	$f[]="    </FilesMatch>";
	$f[]="    <FilesMatch \"\.phps$\">";
	$f[]="	SetHandler application/x-httpd-php-source";
	$f[]="    </FilesMatch>";
	$f[]="    <IfModule mod_userdir.c>";
	$f[]="        <Directory /home/*/public_html>";
	$f[]="            php_admin_value engine Off";
	$f[]="        </Directory>";
	$f[]="    </IfModule>";
	$f[]="</IfModule>";	


	
	
	$f[]=apache_nagios_config();
	$f[]=apache_phpldapadmin();
	
	
	
	$squid=$unix->LOCATE_SQUID_BIN();
	if(is_file($squid)){
		$f[]="Alias /proxy /usr/share/artica-postfix/squid.access.log.php";
		$f[]="Alias /parent /usr/share/artica-postfix/squid.access.log.php";
		$f[]="Alias /webfilter /usr/share/artica-postfix/squid.access.webfilter.log.php";
		$f[]="Alias /meta-updates /home/artica-meta";
		$f[]="Alias /categories /usr/share/artica-postfix/public.categories.personnal.php";
		if($SquidReloadInpublic==1){
			$SquidReloadInpublicAlias=$sock->GET_INFO("SquidReloadInpublicAlias");
			if($SquidReloadInpublicAlias<>null){
				$f[]="Alias /$SquidReloadInpublicAlias /usr/share/artica-postfix/SquidReloadInpublicAlias.php";
			}
		}
	}
	
	
	$f[]="Alias /pfx  \"/usr/share/artica-postfix/pfx.php\"";
	
	
	
	$f[]="<Directory \"/home/artica-meta\">";
	$f[]="\tOptions Indexes FollowSymLinks";
	$f[]="</Directory>";
	
	$f[]="<Directory \"/usr/share/artica-postfix\">";
	$f[]="\tDirectoryIndex logon.php";
	$f[]="\tSSLOptions +StdEnvVars";
	$f[]="\tOptions Indexes FollowSymLinks";
	$f[]="\tAllowOverride Options";
	if($apache_firewall<>null){
		$f[]=$apache_firewall;	
		
	}
	//$f[]="\tOrder allow,deny";
	//$f[]="\tAllow from all";
	$f[]="</Directory>";	
	
	if(is_file($unix->LOCATE_SQUID_BIN())){
		$ArticaProxyStatisticsBackupFolder=$sock->GET_INFO("ArticaProxyStatisticsBackupFolder");
		if($ArticaProxyStatisticsBackupFolder==null){$ArticaProxyStatisticsBackupFolder="/home/artica/squid/backup-statistics";}
		$ArticaProxyStatisticsOpenWeb=intval($sock->GET_INFO("ArticaProxyStatisticsOpenWeb"));
		if($ArticaProxyStatisticsOpenWeb==1){
			$f[]="Alias /backup-stats  \"$ArticaProxyStatisticsBackupFolder\"";
			$f[]="<Directory \"$ArticaProxyStatisticsBackupFolder\">";
			$f[]="\tSSLOptions +StdEnvVars";
			$f[]="\tOptions Indexes FollowSymLinks";
			$f[]="\tAllowOverride All";
			//$f[]="\tOrder allow,deny";
			//$f[]="\tAllow from all";
			$f[]="</Directory>";
			
		}
	}	
	if($pydio_installed){
		$directories[]="/home/pydio/plugins/auth.serial";
		$directories[]="/home/pydio/plugins/conf.serial";
		$directories[]="/home/pydio/plugins";
		$directories[]="/home/pydio/cache";  
		$directories[]="/home/pydio/files";  
		$directories[]="/home/pydio/logs";  
		$directories[]="/home/pydio/personal";  
		$directories[]="/home/pydio/public";  
		$directories[]="/home/pydio/tmp";
		
		while (list ($index, $dir) = each ($directories) ){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} permissions on $dir\n";}
			@mkdir($dir,0755,true);
			$unix->chown_func($APACHE_SRC_ACCOUNT,$APACHE_SRC_GROUP,$dir);
		}
		

		$unix->chown_func($APACHE_SRC_ACCOUNT,$APACHE_SRC_GROUP,"/home/pydio/cache");
		$unix->chown_func($APACHE_SRC_ACCOUNT,$APACHE_SRC_GROUP,"/home/pydio/plugins");
		$f[]="Alias /explorer  \"/usr/share/artica-postfix/pyio\"";
		$f[]="<Directory \"/usr/share/artica-postfix/pyio\">";
		
		$f[]="\tDirectoryIndex index.php";
		$f[]="\tSSLOptions +StdEnvVars";
		$f[]="\tOptions Indexes FollowSymLinks";
		$f[]="\tAllowOverride All";
		//$f[]="\tOrder allow,deny";
		//$f[]="\tAllow from all";
		$f[]="</Directory>";
	}
	
	if($InfluxDBAllowBrowse==1){
		$InFluxBackupDatabaseDir=$sock->GET_INFO("InFluxBackupDatabaseDir");
		if($InFluxBackupDatabaseDir==null){$InFluxBackupDatabaseDir="/home/artica/influx/backup";}
		$f[]="Alias /backup-influx  \"$InFluxBackupDatabaseDir\"";
		$f[]="<Directory \"$InFluxBackupDatabaseDir\">";
		$f[]="\tSSLOptions +StdEnvVars";
		$f[]="\tOptions Indexes FollowSymLinks";
		$f[]="\tAllowOverride All";
		//$f[]="\tOrder allow,deny";
		//$f[]="\tAllow from all";
		$f[]="</Directory>";
	}
	
	
	
	if($EnableArticaApachePHPFPM==1){	
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Activate PHP5-FPM\n";}
		shell_exec("$php /usr/share/artica-postfix/exec.initslapd.php --phppfm");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Restarting PHP5-FPM\n";}
		shell_exec("/etc/init.d/php5-fpm restart");
		$f[]="\tAlias /php5.fastcgi /var/run/artica-apache/php5.fastcgi";
		$f[]="\tAddHandler php-script .php";
		$f[]="\tFastCGIExternalServer /var/run/artica-apache/php5.fastcgi -socket /var/run/php-fpm.sock -idle-timeout 610";
		$f[]="\tAction php-script /php5.fastcgi virtual";
		$f[]="\t<Directory /var/run/artica-apache>";
		$f[]="\t\t<Files php5.fastcgi>";
		//$f[]="\t\tOrder deny,allow";
		//$f[]="\t\tAllow from all";
		$f[]="\t\t</Files>";
		$f[]="\t</Directory>";
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} PHP5-FPM is disabled\n";}
	}	
	
	
	
	
	$f[]="</VirtualHost>";
	
	
	if($artica_webdav<>null){
		$f[]="<VirtualHost *:$ArticaWebDAVHTTPPort>";
		$f[]="\tServerName $ServerName";
		//$f[]="\tEnabledSendfile Off";
		$f[]="\tDavLockDB \"/var/db/apache/DavLock\"";
		$f[]="\tBrowserMatch \"Microsoft Data Access Internet Publishing Provider\" redirect-carefully";
		$f[]="\tBrowserMatch \"^WebDrive\" redirect-carefully";
		$f[]="\tBrowserMatch \"^gnome-vfs\" redirect-carefully";
		$f[]="\tBrowserMatch \"^WebDAVFS/1.[012]\" redirect-carefully";
		$f[]=$artica_webdav;
		$f[]="</VirtualHost>";
		@mkdir("/var/db/apache",0755,true);
		@chown("/var/db/apache", $APACHE_SRC_ACCOUNT);
		@chgrp("/var/db/apache", $APACHE_SRC_GROUP);
		
		
	}else{
		$f[]="# WebDAV OFF...";
	}
	

	
	
	
	if($EnableSquidGuardHTTPToArtica==1){
		$f[]=apache_squidguard_config();
		
	}
	
	
	$f[]="Loglevel info";
	$f[]="ErrorLog /var/log/lighttpd/apache-error.log";
	$f[]="LogFormat \"%h %l %u %t \\\"%r\\\" %<s %b\" common";
	$f[]="CustomLog /var/log/lighttpd/apache-access.log common";
	
	if($EnableArticaApachePHPFPM==0){$array["php5_module"]="libphp5.so";}
	
	
	$array["actions_module"]="mod_actions.so";
	$array["expires_module"]="mod_expires.so";
	$array["rewrite_module"]="mod_rewrite.so";
	$array["dir_module"]="mod_dir.so";
	$array["mime_module"]="mod_mime.so";
	$array["alias_module"]="mod_alias.so";
	$array["auth_basic_module"]="mod_auth_basic.so";
	$array["authn_file_module"]="mod_authn_file.so";	
	//$array["authz_host_module"]="mod_authz_host.so";
	$array["autoindex_module"]="mod_autoindex.so";
	$array["negotiation_module"]="mod_negotiation.so";
	$array["ssl_module"]="mod_ssl.so";
	$array["headers_module"]="mod_headers.so";
	$array["ldap_module"]="mod_ldap.so";
	
	if($artica_webdav<>null){
		$array["setenvif_module"]="mod_setenvif.so";
	}
	
	if($EnableArticaApachePHPFPM==1){$array["fastcgi_module"]="mod_fastcgi.so";}
	
	if(is_dir("/etc/apache2")){
		if(!is_file("/etc/apache2/mime.types")){
			if($apache_LOCATE_MIME_TYPES<>"/etc/apache2/mime.types"){
				@copy($apache_LOCATE_MIME_TYPES, "/etc/apache2/mime.types");
			}
		}
		
	}
	
	
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Mime types path.......: $apache_LOCATE_MIME_TYPES\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Modules path..........: $APACHE_MODULES_PATH\n";}
	
	while (list ($module, $lib) = each ($array) ){
		
		if(is_file("$APACHE_MODULES_PATH/$lib")){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} include module \"$module\"\n";}
			$f[]="LoadModule $module $APACHE_MODULES_PATH/$lib";
		}else{
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} skip module \"$module\"\n";}
		}
	
	}
	
	if($apache_firewall<>null){
		if(is_file("$APACHE_MODULES_PATH/mod_authz_host.so")){echo "Starting......: ".date("H:i:s")." [INIT]: Apache module 'dav_module' enabled\n";$f[]="LoadModule authz_host_module $APACHE_MODULES_PATH/mod_authz_host.so";}
	}
	
	if($artica_webdav<>null){
		if(is_file("$APACHE_MODULES_PATH/mod_dav.so")){echo "Starting......: ".date("H:i:s")." [INIT]: Apache module 'dav_module' enabled\n";$f[]="LoadModule dav_module $APACHE_MODULES_PATH/mod_dav.so";}
		if(is_file("$APACHE_MODULES_PATH/mod_dav_lock.so")){echo "Starting......: ".date("H:i:s")." [INIT]: Apache module 'dav_lock_module' enabled\n";$f[]="LoadModule dav_lock_module $APACHE_MODULES_PATH/mod_dav_lock.so";}
		if(is_file("$APACHE_MODULES_PATH/mod_dav_fs.so")){echo "Starting......: ".date("H:i:s")." [INIT]: Apache module 'dav_fs_module' enabled\n";$f[]="LoadModule dav_fs_module $APACHE_MODULES_PATH/mod_dav_fs.so";}
		if(is_file("$APACHE_MODULES_PATH/mod_authz_user.so")){echo "Starting......: ".date("H:i:s")." [INIT]: Apache module 'dav_fs_module' enabled\n";$f[]="LoadModule authz_user_module $APACHE_MODULES_PATH/mod_authz_user.so";}
	}
	
	$f[]=apache_phpmyadmin();
	
	@file_put_contents("/etc/artica-postfix/httpd.conf", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} /etc/artica-postfix/httpd.conf done\n";}
	
}
function apache_phpmyadmin(){
	$sock=new sockets();$EnablePHPFPM=intval($sock->GET_INFO("EnablePHPFPM"));
	if(!is_dir("/usr/share/phpmyadmin/libraries")){return;}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} PHPMyAdmin is installed\n";}
	$f[]="Alias /mysql /usr/share/phpmyadmin";
	$f[]=" ";
	$f[]="<Directory /usr/share/phpmyadmin/>";
	$f[]=" ";
	$f[]="    DirectoryIndex index.php";
	$f[]="    Options +FollowSymLinks";
	$f[]="    AllowOverride None";
	$f[]="    <IfModule mod_mime.c>";
	$f[]=" ";
	$f[]="      <IfModule mod_php5.c>";
	$f[]="        AddType application/x-httpd-php .php";
	$f[]=" ";
	if($EnablePHPFPM==0){
		$f[]="        php_flag magic_quotes_gpc Off";
		$f[]="        php_flag track_vars On";
		$f[]="        php_flag register_globals Off";
		$f[]="        php_value include_path .";
		$f[]="        php_value memory_limit 32M";
	}
	$f[]="      </IfModule>";
	$f[]=" ";
	$f[]="      <IfModule !mod_php5.c>";
	$f[]="        <IfModule mod_actions.c>";
	$f[]="          <IfModule mod_cgi.c>";
	$f[]="            AddType application/x-httpd-php .php";
	$f[]="            Action application/x-httpd-php /cgi-bin/php5";
	$f[]="          </IfModule>";
	$f[]="          <IfModule mod_cgid.c>";
	$f[]="            AddType application/x-httpd-php .php";
	$f[]="            Action application/x-httpd-php /cgi-bin/php5";
	$f[]="           </IfModule>";
	$f[]="        </IfModule>";
	$f[]="      </IfModule>";
	$f[]=" ";
	$f[]="    </IfModule>";
	$f[]=" ";
	$f[]="</Directory>";
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php /usr/share/artica-postfix/exec.phpmyadmin.php --build");
	return @implode("\n", $f);


}

function apache_phpldapadmin(){
	$sock=new sockets();$EnablePHPFPM=intval($sock->GET_INFO("EnablePHPFPM"));
	if(!is_dir("/usr/share/phpldapadmin/htdocs")){return;}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} PHPLDAPAdmin is installed\n";}
	$f[]="Alias /ldap /usr/share/phpldapadmin/htdocs";
	$f[]=" ";
	$f[]="<Directory /usr/share/phpldapadmin/htdocs/>";
	$f[]=" ";
	$f[]="    DirectoryIndex index.php";
	$f[]="    Options +FollowSymLinks";
	$f[]="    AllowOverride None";
	$f[]="    <IfModule mod_mime.c>";
	$f[]=" ";
	$f[]="      <IfModule mod_php5.c>";
	$f[]="        AddType application/x-httpd-php .php";
	if($EnablePHPFPM==0){
	$f[]=" ";
	$f[]="        php_flag magic_quotes_gpc Off";
	$f[]="        php_flag track_vars On";
	$f[]="        php_flag register_globals Off";
	$f[]="        php_value include_path .";
	$f[]="        php_value memory_limit 32M";
	}
	$f[]="      </IfModule>";
	$f[]=" ";
	$f[]="      <IfModule !mod_php5.c>";
	$f[]="        <IfModule mod_actions.c>";
	$f[]="          <IfModule mod_cgi.c>";
	$f[]="            AddType application/x-httpd-php .php";
	$f[]="            Action application/x-httpd-php /cgi-bin/php5";
	$f[]="          </IfModule>";
	$f[]="          <IfModule mod_cgid.c>";
	$f[]="            AddType application/x-httpd-php .php";
	$f[]="            Action application/x-httpd-php /cgi-bin/php5";
	$f[]="           </IfModule>";
	$f[]="        </IfModule>";
	$f[]="      </IfModule>";
	$f[]=" ";
	$f[]="    </IfModule>";
	$f[]=" ";
	$f[]="</Directory>";
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php /usr/share/artica-postfix/exec.phpldapadmin.php --build");
	return @implode("\n", $f);
	
	
}

function apache_htpassword($clearTextPassword){
	$password = crypt($clearTextPassword, base64_encode($clearTextPassword));
	return $password;
}


function apache_webdav(){
	$q=new mysql();
	$unix=new unix();
	
	$APACHE_SRC_ACCOUNT=$unix->APACHE_SRC_ACCOUNT();
	$APACHE_SRC_GROUP=$unix->APACHE_SRC_GROUP();
	
	if($q->COUNT_ROWS("artica_webdav","artica_backup")==0){return null;}
	$ldap=new clladp();
	$password=apache_htpassword($ldap->ldap_password);
	$username=$ldap->ldap_admin;
	@file_put_contents("/etc/artica-postfix/webdav.passwd", "$username:$password\n");
	
	
	
	$results=$q->QUERY_SQL("SELECT * FROM artica_webdav","artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		$directory=$ligne["directory"];
		@mkdir($directory,0755,true);
		
		if($ligne["write"]==1){
			@chown($directory, $APACHE_SRC_ACCOUNT);
			@chgrp($directory, $APACHE_SRC_GROUP);
			
		}
		
		$basename=basename($directory);
		$f[]="";
		$f[]="\tAlias /$basename $directory";
		$f[]="";
		$f[]="\t<Location /$basename>";
		$f[]="\t\tOptions Indexes";
		$f[]="\t\tDAV On";
		$f[]="\t\tAuthType Basic";
		$f[]="\t\tAuthName \"$basename\"";
		$f[]="\t\tAuthUserFile /etc/artica-postfix/webdav.passwd";
		$f[]="\t\tRequire valid-user";
		$f[]="\t</Location>";
		
		
	}
	if(count($f)>0){
		return @implode("\n", $f);
	}
	
	
	
}
function apache_firewall(){
	$q=new mysql();
	$unix=new unix();

	$APACHE_SRC_ACCOUNT=$unix->APACHE_SRC_ACCOUNT();
	$APACHE_SRC_GROUP=$unix->APACHE_SRC_GROUP();

	if($q->COUNT_ROWS("iptables_webint","artica_backup")==0){return null;}
	$f[]="Order Allow,Deny";
	$ipClass=new IP();

	$c=0;
	$results=$q->QUERY_SQL("SELECT * FROM iptables_webint","artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		$pattern=$ligne["pattern"];
		if(!$ipClass->isIPAddressOrRange($pattern)){continue;}
		$f[]="\tAllow from $pattern";
		$c++;

	}
	if(count($c)>0){
		$f[]="\tAllow from 127.0.0.1";
		return @implode("\n", $f);
	}



}

function apache_squidguard_config(){
	$unix=new unix();
	$sock=new sockets();
	$SquidGuardApachePort=intval($sock->GET_INFO("SquidGuardApachePort"));
	$SquidGuardApacheSSLPort=intval($sock->GET_INFO("SquidGuardApacheSSLPort"));
	if($SquidGuardApachePort==0){$SquidGuardApachePort=9020;}
	if($SquidGuardApacheSSLPort==0){$SquidGuardApacheSSLPort=9025;}
	$SquidGuardWebSSLCertificate=$sock->GET_INFO("SquidGuardWebSSLCertificate");
	
	$f[]="";
	$f[]="";
	$f[]="<VirtualHost *:$SquidGuardApachePort>";
	$f[]="	AddType application/x-gtar-compressed .tgz";
	$f[]="	AddType application/x-gzip 	.gz";
	$f[]="  AddType application/x-httpd-php .php";
	$f[]="	ServerName ".$unix->hostname_g();
	$f[]="	ServerSignature Off";
	$f[]="	DocumentRoot /usr/share/artica-postfix";
	$f[]="	php_value include_path \".:/usr/share/php:/usr/share/artica-postfix:/usr/share/php5:/usr/local/share/php:/usr/share/php5/PEAR:/usr/share/pear:/tmp\"";
	$f[]="	php_value error_log \"/var/log/php.log\"";
	$f[]="	php_value log_errors \"On\"";
	$f[]="	php_value open_basedir \"/usr/share/artica-postfix:/var/log/apache2:/home/artica:/var/log:/var/run/mysqld:/tmp:/usr/share/php:/usr/share/php5:/var/lighttpd/upload:/var/lib/php5:/usr/share/artica-postfix/ressources:/usr/share/artica-postfix/framework:/usr/share/artica-postfix/user-backup:/usr/share/artica-postfix/user-backup/ressources:/etc/ssl/certs/mysql-client-download:/usr/share/artica-postfix/LocalDatabases:/var/run:/bin:/usr/sbin:/etc/artica-postfix/settings\"";
	$f[]="";
	$f[]="	<Directory \"/usr/share/artica-postfix/\">";
	$f[]="		DirectoryIndex ufdbguardd.php";
	$f[]="		Options Indexes +FollowSymLinks +SymLinksIfOwnerMatch MultiViews";
	$f[]="		IndexIgnore *";
	$f[]="		AllowOverride All";
	$f[]="	</Directory>";
	$f[]="";
	$f[]="";
	$f[]="</VirtualHost>";
	$f[]="";
	
	$cert=new apache_certificate($SquidGuardWebSSLCertificate);
	$f[]="<VirtualHost *:$SquidGuardApacheSSLPort>";
	$f[]="  SSLEngine on";
	$f[]=$cert->build();
	$f[]="	AddType application/x-gtar-compressed .tgz";
	$f[]="	AddType application/x-gzip 	.gz";
	$f[]="  AddType application/x-httpd-php .php";
	$f[]="	ServerName ".$unix->hostname_g();
	$f[]="	ServerSignature Off";
	$f[]="	DocumentRoot /usr/share/artica-postfix";
	$f[]="	php_value include_path \".:/usr/share/php:/usr/share/artica-postfix:/usr/share/php5:/usr/local/share/php:/usr/share/php5/PEAR:/usr/share/pear:/tmp\"";
	$f[]="	php_value error_log \"/var/log/php.log\"";
	$f[]="	php_value log_errors \"On\"";
	$f[]="	php_value open_basedir \"/usr/share/artica-postfix:/var/log/apache2:/home/artica:/var/log:/var/run/mysqld:/tmp:/usr/share/php:/usr/share/php5:/var/lighttpd/upload:/var/lib/php5:/usr/share/artica-postfix/ressources:/usr/share/artica-postfix/framework:/usr/share/artica-postfix/user-backup:/usr/share/artica-postfix/user-backup/ressources:/etc/ssl/certs/mysql-client-download:/usr/share/artica-postfix/LocalDatabases:/var/run:/bin:/usr/sbin:/etc/artica-postfix/settings\"";
	$f[]="";
	$f[]="	<Directory \"/usr/share/artica-postfix/\">";
	$f[]="		DirectoryIndex ufdbguardd.php";
	$f[]="		Options Indexes +FollowSymLinks +SymLinksIfOwnerMatch MultiViews";
	$f[]="		IndexIgnore *";
	$f[]="		AllowOverride All";
	$f[]="	</Directory>";
	$f[]="";
	$f[]="";
	$f[]="</VirtualHost>";	
	
	return @implode("\n", $f);
	
}


function apache_nagios_config(){
	return;
	if(!is_dir("/usr/share/nagios3")){return;}
	$f[]="## apache configuration for nagios 3.x";
	$f[]="# note to users of nagios 1.x and 2.x:";
	$f[]="#	throughout this file are commented out sections which preserve";
	$f[]="#	backwards compatibility with bookmarks/config for older nagios versios.";
	$f[]="#	simply look for lines following \"nagios 1.x:\" and \"nagios 2.x\" comments.";
	$f[]="";
	$f[]="ScriptAlias /cgi-bin/nagios3 /usr/lib/cgi-bin/nagios3";
	$f[]="ScriptAlias /nagios3/cgi-bin /usr/lib/cgi-bin/nagios3";
	$f[]="# nagios 1.x:";
	$f[]="#ScriptAlias /cgi-bin/nagios /usr/lib/cgi-bin/nagios3";
	$f[]="#ScriptAlias /nagios/cgi-bin /usr/lib/cgi-bin/nagios3";
	$f[]="# nagios 2.x: ";
	$f[]="#ScriptAlias /cgi-bin/nagios2 /usr/lib/cgi-bin/nagios3";
	$f[]="#ScriptAlias /nagios2/cgi-bin /usr/lib/cgi-bin/nagios3";
	$f[]="";
	$f[]="# Where the stylesheets (config files) reside";
	$f[]="Alias /nagios3/stylesheets /etc/nagios3/stylesheets";
	$f[]="# nagios 1.x:";
	$f[]="#Alias /nagios/stylesheets /etc/nagios3/stylesheets";
	$f[]="# nagios 2.x:";
	$f[]="#Alias /nagios2/stylesheets /etc/nagios3/stylesheets";
	$f[]="";
	$f[]="# Where the HTML pages live";
	$f[]="Alias /nagios3 /usr/share/nagios3/htdocs";
	$f[]="# nagios 2.x: ";
	$f[]="#Alias /nagios2 /usr/share/nagios3/htdocs";
	$f[]="# nagios 1.x:";
	$f[]="#Alias /nagios /usr/share/nagios3/htdocs";
	$f[]="";
	$f[]="<DirectoryMatch (/usr/share/nagios3/htdocs|/usr/lib/cgi-bin/nagios3|/etc/nagios3/stylesheets)>";
	$f[]="	Options FollowSymLinks";
	$f[]="";
	$f[]="	DirectoryIndex index.php";
	//$f[]="	Order Allow,Deny";
	//$f[]="	Allow From All";
	$f[]="</DirectoryMatch>";
	$f[]="";
	$f[]="# Enable this ScriptAlias if you want to enable the grouplist patch.";
	$f[]="# See http://apan.sourceforge.net/download.html for more info";
	$f[]="# It allows you to see a clickable list of all hostgroups in the";
	$f[]="# left pane of the Nagios web interface";
	$f[]="# XXX This is not tested for nagios 2.x use at your own peril";
	$f[]="#ScriptAlias /nagios3/side.html /usr/lib/cgi-bin/nagios3/grouplist.cgi";
	$f[]="# nagios 1.x:";
	$f[]="#ScriptAlias /nagios/side.html /usr/lib/cgi-bin/nagios3/grouplist.cgi";	
	return @implode("\n", $f);
}


function buildConfig(){
	$unix=new unix();
	$sock=new sockets();
	$phpcgi=$unix->LIGHTTPD_PHP5_CGI_BIN_PATH();
	$chown=$unix->find_program("chown");
	$perlbin=$unix->find_program("perl");
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$PHP_STANDARD_MODE=true;
	
	$phpfpm=$unix->find_program('php5-fpm')  ;
	if(!is_file($phpfpm)){$phpfpm=$unix->find_program('php-fpm');}
	
	@mkdir("/usr/share/artica-postfix/framework",0755,true);
	@mkdir("/usr/share/artica-postfix/ressources/sock",0755,true);

	$LighttpdRunAsminimal=$sock->GET_INFO("LighttpdRunAsminimal");
	$LighttpdArticaMaxProcs=$sock->GET_INFO("LighttpdArticaMaxProcs");
	$LighttpdArticaMaxChildren=$sock->GET_INFO("LighttpdArticaMaxChildren");
	$PHP_FCGI_MAX_REQUESTS=$sock->GET_INFO("PHP_FCGI_MAX_REQUESTS");
	$SessionPathInMemory=$sock->GET_INFO("SessionPathInMemory");
	if(!is_numeric($LighttpdRunAsminimal)){$LighttpdRunAsminimal=0;}
	if(!is_numeric($LighttpdArticaMaxProcs)){$LighttpdArticaMaxProcs=0;}
	if(!is_numeric($LighttpdArticaMaxChildren)){$LighttpdArticaMaxChildren=0;}
	if(!is_numeric($PHP_FCGI_MAX_REQUESTS)){$PHP_FCGI_MAX_REQUESTS=200;}
	if(!is_numeric($SessionPathInMemory)){$SessionPathInMemory=0;}
	$EnableArticaApachePHPFPM=$sock->GET_INFO("EnableArticaApachePHPFPM");
	if(!is_numeric($EnableArticaApachePHPFPM)){$EnableArticaApachePHPFPM=0;}
	
	
	
	
	
	if(!is_file("/opt/artica/ssl/certs/lighttpd.pem")){
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} creating SSL certificate..\n";}
		exec("/usr/share/artica-postfix/bin/artica-install -lighttpd-cert 2>&1",$results);
		while (list ($pid, $line) = each ($results) ){
			$line=trim($line);
			if($line==null){continue;}
			if(preg_match("#Starting.*?lighttpd(.+)#", $line,$re)){$line=$re[1];}
			$line=str_replace(": ", "", $line);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [ARTI]: {$GLOBALS["SERVICE_NAME"]} $line\n";}
		}
		
	}
	$results=array();
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Creating PHP configuration..\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Executing PHP.INI\n";}
	system("$php /usr/share/artica-postfix/exec.php.ini.php");
	PHP_MYADMIN();
	
	
	$PHP_FCGI_CHILDREN=3;
	$max_procs=3;
	
	
	if($LighttpdArticaMaxProcs>0){$max_procs=$LighttpdArticaMaxProcs;}
	if($LighttpdArticaMaxChildren>0){$PHP_FCGI_CHILDREN=$LighttpdArticaMaxChildren;}
	
	if(!$unix->ISMemoryHiger1G()){
		$PHP_FCGI_CHILDREN=2;
		$max_procs=1;
	}
	
	$MEMORY=$unix->MEM_TOTAL_INSTALLEE();
	if($MEMORY<624288){$LighttpdRunAsminimal=1;}
	
	if($LighttpdRunAsminimal==1){
		$max_procs=1;
		$PHP_FCGI_CHILDREN=2;
		$PHP_FCGI_MAX_REQUESTS=500;
	}
	

	
	$phpfpm=$unix->APACHE_LOCATE_PHP_FPM();
	$EnablePHPFPM=intval($sock->GET_INFO("EnablePHPFPM"));
	if(!is_numeric($EnablePHPFPM)){$EnablePHPFPM=0;}
	if(!is_file($phpfpm)){$EnablePHPFPM=0;}
	if($EnablePHPFPM==0){$EnableArticaApachePHPFPM=0;}
	if($EnableArticaApachePHPFPM==0){$EnablePHPFPM=0;}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} EnableArticaApachePHPFPM = $EnableArticaApachePHPFPM\n";}
	if($EnablePHPFPM==1){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Using PHP-FPM........: Yes\n";}
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Using PHP-FPM........: No\n";}
	}
	
	$ArticaHttpsPort=9000;
	$NoLDAPInLighttpdd=0;
	$ArticaHttpUseSSL=1;
	
	$ArticaHttpsPort=$sock->GET_INFO("ArticaHttpsPort");
	$ArticaHttpUseSSL=$sock->GET_INFO("ArticaHttpUseSSL");
	if(!is_numeric($ArticaHttpUseSSL)){$ArticaHttpUseSSL=1;}
	if(!is_numeric($ArticaHttpsPort)){$ArticaHttpsPort="9000";}
	
	$ArticaHttpUseSSL=$sock->GET_INFO('ArticaHttpUseSSL');
	if(!is_numeric($ArticaHttpUseSSL)){$ArticaHttpUseSSL=1;}
	
	$NoLDAPInLighttpdd=$sock->GET_INFO('NoLDAPInLighttpdd');
	if(!is_numeric($NoLDAPInLighttpdd)){$NoLDAPInLighttpdd=0;} 

	$LighttpdUseUnixSocket=$sock->GET_INFO('LighttpdUseUnixSocket');
	if(!is_numeric($LighttpdUseUnixSocket)){$LighttpdUseUnixSocket=0;}

	$lighttpdPhpPort=$sock->GET_INFO('lighttpdPhpPort');
	if(!is_numeric($lighttpdPhpPort)){$lighttpdPhpPort=1808;}
	 
	$DenyMiniWebFromStandardPort=$sock->GET_INFO('DenyMiniWebFromStandardPort');
	if(!is_numeric($DenyMiniWebFromStandardPort)){$DenyMiniWebFromStandardPort=0;}
		 
	$LighttpdArticaDisableSSLv2=$sock->GET_INFO('LighttpdArticaDisableSSLv2');
	if(!is_numeric($LighttpdArticaDisableSSLv2)){$LighttpdArticaDisableSSLv2=1;}
 
	$LighttpdArticaMaxProcs=$sock->GET_INFO('LighttpdArticaMaxProcs');
	if(!is_numeric($LighttpdArticaMaxProcs)){$LighttpdArticaMaxProcs=0;}
	
	$LighttpdArticaMaxChildren=$sock->GET_INFO('LighttpdArticaMaxChildren');
	if(!is_numeric($LighttpdArticaMaxChildren)){$LighttpdArticaMaxChildren=0;}
	
	
	$LighttpdRunAsminimal=$sock->GET_INFO('LighttpdRunAsminimal');
	if(!is_numeric($LighttpdRunAsminimal)){$LighttpdRunAsminimal=0;}
	
	$PHP_FCGI_MAX_REQUESTS=$sock->GET_INFO('PHP_FCGI_MAX_REQUESTS');
	if(!is_numeric($PHP_FCGI_MAX_REQUESTS)){$PHP_FCGI_MAX_REQUESTS=200;}
	
	
	$EnableArticaApachePHPFPM=$sock->GET_INFO("EnableArticaApachePHPFPM");
	if(!is_numeric($EnableArticaApachePHPFPM)){$EnableArticaApachePHPFPM=0;}
	if(!is_file($phpfpm)){$EnableArticaApachePHPFPM=0;}
	
	$EnablePHPFPM=intval($sock->GET_INFO("EnablePHPFPM"));
	if(!is_numeric($EnablePHPFPM)){$EnablePHPFPM=0;}
	if($EnablePHPFPM==0){$EnableArticaApachePHPFPM=0;}
	if($EnableArticaApachePHPFPM==0){$EnablePHPFPM=0;}
	
	
	$PHP_STANDARD_MODE=true;
	$LighttpdArticaListenIP=$sock->GET_INFO('LighttpdArticaListenIP');	
	$phpcgi_path=$unix->LIGHTTPD_PHP5_CGI_BIN_PATH();
	$LIGHTTPD_GET_USER=LIGHTTPD_GET_USER();
	$LIGHTTPD_CONF_PATH=LIGHTTPD_CONF_PATH();
	
	if(preg_match("#^(.+?):(.+)#", $LIGHTTPD_GET_USER,$re)){
		$LIGHTTPD_USER=$re[1];
		$LIGHTTPD_GROUP=$re[1];
	}
	
	$PHP_FCGI_CHILDREN=1;
	$max_procs=2;
	
	@mkdir("/var/log/lighttpd",0755,true);
	@mkdir("/usr/share/artica-postfix/ressources/logs",0755,true);
	if(!is_file("/var/log/lighttpd/access.log")){@touch("/var/log/lighttpd/access.log");}
	@chown("/var/log/lighttpd", $LIGHTTPD_USER);
	@chgrp("/var/log/lighttpd", $LIGHTTPD_GROUP);
	@chown("/var/log/lighttpd/access.log", $LIGHTTPD_USER);
	@chgrp("/var/log/lighttpd/access.log", $LIGHTTPD_GROUP);
	@chmod("/var/log/lighttpd/access.log",0777);
	
	
	$unix->chown_func($LIGHTTPD_USER, $LIGHTTPD_GROUP,"/var/log/lighttpd/*");
	$unix->chown_func($LIGHTTPD_USER, $LIGHTTPD_GROUP,"/usr/share/artica-postfix/ressources/logs/*");
	
	
	
	if($LighttpdArticaMaxProcs>0){$max_procs=$LighttpdArticaMaxProcs;}
	if($LighttpdArticaMaxChildren>0){$HP_FCGI_CHILDREN=$LighttpdArticaMaxChildren;}	
	
	if($LighttpdRunAsminimal==1){
		$max_procs=2;
		$PHP_FCGI_CHILDREN=2;
	}
	$mod_auth=isModule('mod_auth');  
	
	if(is_file('/proc/user_beancounters')){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} VPS mode enabled, swith to socket mode for PHP\n";}
		$LighttpdUseUnixSocket=1;
	}
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} MAX Procs............: $max_procs\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Php5 processes.......: $PHP_FCGI_CHILDREN\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Max cnx/processes....: $PHP_FCGI_MAX_REQUESTS\n";}	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} php-cgi path.........: $phpcgi_path\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} chown path...........: $chown\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} php path.............: $php\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} php FPM Path.........: $phpfpm\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} php FPM Enabled......: $EnableArticaApachePHPFPM\n";}
	
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Perl Path............: $perlbin\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Conf Path............: $LIGHTTPD_CONF_PATH\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Pid Path.............: /var/run/lighttpd/lighttpd.pid\n";}
	
	
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} SSL enabled..........: $ArticaHttpUseSSL\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Disable SSLv2........: $LighttpdArticaDisableSSLv2\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Listen Port..........: $ArticaHttpsPort\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Run as...............: $LIGHTTPD_USER / $LIGHTTPD_GROUP\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} No LDAP in Lighttpd..: $NoLDAPInLighttpdd\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Mod auth installed...: $mod_auth\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Use Unix socket......: $LighttpdUseUnixSocket\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Sessions in Memory...: {$SessionPathInMemory}MB\n";}
	
	
	
	
	$MakeDirs[]="/opt/artica/ssl/certs";
	$MakeDirs[]="/var/lib/php/session";
	$MakeDirs[]="/var/lighttpd/upload";
	$MakeDirs[]="/var/run/lighttpd";
	$MakeDirs[]="/var/log/lighttpd";
	$MakeDirs[]="/opt/artica/share/www/jpegPhoto";
	$MakeDirs[]=dirname($LIGHTTPD_CONF_PATH);

	
	while (list ($pid, $dir) = each ($MakeDirs) ){
		if(!is_dir($dir)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} creating $dir\n";}
		}
		@mkdir($dir,0755,true);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} permissions on $dir\n";}
		shell_exec("$chown $LIGHTTPD_GET_USER $dir");
		
	}
	
	$f[]='#artica-postfix saved by artica lighttpd.conf (Artica Install binary) v3.0';
	$f[]='';
	$f[]='server.modules = (';
	$f[]='        "mod_alias",';
	$f[]='        "mod_access",';
	$f[]='        "mod_accesslog",';
	$f[]='        "mod_compress",';
	$f[]='        "mod_fastcgi",';
	$f[]='        "mod_cgi",';
	$f[]='	       "mod_status",';
	if($NoLDAPInLighttpdd==1){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} LDAP Mode is disabled\n";}
	}
	if($mod_auth){$f[]='	       "mod_auth"';}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} mod_auth module does not exists (should be a security issue !!!)\n";}
		
	}
	$f[]=')';
	$f[]='';
	$f[]='server.document-root        = "/usr/share/artica-postfix"';
	$f[]='server.username = "'.$LIGHTTPD_USER.'"';
	$f[]='server.groupname = "'.$LIGHTTPD_GROUP.'"';
	$f[]='server.errorlog-use-syslog = "enable"';
	//$f[]='server.errorlog             = "/var/log/lighttpd/error.log"';
	$f[]='index-file.names            = ( "index.php","index.cgi")';
	$f[]='';
	$f[]='mimetype.assign             = (';
	$f[]='  ".pdf"          =>      "application/pdf",';
	$f[]='  ".sig"          =>      "application/pgp-signature",';
	$f[]='  ".spl"          =>      "application/futuresplash",';
	$f[]='  ".class"        =>      "application/octet-stream",';
	$f[]='  ".ps"           =>      "application/postscript",';
	$f[]='  ".torrent"      =>      "application/x-bittorrent",';
	$f[]='  ".dvi"          =>      "application/x-dvi",';
	$f[]='  ".gz"           =>      "application/x-gzip",';
	$f[]='  ".pac"          =>      "application/x-ns-proxy-autoconfig",';
	$f[]='  ".swf"          =>      "application/x-shockwave-flash",';
	$f[]='  ".tar.gz"       =>      "application/x-tgz",';
	$f[]='  ".tgz"          =>      "application/x-tgz",';
	$f[]='  ".tar"          =>      "application/x-tar",';
	$f[]='  ".zip"          =>      "application/zip",';
	$f[]='  ".mp3"          =>      "audio/mpeg",';
	$f[]='  ".m3u"          =>      "audio/x-mpegurl",';
	$f[]='  ".wma"          =>      "audio/x-ms-wma",';
	$f[]='  ".wax"          =>      "audio/x-ms-wax",';
	$f[]='  ".ogg"          =>      "application/ogg",';
	$f[]='  ".wav"          =>      "audio/x-wav",';
	$f[]='  ".gif"          =>      "image/gif",';
	$f[]='  ".jar"          =>      "application/x-java-archive",';
	$f[]='  ".jpg"          =>      "image/jpeg",';
	$f[]='  ".jpeg"         =>      "image/jpeg",';
	$f[]='  ".png"          =>      "image/png",';
	$f[]='  ".xbm"          =>      "image/x-xbitmap",';
	$f[]='  ".xpm"          =>      "image/x-xpixmap",';
	$f[]='  ".xwd"          =>      "image/x-xwindowdump",';
	$f[]='  ".css"          =>      "text/css",';
	$f[]='  ".html"         =>      "text/html",';
	$f[]='  ".htm"          =>      "text/html",';
	$f[]='  ".js"           =>      "text/javascript",';
	$f[]='  ".asc"          =>      "text/plain",';
	$f[]='  ".c"            =>      "text/plain",';
	$f[]='  ".cpp"          =>      "text/plain",';
	$f[]='  ".log"          =>      "text/plain",';
	$f[]='  ".conf"         =>      "text/plain",';
	$f[]='  ".text"         =>      "text/plain",';
	$f[]='  ".txt"          =>      "text/plain",';
	$f[]='  ".dtd"          =>      "text/xml",';
	$f[]='  ".xml"          =>      "text/xml",';
	$f[]='  ".mpeg"         =>      "video/mpeg",';
	$f[]='  ".mpg"          =>      "video/mpeg",';
	$f[]='  ".mov"          =>      "video/quicktime",';
	$f[]='  ".qt"           =>      "video/quicktime",';
	$f[]='  ".avi"          =>      "video/x-msvideo",';
	$f[]='  ".asf"          =>      "video/x-ms-asf",';
	$f[]='  ".asx"          =>      "video/x-ms-asf",';
	$f[]='  ".wmv"          =>      "video/x-ms-wmv",';
	$f[]='  ".bz2"          =>      "application/x-bzip",';
	$f[]='  ".tbz"          =>      "application/x-bzip-compressed-tar",';
	$f[]='  ".tar.bz2"      =>      "application/x-bzip-compressed-tar",';
	$f[]='  ""              =>      "application/octet-stream",';
	$f[]=' )';
	$f[]='';
	$f[]='';
	$f[]='accesslog.filename          = "/var/log/lighttpd/access.log"';
	$f[]='url.access-deny             = ( "~", ".inc",".log",".ini" )';
	$f[]='';
	$f[]='static-file.exclude-extensions = ( ".php", ".pl", ".fcgi" )';
	$f[]='server.port                 = '.$ArticaHttpsPort;
	
	
	
	if($LighttpdArticaListenIP<>null){
		$unix=new unix();
		$IPS=$unix->NETWORK_ALL_INTERFACES(true);
		if(!isset($IPS[$LighttpdArticaListenIP])){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} ERROR! Listen IP: $LighttpdArticaListenIP -> FALSE !!\n";}
			$LighttpdArticaListenIP=null;
		}
	}
	
	
	if(strlen($LighttpdArticaListenIP)>3){$f[]='server.bind                = "'.$LighttpdArticaListenIP.'"';}
	$f[]='server.pid-file             = "/var/run/lighttpd/lighttpd.pid"';
	$f[]='server.max-fds 		   = 2048';
	$f[]='server.max-connections      = 512';
	$f[]='server.network-backend      = "write"';

	
	shell_exec("$php /usr/share/artica-postfix/exec.lighttpd.nets.php");
	shell_exec("$php /usr/share/artica-postfix/exec.lighttpd.nets.php --phpmyadmin");
	
	if(is_file('/etc/artica-postfix/lighttpd_nets')){$f[]=@file_get_contents("/etc/artica-postfix/lighttpd_nets");}
	
	$f[]='';
	if(is_file($phpfpm)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} PHP-FPM is installed\n";}
		if($EnablePHPFPM==1){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} PHP-FPM is enabled\n";}
			$PHP_STANDARD_MODE=false;
			$f[]='fastcgi.server = ( ".php" =>((';
			$f[]='         "socket" => "/var/run/php-fpm.sock",';
		}
	}
	
	
	
	if ($PHP_STANDARD_MODE){
		$f[]='fastcgi.server = ( ".php" =>((';
		$f[]='         "bin-path" => "/usr/bin/php-cgi",';
		if($LighttpdUseUnixSocket==1){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Fast-cgi server unix socket mode\n";}
			$f[]='         "socket" => "/var/run/lighttpd/php.socket" + var.PID,';
		}else{
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Fast-cgi server socket 127.0.0.1:$lighttpdPhpPort\n";}
			$f[]='         "host" => "127.0.0.1","port" =>'.$lighttpdPhpPort.',';
		}
	}
	$f[]='         "min-procs" => 1,';
	$f[]='         "max-procs" => 1,';
	$f[]='         "idle-timeout" => 10,';
	$f[]='         "bin-environment" => (';
	$f[]='             "PHP_FCGI_CHILDREN" => "'.$PHP_FCGI_CHILDREN.'",';
	$f[]='             "PHP_FCGI_MAX_REQUESTS" => "'.$PHP_FCGI_MAX_REQUESTS.'"';
	$f[]='          ),';
	$f[]='          "bin-copy-environment" => (';
	$f[]='            "PATH", "SHELL", "USER"';
	$f[]='           ),';
	$f[]='          "broken-scriptfilename" => "enable"';
	$f[]='        ))';
	$f[]=')';
	
	
	if($ArticaHttpUseSSL==1){
		$f[]='ssl.engine                 = "enable"';
		$f[]='ssl.pemfile                = "/opt/artica/ssl/certs/lighttpd.pem"';
	}
	
	if($LighttpdArticaDisableSSLv2==1){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Disable SSLv2 and weak ssl cipher\n";}
		$f[]='ssl.use-sslv2              = "disable"';
		$f[]='ssl.cipher-list            = "TLSv1+HIGH !SSLv2 RC4+MEDIUM !aNULL !eNULL !3DES @STRENGTH"';
	}else{
		$f[]='ssl.use-sslv2              = "enable"';
		$f[]='ssl.cipher-list            = "TLSv1+HIGH RC4+MEDIUM !SSLv2 !3DES !aNULL @STRENGTH"';
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} No LDAP In lighttpd: $NoLDAPInLighttpdd\n";}
	if($NoLDAPInLighttpdd==0){
		if($mod_auth){
			$f[]='status.status-url          = "/server-status"';
			$f[]='status.config-url          = "/server-config"';
		}
	}
	
	$f[]='server.upload-dirs         = ( "/var/lighttpd/upload" )';
	
	$f[]='	server.follow-symlink = "enable"';
	$f[]='alias.url +=("/monitorix"  => "/var/www/monitorix/")';
	$f[]='alias.url += ("/blocked_attachments"=> "/var/spool/artica-filter/bightml")';
	$f[]='alias.url += ("/squid-rrd"=> "/opt/artica/share/www/squid/rrd")';
	$f[]='alias.url += ("/artica-agent"=> "/usr/share/artica-postfix/ressources/artica-agent")';
	
	
	
	if($DenyMiniWebFromStandardPort==1){
		$f[]='$HTTP["url"] =~ "^/miniadm.*|/computers|/user-backup" { url.access-deny = ( "" )}';
	}
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} checking AWSTATS...\n";}
	$AWSTATS_www_root=AWSTATS_www_root();
	
	$f[]='$HTTP["url"] =~ "^/prxy.*\.php" { url.access-deny = ( "" )}';
	if( is_dir( $AWSTATS_www_root ) ){ $f[]='alias.url += ( "/awstats" => "'.$AWSTATS_www_root.'" )';}
	if(is_file('/usr/share/poweradmin/index.php')){
		$f[]='alias.url += ( "/powerdns" => "/usr/share/poweradmin" )';
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Checking PowerAdmin\n";}
		shell_exec("$nohup $php /usr/share/artica-postfix/exec.pdns.php --poweradmin >/dev/null 2>&1 &");
	}

	//$perlbin
	$f[]='alias.url += ( "/cgi-bin/" => "/usr/lib/cgi-bin/" )';


	
	$f[]='';
	$f[]='cgi.assign= (';
	$f[]='	".pl"  => "'.$perlbin.'",';
	$f[]='	".php" => "/usr/bin/php-cgi",';
	$f[]='	".py"  => "/usr/bin/python",';
	$f[]='	".cgi"  => "'.$perlbin.'",';
	

	
	if(is_file("/usr/lib/mailman/bin/mailmanctl")){
		$f[]='"/admin" => "",';
		$f[]='"/admindb" => "",';
		$f[]='"/confirm" => "",';
		$f[]='"/create" => "",';
		$f[]='"/edithtml" => "",';
		$f[]='"/listinfo" => "",';
		$f[]='"/options" => "",';
		$f[]='"/private" => "",';
		$f[]='"/rmlist" => "",';
		$f[]='"/roster" => "",';
		$f[]='"/subscribe" => ""';
	}
	$f[]=')';
	$f[]='';
	if($mod_auth){
		$f[]='auth.debug = 2';
		$f[]='$HTTP["url"] =~ "^/cgi-bin/" {';
		$f[]='auth.backend = "plain"';
		$f[]='auth.backend.plain.userfile = "/etc/lighttpd/.lighttpdpassword" ';
		$f[]='auth.require = ("/cgi-bin/" => (';
		$f[]='     "method"  => "basic",';
		$f[]='     "realm"   => "awstats Statistics",';
		$f[]='     "require" => "valid-user"';
		$f[]='  ))';
		$f[]='}';
		$f[]='';

	
		$f[]='$HTTP["url"] =~ "^/server-status" {';
		$f[]='auth.backend = "plain"';
		$f[]='auth.backend.plain.userfile = "/etc/lighttpd/.lighttpdpassword" ';
		$f[]='auth.require = ("/server-status" => (';
		$f[]='     "method"  => "basic",';
		$f[]='     "realm"   => "Lighttpd config - status",';
		$f[]='     "require" => "valid-user"';
		$f[]='  ))';
		$f[]='}';
		$f[]='';
		
		$f[]='$HTTP["url"] =~ "^/server-config" {';
		$f[]='auth.backend = "plain"';
		$f[]='auth.backend.plain.userfile = "/etc/lighttpd/.lighttpdpassword" ';
		$f[]='auth.require = ("/server-config" => (';
		$f[]='     "method"  => "basic",';
		$f[]='     "realm"   => "Lighttpd config - status",';
		$f[]='     "require" => "valid-user"';
		$f[]='  ))';
		$f[]='}';
		$f[]='';
		
		$f[]='$HTTP["url"] =~ "^/squid/" {';
		$f[]='auth.backend = "plain"';
		$f[]='auth.debug = 2';
		$f[]='auth.backend.plain.userfile = "/etc/lighttpd/squid-users.passwd" ';
		$f[]='auth.require = ("/squid/" => (';
		$f[]='     "method"  => "basic",';
		$f[]='     "realm"   => "Squid Statistics",';
		$f[]='     "require" => "valid-user"';
		$f[]='  ))';
		$f[]='}';
		$f[]='';
		
		$f[]='$HTTP["url"] =~ "^/cluebringer/" {';
		$f[]='auth.backend = "plain"';
		$f[]='auth.debug = 2';
		$f[]='auth.backend.plain.userfile = "/etc/lighttpd/cluebringer.passwd" ';
		$f[]='auth.require = ("/cluebringer/" => (';
		$f[]='     "method"  => "basic",';
		$f[]='     "realm"   => "ClueBringer (Policyd V2) administration",';
		$f[]='     "require" => "valid-user"';
		$f[]='  ))';
		$f[]='}';
		$f[]='';
	}	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} writing $LIGHTTPD_CONF_PATH..\n";}
	@file_put_contents($LIGHTTPD_CONF_PATH, @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $LIGHTTPD_CONF_PATH done\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Check sessions...\n";}
	
	shell_exec("$nohup $php /usr/share/artica-postfix/exec.shm.php --SessionMem >/dev/null 2>&1 &");
	shell_exec("$nohup $php /usr/share/artica-postfix/exec.shm.php --service-up >/dev/null 2>&1 &");
	
	
}
function PHP_MYADMIN(){
	$sock=new sockets();
	$phpmyadminAllowNoPassword=$sock->GET_INFO("phpmyadminAllowNoPassword");
	if(!is_numeric($phpmyadminAllowNoPassword)){$phpmyadminAllowNoPassword=0;}
	if(!is_file('/usr/share/phpmyadmin/index.php')){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} PhpMyAdmin: /usr/share/phpmyadmin/index.php no such file\n";}
		return;
	}
	include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
	
	if(is_file('/etc/artica-postfix/phpmyadmin_config.txt')){$phpmyadmin_config_add=@file_get_contents('/etc/artica-postfix/phpmyadmin_config.txt');}
	@mkdir("/usr/share/phpmyadmin/config",0755,true);
	
	
	$database_password=@file_get_contents("/etc/artica-postfix/settings/Mysql/database_password");
	if(database_password=='!nil'){$database_password=null;}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} PhpMyAdmin: AllowNoPassword=$phpmyadminAllowNoPassword\n";}
	//$phpmyadminAllowNoPassword
	
	$q=new mysql();
	$f[]='<?php';
	$f[]='/* Servers configuration */';
	$f[]='$i = 0;';
	$f[]='';
	$f[]='/* Server: Artica Mysql [1] */';
	$f[]='$i++;';
	$f[]='$cfg["Servers"][$i]["verbose"] = "Artica Mysql";';
	$f[]='$cfg["Servers"][$i]["host"] = "'.$q->mysql_server.'";';
	$f[]='$cfg["Servers"][$i]["port"] = '.$q->mysql_port.';';
	$f[]='$cfg["Servers"][$i]["socket"] = "'.$q->SocketName.'";';
	$f[]='$cfg["Servers"][$i]["connect_type"] = "tcp";';
	$f[]='$cfg["Servers"][$i]["extension"] = "mysql";';
	$f[]='$cfg["Servers"][$i]["auth_type"] = "cookie";';
	$f[]='$cfg["Servers"][$i]["user"] = "'.$q->mysql_admin.'";';
	$f[]='$cfg["Servers"][$i]["password"] = "'.$q->mysql_password.'";';
	if($phpmyadminAllowNoPassword==1){  $f[]='$cfg["Servers"][$i]["AllowNoPassword"] = True;';}
	if(is_file('/opt/squidsql/bin/mysqld')){
		$f[]='$i++;';
		$f[]='$cfg["Servers"][$i]["verbose"] = "Squid Mysql";';
		$f[]='$cfg["Servers"][$i]["socket"] = "/var/run/mysqld/squid-db.sock";';
		$f[]='$cfg["Servers"][$i]["connect_type"] = "socket";';
		$f[]='$cfg["Servers"][$i]["extension"] = "mysql";';
		$f[]='$cfg["Servers"][$i]["auth_type"] = "cookie";';
		$f[]='$cfg["Servers"][$i]["user"] = "root";';
		$f[]='$cfg["Servers"][$i]["password"] = "";';
		$f[]='$cfg["Servers"][$i]["AllowNoPassword"] = True;';
	}
	if(is_file('/opt/amavisdb/data/amavis/db.opt')){
		$f[]='$i++;';
		$f[]='$cfg["Servers"][$i]["verbose"] = "Amavis Mysql";';
		$f[]='$cfg["Servers"][$i]["socket"] = "/var/run/mysqld/amavis-db.sock";';
		$f[]='$cfg["Servers"][$i]["connect_type"] = "socket";';
		$f[]='$cfg["Servers"][$i]["extension"] = "mysql";';
		$f[]='$cfg["Servers"][$i]["auth_type"] = "cookie";';
		$f[]='$cfg["Servers"][$i]["user"] = "root";';
		$f[]='$cfg["Servers"][$i]["password"] = "";';
		$f[]='$cfg["Servers"][$i]["AllowNoPassword"] = True;';
	}
	
	
	$f[]='';
	if($phpmyadmin_config_add<>null){$f[]=$phpmyadmin_config_add;}
	$f[]='/* End of servers configuration */';
	$f[]='';
	$f[]='$cfg["blowfish_secret"] = "4bf112360c9db0.66618545";';
	$f[]='$cfg["DefaultLang"] = "en-utf-8";';
	$f[]='$cfg["ServerDefault"] = 1;';
	$f[]='$cfg["UploadDir"] = "";';
	$f[]='$cfg["SaveDir"] = "";';
	$f[]='?>';
	@file_put_contents('/usr/share/phpmyadmin/config.inc.php',@implode("\n", $f));

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} PhpMyAdmin: Success writing phpmyadmin configuration\n";}
	IF(is_dir('/usr/share/phpmyadmin/setup')){shell_exec('/bin/rm -rf /usr/share/phpmyadmin/setup');}
	IF(is_dir('/usr/share/phpmyadmin/config')){shell_exec('/bin/rm -rf /usr/share/phpmyadmin/config');}
	
}
function islighttpd_error_500(){
	$sock=new sockets();
	$unix=new unix();
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}

	@file_put_contents($pidfile, getmypid());	
	
	$curl=$unix->find_program("curl");
	if(!is_file($curl)){return;}
	$LighttpdArticaListenIP=$sock->GET_INFO('LighttpdArticaListenIP');
	$ArticaHttpsPort=9000;
	$ArticaHttpUseSSL=1;
	$ArticaHttpsPort=$sock->GET_INFO("ArticaHttpsPort");
	$ArticaHttpUseSSL=$sock->GET_INFO("ArticaHttpUseSSL");
	if(!is_numeric($ArticaHttpUseSSL)){$ArticaHttpUseSSL=1;}
	if(!is_numeric($ArticaHttpsPort)){$ArticaHttpsPort="9000";}
	$EnableArticaFrontEndToNGninx=$sock->GET_INFO("EnableArticaFrontEndToNGninx");
	if(!is_numeric($EnableArticaFrontEndToNGninx)){$EnableArticaFrontEndToNGninx=0;}
	$proto="http";
	if($ArticaHttpUseSSL==1){$proto="https";}
	
	if($LighttpdArticaListenIP<>null){
		$IPS=$unix->NETWORK_ALL_INTERFACES(true);
		if(!isset($IPS[$LighttpdArticaListenIP])){$LighttpdArticaListenIP=null;}
	}
	
	
	if(strlen($LighttpdArticaListenIP)>3){
		$ips[$LighttpdArticaListenIP]=true;
		$uri="$proto://$LighttpdArticaListenIP:$ArticaHttpsPort/logon.php";
	}else{
		$ips=$unix->NETWORK_ALL_INTERFACES(true);
		unset($ips["127.0.0.1"]);
	}
	
	while (list ($ipaddr, $line) = each ($ips) ){
		$f=array();
		$results=array();
		$uri="$proto://$ipaddr:$ArticaHttpsPort/logon.php";
		$f[]="$curl -I --connect-timeout 5";
		$f[]="--insecure";
		$f[]="--interface $ipaddr";
		$f[]="--url $uri 2>&1";
		$cmdline=@implode(" ", $f);
		if($GLOBALS['VERBOSE']){echo "$cmdline\n";}
		exec(@implode(" ", $f),$results);
		if($GLOBALS['VERBOSE']){echo count($results)." rows\n";}
		
		if(DetectError($results,"Artica Web Interface")){if($EnableArticaFrontEndToNGninx==1){shell_exec("/etc/init.d/nginx restart");}else{restart(true);}}
		
		
	}
	
	$results=array();
	if($GLOBALS['VERBOSE']){echo "done\n";}
	
}
function FrmToSyslog($text){

	$LOG_SEV=LOG_INFO;
	if(function_exists("openlog")){openlog(basename(__FILE__), LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
	if(function_exists("closelog")){closelog();}
}
function DetectError($results,$type){
	while (list ($a, $b) = each ($results) ){
		if($GLOBALS["VERBOSE"]){echo "$a \"$b\"\n";}
		if(preg_match("#HTTP.+?200 OK#", $b)){
			if($GLOBALS['VERBOSE']){echo "$type: 200 OK Nothing to do...\n";}
			return false;
		}
	
		IF(preg_match("#HTTP.*?502 Bad Gateway#", $b)){
			$GLOBALS["DetectError"]="$b";
			if($GLOBALS['VERBOSE']){echo "$b detected\n";}
			system_admin_events("$type: $b detected ",__FUNCTION__,__FILE__,__LINE__);
			return true;
		}
			
		IF(preg_match("#HTTP.*?500.*?Error#", $b)){
			$GLOBALS["DetectError"]="$b";
			if($GLOBALS['VERBOSE']){echo "$b detected\n";}
			system_admin_events("$type: $b detected",__FUNCTION__,__FILE__,__LINE__);
			return true;
		}
		
		IF(preg_match("#HTTP.*?500.*?Internal#", $b)){
			$GLOBALS["DetectError"]="$b";
			if($GLOBALS['VERBOSE']){echo "$b detected\n";}
			system_admin_events("$type: $b detected",__FUNCTION__,__FILE__,__LINE__);
			return true;
		}
		
		IF(preg_match("#HTTP.*?503.*?Service Not Available#i", $b)){
			$GLOBALS["DetectError"]="$b";
			if($GLOBALS['VERBOSE']){echo "$b detected\n";}
			system_admin_events("$type: $b detected",__FUNCTION__,__FILE__,__LINE__);
			return true;		
		}		
		
			
	}	
	
	
}

function phpcgi(){
	$unix=new unix();
	$phpcgi=$unix->LIGHTTPD_PHP5_CGI_BIN_PATH();
	
	$pids=$unix->PIDOF_PATTERN_ALL($phpcgi);
	if(count($pids)==0){return;}
	$c=0;
	while (list ($pid, $ligne) = each ($pids) ){
		$time=$unix->PROCESS_TTL($pid);
		
		if($time>1640){
			$c++;
			$unix->KILL_PROCESS($pid,9);
		}
	}
}
function php_snmp_progress($text,$pourc){
	if(!$GLOBALS["PROGRESS"]){return;}
	echo $text."\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/php-snmp.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	sleep(1);
}
function php_snmp(){
	$unix=new unix();
	php_snmp_progress("{checking_php_snmp}",20);
	$unix->DEBIAN_INSTALL_PACKAGE("php5-snmp");
	$nohup=$unix->find_program("nohup");
	
	if(is_file("/usr/lib/php5/20090626/snmp.so")){
		php_snmp_progress("{checking_php_snmp} {success}",100);
		sleep(5);
		system("$nohup /etc/init.d/artica-webconsole restart >/dev/null 2>&1 &");
		system("$nohup /etc/init.d/artica-status restart >/dev/null 2>&1 &");
	}
	
	
	if(is_file("/usr/lib/php5/20100525/snmp.so")){
		php_snmp_progress("{checking_php_snmp} {success}",100);
		sleep(5);
		system("$nohup /etc/init.d/artica-webconsole restart >/dev/null 2>&1 &");
		system("$nohup /etc/init.d/artica-status restart >/dev/null 2>&1 &");
	}
	
	php_snmp_progress("{checking_php_snmp} {failed}",110);
	
	
	//php5-snmp
	
	
}

