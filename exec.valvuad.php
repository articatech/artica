<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="Valvuad Policy Daemon";
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
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');



$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}




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


function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("valvulad");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, arpd not installed\n";}
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
	
	if($unix->MEM_TOTAL_INSTALLEE()<624288){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} not enough memory\n";}
		if($unix->process_exists($pid)){stop();}
		return;
	}

	

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		return;
	}
	$ValvuladEnabled=intval($sock->GET_INFO("ValvuladEnabled"));
	
	
	build();
	

	if($ValvuladEnabled==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see ValvuladEnabled)\n";}
		return;
	}

	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	
	
	
	$f[]="$Masterbin --config /etc/valvula/valvula.conf";
	
	$cmd=@implode(" ", $f) ." >/dev/null 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	
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
		
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	}


}

function build(){
	@mkdir("/etc/valvula/mods-enabled",0755,true);
	$q=new mysql();
	$f[]="<valvula>";
	$f[]="\t<global-settings>";
	$f[]="\t\t<running user=\"postfix\" group=\"postfix\" enabled=\"yes\"/>";
	$f[]="\t\t<log-reporting enabled=\"yes\" use-syslog=\"yes\" />";
	$f[]="\t\t<signal action=\"reexec\" />";
	$f[]="\t\t<request-line limit=\"40\" />";
	$f[]="\t</global-settings>";
	$f[]="";
	$f[]="\t<general>";
	$f[]="\t\t<listen host=\"127.0.0.1\" port=\"3579\">";
	$f[]="\t\t\t<run module=\"mod-ticket\" /> ";
	$f[]="\t\t</listen>  ";
	$f[]="\t</general>";
	$f[]="";
	$f[]="\t<database>";
	$f[]="\t\t<config driver=\"mysql\" dbname=\"artica_backup\" user=\"$q->mysql_admin\" password=\"$q->mysql_password\" host=\"$q->mysql_server\" port=\"$q->mysql_port\" />";
	$f[]="\t</database>";
	$f[]="";
	$f[]="\t<enviroment>";
	$f[]="\t\t<local-domains config=\"autodetect\" />";
	$f[]="\t\t<sender-login-mismatch mode=\"full\" allow-empty-mail-from=\"yes\" />";
	$f[]="\t\t<default-sending-quota status=\"full\" if-no-match=\"first\" debug=\"yes\">";
	$f[]="\t\t\t<limit label='day quota' from=\"9:00\" to=\"21:00\"  status=\"full\" minute-limit=\"150\" hour-limit=\"250\" global-limit=\"750\" domain-minute-limit=\"300\" domain-hour-limit=\"500\" domain-global-limit=\"2500\" />";
	$f[]="\t\t\t<limit label='night quota' from=\"21:00\" to=\"9:00\"  status=\"full\" minute-limit=\"50\" hour-limit=\"150\" global-limit=\"300\" domain-minute-limit=\"100\" domain-hour-limit=\"300\" domain-global-limit=\"600\" />";
	$f[]="\t\t</default-sending-quota>";
	$f[]="";
	$f[]="    <!-- <bwl debug=\"no\" /> -->";
	$f[]="";
	$f[]="    <!-- mod-mw : mysql works -->";
	$f[]="    <!-- It allows to run user defined sql queries with the provided";
	$f[]="         credentials. Each SQL query is then personalized with support";
	$f[]="         substitutions. All substitutions takes the value indicated or";
	$f[]="         evals to emtpy string.-->";
	$f[]="";
	$f[]="    <!-- Allowed substitutions are: ";
	$f[]="";
	$f[]="	 - #queue-id# if defined, it is replaced by reported queue id";
	$f[]="	 - #size# if defined, it is replaced by reported size (single size, you may have to consider having this value by #rpct-count# to have actual size to handle/send.";
	$f[]="	 - #sasl_user# if defined, it is replaced by sasl user account used.";
	$f[]="	 - #mail-from# if defined, it is replaced by mail from: reported account used.";
	$f[]="	 - #rcpt-count# if defined, it is replaced by reported recipient count (recipient_count reported by postfix).This value is only reliable if valvula is connected to smtpd_data_restrictions.";
	$f[]="	 - #rcpt-to# if defined, it is replaced by reported rcpt to: This value isn't reliable if connected to smtpd_data_restrictions (it may be empty for multi recipients operations). Connect valvula to smtpd_sender_restrictions if you want a reliable #rcpt-to# value.";
	$f[]="	 - #client-address# if defined, it is replaced by reported connecting ip";
	$f[]="    -->";
	$f[]="    <!-- configuration example follows: -->";
	$f[]="    <!-- ";
	$f[]="      <mysql-works>";
	$f[]="      <with-db-def use=\"valvula\" port=\"3579\"> ";
	$f[]="	<run-on-request sql=\"INSERT INTO example_table (sasl_user, mail_from, rcpt_count) VALUES ('#sasl_user#', '#mail-from#', '#rcpt-count#')\" />";
	$f[]="	<run-every-hour sql=\"DELETE FROM example_table\" />";
	$f[]="      </with-db-def>";
	$f[]="    </mysql-works> -->";
	$f[]="  </enviroment>";
	$f[]="";
	$f[]="  <modules>";
	$f[]="    <directory src=\"/etc/valvula/mods-enabled\" /> ";
	$f[]="  </modules>";
	$f[]="</valvula>";
	$f[]="";
	@mkdir("/etc/valvula",0755,true);
	@file_put_contents("/etc/valvula/valvula.conf", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/valvula/valvula.conf done.\n";}
	
	
	$f[]=array();
	$f[]="\n<mod-valvulad location=\"/usr/lib/valvulad/modules/mod-bwl.so\"/>\n";
	@file_put_contents("/etc/valvula/mods-enabled/mod-bwl.xml", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} mod-bwl.xml done.\n";}
	
	
	
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
	$pid=$unix->get_pid_from_file("/var/run/valvulad.pid");
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("valvulad");
	return $unix->PIDOF($Masterbin);
	
}
?>