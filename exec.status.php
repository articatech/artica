<?php
ini_set('error_reporting', E_ALL);

function shutdown() {
	$error = error_get_last();
	$lastfunc=null;$type=null;$message=null;
	if(!isset($error["file"])){$error["file"]=basename(__FILE__);}
	if(isset($error["type"])){$type=trim($error["type"]);}
	if(isset($error["message"])){$message= trim($error["message"]);}
	if($message==null){return;}
	$file = $error["file"];
	if(isset($GLOBALS["LAST_FUNCTION_USED"])){$lastfunc=$GLOBALS["LAST_FUNCTION_USED"]; }
	if(function_exists("openlog")){openlog("artica-status", LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog(true, "$file: Last function: `$lastfunc` Fatal, stopped with error $type $message");}
	if(function_exists("closelog")){closelog();}
	
}
register_shutdown_function('shutdown');
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["SCHEDULE_ID"]=0;
$GLOBALS["STARTED_BY_CRON"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--startcron#",implode(" ",$argv),$re)){$GLOBALS["STARTED_BY_CRON"]=true;}
$GLOBALS["FORCE"]=false;
$GLOBALS["EXECUTED_AS_ROOT"]=true;
$GLOBALS["RUN_AS_DAEMON"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["DISABLE_WATCHDOG"]=false;
$GLOBALS["BASE_ROOT"]=dirname(__FILE__);
$GLOBALS["NOSTATUSTIME"]=false;
$GLOBALS["SQUID_INSTALLED"]=false;
$GLOBALS["MY-POINTER"]="/etc/artica-postfix/pids/". basename(__FILE__).".pointer";
$GLOBALS["COMMANDLINE"]=implode(" ",$argv);
if(strpos($GLOBALS["COMMANDLINE"],"--verbose")>0){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;$GLOBALS["DEBUG"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--nowachdog#",$GLOBALS["COMMANDLINE"])){$GLOBALS["DISABLE_WATCHDOG"]=true;}
if(preg_match("#--force#",$GLOBALS["COMMANDLINE"])){$GLOBALS["FORCE"]=true;}
if($GLOBALS["VERBOSE"]){echo "LoadIncludes();\n";}
LoadIncludes();

// $GLOBALS["PHP5"]=  $GLOBALS["nohup"] $GLOBALS["DISABLE_WATCHDOG"]
$sock=new sockets();
$GLOBALS["CLASS_SOCKETS"]=$sock;
$DisableArticaStatusService=$sock->GET_INFO("DisableArticaStatusService");
if(!is_numeric($DisableArticaStatusService)){$DisableArticaStatusService=0;}
$unix=new unix();

if(is_file("/etc/artica-postfix/FROM_ISO")){
	if($unix->file_time_min("/etc/artica-postfix/FROM_ISO")<1){die();}
}
$GLOBALS["NMAP_INSTALLED"]=false;
$GLOBALS["ArticaWatchDogList"]=unserialize(base64_decode($sock->GET_INFO("ArticaWatchDogList")));
$GLOBALS["PHP5"]=$unix->LOCATE_PHP5_BIN(); 
$GLOBALS["NICE"]=$unix->EXEC_NICE();
$GLOBALS["nohup"]=$unix->find_program("nohup");
$GLOBALS["pgrep"]=$unix->find_program("pgrep");
$GLOBALS["CHMOD"]=$unix->find_program("chmod");
$GLOBALS["CHOWN"]=$unix->find_program("chown");
$GLOBALS["KILLBIN"]=$unix->find_program("kill");
$GLOBALS["RMBIN"]=$unix->find_program("rm");
$GLOBALS["SYNCBIN"]=$unix->find_program("sync");
$GLOBALS["ECHOBIN"]=$unix->find_program("echo");
$GLOBALS["NMAPBIN"]=$unix->find_program("nmap");
$GLOBALS["UPTIMEBIN"]=$unix->find_program("uptime");
$GLOBALS["TAILBIN"]=$unix->find_program("tail");



$GLOBALS["SquidRotateOnlySchedule"]=intval($sock->GET_INFO("SquidRotateOnlySchedule"));
$GLOBALS["SQUID_BIN"]=$unix->LOCATE_SQUID_BIN();
if(is_file($GLOBALS["SQUID_BIN"])){$GLOBALS["SQUID_INSTALLED"]=true;}
$GLOBALS["KILL"]=$GLOBALS["KILLBIN"];
if($GLOBALS["VERBOSE"]){echo "DEBUG MODE ENABLED\n";}
if($GLOBALS["VERBOSE"]){echo "command line: {$GLOBALS["COMMANDLINE"]}\n";}
if(is_file("/etc/artica-postfix/amavis.watchdog.cache")){$GLOBALS["AMAVIS_WATCHDOG"]=unserialize(@file_get_contents("/etc/artica-postfix/amavis.watchdog.cache"));}
$GLOBALS["TOTAL_MEMORY_MB"]=$unix->TOTAL_MEMORY_MB();
if(is_file($GLOBALS["NMAPBIN"])){$GLOBALS["NMAP_INSTALLED"]=true;}


if(!is_dir("/var/log/artica-postfix/rotate_events")){mkdir_test("/var/log/artica-postfix/rotate_events",0755,true);}
if(!is_dir("/etc/artica-postfix/settings/Mysql")){mkdir_test("/etc/artica-postfix/settings/Mysql",0755,true);}

$sock=null;
$unix=null;
include_once('/usr/share/artica-postfix/framework/class.status.samba.inc');

if(isset($argv[1])){
	if(strlen($argv[1])>0){
		events("parsing command line ".@implode(";", $argv),"MAIN",__LINE__);
		$GLOBALS["CLASS_UNIX"]=new unix();
		$GLOBALS["CLASS_SOCKETS"]=new sockets();
		$GLOBALS["CLASS_USERS"]=new settings_inc();
		include_once('/usr/share/artica-postfix/ressources/class.status.videocache.inc');
		include_once('/usr/share/artica-postfix/ressources/class.status.squid.inc');
		include_once('/usr/share/artica-postfix/ressources/class.status.postfix.inc');
		include_once('/usr/share/artica-postfix/ressources/class.status.zarafa.inc');
		
		CheckCallable();
	}
	
	$mem=round(((memory_get_usage()/1024)/1000),2);events("{$mem}MB after declarations","MAIN",__LINE__);
	
	if(strlen($argv[1])>2){events("parsing command line {$argv[1]}","MAIN",__LINE__);}
	if($argv[1]=="--nice"){echo "{$GLOBALS["NICE"]}\n";exit;}
	if($argv[1]=="--klms"){echo klms_status();echo klmsdb_status();echo klms_milter();die();}
	if($argv[1]=="--reboot"){echo reboot();exit;}
	if($argv[1]=="--all"){
			events("-> launch_all_status_cmdline()","MAIN",__LINE__);
			$GLOBALS["NOSTATUSTIME"]=true;
			launch_all_status_cmdline();
			if($GLOBALS["VERBOSE"]){echo "DONE\n\n\n";}
			die();
	}
	if($argv[1]=="--gold"){echo gold();exit;}
	if($argv[1]=="--free"){echo getmem();exit;}
	if($argv[1]=="--procs"){$PROCESSES_CLASS=new processes_php();$PROCESSES_CLASS->MemoryInstances();die();}
	if($argv[1]=="--ss5"){echo "\n".ss5()."\n".redsocks();exit;}
	if($argv[1]=="--squid"){echo squid_master_status();exit;}
	if($argv[1]=="--squid-stats-central"){echo squid_stats_central();exit;}
	if($argv[1]=="--disks"){$GLOBALS["VERBOSE"]=true;$unix=new unix();print_r($unix->ALL_DISKS_STATUS());exit;}
	if($argv[1]=="--watchdog-me"){
		include_once(dirname(__FILE__)."/ressources/class.status.watchdog.me.inc");
		watchdog_me();
		exit;
	}
	if($argv[1]=="--defaults"){$GLOBALS["VERBOSE"]=true;Build_default_values();exit;}
	
	if($argv[1]=="--sealion"){echo sealion_agent();exit;}
	if($argv[1]=="--freshclam"){echo freshclam();exit;}
	if($argv[1]=="--syncthing"){echo syncthing();exit;}
	if($argv[1]=="--c-icap"){echo c_icap_master_status()."\n".clamd()."\n".freshclam();exit;}
	if($argv[1]=="--kav4proxy"){echo kav4Proxy_status();exit;}
	if($argv[1]=="--dansguardian"){echo dansguardian_master_status();exit;}
	if($argv[1]=="--wifi"){echo wpa_supplicant();;exit;}
	if($argv[1]=="--fetchmail"){echo fetchmail();;exit;}
	if($argv[1]=="--milter-greylist"){echo milter_greylist();;exit;}
	if($argv[1]=="--framework"){echo framework();;exit;}
	if($argv[1]=="--pdns"){echo pdns_server()."\n".pdns_recursor();exit;}
	if($argv[1]=="--cyrus-imap"){echo cyrus_imap();exit;}
	if($argv[1]=="--mysql"){echo "\n".mysql_server()."\n".mysql_mgmt()."\n". mysql_replica();exit;}
	if($argv[1]=="--openldap"){echo "\n".openldap();;exit;}
	if($argv[1]=="--saslauthd"){echo "\n".saslauthd();;exit;}
	if($argv[1]=="--sysloger"){echo "\n".syslogger();;exit;}
	if($argv[1]=="--squid-tail"){echo squid_tail();exit;}
	if($argv[1]=="--amavis"){echo "\n".amavis();exit;}
	if($argv[1]=="--amavis-milter"){echo"\n". amavis_milter();exit;}
	if($argv[1]=="--amavisdb"){echo"\n". amavisdb();exit;}
	if($argv[1]=="--xmail"){XMail();exim4();exit;}
	if($argv[1]=="--bwm-ng"){echo bwm_ng();exit;}
	if($argv[1]=="--ntopng"){echo ntopng()."\n".bandwidthd()."\n".redis_server()."\n".bwm_ng()."\n";exit;}
	if($argv[1]=="--load-stats"){$GLOBALS["VERBOSE"]=true;load_stats();exit;}
	if($argv[1]=="--vsftpd"){echo vsftpd();exit;}
	if($argv[1]=="--unifi"){echo unifi_mongodb()."\n".unifi();exit;}
	if($argv[1]=="--transmission-daemon"){echo transmission_daemon()."\n";exit;}
	if($argv[1]=="--suricata"){echo suricata_status()."\n".barnyard2_status();exit;}
	
	
	
	
	
	if($argv[1]=="--boa"){echo"\n". boa();exit;}
	if($argv[1]=="--lighttpd"){echo"\n". lighttpd();exit;}
	
	if($argv[1]=="--clamav"){echo"\n". clamd()."\n".clamscan()."\n".clammilter()."\n".freshclam(); exit;}
	if($argv[1]=="--retranslator"){echo"\n". retranslator_httpd(); exit;}
	if($argv[1]=="--spamassassin"){echo spamassassin_milter()."\n".spamassassin();exit;}
	if($argv[1]=="--postfix"){
		if($GLOBALS["VERBOSE"]){echo "Running postfix\n";}
		echo "\n".postfix();exit;}
	if($argv[1]=="--postfix-logger"){echo "\n".postfix_logger();exit;}
	if($argv[1]=="--mailman"){echo "\n".mailman();exit;}
	if($argv[1]=="--kas3"){echo "\n".kas3_milter()."\n".kas3_ap(); exit;}
	if($argv[1]=="--samba"){$GLOBALS["DISABLE_WATCHDOG"]=true;echo "\n".smbd()."\n".nmbd()."\n".winbindd()."\n".scanned_only()."\n"; exit;}
	if($argv[1]=="--roundcube"){echo "\n".roundcube()."\n".roundcube_db();exit;}
	if($argv[1]=="--cups"){echo "\n".cups();exit;}
	if($argv[1]=="--apache-groupware"){echo "\n";exit;}
	if($argv[1]=="--gdm"){echo "\n".gdm();exit;}
	if($argv[1]=="--console-kit"){echo "\n".consolekit();exit;}
	if($argv[1]=="--xfce"){echo "\n".xfce();exit;}
	if($argv[1]=="--vmtools"){echo "\n".vmtools();exit;}
	if($argv[1]=="--hamachi"){echo "\n".hamachi();exit;}
	if($argv[1]=="--artica-notifier"){echo "\n".artica_notifier();exit;}
	if($argv[1]=="--dhcpd"){echo "\n".dhcpd_server();exit;}
	if($argv[1]=="--pure-ftpd"){echo "\n".pure_ftpd();exit;}
	if($argv[1]=="--mldonkey"){echo "\n".mldonkey();exit;}
	if($argv[1]=="--policydw"){echo "\n".policyd_weight();exit;}
	if($argv[1]=="--backuppc"){echo "\n".backuppc();exit;}
	if($argv[1]=="--kav4fs"){echo "\n".kav4fs()."\n".kav4fsavs();exit;}
	if($argv[1]=="--ocsweb"){echo "\n".apache_ocsweb()."\n".apache_ocsweb_download()."\n";exit;}
	if($argv[1]=="--ocsagent"){echo "\n".ocs_agent();exit;}
	if($argv[1]=="--openssh"){echo "\n".openssh();exit;}
	if($argv[1]=="--gluster"){echo "\n".gluster();exit;}
	if($argv[1]=="--auditd"){echo "\n".auditd();exit;}
	if($argv[1]=="--squidguard-http"){echo "\n".squidguardweb();exit;}
	if($argv[1]=="--opendkim"){echo "\n".opendkim();exit;}
	if($argv[1]=="--ufdbguardd"){echo "\n".ufdbguardd()."\n".squidguardweb()."\n".ufdbguardd_client();exit;}
	if($argv[1]=="--ufdb-tail"){echo "\n".ufdbguardd_tail();exit;}
	if($argv[1]=="--squidguard-tail"){echo "\n".squidguard_logger();exit;}
	if($argv[1]=="--dkim-milter"){echo "\n".milter_dkim();exit;}
	if($argv[1]=="--dropbox"){echo "\n".dropbox();exit;}
	if($argv[1]=="--artica-policy"){echo "\n".artica_policy();exit;}
	if($argv[1]=="--vboxwebsrv"){echo "\n".virtualbox_webserv();exit;}
	if($argv[1]=="--tftpd"){echo "\n".tftpd();exit;}
	if($argv[1]=="--vdi"){echo "\n".virtualbox_webserv()."\n".tftpd()."\n".dhcpd_server();exit;}
	if($argv[1]=="--crossroads"){echo "\n".crossroads();exit;}
	if($argv[1]=="--artica-status"){echo "\n".artica_status();exit;}
	if($argv[1]=="--artica-background"){echo "\n";exit;}
	if($argv[1]=="--pptpd"){echo "\n".pptpd();exit;}
	if($argv[1]=="--pptpd-clients"){echo "\n".pptp_clients();exit;}
	
	if($argv[1]=="--apt-mirror"){echo "\n".apt_mirror();exit;}
	if($argv[1]=="--squidclamav-tail"){echo "\n".squid_clamav_tail();exit;}
	if($argv[1]=="--squidcache-tail"){echo "\n".squid_cache_tail();exit;}
	
	
	if($argv[1]=="--ddclient"){echo "\n".ddclient();exit;}
	if($argv[1]=="--cluebringer"){echo "\n".cluebringer();exit;}
	if($argv[1]=="--apachesrc"){echo "\n".apachesrc();exit;}
	if($argv[1]=="--assp"){echo "\n".assp();exit;}
	if($argv[1]=="--freewebs"){echo "\n".apachesrc()."\n".pure_ftpd()."\n".tomcat()."\n".php_fpm()."\n".nginx()."\n".php_fcgi();exit;}
	if($argv[1]=="--openvpn"){echo "\n".openvpn();exit;}
	if($argv[1]=="--vboxguest"){echo "\n".vboxguest();exit;}
	if($argv[1]=="--sabnzbdplus"){echo "\n".sabnzbdplus();exit;}
	if($argv[1]=="--openvpn-clients"){echo "\n".OpenVPNClientsStatus();exit;}
	if($argv[1]=="--stunnel"){echo "\n".stunnel();exit;}
	if($argv[1]=="--meta-checks"){echo "\n".meta_checks();exit;}
	if($argv[1]=="--smbd"){echo "\n".smbd();exit;}
	if($argv[1]=="--vnstat"){echo "\n".vnstat();exit;}
	if($argv[1]=="--munin"){echo "\n".munin();exit;}
	if($argv[1]=="--autofs"){echo "\n".autofs();exit;}
	if($argv[1]=="--greyhole"){echo "\n".greyhole();exit;}
	if($argv[1]=="--amavis-watchdog"){echo "\n".AmavisWatchdog();exit;}
	if($argv[1]=="--dnsmasq"){echo "\n".dnsmasq();exit;}
	if($argv[1]=="--iscsi"){echo "\n".iscsi();exit;}
	if($argv[1]=="--yorel"){echo "\n".watchdog_yorel();exit;}
	if($argv[1]=="--watchdog-service"){echo "\n".WATCHDOG($argv[2],$argv[3]);exit;}
	if($argv[1]=="--postfwd2"){echo "\n".postfwd2();exit;}
	if($argv[1]=="--zarafa-watchdog"){zarafa_watchdog();exit;}
	if($argv[1]=="--vps"){echo vps_servers();exit;}
	if($argv[1]=="--crossroads-multiple"){echo crossroads_multiple();exit;}
	if($argv[1]=="--smartd"){echo "\n".smartd();exit;}
	if($argv[1]=="--watchdog-me"){include_once(dirname(__FILE__)."/ressources/class.status.watchdog.me.inc");echo watchdog_me();die();}
	if($argv[1]=="--auth-tail"){echo auth_tail();exit;}
	if($argv[1]=="--snort"){echo snort();exit;}
	if($argv[1]=="--xload"){echo xLoadAvg();$GLOBALS["VERBOSE"]=true;exit;}
	if($argv[1]=="--greyhole-watchdog"){greyhole_watchdog();exit;}
	if($argv[1]=="--greensql"){echo greensql();exit;}
	if($argv[1]=="--nscd"){echo nscd();exit;}
	if($argv[1]=="--tomcat"){echo tomcat();exit;}
	if($argv[1]=="--cgroups"){echo cgroups();exit;}
	if($argv[1]=="--openemm"){echo openemm()."\n".openemm_sendmail();exit;}
	if($argv[1]=="--exec-nice"){$GLOBALS["VERBOSE"]=true;echo "\"{$GLOBALS["CLASS_UNIX"]->EXEC_NICE()}\"\n";die();}
	if($argv[1]=="--ntpd"){echo ntpd_server();die();}
	if($argv[1]=="--ps-mem"){echo ps_mem();die();}
	if($argv[1]=="--arpd"){echo arpd();die();}
	if($argv[1]=="--netatalk"){echo netatalk();die();}
	if($argv[1]=="--yaffas"){echo yaffas();die();}
	if($argv[1]=="--network"){echo ifconfig_network();die();}
	if($argv[1]=="--avahi-daemon"){echo avahi_daemon();die();}
	if($argv[1]=="--time-capsule"){echo avahi_daemon(); echo "\n";echo netatalk();die();}
	if($argv[1]=="--rrd"){echo testingrrd();die();}
	if($argv[1]=="--memcached"){echo memcached();die();}
	if($argv[1]=="--monit"){echo monit();die();}
	if($argv[1]=="--UpdateUtility"){echo UpdateUtilityHTTP();die();}
	if($argv[1]=="--zarafa-web"){echo zarafa_web();die();}
	if($argv[1]=="--ejabberd"){echo ejabberd()."\n";echo pymsnt();die();}
	if($argv[1]=="--lighttpd-all"){echo lighttpd()."\n";echo framework();die();}
	if($argv[1]=="--arkeia"){echo arkwsd()."\n";echo arkeiad();die();}
	if($argv[1]=="--haproxy"){echo haproxy();die();}
	if($argv[1]=="--privoxy"){echo privoxy();die();}
	if($argv[1]=="--mailman"){echo mailman();die();}
	if($argv[1]=="--mimedefang"){echo mimedefang()."\n";die();}
	if($argv[1]=="--articadb"){echo articadb();die();}
	if($argv[1]=="--maillog"){echo maillog_watchdog();die();}
	if($argv[1]=="--freeradius"){echo freeradius();die();}
	if($argv[1]=="--php-pfm"){echo php_fpm()."\n".php_fcgi();die();}
	if($argv[1]=="--syslog-db"){echo syslog_db();die();}
	if($argv[1]=="--nginx"){echo nginx()."\n".nginx_db();die();}
	if($argv[1]=="--haarp"){echo haarp();die();}
	if($argv[1]=="--proftpd"){echo proftpd_status();die();}
	
	if($argv[1]=="--ftp-proxy"){echo ftp_proxy()."\n";die();}
	if($argv[1]=="--rsync-debian-mirror"){echo rsync_debian_mirror()."\n";die();}
	if($argv[1]=="--cntlm"){echo cntlm()."\n";echo cntlm_parent()."\n";die();}
	if($argv[1]=="--roundcube-db"){echo roundcube_db()."\n";die();}
	if($argv[1]=="--rdpproxy"){echo rdpproxy()."\n".rdpproxy_authhook();die();}
	if($argv[1]=="--dnscache"){echo dnsmasq();die();}
	if($argv[1]=="--vde-uniq"){echo vde_uniq($argv[2]);die();}
	if($argv[1]=="--vde-all"){echo vde_all();die();}
	if($argv[1]=="--ufdb"){echo ufdbguardd()."\n".squidguardweb()."\n".ufdbguardd_client()."\n".ufdbcat();die();}
	if($argv[1]=="--ufdbcat"){echo ufdbcat()."\n";die();}
	if($argv[1]=="--ucarp"){echo ucarp();die();}
	if($argv[1]=="--squid-db"){echo squid_db();die();}
	if($argv[1]=="--sarg"){die();}
	if($argv[1]=="--snmpd"){echo snmpd();die();}
	if($argv[1]=="--squid-nat"){echo squid_nat();die();}
	if($argv[1]=="--ziproxy"){echo ziproxy();die();}
	if($argv[1]=="--iredmail"){echo iredmail();die();}
	if($argv[1]=="--milter-regex"){echo milter_regex();die();}
	if($argv[1]=="--l7filter"){echo l7filter();die();}
	if($argv[1]=="--hypercacheweb"){echo HyperCacheWeb();die();}
	if($argv[1]=="--hypercachestoreid"){echo HyperCacheStoreID_client();echo "\n";echo hypercache_logger();die();}
	if($argv[1]=="--postgres"){echo postgres();die();}
	if($argv[1]=="--influx"){echo postgres();die();}
	
	
	
	if($argv[1]=="--philesight"){echo philesight();die();}
	if($argv[1]=="--squid-transparent"){echo iptables_transparent();die();}
	
	
	
	if($argv[1]=="--videocache"){
		
		$conf[]=videocache();
		$conf[]=videocache_scheduler();
		$conf[]=videocache_clients();
		echo @implode("\n",$conf);
		die();
	}
	
	
	
	
	
	if($argv[1]=="--functions"){
		$arr = get_defined_functions();
		print_r($arr);
		die();
	}
	
	
	
	if($argv[1]=="--all-squid"){
		$unix=new unix();
		$processes=$unix->PIDOF_PATTERN_ALL(basename(__FILE__).".*?{$argv[1]}",true);
		events(count($processes)." Running  ".@implode(";", $processes),"{$argv[1]}",__LINE__);
		
		
		if(count($processes)>2){
			while (list ($num, $pid) = each ($processes)){
				events("Killing pid $pid  ","MAIN",__LINE__);
				$unix->KILL_PROCESS($pid,9);
			}
			$processes=$unix->PIDOF_PATTERN_ALL(basename(__FILE__).".*?{$argv[1]}",true);
			events(count($processes)." Running  ".@implode(";", $processes),"{$argv[1]}",__LINE__);
		}
		
		if(count($processes)>0){
			events("ALL_SQUID: Processes already exists, aborting","{$argv[1]}",__LINE__);
			die();
		}
		
		$cachefile="/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS";
		$GLOBALS["DISABLE_WATCHDOG"]=true;
		$TimeFile=$cachefile;
		
		if($GLOBALS["SCHEDULE_ID"]>0){
			if($unix->file_time_min($TimeFile)>5){
				$php=$unix->LOCATE_PHP5_BIN();
				$nohup=$unix->find_program("nohup");
				shell_exec2("$nohup $php /usr/share/artica-postfix/exec.squid.run.schedules.php >/dev/null 2>&1 &");
			}
		}
		
		$conf[]=squid_master_status(true);
		$conf[]=kav4Proxy_status();
		$conf[]=proxy_pac_status();
		$conf[]=privoxy();
		$conf[]=squidguardweb();
		$conf[]=ufdbguardd();
		$conf[]=freshclam();
		$conf[]=articadb();
		$conf[]=winbindd();
		$conf[]=squid_db();
		$conf[]=haarp();
	
		$conf[]=nginx();
		$conf[]=ftp_proxy();
		$conf[]=c_icap_master_status();
		$conf[]=cntlm();
		$conf[]=cntlm_parent();
		$conf[]=rdpproxy_authhook();
		$conf[]=rdpproxy();
		$conf[]=clamd();
		$conf[]=dnsmasq();
		$conf[]=ufdbguardd_client();
		$conf[]=ucarp();
		$conf[]=hotspot_web();
		$conf[]=hotspot_fw();
		$conf[]=squid_nat();
		$conf[]=ziproxy();
		$conf[]=ufdbcat();
		$conf[]=HyperCacheWeb();
		$conf[]=postgres();
		$conf[]=squid_tail();
		$conf[]=iptables_transparent();
		
		
		if(is_file($cachefile)){@unlink($cachefile);}
		@file_put_contents($cachefile, @implode("\n",$conf));
		@chmod($cachefile, 0755);
		echo @implode("\n",$conf);
		die();
		
	}
	
	if($argv[1]=="--hotspot"){
		$conf[]=hotspot_web();
		$conf[]=hotspot_fw();
		echo @implode("\n", $conf)."\n";
		die();
	}
	
	if($argv[1]=="--zarafa"){
		$GLOBALS["DISABLE_WATCHDOG"]=true;
		include_once(dirname(__FILE__)."/ressources/class.status.zarafa.inc");
		$conf[]=zarafa_web();
		$conf[]=zarafa_ical();
		$conf[]=zarafa_dagent();
		$conf[]=zarafa_monitor();
		$conf[]=zarafa_gateway();
		$conf[]=zarafa_spooler();
		$conf[]=zarafa_server();
		$conf[]=zarafa_server2();
		$conf[]=zarafa_licensed();
		$conf[]=zarafa_db();
		$conf[]=zarafa_indexer();
		$conf[]=yaffas();
		$conf[]=zarafa_multi();
		$conf[]=zarafa_search();
		$conf[]=php_fpm();
		echo @implode("\n",$conf);
		die();
	}
	
	
	
		if($argv[1]=="--amavis-full"){
			$conf[]=spamassassin();
			$conf[]=clamd();
			$conf[]=amavis();
			$conf[]=amavis_milter();
			$conf[]=amavisdb();
			
			echo @implode("\n",$conf);
			die();
		}
		if($argv[1]=="--verbose"){unset($argv[1]);} 		
		if($GLOBALS["VERBOSE"]){echo "cannot understand {$argv[1]} assume perhaps it is a function\n";}		
}

if(isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
CheckNetInterfaces();





if(isset($argv[1])){
	if(strlen($argv[1])>0){
		write_syslog("Unable to understand {$argv[1]}",basename(__FILE__));
		die();
	}
}


if($DisableArticaStatusService==1){
	if(systemMaxOverloaded(basename(__FILE__))){events("OVERLOADED !! aborting","MAIN",__LINE__);die();}
	events("-> launch_all_status()","MAIN",__LINE__);
	launch_all_status();
	die();
}


$pidfile="/etc/artica-postfix/".basename(__FILE__).".pid";
$pid=@file_get_contents($pidfile);

if($GLOBALS["CLASS_UNIX"]->process_exists($pid,(basename(__FILE__)))){
	print "Starting......: ".date("H:i:s")." artica-status Already executed PID $pid...\n";
	die();
}
$nofork=false;
$mem=round(((memory_get_usage()/1024)/1000),2);events("{$mem}MB artica-status System Memory: {$GLOBALS["TOTAL_MEMORY_MB"]}MB","MAIN",__LINE__);
print "Starting......: ".date("H:i:s")." artica-status system memory: {$GLOBALS["TOTAL_MEMORY_MB"]}MB\n";
if(!function_exists("pcntl_fork")){$nofork=true;}
if($GLOBALS["TOTAL_MEMORY_MB"]<400){$nofork=true;}
if($DisableArticaStatusService==1){$nofork=true;}


if($nofork){
	if(systemMaxOverloaded(basename(__FILE__))){events("OVERLOADED !! aborting","MAIN",__LINE__);die();}
	print "Starting......: ".date("H:i:s")." artica-status pcntl_fork module not loaded !\n";
	$pidfile="/etc/artica-postfix/".basename(__FILE__).".pid";
	$childpid=posix_getpid();
	events("{$mem}MB artica-status Memory NO fork.... pid=$childpid","MAIN",__LINE__);
	@file_put_contents($pidfile,$childpid);

	$timefile="/etc/artica-postfix/".basename(__FILE__).".time";
	if(file_time_min($timefile)>1){
		@unlink($timefile);
		events("{$mem}MB artica-status Memory NO fork.... -> launch_all_status()","MAIN",__LINE__);
		launch_all_status();
		@file_put_contents($timefile,time());
	}
	events("{$mem}MB artica-status Memory NO fork.... -> die()","MAIN",__LINE__);
	$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
	print "Starting......: ".date("H:i:s")." artica-status building parse-orders..\n";
	shell_exec2(trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.parse-orders.php >/dev/null 2>&1 &"));
	
	die();



}
print "Starting......: ".date("H:i:s")." artica-status building monit..\n";
shell_exec2(trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.monit.php --build >/dev/null 2>&1 &"));


if(function_exists("pcntl_signal")){
	pcntl_signal(SIGTERM,'sig_handler');
	pcntl_signal(SIGINT, 'sig_handler');
	pcntl_signal(SIGCHLD,'sig_handler');
	pcntl_signal(SIGHUP, 'sig_handler');
}


set_time_limit(0);
ob_implicit_flush();
declare(ticks = 1);


$stop_server=false;
$reload=false;
$pid = pcntl_fork();
if ($pid == -1) {
	die("Starting......: ".date("H:i:s")." artica-status fork() call asploded!\n");
} else if ($pid) {
	// we are the parent
	print "Starting......: ".date("H:i:s")." artica-status fork()ed successfully.\n";
	die();
}

$pidfile="/etc/artica-postfix/".basename(__FILE__).".pid";
$childpid=posix_getpid();
@file_put_contents($pidfile,$childpid);
events("Starting PID $childpid","MAIN",__LINE__);
if(is_file("/var/log/artica-status.log")){@copy("/var/log/artica-status.log", "/var/log/artica-status.log.".time());@unlink("/var/log/artica-status.log");}

$renice_bin=$GLOBALS["CLASS_UNIX"]->find_program("renice");
events("$renice_bin 19 $childpid","MAIN",__LINE__);
shell_exec2("$renice_bin 19 $childpid &");
$GLOBALS["RUN_AS_DAEMON"]=true;
$GLOBALS["SHUTDOWN_COUNT"]=0;
events("Memory: ".round(((memory_get_usage()/1024)/1000),2) ." before start service".__LINE__);
$count=0;
$TTL=0;
$PP=0;
CheckCallable();
Build_default_values();
$PROCESSES_CLASS=new processes_php();
$FIRST_RUN=FALSE;
while ($stop_server==false) {
	$count++;
	$TTL++;
	
	$childpid=posix_getpid();
	$seconds=$count*5;
	
	if(is_file("/tmp/postgressql-restore.sh")){
		shell_exec("/tmp/postgressql-restore.sh >/tmp/postgressql-restore.sh.results 2>&1 &");
	}
	
	$mem=round(((memory_get_usage()/1024)/1000),2);
	
	
	if(!is_file("/usr/share/artica-postfix/ressources/logs/global.status.ini")){
		@unlink("/etc/artica-postfix/cron.1/exec.status.daemon.time");
	}
	
	if(is_file("/etc/artica-postfix/ARTICA_STATUS_RUN")){
		ToSyslog("RUN STATUS MANUALLY");
		@unlink("/etc/artica-postfix/ARTICA_STATUS_RUN");
		@unlink("/etc/artica-postfix/cron.1/exec.status.daemon.time");
	}
	
	$timefile=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/cron.1/exec.status.daemon.time");
	
	if(is_file("/etc/artica-postfix/ARTICA_STATUS_RELOAD")){ToSyslog("Reloading settings and libraries...");Reload();}

	events("[$childpid]: {$timefile}mn/3mn {$mem}MB stop_server=\"$stop_server\"",__FUNCTION__,__LINE__);
	
	if(!is_file("/usr/share/artica-postfix/ressources/logs/global.status.ini")){
		events("global.status.ini does not exists  -> Launch all status...",__FUNCTION__,__LINE__);
		try {launch_all_status(true);} catch (Exception $e) {writelogs("Fatal while running function launch_all_status $e",__FUNCTION__,__FILE__,__LINE__);}
		continue;
	}


	if($timefile>=3){
		events("***** LAUNCH ! *******",__FUNCTION__,__LINE__);
		@unlink("/etc/artica-postfix/cron.1/exec.status.daemon.time");
		@file_put_contents("/etc/artica-postfix/cron.1/exec.status.daemon.time", time());
		try {launch_all_status(true);} catch (Exception $e) {writelogs("Fatal while running function launch_all_status $e",__FUNCTION__,__FILE__,__LINE__);}
		try{
			$PROCESSES_CLASS->ParseLocalQueue();
		}catch (Exception $e){
			ToSyslog("Fatal while running function ParseLocalQueue $e");
		}
		
		continue;
	}


		sleep(5);
		$TTLSeconds=$TTL+5;

	try{
		$PROCESSES_CLASS->ParseLocalQueue();
	}catch (Exception $e){
		ToSyslog("Fatal while running function ParseLocalQueue $e");
	}

	if(is_file("/etc/artica-postfix/ARTICA_STATUS_RELOAD")){ToSyslog("Reloading settings and libraries...");Reload();}

}
write_syslog("Shutdown after $TTLSeconds seconds. stop_server=$stop_server");
events("!!! STOPPED DAEMON....die()...","MAIN",__LINE__);


function sig_handler($signo) {
	global $stop_server;
	global $reload;
	switch($signo) {
		case SIGTERM: {
			$GLOBALS["SHUTDOWN_COUNT"]=$GLOBALS["SHUTDOWN_COUNT"]+1;
			if($GLOBALS["SHUTDOWN_COUNT"]>3){
				$stop_server = true;
			}
			events("Memory: ".round(((memory_get_usage()/1024)/1000),2) ." Asked to shutdown {$GLOBALS["SHUTDOWN_COUNT"]}/3",__FUNCTION__,__LINE__);
			break;
		}

		case 1: {
			$reload=true;
			 
		}

		default: {
			if($signo<>17){events("Receive sig_handler $signo",__FUNCTION__,__LINE__);}
		}
	}
}


function LoadIncludes(){
	include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
	include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
	include_once(dirname(__FILE__).'/framework/class.unix.inc');
	include_once(dirname(__FILE__)."/framework/frame.class.inc");
	include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
	include_once(dirname(__FILE__)."/framework/class.settings.inc");
	include_once(dirname(__FILE__)."/ressources/mysql.status.inc");
	include_once(dirname(__FILE__)."/ressources/class.status.schedules.php");
	include_once(dirname(__FILE__)."/ressources/class.process.inc");
	include_once(dirname(__FILE__)."/ressources/class.status.postgres.inc");
	include_once(dirname(__FILE__)."/ressources/class.status.unifi.inc");
	include_once(dirname(__FILE__)."/ressources/class.status.irqbalance.inc");
	include_once(dirname(__FILE__)."/ressources/class.status.statistics.inc");
	include_once(dirname(__FILE__)."/ressources/class.status.suricata.inc");
	include_once(dirname(__FILE__)."/ressources/class.status.defaults.inc");
	include_once(dirname(__FILE__)."/ressources/class.status.cgroups.inc");
	include_once(dirname(__FILE__)."/ressources/class.status.bandwidthd.inc");
	include_once(dirname(__FILE__)."/ressources/class.status.haproxy.inc");
	include_once(dirname(__FILE__)."/ressources/class.status.privoxy.inc");
	include_once(dirname(__FILE__)."/ressources/class.status.sealion.inc");
	include_once(dirname(__FILE__)."/ressources/class.status.artica-meta.inc");
	include_once(dirname(__FILE__)."/ressources/class.status.proftpd.inc");
	include_once(dirname(__FILE__)."/ressources/class.status.watchdog.me.inc");
	$mem=round(((memory_get_usage()/1024)/1000),2);
	events("{$mem}MB",__FUNCTION__,__LINE__);
}


function squid_relatime_events($text){
	if(trim($text)==null){return;}

	$pid=@getmypid();
	$date=@date("H:i:s");
	$logFile="/var/log/squid/logfile_daemon.debug";

	$size=@filesize($logFile);
	if($size>1000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');
	if($GLOBALS["VERBOSE"]){echo "$date:[".basename(__FILE__)."] $pid `$text`\n";}
	@fwrite($f, "$date:[".basename(__FILE__)."] $pid `$text`\n");
	@fclose($f);

}


function Reload(){
	
	@unlink("/etc/artica-postfix/ARTICA_STATUS_RELOAD");
	$mem=((memory_get_usage()/1024)/1000);
	
	unset($GLOBALS["CLASS_SOCKETS"]);
	unset($GLOBALS["CLASS_USERS"]);
	unset($GLOBALS["CLASS_UNIX"]);
	unset($GLOBALS["TIME_CLASS"]);
	unset($GLOBALS["AMAVIS_WATCHDOG"]);
	unset($GLOBALS["GetVersionOf"]);
	unset($GLOBALS["ArticaWatchDogList"]);
	
	$mem2=((memory_get_usage()/1024)/1000);
	
	$free=$mem-$mem2;
	
	ToSyslog("Reloading {$free}Mb Free...");
	CheckCallable();
	

	
}
function ToSyslog($text){
	if($GLOBALS["VERBOSE"]){echo $text."\n";}
	if(!function_exists("syslog")){return;}
	$file=basename(__FILE__);
	$LOG_SEV=LOG_INFO;
	openlog($file, LOG_PID , LOG_SYSLOG);
	syslog($LOG_SEV, $text);
	closelog();
}





function Scheduler(){
	include_once('/usr/share/artica-postfix/ressources/class.status.scheduler.inc');
	$sch=new status_scheduler();
}


function amavis_watchdog_load_conf(){
	if(is_file("/etc/artica-postfix/settings/Daemons/AmavisGlobalConfiguration")){
		$ini=new iniFrameWork();
		$ini->loadFile("/etc/artica-postfix/settings/Daemons/AmavisGlobalConfiguration");
		$GLOBALS["AMAVIS_WATCHDOG_MAX_SERVERS"]=$ini->_params["BEHAVIORS"]["max_servers"];
		$GLOBALS["AMAVIS_WATCHDOG_CHILD_TIMEOUT"]=$ini->_params["BEHAVIORS"]["child_timeout"];
			
	}
	$GLOBALS["AMAVIS_WATCHDOG_CONF_TIME"]=filemtime("/usr/local/etc/amavisd.conf");

	events("/usr/local/etc/amavisd.conf: time:{$GLOBALS["AMAVIS_WATCHDOG_CONF_TIME"]}",__FUNCTION__,__LINE__);
	events("max_servers: {$GLOBALS["AMAVIS_WATCHDOG_MAX_SERVERS"]}",__FUNCTION__,__LINE__);
	events("child_timeout: {$GLOBALS["AMAVIS_WATCHDOG_CHILD_TIMEOUT"]}",__FUNCTION__,__LINE__);
}

function amavis_watchdog_removebayes(){
	$f[]="bayes_journal";
	$f[]="bayes_seen";
	$f[]="bayes_toks";
	while (list ($num, $filename) = each ($f)){
		if(is_file("/etc/spamassassin/$filename")){@unlink("/etc/spamassassin/$filename");}
		if(is_file("/etc/mail/spamassassin/$filename")){@unlink("/etc/mail/spamassassin/$filename");}
	}



}


function AmavisWatchdog(){
	if(!is_file("/usr/local/etc/amavisd.conf")){return;}
	if(!isset($GLOBALS["AMAVIS_WATCHDOG_CONF_TIME"])){amavis_watchdog_load_conf();}
	if(!isset($GLOBALS["AMAVIS_WATCHDOG_MAX_SERVERS"])){amavis_watchdog_load_conf();}
	$time=filemtime("/usr/local/etc/amavisd.conf");
	if($time<>$GLOBALS["AMAVIS_WATCHDOG_CONF_TIME"]){amavis_watchdog_load_conf();}


	if(preg_match("#([0-9]+)\*([0-9]+)#",$GLOBALS["AMAVIS_WATCHDOG_CHILD_TIMEOUT"],$re)){
		$seconds=intval($re[2]);
		$int=intval($re[1]);
		$AmavisWatchdogMaxInterval=round($int*$seconds)/60;
	}else{
		$AmavisWatchdogMaxInterval=50;
	}

	$AmavisWatchdogFinalInterval=$AmavisWatchdogMaxInterval+5;

	if(!is_numeric($AmavisWatchdogMaxInterval)){$AmavisWatchdogMaxInterval=50;}
	if(!is_numeric($GLOBALS["AMAVIS_WATCHDOG_MAX_SERVERS"])){$GLOBALS["AMAVIS_WATCHDOG_MAX_SERVERS"]=5;}


	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAmavisWatchdog");
	$AmavisWatchdogMaxCPU=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("AmavisWatchdogMaxCPU");
	$AmavisWatchdogKillProcesses=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("AmavisWatchdogKillProcesses");
	if(!is_numeric($enabled)){$enabled=1;}
	if($enabled==0){return;}

	if(!is_numeric($AmavisWatchdogMaxCPU)){$AmavisWatchdogMaxCPU=80;}
	if(!is_numeric($AmavisWatchdogKillProcesses)){$AmavisWatchdogKillProcesses=1;}


	if(!isset($GLOBALS["psbin"])){$GLOBALS["psbin"]=$GLOBALS["CLASS_UNIX"]->find_program("ps");}
	if(!isset($GLOBALS["grepbin"])){$GLOBALS["grepbin"]=$GLOBALS["CLASS_UNIX"]->find_program("grep");}
	if(!isset($GLOBALS["killbin"])){$GLOBALS["killbin"]=$GLOBALS["CLASS_UNIX"]->find_program("kill");}

	if(!isset($GLOBALS["AMAVIS_WATCHDOG"])){
		if(is_file("/etc/artica-postfix/amavis.watchdog.cache")){
			$GLOBALS["AMAVIS_WATCHDOG"]=unserialize(@file_get_contents("/etc/artica-postfix/amavis.watchdog.cache"));
		}
	}
	$notify_text="";
	$cmd="{$GLOBALS["psbin"]} aux|{$GLOBALS["grepbin"]} -E \"amavisd \(\" 2>&1";
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	exec("$cmd",$results);
	$childs=0;
	while (list ($num, $line) = each ($results)){
		if(preg_match("#[a-z]+\s+([0-9]+)\s+([0-9\.]+)\s+([0-9\.]+).+?amavisd\s+\((.+?)\)#",$line,$re)){
			$type=$re[4];
			$pid=$re[1];
			$cpu_pourc=intval($re[2]);
			$cpumem=$re[3];
			$time=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($pid);
			$time_by_pid=$time;
				
			$rss=$GLOBALS["CLASS_UNIX"]->PROCESS_MEMORY($pid);
			$vm=$GLOBALS["CLASS_UNIX"]->PROCESS_CACHE_MEMORY($pid);
				
			$array_status[$pid]=array("TYPE"=>$type,"CPU"=>$cpu_pourc,"TIME"=>$time
			,"RSS"=>$rss,"VMSIZE"=>$vm
				
			);
			if($type<>"master"){if($type<>"virgin"){if($type<>"virgin child"){$childs++;}}}
			$info="$childs/{$GLOBALS["AMAVIS_WATCHDOG_MAX_SERVERS"]} Found child type:$type pid:$pid CPU:$cpu_pourc% Memory:{$rss}Mb Cached :{$vm}Mb running since {$time}Mn max running:{$AmavisWatchdogFinalInterval}Mn";
			events("$info",__FUNCTION__,__LINE__);
			$text[]="$info";
				
			if($type<>"master"){
				if($time_by_pid>=$AmavisWatchdogFinalInterval){
					events("Killing $pid pid...",__FUNCTION__,__LINE__);
					shell_exec2("{$GLOBALS["killbin"]} -9 $pid");
					$notify_text="This process has been killed";
					$GLOBALS["CLASS_UNIX"]->send_email_events("Warning Amavis child ($type) reach {$AmavisWatchdogFinalInterval}Mn ({$time_by_pid}Mn)",
						"Amavis child PID $pid using $cpu_pourc and has been detected {$GLOBALS["AMAVIS_WATCHDOG"][$pid]["count"]} times
						 in {$time_by_pid}Mn
						 $notify_text
						 \n".@implode("\n",$text),"AmavisWatchdog");
						 amavis_watchdog_removebayes();
						 continue;
				}
			}
				
				
				
			if($cpu_pourc>$AmavisWatchdogMaxCPU){
				events("Warning on pid $pid",__FUNCTION__,__LINE__);
				if(!isset($GLOBALS["AMAVIS_WATCHDOG"][$pid]["time"])){
					$GLOBALS["AMAVIS_WATCHDOG"][$pid]["time"]=time();
					$GLOBALS["AMAVIS_WATCHDOG"][$pid]["count"]=1;
					continue;
				}else{
					$min_interval=calc_time_min($GLOBALS["AMAVIS_WATCHDOG"][$pid]["time"]);
					$GLOBALS["AMAVIS_WATCHDOG"][$pid]["count"]=$GLOBALS["AMAVIS_WATCHDOG"][$pid]["count"]+1;
					events("Last detected time $min_interval minutes add score +1 -> {$GLOBALS["AMAVIS_WATCHDOG"][$pid]["count"]}",__FUNCTION__,__LINE__);
					if($min_interval>$AmavisWatchdogMaxInterval){
						if($AmavisWatchdogKillProcesses==1){
							shell_exec2("{$GLOBALS["killbin"]} -9 $pid");
							$notify_text="This process has been killed";
						}
						$GLOBALS["CLASS_UNIX"]->send_email_events("Warning Amavis child reach $AmavisWatchdogMaxCPU% CPU after {$AmavisWatchdogMaxInterval}Mn max running:$AmavisWatchdogFinalInterval",
						"Amavis child PID $pid using $cpu_pourc and has been detected {$GLOBALS["AMAVIS_WATCHDOG"][$pid]["count"]} times
						 in {$min_interval}Mn
						 $notify_text
						 \n".@implode("\n",$text),"AmavisWatchdog");
						 amavis_watchdog_removebayes();
						 continue;
					}
						
						

				}

			}else{
				if(isset($GLOBALS["AMAVIS_WATCHDOG"][$pid])){
					events("Remove warning on pid $pid",__FUNCTION__,__LINE__);
					unset($GLOBALS["AMAVIS_WATCHDOG"][$pid]);
				}

			}
		}
	}

	/*if($childs>=$GLOBALS["AMAVIS_WATCHDOG_MAX_SERVERS"]){
		$GLOBALS["CLASS_UNIX"]->send_email_events("Warning Amavis reach the maximal servers processes",
		"You have defined Amavis to run {$GLOBALS["AMAVIS_WATCHDOG_MAX_SERVERS"]}, you need to increase this value\nhere it
		is the processes list:\n".@implode("\n",$text),"postfix");
		}*/


	if(is_array($GLOBALS["AMAVIS_WATCHDOG"])){
		while (list ($pid, $array) = each ($GLOBALS["AMAVIS_WATCHDOG"])){
			events("in memory... PID:$pid",__FUNCTION__,__LINE__);
			if(!$GLOBALS["CLASS_UNIX"]->process_exists($pid)){
				events("remove from memory... PID:$pid",__FUNCTION__,__LINE__);
				unset($GLOBALS["AMAVIS_WATCHDOG"][$pid]);
			}
		}

	}

	@file_put_contents("/etc/artica-postfix/amavis.watchdog.cache",@serialize($GLOBALS["AMAVIS_WATCHDOG"]));
	events("Save /usr/share/artica-postfix/ressources/logs/amavis.infos.array",__FUNCTION__,__LINE__);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/amavis.infos.array",@serialize($array_status));
	@chmod("/usr/share/artica-postfix/ressources/logs/amavis.infos.array",0777);

}




function CleanCloudCatz(){
	$pidfile="/etc/artica-postfix/pids/CleanCloudCatz.pid";
	$f=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/CleanCloudCatz"));
	events("CleanCloudCatz:: `$f`");
	if(!is_numeric($f)){return;}
	if($f<>1){return;}
	$pid=trim(@file_get_contents($pidfile));
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){
		xdcloudlogs("CleanCloudCatz:: `$pid` -> run...Abort");
		return;
	}
	$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.cleancloudcatz.php --nocatz >/dev/null 2>&1 &");
	xdcloudlogs($cmd);
	shell_exec2($cmd);
	$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.cleancloudcatz.php --catz >/dev/null 2>&1 &");
	xdcloudlogs($cmd);
	shell_exec2($cmd);
}

function xdcloudlogs($text=null){
	$logFile="/var/log/cleancloud.log";
	$time=date("Y-m-d H:i:s");
	$PID=getmypid();
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
	if (is_file($logFile)) {
		$size=filesize($logFile);
		if($size>1000000){unlink($logFile);}
	}
	$logFile=str_replace("//","/",$logFile);
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$time [$PID]:exec.status.php:: $text\n");
	@fclose($f);
}

function MemoryWatchdog(){
	
	events("Starting Scanning memory....",__FUNCTION__,__LINE__);
	if(is_dir("/home/artica/system/perf-queue")){
		$Dirs=$GLOBALS["CLASS_UNIX"]->dirdir("/home/artica/system/perf-queue");
		$CountOfDirs=count($Dirs);
		$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.monit-queue.php.Watch.time");
		events("Perfs Queue: $CountOfDirs Time: {$filetime}Mn",__FUNCTION__,__LINE__);
		if($filetime>5){
			if(count($Dirs)>0){
				$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.monit-queue.php >/dev/null 2>&1");
				events("Perfs Queue: $cmd",__FUNCTION__,__LINE__);
				shell_exec2($cmd);
			}
		}
	}
	
	
	
	$SessionPathInMemoryInfos_array=$GLOBALS["CLASS_UNIX"]->SessionPathInMemoryInfos();
	$SessionPathInMemoryInfos=base64_encode(serialize($SessionPathInMemoryInfos_array));
	@file_put_contents("/etc/artica-postfix/settings/Daemons/SessionPathInMemoryInfos", $SessionPathInMemoryInfos);
	
	
	
	$SwapOffOn=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SwapOffOn")));
	if(!isset($SwapOffOn["AutoMemWatchdog"])){$SwapOffOn["AutoMemWatchdog"]=1;}
	if(!isset($SwapOffOn["AutoMemPerc"])){$SwapOffOn["AutoMemPerc"]=90;}
	if(!isset($SwapOffOn["AutoMemInterval"])){$SwapOffOn["AutoMemInterval"]=180;}
	
	if(!is_numeric($SwapOffOn["AutoMemWatchdog"])){$SwapOffOn["AutoMemWatchdog"]=1;}
	if(!is_numeric($SwapOffOn["AutoMemPerc"])){$SwapOffOn["AutoMemPerc"]=90;}
	if(!is_numeric($SwapOffOn["AutoMemInterval"])){$SwapOffOn["AutoMemInterval"]=180;}
	if($SwapOffOn["AutoMemWatchdog"]==0){return;}
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.swap-monitor.php.Watch.time");
	if($filetime<$SwapOffOn["AutoMemInterval"]){return;}
	$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.swap-monitor.php --watchdog >/dev/null 2>&1");
	shell_exec2($cmd);
}




function MemorySync(){
	
	$filecacheInodes="/etc/artica-postfix/cron.1/InodeSync.time";
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filecacheInodes);
	
	if($filetime>30){		
		if(is_file($filecacheInodes)){@unlink($filecacheInodes);}
		$DISK_INODES=$GLOBALS["CLASS_UNIX"]->DISK_INODES();
		while (list ($num, $ligne) = each ($DISK_INODES) ){
			$POURC=$ligne["POURC"];
			if($POURC>90){
				squid_admin_mysql(1,"Alertes too many files on partition $num {$POURC}% used",
				"Please remove some files on this partition",__FILE__,__LINE__);
				
				system_admin_mysql(1,"Alertes too many files on partition $num {$POURC}% used",
				"Please remove some files on this partition",__FILE__,__LINE__);
				
				@file_put_contents($filecacheInodes, time());
				continue;
			}
		}
	}
	
	
	
	
	
	$TOTAL_MEM_POURCENT_USED=$GLOBALS["CLASS_UNIX"]->TOTAL_MEM_POURCENT_USED();
	$GLOBALS["CLASS_UNIX"]->ToSyslog("Memory use {$TOTAL_MEM_POURCENT_USED}%");
	$filecache="/etc/artica-postfix/cron.1/MemorySync.time";
	$filecache_80="/etc/artica-postfix/cron.1/MemorySync80.time";
	$filecache_90="/etc/artica-postfix/cron.1/MemorySync90.time";
	$filecache_100="/etc/artica-postfix/cron.1/MemorySync99.time";
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filecache);
	if($TOTAL_MEM_POURCENT_USED>80){
		if($TOTAL_MEM_POURCENT_USED<90){
			$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filecache_80);
			if($filetime>15){
				@unlink($filecache_80);
				@file_put_contents($filecache_80, time());
				squid_admin_mysql(2,"System memory exceed {$TOTAL_MEM_POURCENT_USED}%",
				"Timeout {$filetime}Mn\nYou will find here a snapshot of current tasks\n".$GLOBALS["CLASS_UNIX"]->ps_mem_report(),__FILE__,__LINE__);
			}
		}
	}
	
	if($TOTAL_MEM_POURCENT_USED>89){
		if($TOTAL_MEM_POURCENT_USED<97){
			$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filecache_90);
			if($filetime>10){
				@unlink($filecache_90);
				@file_put_contents($filecache_90, time());
				squid_admin_mysql(1,"System memory exceed {$TOTAL_MEM_POURCENT_USED}%",
				"Timeout {$filetime}Mn\nYou will find here a snapshot of current tasks\n".$GLOBALS["CLASS_UNIX"]->ps_mem_report(),__FILE__,__LINE__);
			}
		}
	}

	if($TOTAL_MEM_POURCENT_USED>97){
			$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filecache_100);
			if($filetime>10){
				@unlink($filecache_100);
				@file_put_contents($filecache_100, time());
				squid_admin_mysql(0,"System memory exceed {$TOTAL_MEM_POURCENT_USED}% (action {$filetime}Mn/20mn)",
				"Timeout {$filetime}Mn\nYou will find here a snapshot of current tasks\n".$GLOBALS["CLASS_UNIX"]->ps_mem_report(),__FILE__,__LINE__);
			}
		}	
	
	

}

function SwapWatchdog(){
	$reboot=false;
	$DisableSWAPP=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableSWAPP");
	if(!is_numeric($DisableSWAPP)){$DisableSWAPP=0;}
	if($DisableSWAPP==1){return;}
	$notif=null;
	
	mkdir_test("/etc/artica-postfix/cron.1",0755,true);
	$filecache="/etc/artica-postfix/cron.1/SwapOffOn.time";
	$filecache20="/etc/artica-postfix/cron.1/SwapOffOn20.time";
	$filecache50="/etc/artica-postfix/cron.1/SwapOffOn50.time";
	$filecache100="/etc/artica-postfix/cron.1/SwapOffOn50.time";
	$ps=$GLOBALS["CLASS_UNIX"]->find_program("ps");
	
	$SwapOffOn=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SwapOffOn")));
	if(!isset($SwapOffOn["SwapEnabled"])){$SwapOffOn["SwapEnabled"]=1;}
	if(!isset($SwapOffOn["SwapMaxPourc"])){$SwapOffOn["SwapMaxPourc"]=20;}
	if(!isset($SwapOffOn["SwapMaxMB"])){$SwapOffOn["SwapMaxMB"]=0;}
	if(!isset($SwapOffOn["SwapTimeOut"])){$SwapOffOn["SwapTimeOut"]=60;}
	
	
	if(!is_numeric($SwapOffOn["SwapEnabled"])){$SwapOffOn["SwapEnabled"]=1;}
	if(!is_numeric($SwapOffOn["SwapMaxPourc"])){$SwapOffOn["SwapMaxPourc"]=20;}
	if(!is_numeric($SwapOffOn["SwapMaxMB"])){$SwapOffOn["SwapMaxMB"]=0;}
	if(!is_numeric($SwapOffOn["SwapTimeOut"])){$SwapOffOn["SwapTimeOut"]=60;}
	
	include_once(dirname(__FILE__)."/ressources/class.main_cf.inc");
	$sys=new systeminfos();
	if($sys->swap_used==0){return;}
	if($sys->swap_total==0){return;}
	if($sys->swap_used==$sys->swap_total){return;}
	
	events("$sys->swap_used/$sys->swap_total ",__FUNCTION__,__LINE__);
	$pourc=round(($sys->swap_used/$sys->swap_total)*100);
	
	$notif=$notif."$sys->swap_used/$sys->swap_total\n";
	
	events("{$sys->swap_used}MB used ($pourc%)",__FUNCTION__,__LINE__);
	
	
	if($pourc>20){
		if($pourc<50){
			$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filecache20);
			if($filetime>30){
				@unlink($filecache20);
				@file_put_contents($filecache20, time());
				
				squid_admin_mysql(1,"[INFO]: System swap exceed {$pourc}%",
				"Time {$filetime}Mn\nYou will find here a snapshot of current tasks\n".$GLOBALS["CLASS_UNIX"]->ps_mem_report(),__FILE__,__LINE__);
			}
		}
	}
	
	if($pourc>50){
		if($pourc<70){
			$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filecache50);
			if($filetime>15){
				@unlink($filecache50);
				@file_put_contents($filecache50, time());
				
				squid_admin_mysql(1,"[WARNING]: System swap exceed {$pourc}%",
				"Time {$filetime}Mn\nYou will find here a snapshot of current tasks\n".$GLOBALS["CLASS_UNIX"]->ps_mem_report(),__FILE__,__LINE__);
			}
		}
	}	
	if($pourc>70){
		$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filecache100);
		if($filetime>10){
			@unlink($filecache100);
			@file_put_contents($filecache100, time());
			squid_admin_mysql(0,"[ALERT!!]: System swap exceed {$pourc}%",
			"Time {$filetime}Mn\nYou will find here a snapshot of current tasks\n".$GLOBALS["CLASS_UNIX"]->ps_mem_report(),__FILE__,__LINE__);
		}
		
	}	
	
	if($SwapOffOn["SwapEnabled"]==0){return;}
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filecache);
	if($filetime<$SwapOffOn["SwapTimeOut"]){
		events("{$filetime}Mn need to wait {$SwapOffOn["SwapTimeOut"]}mn",__FUNCTION__,__LINE__);
		return;
	}
	
	if($SwapOffOn["SwapMaxMB"]>0){
		if($sys->swap_used>$SwapOffOn["SwapMaxMB"]){
			$execeed_text=$SwapOffOn["SwapMaxMB"]."MB";
			$reboot=true;
		}
	}
	if($SwapOffOn["SwapMaxMB"]==0){
		if($pourc>3){
			if($pourc>$SwapOffOn["SwapMaxPourc"]){
				$execeed_text=$SwapOffOn["SwapMaxPourc"]."%";
				$reboot=true;
			}
		}
	}
	@unlink($filecache);
	@file_put_contents($filecache,time());
	if(!$reboot){return;}

	$swapoff=$GLOBALS["CLASS_UNIX"]->find_program("swapoff");
	$swapon=$GLOBALS["CLASS_UNIX"]->find_program("swapon");

	if(!is_file($swapoff)){events("swapoff no such file",__FUNCTION__,__LINE__);shell_exec2("sync; echo \"3\" > /proc/sys/vm/drop_caches >/dev/null 2>&1");return;}
	if(!is_file($swapon)){events("swapon no such file",__FUNCTION__,__LINE__);shell_exec2("sync; echo \"3\" > /proc/sys/vm/drop_caches >/dev/null 2>&1");return;}

	
	$time=time();
	if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("SwapWatchdog:: Starting to purge the swap file because it execeed rules", basename(__FILE__));}
	$cmd="$swapoff -a 2>&1";

	$results=array();
	$results[]=$cmd;
	events("running $cmd",__FUNCTION__,__LINE__);
	exec($cmd,$results);

	$cmd="$swapon -a 2>&1";

	$results[]=$cmd;
	events("running $cmd",__FUNCTION__,__LINE__);
	exec($cmd,$results);

	$text=@implode("\n",$results);
	$time_duration=distanceOfTimeInWords($time,time());
	events("results: $time_duration\n $text",__FUNCTION__,__LINE__);
	
	$notif=$notif."\nMemory swap purge $execeed_text ($time_duration)\n$text";
	$notif=$notif."\n".$GLOBALS["CLASS_UNIX"]->ps_mem_report();
	
	squid_admin_mysql(1,"Memory swap purge $execeed_text","(Execution time: $time_duration)",__FILE__,__LINE__);
	$GLOBALS["CLASS_UNIX"]->send_email_events("Memory swap purge $execeed_text (task time execuction: $time_duration)",$text,"system");
	
	$sqdbin=$GLOBALS["CLASS_UNIX"]->find_program("squid");
	if(!is_file($sqdbin)){$sqdbin=$GLOBALS["CLASS_UNIX"]->find_program("squid3");}	
	if(is_file($sqdbin)){
		$php5=$GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN();
		$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
		if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
		squid_admin_mysql(1,"Asking to reload proxy service after purging the Swap file","$executed\n$notif",__FILE__,__LINE__);
		if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("SwapWatchdog:: reloading Squid after purging the Swap file", basename(__FILE__));}
		shell_exec2("$nohup $php5 /usr/share/artica-postfix/exec.squid.php --reload-squid --bywatchdog >/dev/null 2>&1 &");
	}	
	

}

function CleanLogs(){
	
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	
	$df=$GLOBALS["CLASS_UNIX"]->find_program("df");
	$rm=$GLOBALS["CLASS_UNIX"]->find_program("rm");
	$php5=$GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN();
	$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
	$chmod=$GLOBALS["CLASS_UNIX"]->find_program("chmod");
	
	
	
	exec("$df -i /usr/share/artica-postfix 2>&1",$results);
	$INODESARTICA=0;
	while (list ($num, $line) = each ($results) ){
		if(preg_match("#.*?\s+[0-9]+\s+[0-9]+\s+[0-9]+\s+([0-9]+)%\s+\/usr\/share\/artica-postfix#", $line,$re)){$INODESARTICA=$re[1];}
	}
	if($INODESARTICA>95){
		shell_exec2("$rm -rf /usr/share/artica-postfix/ressources/logs/web/*.html");
		shell_exec2("$rm -rf /usr/share/artica-postfix/ressources/logs/web/*.log");
		shell_exec2("$rm -rf /usr/share/artica-postfix/ressources/logs/web/*.cache");
		shell_exec2("$rm -rf /usr/share/artica-postfix/ressources/logs/jGrowl/*");
		shell_exec2("$rm -rf /usr/share/artica-postfix/ressources/conf/*");
		
	}
	
	if(!is_dir("/etc/artica-postfix/settings/Daemons")){mkdir_test("/etc/artica-postfix/settings/Daemons",true);}
	@chmod("/etc/artica-postfix/settings/Daemons",0755);
	shell_exec2("$chmod 0755 /etc/artica-postfix/settings/Daemons/* >/dev/null 2>&1");
	
	if(is_file("/var/log/php.log")){
		$size=$GLOBALS["CLASS_UNIX"]->file_size("/var/log/php.log");
		$size=intval(round(($size/1024))/1000);
		if($size>150){
			@unlink("/var/log/php.log");
			@file_put_contents("/var/log/php.log", "#");
			@chmod("/var/log/php.log", 0777);
		}
	}
	


	
	$MirrorEnableDebian=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MirrorEnableDebian");
	if(!is_numeric($MirrorEnableDebian)){$MirrorEnableDebian=0;}
	if($MirrorEnableDebian==1){
		$TIME=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.debian.mirror.php.debian_size.time");
		if($TIME>30){shell_exec2("$nohup $php5 /usr/share/artica-postfix/exec.debian.mirror.php --debian-size >/dev/null 2>&1 &");}
		
		$MirrorDebianEachMn=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MirrorDebianEachMn");
		
		$MirrorDebianMaxExecTime=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MirrorDebianMaxExecTime");
		if($MirrorDebianMaxExecTime>0){shell_exec2("$nohup $php5 /usr/share/artica-postfix/exec.debian.mirror.php --kill >/dev/null 2>&1 &");}
		
		if(!is_numeric($MirrorDebianEachMn)){$MirrorDebianEachMn=2880;}
		$pidtime="/etc/artica-postfix/pids/DEBIAN_MIRROR_EXECUTION.TIME";
		$TIME=$GLOBALS["CLASS_UNIX"]->file_time_min($pidtime);
		if($TIME>$MirrorDebianEachMn){shell_exec2("$nohup $php5 /usr/share/artica-postfix/exec.debian.mirror.php --start-exec >/dev/null 2>&1 &");}
	}
	
}

function xLoadAvg(){
	if(!isset($GLOBALS["CLASS_UNIX"])){CheckCallable();}
	if(!function_exists("sys_getloadavg")){return;}
	$timeDaemonFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if(!is_file($timeDaemonFile)){@file_put_contents($timeDaemonFile, time());$GLOBALS["FORCE"]=true;}
	$DaemonTime=$GLOBALS["CLASS_UNIX"]->file_time_min($timeDaemonFile);

	if($GLOBALS["VERBOSE"]){echo "\"$timeDaemonFile\" : $DaemonTime minutes...\n";}

	if(!$GLOBALS["FORCE"]){
		if($DaemonTime<3){
			if($GLOBALS["VERBOSE"]){echo "End due of time\n";}
			return;
		}
	}
	@unlink($timeDaemonFile);
	@file_put_contents($timeDaemonFile, time());
	$array_load=sys_getloadavg();
	$ttt=time();
	$internal_load=$array_load[0];
	if($GLOBALS["VERBOSE"]){echo "System load $internal_load\n";}
	if(!is_dir("/var/log/artica-postfix/loadavg")){mkdir_test("/var/log/artica-postfix/loadavg",644,true);}
	@file_put_contents("/var/log/artica-postfix/loadavg/$ttt", $internal_load);
}

function launch_all_status_cmdline(){
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	if($GLOBALS["VERBOSE"]){echo "launch_all_status_cmdline()\n";}
	$pids="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$CacheFileTime="/usr/share/artica-postfix/ressources/logs/global.status.ini";
	$GLOBALS["CLASS_UNIX"]=new unix();
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pids);
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return;}
	@file_put_contents($pids, getmypid());
	$time=$GLOBALS["CLASS_UNIX"]->file_time_min($CacheFileTime);
	if(!$GLOBALS["VERBOSE"]){
		if($time<2){
			events("{$time}mn, need at least 2mn",__FUNCTION__,__LINE__);
			return;
		}
	}
	
	@unlink($CacheFileTime);
	@file_put_contents($CacheFileTime, "\n");
	events("-> launch_all_status()",__FUNCTION__,__LINE__);
	launch_all_status();
}


function killstrangeprocesses(){
	

	
		
	
}


function launch_all_status($force=false){
	$conf=array();
	$squid_functions=array();
	$postfix_functions=array();
	$CacheFileTime="/usr/share/artica-postfix/ressources/logs/global.status.ini";
	
	if(!isset($GLOBALS["CLASS_UNIX"])){
		$GLOBALS["CLASS_SOCKETS"]=new sockets();
		$GLOBALS["CLASS_USERS"]=new settings_inc();
		$GLOBALS["CLASS_UNIX"]=new unix();		
	}

	
	mkdir_test("/usr/share/artica-postfix/ressources/logs",0755,true);
	if(!is_file("/usr/share/artica-postfix/ressources/logs/php.log")){@touch("/usr/share/artica-postfix/ressources/logs/php.log");}
	events("launch_all_status() -> xLoadAvg().., started",__FUNCTION__,__LINE__);
	xLoadAvg();
	ChecksRoutes();
	events("global.status.ini OK next step...",__FUNCTION__,__LINE__);
	
	
	
	$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";events("$called",__FUNCTION__,__LINE__);}
	events("global.status.ini OK CheckCallable()",__FUNCTION__,__LINE__);
	CheckCallable();
	if(!system_is_overloaded()){
		$GLOBALS["CLASS_UNIX"]->Process1();
		if(!is_file("/usr/share/artica-postfix/ressources/logs/global.versions.conf")){
			events("-> artica-install --write-version",__FUNCTION__,__LINE__);
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/usr/share/artica-postfix/bin/artica-install --write-versions");
		}else{
			$filetime=file_time_min("/usr/share/artica-postfix/ressources/logs/global.versions.conf");
			events("global.versions.conf={$filetime}mn ",__FUNCTION__,__LINE__);
			if($filetime>60){
				events("global.versions.conf \"$filetime\"mn",__FUNCTION__,__LINE__);
				@unlink("/usr/share/artica-postfix/ressources/logs/global.versions.conf");
				$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/usr/share/artica-postfix/bin/artica-install --write-versions");
			}
		}
	}
	@unlink($GLOBALS["MY-POINTER"]);
	@file_put_contents($GLOBALS["MY-POINTER"],time());
	
	
	$authtailftime="/etc/artica-postfix/pids/auth-tail.time";
	
	$timefile=$GLOBALS["CLASS_UNIX"]->file_time_min($authtailftime);
	events("/etc/artica-postfix/pids/auth-tail.time -> {$timefile}Mn",__FUNCTION__,__LINE__);
	if($timefile>15){
		@unlink($timefile);
		@file_put_contents($authtailftime, time());
		$cmd=trim("{$GLOBALS["nohup"]} /etc/init.d/artica-postfix restart auth-logger >/dev/null 2>&1 &");
		events($cmd);
		shell_exec2($cmd);
	}
	$TimeF="/etc/artica-postfix/pids/exec.system.last.php.xstart.php.time";
	$timefile=$GLOBALS["CLASS_UNIX"]->file_time_min($TimeF);
	if($timefile>60){
		$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.system.last.php >/dev/null 2>&1 &");
		events($cmd);
		shell_exec2($cmd);
	}
	

	
	@unlink($CacheFileTime);
	@file_put_contents($CacheFileTime,time());
	
	
	if(is_dir("/etc/resolvconf")){
		if(!is_file("/etc/resolvconf/resolv.conf.d/base")){
			$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.virtuals-ip.php --resolvconf >/dev/null 2>&1 &");
			events($cmd);
			shell_exec2($cmd);
		}
	}
	
	
	events("**************** START ALL STATUS ****************");
	events("global.status.ini start processing",__FUNCTION__,__LINE__);
	

	
	
	events_syslog("start processing");
	$t1=time();
	

	
	
	
	$GLOBALS["CLASS_UNIX"]->chmod_func(0755, "/etc/artica-postfix/settings/Daemons/*");
	

	$functions=array("Default_values","load_stats","watchdog_me_load","fail2ban","snmpd","bandwidthd","unifi_mongodb","unifi","Popuplate_cron","squid_dashboard_statistics",
	"philesight","cron","transmission_daemon","disks_monitor","firehol_watch_status","postgres",
	"CleanLogs","monit","kav4Proxy_status","dansguardian_master_status","wpa_supplicant","status_schedule_meta",
	"fetchmail","milter_greylist","irqbalance","barnyard2_status","suricata_status",
	"framework","pdns_server","pdns_recursor","cyrus_imap","mysql_server","mysql_mgmt","mysql_replica","openldap","saslauthd","syslogger","amavis",
	"amavis_milter","boa","lighttpd","clamd","clamscan","freshclam","retranslator_httpd","spamassassin_milter","spamassassin",
	 "mailman","kas3_milter","kas3_ap","rpcbind","smbd","nmbd","winbindd","scanned_only","roundcube","cups",
	"gdm","xfce","vmtools","hamachi","artica_notifier","dhcpd_server","pure_ftpd","mldonkey","backuppc","kav4fs","kav4fsavs",
	"apache_ocsweb","ocs_agent","openssh","gluster","auditd","milter_dkim","dropbox",
	"artica_policy","virtualbox_webserv","tftpd","dhcpd_server","crossroads","artica_status","bandwith",
	 "pptpd","pptp_clients","apt_mirror","ddclient","cluebringer","apachesrc","proftpd_status",
	 "zarafa_server2","assp","openvpn","vboxguest","sabnzbdplus","MemorySync","MemoryWatchdog","SwapWatchdog","artica_meta_scheduler",
	"OpenVPNClientsStatus","stunnel","meta_checks","avahi_daemon","CheckCurl","vnstat","NetAdsWatchdog","munin","autofs","greyhole",
	"dnsmasq","iscsi","watchdog_yorel","netatalk","postfwd2","vps_servers","smartd","crossroads_multiple","auth_tail","greyhole_watchdog","greensql","nscd","tomcat",
	"openemm","openemm_sendmail","cgroups","ntpd_server","arpd","ps_mem","ipsec","yaffas","ifconfig_network","testingrrd","zarafa_multi","memcached","UpdateUtilityHTTP",
	"udevd_daemon","dbus_daemon","ejabberd","pymsnt", "arkwsd", "arkeiad","haproxy","privoxy","klms_status","klmsdb_status","klms_milter","CleanLogs",
	"zarafa_search","snort","amavisdb","nginx","nginx_db","checksyslog","freeradius","maillog_watchdog","arp_spoof","caches_pages",
	"php_fpm","php_fcgi","CleanCloudCatz","syslog_db","roundcube_db","Scheduler","exim4","ntopng","redis_server","bwm_ng","XMail","conntrackd","iptables",
	"rdpproxy_authhook","rdpproxy","vde_all","iptables_tasks","l7filter","sealion_agent","syncthing","killstrangeprocesses");
	
	
	ToSyslog("launch_all_status(): ".count($functions));
	
	if(!isset($GLOBALS["ZARAFA_INSTALLED"])){
		$zarafa_server=$GLOBALS["CLASS_UNIX"]->find_program("zarafa-server");
		if(is_file($zarafa_server)){$GLOBALS["ZARAFA_INSTALLED"]=true;}else{$GLOBALS["ZARAFA_INSTALLED"]=false;}
	}

	
	$postfix_functions=array();
	$postconf=$GLOBALS["CLASS_UNIX"]->find_program("postconf");
	ToSyslog("launch_all_status(): postconf: $postconf");
	if(is_file($postconf)){
		include_once('/usr/share/artica-postfix/ressources/class.status.postfix.inc');
		$postfix_functions=postfix_increment_func(array());
		if($GLOBALS["ZARAFA_INSTALLED"]){
			include_once('/usr/share/artica-postfix/ressources/class.status.zarafa.inc');
			$postfix_functions=zarafa_increment_func($postfix_functions);
		}
	}
	
	$LOAD_SQUID_LIB=false;
	if(is_file("/etc/artica-postfix/STATS_APPLIANCE")){$LOAD_SQUID_LIB=true;}
	$AsCategoriesAppliance=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/AsCategoriesAppliance"));
	if($GLOBALS["SQUID_INSTALLED"]){$LOAD_SQUID_LIB=true;}
	if($AsCategoriesAppliance){$LOAD_SQUID_LIB=true;}
	
	if($LOAD_SQUID_LIB){
		include_once('/usr/share/artica-postfix/ressources/class.status.squid.inc');
		$squid_functions=squid_increment_func(array());
		
	}
	
	
	
	
	
	ToSyslog("launch_all_status(): ".count($functions));
	$stats=new status_hardware();
	$data1=$GLOBALS["TIME_CLASS"];
	$data2 = time();
	$difference = ($data2 - $data1);
	$min=round($difference/60);
	if($min>9){
		events("reloading classes...",__FUNCTION__,__LINE__);
		$GLOBALS["TIME_CLASS"]=time();
		$GLOBALS["CLASS_SOCKETS"]=new sockets();
		$GLOBALS["CLASS_USERS"]=new settings_inc();
		$GLOBALS["CLASS_UNIX"]=new unix();
	}
	

	

	$AllFunctionCount=count($functions);
    events("running $AllFunctionCount functions ",__FUNCTION__,__LINE__);
  	if($force){events("running function in FORCE MODE !",__FUNCTION__,__LINE__);}
  	$max=count($functions);
  	$c=0;
  	$TEX=time();
	while (list ($num, $func) = each ($functions) ){
		$c++;
		$mem=round(((memory_get_usage()/1024)/1000),2);
		if($GLOBALS["VERBOSE"]){echo "*****\n$func $c/$max\n*****\n";}
		if(!function_exists($func)){ continue; }
		events("Running $c/$max $func() function {$mem}MB",__FUNCTION__,__LINE__);
		
		if(is_file("/etc/artica-postfix/ARTICA_STATUS_RELOAD")){
			ToSyslog("Reloading settings and libraries...");
			Reload();
		}	
			
		if(!$force){
			if(system_is_overloaded(basename(__FILE__))){
				events("System is overloaded: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting",__FUNCTION__,__LINE__);
				ToSyslog("System is overloaded: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting");
				AmavisWatchdog();
				greyhole_watchdog();
				break;
			}
		}

			
		try {
			if($GLOBALS["VERBOSE"]){echo "***** $c/$max $func *****\n";}
			$results=call_user_func($func);
			$GLOBALS["LAST_FUNCTION_USED"]="$func()";
			} catch (Exception $e) {
				ToSyslog("Fatal while running function $func ($e)");
			}
				
			if(trim($results)<>null){$conf[]=$results;}
		
	}

	

	
	events("Postfix functions: ".count($postfix_functions)." functions",__FUNCTION__,__LINE__);
	if(count($postfix_functions)>0){
		$c=0;$max=count($postfix_functions);
		while (list ($num, $func) = each ($postfix_functions) ){
			
			$c++;
			$mem=round(((memory_get_usage()/1024)/1000),2);
			if($GLOBALS["VERBOSE"]){echo "*****\npostfix_functions $func $c/$max\n*****\n";}
			events("Postfix functions: Running $c/$max $func() function {$mem}MB",__FUNCTION__,__LINE__);
			if(!function_exists($func)){ continue; }
			
			if(!$force){
				if(system_is_overloaded(basename(__FILE__))){
					events("System is overloaded: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting",__FUNCTION__,__LINE__);
					ToSyslog("System is overloaded: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting");
					AmavisWatchdog();
					greyhole_watchdog();
					break;
				}
			}			
			
			
			
			try {
				$results=call_user_func($func);
			} catch (Exception $e) {
				ToSyslog("Fatal while running function $func ($e)");
			}
			
			if(trim($results)<>null){$conf[]=$results;}
			
		}
	}
	
	
	
	events("Squid functions: ".count($squid_functions)." functions",__FUNCTION__,__LINE__);
	if(count($squid_functions)>0){
		$c=0;$max=count($squid_functions);
		while (list ($num, $func) = each ($squid_functions) ){
			$mem=round(((memory_get_usage()/1024)/1000),2);
			if($GLOBALS["VERBOSE"]){echo "*****\n$func $c/$max\n*****\n";}
			if(!function_exists($func)){ 
				events("Squid functions: $func() No such function",__FUNCTION__,__LINE__);
				
				continue; }
			events("Squid functions: Running $c/$max $func() function {$mem}MB",__FUNCTION__,__LINE__);			
			_statussquid("Launch $func(): {$mem}MB in memory");
			$c++;
			try {
				$results=call_user_func($func);
			} catch (Exception $e) {
				events("Fatal while running function $func ($e)",__FUNCTION__,__LINE__);
				_statussquid("Fatal while running function $func ($e)");
			}
				
			if(trim($results)<>null){$conf[]=$results;}
				
		}
	}	
	
	
	
	
	
	
	
	
	$p=new processes_php();
	$p->MemoryInstances();
	$p=null;
	
	$TOOK=$GLOBALS["CLASS_UNIX"]->distanceOfTimeInWords($TEX,time(),true);
	$mem=round(((memory_get_usage()/1024)/1000),2);
	$percent_free=$GLOBALS["CLASS_UNIX"]->GetMemFreePourc();
	ToSyslog("Executed ". count($functions)." functions in $TOOK MemFree {$percent_free}% Used memory: {$mem}MB");
	
	
	
	@unlink("/usr/share/artica-postfix/ressources/logs/global.status.ini");
	file_put_contents("/usr/share/artica-postfix/ressources/logs/global.status.ini",@implode("\n",$conf));
	@chmod("/usr/share/artica-postfix/ressources/logs/global.status.ini",0777);
	@file_put_contents("/etc/artica-postfix/cache.global.status",@implode("\n",$conf));
	events("creating status done ". count($conf)." lines....",__FUNCTION__,__LINE__);
	$sock=new sockets();
	$WizardSavedSettingsSend=$sock->GET_INFO("WizardSavedSettingsSend");
	if(!is_numeric($WizardSavedSettingsSend)){$WizardSavedSettingsSend=0;}
	if($WizardSavedSettingsSend==0){
		$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.web-community-filter.php --register >/dev/null 2>&1 &");
		shell_exec2($cmd);		
	}
	
	if(!is_file("/usr/share/artica-postfix/ressources/settings.inc")){
		$GLOBALS["CLASS_UNIX"]->Process1(true);
	}
	
	if(is_dir("/opt/artica-agent/usr/share/artica-agent/ressources")){
		events("writing /opt/artica-agent/usr/share/artica-agent/ressources/status.ini",__FUNCTION__,__LINE__);
		@file_put_contents("/opt/artica-agent/usr/share/artica-agent/ressources/status.ini",@implode("\n",$conf));
	}
	
	
	
	if(system_is_overloaded(__FILE__)){
		ToSyslog("Overloaded system {$GLOBALS["SYSTEM_INTERNAL_LOAD"]} {$GLOBALS["SYSTEM_INTERNAL_MEMM"]}MB Memory free");
		return;
	}
	
	$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.syslog-engine.php --admin-evs >/dev/null 2>&1 &");
	events($cmd);
	shell_exec2($cmd);
	
	
	$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".__FILE__." --samba >/usr/share/artica-postfix/ressources/logs/web/samba.status 2>&1 &");
	shell_exec2($cmd);
	
	
	$GLOBALS["CLASS_UNIX"]->BLKID_ALL();
	events("*****  FINISH $TOOK ****",__FUNCTION__,__LINE__);
	events("********************************************************************",__FUNCTION__,__LINE__);
	if($GLOBALS["VERBOSE"]){echo " *****  FINISH **** \n\n";}
	

	
	

}
// ========================================================================================================================================================

function artica_meta_scheduler(){
	if($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaMetaEnabled")==0){events("Meta Server console is disabled....",__FUNCTION__,__LINE__);return;}
	if($GLOBALS["PHP5"]==null){$GLOBALS["PHP5"]=LOCATE_PHP5_BIN2();}
	$agent_pid="/etc/artica-postfix/pids/exec.artica.meta.php.SendStatus.pid";
	$filetime=file_time_min($agent_pid);
	events("pid return {$filetime}Mn",__FUNCTION__,__LINE__);
	$cmd="{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ". dirname(__FILE__)."/exec.artica.meta.php --status";

	if($filetime>15){
		events("It seems that scheduler did not wants to execute agent, i execute it myself",__FUNCTION__,__LINE__);
		$nohup=$GLOBALS["nohup"];
		if(strlen($nohup)>4){$cmd="$nohup $cmd >/dev/null 2>&1 &";}
		events("$cmd",__FUNCTION__,__LINE__);
		shell_exec2($cmd);
		return;
	}

	events("Scheduling status to Meta Server console....GLOBALS[CLASS_UNIX]->THREAD_COMMAND_SET(\"$cmd\")",__FUNCTION__,__LINE__);
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("$cmd");
	events("Done...",__FUNCTION__,__LINE__);


}

function caches_pages(){
	if($GLOBALS["PHP5"]==null){$GLOBALS["PHP5"]=$GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN();}
	$nohup=$GLOBALS["nohup"];
	$nice=$GLOBALS["NICE"];
	if($nohup==null){$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");}
	if($nice==null){$nice=$GLOBALS["CLASS_UNIX"]->EXEC_NICE();}
	
	events("nohup: $nohup",__FUNCTION__,__LINE__);
	events("nice.: $nice",__FUNCTION__,__LINE__);
	events("php5.: {$GLOBALS["PHP5"]}",__FUNCTION__,__LINE__);
	
	if(is_file("/etc/artica-postfix/settings/Daemons/WizardSavedSettings")){
		
		$WizardSavedSettingsTime=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/settings/Daemons/WizardSavedSettings");
		events("WizardSavedSettings: {$WizardSavedSettingsTime}Mn...",__FUNCTION__,__LINE__);
		
		if($WizardSavedSettingsTime>2){
			if($WizardSavedSettingsTime<240){
				if(!is_file("/etc/artica-postfix/WIZARD_INSTALL_EXECUTED")){
					$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.wizard-install.php --noreboot >/dev/null 2>&1 &");
					shell_exec2($cmd);
				}else{
					events("/etc/artica-postfix/WIZARD_INSTALL_EXECUTED ok...");
				}
			}
		}
	}
	

	
}

function testingrrd(){
	return;
	
}


function OpenVPNClientsStatus(){
	$q=new mysql();
	$l=array();
	@unlink("/usr/share/artica-postfix/ressources/logs/openvpn-clients.status");
	$sql="SELECT ID,connexion_name FROM vpnclient WHERE `connexion_type`=2 AND `enabled`=1";
	$results=$q->QUERY_SQL($sql,"artica_backup");

	if(!$q->ok){
		events($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
		return;
	}

	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$id=$ligne["ID"];
		events("Checking VPN client N.$id",__FUNCTION__,__FILE__,__LINE__);
		$l[]="[{$ligne["connexion_name"]}]";
		$l[]="service_name={$ligne["connexion_name"]}";
		$l[]="service_cmd=openvpn";
		$l[]="master_version=".GetVersionOf("openvpn");
		$l[]="service_disabled=1";
		$l[]="family=vpn";
		$l[]="watchdog_features=1";
		$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/etc/artica-postfix/openvpn/clients/$id/pid");

		if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			WATCHDOG("APP_OPENVPN {$ligne["connexion_name"]}","openvpn");
			$l[]="running=0\ninstalled=1";$l[]="";
		}else{
			$l[]="running=1";
			$l[]=GetMemoriesOf($master_pid);
			$l[]="";
		}

	}
	if(is_array($l)){$final=implode("\n",$l);}
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/openvpn-clients.status",$final);
	return $final;

}

function maillog_watchdog(){
	if(!isset($GLOBALS["CLASS_USERS"])){CheckCallable();}
	if(!$GLOBALS["CLASS_USERS"]->POSTFIX_INSTALLED){return;}
	$EnableStopPostfix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStopPostfix"));
	if($EnableStopPostfix==1){return;}
	$maillog_path=$GLOBALS["CLASS_USERS"]->maillog_path;
	if($GLOBALS["VERBOSE"]){echo "maillog_path --> ??? filesize(`$maillog_path`)\n";}
	if(trim($maillog_path)==null){return;}
	$maillog_size=@filesize($maillog_path);
	
	if($GLOBALS["VERBOSE"]){echo "maillog_path --> $maillog_size bytes\n";}
	if($GLOBALS["VERBOSE"]){echo "$maillog_path: $maillog_size Bytes...\n";}
	if($maillog_size<50){
		$GLOBALS["CLASS_UNIX"]->send_email_events("Warning, Log path:$maillog_path Size:$maillog_size bytes.. restarting syslog", "Suspicious size on maillog, restarting system log daemon", "postfix");
		$GLOBALS["CLASS_UNIX"]->RESTART_SYSLOG(true);
	}
	if($GLOBALS["VERBOSE"]){echo "maillog_watchdog finish --> ???\n";}
	
}

//---------------------------------------------------------------------------------------------------
function amavisdb(){
	$EnableStopPostfix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStopPostfix"));
	
	if($EnableStopPostfix==1){return;}
	if(!$GLOBALS["CLASS_USERS"]->AMAVIS_INSTALLED){return;}

	
	$AmavisPerUser=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("AmavisPerUser");
	$pid_path="/var/run/amavis-db.pid";
	
	
	if(!is_numeric($AmavisPerUser)){$AmavisPerUser=0;}
	if($GLOBALS["VERBOSE"]){echo "AmavisPerUser=$AmavisPerUser\n";}
	
		$mysqlversion=GetVersionOf("mysql-ver");
		$l[]="[APP_AMAVISDB]";
		$l[]="service_name=APP_AMAVISDB";
		$l[]="service_cmd=amavisdb";
		$l[]="master_version=".$mysqlversion;
		$l[]="service_disabled=$AmavisPerUser";
		$l[]="family=statistics";
		$l[]="watchdog_features=1";
		if($AmavisPerUser==0){
			$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);
			if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
			shell_exec2("$nohup /etc/init.d/amavis stopdb >/dev/null 2>&1 &");
		}
			$l[]="";
			return implode("\n",$l);
		
		}

		$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);
		$l[]="watchdog_features=1";

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_AMAVISDB","amavisdb");
		$l[]="running=0\ninstalled=1";
		$l[]="";
		return implode("\n",$l);
	}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
	$l[]="running=0\ninstalled=1";$l[]="";
	return implode("\n",$l);return;
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;


}
//---------------------------------------------------------------------------------------------------
function bwm_ng(){
	return;
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("bwm-ng");
	if(!is_file($masterbin)){return;}
   
    $master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($masterbin,true);
    $SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
    $DisableBWMng=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableBWMng"));
    $EnableBwmNG=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableBwmNG"));
    if($DisableBWMng==1){$SquidPerformance=3;}
    if($EnableBwmNG==0){$SquidPerformance=3;}
    
    if($SquidPerformance>2){
    	if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
    		shell_exec2("{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.bwm-ng.php --stop >/dev/null 2>&1");
    		shell_exec2("{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.bwm-ng.php --purge >/dev/null 2>&1");
    		if(is_dir("/home/artica/bwm-ng")){
    			$rm=$GLOBALS["CLASS_UNIX"]->find_program("rm");
    			shell_exec2("$rm -rf /home/artica/bwm-ng");
    		}
    	}
    	
    	
    	$l[]="[APP_BMWNG]";
    	$l[]="service_name=APP_BMWNG";
    	$l[]="service_cmd=bwm-ng";
    	$l[]="master_version=1.0";
    	$l[]="service_disabled=0";
    	$l[]="family=statistics";
    	$l[]="watchdog_features=1";
    	$l[]="running=0\ninstalled=1";
    	$l[]="";
    	return @implode("\n", $l);
    }
    
    
    
    
    
    
    if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
    	if(!is_file("/etc/init.d/bwm-ng")){
    		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.initslapd.php --bwm-ng >/dev/null 2>&1");
    	}
    	
    	shell_exec2("{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.bwm-ng.php --start >/dev/null 2>&1");
    	$l[]="[APP_BMWNG]";
    	$l[]="service_name=APP_BMWNG";
    	$l[]="service_cmd=bwm-ng";
    	$l[]="master_version=1.0";
    	$l[]="service_disabled=1";
    	$l[]="family=statistics";
    	$l[]="watchdog_features=1";
    	$l[]="running=0\ninstalled=1";
    	$l[]="";
    	return @implode("\n", $l);
    }
    	
    $l[]="[APP_BMWNG]";
    $l[]="service_name=APP_BMWNG";
    $l[]="service_cmd=bwm-ng";
    $l[]="master_version=1.0";
    $l[]="service_disabled=1";
    $l[]="family=statistics";
    $l[]="watchdog_features=1";
    $l[]="installed=1";


   
   $CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.bwm-ng.php.rotate.time");
   if($CacheSchedules>5){
   	shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.bwm-ng.php --rotate >/dev/null 2>&1 &");
   }
   

   $l[]="running=1";
   $l[]=GetMemoriesOf($master_pid);
   $l[]="";
   return implode("\n",$l);
    
}
//---------------------------------------------------------------------------------------------------



function squid_watchdog_events($text){
	$sourcefunction=null;
	$sourceline=null;
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			if(isset($trace[1]["file"])){
				$sourcefile=basename($trace[1]["file"]);
			}
			if(isset($trace[1]["function"])){
				$sourcefunction=$trace[1]["function"];
			}
			if(isset($trace[1]["line"])){
				$sourceline=$trace[1]["line"];
			}
		}

	}

	
	$GLOBALS["CLASS_UNIX"]->events($text,"/var/log/squid.watchdog.log",false,$sourcefunction,$sourceline);
}


function WATCHDOG($APP_NAME,$cmd){
	if(!is_file(dirname(__FILE__)."/exec.watchdog.php")){return;}
	if($GLOBALS["DISABLE_WATCHDOG"]){return null;}
	if(!isset($GLOBALS["ArticaWatchDogList"][$APP_NAME])){$GLOBALS["ArticaWatchDogList"][$APP_NAME]=1;}
	if($GLOBALS["ArticaWatchDogList"][$APP_NAME]==null){$GLOBALS["ArticaWatchDogList"][$APP_NAME]=1;}

	if(systemMaxOverloaded(basename(__FILE__))){
		$array_load=sys_getloadavg();
		$internal_load=$array_load[0];
		$GLOBALS["CLASS_UNIX"]->send_email_events("Artica Watchdog start $APP_NAME is not performed (load $internal_load)","System is very overloaded ($internal_load) all watchdog tasks are stopped and waiting a better time!","system");
		return;
	}

	if($GLOBALS["ArticaWatchDogList"][$APP_NAME]==1){
			
		$cmd="{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.watchdog.php --start-process \"$APP_NAME\" \"$cmd\" >/dev/null 2>&1 &";
		events("WATCHDOG: running $APP_NAME ($cmd)",basename(__FILE__));
		shell_exec2($cmd);

	}

}



// ========================================================================================================================================================
function dansguardian_master_status(){



	if(!$GLOBALS["CLASS_UNIX"]->SQUID_INSTALLED()){return null;}
	if(!$GLOBALS["CLASS_USERS"]->DANSGUARDIAN_INSTALLED){return null;}

	$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DansGuardianEnabled");
	if($SQUIDEnable==0){$enabled=0;}
	if($enabled==null){$enabled=0;}


	$l[]="[DANSGUARDIAN]";
	$l[]="service_name=APP_DANSGUARDIAN";
	$l[]="master_version=".GetVersionOf("dansguardian");
	$l[]="service_cmd=dansguardian";
	$l[]="service_disabled=$enabled";
	$l[]="remove_cmd=--dansguardian-remove";
	$l[]="explain=enable_dansguardian_text";
	$l[]="family=squid";

	if($enabled==0){return implode("\n",$l);return;}

	$master_pid=trim(@file_get_contents("/var/run/dansguardian.pid"));
	if($master_pid==null){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($GLOBALS["CLASS_UNIX"]->find_program("dansguardian"));
	}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_DANSGUARDIAN",'dansguardian');
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		
	}		
		
		
	
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);

}

// ========================================================================================================================================================
function dansguardian_tail_status(){}
// ========================================================================================================================================================

function disks_monitor(){
	$HardDisksWatchDog=unserialize(@file_get_contents('/etc/artica-postfix/settings/Daemons/HardDisksWatchDog'));
	if(count($HardDisksWatchDog)==0){return;}
	include_once(dirname(__FILE__)."/ressources/class.disk.monitor.inc");
	$monitor=new disk_monitor();
	$monitor->Scan();
}


function proxy_pac_status(){


	if(is_file("/opt/artica-agent/bin/php")){return null;}
	if(!$GLOBALS["CLASS_UNIX"]->SQUID_INSTALLED()){return null;}


	$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidEnableProxyPac");
	if($enabled==null){$enabled=0;}
	if($SQUIDEnable==0){$enabled=0;}

	$master_pid=trim(@file_get_contents("/var/run/proxypac.pid"));

	$l[]="[APP_PROXY_PAC]";
	$l[]="service_name=APP_PROXY_PAC";
	$l[]="master_version=1.00";
	$l[]="service_cmd=proxy-pac";
	$l[]="service_disabled=$enabled";

	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);return;}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}

// ========================================================================================================================================================
function wpa_supplicant(){
	if(!$GLOBALS["CLASS_USERS"]->WPA_SUPPLIANT_INSTALLED){return null;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WpaSuppliantEnabled");
	if($enabled==null){$enabled=1;}
	$eth=trim($GLOBALS["CLASS_UNIX"]->GET_WIRELESS_CARD());
	if(trim($eth)==null){$enabled=0;}
	$master_pid=trim(@file_get_contents("/var/run/wpa_supplicant.$eth.pid"));
	$WifiAPEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WifiAPEnable");
	if($WifiAPEnable<>1){$WifiAPEnable=0;}
	if($WifiAPEnable==0){$enabled=0;}

	$l[]="[APP_WPA_SUPPLIANT]";
	$l[]="service_name=APP_WPA_SUPPLIANT";
	$l[]="master_version=".GetVersionOf("wpa_suppliant");
	$l[]="service_cmd=wifi";
	$l[]="service_disabled=$enabled";
	$l[]="family=network";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);return;}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
// ========================================================================================================================================================
function arp_spoof(){
	if(!$GLOBALS["CLASS_USERS"]->ETTERCAP_INSTALLED){return null;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArpSpoofEnabled");
	if(!is_numeric($enabled)){$enabled=0;}
	if($enabled==0){return;}
	shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.arpspoof.php --start >/dev/null 2>&1 &");
}
// ========================================================================================================================================================
function fetchmail_version(){
	if(isset($GLOBALS["fetchmail_version"])){return $GLOBALS["fetchmail_version"];}
	$fetchmail=$GLOBALS["CLASS_UNIX"]->find_program("fetchmail");
	if(!is_file($fetchmail)){return "0.0.0";}
	exec("$fetchmail -V 2>&1",$results);

	while (list ($md, $line) = each ($results) ){
		if(preg_match("#release\s+([0-9\.]+)#", $line,$re)){
			$GLOBALS["fetchmail_version"]=$re[1];
			return $re[1];
		}
		if(preg_match("#version\s+([0-9\.]+)#", $line,$re)){
			$GLOBALS["fetchmail_version"]=$re[1];
			return $re[1];
		}
	}

	return "0.0.0";
}



function fail2ban_version(){
	if(isset($GLOBALS["fail2ban_version"])){return $GLOBALS["fail2ban_version"];}
	$fail2ban=$GLOBALS["CLASS_UNIX"]->find_program("fail2ban-server");
	if(!is_file($fail2ban)){return "0.0.0";}
	exec("$fail2ban -V 2>&1",$results);

	while (list ($md, $line) = each ($results) ){
		if(preg_match("#Fail2Ban v([0-9\.]+)#", $line,$re)){
			$GLOBALS["fail2ban_version"]=$re[1];
			return $re[1];
		}

	}

	return "0.0.0";
}



function fail2ban(){


	$bin=$GLOBALS["CLASS_UNIX"]->find_program("fail2ban-server");
	if(!is_file($bin)){
		shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.fail2ban.php --install >/dev/null 2>&1");
		return;
	}
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableFail2Ban")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableFail2Ban", 1);
		$enabled=1;
	}else{
		$enabled=@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableFail2Ban");
	}
	
	$l[]="[FAIL2BAN]";
	$l[]="service_name=APP_FAIL2BAN";
	$l[]="master_version=".fail2ban_version();
	$l[]="service_cmd=/etc/init.d/fail2ban";
	$l[]="service_disabled=$enabled";
	$l[]="watchdog_features=1";
	$l[]="family=mailbox";
	if($enabled==0){
		$l[]="running=0";
		$l[]="installed=1\n";
		return implode("\n",$l);
		
	}
	$master_pid=trim(@file_get_contents("/var/run/fail2ban/fail2ban.pid"));
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		postfix_admin_mysql(0, "Fatal Fail2ban not running, start it", null,__FILE__,__LINE__);
		shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/fail2ban start >/dev/null 2>&1 &");
		$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);
		return;
	}
	
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}



function fetchmail(){



	if(!$GLOBALS["CLASS_USERS"]->fetchmail_installed){return null;}
	$EnablePostfixMultiInstance=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfixMultiInstance");
	$EnableFetchmailScheduler=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFetchmailScheduler");
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFetchmail");
	if(!is_numeric($enabled)){$enabled=0;}
	if(!is_numeric($EnableFetchmailScheduler)){$EnableFetchmailScheduler=0;}
	if($EnableFetchmailScheduler==1){return;}
	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	if($DisableMessaging==1){$enabled=0;}


	if($EnablePostfixMultiInstance<>1){
		if(!is_file("/etc/fetchmailrc")){$enabled=0;}
		$master_pid=trim(@file_get_contents("/var/run/fetchmail.pid"));
		if(preg_match("#^([0-9]+)#",$master_pid,$re)){$master_pid=$re[1];}
		$l[]="[FETCHMAIL]";
		$l[]="service_name=APP_FETCHMAIL";
		$l[]="master_version=".fetchmail_version();
		$l[]="service_cmd=/etc/init.d/fetchmail";
		$l[]="service_disabled=$enabled";
		$l[]="watchdog_features=1";
		$l[]="family=mailbox";
		 
		if($enabled==1){
			$fetchmail_count_server=fetchmail_count_server();
			if($GLOBALS["VERBOSE"]){echo "fetchmail_count_server: $fetchmail_count_server\n";}

			if($fetchmail_count_server>0){
				if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
					if(!$GLOBALS["DISABLE_WATCHDOG"]){
						shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.initslapd.php --fetchmail >/dev/null 2>&1");
						shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.fetchmail.php --start >/dev/null 2>&1 &");
					}
					$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);
					return;
				}
			}
		}

	if($enabled==0){return implode("\n",$l);return;}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
	$l[]="running=0";}else{$l[]="running=1";$l[]=GetMemoriesOf($master_pid);}
	$l[]="";
	}else{
		$enabled=1;
	}

	$master_pid=trim(@file_get_contents("/etc/artica-postfix/exec.fetmaillog.php.pid"));
	$l[]="[FETCHMAIL_LOGGER]";
	$l[]="service_name=APP_FETCHMAIL_LOGGER";
	$l[]="master_version=".fetchmail_version();
	$l[]="service_cmd=fetchmail-logger";
	$l[]="service_disabled=$enabled";
	$l[]="watchdog_features=1";

	if($enabled==1){
		if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			$fetchmail_count_server=fetchmail_count_server();
			if($GLOBALS["VERBOSE"]){echo "fetchmail_count_server: $fetchmail_count_server\n";}
			if($fetchmail_count_server>0){
				WATCHDOG("APP_FETCHMAIL_LOGGER","fetchmail-logger");
				$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);
				return;
			}else{
				return implode("\n",$l);return;
			}
		}
	}

	if($enabled==0){return implode("\n",$l);return;}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}

function fetchmail_count_server(){
	$f=explode("\n",@file_get_contents("/etc/fetchmailrc"));
	$count=0;
	while (list ( $i,$line) = each ($f)){if(preg_match("#^poll\s+(.+)#",$line)){$count=$count+1;}}
	return $count;
}

//========================================================================================================================================================







function assp(){
	$users=new settings_inc();

	if(!$GLOBALS["CLASS_USERS"]->ASSP_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo "DEBUG:assp(): Not installed\n";}
		return null;


	}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO('EnableASSP');
	if($enabled==null){$enabled=0;}

	if($GLOBALS["VERBOSE"]){echo "DEBUG: EnableASSP..: $enabled\n";}
	$pid_path="/usr/share/assp/pid";
	if($GLOBALS["VERBOSE"]){echo "DEBUG: pid path....: $pid_path\n";}
	$master_pid=trim(@file_get_contents($pid_path));
	if($GLOBALS["VERBOSE"]){echo "DEBUG: master pid..: $master_pid\n";}
	$l[]="[ASSP]";
	$l[]="service_name=APP_ASSP";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->ASSP_VERSION();
	$l[]="service_cmd=assp";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=postfix";
	if($enabled==0){return implode("\n",$l);return;}
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_ASSP","assp");
		$l[]="running=0";
		$l[]="installed=1\n";
		return implode("\n",$l);
		return;
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================
function framework(){
	if(!is_file("/etc/artica-postfix/framework.conf")){return;}
	$pid_path="/var/run/lighttpd/framework.pid";
	$lighttpd=$GLOBALS["CLASS_UNIX"]->find_program("lighttpd");
	if(!is_file($lighttpd)){return;}
	$master_pid=trim(@file_get_contents($pid_path));
	if($master_pid==null){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("lighttpd -f /etc/artica-postfix/framework.conf");
		if($master_pid<>null){@file_put_contents("/var/run/lighttpd/framework.pid",$master_pid);}
	}
	
	

	

	$l[]="[FRAMEWORK]";
	$l[]="service_name=APP_FRAMEWORK";
	$l[]="master_version=".GetVersionOf("lighttpd");
	$l[]="service_cmd=apache";
	$l[]="service_disabled=1";
	$l[]="watchdog_features=1";
	$l[]="pid_path=$pid_path";
	$l[]="family=system";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.framework.php --start >/dev/null 2>&1 &");
		shell_exec2($cmd);
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	
	if(!$GLOBALS["CLASS_UNIX"]->is_socket("/usr/share/artica-postfix/ressources/web/framework.sock")){
		ToSyslog("Fatal artica-postfix/ressources/web/framework.sock no such socket !!!");
		$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.framework.php --restart >/dev/null 2>&1 &");
		return;
	}
	
	@chmod("/usr/share/artica-postfix/ressources/web/framework.sock",0777);
	
	$time=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/settings/Daemons/HdparmInfos");
	$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.hdparm.php >/dev/null 2>&1 &");
	if($time>60){shell_exec2($cmd);}
	if(!is_file("/etc/init.d/artica-swap")){
		$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.initd-swap.php >/dev/null 2>&1 &");
		shell_exec2($cmd);
	}
	$WifiCardOk=$GLOBALS["CLASS_SOCKETS"]->GET_INFO('WifiCardOk');
	if(!is_numeric($WifiCardOk)){
		if($GLOBALS["CLASS_UNIX"]->file_time_get("exec.wifi.detect.cards.php")>5){
			$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.wifi.detect.cards.php --detect >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
	}
		
	if($WifiCardOk==1){
		$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.wifi.detect.cards.php --iwlist >/dev/null 2>&1 &");
		shell_exec2($cmd);
	}
	
	
	
	return implode("\n",$l);

}
//========================================================================================================================================================

function UpdateUtilityHTTP(){
	$lighttpd=$GLOBALS["CLASS_UNIX"]->find_program("lighttpd");
	if(!is_file($lighttpd)){return null;}
	$pid_path="/var/run/UpdateUtility/lighttpd.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	if($master_pid==null){
		$lighttpd=$GLOBALS["CLASS_UNIX"]->find_program("lighttpd");
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("lighttpd -f /etc/UpdateUtility/lighttpd.conf");
		if($master_pid<>null){@file_put_contents($pid_path,$master_pid);}
	}
	$UpdateUtilityEnableHTTP=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UpdateUtilityEnableHTTP");
	if(!is_numeric($UpdateUtilityEnableHTTP)){$UpdateUtilityEnableHTTP=0;}

	$l[]="[APP_UPDATEUTILITYHTTP]";
	$l[]="service_name=APP_UPDATEUTILITYHTTP";
	$l[]="master_version=".GetVersionOf("lighttpd");
	$l[]="service_cmd=UpdateUtility";
	$l[]="service_disabled=$UpdateUtilityEnableHTTP";
	$l[]="watchdog_features=1";
	$l[]="pid_path=$pid_path";
	$l[]="family=system";
	
	$arrayfile="/usr/share/artica-postfix/ressources/logs/web/UpdateUtilitySize.size.db";
	$time=$GLOBALS["CLASS_UNIX"]->file_time_min($arrayfile);
	if($arrayfile>19){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.keepup2date.php --UpdateUtility-size >/dev/null 2>&1 &");
	}
	$scan=$GLOBALS["CLASS_UNIX"]->DirFiles("UpdateUtility-.*?\.log$");
	if(count($scan)>0){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.keepup2date.php --UpdateUtility-logs >/dev/null 2>&1 &");
	}
	
	
	if($UpdateUtilityEnableHTTP==0){
		return implode("\n",$l);
		return;
	}
	
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_UPDATEUTILITYHTTP","UpdateUtility");
		$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);return;}
		$l[]="running=1";
		$l[]=GetMemoriesOf($master_pid);
		$l[]="";

		return implode("\n",$l);return;

}
//========================================================================================================================================================

function checksyslog(){
	
	$syslogpath=$GLOBALS["CLASS_UNIX"]->LOCATE_SYSLOG_PATH();
	$size=@filesize($syslogpath);
	if($GLOBALS["VERBOSE"]){echo "$syslogpath -> Size:$size\n";}
	if($size<5){
		$GLOBALS["CLASS_UNIX"]->send_email_events("Warning $syslogpath $size Bytes, restarting Syslog", "Suspicious system log size, restarting syslog daemon", "system");
		$GLOBALS["CLASS_UNIX"]->RESTART_SYSLOG(true);
	}
}





function philesight(){
	$pids=array();
	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
	$kill=$GLOBALS["CLASS_UNIX"]->find_program("kill");
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."/".__LINE__."\n";}
	exec("$pgrep -l -f \"ruby.*?philesight\" 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#pgrep#", $line)){continue;}
		if(!preg_match("#^([0-9]+)\s+#",$line,$re)){
			if($GLOBALS["VERBOSE"]){echo "No match.. <$line>\n";}
		}
		if($GLOBALS["VERBOSE"]){echo "match..$line\n";}
		$pids[$re[1]]=true;
		
	}
	
	if(count($pids)==0){return;}
	while (list ($pid, $line) = each ($pids)){
		$time=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["VERBOSE"]){echo "$pid -> {$time}mn\n";}
		if($time>30){
			system_admin_mysql(1,"Warning killing philesight process $pid running since {$time}mn",null,__FILE__,__LINE__);
			unix_system_kill_force($pid);
			
		}
	}
}

function ucarp_version(){
	if(isset($GLOBALS["ucarp_version"])){return $GLOBALS["ucarp_version"];}
	$ucarp=$GLOBALS["CLASS_UNIX"]->find_program("ucarp");
	exec("$ucarp --help 2>&1",$results);
	while (list ($i, $line) = each ($results) ){
		$line=trim($line);
		if($line==null){continue;}
		if(preg_match("#^ucarp\s+([0-9\.]+)\s+#", $line,$re)){
			$GLOBALS["ucarp_version"]=$re[1];
			return $GLOBALS["ucarp_version"];
		}
	}
	
	return 0;
	
}

function ucarp(){
	if($GLOBALS["VERBOSE"]){echo " ********************************** UCARP ******************\n";}
	$ucarp=$GLOBALS["CLASS_UNIX"]->find_program("ucarp");
	if(!is_file($ucarp)){
		if($GLOBALS["VERBOSE"]){echo "No such binary\n";}
		
		return;}
	$enabled=1;
	$HEAD="UCARP_SLAVE";
	if(!is_file("/usr/share/ucarp/ETH_LIST")){
		if($GLOBALS["VERBOSE"]){echo " */usr/share/ucarp/ETH_LIST no such file\n";}
		return;}
	if(is_file("/usr/share/ucarp/Master")){$HEAD="UCARP_MASTER";}
	
	$ETHS=unserialize(@file_get_contents("/usr/share/ucarp/ETH_LIST"));
	while (list ($Interface, $ucarpcmdLINE) = each ($ETHS) ){
		$PID=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("$ucarp.*?--interface=$Interface");
		if($GLOBALS["CLASS_UNIX"]->process_exists($PID)){
			$l[]="[$HEAD]";
			$l[]="service_name=$HEAD";
			$l[]="master_version=".ucarp_version();
			$l[]="service_cmd=/etc/init.d/artica-failover";
			$l[]="service_disabled=1";
			$l[]="watchdog_features=1";
			$l[]="running=1";
			$l[]=GetMemoriesOf($PID);
			$l[]="";
			return implode("\n",$l);			
			
		}
		
	}
	
	$l[]="[$HEAD]";
	$l[]="service_name=$HEAD";
	$l[]="master_version=".ucarp_version();
	$l[]="service_cmd=/etc/init.d/artica-failover";
	$l[]="service_disabled=1";
	$l[]="watchdog_features=1";
	$l[]="running=0";
	$l[]="";	
	return implode("\n",$l);
	
}

function ChecksRoutes(){
	$CacheFileTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$globalStatusIniTime=$GLOBALS["CLASS_UNIX"]->file_time_min($CacheFileTime);
	if($globalStatusIniTime<1){return;}
		
	@unlink($CacheFileTime);
	@file_put_contents($CacheFileTime, time());
	
	$ip=$GLOBALS["CLASS_UNIX"]->find_program("ip");
	exec("$ip route 2>&1",$results);
	$c=0;
	while (list ($num, $ligne) = each ($results) ){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		$c++;
	}

	if($c>0){return;}
	squid_admin_mysql(0, "Rebooting Network", null,__FILE__,__LINE__);
	events_syslog("kernel: [  Artica-Net] Start Network [artica-ifup] (".basename(__FILE__)."/".__LINE__.")" );
	shell_exec2("/etc/init.d/artica-ifup start --script=".basename(__FILE__)."/".__FUNCTION__);
	system_admin_events("No route defined", "I can't see routes in\n".@implode("\n", $results)."\nNetwork will be rebooted",__FUNCTION__,__FILE__,__LINE__,"network",0);
	
}


function pdns_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$pdns=$GLOBALS["CLASS_UNIX"]->find_program("pdns_server");
	exec("$pdns --version 2>&1",$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#Version:\s+([0-9\.]+)#", $line,$re)){
			$GLOBALS[__FUNCTION__]=$re[1];
			return $re[1];
		}
		if(preg_match("#PowerDNS Authoritative Server\s+([0-9\.]+)#", $line,$re)){
			$GLOBALS[__FUNCTION__]=$re[1];
			return $re[1];
			}
		}
	
}
function pdns_recursor_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$pdns_recursor=$GLOBALS["CLASS_UNIX"]->find_program("pdns_recursor");
	exec("$pdns_recursor --version 2>&1",$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#version:\s+([0-9\.]+)#i", @implode("", $results),$re)){$GLOBALS[__FUNCTION__]=$re[1]; return $GLOBALS[__FUNCTION__];}
		if(preg_match("#PowerDNS Recursor\s+([0-9\.]+)#i", @implode("", $results),$re)){$GLOBALS[__FUNCTION__]=$re[1]; return $GLOBALS[__FUNCTION__];}
	}
}


function pdns_server(){
	$verbose=$GLOBALS["VERBOSE"];
	if(!$GLOBALS["CLASS_USERS"]->POWER_DNS_INSTALLED){if($verbose){echo "POWER_DNS_INSTALLED -> FALSE, return\n";}}
	if(!$GLOBALS["CLASS_USERS"]->POWER_DNS_INSTALLED){return null;}
	$enabled=1;
	$DisablePowerDnsManagement=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePowerDnsManagement");
	$EnablePDNS=$GLOBALS["CLASS_USERS"]->EnablePDNS();
	$PDNSRestartIfUpToMB=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSRestartIfUpToMB");
	if($DisablePowerDnsManagement==1){$enabled=0;}
	
	if(!is_numeric($PDNSRestartIfUpToMB)){$PDNSRestartIfUpToMB=700;}

	$pdns_server=$GLOBALS["CLASS_UNIX"]->find_program("pdns_server");


	if($pdns_server==null){
		if($verbose){echo "pdns_server no such binary\n";}
		return null;
	}

	$pid_path="/var/run/pdns/pdns.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($pdns_server);
		if($master_pid<>null){@file_put_contents($pid_path,$master_pid);}
	}

	if($enabled==1){
		if($EnablePDNS==0){$enabled=0;}
	}
	$version=pdns_version();
	$GLOBALS["PDNS_VERSION"]=$version;
	if($verbose){echo "version=$version Enabled=$enabled\n";}

	$l[]="[APP_PDNS]";
	$l[]="service_name=APP_PDNS";
	$l[]="master_version=$version";
	$l[]="service_cmd=/etc/init.d/pdns";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=network";
	 
	if($enabled==0){
		if($verbose){echo "PNS is not enabled running next function -> pdns_instance()\n";}
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){if($DisablePowerDnsManagement==0){shell_exec2("/etc/init.d/pdns stop >/dev/null 2>&1 &");}}
		$instance=pdns_instance();
		return implode("\n",$l).$instance;
	}
	 
	if($verbose){echo "Detected PID: $master_pid ->  check it...\n";}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			if($verbose){echo "-> pid: [$master_pid] failed -> watchdog";}
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/pdns restart >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);
		return;
	}
	if($verbose){echo "Detected PID: $master_pid ->  Seems to be running\n";}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	if($verbose){echo "-> pdns_instance()";}
	$instance=pdns_instance();
	return implode("\n",$l).$instance;return;

}

function pdns_instance(){
	$verbose=$GLOBALS["VERBOSE"];
	$master_pid=null;
	$PDNSRestartIfUpToMB=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSRestartIfUpToMB");
	$pdns_server=$GLOBALS["CLASS_UNIX"]->find_program("pdns_server");
	if($pdns_server==null){if($verbose){echo "pdns_server no such binary\n";}return null;}

	$pidof=$GLOBALS["CLASS_UNIX"]->find_program("pidof");
	$cmd="$pidof $pdns_server-instance 2>&1";
	exec($cmd,$results);
	if($verbose){echo "$cmd return ". count($results)." rows\n";}
	while (list ($num, $ligne) = each ($results) ){
		if(trim($ligne)==null){continue;}
		if(preg_match("#^([0-9]+)#",$ligne,$re)){
			if($GLOBALS["CLASS_UNIX"]->process_exists($re[1])){$master_pid=$re[1];break;}
		}
	}


	if(!is_numeric($master_pid)){
		$results=array();
		$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
		$cmd="$pgrep -l -f $pdns_server-instance 2>&1";
		exec($cmd,$results);
		if($verbose){echo "$cmd return ". count($results)." rows\n";}
		while (list ($num, $ligne) = each ($results) ){
			if(trim($ligne)==null){continue;}
			if(preg_match("#^([0-9]+)\s+.+?pdns#",$ligne,$re)){
				if($GLOBALS["CLASS_UNIX"]->process_exists($re[1])){$master_pid=$re[1];break;}
			}
		}
	}



	if($GLOBALS["VERBOSE"]){echo "$pdns_server-instance -> $master_pid\n";}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){return;}

	$PROCESS_MEMORY=$GLOBALS["CLASS_UNIX"]->PROCESS_MEMORY($master_pid,true);
	$PROCESS_CACHE_MEMORY=$GLOBALS["CLASS_UNIX"]->PROCESS_CACHE_MEMORY($master_pid,true);
	$PDNSRestartIfUpToMBOrg=$PDNSRestartIfUpToMB;
	if($PDNSRestartIfUpToMB>0){
		$PDNSRestartIfUpToMB=$PDNSRestartIfUpToMB*1024;

		if($verbose){echo "PROCESS_MEMORY:{$PROCESS_MEMORY}KB against {$PDNSRestartIfUpToMB}KB\n";}

		if($PROCESS_MEMORY>$PDNSRestartIfUpToMB){
			$PROCESS_MEMORY_EX=round($PROCESS_MEMORY/1024,2);
			$GLOBALS["CLASS_UNIX"]->send_email_events("Watchdog: PowerDNS reach Max memory !!! ({$PROCESS_MEMORY_EX}M/{$PDNSRestartIfUpToMBOrg}M)","PowerDNS service was restarted","system");
			$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
			shell_exec2(trim("$nohup /etc/init.d/pdns restart >/dev/null 2>&1 &"));
		}}
		$l[]="";
		$l[]="";
		$l[]="[APP_PDNS_INSTANCE]";
		$l[]="service_name=APP_PDNS_INSTANCE";
		$l[]="master_version={$GLOBALS["PDNS_VERSION"]}";
		$l[]="service_cmd=pdns";
		$l[]="service_disabled=1";
		$l[]="watchdog_features=1";
		$l[]="family=network";
		$l[]="running=1";
		$l[]="master_memory=$PROCESS_MEMORY";
		$l[]="master_cached_memory=$PROCESS_CACHE_MEMORY";
		$l[]="processes_number=1";
		$l[]="master_pid=$master_pid";
		$l[]="running=1\ninstalled=1";
		$l[]="";
		return implode("\n",$l);
}

//========================================================================================================================================================
function pdns_recursor(){


	if(!$GLOBALS["CLASS_USERS"]->POWER_DNS_INSTALLED){return null;}
	$enabled=1;
	$DisablePowerDnsManagement=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePowerDnsManagement");
	if($DisablePowerDnsManagement==1){$enabled=0;}
	$pdns_server=$GLOBALS["CLASS_UNIX"]->find_program("pdns_recursor");
	if($pdns_server==null){
		if($GLOBALS["VERBOSE"]){echo "pdns_recursor no such binary\n";}
		return null;}
	if(!is_file($pdns_server)){
		if($GLOBALS["VERBOSE"]){echo "pdns_recursor no such binary\n";}
		return null;}
	$EnablePDNS=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePDNS");
	if(!is_numeric($EnablePDNS)){$EnablePDNS=0;}
	

	

	
	if($GLOBALS["VERBOSE"]){echo "pdns_recursor EnablePDNS=$EnablePDNS\n";}

	$pid_path="/var/run/pdns/pdns_recursor.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($pdns_server);
		if($master_pid<>null){@file_put_contents($pid_path,$master_pid);}
	}
	
	
	$EnableChilli=0;
	$chilli=$GLOBALS["CLASS_UNIX"]->find_program("chilli");
	if(is_file($chilli)){
		$EnableChilli=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableChilli");
		if(!is_numeric($EnableChilli)){$EnableChilli=0;}
		if($EnableChilli==1){$enabled=0;}
	}


	if($enabled==1){
		if($EnablePDNS==0){$enabled=0;}
	}
	
	
	

	$l[]="[PDNS_RECURSOR]";
	$l[]="service_name=APP_PDNS_RECURSOR";
	$l[]="master_version=".pdns_recursor_version();
	$l[]="service_cmd=/etc/init.d/pdns-recursor";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=network";
	 
	if($enabled==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if($DisablePowerDnsManagement==0){shell_exec2("/etc/init.d/pdns-recursor stop >/dev/null 2>&1 &");}}
		return implode("\n",$l);
		return;
	}
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			if($verbose){echo "-> pid: [$master_pid] failed -> watchdog";}
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/pdns-recursor restart >/dev/null 2>&1 &");
		}
		
		
		$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);return;}
		$l[]="running=1";
		$l[]=GetMemoriesOf($master_pid);
		$l[]="";

		return implode("\n",$l);return;

}

//========================================================================================================================================================
function cyrus_imap(){
	if(!$GLOBALS["CLASS_USERS"]->cyrus_imapd_installed){return null;}
	$pid_path=$GLOBALS["CLASS_UNIX"]->LOCATE_CYRUS_PIDPATH();
	$master_pid=trim(@file_get_contents($pid_path));
	$enabled=1;
	if($GLOBALS["CLASS_USERS"]->ZARAFA_INSTALLED){$enabled=0;}
	$EnableCyrusImap=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCyrusImap");
	if(!is_numeric($EnableCyrusImap)){$EnableCyrusImap=1;}
	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	if($EnableCyrusImap==0){$enabled=0;}
	if($DisableMessaging==1){$enabled=0;}
	
	$l[]="[CYRUSIMAP]";
	$l[]="service_name=APP_CYRUS";
	$l[]="master_version=".GetVersionOf("cyrus-imap");
	$l[]="service_cmd=/etc/init.d/cyrus-imapd";
	$l[]="service_disabled=1";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=mailbox";
	$l[]="service_disabled=$enabled";
	if($enabled==0){
		return implode("\n",$l);
		return;
	}
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.cyrus-imapd.php --start >/dev/null 2>&1 &");
		shell_exec2($cmd);
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	if(is_file("/var/run/saslauthd/mux")){@chmod("/var/run/saslauthd/mux", 0777);}
	
	
	$timefile="/etc/artica-postfix/croned.1/exec.cyrus.php.DirectorySize.time";
	$filetim=$GLOBALS["CLASS_UNIX"]->file_time_min($timefile);
	if($filetim>240){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.cyrus.php --DirectorySize >/dev/null 2>&1 &");
	}
	
	return implode("\n",$l);return;
	

}
function cyrus_imap_pid(){
	$pidpath=$GLOBALS["CLASS_UNIX"]->CYRUS_PID_PATH();
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pidpath);
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($pid)){
		return $GLOBALS["CLASS_UNIX"]->PIDOF($unix->CYRUS_DAEMON_BIN_PATH());
	}
	return $pid;


}


function mysql_watchdog(){
	$mysqladmin=$GLOBALS["CLASS_UNIX"]->find_program("mysqladmin");
	$zarafa_enabled=0;
	if($GLOBALS["CLASS_USERS"]->ZARAFA_INSTALLED){
		$zarafa_enabled=1;
		$pid_path="/var/run/zarafa-server.pid";
		if(!$GLOBALS["CLASS_UNIX"]->process_exists(@file_get_contents($pid_path))){
			events("Zarafa is installed but did not running...",__FUNCTION__,__LINE__);
			$zarafa_enabled=0;
		}
	}
	
	
	
	$timefile="/etc/artica-postfix/pids/MySQLRepairDBTime.time";
	$timex=$GLOBALS["CLASS_UNIX"]->file_time_min($timefile);
	events("/etc/artica-postfix/pids/MySQLRepairDBTime.time = $timex/240Min",__FUNCTION__,__LINE__);
	if($timex>240){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.mysql.clean.php --corrupted >/dev/null 2>&1 &");
		
	}
	
	$countq=array();
	if(!is_file($mysqladmin)){
		events("mysqladmin no such file",__FUNCTION__,__LINE__);
		return;
	}

	exec("$mysqladmin processlist 2>&1",$results);

	while (list ($num, $ligne) = each ($results) ){
		$QUERY=null;
		if(preg_match("#\|\s+([0-9]+)\s+\|.+?\|.+?\|\s+(.+?)\s+\|.+?\|.+?\|\s+(.+?)\s+|(.+?)\|#",$ligne,$re)){
			$ID=$re[1];
			$DB=$re[2];
			$State=$re[3];
			if(isset($re[4])){$QUERY=$re[4];}
			if($QUERY==null){continue;}
			$notifs[]="$ID db:$DB ($State) query:$QUERY";
			$md5=md5("$DB$State$QUERY");
			if(!isset($countq[$md5])){$countq[$md5]=1;}else{$countq[$md5]=$countq[$md5]+1;}
			events("$ID db:$DB ($State) $QUERY count({$countq[$md5]}) zarafa:$zarafa_enabled",__FUNCTION__,__LINE__);
			if($countq[$md5]>10){
				events("Too many same processes",__FUNCTION__,__LINE__);
				$text="It seems that the mysql server using many threads.
				this is what artica has detected:
				
				".@implode("\n",$notifs)."
				--------------------------------------------------------------------------
				Process dump :
				" .@implode("\n",$results)."\n";
					
				if($zarafa_enabled==0){

				}else{
					$GLOBALS["CLASS_UNIX"]->send_email_events("Mysql many queries (information)",$text,"system");
				}
			}
		}
	}
}




//========================================================================================================================================================


function mysqld_init_fix(){
	$f=explode("\n",@file_get_contents("/etc/init.d/mysql"));
	while (list ($file, $line) = each ($f) ){
		if(preg_match("#RSYSLOGD=#", $line)){
			shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.initd-mysql.php");
		}
	}
	
	
}

function mysql_server_pid(){
	
	$GLOBALS["MYSQL_WATCHOG_EVENTS"]=array();
	
	
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/mysqld/mysqld.pid");
	if($GLOBALS["VERBOSE"]){echo "[VERBOSE]: /var/run/mysqld/mysqld.pid -> \"$pid\"\n";}
	
	$GLOBALS["MYSQL_WATCHOG_EVENTS"][]="/var/run/mysqld/mysqld.pid -> \"$pid\"";
	
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	
	if(is_file("/proc/$pid/cmdline")){
			$GLOBALS["MYSQL_WATCHOG_EVENTS"][]="/proc/$pid/cmdline = ".@file_get_contents("/proc/$pid/cmdline");
	}
			
	
	
	
	$mysqlbin=$GLOBALS["CLASS_UNIX"]->LOCATE_mysqld_bin();
	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
	$lsof=$GLOBALS["CLASS_UNIX"]->find_program("lsof");
	if(is_file($pgrep)){
			if($GLOBALS["VERBOSE"]){echo "[VERBOSE]: $pgrep -l -f \"$mysqlbin.*?--pid-file=/var/run/mysqld/mysqld.pid\" 2>&1\n";}
			
			$GLOBALS["MYSQL_WATCHOG_EVENTS"][]="$pgrep -l -f \"$mysqlbin.*?--pid-file=/var/run/mysqld/mysqld.pid\"";
			exec("$pgrep -l -f \"$mysqlbin.*?--pid-file=/var/run/mysqld/mysqld.pid\" 2>&1",$results);
				
			while (list ($num, $line) = each ($results) ){
				$GLOBALS["MYSQL_WATCHOG_EVENTS"][]="$line";
				if($GLOBALS["VERBOSE"]){echo "[VERBOSE]: $line\n";}
				if(preg_match("#pgrep#",$line)){continue;}
				if(preg_match("#^([0-9]+)\s+#", $line,$re)){
					@file_put_contents("/var/run/mysqld/mysqld.pid", $re[1]);
					$GLOBALS["MYSQL_WATCHOG_EVENTS"][]="Return ".$re[1];
					return $re[1];
				}
			}
		}
		
		
		$results=array();
		exec("$lsof -Pnl +M -i TCP:3306 2>&1",$results);
		$GLOBALS["MYSQL_WATCHOG_EVENTS"][]="$lsof -Pnl +M -i TCP:3306 2>&1";
		while (list ($num, $line) = each ($results) ){
			$GLOBALS["MYSQL_WATCHOG_EVENTS"][]="$line";
			if(preg_match("#mysqld\s+([0-9]+).*?TCP.*?:3306#",$line,$re)){
				@file_put_contents("/var/run/mysqld/mysqld.pid", $re[1]);
				$GLOBALS["MYSQL_WATCHOG_EVENTS"][]="Return ".$re[1];
				return $re[1];
			}
				
	}
	
	
	$GLOBALS["MYSQL_WATCHOG_EVENTS"][]="Return $pid";
	return $pid;
}





function mysql_server(){


	if(!$GLOBALS["CLASS_USERS"]->mysql_installed ){return;}
	$master_pid=mysql_server_pid();
	$mysqlversion=$GLOBALS["CLASS_UNIX"]->MYSQL_VERSION();

	$l[]="[ARTICA_MYSQL]";
	$l[]="service_name=APP_MYSQL_ARTICA";
	$l[]="master_version=$mysqlversion";
	$l[]="service_cmd=/etc/init.d/mysql";
	$l[]="service_disabled=1";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	 
	$status=$GLOBALS["CLASS_UNIX"]->PROCESS_STATUS($master_pid);
	
	
	
	shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.mysql.start.php --watch --framework=".__FILE__." >/dev/null 2>&1 &");
	
	
	$GLOBALS["CLASS_UNIX"]->chown_func("mysql","mysql", "/var/run/mysqld");
	$GLOBALS["CLASS_UNIX"]->chown_func("mysql","mysql", "/var/log/mysql");
	@chmod("/var/run/mysqld", 0777);
	
	 
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		events("mysqld not running....",__FUNCTION__,__LINE__);
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			
			exec("{$GLOBALS["TAILBIN"]} -n 120 /var/lib/mysql/mysqld.err 2>&1",$tailR);
			
			$GLOBALS["CLASS_UNIX"]->send_email_events("MySQL not running, starting MySQL service and repair",
					 @implode("\n", $GLOBALS["MYSQL_WATCHOG_EVENTS"])."\n".@implode("\n", $tailR), "system");
			if($GLOBALS["SQUID_INSTALLED"]){squid_admin_mysql(0, 
			"MySQL not running, starting MySQL service and repair", 
			@implode("\n", $GLOBALS["MYSQL_WATCHOG_EVENTS"])."\n".@implode("\n", $tailR),__FILE__,__LINE__);}
			shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.initd-mysql.php >/dev/null 2>&1");
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/mysql start >/dev/null 2>&1 &");
			shell_exec2("{$GLOBALS["nohup"]} /usr/share/artica-postfix/exec.exec.mysqld.crash.php --crashed --force >/dev/null 2>&1 &");
			
		}
		$l[]="";return implode("\n",$l);
		return;
	}else{
		events("mysqld running -> exec.rrd.php --mysql ....",__FUNCTION__,__LINE__);
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.rrd.php --mysql >/dev/null 2>&1 &");
		events("mysqld running -> mysql_watchdog() ....",__FUNCTION__,__LINE__);
		mysql_watchdog();
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	
	
	$AS_CGROUP=false;
	if($GLOBALS["CLASS_SOCKETS"]->EnableIntelCeleron==1){$AS_CGROUP=true;}
	if($AS_CGROUP){
			events("mysqld cgroup must be enabled ....",__FUNCTION__,__LINE__);
			$cgroups=new status_cgroups();
			$limit=$cgroups->GetLimit($master_pid);
			if($cgroups->GetLimit($master_pid)=="unlimited"){
				$cgroups->set_limit("mysql", $master_pid);
			}
		
	}
	
	
	if(!$GLOBALS["DISABLE_WATCHDOG"]){ mysqld_init_fix(); }
	exec("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.mysql.build.php --multi-status 2>&1",$result1s);
	$l[]="".@implode("\n", $result1s);
	
	
	shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.mysql.start.php --engines --framework=".__FILE__." >/dev/null 2>&1 &");
	
	if(!$GLOBALS["DISABLE_WATCHDOG"]){
		if(!$GLOBALS["CLASS_UNIX"]->is_socket("/var/run/mysqld/mysqld.sock")){
			$xxx=array();
			exec("/usr/bin/stat /var/run/mysqld/mysqld.sock 2>&1",$xxx);
			mysql_admin_mysql(0, "/var/run/mysqld/mysqld.sock no such socket [action=restart]", @implode("\n", $xxx));
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.mysql.start.php --restart --socketfailed --framework=".__FILE__." >/dev/null 2>&1 &");
		}
		
		
		$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
		if($DisableMessaging==0){
			$dir="/var/lib/mysql/postfixlog";
			$unix=new unix();
			$countDefiles=$GLOBALS["CLASS_UNIX"]->COUNT_FILES($dir)/2;
			if($countDefiles>500){
				shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.postfix.miltergrey.stats.php >/dev/null 2>&1 &");
			}
		}
	}
	
	$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.mysqld.crash.php.check_crashed.time");
	events("exec.mysqld.crash.php.check_crashed.time = $CacheSchedules/240Min",__FUNCTION__,__LINE__);
	if($CacheSchedules>240){
		if(!system_is_overloaded()){
			$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.mysqld.crash.php --crashed >/dev/null 2>&1 &");
			if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
			shell_exec2($cmd);
		}
	}
	
	$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.mysql.start.php.test_sockets.time");
	events("exec.mysql.start.php.test_sockets.time = $CacheSchedules/15Min",__FUNCTION__,__LINE__);
	if($CacheSchedules>15){
		$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.mysql.start.php --test-sock >/dev/null 2>&1 &");
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		shell_exec2($cmd);
	}
	
	return implode("\n",$l);return;

}
//========================================================================================================================================================
function mysql_mgmt(){
	if(!$GLOBALS["CLASS_USERS"]->mysql_installed ){return;}
	$program=$GLOBALS["CLASS_UNIX"]->find_program("ndb_mgmd");
	if($program==null){return;}
	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($program);
	$EnableMysqlClusterManager=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMysqlClusterManager");
	if(!is_numeric($EnableMysqlClusterManager)){$EnableMysqlClusterManager=0;}
	if($EnableMysqlClusterManager==0){return;}
	$l[]="[MYSQL_CLUSTER_MGMT]";
	$l[]="service_name=APP_MYSQL_CLUSTER_MGMT";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->MYSQL_VERSION();
	$l[]="service_cmd=mysql-cluster";
	$l[]="service_disabled=$EnableMysqlClusterManager";
	$l[]="pid_path=$pid_path";
	$l[]="family=system";

	if($EnableMysqlClusterManager==0){
		return implode("\n",$l);
		return;
	}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="";return implode("\n",$l);return;}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================
function mysql_replica(){
	if(!$GLOBALS["CLASS_USERS"]->mysql_installed ){return;}
	$program=$GLOBALS["CLASS_UNIX"]->find_program("ndbd");
	if($program==null){return;}
	$EnableMysqlClusterReplicat=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMysqlClusterReplicat");
	if(!is_numeric($EnableMysqlClusterReplicat)){$EnableMysqlClusterReplicat=0;}



	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($program);
	$l[]="[MYSQL_CLUSTER_REPLICA]";
	$l[]="service_name=APP_MYSQL_CLUSTER_REPLICA";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->MYSQL_VERSION();
	$l[]="service_cmd=mysql-cluster";
	$l[]="service_disabled=$EnableMysqlClusterReplicat";
	$l[]="pid_path=$pid_path";
	$l[]="family=system";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="";return implode("\n",$l);return;}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================


function saslauthd(){
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program("saslauthd");
	if(!is_file($binpath)){return;}
	$users=new settings_inc();
	if(!$GLOBALS["CLASS_USERS"]->POSTFIX_INSTALLED){
		if(!$GLOBALS["CLASS_USERS"]->cyrus_imapd_installed){
			return;
		}
	}
	
	
	
	$pid_path=GetVersionOf("saslauthd-pid");
	$master_pid=trim(@file_get_contents($pid_path));
	$l[]="[SASLAUTHD]";
	$l[]="service_name=APP_SASLAUTHD";
	$l[]="master_version=".GetVersionOf("saslauthd");
	$l[]="service_cmd=saslauthd";
	$l[]="service_disabled=1";
	$l[]="pid_path=$pid_path";
	$l[]="family=system";
	$l[]="watchdog_features=1";
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
	}
	 
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.saslauthd.php --build >/dev/null 2>&1");
			shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.saslauthd.php --start >/dev/null 2>&1");
		}
		
		$l[]="";
		return implode("\n",$l);
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	
	if(is_file("/var/run/saslauthd/mux")){@chmod("/var/run/saslauthd/mux", 0777);}

	return implode("\n",$l);return;

}
//========================================================================================================================================================
function syslogger(){
	if(!is_file("/usr/share/artica-postfix/exec.syslog.php")){return;}
	CheckCallable();
	$pid_path="/etc/artica-postfix/exec.syslog.php.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	$service_disabled=1;
	if(is_file("/etc/init.d/syslog")){@chmod("/etc/init.d/syslog",0755);}

	$l[]="[APP_SYSLOGER]";
	$l[]="service_name=APP_SYSLOGER";
	$l[]="master_version=".trim(@file_get_contents(dirname(__FILE__)."/VERSION"));
	$l[]="service_cmd=/etc/init.d/artica-syslog";
	$l[]="service_disabled=1";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	$l[]="pid_path=$pid_path";
	
	
	$size=$GLOBALS["CLASS_UNIX"]->file_size("/usr/share/artica-postfix/ressources/logs/php.log");
	if($size>104857600){@unlink("/usr/share/artica-postfix/ressources/logs/php.log");}
	$SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
	if($SquidPerformance>2){return;}
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		shell_exec2("/etc/init.d/artica-syslog restart");
		$l[]="";return implode("\n",$l);
		events("done",__FUNCTION__,__LINE__);
		return;
	}


	if(!is_file("/var/log/artica-postfix/syslogger.debug")){
		events("restart sysloger",__FUNCTION__,__LINE__);
		$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/etc/init.d/artica-syslog restart");
	}
	
	$unix=new unix();
	$timelog=$unix->file_time_min("/var/log/artica-postfix/syslogger.debug");
	events("/var/log/artica-postfix/syslogger.debug = $timelog minutes TTL",__FUNCTION__,__LINE__);

	$l[]="running=1";
	if($GLOBALS ["VERBOSE"]){echo "GetMemoriesOf -> $master_pid\n";}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	
	
	if($GLOBALS ["VERBOSE"]){echo "/var/log/auth.log ?\n";}
	if(is_file("/var/log/auth.log")){
		$authlog=@filesize("/var/log/auth.log");
		events("Syslog.../var/log/auth.log {$authlog} Bytes",__FUNCTION__,__LINE__);
		if($authlog<5){
			rsyslogd_bug_check();
			events("Restart syslog.../var/log/auth.log < 5 ",__FUNCTION__,__LINE__);
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.syslog-engine.php --restart-syslog --syslogmini >/dev/null 2>&1 &");
		}
		
	}else{
		events("Syslog.../var/log/auth.log no such file ",__FUNCTION__,__LINE__);
	}

	if(!$GLOBALS["DISABLE_WATCHDOG"]){
		$time=file_time_min("/var/log/artica-postfix/syslogger.debug");
		//writelogs("LOG TIME: $time",__FUNCTION__,__FILE__,__LINE__);
		if($time>5){
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/etc/init.d/artica-syslog restart");
		}
	}
	
	return implode("\n",$l);return;

}
function rsyslogd_bug_check(){
	if(!is_file("/etc/init.d/rsyslog")){return;}
	$f=explode("\n",@file_get_contents("/etc/init.d/rsyslog"));
	while (list ($index, $ligne) = each ($f) ){
		if(preg_match("#Provides:\s+mysql#", $ligne)){
			shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.initslapd.php --rsyslogd-init >/dev/null 2>&1");
			return;
		}
	}
}

function auth_tail_pid(){
	$pid=trim(@file_get_contents("/var/run/artica-auth-tail.pid"));
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
	exec("$pgrep -l -f \"exec.auth-tail.php\" 2>&1",$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#pgrep#", $line)){continue;}
		if(!preg_match("#^([0-9]+)\s+#", $line,$re)){continue;}
		return $re[1];

	}
}


function auth_tail(){
	if(!is_file("/usr/share/artica-postfix/exec.auth-tail.php")){return;}
	$EnableSSHD=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSSHD");
	if(strlen($GLOBALS["CLASS_USERS"]->LOCATE_AUTHLOG_PATH)==0){return;}
	if(!is_numeric($EnableSSHD)){$EnableSSHD=1;}
	if($EnableSSHD==0){
		$l[]="[APP_ARTICA_AUTH_TAIL]";
		$l[]="service_name=APP_ARTICA_AUTH_TAIL";
		$l[]="master_version=".trim(@file_get_contents(dirname(__FILE__)."/VERSION"));
		$l[]="service_disabled=0";
		return implode("\n",$l);
	}

	$SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
	if($SquidPerformance>2){return;}
	$pid_path="/etc/artica-postfix/exec.auth-tail.php.pid";
	$master_pid=auth_tail_pid();
	if($GLOBALS["VERBOSE"]){
		echo "$pid_path = $master_pid\n";
	}

	$l[]="[APP_ARTICA_AUTH_TAIL]";
	$l[]="service_name=APP_ARTICA_AUTH_TAIL";
	$l[]="master_version=".trim(@file_get_contents(dirname(__FILE__)."/VERSION"));
	$l[]="service_cmd=/etc/init.d/auth-tail";
	$l[]="service_disabled=1";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=squid";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			squid_admin_mysql(1,"Starting Auth-tail ( not running) ", "Found pid: $master_pid\nNice={{$GLOBALS["NICE"]}}",__FILE__,__LINE__);
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.authtail.php --start >/dev/null 2>&1 &");
		}
		$l[]="running=0";
		return implode("\n",$l);
		
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="running=1";

	return implode("\n",$l);return;

}
//==//========================================================================================================================================================
function amavis(){
	$EnableStopPostfix=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStopPostfix");
	if(!is_numeric($EnableStopPostfix)){$EnableStopPostfix=0;}
	if($EnableStopPostfix==1){return;}
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("amavisd");
	if($bin_path==null){return null;}
	$pid_path="/var/spool/postfix/var/run/amavisd-new/amavisd-new.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$GLOBALS["DEBUG_LOGS"][]="$pid_path = $master_pid";

	
	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	if($DisableMessaging==1){$enabled=0;}

	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAmavisDaemon");
	$l[]="[AMAVISD]";
	$l[]="service_name=APP_AMAVISD_NEW";
	$l[]="master_version=".GetVersionOf("amavis");
	$l[]="service_cmd=/etc/init.d/amavis";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="family=postfix";
	$l[]="master_pid=$master_pid";
	$l[]="watchdog_features=1";
	 
	if($enabled==0){
		return implode("\n",$l);
		return;
	}
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
		exec("$pgrep -l -f \"amavisd\s+\(master\)\" 2>&1",$results);
		while (list ($num, $line) = each ($results) ){
			$GLOBALS["DEBUG_LOGS"][]="$pgrep = $line";
			if(preg_match("#([0-9]+)\s+amavis#", $line,$re)){
				$GLOBALS["DEBUG_LOGS"][]="$pgrep = PID:{$re[1]}";
				if($GLOBALS["CLASS_UNIX"]->process_exists($re[1])){$master_pid=$re[1];break;}
			}
		}
	}
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$GLOBALS["CLASS_UNIX"]->send_email_events("Amavisd-new stopped (watchdog)",
			"Artica will try to start it\n".@implode("\n", $GLOBALS["DEBUG_LOGS"]),"postfix");
		unset($GLOBALS["DEBUG_LOGS"]);
		WATCHDOG("APP_AMAVISD_NEW","amavis");
		$l[]="";return implode("\n",$l);
		return;
	}
	unset($GLOBALS["DEBUG_LOGS"]);
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	AmavisWatchdog();

	return implode("\n",$l);return;

}
//========================================================================================================================================================
function amavis_milter(){
	$EnableStopPostfix=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStopPostfix");
	if(!is_numeric($EnableStopPostfix)){$EnableStopPostfix=0;}
	if($EnableStopPostfix==1){return;}
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("amavisd-milter");
	$EnableAmavisDaemon=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAmavisDaemon");
	$EnableAmavisInMasterCF=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAmavisInMasterCF");
	$EnablePostfixMultiInstance=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfixMultiInstance");

	if($bin_path==null){return null;}
	$pid_path="/var/spool/postfix/var/run/amavisd-milter/amavisd-milter.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	if($EnableAmavisInMasterCF==1){$EnableAmavisDaemon=0;}
	if($EnablePostfixMultiInstance==1){$EnableAmavisDaemon=0;}
	
	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	if($DisableMessaging==1){$EnableAmavisDaemon=0;}

	$l[]="[AMAVISD_MILTER]";
	$l[]="service_name=APP_AMAVISD_MILTER";
	$l[]="master_version=".GetVersionOf("amavis");
	$l[]="service_cmd=amavis-milter";
	$l[]="service_disabled=$EnableAmavisDaemon";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=postfix";
	 
	if($EnableAmavisDaemon==0){
		return implode("\n",$l);
		return;
	}
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_AMAVISD_MILTER","amavis-milter");
		$l[]="";return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================
function lighttpd_version(){
	if(isset($GLOBALS["lighttpd_version"])){return $GLOBALS["lighttpd_version"];}
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("lighttpd");
	exec("$bin_path -V 2>&1",$results);
	while (list ($pid, $line) = each ($results) ){
		if(preg_match("#lighttpd.*?([0-9\.]+)#", $line,$re)){
			$GLOBALS["lighttpd_version"]=$re[1];
			return $GLOBALS["lighttpd_version"];
		}
	}
}
//========================================================================================================================================================
function iptables_version(){
	if(isset($GLOBALS["iptables_version"])){return $GLOBALS["iptables_version"];}
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("iptables");
	exec("$bin_path -V 2>&1",$results);
	while (list ($pid, $line) = each ($results) ){
		if(preg_match("#iptables v([0-9\.]+)#", $line,$re)){
			$GLOBALS["iptables_version"]=$re[1];
			return $GLOBALS["iptables_version"];
		}
	}
}
//========================================================================================================================================================



//========================================================================================================================================================
function apache_version(){
	if(isset($GLOBALS["apache_version"])){return $GLOBALS["apache_version"];}
	$GLOBALS["apache_version"]=$GLOBALS["CLASS_UNIX"]->LOCATE_APACHE_VERSION();
	return $GLOBALS["apache_version"];
}
//========================================================================================================================================================

function lighttpd(){
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("lighttpd");
	$EnableLighttpd=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLighttpd");
	$ApacheArticaEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ApacheArticaEnabled");
	$LighttpdArticaDisabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdArticaDisabled");
	$EnableArticaFrontEndToNGninx=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaFrontEndToNGninx");
	$EnableArticaFrontEndToApache=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaFrontEndToApache");
	if($ApacheArticaEnabled==1){$EnableLighttpd=0;}
	if(!is_numeric($LighttpdArticaDisabled)){$LighttpdArticaDisabled=0;}
	if(!is_numeric($EnableLighttpd)){$EnableLighttpd=1;}
	if(!is_numeric($EnableArticaFrontEndToNGninx)){$EnableArticaFrontEndToNGninx=0;}
	
	

	if($LighttpdArticaDisabled==1){$EnableLighttpd=0;}
	if(!$GLOBALS["CLASS_USERS"]->NGINX_INSTALLED){$EnableArticaFrontEndToNGninx=0;}
	if($EnableArticaFrontEndToNGninx==1){$EnableLighttpd=0;}
	
	
	
	$lighttpd_user=$GLOBALS["CLASS_UNIX"]->LIGHTTPD_USER();
	$master_version=lighttpd_version();
	$pid_path="/var/run/lighttpd/lighttpd.pid";
	$PatternPIDOF="$bin_path -f /etc/lighttpd/lighttpd.conf";
	
	if($EnableArticaFrontEndToApache==1){
		$lighttpd_user=$GLOBALS["CLASS_UNIX"]->APACHE_SRC_ACCOUNT();
		$master_version=apache_version();
		$pid_path="/var/run/artica-apache/apache.pid";
		$PatternPIDOF=$GLOBALS["CLASS_UNIX"]->LOCATE_APACHE_CTL()." -f /etc/artica-postfix/httpd.conf";
	}else{
		if($bin_path==null){return null;}
	}
	
	
	if($GLOBALS["VERBOSE"]){echo "EnableArticaFrontEndToApache:$EnableArticaFrontEndToApache\n";}
	if($GLOBALS["VERBOSE"]){echo "lighttpd-user:$lighttpd_user\n";}
	$array=stat("/usr/share/artica-postfix/logon.php");
	$activeuser=posix_getpwuid($array["uid"]);
	if($GLOBALS["VERBOSE"]){echo "Current:{$activeuser["name"]}\n";}
	if(trim($lighttpd_user)<>null){
		if($activeuser["name"]<>$lighttpd_user){
			$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["CHOWN"]} $lighttpd_user /usr/share/artica-postfix/* >/dev/null 2>&1 &");
			$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["CHOWN"]} -R $lighttpd_user /usr/share/artica-postfix/ressources >/dev/null 2>&1 &");
		}
	}
	
	
	
	$l[]="[LIGHTTPD]";
	$l[]="service_name=APP_LIGHTTPD";
	$l[]="master_version=$master_version";
	$l[]="service_cmd=/etc/init.d/artica-webconsole";
	$l[]="service_disabled=$EnableLighttpd";
	 
	$l[]="watchdog_features=1";
	$l[]="family=system";
	 
	if($EnableLighttpd==0){return implode("\n",$l);}
	 
	$APACHE_SRC_ACCOUNT=$GLOBALS["CLASS_UNIX"]->APACHE_SRC_ACCOUNT();
	$APACHE_SRC_GROUP=$GLOBALS["CLASS_UNIX"]->APACHE_SRC_GROUP();
	$GLOBALS["CLASS_UNIX"]->chown_func($APACHE_SRC_ACCOUNT,$APACHE_SRC_GROUP,"/var/lib/php5/*");
	$master_pid=trim(@file_get_contents($pid_path));
	if($master_pid==null){$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN($PatternPIDOF);}

	$l[]="pid_path=$pid_path";
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.lighttpd.php --start >/dev/null 2>&1 &");
		$l[]="";return implode("\n",$l);return;
	}else{
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.lighttpd.php --error500 >/dev/null 2>&1 &");
		
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================
function boa(){
	if(is_file("/usr/share/artica-postfix/bin/boa")){@unlink("/usr/share/artica-postfix/bin/boa");}
	if(is_file("/usr/share/artica-postfix/bin/boa.24")){@unlink("/usr/share/artica-postfix/bin/boa");}
	return; }
//========================================================================================================================================================
function clamscan(){
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("clamscan");
	if($bin_path==null){return null;}
	$master_pid=1;


	$l[]="[CLAMSCAN]";
	$l[]="service_name=APP_CLAMSCAN";
	$l[]="master_version=".GetVersionOf("clamav");
	$l[]="service_cmd=";
	
	$l[]="family=system";
	$l[]="pid_path=";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$l[]="service_disabled=0";
		$l[]="";
		return implode("\n",$l);
	}
	$l[]="service_disabled=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
function clamd_pid(){
	
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/clamav/clamd.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	$Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("clamd");
	return $GLOBALS["CLASS_UNIX"]->PIDOF($Masterbin);

}
function clamd_version(){
	if(isset( $GLOBALS["clamd_version"])){return  $GLOBALS["clamd_version"];}
	$Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("clamd");
	exec("$Masterbin -V 2>&1",$results);
	while (list ($i, $line) = each ($results) ){
		if(preg_match("#ClamAV\s+([0-9\.]+)\/#i", $line,$re)){
			$GLOBALS["clamd_version"]=$re[1];
			return $GLOBALS["clamd_version"];
		}
	}
}



//========================================================================================================================================================
function clamd(){
	
	$l[]="[CLAMAV]";
	$l[]="service_name=APP_CLAMAV";
	$Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("clamd");
	if(!is_file($Masterbin)){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/ClamAVDaemonInstalled", 0);
		$l[]="running=0\ninstalled=0";
		return @implode("\n", $l);
	
	}
	@file_put_contents("/etc/artica-postfix/settings/Daemons/ClamAVDaemonInstalled", 1);
	
	
	$EnableClamavDaemon=$GLOBALS["CLASS_UNIX"]->EnableClamavDaemon();
	if(!is_numeric($EnableClamavDaemon)){$EnableClamavDaemon=0;}
	$AS_SQUID=false;
	$AS_POSTFIX=false;
	$squidbin=$GLOBALS["CLASS_UNIX"]->LOCATE_SQUID_BIN();
	if(is_file($squidbin)){$AS_SQUID=true;}
	if($GLOBALS["CLASS_USERS"]->POSTFIX_INSTALLED){$AS_POSTFIX=true;}
	
	$master_pid=clamd_pid();
	
	$clamd_version=clamd_version();
	@file_put_contents("/etc/artica-postfix/settings/Daemons/ClamAVDaemonVersion", $clamd_version);

	$l[]="master_version=$clamd_version";
	$l[]="service_cmd=/etc/init.d/clamav-daemon";
	$l[]="service_disabled=$EnableClamavDaemon";
	$l[]="binpath=";
	$l[]="family=system";
	$l[]="watchdog_features=1";
	$l[]="";

	if($EnableClamavDaemon==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/etc/init.d/clamav-daemon stop");
		}
	}

	if($EnableClamavDaemon==0){$l[]="";return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.clamd.php --start >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		$l[]="";
		return implode("\n",$l);
		return;
	}
	
	
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	
	
	$timeFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$timeTimeFile=$GLOBALS["CLASS_UNIX"]->file_time_min($timeFile);
	
	
	
	
	
	
	if(!$GLOBALS["DISABLE_WATCHDOG"]){
		if(!$GLOBALS["CLASS_UNIX"]->is_socket("/var/run/clamav/clamav.sock")){
			if($AS_SQUID){squid_admin_mysql(1, "{reconfigure} clamav /var/run/clamav/clamav.sock socket missing", "",__FILE__,__LINE__);}
			$cmd=trim("{$GLOBALS["nohup"]} /etc/init.d/clamav-daemon restart >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
	
	
		if(!is_file("/var/lib/clamav/main.cvd")){
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.freshclam.php --exec --force >/dev/null 2>&1 &");
			if($AS_SQUID){squid_admin_mysql(1, "Run Clamav Updates main.cvd missing", "$cmd",__FILE__,__LINE__);}
			if($AS_POSTFIX){postfix_admin_mysql(1, "Run Clamav Updates main.cvd missing", "$cmd",__FILE__,__LINE__);}
			shell_exec2($cmd);
		}	
	}
	
	
	if($timeTimeFile>5){
		@unlink($timeFile);
		@file_put_contents($timeFile, time());
		$ClamavRefreshDaemonTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamavRefreshDaemonTime"));
		$ClamavRefreshDaemonMemory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamavRefreshDaemonMemory"));
		if(!is_numeric($ClamavRefreshDaemonMemory)){$ClamavRefreshDaemonMemory=350;}	
		if(!is_numeric($ClamavRefreshDaemonTime)){$ClamavRefreshDaemonTime=60;}
		if($ClamavRefreshDaemonTime>2){
			$ClamavRefreshDaemonTime=$ClamavRefreshDaemonTime-1;
		}
		
		$rss=$GLOBALS["CLASS_UNIX"]->PROCESS_MEMORY($master_pid,false);
		$vm=$GLOBALS["CLASS_UNIX"]->PROCESS_CACHE_MEMORY($master_pid,false);
		$time=time();
		
		
	
		
		
		
		if($ClamavRefreshDaemonTime>10){	
			if($ClamavRefreshDaemonMemory>10){
				if($rss>$ClamavRefreshDaemonMemory){
					if($AS_SQUID){squid_admin_mysql(2, "Reboot ClamAV Antivirus Daemon", "ClamAV Antivirus Daemon memory {$rss}MB exceed {$ClamavRefreshDaemonMemory}MB",__FILE__,__LINE__);}
					if($AS_POSTFIX){postfix_admin_mysql(2, "Reboot ClamAV Antivirus Daemon", "ClamAV Antivirus Daemon memory {$rss}MB exceed {$ClamavRefreshDaemonMemory}MB",__FILE__,__LINE__);}
					$cmd=trim("{$GLOBALS["nohup"]} /etc/init.d/clamav-daemon restart >/dev/null 2>&1 &");
					shell_exec2($cmd);
				}
			}
		
			$time=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($master_pid);
			if($time>$ClamavRefreshDaemonTime){
				if($AS_SQUID){squid_admin_mysql(2, "Reboot ClamAV Antivirus Daemon", "ClamAV Antivirus Daemon TTL {$time} minutes exceed {$ClamavRefreshDaemonTime} minutes",__FILE__,__LINE__);}
				if($AS_POSTFIX){postfix_admin_mysql(2, "Reboot ClamAV Antivirus Daemon", "ClamAV Antivirus Daemon TTL {$time} minutes exceed {$ClamavRefreshDaemonTime} minutes",__FILE__,__LINE__);}
				
				events("Reboot clamd daemon");
				$cmd=trim("{$GLOBALS["nohup"]} /etc/init.d/clamav-daemon restart >/dev/null 2>&1 &");
				shell_exec2($cmd);
			}
			
		}

	}
	return implode("\n",$l);return;

}
//========================================================================================================================================================

function NetAdsWatchdog(){

	$GLOBALS["PHP5"]=LOCATE_PHP5_BIN2();
	$EnableSambaActiveDirectory=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSambaActiveDirectory");
	if(!is_numeric($EnableSambaActiveDirectory)){return;}
	if($EnableSambaActiveDirectory<>1){return;}
	$net=$GLOBALS["CLASS_UNIX"]->LOCATE_NET_BIN_PATH();
	if(!is_file($net)){return;}
	exec("$net ads info 2>&1",$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#^(.+?):(.+)#",trim($line),$re)){
			events($line,__FUNCTION__,__LINE__);
			$array[trim($re[1])]=trim($re[2]);
		}
	}

	$log=@implode("\n",$results);
	unset($results);
	if($array["KDC server"]==null){
		exec("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.samba.php --build 2>&1",$results);

		$text="Watchdog Daemon has detected an unlinked AD connection.:
		$log
		This is the result of re-connect operation:
		".@implode("\n",$results);

		$GLOBALS["CLASS_UNIX"]->send_email_events(
		"Connection to Active Directory Failed (Action reconnect)",
		$text,
		"system"
		
		);
	}

}
//========================================================================================================================================================

function ipsec_init(){
	if(is_file("/etc/init.d/ipsec")){return "/etc/init.d/ipsec";}
}

function ipsec_pid_path(){
	if(is_file("/var/run/charon.pid")){return "/var/run/charon.pid";}
}

function ipsec_binpath(){
	if(is_file("/usr/lib/ipsec/charon")){return "/usr/lib/ipsec/charon";}
}


function iptables(){iptables_tasks();}

function ipsec(){
	if(!$GLOBALS["CLASS_USERS"]->IPSEC_INSTALLED){return;}
	$bin_path=ipsec_binpath();
	if(!is_file($bin_path)){return;}
	$EnableIPSEC=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableIPSEC");
	if(!is_numeric($EnableIPSEC)){$EnableIPSEC=0;}
	$pid_path=ipsec_pid_path();
	$master_pid=trim(@file_get_contents($pid_path));
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin_path);}
	
	$l[]="[IPSEC]";
	$l[]="service_name=APP_IPSEC";
	$l[]="master_version=0.00";
	$l[]="service_cmd=";
	$l[]="service_disabled=$EnableIPSEC";
	$l[]="pid_path=$pid_path";
	$l[]="family=system";
	$l[]="watchdog_features=1";

	$l[]="";

	if($EnableIPSEC==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			$init=ipsec_init();
			if(is_file($init))
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("$init stop");
		}
	}

	if($EnableIPSEC==0){$l[]="";return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		//WATCHDOG("APP_FRESHCLAM","freshclam");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";


	return implode("\n",$l);return;	
}

function c_icap_master_enabled(){
	$cicapbin=$GLOBALS["CLASS_UNIX"]->find_program("c-icap");

	if(!is_file($cicapbin)){return 0;}
	$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
	$SquidDisableAllFilters=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableAllFilters");
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SQUIDEnable",1);}
	$EnableRemoteStatisticsAppliance=$GLOBALS["CLASS_SOCKETS"]->GET_INFO('EnableRemoteStatisticsAppliance');
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	$CicapEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapEnabled");
	$UnlockWebStats=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnlockWebStats");
	if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}
	if($UnlockWebStats==1){$EnableRemoteStatisticsAppliance=0;}

	if(is_file("/etc/artica-postfix/WEBSTATS_APPLIANCE")){
		$EnableStatisticsCICAPService=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStatisticsCICAPService");
		if(!is_numeric($EnableStatisticsCICAPService)){$EnableStatisticsCICAPService=1;}
		$CicapEnabled=1;
		if($EnableStatisticsCICAPService==0){$CicapEnabled=0;}
	}

	if($SQUIDEnable==0){$CicapEnabled=0;}
	if(!is_numeric($CicapEnabled)){$CicapEnabled=0;}
	if(!is_numeric($SquidDisableAllFilters)){$SquidDisableAllFilters=0;}

	if($GLOBALS["CLASS_USERS"]->APP_KHSE_INSTALLED){
		$KavMetascannerEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KavMetascannerEnable");
		if(!is_numeric($KavMetascannerEnable)){$KavMetascannerEnable=0;}
		if($KavMetascannerEnable==1){$CicapEnabled=1;}
	}

	if($SquidDisableAllFilters==1){$CicapEnabled=0;}

	if(!$GLOBALS["CLASS_USERS"]->MEM_HIGER_1G){
		if($GLOBALS["VERBOSE"]){echo "MEM_HIGER_1G !!! FALSE\n";}
		if($CicapEnabled==1){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("CicapEnabled",0);}
		$CicapEnabled=0;
	}
	if($EnableRemoteStatisticsAppliance==1){$CicapEnabled=0;}
	return $CicapEnabled;
}


function freshclam_pid(){
	$unix=new unix();
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/clamav/freshclam.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	$Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("freshclam");
	return $GLOBALS["CLASS_UNIX"]->PIDOF($Masterbin);

}


function freshclam(){
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("freshclam");
	$EnableFreshClam=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFreshClam");
	$EnableClamavDaemon=$GLOBALS["CLASS_UNIX"]->EnableClamavDaemon();
	if($bin_path==null){return null;}
	$pid_path=GetVersionOf("freshclam-pid");
	$master_pid=freshclam_pid();
	
	
	if(!is_numeric($EnableFreshClam)){$EnableFreshClam=0;}
	
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("$bin_path.*?--on-update-execute=");
	}

	

	$l[]="[FRESHCLAM]";
	$l[]="service_name=APP_FRESHCLAM";
	$l[]="master_version=".clamd_version();
	$l[]="service_cmd=/etc/init.d/clamav-freshclam";
	$l[]="service_disabled=$EnableFreshClam";
	$l[]="pid_path=$pid_path";
	$l[]="family=system";
	$l[]="watchdog_features=1";
	$l[]="";

	
	if($EnableFreshClam==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			if(!$GLOBALS["DISABLE_WATCHDOG"]){
				if($GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($master_pid)>120){
					squid_admin_mysql(1,"Stopping Clamav Daemon Updater (not enabled)",null,__FILE__,__LINE__);
					$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.freshclam.php --stop >/dev/null 2>&1 &");
					shell_exec2($cmd);
				}
			}
		}
	}

	

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			if(!$GLOBALS["DISABLE_WATCHDOG"]){
				squid_admin_mysql(0,"Clamav Daemon Updater stopped [action=start]",null,__FILE__,__LINE__);
				$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.freshclam.php --start >/dev/null 2>&1 &");
				shell_exec2($cmd);
			}
		}
		$l[]="";
		return implode("\n",$l);
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";


	return implode("\n",$l);return;

}
//========================================================================================================================================================
function retranslator_httpd(){




	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("lighttpd");
	$RetranslatorHttpdEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RetranslatorHttpdEnabled");
	if($bin_path==null){return null;}
	$pid_path="/var/run/lighttpd/lighttpd-retranslator.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	if($RetranslatorHttpdEnabled==null){$RetranslatorHttpdEnabled=0;}
	if($RetranslatorHttpdEnabled==0){return ;}

	$l[]="[KRETRANSLATOR_HTTPD]";
	$l[]="service_name=APP_KRETRANSLATOR_HTTPD";
	$l[]="master_version=".GetVersionOf("lighttpd-version");
	$l[]="service_cmd=retranslator";
	$l[]="service_disabled=$RetranslatorHttpdEnabled";
	$l[]="family=system";
	$l[]="pid_path=$pid_path";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="";return implode("\n",$l);return;}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================

function tomcat(){
	if(!$GLOBALS["CLASS_USERS"]->TOMCAT_INSTALLED){return;}

	$TomcatEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("TomcatEnable");
	if(!is_numeric($TomcatEnable)){$TomcatEnable=1;}
	if($GLOBALS["CLASS_USERS"]->OPENEMM_INSTALLED){
		$OpenEMMEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenEMMEnable");
		if(!is_numeric($OpenEMMEnable)){$OpenEMMEnable=1;}
		if($OpenEMMEnable==1){$TomcatEnable=0;}
	}

	$pid_path="/opt/openemm/tomcat/temp/tomcat.pid";
	$master_pid=trim(@file_get_contents($pid_path));


	$l[]="[APP_TOMCAT]";
	$l[]="service_name=APP_TOMCAT";
	$l[]="master_version=".$GLOBALS["CLASS_USERS"]->TOMCAT_VERSION;
	$l[]="service_cmd=spamd";
	$l[]="service_disabled=$TomcatEnable";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=web";
	 
	if($TomcatEnable==0){$l[]="";return implode("\n",$l);return;}
	 
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_TOMCAT","tomcat");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}



function openemm(){
	if(!$GLOBALS["CLASS_USERS"]->OPENEMM_INSTALLED){return;}
	$OpenEMMEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenEMMEnable");
	if(!is_numeric($OpenEMMEnable)){$OpenEMMEnable=1;}


	$l[]="[APP_OPENEMM]";
	$l[]="service_name=APP_OPENEMM";
	$l[]="master_version=".$GLOBALS["CLASS_USERS"]->OPENEMM_VERSION;
	$l[]="service_cmd=spamd";
	$l[]="service_disabled=$OpenEMMEnable";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=smtp";
	 
	if($OpenEMMEnable==0){$l[]="";return implode("\n",$l);return;}
	 
	$grep=$GLOBALS["CLASS_UNIX"]->find_program("grep");
	$ps=$GLOBALS["CLASS_UNIX"]->find_program("ps");
	$awk=$GLOBALS["CLASS_UNIX"]->find_program("awk");
	$cmd="$ps -eo pid,command|$grep -E \"\/home\/openemm.*?org\.apache\.catalina\"|$grep -v grep|$awk '{print $1}' 2>&1";
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	exec($cmd,$results);
	$master_pid=trim(@implode("", $results));
	 
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_OPENEMM","openemm");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
function openemm_sendmail(){
	if(!$GLOBALS["CLASS_USERS"]->OPENEMM_INSTALLED){return;}
	$OpenEMMEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenEMMEnable");
	if(!is_numeric($OpenEMMEnable)){$OpenEMMEnable=1;}
	if(!is_file("/home/openemm/sendmail/sbin/sendmail")){return;}


	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/home/openemm/sendmail/run/sendmail.pid");

	$l[]="[APP_OPENEMM_SENDMAIL]";
	$l[]="service_name=APP_OPENEMM_SENDMAIL";
	$l[]="master_version=".$GLOBALS["CLASS_USERS"]->OPENEMM_SENDMAIL_VERSION;
	$l[]="service_cmd=smtp";
	$l[]="service_disabled=$OpenEMMEnable";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=smtp";
	 
	if($OpenEMMEnable==0){$l[]="";return implode("\n",$l);return;}
	 
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_OPENEMM_SENDMAIL","openemm-sendmail");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}



function iscsi_pid_path(){
	if(is_file("/var/run/ietd.pid")){return "/var/run/ietd.pid";}
	if(is_file("/var/run/iscsi_trgt.pid")){return "/var/run/iscsi_trgt.pid";}

}
 
function iscsi(){
	if(!$GLOBALS["CLASS_USERS"]->ISCSI_INSTALLED){return;}
	$EnableISCSI=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableISCSI");
	if($EnableISCSI==null){$EnableISCSI=0;}
	$pid_path=iscsi_pid_path();
	$master_pid=trim(@file_get_contents($pid_path));
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("ietd");

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin_path);
	}



	$l[]="[APP_IETD]";
	$l[]="service_name=APP_IETD";
	$l[]="master_version=".GetVersionOf("ietd");
	$l[]="service_cmd=iscsi";
	$l[]="service_disabled=$EnableISCSI";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	if($EnableISCSI==0){$l[]="";return implode("\n",$l);return;}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_IETD","iscsi");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;


}
function smartd_version(){
	if(isset($GLOBALS["smartd_version"])){return $GLOBALS["smartd_version"];}
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("smartd");
	exec("$bin_path -V 2>&1",$results);
	if(preg_match("#release\s+([0-9\.]+)#",@implode("",$results),$re)){$GLOBALS["smartd_version"]=$re[1];return $re[1];}
}

function smartd(){
	if($GLOBALS["CLASS_USERS"]->VMWARE_HOST){return;}
	if($GLOBALS["CLASS_USERS"]->VIRTUALBOX_HOST){return;}
	if($GLOBALS["CLASS_USERS"]->XEN_HOST){return;}
	if($GLOBALS["CLASS_USERS"]->HYPERV_HOST){return;}
	if(!$GLOBALS["CLASS_USERS"]->SMARTMONTOOLS_INSTALLED){return;}
	
	$EnableSMARTDisk=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSMARTDisk"));
	
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("smartd");
	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin_path);




	$l[]="[SMARTD]";
	$l[]="service_name=APP_SMARTMONTOOLS";
	$l[]="master_version=".smartd_version();
	$l[]="service_cmd=/etc/init.d/smartd";
	$l[]="service_disabled=$EnableSMARTDisk";
	$l[]="pid_path=none";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	if($EnableSMARTDisk==0){$l[]="";return implode("\n",$l);return;}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("/etc/init.d/smartd start >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		
		$l[]="";
		return implode("\n",$l);
		
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}

function postfwd2(){

	if(!$GLOBALS["CLASS_USERS"]->POSTFIX_INSTALLED){return;}
	if(is_file("/etc/artica-postfix/KASPERSKY_WEB_APPLIANCE")){return;}
	exec($GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.postfwd2.php --all-status",$results);
	return @implode("\n",$results);
}
function opendkim_version(){
	if(isset($GLOBALS["OPENDKIM_VERSION"])){return $GLOBALS["OPENDKIM_VERSION"];}

	$opendkim=$GLOBALS["CLASS_UNIX"]->find_program("opendkim");

	exec("$opendkim -V 2>&1",$results);
	while (list ($index, $line) = each ($results) ){
		if(!preg_match("#OpenDKIM Filter v([0-9\.]+)#", $line,$re)){
			if($GLOBALS["VERBOSE"]){echo "VERSION NO MATCH: \"$line\"\n";}
			continue;}
		$GLOBALS["OPENDKIM_VERSION"]=$re[1];
		return $GLOBALS["OPENDKIM_VERSION"];

	}

}


function watchdog_yorel(){
	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
	$kill=$GLOBALS["CLASS_UNIX"]->find_program("kill");
	if(!is_file($pgrep)){
		if($GLOBALS["VERBOSE"]){echo "pgrep, no such file\n";}
		return;
	}

	$cmd="$pgrep -f \"/usr/bin/perl /usr/share/artica-postfix/bin/install/rrd/yorel-create\" 2>&1";
	exec($cmd,$results_yorel);
	if($GLOBALS["VERBOSE"]){echo "$cmd ". count($results_yorel)." processes\n";}
	while (list ($num, $ligne) = each ($results_yorel) ){
		if(!preg_match("#^([0-9]+)#",$ligne,$re)){
			if($GLOBALS["VERBOSE"]){echo "$ligne no match #^([0-9]+)\s+#\n";}
			continue;
		}
		$time=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($re[1]);
		if($GLOBALS["VERBOSE"]){echo "PID:{$re[1]} -> $time minutes TTL\n";}
		if($time>3){
			events("Killing process {$re[1]}: $time minutes TTL",__FUNCTION__,__FILE__);
			$GLOBALS["CLASS_UNIX"]->KILL_PROCESS($re[1],9);
			
			if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
			exec($cmd,$kill_results);
				
		}
		if(isset($kill_results)){
			if(count($kill_results)>0){
				if($GLOBALS["VERBOSE"]){
					while (list ($num, $ligne) = each ($kill_results) ){
						echo $ligne."\n";
					}
				}
			}
		}

	}


	$cmd="$pgrep -l -f rrd/yorel-upd 2>&1";
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	$max=0;
	exec("$cmd",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#^([0-9]+)\s+#",$ligne,$re)){
			if($GLOBALS["VERBOSE"]){echo "PID:{$re[1]}\n";}
			if($GLOBALS["CLASS_UNIX"]->process_exists($re[1])){
				$time=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($re[1]);
				if($GLOBALS["VERBOSE"]){echo "PID:{$re[1]} -> $time minutes TTL\n";}

				if($time>3){
					events("Killing process {$re[1]}: $time minutes TTL",__FUNCTION__,__FILE__);
					shell_exec2("/bin/rm -rf /opt/artica/var/rrd/yorel/*");
					shell_exec2("/bin/rm -rf /opt/artica/share/www/system/rrd/*");
					$GLOBALS["CLASS_UNIX"]->KILL_PROCESS($re[1],9);
					
					continue;
				}else{
					if($GLOBALS["VERBOSE"]){echo "PID:{$re[1]} -> $time minutes TTL -> results=keep\n";}
				}


				if($max>1){
					events("No more than one process allowed",__FUNCTION__,__FILE__);
					$GLOBALS["CLASS_UNIX"]->KILL_PROCESS($re[1],9);
					
					shell_exec2("/bin/rm -rf /opt/artica/var/rrd/yorel/*");
					shell_exec2("/bin/rm -rf /opt/artica/share/www/system/rrd/*");
					continue;
				}
				$max++;
				events("Found process {$re[1]}: $time minutes TTL Process number $max",__FUNCTION__,__FILE__);

			}
		}else{
			if($GLOBALS["VERBOSE"]){echo "$ligne no match\n";}
		}
	}
}
//========================================================================================================================================================

function milter_dkim(){




	if(!$GLOBALS["CLASS_USERS"]->MILTER_DKIM_INSTALLED){return;}
	$EnableDKFilter=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDkimMilter");
	if(!is_numeric($EnableDKFilter)){$EnableDKFilter=0;}
	$DisconnectDKFilter=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisconnectDKFilter");
	if(!is_numeric($DisconnectDKFilter)){$DisconnectDKFilter=0;}


	$pid_path="/var/run/dkim-milter/dkim-milter.pid";
	$master_pid=trim(@file_get_contents($pid_path));


	$l[]="[APP_MILTER_DKIM]";
	$l[]="service_name=APP_MILTER_DKIM";
	$l[]="master_version=".GetVersionOf("milterdkim");
	$l[]="service_cmd=dkim-milter";
	$l[]="service_disabled=$EnableDKFilter";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=postfix";
	 
	if($EnableDKFilter==0){$l[]="";return implode("\n",$l);return;}
	 
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$binpath=$GLOBALS["CLASS_UNIX"]->find_program("dkim-filter");
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
	}
		
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){	
		if($DisconnectDKFilter==0){WATCHDOG("APP_MILTER_DKIM","dkim-milter");}
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================

function dropbox(){
	if(!$GLOBALS["CLASS_USERS"]->DROPBOX_INSTALLED){
		$l[]="";
		$l[]="[APP_DROPBOX]";
		$l[]="service_name=APP_DROPBOX";
		$l[]="installed=0";
		$l[]="service_disabled=0";
		$l[]="";
		return @implode("\n", $l);
	}
	$EnableDropBox=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDropBox");
	if($EnableDropBox==null){$EnableDropBox=0;}


	$pid_path="/root/.dropbox/dropbox.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="";
	$l[]="[APP_DROPBOX]";
	$l[]="service_name=APP_DROPBOX";
	$l[]="master_version=".GetVersionOf("dropbox");
	$l[]="service_cmd=dropbox";
	$l[]="service_disabled=$EnableDropBox";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=storage";
	 
	if($EnableDropBox==0){$l[]="";return implode("\n",$l);return;}
	 
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_DROPBOX","dropbox");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================
function arkeiad_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$line=exec("/opt/arkeia/bin/arktrans --version 2>&1");
	if(preg_match("#Backup\s+([0-9\.]+)#", $line,$re)){
		$GLOBALS[__FUNCTION__]=$re[1];
		return $GLOBALS[__FUNCTION__];
	}
}
//========================================================================================================================================================

//========================================================================================================================================================

function arkeiad(){
	if(!$GLOBALS["CLASS_USERS"]->APP_ARKEIA_INSTALLED){return;}
	$EnableArkeia=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArkeia");
	if($EnableArkeia==null){$EnableArkeia=0;}


	$pid_path="/opt/arkeia/arkeiad/arkeiad.pid";
	$master_pid=trim(@file_get_contents($pid_path));


	$l[]="[APP_ARKEIAD]";
	$l[]="service_name=APP_ARKEIAD";
	$l[]="master_version=".arkeiad_version();
	$l[]="service_cmd=arkeia";
	$l[]="service_disabled=$EnableArkeia";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=storage";
	 
	if($EnableArkeia==0){$l[]="";return implode("\n",$l);return;}
	 
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_ARKEIAD","arkeia");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================




function lms_version(){
	if(isset($GLOBALS["lms_version"])){return $GLOBALS["lms_version"];}
	$results=file("/var/opt/kaspersky/apps/1463");
	while (list ($index, $line) = each ($results)){
		if(preg_match("#version=.*?([0-9\.]+)#", $line,$re)){$GLOBALS["lms_version"]=$re[1];return $GLOBALS["lms_version"];}
	}
	
}

function klms_milter(){
	
	
	
	if(!$GLOBALS["CLASS_USERS"]->KLMS_INSTALLED){if($GLOBALS["VERBOSE"]){echo " Not installed...\n";}return;}
	
	$pid_path="/var/run/klms/klms-milter.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	$EnableKlms=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKlms");
	if(!is_numeric($EnableKlms)){$EnableKlms=1;}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$master_pid=trim($GLOBALS["CLASS_UNIX"]->PIDOF("/opt/kaspersky/klms/libexec/klms-milter"));
	}
	
	$l[]="[APP_KLMS_MILTER]";
	$l[]="service_name=APP_KLMS_MILTER";
	$l[]="master_version=".lms_version();
	$l[]="service_cmd=klms";
	$l[]="service_disabled=$EnableKlms";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=smtp";
	$mem=$GLOBALS["CLASS_UNIX"]->TOTAL_MEMORY_MB();
	if($mem<1500){
		$EnableKlms=0;
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableKlms","0");
	}
	 
	if($EnableKlms==0){
		$l[]="";return implode("\n",$l);
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/klms stop >/dev/null 2>&1 &");
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.klms.php --watchdog >/dev/null 2>&1 &");
		}
		return;
	}	
	
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$l[]="";
		return implode("\n",$l);
		
	}
		
	
	$l[]=$GLOBALS["CLASS_UNIX"]->GetMemoriesOf($master_pid,true);
	$l[]="";

	return implode("\n",$l);return;	
	
}

function klms_status(){
	if(!$GLOBALS["CLASS_USERS"]->KLMS_INSTALLED){if($GLOBALS["VERBOSE"]){echo " Not installed...\n";}return;}
	
	$pid_path="/var/run/klms/klms.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	$EnableKlms=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKlms");
	if(!is_numeric($EnableKlms)){$EnableKlms=1;}
	
	
	$l[]="[APP_KLMSS]";
	$l[]="service_name=APP_KLMSS";
	$l[]="master_version=".lms_version();
	$l[]="service_cmd=klms";
	$l[]="service_disabled=$EnableKlms";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=smtp";
	 
	$mem=$GLOBALS["CLASS_UNIX"]->TOTAL_MEMORY_MB();
	if($mem<1500){
		$EnableKlms=0;
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableKlms","0");
	}
	
	if($EnableKlms==0){
		$l[]="";
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/klms stop >/dev/null 2>&1 &");
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.klms.php --watchdog >/dev/null 2>&1 &");
		}
		return implode("\n",$l);
	}
	
	
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_KLMSS","klms");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;
}
function klmsdb_status(){
	if(!$GLOBALS["CLASS_USERS"]->KLMS_INSTALLED){return;}
	
	$pid_path="/var/opt/kaspersky/klms/postgresql/postmaster.pid";
	$f=file($pid_path);
	$EnableKlms=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKlms");
	if(!is_numeric($EnableKlms)){$EnableKlms=1;}
	$master_pid=$f[0];
	
	$l[]="[APP_KLMSDB]";
	$l[]="service_name=APP_KLMSDB";
	$l[]="master_version=".lms_version();
	$l[]="service_cmd=klmsdb";
	$l[]="service_disabled=$EnableKlms";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=smtp";
	 
	$mem=$GLOBALS["CLASS_UNIX"]->TOTAL_MEMORY_MB();
	if($mem<1500){
		$EnableKlms=0;
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableKlms","0");
	}
	
	if($EnableKlms==0){
		$l[]="";
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/klmsdb stop >/dev/null 2>&1 &");
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.klms.php --watchdog >/dev/null 2>&1 &");
		}
					
		return implode("\n",$l);
	}
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_KLMSDB","klmsdb");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;
}


function vde_all(){
	$files=$GLOBALS["CLASS_UNIX"]->DirFiles("/etc/init.d","virtualswitch");
	while (list ($num, $ligne) = each ($files) ){
		if(preg_match("#virtualswitch-(.+)#", $ligne,$re)){
			$f[]=vde_uniq($re[1])."\n";
		}
	}
	
	return @implode("\n", $f);
}

function vde_uniq($switch){
	$unix=new unix();
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("vde_switch");
	if(!is_file($bin)){return;}
	
	$switch_init="/etc/init.d/virtualswitch-$switch";
	if(!is_file($switch_init)){return;}
	$switch_pid="/var/run/switch-$switch.pid";
	$VirtualSwitchEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("VirtualSwitchEnabled{$switch}");
	if(!is_numeric($VirtualSwitchEnabled)){$VirtualSwitchEnabled=1;}
	
	$master_pid=@file_get_contents($switch_pid);
	
	
	$l[]="[VDE_$switch]";
	$l[]="service_name=virtual_switch";
	$l[]="service_cmd=$switch_init";
	$l[]="master_version=".vde_version();
	$l[]="service_disabled=$VirtualSwitchEnabled";
	$l[]="pid_path=$switch_pid";
	$l[]="watchdog_features=1";
	$l[]="family=network";
	if($VirtualSwitchEnabled==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.vde.php --stop-switch $switch >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		return implode("\n",$l).vde_hook_uniq($switch);
	}
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.vde.php --start-switch $switch >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		$l[]="";
		return implode("\n",$l).vde_hook_uniq($switch);
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	
	return implode("\n",$l).vde_hook_uniq($switch);
	
	
}
function vde_hook_uniq($switch){
	$unix=new unix();
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("vde_switch");
	if(!is_file($bin)){return;}

	$switch_init="/etc/init.d/virtualhook-$switch";
	if(!is_file($switch_init)){return;}
	$switch_pid="/var/run/switch{$switch}p.pid";
	$VirtualSwitchEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("VirtualSwitchEnabled{$switch}");
	if(!is_numeric($VirtualSwitchEnabled)){$VirtualSwitchEnabled=1;}

	$master_pid=@file_get_contents($switch_pid);


	$l[]="[VDHOOK_$switch]";
	$l[]="service_name=virtual_hook";
	$l[]="service_cmd=$switch_init";
	$l[]="master_version=".vde_version();
	$l[]="service_disabled=$VirtualSwitchEnabled";
	$l[]="pid_path=$switch_pid";
	$l[]="watchdog_features=1";
	$l[]="family=network";
	if($VirtualSwitchEnabled==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.vde.php --pcapplug-stop $switch >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		return implode("\n",$l);
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.vde.php --pcapplug-start $switch >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;


}
function vde_version(){
	if(isset($GLOBALS["vde_version"])){return $GLOBALS["vde_version"];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("vde_switch");
	if(!is_file($bin)){if($GLOBALS['VERBOSE']){echo "vde_switch -> no such file\n";}return;}
	exec("$bin -v 2>&1",$array);
	while (list ($pid, $line) = each ($array) ){
		if(preg_match("#VDE\s+([0-9\.]+)#i", $line,$re)){$GLOBALS["vde_version"]=$re[1];return $GLOBALS["vde_version"];}
		if($GLOBALS['VERBOSE']){echo "vde_switch(),  \"$line\", not found \n";}
	}
}
//========================================================================================================================================================






function php_fpm_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$bin=$GLOBALS["CLASS_UNIX"]->APACHE_LOCATE_PHP_FPM();
	if(!is_file($bin)){
		if($GLOBALS['VERBOSE']){echo "APACHE_LOCATE_PHP_FPM -> no such file\n";}
		return;
	}
	$array=array();
	if(is_file("/etc/artica-postfix/phpfpm_version.db")){
		$array=unserialize(@file_get_contents("/etc/artica-postfix/phpfpm_version.db"));
	}	
	$binMD5=md5_file($bin);
	if($binMD5<>$array["binMD5"]){
		$array["binMD5"]=$binMD5;
		exec("$bin -v 2>&1",$array);
		while (list ($pid, $line) = each ($array) ){
			if(preg_match("#^PHP\s+([0-9\.\-]+)#i", $line,$re)){
				$GLOBALS[__FUNCTION__]=$re[1];
				$array["binversion"]=$re[1];
				syslog_status("php5-FPM: v{$array["binversion"]} - $binMD5", "artica-status");
				@file_put_contents("/etc/artica-postfix/phpfpm_version.db", serialize($array));
				return $re[1];
			}
			if($GLOBALS['VERBOSE']){echo "php_fpm_version(), $line, not found \n";}
		}
	}
	
	$GLOBALS[__FUNCTION__]=$array["binversion"];
	return $GLOBALS[__FUNCTION__];
	
}
function spwanfcgi_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("spawn-fcgi");
	if(!is_file($bin)){
		if($GLOBALS['VERBOSE']){echo "spwanfcgi_version -> no such file\n";}
		return;}
		exec("$bin -h 2>&1",$array);
		while (list ($pid, $line) = each ($array) ){
			if(preg_match("#spawn-fcgi v([0-9\.\-]+)#i", $line,$re)){
					$GLOBALS[__FUNCTION__]=$re[1];
					return $re[1];
			}
			if($GLOBALS['VERBOSE']){echo "spwanfcgi_version(), $line, not found \n";}
		}
}
function FPM_PID(){
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file('/var/run/php5-fpm.pid');
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	$bin=$GLOBALS["CLASS_UNIX"]->APACHE_LOCATE_PHP_FPM();
	return $GLOBALS["CLASS_UNIX"]->PIDOF($bin);
}
//========================================================================================================================================================
function php_fpm(){
	$unix=new unix();
	$bin=$GLOBALS["CLASS_UNIX"]->APACHE_LOCATE_PHP_FPM();
	if(!is_file($bin)){
		if(!is_file("/etc/debian_version")){return;}
		
		$StampFile="/etc/artica-postfix/pids/php_fpm.install.time";
		$TimeFile=$GLOBALS["CLASS_UNIX"]->file_time_min($StampFile);
		if($TimeFile>1440){
			@unlink($StampFile);
			@file_put_contents($StampFile, time());
			syslog_status("php5-FPM: Not installed , installing php5-fpm Time:{$TimeFile}Mn", "artica-status");
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.apt-get.php --phpfpm-daemon >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		return;
	}
	
	$master_pid=FPM_PID();
	$EnablePHPFPM=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePHPFPM");
	$ZarafaApachePHPFPMEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaApachePHPFPMEnable");
	if(!is_numeric($ZarafaApachePHPFPMEnable)){$ZarafaApachePHPFPMEnable=0;}
	
	
	$EnableArticaApachePHPFPM=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaApachePHPFPM");
	if(!is_numeric($EnableArticaApachePHPFPM)){$EnableArticaApachePHPFPM=0;}
	
	$EnablePHPFPMFreeWeb=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePHPFPMFreeWeb");
	if(!is_numeric($EnablePHPFPMFreeWeb)){$EnablePHPFPMFreeWeb=0;}
	
	$EnablePHPFPMFrameWork=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePHPFPMFrameWork");
	if(!is_numeric($EnablePHPFPMFrameWork)){$EnablePHPFPMFrameWork=0;}
	
	
	if($EnableArticaApachePHPFPM==1){$EnablePHPFPM=1;}
	if($ZarafaApachePHPFPMEnable==1){$EnablePHPFPM=1;}
	if($EnablePHPFPMFreeWeb==1){$EnablePHPFPM=1;}
	if($EnablePHPFPMFrameWork==1){$EnablePHPFPM=1;}
	if($ZarafaApachePHPFPMEnable==1){$EnablePHPFPM=1;}
	if($EnableArticaApachePHPFPM==1){$EnablePHPFPM=1;}
	if(!is_numeric($EnablePHPFPM)){$EnablePHPFPM=0;}
	if(is_file("/etc/artica-postfix/WORDPRESS_APPLIANCE")){$EnablePHPFPM=1;$EnablePHPFPMFreeWeb=1;}
	

	$l[]="[APP_PHPFPM]";
	$l[]="service_name=APP_PHPFPM";
	$l[]="master_version=".php_fpm_version();
	$l[]="service_disabled=$EnablePHPFPM";
	$l[]="pid_path=/var/run/php5-fpm.pid";
	$l[]="service_cmd=/etc/init.d/php5-fpm";
	$l[]="watchdog_features=1";
	$l[]="family=network";
	
	if(is_file("/etc/monit/conf.d/phpfpm.monitrc")){
		@unlink("/etc/monit/conf.d/phpfpm.monitrc");
		$GLOBALS["CLASS_UNIX"]->MONIT_RELOAD();
	}

	if($EnablePHPFPM==0){
		$l[]="";return implode("\n",$l);
		return;
	}
	
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin);}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			syslog_status("php5-FPM: Not running starting php5-fpm", "artica-status");
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.php-fpm.php --start >/dev/null 2>&1 &");
			shell_exec2($cmd);
			}
			$l[]="";
			return implode("\n",$l);
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	
	if($EnableArticaApachePHPFPM==1){
		if(!$GLOBALS["CLASS_UNIX"]->is_socket("/var/run/php-fpm.sock")){
			syslog_status("/var/run/php-fpm.sock: no such file, restarting php5-FPM", "artica-status");
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.php-fpm.php --restart >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
	}
	
	if($EnablePHPFPMFreeWeb==1){
		if(!$GLOBALS["CLASS_UNIX"]->is_socket("/var/run/php-fpm-apache2.sock")){
			syslog_status("/var/run/php-fpm-apache2.sock: no such file, restarting php5-FPM", "artica-status");
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.php-fpm.php --restart >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
	}

	$zarafabin=$GLOBALS["CLASS_UNIX"]->find_program("zarafa-server");
	if(is_file($zarafabin)){
		if($ZarafaApachePHPFPMEnable==1){
			if(!$GLOBALS["CLASS_UNIX"]->is_socket("/var/run/php-fpm-zarafa.sock")){
				syslog_status("/var/run/php-fpm-zarafa.sock: no such file, restarting php5-FPM", "artica-status");
				$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.php-fpm.php --restart >/dev/null 2>&1 &");
				shell_exec2($cmd);
			}
		}
	}
	
	
	return implode("\n",$l);return;
}

//========================================================================================================================================================
function syslog_status($text){$file="artica-status";if(!function_exists('syslog')){return null;}openlog($file, LOG_PID | LOG_PERROR, LOG_LOCAL0);syslog(LOG_INFO, $text);closelog();}
//========================================================================================================================================================
function php_fcgi(){
	$unix=new unix();
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("spawn-fcgi");
	if(!is_file($bin)){
		if(!is_file("/etc/debian_version")){return;}
			$cmd="DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::=\"--force-confnew\" --force-yes --yes install spawn-fcgi";
			$cmd=trim("{$GLOBALS["NICE"]} $cmd >/dev/null 2>&1 &");
			shell_exec2($cmd);
			return;
	}

	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/spawn-fcgi.pid");
	$EnableSPAWNFCGI=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableSPAWNFCGI"));
	
	$EnablePHPFPM=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePHPFPM"));
	$EnablePHPFPMFrameWork=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePHPFPMFrameWork");
	$EnableArticaApachePHPFPM=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaApachePHPFPM");
	$EnablePHPFPMFreeWeb=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePHPFPMFreeWeb");
	$EnableFreeWeb=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFreeWeb");
	if(!is_numeric($EnablePHPFPMFrameWork)){$EnablePHPFPMFrameWork=0;}
	if(!is_numeric($EnableArticaApachePHPFPM)){$EnableArticaApachePHPFPM=0;}
	if(!is_numeric($EnablePHPFPMFreeWeb)){$EnablePHPFPMFreeWeb=0;}
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}
	if($EnableFreeWeb==0){$EnablePHPFPMFreeWeb=1;}
	
	if($EnablePHPFPM==1){
		if($EnablePHPFPMFrameWork==1){
			if($EnableArticaApachePHPFPM==1){
				if($EnablePHPFPMFreeWeb==1){
					$EnableSPAWNFCGI=0;
				}
			}
		}
	}

	if($EnableSPAWNFCGI==0){
		if($GLOBALS["CLASS_UNIX"]->is_socket("/var/run/php-fcgi.sock")){
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.php5-fcgi.php --stop >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
	}

		$l[]="[APP_SPWANFCGI]";
		$l[]="service_name=APP_SPWANFCGI";
		$l[]="master_version=".spwanfcgi_version();
		$l[]="service_disabled=$EnableSPAWNFCGI";
		$l[]="pid_path=/var/run/spawn-fcgi.pid";
		$l[]="watchdog_features=1";
		$l[]="family=network";

		if($EnableSPAWNFCGI==0){$l[]="";return implode("\n",$l);return;}
		if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin);}
		if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){if(!$GLOBALS["DISABLE_WATCHDOG"]){$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.php5-fcgi.php --start >/dev/null 2>&1 &");shell_exec2($cmd);}$l[]="";return implode("\n",$l);}
		$l[]=GetMemoriesOf($master_pid);
		$l[]="";

		if(!$GLOBALS["CLASS_UNIX"]->is_socket("/var/run/php-fcgi.sock")){
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.php5-fcgi.php --restart >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}

		return implode("\n",$l);return;
}

//========================================================================================================================================================





function arkwsd(){
	if(!$GLOBALS["CLASS_USERS"]->APP_ARKEIA_INSTALLED){return;}
	$EnableArkeia=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArkeia");
	if($EnableArkeia==null){$EnableArkeia=0;}


	$pid_path="/opt/arkeia/arkeiad/arkwsd.pid";
	$master_pid=trim(@file_get_contents($pid_path));


	$l[]="[APP_ARKWSD]";
	$l[]="service_name=APP_ARKWSD";
	$l[]="master_version=".arkeiad_version();
	$l[]="service_cmd=arkeia";
	$l[]="service_disabled=$EnableArkeia";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=storage";
	 
	if($EnableArkeia==0){$l[]="";return implode("\n",$l);return;}
	 
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_ARKWSD","arkeia");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}

function iptables_tasks(){
	
	$dirname=dirname(__FILE__);
	$EnableSpamhausDROPList=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSpamhausDROPList");
	if(!is_numeric($EnableSpamhausDROPList)){$EnableSpamhausDROPList=0;}
	
	
	$iptables=$GLOBALS["CLASS_UNIX"]->find_program("iptables");
	$cachefile="/etc/artica-postfix/IPTABLES_INPUT";
	$unix=new unix();
	$TimeFile=$GLOBALS["CLASS_UNIX"]->file_time_min($cachefile);
	if($TimeFile>30){
		@unlink($cachefile);
		shell_exec2("{$GLOBALS["nohup"]} $iptables -L --line-numbers -n >$cachefile 2>&1 &");
	}
	
	if($EnableSpamhausDROPList==1){
		$TimePath="/etc/artica-postfix/pids/exec.spamhausdrop.php.update.time";
		$TimeFile=$GLOBALS["CLASS_UNIX"]->file_time_min($TimePath);
		if($TimeFile>30){
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $dirname/exec.spamhausdrop.php 2>&1 &");
		}
	}
	

	
}


//========================================================================================================================================================
function virtualbox_webserv(){
	if(!$GLOBALS["CLASS_USERS"]->VIRTUALBOX_INSTALLED){return;}
	$EnableVirtualBox=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableVirtualBox");
	if($EnableVirtualBox==null){$EnableVirtualBox=1;}


	$pid_path="/var/run/virtualbox/vboxwebsrv.pid";
	$master_pid=trim(@file_get_contents($pid_path));


	$l[]="[APP_VIRTUALBOX_WEBSERVICE]";
	$l[]="service_name=APP_VIRTUALBOX_WEBSERVICE";
	$l[]="master_version=".GetVersionOf("virtualbox");
	$l[]="service_cmd=virtualbox-web";
	$l[]="service_disabled=$EnableVirtualBox";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=virtual";
	 
	if($EnableVirtualBox==0){$l[]="";return implode("\n",$l);return;}
	 
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_VIRTUALBOX_WEBSERVICE","virtualbox-web");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================
function crossroads(){
	if(!$GLOBALS["CLASS_USERS"]->crossroads_installed){return;}
	$EnableCrossRoads=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCrossRoads");
	if($EnableCrossRoads==null){$EnableCrossRoads=0;}
	
	shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.loadbalance.php --status --watchdog >/dev/null 2>&1 &");
	
	$MAIN=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CrossRoadsParams")));
	if(!is_array($MAIN["BACKENDS"])){$EnableCrossRoads=0;}
	$master_pid=trim($GLOBALS["CLASS_UNIX"]->PIDOF($GLOBALS["CLASS_UNIX"]->find_program("xr")));


	$l[]="[APP_CROSSROADS]";
	$l[]="service_name=APP_CROSSROADS";
	$l[]="master_version=".GetVersionOf("crossroads");
	$l[]="service_cmd=crossroads";
	$l[]="service_disabled=$EnableCrossRoads";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=network";
	 
	if($EnableCrossRoads==0){$l[]="";return implode("\n",$l);return;}
	 
	 
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_CROSSROADS","crossroads");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================

function cron_pid(){
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/crond.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
		
	$cron=$GLOBALS["CLASS_UNIX"]->find_program("cron");
	return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN($cron);
}

function cron(){
	
	$master_pid=cron_pid();


	$l[]="[APP_CRON]";
	$l[]="service_name=APP_CRON";
	$l[]="master_version=1.0";
	$l[]="service_cmd=/etc/init.d/cron";
	$l[]="service_disabled=1";
	
	$l[]="watchdog_features=1";
	$l[]="family=system";

	


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			ToSyslog("Cron is not started -> run it");
			shell_exec2("/etc/init.d/cron start");
		}
		$l[]="";
		return implode("\n",$l);
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================



function pptpd(){
	if(!$GLOBALS["CLASS_USERS"]->PPTPD_INSTALLED){return;}
	$EnablePPTPDVPN=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePPTPDVPN");
	if($EnablePPTPDVPN==null){$EnablePPTPDVPN=0;}
	$pid_path="/var/run/pptpd.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="[APP_PPTPD]";
	$l[]="service_name=APP_PPTPD";
	$l[]="master_version=".GetVersionOf("pptpd");
	$l[]="service_cmd=pptpd";
	$l[]="service_disabled=$EnablePPTPDVPN";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=network";
	if($EnablePPTPDVPN==0){$l[]="";return implode("\n",$l);return;}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_PPTPD","pptpd");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}
//========================================================================================================================================================
function apt_mirror(){
	if(!$GLOBALS["CLASS_USERS"]->APT_MIRROR_INSTALLED){return;}
	$EnableAptMirror=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAptMirror");
	if($EnableAptMirror==null){$EnableAptMirror=0;}
	$master_pid=trim($GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN($GLOBALS["CLASS_UNIX"]->find_program("apt-mirror")));

	$l[]="[APP_APT_MIRROR]";
	$l[]="service_name=APP_APT_MIRROR";
	$l[]="master_version=".GetVersionOf("apt-mirror");
	$l[]="service_cmd=apt-mirror";
	$l[]="service_disabled=$EnableAptMirror";
	$l[]="pid_path=";
	$l[]="watchdog_features=0";
	$l[]="family=network";
	if($EnableAptMirror==0){$l[]="";return implode("\n",$l);return;}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}
//========================================================================================================================================================

function ddclient(){
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program("ddclient");
	if(!is_file($binpath)){return;}
	$EnableDDClient=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDDClient");
	if($EnableDDClient==null){$EnableDDClient=0;}
	$pid_path="/var/run/ddclient.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="[APP_DDCLIENT]";
	$l[]="service_name=APP_DDCLIENT";
	$l[]="master_version=".GetVersionOf("ddclient");
	$l[]="service_cmd=apt-mirror";
	$l[]="service_disabled=$EnableDDClient";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=network";
	if($EnableDDClient==0){$l[]="";return implode("\n",$l);return;}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_DDCLIENT","ddclient");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}
//========================================================================================================================================================

function APACHE_PID(){
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	$APACHE_PID_PATH=$GLOBALS["CLASS_UNIX"]->APACHE_PID_PATH();
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($APACHE_PID_PATH);
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}


	$LOCATE_APACHE_CONF_PATH=$GLOBALS["CLASS_UNIX"]->LOCATE_APACHE_CONF_PATH();
	if($GLOBALS["VERBOSE"]){echo "LOCATE_APACHE_CONF_PATH=$LOCATE_APACHE_CONF_PATH\n";}
	$apache=$GLOBALS["CLASS_UNIX"]->APACHE_BIN_PATH();
	$pattern="$apache.*?-f $LOCATE_APACHE_CONF_PATH";
	if($GLOBALS["VERBOSE"]){echo "pattern=`$pattern`\n";}
	
	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
	exec("$pgrep -l -f \"$pattern\" 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#pgrep#", $ligne)){continue;}
		if(!preg_match("#^([0-9]+)#", $ligne,$re)){continue;}
		if($GLOBALS["VERBOSE"]){echo "LINE=`$ligne`\n";}
		$ppid=$GLOBALS["CLASS_UNIX"]->PPID_OF($re[1]);
		if($ppid==$re[1]){return $re[1];}
		return $ppid;
	}

}

function apache_tail_pid(){

	$pid=$GLOBALS["CLASS_UNIX"]->PIDOF("/bin/apache-tail");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return 0;
}
function apache_tail(){

	$ARTICA_VERSION=@file_get_contents("/usr/share/artica-postfix/VERSION");
	$enabled=0;
	$EnableNginx=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableNginx"));
	$EnableFreeWeb=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableFreeWeb"));
	$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableInfluxDB")){@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableInfluxDB", 1);}
	
	
	if($EnableNginx==1){$enabled=1;}
	if($EnableFreeWeb==1){$enabled=1;}
	if($EnableIntelCeleron==1){$enabled=0;}
	
		$l[]="";
		$l[]="[APP_ARTICA_APACHE_TAIL]";
		$l[]="service_name=APP_ARTICA_APACHE_TAIL";
		$l[]="master_version=$ARTICA_VERSION";
		$l[]="service_cmd=/etc/init.d/apache-tail";
		$l[]="service_disabled=$enabled";
		$l[]="family=squid";
		$l[]="watchdog_features=1";
		$l[]="installed=1";

		$master_pid=apache_tail_pid();

		if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			
			if($enabled==1){
				if(!$GLOBALS["DISABLE_WATCHDOG"]){
					squid_watchdog_events("[apache_tail]: Not running start it");
					squid_admin_mysql(1, "apache-tail not running [action=start]", null,__FILE__,__LINE__);
					if(!is_file("/etc/init.d/apache-tail")){
						shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.initslapd.php --apache-tail >/dev/null 2>&1");
					}
					shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/apache-tail start >/dev/null 2>&1 &");
				}
			}
			$l[]="running=0\ninstalled=1";
			$l[]="";
			return implode("\n",$l);

		}else{
			if($enabled==0){
				squid_watchdog_events("[apache_tail]: Running stop it");
				squid_admin_mysql(1, "apache-tail running but disabled [action=stop]", null,__FILE__,__LINE__);
				shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/apache-tail stop >/dev/null 2>&1 &");
			}
		}

		
		$timeSec=$GLOBALS["CLASS_UNIX"]->file_time_sec("/etc/artica-postfix/apache-tail.time");
		squid_watchdog_events("[apache_tail]: apache-tail.time = {$timeSec} seconds");




		$l[]=GetMemoriesOf($master_pid);
		$l[]="";
		if($timeSec>40){
			squid_watchdog_events("[apache_tail]: Time out -> Restart");
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/apache-tail restart >/dev/null 2>&1 &");
		}
		return implode("\n",$l);return;


}


//========================================================================================================================================================
function apachesrc(){
	$EnableFreeWeb=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFreeWeb");
	if(!is_numeric($EnableFreeWeb)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableFreeWeb",0);
		$EnableFreeWeb=0;
	}
	$pid_path=$GLOBALS["CLASS_UNIX"]->LOCATE_APACHE_PID_PATH();
	$binpath=$GLOBALS["CLASS_UNIX"]->LOCATE_APACHE_BIN_PATH();
	if(!is_file($pid_path)){$pid_path="/var/run/httpd/httpd.pid";}
	if(strlen($binpath)<5){return;}
	$EnableRemoteStatisticsAppliance=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	$master_pid=APACHE_PID();
	$TOTAL_MEMORY_MB=$GLOBALS["CLASS_UNIX"]->TOTAL_MEMORY_MB();
	$MonitConfig=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ApacheWatchdogMonitConfig")));

	if(!isset($MonitConfig["watchdog"])){$MonitConfig["watchdog"]=1;}
	if(!isset($MonitConfig["watchdogCPU"])){$MonitConfig["watchdogCPU"]=95;}
	if(!isset($MonitConfig["watchdogMEM"])){$MonitConfig["watchdogMEM"]=1500;}
	if(!isset($MonitConfig["watchdogTTL"])){$MonitConfig["watchdogTTL"]=1440;}
	
	
	if(!is_numeric($MonitConfig["watchdog"])){$MonitConfig["watchdog"]=1;}
	if(!is_numeric($MonitConfig["watchdogCPU"])){$MonitConfig["watchdogCPU"]=95;}
	if(!is_numeric($MonitConfig["watchdogMEM"])){$MonitConfig["watchdogMEM"]=1500;}
	if(!is_numeric($MonitConfig["watchdogTTL"])){$MonitConfig["watchdogTTL"]=1440;}
	
	$SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
	$EnableIntelCeleron=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableIntelCeleron"));
	$SquidAllow80Port=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAllow80Port"));
	if($SquidPerformance>2){$EnableFreeWeb=0;}
	if($EnableIntelCeleron==1){$EnableFreeWeb=0;}
	if($SquidAllow80Port==1){$EnableFreeWeb=0;}
	
	$APACHE_MODULES_PATH=$GLOBALS["CLASS_UNIX"]->APACHE_MODULES_PATH();
	$LOCATE_APACHE_VERSION=$GLOBALS["CLASS_UNIX"]->LOCATE_APACHE_VERSION();
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ApacheModulesPath",$APACHE_MODULES_PATH);
	if(is_file("$APACHE_MODULES_PATH/mod_evasive20.so")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ApacheEvasiveInstalled",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ApacheEvasiveInstalled",0);
	}
	
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ApacheVersion",$LOCATE_APACHE_VERSION);
	$l[]="[APP_APACHE_SRC]";
	$l[]="service_name=APP_APACHE_SRC";
	$l[]="master_version=$LOCATE_APACHE_VERSION";
	$l[]="service_cmd=/etc/init.d/apache2";
	$l[]="service_disabled=$EnableFreeWeb";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=www";
	
	$master_pid=APACHE_PID();
	
	if($TOTAL_MEMORY_MB>5){
		if($TOTAL_MEMORY_MB<550){
			$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableFreeWeb",0);
			$EnableFreeWeb=0;
		}
	}
	
	if($EnableFreeWeb==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			ToSyslog("Apache is running pid $master_pid but disabled: Stop it..[".__LINE__."]");
			apache_admin_mysql(0, "Apache Web service is running PID: $master_pid [action=stop]", null,__FILE__,__LINE__);
			shell_exec2("/etc/init.d/apache2 stop");
		}
		$l[]="";
		return implode("\n",$l);return;
	}



	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			ToSyslog("Apache is not running but enabled: [start it]..[".__LINE__."]");
			apache_admin_mysql(0, "Apache Web service is not running [action=start]", null,__FILE__,__LINE__);
			shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.initslapd.php --apache >/dev/null 2>&1");
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/apache2 start >/dev/null 2>&1 &");
		}
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	
	
	if(!$GLOBALS["DISABLE_WATCHDOG"]){
		if($MonitConfig["watchdog"]==1){
			if($MonitConfig["watchdogTTL"]>5){
				$TTL=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($master_pid);
				if($TTL>$MonitConfig["watchdogTTL"]){
					apache_admin_mysql(1, "Apache Web service TTL {$TTL}Mn exceed {$MonitConfig["watchdogTTL"]}Mn [action=restart]", null,__FILE__,__LINE__);
					shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.freeweb.php --restart-maintenance >/dev/null 2>&1 &");
					shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.nginx.php --reload >/dev/null 2>&1 &");
				}
			}
		}
	}
	if(!$GLOBALS["DISABLE_WATCHDOG"]){
		$timefile="/etc/artica-postfix/pids/tests.ScanSize.time";
		$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min($timefile);
		if($time_file>15){
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.freeweb.php --ScanSize >/dev/null 2>&1 &");
		}
	}
	
	return implode("\n",$l);return;

}
//========================================================================================================================================================



function cluebringer(){
	if(!$GLOBALS["CLASS_USERS"]->CLUEBRINGER_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." CLUEBRINGER_INSTALLED = FALSE\n";}
		return;
	}
	$EnableCluebringer=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCluebringer");
	if($EnableCluebringer==null){$EnableCluebringer=0;}
	
	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	if($DisableMessaging==1){$EnableCluebringer=0;}
	
	$pid_path="/var/run/cbpolicyd.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="[APP_CLUEBRINGER]";
	$l[]="service_name=APP_CLUEBRINGER";
	$l[]="master_version=".GetVersionOf("cluebringer");
	$l[]="service_cmd=cluebringer";
	$l[]="service_disabled=$EnableCluebringer";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=postfix";
	if($EnableCluebringer==0){$l[]="";return implode("\n",$l);return;}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_CLUEBRINGER","cluebringer");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}
//========================================================================================================================================================
function sabnzbdplus(){

	if(!$GLOBALS["CLASS_USERS"]->APP_SABNZBDPLUS_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." APP_SABNZBDPLUS_INSTALLED = FALSE\n";}
		return;
	}
	$EnableSabnZbdPlus=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSabnZbdPlus");
	if($EnableSabnZbdPlus==null){$EnableSabnZbdPlus=0;}

	if($GLOBALS["VERBOSE"]){echo __FUNCTION__." EnableSabnZbdPlus = $EnableSabnZbdPlus\n";}
	if(is_file("/usr/share/sabnzbdplus/SABnzbd.py")){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("SABnzbd.py");
		$binary="SABnzbd.py";
	}else{
		$binary=$GLOBALS["CLASS_UNIX"]->find_program("sabnzbdplus");
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN($binary);
	}

	$l[]="[APP_SABNZBDPLUS]";
	$l[]="service_name=APP_SABNZBDPLUS";
	$l[]="master_version=".GetVersionOf("sabnzbdplus");
	$l[]="service_cmd=sabnzbdplus";
	$l[]="service_disabled=$EnableSabnZbdPlus";
	$l[]="pid_path=pidof $binary";
	$l[]="watchdog_features=1";
	$l[]="family=samba";
	if($EnableSabnZbdPlus==0){$l[]="";return implode("\n",$l);return;}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_SABNZBDPLUS","sabnzbdplus");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================
function greensql_version(){
	if(isset($GLOBALS["greensql_version"])){return $GLOBALS["greensql_version"];}
	$f=explode("\n", @file_get_contents("/usr/share/greensql-console/config.php"));
	while (list ($num, $ligne) = each ($f) ){
		if(preg_match("#version.+?([0-9\.]+)#", $ligne,$re)){
			$GLOBALS["greensql_version"]=$re[1];
			return $GLOBALS["greensql_version"];
		}else{
			if($GLOBALS["VERBOSE"]){echo "\"$ligne\" ->NO MATCH\n";}
		}
	}

}
//========================================================================================================================================================
function nscd(){
	if(!$GLOBALS["CLASS_USERS"]->NSCD_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." NSCD_INSTALLED = FALSE\n";}
		return;
	}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("nscd");
	$EnableNSCD=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNSCD");
	if(!is_numeric($EnableNSCD)){$EnableNSCD=0;}
	$pid_path="/var/run/nscd/nscd.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	$version=nscd_version($bin);
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin);}
	if($EnableNSCD==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){shell_exec2("/etc/init.d/nscd stop");}
	}


	$l[]="[APP_NSCD]";
	$l[]="service_name=APP_NSCD";
	$l[]="master_version=$version";

	$l[]="service_disabled=$EnableNSCD";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	if($EnableNSCD==0){$l[]="";return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.initslapd.php nscd");
		shell_exec2("/etc/init.d/nscd start");
		$l[]="";
		return implode("\n",$l);
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;


}
//========================================================================================================================================================
function exim4(){
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("exim4");
	if(!is_file($bin)){return;}
	
	
	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin);
	
	
	
	if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$cmd="{$GLOBALS["KILLBIN"]} -9 $master_pid >/dev/null 2>&1";
		echo " *****  *****  KILLING EXIM **** **** $cmd\n";
		shell_exec2($cmd);
	
		
	}
	return;


}
//========================================================================================================================================================
//========================================================================================================================================================
function conntrackd_version(){
	if(isset($GLOBALS["conntrackd_version"])){return $GLOBALS["conntrackd_version"];}
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("conntrackd");
	exec("$bin_path -v 2>&1",$results);
	while (list ($pid, $line) = each ($results) ){
		if(preg_match("#v([0-9\.]+)#", $line,$re)){
			$GLOBALS["conntrackd_version"]=$re[1];
			return $GLOBALS["conntrackd_version"];
		}
	}
}
//========================================================================================================================================================
function conntrackd(){
	if(!is_file("/etc/init.d/artica-postfix")){return;}
	
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("conntrackd");
	$EnableConntrackd=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableConntrackd");
	if(!is_numeric($EnableConntrackd)){$EnableConntrackd=0;}
	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin,true);
	if($EnableConntrackd==0){if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){shell_exec2("/etc/init.d/conntrackd stop");}}
	
	$l[]="[APP_CONNTRACKD]";
	$l[]="service_name=APP_CONNTRACKD";
	$l[]="master_version=".conntrackd_version();;
	$l[]="service_disabled=$EnableConntrackd";
	$l[]="watchdog_features=1";
	$l[]="installed=1";
	$l[]="family=system";
	$l[]="service_cmd=/etc/init.d/conntrackd";
	if($EnableConntrackd==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			$cmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}/etc/init.d/conntrackd stop >/dev/null 2>&1 &";
			events("$cmd",__FUNCTION__,__LINE__);
			shell_exec2($cmd);
				
		}
		$l[]="";return implode("\n",$l);
		return;
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.conntrackd.php --start >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		return implode("\n",$l);
	}else{
		if($EnableConntrackd==0){
			shell_exec2("{$GLOBALS["KILLBIN"]} -9 $master_pid >/dev/null 2>&1");
		}
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);
}
//========================================================================================================================================================

function syncthing_pid(){
	
	$Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("syncthing");
	$pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("syncthing.*?no-browser");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
}
function syncthing_version(){
	if(isset($GLOBALS["syncthing_version"])){return $GLOBALS["syncthing_version"];}
	
	$Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("syncthing");
	exec("$Masterbin -version 2>&1",$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#syncthing v([0-9\.]+)\s+#" ,$line,$re)){
			$GLOBALS["syncthing_version"]=$re[1];
			return $GLOBALS["syncthing_version"];
		}
	}

}
function syncthing(){
	if(!is_file("/etc/init.d/artica-postfix")){return;}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("syncthing");
	if(!is_file($bin)){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." SYNCTHING_INSTALLED = FALSE\n";}
		$l[]="[APP_SYNCTHING]";
		$l[]="service_name=APP_SYNCTHING";
		$l[]="installed=0";
		$l[]="service_disabled=0";
		return @implode("\n", $l);
	}
	
	$EnableSyncThing=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSyncThing"));
	
	$master_pid=syncthing_pid();
	if($EnableSyncThing==0){if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		shell_exec2("/etc/init.d/syncthing stop");}}

	$l[]="[APP_SYNCTHING]";
	$l[]="service_name=APP_SYNCTHING";
	$l[]="master_version=".syncthing_version();;
	$l[]="service_disabled=$EnableSyncThing";
	$l[]="watchdog_features=1";
	$l[]="installed=1";
	$l[]="family=system";
	$l[]="service_cmd=/etc/init.d/syncthing";
	if($EnableSyncThing==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			$cmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}/etc/init.d/syncthing stop >/dev/null 2>&1 &";
			events("$cmd",__FUNCTION__,__LINE__);
			shell_exec2($cmd);

		}
		$l[]="";return implode("\n",$l);
		return;
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.syncthing.php --start >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		return implode("\n",$l);
	}else{
		if($EnableSyncThing==0){
			shell_exec2("{$GLOBALS["KILLBIN"]} -9 $master_pid >/dev/null 2>&1");
		}
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);
}
//========================================================================================================================================================

function arpd(){
	if(!is_file("/etc/init.d/artica-postfix")){return;}
	if(!$GLOBALS["CLASS_USERS"]->ARPD_INSTALLED){
		$l[]="[APP_ARPD]";
		$l[]="service_name=APP_ARPD";
		$l[]="installed=0";
		$l[]="service_disabled=0";
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." ARPD_INSTALLED = FALSE\n";}
		return @implode("\n", $l);
	}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("arpd");
	$EnableArpDaemon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArpDaemon"));	
	
	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin,true);
	if($EnableArpDaemon==0){if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){shell_exec2("/etc/init.d/arpd stop");}}	
	if($GLOBALS["CLASS_USERS"]->LIGHT_INSTALL){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableArpDaemon",0);$EnableArpDaemon=0;}
	if($GLOBALS["CLASS_UNIX"]->MEM_TOTAL_INSTALLEE()<624288){$EnableArpDaemon=0;}
	
	$l[]="[APP_ARPD]";
	$l[]="service_name=APP_ARPD";
	$l[]="master_version=No";
	$l[]="service_disabled=$EnableArpDaemon";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	if($EnableArpDaemon==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			$cmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} /etc/init.d/arpd stop >/dev/null 2>&1 &"; 
			events("$cmd",__FUNCTION__,__LINE__);
			shell_exec2($cmd);
			
		}
		$l[]="";return implode("\n",$l);
		return;
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.arpd.php --start >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		$l[]="";
		return implode("\n",$l);
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);
}
//========================================================================================================================================================
function netatalk_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("afpd");
	exec("$bin -V 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#afpd\s+([0-9\.]+)#", $line)){$GLOBALS[__FUNCTION__]=$re[1];return $re[1];}
	}
}
//========================================================================================================================================================
function avahi_daemon_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("avahi-daemon");
	exec("$bin -V 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#avahi-daemon\s+([0-9\.]+)#", $line,$re)){$GLOBALS[__FUNCTION__]=$re[1];return $re[1];}
	}
}
//========================================================================================================================================================

function udevd_daemon_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("udevd");
	exec("$bin --version 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#^([0-9\.]+)#", $line,$re)){$GLOBALS[__FUNCTION__]=$re[1];return $re[1];}
	}	
	
}
//========================================================================================================================================================
function dbus_daemon_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("dbus-daemon");
	exec("$bin --version 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#Bus Daemon\s+([0-9\.]+)#", $line,$re)){$GLOBALS[__FUNCTION__]=$re[1];return $re[1];}
	}	
	
}
//========================================================================================================================================================


function memcached_daemon_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("memcached");
	exec("$bin -h 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#memcached\s+([0-9\.]+)#", $line,$re)){$GLOBALS[__FUNCTION__]=$re[1];return $re[1];}
	}
}
//========================================================================================================================================================
function monit_daemon_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("monit");
	if($GLOBALS["VERBOSE"]){echo "monit_daemon_version():: Monit binary: $bin\n";}
	exec("$bin -V 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#monit version\s+([0-9\.]+)#", $line,$re)){$GLOBALS[__FUNCTION__]=$re[1];return $re[1];}
	}
}
//========================================================================================================================================================


function netatalk(){
	if(!$GLOBALS["CLASS_USERS"]->NETATALK_INSTALLED){
		$l[]="[APP_NETATALK]";
		$l[]="service_name=APP_NETATALK";
		$l[]="installed=0";
		$l[]="service_disabled=0";
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." NETATALK_INSTALLED = FALSE\n";}
		return @implode("\n", $l);
	}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("afpd");
	$EnableArpDaemon=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetatalkEnabled");	
	if(!is_numeric($NetatalkEnabled)){$NetatalkEnabled=1;}
	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin,true);
	if($NetatalkEnabled==0){if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){shell_exec2("/etc/init.d/netatalk stop");}}	
	
	$l[]="[APP_NETATALK]";
	$l[]="service_name=APP_NETATALK";
	$l[]="master_version=".netatalk_version();
	$l[]="service_disabled=$NetatalkEnabled";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	$l[]="installed=1";
	if($NetatalkEnabled==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			$cmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}/etc/init.d/artica-postfix stop netatalk >/dev/null 2>&1 &"; 
			events("$cmd",__FUNCTION__,__LINE__);
			shell_exec2($cmd);
			
		}
		$l[]="running=0";
		$l[]="";return implode("\n",$l);
		return;
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_NETATALK","netatalk");
		$l[]="";
		return implode("\n",$l);
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);
}
//========================================================================================================================================================
function avahi_daemon(){
	
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("avahi-daemon");
	$NetatalkEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetatalkEnabled");	
	if(!is_numeric($NetatalkEnabled)){$NetatalkEnabled=1;}
	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin,true);
	if($NetatalkEnabled==0){if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){shell_exec2("/etc/init.d/netatalk stop");}}	
	if(!$GLOBALS["CLASS_USERS"]->NETATALK_INSTALLED){
		$NetatalkEnabled=0;
	}
	
	$l[]="[APP_AVAHI]";
	$l[]="service_name=APP_AVAHI";
	$l[]="master_version=".avahi_daemon_version();
	$l[]="service_disabled=$NetatalkEnabled";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if($NetatalkEnabled==0){
			
			$kill=$GLOBALS["CLASS_UNIX"]->find_program("kill");
			if($GLOBALS["VERBOSE"]){echo "avahi_daemon:: Killing PID $master_pid\n";}
			$GLOBALS["CLASS_UNIX"]->KILL_PROCESS($master_pid,9);
			
		}
	}
	
	if($NetatalkEnabled==0){$l[]="";return implode("\n",$l);return;}
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_AVAHI","netatalk");
		$l[]="";
		return implode("\n",$l);
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);
}
//========================================================================================================================================================
function udevd_daemon(){
	
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("udevd");
	if(!is_file($bin)){
		$l[]="[APP_UDEVD]";
		$l[]="service_name=APP_UDEVD";
		$l[]="installed=0";
		$l[]="service_disabled=0";
		$l[]="service_disabled=0";
		implode("\n",$l);
	}
	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin,true);
		
	
	$l[]="[APP_UDEVD]";
	$l[]="service_name=APP_UDEVD";
	$l[]="master_version=".udevd_daemon_version();
	$l[]="service_disabled=1";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	$l[]="installed=1";
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$l[]="";
		return implode("\n",$l);
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);
}
//========================================================================================================================================================
function dbus_daemon(){
	
	
	$EnableDbusDaemon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDbusDaemon"));
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("dbus-daemon");
	if(!is_file($bin)){
		$l[]="[APP_DBUS]";
		$l[]="service_name=APP_DBUS";
		$l[]="installed=0";
		implode("\n",$l);
	}
	
	
	
	if(!is_file("/etc/machine-id")){$EnableDbusDaemon=0;}
	
	if($EnableDbusDaemon==0){
		$l[]="[APP_DBUS]";
		$l[]="service_name=APP_DBUS";
		$l[]="master_version=".dbus_daemon_version();
		$l[]="service_disabled=0";
		$l[]="watchdog_features=0";
		$l[]="family=system";
		$l[]="";
		return implode("\n",$l);
		
	}
	
	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin,true);
		
	
	$l[]="[APP_DBUS]";
	$l[]="service_name=APP_DBUS";
	$l[]="master_version=".dbus_daemon_version();
	$l[]="service_disabled=1";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$l[]="";
		return implode("\n",$l);
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);
}
//========================================================================================================================================================




function memcached(){
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("memcached");
	if(!is_file($bin)){
		events("memcached not installed",__FUNCTION__,__LINE__);
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." MEMCACHED_INSTALLED = FALSE\n";}
		$l[]="[APP_MEMCACHED]";
		$l[]="service_name=APP_MEMCACHED";
		$l[]="installed=0";
		$l[]="service_disabled=0";
		implode("\n",$l);
		
		return;
	}
	
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("memcached");
	$EnableMemcached=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMemcached");
	$SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
	if($SquidPerformance>2){$EnableMemcached=0;}	
	if(!is_numeric($EnableMemcached)){$EnableMemcached=1;}
	
	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/memcached.pid");
	events("master pid = $master_pid",__FUNCTION__,__LINE__);
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin,true);
		events("master pid = $master_pid after pidof($bin)",__FUNCTION__,__LINE__);
	}
	
	
	
	if($EnableMemcached==0){if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		events("Stopping memcached...",__FUNCTION__,__LINE__);
		shell_exec2("/etc/init.d/artica-memcache stop");
		}
	}	
	
	$l[]="[APP_MEMCACHED]";
	$l[]="service_name=APP_MEMCACHED";
	$l[]="master_version=".memcached_daemon_version();
	$l[]="service_disabled=$EnableMemcached";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	$l[]="service_cmd=/etc/init.d/artica-memcache";
	
	if($EnableMemcached==0){
		$l[]="";return implode("\n",$l);
		return;
	}
	

	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		events("Not running pid `$master_pid`...",__FUNCTION__,__LINE__);
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} /etc/init.d/artica-memcache start");
		}
		$l[]="running=0";
		return implode("\n",$l);
	}
	
	
	if(!$GLOBALS["CLASS_UNIX"]->is_socket("/var/run/memcached.sock")){
		ToSyslog("\"/var/run/memcached.sock\" no such socket",__FUNCTION__,__LINE__);
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} /etc/init.d/artica-memcache restart");
	}else{
		@chmod("/var/run/memcached.sock",0777);
	}
	
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);
}
//========================================================================================================================================================
function monit(){
	if(!$GLOBALS["CLASS_USERS"]->MONIT_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." MEMCACHED_INSTALLED = FALSE\n";}
		$l[]="[APP_MONIT]";
		$l[]="service_name=APP_MONIT";
		$l[]="installed=0";
		$l[]="service_disabled=0";
		return implode("\n",$l);
	}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("monit");
	$EnableDaemon=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMonit");	
	$SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
	if($SquidPerformance>2){$EnableDaemon=0;}
	if(!is_numeric($EnableDaemon)){$EnableDaemon=1;}
	
	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/monit/monit.pid");
	$cache_file="/usr/share/artica-postfix/ressources/logs/web/monit.status.all";
	
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin,true);
	}
	
	if($EnableDaemon==0){if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		shell_exec2("/etc/init.d/monit stop");}}	
	
	$l[]="[APP_MONIT]";
	$l[]="service_name=APP_MONIT";
	$l[]="master_version=".monit_daemon_version();
	$l[]="service_disabled=$EnableDaemon";
	$l[]="watchdog_features=1";
	$l[]="service_cmd=/etc/init.d/monit";
	$l[]="family=system";
	$l[]="installed=1";
	
	if($EnableDaemon==0){$l[]="";return implode("\n",$l);return;}
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if($GLOBALS["VERBOSE"]){echo " **** NO RUNNING ****\n";}
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("/etc/init.d/monit start");
		}
		$l[]="running=0";
		$l[]="";
		return implode("\n",$l);
	}
	
	$AS_CGROUP=false;
	if($GLOBALS["CLASS_SOCKETS"]->EnableIntelCeleron==1){$AS_CGROUP=true;}
	if($AS_CGROUP){
		events("mysqld cgroup must be enabled ....",__FUNCTION__,__LINE__);
		$cgroups=new status_cgroups();
		$limit=$cgroups->GetLimit($master_pid);
		if($cgroups->GetLimit($master_pid)=="unlimited"){
			$cgroups->set_limit("php", $master_pid);
		}
	
	}
	
	
	
	$l[]=GetMemoriesOf($master_pid);
	$l[]="running=1";
	$monit=new monit_unix();
	$monit->WAKEUP();
	$time=$GLOBALS["CLASS_UNIX"]->file_time_min($cache_file);
	if($GLOBALS["VERBOSE"]){echo "$cache_file = {$time}mn DISABLE_WATCHDOG = {$GLOBALS["DISABLE_WATCHDOG"]}\n";}
	if($time>2){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.monit.php --status >/dev/null 2>&1 &");
		}
	}
	return implode("\n",$l);
}
//========================================================================================================================================================




function yaffas(){
	if(!$GLOBALS["CLASS_USERS"]->YAFFAS_INSTALLED){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." YAFFAS_INSTALLED = FALSE\n";}return;}
	$bin="/opt/yaffas/webmin/miniserv.pl";
	$EnableYaffas=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableYaffas");	
	if(!is_numeric($EnableYaffas)){$EnableYaffas=1;}
	$master_pid=trim(@file_get_contents("/opt/yaffas/var/miniserv.pid"));
	if($EnableYaffas==0){if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){shell_exec2("/etc/init.d/artica-postfix stop yaffas");}}	
	
	$l[]="[APP_YAFFAS]";
	$l[]="service_name=APP_YAFFAS";
	$l[]="master_version=".yaffas_version();
	$l[]="service_disabled=$EnableYaffas";
	$l[]="watchdog_features=1";
	$l[]="family=mailbox";
	if($EnableYaffas==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			$cmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} /etc/init.d/artica-postfix stop yaffas >/dev/null 2>&1 &"; 
			events("$cmd",__FUNCTION__,__LINE__);
			shell_exec2($cmd);
			
		}
		$l[]="";return implode("\n",$l);
		return;
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_YAFFAS","yaffas");
		$l[]="";
		return implode("\n",$l);
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);
}
//========================================================================================================================================================
function nscd_version($bin){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	exec("$bin -V 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#nscd.+?([0-9\.]+)#", $line,$re)){$GLOBALS[__FUNCTION__]=$re[1];return $re[1];}
	}

}
//========================================================================================================================================================
function yaffas_version($bin){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	
	$results=explode("\n",@file_get_contents("/opt/yaffas/etc/installed-products"));
	
	while (list ($num, $line) = each ($results)){
		if(preg_match("#framework=yaffas v([0-9\.]+)#", $line,$re)){$GLOBALS[__FUNCTION__]=$re[1];return $re[1];}
	}

}
function greensql(){

	if(!$GLOBALS["CLASS_USERS"]->APP_GREENSQL_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." APP_GREENSQL_INSTALLED = FALSE\n";}
		$l[]="[APP_GREENSQL]";
		$l[]="service_name=APP_GREENSQL";
		$l[]="installed=0";
		$l[]="service_disabled=0";
		return implode("\n",$l);
	}
	$EnableGreenSQL=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGreenSQL");
	if(!is_numeric($EnableGreenSQL)){$EnableGreenSQL=1;}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("greensql-fw");
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__." EnableGreenSQL = $EnableGreenSQL\n";}


	$l[]="[APP_GREENSQL]";
	$l[]="service_name=APP_GREENSQL";
	$l[]="master_version=".greensql_version();
	$l[]="service_cmd=greensql";
	$l[]="service_disabled=$EnableGreenSQL";
	$l[]="watchdog_features=1";
	$l[]="family=samba";
	$l[]="installed=1";
	if($EnableGreenSQL==0){$l[]="";return implode("\n",$l);return;}

	$master_pid=@file_get_contents("/var/run/greensql-fw.pid");
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__." /var/run/greensql-fw.pid = $master_pid\n";}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin,true);
	}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_GREENSQL","greensql");
		$l[]="running=0";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="running=1";
	return implode("\n",$l);
}
//========================================================================================================================================================

function stunnel(){

	if(!$GLOBALS["CLASS_USERS"]->stunnel4_installed){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." stunnel4_installed = FALSE\n";}
		$l[]="[STUNNEL]";
		$l[]="service_name=APP_STUNNEL";
		$l[]="installed=0";
		$l[]="service_disabled=0";
		return implode("\n",$l);
		
	}
	$sTunnel4enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("sTunnel4enabled");
	if($sTunnel4enabled==null){$sTunnel4enabled=0;}
	
	
	
	
	$binary=$GLOBALS["CLASS_UNIX"]->LOCATE_STUNNEL_BIN();
	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/stunnel/stunnel4.pid");

	if($GLOBALS["VERBOSE"]){echo "binary............: $binary\n";}
	if($GLOBALS["VERBOSE"]){echo "PID...............: $master_pid\n";}

	$l[]="[STUNNEL]";
	$l[]="service_name=APP_STUNNEL";
	$l[]="master_version=".stunnel_version();
	$l[]="service_cmd=".$GLOBALS["CLASS_UNIX"]->LOCATE_STUNNEL_INIT();
	$l[]="service_disabled=$sTunnel4enabled";
	$l[]="pid_path=pidof $binary";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	$l[]="installed=1";
	if($sTunnel4enabled==0){$l[]="";return implode("\n",$l);return;}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
				shell_exec2("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.stunnel.php --start &");
		}
		
		$l[]="running=0";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="running=1";
	return implode("\n",$l);return;
}


function stunnel_version(){
	if(isset($GLOBALS["stunnel_version"])){return $GLOBALS["stunnel_version"];}

	$stunnel=$GLOBALS["CLASS_UNIX"]->LOCATE_STUNNEL();
	exec("$stunnel -version 2>&1",$f);
	while (list ($pid, $line) = each ($f) ){
		if(preg_match("#stunnel\s+([0-9\.]+)#", $line,$re)){$GLOBALS["stunnel_version"]=$re[1];return $re[1];}
	}

}

//========================================================================================================================================================


function pptp_clients(){
	if(!$GLOBALS["CLASS_USERS"]->PPTP_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." PPTP_INSTALLED = FALSE\n";}
		return;
	}
	$version=GetVersionOf("pptpd");
	$array=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PPTPVpnClients")));
	if(!is_array($array)){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not an array PPTPVpnClients\n";}
		return;
	}
	if(count($array)==0){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." PPTPVpnClients\n";}
		return;
	}
	$reload=false;
	while (list ($connexionname, $PPTPDConfig) = each ($array) ){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." $connexionname...:{$PPTPDConfig["ENABLED"]}\n";}
		if($PPTPDConfig["ENABLED"]<>1){continue;}
		$arrayPIDS=pptp_client_is_active($connexionname);
		$l[]="[PPTPDCLIENT_$connexionname]";
		$l[]="service_name=$connexionname";
		$l[]="master_version=$version";
		$l[]="service_cmd=pptpd-clients";
		$l[]="service_disabled=1";
		$l[]="pid_path=";
		$l[]="watchdog_features=1";
		$l[]="family=network";

		if(!is_array($arrayPIDS)){$reload=true;}else{
			$l[]=GetMemoriesOf($arrayPIDS[0]);
			$l[]="";
		}
	}

	$l[]="";
	if(!$GLOBALS["DISABLE_WATCHDOG"]){
		if($reload){
			$cmd="{$GLOBALS["PHP5"]} ". dirname(__FILE__)."/exec.pptpd.php --clients-start &";
			events("START PPTP Clients -> $cmd",__FUNCTION__,__LINE__);
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET($cmd);
		}}

		return implode("\n",$l);return;

}
//========================================================================================================================================================
function pptp_client_is_active($connexionname){
	if($GLOBALS["PGREP"]==null){
		$unix=new unix();
		$GLOBALS["PGREP"]=$unix->find_program("pgrep");
	}

	$cmd="{$GLOBALS["PGREP"]} -l -f \"pptp.+?call $connexionname\" 2>&1";
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__." ->$cmd\n";}
	exec($cmd,$results);

	while (list ($num, $line) = each ($results) ){
		if(preg_match("#^([0-9]+).+?pptp#",$line,$re)){
			if($GLOBALS["VERBOSE"]){echo __FUNCTION__." ->PID: {$re[1]}\n";}
			if($unix->PID_IS_CHROOTED($re[1])){continue;}
			$arr[]=$re[1];
		}else{
			if($GLOBALS["VERBOSE"]){echo __FUNCTION__." NO MATCH \"$line\"\n";}
		}

	}

	return $arr;


}



function tftpd(){
	if(!$GLOBALS["CLASS_USERS"]->TFTPD_INSTALLED){return;}
	$EnableTFTPD=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableTFTPD");
	if($EnableTFTPD==null){$EnableTFTPD=1;}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("inetd");
	if(!is_file($bin)){
		$bin=$GLOBALS["CLASS_UNIX"]->find_program("xinetd");
		if(is_file("/var/run/xinetd.pid")){
			$master_pid=trim(@file_get_contents("/var/run/xinetd.pid"));
		}
	}
	if(!is_numeric($master_pid)){$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin);}


	$l[]="[APP_TFTPD]";
	$l[]="service_name=APP_TFTPD";
	$l[]="master_version=".GetVersionOf("tftpd");
	$l[]="service_cmd=tftpd";
	$l[]="service_disabled=$EnableTFTPD";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=0";
	$l[]="family=storage";
	if($EnableTFTPD==0){$l[]="";return implode("\n",$l);return;}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}



//========================================================================================================================================================
function postfix_multi_status(){
	if(!is_array($GLOBALS["MULTI-INSTANCES-LIST"])){$calc=true;}
	if($GLOBALS["MULTI-INSTANCES-TIME"]==null){$cacl=true;}
	if(calc_time_min($GLOBALS["MULTI-INSTANCES-TIME"])>5){$cacl=true;}
	if($GLOBALS["VERBOSE"]){echo "GetVersionOf(postfix) line:".__LINE__."\n";}
	$version=postfix_version();

	if($GLOBALS["VERBOSE"]){echo "calc=\"$cacl\" postfix v$version\n";}

	if($calc){
		if($GLOBALS["VERBOSE"]){echo "POSTFIX_MULTI_INSTANCES_LIST() line:".__LINE__."\n";}
		$GLOBALS["MULTI-INSTANCES-LIST"]=$GLOBALS["CLASS_UNIX"]->POSTFIX_MULTI_INSTANCES_LIST();
		$GLOBALS["MULTI-INSTANCES-TIME"]=time();
	}
	if(is_array($GLOBALS["MULTI-INSTANCES-LIST"])){
		while (list ($num, $instance) = each ($GLOBALS["MULTI-INSTANCES-LIST"]) ){
			if($instance==null){continue;}
			$l[]="[POSTFIX-MULTI-$instance]";
			$l[]="service_name=$instance";
			$l[]="master_version=".GetVersionOf("postfix");
			$l[]="service_cmd=postfix-multi";
			$l[]="service_disabled=1";
			$l[]="remove_cmd=--postfix-remove";
			$l[]="family=postfix";
			$master_pid=$GLOBALS["CLASS_UNIX"]->POSTFIX_MULTI_PID($instance);
			if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
				$l[]="";return implode("\n",$l);return;
			}

			$l[]=GetMemoriesOf($master_pid);
			$l[]="";
		}
	}
	if(is_array($l)){return implode("\n",$l);}



}






function artica_policy(){
	return;
	if(!is_file("/usr/share/artica-postfix/exec.artica-filter-daemon.php")){return;}
	$pid_path="/etc/artica-postfix/exec.artica-filter-daemon.php.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	$EnableArticaPolicyFilter=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaPolicyFilter");
	if($EnableArticaPolicyFilter==null){$EnableArticaPolicyFilter=0;}
	$l[]="[APP_ARTICA_POLICY]";
	$l[]="service_name=APP_ARTICA_POLICY";
	$l[]="master_version=".GetVersionOf("artica");
	$l[]="service_cmd=artica-policy";
	$l[]="service_disabled=$EnableArticaPolicyFilter";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=postfix";
	$l[]="installed=1";
	if($EnableArticaPolicyFilter<>1){
		$l[]="";$l[]="";
		return implode("\n",$l);
		return;
	}

	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_ARTICA_POLICY","artica-policy");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================
function artica_status(){
	if(!is_file("/usr/share/artica-postfix/bin/artica.status.php")){return;}
	if($GLOBALS["TOTAL_MEMORY_MB"]<400){return;}
	$pid_path="/etc/artica-postfix/exec.status.php.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	$EnableArticaStatus=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaStatus");
	if($EnableArticaStatus==null){$EnableArticaStatus=1;}
	$l[]="[APP_ARTICA_STATUS]";
	$l[]="service_name=APP_ARTICA_STATUS";
	$l[]="master_version=".GetVersionOf("artica");
	$l[]="service_cmd=artica-status";
	$l[]="service_disabled=$EnableArticaStatus";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	$l[]="installed=1";
	if($EnableArticaStatus<>1){
		$l[]="";$l[]="";
		return implode("\n",$l);
		return;
	}

	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_ARTICA_STATUS","artica-status");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}







function mailman(){
	if(!$GLOBALS["CLASS_USERS"]->MAILMAN_INSTALLED){return null;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MailManEnabled");
	if($enabled==null){$enabled=0;}
	$pid_path=trim(GetVersionOf("mailman-pid"));
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="[MAILMAN]";
	$l[]="service_name=APP_MAILMAN";
	$l[]="master_version=".GetVersionOf("mailman");
	$l[]="service_cmd=mailman";
	$l[]="service_disabled=$enabled";
	$l[]="family=postfix";
	$l[]="pid_path=$pid_path";
	//$l[]="remove_cmd=--milter-grelist-remove";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);return;}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;

}
//========================================================================================================================================================

function kas3_milter(){



	if(!is_file("/usr/local/ap-mailfilter3/bin/kas-milter")){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}
		return null;
	}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KasxFilterEnabled");
	if($enabled==null){$enabled=0;}
	$pid_path="/usr/local/ap-mailfilter3/run/kas-milter.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="[KAS_MILTER]";
	$l[]="service_name=APP_KAS3_MILTER";
	$l[]="master_version=".GetVersionOf("kas3");
	$l[]="service_cmd=kas3";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="remove_cmd=--kas3-remove";
	$l[]="family=postfix";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);return;}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}
//========================================================================================================================================================
function rsync_version(){
	$rsync=$GLOBALS["CLASS_UNIX"]->find_program("rsync");
	if(isset($GLOBALS["rsync-VER"])){return $GLOBALS["rsync-VER"];}
	exec("$rsync --version 2>&1",$results);
	
	while (list ($num, $val) = each ($results)){
		if(!preg_match("#rsync\s+version\s+([0-9\.]+)#",$val,$re)){continue;}
		$GLOBALS["rsync-VER"]=$re[1];
		return $re[1];
	
	}
	
}
function rsync_debian_mirror(){

	$rsync=$GLOBALS["CLASS_UNIX"]->find_program("rsync");

	if(!is_file($rsync)){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}
		return null;
	}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MirrorEnableDebian");
	if($enabled==null){$enabled=0;}
	
	$MirrorDebianDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MirrorDebianDir");
	if($MirrorDebianDir==null){$MirrorDebianDir="/home/mirrors/Debian";}
	
	
	
	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("rsync.+?$MirrorDebianDir");

	$l[]="[APP_RSYNC_DEBIAN]";
	$l[]="service_name=APP_RSYNC_DEBIAN";
	$l[]="master_version=".rsync_version();
	$l[]="service_disabled=$enabled";
	$l[]="remove_cmd=--kas3-remove";
	$l[]="service_cmd=/etc/init.d/debian-artmirror";
	$l[]="family=proxy";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);return;}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}




function kas3_ap(){



	if(!is_file("/usr/local/ap-mailfilter3/bin/kas-milter")){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}
		return null;
	}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KasxFilterEnabled");
	if($enabled==null){$enabled=0;}
	$pid_path="/usr/local/ap-mailfilter3/run/ap-process-server.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="[KAS3]";
	$l[]="service_name=APP_KAS3";
	$l[]="master_version=".GetVersionOf("kas3");
	$l[]="service_cmd=kas3";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="remove_cmd=--kas3-remove";
	$l[]="family=postfix";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);return;}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================
function samba_version(){
	if(isset($GLOBALS["SMBD-VER"])){return $GLOBALS["SMBD-VER"];}
	$smbd_bin=$GLOBALS["CLASS_UNIX"]->find_program("smbd");
	if($smbd_bin==null){return;}	
	exec("$smbd_bin -V 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#Version\s+(.+)#", $val,$re)){
			$GLOBALS["SMBD-VER"]=$re[1];
			return $GLOBALS["SMBD-VER"];
		}	
	}
	
}

function winbindd_ping(){
	$smbcontrol=$GLOBALS["CLASS_UNIX"]->find_program('smbcontrol');
	
	shell_exec2("{$GLOBALS["CHMOD"]} 0750 /var/lib/samba/winbindd_privileged >/dev/null 2>&1");
	
	
	$results=exec("$smbcontrol winbindd ping 2>&1");
	
	if(preg_match("#No replies received#i", $results)){
		Winbindd_events("Winbindd service ping failed","winbindd_ping","winbindd_ping",__LINE__);
		$GLOBALS["CLASS_UNIX"]->send_email_events("Winbindd service ping failed","Winbindd failed to answer with error: $results\nArtica will try to restart it","samba");
		
		shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/winbind restart --force >/dev/null 2>&1 &");	
	}
}


function Winbindd_events($text,$sourcefunction=null,$sourceline=null){
	$GLOBALS["CLASS_UNIX"]->events("exec.status.php::$text","/var/log/squid.watchdog.log",false,$sourcefunction,$sourceline);
	
}

function winbind_pid(){
	
	$pidfile=$GLOBALS["CLASS_UNIX"]->LOCATE_WINBINDD_PID();
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pidfile);
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	$winbindbin=$GLOBALS["CLASS_UNIX"]->find_program("winbindd");
	$pid=$GLOBALS["CLASS_UNIX"]->PIDOF($winbindbin);
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){
		@file_put_contents($pidfile, $pid);
		return $pid;
	}

	return null;

}

function winbindd_logs($disabled=false){
	$f[]="log.nmbd";
	$f[]="log.smbd";
	$f[]="log.wb-BUILTIN";
	$f[]="log.winbindd";
	$f[]="log.winbindd-idmap";
	
	$echo=$GLOBALS["CLASS_UNIX"]->find_program("echo");
	
	while (list ($num, $filename) = each ($f)){
		if(!is_file("/var/log/samba/$filename")){continue;}
		if($disabled){@unlink("/var/log/samba/$filename");continue;}
		$size=@filesize("/var/log/samba/$filename");
		$size=$size/1024;
		$size=$size/1024;
		squid_watchdog_events("[winbindd]: $filename = {$size}Mb");
		if($size>100){
			squid_admin_mysql(1, "$filename exceed 100M [action=clean]", null,__FILE__,__LINE__);
			shell_exec("$echo \"\" >/var/log/samba/$filename");
			continue;
		}
		$time=$GLOBALS["CLASS_UNIX"]->file_time_min("/var/log/samba/$filename");
		squid_watchdog_events("[winbindd]: $filename = {$time}mn");
		if($time>2880){
			squid_admin_mysql(1, "$filename exceed 2880mn ({$time}mn) [action=remove]", null,__FILE__,__LINE__);
			@unlink("/var/log/samba/$filename");
			continue;
		}
		
	}
	
}

function scanned_only(){
	if(!is_file("/usr/share/artica-postfix/bin/artica-install")){return;}
	if(!$GLOBALS["CLASS_USERS"]->SAMBA_INSTALLED){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}return null;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableScannedOnly");
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program('scannedonlyd_clamav');
	if(strlen($binpath)<strlen("scannedonlyd_clamav")){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}
		return null;
	}
	
	
	if(!is_numeric($enabled)){$enabled=1;}
	
	$pid_path="/var/run/scannedonly.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="[SAMBA_SCANNEDONLY]";
	$l[]="service_name=APP_SCANNED_ONLY";
	$l[]="master_version=unknown";
	$l[]="service_cmd=samba";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	//$l[]="remove_cmd=--samba-remove";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($binpath);}
	
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);return;}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}



function cups(){



	if(!$GLOBALS["CLASS_USERS"]->CUPS_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}
		return null;
	}
	$enabled=1;
	if($enabled==null){$enabled=0;}
	$pid_path="/var/run/cups/cupsd.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="[CUPS]";
	$l[]="service_name=APP_CUPS";
	$l[]="master_version=".GetVersionOf("cups");
	$l[]="service_cmd=cups";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="family=storage";
	//$l[]="remove_cmd=--samba-remove";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);return;}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================

function apache_ocsweb(){



	if(!$GLOBALS["CLASS_USERS"]->APACHE_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." apache not installed\n";}
		return null;
	}

	if(!is_file("/usr/share/ocsinventory-reports/ocsreports/dbconfig.inc.php")){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}
		return null;
	}

	$OCSNGEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OCSNGEnabled");
	if($OCSNGEnabled==null){$OCSNGEnabled=1;}
	$pid_path="/var/run/apache-ocs/httpd.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="[APP_OCSI]";
	$l[]="service_name=APP_OCSI";
	$l[]="master_version=".GetVersionOf("ocsi");
	$l[]="service_cmd=ocsweb";
	$l[]="service_disabled=$OCSNGEnabled";
	$l[]="pid_path=$pid_path";
	$l[]="family=computers";
	$l[]="watchdog_features=1";
	//$l[]="remove_cmd=--samba-remove";

	if($OCSNGEnabled==0){return implode("\n",$l);return;}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_OCSI","ocsweb");
		$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);return;}
		$l[]="running=1";
		$l[]=GetMemoriesOf($master_pid);
		$l[]="";
		return implode("\n",$l);return;
}
//========================================================================================================================================================
function apache_ocsweb_download(){



	if(!$GLOBALS["CLASS_USERS"]->APACHE_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." apache not installed\n";}
		return null;
	}

	if(!is_file("/usr/share/ocsinventory-reports/ocsreports/dbconfig.inc.php")){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}
		return null;
	}
	$UseFusionInventoryAgents=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseFusionInventoryAgents");
	$OCSNGEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OCSNGEnabled");
	if($OCSNGEnabled==null){$OCSNGEnabled=1;}
	if($UseFusionInventoryAgents==null){$UseFusionInventoryAgents=1;}
	$pid_path="/var/run/apache-ocs/httpd-download.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$f[]='cacert.pem';
	$f[]='server.crt';
	$f[]='server.key';
	 
	while (list ($num, $file) = each ($f) ){
		if(!is_file("/etc/ocs/cert/$file")){
			$OCSNGEnabled=0;
		}
	}
	if($UseFusionInventoryAgents==1){$OCSNGEnabled=0;}

	$l[]="[APP_OCSI_DOWNLOAD]";
	$l[]="service_name=APP_OCSI_DOWNLOAD";
	$l[]="master_version=".GetVersionOf("ocsi");
	$l[]="service_cmd=ocsweb";
	$l[]="service_disabled=$OCSNGEnabled";
	$l[]="pid_path=$pid_path";
	$l[]="family=computers";
	$l[]="watchdog_features=1";
	//$l[]="remove_cmd=--samba-remove";

	if($OCSNGEnabled==0){return implode("\n",$l);return;}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_OCSI_DOWNLOAD","ocsweb");
		$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);return;}
		$l[]="running=1";
		$l[]=GetMemoriesOf($master_pid);
		$l[]="";
		return implode("\n",$l);return;
}
//========================================================================================================================================================
function ocs_agent(){
	if(!$GLOBALS["CLASS_USERS"]->OCS_LNX_AGENT_INSTALLED){return null;}
	$OCSNGEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOCSAgent");
	if($OCSNGEnabled==null){$OCSNGEnabled=1;}
	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("/usr/local/bin/ocsinventory-agent");


	 

	$l[]="[APP_OCSI_LINUX_CLIENT]";
	$l[]="service_name=APP_OCSI_LINUX_CLIENT";
	$l[]="master_version=".GetVersionOf("ocsagent");
	$l[]="service_cmd=ocsagent";
	$l[]="service_disabled=$OCSNGEnabled";
	$l[]="pid_path=$pid_path";
	$l[]="family=computers";
	$l[]="watchdog_features=1";
	//$l[]="remove_cmd=--samba-remove";

	if($OCSNGEnabled==0){return implode("\n",$l);return;}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_OCSI_LINUX_CLIENT","ocsagent");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}

//========================================================================================================================================================

function openssh_version(){
	if(isset($GLOBALS["OPENSSH-VER"])){return $GLOBALS["OPENSSH-VER"];}
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program('sshd');
	if($bin_path==null){return;}
	exec("$bin_path -h 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#OpenSSH_(.+?),#", $val,$re)){
			$GLOBALS["OPENSSH-VER"]=$re[1];
			return $GLOBALS["OPENSSH-VER"];
		}
	}
	
}
//========================================================================================================================================================

function openssh(){



	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program('sshd');
	if($bin_path==null){return;}
	$pid_path=$GLOBALS["CLASS_UNIX"]->LOCATE_SSHD_PID_PATH();
	$master_pid=trim(@file_get_contents($pid_path));
	$EnableSSHD=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSSHD");
	if($EnableSSHD==null){$EnableSSHD=1;}
	
	
	
	$l[]="[APP_OPENSSH]";
	$l[]="service_name=APP_OPENSSH";
	$l[]="master_version=".openssh_version();
	$l[]="service_cmd=/etc/init.d/artica-ssh";
	$l[]="service_disabled=1";
	$l[]="pid_path=$pid_path";
	$l[]="family=network";
	$l[]="watchdog_features=1";
	if($EnableSSHD==0){return implode("\n",$l);return;}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin_path);
	}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		system_admin_events("OpenSSH server is not running, start it",__FUNCTION__,__FILE__,__LINE__);
		shell_exec2("/etc/init.d/ssh start >/dev/null 2>&1 &");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		
	}
	
	$ttl=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($master_pid);
	
	
	
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}



//========================================================================================================================================================
function gdm(){



	$gdm_path=$GLOBALS["CLASS_UNIX"]->find_program('gdm');
	if($gdm_path==null){return;}
	$pid_path="/var/run/gdm.pid";
	$master_pid=trim(@file_get_contents($pid_path));

	$l[]="[GDM]";
	$l[]="service_name=APP_GDM";
	$l[]="master_version=".GetVersionOf("gdm");
	//$l[]="service_cmd=apache-groupware";
	$l[]="service_disabled=1";
	$l[]="pid_path=$pid_path";
	$l[]="family=system";
	//$l[]="remove_cmd=--samba-remove";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);return;}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================
function consolekit(){
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program('console-kit-daemon');
	if($binpath==null){return;}

	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
	if($master_pid==null){return;}
	$l[]="[CONSOLEKIT]";
	$l[]="service_name=APP_CONSOLEKIT";
	$l[]="master_version=0.00";
	$l[]="binpath=$binpath";
	//$l[]="service_cmd=apache-groupware";
	$l[]="service_disabled=1";
	$l[]="family=system";
	$l[]="pid_path=$pid_path";
	//$l[]="remove_cmd=--samba-remove";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);return;}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================
function xfce(){
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program('xfdesktop');
	if($binpath==null){return;}

	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($binpath);

	$l[]="[XFCE]";
	$l[]="service_name=APP_XFCE";
	$l[]="master_version=".GetVersionOf("xfce");
	$l[]="family=system";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$l[]="running=0";
		$l[]="installed=1";
		$l[]="service_disabled=0";
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]="running=1";
	$l[]="service_disabled=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================
function XMail(){
	$binpath="/var/lib/xmail/bin/XMail";
	if($binpath==null){return;}

	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){return;}
	shell_exec2("{$GLOBALS["KILLBIN"]} -9 $master_pid");
}
//========================================================================================================================================================







function _zarafa_checkExtension($name, $version=""){
	$result = true;
	$help_msg=null;
	if (extension_loaded($name)){
		if (version_compare(phpversion($name), $version) == -1){
			$GLOBALS["ZARAFA_ERROR"]=_zarafa_error_version("PHP ".$name." extension",phpversion($name), $version, $help_msg);
			$result = false;
		}
	}else{
			
		$GLOBALS["ZARAFA_ERROR"]=_zarafa_error_notfound("PHP ".$name." extension", $help_msg);
		$result = false;
	}
	return $result;
}

function vps_servers(){
	$xr=$GLOBALS["CLASS_UNIX"]->find_program("lxc-version");
	if(strlen($xr)<4){return;}
	$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
	if($EnableIntelCeleron==1){return;}
	if(is_file("/etc/artica-postfix/KASPERSKY_WEB_APPLIANCE")){return;}
	if(is_file("/etc/artica-postfix/WEBSTATS_APPLIANCE")){return;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LXCEnabled");
	if(!is_numeric($enabled)){$enabled=0;}
	if($enabled==0){return;}
	if($GLOBALS["VERBOSE"]){$verbs=" --verbose";}
	exec("{$GLOBALS["PHP5"]} ". dirname(__FILE__)."/exec.vservers.php --status$verbs 2>&1",$results);
	return implode("\n",$results);return;


}

function crossroads_multiple(){
	$xr=$GLOBALS["CLASS_UNIX"]->find_program("xr");
	if(strlen($xr)<4){return;}
	if(!is_file( dirname(__FILE__)."/exec.crossroads.php")){return;}
	if($GLOBALS["VERBOSE"]){$verbs=" --verbose";}
	exec("{$GLOBALS["PHP5"]} ". dirname(__FILE__)."/exec.crossroads.php --multiples-status$verbs 2>&1",$results);
	return implode("\n",$results);return;
}


function roundcube_db(){

	$RoundCubeMySQLServiceType=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RoundCubeMySQLServiceType");
	if(!is_numeric($RoundCubeMySQLServiceType)){$RoundCubeMySQLServiceType=1;}
	$RoundCubeDedicateMySQLServer=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RoundCubeDedicateMySQLServer");
	if(!is_numeric($RoundCubeDedicateMySQLServer)){$RoundCubeDedicateMySQLServer=0;}
	if($RoundCubeDedicateMySQLServer==0){return;}

	$pid_path="/var/run/roundcube-db.pid";
	$master_pid=trim(@file_get_contents($pid_path));


	$l[]="[APP_ROUNDCUBE_DB]";
	$l[]="service_name=APP_ROUNDCUBE_DB";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->MYSQL_VERSION();
	$l[]="service_cmd=/etc/init.d/roundcube-db";
	$l[]="service_disabled=$RoundCubeDedicateMySQLServer";
	$l[]="family=mailbox";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";

	if($RoundCubeDedicateMySQLServer==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$mysqld=$GLOBALS["CLASS_UNIX"]->find_program("mysqld");
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("$mysqld.*?--pid-file=$pid_path");
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.roundcube-db.php --start >/dev/null 2>&1");
			shell_exec2($cmd);
				
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}


	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.roundcube-db.php --databasesize >/dev/null 2>&1 &");
	return implode("\n",$l);return;
}
//========================================================================================================================================================



function ntopng_version(){
	
	if(isset($GLOBALS["ntopng_version"])){return $GLOBALS["ntopng_version"];}
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("ntopng");
	if(!is_file($masterbin)){return "0.0.0";}
	exec("$masterbin -h 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#ntopng.*?v\.([0-9\.]+)#", $val,$re)){
			$GLOBALS["ntopng_version"]=trim($re[1]);
			return $GLOBALS["ntopng_version"];
		}
	}
}

function ntopng_pid(){
	
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("ntopng");
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file('/var/run/ntopng/ntopng.pid');
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF($masterbin);
}
function redis_pid(){
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("redis-server");
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file('/var/run/redis/redis-server.pid');
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN($masterbin." -f /etc/redis/redis.conf");
}
function redis_version(){
	
	if(isset($GLOBALS["redis_version"])){return $GLOBALS["redis_version"];}
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("redis-server");
	if(!is_file($masterbin)){return "0.0.0";}
	exec("$masterbin -v 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#Redis server version\s+([0-9\.]+)#", $val,$re)){
			$GLOBALS["redis_version"]=trim($re[1]);
			return $GLOBALS["redis_version"];
		}
	}
}


function ntopng(){
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("ntopng");
	if(!is_file($masterbin)){return;}
	$enabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablentopng"));
	
	$SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
	$EnableIntelCeleron=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableIntelCeleron"));
	if($SquidPerformance>2){$Enablentopng=0;}
	if($EnableIntelCeleron==1){$Enablentopng=0;}
	
	$l[]="[APP_NTOPNG]";
	$l[]="service_name=APP_NTOPNG";
	$l[]="master_version=".ntopng_version();
	$l[]="service_cmd=/etc/init.d/ntopng";
	$l[]="service_disabled=$enabled";
	$l[]="family=proxy";
	$l[]="watchdog_features=1";

	if($enabled==0){
		$master_pid=ntopng_pid();
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			ToSyslog("Stopping ntopng pid $master_pid, service disabled");
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/ntopng stop >/dev/null 2>&1 &");
		}
		return implode("\n",$l);return;}

	$master_pid=ntopng_pid();

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/ntopng start >/dev/null 2>&1 &");
			
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	$CacheFile="/etc/artica-postfix/settings/Daemons/NTOPNgSize";
	if(!is_file($CacheFile)){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.ntopng.php --clean  >/dev/null 2>&1 &");
		return implode("\n",$l);return;
	}
	
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.ntopng.php.cleanstorage.time");
	if($time_file>1880){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.ntopng.php --clean  >/dev/null 2>&1 &");
	}
	return implode("\n",$l);return;
}
function redis_server(){
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("ntopng");
	if(!is_file($masterbin)){return;}
	$enabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablentopng"));
	
	$SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
	if($SquidPerformance>2){$Enablentopng=0;}
	$l[]="[APP_REDIS_SERVER]";
	$l[]="service_name=APP_REDIS_SERVER";
	$l[]="master_version=".redis_version();
	$l[]="service_cmd=/etc/init.d/redis-server";
	$l[]="service_disabled=$enabled";
	$l[]="family=proxy";
	$l[]="watchdog_features=1";

	if($enabled==0){
		$master_pid=redis_pid();
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/ntopng stop >/dev/null 2>&1 &");
		}
		
		return implode("\n",$l);
		return;
	}

	$master_pid=redis_pid();

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
				
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/redis-server start >/dev/null 2>&1 &");
				
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;
}










//========================================================================================================================================================
function rdpproxy_pid(){

	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/redemption/rdpproxy.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	$Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("rdpproxy");
	return $GLOBALS["CLASS_UNIX"]->PIDOF($Masterbin);

}
//========================================================================================================================================================
function rdpproxy_authhook_pid(){
	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
	exec("$pgrep -l -f \"authhook.py\" 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#pgrep#", $ligne)){continue;}
		if(!preg_match("#^([0-9]+)\s+#", $ligne,$re)){continue;}
		return $re[1];
	}

}
//========================================================================================================================================================
function rdpproxy_version(){
	if(isset($GLOBALS["rdpproxy_version"])){return $GLOBALS["rdpproxy_version"];}
	$Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("rdpproxy");
	exec("$Masterbin --version 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(!preg_match("#Version\s+([0-9\.]+)#i", $ligne,$re)){continue;}
		$GLOBALS["rdpproxy_version"]= $re[1];
		return $GLOBALS["rdpproxy_version"];
	}
}
//========================================================================================================================================================
function rdpproxy(){
	$Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("rdpproxy");
	if(!is_file($Masterbin)){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}return null;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRDPProxy");
	if(!is_numeric($enabled)){$enabled=0;}
	$master_pid=rdpproxy_pid();
	

	$l[]="[APP_RDPPROXY]";
	$l[]="service_name=APP_RDPPROXY";
	$l[]="master_version=".rdpproxy_version();
	$l[]="service_cmd=/etc/init.d/rdpproxy";
	$l[]="service_disabled=$enabled";
	$l[]="family=proxy";
	$l[]="pid_path=";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.rdpproxy.php --start");
			shell_exec2($cmd);
			
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}else{
		if($enabled==0){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/rdpproxy stop >/dev/null 2>&1 &");
		}
	}

	@file_put_contents("/var/run/redemption/rdpproxy.pid", $master_pid);
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================
function rdpproxy_authhook(){
	$Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("rdpproxy");
	if(!is_file($Masterbin)){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}return null;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRDPProxy");
	if(!is_numeric($enabled)){$enabled=0;}
	$master_pid=rdpproxy_authhook_pid();
	

	$l[]="[APP_RDPPROXY_AUTHHOOK]";
	$l[]="service_name=APP_RDPPROXY_AUTHHOOK";
	$l[]="master_version=".rdpproxy_version();
	$l[]="service_cmd=/etc/init.d/rdpproxy-authhook";
	$l[]="service_disabled=$enabled";
	$l[]="family=proxy";
	$l[]="pid_path=";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.rdpproxy.php --authhook-start");
			shell_exec2($cmd);
				
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}else{
		if($enabled==0){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/rdpproxy-authhook stop >/dev/null 2>&1 &");
		}
	}


	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================

//========================================================================================================================================================
function dnsmasq_version($binpath=null){
	$key=md5(__FUNCTION__.$binpath);
	if(isset($GLOBALS[$key])){return $GLOBALS[$key];}
	if($binpath==null){$binpath=$GLOBALS["CLASS_UNIX"]->find_program("dnsmasq");}
	if(!is_file($binpath)){return 0;}

	exec("$binpath --version 2>&1",$array);
	while (list ($pid, $line) = each ($array) ){
		if(preg_match("#version\s+([0-9a-z\.]+)\s+Copyright#i", $line,$re)){
			$GLOBALS[$key]=$re[1];
			return $re[1];}
			if($GLOBALS['VERBOSE']){echo "dnsmasq_version(), $line, not found \n";}
	}

}
//========================================================================================================================================================



//========================================================================================================================================================
function haarp(){
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("haarp");
	if(!is_file($bin)){return;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableHaarp");
	if(!is_numeric($enabled)){$enabled=0;}
	$pid_path="/var/run/haarp.pid";
	$master_pid=trim(@file_get_contents($pid_path));


	$l[]="[APP_HAARP]";
	$l[]="service_name=APP_HAARP";
	$l[]="master_version=".haarp_version();
	$l[]="service_cmd=/etc/init.d/haarp";
	$l[]="service_disabled=$enabled";
	$l[]="family=proxy";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";

	
	
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin);
	}
	
	
	if($enabled==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			ToSyslog("Shutdown Haarp daemon EnableHaarp == 0");
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.haarp.php --stop >/dev/null 2>&1 &");
			
			
		}
		return implode("\n",$l);
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.initslapd.php --haarp >/dev/null 2>&1");
			shell_exec2($cmd);
			squid_admin_mysql(0, "HAARP not running, start it...","Bin: $bin, PID: $pid_path, master_pid:$master_pid");
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.haarp.php --start >/dev/null 2>&1 &");
			shell_exec2($cmd);
				
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}


	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.haarp.php --status >/dev/null 2>&1 &");
	shell_exec2($cmd);

	return implode("\n",$l);return;
}
//========================================================================================================================================================
function haarp_version(){
	return "1.1";
	if(isset($GLOBALS["haarp_version"])){return $GLOBALS["haarp_version"];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("haarp");
	if(!is_file($bin)){return 0;}

	exec("$bin -h 2>&1",$array);
	while (list ($pid, $line) = each ($array) ){
		if(preg_match("#Haarp Version\s+([0-9\.\-]+)#i", $line,$re)){
			$GLOBALS["haarp_version"]=$re[1];
			return $re[1];}
			if($GLOBALS['VERBOSE']){echo "haarp_version(), $line, not found \n";}
	}

}

//========================================================================================================================================================
function nginx(){
	
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("nginx");
	if(!is_file($bin)){$GLOBALS["CLASS_USERS"]->NGINX_INSTALLED=false;}
	
	$MEMORY=$GLOBALS["CLASS_UNIX"]->MEM_TOTAL_INSTALLEE();
	if($MEMORY<624288){return;}
	
	if(!$GLOBALS["CLASS_USERS"]->NGINX_INSTALLED){
		$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.nginx.php --install-nginx >/dev/null 2>&1 &");
		shell_exec2($cmd);
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}
		return null;
	}
	$enabled=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableNginx"));
	$SquidAllow80Port=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAllow80Port"));
	if($SquidAllow80Port==1){$enabled=0;}
	
	$pid_path="/var/run/nginx.pid";
	$master_pid=trim(@file_get_contents($pid_path));


	$l[]="[APP_NGINX]";
	$l[]="service_name=APP_NGINX";
	$l[]="master_version=".nginx_version();
	$l[]="service_cmd=/etc/init.d/nginx";
	$l[]="service_disabled=$enabled";
	$l[]="family=proxy";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);return;}
	
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin);
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			apache_admin_mysql(0, "Nginx Web service was stopped [action=start]", "Enabled:$enabled",__FILE__,__LINE__);
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.nginx.php --start >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}


	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	$prefixcmd="{$GLOBALS["NICE"]} {$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ";
	
	
	if(!$GLOBALS["CLASS_UNIX"]->is_socket("/var/run/nginx-authenticator.sock")){
		$cmd=trim($prefixcmd.dirname(__FILE__)."/exec.nginx.php --authenticator >/dev/null 2>&1 &");
	}
	
	$timeFile=$GLOBALS["CLASS_UNIX"]->file_time_min("/usr/share/artica-postfix/ressources/logs/web/nginx.status.acl");
	if($timeFile>5){
		$cmd=trim($prefixcmd.dirname(__FILE__)."/exec.nginx.php --status >/dev/null 2>&1 &");
		shell_exec2($cmd);	
	}
	
	$timeFile=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.nginx.php.test_sources.time");
	if($timeFile>15){
		$cmd=trim($prefixcmd.dirname(__FILE__)."/exec.nginx.php --tests-sources >/dev/null 2>&1 &");
		shell_exec2($cmd);
	}
	
	
	$timeFile=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.nginx.php.parse_memory.time");
	if($timeFile>4){
		$cmd=trim($prefixcmd.dirname(__FILE__)."/exec.nginx.php --mem >/dev/null 2>&1 &");
		shell_exec2($cmd);
	}
	
	$timeFile=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.nginx.wizard.php.check_all_websites_status_available.time");
	if($timeFile>15){
		$cmd=trim($prefixcmd.dirname(__FILE__)."/exec.nginx.wizard.php --avail-status >/dev/null 2>&1 &");
		shell_exec2($cmd);
	}

	if(!system_is_overloaded(basename(__FILE__))){
		$timeFile=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squid.interface-size.php.time");
		if($timeFile>14){
			$cmd=trim($prefixcmd.dirname(__FILE__)."/exec.squid.interface-size.php >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
	}else{
		apache_admin_mysql(2, "Overloaded system:{$GLOBALS["SYSTEM_INTERNAL_LOAD"]} aborting dashboard status", null,__FILE__,__LINE__);
	}
	
	
	return implode("\n",$l);return;
}
//========================================================================================================================================================
function nginx_version(){
	if(isset($GLOBALS["nginx_version"])){return $GLOBALS["nginx_version"];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("nginx");
	if(!is_file($bin)){return 0;}
	
	exec("$bin -v 2>&1",$array);
	while (list ($pid, $line) = each ($array) ){
			if(preg_match("#\/([0-9\.\-]+)#i", $line,$re)){
				$GLOBALS["nginx_version"]=$re[1];
				return $re[1];}
			if($GLOBALS['VERBOSE']){echo "nginx_version(), $line, not found \n";}
		}	
	
}
//========================================================================================================================================================
function syslog_db(){
	
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSyslogDB");
	if(!is_numeric($enabled)){$enabled=0;}
	if($enabled==0){
		if($GLOBALS["VERBOSE"]){echo "Failed: EnableSyslogDB = $enabled\n";}
		return;}
	$MySQLSyslogType=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLSyslogType");
	if(!is_numeric($MySQLSyslogType)){$MySQLSyslogType=1;}
	if($GLOBALS["VERBOSE"]){echo "MySQLSyslogType  = $MySQLSyslogType\n";}
	if($MySQLSyslogType<>1){return;}
	
	$pid_path="/var/run/syslogdb.pid";
	$master_pid=trim(@file_get_contents($pid_path));


	$l[]="[APP_SYSLOG_DB]";
	$l[]="service_name=APP_SYSLOG_DB";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->MYSQL_VERSION();
	$l[]="service_cmd=/etc/init.d/syslog-db";
	$l[]="service_disabled=$enabled";
	$l[]="family=proxy";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$unix=new unix();
		$mysqld=$unix->LOCATE_mysqld_bin();
		$master_pid=$unix->PIDOF_PATTERN("$mysqld.*?syslogdb.sock");
	}
		
		
		
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){	
		if($GLOBALS["VERBOSE"]){echo "Not running !!!\n";}
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.logs-db.php --init");
			shell_exec2($cmd);
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/syslog-db restart >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}
	
	
	if(!$GLOBALS["CLASS_UNIX"]->is_socket("/var/run/syslogdb.sock")){
		if($GLOBALS["VERBOSE"]){echo "/var/run/syslogdb.sock no such socket !!!\n";}
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.logs-db.php --init");
			shell_exec2($cmd);
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/syslog-db restart >/dev/null 2>&1 &");
		}
	}else{
		if($GLOBALS["VERBOSE"]){echo "/var/run/syslogdb.sock socket OK !!!\n";}
	}


	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.logs-db.php --databasesize >/dev/null 2>&1 &");

	return implode("\n",$l);return;
}
//========================================================================================================================================================
function nginx_db(){

	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginxStats");
	if(!is_numeric($enabled)){$enabled=0;}
	if($enabled==0){return;}
	$MySQLSyslogType=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLNgnixType");
	if(!is_numeric($MySQLSyslogType)){$MySQLSyslogType=1;}
	if($MySQLSyslogType<>1){return;}

	$pid_path="/var/run/nginxdb.pid";
	$master_pid=trim(@file_get_contents($pid_path));


	$l[]="[APP_NGINXDB]";
	$l[]="service_name=APP_NGINXDB";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->MYSQL_VERSION();
	$l[]="service_cmd=/etc/init.d/nginx-db";
	$l[]="service_disabled=$enabled";
	$l[]="family=proxy";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.nginx-db.php --init");
			shell_exec2($cmd);
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/nginx-db restart >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}


	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.nginx-db.php --databasesize >/dev/null 2>&1 &");
	return implode("\n",$l);return;
}








function zarafa_watchdog(){
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaEnableServer");
	if(!is_numeric($enabled)){$enabled=1;}
	
	if($enabled==0){return;}
	$pid_path="/var/run/zarafa-server.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	$text[]="This is watchdog the report for Zarafa server ";
	$text[]="Pid: $master_pid";


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$text[]="Running No : -> running watchdog";
		$text[]="Process report: ".zarafa_server();
	}else{
		$text[]="Running Yes :";
	}

	$text[]="Mysql server status:\n---------------\n".mysql_server();

	$GLOBALS["CLASS_UNIX"]->send_email_events("Zarafa watchdog report",@implode("\n",$text),"mailbox");

}

function freeradius_version(){
	
	$freeradius=$GLOBALS["CLASS_UNIX"]->find_program("freeradius");
	exec("$freeradius -v 2>&1",$results);
	while (list ($dir, $val) = each ($results) ){
		if(!preg_match("#Version ([0-9\.]+)#", $val,$re)){continue;}
		return $re[1];
	}

}

function freeradius(){

	if(!$GLOBALS["CLASS_USERS"]->FREERADIUS_INSTALLED){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}return null;}

	$enabled=1;
	$pid_path="/var/run/freeradius/freeradius.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	$freeradius=$GLOBALS["CLASS_UNIX"]->find_program("freeradius");
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFreeRadius");
	if(!is_numeric($enabled)){$enabled=0;}

	$l[]="[APP_FREERADIUS]";
	$l[]="service_name=APP_FREERADIUS";
	$l[]="master_version=".freeradius_version();
	$l[]="service_cmd=freeradius";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=system";

	if($enabled==0){return implode("\n",$l);return;}
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($freeradius);
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_FREERADIUS","freeradius");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;



}

function zarafa_server2(){

	if(!$GLOBALS["CLASS_USERS"]->ZARAFA_INSTALLED){if($GLOBALS["VERBOSE"]){echo __FUNCTION__." not installed\n";}return null;}

	$enabled=1;
	$pid_path="/var/run/zarafa-server2.pid";
	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/zarafa-server2.pid");
	

	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaDBEnable2Instance");
	if(!is_numeric($enabled)){$enabled=0;}

	$l[]="[APP_ZARAFA_SERVER2]";
	$l[]="service_name=APP_ZARAFA_SERVER2";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->ZARAFA_VERSION();
	$l[]="service_cmd=zarafa2";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="remove_cmd=--zarafa-remove";
	$l[]="watchdog_features=1";
	$l[]="family=mailbox";

	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_ZARAFA_SERVER2","zarafa2");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);return;
	}
	
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;	
	
	
	
}


function zarafa_multi(){
	if(!$GLOBALS["CLASS_USERS"]->ZARAFA_INSTALLED){return;}
	$add=null;
	if(!is_file("/usr/share/artica-postfix/exec.zarafa-multi.php")){return;}
	if($GLOBALS["DISABLE_WATCHDOG"]){$add=" --nowtachdog";}
	$EnableZarafaMulti=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableZarafaMulti");
	if(!is_numeric($EnableZarafaMulti)){$EnableZarafaMulti=0;}
	$php5=$GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN();
	$cmd="$php5 /usr/share/artica-postfix/exec.zarafa-multi.php --status$add 2>&1";
	exec($cmd,$results);
	return @implode("\n", $results);	
	
}

function vmtools_pid(){
	if(is_file("/var/run/vmware-guestd.pid")){return "/var/run/vmware-guestd.pid";}
	if(is_file("/var/run/vmtoolsd.pid")){return "/var/run/vmtoolsd.pid";}
	
}

function vmtools_version_text(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	if(!is_file("/etc/vmware-tools/manifest.txt.shipped")){return;}
	$f=file("/etc/vmware-tools/manifest.txt.shipped");
	while (list ($i, $line) = each ($f)){
		if(preg_match("#guestd\.version.+?([0-9\.]+)#",$line,$re)){
			$GLOBALS[__FUNCTION__]=$re[1];
			return $GLOBALS[__FUNCTION__];
		}
	}
	if(is_file("/usr/bin/vmware-toolbox-cmd")){	
		exec("/usr/bin/vmware-toolbox-cmd -v 2>&1",$results);
		$GLOBALS[__FUNCTION__]=trim(@implode("", $results));
		if(preg_match("#(.+?)\s+#", $GLOBALS[__FUNCTION__],$re)){$GLOBALS[__FUNCTION__]=$re[1];}
		return $GLOBALS[__FUNCTION__];
	}
	
}

function vmtools_init(){
	
	if(is_file("/etc/init.d/vmware-tools")){return "/etc/init.d/vmware-tools";}
	if(is_file("/etc/init.d/open-vm-tools")){return "/etc/init.d/open-vm-tools";}
}

function vmtools(){
	$binpath=_vmtools_bin_path();
	if($binpath==null){
		if($GLOBALS["VERBOSE"]){echo "Not Installed\n";}
		
		return null;}
	$enabled=1;
	$pid_path=vmtools_pid();
	$master_pid=trim(@file_get_contents($pid_path));
	if($master_pid==null){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
	}

	$vmtools_init=vmtools_init();
	$l[]="[APP_VMTOOLS]";
	$l[]="service_name=APP_VMTOOLS";
	$l[]="master_version=".vmtools_version_text();
	$l[]="service_cmd=$vmtools_init";
	$l[]="service_disabled=$enabled";

	$l[]="family=system";
	$l[]="pid_path=$pid_path";
	$l[]="binpath=$binpath";
	//$l[]="remove_cmd=--zarafa-remove";

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		shell_exec2("{$GLOBALS["nohup"]} $vmtools_init start >/dev/null 2>&1 &");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}

function _vmtools_bin_path(){
	if(is_file("/usr/sbin/vmtoolsd")){return "/usr/sbin/vmtoolsd";}
	if(is_file("/usr/bin/vmtoolsd")){return "/usr/bin/vmtoolsd";}
	if(is_file("/usr/sbin/vmware-guestd")){return "/usr/sbin/vmware-guestd";}
	if(is_file("/usr/lib/vmware-tools/bin32/vmware-user-loader")){return "/usr/lib/vmware-tools/bin32/vmware-user-loader";}
}

//========================================================================================================================================================
function hamachi_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	exec("/usr/bin/hamachi 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#version.+?([0-9\.]+)#", $ligne,$re)){
			$GLOBALS[__FUNCTION__]=$re[1];
			return $GLOBALS[__FUNCTION__];
		}
	}
}

//========================================================================================================================================================


function hamachi(){
	if(!is_file("/opt/logmein-hamachi/bin/hamachid")){return null;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableHamachi");
	if(!is_numeric($enabled)){$enabled=1;}
	$pid_path="/var/run/logmein-hamachi/hamachid.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF("/opt/logmein-hamachi/bin/hamachid");
	}

	$l[]="[APP_AMACHI]";
	$l[]="service_name=APP_AMACHI";
	$l[]="master_version=".hamachi_version();
	$l[]="service_cmd=amachi";
	$l[]="family=network";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";

	if($enabled==0){return implode("\n",$l);return;}
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_HAMACHI","hamachi");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}	
	
	
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================
function ejabberd_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program("ejabberdctl");
	exec("$binpath status 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#ejabberd\s+([0-9\.]+)\s+#", $ligne,$re)){
			$GLOBALS[__FUNCTION__]=$re[1];
			return $GLOBALS[__FUNCTION__];
		}
	}
}
function ejabberd_bin(){
	
	if(is_file("/usr/lib/erlang/erts-5.8/bin/beam")){return "/usr/lib/erlang/erts-5.8/bin/beam";}
	
}
//========================================================================================================================================================
function ejabberd(){
	if(!$GLOBALS["CLASS_USERS"]->EJABBERD_INSTALLED){return null;}
	
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ejabberdEnabled");
	if(!is_numeric($enabled)){$enabled=1;}
	$pid_path="/var/run/ejabberd/ejabberd.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$binpath=ejabberd_bin();
		if($binpath<>null){
			$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
		}
	}
	$version=ejabberd_version();
	@file_put_contents("/etc/artica-postfix/ejabberd_version", $version);
	$l[]="[APP_EJABBERD]";
	$l[]="service_name=APP_EJABBERD";
	$l[]="master_version=$version";
	$l[]="service_cmd=ejabberd";
	$l[]="family=network";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";

	if($enabled==0){return implode("\n",$l);return;}
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_EJABBERD","ejabberd");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}	else{
		if($enabled==0){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/artica-postfix stop ejabberd >/dev/null 2>&1 &");
		}
	}
	
	
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

	
}
//========================================================================================================================================================
function pymsnt_pgrep(){
	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
	exec("$pgrep -l -f \"/usr/share/pymsnt/PyMSNt.py\" 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#pgrep#", $ligne)){continue;}
		if(preg_match("#^([0-9]+)#", $ligne,$re)){return $re[1];}
	}
	
}
//========================================================================================================================================================
function pymsnt_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$binpath="/usr/share/pymsnt/src/legacy/glue.py";
	if(!is_file($binpath)){return "0.0";}
	$results=file($binpath);

	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#version.*?=.*?([0-9\.]+)#", $ligne,$re)){
			$GLOBALS[__FUNCTION__]=$re[1];
			return $GLOBALS[__FUNCTION__];
		}
	}
}
//========================================================================================================================================================
function pymsnt(){
	if(!$GLOBALS["CLASS_USERS"]->EJABBERD_INSTALLED){return null;}
	if(!$GLOBALS["CLASS_USERS"]->PYMSNT_INSTALLED){return null;}
	
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ejabberdEnabled");
	if(!is_numeric($enabled)){$enabled=1;}
	$pid_path="/var/run/pymsnt/pymsnt.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$master_pid=pymsnt_pgrep();}
	
	
	$version=pymsnt_version();
	@file_put_contents("/etc/artica-postfix/pymsnt_version", $version);
	$l[]="[APP_PYMSNT]";
	$l[]="service_name=APP_PYMSNT";
	$l[]="master_version=$version";
	$l[]="service_cmd=pymsnt";
	$l[]="family=network";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";

	if($enabled==0){return implode("\n",$l);return;}
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_PYMSNT","pymsnt");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}else{
		if($enabled==0){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/artica-postfix stop pymsnt >/dev/null 2>&1 &");
		}
	}	
	
	
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

	
}
//========================================================================================================================================================



function artica_notifier(){


	$binpath=$GLOBALS["CLASS_UNIX"]->find_program('emailrelay');
	if($binpath==null){return;}


	$l[]="[APP_ARTICA_NOTIFIER]";
	$l[]="service_name=APP_ARTICA_NOTIFIER";
	$l[]="service_cmd=artica-notifier";
	$l[]="master_version=".GetVersionOf("emailrelay");

	if(!is_file("/etc/artica-postfix/smtpnotif.conf")){
		$l[]="service_disabled=0";
		return implode("\n",$l);
		return;
	}

	$ini=new Bs_IniHandler("/etc/artica-postfix/smtpnotif.conf");
	if($ini->_params["SMTP"]["enabled"]<>1){
		$l[]="service_disabled=0";
		return implode("\n",$l);
		return;
	}

	$l[]="service_disabled=1";
	$pid_path="/var/run/artica-notifier.pid";
	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);
	$l[]="service_cmd=artica-notifier";
	$l[]="service_disabled=1";
	$l[]="family=system";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_ARTICA_NOTIFIER","artica-notifier");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	//$l[]="remove_cmd=--zarafa-remove";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);return;
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}
//========================================================================================================================================================

function autofs(){
	$pid_path=null;
	$master_pid=null;
	if(!$GLOBALS["CLASS_USERS"]->autofs_installed){
		if($GLOBALS["VERBOSE"]){echo "autofs_installed FALSE\n";}
		return;
	}
	if(!is_file('/etc/init.d/autofs')){
		if($GLOBALS["VERBOSE"]){echo "/etc/init.d/autofs no such file.\n";}
		return;
	}
	
	if(!is_dir("/automounts")){
		mkdir_test("/automounts",0755,true);
		shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/artica-postfix restart autofs >/dev/null 2>&1 &");
	}
	
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program('automount');
	if($binpath==null){
		if($GLOBALS["VERBOSE"]){echo "automount no such binary.\n";}
		return;
	}
	if(is_file("/var/run/autofs-running")){$pid_path="/var/run/autofs-running";}
	if($pid_path==null){if(is_file("/var/run/automount.pid")){$pid_path="/var/run/automount.pid";}}

	$Enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("AutoFSEnabled");
	if(!is_numeric($Enabled)){$Enabled=1;}
	$AutoFSCountDirs=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("AutoFSCountDirs");
	if(!is_numeric($AutoFSCountDirs)){$AutoFSCountDirs=0;}
	if($AutoFSCountDirs==0){$Enabled=0;}
	
	$SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
	$EnableIntelCeleron=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableIntelCeleron"));
	
	
	if($SquidPerformance>2){$Enabled=0;}
	if($EnableIntelCeleron==1){$Enabled=0;}

	if($pid_path<>null){$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);}
	if(!is_numeric($master_pid)){$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($binpath);}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
	}
	$l[]="[APP_AUTOFS]";
	$l[]="service_name=APP_AUTOFS";
	$l[]="service_cmd=autofs";
	$l[]="master_version=".GetVersionOf("autofs");
	$l[]="service_disabled=$Enabled";
	$l[]="family=network";
	$l[]="watchdog_features=1";

	if($Enabled==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/autofs stop >/dev/null 2>&1 &");
		}
		return implode("\n",$l);}
	
	

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_AUTOFS","autofs");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}




function greyhole(){

	if(!$GLOBALS["CLASS_USERS"]->GREYHOLE_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo "GREYHOLE_INSTALLED FALSE\n";}
		return;
	}

	$EnableGreyhole=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGreyhole");
	if(!is_numeric($EnableGreyhole)){$EnableGreyhole=1;}

	$binpath=$GLOBALS["CLASS_UNIX"]->find_program('greyhole');
	if($binpath==null){
		if($GLOBALS["VERBOSE"]){echo "automount no such binary.\n";}
		return;
	}
	if(is_file("/var/run/greyhole.pid")){$pid_path="/var/run/greyhole.pid";}



	if($pid_path<>null){$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);}
	if(!is_numeric($master_pid)){$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN($binpath);}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
	}

	if(!is_file("/etc/greyhole.conf")){$EnableGreyhole=0;}

	$l[]="[APP_GREYHOLE]";
	$l[]="service_name=APP_GREYHOLE";
	$l[]="service_cmd=greyhole";
	$l[]="master_version=".GetVersionOf("greyhole");
	$l[]="service_disabled=$EnableGreyhole";
	$l[]="family=network";
	$l[]="watchdog_features=1";
	if($EnableGreyhole==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_GREYHOLE","greyhole");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}


function greyhole_watchdog(){

	$greyhole=$GLOBALS["CLASS_UNIX"]->find_program('greyhole');
	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program('pgrep');
	if(!is_file($greyhole)){
		events("greyhole is not installed",__FUNCTION__,__LINE__);
		return;
	}
	$kill=$GLOBALS["CLASS_UNIX"]->find_program('kill');
	events("$pgrep -l -f \"$greyhole --fsck\" 2>&1",__FUNCTION__,__LINE__);
	exec("$pgrep -l -f \"$greyhole --fsck\"",$results);
	if(count($results)==0){return;}
	while (list ($key, $value) = each ($results) ){
		events("$value",__FUNCTION__,__LINE__);
		if(!preg_match("#^([0-9]+)\s+#",$value,$re)){continue;}
		$pid=$re[1];
		if($GLOBALS["CLASS_UNIX"]->PID_IS_CHROOTED($pid)){continue;}
		$time=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($pid);
		events("Found pid $pid, $time minutes",__FUNCTION__,__LINE__);
		if(!is_file("/etc/greyhole.conf")){
			events("/etc/greyhole.conf no such file, kill process",__FUNCTION__,__LINE__);
			$GLOBALS["CLASS_UNIX"]->KILL_PROCESS($pid,9);
			
			continue;
		}
		if($time>120){
			events("killing PID $pid",__FUNCTION__,__LINE__);
			$GLOBALS["CLASS_UNIX"]->KILL_PROCESS($pid,9);
			$GLOBALS["CLASS_UNIX"]->send_email_events("greyhole process $pid was killed after {$time}Mn execution",
			"It reach max execution time : 120Mn ","system"
			);
		}

	}
}

function snort(){
	if(!$GLOBALS["CLASS_USERS"]->SNORT_INSTALLED){if($GLOBALS["VERBOSE"]){echo "SNORT_INSTALLED FALSE\n";}return;}

	$EnableSnort=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSnort");
	if($GLOBALS["VERBOSE"]){echo "EnableSnort = $EnableSnort\n";}
	if(!is_numeric($EnableSnort)){$EnableSnort=0;}
	$snortInterfaces=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SnortNics")));
	if(count($snortInterfaces)==0){$EnableSnort=0;}

	$binpath=$GLOBALS["CLASS_UNIX"]->find_program('snort');
	if($binpath==null){if($GLOBALS["VERBOSE"]){echo "snort no such binary.\n";}return;}
	if($GLOBALS["VERBOSE"]){echo "EnableSnort = $EnableSnort\n";}	
	
	
	if($EnableSnort==0){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cz=0;
			$kill=$GLOBALS["CLASS_UNIX"]->find_program('kill');
			if($GLOBALS["VERBOSE"]){echo "$binpath = PID?\n";}
			$pid=$GLOBALS["CLASS_UNIX"]->PIDOF($binpath,true);
			while ($pid>50) {
				$cz++;
				system_admin_events("Snort pid $pid was killed, it is not enabled", __FUNCTION__, __FILE__, __LINE__, "watchdog");
				unix_system_kill_force($pid);
				$pid=$GLOBALS["CLASS_UNIX"]->PIDOF($binpath,true);
				if($cz>10){system_admin_events("Break loop after 10 attempts...", __FUNCTION__, __FILE__, __LINE__, "watchdog");break;}
				sleep(1);
			}
		}
		
		$l[]="[APP_SNORT]";
		$l[]="service_name=APP_SNORT";
		$l[]="service_cmd=snort";
		$l[]="master_version="._snort_version();
		$l[]="service_disabled=$EnableSnort";
		$l[]="family=network";
		$l[]="watchdog_features=1";
		return implode("\n",$l);
	}



	while (list ($eth, $ligne) = each ($snortInterfaces) ){

		$l[]="[APP_SNORT:$eth]";
		$l[]="service_name=APP_SNORT";
		$l[]="service_cmd=snort";
		$l[]="master_version="._snort_version();
		$l[]="service_disabled=$EnableSnort";
		$l[]="family=network";
		$l[]="watchdog_features=1";


		$pidpath="/var/run/snort_$eth.pid";
		$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pidpath);
		if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			WATCHDOG("APP_SNORT (Nic:$eth)","snort");
			$l[]="running=0\ninstalled=1";$l[]="";
		}else{
			$l[]="running=1";
			$l[]=GetMemoriesOf($master_pid);
			$l[]="";
				
		}
	}

	return implode("\n",$l);return;


}

function _snort_pid(){
	if(is_file("/var/run/snort_eth0.pid")){return "/var/run/snort_eth0.pid";}
}
function _snort_version(){
	if(!isset($GLOBALS["SNORT_PATH"])){$GLOBALS["CLASS_UNIX"]=new unix();$GLOBALS["SNORT_PATH"]=$GLOBALS["CLASS_UNIX"]->find_program("snort");}
	exec("{$GLOBALS["SNORT_PATH"]} -V 2>&1",$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#Version\s+([0-9\.]+)#",$line,$re)){return $re[1];}

	}
	return 0;
}


function snmpd_pid(){
	$pid_path="/var/run/snmpd.pid";
	$pid=trim(@file_get_contents($pid_path));
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF($GLOBALS["CLASS_UNIX"]->find_program("snmpd"));
}
function snmpd_version(){
	if(isset($GLOBALS["SNMPD_VERSION"])){return $GLOBALS["SNMPD_VERSION"];}
	$snmpd=$GLOBALS["CLASS_UNIX"]->find_program("snmpd");
	exec("$snmpd -v 2>&1",$results);
	while (list ($i, $line) = each ($results)){
		if(preg_match("#NET-SNMP version:\s+([0-9\.]+)#i", $line,$re)){
			$GLOBALS["SNMPD_VERSION"]=$re[1];
			return $re[1];
		}
	}
}
function transmission_daemon_version(){
	if(isset($GLOBALS["transmission_daemon_version"])){return $GLOBALS["transmission_daemon_version"];}
	$snmpd=$GLOBALS["CLASS_UNIX"]->find_program("transmission-daemon");
	exec("$snmpd --version 2>&1",$results);
	while (list ($i, $line) = each ($results)){
		if(preg_match("#transmission-daemon\s+([0-9\.]+)#i", $line,$re)){
			$GLOBALS["transmission_daemon_version"]=$re[1];
			return $re[1];
		}
	}
}
function transmission_daemon_pid(){
	$pid_path="/var/run/transmission-daemon/transmission-daemon.pid";
	$pid=trim(@file_get_contents($pid_path));
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF($GLOBALS["CLASS_UNIX"]->find_program("transmission-daemon"));
}
function transmission_daemon(){
	$snmpd=$GLOBALS["CLASS_UNIX"]->find_program("transmission-daemon");
	if(!is_file($snmpd)){return;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableTransMissionDaemon");
	if(!is_numeric($enabled)){$enabled=0;}
	$pid_path="/var/run/transmission-daemon/transmission-daemon.pid";
	$master_pid=transmission_daemon_pid();

	$l[]="[bittorrent_service]";
	$l[]="service_name=bittorrent_service";
	$l[]="master_version=".transmission_daemon_version();
	$l[]="service_cmd=/etc/init.d/transmission-daemon";
	$l[]="service_disabled=$enabled";
	$l[]="family=system";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["nohup"]} /etc/init.d/transmission-daemon start >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}else{
		if($enabled==0){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/transmission-daemon stop >/dev/null 2>&1 &");
		}
	}


	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}




function snmpd(){
	
	
	if(!extension_loaded('snmp')){
		$GLOBALS["CLASS_UNIX"]->ToSyslog("SNMP PHP library not loaded...");
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.snmp.install.php >/dev/null 2>&1 &");
	}
	
	if(!extension_loaded('pgsql')){
	
		$GLOBALS["CLASS_UNIX"]->ToSyslog("PGSQL PHP library not loaded...");
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.snmp.install.php --pgsql >/dev/null 2>&1 &");
	}
	
	
	$snmpd=$GLOBALS["CLASS_UNIX"]->find_program("snmpd");
	if(!is_file($snmpd)){return;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSNMPD");
	if(!is_numeric($enabled)){$enabled=0;}
	$pid_path="/var/run/snmpd.pid";
	$master_pid=snmpd_pid();

	$l[]="[APP_SNMPD]";
	$l[]="service_name=APP_SNMPD";
	$l[]="master_version=".snmpd_version();
	$l[]="service_cmd=/etc/init.d/snmpd";
	$l[]="service_disabled=$enabled";
	$l[]="family=system";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.snmpd.php --start");
			shell_exec2($cmd);
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}else{
		if($enabled==0){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/snmpd stop >/dev/null 2>&1 &");
		}
	}


	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}


function vsftpd_pid(){

	
	$Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("vsftpd");
	$pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("^vsftpd$");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN($Masterbin);

}

function l7filter_pid(){
	$Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("l7-filter");
	return $GLOBALS["CLASS_UNIX"]->PIDOF($Masterbin);

}

function l7filter_version(){

	if(isset($GLOBALS["l7filter_version"])){return $GLOBALS["l7filter_version"];}
	$Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("l7-filter");
	exec("$Masterbin -h 2>&1",$results);
	while (list ($none, $line) = each ($results)){
		if(preg_match("#l7-filter v([0-9\.]+)#", $line,$re)){
			$GLOBALS["l7filter_version"]= $re[1];
			break;
		}

	}
	return $GLOBALS["l7filter_version"];
}

function l7filter(){
	$snmpd=$GLOBALS["CLASS_UNIX"]->find_program("l7-filter");
	if(!is_file($snmpd)){
		$l[]="";
		$l[]="[APP_l7FILTER]";
		$l[]="service_name=APP_l7FILTER";
		$l[]="running=0\ninstalled=0";$l[]="";
		return @implode("\n",$l);
	}

	$enabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableL7Filter"));
	$master_pid=l7filter_pid();
	$l[]="";
	$l[]="[APP_l7FILTER]";
	$l[]="service_name=APP_l7FILTER";
	$l[]="master_version=".l7filter_version();
	$l[]="service_cmd=/etc/init.d/l7filter";
	$l[]="service_disabled=$enabled";
	$l[]="family=system";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.l7filter.php --start");
			shell_exec2($cmd);
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
	}else{
		if($enabled==0){
		shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/l7filter stop >/dev/null 2>&1 &");
		}
	}


	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
	}



function vsftpd(){
	$snmpd=$GLOBALS["CLASS_UNIX"]->find_program("vsftpd");
	if(!is_file($snmpd)){return;}
	$enabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableVSFTPDDaemon");
	if(!is_numeric($enabled)){$enabled=0;}
	
$master_pid=vsftpd_pid();
	$l[]="[APP_VSFTPD]";
	$l[]="service_name=APP_VSFTPD";
	$l[]="master_version=2.3.5";
	$l[]="service_cmd=/etc/init.d/vsftpd";
	$l[]="service_disabled=$enabled";
	$l[]="family=system";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			vsftpd_admin_mysql(0,"Starting VSFTPD service [not running]",null,__FILE__,__LINE__);
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.vsftpd.php --start");
			shell_exec2($cmd);
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}else{
		if($enabled==0){
			vsftpd_admin_mysql(0,"Stopping VSFTPD service EnableVSFTPDDaemon = 0",null,__FILE__,__LINE__);
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/vsftpd stop >/dev/null 2>&1 &");
		}
	}


	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;
}






function dnsmasq(){
	
	if(!$GLOBALS["CLASS_USERS"]->dnsmasq_installed){
		if($GLOBALS["VERBOSE"]){echo "dnsmasq_installed FALSE\n";}
		return;
	}
	
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program('dnsmasq');
	if($binpath==null){if($GLOBALS["VERBOSE"]){echo "dnsmasq no such binary.\n";}return;}	
	$EnableDNSMASQ=$GLOBALS["CLASS_SOCKETS"]->dnsmasq_enabled();
	$NoStopBind9=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NoStopBind9");
	
	if(!is_numeric($NoStopBind9)){$NoStopBind9=0;}

	

	if($GLOBALS["CLASS_USERS"]->BIND9_INSTALLED){
		if($NoStopBind9==0){
			$namedbin=$GLOBALS["CLASS_UNIX"]->find_program("named");
			$namedpid=$GLOBALS["CLASS_UNIX"]->PIDOF("$namedbin");
			
			if($GLOBALS["CLASS_UNIX"]->process_exists($namedpid)){
				$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
				$kill=$GLOBALS["CLASS_UNIX"]->find_program("kill");
				$GLOBALS["CLASS_UNIX"]->KILL_PROCESS($namedpid,9);
				$cmd="$nohup {$GLOBALS["NICE"]}/etc/init.d/dnsmasq restart >/dev/null 2>&1 &";
				shell_exec2($cmd);
				$GLOBALS["CLASS_UNIX"]->send_email_events("Stopping bind9 Pid $namedpid","Artica has stopped bind9 process\nthis to prevent port conflicts with DnsMasq or PowerDNS.\nIf you did not want to Artica perform this operation do this operation:\necho \"1\" >/etc/artica-postfix/settings/Daemons/NoStopBind9\n","system");
			}
		}
	}

	$master_pid=_dnsmasq_pid();
	
	


	$l[]="[DNSMASQ]";
	$l[]="service_name=APP_DNSMASQ";
	$l[]="service_cmd=/etc/init.d/dnsmasq";
	$l[]="master_version=".dnsmasq_version();
	$l[]="service_disabled=$EnableDNSMASQ";
	$l[]="family=network";
	$l[]="watchdog_features=1";
	if($EnableDNSMASQ==0){return implode("\n",$l);return;}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
			shell_exec2("$nohup {$GLOBALS["NICE"]} /etc/init.d/dnsmasq start >/dev/null 2>&1 &");
				
		}

		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.dnsmasq.php --varrun >/dev/null 2>&1 &");
	return implode("\n",$l);return;

}

function _dnsmasq_pid(){
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/dnsmasq.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	$Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("dnsmasq");
	return $GLOBALS["CLASS_UNIX"]->PIDOF($Masterbin);

}

function dhcpd_version() {
	if(isset($GLOBALS["DHCPD_VERSION"])){return $GLOBALS["DHCPD_VERSION"];}
	if($GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/DHCPD_VER")<60){
		return trim(@file_get_contents("/etc/artica-postfix/DHCPD_VER"));
	}
	
	$dhcpd_server=$GLOBALS["CLASS_UNIX"]->find_program("dhcpd");
	if(!is_file($dhcpd_server)){$dhcpd_server=$GLOBALS["CLASS_UNIX"]->find_program("dhcpd3");}
	
	exec("$dhcpd_server -V 2>&1",$results);
	
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#DHCP Server\s+([0-9\.])#", $ligne,$re)){
			$version=$re[1];
			@file_put_contents("/etc/artica-postfix/DHCPD_VER", $version);
			$GLOBALS["DHCPD_VERSION"]=$re[1];
			return $GLOBALS["DHCPD_VERSION"];
		}
	
	}
	
}


function dhcpd_server(){
	if(!$GLOBALS["CLASS_USERS"]->dhcp_installed){
		$l[]="";
		$l[]="[DHCPD]";
		$l[]="service_name=APP_DHCP";
		$l[]="running=0\ninstalled=0";$l[]="";
		return @implode("\n",$l);}
	$EnableDHCPServer=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDHCPServer");
	if(!is_numeric($EnableDHCPServer)){$EnableDHCPServer=0;}
	if($EnableDHCPServer==null){$EnableDHCPServer=0;}
	$pid_path=$GLOBALS["CLASS_UNIX"]->LOCATE_DHCPD_PID_PATH();
	$EnableChilli=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableChilli");
	if(!is_numeric($EnableChilli)){$EnableChilli=0;}
	if($EnableChilli==1){$EnableDHCPServer=0;}
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program("dhcpd");
	if(!is_file($binpath)){$binpath=$GLOBALS["CLASS_UNIX"]->find_program("dhcpd3");}
	$l[]="";
	$l[]="[DHCPD]";
	$l[]="service_name=APP_DHCP";
	$l[]="service_cmd=/etc/init.d/isc-dhcp-server";
	$l[]="master_version=".dhcpd_version();
	$l[]="service_disabled=$EnableDHCPServer";
	$l[]="family=network";
	$l[]="watchdog_features=1";
	if($EnableDHCPServer==0){$l[]="";return implode("\n",$l);return;}
	
	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);
	$l[]="watchdog_features=1";
	
	
	if($GLOBALS["VERBOSE"]){echo "PID PATH: $pid_path\n";}
	if($GLOBALS["VERBOSE"]){echo "BIN PATH: $binpath\n";}
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(is_file($binpath)){
			$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF("$binpath");
		
		}
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
			shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.initslapd.php --dhcpd >/dev/null 2>&1 &");
			shell_exec2("$nohup {$GLOBALS["NICE"]} /etc/init.d/isc-dhcp-server start >/dev/null 2>&1 &");
			
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);return;
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	
	$timefile="/etc/artica-postfix/dhcpd.leases.dmp";
	$exTime=$GLOBALS["CLASS_UNIX"]->file_time_min($timefile);
	if($exTime>30){
		$cmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.dhcpd-leases.php >/dev/null 2>&1 &";
		shell_exec2($cmd);

	}	
	
	$timefile="/etc/artica-postfix/pids/exec.dhcpd-leases.php.parseLeases.time";
	$exTime=$GLOBALS["CLASS_UNIX"]->file_time_min($timefile);
	if($exTime>5){
		$cmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.dhcpd-leases.php --parse-leases >/dev/null 2>&1 &";
		shell_exec2($cmd);
	
	}
	
	
	
	return implode("\n",$l);return;

}

function ntpd_version(){
	if(isset($GLOBALS["NTPDVERSION"])){return $GLOBALS["NTPDVERSION"];}
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("ntpd");
	exec("$bin_path -v 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#Ver\.\s+([0-9\.a-z]+)#", $ligne,$re)){
			$GLOBALS["NTPDVERSION"]=$re[1];
			return $re[1];
		}
	}
}

function ntpd_server(){
	if(!$GLOBALS["CLASS_USERS"]->NTPD_INSTALLED){
		
		return;}
	$NTPDEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDEnabled");
	if(!is_numeric($NTPDEnabled)){$NTPDEnabled=0;}
	$pid_path="/var/run/ntpd.pid";
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("ntpd");

	$l[]="[NTPD]";
	$l[]="service_name=APP_NTPD";
	$l[]="service_cmd=dhcp";
	$l[]="master_version=".ntpd_version();
	$l[]="service_disabled=$NTPDEnabled";
	$l[]="family=network";
	$l[]="watchdog_features=1";
	if($NTPDEnabled==0){$l[]="";return implode("\n",$l);return;}

	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin_path);
	}

	$l[]="watchdog_features=1";

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_NTPD","ntpd");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}




function vnstat(){
	if(!$GLOBALS["CLASS_USERS"]->APP_VNSTAT_INSTALLED){return;}
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("vnstatd");
	if(!is_file($bin_path)){return;}
	$EnableVnStat=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableVnStat");
	if(!is_numeric($EnableVnStat)){$EnableVnStat=0;}
	if($GLOBALS["VERBOSE"]){echo "EnableVnStat = $EnableVnStat\n";}
	
	if($GLOBALS["CLASS_USERS"]->LIGHT_INSTALL){
		if($EnableVnStat==1){
			$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableVnStat",0);
			$EnableVnStat=0;
		}
	}
	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);
	
	$pid_path="/var/run/vnstat.pid";

	$l[]="[APP_VNSTAT]";
	$l[]="service_name=APP_VNSTAT";
	$l[]="service_cmd=vnstat";
	$l[]="master_version=".GetVersionOf("vnstat");
	$l[]="service_disabled=$EnableVnStat";
	//$l[]="remove_cmd=--pureftpd-remove";
	$l[]="family=network";
	$l[]="watchdog_features=1";
	
	
	if($EnableVnStat==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			shell_exec2("{$GLOBALS["KILLBIN"]} -9 $master_pid >/dev/null 2>&1");
		}
	
		return implode("\n",$l);
	}
	

	

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin_path);
	}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_VNSTAT","vnstat");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}
function munin(){
	if(!$GLOBALS["CLASS_USERS"]->MUNIN_CLIENT_INSTALLED){return;}
	$enabled=1;
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("munin-node");
	$MuninDisabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MuninDisabled");
	if($MuninDisabled==null){$MuninDisabled=0;}
	$pid_path="/var/run/munin/munin-node.pid";
	if($MuninDisabled==1){$enabled=0;}
	$l[]="[APP_MUNIN]";
	$l[]="service_name=APP_MUNIN";
	$l[]="service_cmd=munin";
	$l[]="master_version=".GetVersionOf("munin");
	$l[]="service_disabled=$enabled";
	$l[]="family=network";
	$l[]="watchdog_features=1";
	if($enabled==0){return implode("\n",$l);return;}

	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin_path);
	}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_MUNIN","munin");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}


function vboxguest(){
	if(!$GLOBALS["CLASS_USERS"]->APP_VBOXADDINTION_INSTALLED){return;}
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("VBoxService");
	if(!is_file($bin_path)){return;}
	$pid_path=$GLOBALS["CLASS_UNIX"]->LOCATE_VBOX_ADDITIONS_PID();

	$l[]="[APP_VBOXADDITIONS]";
	$l[]="service_name=APP_VBOXADDITIONS";
	$l[]="service_cmd=vboxguest";
	$l[]="master_version=".GetVersionOf("vboxguest");
	$l[]="service_disabled=1";
	$l[]="pid_path=$pid_path";
	//$l[]="remove_cmd=--pureftpd-remove";
	$l[]="family=system";
	$l[]="watchdog_features=1";


	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin_path);
	}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_VBOXADDITIONS","vboxguest");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;


}
//========================================================================================================================================================


function pure_ftpd(){



	if(!$GLOBALS["CLASS_USERS"]->PUREFTP_INSTALLED){return;}

	$PureFtpdEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PureFtpdEnabled");
	if($PureFtpdEnabled==null){$PureFtpdEnabled=0;}
	$pid_path=$GLOBALS["CLASS_UNIX"]->LOCATE_PURE_FTPD_PID_PATH();
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("pure-ftpd");

	$l[]="[PUREFTPD]";
	$l[]="service_name=APP_PUREFTPD";
	$l[]="service_cmd=ftp";
	$l[]="master_version=".GetVersionOf("pure-ftpd");
	$l[]="service_disabled=$PureFtpdEnabled";
	$l[]="remove_cmd=--pureftpd-remove";
	$l[]="family=storage";
	$l[]="watchdog_features=1";
	if($PureFtpdEnabled==0){return implode("\n",$l);return;}

	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);
	$l[]="watchdog_features=1";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin_path);
	}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_PUREFTPD","ftp");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}
//========================================================================================================================================================
function mldonkey(){



	if(!$GLOBALS["CLASS_USERS"]->MLDONKEY_INSTALLED){return;}

	$EnableMLDonKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMLDonKey"));
	if($EnableMLDonKey==null){$EnableMLDonKey=1;}
	$pid_path="/var/run/mlnet.pid";
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("mlnet");

	$l[]="[APP_MLDONKEY]";
	$l[]="service_name=APP_MLDONKEY";
	$l[]="service_cmd=mldonkey";
	$l[]="family=storage";
	$l[]="master_version=".GetVersionOf("mldonkey");
	$l[]="service_disabled=$EnableMLDonKey";
	//$l[]="remove_cmd=--pureftpd-remove";

	if($EnableMLDonKey==0){
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);
	$l[]="watchdog_features=1";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin_path);
	}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_MLDONKEY","mldonkey");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}
function kav4fs(){



	if(!$GLOBALS["CLASS_USERS"]->KAV4FS_INSTALLED){return null;}

	$EnableKav4FS=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKav4FS"));
	if($EnableKav4FS==null){$EnableKav4FS=1;}

	$l[]="[APP_KAV4FS]";
	$l[]="service_name=APP_KAV4FS";
	$l[]="family=system";
	$l[]="service_cmd=kav4fs";
	$l[]="master_version=".GetVersionOf("kav4fs");
	$l[]="service_disabled=$EnableKav4FS";
	$l[]="watchdog_features=1";
	//$l[]="remove_cmd=--pureftpd-remove";

	if($EnableKav4FS==0){
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$pid_path="/var/run/kav4fs/supervisor.pid";
	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);

	$l[]="watchdog_features=1";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF('kav4fs-supervisor');
	}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_KAV4FS","kav4fs");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}
//========================================================================================================================================================
function gluster_directories_number(){
	
	$f=file("/etc/artica-cluster/glusterfs-server.vol");
	$c=0;
	while (list ($index, $line) = each ($f) ){
		if(preg_match("#option directory\s+(.+)#",$line)){$c++;}
	}
	return $c;
}


function gluster(){
	if(!$GLOBALS["CLASS_USERS"]->GLUSTER_INSTALLED){return null;}
	$EnableGluster=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGluster"));
	if(!is_numeric($EnableGluster)){$EnableGluster=0;}
	
	$l[]="[GLUSTER]";
	$l[]="service_name=APP_GLUSTER";
	$l[]="service_cmd=gluster";
	$l[]="family=storage";
	$l[]="master_version=".GetVersionOf("gluster");
	$l[]="service_disabled=$EnableGluster";
	$l[]="watchdog_features=1";
	//$l[]="remove_cmd=--pureftpd-remove";

	if($EnableGluster==0){
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$pid_path="/var/run/glusterd.pid";
	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_GLUSTER","gluster");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}
//========================================================================================================================================================
function auditd(){



	if(!$GLOBALS["CLASS_USERS"]->APP_AUDITD_INSTALLED){return null;}


	$EnableAuditd=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAuditd");
	if($EnableAuditd==null){$EnableAuditd=1;}


	$l[]="[APP_AUDITD]";
	$l[]="service_name=APP_AUDITD";
	$l[]="service_cmd=auditd";
	$l[]="master_version=".GetVersionOf("auditd");
	$l[]="service_disabled=$EnableAuditd";
	$l[]="watchdog_features=1";
	$l[]="family=system";
	

	if($EnableAuditd==0){
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$pid_path="/var/run/auditd.pid";
	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_AUDITD","auditd");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}
//========================================================================================================================================================

function kav4fsavs(){



	if(!$GLOBALS["CLASS_USERS"]->KAV4FS_INSTALLED){return null;}

	$EnableKav4FS=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKav4FS"));
	if($EnableKav4FS==null){$EnableKav4FS=1;}

	$l[]="[APP_KAV4FS_AVS]";
	$l[]="service_name=APP_KAV4FS_AVS";
	$l[]="service_cmd=kav4fs";
	$l[]="master_version=".GetVersionOf("kav4fs");
	$l[]="service_disabled=$EnableKav4FS";
	$l[]="family=system";


	if($EnableKav4FS==0){
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF('/opt/kaspersky/kav4fs/libexec/avs');

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;


}


function backuppc(){



	$binpath="/usr/share/backuppc/bin/BackupPC";
	if(!is_file("/usr/share/backuppc/bin/BackupPC")){return;}
	if(is_file("/etc/artica-postfix/KASPERSKY_WEB_APPLIANCE")){return;}
	$EnableBackupPc=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableBackupPc"));
	if(!is_numeric($EnableBackupPc)){$EnableBackupPc=0;}


	$l[]="[APP_BACKUPPC]";
	$l[]="service_name=APP_BACKUPPC";
	$l[]="service_cmd=backuppc";
	$l[]="master_version=".GetVersionOf("backuppc");
	$l[]="service_disabled=$EnableBackupPc";
	$l[]="family=storage";


	if($EnableBackupPc==0){
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$pid_path="/var/run/backuppc/BackupPC.pid";
	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);

	$l[]="watchdog_features=1";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		writelogs("$master_pid, process not exists",__FUNCTION__,__FILE__,__LINE__);
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN($binpath);
		writelogs("first, process not exists pidof return $master_pid",__FUNCTION__,__FILE__,__LINE__);
	}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		WATCHDOG("APP_BACKUPPC","backuppc");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		return;
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;

}


function GetMemoriesOf($pid){

	return $GLOBALS["CLASS_UNIX"]->GetMemoriesOf($pid);

}

function CheckCallable(){
	include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
	include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
	include_once(dirname(__FILE__)."/ressources/class.status.openldap.inc");
	include_once(dirname(__FILE__)."/framework/class.status.hardware.inc");
	include_once(dirname(__FILE__)."/ressources/class.status.ftp-proxy.inc");
	include_once(dirname(__FILE__)."/ressources/class.status.firehol.inc");
	include_once(dirname(__FILE__)."/ressources/class.status.suricata.inc");
	include_once(dirname(__FILE__)."/ressources/class.status.defaults.inc");
	include_once(dirname(__FILE__)."/ressources/class.status.bandwidthd.inc");
	include_once(dirname(__FILE__)."/ressources/class.status.haproxy.inc");
	include_once(dirname(__FILE__)."/ressources/class.status.privoxy.inc");
	include_once(dirname(__FILE__)."/ressources/class.status.postgres.inc");
	include_once(dirname(__FILE__)."/ressources/class.status.sealion.inc");
	include_once(dirname(__FILE__)."/ressources/class.status.artica-meta.inc");
	include_once(dirname(__FILE__)."/ressources/class.status.openvpn.inc");
	include_once(dirname(__FILE__)."/ressources/class.status.proftpd.inc");
	include_once(dirname(__FILE__)."/ressources/class.status.watchdog.me.inc");
	
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new settings_inc();}
	if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
	
	
	$methodVariable=array($GLOBALS["CLASS_UNIX"], 'GetVersionOf');
	if(!is_callable($methodVariable, true, $callable_name)){
		ToSyslog("Loading unix class");
		$GLOBALS["CLASS_UNIX"]=new unix();
	}

	$methodVariable=array($GLOBALS["CLASS_UNIX"], 'find_program');
	if(!is_callable($methodVariable, true, $callable_name)){
		events("Loading unix class");
		$GLOBALS["CLASS_UNIX"]=new unix();
	}
	$methodVariable=array($GLOBALS["CLASS_SOCKETS"], 'GET_INFO');
	if(!is_callable($methodVariable, true, $callable_name)){
		ToSyslog("Loading socket class");
		$GLOBALS["CLASS_SOCKETS"]=new sockets();
	}


	$methodVariable=array($GLOBALS["CLASS_USERS"], 'BuildLeftMenus');
	if(!is_callable($methodVariable, true, $callable_name)){
		ToSyslog("Loading usersMenus class");
		$GLOBALS["CLASS_USERS"]=new settings_inc();
	}
	
	
	$GLOBALS["OS_SYSTEM"]=new os_system();
	$GLOBALS["MEMORY_INSTALLED"]=$GLOBALS["OS_SYSTEM"]->memory();
	if(is_file("/etc/artica-postfix/amavis.watchdog.cache")){
		$GLOBALS["AMAVIS_WATCHDOG"]=unserialize(@file_get_contents("/etc/artica-postfix/amavis.watchdog.cache"));
	}
	$GLOBALS["TIME_CLASS"]=time();
	$GLOBALS["ArticaWatchDogList"]=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWatchDogList")));
	
	


}


function meta_checks(){
	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
	$kill=$GLOBALS["CLASS_UNIX"]->find_program("kill");
	if(!is_file($pgrep)){
		events("pgrep no such file",__FUNCTION__,__LINE__);
		return;
	}
	events("$pgrep -f \"exec.artica.meta.users.php\" 2>&1",__FUNCTION__,__LINE__);
	exec("$pgrep -f \"exec.artica.meta.users.php\" 2>&1",$results);

	while (list ($index, $line) = each ($results) ){
		if(preg_match("#([0-9]+)#",$line,$re)){
			events("checking process time of {$re[1]}",__FUNCTION__,__LINE__);
			if($GLOBALS["CLASS_UNIX"]->PID_IS_CHROOTED($re[1])){continue;}
			$time=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($re[1]);
			events("checking pid {$re[1]} {$time}Mn",__FUNCTION__,__LINE__);
			if($time>30){
				events("Killing pid {$re[1]} {$time}Mn",__FUNCTION__,__LINE__);
				$GLOBALS["CLASS_UNIX"]->KILL_PROCESS($re[1],9);
				
			}
		}
	}

}


function getmem(){
	include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
	$os=new os_system();
	$GLOBALS["MEMORY_INSTALLED"]=$os->memory();
	$os=null;
	print_r($GLOBALS["MEMORY_INSTALLED"]);

}

function CheckCurl(){
	$results=array();
	$pidof=$GLOBALS["CLASS_UNIX"]->find_program("pidof");
	if($pidof==null){
		events("pidof no such file",__FUNCTION__,__LINE__);
		return;
	}
	$curl=$GLOBALS["CLASS_UNIX"]->find_program("curl");
	if($curl==null){
		events("curl binary no such file",__FUNCTION__,__LINE__);
		return;
	}

	exec("$pidof $curl 2>&1",$results);
	if(count($results)==0){
		events("no curl instance in memory",__FUNCTION__,__LINE__);
		return;
	}

	while (list ($index, $pid) = each ($results) ){
		$pid=trim($pid);
		if(!is_numeric($pid)){continue;}
		if($pid<5){continue;}
		if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){
			$time=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($pid);
			events("$curl: $pid {$time}Mn",__FUNCTION__,__LINE__);
			if($time>60){
				events("$curl: too long time for $pid, kill it",__FUNCTION__,__LINE__);
				$GLOBALS["CLASS_UNIX"]->KILL_PROCESS($pid,9);
				
			}
		}
	}

}

function GetVersionOf($name){
	if(isset($GLOBALS["GetVersionOf"][$name])){return $GLOBALS["GetVersionOf"][$name];}
	CheckCallable();
	$GLOBALS["GetVersionOf"][$name]=$GLOBALS["CLASS_UNIX"]->GetVersionOf($name);
	return $GLOBALS["GetVersionOf"][$name];
}
function events($text,$function=null,$line=0){
	if($GLOBALS["VERBOSE"]){
		echo "$function:: $text (L.$line)\n";
		return;
	}
	$filename=basename(__FILE__);
	$classunix=dirname(__FILE__)."/framework/class.unix.inc";
	if(!isset($GLOBALS["CLASS_UNIX"])){
		if(!is_file($classunix)){$classunix="/opt/artica-agent/usr/share/artica-agent/ressources/class.unix.inc";}
		include_once($classunix);
		$GLOBALS["CLASS_UNIX"]=new unix();
	}
	
	$GLOBALS["CLASS_UNIX"]->events("$filename $function:: $text (L.$line)","/var/log/artica-status.log");
}
function events_syslog($text=null){
	if($GLOBALS["VERBOSE"]){echo "$text\n";}
	if(!function_exists("syslog")){return;}
	$file="artica-watchdog";
	$LOG_SEV=LOG_INFO;
	openlog($file, LOG_PID , LOG_SYSLOG);
	syslog($LOG_SEV, $text);
	closelog();
}


function events_Loadavg($text,$function=null,$line=0){
	$filename=basename(__FILE__);
	if(!isset($GLOBALS["CLASS_UNIX"])){include_once(dirname(__FILE__)."/framework/class.unix.inc");$GLOBALS["CLASS_UNIX"]=new unix();}
	$GLOBALS["CLASS_UNIX"]->events("$filename $function:: $text (L.$line)","/var/log/artica-postfix/xLoadAvg.debug");
}


function phpmyadmin_perms(){
	if(is_file("/usr/share/artica-postfix/mysql/config.inc.php")){@chmod("/usr/share/artica-postfix/mysql/config.inc.php",0600);}
}

function ps_mem(){
	include_once(dirname(__FILE__)."/ressources/class.artica.status.bin.inc");
	$s=new periodics_status();
	$s->ps_mem();
	
}

function ifconfig_network(){
	$ifconfigs=$GLOBALS["CLASS_UNIX"]->ifconfig_all_ips();
	$DisableWatchDogNetwork=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableWatchDogNetwork");
	if(!is_numeric($DisableWatchDogNetwork)){$DisableWatchDogNetwork=0;}
	if($DisableWatchDogNetwork==1){return;}
	unset($ifconfigs["127.0.0.1"]);
	events(count($ifconfigs). " Ip addresses",__FUNCTION__,__LINE__);
	if(count($ifconfigs)==0){
		$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
		$timmin=$GLOBALS["CLASS_UNIX"]->file_time_min($timefile);
		if($timmin>10){
			$ifconfigbin=$GLOBALS["CLASS_UNIX"]->find_program("ifconfig");
			if(is_file($ifconfigbin)){
				exec("$ifconfigbin -a 2>&1",$ifconfigbinDump);	
				$GLOBALS["CLASS_UNIX"]->send_email_events("No Network detected !, rebuild network configuration","Artica has no detected network the network interface will be rebuilded\nHere it is the Network dump\n".@implode("\n", $ifconfigbinDump)."\nIf you did not want this watchdog, do the following command on this console server:\n# echo 1 >/etc/artica-postfix/settings/Daemons/DisableWatchDogNetwork\n# /etc/init.d/artica-status reload","system");
				@unlink($timefile);
				@file_put_contents($timefile, time());
				@unlink("/etc/artica-postfix/MEM_INTERFACES");
				$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ".dirname(__FILE__)."/exec.virtuals-ip.php >/dev/null 2>&1 &");
				shell_exec2($cmd);
			}		
		}
	}

}

function reboot(){
	
	$reboot=$GLOBALS["CLASS_UNIX"]->find_program("reboot");
	system_admin_events("Ask to reboot the system...\nExecuting $reboot", __FUNCTION__, __FILE__, __LINE__, "system");
	shell_exec2("$reboot");
	
	
}
function CheckNetInterfaces(){
	$Prefix=__FUNCTION__;
	$arrayTCP=$GLOBALS["CLASS_UNIX"]->NETWORK_ALL_INTERFACES(true);
	unset($arrayTCP["127.0.0.1"]);
	events_syslog("$Prefix: ". count($arrayTCP)." Interface(s)");
	if(count($arrayTCP)==0){
		events_syslog("kernel: [  Artica-Net] Start Network [artica-ifup] (".basename(__FILE__)."/".__LINE__.")" );
		events_syslog("$Prefix: Running artica-ifup in order to relink interface(s)");
		squid_admin_mysql(0, "Rebooting Network", null,__FILE__,__LINE__);
		shell_exec2("/etc/init.d/artica-ifup start --script=".basename(__FILE__)."/".__FUNCTION__);
	}

}



function shell_exec_time($cmdlineNophp5,$mintime=5){
	if(!is_numeric($mintime)){$mintime=5;}
	if($mintime<5){$mintime=5;}
	$md5=md5($cmdlineNophp5);
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".$md5.time";
	$TimeExec=$GLOBALS["CLASS_UNIX"]->file_time_min($timefile);
	if($TimeExec<$mintime){return;}
	@unlink($timefile);
	@file_put_contents($timefile, time());
	shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} ". basename(__FILE__)."/$cmdlineNophp5 >/dev/null 2>&1 &");
}

function shell_exec2($cmdline){
	$cmdline=str_replace("/usr/share/artica-postfix/ressources/exec.","/usr/share/artica-postfix/exec.",$cmdline);
	
	if(function_exists("debug_backtrace")){
		
		
		$trace=debug_backtrace();
		if(isset($trace[0])){
			$T_FUNCTION=$trace[0]["function"];
			$T_LINE=$trace[0]["line"];
			$T_FILE=basename($trace[0]["file"]);
		}
		
		
		if(isset($trace[1])){
			$T_FUNCTION=$trace[1]["function"];
			if(isset($trace[1]["line"])){$T_LINE=$trace[1]["line"];}
			if(isset($trace[1]["file"])){$T_FILE=basename($trace[1]["file"]);}
		}

		
	}
	
	
	if(!isset($GLOBALS["shell_exec2"])){$GLOBALS["shell_exec2"]=array();}
	if(!is_array($GLOBALS["shell_exec2"])){$GLOBALS["shell_exec2"]=array();}
	$md5=md5($cmdline);
	$time=date("YmdHi");
	if(isset($GLOBALS["shell_exec2"][$time][$md5])){
		if($GLOBALS["VERBOSE"]){echo "ERROR ALREADY EXECUTED $cmdline\n";}
		return;
	}
	if(count($GLOBALS["shell_exec2"])>5){$GLOBALS["shell_exec2"]=array();}
	$GLOBALS["shell_exec2"][$time][$md5]=true;
	
	
	if(!preg_match("#\/nohup\s+#",$cmdline)){
		$cmdline="{$GLOBALS["nohup"]} $cmdline";
	}
	if(!preg_match("#\s+>\/.*?2>\&1#",$cmdline)){
		if(!preg_match("#\&$#",$cmdline)){
			$cmdline="$cmdline >/dev/null 2>&1 &";
		}
	}
		
	if($GLOBALS["VERBOSE"]){echo "******************* EXEC ********************************\n$cmdline\n********************************\n";}
	if(!$GLOBALS["VERBOSE"]){events("$T_FILE:$T_FUNCTION:$T_LINE:Execute: \"$cmdline\"",__FUNCTION__,__LINE__);}
	shell_exec($cmdline);
	
}


function Default_values(){
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/php5MemoryLimit")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/php5MemoryLimit", 500);
		@chmod("/etc/artica-postfix/settings/Daemons/php5MemoryLimit",0755);
	}
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableOpenLDAP")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableOpenLDAP", 1);
		@chmod("/etc/artica-postfix/settings/Daemons/EnableOpenLDAP",0755);
	}
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/WindowsUpdateUseLocalProxy")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateUseLocalProxy", 1);
		@chmod("/etc/artica-postfix/settings/Daemons/WindowsUpdateUseLocalProxy",0755);
	}
	
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/DirectoriesMonitorH")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/DirectoriesMonitorH", 4);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/DirectoriesMonitorM", 30);
		@chmod("/etc/artica-postfix/settings/Daemons/DirectoriesMonitorH",0755);
		@chmod("/etc/artica-postfix/settings/Daemons/DirectoriesMonitorM",0755);
		$GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("DirectoriesMonitor","30 4 * * *","exec.philesight.php --directories");
	}	
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/DisksBenchs")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/DisksBenchs", 6);
		@chmod("/etc/artica-postfix/settings/Daemons/DisksBenchs",0755);
	}	

	if(is_file("/usr/share/wordpress-src/wp-includes/version.php")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/WordPressInstalled", 1);
		@chmod("/etc/artica-postfix/settings/Daemons/WordPressInstalled",0755);
	}else{
		@file_put_contents("/etc/artica-postfix/settings/Daemons/WordPressInstalled", 0);
		@chmod("/etc/artica-postfix/settings/Daemons/WordPressInstalled",0755);
	}
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/SessionPathInMemory")){
		$memoire=$GLOBALS["CLASS_UNIX"]->MEM_TOTAL_INSTALLEE();
		$memoire=round($memoire/1024);
		if($memoire>512){$SessionPathInMemory=50;}
		if($memoire>699){$SessionPathInMemory=90;}
		if($memoire>999){$SessionPathInMemory=128;}
		if($memoire>1499){$SessionPathInMemory=256;}
		if($memoire>1999){$SessionPathInMemory=320;}
		if($memoire>2599){$SessionPathInMemory=512;}
		if($memoire>4999){$SessionPathInMemory=728;}
		@file_put_contents("/etc/artica-postfix/settings/Daemons/SessionPathInMemory", $SessionPathInMemory);
		@chmod("/etc/artica-postfix/settings/Daemons/SessionPathInMemory",0755);
	}	
	if(!is_file("/etc/artica-postfix/settings/Daemons/InfluxRestartMem")){
		$memoire=$GLOBALS["CLASS_UNIX"]->MEM_TOTAL_INSTALLEE();
		$memoire=round($memoire/1024);
		if($memoire>512){$InfluxRestartMem=128;}
		if($memoire>699){$InfluxRestartMem=256;}
		if($memoire>999){$InfluxRestartMem=312;}
		if($memoire>1499){$InfluxRestartMem=400;}
		if($memoire>1999){$InfluxRestartMem=600;}
		if($memoire>2599){$InfluxRestartMem=1000;}
		if($memoire>2999){$InfluxRestartMem=1200;}
		if($memoire>3499){$InfluxRestartMem=1500;}
		if($memoire>3999){$InfluxRestartMem=2000;}
		if($memoire>3499){$InfluxRestartMem=2400;}
		if($memoire>3999){$InfluxRestartMem=2800;}
		if($memoire>4499){$InfluxRestartMem=2900;}
		if($memoire>4999){$InfluxRestartMem=3000;}
		if($memoire>5499){$InfluxRestartMem=3500;}
		if($memoire>5999){$InfluxRestartMem=3800;}
		if($memoire>6499){$InfluxRestartMem=4000;}
		if($memoire>6999){$InfluxRestartMem=4500;}
		if($memoire>7499){$InfluxRestartMem=5000;}
		if($memoire>7999){$InfluxRestartMem=5500;}
		if($memoire>8499){$InfluxRestartMem=6000;}
		@file_put_contents("/etc/artica-postfix/settings/Daemons/InfluxRestartMem", $InfluxRestartMem);
		@chmod("/etc/artica-postfix/settings/Daemons/InfluxRestartMem",0755);
	}
	
}




function testspeed(){
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableBandwithCalculation")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableBandwithCalculation", 1);
		@chmod("/etc/artica-postfix/settings/Daemons/EnableBandwithCalculation",0755);
	}
	if(!is_file("/etc/artica-postfix/settings/Daemons/BandwithCalculationSchedule")){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/BandwithCalculationSchedule", 1);
		@chmod("/etc/artica-postfix/settings/Daemons/BandwithCalculationSchedule",0755);
	}	
	
	
	
	$EnableBandwithCalculation=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableBandwithCalculation"));
	
	if($EnableBandwithCalculation==1){
		if(!is_file("/etc/cron.d/artica-testspeed")){
			Popuplate_cron_make("artica-testspeed","0 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22 * * *","exec.testspeed.php");
			shell_exec("/etc/init.d/cron reload");
			return;
		}
	}
	
	if(is_file("/etc/cron.d/artica-testspeed")){
		@unlink("/etc/cron.d/artica-testspeed");
		shell_exec("/etc/init.d/cron reload");
		return;
	}
	
}

function Popuplate_cron(){
	
	Build_default_values();
	shell_exec_time("exec.sys-stats.php",2);
	$EnableDNSPerfs=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableDNSPerfs"));
	$EnableSquidQuotasBandwidth=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableSquidQuotasBandwidth"));
	
	$SquidSMTPNotifs=0;
	if(is_file("/etc/artica-postfix/settings/Daemons/UfdbguardSMTPNotifs")){
		$UfdbguardSMTPNotifs=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/UfdbguardSMTPNotifs")));
		if(!isset($UfdbguardSMTPNotifs["UfdbguardSMTPNotifs"]["ENABLED_SQUID_WATCHDOG"])){$UfdbguardSMTPNotifs["UfdbguardSMTPNotifs"]["ENABLED_SQUID_WATCHDOG"]=0;}
		$SquidSMTPNotifs=$UfdbguardSMTPNotifs["UfdbguardSMTPNotifs"]["ENABLED_SQUID_WATCHDOG"];
	}
	
	testspeed();
	$CRON_RELOAD=false;
	$EnableArticaMetaServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaMetaServer"));
	
	if(!is_file("/etc/cron.hourly/squidrotate.sh")){
		$CRON[]="#!/bin/sh";
		$CRON[]="export LC_ALL=C";
		$CRON[]="{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.rotate.php --cron >/dev/null 2>&1";
		$CRON[]="";
		@file_put_contents("/etc/cron.hourly/squidrotate.sh", @implode("\n", $CRON));
		@chmod("/etc/cron.hourly/squidrotate.sh",0755);
		$CRON_RELOAD=true;
	
	}
	
	if($EnableSquidQuotasBandwidth==1){
		$SquidQuotaBandwidthRefresh=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidQuotaBandwidthRefresh"));
		$SquidQuotaBandwidthRefresh_array[15]="0,15,30,45 * * * *";
		$SquidQuotaBandwidthRefresh_array[30]="0,30 * * * *";
		$SquidQuotaBandwidthRefresh_array[60]="0 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23 * * *";
		$SquidQuotaBandwidthRefresh_array[120]="0 2,4,6,8,10,12,14,16,18,20,22 * * *";
		$FileMD5=@md5_file("/etc/cron.d/squid-squidbandquot");
		Popuplate_cron_make("squid-squidbandquot",$SquidQuotaBandwidthRefresh_array[$SquidQuotaBandwidthRefresh],"exec.quotaband.php");
		$FileMD52=@md5_file("/etc/cron.d/squid-squidbandquot");
		if($FileMD5<>$FileMD52){$CRON_RELOAD=true;}
	}else{
		if(is_file("/etc/cron.d/squid-squidbandquot")){@unlink("/etc/cron.d/squid-squidbandquot");$CRON_RELOAD=true;}
		
	}
	
	
	
	
	
	if($SquidSMTPNotifs==1){
		if(!is_file("/etc/cron.d/squid-notifications")){
			Popuplate_cron_make("squid-notifications","* * * * *","exec.squid.smtp.notifications.php");
			$CRON_RELOAD=true;
		}

	}else{
		if(is_file("/etc/cron.d/squid-notifications")){
			@unlink("/etc/cron.d/squid-notifications");
			$CRON_RELOAD=true;
		}
	}
	
	
	if($EnableArticaMetaServer==1){
		if(!is_file("/etc/cron.d/artica-meta-ufdb")){
			Popuplate_cron_make("artica-meta-ufdb","45 0,2,4,6,8,10,12,14,16,18,20,22 * * *","exec.squid.blacklists.php --bycron");
			$CRON_RELOAD=true;
		}
		if(!is_file("/etc/cron.d/ufdb-db-checkv")){
			Popuplate_cron_make("ufdb-db-checkv","30 0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22 * * *","exec.squid.blacklists.php --get-version");
			$CRON_RELOAD=true;
		}
		
	}else{
		if(is_file("/etc/cron.d/artica-meta-ufdb")){
			@unlink("/etc/cron.d/artica-meta-ufdb");
			$CRON_RELOAD=true;
		}
	}
	
	
	if(!is_file("/etc/cron.d/artica-squid-5min")){
		Popuplate_cron_make("artica-squid-5min","0,5,10,15,20,25,30,35,40,45,50,55 * * * *","exec.squidMins.php");
		$CRON_RELOAD=true;
	}	
	
	
	
	
	if(!is_file("/etc/cron.d/artica-rxtx-stats")){
		Popuplate_cron_make("artica-rxtx-stats","10 0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23 * * *","exec.rxtx.hourly.php");
		$CRON_RELOAD=true;
	}	


	if(!is_file("/etc/cron.d/artica-sys-stats")){
		Popuplate_cron_make("artica-sys-stats","* * * * *","exec.sys-stats.php");
		$CRON_RELOAD=true;
	}
	
	if(!is_file("/etc/cron.d/access-parser-logs")){
		Popuplate_cron_make("access-parser-logs","0 * * * *","exec.squidparse.hourly.php");
		$CRON_RELOAD=true;
	}
	
	if(!is_file("/etc/cron.d/apache-parser-logs")){
		Popuplate_cron_make("apache-parser-logs","30 * * * *","exec.apache.hour.php");
		$CRON_RELOAD=true;
	}

	if(!is_file("/etc/cron.d/access-parser-members")){
		Popuplate_cron_make("access-parser-members","5,15,30,45 * * * *","exec.squidparse.members.php --rtt");
		$CRON_RELOAD=true;
	}	
	
	if(!is_file("/etc/cron.d/access-parser-members-h")){
		Popuplate_cron_make("access-parser-members-h","0 * * * *","exec.squidparse.members.php --hour");
		$CRON_RELOAD=true;
	}	
	
	
	if($EnableDNSPerfs==1){
		if(!is_file("/etc/cron.d/artica-dnsperf")){
			Popuplate_cron_make("artica-dnsperf","0,5,10,15,20,25,30,35,40,45,50,55 * * * *","exec.dnsperf.php");
			$CRON_RELOAD=true;
		}
		
	}else{
		if(is_file("/etc/cron.d/artica-dnsperf")){
			@unlink("/etc/cron.d/artica-dnsperf");
			$CRON_RELOAD=true;
		}
	}
	
	if(!is_file("/etc/cron.d/access-parser-failed")){
		Popuplate_cron_make("access-parser-failed","0,5,10,15,20,25,30,35,40,45,50,55 * * * *","exec.squidparse.hourly.php --failed");
		$CRON_RELOAD=true;
	}	
	
	if(!is_file("/etc/cron.d/available-softwares")){
		Popuplate_cron_make("available-softwares","30 0,8,16 * * *","exec.web-community-filter.php --updates");
		$CRON_RELOAD=true;
	}	
	
	
	
	
	
	
	if(!is_file("/etc/cron.d/artica-clean-logs")){
		Popuplate_cron_make("artica-clean-logs","30 0,4,6,12 * * *","exec.clean.logs.php --clean-tmp1");
		$CRON_RELOAD=true;
	}
	
	if(!is_file("/etc/cron.d/artica-clean-tmp")){
		Popuplate_cron_make("artica-clean-logs","30 1,6,20,23 * * *","exec.clean.logs.php --clean-logs");
		$CRON_RELOAD=true;
	}	
	
	if(!is_file("/etc/cron.d/artica-whatsnew")){
		Popuplate_cron_make("artica-whatsnew","30 8,10,12,16,18,20,22 * * *","exec.web-community-filter.php --whatsnew");
		$CRON_RELOAD=true;
	}
	
	if(!is_file("/etc/cron.d/artica-clean-RTTSize")){
		Popuplate_cron_make("artica-clean-RTTSize","30 4 * * *","exec.clean.logs.php --rttsize");
		$CRON_RELOAD=true;
	}	
	
	if(!is_file("/etc/cron.d/artica-interface-size")){
		Popuplate_cron_make("artica-interface-size","0,15,30,45 * * * *","exec.squid.interface-size.php");
		$CRON_RELOAD=true;
	}
	if(!is_file("/etc/cron.d/artica-interface-hour")){
		Popuplate_cron_make("artica-interface-hour","25 * * * *","exec.squid.interface-size.php --flux-hour");
		$CRON_RELOAD=true;
	}
	
	
	if(!is_file("/etc/cron.d/artica-sys-alert")){
		Popuplate_cron_make("artica-sys-alert","0,2,4,6,8,10,12,14,16,18,20,22,24,26,28,30,32,34,36,38,40,42,44,46,48,50,52,54,56,58 * * * *","exec.mpstat.php");
		$CRON_RELOAD=true;
	}
	if(!is_file("/etc/cron.d/artica-auth-logs")){
		Popuplate_cron_make("artica-auth-logs","0,5,10,15,20,25,30,35,40,45,50,55 * * * *","exec.syslog-engine.php --auth-logs");
		$CRON_RELOAD=true;
	}
	
	if(!is_file("/etc/cron.d/artica-loadavg-interface")){
		Popuplate_cron_make("artica-auth-logs","0,5,10,15,20,25,30,35,40,45,50,55 * * * *","exec.loadavg.php");
		$CRON_RELOAD=true;
	}	
	
	if(!is_file("/etc/cron.d/artica-usb-scan")){
		Popuplate_cron_make("artica-usb-scan","0,30 * * * *","exec.usb.scan.write.php");
		$CRON_RELOAD=true;
	}
	if(!is_file("/etc/cron.d/artica-nightly")){
		Popuplate_cron_make("artica-nightly","0,30 * * * *","exec.nightly.php");
		$CRON_RELOAD=true;
	}	
	
	
	if(!is_file("/etc/cron.d/artica-process1")){
		
		$nice=$GLOBALS["CLASS_UNIX"]->EXEC_NICE();
		$PATH="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin:/usr/share/artica-postfix/bin";
		$CRON[]="PATH=$PATH";
		$CRON[]="MAILTO=\"\"";
		$CRON[]="0 0,2,6,8,10,14,16,18,20 * * *\troot\t$nice /usr/share/artica-postfix/bin/process1 --force >/dev/null 2>&1";
		$CRON[]="";
		file_put_contents("/etc/cron.d/artica-process1",@implode("\n", $CRON));
		chmod("/etc/cron.d/artica-process1",0640);
		chown("/etc/cron.d/artica-process1","root");
		$CRON_RELOAD=true;
		$CRON=array();
	}
	

	
	if($CRON_RELOAD){shell_exec2("/etc/init.d/cron reload");}
}

function Popuplate_cron_make($cronfile,$schedule,$phpprocess){
	
	$GLOBALS["CLASS_UNIX"]->Popuplate_cron_make($cronfile,$schedule,$phpprocess);

}

function gold(){
	$A=unserialize(base64_decode("YToyMDU6e3M6Mjc6IjJPWlRBWi1INU9HRzItWEQyRFJBLVlNN0lOWSI7YjoxO3M6Mjc6IldVRkhUNi1aT1VYTDktU00xWFFSLU9RTUVVQSI7YjoxO3M6Mjc6IktBRVFFMi1ZRUROUzEtSU5TSU9YLUJYNVdLNCI7YjoxO3M6Mjc6IlBQUEFUSi0ySDlWUkEtQTFMVktGLTlKTVZNMiI7YjoxO3M6Mjc6Ik9PVUk5TS1DN0RFUEEtSEdDRzFELUw2NEhKSSI7YjoxO3M6Mjc6IkNFUExGQi1GTjVMMTMtWFFVM1NBLVVWTEpLSiI7YjoxO3M6Mjc6Ik41NVlIUi1MQUYwQ1QtM0lWSlY5LTM5WllJQyI7YjoxO3M6Mjc6IkIxSklPSy1HWFNMUVMtRUFaMVhHLUxZOFNFUyI7YjoxO3M6Mjc6Ik9IVDZQVS00UElFUFgtOUZDTkJJLTlTREVWRiI7YjoxO3M6Mjc6Ikk5WEE2RC0yTk5BRjItNk1VQ0pPLTU5UFA4TCI7YjoxO3M6Mjc6IkU0TENXWi1WQlRKUVotTkFLWE5TLVNRT1JQMiI7YjoxO3M6Mjc6IlBaWE5LVS1VMkREQ1ktTUZPM0ZVLVFSRzhHTiI7YjoxO3M6Mjc6IlFETDVPRS0yMEhVOVctSlkyV05GLVpFR0JGRiI7YjoxO3M6Mjc6IlRBUk9EQS1TSkdRM0stR1lNRjBULThaSTlPVyI7YjoxO3M6Mjc6IlFBMUJBSy1YRFNWWFItWVUyRDdCLUNYNDdDWSI7YjoxO3M6Mjc6IkUwWlRZTi1PNlJSRkEtVDlNVTlaLUdaOFFBRCI7YjoxO3M6Mjc6IjhUM09OMS0xQTU5Qk0tR1dZRThULVRGR0ZXUyI7YjoxO3M6Mjc6IkZQWFZEMy1ORkMwR1YtVlFJVko5LVlJSFVMSyI7YjoxO3M6Mjc6IkJWUlRTSC1CSVdMVEUtQlJCMFhDLVJIV0RGQiI7YjoxO3M6Mjc6IkwxVlpJMC1VSlRTUU8tT1pFTkFHLVRVUVdPQiI7YjoxO3M6Mjc6IkhaVVFQRS1LV0VPRFctUUlDRE5SLUREV0pYNiI7YjoxO3M6Mjc6IklRRjUyRS1aTUZVOEUtMFdHQzdLLURRMktBQSI7YjoxO3M6Mjc6IjhEU1ZQWi03WTg2RUUtVUgxQTlULTVKSUhLQyI7YjoxO3M6Mjc6IlFRRElLUC1EOE5VSkYtOURGRFpaLUZJUk9QTyI7YjoxO3M6Mjc6IlpaUjg4OC1VRFdHVUgtRDhGTkxaLVNIT0ZHMyI7YjoxO3M6Mjc6IklEUDdTTS1INkJTUEotRlVSUUNMLUhBRjlNNCI7YjoxO3M6Mjc6IlJNR1VCVi1ESlJCTlMtWE1FVDlQLU5ISUlDUyI7YjoxO3M6Mjc6IlZOUFlOVS1RN1ZFWkItME5VOFBULUNaWEJEUCI7YjoxO3M6Mjc6IlBGREk2Uy1PQUVOV1UtNTFaNDFOLTI1TEk5WSI7YjoxO3M6Mjc6IjlLRUlCSC1FUkxIWUQtRkdDWk9VLVRVWEdWWSI7YjoxO3M6Mjc6IjNEOE5INy1IRzNMREktQkdSQU0yLTlGNUw1TiI7YjoxO3M6Mjc6Ikg4SVNVUS04T0dZTjktSUZPVU5OLVpOTVVPVSI7YjoxO3M6Mjc6IlpIVUhIUi00S05MNVotM0lZQVAwLVNCOVJENSI7YjoxO3M6Mjc6IlNWNVNQUi1JOU5VVFAtTkFMRE5JLUJOMkpVTiI7YjoxO3M6Mjc6IktOVU5HNC1OVktXQ04tWVI0WEk5LVZGRjYwRiI7YjoxO3M6Mjc6IllMTEtERi1KT0VYSTAtSUFTQTVaLVk1Q1BFRyI7YjoxO3M6Mjc6IklVWkhCRS1FUUZPUkEtT0xaUUI2LTFCQjNINCI7YjoxO3M6Mjc6Ik4ySzBaTi02SzhLSjAtOUpCRkZWLUY5VVNEVyI7YjoxO3M6Mjc6IlhGOFpROC1PRlpXUVgtV0xRQUZYLVNUVVNXTiI7YjoxO3M6Mjc6IkpTUlo1Vi1FRVBYU1gtMlNIRUhHLU5DOUlITyI7YjoxO3M6Mjc6IkVaUktQVS1FT1FVS0gtU1BTWEIxLVdTS1BQQSI7YjoxO3M6Mjc6IkZQRkUyQS02OUtGNkwtUEdOTFowLUlTMFlJQyI7YjoxO3M6Mjc6IjdCTU5UWC1FRUpWQUwtWUIzM0pLLUtOQlpGMSI7YjoxO3M6Mjc6IkJPOU5JSy1QUUJCNFotRkpLTVE0LVhTSzhERCI7YjoxO3M6Mjc6Ilg2UzZMRC01U1JYWlctUUhBNVFELVNCSDhBWSI7YjoxO3M6Mjc6IkdBVEE0Ui1SWDBJSkItRDFQV0ZFLTJUUDNVUCI7YjoxO3M6Mjc6IkJLMkdaTy1FQ0tXQU8tSFhBWU9RLTZYWUk3NiI7YjoxO3M6Mjc6IlZOVzBJVy1CTUs5VE4tWTkzR0FKLVdQUk1NUyI7YjoxO3M6Mjc6IkxaR0dPMC1COURITVItTTBQUDNCLU1ZRVlIVCI7YjoxO3M6Mjc6IjJVTzVMVC0yNkhXS0stNDVMR0RILU9BVlVWUyI7YjoxO3M6Mjc6Ik81SlAxVS1BRzlNUTQtWjlWRjVCLU1GTEFDUiI7YjoxO3M6Mjc6IjZYRFlDVy1LNFBISUEtNEYwVjVCLThXTVpRUCI7YjoxO3M6Mjc6Ik5UMVFOTC03WlVFTDQtOFBYSVdXLU1EWFRFUiI7YjoxO3M6Mjc6IlFENzhCVS01UklaU04tMTVHTEVCLVZIQVk4SCI7YjoxO3M6Mjc6IlRVSk1LRS1ZQ0hFRUwtRUZMTk1LLVhSVExQVyI7YjoxO3M6Mjc6IldIU0sxTC00VlhSV0YtTElQQ1dNLUFQUlhGUSI7YjoxO3M6Mjc6IkZBM1hQMi1USzgyRFItUFpXWUlELTgwVlA5TSI7YjoxO3M6Mjc6IklDQjZXSC1EU1hNWVEtTzJIMUVaLTVKSFpTViI7YjoxO3M6Mjc6IkhYRDlGRS01WFVBRFItQzROSFRJLUZXTE5aNSI7YjoxO3M6Mjc6IklXRk5FRi1OQUcyUDUtVU0yWFdTLVBFN1RTSyI7YjoxO3M6Mjc6IjE3TFpaQi00NDVYSFMtUElMVlhTLVQ3Nk9CRCI7YjoxO3M6Mjc6Iko2V1JUVy1NWVJOOEgtWFBCVzZNLUdVQ09ZTiI7YjoxO3M6Mjc6IlZCRUtXRi1OM1NEWUEtTjJZMU45LUxVTElOSCI7YjoxO3M6Mjc6IkJQS0RPSC1PRktKOU4tWUpRSE9ZLU1XRVdQWiI7YjoxO3M6Mjc6Ik9CR1o5US1SWUZCUlgtV1FQWEVQLVowRkJGUSI7YjoxO3M6Mjc6IlVZOU9RRS1CTTA0Q0wtVUNYTEhQLTZYQUQ0TiI7YjoxO3M6Mjc6IkFXR1lOUy1VQ1A4SEItQVcxRTJQLU9CR1hMWCI7YjoxO3M6Mjc6IksxQ0U0UC1JUTJGWTEtR1ZIVUJBLTFLTktGNCI7YjoxO3M6Mjc6IkFMU1lIUy1PRjk5TDktR1JWMlYwLVNIMkdaWSI7YjoxO3M6Mjc6IkFZSkM5TC1DT0lKRlItUElFWUpJLVcxVUxFUiI7YjoxO3M6Mjc6IkhXV0hKMS1WREZIUkYtTTJRUU1XLTNUU1ZHVSI7YjoxO3M6Mjc6Ikw5UUhPVS01OTU1OUwtTVhaWVc0LVpLUEpaTiI7YjoxO3M6Mjc6IldXQVhaTS1KM0oyTVEtMlI3SENJLTBDWkpPSyI7YjoxO3M6Mjc6IkRHWU1DMC0wR0dVRkctN0RPQVE0LU1XQ0ZZQyI7YjoxO3M6Mjc6IjlZQUk0Ri1XSUIxMUYtUFBTRENDLVM4N0dEOCI7YjoxO3M6Mjc6IlRIVkZWTS1BS1JXTk4tQkpQME1WLVpQWUVTMCI7YjoxO3M6Mjc6Ik9YRUM1Vi01S1NNRFQtWUpUVTA5LUpRUTcyRiI7YjoxO3M6Mjc6IlU4VFFPVS1ZQjFZM0otVUxSVVNDLVhWSVpCWSI7YjoxO3M6Mjc6IkVRTk9UNC1KS0cySzctRFAzUE5ULTdMUU5STyI7YjoxO3M6Mjc6IlRUVVI2Si1LTzNENDQtSUlXQUE5LVVKS1RNTiI7YjoxO3M6Mjc6IkVBRERUSy1MN0paTlUtWUdKSk1aLThBUDFaTSI7YjoxO3M6Mjc6IlZBUlhSVi05SVNUUTUtQUc0S09CLVgyTkM0QyI7YjoxO3M6Mjc6IlRVREdRMC1XS09TMlMtTEVESFdRLVVOQVdDViI7YjoxO3M6Mjc6IlBKS0c0VC1aUERNSFctV0JDUk9FLUhJSzhWVSI7YjoxO3M6Mjc6IlkyRUhFSi1RQVFRUU0tV1MzQk0yLUxZSFBLSSI7YjoxO3M6Mjc6IkpMRlBaOC1WOUdETkwtVDZJMVg5LUZYWjNZMyI7YjoxO3M6Mjc6IlhHVkNVQy1FTjROSkctMFdaWFVJLVhGSEJCVyI7YjoxO3M6Mjc6IlBEUzFKVS1UTkNXUFAtUU5CQTVMLUI3QktKSSI7YjoxO3M6Mjc6IkFEUDZJRS1XTU5JWUMtTFZSTk5LLTVITVhXTyI7YjoxO3M6Mjc6IkJTT1lNUC1DVDBGWVctSTRNTDVaLVQyQlE4UiI7YjoxO3M6Mjc6IlNBUk9FWi1LSUJUUEctUU5TSTk0LUE4QUNFUiI7YjoxO3M6Mjc6IkhMT1lUUC04VVNVS0stWldZNk1LLUpKOE8yTiI7YjoxO3M6Mjc6Ik80VkFZVy01SlQwSEYtTExVWEhYLVlZQzdKQiI7YjoxO3M6Mjc6IkxQRjNQVy1PNVBCR1UtMFA1RUtOLVhTQ09PWSI7YjoxO3M6Mjc6IlI5UE40Qi1JWFc4RVMtOUJJT0xHLThURFA0UiI7YjoxO3M6Mjc6IjhGVU5DVi1YVlNPMVQtTDNWUUdELUFKUEtQUCI7YjoxO3M6Mjc6IlJFUTJOSi0wWlRXWkwtNk9FUTlJLUpUWUFQRSI7YjoxO3M6Mjc6IkhGU0lBQy1NVElZTFQtWUlGUUlELVVKS1hHMiI7YjoxO3M6Mjc6IkxZQjFXVC1WVzNMNVktUEJMUExJLUdIRUNaUyI7YjoxO3M6Mjc6Ik1JSkdCMS1MTlQyRDItUk0wS0RKLVRaRVFMNyI7YjoxO3M6Mjc6IkJUV0VPTi0zWTNESVctVEdDV1NYLUtPRUhSTCI7YjoxO3M6Mjc6IlRPUEJBSS1QQ0tUUEctN0pSSlVKLUVTQU5HMSI7YjoxO3M6Mjc6IkY0QTNCTS1KTkZPUVktVFhOTUtTLTFMVjlHTiI7YjoxO3M6Mjc6IlM2Uk1QRS1GQ0I2Tk0tUkVDSVoyLTJOSlBNRCI7YjoxO3M6Mjc6IldUSU5YUS1BMVFCS0QtSVVQQ0swLUhTS1dDVyI7YjoxO3M6Mjc6IjJYSUJHMy1TVEJRSjgtTzRVQllYLVlTQ1hHQyI7YjoxO3M6Mjc6Ik4wVVVUNC1DUFgyOUQtT0VNQ0pMLURJOFdPOCI7YjoxO3M6Mjc6IkM5SkJLVS1MSFpXQVQtV0Y0WVhWLTMzNlhNUSI7YjoxO3M6Mjc6IjAyTkFIUS1FWTA3V1YtSk9NVEhULUxKVk1FOSI7YjoxO3M6Mjc6IjBZMUZMMi1NSDZKRUMtSlpDR05ILVpST0VUTiI7YjoxO3M6Mjc6Ikk1Sk5YTi1UQk5ITkstR1JBVDk2LUpRTzZJMSI7YjoxO3M6Mjc6IjU4V0pNTi1US1NUQkotSkdMWkcwLUVPU0NDMyI7YjoxO3M6Mjc6IjhGT0RXTy1SRk5GWDEtSFNLRldYLUZVS05QUCI7YjoxO3M6Mjc6Ik5aQ1pFWi1PRlUzWTItVzYwN0hELVVaUFlGVCI7YjoxO3M6Mjc6IlRBQ0dPSS01VEJQM1ktM1pCUk9PLUdCT1lTUCI7YjoxO3M6Mjc6IlEyUVVSQi1KMERBWUEtMTNWU1lRLTBKMEpVWSI7YjoxO3M6Mjc6IkhYUkNBWS00Q0owVk0tQlJJWVpTLVVWR04wRSI7YjoxO3M6Mjc6IkE4T1ZHNi1QWVNNTEEtTEJSWFZNLUpISTVJOCI7YjoxO3M6Mjc6IkFZOThaRi1WQ1ZEVU8tVjdDREVPLVRTTVZRNyI7YjoxO3M6Mjc6IlJZSkgzWC1IUUMwTU4tWkkyNjBKLUNTSUY2TSI7YjoxO3M6Mjc6IkUxRkNOWC1OV0NSMUQtTFlVNVRGLU8xREJBWCI7YjoxO3M6Mjc6IjRBTVNRVC1YQlowOEYtWU45WkVELTM4UkhIUSI7YjoxO3M6Mjc6Ik9URUZCSy1SSlBLN0wtOEVPRFg0LTlCTVZaMSI7YjoxO3M6Mjc6IkQxOUhYWC1SUEFaWkEtV1dVRUFXLVBTTjhBVyI7YjoxO3M6Mjc6Ik9LT0xLQy05Q0hKTkgtTE1BSEtFLVVISlpTRiI7YjoxO3M6Mjc6IlZXWlpWQy1VS1RaRlAtRU83MFFFLVpHTThDNiI7YjoxO3M6Mjc6IjJBRk4xUy1IREkySU8tSjRGWVhNLVVaMkdMRyI7YjoxO3M6Mjc6IllYRUJFSi1OMEhKVUMtWkJPWllRLVNEOUFXVCI7YjoxO3M6Mjc6IlJTVFlBRS1SUEVJU1MtVE9aQlFPLVBaUFE2QiI7YjoxO3M6Mjc6IjhCQVhGUi1LSVNJQUYtNVFVN0RYLTZPUlBYNCI7YjoxO3M6Mjc6IllWT1hLWi1UQ0EwWFItV0NWUk5YLVVFSVdYWSI7YjoxO3M6Mjc6IjBPQkxSVC1PTExBWkotQTlPMldILVhMQklNTyI7YjoxO3M6Mjc6IkROS1pSQS04MDNLSk4tVVU3WTRYLTg2RUdPNiI7YjoxO3M6Mjc6IkVSUUhZRi1HQkdDTkUtSk5QTEtLLVdWUENCVCI7YjoxO3M6Mjc6IlRHQ0ROSS1aVk1TRDktVzlKQlg0LTYwMEJQTCI7YjoxO3M6Mjc6Ik5FVUxRQi1YUk1VRlAtWEhMRE1JLVlJMFpaSSI7YjoxO3M6Mjc6Ikw1VjcxRy0wSEpUWlMtWUxNOU9OLUFaSExNOSI7YjoxO3M6Mjc6IjVFQ0dNRS1ZTktUQ0ktSkxYR0VILU5WWFJJTSI7YjoxO3M6Mjc6Ik5RQVVUTC1PREtHVTQtRVdQSVcyLUNHRU9YUSI7YjoxO3M6Mjc6IlFOSkRHOC1JRVRTSkotWFUwT1Q3LUJSNVlaRCI7YjoxO3M6Mjc6IlNFRkFIQy1ZUU9EQjAtUVFORUdPLVBTSEdFUyI7YjoxO3M6Mjc6IkxKUjhTUi1CSk1OQUMtWE04V0wzLUNZQ0dGRSI7YjoxO3M6Mjc6IkdPN1JORC1HSkk4RkYtQ1UzMUJQLUpETzdZSiI7YjoxO3M6Mjc6IkxGSkNLQy1USkdKMjAtTDlOSTI3LU9ONFVCSyI7YjoxO3M6Mjc6IkZCV0VCVS1CQ05NUEItQ1RQR0NULVNUUkdPViI7YjoxO3M6Mjc6IkFZVU5TUy1GVFpPQjUtWFJGSzQxLVBGR0ZIVCI7YjoxO3M6Mjc6Ik5MQU40Sy1KWEpEUU4tSU9XMk5ELVExN1JTUCI7YjoxO3M6Mjc6IldCVFhFTC1VTVhMUlotSENLRTdZLVhIMDVDRSI7YjoxO3M6Mjc6IjlIQ1NOMy1GU1M0UVctRElVNUFMLUEzT0dZUiI7YjoxO3M6Mjc6IkRBWEFSWi1UNEJaVVYtUEFNUkpJLVFKUDJPNyI7YjoxO3M6Mjc6IkpYMkNDWS1MNlZGWEEtQUJBS0hQLVpOMVBKQiI7YjoxO3M6Mjc6IktEWVVDTC1WUTJLWjgtM1FQWkhWLUFHV1NaMCI7YjoxO3M6Mjc6IlFYMkxOUi1RRVBHSFgtQ0lIUEdSLVg4WUNVSyI7YjoxO3M6Mjc6IlNLV1Q2TS1JR0FGNUEtTVpFWk1VLVZQU1dJTSI7YjoxO3M6Mjc6IklSQ1hUUC1TWDFCMUotRlhEQVk1LUZQUFJFTSI7YjoxO3M6Mjc6IjRQQ0hMRi1VQk4zSFgtTVNPSlMxLUM4WUNJRCI7YjoxO3M6Mjc6IlJITUhOSC1NRFdZWTMtOVBHSURGLVRLWlNTNyI7YjoxO3M6Mjc6IkxaTlVPOC1WM0lWMTgtUjRURk5GLVk4SlRNQSI7YjoxO3M6Mjc6IlBFUFZYNi1BVFozRUktUzgwNkNPLUpJRzJSSiI7YjoxO3M6Mjc6IkdTT0xLRy1aUFFLVVQtWlA3WllCLUhSUEtDMCI7YjoxO3M6Mjc6IlBTUkJRUS1ZRURZVUotSUdVWkZZLTBIR09VUCI7YjoxO3M6Mjc6IkVQRTlMWi1LSU1ENFotMkJOSFo1LUNEVVdXMSI7YjoxO3M6Mjc6IklaUEZTWS1FTVVDVE4tTzczS0JMLU9FSkFMTiI7YjoxO3M6Mjc6IlBSRUNCMC1CMFBXV0ctUllKUEJILTI1U1pPVSI7YjoxO3M6Mjc6IjVENlpTRy1HWlpTVlktR0ZVRFNNLU5VQjlTRSI7YjoxO3M6Mjc6IlNMVlRNTy1RQkFEMEwtVzJGWDNKLUdWSUZRTCI7YjoxO3M6Mjc6IktSQVlNUC1RUUJFMUMtQktIVVBZLUY3U0hVQSI7YjoxO3M6Mjc6IkxST0NHRC1SRVFLT08tNUozWkVaLU1WWUhEVSI7YjoxO3M6Mjc6IkNRQ0VTTi1STDJVQkotQ1I1U0VJLVdBUFVRWCI7YjoxO3M6Mjc6IjBDWldOSC1EQUJHV0ItRlFTUlAyLUs5UlNHSiI7YjoxO3M6Mjc6Ik1BRVFKRy1PRVBKODgtNUdQSVkyLUI5ODNaRiI7YjoxO3M6Mjc6IlRGVUZJNy1UUE9VNTAtNVNCSjNDLUJZRVlYSCI7YjoxO3M6Mjc6IkhIR1pZNi1DVElMNUstQk9OVkNILTk1WU5BVCI7YjoxO3M6Mjc6IjlFODJLRy04RTBaTUMtODNCV0RBLVY5TVhIQiI7YjoxO3M6Mjc6IkEyOVpCVy1USENDSDEtTFlQTVRJLU01RFBaUiI7YjoxO3M6Mjc6Ikg4SEdBWS1HVVQ3VlQtTE41NDlWLUpTTzVBSiI7YjoxO3M6Mjc6IjlPS0JGSC1KTkxRQ0wtTlVER1c5LUROV0s3VyI7YjoxO3M6Mjc6IllXR1VTVy1KR1hXQkItVDVNOTYxLUtPUlNINCI7YjoxO3M6Mjc6IkhVWUJINi1DUU1MQVMtTTdWTkpULUpVQ1QwMiI7YjoxO3M6Mjc6IkI0MElMUC1GSjVCVFctNUxIMThTLTgyVjFXTCI7YjoxO3M6Mjc6IjdXRjhQVC1URU9JT1ctSVc5NlVSLVI2R1BCNSI7YjoxO3M6Mjc6IkU1U0VEWi01TUNLN1AtS1JRT1hQLVBTQVZONiI7YjoxO3M6Mjc6Ilg2RU1JUy1TTThXSEwtSTJURE5RLVdNV1VPVCI7YjoxO3M6Mjc6Ik9UMzFDTi1BWUFISkYtRUcwUDZPLUJBOVNXWCI7YjoxO3M6Mjc6IloxQVdDQi0xUzZNQVYtQkVJS1ZHLUlETUNUUyI7YjoxO3M6Mjc6IkdNWlVaWC1XR1VJR1MtRlJPSDBLLTJRRzYwTyI7YjoxO3M6Mjc6IlVETVRORi1LUTNRVlgtRUJKNlFOLVhZNlA0RSI7YjoxO3M6Mjc6Ik5HUE1HNS00VFpaQ0UtRUlJMUVQLUZVNFlVQyI7YjoxO3M6Mjc6IjI4VUlaRC00SEdBTlctUFVTVUVRLUxRVFhSMCI7YjoxO3M6Mjc6IkJRV1lMVi1QSkJBNU0tSEdWTVZKLVZOTkdQTyI7YjoxO3M6Mjc6IkNaUklFTi01Vk1LRUQtNkxPTU01LVpPWUE4NiI7YjoxO3M6Mjc6IjNNVVhQMC1KR1pISVUtVEJMWENaLTdYSUJBVyI7YjoxO3M6Mjc6Ik02TElGRi1SWElGMVAtSFE2VVFCLTQ2STRDTSI7YjoxO3M6Mjc6IkNFWUpURS1ORkFWU1ctQUNQR1VJLVVTUlpTOSI7YjoxO3M6Mjc6IjdaT0pZQi1HOE5VNVAtRVlFNlVBLUFDRjJTRyI7YjoxO3M6Mjc6IlhSTEhSTS1PT01JUEYtVVQ4UDdLLVJFNkgzVCI7YjoxO3M6Mjc6IkpOS1NHQS1XQ0VJV00tR05JV1JNLVZMUDY4NSI7YjoxO3M6Mjc6IlgwV0dFVi1WSUNFS0UtTkRPQkpSLUxLUFFTTiI7YjoxO3M6Mjc6Ik5BQzFEOS1QQlJXTEctOTlZU1lCLVJVQ09XSSI7YjoxO3M6Mjc6IllXV1FGTi1RSlVMQlYtRlVUNlBQLUFYTlRFUyI7YjoxO3M6Mjc6IkhQVlVURC1XVTk3VEotTU1VUk1aLUZLOE1TVSI7YjoxO3M6Mjc6IkdQOFRSVC1aU1JENUUtVURBVVRMLVU4NlBZUiI7YjoxO3M6Mjc6IkdVWVdXRi0wRDE0WDktNUNCVVNaLVZHWVlOSSI7YjoxO3M6Mjc6Ik5MTUlUNC1ONlE3RTUtNkZLWTRFLThMWTFLMSI7YjoxO3M6Mjc6IllCSUVNUy1LSDNPUFUtVlFSUVJHLVlSOFY0RiI7YjoxO30="));
	print_r($A);
}

function mkdir_test($dir,$bit=0755,$continue=true){if(is_dir($dir)){return;}@mkdir($dir,$bit,$continue);}

?>