<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="Daemon Monitor";
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
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.monit.inc');


if(system_is_overloaded(basename(__FILE__))){echo "Overloaded system, die();";die();}

$GLOBALS["MONIT_CLASS"]=new monit_unix();

$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;build();die();}
if($argv[1]=="--status"){status();die();}
if($argv[1]=="--monitor-wait"){monitor_wait();die();}


function status(){
	$cache_file="/usr/share/artica-postfix/ressources/logs/web/monit.status.all";
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}	
	
	$time=$unix->file_time_min($cache_file);
	if($time<2){return;}
	$monit=new monit_unix();
	$array=$monit->all_status();
	if(count($array)<2){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} array returned less than 2 items\n";}
		return;}
	@unlink($cache_file);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Saving $cache_file\n";}
	@file_put_contents($cache_file, serialize($array));
	@chmod($cache_file,0755);
}


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
	build_progress_restart("{stopping_service}",15);
	if(!stop(true)){return;}
	build_progress_restart("{reconfiguring}",21);
	build();
	sleep(1);
	build_progress_restart("{starting_service}",46);
	start(true);
	
}

function monitor_wait(){
	sleep(5);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	shell_exec("{$GLOBALS["MONIT_CLASS"]->monitor_all_cmdline} >/dev/null 2>&1");
	
}


function start($aspid=false){
	if(is_file("/etc/artica-postfix/FROM_ISO")){if(!is_file("/etc/artica-postfix/artica-iso-setup-launched")){return;}}
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("monit");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, arpd not installed\n";}
		build_progress_restart("{starting_service} {failed}",110);
		return false;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			build_progress_restart("{starting_service} {failed}",110);
			return false;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=$GLOBALS["MONIT_CLASS"]->PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		build_progress_restart("{starting_service} {success}",100);
		return true;
	}
	$EnableMonit=$sock->GET_INFO("EnableMonit");
	if(!is_numeric($EnableMonit)){$EnableMonit=1;}
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	if($SquidPerformance>2){$EnableMonit=0;}
	

	if($EnableMonit==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableArpDaemon)\n";}
		build_progress_restart("{starting_service} {failed}",110);
		return false;
	}

	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	
	@mkdir("/var/run/monit",0755,true);
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} apply permissions\n";}
	$GLOBALS["MONIT_CLASS"]->CheckFolders();
	
	$cmd=$GLOBALS["MONIT_CLASS"]->start_cmdline ." 2>&1";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} configuring service\n";}
	build();
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} starting service\n";}
	shell_exec("$nohup $cmd >/dev/null 2>&1 &");
	
	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		build_progress_restart("{starting_service} {waiting} $i/5",47);
		$pid=$GLOBALS["MONIT_CLASS"]->PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=$GLOBALS["MONIT_CLASS"]->PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Force to monitor all daemons..\n";}
		shell_exec("$nohup $php5 --monitor-wait >/dev/null 2>&1 &");
		shell_exec("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");
		build_progress_restart("{starting_service} {success}",100);
		return true;
	}
	build_progress_restart("{starting_service} {failed}",110);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	
		
}

function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";}
			build_progress_restart("{stopping_service} {failed}",110);
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=$GLOBALS["MONIT_CLASS"]->PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		build_progress_restart("{stopping_service} {success}",20);
		return true;
	}
	$pid=$GLOBALS["MONIT_CLASS"]->PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	
	
	build_progress_restart("{stopping_service}",16);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} unmonitor all processes\n";}
	exec($GLOBALS["MONIT_CLASS"]->stop_cmdline." 2>&1",$results);
	sleep(1);

	build_progress_restart("{stopping_service}",17);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=$GLOBALS["MONIT_CLASS"]->PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	build_progress_restart("{stopping_service}",18);
	$pid=$GLOBALS["MONIT_CLASS"]->PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		build_progress_restart("{stopping_service} {success}",20);
		return true;
	}
	
	build_progress_restart("{stopping_service}",19);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=$GLOBALS["MONIT_CLASS"]->PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}
	
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		build_progress_restart("{stopping_service} {failed}",110);
		return false;
	}
	build_progress_restart("{stopping_service} {success}",20);
	return true;

}
function build_progress_restart($text,$pourc){
	
	if($GLOBALS["OUTPUT"]){echo "Progress......: ".date("H:i:s")." [{$pourc}%]: {$GLOBALS["TITLENAME"]} $text..\n";}
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/exec.monit.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
	if($GLOBALS["PROGRESS"]){sleep(1);}

}

function build(){
	$users=new usersMenus();
	$sock=new sockets();
	$unix=new unix();
	$SystemLoadNotif=$sock->GET_INFO("SystemLoadNotif");
	if(!is_numeric($SystemLoadNotif)){$SystemLoadNotif=0;}
	$EnableSyslogDB=$sock->GET_INFO("EnableSyslogDB");
	if(!is_numeric($EnableSyslogDB)){$EnableSyslogDB=0;}
	$MySQLSyslogType=$sock->GET_INFO("MySQLSyslogType");
	if(!is_numeric($MySQLSyslogType)){$MySQLSyslogType=1;}
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
	$python=$unix->find_program("python");
	$nice=$unix->EXEC_NICE();
	$ps=$unix->find_program("ps");
	$sort=$unix->find_program("sort");
	$head=$unix->find_program("head");
	$echo=$unix->find_program("echo");
	$date=$unix->find_program("date");
	$mkdir=$unix->find_program("mkdir");
	$php5=$unix->LOCATE_PHP5_BIN();
	
	
	$ZarafaDedicateMySQLServer=$sock->GET_INFO("ZarafaDedicateMySQLServer");
	if(!is_numeric($ZarafaDedicateMySQLServer)){$ZarafaDedicateMySQLServer=0;}
	build_progress_restart("{reconfiguring}",22);
	
	$ini=new Bs_IniHandler();
	$ini->loadFile('/etc/artica-postfix/smtpnotif.conf');
	
	if(!is_numeric($ini->_params["SMTP"]["EnableNotifs"])){$ini->_params["SMTP"]["EnableNotifs"]=0;}
	if(!is_numeric($ini->_params["SMTP"]["tls_enabled"])){$ini->_params["SMTP"]["tls_enabled"]=0;}
	
	
	$smtp_server=trim($ini->_params["SMTP"]['smtp_server_name']);
	$smtp_server_port=$ini->_params["SMTP"]['smtp_server_port'];
	$smtp_dest=$ini->_params["SMTP"]['smtp_dest'];
	$smtp_sender=$ini->_params["SMTP"]['smtp_sender'];
	$smtp_auth_user=$ini->_params["SMTP"]['smtp_auth_user'];
	$smtp_auth_passwd=$ini->_params["SMTP"]['smtp_auth_passwd'];
	$tls_enabled=$ini->_params["SMTP"]["tls_enabled"];
	
	$recipientsZ=explode("\n","/etc/artica-postfix/settings/Daemons/SmtpNotificationConfigCC");
	
	$recipients=array();
	while (list ($index, $to) = each ($recipientsZ) ){
		if(trim($to)==null){continue;}
		$recipients[]=$to;
		
	}
	
	if($smtp_server==null){$ini->_params["SMTP"]["EnableNotifs"]=0;}
	if($smtp_dest==null){
		if(count($recipients)==0){$ini->_params["SMTP"]["EnableNotifs"]=0;}
	}
	if(!is_numeric($smtp_server_port)){$smtp_server_port=25;}
	
	$EnableNotifs=$ini->_params["SMTP"]["EnableNotifs"];
	$monit_not_on='instance,action';
	
	$f[]='set daemon 60 with start delay 5';
	$f[]='set idfile /var/run/monit/monit.id';
	
	
	$cpunum=$unix->CPU_NUMBER();
	$normal=($cpunum*2)+1;
	$normal2=$cpunum*2;
	$busy=$cpunum*4;
	build_progress_restart("{reconfiguring}",23);
	
	$EnableMONITSmtpNotif=$sock->GET_INFO("EnableMONITSmtpNotif");
	if(!is_numeric($EnableMONITSmtpNotif)){$EnableMONITSmtpNotif=1;}
	

	
	$MonitCPUUsage=intval($sock->GET_INFO("MonitCPUUsage"));
	$MonitCPUUsageCycles=intval($sock->GET_INFO("MonitCPUUsageCycles"));
	
	$MonitMemUsage=intval($sock->GET_INFO("MonitMemUsage"));
	$MonitMemUsageCycles=intval($sock->GET_INFO("MonitMemUsageCycles"));
	
	$MonitReportLoadVG1mn=intval($sock->GET_INFO("MonitReportLoadVG1mn"));
	$MonitReportLoadVG1mnCycles=intval($sock->GET_INFO("MonitReportLoadVG1mnCycles"));
	
	if($MonitReportLoadVG1mnCycles==0){$MonitReportLoadVG1mnCycles=5;}
	
	$MonitReportLoadVG5mn=intval($sock->GET_INFO("MonitReportLoadVG5mn"));
	$MonitReportLoadVG5mnCycles=intval($sock->GET_INFO("MonitReportLoadVG5mnCycles"));
	
	if($MonitReportLoadVG5mnCycles==0){$MonitReportLoadVG5mnCycles=15;}
	
	$MonitReportLoadVG15mn=intval($sock->GET_INFO("MonitReportLoadVG15mn"));
	$MonitReportLoadVG15mnCycles=intval($sock->GET_INFO("MonitReportLoadVG15mnCycles"));
	
	if($MonitReportLoadVG15mnCycles==0){$MonitReportLoadVG15mnCycles=60;}
	$MonitMemPurgeCache=intval($sock->GET_INFO("MonitMemPurgeCache"));
	$MonitMemPurgeCacheCycles=intval($sock->GET_INFO("MonitMemPurgeCacheCycles"));
	if($MonitMemPurgeCache==0){$MonitMemPurgeCache=70;}
	if($MonitMemPurgeCacheCycles==0){$MonitMemPurgeCacheCycles=5;}
	if($MonitMemUsageCycles==0){$MonitMemUsageCycles=5;}

	if($MonitCPUUsageCycles==0){$MonitCPUUsageCycles=15;}
	
	if($MonitCPUUsage>0){
		if($MonitCPUUsage<50){
			$MonitCPUUsage=90;
		}
	}
	
	if($MonitMemUsage>0){
		if($MonitMemUsage<50){
			$MonitMemUsage=90;
		}
	}

	
	build_progress_restart("{reconfiguring}",24);
	
	$rmbin=$unix->find_program("rm");
	$echo=$unix->find_program("echo");
	$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	
	$f[]='set logfile syslog facility log_daemon';
	
	$f[]='set statefile /var/run/monit/monit.state';
	$f[]='';
	if($EnableNotifs==1){
		if($EnableMONITSmtpNotif==1){
			$f[]="set mailserver $smtp_server PORT $smtp_server_port";
			if(strlen($smtp_auth_user)>0){ $f[]="\tUSERNAME \"$smtp_auth_user\" PASSWORD \"$smtp_auth_passwd\"";}
			if($tls_enabled==1){$f[]="\tusing TLSV1";}
			$f[]="\tset eventqueue";
			$f[]="\tbasedir /var/monit";
			$f[]="\tslots 100";
	
			$f[]="\tset mail-format {";
			$f[]="\t\tfrom: $smtp_sender";
			$f[]="\t\tsubject: Artica service monitor: \$SERVICE \$EVENT";
			$f[]="\t\tmessage: Artica service monitor  \$ACTION  \$SERVICE at  \$DATE on  \$HOST:  \$DESCRIPTION";
			$f[]="\t}";
			$f[]="set alert $smtp_dest but not on {{$monit_not_on}}";
			if($recipients>0){
				while (list ($index, $to) = each ($recipientsZ) ){
					$f[]="set alert $to but not on {{$monit_not_on}}";
				}
			}
	
		}
	}
	
	build_progress_restart("{reconfiguring}",25);
	$allips=$unix->NETWORK_ALL_INTERFACES(true);
	
	$f[]="set httpd port 2874 and use address 127.0.0.1";
	$f[]="\tallow 127.0.0.1";
	while (list ($tcpi, $to) = each ($allips) ){
		$f[]="\tallow $tcpi";
	}
	
	$top=$unix->find_program("top");
	$hostname=$unix->hostname_g();
	
		$TSCR=array();
		
		if($MonitReportLoadVG1mn>0){$TSCR[]="\tif loadavg (1min) > $MonitReportLoadVG1mn for $MonitReportLoadVG1mnCycles cycles then exec \"/bin/artica-system-alert.sh LOAD_1 $MonitReportLoadVG1mn\"";}
		if($MonitReportLoadVG5mn>0){$TSCR[]="\tif loadavg (5min) > $MonitReportLoadVG5mn for $MonitReportLoadVG5mnCycles cycles then exec \"/bin/artica-system-alert.sh LOAD_5 $MonitReportLoadVG5mn\"";}
		if($MonitReportLoadVG15mn>0){$TSCR[]="\tif loadavg (15min) > $MonitReportLoadVG15mn for $MonitReportLoadVG15mnCycles cycles then exec \"/bin/artica-system-alert.sh LOAD_15 $MonitReportLoadVG15mn\"";}
		
		if($MonitCPUUsage>0){
			if($MonitCPUUsage<75){$MonitCPUUsage=75;}
			$TSCR[]="\tif cpu usage(system) > {$MonitCPUUsage}% for $MonitCPUUsageCycles cycles then exec \"/bin/artica-system-alert.sh CPU_SYSTEM $MonitCPUUsage\"";
			$TSCR[]="\tif cpu usage(user) > {$MonitCPUUsage}% for $MonitCPUUsageCycles cycles then exec \"/bin/artica-system-alert.sh CPU_USER $MonitCPUUsage\"";
			$TSCR[]="\tif cpu usage(wait) > {$MonitCPUUsage}% for $MonitCPUUsageCycles cycles then exec \"/bin/artica-system-alert.sh CPU_WAIT $MonitCPUUsage\"";
			
		}
		if($MonitMemUsage>0){
			if($MonitMemUsage<75){$MonitMemUsage=75;}
			$TSCR[]="\tif memory > {$MonitMemUsage}% for $MonitMemUsageCycles cycles then exec \"/bin/artica-system-alert.sh MEM $MonitMemUsage\"";
		}
		
		if($MonitMemPurgeCache>5){
			@chmod("/usr/share/artica-postfix/exec.kernel.purge.cache.php",0755);
			$TSCR[]="\tif memory > {$MonitMemPurgeCache}% for $MonitMemPurgeCacheCycles cycles then exec \"/usr/share/artica-postfix/exec.kernel.purge.cache.php\"";
		}
		
		if(count($TSCR)>1){
			$f[]="check system ".$unix->hostname_g();
			$f[]=@implode("\n", $TSCR);
		}
		$TSCR=array();
		$SCRIPT=array();
		$SCRIPT[]="#!/bin/sh";
		$SCRIPT[]="CURRENT=`$date +%s`";
		$SCRIPT[]="DIR=\"/home/artica/system/perf-queue/\$CURRENT\"";
		$SCRIPT[]="$mkdir -p \"\$DIR\"";
		$SCRIPT[]="$echo \$CURRENT >\$DIR/time.txt";
		$SCRIPT[]="$echo \$1 >\$DIR/why.txt";
		$SCRIPT[]="$echo \$2 >\$DIR/why2.txt";
		$iotop=$unix->find_program("iotop");
		if(is_file($iotop)){
			$SCRIPT[]="$iotop -o -a -b -q -t -n 20  >\$DIR/iotop.txt || true";
		}
		$SCRIPT[]="$nice $python /usr/share/artica-postfix/bin/ps_mem.py >\$DIR/psmem.txt 2>&1";
		$SCRIPT[]="$ps --no-heading -eo user,pid,pcpu,args|$sort -grbk 3|$head -50 >\$DIR/TOP50-CPU.txt 2>&1";
		$SCRIPT[]="$ps --no-heading -eo user,pid,pmem,args|$sort -grbk 3|$head -50 >\$DIR/TOP50-MEM.txt 2>&1";
		$SCRIPT[]="$ps auxww  >\$DIR/ALLPS.txt 2>&1";
		$SCRIPT[]="";
		@file_put_contents("/bin/artica-system-alert.sh", @implode("\n", $SCRIPT));
		@chmod("/bin/artica-system-alert.sh",0755);
		$SCRIPT=array();
		
	$f[]="";
	$f[]="check host loopback with address 127.0.0.1";
	$f[]="\tif failed icmp type echo with timeout 1 seconds then exec \"/bin/loopbackfailed.sh\"";
	$f[]="";
	
	$loopbackfailed[]="#!/bin/sh";
	$loopbackfailed[]="$php5 /usr/share/artica-postfix/exec.virtuals-ip.php --loopback";
	$loopbackfailed[]="";
	@file_put_contents("/bin/loopbackfailed.sh", @implode("\n", $loopbackfailed));
	@chmod("/bin/loopbackfailed.sh",0755);
	$loopbackfailed=array();
	build_progress_restart("{reconfiguring}",25);

//********************************************************************************************************************	
	$f[]="check file php.log with path /var/log/php.log";
	$f[]="\tif size > 100 MB then";
	$f[]="\t\texec \"/bin/clean-phplog.sh\"";
	$f[]="";
	$f[]="check file usrphp.log with path /usr/share/artica-postfix/ressources/logs/php.log";
	$f[]="      if size > 100 MB then";
	$f[]="\t\texec \"/bin/clean-phplog.sh\"";
	$f[]="";
	
	$f[]="check file squid-logger-start.log with path /var/log/artica-postfix/squid-logger-start.log";
	$f[]="\tif size > 100 MB then";
	$f[]="\t\texec \"/bin/squid-logger-start.sh\"";
	$f[]="";

	
	
	build_progress_restart("{reconfiguring}",26);
	$f[]="include /etc/monit/conf.d/*";
	@file_put_contents("/etc/monit/monitrc", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/monit/monitrc done...\n";}
	$AA[]="#!/bin/sh";
	$AA[]="$echo \"#\" >/var/log/artica-postfix/squid-logger-start.log";
	$AA[]="";
	@file_put_contents("/bin/squid-logger-start.sh", @implode("\n", $AA));
	@chmod("/bin/squid-logger-start.sh",0755);
	
	
	
	$AA=array();
	$AA[]="#!/bin/sh";
	$AA[]="$echo \"#\" >/var/log/php.log";
	$AA[]="";
	@file_put_contents("/bin/clean-phplog.sh", @implode("\n", $AA));
	@chmod("/bin/clean-phplog.sh",0755);
	$AA=array();
	
	
	$monit=new monit();
	$monit->save();
	$INITD_PATH=$unix->SLAPD_INITD_PATH();
	$SLAPD_PID_FILE=$unix->SLAPD_PID_PATH();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	@unlink("/etc/monit/conf.d/APP_OPENLDAP.monitrc");
//********************************************************************************************************************	
	$f=array();


//********************************************************************************************************************	
	build_progress_restart("{reconfiguring}",27);
	$f=array();
	$f[]="check process APP_FRAMEWORK";
	$f[]="with pidfile /var/run/lighttpd/framework.pid";
	$f[]="start program = \"/etc/init.d/artica-framework start --monit\"";
	$f[]="stop program =  \"/etc/init.d/artica-framework stop --monit\"";
	$f[]="if 5 restarts within 5 cycles then timeout";
	@file_put_contents("/etc/monit/conf.d/articaframework.monitrc", @implode("\n", $f));
	$f=array();
//********************************************************************************************************************	
	$f=array();
	@unlink("/etc/monit/conf.d/APP_OPENSSH.monitrc");
	@unlink("/etc/monit/conf.d/APP_MYSQLD.monitrc");
	
//********************************************************************************************************************	
	$f=array();
	build_progress_restart("{reconfiguring}",28);
	$f[]="check process APP_ARTICA_STATUS with pidfile /etc/artica-postfix/exec.status.php.pid";
	$f[]="\tstart program = \"/etc/init.d/artica-status start --monit\"";
	$f[]="\tstop program = \"/etc/init.d/artica-status stop --monit\"";
	$f[]="\tif 5 restarts within 5 cycles then timeout";
	$f[]="";
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring Artica Status...\n";}
	@file_put_contents("/etc/monit/conf.d/APP_ARTICASTATUS.monitrc", @implode("\n", $f));	
//********************************************************************************************************************	
	$f=array();
	$EnableInflux=1;
	if($SquidPerformance>2){$EnableInflux=0;}
	$InfluxUseRemote=intval($sock->GET_INFO("InfluxUseRemote"));
	$EnableInfluxDB=intval($sock->GET_INFO("EnableInfluxDB"));
	
	if($InfluxUseRemote==1){$EnableInfluxDB=0;}
	if($EnableIntelCeleron==1){$EnableInflux=0;}
	if($EnableInfluxDB==0){$EnableInflux=0;}
	
	if(is_file("/etc/artica-postfix/STATS_APPLIANCE")){$EnableInflux=1;}
	build_progress_restart("{reconfiguring}",29);
	@unlink("/etc/monit/conf.d/APP_INFLUXDB.monitrc");
	@unlink("/etc/monit/conf.d/APP_POSTGRES.monitrc");
	if(is_file("/usr/local/ArticaStats/bin/postgres")){
	if($EnableInflux==1){
		$InfluxRestartMem=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/InfluxRestartMem"));
		$f[]="check process APP_POSTGRES with pidfile /home/ArticaStatsDB/postmaster.pid";
		$f[]="\tstart program = \"/etc/init.d/artica-postgres start --monit\"";
		$f[]="\tstop program = \"/etc/init.d/artica-postgres --monit\"";
		$f[]="\tif 5 restarts within 5 cycles then timeout";
		if($InfluxRestartMem>50){
			$f[]="\tif totalmem > {$InfluxRestartMem} MB for 5 cycles then restart";
		}
		$f[]="";
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring Artica Status...\n";}
		@file_put_contents("/etc/monit/conf.d/APP_POSTGRES.monitrc", @implode("\n", $f));
		//********************************************************************************************************************
		}
		
	}
	
	$f=array();
	@unlink("/etc/monit/conf.d/squid.monitrc");
	@unlink("/etc/monit/conf.d/APP_SQUIDMAIN.monitrc");
		
// ********************************************************************************************************************	
	$f=array();
	@unlink("/etc/monit/conf.d/APP_SQUIDDB.monitrc");
	build_progress_restart("{reconfiguring} Proxy service",30);
	if(is_dir("/opt/squidsql/data")){
		if($SQUIDEnable==1){
			$f=array();
			$f[]="check process APP_SQUID_DB with pidfile /var/run/squid-db.pid";
			$f[]="\tstart program = \"/etc/init.d/squid-db start --monit\"";
			$f[]="\tstop program = \"/etc/init.d/squid-db stop --monit\"";
			$f[]="\tif failed unixsocket /var/run/mysqld/squid-db.sock then restart";
			$f[]="\tif 5 restarts within 5 cycles then timeout";
			$f[]="";
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring Squid MySQL DB...\n";}
			@file_put_contents("/etc/monit/conf.d/APP_SQUIDDB.monitrc", @implode("\n", $f));
		}		
	}
	
// ********************************************************************************************************************
	$f=array();
	build_progress_restart("{reconfiguring} Dnsmasq",31);
	@unlink("/etc/monit/conf.d/APP_DNSMASQ.monitrc");
	if($users->dnsmasq_installed){
			$enabled=$sock->dnsmasq_enabled();
			if($enabled==1){
				$f[]="check process APP_DNSMASQ with pidfile /var/run/dnsmasq.pid";
				$f[]="\tstart program = \"/etc/init.d/dnsmasq start --monit\"";
				$f[]="\tstop program = \"/etc/init.d/dnsmasq stop --monit\"";
				$f[]="\tif 5 restarts within 5 cycles then timeout";
				$f[]="";
				if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring DnsMASQ...\n";}
				@file_put_contents("/etc/monit/conf.d/APP_DNSMASQ.monitrc", @implode("\n", $f));
			}
	}
// ********************************************************************************************************************
	$f=array();
	build_progress_restart("{reconfiguring} Proftpd",31);
	@unlink("/etc/monit/conf.d/APP_PROFTPD.monitrc");
	$proftpd=$unix->find_program("proftpd");
	
	if(is_file($proftpd)){
		$enabled=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableProFTPD"));
		if($enabled==1){
			$f[]="check process APP_PROFTPD with pidfile /var/run/proftpd.pid";
			$f[]="\tstart program = \"/etc/init.d/proftpd start --monit\"";
			$f[]="\tstop program = \"/etc/init.d/proftpd stop --monit\"";
			$f[]="\tif 5 restarts within 5 cycles then timeout";
			$f[]="";
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring DnsMASQ...\n";}
			@file_put_contents("/etc/monit/conf.d/APP_PROFTPD.monitrc", @implode("\n", $f));
		}
	}
	// ********************************************************************************************************************
	
	
	$f=array();
	build_progress_restart("{reconfiguring} Bandwidthd",31);
	@unlink("/etc/monit/conf.d/APP_BANDWIDTHD.monitrc");
	if(is_file("/usr/bandwidthd/bandwidthd")){	
		$enabled=$sock->Bandwidthd_enabled();
		if($enabled==1){
			$f[]="check process APP_BANDWIDTHD with pidfile /var/run/bandwidthd.pid";
			$f[]="\tstart program = \"/etc/init.d/bandwidthd start --monit\"";
			$f[]="\tstop program = \"/etc/init.d/bandwidthd stop --monit\"";
			$f[]="\tif 5 restarts within 5 cycles then timeout";
			$f[]="";
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring DnsMASQ...\n";}
			@file_put_contents("/etc/monit/conf.d/APP_BANDWIDTHD.monitrc", @implode("\n", $f));
		}
		
	}
	// ********************************************************************************************************************	
	$f=array();
	build_progress_restart("{reconfiguring} rsyslog",32);
	$rsyslogd=$unix->find_program("rsyslogd");
	@unlink("/etc/monit/conf.d/APP_RSYSLOG.monitrc");
	if(is_file($rsyslogd)){
		
		$SCRIPT=array();
		$SCRIPT[]="#!/bin/sh";
		$SCRIPT[]="$php5 /usr/share/artica-postfix/exec.watchdog.rsyslogd.php --start";
		$SCRIPT[]="";
		@file_put_contents("/bin/artica-rsyslog-start.sh", @implode("\n", $SCRIPT));
		@chmod("/bin/artica-rsyslog-start.sh",0755);
		$SCRIPT=array();
		$SCRIPT[]="#!/bin/sh";
		$SCRIPT[]="$php5 /usr/share/artica-postfix/exec.watchdog.rsyslogd.php --stop";
		$SCRIPT[]="";
		@file_put_contents("/bin/artica-rsyslog-stop.sh", @implode("\n", $SCRIPT));
		@chmod("/bin/artica-rsyslog-stop.sh",0755);
		$SCRIPT=array();		
		
		
		$f[]="check process APP_RSYSLOG with pidfile /var/run/rsyslogd.pid";
		$f[]="\tstart program = \"/bin/artica-rsyslog-start.sh\"";
		$f[]="\tstop program = \"/bin/artica-rsyslog-stop.sh\"";
		$f[]="\tif 5 restarts within 5 cycles then timeout";
		$f[]="";
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring rsyslogd...\n";}
		@file_put_contents("/etc/monit/conf.d/APP_RSYSLOG.monitrc", @implode("\n", $f));
	}
	
	
	// ********************************************************************************************************************
	
	build_progress_restart("{reconfiguring} Squid-tail",32);
	
	$APP_ARTICA_SQUID_TAIL=$unix->SQUID_TAIL_ENABLED();
	@unlink("/etc/monit/conf.d/APP_ARTICA_SQUID_TAIL.monitrc");
	if($APP_ARTICA_SQUID_TAIL==1){
		$f=array();
		$f[]="check process APP_ARTICA_SQUID_TAIL with pidfile /etc/artica-postfix/pids/exec.logfile_daemon.php.pid";
		$f[]="\tstart program = \"/etc/init.d/squid-tail start\"";
		$f[]="\tstop program = \"/etc/init.d/squid-tail stop\"";
		$f[]="\tif cpu usage > 95% for 5 cycles then restart";
		$f[]="\tif totalmem > 550.0 MB for 5 cycles then restart";
		$f[]="\tif 5 restarts within 5 cycles then timeout";
		$f[]="";
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring squid-tail...\n";}
		@file_put_contents("/etc/monit/conf.d/APP_ARTICA_SQUID_TAIL.monitrc", @implode("\n", $f));
	}
	
// ********************************************************************************************************************	
	
	build_progress_restart("{reconfiguring} Squid-Proxy",32);
	
	

	
	$APP_SQUID=$unix->SQUID_ENABLED();
	@unlink("/etc/monit/conf.d/APP_SQUID.monitrc");
	if($APP_SQUID==1){
		$MonitConfig=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/SquidWatchdogMonitConfig")));
		if(!isset($MonitConfig["watchdog"])){$MonitConfig["watchdog"]=1;}
		if(!is_numeric($MonitConfig["watchdog"])){$MonitConfig["watchdog"]=1;}
		
		$f=array();
		if($MonitConfig["watchdog"]==1){
			$MonitSquidMaxRestartMem=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/MonitSquidMaxRestartMem"));
			$MonitSquidMaxCPU=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/MonitSquidMaxCPU"));
			
			$f[]="check process APP_SQUID with pidfile /var/run/squid/squid.pid";
			$f[]="\tstart program = \"/etc/init.d/squid start --monit\"";
			$f[]="\tstop program = \"/etc/init.d/squid stop --monit\"";
			if($MonitSquidMaxCPU>0){
				$f[]="\tif cpu usage > {$MonitSquidMaxCPU}% for 5 cycles then restart";
			}
			if($MonitSquidMaxRestartMem>0){
				$f[]="\tif totalmem > {$MonitSquidMaxRestartMem}.0 MB for 5 cycles then restart";
			}
			$f[]="\tif 5 restarts within 5 cycles then timeout";
			$f[]="";
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring squid...\n";}
			@file_put_contents("/etc/monit/conf.d/APP_SQUID.monitrc", @implode("\n", $f));
		}
	}
	
	// ********************************************************************************************************************
		
	
	
	build_progress_restart("{reconfiguring} OpenLDAP",32);
	$EnableOpenLDAP=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableOpenLDAP"));
	$SLAPD_PID_FILE=$unix->SLAPD_PID_PATH();
	@unlink("/etc/monit/conf.d/APP_SLAPD.monitrc");
	if($EnableOpenLDAP==1){
		if($EnableIntelCeleron==0){
			$f=array();
			$f[]="check process APP_SLAPD with pidfile $SLAPD_PID_FILE";
			$f[]="\tstart program = \"/etc/init.d/slapd start --force --monit\"";
			$f[]="\tstop program = \"/etc/init.d/slapd stop --force\"";
			$f[]="\tif cpu usage > 95% for 5 cycles then restart";
			$f[]="\tif totalmem > 550.0 MB for 5 cycles then restart";
			$f[]="\tif failed unixsocket /var/run/slapd/slapd.sock then restart";
			$f[]="\tif 5 restarts within 5 cycles then timeout";
			$f[]="";
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring slapd...\n";}
			@file_put_contents("/etc/monit/conf.d/APP_SLAPD.monitrc", @implode("\n", $f));
		}
	}
// ********************************************************************************************************************
	$f=array();
	build_progress_restart("{reconfiguring} Suricata",32);
	$EnableSuricata=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableSuricata"));
	@unlink("/etc/monit/conf.d/APP_SURICATA.monitrc");
	@unlink("/etc/monit/conf.d/APP_SURICATA_TAIL.monitrc");
	$suricata=$unix->find_program("suricata");
	if(is_file($suricata)){
		if($EnableSuricata==1){
			$f[]="check process APP_SURICATA with pidfile /var/run/suricata/suricata.pid";
			$f[]="\tstart program = \"/etc/init.d/suricata start --monit\"";
			$f[]="\tstop program = \"/etc/init.d/suricata stop --monit\"";
			$f[]="\tif cpu usage > 95% for 5 cycles then restart";
			$f[]="\tif 5 restarts within 5 cycles then timeout";
			$f[]="";
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring Suricata...\n";}
			@file_put_contents("/etc/monit/conf.d/APP_SURICATA.monitrc", @implode("\n", $f));
			
			$f=array();
			$f[]="check process APP_SURICATA_TAIL with pidfile /etc/artica-postfix/exec.suricata-tail.php.pid";
			$f[]="\tstart program = \"/etc/init.d/suricata-tail start --monit\"";
			$f[]="\tstop program = \"/etc/init.d/suricata-tail stop --monit\"";
			$f[]="\tif 5 restarts within 5 cycles then timeout";
			$f[]="";
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring Suricata tail...\n";}
			@file_put_contents("/etc/monit/conf.d/APP_SURICATA_TAIL.monitrc", @implode("\n", $f));
		}
	}
// ********************************************************************************************************************	
	$f=array();
	build_progress_restart("{reconfiguring}",32);
	$winbind=$unix->find_program("winbindd");
	if(is_file("/etc/monit/conf.d/winbindd.monitrc")){@unlink("/etc/monit/conf.d/winbindd.monitrc");}
	@unlink("/etc/monit/conf.d/winbind.monitrc");
	$EnableKerbAuth=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableKerbAuth"));
	if(is_file($winbind)){
		if($EnableKerbAuth==1){
			$f[]="check process winbindd with pidfile /var/run/samba/winbindd.pid";
			$f[]="\tstart program = \"/etc/init.d/winbind start\"";
			$f[]="\tstop program = \"/etc/init.d/winbind stop\"";
			$f[]="\tif 5 restarts within 5 cycles then timeout";
			$f[]="";
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring winbindd...\n";}
			@file_put_contents("/etc/monit/conf.d/winbind.monitrc", @implode("\n", $f));
		}
	}
// ********************************************************************************************************************
	$f=array();
	build_progress_restart("{reconfiguring}",33);
	@unlink("/etc/monit/conf.d/APP_CICAP.monitrc");
	if($users->C_ICAP_INSTALLED){
		if($SQUIDEnable==1){
			$CicapEnabled=$sock->GET_INFO("CicapEnabled");
			if(!is_numeric($CicapEnabled)){$CicapEnabled=0;}
			if($CicapEnabled==1){
				$f[]="check process APP_C_ICAP with pidfile /var/run/c-icap/c-icap.pid";
				$f[]="\tstart program = \"/etc/init.d/artica-postfix start cicap\"";
				$f[]="\tstop program = \"/etc/init.d/artica-postfix stop cicap\"";
				$f[]="\tif 5 restarts within 5 cycles then timeout";
				$f[]="";
				if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring C-ICAP...\n";}
				@file_put_contents("/etc/monit/conf.d/APP_CICAP.monitrc", @implode("\n", $f));
			}
			
		}
	}
// ********************************************************************************************************************	
	build_progress_restart("{reconfiguring}",34);
	@unlink("/etc/monit/conf.d/APP_SYSLOGDB.monitrc");
	if($EnableSyslogDB==1){
		if($MySQLSyslogType==1){
			$f=array();
			$f[]="check process APP_SYSLOG_DB with pidfile /var/run/syslogdb.pid";
			$f[]="\tstart program = \"/etc/init.d/syslog-db start --monit\"";
			$f[]="\tstop program = \"/etc/init.d/syslog-db stop --monit\"";
			$f[]="\tif failed unixsocket /var/run/syslogdb.sock then restart";
			$f[]="\tif 5 restarts within 5 cycles then timeout";
			$f[]="";
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring syslogd...\n";}
			@file_put_contents("/etc/monit/conf.d/APP_SYSLOGDB.monitrc", @implode("\n", $f));			
			$f=array();
		}
		
	}
//********************************************************************************************************************
	$f=array();
	@unlink("/etc/monit/conf.d/cron.monitrc");
	if(is_file("/etc/monit/templates/rootbin")){
		$f[]="check process crond with pidfile /var/run/crond.pid";
		$f[]="   group system";
		$f[]="   group crond";
		$f[]="   start program = \"/etc/init.d/cron start\"";
		$f[]="   stop  program = \"/etc/init.d/cron stop\"";
		$f[]="   if 5 restarts with 5 cycles then timeout";
		$f[]="   depend cron_bin";
		$f[]="   depend cron_rc";
		$f[]="   depend cron_spool";
		$f[]="";
		$f[]=" check file cron_bin with path /usr/sbin/cron";
		$f[]="   group crond";
		$f[]="   include /etc/monit/templates/rootbin";
		$f[]="";
		$f[]=" check file cron_rc with path \"/etc/init.d/cron\"";
		$f[]="   group crond";
		$f[]="   include /etc/monit/templates/rootbin";
		$f[]="";
		$f[]=" check directory cron_spool with path /var/spool/cron/crontabs";
		$f[]="   group crond";
		$f[]="   if failed permission 1730 then unmonitor";
		$f[]="   if failed uid root        then unmonitor";
		$f[]="   if failed gid crontab     then unmonitor";	
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring cron...\n";}
		@file_put_contents("/etc/monit/conf.d/cron.monitrc", @implode("\n", $f));
		$f=array();
	}
	
	@unlink("/etc/monit/conf.d/APP_ZARAFASERVER.monitrc");
	@unlink("/etc/monit/conf.d/APP_ZARAFAGATEWAY.monitrc");
	@unlink("/etc/monit/conf.d/APP_ZARAFAAPACHE.monitrc");
	@unlink("/etc/monit/conf.d/APP_ZARAFAWEB.monitrc");
	@unlink("/etc/monit/conf.d/APP_ZARAFASPOOLER.monitrc");	
	@unlink("/etc/monit/conf.d/APP_ZARAFADB.monitrc");
	build_progress_restart("{reconfiguring}",35);
	

	if(is_file($unix->find_program("zarafa-server"))){
		$ZarafaApacheEnable=$sock->GET_INFO("ZarafaApacheEnable");
		if(!is_numeric($ZarafaApacheEnable)){$ZarafaApacheEnable=1;}
		$ZarafaApachePort=$sock->GET_INFO("ZarafaApachePort");
		if(!is_numeric($ZarafaApachePort)){$ZarafaApachePort=9010;}
		
		if($ZarafaDedicateMySQLServer==1){
			$f=array();
			$f[]="check process APP_ZARAFA_DB with pidfile /var/run/zarafa-db.pid";
			$f[]="\tstart program = \"/etc/init.d/zarafa-db start --monit\"";
			$f[]="\tstop program = \"/etc/init.d/zarafa-db stop --monit\"";
			$f[]="\tif failed unixsocket /var/run/mysqld/zarafa-db.sock then restart";
			$f[]="\tif 5 restarts within 5 cycles then timeout";
			$f[]="";
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring Zarafa Database...\n";}
			@file_put_contents("/etc/monit/conf.d/APP_ZARAFADB.monitrc", @implode("\n", $f));
		}		
		
		
		
		$f=array();
		$f[]="check process APP_ZARAFA_SERVER with pidfile /var/run/zarafa-server.pid";
		$f[]="\tstart program = \"/etc/init.d/zarafa-server start --monit\"";
		$f[]="\tstop program = \"/etc/init.d/zarafa-server stop --monit\"";
		$f[]="\tif failed unixsocket /var/run/zarafa then restart";
		$f[]="\tif 5 restarts within 5 cycles then timeout";
		$f[]="";
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring Zarafa Server...\n";}
		@file_put_contents("/etc/monit/conf.d/APP_ZARAFASERVER.monitrc", @implode("\n", $f));

		$f=array();
		$f[]="check process APP_ZARAFA_SPOOLER with pidfile /var/run/zarafa-spooler.pid";
		$f[]="\tstart program = \"/etc/init.d/zarafa-spooler start --monit\"";
		$f[]="\tstop program = \"/etc/init.d/zarafa-spooler stop --monit\"";
		$f[]="\tif 5 restarts within 5 cycles then timeout";
		$f[]="";
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring Zarafa Spooler...\n";}
		@file_put_contents("/etc/monit/conf.d/APP_ZARAFASPOOLER.monitrc", @implode("\n", $f));		
		
		
		
		
		$f=array();
		$f[]="check process APP_ZARAFA_GATEWAY with pidfile /var/run/zarafa-gateway.pid";
		$f[]="\tstart program = \"/etc/init.d/zarafa-gateway start --monit\"";
		$f[]="\tstop program = \"/etc/init.d/zarafa-gateway stop --monit\"";
		$f[]="\tif 5 restarts within 5 cycles then timeout";
		$f[]="";
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring Zarafa Gateway...\n";}
		@file_put_contents("/etc/monit/conf.d/APP_ZARAFAGATEWAY.monitrc", @implode("\n", $f));		
		
	}

//********************************************************************************************************************
	build_progress_restart("{reconfiguring}",36);
	$EnableClamavDaemon=$sock->GET_INFO("EnableClamavDaemon");
	$EnableClamavDaemonForced=$sock->GET_INFO("EnableClamavDaemonForced");
	$CicapEnabled=$sock->GET_INFO("CicapEnabled");
	$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
	
	if(!is_numeric($EnableClamavDaemon)){$EnableClamavDaemon=0;}
	if(!is_numeric($EnableClamavDaemonForced)){$EnableClamavDaemonForced=0;}
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	if(!is_numeric($CicapEnabled)){$CicapEnabled=0;}
	if($SQUIDEnable==1){if($CicapEnabled==1){$EnableClamavDaemon=1;}}
	if($EnableClamavDaemonForced==1){$EnableClamavDaemon=1;}
//********************************************************************************************************************
	build_progress_restart("{reconfiguring}",37);
	@unlink("/etc/monit/conf.d/APP_CLAMAV.monitrc");
	$MasterBin=$unix->find_program("clamd");
	if(is_file($MasterBin)){
		if($EnableClamavDaemon==1){
			$f=array();
			$f[]="check process APP_CLAMAV";
			$f[]="with pidfile /var/run/clamav/clamd.pid";
			$f[]="start program = \"/etc/init.d/clamav-daemon start --monit\"";
			$f[]="stop program =  \"/etc/init.d/clamav-daemon stop --monit\"";
			$f[]="if 5 restarts within 5 cycles then timeout";
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring Clamd service...\n";}
			@file_put_contents("/etc/monit/conf.d/APP_CLAMAV.monitrc", @implode("\n", $f));
			$f=array();
		}
	}
	
	
	
//********************************************************************************************************************	
	@unlink("/etc/monit/conf.d/ufdb.monitrc");
	@unlink("/etc/monit/conf.d/ufdbweb.monitrc");
	$ufdbbin=$unix->find_program("ufdbguardd");
	build_progress_restart("{reconfiguring}",38);
	if(is_file($ufdbbin)){
		$EnableUfdbGuard=intval($sock->EnableUfdbGuard());
		
		$UseRemoteUfdbguardService=$sock->GET_INFO('UseRemoteUfdbguardService');
		$EnableSquidGuardHTTPService=$sock->GET_INFO("EnableSquidGuardHTTPService");
		$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
		$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
		$SquidGuardApachePort=$sock->GET_INFO("SquidGuardApachePort");
		$SquidGuardApacheSSLPort=$sock->GET_INFO("SquidGuardApacheSSLPort");
		if(!is_numeric($UseRemoteUfdbguardService)){$UseRemoteUfdbguardService=0;}
		if(!is_numeric($EnableUfdbGuard)){$EnableUfdbGuard=0;}
		if(!is_numeric($EnableSquidGuardHTTPService)){$EnableSquidGuardHTTPService=1;}
		if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
		if($EnableUfdbGuard==0){$EnableSquidGuardHTTPService=0;}
		if($EnableWebProxyStatsAppliance==1){$EnableSquidGuardHTTPService=1;}
		if(!is_numeric($SquidGuardApachePort)){$SquidGuardApachePort="9020";}
		if(!is_numeric($SquidGuardApacheSSLPort)){$SquidGuardApacheSSLPort=9025;}
		if($SquidPerformance>2){$EnableSquidGuardHTTPService=0;}
		
		if($SQUIDEnable==1){	
			
			if($EnableSquidGuardHTTPService==1){
				$f=array();
				$f[]="check process APP_SQUIDGUARD_HTTP";
				$f[]="with pidfile /var/run/lighttpd/squidguard-lighttpd.pid";
				$f[]="start program = \"/etc/init.d/squidguard-http start --monit\"";
				$f[]="stop program =  \"/etc/init.d/squidguard-http stop --monit\"";
				$f[]="if failed host 127.0.0.1 port $SquidGuardApachePort then restart";
				$f[]="if 5 restarts within 5 cycles then timeout";
				if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring Web filtering HTTP service...\n";}
				@file_put_contents("/etc/monit/conf.d/ufdbweb.monitrc", @implode("\n", $f));
				
			}
			
			
		}
	}
	
//********************************************************************************************************************	
	$EnableArticaFrontEndToNGninx=$sock->GET_INFO("EnableArticaFrontEndToNGninx");
	$EnableArticaFrontEndToApache=$sock->GET_INFO("EnableArticaFrontEndToApache");
	if(!is_numeric($EnableArticaFrontEndToNGninx)){$EnableArticaFrontEndToNGninx=0;}
	if(!is_numeric($EnableArticaFrontEndToApache)){$EnableArticaFrontEndToApache=0;}
	$EnableNginx=$sock->GET_INFO("EnableNginx");
	$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}
	if(!is_numeric($EnableNginx)){$EnableNginx=1;}
	if($EnableNginx==0){$EnableArticaFrontEndToNGninx=0;}
	$pid=null;
	build_progress_restart("{reconfiguring}",39);
	@unlink("/etc/monit/conf.d/APP_LIGHTTPD.monitrc");
	if($EnableArticaFrontEndToNGninx==0){
		$pid="/var/run/lighttpd/lighttpd.pid";
		if($EnableArticaFrontEndToApache==1){$pid="/var/run/artica-apache/apache.pid";}
		$f=array();
		$f[]="check process APP_ARTICAWEBCONSOLE with pidfile $pid";
		$f[]="\tstart program = \"/etc/init.d/artica-webconsole start --monit\"";
		$f[]="\tstop program = \"/etc/init.d/artica-webconsole stop --monit\"";
		$f[]="\tif 5 restarts within 5 cycles then timeout";
		$f[]="";
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring Artica Web Console...\n";}
		@file_put_contents("/etc/monit/conf.d/APP_LIGHTTPD.monitrc", @implode("\n", $f));
	}
	//********************************************************************************************************************		
	@unlink("/etc/monit/conf.d/APP_NGINX.monitrc");
	$nginx=$unix->find_program("nginx");
	if(is_file($nginx)){
		if($EnableNginx==1){
			
			$f=array();
			$f[]="check process APP_NGINX with pidfile /var/run/nginx.pid";
			$f[]="\tstart program = \"/etc/init.d/nginx start --monit\"";
			$f[]="\tstop program = \"/etc/init.d/nginx stop --monit\"";
			$f[]="\tif 5 restarts within 5 cycles then timeout";
			$f[]="";
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring NgINX...\n";}
			@file_put_contents("/etc/monit/conf.d/APP_NGINX.monitrc", @implode("\n", $f));			
			
		}
		
	}
	//********************************************************************************************************************	
	
	build_progress_restart("{reconfiguring}",40);
	$f=array();
	if(is_file("/etc/init.d/sysklogd")){
		$f[]="check process APP_SYSLOGD with pidfile /var/run/syslogd.pid";
		$f[]="\tstart program = \"/etc/init.d/sysklogd start --monit\"";
		$f[]="\tstop program = \"/etc/init.d/sysklogd stop --monit\"";
		$f[]="\tif 5 restarts within 5 cycles then timeout";
		$f[]="\tcheck file syslogd_file with path /var/log/syslog";
		$f[]="\tif timestamp > 10 minutes then restart";
		$f[]="";
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring sysklogd...\n";}
		@file_put_contents("/etc/monit/conf.d/APP_SYSKLOGD.monitrc", @implode("\n", $f));
	}
	
//********************************************************************************************************************	
	build_progress_restart("{reconfiguring}",41);
	$binpath=$unix->DHCPD_BIN_PATH();
	@unlink("/etc/monit/conf.d/APP_DHCPD.monitrc");
	$f=array();
	if(is_file($binpath)){
		$EnableDHCPServer=$sock->GET_INFO("EnableDHCPServer");
		if(!is_numeric($EnableDHCPServer)){$EnableDHCPServer=0;}
		if($EnableDHCPServer==1){
		$f[]="check process APP_DHCP with pidfile /var/run/dhcpd.pid";
		$f[]="\tstart program = \"/etc/init.d/isc-dhcp-server start --monit\"";
		$f[]="\tstop program = \"/etc/init.d/isc-dhcp-server stop --monit\"";
		$f[]="\tif 5 restarts within 5 cycles then timeout";
		$f[]="";
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring DHCP Service...\n";}
		@file_put_contents("/etc/monit/conf.d/APP_DHCPD.monitrc", @implode("\n", $f));
		}
	}
//********************************************************************************************************************	
$binpath=$unix->find_program("rdpproxy");
build_progress_restart("{reconfiguring}",42);
@unlink("/etc/monit/conf.d/APP_RDPPROXY.monitrc");
$f=array();
if(is_file($binpath)){
	$EnableRDPProxy=$sock->GET_INFO("EnableRDPProxy");
	if(!is_numeric($EnableRDPProxy)){$EnableRDPProxy=0;}
	if($EnableRDPProxy==1){
		$f[]="check process APP_RDPPROXY with pidfile /var/run/redemption/rdpproxy.pid";
		$f[]="\tstart program = \"/etc/init.d/rdpproxy start --monit\"";
		$f[]="\tstop program = \"/etc/init.d/rdpproxy stop --monit\"";
		$f[]="\tif 5 restarts within 5 cycles then timeout";
		$f[]="";
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring RDP Proxy...\n";}
		@file_put_contents("/etc/monit/conf.d/APP_RDPPROXY.monitrc", @implode("\n", $f));
	}
}
//********************************************************************************************************************
build_progress_restart("{reconfiguring}",43);
@unlink("/etc/monit/conf.d/APP_DNSMASQ.monitrc");
$f=array();
$binpath=$unix->find_program("dnsmasq");
if(is_file($binpath)){
	$EnableDNSMASQ=$users->EnableDNSMASQ();
	if($EnableDNSMASQ==1){
		$f[]="check process APP_DNSMASQ with pidfile /var/run/dnsmasq.pid";
		$f[]="\tstart program = \"/etc/init.d/dnsmasq start --monit\"";
		$f[]="\tstop program = \"/etc/init.d/dnsmasq stop --monit\"";
		$f[]="\tif 5 restarts within 5 cycles then timeout";
		$f[]="";
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring DNSMasq Service...\n";}
		@file_put_contents("/etc/monit/conf.d/APP_DNSMASQ.monitrc", @implode("\n", $f));
	}
}
//********************************************************************************************************************	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} checking syslog\n";}
	if(is_file("/etc/init.d/syslog")){checkDebSyslog();}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} configuration done\n";}
	shell_exec($GLOBALS["MONIT_CLASS"]->monitor_all_cmdline." 2>&1");
	build_progress_restart("{reconfiguring}",45);
}
function checkDebSyslog(){
	if(!is_file("/etc/rsyslog.conf")){return;}
	$f=file("/etc/init.d/syslog");
	$RSYSLOGD_PIDFILE=null;
	while (list ($num, $line) = each ($f)){
		if(preg_match("#RSYSLOGD_PIDFILE=(.+)#", $line,$re)){
			$RSYSLOGD_PIDFILE=$re[1];
			break;
		}
	}

	$filesize=filesize("/etc/init.d/syslog");
	if($filesize<50){$RSYSLOGD_PIDFILE="/var/run/rsyslogd.pid";}
	if($RSYSLOGD_PIDFILE==null){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} pidfile `cannot check pid...`\n";return;}}

if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} rsyslog pidfile `$RSYSLOGD_PIDFILE`\n";}

	$f=file("/etc/rsyslog.conf");
	while (list ($num, $line) = each ($f)){
		if(preg_match("#\*\.\*.*?\s+(.+)#", $line,$re)){
			$syslogpath=$re[1];
			if(substr($syslogpath, 0,1)=='-'){$syslogpath=substr($syslogpath, 1,strlen($syslogpath));}
			break;
		}
		 
	}

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} syslog path `$syslogpath`\n";}
	if(!is_file($syslogpath)){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} syslog path `$syslogpath` no such file!\n";return;}}

	$f=array();
	$f[]="check process APP_SYSLOGD with pidfile $RSYSLOGD_PIDFILE";
	$f[]="start program = \"/etc/init.d/syslog start --monit\"";
	$f[]="stop program = \"/etc/init.d/syslog stop --monit\"";
	$f[]="if 5 restarts within 5 cycles then timeout";
	@chmod("/etc/init.d/syslog",0755);
	@file_put_contents("/etc/monit/conf.d/APP_RSYSLOGD.monitrc", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/monit/conf.d/APP_RSYSLOGD.monitrc done\n";}
}
?>