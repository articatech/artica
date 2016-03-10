<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.roundcube.inc');
include_once(dirname(__FILE__) . '/ressources/class.apache.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$bd="roundcubemail";
$GLOBALS["OUTPUT"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["SERVICE_NAME"]="PHP5 Cgi Daemon";
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}

if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}





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
	@file_put_contents($pidfile, getmypid());
	stop(true);
	start(true);
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
	
	$spawn_fcgi=$unix->find_program("spawn-fcgi");
	if(!is_file($spawn_fcgi)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} not installed\n";}
		return;
	}

	$pid=DEFAULT_PID();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} already stopped...\n";}
		return;
	}
	$pid=DEFAULT_PID();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$lighttpd_bin=$unix->find_program("lighttpd");
	$kill=$unix->find_program("kill");



	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=DEFAULT_PID();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=DEFAULT_PID();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=DEFAULT_PID();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success...\n";}
		return;
	}else{
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} failed...\n";}
	}
}

function LOAD_CMDLINES(){
	$unix=new unix();
	$spawn_fcgi=$unix->find_program("spawn-fcgi");
	exec("$spawn_fcgi -h 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#spawn-fcgi v([0-9\.]+)#", $ligne,$re)){
			$ARRAY["VERSION"]=$re[1];
			continue;
		}
		
		$ligne=trim($ligne);
		if(preg_match("#^-([a-zA-z])+#", $ligne,$re)){
			$ARRAY[$re[1]]=true;
		}
		
	}
	
	return $ARRAY;
	
}

function start($aspid=false){
	$unix=new unix();
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
	
	$spawn_fcgi=$unix->find_program("spawn-fcgi");
	
	
	
	
	if(!is_file($spawn_fcgi)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} not installed\n";}
		return;
	}
	
	$pid=DEFAULT_PID();
		
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} already started $pid since {$timepid}Mn...\n";}
		return;
	}
	
	
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$phpcgi=$unix->LIGHTTPD_PHP5_CGI_BIN_PATH();
	
	$EnablePHPFPM=intval($sock->GET_INFO("EnablePHPFPM"));
	$EnablePHPFPMFrameWork=$sock->GET_INFO("EnablePHPFPMFrameWork");
	$EnableArticaApachePHPFPM=$sock->GET_INFO("EnableArticaApachePHPFPM");
	$EnablePHPFPMFreeWeb=$sock->GET_INFO("EnablePHPFPMFreeWeb");
	$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
	if(!is_numeric($EnablePHPFPMFrameWork)){$EnablePHPFPMFrameWork=0;}
	if(!is_numeric($EnableArticaApachePHPFPM)){$EnableArticaApachePHPFPM=0;}
	if(!is_numeric($EnablePHPFPMFreeWeb)){$EnablePHPFPMFreeWeb=0;}
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}
	if($EnableFreeWeb==0){$EnablePHPFPMFreeWeb=1;}
	$EnablePHPFCGI=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableSPAWNFCGI"));
	
	$enabled=$EnablePHPFCGI;
	if($EnablePHPFPM==1){
		if($EnablePHPFPMFrameWork==1){
			if($EnableArticaApachePHPFPM==1){
				if($EnablePHPFPMFreeWeb==1){
					$enabled=0;
				}
			}
		}
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} EnableSPAWNFCGI.........: $EnablePHPFCGI\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} EnablePHPFPM............: $EnablePHPFPM\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} EnablePHPFPMFrameWork...: $EnablePHPFPMFrameWork\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} EnableArticaApachePHPFPM: $EnableArticaApachePHPFPM\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} EnablePHPFPMFreeWeb.....: $EnablePHPFPMFreeWeb\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Enabled.................: $enabled\n";}
	
	if($enabled==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} is disabled...\n";}
		stop();
	}
	
	if(!is_file($phpcgi)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} FATAL no php-cgi can be enabled !\n";}
		return false;
	}
	
	
		$unix->chmod_func(0777, "/var/run");
		
		if($unix->is_socket("/var/run/php-fcgi.sock")){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} remove old socket /var/run/php-fcgi.sock\n";}
			@unlink("/var/run/php-fcgi.sock");
		}
		$params=LOAD_CMDLINES();
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} version {$params["VERSION"]}\n";}
		
		$PROCESSES=3;
		$CHILDREN=5;
		$MEMORY=$unix->MEM_TOTAL_INSTALLEE();
		if($MEMORY<624288){$PROCESSES=1;$CHILDREN=2;}
		
		$pid=$unix->get_pid_from_file("/var/run/spawn-fcgi.pid");
		$f[]=$spawn_fcgi;
		$f[]="-s /var/run/php-fcgi.sock";
		if(isset($params["C"])){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $PROCESSES Processes\n";}
			$f[]="-C $PROCESSES";
		}
		
		if(isset($params["F"])){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $CHILDREN Children\n";}
			$f[]="-F $CHILDREN";
		}		
		
		$f[]="-u www-data -g www-data";
		$f[]="-f $phpcgi";
		$f[]="-P /var/run/spawn-fcgi.pid";
		
		$cmd=@implode(" ", $f);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $cmd\n";}
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		shell_exec($cmd);
	
	for($i=0;$i<4;$i++){
		$pid=DEFAULT_PID();
		if($unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting $i/4...\n";}
		sleep(1);
	}
	
	$pid=DEFAULT_PID();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Success service started pid:$pid...\n";}
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} failed...\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $cmd\n";}
	}
	
	
	
}

function DEFAULT_PID(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file('/var/run/spawn-fcgi.pid');
	if($unix->process_exists($pid)){return $pid;}
	$spawn_fcgi=$unix->find_program("spawn-fcgi");
	return $unix->PIDOF($spawn_fcgi);
}



	

			
			









?>