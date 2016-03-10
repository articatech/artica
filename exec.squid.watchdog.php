<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}




$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["DUMP"]=false;
$GLOBALS["MONIT"]=false;
$GLOBALS["CRASHED"]=false;
$GLOBALS["BY_CACHE_LOGS"]=false;
$GLOBALS["BY_STATUS"]=false;
$GLOBALS["BY_CLASS_UNIX"]=false;
$GLOBALS["BY_FRAMEWORK"]=false;
$GLOBALS["BY_OTHER_SCRIPT"]=false;
$GLOBALS["BY_ARTICA_INSTALL"]=false;
$GLOBALS["BY_RESET_CACHES"]=false;
$GLOBALS["BY_CRON"]=false;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["KILL_ALL"]=false;
$GLOBALS["URGENCY"]=false;
$GLOBALS["START_PROGRESS"]=false;

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;
	$GLOBALS["debug"]=true;
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#--monit#",implode(" ",$argv))){$GLOBALS["MONIT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#reconfigure-count=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE_COUNT"]=$re[1];}

if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--dump#",implode(" ",$argv),$re)){$GLOBALS["DUMP"]=true;}
if(preg_match("#--crashed#",implode(" ",$argv),$re)){$GLOBALS["CRASHED"]=true;}
if(preg_match("#--cache-logs#",implode(" ",$argv),$re)){$GLOBALS["BY_CACHE_LOGS"]=true;}
if(preg_match("#--exec-status#",implode(" ",$argv),$re)){$GLOBALS["BY_STATUS"]=true;}
if(preg_match("#--class-unix#",implode(" ",$argv),$re)){$GLOBALS["BY_CLASS_UNIX"]=true;}
if(preg_match("#--framework#",implode(" ",$argv),$re)){$GLOBALS["BY_FRAMEWORK"]=true;}
if(preg_match("#--script=(.+)#",implode(" ",$argv),$re)){$GLOBALS["BY_OTHER_SCRIPT"]=$re[1];}
if(preg_match("#--bydaemon#",implode(" ",$argv),$re)){$GLOBALS["BY_ARTICA_INSTALL"]=true;}
if(preg_match("#--bycron#",implode(" ",$argv),$re)){$GLOBALS["BY_CRON"]=true;}
if(preg_match("#--byForceReconfigure#",implode(" ",$argv),$re)){$GLOBALS["BY_FORCE_RECONFIGURE"]=true;}
if(preg_match("#--by-reset-caches#",implode(" ",$argv),$re)){$GLOBALS["BY_RESET_CACHES"]=true;$GLOBALS["FORCE"]=true;}
if(preg_match("#--kill-all#",implode(" ",$argv),$re)){$GLOBALS["KILL_ALL"]=true;$GLOBALS["FORCE"]=true;}
if(preg_match("#--urgency#",implode(" ",$argv),$re)){$GLOBALS["URGENCY"]=true;$GLOBALS["URGENCY"]=true;}
if(preg_match("#--start-progress#",implode(" ",$argv),$re)){$GLOBALS["START_PROGRESS"]=true;}





$GLOBALS["AS_ROOT"]=true;
$GLOBALS["MYPID"]=getmypid();
$GLOBALS["NO_KILL_MYSQL"]=true;
$GLOBALS["STAMP_MAX_RESTART"]="/etc/artica-postfix/SQUID_STAMP_RESTART";
$GLOBALS["STAMP_MAX_RESTART_TTL"]="/etc/artica-postfix/STAMP_MAX_RESTART_TTL";
$GLOBALS["STAMP_MAX_PING"]="/etc/artica-postfix/SQUID_STAMP_MAX_PING";
$GLOBALS["STAMP_FAILOVER"]="/etc/artica-postfix/SQUID_FAILOVER";
$GLOBALS["STAMP_REBOOT"]="/etc/artica-postfix/SQUID_REBOOT";
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
include_once(dirname(__FILE__)."/ressources/class.resolv.conf.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.booster.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.watchdog.inc");
include_once(dirname(__FILE__)."/ressources/class.influx.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");

	$AsCategoriesAppliance=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/AsCategoriesAppliance"));
	if($AsCategoriesAppliance==1){die();}
	$SQUIDEnable=@file_get_contents(@file_get_contents("/etc/artica-postfix/settings/Daemons/SQUIDEnable"));
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	if($SQUIDEnable==0){die();}
	$unix=new unix();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	if(!is_file($squidbin)){die();}

	$GLOBALS["ARGVS"]=implode(" ",$argv);
	
	
	if($argv[1]=="--shm"){dev_shm();die();}
	if($argv[1]=="--redirector-array"){redirectors_array(true);die();}
	if($argv[1]=="--ufdb-kill"){killufdbgClientPHP();die();}
	if($argv[1]=="--cache-mem"){squid_cache_mem_current();die();}
	if($argv[1]=="--caches-size"){caches_size();die();}
	if($argv[1]=="--cnx"){squid_conx();die();}
	if($argv[1]=="--mem"){squid_memory_monitor();die();}
	if($argv[1]=="--mem-month"){die();}
	
	if($argv[1]=="--storage-infos"){squid_get_storage_info();die();}
	if($argv[1]=="--check-status"){check_status();die();}
	if($argv[1]=="--external-acl-children-more"){external_acl_children_more();die();}
	if($argv[1]=="--redirectors-more"){redirectors_more();die();exit;}
	if($argv[1]=="--ha-up"){CHECK_HA_MASTER_UP(true);die();}
	if($argv[1]=="--ha"){CHECK_HA_MASTER_UP();die();}
	if($argv[1]=="--caches-center-status"){$GLOBALS["OUTPUT"]=true;cache_center_status();die();}
	if($argv[1]=="--caches-center"){$GLOBALS["OUTPUT"]=true;caches_center();die();}
	if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop_squid();die();}
	if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start_squid();die();}
	if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart_squid();die();}
	if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload_squid();die();}
	if($argv[1]=="--squidz"){$GLOBALS["OUTPUT"]=true;squidz();die();}
	
	if($argv[1]=="--tests-smtp"){$GLOBALS["OUTPUT"]=true;test_smtp_watchdog();die();}
	if($argv[1]=="--swapstate"){$GLOBALS["OUTPUT"]=true;$GLOBALS["SWAPSTATE"]=true;restart_squid();die();}
	if($argv[1]=="--storedirs"){$GLOBALS["OUTPUT"]=true;CheckStoreDirs();die();}
	if($argv[1]=="--memboosters"){$GLOBALS["OUTPUT"]=true;MemBoosters();die();}
	if($argv[1]=="--swap"){$GLOBALS["OUTPUT"]=true;SwapCache();die();}
	if($argv[1]=="--counters"){$GLOBALS["OUTPUT"]=true;counters();die();}
	if($argv[1]=="--peer-status"){$GLOBALS["OUTPUT"]=true;peer_status();die();}
	if($argv[1]=="--dead-parent"){peer_dead($argv[2]);die();}
	if($argv[1]=="--ping"){PING_GATEWAY();die();}
	if($argv[1]=="--ntlmauthenticator"){ntlmauthenticator();die();}
	if($argv[1]=="--ntlmauthenticator-edit"){ntlmauthenticator_edit($argv[2]);die();}
	if($argv[1]=="--logs"){CheckOldCachesLog();die();}
	if($argv[1]=="--DeletedCaches"){DeletedCaches();die();}
	if($argv[1]=="--CleanMemBoosters"){CleanMemBoosters();die();}
	if($argv[1]=="--ufdb"){CheckUFDBGuardConfig(true);die();}
	if($argv[1]=="--www"){Checks_external_webpage();die();}
	if($argv[1]=="--squid-store-status"){$GLOBALS["OUTPUT"]=true;ALL_STATUS();die();}
	if($argv[1]=="--swap-watch"){SwapWatchdog();die();}
	if($argv[1]=="--checkufdbguard"){CheckUFDBGuardConfig(true);die();}
	if($argv[1]=="--route"){DefaultRoute(true);die();}
	if($argv[1]=="--idns"){idns();die();}
	if($argv[1]=="--dns"){die();}
	if($argv[1]=="--ufdbthreads"){CheckUFDBGuardLocalThreads();die();}
	if($argv[1]=="--sizes"){CheckAvailableSize();die();}
	if($argv[1]=="--info"){CheckGlobalInfos(true);die();exit;}
	if($argv[1]=="--rqs"){die("Depreciated");exit;}
	if($argv[1]=="--rqsb"){die("Depreciated");exit;}
	if($argv[1]=="--allkids"){ALLKIDS();die();exit;}
	if($argv[1]=="--all-status"){ALL_STATUS();die();exit;}
	
	if($argv[1]=="--icap"){C_ICAP_CLIENTS();die();exit;}
	if($argv[1]=="--varlog"){verify_var_log();die();exit;}
	if($argv[1]=="--disable-snmp"){disable_snmp();die();exit;}
	if($argv[1]=="--mem-status"){squid_mem_status(true);die();exit;}
	if($argv[1]=="--store-status"){squid_stores_status(true);die();exit;}
	if($argv[1]=="--test-port"){TEST_PORT(true);die();exit;}
	if($argv[1]=="--start-progress"){$GLOBALS["START_PROGRESS"]=true;start_squid();die();exit;}
	if($argv[1]=="--categories"){$GLOBALS["START_PROGRESS"]=true;CATEGORIES_SERVICE();die();exit;}
	if($argv[1]=="--bandwidth-cron"){BANDWIDTH_MONITOR_CRON();die();exit;}
	if($argv[1]=="--bandwidth-run"){BANDWIDTH_MONITOR();die();exit;}
	if($argv[1]=="--google"){DisableGoogleSSL();die();}
	if($argv[1]=="--wifidog"){CHECK_WIFIDOG_IPTABLES_RULES();die();}
	if($argv[1]=="--import-old-logs"){import_old_logs();die();}
	if($argv[1]=="--taskset"){taskset();die();}
	
	
	
	
	if($GLOBALS["VERBOSE"]){echo "start_watchdog()\n";}
	start_watchdog();
	
function PING_GATEWAY_DEFAULT_PARAMS($MonitConfig){
	if(!isset($MonitConfig["ENABLE_PING_GATEWAY"])){$MonitConfig["ENABLE_PING_GATEWAY"]=0;}
	if(!isset($MonitConfig["MAX_PING_GATEWAY"])){$MonitConfig["MAX_PING_GATEWAY"]=10;}
	if(!isset($MonitConfig["PING_FAILED_RELOAD_NET"])){$MonitConfig["PING_FAILED_RELOAD_NET"]=0;}
	if(!isset($MonitConfig["PING_FAILED_REPORT"])){$MonitConfig["PING_FAILED_REPORT"]=1;}
	if(!isset($MonitConfig["PING_FAILED_REBOOT"])){$MonitConfig["PING_FAILED_REBOOT"]=0;}
	if(!isset($MonitConfig["PING_FAILED_FAILOVER"])){$MonitConfig["PING_FAILED_FAILOVER"]=0;}
	if(!is_numeric($MonitConfig["ENABLE_PING_GATEWAY"])){$MonitConfig["ENABLE_PING_GATEWAY"]=0;}
	if(!is_numeric($MonitConfig["MAX_PING_GATEWAY"])){$MonitConfig["MAX_PING_GATEWAY"]=10;}
	if(!is_numeric($MonitConfig["PING_FAILED_RELOAD_NET"])){$MonitConfig["PING_FAILED_RELOAD_NET"]=0;}
	if(!is_numeric($MonitConfig["PING_FAILED_REPORT"])){$MonitConfig["PING_FAILED_REPORT"]=1;}
	if(!is_numeric($MonitConfig["PING_FAILED_REBOOT"])){$MonitConfig["PING_FAILED_REBOOT"]=0;}
	if(!is_numeric($MonitConfig["PING_FAILED_FAILOVER"])){$MonitConfig["PING_FAILED_FAILOVER"]=0;}	
	return $MonitConfig;
}

function DNSCACHE(){
	$sock=new sockets();
	$unix=new unix();
	$EnableLocalDNSMASQ=$sock->GET_INFO('EnableLocalDNSMASQ');
	if(!is_numeric($EnableLocalDNSMASQ)){$EnableLocalDNSMASQ=0;}
	if($EnableLocalDNSMASQ==0){return;}
	$pid=DNSCACHE_PID_NUM();
	Events("DNSCACHE_PID_NUM -> $pid");
	if(!$unix->process_exists($pid)){
		squid_admin_mysql(1, "DNS Cache stopped", "Starting DNS cache, not started");
		shell_exec("/etc/init.d/dnsmasq start");
		$pid=DNSCACHE_PID_NUM();
		if(!$unix->process_exists($pid)){
			squid_admin_mysql(1, "DNS Cache stopped/bugged", "Unable to start DNS Cache !!!");
		}
	}
	
	Events("DONE....");
	return;
}

function DNSCACHE_PID_NUM(){

	$unix=new unix();

	$pid=$unix->get_pid_from_file("/var/run/dnsmasq.pid");
	if($unix->process_exists($pid)){return $pid;}

	$Masterbin=$unix->find_program("dnsmasq");
	return $unix->PIDOF($Masterbin);

}

function disable_snmp(){
	$squid=new squidbee();
	if($squid->snmp_enable==1){
		squid_admin_mysql(1,"SNMP feature was disabled by watchdog",null,__FILE__,__LINE__);
		$squid->snmp_enable=0;
		$squid->SaveToLdap();
	}
	
}

function verify_var_log(){
	if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."][".__LINE__."]\n";}
	$unix=new unix();
	$df=$unix->find_program("df");
	$php=$unix->LOCATE_PHP5_BIN();
	$rm=$unix->find_program("rm");
	$echobin=$unix->find_program("echo");
	$varlogsquid="/var/log/squid";
	$sock=new sockets();
	if(is_link($varlogsquid)){$varlogsquid=@readlink($varlogsquid);}
	$LogsWarninStop=intval($sock->GET_INFO("LogsWarninStop"));
	
	if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."][".__LINE__."] LogsWarninStop=$LogsWarninStop\n";}
	
	$cmd="$df $varlogsquid 2>&1";
	if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."][".__LINE__."] $cmd\n";}
	exec($cmd,$results);
	
	
	
	while (list ($num, $line) = each ($results) ){
		
		if(!preg_match("#(.+?)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9\.]+)%\s+#", $line,$re)){continue;}
		$ARRAY["USED"]=$re[2];
		$ARRAY["PERCENT"]=$re[5];
		
	}
	
	if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."][".__LINE__."] Used:{$ARRAY["USED"]} Percent={$ARRAY["PERCENT"]}\n";}
	
	
	if($ARRAY["PERCENT"]>99){
		$filetime=$unix->file_time_min("/etc/artica-postfix/pids/squid.verify_var_log_99.time");
		$GLOBALS["ALL_SCORES"]++;
		$GLOBALS["ALL_SCORES_WHY"][]="/var/log/squid partition exceed 99%";
		
		if($LogsWarninStop==0){
			squid_admin_mysql(1, "Warning partition log exceed 99% [action=stop logging]", "Proxy is configured to not stop logging",__FILE__,__LINE__);
			@file_put_contents("/etc/artica-postfix/settings/Daemons/LogsWarninStop", 1);
			shell_exec("$php /usr/share/artica-postfix/exec.squid.php --build --force");
			die();
		}
		
		if($filetime>15){
			squid_admin_mysql(1, "Warning partition log exceed 99% [action=stop logging]", "Proxy is configured to not stop logging",__FILE__,__LINE__);
			@unlink("/etc/artica-postfix/pids/squid.verify_var_log_99.time");
			@file_put_contents("/etc/artica-postfix/pids/squid.verify_var_log_99.time", time());
		}
	}	
	
	
	if($ARRAY["PERCENT"]>80){
		$filetime=$unix->file_time_min("/etc/artica-postfix/pids/squid.verify_var_log_80.time");
		if($filetime>30){
			squid_admin_mysql(1, "Warning partition log exceed 80% [action=warn]", null,__FILE__,__LINE__);
			@unlink("/etc/artica-postfix/pids/squid.verify_var_log_80.time");
			@file_put_contents("/etc/artica-postfix/pids/squid.verify_var_log_80.time", time());
		}
	}

	if($ARRAY["PERCENT"]>95){
		
		$filetime=$unix->file_time_min("/etc/artica-postfix/pids/squid.verify_var_log_95.time");
		if($filetime>30){
			squid_admin_mysql(1, "Warning partition log exceed 95% [action=cleaning something]", null,__FILE__,__LINE__);
			@unlink("/etc/artica-postfix/pids/squid.verify_var_log_95.time");
			@file_put_contents("/etc/artica-postfix/pids/squid.verify_var_log_95.time", time());
		}
		
		
		
		// on verify
		if(is_dir("/var/log/installer")){shell_exec("$rm -rf /var/log/installer");}
		$f["/var/log/lighttpd/apache-access.log"]=True;
		$f["/var/log/lighttpd/apache-error.log"]=True;
		$f["/var/log/lighttpd/php.log"]=True;
		$f["/var/log/lighttpd/squidguard-lighttpd-error.log"]=True;
		$f["/var/log/lighttpd/squidguard-lighttpd.log"]=True;
		$f["/var/log/ufdbcat/ufdbguardd.log"]=True;
		$f["/var/log/squid/cache.log"]=True;
		while (list ($filename, $line) = each ($f) ){
			if(is_file($filename)){shell_exec("$echobin \"\" > $filename 2>&1");}
				
		}
		
		$articalogsSize=$unix->DIRSIZE_MB("/var/log/artica-postfix");
		if($articalogsSize>10){
			shell_exec("$rm -rf /var/log/artica-postfix/*");
		}
		
		
		
		$filesize=@filesize("/var/log/syslog");
		$filesize=$filesize/1024;
		$filesize=$filesize/1024;
		if($filesize>100){
			squid_admin_mysql(0, "Warning partition log exceed 95%  [action=empty syslog]", null,__FILE__,__LINE__);
			shell_exec("$echobin \"\" > /var/log/syslog 2>&1");
			$unix->RESTART_SYSLOG();
		}
		
		
		$filesize=@filesize("/var/log/squid/access.log");
		$filesize=$filesize/1024;
		$filesize=$filesize/1024;
		
		if($filesize>300){
			@mkdir("/home/artica/squidlogs_urgency",0755,true);
			squid_admin_mysql(0, "Warning partition log exceed 95% log = {$filesize}Mb [action=rotate]", null,__FILE__,__LINE__);
			@copy("/var/log/squid/access.log", "/home/artica/squidlogs_urgency/access.".time().".log");
			@unlink("/var/log/squid/access.log");
			@touch("/var/log/squid/access.log");
			$unix->chown_func("squid","squid","/var/log/squid/access.log");
			shell_exec("/etc/init.d/squid reload --script=".basename(__FILE__));
		}
		
	}
	
	
	
	

	
	
}


function watchdog_config_default($MonitConfig){
	$w=new squid_watchdog();
	return $w->MonitConfig;	
}

function ALL_STATUS($aspid=false){
	if(is_file("/usr/bin/cgclassify")){if(is_dir("/cgroups/blkio/php")){shell_exec("/usr/bin/cgclassify -g cpu,cpuset,blkio:php ".getmypid());}}
	$unix=new unix();
	
	
	if(!is_file("/usr/sbin/mgr-info")){
		$ln=$unix->find_program("ln");
		shell_exec("$ln -sf ".dirname(__FILE__)."/exec.cmdline.squid.cache.mem.php /usr/sbin/mgr-info");
		@chmod(dirname(__FILE__)."/exec.cmdline.squid.cache.mem.php", 0755);
	}

	if($GLOBALS["VERBOSE"]){
		$cmdline_verbose=" --verbose";
		$GLOBALS["OUTPUT"]=true;}
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")."Already `task` running PID $pid since {$time}mn\n";}
			system_admin_events("stop_squid::Already task running PID $pid since {$time}mn", __FUNCTION__, __FILE__, __LINE__, "proxy");
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	build_progress_status("verify_var_log()",2);
	verify_var_log();
	squeezer();
	build_progress_status("squid_stores_status()",5);
	squid_stores_status();
	build_progress_status("squid_mem_status()",8);
	squid_mem_status();
	build_progress_status("squid_memory_monitor()",9);
	squid_memory_monitor();
	build_progress_status("bandwith_stats_today()",9);
	bandwith_stats_today();
	build_progress_status("squid_cache_mem_current()",9);
	squid_cache_mem_current();
	build_progress_status("redirectors_array()",9);
	redirectors_array();
	build_progress_status("CATEGORIES_SERVICE()",9);
	CATEGORIES_SERVICE();
	build_progress_status("dev_shm()",9);
	dev_shm();
	build_progress_status("DisableGoogleSSL()",9);
	DisableGoogleSSL();
	build_progress_status("CHECK_WIFIDOG_IPTABLES_RULES()",9);
	CHECK_WIFIDOG_IPTABLES_RULES();
	build_progress_status("taskset()",9);
	taskset();
	
	build_progress_status("CRON_NECESSARIES()",9);
	CRON_NECESSARIES();
	build_progress_status("import_old_logs()",9);
	import_old_logs();
	
	$php5=$unix->LOCATE_PHP5_BIN();
	build_progress_status("CheckGlobalInfos()",10);
	CheckGlobalInfos();
	build_progress_status("ALLKIDS()",20);
	ALLKIDS();
	build_progress_status("C_ICAP_CLIENTS()",22);
	C_ICAP_CLIENTS();
	
	build_progress_status("squid_stores_status()",30);
	squid_stores_status();
	build_progress_status("{caches_center} {status}",30);
	cache_center_status();
	eCapClamav();
	squid_conx();
	if($GLOBALS["FORCE"]){
		build_progress_status("CacheInfos()",50);
		system("$php5 /usr/share/artica-postfix/exec.squid.php --cache-infos --force --verbose");
		build_progress_status("{caches_center}",60);
		system("$php5 ".__FILE__." --caches-center --force$cmdline_verbose");
		
		
		
	}
	build_progress_status("Done...",100);
}

function squeezer(){
}

function build_progress_status($text,$pourc){
	$cachefile="/usr/share/artica-postfix/ressources/logs/squid.reload.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}
function build_progress_status2($text,$pourc){
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/squid.status.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}


function DisableGoogleSSL(){
	
	
	$TimeFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if(is_file($TimeFile)){
		$unix=new unix();
		if($unix->file_time_min($TimeFile)<20){return;}
		@unlink($TimeFile);
		@touch($TimeFile);
	}
	
	Events("DisableGoogleSSL()...");
	$sock=new sockets();
	$DisableGoogleSSL=intval($sock->GET_INFO("DisableGoogleSSL"));
	$EnableGoogleSafeSearch=$sock->GET_INFO("EnableGoogleSafeSearch");
	if(!is_numeric($EnableGoogleSafeSearch)){$EnableGoogleSafeSearch=1;}
	$Enabled=1;
	if($DisableGoogleSSL==0){
		if($EnableGoogleSafeSearch==0){
			$Enabled=0;
		}
	}
	
	if($Enabled==1){Events("DisableGoogleSSL() is enabled...");return;}
		
	Events("DisableGoogleSSL() is disabled...");
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	
	$f=explode("\n",@file_get_contents("/etc/hosts"));
	
	Events("DisableGoogleSSL() Scanning ".count($f)." entries");
	
	while (list ($index, $line) = each ($f)){
		if(preg_match("#google\.com#", $line)){
			squid_admin_mysql(1, "Google no-ssl was disabled but still exists in system [action=disable]", null,__FILE__,__LINE__);
			Events("$php /usr/share/artica-postfix/exec.nosslsearch.google.com.php --run");
			shell_exec("$nohup $php /usr/share/artica-postfix/exec.nosslsearch.google.com.php --run >/dev/null 2>&1 &");
			return;
		}
		if($GLOBALS["VERBOSE"]){echo "$line no match\n";}
		
	}
	Events("DisableGoogleSSL() Done... Nothing to do...");
	
}
	




function build_progress_restart($text,$pourc){
	if($GLOBALS["VERBOSE"]){echo "******************** {$pourc}% $text ********************\n";}
	
	$cachefile="/usr/share/artica-postfix/ressources/logs/squid.restart.progress";
	
	if($GLOBALS["URGENCY"]){
		$cachefile="/usr/share/artica-postfix/ressources/logs/web/squid.urgency.disable.progress";
	}
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}	
function build_progress_reload($text,$pourc){
	if($GLOBALS["VERBOSE"]){echo "******************** {$pourc}% $text ********************\n";}else{
		echo "Starting......: ".date("H:i:s")." {$pourc}% $text\n";
	}
	$cachefile="/usr/share/artica-postfix/ressources/logs/squid.reload.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	Events($text);
}
function build_progress_start($text,$pourc){
	if(!$GLOBALS["START_PROGRESS"]){return;}
	if($GLOBALS["VERBOSE"]){echo "******************** {$pourc}% $text ********************\n";}else{
		echo "Starting......: ".date("H:i:s")." {$pourc}% $text\n";
	}
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/squid.start.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	sleep(1);
}


function start_watchdog(){
	if($GLOBALS["VERBOSE"]){$GLOBALS["FORCE"]=true;}
	$pidtime="/etc/artica-postfix/pids/exec.squid.watchdog.php.start_watchdog.time";
	$pidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidtimeNTP="/etc/artica-postfix/pids/exec.squid.watchdog.php.start_watchdog.ntp.time";
	
	$unix=new unix();
	$pid=$unix->get_pid_from_file($pidFile);
	if($unix->process_exists($pid)){
		$pptime=$unix->PROCCESS_TIME_MIN($pid,10);
		if($GLOBALS["VERBOSE"]){echo "Process already running PID $pid since {$pptime}Mn\n";}
		return;}
	
	@file_put_contents($pidFile, getmypid());
	$time=$unix->file_time_min($pidtime);
	$sock=new sockets();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$NtpdateAD=intval($sock->GET_INFO("NtpdateAD"));
	$EnableFailover=$sock->GET_INFO("EnableFailover");
	$GLOBALS["EnableFailover"]=$sock->GET_INFO("EnableFailover");
	$SQUIDEnable=trim($sock->GET_INFO("SQUIDEnable"));
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}	
	if(!is_numeric($EnableFailover)){$EnableFailover=1;}
	
	
	
	
	if(!is_file("/usr/share/squid3/icons/silk/bigshield-256.png")){
		@copy("/usr/share/artica-postfix/img/bigshield-256.png","/usr/share/squid3/icons/silk/bigshield-256.png");
	}
	
	if(!is_file("/usr/share/squid3/icons/silk/logo-artica-64.png")){
		@copy("/usr/share/artica-postfix/img/logo-artica-64.png","/usr/share/squid3/icons/silk/logo-artica-64.png");
	}
	
	$articafiles=$unix->SquidPHPFiles();

	
	
	while (list ($num, $filename) = each ($articafiles) ){
		$filepath="/usr/share/artica-postfix/$num";
		@chmod($filepath,0755);
		@chown($filepath,"squid");
		@chgrp($filepath,"squid");
	
	}
	
	@unlink($pidtime);
	@file_put_contents($pidtime,time());
	Events("* * * * * * * * * * * * * * * * START WATCHDOG * * * * * * * * * * * * * * * *");
	if($SQUIDEnable==0){die();}
	Events("Testing Memory");
	CHECK_MEMORY_USE();
	PARANOID_MODE_CLEAN();
	
	Events("Testing Active Directory");
	
	TEST_ACTIVE_DIRECTORY();
	Events("Testing Active Directory Emergency....");
	CheckActiveDirectoryEmergency();
	
	Events("Testing port....");
	TEST_PORT();
	Events("ALL_STATUS");
	ALL_STATUS(true);

	
	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
	$MonitConfig["EnableFailover"]=$EnableFailover;
	$MonitConfig=watchdog_config_default($MonitConfig);
	$unix->chmod_func(0755, "/etc/artica-postfix/settings/Daemons/*");
	@mkdir("{$GLOBALS["ARTICALOGDIR"]}/squid/mysql-failed",0755,true);
	$unix->chown_func("squid","squid","{$GLOBALS["ARTICALOGDIR"]}/squid/mysql-failed");
	if(!$GLOBALS["VERBOSE"]){if($time<$MonitConfig["MIN_INTERVAL"]){return;}}
	
	$STAMP_MAX_RESTART_TIME=$unix->file_time_min($GLOBALS["STAMP_MAX_RESTART"]);
	if($STAMP_MAX_RESTART_TIME>60){@unlink($GLOBALS["STAMP_MAX_RESTART"]);}

	
	//Events("Start: ". basename($pidtime).":{$time}Mn / {$MonitConfig["MIN_INTERVAL"]}Mn STAMP_MAX_RESTART_TIME={$STAMP_MAX_RESTART_TIME}Mn");
	
	
	if(!is_file("/etc/artica-postfix/SQUID_TEMPLATE_DONE")){
		mysql_admin_mysql(1, "SQUID_TEMPLATE_DONE: No such file, launch build template action...", null,__FILE__,__LINE__);
		shell_exec("$nohup $php ".dirname(__FILE__)."/exec.squid.php --tpl-save >/dev/null 2>&1 &");
		
	}
	
	
	
	
	$GLOBALS["ALL_SCORES"]=0;
	
	$pid=SQUID_PID();
	$processtime=$unix->PROCCESS_TIME_MIN($pid);
	if(!$GLOBALS["FORCE"]){
		if($processtime<2){return;}
	}
	
	
	
	verify_var_log();
	
	$GLOBALS["ALL_SCORES_WHY"][]="score: {$GLOBALS["ALL_SCORES"]} after START";
	Checks_mgrinfos($MonitConfig,true);
	ntlmauthenticator();
	$GLOBALS["ALL_SCORES_WHY"][]="score: {$GLOBALS["ALL_SCORES"]} after ntlmauthenticator()";
	CheckOldCachesLog();
	$GLOBALS["ALL_SCORES_WHY"][]="score: {$GLOBALS["ALL_SCORES"]} after CheckOldCachesLog()";
	DeletedCaches();
	$GLOBALS["ALL_SCORES_WHY"][]="score: {$GLOBALS["ALL_SCORES"]} after DeletedCaches()";
	caches_center(true);
	$GLOBALS["ALL_SCORES_WHY"][]="score: {$GLOBALS["ALL_SCORES"]} after caches_center()";
	squid_stores_status();
	$GLOBALS["ALL_SCORES_WHY"][]="score: {$GLOBALS["ALL_SCORES"]} after squid_stores_status()";
	squid_mem_status();
	$GLOBALS["ALL_SCORES_WHY"][]="score: {$GLOBALS["ALL_SCORES"]} after squid_mem_status()";
	squid_memory_monitor();
	$GLOBALS["ALL_SCORES_WHY"][]="score: {$GLOBALS["ALL_SCORES"]} after squid_memory_monitor()";
	caches_size();
	$GLOBALS["ALL_SCORES_WHY"][]="score: {$GLOBALS["ALL_SCORES"]} after caches_size()";
	
	
	
	if($MonitConfig["watchdog"]==0){
		if($GLOBALS["VERBOSE"]){echo "Watchdog is disabled...\n";}
		counters(true);
		return;
	}
	
	if($processtime<5){return;}
	if($GLOBALS["VERBOSE"]){echo "Check DefaultRoute\n";}
	DefaultRoute();
	$GLOBALS["ALL_SCORES_WHY"][]="score: {$GLOBALS["ALL_SCORES"]} after DefaultRoute()";
	if($GLOBALS["VERBOSE"]){echo "Check UFDB\n";}
	Events("CheckUFDBGuardLocalThreads....");
	CheckUFDBGuardLocalThreads();
	Events("CheckUFDBGuardConfig....");
	CheckUFDBGuardConfig();
	Events("CheckUFDBGuardPort....");
	CheckUFDBGuardPort();
	$GLOBALS["ALL_SCORES_WHY"][]="score: {$GLOBALS["ALL_SCORES"]} after CheckUFDBGuardConfig()";
	if($GLOBALS["VERBOSE"]){echo "PING_GATEWAY()\n";}
	Events("PING_GATEWAY....");
	PING_GATEWAY();
	$GLOBALS["ALL_SCORES_WHY"][]="score: {$GLOBALS["ALL_SCORES"]} after PING_GATEWAY()";
	if($GLOBALS["VERBOSE"]){echo "SwapWatchdog()\n";}
	Events("SwapWatchdog....");
	SwapWatchdog();
	$GLOBALS["ALL_SCORES_WHY"][]="score: {$GLOBALS["ALL_SCORES"]} after SwapWatchdog()";
	if($GLOBALS["VERBOSE"]){echo "Checks_Winbindd()\n";}
	Events("Checks_Winbindd....");
	Checks_Winbindd();
	if($GLOBALS["VERBOSE"]){echo "CheckStoreDirs()\n";}
	$GLOBALS["ALL_SCORES_WHY"][]="score: {$GLOBALS["ALL_SCORES"]} after Checks_Winbindd()";
	Events("CheckStoreDirs....");
	CheckStoreDirs();
	
	Events("CHECK_SQUID_EXTERNAL_LDAP()....");
	CHECK_SQUID_EXTERNAL_LDAP();
	
	
	if($GLOBALS["VERBOSE"]){echo "MemBoosters()\n";}
	$GLOBALS["ALL_SCORES_WHY"][]="score: {$GLOBALS["ALL_SCORES"]} after CheckStoreDirs()";
	Events("MemBoosters....");
	MemBoosters();
	$GLOBALS["ALL_SCORES_WHY"][]="score: {$GLOBALS["ALL_SCORES"]} after MemBoosters()";
	if($GLOBALS["VERBOSE"]){echo "SwapCache()\n";}
	Events("SwapCache....");
	SwapCache($MonitConfig);
	$GLOBALS["ALL_SCORES_WHY"][]="score: {$GLOBALS["ALL_SCORES"]} after SwapCache()";
	Events("MaxSystemLoad....");
	MaxSystemLoad($MonitConfig);
	$GLOBALS["ALL_SCORES_WHY"][]="score: {$GLOBALS["ALL_SCORES"]} after MaxSystemLoad()";
	
	
	
	if($GLOBALS["VERBOSE"]){echo "CheckAvailableSize()\n";}
	Events("CheckAvailableSize....");
	CheckAvailableSize();
	$GLOBALS["ALL_SCORES_WHY"][]="score: {$GLOBALS["ALL_SCORES"]} after CheckAvailableSize()";
	Events("FailOverCheck....");
	FailOverCheck();
	Events("DNSCACHE....");
	DNSCACHE();
	Events("cache_center_status....");
	cache_center_status();
	
	
	if($GLOBALS["VERBOSE"]){echo "counters()\n";}
	counters(true);
	
	if($NtpdateAD==1){
		$pidtimeNTPT=$unix->file_time_min($pidtimeNTP);
		if($pidtimeNTPT>120){
			if($GLOBALS["VERBOSE"]){echo "/usr/share/artica-postfix/exec.kerbauth.php --ntpdate\n";}
			shell_exec("$nohup $php /usr/share/artica-postfix/exec.kerbauth.php --ntpdate >/dev/null 2>&1 &");
			@unlink($pidtimeNTP);
			@file_put_contents($pidtimeNTP, time());
		}
	}
	Events("* * * * * * * * * * * * * * * * END WATCHDOG * * * * * * * * * * * * * * * *");
	
}

function CRON_NECESSARIES(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	
	
	if(!is_file("/etc/cron.d/squid-repos")){
		$CRON[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin:/usr/share/artica-postfix/bin";
		$CRON[]="MAILTO=\"\"";
		$CRON[]="* 0,2,4,6,8,10,12,14,16,18,20,22 * * *\troot\t$php5 /usr/share/artica-postfix/exec.web-community-filter.php --squid-repo >/dev/null 2>&1 >/dev/null 2>&1";
		$CRON[]="";
		@file_put_contents("/etc/cron.d/squid-repos", @implode("\n", $CRON));
		@chmod("/etc/cron.d/squid-repos",0644);
		shell_exec("/etc/init.d/cron reload");
		$CRON=array();
	}
	
	if(!is_file("/etc/cron.d/squid-chown")){
		$CRON[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin:/usr/share/artica-postfix/bin";
		$CRON[]="MAILTO=\"\"";
		$CRON[]="30 5 * * *\troot\t$php5 /usr/share/artica-postfix/exec.squid.chown.caches.php >/dev/null 2>&1";
		$CRON[]="";
		@file_put_contents("/etc/cron.d/squid-chown", @implode("\n", $f));
		@chmod("/etc/cron.d/squid-chown",0644);
		shell_exec("{$GLOBALS["nohup"]} /etc/init.d/cron reload >/dev/null 2>&1 &");
		$CRON=array();
	}
	
	
	
	
}


function MaxSystemLoad($MonitConfig){

	
	
	
	$sock=new sockets();
	$unix=new unix();
	$shutdown=$unix->find_program("shutdown");
	$nohup=$unix->find_program("nohup");
	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
	$MonitConfig=watchdog_config_default($MonitConfig);
	
	
	if($MonitConfig["LOAD_TESTS"]==0){return;}
	
	
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];
	
	$LOAD_WARNING=$MonitConfig["LOAD_WARNING"];
	$LOAD_MAX=$MonitConfig["LOAD_MAX"];
	$LOAD_MAX_ACTION=$MonitConfig["LOAD_MAX_ACTION"];
	
	
	$array_mem=getSystemMemInfo();
	$MemFree=$array_mem["MemFree"];
	$MemFree=round($MemFree/1024);
	
	
	if($MonitConfig["MinFreeMem"]>0){
		$report=$unix->ps_mem_report();
		if($MemFree<$MonitConfig["MinFreeMem"]){
			squid_admin_mysql(2, "No memory free: {$MemFree}MB, Need at least {$MonitConfig["MinFreeMem"]}MB",$report,__FILE__,__LINE__);
			if($MonitConfig["MaxLoadFailOver"]==1){
				$GLOBALS["ALL_SCORES"]++;
				$GLOBALS["ALL_SCORES_WHY"][]="No memory free: {$MemFree}MB, Need at least {$MonitConfig["MinFreeMem"]}MB";
				FailOverDown("No mem free");
				return;
			}
				
		}
	}	
	

if($internal_load>$LOAD_MAX){
		$report=$unix->ps_mem_report();
		system_is_overloaded();
		$GLOBALS["ALL_SCORES_WHY"][]="Overloaded system Load: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, system {$GLOBALS["SYSTEM_INTERNAL_MEMM"]}MB memory free";
		if($LOAD_MAX_ACTION=="reboot"){
			squid_admin_mysql(0, "Overloaded system Load: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}: reboot the server", $report,__FILE__,__LINE__);
			shell_exec("$nohup $shutdown -r -t 5 >/dev/null 2>&1 &");
			return;
		}
		
		if($LOAD_MAX_ACTION=="failover"){
			squid_admin_mysql(0, "Overloaded system Load: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}: [action=failover]",$report,__FILE__,__LINE__);
			FailOverDown("Overloaded");
			return;
		}

		
		if($LOAD_MAX_ACTION=="restart"){
			squid_admin_mysql(0, "Overloaded system Load: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}: [action=restart]", $report,__FILE__,__LINE__);
			restart_squid(true);
			return;
		}		
		
		squid_admin_mysql(0, "Overloaded system Load: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}: Overloaded system [action=none]", $report,__FILE__,__LINE__);
		
	}
	
	
	
	
	if($MonitConfig["MaxLoad"]>0){
		$report=$unix->ps_mem_report();
		if($internal_load>$MonitConfig["MaxLoad"]){
			if($MonitConfig["MaxLoadFailOver"]==1){
				$GLOBALS["ALL_SCORES"]++;
				system_is_overloaded();
				squid_admin_mysql(2, "Overloaded system Load: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}", "System reach {$MonitConfig["MaxLoadFailOver"]} value",__FILE__,__LINE__);
				$GLOBALS["ALL_SCORES_WHY"][]="Overloaded system Load: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, system {$GLOBALS["SYSTEM_INTERNAL_MEMM"]}MB memory free";
			}
			
			if($MonitConfig["MaxLoadReboot"]==1){
				squid_admin_mysql(0, "Overloaded system Load: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}: reboot the server", "Watchdog system, reboot the server",__FILE__,__LINE__);
				$unix=new unix();
				$shutdown=$unix->find_program("shutdown");
				$nohup=$unix->find_program("nohup");
				shell_exec("$nohup $shutdown -r -t 5 >/dev/null 2>&1 &");
				return;
			}
		}
		
	}

	

}

function swap_state(){
	$unix=new unix();
	$caches=$unix->SQUID_CACHE_FROM_SQUIDCONF();
	while (list ($directory, $type) = each ($caches)){
		if(strtolower($type)=="rock"){continue;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." scanning cache $directory\n";}
		foreach (glob("$directory/swap.*") as $filename) {
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." removing $filename\n";}
			@unlink($filename);	
		}
	}
}

function CheckOldCachesLog(){
	
	@mkdir("/home/squid/cache-logs",0755,true);
	$unix=new unix();
	foreach (glob("/var/log/squid/cache.log.*") as $filename) {
		if($GLOBALS["VERBOSE"]){echo "Move $filename to /home/squid/cache-logs\n";}
		Events("Move $filename to /home/squid/cache-logs");
		@copy($filename, "/home/squid/cache-logs/".basename($filename));
		@unlink($filename);
	}
	
	foreach (glob("/home/squid/cache-logs/*") as $filename) {
		$ext=$unix->file_ext($filename);
		if(is_numeric($ext)){
			Events("Compress $filename to $filename.gz");
			if($unix->compress($filename, "$filename.gz")){@unlink($filename);}
			continue;
		}
		
		if($ext=="gz"){
			$time=$unix->file_time_min($filename);
			if($GLOBALS["VERBOSE"]){echo "$filename  = {$time}Mn\n";}
			if($time>4320){
				Events("Remove $filename (exceed 3 days on disk...)");
				@unlink($filename);
				continue;
			}
		}
		
	}
	
	
	
	if($GLOBALS["VERBOSE"]){echo "CheckOldCachesLog:: END\n";}
	
	
	
	
}

function UFDBGUARD_PID_NUM(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/urlfilterdb/ufdbguardd.pid");
	if($unix->process_exists($pid)){
		$cmdline=trim(@file_get_contents("/proc/$pid/cmdline"));
		if(!preg_match("#ufdbcatdd#", $cmdline)){return $pid;}
	}
	$Masterbin=$unix->find_program("ufdbguardd");
	if(!is_file($Masterbin)){return 0;}
	return $unix->PIDOF($Masterbin);
}

function CheckUFDBGuardLocalThreads($ForcePid=false){
	if($GLOBALS["VERBOSE"]){echo "Ufdbguard CheckUFDBGuardLocalThreads()\n";}
	if($GLOBALS["FORCE"]){$ForcePid=false;}
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidtimeNTP="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".ntp.time";
	
	$unix=new unix();
	$sock=new sockets();
	
	if($ForcePid){
		$pid=$unix->get_pid_from_file($pidFile);
		if($unix->process_exists($pid)){
			if($unix->PROCCESS_TIME_MIN($pid,10)<2){return;}
		}
	}

	@file_put_contents($pidFile, getmypid());
	$time=$unix->file_time_min($pidtime);
	if(!$GLOBALS["VERBOSE"]){ if($time<1){return;} }
	
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	
	$EnableUfdbGuard=intval($sock->EnableUfdbGuard());
	if($EnableUfdbGuard==0){if($GLOBALS["VERBOSE"]){echo "EnableUfdbGuard = $EnableUfdbGuard, return...\n";}return;}
	
	$pid=UFDBGUARD_PID_NUM();
	
	if($GLOBALS["VERBOSE"]){echo "Ufdbguard PID:$pid\n";}
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["VERBOSE"]){echo "Ufdbguard not running...\n";}
	}
	
	$MaxThreads=140;
	$CurrentThreads=$unix->PROCESS_SOCKETS_NUM($pid);
	if($CurrentThreads>=$MaxThreads){
		squid_admin_mysql(0, "Web filtering, Max threads limit reached - $CurrentThreads - restarting", "The Web filtering service threads are freeze, the dameon will be restarted",__FILE__,__LINE__);
		shell_exec("/etc/init.d/ufdb restart --watchdog");
	}
	
}

function CheckUFDBGuardPort(){
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	$unix=new unix();
	
	
	$time=$unix->file_time_min($pidtime);
	if($time<5){return;}
	
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	
	
	
	$sock=new sockets();
	$EnableUfdbGuard=intval($sock->EnableUfdbGuard());
	
	if($GLOBALS["VERBOSE"]){echo "EnableUfdbGuard = $EnableUfdbGuard\n";}
	if($EnableUfdbGuard==0){return;}
	
	if(!CheckUFDBGuardConfigIsClient()){
		if($GLOBALS["VERBOSE"]){echo "Client Not detected in config\n";}
		return;
	}
	
	$datas=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
	if(!isset($datas["UseRemoteUfdbguardService"])){$datas["UseRemoteUfdbguardService"]=0;}
	if(!is_numeric($datas["remote_port"])){$datas["remote_port"]=3977;}
	
	if($datas["remote_server"]=="127.0.0.1"){$datas["UseRemoteUfdbguardService"]=0;}
	
	if($datas["UseRemoteUfdbguardService"]==0){
		$LOCAL_CONF=CheckUFDBGuardConfigLocalIPPort();
		$datas["remote_server"]=$LOCAL_CONF[0];
		$datas["remote_port"]=$LOCAL_CONF[1];
	}
	
	
	
	$fsock = fsockopen($datas["remote_server"], $datas["remote_port"], $errno, $errstr, 5);
	if ( ! $fsock ){
		
		if($datas["UseRemoteUfdbguardService"]==1){
			squid_admin_mysql(0,"Failed to connect to the Webfiltering service {$datas["remote_server"]}:{$datas["remote_port"]} [action=notify]",
			"{$datas["remote_server"]}:{$datas["remote_port"]} Error number $errno $errstr",__FILE__,__LINE__);
			return;
		}
		
		squid_admin_mysql(0,"Failed to connect to the Webfiltering service {$datas["remote_server"]}:{$datas["remote_port"]} [action=restart]",
		"{$datas["remote_server"]}:{$datas["remote_port"]} Error number $errno $errstr\nThe Webfiltering service will be restarted",__FILE__,__LINE__);
		shell_exec("/etc/init.d/ufdb restart --fatal-error");
	}
	
	
	
	
	
}

function CheckUFDBGuardConfigLocalIPPort(){
	$port=0;
	$interface=null;
	$f=explode("\n",@file_get_contents("/etc/squid3/ufdbGuard.conf"));
	
	while (list ($index, $line) = each ($f)){
		$line=trim($line);
		if($line==null){continue;}
		if(preg_match("#^port\s+([0-9]+)#", $line,$re)){$port=$re[1];continue;}
		if(preg_match("#^interface\s+(.+)#", $line,$re)){$interface=trim($re[1]);continue;}
		if($port>0){ if($interface<>null){break;} }
		
	}
	if($interface==null){$interface="127.0.0.1";}
	if($port==0){$port=3977;}
	if($interface=="all"){$interface="127.0.0.1";}
	return array($interface,$port);
	
}

function CheckUFDBGuardConfigIsClient(){
	
	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	while (list ($index, $line) = each ($f)){
		$line=trim($line);
		if($line==null){continue;}
		if(preg_match("#^url_rewrite_program.*?ufdbgclient#", $line)){return true; }
	
	}
}


function CheckUFDBGuardConfig($ForcePid=false){
	if($GLOBALS["FORCE"]){$ForcePid=false;}
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidtimeNTP="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".ntp.time";
	
	$unix=new unix();
	$sock=new sockets();
	
	if($ForcePid){
		$pid=$unix->get_pid_from_file($pidFile);
		if($unix->process_exists($pid)){
			if($unix->PROCCESS_TIME_MIN($pid,10)<2){return;}
		}
	}
	
	@file_put_contents($pidFile, getmypid());
	$time=$unix->file_time_min($pidtime);
	if($time<2){return;}
	
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	
	$EnableUfdbGuard=intval($sock->EnableUfdbGuard());
	if($EnableUfdbGuard==0){if($GLOBALS["VERBOSE"]){echo "EnableUfdbGuard = $EnableUfdbGuard, return...\n";}return;}


	$datas=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));	
	if(!isset($datas["UseRemoteUfdbguardService"])){$datas["UseRemoteUfdbguardService"]=0;}
	if(!is_numeric($datas["remote_port"])){$datas["remote_port"]=3977;}
	
	
	$Detected=CheckUFDBGuardConfigIsClient();
	
	
	
	
	if($Detected){
		if($datas["UseRemoteUfdbguardService"]==1){
			$fsock = fsockopen($datas["remote_server"], $datas["remote_port"], $errno, $errstr, 5);
			if ( ! $fsock ){
				@fclose($fsock);
				squid_admin_mysql(0,"Fatal, failed to connect to the remote Webfiltering service",
				"{$datas["remote_server"]}:{$datas["remote_port"]} Error number $errno $errstr",__FILE__,__LINE__);
				shell_exec("/etc/init.d/ufdb-client stop --watchdog");
				
			}else{
				@fclose($fsock);
				if($GLOBALS["VERBOSE"]){echo "`{$datas["remote_server"]}:{$datas["remote_port"]}` OK\n";return;}
			}
		}
		
		return;
	}
	
	

	
	if($datas["UseRemoteUfdbguardService"]==1){
		$fsock = fsockopen($datas["remote_server"], $datas["remote_port"], $errno, $errstr, 5);
		if ( ! $fsock ){
			squid_admin_mysql(0,"Fatal, failed to connect to the remote Webfiltering service",
			"{$datas["remote_server"]}:{$datas["remote_port"]} Error number $errno $errstr",__FILE__,__LINE__);
			@fclose($fsock);
			return;
		}
		
	}
	if($GLOBALS["VERBOSE"]){echo "`{$datas["remote_server"]}:{$datas["remote_port"]}` OK\n";}
	@fclose($fsock);
	
	
	if(!$Detected){
		shell_exec("/etc/init.d/ufdb-client start --watchdog");
	}
	
	
	
	
}


function CHECK_HA_MASTER_UP($MustUp=false){
	$sock=new sockets();
	$unix=new unix();
	$users=new settings_inc();
	if(!$users->CORP_LICENSE){if($GLOBALS["VERBOSE"]){echo "License error\n";}return;		}
	$MAIN=unserialize(base64_decode($sock->GET_INFO("HASettings")));
	if(!isset($MAIN["SLAVE"])){if($GLOBALS["VERBOSE"]){echo "I'm the slave,nothing to do.\n";}return;}
	
	include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
	$proto="http";
	if($MAIN["SLAVE_SSL"]==1){$proto="https";}
	$SLAVE_PORT=$MAIN["SLAVE_PORT"];
	$IP=$MAIN["SLAVE"];
	$uri="$proto://$IP:$SLAVE_PORT/nodes.listener.php";
	$Hooked_interface=$MAIN["eth"];
	$nic=new system_nic($Hooked_interface);
	$ProductionIP=$nic->ucarp_vip;
	$WgetBindIpAddress=$MAIN["WgetBindIpAddress"];
	$ifconfig=$unix->find_program("ifconfig");
	
	exec("$ifconfig $Hooked_interface:ucarp 2>&1",$results);
	$ProductionIP_regex=str_replace(".", "\.", $ProductionIP);
	
	$available=false;
	while (list ($num, $line) = each ($results) ){
		if(preg_match("#$ProductionIP_regex#", $line)){
			$available=true;
			break;
		}
	}
	
	$LL[]="";
	$LL[]="URI: $uri";
	$LL[]= "Hooked Interface.: $Hooked_interface";
	$LL[]= "Main Interface...: $ProductionIP";
	$LL[]= "Bind Interface...: $WgetBindIpAddress";
	$LL[]= "Local available..: $available";
	$LL[]= "Must UP..........: $MustUp";
	
	if($GLOBALS["VERBOSE"]){
		echo @implode("\n", $LL);
		
	
	}	
	
	if(!$available){
		if($MustUp){
			squid_admin_mysql(1, "FailOver: Master must be UP: Notify slave $IP to DOWN","Uri:$uri\nScript: /usr/share/ucarp/vip-$Hooked_interface-up.sh\n".@implode("\n", $LL),__FILE__,__LINE__);
			shell_exec("/usr/share/ucarp/vip-$Hooked_interface-up.sh");
			$curl=new ccurl($uri,true,$WgetBindIpAddress,true);
			$curl->parms["UCARP_DOWN"]=$ProductionIP;
			if(!$curl->get()){
					squid_admin_mysql(0, "FailOver: Unable to notify slave $IP for order [DOWN]","Error:$curl->error\n".
					$uri.@implode("\n", $LL),__FILE__,__LINE__);}
			return;
		}
	}
	
	if($available){
		if($MustUp){
			$curl=new ccurl($uri,true,$WgetBindIpAddress,true);
			$curl->parms["UCARP_DOWN"]=$ProductionIP;
			if($GLOBALS["VERBOSE"]){"Notify slave down...\n";}
			if(!$curl->get()){squid_admin_mysql(0, "FailOver: Unable to notify slave $IP for order [DOWN]","Error:$curl->error\n".$uri.@implode("\n", $LL),__FILE__,__LINE__);}
			if(preg_match("#<RESULTS>(.*?)</RESULTS>#is", $curl->data,$re)){
				if($re[1]=="DOWN_OK"){squid_admin_mysql(1, "FailOver: Master is UP: slave $IP as been notified to be DOWN".@implode("\n", $LL),$uri,__FILE__,__LINE__);}
			}
			return;
		}
	}

	if($GLOBALS["VERBOSE"]){echo "Nothing to do....\n";}
	
}






function CHEK_SYSTEM_LOAD(){
	$sock=new sockets();
	$unix=new unix();
	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));	
	$MonitConfig=watchdog_config_default($MonitConfig);
	if($MonitConfig["MaxLoad"]==0){return;}
	if(!function_exists("sys_getloadavg")){return;}
	
	$array_load=sys_getloadavg();
	$internal_load=intval($array_load[0]);
	
	if($internal_load < $MonitConfig["MaxLoad"] ){return;}
		
	$notifymessage="System load $internal_load exceed {$MonitConfig["MaxLoad"]}";
	RESTARTING_SQUID_WHY($MonitConfig, $notifymessage);
	
	if($MonitConfig["MaxLoadFailOver"]==1){
		FailOverDown($notifymessage);
	}
	
	if($MonitConfig["MaxLoadReboot"]==0){return;}
	REBOOTING_SYSTEM();
	
	
}




function REBOOTING_SYSTEM(){
	
	$sock=new sockets();
	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
	$MonitConfig=watchdog_config_default($MonitConfig);
	$unix=new unix();
	
	$timex=$unix->file_time_min($GLOBALS["STAMP_REBOOT"]);
	if($timex < $MonitConfig["REBOOT_INTERVAL"]){
		Events("Cannot reboot, need to wait {$MonitConfig["REBOOT_INTERVAL"]}mn, current is {$timex}mn");
		return;
	}
	
	
	squid_admin_mysql(0,"Reboot the system", __FUNCTION__, __FILE__, __LINE__, "proxy");
	$shutdown=$unix->find_program("shutdown");
	@unlink($GLOBALS["STAMP_REBOOT"]);
	@file_put_contents($GLOBALS["STAMP_REBOOT"], time());
	sleep(5);
	shell_exec("$shutdown -rF now");	
	
}

function CICAP_PID_PATH(){
	return '/var/run/c-icap/c-icap.pid';
}

function CICAP_PID_NUM(){
	$filename=CICAP_PID_PATH();
	$pid=trim(@file_get_contents($filename));
	$unix=new unix();
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF($unix->find_program("c-icap"));
}

function reload_squid($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	$TimeFile="/etc/artica-postfix/pids/reloadsquid.time";
	$PidFile="/etc/artica-postfix/pids/reloadsquid.pid";
	
	
	if(!is_file($squidbin)){
		if($GLOBALS["OUTPUT"]){echo "Reloading.......: Squid-cache, not installed\n";}
		shell_exec("/etc/init.d/dnsmasq restart");
		return;
	}			
			
	
	
	
	if(!$aspid){
		
		$pid=$unix->get_pid_from_file($PidFile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$TimeMin=$unix->PROCCESS_TIME_MIN($pid);
			build_progress_reload("Already task running PID $pid since {$TimeMin}mn",100);
			return;
		}
		
	}	
	@file_put_contents($PidFile, getmypid());
	
	$SquidCacheReloadTTL=$sock->GET_INFO("SquidCacheReloadTTL");
	if(!is_numeric($SquidCacheReloadTTL)){$SquidCacheReloadTTL=10;}
	
	$pid=SQUID_PID();
	if(!$unix->process_exists($pid)){start_squid(true);return;}
	$TimeMin=$unix->PROCCESS_TIME_MIN($pid);
	$php5=$unix->LOCATE_PHP5_BIN();
	echo "Reloading.......: ".date("H:i:s")." Building mime type..\n";
	shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --mime");
	
	if($GLOBALS["FORCE"]){
		squid_admin_mysql(1, "Reload::{$GLOBALS["BY_OTHER_SCRIPT"]} Reloading + FORCE", null,__FILE__,__LINE__);
		echo "Reloading.....: ".date("H:i:s")." Squid-cache, Force enabled...\n";
	}
	
	if(!$GLOBALS["FORCE"]){
		if($TimeMin<$SquidCacheReloadTTL){
			squid_admin_mysql(1, "Reload::{$GLOBALS["BY_OTHER_SCRIPT"]} Aborted need at least {$SquidCacheReloadTTL}mn", null,__FILE__,__LINE__);
			build_progress_reload("Aborted need at least {$SquidCacheReloadTTL}mn",100);
			echo "Reloading.......: ".date("H:i:s")." Squid-cache, Reload squid PID $pid aborted, need at least {$SquidCacheReloadTTL}mn current {$TimeMin}mn\n";
			return;
		}
	}	
	
	
	
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	$suffix=get_action_script_source();
	
	build_progress_reload("Reloading PID $pid",10);
	echo "Reloading.....: ".date("H:i:s")." Reloading proxy service PID:$pid running since {$TimeMin}Mn $suffix...\n";
	squid_admin_mysql(2, "Reloading squid service by [{$GLOBALS["BY_OTHER_SCRIPT"]}] PID:$pid running since {$TimeMin}Mn $suffix",null,__FILE__,__LINE__);
	
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$chmod=$unix->find_program("chmod");
	$pgrep=$unix->find_program("pgrep");
	$kill=$unix->find_program("kill");
	$executed=null;
	
	
	$EnableTransparent27=intval($sock->GET_INFO("EnableTransparent27"));
	
	$SystemInfoCache="/etc/squid3/squid_get_system_info.db";
	$TimeMin=$unix->file_time_min($TimeFile);
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	$CicapEnabled=intval($sock->GET_INFO("CicapEnabled"));
	$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	
	@unlink($SystemInfoCache);
	build_progress_reload("Reloading PID $pid",15);
	if(!is_file("/etc/squid3/url_rewrite_program.deny.db")){ @file_put_contents("/etc/squid3/url_rewrite_program.deny.db", ""); }
	$ssltrd=$unix->squid_locate_generic_bin("ssl_crtd");
	if(is_file($ssltrd)){
		if(!is_dir("/var/lib/squid/session/ssl/ssl_db")){
			@mkdir("/var/lib/squid/session/ssl",0755,true);
			shell_exec("$ssltrd -c -s /var/lib/squid/session/ssl/ssl_db >/dev/null 2>&1");
			$unix->chown_func("squid", "squid","/var/lib/squid/session/ssl/ssl_db/*");
		}
	}
	
	$GLOBALS["SQUIDBIN"]=$unix->LOCATE_SQUID_BIN();
	echo "Reloading.....: ".date("H:i:s")." Squid-cache, Checking transparent mode..\n";
	
	build_progress_reload("Reloading PID $pid",20);
	

	
	build_progress_reload("Check files and security",22);
	start_prepare();
	
	
	build_progress_reload("Reloading PID $pid",25);
	if($EnableTransparent27==1){
		if(!is_file("/etc/init.d/squid-nat")){
			shell_exec("$php5 /usr/share/artica-postfix/exec.initslapd.php --squid-nat");
		}
		echo "Reloading......: ".date("H:i:s")." Squid-cache, Reloading squid-nat\n";
		shell_exec("/etc/init.d/squid-nat reload");
	
	}
	
	build_progress_reload("Reloading PID $pid",30);
	if($CicapEnabled==1){
		echo "Reloading......: ".date("H:i:s")." Squid-cache, Reloading C-ICAP service\n";
		shell_exec("$nohup /etc/init.d/c-icap reload >/dev/null 2>&1 ");
	}
	
	
	build_progress_reload("Reloading PID $pid",35);
	if($EnableKerbAuth==1){
		echo "Reloading......: ".date("H:i:s")." Squid-cache, Checks winbind privileges\n";
		shell_exec("$php5 /usr/share/artica-postfix/exec.winbindd.php --privs-squid");
	}
	
	build_progress_reload("Reloading PID $pid",40);
	echo "Reloading.....: ".date("H:i:s")." Squid-cache, Checks auth-tail\n";
	shell_exec("$nohup /etc/init.d/auth-tail restart >/dev/null 2>&1 &");

	

	
	if(!is_file($GLOBALS["SQUIDBIN"])){ 
		build_progress_reload("Reloading PID {failed}",100);
		return; 
	}
	
	
	
	echo "Reloading.....: ".date("H:i:s")." Squid-cache, With binary {$GLOBALS["SQUIDBIN"]} PID $pid\n";
	
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$file=basename($trace[1]["file"]);
	$function=$trace[1]["function"];$line=$trace[1]["line"];$called="Called by $function() from line $line";}}
	
	$results=array();
	build_progress_reload("Reloading PID $pid",45);
	$unix->TCP_TUNE_SQUID_DEFAULT();
	
	echo "Reloading.....: ".date("H:i:s")." Squid-cache, Reloading artica-status\n";
	shell_exec("$nohup $php5 /etc/init.d/artica-status reload >/dev/null 2>&1 &");
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.clean.logs.php --squid-caches --force >/dev/null 2>&1 &");
	
	if($EnableRemoteStatisticsAppliance==1){
		echo "Reloading.....: ".date("H:i:s")." Squid-cache, Sends information to the Statistics Appliance...\n";
		shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.netagent.php >/dev/null 2>&1 &");
	}
	
	
	
	build_progress_reload("Reloading Main proxy service PID $pid",50);
	
	$sock=new sockets();
	exec("{$GLOBALS["SQUIDBIN"]} -f \"/etc/squid3/squid.conf\" -k reconfigure 2>&1",$pgrepArray);
	CHECK_WIFIDOG_IPTABLES_RULES();
	
	$sock=new sockets();
	$EnableTransparent27=intval($sock->GET_INFO("EnableTransparent27"));
	if($EnableTransparent27==1){
		build_progress("Reloading Proxy NAT service...",60);
		system("$nohup /etc/init.d/squid-nat reload --script=".basename(__FILE__)." >/dev/null 2>&1 &");
	}
	
	
	while (list ($num, $ligne) = each ($pgrepArray) ){
		if(preg_match("#^.*?\|\s+(.+)#", $ligne,$re)){
		echo "Reloading.....: ".date("H:i:s")." Squid-cache, {$re[1]}\n";
		}
	}
	build_progress_reload("Reloading PID $pid",100);
	

	
}

function external_acl_children_more(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		return;
	}

$SquidClientParams=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidClientParams")));

if(!is_numeric($SquidClientParams["external_acl_children"])){$SquidClientParams["external_acl_children"]=5;}
if(!is_numeric($SquidClientParams["external_acl_startup"])){$SquidClientParams["external_acl_startup"]=1;}
if(!is_numeric($SquidClientParams["external_acl_idle"])){$SquidClientParams["external_acl_idle"]=1;}


$external_acl_children=$SquidClientParams["external_acl_children"];
$external_acl_startup=$SquidClientParams["external_acl_startup"];
$external_acl_idle=$SquidClientParams["external_acl_idle"];

$external_acl_children=$external_acl_children+2;
$external_acl_startup=$external_acl_startup+2;
$external_acl_idle=$external_acl_idle+2;

squid_admin_mysql(2, "ACL Children: from {$SquidClientParams["external_acl_children"]}/{$SquidClientParams["external_acl_startup"]}/{$SquidClientParams["external_acl_idle"]} to $external_acl_children/$external_acl_startup/$external_acl_idle","",__FILE__,__LINE__);
$SquidClientParams["external_acl_children"]=$external_acl_children;
$SquidClientParams["external_acl_startup"]=$external_acl_startup;
$SquidClientParams["external_acl_idle"]=$external_acl_idle;
@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidClientParams", base64_encode(serialize($SquidClientParams)));
$php=$unix->LOCATE_PHP5_BIN();
shell_exec("$php /usr/share/artica-postfix/exec.squid.php --build --force");

}


function redirectors_more(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		return;
	}
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
	if(!isset($datas["url_rewrite_children_concurrency"])){$datas["url_rewrite_children_concurrency"]=2;}
	if(!isset($datas["url_rewrite_children_startup"])){$datas["url_rewrite_children_startup"]=5;}
	if(!isset($datas["url_rewrite_children_idle"])){$datas["url_rewrite_children_idle"]=5;}
	if(!isset($datas["url_rewrite_children_max"])){$datas["url_rewrite_children_max"]=30;}
	
	$datas["url_rewrite_children_startup"]=$datas["url_rewrite_children_startup"]+5;
	$datas["url_rewrite_children_idle"]=$datas["url_rewrite_children_idle"]+5;
	$datas["url_rewrite_children_max"]=$datas["url_rewrite_children_max"]+5;
	
	squid_admin_mysql(2, "WebFiltering increased Children To {$datas["url_rewrite_children_max"]}","",__FILE__,__LINE__);
	
	@file_put_contents("/etc/artica-postfix/settings/Daemons/ufdbguardConfig", base64_encode(serialize($datas)));
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php /usr/share/artica-postfix/exec.squid.php --build --force");	
	
}

function ntlmauthenticator(){
	$sock=new sockets();
	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
	$MonitConfig=watchdog_config_default($MonitConfig);
	$unix=new unix();
	$cacheFile="/usr/share/artica-postfix/ressources/logs/web/ntlmauthenticator.cache";
	if(!$unix->isSquidNTLM()){
		if($GLOBALS["VERBOSE"]){echo "NOT an NTLM proxy\n";}
		@unlink($cacheFile);
		return;
	}
	$ADDNNEW=false;
	$datas=explode("\n",$unix->squidclient("ntlmauthenticator"));
	$CPU_NUMBER=1;
	$ARRAY=array();
	if($GLOBALS["VERBOSE"]){echo "Watchdog enabled: {$MonitConfig["watchdog"]}\n";}
	
	
	while (list ($num, $ligne) = each ($datas) ){
		if(preg_match("#by kid([0-9]+)#", $ligne,$re)){
			$CPU_NUMBER=$re[1];
			continue;
		}
		
		if(preg_match("#number active: ([0-9]+) of ([0-9]+)#",$ligne,$re)){
			$Active=intval($re[1]);
			$Max=intval($re[2]);
			$prc=round(($Active/$Max)*100);
			$ARRAY[$CPU_NUMBER]=$prc;
		}
		
	}
	
	if(count($ARRAY)==0){return;}
	if($MonitConfig["watchdog"]==1){
		while (list ($CPU, $PRC) = each ($ARRAY) ){
			if($GLOBALS["VERBOSE"]){echo "CPU.$CPU = $PRC%\n";}
			Events("ntlmauthenticator: CPU.$CPU = $PRC%");
			$LOG[]="Instance on CPU $CPU is $PRC% used.";
			if($PRC>94){
				if($Max+5<151){
					$NewMax=$Max+5;
					$ADDNNEW=true;
					
					squid_admin_mysql(2,"NTLM Authenticator on CPU $CPU reach 95%",
					"Artica will increase your ntlm authenticator processes to $NewMax instances 
					per CPU and reload the Proxy service\r\nCurrent status:\r\n".@implode("\r\n", $LOG));
				}
			}
		}
	}
	
	
	if($ADDNNEW){
		if($NewMax>0){
			$squid=new squidbee();
			$SquidClientParams=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidClientParams")));
			$Old=$squid->auth_param_ntlm_children();
			$SquidClientParams["auth_param_ntlm_children"]=$NewMax;
			$SquidClientParams["auth_param_ntlm_startup"]=round($NewMax*0.2);
			
			@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidClientParams", base64_encode(serialize($SquidClientParams)));
			if(ntlmauthenticator_edit($NewMax)){
				$squid=$unix->LOCATE_SQUID_BIN();
				$php=$unix->LOCATE_PHP5_BIN();
				if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$file=basename($trace[1]["file"]);$function=$trace[1]["function"];$line=$trace[1]["line"];$called="Called by $function() from line $line";}}
				squid_admin_mysql(1, "Reconfiguring squid-cache Increase NTLM From $Old to $NewMax","$called");
				shell_exec("$php /usr/share/artica-postfix/exec.squid.php --build --force");
				
			}
		}
		
	}
	
	@file_put_contents($cacheFile, serialize($ARRAY));
	@chmod($cacheFile, 0755);
	
	
}

function ntlmauthenticator_edit($newvalue=0){
	if(!is_numeric($newvalue)){$newvalue=20;}
	if($newvalue<20){$newvalue=20;}
	
	$FOUND=false;
	$ARRAY=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	while (list ($index, $line) = each ($ARRAY) ){
		if(preg_match("#auth_param ntlm children ([0-9]+)#", $line,$re)){
			if($GLOBALS["VERBOSE"]){echo "{$ARRAY[$index]}\n";}
			$ARRAY[$index]=str_replace("children {$re[1]}", "children $newvalue", $ARRAY[$index]);
			if($GLOBALS["VERBOSE"]){echo "Change to {$ARRAY[$index]}\n";}
			$FOUND=true;
			break;
		}
		
	}
	
	if($FOUND){
		@file_put_contents("/etc/squid3/squid.conf", @implode("\n", $ARRAY));
		return true;
	}
	
}


function TEST_ACTIVE_DIRECTORY(){
	$ADD=true;
	$sock=new sockets();
	$watch=new squid_watchdog();
	$EnableKerbAuth=intval($sock->GET_INFO("EnableKerbAuth"));
	$CHECK_AD=intval($watch->MonitConfig["CHECK_AD"]);
	if($EnableKerbAuth==0){$ADD=false;}
	if($CHECK_AD==0){$ADD=false;}
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nice=$unix->EXEC_NICE();
	
	if(!is_file("/etc/init.d/artica-optimize")){
		shell_exec("nice $php5 /usr/share/artica-postfix/exec.sysctl.php --build >/dev/null 2>&1");
	}
	
	if(!is_file("/etc/cron.d/artica-ping-cloud")){
		$f=array();
		$f[]="MAILTO=\"\"";
		$f[]="15 0,2,4,6,8,10,12,14,16,18,20,22 * * * *  root $nice $php5 /usr/share/artica-postfix/exec.web-community-filter.php --bycron >/dev/null 2>&1";
		$f[]="";
		@file_put_contents("/etc/cron.d/artica-ping-cloud", @implode("\n", $f));
		
	}
	
	
	if(!$ADD){
		if(is_file("/etc/cron.d/artica-ads-watchdog")){
			@unlink("/etc/cron.d/artica-ads-watchdog");
		}
		return;
	}

	
}

function TEST_PORT($aspid=false){
	$sock=new sockets();
	$unix=new unix();
	$suffix=null;
	if(isset($GLOBALS["BY_CRON"])){$suffix="[CRON] ";}
	$nice=$unix->EXEC_NICE();
	$php5=$unix->LOCATE_PHP5_BIN();
	
	
	if(!is_file("/etc/cron.d/squid-watch-sockets")){

		$me=__FILE__;
		$cmdline=trim("$nice $php5 $me --test-port --cron");
		$f[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin:/usr/share/artica-postfix/bin";
		$f[]="MAILTO=\"\"";
		$f[]="0,2,4,6,8,10,12,14,16,18,20,22,24,26,28,30,32,34,36,38,40,42,44,46,48,50,52,54,56,58 * * * *  root $cmdline >/dev/null 2>&1";
		$f[]="";
		@file_put_contents("/etc/cron.d/squid-watch-sockets", @implode("\n", $f));
		shell_exec("/etc/init.d/cron reload");
		
	}
	$f=array();
	
	
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	if(is_file("/etc/cron.d/squid-statsmembers5mn")){@unlink("/etc/cron.d/squid-statsmembers5mn");}
		
	
	$f=array();
	if(!is_file("/etc/cron.d/squid-statsclean")){
		$cmdline=trim("$nice $php5 ".dirname(__FILE__)."/exec.influxdb.php --clean");
		$f[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin:/usr/share/artica-postfix/bin";
		$f[]="MAILTO=\"\"";
		$f[]="0 1,5 * * *  root $cmdline >/dev/null 2>&1";
		$f[]="";
		@file_put_contents("/etc/cron.d/squid-statsclean", @implode("\n", $f));
		shell_exec("/etc/init.d/cron reload");
	}
	
	
	$php5=$unix->LOCATE_PHP5_BIN();

	
	
	if($aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			Events("{$suffix}A task already exists pid $pid and running since {$time}mn");
			return;
		}
		
	}	
	
	Events("{$suffix}Testing Manager port...." );
	@file_put_contents($pidfile, getmypid());
	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
	
	$MonitConfig= watchdog_config_default($MonitConfig);
	if($MonitConfig["TEST_PORT"]==0){Events("Test port is disabled, aborting" );return;}
	if($MonitConfig["watchdog"]==0){Events("Watchog is disabled, aborting" );return;}

	
	if(!isset($MonitConfig["TEST_PORT_MAX"])){$MonitConfig["TEST_PORT_MAX"]=5;}
	if(!isset($MonitConfig["TEST_PORT_INTERVAL"])){$MonitConfig["TEST_PORT_INTERVAL"]=2;}
	if(!isset($MonitConfig["TEST_PORT_TIMEOUT"])){$MonitConfig["TEST_PORT_TIMEOUT"]=2;}
	if(!isset($MonitConfig["TEST_PORT_RESTART"])){$MonitConfig["TEST_PORT_RESTART"]=1;}
	if(!isset($MonitConfig["MAX_RESTART"])){$MonitConfig["MAX_RESTART"]=2;}	
	
	if($MonitConfig["TEST_PORT_MAX"]==0){$MonitConfig["TEST_PORT_MAX"]=5;}
	if($MonitConfig["TEST_PORT_TIMEOUT"]==0){$MonitConfig["TEST_PORT_TIMEOUT"]=2;}
	$MAX_RESTART=$MonitConfig["MAX_RESTART"];
	$SquidPortTimeOudRestart=intval($sock->GET_INFO("SquidPortTimeOudRestart"));
	
	
	$squidpid=SQUID_PID();
	$timemin=$unix->PROCCESS_TIME_MIN($squidpid);
	Events("{$suffix}Squid running since {$timemin}mn" );
	if($GLOBALS["VERBOSE"]){echo "Process Running since {$timemin}mn\n";}
	if($timemin<2){
		Events("{$suffix}Can test a port only if Squid run after 2mn , current {$timemin}mn" );
		return;
	}
	
	
	$cache_manager=new cache_manager();
	$cache_manager->TimeOutsec=$MonitConfig["TEST_PORT_TIMEOUT"];
	$data=$cache_manager->makeQuery("info");
	if($cache_manager->ok){
		if($GLOBALS["VERBOSE"]){echo "$data\n";}
		if($GLOBALS["VERBOSE"]){echo "Process Running since {$timemin}mn\n";}
		$sock->SET_INFO("SquidPortTimeOudRestart",0);
		return;
	}
	
	
	Events("{$suffix}Cache Manager report failed with error $cache_manager->errstr");
	PROXY_TESTS_PORTS_EVENTS("{$suffix}Cache Manager report failed with error $cache_manager->errstr");
	
	
	sleep($MonitConfig["TEST_PORT_INTERVAL"]);
	
	for($i=1;$i<$MonitConfig["TEST_PORT_MAX"];$i++){
		$cache_manager=new cache_manager();
		$cache_manager->TimeOutsec=$MonitConfig["TEST_PORT_TIMEOUT"];
		$data=$cache_manager->makeQuery("info");
		if($cache_manager->ok){
			squid_admin_mysql(2, "{$suffix}Manager port retreive information after $i attempts", null,__FILE__,__LINE__);
			$sock->SET_INFO("SquidPortTimeOudRestart",0);
			@unlink("/var/log/artica.proxy.watchdog.test.ports.log");
			return;
		}
		
		if($cache_manager->errno==3){
			squid_admin_mysql(0,"{$suffix}Proxy Port report $cache_manager->errstr ($cache_manager->errstr_plus) [action=disable-icap]", @file_get_contents("/var/log/artica.proxy.watchdog.test.ports.log"),__FILE__,__LINE__);
			@file_put_contents("/etc/squid3/icap.conf", "\n");
			reload_squid(true);
			sleep(10);
			continue;
			
		}
		if($cache_manager->errno==4){
			squid_admin_mysql(1,"{$suffix}Proxy Port report $cache_manager->errstr ($cache_manager->errstr_plus) [action=disable-icap]", @file_get_contents("/var/log/artica.proxy.watchdog.test.ports.log"),__FILE__,__LINE__);
			return true;				
		}
		if($cache_manager->errno==5){
			squid_admin_mysql(1,"{$suffix}Proxy Port report invalid URL $cache_manager->URL_SENDED suggest to restart proxy service", @file_get_contents("/var/log/artica.proxy.watchdog.test.ports.log"),__FILE__,__LINE__);
			return true;
		}				
		if($cache_manager->errno==6){
			squid_admin_mysql(1,"{$suffix}Proxy Port report $cache_manager->errstr suggest to check your paranoid rules", @file_get_contents("/var/log/artica.proxy.watchdog.test.ports.log"),__FILE__,__LINE__);
			return true;
		}		
		
		
		PROXY_TESTS_PORTS_EVENTS("{$suffix} ($i/{$MonitConfig["TEST_PORT_TIMEOUT"]}) Cache Manager report failed with error $cache_manager->errstr ($cache_manager->errstr_plus)");
		$LOGS[]="{$suffix}Connection $i/{$MonitConfig["TEST_PORT_TIMEOUT"]} Error:$cache_manager->errstr sleeping {$MonitConfig["TEST_PORT_INTERVAL"]}";
		Events("{$suffix}Connection $i/{$MonitConfig["TEST_PORT_TIMEOUT"]} Error:$cache_manager->errstr sleeping {$MonitConfig["TEST_PORT_INTERVAL"]}" );
		sleep($MonitConfig["TEST_PORT_INTERVAL"]);
	}
	
	if(!is_numeric($MAX_RESTART)){$MAX_RESTART=2;}
	
	
	$STAMP_MAX_RESTART=STAMP_MAX_RESTART_GET();
	if($STAMP_MAX_RESTART >= $MAX_RESTART){
		Events("{$suffix}Restarting Squid aborted, max $MAX_RESTART restarts has already been made (waiting Squid restart correctly to return back to 0)...");
		return;
	}
	

	$SquidPortTimeOudRestart++;
	$sock->SET_INFO("SquidPortTimeOudRestart", $SquidPortTimeOudRestart);
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
	PROXY_TESTS_PORTS_EVENTS("{$suffix} Restarting squid Max restarts: $SquidPortTimeOudRestart/$MAX_RESTART");
	Events("{$suffix}Restarting squid Max restarts: $SquidPortTimeOudRestart/$MAX_RESTART");
	
	$SecondsToWaint=$MonitConfig["TEST_PORT_INTERVAL"]+$MonitConfig["TEST_PORT_TIMEOUT"];
	$max_duration=$MonitConfig["TEST_PORT_MAX"]*$SecondsToWaint;
	
	if($MonitConfig["TEST_PORT_RESTART"]==0){
		squid_admin_mysql(0,"{$suffix}Proxy Port did not respond during $max_duration seconds [action=notify]", @file_get_contents("/var/log/artica.proxy.watchdog.test.ports.log"),__FILE__,__LINE__);
		@unlink("/var/log/artica.proxy.watchdog.test.ports.log");
		return;
	}

	

	if($SquidPortTimeOudRestart>$MAX_RESTART){
		squid_admin_mysql(0,"{$suffix}Proxy Port did not respond during $max_duration seconds (max restarts) [action=notify]", @file_get_contents("/var/log/artica.proxy.watchdog.test.ports.log"),__FILE__,__LINE__);
		@unlink("/var/log/artica.proxy.watchdog.test.ports.log");
		return;
	}
	
	
	squid_admin_mysql(0,"{$suffix}Proxy Port did not respond during $max_duration seconds [action=restart]", @file_get_contents("/var/log/artica.proxy.watchdog.test.ports.log"),__FILE__,__LINE__);
	@unlink("/var/log/artica.proxy.watchdog.test.ports.log");
	restart_squid(true);
	
	
	
	$GLOBALS["ALL_SCORES_WHY"][]="function ".__FUNCTION__." return failed";
	$GLOBALS["ALL_SCORES"]++;	
	
}


function PING_GATEWAY(){
	$sock=new sockets();
	$unix=new unix();
	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
	$MonitConfig=PING_GATEWAY_DEFAULT_PARAMS($MonitConfig);

	if($MonitConfig["ENABLE_PING_GATEWAY"]==0){return;}
	if(!isset($MonitConfig["PING_GATEWAY"])){$MonitConfig["PING_GATEWAY"]=null;}
	$PING_GATEWAY=$MonitConfig["PING_GATEWAY"];
	
	if($PING_GATEWAY==null){
		$TCP_NICS_STATUS_ARRAY=$unix->NETWORK_ALL_INTERFACES();
		if(isset($TCP_NICS_STATUS_ARRAY["eth0"])){$PING_GATEWAY=$TCP_NICS_STATUS_ARRAY["eth0"]["GATEWAY"];}
		if($PING_GATEWAY==null){if(isset($TCP_NICS_STATUS_ARRAY["eth1"])){$PING_GATEWAY=$TCP_NICS_STATUS_ARRAY["eth1"]["GATEWAY"];}	}
	}
	
	if($PING_GATEWAY==null){Events("No IP address defined in the configuration, aborting test...");return;}
	if(!$unix->isIPAddress($PING_GATEWAY)){Events("\"$PING_GATEWAY\" not a valid ip address");return;}
	
	$STAMP_MAX_PING=intval(trim(@file_get_contents($GLOBALS["STAMP_MAX_PING"])));
	if(!is_numeric($STAMP_MAX_PING)){$STAMP_MAX_PING=1;}
	if($STAMP_MAX_PING<1){$STAMP_MAX_PING=1;}

	if($GLOBALS["VERBOSE"]){echo "PING $PING_GATEWAY STAMP_MAX_PING=$STAMP_MAX_PING\n";}
	
	if($unix->PingHost($PING_GATEWAY,true)){
		Events("PingHost($PING_GATEWAY -> TRUE OK");
		if($STAMP_MAX_PING>1){
			@file_put_contents($GLOBALS["STAMP_MAX_PING"], 1);
		}
		return;
	}	
	Events("PingHost($PING_GATEWAY -> FALSE -> PING_FAILED_RELOAD_NET={$MonitConfig["PING_FAILED_RELOAD_NET"]}");
	if($MonitConfig["PING_FAILED_RELOAD_NET"]==1){
		squid_admin_mysql(1,"Reloading network Ping $PING_GATEWAY failed","Reloading network  $PING_GATEWAY:\nThe $PING_GATEWAY ping failed, Artica will restart network");
		$report=$unix->NETWORK_REPORT();
		ToSyslog("kernel: [  Artica-Net] Start Network [artica-ifup] (".basename(__FILE__)."/".__LINE__.")" );
		shell_exec("/etc/init.d/artica-ifup start --script=".basename(__FILE__)."/".__FUNCTION__);
		if($unix->PingHost($PING_GATEWAY,true)){
			squid_admin_mysql(2,"Relink network success","Relink network success after ping failed on $PING_GATEWAY:\nThe $PING_GATEWAY ping failed, Artica as restarted network and ping is now success.\nHere it is the network report when Ping failed\n$report");
			return;
		}
		
	}
	
	$MAX_PING_GATEWAY=$MonitConfig["MAX_PING_GATEWAY"];
	$STAMP_MAX_PING=$STAMP_MAX_PING+1;
	Events("$PING_GATEWAY not available - $STAMP_MAX_PING time(s) / $MAX_PING_GATEWAY Max");
	@file_put_contents($GLOBALS["STAMP_MAX_PING"], $STAMP_MAX_PING);
	if($STAMP_MAX_PING < $MAX_PING_GATEWAY){
		Events("$STAMP_MAX_PING < $MAX_PING_GATEWAY ABORTING...");
		return;}
	
	$UfdbguardSMTPNotifs=unserialize(base64_decode($sock->GET_INFO("UfdbguardSMTPNotifs")));
	if(!isset($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"])){$UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]=0;}
	if(!is_numeric($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"])){$UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]=0;}	
	@file_put_contents($GLOBALS["STAMP_MAX_PING"], 1);
	
	
	if($MonitConfig["PING_FAILED_REPORT"]==1){
		$report=$unix->NETWORK_REPORT();
		squid_admin_mysql(1,"Unable to ping $PING_GATEWAY","$report");
		
	}

	if($MonitConfig["PING_FAILED_FAILOVER"]==1){
		$GLOBALS["ALL_SCORES_WHY"][]="function ".__FUNCTION__." return failed";
		$GLOBALS["ALL_SCORES"]++;
	}
	if($MonitConfig["PING_FAILED_REBOOT"]==1){
			REBOOTING_SYSTEM();
	}


}





function SwapCache($MonitConfig){
	
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	if($MonitConfig["MaxSwapPourc"]==0){return;}
	if($MonitConfig["MaxSwapPourc"]>99){return;}
	
	$unix=new unix();
	if($unix->file_time_min($pidtime)<59){return;}
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	
	
	
	$free=$unix->find_program("free");
	$echo=$unix->find_program("echo");
	$sync=$unix->find_program("sync");
	$swapoff=$unix->find_program("swapoff");
	$swapon=$unix->find_program("swapon");
	exec("$free 2>&1",$results);
	$used=0;
	$total=0;
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#Swap:\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)#", $ligne,$re)){
			$total=$re[1];
			$used=$re[2];
			
		}
		
	}
	if(!is_numeric($total)){return;}
	if($total==0){return;}
	if($used==0){return;}
	if($total==$used){return;}
	$tot1=$used/$total;
	$tot1=$tot1*100;
	if($GLOBALS["VERBOSE"]){echo "Swap:$used/$total - $tot1\n";}
	
	
	
	$perc=round($tot1);
	if($GLOBALS["VERBOSE"]){echo "Swap:$used/$total {$perc}%\n";}
	
	Events("Swap: $used/$total {$perc}% Rule {$MonitConfig["MaxSwapPourc"]}%");
	
	if($perc>$MonitConfig["MaxSwapPourc"]){
		$t=time();
		squid_admin_mysql(2, "Swap exceed rule: {$perc}% flush the swap", "Swap: $used/$total {$perc}% Rule {$MonitConfig["MaxSwapPourc"]}%",__FILE__,__LINE__);
		Events("Swap exceed rule: {$perc}% flush the swap...");
		$GLOBALS["ALL_SCORES_WHY"][]="Swap exceed rule: {$perc}% flush the swap...";
		$GLOBALS["ALL_SCORES"]++;
		SwapWatchdog_FreeSync();
	}
	        
}


function squidz($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			system_admin_events("restart_squid::Already task running PID $pid since {$time}mn", __FUNCTION__, __FILE__, __LINE__, "proxy");
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}	
	echo date("Y/m/d H:i:s")." Arti| Stopping Squid\n";
	echo date("Y/m/d H:i:s")." Arti| Please wait....\n";
	stop_squid(true);
	$squidbin=$unix->LOCATE_SQUID_BIN();
	$su_bin=$unix->find_program("su");
	$t1=time();
	

	
	exec("$su_bin squid -c \"$squidbin -z\" 2>&1",$results);
	echo date("Y/m/d H:i:s")." Arti| Checking caches `$squidbin`....Please wait\n";
	while (list ($index, $val) = each ($results)){
		echo $val."\n";
	}
	
	
	$execnice=$unix->EXEC_NICE();
	$nohup=$unix->find_program("nohup");
	$chown=$unix->find_program("chown");
	$tail=$unix->find_program("tail");
	
	$GetCachesInsquidConf=$unix->SQUID_CACHE_FROM_SQUIDCONF();
	while (list ($CacheDirectory, $type) = each ($GetCachesInsquidConf)){
		echo date("Y/m/d H:i:s")." Arti| Lauching a chown task in background mode on `$CacheDirectory`... this could take a while....\n";
		$unix->chmod_alldirs(0755, $CacheDirectory);
		$cmd="$execnice$nohup $chown -R squid:squid $CacheDirectory >/dev/null 2>&1 &";
		echo date("Y/m/d H:i:s")." Arti| $cmd\n";
		shell_exec($cmd);
		
	}	
	
	echo date("Y/m/d H:i:s")." Arti| Starting squid....Please wait\n";
	start_squid(true);
	sleep(5);
	
	exec("$tail -n 100 /var/log/squid/cache.log 2>&1",$results2);
	while (list ($index, $val) = each ($results2)){echo $val."\n";}
	
	echo date("Y/m/d H:i:s")." Arti| Done...\n";
	echo date("Y/m/d H:i:s")." Arti| Took ". $unix->distanceOfTimeInWords($t1,time())."\n";
}


function CheckAvailableSize(){
	$unix=new unix();
	$GetCachesInsquidConf=$unix->SQUID_CACHE_FROM_SQUIDCONF();
	
	
	
	
	while (list ($CacheDirectory, $type) = each ($GetCachesInsquidConf)){
		$free=$unix->DIR_STATUS($CacheDirectory);
		$POURC=$free["POURC"];
		$SIZE=round(($free["SIZE"]/1024));
		$MOUNTED=$free["MOUNTED"];
		
		
		
		if($GLOBALS["VERBOSE"]){
			echo "********\n$CacheDirectory Used:{$POURC}%\n********\nMonted on $MOUNTED\nSize: {$free["SIZE"]} {$SIZE}MB\n";
		}
		if($POURC>99){
			$GLOBALS["ALL_SCORES"]++;
			$GLOBALS["ALL_SCORES_WHY"][]="$CacheDirectory Used:{$POURC}% on $MOUNTED";
			squid_admin_mysql(0, "$CacheDirectory Used:{$POURC}% on $MOUNTED", "
					Partition on: $MOUNTED ( {$SIZE}M )
					You need to clean this cache to make free space",__FILE__,__LINE__); 
			}
		
		
	}
	
}

function idns(){
	
	
}
function CheckAllports(){
	$unix=new unix();
	return $unix->SQUID_ALL_PORTS();
	
}

function CheckGlobalInfos($ByExec=false){
	
	$unix=new unix();
	
	$pidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	
	if($ByExec){
		$pid=$unix->get_pid_from_file($pidFile);
		if($unix->process_exists($pid)){
			if($unix->PROCCESS_TIME_MIN($pid,10)<2){return;}
		}
		@file_put_contents($pidFile, getmypid());
	}
	
	
	$data=$unix->squidclient("info");
	if($data==null){return;}
	$f=explode("\n",$data);
	
	
	while (list ($index, $line) = each ($f)){
		if(preg_match("#Number of HTTP requests received:\s+([0-9]+)#", $line,$re)){
			$ARRAY["TOTAL_REQUESTS"]=$re[1];
			continue;
		}
		if(preg_match("#Average HTTP requests per minute since start:\s+([0-9]+)#", $line,$re)){
			$ARRAY["AVERAGE_REQUESTS"]=$re[1];
			continue;
		}
		
		if(preg_match("#Storage Swap size:\s+([0-9]+)#", $line,$re)){
			$ARRAY["ALL_CACHES"]=$re[1];
			continue;
		}
		
		if(preg_match("#Storage Swap capacity:\s+([0-9\.]+)#", $line,$re)){
			$ARRAY["ALL_CACHES_PERC"]=$re[1];
			continue;
		}
		if(preg_match("#Storage Mem capacity:\s+([0-9\.]+)#", $line,$re)){
			$ARRAY["MEM_POURC"]=$re[1];
			continue;
		}
		
		if(preg_match("#UP Time:\s+([0-9\.]+)#", $line,$re)){
			$ARRAY["UPTIME"]=$re[1];
			continue;
		}
		if(preg_match("#CPU Usage:\s+([0-9\.]+)#", $line,$re)){
			$ARRAY["CPU_PERC"]=$re[1];
			continue;
		}
		
		if(preg_match("#Start Time:\s+(.+)#", $line,$re)){
			$ARRAY["T"]=strtotime(trim($re[1]));
			$ARRAY["D"]=$unix->distanceOfTimeInWords($ARRAY["T"],time(),true);
			continue;
		}
		if(preg_match("#Current Time:\s+(.+)#", $line,$re)){
			$ARRAY["T2"]=strtotime(trim($re[1]));
			$ARRAY["D"]=$unix->distanceOfTimeInWords($ARRAY["T"],$ARRAY["T2"],true);
			continue;
		}
		
		
				
		
		
	}
	
	if(count($ARRAY)>7){
		$ARRAY["F"]=time();
		@unlink("/usr/share/artica-postfix/ressources/logs/web/SQUID_MGR_INFO.DB");
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/SQUID_MGR_INFO.DB", serialize($ARRAY));
		@chmod("/usr/share/artica-postfix/ressources/logs/web/SQUID_MGR_INFO.DB",0755);
	}
	
	
	
}


function CheckStoreDirs($direct=false){
	$unix=new unix();
	$GetCachesInsquidConf=$unix->SQUID_CACHE_FROM_SQUIDCONF();
	$mustBuild=false;
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
		
	$subdirs["00"]=true;
	$subdirs["01"]=true;
	$subdirs["02"]=true; 
	$subdirs["03"]=true;
	$subdirs["04"]=true; 
	$subdirs["05"]=true;  
	$subdirs["06"]=true;  
	$subdirs["07"]=true;  
	$subdirs["08"]=true;  
	$subdirs["09"]=true;  
	$subdirs["0A"]=true;  
	$subdirs["0B"]=true;  
	$subdirs["0C"]=true;  
	$subdirs["0D"]=true;  
	$subdirs["0E"]=true;  
	$subdirs["0F"]=true;
	
	while (list ($CacheDirectory, $type) = each ($GetCachesInsquidConf)){
		if($GLOBALS["VERBOSE"]){echo "Checking $CacheDirectory\n";}
		
		if(!is_dir("$CacheDirectory")){
			if($GLOBALS["VERBOSE"]){echo "Checking $CacheDirectory no such directory\n";}
			@mkdir($CacheDirectory,0755,true);
			@chown($CacheDirectory,"squid");
			@chgrp($CacheDirectory,"squid");
			$mustBuild=true;
			break;
		}
		if($direct){SendLogs("Found cache $CacheDirectory [$type]");}
		@chown($CacheDirectory,"squid");
		@chgrp($CacheDirectory,"squid");
		
		
		if(strtolower($type)=="rock"){
			if(!is_file("$CacheDirectory/rock")){$mustBuild=true;break;}
			continue;
		}
		if(preg_match("#rock#", $CacheDirectory)){
			if(!is_file("$CacheDirectory/rock")){$mustBuild=true;break;}
			
			
			continue;
		}
			
		reset($subdirs);
			
		while (list ($subdir, $type) = each ($subdirs)){
			if(!is_dir("$CacheDirectory/$subdir")){
				if($GLOBALS["VERBOSE"]){echo "Checking $CacheDirectory/$subdir no such directory\n";}
				$mustBuild=true;
				break;
			}
			@chown("$CacheDirectory/$subdir","squid");
			@chgrp("$CacheDirectory/$subdir","squid");
		}
		
		if($mustBuild){break;}
		
	}
	
	if($mustBuild){
		if($direct){
			$su_bin=$unix->find_program("su");
			$squidbin=$unix->LOCATE_SQUID_BIN();
			exec("$su_bin squid -c \"$squidbin -z\" 2>&1",$results);
			while (list ($index, $line) = each ($results) ){SendLogs("$line");}
			return;
		}
		$GLOBALS["ALL_SCORES_WHY"][]="function ".__FUNCTION__." return failed";
		$GLOBALS["ALL_SCORES"]++;
		$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.smp.php --squid-z-fly --force >/dev/null 2>&1 &";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		shell_exec("$cmd");
	}
	
}

function get_action_script_source(){
	if($GLOBALS["MONIT"]){$suffix=" (by system monitor)";}
	if($GLOBALS["CRASHED"]){$suffix= " ( after a crash !)";}
	if($GLOBALS["BY_CACHE_LOGS"]){$suffix= " ( ordered by logs monitor )";}
	if($GLOBALS["BY_STATUS"]){$suffix=" ( by Monitor )";}
	if($GLOBALS["BY_CLASS_UNIX"]){$suffix=" (by class.unix.inc)";}
	if($GLOBALS["BY_FRAMEWORK"]){$suffix=" (by Framework)";}
	if($GLOBALS["BY_WATCHDOG"]){$suffix=" (by Watchdog)";}
	
	if($GLOBALS["BY_ARTICA_INSTALL"]){$suffix=" (by artica-install)";}
	if($GLOBALS["BY_FORCE_RECONFIGURE"]){$suffix=" (after building settings)";}
	if($GLOBALS["BY_RESET_CACHES"]){$suffix=" (after reset caches)";}
	if(strlen($GLOBALS["BY_OTHER_SCRIPT"])>2){$suffix=" (by other script {$GLOBALS["BY_OTHER_SCRIPT"]})";}
	if($GLOBALS["KILL_ALL"]){$suffix=" - Force Kill - $suffix";}
}


function restart_squid($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	build_progress_restart("{please_wait}", 10);
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			system_admin_events("restart_squid::Already task running PID $pid since {$time}mn", __FUNCTION__, __FILE__, __LINE__, "proxy");
			build_progress_restart("{failed}: Already task running PID $pid since {$time}mn", 110);
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	if(!is_file($squidbin)){
		build_progress_restart("{failed}", 110);
		if($GLOBALS["OUTPUT"]){echo "Restart.......: ".date("H:i:s")." Squid-cache, not installed\n";}
		return;
	}
	

	$t1=time();
	
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
		
	if($GLOBALS["OUTPUT"]){echo "Restart.......: ".date("H:i:s")." Restarting Squid-cache...\n";}
	
	$suffix=null;
	$reconfigure=null;
	$suffix=get_action_script_source();
	
	
	if($GLOBALS["RECONFIGURE"]){$reconfigure=" - with reconfigure";}
	
	build_progress_restart("{stopping_service}", 20);
	stop_squid(true);
	$date=date("Y-m-d H:i:s");
	squid_admin_mysql(1, "Restarting Squid-Cache service: $suffix$reconfigure",
	"$suffix - $date\n a process ask to restart it\nCalled by function:$sourcefunction in line $sourceline",__FILE__,__LINE__);
	
	
	
	$php5=$unix->LOCATE_PHP5_BIN();
	if($GLOBALS["RECONFIGURE"]){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." Reconfiguring Squid-cache...\n";}
		build_progress_restart("{building_parameters}", 30);
		system("$php5 /usr/share/artica-postfix/exec.squid.php --build --force");
	}
	
	if($GLOBALS["OUTPUT"]){echo "Restart.......: ".date("H:i:s")." Stopping Squid...\n";}
	
	
	
	if($GLOBALS["SWAPSTATE"]){
		$GLOBALS["FORCE"]=true;
		swap_state();
	}
	if($GLOBALS["OUTPUT"]){echo "Restart.......: Starting Squid...\n";}
	build_progress_restart("{starting_service}", 40);
    start_squid(true);
    $took=$unix->distanceOfTimeInWords($t1,time());
    $EnableTransparent27=intval($sock->GET_INFO("EnableTransparent27"));
    if($EnableTransparent27==1){
    	build_progress_restart("{restart_cache_nat}", 60);
    	if($GLOBALS["OUTPUT"]){echo "Restart.......: Restarting Cache NAT\n";}
    	shell_exec("/etc/init.d/squid-nat restart --force 2>&1 >> /usr/share/artica-postfix/ressources/logs/web/restart.squid");
    }
    
    if($GLOBALS["BY_FRAMEWORK"]){
	    if($GLOBALS["OUTPUT"]){echo "Restart.......: Restarting DNS...\n";}
	    build_progress_restart("{restarting_dns_service}", 70);
	    shell_exec("/etc/init.d/dnsmasq restart --force --framework 2>&1 >> /usr/share/artica-postfix/ressources/logs/web/restart.squid");
    } 
    build_progress_restart("{starting_service} {done}", 100);
    system_admin_events("Squid restarted took: $took", __FUNCTION__, __FILE__, __LINE__, "proxy");
	
}

function DeletedCaches(){
	$unix=new unix();
	$dirs=$unix->dirdir("/home/squid");
	$rm=$unix->find_program("rm");
	while (list ($CacheDirectory, $type) = each ($dirs)){
		if(!preg_match("#-delete-[0-9]+#", $CacheDirectory)){continue;}
		Events("Found an old cache: $CacheDirectory");
		shell_exec("$rm -rf $CacheDirectory");
	}

}

function Checks_Winbindd(){
	$sock=new sockets();
	$unix=new unix();
	$chmod=$unix->find_program("chmod");
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}	
	if($EnableKerbAuth==0){return;}
	$unix->Winbindd_privileged_SQUID();
	if(winbind_is_run()){
		Events("Winbind OK pid:{$GLOBALS["WINBINDPID"]}...");
		return;}
	system_admin_events("Winbindd not running, start it...", __FUNCTION__, __FILE__, __LINE__, "proxy");
	Events("Start Winbind...");
	$php=$unix->LOCATE_PHP5_BIN();
	exec("$php /usr/share/artica-postfix/exec.winbindd.php --start 2>&1",$results);
	
	
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
	squid_admin_mysql(1,"Winbindd service was started",@implode("\n", $results)."\n$executed");
	
	
	
	Events(@implode("\n", $results));
	
	if(!winbind_is_run()){ $GLOBALS["ALL_SCORES_WHY"][]="function ".__FUNCTION__." return failed";$GLOBALS["ALL_SCORES"]++; }
	
}

function winbind_is_run(){
	$GLOBALS["WINBINDPID"]=0;
	$pidfile="/var/run/samba/winbindd.pid";
	$unix=new unix();
	$GLOBALS["WINBINDPID"]=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($GLOBALS["WINBINDPID"])){return true;}
	$winbindbin=$unix->find_program("winbindd");
	$GLOBALS["WINBINDPID"]=$unix->PIDOF($winbindbin);
	if($unix->process_exists($GLOBALS["WINBINDPID"])){return true;}
	
	return false;

}

function Events($text){
	
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}
		
	}	
	
	$unix=new unix();
	$unix->events($text,"/var/log/squid.watchdog.log",false,$sourcefunction,$sourceline);
}

function PROXY_TESTS_PORTS_EVENTS($text){
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}
	
	}
	$unix=new unix();
	$unix->events($text,"/var/log/artica.proxy.watchdog.test.ports.log",false,$sourcefunction,$sourceline);
}

function ChecksInstances(){
	$unix=new unix();
	$pidof=$unix->find_program("pidof");
	
	
	
}

function CleanMemBoosters(){
return;
}


function MemBoosters(){
	if($GLOBALS["VERBOSE"]){echo "Membooster (Verbose) \n";}
	$unix=new unix();
	$df=$unix->find_program("df");
	$rm=$unix->find_program("rm");

	
	$swapiness=intval(trim(@file_get_contents("/proc/sys/vm/swappiness")));
	if($GLOBALS["VERBOSE"]){echo "SWAPINESS = {$swapiness}%\n";}
	//vm.swappiness
	
	if($swapiness>5){
		squid_admin_mysql(2,"Swapiness set to 5%","The SWAPINESS was {$swapiness}%:\nIt will be modified to 5% for MemBoosters",__FILE__,__LINE__);
		@file_put_contents("/proc/sys/vm/swappiness", "5");
	}
}

function FailOverParams(){
	if(isset($GLOBALS["FailOverParams"])){return $GLOBALS["FailOverParams"];}
	$sock=new sockets();
	$FailOverArticaParams=unserialize(base64_decode($sock->GET_INFO("FailOverArticaParams")));
	if(!is_numeric($FailOverArticaParams["squid-internal-mgr-info"])){$FailOverArticaParams["squid-internal-mgr-info"]=1;}
	if(!is_numeric($FailOverArticaParams["ExternalPageToCheck"])){$FailOverArticaParams["ExternalPageToCheck"]=1;}
	
	$GLOBALS["FailOverParams"]=$FailOverArticaParams;
	return $GLOBALS["FailOverParams"];
}
function Checks_mgrinfos_31(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$StoreDirCache="/etc/squid3/squid_storedir_info.db";
	$data=CurlGet("storedir");
	$array=MgrStoreDirToArray($data);
	if(is_array($array)){
		@unlink($StoreDirCache);
		@file_put_contents($StoreDirCache, serialize($array));
		$time=$unix->file_time_min("/etc/artica-postfix/pids/exec.squid.php.caches_infos.time");
		if($time>15){
			shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --cache-infos");
		}
	}
		
}

function Checks_mgrinfos($MonitConfig,$aspid=false){
	
	$sock=new sockets();
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$SystemInfoCache="/etc/squid3/squid_get_system_info.db";
	$StoreDirCache="/etc/squid3/squid_storedir_info.db";
	if(!is_array($MonitConfig)){
		$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
		$MonitConfig=watchdog_config_default($MonitConfig);
	}
	
	$FailOverArticaParams=FailOverParams();
	$MgrInfosMaxTimeOut=$MonitConfig["MgrInfosMaxTimeOut"];
	$MgrInfosRestartFailed=$MonitConfig["MgrInfosRestartFailed"];
	$MgrInfosFaileOverFailed=$MonitConfig["MgrInfosFaileOverFailed"];
	$MgrInfosMaxFailed=intval($MonitConfig["MgrInfosMaxFailed"]);
	
	$MgrInfosMaxFailedCount=@file_get_contents("/etc/squid3/MgrInfosMaxFailedCount");
	if(!is_numeric($MgrInfosMaxFailedCount)){$MgrInfosMaxFailedCount=0;}
	if($MgrInfosMaxFailed<4){$MgrInfosMaxFailed=4;}
	
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($time>5){
				$kill=$unix->find_program("kill");
				Events("kill old $pid process {$time}mn");
				unix_system_kill_force($pid);
			}else{
				system_admin_events("Start_squid:: Already task running PID $pid since {$time}mn", __FUNCTION__, __FILE__, __LINE__, "proxy");
				return;
			}
		}
	
	}
	@file_put_contents($pidfile, getmypid());
	
	$squidpid=SQUID_PID();
	if(!$unix->process_exists($squidpid)){
		if($GLOBALS["VERBOSE"]){echo "Squid not running aborting\n";}
		$GLOBALS["ALL_SCORES"]++;
		$GLOBALS["ALL_SCORES_WHY"][]="function ".__FUNCTION__." return Squid not running";
		return;
	}
	
	$rpcesstime=$unix->PROCESS_TTL($squidpid);
	if($rpcesstime<5){
		if($GLOBALS["VERBOSE"]){echo "Squid running since {$rpcesstime}mn, need 5mn\n";}
		return;
	}
	
	
	if(is31()){
		if($GLOBALS["VERBOSE"]){echo "Only squid 3.1x... aborting\n";}
		Checks_mgrinfos_31();
		return;
	}
	$SquidMgrListenPort=trim($sock->GET_INFO("SquidMgrListenPort"));
	
	if( !is_numeric($SquidMgrListenPort) OR ($SquidMgrListenPort==0) ){
		$SquidBinIpaddr=$sock->GET_INFO("SquidBinIpaddr");
		if($SquidBinIpaddr==null){$SquidBinIpaddr="127.0.0.1";}
		$http_port=squid_get_alternate_port();
	
		if(preg_match("#(.+?):([0-9]+)#", $http_port,$re)){
			$SquidBinIpaddr=$re[1];
			if($SquidBinIpaddr=="0.0.0.0"){$SquidBinIpaddr="127.0.0.1";}
			$http_port=$re[2];
		}

	}else{
		$SquidBinIpaddr="127.0.0.1";
		$http_port=$SquidMgrListenPort;
	}
	
	
	
	
	$t0=time();
	$curl=new ccurl("http://$SquidBinIpaddr:$http_port/squid-internal-mgr/info",true);
	$curl->CURLOPT_NOPROXY=$SquidBinIpaddr;
	$curl->ArticaProxyServerEnabled=="no";
	$curl->interface="127.0.0.1";
	$curl->Timeout=$MgrInfosMaxTimeOut;
	$curl->UseDirect=true;
	if(!$curl->get()){
		
		$MgrInfosMaxFailedCount++;


		
		if($MgrInfosMaxFailedCount<=$MgrInfosMaxFailed){
			squid_admin_mysql(1, "Unable to retreive informations [$MgrInfosMaxFailedCount/$MgrInfosMaxFailed] from $SquidBinIpaddr:$http_port",$curl->errors,__FILE__,__LINE__);
			@file_put_contents("/etc/squid3/MgrInfosMaxFailedCount", $MgrInfosMaxFailedCount);
			return true;
		}
		
		@file_put_contents("/etc/squid3/MgrInfosMaxFailedCount", 0);
		if($MonitConfig["watchdog"]==1){
			if($MgrInfosFaileOverFailed==1){
					squid_admin_mysql(0, "Retreive informations: Failed [$MgrInfosMaxFailedCount/$MgrInfosMaxFailed] from $SquidBinIpaddr:$http_port [action=failover]",$curl->errors,__FILE__,__LINE__);
					FailOverDown("Unable to retreive informations [$MgrInfosMaxFailedCount/$MgrInfosMaxFailed] from $SquidBinIpaddr:$http_port, $curl->error");
				}
			}
		
		if($MgrInfosRestartFailed==1){	
			squid_admin_mysql(0, "Retreive informations: Failed [$MgrInfosMaxFailedCount/$MgrInfosMaxFailed] from $SquidBinIpaddr:$http_port [action=restart]",$curl->errors,__FILE__,__LINE__);
			RESTARTING_SQUID_WHY($MonitConfig,"$curl->error: Unable to retreive informations [$MgrInfosMaxFailedCount/$MgrInfosMaxFailed] from $SquidBinIpaddr:$http_port");
			$GLOBALS["ALL_SCORES_WHY"][]="function ".__FUNCTION__." return failed";
			$GLOBALS["ALL_SCORES"]++;
		}
	
	}else{
		if($MgrInfosMaxFailedCount>0){
			squid_admin_mysql(2, "Retreive informations: Success from $SquidBinIpaddr:$http_port after $MgrInfosMaxFailedCount attempts [action=none]",null,__FILE__,__LINE__);
		}
		
		STAMP_MAX_RESTART_RESET();
		@file_put_contents("/etc/squid3/MgrInfosMaxFailedCount", 0);
		if($MonitConfig["EnableFailover"]==1){FailOverUp();}
		$StoreDirCache="/etc/squid3/squid_storedir_info.db";
		$curl=new ccurl("http://$SquidBinIpaddr:$http_port/squid-internal-mgr/storedir");
		$curl->ArticaProxyServerEnabled=="no";
		$curl->interface="127.0.0.1";
		$curl->Timeout=$MgrInfosMaxTimeOut;
		$curl->UseDirect=true;
		if($curl->get()){	
			$array=MgrStoreDirToArray($curl->data);
			if(is_array($array)){
				@unlink($StoreDirCache);
				@file_put_contents($StoreDirCache, serialize($array));
				$time=$unix->file_time_min("/etc/artica-postfix/pids/exec.squid.php.caches_infos.time");
				if($time>15){
					shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --cache-infos");
				}
			}
		}	
		
	}
	
	$array=MgrInfoToArray($curl->data);
	if(count($array)>5){
		@unlink($SystemInfoCache);
		@file_put_contents("$SystemInfoCache", serialize($array));
	}
	
	if($MonitConfig["watchdog"]==1){
		if($GLOBALS["VERBOSE"]){echo " *** *** *** Checks_external_webpage() *** *** *** \n";}
		Checks_external_webpage($MonitConfig);
	}
	
}

function RESTARTING_SQUID_WHY($MonitConfig,$explain){
	
	if(!is_array($MonitConfig)){
		$sock=new sockets();
		$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
		$MonitConfig=watchdog_config_default($MonitConfig);
	
	}
	
	
	
	Events($explain, __FUNCTION__, __FILE__, __LINE__, "proxy");
	$MAX_RESTART=$MonitConfig["MAX_RESTART"];
	if(!is_numeric($MAX_RESTART)){$MAX_RESTART=2;}	
	$STAMP_MAX_RESTART=STAMP_MAX_RESTART_GET();
	if($STAMP_MAX_RESTART >= $MAX_RESTART){
		Events("Restarting Squid aborted, max $MAX_RESTART restarts has already been made (waiting Squid restart correctly to return back to 0)...");
		return;
	}
	
	$SquidCacheReloadTTL=$MonitConfig["SquidCacheReloadTTL"];
	$unix=new unix();
	$timex=$unix->file_time_min($GLOBALS["STAMP_MAX_RESTART_TTL"]);
	if($timex<$SquidCacheReloadTTL){return;}
	@unlink($GLOBALS["STAMP_MAX_RESTART_TTL"]);
	@file_put_contents($GLOBALS["STAMP_MAX_RESTART_TTL"], time());
	
	
	STAMP_MAX_RESTART_SET();
	$STAMP_MAX_RESTART++;
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
	
	system_admin_events($explain, __FUNCTION__, __FILE__, __LINE__, "proxy");
	Events("Restarting squid Max restarts: $STAMP_MAX_RESTART/$MAX_RESTART");
	
	$infos=@implode("\n", $GLOBALS["RESTART_SQUID_WHY_EVTS"]);
	
	squid_admin_mysql(1,"Ask to restart Squid-cache: $sourcefunction"
	,"Restarting squid Max restarts: $STAMP_MAX_RESTART/$MAX_RESTART\n$explain\n$infos",__FILE__,__LINE__);
	$GLOBALS["BY_WATCHDOG"]=true;
	restart_squid(true);
}


function STAMP_MAX_RESTART_GET(){
	$STAMP_MAX_RESTART=@file_get_contents($GLOBALS["STAMP_MAX_RESTART"]);
	if(!is_numeric($STAMP_MAX_RESTART)){$STAMP_MAX_RESTART=0;}
	return $STAMP_MAX_RESTART;
}
function STAMP_MAX_RESTART_SET(){
	$STAMP_MAX_RESTART=STAMP_MAX_RESTART_GET();
	$STAMP_MAX_RESTART++;
	@file_put_contents($GLOBALS["STAMP_MAX_RESTART"], $STAMP_MAX_RESTART);
}
function STAMP_MAX_RESTART_RESET(){
	@file_put_contents($GLOBALS["STAMP_MAX_RESTART"], 0);
}
				

function FailOverDown($why=null){
	$sock=new sockets();
	$FailOverArtica=$sock->GET_INFO("FailOverArtica");
	if(!is_numeric($FailOverArtica)){$FailOverArtica=1;}
	if($FailOverArtica==0){return;}
	if($GLOBALS["EnableFailover"]==0){return;}
	$users=new settings_inc();
	if(!$users->CORP_LICENSE){Events("Unable to switch to failover backup, license error");return;}
	Events("Down failover network interface in order to switch to backup...");
	
	if($why<>null){
		if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefile=basename($trace[1]["file"]);$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];}}
		system_failover_events("Switch to backup:<br>$why",$sourcefunction,basename(__FILE__),$sourceline);
	}
	
	if(!is_file("/etc/init.d/artica-failover")){
		$unix=new unix();
		$php=$unix->LOCATE_PHP5_BIN();
		shell_exec("$php ". dirname(__FILE__)."/exec.initslapd.php --failover");
	}
	
	
	@unlink($GLOBALS["STAMP_FAILOVER"]);
	@file_put_contents($GLOBALS["STAMP_FAILOVER"], time());
	
	shell_exec("/etc/init.d/artica-failover stop");
	
}

function FailOverCheck(){
	$sock=new sockets();
	$unix=new unix();
	$users=new settings_inc();
	if($GLOBALS["VERBOSE"]){echo "************************\n";}
	if($GLOBALS["VERBOSE"]){echo "********* FAILOVER: Score {$GLOBALS["ALL_SCORES"]}\n";}
	$EnableFailover=$sock->GET_INFO("EnableFailover");
	$FailOverArtica=$sock->GET_INFO("FailOverArtica");
	if(!is_numeric($FailOverArtica)){$FailOverArtica=1;}
	if(!is_numeric($EnableFailover)){$EnableFailover=1;}
	$ucarp=$unix->find_program("ucarp");
	$GLOBALS["EnableFailover"]=$EnableFailover;
	$Master=1;
	
	if(!$users->CORP_LICENSE){
		$EnableFailover=0;
		return;
	}

	

	if(!is_file("/usr/share/ucarp/Master")){
		$Master=0;
	}
	$running=0;
	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
	$MonitConfig["EnableFailover"]=$EnableFailover;
	$MonitConfig=watchdog_config_default($MonitConfig);
	if($GLOBALS["VERBOSE"]){echo "********* CORP_LICENSE = $users->CORP_LICENSE...\n";}
	if($GLOBALS["VERBOSE"]){echo "********* EnableFailover = $EnableFailover...\n";}
	if($GLOBALS["VERBOSE"]){echo "********* FailOverArtica = $FailOverArtica...\n";}
	if($GLOBALS["VERBOSE"]){echo "********* Master = $Master...\n";}
	if($GLOBALS["VERBOSE"]){echo "********* ucarp = $ucarp...\n";}
	
	if($Master==0){if($GLOBALS["VERBOSE"]){echo "********* Not a master, abort\n";}return;}
	if($FailOverArtica==0){if($GLOBALS["VERBOSE"]){echo "********* FailOverArtica = 0, abort\n";}return;}
	if($EnableFailover==0){if($GLOBALS["VERBOSE"]){echo "********* EnableFailover = 0, abort\n";}return;}
	if(!is_file($ucarp)){if($GLOBALS["VERBOSE"]){echo "********* ucarp no such file, abort\n";}return;}
	
	$Interface=@file_get_contents("/usr/share/ucarp/Master");
	if($GLOBALS["VERBOSE"]){echo "********* Interface: $Interface\n";}
	
	$PID=$unix->PIDOF_PATTERN("$ucarp.*?--interface=$Interface");
	if($unix->process_exists($PID)){$running=1;}
	if($GLOBALS["VERBOSE"]){echo "********* PID = $PID...\n";}
	if($GLOBALS["VERBOSE"]){echo "********* Running = $running...\n";}
	
	
	$ALL_SCORES_WHY=count($GLOBALS["ALL_SCORES_WHY"])."elements\n".@implode("\n", $GLOBALS["ALL_SCORES_WHY"]);
	
	$MAIN=unserialize(base64_decode($sock->GET_INFO("HASettings")));
	$SLAVE_IP=$MAIN["SLAVE"];
	
	if($unix->isIPAddress($SLAVE_IP)){
		if(!$unix->PingHost($SLAVE_IP,true)){
			squid_admin_mysql(0, "FailOver: Unable to ping slave $SLAVE_IP",__FILE__,__LINE__);
			return;
		}
	}
	
	
	if($GLOBALS["ALL_SCORES"]>0){
		if($running==0){CHECK_HA_MASTER_UP(false);return;}
		squid_admin_mysql(0, "FailOver: Switch to slave server $SLAVE_IP - score: {$GLOBALS["ALL_SCORES"]}", "Health check failed\n$ALL_SCORES_WHY\n",__FILE__,__LINE__);
		if($GLOBALS["VERBOSE"]){echo "********* /etc/init.d/artica-failover stop\n";}
		if($GLOBALS["VERBOSE"]){echo "************************\n";}
		shell_exec("/etc/init.d/artica-failover stop");
		return;
	}

	if($running==0){
		squid_admin_mysql(0, "FailOver: Return back to master", "Health check Success",__FILE__,__LINE__);
		if($GLOBALS["VERBOSE"]){echo "********* /etc/init.d/artica-failover start\n";}
		if($GLOBALS["VERBOSE"]){echo "************************\n";}
		shell_exec("/etc/init.d/artica-failover start");
		CHECK_HA_MASTER_UP(true);
		return;
	}
	CHECK_HA_MASTER_UP(true);
	if($GLOBALS["VERBOSE"]){echo "********* FailOver: NOTHING TO DO\n";}
	if($GLOBALS["VERBOSE"]){echo "************************\n";}
}

function DefaultRoute(){
	$unix=new unix();
	$ip=$unix->find_program("ip");
	exec("$ip route 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		if(preg_match("#^default via#", $ligne)){
			if($GLOBALS["VERBOSE"]){echo "$ligne OK\n";}
			return true;
		}
		
		if(preg_match("#default dev#", $ligne)){
			if($GLOBALS["VERBOSE"]){echo "$ligne OK\n";}
			return true;
		}
		
	}
	
	squid_admin_mysql(1, "No default route set in network", "I can't see default route in\n".@implode("\n", $results)."\nNetwork will be rebooted");
	ToSyslog("kernel: [  Artica-Net] Start Network [artica-ifup] (".basename(__FILE__)."/".__LINE__.")" );
	shell_exec("/etc/init.d/artica-ifup start --script=".basename(__FILE__)."/".__FUNCTION__);
}



function FailOverUp(){
	if($GLOBALS["EnableFailover"]==0){return;}
	if(!is_file($GLOBALS["STAMP_FAILOVER"])){return;}
	$sock=new sockets();
	$unix=new unix();
	$FailOverArtica=$sock->GET_INFO("FailOverArtica");
	if(!is_numeric($FailOverArtica)){$FailOverArtica=1;}
	if($FailOverArtica==0){return;}	
	if(isset($GLOBALS[__FUNCTION__])){return;}
	$GLOBALS[__FUNCTION__]=true;
	$users=new settings_inc();
	if(!$users->CORP_LICENSE){Events("Unable to switch to failover master, license error");return;}
	
	$sock=new sockets();
	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
	$MonitConfig=watchdog_config_default($MonitConfig);
	$TimeEx=$unix->file_time_min($GLOBALS["STAMP_FAILOVER"]);
	if($TimeEx<$MonitConfig["MinTimeFailOverSwitch"]){
		Events("Need to wait {$MonitConfig["MinTimeFailOverSwitch"]}mn before switch to master (current is {$TimeEx}Mn");
		return;
	}
	
	
	
	
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefile=basename($trace[1]["file"]);$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];}}
	system_failover_events("Return to master:<br>Up failover network interface in order to turn back to master",$sourcefunction,basename(__FILE__),$sourceline);
	
	Events("Up failover network interface in order to turn back to master...");
	if(!is_file("/etc/init.d/artica-failover")){
		$unix=new unix();
		$php=$unix->LOCATE_PHP5_BIN();
		shell_exec("$php ". dirname(__FILE__)."/exec.initslapd.php --failover");
	}
	@unlink($GLOBALS["STAMP_FAILOVER"]);
	shell_exec("/etc/init.d/artica-failover start");	
}

function MgrStoreDirToArray($datas){
	$results=explode("\n", $datas);
	while (list ($num, $ligne) = each ($results) ){
	if(preg_match("#Store Directory.*?:\s+(.+)#", $ligne,$re)){$StoreDir=trim($re[1]);continue;}
	if(preg_match("#Percent Used:\s+([0-9\.]+)%#", $ligne,$re)){
		$array[$StoreDir]["PERC"]=$re[1];
		continue;
	}
	if(preg_match("#Maximum Size:\s+([0-9\.]+)#", $ligne,$re)){
		$array[$StoreDir]["SIZE"]=$re[1];
		continue;
	}	
	
	if(preg_match("#Shared Memory Cache#", $ligne)){
		$StoreDir="MEM";
		continue;
	}
	
	if(preg_match("#Current entries:\s+([0-9\.]+)\s+([0-9\.]+)%#",$ligne,$re)){
		$array[$StoreDir]["ENTRIES"]=$re[1];
		$array[$StoreDir]["PERC"]=$re[2];
	}
}

return $array;
	
}

function MgrInfoToArray($datas){
	$results=explode("\n", $datas);
	$ARRAY=array();
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#^(.*?):$#", trim($ligne),$re)){
			$title=$re[1];
			continue;
		}
	
		if(preg_match("#\s+(.*?):\s+(.+)#", $ligne,$re)){
			$sub=$re[1];
			$values=trim($re[2]);
		}
		if(strpos($ligne, ':')==0){
			if(preg_match("#\s+([0-9]+)\s+(.+)#", $ligne,$re)){
				$sub=trim($re[2]);
				$values=trim($re[1]);
			}
		}
	
		if($title==null){continue;}
		if($sub==null){continue;}
		$ARRAY[$title][$sub]=$values;
	
	
	}	
	
	return $ARRAY;
}


function Checks_external_webpage($MonitConfig){
	include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
	$sock=new sockets();
	$StartTime=time();
	$unix=new unix();
	if($unix->IsSquidReverse()){if($GLOBALS["VERBOSE"]){echo "Checks_external_webpage() -> IsSquidReverse -> TRUE -> STOP\n";}return;}
	$tcp=new networking();
	
	if(!is_array($MonitConfig)){
		$sock=new sockets();
		$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
	}
	$MonitConfig=watchdog_config_default($MonitConfig);
	if($MonitConfig["TestExternalWebPage"]==0){return;}
	$FailOverArticaParams=FailOverParams();
	
	
	$ALL_IPS_GET_ARRAY=$tcp->ALL_IPS_GET_ARRAY();
	unset($ALL_IPS_GET_ARRAY["127.0.0.1"]);
	while (list ($index, $val) = each ($ALL_IPS_GET_ARRAY)){$IPZ[]=$index;}
	$IPZ_COUNT=count($IPZ);
	if($IPZ_COUNT==1){$choosennet=$IPZ[0];}else{$choosennet=$IPZ[rand(0,$IPZ_COUNT-1)];}
	
	$uri=$MonitConfig["ExternalPageToCheck"];
	if($MonitConfig["ExternalPageListen"]=="127.0.0.1"){$MonitConfig["ExternalPageListen"]=null;}
	if($MonitConfig["ExternalPageListen"]==null){$MonitConfig["ExternalPageListen"]=$choosennet;}
	
	if($GLOBALS["VERBOSE"]){echo "Checks_external_webpage(): choosennet=$choosennet({$MonitConfig["ExternalPageListen"]})\n";}
	
	$URLAR=parse_url($uri);
	if(isset($URLAR["host"])){$sitename=$URLAR["host"];}
	$ipClass=new IP();
	if(!$ipClass->isValid($sitename)){
		$ip=gethostbyname($sitename);
		if(!$ipClass->isValid($ip)){
			squid_admin_mysql(0, "Unable to resolve $sitename from $uri", 
			"It seems the server is unable to resolve $uri");
			return;
		}
	}else{
		$ip=$sitename;
	}

	
	
	
	$uri=str_replace("%T", time(), $uri);
	$http_port=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidMgrListenPort"));
	$SquidBinIpaddr="127.0.0.1";
	if(!is_numeric($http_port)){
		$http_port=squid_get_alternate_port();
		$SquidBinIpaddr=$sock->GET_INFO("SquidBinIpaddr");
		if($SquidBinIpaddr==null){$SquidBinIpaddr="127.0.0.1";}	
		
		if(preg_match("#(.+?):([0-9]+)#", $http_port,$re)){
			$SquidBinIpaddr=$re[1];
			if($SquidBinIpaddr=="0.0.0.0"){$SquidBinIpaddr="127.0.0.1";}
			$http_port=$re[2];
		}
	
	}
	
	$curl=new ccurl($uri,true);
	
	$t0=time();
	
	$curl->ArticaProxyServerEnabled="yes";
	$curl->ArticaProxyServerName=$SquidBinIpaddr;
	$curl->interface="127.0.0.1";
	
	$GLOBALS["RESTART_SQUID_WHY_EVTS"][]="Local interface: $curl->interface\n";
	
	
	if($MonitConfig["ExternalPageUsername"]<>null){
		$curl->interface=$MonitConfig["ExternalPageListen"];
		$curl->ArticaProxyServerUsername=$MonitConfig["ExternalPageUsername"];
		$curl->ArticaProxyServerUserPassword=$MonitConfig["ExternalPagePassword"];
	}
	
	if($GLOBALS["VERBOSE"]){
		echo "{$uri}:Using SQUID + $curl->interface/{$MonitConfig["ExternalPageListen"]} -> $curl->ArticaProxyServerUsername@$curl->ArticaProxyServerName\n";
	}
	$curl->ArticaProxyServerPort=$http_port;
	$curl->NoHTTP_POST=true;
	$curl->Timeout=$MonitConfig["MgrInfosMaxTimeOut"];
	
	$GLOBALS["RESTART_SQUID_WHY_EVTS"][]="Connection to: $SquidBinIpaddr:$http_port";
	
if(!$curl->get()){
		$took=$unix->distanceOfTimeInWords($StartTime,time(),true);
		
		
		if( ($curl->CURLINFO_HTTP_CODE==403)  OR ($curl->CURLINFO_HTTP_CODE==407) ){
			while (list ($index, $val) = each ($curl->CURL_ALL_INFOS)){
				if($GLOBALS["VERBOSE"]){echo "$index: $val\n";}
				$tr[]="$index: $val";
			}
			return;
		}
		
		$GLOBALS["RESTART_SQUID_WHY_EVTS"][]="Task took: $took";
		$GLOBALS["RESTART_SQUID_WHY_EVTS"][]="CURLINFO_HTTP_CODE:: $curl->CURLINFO_HTTP_CODE";
		$GLOBALS["ALL_SCORES_WHY"][]="function ".__FUNCTION__." return failed";
		$GLOBALS["ALL_SCORES"]++;
		while (list ($index, $val) = each ($curl->CURL_ALL_INFOS)){
			if($GLOBALS["VERBOSE"]){echo "$index: $val\n";}
			$GLOBALS["RESTART_SQUID_WHY_EVTS"][]="$index: $val";
		}
		
		if($GLOBALS["VERBOSE"]){echo "CURL8ERR:".$curl->error."\n\n";}
		if(preg_match("#407 Proxy Authentication#i",$curl->error)){
			Events("Watchdog receive authentication, this is not expected for $uri !");
			return;
		}
		
		if($GLOBALS["VERBOSE"]){echo $curl->data;}
		RESTARTING_SQUID_WHY($MonitConfig, 
		"Unable to download \"$uri\" from Interface:$curl->interface with error `$curl->error`");
		
		if($MonitConfig["EnableFailover"]==1){
			if($FailOverArticaParams["ExternalPageToCheck"]==1){
				FailOverDown("Unable to download \"$uri\" from Interface:$curl->interface with error `$curl->error` ($STAMP_MAX_RESTART/$MAX_RESTART attempt(s)): $uri max:$MgrInfosMaxTimeOut seconds Proxy:http://$SquidBinIpaddr:$http_port");
			}
		}
		
		return;
	}
	
	if($GLOBALS["VERBOSE"]){echo "***** SUCCESS *****\n";}
	if($GLOBALS["VERBOSE"]){echo $curl->data;}
	
	$datas=$curl->data;
	$length=strlen($datas);
	$unit="bytes";
	if($length>1024){$length=$length/1024;$unit="Ko";}
	if($length>1024){$length=$length/1024;$unit="Mo";}
	$length=round($length,2);
	STAMP_MAX_RESTART_RESET();
	Events("Success Internet should be available webpage length:{$length}$unit Took:".$unix->distanceOfTimeInWords($t0,time(),true));		
	
}

function SQUID_PID(){
	$unix=new unix();
	return $unix->SQUID_PID();
	
	
}

function ToSyslog($text){
	Events("$text");
	if($GLOBALS["VERBOSE"]){echo $text."\n";}
	if(!function_exists("syslog")){return;}
	$file=basename($file);
	$LOG_SEV=LOG_INFO;
	openlog($file, LOG_PID , LOG_SYSLOG);
	syslog($LOG_SEV, $text);
	closelog();
}

function start_prepare(){
	if($GLOBALS["CRASHED"]){return;}
	
	
	
	$reconfigure=false;
	
	$unix=new unix();
	$sock=new sockets();
	$NtpdateAD=$sock->GET_INFO("NtpdateAD");
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	if(!is_file("/etc/squid3/malwares.acl")){@file_put_contents("/etc/squid3/malwares.acl", "\n");}
	if(!is_file("/etc/squid3/squid-block.acl")){@file_put_contents("/etc/squid3/squid-block.acl", "\n");}
	$EXPLODED=explode("\n", @file_get_contents("/etc/squid3/squid.conf"));
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." Checking configuration consistency\n";}
	while (list ($index, $val) = each ($EXPLODED)){
		if(preg_match("#INSERT YOUR OWN RULE#", $val)){
			if($GLOBALS["OUTPUT"]){echo "Preparing.....: ".date("H:i:s")." squid must be reconfigured...\n";}
			$reconfigure=true;
		}
	}
	
	if($reconfigure){
		if($GLOBALS["OUTPUT"]){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." Rebuild configuration\n";}
			system("$php /usr/share/artica-postfix/exec.squid.php --build --withoutloading");
		}else{
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." Rebuild configuration\n";}
			exec("$php /usr/share/artica-postfix/exec.squid.php --build --withoutloading 2>&1",$GLOBALS["LOGS"]);
		}
			
	}
	
	
	if($NtpdateAD==1){shell_exec("$nohup $php /usr/share/artica-postfix/exec.kerbauth.php --ntpdate >/dev/null 2>&1 &");}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." Checking squid user\n";}
	$unix->CreateUnixUser("squid","squid");
	
	if(!is_file("/etc/squid3/squid.conf")){
		if($GLOBALS["OUTPUT"]){echo "Preparing.....: ".date("H:i:s")." Warning /etc/squid3/squid.conf no such file\n";}
		if($GLOBALS["OUTPUT"]){echo "Preparing.....: ".date("H:i:s")." Ask to build it and die\n";}
		shell_exec("$php /usr/share/artica-postfix/exec.squid.php --build --force --withoutloading");
		die();
	}

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." Watchdog config\n";}
	shell_exec("$php /usr/share/artica-postfix/exec.squid.php --watchdog-config >/dev/null 2>&1");

	

	
	$directories_squid[]="/var/squid";
	$directories_squid[]="/var/squid/cache";
	$directories_squid[]="/usr/share/squid3/icons";
	$directories_squid[]="/usr/share/squid3";
	
	$directories_squid[]="/var/log/squid";
	$directories_squid[]="/etc/squid3";
	$directories_squid[]="/var/lib/squidguard";
	$directories_squid[]="/var/run/squid";
	$directories_squid[]="/lib/squid3";
	
	$directories_chmod[]="/var/logs";
	$directories_chmod[]="/var/log";
	$directories_chmod[]="/var";
	
	$directories_chmod_owned[]="/home/squid";
	
	$filesOblig[]="/etc/squid3/url_rewrite_program.deny.db";
	$filesOblig[]="/var/run/squid/squid.pid";
	
	while (list ($num, $directory) = each ($directories_squid) ){
		if($GLOBALS["OUTPUT"]){echo "Preparing.....: ".date("H:i:s")." $directory\n";}
		if(!is_dir($directory)){
			@mkdir($directory,0755,true);
		}
		@chmod($directory,0755);
		$unix->chmod_func(0755, "$directory");
		$unix->chown_func("squid","squid", "$directory");
		
	}
	while (list ($num, $directory) = each ($directories_chmod) ){
		if($GLOBALS["OUTPUT"]){echo "Preparing.....: ".date("H:i:s")." $directory\n";}
		if(!is_dir($directory)){
			@mkdir($directory,0755,true);
		}
		@chmod($directory,0755);

	
	}	
	
	
	while (list ($num, $directory) = each ($directories_chmod) ){
		if($GLOBALS["OUTPUT"]){echo "Preparing.....: ".date("H:i:s")." $directory\n";}
		if(!is_dir($directory)){
			@mkdir($directory,0755,true);
		}
		$unix->chmod_func(0755, "$directory");
		$unix->chown_func("squid","squid", "$directory");
	
	}
	
	
	while (list ($num, $filepath) = each ($filesOblig) ){
		if($GLOBALS["OUTPUT"]){echo "Preparing.....: ".date("H:i:s")." $filepath\n";}
		if(!is_file($filepath)){
			@touch("$filepath");
		}
		@chmod($filepath,0755);
		@chown($filepath,"squid");
		@chgrp($filepath,"squid");
	
	
	}	
	
	$articafiles[]="exec.logfile_daemon.php";
	$articafiles[]="external_acl_squid_ldap.php";
	$articafiles[]="external_acl_dynamic.php";
	$articafiles[]="external_acl_quota.php";
	$articafiles[]="external_acl_basic_auth.php";
	$articafiles[]="external_acl_squid.php";
	$articafiles[]="ufdbgclient.php";
	
	
	while (list ($num, $filename) = each ($articafiles) ){
		$filepath="/usr/share/artica-postfix/$filename";
		if($GLOBALS["OUTPUT"]){echo "Preparing.....: ".date("H:i:s")." $filepath\n";}
		@chmod($filepath,0755);
		@chown($filepath,"squid");
		@chgrp($filepath,"squid");
		
	}
	
	
	$squid_locate_pinger=$unix->squid_locate_pinger();
	$setcap=$unix->find_program("setcap");
	if(is_file($squid_locate_pinger)){
		@chmod($squid_locate_pinger, 0755);
		@chown($squid_locate_pinger, "squid");
		@chgrp($squid_locate_pinger,"squid");	
		if(is_file("$setcap")){
			shell_exec("$setcap cap_net_raw=pe $squid_locate_pinger");
		}else{
			if($GLOBALS["OUTPUT"]){echo "Preparing.....: ".date("H:i:s")." WARNING! setcap, no such binary!!\n";}
			$unix->DEBIAN_INSTALL_PACKAGE("libcap2-bin");
		}
	
	}
	
}

function start_squid($aspid=false){
	$GLOBALS["LOGS"]=array();
	$suffix=null;
	if($GLOBALS["MONIT"]){$suffix=" (by system monitor)";}
	if($GLOBALS["BY_CACHE_LOGS"]){$suffix=" (by cache.log monitor)";}
	if($GLOBALS["BY_STATUS"]){$suffix=" (by Artica monitor)";}
	if($GLOBALS["BY_CLASS_UNIX"]){$suffix=" (by Artica class.unix.inc)";}
	if($GLOBALS["BY_FRAMEWORK"]){$suffix=" (by Artica framework)";}
	if($GLOBALS["BY_OTHER_SCRIPT"]){$suffix=" (by other script)";}
	if($GLOBALS["BY_ARTICA_INSTALL"]){$suffix=" (by artica-install)";}
	if($GLOBALS["BY_FORCE_RECONFIGURE"]){$suffix=" (after building settings)";}
	
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$sock=new sockets();
	$reconfigure=false;
	$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
	$NtpdateAD=$sock->GET_INFO("NtpdateAD");
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	$kill=$unix->find_program("kill");
	if(!is_numeric($NtpdateAD)){$NtpdateAD=0;}
	$su_bin=$unix->find_program("su");
	
	$sysctl=$unix->find_program("sysctl");
	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	if(!is_file($squidbin)){
		build_progress_start("Not installed",110);
		if($GLOBALS["OUTPUT"]){echo "Restart......: Squid-cache, not installed\n";}
		return;
	}
	
	
	
	if($GLOBALS["MONIT"]){
		if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$file=basename($trace[1]["file"]);$function=$trace[1]["function"];$line=$trace[1]["line"];$called="Called by $function() from line $line";}}
		$pid=SQUID_PID();
		if($unix->process_exists($pid)){
			$ps=$unix->find_program("ps");
			$grep=$unix->find_program("grep");
			exec("$ps aux|$grep squid 2>&1",$results);
			squid_admin_mysql(2, "Monit ordered to start squid but squid is still in memory PID $pid ??",
			"I cannot accept this order, see details\n".@implode("\n", $results)
			,__FILE__,__LINE__);
			$squidpidfile=$unix->LOCATE_SQUID_PID();
			@file_put_contents($squidpidfile, $pid);
			return;
		}
		squid_admin_mysql(1, "Monit ordered to start squid",$called,__FILE__,__LINE__);
	}
	
	
	
	
	if($SQUIDEnable==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." Squid is disabled...\n";}
		build_progress_start("Proxy service is disabled",110);
		return;
	}
	
	if(is_file("/etc/init.d/iptables-transparent")){shell_exec("/etc/init.d/iptables-transparent start");}
	
	if(is_file("/etc/artica-postfix/squid.lock")){
		$time=$unix->file_time_min("/etc/artica-postfix/squid.lock");
		if($time<60){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." Proxy is locked (since {$time}Mn...\n";}
			build_progress_start(" Proxy is locked (since {$time}Mn",110);
			return;
		}
		@unlink("/etc/artica-postfix/squid.lock");		
	}
	

	$pids=$unix->PIDOF_PATTERN_ALL("exec.squid.watchdog.php --start");
	if(count($pids)>2){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." Too many instances ". count($pids)." starting squid, kill them!\n";}
		$mypid=getmypid();
		while (list ($pid, $ligne) = each ($pids) ){
			if($pid==$mypid){continue;}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." killing $pid\n";}
			unix_system_kill_force($pid);
		} 
		
	}
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			
			if($time<5){
				build_progress_start("Task Already running PID $pid since {$time}mn",110);
				Events("Task Already running PID $pid since {$time}mn");
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." Already task running PID $pid since {$time}mn, Aborting operation (".__LINE__.")\n";}
				return;
			}
			squid_admin_mysql(0,"Too long time for artica task PID $pid running since {$time}mn", "Process will be killed");
			Tosyslog("Too long time for artica task PID $pid running since {$time}mn -> kill");
			unix_system_kill_force($pid);
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$squidbin=$unix->find_program("squid");
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
	if(!is_file($squidbin)){
		build_progress_start("Not installed",110);
		system_admin_events("Squid not seems to be installed", __FUNCTION__, __FILE__, __LINE__, "proxy");return;}	
	@chmod($squidbin,0755);
	
	
	
	$sock=new sockets();
	$DisableForceFCK=intval($sock->GET_INFO("DisableForceFCK"));
	if($DisableForceFCK==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." Will force a checkdisk At next reboot\n";}
		@touch("/forcefsck");
	}
	
	
	$pid=SQUID_PID();
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		build_progress_start("Proxy service already running since {$time}Mn",50);
		if($GLOBALS["START_PROGRESS"]){
			$php=$unix->LOCATE_PHP5_BIN();
			build_progress_start("Removing caches...",55);
			@unlink("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS");
			build_progress_start("Building caches...",70);
			system("$php /usr/share/artica-postfix/exec.status.php --all-squid");
			if(!is_file("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS")){
				build_progress_start("Fatal!! Watchdog issue!!",110);
				return;
			}
			build_progress_start("{done}",100);
			
		}
		return;
	}
	
	build_progress_start("Preparing proxy service",50);
	start_prepare();
	$squid_checks=new squid_checks();
	$squid_checks->squid_parse();
	build_progress_start("{starting_proxy_service}",60);
	$pid=SQUID_PID();
	if($GLOBALS["CRASHED"]){
		for($i=0;$i<10;$i++){
			sleep(1);
			$pid=SQUID_PID();
			if($unix->process_exists($pid)){continue;}
			break;
		}
		
		squid_admin_mysql(2,"No need to start Proxy service after a crash",
		"It seems the watchdog detect a crash but after 10s the proxy still running\nOperation is aborted",__FILE__,__LINE__);
		return;
		
	}
	
	build_progress_start("Tuning network",70);
	$unix->TCP_TUNE_SQUID_DEFAULT();
	
	
	$t1=time();
	build_progress_start("Checking caches",71);
	SendLogs("Checking caches...");
	$cacheBooster=new squidbooster();
	$cacheBooster->cache_booster();
	
	build_progress_start("Checking caches",73);
	CheckStoreDirs(true);
	SendLogs("Checking caches done...");
	
	build_progress_start("Checking Ports",75);
	SendLogs("Checking Ports...");
	$array=CheckAllports();
	SendLogs("Checking ". count($array) ." ports");
	
	while (list ($port, $ligne) = each ($array) ){
		$portZ=$unix->PIDOF_BY_PORT($port);
		SendLogs("Checking port $port - ". count($portZ) ." process(es)");
		if(count($portZ)>0){
			while (list ($pid, $ligne) = each ($portZ) ){
			SendLogs("Checking port $port - killing pid $pid");
			shell_exec("kill -9 $pid >/dev/null 2>&1");
			}
		}
		
	}
	build_progress_start("Checking SHM",75);
	system("$php /usr/share/artica-postfix/exec.squid.smp.php");
	SendLogs("Starting squid $squidbin....");
	$echo=$unix->find_program("echo");
	$size=round(@filesize("/var/log/squid/cache.log")/1024,2)/1024;
	if($size>50){
		squid_admin_mysql(2, "Cleaning cache.log {$size}MB", null,__FILE__,__LINE__);
		@copy("/var/log/squid/cache.log", "/var/log/squid/cache.log.".time());
		shell_exec("$echo \" \"> /var/log/squid/cache.log 2>&1");
	}
	
	
	
	
	
	@chmod($squidbin,0755);
	@chmod("/var/log/squid",0755);
	if(is_link("/var/log/squid")){ @chmod(readlink("/var/log/squid"),0755); }
	squid_admin_mysql(1,"Starting Squid-cache service $suffix",@implode("\n", $GLOBALS["LOGS"]),__FILE__,__LINE__);
	
	build_progress_start("Remove SystemV5 Memory",80);
	kill_shm();
	CHECK_WIFIDOG_IPTABLES_RULES();
	$PIDFILE=$unix->LOCATE_SQUID_PID();
	$f=array();
	$f[]="#! /bin/sh";
	$f[]=". /lib/lsb/init-functions";
	$f[]="PATH=/bin:/usr/bin:/sbin:/usr/sbin";
	
	$f[]="DAEMON=\"$squidbin\"";
	$f[]="CONFIG=\"/etc/squid3/squid.conf\"";
	$f[]="SQUID_ARGS=\"-YC -f \$CONFIG\"";
	$f[]="PIDFILE=\"$PIDFILE\"";
	$f[]="";
	$f[]="KRB5RCACHETYPE=none";
	$f[]="KRB5_KTNAME=/etc/squid3/PROXY.keytab";
	$f[]="export KRB5RCACHETYPE";
	$f[]="export KRB5_KTNAME";
	$f[]="";
	$f[]="";
	$f[]="umask 027";
	$f[]="ulimit -n 65535";
	$f[]="start-stop-daemon --start --pidfile \$PIDFILE --exec \$DAEMON -- \$SQUID_ARGS";
	$f[]="status=\$?";
	$f[]="if [ \$status -eq 0 ]";
	$f[]="	then";
	$f[]="	        echo \"Success starting Proxy service\"";
	$f[]="	fi ";
	$f[]="exit 0\n";
	@file_put_contents("/usr/sbin/squid-start", @implode("\n", $f));
	$f=array();
	@chmod("/usr/sbin/squid-start",0755);
	exec("/usr/sbin/squid-start 2>&1",$GLOBALS["LOGS"]);
	
	$PRC=40;	
	$MAXPRC=60;
	$AB=0;
	$TESTFAILED=false;
	
	while (list ($index, $line) = each ($GLOBALS["LOGS"]) ){
		if(preg_match("#FATAL: Bungled#", $line)){
			squid_admin_mysql(1,"Alert: Bungled configuration when starting Proxy",$line,__FILE__,__LINE__);
			$TESTFAILED=true;
			break;
		}
		
		
	}
	if($TESTFAILED){
		$TESTFAILED=false;
		
		if(!is_file("/etc/artica-postfix/SQUID_TEST_FAILED")){
			build_progress_start("Reconfigure Proxy service",80);
			system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
		}
		$GLOBALS["LOGS"]=array();
		exec("$squidbin -f /etc/squid3/squid.conf 2>&1",$GLOBALS["LOGS"]);
		while (list ($index, $line) = each ($GLOBALS["LOGS"]) ){
			if(preg_match("#FATAL: Bungled#", $line)){
				squid_admin_mysql(1,"Alert: Bungled configuration after reconfiguring Proxy",$line,__FILE__,__LINE__);
				$TESTFAILED=true;
				break;
			}
		}
	}
	
	if($TESTFAILED){
		@touch("/etc/artica-postfix/SQUID_TEST_FAILED");
		build_progress_start("Start Proxy service {failed}",110);
		die();
	}
	
	
	
	@unlink("/etc/artica-postfix/SQUID_TEST_FAILED");
	
	
	for($i=0;$i<10;$i++){
		$PRC++;
		if($PRC>$MAXPRC-1){$PRC=$MAXPRC-1;}
		build_progress_start("{starting_service} $i/10",85);
		build_progress_restart("{starting_service}", $PRC);
		$pid=SQUID_PID();
		if($unix->process_exists($pid)){SendLogs("Starting squid started pid $pid...");break;}
		ToSyslog("Starting squid waiting $i/10s");
		SendLogs("Starting squid waiting $i/10s");
		sleep(1);
	}
	
	if(!$unix->process_exists($pid)){
		build_progress_start("{failed}",110);
		SendLogs("Starting Squid failed to start...");
		ToSyslog("Starting Squid failed to start...");
		if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
		squid_admin_mysql(0,"Squid failed to start $suffix",@implode("\n", $GLOBALS["LOGS"])."\n$executed");
	
		system_admin_events("Starting Squid failed to start\n".@implode("\n", $GLOBALS["LOGS"]), __FUNCTION__, __FILE__, __LINE__, "proxy");
		return;
	}
	
	SendLogs("Starting Squid Tests if it listen all connections....");
	for($i=0;$i<10;$i++){
		build_progress_start("{checking} $i/10",90);
		if(is_started()){SendLogs("Starting squid listen All connections OK");break;}
		SendLogs("Starting squid listen All connections... waiting $i/10");
		sleep(1);
	}
	
	$took=$unix->distanceOfTimeInWords($t1,time());
	$nohup=$unix->find_program("nohup");
	SendLogs("Starting Squid success to start PID $pid...");
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
	$php5=$unix->LOCATE_PHP5_BIN();
	taskset();
	build_progress_start("Restarting cache-tail",91);
	shell_exec("$nohup /etc/init.d/cache-tail restart >/dev/null 2>&1 &");
	build_progress_start("Restarting access-tail",92);
	shell_exec("$nohup /etc/init.d/squid-tail restart >/dev/null 2>&1 &");
	build_progress_start("Restarting auth-tail",93);
	shell_exec("$nohup /etc/init.d/auth-tail restart >/dev/null 2>&1 &");
	build_progress_start("{done}",100);
	system_admin_events("Starting Squid success to start PID $pid took $took\n".@implode("\n", $GLOBALS["LOGS"]), __FUNCTION__, __FILE__, __LINE__, "proxy");
	SendLogs("Starting Squid done...");
	if(is_file("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS")){@unlink("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS");}
	
}

function CheckMySQL(){
	$unix=new unix();
	$sock=new sockets();
	$EnableSquidRemoteMySQL=$sock->GET_INFO("EnableSquidRemoteMySQL");
	$ProxyUseArticaDB=$sock->GET_INFO("ProxyUseArticaDB");
	$squidEnableRemoteStatistics=$sock->GET_INFO("squidEnableRemoteStatistics");
	if(!is_numeric($squidEnableRemoteStatistics)){$squidEnableRemoteStatistics=0;}
	if(!is_numeric($ProxyUseArticaDB)){$ProxyUseArticaDB=0;}
	if($EnableSquidRemoteMySQL==1){return true;}
	if($squidEnableRemoteStatistics==1){return true;}
	if($ProxyUseArticaDB==0){return true;}
	
	$filetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$time=$unix->file_time_min($filetime);
	$WORKDIR=$sock->GET_INFO("SquidStatsDatabasePath");
	if($WORKDIR==null){$WORKDIR="/opt/squidsql";}
	if(is_link($WORKDIR)){$WORKDIR=readlink($WORKDIR);}
	
	if(!is_dir("$WORKDIR/data")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." MySQL database not prepared\n";}
		shell_exec("/etc/init.d/squid-db start");
		shell_exec("/etc/init.d/artica-status start");
		if(!is_dir("$WORKDIR/data")){return;}
	}
	
	
	
	if(!is_dir("$WORKDIR/data/squidlogs")){
		if($unix->is_socket("/var/run/mysqld/squid-db.sock")){
			include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
			$q=new mysql_squid_builder();
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." Creating database squidlogs\n";}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." Use DB: $q->ProxyUseArticaDB\n";}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." Socket: $q->SocketPath\n";}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." Server: $q->mysql_server\n";}
			$q->CREATE_DATABASE("squidlogs");
			if(!$q->ok){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." $q->mysql_error";}
				return;
			}
			if(is_dir("$WORKDIR/data/squidlogs")){return true;}
		}
			
			
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." MySQL database not prepared\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." $WORKDIR/data/squidlogs no such directory\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." Starting MySQL database\n";}
		shell_exec("/etc/init.d/squid-db start");
		shell_exec("/etc/init.d/artica-status start");
		
	}
		
		
	if(!is_dir("$WORKDIR/data/squidlogs")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." MySQL database not prepared\n";}
		return;
	}
	
	return true;
}




function is_started(){
	
	$f=file("/var/log/squid/cache.log");
	krsort($f);
	while (list ($num, $val) = each ($f) ){
		if(preg_match("#Accepting HTTP Socket connections#i", $val)){
			SendLogs("Detected:$val...");
			return true;}
		
	}
	
	return false;
	
}

function test_smtp_watchdog(){
	squid_admin_notifs("This is an SMTP tests from the configuration file", __FUNCTION__, __FILE__, __LINE__, "proxy");
}

function stop_squid_analyze($array){
while (list ($num, $ligne) = each ($array) ){
	if(preg_match("#is a subnetwork of#i", $ligne)){continue;}
	if(preg_match("#is ignored to keep splay tree#i", $ligne)){continue;}
	if(preg_match("#You should probably remove#i", $ligne)){continue;}
	if(preg_match("#Warning: empty ACL#i", $ligne)){continue;}
		
		
	if(preg_match("#No running copy#i", $ligne)){
		SendLogs("Stopping Squid-Cache service \"$ligne\" [anaylyze]");
		return true;
	}
		
	if(preg_match("#Illegal instruction#i", $ligne)){
		SendLogs("Stopping Squid-Cache service \"$ligne\"");
		return true;
	}
		
	if(preg_match("#ERROR: Could not send signal [0-9]+ to process [0-9]+.*?No such process#", $ligne)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." Squid-Cache service \"$ligne\"\n";}
		return true;
	}
		
	SendLogs("Stopping Squid-Cache service \"$ligne\"");
}
return false;
}


function stop_squid($aspid=false){
	
	
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$file=basename($trace[1]["file"]);$function=$trace[1]["function"];$line=$trace[1]["line"];$called="Called by $function() from line $line";}}
	$GLOBALS["LOGS"]=array();
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")."Already `task` running PID $pid since {$time}mn\n";}
			system_admin_events("stop_squid::Already task running PID $pid since {$time}mn", __FUNCTION__, __FILE__, __LINE__, "proxy");
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$sock=new sockets();
	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
	$MonitConfig=watchdog_config_default($MonitConfig);
	$STOP_SQUID_TIMEOUT=$MonitConfig["StopMaxTTL"];
	$STOP_SQUID_MAXTTL_DAEMON=$MonitConfig["STOP_SQUID_MAXTTL_DAEMON"];
	
	
	
	if(!is_numeric($STOP_SQUID_TIMEOUT)){$STOP_SQUID_TIMEOUT=60;}
	if(!is_numeric($STOP_SQUID_MAXTTL_DAEMON)){$STOP_SQUID_MAXTTL_DAEMON=5;}
	if($STOP_SQUID_TIMEOUT<5){$STOP_SQUID_TIMEOUT=5;}
	
	
	$squidbin=$unix->find_program("squid");
	$kill=$unix->find_program("kill");
	$pgrep=$unix->find_program("pgrep");
	
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
	if(!is_file($squidbin)){
		system_admin_events("Squid not seems to be installed", __FUNCTION__, __FILE__, __LINE__, "proxy");
		return;
	}
	$suffix=" (by unknown process)";
	if($GLOBALS["MONIT"]){$suffix=" (by system monitor)";}
	if($GLOBALS["CRASHED"]){$suffix= " ( after a crash )";}
	if($GLOBALS["BY_CACHE_LOGS"]){$suffix= " ( ordered by logs monitor )";}
	if($GLOBALS["BY_STATUS"]){$suffix=" ( by Artica monitor )";}
	if($GLOBALS["BY_CLASS_UNIX"]){$suffix=" (by Artica class.unix.inc)";}
	if($GLOBALS["BY_FRAMEWORK"]){$suffix=" (by Artica framework)";}
	if($GLOBALS["BY_OTHER_SCRIPT"]){$suffix=" (by other script)";}
	if($GLOBALS["BY_ARTICA_INSTALL"]){$suffix=" (by artica-install)";}
	if($GLOBALS["BY_FORCE_RECONFIGURE"]){$suffix=" (after building settings)";}
	
	if($GLOBALS["MONIT"]){
		if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$file=basename($trace[1]["file"]);$function=$trace[1]["function"];$line=$trace[1]["line"];$called="Called by $function() from line $line";}}
		$pid=SQUID_PID();
		if($unix->process_exists($pid)){
			$ps=$unix->find_program("ps");
			$grep=$unix->find_program("grep");
			exec("$ps aux|$grep squid 2>&1",$results);
			return;
		}
		squid_admin_mysql(2, "Monit ordered to stop squid",$called);
		
	}
	
	if($GLOBALS["BY_ARTICA_INSTALL"]){
		$pid=SQUID_PID();
		if($unix->process_exists($pid)){
			$ps=$unix->find_program("ps");
			$grep=$unix->find_program("grep");
			exec("$ps aux|$grep squid 2>&1",$results);
			return;
		}
		squid_admin_mysql(2, "artica-install ordered to stop squid",$called);
		
	}
	
	$t1=time();
	$pid=SQUID_PID();
	if(!$GLOBALS["FORCE"]){
		if($unix->process_exists($pid)){
			$timeTTL=$unix->PROCCESS_TIME_MIN($pid);
			if($timeTTL<$STOP_SQUID_MAXTTL_DAEMON){
				if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")."Squid live since {$timeTTL}Mn, this is not intended to stop before {$STOP_SQUID_MAXTTL_DAEMON}Mn\n";}
				Events("Squid live since {$timeTTL}Mn, this is not intended to stop before {$STOP_SQUID_MAXTTL_DAEMON}Mn");
				if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$file=basename($trace[1]["file"]);$function=$trace[1]["function"];$line=$trace[1]["line"];$called="Called by $function() from line $line";}}
				reload_squid(true);
				return;
			}
			
		}
	}
	
	
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." Squid-Cache service Already stopped...\n";}
		KillGhosts();
		return;
	}
	

	
	
	
	$date=date("Y-m-d H:i:s");
	
	$timeTTL=$unix->PROCCESS_TIME_MIN($pid);
	squid_admin_mysql(0, "Stopping Squid-Cache service: running since {$timeTTL}Mn $suffix","$suffix - $date\nSquid live since {$timeTTL}Mn and a process ask to stop it\n$called",__FILE__,__LINE__);
	
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." Squid-Cache service PID $pid running since {$timeTTL}Mn....\n";}
	
	$commut="shutdown";
	
	if($GLOBALS["KILL_ALL"]){
		$commut="kill";
	}
	
	exec("$squidbin -f /etc/squid3/squid.conf -k $commut 2>&1",$shutdown);
	$KILLER=false;
	if($GLOBALS["FORCE"]){$STOP_SQUID_TIMEOUT=30;}
	if($GLOBALS["KILL_ALL"]){$STOP_SQUID_TIMEOUT=2;}
	if(stop_squid_analyze($shutdown)){$STOP_SQUID_TIMEOUT=1;$KILLER=true;}
	$PRC=20;	
	$MAXPRC=30;
	$AB=0;
	for($i=0;$i<$STOP_SQUID_TIMEOUT;$i++){
		sleep(1);$PRC++;
		if($PRC>$MAXPRC-1){$PRC=$MAXPRC-1;}
		build_progress_restart("{stopping_service}", $PRC);
		$STOPIT=false;
		$task=null;
		$pid=SQUID_PID();
		
		if(!$unix->process_exists($pid)){break;}
		$cmdline=@file_get_contents("/proc/$pid/cmdline");
		if(preg_match("#\((.+?)\)-#", $cmdline,$re)){$task=$re[1];}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." Squid-Cache service waiting $i seconds (max $STOP_SQUID_TIMEOUT) for $pid PID Task:$task....\n";}
		$shutdown=array();
		if($STOPIT){break;}
	}
	
	
	$pid=SQUID_PID();
	if($unix->process_exists($pid)){
		exec("$squidbin -f /etc/squid3/squid.conf -k kill >/dev/null 2>&1");
		if($GLOBALS["FORCE"]){$STOP_SQUID_TIMEOUT=20;}
		if($GLOBALS["KILL_ALL"]){$STOP_SQUID_TIMEOUT=2;}
		if(stop_squid_analyze($shutdown)){$STOP_SQUID_TIMEOUT=1;}
		if($KILLER){$STOP_SQUID_TIMEOUT=1;}
		
		for($i=0;$i<$STOP_SQUID_TIMEOUT;$i++){
			if($GLOBALS["OUTPUT"]){echo "Killing.......: ".date("H:i:s")." Squid-Cache service waiting $i/$STOP_SQUID_TIMEOUT seconds for $pid PID Task:$task....\n";}
			sleep(1);
			$pid=SQUID_PID();
			if(!$unix->process_exists($pid)){break;}
		}
		
	}
	
	$pidof=$unix->find_program("pidof");
	$kill=$unix->find_program("kill");	
	
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." Squid-Cache service PID(s): ".exec("$pidof $squidbin 2>&1")."\n";}
		
	}
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." Squid-Cache service Search ghost processes...\n";}
	$pids=explode(" ", exec("$pidof $squidbin 2>&1"));
	if($GLOBALS["VERBOSE"]){echo "exec($pidof $squidbin 2>&1) = `".exec("$pidof $squidbin 2>&1")."`";}
	
	
	while (list ($num, $pid) = each ($pids) ){
		if(!is_numeric($pid)){continue;}
		if($pid<10){continue;}
		if(!$unix->process_exists($pid)){continue;}
		$cmdline=trim(@file_get_contents("/proc/$pid/cmdline"));
		if(preg_match("#\((.+?)\)-#", $cmdline,$re)){$task=$re[1];}	
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." Squid-Cache service Killing ghost task pid $pid `$task`\n";}
		unix_system_kill($pid);
		if($unix->process_exists($pid)){
			for($i=0;$i<4;$i++){
				if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." Squid-Cache service waiting $i seconds (max 3) for $pid PID Task:$task....\n";}
				if(!$unix->process_exists($pid)){break;}
				sleep(1);
			}
		}
		if($unix->process_exists($pid)){unix_system_kill_force($pid);}
		
		
	}
	
	KillGhosts();
	kill_shm();
	@unlink("/var/run/squid/squid.pid");
	
	if(is_file("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS")){@unlink("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS");}
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
	system_admin_events("Squid success to stop\n".@implode("\n", $GLOBALS["LOGS"]), __FUNCTION__, __FILE__, __LINE__, "proxy");
	
}

function killufdbgClientPHP(){
	$unix=new unix();
	$pgrep=$unix->find_program("pgrep");
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." Squid-Cache service seems stopped search ufdbgclient processes...\n";}
	exec("$pgrep -l -f \"/ufdbgclient.php\" 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#pgrep#", $ligne)){continue;}
		if(!preg_match("#^([0-9]+)\s+(.+)#",$ligne,$re)){SendLogs("Skipping $ligne");continue;}
		$pid=$re[1];
		$cmdline=$re[2];
		unix_system_kill_force($pid);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." Artica webfiltering process \"$cmdline\" process PID $pid\n";}
	
	}
	
}

function kill_shm(){
	if(!is_dir("/run/shm")){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." Squid-Cache service /dev/shm no such directory\n";}
		return;
	}
	$unix=new unix();
	$files=$unix->DirFiles("/run/shm","squid-.*?\.shm");
	while (list ($num, $filename) = each ($files) ){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." Squid-Cache service removing $filename\n";}
		@unlink("/run/shm/$filename");
		
	}
}

function KillGhosts(){
	$unix=new unix();
	$pgrep=$unix->find_program("pgrep");
	exec("$pgrep -l -f \"squid.*?-[0-9]+\)\" 2>&1",$results);
	
	
	
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#pgrep#", $ligne)){continue;}
		if(!preg_match("#^([0-9]+)\s+\(.+)#", $ligne,$re)){SendLogs("Skipping $ligne");continue;}
		$pid=$re[1];
		$cmdline=$re[2];
		unix_system_kill_force($pid);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." Squid-Cache process \"$cmdline\" process PID $pid\n";}
	
	}
	
	killufdbgClientPHP();
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." Squid-Cache service seems stopped search ntlm_auth processes...\n";}
	exec("$pgrep -l -f \"ntlm_auth.*?--helper-proto\" 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#pgrep#", $ligne)){continue;}
		if(!preg_match("#^([0-9]+)\s+\(ntlm_auth#", $ligne,$re)){SendLogs("Skipping $ligne");continue;}
		$pid=$re[1];
		unix_system_kill_force($pid);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." Squid-Cache service ntlm_auth process PID $pid\n";}
	
	}
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." Squid-Cache service seems stopped search external_acl_squid processes..\n";}
	exec("$pgrep -l -f \"external_acl_squid.php\" 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#pgrep#", $ligne)){continue;}
		if(!preg_match("#^([0-9]+)\s+.*#", $ligne,$re)){continue;}
		$pid=$re[1];
		unix_system_kill_force($pid);
		SendLogs("Stopping external_acl_squid process PID $pid");
	
	}
	
	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." Squid-Cache service seems stopped search $squidbin processes...\n";}
	exec("$pgrep -l -f \"$squidbin\" 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#pgrep#", $ligne)){continue;}
		if(preg_match("#squid27#", $ligne)){continue;}
		if(!preg_match("#^([0-9]+)\s+.*#", $ligne,$re)){continue;}
		$pid=$re[1];
		unix_system_kill_force($pid);
		SendLogs("Stopping squid process PID $pid");
	
	}
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." Squid-Cache service seems stopped search $squidbin (sub-daemons) processes...\n";}
	exec("$pgrep -l -f \"\(squid-[0-9]+\)\" 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#pgrep#", $ligne)){continue;}
		if(!preg_match("#^([0-9]+)\s+.*#", $ligne,$re)){continue;}
		$pid=$re[1];
		unix_system_kill_force($pid);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." Squid-Cache service squid process PID $pid\n";}
	
	}	
}


function builduri($cmd){
	
	if(!isset($GLOBALS["builduri"])){
	$sock=new sockets();
	$SquidMgrListenPort=trim($sock->GET_INFO("SquidMgrListenPort"));
	
	if( !is_numeric($SquidMgrListenPort) OR ($SquidMgrListenPort==0) ){
		$SquidBinIpaddr=$sock->GET_INFO("SquidBinIpaddr");
		if($SquidBinIpaddr==null){$SquidBinIpaddr="127.0.0.1";}
		$http_port=squid_get_alternate_port();
	
		if(preg_match("#(.+?):([0-9]+)#", $http_port,$re)){
			$SquidBinIpaddr=$re[1];
			if($SquidBinIpaddr=="0.0.0.0"){$SquidBinIpaddr="127.0.0.1";}
			$http_port=$re[2];
		}
	
	}else{
		$SquidBinIpaddr="127.0.0.1";
		$http_port=$SquidMgrListenPort;
	}
	$GLOBALS["builduri"]="http://$SquidBinIpaddr:$http_port/squid-internal-mgr";
	}
	
	return $GLOBALS["builduri"]."/$cmd";
}
function CurlGet($cmd){
	
	if(is31()){
		if($GLOBALS["VERBOSE"]){echo "squidclient($cmd)\n";}
		$data=squidclient($cmd);
		if($GLOBALS["VERBOSE"]){echo "squidclient($cmd) -> ".strlen($data)." bytes\n";}
		return $data;
	}
	
	if($GLOBALS["VERBOSE"]){echo "builduri($cmd)\n";}
	$curl=new ccurl(builduri($cmd));
	$curl->ArticaProxyServerEnabled=="no";
	$curl->interface="127.0.0.1";
	$curl->Timeout=5;
	$curl->UseDirect=true;
	if(!$curl->get()){return;}	
	return $curl->data;
	
}

function counters($aspid=false){
	$cacheFile="/usr/share/artica-postfix/ressources/logs/web/squid.counters.db";
	if(!is_dir(dirname($cacheFile))){@mkdir(dirname($cacheFile),0755,true);}
	
	$unix=new unix();
	if(!$GLOBALS["VERBOSE"]){
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")."Already `task` running PID $pid since {$time}mn\n";}
			
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}	
	
	}
	$timefile=$unix->file_time_min($cacheFile);
	if($GLOBALS["VERBOSE"]){echo basename($cacheFile)." {$timefile}mn\n";}
	
	if(!$GLOBALS["FORCE"]){
		if($timefile<5){if(!$GLOBALS["VERBOSE"]){return;}}
	}
	
	$sock=new sockets();
	
	
	$datas=explode("\n",CurlGet("5min"));
	
	
	while (list ($num, $ligne) = each ($datas) ){
		$ligne=trim($ligne);
		if(!preg_match("#(.+?)=(.+)#", $ligne,$re)){continue;}
		$ARRAY[trim($re[1])]=trim($re[2]);
		
	}

	$datas=explode("\n",CurlGet("active_requests"));
	@file_put_contents("/var/log/squid/monitor.sessions.cache", serialize($datas));
	
	$c=0;
	while (list ($num, $ligne) = each ($datas) ){
		$ligne=trim($ligne);
		
		if(!preg_match("#Connection:\s+(.+)#", $ligne,$re)){continue;}
		if(trim($re[1])=="close"){continue;}
		$c++;
	
	}

	
	
	$ARRAY["active_requests"]=$c;
	$ARRAY["SAVETIME"]=time();
	@unlink($cacheFile);
	@file_put_contents($cacheFile, serialize($ARRAY));
	@chmod($cacheFile, 0775);
	peer_status(true);
	
	
}

function SendLogs($text){
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." $text\n";}
	$GLOBALS["LOGS"][]=$text;
}
function squid_get_alternate_port(){
	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	while (list ($num, $ligne) = each ($f) ){
		if(preg_match("#(transparent|tproxy|intercept)#i", trim($ligne))){continue;}
		if(preg_match("#http_port\s+([0-9]+)$#", trim($ligne),$re)){return $re[1];}
		if(preg_match("#http_port\s+([0-9\.]+):([0-9]+)$#", trim($ligne),$re)){return "{$re[1]}:{$re[2]}";}
			
		if(preg_match("#http_port\s+([0-9]+)\s+#", trim($ligne),$re)){return $re[1];}
		if(preg_match("#http_port\s+([0-9\.]+):([0-9]+)\s+#", trim($ligne),$re)){return "{$re[1]}:{$re[2]}";}
	}

}
function squid_watchdog_events($text){
	$unix=new unix();
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefile=basename($trace[1]["file"]);$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];}}
	$unix->events($text,"/var/log/squid.watchdog.log",false,$sourcefunction,$sourceline);
}

function is_peer(){
	
	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	while (list ($num, $directory) = each ($f)){
		if(preg_match("#^cache_peer\s+#", $directory)){return true;}
	}
	return false;
	
}

function root_squid_version(){
	if(isset($GLOBALS["root_squid_version"])){return $GLOBALS["root_squid_version"];}
	$unix=new unix();
	$squidbin=$unix->find_program("squid");
	if($squidbin==null){$squidbin=$unix->find_program("squid3");}
	exec("$squidbin -v 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#Squid Cache: Version.*?([0-9\.\-a-z]+)#", $val,$re)){
			$GLOBALS["root_squid_version"]= trim($re[1]);
			return $GLOBALS["root_squid_version"];
		}
	}

}

function squidclient($cmd){
	
	if(!isset($GLOBALS["SQUIDCLIENT"])){
	$sock=new sockets();
	$SquidMgrListenPort=trim($sock->GET_INFO("SquidMgrListenPort"));
	$unix=new unix();
	if( !is_numeric($SquidMgrListenPort) OR ($SquidMgrListenPort==0) ){
		$SquidBinIpaddr=$sock->GET_INFO("SquidBinIpaddr");
		if($SquidBinIpaddr==null){$SquidBinIpaddr="127.0.0.1";}
		$http_port=squid_get_alternate_port();
	
		if(preg_match("#(.+?):([0-9]+)#", $http_port,$re)){
			$SquidBinIpaddr=$re[1];
			if($SquidBinIpaddr=="0.0.0.0"){$SquidBinIpaddr="127.0.0.1";}
			$http_port=$re[2];
		}
	
	}else{
		$SquidBinIpaddr="127.0.0.1";
		$http_port=$SquidMgrListenPort;
	}
		$squidclient=$unix->find_program("squidclient");
		$GLOBALS["SQUIDCLIENT"]="$squidclient -T 5 -h 127.0.0.1 -p $http_port mgr";
			
	}
	
	exec($GLOBALS["SQUIDCLIENT"].":$cmd 2>&1",$results);
	
	
	if($GLOBALS["VERBOSE"]){echo $GLOBALS["SQUIDCLIENT"].":$cmd ". count($results)." lines\n";}
	return @implode("\n", $results);
	
}

function is31(){
	if(isset($GLOBALS["is31"])){return $GLOBALS["is31"];}
	$root_squid_version=root_squid_version();
	if($GLOBALS["VERBOSE"]){echo "Version: $root_squid_version\n";}
	$data=null;
	$GLOBALS["is31"]=false;
	$VER=explode(".",$root_squid_version);
	if($VER[0]<4){
		if($VER[1]<2){
			if($GLOBALS["VERBOSE"]){echo "$root_squid_version -> is 3.1.x\n";}
			$GLOBALS["is31"]=true;return true;}
	}
	return false;
	
}
function peer_status($aspid=false){
	if($GLOBALS["VERBOSE"]){echo "peer_status();\n";}
	$unix=new unix();
	$cacheFile="/usr/share/artica-postfix/ressources/logs/web/squid.peers.db";
	if(!is_dir(dirname($cacheFile))){@mkdir(dirname($cacheFile),0755,true);}
	
	if(!$GLOBALS["DUMP"]){
		if(!$GLOBALS["VERBOSE"]){
			$unix=new unix();
			if(!$aspid){
				$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
				$pid=$unix->get_pid_from_file($pidfile);
				if($unix->process_exists($pid,basename(__FILE__))){
					$time=$unix->PROCCESS_TIME_MIN($pid);
					if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")."Already `task` running PID $pid since {$time}mn\n";}
						
					return;
				}
				@file_put_contents($pidfile, getmypid());
			}
			
		}
	
	}
	
	$timefile=$unix->file_time_min($cacheFile);
	if($GLOBALS["VERBOSE"]){echo basename($cacheFile)." {$timefile}mn\n";}
	if(!$GLOBALS["DUMP"]){
		if(!$GLOBALS["FORCE"]){
			if(!$GLOBALS["VERBOSE"]){if($timefile<5){return;}}
		}	
	}
	
	
	
	if(!is_peer()){
		if($GLOBALS["DUMP"]){echo "No cache_peer\n";return;}
		if($GLOBALS["VERBOSE"]){echo "No cache_peer...\n";}return;}
	$sock=new sockets();

	
	$datas=trim($unix->squidclient("server_list"));
	if($GLOBALS["DUMP"]){echo $datas."\n";return;}
	if($datas==null){return;}
	$tr=explode("\n",$datas);
	
	
	while (list ($num, $val) = each ($tr)){
		if($GLOBALS["VERBOSE"]){echo "Found: \"$val\"\n";}
		if(preg_match("#Parent\s+:(.+)#", $val,$re)){
			$peer=trim($re[1]);
			continue;
		}
		
		
		if(preg_match("#(.+?)\s+:(.*)#", $val,$re)){
			$key=strtoupper(trim($re[1]));
			$array[$peer][$key]=trim($re[2]);
		}
		
	}
	
	if($GLOBALS["VERBOSE"]){
		echo count($array)." peers detected\n";
	}
	
	@unlink($cacheFile);
	@file_put_contents($cacheFile, serialize($array));
	@chmod($cacheFile,0777);
	
	
}

function peer_dead($parent){
	
	$sock=new sockets();
	$detected=false;
	$DisableDeadParents=$sock->GET_INFO("DisableDeadParents");
	if(!is_numeric($DisableDeadParents)){$DisableDeadParents=0;}
	if($DisableDeadParents==0){return;}
	SendLogs("Parent $parent should be removed....");
	$parent_regex=str_replace(".", "\.", $parent);
	$tr=explode("\n", @file_get_contents("/etc/squid3/squid.conf"));
	while (list ($num, $val) = each ($tr)){
		if(preg_match("#^cache_peer\s+$parent_regex#", $val)){
			SendLogs("Mark Removing $val...");
			$tr[$num]="#$val";
			$detected=true;
			break;
		}
	}
	if($detected){
		@file_put_contents("/etc/squid3/squid.conf", @implode("\n", $tr));
		$unix=new unix();
		SendLogs("Reconfiguring Squid in order to remove \"$parent\"....");
		squid_admin_mysql(1, "Reconfiguring Squid in order to remove \"$parent\"",null,__FILE__,__LINE__);
		reload_squid(true);
		$php5=$unix->LOCATE_PHP5_BIN();
	}
	
	
	$DisableDeadParentsSQL=$sock->GET_INFO("DisableDeadParentsSQL");
	if(!is_numeric($DisableDeadParentsSQL)){$DisableDeadParentsSQL=0;}
	if($DisableDeadParentsSQL==0){return;}
	$q=new mysql();
	SendLogs("Stamp parent $parent to disabled in database..");
	$sql="UPDATE squid_parents SET enabled=0 WHERE servername='$parent'";
	$q->QUERY_SQL($sql,"artica_backup");
}


function squid_cache_mem_current(){
	$unix=new unix();
	$results=explode("\n",$unix->squidclient("info"));
	
	while (list($num,$ligne)=each($results)){
		
		if(preg_match("#Storage Mem size:\s+([0-9]+)\s+([A-Z]+)#", $ligne,$re)){
			if(strtoupper($re[2])=="MB"){
				$re[1]=$re[1]*1024;
			}
			if(strtoupper($re[2])=="GB"){
				$re[1]=$re[1]*1024;
				$re[1]=$re[1]*1024;
				
			}
			$array["CUR"]=$re[1];
		}
		
		if(!preg_match("#Storage Mem capacity:\s+([0-9\.]+).*?used,\s+([0-9\.]+).*?free#", $ligne,$re)){
			if($GLOBALS["VERBOSE"]){echo $ligne." no match\n";}
			continue;}
		$array["USED"]=$re[1];
		$array["FREE"]=$re[2];
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/Storage.Mem.capacity", serialize($array));
		@chmod("/usr/share/artica-postfix/ressources/logs/web/Storage.Mem.capacity",0755);
		return;
	}
	
	
}

function squid_get_storage_info(){
	$CURCAP=null;
	if($GLOBALS["VERBOSE"]){
		echo " **** FORCED MODE ****\n";
		$GLOBALS["OUTPUT"]=true;$GLOBALS["FORCE"]=true;
	}else{
		if($GLOBALS["FORCE"]){
			echo " **** FORCED MODE ****\n";
		}
	}
	if(isset($GLOBALS["squid_get_storage_info"])){return $GLOBALS["squid_get_storage_info"];}
	$unix=new unix();
	$dats=null;
	$StoreDirCache="/etc/squid3/squid_storedir_info.db";
	if(!$GLOBALS["FORCE"]){
		if($unix->file_time_min($StoreDirCache)<10){
			$dats=unserialize(@file_get_contents($StoreDirCache));
		}
	}


	if(!is_array($dats)){$dats=array();}
	if(count($dats)<1){
		if($GLOBALS["OUTPUT"]){echo "Ask to service: storedir...\n";}
		$results=explode("\n",$unix->squidclient("storedir"));
		if(!$GLOBALS["FORCE"]){
			if($GLOBALS["OUTPUT"]){echo "Not an array, abort\n";}
			writelogs_framework("$StoreDirCache not an array  = ".count($results)." items...",__FUNCTION__,__FILE__,__LINE__);
		}
		$dirs=0;
		while (list($num,$ligne)=each($results)){
			
			
			
			if(preg_match("#Current Capacity.*?:\s+([0-9\.]+)%\s+used#",$ligne,$re)){
				$CURCAP=trim($re[1]);
				if($GLOBALS["OUTPUT"]){echo "* * * * * * * * *\nDISK \"$ligne\"\nDISK Current Capacity: $CURCAP\n* * * * * * * * *\n";}
				continue;}
			if(preg_match("#Store Directory.*?:\s+(.+)#", $ligne,$re)){
				$StoreDir=trim($re[1]);
				if($StoreDir==null){continue;}
				if($GLOBALS["OUTPUT"]){echo " \n\n******************************************\n";}
				if($GLOBALS["VERBOSE"]){echo "\"$ligne\" => $StoreDir\n";}
				$dirs++;
				continue;
			}
			
			if(preg_match("#Filesystem Space in use:\s+([0-9]+)\/([0-9]+)#",$ligne,$re)){
				if(isset($dats[$StoreDir]["USED"])){continue;}
				$dats[$StoreDir]["USED"]=$re[2];
				continue;
			}
			
			
			
			
			
			if(preg_match("#Percent Used:\s+([0-9\.]+)%#", $ligne,$re)){
				if($StoreDir==null){continue;}
				$dats[$StoreDir]["PERC"]=$re[1];
				continue;
			}
			if(preg_match("#Maximum Size:\s+([0-9\.]+)#", $ligne,$re)){
				if($StoreDir==null){continue;}
				$dats[$StoreDir]["SIZE"]=intval($re[1]);
				if($GLOBALS["OUTPUT"]){echo "DISK $StoreDir Maximum Size: {$re[1]}\n";}
				continue;
			
			}
			
			if(preg_match("#Current Size:\s+([0-9\.]+)#", $ligne,$re)){
				if($GLOBALS["OUTPUT"]){echo "* * * * * * * * *\nDETECTED \"$ligne\"\nDISK $StoreDir Current Size: ". intval($re[1])."\n* * * * * * * * *\n";}
				$dats[$StoreDir]["USED"]=intval($re[1]);
				continue;
			}
			
				
			if(preg_match("#Current entries:\s+([0-9\.]+)\s+([0-9\.]+)%#",$ligne,$re)){
				if($GLOBALS["OUTPUT"]){echo "* * * * * * * * *\nDISK \"$ligne\"\nDISK $StoreDir Current entries:{$re[1]} {$re[2]}%\n* * * * * * * * *\n";}
				if($StoreDir==null){continue;}
				$dats[$StoreDir]["ENTRIES"]=$re[1];
				$dats[$StoreDir]["PERC"]=$re[2];
				continue;}
				
			
			if(preg_match("#Filesystem Space in use:\s+([0-9]+)\/#",$ligne,$re)){
				if($StoreDir==null){continue;}
				if(isset($dats[$StoreDir]["USED"])){continue;}
				if($GLOBALS["OUTPUT"]){echo "* * * * * * * * *\nDISK \"$ligne\"\nDISK (2) $StoreDir Current Size: {$re[1]}\n* * * * * * * * *\n";}
				$dats[$StoreDir]["USED"]=intval($re[1]);
			}
			
			
			if($GLOBALS["VERBOSE"]){echo "No trapped \"$ligne\"\n";}

		}

		if($dirs==0){
			if($CURCAP<>null){
				$dats["CURCAP"]=$CURCAP;
			}
		}

		@unlink($StoreDirCache);
		if(is_array($dats)){
			if($GLOBALS["OUTPUT"]){echo "Saving new array\n";}
			if($GLOBALS["VERBOSE"]){print_r($dats);}
			writelogs_framework("Saving new array in $StoreDirCache",__FUNCTION__,__FILE__,__LINE__);
			file_put_contents($StoreDirCache, serialize($dats));
		}
	}
	$GLOBALS["squid_get_storage_info"]=base64_encode(serialize($dats));
	return $GLOBALS["squid_get_storage_info"];
}

function caches_infos(){
	$unix=new unix();
	
	$squid_pid=SQUID_PID();
	if(!$unix->process_exists($squid_pid)){
		$nohup=$unix->find_program("nohup");
		squid_admin_mysql(0, "Squid-Cache is not running...", null,__FILE__,__LINE__);
		return;
	}

	

	$ttl=$unix->PROCCESS_TIME_MIN($squid_pid);
	if($unix->PROCCESS_TIME_MIN($squid_pid)<5){
		ToSyslog("caches_infos(): squid-cache running only since {$ttl}mn, aborting");
		return;
	}


	ToSyslog("caches_infos(): Starting get Squid-cache informations.");

	$array=$unix->squid_get_cache_infos();

	for($i=0;$i<10;$i++){
		$check=true;
			
		if(!is_array($array)){
			if($GLOBALS["VERBOSE"]){echo "unix->squid_get_cache_infos() Not an array...\n";}
			$check=false;
			sleep(1);
			$array=$unix->squid_get_cache_infos();
			continue;

		}
			
		if(count($array)==0){
			if($GLOBALS["VERBOSE"]){echo "unix->squid_get_cache_infos() O items !!\n";}
			$check=false;
			sleep(1);
			$array=$unix->squid_get_cache_infos();
			continue;
		}
		if($check){
			break;
		}

	}

	if(!is_array($array)){if($GLOBALS["VERBOSE"]){echo "unix->squid_get_cache_infos() Not an array...\n";}return;}
	if(count($array)==0){if($GLOBALS["VERBOSE"]){echo basename(__FILE__)."[".__LINE__."] unix->squid_get_cache_infos() O items !!...\n";}
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/squid_get_cache_infos.db", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/web/squid_get_cache_infos.db",0755);
	return;}

	@unlink("/usr/share/artica-postfix/ressources/logs/web/squid_get_cache_infos.db");
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/squid_get_cache_infos.db", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/web/squid_get_cache_infos.db",0755);

	$q=new mysql_squid_builder();
	$uuid=$unix->GetUniqueID();

	$profix="INSERT IGNORE INTO cachestatus(uuid,cachedir,maxsize,currentsize,pourc) VALUES ";
	while (list ($directory, $arrayDir) = each ($array)){
		$directory=trim($directory);
		if($directory==null){continue;}
		if($GLOBALS["VERBOSE"]){echo "('$uuid','$directory','{$arrayDir["MAX"]}','{$arrayDir["CURRENT"]}','{$arrayDir["POURC"]}')\n";}
		$f[]="('$uuid','$directory','{$arrayDir["MAX"]}','{$arrayDir["CURRENT"]}','{$arrayDir["POURC"]}')";
	}
	if(count($f)>0){
		$q->QUERY_SQL("TRUNCATE TABLE cachestatus");
		$q->QUERY_SQL("$profix".@implode(",", $f));
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
}

function eCapClamav(){
	$sock=new sockets();
	$EnableeCapClamav=intval($sock->GET_INFO("EnableeCapClamav"));
	if($EnableeCapClamav==0){return;}
	$unix=new unix();
	$ln=$unix->find_program("ln");
	$chown=$unix->find_program("chown");
	shell_exec("$ln -sf /var/lib/clamav /usr/share/clamav >/dev/null 2>&1");
	shell_exec("$chown -R squid:squid /var/lib/clamav >/dev/null 2>&1");
	
}

function squid_stores_status($ByExec=false){
	
	
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/squid_stores_status.html";
	
	$unix=new unix();
	
	$pidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	
	if($ByExec){
		$pid=$unix->get_pid_from_file($pidFile);
		if($unix->process_exists($pid)){
			if($unix->PROCCESS_TIME_MIN($pid,10)<2){return;}
		}
		@file_put_contents($pidFile, getmypid());
	}
	
	
	
	$squid_pid=SQUID_PID();
	
	if(!$unix->process_exists($squid_pid)){
		if($GLOBALS["OUTPUT"]){echo "Squid-Cache is not running\n";}
		if(is_file("/usr/share/artica-postfix/ressources/logs/web/status.squid")){@chmod("/usr/share/artica-postfix/ressources/logs/web/status.squid",0777);}
		return;
		
	}
	
	if(!$GLOBALS["FORCE"]){
		$ttl=$unix->PROCCESS_TIME_MIN($squid_pid);
		if($ttl<1){
			if($GLOBALS["OUTPUT"]){echo "squid_stores_status(): Squid-Cache is running since {$ttl}Mn, please wait at least 1mn\n";}
			if(is_file("/usr/share/artica-postfix/ressources/logs/web/status.squid")){@chmod("/usr/share/artica-postfix/ressources/logs/web/status.squid",0777);}
			return;
		}
	}
	if($GLOBALS["OUTPUT"]){echo "squid_stores_status(): Squid-Cache is running since {$ttl}Mn\n";}
	
	
	$StoreDirs=unserialize(base64_decode(squid_get_storage_info()));
	caches_infos();
	
	
	$imgRefresh=imgtootltip("20-refresh.png","{refresh}","Loadjs('squid.store.status.php',true)");

	@unlink($cachefile);
	while (list($directory,$arrayStore)=each($StoreDirs)){
		$FROM_TIME=0;
		
		
		
		
		
		if($directory=="MEM"){continue;}
		if($directory=="CURCAP"){
			$TTR[]="<tr>
		<td style='font-weight:bold;font-size:12px' align='right'>{capacity}:</td>
		<td style='font-weight:bold;font-size:12px'>&nbsp;</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>". pourcentage($arrayStore,10)."</td>
				</tr>";
				
				
				
			continue;}
			
			$FROM_TIME=$unix->file_time_min("$directory/.");
			
			if(is_dir("$directory/00")){
				$FROM_TIME=filemtime("$directory/00/.");
			}
			
			
			
			$FROM_TIME_TEXT=$unix->distanceOfTimeInWords($FROM_TIME,time())."\n";

			$directory=basename($directory);
			$TTR[]="
			<tr>
				<td style='font-weight:bold;font-size:12px' align='right'>$directory:</td>
				<td style='font-weight:bold;font-size:12px'>". FormatBytes($arrayStore["SIZE"])."</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>". 
					pourcentage($arrayStore["PERC"],10,"green")."
					<div style='font-size:11px;text-align:right'>$FROM_TIME_TEXT</span>
				</td>
			</tr>";

	}
	
	$TTR[]="<tr><td colspan=2 align='right'>$imgRefresh</td></tr>";

	if($GLOBALS["OUTPUT"]){echo "Saving new cache file: ".basename($cachefile)."\n";}
	
	if(count($TTR)>0){
		$datas=RoundedLightGreen("<div style='min-height:147px'>
		<table style='width:100%'>".@implode($TTR, "\n")."</table></div>")."<br>";
		@mkdir(dirname($cachefile),0755);
		@unlink($cachefile);
		@file_put_contents($cachefile ,$datas);
		@chmod($datas,0755);
		
	}
	
	if(is_file("/usr/share/artica-postfix/ressources/logs/web/status.squid")){
		@chmod("/usr/share/artica-postfix/ressources/logs/web/status.squid",0777);
	}

}
function squid_get_system_info(){
	$unix=new unix();

	$fileCache="/etc/squid3/squid_get_system_info.db";
	if($unix->file_time_min($fileCache)<10){
		$dats=unserialize(@file_get_contents($fileCache));
	}
	if(!is_array($dats)){$dats=array();}
	if(count($dats)<2){
		@unlink($fileCache);
		$dats=$unix->squid_get_system_info();
		@file_put_contents($fileCache,serialize($dats));
	}

	return base64_encode(serialize($dats));
}
function squid_mem_status($ByExec=false){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$unix=new unix();
	$reboot=false;
	$users=new usersMenus();
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/squid_mem_status.html";
	$cacheSwap="/usr/share/artica-postfix/ressources/logs/web/squid_swap_status.html";
	$cacheSwapT="/usr/share/artica-postfix/ressources/logs/web/squid_swap_status.time";
	$pidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();
	
	if($ByExec){
		$pid=$unix->get_pid_from_file($pidFile);
		if($unix->process_exists($pid)){
			if($unix->PROCCESS_TIME_MIN($pid,10)<2){return;}
		}
		@file_put_contents($pidFile, getmypid());
	}
	
	
	
	$squid_pid=SQUID_PID();
	
	if(!$unix->process_exists($squid_pid)){
		if($GLOBALS["OUTPUT"]){echo "squid_mem_status(): Squid-Cache is not running\n";}
		if(is_file("/usr/share/artica-postfix/ressources/logs/web/status.squid")){@chmod("/usr/share/artica-postfix/ressources/logs/web/status.squid",0777);}
		return;
	
	}
	$ttl=$unix->PROCCESS_TIME_MIN($squid_pid);
	if($ttl<3){
		if($GLOBALS["OUTPUT"]){echo "squid_mem_status(): Squid-Cache is running since {$ttl}Mn, please wait at least 5mn\n";}
		if(is_file("/usr/share/artica-postfix/ressources/logs/web/status.squid")){@chmod("/usr/share/artica-postfix/ressources/logs/web/status.squid",0777);}
		return;		
	}
	
	
	if($GLOBALS["FORCE"]){
		@unlink($cachefile);
		@unlink($cacheSwap);
		@unlink($cacheSwapT);
	}
	
	@unlink($cachefile);
	if($users->WEBSTATS_APPLIANCE){return null;}
	$datas=unserialize(base64_decode(squid_get_system_info()));
	$StoreDirs=unserialize(base64_decode(squid_get_storage_info()));
	$freebin=$unix->find_program("free");

	$MEMSEC=$datas["Memory usage for squid via mallinfo()"];
	$Total_space_in_arena=trim($MEMSEC["Total space in arena"]);
	$Total_in_use=trim($MEMSEC["Total in use"]);

	$InternalDataStructures=$datas["Internal Data Structures"];
	$StoreEntriesWithMemObjects=$InternalDataStructures["StoreEntries with MemObjects"];
	$HotObjectCacheItems=$InternalDataStructures["Hot Object Cache Items"];
	
	exec("$freebin -m 2>&1",$results);
	while (list ($index, $ligne) = each ($results) ){
		if(preg_match("#^Swap:\s+([0-9]+)\s+([0-9]+)#", $ligne,$re)){
			$SwapTotal=$re[1];
			$SwapUsed=$re[2];
			$SwapPerc=($SwapUsed/$SwapTotal)*100;
			$SwapPerc=round($SwapPerc,1);
			break;
		}
	
	}
	
	
	@unlink($cacheSwap);
	
	if($SwapPerc>40){
		$cacheSwapTime=$unix->file_time_min($cacheSwapT);
		if($cacheSwapTime>30){
			@unlink($cacheSwapT);
			@file_put_contents($cacheSwapT, time());
			squid_admin_mysql(1,"{high_swap_value} {$SwapPerc}%","{high_swap_value_exceed_explain}");
		}
	}
	
	if($SwapPerc>10){
		$swaphtml="<div style='min-height:147px'>
		<table style='width:100%'>
				<tr>
					<td style='vertical-align:top'><img src='img/warning-panneau-42.png'></td>
					<td style='vertical-align:top'><div style='font-size:14px;font-weight:bold'>{high_swap_value} {$SwapPerc}%</div>
					<div style='font-weight:bold'>{high_swap_value_exceed_explain}</div>
				</tr>
		</table>
		</div>				
		";
		@file_put_contents($cacheSwap, RoundedLightGreen($swaphtml));
		@chmod($cacheSwap,0755);
		
	}



	$ConnectionInformationForSquid=$datas["Connection information for squid"];

	$NumberOfHTTPRequestsReceived=$ConnectionInformationForSquid["Number of HTTP requests received"];
	$AverageHTTPRequestsPerMinuteSinceStart=round($ConnectionInformationForSquid["Average HTTP requests per minute since start"]);


	$StorageMemSize=$datas["Cache information for squid"]["Storage Mem size"];
	$StorageMemCapacity=$datas["Cache information for squid"]["Storage Mem capacity"];


	preg_match("#^([0-9]+)\s+([A-Z]+)#", trim($StorageMemSize),$re);
	$StorageMemSize=round($re[1]/1024,2);

	preg_match("#([0-9\.]+)% used#", trim($StorageMemCapacity),$re);
	$StorageMemCapacityPourc=$re[1];


	preg_match("#^([0-9]+)\s+([A-Z]+)#", trim($MEMSEC["Total space in arena"]),$re);



	if($re[2]=="KB"){$Total_space_in_arena=round(($Total_space_in_arena/1024),2);}
	if($re[2]=="GB"){$Total_space_in_arena=round(($Total_space_in_arena*1024),2);}

	preg_match("#^([0-9]+)\s+([A-Z]+).*?([0-9\.]+)%#", $Total_in_use,$re);
		$USED_VALUE=$re[1];
		$USED_UNIT=$re[2];
		$USED_PRC=$re[3];
		if($USED_UNIT=="KB"){
		$USED_VALUE=round(($USED_VALUE/1024),2);
	}

	if($USED_UNIT=="GB"){$USED_VALUE=round(($USED_VALUE*1024),2);}

	$NumberOfHTTPRequestsReceived=FormatNumber($NumberOfHTTPRequestsReceived);
	$HotObjectCacheItems=FormatNumber($HotObjectCacheItems);
	$StoreEntriesWithMemObjects=FormatNumber($StoreEntriesWithMemObjects);
	if(isset($StoreDirs["MEM"])){
		$BigMem=$StoreDirs["MEM"]["SIZE"];
		$Items=$StoreDirs["MEM"]["ENTRIES"];
		if($BigMem>0){
			$MemDir="	<tr>
				<td style='font-weight:bold;font-size:12px' align='right' nowrap>{memory_cache}:</td>
				<td style='font-weight:bold;font-size:12px'>". FormatBytes($BigMem)." ($Items {items})</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>". pourcentage($StoreDirs["MEM"]["PERC"])."</td>
			</tr>	";
		}
	}

	$usersC=0;
	$ipzs=0;
	$size=0;
	$q=new mysql_squid_builder();
	$current_table="quotahours_".date('YmdH',time());
	if($q->COUNT_ROWS($current_table)>0){
		$results=$q->QUERY_SQL("SELECT COUNT(uid) as tcount FROM $current_table GROUP BY uid");
		$usersC=mysql_num_rows($results);
		$results=$q->QUERY_SQL("SELECT COUNT(ipaddr) as tcount FROM $current_table GROUP BY ipaddr");
		$ipzs=mysql_num_rows($results);
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(size) as size FROM $current_table"));
		if($ligne["size"]>0){$size=FormatBytes($ligne["size"]/1024);}
	}

	$imgRefresh=imgtootltip("20-refresh.png","{refresh}","Loadjs('squid.store.status.php',true)");
	$html="
	<div style='min-height:147px'>
		<table style='width:100%'>
			$MemDir
			<tr>
				<td style='font-weight:bold;font-size:12px' align='right'>{memory}:</td>
				<td style='font-weight:bold;font-size:12px'>$StorageMemSize&nbsp;MB</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>". pourcentage($StorageMemCapacityPourc)."</td>
			</tr>
			<tr>
				<td style='font-weight:bold;font-size:12px' align='right'>{objects}:</td>
				<td style='font-weight:bold;font-size:12px'>$StoreEntriesWithMemObjects</td>
			</tr>
			<tr>
				<td style='font-weight:bold;font-size:12px' align='right'>{hot_objects}:</td>
				<td style='font-weight:bold;font-size:12px'>$HotObjectCacheItems</td>
			</tr>
			<tr>
				<td style='font-weight:bold;font-size:12px' align='right'>{requests}:</td>
				<td style='font-weight:bold;font-size:12px'>$NumberOfHTTPRequestsReceived ({$AverageHTTPRequestsPerMinuteSinceStart} {requests}/{minute})</td>
			</tr>
			<tr>
				<td style='font-weight:bold;font-size:12px' align='right'>{members}:</td>
				<td style='font-weight:bold;font-size:12px'>$usersC</td>
			</tr>
			<tr>
				<td style='font-weight:bold;font-size:12px' align='right'>{clients}:</td>
				<td style='font-weight:bold;font-size:12px'>$ipzs ($size)</td>
			</tr>
			<tr>
			<td colspan=2 align='right'>$imgRefresh</td>
			</tr>
			</table>
		</div>
	";
	@file_put_contents($cachefile, RoundedLightGreen($html));
	@chmod($cachefile,0755);
	if(is_file("/usr/share/artica-postfix/ressources/logs/web/status.squid")){
		@chmod("/usr/share/artica-postfix/ressources/logs/web/status.squid",0777);
	}
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
$tmp1 = round((float) $number, $decimals);
while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
$tmp1 = $tmp2;
return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}

function squid_memory_monitor(){
	$unix=new unix();
	
	
	$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
	if($EnableIntelCeleron==1){return;}
	$pidfileTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	if($GLOBALS["VERBOSE"]){echo "$pidfileTime\n";}
	
	
	if(!$GLOBALS["VERBOSE"]){
		if($unix->file_time_min($pidfileTime)<10){
			return;
		}
	}
	
	
	$sock=new sockets();
	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
	$MonitConfig=watchdog_config_default($MonitConfig);
	if(!class_exists("influx")){return;}
	$q=new influx();

	$total_memory=0;
	$python=$unix->find_program("python");
	exec("$python /usr/share/artica-postfix/bin/ps_mem.py 2>&1",$results);

	while (list ($index, $line) = each ($results) ){
		$line=trim($line);
		if(!preg_match("#^[0-9\.]+.*?=\s+([0-9\.]+)\s+([a-zA-Z]+)\s+squid#", $line,$re)){
			if($GLOBALS["VERBOSE"]){echo "No match $line\n";}
			continue;
		}
		$total_memory=$re[1];
		$total_memory_unit=$re[2];
		if($GLOBALS["VERBOSE"]){echo "!!Match : $total_memory $total_memory_unit\n";}
		break;

	}

	if($total_memory==0){return;}

	if($total_memory_unit=="KiB"){
		$total_memory=round($total_memory*1024);
	}

	if($total_memory_unit=="MiB"){
		$total_memory=round($total_memory*1024);
		$total_memory=$total_memory*1024;
	}
	if($total_memory_unit=="GiB"){
		$total_memory=round($total_memory*1024);
		$total_memory=$total_memory*1024;
		$total_memory=$total_memory*1024;
	}

	
	$total_memory_MB=$total_memory/1024;
	$total_memory_MB=$total_memory_MB/1024;

	$MEMORY_SYSTEM_MB=$unix->SYSTEM_GET_MEMORY_MB();
	if($GLOBALS["VERBOSE"]){echo "MEMORY_SYSTEM_MB: {$MEMORY_SYSTEM_MB}MB\n";}

	$prc=($total_memory_MB/$MEMORY_SYSTEM_MB);
	$prc=round($prc*100);
	$total_memory_MB=round($total_memory_MB);
	if($GLOBALS["VERBOSE"]){echo "Bytes: $total_memory MB: $total_memory_MB/$MEMORY_SYSTEM_MB ({$prc}%) \n";}
	$date=date("Y-m-d H:i:s");
	$proxyname=$unix->hostname_g();


	$q=new postgres_sql();
	$q->QUERY_SQL("INSERT INTO squidmem (zdate,zpercent,memory,proxyname) VALUES ('$date','$prc','$total_memory_MB','$proxyname')");

	if($MonitConfig["watchdog"]==0){ return;}
	if($MonitConfig["watchdogRestart"]>99){return;}
	
	if($prc>$MonitConfig["watchdogRestart"]){
		$GLOBALS["ALL_SCORES"]++;
		$GLOBALS["ALL_SCORES_WHY"][]="Proxy service memory exceed % value ($prc)";
		$GLOBALS["BY_WATCHDOG"]=true;
		restart_squid(true);
	}
	

}







function SwapWatchdog(){
	$sock=new sockets();
	$unix=new unix();
	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
	$MonitConfig=watchdog_config_default($MonitConfig);
	$ps=$unix->find_program("ps");
	
	if($GLOBALS["VERBOSE"]){echo "\n******** SWAP *******\n";}
	
	
	if($MonitConfig["SWAP_MONITOR"]==0){return;}
	
	

	include_once(dirname(__FILE__)."/ressources/class.main_cf.inc");
	$sys=new systeminfos();

	$pourc=round(($sys->swap_used/$sys->swap_total)*100);
	$freeMemory=$unix->TOTAL_MEMORY_MB_FREE();
	$SwapMemoryused=$sys->swap_used;
	ToSyslog("SwapWatchdog(): {$sys->swap_used}MB used Current {$pourc}% Free Memory: {$freeMemory}MB, min:{$MonitConfig["SWAP_MIN"]}% MAX:{$MonitConfig["SWAP_MAX"]}%");
	
	if($pourc<$MonitConfig["SWAP_MIN"]){return;}
	if(!isset($MonitConfig["SWAP_MIN"])){$MonitConfig["SWAP_MIN"]=5;}
	if(!isset($MonitConfig["SWAP_MAX"])){$MonitConfig["SWAP_MAX"]=55;}
	
	
	$ps_text[]="There is not enough memory to clean the swap";
	$ps_text[]="Current configuration was: Free Swap memory over than {$MonitConfig["SWAP_MAX"]}%";
	$ps_text[]="Your current Swap file using: {$SwapMemoryused}M - {$pourc}% - $sys->swap_used/$sys->swap_total";
	$ps_text[]="Memory free on your system:{$freeMemory}M";
	$ps_text[]="You will find here a snapshot of current tasks";
	$ps_text[]=$unix->ps_mem_report();
	$ps_mail=@implode("\n", $ps_text);
	
	if($pourc>$MonitConfig["SWAP_MAX"]){
		if($SwapMemoryused<$freeMemory){
			squid_admin_mysql(0, "[ALERT] REBOOT server!!! Swap exceed rule {$pourc}% max: {$MonitConfig["SWAP_MAX"]}%",$ps_mail,__FILE__,__LINE__);
			FailOverDown("Swap exceed rule - reboot - {$pourc}% max:{$MonitConfig["SWAP_MAX"]}%\n$ps_mail");
			shell_exec($unix->find_program("shutdown")." -rF now");
			die();
		}
		
		
		squid_admin_mysql(1, "Cleaning SWAP current: {$pourc}% max:{$MonitConfig["SWAP_MAX"]}%","clean the swap ({$SwapMemoryused}M/{$freeMemory}M)\n$ps_mail",__FILE__,__LINE__);
		SwapWatchdog_FreeSync();
		die();
		
	}
	squid_admin_mysql(1, "Cleaning SWAP current:{$pourc}% min:{$MonitConfig["SWAP_MIN"]}%","clean the swap ({$SwapMemoryused}M/{$freeMemory}M)\n$ps_mail",__FILE__,__LINE__);
	SwapWatchdog_FreeSync();

}




function SwapWatchdog_FreeSync(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$GLOBALS["ALL_SCORES"]++;
	$GLOBALS["ALL_SCORES_WHY"][]="Launch purge Swap procedure";
	shell_exec("$nohup $php /usr/share/artica-postfix/exec.squid.swapoff.php >/dev/null 2>&1 &");
	
}


function cache_center_status($aspid=false){
	$unix=new unix();
	if($GLOBALS["VERBOSE"]){echo "Running cache_center_status()\n";$aspid=true;}
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")."Already `task` running PID $pid since {$time}mn\n";}
			system_admin_events("stop_squid::Already task running PID $pid since {$time}mn", __FUNCTION__, __FILE__, __LINE__, "proxy");
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}	
	
	$TimeFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if($GLOBALS["VERBOSE"]){ echo "TimeFile=$TimeFile\n";}
	$Time=$unix->file_time_min($TimeFile);
	if(!$GLOBALS["VERBOSE"]){
		if($Time<5){return;}
	}
	
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	
	$ARRAY=unserialize(base64_decode(squid_get_storage_info()));
	
	if($GLOBALS["VERBOSE"]){print_r($ARRAY);}
	
	$q=new mysql();
	while (list($directory,$arrayStore)=each($ARRAY)){
		$arrayStore["USED"]=intval($arrayStore["USED"]);
		$arrayStore["PERC"]=intval($arrayStore["PERC"]);
		if($GLOBALS["VERBOSE"]){echo "$directory USED {$arrayStore["USED"]} PERC:{$arrayStore["PERC"]}\n";}
		
		if($directory=="MEM"){continue;}
		if($arrayStore["USED"]==0){continue;}
		$PERC=$arrayStore["PERC"];
		$USED=$arrayStore["USED"];
		
		
	
		if(preg_match("#\/home\/squid\/cache\/MemBooster([0-9]+)#", $directory,$re)){
			$q->QUERY_SQL("UPDATE squid_caches_center SET percentcache='$PERC',percenttext='$PERC', 
					`usedcache`='$USED' WHERE ID={$re[1]}","artica_backup");
			continue;
		}
	
	
		if($GLOBALS["VERBOSE"]){echo "$directory -> $USED / {$PERC}%\n";}
		$q->QUERY_SQL("UPDATE squid_caches_center SET 
				percentcache='$PERC',percenttext='$PERC', `usedcache`='$USED' WHERE 
				`cache_dir`='$directory'","artica_backup");
	}	
	
	
	
}


function caches_center($aspid=false){
	$unix=new unix();
	$umount=$unix->find_program("umount");
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")."Already `task` running PID $pid since {$time}mn\n";}
			system_admin_events("stop_squid::Already task running PID $pid since {$time}mn", __FUNCTION__, __FILE__, __LINE__, "proxy");
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	if(system_is_overloaded(__FILE__)){ return; }
	
	
	
	$rm=$unix->find_program("rm");
	
	$q=new mysql();
	

	if(!$q->FIELD_EXISTS("squid_caches_center","percenttext","artica_backup")){
		$sql="ALTER TABLE `squid_caches_center` ADD `percenttext` VARCHAR(10)";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){writelogs("$q->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	}
	
	$sql="SELECT * FROM squid_caches_center WHERE `remove`=1";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){squid_admin_mysql(1, "MySQL error $q->mysql_error", "$q->mysql_error");return;}
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$cache_dir=$ligne["cache_dir"];
		$cache_type=$ligne["cache_type"];
		if($cache_type=="Cachenull"){
			$q->QUERY_SQL("DELETE FROM squid_caches_center WHERE ID=$ID","artica_backup");
			continue; 
		}
		
		
		if($cache_type=="tmpfs"){
			$cache_dir="/home/squid/cache/MemBooster$ID";
		}
		
		if(is_link($cache_dir)){$cache_dir=readlink($cache_dir);}
		shell_exec("$rm -rf $cache_dir");
		squid_admin_mysql(1, "Cache $cache_dir was deleted from DISK", "ID=$ID\ndirectory=$cache_dir");
		$q->QUERY_SQL("DELETE FROM squid_caches_center WHERE ID=$ID","artica_backup");
		
		if($cache_type=="tmpfs"){
			shell_exec("$umount -l $cache_dir");
		}
		
		if(!$q->ok){squid_admin_mysql(1, "MySQL error $q->mysql_error", "ID=$ID\ndirectory=$cache_dir");}
	}
	
	if($GLOBALS["VERBOSE"]){echo "Cache Center done\n";}
	
	
}
function mini_bench_to($arg_t, $arg_ra=false){
	$tttime=round((end($arg_t)-$arg_t['start'])*1000,4);
	if ($arg_ra) $ar_aff['total_time']=$tttime;
	else return $tttime;
	$prv_cle='start';
	$prv_val=$arg_t['start'];

	foreach ($arg_t as $cle=>$val)
	{
		if($cle!='start')
		{
			$prcnt_t=round(((round(($val-$prv_val)*1000,4)/$tttime)*100),1);
			if ($arg_ra) $ar_aff[$prv_cle.' -> '.$cle]=$prcnt_t;
			$aff.=$prv_cle.' -> '.$cle.' : '.$prcnt_t." %\n";
			$prv_val=$val;
			$prv_cle=$cle;
		}
	}
	if ($arg_ra) return $ar_aff;
	return $aff;
}





function ALLKIDS(){
	$unix=new unix();
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$time=$unix->file_time_min($pidtime);
	if(!$GLOBALS["FORCE"]){
		if($GLOBALS["VERBOSE"]){echo "Current {$time}Mn, need 5mn\n";}
		if($time<5){return;}
	}
	
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	
	
	
	$data=$unix->squidclient("utilization");
	if($data==null){return;}
	$f=explode("\n",$data);
	
	
	while (list ($index, $line) = each ($f)){
	
	if(preg_match("#by kid([0-9]+) \{#", $line,$re)){
		$CPU=$re[1];
		$STARTUP=false;
	}
	
	if(preg_match("#client_http\.requests.*?=.*?([0-9\.]+)#", $line,$re)){	
			if(!$STARTUP){
				if(!isset($ARRAY[$CPU]["5mn"]["client_http_requests"])){
					$ARRAY[$CPU]["5mn"]["client_http_requests"]=round($re[1]);
					continue;
				}
			}
			
			if($STARTUP){
				if(!isset($ARRAY[$CPU]["TOTAL"]["client_http_requests"])){
					$ARRAY[$CPU]["TOTAL"]["client_http_requests"]=round($re[1]);
					continue;
				}
			}
				
		}
	if(preg_match("#client_http\.kbytes_in.*?=.*?([0-9\.]+)#", $line,$re)){
			if(!$STARTUP){
				if(!isset($ARRAY[$CPU]["5mn"]["client_http_kbytes_in"])){
					$ARRAY[$CPU]["5mn"]["client_http_kbytes_in"]=round($re[1]);
					continue;
				}
			}
				
			if($STARTUP){
				if(!isset($ARRAY[$CPU]["TOTAL"]["client_http_kbytes_in"])){
					$ARRAY[$CPU]["TOTAL"]["client_http_kbytes_in"]=round($re[1]);
					continue;
				}
			}
		
		}	

	if(preg_match("#client_http\.kbytes_out.*?=.*?([0-9\.]+)#", $line,$re)){
			if(!$STARTUP){
				if(!isset($ARRAY[$CPU]["5mn"]["client_http_kbytes_out"])){
					$ARRAY[$CPU]["5mn"]["client_http_kbytes_out"]=round($re[1]);
					continue;
				}
			}
		
			if($STARTUP){
				if(!isset($ARRAY[$CPU]["TOTAL"]["client_http_kbytes_out"])){
					$ARRAY[$CPU]["TOTAL"]["client_http_kbytes_out"]=round($re[1]);
					continue;
				}
			}
		
		}		
	
	if(preg_match("#cpu_usage.*?([0-9\.]+)#", $line,$re)){	
			if(!isset($ARRAY[$CPU]["5mn"]["CPU"])){
				$ARRAY[$CPU]["5mn"]["CPU"]=$re[1];
			}
		}
		
	if(preg_match("#cpu_usage.*?wall_time.*?([0-9\.]+)#", $line,$re)){	
		if(!isset($ARRAY[$CPU]["5mn"]["wall_time"])){
				$ARRAY[$CPU]["5mn"]["wall_time"]=round($re[1]);
		}
	}
		
	if(preg_match("#Totals since cache startup#", $line)){$STARTUP=true;continue;}
		
	}
	
	
	
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/AllSquidKids.db", serialize($ARRAY));
	@chmod("/usr/share/artica-postfix/ressources/logs/AllSquidKids.db",0755);
	
	
	
	
}




function LOGFILE_DAEMON_WAKEUP(){

	$unix=new unix();
	$pgrep=$unix->find_program("pgrep");
	exec("$pgrep -l -f \"exec.logfile_daemon.php\" 2>&1",$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#pgrep#", $line)){continue;}
		if(!preg_match("#^([0-9]+)\s+#", $line,$re)){continue;}
		$PID=$re[1];
		$PID_LIST[$re[1]]=true;
		if($GLOBALS["VERBOSE"]){echo "Wakup pid:$PID\n";}
		echo "Reloading.....: ".date("H:i:s")." Squid-cache, wake-up logger PID:$PID...\n";
		@touch("/var/run/squid/exec.logfilefile_daemon.$PID.wakeup");
		@chmod("/var/run/squid/exec.logfilefile_daemon.$PID.wakeup",0777);
		@chown("/var/run/squid/exec.logfilefile_daemon.$PID.wakeup","squid");
		@chgrp("/var/run/squid/exec.logfilefile_daemon.$PID.wakeup","squid");
	}

	foreach (glob("/var/run/squid/exec.logfilefile_daemon.*.pid") as $filepath) {
		if($GLOBALS["VERBOSE"]){echo "$filepath\n";}
		$basename=basename($filepath);
		if(!preg_match("#exec\.logfilefile_daemon\.([0-9]+)\.pid#", $basename,$re)){continue;}
		$PID=$re[1];
		if($GLOBALS["VERBOSE"]){echo "Found pid:$PID\n";}
		if(!$unix->process_exists($PID)){
			if($GLOBALS["VERBOSE"]){echo "pid:$PID not running, delete it\n";}
			@unlink($filepath);
		}
	}


}

function bandwith_stats_today(){
	$q=new mysql_squid_builder();
	$unix=new unix();
	$Time=$unix->file_time_min("/usr/share/artica-postfix/ressources/logs/bandwith_stats_today-fam.db");
	if($Time<15){return;}
	
	$current_table="quotaday_".date("Ymd");
	if(!$q->TABLE_EXISTS($current_table)){return;}
	if($q->COUNT_ROWS($current_table)<5){return;}
	
	
	
	
	
	$sql="SELECT SUM(size) as size,familysite FROM `$current_table` GROUP BY familysite ORDER BY size DESC LIMIT 0,10";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){return;}
	
	while ($ligne = mysql_fetch_assoc($results)) { 
		$size=$ligne["size"];
		$size=$size/1024;
		$size=$size/1000;
		$ARRAY[$ligne["familysite"]]=$size;
		
	}
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/bandwith_stats_today-fam.db", serialize($ARRAY));
	
	$sql="SELECT SUM(size) as size,uid FROM `$current_table` GROUP BY uid ORDER BY size DESC LIMIT 0,10";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){return;}
	
	$ARRAY=array();
	while ($ligne = mysql_fetch_assoc($results)) {
		$size=$ligne["size"];
		$size=$size/1024;
		$size=$size/1000;
		$ARRAY[$ligne["uid"]]=$size;
	
	}
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/bandwith_stats_today-uid.db", serialize($ARRAY));
	
	$sql="SELECT SUM(size) as size,ipaddr FROM `$current_table` GROUP BY ipaddr ORDER BY size DESC LIMIT 0,10";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){return;}	
	
	$ARRAY=array();
	while ($ligne = mysql_fetch_assoc($results)) {
		$size=$ligne["size"];
		$size=$size/1024;
		$size=$size/1000;
		$ARRAY[$ligne["ipaddr"]]=$size;
	
	}
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/bandwith_stats_today-ipaddr.db", serialize($ARRAY));	
		
}

function C_ICAP_CLIENTS($aspid=false){
	$unix=new unix();
	$PidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	if(!$aspid){
		$pid=$unix->get_pid_from_file($PidFile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$TimeMin=$unix->PROCCESS_TIME_MIN($pid);
			return;
		}
	
	}
	@file_put_contents($PidFile, getmypid());
	
	$cicap_client=$unix->find_program("c-icap-client");
	if(!is_file($cicap_client)){return;}
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("UPDATE c_icap_services SET `status`=0 WHERE `enabled`=0");
	$q->CheckTablesICAP();
	$sql="SELECT * FROM c_icap_services WHERE enabled=1";
	$results = $q->QUERY_SQL($sql);
	if(mysql_num_rows($results)==0){return;}
	
	$METHODS["reqmod_precache"]="REQMOD"; 
	$METHODS["respmod_precache"]="RESPMOD";
	$php=$unix->LOCATE_PHP5_BIN();
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$service_name_text=$ligne["service_name"];
		$ID=$ligne["ID"];
		$addr=$ligne["ipaddr"];
		$port=$ligne["listenport"];
		$service=$ligne["icap_server"];
		$cmdline="$cicap_client -i $addr -p $port -s $service -method {$METHODS[$ligne["respmod"]]} 2>&1";
		if($GLOBALS["VERBOSE"]){echo $cmdline."\n";}
		$Mresults=array();
		$Mresults[]="Service ID:$ID $addr:$port/$service/{$METHODS[$ligne["respmod"]]}";
		$FINAL=true;
		exec($cmdline,$Mresults);
		echo @implode("\n", $Mresults);
		$HEADER_LOG="$addr:$port/$service ({$METHODS[$ligne["respmod"]]})";
		$FOUND=false;
		while (list ($index, $line) = each ($Mresults) ){
			if(preg_match("#200 OK#i", $line)){ $FINAL=true; $FOUND=true;break; }
			if(preg_match("#404 Service not found#i", $line)){ $FINAL=false;$FOUND=true; break; }
			if(preg_match("#Failed to connect#i", $line)){ $FINAL=false;$FOUND=true; break; }
		}
		
		if(!$FOUND){
			squid_admin_mysql(1,"Unknown ICAP $service for $HEADER_LOG [action=warn]",@implode("\n", $Mresults),__FILE__,__LINE__);
			$q->QUERY_SQL("UPDATE c_icap_services SET `status`=3 WHERE ID=$ID");
			continue;
		}
		
		if(!$FINAL){
			squid_admin_mysql(0,"ICAP $service FAILED for $HEADER_LOG [action=disable]",@implode("\n", $Mresults),__FILE__,__LINE__);
			$q->QUERY_SQL("UPDATE c_icap_services SET `status`=2,`enabled`=0 WHERE ID=$ID");
			shell_exec("$php /usr/share/artica-postfix/exec.squid.php --build --force");
			continue;
		}
		
		$q->QUERY_SQL("UPDATE c_icap_services SET `status`=1 WHERE ID=$ID");
		
		
	}
	
	
}

function check_status(){
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress_status2("{checking_proxy_service}",20);
	sleep(1);
	$pid=SQUID_PID();
	echo "PID: $pid\n";
	if($unix->process_exists($pid)){
		$ttl=$unix->PROCCESS_TIME_MIN($pid);
		build_progress_status2("{APP_SQUID}: {running} {since} {$ttl}Mn",30);
		echo "Running since {$ttl}Mn\n";
	}else{
		build_progress_status2("{APP_SQUID}: {starting_service}",30);
		start_squid(true);
	}
	
	$pid=SQUID_PID();
	echo "PID: $pid\n";
	if(!$unix->process_exists($pid)){
		build_progress_status2("{APP_SQUID}: {failed}",110);
	}
	$GLOBALS["FORCE"]=true;
	build_progress_status2("{APP_SQUID}: {remove_cached_datas}",50);
	sleep(1);
	echo "Remove ALL_SQUID_STATUS\n";
	@unlink("/usr/share/artica-postfix/ressources/logs/web/squid.status.html");
	@unlink("/usr/share/artica-postfix/ressources/logs/web/status.right.image.cache");
	if(is_file("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS")){@unlink("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS");}
	$cmd="$php /usr/share/artica-postfix/exec.status.php --all-squid --nowachdog >/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS 2>&1";
	echo "$cmd\n";
	shell_exec($cmd);
	build_progress_status2("{APP_SQUID}: {checking_caches}",55);
	sleep(1);
	CheckGlobalInfos();
	build_progress_status2("{APP_SQUID}: {checking_caches}",60);
	sleep(1);
	CheckStoreDirs(true);
	build_progress_status2("{APP_SQUID}: {checking_caches}",61);
	sleep(1);
	
	
	build_progress_status2("{APP_SQUID}: {checking_caches}",62);
	counters(true);
	sleep(1);

	build_progress_status2("{APP_SQUID}: {checking_caches}",63);
	ALLKIDS(true);
	sleep(1);	
	
	

	
	build_progress_status2("{APP_SQUID}: {done}",100);
	sleep(1);
}

function squid_conx(){
	$unix=new unix();
	$unix->SQUID_ACTIVE_REQUESTS();
}


function caches_size(){
	$unix=new unix();
	$cache_file="/usr/share/artica-postfix/ressources/logs/web/squid_caches_center.db";
	
	if($unix->file_time_min($cache_file)<120){return;}
	
	
	$q=new mysql();
	if($q->COUNT_ROWS("squid_caches_center", "artica_backup")>0){
		$sql="SELECT * FROM squid_caches_center WHERE `enabled`=1 AND `remove`=0 ORDER BY zOrder";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$cachename=$ligne["cachename"];
			$cache_dir=$ligne["cache_dir"];
			$cache_type=$ligne["cache_type"];
			if($cache_type=="Cachenull"){continue;}
			$cache_partition=$unix->DIRPART_OF($cache_dir);
			$ARRAY["CACHES_SIZE"][$cache_partition]=$unix->DIRECTORY_FREEM($cache_dir);
			$ARRAY[$cache_dir]["NAME"]=$cachename;
			$ARRAY[$cache_dir]["PARTITION"]=$cache_partition;
			$ARRAY[$cache_dir]["DIRPART_INFO"]=$unix->DIRPART_INFO($cache_dir);
			$ARRAY[$cache_dir]["SIZE"]=$ARRAY["CACHES_SIZE"][$cache_partition];
			
			
			
			
			
		}
		
	}
	
	$caches=$unix->SQUID_CACHE_FROM_SQUIDCONF_FULL();
	while (list ($cache_dir, $line) = each ($caches)){
		if(isset($ARRAY[$cache_dir])){continue;}
		$cachename=basename($cache_dir);
		$cache_partition=$unix->DIRPART_OF($cache_dir);
		$ARRAY["CACHES_SIZE"][$cache_partition]=$unix->DIRECTORY_FREEM($cache_dir);
		$ARRAY[$cache_dir]["NAME"]=$cachename;
		$ARRAY[$cache_dir]["PARTITION"]=$cache_partition;
		$ARRAY[$cache_dir]["DIRPART_INFO"]=$unix->DIRPART_INFO($cache_dir);
		$ARRAY[$cache_dir]["SIZE"]=$GLOBALS["CACHES_SIZE"][$cache_partition];
		
		
	}
	
	
	@unlink($cache_file);
	@file_put_contents($cache_file, serialize($ARRAY));
	@chmod($cache_file, 0777);
	
	
	
}

function redirectors_array($aspid=false){
	$cache_file="/usr/share/artica-postfix/ressources/logs/web/squid_redirectors_status.db";
	$NotifyTime="/etc/artica-postfix/pids/squid.redirectors.notify";
	$pidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	$unix=new unix();
	if($aspid){
		$pid=$unix->get_pid_from_file($pidFile);
		if($unix->process_exists($pid)){
			$pptime=$unix->PROCCESS_TIME_MIN($pid,10);
			if($GLOBALS["VERBOSE"]){echo "Process already running PID $pid since {$pptime}Mn\n";}
			return;
		}
		
		@file_put_contents($pidFile, getmypid());
		
	}
	
	
	
	$sock=new sockets();
	$datas=explode("\n",$unix->squidclient("redirector"));
	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
	$MonitConfig=watchdog_config_default($MonitConfig);
	if($MonitConfig["watchdog"]==0){return;}
	
	
	
	$redirector=array();
	$RedirectorsArray=unserialize(base64_decode($sock->GET_INFO("SquidRedirectorsOptions")));
	if(!is_numeric($RedirectorsArray["url_rewrite_children"])){$RedirectorsArray["url_rewrite_children"]=20;}
	$PERCENTS=array();
	while (list ($index, $line) = each ($datas)){
		
		if(preg_match("#by kid([0-9]+)#", $line,$re)){
			$KID="Processor #{$re[1]}";
		}
		
		if(preg_match("#number active:\s+([0-9]+)\s+of\s+([0-9]+)#i",$line,$re)){
			$max=$re[2];
			$Current=$re[1];
			$percent=$Current/$max;
			$percent=$percent*100;
			$LOGs[]="$KID: $Current/$max = {$percent}%";
			Events("$line $Current/$max = {$percent}%");
			$PERCENTS[]=round($percent);
		}
		
		
		if(!preg_match("#([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)(.*?)\s+([0-9\.]+)\s+([0-9\.]+)\s+(.*)#", $line,$re)){continue;}
		$re[6]=trim($re[6]);
		$re[3]=trim($re[3]);
		$re[6]=str_replace(" ", "", $re[6]);
		$fflosg[]=$re[3];
		
		if($GLOBALS["VERBOSE"]){echo "STATE \"{$re[6]}\"\n";}
		
		if($re[6]=="BS"){
			$unix->KILL_PROCESS($re[3],9);
			if($GLOBALS["VERBOSE"]){echo "KILLING \"{$re[3]}\"\n";}
		}
		if($re[6]=="S"){
			$unix->KILL_PROCESS($re[3],9);
			if($GLOBALS["VERBOSE"]){echo "KILLING \"{$re[3]}\"\n";}
		}
		
		$redirector[$re[1]]=array(
			"FD"=>$re[2],
			"PID"=>$re[3],
			"REQ"=>$re[4],
			"REP"=>$re[5],
			"STATE"=>$re[6],
			"TIME"=>$re[7],
			"OFFSET"=>$re[8],
			"URI"=>$re[9]
				
			);

	}
	
	$FFperc=0;
	$FINAL_PERC=0;
	$CountOFPercent=count($PERCENTS);
	if(count($PERCENTS>0)){
		while (list ($index, $pp) = each ($PERCENTS)){
			$FFperc=$FFperc+$pp;
		}
		
		$FINAL_PERC=round($FFperc/$CountOFPercent,2);
		$LOGs[]="************************************************";
		$LOGs[]="ALL CPUS: {$FINAL_PERC}%";
		$LOGs[]="";
	}
	
	
	
	
	if($FINAL_PERC>90){
		$severity=1;
		$action="notify";
		$NotifyTimeEx=$unix->file_time_min($NotifyTime);
		if($NotifyTimeEx<10){return;}
		@unlink($NotifyTime);
		@file_put_contents($NotifyTime, time());
		if($FINAL_PERC>95){$severity=1;$action="reload";}
	
		Events("$line $Current/$max  severity = $severity");
	
		squid_admin_mysql($severity, "Warning! redirectors reach {$FINAL_PERC}% [action=$action]",
		@implode("\n", $LOGs)."\n\n---------------------------------\n\n".
		@implode("\n", $datas));
		if($severity==0){
			shell_exec("/etc/init.d/squid reload --script=".basename(__FILE__));
			return;
		}
	}
	
	
	
	
	
	@unlink($cache_file);
	if(count($redirector)==0){return;}
	@file_put_contents($cache_file, serialize($redirector));
	@chmod($cache_file, 0755);
	
	

		
	
}

function dev_shm(){
	$NotifyTime="/etc/artica-postfix/pids/squid.dev.shm";
	if(!is_dir("/run/shm")){return;}
	$sock=new sockets();
	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
	$MonitConfig=watchdog_config_default($MonitConfig);

	
	
	$unix=new unix();
	$percent=$unix->TMPFS_USEPERCENT("/run/shm");
	
	
	@file_put_contents("/etc/artica-postfix/settings/Daemons/StatusDevSHMPerc", $percent);
	@chmod("/etc/artica-postfix/settings/Daemons/StatusDevSHMPerc", 0755);
	
	if($percent>90){
		if($percent<98){
			$NotifyTimeEx=$unix->file_time_min($NotifyTime);
			if($NotifyTimeEx>10){
				squid_admin_mysql(2, "Warning Shared memory exceed 90%!", "The /dev/shm memory partition exceed 90%, at 100% the proxy will crash!");
				return;
			}
		}
	}
	
	if($percent>98){
		if($MonitConfig["watchdog"]==1){
			squid_admin_mysql(0, "Warning Shared memory exceed 98% ({$percent}%)[action=restart]", "The /dev/shm memory partition exceed 98%, at 100% the proxy will crash!, in this case, the proxy will be restarted.");
			$GLOBALS["BY_OTHER_SCRIPT"]="function SHM";
			$GLOBALS["FORCE"]=true;
			restart_squid(true);
		}else{
			squid_admin_mysql(0, "Warning Shared memory exceed 98% ({$percent}%) [action=notify]", "The /dev/shm memory partition exceed 98%, at 100% the proxy will crash!");
		}
	}
	
}

function CHECK_MEMORY_USE_WARN(){
	$w=new squid_watchdog();
	$unix=new unix();
	$MonitConfig=$w->MonitConfig;
	
	
	
	if($MonitConfig["MEMORY_TEST"]==0){return;}
	
	
	$sys=new os_system();
	$mem=$sys->realMemory();
	$pourc=$mem["ram"]["percent"];
	$ram_used=$mem["ram"]["used"];
	$ram_total=$mem["ram"]["total"];
	$MAX_MEM_ALERT_TTIME="/etc/artica-postfix/pids/MAX_MEM_ALERT_TTIME";
	$MAX_MEM_PRC_TTIME="/etc/artica-postfix/pids/MAX_MEM_PRC_TTIME";
	$MAX_MEM_ALERT=$MonitConfig["MAX_MEM_ALERT"];
	
	if($pourc<$MAX_MEM_ALERT){return;}
	if($unix->file_time_min($MAX_MEM_ALERT_TTIME)<10){return;}
	$report=$unix->ps_mem_report();
	$text_ram=FormatBytes($ram_used,true)."/".FormatBytes($ram_total,true);
	squid_admin_mysql(0, "Memory exceed {$MAX_MEM_ALERT}% - Current {$pourc}%($text_ram) [action=warn]", $report,__FILE__,__LINE__);
	@unlink($MAX_MEM_ALERT_TTIME);
	@file_put_contents($MAX_MEM_ALERT_TTIME, time());
		
	
}


function CHECK_MEMORY_USE(){

	
	$unix=new unix();
	$w=new squid_watchdog();
	$MonitConfig=$w->MonitConfig;
	if($MonitConfig["MEMORY_TEST"]==0){return;}
	
	
	$sys=new os_system();
	$mem=$sys->realMemory();
	$pourc=$mem["ram"]["percent"];
	$ram_used=$mem["ram"]["used"];
	$ram_total=$mem["ram"]["total"];
	$swap_perc=$unix->SYSTEM_GET_SWAP_PERC();
	
	Events("Memory {$pourc}% SWAP:{$swap_perc}% Used:$ram_used Total: $ram_total");
	
	$MAX_MEM_ALERT_TTIME="/etc/artica-postfix/pids/MAX_MEM_ALERT_TTIME";
	$MAX_MEM_PRC_TTIME="/etc/artica-postfix/pids/MAX_MEM_PRC_TTIME";
	
	$MAX_MEM_MNS=$MonitConfig["MAX_MEM_MNS"];
	$MAX_MEM_ALERT=$MonitConfig["MAX_MEM_ALERT"];
	$MAX_MEM_PRC=$MonitConfig["MAX_MEM_PRC"];
	
	if($pourc>$MAX_MEM_PRC){
		$CheckTime=$unix->file_time_min($MAX_MEM_ALERT_TTIME);
		if($CheckTime<$MAX_MEM_MNS){
			CHECK_MEMORY_USE_ACTION();
			return;
		}
	}
	
	
	CHECK_MEMORY_USE_WARN();
}


function CheckActiveDirectoryEmergency(){
	
	
	$f=explode("\n",@file_get_contents("/etc/squid3/non_ntlm.access"));
	$sock=new sockets();
	$ActiveDirectoryEmergency=intval($sock->GET_INFO("ActiveDirectoryEmergency"));
	$EnableKerbAuth=intval($sock->GET_INFO("EnableKerbAuth"));
	
	if($EnableKerbAuth==0){
		$f[]="# Nothing to do... ";
		$f[]="# This file is used for Allow users surfing trough Internet when there are issues on Active Directory ";
		$f[]="";
		@file_put_contents("/etc/squid3/non_ntlm.access", @implode("\n", $f));
		return;
	}
	
	if($ActiveDirectoryEmergency==1){return;}
	$ENABLED=false;
	while (list ($md5, $line) = each ($f) ){
		if(preg_match("#http_access allow all#", $line)){
			$ENABLED=true;
			break;
		}
	
	}	
	
	
	$f=array();
	if(!$ENABLED){return;}
	
	if($ENABLED){
		
		$f[]="# Nothing to do... ";
		$f[]="# This file is used for Allow users surfing trough Internet when there are issues on Active Directory ";
		$f[]="";
		@file_put_contents("/etc/squid3/non_ntlm.access", @implode("\n", $f));
		
		squid_admin_mysql(1, "Re-Activate Authentication mode after Active Directory Emergency mode [action=reload proxy]", "",__FILE__,__LINE__);
		$unix=new unix();
		$squidbin=$unix->LOCATE_SQUID_BIN();
		shell_exec("$squidbin -k reconfigure");
	}
	
}



function CHECK_MEMORY_USE_ACTION(){
	$sys=new os_system();
	$unix=new unix();
	$mem=$sys->realMemory();
	$pourc=$mem["ram"]["percent"];
	$ram_used=$mem["ram"]["used"];
	$ram_total=$mem["ram"]["total"];
	$MAX_MEM_ALERT_TTIME="/etc/artica-postfix/pids/MAX_MEM_ALERT_TTIME";
	$MAX_MEM_PRC_TTIME="/etc/artica-postfix/pids/MAX_MEM_PRC_TTIME";
	
	$ram_log[]="Before Action = {$pourc}% $ram_used/$ram_total";
	$w=new squid_watchdog();
	$MonitConfig=$w->MonitConfig;
	$MAX_MEM_RST_MYSQL=$MonitConfig["MAX_MEM_RST_MYSQL"];
	$MAX_MEM_RST_UFDB=$MonitConfig["MAX_MEM_RST_UFDB"];
	$MAX_MEM_RST_APACHE=$MonitConfig["MAX_MEM_RST_APACHE"];
	$MAX_MEM_RST_SQUID=$MonitConfig["MAX_MEM_RST_SQUID"];
	$MAX_MEM_PRC=$MonitConfig["MAX_MEM_PRC"];
	$text_ram=FormatBytes($ram_used,true)."/".FormatBytes($ram_total,true);
	Events("#1 Memory {$pourc}% Used:$ram_used Total: $ram_total");
	
	
	$unix->send_email_events("Memory exceed {$MAX_MEM_PRC}% ($text_ram) [action=stop-services]", 
	"MySQL and Categories database will be restarted\n"
	, "system");
	squid_admin_mysql(0, "Memory exceed {$MAX_MEM_PRC}% ($text_ram) [action=stop-services]", null,__FILE__,__LINE__);
	
	if($MAX_MEM_RST_MYSQL==1){
		squid_admin_mysql(1, "Memory exceed - Restarting MySQL services", null,__FILE__,__LINE__);
		shell_exec("/etc/init.d/mysql restart --force --framework=". basename(__FILE__));
		shell_exec("/etc/init.d/squid-db restart --force");
		shell_exec("/etc/init.d/ufdbcat restart --force");
		
		$mem=$sys->realMemory();
		$pourc=$mem["ram"]["percent"];
		$ram_used=$mem["ram"]["used"];
		$ram_total=$mem["ram"]["total"];	
		$text_ram=FormatBytes($ram_used,true)."/".FormatBytes($ram_total,true);
		$ram_log[]="After restarting MySQL services {$pourc}% $text_ram";
	}
	
	
	$mem=$sys->realMemory();
	$pourc=$mem["ram"]["percent"];
	$ram_used=$mem["ram"]["used"];
	$ram_total=$mem["ram"]["total"];
	Events("#2 Memory {$pourc}% Used:$ram_used Total: $ram_total");
	
	
	if($pourc<$MAX_MEM_PRC){	
		$report=$unix->ps_mem_report();
		squid_admin_mysql(1, "Memory OK {$pourc}% [action=report]", @implode("\n", $ram_log)."\n$report",__FILE__,__LINE__);
		@unlink($MAX_MEM_ALERT_TTIME);
		@unlink($MAX_MEM_PRC_TTIME);
		return;
	}
	
	
	if($MAX_MEM_RST_UFDB==1){
		squid_admin_mysql(1, "Memory exceed {$pourc}% - Restarting Webfiltering service", null,__FILE__,__LINE__);
		shell_exec("/etc/init.d/ufdb restart --force --framework=". basename(__FILE__));
	
		$mem=$sys->realMemory();
		$pourc=$mem["ram"]["percent"];
		$ram_used=$mem["ram"]["used"];
		$ram_total=$mem["ram"]["total"];
		$text_ram=FormatBytes($ram_used,true)."/".FormatBytes($ram_total,true);
		$ram_log[]="After restarting Webfiltering service {$pourc}% $text_ram";
	}	
	
	
	$mem=$sys->realMemory();
	$pourc=$mem["ram"]["percent"];
	$ram_used=$mem["ram"]["used"];
	$ram_total=$mem["ram"]["total"];
	Events("#3 Memory {$pourc}% Used:$ram_used Total: $ram_total");
	
	
	if($pourc<$MAX_MEM_PRC){
		$report=$unix->ps_mem_report();
		squid_admin_mysql(1, "Memory OK {$pourc}% [action=report]", @implode("\n", $ram_log)."\n$report",__FILE__,__LINE__);
		@unlink($MAX_MEM_ALERT_TTIME);
		@unlink($MAX_MEM_PRC_TTIME);
		return;
	
	}	
	
	
	if($MAX_MEM_RST_APACHE==1){
		squid_admin_mysql(1, "Memory exceed {$pourc}% - Restarting Web Servers services", null,__FILE__,__LINE__);
		shell_exec("/etc/init.d/apache2 restart --force --framework=". basename(__FILE__));
		shell_exec("/etc/init.d/artica-webconsole restart --force --framework=". basename(__FILE__));
		shell_exec("/etc/init.d/squidguard-http restart --force --framework=". basename(__FILE__));
		shell_exec("/etc/init.d/artica-memcache restart --force --framework=". basename(__FILE__));
		shell_exec("/etc/init.d/ntopng restart  --force --framework=". basename(__FILE__));
		$mem=$sys->realMemory();
		$pourc=$mem["ram"]["percent"];
		$ram_used=$mem["ram"]["used"];
		$ram_total=$mem["ram"]["total"];
		$ram_log[]="After restarting web servers services {$pourc}% $ram_used/$ram_total";
	}	
	
	
	$mem=$sys->realMemory();
	$pourc=$mem["ram"]["percent"];
	$ram_used=$mem["ram"]["used"];
	$ram_total=$mem["ram"]["total"];
	Events("#4 Memory {$pourc}% Used:$ram_used Total: $ram_total");
	
	if($pourc<$MAX_MEM_PRC){
		$report=$unix->ps_mem_report();
		squid_admin_mysql(1, "Memory OK {$pourc}% [action=report]", @implode("\n", $ram_log)."\n$report",__FILE__,__LINE__);
		@unlink($MAX_MEM_ALERT_TTIME);
		@unlink($MAX_MEM_PRC_TTIME);
		return;
	
	}
	
	if($MAX_MEM_RST_SQUID==1){
		squid_admin_mysql(1, "Memory exceed {$pourc}% - Restarting Proxy service", null,__FILE__,__LINE__);
		stop_squid(true);
		start_squid(true);
		$mem=$sys->realMemory();
		$pourc=$mem["ram"]["percent"];
		$ram_used=$mem["ram"]["used"];
		$ram_total=$mem["ram"]["total"];
		$text_ram=FormatBytes($ram_used,true)."/".FormatBytes($ram_total,true);
		$ram_log[]="After restarting Proxy service {$pourc}% $text_ram";
	}
	
	$mem=$sys->realMemory();
	$pourc=$mem["ram"]["percent"];
	$ram_used=$mem["ram"]["used"];
	$ram_total=$mem["ram"]["total"];
	Events("#5 Memory {$pourc}% Used:$ram_used Total: $ram_total");
	$report=$unix->ps_mem_report();
	
	if($pourc<$MAX_MEM_PRC){
		
		squid_admin_mysql(1, "Memory OK {$pourc}% [action=report]", @implode("\n", $ram_log)."\n$report",__FILE__,__LINE__);
		@unlink($MAX_MEM_ALERT_TTIME);
		@unlink($MAX_MEM_PRC_TTIME);
		return;
	
	}
	
	squid_admin_mysql(0, "Clean memory failed after restarting all services!!! [action=report]", @implode("\n", $ram_log)."\n$report",__FILE__,__LINE__);
	
	
}

function CATEGORIES_SERVICE(){
	
	$catz=new mysql_catz();
	if($catz->UfdbCatEnabled==0){return;}
	$catz->ufdbcat("google.com");
	
	if(!$catz->ok){
		if(preg_match("#UnixSocket N\.11#", $catz->mysql_error)){
			exec("/etc/init.d/ufdbcat restart 2>&1",$results);
			squid_admin_mysql(0, "Categories services unavailable [action=restart]",@implode("\n", $results),__FILE__,__LINE);
			return;	
		}
		if(preg_match("#UnixSocket N\.2#", $catz->mysql_error)){
			exec("/etc/init.d/ufdbcat restart 2>&1",$results);
			squid_admin_mysql(0, "Categories services unix socket failed [action=restart]",@implode("\n", $results),__FILE__,__LINE);
			return;
		}	

		if(preg_match("#No unix socket found#i", $catz->mysql_error)){
			exec("/etc/init.d/ufdbcat restart 2>&1",$results);
			squid_admin_mysql(0, "Categories services unix socket failed [action=restart]",@implode("\n", $results),__FILE__,__LINE);
			return;
		}
		
		squid_admin_mysql(0, "Categories service unavailable [action=report]",$catz->mysql_error,__FILE__,__LINE__);
	}
	
}

function BANDWIDTH_MONITOR_CRON(){
	$sock=new sockets();
	$cronfile="/etc/cron.d/artica-squid-watchband";
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	if($SquidPerformance>1){
		if(is_file($cronfile)){@unlink("$cronfile"); system("/etc/init.d/cron reload"); }
		return;
	}
	
	$watchdog=new squid_watchdog();
	$MonitConfig=$watchdog->MonitConfig;
	if($MonitConfig["CHECK_BANDWITDH"]==0){
		if(is_file($cronfile)){@unlink("$cronfile"); system("/etc/init.d/cron reload"); }
		return;
	}
	
	
	
	$per[5]="0,5,10,15,20,25,30,35,40,45,50,55 * * * *";
	$per[10]="0,10,20,30,40,45,50 * * * *";
	$per[30]="0,30 * * * *";
	$per[60]="0 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23 * * *";
	$per[120]="0 0,2,4,6,8,10,12,14,16,18,20,22 * * *";
	$per[360]="0 0,6,12,18 * * *";
	$per[720]="0 0,12 * * *";
	$per[1440]="0 0 * * *";
	
	$unix=new unix();
	$nice=$unix->EXEC_NICE();
	$php=$unix->LOCATE_PHP5_BIN();
	$CHECK_BANDWITDH_INTERVAL=$MonitConfig["CHECK_BANDWITDH_INTERVAL"];
	if(!is_numeric($CHECK_BANDWITDH_INTERVAL)){$CHECK_BANDWITDH_INTERVAL=5;}
	
	$f[]="MAILTO=\"\"";
	$f[]=$per[$CHECK_BANDWITDH_INTERVAL]." $nice $php ".__FILE__." --bandwidth-run >/dev/null 2>&1";
	$f[]="";
	
	@file_put_contents($cronfile, @implode("\n", $f));
	@chmod($cronfile,0644);
	system("/etc/init.d/cron reload");
	unset($f);
	
}

function BANDWIDTH_MONITOR(){
	$sock=new sockets();
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	if($SquidPerformance>1){return;}
	$watchdog=new squid_watchdog();
	$MonitConfig=$watchdog->MonitConfig;
	if($MonitConfig["CHECK_BANDWITDH"]==0){return;}
	$CHECK_BANDWITDH_INTERVAL=$MonitConfig["CHECK_BANDWITDH_INTERVAL"];
	$influx=new influx();
	if(!is_numeric($CHECK_BANDWITDH_INTERVAL)){$CHECK_BANDWITDH_INTERVAL=5;}
	$olddate=strtotime("-{$CHECK_BANDWITDH_INTERVAL} minutes",time());
	$CHECK_BANDWITDH_SIZE=intval($MonitConfig["CHECK_BANDWITDH_INTERVAL"]);
	
	
	$query_date=date("Y-m-d H:i:s",$olddate);
	$postgres=new postgres_sql();
	$sql="select sum(SIZE) as size from access_log where zdate > '$olddate'";
	$ligne=@pg_fetch_assoc($postgres->QUERY_SQL($sql));
	

	$size=($ligne["size"]/1024);
	$size=round($size/1024,2);
	if($GLOBALS["VERBOSE"]){echo "Since ".date("Y-m-d H:i:s",$olddate)."- Size: {$size}MB\n";}

	
	
	if($GLOBALS["VERBOSE"]){echo "{$size}MB must be higher than {$CHECK_BANDWITDH_SIZE}MB\n";}
	if($size<$CHECK_BANDWITDH_SIZE){return;}
	
	$EXCEED_SIZE=$size;
	
	$REPORT[]="Report bandwidth usage since: ".date("{l} {F} d H:i:s",$olddate);

	$ipclass=new IP();
	$sql="select sum(size) as size,ipaddr,mac,userid from access_log where zdate > '$olddate' group by IPADDR,MAC,USERID order by size desc";
	$results=$postgres->QUERY_SQL($sql);
	
	
	
	while($ligne=@pg_fetch_assoc($results)){
		$users2=array();
	
		$size=$ligne["size"]/1024;
		$size=round($size/1024,2);
		if($size==0){continue;}
		if($size<1){continue;}
		if($CHECK_BANDWITDH_SIZE>1){if($size<2){continue;}}
		$IPADDR=$ligne["ipaddr"];
		$users2[]=$IPADDR;
		$MAC=trim($ligne["mac"]);
		
		$USERID=$ligne["userid"];
		if($USERID<>null){
			$users2[]=$USERID;
		}
		if($ipclass->IsvalidMAC($MAC)){
			$users2[]=$MAC;
		}
		
		
		$REPORT[]="User: ".@implode(", ", $users2)." {$size}MB used";
		
		if($GLOBALS["VERBOSE"]){echo "Since ".date("Y-m-d H:i:s",$olddate)."- $IPADDR,$MAC,$USERID Size: {$size}MB\n";}
	}
	
	
	
	$catz=new mysql_catz();
	$sql="select sum(SIZE) as size,familysite from access_log group by familysite where zdate > '{$olddate}' ORDER by size desc";
	$results=$postgres->QUERY_SQL($sql);
	while($ligne=@pg_fetch_assoc($results)){
		$size=$ligne["size"]/1024;
		$size=round($size/1024,2);
		if($size==0){continue;}
		if($size<1){continue;}
		$FAMILYSITE=$ligne["familysite"];
		$category=$catz->GET_CATEGORIES($FAMILYSITE);
		if($category<>null){$category_text=" (category:$category)";}
		$REPORT[]="Web site: $FAMILYSITE {$size}MB used$category_text";
		
		
	}
	
	squid_admin_mysql(0, "Bandwidth usage {$EXCEED_SIZE}MB exceed {$CHECK_BANDWITDH_SIZE}MB",
	 @implode("\n", $REPORT),__FILE__,__LINE__);
	
}

function CHECK_WIFIDOG_IPTABLES_RULES(){
	$sock=new sockets();
	$unix=new unix();
	$EnableArticaHotSpot=intval($sock->GET_INFO("EnableArticaHotSpot"));
	if($EnableArticaHotSpot==0){return;}
	$ArticaHotSpotEnableMIT=$sock->GET_INFO("ArticaHotSpotEnableMIT");
	$ArticaHotSpotEnableProxy=$sock->GET_INFO("ArticaHotSpotEnableProxy");
	if(!is_numeric($ArticaHotSpotEnableMIT)){$ArticaHotSpotEnableMIT=1;}
	if(!is_numeric($ArticaHotSpotEnableProxy)){$ArticaHotSpotEnableProxy=1;}
	$ArticaHotSpotInterface=$sock->GET_INFO("ArticaHotSpotInterface");
	if($ArticaHotSpotInterface==null){$ArticaHotSpotInterface="eth0";}
	$Squid_http_port=intval($sock->GET_INFO("SquidHotSpotPort"));
	$Squid_hssl_port=intval($sock->GET_INFO("SquidHotSpotSSLPort"));
	
	$iptables=$unix->find_program("iptables");
	
	$HTTP_RULE ="-t nat -I WiFiDog_{$ArticaHotSpotInterface}_Internet -i $ArticaHotSpotInterface -p tcp -m tcp --dport 80 -m mark --mark 0x1 -j REDIRECT --to-ports $Squid_http_port";
	$HTTPS_RULE="-t nat -I WiFiDog_{$ArticaHotSpotInterface}_Internet -i $ArticaHotSpotInterface -m mark --mark 0x1 -p tcp --dport 443 -j REDIRECT --to-port $Squid_hssl_port";
	$HTTPS_RULE2="-t nat -I WiFiDog_{$ArticaHotSpotInterface}_Internet -i $ArticaHotSpotInterface -m mark --mark 0x2 -p tcp --dport 443 -j REDIRECT --to-port $Squid_hssl_port";
	

	
	if($ArticaHotSpotEnableProxy==1){
		Events("HotSpot must be connected to the HTTP proxy port $Squid_http_port Interface $ArticaHotSpotInterface");
		if(!CHECK_WIFIDOG_IPTABLES_EXISTS(80,$Squid_http_port)){
			squid_admin_mysql(0, "Fatal HotSpot is not link to $ArticaHotSpotInterface:$Squid_http_port [action=relink]", null,__FILE__,__LINE__);
			shell_exec("$iptables $HTTP_RULE");
			
		}else{
			Events("HotSpot 80 -> $Squid_http_port OK");
		}
		
	}
	
	if($ArticaHotSpotEnableMIT==1){
		
		if($Squid_hssl_port<10){
			echo "Warning $Squid_hssl_port < 10\n";
			return;
		}
		
		Events("HotSpot must be connected to the HTTPS proxy port $Squid_hssl_port Interface $ArticaHotSpotInterface");
		if(!CHECK_WIFIDOG_IPTABLES_EXISTS(443,$Squid_hssl_port)){
			squid_admin_mysql(0, "Fatal HotSpot is not link to $ArticaHotSpotInterface:$Squid_hssl_port [action=relink]", $HTTPS_RULE,__FILE__,__LINE__);
			shell_exec("$iptables $HTTPS_RULE");
			shell_exec("$iptables $HTTPS_RULE2");
				
		}else{
			Events("HotSpot 443 -> $Squid_hssl_port OK");
		}
	
	}	
	
	
	
	
}
function CHECK_WIFIDOG_IPTABLES_EXISTS($From_port,$to_port){
	$unix=new unix();
	$iptables_save=$unix->find_program("iptables-save");
	exec("$iptables_save 2>&1",$results);
	
	while (list ($md5, $line) = each ($results) ){
		if(!preg_match("#WiFiDog_.*?--dport $From_port.*?-j REDIRECT --to-ports $to_port#", $line)){
			continue;
		}
		
		return true;
		
	}
	
	
}

function CHECK_SQUID_EXTERNAL_LDAP(){

	$sock=new sockets();
	if(!$sock->SQUID_IS_EXTERNAL_LDAP()){return;}
	
	$unix=new unix();
	$filetime="/etc/artica-postfix/pids/".md5(__FILE__.__FUNCTION__);
	$TimeExec=$unix->file_time_min($filetime);

	$EXTERNAL_LDAP_AUTH_PARAMS=unserialize(base64_decode($sock->GET_INFO("SquidExternalAuth")));
	$ldap_server=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_server"];
	$ldap_port=intval($EXTERNAL_LDAP_AUTH_PARAMS["ldap_port"]);
	if($ldap_port==0){$ldap_port=389;}
	$ldap_suffix=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_suffix"];
	$CONNECTION=@ldap_connect($ldap_server,$ldap_port);

	if(!$CONNECTION){
		if($TimeExec>30){
			@unlink($filetime);
			squid_admin_mysql(0, "Connection to LDAP server failed $ldap_server:$ldap_port", null,__FILE__,__LINE__);
			@file_put_contents($filetime, time());
		}
		return;

	}
	@ldap_set_option($CONNECTION, LDAP_OPT_PROTOCOL_VERSION, 3);
	@ldap_set_option($CONNECTION, LDAP_OPT_REFERRALS, 0);
	@ldap_set_option($CONNECTION, LDAP_OPT_PROTOCOL_VERSION, 3); // on passe le LDAP en version 3, necessaire pour travailler avec le AD
	@ldap_set_option($CONNECTION, LDAP_OPT_REFERRALS, 0);

	$userdn=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_user"];
	$ldap_password=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_password"];
	$BIND=@ldap_bind($CONNECTION, $userdn, $ldap_password);

	if(!$BIND){
		$error=@ldap_err2str(@ldap_errno($CONNECTION));
		if (@ldap_get_option($CONNECTION, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {$error=$error." $extended_error";}
		@ldap_close($CONNECTION);
		if($TimeExec>30){
			@unlink($filetime);
			squid_admin_mysql(0, "Authenticate to LDAP server $ldap_server:$ldap_port failed $error", $error,__FILE__,__LINE__);
			@file_put_contents($filetime, time());
		}
		return ;
	}
	@unlink($filetime);
	@ldap_close($CONNECTION);

}


function import_old_logs(){
	$unix=new unix();
	$q=new mysql_squid_builder();
	if($q->COUNT_ROWS("import_srclogs")==0){return;}
	
	$sql="SELECT COUNT(*) as tcount FROM import_srclogs WHERE status=0";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$count=intval($ligne["tcount"]);
	if($GLOBALS["VERBOSE"]){echo "$count items\n";}
	
	$pid=intval($unix->PIDOF_PATTERN("exec.squid.influx.import.php --run-mysql"));
	if($GLOBALS["VERBOSE"]){echo "$pid PID\n";}
	if($pid>1){return;}
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	squid_admin_mysql(1, "Importation task stopped $count files to parse [action=start]", null,__FILE__,__LINE__);
	shell_exec("$nohup $php /usr/share/artica-postfix/exec.squid.influx.import.php --run-mysql >/dev/null 2>&1 &");
}


function PARANOID_MODE_CLEAN(){
	
	$unix=new unix();
	$cache_file="/etc/artica-postfix/pids/PARANOID_MODE_CLEAN";
	if($unix->file_time_min($cache_file)<30){return;}
	@unlink($cache_file);
	@file_put_contents($cache_file, time());
	
	$php=$unix->LOCATE_PHP5_BIN();
	$sock=new sockets();
	$UfdbEnableParanoidMode=intval($sock->GET_INFO("UfdbEnableParanoidMode"));
	if($UfdbEnableParanoidMode==0){return;}
	$UfdbEnableParanoidBlockR=intval($sock->GET_INFO("UfdbEnableParanoidBlockR"));

	if($UfdbEnableParanoidBlockR==0){$UfdbEnableParanoidBlockR=24;}
	$q=new mysql_squid_builder();
	$Count1=$q->COUNT_ROWS("webfilters_paranoid");
	$q->QUERY_SQL("DELETE FROM `webfilters_paranoid` WHERE zDate<DATE_SUB(NOW(),INTERVAL {$UfdbEnableParanoidBlockR} HOUR)");
	$Count2=$q->COUNT_ROWS("webfilters_paranoid");
	$return=$Count1-$Count2;
	if($Count1<>$Count2){
		squid_admin_mysql(1, "Release $return rules from paranoid mode", null,__FILE__,__LINE__);
		shell_exec("$php /usr/share/artica-postfix/exec.squid.global.access.php >/dev/null 2>&1");
		
	}
	
}
function taskset(){
	$processes=array();
	$cores=array();
	$unix=new unix();
	$taskset=$unix->find_program("taskset");
	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	while (list ($num, $line) = each ($f) ){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^cpu_affinity_map process_numbers=(.+?)\s+cores=(.+?)#",$line,$re)){continue;}
		$re[1]=trim($re[1]);
		$re[2]=trim($re[2]);
		if(strpos($re[1], ",")>0){
			$processes=explode(",",$re[1]);
			$cores=explode(",",$re[2]);
		}else{
			$processes[]=$re[1];
			$cores[]=$re[2];
		}
		break;
		
	}
	if(count($processes)==0){
		if($GLOBALS["VERBOSE"]){echo "No cpu_affinity_map found\n";}
		return;}
	
	
	while (list ($index, $ProcessNumber) = each ($processes) ){
		$pid=intval(KidGetPid($ProcessNumber));
		if($GLOBALS["VERBOSE"]){echo "$ProcessNumber -> $pid\n";}
		if($pid==0){continue;}
		
		$CpuSetGet=CpuSetGet($pid);
		$expected=$cores[$index]-1;
		echo "Process Number $ProcessNumber PID $pid task:{$CpuSetGet} Expected $expected\n";
		if($CpuSetGet<>$expected){
			if($GLOBALS["VERBOSE"]){echo "Setting PID $pid to $expected\n";}
			
			$cmd="$taskset -a -c -p $expected $pid";
			if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
			shell_exec("$cmd");
		}
	}
	
	
	
}

function KidGetPid($number){
	$unix=new unix();
	$pgrep=$unix->find_program("pgrep");
	exec("$pgrep -l -f \"\(squid-$number\" 2>&1",$results);
	while (list ($num, $line) = each ($results) ){
		$line=trim($line);
		if($line==null){continue;}
		if(preg_match("#pgrep#", $line)){continue;}
		if(!preg_match("#^([0-9]+)\s+#", $line,$re)){continue;}
		return $re[1];
	}
	
	
}

function CpuSetGet($pid){
	$unix=new unix();
	$taskset=$unix->find_program("taskset");
	exec("$taskset -c -p $pid 2>&1",$results);
	while (list ($num, $line) = each ($results) ){
		if(!preg_match("#$pid.*?:\s+(.+)#",$line,$re)){continue;}
		if(strpos($re[1], "-")>0){return 0;}
		return intval($re[1])+1;
	}
	
	
}



?>