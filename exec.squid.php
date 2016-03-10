<?php
ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
$GLOBALS["SCHEDULE_ID"]=0;
$GLOBALS["AD_PROGRESS"]=0;
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["ARGVS"]=implode(" ",$argv);
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if(preg_match("#--progress-activedirectory=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["AD_PROGRESS"]=$re[1];}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.remote-stats-appliance.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.acls.inc');
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}


$unix=new unix();

if(is_file("/etc/artica-postfix/FROM_ISO")){
	if($unix->file_time_min("/etc/artica-postfix/FROM_ISO")<1){return;}
}

$sock=new sockets();
$GLOBALS["RELOAD"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["NO_USE_BIN"]=false;
$GLOBALS["REBUILD"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["NOCACHES"]=false;
$GLOBALS["NOAPPLY"]=false;
$GLOBALS["NORELOAD"]=false;
$GLOBALS["SMOOTH"]=false;
$GLOBALS["RESTART"]=false;
$GLOBALS["BY_SCHEDULE"]=false;
$GLOBALS["NO_VERIF_CACHES"]=false;
$GLOBALS["PROGRESS"]=false;
$GLOBALS["EMERGENCY"]=false;
$GLOBALS["NOUFDBG"]=false;
$GLOBALS["FORMETA"]=false;

CheckWatdogCron();
CheckSettingsInc();
$GLOBALS["MYCOMMANDS"]=implode(" ",$argv);
WriteMyLogs("commands= {$GLOBALS["MYCOMMANDS"]}","MAIN",__FILE__,__LINE__);


if(preg_match("#--smooth#",implode(" ",$argv))){$GLOBALS["SMOOTH"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--noreload#",implode(" ",$argv))){$GLOBALS["NORELOAD"]=true;}
if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#--withoutloading#",implode(" ",$argv))){$GLOBALS["NO_USE_BIN"]=true;$GLOBALS["NORELOAD"]=true;}
if(preg_match("#--nocaches#",implode(" ",$argv))){$GLOBALS["NOCACHES"]=true;}
if(preg_match("#--noapply#",implode(" ",$argv))){$GLOBALS["NOCACHES"]=true;$GLOBALS["NOAPPLY"]=true;$GLOBALS["FORCE"]=true;}
if(preg_match("#--restart#",implode(" ",$argv))){$GLOBALS["RESTART"]=true;}
if(preg_match("#--byschedule#",implode(" ",$argv))){$GLOBALS["BY_SCHEDULE"]=true;}
if(preg_match("#--noverifcaches#",implode(" ",$argv))){$GLOBALS["NO_VERIF_CACHES"]=true;}
if(preg_match("#--progress#",implode(" ",$argv))){$GLOBALS["PROGRESS"]=true;}
if(preg_match("#--emergency#",implode(" ",$argv))){$GLOBALS["EMERGENCY"]=true;}
if(preg_match("#--noufdbg#",implode(" ",$argv))){$GLOBALS["NOUFDBG"]=true;}
if(preg_match("#--for-meta#",implode(" ",$argv))){$GLOBALS["FORMETA"]=true;}



if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

$squidbin=$unix->find_program("squid3");
$php5=$unix->LOCATE_PHP5_BIN();
if(!is_file($squidbin)){$squidbin=$unix->find_program("squid");}
$GLOBALS["SQUIDBIN"]=$squidbin;
$GLOBALS["CLASS_USERS"]=new usersMenus();
if($GLOBALS["VERBOSE"]){echo "squid binary=$squidbin\n";}

if($argv[1]=="--mime"){if($GLOBALS["VERBOSE"]){echo "->mime_conf()\n";}mime_conf();return;}
if($argv[1]=="--tests-caches"){test_caches();return;}
if($argv[1]=="--purge-dns"){purge_dns();return;}	
if($argv[1]=="--import-acls"){import_acls($argv[2]);return;}
if($argv[1]=="--import-webfilter"){import_webfilter($argv[2]);return;}	
if($argv[1]=="--quick-ban"){quick_bann();die();}
if($argv[1]=="--kreconfigure"){Reload_only_squid();die();}
if($argv[1]=="--artica-templates"){DefaultTemplatesInArtica();die();}
if($argv[1]=="--squid-reconfigure"){exec_reconfigure_squid();die();}
if($argv[1]=="--dump-tpl"){dump_templates();die();}
if($argv[1]=="--shm"){$GLOBALS["VERBOSE"]=true;echo $unix->TMPFS_CURRENTSIZE("/run/shm")."\n";}
if($argv[1]=="--SquidReloadInpublicAlias"){SquidReloadInpublicAlias();exit;}


if($argv[1]=="--disableUFDB"){disableUFDB($argv[2]);return;}
if($argv[1]=="--checks"){CheckConfig($argv[2]);return;}
if($argv[1]=="--notify-clients-proxy"){notify_remote_proxys();return;}
if($argv[1]=="--ping-clients-proxy"){notify_remote_proxys("PING");return;}
if($argv[1]=="--export-tables"){StatsApplianceExportTables();return;}	
if($argv[1]=="--reload-squid"){if($GLOBALS["VERBOSE"]){echo "reload in debug mode\n";} Reload_Squid();die();}
if($argv[1]=="--retrans"){retrans();die();}
if($argv[1]=="--certificate"){certificate_generate();die();}
if($argv[1]=="--caches"){BuildCaches();die();}
if($argv[1]=="--caches-reconstruct"){ReconstructCaches();die();}
if($argv[1]=="--compilation-params"){compilation_params();die();}
if($argv[1]=="--mysql-tpl"){DefaultTemplatesInArtica();die();}
if($argv[1]=="--tpl-save"){TemplatesInMysql();die();}
if($argv[1]=="--templates"){TemplatesInMysql();die();}
if($argv[1]=="--tpl-unique"){TemplatesUniqueInMysql($argv[2]);die();}
if($argv[1]=="--cache-infos"){caches_infos(true);die();}
if($argv[1]=="--watchdog"){watchdog();die();}
if($argv[1]=="--watchdog-config"){watchdog_config();die();}
if($argv[1]=="--build-schedules"){build_schedules();die();}
if($argv[1]=="--build-schedules-test"){build_schedules_tests();die();}
if($argv[1]=="--run-schedules"){run_schedules($argv[2]);die();}
if($argv[1]=="--schedules-extract"){extract_schedules();die();}
if($argv[1]=="--restart-squid"){restart_squid();die();}
if($argv[1]=="--restart-kav4proxy"){restart_kav4proxy();die();}
if($argv[1]=="--wrapzap"){wrapzap();die();}
if($argv[1]=="--wrapzap-compile"){wrapzap_compile();die();}
if($argv[1]=="--change-value"){change_value($argv[2],$argv[3]);die();}
if($argv[1]=="--smooth-build"){$GLOBALS["FORCE"]=true;build_smoothly();die();}
if($argv[1]=="--reconfigure-squid"){Reload_Squid();die();}
if($argv[1]=="--remove-cache"){remove_cache($argv[2]);die();}
if($argv[1]=="--rotate"){rotate_logs();die();}
if($argv[1]=="--replicate"){remote_appliance_restore_tables();die();}
if($argv[1]=="--banddebug"){bandwithdebug();die();}
if($argv[1]=="--acls"){output_acls();die();}
if($argv[1]=="--global-conf"){output_global_conf();die();}
if($argv[1]=="--remoteapp-conf"){remote_appliance_retreive_conf();die();}
if($argv[1]=="--remote-settings"){remote_appliance_getsettings();die();}
if($argv[1]=="--build-whitelists"){build_whitelist();die();}
if($argv[1]=="--build-blacklists"){build_blacklists(true);die();}
if($argv[1]=="--dyn-caches"){dyn_caches(true);die();}
if($argv[1]=="--dump-ports"){dump_ports(true);die();}
if($argv[1]=="--ntlm"){dump_ntlm(true);die();}
if($argv[1]=="--cache-peers"){echo dump_cache_peer();die();}




if($argv[1]=="--check-temp"){CheckTempConfig();die();}
if($argv[1]=="--test-sarg"){test_sarg();die();}
if($argv[1]=="--pactester"){squid_pactester();die();}
if($argv[1]=="--cache-rules"){cache_rules();die();}
if($argv[1]=="--band"){bandwith_rules();die();}
if($argv[1]=="--cert"){BuildSquidCertificate();die();}
if($argv[1]=="--defaults-schedules"){Defaultschedules();die();}
if($argv[1]=="--reconfigure"){$argv[1]="--build";}
if($argv[1]=="--build"){build();exit;}
writelogs("Unable to understand:`".@implode(" ", $argv)."`","MAIN",__FILE__,__LINE__);
// $EnableRemoteStatisticsAppliance -> Le proxy est un client.
// $EnableWebProxyStatsAppliance -> Le serveur est un serveur de statistiques.


function CheckSettingsInc(){
	$unix=new unix();
	$unix->Process1();
}

function WriteToSyslog_execsquid($text){
	if(!function_exists("syslog")){return;}
	$LOG_SEV=LOG_INFO;
	openlog("stats-appliance", LOG_PID , LOG_SYSLOG);
	syslog($LOG_SEV, $text);
	closelog();

}

function dump_cache_peer(){
	
	$squid=new squidbee();
	echo $squid->cache_peer();
}

function CheckWatdogCron(){
	$nice=EXEC_NICE();
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	
	if(!is_file("/etc/cron.d/artica-squid-watchdog")){
		$f=array();
		$f[]="MAILTO=\"\"";
		$f[]="3,6,9,11,13,15,17,19,21,23,25,27,29,31,33,35,37,39,41,43,45,47,49,51,55,57,59 * * * *  root $nice $php5 /usr/share/artica-postfix/exec.squid.watchdog.php >/dev/null 2>&1";
		$f[]="";
		@file_put_contents("/etc/cron.d/artica-squid-watchdog", @implode("\n", $f));
		WriteMyLogs("Creating Cron task cron.d/artica-squid-watchdog done",__FUNCTION__,__FILE__,__LINE__);
	}
	
	if(!is_file("/etc/cron.d/artica-ping-cloud")){
		$f=array();
		$f[]="MAILTO=\"\"";
		$f[]="15 0,2,4,6,8,10,12,14,16,18,20,22 * * * *  root $nice $php5 /usr/share/artica-postfix/exec.web-community-filter.php --bycron >/dev/null 2>&1";
		$f[]="";
		@file_put_contents("/etc/cron.d/artica-ping-cloud", @implode("\n", $f));
		WriteMyLogs("Creating Cron task cron.d/artica-ping-cloud done",__FUNCTION__,__FILE__,__LINE__);
	}	
}
function build_progress($text,$pourc){
	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/squid.build.progress";
	
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	
	if($GLOBALS["AD_PROGRESS"]>0){
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/squid.ad.progress", serialize($array));
		@chmod("/usr/share/artica-postfix/ressources/logs/web/squid.ad.progress",0755);
	}
	
	
}
function SquidReloadInpublicAlias_progress($text,$pourc){
	$echotext=$text;
	
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/SquidReloadInpublicAlias.progress";
	@mkdir(dirname($cachefile),0755,true);
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	if(!is_file($cachefile)){echo "!!! $cachefile No such file\n";}
	@chmod($cachefile,0755);
	sleep(1);
}
function SquidReloadInpublicAlias(){
	
	
	SquidReloadInpublicAlias_progress("{reloading_proxy_service}",15);
	squid_admin_mysql(1, "Proxy was reloaded by the Quick Alias reload", null,__FILE__,__LINE__);
	$unix=new unix();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	system("$squidbin -f /etc/squid3/squid.conf -k reconfigure");
	
	$sock=new sockets();
	if($sock->EnableUfdbGuard()==1){
		SquidReloadInpublicAlias_progress("{reloading_webfilter_service}",50);
		system("/etc/init.d/ufdb reload --force");
	}
	SquidReloadInpublicAlias_progress("{done}",100);
	
}



function build(){
		build_progress("{reconfigure} (1)",2);
		if($GLOBALS["VERBOSE"]){echo "Running build...\n";}
		$unix=new unix();
		$sock=new sockets();
		$users=new usersMenus();
		$forceCMD=null;
		$argv=null;
		$PHP=$unix->LOCATE_PHP5_BIN();
		$mypid=getmypid();
		if(isset($argv[1])){$argv=$argv[1];}
		$pids=$unix->PIDOF_PATTERN_ALL(basename(__FILE__).".*?$argv");
		if(count($pids)>1){
			build_progress("{already_process_exists_try_later}",110);
			while (list ($num, $ligne) = each ($pids) ){
				$cmdline=@file_get_contents("/proc/$num/cmdline");
				echo "Starting......: ".date("H:i:s")." [SERV]: [$mypid] Already process PID $num $cmdline exists..\n";
				echo "Starting......: ".date("H:i:s")." [SERV]: [$mypid] Running ".@file_get_contents("/proc/$num/cmdline")."\n";
			}
			die();
		}
		
		$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
		if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
		$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
		$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
		
		if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
		if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
		$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
		if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}
		$ServiceFTPEnabled=intval($sock->GET_INFO("ServiceFTPEnabled"));
		
		
		if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;
		$sock->SET_INFO("$EnableWebProxyStatsAppliance",1);}
		if($EnableWebProxyStatsAppliance==1){notify_remote_proxys();}
		if($UnlockWebStats==1){$EnableRemoteStatisticsAppliance=0;}
		
		//Vérifie le compte utilisateur.
		//------------------------------------------------------------------------------------------------------------------------------------------------------------		
		$unix->CreateUnixUser("squid","squid","Squid Cache Service");	
		$WindowsUpdateCachingDir=@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateCachingDir");
		if($WindowsUpdateCachingDir==null){$WindowsUpdateCachingDir="/home/squid/WindowsUpdate";}
		@mkdir($WindowsUpdateCachingDir,0755,true);
		@chmod($WindowsUpdateCachingDir,0755);
		@chown($WindowsUpdateCachingDir,"squid");
		@chgrp($WindowsUpdateCachingDir,"squid");
		
		$MustHave[]="/etc/squid3/artica-meta/whitelist-nets.db";
		$MustHave[]="/var/logs/cache.log";
		$MustHave[]="/etc/squid3/squid-block.acl";
		$MustHave[]="/etc/squid3/allowed-user-agents.acl";
		$MustHave[]="/etc/squid3/GlobalAccessManager_auth.conf";
		$MustHave[]="/etc/squid3/icap.conf";
		$MustHave[]="/etc/squid3/GlobalAccessManager_url_rewrite.conf";
		$MustHave[]="/etc/squid3/GlobalAccessManager_deny_cache.conf";
		$MustHave[]="/etc/squid3/GlobalAccessManager_deny.conf";
		$MustHave[]="/etc/squid3/squid-block.acl";
		$MustHave[]="/etc/squid3/clients_ftp.acl";
		$MustHave[]="/etc/squid3/allowed-user-agents.acl";
		$MustHave[]="/etc/squid3/whitelisted-computers-by-mac.acl";
		$MustHave[]="/etc/squid3/acls_center_meta.conf";
		
		
		
		while (list ($none, $path) = each ($MustHave)){
			echo "Starting......: ".date("H:i:s")." [SYS]: checking $path\n";
			if(!is_file($path)){@touch($path);}
			@chown($path,"squid");
			@chgrp($path, "squid");
		}
		
		if($GLOBALS["FORCE"]){$forceCMD=" --force";}
		$squidbin=$unix->LOCATE_SQUID_BIN();
		if(!is_file($squidbin)){
			build_progress("{squid_binary_not_found}",110);
			echo "Starting......: ".date("H:i:s")." [SERV]: Unable to stat squid binary, aborting..\n";
			die();
		}
		
		
		$EXEC_TIME_FILE="/etc/artica-postfix/".basename(__FILE__).".build.time";
		if(!$GLOBALS["FORCE"]){
			$time=$unix->file_time_min($EXEC_TIME_FILE);
			if($time==0){
				build_progress("Failed! Only one config per minute !!!",110);
				echo "Starting......: ".date("H:i:s")." [SERV]: Only one config per minute...\n";
				die();
			}
			
		}
		
		
		@unlink($EXEC_TIME_FILE);
		@file_put_contents($EXEC_TIME_FILE, time());
		if($GLOBALS["EMERGENCY"]){squid_admin_mysql(0, "Reconfiguring Proxy service after Emergency enabled", null,__FILE__,__LINE__);}
	
		$TimeStart=time();
		$EXEC_PID_FILE="/etc/artica-postfix/".basename(__FILE__).".build.pid";
		
		$kill=$unix->find_program("kill");
		$pid=@file_get_contents($EXEC_PID_FILE);
		if($unix->process_exists($pid,basename(__FILE__))){
			$TimePid=$unix->PROCCESS_TIME_MIN($pid);
			if($TimePid>30){
				posix_kill(intval($pid),9);
			}else{
				if(!$GLOBALS["FORCE"]){
					print "Starting......: ".date("H:i:s")." Checking (L.".__LINE__.") Squid Already executed pid $pid since {$TimePid}mn ...\n";
					die();
				}
			}
		}

		echo "Starting......: ".date("H:i:s")." [SERV]: is connected to remote appliance ? `$EnableRemoteStatisticsAppliance`\n";
		
		if($EnableRemoteStatisticsAppliance==1){
			$r=new squid_stats_appliance();
			echo "Starting......: ".date("H:i:s")." [SERV]: ################################\n";
			echo "Starting......: ".date("H:i:s")." [SERV]: # This server is connected to: #\n";
			echo "Starting......: ".date("H:i:s")." [SERV]: # $r->URI #\n";
			echo "Starting......: ".date("H:i:s")." [SERV]: ################################\n";
			remote_appliance_restore_tables();
		}
		
		build_progress("{reconfigure} (1)",5);
		
		if(!PortsConversion()){build_progress("{reconfigure} {failed} !PortsConversion",110);return;}
		squid_reconfigure_build_tool();
		build_progress("{reconfigure}",10);
		squid_pactester();
		build_progress("{reconfigure}",15);

		
		$childpid=posix_getpid();
		$sock=new sockets();
		$squid_user=SquidUser();
		$SQUID_CONFIG_PATH=$unix->SQUID_CONFIG_PATH();	
		$PHP=LOCATE_PHP5_BIN2();	
		$NOHUP=$unix->find_program("nohup");
		build_progress("{reconfigure}",20);
		
		@file_put_contents($EXEC_PID_FILE,$childpid);
		if(is_file("/etc/squid3/mime.conf")){shell_exec("/bin/chown squid:squid /etc/squid3/mime.conf");}
		$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
		if(!is_numeric("$EnableKerbAuth")){$EnableKerbAuth=0;}	
		
		if(!is_dir("/usr/share/squid-langpack")){TemplatesInMysql(true);exit;}
		echo "Starting......: ".date("H:i:s")." Checking squid kerberos authentification is set to $EnableKerbAuth\n";
		echo "Starting......: ".date("H:i:s")." Checking squid certificate\n";
		build_progress("{reconfigure} Check database",25);
		checkdatabase();
		build_progress("{reconfigure} certificates",30);
		certificate_generate();
		
		
		
		
		build_progress("{reconfigure}",35);
		remote_appliance_restore_tables();
		build_progress("{reconfigure}",40);
		echo "Starting......: ".date("H:i:s")." Instanciate squid library..\n";
		$squid=new squidbee();
		$squidbin=$unix->find_program("squid3");
		echo "Starting......: ".date("H:i:s")." checking squid binaries..\n";
		if(!is_file($squidbin)){$squidbin=$unix->find_program("squid");}
		echo "Starting......: ".date("H:i:s")." Binary: $squidbin\n";
		echo "Starting......: ".date("H:i:s")." Config: $SQUID_CONFIG_PATH\n";
		echo "Starting......: ".date("H:i:s")." User..: $squid_user\n";
		echo "Starting......: ".date("H:i:s")." Checking blocked sites\n";
		build_progress("{reconfigure} {building} NET ADS",45);
		shell_exec("$NOHUP $PHP ".basename(__FILE__)."/exec.squid.netads.php >/dev/null 2>&1 &");
		echo "Starting......: ".date("H:i:s")." Building master configuration\n";
		
		$squid->ASROOT=true;	

		
		echo "Starting......: ".date("H:i:s")." Checking Watchdog\n";
		build_progress("{reconfigure} checking Watchdog settings",46);
		watchdog_config();
		build_progress("{reconfigure} build errors",47);
		errors_details_txt();
		build_progress("{reconfigure} Checking caches",48);
		BuildCaches(true);
		build_progress("{reconfigure} Check files and security",49);
		CheckFilesAndSecurity();
		build_progress("{reconfigure} Building schedules",50);
		build_schedules(true);
		build_progress("{reconfigure} Building SSL passwords",89);
		build_sslpasswords();
		build_progress("{reconfigure} Building blacklists",90);
		build_blacklists();
		build_progress("{reconfigure} Building No caches list",91);
		build_denycaches();
		build_progress("{reconfigure} Building {GLOBAL_ACCESS_CENTER}",93);
		system("$PHP /usr/share/artica-postfix/exec.squid.global.access.php --nochek");

		if(!$GLOBALS["FORMETA"]){
			build_progress("{reconfigure} Building META ACLS",93);
			system("$PHP /usr/share/artica-postfix/exec.squid-meta.php --nochek");
		}
		
		build_progress("{reconfigure} Building main configuration",94);
		if(!ApplyConfig()){
			build_progress("Apply configuration failed",110);
			echo "Starting......: ".date("H:i:s")." Apply configuration failed....\n";
			return;
		}
		
		build_progress("{reconfigure} FTP Proxy service",95);
		system("$NOHUP $PHP /usr/share/artica-postfix/exec.ftpproxy-multi.php --build >/dev/null 2>&1 &");
		build_progress("{reconfigure} Wan Compressor Proxy service",95);
		system("$NOHUP $PHP /usr/share/artica-postfix/exec.wanproxy.php --build-squid >/dev/null 2>&1 &");
		build_progress("{reconfigure} FTP Transparent Proxy service",95);
		system("$PHP /usr/share/artica-postfix/exec.frox.php --build-squid >/dev/null 2>&1 &");
		
		
		
		build_progress("{checking_transparent_mode}",95);
		if($unix->IS_FIREHOLE_ACTIVE()){
			build_progress("{restarting_firewall}",95);
			system("$PHP /usr/share/artica-postfix/exec.firehol.php");
			system("/etc/init.d/firehol restart"); 
		}else{
			system("$PHP /usr/share/artica-postfix/exec.squid.transparent.php");
			system("$PHP /usr/share/artica-postfix/exec.secure.gateway.php");
			
			if(is_file("/etc/init.d/iptables-transparent")){
				build_progress("{restarting_firewall}",95);
				system("/etc/init.d/iptables-transparent restart");
			}
			
			if(is_file("/bin/iptables-parents.sh")){
				build_progress("{restarting_firewall} (parent)",95);
				system("/bin/iptables-parents.sh");
			}
			if(is_file("/bin/artica-secure-gateway.sh")){
				build_progress("{restarting_firewall} (Secure gateway)",95);
				shell_exec("/bin/artica-secure-gateway.sh");
			}
			
		}
		
		build_progress("{checking_wccp_mode}",95);
		system("$PHP /usr/share/artica-postfix/exec.squid.wccp.php --reconfigure");
		
		build_progress("{checking_mikrotik_mode}",95);
		system("$PHP /usr/share/artica-postfix/exec.mikrotik.php");
		
		
		build_progress("{reconfigure}",96);
		$GLOBALS["OUTPUT"]=true;
		if($GLOBALS["NOAPPLY"]){
			build_progress("{reconfiguring_proxy_service} {success}",100);
			return;
		}

		
		if(!$GLOBALS["RESTART"]){
			build_progress("{reloading_service}",91);
			if(!$GLOBALS["NORELOAD"]){
				Reload_Squid();
			}
		}
		
		if($GLOBALS["RESTART"]){
			if(!$GLOBALS["NORELOAD"]){
				build_progress("{stopping_service}",91);
				system("$PHP /usr/share/artica-postfix/exec.squid.watchdog.php --stop $forceCMD --byForceReconfigure");
				build_progress("{starting_service}",93);
				system("$PHP /usr/share/artica-postfix/exec.squid.watchdog.php --start $forceCMD --byForceReconfigure");
				build_progress("{starting_service}",95);
			}
		}
		
		
		
		build_progress("{building} Cached Web frontend pages",97);
		shell_exec("$NOHUP $PHP ".basename(__FILE__)."/exec.cache.pages.php --force >/dev/null 2>&1 &");
		
		$BuildAllTemplatesDone=$sock->GET_INFO("BuildAllTemplatesDone");
		if(!is_numeric($BuildAllTemplatesDone)){$BuildAllTemplatesDone=0;}
		if($BuildAllTemplatesDone==0){
			build_progress("{building} Templates schedules",97);
			echo "Starting......: ".date("H:i:s")." scheduling Building templates\n";
			sys_THREAD_COMMAND_SET("$PHP ". __FILE__." --tpl-save");
			$sock->SET_INFO("BuildAllTemplatesDone", 1);
		}
		
		build_progress("{building} Templates",98);
		sys_THREAD_COMMAND_SET("$PHP ". __FILE__." --mysql-tpl");
		
		
		build_progress("{reconfiguring_proxy_service} {success}",100);
		
		echo "Starting......: ".date("H:i:s")." Done (Took: ".$unix->distanceOfTimeInWords($TimeStart,time()).")\n";
		die();
}

	
	

	
	
function change_value($key,$val){
	$squid=new squidbee();
	$squid->global_conf_array[$key]=$val;
	$squid->SaveToLdap();
	echo "Starting......: ".date("H:i:s")." Squid change $key to $val (squid will be restarted)\n";
	
}


function mime_conf(){
	
	$sock=new sockets();
	
	$SquidHTTPTemplateLogoEnable=intval($sock->GET_INFO("SquidHTTPTemplateLogoEnable"));
	
	@mkdir("/usr/share/squid3/icons/silk",0755);
	@chown("/usr/share/squid3/icons/silk", "squid");
	@chgrp("/usr/share/squid3/icons/silk", "squid");
	@unlink("/usr/share/squid3/icons/silk/bigshield-256.png");
	@unlink("/usr/share/squid3/icons/silk/logo-artica-64.png");
	
	
	@copy("/usr/share/artica-postfix/img/bigshield-256.png","/usr/share/squid3/icons/silk/bigshield-256.png");
	@copy("/usr/share/artica-postfix/img/logo-artica-64.png","/usr/share/squid3/icons/silk/logo-artica-64.png");
	@chown("/usr/share/squid3/icons/silk/bigshield-256.png", "squid");
	@chgrp("/usr/share/squid3/icons/silk/bigshield-256.png", "squid");
	@chown("/usr/share/squid3/icons/silk/logo-artica-64.png", "squid");
	@chgrp("/usr/share/squid3/icons/silk/logo-artica-64.png", "squid");
	$SquidHTTPTemplateLogoLine=null;
	
	
	if($SquidHTTPTemplateLogoEnable==1){
		$SquidHTTPTemplateLogoPath=$sock->GET_INFO("SquidHTTPTemplateLogoPath");
		if(is_file($SquidHTTPTemplateLogoPath)){
			$SquidHTTPTemplateLogo=basename($SquidHTTPTemplateLogoPath);
			$SquidHTTPTemplateLogoDest="/usr/share/squid3/icons/silk/$SquidHTTPTemplateLogo";
			@unlink($SquidHTTPTemplateLogoDest);
			@copy("$SquidHTTPTemplateLogoPath",$SquidHTTPTemplateLogoDest);
			@chown($SquidHTTPTemplateLogoDest, "squid");
			@chgrp($SquidHTTPTemplateLogoDest, "squid");
			$SquidHTTPTemplateLogoLine="^internal-companylogo\$	-			silk/$SquidHTTPTemplateLogo		-	-";
			
		}else{
			if($GLOBALS["VERBOSE"]){echo "$SquidHTTPTemplateLogoPath no such file\n";}
		}
		
	}else{
		if($GLOBALS["VERBOSE"]){echo "$SquidHTTPTemplateLogoEnable is not 1\n";}
	}
	
	

	if($GLOBALS["VERBOSE"]){echo "$SquidHTTPTemplateLogoLine\n";}
	
	$f[]="\.gif\$			image/gif		silk/image.png			-	image	+download";
	$f[]="\.mime\$			www/mime		silk/page_white_text.png	-	ascii	+download";
	$f[]="^internal-dirup\$	-			silk/arrow_up.png		-	-";
	$f[]="^internal-artica-deny\$		-			silk/bigshield-256.png			-	-";
	$f[]="^internal-artica-logo\$		-			silk/logo-artica-64.png			-	-";
	$f[]="$SquidHTTPTemplateLogoLine";
	
	$f[]="^internal-dir\$		-			silk/folder.png			-	-";
	$f[]="^internal-link\$		-			silk/link.png			-	-";
	$f[]="^internal-logo\$		-			SN.png				-	-";
	$f[]="^internal-menu\$		-			silk/folder_table.png		-	-";
	$f[]="^internal-text\$		-			silk/page_white_text.png	-	-";
	$f[]="^internal-index\$	-			silk/folder_table.png		-	-";
	$f[]="^internal-image\$	-			silk/image.png			-	-";
	$f[]="^internal-sound\$	-			silk/music.png			-	-";
	$f[]="^internal-movie\$	-			silk/film.png			-	-";
	$f[]="^internal-telnet\$	-			silk/computer_link.png		-	-";
	$f[]="^internal-binary\$	-			silk/application.png		-	-";
	$f[]="^internal-unknown\$	-			silk/bullet_red.png		-	-";
	$f[]="^internal-view\$		-			silk/page_white.png		-	-";
	$f[]="^internal-download\$	-			silk/package_go.png		-	-";
	$f[]="\.bin\$		application/macbinary		silk/application.png		-	image	+download";
	$f[]="\.oda\$		application/oda			silk/application.png		-	image	+download";
	$f[]="\.exe\$		application/octet-stream	silk/application.png		-	image	+download";
	$f[]="\.pdf\$		application/pdf			silk/page_white_acrobat.png	-	image	+download";
	$f[]="\.ai\$		application/postscript		silk/page_green.png		-	image	+download +view";
	$f[]="\.eps\$		application/postscript		silk/page_green.png		-	image	+download +view";
	$f[]="\.ps\$		application/postscript		silk/page_green.png		-	image	+download +view";
	$f[]="\.rtf\$		text/rtf			silk/page_white_picture.png	-	ascii	+download +view";
	$f[]="\.Z\$		-				silk/compress.png		compress image	+download";
	$f[]="\.gz\$		-				silk/compress.png		gzip	image	+download";
	$f[]="\.bz2\$		application/octet-stream	silk/compress.png		-	image	+download";
	$f[]="\.bz\$		application/octet-stream	silk/compress.png		-	image	+download";
	$f[]="\.tgz\$		application/x-tar		silk/compress.png		gzip	image	+download";
	$f[]="\.csh\$		application/x-csh		silk/script.png			-	ascii	+download +view";
	$f[]="\.dvi\$		application/x-dvi		silk/page_white_text.png	-	image	+download";
	$f[]="\.hdf\$		application/x-hdf		silk/database.png		-	image	+download";
	$f[]="\.latex\$	application/x-latex		silk/page_white_text.png	-	ascii	+download +view";
	$f[]="\.lsm\$		text/plain			silk/page_white_text.png	-	ascii	+download +view";
	$f[]="\.nc\$		application/x-netcdf		silk/cd.png			-	image	+download";
	$f[]="\.cdf\$		application/x-netcdf		silk/cd.png			-	ascii	+download";
	$f[]="\.sh\$		application/x-sh		silk/script.png			-	ascii	+download +view";
	$f[]="\.tcl\$		application/x-tcl		silk/script.png			-	ascii	+download +view";
	$f[]="\.tex\$		application/x-tex		silk/page_white_text.png	-	ascii	+download +view";
	$f[]="\.texi\$		application/x-texinfo		silk/page_white_text.png	-	ascii	+download +view";
	$f[]="\.texinfo\$	application/x-texinfo		silk/page_white_text.png	-	ascii	+download +view";
	$f[]="\.t\$		application/x-troff		silk/page_white_text.png	-	ascii	+download +view";
	$f[]="\.roff\$		application/x-troff		silk/page_white_text.png	-	ascii	+download +view";
	$f[]="\.tr\$		application/x-troff		silk/page_white_text.png	-	ascii	+download +view";
	$f[]="\.man\$		application/x-troff-man		silk/page_white_magnify.png	-	ascii	+download +view";
	$f[]="\.me\$		application/x-troff-me		silk/page_white_text.png	-	ascii	+download +view";
	$f[]="\.ms\$		application/x-troff-ms		silk/page_white_text.png	-	ascii	+download +view";
	$f[]="\.src\$		application/x-wais-source	silk/script.png			-	ascii	+download";
	$f[]="\.zip\$		application/zip			silk/compress.png		-	image	+download";
	$f[]="\.bcpio\$	application/x-bcpio		silk/box.png			-	image	+download";
	$f[]="\.cpio\$		application/x-cpio		silk/box.png			-	image	+download";
	$f[]="\.gtar\$		application/x-gtar		silk/page_white_stack.png	-	image	+download";
	$f[]="\.rpm\$		application/x-rpm		silk/package.png		-	image	+download";
	$f[]="\.shar\$		application/x-shar		silk/script.png			-	image	+download +view";
	$f[]="\.sv4cpio\$	application/x-sv4cpio		silk/box.png			-	image	+download";
	$f[]="\.sv4crc\$	application/x-sv4crc		silk/box.png			-	image	+download";
	$f[]="\.tar\$		application/x-tar		silk/page_white_stack.png	-	image	+download";
	$f[]="\.ustar\$	application/x-ustar		silk/page_white_stack.png	-	image	+download";
	$f[]="\.au\$		audio/basic			silk/music.png			-	image	+download";
	$f[]="\.snd\$		audio/basic			silk/music.png			-	image	+download";
	$f[]="\.mp2\$		audio/mpeg			silk/music.png			-	image	+download";
	$f[]="\.mp3\$		audio/mpeg			silk/music.png			-	image	+download";
	$f[]="\.mpga\$		audio/mpeg			silk/music.png			-	image	+download";
	$f[]="\.aif\$		audio/x-aiff			silk/music.png			-	image	+download";
	$f[]="\.aiff\$		audio/x-aiff			silk/music.png			-	image	+download";
	$f[]="\.aifc\$		audio/x-aiff			silk/music.png			-	image	+download";
	$f[]="\.wav\$		audio/x-wav			silk/music.png			-	image	+download";
	$f[]="\.bmp\$		image/bmp			silk/image.png			-	image	+download";
	$f[]="\.ief\$		image/ief			silk/image.png			-	image	+download";
	$f[]="\.jpeg\$		image/jpeg			silk/photo.png			-	image	+download";
	$f[]="\.jpg\$		image/jpeg			silk/photo.png			-	image	+download";
	$f[]="\.jpe\$		image/jpeg			silk/photo.png			-	image	+download";
	$f[]="\.tiff\$		image/tiff			silk/photo.png			-	image	+download";
	$f[]="\.tif\$		image/tiff			silk/image.png			-	image	+download";
	$f[]="\.ras\$		image/x-cmu-raster		silk/image.png			-	image	+download";
	$f[]="\.pnm\$		image/x-portable-anymap		silk/image.png			-	image	+download";
	$f[]="\.pbm\$		image/x-portable-bitmap		silk/image.png			-	image	+download";
	$f[]="\.pgm\$		image/x-portable-graymap	silk/image.png			-	image	+download";
	$f[]="\.ppm\$		image/x-portable-pixmap		silk/image.png			-	image	+download";
	$f[]="\.rgb\$		image/x-rgb			silk/image.png			-	image	+download";
	$f[]="\.xbm\$		image/x-xbitmap			silk/image.png			-	image	+download";
	$f[]="\.xpm\$		image/x-xpixmap			silk/image.png			-	image	+download";
	$f[]="\.xwd\$		image/x-xwindowdump		silk/image.png			-	image	+download";
	$f[]="\.html\$		text/html			silk/page_world.png		-	ascii	+download +view";
	$f[]="\.htm\$		text/html			silk/page_world.png		-	ascii	+download +view";
	$f[]="\.css\$		text/css			silk/css.png			-	ascii	+download +view";
	$f[]="\.js\$		application/x-javascript	silk/script.png			-	ascii	+download +view";
	$f[]="\.c\$		text/plain			silk/page_white_c.png		-	ascii	+download";
	$f[]="\.h\$		text/plain			silk/page_white_c.png		-	ascii	+download";
	$f[]="\.cc\$		text/plain			silk/page_white_cplusplus.png	-	ascii	+download";
	$f[]="\.cpp\$		text/plain			silk/page_white_cplusplus.png	-	ascii	+download";
	$f[]="\.hh\$		text/plain			silk/page_white_c.png		-	ascii	+download";
	$f[]="\.m\$		text/plain			silk/script.png			-	ascii	+download";
	$f[]="\.f90\$		text/plain			silk/page_code.png		-	ascii	+download";
	$f[]="\.txt\$		text/plain			silk/page_white_text.png	-	ascii	+download";
	$f[]="\.asc\$		text/plain			silk/page_white_text.png	-	ascii	+download";
	$f[]="\.rtx\$		text/richtext			silk/page_white_picture.png	-	ascii	+download +view";
	$f[]="\.tsv\$		text/tab-separated-values	silk/script.png			-	ascii	+download +view";
	$f[]="\.etx\$		text/x-setext			silk/page_white_text.png	-	ascii	+download +view";
	$f[]="\.mpeg\$		video/mpeg			silk/film.png			-	image	+download";
	$f[]="\.mpg\$		video/mpeg			silk/film.png			-	image	+download";
	$f[]="\.mpe\$		video/mpeg			silk/film.png			-	image	+download";
	$f[]="\.qt\$		video/quicktime			silk/film.png			-	image	+download";
	$f[]="\.mov\$		video/quicktime			silk/film.png			-	image	+download";
	$f[]="\.avi\$		video/x-msvideo			silk/film.png			-	image	+download";
	$f[]="\.movie\$	video/x-sgi-movie		silk/film.png			-	image	+download";
	$f[]="\.cpt\$		application/mac-compactpro	silk/compress.png		-	image	+download";
	$f[]="\.hqx\$		application/mac-binhex40	silk/page_white_zip.png		-	image	+download";
	$f[]="\.mwrt\$		application/macwriteii		silk/page_white_text.png	-	image	+download";
	$f[]="\.msw\$		application/msword		silk/script.png			-	image	+download";
	$f[]="\.doc\$		application/msword		silk/page_white_word.png	-	image	+download +view";
	$f[]="\.xls\$		application/vnd.ms-excel	silk/page_excel.png		-	image	+download";
	$f[]="\.ppt\$		application/vnd.ms-powerpoint	silk/page_white_powerpoint.png	-	image	+download";
	$f[]="\.wk[s1234]\$	application/vnd.lotus-1-2-3	silk/script.png			-	image	+download";
	$f[]="\.mif\$		application/vnd.mif		silk/page_white_text.png	-	image	+download";
	$f[]="\.sit\$		application/x-stuffit		silk/compress.png		-	image	+download";
	$f[]="\.pict\$		application/pict		silk/picture.png		-	image	+download";
	$f[]="\.pic\$		application/pict		silk/picture.png		-	image	+download";
	$f[]="\.arj\$		application/x-arj-compressed	silk/compress.png		-	image	+download";
	$f[]="\.lzh\$		application/x-lha-compressed	silk/compress.png		-	image	+download";
	$f[]="\.lha\$		application/x-lha-compressed	silk/compress.png		-	image	+download";
	$f[]="\.zlib\$		application/x-deflate		silk/compress.png		deflate	image	+download";
	$f[]="README		text/plain			silk/information.png		-	ascii	+download";
	$f[]="^core\$		application/octet-stream	silk/bomb.png			-	image	+download";
	$f[]="\.core\$		application/octet-stream	silk/bomb.png			-	image	+download";
	$f[]="\.png\$		image/png			silk/image.png			-	image	+download";
	$f[]="\.cab\$		application/octet-stream	silk/compress.png		-	image	+download +view";
	$f[]="\.xpi\$		application/x-xpinstall		silk/plugin_add.png		-	image	+download";
	$f[]="\.class\$	application/octet-stream	silk/script_gear.png		-	image	+download";
	$f[]="\.java\$		text/plain			silk/cup.png			-	ascii	+download";
	$f[]="\.dcr\$		application/x-director		silk/script_palette.png		-	image	+download";
	$f[]="\.dir\$		application/x-director		silk/film.png			-	image	+download";
	$f[]="\.dxr\$		application/x-director		silk/film_key.png		-	image	+download";
	$f[]="\.djv\$		image/vnd.djvu			silk/image.png			-	image	+download";
	$f[]="\.djvu\$		image/vnd.djvu			silk/image.png			-	image	+download";
	$f[]="\.dll\$		application/octet-stream	silk/plugin.png			-	image	+download";
	$f[]="\.dms\$		application/octet-stream	silk/drive_disk.png		-	image	+download";
	$f[]="\.ez\$		application/andrew-inset	silk/bullet_red.png		-	image	+download";
	$f[]="\.ice\$		x-conference/x-cooltalk		silk/compress.png		-	image	+download";
	$f[]="\.iges\$		model/iges			silk/image.png			-	image	+download";
	$f[]="\.igs\$		model/iges			silk/image.png			-	image	+download";
	$f[]="\.kar\$		audio/midi			silk/music.png			-	image	+download";
	$f[]="\.mid\$		audio/midi			silk/music.png			-	image	+download";
	$f[]="\.midi\$		audio/midi			silk/music.png			-	image	+download";
	$f[]="\.mesh\$		model/mesh			silk/image.png			-	image	+download";
	$f[]="\.silo\$		model/mesh			silk/image.png			-	image	+download";
	$f[]="\.mxu\$		video/vnd.mpegurl		silk/film.png			-	image	+download";
	$f[]="\.pdb\$		chemical/x-pdb			silk/chart_line.png		-	image	+download";
	$f[]="\.pgn\$		application/x-chess-pgn		silk/bricks.png			-	image	+download";
	$f[]="\.ra\$		audio/x-realaudio		silk/music.png			-	image	+download";
	$f[]="\.ram\$		audio/x-pn-realaudio		silk/music.png			-	image	+download";
	$f[]="\.rm\$		audio/x-pn-realaudio		silk/music.png			-	image	+download";
	$f[]="\.sgml\$		text/sgml			silk/page_code.png		-	ascii	+download";
	$f[]="\.sgm\$		text/sgml			silk/page_code.png		-	ascii	+download";
	$f[]="\.skd\$		application/x-koan		silk/music.png			-	image	+download";
	$f[]="\.skm\$		application/x-koan		silk/music.png			-	image	+download";
	$f[]="\.skp\$		application/x-koan		silk/music.png			-	image	+download";
	$f[]="\.skt\$		application/x-koan		silk/music.png			-	image	+download";
	$f[]="\.smi\$		application/smil		silk/layers.png			-	image	+download";
	$f[]="\.smil\$		application/smil		silk/layers.png			-	image	+download";
	$f[]="\.so\$		application/octet-stream	silk/plugin.png			-	image	+download";
	$f[]="\.spl\$		application/x-futuresplash	silk/page_white_flash.png	-	image	+download";
	$f[]="\.swf\$		application/x-shockwave-flash	silk/page_white_flash.png	-	image	+download";
	$f[]="\.vcd\$		application/x-cdlink		silk/cd.png			-	image	+download";
	$f[]="\.vrml\$		model/vrml			silk/image.png			-	image	+download";
	$f[]="\.wbmp\$		image/vnd.wap.wbmp		silk/image.png			-	image	+download";
	$f[]="\.wbxml\$	application/vnd.wap.wbxml	silk/database_table.png		-	image	+download";
	$f[]="\.wmlc\$		application/vnd.wap.wmlc	silk/database_table.png		-	image	+download";
	$f[]="\.wmlsc\$	application/vnd.wap.wmlscriptc	silk/script.png			-	image	+download";
	$f[]="\.wmls\$		application/vnd.wap.wmlscript	silk/script.png			-	image	+download";
	$f[]="\.xht\$		application/xhtml		silk/page_world.png		-	ascii	+download";
	$f[]="\.xhtml\$	application/xhtml		silk/page_world.png		-	ascii	+download";
	$f[]="\.xml\$		text/xml			silk/page_world.png		-	ascii	+download";
	$f[]="\.xsl\$		text/xml			silk/layout.png			-	ascii	+download";
	$f[]="\.xyz\$		chemical/x-xyz			silk/chart_line.png		-	image	+download";
	$f[]=".		text/plain			silk/bullet_red.png		-	image	+download +view";
	$f[]="";
	@file_put_contents("/etc/squid3/mime.conf", @implode("\n", $f));
	@chown("/etc/squid3/mime.conf","squid");
	@chgrp("/etc/squid3/mime.conf","squid");
}


function build_sslpasswords(){
	
	$q=new mysql();
	$sql="SELECT `keyPassword`,`CommonName` FROM sslcertificates WHERE LENGTH(keyPassword)>0";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		$array["/etc/squid3/{$ligne["CommonName"]}.key"]=$ligne["keyPassword"];
	}
	@file_put_contents("/etc/squid3/sslpass", serialize($array));
	
}
function squid_pactester(){
	if(is_file("/usr/bin/pactester")){return;}
	if(!is_file("/usr/share/artica-postfix/bin/install/squid/pactester.tar.gz")){return;}
	$unix=new unix();
	$ldconfig=$unix->find_program("ldconfig");
	$tar=$unix->find_program("tar");
	shell_exec("$tar -xf /usr/share/artica-postfix/bin/install/squid/pactester.tar.gz -C /");
	if(!is_file("/usr/bin/pactester")){return;}
	@chmod("/usr/bin/pactester", 0755);
	shell_exec("$ldconfig >/dev/null 2>&1");
}

function output_global_conf(){
	$sock=new sockets();
	echo $sock->GET_INFO("ArticaSquidParameters");
	
}

function build_smoothly(){
		$unix=new unix();
		remote_appliance_restore_tables();
		$squid=new squidbee();
		ApplyConfig(true);
		Reload_Squid();
	
}

function remote_appliance_retreive_conf(){
	$unix=new unix();
	$sock=new sockets();
	$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
	if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}
	if($UnlockWebStats==1){return;}
	
	
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	if($EnableRemoteStatisticsAppliance==0){$GLOBALS[__FUNCTION__."_EXECUTED"]=true;return;}
	echo "Starting......: ".date("H:i:s")." [SYS]: Squid check -> squid_stats_appliance()\n";
	$s=new squid_stats_appliance();
	echo "Starting......: ".date("H:i:s")." [SERV] Replicate settings from the remote appliance...\n";
	$s->REPLICATE_ETC_ARTICA_CONFS();
	$s->Replicate();
	echo "Starting......: ".date("H:i:s")." Replicate all settings from the remote appliance done...\n";	
		
	
}
function remote_appliance_getsettings(){
	$sock=new sockets();
	$uuid=$sock->getFrameWork("services.php?GetMyHostId=yes");
	echo "UUID:$uuid\n";
	
	$sq=new squid_stats_appliance();
	$array=$sq->GetSquidDefinedSettings();
	
	
	if(is_array($array)){
		$sock=new sockets();
		while (list ($key, $val) = each ($array) ){
			if($key=="uuid"){continue;}
			echo "Starting......: ".date("H:i:s")." Replicate $key = `$val`\n";
			$sock->SET_INFO($key, $val);
		}
	}
	
}

function remote_appliance_restore_tables(){
	if(isset($GLOBALS[__FUNCTION__."_EXECUTED"])){return;}
	$unix=new unix();
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	if($EnableRemoteStatisticsAppliance==0){$GLOBALS[__FUNCTION__."_EXECUTED"]=true;return;}
	echo "Starting......: ".date("H:i:s")." [SYS]: Squid check if configuration must be retreived\n";
	remote_appliance_retreive_conf();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$dirname=dirname(__FILE__);
	$GLOBALS[__FUNCTION__."_EXECUTED"]=true;
}



function CheckFilesAndSecurity(){
	if(isset($GLOBALS[__FUNCTION__."_EXECUTED"])){return;}
	$GLOBALS[__FUNCTION__."_EXECUTED"]=true;
	$squid_user=SquidUser();
	$unix=new unix();
	$chown=$unix->find_program("chown");
	$chmod=$unix->find_program("chmod");
	$squid_user=SquidUser();
	$ln=$unix->find_program("ln");
	$rm=$unix->find_program("rm");
	if(!is_dir("/var/logs")){@mkdir("/var/logs",0755,true);}
	
	$squidlogdir="/var/log/squid";
	if(is_link($squidlogdir)){$squidlogdir=@readlink($squidlogdir);}
	$baselogdir=dirname($squidlogdir);
	@chmod($baselogdir,0755);

	@mkdir("/var/lib/squid/session",0755,true);
	@mkdir("/usr/local/share/artica",0755,true);
	@mkdir("/var/squid/cache",0755,true);
	@mkdir("/var/lib/ssl_db",0755,true);
	@mkdir("/usr/share/squid-langpack/templates",0755,true);
	@mkdir("/var/log/squid/nudity",0755,true);
	if(!is_dir("/var/run/squid")){@mkdir("/var/run/squid",0755,true);}
	@mkdir("/var/log/squid/squid",0755,true);
	
	if(!is_dir("/usr/share/squid-langpack/templates/lb-lu")){shell_exec("$ln -sf /usr/share/squid-langpack/templates/en-us /usr/share/squid-langpack/templates/lb-lu");}
	
	
	$MustHave[]="/etc/squid3/artica-meta/whitelist-nets.db";
	$MustHave[]="/var/logs/cache.log";
	$MustHave[]="/etc/squid3/squid-block.acl";
	$MustHave[]="/etc/squid3/allowed-user-agents.acl";
	$MustHave[]="/etc/squid3/GlobalAccessManager_auth.conf";
	$MustHave[]="/etc/squid3/icap.conf";
	$MustHave[]="/etc/squid3/GlobalAccessManager_url_rewrite.conf";
	$MustHave[]="/etc/squid3/GlobalAccessManager_deny_cache.conf";
	$MustHave[]="/etc/squid3/GlobalAccessManager_deny.conf";
	$MustHave[]="/etc/squid3/squid-block.acl";
	$MustHave[]="/etc/squid3/clients_ftp.acl";
	$MustHave[]="/etc/squid3/allowed-user-agents.acl";
	

	while (list ($none, $path) = each ($MustHave)){
		echo "Starting......: ".date("H:i:s")." [SYS]: checking $path\n";
		if(!is_file($path)){@touch($path);}
		@chown($path,"squid");
		@chgrp($path, "squid");
	}

	$unix->chmod_func(0755, "/etc/artica-postfix/settings/Daemons");
	$unix->chmod_func(0755, "/etc/artica-postfix/settings/Daemons/*");
	
	//helpers
	@chmod("/usr/share/artica-postfix/ufdbgclient.php",0755);
	@chown("/usr/share/artica-postfix/ufdbgclient.php","squid");
	@chgrp("/usr/share/artica-postfix/ufdbgclient.php","squid");
	
	@chown("/usr/share/squid-langpack","squid");
	@chgrp("/usr/share/squid-langpack","squid");
	@chown("/usr/share/squid-langpack/templates","squid");
	@chgrp("/usr/share/squid-langpack/templates","squid");
	
	$GLOBALS["LogFileDeamonLogDir"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/LogFileDeamonLogDir");
	if($GLOBALS["LogFileDeamonLogDir"]==null){$GLOBALS["LogFileDeamonLogDir"]="/home/artica/squid/realtime-events";}
	
	if(is_file("/usr/local/ArticaStats/bin/postgres")){
		$GLOBALS["LogFileDeamonLogDir"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/LogFileDeamonLogPostGresDir");
		if($GLOBALS["LogFileDeamonLogDir"]==null){$GLOBALS["LogFileDeamonLogDir"]="/home/artica/squid-postgres/realtime-events";}
	}
	
	@mkdir($GLOBALS["LogFileDeamonLogDir"],0755,true);
	@chmod($GLOBALS["LogFileDeamonLogDir"], 0755);
	@chown($GLOBALS["LogFileDeamonLogDir"],"squid");
	@chgrp($GLOBALS["LogFileDeamonLogDir"], "squid");
	

	
	
	$items[]="/etc/squid3/SquidNudityScanParams";
	$items[]="/var/squid/cache";
	$items[]="/var/lib/squid/session";
	$items[]="/etc/squid3/*";
	$items[]="$squidlogdir";
	$items[]="$squidlogdir/*";
	$items[]="$squidlogdir/nudity";
	$items[]="/var/logs";
	$items[]="/var/lib/ssl_db";
	$items[]="/var/logs/cache.log";
	$items[]="/home/squid/cache";
	$items[]="/home/squid";
	$items[]="/var/run/squid/*";
	$items[]="/usr/local/share/artica";
	
	
	
	while (list ($none, $path) = each ($items)){
		echo "Starting......: ".date("H:i:s")." [SYS]: permissions on \"$path\"\n";
		$unix->chown_func($squid_user, $squid_user,$path);
		$unix->chmod_func(0755, $path);
	}
	
	
	
	
	
	$squid_locate_pinger=$unix->squid_locate_pinger();
	$setcap=$unix->find_program("setcap");
	if(is_file($squid_locate_pinger)){
		shell_exec("$chmod 0755 $squid_locate_pinger");
		@chown($squid_locate_pinger, "squid");
		@chgrp($squid_locate_pinger,"squid");	
		if(is_file("$setcap")){
			shell_exec("$setcap cap_net_raw=pe $squid_locate_pinger");
		}else{
			$unix->DEBIAN_INSTALL_PACKAGE("libcap2-bin");
		}
	
	}
	$unix->THREAD_COMMAND_SET("$chown -R squid:squid /var/lib/squid/session");
	
	$GetCachesInsquidConf=$unix->SQUID_CACHE_FROM_SQUIDCONF();
	while (list ($CacheDirectory, $type) = each ($GetCachesInsquidConf)){
		@chmod($CacheDirectory, 0755);
	}
	
		
	if(is_dir("/usr/share/squid-langpack")){$unix->chown_func($squid_user,$squid_user,"/usr/share/squid-langpack");}
	if(!is_file("$squidlogdir/squidGuard.log")){@file_put_contents("/var/log/squid/squidGuard.log","#");}
	
	
	
	
	$unix->Winbindd_privileged_SQUID();
	
	$tpls["ERR_CONFLICT_HOST"]="<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01//EN\" \"http://www.w3.org/TR/html4/strict.dtd\"> <html><head> <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"> <title>ERROR: The requested URL could not be retrieved</title> <style type=\"text/css\"><!--   %l  body :lang(fa) { direction: rtl; font-size: 100%; font-family: Tahoma, Roya, sans-serif; float: right; } :lang(he) { direction: rtl; }  --></style> </head><body id=%c> <div id=\"titles\"> <h1>ERROR</h1> <h2>The requested URL could not be retrieved</h2> </div> <hr>  <div id=\"content\"> <p>The following error was encountered while trying to retrieve the URL: <a href=\"%U\">%U</a></p>  <blockquote id=\"data\"> <pre>URI Host Conflict</pre> </blockquote>  <p>This means the domain name you are trying to access apparently no longer exists on the machine you are requesting it from.</p>  <p>Some possible problems are:</p> <ul> <li>The domain may have moved very recently. Trying again will resolve that.</li> <li>The website may require you to use a local country-based version. Using your ISP provided DNS server(s) should resolve that.</li> </ul>  <p>Your cache administrator is <a href=\"mailto:%w%W\">%w</a>.</p> <br> </div>  <hr> <div id=\"footer\"> <p>Generated %T by %h (%s)</p> <!-- %c --> </div> </body></html>";
	$tpls["MGR_INDEX"]="\n";
	
	while (list ($file, $lined) = each ($tpls)){
		if(!is_file("/usr/share/squid-langpack/en/$file")){@file_put_contents("/usr/share/squid-langpack/en/$file", $lined);}
		if(!is_file("/usr/share/squid-langpack/templates/$file")){@file_put_contents("/usr/share/squid-langpack/templates/$file",$lined);}
	}
	
	$ssl_crtd=locate_ssl_crtd();
	if(!is_file("/var/lib/ssl_db/index.txt")){
		if(is_file($ssl_crtd)){
			if(is_dir("/var/lib/ssl_db")){shell_exec("$rm -rf /var/lib/ssl_db");}
			shell_exec("$ssl_crtd -c -s /var/lib/ssl_db");
			$unix->chown_func($squid_user, $squid_user,"/var/lib/ssl_db/*");
		}else{
			echo "Starting......: ".date("H:i:s")." unable to stat ssl_crtd to fill `/var/lib/ssl_db`\n";	
		}
	}
}




function watchdog($direction){
	$EXEC_PID_FILE="/etc/artica-postfix/".basename(__FILE__).".".__FUNCTION__.".$direction.pid";
	$unix=new unix();
	if($unix->process_exists(@file_get_contents($EXEC_PID_FILE))){
		writelogs("Starting......: ".date("H:i:s")." Checking squid $direction executed pid ". @file_get_contents($EXEC_PID_FILE)."...",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	$childpid=posix_getpid();
	@file_put_contents($EXEC_PID_FILE,$childpid);

	if($direction=="start"){
		shell_exec("/etc/init.d/artica-postfix start squid-cache");
		return;
	}
	
	if($direction=="stop"){
		shell_exec("/etc/init.d/artica-postfix stop squid-cache");
		return;
	}	
	
}


function locate_ssl_crtd(){
	return locate_generic_bin("ssl_crtd");

	
}

function locate_generic_bin($program){
	$unix=new unix();
	return $unix->squid_locate_generic_bin($program);
	
}



function remove_cache($cacheenc){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$sock=new sockets();
	$PidFile="/etc/artica-postfix/pids/".md5("remove-$cacheenc").".pid";
	
	
	$pid=$unix->get_pid_from_file($PidFile);
	if($unix->process_exists($pid,basename(__FILE__))){
		WriteToSyslogMail("remove_cache():: Another artica script running pid $pid, aborting ...", basename(__FILE__));
		return;
	}

	$directory=base64_decode($cacheenc);
	if(!is_dir($directory)){WriteToSyslogMail("remove_cache():: $directory no such directory", basename(__FILE__));return;}
	$rm=$unix->find_program("rm");
	shell_exec("$rm -rf $directory");
	ApplyConfig();
	shell_exec('/etc/init.d/artica-postfix restart squid-cache');
	caches_infos();
	
}
function SQUID_PID(){
	$unix=new unix();
	$squidbin=$unix->find_program("squid");
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
	$pid=$unix->get_pid_from_file($unix->LOCATE_SQUID_PID());
	if(!$unix->process_exists($pid)){
		$pid=$unix->PIDOF($squidbin);
	}

	return $pid;

}

function Start_squid(){
	system("/etc/init.d/squid start");
	system("/etc/init.d/auth-tail restart");
	
}

function dyn_caches($aspid=false){
	$unix=new unix();
	$FINALARRAY=array();
	$f=array();
	$sock=new sockets();
	$PidFile="/etc/artica-postfix/pids/squid_build_dyn_caches.pid";
	
	if($aspid){
		$pid=$unix->get_pid_from_file($PidFile);
		if($pid<>getmypid()){
			if($unix->process_exists($pid,basename(__FILE__))){
				echo "Starting......: ".date("H:i:s")." Dynamic caches: Another artica script running pid $pid, aborting ...\n";
				WriteToSyslogMail("dyn_caches():: Another artica script running pid $pid, aborting ...", basename(__FILE__));
				return;
			}
		}
	}

	$q=new mysql_squid_builder();
	$LEVELS[1]="1440\t20%\t1440";
	$LEVELS[2]="1440\t30%\t".(1440*2);
	$LEVELS[3]=(1440*1)."\t40%\t".(1440*4);
	$LEVELS[4]=(1440*2)."\t50%\t".(1440*6);
	$LEVELS[5]=(1440*3)."\t60%\t".(1440*7);
	$LEVELS[6]=(1440*4)."\t70%\t".(1440*9);
	$LEVELS[7]=(1440*5)."\t80%\t".(1440*10);
	$LEVELS[8]=(1440*6)."\t85%\t".(1440*14);
	$LEVELS[9]=(1440*7)."\t90%\t".(1440*20);
	$LEVELS[10]=(1440*8)."\t95%\t".(1440*30);
	
	
	
	@unlink("/etc/squid3/refresh_patterns.conf");
	@touch("/etc/squid3/refresh_patterns.conf");
	@chown("/etc/squid3/refresh_patterns.conf","squid");
	@chmod("/etc/squid3/refresh_patterns.conf",0755);
	
	$SquidCacheLevel=$sock->GET_INFO("SquidCacheLevel");
	if(!is_numeric($SquidCacheLevel)){$SquidCacheLevel=4;}
	
	$ARRAY=unserialize(base64_decode($sock->GET_INFO("SquidDynamicCaches")));
	if(!is_numeric($ARRAY["ENABLED"])){if($SquidCacheLevel>3){$ARRAY["ENABLED"]=1; }}
	
	if($SquidCacheLevel<3){$ARRAY["ENABLED"]=0;}
	
	if(!isset($ARRAY["MAX_WWW"])){$ARRAY["MAX_WWW"]=100;}
	if(!isset($ARRAY["ENABLED"])){$ARRAY["ENABLED"]=0;}
	if(!isset($ARRAY["LEVEL"])){$ARRAY["LEVEL"]=5;}
	if(!isset($ARRAY["INTERVAL"])){$ARRAY["INTERVAL"]=420;}
	if(!isset($ARRAY["MAX_TTL"])){$ARRAY["MAX_TTL"]=15;}
	
	if(!is_numeric($ARRAY["MAX_WWW"])){$ARRAY["MAX_WWW"]=100;}
	if(!is_numeric($ARRAY["ENABLED"])){$ARRAY["ENABLED"]=0;}
	if(!is_numeric($ARRAY["LEVEL"])){$ARRAY["LEVEL"]=5;}
	if(!is_numeric($ARRAY["INTERVAL"])){$ARRAY["INTERVAL"]=420;}
	if(!is_numeric($ARRAY["MAX_TTL"])){$ARRAY["MAX_TTL"]=15;}
	
	
	if($ARRAY["ENABLED"]==0){return;}
	
	
	
	$sql="SELECT * FROM main_cache_dyn WHERE enabled=1";
	$results=$q->QUERY_SQL($sql);
	$Count=mysql_num_rows($results);
	if($Count==0){return ;}
	$c=0;
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$familysite=$ligne["familysite"];
		$OnlyImages=intval($ligne["OnlyImages"]);
		$OnlyeDoc=intval($ligne["OnlyeDoc"]);
		$OnlyMultimedia=intval($ligne["OnlyMultimedia"]);
		$OnlyFiles=intval($ligne["OnlyFiles"]);
		$ignore_reload=null;
		if(!isset($ligne["LEVEL"])){$ligne["LEVEL"]=6;}
		
		$addons=null;
		$PZ=array();
		if($OnlyImages==1){
			$PZ[]="jp(e?g|e|2)|tiff?|bmp|gif|png|ico";
		}
		if($OnlyeDoc==1){
			$PZ[]="css|htm|js";
		}
		if($OnlyMultimedia==1){
			$PZ[]="avi|ac4|mp(e?g|a|e|1|2|3|4)|mk(a|v)|ms(i|u|p)|og(x|v|a|g)|rm|r(a|p)m|snd|vob";
		}
		if($OnlyFiles==1){
			$PZ[]="rar|jar|gz|tgz|bz2|iso|m1v|m2(v|p)|mo(d|v)|arj|lha|lzh|zip|tar|3gp|7z|ace|asx|bin|deb|divx|dvr-ms|ram|rpm|exe|inc|cab|qt|pp(t?x)|s|t)|pdf|rtf|wax|wm(a|v)|wmx|wpl|cb(r|z|t)|xl(s?x)|do(c?x)|flv|x-flv";
		}		
		$familysite=str_replace(".", "\.", $familysite);
		$level_text=$LEVELS[$ligne["level"]];
		if(count($PZ)>0){
			$familysite="$familysite\/(.*)\.(".@implode("|", $PZ).")";
			
		}
		
		if($ligne["LEVEL"]>4){
			$ignore_reload="ignore-reload ";
		}
		
		if($ligne["LEVEL"]>6){
			$addons=" ignore-no-cache ignore-private ignore-auth ignore-no-store ignore-must-revalidate";
		}
		
		$f[]="#$familysite Level {$ligne["LEVEL"]} OnlyImages=$OnlyImages";
		$FAMS[]=$familysite.": Level: {$ligne["level"]} \"$level_text\"";
		$f[]="refresh_pattern -i $familysite $level_text {$ignore_reload}override-expire override-lastmod reload-into-ims$addons";
	}
	
	@file_put_contents("/etc/squid3/refresh_patterns.conf", @implode("\n", $f)."\n");
	if($GLOBALS["RELOAD"]){
		squid_admin_mysql(1, "Reloading Proxy service for optimize caches rules", @implode("\n", $FAMS),__FILE__,__LINE__);
		shell_exec("/etc/init.d/squid reload --script=".basename(__FILE__));
	}
	
}

function Defaultschedules($aspid=false){
	$PidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$PidTime="/etc/artica-postfix/pids/exec.squid.php.Defaultschedules.time";
	if($GLOBALS["VERBOSE"]){echo "$PidTime\n";}
	$unix=new unix();
	if(!$aspid){
		$pid=$unix->get_pid_from_file($PidFile);
		if($pid<>getmypid()){
			if($unix->process_exists($pid,basename(__FILE__))){
				echo "Starting......: ".date("H:i:s")." Blacklists: Another artica script running pid $pid, aborting ...\n";
				WriteToSyslogMail("build_blacklists():: Another artica script running pid $pid, aborting ...", basename(__FILE__));
				return;
			}
		}
	}
	
	if(!$GLOBALS["VERBOSE"]){
		$time=$unix->file_time_min($PidTime);
		if($time<120){return;}
	}
	@unlink($PidTime);
	@file_put_contents($PidTime, time());
	
	$q=new mysql_squid_builder();
	$q->CheckDefaultSchedules();
}

function build_blacklists($aspid=false){
	$unix=new unix();
	$FINALARRAY=array();
	$f=array();
	@unlink("/etc/squid3/www-blacklists.db");

	
	
}

function build_denycaches($canreconfigure=false){
	@unlink("/etc/squid3/www-denycache.db");
}


function exec_reconfigure_squid(){
	$unix=new unix();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	$php=$unix->LOCATE_PHP5_BIN();
	if(!is_file($squidbin)){return;}
	$pid=SQUID_PID();
	
	$squid_checks=new squid_checks();
	if(!$squid_checks->squid_parse()){return;}
	
	if(!$unix->process_exists($pid)){
		shell_exec("/etc/init.d/squid start");
		return;
	}
	
	echo "Starting......: ".date("H:i:s")." [ACLS]: Reconfigure Squid-cache\n";
	
	$datas=shell_exec("/etc/init.d/squid reload --force --script=exec.squid.php/".__LINE__);
	squid_admin_mysql(1, "Ask to reload the Proxy", $datas,__FILE__,__LINE__);
	$sock=new sockets();
	$EnableTransparent27=intval($sock->GET_INFO("EnableTransparent27"));
	if($EnableTransparent27==1){
		system("/etc/init.d/squid-nat reload --force --script=".basename(__FILE__));
	}
	
}


function urlrewriteaccessdeny(){
	$q=new mysql();
	$q2=new mysql_squid_builder();
	$acl=new squid_acls();
	$sql="SELECT * FROM urlrewriteaccessdeny";
	
	if(!$q->TABLE_EXISTS("urlrewriteaccessdeny", "artica_backup")){
		@file_put_contents("/etc/squid3/url_rewrite_program.deny.db", "# urlrewriteaccessdeny table does not exists\n");
		@chown("/etc/squid3/url_rewrite_program.deny.db", "squid");
		@chgrp("/etc/squid3/url_rewrite_program.deny.db","squid");
		return;
	}
	
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "Starting......: ".date("H:i:s")." [ACLS]: $q->mysql_error\n";return; }

	$array=array();
	while ($ligne = mysql_fetch_assoc($results)) {
		$ligne["items"]=trim($ligne["items"]);
		if($ligne["items"]==null){continue;}
		$array[]=$ligne["items"];
	}
	
	$acl=new squid_acls();
	$url_rewrite_program=$acl->clean_dstdomains($array);
	
	
	
	echo "Starting......: ".date("H:i:s")." [ACLS]: ".count($url_rewrite_program)." Whitelisted webistes from webfiltering\n";
	@file_put_contents("/etc/squid3/url_rewrite_program.deny.db", @implode("\n", $url_rewrite_program)."\n");
	@chown("/etc/squid3/url_rewrite_program.deny.db", "squid");
	@chgrp("/etc/squid3/url_rewrite_program.deny.db","squid");	
	
}


function build_progress_wb($text,$pourc){
	
	$cachefile="/usr/share/artica-postfix/ressources/logs/squid.wb.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	if($GLOBALS["PROGRESS"]){sleep(1);}
}
function build_progress_reload($text,$pourc){
	if($GLOBALS["VERBOSE"]){echo "{$pourc}% $text\n";}
	
	echo "{$pourc}% $text\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/squid.reload.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}
function build_progress_rotation($text,$pourc){
	if($GLOBALS["VERBOSE"]){echo "{$pourc}% $text\n";}
	if(!$GLOBALS["PROGRESS"]){return;}
	echo "{$pourc}% $text\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/squid.rotate.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	sleep(1);
}
function build_progress_schedules($text,$pourc){
	if($GLOBALS["VERBOSE"]){echo "{$pourc}% $text\n";}
	if(!$GLOBALS["PROGRESS"]){return;}
	echo "{$pourc}% $text\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/squid.databases.schedules.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	
}



function build_whitelist(){
	$sock=new sockets();
	$unix=new unix();
	$UfdbUseArticaClient=$sock->GET_INFO("UfdbUseArticaClient");
	if(!is_numeric($UfdbUseArticaClient)){$UfdbUseArticaClient=1;}
	$PrivoxyEnabled=intval($sock->GET_INFO("PrivoxyEnabled"));
	if($UfdbUseArticaClient==1){
		if(!$GLOBALS["NOUFDBG"]){
			build_progress_wb("* * * Use Whitelist * * *",20);
			$php=$unix->LOCATE_PHP5_BIN();
			system("$php /usr/share/artica-postfix/exec.ufdbgclient.whitelists.php");
		}
		
		
		
		return;
	}
	build_progress_wb("{compiling}",30);
	urlrewriteaccessdeny();
	build_progress_wb("{building} {blacklists}",31);
	build_blacklists();
	build_progress_wb("{building} deny cache",32);
	build_denycaches(true);
	
	build_progress_wb("{building} {APP_PRIVOXY} = $PrivoxyEnabled",33);
	
	
	if($PrivoxyEnabled==1){
		build_progress_wb("{compiling} {APP_PRIVOXY}",40);
		shell_exec("/etc/init.d/privoxy restart");
		build_progress_wb("{compiling} {APP_PRIVOXY} {done}",45);
	}
	
	
	build_progress_wb("{reloading}",50);
	system("/etc/init.d/squid reload --script=".basename(__FILE__));
	$sock=new sockets();
	$EnableTransparent27=intval($sock->GET_INFO("EnableTransparent27"));
	$PrivoxyEnabled=intval($sock->GET_INFO("PrivoxyEnabled"));
	if($EnableTransparent27==1){
		build_progress_wb("{reloading} NAT",60);
		system("/etc/init.d/squid-nat reload --script=".basename(__FILE__));
	}

	
	
	build_progress_wb("{success}",100);
}

function Reload_only_squid(){
	$unix=new unix();
	$results=array();
	$force=null;
	if(!is_file($GLOBALS["SQUIDBIN"])){
		$GLOBALS["SQUIDBIN"]=$unix->find_program("squid");
		if(!is_file($GLOBALS["SQUIDBIN"])){$GLOBALS["SQUIDBIN"]=$unix->find_program("squid3");}
	}	
	
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$file=basename($trace[1]["file"]);
			$function=$trace[1]["function"];
			$line=$trace[1]["line"];
			$called="Called by $function() from line $line";
		}
			
	}
	
	if($GLOBALS["FORCE"]){$force=" --force";}
	build_progress_reload("{reloading}",50);
	squid_watchdog_events("Reconfiguring Proxy parameters...");
	
	$cmd="/etc/init.d/squid reload$force --script=".basename(__FILE__)." >/dev/null 2>&1 &";
	shell_exec($cmd);
	
	
	while (list ($num, $val) = each ($results)){
		echo "Starting......: ".date("H:i:s")." [RELOAD]: $val\n";
		
	}
	build_progress_reload("{reloading}",70);
	$pgrep=$unix->find_program("pgrep");
	$kill=$unix->find_program("kill");
	exec("$pgrep -l -f \"exec.logfile_daemon.php\" 2>&1",$results2);
	while (list ($num, $val) = each ($results2)){
		if(preg_match("#pgrep#", $val)){continue;}
		if(!preg_match("#^([0-9]+)\s+#", $val,$re)){continue;}
		$pid=$re[1];
		$processtime=$unix->PROCCESS_TIME_MIN($pid);
		
		if($processtime<1){
			echo "Starting......: ".date("H:i:s")." [RELOAD]: exec.logfile_daemon.php $pid running since {$processtime}Mn\n";
			continue;}
		echo "Starting......: ".date("H:i:s")." [RELOAD]: Kill exec.logfile_daemon.php $pid running since {$processtime}Mn\n";
		unix_system_kill_force($pid);
		
	}
	$sock=new sockets();
	$EnableTransparent27=intval($sock->GET_INFO("EnableTransparent27"));
	if($EnableTransparent27==1){
		system("/etc/init.d/squid-nat reload --script=".basename(__FILE__));
	}
	build_progress_reload("{done}",100);
	
}
function squid_watchdog_events($text){
	$unix=new unix();
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefile=basename($trace[1]["file"]);$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];}}
	$unix->events($text,"/var/log/squid.watchdog.log",false,$sourcefunction,$sourceline);
}

function Reload_Squid(){
	if($GLOBALS["NORELOAD"]){return;}
	$force=null;
	if($GLOBALS["FORCE"]){$force=" --force";}
	mime_conf();
	$sock=new sockets();
	system("/etc/init.d/squid reload$force --script=".basename(__FILE__));
	$EnableTransparent27=intval($sock->GET_INFO("EnableTransparent27"));
	if($EnableTransparent27==1){
		system("/etc/init.d/squid-nat reload$force --script=".basename(__FILE__));
	}
	
}

function KillSquid(){
	$unix=new unix();
	$pidof=$unix->find_program("pidof");
	$kill=$unix->find_program("kill");
	if(strlen($pidof)<4){return;}
	exec("$pidof {$GLOBALS["SQUIDBIN"]}",$results);
	$f=explode(" ",@implode("",$results));
	while (list ($num, $val) = each ($f)){
		$val=trim($val);
		if(!is_numeric($val)){continue;}
		echo "Starting......: ".date("H:i:s")." stopping pid $val\n";
		$unix->KILL_PROCESS($val,9);
		usleep(10000);
	}
	
	
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


function squidclamav(){
	$squid=new squidbee();
	$sock=new sockets();
	$unix=new unix();
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
	$users=$GLOBALS["CLASS_USERS"];
	$SquidGuardIPWeb=$sock->GET_INFO("SquidGuardIPWeb");
	if($SquidGuardIPWeb==null){$SquidGuardIPWeb="http://$users->hostname:9020/exec.squidguard.php";}
	
	
	$conf[]="squid_ip 127.0.0.1";
	$conf[]="squid_port $squid->listen_port";
	$conf[]="logfile /var/log/squid/squidclamav.log";
	$conf[]="debug 0";
	$conf[]="stat 0";
	$conf[]="clamd_local ".$unix->LOCATE_CLAMDSOCKET();
	$conf[]="#clamd_ip 192.168.1.5";
	$conf[]="#clamd_port 3310";
	$conf[]="maxsize 5000000";
	$conf[]="redirect $SquidGuardIPWeb";
	if($squid->enable_squidguard==1){
		$conf[]="squidguard $users->SQUIDGUARD_BIN_PATH";
	}else{
		if($squid->enable_UfdbGuard==1){
			$conf[]="squidguard $users->ufdbgclient_path";
		}
	}
	$conf[]="maxredir 30";
	$conf[]="timeout 60";
	$conf[]="useragent Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)";
	$conf[]="trust_cache 1";
	$conf[]="";
	$conf[]="# Do not scan standard HTTP images";
	$conf[]="abort ^.*\.(ico|gif|png|jpg)$";
	$conf[]="abortcontent ^image\/.*$";
	$conf[]="# Do not scan text and javascript files";
	$conf[]="abort ^.*\.(css|xml|xsl|js|html|jsp)$";
	$conf[]="abortcontent ^text\/.*$";
	$conf[]="abortcontent ^application\/x-javascript$";
	$conf[]="# Do not scan streaming videos";
	$conf[]="abortcontent ^video\/mp4";
	$conf[]="abortcontent ^video\/x-flv$";
	$conf[]="# Do not scan pdf and flash";
	$conf[]="#abort ^.*\.(pdf|swf)$";
	$conf[]="";
	$conf[]="# Do not scan sequence of framed Microsoft Media Server (MMS)";
	$conf[]="abortcontent ^.*application\/x-mms-framed.*$";
	$conf[]="";
	$conf[]="# White list some sites";
	$conf[]="whitelist .*\.clamav.net";	
	@file_put_contents("/etc/squidclamav.conf",@implode("\n",$conf));
	echo "Starting......: ".date("H:i:s")." Squid building squidclamav.conf configuration done\n";
}

function GetLocalCaches(){
	$unix=new unix();	
	return $unix->SQUID_CACHE_FROM_SQUIDCONF();
}



function ReconstructCaches(){
	$squid=new squidbee();
	$unix=new unix();	
	$main_cache=$squid->CACHE_PATH;
	echo "Starting......: ".date("H:i:s")."  reconstruct caches\n";
	$GetCachesInsquidConf=$unix->SQUID_CACHE_FROM_SQUIDCONF();
	while (list ($dir, $type) = each ($GetCachesInsquidConf)){
		if(is_dir($dir)){
			echo "Starting......: ".date("H:i:s")." Squid removing directory $num\n";
			shell_exec("/bin/rm -rf $dir");
		}
	}
	echo "Starting......: ".date("H:i:s")."  Building caches\n";
	BuildCaches();
	caches_infos();
}

function NudeBooster(){
	$sock=new sockets();
	$unix=new unix();	
	$umount=$unix->find_program("umount");
	$SquidNuditScanParams=unserialize(base64_decode($sock->GET_INFO("SquidNudityScanParams")));	
	if(!isset($SquidNuditScanParams["MemoryDir"])){$SquidNuditScanParams["MemoryDir"]=0;}
	$MemoryDir=$SquidNuditScanParams["MemoryDir"];
	$workdir="/var/lib/nudityScan";	
	if(!is_numeric($MemoryDir)){$MemoryDir=0;}
	echo "Starting......: ".date("H:i:s")." Squid nudity MemBoost {$MemoryDir}M\n";
	if($MemoryDir==0){
		shell_exec("$umount -l /var/lib/nudityScan >/dev/null 2>&1");
		return;
	}
	$idbin=$unix->find_program("id");
	$rm=$unix->find_program("rm");
	$mount=$unix->find_program("mount");
	exec("$idbin squid 2>&1",$results);
	if(!preg_match("#uid=([0-9]+).*?gid=([0-9]+)#", @implode("", $results),$re)){echo "Starting......: ".date("H:i:s")." Squid nudity squid no such user...\n";return;}
	
	shell_exec("$umount -l $workdir");
	$uid=$re[1];
	$gid=$re[2];	
	shell_exec("$rm -rf $workdir");
	@mkdir($workdir,0755);	
	echo "Starting......: ".date("H:i:s")." Squid nudity MemBoost squid ($uid/$gid)\n";	
	shell_exec("$mount -t tmpfs -o size={$MemoryDir}M,noauto,user,exec,uid=$uid,gid=$gid tmpfs $workdir");
	$mountedM=NudeBooster_tmpfs_mounted_size();
	if($mountedM>1){echo "Starting......: ".date("H:i:s")." Squid nudity MemBoost mounted with {$mountedM}M\n";}else{
		echo "Starting......: ".date("H:i:s")." Squid nudity mounted failed\n";
	}			
	
}

function NudeBooster_tmpfs_mounted_size(){
	$unix=new unix();
	$mount=$unix->find_program("mount");
	exec("$mount 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#^tmpfs on.*?lib\/nudityScan.*?tmpfs\s+\(.*?size=([0-9]+)M#", $ligne,$re)){return $re[1];}}
	return null;
}


function BuildCaches($NOTSTART=false){
	echo "Starting......: ".date("H:i:s")." Squid Check *** caches ***\n";
	$squid=new squidbee();
	$unix=new unix();	
	$SQUID_CONFIG_PATH=$unix->SQUID_CONFIG_PATH();
	$unix=new unix();
	$sock=new sockets();
	$su_bin=$unix->find_program("su");
	$chown=$unix->find_program("chown");
	$chmod=$unix->find_program("chmod");
	$nohup=$unix->find_program("nohup");
	$TimeFileChown="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$SquidBoosterMem=$sock->GET_INFO("SquidBoosterMem");
	if(!is_numeric($SquidBoosterMem)){$SquidBoosterMem=0;}
	$squid_user=SquidUser();
	writelogs("Using squid user: \"$squid_user\"",__FUNCTION__,__FILE__,__LINE__);
	writelogs("$chown cache directories...",__FUNCTION__,__FILE__,__LINE__);
	$unix->chown_func($squid_user,null, "/etc/squid3/*");
	if(is_dir("/usr/share/squid-langpack")){$unix->chown_func($squid_user,null, "/usr/share/squid-langpack");}
	
	$GetCachesInsquidConf=$unix->SQUID_CACHE_FROM_SQUIDCONF();
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." Squid ".count($GetCachesInsquidConf)." caches to check\n";}
	writelogs(count($GetCachesInsquidConf)." caches to check",__FUNCTION__,__FILE__,__LINE__);
	
	$MustBuild=false;
	if($SquidBoosterMem>0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." Squid Cache booster set to {$SquidBoosterMem}Mb\n";}
		@mkdir("/var/squid/cache_booster",0755,true);
		@chown("/var/squid/cache_booster", "squid");
		@chgrp("/var/squid/cache_booster", "squid");
		if(!is_dir("/var/squid/cache_booster/00")){
			echo "Starting......: ".date("H:i:s")." Squid *** /var/squid/cache_booster/00 *** No such directory ask to rebuild caches\n";
			$MustBuild=true;
		}
		
	}		
	
	$nice=$unix->EXEC_NICE();
	$rm=$unix->find_program("rm");
	if(!$GLOBALS["NOCACHES"]){
		$TimeFileChownTime=$unix->file_time_min($TimeFileChown);
		$SH[]="#!/bin/sh";
		while (list ($CacheDirectory, $type) = each ($GetCachesInsquidConf)){
			if(trim($CacheDirectory)==null){continue;}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." Squid Check *** $CacheDirectory ***\n";}
			$subdir=basename($CacheDirectory);
			$MainDir=dirname($CacheDirectory);
			
			writelogs("Directory \"$CacheDirectory\" SUBDIR=$subdir Main dir=$MainDir",__FUNCTION__,__FILE__,__LINE__);
			if(isDirInFsTab($MainDir)){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." Squid Check *** $MainDir -> Mounted ? ***\n";}
			}
			
			if(!is_dir($CacheDirectory)){
				echo "Starting......: ".date("H:i:s")." Squid Check creating cache \"$CacheDirectory\" no such directory\n";
				@mkdir($CacheDirectory,0755,true);
				$MustBuild=true;
			}
			echo "Starting......: ".date("H:i:s")." Squid Check cache \"$CacheDirectory\" owned by $squid_user (".__LINE__.")\n";
			
			build_progress("{reconfigure} Checking $CacheDirectory",86);
			$SH[]="$nice $chown -R $squid_user:$squid_user $CacheDirectory";
			@chmod($CacheDirectory, 0755);
			
			
					
		}
		
		$TMPFILE=$unix->FILE_TEMP();
		$SH[]="$rm -f $TMPFILE.sh";
		@file_put_contents("$TMPFILE.sh", @implode("\n", $SH));
		@chmod("$TMPFILE.sh",0755);
		build_progress("{reconfigure} Checking $TMPFILE.sh ok",86);
		shell_exec("$nohup $TMPFILE.sh >/dev/null 2>&1 &");
		$SH=array();
		
	}
	if($unix->file_time_min($TimeFileChown)>120){
		@unlink($TimeFileChown);
		@file_put_contents($TimeFileChown, time());
	}
	
	
	if(!$GLOBALS["NOCACHES"]){$MustBuild=false;return;}
	
	
	if(!$MustBuild){
		if($GLOBALS["OUTPUT"]){caches_infos();}
		echo "Starting......: ".date("H:i:s")." Squid all caches are OK\n";
		return;
	}
	
	
	if(preg_match("#(.+?):#",$squid_user,$re)){$squid_uid=$re[1];}else{$squid_uid="squid";}
	writelogs("Stopping squid...",__FUNCTION__,__FILE__,__LINE__);
	shell_exec("/etc/init.d/artica-postfix stop squid-cache");
	writelogs("Building caches with user: \"$squid_uid\"",__FUNCTION__,__FILE__,__LINE__);
	writelogs("$su_bin $squid_uid -c \"{$GLOBALS["SQUIDBIN"]} -z\" 2>&1",__FUNCTION__,__FILE__,__LINE__);
	exec("$su_bin $squid_uid -c \"{$GLOBALS["SQUIDBIN"]} -z\" 2>&1",$results);	
	
	while (list ($agent, $val) = each ($results) ){
			writelogs("$val",__FUNCTION__,__FILE__,__LINE__);
	}
	
	
	writelogs("Send Notifications",__FUNCTION__,__FILE__,__LINE__);
	send_email_events("Squid Cache: reconfigure caches","Here it is the results\n",@implode("\n",$results),"proxy");
	writelogs("Starting squid",__FUNCTION__,__FILE__,__LINE__);
	
	unset($results);
	if(!$NOTSTART){
		reconfigure_squid();
	}	
	
	
	
}

function kernel_values(){
	return;
}

function isDirInFsTab($directory){
	$directoryRegex=$directory;
	$directoryRegex=str_replace("/", "\/", $directoryRegex);
	$directoryRegex=str_replace(".", "\.", $directoryRegex);
	$f=explode("\n", @file_get_contents("/etc/fstab"));
	while (list ($index, $val) = each ($f) ){
		if(preg_match("#^(.+)\s+$directoryRegex#", $val,$re)){
			echo "Starting......: ".date("H:i:s")." Squid Check $directory must be mounted on {$re[1]}\n";
			return true;
		}
		
	}
}



function security_limit(){
	
	$f=file("/etc/security/limits.conf");
	$add=false;
	while (list ($index, $line) = each ($f) ){
		
		if(preg_match("#^squid.*?#", $line)){
			echo "Starting......: ".date("H:i:s")." [SYS]: Squid /etc/security/limits.conf OK\n"; 
			return;
		}
		
	}
	echo "Starting......: ".date("H:i:s")." [SYS]: Squid /etc/security/limits.conf adding 65535 for squid username\n"; 
	$f[]="squid - nofile 65535";
	@file_put_contents("/etc/security/limits.conf", @implode("\n", $f));
	
	
}

function CheckTempConfig(){
	$unix=new unix();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	
	
	if(!is_dir("/tmp")){@mkdir("/tmp",0755,true);}
	
	if(!is_file("/tmp/squid.conf")){
		
		$squid=new squidbee();
		$conf=$squid->BuildSquidConf();
		$conf=str_replace("\n\n", "\n", $conf);
		@file_put_contents("/tmp/squid.conf", $conf);
	}
	
	$cmd="$squidbin -f /tmp/squid.conf -k parse 2>&1";
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	exec($cmd,$results);
	
	while (list ($index, $ligne) = each ($results) ){
		if(preg_match("#(unrecognized|FATAL|Bungled)#", $ligne)){
			echo "DETECTD: FAILED\n";
			echo "LINE \"$ligne\"\n";
			if(preg_match("#line ([0-9]+):#", $ligne,$ri)){
				$Buggedline=$ri[1];
				$tt=explode("\n",@file_get_contents("/tmp/squid.conf"));
				for($i=$Buggedline-2;$i<$Buggedline+2;$i++){
					$lineNumber=$i+1;
					if(trim($tt[$i])==null){continue;}
					echo "[line:$lineNumber]: {$tt[$i]}\n";}
				}	
			return;		
		}
		
		if(preg_match("#ERROR: Failed#", $ligne)){
			echo "FAILED\n";
			echo "$ligne\n";
			return;
		}

	}
	
	echo "SUCCESS\n";
	
}


function ApplyConfig($smooth=false){
	if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("Invoke ApplyConfig function", basename(__FILE__));}
	$unix=new unix();
	$ulimit=$unix->find_program("ulimit");
	if(is_file($ulimit)){
		shell_exec("$ulimit -HSd unlimited");
	}else{
		echo "Starting......: ".date("H:i:s")." [SYS]: Squid ulimit no such binary...\n"; 
	}
	
	echo "Starting......: ".date("H:i:s")." [SYS]: Squid apply kernel settings\n"; 
	build_progress("{reconfigure} Kernel values",46);
	kernel_values();
	echo "Starting......: ".date("H:i:s")." [SYS]: Squid apply Checks security limits\n"; 
	build_progress("{reconfigure} Security limits",47);
	security_limit();
	echo "Starting......: ".date("H:i:s")." [SYS]: Squid Checking Remote appliances...\n";
	build_progress("{reconfigure} checks remote appliances",48);
	remote_appliance_restore_tables();
	echo "Starting......: ".date("H:i:s")." [SYS]: Squid Checking Remote appliances done...\n";
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$squidbin=$unix->find_program("squid");
	$SQUID_CONFIG_PATH=$unix->SQUID_CONFIG_PATH();
	
	echo "Starting......: ".date("H:i:s")." [SYS]: Squid loading libraires...\n";
	$sock=new sockets();
	$squid=new squidbee();
	
	
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
	echo "Starting......: ".date("H:i:s")." [SYS]: Squid binary: `$squidbin`\n";
	echo "Starting......: ".date("H:i:s")." [SYS]: Squid Conf..: `$SQUID_CONFIG_PATH`\n";
	echo "Starting......: ".date("H:i:s")." [SYS]: Squid php...: `$php5`\n";
	echo "Starting......: ".date("H:i:s")." [SYS]: Squid nohup.: `$nohup`\n";
	
	
	$DenySquidWriteConf=$sock->GET_INFO("DenySquidWriteConf");
	if(!is_numeric($DenySquidWriteConf)){$DenySquidWriteConf=0;}

	echo "Starting......: ".date("H:i:s")." [SYS]: Squid Checking `DenySquidWriteConf` = $DenySquidWriteConf\n";
	
	@copy("/etc/artica-postfix/settings/Daemons/SquidNudityScanParams","/etc/squid3/SquidNudityScanParams");

	echo "Starting......: ".date("H:i:s")." [SYS]: Squid Checking `NudeBooster`\n";
	build_progress("{reconfigure} Nude booster",49);
	NudeBooster();
	if(!is_dir("/usr/share/squid-langpack")){
		echo "Starting......: ".date("H:i:s")." [SYS]: Squid Checking Templates from MySQL\n";
		$unix->THREAD_COMMAND_SET("$php5 ".__FILE__." --tpl-save");
	}
	
	$EnableRemoteStatisticsAppliance=0;
	echo "Starting......: ".date("H:i:s")." [SYS]: Squid Build blocked Websites list...\n";
	
	
	if(!is_dir("/etc/squid3/artica-meta")){@mkdir("/etc/squid3/artica-meta",0755,true);}
	if(!is_file("/etc/squid3/artica-meta/whitelist-net.db")){@touch("/etc/squid3/artica-meta/whitelist-net.db");}
	if(!is_file("/etc/squid3/artica-meta/whitelist-domains.db")){@touch("/etc/squid3/artica-meta/whitelist-domains.db");}
	
	
	build_progress("{reconfigure} Whitelisted browsers",50);
	acl_whitelisted_browsers();
	build_progress("{reconfigure} allowed browsers",51);
	acl_allowed_browsers();
	echo "Starting......: ".date("H:i:s")." Checking wrapzap\n";
	build_progress("{reconfigure} wrapzap",52);
	wrapzap();
	build_progress("{reconfigure} Mime.conf",53);
	mime_conf();
	build_progress("{reconfigure} Blocked websites",54);
	$squid->BuildBlockedSites();
	build_progress("{reconfigure} FTP clients ACLs",55);
	acl_clients_ftp();
	build_progress("{checking_wccp_mode}",55);
	system("$php5 /usr/share/artica-postfix/exec.squid.wccp.php --squid");

	build_progress("{reconfigure} Dynamic rules caches",56);
	echo "Starting......: ".date("H:i:s")." [SYS]: Dynamic rules caches...\n";
	dyn_caches();
	build_progress("{reconfigure} Webfiltering whitelisted",57);
	echo "Starting......: ".date("H:i:s")." [SYS]: Squid Build url_rewrite_access deny...\n";
	urlrewriteaccessdeny();
	echo "Starting......: ".date("H:i:s")." [SYS]:Squid building main configuration done\n";
	build_progress("{reconfigure} Check files and security",58);
	CheckFilesAndSecurity();
	$tar=$unix->find_program("tar");
	if($GLOBALS["NOAPPLY"]){$DenySquidWriteConf=0;}
	
	
	if($DenySquidWriteConf==0){
			@mkdir("/tmp",0755,true);
			$squid->CURRENT_PROGRESS=79;
			$squid->MAX_PROGRESS=79;
			$conf=$squid->BuildSquidConf();
			$conf=str_replace("\n\n", "\n", $conf);
			build_progress("{writing_configuration}",79);
			@file_put_contents("/tmp/squid.conf", $conf);
			echo "Starting......: ".date("H:i:s")." [SYS]: Squid Check validity of the configuration file with /tmp/squid.conf...\n";
			$GLOBALS["SQUID_PATTERN_ERROR"]=array();
		
			$squid_checks=new squid_checks("/tmp/squid.conf");
			if(!$squid_checks->squid_parse()){
				build_progress("{checking}: {failed}",110);
				return;
			}
			
	
			
			
			echo "Starting......: ".date("H:i:s")." [SYS]: Squid Check validity OK...\n";
			if($GLOBALS["NOAPPLY"]){
				echo "Starting......: ".date("H:i:s")." [SYS]: WARNING \"NOAPPLY\" Artica is denied to apply settings...\n";
				return true;
			}
			echo "Starting......: ".date("H:i:s")." [SYS]: Squid Writing configuration file \"$SQUID_CONFIG_PATH\" ". strlen($conf)." bytes...\n";
			@file_put_contents($SQUID_CONFIG_PATH,$conf);
			@mkdir("/etc/squid3",0755,true);
			if($SQUID_CONFIG_PATH<>"/etc/squid3/squid.conf"){@file_put_contents("/etc/squid3/squid.conf",$conf);}
			$sock->TOP_NOTIFY("{squid_parameters_was_saved}","info");
			$cmd=$unix->LOCATE_PHP5_BIN()." ".__FILE__." --templates --noreload";
			$unix->THREAD_COMMAND_SET($cmd);			
	}
	
	build_progress("{checking}: squidclamav",79);
	if(!$smooth){squidclamav();}
	build_progress("{checking}: wrapzap",79);
	if(!$smooth){wrapzap();}
	build_progress("{checking}: Cerificates",79);
	if(!$smooth){certificate_generate();}
	$cmd=$nohup." ". $unix->LOCATE_PHP5_BIN()." ".__FILE__." --cache-infos --force >/dev/null 2>&1 &";
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	
	shell_exec($cmd);
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.syslog-engine.php --rsylogd >/dev/null 2>&1 &");
	
	if(!$smooth){
		build_progress("{checking}: Check Files And Security",79);
		CheckFilesAndSecurity();
	}
		
	if(is_file("/root/squid-good.tgz")){@unlink("/root/squid-good.tgz");}
	chdir("/etc/squid3");
	shell_exec("cd /etc/squid3");
	shell_exec("tar -czf /root/squid-good.tgz *");
	chdir("/root");
	shell_exec("cd /root");
	
	return true;
	
}



function acl_clients_ftp(){
	$q=new mysql();
	$sql="SELECT * FROM squid_white WHERE task_type='FTP_RESTR' ORDER BY ID DESC";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){return;}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(!preg_match("#FTP_RESTR:(.+)#",$ligne["uri"],$re)){continue;}	
		$f[]=$re[1];
	}
	@file_put_contents("/etc/squid3/clients_ftp.acl",@implode("\n",$f));
	
}

function acl_allowed_browsers(){
	$sql="SELECT uri FROM squid_white WHERE task_type='USER_AGENT_BAN_WHITE' ORDER BY ID DESC";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");	
	if(!$q->ok){
		writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		return;
	}	
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$string=trim($ligne["uri"]);
		if($string==null){continue;}
		$string=str_replace(".","\.",$string);
		$string=str_replace("(","\(",$string);
		$string=str_replace(")","\)",$string);
		$string=str_replace("/","\/",$string);
		$f[]=$string;
	}	
	@file_put_contents("/etc/squid3/allowed-user-agents.acl",@implode("\n",$f));
}

function acl_whitelisted_browsers(){
	$sql="SELECT uri FROM squid_white WHERE task_type='AUTH_WL_USERAGENTS'";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$arrayUserAgents[$ligne["uri"]]=1;
	}
	
	if(!isset($arrayUserAgents)){
		echo "Starting......: ".date("H:i:s")." Whitelisted User-Agents: 0\n";
		@file_put_contents("/etc/squid3/white-listed-user-agents.acl","");
		return;
	}
		
	if(!is_array($arrayUserAgents)){
		echo "Starting......: ".date("H:i:s")." Whitelisted User-Agents: 0\n";
		@file_put_contents("/etc/squid3/white-listed-user-agents.acl","");
		return;
	}
		

	while (list ($agent, $val) = each ($arrayUserAgents) ){
		$sql="SELECT unique_key,`string` FROM `UserAgents` WHERE browser='$agent' ORDER BY string";
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$string=trim($ligne["string"]);
			if($string==null){continue;}
			$string=str_replace(".","\.",$string);
			$string=str_replace("(","\(",$string);
			$string=str_replace(")","\)",$string);
			$string=str_replace("/","\/",$string);
			$f[]=$string;
		}
	}
	echo "Starting......: ".date("H:i:s")." Whitelisted User-Agents: ". count($arrayUserAgents)." (". count($f)." patterns)\n";		
	@file_put_contents("/etc/squid3/white-listed-user-agents.acl",@implode("\n",$f));		
		
	
}


function retrans(){
	$unix=new unix();
	$array=$unix->getDirectories("/tmp");
	while (list ($num, $ligne) = each ($array) ){
		if(preg_match("#(.+?)\/temporaryFolder\/bases\/av#",$ligne,$re)){
			$folder=$re[1];
		}
	}
	if(is_dir($folder)){
		$cmd=$unix->find_program("du")." -h -s $folder 2>&1";
		exec($cmd,$results);
		$text=trim(implode(" ",$results));
		if(preg_match("#^([0-9\.\,A-Z]+)#",$text,$re)){
			$dbsize=$re[1];
		}
	}else{
		$dbsize="0M";
	}
	
	echo $dbsize;
}


function certificate_conf(){
	include_once('ressources/class.ssl.certificate.inc');
	$ssl=new ssl_certificate();
	$array=$ssl->array_ssl;
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
	$users=$GLOBALS["CLASS_USERS"];
	$sock=new sockets();	
	$cc=$array["artica"]["country"]."_".$array["default_ca"]["countryName_value"];
	

	
	
		$country_code="US";
		$contryname="Delaware";
		$locality="Wilmington";
		$organizationalUnitName="Artica Web Proxy Unit";
		$organizationName="Artica";
		$emailAddress="root@$users->hostname";
		$commonName=$users->hostname;
		
		
		
		if(preg_match("#(.+?)_(.+?)$#",$cc,$re)){
			$contryname=$re[1];
			$country_code=$re[2];
		}
		if($array["server_policy"]["localityName"]<>null){$locality=$array["server_policy"]["localityName"];}
		if($array["server_policy"]["organizationalUnitName"]<>null){$organizationalUnitName=$array["server_policy"]["organizationalUnitName"];}
		if($array["server_policy"]["emailAddress"]<>null){$emailAddress=$array["server_policy"]["emailAddress"];}
		if($array["server_policy"]["organizationName"]<>null){$organizationName=$array["server_policy"]["organizationName"];}
		if($array["server_policy"]["commonName"]<>null){$commonName=$array["server_policy"]["commonName"];}
	
		@mkdir("/etc/squid3/ssl/new",0666,true);
		
		$conf[]="[ca]";
		$conf[]="default_ca=default_db";
		$conf[]="unique_subject=no";
		$conf[]="";
		$conf[]="[default_db]";
		$conf[]="dir=.";
		$conf[]="certs=.";
		$conf[]="new_certs_dir=/etc/squid3/ssl/new";
		$conf[]="database= /etc/squid3/ssl/ca.index";
		$conf[]="serial = /etc/squid3/ssl/ca.serial";
		$conf[]="RANDFILE=.rnd";
		$conf[]="certificate=/etc/squid3/ssl/key.pem";
		$conf[]="private_key=/etc/squid3/ssl/ca.key";
		$conf[]="default_days= 730";
		$conf[]="default_crl_days=30";
		$conf[]="default_md=md5";
		$conf[]="preserve=no";
		$conf[]="name_opt=ca_default";
		$conf[]="cert_opt=ca_default";
		$conf[]="unique_subject=no";
		$conf[]="policy=policy_match";
		$conf[]="";
		$conf[]="[server_policy]";
		$conf[]="countryName=supplied";
		$conf[]="stateOrProvinceName=supplied";
		$conf[]="localityName=supplied";
		$conf[]="organizationName=supplied";
		$conf[]="organizationalUnitName=supplied";
		$conf[]="commonName=supplied";
		$conf[]="emailAddress=supplied";
		$conf[]="";
		$conf[]="[server_cert]";
		$conf[]="subjectKeyIdentifier=hash";
		$conf[]="authorityKeyIdentifier=keyid:always";
		$conf[]="extendedKeyUsage=serverAuth,clientAuth,msSGC,nsSGC";
		$conf[]="basicConstraints= critical,CA:false";
		$conf[]="";
		$conf[]="[user_policy]";
		$conf[]="commonName=supplied";
		$conf[]="emailAddress=supplied";
		$conf[]="";
		$conf[]="[user_cert]";
		$conf[]="subjectAltName=email:copy";
		$conf[]="basicConstraints= critical,CA:false";
		$conf[]="authorityKeyIdentifier=keyid:always";
		$conf[]="extendedKeyUsage=clientAuth,emailProtection";
		$conf[]="";
		$conf[]="[req]";
		$conf[]="default_bits=1024";
		$conf[]="default_keyfile=ca.key";
		$conf[]="distinguished_name=default_ca";
		$conf[]="x509_extensions=extensions";
		$conf[]="string_mask=nombstr";
		$conf[]="req_extensions=req_extensions";
		$conf[]="input_password=secret";
		$conf[]="output_password=secret";
		$conf[]="";
		$conf[]="[default_ca]";
		$conf[]="countryName=Country Code";
		$conf[]="countryName_value=$country_code";
		$conf[]="countryName_min=2";
		$conf[]="countryName_max=2";
		$conf[]="stateOrProvinceName=State Name";
		$conf[]="stateOrProvinceName_value=$contryname";
		$conf[]="localityName=Locality Name";
		$conf[]="localityName_value=$locality";
		$conf[]="organizationName=Organization Name";
		$conf[]="organizationName_value=$organizationName";
		$conf[]="organizationalUnitName=Organizational Unit Name";
		$conf[]="organizationalUnitName_value=$organizationalUnitName";
		$conf[]="commonName=Common Name";
		$conf[]="commonName_value=$commonName";
		$conf[]="commonName_max=64";
		$conf[]="emailAddress=Email Address";
		$conf[]="emailAddress_value=$emailAddress";
		$conf[]="emailAddress_max=".strlen($emailAddress);
		$conf[]="unique_subject=no";
		$conf[]="";
		$conf[]="[extensions]";
		$conf[]="subjectKeyIdentifier=hash";
		$conf[]="authorityKeyIdentifier=keyid:always";
		$conf[]="basicConstraints=critical,CA:false";
		$conf[]="";
		$conf[]="[req_extensions]";
		$conf[]="nsCertType=objsign,email,server";
		$conf[]="";
		$conf[]="[CA_default]";
		$conf[]="policy=policy_match";
		$conf[]="";
		$conf[]="[policy_match]";
		$conf[]="countryName=match";
		$conf[]="stateOrProvinceName=match";
		$conf[]="organizationName=match";
		$conf[]="organizationalUnitName=optional";
		$conf[]="commonName=match";
		$conf[]="emailAddress=optional";
		$conf[]="";
		$conf[]="[policy_anything]";
		$conf[]="countryName=optional";
		$conf[]="stateOrProvinceName=optional";
		$conf[]="localityName=optional";
		$conf[]="organizationName=optional";
		$conf[]="organizationalUnitName=optional";
		$conf[]="commonName=optional";
		$conf[]="emailAddress=optional";
		$conf[]="";
		$conf[]="[v3_ca]";
		$conf[]="subjectKeyIdentifier=hash";
		$conf[]="authorityKeyIdentifier=keyid:always,issuer:always";
		$conf[]="basicConstraints=critical,CA:false";
		@mkdir("/etc/squid3/ssl",0666,true);
		file_put_contents("/etc/squid3/ssl/openssl.conf",@implode("\n",$conf));		
	}

function certificate_generate(){
		$ssl_path="/etc/squid3/ssl";
		
		if(is_certificate()){
			echo "Starting......: ".date("H:i:s")." Squid SSL certificate OK\n";
			return;
		}
		
		
		@unlink("$ssl_path/privkey.cp.pem");
		@unlink("$ssl_path/cacert.pem");
		@unlink("$ssl_path/privkey.pem");
		
		
		 echo "Starting......: ".date("H:i:s")." Squid building SSL certificate\n";
		 certificate_conf();
		 $ldap=new clladp();
		 $sock=new sockets();
		 $unix=new unix();
		$CertificateMaxDays=$sock->GET_INFO('CertificateMaxDays');
		if($CertificateMaxDays==null){$CertificateMaxDays='730';}
		 echo "Starting......: ".date("H:i:s")." Squid Max Days are $CertificateMaxDays\n";		 
		 $password=$unix->shellEscapeChars($ldap->ldap_password);
		 
		 $openssl=$unix->find_program("openssl");
		 $config="/etc/squid3/ssl/openssl.conf";
		 
		 
		 system("$openssl genrsa -des3 -passout pass:$password -out $ssl_path/privkey.pem 2048 1024");
		 system("$openssl req -new -x509 -nodes -passin pass:$password -key $ssl_path/privkey.pem -batch -config $config -out $ssl_path/cacert.pem -days $CertificateMaxDays");
		 system("/bin/cp $ssl_path/privkey.pem $ssl_path/privkey.cp.pem");
		 system("$openssl rsa -passin pass:$password -in $ssl_path/privkey.cp.pem -out $ssl_path/privkey.pem"); 
		 
	     
	}
	
function is_certificate(){
	$ssl_path="/etc/squid3/ssl";;
	if(!is_file("$ssl_path/cacert.pem")){return false;}
	if(!is_file("$ssl_path/privkey.pem")){return false;}
	if(!is_file("$ssl_path/privkey.cp.pem")){return false;}
	return true;
	
}

function wrapzap_compile(){
	$sql="SELECT * FROM squid_adzapper WHERE enabled=1";
	$q=new mysql();
	$f=array();
	$tpl=new templates();
	$unix=new unix();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){writelogs($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);return;}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$f[]="{$ligne["uri_type"]} {$ligne["uri"]}";
	}
	
	echo "Starting......: ".date("H:i:s")." adZapper ". count($f)." rows\n"; 
	@file_put_contents("/etc/squid3/zapper.post-database.txt",@implode("\n",$f));
	$squiduser=SquidUser();
	$unix->chown_func($squiduser,null, "/etc/squid3/zapper.pre-database.txt");
	$unix->chown_func($squiduser,null, "/etc/squid3/zapper.post-database.txt");


	if($GLOBALS["RELOAD"]){
		$unix=new unix();
		squid_watchdog_events("Reconfiguring Proxy parameters...");
		if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$file=basename($trace[1]["file"]);$function=$trace[1]["function"];$line=$trace[1]["line"];$called="Called by $function() from line $line";}}
		$cmd="/etc/init.d/squid reload --script=".basename(__FILE__)." >/dev/null";
		shell_exec($cmd);
	}
}


function wrapzap(){
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
	$users=$GLOBALS["CLASS_USERS"];
	$sock=new sockets();
	$SquidGuardIPWeb=$sock->GET_INFO("SquidGuardIPWeb");
	if($SquidGuardIPWeb==null){$SquidGuardIPWeb="http://$users->hostname:9020/zaps";}
	$SquidGuardIPWeb=str_replace('.(none)',"",$SquidGuardIPWeb);
	
	if(preg_match("#http:\/\/(.+?)\/#",$SquidGuardIPWeb,$re)){
		$SquidGuardIPWeb="http://{$re[1]}/zaps";
	}
	
	if(!is_file("/etc/squid3/zapper.pre-database.txt")){@file_put_contents("/etc/squid3/zapper.pre-database.txt","#");}
	if(!is_file("/etc/squid3/zapper.post-database.txt")){@file_put_contents("/etc/squid3/zapper.post-database.txt","#");}
	
	wrapzap_compile();
	
	
	echo "Starting......: ".date("H:i:s")." adZapper redirector to \"$SquidGuardIPWeb\"\n"; 
	
$f[]="#!/bin/sh";
$f[]="#";
$f[]="# Wrapper to set environment variables then exec the real zapper.";
$f[]="# The reasons for this are twofold:";
$f[]="#	- for some reason squid doesn't preserve the original environment";
$f[]="#	  when you do a restart (or SIGHUP)";
$f[]="#	- to avoid having to hack the squid startup script (if you have";
$f[]="#	  a presupplied one, such as ships with some linux distributions)";
$f[]="#";
$f[]="# Install in the same directory you put the zapper (just for convenience) and";
$f[]="# hack the pathnames below to suit.";
$f[]="# Note that you can skip this script and run the zapper with no environment";
$f[]="# settings at all and it will work fine; the variables are all set here merely";
$f[]="# for completeness so that customisation is easy for you.";
$f[]="#	- Cameron Simpson <cs@zip.com.au> 21apr2000";
$f[]="#";
$f[]="";
$f[]="# modify this to match your install";
$f[]="zapper=/usr/bin/squid_redirect";
$f[]="";
$f[]="ZAP_MODE=				# or \"CLEAR\"";
$f[]="ZAP_BASE=$SquidGuardIPWeb	# a local web server will be better";
$f[]="ZAP_BASE_SSL=https://adzapper.sourceforge.net/zaps # this can probably be ignored";
$f[]="";
$f[]="ZAP_PREMATCH=/etc/squid3/zapper.pre-database.txt";
$f[]="ZAP_POSTMATCH=/etc/squid3/zapper.post-database.txt";
$f[]="ZAP_MATCH=				# pathname of extra pattern file";
$f[]="					# for patterns to use instead of the";
$f[]="					# inbuilt pattern list";
$f[]="ZAP_NO_CHANGE=				# set to \"NULL\" is your proxy is Apache2 instead of Squid";
$f[]="";
$f[]="STUBURL_AD=\$ZAP_BASE/ad.gif";
$f[]="STUBURL_ADSSL=\$ZAP_BASE_SSL/ad.gif";
$f[]="STUBURL_ADBG=\$ZAP_BASE/adbg.gif";
$f[]="STUBURL_ADJS=\$ZAP_BASE/no-op.js";
$f[]="STUBURL_ADJSTEXT=";
$f[]="STUBURL_ADHTML=\$ZAP_BASE/no-op.html";
$f[]="STUBURL_ADHTMLTEXT=";
$f[]="STUBURL_ADMP3=\$ZAP_BASE/ad.mp3";
$f[]="STUBURL_ADPOPUP=\$ZAP_BASE/closepopup.html";
$f[]="STUBURL_ADSWF=\$ZAP_BASE/ad.swf";
$f[]="STUBURL_COUNTER=\$ZAP_BASE/counter.gif";
$f[]="STUBURL_COUNTERJS=\$ZAP_BASE/no-op-counter.js";
$f[]="STUBURL_COUNTERHTML=\$ZAP_BASE/no-op-counter.html";
$f[]="STUBURL_WEBBUG=\$ZAP_BASE/webbug.gif";
$f[]="STUBURL_WEBBUGJS=\$ZAP_BASE/webbug.js";
$f[]="STUBURL_WEBBUGHTML=\$ZAP_BASE/webbug.html";
$f[]="";
$f[]="STUBURL_PRINT=				# off by default, set to 1";
$f[]="";
$f[]="export ZAP_MODE ZAP_BASE ZAP_BASE_SSL ZAP_PREMATCH ZAP_POSTMATCH ZAP_MATCH ZAP_NO_CHANGE";
$f[]="export STUBURL_AD STUBURL_ADSSL STUBURL_ADJS STUBURL_ADHTML STUBURL_ADMP3 \ ";
$f[]="	STUBURL_ADPOPUP STUBURL_ADSWF STUBURL_COUNTER STUBURL_COUNTERJS \ ";
$f[]="	STUBURL_COUNTERHTML STUBURL_WEBBUG STUBURL_WEBBUGJS STUBURL_WEBBUGHTML \ ";
$f[]="	STUBURL_PRINT STUBURL_ADHTMLTEXT STUBURL_ADJSTEXT";
$f[]="";
$f[]="# Here, having arranged the environment, we exec the real zapper.";
$f[]="# If you're chaining redirectors then comment out the direct exec below and";
$f[]="# uncomment (and adjust) the exec of zapchain which takes care of running";
$f[]="# multiple redirections.";
$f[]="";
$f[]="exec \"\$zapper\"";
$f[]="# exec /path/to/zapchain \"\$zapper\" /path/to/another/eg/squirm";	
@file_put_contents("/usr/bin/wrapzap",@implode("\n",$f));
@chmod("/usr/bin/wrapzap",0755);
echo "Starting......: ".date("H:i:s")." adZapper wrapzap done...\n"; 

}


function SquidUser(){
	$unix=new unix();
	$squidconf=$unix->SQUID_CONFIG_PATH();
	$group=null;
	if(!is_file($squidconf)){
		echo "Starting......: ".date("H:i:s")." squidGuard unable to get squid configuration file\n";
		return "squid:squid";
	}
	
	writelogs("Open $squidconf");
	$array=explode("\n",@file_get_contents($squidconf));
	while (list ($index, $line) = each ($array)){
		if(preg_match("#cache_effective_user\s+(.+)#",$line,$re)){
			$user=trim($re[1]);
			$user=trim($re[1]);
		}
		if(preg_match("#cache_effective_group\s+(.+)#",$line,$re)){
			$group=trim($re[1]);
		}
	}
	if($group==null){$group="squid";}
	return "$user:$group";
}






function compilation_params(){
	
	
	
	@mkdir("/etc/artica-postfix/pids",0755,true);
	if(!is_file($GLOBALS["SQUIDBIN"])){return;}
	$EXEC_PID_FILE="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".build.pid";
	$EXEC_PID_TIME="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".build.time";
	
	$unix=new unix();
	$kill=$unix->find_program("kill");
	$pid=@file_get_contents($EXEC_PID_FILE);
	if($unix->process_exists($pid,basename(__FILE__))){die();}
	$cachefile="/usr/share/artica-postfix/ressources/logs/squid.compilation.params";
	
	$timefile=$unix->file_time_min($EXEC_PID_TIME);
	if($timefile<5){return;}
	@unlink($EXEC_PID_TIME);
	@file_put_contents($EXEC_PID_TIME, time());
	
	if(is_file($cachefile)){
		$timefile=$unix->file_time_min($cachefile);
		if($timefile<30){return;}
	}
	
	
	exec($GLOBALS["SQUIDBIN"]." -v",$results);
	$text=@implode("\n", $results);
	if(preg_match("#configure options:\s+(.+)#is", $text,$re)){$text=$re[1];}
	if(preg_match_all("#'(.+?)'#is", $text, $re)){
		while (list ($index, $line) = each ($re[1])){
			if(preg_match("#(.+?)=(.+)#", $line,$ri)){
				$key=$ri[1];
				$value=$ri[2];
				$key=str_replace("--", "", $key);
				$array[$key]=$value;
				continue;
			}
			$key=$line;
			$value=1;
			$key=str_replace("--", "", $key);
			$array[$key]=$value;
					
			
		}
		@unlink("/usr/share/artica-postfix/ressources/logs/squid.compilation.params");
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/squid.compilation.params", base64_encode(serialize($array)));
		shell_exec("/bin/chmod 755 /usr/share/artica-postfix/ressources/logs/squid.compilation.params");
	}
}

function errors_details_txt(){
return;
shell_exec("/bin/chown -R squid:squid /usr/share/squid3");
	
}

function TemplatesInMysql_remote(){
	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
	$users=new usersMenus();
	$sock=new sockets();
	$unix=new unix();
	$base="/usr/share/squid-langpack";
	@mkdir($base,0755,true);
	
	
	$sock=new sockets();
	$SquidTemplateSimple=$sock->GET_INFO("SquidTemplateSimple");
	if(!is_numeric($SquidTemplateSimple)){$SquidTemplateSimple=1;}
	if($SquidTemplateSimple==1){
		$unix=new unix();
		$php=$unix->LOCATE_PHP5_BIN();
		$nohup=$unix->find_program("nohup");
		$by="--FUNC-".__FUNCTION__."-L-".__LINE__;
		shell_exec("$nohup $php /usr/share/artica-postfix/exec.squid.templates.php $by >/dev/null 2>&1 &");
		return;
	}
	
	
	$RemoteStatisticsApplianceSettings=unserialize(base64_decode($sock->GET_INFO("RemoteStatisticsApplianceSettings")));
	if(!is_numeric($RemoteStatisticsApplianceSettings["SSL"])){$RemoteStatisticsApplianceSettings["SSL"]=1;}
	if(!is_numeric($RemoteStatisticsApplianceSettings["PORT"])){$RemoteStatisticsApplianceSettings["PORT"]=9000;}
	$GLOBALS["REMOTE_SSERVER"]=$RemoteStatisticsApplianceSettings["SERVER"];
	$GLOBALS["REMOTE_SPORT"]=$RemoteStatisticsApplianceSettings["PORT"];
	$GLOBALS["REMOTE_SSL"]=$RemoteStatisticsApplianceSettings["SSL"];
	if($GLOBALS["REMOTE_SSL"]==1){$refix="https";}else{$refix="http";}
	$uri="$refix://{$GLOBALS["REMOTE_SSERVER"]}:{$GLOBALS["REMOTE_SPORT"]}/ressources/databases/squid-lang-pack.tgz";
	$curl=new ccurl($uri,true);
	if(!$curl->GetFile("/tmp/squid-lang-pack.tgz")){
			squid_admin_mysql(1,"Failed to download $uri `$curl->error`",__FILE__,__LINE__);
			EventsWatchdog("$uri `$curl->error`");
			return;
	}	
	$chown=$unix->find_program("chown");
	$tar=$unix->find_program("tar");
	shell_exec("$tar -xf /tmp/squid-lang-pack.tgz -C $base/");
	shell_exec("$chown -R squid:squid $base");
	
	EventsWatchdog("Writing /etc/artica-postfix/SQUID_TEMPLATE_DONE");
	@file_put_contents("/etc/artica-postfix/SQUID_TEMPLATE_DONE", time());
	squid_admin_mysql(1, "Reloading proxy service in order to refresh templates", null,__FILE__,__LINE__);
	Reload_Squid();
}



function TemplatesUniqueInMysql($zmd5){
	$sock=new sockets();
	$unix=new unix();
	$q=new mysql_squid_builder();
	$users=new usersMenus();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
	if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}
	if($UnlockWebStats==1){$EnableRemoteStatisticsAppliance=0;}	
	if($EnableRemoteStatisticsAppliance==1){if($GLOBALS["VERBOSE"]){echo "Use the Web statistics appliance to get template files...\n";}TemplatesInMysql_remote();return;}	
	
	$base="/usr/share/squid-langpack/templates";
	@mkdir("/usr/share/squid-langpack/templates",0755,true);
	@mkdir("/usr/share/squid3/icons",0755,true);
	
	@mkdir($base,0755,true);
	if(!is_dir("$base/templates")){@mkdir("$base/templates",0755,true);}
	$sql="SELECT * FROM squidtpls WHERE `zmd5`='{$zmd5}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo $q->mysql_error."\n";return;}
	
	if($ligne["template_link"]==1){return;}
	$ligne["template_header"]=stripslashes($ligne["template_header"]);
	$ligne["template_title"]=stripslashes($ligne["template_title"]);
	$ligne["template_body"]=stripslashes($ligne["template_body"]);	
	
	
	$header=trim($ligne["template_header"]);
	if($ligne["template_name"]==null){return;}
	
	if(!$users->CORP_LICENSE){
		$header=null;
		$ligne["template_header"]=null;
		$ligne["template_body"]=null;
	}
	

	if(is_numeric($ligne["lang"])){$ligne["lang"]="en";}
	
	if($header==null){$header=@file_get_contents(dirname(__FILE__)."/ressources/databases/squid.default.header.db");}
	if(!preg_match("#ERR_.+#", $ligne["template_name"])){$ligne["template_name"]="ERR_".$ligne["template_name"];}
	
	$filename="$base/{$ligne["lang"]}/{$ligne["template_name"]}";
	$newheader=str_replace("{TITLE}", $ligne["template_title"], $header);
	$templateDatas="$newheader{$ligne["template_body"]}</body></html>";
	
	if($ligne["emptytpl"]==1){
		$templateDatas="<html><head></head><body></body></html>";
	}	
	
	@mkdir(dirname($filename),0755,true);
	@file_put_contents($filename, $templateDatas);
	
	
	
	if($GLOBALS["VERBOSE"]){echo "Writing $base/{$ligne["lang"]}/{$ligne["template_name"]}\n";}
	@file_put_contents("$base/{$ligne["lang"]}/{$ligne["template_name"]}", $templateDatas);
	$unix->chown_func("squid","squid","$base/{$ligne["lang"]}/{$ligne["template_name"]}");
	$unix->chown_func("squid:squid",null, "$base/{$ligne["lang"]}/{$ligne["template_name"]}");
	$unix->chown_func("squid:squid",null, dirname($filename)."/*");
	if($ligne["lang"]=="en"){
		if($GLOBALS["VERBOSE"]){echo "Writing $base/{$ligne["template_name"]}\n";}
		@file_put_contents("$base/{$ligne["template_name"]}", $templateDatas);
		$unix->chown_func("squid:squid", null,"$base/{$ligne["template_name"]}");
	}
		
}

function dump_templates(){
	$defaultdb=dirname(__FILE__)."/ressources/databases/squid.default.templates.db";
	$array=unserialize(@file_get_contents($defaultdb));
	print_r($array);
}


function DefaultTemplatesInArtica(){
	if(is_file("/usr/bin/cgclassify")){if(is_dir("/cgroups/blkio/php")){shell_exec("/usr/bin/cgclassify -g cpu,cpuset,blkio:php ".getmypid());}}
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
	}
	$sock=new sockets();
	$SquidTemplateSimple=$sock->GET_INFO("SquidTemplateSimple");
	if(!is_numeric($SquidTemplateSimple)){$SquidTemplateSimple=1;}
	if($SquidTemplateSimple==1){
		$unix=new unix();
		$php=$unix->LOCATE_PHP5_BIN();
		$nohup=$unix->find_program("nohup");
		$by="--FUNC-".__FUNCTION__."-L-".__LINE__;
		squid_admin_mysql(2, "Ask to build simple templates [$called]", $GLOBALS["ARGVS"],__FILE__,__LINE__);
		shell_exec("$nohup $php /usr/share/artica-postfix/exec.squid.templates.php $by >/dev/null 2>&1 &");
		return;
	}
	
	
	$ASMYSQL=false;
	$headerTemp=@file_get_contents(dirname(__FILE__)."/ressources/databases/squid.default.header.db");
	$defaultdb=dirname(__FILE__)."/ressources/databases/squid.default.templates.db";
	$array=unserialize(@file_get_contents($defaultdb));
	
	$basename="/usr/share/squid-langpack/templates";
	@mkdir("/usr/share/squid3/icons/silk",0755);
	@chown("/usr/share/squid3/icons/silk", "squid");
	@chgrp("/usr/share/squid3/icons/silk", "squid");
	@unlink("/usr/share/squid3/icons/silk/bigshield-256.png");
	@unlink("/usr/share/squid3/icons/silk/logo-artica-64.png");
	@mkdir("/usr/share/squid-langpack/templates",0755,true);
	
	@copy("/usr/share/artica-postfix/img/bigshield-256.png","/usr/share/squid3/icons/silk/bigshield-256.png");
	@copy("/usr/share/artica-postfix/img/logo-artica-64.png","/usr/share/squid3/icons/silk/logo-artica-64.png");
	@chown("/usr/share/squid3/icons/silk/bigshield-256.png", "squid");
	@chgrp("/usr/share/squid3/icons/silk/bigshield-256.png", "squid");
	@chown("/usr/share/squid3/icons/silk/logo-artica-64.png", "squid");
	@chgrp("/usr/share/squid3/icons/silk/logo-artica-64.png", "squid");
	
	$artica_version=@file_get_contents("/usr/share/artica-postfix/VERSION");
	if(!is_file("/etc/artica-postfix/SQUID_TEMPLATE_DONEv3")){
		$ASMYSQL=true;
		@file_put_contents("/etc/artica-postfix/SQUID_TEMPLATE_DONEv3", time());
		$q=new mysql_squid_builder();
	}
	
	$prefix="INSERT IGNORE INTO squidtpls (`zmd5`,`lang`,`template_name`,`template_body`,`template_title`,`emptytpl`) VALUES ";
	
	while (list ($language, $arrayTPL) = each ($array)){
		$directory="$basename/$language";
		@mkdir($directory,0755,true);
		@chown($directory, "squid");
		@chgrp($directory, "squid");
		$q=new mysql_squid_builder();
		while (list ($templateName, $templateData) = each ($arrayTPL)){
			$title=$templateData["TITLE"];
			$md5=md5($language.$templateName);
			if($title==null){echo "$templateName -> null title\n";}
			$body=$templateData["BODY"];
			$filepath="$directory/$templateName";
			$content=TemplatesDesign($title,$body);
			if($templateName=="ERR_DIR_LISTING"){
				$content=TemplatesFTP($title,$body);
			}
			
			$body=mysql_escape_string2($content);
			$title=mysql_escape_string2($title);
			
				
			$ss="('$md5','$language','$templateName','$body','$title',0)";
			if($ASMYSQL){
				$q->QUERY_SQL("DELETE FROM squidtpls WHERE `zmd5`='$md5'");
				$q->QUERY_SQL($prefix.$ss);
			}
			@file_put_contents($filepath, $content);
			@chown($filepath, "squid");
			@chgrp($filepath, "squid");
			
			
		}
	}	
	mime_conf();
}


function TemplatesFTP($title,$content){
	$title=utf8_decode($title);
	$content=utf8_decode($content);
	if(!isset($GLOBALS["CORP_LICENSE"])){
		$users=new usersMenus();
		$GLOBALS["CORP_LICENSE"]=$users->CORP_LICENSE;
	}
	
	
	$sock=new sockets();
	$sock->BuildTemplatesConfig();
	$UfdbGuardHTTPEnablePostmaster=1;
	
	
	
	if(!$GLOBALS["CORP_LICENSE"]){
		$FOOTER="
		<table style='width:75%;border-top:1px solid {$GLOBALS["UfdbGuardHTTP"]["FontColor"]};margin-top:15px'>
		<tr><td colspan=2>&nbsp;</td></tr>
		<tr>
		<td width=64px><img src='/squid-internal-static/icons/silk/logo-artica-64.png'></td>
		<td style='font-size:14px;padding-left:10px' width=99%>
		You using Artica Proxy Appliance v{$GLOBALS["ARTICA_VERSION"]} in Community mode.<br>
		<i>Visit our  <a href=\"http://artica-proxy.com\">website</a> for technical informations or to purchase an Entreprise Edition License</i>
		</td>
		</tr>
		</table>
		</div>";
	}
	$f[]="<!DOCTYPE HTML>";
	$f[]="<html>";
		$f[]="<head>";
		$f[]="<title>$title</title>";
		$f[]="<script type=\"text/javascript\">";
	$f[]="    function checkIfTopMostWindow()";
	$f[]="    {";
		$f[]="        if (window.top != window.self) ";
	$f[]="        {  ";
	$f[]="            document.body.style.opacity    = \"0.0\";";
	$f[]="            document.body.style.background = \"#FFFFFF\";";
	$f[]="        }";
		$f[]="        else";
	$f[]="        {";
		$f[]="            document.body.style.opacity    = \"1.0\";";
	$f[]="            document.body.style.background = \"{$GLOBALS["UfdbGuardHTTP"]["BackgroundColor"]}\";";
		$f[]="        } ";
	$f[]="    }";
		$f[]="</script>";
		$f[]="<style type=\"text/css\">";
	$f[]="    body {";
	$f[]="        color:            {$GLOBALS["UfdbGuardHTTP"]["FontColor"]}; ";
	$f[]="        background-color: #FFFFFF; ";
	$f[]="        font-family:      {$GLOBALS["UfdbGuardHTTP"]["Family"]}; ";
	$f[]="        font-weight:      lighter;";
	$f[]="        font-size:        14pt; ";
	$f[]="        ";
		$f[]="        opacity:            0.0;";
	$f[]="        transition:         opacity 2s;";
	$f[]="        -webkit-transition: opacity 2s;";
	$f[]="        -moz-transition:    opacity 2s;";
	$f[]="        -o-transition:      opacity 2s;";
	$f[]="        -ms-transition:     opacity 2s;    ";
	$f[]="    }";
	
	
	$f[]="    center {";
	$f[]="        color:            {$GLOBALS["UfdbGuardHTTP"]["FontColor"]}; ";
	$f[]="        font-family:      {$GLOBALS["UfdbGuardHTTP"]["Family"]}; ";
	$f[]="        font-weight:      lighter;";
	$f[]="        font-size:        12pt; ";
	$f[]="}";
	
	$f[]="    h1 {";
	$f[]="        font-size: 72pt; ";
	$f[]="        margin-bottom: 0; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]};";
	$f[]="        margin-top: 0 ;";
	$f[]="    }    ";
	$f[]="    h2 {";
	$f[]="        font-size: 22pt; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]}; ";
	$f[]="        font-weight: lighter;";
	$f[]="    }   ";
	$f[]="    h3 {";
	$f[]="        font-size: 18pt; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]}; ";
	$f[]="        font-weight: lighter;";
	$f[]="        margin-bottom: 0 ;";
	$f[]="    }   ";
	$f[]="    #wrapper {";
	$f[]="        width: 700px ;";
	$f[]="        margin-left: auto ;";
	$f[]="        margin-right: auto ;";
	$f[]="    }    ";
	$f[]="    #info {";
	$f[]="        width: 600px ;";
	$f[]="        margin-left: auto ;";
	$f[]="        margin-right: auto ;";
	$f[]="    }    ";
	
	$f[]="    #titles h1 {";
	$f[]="        font-size: 72pt; ";
	$f[]="        margin-bottom: 0; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]};";
	$f[]="        margin-top: 0 ;";
	$f[]="    }    ";
	
	$f[]="hr {
				border-top: 1px dotted #f00;
  color: #fff;
  background-color: #fff;
  height: 1px;
  width:50%;
}
";
	
	$f[]="    #content p {";
	$f[]="       font-size:  11pt;  ";
	$f[]="        margin-bottom: 0; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]};";
	$f[]="        margin-top: 0 ;";
	$f[]="    }    ";
	$f[]="    #footer p {";
	$f[]="       font-size:  12pt;  ";
	$f[]="        margin-bottom: 0; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]};";
	$f[]="        margin-top: 0 ;";
	$f[]="    }    ";
	
	$f[]="    #data pre{";
	$f[]="       font-size:  12pt;  ";
	$f[]="        margin-bottom: 0; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]};";
	$f[]="        font-weight: bold;";
	$f[]="        margin-top: 0 ;";
	$f[]="    }    ";
	$f[]="    #data pre:before{content: \"\\275D\";margin-right:5px;font-size:22pt}";
	$f[]="    #data pre:after{content: \"\\275E\";margin-left:5px;font-size:22pt}";
	$f[]=".bad{ font-size: 110px; float:left; margin-right:30px; }";
	$f[]=".bad:before{ content: \"\\260C\";}";
	
	$f[]="#dirlisting{";
	$f[]="       font-size:  12pt;  ";
	$f[]="        margin-bottom: 0; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]};";
	$f[]="        font-weight: lighter;";
	$f[]="        margin-top: 0 ;";		
	$f[]="    }    ";
	$f[]="#dirlisting th{";
	$f[]="       font-size:  16pt;  ";
	$f[]="    }    ";
	$f[]="    td.info_title {    ";
	$f[]="        text-align: right;";
	$f[]="        font-size:  12pt;  ";
	$f[]="        min-width: 100px;";
	$f[]="    }";
	$f[]="    td.info_content {";
	$f[]="        text-align: left;";
	$f[]="        padding-left: 10pt ;";
	$f[]="        font-size:  12pt;  ";
	$f[]="    }";
	$f[]="    .break-word {";
	$f[]="        width: 500px;";
	$f[]="        word-wrap: break-word;";
	$f[]="    }    ";
	$f[]="    a {";
	$f[]="        text-decoration: underline;";
	$f[]="        color: {$GLOBALS["UfdbGuardHTTP"]["FontColor"]}; ";
	$f[]="    }";
	$f[]="    a:visited{";
	$f[]="        text-decoration: underline;";
	$f[]="        color: {$GLOBALS["UfdbGuardHTTP"]["FontColor"]}; ";
	$f[]="    }";
	$f[]="</style>";
	$f[]="</head>";
	$f[]="<body onLoad='checkIfTopMostWindow()'>";
	$f[]="<div id=\"wrapper\">";
	$f[]="    <h1 class=bad></h1>";
	$f[]="    <div id=\"info\">";
	$f[]="$content";
	
	
		if($GLOBALS["UfdbGuardHTTP"]["NoVersion"]==0){
		$f[]="<center>Artica Proxy, version {$GLOBALS["ARTICA_VERSION"]}</center>";
				}
				$f[]="    </div>    $FOOTER";
				$f[]="</div>";
	$f[]="</body>";
	$f[]="<!-- ";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="-->";
	$f[]="</html>";
	return @implode("\n", $f);	
	
}


function TemplatesDesign($title,$content){
	$title=utf8_decode($title);
	$content=utf8_decode($content);
	if(!isset($GLOBALS["CORP_LICENSE"])){
		$users=new usersMenus();
		$GLOBALS["CORP_LICENSE"]=$users->CORP_LICENSE;
	}
	
	$sock=new sockets();
	$sock->BuildTemplatesConfig();
	$UfdbGuardHTTPEnablePostmaster=1;
	$SquidHTTPTemplateSmiley=$sock->GET_INFO("SquidHTTPTemplateSmiley");
	if($SquidHTTPTemplateSmiley==null){$SquidHTTPTemplateSmiley=2639;}
	
	
	if(!$GLOBALS["CORP_LICENSE"]){
		$FOOTER="
		<table style='width:75%;border-top:1px solid {$GLOBALS["UfdbGuardHTTP"]["FontColor"]};margin-top:15px'>
		<tr><td colspan=2>&nbsp;</td></tr>
		<tr>
		<td width=64px><img src='/squid-internal-static/icons/silk/logo-artica-64.png'></td>
		<td style='font-size:14px;padding-left:10px' width=99%>
		You using Artica Proxy Appliance v{$GLOBALS["ARTICA_VERSION"]} in Community mode.<br>
		<i>Visit our  <a href=\"http://artica-proxy.com\">website</a> for technical informations or to purchase an Entreprise Edition License</i>
		</td>
		</tr>
		</table>
		</div>";
	}
	$f[]="<!DOCTYPE HTML>";
	$f[]="<html>";
	$f[]="<head>";
	$f[]="<title>$title</title>";
	$f[]="<script type=\"text/javascript\">";
	$f[]="    function checkIfTopMostWindow()";
	$f[]="    {";
	$f[]="        if (window.top != window.self) ";
	$f[]="        {  ";
	$f[]="            document.body.style.opacity    = \"0.0\";";
	$f[]="            document.body.style.background = \"#FFFFFF\";";
	$f[]="        }";
	$f[]="        else";
	$f[]="        {";
	$f[]="            document.body.style.opacity    = \"1.0\";";
	$f[]="            document.body.style.background = \"{$GLOBALS["UfdbGuardHTTP"]["BackgroundColor"]}\";";
		$f[]="        } ";
	$f[]="    }";
	$f[]="</script>";
	$f[]="<style type=\"text/css\">";
	$f[]="    body {";
	$f[]="        color:            {$GLOBALS["UfdbGuardHTTP"]["FontColor"]}; ";
	$f[]="        background-color: #FFFFFF; ";
	$f[]="        font-family:      {$GLOBALS["UfdbGuardHTTP"]["Family"]}; ";
	$f[]="        font-weight:      lighter;";
	$f[]="        font-size:        14pt; ";
	$f[]="        ";
	$f[]="        opacity:            0.0;";
	$f[]="        transition:         opacity 2s;";
	$f[]="        -webkit-transition: opacity 2s;";
	$f[]="        -moz-transition:    opacity 2s;";
	$f[]="        -o-transition:      opacity 2s;";
	$f[]="        -ms-transition:     opacity 2s;    ";
	$f[]="    }";
	
	
	$f[]="    center {";
	$f[]="        color:            {$GLOBALS["UfdbGuardHTTP"]["FontColor"]}; ";
	$f[]="        font-family:      {$GLOBALS["UfdbGuardHTTP"]["Family"]}; ";
	$f[]="        font-weight:      lighter;";
	$f[]="        font-size:        12pt; ";
	$f[]="}";
	
	$f[]="    h1 {";
	$f[]="        font-size: 72pt; ";
	$f[]="        margin-bottom: 0; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]};";
	$f[]="        margin-top: 0 ;";
	$f[]="    }    ";
	$f[]="    h2 {";
	$f[]="        font-size: 22pt; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]}; ";
	$f[]="        font-weight: lighter;";
	$f[]="    }   ";
	$f[]="    h3 {";
	$f[]="        font-size: 18pt; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]}; ";
	$f[]="        font-weight: lighter;";
	$f[]="        margin-bottom: 0 ;";
	$f[]="    }   ";
	$f[]="    #wrapper {";
	$f[]="        width: 700px ;";
	$f[]="        margin-left: auto ;";
	$f[]="        margin-right: auto ;";
	$f[]="    }    ";
	$f[]="    #info {";
	$f[]="        width: 600px ;";
	$f[]="        margin-left: auto ;";
	$f[]="        margin-right: auto ;";
	$f[]="    }    ";
	
	$f[]="    #titles h1 {";
	$f[]="        font-size: 72pt; ";
	$f[]="        margin-bottom: 0; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]};";
	$f[]="        margin-top: 0 ;";
	$f[]="    }    ";
	
	$f[]="hr {
   border-top: 1px dotted #f00;
  color: #fff;
  background-color: #fff;
  height: 1px;
  width:50%;
}
";
	
	$f[]="    #content p {";
	$f[]="       font-size:  11pt;  ";
	$f[]="        margin-bottom: 0; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]};";
	$f[]="        margin-top: 0 ;";
	$f[]="    }    ";
	
	$f[]="    #footer p {";
	$f[]="       font-size:  12pt;  ";
	$f[]="        margin-bottom: 0; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]};";
	$f[]="        margin-top: 0 ;";
	$f[]="    }    ";	

	
	$f[]="    #data pre{";
	$f[]="       font-size:  12pt;  ";
	$f[]="        margin-bottom: 0; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]};";
	$f[]="        font-weight: bold;";
	$f[]="        margin-top: 0 ;";
	$f[]="    }    ";
	$f[]="    #data pre:before{content: \"\\275D\";margin-right:5px;font-size:22pt}";
	$f[]="    #data pre:after{content: \"\\275E\";margin-left:5px;font-size:22pt}";
	$f[]=".bad{ font-size: 110px; float:left; margin-right:30px; }";
	$f[]=".bad:before{ content: \"\\$SquidHTTPTemplateSmiley\";}";
	
	$f[]="    td.info_title {    ";
	$f[]="        text-align: right;";
	$f[]="        font-size:  12pt;  ";
	$f[]="        min-width: 100px;";
	$f[]="    }";
	$f[]="    td.info_content {";
	$f[]="        text-align: left;";
	$f[]="        padding-left: 10pt ;";
	$f[]="        font-size:  12pt;  ";
	$f[]="    }";
	$f[]="    .break-word {";
	$f[]="        width: 500px;";
	$f[]="        word-wrap: break-word;";
	$f[]="    }    ";
	$f[]="    a {";
	$f[]="        text-decoration: underline;";
	$f[]="        color: {$GLOBALS["UfdbGuardHTTP"]["FontColor"]}; ";
		$f[]="    }";
	$f[]="    a:visited{";
	$f[]="        text-decoration: underline;";
	$f[]="        color: {$GLOBALS["UfdbGuardHTTP"]["FontColor"]}; ";
		$f[]="    }";
	$f[]="</style>";
	$f[]="</head>";
	$f[]="<body onLoad='checkIfTopMostWindow()'>";
	$f[]="<div id=\"wrapper\">";
	$f[]="    <h1 class=bad></h1>";
	$f[]="    <div id=\"info\">";
	$f[]="$content";
	
	
	if($GLOBALS["UfdbGuardHTTP"]["NoVersion"]==0){
		$f[]="<center>Artica Proxy, version {$GLOBALS["ARTICA_VERSION"]}</center>";
	}
	$f[]="    </div>    $FOOTER";
	$f[]="</div>";
	$f[]="</body>";
	$f[]="<!-- ";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="-->";
	$f[]="</html>";
	return @implode("\n", $f);
	
}


function TemplatesInMysql($aspid=false){
	if(is_file("/usr/bin/cgclassify")){if(is_dir("/cgroups/blkio/php")){shell_exec("/usr/bin/cgclassify -g cpu,cpuset,blkio:php ".getmypid());}}
	$unix=new unix();
	$pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if(!$aspid){
		$pid=$unix->get_pid_from_file($pidpath);
		if($unix->process_exists($pid)){return;}
			
	}
	
	@file_put_contents($pidpath, getmypid());
	@file_put_contents("/etc/artica-postfix/SQUID_TEMPLATE_DONE", time());
	@file_put_contents("/etc/artica-postfix/SQUID_TEMPLATE_DONEv2", time());
	
	
	$sock=new sockets();
	$SquidTemplateSimple=$sock->GET_INFO("SquidTemplateSimple");
	if(!is_numeric($SquidTemplateSimple)){$SquidTemplateSimple=1;}
	if($SquidTemplateSimple==1){
		$unix=new unix();
		$php=$unix->LOCATE_PHP5_BIN();
		$nohup=$unix->find_program("nohup");
		$by="--FUNC-".__FUNCTION__."-L-".__LINE__;
		shell_exec("$nohup $php /usr/share/artica-postfix/exec.squid.templates.php $by >/dev/null 2>&1 &");
		return;
	}
	
	
	
	$TimeExec=$unix->file_time_min($pidtime);
	if(!$GLOBALS["FORCE"]){
		if($TimeExec<240){return;}
	}
	
	
	
	$users=new usersMenus();
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
	if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}
	if($UnlockWebStats==1){$EnableRemoteStatisticsAppliance=0;}	
	if($EnableRemoteStatisticsAppliance==1){
		if(!$users->CORP_LICENSE){
			if(!is_file("/etc/artica-postfix/SQUID_TEMPLATE_DONE")){
				DefaultTemplatesInArtica();
				return;
			}
		}
		EventsWatchdog("Using the Web statistics appliance to get template files");
		if($GLOBALS["VERBOSE"]){echo "Use the Web statistics appliance to get template files...\n";}
		TemplatesInMysql_remote();
		return;
	}	
		
	
	@mkdir("/etc/artica-postfix",0755,true);
	$base="/usr/share/squid-langpack";
	@mkdir($base,0755,true);
	if(!is_dir("$base/templates")){@mkdir("$base/templates",0755,true);}
	$headerTemp=@file_get_contents(dirname(__FILE__)."/ressources/databases/squid.default.header.db");
	
	
	
	
	$q=new mysql_squid_builder();
	if(!$q->BD_CONNECT(true)){
		squid_admin_mysql(2,"Error, unable to connect to MySQL",__FILE__,__LINE__);
		if(!$users->CORP_LICENSE){DefaultTemplatesInArtica();}
		return;
	}
	
	$sql="CREATE TABLE IF NOT EXISTS `squidtpls` (
			  `zmd5` CHAR(32)  NOT NULL,
			  `template_name` varchar(128)  NOT NULL,
			  `template_body` LONGTEXT  NOT NULL,
			  `template_header` LONGTEXT  NOT NULL,
			  `template_title` varchar(255)  NOT NULL,
			  `template_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			  `template_link` smallint(1) NOT NULL,
			  `template_uri` varchar(255)  NOT NULL,
			  `lang` varchar(5)  NOT NULL,
			  PRIMARY KEY (`zmd5`),
			  KEY `template_name` (`template_name`,`lang`),
			  KEY `template_title` (`template_title`),
			  KEY `template_time` (`template_time`),
			  KEY `template_link` (`template_link`),
			  FULLTEXT KEY `template_body` (`template_body`)
			)  ENGINE = MYISAM;";
	$q->QUERY_SQL($sql);
	

	
	
	if($q->COUNT_ROWS("squidtpls")==0){
		if(!is_file("/etc/artica-postfix/SQUID_TEMPLATE_DONE")){
			squid_admin_mysql(2,"Ask to build default templates squidtpls=0", null,__FILE__,__LINE__);
			DefaultTemplatesInArtica();
		}
	}
	
	if(!$users->CORP_LICENSE){
		if(!is_file("/etc/artica-postfix/SQUID_TEMPLATE_DONE")){
			squid_admin_mysql(2,"Ask to build default templates - no license -", null,__FILE__,__LINE__);
			DefaultTemplatesInArtica();
			return;
		}
	}
	
	$sql="SELECT * FROM squidtpls";
	$results = $q->QUERY_SQL($sql);	
	if(!$q->ok){
		squid_admin_mysql(1, "MySQL Error on templates", $q->mysql_error,__FILE__,__LINE__);
		ufdbguard_admin_events("Fatal,$q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "proxy");
		return;
	}
	$c=0;
	while ($ligne = mysql_fetch_assoc($results)) {
		$ligne["template_header"]=stripslashes($ligne["template_header"]);
		$ligne["template_title"]=stripslashes($ligne["template_title"]);
		$ligne["template_body"]=stripslashes($ligne["template_body"]);
		$template_name=$ligne["template_name"];
		if($ligne["template_link"]==1){continue;}
		$header=trim($ligne["template_header"]);
		if($header==null){$header=$headerTemp;}
		if($GLOBALS["VERBOSE"]){
			echo "Template: `$template_name`: {$ligne["template_title"]}\n";
		}
		
		if(!preg_match("#^ERR_.+#", $ligne["template_name"])){
				$ligne["template_name"]="ERR_".$ligne["template_name"];
		}
		
		$filename2=null;
		$ligne["template_body"]=utf8_encode($ligne["template_body"]);
		$ligne["template_title"]=utf8_encode($ligne["template_title"]);
		
		
		$filename="$base/{$ligne["lang"]}/{$ligne["template_name"]}";
		if($ligne["lang"]=="en"){
			$filename2="/usr/share/squid-langpack/templates/{$ligne["template_name"]}";
		}
		$newheader=str_replace("{TITLE}", $ligne["template_title"], $header);
		$templateDatas="$newheader{$ligne["template_body"]}</body></html>";
		
		if($GLOBALS["VERBOSE"]){
			echo "Template: `$template_name`: Path `$filename`\n";
		}
		
		if($ligne["emptytpl"]==1){
			$templateDatas="<html><head></head><body></body></html>";
		}
		
		if($GLOBALS["VERBOSE"]){
			echo "Template: `$template_name`: {$ligne["lang"]}\n";
		}
		
		if(is_numeric($ligne["lang"])){$ligne["lang"]="en";}
		
		@mkdir(dirname($filename),0755,true);
		@file_put_contents($filename, $templateDatas);
		if($filename2<>null){
			@file_put_contents($filename2, $templateDatas);
			$unix->chown_func("squid","squid","$filename2");
		}
		@file_put_contents("$base/{$ligne["lang"]}/{$ligne["template_name"]}", $templateDatas);
		$unix->chown_func("squid","squid","$base/{$ligne["lang"]}/{$ligne["template_name"]}");
		$unix->chown_func("squid","squid","$filename");
		
		$c++;
		

		if($ligne["lang"]=="en"){
			if($GLOBALS["VERBOSE"]){echo "Writing $base/{$ligne["template_name"]}\n";}
			@file_put_contents("$base/{$ligne["template_name"]}", $templateDatas);
			$unix->chown_func("squid:squid", null,"$base/templates/{$ligne["template_name"]}");
		}else{
			if(!IfTemplateExistsinEn($template_name)){
				@mkdir("$base/en",0755,true);
				@file_put_contents("$base/en/{$ligne["template_name"]}", $templateDatas);
				$unix->chown_func("squid:squid", null,"$base/en/{$ligne["template_name"]}");
				}
		}
	}
	
	
	
	$sql="SELECT * FROM squidtpls WHERE emptytpl=1";
	$results = $q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		if(is_numeric($ligne["lang"])){$ligne["lang"]="en";}
		if(!preg_match("#^ERR_.+#", $ligne["template_name"])){
			$ligne["template_name"]="ERR_".$ligne["template_name"];
		}
		
		$filename="$base/{$ligne["lang"]}/{$ligne["template_name"]}";
		$templateDatas="<html><head></head><body></body></html>";
		@mkdir(dirname($filename),0755,true);
		@file_put_contents($filename, $templateDatas);
		@file_put_contents("$base/{$ligne["lang"]}/{$ligne["template_name"]}", $templateDatas);
		$unix->chown_func("squid","squid","$base/{$ligne["lang"]}/{$ligne["template_name"]}");
		$unix->chown_func("squid","squid","$filename");
	}
	
	
	
	
	$unix=new unix();
	$tar=$unix->find_program("tar");
	$unix->chown_func("squid","squid", "$base/*");
	chdir($base);
	shell_exec("$tar -czf ".dirname(__FILE__)."/ressources/databases/squid-lang-pack.tgz *");
	
	if($EnableWebProxyStatsAppliance==1){
		if($GLOBALS["VERBOSE"]){echo "-> notify_remote_proxys()\n";}
		notify_remote_proxys("SQUID_LANG_PACK");
		if($GLOBALS["VERBOSE"]){echo "This is a statistics appliance, aborting next step\n";}
		return;
	}	
	
	squid_admin_mysql(2, "$c web pages templates saved", "no information",__FILE__,__LINE__);
	Reload_Squid();
	
}

function EventsWatchdog($text){

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


function IfTemplateExistsinEn($template_name){
	if(isset($GLOBALS["IfTemplateExistsinEn$template_name"])){return $GLOBALS["IfTemplateExistsinEn$template_name"];}
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT zmd5 FROM squidtpls WHERE template_name='$template_name' AND lang='en'","artica_backup"));
	if($ligne["zmd5"]==null){$GLOBALS["IfTemplateExistsinEn$template_name"]=false;return false;}
	$GLOBALS["IfTemplateExistsinEn$template_name"]=true;
	return true;
}




function StatsApplianceExportTables(){
	$f=new squid_stats_appliance();
	$f->export_tables();	
}

function notify_remote_proxys($COMMANDS=null){
	$unix=new unix();
	include_once(dirname(__FILE__)."/ressources/class.blackboxes.inc");
	$EXEC_PID_FILE="/etc/artica-postfix/".basename(__FILE__).".".__FUNCTION__.".pid";
	$EXEC_PID_TIME="/etc/artica-postfix/".basename(__FILE__).".".__FUNCTION__.".time";
	$pid=@file_get_contents($EXEC_PID_FILE);
	if($unix->process_exists($pid,basename(__FILE__))){
		$timefile=$unix->file_time_min($EXEC_PID_FILE);
		if($timefile<15){
			$unix->events("Skipping, Already executed pid $pid {$timefile}Mn","/var/log/stats-appliance.log");
			ufdbguard_admin_events("Skipping, Already executed pid $pid {$timefile}Mn...", __FUNCTION__, __FILE__, __LINE__, "communicate");return ;}
		$kill=$unix->find_program("kill");
		unix_system_kill_force($pid);
	}	
	

	@file_put_contents($EXEC_PID_FILE, getmypid());
	
	if($COMMANDS==null){$COMMANDS="BUILDCONF";}
	
	if($COMMANDS=="PING"){
		$time=$unix->file_time_min($EXEC_PID_TIME);
		if(!$GLOBALS["VERBOSE"]){
			if($time<5){return;}
		}
		@unlink($EXEC_PID_TIME);
		@file_put_contents($EXEC_PID_TIME, time());
		$bb=new blackboxes();
		$bb->NotifyAll("PING");
		return;
	}
	
	
	$t=time();
	$f=new squid_stats_appliance();
	$f->export_tables();
	$took=$unix->distanceOfTimeInWords($t,time(),true);
	ufdbguard_admin_events("Exporting MySQL datas done... took:$took", 
	__FUNCTION__, __FILE__, __LINE__, "communicate");
	$unix->events("Exporting MySQL datas done... took:$took","/var/log/stats-appliance.log");
	
	include_once(dirname(__FILE__)."/ressources/class.blackboxes.inc");
	$unix->events("Send order to appliance(s)","/var/log/stats-appliance.log");
	$bb=new blackboxes();
	$bb->NotifyAll("BUILDCONF");
	
	
	
}
function watchdog_config(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$unix->THREAD_COMMAND_SET("$php /usr/share/artica-postfix/exec.monit.php --build");
}

function checkdatabase(){
	
	$f["webfilter_aclsdynamic"]=true;
	$f["webfilter_aclsdynlogs"]=true;
	$f["webfilter_assoc_groups"]=true;
	$f["webfilter_avwhitedoms"]=true;
	$f["webfilter_bannedexts"]=true;
	$f["webfilter_bannedextsdoms"]=true;
	$f["webfilter_blkcnt"]=true;
	$f["webfilter_blkgp"]=true;
	$f["webfilter_blklnk"]=true;
	$f["webfilter_blks"]=true;
	$f["webfilter_certs"]=true;
	$f["webfilter_dnsbl"]=true;
	$f["webfilter_group"]=true;
	$f["webfilter_members"]=true;
	$f["webfilter_rules"]=true;
	$f["webfilter_terms"]=true;
	$f["webfilter_termsassoc"]=true;
	$f["webfilter_termsg"]=true;
	$f["webfilter_ufdbexpr"]=true;
	$f["webfilter_ufdbexprassoc"]=true;
	$f["webfilter_updateev"]=true;
	$f["webfilters_backupeddbs"]=true;
	$f["webfilters_bigcatzlogs"]=true;
	$f["webfilters_blkwhlts"]=true;
	$f["webfilters_categories_caches"]=true;
	$f["webfilters_databases_disk"]=true;
	$f["webfilters_dbstats"]=true;
	$f["webfilters_dtimes_blks"]=true;
	$f["webfilters_dtimes_rules"]=true;
	$f["webfilters_ipaddr"]=true;
	$f["webfilters_nodes"]=true;
	$f["webfilters_quotas"]=true;
	$f["webfilters_rewriteitems"]=true;
	$f["webfilters_rewriterules"]=true;
	$f["webfilters_schedules"]=true;
	$f["webfilters_sqaclaccess"]=true;
	$f["webfilters_sqacllinks"]=true;
	$f["webfilters_sqacls"]=true;
	$f["webfilters_sqaclsports"]=true;
	$f["webfilters_sqgroups"]=true;
	$f["webfilters_sqitems"]=true;
	$f["webfilters_sqtimes_assoc"]=true;
	$f["webfilters_sqtimes_rules"]=true;
	$f["webfilters_thumbnails"]=true;
	$f["webfilters_updates"]=true;
	$f["webfilters_usersasks"]=true;
	$f["websites_caches_params"]=true;
	$q=new mysql_squid_builder();
	build_progress("{reconfigure} Testing connection",25);
	$GLOBALS["SQUID_OUTPUT_TESTCNX"]=true;
	if(!$q->TestingConnection()){
		echo "Starting......: ".date("H:i:s")." [MYSQL]: Connection failed...\n";
		return;
	}
	
	$build=false;
	while (list ($tablename, $DGRULE) = each ($f)){
		if(!$q->TABLE_EXISTS($tablename)){
			echo "Starting......: ".date("H:i:s")." [MYSQL]: Missing table `$tablename`\n";
			$build=true;
		}
	}
	
	if($build){
		echo "Starting......: ".date("H:i:s")." [MYSQL]: Construct database\n";
		$q->CheckTables(null,true);
		$q->CheckTablesICAP();
	}
	
	build_progress("{reconfigure} Testing connection {done}",25);
	
}




function ToSyslog($text){
	
if(!function_exists("syslog")){return;}
$file=basename(__FILE__);
$LOG_SEV=LOG_INFO;
openlog($file, LOG_PID , LOG_SYSLOG);
syslog($LOG_SEV, $text);
closelog();
}



function caches_infos($aspid=false){
	$cacheFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$cachePID="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	if($GLOBALS["FORCE"]){ToSyslog("caches_infos(): Executed in force mode");}
	$unix=new unix();
	$sock=new sockets();
	
	if(!$GLOBALS["VERBOSE"]){
		if(system_is_overloaded(basename(__FILE__))){
			EventsWatchdog("Overloaded system, aborting task...");
			ToSyslog("caches_infos(): Overloaded system, aborting task");
			writelogs("Overloaded system, aborting task...",__FUNCTION__,__FILE__,__LINE__);
			return;
		}
		
		if($aspid){
			$pid=@file_get_contents($cachePID);
			if($unix->process_exists($pid)){
				$timepid=$unix->PROCCESS_TIME_MIN($pid);
				if($timepid<15){
					ToSyslog("caches_infos(): Already task PID: $pid running since {$timepid}Mn");
				}
				$kill=$unix->find_program("$kill");unix_system_kill_force($pid);}
		}
	}
	

	if($GLOBALS["VERBOSE"]){echo "init...\n";}
	
	$q=new mysql_squid_builder();
	if(!$GLOBALS["FORCE"]){
		$CacheTime=$unix->file_time_min($cacheFile);
		if($CacheTime<15){
			EventsWatchdog("Max 15Mn, current=$CacheTime ($cacheFile)...");
			if($GLOBALS["VERBOSE"]){echo "Max 15Mn, current=$CacheTime\n";}
			return;
		}
	}
	
	$squid_pid=SQUID_PID();
	if(!$unix->process_exists($squid_pid)){
		$nohup=$unix->find_program("nohup");
		squid_admin_mysql(0, "Squid-Cache is not running..., start it", null,__FILE__,__LINE__);
		ToSyslog("caches_infos(): Squid-Cache is not running..., start it");
		shell_exec("$nohup /etc/init.d/squid start >/dev/null 2>&1 &");
		return;
	}
	
	@unlink($cacheFile);
	@file_put_contents($cacheFile, time());
	
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

function restart_squid(){
	$unix=new unix();
	$byschedule=null;
	$taskid=null;
	$timeFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$TimeMin=$unix->file_time_min($timeFile);
	if($TimeMin<60){
		squid_admin_mysql(1, "Ask to restart proxy service aborted {$TimeMin}Mn need at least 60mn", null,__FILE__,__LINE__);
		return;
	}
	
	@unlink($timeFile);
	@file_put_contents($timeFile, time());
	
	if($GLOBALS["BY_SCHEDULE"]){
		$byschedule="Scheduled task";
		if($GLOBALS["SCHEDULE_ID"]>0){
			$taskid=" - Task ID {$GLOBALS["SCHEDULE_ID"]}";
		}
	}
	
	squid_admin_mysql(1, "Ask to restart proxy service ($byschedule$taskid)", null,__FILE__,__LINE__);
	shell_exec("/etc/init.d/squid restart --force --script=".basename(__FILE__));
	
	
}

function restart_kav4proxy(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	exec("/etc/init.d/artica-postfix restart kav4proxy 2>&1",$results);
	ufdbguard_admin_events("Task `restart Kav4Proxy` was executed`\n".@implode("\n", $results) , __FUNCTION__, __FILE__, __LINE__, "tasks");
}


function extract_schedules(){
	$sql="SELECT *  FROM webfilters_schedules WHERE enabled=1";
	$q=new mysql_squid_builder();
	$results = $q->QUERY_SQL($sql);	
	while ($ligne = mysql_fetch_assoc($results)) {
		$TaskType=$ligne["TaskType"];
		$TimeText=$ligne["TimeText"];		
		$TimeDescription=mysql_escape_string2($ligne["TimeDescription"]);
		$lines[]="\$array[$TaskType]=array(\"TimeText\"=>\"$TimeText\",\"TimeDescription\"=>\"$TimeDescription\");";
		
	}
	echo implode("\n", $lines);
	
}

function run_schedules($ID){
	$GLOBALS["SCHEDULE_ID"]=$ID;
	writelogs("Task $ID",__FUNCTION__,__FILE__,__LINE__);
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT TaskType FROM webfilters_schedules WHERE ID=$ID"));
	
	$TaskType=$ligne["TaskType"];
	if($TaskType==0){continue;}	
	if(!isset($q->tasks_processes[$TaskType])){ufdbguard_admin_events("Unable to understand task type `$TaskType` For this task" , __FUNCTION__, __FILE__, __LINE__, "tasks");return;}
	$script=$q->tasks_processes[$TaskType];
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$WorkingDirectory=dirname(__FILE__);
	$cmd="$nohup $php5 $WorkingDirectory/$script --schedule-id=$ID >/dev/null 2>&1 &";
	writelogs("Task {$GLOBALS["SCHEDULE_ID"]} is executed with `$cmd` ",__FUNCTION__,__FILE__,__LINE__);
	ufdbguard_admin_events("Task is executed with `$cmd`" , __FUNCTION__, __FILE__, __LINE__, "tasks");
	shell_exec($cmd);
	
}

function build_schedules_tests(){
	$unix=new unix();
	if(!$unix->IsSquidTaskCanBeExecuted()){
		EventsWatchdog("IsSquidTaskCanBeExecuted() return false");
		
		return;}
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	$pidTimeINT=$unix->file_time_min($pidTime);
	if(!$GLOBALS["VERBOSE"]){
		if($pidTimeINT<5){
			EventsWatchdog("Too short time to execute the process ($pidTime)");
			writelogs("To short time to execute the process",__FILE__,__FUNCTION__,__LINE__);
			return;
		}
	}

	@file_put_contents($pidTime, time());
	
	if(!is_file("/etc/artica-postfix/squid.schedules")){
		echo "No schedule yet....\n";
		shell_exec("/etc/init.d/artica-postfix restart watchdog");
	}
	
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT TimeText FROM webfilters_schedules WHERE TaskType=14"));
	if($ligne["TimeText"]==null){
		$sql="INSERT INTO `webfilters_schedules` (`TimeText`, `TimeDescription`, `TaskType`, `enabled`) VALUES ('30 6 * * *', 'Optimize all tables  each day at 06h30', 14, 1);";
		$q->QUERY_SQL($sql);
		if(!$q->ok){writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);return;}
		shell_exec("/etc/init.d/artica-postfix restart watchdog");
	}
}

function rotate_logs(){
	
	$pidfile="/etc/artica-postfix/pids/exec.squid.php.rotate_logs.pid";
	$unix=new unix();
	$sock=new sockets();
	$GLOBALS["SQUIDBIN"]=$unix->LOCATE_SQUID_BIN();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}
		return;
	}
	$getmypid=getmypid();
	@file_put_contents($pidfile, getmypid());
	
	
	build_progress_rotation("{starting}",5);
	
	$LogsRotateDefaultSizeRotation=$sock->GET_INFO("LogsRotateDefaultSizeRotation");
	if(!is_numeric($LogsRotateDefaultSizeRotation)){$LogsRotateDefaultSizeRotation=100;}
	$EnableProxyCompressor=intval($sock->GET_INFO("EnableProxyCompressor"));
	
	$ScheduleCMD=null;
	build_progress_rotation("{default} {$LogsRotateDefaultSizeRotation}MB",6);
	
	$size=@filesize("/var/log/squid/access.log");
	$squidtail_size=@filesize("/var/log/squid/squidtail.log");
	$ROTATED=false;
	$size=$size/1024;
	$size=round($size/1024);
	
	$squidtail_size=$squidtail_size/1024;
	$squidtail_size=round($squidtail_size/1024);
	
	
	$echo=$unix->find_program("echo");
	
	
	if($GLOBALS["FORCE"]){$forced_text=" - forced";}
	if($GLOBALS["SMOOTH"]){$forced_text=" - smooth";}
	
	$MustRotateAt=$LogsRotateDefaultSizeRotation;
	
	$LastRotate=$unix->file_time_min("/etc/artica-postfix/pids/squid-rotate-cache.time");
	$SquidLogRotateFreq=intval($sock->GET_INFO("SquidLogRotateFreq"));
	$SquidRotateOnlySchedule=intval($sock->GET_INFO("SquidRotateOnlySchedule"));
	if($SquidLogRotateFreq<10){$SquidLogRotateFreq=1440;}
	
	@unlink("/etc/artica-postfix/pids/squid-rotate-cache.time");
	@file_put_contents("/etc/artica-postfix/pids/squid-rotate-cache.time", time());
	
	if($SquidRotateOnlySchedule==1){if($GLOBALS["BY_SCHEDULE"]){$MustRotateAt=0;}}
	if($GLOBALS["FORCE"]){$MustRotateAt=0;}
	if($GLOBALS["PROGRESS"]){$MustRotateAt=0;}
	
	$MUSTROTATE=false;
	
	if($SquidRotateOnlySchedule==0){
		if($LastRotate<$SquidLogRotateFreq){
			if($size>=$MustRotateAt){
				$MUSTROTATE=true;
				build_progress_rotation("/var/log/squid/access.log Must be rotated ",10);
			}
			if($squidtail_size>=$squidtail_size){
				$MUSTROTATE=true;
				build_progress_rotation("/var/log/squid/squidtail.log Must be rotated ",10);
			}
			
		}
		
		if($MUSTROTATE==false){
			build_progress_rotation("Nothing to do...",100);
			return;
		}
	}

	
	
	build_progress_rotation("Rotate access events current size:{$size}M rotate to $MustRotateAt M",10);
	build_progress_rotation("Rotate tail events current size:{$squidtail_size}M rotate to $MustRotateAt M",10);
	

	
	
	if($size>$MustRotateAt){
		
		build_progress_rotation("Backup source file",15);
		if(!@copy("/var/log/squid/access.log", "/var/log/squid/access.log.".time())){
			squid_admin_mysql(0, "[LOG ROTATION]: Unable to duplicate source log!", "",__FILE__,__LINE__);
			build_progress_rotation("Rotate events duplicate file failed",110);
			return;
		}
		
		if(is_file("/var/log/squid/squidtail.log")){
			if(!@copy("/var/log/squid/squidtail.log", "/var/log/squid/squidtail.log.".time())){
				squid_admin_mysql(0, "[LOG ROTATION]: Unable to duplicate squidtail source log!", "",__FILE__,__LINE__);
				build_progress_rotation("Rotate events duplicate file failed",110);
				return;
			}
		}
		
		
		if(is_file("/var/log/squid/childs-access.log")){
			@copy("/var/log/squid/childs-access.log", "/var/log/squid/childs-access.log.".time());
			shell_exec("$echo \"\" >/var/log/squid/childs-access.log");
		}
		
		
		build_progress_rotation("Backup source file {success}",20);
		
		build_progress_rotation("Empty source file",21);
		shell_exec("$echo \"\" >/var/log/squid/access.log");
		shell_exec("$echo \"\" >/var/log/squid/hypercache-plugin.log");
		if(is_file("/var/log/squid/squidtail.log")){shell_exec("$echo \" \"> /var/log/squid/squidtail.log 2>&1");}
		build_progress_rotation("Backup source file {success}",21);
		
		squid_admin_mysql(1, "[LOG ROTATION]: Rotate proxy events {$size}MB", "",__FILE__,__LINE__);
		build_progress_rotation("Ask proxy to rotate",25);
		shell_exec("{$GLOBALS["SQUIDBIN"]} -k rotate >/dev/null 2>&1");
		
		build_progress_rotation("Restarting watchdog services",30);
		shell_exec("/etc/init.d/auth-tail restart >/dev/null 2>&1");
		shell_exec("/etc/init.d/cache-tail restart >/dev/null 2>&1");
		shell_exec("/etc/init.d/hypercache-tail restart >/dev/null 2>&1");
		$php=$unix->LOCATE_PHP5_BIN();
		$nohup=$unix->find_program("nohup");
		
		if($GLOBALS["PROGRESS"]){
			build_progress_rotation("backup and store files",35);
			system("$php /usr/share/artica-postfix/exec.squid.rotate.php --force --progress");
		}else{
			shell_exec("$php /usr/share/artica-postfix/exec.squid.rotate.php --force >/dev/null 2>&1 &");
		}
		
		build_progress_rotation("{done}...",100);
		
		
	}else{
		build_progress_rotation("Rotate events Nothing to do",100);
	}
	
	
	
	
	if($EnableProxyCompressor==1){
		$ScheduleCMD="--schedule-id={$GLOBALS["SCHEDULE_ID"]}";
		if($GLOBALS["BY_SCHEDULE"]){$ScheduleCMD=" --byschedule";}
		shell_exec("$nohup $php /usr/share/artica-postfix/exec.zipproxy.php --rotate $ScheduleCMD >/dev/null 2>&1 &");
	}
	
	if(is_file("/var/log/squid/childs-access.log")){
		$size=@filesize("/var/log/squid/childs-access.log");
		$ROTATED=false;
		$size=$size/1024;
		$size=round($size/1024);
		if($size>$LogsRotateDefaultSizeRotation+50){
			squid_admin_mysql(1, "Rotate Parent log proxy events {$size}MB exceed rule {$LogsRotateDefaultSizeRotation}M and is more than 50MB", "",__FILE__,__LINE__);
			@copy("/var/log/squid/childs-access.log", "/var/log/squid/childs-access.log.".time());
			shell_exec("{$GLOBALS["SQUIDBIN"]} -k rotate >/dev/null 2>&1");
			shell_exec("/etc/init.d/auth-tail restart >/dev/null 2>&1");
			shell_exec("/etc/init.d/cache-tail restart >/dev/null 2>&1");
			$php=$unix->LOCATE_PHP5_BIN();
			$nohup=$unix->find_program("nohup");
			shell_exec("/usr/share/artica-postfix/exec.squid.rotate.php --force >/dev/null 2>&1 &");
			return;
		
		}
	}
	
}

function _rotate_events($text,$function,$line){
	
	if(function_exists("debug_backtrace")){
		$trace=@debug_backtrace();
		if(isset($trace[1])){
			$file=basename($trace[1]["file"]);
			if($function==null){$function=$trace[1]["function"];}
			if($line==0){$line=$trace[1]["line"];}
		}
			
		if(isset($trace[0])){
			$file=basename($trace[0]["file"]);
			if($function==null){$function=$trace[0]["function"];}
			if($line==0){$line=$trace[0]["line"];}
		}
	}
	
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	$GLOBALS["CLASS_UNIX"]->events($text,"/var/log/artica-postfix/logrotate.debug",false,
			__CLASS__."/$function",$line,__FILE__);
	
}

function build_schedules($notfcron=false){
	$unix=new unix();
	$sock=new sockets();
	
	$squid=$unix->LOCATE_SQUID_BIN();
	if(!is_file($squid)){return;}
	
	@mkdir("/var/log/artica-postfix/youtube",0755,true);
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pid=$unix->get_pid_from_file($pidfile);
	
	
	if($unix->process_exists($pid,basename(__FILE__))){
		writelogs("Already executed pid $pid",__FILE__,__FUNCTION__,__LINE__);
		return;
	}
	
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}		
	
	@file_put_contents($pidfile, getmypid());
	
	$pidTimeINT=$unix->file_time_min($pidTime);
	if(!$GLOBALS["VERBOSE"]){
		if($pidTimeINT<2){
			build_progress_schedules("{failed}", 110);
			writelogs("To short time to execute the process",__FILE__,__FUNCTION__,__LINE__);
			return;
		}
	}
	
	
	
	@file_put_contents($pidTime, time());
	if(!$unix->IsSquidTaskCanBeExecuted()){
		build_progress_schedules("{failed}", 110);
		if($GLOBALS["VERBOSE"]){echo "These tasks cannot be executed in this server\n";}
		return;
	}
	
	$q=new mysql_squid_builder();
	$q->CheckDefaultSchedules();
	if($q->COUNT_ROWS("webfilters_schedules")==0){
		build_progress_schedules("{failed} no schedules set", 110);
		return;}
	
	
	$sql="SELECT *  FROM webfilters_schedules WHERE enabled=1";
	
	$results = $q->QUERY_SQL($sql);	
	if(!$q->ok){
		build_progress_schedules("{failed} MySQL error", 110);
		return;}	
	
	@unlink("/etc/cron.d/SquidTailInjector");
	$php5=$unix->LOCATE_PHP5_BIN();
	$WorkingDirectory=dirname(__FILE__);
	$chmod=$unix->find_program("chmod");
	foreach (glob("/etc/cron.d/*") as $filename) {
		$file=basename($filename);
		
		if(preg_match("#squidsch-[0-9]+#", $filename)){if($GLOBALS["VERBOSE"]){echo "Removing old task $file\n";}@unlink($filename);}
	}
	@unlink("/etc/artica-postfix/TASKS_SQUID_CACHE.DB");
	$settings=unserialize(base64_decode($sock->GET_INFO("FcronSchedulesParams")));
	if(!isset($settings["max_nice"])){$settings["max_nice"]=19;}
	if(!isset($settings["max_load_avg5"])){$settings["max_load_avg5"]=3;}
	if(!isset($settings["max_load_wait"])){$settings["max_load_wait"]=10;}
	if(!is_numeric($settings["max_load_avg5"])){$settings["max_load_avg5"]="3";}
	if(!is_numeric($settings["max_load_wait"])){$settings["max_load_wait"]="10";}
	if(!is_numeric($settings["max_nice"])){$settings["max_nice"]="19";}	
	$max_load_wait=$settings["max_load_wait"];	
	
	$finalsettings="nice({$settings["max_nice"]}),lavg5({$settings["max_load_avg5"]}),until($max_load_wait)";
	@unlink("/etc/artica-postfix/squid.schedules");
	$nice=EXEC_NICE();
	$q=new mysql_squid_builder();
	build_progress_schedules("{building}", 50);
	$c=0;$d=0;
	while ($ligne = mysql_fetch_assoc($results)) {
		$allminutes="1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59";
		$TaskType=$ligne["TaskType"];
		$TimeText=$ligne["TimeText"];
		if($TaskType==0){continue;}
		if($ligne["TimeText"]==null){continue;}
		if($EnableRemoteStatisticsAppliance==1){if($q->tasks_remote_appliance[$TaskType]){$d++;continue;}}
		
		$md5=md5("$TimeText$TaskType");
		if(isset($alreadydone[$md5])){if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." artica-postfix watchdog task {$ligne["ID"]} already set\n";}continue;}
		$alreadydone[$md5]=true;		
		
		
		if(!isset($q->tasks_processes[$TaskType])){
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." artica-postfix task {$ligne["ID"]} no such task...\n";}
			$d++;continue;
		}
		if(isset($q->tasks_disabled[$TaskType])){
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." artica-postfix task {$ligne["ID"]} is disabled or did not make sense...\n";}
			$d++;continue;}
		$script=$q->tasks_processes[$TaskType];
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." artica-postfix create task {$ligne["ID"]} type $TaskType..\n";}
		if(trim($ligne["TimeText"]=="$allminutes * * * *")){$ligne["TimeText"]="* * * * *";}
		
		$f=array();
		$f[]="MAILTO=\"\"";
		$f[]="{$ligne["TimeText"]}  root $nice $php5 $WorkingDirectory/exec.schedules.php --run-squid {$ligne["ID"]} >/dev/null 2>&1";
		$f[]="";
		
		@file_put_contents("/etc/cron.d/squidsch-{$ligne["ID"]}", @implode("\n", $f));
		$c++;
		continue;
		
		
		if(trim($ligne["TimeText"]=="20,40,59 * * * *")){
			$f[]="@$finalsettings,mail(false) 20 $php5 $WorkingDirectory/$script --schedule-id={$ligne["ID"]} >/dev/null 2>&1";
			continue;
		}
		
		
		
		if(trim($ligne["TimeText"]=="0 * * * *")){
			$f[]="@$finalsettings,mail(false) 1h $php5 $WorkingDirectory/$script --schedule-id={$ligne["ID"]} >/dev/null 2>&1";
			continue;		
		}
		if(trim($ligne["TimeText"]=="10,20,30,40,50 * * * *")){
			$f[]="@$finalsettings,mail(false) 10 $php5 $WorkingDirectory/$script --schedule-id={$ligne["ID"]} >/dev/null 2>&1";
			continue;		
		}		
		
		
		
		if(trim($ligne["TimeText"]=="0 0,3,5,7,9,11,13,15,17,19,23 * * *")){
			$f[]="@$finalsettings,mail(false) 3h $php5 $WorkingDirectory/$script --schedule-id={$ligne["ID"]} >/dev/null 2>&1";
			continue;		
		}
		
		if(trim($ligne["TimeText"]=="0 2,4,6,8,10,12,14,16,18,20,22 * * *")){
			$f[]="@$finalsettings,mail(false) 2h $php5 $WorkingDirectory/$script --schedule-id={$ligne["ID"]} >/dev/null 2>&1";
			continue;		
		}
		if(trim($ligne["TimeText"]=="0 0,2,4,6,8,10,12,14,16,18,20,22 * * *")){
			$f[]="@$finalsettings,mail(false) 2h $php5 $WorkingDirectory/$script --schedule-id={$ligne["ID"]} >/dev/null 2>&1";
			continue;		
		}		
		
		$f[]="&$finalsettings,mail(false) {$ligne["TimeText"]} $php5 $WorkingDirectory/$script --schedule-id={$ligne["ID"]} >/dev/null 2>&1";
	}
	
	
	build_progress_schedules("{building}", 80);
	@file_put_contents("/etc/artica-postfix/squid.schedules",implode("\n",$f));
	if($notfcron){
		echo "Starting......: ".date("H:i:s")." Squid $c scheduled tasks ($d disabled)\n";
		return;
	}
	$cron_path=$unix->find_program("cron");
	$kill=$unix->find_program("kill");
	$cron_pid=null;
	if(is_file("/var/run/cron.pid")){$cron_pid=$unix->get_pid_from_file("/var/run/cron.pid");}
	if(!$unix->process_exists($cron_pid)){$cron_pid=0;}
	if(!is_numeric($cron_pid) OR $cron_pid<5){$cron_pid=$unix->PIDOF("$cron_path");}
	if($cron_pid>5){
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." artica-postfix reloading $cron_path [$cron_pid]...\n";}
		unix_system_HUP("$cron_pid");
	}
	build_progress_schedules("{building}", 90);
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." artica-postfix reloading fcron...\n";}
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup /etc/init.d/artica-postfix restart fcron >/dev/null 2>&1 &");
	shell_exec("/etc/init.d/cron reload");
	build_progress_schedules("{done}", 100);
	
}

function WriteMyLogs($text,$function=null,$file=null,$line=0){
	if(!isset($GLOBALS["MYPID"])){$GLOBALS["MYPID"]=getmypid();}
	$sourcefunction=null;
	$sourceline=0;
	
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}
		
	}	
	$file=basename(__FILE__);
	if($function==null){$function=$sourcefunction;}
	if($line==0){$line=$sourceline;}
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	$GLOBALS["CLASS_UNIX"]->events($text,"/var/log/squid.watchdog.log",false,$sourcefunction,$sourceline);
}

function squid_reconfigure_build_tool(){
	$unix=new unix();
	$squidbin=$unix->find_program("squid3");
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid");}	
	$php5=$unix->find_program("php5");
	$f[]="#! /bin/sh";
	$f[]="echo \"Reconfiguring proxy, please wait\"";
	$f[]="$php5 ".__FILE__." --build \$1";
	$f[]="exit 0";
	@file_put_contents("/bin/squidreconf", @implode("\n", $f));
	@chmod("/bin/squidreconf",0755);
}

function bandwithdebug(){
	$GLOBALS["VERBOSE"]=true;
	ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	$ban=new squid_bandwith_builder();
	echo $ban->compile();
}

function output_acls(){
	$q=new squidbee();
	$acls=new squid_acls_groups();
	$external_acls_squid=new external_acls_squid();
	$squid=new squidbee();
	echo "\nexternal_acl_helper_categories\n-----------------\n";
	echo $external_acls_squid->external_acl_helper_categories();
	echo "\n-----------------\n";
	
	
	
	$sock=new sockets();
	$refreshpattern=$squid->refresh_pattern_list();
	$SquidBubbleMode=$sock->GET_INFO("SquidBubbleMode");
	if(!is_numeric($SquidBubbleMode)){$SquidBubbleMode=0;}
	$acl=new squid_acls_quotas_time();
	$squid_acls_quotas_time= $acl->build()."\n";
	$acls=new squid_acls();
	$acls->Build_Acls();
	$aclgroups=new squid_acls_groups();
	
	if(count($acls->acls_array)>0){
		$ACLS_TO_ADD=@implode("\n",$acls->acls_array);
	}
	echo "\nAcls\n-----------------\n".$ACLS_TO_ADD."\n-----------------\n\n";
	echo "\nQuotas Time\n-----------------\n".$squid_acls_quotas_time."\n-----------------\n\n";
	echo "\n\n-----------------\n".$aclgroups->buildacls_order(0)."\n-----------------\n\n";
	
	echo "######\n";
	$tcp_outgoing_address=$aclgroups->buildacls_bytype("tcp_outgoing_address");
	
	echo "\n\ntcp_outgoing_address -----------------\n";
	if(count($tcp_outgoing_address)>0){
		echo "Starting......: ".date("H:i:s")." [ACLS]: Engine tcp_outgoing_address ".count($tcp_outgoing_address)." rules..\n";
		while (list ($index, $line) = each ($tcp_outgoing_address) ){
			
			echo "tcp_outgoing_address $line\n";}
	}else{
		echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine tcp_outgoing_address No rules..\n";
	}
	echo "\n-----------------\n\n";
	echo "######\n";
	
	$q=new mysql_squid_builder();
		
	if($SquidBubbleMode==1){
		
		$sql="SELECT * FROM webfilters_sqaclsports ORDER BY aclport";
		$results = $q->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {
			echo "\n\n#--------- ACLS {$ligne["portname"]}\n\n";
			echo "\n\n".$aclgroups->buildacls_order($ligne["aclport"])."\n\n";
		}
	}	
	
	$bandwith=new squid_bandwith_builder();
	echo "\n-----------------\n\n";
	echo $bandwith->compile()."\n";
	
	
	$acls_rules=$acls->build_http_access(0);
	echo "\n\n# Builded acls from engine [".count($acls_rules)."] items.\n";
	if(count($acls_rules)>0){
		echo "\n\n".@implode("\n", $acls_rules)."\n";
	}	
	if($SquidBubbleMode==1){
		$sql="SELECT * FROM webfilters_sqaclsports ORDER BY aclport";
		$results = $q->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {
			$acls_rules=$acls->build_http_access($ligne["aclport"]);
			echo "\n\n# Builded acls from engine {$ligne["portname"]} [".count($acls_rules)."] items.\n";
			if(count($acls_rules)>0){
				echo "\n\n".@implode("\n", $acls_rules)."\n";
			}
		}		
		
	}
	
	
	echo "\n\n-----------------\n".$refreshpattern."\n-----------------\n\n";
	
	$acls=new squid_acls_groups();
	
	$acls_allow=$acls->buildacls_bytype("url_rewrite_access_allow");
	if(count($acls_allow)>0){
		while (list ($index, $line) = each ($acls_deny) ){
			$MyStandPort="MyStandPort";
			echo "url_rewrite_access allow $line\n";
		}
		}
	$acls_deny=$acls->buildacls_bytype("url_rewrite_access_deny");
	if(count($acls_deny)>0){
		while (list ($index, $line) = each ($acls_deny) ){
			$MyStandPort="MyStandPort";
			echo "url_rewrite_access deny $line\n";
		}
	}
	
	echo "######\n";
	echo "######\n";
	echo "######\n";

		


}
	


function import_webfilter($filename){
	if(!is_file($filename)){echo "$filename no such file\n";return;}
	$unix=new unix();	
	$ext=Get_extension($filename);
	if($ext<>"gz"){
		echo "$filename not a compressed file\n";
		return;
	}

	$destinationfile=$unix->FILE_TEMP();
	$sqlsourcefile=$unix->FILE_TEMP().".sql";
	if(!$unix->uncompress($filename, $destinationfile)){
		echo "$filename corrupted GZ file...\n";
		;return;
	}

	$contentArray=unserialize(base64_decode(@file_get_contents($destinationfile)));
	if(!is_array($contentArray)){
		echo "$filename corrupted file not an array...\n";
		return;		
	}
	print_r($contentArray);
	@file_put_contents($sqlsourcefile, $contentArray["SQL"]);
	$sock=new sockets();
	echo "Saving default rule...\n";
	$sock->SaveClusterConfigFile($contentArray["DansGuardianDefaultMainRule"], "DansGuardianDefaultMainRule");
	$mysqlbin=$unix->find_program("mysql");
	$q=new mysql_squid_builder();
	$password=null;
	$localdatabase="squidlogs";
	$q=new mysql_squid_builder();
	$cmdline="$mysqlbin --batch --force $q->MYSQL_CMDLINES";
	$cmd="$cmdline --database=$localdatabase <$sqlsourcefile 2>&1";
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
	exec($cmd,$results);
	while (list ($key, $value) = each ($results)){
		echo "$value\n";
		
	}
	
}


function import_acls($filename){
	if(!is_file($filename)){echo "$filename no such file\n";return;}
	$unix=new unix();
	
	$ext=Get_extension($filename);
	if($ext=="acl"){
		import_acls_extacl($filename,null,0);
		return;
	}
	
	$destinationfile=$unix->FILE_TEMP();
	if(!$unix->uncompress($filename, $destinationfile)){
		echo "$filename corrupted GZ file...\n";
		;return;
	}
	
	$mysqlbin=$unix->find_program("mysql");
	$q=new mysql_squid_builder();
	$password=null;
	$localdatabase="squidlogs";
	
	
	
	$q=new mysql_squid_builder();
	
	$cmdline="$mysqlbin --batch --force $q->MYSQL_CMDLINES";
	$cmd="$cmdline --database=$localdatabase <$destinationfile 2>&1";
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
	shell_exec($cmd);
	
}

function import_acls_extacl($filename=null,$ARRAY,$aclgpid=0){
	$q=new mysql_squid_builder();
	$acl=new squid_acls_groups();
	if($filename<>null){
		if(is_file($filename)){
			$ARRAY=unserialize(base64_decode(@file_get_contents($filename)));
		}
	}
	
	
	
	if(!is_array($ARRAY)){
		echo "$filename, unable to decode Array()\n";return;
	}
	
	if(!isset($ARRAY["webfilters_sqacls"])){
		echo "$filename, unable to decode webfilters_sqacls (".__LINE__.")\n";
		return;
	}
	
	
	if(!is_array($ARRAY["webfilters_sqacls"])){
		echo "$filename, unable to decode webfilters_sqacls\n";return;
	}
	
	if(isset($ARRAY["webfilters_sqaclaccess"])){
		if(!is_array($ARRAY["webfilters_sqaclaccess"])){
			if(!isset($ARRAY["SUBRULES"])){
				echo "$filename, unable to decode webfilters_sqaclaccess\n";return;
			}
		}
	}	
	
	if(!isset($ARRAY["SUBRULES"])){
		if(!is_array($ARRAY["webfilters_sqgroups"])){
			echo "$filename, unable to decode webfilters_sqgroups\n";return;
		}
	}	

	$keys=array();$values=array();
	while (list ($key, $value) = each ($ARRAY["webfilters_sqacls"])){
		$keys[]="`$key`";
		$values[]="'".mysql_escape_string2($value)."'";
		
	}
	if($aclgpid>0){
		echo "Prepare SUB-ACL Master ACL:$aclgpid\n";
		$keys[]="`aclgpid`";
		$values[]="'$aclgpid'";
	}
	
	$sql="INSERT IGNORE INTO webfilters_sqacls (".@implode(",", $keys).") VALUES (".@implode(",", $values).")";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n$sql\n";return;}
	$ACLID=$q->last_id;
	
	
	echo "*** New ACL $ACLID ***\n";
	
	
	if(isset($ARRAY["SUBRULES"])){
		if(is_array($ARRAY["SUBRULES"])){
			while (list ($index, $arrayrule) = each ($ARRAY["SUBRULES"])){
				if($GLOBALS["VERBOSE"]){echo "import_acls_extacl(null,$arrayrule,$ACLID)\n";}
				import_acls_extacl(null,$arrayrule,$ACLID);
			}
		}
	}
	
	
	
	$keys=array();$values=array();
	if(isset($ARRAY["webfilters_sqaclaccess"])){
		$acl->aclrule_edittype($ACLID, $ARRAY["webfilters_sqaclaccess"]["httpaccess"], $ARRAY["webfilters_sqaclaccess"]["httpaccess_value"]);
		echo "New sqaclaccess for $ACLID {$ARRAY["webfilters_sqaclaccess"]["httpaccess"]}\n";
	}
	
	
	if(isset($ARRAY["webfilters_sqgroups"])){
			while (list ($index, $grouparray) = each ($ARRAY["webfilters_sqgroups"])){
				$GROUP_ARRAY=$grouparray["GROUP"];
				$GROUP_ITEMS=$grouparray["ITEMS"];
				$GROUP_DYN=$grouparray["DYN"];
				$keys=array();$values=array();
				while (list ($key, $value) = each ($GROUP_ARRAY)){
					$keys[]="`$key`";
					$values[]="'".mysql_escape_string2($value)."'";
				}
				$sql="INSERT IGNORE INTO webfilters_sqgroups (".@implode(",", $keys).") VALUES (".@implode(",", $values).")";
				$q->QUERY_SQL($sql);
				if(!$q->ok){echo $q->mysql_error."\n$sql\n";return;}
				$GPID=$q->last_id;
				$GROUPSACLS[$GPID]=true;
				
				
				while (list ($index, $itemsArray) = each ($GROUP_ITEMS)){
					$keys=array();$values=array();
				
					while (list ($key, $value) = each ($itemsArray)){
						$keys[]="`$key`";
						$values[]="'".mysql_escape_string2($value)."'";
					}
					$keys[]="`gpid`";
					$values[]="$GPID";
					$sql="INSERT IGNORE INTO webfilters_sqitems (".@implode(",", $keys).") VALUES (".@implode(",", $values).")";
					$q->QUERY_SQL($sql);
					if(!$q->ok){echo $q->mysql_error."\n$sql\n";return;}
					
				}
				
				if(count($GROUP_DYN)>0){
					$keys=array();$values=array();
					while (list ($key, $value) = each ($GROUP_DYN)){
						$keys[]="`$key`";
						$values[]="'".mysql_escape_string2($value)."'";
					}
					
					$keys[]="`gpid`";
					$values[]="$GPID";
					$sql="INSERT IGNORE INTO webfilter_aclsdynamic (".@implode(",", $keys).") VALUES (".@implode(",", $values).")";
					$q->QUERY_SQL($sql);
					if(!$q->ok){echo $q->mysql_error."\n$sql\n";return;}					
				}
			}
			
			
			while (list ($gpid, $value) = each ($GROUPSACLS)){
				echo "Linking ACL $ACLID with group $gpid\n";
				$md5=md5($ACLID.$gpid);
				$sql="INSERT IGNORE INTO webfilters_sqacllinks (zmd5,aclid,gpid) VALUES('$md5','$ACLID','$gpid')";
				$q->QUERY_SQL($sql);
			}
		
	}
	
	
	
}
function test_sarg(){
	$sock=new sockets();
	$EnableSargGenerator=$sock->GET_INFO("EnableSargGenerator");
	if(!is_numeric($EnableSargGenerator)){$EnableSargGenerator=0;}
	$unix=new unix();
	
	
	$SARGOK=false;
	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	while (list ($gpid, $line) = each ($f)){
		if(preg_match("#\/sarg\.log#", $line)){$SARGOK=true;break;}
		
	}
	
	$php=$unix->LOCATE_PHP5_BIN();
	
	if(!$SARGOK){
		if($EnableSargGenerator==0){return;}
		shell_exec("$php ".__FILE__." --build --force >/dev/null 2>&1 &");
		return;
	}else{
		if($EnableSargGenerator==1){return;}
		shell_exec("$php ".__FILE__." --build --force >/dev/null 2>&1 &");
		return;		
	}
}

function disableUFDB(){
	$Detected=false;
	$unix=new unix();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	while (list ($index, $line) = each ($f)){
		if(preg_match("#^url_rewrite_program.*?ufdbgclient#", $line)){
			$f[$index]="#$line";
			$Detected=true;
		}
		
	}
	if($Detected){
		@file_put_contents("/etc/squid3/squid.conf", @implode("\n", $f));
		$GLOBALS["FORCE"]=true;
		reload_squid(true);
	}
}

function cache_rules(){
	
	
	
	$q=new squid_caches_rules();
	$q->build();
	echo @implode("\n", $q->final_array);
	
}

function bandwith_rules(){
	
	$bandwith=new squid_bandwith_builder();
	echo "\n-----------------\n\n";
	echo $bandwith->compile()."\n";
}

function purge_dns(){
	$unix=new unix();
	
	$pidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	
	if(!$GLOBALS["VERBOSE"]){
		
		$pid=$unix->get_pid_from_file($pidFile);
		if($unix->process_exists($pid,__FILE__)){return false;}
	}
	
	@file_put_contents($pidFile, getmypid());	
	
	
	$PP=$unix->squid_internal_port();
	$squidclient=$unix->find_program("squidclient");
	$cmdrix="$squidclient -l 127.0.0.1 -T 5 -h 127.0.0.1 -p $PP -m PURGE ";
	
	$data=$unix->squidclient("ipcache");
	$f=explode("\n",$data);
	while (list ($index, $line) = each ($f)){
		if(!preg_match("#^(.+?)\s+.*?\(#", $line,$re)){continue;}
		if($GLOBALS["VERBOSE"]){echo "Matches {$re[1]}\n";}
		$re[1]=trim($re[1]);
		$cmd="$cmdrix http://{$re[1]}";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		shell_exec($cmd);
		$cmd="$cmdrix https://{$re[1]}";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		shell_exec($cmd);
	}
	
	
	
}

function test_caches(){
	$GLOBALS["VERBOSE"]=true;
	$squid=new squidbee();
	echo $squid->cache_dir_method_0();
}

function quick_bann(){
	$unix=new unix();
	$EXEC_PID_FILE="/etc/artica-postfix/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();
	
	$pid=$unix->get_pid_from_file($EXEC_PID_FILE);
	if(!$GLOBALS["VERBOSE"]){
		if($unix->process_exists($pid,basename(__FILE__))){ return; }
	}
	@file_put_contents($EXEC_PID_FILE, getmypid());
	
	$squid=new squidbee();
	$returned=$squid->ACL_BANNED_COMPUTERS_IP();
	
	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	$compile=false;
	
	if(isset($GLOBALS["HTTP_ACCESS"]["BANNED_COMPUTERS_MAC"])){
		if($GLOBALS["VERBOSE"]){echo "MAC Blacklisted detected\n";}
		$MAC=false;
		while (list ($index, $line) = each ($f)){
			if(preg_match("#deny.*?banned_mac_computers#", $line)){ if($GLOBALS["VERBOSE"]){echo "$line FOUND\n";} $MAC=true; break;}
		}
		
		if(!$MAC){$compile=true;}
		
	}
	if(isset($GLOBALS["HTTP_ACCESS"]["BANNED_COMPUTERS"])){
		if($GLOBALS["VERBOSE"]){echo "IP Blacklisted detected\n";}
		$MAC=false;
		reset($f);
		while (list ($index, $line) = each ($f)){
			if(preg_match("#deny.*?banned_computers#", $line)){ if($GLOBALS["VERBOSE"]){echo "$line FOUND\n";} $MAC=true; break;}
		}
	
		if(!$MAC){$compile=true;}
	
	}	
	if(!$compile){
		if($GLOBALS["VERBOSE"]){echo "Just reload ok\n";}
		Reload_Squid();return;}
		
		if($GLOBALS["VERBOSE"]){echo "Just reconfigure ok\n";}
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $php ".__FILE__." --build >/dev/null 2>&1 &");
	
	
}

function BuildSquidCertificate(){
	$squid=new squidbee();
	$squid->BuildSquidCertificate();
	
}

function PortsConversion(){
	
	$sock=new sockets();
	$IsPortsConverted=intval($sock->GET_INFO("IsPortsConverted"));
	if($IsPortsConverted==1){return true;}
	
	$EnableKerbAuth=intval($sock->GET_INFO("EnableKerbAuth"));
	$squid=new squidbee();
	if(!is_numeric($squid->second_listen_port)){$squid->second_listen_port=0;}
	if(!is_numeric($squid->ssl_port)){$squid->ssl_port=0;}
	$SquidBinIpaddr=$sock->GET_INFO("SquidBinIpaddr");
	if($SquidBinIpaddr==null){$SquidBinIpaddr="0.0.0.0";}
	$q=new mysql_squid_builder();
	
	$HTTP_PORT=$squid->listen_port;
	
	$q=new mysql_squid_builder();
	
	if(!$q->TABLE_EXISTS("proxy_ports")){
		$sql="CREATE TABLE IF NOT EXISTS `proxy_ports` (
			`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`PortName` VARCHAR(128) NULL,
			`zMD5` VARCHAR(90) NOT NULL,
			`xnote` TEXT NULL ,
			`Params` TEXT NULL ,
			`TProxy` smallint(1) NOT NULL ,
			`ipaddr` VARCHAR(128) NOT NULL,
			`AuthForced` smallint(1) NOT NULL,
			`AuthPort` smallint(1) NOT NULL,
			`port` INT NOT NULL,
			`transparent` smallint(1) NOT NULL DEFAULT '0' ,
			`enabled` smallint(1) NOT NULL DEFAULT '1' ,
			 KEY `ipaddr` (`ipaddr`),
			 KEY `TProxy` (`TProxy`),
			 KEY `AuthForced` (`AuthForced`),
			 KEY `AuthPort` (`AuthPort`),
			 KEY `enabled` (`enabled`),
			 KEY `port` (`port`)
			)  ENGINE = MYISAM AUTO_INCREMENT = 20;";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error."\n";return;}
		
	}
	
	
	
	if(!$q->FIELD_EXISTS("proxy_ports", "PortName")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `PortName` VARCHAR(128) NULL");
		if(!$q->ok){echo $q->mysql_error."\n";return;}
	}
	
	if(!$q->FIELD_EXISTS("proxy_ports", "AuthForced")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `AuthForced` smallint(1) NOT NULL DEFAULT 0");
		if(!$q->ok){echo $q->mysql_error."\n";return;}
	}	
	
	if(!$q->FIELD_EXISTS("proxy_ports", "AuthPort")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `AuthPort` smallint(1) NOT NULL DEFAULT 0");
		if(!$q->ok){echo $q->mysql_error."\n";return;}
	}	
	
	if($squid->hasProxyTransparent==1){
		$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT ID FROM proxy_ports WHERE ipaddr='$SquidBinIpaddr' AND port='$squid->listen_port'"));
		if($ligne["ID"]==0){
			if(intval($squid->listen_port)>80){
				$PortName="{main_port}: Main transparent port $SquidBinIpaddr:$squid->listen_port";
				$sql="INSERT IGNORE INTO proxy_ports (PortName,ipaddr,port,enabled,transparent) VALUES ('$PortName','$SquidBinIpaddr','$squid->listen_port',1,1)";
				$q->QUERY_SQL($sql);
				if(!$q->ok){echo $q->mysql_error."\n";return;}
			}
			
			if(intval($squid->second_listen_port)>80){
				$PortName="Main connected port $SquidBinIpaddr:$squid->second_listen_port";
				$sql="INSERT IGNORE INTO proxy_ports (PortName,ipaddr,port,enabled,transparent,AuthPort) VALUES ('$PortName','$SquidBinIpaddr','$squid->second_listen_port',1,0,$EnableKerbAuth)";
				$q->QUERY_SQL($sql);
				if(!$q->ok){echo $q->mysql_error."\n";return;}
			}
				$sock->SET_INFO("IsPortsConverted", 1);
				return true;
		}
		$sock->SET_INFO("IsPortsConverted", 1);
		return true;
	}	
	
	$PortName="{main_port}: Main connected port $SquidBinIpaddr:$squid->listen_port";
	
	if(intval($squid->listen_port)>80){
		$sql="INSERT IGNORE INTO proxy_ports (PortName,ipaddr,port,enabled,AuthPort) VALUES ('$PortName','$SquidBinIpaddr','$squid->listen_port',1,$EnableKerbAuth)";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error."\n";return;}
	}
	
	
	
	$sock->SET_INFO("IsPortsConverted", 1);
	return true;
	
	
	
}

function dump_ports(){
	$q=new mysql_squid_builder();
	$GLOBALS["DUMP_CLASS_VERBOSE"]=true;
	$q->dump_table_to_file("proxy_ports", "/tmp/proxy_ports.txt","uuid","VARCHAR(90)","myUiid");
	
}
function dump_ntlm(){
	$squid=new squidbee();
	echo $squid->ntlm_auth_conf();
}

?>