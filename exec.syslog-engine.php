<?php
if(is_file("/usr/bin/cgclassify")){if(is_dir("/cgroups/blkio/php")){shell_exec("/usr/bin/cgclassify -g cpu,cpuset,blkio:php ".getmypid());}}
if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }
$GLOBALS["VERBOSE"]=false;
$GLOBALS["DEBUG"]=false;;
$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){
	$GLOBALS["DEBUG_SQL"]=true;
	$GLOBALS["VERBOSE"]=true;
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}

$BASEDIR="/usr/share/artica-postfix";

include_once($BASEDIR . '/ressources/class.users.menus.inc');
include_once($BASEDIR . '/ressources/class.sockets.inc');
include_once($BASEDIR . '/framework/class.unix.inc');
include_once($BASEDIR. '/framework/frame.class.inc');
include_once($BASEDIR. '/ressources/class.iptables-chains.inc');
include_once($BASEDIR . '/ressources/class.mysql.haproxy.builder.php');
include_once($BASEDIR . "/ressources/class.mysql.squid.builder.php");
include_once($BASEDIR. "/ressources/class.mysql.builder.inc");
include_once($BASEDIR . "/ressources/class.mysql.syslogs.inc");
if(preg_match("#--norestart#",implode(" ",$argv))){$GLOBALS["NORESTART"]=true;}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--syslogmini#",implode(" ",$argv))){$GLOBALS["SYSLOGMINI"]=true;}

$unix=new unix();
$pids=$unix->PIDOF_PATTERN_ALL(basename(__FILE__));
if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." instances:".count($pids)."\n";}
if(count($pids)>3){
	echo "Starting......: ".date("H:i:s")." Too many instances ". count($pids)." starting squid, kill them!\n";
	$mypid=getmypid();
	while (list ($pid, $ligne) = each ($pids) ){
		if($pid==$mypid){continue;}
		echo "Starting......: ".date("H:i:s")." killing $pid\n";
		unix_system_kill_force($pid);
	}

}

$pids=$unix->PIDOF_PATTERN_ALL(basename(__FILE__));
if(count($pids)>3){
	echo "Starting......: ".date("H:i:s")." Too many instances ". count($pids)." dying\n";
	die();
}

if($GLOBALS["VERBOSE"]){echo __LINE__." TRUE\n";}


if(is_file("/etc/artica-postfix/FROM_ISO")){
	if($unix->file_time_min("/etc/artica-postfix/FROM_ISO")<1){
		if($GLOBALS["VERBOSE"]){echo __LINE__." FALSE\n";}
		return;
	}
}

if($argv[1]=='--seeker'){$GLOBALS["VERBOSE"]=true;seeker();die();}
if($argv[1]=='--sysalerts'){$GLOBALS["VERBOSE"]=true;sys_alerts();die();}
if($argv[1]=='--purge'){$GLOBALS["VERBOSE"]=true;clean_mysql_events();die();}
if($argv[1]=='--imap-bw'){blackwhite_admin_mysql_check(true);die();}
if($argv[1]=='--rotate'){squid_notifications();die();}
if($argv[1]=='--updtev'){udfbguard_update_events();die();}
if($argv[1]=='--rsylogd'){rsyslog_check_includes();die();}
//exec.syslog-engine.php --rsylogd

if($argv[1]=='--sysev'){sysev();die();}
if($argv[1]=='--admin-evs'){scan_queue();die();}
if($argv[1]=='--squid-rt-failed'){squid_rt_mysql_failed();die();}
if($argv[1]=='--loadavg'){loadavg_logs();sys_alerts();clamd_mem();crossroads();system_admin_events_checks();system_rotate_events_checks();die();}
if($argv[1]=='--buildconf'){rsyslog_check_includes();exit;}
if($argv[1]=='--apache'){apache_admin_mysql_check();exit;}


if(!$GLOBALS["FORCE"]){
	if(system_is_overloaded(basename(__FILE__))){
		system_admin_events("OVERLOADED system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting",__FUNCTION__,__FILE__,__LINE__,"system");
		system_admin_events("OVERLOADED system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting",__FUNCTION__,__FILE__,__LINE__,"system");
		die();
	}
}
if($GLOBALS["VERBOSE"]){echo __LINE__." TRUE\n";}
if(!isset($argv[1])){$argv[1]="--auth-logs";}

if($argv[1]=='--restart-syslog'){restart_syslog();die();}
if($argv[1]=='--build-server'){build_server_mode();die();}
if($argv[1]=='--build-client'){build_client_mode();die();}
if($argv[1]=='--haproxy'){haproxy_events();die();}
if($argv[1]=='--squid-notifs'){squid_admin_notifs_check();die();}
if($argv[1]=='--squid-mysql'){squid_notifications();die();}
if($argv[1]=='--load-stats'){load_stats();die();}



if($argv[1]=='--auth-logs'){
		$TimeFile="/etc/artica-postfix/pids/exec.syslog-engine.auth.time";
		$unix=new unix();
		$TimExec=$unix->file_time_min($TimeFile);
		if($TimExec<5){die();}
		@unlink($TimeFile);
		@file_put_contents($TimeFile, time());
		if(system_is_overloaded(basename(__FILE__))){system_admin_events("OVERLOADED system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting",__FUNCTION__,__FILE__,__LINE__,"system");die();}
		authlogs();
		if(system_is_overloaded(basename(__FILE__))){system_admin_events("OVERLOADED system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting",__FUNCTION__,__FILE__,__LINE__,"system");die();}
		sessions_logs();
		if(system_is_overloaded(basename(__FILE__))){system_admin_events("OVERLOADED system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting",__FUNCTION__,__FILE__,__LINE__,"system");die();}
		
		if(system_is_overloaded(basename(__FILE__))){system_admin_events("OVERLOADED system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting",__FUNCTION__,__FILE__,__LINE__,"system");die();}
		clamd_mem();
		if(system_is_overloaded(basename(__FILE__))){system_admin_events("OVERLOADED system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting",__FUNCTION__,__FILE__,__LINE__,"system");die();}
		admin_logs();
		if(system_is_overloaded(basename(__FILE__))){system_admin_events("OVERLOADED system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting",__FUNCTION__,__FILE__,__LINE__,"system");die();}
		crossroads();
		if(system_is_overloaded(basename(__FILE__))){system_admin_events("OVERLOADED system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting",__FUNCTION__,__FILE__,__LINE__,"system");die();}
		udfbguard_admin_events();
		if(system_is_overloaded(basename(__FILE__))){system_admin_events("OVERLOADED system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting",__FUNCTION__,__FILE__,__LINE__,"system");die();}
		dhcpd_logs();
		if(system_is_overloaded(basename(__FILE__))){system_admin_events("OVERLOADED system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting",__FUNCTION__,__FILE__,__LINE__,"system");die();}
		scan_queue();
		if(system_is_overloaded(basename(__FILE__))){system_admin_events("OVERLOADED system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting",__FUNCTION__,__FILE__,__LINE__,"system");die();}
		sys_alerts();
		seeker();
		loadavg_logs();
		system_admin_events_checks();
		system_rotate_events_checks();
		postfix_admin_mysql_check();
		die();
}
if($argv[1]=='--authfw'){authfw();sessions_logs();die();system_admin_events_checks();}
if($argv[1]=='--authfw-compile'){compile_sshd_rules();sessions_logs();system_admin_events_checks();die();}
if($argv[1]=='--snort'){snort_logs();sessions_logs();clamd_mem();crossroads();udfbguard_admin_events();system_admin_events_checks();die();}
if($argv[1]=='--sessions'){sessions_logs();system_admin_events();system_rotate_events_checks();die();}

if($argv[1]=='--;'){scan_queue(true);die();}
if($argv[1]=='--adminlogs'){admin_logs();crossroads();udfbguard_admin_events();dhcpd_logs();die();}
if($argv[1]=='--psmem'){ps_mem(true);crossroads();dhcpd_logs();die();}
if($argv[1]=='--squid-tasks'){die();}
if($argv[1]=='--update-events-check'){sarg_admin_events_checks(true);update_events_check(true);system_admin_events_checks();squid_admin_notifs_check();system_rotate_events_checks();die();}
if($argv[1]=='--squidsys'){build_local6_squid();die();}
if($argv[1]=='--localx'){build_localx_servers();die();}


if($GLOBALS["VERBOSE"]){echo __LINE__." TRUE\n";}
$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".time";
$unix=new unix();
$pid=$unix->get_pid_from_file($pidfile);
if($unix->process_exists($pid,basename(__FILE__))){
	ssh_events("Already PID $pid exists, aborting" , "MAIN", __FILE__, __LINE__);
	die();
}

if(!$GLOBALS["VERBOSE"]){
	$time=$unix->file_time_min($timefile);
	if($time<5){die();}
}

@file_put_contents($pidfile, getmypid());
@unlink($timefile);
@file_put_contents($timefile, time());

if($GLOBALS["VERBOSE"]){echo "MAIN::".__LINE__." ->udfbguard_admin_events()\n";}
udfbguard_admin_events();
if($GLOBALS["VERBOSE"]){echo "MAIN::".__LINE__." ->admin_logs()\n";}
admin_logs();
if(system_is_overloaded(basename(__FILE__))){system_admin_events("OVERLOADED system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting",__FUNCTION__,__FILE__,__LINE__,"system");die();}
if($GLOBALS["VERBOSE"]){echo "MAIN::".__LINE__." ->ps_mem()\n";}
ps_mem(true);
if($GLOBALS["VERBOSE"]){echo "MAIN::".__LINE__." ->authlogs()\n";}
authlogs();
if(system_is_overloaded(basename(__FILE__))){system_admin_events("OVERLOADED system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting",__FUNCTION__,__FILE__,__LINE__,"system");die();}
if($GLOBALS["VERBOSE"]){echo "MAIN::".__LINE__." ->sessions_logs()\n";}
sessions_logs();
if(system_is_overloaded(basename(__FILE__))){system_admin_events("OVERLOADED system: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting",__FUNCTION__,__FILE__,__LINE__,"system");die();}
if($GLOBALS["VERBOSE"]){echo "MAIN::".__LINE__." ->clamd_mem()\n";}
clamd_mem();
if($GLOBALS["VERBOSE"]){echo "MAIN::".__LINE__." ->crossroads()\n";}
crossroads();
if($GLOBALS["VERBOSE"]){echo "MAIN::".__LINE__." ->dhcpd_logs()\n";}
dhcpd_logs();
if($GLOBALS["VERBOSE"]){echo __LINE__." TRUE\n";}
$EnableArticaMetaClient=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaMetaClient"));
if($GLOBALS["VERBOSE"]){echo __LINE__." TRUE\n";}
if($GLOBALS["VERBOSE"]){echo "MAIN::".__LINE__." ->mysql_admin_events_check()\n";}
mysql_admin_events_check();
if($GLOBALS["VERBOSE"]){echo "MAIN::".__LINE__." ->meta_admin_mysql_check()\n";}
meta_admin_mysql_check();
mysql_admin_mysql_check();
system_admin_mysql_check();
nginx_admin_mysql_check();
seeker();
if($EnableArticaMetaClient==0){meta_client_clean_logs();exit;}

if($GLOBALS["VERBOSE"]){echo "MAIN::".__LINE__." ->END\n";}
die();


function squid_notifications(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}
	@file_put_contents($pidfile, getmypid());
	rotate_admin_events_checks();
	squid_admin_mysql_check();
	nginx_admin_mysql_check();
	mysql_admin_mysql_check();
	system_admin_mysql_check();
	meta_admin_mysql_check();
	rdpproxy_admin_mysql_check();
	sarg_admin_events_checks();	
	udfbguard_admin_events();
	seeker();
	
}


function sysev(){
	
		$unix=new unix();
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}

		
		$t=0;		
		system_admin_events_checks();
		system_rotate_events_checks();
		udfbguard_admin_events();
		mysql_admin_mysql_check();
		nginx_admin_mysql_check();
		system_admin_mysql_check();
		postfix_admin_mysql_check();
		sys_load();
		scan_queue();
	
}


function build_server_mode(){
	$sock=new sockets();
	$ActAsASyslogServer=$sock->GET_INFO("ActAsASyslogServer");
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(is_file("/etc/artica-postfix/WEBSTATS_APPLIANCE")){$ActAsASyslogServer=1;$EnableWebProxyStatsAppliance=1;$sock->SET_INFO("ActAsASyslogServer", 1);$sock->SET_INFO("EnableWebProxyStatsAppliance", 1);}
	if(is_file("/etc/artica-postfix/STATS_APPLIANCE")){$ActAsASyslogServer=1;}
	
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($EnableWebProxyStatsAppliance==1){
		$ActAsASyslogServer=1;
		$sock->SET_INFO("ActAsASyslogServer", 1);
	}
	
	if(!is_numeric($ActAsASyslogServer)){
		echo "Starting......: ".date("H:i:s")." syslog server parameters not defined, aborting tasks\n";
		return;
	}
	
	
	
	if(is_file("/etc/default/syslogd")){
		echo "Starting......: ".date("H:i:s")." syslog old syslog mode\n";
		build_server_mode_debian();
		if($GLOBALS["NORESTART"]){return;}
		shell_exec("/etc/init.d/auth-tail restart");
		return;
	}
	
	if(is_dir("/etc/rsyslog.d")){
		echo "Starting......: ".date("H:i:s")." syslog rsyslog mode\n";
		build_server_mode_ubuntu();
		if($GLOBALS["NORESTART"]){return;}
		shell_exec("/etc/init.d/auth-tail restart");
	}
}

function build_stats_appliance(){
	rsyslog_check_includes();
	build_server_mode_debian();
	
}


function build_local6_squid(){
		$sock=new sockets();
		$array=unserialize(base64_decode($sock->GET_INFO("SquidSyslogAdd")));
		if(!isset($array["ENABLE"])){return;}
		if(!is_numeric($array["ENABLE"])){$array["ENABLE"]==0;}
		if($array["ENABLE"]==0){
			if(is_file("/etc/rsyslog.d/artica-client-local6.conf")){
				@unlink("/etc/rsyslog.d/artica-client-local6.conf");
				restart_syslog();
			}
			
			return;
		}
		if($array["SERVER"]==null){return;}	
		
	if(is_file("/etc/rsyslog.conf")){
		echo "Starting......: ".date("H:i:s")." syslog client rsyslog mode\n";
		rsyslog_check_includes();
		build_local6_rsyslogd($array["SERVER"]);
		
		restart_syslog();
		return;
	}	

	echo "Starting......: ".date("H:i:s")." syslog client /etc/rsyslog.conf no such file !!\n";

}

function rsyslog_check_includes(){
	if(!is_file("/etc/rsyslog.conf")){return;}
	$imudp="#";
	$unix=new unix();
	
	$haproxy=$unix->find_program("haproxy");
	if(is_file($haproxy)){
		$imudp=null;
		
	}
	

	$f[]="#  /etc/rsyslog.conf	Configuration file for rsyslog.";
	$f[]="#";
	$f[]="#			Written by Artica " .date("Y-m-d H:i:s");
	$f[]="#			See ".__FILE__." function ". __FUNCTION__."() line ". __LINE__;
	$f[]="#			For more information see";
	$f[]="#			/usr/share/doc/rsyslog-doc/html/rsyslog_conf.html";
	$f[]="";
	$f[]="";
	$f[]="#################";
	$f[]="#### MODULES ####";
	$f[]="#################";
	$f[]="";
	$f[]="\$ModLoad imuxsock # provides support for local system logging";
	$f[]="\$ModLoad imklog   # provides kernel logging support (previously done by rklogd)";
	$f[]="#\$ModLoad immark  # provides --MARK-- message capability";
	$f[]="";
	$f[]="# provides UDP syslog reception";
	$f[]="$imudp\$ModLoad imudp";
	$f[]="$imudp\$UDPServerRun 514";
	$f[]="\$SystemLogRateLimitInterval 10";
	$f[]="\$SystemLogRateLimitBurst 5000";
	$f[]="";
	$f[]="# provides TCP syslog reception";
	$f[]="#\$ModLoad imtcp";
	$f[]="#\$InputTCPServerRun 514";
	$f[]="";
	$f[]="";
	$f[]="###########################";
	$f[]="#### GLOBAL DIRECTIVES ####";
	$f[]="###########################";
	$f[]="";
	$f[]="#";
	$f[]="# Use traditional timestamp format.";
	$f[]="# To enable high precision timestamps, comment out the following line.";
	$f[]="#";
	$f[]="\$ActionFileDefaultTemplate RSYSLOG_TraditionalFileFormat";
	$f[]="";
	$f[]="#";
	$f[]="# Set the default permissions for all log files.";
	$f[]="#";
	$f[]="\$FileOwner root";
	$f[]="\$FileGroup adm";
	$f[]="\$FileCreateMode 0640";
	$f[]="\$DirCreateMode 0755";
	$f[]="\$Umask 0022";
	$f[]="";
	$f[]="#";
	$f[]="# Include all config files in /etc/rsyslog.d/";
	$f[]="#";
	$f[]="\$IncludeConfig /etc/rsyslog.d/*.conf";
	$f[]="";
	$f[]="";
	$f[]="###############";
	$f[]="#### RULES ####";
	$f[]="###############";
	$f[]="";
	$f[]="#";
	$f[]="# First some standard log files.  Log by facility.";
	$f[]="#";
	$f[]="auth,authpriv.*			/var/log/auth.log";
	$f[]="*.*;auth,authpriv.none		-/var/log/syslog";
	$f[]="#cron.*				-/var/log/cron.log";
	$f[]="daemon.*				-/var/log/daemon.log";
	$f[]="kern.=debug 			-/var/log/iptables.log";
	$f[]="kern.*;kern.!=debug	-/var/log/kern.log";
	$f[]="lpr.*					-/var/log/lpr.log";
	$f[]="mail.*				-/var/log/mail.log";
	$f[]="user.*				-/var/log/user.log";
	$f[]="mail.info			-/var/log/mail.info";
	$f[]="mail.warn			-/var/log/mail.warn";
	$f[]="mail.err			/var/log/mail.err";
	$f[]="news.crit			/var/log/news/news.crit";
	$f[]="news.err			/var/log/news/news.err";
	$f[]="news.notice			-/var/log/news/news.notice";

	
	$f[]="";
	$f[]="#";
	$f[]="# Some \"catch-all\" log files.";
	$f[]="#";
	$f[]="*.=debug;\ ";
	$f[]="	auth,authpriv.none;\ ";
	$f[]="	news.none;mail.none	-/var/log/debug";
	$f[]="*.=info;*.=notice;*.=warn;\ ";
	$f[]="	auth,authpriv.none;\ ";
	$f[]="	cron,daemon.none;\ ";
	$f[]="	mail,news.none		-/var/log/messages";
	$f[]="";
	$f[]="#";
	$f[]="# Emergencies are sent to everybody logged in.";
	$f[]="#";
	$f[]="*.emerg				*";
	$f[]="";
	$f[]="#";
	$f[]="# I like to have messages displayed on the console, but only on a virtual";
	$f[]="# console I usually leave idle.";
	$f[]="#";
	$f[]="#daemon,mail.*;\ ";
	$f[]="#	news.=crit;news.=err;news.=notice;\ ";
	$f[]="#	*.=debug;*.=info;\ ";
	$f[]="#	*.=notice;*.=warn	/dev/tty8";
	$f[]="";
	$f[]="# The named pipe /dev/xconsole is for the `xconsole' utility.  To use it,";
	$f[]="# you must invoke `xconsole' with the `-file' option:";
	$f[]="# ";
	$f[]="#    \$ xconsole -file /dev/xconsole [...]";
	$f[]="#";
	$f[]="# NOTE: adjust the list below, or you'll go crazy if you have a reasonably";
	$f[]="#      busy site..";
	$f[]="#";
	$f[]="daemon.*;mail.*;\ ";
	$f[]="	news.err;\ ";
	$f[]="	*.=debug;*.=info;\ ";
	$f[]="	*.=notice;*.=warn	|/dev/xconsole";
	$f[]="";
	@file_put_contents("/etc/rsyslog.conf", @implode("\n", $f)."\n");
	echo "Starting......: ".date("H:i:s")." [INIT]: Sylog daemon Building /etc/rsyslog.conf done\n";
	echo "Starting......: ".date("H:i:s")." [INIT]: Sylog daemon You can restart syslog by typing `".$unix->LOCATE_SYSLOG_INITD()." restart`\n";
	
	
	
}


function build_client_mode(){
	$sock=new sockets();
	$ActAsASyslogClient=$sock->GET_INFO("ActAsASyslogClient");
	$ActAsASyslogSMTPClient=$sock->GET_INFO("ActAsASyslogSMTPClient");
	if(!is_numeric($ActAsASyslogSMTPClient)){$ActAsASyslogSMTPClient=0;}
	if(!is_numeric($ActAsASyslogClient)){$ActAsASyslogClient=0;}
	$EnableRemoteSyslogStatsAppliance=$sock->GET_INFO("EnableRemoteSyslogStatsAppliance");
	if(!is_numeric($EnableRemoteSyslogStatsAppliance)){$EnableRemoteSyslogStatsAppliance=0;}
	if($EnableRemoteSyslogStatsAppliance==1){$ActAsASyslogClient=1;}
	
	if(($ActAsASyslogClient==0) OR ($ActAsASyslogSMTPClient==0)){
		echo "Starting......: ".date("H:i:s")." syslog client parameters not defined, aborting tasks\n";
	}
	
	if(is_file("/etc/default/syslogd")){
		echo "Starting......: ".date("H:i:s")." syslog client old syslog mode\n";
		build_client_mode_debian();
		shell_exec("/etc/init.d/auth-tail restart");
		return;
	}
	
	if(is_dir("/etc/rsyslog.d")){
		echo "Starting......: ".date("H:i:s")." syslog client rsyslog mode\n";
		build_client_mode_ubuntu();
		shell_exec("/etc/init.d/auth-tail restart");
	}
}

function build_client_mode_ubuntu(){
	
	$sock=new sockets();
	$ActAsASyslogServer=$sock->GET_INFO("ActAsASyslogServer");
	$ActAsASyslogClient=$sock->GET_INFO("ActAsASyslogClient");
	$EnableRemoteSyslogStatsAppliance=$sock->GET_INFO("EnableRemoteSyslogStatsAppliance");
	$ActAsASyslogSMTPClient=$sock->GET_INFO("ActAsASyslogSMTPClient");
	if(!is_numeric($EnableRemoteSyslogStatsAppliance)){$EnableRemoteSyslogStatsAppliance=0;}	
	if(!is_numeric($ActAsASyslogClient)){$ActAsASyslogClient=0;}
	if(!is_numeric($ActAsASyslogSMTPClient)){$ActAsASyslogSMTPClient=0;}	
	if($EnableRemoteSyslogStatsAppliance==1){$ActAsASyslogClient=1;}
	
	
	
	$serversList=unserialize(base64_decode($sock->GET_INFO("ActAsASyslogClientServersList")));
	@unlink("/etc/rsyslog.d/artica-client.conf");
	$g[]="";

if($EnableRemoteSyslogStatsAppliance==1){
	$RemoteStatisticsApplianceSettings=unserialize(base64_decode($sock->GET_INFO("RemoteStatisticsApplianceSettings")));
	if(isset($RemoteStatisticsApplianceSettings["SERVER"])){
		$s[]="authpriv.info\t@{$RemoteStatisticsApplianceSettings["SERVER"]}";
	}
	
	$RemoteSyslogAppliance=unserialize(base64_decode($sock->GET_INFO("RemoteSyslogAppliance")));
	if(isset($RemoteSyslogAppliance["SERVER"])){
		$s[]="authpriv.info\t@{$RemoteSyslogAppliance["SERVER"]}";
	}
}

if($ActAsASyslogClient==1){
	if(count($serversList)>0){
		while (list ($num, $server) = each ($serversList) ){
			if($server==null){continue;}
			if(preg_match("#(.+?):([0-9]+)#",$server,$re)){$server=$re[1];}
			echo "Starting......: ".date("H:i:s")." syslog client $server (forced to 514 port)\n";
			$s[]="*.*\t@$server";
		}
	}
}

if($ActAsASyslogSMTPClient==1){
	$serversList=unserialize(base64_decode($sock->GET_INFO("ActAsASyslogClientSMTPList")));
	if(count($serversList)>0){
		while (list ($num, $server) = each ($serversList) ){
			if($server==null){continue;}
			if(preg_match("#(.+?):([0-9]+)#",$server,$re)){$server=$re[1];}
			echo "Starting......: ".date("H:i:s")." syslog mail.* client $server (forced to 514 port)\n";
			$s[]="mail.*\t@$server";
		}
	}	
}


$g[]="";

if(is_array($s)){
	$final=@implode("\n",$s)."\n".@implode("\n",$g);
}else{
	$final=@implode("\n",$g);
}

@file_put_contents("/etc/rsyslog.d/artica-client.conf",$final);
echo "Starting......: ".date("H:i:s")." syslog client /etc/rsyslog.d/artica-client.conf done\n";

@unlink("/etc/rsyslog.d/artica-authpriv.conf");
if(!ParseDirAuthpriv("/etc/rsyslog.d")){
	@file_put_contents("/etc/rsyslog.d/artica-authpriv.conf","authpriv.*			/var/log/auth.log");
}

restart_syslog();	
	
	
}

function ParseDirAuthpriv($dirname){
	foreach (glob("$dirname/*") as $filename) {
		if(CheckAuthAuthpriv($filename)){return true;}
	}
	return false;
	
}


function CheckAuthAuthpriv($filename){
	$f=explode("\n", @file_get_contents($filename));
	while (list ($num, $line) = each ($f) ){
		if(preg_match("#authpriv#", $line)){echo "Starting......: ".date("H:i:s")." syslog client $filename has Authpriv\n";return true;}
	}
	return false;
}

function build_local6_rsyslogd($hostname){
	@unlink("/etc/rsyslog.d/artica-client-local6.conf");
	$f[]="local6.*\t@$hostname\n";
	@file_put_contents("/etc/rsyslog.d/artica-client-local6.conf", @implode("\n", $f));
	
	
}



function build_localx_servers(){
	
	for($i=0;$i<8;$i++){
		@unlink("/etc/rsyslog.d/artica-server-local$i.conf");
		
	}
	
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->GET_INFO("SyslogLocals")));
	while (list ($local, $path) = each ($datas) ){
		if(!preg_match("#\.[a-z]+$#", $path)){
			$path=$path."/$local.log";
		}
		@file_put_contents("/etc/rsyslog.d/artica-server-$local.conf", "$local.*\t-$path\n");
	}
	rsyslog_check_includes();
	restart_syslog();
	
}



function build_client_mode_debian(){
	
	$sock=new sockets();
	$ActAsASyslogServer=$sock->GET_INFO("ActAsASyslogServer");
	$ActAsASyslogClient=$sock->GET_INFO("ActAsASyslogClient");
	$ActAsASyslogSMTPClient=$sock->GET_INFO("ActAsASyslogSMTPClient");
		
	$EnableRemoteSyslogStatsAppliance=$sock->GET_INFO("EnableRemoteSyslogStatsAppliance");
	if(!is_numeric($EnableRemoteSyslogStatsAppliance)){$EnableRemoteSyslogStatsAppliance=0;}	
	if(!is_numeric($ActAsASyslogClient)){$ActAsASyslogClient=0;}
	if(!is_numeric($ActAsASyslogServer)){$ActAsASyslogServer=0;}	
	if(!is_numeric($ActAsASyslogSMTPClient)){$ActAsASyslogSMTPClient=0;}
	if($EnableRemoteSyslogStatsAppliance==1){$ActAsASyslogClient=1;}
	
	
	$serversList=unserialize(base64_decode($sock->GET_INFO("ActAsASyslogClientServersList")));
	$f=explode("\n",@file_get_contents("/etc/syslog.conf"));
	while (list ($num, $line) = each ($f) ){
		if(preg_match("#\.+?\.\.+?\s+@#",$line,$re)){
			$f[$num]=null;
			echo "Starting......: ".date("H:i:s")." syslog client removing $line\n";
		}
	}
	
	reset($f);
	while (list ($num, $line) = each ($f) ){
		if(trim($line)==null){continue;}
		$g[]=$line;
	}
$g[]="";

if($EnableRemoteSyslogStatsAppliance==1){
	$RemoteStatisticsApplianceSettings=unserialize(base64_decode($sock->GET_INFO("RemoteStatisticsApplianceSettings")));
	if(isset($RemoteStatisticsApplianceSettings["SERVER"])){
		$s[]="authpriv.info\t@{$RemoteStatisticsApplianceSettings["SERVER"]}";
	}
	
	$RemoteSyslogAppliance=unserialize(base64_decode($sock->GET_INFO("RemoteSyslogAppliance")));
	if(isset($RemoteSyslogAppliance["SERVER"])){
		$s[]="authpriv.info\t@{$RemoteSyslogAppliance["SERVER"]}";
	}
}

if($ActAsASyslogClient==1){
	if(count($serversList)>0){
		while (list ($num, $server) = each ($serversList) ){
			if($server==null){continue;}
			if(preg_match("#(.+?):([0-9]+)#",$server,$re)){$server=$re[1];}
			echo "Starting......: ".date("H:i:s")." syslog client $server (forced to 514 port)\n";
			$s[]="*.*\t@$server";
		}
	}
}

if($ActAsASyslogSMTPClient==1){
	$serversList=unserialize(base64_decode($sock->GET_INFO("ActAsASyslogClientSMTPList")));
	if(count($serversList)>0){
		while (list ($num, $server) = each ($serversList) ){
			if($server==null){continue;}
			if(preg_match("#(.+?):([0-9]+)#",$server,$re)){$server=$re[1];}
			echo "Starting......: ".date("H:i:s")." syslog mail.* client $server (forced to 514 port)\n";
			$s[]="mail.*\t@$server";
		}
	}	
}




$g[]="";

if(is_array($s)){
	$final=@implode("\n",$s)."\n".@implode("\n",$g);
}else{
	$final=@implode("\n",$g);
}

@file_put_contents("/etc/syslog.conf",$final);
echo "Starting......: ".date("H:i:s")." syslog client /etc/syslog.conf done\n";
restart_syslog();	
	
	
}

function build_server_mode_debian(){
	$sock=new sockets();
	$ActAsASyslogServer=$sock->GET_INFO("ActAsASyslogServer");
	if(is_file("/etc/artica-postfix/WEBSTATS_APPLIANCE")){$ActAsASyslogServer=1;}
	if(is_file("/etc/artica-postfix/STATS_APPLIANCE")){$ActAsASyslogServer=1;$ActAsASyslogClient=0;}	
	
	
	$moinsr=null;
	if($ActAsASyslogServer==1){
		echo "Starting......: ".date("H:i:s")." syslog turn to master syslog server\n";
		$moinsr="-r";
	}
	
	$f[]="";
	$f[]="SYSLOGD=\"$moinsr\"";
	$f[]="";
	@file_put_contents("/etc/default/syslogd",@implode("\n",$f));
	restart_syslog();
}

function build_server_mode_ubuntu(){
	

	
	if(!is_dir("/etc/rsyslog.d")){
		echo "Starting......: ".date("H:i:s")." syslog /etc/rsyslog.d no such directory\n";
		return;
	}
	$sock=new sockets();
	$ActAsASyslogServer=$sock->GET_INFO("ActAsASyslogServer");
	$ActAsASyslogClient=$sock->GET_INFO("ActAsASyslogClient");
	if(!is_numeric($ActAsASyslogClient)){$ActAsASyslogClient=0;}
	if(!is_numeric($ActAsASyslogServer)){$ActAsASyslogServer=0;}
	if(is_file("/etc/artica-postfix/WEBSTATS_APPLIANCE")){$ActAsASyslogServer=1;}
	if(is_file("/etc/artica-postfix/STATS_APPLIANCE")){$ActAsASyslogServer=1;$ActAsASyslogClient=0;}	
	$serversList=array();
	
	if(($ActAsASyslogServer==0) && ($ActAsASyslogClient==0)){
		echo "Starting......: ".date("H:i:s")." syslog Client or server are disabled\n";
		@unlink("/etc/rsyslog.d/artica.conf");
		return;
	}	
	
	$libdir=locate_rsyslog_lib();
	echo "Starting......: ".date("H:i:s")." syslog libdir: $libdir\n";
	if(!is_file("$libdir/imudp.so")){
		echo "Starting......: ".date("H:i:s")." syslog $libdir/imudp.so no such file\n";
		return; 
	}

if($ActAsASyslogServer==1){
	echo "Starting......: ".date("H:i:s")." syslog master mode enabled\n";
}
if($ActAsASyslogClient==1){
	echo "Starting......: ".date("H:i:s")." syslog client mode enabled\n";
	$serversList=unserialize(base64_decode($sock->GET_INFO("ActAsASyslogClientServersList")));
}

if(($ActAsASyslogServer==1) OR ($ActAsASyslogClient=1)){
	echo "Starting......: ".date("H:i:s")." syslog define communications settings\n";
	$f[]="\$WorkDirectory /var/spool/rsyslog # where to place spool files";
	$f[]="\$ActionQueueFileName uniqName # unique name prefix for spool files";
	$f[]="\$ActionQueueMaxDiskSpace 1g   # 1gb space limit (use as much as possible)";
	$f[]="\$ActionQueueSaveOnShutdown on # save messages to disk on shutdown";
	$f[]="\$ActionQueueType LinkedList   # run asynchronously";
	$f[]="\$ActionResumeRetryCount -1    # infinite retries if host is down";
	$f[]="\$ModLoad imudp.so  # provides UDP syslog reception";
	$f[]="";
}


if($ActAsASyslogClient==1){
	if(count($serversList)>0){
		while (list ($num, $server) = each ($serversList) ){
			if($server==null){continue;}
			$f[]="*.*\t@$server";
		}
	}
}

$f[]="";
$f[]="#\$ModLoad imtcp.so  # load module";

if(is_file("$libdir/imklog.so")){
	echo "Starting......: ".date("H:i:s")." syslog set imklog module\n";
	$f[]="\$ModLoad imklog.so  # load module";
}
if(is_file("$libdir/immark.so")){
	echo "Starting......: ".date("H:i:s")." syslog set immark module\n";
	$f[]="\$ModLoad immark.so  # load module";
}
   

if($ActAsASyslogServer==1){
	$f[]="\$UDPServerRun 514 # start a UDP syslog server at standard port 514";
}

//http://www.rsyslog.com/doc/rsyslog_tls.html
$f[]="#\$DefaultNetstreamDriver gtls";
$f[]="#\$DefaultNetstreamDriverCAFile /etc/rsyslog.d/ca.pem";
$f[]="#\$DefaultNetstreamDriverCertFile /etc/rsyslog.d/server_cert.pem";
$f[]="#\$DefaultNetstreamDriverKeyFile /etc/rsyslog.d/server_key.pem";
$f[]="#\$ModLoad imtcp # load TCP listener";
$f[]="#\$InputTCPServerStreamDriverMode 1 # run driver in TLS-only mode";
$f[]="#\$InputTCPServerStreamDriverAuthMode anon # client is NOT authenticated";
$f[]="#\$InputTCPServerRun 10514 # start up listener at port 10514";
$f[]="#\$DefaultNetstreamDriverCAFile /etc/rsyslog.d/ca.pem";
$f[]="#\$DefaultNetstreamDriver gtls # use gtls netstream driver";
$f[]="#\$ActionSendStreamDriverMode 1 # require TLS for the connection";
$f[]="#\$ActionSendStreamDriverAuthMode anon # server is NOT authenticated";
$f[]="#*.* @@(o)server.example.net:10514 # send (all) messages";
$f[]="";


if(is_file("/etc/artica-postfix/STATS_APPLIANCE")){
	if(!is_file("/var/log/squid/squidtail.log")){
		@mkdir("/var/log/squid",0755,true);
		@touch("/var/log/squid/squidtail.log");
	}
	@file_put_contents("/etc/rsyslog.d/stats-appliance.conf","local5.*\t/var/log/squid/squidtail.log");
}



@file_put_contents("/etc/rsyslog.d/artica.conf",@implode("\n",$f));
@file_put_contents("/etc/rsyslog.d/artica-authpriv.conf","auth,authpriv.*			/var/log/auth.log");
restart_syslog();	
}


function restart_syslog(){
	if($GLOBALS["NORESTART"]){return;}
	echo "Starting......: ".date("H:i:s")." syslog restart daemon\n";
	$unix=new unix();
	$sysloginit=$unix->LOCATE_SYSLOG_INITD();
	if(!is_file($sysloginit)){echo "Starting......: ".date("H:i:s")." syslog init.d/*? no such file\n";return;}
	exec("$sysloginit restart 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		if(trim($line)==null){continue;}
		echo "Starting......: ".date("H:i:s")." syslog $line\n";
	}
	
	
	shell_exec("/etc/init.d/artica-syslog restart");
	shell_exec("/etc/init.d/auth-tail restart");
	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	
	if(is_file($squidbin)){
		shell_exec("/etc/init.d/squid reload --script=".basename(__FILE__)." >/dev/null 2>&1");
	}
	$postfix=$unix->find_program("postfix");
	if(is_file($postfix)){shell_exec("$postfix reload >/dev/null 2>&1");}
	
		
}

function locate_rsyslog_lib(){
	if(is_file("/usr/lib/rsyslog/imudp.so")){return "/usr/lib/rsyslog";}
	if(is_file("/usr/lib64/rsyslog/imudp.so")){return "/usr/lib64/rsyslog";}
	if(is_file("/lib/rsyslog/imudp.so")){return "/lib/rsyslog";}
	if(is_file("/lib64/rsyslog/imudp.so")){return "/lib64/rsyslog";}
	
}


function admin_logs(){
	
	if(system_is_overloaded()){return;}
	
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}	
	$t=0;
	include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
	$q=new mysql();
	foreach (glob("{$GLOBALS["ARTICALOGDIR"]}/adminevents/*") as $filename) {
		$sql=@file_get_contents($filename);
		if(trim($sql)==null){@unlink($filename);}
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){writelogs("Fatal, $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		continue;}
		@unlink($filename);
	}
	
	ps_mem();
		
}

function artica_update_task($nopid=false){
	$f=array();
	$unix=new unix();
	if($nopid){
	
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}
		$t=0;
	
	}
	

	$BaseWorkDir="{$GLOBALS["ARTICALOGDIR"]}/artica_update_task";
	if(!is_dir($BaseWorkDir)){return;}
	if (!$handle = opendir($BaseWorkDir)) {return;}
	
	$sock=new sockets();
	$users=new usersMenus();
	$hostname=$unix->hostname_g();
	$q=new mysql();
	if(!$q->test_mysql_connection()){return;}
	
	if(!$q->TABLE_EXISTS("artica_update_task", "artica_events")){
		$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`artica_update_task` (
			`ID` int(11) NOT NULL AUTO_INCREMENT,
			`zDate` TIMESTAMP NOT NULL ,
			`content` MEDIUMTEXT NOT NULL ,
			`subject` VARCHAR( 255 ) NOT NULL ,
			`function` VARCHAR( 60 ) NOT NULL ,
			`filename` VARCHAR( 50 ) NOT NULL ,
			`line` INT( 10 ) NOT NULL ,
			`severity` smallint( 1 ) NOT NULL ,
			`TASKID` BIGINT UNSIGNED ,
			PRIMARY KEY (`ID`),
			  KEY `zDate` (`zDate`),
			  KEY `subject` (`subject`),
			  KEY `function` (`function`),
			  KEY `filename` (`filename`),
			  KEY `severity` (`severity`)
			) ENGINE=MYISAM;";
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){echo $q->mysql_error."\n";return;}
	}
	
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		if($unix->file_time_min($targetFile)>240){@unlink($targetFile);continue;}
		$array=unserialize(@file_get_contents($targetFile));
		if(!is_array($array)){@unlink($targetFile);continue;}
	
		if(!is_numeric($array["TASKID"])){$array["TASKID"]=0;}
		$content=mysql_escape_string2($array["text"]);
		$subject=mysql_escape_string2($array["subject"]);
	
		$zdate=$array["zdate"];
		$function=$array["function"];
		$file=$array["file"];
		$line=$array["line"];
		$TASKID=$array["TASKID"];
		$severity=$array["severity"];
	
		$q->QUERY_SQL("INSERT IGNORE INTO `artica_update_task`
				(`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`) VALUES
				('$zdate','$content','$subject','$function','$file','$line','$severity')","artica_events");
	
		if(!$q->ok){return;}
	
		@unlink($targetFile);
	
	}	
	
}
function checks_stats_admin_events($nopid=false){
	$f=array();
	$unix=new unix();
	if($nopid){
	
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}
		$t=0;
	
	}
	
	$sock=new sockets();
	$users=new usersMenus();
	$hostname=$unix->hostname_g();
	$BaseWorkDir="{$GLOBALS["ARTICALOGDIR"]}/stats_admin_events";
	@mkdir($BaseWorkDir,0755,true);
	if (!$handle = opendir($BaseWorkDir)) {echo "Failed open $BaseWorkDir\n";return;}
	
	
	
	$q=new mysql();
	if(!$q->test_mysql_connection()){return;}
	
	if(!$q->TABLE_EXISTS("stats_admin_events", "artica_events")){
		$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`stats_admin_events` (
			`ID` int(11) NOT NULL AUTO_INCREMENT,
			`zDate` TIMESTAMP NOT NULL ,
			`content` MEDIUMTEXT NOT NULL ,
			`subject` VARCHAR( 255 ) NOT NULL ,
			`function` VARCHAR( 60 ) NOT NULL ,
			`filename` VARCHAR( 50 ) NOT NULL ,
			`line` INT( 10 ) NOT NULL ,
			`severity` smallint( 1 ) NOT NULL ,
			`TASKID` BIGINT UNSIGNED ,
			PRIMARY KEY (`ID`),
			  KEY `zDate` (`zDate`),
			  KEY `subject` (`subject`),
			  KEY `function` (`function`),
			  KEY `filename` (`filename`),
			  KEY `severity` (`severity`)
			) ENGINE=MYISAM;";
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){echo $q->mysql_error."\n";return;}
	}
	
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		if($unix->file_time_min($targetFile)>240){@unlink($targetFile);continue;}
		$array=unserialize(@file_get_contents($targetFile));
		if(!is_array($array)){@unlink($targetFile);continue;}
	
		if(!is_numeric($array["TASKID"])){$array["TASKID"]=0;}
		$content=mysql_escape_string2($array["text"]);
		$subject=mysql_escape_string2($array["subject"]);
	
		$zdate=$array["zdate"];
		$function=$array["function"];
		$file=$array["file"];
		$line=$array["line"];
		$TASKID=$array["TASKID"];
		$severity=$array["severity"];
	
		$q->QUERY_SQL("INSERT IGNORE INTO `stats_admin_events`
				(`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`) VALUES
				('$zdate','$content','$subject','$function','$file','$line','$severity')","artica_events");
	
		if(!$q->ok){return;}
	
		@unlink($targetFile);
	
	}
}


function checks_hotspot_admin_mysql($nopid=false){
	$f=array();
	$unix=new unix();
	if($nopid){

		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}
		$t=0;

	}

	$sock=new sockets();
	$users=new usersMenus();
	$hostname=$unix->hostname_g();
	$BaseWorkDir="{$GLOBALS["ARTICALOGDIR"]}/hotspot_admin_mysql";
	if(!is_dir($BaseWorkDir)){return;}
	if (!$handle = opendir($BaseWorkDir)) {echo "Failed open $BaseWorkDir\n";return;}



	$q=new mysql();
	if(!$q->test_mysql_connection()){return;}

	if(!$q->TABLE_EXISTS("hotspot_admin_mysql", "artica_events")){
		$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`hotspot_admin_mysql` (
			`ID` int(11) NOT NULL AUTO_INCREMENT,
			`zDate` TIMESTAMP NOT NULL ,
			`content` MEDIUMTEXT NOT NULL ,
			`subject` VARCHAR( 255 ) NOT NULL ,
			`function` VARCHAR( 60 ) NOT NULL ,
			`filename` VARCHAR( 50 ) NOT NULL ,
			`line` INT( 10 ) NOT NULL ,
			`severity` smallint( 1 ) NOT NULL ,
			`TASKID` BIGINT UNSIGNED ,
			PRIMARY KEY (`ID`),
			  KEY `zDate` (`zDate`),
			  KEY `subject` (`subject`),
			  KEY `function` (`function`),
			  KEY `filename` (`filename`),
			  KEY `severity` (`severity`)
			) ENGINE=MYISAM;";
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){echo $q->mysql_error."\n";return;}
	}


	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		if($unix->file_time_min($targetFile)>240){@unlink($targetFile);continue;}
		$array=unserialize(@file_get_contents($targetFile));
		if(!is_array($array)){@unlink($targetFile);continue;}

		if(!is_numeric($array["TASKID"])){$array["TASKID"]=0;}
		$content=mysql_escape_string2($array["text"]);
		$subject=mysql_escape_string2($array["subject"]);

		$zdate=$array["zdate"];
		$function=$array["function"];
		$file=$array["file"];
		$line=$array["line"];
		$TASKID=$array["TASKID"];
		$severity=$array["severity"];

		$q->QUERY_SQL("INSERT IGNORE INTO `hotspot_admin_mysql`
				(`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`) VALUES
				('$zdate','$content','$subject','$function','$file','$line','$severity')","artica_events");

		if(!$q->ok){return;}

		@unlink($targetFile);

	}

}

function scan_queue($nopid=false){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$unix=new unix();
	
	if(!$GLOBALS["VERBOSE"]){
		if($nopid){
			$pid=@file_get_contents($pidfile);
			if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}
			$t=0;
		}
		
		$pids=$unix->PIDOF_PATTERN_ALL("exec.syslog-engine.php --admin-evs");
		if(count($pids)>1){
			writelogs("2 instances already runnin.. aborting",__FUNCTION__,__FILE__,__LINE__);
			return;
		}
		
		$TimeExec=$unix->file_time_min($pidTime);
		if($TimeExec<5){ writelogs("Only each 5 mn",__FUNCTION__,__FILE__,__LINE__);return;}
	
	}
	
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	@file_put_contents($pidfile, getmypid());
	blackwhite_admin_mysql_check(true);
	squid_admin_notifs_check(true);
	system_rotate_events_checks(true);
	haproxy_events();
	sys_load();
	cyrus_admin_mysql_check(true);
	apache_admin_mysql_check(true);
	vsftpd_admin_mysql_check(true);
	squid_admin_mysql_check(true);
	squid_admin_enforce_check(true);
	webupdate_admin_mysql_check(true);
	nginx_admin_mysql_check(true);
	system_admin_events_checks(true);
	artica_update_task(true);
	checks_hotspot_admin_mysql(true);
	checks_stats_admin_events(true);
	squid_admin_purge_check(true);
	rotate_admin_events_checks(true);
	udfbguard_admin_events(true);
	sys_alerts(true);
	clean_mysql_events(true);
	seeker();
	
	
	
	
}


function sys_alerts($nopid=false){
	$f=array();
	$unix=new unix();
	if($nopid){
	
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}
		$t=0;
	
	}
	
	$sock=new sockets();
	$users=new usersMenus();
	$hostname=$unix->hostname_g();
	$q=new mysql();
	if(!$q->test_mysql_connection()){return;}
	$uuid=$unix->GetUniqueID();

	$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`sys_alerts` (
	`zmd5` varchar(90) NOT NULL ,
	`zDate` TIMESTAMP NOT NULL ,
	`sended` smallint(1) NOT NULL DEFAULT 0,
	`load` FLOAT NOT NULL ,
	`uuid` VARCHAR( 90 ) NOT NULL ,
	`content` TEXT ,
	PRIMARY KEY (`zmd5`),
	KEY  `zDate` ( `zDate`),
	KEY  `sended` ( `sended`),
	KEY `load` (`load`)

	) ENGINE=MYISAM;";

	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){echo $q->mysql_error;return;}

	$BaseWorkDir="{$GLOBALS["ARTICALOGDIR"]}/sys_alerts";
	if(!is_dir($BaseWorkDir)){return;}
	if (!$handle = opendir($BaseWorkDir)) {echo "Failed open $BaseWorkDir\n";return;}
	

	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		if($unix->file_time_min($targetFile)>1880){@unlink($targetFile);continue;}
		if(!preg_match("#([0-9]+)-([0-9\.]+)#", $filename,$re)){
			@unlink($targetFile);continue;
		}
		
		$time=date("Y-m-d H:i:s",$re[1]);
		$load=$re[2];
		$zmd5=md5("$filename$uuid");
		$content=mysql_escape_string2(@file_get_contents($targetFile));
		$q->QUERY_SQL("INSERT IGNORE INTO sys_alerts (`zmd5`,`zDate`,`sended`,`load`,`uuid`,`content`) 
		VALUES ('$zmd5','$time','0','$load','$uuid','$content')","artica_events");
		if(!$q->ok){echo $q->mysql_error;continue;}
		@unlink($targetFile);
		
		
	}

}


function squid_admin_purge_check($nopid=false){
	$f=array();
	$unix=new unix();
	if($nopid){

		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}
		$t=0;

	}


	$BaseWorkDir="{$GLOBALS["ARTICALOGDIR"]}/squid_admin_purge";
	if(!is_dir($BaseWorkDir)){return;}
	if (!$handle = opendir($BaseWorkDir)) {echo "Failed open $BaseWorkDir\n";return;}
	
	$sock=new sockets();
	$users=new usersMenus();
	$hostname=$unix->hostname_g();
	$q=new mysql();
	
	if(!$q->test_mysql_connection()){return;}

	if(!$q->TABLE_EXISTS("squid_admin_purge", "artica_events")){
		$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`squid_admin_purge` (
			`ID` int(11) NOT NULL AUTO_INCREMENT,
			`zDate` TIMESTAMP NOT NULL ,
			`content` MEDIUMTEXT NOT NULL ,
			`subject` VARCHAR( 255 ) NOT NULL ,
			`function` VARCHAR( 60 ) NOT NULL ,
			`filename` VARCHAR( 50 ) NOT NULL ,
			`line` INT( 10 ) NOT NULL ,
			`severity` smallint( 1 ) NOT NULL ,
			`TASKID` BIGINT UNSIGNED ,
			PRIMARY KEY (`ID`),
			  KEY `zDate` (`zDate`),
			  KEY `subject` (`subject`),
			  KEY `function` (`function`),
			  KEY `filename` (`filename`),
			  KEY `severity` (`severity`)
			) ENGINE=MYISAM;";
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){echo $q->mysql_error."\n";return;}
	}


	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		if($unix->file_time_min($targetFile)>240){@unlink($targetFile);continue;}
		$array=unserialize(@file_get_contents($targetFile));
		if(!is_array($array)){@unlink($targetFile);continue;}

		if(!is_numeric($array["TASKID"])){$array["TASKID"]=0;}
		$content=mysql_escape_string2($array["text"]);
		$subject=mysql_escape_string2($array["subject"]);

		$zdate=$array["zdate"];
		$function=$array["function"];
		$file=$array["file"];
		$line=$array["line"];
		$TASKID=$array["TASKID"];
		$severity=$array["severity"];

		$q->QUERY_SQL("INSERT IGNORE INTO `squid_admin_purge`
				(`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`) VALUES
				('$zdate','$content','$subject','$function','$file','$line','$severity')","artica_events");

		if(!$q->ok){return;}

		@unlink($targetFile);

	}

}






function rotate_admin_events_checks($nopid=false){
	$f=array();
	$unix=new unix();

	$BaseWorkDir="{$GLOBALS["ARTICALOGDIR"]}/rotate_admin_events";
	if(!is_dir($BaseWorkDir)){return;}
	if (!$handle = opendir($BaseWorkDir)) {echo "Failed open $BaseWorkDir\n";return;}
	
	$sock=new sockets();
	$users=new usersMenus();
	$hostname=$unix->hostname_g();
	
	
	
	
	
	$hostname=$unix->hostname_g();
	$q=new mysql();
	if(!$q->test_mysql_connection()){
		if($GLOBALS["VERBOSE"]){echo " **** rotate_admin_events_checks() test_mysql_connection() FAILED **** \n";}
		return;}
	
	
	$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`rotate_admin_events` (
	`zDate` TIMESTAMP NOT NULL ,
	`description` MEDIUMTEXT NOT NULL ,
	`function` VARCHAR( 60 ) NOT NULL ,
	`filename` VARCHAR( 50 ) NOT NULL ,
	`line` INT( 10 ) NOT NULL ,
	`category` VARCHAR( 50 ) NOT NULL ,
	`TASKID` INT(10) NOT NULL,
	KEY  `zDate` ( `zDate`),
	KEY `function` (`function`),
	KEY `filename` (`filename`),
	KEY `line` (`line`),
	KEY `TASKID` (`TASKID`),
	KEY `category` (`category`)
	) ENGINE=MYISAM;";
	$q->QUERY_SQL($sql,"artica_events");
	
	if(!$q->FIELD_EXISTS("rotate_admin_events", "hostname", "artica_events")){
		$q->QUERY_SQL("ALTER TABLE `rotate_admin_events` ADD `hostname` VARCHAR( 255 ),ADD INDEX ( `hostname` )","artica_events");
	}
	
	if($GLOBALS["VERBOSE"]){echo " **** rotate_admin_events_checks() START LOOP ON $BaseWorkDir **** \n";}
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		
		$targetFile="$BaseWorkDir/$filename";
		
		
		if($unix->file_time_min($targetFile)>240){@unlink($targetFile);continue;}
		if($GLOBALS["VERBOSE"]){echo " **** $targetFile PARSE **** \n";}
		
		
		$array=unserialize(@file_get_contents($targetFile));
		if(!is_array($array)){@unlink($targetFile);continue;}
	
		if(!is_numeric($array["TASKID"])){$array["TASKID"]=0;}
		$content=mysql_escape_string2($array["text"]);
		
		$zdate=$array["zdate"];
		$function=$array["function"];
		$file=$array["file"];
		$line=$array["line"];
		$TASKID=$array["TASKID"];
		$category=$array["category"];
	
		$q->QUERY_SQL("INSERT IGNORE INTO `rotate_admin_events`
				(`zDate`,`description`,`function`,`filename`,`line`,`category`,`hostname`,`TASKID`) VALUES
				('$zdate','$content','$function','$file','$line','$category','$hostname','$TASKID')","artica_events");
	
		if(!$q->ok){continue;}
	
		@unlink($targetFile);
	
	}	

}

function rdpproxy_admin_mysql_check($nopid=false){
	$f=array();
	$unix=new unix();


	$sock=new sockets();
	$users=new usersMenus();
	$hostname=$unix->hostname_g();
	$BaseWorkDir="{$GLOBALS["ARTICALOGDIR"]}/rdpproxy_admin_mysql";

	if (!$handle = opendir($BaseWorkDir)) {return;}


	$hostname=$unix->hostname_g();
	$q=new mysql();
	if(!$q->test_mysql_connection()){return;}


	$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`rdpproxy_admin_mysql` (
		`ID` int(11) NOT NULL AUTO_INCREMENT,
		`zDate` TIMESTAMP NOT NULL ,
		`content` MEDIUMTEXT NOT NULL ,
		`hostname` VARCHAR( 255 ),
		`subject` VARCHAR( 255 ) NOT NULL ,
		`function` VARCHAR( 60 ) NOT NULL ,
		`filename` VARCHAR( 50 ) NOT NULL ,
		`line` INT( 10 ) NOT NULL ,
		`severity` smallint( 1 ) NOT NULL ,
		`TASKID` BIGINT UNSIGNED ,
		PRIMARY KEY (`ID`),
		  KEY `zDate` (`zDate`),
		  KEY `subject` (`subject`),
		  KEY `hostname` (`hostname`),
		  KEY `function` (`function`),
		  KEY `filename` (`filename`),
		  KEY `severity` (`severity`)
		) ENGINE=MYISAM;";
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){echo $q->mysql_error."\n";return;}



	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		if($GLOBALS["VERBOSE"]){echo "rdpproxy_admin_mysql_check:: $targetFile\n";}
		if($unix->file_time_min($targetFile)>240){@unlink($targetFile);continue;}
		$array=unserialize(@file_get_contents($targetFile));
		if(!is_array($array)){@unlink($targetFile);continue;}

		if(!is_numeric($array["TASKID"])){$array["TASKID"]=0;}
		$content=mysql_escape_string2($array["text"]);
		$subject=mysql_escape_string2($array["subject"]);

		$zdate=$array["zdate"];
		$function=$array["function"];
		$file=$array["file"];
		$line=$array["line"];
		$TASKID=$array["TASKID"];
		$severity=$array["severity"];

		$q->QUERY_SQL("INSERT IGNORE INTO `rdpproxy_admin_mysql`
				(`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`,`hostname`) VALUES
				('$zdate','$content','$subject','$function','$file','$line','$severity','$hostname')","artica_events");

		if(!$q->ok){
			if($GLOBALS["VERBOSE"]){echo "rdpproxy_admin_mysql_check:: $q->mysql_error\n";}
			return;}

		@unlink($targetFile);

	}

}


function blackwhite_admin_mysql_check($nopid=false){
	$f=array();
	$unix=new unix();
	if($nopid){
	
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}
		$t=0;
	
	}
	
	$sock=new sockets();
	$users=new usersMenus();
	$hostname=$unix->hostname_g();
	$BaseWorkDir="{$GLOBALS["ARTICALOGDIR"]}/blackwhite_admin_mysql";
	if(!is_dir($BaseWorkDir)){return; }
	if (!$handle = opendir($BaseWorkDir)) {echo "Failed open $BaseWorkDir\n";return;}
	
	
	$hostname=$unix->hostname_g();
	$q=new mysql();
	if(!$q->test_mysql_connection()){return;}
	
	
	$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`blackwhite_admin_mysql` (
		`ID` int(11) NOT NULL AUTO_INCREMENT,
		`zDate` TIMESTAMP NOT NULL ,
		`content` MEDIUMTEXT NOT NULL ,
		`hostname` VARCHAR( 255 ),
		`subject` VARCHAR( 255 ) NOT NULL ,
		`function` VARCHAR( 60 ) NOT NULL ,
		`filename` VARCHAR( 50 ) NOT NULL ,
		`line` INT( 10 ) NOT NULL ,
		`severity` smallint( 1 ) NOT NULL ,
		`TASKID` BIGINT UNSIGNED ,
		PRIMARY KEY (`ID`),
		  KEY `zDate` (`zDate`),
		  KEY `subject` (`subject`),
		  KEY `hostname` (`hostname`),
		  KEY `function` (`function`),
		  KEY `filename` (`filename`),
		  KEY `severity` (`severity`)
		) ENGINE=MYISAM;";
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){echo $q->mysql_error."\n";return;}
	
	if(!$q->FIELD_EXISTS("blackwhite_admin_mysql", "hostname", "artica_events")){
		$q->QUERY_SQL("ALTER TABLE `squid_admin_mysql` ADD `hostname` VARCHAR( 255 ),ADD INDEX ( `hostname` )","artica_events");
	}	
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		if($unix->file_time_min($targetFile)>240){@unlink($targetFile);continue;}
		$array=unserialize(@file_get_contents($targetFile));
		if(!is_array($array)){@unlink($targetFile);continue;}
	
		if(!is_numeric($array["TASKID"])){$array["TASKID"]=0;}
		$content=mysql_escape_string2($array["text"]);
		$subject=mysql_escape_string2($array["subject"]);
	
		$zdate=$array["zdate"];
		$function=$array["function"];
		$file=$array["file"];
		$line=$array["line"];
		$TASKID=$array["TASKID"];
		$severity=$array["severity"];
	
		$q->QUERY_SQL("INSERT IGNORE INTO `blackwhite_admin_mysql`
				(`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`,`hostname`) VALUES
				('$zdate','$content','$subject','$function','$file','$line','$severity','$hostname')","artica_events");
	
		if(!$q->ok){return;}
	
		@unlink($targetFile);
	
	}	
	
}

function apache_admin_mysql_check($nopid=false){
	$f=array();
	$unix=new unix();
	if($nopid){
	
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}
		$t=0;
	
	}
	
	$sock=new sockets();
	$users=new usersMenus();
	$hostname=$unix->hostname_g();
	$BaseWorkDir="{$GLOBALS["ARTICALOGDIR"]}/apache_admin_mysql";
	if(!is_dir($BaseWorkDir)){return;}
	if (!$handle = opendir($BaseWorkDir)) {echo "Failed open $BaseWorkDir\n";return;}
	
	
	$hostname=$unix->hostname_g();
	$q=new mysql();
	if(!$q->test_mysql_connection()){return;}
	
	
	$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`apache_admin_mysql` (
		`ID` int(11) NOT NULL AUTO_INCREMENT,
		`zDate` TIMESTAMP NOT NULL ,
		`content` MEDIUMTEXT NOT NULL ,
		`hostname` VARCHAR( 255 ),
		`subject` VARCHAR( 255 ) NOT NULL ,
		`function` VARCHAR( 60 ) NOT NULL ,
		`filename` VARCHAR( 50 ) NOT NULL ,
		`line` INT( 10 ) NOT NULL ,
		`severity` smallint( 1 ) NOT NULL ,
		`TASKID` BIGINT UNSIGNED ,
		PRIMARY KEY (`ID`),
		  KEY `zDate` (`zDate`),
		  KEY `subject` (`subject`),
		  KEY `hostname` (`hostname`),
		  KEY `function` (`function`),
		  KEY `filename` (`filename`),
		  KEY `severity` (`severity`)
		) ENGINE=MYISAM;";
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){echo $q->mysql_error."\n";return;}

	
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		if($unix->file_time_min($targetFile)>240){@unlink($targetFile);continue;}
		$array=unserialize(@file_get_contents($targetFile));
		if(!is_array($array)){@unlink($targetFile);continue;}
	
		if(!is_numeric($array["TASKID"])){$array["TASKID"]=0;}
		$content=mysql_escape_string2($array["text"]);
		$subject=mysql_escape_string2($array["subject"]);
	
		$zdate=$array["zdate"];
		$function=$array["function"];
		$file=$array["file"];
		$line=$array["line"];
		$TASKID=$array["TASKID"];
		$severity=$array["severity"];
	
		$q->QUERY_SQL("INSERT IGNORE INTO `apache_admin_mysql`
				(`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`,`hostname`) VALUES
				('$zdate','$content','$subject','$function','$file','$line','$severity','$hostname')","artica_events");
	
		if(!$q->ok){return;}
	
		@unlink($targetFile);
	
	}
	
	
}




function cyrus_admin_mysql_check($nopid=false){
	$f=array();
	$unix=new unix();
	if($nopid){
	
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}
		$t=0;
	
	}
	
	$sock=new sockets();
	$users=new usersMenus();
	$hostname=$unix->hostname_g();
	$BaseWorkDir="{$GLOBALS["ARTICALOGDIR"]}/cyrus_admin_mysql";
	if(!is_dir($BaseWorkDir)){return;}
	if (!$handle = opendir($BaseWorkDir)) {echo "Failed open $BaseWorkDir\n";return;}
	
	
	$hostname=$unix->hostname_g();
	$q=new mysql();
	if(!$q->test_mysql_connection()){return;}
	
	
	$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`cyrus_admin_mysql` (
		`ID` int(11) NOT NULL AUTO_INCREMENT,
		`zDate` TIMESTAMP NOT NULL ,
		`content` MEDIUMTEXT NOT NULL ,
		`hostname` VARCHAR( 255 ),
		`subject` VARCHAR( 255 ) NOT NULL ,
		`function` VARCHAR( 60 ) NOT NULL ,
		`filename` VARCHAR( 50 ) NOT NULL ,
		`line` INT( 10 ) NOT NULL ,
		`severity` smallint( 1 ) NOT NULL ,
		`TASKID` BIGINT UNSIGNED ,
		PRIMARY KEY (`ID`),
		  KEY `zDate` (`zDate`),
		  KEY `subject` (`subject`),
		  KEY `hostname` (`hostname`),
		  KEY `function` (`function`),
		  KEY `filename` (`filename`),
		  KEY `severity` (`severity`)
		) ENGINE=MYISAM;";
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){echo $q->mysql_error."\n";return;}
	
	if(!$q->FIELD_EXISTS("cyrus_admin_mysql", "hostname", "artica_events")){
		$q->QUERY_SQL("ALTER TABLE `squid_admin_mysql` ADD `hostname` VARCHAR( 255 ),ADD INDEX ( `hostname` )","artica_events");
	}
	
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		if($unix->file_time_min($targetFile)>240){@unlink($targetFile);continue;}
		$array=unserialize(@file_get_contents($targetFile));
		if(!is_array($array)){@unlink($targetFile);continue;}
	
		if(!is_numeric($array["TASKID"])){$array["TASKID"]=0;}
		$content=mysql_escape_string2($array["text"]);
		$subject=mysql_escape_string2($array["subject"]);
	
		$zdate=$array["zdate"];
		$function=$array["function"];
		$file=$array["file"];
		$line=$array["line"];
		$TASKID=$array["TASKID"];
		$severity=$array["severity"];
	
		$q->QUERY_SQL("INSERT IGNORE INTO `cyrus_admin_mysql`
				(`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`,`hostname`) VALUES
				('$zdate','$content','$subject','$function','$file','$line','$severity','$hostname')","artica_events");
	
		if(!$q->ok){return;}
	
		@unlink($targetFile);
	
	}	
	
}

function meta_admin_mysql_check($nopid=false){
	$f=array();
	$unix=new unix();

	
	$sock=new sockets();
	$users=new usersMenus();
	$hostname=$unix->hostname_g();
	$BaseWorkDir="{$GLOBALS["ARTICALOGDIR"]}/meta_admin_mysql";
	if(!is_dir($BaseWorkDir)){return;}
	$uuid=$unix->GetUniqueID();
	if (!$handle = opendir($BaseWorkDir)) {echo "Failed open $BaseWorkDir\n";return;}
	
	$hostname="master";
	$EnableArticaMetaServer=intval($sock->GET_INFO("EnableArticaMetaServer"));
	if($EnableArticaMetaServer==0){
		$hostname=$unix->hostname_g();
	}
	$q=new mysql();
	if(!$q->test_mysql_connection()){return;}
	
	if(!$q->TABLE_EXISTS("meta_admin_mysql", "artica_events")){return;}
	
	
	if(!$q->ok){
		meta_events("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		if($unix->file_time_min($targetFile)>240){@unlink($targetFile);continue;}
		$array=unserialize(@file_get_contents($targetFile));
		if(!is_array($array)){@unlink($targetFile);continue;}
	
		if(!is_numeric($array["TASKID"])){$array["TASKID"]=0;}
		$content=mysql_escape_string2($array["text"]);
		$subject=mysql_escape_string2($array["subject"]);
	
		$zdate=$array["zdate"];
		$function=$array["function"];
		$file=$array["file"];
		$line=$array["line"];
		$TASKID=$array["TASKID"];
		$severity=$array["severity"];
		
		$zm5=md5("$zdate$subject$function$file$line$uuid");
		
		
		meta_events("[meta_admin_mysql]:: $subject ",__FUNCTION__,__FILE__,__LINE__);
		
		$q->QUERY_SQL("INSERT IGNORE INTO `meta_admin_mysql`
				(`zmd5`,`uuid`,`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`,`hostname`) VALUES
				('$zm5','$uuid','$zdate','$content','$subject','$function','$file','$line','$severity','$hostname')","artica_events");
		
	
		
		$q->QUERY_SQL("INSERT IGNORE INTO `meta_admin_mysql`
				(`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`,`hostname`) VALUES
				('$zdate','$content','$subject','$function','$file','$line','$severity','$hostname')","artica_events");
	
		if(!$q->ok){
			meta_events("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
			return;}
	
		@unlink($targetFile);
	
	}	
	
}

function meta_events($text,$function,$file=null,$line=0){
	if($file==null){$file=basename(__FILE__);}else{$file=basename($file);}
	$pid=@getmypid();
	$date=@date("H:i:s");
	$logFile="/var/log/artica-meta-agent.log";
	$size=@filesize($logFile);
	if($size>1000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');
	$text="[$file][$pid] $date $function:: $text (L.$line)\n";
	if($GLOBALS["VERBOSE"]){echo $text;}
	@fwrite($f, $text);
	@fclose($f);

	$logFile="/var/log/artica-meta.log";
	$size=@filesize($logFile);
	if($size>1000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');
	@fwrite($f, $text);
	@fclose($f);


}

function system_admin_mysql_check($nopid=false){
	$f=array();
	$unix=new unix();


	$BaseWorkDir="{$GLOBALS["ARTICALOGDIR"]}/system_admin_mysql";
	if(!is_dir($BaseWorkDir)){@mkdir($BaseWorkDir,0755,true);}
	if (!$handle = opendir($BaseWorkDir)) {echo "Failed open $BaseWorkDir\n";return;}

	$sock=new sockets();
	$users=new usersMenus();
	$hostname=$unix->hostname_g();

	$q=new mysql();
	if(!$q->test_mysql_connection()){return;}


	$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`system_admin_mysql` (
		`ID` int(11) NOT NULL AUTO_INCREMENT,
		`zDate` TIMESTAMP NOT NULL ,
		`content` MEDIUMTEXT NOT NULL ,
		`hostname` VARCHAR( 255 ),
		`subject` VARCHAR( 255 ) NOT NULL ,
		`function` VARCHAR( 60 ) NOT NULL ,
		`filename` VARCHAR( 50 ) NOT NULL ,
		`line` INT( 10 ) NOT NULL ,
		`severity` smallint( 1 ) NOT NULL ,
		`sended` smallint( 1 ) NOT NULL DEFAULT 0,
		`TASKID` BIGINT UNSIGNED ,
		PRIMARY KEY (`ID`),
		  KEY `zDate` (`zDate`),
		  KEY `subject` (`subject`),
		  KEY `hostname` (`hostname`),
		  KEY `function` (`function`),
		  KEY `filename` (`filename`),
		  KEY `sended` (`sended`),
		  KEY `severity` (`severity`)
		) ENGINE=MYISAM;";
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){echo $q->mysql_error."\n";return;}

	if(!$q->FIELD_EXISTS("mysql_admin_mysql", "hostname", "artica_events")){
		$q->QUERY_SQL("ALTER TABLE `system_admin_mysql` ADD `hostname` VARCHAR( 255 ),ADD INDEX ( `hostname` )","artica_events");
	}
	if(!$q->FIELD_EXISTS("mysql_admin_mysql", "sended", "artica_events")){
		$q->QUERY_SQL("ALTER TABLE `system_admin_mysql` ADD `sended` smallint(1) NOT NULL DEFAULT 0,ADD INDEX ( `sended` )","artica_events");
	}


	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		if($unix->file_time_min($targetFile)>240){@unlink($targetFile);continue;}
		$array=unserialize(@file_get_contents($targetFile));
		if(!is_array($array)){@unlink($targetFile);continue;}

		if(!is_numeric($array["TASKID"])){$array["TASKID"]=0;}
		$content=mysql_escape_string2($array["text"]);
		$subject=mysql_escape_string2($array["subject"]);

		$zdate=$array["zdate"];
		$function=$array["function"];
		$file=$array["file"];
		$line=$array["line"];
		$TASKID=$array["TASKID"];
		$severity=$array["severity"];

		$q->QUERY_SQL("INSERT IGNORE INTO `system_admin_mysql`
				(`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`,`hostname`) VALUES
				('$zdate','$content','$subject','$function','$file','$line','$severity','$hostname')","artica_events");

		if(!$q->ok){return;}

		@unlink($targetFile);

	}
}

function meta_client_clean_logs(){
	$BaseWorkDir="/home/artica-meta/events";
	if(!is_dir($BaseWorkDir)){return;}
	if (!$handle = opendir($BaseWorkDir)) {echo "Failed open $BaseWorkDir\n";return;}
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		if(is_dir($targetFile)){continue;}
		@unlink($targetFile);
	}
	
}


function mysql_admin_mysql_check($nopid=false){
	$f=array();
	$unix=new unix();



	$BaseWorkDir="{$GLOBALS["ARTICALOGDIR"]}/mysql_admin_mysql";
	if(!is_dir($BaseWorkDir)){@mkdir($BaseWorkDir,0755,true);}
	if (!$handle = opendir($BaseWorkDir)) {echo "Failed open $BaseWorkDir\n";return;}

	$sock=new sockets();
	$users=new usersMenus();
	$hostname=$unix->hostname_g();
	
	$q=new mysql();
	if(!$q->test_mysql_connection()){return;}
	
	
	$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`mysql_admin_mysql` (
		`ID` int(11) NOT NULL AUTO_INCREMENT,
		`zDate` TIMESTAMP NOT NULL ,
		`content` MEDIUMTEXT NOT NULL ,
		`hostname` VARCHAR( 255 ),
		`subject` VARCHAR( 255 ) NOT NULL ,
		`function` VARCHAR( 60 ) NOT NULL ,
		`filename` VARCHAR( 50 ) NOT NULL ,
		`line` INT( 10 ) NOT NULL ,
		`severity` smallint( 1 ) NOT NULL ,
		`sended` smallint( 1 ) NOT NULL DEFAULT 0,
		`TASKID` BIGINT UNSIGNED ,
		PRIMARY KEY (`ID`),
		  KEY `zDate` (`zDate`),
		  KEY `subject` (`subject`),
		  KEY `hostname` (`hostname`),
		  KEY `function` (`function`),
		  KEY `filename` (`filename`),
		  KEY `sended` (`sended`),
		  KEY `severity` (`severity`)
		) ENGINE=MYISAM;";
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){echo $q->mysql_error."\n";return;}
	
		if(!$q->FIELD_EXISTS("mysql_admin_mysql", "hostname", "artica_events")){
			$q->QUERY_SQL("ALTER TABLE `squid_admin_mysql` ADD `hostname` VARCHAR( 255 ),ADD INDEX ( `hostname` )","artica_events");
		}
		if(!$q->FIELD_EXISTS("mysql_admin_mysql", "sended", "artica_events")){
			$q->QUERY_SQL("ALTER TABLE `squid_admin_mysql` ADD `sended` smallint(1) NOT NULL DEFAULT 0,ADD INDEX ( `sended` )","artica_events");
		}		
		
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		if(is_dir($targetFile)){continue;}
		if($unix->file_time_min($targetFile)>240){@unlink($targetFile);continue;}
		$array=unserialize(@file_get_contents($targetFile));
		if(!is_array($array)){@unlink($targetFile);continue;}
	
		if(!is_numeric($array["TASKID"])){$array["TASKID"]=0;}
		$content=mysql_escape_string2($array["text"]);
		$subject=mysql_escape_string2($array["subject"]);
		
		$zdate=$array["zdate"];
		$function=$array["function"];
		$file=$array["file"];
		$line=$array["line"];
		$TASKID=$array["TASKID"];
		$severity=$array["severity"];
		
		$q->QUERY_SQL("INSERT IGNORE INTO `mysql_admin_mysql`
				(`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`,`hostname`) VALUES
				('$zdate','$content','$subject','$function','$file','$line','$severity','$hostname')","artica_events");
		
		if(!$q->ok){return;}
		
		@unlink($targetFile);

	}
}
function nginx_admin_mysql_check($nopid=false){
	$f=array();
	$unix=new unix();



	$BaseWorkDir="{$GLOBALS["ARTICALOGDIR"]}/nginx_admin_mysql";
	if(!is_dir($BaseWorkDir)){return;}
	if (!$handle = opendir($BaseWorkDir)) {echo "Failed open $BaseWorkDir\n";return;}

	$sock=new sockets();
	$users=new usersMenus();
	$hostname=$unix->hostname_g();

	$q=new mysql();
	if(!$q->test_mysql_connection()){return;}


	$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`nginx_admin_mysql` (
		`ID` int(11) NOT NULL AUTO_INCREMENT,
		`zDate` TIMESTAMP NOT NULL ,
		`content` MEDIUMTEXT NOT NULL ,
		`hostname` VARCHAR( 255 ),
		`subject` VARCHAR( 255 ) NOT NULL ,
		`function` VARCHAR( 60 ) NOT NULL ,
		`filename` VARCHAR( 50 ) NOT NULL ,
		`line` INT( 10 ) NOT NULL ,
		`severity` smallint( 1 ) NOT NULL ,
		`sended` smallint( 1 ) NOT NULL DEFAULT 0,
		`TASKID` BIGINT UNSIGNED ,
		PRIMARY KEY (`ID`),
		  KEY `zDate` (`zDate`),
		  KEY `subject` (`subject`),
		  KEY `hostname` (`hostname`),
		  KEY `function` (`function`),
		  KEY `filename` (`filename`),
		  KEY `sended` (`sended`),
		  KEY `severity` (`severity`)
		) ENGINE=MYISAM;";
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){echo $q->mysql_error."\n";return;}

	if(!$q->FIELD_EXISTS("nginx_admin_mysql", "hostname", "artica_events")){
		$q->QUERY_SQL("ALTER TABLE `nginx_admin_mysql` ADD `hostname` VARCHAR( 255 ),ADD INDEX ( `hostname` )","artica_events");
	}
	if(!$q->FIELD_EXISTS("nginx_admin_mysql", "sended", "artica_events")){
		$q->QUERY_SQL("ALTER TABLE `nginx_admin_mysql` ADD `sended` smallint(1) NOT NULL DEFAULT 0,ADD INDEX ( `sended` )","artica_events");
	}


	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		if($unix->file_time_min($targetFile)>240){@unlink($targetFile);continue;}
		$array=unserialize(@file_get_contents($targetFile));
		if(!is_array($array)){@unlink($targetFile);continue;}

		if(!is_numeric($array["TASKID"])){$array["TASKID"]=0;}
		$content=mysql_escape_string2($array["text"]);
		$subject=mysql_escape_string2($array["subject"]);

		$zdate=$array["zdate"];
		$function=$array["function"];
		$file=$array["file"];
		$line=$array["line"];
		$TASKID=$array["TASKID"];
		$severity=$array["severity"];

		$q->QUERY_SQL("INSERT IGNORE INTO `nginx_admin_mysql`
				(`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`,`hostname`) VALUES
				('$zdate','$content','$subject','$function','$file','$line','$severity','$hostname')","artica_events");

		if(!$q->ok){return;}

		@unlink($targetFile);

	}

	
}

function webupdate_admin_mysql_check($nopid=false){
	$f=array();
	$unix=new unix();
	
	
	
	$BaseWorkDir="{$GLOBALS["ARTICALOGDIR"]}/webupdate_admin_mysql";
	if(!is_dir($BaseWorkDir)){return;}
	if (!$handle = opendir($BaseWorkDir)) {echo "Failed open $BaseWorkDir\n";return;}
	
	$sock=new sockets();
	$users=new usersMenus();
	$hostname=$unix->hostname_g();
	
	$q=new mysql();
	if(!$q->test_mysql_connection()){return;}
	
	
	$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`webupdate_admin_mysql` (
		`ID` int(11) NOT NULL AUTO_INCREMENT,
		`zDate` TIMESTAMP NOT NULL ,
		`content` MEDIUMTEXT NOT NULL ,
		`hostname` VARCHAR( 255 ),
		`subject` VARCHAR( 255 ) NOT NULL ,
		`function` VARCHAR( 60 ) NOT NULL ,
		`filename` VARCHAR( 50 ) NOT NULL ,
		`line` INT( 10 ) NOT NULL ,
		`severity` smallint( 1 ) NOT NULL ,
		`sended` smallint( 1 ) NOT NULL DEFAULT 0,
		`TASKID` BIGINT UNSIGNED ,
		PRIMARY KEY (`ID`),
		  KEY `zDate` (`zDate`),
		  KEY `subject` (`subject`),
		  KEY `hostname` (`hostname`),
		  KEY `function` (`function`),
		  KEY `filename` (`filename`),
		  KEY `sended` (`sended`),
		  KEY `severity` (`severity`)
		) ENGINE=MYISAM;";
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){echo $q->mysql_error."\n";return;}
	
	if(!$q->FIELD_EXISTS("webupdate_admin_mysql", "hostname", "artica_events")){
		$q->QUERY_SQL("ALTER TABLE `webupdate_admin_mysql` ADD `hostname` VARCHAR( 255 ),ADD INDEX ( `hostname` )","artica_events");
	}
	if(!$q->FIELD_EXISTS("webupdate_admin_mysql", "sended", "artica_events")){
		$q->QUERY_SQL("ALTER TABLE `webupdate_admin_mysql` ADD `sended` smallint(1) NOT NULL DEFAULT 0,ADD INDEX ( `sended` )","artica_events");
	}
	
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		if($unix->file_time_min($targetFile)>240){@unlink($targetFile);continue;}
		$array=unserialize(@file_get_contents($targetFile));
		if(!is_array($array)){@unlink($targetFile);continue;}
	
		if(!is_numeric($array["TASKID"])){$array["TASKID"]=0;}
		$content=mysql_escape_string2($array["text"]);
		$subject=mysql_escape_string2($array["subject"]);
	
		$zdate=$array["zdate"];
		$function=$array["function"];
		$file=$array["file"];
		$line=$array["line"];
		$TASKID=$array["TASKID"];
		$severity=$array["severity"];
	
		$q->QUERY_SQL("INSERT IGNORE INTO `webupdate_admin_mysql`
				(`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`,`hostname`) VALUES
				('$zdate','$content','$subject','$function','$file','$line','$severity','$hostname')","artica_events");
	
		if(!$q->ok){return;}
	
		@unlink($targetFile);
	
	}
	
}

function squid_admin_enforce_check($nopid=false){
	$f=array();
	$unix=new unix();



	$BaseWorkDir="{$GLOBALS["ARTICALOGDIR"]}/squid_admin_enforce";
	if(!is_dir($BaseWorkDir)){return;}
	if (!$handle = opendir($BaseWorkDir)) {echo "Failed open $BaseWorkDir\n";return;}

	$sock=new sockets();
	$users=new usersMenus();
	$hostname=$unix->hostname_g();

	$q=new mysql();
	if(!$q->test_mysql_connection()){return;}


	$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`squid_admin_enforce` (
		`ID` int(11) NOT NULL AUTO_INCREMENT,
		`zDate` TIMESTAMP NOT NULL ,
		`content` MEDIUMTEXT NOT NULL ,
		`hostname` VARCHAR( 255 ),
		`subject` VARCHAR( 255 ) NOT NULL ,
		`function` VARCHAR( 60 ) NOT NULL ,
		`filename` VARCHAR( 50 ) NOT NULL ,
		`line` INT( 10 ) NOT NULL ,
		`severity` smallint( 1 ) NOT NULL ,
		`sended` smallint( 1 ) NOT NULL DEFAULT 0,
		`TASKID` BIGINT UNSIGNED ,
		PRIMARY KEY (`ID`),
		  KEY `zDate` (`zDate`),
		  KEY `subject` (`subject`),
		  KEY `hostname` (`hostname`),
		  KEY `function` (`function`),
		  KEY `filename` (`filename`),
		  KEY `sended` (`sended`),
		  KEY `severity` (`severity`)
		) ENGINE=MYISAM;";
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){echo $q->mysql_error."\n";return;}

	if(!$q->FIELD_EXISTS("squid_admin_enforce", "hostname", "artica_events")){
		$q->QUERY_SQL("ALTER TABLE `squid_admin_enforce` ADD `hostname` VARCHAR( 255 ),ADD INDEX ( `hostname` )","artica_events");
	}
	if(!$q->FIELD_EXISTS("squid_admin_enforce", "sended", "artica_events")){
		$q->QUERY_SQL("ALTER TABLE `squid_admin_enforce` ADD `sended` smallint(1) NOT NULL DEFAULT 0,ADD INDEX ( `sended` )","artica_events");
	}


	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		if($unix->file_time_min($targetFile)>240){@unlink($targetFile);continue;}
		$array=unserialize(@file_get_contents($targetFile));
		if(!is_array($array)){@unlink($targetFile);continue;}

		if(!is_numeric($array["TASKID"])){$array["TASKID"]=0;}
		$content=mysql_escape_string2($array["text"]);
		$subject=mysql_escape_string2($array["subject"]);

		$zdate=$array["zdate"];
		$function=$array["function"];
		$file=$array["file"];
		$line=$array["line"];
		$TASKID=$array["TASKID"];
		$severity=$array["severity"];

		$q->QUERY_SQL("INSERT IGNORE INTO `squid_admin_enforce`
				(`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`,`hostname`) VALUES
				('$zdate','$content','$subject','$function','$file','$line','$severity','$hostname')","artica_events");

		if(!$q->ok){return;}

		@unlink($targetFile);

	}

}

function postfix_admin_mysql_check($nopid=false){
	$f=array();
	$unix=new unix();



	$BaseWorkDir="{$GLOBALS["ARTICALOGDIR"]}/postfix_admin_mysql";
	if(!is_dir($BaseWorkDir)){return;}
	if (!$handle = opendir($BaseWorkDir)) {echo "Failed open $BaseWorkDir\n";return;}

	$sock=new sockets();
	$users=new usersMenus();
	$hostname=$unix->hostname_g();
	
	$q=new mysql();
	if(!$q->test_mysql_connection()){return;}
	
	
	$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`postfix_admin_mysql` (
		`ID` int(11) NOT NULL AUTO_INCREMENT,
		`zDate` TIMESTAMP NOT NULL ,
		`content` MEDIUMTEXT NOT NULL ,
		`hostname` VARCHAR( 255 ),
		`subject` VARCHAR( 255 ) NOT NULL ,
		`function` VARCHAR( 60 ) NOT NULL ,
		`filename` VARCHAR( 50 ) NOT NULL ,
		`line` INT( 10 ) NOT NULL ,
		`severity` smallint( 1 ) NOT NULL ,
		`sended` smallint( 1 ) NOT NULL DEFAULT 0,
		`TASKID` BIGINT UNSIGNED ,
		PRIMARY KEY (`ID`),
		  KEY `zDate` (`zDate`),
		  KEY `subject` (`subject`),
		  KEY `hostname` (`hostname`),
		  KEY `function` (`function`),
		  KEY `filename` (`filename`),
		  KEY `sended` (`sended`),
		  KEY `severity` (`severity`)
		) ENGINE=MYISAM;";
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){echo $q->mysql_error."\n";return;}
	
		if(!$q->FIELD_EXISTS("postfix_admin_mysql", "hostname", "artica_events")){
			$q->QUERY_SQL("ALTER TABLE `squid_admin_mysql` ADD `hostname` VARCHAR( 255 ),ADD INDEX ( `hostname` )","artica_events");
		}
		if(!$q->FIELD_EXISTS("postfix_admin_mysql", "sended", "artica_events")){
			$q->QUERY_SQL("ALTER TABLE `squid_admin_mysql` ADD `sended` smallint(1) NOT NULL DEFAULT 0,ADD INDEX ( `sended` )","artica_events");
		}		
		
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		if($unix->file_time_min($targetFile)>240){@unlink($targetFile);continue;}
		$array=unserialize(@file_get_contents($targetFile));
		if(!is_array($array)){@unlink($targetFile);continue;}
	
		if(!is_numeric($array["TASKID"])){$array["TASKID"]=0;}
		$content=mysql_escape_string2($array["text"]);
		$subject=mysql_escape_string2($array["subject"]);
		
		$zdate=$array["zdate"];
		$function=$array["function"];
		$file=$array["file"];
		$line=$array["line"];
		$TASKID=$array["TASKID"];
		$severity=$array["severity"];
		
		$q->QUERY_SQL("INSERT IGNORE INTO `postfix_admin_mysql`
				(`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`,`hostname`) VALUES
				('$zdate','$content','$subject','$function','$file','$line','$severity','$hostname')","artica_events");
		
		if(!$q->ok){return;}
		
		@unlink($targetFile);

	}
	

	
}

function squid_admin_mysql_check($nopid=false){
	$f=array();
	$unix=new unix();



	$BaseWorkDir="{$GLOBALS["ARTICALOGDIR"]}/squid_admin_mysql";
	if(!is_dir($BaseWorkDir)){return;}
	if (!$handle = opendir($BaseWorkDir)) {echo "Failed open $BaseWorkDir\n";return;}

	$sock=new sockets();
	$users=new usersMenus();
	$hostname=$unix->hostname_g();
	
	$q=new mysql();
	if(!$q->test_mysql_connection()){return;}
	
	
	$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`squid_admin_mysql` (
		`ID` int(11) NOT NULL AUTO_INCREMENT,
		`zDate` TIMESTAMP NOT NULL ,
		`content` MEDIUMTEXT NOT NULL ,
		`hostname` VARCHAR( 255 ),
		`subject` VARCHAR( 255 ) NOT NULL ,
		`function` VARCHAR( 60 ) NOT NULL ,
		`filename` VARCHAR( 50 ) NOT NULL ,
		`line` INT( 10 ) NOT NULL ,
		`severity` smallint( 1 ) NOT NULL ,
		`sended` smallint( 1 ) NOT NULL DEFAULT 0,
		`TASKID` BIGINT UNSIGNED ,
		PRIMARY KEY (`ID`),
		  KEY `zDate` (`zDate`),
		  KEY `subject` (`subject`),
		  KEY `hostname` (`hostname`),
		  KEY `function` (`function`),
		  KEY `filename` (`filename`),
		  KEY `sended` (`sended`),
		  KEY `severity` (`severity`)
		) ENGINE=MYISAM;";
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){echo $q->mysql_error."\n";return;}
	
		if(!$q->FIELD_EXISTS("squid_admin_mysql", "hostname", "artica_events")){
			$q->QUERY_SQL("ALTER TABLE `squid_admin_mysql` ADD `hostname` VARCHAR( 255 ),ADD INDEX ( `hostname` )","artica_events");
		}
		if(!$q->FIELD_EXISTS("squid_admin_mysql", "sended", "artica_events")){
			$q->QUERY_SQL("ALTER TABLE `squid_admin_mysql` ADD `sended` smallint(1) NOT NULL DEFAULT 0,ADD INDEX ( `sended` )","artica_events");
		}		
		
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		if($unix->file_time_min($targetFile)>240){@unlink($targetFile);continue;}
		$array=unserialize(@file_get_contents($targetFile));
		if(!is_array($array)){@unlink($targetFile);continue;}
	
		if(!is_numeric($array["TASKID"])){$array["TASKID"]=0;}
		$content=mysql_escape_string2($array["text"]);
		$subject=mysql_escape_string2($array["subject"]);
		
		$zdate=$array["zdate"];
		$function=$array["function"];
		$file=$array["file"];
		$line=$array["line"];
		$TASKID=$array["TASKID"];
		$severity=$array["severity"];
		
		$q->QUERY_SQL("INSERT IGNORE INTO `squid_admin_mysql`
				(`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`,`hostname`) VALUES
				('$zdate','$content','$subject','$function','$file','$line','$severity','$hostname')","artica_events");
		
		if(!$q->ok){return;}
		
		@unlink($targetFile);

	}
	
	$BaseWorkDir="/var/log/squid/squid_admin_mysql";
	if(is_dir($BaseWorkDir)){
		if ($handle = opendir($BaseWorkDir)) {
			while (false !== ($filename = readdir($handle))) {
				if($filename=="."){continue;}
				if($filename==".."){continue;}
				$targetFile="$BaseWorkDir/$filename";
				if($unix->file_time_min($targetFile)>240){@unlink($targetFile);continue;}
				$array=unserialize(@file_get_contents($targetFile));
				if(!is_array($array)){@unlink($targetFile);continue;}
			
				if(!is_numeric($array["TASKID"])){$array["TASKID"]=0;}
				$content=mysql_escape_string2($array["text"]);
				$subject=mysql_escape_string2($array["subject"]);
			
				$zdate=$array["zdate"];
				$function=$array["function"];
				$file=$array["file"];
				$line=$array["line"];
				$TASKID=$array["TASKID"];
				$severity=$array["severity"];
			
				$q->QUERY_SQL("INSERT IGNORE INTO `squid_admin_mysql`
						(`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`,`hostname`) VALUES
						('$zdate','$content','$subject','$function','$file','$line','$severity','$hostname')","artica_events");
			
				if(!$q->ok){return;}
			
				@unlink($targetFile);
			
			}
		}	
	}
	
	
	artica_update_task(true);
}

function squid_admin_notifs_check($nopid=false){
	squid_admin_mysql_check(true);
	squid_admin_enforce_check(true);
	webupdate_admin_mysql_check(true);

}

function system_rotate_events_checks($nopid=false){
	$f=array();
	$BaseWorkDir="{$GLOBALS["ARTICALOGDIR"]}/rotate_events";
	$unix=new unix();
	
	
	if($nopid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}
		$t=0;
		@file_put_contents($pidfile, getmypid());
	}	
	
	if (!$handle = opendir($BaseWorkDir)) {echo "Failed open $BaseWorkDir\n";return;}	
	$q=new mysql();
	
	$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`system_rotate_events` (
	`zDate` TIMESTAMP NOT NULL ,
	`description` MEDIUMTEXT NOT NULL ,
	`hostname` VARCHAR( 90 ) NOT NULL ,
	`function` VARCHAR( 60 ) NOT NULL ,
	`filename` VARCHAR( 50 ) NOT NULL ,
	`line` INT( 10 ) NOT NULL ,
	`category` VARCHAR( 50 ) NOT NULL ,
	`TASKID` INT(10) NOT NULL,
	KEY  `zDate` ( `zDate`),
	KEY `function` (`function`),
	KEY `filename` (`filename`),
	KEY `line` (`line`),
	KEY `hostname` (`hostname`),
	KEY `TASKID` (`TASKID`),
	KEY `category` (`category`)
	) ENGINE=MYISAM;";
	$q->QUERY_SQL($sql,"artica_events");	
	
	
	
	$prefix="INSERT IGNORE INTO system_rotate_events (`zDate`,`function`,`filename`,`line`,`description`,`category`,`TASKID`,`hostname`) VALUES ";
	$hostname=$unix->hostname_g();
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		
		if($unix->file_time_min($targetFile)>1440){@unlink($targetFile);continue;}
		
		$array=unserialize(@file_get_contents($targetFile));
		if(!is_array($array)){
			$array["text"]=basename($filename)." is not an array, skip event \n".@file_get_contents($targetFile);
			$array["zdate"]=date('Y-m-d H:i:s');
			$array["pid"]=getmypid();
			$array["function"]=__FUNCTION__;
			$array["category"]="parser";
			$array["file"]=basename(__FILE__);
			$array["line"]=__LINE__;
		}
			
			
		if(!is_numeric($array["TASKID"])){$array["TASKID"]=0;}
		while (list ($key, $val) = each ($array)){
			$val=mysql_escape_string2($val);
			$array[$key]=str_replace("'", "`", $val);
		}
	
		$suffix="('{$array["zdate"]}','{$array["function"]}','{$array["file"]}','{$array["line"]}','{$array["text"]}','{$array["category"]}','{$array["TASKID"]}','$hostname')";
		$q->QUERY_SQL($prefix.$suffix,"artica_events");
		@unlink($targetFile);
		if(!$q->ok){continue;}
		
	}
	
	
		
}

// {$GLOBALS["ARTICALOGDIR"]}/system_failover_events
function system_failover_events_checks($nopid=false){
	$f=array();
	if($nopid){
		$unix=new unix();
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}
		$t=0;
		@file_put_contents($pidfile, getmypid());
	}
	
	$BaseWorkDir="{$GLOBALS["ARTICALOGDIR"]}/system_failover_events";
	if (!$handle = opendir($BaseWorkDir)) {echo "Failed open $BaseWorkDir\n";return;}
	
	$q=new mysql();
	
	$prefix="INSERT IGNORE INTO system_failover_events (`zDate`,`function`,`filename`,`line`,`description`) VALUES ";
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		$array=unserialize(@file_get_contents($targetFile));
		if(!is_array($array)){
			$array["text"]=basename($filename)." is not an array, skip event \n".@file_get_contents($targetFile);
			$array["zdate"]=date('Y-m-d H:i:s');
			$array["pid"]=getmypid();
			$array["function"]=__FUNCTION__;
			$array["category"]="none";
			$array["file"]=basename(__FILE__);
			$array["line"]=__LINE__;
		}
			
			
		while (list ($key, $val) = each ($array)){
			$val=mysql_escape_string2($val);
			$array[$key]=str_replace("'", "`", $val);
		}
	
		$suffix="('{$array["zdate"]}','{$array["function"]}','{$array["file"]}','{$array["line"]}','{$array["text"]}')";
		if(count($f)>1500){
			$q->QUERY_SQL($prefix.$suffix);
			$f=array();
		}
		
	
		@unlink($targetFile);
	}
	
	if(count($f)>0){
		$q->QUERY_SQL($prefix.@implode(",", $f));
		$f=array();
	}
	

	
	
}

function system_admin_events_checks($nopid=false){
	$f=array();
	$unix=new unix();
	$TRW=array();
	if($nopid){
		$unix=new unix();
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}	
		$t=0;		
		@file_put_contents($pidfile, getmypid());
	}	
	
	// removed : foreach (glob("{$GLOBALS["ARTICALOGDIR"]}/system_admin_events/*") as $filename) {
	$BaseWorkDir="{$GLOBALS["ARTICALOGDIR"]}/system_admin_events";
	
	$FILES=$unix->COUNT_FILES($BaseWorkDir);
	if($FILES>5000){
		if (!$handle = opendir($BaseWorkDir)) {echo "Failed open $BaseWorkDir\n";return;}
		while (false !== ($filename = readdir($handle))) {
			if($filename=="."){continue;}
			if($filename==".."){continue;}
			$targetFile="$BaseWorkDir/$filename";
			if(is_dir($targetFile)){continue;}
			@unlink($targetFile);
		}
		return;
	}
	
	if (!$handle = opendir($BaseWorkDir)) {echo "Failed open $BaseWorkDir\n";return;}
	
	
	
	$q=new mysql();	
	if(!$q->BD_CONNECT(true,"called by ".basename(__FILE__)." (".__FUNCTION__.") line: ".__LINE__)){return;}
	
	$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`system_admin_events` (
	`zDate` TIMESTAMP NOT NULL ,
	`description` MEDIUMTEXT NOT NULL ,
	`function` VARCHAR( 60 ) NOT NULL ,
	`filename` VARCHAR( 50 ) NOT NULL ,
	`line` INT( 10 ) NOT NULL ,
	`category` VARCHAR( 50 ) NOT NULL ,
	`TASKID` INT(10) NOT NULL,
	KEY  `zDate` ( `zDate`),
	KEY `function` (`function`),
	KEY `filename` (`filename`),
	KEY `line` (`line`),
	KEY `TASKID` (`TASKID`),
	KEY `category` (`category`)
	) ENGINE=MYISAM;";
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){return;}
	
	$prefix="INSERT IGNORE INTO system_admin_events (`zDate`,`function`,`filename`,`line`,`description`,`category`,`TASKID`) VALUES ";
	while (false !== ($filename = readdir($handle))) {
			if($filename=="."){continue;}
			if($filename==".."){continue;}
			$targetFile="$BaseWorkDir/$filename";
			$array=unserialize(@file_get_contents($targetFile));
			
			@unlink($targetFile);
			
			if(!is_array($array)){
				$array["text"]=basename($filename)." is not an array, skip event \n".@file_get_contents($targetFile);
				$array["zdate"]=date('Y-m-d H:i:s');
				$array["pid"]=getmypid();
				$array["function"]=__FUNCTION__;
				$array["category"]="parser";
				$array["file"]=basename(__FILE__);
				$array["line"]=__LINE__;
			}			
			
			
		if(!is_numeric($array["TASKID"])){$array["TASKID"]=0;}		
		
		$tableName="Taskev{$array["TASKID"]}";
		$chkTables[$tableName]=true;

		WriteMyLogs(substr($array["text"],0,128),__FUNCTION__,__FILE__,__LINE__);
		while (list ($key, $val) = each ($array)){
			$val=mysql_escape_string2($val);
			$array[$key]=str_replace("'", "`", $val);
		}
		
		$rom2="('{$array["zdate"]}','{$array["function"]}','{$array["file"]}','{$array["line"]}','{$array["text"]}','{$array["category"]}','{$array["TASKID"]}')";
		$rom="('{$array["zdate"]}','{$array["function"]}','{$array["file"]}','{$array["line"]}','{$array["text"]}','{$array["category"]}')";
		$TRW[]=$rom2;
		$f[$tableName][]=$rom;
		if(count($f[$tableName])>1500){
			system_admin_events_inject($f,true);
			$f=array();
		}
		if(count($f)>10){
			system_admin_events_inject($f,true);
			$f=array();
		}
		
		@unlink($targetFile);
	}
	
	$q=new mysql();
	if(count($TRW)>0){
		$q->QUERY_SQL("$prefix" .@implode(",", $TRW),"artica_events");
	}
	

	
	system_admin_events_inject($f);
	loadavg_logs();
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::".__LINE__." ->END\n";}
}

function system_admin_events_inject($f,$nooptimize=false){
	if(count($f)==0){return;}	
	$tq=new mysql_builder();
	$sock=new sockets();
	$q=new mysql();	
	
		while (list ($tablename, $rows) = each ($f) ){
			if(!$tq->CheckTableTaskEvents($tablename)){
				WriteMyLogs("system_admin_events:: $tablename: `CheckTableTaskEvents failed`",__FUNCTION__,__FILE__,__LINE__);
				continue;
			}
			$chkTables[$tablename]=true;
			$prefix="INSERT IGNORE INTO `$tablename` (`zDate`,`function`,`filename`,`line`,`description`,`category`) VALUES ";
			$sql=$prefix.@implode(",", $rows);
			$q->QUERY_SQL($sql,"artica_events");
			if(!$q->ok){
				WriteMyLogs("system_admin_events_inject:: $tablename: `$q->mysql_error`",__FUNCTION__,__FILE__,__LINE__);
				writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
				if(preg_match("#Can't find file#", "$q->mysql_error")){
					if($tablename=="artica_events"){continue;}
					$q->QUERY_SQL("DROP TABLE $tablename","artica_events");
					$tq->CheckTableTaskEvents($tablename);
					$q->QUERY_SQL($sql,"artica_events");
				}
			}else{
				if($GLOBALS["VERBOSE"]){echo "$tablename (".count($rows)." rows)\n";}
			}			
			
		}
	
	

	$settings=unserialize(base64_decode($sock->GET_INFO("FcronSchedulesParams")));
	if(!is_numeric($settings["max_events"])){$settings["max_events"]="10000";}
	if(count($chkTables)==0){return;}	
	
		while (list ($tablename, $rows) = each ($chkTables) ){
			$NumRows=$q->COUNT_ROWS("$tablename", "artica_events");
			if($NumRows>$settings["max_events"]){
				$toDelete=$NumRows-$settings["max_events"];
				$q->QUERY_SQL("DELETE FROM `$tablename` ORDER BY zDate LIMIT $toDelete","artica_events");
				if(!$q->ok){
				 if(preg_match("#Got error 134 from storage engine#i", $q->mysql_error)){
					$q->QUERY_SQL("REPAIR TABLE `$tablename` QUICK","artica_events");
					$q->QUERY_SQL("DELETE FROM `$tablename` ORDER BY zDate LIMIT $toDelete","artica_events");
					}
				if(!$q->ok){writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
			}else{
				if(!$nooptimize){$q->QUERY_SQL("OPTIMIZE TABLE `$tablename`","artica_events");}
				}
			}	
		}
		
	
}

function vsftpd_admin_mysql_check($nopid=false){
	$f=array();
	$unix=new unix();
	if($nopid){
	
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}
		$t=0;
	
	}
	
	$sock=new sockets();
	$users=new usersMenus();
	$hostname=$unix->hostname_g();
	$BaseWorkDir="{$GLOBALS["ARTICALOGDIR"]}/vsftpd_admin_mysql";
	if(!is_dir($BaseWorkDir)){return;}
	if (!$handle = opendir($BaseWorkDir)) {echo "Failed open $BaseWorkDir\n";return;}
	
	
	$hostname=$unix->hostname_g();
	$q=new mysql();
	if(!$q->test_mysql_connection()){return;}
	
	
	$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`vsftpd_admin_mysql` (
		`ID` int(11) NOT NULL AUTO_INCREMENT,
		`zDate` TIMESTAMP NOT NULL ,
		`content` MEDIUMTEXT NOT NULL ,
		`hostname` VARCHAR( 255 ),
		`subject` VARCHAR( 255 ) NOT NULL ,
		`function` VARCHAR( 60 ) NOT NULL ,
		`filename` VARCHAR( 50 ) NOT NULL ,
		`line` INT( 10 ) NOT NULL ,
		`severity` smallint( 1 ) NOT NULL ,
		`TASKID` BIGINT UNSIGNED ,
		PRIMARY KEY (`ID`),
		  KEY `zDate` (`zDate`),
		  KEY `subject` (`subject`),
		  KEY `hostname` (`hostname`),
		  KEY `function` (`function`),
		  KEY `filename` (`filename`),
		  KEY `severity` (`severity`)
		) ENGINE=MYISAM;";
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){echo $q->mysql_error."\n";return;}

	
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		if($unix->file_time_min($targetFile)>240){@unlink($targetFile);continue;}
		$array=unserialize(@file_get_contents($targetFile));
		if(!is_array($array)){@unlink($targetFile);continue;}
	
		if(!is_numeric($array["TASKID"])){$array["TASKID"]=0;}
		$content=mysql_escape_string2($array["text"]);
		$subject=mysql_escape_string2($array["subject"]);
	
		$zdate=$array["zdate"];
		$function=$array["function"];
		$file=$array["file"];
		$line=$array["line"];
		$TASKID=$array["TASKID"];
		$severity=$array["severity"];
	
		$q->QUERY_SQL("INSERT IGNORE INTO `vsftpd_admin_mysql`
				(`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`,`hostname`) VALUES
				('$zdate','$content','$subject','$function','$file','$line','$severity','$hostname')","artica_events");
	
		if(!$q->ok){return;}
	
		@unlink($targetFile);
	
	}
	
	
}


function sarg_admin_events_checks($nopid=false){
	$f=array();
	$unix=new unix();

	if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/sarg_admin_events")){return;}
	$q=new mysql();
	$q->BuildTables();
	
	$prefix="INSERT IGNORE INTO sarg_admin_events 
			(`zDate`,`function`,`filename`,`line`,`description`,`category`,`TASKID`) VALUES ";

	
	if (!$handle = opendir("{$GLOBALS["ARTICALOGDIR"]}/sarg_admin_events")){return;}

	$countDeFiles=0;
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$filename="{$GLOBALS["ARTICALOGDIR"]}/sarg_admin_events/$filename";

		$filetime=$unix->file_time_min($filename);
		if($filetime>240){@unlink($filename);continue;}

		$array=unserialize(@file_get_contents($filename));
		if(!is_array($array)){@unlink($filename);continue;}
		if(!is_numeric($array["TASKID"])){$array["TASKID"]=0;}
		$array["text"]=substr($array["text"], 0,254);
		$array["text"]=mysql_escape_string2($array["text"]);
		
		$line="('{$array["zdate"]}','{$array["function"]}','{$array["file"]}','{$array["line"]}','{$array["text"]}','{$array["category"]}','{$array["TASKID"]}')";
		$q->QUERY_SQL($prefix.$line,"artica_events");
		if(!$q->ok){echo $q->mysql_error;continue;}
		@unlink($filename);
	}

	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::".__LINE__." ->END\n";}

}


function udfbguard_admin_events($nopid=false){
	$f=array();
	$unix=new unix();

	
	$q=new mysql();	
	$q->BuildTables();
	if(!$q->TABLE_EXISTS('ufdbguard_admin_events','artica_events',true)){return;mysql_admin_events_check();}
	$prefix="INSERT IGNORE INTO ufdbguard_admin_events (`zDate`,`function`,`filename`,`line`,`description`,`category`,`TASKID`) VALUES ";
	
	@mkdir("{$GLOBALS["ARTICALOGDIR"]}/ufdbguard_admin_events",0755,true);
	if (!$handle = opendir("{$GLOBALS["ARTICALOGDIR"]}/ufdbguard_admin_events")){return;}
	
	$countDeFiles=0;
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$filename="{$GLOBALS["ARTICALOGDIR"]}/ufdbguard_admin_events/$filename";	
		
		$filetime=$unix->file_time_min($filename);
		if($filetime>240){@unlink($filename);continue;}
		
		$array=unserialize(@file_get_contents($filename));
		if(!is_array($array)){@unlink($filename);continue;}			
			
		if($array["category"]=="ufdbguard-service"){udfbguard_admin_events_smtp($array);}
			
		if(!is_numeric($array["TASKID"])){$array["TASKID"]=0;}
		$tableName="TaskSq{$array["TASKID"]}";
		
		
		$array["text"]=mysql_escape_string2($array["text"]);
		WriteMyLogs("ufdbguard_admin_events:{$array["function"]}/{$array["file"]}: Task  `{$array["TASKID"]}` ". strlen("{$array["text"]}")."bytes",__FUNCTION__,__FILE__,__LINE__);
		$f[$tableName][]="('{$array["zdate"]}','{$array["function"]}','{$array["file"]}','{$array["line"]}','{$array["text"]}','{$array["category"]}')";
		@unlink($filename);
	}
	
	system_admin_events_inject($f);
	udfbguard_update_events();
	sarg_admin_events_checks();
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::".__LINE__." ->END\n";}
	
}
function udfbguard_update_events($nopid=false){
	if($GLOBALS["VERBOSE"]){echo "udfbguard_update_events\n";}
	$f=array();
	if($nopid){
		$unix=new unix();
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}
		$t=0;

	}

	$q=new mysql_squid_builder();
	if($GLOBALS["VERBOSE"]){echo "udfbguard_update_events::".__LINE__." ->CheckTables()\n";}
	$q->CheckTables();
	if(!$q->TABLE_EXISTS('webfilter_updateev','artica_events',true)){
		if($GLOBALS["VERBOSE"]){echo "udfbguard_update_events:: webfilter_updateev no such table...".__LINE__." ->CheckTables()\n";}
		$q->CheckTables();
	}
	$prefix="INSERT IGNORE INTO webfilter_updateev (`zDate`,`function`,`filename`,`line`,`description`,`category`,`TASKID`) VALUES ";
	
	if($GLOBALS["VERBOSE"]){echo "udfbguard_update_events::".__LINE__." ->{$GLOBALS["ARTICALOGDIR"]}/ufdbguard_update_events/*\n";}
	foreach (glob("{$GLOBALS["ARTICALOGDIR"]}/ufdbguard_update_events/*") as $filename) {
		$array=unserialize(@file_get_contents($filename));
		if($GLOBALS["VERBOSE"]){echo "$filename\n";}
		if(!is_array($array)){
			if($GLOBALS["VERBOSE"]){echo "$filename not an array\n";}
			@unlink($filename);continue;}

		
		if(!is_numeric($array["TASKID"])){$array["TASKID"]=0;}
		$array["text"]=mysql_escape_string2($array["text"]);
		WriteMyLogs("udfbguard_update_events:{$array["function"]}/{$array["file"]}: Task  `{$array["TASKID"]}` ". strlen("{$array["text"]}")."bytes",__FUNCTION__,__FILE__,__LINE__);
		$f[]="('{$array["zdate"]}','{$array["function"]}','{$array["file"]}','{$array["line"]}','{$array["text"]}','{$array["category"]}','{$array["TASKID"]}')";
		@unlink($filename);
	}
	
	if(count($f)>0){
		$q->QUERY_SQL($prefix.@implode(",", $f));
		if($GLOBALS["VERBOSE"]){echo $prefix.@implode(",", $f)."\n";}
		if(!$q->ok){writelogs($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);}
	}
	
	if($GLOBALS["VERBOSE"]){echo "udfbguard_update_events::".__LINE__." ->END\n";}
	


}
function udfbguard_admin_events_smtp($array){
	include_once(dirname(__FILE__) . '/ressources/class.mail.inc');
	include_once(dirname(__FILE__)."/ressources/smtp/class.phpmailer.inc");
	
	if(count($array)==0){return;}
	
	$users=new usersMenus();
	$sock=new sockets();
	$UfdbguardSMTPNotifs=unserialize(base64_decode($sock->GET_INFO("UfdbguardSMTPNotifs")));
	if(!isset($UfdbguardSMTPNotifs["ENABLED"])){return ;}
	if($UfdbguardSMTPNotifs["ENABLED"]==0){return ;}
	$smtp_dest=$UfdbguardSMTPNotifs["smtp_dest"];
	$smtp_sender=$UfdbguardSMTPNotifs["smtp_sender"];
	if($smtp_dest==null){return;}
	if($smtp_sender==null){$smtp_sender="root@artica.localhost.localdomain";}
	
	while (list ($a, $b) = each ($array)){$TEXTZ[]="$a = $b";}
	
	$subject="[$users->hostname]: Web filtering service notification";
	$text=@implode("\r\n", $TEXTZ);
	$mail = new PHPMailer(true);
	$mail->IsSMTP();
	$mail->AddAddress($smtp_dest,$smtp_dest);
	$mail->AddReplyTo($smtp_sender,$smtp_sender);
	$mail->From=$smtp_sender;
	$mail->FromName=$smtp_sender;
	$mail->Subject=$subject;
	$mail->Body=$text;
	$mail->Host=$UfdbguardSMTPNotifs["smtp_server_name"];
	$mail->Port=$UfdbguardSMTPNotifs["smtp_server_port"];
	
	if(($UfdbguardSMTPNotifs["smtp_auth_user"]<>null) && ($UfdbguardSMTPNotifs["smtp_auth_passwd"]<>null)){
		$mail->SMTPAuth=true;
		$mail->Username=$UfdbguardSMTPNotifs["smtp_auth_user"];
		$mail->Password=$UfdbguardSMTPNotifs["smtp_auth_passwd"];
		if($UfdbguardSMTPNotifs["tls_enabled"]==1){$mail->SMTPSecure = 'tls';}
		if($UfdbguardSMTPNotifs["ssl_enabled"]==1){$mail->SMTPSecure = 'ssl';}
	
	
	}
	
	$mail->Send();	
	
	
}


function mysql_admin_events_check($nopid=false){
	$f=array();
	$unix=new unix();
	if($nopid){
		
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}	
		$t=0;		
		
	}	
	
	$q=new mysql();	
	if(!$q->TABLE_EXISTS('mysql_events','artica_events')){$q->BuildTables();}
	
	$users=new usersMenus();
	$hostname=$users->hostname;
	$prefix="INSERT IGNORE INTO mysql_events (`zDate`,`function`,`process`,`line`,`description`,`category`,`servername`) VALUES ";
	@mkdir("{$GLOBALS["ARTICALOGDIR"]}/mysql_admin_events",0755,true);
		
	
	if (!$handle = opendir("{$GLOBALS["ARTICALOGDIR"]}/mysql_admin_events")){return;}
	$countDeFiles=0;
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$filename="{$GLOBALS["ARTICALOGDIR"]}/mysql_admin_events/$filename";	

		$timeFile=$unix->file_time_min($filename);
		if($timeFile>240){@unlink($filename);continue;}
		
		$array=unserialize(@file_get_contents($filename));
		if(!is_array($array)){@unlink($filename);continue;}
		
		$array["text"]=addslashes($array["text"]);
		$f[]="('{$array["zdate"]}','{$array["function"]}','{$array["file"]}','{$array["line"]}','{$array["text"]}','{$array["category"]}','$hostname')";
		@unlink($filename);
		
		if(count($f)>500){
			$sql=$prefix.@implode(",", $f);
			$q->QUERY_SQL($sql,"artica_events");
			if(!$q->ok){writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
			$f=array();
		}
	
	}
	
	if(count($f)>0){
		$sql=$prefix.@implode(",", $f);
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
	
	}
	
	
	update_events_check();
	
	
}


function clean_mysql_events(){
	$sock=new sockets();
	$unix=new unix();
	
	
	$file_time="/etc/artica-postfix/pids/". basename(__FILE__).".".__FUNCTION__.".time";
	if(!$GLOBALS["VERBOSE"]){
		if($unix->file_time_min($file_time)<300){return;}
		@unlink($file_time);
		@file_put_contents($file_time,time());
	
	}
	$settings=unserialize(base64_decode($sock->GET_INFO("FcronSchedulesParams")));
	if(!is_numeric($settings["max_events"])){$settings["max_events"]="10000";}
	$q=new mysql();
	
	$array["artica_events"]=true;
	$array["artica_update_task"]=true;
	$array["hotspot_admin_mysql"]=true;
	$array["stats_admin_events"]=true;
	$array["squid_admin_purge"]=true;
	$array["system_failover_events"]=true;
	$array["system_admin_events"]=true;
	$array["system_rotate_events"]=true;
	$array["update_events"]=true;
	$array["squid_admin_mysql"]=true;
	$array["squid_admin_enforce"]=true;
	$array["postfix_admin_mysql"]=true;
	$array["webupdate_admin_mysql"]=true;
	$array["nginx_admin_mysql"]=true;
	$array["mysql_admin_mysql"]=true;
	$array["system_admin_mysql"]=true;
	$array["meta_admin_mysql"]=true;
	$array["cyrus_admin_mysql"]=true;
	$array["apache_admin_mysql"]=true;
	$array["vsftpd_admin_mysql"]=true;
	$array["blackwhite_admin_mysql"]=true;
	$array["auth_events"]=true;
	
	$max=count($array);$c=0;
	if($GLOBALS["VERBOSE"]){echo "Checking $c tables\n";}
	while (list ($table, $lib) = each ($array) ){
		$c++;
	
		
		if(!$q->TABLE_EXISTS($table, "artica_events")){
			if($GLOBALS["VERBOSE"]){echo "$table: No such table\n";}
			continue;
		}
		$FileData="/var/lib/mysql/artica_events/$table.MYD";
		$NumRows=$q->COUNT_ROWS($table, "artica_events");
		$size=@filesize($FileData);
		$size=$size/1024;
		$size=$size/1024;
		$size=round($size,2);
		if($GLOBALS["VERBOSE"]){echo "$table:[$c/$max] $NumRows rows, {$size}MB Max rows:{$settings["max_events"]}\n";}
		
		if($size>500){
			if($GLOBALS["VERBOSE"]){echo "$table is more than 500MB > purge it\n";}
			$q->QUERY_SQL("TRUNCATE TABLE `$table` ","artica_events");
			continue;
		}
		
	
		
		if($NumRows>$settings["max_events"]){
			$toDelete=$NumRows-$settings["max_events"];
			if($GLOBALS["VERBOSE"]){echo "$table DELETING $toDelete rows\n";}
			$q->QUERY_SQL("DELETE FROM `$table` ORDER BY zDate LIMIT $toDelete","artica_events");
			continue;
		}
		
		
		$q->QUERY_SQL("DELETE FROM `$table` WHERE zDate<DATE_SUB(NOW(),INTERVAL 60 DAY)","artica_events");
	}
	

	if($q->TABLE_EXISTS("evnts", "artica_events")){
		$q->QUERY_SQL("DELETE FROM evnts WHERE zDate<DATE_SUB(NOW(),INTERVAL 15 DAY)","artica_events");
	}
	
	if($q->TABLE_EXISTS("sys_alert", "artica_events")){
		$q->QUERY_SQL("DELETE FROM sys_alert WHERE zDate<DATE_SUB(NOW(),INTERVAL 7 DAY)","artica_events");
	}
	
	if($q->TABLE_EXISTS("ps_mem_tot", "artica_events")){
		$q->QUERY_SQL("DELETE FROM ps_mem_tot WHERE zDate<DATE_SUB(NOW(),INTERVAL 35 DAY)","artica_events");
	}
	
	if($q->TABLE_EXISTS("ps_mem", "artica_events")){
		$q->QUERY_SQL("DELETE FROM ps_mem WHERE zDate<DATE_SUB(NOW(),INTERVAL 35 DAY)","artica_events");
	}

	

	
	if($q->TABLE_EXISTS("loadavg", "artica_events")){
		$q->QUERY_SQL("DELETE FROM loadavg WHERE stime < DATE_SUB( NOW( ) , INTERVAL 7 DAY )","artica_events");
	}
	
	if($q->TABLE_EXISTS("rotate_admin_events", "artica_events")){
		$q->QUERY_SQL("DELETE FROM rotate_admin_events WHERE zDate < DATE_SUB( NOW( ) , INTERVAL 7 DAY )","artica_events");
	}

	if($q->TABLE_EXISTS("rdpproxy_admin_mysql", "artica_events")){
		$q->QUERY_SQL("DELETE FROM rdpproxy_admin_mysql WHERE zDate < DATE_SUB( NOW( ) , INTERVAL 30 DAY )","artica_events");
	}	
	
	
	$q=new mysql_squid_builder();
	
	if($q->TABLE_EXISTS("wpad_events")){
		if($q->COUNT_ROWS("wpad_events")>0){
			$q->QUERY_SQL("DELETE FROM wpad_events WHERE zDate < DATE_SUB( NOW( ) , INTERVAL 7 DAY )");
		}
	}
	
	
	
	
	
	
	
	
}


function update_events_check($nopid=false){
	$f=array();
	if($nopid){
		$unix=new unix();
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}	
		$t=0;		
		
	}	
	
	$q=new mysql();	
	if(!$q->TABLE_EXISTS('update_events','artica_events')){$q->BuildTables();}
	if(!$q->TABLE_EXISTS('update_events','artica_events',true)){return;}
	$users=new usersMenus();
	$hostname=$users->hostname;
	$prefix="INSERT IGNORE INTO update_events (`zDate`,`function`,`process`,`line`,`description`,`category`,`servername`) VALUES ";
	foreach (glob("{$GLOBALS["ARTICALOGDIR"]}/update_admin_events/*") as $filename) {
		$array=unserialize(@file_get_contents($filename));
		if(!is_array($array)){
			$array["text"]=basename($filename)." is not an array, skip event ".@file_get_contents($filename);
			$array["date"]=date('Y-m-d H:i:s');
			$array["pid"]=getmypid();
			$array["function"]=__FUNCTION__;
			$array["category"]="sys-update";
			$array["file"]=basename(__FILE__);
			$array["line"]=__LINE__;
		}			
			
			
			
		$array["text"]=addslashes($array["text"]);
		$f[]="('{$array["zdate"]}','{$array["function"]}','{$array["file"]}','{$array["line"]}','{$array["text"]}','{$array["category"]}','$hostname')";
		@unlink($filename);
	}
	
	if(count($f)>0){$sql=$prefix.@implode(",", $f);
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){
			writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		}
	
	}
	
	$f=array();
	
	foreach (glob("{$GLOBALS["ARTICALOGDIR"]}/artica-update-*.debug") as $filename) {
		$file=basename($filename);
		$unix=new unix();
		$last_modified = filemtime($filename);
		$array["zdate"]=date('Y-m-d H:i:s',$last_modified);
		$array["pid"]=0;
		$array["function"]="update-deamon";
		$array["category"]="firmware-update";
		$array["file"]="artica-update";
		$array["line"]=0;
		$array["text"]=addslashes(@file_get_contents($filename));
		$f[]="('{$array["zdate"]}','{$array["function"]}','{$array["file"]}','{$array["line"]}','{$array["text"]}','{$array["category"]}','$hostname')";
		@unlink($filename);
		
		
	}
	
	
if(count($f)>0){$sql=$prefix.@implode(",", $f);
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){
			writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		}
	
	}	
	
	


	
	
}


function dhcpd_logs($nopid=false){
	$f=array();
	if($nopid){
		$unix=new unix();
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}	
		$t=0;		
		
	}
	$q=new mysql();	
	if(!$q->TABLE_EXISTS('dhcpd_logs','artica_events')){$q->BuildTables();}
	if(!$q->TABLE_EXISTS('dhcpd_logs','artica_events',true)){return;}
	$prefix="INSERT IGNORE INTO dhcpd_logs (`zDate`,`description`) VALUES ";
	foreach (glob("{$GLOBALS["ARTICALOGDIR"]}/dhcpd/*") as $filename) {
		$sqlcontent=@file_get_contents($filename);
		if(trim($sqlcontent)==null){@unlink($filename);continue;}
		
		$f[]=$sqlcontent;
		@unlink($filename);
	}
	
	if(count($f)>0){$sql=$prefix.@implode(",", $f);
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){
			writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		}
	
	}
	
	
	
	
}



function crossroads($nopid=false){
	$f=array();
	if($nopid){
		$unix=new unix();
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}	
		$t=0;		
		
	}
	$q=new mysql();	
	if(!$q->TABLE_EXISTS('crossroads_events','artica_events')){$q->BuildTables();}
	if(!$q->TABLE_EXISTS('crossroads_events','artica_events',true)){return;}
	$prefix="INSERT IGNORE INTO crossroads_events (`zDate`,`instance_id`,`function`,`line`,`description`) VALUES ";
	foreach (glob("{$GLOBALS["ARTICALOGDIR"]}/crossroads/*") as $filename) {
		$array=unserialize(@file_get_contents($filename));
		if(!is_array($array)){@unlink($filename);continue;}		
		$array["TEXT"]=addslashes($array["TEXT"]);
		$f[]="('{$array["TIME"]}','{$array["ID"]}','{$array["FUNCTION"]}','{$array["LINE"]}','{$array["TEXT"]}')";
		@unlink($filename);
	}
	
	if(count($f)>0){$sql=$prefix.@implode(",", $f);
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){
			writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		}
	
	}
	

	
}

function squid_rt_mysql_failed(){
	if($GLOBALS["VERBOSE"]){echo "Start squid_rt_mysql_failed()\n";}
	$DirPath="{$GLOBALS["ARTICALOGDIR"]}/mysql-failed";
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if(!is_dir($DirPath)){
		
		if($GLOBALS["VERBOSE"]){echo "$DirPath, no such directory\n";}
		return;}
	///etc/artica-postfix/pids/exec.syslog-engine.php.squid_rt_mysql_failed.time
	if($GLOBALS["VERBOSE"]){echo "timefile=$timefile\n";}
	
	$pid=@file_get_contents($pidfile);
	
	
	if(!$GLOBALS["FORCE"]){
		if($pid<100){$pid=null;}
		$unix=new unix();
		if($unix->process_exists($pid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}return;}
		$timeexec=$unix->file_time_min($timefile);
		if($timeexec<5){if($GLOBALS["VERBOSE"]){echo "Only each 5mn - current {$timeexec}mn, use --force to bypass\n";}return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}
	
	if($GLOBALS["VERBOSE"]){echo "Time File: $timefile\n";}
	@file_put_contents($timefile, time());
	if(system_is_overloaded(basename(__FILE__))){die();}
	
	
	if(system_is_overloaded()){if($GLOBALS["VERBOSE"]){echo "system_is_overloaded\n";}return;}
	if (!$handle = opendir($DirPath)) {if($GLOBALS["VERBOSE"]){echo "$DirPath ERROR\n";}return;}
	$q=new mysql_squid_builder();
	if($GLOBALS["VERBOSE"]){echo "Start Loop\n";}
	
	
	
	$c=0;
	while (false !== ($file = readdir($handle))) {
		if ($file == "."){continue;}
		if ($file == ".."){continue;}
		if(is_dir("$DirPath/$file")){if($GLOBALS["VERBOSE"]){echo "$DirPath/$file -> DIR\n";} continue;}
		$filename="$DirPath/$file";
		if($GLOBALS["VERBOSE"]){echo "$filename -> file_time_min();\n";}
		$timeTemp=$unix->file_time_min($filename);
		if($GLOBALS["VERBOSE"]){echo "$filename {$timeTemp}Mn\n";}
		if($timeTemp>180){@unlink($filename);continue;}
		$sql=@file_get_contents($filename);
		if($GLOBALS["VERBOSE"]){echo "->QUERY_SQL($sql)\n";}
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			if($GLOBALS["VERBOSE"]){echo "Error$q->mysql_error\n";}
			continue;}
		@unlink($filename);
		if($c>1000){
			if(system_is_overloaded()){if($GLOBALS["VERBOSE"]){echo "system_is_overloaded\n";}return;}
			$c=0;
		}
		
	}
}


function load_stats(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	///etc/artica-postfix/pids/exec.syslog-engine.php.load_stats.time
	if($GLOBALS["VERBOSE"]){echo "timefile=$timefile\n";}
	
	$pid=@file_get_contents($pidfile);
	
	
	if(!$GLOBALS["FORCE"]){
		if($pid<100){$pid=null;}
		$unix=new unix();
		if($unix->process_exists($pid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}return;}
		$timeexec=$unix->file_time_min($timefile);
		if($timeexec<5){if($GLOBALS["VERBOSE"]){echo "Only each 5mn - current {$timeexec}mn, use --force to bypass\n";}return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}
	
	if($GLOBALS["VERBOSE"]){echo "Time File: $timefile\n";}
	@file_put_contents($timefile, time());
	if(system_is_overloaded(basename(__FILE__))){die();}
	$q=new mysql_squid_builder();
	
	sys_load();
	
	
}

function seeker_log($text,$line){
	$unix=new unix();
	$unix->events($text,"/var/log/seeker.log",false,"seeker",$line,basename(__FILE__));
}

function seeker(){
	seeker_log("Start seeker()",__LINE__);
	$q=new mysql();
	$sock=new sockets();
	$unix=new unix();
	$f=array();	
	
	$sql="CREATE TABLE IF NOT EXISTS `disk_seeker` (
			   `zDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`ms_read` float,
				`disk` VARCHAR(128),
				`seeks` INT(100),
				`kbs` INT(100),
				KEY `zDate` (`zDate`),
				KEY `disk` (`disk`),
				KEY `kbs` (`kbs`),
				KEY `ms_read` (`ms_read`)
			) ENGINE=MYISAM;
			";
	$q->QUERY_SQL($sql,'artica_events');
	if(!$q->ok){
		seeker_log($q->mysql_error,__LINE__);
		return;
	}	
	
	$DirPath="{$GLOBALS["ARTICALOGDIR"]}/seeker-queue";
	if(!is_dir($DirPath)){@mkdir($DirPath,0755,true);}
	if(system_is_overloaded()){seeker_log("system_is_overloaded, aborting",__LINE__);return;}
	if (!$handle = opendir($DirPath)) {seeker_log("$DirPath ERROR",__LINE__);return;}
	
	$prefix="INSERT IGNORE INTO `disk_seeker` (`zDate`,`ms_read`,`disk`,`seeks`,`kbs`) VALUES ";
	$c=0;
	seeker_log("Start Loop",__LINE__);
	while (false !== ($file = readdir($handle))) {
		if ($file == "."){continue;}
		if ($file == ".."){continue;}
		if(is_dir("$DirPath/$file")){continue;}
		$filename="$DirPath/$file";
		seeker_log("$filename",__LINE__);
		$data=unserialize(@file_get_contents($filename));
		if(!is_array($data)){
			seeker_log("$file not an array...",__LINE__);
			@unlink($filename);
			continue;
		}
		
		
		$seeks=$data["SEEKS"];
		$disk=$data["DISK"];
		$ms=$data["MS"];
		$time=$data["time"];
		$zdate=date("Y-m-d H:i:s",$time);
		$kbs=$seeks*4096;
		$kbs=$kbs/1024;
		$kbs=round($kbs);
		if(!is_numeric($seeks)){
			seeker_log("$file seek not a numeric value",__LINE__);
			@unlink($filename);
			continue;
		}
		$line="('$zdate','$ms','$disk','$seeks','$kbs')";
		seeker_log($line,__LINE__);
		$q->QUERY_SQL("$prefix $line","artica_events");
		if(!$q->ok){
			seeker_log($q->mysql_error,__LINE__);
			return;
		}
		$c++;
		@unlink($filename);
	}	
	
	seeker_log("$c injected files",__LINE__);
	if($q->TABLE_EXISTS("disk_seeker", "artica_events")){
		$q->QUERY_SQL("DELETE FROM disk_seeker WHERE zDate<DATE_SUB(NOW(),INTERVAL 7 DAY)","artica_events");
	}
	
}

function sys_load(){
	if($GLOBALS["VERBOSE"]){echo "Start sys_load()\n";}
	$q=new mysql();
	$sock=new sockets();
	$unix=new unix();
	$f=array();

	sys_alert();
	$EnableSyslogDB=$sock->GET_INFO("EnableSyslogDB");
	if(!is_numeric($EnableSyslogDB)){$EnableSyslogDB=0;}	
	if($EnableSyslogDB==1){
		$php=$unix->LOCATE_PHP5_BIN();
		$nohup=$unix->find_program("nohup");
		shell_exec("$nohup $php /usr/share/artica-postfix/exec.logrotate.php --convert >/dev/null 2>&1 &");
	}
		
	
}

function sys_alert(){

}
function ps_mem($nopid=false){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	if($nopid){
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);return;}	
		$t=0;		
		@unlink($pidfile);
		@file_put_contents($pidfile, getmypid());
	}
	
	$timefile=$unix->file_time_min($pidfile);
	if($timefile<5){
		writelogs("Minimal time = 5mn, current = $timefile",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	
	
	
	$f=array();
	$q=new mysql();
	$prefix="INSERT IGNORE INTO ps_mem (zmd5,zDate,process,memory) VALUES ";
	if($GLOBALS["VERBOSE"]){writelogs("Starting glob()...",__FUNCTION__,__FILE__,__LINE__);}
	foreach (glob("{$GLOBALS["ARTICALOGDIR"]}/ps-mem/*") as $filename) {
		$array=unserialize(@file_get_contents($filename));
		if(!is_array($array)){@unlink($filename);continue;}
		$md5=md5(serialize($array));
		$f[]="('$md5','{$array["time"]}','{$array["process"]}','{$array["mem"]}')";
		if(count($f)>500){
			$sql=$prefix.@implode(",", $f);
			$f=array();
			$q->QUERY_SQL($sql,"artica_events");
			if(!$q->ok){writelogs("Fatal, $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
		}
		@unlink($filename);
		
	}
	
	if(count($f)>0){
			$sql=$prefix.@implode(",", $f);
			$f=array();
			$q->QUERY_SQL($sql,"artica_events");
			if(!$q->ok){writelogs("Fatal, $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
		}	
		
		
	
		
// -------------------------------------------------------------------------

	$f=array();
	$prefix="INSERT IGNORE INTO ps_mem_tot (zDate,mem) VALUES ";
	foreach (glob("{$GLOBALS["ARTICALOGDIR"]}/ps-mem-tot/*") as $filename) {	
		$array=unserialize(@file_get_contents($filename));
		if(!is_array($array)){@unlink($filename);continue;}
		$md5=md5(serialize($array));
		$f[]="('{$array["time"]}','{$array["mem"]}')";
		if(count($f)>500){
			$sql=$prefix.@implode(",", $f);
			$f=array();
			$q->QUERY_SQL($sql,"artica_events");
			if(!$q->ok){writelogs("Fatal, $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
		}
		@unlink($filename);
		
	}
	
	if(count($f)>0){
			$sql=$prefix.@implode(",", $f);
			$f=array();
			$q->QUERY_SQL($sql,"artica_events");
			if(!$q->ok){writelogs("Fatal, $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
		}


	
}

		
function snort_logs(){
	
	if(system_is_overloaded()){return;}
	
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){
		echo "Already running pid $pid\n";
		return;
	}	
	$t=0;
	include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
	$q=new mysql();
	foreach (glob("{$GLOBALS["ARTICALOGDIR"]}/snort-queue/*") as $filename) {
		$base=basename($filename);
		if(!preg_match("#([0-9]+)\..+?\.snort#",$base,$re)){@unlink($filename);continue;}
		$zDate=date("Y-m-d H:i:s",$re[1]);
		echo $zDate." -> {$re[1]}\n";
		$array=unserialize(@file_get_contents($filename));
		if(!is_array($array)){@unlink($filename);continue;}
		$local_ipaddr=$array[7];
		$port=$array[8];
		$ipaddr=$array[5];
		if(!isset($GLOBALS["RESOLV"][$ipaddr])){
			$hostname=gethostbyaddr($ipaddr);
			$GLOBALS["RESOLV"][$ipaddr]=$hostname;
		}else{
			$hostname=$GLOBALS["RESOLV"][$ipaddr]=$hostname;
		}
		if(!isset($GLOBALS["GEO"][$ipaddr])){
			if(function_exists("geoip_record_by_name")){
				$record = geoip_record_by_name($ipaddr);
				if ($record) {
					$country=$record["country_name"];
					$GLOBALS["GEO"][$ipaddr]=$country;
				}
			}
		}else{
			$country=$GLOBALS["GEO"][$ipaddr];
		}	
		$infos=$array[1];
		$classification=$array[2];
		if(preg_match("#SCAN.+?Port.+?attempt#",$infos)){$unix->send_email_events("$infos FROM $ipaddr","Country:$country\nHostname:$hostname\nclassification:$classification","security");}

		$proto=$array[4];
		$priority=$array[3];
		if($GLOBALS["VERBOSE"]){echo "$hostname\n";}
		
		
		
		$sql="INSERT IGNORE INTO `snort`
		(`zDate`,`hostname`,`ipaddr` ,`local_ipaddr`,`port`,`infos`,`classification`,`priority`,`proto` ,`country`)
  VALUES('$zDate','$hostname','$ipaddr' ,'$local_ipaddr','$port','$infos','$classification','$priority','$proto' ,'$country')";

		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){
			if($GLOBALS["VERBOSE"]){echo $q->mysql_error."\n";}
			continue;
		}
		$t++;
		@unlink($filename);
	}
	
	if($t>0){
		writelogs("Adding $t entries",__FUNCTION__,__FILE__,__LINE__);
	}
	

	
}


function authlogs(){
	include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
	include_once(dirname(__FILE__) . '/ressources/class.auth.tail.inc');
	include_once(dirname(__FILE__) . '/ressources/class.iptables-chains.inc');
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){echo "Already running pid $pid\n";return;}
	
	$q=new mysql();
	
	$DirPath="{$GLOBALS["ARTICALOGDIR"]}/sshd-failed";
	if(!is_dir($DirPath)){return;}
	if (!$handle = opendir($DirPath)) {return;}
	while (false !== ($file = readdir($handle))) {
		if ($file == "."){continue;}
		if ($file == ".."){continue;}
		$filename="{$GLOBALS["ARTICALOGDIR"]}/sshd-failed/$file";
		if(is_dir($filename)){continue;}
		
		if($unix->file_time_min($filename)>120){
			@unlink($filename);
			continue;
		}
		
		events("Open $filename",__FUNCTION__,__FILE__,__LINE__);
		$array=unserialize(@file_get_contents($filename));
		if(!is_array($array)){
			@unlink($filename);
			continue;
		}
		
		$zdate=date("Y-m-d H:i:s",basename($filename));
		if(is_array($array)){
			while (list ($ip, $uid) = each ($array)){
				$hostname=gethostbyaddr($ip);
				if(function_exists("geoip_record_by_name")){
					$record = geoip_record_by_name($ip);
					if (!$record) {ssh_events("Unable to detect country for $ip",__FUNCTION__,__FILE__,__LINE__);}else{
						$Country=$record["country_name"];
					}
				}
				$Country=addslashes($Country);
				$hostname=addslashes($hostname);
				$uid=addslashes($uid);
				ssh_events("SSH Failed $ip $hostname ($Country)",__FUNCTION__,__FILE__,__LINE__);
				$sql="INSERT IGNORE INTO auth_events (ipaddr,hostname,success,uid,zDate,Country) VALUES ('$ip','$hostname','0','$uid','$zdate','$Country')";
				$q->QUERY_SQL($sql,"artica_events");
				if(!$q->ok){@unlink($filename);}
			}
		}
	}
	
	foreach (glob("{$GLOBALS["ARTICALOGDIR"]}/sshd-success/*") as $filename) {
		$array=unserialize(@file_get_contents($filename));
		$zdate=date("Y-m-d H:i:s",basename($filename));
		while (list ($ip, $uid) = each ($array)){
			if(!isset($GLOBALS["HOSTNAME"][$ip])){$GLOBALS["HOSTNAME"][$ip]=gethostbyaddr($ip);}
			$hostname=$GLOBALS["HOSTNAME"][$ip];
			
			if(!is_file("/usr/share/GeoIP/GeoIPCity.dat")){
				_UpdateGeoip();
			}
			
			if(function_exists("geoip_record_by_name")){
					$record = geoip_record_by_name($ip);
					if (!$record) {ssh_events("Unable to detect country for $ip",__FUNCTION__,__FILE__,__LINE__);}else{
						$Country=$record["country_name"];
					}
				}	
			$Country=addslashes($Country);
			$hostname=addslashes($hostname);
			$uid=addslashes($uid);					
			$sql="INSERT IGNORE INTO auth_events (ipaddr,hostname,success,uid,zDate,Country) VALUES ('$ip','$hostname','1','$uid','$zdate','$Country')";
			ssh_events("SSH Success $ip $hostname ($Country) `$sql`",__FUNCTION__,__FILE__,__LINE__);
			$q->QUERY_SQL($sql,"artica_events");
			if(!$q->ok){ssh_events($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);}else{@unlink($filename);}}
		}

		authfw();
		snort_logs();
		loadavg_logs();
		clamd_mem();
}

function _UpdateGeoip(){
	if(isset($GLOBALS["UpdateGeoip_executed"])){return;}
	$GLOBALS["UpdateGeoip_executed"]=true;
	$unix=new unix();
	$ln=$unix->find_program("ln");
	$database="/usr/share/GeoIP/GeoIP.dat";
	if(!is_file($database)){_installgeoip();return null;}
	if(!is_file("/usr/local/share/GeoIP/GeoIPCity.dat")){
		if(is_file("/usr/local/share/GeoIP/GeoLiteCity.dat")){
			shell_exec("$ln -s /usr/local/share/GeoIP/GeoLiteCity.dat /usr/local/share/GeoIP/GeoIPCity.dat >/dev/null 2>&1");
		}
	}


	if(!is_file("/usr/share/GeoIP/GeoIPCity.dat")){
		if(is_file("/usr/share/GeoIP/GeoLiteCity.dat")){
			system("$ln -s /usr/share/GeoIP/GeoLiteCity.dat /usr/share/GeoIP/GeoIPCity.dat >/dev/null 2>&1");
		}
	}

	if(!function_exists("geoip_record_by_name")){installgeoip();return null;}

}

function _installgeoip(){
	if(isset($GLOBALS["installgeoip_executed"])){return;}
	$GLOBALS["installgeoip_executed"]=true;

	$unix=new unix();
	if(is_file("/etc/artica-postfix/FROM_ISO")){$time=$unix->file_time_min("/etc/artica-postfix/FROM_ISO");if($time<60){return;}}
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$pecl=$unix->find_program("pecl");

	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.geoip.update.php >/dev/null 2>&1 &");

	if(is_file($pecl)){
		if(!is_file("/etc/artica-postfix/php-geoip-checked")){
			shell_exec("$pecl install geoip");
			shell_exec("/etc/init.d/artica-postfix restart apache");
			@file_put_contents("/etc/artica-postfix/php-geoip-checked",time());
		}
	}

}

function clamd_mem(){
	include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
	$f=array();
	$unix=new unix();
	$q=new mysql();
	if(!$q->TABLE_EXISTS("clamd_mem", "artica_events")){
		$q->QUERY_SQL("CREATE TABLE `artica_events`.`clamd_mem` (`zDate` TIMESTAMP NOT NULL ,`rss` INT( 10 ) NOT NULL ,`vm` INT( 10 ) NOT NULL ,PRIMARY KEY ( `zDate` ))","artica_events");
	}
	$prefix="INSERT IGNORE INTO clamd_mem (zDate,rss,vm) VALUES ";
	
	
	foreach (glob("{$GLOBALS["ARTICALOGDIR"]}/clamd-mem/*") as $filename) {
		events("Open $filename",__FUNCTION__,__FILE__,__LINE__);
		$content=trim(@file_get_contents($filename));
		@unlink($filename);
		if($content==null){continue;}
		$f[]=$content;
		if(count($f)>100){
			$sql=$prefix.@implode(",", $f);
			$f=array();
			$q->QUERY_SQL($sql,"artica_events");
		}
		
	}
	
		if(count($f)>0){
			$sql=$prefix.@implode(",", $f);
			$f=array();
			$q->QUERY_SQL($sql,"artica_events");
		}	
	

	
}

function sessions_logs(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	
	if(system_is_overloaded()){return;}
	$q=new mysql();
	foreach (glob("/usr/share/artica-postfix/ressources/logs/web/queue/sessions/*") as $filename) {
		$base=basename($filename);
		if(!preg_match("#([0-9]+)\.(.+)#",$base,$re)){@unlink($filename);continue;}
		$array=unserialize(@file_get_contents($filename));
		@unlink($filename);
		if(!is_array($array)){
			writelogs("Not an array... $base",__FUNCTION__,__FILE__,__LINE__);
			continue;
		}
		if(strlen($array["SESSION_ID"])<3){
			writelogs("SESSION_ID is null...({$array["SESSION_ID"]}) $base",__FUNCTION__,__FILE__,__LINE__);
			continue;
		}
		$sql="DELETE FROM admin_cnx WHERE session_id='{$array["SESSION_ID"]}'";
		$connected=date('Y-m-d H:i:s',$re[1]);
		$q->QUERY_SQL($sql,"artica_events");
		if(!isset($GLOBALS["HOSTNAME"][$array["ipaddr"]])){$GLOBALS["HOSTNAME"][$array["ipaddr"]]=gethostbyaddr($array["ipaddr"]);}
		$hostname=$GLOBALS["HOSTNAME"][$array["ipaddr"]];
		$sql="INSERT IGNORE INTO admin_cnx(connected,session_id,ipaddr,InterfaceType,webserver,hostname,uid) VALUES
		('$connected','{$array["SESSION_ID"]}','{$array["ipaddr"]}','{$array["interface"]}','{$array["myname"]}',
		'$hostname','{$array["uid"]}')";
		
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
		
	}
	
	$sql="DELETE FROM admin_cnx WHERE connected<DATE_SUB(NOW(), INTERVAL 3600 SECOND)";
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}	
	loadavg_logs();
}




function authfw(){
	if($GLOBALS["VERBOSE"]){echo "authfw()\n";}
	$unix=new unix();
	$iptablesClass=new iptables_chains();
	$iptables=$unix->find_program("iptables");
	$GLOBALS["IPTABLES_WHITELISTED"]=$iptablesClass->LoadWhiteLists();
	$sock=new sockets();
	$GlobalIptablesEnabled=$sock->GET_INFO("GlobalIptablesEnabled");
	if(!is_numeric($GlobalIptablesEnabled)){$GlobalIptablesEnabled=1;}	
			
	
	$c=0;
	foreach (glob("/etc/artica-postfix/sshd-fw/*") as $filename) {
		if($GlobalIptablesEnabled<>1){@unlink($filename);continue;}
		$array=unserialize(@file_get_contents($filename));
		$zdate=date("Y-m-d H:i:s",basename($filename));	
		while (list ($IP, $server_name) = each ($array)){
		if($iptablesClass->isWhiteListed($IP)<>null){@unlink($filename);continue;}
		
		
		
		$cmd="$iptables -A INPUT -s $IP -p tcp --destination-port 22 -j DROP -m comment --comment \"ArticaInstantSSH\"";
		$iptablesClass=new iptables_chains();
		$iptablesClass->serverip=$IP;
		$iptablesClass->servername=$server_name;
		$iptablesClass->rule_string=$cmd;
		$iptablesClass->EventsToAdd="Max SSHD connexions";
		if($iptablesClass->addSSHD_chain()){
			$unix->send_email_events("SSHD Hack!: $server_name [$IP] has been banned to your SSH",
			"Artica anti-hack SSH has banned this ip address","system");
			$c++;
			ssh_events("Add IP:Addr=<$IP>, servername=<{$server_name}> to mysql",__FUNCTION__,__FILE__,__LINE__);
			if($GLOBALS["VERBOSE"]){echo "Add IP:Addr=<$IP>, servername=<{$server_name}> to mysql\n";}
			@unlink($filename);
			}

		}
	}
	
	if($c>0){compile_sshd_rules();}
	loadavg_logs();
}

	function ssh_events($text,$function,$file,$line){
		writelogs($text,$function,$file,$line);
		$pid=@getmypid();
		$filename=basename(__FILE__);
		$date=@date("H:i:s");
		$logFile="{$GLOBALS["ARTICALOGDIR"]}/auth-tail.debug";
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		@fwrite($f, "$pid ".basename(__FILE__)." $text\n");
		@fclose($f);	
		$logFile="{$GLOBALS["ARTICALOGDIR"]}/syslogger.debug";
		if(!isset($GLOBALS["CLASS_UNIX"])){
			include_once(dirname(__FILE__)."/framework/class.unix.inc");
			$GLOBALS["CLASS_UNIX"]=new unix();
		}
		$GLOBALS["CLASS_UNIX"]->events("$filename $text",$logFile);
		}



function compile_sshd_rules(){
	$sock=new sockets();
	$GlobalIptablesEnabled=$sock->GET_INFO("GlobalIptablesEnabled");
	if(!is_numeric($GlobalIptablesEnabled)){$GlobalIptablesEnabled=1;}	
	if($GlobalIptablesEnabled<>1){iptables_delete_all();return;}
	include_once(dirname(__FILE__)."/ressources/class.openssh.inc");
	$q=new mysql();	
	$iptablesClass=new iptables_chains();
	$unix=new unix();
	$openssh=new openssh();
	$SSHDPort=$openssh->main_array["Port"];
	if(!is_numeric($SSHDPort)){$SSHDPort=22;}
	$iptables=$unix->find_program("iptables");
	$GLOBALS["IPTABLES_WHITELISTED"]=$iptablesClass->LoadWhiteLists();	
	$sql="SELECT * FROM iptables WHERE disable=0 AND flux='INPUT' AND local_port=22";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	iptables_delete_all();
		
	
	
	if($GLOBALS["VERBOSE"]){echo "OpenSSH port is $SSHDPort\n";}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$ip=$ligne["serverip"];
		if($iptablesClass->isWhiteListed($ip)){continue;}
		if($GLOBALS["VERBOSE"]){events("ADD REJECT {$ligne["serverip"]} INBOUND PORT 22");}
		if($GLOBALS["VERBOSE"]){ssh_events("ADD REJECT {$ligne["serverip"]} INBOUND PORT 22",__FUNCTION__,__FILE__,__LINE__);}

		/*if($InstantIptablesEventAll==1){
			if($GLOBALS["VERBOSE"]){echo "$ip -> LOG\n";}
			$cmd="$iptables -A INPUT -s $ip -p tcp --destination-port 25 -j LOG --log-prefix \"SMTP DROP: \" -m comment --comment \"ArticaInstantPostfix\"";
			$commands[]=$cmd;
		}*/
		
		$cmd="$iptables -A INPUT -s $ip -p tcp --destination-port $SSHDPort -j DROP -m comment --comment \"ArticaInstantSSH\"";
		$commands[]=$cmd;
	}
	
	if($GLOBALS["VERBOSE"]){echo count($commands)." should be performed\n";}
	
	if(is_array($commands)){
		while (list ($index, $line) = each ($commands) ){
			writelogs($line,__FUNCTION__,__FILE__,__LINE__);
			if($GLOBALS["VERBOSE"]){echo $line."\n";}
			shell_exec($line);
		}
		
		$unix->send_email_events("SSHD Hack ".count($commands)." rules(s) added",null,"system");
		
	}	

	
	
	
}

function iptables_delete_all(){
$unix=new unix();
$iptables_restore=$unix->find_program("iptables-restore");
$iptables_save=$unix->find_program("iptables-save");	
events("Exporting datas iptables-save > /etc/artica-postfix/iptables.conf");
system("$iptables_save > /etc/artica-postfix/iptables.conf");
$data=file_get_contents("/etc/artica-postfix/iptables.conf");
$datas=explode("\n",$data);
$pattern="#.+?ArticaInstantSSH#";	
while (list ($num, $ligne) = each ($datas) ){
		if($ligne==null){continue;}
		if(preg_match($pattern,$ligne)){continue;}
		events("skip rule $ligne from deletion");
		$conf=$conf . $ligne."\n";
		}

events("restoring datas $iptables_restore < /etc/artica-postfix/iptables.new.conf");
file_put_contents("/etc/artica-postfix/iptables.new.conf",$conf);
system("$iptables_restore < /etc/artica-postfix/iptables.new.conf");


}

function events($text){
		$pid=@getmypid();
		$filename=basename(__FILE__);
		$date=@date("H:i:s");
		$logFile="{$GLOBALS["ARTICALOGDIR"]}/auth-tail.debug";
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		@fwrite($f, "$pid ".basename(__FILE__)." $text\n");
		@fclose($f);	
		$logFile="{$GLOBALS["ARTICALOGDIR"]}/syslogger.debug";
		if(!isset($GLOBALS["CLASS_UNIX"])){include_once(dirname(__FILE__)."/framework/class.unix.inc");$GLOBALS["CLASS_UNIX"]=new unix();}
		$GLOBALS["CLASS_UNIX"]->events("$filename $text",$logFile);
}

function loadavg_logs(){
	include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){echo "Already running pid $pid\n";return;}	
	$q=new mysql();
	if(!$q->DATABASE_EXISTS("artica_events")){
		if($GLOBALS["VERBOSE"]){echo "Stop !\n";}
		events_Loadavg("loadavg_logs:: artica_events database does not exists... try to build one".__LINE__);
		$q->BuildTables();
	}
	
	if(!$q->DATABASE_EXISTS("artica_events")){
		if($GLOBALS["VERBOSE"]){echo "Stop !\n";}
		events_Loadavg("loadavg_logs:: artica_events database cannot continue".__LINE__);
		return;
	}	
	
if($GLOBALS["VERBOSE"]){echo "Scan {$GLOBALS["ARTICALOGDIR"]}/loadavg/*\n";}

$COUNT=$unix->COUNT_FILES("{$GLOBALS["ARTICALOGDIR"]}/loadavg");
if($COUNT>5000){
	if (!$handle = opendir("{$GLOBALS["ARTICALOGDIR"]}/loadavg")) {return;}
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$filename="{$GLOBALS["ARTICALOGDIR"]}/loadavg/$filename";
		@unlink($filename);
	}
	return;
}


if (!$handle = opendir("{$GLOBALS["ARTICALOGDIR"]}/loadavg")) {@mkdir("{$GLOBALS["ARTICALOGDIR"]}/loadavg",0755,true);return;}

	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$filename="{$GLOBALS["ARTICALOGDIR"]}/loadavg/$filename";
		if($unix->file_time_min($filename)>240){
			@unlink($filename);
			continue;
		}
		$time=basename($filename);
		$load=@file_get_contents($filename);
		$date=date('Y-m-d H:i:s',$time);
		$sql="INSERT IGNORE INTO loadavg (`stime`,`load`) VALUES ('$date','$load');";
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){events_Loadavg("loadavg_logs:: $q->mysql_error line:".__LINE__);continue;}
		events_Loadavg("loadavg_logs:: success $filename".__LINE__);
		@unlink($filename);
	}
	

	
	
}

function events_Loadavg($text,$function=null,$line=0){
		$filename=basename(__FILE__);
		if(!isset($GLOBALS["CLASS_UNIX"])){
			include_once(dirname(__FILE__)."/framework/class.unix.inc");
			$GLOBALS["CLASS_UNIX"]=new unix();
		}
		$GLOBALS["CLASS_UNIX"]->events("$filename $function:: $text (L.$line)","{$GLOBALS["ARTICALOGDIR"]}/xLoadAvg.debug");	
		}	



function haproxy_events(){
	$qs=new mysql_squid_builder();
	$q=new mysql_haproxy_builder();
	if (!$handle = opendir("{$GLOBALS["ARTICALOGDIR"]}/haproxy-rtm")) {@mkdir("{$GLOBALS["ARTICALOGDIR"]}/haproxy-rtm",0755,true);return;}
	$hash=array();
	$prefixMid=" (sitename,uri,td,http_code,client,hostname,familysite,service,backend,zDate,size,MAC,zMD5,statuslb)";
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
			$targetFile="{$GLOBALS["ARTICALOGDIR"]}/haproxy-rtm/$filename";
			$countDeFiles++;
			$ARRAY=unserialize(@file_get_contents($targetFile));
			while (list ($key, $value) = each ($ARRAY) ){$ARRAY[$key]=trim(addslashes($value));}
			$ARRAY["MAC"]=GetMacFromIP($ARRAY["SOURCE"]);
			$hostname=GetComputerName($ARRAY["SOURCE"]);
			$dayhour=date("YmdH",$ARRAY["TIME"]);
			$time=date("H:i:s",$ARRAY["TIME"]);
			$fulldate=date('Y-m-d H:i:s',$ARRAY["TIME"]);
			$table="hour_$dayhour";
			if(preg_match("#(.+?)\s+(.*?)#", $ARRAY["SERVICE"],$ri)){$ARRAY["SERVICE"]=$ri[1];}
			if(preg_match("#(.+?)\s+(.*?)#", $ARRAY["BACKEND"],$ri)){$ARRAY["BACKEND"]=$ri[1];}
			$uri=$ARRAY["URI"];
			$md5=md5(serialize($ARRAY));
			if(preg_match("#^(?:[^/]+://)?([^/:]+)#",$uri,$re)){
				$sitename=$re[1];
				if(preg_match("#^www\.(.+)#",$sitename,$ri)){$sitename=$ri[1];}
				$familysite=$qs->GetFamilySites($sitename);
			}
			
		  $linsql="('$sitename','$uri','{$ARRAY["TD"]}','{$ARRAY["HTTP_CODE"]}','{$ARRAY["SOURCE"]}','$hostname','$familysite','{$ARRAY["SERVICE"]}','{$ARRAY["BACKEND"]}','$fulldate','{$ARRAY["BYTES"]}','{$ARRAY["MAC"]}','$md5','{$ARRAY["STATUSLB"]}')";
		  $hash[$table][]=$linsql;
		  if($GLOBALS["VERBOSE"]){echo "Remove: $targetFile\n";}
  		  @unlink($targetFile);
		  if(system_is_overloaded()){break;}
		}
		
		
		while (list ($table, $tr) = each ($hash)){
			if(trim($table)==null){continue;}
			if(!$q->create_TableHour($table)){
				@mkdir("{$GLOBALS["ARTICALOGDIR"]}/haproxy-errors",0755,true);
				@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/haproxy-errors/".md5(serialize($hash)), serialize($hash));
				return;
			}
			
			$sql="INSERT IGNORE INTO $table $prefixMid VALUES ".@implode(",", $tr);
			$q->QUERY_SQL($sql);
			if(!$q->ok){
				WriteMyLogs($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
				@mkdir("{$GLOBALS["ARTICALOGDIR"]}/haproxy-errors",0755,true);
				@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/haproxy-errors/".md5(serialize($hash)), serialize($hash));
				return;				
			}
			
			
		}
		
	haproxy_errors();
	
}

function haproxy_errors(){
	$q=new mysql_haproxy_builder();
	$prefixMid=" (sitename,uri,td,http_code,client,hostname,familysite,service,backend,zDate,size,MAC,zMD5,statuslb)";
	foreach (glob("{$GLOBALS["ARTICALOGDIR"]}/haproxy-errors/*") as $filename) {
		$hash=unserialize(@file_get_contents($filename));
		while (list ($table, $tr) = each ($hash)){
			if(!$q->create_TableHour($table)){continue;}
			$sql="INSERT IGNORE INTO $table $prefixMid VALUES ".@implode(",", $tr);
			$q->QUERY_SQL($sql);
			if(!$q->ok){
				WriteMyLogs($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
				continue;}
			@unlink($filename);
			if(system_is_overloaded()){return;}
		}		
		
		
	}
	
	
}



function GetComputerName($ip){
		if($GLOBALS["resvip"][$ip]<>null){return $GLOBALS["resvip"][$ip];}
		$name=gethostbyaddr($ip);
		$GLOBALS["resvip"]=$name;
		return $name;
		}

function GetMacFromIP($ipaddr){
		$ipaddr=trim($ipaddr);
		$ttl=date('YmdH');
		if(count($GLOBALS["CACHEARP"])>3){unset($GLOBALS["CACHEARP"]);}
		if(isset($GLOBALS["CACHEARP"][$ttl][$ipaddr])){return $GLOBALS["CACHEARP"][$ttl][$ipaddr];}
		
		if(!isset($GLOBALS["SBIN_ARP"])){$unix=new unix();$GLOBALS["SBIN_ARP"]=$unix->find_program("arp");}
		if(strlen($GLOBALS["SBIN_ARP"])<4){return;}
		
		if(!isset($GLOBALS["SBIN_PING"])){$unix=new unix();$GLOBALS["SBIN_PING"]=$unix->find_program("ping");}
		if(!isset($GLOBALS["SBIN_NOHUP"])){$unix=new unix();$GLOBALS["SBIN_NOHUP"]=$unix->find_program("nohup");}
		
		$cmd="{$GLOBALS["SBIN_ARP"]} -n \"$ipaddr\" 2>&1";
		events($cmd);
		exec("{$GLOBALS["SBIN_ARP"]} -n \"$ipaddr\" 2>&1",$results);
		while (list ($num, $line) = each ($results)){
			if(preg_match("#^[0-9\.]+\s+.+?\s+([0-9a-z\:]+)#", $line,$re)){
				if($re[1]=="no"){continue;}
				$GLOBALS["CACHEARP"][$ttl][$ipaddr]=$re[1];
				return $GLOBALS["CACHEARP"][$ttl][$ipaddr];
			}
			
		}
		events("$ipaddr not found (".__LINE__.")");
		if(!isset($GLOBALS["PINGEDHOSTS"][$ipaddr])){
			shell_exec("{$GLOBALS["SBIN_NOHUP"]} {$GLOBALS["SBIN_PING"]} $ipaddr -c 3 >/dev/null 2>&1 &");
			$GLOBALS["PINGEDHOSTS"][$ipaddr]=true;
		}
			
		
	}
function WriteMyLogs($text,$function,$file,$line){
	$mem=round(((memory_get_usage()/1024)/1000),2);
	if(!isset($GLOBALS["MYPID"])){$GLOBALS["MYPID"]=getmypid();}
	writelogs($text,$function,__FILE__,$line);
	$logFile="{$GLOBALS["ARTICALOGDIR"]}/".basename(__FILE__).".log";
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
   	if (is_file($logFile)) { 
   		$size=filesize($logFile);
   		if($size>9000000){unlink($logFile);}
   	}
   	$date=date('m-d H:i:s');
	$logFile=str_replace("//","/",$logFile);
	$f = @fopen($logFile, 'a');
	if($GLOBALS["VERBOSE"]){echo "$date [{$GLOBALS["MYPID"]}][{$mem}MB]: [$function::$line] $text\n";}
	@fwrite($f, "$date [{$GLOBALS["MYPID"]}][{$mem}MB][Task:{$GLOBALS["SCHEDULE_ID"]}]: [$function::$line] $text\n");
	@fclose($f);
}

?>
