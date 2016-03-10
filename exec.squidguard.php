<?php
if(isset($_GET["verbose"])){ini_set_verbosedx();}else{	ini_set('display_errors', 0);ini_set('error_reporting', 0);}
$GLOBALS["KAV4PROXY_NOSESSION"]=true;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["RELOAD"]=false;
$GLOBALS["RESTART"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["WRITELOGS"]=false;
$GLOBALS["TITLENAME"]="URLfilterDB daemon";
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
$_GET["LOGFILE"]="/var/log/artica-postfix/dansguardian.compile.log";
if(posix_getuid()<>0){
	$ID=0;
	$TARGET_GROUP_SOURCE=null;
	$CATEGORY_SOURCE=null;
	$fatalerror=null;
	$HTTP_HOST=null;
	header("Pragma: no-cache");
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	$proto="http";
	$HTTP_HOST=$_SERVER["HTTP_HOST"];
	$REQUEST_URI=$_SERVER["REQUEST_URI"];
	$SERVER_NAME=$_SERVER["SERVER_NAME"];
	if(isset($_GET["fatalerror"])){$ID=0;$fatalerror="&fatalerror=yes";}
	if(isset($_GET["loading-database"])){$ID=0;$fatalerror="&loading-database=yes";}
	if($HTTP_HOST==null){$HTTP_HOST=$SERVER_NAME;}
	$SquidGuardServerName=@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidGuardServerName");
	$SquidGuardApachePort=intval(@file_get_contents("/etc/artica-postfix/settings/SquidGuardApachePort"));
	$SquidGuardApacheSSLPort=intval(@file_get_contents("/etc/artica-postfix/settings/SquidGuardApacheSSLPort"));
	if($SquidGuardApacheSSLPort==0){$SquidGuardApacheSSLPort=9025;}
	if($SquidGuardApachePort==0){$SquidGuardApachePort=9020;}
	$localport=$SquidGuardApachePort;
	
	if(isset($_SERVER["HTTPS"])){$proto="https";$localport=$SquidGuardApacheSSLPort;}
	if(isset($_GET["rule-id"])){$ID=$_GET["rule-id"];}
	if(isset($_GET["category"])){$CATEGORY_SOURCE=$_GET["category"];}
	if(isset($_GET["targetgroup"])){
		$TARGET_GROUP_SOURCE=$_GET["targetgroup"];
		if($CATEGORY_SOURCE==null){$CATEGORY_SOURCE=$TARGET_GROUP_SOURCE;}
	}
	$uri="$proto://$HTTP_HOST/$REQUEST_URI";
	if(isset($_GET["url"])){$uri=$_GET["url"];}
	$uri=urlencode($uri);
	$link="$proto://$SquidGuardServerName:$localport/ufdbguardd.php?rule-id=$ID&category=$CATEGORY_SOURCE&targetgroup=$TARGET_GROUP_SOURCE{$fatalerror}&url=$uri\n";
	
	$data="
	<html>
	<head>
		<meta http-equiv=\"refresh\" content=\"0; url=$link/\" />
	</head>
			<body>
				<center><H1>Please redirecting...</H1></center>
			</body>
	</html>";
	echo $data;
	
	die();
}

if(preg_match("#--ouput#",implode(" ",$argv),$re)){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#--schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
$GLOBALS["GETPARAMS"]=@implode(" Params:",$argv);
$GLOBALS["CMDLINEXEC"]=@implode("\nParams:",$argv);

include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dansguardian.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.squidguard.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/ressources/class.compile.ufdbguard.inc");
include_once(dirname(__FILE__)."/ressources/class.compile.dansguardian.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.ufdbguard-tools.inc');
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/ressources/class.ufdb.microsoft.inc");


if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(count($argv)>0){
	$imploded=implode(" ",$argv);
	
	if(preg_match("#--(output|ouptut)#",$imploded)){
		$GLOBALS["OUTPUT"]=true;
	}
	
	if(preg_match("#--verbose#",$imploded)){
			$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;
			$GLOBALS["OUTPUT"]=true;ini_set_verbosed(); 
	}
	
	
	
	if(preg_match("#--reload#",$imploded)){$GLOBALS["RELOAD"]=true;}
	if(preg_match("#--force#",$imploded)){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--shalla#",$imploded)){$GLOBALS["SHALLA"]=true;}
	if(preg_match("#--restart#",$imploded)){$GLOBALS["RESTART"]=true;}
	if(preg_match("#--catto=(.+?)\s+#",$imploded,$re)){$GLOBALS["CATTO"]=$re[1];}
	if($argv[1]=="--disks"){DisksStatus();exit;}
	if($argv[1]=="--version"){checksVersion();exit;}
	if($argv[1]=="--dump-adrules"){dump_adrules($argv[2]);exit;}
	if($argv[1]=="--dbmem"){ufdbdatabases_in_mem();exit;}
	if($argv[1]=="--notify-start"){ufdguard_start_notify();exit;}
	if($argv[1]=="--artica-db-status"){ufdguard_artica_db_status();exit;}
	
	
	
	
	$argvs=$argv;
	unset($argvs[0]);
	
	if($argv[1]=="--stop"){stop_ufdbguard();exit;}
	if($argv[1]=="--reload"){build_ufdbguard_HUP();exit;}
	if($argv[1]=="--reload-ufdb"){build_ufdbguard_HUP();exit;}
	if($argv[1]=="--dansguardian"){buildDans();exit;}
	if($argv[1]=="--databases-status"){databases_status();exit;}
	if($argv[1]=="--ufdbguard-status"){print_r(UFDBGUARD_STATUS());exit;}
	if($argv[1]=="--cron-compile"){cron_compile();exit;}
	if($argv[1]=="--compile-category"){UFDBGUARD_COMPILE_CATEGORY($argv[2]);exit;}
	if($argv[1]=="--compile-all-categories"){UFDBGUARD_COMPILE_ALL_CATEGORIES();exit;}
	if($argv[1]=="--ufdbguard-recompile-dbs"){echo UFDBGUARD_COMPILE_ALL_CATEGORIES();exit;}
	if($argv[1]=="--phraselists"){echo CompileCategoryWords();exit;}
	if($argv[1]=="--fix1"){echo FIX_1_CATEGORY_CHECKED();exit;}
	if($argv[1]=="--bads"){echo remove_bad_files();exit;}
	if($argv[1]=="--reload131"){exit;}
	
	
	
	$GLOBALS["EXECUTEDCMDLINE"]=@implode(" ", $argvs);
	ufdbguard_admin_events("receive ".$GLOBALS["EXECUTEDCMDLINE"],"MAIN",__FILE__,__LINE__,"config");
	if($GLOBALS["VERBOSE"]){echo "Execute ".@implode(" ", $argv)."\n";}
	
	if($argv[1]=="--inject"){echo inject($argv[2],$argv[3]);exit;} // category filepath
	if($argv[1]=="--parse"){echo inject($argv[2],$argv[3],$argv[4]);exit;}
	if($argv[1]=="--conf"){echo build();exit;}
	if($argv[1]=="--ufdb-monit"){echo ufdbguard_watchdog();exit;}
	
	
	if($argv[1]=="--ufdbguard-compile"){echo UFDBGUARD_COMPILE_SINGLE_DB($argv[2]);exit;}	
	if($argv[1]=="--ufdbguard-dbs"){echo UFDBGUARD_COMPILE_DB();exit;}
	if($argv[1]=="--ufdbguard-miss-dbs"){echo ufdbguard_recompile_missing_dbs();exit;}
	
	if($argv[1]=="--ufdbguard-schedule"){ufdbguard_schedule();exit;}
	if($argv[1]=="--ufdbguard-start"){ufdbguard_start();exit;}
	if($argv[1]=="--list-missdbs"){BuildMissingUfdBguardDBS(false,true);exit;}				
	if($argv[1]=="--parsedir"){ParseDirectory($argv[2]);exit;}
	if($argv[1]=="--notify-dnsmasq"){notify_remote_proxys_dnsmasq();exit;}
	if($argv[1]=='--build-ufdb-smoothly'){$GLOBALS["FORCE"]=true;echo build_ufdbguard_smooth();echo "Starting......: ".date("H:i:s")." Starting UfdGuard FINISH DONE\n";exit;}
	if($argv[1]=='--apply-restart'){$GLOBALS["FORCE"]=true;echo build_ufdbguard_restart();;exit;}
	
	
}
	


$unix=new unix();
$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".MAIN.pid";
$pid=@file_get_contents($pidfile);
if($unix->process_exists($pid,basename(__FILE__))){
	$timefile=$unix->PROCCESS_TIME_MIN($pid);
	if($timefile<6){
		writelogs(basename(__FILE__).": Already running PID $pid since {$timefile}mn.. aborting the process",
		basename(__FILE__),__FILE__,__LINE__);
		die();
	}else{
		$kill=$unix->find_program("kill");
		unix_system_kill_force($pid);
	}
}
@file_put_contents($pidfile, getmypid());
if($GLOBALS["VERBOSE"]){echo "New PID ".getmypid()." [1]={$argv[1]}\n";}

if($argv[1]=="--categories"){build_categories();exit;}
if(isset($argv[2])){if($argv[2]=="--reload"){$GLOBALS["RELOAD"]=true;}}
if($argv[1]=="--build"){build();die();}
if($argv[1]=="--status"){echo status();exit;}
if($argv[1]=="--compile"){echo compile_databases();exit;}
if($argv[1]=="--db-status"){print_r(databasesStatus());exit;}
if($argv[1]=="--db-status-www"){echo serialize(databasesStatus());exit;}

if($argv[1]=="--compile-single"){echo CompileSingleDB($argv[2]);exit;}
if($argv[1]=="--conf"){echo conf();exit;}



//http://cri.univ-tlse1.fr/documentations/cache/squidguard.html


function build_categories(){
	$q=new mysql_squid_builder();
	
	$sql="SELECT LOWER(pattern) FROM category_porn WHERE enabled=1 AND pattern REGEXP '[a-zA-Z0-9\_\-]+\.[a-zA-Z0-9\_\-]+' ORDER BY pattern INTO OUTFILE '/tmp/porn.txt' FIELDS OPTIONALLY ENCLOSED BY 'n'";
	$q->QUERY_SQL($sql);	
	if(!$q->ok){echo $q->mysql_error."\n";}
	
	
}

function build_progress($text,$pourc){
	echo "[{$pourc}%]: $text\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/ufdbguard.compile.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}


function build_ufdbguard_restart(){
	$GLOBALS["build_ufdbguard_HUP_EXECUTED"]=true;
	$GLOBALS["FORCE"]=true;
	build_ufdbguard_config();
	build_progress("{apply_restart}: {restarting_service}",70);
	system("/etc/init.d/ufdb restart --force");
	
	build_progress("{apply_restart}: {restarting_service}",80);
	system("/etc/init.d/squidguard-http restart --force");
	
	build_progress("{apply_restart}: {reloading_proxy_service}",90);
	$unix=new unix();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	exec("$squidbin -f /etc/squid3/squid.conf -k reconfigure 2>&1",$RESULTS);
	squid_admin_mysql(1,"Reloading proxy service (Web filtering)",@implode("\n", $RESULTS),__FILE__,__LINE__);
	sleep(5);
	build_progress("{apply_restart}: {done}",100);
}


function build_ufdbguard_smooth(){
	$users=new usersMenus();
	$unix=new unix();
	if(!$users->APP_UFDBGUARD_INSTALLED){echo "Starting......: ".date("H:i:s")." Webfiltering service is not installed, aborting\n";return;}
	$sock=new sockets();
	$php=$unix->LOCATE_PHP5_BIN();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	if($EnableRemoteStatisticsAppliance==1){
		echo "Starting......: ".date("H:i:s")." It use Statistics appliance, aborting\n";
		build_progress("use Statistics appliance, aborting",110);
		return;
	}
	if(function_exists('WriteToSyslogMail')){WriteToSyslogMail("build_ufdbguard_smooth() -> reconfigure UfdbGuardd", basename(__FILE__));}
	
	echo "Starting......: ".date("H:i:s")." Webfiltering service ". date("Y-m-d H:i:s")."\n";
	build_ufdbguard_config();
	build_progress("{reloading_service}",70);
	if(!build_ufdbguard_HUP()){
		build_progress("{reloading_service} {failed}",75);
		ufdbguard_start();
	}
	
	if(!build_ufdbguard_isinconf()){
		build_progress("{reconfiguring_proxy_service}",95);
		system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
		
	}
	
	
	build_progress("{done}",100);
}


function build_ufdbguard_isinconf(){

	$squidconf="/etc/squid3/squid.conf";
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableTransparent27")){@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableTransparent27", 0);}
	$EnableTransparent27=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableTransparent27"));
	if($EnableTransparent27==1){$squidconf="/etc/squid27/squid.conf";}
	
	$f=explode("\n",@file_get_contents($squidconf));
	while (list($num,$val)=each($f)){
		if(preg_match("#ufdbgclient#i", $val)){return true;}
	}

}


function build_ufdbguard_HUP(){
	if(isset($GLOBALS["build_ufdbguard_HUP_EXECUTED"])){return;}
	$GLOBALS["build_ufdbguard_HUP_EXECUTED"]=true;
	$unix=new unix();
	$sock=new sockets();$forceTXT=null;
	$ufdbguardReloadTTL=intval($sock->GET_INFO("ufdbguardReloadTTL"));
	if($ufdbguardReloadTTL<1){$ufdbguardReloadTTL=10;}
	$php5=$unix->LOCATE_PHP5_BIN();
	$rm=$unix->find_program("rm");
	shell_exec("$php5 /usr/share/artica-postfix/exec.ufdbclient.reload.php");
	shell_exec("$rm /home/squid/error_page_cache/*");
	
	if(function_exists("debug_backtrace")){
		$trace=@debug_backtrace();
		if(isset($trace[1])){
			$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
		}
	}
	$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
	
	$timeFile="/etc/artica-postfix/pids/UfdbGuardReload.time";
	$TimeReload=$unix->file_time_min($timeFile);
	if(!$GLOBALS["FORCE"]){
		if($TimeReload<$ufdbguardReloadTTL){
			build_progress("{reloading_service} {failed}",110);
			$unix->_syslog("Webfiltering service Aborting reload, last reload since {$TimeReload}Mn, need at least {$ufdbguardReloadTTL}Mn", basename(__FILE__));
			echo "Starting......: ".date("H:i:s")." Webfiltering service Aborting reload, last reload since {$TimeReload}Mn, need at least {$ufdbguardReloadTTL}Mn\n";
			return;
		}
	}else{
		echo "Starting......: ".date("H:i:s")." --- FORCED --- ufdbGuard last reload was {$TimeReload}mn\n";
	}
	@unlink($timeFile);
	@file_put_contents($timeFile, time());
	
	$pid=ufdbguard_pid();
	build_progress("{reloading_service} $pid",71);
	$squidbin=$unix->LOCATE_SQUID_BIN();
	$ufdbguardd=$unix->find_program("ufdbguardd");
	if(strlen($ufdbguardd)<5){WriteToSyslogMail("ufdbguardd no such binary", basename(__FILE__));return;}
	$kill=$unix->find_program("kill");

	
	
	
if($unix->process_exists($pid)){
		$processTTL=intval($unix->PROCCESS_TIME_MIN($pid));
		
		$LastTime=intval($unix->file_time_min($timeFile));
		build_progress("{reloading_service} $pid {$processTTL}Mn",72);
		
		echo "Starting......: ".date("H:i:s")." Webfiltering service Reloading service TTL {$processTTL}Mn\n";
		echo "Starting......: ".date("H:i:s")." Webfiltering service Reloading service Last config since {$LastTime}Mn\n";
		echo "Starting......: ".date("H:i:s")." Webfiltering service Reloading Max reload {$ufdbguardReloadTTL}Mn\n";
		
		if(!$GLOBALS["FORCE"]){
			echo "Starting......: ".date("H:i:s")." Webfiltering service Reloading force is disabled\n";
			if($LastTime<$ufdbguardReloadTTL){
				squid_admin_mysql(2, "Reloading Web Filtering PID: $pid [Aborted] last reload {$LastTime}Mn, need {$ufdbguardReloadTTL}mn",null,__FILE__,__LINE__);
				echo "Starting......: ".date("H:i:s")." Webfiltering service Reloading service Aborting... minimal time was {$ufdbguardReloadTTL}mn - Current {$LastTime}mn\n$called\n";
				return;
			}			
			
			
			if($processTTL<$ufdbguardReloadTTL){
				squid_admin_mysql(2, "Reloading Web Filtering PID: $pid [Aborted] {$processTTL}Mn, need {$ufdbguardReloadTTL}mn",null,__FILE__,__LINE__);
				echo "Starting......: ".date("H:i:s")." Webfiltering service PID: $pid  Reloading service Aborting... minimal time was {$ufdbguardReloadTTL}mn\n$called\n";
				return;
			}
		}
		
		
		if($GLOBALS["FORCE"]){ $forceTXT=" with option FORCE enabled";$prefix="[FORCED]:";}
		@unlink($timeFile);
		@file_put_contents($timeFile, time());
		
		echo "Starting......: ".date("H:i:s")." Webfiltering service Reloading service PID:$pid {$processTTL}mn\n";
		squid_admin_mysql(1, "{$prefix}Reloading Web Filtering service PID: $pid TTL {$processTTL}Mn","$forceTXT\n$called\n{$GLOBALS["CMDLINEXEC"]}");
		
		build_progress("{reloading_service} HUP $pid",75);
		unix_system_HUP($pid);
		build_progress("{reloading_proxy_service}",76);
		shell_exec("$php5 /usr/share/artica-postfix/exec.ufdbclient.reload.php");
		$squidbin=$unix->LOCATE_SQUID_BIN();
		squid_admin_mysql(1, "{$prefix}Reloading Proxy service",null,__FILE__,__LINE__);
		system("$squidbin -k reconfigure");
		return true;
}
	
	squid_admin_mysql(1, "Warning, Reloading Web Filtering but not running [action=start]","$forceTXT\n$called\n{$GLOBALS["CMDLINEXEC"]}");
	echo "Starting......: ".date("H:i:s")." Webfiltering service reloading service no pid is found, Starting service...\n";
	@unlink($timeFile);
	@file_put_contents($timeFile, time());
	build_progress("{starting_service}",76);
	if(!ufdbguard_start()){return;}
	
	echo "Starting......: ".date("H:i:s")." Webfiltering Service restarting ufdb-tail process\n";
	shell_exec("/etc/init.d/ufdb-tail restart");
	shell_exec("$php5 /usr/share/artica-postfix/exec.ufdbclient.reload.php");
	squid_admin_mysql(1, "{$prefix}Reloading Proxy service",null,__FILE__,__LINE__);
	system("$squidbin -k reconfigure");
	build_progress("{starting_service} {done}",77);
	return true;
}

function ufdbguard_pid(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/tmp/ufdbguardd.pid");
	if($unix->process_exists($pid)){
		$cmdline=trim(@file_get_contents("/proc/$pid/cmdline"));
		if(!preg_match("#ufdbcatdd#", $cmdline)){return $pid;}
	}
	$ufdbguardd=$unix->find_program("ufdbguardd");
	return $unix->PIDOF($ufdbguardd);
}

function ufdguard_start_notify(){
	squid_admin_mysql(2, "{starting_web_filtering} engine service by init.d script","",__FILE__,__LINE__);
	$unix=new unix();
	$fuser=$unix->find_program("fuser");
	$port=ufdguard_get_listen_port();
	$results=array();
	echo "Starting......: ".date("H:i:s")." Webfiltering service Listen on port $port\n";
	$cmd="$fuser $port/tcp 2>&1";
	exec("$cmd",$results);
	echo "Starting......: ".date("H:i:s")." Webfiltering service `$cmd` ". count($results) ." lines.\n";
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#$port\/tcp:(.+)#", $ligne,$re)){
			$ff=explode(" ", $re[1]);
			while (list ($index, $ligne2) = each ($ff) ){
				$ligne2=trim($ligne2);
				if(!is_numeric($ligne2)){continue;}
				echo "Starting......: ".date("H:i:s")." Webfiltering service killing PID $ligne2\n";
				$unix->KILL_PROCESS($ligne2,9);
			}
		}
	}
}


function ufdguard_get_listen_port(){
	$f=explode("\n",@file_get_contents("/etc/squid3/ufdbGuard.conf"));
	while (list ($index, $ligne) = each ($f) ){
		if(preg_match("#^port\s+([0-9]+)#", $ligne,$re)){return $re[1];}
		
	}
	return 3977;
}




function ufdbguard_start(){
	$unix=new unix();
	$sock=new sockets();
	$nohup=$unix->find_program("nohup");
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		build_progress("Already task executed", 110);
		echo "Starting......: ".date("H:i:s")." Webfiltering service Starting service aborted, task pid already running $pid\n";
		writelogs(basename(__FILE__).":Already executed.. aborting the process",basename(__FILE__),__FILE__,__LINE__);
		return;
	}
	@file_put_contents($pidfile, getmypid());	
	
	
	$pid_path="/var/tmp/ufdbguardd.pid";
	if(!is_dir("/var/tmp")){@mkdir("/var/tmp",0775,true);}
	$ufdbguardd_path=$unix->find_program("ufdbguardd");
	$master_pid=ufdbguard_pid();

	if(!$unix->process_exists($master_pid)){
		if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("UfdGuard master Daemon seems to not running, trying with pidof", basename(__FILE__));}
		$master_pid=$unix->PIDOF($ufdbguardd_path);
		if($unix->process_exists($master_pid)){
			echo "Starting......: ".date("H:i:s")." UfdGuard master is running, updating PID file with $master_pid\n";
			if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("UfdGuard master is running, updating PID file with $master_pid", basename(__FILE__));}
			@file_put_contents($pid_path,$master_pid);	
			build_progress("Already running...",76);
			return true;
		}
	}
	$EnableUfdbGuard=intval($sock->EnableUfdbGuard());
	$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
	$UseRemoteUfdbguardService=$sock->GET_INFO('UseRemoteUfdbguardService');
	if(!is_numeric($UseRemoteUfdbguardService)){$UseRemoteUfdbguardService=0;}
	
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	if($UseRemoteUfdbguardService==1){$EnableUfdbGuard=0;}
	if($SQUIDEnable==0){$EnableUfdbGuard=0;}
	if($EnableUfdbGuard==0){echo "Starting......: ".date("H:i:s")." Starting UfdGuard master service Aborting, service is disabled\n";return;}
	$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
	squid_admin_mysql(2, "{starting_web_filtering} engine service","$trace\n{$GLOBALS["CMDLINEXEC"]}");
	ufdbguard_admin_events("Asking to start ufdbguard $trace",__FUNCTION__,__FILE__,__LINE__,"ufdbguard-service");	
	echo "Starting......: ".date("H:i:s")." Starting UfdGuard master service...\n";
	if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("Starting UfdGuard master service...", basename(__FILE__));}
	@mkdir("/var/log/ufdbguard",0755,true);
	@file_put_contents("/var/log/ufdbguard/ufdbguardd.log", "#");
	@chown("/var/log/ufdbguard/ufdbguardd.log", "squid");
	@chgrp("/var/log/ufdbguard/ufdbguardd.log", "squid");	
	
	
	shell_exec("$nohup /etc/init.d/ufdb start >/dev/null 2>&1 &");
	
	
	for($i=1;$i<5;$i++){
		build_progress("Starting {webfiltering} waiting $i/5",76);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." Starting UfdGuard  waiting $i/5\n";}
		sleep(1);
		$pid=ufdbguard_pid();
		if($unix->process_exists($pid)){break;}
	}
	
	echo "Starting......: ".date("H:i:s")." Starting UfdGuard master init.d ufdb done...\n";
	$master_pid=ufdbguard_pid();
	if(!$unix->process_exists($master_pid)){
		echo "Starting......: ".date("H:i:s")." Starting UfdGuard master service failed...\n";
		squid_admin_mysql(0, "{starting_web_filtering} engine service failed","$trace\n{$GLOBALS["CMDLINEXEC"]}\n");
		return false;
	}
	echo "Starting......: ".date("H:i:s")." Starting UfdGuard master success pid $master_pid...\n";
	squid_admin_mysql(2, "{starting_web_filtering} engine service success","$trace\n{$GLOBALS["CMDLINEXEC"]}\n");
	echo "Starting......: ".date("H:i:s")." Starting UfdGuard master ufdbguard_start() function done\n";
	return true;
	
}

function checksVersion(){
	$unix=new unix();
	$ufdbguardd=$unix->find_program("ufdbguardd");
	if(!is_file($ufdbguardd)){return;}
	$mustcompile=false;
	exec("ufdbguardd -v 2>&1",$results);
	while (list ($a, $line) = each ($results)){
		
		if(preg_match("#ufdbguardd:\s+([0-9\.]+)#", $line,$re)){
			$version=$re[1];
			$version=str_replace(".", "", $version);
			break;
		}
	}
	
	echo "Starting......: ".date("H:i:s")." Starting UfdGuard binary version $version\n";
	if($version<130){$mustcompile=true;}
	
	
	if(!$mustcompile){
		$binadate=filemtime($ufdbguardd);
		$fileatime=fileatime($ufdbguardd);
		echo "Starting......: ".date("H:i:s")." Starting UfdGuard version date $binadate (".date("Y-m-d",$binadate).")\n";
		if($binadate<1358240994){
			$mustcompile=true;
		}
	}
	
	if($mustcompile){
		echo "Starting......: ".date("H:i:s")." Starting UfdGuard must be updated !!\n";
		shell_exec("/usr/share/artica-postfix/bin/artica-make APP_UFDBGUARD");
	}
	
}


function build_ufdbguard_config(){
	checksVersion();
	$sock=new sockets();
	$DenyUfdbWriteConf=$sock->GET_INFO("DenyUfdbWriteConf");
	if(!is_numeric($DenyUfdbWriteConf)){$DenyUfdbWriteConf=0;}
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	if($EnableRemoteStatisticsAppliance==1){return;}	
	$unix=new unix();
	$chmod=$unix->find_program("chmod");
	$chown=$unix->find_program("chown");
	$ln=$unix->find_program("ln");
	$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}	
	$unix->send_email_events("Order to rebuild ufdbGuard config" , $called, "proxy");
	$sock=new sockets();	
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	$users=new usersMenus();
	
	@mkdir("/var/tmp",0775,true);
	@mkdir("/etc/ufdbguard",0777,true);
	@mkdir("/etc/squid3",0755,true);
	@mkdir("/var/log/squid",0755,true);
	@mkdir("/var/lib/ufdbartica",0755,true);
	@unlink("/etc/ufdbguard/ufdbGuard.conf");
	@unlink("/etc/squid3/ufdbGuard.conf");	
	remove_bad_files();
	
	build_progress("Building parameters",10);
	
	$ufdb=new compile_ufdbguard();
	$datas=$ufdb->buildConfig();	
	
	if(is_file("/var/log/squid/UfdbguardCache.db")){@unlink("/var/log/squid/UfdbguardCache.db"); }
	
	
	if($EnableWebProxyStatsAppliance==1){
		@file_put_contents("/usr/share/artica-postfix/ressources/databases/ufdbGuard.conf",$datas);
	}

	if($DenyUfdbWriteConf==0){
		build_progress("Saving configuration",60);
		@file_put_contents("/etc/ufdbguard/ufdbGuard.conf",$datas);
		@file_put_contents("/etc/squid3/ufdbGuard.conf",$datas);
		$sock->TOP_NOTIFY("{webfiltering_parameters_was_saved}");
	}
	shell_exec("$chmod 755 /etc/squid3/ufdbGuard.conf");
	shell_exec("$chmod -R 755 /etc/squid3/ufdbGuard.conf");
	shell_exec("$chmod -R 755 /etc/ufdbguard");	
	
	shell_exec("chown -R squid:squid /etc/ufdbguard");
	shell_exec("chown -R squid:squid /var/log/squid");
	shell_exec("chown -R squid:squid /etc/squid3");
	shell_exec("chown -R squid:squid /var/lib/ufdbartica");
	build_progress("Saving configuration {done}",65);
	
}


function conf(){
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	if($EnableRemoteStatisticsAppliance==1){return;}	
	$users=new usersMenus();
	$sock=new sockets();
	$unix=new unix();
	
	@mkdir("/var/tmp",0775,true);
	
	
	if(!is_file("/var/log/ufdbguard/ufdbguardd.log")){
		@mkdir("/var/log/ufdbguard",0755,true);
		@file_put_contents("/var/log/ufdbguard/ufdbguardd.log", "see /var/log/squid/ufdbguardd.log\n");
		shell_exec("chmod 777 /var/log/ufdbguard/ufdbguardd.log");
	}
	
	
	if(is_file("/usr/sbin/ufdbguardd")){
		if(!is_file("/usr/bin/ufdbguardd")){
			$unix=new unix();
			$ln=$unix->find_program("ln");
			shell_exec("$ln -s /usr/sbin/ufdbguardd /usr/bin/ufdbguardd");
		}
	}
	@mkdir("/etc/ufdbguard",0755,true);
	
	build_ufdbguard_config();
	buildDans();
	ufdbguard_schedule();

	
	if($users->APP_UFDBGUARD_INSTALLED){
		$chmod=$unix->find_program("chmod");
		shell_exec("$chmod 755 /etc >/dev/null 2>&1");
		shell_exec("$chmod 755 /etc/ufdbguard >/dev/null 2>&1");
		shell_exec("$chmod 755 /var/log/ufdbguard >/dev/null 2>&1");
		shell_exec("$chmod 755 /var/log/squid >/dev/null 2>&1");
		shell_exec("$chmod -R 755 /var/lib/squidguard >/dev/null 2>&1 &");	
		ufdbguard_admin_events("Asking to reload ufdbguard",__FUNCTION__,__FILE__,__LINE__,"ufdbguard-service");	
		build_ufdbguard_HUP();
		
	}
	
	
}

function buildDans(){
	if(!is_dir("/var/run/dansguardian")){@mkdir("/var/run/dansguardian",0755,true);}
	$dans=new compile_dansguardian();
	$dans->build();
	$sock=new sockets();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	
	if($EnableWebProxyStatsAppliance==1){
		echo "TO DEV -> Send to stats appliance !\n";
		return;
	}		
}

function remove_bad_files(){
	
	$unix=new unix();
	
	$dirs=$unix->dirdir("/var/lib/ftpunivtlse1fr");
	while (list ($directory, $b) = each ($dirs)){
		$dirname=basename($directory);
		if(is_link("$directory/$dirname")){
			echo "Starting......: ".date("H:i:s")." Webfiltering service removing $dirname/$dirname bad file\n";
			@unlink("$directory/$dirname");
		}
	}
	
	
	echo "Starting......: ".date("H:i:s")." Webfiltering service removing bad files done...\n";
}




function build(){
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	if($EnableRemoteStatisticsAppliance==1){return;}	
	send_email_events("Order to rebuild filters configuration",@implode("\nParams:",$argv),"proxy");
	$funtion=__FUNCTION__;
	if(!isset($GLOBALS["VERBOSE"])){$GLOBALS["VERBOSE"]=false;}
	if($GLOBALS["VERBOSE"]){echo "$funtion::".__LINE__." Loading libraries\n";}
	$users=new usersMenus();
	$sock=new sockets();
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$chown=$unix->find_program("chown");
	$chmod=$unix->find_program("chmod");
	$squidbin=$unix->find_program("squid3");
	$nohup=$unix->find_program("nohup");
	$unix->SystemCreateUser("squid","squid");
	@mkdir("/var/tmp",0775,true);
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	$UseRemoteUfdbguardService=$sock->GET_INFO('UseRemoteUfdbguardService');
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid");}
	$EnableUfdbGuard=intval($sock->EnableUfdbGuard());
	$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	if(!is_numeric($UseRemoteUfdbguardService)){$UseRemoteUfdbguardService=0;}
	
	if($GLOBALS["VERBOSE"]){echo "DEBUG::$funtion:: EnableWebProxyStatsAppliance=$EnableWebProxyStatsAppliance\n";}
	if($GLOBALS["VERBOSE"]){echo "DEBUG::$funtion:: EnableRemoteStatisticsAppliance=$EnableRemoteStatisticsAppliance\n";}
	if($GLOBALS["VERBOSE"]){echo "DEBUG::$funtion:: EnableUfdbGuard=$EnableUfdbGuard\n";}
	if($GLOBALS["VERBOSE"]){echo "DEBUG::$funtion:: SQUIDEnable=$SQUIDEnable\n";}
	if($GLOBALS["VERBOSE"]){echo "DEBUG::$funtion:: UseRemoteUfdbguardService=$UseRemoteUfdbguardService\n";}
	

	
	$GLOBALS["SQUIDBIN"]=$squidbin;	
	if($EnableWebProxyStatsAppliance==0){
		$installed=false;
		if($users->SQUIDGUARD_INSTALLED){$installed=true;echo "Starting......: ".date("H:i:s")." SquidGuard is installed\n";}
		if($users->APP_UFDBGUARD_INSTALLED){$installed=true;echo "Starting......: ".date("H:i:s")." Webfiltering service is installed\n";}
		if($users->DANSGUARDIAN_INSTALLED){$installed=true;echo "Starting......: ".date("H:i:s")." Dansguardian is installed\n";}
		if(!$installed){if($GLOBALS["VERBOSE"]){echo "No one installed...\n";
		shell_exec("$nohup ".LOCATE_PHP5_BIN2()." ".dirname(__FILE__)."/exec.usrmactranslation.php >/dev/null 2>&1 &");
		return false;}}
		
	}
	
	
	if($EnableUfdbGuard==0){if($GLOBALS["VERBOSE"]){echo "UfDbguard is disabled ( see EnableUfdbGuard ) in line: ". __LINE__."\n";}return;}	
	if($SQUIDEnable==0){if($GLOBALS["VERBOSE"]){echo "UfDbguard is disabled ( see SQUIDEnable ) in line: ". __LINE__."\n";}return;}
	if($UseRemoteUfdbguardService==1){if($GLOBALS["VERBOSE"]){echo "UfDbguard is disabled ( see UseRemoteUfdbguardService ) in line: ". __LINE__."\n";}return;}
	
	if($GLOBALS["VERBOSE"]){echo "FIX_1_CATEGORY_CHECKED()\n";}
	FIX_1_CATEGORY_CHECKED();
	
	if($EnableRemoteStatisticsAppliance==1){
		if($GLOBALS["VERBOSE"]){echo "Use the Web statistics appliance to get configuration file...\n";}
		shell_exec("$nohup ".LOCATE_PHP5_BIN2()." ".dirname(__FILE__)."/exec.usrmactranslation.php >/dev/null 2>&1 &");
		ufdbguard_remote();
		return;
	}		

	
	if($GLOBALS["VERBOSE"]){echo "$funtion::".__LINE__."Loading compile_dansguardian()\n";}
	$dans=new compile_dansguardian();
	if($GLOBALS["VERBOSE"]){echo "$funtion::".__LINE__."Loading compile_dansguardian::->build()\n";}
	$dans->build();
	echo "Starting......: ".date("H:i:s")." Dansguardian compile done...\n";	
	if(function_exists('WriteToSyslogMail')){WriteToSyslogMail("build() -> reconfigure UfdbGuardd", basename(__FILE__));}
	build_ufdbguard_config();
	ufdbguard_schedule();
	
	
	if($EnableWebProxyStatsAppliance==1){
		echo "Starting......: ".date("H:i:s")." This server is a Squid Appliance, compress databases and notify proxies\n";
		CompressCategories();	
		notify_remote_proxys();
	}
	
	shell_exec("$php5 /usr/share/artica-postfix/exec.initslapd.php --ufdbguard");
	CheckPermissions();
	ufdbguard_admin_events("Service will be rebuiled and restarted",__FUNCTION__,__FILE__,__LINE__,"config");
	shell_exec("$nohup ".LOCATE_PHP5_BIN2()." ".dirname(__FILE__)."/exec.usrmactranslation.php >/dev/null 2>&1 &");
	
	if(!$GLOBALS["RESTART"]){
		if(is_file("/etc/init.d/ufdb")){
			echo "Starting......: ".date("H:i:s")." Checking watchdog\n";
			ufdbguard_watchdog();
			echo "Starting......: ".date("H:i:s")." Webfiltering service reloading service\n";
			build_ufdbguard_HUP();
		}
	}
	
	if($GLOBALS["RESTART"]){
		if(is_file("/etc/init.d/ufdb")){
			echo "Starting......: ".date("H:i:s")." Restarting\n";
			shell_exec("/etc/init.d/ufdb restart");
		}
	}
	
	if($users->DANSGUARDIAN_INSTALLED){
		echo "Starting......: ".date("H:i:s")." Dansguardian reloading service\n";
		shell_exec("/usr/share/artica-postfix/bin/artica-install --reload-dansguardian --withoutconfig");
	}
	

	
}
	

	
function FileMD5($path){
if(strlen(trim($GLOBALS["md5sum"]))==0){
		$unix=new unix();
		$md5sum=$unix->find_program("md5sum");
		$GLOBALS["md5sum"]=$md5sum;
}

if(strlen(trim($GLOBALS["md5sum"]))==0){return md5(@file_get_contents($path));}


exec("{$GLOBALS["md5sum"]} $path 2>&1",$res);
$data=trim(@implode(" ",$res));
if(preg_match("#^(.+?)\s+.+?#",$data,$re)){return trim($re[1]);}
	
}

function ufdbguard_watchdog_remove(){
}
function ufdbguard_watchdog(){
}

function dump_adrules($ruleid){
	
	$ufbd=new compile_ufdbguard();
	$ufbd->build_membersrule($ruleid);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/ufdb-dump-$ruleid.wt",0);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/ufdb-dump-$ruleid.txt","\n");
	@chmod("/usr/share/artica-postfix/ressources/logs/web/ufdb-dump-$ruleid.wt",0777);
	@chmod("/usr/share/artica-postfix/ressources/logs/web/ufdb-dump-$ruleid.txt",0777);
	if($GLOBALS["VERBOSE"]){echo "/usr/share/artica-postfix/external_acl_squid_ldap.php --db $ruleid\n";}
	exec("/usr/share/artica-postfix/external_acl_squid_ldap.php --db $ruleid --output 2>&1", $results);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/ufdb-dump-$ruleid.wt",1);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/ufdb-dump-$ruleid.txt",@implode("\n", $results));
	
}


function CheckPermissions(){
	$unix=new unix();
	$mv=$unix->find_program("mv");
	$chown=$unix->find_program("chown");
	$chmod=$unix->find_program("chmod");	
	$ln=$unix->find_program("ln");
	@mkdir("/var/lib/squidguard",644,true);	
	@mkdir("/etc/ufdbguard",644,true);

	$user=GetSquidUser();
	if(!is_file("/squid/log/squid/squidGuard.log")){
		@mkdir("/squid/log/squid",0755,true);
		@file_put_contents("/squid/log/squid/squidGuard.log","#");
		shell_exec("$chown $user /squid/log/squid/squidGuard.log");
	}
	
	if(!is_dir("/var/run/dansguardian")){@mkdir("/var/run/dansguardian",0755,true);}
	if(!is_dir("/etc/dansguardian")){@mkdir("/etc/dansguardian",0755,true);}
	shell_exec("$chown squid:squid /var/run/dansguardian");
	if(is_file("/usr/sbin/ufdbguardd")){if(!is_file("/usr/bin/ufdbguardd")){$unix=new unix();$ln=$unix->find_program("ln");shell_exec("$ln -s /usr/sbin/ufdbguardd /usr/bin/ufdbguardd");}}
	if(!is_dir("/var/lib/ftpunivtlse1fr")){@mkdir("/var/lib/ftpunivtlse1fr",0755,true);}
	if(!is_dir("/var/lib/squidguard/checked")){@mkdir("/var/lib/squidguard/checked",0755,true);@chown("/var/lib/squidguard/checked","squid");}
	
	if(!is_file("/squid/log/squid/squidGuard.log")){
		@mkdir("/squid/log/squid",0755,true);
		@file_put_contents("/squid/log/squid/squidGuard.log","#");
		shell_exec("$chown $user /squid/log/squid/squidGuard.log");
	}

	
	if(!is_file("/var/log/ufdbguard/ufdbguardd.log")){
		@mkdir("/var/log/ufdbguard",0755,true);
		@file_put_contents("/var/log/ufdbguard/ufdbguardd.log", "see /var/log/squid/ufdbguardd.log\n");
	}
	if(is_file("/usr/sbin/ufdbguardd")){if(!is_file("/usr/bin/ufdbguardd")){shell_exec("$ln -s /usr/sbin/ufdbguardd /usr/bin/ufdbguardd");}}
	@mkdir("/etc/ufdbguard",0755,true);
	@mkdir("/var/lib/ufdbartica",0755,true);
	shell_exec("$chown $user /var/lib/squidguard");
	shell_exec("$chown $user /var/lib/ftpunivtlse1fr");
	shell_exec("$chown -R $user /var/lib/squidguard");
	shell_exec("$chown -R $user /etc/dansguardian");
	shell_exec("$chown -R $user /var/log/squid");
	shell_exec("$chown -R $user /var/log/squid/");
	shell_exec("$chown -R $user /etc/ufdbguard");
	shell_exec("$chown -R $user /etc/ufdbguard");
	shell_exec("$chown -R $user /var/lib/ftpunivtlse1fr");
	shell_exec("$chmod -R ug+x /var/lib/squidguard/");
	shell_exec("$chown -R $user /var/lib/squidguard");
	shell_exec("$chown -R $user /var/lib/ufdbartica");
	shell_exec("$chown -R $user /var/lib/ufdbartica");		
	shell_exec("$chown -R $user /var/log/squid");
	shell_exec("$chmod -R 755 /var/lib/squidguard");
	shell_exec("$chmod -R 755 /var/lib/ufdbartica");	
	shell_exec("$chmod -R ug+x /var/lib/squidguard");
	@chown("/var/lib/squidguard/checked","squid");
	if(!is_file("/var/log/ufdbguard/ufdbguardd.log")){@mkdir("/var/log/ufdbguard",0755,true);@file_put_contents("/var/log/ufdbguard/ufdbguardd.log", "see /var/log/squid/ufdbguardd.log\n");}
	shell_exec("chmod 755 /var/log/ufdbguard/ufdbguardd.log");	
	@link(dirname(__FILE__)."/ressources/logs/squid-template.log", "/var/log/squid/squid-template.log");
	
}

function UFDBGUARD_COMPILE_SINGLE_DB($path){
	$timeStart=time();
	$OriginalDirename=dirname($path);
	$unix=new unix();
	$path=str_replace(".ufdb","",$path);
	$pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.md5($path).".pid";
	$pid=@file_get_contents($pidpath);
	if($unix->process_exists($pid)){
		events_ufdb_tail("Check \"$path\"... Already process PID \"$pid\" running task has been aborted");
		return;
	}
	
	
	
	$category=null;
	$ufdbGenTable=$unix->find_program("ufdbGenTable");
	if(!is_file($ufdbGenTable)){writelogs("ufdbGenTable no such binary",__FUNCTION__,__FILE__,__LINE__);return;}
	
	events_ufdb_tail("Check \"$path\"...",__LINE__);
	if(preg_match("#\/var\/lib\/squidguard\/(.+?)\/(.+?)/(.+?)$#",$path,$re)){
		$category=$re[2];
		$domain_path="/var/lib/squidguard/{$re[1]}/{$re[2]}/domains";		
	}
	if($category==null){
		if(preg_match("#\/var\/lib\/squidguard\/(.+?)\/domains#",$path,$re)){
			$category=$re[1];
			$domain_path="/var/lib/squidguard/{$re[1]}/domains";		
		}	
	}
	
	if(preg_match("#web-filter-plus\/BL\/(.+?)\/domains#",$path,$re)){
		$category=$re[1];
		$domain_path="/var/lib/squidguard/web-filter-plus/BL/$category/domains";	
	}
	
	if(preg_match("#blacklist-artica\/(.+?)\/(.+?)\/domains#",$path,$re)){
		events_ufdb_tail("find double category \"{$re[1]}-{$re[2]}\"...",__LINE__);
		$category="{$re[1]}-{$re[2]}";
		$domain_path="/var/lib/squidguard/blacklist-artica/{$re[1]}/{$re[2]}/domains";	
	}	

	if(preg_match("#blacklist-artica\/sex\/(.+?)\/domains#",$path,$re)){
		$category=$re[1];
		$domain_path="/var/lib/squidguard/blacklist-artica/sex/$category/domains";	
	}
	
	if($category==null){
		events_ufdb_tail("exec.squidguard.php:: \"$path\" cannot understand...");
	}
	
	events_ufdb_tail("exec.squidguard.php:: Found category \"$category\"",__LINE__);

	if(!is_file($path)){
		events_ufdb_tail("exec.squidguard.php:$category: \"$path\" no such file, build it",__LINE__);
		@file_put_contents($domain_path," ");
	}
	
	$category_compile=substr($category,0,15);
	if(strlen($category_compile)>15){
			$category_compile=str_replace("recreation_","recre_",$category_compile);
			$category_compile=str_replace("automobile_","auto_",$category_compile);
			$category_compile=str_replace("finance_","fin_",$category_compile);
			if(strlen($category_compile)>15){
				$category_compile=str_replace("_", "", $category_compile);
				if(strlen($category_compile)>15){
					$category_compile=substr($category_compile, strlen($category_compile)-15,15);
				}
			}
		}	
	
	events_ufdb_tail("exec.squidguard.php:: category \"$category\" retranslated to \"$category_compile\"",__LINE__);
	
	
	if(is_file("$domain_path.ufdb")){
		events_ufdb_tail("exec.squidguard.php:: removing \"$domain_path.ufdb\" ...");
		@unlink("$domain_path.ufdb");
	
	}
	if(!is_file($domain_path)){
		events_ufdb_tail("exec.squidguard.php:: $domain_path no such file, create an empty one",__LINE__);
		@mkdir(dirname($domain_path),0755,true);
		@file_put_contents($domain_path,"#");
	}
	
	$urlcmd=null;
	$d=" -d $domain_path";
	if(is_file("$OriginalDirename/urls")){
		$urlssize=@filesize("$OriginalDirename/urls");
		events_ufdb_tail("exec.squidguard.php:: $OriginalDirename/urls $urlssize bytes...",__LINE__);
		if($urlssize>50){
			$urlcmd=" -u $OriginalDirename/urls";
		}
	}

	$NICE=EXEC_NICE();
	$cmd="$NICE$ufdbGenTable -n -D -W -t $category_compile$d$urlcmd 2>&1";
	events_ufdb_tail("exec.squidguard.php:$category:$cmd");
	$time=time();
	exec($cmd,$results);
	exec($cmd,$results);
	while (list ($a, $b) = each ($results)){
		if(strpos($b,"is not added because it was already matched")){continue;}
		if(strpos($b,"has optimised subdomains")){continue;}
		events_ufdb_tail("exec.squidguard.php:$category:$b");
	}
	$tookrecompile=$unix->distanceOfTimeInWords($time,time());
	events_ufdb_tail("exec.squidguard.php:$category_compile: execution $tookrecompile",__LINE__);
	
	events_ufdb_tail("exec.squidguard.php:$category:done..");
	
	$user=GetSquidUser();
	$chown=$unix->find_program("chown");
	if(is_file($chown)){
		events_ufdb_tail("exec.squidguard.php:$category:$chown -R $user $OriginalDirename");
		shell_exec("$chown -R $user $OriginalDirename/*");
		shell_exec("$chown -R $user /var/log/squid/*");
	}
	$sock=new sockets();
	$took=$unix->distanceOfTimeInWords($timeStart,time());
	$sock->TOP_NOTIFY("$OriginalDirename webfiltering database ($category) was recompiled took $took hard compilation took: $tookrecompile","info");
	
}
	

function databasesStatus(){
	$datas=explode("\n",@file_get_contents("/etc/squid/squidGuard.conf"));
	$count=0;
	$f=array();
	while (list ($a, $b) = each ($datas)){
		
		if(preg_match("#domainlist.+?(.+)#",$b,$re)){
			$f[]["domainlist"]["path"]="/var/lib/squidguard/{$re[1]}";
			
			continue;
			
		}
		
		if(preg_match("#expressionlist.+?(.+)#",$b,$re)){
			$f[]["expressionlist"]["path"]="/var/lib/squidguard/{$re[1]}";
			
			continue;
		}
		
		if(preg_match("#urllist.+?(.+)#",$b,$re)){
			$f[]["urllist"]["path"]="/var/lib/squidguard/{$re[1]}";
			
			continue;
		}
		
		
	}
	

	
	while (list ($a, $b) = each ($f)){

		$domainlist=$b["domainlist"]["path"];
		$expressionlist=$b["expressionlist"]["path"];
		$urllist=$b["urllist"]["path"];
		
		if(is_file($domainlist)){
			$key="domainlist";
			$path=$domainlist;
		}
		
		if(is_file($expressionlist)){
			$key="expressionlist";
			$path=$expressionlist;
		}

		if(is_file($urllist)){
			$key="urllist";
			$path=$urllist;
		}			
		
		$d=explode("\n",@file_get_contents($path));
		$i[$path]["type"]=$key;
		$i[$path]["size"]=@filesize("$domainlist.db");
		$i[$path]["linesn"]=count($d);
		$i[$path]["date"]=filemtime($path);
		
		
		
		
	}
	
	return $i;
	
}

function status(){
	
	
	$squid=new squidbee();
	$array=$squid->SquidGuardDatabasesStatus();
	$conf[]="[APP_SQUIDGUARD]";
	$conf[]="service_name=APP_SQUIDGUARD";
	
	
	if(is_array($array)){
		$conf[]="running=0";
		$conf[]="why={waiting_database_compilation}<br>{databases}:&nbsp;".count($array);
		return implode("\n",$conf);
		
	}
	
	
	$unix=new unix();
	$users=new usersMenus();
	$pidof=$unix->find_program("pidof");
	exec("$pidof $users->SQUIDGUARD_BIN_PATH",$res);
	$array=explode(" ",implode(" ",$res));
	while (list ($index, $line) = each ($array)){
		if(preg_match("#([0-9]+)#",$line,$ri)){
			$pid=$ri[1];
			$inistance=$inistance+1;
			$mem=$mem+$unix->MEMORY_OF($pid);
			$ppid=$unix->PPID_OF($pid);
		}
	}
	$conf[]="running=1";
	$conf[]="master_memory=$mem";
	$conf[]="master_pid=$ppid";
	$conf[]="other={processes}:$inistance"; 
	return implode("\n",$conf);
	
}

function CompileSingleDB($db_path){
	$user=GetSquidUser();
	$users=new usersMenus();
	$unix=new unix();
	if(strpos($db_path,".db")>0){$db_path=str_replace(".db","",$db_path);}
	$verb=" -d";
	$chown=$unix->find_program("chown");
	$chmod=$unix->find_program("chmod");
	exec($users->SQUIDGUARD_BIN_PATH." $verb -C $db_path",$repair);	
	shell_exec("$chown -R $user /var/lib/squidguard/*");
	shell_exec("$chmod -R 755 /var/lib/squidguard/*");	
	shell_exec("$chmod -R ug+x /var/lib/squidguard/*");	
	
	$db_recover=$unix->LOCATE_DB_RECOVER();
	shell_exec("$db_recover -h ".dirname($db_path));
	build();
	KillSquidGuardInstances();	
	send_email_events("squidGuard: $db_path repair","the database $db_path was repair by artica\n",@implode("\n",$repair),"squid");
	
}

function KillSquidGuardInstances(){
	$unix=new unix();
	$users=new usersMenus();
	$pidof=$unix->find_program("pidof");
	if(strlen($pidof)>3){
		exec("$pidof $users->SQUIDGUARD_BIN_PATH 2>&1",$results);
		$pids=trim(@implode(" ",$results));
		if(strlen($pids)>3){
			echo "Starting......: ".date("H:i:s")." squidGuard kill $pids PIDs\n";
			shell_exec("/bin/kill $pids");
		}
		
	}	
	
}


function compile_databases(){
	$users=new usersMenus();
	$squid=new squidbee();
	$array=$squid->SquidGuardDatabasesStatus();
	$verb=" -d";
	
	
		$array=$squid->SquidGuardDatabasesStatus(0);

	
	if( count($array)>0){
		while (list ($index, $file) = each ($array)){
			echo "Starting......: ".date("H:i:s")." squidGuard compiling ". count($array)." databases\n";
			$file=str_replace(".db",'',$file);
			$textfile=str_replace("/var/lib/squidguard/","",$file);
			echo "Starting......: ".date("H:i:s")." squidGuard compiling $textfile database ".($index+1) ."/". count($array)."\n";
			if($GLOBALS["VERBOSE"]){$verb=" -d";echo $users->SQUIDGUARD_BIN_PATH." $verb -C $file\n";}
			system($users->SQUIDGUARD_BIN_PATH." -P$verb -C $file");
		}
	}else{
		echo "Starting......: ".date("H:i:s")." squidGuard compiling all databases\n";
		if($GLOBALS["VERBOSE"]){$verb=" -d";echo $users->SQUIDGUARD_BIN_PATH." $verb -C all\n";}
		system($users->SQUIDGUARD_BIN_PATH." -P$verb -C all");
	}

	
		
	$user=GetSquidUser();
	$unix=new unix();
	$chown=$unix->find_program("chown");
	$chmod=$unix->find_program("chmod");
	shell_exec("$chown -R $user /var/lib/squidguard/*");
	shell_exec("$chmod -R 755 /var/lib/squidguard/*");		
 	system(LOCATE_PHP5_BIN2()." ".dirname(__FILE__)."/exec.squid.php --build");
	build();
	KillSquidGuardInstances();
	
	
	
 
 
}

function CacheManager_default(){
	$sock=new sockets();
	$LicenseInfos=unserialize(base64_decode($sock->GET_INFO("LicenseInfos")));
	$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
		
	if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}
	if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]="contact@articatech.com";}
	$LicenseInfos["EMAIL"]=str_replace("'", "", $LicenseInfos["EMAIL"]);
	$LicenseInfos["EMAIL"]=str_replace('"', "", $LicenseInfos["EMAIL"]);
	$LicenseInfos["EMAIL"]=str_replace(' ', "", $LicenseInfos["EMAIL"]);
	return $LicenseInfos["EMAIL"];
}

function CacheManager(){
	$sock=new sockets();
	$cache_mgr_user=$sock->GET_INFO("cache_mgr_user");
	if($cache_mgr_user<>null){return $cache_mgr_user;}
	return CacheManager_default();
}









function GetSquidUser(){
	$unix=new unix();
	$squidconf=$unix->SQUID_CONFIG_PATH();
	$group=null;
	if(!is_file($squidconf)){
		echo "Starting......: ".date("H:i:s")." squidGuard unable to get squid configuration file\n";
		return "squid:squid";
	}
	
	$array=explode("\n",@file_get_contents($squidconf));
	while (list ($index, $line) = each ($array)){
		if(preg_match("#cache_effective_user\s+(.+)#",$line,$re)){
			$user=trim($re[1]);
		}
		if(preg_match("#cache_effective_group\s+(.+)#",$line,$re)){
			$group=trim($re[1]);
		}
	}
	
	
	if($group==null){$group="squid";}	
	return "$user:$group";
	
	
	
}

function ParseDirectory($path){
	if(!is_dir($path)){echo "$path No such directory\n";return;}
	$sock=new sockets();
	$unix=new unix();
	$uuid=$unix->GetUniqueID();
	if($uuid==null){echo "No uuid\n";return;}	
	$handle=opendir($path);
	$q=new mysql_squid_builder();
	$f=false;
	while (false !== ($dir = readdir($handle))) {
		if($dir=="."){continue;}
		if($dir==".."){continue;}	
		if(!is_file("$path/$dir/domains")){echo "$path/$dir/domains no such file\n";continue;}
		$category=sourceCategoryToArticaCategory($dir);
		if($category==null){echo "$path/$dir/domains no such category\n";continue;}
		$table="category_".$q->category_transform_name($category);
		if(!$q->TABLE_EXISTS($table)){echo "$category -> no such table $table\n";continue;}
		inject($category,$table,"$path/$dir/domains");
		
		
	}
	
	
	$tables=$q->LIST_TABLES_CATEGORIES();
	while (list ($table, $www) = each ($tables)){
		$sql="SELECT COUNT(zmd5) as tcount FROM $table WHERE sended=0 and enabled=1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$prefix="INSERT IGNORE INTO categorize (zmd5 ,pattern,zDate,uuid,category) VALUES";
		if($ligne["tcount"]>0){
			echo "$table {$ligne["tcount"]} items to export\n";
			$results=$q->QUERY_SQL("SELECT * FROM $table WHERE sended=0 and enabled=1");
			while($ligne2=mysql_fetch_array($results,MYSQL_ASSOC)){
				$f[]="('{$ligne2["zmd5"]}','{$ligne2["pattern"]}','{$ligne2["zDate"]}','$uuid','{$ligne2["category"]}')";
				$c++;
				if(count($f)>3000){
					$q->QUERY_SQL($prefix.@implode(",",$f));
					if(!$q->ok){echo $q->mysql_error."\n";return;}
					$f=array();
				}
				
			}
		$q->QUERY_SQL("UPDATE $table SET sended=1 WHERE sended=0");
		}
		
	}
	
if(count($f)>0){
	$q->QUERY_SQL($prefix.@implode(",",$f));
	$f=array();	
}	
	
	
	
}

function sourceCategoryToArticaCategory($category){
	$array["gambling"]="gamble";
	$array["gamble"]="gamble";
	$array["hacking"]="hacking";
	$array["malware"]="malware";
	$array["phishing"]="phishing";
	$array["porn"]="porn";
	$array["sect"]="sect";
	$array["socialnetwork"]="socialnet";
	$array["violence"]="violence";
	$array["adult"]="porn";
	$array["ads"]="publicite";
	$array["warez"]="warez";
	$array["drugs"]="drogue";
	$array["forums"]="forums";
	$array["filehosting"]="filehosting";
	$array["games"]="games";
	$array["astrology"]="astrology";
	$array["publicite"]="publicite";
	$array["radio"]="webradio";
	$array["sports"]="recreation/sports";
	$array["getmarried"]="getmarried";
	$array["police"]="police";
	$array["press"]="news";
	$array["youtube"]="youtube";
	$array["audio-video"]="audio-video";
	$array["webmail"]="webmail";
	$array["chat"]="chat";
	$array["social_networks"]="socialnet";
	$array["ads"]="publicite";
	$array["adult"]="porn";
	$array["aggressive"]="aggressive";
	$array["astrology"]="astrology";
	
	$array["bank"]="finance/banking";
	$array["blog"]="blog";
	$array["celebrity"]="celebrity";
	$array["chat"]="chat";
	$array["cleaning"]="cleaning";
	$array["dangerous_material"]="dangerous_material";
	$array["dating"]="dating";
	$array["drugs"]="porn";
	$array["filehosting"]="filehosting";
	$array["financial"]="financial";
	$array["forums"]="forums";
	$array["gambling"]="gamble";
	$array["games"]="games";
	$array["hacking"]="hacking";
	$array["jobsearch"]="jobsearch";
	$array["liste_bu"]="liste_bu";
	$array["malware"]="malware";
	$array["marketingware"]="marketingware";
	$array["mixed_adult"]="mixed_adult";
	$array["mobile-phone"]="mobile-phone";
	$array["phishing"]="phishing";
	
	$array["radio"]="webradio";
	$array["reaffected"]="reaffected";
	$array["redirector"]="redirector";
	$array["remote-control"]="remote-control";
	$array["sect"]="sect";
	$array["sexual_education"]="sexual_education";
	$array["shopping"]="shopping";
	$array["social_networks"]="socialnet";
	$array["sports"]="recreation/sports";
	$array["getmarried"]="getmarried";
	$array["police"]="police";	

	$array["tricheur"]="tricheur";
	$array["violence"]="violence";
	$array["warez"]="warez";
	$array["webmail"]="webmail";
	$array["ads"]="publicite";
	$array["adult"]="porn";
	$array["aggressive"]="aggressive";
	$array["astrology"]="astrology";
	
	$array["bank"]="finance/banking";
	$array["blog"]="blog";
	$array["celebrity"]="celebrity";
	$array["chat"]="chat";
	$array["cleaning"]="cleaning";
	$array["dangerous_material"]="dangerous_material";
	$array["dating"]="dating";
	$array["drugs"]="porn";
	$array["filehosting"]="filehosting";
	$array["financial"]="financial";
	$array["forums"]="forums";
	$array["gambling"]="gamble";
	$array["games"]="games";
	$array["hacking"]="hacking";
	$array["jobsearch"]="jobsearch";
	$array["liste_bu"]="liste_bu";
	$array["malware"]="malware";
	$array["marketingware"]="marketingware";
	$array["mixed_adult"]="mixed_adult";
	$array["mobile-phone"]="mobile-phone";
	$array["phishing"]="phishing";
	
	$array["radio"]="webradio";
	$array["reaffected"]="reaffected";
	$array["redirector"]="redirector";
	$array["remote-control"]="remote-control";
	$array["sect"]="sect";
	$array["sexual_education"]="sexual_education";
	$array["shopping"]="shopping";
	$array["social_networks"]="socialnet";
	$array["sports"]="recreation/sports";
	$array["getmarried"]="getmarried";
	$array["police"]="police";	

	$array["tricheur"]="tricheur";
	$array["violence"]="violence";
	$array["warez"]="warez";
	$array["webmail"]="webmail";	
	$array["adv"]="publicite";
	$array["aggressive"]="aggressive";
	$array["automobile"]="automobile/cars";
	$array["chat"]="chat";
	$array["dating"]="dating";
	$array["downloads"]="downloads";
	$array["drugs"]="drugs";
	$array["education"]="recreation/schools";
	$array["finance"]="financial";
	$array["forum"]="forums";
	$array["gamble"]="gamble";
	$array["government"]="governments";
	$array["hacking"]="hacking";
	$array["hospitals"]="hospitals";
	$array["imagehosting"]="imagehosting";
	$array["isp"]="isp";
	$array["jobsearch"]="jobsearch";
	$array["library"]="books";
	$array["models"]="models";
	$array["movies"]="movies";
	$array["music"]="music";
	$array["news"]="news";
	$array["porn"]="porn";
	$array["redirector"]="redirector";
	$array["religion"]="religion";
	$array["remotecontrol"]="remote-control";
	
	$array["searchengines"]="searchengines";
	$array["shopping"]="shopping";
	$array["socialnet"]="socialnet";
	$array["spyware"]="spyware";
	$array["tracker"]="tracker";
	$array["updatesites"]="updatesites";
	$array["violence"]="violence";
	$array["warez"]="warez";
	$array["weapons"]="weapons";
	$array["webmail"]="webmail";
	$array["webphone"]="webphone";
	$array["webradio"]="webradio";
	$array["webtv"]="webtv";		
	if(!isset($array[$category])){return null;}
	return $array[$category];
	
	
}
// exec.squidguard.php --inject porn /root/blablabl/domains
function inject($category,$table=null,$file=null){
	include_once(dirname(__FILE__)."/ressources/class.dansguardian.inc");
	$unix=new unix();
	$q=new mysql_squid_builder();
	
	
	
	if(is_file($category)){
		$file=$category;
		$category_name=basename($file);
		echo "$file -> $category_name\n";
		if(preg_match("#(.+?)\.gz$#", $category_name)){
			echo "$category_name -> gunzip\n";
			$new_category_name=str_replace(".gz", "", $category_name);
			$gunzip=$unix->find_program("gunzip");
			$target_file=dirname($file)."/$new_category_name";
			$cmd="/bin/gunzip -d -c \"$file\" >$target_file 2>&1";
			echo "$cmd\n";
			shell_exec($cmd);
			if(!is_file($target_file)){echo "Uncompress failed\n";return;}
			$file=$target_file;
			$table=$new_category_name;
			$category=$q->tablename_tocat($table);
			echo "$new_category_name -> $table\n";
			
			
			
		}else{
			$table=$category_name;
			echo "$new_category_name -> $table\n";
			$category=$q->tablename_tocat($table);
		}
		
		echo "Table: $table\nSource File:$file\nCategory: $category\n";
		
		
	}
	
	
	if(!is_file($file)){
		if(!is_file($table)){echo "`$table` No such file\n";}
		if(is_file($table)){$file=$table;$table=null;}
	}
	
	
	if($table==null){
		$table="category_".$q->category_transform_name($category);
		echo "Table will be $table\n";
	}
	
	if(!$q->TABLE_EXISTS($table)){
		echo "$table does not exists, check if it is an official one\n";
		$dans=new dansguardian_rules();
		if(isset($dans->array_blacksites[$category])){
			$q->CreateCategoryTable($category);
		}
		
	}
	if(!$q->TABLE_EXISTS($table)){	
		echo "`$category` -> no such table \"$table\"\n";return;
	}
	
	
	$sql="SELECT COUNT(*) AS TCOUNT FROM $table";
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo $q->mysql_error."\n";
		if(preg_match("#is marked as crashed and last#", $q->mysql_error)){
			echo "`$table` -> crashed, remove \"$table\"\n";
			$q->QUERY_SQL("DROP TABLE $table");
			$q->QUERY_SQL("flush tables");
			$q=new mysql_squid_builder();
			echo "`$table` -> Create category \"$category\"\n";
			$q->CreateCategoryTable($category);
			$q->CreateCategoryTable($category);
			$q=new mysql_squid_builder();
		}
		
		if(!$q->TABLE_EXISTS($table)){
			echo "`$category` -> no such table \"$table\"\n";
			return;
		}		
	}
		
		
	if($file==null){
		$dir="/var/lib/squidguard";
		if($GLOBALS["SHALLA"]){$dir="/root/shalla/BL";}
		if(!is_file("$dir/$category/domains")){
			echo "$dir/$category/domains no such file";
			return;
			
		}
		$file="$dir/$category/domains";
	}
		
	if(!is_file($file)){echo "$file no such file";return;}
		
	$sock=new sockets();
	$unix=new unix();
	$uuid=$unix->GetUniqueID();
	if($uuid==null){echo "No uuid\n";return;}
	echo "open $file\n";
	
	
	$handle = @fopen($file, "r"); 
	if (!$handle) {echo "Failed to open file\n";return;}
	$q=new mysql_squid_builder();
	if($GLOBALS["CATTO"]<>null){$category=$GLOBALS["CATTO"];}
	$countstart=$q->COUNT_ROWS($table);
	$prefix="INSERT IGNORE INTO $table (zmd5,zDate,category,pattern,uuid) VALUES ";
	echo "$prefix\n";
	
	$catz=new mysql_catz();
	$c=0;
	$CBAD=0;
	$CBADIP=0;
	$CBADNULL=0;
	while (!feof($handle)){
		$c++;
		$www =trim(fgets($handle, 4096));
		if($www==null){$CBADNULL++;continue;}
		$www=str_replace('"', "", $www);
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $www)){$CBADIP++;continue;}
		$www=trim(strtolower($www));
		if($www=="thisisarandomentrythatdoesnotexist.com"){$CBAD++;continue;}
		
		if($www==null){$CBADNULL++;continue;}
		if(preg_match("#(.+?)\s+(.+)#", $www,$re)){$www=$re[1];}
		if(preg_match("#^\.(.*)$#", $www,$re)){$www=$re[1];}
		
		if(strpos($www, "#")>0){echo "FALSE: $www\n";continue;}
		if(strpos($www, "'")>0){echo "FALSE: $www\n";continue;}
		if(strpos($www, "{")>0){echo "FALSE: $www\n";continue;}
		if(strpos($www, "(")>0){echo "FALSE: $www\n";continue;}
		if(strpos($www, ")")>0){echo "FALSE: $www\n";continue;}
		if(strpos($www, "%")>0){echo "FALSE: $www\n";continue;}
		
		$category2=$catz->GET_CATEGORIES($www);
		if($category2<>null){
			if($category2==$category){continue;}
			$md5=md5($category.$www);
			
			if($category=="porn"){
				
				
				if($category2=="shopping"){
					echo date("H:i:s"). " Remove $www from shopping and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_shopping WHERE `pattern`='$www'");
					$q->categorize($www, $category,true);
					continue;
				}
				
				if($category2=="hobby/arts"){
					echo date("H:i:s"). " Remove $www from hobby/arts and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_hobby_arts WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}
				
				if($category2=="society"){
					echo date("H:i:s"). " Remove $www from society and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_society WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}
				


				if($category2=="finance/realestate"){
					echo date("H:i:s"). " Remove $www from finance/realestate and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_finance_realestate WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
					
				}
				
				if($category2=="science/computing"){
					echo date("H:i:s"). " Remove $www from science/computing and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_science_computing WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
					
				}
				
				if($category2=="industry"){
					echo date("H:i:s"). " Remove $www from industry and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_industry WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
								
				}	

				if($category2=="proxy"){
					echo date("H:i:s"). " Remove $www from proxy and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_proxy WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}
				if($category2=="searchengines"){
					echo date("H:i:s"). " Remove $www from searchengines and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_searchengines WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}		

				if($category2=="blog"){
					echo date("H:i:s"). " Remove $www from blog and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_blog WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}				
				if($category2=="forums"){
					echo date("H:i:s"). " Remove $www from blog and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_blog WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}		

				if($category2=="recreation/sports"){
					echo date("H:i:s"). " Remove $www from recreation/sports and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_recreation_sports WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}	

				if($category2=="hacking"){
					echo date("H:i:s"). " Remove $www from hacking and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_hacking WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}	
				if($category2=="malware"){
					echo date("H:i:s"). " Remove $www from malware and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_malware WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}
				if($category2=="drugs"){
					echo date("H:i:s"). " Remove $www from drugs and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_drugs WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}
				if($category2=="health"){
					echo date("H:i:s"). " Remove $www from health and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_health WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}				
				if($category2=="news"){
					echo date("H:i:s"). " Remove $www from news and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_news WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}	
				if($category2=="audio-video"){
					echo date("H:i:s"). " Remove $www from audio-video and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_audio_video WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}	

				if($category2=="recreation/schools"){
					echo date("H:i:s"). " Remove $www from recreation/schools and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_recreation_schools WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}	
				if($category2=="reaffected"){
					echo date("H:i:s"). " Remove $www from reaffected and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_reaffected WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}						
				if($category2=="warez"){
					echo date("H:i:s"). " Remove $www from warez and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_warez WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}				
				if($category2=="suspicious"){
					echo date("H:i:s"). " Remove $www from suspicious and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_suspicious WHERE `pattern`='$www'");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					$q->QUERY_SQL("INSERT IGNORE INTO category_porn (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}				
				
				
			}
			
			
			if($category=="gamble"){
				if($category2=="shopping"){
					echo date("H:i:s"). " Remove $www from shopping and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_shopping WHERE `pattern`='$www'");
					$q->categorize($www, $category,true);
					continue;
				}
			}
			if($category=="proxy"){
				if($category2=="society"){
					echo date("H:i:s"). " Remove $www from society and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_society WHERE `pattern`='$www'");
					$q->categorize($www, $category,true);
					continue;
				}
			
				if($category2=="porn"){
					echo date("H:i:s"). " Remove $www from porn and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_porn WHERE `pattern`='$www'");
					$q->categorize($www, $category,true);
					continue;
				}
				if($category2=="shopping"){
					echo date("H:i:s"). " Remove $www from shopping and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_shopping WHERE `pattern`='$www'");
					$q->categorize($www, $category,true);
					continue;
				}				
				
				if($category2=="science/computing"){
					echo date("H:i:s"). " Remove $www from science/computing and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_science_computing WHERE `pattern`='$www'");
					$q->categorize($www, $category,true);
					continue;
				}

				if($category2=="industry"){
					echo date("H:i:s"). " Remove $www from industry and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_industry WHERE `pattern`='$www'");
					$q->categorize($www, $category,true);
					continue;
				}				
				
				if($category2=="filehosting"){
					echo date("H:i:s"). " Remove $www from filehosting and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_filehosting WHERE `pattern`='$www'");
					$q->categorize($www, $category,true);
					continue;
				}	

				if($category2=="hacking"){
					echo date("H:i:s"). " Remove $www from hacking and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_hacking WHERE `pattern`='$www'");
					$q->categorize($www, $category,true);
					continue;
				}	
				if($category2=="governments"){
					echo date("H:i:s"). " Remove $www from governments and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_governments WHERE `pattern`='$www'");
					$q->categorize($www, $category,true);
					continue;
				}
			}

			if($category=="spyware"){
				if($category2=="society"){
					echo date("H:i:s"). " Remove $www from society and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_society WHERE `pattern`='$www'");
					$q->QUERY_SQL("INSERT IGNORE INTO category_spyware (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}
				if($category2=="industry"){
					echo date("H:i:s"). " Remove $www from industry and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_industry WHERE `pattern`='$www'");
					$q->QUERY_SQL("INSERT IGNORE INTO category_spyware (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}				
				if($category2=="recreation/sports"){
					echo date("H:i:s"). " Remove $www from recreation/sports and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_recreation_sports WHERE `pattern`='$www'");
					$q->QUERY_SQL("INSERT IGNORE INTO category_spyware (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}
				
				if($category2=="recreation/schools"){
					echo date("H:i:s"). " Remove $www from recreation/schools and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_recreation_schools WHERE `pattern`='$www'");
					$q->QUERY_SQL("INSERT IGNORE INTO category_spyware (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}
				
				if($category2=="searchengines"){
					echo date("H:i:s"). " Remove $www from searchengines and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_searchengines WHERE `pattern`='$www'");
					$q->QUERY_SQL("INSERT IGNORE INTO category_spyware (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}
				if($category2=="shopping"){
					echo date("H:i:s"). " Remove $www from shopping and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_shopping WHERE `pattern`='$www'");
					$q->QUERY_SQL("INSERT IGNORE INTO category_spyware (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}	
				if($category2=="audio-video"){
					echo date("H:i:s"). " Remove $www from audio-video and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_audio_video WHERE `pattern`='$www'");
					$q->QUERY_SQL("INSERT IGNORE INTO category_spyware (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}
				if($category2=="suspicious"){
					$q->QUERY_SQL("DELETE FROM category_suspicious WHERE `pattern`='$www'");
					$q->QUERY_SQL("INSERT IGNORE INTO category_spyware (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}
				if($category2=="health"){
					echo date("H:i:s"). " Remove $www from health and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_health WHERE `pattern`='$www'");
					$q->QUERY_SQL("INSERT IGNORE INTO category_spyware (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}	
				if($category2=="jobsearch"){
					echo date("H:i:s"). " Remove $www from jobsearch and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_jobsearch WHERE `pattern`='$www'");
					$q->QUERY_SQL("INSERT IGNORE INTO category_spyware (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}		

				if($category2=="hobby/arts"){
					$q->QUERY_SQL("DELETE FROM category_hobby_arts WHERE `pattern`='$www'");
					$q->QUERY_SQL("INSERT IGNORE INTO category_spyware (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}	

				if($category2=="science/computing"){
					echo date("H:i:s"). " Remove $www from science_computing and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_science_computing WHERE `pattern`='$www'");
					$q->QUERY_SQL("INSERT IGNORE INTO category_spyware (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}

				if($category2=="recreation/travel"){
					echo date("H:i:s"). " Remove $www from recreation_travel and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_recreation_travel WHERE `pattern`='$www'");
					$q->QUERY_SQL("INSERT IGNORE INTO category_spyware (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}
				if($category2=="dynamic"){
					echo date("H:i:s"). " Remove $www from dynamic and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_dynamic WHERE `pattern`='$www'");
					$q->QUERY_SQL("INSERT IGNORE INTO category_spyware (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}				
				
				if($category2=="finance/realestate"){
					echo date("H:i:s"). " Remove $www from finance_realestate and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_finance_realestate WHERE `pattern`='$www'");
					$q->QUERY_SQL("INSERT IGNORE INTO category_spyware (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}
				if($category2=="isp"){
					echo date("H:i:s"). " Remove $www from isp and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_isp WHERE `pattern`='$www'");
					$q->QUERY_SQL("INSERT IGNORE INTO category_spyware (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}if($category2=="housing/accessories"){
					echo date("H:i:s"). " Remove $www from housing/accessories and add it to $category\n";
					$q->QUERY_SQL("DELETE FROM category_housing_accessories WHERE `pattern`='$www'");
					$q->QUERY_SQL("INSERT IGNORE INTO category_spyware (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'porn','$www','$uuid',1)");
					if(!$q->ok){echo "$q->mysql_error\n";die();}
					continue;
				}		

				
				
				if($category2=="malware"){continue;}
				if($category2=="phishing"){continue;}
				
			}			
			
			echo date("H:i:s"). " $www $category2 SKIP\n";
			continue;
		}
		
		$md5=md5($www.$category);
		$n[]="('$md5',NOW(),'$category','$www','$uuid')";
		
		
		if(count($n)>6000){
			$sql=$prefix.@implode(",",$n);
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){echo $q->mysql_error."\n";$n=array();continue;}
			$countend=$q->COUNT_ROWS($table);
			$final=$countend-$countstart;
			echo "".numberFormat($c,0,""," ")." items, ".numberFormat($final,0,""," ")." new entries added - $CBADNULL bad entries for null value,$CBADIP entries for IP addresses\n";	
			$n=array();
			
		}
		
	}
	
	fclose($handle);
	
	if(count($f)>0){
			if($c>0){
				$countend=$q->COUNT_ROWS($table);
				$final=$countend-$countstart;
				echo "$c items, $final new entries added - $CBAD bad entries\n";		
				$sql=$prefix.@implode(",",$n);
				$q->QUERY_SQL($sql,"artica_backup");
				if(!$q->ok){echo $q->mysql_error."\n$sql";continue;}
				$n=array();
			}
		}	
		
	$countend=$q->COUNT_ROWS($table);
	$final=$countend-$countstart;
	echo "".numberFormat($final,0,""," ")." new entries added\n";
	
	@unlink($file);
	
	
}

function UFDBGUARD_COMPILE_DB(){
	$tstart=time();
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	if($EnableRemoteStatisticsAppliance==1){return;}	
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/UFDBGUARD_COMPILE_DB.pid";
	if($unix->process_exists(@file_get_contents($pidfile))){
		echo "Process already exists PID: ".@file_get_contents($pidfile)."\n";
		return;
	}
	
	
	@file_put_contents($pidfile,getmypid());
	$ufdbGenTable=$unix->find_program("ufdbGenTable");
	$datas=explode("\n",@file_get_contents("/etc/squid3/ufdbGuard.conf"));
	if(strlen($ufdbGenTable)<5){echo "ufdbGenTable no such file\n";return ;}
	
	$md5db=unserialize(@file_get_contents("/etc/artica-postfix/ufdbGenTableMD5"));
	
	
	$count=0;
	while (list ($a, $b) = each ($datas)){
		if(preg_match('#domainlist\s+"(.+)\/domains#',$b,$re)){
			$f["/var/lib/squidguard/{$re[1]}"]="/var/lib/squidguard/{$re[1]}";
		}
	}
	
	
	
	if(!is_array($datas)){echo "No databases set\n";return ;}
	while (list ($directory, $b) = each ($f)){
		$mustrun=false;
		if(preg_match("#.+?\/([a-zA-Z0-9\-\_]+)$#",$directory,$re)){
			$category=$re[1];
			$category=substr($category,0,15);
			if($GLOBALS["VERBOSE"]){echo "Checking $category\n";}
		}
		
		// ufdbGenTable -n -D -W -t adult -d /var/lib/squidguard/adult/domains -u /var/lib/squidguard/adult/urls     
		if(is_file("$directory/domains")){
			$md5=FileMD5("$directory/domains");
			if($md5<>$md5db["$directory/domains"]){
				$mustrun=true;
				$md5db["$directory/domains"]=$md5;
				$dbb[]="$directory/domains";
			}else{
				if($GLOBALS["VERBOSE"]){echo "$md5 is the same, skip $directory/domains\n";}
			}
			
			
			$d=" -d $directory/domains";
		}else{
			if($GLOBALS["VERBOSE"]){echo "$directory/domains no such file\n";}
		}
		if(is_file("$directory/urls")){
			$md5=FileMD5("$directory/urls");
			if($md5<>$md5db["$directory/urls"]){$mustrun=true;$md5db["$directory/urls"]=$md5;$dbb[]="$directory/urls";}
			$u=" -u $directory/urls";
		}
		
		if(!is_file("$directory/domains.ufdb")){$mustrun=true;$dbb[]="$directory/*";}
		
		if($mustrun){
				$dbcount=$dbcount+1;
				$category_compile=$category;
				if(strlen($category_compile)>15){
				$category_compile=str_replace("recreation_","recre_",$category_compile);
				$category_compile=str_replace("automobile_","auto_",$category_compile);
				$category_compile=str_replace("finance_","fin_",$category_compile);
				if(strlen($category_compile)>15){
					$category_compile=str_replace("_", "", $category_compile);
					if(strlen($category_compile)>15){
						$category_compile=substr($category_compile, strlen($category_compile)-15,15);
					}
				}
			}			
				
				
			$cmd="$ufdbGenTable -n -D -W -t $category_compile$d$u";
			echo $cmd."\n";
			$t=time();
			shell_exec($cmd);
			$took=$unix->distanceOfTimeInWords($t,time(),true);
			ufdbguard_admin_events("Compiled $category_compile in $directory took $took",@implode("\n",$dbb)."\n",__FUNCTION__,__FILE__,__LINE__, "ufdb-compile");
			if(function_exists("system_is_overloaded")){
				if(system_is_overloaded(__FILE__)){
					ufdbguard_admin_events("Overloaded system after $dbcount compilations, oberting task...",@implode("\n",$dbb)."\n",__FUNCTION__,__FILE__,__LINE__, "ufdb-compile");
					return;
				}
			}
		}
		$u=null;$d=null;$md5=null;
	}
	
	@file_put_contents("/etc/artica-postfix/ufdbGenTableMD5",serialize($md5db));
	$user=GetSquidUser();
	$chown=$unix->find_program($chown);
	if(is_file($chown)){
		shell_exec("$chown -R $user /var/lib/squidguard/*");
		shell_exec("$chown -R $user /var/log/squid/*");
	}	
	if($dbcount>0){
		$took=$unix->distanceOfTimeInWords($tstart,time(),true);
		ufdbguard_admin_events("Maintenance on Web Proxy urls Databases: $dbcount database(s) took $took",@implode("\n",$dbb)."\n",__FUNCTION__,__FILE__,__LINE__, "ufdb-compile");
	}
	
	
	
}

function BuildMissingUfdBguardDBS($all=false,$output=false){
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	if($EnableRemoteStatisticsAppliance==1){return;}	
	$Narray=array();
	$array=explode("\n",@file_get_contents("/etc/ufdbguard/ufdbGuard.conf"));
	while (list ($index, $line) = each ($array) ){
		if(preg_match("#domainlist.+?(.+)\/domains#",$line,$re)){
			$datas_path="/var/lib/squidguard/{$re[1]}/domains";
			$path="/var/lib/squidguard/{$re[1]}/domains.ufdb";
			
			if(!$all){
				if(!is_file($path)){
					if($output){echo "Missing $path\n";} 
					$Narray[$path]=@filesize($datas_path);
				}
			}
			if($all){$Narray[$path]=@filesize($datas_path);}
			
		}
		
	}
	
	echo "Starting......: ".date("H:i:s")." Webfiltering service ". count($Narray)." database(s) must be compiled\n";
	if(!$all){
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/ufdbguard.db.status.txt",serialize($Narray));
		chmod("/usr/share/artica-postfix/ressources/logs/ufdbguard.db.status.txt",777);
	}
	return $Narray;
}

function UFDBGUARD_STATUS(){
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	if($EnableRemoteStatisticsAppliance==1){return;}	
	$Narray=array();
	$unix=new unix();
	$array=explode("\n",@file_get_contents("/etc/ufdbguard/ufdbGuard.conf"));
	while (list ($index, $line) = each ($array) ){
		if(preg_match("#domainlist.+?(.+)\/domains#",$line,$re)){
			$datas_path="/var/lib/squidguard/{$re[1]}/domains";
			$path="/var/lib/squidguard/{$re[1]}/domains.ufdb";
			$size=$unix->file_size($path);
			$Narray[$path]=$size;
			
		}
		
	}
	
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/ufdbguard.db.size.txt",serialize($Narray));
	chmod("/usr/share/artica-postfix/ressources/logs/ufdbguard.db.size.txt",777);
	
	return $Narray;
}


function DisksStatus($aspid=false){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".time";
	
	if(!$aspid){
		$pid=@file_get_contents("$pidfile");
		if($unix->process_exists($pid,basename(__FILE__))){return;}
		$pidTime=$unix->file_time_min($pidTime);
		if($pidTime<5){return;}
	}
	
	@unlink($pidTime);
	@file_put_contents($pidTime, getmypid());
	@file_put_contents($pidfile, getmypid());
	if(system_is_overloaded()){
		$php5=$unix->LOCATE_PHP5_BIN();
		$unix->THREAD_COMMAND_SET("$php5 ".__FILE__." --disks");
		return;}	
	
	
	$q=new mysql_squid_builder();
	
	
	if(!$q->TABLE_EXISTS('webfilters_dbstats')){
			
		$sql="CREATE TABLE IF NOT EXISTS `webfilters_dbstats` (
				  `category` varchar(128) NOT NULL PRIMARY KEY,
				  `articasize` BIGINT UNSIGNED NOT NULL,
				  `unitoulouse` BIGINT UNSIGNED NOT NULL,
				  `persosize` BIGINT UNSIGNED  NOT NULL,
				  KEY `articasize` (`articasize`),KEY `unitoulouse` (`unitoulouse`), KEY `persosize` (`persosize`) )  ENGINE = MYISAM;"; $q->QUERY_SQL($sql);
			
	}	
	
	
	$unix=new unix();
	if($GLOBALS["VERBOSE"]){echo "-> /var/lib/ftpunivtlse1fr\n";}
	$dirs=$unix->dirdir("/var/lib/ftpunivtlse1fr");
	while (list ($a, $dir) = each ($dirs)){
		if(!is_file("$dir/domains.ufdb")){continue;}
		$size=filesize("$dir/domains.ufdb");
		$category=basename($dir);
		$category=$q->filaname_tocat($category);
		$array[$category]["UNIV"]=$size;
		
		
		
	}
	$dirs=$unix->dirdir("/var/lib/squidguard");
	while (list ($a, $dir) = each ($dirs)){
		if(!is_file("$dir/domains.ufdb")){continue;}
		$size=filesize("$dir/domains.ufdb");
		$category=basename($dir);
		$category=$q->filaname_tocat($category);
		$array[$category]["PERSO"]=$size;
	}	
	
	$dirs=$unix->dirdir("/var/lib/ufdbartica");
	while (list ($a, $dir) = each ($dirs)){
		if(!is_file("$dir/domains.ufdb")){continue;}
		$size=filesize("$dir/domains.ufdb");
		$category=basename($dir);
		$category=$q->filaname_tocat($category);
		$array[$category]["ARTICA"]=$size;
	}	
	
	while (list ($category, $sizes) = each ($array)){
		if(!isset($sizes["UNIV"])){$sizes["UNIV"]=0;}
		if(!isset($sizes["ARTICA"])){$sizes["ARTICA"]=0;}
		if(!isset($sizes["PERSO"])){$sizes["PERSO"]=0;}
		$f[]="('$category','{$sizes["ARTICA"]}','{$sizes["UNIV"]}','{$sizes["PERSO"]}')";
		
	}
	
	if(count($f)>0){
		$q->QUERY_SQL("TRUNCATE TABLE webfilters_dbstats");
		$q->QUERY_SQL("INSERT IGNORE INTO webfilters_dbstats (category,articasize,unitoulouse,persosize) VALUES ".@implode(",", $f));
		
	}
	
}


function databases_status(){
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	if($EnableRemoteStatisticsAppliance==1){return;}	
	if($GLOBALS["VERBOSE"]){echo "databases_status() line:".__LINE__."\n";}
	$unix=new unix();
	$chmod=$unix->find_program("chmod");
	@mkdir("/var/lib/squidguard",0755,true);
	$q=new mysql_squid_builder();
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'category_%'";
	$results=$q->QUERY_SQL($sql);
	if($GLOBALS["VERBOSE"]){echo $sql."\n";}	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$table=$ligne["c"];
		if(!preg_match("#^category_(.+)#", $table,$re)){continue;}
		$categoryname=$re[1];
		if($GLOBALS["VERBOSE"]){echo "Checks $categoryname\n";}
		if(is_file("/var/lib/squidguard/$categoryname/domains.ufdb")){
			if($GLOBALS["VERBOSE"]){echo "Checks $categoryname/domains.ufdb\n";}
			$size=@filesize("/var/lib/squidguard/$categoryname/domains.ufdb");
			if($GLOBALS["VERBOSE"]){echo "Checks $categoryname/domains\n";}
			$textsize=@filesize("/var/lib/squidguard/$categoryname/domains");
			
		}
		if(!is_numeric($textsize)){$textsize=0;}
		if(!is_numeric($size)){$size=0;}
		$array[$table]=array("DBSIZE"=>$size,"TXTSIZE"=>$textsize);
	}

	if($GLOBALS["VERBOSE"]){print_r($array);}
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/ufdbguard_db_status", serialize($array));
	shell_exec("$chmod 777 /usr/share/artica-postfix/ressources/logs/web/ufdbguard_db_status");
	
}

function ufdbguard_recompile_missing_dbs(){
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	if($EnableRemoteStatisticsAppliance==1){return;}	
	$unix=new unix();
	$MYSQL_DATA_DIR=$unix->MYSQL_DATA_DIR();
	$touch=$unix->find_program("touch");
	@mkdir("/var/lib/squidguard",0755,true);
	$q=new mysql_squid_builder();
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'category_%'";
	$results=$q->QUERY_SQL($sql);
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$table=$ligne["c"];
		if(!preg_match("#^category_(.+)#", $table,$re)){continue;}
		$categoryname=$re[1];
		echo "Starting......: ".date("H:i:s")." Webfiltering service $table -> $categoryname\n";
		if(!is_file("/var/lib/squidguard/$categoryname/domains")){
			@mkdir("/var/lib/squidguard/$categoryname",0755,true);
			$sql="SELECT LOWER(pattern) FROM {$ligne["c"]} WHERE enabled=1 AND pattern REGEXP '[a-zA-Z0-9\_\-]+\.[a-zA-Z0-9\_\-]+' ORDER BY pattern INTO OUTFILE '$table.temp' FIELDS OPTIONALLY ENCLOSED BY 'n'";
			$q->QUERY_SQL($sql);
			if(!is_file("$MYSQL_DATA_DIR/squidlogs/$table.temp")){
				echo "Starting......: ".date("H:i:s")." Webfiltering service $MYSQL_DATA_DIR/squidlogs/$table.temp no such file\n";
				continue;
			}
			echo "Starting......: ".date("H:i:s")." Webfiltering service $MYSQL_DATA_DIR/squidlogs/$table.temp done...\n";
			@copy("$MYSQL_DATA_DIR/squidlogs/$table.temp", "/var/lib/squidguard/$categoryname/domains");	
			@unlink("$MYSQL_DATA_DIR/squidlogs/$table.temp");
			echo "Starting......: ".date("H:i:s")." Webfiltering service UFDBGUARD_COMPILE_SINGLE_DB(/var/lib/squidguard/$categoryname/domains)\n";
			UFDBGUARD_COMPILE_SINGLE_DB("/var/lib/squidguard/$categoryname/domains");					
		}else{
			echo "Starting......: ".date("H:i:s")." Webfiltering service /var/lib/squidguard/$categoryname/domains OK\n";
			
		}
		
		if(!is_file("/var/lib/squidguard/$categoryname/expressions")){shell_exec("$touch /var/lib/squidguard/$categoryname/expressions");}
		
	}
	build();
	if(is_file("/etc/init.d/ufdb")){
		echo "Starting......: ".date("H:i:s")." Webfiltering service reloading service\n";
		ufdbguard_admin_events("Service will be reloaded",__FUNCTION__,__FILE__,__LINE__,"config");
		build_ufdbguard_HUP();
	}
	
}

function ufdbguard_recompile_dbs(){
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	if($EnableRemoteStatisticsAppliance==1){return;}	
	@unlink("/var/log/artica-postfix/ufdbguard-compilator.debug");
	build();
	$unix=new unix();
	$rm=$unix->find_program("rm");
	shell_exec("$rm -rf /var/lib/squidguard/*");
	ufdbguard_recompile_missing_dbs();	
	
}
function ufdbguard_schedule(){
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	if($EnableRemoteStatisticsAppliance==1){return;}	
	$sock=new sockets();
	$unix=new unix();
	$UfdbGuardSchedule=unserialize(base64_decode($sock->GET_INFO("UfdbGuardSchedule")));
	if(!isset($UfdbGuardSchedule["EnableSchedule"])){$UfdbGuardSchedule["EnableSchedule"]=1;$UfdbGuardSchedule["H"]=5;$UfdbGuardSchedule["M"]=0;}
	$cronfile="/etc/cron.d/artica-ufdb-dbs";	
	if(!is_numeric($UfdbGuardSchedule["EnableSchedule"])){$UfdbGuardSchedule["EnableSchedule"]=1;}
	if($UfdbGuardSchedule["EnableSchedule"]==0){
		@unlink($cronfile);
		echo "Starting......: ".date("H:i:s")." Webfiltering service recompile all databases is not scheduled\n";
		return;
	}
	if(!is_numeric($UfdbGuardSchedule["H"])){$UfdbGuardSchedule["H"]=5;}
	if(!is_numeric($UfdbGuardSchedule["M"])){$UfdbGuardSchedule["M"]=0;}
	$f[]="MAILTO=\"\"";
	$f[]="{$UfdbGuardSchedule["H"]} {$UfdbGuardSchedule["M"]} * * * root ".$unix->LOCATE_PHP5_BIN()." ".__FILE__." --ufdbguard-recompile-dbs >/dev/null 2>&1"; 
	$f[]="";
	@file_put_contents($cronfile,@implode("\n",$f) );	
	echo "Starting......: ".date("H:i:s")." Webfiltering service recompile all databases each day at {$UfdbGuardSchedule["H"]}:{$UfdbGuardSchedule["M"]}\n";
	//events_ufdb_tail("ufdbGuard recompile all databases each day at {$UfdbGuardSchedule["H"]}:{$UfdbGuardSchedule["M"]}",__LINE__);
}

function UFDBGUARD_COMPILE_CATEGORY_PROGRESS($text,$pourc){
	
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/ufdbguard.compile.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	if($GLOBALS["OUTPUT"]){echo "{$pourc}% $text\n";sleep(2);}
}

function UFDBGUARD_COMPILE_CATEGORY($category){
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	$UseRemoteUfdbguardService=$sock->GET_INFO("UseRemoteUfdbguardService");
	if(!is_numeric($UseRemoteUfdbguardService)){$UseRemoteUfdbguardService=0;}	
	if($EnableRemoteStatisticsAppliance==1){
		UFDBGUARD_COMPILE_CATEGORY_PROGRESS("{failed} Stat Appliance enabled",110);
		return;
	}
	if($UseRemoteUfdbguardService==1){
		UFDBGUARD_COMPILE_CATEGORY_PROGRESS("{failed} Use remote service",110);
		return;
	}	
	$unix=new unix();
	if($GLOBALS["VERBOSE"]){
		$ufdbguardd=$unix->find_program("ufdbguardd");
		system("$ufdbguardd -v");
	}
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		UFDBGUARD_COMPILE_CATEGORY_PROGRESS("{failed} $category category aborting,task pid $pid running since {$time}Mn",110);
		ufdbguard_admin_events("Compile $category category aborting,task pid $pid running since {$time}Mn",__FUNCTION__,__FILE__,__LINE__,"compile");
		return;
	}
	@file_put_contents($pidfile, getmypid());
	$t=time();
	
	echo "Starting......: ".date("H:i:s")." Compiling category $category\n";
	UFDBGUARD_COMPILE_CATEGORY_PROGRESS("{compiling} Compiling category $category",2);
	$ufdb=new compile_ufdbguard();
	$ufdb->compile_category($category);
	$sock=new sockets();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	
	if($EnableWebProxyStatsAppliance==1){
		echo "Starting......: ".date("H:i:s")." This server is a Squid Appliance, compress databases and notify proxies\n";
		CompressCategories();	
		notify_remote_proxys();
	}	
}

function UFDBGUARD_COMPILE_ALL_CATEGORIES(){
	$sock=new sockets();
	if(system_is_overloaded(basename(__FILE__))){
		squid_admin_mysql(1, "Overloaded system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting recompiling personal categories", null,__FILE__,__LINE__);
		die();
	}
	
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	$UseRemoteUfdbguardService=$sock->GET_INFO("UseRemoteUfdbguardService");
	if(!is_numeric($UseRemoteUfdbguardService)){$UseRemoteUfdbguardService=0;}	
	if($EnableRemoteStatisticsAppliance==1){return;}
	if($UseRemoteUfdbguardService==1){return;}		
	
	if($EnableRemoteStatisticsAppliance==1){ return; }	
	
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){return;}
	@file_put_contents($pidfile, getmypid());
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	
	if($EnableRemoteStatisticsAppliance==1){UFDBGUARD_DOWNLOAD_ALL_CATEGORIES();return;}
	
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}		
	$q=new mysql_squid_builder();
	$t=time();
	$cats=$q->LIST_TABLES_CATEGORIES();
	$ufdb=new compile_ufdbguard();
	while (list ($table, $line) = each ($cats) ){
		if(preg_match("#categoryuris_#",$table)){continue;}
		$category=$q->tablename_tocat($table);
		if($category==null){squid_admin_mysql(1,"Compilation failed for table $table, unable to determine category",null,__FILE__,__LINE__);continue;}
		$ufdb->compile_category($category);
		
	}
	
	$ttook=$unix->distanceOfTimeInWords($t,time(),true);
	squid_admin_mysql(2,"All personal categories are compiled ($ttook)",@implode("\n", $cats),__FILE__,__LINE__,"global-compile");
	if($EnableWebProxyStatsAppliance==1){CompressCategories();return;}
	
	
}

function CompressCategories(){
	
	
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	if($EnableRemoteStatisticsAppliance==1){return;}
	$unix=new unix();
	$tar=$unix->find_program("tar");
	$chmod=$unix->find_program("chmod");
	$chown=$unix->find_program("chown");
	$lighttpdUser=$unix->LIGHTTPD_USER();
	$StorageDir="/usr/share/artica-postfix/ressources/databases";
	
	if(!is_dir("/var/lib/squidguard")){ufdbguard_admin_events("/var/lib/squidguard no such directory",__FUNCTION__,__FILE__,__LINE__,"global-compile");return;}
	$t=time();
	if(is_dir("/var/lib/squidguard")){
		chdir("/var/lib/squidguard");
		if(is_file("$StorageDir/blacklist.tar.gz")){@unlink("$StorageDir/blacklist.tar.gz");}
		writelogs("Compressing /var/lib/squidguard",__FUNCTION__,__FILE__,__LINE__);
		shell_exec("$tar -czf $StorageDir/blacklist.tar.gz *");
		shell_exec("$chmod 770 $StorageDir/blacklist.tar.gz");
	}
	
	if(is_dir("/var/lib/ftpunivtlse1fr")){
		chdir("/var/lib/ftpunivtlse1fr");
		writelogs("Compressing /var/lib/ftpunivtlse1fr",__FUNCTION__,__FILE__,__LINE__);
		if(is_file("$StorageDir/ftpunivtlse1fr.tar.gz")){@unlink("$StorageDir/ftpunivtlse1fr.tar.gz");}
		shell_exec("$tar -czf $StorageDir/ftpunivtlse1fr.tar.gz *");
		shell_exec("$chmod 770 $StorageDir/ftpunivtlse1fr.tar.gz");
	}
	
	if(is_dir("/etc/dansguardian")){
		chdir("/etc/dansguardian");
		writelogs("Compressing /etc/dansguardian",__FUNCTION__,__FILE__,__LINE__);
		if(is_file("$StorageDir/dansguardian.tar.gz")){@unlink("$StorageDir/dansguardian.tar.gz");}
		exec("$tar -czf $StorageDir/dansguardian.tar.gz * 2>&1",$lines);
		while (list ($linum, $line) = each ($lines) ){writelogs($line,__FUNCTION__,__FILE__,__LINE__);}
		if(!is_file("$StorageDir/dansguardian.tar.gz")){writelogs(".$StorageDir/dansguardian.tar.gz no such file",__FUNCTION__,__FILE__,__LINE__);}
		shell_exec("$chmod 770 /usr/share/artica-postfix/ressources/databases/dansguardian.tar.gz");
	}
	
	writelogs("Compressing done, apply permissions for `$lighttpdUser` user",__FUNCTION__,__FILE__,__LINE__);
	shell_exec("$chown $lighttpdUser:$lighttpdUser $StorageDir");
	shell_exec("$chown $lighttpdUser:$lighttpdUser $StorageDir/*");
	
	$ttook=$unix->distanceOfTimeInWords($t,time(),true);
	ufdbguard_admin_events("compress all categories done ($ttook)",__FUNCTION__,__FILE__,__LINE__,"global-compile");	
	
	
	
}

function cron_compile(){
	$sock=new sockets();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	$isFiltersInstalled=false;
	$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}	
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	if($EnableRemoteStatisticsAppliance==1){return;}
	$users=new usersMenus();
	if($users->APP_UFDBGUARD_INSTALLED){$isFiltersInstalled=true;}
	if($users->DANSGUARDIAN_INSTALLED){$isFiltersInstalled=true;}
	if($EnableWebProxyStatsAppliance==0){if(!$isFiltersInstalled){return;}}

	
			
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();
	$restart=false;
	if($unix->process_exists(@file_get_contents($pidfile))){return;}
	@file_put_contents($pidfile, getmypid());
	
	
	if(is_file("/etc/artica-postfix/ufdbguard.compile.alldbs")){
		$WHY="ufdbguard.compile.alldbs exists";
		@unlink("/etc/artica-postfix/ufdbguard.compile.alldbs");
		events_ufdb_exec("CRON:: -> ufdbguard_recompile_dbs()");
		ufdbguard_admin_events("-> ufdbguard_recompile_dbs()",__FUNCTION__,__FILE__,__LINE__,"config");
		UFDBGUARD_COMPILE_ALL_CATEGORIES();
		return;
	}
	
	if(is_file("/etc/artica-postfix/ufdbguard.compile.missing.alldbs")){
		$WHY="ufdbguard.compile.missing.alldbs exists";
		events_ufdb_exec("CRON:: -> ufdbguard_recompile_missing_dbs()");
		@unlink("/etc/artica-postfix/ufdbguard.compile.missing.alldbs");
		ufdbguard_admin_events("-> ufdbguard_recompile_missing_dbs()",__FUNCTION__,__FILE__,__LINE__,"config");
		ufdbguard_recompile_missing_dbs();
		return;
	}
	
	if(is_file("/etc/artica-postfix/ufdbguard.reconfigure.task")){
		$WHY="ufdbguard.reconfigure.task exists";
		events_ufdb_exec("CRON:: -> build()");
		@unlink("/etc/artica-postfix/ufdbguard.reconfigure.task");
		ufdbguard_admin_events("-> build()",__FUNCTION__,__FILE__,__LINE__,"config");
		build();
		return;
	}
	

	foreach (glob("/etc/artica-postfix/ufdbguard.recompile-queue/*") as $filename) {
		$restart=true;
		$db=@file_get_contents($filename);
		@unlink($filename);
		ufdbguard_admin_events("-> UFDBGUARD_COMPILE_SINGLE_DB(/var/lib/squidguard/$db/domains)",__FUNCTION__,__FILE__,__LINE__,"config");
		UFDBGUARD_COMPILE_SINGLE_DB("/var/lib/squidguard/$db/domains");
		
		
	}
	
	if($restart){
		$unix->send_email_events("cron-compile: Ask to reload ufdbguard service", "\n$WHY\nFunction:".__FUNCTION__."\nFile:".__FILE__."\nLine:".__LINE__, "proxy");
		ufdbguard_admin_events("Service will be reloaded",__FUNCTION__,__FILE__,__LINE__,"ufdbguard-service");
		build_ufdbguard_HUP();
	}
	
	
}

function UFDBGUARD_DOWNLOAD_ALL_CATEGORIES(){
	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
	$unix=new unix();
	$sock=new sockets();
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$RemoteStatisticsApplianceSettings=unserialize(base64_decode($sock->GET_INFO("RemoteStatisticsApplianceSettings")));
	if(!is_numeric($RemoteStatisticsApplianceSettings["SSL"])){$RemoteStatisticsApplianceSettings["SSL"]=1;}
	if(!is_numeric($RemoteStatisticsApplianceSettings["PORT"])){$RemoteStatisticsApplianceSettings["PORT"]=9000;}
	$GLOBALS["REMOTE_SSERVER"]=$RemoteStatisticsApplianceSettings["SERVER"];
	$GLOBALS["REMOTE_SPORT"]=$RemoteStatisticsApplianceSettings["PORT"];
	$GLOBALS["REMOTE_SSL"]=$RemoteStatisticsApplianceSettings["SSL"];
	if($GLOBALS["REMOTE_SSL"]==1){$refix="https";}else{$refix="http";}
	$uri="$refix://{$GLOBALS["REMOTE_SSERVER"]}:{$GLOBALS["REMOTE_SPORT"]}/ressources/databases/blacklist.tar.gz";
	$curl=new ccurl($uri,true);
	if(!$curl->GetFile("/tmp/blacklist.tar.gz")){ufdbguard_admin_events("Failed to download blacklist.tar.gz aborting `$curl->error`",__FUNCTION__,__FILE__,__LINE__,"global-compile");return;}
	$t=time();
	shell_exec("$rm -rf /var/lib/squidguard/*");
	exec("$tar -xf /tmp/blacklist.tar.gz -C /var/lib/squidguard/ 2>&1",$results);
	$ttook=$unix->distanceOfTimeInWords($t,time(),true);
	ufdbguard_admin_events("Extracting blacklist.tar.gz took $ttook `".@implode("\n",$results),__FUNCTION__,__FILE__,__LINE__,"global-compile");
	
	$array=$unix->dirdir("/var/lib/squidguard");
	$GLOBALS["NORESTART"]=true;
	while (list ($index, $directoryPath) = each ($array)){
		if(!is_file("$directoryPath/domains.ufdb")){UFDBGUARD_COMPILE_SINGLE_DB("$directoryPath/domains");}
	}
	
	build_ufdbguard_HUP();
	

}

function Dansguardian_remote(){
	$users=new usersMenus();
	$sock=new sockets();
	$unix=new unix();	
	$tar=$unix->find_program("tar");
	$RemoteStatisticsApplianceSettings=unserialize(base64_decode($sock->GET_INFO("RemoteStatisticsApplianceSettings")));
	if(!is_numeric($RemoteStatisticsApplianceSettings["SSL"])){$RemoteStatisticsApplianceSettings["SSL"]=1;}
	if(!is_numeric($RemoteStatisticsApplianceSettings["PORT"])){$RemoteStatisticsApplianceSettings["PORT"]=9000;}
	$GLOBALS["REMOTE_SSERVER"]=$RemoteStatisticsApplianceSettings["SERVER"];
	$GLOBALS["REMOTE_SPORT"]=$RemoteStatisticsApplianceSettings["PORT"];
	$GLOBALS["REMOTE_SSL"]=$RemoteStatisticsApplianceSettings["SSL"];
	if($GLOBALS["REMOTE_SSL"]==1){$refix="https";}else{$refix="http";}
	$baseUri="$refix://{$GLOBALS["REMOTE_SSERVER"]}:{$GLOBALS["REMOTE_SPORT"]}/ressources/databases";	
	$uri="$baseUri/dansguardian.tar.gz";
	$curl=new ccurl($uri,true);
	if($curl->GetFile("/tmp/dansguardian.tar.gz")){
		$cmd="$tar -xf /tmp/dansguardian.tar.gz -C /etc/dansguardian/";
		writelogs($cmd,__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);
		
		if($users->DANSGUARDIAN_INSTALLED){
			echo "Starting......: ".date("H:i:s")." Dansguardian reloading service\n";
			shell_exec("/usr/share/artica-postfix/bin/artica-install --reload-dansguardian --withoutconfig");
		}		
		
	}else{
		ufdbguard_admin_events("Failed to download dansguardian.tar.gz aborting `$curl->error`",__FUNCTION__,__FILE__,__LINE__,"global-compile");			
	}		
}


function ufdbguard_remote(){
	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
	$users=new usersMenus();
	$sock=new sockets();
	$unix=new unix();
	$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}	
	$timeFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if($unix->file_time_min($timeFile)<5){
		writelogs("too short time to change settings, aborting $called...",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	
	@unlink($timeFile);
	@file_put_contents($timeFile, time());
	@mkdir("/etc/ufdbguard",null,true);
	$tar=$unix->find_program("tar");
	$RemoteStatisticsApplianceSettings=unserialize(base64_decode($sock->GET_INFO("RemoteStatisticsApplianceSettings")));
	if(!is_numeric($RemoteStatisticsApplianceSettings["SSL"])){$RemoteStatisticsApplianceSettings["SSL"]=1;}
	if(!is_numeric($RemoteStatisticsApplianceSettings["PORT"])){$RemoteStatisticsApplianceSettings["PORT"]=9000;}
	$GLOBALS["REMOTE_SSERVER"]=$RemoteStatisticsApplianceSettings["SERVER"];
	$GLOBALS["REMOTE_SPORT"]=$RemoteStatisticsApplianceSettings["PORT"];
	$GLOBALS["REMOTE_SSL"]=$RemoteStatisticsApplianceSettings["SSL"];
	if($GLOBALS["REMOTE_SSL"]==1){$refix="https";}else{$refix="http";}
	$DenyUfdbWriteConf=$sock->GET_INFO("DenyUfdbWriteConf");
	if(!is_numeric($DenyUfdbWriteConf)){$DenyUfdbWriteConf=0;}
	$baseUri="$refix://{$GLOBALS["REMOTE_SSERVER"]}:{$GLOBALS["REMOTE_SPORT"]}/ressources/databases";
	
	if($DenyUfdbWriteConf==0){
		$uri="$baseUri/ufdbGuard.conf";
		$curl=new ccurl($uri,true);
		if($curl->GetFile("/tmp/ufdbGuard.conf")){
			@file_put_contents("/etc/ufdbguard/ufdbGuard.conf", @file_get_contents("/tmp/ufdbGuard.conf"));
			@file_put_contents("/etc/squid3/ufdbGuard.conf", @file_get_contents("/tmp/ufdbGuard.conf"));
		}else{
			ufdbguard_admin_events("Failed to download ufdbGuard.conf aborting `$curl->error`",__FUNCTION__,__FILE__,__LINE__,"global-compile");			
		}
	}

	$uri="$baseUri/blacklist.tar.gz";
	$curl=new ccurl($uri,true);
	if($curl->GetFile("/tmp/blacklist.tar.gz")){
		$cmd="$tar -xf /tmp/blacklist.tar.gz -C /var/lib/squidguard/";
		writelogs($cmd,__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);
	}else{
		ufdbguard_admin_events("Failed to download blacklist.tar.gz aborting `$curl->error`",__FUNCTION__,__FILE__,__LINE__,"global-compile");			
	}	
	
	$uri="$baseUri/ftpunivtlse1fr.tar.gz";
	$curl=new ccurl($uri,true);
	if($curl->GetFile("/tmp/ftpunivtlse1fr.tar.gz")){
		$cmd="$tar -xf /tmp/ftpunivtlse1fr.tar.gz -C /var/lib/ftpunivtlse1fr/";
		writelogs($cmd,__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);
	}else{
		ufdbguard_admin_events("Failed to download ftpunivtlse1fr.tar.gz aborting `$curl->error`",__FUNCTION__,__FILE__,__LINE__,"global-compile");			
	}

	Dansguardian_remote();	
	
	CheckPermissions();	
	ufdbguard_schedule();
	
	if($unix->Ufdbguard_remote_srvc_bool()){ufdbguard_admin_events("Using a remote UfdbGuard service, aborting",__FUNCTION__,__FILE__,__LINE__,"config");return;}
	
	
	ufdbguard_admin_events("Service will be rebuiled and restarted",__FUNCTION__,__FILE__,__LINE__,"config");
	build_ufdbguard_HUP();
	

	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	if(is_file($GLOBALS["SQUIDBIN"])){
		echo "Starting......: ".date("H:i:s")." Squid reloading service\n";
		shell_exec("$nohup $php5 ". basename(__FILE__)."/exec.squid.php --reconfigure-squid >/dev/null 2>&1");
	}	
	
	$datas=@file_get_contents("/etc/ufdbguard/ufdbGuard.conf");
	send_email_events("SquidGuard/ufdbGuard/Dansguardian rules was rebuilded",basename(__FILE__)."\nFunction:".__FUNCTION__."\nLine:".__LINE__."\n".
	"This is new configuration file of the squidGuard/ufdbGuard:\n-------------------------------------\n$datas","proxy");
	shell_exec(LOCATE_PHP5_BIN2()." ".dirname(__FILE__)."/exec.c-icap.php --maint-schedule");	
	
	
}





function events_ufdb_exec($text){
		$pid=@getmypid();
		$date=@date("H:i:s");
		$logFile="/var/log/artica-postfix/ufdbguard-compilator.debug";
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		$textnew="$date [$pid]:: ".basename(__FILE__)." $text\n";
		
		@fwrite($f,$text );
		@fclose($f);	
		}


function events_ufdb_tail($text,$line=0){
		$pid=@getmypid();
		$date=@date("H:i:s");
		$logFile="/var/log/artica-postfix/ufdbguard-tail.debug";
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		if($line>0){$line=" line:$line";}else{$line=null;}
		$textnew="$date [$pid]:: ".basename(__FILE__)." $text$line\n";
		if($GLOBALS["VERBOSE"]){echo $textnew;}
		@fwrite($f,$textnew );
		@fclose($f);	
		events_ufdb_exec($textnew);
		}

function CompileCategoryWords(){
	$unix=new unix();
	$uuid="8cdd119c-2dc1-452d-b9d0-451c6046464f";
	$f=$unix->DirRecursiveFiles("/etc/dansguardian/lists/phraselists");
	$q=new mysql_squid_builder();
	while (list ($index, $filename) = each ($f) ){
		$basename=basename($filename);
		
		
		if(!preg_match("#weighted#",$basename)){continue;}
		$categoryname=basename(dirname($filename));
		$language="english";
		if($categoryname=="pornography"){$categoryname="porn";}
		if($categoryname=="gambling"){$categoryname="gamble";}
		if($categoryname=="nudism"){$categoryname="mixed_adult";}
		if($categoryname=="illegaldrugs"){$categoryname="drugs";}
		if($categoryname=="translation"){$categoryname="translators";}
		if($categoryname=="warezhacking"){$categoryname="warez";}
		
		
		if(preg_match("#weighted_(.+)#", $basename,$re)){$language=$re[1];}
		$language=str_replace("general_", "",$language);
		echo "$basename -> $categoryname ($language)\n";
		
		$q->CreateCategoryWeightedTable();
		
		$lines=explode("\n",@file_get_contents($filename));
		
		
		$prefix="INSERT IGNORE INTO phraselists_weigthed (zmd5,zDate,category,pattern,score,uuid,language) VALUES ";
		
		while (list ($linum, $line) = each ($lines) ){
			if(substr($line,0,1)=="#"){continue;}
			if(preg_match("#.+?<([0-9]+)>$#",$line,$re)){
				$line=str_replace("<{$re[1]}>","",$line);
				echo "$categoryname: $line -> score:{$re[1]}\n";
				$score=$re[1];
				$zmd5=md5($line.$score);
				$zDate=date('Y-m-d H:i:s');
				$line=addslashes($line);
				$sqls[]="('$zmd5','$zDate','$categoryname','$line','$score','$uuid','$language')";
				$sqlb[]="('$zmd5','$zDate','$categoryname','$line','$score','$uuid','$language')";
			}
		}
		
		$q->QUERY_SQL($prefix.@implode(",",$sqls));
		if(!$q->ok){echo $q->mysql_error."\n";}
		$sqls=array();
		
	}
	
	@file_put_contents("/root/weightedPhrases.db", serialize($sqlb));

	
}	

function notify_remote_proxys(){
	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM squidservers";
		$results=$q->QUERY_SQL($sql);
		
		
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$server=$ligne["ipaddr"];
		$port=$ligne["port"];
		if(!is_numeric($port)){continue;}
		$refix="https";
		$uri="$refix://$server:$port/squid.stats.listener.php";
		writelogs($uri,__FUNCTION__,__FILE__,__LINE__);
		$curl=new ccurl($uri,true);
		$curl->parms["CHANGE_CONFIG"]="FILTERS";
		
		if(!$curl->get()){squidstatsApplianceEvents("$server:$port","FAILED Notify change it`s configuration $curl->error");continue;}
		if(preg_match("#<ANSWER>OK</ANSWER>#is",$curl->data)){squidstatsApplianceEvents("$server:$port","SUCCESS to notify change it`s configuration");continue;}
		squidstatsApplianceEvents("$server:$port","FAILED Notify change it`s configuration $curl->data");
	}
}

function FIX_1_CATEGORY_CHECKED(){
	@mkdir("/var/lib/squidguard/checked",0755,true);
	if(!is_file("/var/lib/squidguard/checked/domains")){
		@unlink("/var/lib/squidguard/checked/domains.ufdb");
		for($i=0;$i<=10;$i++){
				$f[]=md5(time()."$i.com").".com";
				$t[]=md5(time()."$i.com").".com/index.html";
		}
		
		@file_put_contents("/var/lib/squidguard/checked/domains", @implode("\n", $f));
	}
	
	if(!is_file("/var/lib/squidguard/checked/urls")){@file_put_contents("/var/lib/squidguard/checked/urls", @implode("\n", $t));}
	if(!is_file("/var/lib/squidguard/checked/expressions")){@file_put_contents("/var/lib/squidguard/checked/expressions", "\n");}	
	
	if(!is_file("/var/lib/squidguard/checked/domains.ufdb")){
		$ufd=new compile_ufdbguard();
		$ufd->compile_category("checked");
	}
	
	
	
}

function ufdbdatabases_in_mem(){
	$sock=new sockets();
	$unix=new unix();
	$UfdbDatabasesInMemory=$sock->GET_INFO("UfdbDatabasesInMemory");
	if(!is_numeric($UfdbDatabasesInMemory)){$UfdbDatabasesInMemory=0;}
	if($UfdbDatabasesInMemory==0){
		echo "Starting URLfilterDB Database in memory feature is disabled\n";
		$MOUNTED_DIR_MEM=$unix->MOUNTED_TMPFS_MEM("/var/lib/ufdbguard-memory");
		if($MOUNTED_DIR_MEM>0){
			echo "Starting URLfilterDB Database unmounting...\n";
			$umount=$unix->find_program("umount");
			shell_exec("$umount -l /var/lib/ufdbguard-memory");
		}
		return;
	}
	
	
	$POSSIBLEDIRS[]="/var/lib/ufdbartica";
	$POSSIBLEDIRS[]="/var/lib/squidguard";
	$POSSIBLEDIRS[]="/var/lib/ftpunivtlse1fr";
	
	$ufdbartica_size=$unix->DIRSIZE_BYTES("/var/lib/ufdbartica");
	$ufdbartica_size=round(($ufdbartica_size/1024)/1000)+5;
	
	$squidguard_size=$unix->DIRSIZE_BYTES("/var/lib/squidguard");
	$squidguard_size=round(($squidguard_size/1024)/1000)+5;
	$ftpunivtlse1fr_size=$unix->DIRSIZE_BYTES("/var/lib/ftpunivtlse1fr");
	$ftpunivtlse1fr_size=round(($ftpunivtlse1fr_size/1024)/1000)+5;
	echo "Starting URLfilterDB ufdbartica DB....: about {$ufdbartica_size}MB\n";
	echo "Starting URLfilterDB squidguard DB....: about {$squidguard_size}MB\n";
	echo "Starting URLfilterDB ftpunivtlse1fr DB: about {$ftpunivtlse1fr_size}MB\n";
	$total=$ufdbartica_size+$squidguard_size+$ftpunivtlse1fr_size+10;
	echo "Starting URLfilterDB require {$total}MB\n";
	$mount=$unix->find_program("mount");
	
	$MOUNTED_DIR_MEM=$unix->MOUNTED_TMPFS_MEM("/var/lib/ufdbguard-memory");
	if($MOUNTED_DIR_MEM==0){
		$system_mem=$unix->TOTAL_MEMORY_MB();
		echo "Starting URLfilterDB system memory {$system_mem}MB\n";
		if($system_mem<$total){
			$require=$total-$system_mem;
			echo "Starting URLfilterDB not engough memory require at least {$require}MB\n";
			return;
		}
		$system_free=$unix->TOTAL_MEMORY_MB_FREE();
		echo "Starting URLfilterDB system memory available {$system_free}MB\n";
		if($system_free<$total){
			$require=$total-$system_free;
			echo "Starting URLfilterDB not engough memory require at least {$require}MB\n";
			return;
		}
	}
	
	$idbin=$unix->find_program("id");
	$mount=$unix->find_program("mount");
	$umount=$unix->find_program("umount");
	$rm=$unix->find_program("rm");
	$cp=$unix->find_program("cp");
	$chown=$unix->find_program("chown");
	if($MOUNTED_DIR_MEM>0){
		if($MOUNTED_DIR_MEM<$total){
			echo "Starting URLfilterDB: umounting from memory\n";
			shell_exec("$umount -l /var/lib/ufdbguard-memory");
			$MOUNTED_DIR_MEM=$unix->MOUNTED_TMPFS_MEM("/var/lib/ufdbguard-memory");
		}
	}

	if($MOUNTED_DIR_MEM==0){
		if(strlen($idbin)<3){echo "Starting URLfilterDB: tmpfs `id` no such binary\n";return;}
		if(strlen($mount)<3){echo "Starting URLfilterDB: tmpfs `mount` no such binary\n";return;}
		exec("$idbin squid 2>&1",$results);
		if(!preg_match("#uid=([0-9]+).*?gid=([0-9]+)#", @implode("", $results),$re)){echo "Starting......: ".date("H:i:s")."MySQL mysql no such user...\n";return;}
		$uid=$re[1];
		$gid=$re[2];
		echo "Starting URLfilterDB: tmpfs uid/gid =$uid:$gid for {$total}M\n";
		@mkdir("/var/lib/ufdbguard-memory");
		$cmd="$mount -t tmpfs -o rw,uid=$uid,gid=$gid,size={$total}M,nr_inodes=10k,mode=0700 tmpfs \"/var/lib/ufdbguard-memory\"";
		shell_exec($cmd);	
		$MOUNTED_DIR_MEM=$unix->MOUNTED_TMPFS_MEM("/var/lib/ufdbguard-memory");
		if($MOUNTED_DIR_MEM==0){
			echo "Starting URLfilterDB: tmpfs failed...\n";
			return;
		}
	}
	
	echo "Starting URLfilterDB: mounted as {$MOUNTED_DIR_MEM}MB\n";
	reset($POSSIBLEDIRS);
	while (list ($index, $directory) = each ($POSSIBLEDIRS) ){
		$directoryname=basename($directory);
		@mkdir("/var/lib/ufdbguard-memory/$directoryname",0755,true);
		if(!is_dir("/var/lib/ufdbguard-memory/$directoryname")){
			echo "Starting URLfilterDB: $directoryname permission denied\n";
			return;
		}
		@chown("/var/lib/ufdbguard-memory/$directoryname","squid");
		echo "Starting URLfilterDB: replicating $directoryname\n";
		shell_exec("$cp -rfu $directory/* /var/lib/ufdbguard-memory/$directoryname/");
	}
	
	$ufdbguardConfs[]="/etc/ufdbguard/ufdbGuard.conf";
	$ufdbguardConfs[]="/etc/squid3/ufdbGuard.conf";
	
	echo "Starting URLfilterDB: setup privileges\n";
	shell_exec("$chown -R squid:squid /var/lib/ufdbguard-memory >/dev/null 2>&1");
	
	echo "Starting URLfilterDB: modify configuration files\n";
	while (list ($index, $configfile) = each ($ufdbguardConfs) ){
		$f=explode("\n",@file_get_contents($configfile));
		while (list ($indexLine, $line) = each ($f) ){
			reset($POSSIBLEDIRS);
			while (list ($index, $directory) = each ($POSSIBLEDIRS) ){
				$directoryname=basename($directory);
				$line=str_replace($directory, "/var/lib/ufdbguard-memory/$directoryname", $line);
				$f[$indexLine]=$line;
			}
		}
	
		@file_put_contents($configfile, @implode("\n", $f));
		echo "Starting URLfilterDB: $configfile success...\n";
	}
	
}



function stop_ufdbguard($aspid=false){
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
	
	$pid=ufdbguard_pid();
	
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		return;
	}
	$pid=ufdbguard_pid();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	squid_admin_mysql(0, "Stopping Web Filtering engine service","",__FILE__,__LINE__);
	
	
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=ufdbguard_pid();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}
	
	$pid=ufdbguard_pid();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return;
	}
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=ufdbguard_pid();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}
	
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}

}

function ufdguard_artica_db_status(){
	$unix=new unix();
	$mainpath="/var/lib/ufdbartica";
	
	
	$mainpath_size=$unix->DIRSIZE_BYTES($mainpath);
	
	$array["SIZE"]=$mainpath_size;
	if(is_file("$mainpath/category_porn/domains.ufdb")){
		$date=filemtime("$mainpath/category_porn/domains.ufdb");
		$array["DATE"]=$date;
	}else{
		$array["DATE"]=0;
	}
	@file_put_contents("/etc/artica-postfix/ARTICA_WEBFILTER_DB_STATUS", serialize($array));
	
}

























function ini_set_verbosedx(){
	ini_set('html_errors',0);
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string','');
	ini_set('error_append_string','');
	$GLOBALS["VERBOSE"]=true;
}
?>