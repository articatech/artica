<?php

$GLOBALS["VERBOSE"]=false;
if(is_array($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;echo "Starting verbose mode\n";}}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
$GLOBALS["FORCE"]=false;$GLOBALS["REINSTALL"]=false;
$GLOBALS["NO_HTTPD_CONF"]=false;
$GLOBALS["NO_HTTPD_RELOAD"]=false;
$GLOBALS["NO_HTTPD_RESTART"]=false;
$GLOBALS["FORCE_RESTART"]=false;
$GLOBALS["NGINX_CONFIGURE"]=false;
if(is_array($argv)){
	if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	
	if(preg_match("#--reinstall#",implode(" ",$argv))){$GLOBALS["REINSTALL"]=true;}
	if(preg_match("#--no-httpd-conf#",implode(" ",$argv))){$GLOBALS["NO_HTTPD_CONF"]=true;}
	if(preg_match("#--noreload#",implode(" ",$argv))){$GLOBALS["NO_HTTPD_RELOAD"]=true;}
	if(preg_match("#--restart#",implode(" ",$argv))){$GLOBALS["FORCE_RESTART"]=true;$GLOBALS["NO_HTTPD_RELOAD"]=true;}
	
}
if($GLOBALS["VERBOSE"]){
	if(!function_exists("posix_getuid")){
		echo "Warning posix_getuid, no such function...\n";
	}
}

if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["posix_getuid"]=0;
if($GLOBALS["VERBOSE"]){ echo "starting include functions....\n";}
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.apache.certificate.php');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
include_once(dirname(__FILE__) . '/ressources/class.freeweb.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
if($GLOBALS["VERBOSE"]){ echo "starting include functions done..\n";}
$GLOBALS["SSLKEY_PATH"]="/etc/ssl/certs/apache";
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}

$settings=new settings_inc();
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(is_array($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;
echo "Starting verbose mode\n";}}
if($GLOBALS["VERBOSE"]){ echo "CheckLibraries()\n";}
CheckLibraries();
if($GLOBALS["VERBOSE"]){ echo "CheckLibraries() Done...\n";}
$GLOBALS["a2enmod"]=$GLOBALS["CLASS_UNIX"]->find_program("a2enmod");


if($GLOBALS["VERBOSE"]){
	echo "Debug mode TRUE for ". @implode(" ",$argv)."\n";
	echo "LOCATE_APACHE_BIN_PATH.....:".$GLOBALS["CLASS_UNIX"]->LOCATE_APACHE_BIN_PATH()."\n";
	echo "LOCATE_APACHE_CONF_PATH....:".$GLOBALS["CLASS_UNIX"]->LOCATE_APACHE_CONF_PATH()."\n";
	echo "a2enmod....................:{$GLOBALS["a2enmod"]}\n";
	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
}


if($argv[1]=="--dumpconf"){dumpconf($argv[2]);die();}
if($argv[1]=="--restore"){restore_container($argv[2],$argv[3],$argv[4]);die();}


if($argv[1]=="--sync-squid"){sync_squid();die();}
if($argv[1]=="--all-status"){mod_status_all();die();}
if($argv[1]=="--httpd"){CheckHttpdConf();reload_apache();die();}
if($argv[1]=="--build"){$GLOBALS["NO_HTTPD_RESTART"]=true;build();reload_apache();sync_squid();die();}
if($argv[1]=="--apache-user"){apache_user();die();}

if($argv[1]=="--sitename"){build_single_site($argv[2]);die();}
if($argv[1]=="--remove-host"){remove_host($argv[2]);reload_apache();sync_squid();die();}
if($argv[1]=="--perms"){FDpermissions($argv[2]);die();}
if($argv[1]=="--failed-start"){CheckFailedStart();die();exit;}
if($argv[1]=="--install-groupware"){install_groupware($argv[2]);die();exit;}
if($argv[1]=="--resolv"){resolv_servers();die();exit;}
if($argv[1]=="--drupal"){createdupal($argv[2]);die();exit;}
if($argv[1]=="--drupal-infos"){drupal_infos($argv[2]);die();exit;}
if($argv[1]=="--drupal-uadd"){drupal_add_user($argv[2],$argv[3]);die();exit;}
if($argv[1]=="--drupal-udel"){drupal_deluser($argv[2],$argv[3]);die();exit;}
if($argv[1]=="--drupal-uact"){drupal_enuser($argv[2],$argv[3],$argv[4]);die();exit;}
if($argv[1]=="--drupal-upriv"){drupal_privuser($argv[2],$argv[3],$argv[4]);die();exit;}
if($argv[1]=="--drupal-cron"){drupal_cron();die();exit;}
if($argv[1]=="--drupal-modules"){drupal_dump_modules($argv[2]);die();exit;}
if($argv[1]=="--drupal-modules-install"){drupal_install_modules($argv[2]);die();exit;}
if($argv[1]=="--drupal-reinstall"){drupal_reinstall($argv[2]);die();exit;}
if($argv[1]=="--drupal-schedules"){drupal_schedules();die();exit;}
if($argv[1]=="--status"){mod_status($argv[2]);die();exit;}
if($argv[1]=="--listwebs"){listwebs();die();exit;}
if($argv[1]=="--reconfigure-all"){reconfigure_all_websites();die();exit;}
if($argv[1]=="--reconfigure-webapp"){reconfigure_all_webapp();die();exit;}
if($argv[1]=="--reconfigure-webaccess"){reconfigure_all_webaccess();die();exit;}
if($argv[1]=="--reconfigure-webapp"){reconfigure_all_webapp();die();exit;}
if($argv[1]=="--reconfigure-zpush"){reconfigure_all_zpush();die();exit;}
if($argv[1]=="--reconfigure-updateutility"){reconfigure_all_updateutility();die();exit;}
if($argv[1]=="--reconfigure-wpad"){reconfigure_all_wpad();die();exit;}
if($argv[1]=="--rouncube-plugins"){roundcube_plugins($argv[2]);die();exit;}
if($argv[1]=="--monit"){build_monit();die();exit;}
if($argv[1]=="--watchdog"){watchdog($argv[2]);die();exit;}
if($argv[1]=="--start"){startApache();die();exit;}
if($argv[1]=="--stop"){StopApache();die();exit;}
if($argv[1]=="--reload"){ReloadApache();die();exit;}
if($argv[1]=="--backupsite"){backupsite($argv[2]);die();exit;}
if($argv[1]=="--ScanSize"){ScanSize();die();exit;}
if($argv[1]=="--remove-disabled"){remove_disabled();die();exit;}
if($argv[1]=="--ttl"){echo "TTL: ".TTL_Apache()."Mn\n";die();exit;}
if($argv[1]=="--restart-maintenance"){RestartApacheMaintenance();exit;}
if($argv[1]=="--check"){TestingApacheConfigurationFile();exit;}
if($argv[1]=="--restart-progress"){restart_progress();exit;}


	
	

help();

// mod_pagespeed ! ! 
//mod_evasive_
//mod_deflate.so

//http://www.tux-planet.fr/installation-et-configuration-de-modsecurity/

function build_single_site($sitename){
	$unix=new unix();
	$sock=new sockets();
	$EnableNginx=intval($sock->GET_INFO("EnableNginx"));
	$EnableFreeWeb=intval($sock->GET_INFO("EnableFreeWeb"));
	$php=$unix->LOCATE_PHP5_BIN();
	
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/freeweb.rebuild.progress";
	$GLOBALS["NGINX_CONFIGURE"]=true;
	build_progress("{reconfigure} $sitename", 5);
	
	if($EnableFreeWeb==0){
		if($EnableNginx==1){
			build_progress("{reconfigure} Nginx for $sitename", 80);
			if($GLOBALS["VERBOSE"]){echo "$php /usr/share/artica-postfix/exec.nginx.php --reconfigure $sitename\n";}
			system("$php /usr/share/artica-postfix/exec.nginx.php --reconfigure $sitename");
			build_progress("{success} $sitename", 100);
			return;
		}
		
	}
	
	if(!buildHost(null,$sitename)){
		build_progress("{failed} $sitename", 110);
		return;
		
	}
		
	if(!$GLOBALS["NO_HTTPD_CONF"]){
		build_progress("CheckHttpdConf() $sitename", 80);
		CheckHttpdConf();
	}
	if(!$GLOBALS["NO_HTTPD_RELOAD"]){
		build_progress("Reloading apache for $sitename", 90);
		reload_apache(true);
	}
			
	if($GLOBALS["FORCE_RESTART"]){
		build_progress("Restarting apache for $sitename", 90);
		reload_apache(true);
	}
			
	build_progress("sync_squid() $sitename ", 95);
	sync_squid();
		
	if($EnableNginx==1){
		build_progress("{reconfigure} Nginx for $sitename", 96);
		shell_exec("$php /usr/share/artica-postfix/exec.nginx.php --reconfigure $sitename");
	}
		
	build_progress("{success} $sitename", 100);
	return;
}


function build_progress($text,$pourc){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);

}
function build_progress_restart($text,$pourc){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/freeweb.progress", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/freeweb.progress",0755);

}

function help(){
	echo @implode(" ", $argv)."\n";
	echo "Usage : \t(use --verbose for more infos)\n";
	echo "--build............................: Configure apache\n";
	echo "--all-status.......................: Build status\n";
	echo "--apache-user --verbose............: Set Apache account in memory\n";
	echo "--sitename 'webservername'.........: Build vhost for webservername\n";
	echo "--remove-host 'webservername'......: Remove vhost for webservername\n";
	echo "--install-groupware 'webservername': Install the predefined groupware\n";
	echo "--httpd............................: Rebuild main configuration and modules\n";
	echo "--perms............................: Check files and folders permissions\n";
	echo "--failed-start.....................: Verify why Apache daemon did not want to run\n";
	echo "--resolv...........................: Verify if hostnames are in DNS\n";
	echo "--drupal...........................: Install drupal site for [servername]\n";
	echo "--drupal-infos.....................: Populate drupal informations in Artica database for [servername]\n";
	echo "--drupal-uadd......................: Create new drupal [user] for [servername]\n";
	echo "--drupal-udel......................: Delete  [user] for [servername]\n";
	echo "--drupal-uact......................: Activate  [user] 1/0 for [servername]\n";
	echo "--drupal-upriv.....................: set privileges  [user] administrator|user|anonym for [servername]\n";
	echo "--drupal-cron......................: execute necessary cron for all drupal websites\n";
	echo "--drupal-modules...................: dump drupal modules for [servername]\n";
	echo "--drupal-modules-install...........: install pre-defined modules [servername]\n";
	echo "--drupal-schedules.................: Run artica orders on the servers\n";
	echo "--listwebs.........................: List websites currently sets\n";
	echo "--ScanSize.........................: Insert into MySQL table the size of each web server directory\n";
}

function create_cron_task(){
	
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nice=$unix->EXEC_NICE();
	$f[]="MAILTO=\"\"";
	$f[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin:/usr/share/artica-postfix/bin";
	$f[]="0,10,20,30,40,50 * * * * root $nice$php5 ".__FILE__." --resolv >/dev/null 2>&1";
	$f[]="";
	
	@file_put_contents("/etc/cron.d/iptaccount", @implode("\n", $f));
	shell_exec("/bin/chmod 640 /etc/cron.d/freeweb_resolv >/dev/null 2>&1");
	shell_exec("/etc/init.d/cron reload");
	
}


function reconfigure_all_websites(){
	$sql="SELECT * FROM freeweb WHERE enabled=1 ORDER BY servername";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,'artica_backup');	
	$count=mysql_num_rows($results);
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$hostname=$ligne["servername"];
		install_groupware($hostname);
		buildHost(null,$hostname);
	}
	sync_squid();
}

function sync_squid(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$free=new freeweb();
	$free->SpecificsChowns();
	system("$php /usr/share/artica-postfix/exec.squid.global.access.php --freewebs");
}


function remove_disabled(){
	$workdir="/etc/apache2/sites-enabled";
	$sql="SELECT * FROM freeweb WHERE enabled=0 ORDER BY servername";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,'artica_backup');
	$count=mysql_num_rows($results);
	$reload=false;
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$hostname=$ligne["servername"];
		if(is_file("$workdir/artica-$hostname.conf")){
			@unlink("$workdir/artica-$hostname.conf");
			$reload=true;
		}
	}
	
	if($reload){reload_apache();}
}

function check_enabled(){
	$workdir="/etc/apache2/sites-enabled";
	$sql="SELECT * FROM freeweb WHERE enabled=1 ORDER BY servername";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,'artica_backup');
	$count=mysql_num_rows($results);
	$reload=false;
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$hostname=$ligne["servername"];
		if(!is_file("$workdir/artica-$hostname.conf")){
			buildHost(null,$hostname);
			$reload=true;
		}
	}

	if($reload){reload_apache();}
	
}

function reconfigure_all_zpush(){
	$unix=new unix();
	@mkdir("/etc/artica-postfix/pids",0755,true);
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		echo "Already instance executed pid $pid\n";
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	$sql="SELECT servername FROM freeweb WHERE groupware='Z-PUSH' AND enabled=1";
	$q=new mysql();
		$results=$q->QUERY_SQL($sql,'artica_backup');
	$count=mysql_num_rows($results);
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$hostname=$ligne["servername"];
		install_groupware($hostname);
			buildHost(null,$hostname);
	}
	
	$php=$unix->LOCATE_PHP5_BIN();
	reload_apache();	
	
}
function reconfigure_all_updateutility(){
	$unix=new unix();
	@mkdir("/etc/artica-postfix/pids",0755,true);
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		echo "Already instance executed pid $pid\n";
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	$sql="SELECT servername FROM freeweb WHERE groupware='UPDATEUTILITY' AND enabled=1";
	$q=new mysql();
		$results=$q->QUERY_SQL($sql,'artica_backup');
	$count=mysql_num_rows($results);
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$hostname=$ligne["servername"];
		install_groupware($hostname);
			buildHost(null,$hostname);
	}
	
	$php=$unix->LOCATE_PHP5_BIN();
	reload_apache();	
}

function reconfigure_all_wpad(){
	$unix=new unix();
	@mkdir("/etc/artica-postfix/pids",0755,true);
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		echo "Already instance executed pid $pid\n";
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	$sql="SELECT servername FROM freeweb WHERE groupware='WPAD' AND enabled=1";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,'artica_backup');
	
	
	$count=mysql_num_rows($results);
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$hostname=$ligne["servername"];
		install_groupware($hostname);
			buildHost(null,$hostname);
	}
	
	$sql="SELECT servername FROM freeweb WHERE groupware='WPADDYN' AND enabled=1";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,'artica_backup');
	
	
	$count=mysql_num_rows($results);
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$hostname=$ligne["servername"];
		install_groupware($hostname);
		buildHost(null,$hostname);
	}	
	
	
	
	reload_apache();	
}

function reconfigure_all_webapp(){
	$unix=new unix();
	@mkdir("/etc/artica-postfix/pids",0755,true);
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		echo "Already instance executed pid $pid\n";
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	$sql="SELECT servername FROM freeweb WHERE groupware='WEBAPP' AND enabled=1";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,'artica_backup');
	$count=mysql_num_rows($results);
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$hostname=$ligne["servername"];
		install_groupware($hostname);
		buildHost(null,$hostname);
	}	
	
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php /usr/share/artica-postfix/exec.ejabberd.php --zarafa >/dev/null 2>&1");
	reload_apache();
	
}

function reconfigure_all_webaccess(){
	$unix=new unix();
	@mkdir("/etc/artica-postfix/pids",0755,true);
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		echo "Already instance executed pid $pid\n";
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	$sql="SELECT servername FROM freeweb WHERE groupware='ZARAFA' AND enabled=1";
	$q=new mysql();
		$results=$q->QUERY_SQL($sql,'artica_backup');
	$count=mysql_num_rows($results);
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$hostname=$ligne["servername"];
		install_groupware($hostname);
			buildHost(null,$hostname);
	}
	
	
	reload_apache();	
}


function listwebs(){
	$unix=new unix();
	$sql="SELECT * FROM freeweb WHERE enabled=1 ORDER BY servername";
	$httpdconf=$unix->LOCATE_APACHE_CONF_PATH();
	$apacheusername=$unix->APACHE_SRC_ACCOUNT();
	$GLOBALS["apacheusername"]=$apacheusername;
	$DAEMON_PATH=$unix->getmodpathfromconf($httpdconf);
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){if($GLOBALS["VERBOSE"]){echo $q->mysql_error."\n";return;}}
	$d_path=$unix->APACHE_DIR_SITES_ENABLED();
	$mods_enabled=$DAEMON_PATH."/mods-enabled";
	
	
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache daemon path: $d_path\n";
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache mods path..: $mods_enabled\n";
	
	if(!is_dir($d_path)){@mkdir($d_path,0666,true);}
	if(!is_dir($mods_enabled)){@mkdir($mods_enabled,0666,true);}
	
	$count=mysql_num_rows($results);
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache checking virtual web sites count:$count\n";
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$hostname=$ligne["servername"];
		echo "Starting......: ".date("H:i:s")." available $hostname\n";
		
	}
	
}



function apache_user(){
	$unix=new unix();
	$apacheusername=$unix->APACHE_SRC_ACCOUNT();
	$sock=new sockets();
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [INIT]: Apache APACHE_SRC_ACCOUNT: $apacheusername\n";}
	$sock->SET_INFO('APACHE_SRC_ACCOUNT',"$apacheusername");
}

function reload_apache(){
	$apache2ctl=$GLOBALS["CLASS_UNIX"]->find_program("apache2ctl");
	if(!is_file($apache2ctl)){$apache2ctl=$GLOBALS["CLASS_UNIX"]->find_program("apachectl");}
	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
	$kill=$GLOBALS["CLASS_UNIX"]->find_program("kill");
	$LOCATE_APACHE_CONF_PATH=$GLOBALS["CLASS_UNIX"]->LOCATE_APACHE_CONF_PATH();
	
	
	if($GLOBALS["NO_HTTPD_RESTART"]==true){
		echo "Starting......: ".date("H:i:s")." [INIT]: Apache reloading \"graceful\"\n";
		$cmd="$apache2ctl -f $LOCATE_APACHE_CONF_PATH -k restart 2>&1";
		exec($cmd,$results);
		while (list ($num, $ligne) = each ($results) ){
			if(apachectl_line_skip($ligne)){continue;}
			echo "Reloading.....: [INIT]: Apache $ligne\n";}
		return;
	}
	
	if(is_file($apache2ctl)){
		$cmd="$apache2ctl -f $LOCATE_APACHE_CONF_PATH -k stop 2>&1";
		echo "Starting......: ".date("H:i:s")." [INIT]: Apache stopping \"$cmd\"\n";
		exec($cmd,$results);
		while (list ($num, $ligne) = each ($results) ){
			if(apachectl_line_skip($ligne)){continue;}
			echo "Stopping......: ".date("H:i:s")." [INIT]: Apache $ligne\n";}
		
			
		apache_admin_mysql(1, "Apache Web service was reloaded [action=info]", @implode("\n", $results),__FILE__,__LINE__);
		$results=array();
		
		KillApacheProcesses();
		startApache(false,true);

	}
}

function StopApache(){
	$apache2ctl=$GLOBALS["CLASS_UNIX"]->find_program("apache2ctl");
	if(!is_file($apache2ctl)){$apache2ctl=$GLOBALS["CLASS_UNIX"]->find_program("apachectl");}
	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
	$kill=$GLOBALS["CLASS_UNIX"]->find_program("kill");
	$LOCATE_APACHE_CONF_PATH=$GLOBALS["CLASS_UNIX"]->LOCATE_APACHE_CONF_PATH();
	if(is_file(!$apache2ctl)){	
		echo "Fatal: unable to locate apachectl\n";
		return;
	}
	
	apache_admin_mysql(0, "Stopping Apache Web service [action=info]", @implode("\n", $results),__FILE__,__LINE__);
	$cmd="$apache2ctl -f $LOCATE_APACHE_CONF_PATH -k stop 2>&1";
	echo "Stopping......: ".date("H:i:s")." [INIT]: Apache stopping \"$cmd\"\n";
	exec($cmd,$results);
	while (list ($num, $ligne) = each ($results) ){
		if(apachectl_line_skip($ligne)){continue;}
		echo "Stopping......: ".date("H:i:s")." [INIT]: Apache $ligne\n";
	
	}
	apache_admin_mysql(1, "Apache Web service was stopped [action=info]", @implode("\n", $results),__FILE__,__LINE__);
	KillApacheProcesses();	
}


function KillApacheProcesses($binaddress=null){
	$apache2ctl=$GLOBALS["CLASS_UNIX"]->find_program("apache2ctl");
	if(!is_file($apache2ctl)){$apache2ctl=$GLOBALS["CLASS_UNIX"]->find_program("apachectl");}
	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
	$kill=$GLOBALS["CLASS_UNIX"]->find_program("kill");
	$fuser=$GLOBALS["CLASS_UNIX"]->find_program("fuser");
	$ipcs=$GLOBALS["CLASS_UNIX"]->find_program("ipcs");
	$ipcrm=$GLOBALS["CLASS_UNIX"]->find_program("ipcrm");
	$APACHE_SRC_ACCOUNT=$GLOBALS["CLASS_UNIX"]->APACHE_SRC_ACCOUNT();
	$APACHE_SRC_GROUP=$GLOBALS["CLASS_UNIX"]->APACHE_SRC_GROUP();
	$ipcsT=array();
	if(is_file($ipcs)){
		$cmd="$ipcs -s 2>&1";
		exec("$cmd",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#[a-z0-9]+\s+([0-9]+)\s+$APACHE_SRC_ACCOUNT#", $ligne,$re)){$ipcsT[$re[1]]=true;}
		}
		echo "Stopping......: ".date("H:i:s")." [INIT]: Apache kill ". count($ipcsT)." semaphores created by $APACHE_SRC_ACCOUNT...\n";
		while (list ($id, $ligne) = each ($ipcsT) ){
			shell_exec("$ipcrm sem $id");
		}
	}
	
	
	$results=array();
	$LOCATE_APACHE_CONF_PATH=$GLOBALS["CLASS_UNIX"]->LOCATE_APACHE_CONF_PATH();	
		$cmd="$pgrep -l -f \"$LOCATE_APACHE_CONF_PATH -k start\" 2>&1";
		exec("$cmd",$results);
		echo "Stopping......: ".date("H:i:s")." [INIT]: Apache `$cmd` ". count($results) ." lines.\n";
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#(is already loaded|has no VirtualHosts)#", $ligne)){continue;}
			if(strpos($ligne, $pgrep)==0){
				if(preg_match("#^([0-9]+)\s+#", $ligne,$re)){
					echo "Stopping......: ".date("H:i:s")." [INIT]: Apache killing PID {$re[1]}\n";
					$GLOBALS["CLASS_UNIX"]->KILL_PROCESS($re[1],9);
					
				}
				
			}
		}

		if($binaddress<>null){
			if(is_file($fuser)){
				$port=0;
				if(preg_match("#(.+?):([0-9]+)#", $binaddress,$re)){$port=$re[2];}
				if($port==0){if(preg_match("#([0-9]+)#", $binaddress,$re)){$port=$re[1];}}
				if($port>0){
					echo "Stopping......: ".date("H:i:s")." [INIT]: Apache find which process use the port $port\n";
					$results=array();
					$cmd="$fuser $port/tcp 2>&1";
					exec("$cmd",$results);
					echo "Stopping......: ".date("H:i:s")." [INIT]: Apache `$cmd` ". count($results) ." lines.\n";
					while (list ($num, $ligne) = each ($results) ){
						if(preg_match("#$port\/tcp:(.+)#", $ligne,$re)){
							$ff=explode(" ", $re[1]);
							while (list ($index, $ligne2) = each ($ff) ){
								$ligne2=trim($ligne2);
								if(!is_numeric($ligne2)){continue;}
								echo "Stopping......: ".date("H:i:s")." [INIT]: Apache killing PID $ligne2\n";
								$GLOBALS["CLASS_UNIX"]->KILL_PROCESS($ligne2,9);
								
							}
						}
						
					}
				}
			}
		}
		
		
	
}

function ReloadApache($nocheck=false){
	CheckLibraries();
	if(!$nocheck){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
		$timefile="/etc/artica-postfix/pids/reload.". __FUNCTION__.".time";
		$pid=@file_get_contents("$pidfile");
		if($GLOBALS["CLASS_UNIX"]->process_exists($pid,basename(__FILE__))){system_admin_events("Already executed PID $pid",__FUNCTION__,__FILE__,__LINE__,"freewebs");die();}
		@file_put_contents($pidfile, getmypid());
		$time=$GLOBALS["CLASS_UNIX"]->file_time_min($timefile);
		if($time<1){system_admin_events("No less than 1mn or delete $timefile file",__FUNCTION__,__FILE__,__LINE__,"freewebs");die();}
		@unlink($timefile);
		@file_put_contents($timefile, time());	
	}	
	
	$LOCATE_APACHE_CONF_PATH=$GLOBALS["CLASS_UNIX"]->LOCATE_APACHE_CONF_PATH();
	$apache2ctl=$GLOBALS["CLASS_UNIX"]->find_program("apache2ctl");
	if(!is_file($apache2ctl)){$apache2ctl=$GLOBALS["CLASS_UNIX"]->find_program("apachectl");}
	if(!is_file($apache2ctl)){return;}	
	$cmd="$apache2ctl -f $LOCATE_APACHE_CONF_PATH -k restart 2>&1";
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache \"$cmd\"\n";		
	exec($cmd,$results);
	ReloadNginx();
}

function ReloadNginx(){
	$sock=new sockets();
	
	$EnableNginx=$sock->GET_INFO("EnableNginx");
	if(!is_numeric($EnableNginx)){$EnableNginx=1;}
	if($EnableNginx==0){return;}
	$nginx=$GLOBALS["CLASS_UNIX"]->find_program("nginx");
	if(!is_file($nginx)){return;}
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache Reload NGINX\n";
	shell_exec("/etc/init.d/nginx reload");
	
	
}

function apachectl_line_skip($ligne){
	if(preg_match("#(is already loaded|has no VirtualHosts)#", $ligne)){return true;}
	if(preg_match("#module authn_file_module is already loaded#",$ligne)){return true;}
	if(preg_match("#The Alias directive in.*?will probably never match because it overlaps#",$ligne)){return true;}
	return false;
}

function TTL_Apache(){
	$APACHE_PID_PATH=$GLOBALS["CLASS_UNIX"]->APACHE_PID_PATH();
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($APACHE_PID_PATH);
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){
		return $GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($pid);
		
	}
	return 0;
}

function RestartApacheMaintenance($aspid=false){
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pidfile);
		if($GLOBALS["CLASS_UNIX"]->process_exists($pid,basename(__FILE__))){
			$time=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Apache Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}	
	
	StopApache();
	startApache(false,true);
	
}

//apt-get --reinstall install apache2.2-bin

function APACHE_INSTALL_DEBIAN(){
	$aptget=$GLOBALS["CLASS_UNIX"]->find_program("apt-get");
	if(!is_file($aptget)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Apache not a Debian system, aborting\n";}
		return;
	}
	
	$timeStamp="/etc/artica-postfix/pids/APACHE_INSTALL_DEBIAN";
	
	if($GLOBALS["CLASS_UNIX"]->file_time_min($timeStamp)<15){return;}
	@unlink($timeStamp);
	@file_put_contents($timeStamp, time());
	shell_exec("$aptget update -q -f");
	@unlink("/usr/sbin/apache2");
	shell_exec("$aptget --reinstall --quiet -f -q --force-yes install apache2.2-bin");
	
}


function startApache($withoutkill=false,$aspid=false){
	
	$unix=new unix();
	if(is_file("/etc/artica-postfix/FROM_ISO")){
		if($unix->file_time_min("/etc/artica-postfix/FROM_ISO")<1){return;}
	}
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Apache Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}	
	
	
	if(!isset($GLOBALS["startApacheCount"])){$GLOBALS["startApacheCount"]=0;}
	$GLOBALS["startApacheCount"]=$GLOBALS["startApacheCount"]+1;
	$EnableFreeWeb=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFreeWeb"));
	$SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
	$EnableIntelCeleron=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableIntelCeleron"));
	$SquidAllow80Port=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAllow80Port"));
	
	
	if($SquidPerformance>2){$EnableFreeWeb=0;}
	if($EnableIntelCeleron==1){$EnableFreeWeb=0;}
	if($SquidAllow80Port==1){$EnableFreeWeb=0;}
	
	if(is_file("/etc/httpd/conf.d/ssl.conf")){@unlink("/etc/httpd/conf.d/ssl.conf");}
	
	
	if($GLOBALS["startApacheCount"]>3){
		echo "Starting......: ".date("H:i:s")." [INIT]: Apache failed, too many start:{$GLOBALS["startApacheCount"]}...\n";
		return;
	}
	
	if(!is_file("/usr/lib/apache2/mpm-prefork/apache2")){
		$ln=$unix->find_program("ln");
		echo "Starting......: ".date("H:i:s")." [INIT]: Apache failed, linking /usr/lib/apache2/mpm-prefork/apache2\n";
		shell_exec("$ln -sf /usr/lib/apache2/mpm-prefork/apache2 /usr/sbin/apache2 >/dev/null 2>&1");
	}
	
	
	$APACHE_PID_PATH=$GLOBALS["CLASS_UNIX"]->APACHE_PID_PATH();
	$APACHE_BIN_PATH=$GLOBALS["CLASS_UNIX"]->LOCATE_APACHE_BIN_PATH();
	
	if(!is_file($APACHE_BIN_PATH)){
		echo "Starting......: ".date("H:i:s")." [INIT]: Apache failed no binary found, re-install it ( if debian )\n";
		APACHE_INSTALL_DEBIAN();
		return;
	}
	
	
	$apache2ctl=$GLOBALS["CLASS_UNIX"]->find_program("apache2ctl");
	if(!is_file($apache2ctl)){$apache2ctl=$GLOBALS["CLASS_UNIX"]->find_program("apachectl");}	
	
	
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($APACHE_PID_PATH);
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){
		$timep=$unix->PROCESS_UPTIME($pid);
		echo "Starting......: ".date("H:i:s")." [INIT]: Apache already started pid $pid $timep [$APACHE_PID_PATH]\n";
		if($EnableFreeWeb==0){StopApache();return;}
		return;
	}	
	
	$sysctl=$unix->find_program("sysctl");
	shell_exec("$sysctl -w kernel.msgmni=1024 >/dev/null 2>&1");
	shell_exec("$sysctl -w \"kernel.sem=250 256000 32 1024\" >/dev/null 2>&1");
	shell_exec("$sysctl -p >/dev/null 2>&1");
	
	if($EnableFreeWeb==0){echo "Starting......: ".date("H:i:s")." [INIT]: Apache Disabled ( see EnableFreeWeb($EnableFreeWeb)/SquidAllow80Port($SquidAllow80Port)/EnableIntelCeleron($EnableIntelCeleron)/SquidPerformance($SquidPerformance) tokens )\n";return;}
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache {$GLOBALS["startApacheCount"]} time(s)\n";
	$files=$unix->DirFiles("/usr/share/artica-postfix/bin");
	while (list ($filename,$line) = each ($files)){
		@chmod("/usr/share/artica-postfix/bin/$filename",0755);
		@chown("/usr/share/artica-postfix/bin/$filename","root");
	}
	
	@chmod("/usr/share/artica-postfix/ressources/mem.pl",0755);
	if(!is_file($apache2ctl)){return;}
	
	apache_permissions();
	$LOCATE_APACHE_CONF_PATH=$GLOBALS["CLASS_UNIX"]->LOCATE_APACHE_CONF_PATH();
	$kill=$GLOBALS["CLASS_UNIX"]->find_program("kill");
	$cmd="$apache2ctl -f $LOCATE_APACHE_CONF_PATH -k start 2>&1";
	echo "Starting......: ".date("H:i:s")." [INIT]: apache2 bin \"$APACHE_BIN_PATH\"\n";
	echo "Starting......: ".date("H:i:s")." [INIT]: apache2ctl: \"$apache2ctl\"\n";
	echo "Starting......: ".date("H:i:s")." [INIT]: PID Path..: \"$APACHE_PID_PATH\"\n";		
	exec($cmd,$results);

	$hostname=$unix->hostname_g();
	$hostname_ip=$unix->get_EtcHostsByName("$hostname");
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache $hostname = $hostname_ip\n";
	if($hostname_ip==null){
		$echo=$unix->find_program("echo");
		echo "Starting......: ".date("H:i:s")." [INIT]: Apache Add $hostname in /etc/hosts\n";
		shell_exec("$echo \"127.0.0.1\t$hostname\" >>/etc/hosts");
	}
	
	$hostname_ip=$unix->get_EtcHostsByName("localhost");
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache localhost = $hostname_ip\n";
	if($hostname_ip==null){
		$echo=$unix->find_program("echo");
		echo "Starting......: ".date("H:i:s")." [INIT]: Apache Add localhost in /etc/hosts\n";
		shell_exec("$echo \"127.0.0.1\tlocalhost\" >>/etc/hosts");
	}	
		
	while (list ($num, $ligne) = each ($results) ){
		if(apachectl_line_skip($ligne)){continue;}
		if(preg_match("#Cannot load .+?mod_dav_fs.+?into server#",$ligne)){
			echo "Starting......: ".date("H:i:s")." [INIT]: Apache $ligne\n";
			echo "Starting......: ".date("H:i:s")." [INIT]: Apache mod_dav_fs failed, disable it\n";
			$sock=new sockets();
			$sock->SET_INFO("ApacheDisableModDavFS",1);
			CheckHttpdConf();
			continue;
		}
		
		if(preg_match("#Cannot load.*?mod_status\.so.*?undefined symbol#",$ligne)){
			echo "Starting......: ".date("H:i:s")." [INIT]: Apache $ligne\n";
			echo "Starting......: ".date("H:i:s")." [INIT]: Apache mod_status failed, disable it\n";
			$sock=new sockets();
			$sock->SET_INFO("ApacheDisableModStatus",1);
			CheckHttpdConf();
			continue;
			
		}
		
		
		
		if(preg_match("#Error retrieving pid file\s+(.+)#", $ligne,$re)){
			$re[1]=trim($re[1]);
			echo "Starting......: ".date("H:i:s")." [INIT]: Apache, removing {$re[1]}\n";
			@unlink(trim($re[1]));
			startApache(true,true);
			return;
		}
			
		if(preg_match("#httpd.+?pid\s+([0-9]+)\) already running#",$ligne,$re)){
			if(!$withoutkill){
				echo "Starting......: ".date("H:i:s")." [INIT]: Apache killing PID {$re[1]}\n";
				unix_system_kill_force($re[1]);
				KillApacheProcesses();
				startApache(true,true);
				return;
			}else{
				echo "Starting......: ".date("H:i:s")." [INIT]: Apache restart\n";
				shell_exec("$apache2ctl restart");
				continue;
			}
		}
		
		if(preg_match("#Address already in use: make_sock:\s+could not bind to address\s+(.+)$#",$ligne,$re)){
			echo "Starting......: ".date("H:i:s")." [INIT]: Apache ERROR $ligne\n";
			sleep(1);
			if(!$withoutkill){
				KillApacheProcesses($re[1]);
				startApache(true,true);
				return;
				
			}
		}
			
			echo "Starting......: ".date("H:i:s")." [INIT]: Apache $ligne [INFO]\n";
		}

		
	$APACHE_PID_PATH=$GLOBALS["CLASS_UNIX"]->APACHE_PID_PATH();
	$unix=new unix();
	sleep(2);
	
	
	$pid=$unix->get_pid_from_file($APACHE_PID_PATH);
	if(!$unix->process_exists($pid)){
		apache_admin_mysql(0, "Apache Web failed to be started [action=info]", @implode("\n", $results),__FILE__,__LINE__);
		echo "Starting......: ".date("H:i:s")." [INIT]: Apache Failed [$APACHE_PID_PATH]\n";
		return;
	}	
	apache_admin_mysql(1, "Apache Web success to be started [action=info]", @implode("\n", $results),__FILE__,__LINE__);
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache Success pid $pid\n";
	$nginx=$GLOBALS["CLASS_UNIX"]->find_program("nginx");
	if(is_file($nginx)){shell_exec("/etc/init.d/nginx start");}
	
}

function APACHE_PID(){
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	$APACHE_PID_PATH=$GLOBALS["CLASS_UNIX"]->APACHE_PID_PATH();
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($APACHE_PID_PATH);
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	
	
	$LOCATE_APACHE_CONF_PATH=$GLOBALS["CLASS_UNIX"]->LOCATE_APACHE_CONF_PATH();
	$apache=$GLOBALS["CLASS_UNIX"]->APACHE_BIN_PATH();
	$pattern="$apache.*?-f $LOCATE_APACHE_CONF_PATH";
	$unix=new unix();
	$pgrep=$unix->find_program("pgrep");
	exec("$pgrep -l -f \"$pattern\" 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#pgrep#", $ligne)){continue;}
		if(!preg_match("#^([0-9]+)#", $ligne,$re)){continue;}
		$ppid=$GLOBALS["CLASS_UNIX"]->PPID_OF($re[1]);
		if($ppid==$re[1]){return $re[1];}
		return $ppid;
	}
	
}


function remove_files(){
	if(is_file("/etc/httpd/conf.d/README")){@unlink("/etc/httpd/conf.d/README");}
}

function patch_suse_default_server(){
		$tmp123=@file_get_contents("/etc/apache2/default-server.conf");
		$tmp123=str_replace("/srv/www/htdocs","/var/www",$tmp123);
		$tmp123=str_replace("/srv/www/","/var/www/",$tmp123);
		$tmp123=str_replace("Options None","Options Indexes FollowSymLinks MultiViews",$tmp123);
		$tmp123=str_replace("Include /etc/apache2/conf.d/*.conf","",$tmp123);
		$tmp123=str_replace("Include /etc/apache2/mod_userdir.conf","",$tmp123);
		@file_put_contents("/etc/apache2/default-server.conf", $tmp123);$tmp123=null;	
}

function php5_fpm(){
	$unix=new unix();
	$daemon_path=$unix->APACHE_LOCATE_PHP_FPM();
	if(!is_file($daemon_path)){return;}
	$f[]="# PHP-FPM configuration";
	$f[]="<IfModule mod_fastcgi.c>";
	$f[]="  Alias /php5.fastcgi /var/lib/apache2/fastcgi/php5.fastcgi";
	$f[]="  AddHandler php-script .php";
	$f[]="  FastCGIExternalServer /var/lib/apache2/fastcgi/php5.fastcgi -socket /var/run/php-fpm-apache2.sock -idle-timeout 610";
	$f[]="  Action php-script /php5.fastcgi virtual";
	$f[]="";
	$f[]="  # Forbid access to the fastcgi handler.";
	$f[]="  <Directory /var/lib/apache2/fastcgi>";
	$f[]="    <Files php5.fastcgi>";
	$f[]="      Order deny,allow";
	$f[]="      Allow from all";
	$f[]="    </Files>";
	$f[]="  </Directory>";
	$f[]="";
	$f[]="  # FPM status page.";
	$f[]="  <Location /php-fpm-status>";
	$f[]="    SetHandler php-script";
	$f[]="    Order deny,allow";
	$f[]="    Deny from all";
	$f[]="    Allow from 127.0.0.1 ::1";
	$f[]="  </Location>";
	$f[]="";
	$f[]="  # FPM ping page.";
	$f[]="  <Location /php-fpm-ping>";
	$f[]="    SetHandler php-script";
	$f[]="    Order deny,allow";
	$f[]="    Deny from all";
	$f[]="    Allow from 127.0.0.1 ::1";
	$f[]="  </Location>";
	$f[]="</IfModule>";

	@file_put_contents("/etc/apache2/mods-available/php5-fpm.conf", @implode("\n", $f));
	
}



function build(){
	$unix=new unix();
	if(is_file("/etc/artica-postfix/FROM_ISO")){
		if($unix->file_time_min("/etc/artica-postfix/FROM_ISO")<1){return;}
	}
	$mef=basename(__FILE__);
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,$mef)){
		echo "Starting......: ".date("H:i:s")." [INIT]: Apache building : Process Already exist pid $pid line:".__LINE__."\n";
		return;
	}	
	@file_put_contents($pidfile, getmypid());		
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [DEBUG]: Apache -> CheckHttpdConf();\n";}
	CheckHttpdConf();
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [DEBUG]: Apache -> RemoveAllSites();\n";}
	RemoveAllSites();
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [DEBUG]: Apache -> create_cron_task();\n";}
	create_cron_task();
	sync_squid();
	$sock=new sockets();
	$FreeWebDisableSSL=intval(trim($sock->GET_INFO("FreeWebDisableSSL")));
	$php5=$unix->LOCATE_PHP5_BIN();
	$varWwwPerms=$sock->GET_INFO("varWwwPerms");
	if($varWwwPerms==null){$varWwwPerms=755;}
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [DEBUG]: Apache -> remove_files();\n";}
	remove_files();
	$sql="SELECT * FROM freeweb ORDER BY servername";
	$httpdconf=$unix->LOCATE_APACHE_CONF_PATH();
	$apacheusername=$unix->APACHE_SRC_ACCOUNT();
	$GLOBALS["apacheusername"]=$apacheusername;
	$DAEMON_PATH=$unix->getmodpathfromconf($httpdconf);
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [DEBUG]: Apache -> sql();\n";}
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [DEBUG]: Apache $q->mysql_error\n";return;}}
	$d_path=$unix->APACHE_DIR_SITES_ENABLED();
	$mods_enabled=$DAEMON_PATH."/mods-enabled";
	if($FreeWebDisableSSL==0){
		SSL_DEFAULT_VIRTUAL_HOST();
	}
	
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache daemon path: $d_path\n";
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache mods path..: $mods_enabled\n";
	
	if(!is_dir($d_path)){@mkdir($d_path,666,true);}
	if(!is_dir($mods_enabled)){@mkdir($mods_enabled,666,true);}
	
	$count=mysql_num_rows($results);
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache checking virtual web sites count:$count\n";
	if($count==0){
		$users=new usersMenus();
		echo "Starting......: ".date("H:i:s")." [INIT]: Apache building default $users->hostname...\n";
		
		buildHost($unix->LIGHTTPD_USER(),$users->hostname,0,$d_path);
	}
	
	if($GLOBALS["VERBOSE"]){$add_plus=" --verbose";}
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$uid=$ligne["uid"];
		$hostname=$ligne["servername"];
		$ssl=$ligne["useSSL"];	
		if($FreeWebDisableSSL==1){$ssl=0;}
		
		echo "Starting......: ".date("H:i:s")." [INIT]: Apache \"$hostname\" starting\n";
		
		$cmd="$php5 ".__FILE__." --sitename \"$hostname\" --no-httpd-conf --noreload$add_plus";
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [INIT]: Apache \"$cmd\"\n";}
		shell_exec($cmd);
	}
	
	$users=$GLOBALS["CLASS_USERS_MENUS"];
	$APACHE_MOD_AUTHNZ_LDAP=$users->APACHE_MOD_AUTHNZ_LDAP;
	if(is_file($GLOBALS["a2enmod"])){
		if($APACHE_MOD_AUTHNZ_LDAP){
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [INIT]: Apache {$GLOBALS["a2enmod"]} authnz_ldap\n";} 
			shell_exec("{$GLOBALS["a2enmod"]} authnz_ldap >/dev/null 2>&1");
		}
	} 
	
	

	$sock=$GLOBALS["CLASS_SOCKETS"];
	if($sock->GET_INFO("ArticaMetaEnabled")==1){
		sys_THREAD_COMMAND_SET(LOCATE_PHP5_BIN()." ".dirname(__FILE__)."/exec.artica.meta.users.php --export-freewebs");
	}
	
	sys_THREAD_COMMAND_SET(LOCATE_PHP5_BIN()." ".__FILE__." --monit");

}

function RemoveAllSites(){
	$unix=new unix();	
	$httpdconf=$unix->LOCATE_APACHE_CONF_PATH();
	$DAEMON_PATH=$unix->getmodpathfromconf($httpdconf);
	$sites_enabled="$DAEMON_PATH/sites-enabled";
	if(!is_dir("$sites_enabled")){@mkdir($sites_enabled,666,true);}
	
	foreach (glob("$sites_enabled/artica-*.conf") as $filename) {
		$file=basename($filename);
		@unlink($filename);
		echo "Starting......: ".date("H:i:s")." [INIT]: Apache remove $file done\n";
	}		
}


function SSL_DEFAULT_VIRTUAL_HOST(){
	$ZarafaWebAccessInFrontEnd=0;
	$sock=new sockets();
	$free=new freeweb("_default_");
	if($free->useSSL==1){return;}
	$users=new usersMenus();
	$unix=new unix();
	
	$workingDir=$free->WORKING_DIRECTORY;
	if($workingDir==null){$workingDir="/usr/share/artica-postfix";}
	@mkdir($workingDir,0755,true);
	$unix=new unix();

	$httpdconf=$unix->LOCATE_APACHE_CONF_PATH();
	$DAEMON_PATH=$unix->getmodpathfromconf($httpdconf);
	$sites_enabled="$DAEMON_PATH/sites-enabled";	
	$unix->vhosts_BuildCertificate("_default_");
	$hostname=$unix->hostname_g();
	
	$FreeWebListenSSLPort=$sock->GET_INFO("FreeWebListenSSLPort");
	if(!is_numeric($FreeWebListenSSLPort)){$FreeWebListenSSLPort=443;}
	$FreeWebListenPort=$sock->GET_INFO("FreeWebListenPort");
	if(!is_numeric($FreeWebListenPort)){$FreeWebListenPort=80;}
	
	if($unix->isNGnx()){
		echo "Starting......: ".date("H:i:s")." [INIT]: Apache Nginx is Enabled set port to 447/82\n";
		$FreeWebListenSSLPort=447;
		$FreeWebListenPort=82;
	
	}
	
	
	
	
	
	if($users->ZARAFA_INSTALLED){
		$ZarafaWebAccessInFrontEnd=$sock->GET_INFO("ZarafaWebAccessInFrontEnd");
		if(!is_numeric($ZarafaWebAccessInFrontEnd)){$ZarafaWebAccessInFrontEnd=1;}
		if($ZarafaWebAccessInFrontEnd==1){
			$workingDir="/usr/share/zarafa-webaccess";
		}
	}
	
	
	
	
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache hostname = $hostname:$FreeWebListenSSLPort\n";
	$f[]="<IfModule mod_ssl.c>";
	$f[]="    <VirtualHost _default_:$FreeWebListenSSLPort>";
	$f[]="            ServerAdmin webmaster@$hostname";
	$f[]="            DocumentRoot $workingDir";
	$f[]="            <Directory />";
	$f[]="                    Options -Indexes +FollowSymLinks";
	$f[]="                    AllowOverride None";
	$f[]="            </Directory>";
	$f[]="            <Directory $workingDir/>";
	$f[]="                    Options Indexes FollowSymLinks MultiViews";
	if($ZarafaWebAccessInFrontEnd==1){
		$f[]="\t\tphp_flag register_globals off";
		$f[]="\t\tphp_flag magic_quotes_gpc off";
		$f[]="\t\tphp_flag magic_quotes_runtime off";
		$f[]="\t\tphp_value post_max_size 50M";
		$f[]="\t\tphp_value upload_max_filesize 50M";
		$f[]="\t\tphp_flag short_open_tag on";		
	}
	$f[]="                    AllowOverride None";
	$f[]="                    Order allow,deny";
	$f[]="                    allow from all";
	$f[]="            </Directory>";

	$f[]="            ErrorLog /var/log/apache2/error.log";
	$f[]="            LogLevel warn";
	$f[]="            CustomLog /var/log/apache2/ssl_access.log combined";
	$f[]="            SSLEngine on";
	
	$apache_certificate=new apache_certificate();
	$f[]=$apache_certificate->build();
	$f[]="    </VirtualHost>";
	$f[]="</IfModule>";
	$f[]="";	
	
	@file_put_contents("/etc/apache2/sites-enabled/default-ssl", @implode("\n", $f));
	if("$DAEMON_PATH/sites-enabled/default-ssl"<>"/etc/apache2/sites-enabled/default-ssl"){
		@file_put_contents("$DAEMON_PATH/sites-enabled/default-ssl", @implode("\n", $f));
	}
	
	$f=array();
	$f[]="<VirtualHost _default_:$FreeWebListenPort>";
	$f[]="ServerAdmin webmaster@_default_";
	$f[]="ServerSignature On";	
	$f[]="</VirtualHost>";
	@file_put_contents("/etc/apache2/sites-enabled/000-default", @implode("\n", $f));
	
	$IPS=$unix->NETWORK_ALL_INTERFACES(true);
	while (list ($ipadr, $none) = each ($IPS) ){
		if($ipadr=="127.0.0.1"){continue;}
		$path="/etc/apache2/sites-enabled/artica-$ipadr.conf";
		@file_put_contents($path, @implode("\n", $f));
	}
	
	
}

function CheckHttpdConf_mailman(){
	@unlink("/etc/apache2/mailman.conf");	
	$users=new usersMenus();
	if(!$users->MAILMAN_INSTALLED){return ;}
	
	if(!is_dir("/usr/lib/cgi-bin/mailman")){echo "/usr/lib/cgi-bin/mailman no such directory !!!!!\n";return;}
	if(!is_dir("/var/lib/mailman/archives/public")){@mkdir("/var/lib/mailman/archives/public",0755,true);}
	
	$f[]="<Directory /usr/lib/cgi-bin/mailman>";
	$f[]="   AllowOverride All";
	$f[]="   Options MultiViews -Indexes Includes FollowSymLinks";
	$f[]="       <IfModule mod_access.c>";
	$f[]="           Order allow,deny";
	$f[]="           Allow from all";
	$f[]="       </IfModule>";
	$f[]="</Directory>";
	$f[]="<Directory /var/lib/mailman/archives/public>";
	$f[]="   AllowOverride All";
	$f[]="   Options MultiViews -Indexes Includes FollowSymLinks";
	$f[]="       <IfModule mod_access.c>";
	$f[]="           Order allow,deny";
	$f[]="           Allow from all";
	$f[]="       </IfModule>";
	$f[]="</Directory>";
	$f[]="<Directory /usr/share/images/mailman>";
	$f[]="   AllowOverride All";
	$f[]="   Options MultiViews -Indexes Includes FollowSymLinks";
	$f[]="       <IfModule mod_access.c>";
	$f[]="           Order allow,deny";
	$f[]="           Allow from all";
	$f[]="       </IfModule>";
	$f[]="</Directory>";
	@file_put_contents("/etc/apache2/mailman.conf", @implode("\n", $f));	
	
}


function php5_conf($DAEMON_PATH){
	$f[]="<IfModule mod_php5.c>";
	$f[]="    <FilesMatch \"\.ph(p3?|tml)$\">";
	$f[]="	SetHandler application/x-httpd-php";
	$f[]="    </FilesMatch>";
	$f[]="    <FilesMatch \"\.phps$\">";
	$f[]="	SetHandler application/x-httpd-php-source";
	$f[]="    </FilesMatch>";
	$f[]="    # To re-enable php in user directories comment the following lines";
	$f[]="    # (from <IfModule ...> to </IfModule>.) Do NOT set it to On as it";
	$f[]="    # prevents .htaccess files from disabling it.";
	$f[]="    <IfModule mod_userdir.c>";
	$f[]="        <Directory /home/*/public_html>";
	$f[]="            php_admin_value engine Off";
	$f[]="        </Directory>";
	$f[]="    </IfModule>";
	$f[]="</IfModule>";	
	
	@file_put_contents("$DAEMON_PATH/mods-enabled/mod_php5.conf", @implode("\n", $f));
	
	$f=array();
	$f[]="<IfModule mod_suphp.c>";
	$f[]="	AddType application/x-httpd-suphp .php .php3 .php4 .php5 .phtml";
	$f[]="	suPHP_AddHandler application/x-httpd-suphp";
	$f[]="";
	$f[]="    <Directory />";
	$f[]="        suPHP_Engine on";
	$f[]="    </Directory>";
	$f[]="";
	$f[]="    # By default, disable suPHP for debian packaged web applications as files";
	$f[]="    # are owned by root and cannot be executed by suPHP because of min_uid.";
	$f[]="    <Directory /usr/share>";
	$f[]="        suPHP_Engine off";
	$f[]="    </Directory>";
	$f[]="";
	$f[]="# # Use a specific php config file (a dir which contains a php.ini file)";
	$f[]="#	suPHP_ConfigPath /etc/php4/cgi/suphp/";
	$f[]="# # Tells mod_suphp NOT to handle requests with the type <mime-type>.";
	$f[]="#	suPHP_RemoveHandler <mime-type>";
	$f[]="</IfModule>";;
	@file_put_contents("$DAEMON_PATH/mods-enabled/suphp.conf", @implode("\n", $f));
	
	
	$f=array();
	$unix=new unix();
	$APACHE_SRC_ACCOUNT=$unix->APACHE_SRC_ACCOUNT();
	$APACHE_SRC_GROUP=$unix->APACHE_SRC_GROUP();
	
	
	
	$f[]="[global]";
	$f[]="logfile=/var/log/apache2/suphp.log";
	$f[]="loglevel=info";
	$f[]="webserver_user=$APACHE_SRC_ACCOUNT";
	$f[]="docroot=/";
	$f[]=";chroot=/mychroot";
	$f[]="; Security options";
	$f[]="allow_file_group_writeable=false";
	$f[]="allow_file_others_writeable=false";
	$f[]="allow_directory_group_writeable=false";
	$f[]="allow_directory_others_writeable=false";
	$f[]=";Check wheter script is within DOCUMENT_ROOT";
	$f[]="check_vhost_docroot=true";
	$f[]=";Send minor error messages to browser";
	$f[]="errors_to_browser=false";
	$f[]=";PATH environment variable";
	$f[]="env_path=/bin:/usr/bin:/usr/local/bin:/usr/local/bin";
	$f[]=";Umask to set, specify in octal notation";
	$f[]="umask=0077";
	$f[]="; Minimum UID";
	$f[]="min_uid=30";
	$f[]="; Minimum GID";
	$f[]="min_gid=30";
	$f[]="[handlers]";
	$f[]=";Handler for php-scripts";
	$f[]="application/x-httpd-suphp=\"php:/usr/bin/php-cgi\"";
	$f[]=";Handler for CGI-scripts";
	$f[]="x-suphp-cgi=\"execute:!self\"";
	$f[]="";	
	@mkdir("/etc/suphp",0755,true);
	@file_put_contents("/etc/suphp/suphp.conf", @implode("\n", $f));
}

function mime_types($path){
	$f[]="# Fixed Mime types by Artica on ". date("Y-m-d H:i:s");
	$f[]="application/andrew-inset				ez";
	$f[]="application/annodex					anx";
	$f[]="application/atom+xml					atom";
	$f[]="application/atomcat+xml				atomcat";
	$f[]="application/atomserv+xml				atomsrv";
	$f[]="application/bbolin					lin";
	$f[]="application/cap						cap pcap";
	$f[]="application/cu-seeme					cu";
	$f[]="application/davmount+xml				davmount";
	$f[]="application/dsptype					tsp";
	$f[]="application/ecmascript				es";
	$f[]="application/futuresplash				spl";
	$f[]="application/hta						hta";
	$f[]="application/java-archive				jar";
	$f[]="application/java-serialized-object	ser";
	$f[]="application/java-vm					class";
	$f[]="application/javascript				js";
	$f[]="application/m3g						m3g";
	$f[]="application/mac-binhex40				hqx";
	$f[]="application/mac-compactpro			cpt";
	$f[]="application/mathematica				nb nbp";
	$f[]="application/msaccess					mdb";
	$f[]="application/msword					doc dot";
	$f[]="application/mxf						mxf";
	$f[]="application/octet-stream				bin";
	$f[]="application/oda						oda";
	$f[]="application/ogg						ogx";
	$f[]="application/pdf						pdf";
	$f[]="application/pgp-keys					key";
	$f[]="application/pgp-signature				pgp";
	$f[]="application/pics-rules				prf";
	$f[]="application/octet-stream				ovf ova xva hdx";
	$f[]="application/postscript				ps ai eps epsi epsf eps2 eps3";
	$f[]="application/rar						rar";
	$f[]="application/rdf+xml					rdf";
	$f[]="application/rss+xml					rss";
	$f[]="application/rtf						rtf";
	$f[]="application/smil						smi smil";
	$f[]="application/xhtml+xml					xhtml xht";
	$f[]="application/xml						xml xsl xsd";
	$f[]="application/xspf+xml					xspf";
	$f[]="application/zip						zip";
	$f[]="application/vnd.android.package-archive		apk";
	$f[]="application/vnd.cinderella			cdy";
	$f[]="application/vnd.google-earth.kml+xml	kml";
	$f[]="application/vnd.google-earth.kmz		kmz";
	$f[]="application/vnd.mozilla.xul+xml		xul";
	$f[]="application/vnd.ms-excel				xls xlb xlt";
	$f[]="application/vnd.ms-pki.seccat			cat";
	$f[]="application/vnd.ms-pki.stl			stl";
	$f[]="application/vnd.ms-powerpoint			ppt pps";
	$f[]="application/vnd.oasis.opendocument.chart			odc";
	$f[]="application/vnd.oasis.opendocument.database			odb";
	$f[]="application/vnd.oasis.opendocument.formula			odf";
	$f[]="application/vnd.oasis.opendocument.graphics			odg";
	$f[]="application/vnd.oasis.opendocument.graphics-template		otg";
	$f[]="application/vnd.oasis.opendocument.image			odi";
	$f[]="application/vnd.oasis.opendocument.presentation			odp";
	$f[]="application/vnd.oasis.opendocument.presentation-template	otp";
	$f[]="application/vnd.oasis.opendocument.spreadsheet			ods";
	$f[]="application/vnd.oasis.opendocument.spreadsheet-template		ots";
	$f[]="application/vnd.oasis.opendocument.text				odt";
	$f[]="application/vnd.oasis.opendocument.text-master			odm";
	$f[]="application/vnd.oasis.opendocument.text-template		ott";
	$f[]="application/vnd.oasis.opendocument.text-web			oth";
	$f[]="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet		xlsx";
	$f[]="application/vnd.openxmlformats-officedocument.spreadsheetml.template		xltx";
	$f[]="application/vnd.openxmlformats-officedocument.presentationml.presentation	pptx";
	$f[]="application/vnd.openxmlformats-officedocument.presentationml.slideshow		ppsx";
	$f[]="application/vnd.openxmlformats-officedocument.presentationml.template		potx";
	$f[]="application/vnd.openxmlformats-officedocument.wordprocessingml.document		docx";
	$f[]="application/vnd.openxmlformats-officedocument.wordprocessingml.template		dotx";
	$f[]="application/vnd.rim.cod				cod";
	$f[]="application/vnd.smaf					mmf";
	$f[]="application/vnd.stardivision.calc		sdc";
	$f[]="application/vnd.stardivision.chart	sds";
	$f[]="application/vnd.stardivision.draw		sda";
	$f[]="application/vnd.stardivision.impress	sdd";
	$f[]="application/vnd.stardivision.math		sdf";
	$f[]="application/vnd.stardivision.writer	sdw";
	$f[]="application/vnd.stardivision.writer-global	sgl";
	$f[]="application/vnd.street-stream";
	$f[]="application/vnd.sun.xml.calc			sxc";
	$f[]="application/vnd.sun.xml.calc.template	stc";
	$f[]="application/vnd.sun.xml.draw			sxd";
	$f[]="application/vnd.sun.xml.draw.template	std";
	$f[]="application/vnd.sun.xml.impress		sxi";
	$f[]="application/vnd.sun.xml.impress.template	sti";
	$f[]="application/vnd.sun.xml.math			sxm";
	$f[]="application/vnd.sun.xml.writer		sxw";
	$f[]="application/vnd.sun.xml.writer.global	sxg";
	$f[]="application/vnd.sun.xml.writer.template		stw";
	$f[]="application/vnd.symbian.install			sis";
	$f[]="application/vnd.visio				vsd";
	$f[]="application/vnd.wap.wbxml			wbxml";
	$f[]="application/vnd.wap.wmlc			wmlc";
	$f[]="application/vnd.wap.wmlscriptc	wmlsc";
	$f[]="application/vnd.wordperfect		wpd";
	$f[]="application/vnd.wordperfect5.1	wp5";
	$f[]="application/x-123					wk";
	$f[]="application/x-7z-compressed		7z";
	$f[]="application/x-abiword				abw";
	$f[]="application/x-apple-diskimage		dmg";
	$f[]="application/x-bcpio				bcpio";
	$f[]="application/x-bittorrent			torrent";
	$f[]="application/x-cab					cab";
	$f[]="application/x-cbr					cbr";
	$f[]="application/x-cbz					cbz";
	$f[]="application/x-cdf					cdf cda";
	$f[]="application/x-cdlink				vcd";
	$f[]="application/x-chess-pgn			pgn";
	$f[]="application/x-cpio				cpio";
	$f[]="application/x-csh					csh";
	$f[]="application/x-debian-package		deb udeb";
	$f[]="application/x-director			dcr dir dxr";
	$f[]="application/x-dms					dms";
	$f[]="application/x-doom				wad";
	$f[]="application/x-dvi					dvi";
	$f[]="application/x-httpd-eruby			rhtml";
	$f[]="application/x-executable			exe";
	$f[]="application/x-font				pfa pfb gsf pcf pcf.Z";
	$f[]="application/x-freemind			mm";
	$f[]="application/x-futuresplash		spl";
	$f[]="application/x-gnumeric			gnumeric";
	$f[]="application/x-go-sgf				sgf";
	$f[]="application/x-graphing-calculator	gcf";
	$f[]="application/x-gtar				gtar tgz taz";
	$f[]="application/x-hdf					hdf";
	$f[]="application/x-httpd-php			phtml pht php";
	$f[]="application/x-httpd-php-source	phps";
	$f[]="application/x-httpd-php3			php3";
	$f[]="application/x-httpd-php3-preprocessed		php3p";
	$f[]="application/x-httpd-php4			php4";
	$f[]="application/x-httpd-php5			php5";
	$f[]="application/x-ica					ica";
	$f[]="application/x-info				info";
	$f[]="application/x-internet-signup		ins isp";
	$f[]="application/x-iphone				iii";
	$f[]="application/x-iso9660-image		iso";
	$f[]="application/x-jam					jam";
	$f[]="application/x-java-jnlp-file		jnlp";
	$f[]="application/x-jmol				jmz";
	$f[]="application/x-kchart				chrt";
	$f[]="application/x-killustrator		kil";
	$f[]="application/x-koan				skp skd skt skm";
	$f[]="application/x-kpresenter			kpr kpt";
	$f[]="application/x-kspread				ksp";
	$f[]="application/x-kword				kwd kwt";
	$f[]="application/x-latex				latex";
	$f[]="application/x-lha					lha";
	$f[]="application/x-lyx					lyx";
	$f[]="application/x-lzh					lzh";
	$f[]="application/x-lzx					lzx";
	$f[]="application/x-maker				frm maker frame fm fb book fbdoc";
	$f[]="application/x-mif					mif";
	$f[]="application/x-ms-wmd				wmd";
	$f[]="application/x-ms-wmz				wmz";
	$f[]="application/x-msdos-program		com exe bat dll";
	$f[]="application/x-msi					msi";
	$f[]="application/x-netcdf				nc";
	$f[]="application/x-ns-proxy-autoconfig	pac dat";
	$f[]="application/x-nwc					nwc";
	$f[]="application/x-object				o";
	$f[]="application/x-oz-application		oza";
	$f[]="application/x-pkcs7-certreqresp	p7r";
	$f[]="application/x-pkcs7-crl			crl";
	$f[]="application/x-python-code			pyc pyo";
	$f[]="application/x-qgis				qgs shp shx";
	$f[]="application/x-quicktimeplayer		qtl";
	$f[]="application/x-redhat-package-manager		rpm";
	$f[]="application/x-ruby				rb";
	$f[]="application/x-sh					sh";
	$f[]="application/x-shar				shar";
	$f[]="application/x-shockwave-flash		swf swfl";
	$f[]="application/x-silverlight			scr";
	$f[]="application/x-stuffit				sit sitx";
	$f[]="application/x-sv4cpio				sv4cpio";
	$f[]="application/x-sv4crc				sv4crc";
	$f[]="application/x-tar					tar";
	$f[]="application/x-tcl					tcl";
	$f[]="application/x-tex-gf				gf";
	$f[]="application/x-tex-pk				pk";
	$f[]="application/x-texinfo				texinfo texi";
	$f[]="application/x-trash				~ % bak old sik";
	$f[]="application/x-troff				t tr roff";
	$f[]="application/x-troff-man			man";
	$f[]="application/x-troff-me			me";
	$f[]="application/x-troff-ms			ms";
	$f[]="application/x-ustar				ustar";
	$f[]="application/x-wais-source			src";
	$f[]="application/x-wingz				wz";
	$f[]="application/x-x509-ca-cert		crt";
	$f[]="application/x-xcf					xcf";
	$f[]="application/x-xfig				fig";
	$f[]="application/x-xpinstall			xpi";
	$f[]="audio/amr							amr";
	$f[]="audio/amr-wb						awb";
	$f[]="audio/amr							amr";
	$f[]="audio/amr-wb						awb";
	$f[]="audio/annodex						axa";
	$f[]="audio/basic						au snd";
	$f[]="audio/flac						flac";
	$f[]="audio/midi						mid midi kar";
	$f[]="audio/mpeg						mpga mpega mp2 mp3 m4a";
	$f[]="audio/mpegurl						m3u";
	$f[]="audio/ogg							oga ogg spx";
	$f[]="audio/prs.sid						sid";
	$f[]="audio/x-aiff						aif aiff aifc";
	$f[]="audio/x-gsm						gsm";
	$f[]="audio/x-mpegurl					m3u";
	$f[]="audio/x-ms-wma					wma";
	$f[]="audio/x-ms-wax					wax";
	$f[]="audio/x-pn-realaudio				ra rm ram";
	$f[]="audio/x-realaudio					ra";
	$f[]="audio/x-scpls						pls";
	$f[]="audio/x-sd2						sd2";
	$f[]="audio/x-wav						wav";
	$f[]="chemical/x-alchemy				alc";
	$f[]="chemical/x-cache					cac cache";
	$f[]="chemical/x-cache-csf				csf";
	$f[]="chemical/x-cactvs-binary			cbin cascii ctab";
	$f[]="chemical/x-cdx					cdx";
	$f[]="chemical/x-cerius					cer";
	$f[]="chemical/x-chem3d					c3d";
	$f[]="chemical/x-chemdraw				chm";
	$f[]="chemical/x-cif					cif";
	$f[]="chemical/x-cmdf					cmdf";
	$f[]="chemical/x-cml					cml";
	$f[]="chemical/x-compass				cpa";
	$f[]="chemical/x-crossfire				bsd";
	$f[]="chemical/x-csml					csml csm";
	$f[]="chemical/x-ctx					ctx";
	$f[]="chemical/x-cxf					cxf cef";
	$f[]="#chemical/x-daylight-smiles		smi";
	$f[]="chemical/x-embl-dl-nucleotide		emb embl";
	$f[]="chemical/x-galactic-spc			spc";
	$f[]="chemical/x-gamess-input			inp gam gamin";
	$f[]="chemical/x-gaussian-checkpoint	fch fchk";
	$f[]="chemical/x-gaussian-cube			cub";
	$f[]="chemical/x-gaussian-input			gau gjc gjf";
	$f[]="chemical/x-gaussian-log			gal";
	$f[]="chemical/x-gcg8-sequence			gcg";
	$f[]="chemical/x-genbank				gen";
	$f[]="chemical/x-hin					hin";
	$f[]="chemical/x-isostar				istr ist";
	$f[]="chemical/x-jcamp-dx				jdx dx";
	$f[]="chemical/x-kinemage				kin";
	$f[]="chemical/x-macmolecule			mcm";
	$f[]="chemical/x-macromodel-input		mmd mmod";
	$f[]="chemical/x-mdl-molfile			mol";
	$f[]="chemical/x-mdl-rdfile				rd";
	$f[]="chemical/x-mdl-rxnfile			rxn";
	$f[]="chemical/x-mdl-sdfile				sd sdf";
	$f[]="chemical/x-mdl-tgf				tgf";
	$f[]="#chemical/x-mif					mif";
	$f[]="chemical/x-mmcif					mcif";
	$f[]="chemical/x-mol2					mol2";
	$f[]="chemical/x-molconn-Z				b";
	$f[]="chemical/x-mopac-graph			gpt";
	$f[]="chemical/x-mopac-input			mop mopcrt mpc zmt";
	$f[]="chemical/x-mopac-out				moo";
	$f[]="chemical/x-mopac-vib			mvb";
	$f[]="chemical/x-ncbi-asn1			asn";
	$f[]="chemical/x-ncbi-asn1-ascii	prt ent";
	$f[]="chemical/x-ncbi-asn1-binary	val aso";
	$f[]="chemical/x-ncbi-asn1-spec		asn";
	$f[]="chemical/x-pdb				pdb ent";
	$f[]="chemical/x-rosdal				ros";
	$f[]="chemical/x-swissprot			sw";
	$f[]="chemical/x-vamas-iso14976		vms";
	$f[]="chemical/x-vmd				vmd";
	$f[]="chemical/x-xtel				xtel";
	$f[]="chemical/x-xyz				xyz";
	$f[]="image/gif						gif";
	$f[]="image/ief						ief";
	$f[]="image/jpeg					jpeg jpg jpe";
	$f[]="image/pcx						pcx";
	$f[]="image/png						png";
	$f[]="image/svg+xml					svg svgz";
	$f[]="image/tiff					tiff tif";
	$f[]="image/vnd.djvu				djvu djv";
	$f[]="image/vnd.wap.wbmp			wbmp";
	$f[]="image/x-canon-cr2				cr2";
	$f[]="image/x-canon-crw				crw";
	$f[]="image/x-cmu-raster			ras";
	$f[]="image/x-coreldraw				cdr";
	$f[]="image/x-coreldrawpattern		pat";
	$f[]="image/x-coreldrawtemplate		cdt";
	$f[]="image/x-corelphotopaint		cpt";
	$f[]="image/x-epson-erf				erf";
	$f[]="image/x-icon					ico";
	$f[]="image/x-jg					art";
	$f[]="image/x-jng					jng";
	$f[]="image/x-ms-bmp				bmp";
	$f[]="image/x-nikon-nef				nef";
	$f[]="image/x-olympus-orf			orf";
	$f[]="image/x-photoshop				psd";
	$f[]="image/x-portable-anymap		pnm";
	$f[]="image/x-portable-bitmap		pbm";
	$f[]="image/x-portable-graymap		pgm";
	$f[]="image/x-portable-pixmap		ppm";
	$f[]="image/x-rgb					rgb";
	$f[]="image/x-xbitmap				xbm";
	$f[]="image/x-xpixmap				xpm";
	$f[]="image/x-xwindowdump			xwd";
	$f[]="message/rfc822				eml";
	$f[]="model/iges					igs iges";
	$f[]="model/mesh					msh mesh silo";
	$f[]="model/vrml					wrl vrml";
	$f[]="model/x3d+vrml				x3dv";
	$f[]="model/x3d+xml					x3d";
	$f[]="model/x3d+binary				x3db";
	$f[]="text/cache-manifest			manifest";
	$f[]="text/calendar					ics icz";
	$f[]="text/css						css";
	$f[]="text/csv						csv";
	$f[]="text/h323						323";
	$f[]="text/html						html htm shtml";
	$f[]="text/iuls						uls";
	$f[]="text/mathml					mml";
	$f[]="text/plain					asc txt text pot brf";
	$f[]="text/richtext					rtx";
	$f[]="text/rtf						rtf";	
	$f[]="text/scriptlet				sct wsc";
	$f[]="text/texmacs					tm ts";
	$f[]="text/tab-separated-values		tsv";
	$f[]="text/vnd.sun.j2me.app-descriptor		jad";
	$f[]="text/vnd.wap.wml				wml";
	$f[]="text/vnd.wap.wmlscript		wmls";
	$f[]="text/x-bibtex					bib";
	$f[]="text/x-boo					boo";
	$f[]="text/x-c++hdr					h++ hpp hxx hh";
	$f[]="text/x-c++src					c++ cpp cxx cc";
	$f[]="text/x-chdr					h";
	$f[]="text/x-component				htc";
	$f[]="text/x-csh					csh";
	$f[]="text/x-csrc					c";
	$f[]="text/x-dsrc					d";
	$f[]="text/x-diff					diff patch";
	$f[]="text/x-haskell				hs";
	$f[]="text/x-java					java";
	$f[]="text/x-literate-haskell		lhs";
	$f[]="text/x-makefile				make";
	$f[]="text/x-moc					moc";
	$f[]="text/x-pascal					p pas";
	$f[]="text/x-pcs-gcd				gcd";
	$f[]="text/x-perl					pl pm";
	$f[]="text/x-python					py";
	$f[]="text/x-scala					scala";
	$f[]="text/x-setext					etx";
	$f[]="text/x-sh						sh";
	$f[]="text/x-tcl					tcl tk";
	$f[]="text/x-tex					tex ltx sty cls";
	$f[]="text/x-vcalendar				vcs";
	$f[]="text/x-vcard					vcf";
	$f[]="video/3gpp					3gp";
	$f[]="video/annodex					axv";
	$f[]="video/dl						dl";
	$f[]="video/dv						dif dv";
	$f[]="video/fli						fli";
	$f[]="video/gl						gl";
	$f[]="video/mpeg					mpeg mpg mpe";
	$f[]="video/mp4						mp4";
	$f[]="video/quicktime				qt mov";
	$f[]="video/ogg						ogv";
	$f[]="video/vnd.mpegurl				mxu";
	$f[]="video/x-flv					flv";
	$f[]="video/x-la-asf				lsf lsx";
	$f[]="video/x-mng					mng";
	$f[]="video/x-ms-asf				asf asx";
	$f[]="video/x-ms-wm					wm";
	$f[]="video/x-ms-wmv				wmv";
	$f[]="video/x-ms-wmx				wmx";
	$f[]="video/x-ms-wvx				wvx";
	$f[]="video/x-msvideo				avi";
	$f[]="video/x-sgi-movie				movie";
	$f[]="video/x-matroska				mpv mkv";
	$f[]="x-conference/x-cooltalk		ice";
	$f[]="x-epoc/x-sisx-app				sisx";
	$f[]="x-world/x-vrml				vrm vrml wrl";	
	@file_put_contents($path, @implode("\n", $f));
	@chmod($path,0755);
	
}


function CheckHttpdConf_mime_module(){
	$unix=new unix();
	$httpdconf=$unix->LOCATE_APACHE_CONF_PATH();
	$DAEMON_PATH=$unix->getmodpathfromconf($httpdconf);
	mime_types("$DAEMON_PATH/mime.types");
	
	
$f[]="<IfModule mod_mime.c>";
$f[]="\tTypesConfig /etc/mime.types";
$f[]="\tAddType application/octet-stream 			.acl";
$f[]="\tAddType application/octet-stream					.ova";
$f[]="\tAddType application/octet-stream					.ovf";
$f[]="\tAddType application/octet-stream					.xva";
$f[]="\tAddType application/octet-stream					.hdx";
$f[]="\tAddType application/x-gzip 					.tgz";
$f[]="\tAddType text/html 							.html .htm";
$f[]="\tAddType application/x-shockwave-flash 		.swf ";
$f[]="\tAddType text/plain 							.txt";
$f[]="\tAddType text/richtext 						.rtx";
$f[]="\tAddType text/tab-separated-values 			.tsv";
$f[]="\tAddType text/x-setext 						.etx";
$f[]="\tAddType text/x-server-parsed-html 			.shtml .sht";
$f[]="\tAddType application/macbinhex-40 			.hqx";
$f[]="\tAddType application/netalivelink 			.nel";
$f[]="\tAddType application/netalive 				.net";
$f[]="\tAddType application/octet-stream 			.bin .exe";
$f[]="\tAddType application/oda 					.oda";
$f[]="\tAddType application/pdf 					.pdf";
$f[]="\tAddType application/postscript 				.ai .eps .ps";
$f[]="\tAddType application/rtf 					.rtf";
$f[]="\tAddType application/zip 					.zip";
$f[]="\tAddType application/x-mif 					.mif";
$f[]="\tAddType application/x-csh 					.csh";
$f[]="\tAddType application/x-dvi 					.dvi";
$f[]="\tAddType application/x-hdf 					.hdf";
$f[]="\tAddType application/x-latex 				.latex";
$f[]="\tAddType application/x-netcdf 				.nc .cdf";
$f[]="\tAddType application/x-sh 					.sh";
$f[]="\tAddType application/x-tcl .tcl";
$f[]="\tAddType application/x-tex .tex";
$f[]="\tAddType application/x-texinfo .texinfo .texi";
$f[]="\tAddType application/x-troff .t .tr .roff";
$f[]="\tAddType application/x-troff-man .man";
$f[]="\tAddType application/x-troff-me .me";
$f[]="\tAddType application/x-troff-ms .ms";
$f[]="\tAddType application/x-wais-source .src";
$f[]="\tAddType application/x-bcpio .bcpio";
$f[]="\tAddType application/x-cpio .cpio";
$f[]="\tAddType application/x-gtar .gtar";
$f[]="\tAddType application/x-shar .shar";
$f[]="\tAddType application/x-sv4cpio .sv4cpio";
$f[]="\tAddType application/x-sv4crc .sv4crc";
$f[]="\tAddType application/x-tar .tar";
$f[]="\tAddType application/x-ustar .ustar";
$f[]="\tAddType application/x-director .dcr";
$f[]="\tAddType application/x-director .dir";
$f[]="\tAddType application/x-director .dxr";
$f[]="\tAddType application/x-onlive .sds";
$f[]="\tAddType application/x-httpd-cgi .cgi";
$f[]="\tAddType image/gif .gif .GIF";
$f[]="\tAddType image/ief .ief";
$f[]="\tAddType image/jpeg .jpeg .jpg .jpe .JPG";
$f[]="\tAddType image/tiff .tiff .tif";
$f[]="\tAddType image/x-cmu-raster .ras";
$f[]="\tAddType image/x-portable-anymap .pnm";
$f[]="\tAddType image/x-portable-bitmap .pbm";
$f[]="\tAddType image/x-portable-graymap .pgm";
$f[]="\tAddType image/x-portable-pixmap .ppm";
$f[]="\tAddType image/x-rgb .rgb";
$f[]="\tAddType image/x-xbitmap .xbm";
$f[]="\tAddType image/x-xpixmap .xpm";
$f[]="\tAddType image/x-xwindowdump .xwd";
$f[]="\tAddType audio/basic .au .snd";
$f[]="\tAddType audio/x-aiff .aif .aiff .aifc";
$f[]="\tAddType audio/x-wav .wav";
$f[]="\tAddType audio/x-pn-realaudio .ram";
$f[]="\tAddType audio/x-midi .mid";
$f[]="\tAddType video/mpeg .mpeg .mpg .mpe";
$f[]="\tAddType video/quicktime .qt .mov";
$f[]="\tAddType video/x-msvideo .avi";
$f[]="\tAddType video/x-sgi-movie .movie";
$f[]="\tAddType x-world/x-vrml .wrl";
$f[]="\tAddType application/x-compress .Z";
$f[]="\tAddType application/x-gzip .gz .tgz";
$f[]="\tAddType application/x-bzip2 .bz2";
$f[]="";
$f[]="#";
$f[]="# DefaultLanguage and AddLanguage allows you to specify the language of ";
$f[]="# a document. You can then use content negotiation to give a browser a ";
$f[]="# file in a language the user can understand.";
$f[]="#";
$f[]="# Specify a default language. This means that all data";
$f[]="# going out without a specific language tag (see below) will ";
$f[]="# be marked with this one. You probably do NOT want to set";
$f[]="# this unless you are sure it is correct for all cases.";
$f[]="#";
$f[]="# * It is generally better to not mark a page as ";
$f[]="# * being a certain language than marking it with the wrong";
$f[]="# * language!";
$f[]="#";
$f[]="# DefaultLanguage us";
$f[]="#";
$f[]="# Note 1: The suffix does not have to be the same as the language";
$f[]="# keyword --- those with documents in Polish (whose net-standard";
$f[]="# language code is pl) may wish to use \"AddLanguage pl .po\" to";
$f[]="# avoid the ambiguity with the common suffix for perl scripts.";
$f[]="#";
$f[]="# Note 2: The example entries below illustrate that in some cases ";
$f[]="# the two character 'Language' abbreviation is not identical to ";
$f[]="# the two character 'Country' code for its country,";
$f[]="# E.g. 'Danmark/dk' versus 'Danish/da'.";
$f[]="#";
$f[]="# Note 3: In the case of 'ltz' we violate the RFC by using a three char";
$f[]="# specifier. There is 'work in progress' to fix this and get";
$f[]="# the reference data for rfc1766 cleaned up.";
$f[]="#";
$f[]="# Catalan (ca) - Croatian (hr) - Czech (cs) - Danish (da) - Dutch (nl)";
$f[]="# English (en) - Esperanto (eo) - Estonian (et) - French (fr) - German (de)";
$f[]="# Greek-Modern (el) - Hebrew (he) - Italian (it) - Japanese (ja)";
$f[]="# Korean (ko) - Luxembourgeois* (ltz) - Norwegian Nynorsk (nn)";
$f[]="# Norwegian (no) - Polish (pl) - Portugese (pt)";
$f[]="# Brazilian Portuguese (pt-BR) - Russian (ru) - Swedish (sv)";
$f[]="# Simplified Chinese (zh-CN) - Spanish (es) - Traditional Chinese (zh-TW)";
$f[]="#";
$f[]="AddLanguage am .amh";
$f[]="AddLanguage ar .ara";
$f[]="AddLanguage be .be";
$f[]="AddLanguage bg .bg";
$f[]="AddLanguage bn .bn";
$f[]="AddLanguage br .br";
$f[]="AddLanguage bs .bs";
$f[]="AddLanguage ca .ca";
$f[]="AddLanguage cs .cz .cs";
$f[]="AddLanguage cy .cy";
$f[]="AddLanguage da .dk";
$f[]="AddLanguage de .de";
$f[]="AddLanguage dz .dz";
$f[]="AddLanguage el .el";
$f[]="AddLanguage en .en";
$f[]="AddLanguage eo .eo";
$f[]="# es is ecmascript in /etc/mime.types";
$f[]="RemoveType  es";
$f[]="AddLanguage es .es";
$f[]="AddLanguage et .et";
$f[]="AddLanguage eu .eu";
$f[]="AddLanguage fa .fa";
$f[]="AddLanguage fi .fi";
$f[]="AddLanguage fr .fr";
$f[]="AddLanguage ga .ga";
$f[]="AddLanguage gl .glg";
$f[]="AddLanguage gu .gu";
$f[]="AddLanguage he .he";
$f[]="AddLanguage hi .hi";
$f[]="AddLanguage hr .hr";
$f[]="AddLanguage hu .hu";
$f[]="AddLanguage hy .hy";
$f[]="AddLanguage id .id";
$f[]="AddLanguage is .is";
$f[]="AddLanguage it .it";
$f[]="AddLanguage ja .ja";
$f[]="AddLanguage ka .ka";
$f[]="AddLanguage kk .kk";
$f[]="AddLanguage km .km";
$f[]="AddLanguage kn .kn";
$f[]="AddLanguage ko .ko";
$f[]="AddLanguage ku .ku";
$f[]="AddLanguage lo .lo";
$f[]="AddLanguage lt .lt";
$f[]="AddLanguage ltz .ltz";
$f[]="AddLanguage lv .lv";
$f[]="AddLanguage mg .mg";
$f[]="AddLanguage mk .mk";
$f[]="AddLanguage ml .ml";
$f[]="AddLanguage mr .mr";
$f[]="AddLanguage ms .msa";
$f[]="AddLanguage nb .nob";
$f[]="AddLanguage ne .ne";
$f[]="AddLanguage nl .nl";
$f[]="AddLanguage nn .nn";
$f[]="AddLanguage no .no";
$f[]="AddLanguage pa .pa";
$f[]="AddLanguage pl .po";
$f[]="AddLanguage pt-BR .pt-br";
$f[]="AddLanguage pt .pt";
$f[]="AddLanguage ro .ro";
$f[]="AddLanguage ru .ru";
$f[]="AddLanguage sa .sa";
$f[]="AddLanguage se .se";
$f[]="AddLanguage si .si";
$f[]="AddLanguage sk .sk";
$f[]="AddLanguage sl .sl";
$f[]="AddLanguage sq .sq";
$f[]="AddLanguage sr .sr";
$f[]="AddLanguage sv .sv";
$f[]="AddLanguage ta .ta";
$f[]="AddLanguage te .te";
$f[]="AddLanguage th .th";
$f[]="AddLanguage tl .tl";
$f[]="RemoveType  tr";
$f[]="# tr is troff in /etc/mime.types";
$f[]="AddLanguage tr .tr";
$f[]="AddLanguage uk .uk";
$f[]="AddLanguage ur .ur";
$f[]="AddLanguage vi .vi";
$f[]="AddLanguage wo .wo";
$f[]="AddLanguage xh .xh";
$f[]="AddLanguage zh-CN .zh-cn";
$f[]="AddLanguage zh-TW .zh-tw";
$f[]="";
$f[]="#";
$f[]="# Commonly used filename extensions to character sets. You probably";
$f[]="# want to avoid clashes with the language extensions, unless you";
$f[]="# are good at carefully testing your setup after each change.";
$f[]="# See http://www.iana.org/assignments/character-sets for the";
$f[]="# official list of charset names and their respective RFCs.";
$f[]="#";
$f[]="AddCharset us-ascii    .ascii .us-ascii";
$f[]="AddCharset ISO-8859-1  .iso8859-1  .latin1";
$f[]="AddCharset ISO-8859-2  .iso8859-2  .latin2 .cen";
$f[]="AddCharset ISO-8859-3  .iso8859-3  .latin3";
$f[]="AddCharset ISO-8859-4  .iso8859-4  .latin4";
$f[]="AddCharset ISO-8859-5  .iso8859-5  .cyr .iso-ru";
$f[]="AddCharset ISO-8859-6  .iso8859-6  .arb .arabic";
$f[]="AddCharset ISO-8859-7  .iso8859-7  .grk .greek";
$f[]="AddCharset ISO-8859-8  .iso8859-8  .heb .hebrew";
$f[]="AddCharset ISO-8859-9  .iso8859-9  .latin5 .trk";
$f[]="AddCharset ISO-8859-10  .iso8859-10  .latin6";
$f[]="AddCharset ISO-8859-13  .iso8859-13";
$f[]="AddCharset ISO-8859-14  .iso8859-14  .latin8";
$f[]="AddCharset ISO-8859-15  .iso8859-15  .latin9";
$f[]="AddCharset ISO-8859-16  .iso8859-16  .latin10";
$f[]="AddCharset ISO-2022-JP .iso2022-jp .jis";
$f[]="AddCharset ISO-2022-KR .iso2022-kr .kis";
$f[]="AddCharset ISO-2022-CN .iso2022-cn .cis";
$f[]="AddCharset Big5        .Big5       .big5 .b5";
$f[]="AddCharset cn-Big5     .cn-big5";
$f[]="# For russian, more than one charset is used (depends on client, mostly):";
$f[]="AddCharset WINDOWS-1251 .cp-1251   .win-1251";
$f[]="AddCharset CP866       .cp866";
$f[]="AddCharset KOI8      .koi8";
$f[]="AddCharset KOI8-E      .koi8-e";
$f[]="AddCharset KOI8-r      .koi8-r .koi8-ru";
$f[]="AddCharset KOI8-U      .koi8-u";
$f[]="AddCharset KOI8-ru     .koi8-uk .ua";
$f[]="AddCharset ISO-10646-UCS-2 .ucs2";
$f[]="AddCharset ISO-10646-UCS-4 .ucs4";
$f[]="AddCharset UTF-7       .utf7";
$f[]="AddCharset UTF-8       .utf8";
$f[]="AddCharset UTF-16      .utf16";
$f[]="AddCharset UTF-16BE    .utf16be";
$f[]="AddCharset UTF-16LE    .utf16le";
$f[]="AddCharset UTF-32      .utf32";
$f[]="AddCharset UTF-32BE    .utf32be";
$f[]="AddCharset UTF-32LE    .utf32le";
$f[]="AddCharset euc-cn      .euc-cn";
$f[]="AddCharset euc-gb      .euc-gb";
$f[]="AddCharset euc-jp      .euc-jp";
$f[]="AddCharset euc-kr      .euc-kr";
$f[]="#Not sure how euc-tw got in - IANA doesn't list it???";
$f[]="AddCharset EUC-TW      .euc-tw";
$f[]="AddCharset gb2312      .gb2312 .gb";
$f[]="AddCharset iso-10646-ucs-2 .ucs-2 .iso-10646-ucs-2";
$f[]="AddCharset iso-10646-ucs-4 .ucs-4 .iso-10646-ucs-4";
$f[]="AddCharset shift_jis   .shift_jis .sjis";
$f[]="";
$f[]="#";
$f[]="# AddHandler allows you to map certain file extensions to \"handlers\":";
$f[]="# actions unrelated to filetype. These can be either built into the server";
$f[]="# or added with the Action directive (see below)";
$f[]="#";
$f[]="# To use CGI scripts outside of ScriptAliased directories:";
$f[]="# (You will also need to add \"ExecCGI\" to the \"Options\" directive.)";
$f[]="#";
$f[]="#AddHandler cgi-script .cgi";
$f[]="";
$f[]="#";
$f[]="# For files that include their own HTTP headers:";
$f[]="#";
$f[]="#AddHandler send-as-is asis";
$f[]="";
$f[]="#";
$f[]="# For server-parsed imagemap files:";
$f[]="#";
$f[]="#AddHandler imap-file map";
$f[]="";
$f[]="#";
$f[]="# For type maps (negotiated resources):";
$f[]="# (This is enabled by default to allow the Apache \"It Worked\" page";
$f[]="#  to be distributed in multiple languages.)";
$f[]="#";
$f[]="AddHandler type-map var";
$f[]="";
$f[]="#";
$f[]="# Filters allow you to process content before it is sent to the client.";
$f[]="#";
$f[]="# To parse .shtml files for server-side includes (SSI):";
$f[]="# (You will also need to add \"Includes\" to the \"Options\" directive.)";
$f[]="#";
$f[]="AddType text/html .shtml";
$f[]="AddOutputFilter INCLUDES .shtml";
$f[]="";
$f[]="</IfModule>";
$f[]="";

@file_put_contents("$DAEMON_PATH/mime.conf", @implode("\n", $f));
	
}
	
	
	


function CheckHttpdConf(){
	EnableMods();
	apache_user();
	$sock=$GLOBALS["CLASS_SOCKETS"];
	$unix=new unix();
	$users=new usersMenus();
	$GLOBALS["ToRestore"]=array();
	$freeweb=new freeweb();
	$chmod=$unix->find_program("chmod");
	$php5=$unix->LOCATE_PHP5_BIN();
	$httpdconf=$unix->LOCATE_APACHE_CONF_PATH();
	if(!is_file($httpdconf)){echo "Starting......: ".date("H:i:s")." [INIT]: Apache unable to stat configuration file\n";return;}
	$d_path=$unix->APACHE_DIR_SITES_ENABLED();
	$DAEMON_PATH=$unix->getmodpathfromconf($httpdconf);
	$APACHE_SRC_ACCOUNT=$unix->APACHE_SRC_ACCOUNT();
	$APACHE_SRC_GROUP=$unix->APACHE_SRC_GROUP();	
	
	if(is_file("/etc/apache2/sites-available/default-ssl")){@unlink("/etc/apache2/sites-available/default-ssl");}
	if(is_link("/etc/apache2/sites-enabled/000-default")){@unlink("/etc/apache2/sites-enabled/000-default");}
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache daemon path: \"$DAEMON_PATH\" run has \"$APACHE_SRC_ACCOUNT:$APACHE_SRC_GROUP\"\n";
	if($APACHE_SRC_ACCOUNT==null){echo "Starting......: ".date("H:i:s")." [INIT]: Apache daemon unable to determine user that will run apache\n";die();}
	if(!is_dir("/var/log/apache2")){@mkdir("/var/log/apache2",0755,true);}
	if(!is_dir("/usr/share/GeoIP")){@mkdir("/usr/share/GeoIP",0755,true);}
	shell_exec("$chmod 755 /usr/share/GeoIP >/dev/null 2>&1");
	
	
	
	$ApacheDisableModDavFS=intval($sock->GET_INFO("ApacheDisableModDavFS"));
	$ApacheDisableModStatus=intval($sock->GET_INFO("ApacheDisableModStatus"));
	
	
	$FreeWebListenPort=$sock->GET_INFO("FreeWebListenPort");
	$FreeWebListenSSLPort=$sock->GET_INFO("FreeWebListenSSLPort");
	$FreeWebEnableModSUPhp=$sock->GET_INFO("FreeWebEnableModSUPhp");
	$FreeWebsEnableModSecurity=$sock->GET_INFO("FreeWebsEnableModSecurity");
	$FreeWebsEnableModEvasive=$sock->GET_INFO("FreeWebsEnableModEvasive");
	$FreeWebsEnableModQOS=$sock->GET_INFO("FreeWebsEnableModQOS");
	$FreeWebsEnableOpenVPNProxy=$sock->GET_INFO("FreeWebsEnableOpenVPNProxy");
	$FreeWebsOpenVPNRemotPort=trim($sock->GET_INFO("FreeWebsOpenVPNRemotPort"));
	$FreeWebDisableSSL=trim($sock->GET_INFO("FreeWebDisableSSL"));
	$FreeWebEnableSQLLog=trim($sock->GET_INFO("FreeWebEnableSQLLog"));
	$ApacheServerTokens=$sock->GET_INFO("ApacheServerTokens");
	if($ApacheServerTokens==null){$ApacheServerTokens="Full";}
	$hostname=$sock->GET_INFO("ApacheServerName");
	if($hostname==null){
		$hostname=$sock->getFrameWork("system.php?hostname-g=yes");
		$sock->SET_INFO($hostname,"ApacheServerName");
	}
	
	$ZarafaWebAccessInFrontEnd=$sock->GET_INFO("ZarafaWebAccessInFrontEnd");
	if(!is_numeric($ZarafaWebAccessInFrontEnd)){$ZarafaWebAccessInFrontEnd=1;}
	
	
	$TomcatEnable=$sock->GET_INFO("TomcatEnable");
	
	
	
	if(!is_numeric($FreeWebDisableSSL)){$FreeWebDisableSSL=0;}
	if(!is_numeric($FreeWebListenSSLPort)){$FreeWebListenSSLPort=443;}
	if(!is_numeric($FreeWebListenPort)){$FreeWebListenPort=80;}
	
	if(!is_numeric($FreeWebsEnableModSecurity)){$FreeWebsEnableModSecurity=0;}
	if(!is_numeric($FreeWebsEnableModEvasive)){$FreeWebsEnableModEvasive=0;}
	if(!is_numeric($FreeWebsEnableModQOS)){$FreeWebsEnableModQOS=0;}		
	if(!is_numeric($FreeWebsEnableOpenVPNProxy)){$FreeWebsEnableOpenVPNProxy=0;}
	if(!is_numeric($TomcatEnable)){$TomcatEnable=1;}
	if(!is_numeric($FreeWebEnableSQLLog)){$FreeWebEnableSQLLog=0;}
	if(!is_numeric($FreeWebEnableModSUPhp)){$FreeWebEnableModSUPhp=0;}
	
	if($unix->isNGnx()){
		$FreeWebListenSSLPort=447;
		$FreeWebListenPort=82;		
		
	}
	
	if($unix->IsSquidReverse()){
		$FreeWebListenSSLPort=447;
		$FreeWebListenPort=82;
	}
	
	
	
	$APACHE_MODULES_PATH=$unix->APACHE_MODULES_PATH();	
	
	$toremove[]="mod-status.init";
	$toremove[]="status.conf";
	$toremove[]="fcgid.load";
	$toremove[]="fcgid.conf";
	$toremove[]="fastcgi.conf";
	$toremove[]="fastcgi.load";
	$toremove[]="fastcgi.conf";
	$toremove[]="log_sql.load";
	$toremove[]="log_sql_mysql.load";
	$toremove[]="geoip.conf";
	$toremove[]="bw.load";
	$toremove[]="geoip_module.load";
	$toremove[]="log_sql_module.conf";
	$toremove[]="log_sql_module.load";
	$toremove[]="log_sql_mysql_module.load";
	$toremove[]="log_sql_ssl.load";
	$toremove[]="unique_id.load";
	$toremove[]="mime.conf";
	$toremove[]="mime.load";
	
	$toremove[]="php5.conf";
	$toremove[]="php5.load";
	$toremove[]="fcgid_module.load";
	$toremove[]="php5-fpm.load";
	$toremove[]="fastcgi.load";
	$toremove[]="php5-fpm.conf";
	$toremove[]="bw.load";
	$toremove[]="status_module.load";
	$toremove[]="autoindex.load";
	$toremove[]="ldap.load";
	

	
	if(is_file("/etc/apache2/sites-enabled/000-default")){
		$ToDeleteDefault=true;
		$q=new mysql();
		$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT servername,enabled FROM freeweb WHERE servername='_default_'",'artica_backup'));
		if($q->ok){
			if($ligne["servername"]=="_default_"){ if($ligne["enabled"]==1){ $ToDeleteDefault=false; } }
		}else{
			$ToDeleteDefault=false;
		}
		
		if($ZarafaWebAccessInFrontEnd==1){ $ToDeleteDefault=false; }
		if($ToDeleteDefault){ @unlink("/etc/apache2/sites-enabled/000-default");}
	}
	if(is_file("/etc/apache2/conf.d/other-vhosts-access-log")){@unlink("/etc/apache2/conf.d/other-vhosts-access-log");}
	@mkdir("/etc/apache2/htdocs",0755,true);
	
	if(is_file("/etc/apache2/sites-enabled/default-www")){@unlink("/etc/apache2/sites-enabled/default-www");}
	if(is_file("/etc/apache2/sites-available/default")){@unlink("/etc/apache2/sites-available/default");}
	if(is_file("/etc/apache2/conf.d/zarafa-webaccess.conf")){@unlink("/etc/apache2/conf.d/zarafa-webaccess.conf");}
	if(is_file("/etc/apache2/conf.d/zarafa-webaccess-mobile.conf")){@unlink("/etc/apache2/conf.d/zarafa-webaccess-mobile.conf");}
	if(is_file("/etc/httpd/conf/extra/httpd-info.conf")){@unlink("/etc/httpd/conf/extra/httpd-info.conf");}
	if(is_file("/etc/apache2/mods-enabled/ssl.conf")){@unlink("/etc/apache2/mods-enabled/ssl.conf");}
	
	$FreeWebListen=$unix->APACHE_ListenDefaultAddress();
	while (list ($num, $file) = each ($toremove) ){
		
		if(is_file("$DAEMON_PATH/mods-enabled/$file")){
			echo "Starting......: ".date("H:i:s")." [INIT]: Apache remove mods-enabled/$file\n";
			shell_exec("/bin/rm -f $DAEMON_PATH/mods-enabled/$file >/dev/null 2>&1");
		}
		
		if(is_file("$DAEMON_PATH/mods-enabled/$file")){
			echo "Starting......: ".date("H:i:s")." [INIT]: Apache remove mods-available/$file\n";
			shell_exec("/bin/rm -f $DAEMON_PATH/mods-available/$file >/dev/null 2>&1");
		}
		
		
	}
	php5_conf($DAEMON_PATH);
	if($FreeWebDisableSSL==1){$FreeWebListenSSLPort=0;}
	$VirtualHostsIPAddresses=VirtualHostsIPAddresses($FreeWebListenPort,$FreeWebListen,$FreeWebListenSSLPort);
	
		
	if(count($VirtualHostsIPAddresses[0])>0){
		$conf[]=@implode("\n",$VirtualHostsIPAddresses[0]);
	}
	
	if(count($VirtualHostsIPAddresses[1])>0){
		$conf[]=@implode("\n",$VirtualHostsIPAddresses[1]);
	}	
	
	
	
	if($FreeWebDisableSSL==0){
		
		$conf[]="<IfModule mod_ssl.c>";
		//$conf[]="\tListen $FreeWebListenSSLPort";
		$conf[]="\tNameVirtualHost $FreeWebListen:$FreeWebListenSSLPort";
		if($VirtualHostsIPAddresses[2]>0){
			$conf[]=@implode("\n", $VirtualHostsIPAddresses[2]);
		}
		$conf[]="\tSSLPassPhraseDialog exec:/etc/apache2/ssl-tools/sslpass.sh";
		shell_exec("$php5 /usr/share/artica-postfix/exec.openssl.php --pass");
		$conf[]="</IfModule>";
		$conf[]="";
		$conf[]="<IfModule mod_gnutls.c>";
		$conf[]="\tNameVirtualHost $FreeWebListen:$FreeWebListenSSLPort";
		if($VirtualHostsIPAddresses[2]>0){
			$conf[]=@implode("\n", $VirtualHostsIPAddresses[2]);
		}		
		//$conf[]="\tListen $FreeWebListenSSLPort";
		$conf[]="</IfModule>";
	}
	
	$conf[]="<IfModule mod_fcgid.c>";
	$conf[]="\tPHP_Fix_Pathinfo_Enable 1";
	$conf[]="</IfModule>";
	
	
	
	$conf[]="<IfModule mod_fastcgi.c>";
	$conf[]="\tAddHandler fastcgi-script .fcgi";
	$conf[]="#FastCgiWrapper /usr/lib/apache2/suexec";
	$conf[]="\tFastCgiIpcDir /var/lib/apache2/fastcgi";
	$conf[]="</IfModule>";
	

	if(is_file("/usr/lib/apache2/modules/mod_cband.so")){
		$conf[]="<IfModule mod_cband.c>";
		$conf[]="\tCBandScoreFlushPeriod 1";
		$conf[]="\tCBandRandomPulse On";
		$conf[]="</IfModule>";
	}
	
	
	$conf[]="";
	if(!is_dir("$DAEMON_PATH/sites-available")){@mkdir("$DAEMON_PATH/sites-available",666,true);}
	
	if(!is_dir("$DAEMON_PATH/BackupConf")){@mkdir("$DAEMON_PATH/BackupConf",666,true);}
	
	if(is_file("$DAEMON_PATH/ports.conf")){
		@unlink("$DAEMON_PATH/BackupConf/ports.conf");
		@copy("$DAEMON_PATH/ports.conf", "$DAEMON_PATH/BackupConf/ports.conf");
		$GLOBALS["ToRestore"][]="$DAEMON_PATH/BackupConf/ports.conf";
	}
	
	
	@file_put_contents("$DAEMON_PATH/ports.conf",@implode("\n",$conf));
	
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache $DAEMON_PATH/ports.conf for NameVirtualHost $FreeWebListen:$FreeWebListenPort done\n";
	mod_security();
	
	if($FreeWebsEnableModEvasive==1){
			$Params=unserialize(base64_decode($sock->GET_INFO("modEvasiveDefault")));
			if(!is_numeric($Params["DOSHashTableSize"])){$Params["DOSHashTableSize"]=1024;}
			if(!is_numeric($Params["DOSPageCount"])){$Params["DOSPageCount"]=10;}
			if(!is_numeric($Params["DOSSiteCount"])){$Params["DOSSiteCount"]=150;}
			if(!is_numeric($Params["DOSPageInterval"])){$Params["DOSPageInterval"]=1.5;}
			if(!is_numeric($Params["DOSSiteInterval"])){$Params["DOSSiteInterval"]=1.5;}
			if(!is_numeric($Params["DOSBlockingPeriod"])){$Params["DOSBlockingPeriod"]=10.7;}		
			$f[]="   LoadModule evasive20_module modules/mod_evasive20.so";
			$f[]="   ExtendedStatus On";
			$f[]="   DOSHashTableSize {$Params["DOSHashTableSize"]}";
			$f[]="   DOSPageCount {$Params["DOSPageCount"]}";
			$f[]="   DOSSiteCount {$Params["DOSSiteCount"]}";
			$f[]="   DOSPageInterval {$Params["DOSPageInterval"]}";
			$f[]="   DOSSiteInterval {$Params["DOSSiteInterval"]}";
			$f[]="   DOSBlockingPeriod {$Params["DOSBlockingPeriod"]}";
			$f[]="   DOSLogDir  \"/var/log/apache2/mod_evasive.log\"";
			$f[]="   DOSSystemCommand \"/bin/echo `date '+%F %T'` apache2  %s >> /var/log/apache2/dos_evasive_attacks.log\"";
			$f[]="";
			echo "Starting......: ".date("H:i:s")." [INIT]: Apache $DAEMON_PATH/mod_evasive.conf\n";
			
			if(is_file("$DAEMON_PATH/mod_evasive.conf")){
				@unlink("$DAEMON_PATH/BackupConf/mod_evasive.conf");
				@copy("$DAEMON_PATH/ports.conf", "$DAEMON_PATH/BackupConf/mod_evasive.conf");
				$GLOBALS["ToRestore"][]="$DAEMON_PATH/BackupConf/mod_evasive.conf";
			}
			
			@file_put_contents("$DAEMON_PATH/mod_evasive.conf",@implode("\n",$f));
			unset($f);		
		
	}
	
	@mkdir("/var/run/apache2",0775,true);	
	
	if(is_file("$DAEMON_PATH/ssl.conf")){
		@unlink("$DAEMON_PATH/BackupConf/ssl.conf");
		@copy("$DAEMON_PATH/ports.conf", "$DAEMON_PATH/BackupConf/ssl.conf");
		$GLOBALS["ToRestore"][]="$DAEMON_PATH/BackupConf/ssl.conf";
	}
	
	
	@unlink("$DAEMON_PATH/ssl.conf");
	
	if($FreeWebDisableSSL==0){
		$f[]="<IfModule mod_ssl.c>";
		$f[]="	SSLRandomSeed connect builtin";
		$f[]="	SSLRandomSeed connect file:/dev/urandom 512";
		$f[]="	AddType application/x-x509-ca-cert .crt";
		$f[]="	AddType application/x-pkcs7-crl    .crl";
		$f[]="	SSLPassPhraseDialog  builtin";
		$f[]="	SSLSessionCache        shmcb:/var/run/apache2/ssl_scache(512000)";
		$f[]="	SSLSessionCacheTimeout  300";
		$f[]="	SSLSessionCacheTimeout  300";
		$f[]="	SSLMutex  sem";
		//$f[]="	SSLMutex  file:/var/run/apache2/ssl_mutex";
		$f[]="	SSLCipherSuite HIGH:MEDIUM:!ADH";
		$f[]="	SSLProtocol all -SSLv2";
		$f[]="</IfModule>";
		$f[]="";	
		@file_put_contents("$DAEMON_PATH/ssl.conf",@implode("\n",$f));	
		unset($f);	
	}

	
	apache_security($DAEMON_PATH);
	$httpdconf_data=@file_get_contents($httpdconf);
	$httpdconf_basename=basename($httpdconf);
	if(is_file($httpdconf)){
		@unlink("$DAEMON_PATH/BackupConf/$httpdconf_basename");
		@copy("$DAEMON_PATH/$httpdconf_basename", "$DAEMON_PATH/BackupConf/$httpdconf_basename");
		$GLOBALS["ToRestore"][]="$DAEMON_PATH/BackupConf/$httpdconf_basename";
	}
	
	
	if(preg_match("#<Location \/server-status>(.+?)<\/Location>#is",$httpdconf_data,$re)){$httpdconf_data=str_replace($re[0], "", $httpdconf_data);}
	
	
	
	$f=explode("\n",$httpdconf_data);
	while (list ($num, $ligne) = each ($f) ){
		if(preg_match("#^Include\s+#",$ligne)){echo "Starting......: ".date("H:i:s")." [INIT]: Apache removing {$f[$num]}\n";$f[$num]=null;}
		if(preg_match("#\#.*?Include\s+#",$ligne)){$f[$num]=null;}
		if(preg_match("#Listen\s+#",$ligne)){$f[$num]=null;}
		if(preg_match("#ProxyRequests#",$ligne)){$f[$num]=null;}
		if(preg_match("#ProxyVia#",$ligne)){$f[$num]=null;}
		if(preg_match("#AllowCONNECT#",$ligne)){$f[$num]=null;}
		if(preg_match("#KeepAlive#",$ligne)){$f[$num]=null;}
		if(preg_match("#Timeout\s+[0-9]+#",$ligne)){$f[$num]=null;}
		if(preg_match("#MaxKeepAliveRequests\s+#",$ligne)){$f[$num]=null;}
		if(preg_match("#KeepAliveTimeout\s+#",$ligne)){$f[$num]=null;}
		if(preg_match("#MinSpareServers\s+#",$ligne)){$f[$num]=null;}
		if(preg_match("#MaxSpareServers\s+#",$ligne)){$f[$num]=null;}
		if(preg_match("#StartServers\s+#",$ligne)){$f[$num]=null;}
		if(preg_match("#MaxClients\s+#",$ligne)){$f[$num]=null;}
		if(preg_match("#MaxRequestsPerChild\s+#",$ligne)){$f[$num]=null;}
		if(preg_match("#ExtendedStatus\s+#",$ligne)){$f[$num]=null;}
		
		if(preg_match("#LoadModule\s+#",$ligne)){$f[$num]=null;}
		if(preg_match("#ErrorLog\s+#",$ligne)){$f[$num]=null;}
		if(preg_match("#LogFormat\s+#",$ligne)){$f[$num]=null;}
		if(preg_match("#User\s+#",$ligne)){$f[$num]=null;}
		if(preg_match("#Group\s+#",$ligne)){$f[$num]=null;}
		if(preg_match("#CustomLog\s+#",$ligne)){$f[$num]=null;}
		if(preg_match("#LogLevel#",$ligne)){$f[$num]=null;}
		if(preg_match("#ServerName#",$ligne)){$f[$num]=null;}
		if(preg_match("#DavLockDB#",$ligne)){$f[$num]=null;}
		if(trim($ligne)=="Loglevel info"){$f[$num]=null;}
		
	}
	
	$FreeWebPerformances=unserialize(base64_decode($sock->GET_INFO("FreeWebPerformances")));
	if(!isset($FreeWebPerformances["Timeout"])){$FreeWebPerformances["Timeout"]=300;}
	if(!isset($FreeWebPerformances["KeepAlive"])){$FreeWebPerformances["KeepAlive"]=0;}
	if(!isset($FreeWebPerformances["MaxKeepAliveRequests"])){$FreeWebPerformances["MaxKeepAliveRequests"]=100;}
	if(!isset($FreeWebPerformances["KeepAliveTimeout"])){$FreeWebPerformances["KeepAliveTimeout"]=15;}
	if(!isset($FreeWebPerformances["MinSpareServers"])){$FreeWebPerformances["MinSpareServers"]=1;}
	if(!isset($FreeWebPerformances["MaxSpareServers"])){$FreeWebPerformances["MaxSpareServers"]=2;}
	if(!isset($FreeWebPerformances["StartServers"])){$FreeWebPerformances["StartServers"]=1;}
	if(!isset($FreeWebPerformances["MaxClients"])){$FreeWebPerformances["MaxClients"]=50;}
	if(!isset($FreeWebPerformances["MaxRequestsPerChild"])){$FreeWebPerformances["MaxRequestsPerChild"]=10000;}	
	if(!is_numeric($FreeWebPerformances["Timeout"])){$FreeWebPerformances["Timeout"]=300;}
	if(!is_numeric($FreeWebPerformances["KeepAlive"])){$FreeWebPerformances["KeepAlive"]=0;}
	if(!is_numeric($FreeWebPerformances["MaxKeepAliveRequests"])){$FreeWebPerformances["MaxKeepAliveRequests"]=100;}
	if(!is_numeric($FreeWebPerformances["KeepAliveTimeout"])){$FreeWebPerformances["KeepAliveTimeout"]=15;}
	if(!is_numeric($FreeWebPerformances["MinSpareServers"])){$FreeWebPerformances["MinSpareServers"]=1;}
	if(!is_numeric($FreeWebPerformances["MaxSpareServers"])){$FreeWebPerformances["MaxSpareServers"]=2;}
	if(!is_numeric($FreeWebPerformances["StartServers"])){$FreeWebPerformances["StartServers"]=1;}
	if(!is_numeric($FreeWebPerformances["MaxClients"])){$FreeWebPerformances["MaxClients"]=50;}
	if(!is_numeric($FreeWebPerformances["MaxRequestsPerChild"])){$FreeWebPerformances["MaxRequestsPerChild"]=10000;}

	
	 
	
	reset($f);
	while (list ($num, $ligne) = each ($f) ){
		if(trim($ligne)==null){continue;}
		if(substr($ligne,0,1)=="#"){continue;}
		$httpd[]=$ligne;
	}
	
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php5 /usr/share/artica-postfix/exec.samba.php --fix-etc-hosts >/dev/null 2>&1");
	if($APACHE_SRC_GROUP=='${APACHE_RUN_GROUP}'){$APACHE_SRC_GROUP=$APACHE_SRC_ACCOUNT;}
	
	
	
	if($FreeWebPerformances["KeepAlive"]==1){$FreeWebPerformances["KeepAlive"]="On";}else{$FreeWebPerformances["KeepAlive"]="Off";}
	$httpd[]="User				   {$APACHE_SRC_ACCOUNT}";
	$httpd[]="Group				   {$APACHE_SRC_GROUP}";
	$httpd[]="Timeout              {$FreeWebPerformances["Timeout"]}";
	$httpd[]="KeepAlive            {$FreeWebPerformances["KeepAlive"]}";
	$httpd[]="KeepAliveTimeout     {$FreeWebPerformances["KeepAliveTimeout"]}";
	$httpd[]="StartServers         {$FreeWebPerformances["StartServers"]}";
	$httpd[]="MaxClients           {$FreeWebPerformances["MaxClients"]}";
	$httpd[]="MinSpareServers      {$FreeWebPerformances["MinSpareServers"]}";
	$httpd[]="MaxSpareServers      {$FreeWebPerformances["MaxSpareServers"]}"; 
	$httpd[]="MaxRequestsPerChild  {$FreeWebPerformances["MaxRequestsPerChild"]}";
	$httpd[]="MaxKeepAliveRequests {$FreeWebPerformances["MaxKeepAliveRequests"]}";
	$httpd[]="ServerName $hostname";
	
	
	if($FreeWebsEnableOpenVPNProxy==1){
		if($FreeWebsOpenVPNRemotPort<>null){
			$httpd[]="ProxyRequests On";
			$httpd[]="ProxyVia On";
			$httpd[]="AllowCONNECT $FreeWebsOpenVPNRemotPort";
			$httpd[]="KeepAlive On";
		}
	}
	
	
	@unlink("$DAEMON_PATH/mods-enabled/klms.FastCgiExternalServer.conf");
	if($users->KLMS_WEB_INSTALLED){
		$sql="SELECT COUNT(*) as tcount FROM freeweb WHERE groupware='KLMS'";
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$CountDeGroupware=$ligne["tcount"];
		echo "Starting......: ".date("H:i:s")." $CountDeGroupware KLMS Groupware(s)\n";
		if($CountDeGroupware>0){
			if(is_file("/opt/kaspersky/klmsui/share/htdocs/cgi-bin/klwi")){
				@file_put_contents("$DAEMON_PATH/mods-enabled/klms.FastCgiExternalServer.conf", "FastCgiExternalServer /opt/kaspersky/klmsui/share/htdocs/cgi-bin/klwi -host 127.0.0.1:2711\n");
			}
		}
	}
	
	
	//$dir_master=$unix->getmodpathfromconf();
	

	
	

	
	if(is_file('/usr/lib/apache2/modules/mod_kav64.so')){
		$sock=new sockets();
		$ApacheEnableKavModule=intval($sock->GET_INFO("ApacheEnableKavModule"));
		if($ApacheEnableKavModule==1){
			$httpd[]="LoadModule kav_module /usr/lib/apache2/modules/mod_kav64.so";
		}
	}
	
	if(!is_file("$DAEMON_PATH/videocache.conf")){@touch("$DAEMON_PATH/videocache.conf");}
	$httpd[]="Include $DAEMON_PATH/videocache.conf";
	if(is_file("$DAEMON_PATH/security.conf")){
		$httpd[]="Include $DAEMON_PATH/security.conf";	
	}

	
	
	$httpd[]="Include $DAEMON_PATH/mods-enabled/*.load";
	$httpd[]="Include $DAEMON_PATH/mods-enabled/*.conf";
	$httpd[]="Include $DAEMON_PATH/mods-enabled/*.init";
	
	$mod_php5[]="<IfModule mod_php5.c>";
	$mod_php5[]="    <FilesMatch \"\.ph(p3?|tml)$\">";
	$mod_php5[]="	SetHandler application/x-httpd-php";
	$mod_php5[]="    </FilesMatch>";
	$mod_php5[]="    <FilesMatch \"\.phps$\">";
	$mod_php5[]="	SetHandler application/x-httpd-php-source";
	$mod_php5[]="    </FilesMatch>";
	$mod_php5[]="    # To re-enable php in user directories comment the following lines";
	$mod_php5[]="    # (from <IfModule ...> to </IfModule>.) Do NOT set it to On as it";
	$mod_php5[]="    # prevents .htaccess files from disabling it.";
	$mod_php5[]="    <IfModule mod_userdir.c>";
	$mod_php5[]="        <Directory /home/*/public_html>";
	$mod_php5[]="            php_admin_value engine Off";
	$mod_php5[]="        </Directory>";
	$mod_php5[]="    </IfModule>";
	$mod_php5[]="</IfModule>";
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache saving: $DAEMON_PATH/mods-enabled/mod_php5.conf\n";
	@file_put_contents("$DAEMON_PATH/mods-enabled/mod_php5.conf", @implode("\n", $mod_php5));
	$httpd[]="Include $DAEMON_PATH/mods-enabled/mod_php5.conf";
	$mod_php5=array();
	
	if(basename($httpdconf)<>"httpd.conf"){$httpd[]="Include $DAEMON_PATH/httpd.conf";}
	$httpd[]="Include $DAEMON_PATH/ports.conf";
	if($FreeWebsEnableModSecurity==1){$httpd[]="Include $DAEMON_PATH/mod_security.conf";}
	if($FreeWebsEnableModEvasive==1){$httpd[]="Include $DAEMON_PATH/mod_evasive.conf";}
	
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache checks WebDav (ApacheDisableModDavFS = $ApacheDisableModDavFS)\n";
	$freeweb_tmp=new freeweb();
	$WebDavContainers=$freeweb_tmp->WebDavContainers();
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache checks WebDav ". strlen($WebDavContainers)." bytes\n";
	@file_put_contents("$DAEMON_PATH/webdavcontainers.conf", $WebDavContainers);
	
	if($ApacheDisableModDavFS==0){
		$httpd[]="DavLockDB \"/var/www/.DavLockDB\"";
		@mkdir("/var/www",0755,true);
		@chown("/var/www", $APACHE_SRC_ACCOUNT);
		@chgrp("/var/www", $APACHE_SRC_GROUP);
	}
	
	@mkdir("/var/lib/apache2/fastcgi",0755,true);
	@chown("/var/lib/apache2/fastcgi", $APACHE_SRC_ACCOUNT);
	@chgrp("/var/lib/apache2/fastcgi", $APACHE_SRC_GROUP);
	
	
	$httpd[]='Loglevel info';
	$httpd[]='ErrorLog /var/log/apache2/error.log';
	$httpd[]='LogFormat "%h %l %u %t \"%r\" %<s %b" common';
	$httpd[]='CustomLog /var/log/apache2/access.log common';  	
	
	
	$mod_status=$freeweb->mod_status();
	if($mod_status<>null){
		$status[]="<IfModule mod_status.c>";
		$status[]="\tExtendedStatus On";
		$status[]="$mod_status";
		$status[]="</IfModule>";
		@file_put_contents("$DAEMON_PATH/mods-enabled/mod-status.init", @implode("\n", $status));
	}
	
	
	@unlink("$DAEMON_PATH/mods-enabled/pagespeed.conf");
	
	if($users->APACHE_MOD_PAGESPEED){
		if(!is_dir("/var/cache/apache2/mod_pagespeed/default/files")){@mkdir("/var/cache/apache2/mod_pagespeed/default/files",644,true);}
		$pspedd[]="<IfModule pagespeed_module>";
 		$pspedd[]="\tModPagespeedFileCachePath            \"/var/cache/apache2/mod_pagespeed/default\"";
		$pspedd[]="\tModPagespeedGeneratedFilePrefix      \"/var/cache/apache2/mod_pagespeed/files/\"";
		$pspedd[]="\tSetOutputFilter MOD_PAGESPEED_OUTPUT_FILTER";
    	$pspedd[]="\tAddOutputFilterByType MOD_PAGESPEED_OUTPUT_FILTER text/html";
    	$pspedd[]="</IfModule>";
    	@file_put_contents("$DAEMON_PATH/mods-enabled/pagespeed.conf", @implode("\n", $pspedd));
	}
	
	if($users->APACHE_MOD_LOGSSQL){
		if($FreeWebEnableSQLLog==1){
			$q=new mysql();
			if(!$q->DATABASE_EXISTS("apachelogs")){$q->CREATE_DATABASE("apachelogs");}
			$APACHE_MOD_LOGSSQL[]="<IfModule log_sql_mysql_module>";
			$APACHE_MOD_LOGSSQL[]="\tLogSQLLoginInfo mysql://$q->mysql_admin:$q->mysql_password@$q->mysql_server:$q->mysql_port/apachelogs";
			$APACHE_MOD_LOGSSQL[]="\tLogSQLMassVirtualHosting On";
			$APACHE_MOD_LOGSSQL[]="\tLogSQLmachineID $users->hostname";
			$APACHE_MOD_LOGSSQL[]="\tLogSQLTransferLogFormat AbcHhmMpRSstTUuvz";
			$APACHE_MOD_LOGSSQL[]="</IfModule>";	
			@file_put_contents("$DAEMON_PATH/mods-enabled/log_sql_module.conf", @implode("\n", $APACHE_MOD_LOGSSQL));
		}
	}
	
	
	CheckHttpdConf_mime_module();
	CheckHttpdConf_mailman();
	if(is_file("/etc/apache2/mailman.conf")){$httpd[]="Include /etc/apache2/mailman.conf";}
	if(is_file("/etc/apache2/sysconfig.d/loadmodule.conf")){$httpd[]="Include /etc/apache2/sysconfig.d/loadmodule.conf";}
	if(is_file("/etc/apache2/uid.conf")){$httpd[]="Include /etc/apache2/uid.conf";}
	if(is_file("/etc/apache2/default-server.conf")){patch_suse_default_server();$httpd[]="Include /etc/apache2/default-server.conf";}
	
	$httpd[]="Include $DAEMON_PATH/mime.conf";
	$httpd[]="Include $DAEMON_PATH/conf.d/";
	$httpd[]="Include $DAEMON_PATH/sites-enabled/";
	$httpd[]="Include $DAEMON_PATH/webdavcontainers.conf";
	
	
	//PHP5 MODULE
	
	//if(is_file("$APACHE_MODULES_PATH/mod_php5.so")){$httpd[]="LoadModule php5_module $APACHE_MODULES_PATH/mod_php5.so";}
	//if(is_file("$APACHE_MODULES_PATH/mod_ldap.so")){$httpd[]="LoadModule ldap_module $APACHE_MODULES_PATH/mod_ldap.so";}
	
	
	
	
	if($ApacheDisableModDavFS==0){
			if(is_file("$APACHE_MODULES_PATH/mod_dav.so")){echo "Starting......: ".date("H:i:s")." [INIT]: Apache module 'dav_module' enabled\n";$httpd[]="LoadModule dav_module $APACHE_MODULES_PATH/mod_dav.so";}		
			if(is_file("$APACHE_MODULES_PATH/mod_dav_lock.so")){echo "Starting......: ".date("H:i:s")." [INIT]: Apache module 'dav_lock_module' enabled\n";$httpd[]="LoadModule dav_lock_module $APACHE_MODULES_PATH/mod_dav_lock.so";}
			if(is_file("$APACHE_MODULES_PATH/mod_dav_fs.so")){echo "Starting......: ".date("H:i:s")." [INIT]: Apache module 'dav_fs_module' enabled\n";$httpd[]="LoadModule dav_fs_module $APACHE_MODULES_PATH/mod_dav_fs.so";}			
	}		
	
	$httpd[]="";
	$httpd[]=YfiAdds();
	
	
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache $httpdconf done\n";
	@file_put_contents($httpdconf,@implode("\n",$httpd));
	
	
	
	
	// MODULES -----------------------------------------------------------------------
	
	
	if(!is_dir("$DAEMON_PATH/mods-enabled")){@mkdir("$DAEMON_PATH/mods-enabled",666,true);}
	if(!is_file("$DAEMON_PATH/httpd.conf")){@file_put_contents("$DAEMON_PATH/httpd.conf", "#");}
	
	
	@unlink("/etc/libapache2-mod-jk/workers.properties");
	@unlink("/etc/apache2/workers.properties");	
	@unlink("$DAEMON_PATH/conf.d/jk.conf");
	$free=new freeweb();
	
	
	
	$array["php5_module"]="libphp5.so";
	
	
	
	if($users->APACHE_MOD_SUPHP){
		if($FreeWebEnableModSUPhp==1){
			$array["suphp_module"]="mod_suphp.so";
		}
	}
	
	
	
	//$array["access_module"]="mod_access.so";
	$array["qos_module"]="mod_qos.so";
	$array["rewrite_module"]="mod_rewrite.so";
	$array["cache_module"]="mod_cache.so";
	$array["disk_cache_module"]="mod_disk_cache.so";
	$array["mem_cache_module"]="mod_mem_cache.so";
	$array["expires_module"]="mod_expires.so";
	
	
	$ApacheDisableModStatus=intval($sock->GET_INFO("ApacheDisableModStatus"));
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache module ApacheDisableModStatus=$ApacheDisableModStatus\n";
	if($ApacheDisableModStatus==0){
		$array["status_module"]="mod_status.so";
	}
	if(is_file($free->locate_geoip_db())){
		$array["geoip_module"]="mod_geoip.so";
	}
	$array["info_module"]="mod_info.so";
	$array["suexec_module"]="mod_suexec.so";
	$array["fcgid_module"]="mod_fcgid.so";
	$array["authz_host_module"]="mod_authz_host.so";
	$array["dir_module"]="mod_dir.so";
	$array["mime_module"]="mod_mime.so";
	$array["log_config_module"]="mod_log_config.so";
	$array["alias_module"]="mod_alias.so";
	$array["autoindex_module"]="mod_autoindex.so";
	$array["negotiation_module"]="mod_negotiation.so";
	$array["setenvif_module"]="mod_setenvif.so";
	$array["logio_module"]="mod_logio.so";
	$array["auth_basic_module"]="mod_auth_basic.so";
	$array["authn_file_module"]="mod_authn_file.so";
	$array["vhost_alias_module"]="mod_vhost_alias.so";
	$array["python_module"]="mod_python.so";
	$array["auth_digest_module"]="mod_auth_digest.so";
	$array["mime_module"]="mod_mime.so";
	
	
	$array["ssl_module"]="mod_ssl.so";
	if($FreeWebEnableSQLLog==1){
		$array["log_sql_module"]="mod_log_sql.so";
		$array["log_sql_mysql_module"]="mod_log_sql_mysql.so";
	}
	
	if(mod_bw_module_must_be_enabled()){
		$array["bw_module"]="mod_bw.so";
	}
	$array["actions_module"]="mod_actions.so";
	$array["expires_module"]="mod_expires.so";
	$array["include_module"]="mod_include.so";
	$array["rpaf_module"]="mod_rpaf-2.0.so";
	$array["fastcgi_module"]="mod_fastcgi.so";
	$array["deflate_module"]="mod_deflate.so";
	$array["headers_module"]="mod_headers.so";
	$array["cband_module"]="mod_cband.so";
	
	
	
	if(is_file("$APACHE_MODULES_PATH/mod_rpaf-2.0.so")){
		$net=new networking();
		$ips=$net->ALL_IPS_GET_ARRAY();
		while (list ($ip, $line) = each ($ips) ){$tip[]=$ip;}
          $rpfmod[]="<IfModule mod_rpaf.c>";
          $rpfmod[]="\tRPAFenable On";
          $rpfmod[]="\tRPAFsethostname On";
          $rpfmod[]="\tRPAFproxy_ips ".@implode(" ", $tip);
          $rpfmod[]="\tRPAFheader X-Forwarded-For";
          $rpfmod[]="</IfModule>";
          $rpfmod[]="<IfModule rpaf_module>";
          $rpfmod[]="\tRPAFenable On";
          $rpfmod[]="\tRPAFsethostname On";
          $rpfmod[]="\tRPAFproxy_ips ".@implode(" ", $tip);
          $rpfmod[]="\tRPAFheader X-Forwarded-For";
          $rpfmod[]="</IfModule>";
		@file_put_contents("$DAEMON_PATH/mods-enabled/rpaf.conf",@implode("\n", $rpfmod));
	}
	
	 
	
	if(is_file("$APACHE_MODULES_PATH/mod_pagespeed.so")){
		echo "Starting......: ".date("H:i:s")." [INIT]: Apache module 'mod_pagespeed' enabled\n";
		$ppsped[]="LoadModule pagespeed_module $APACHE_MODULES_PATH/mod_pagespeed.so";
		if(is_file("$APACHE_MODULES_PATH/mod_deflate.so")){
			$ppsped[]="# Only attempt to load mod_deflate if it hasn't been loaded already.";
			$ppsped[]="<IfModule !mod_deflate.c>";
			$ppsped[]="\tLoadModule deflate_module $APACHE_MODULES_PATH/mod_deflate.so";
			$ppsped[]="</IfModule>";
		}
		@file_put_contents("$DAEMON_PATH/mods-enabled/mod_pagespeed.load",@implode("\n", $ppsped));
	}else{
		echo "Starting......: ".date("H:i:s")." [INIT]: Apache module 'mod_pagespeed' $APACHE_MODULES_PATH/mod_pagespeed.so no such file\n";
	}
	
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [DEBUG] Apache TOMCAT_INSTALLED -> $users->TOMCAT_INSTALLED\n";}
	
	if($users->TOMCAT_INSTALLED){
		if($TomcatEnable==1){
			if(is_dir($users->TOMCAT_DIR)){
				if(is_dir($users->TOMCAT_JAVA)){
					$array["jk_module"]="mod_jk.so";
					$ftom[]="workers.tomcat_home=$users->TOMCAT_DIR";
					$ftom[]="workers.java_home=$users->TOMCAT_JAVA";
					$ftom[]="ps=/";
					$ftom[]="worker.list=ajp13_worker";
					$ftom[]="worker.ajp13_worker.port=8009";
					$ftom[]="worker.ajp13_worker.host=127.0.0.1";
					$ftom[]="worker.ajp13_worker.type=ajp13";
					$ftom[]="worker.ajp13_worker.lbfactor=1";
					$ftom[]="worker.loadbalancer.type=lb";
					$ftom[]="worker.loadbalancer.balance_workers=ajp13_worker";
					$ftom[]="";		
					@file_put_contents("/etc/apache2/workers.properties", @implode("\n", $ftom));
					@mkdir("/etc/libapache2-mod-jk",644);
					@file_put_contents("/etc/libapache2-mod-jk/workers.properties", @implode("\n", $ftom));	
					$faptom[]="<ifmodule mod_jk.c>";
					$faptom[]="\tJkWorkersFile /etc/apache2/workers.properties";
					$faptom[]="\tJkLogFile /var/log/apache2/mod_jk.log";
					$faptom[]="\tJkLogLevel error";
					$faptom[]="</ifmodule>";
					@file_put_contents("$DAEMON_PATH/conf.d/jk.conf", @implode("\n", $faptom));	
				}
			}			
		}
		
	}

	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [DEBUG] Apache cleaning mods...\n";}
	
	@unlink("$DAEMON_PATH/mods-enabled/mod-security.load");
	@unlink("$DAEMON_PATH/mods-enabled/mod_security.load");
	@unlink("$DAEMON_PATH/mods-enabled/mod-evasive.load");
	@unlink("$DAEMON_PATH/mods-enabled/mod_evasive.load");
	@unlink("$DAEMON_PATH/mods-enabled/geoip.load");
	@unlink("$DAEMON_PATH/mods-enabled/status.conf");
	@unlink("$DAEMON_PATH/mods-enabled/status.load");
	@unlink("$DAEMON_PATH/mods-enabled/php5.load");
	@unlink("$DAEMON_PATH/mods-enabled/jk.load");
	@unlink("$DAEMON_PATH/mods-enabled/dav_lock_module.load");
	@unlink("$DAEMON_PATH/mods-enabled/dav_module.load");
	@unlink("$DAEMON_PATH/mods-enabled/dav_fs_module.load");
	@unlink("$DAEMON_PATH/mods-enabled/pagespeed.load");
	@unlink("$DAEMON_PATH/mods-enabled/rpaf.load");
	@unlink("$DAEMON_PATH/mods-enabled/alias.load");
	@unlink("$DAEMON_PATH/mods-enabled/auth_basic.load");
	@unlink("$DAEMON_PATH/mods-enabled/dir.load");
	@unlink("$DAEMON_PATH/mods-enabled/deflate.load");
	@unlink("$DAEMON_PATH/mods-enabled/negotiation.load");
	@unlink("$DAEMON_PATH/mods-enabled/authn_file.load");
	@unlink("$DAEMON_PATH/mods-enabled/authz_host.load");
	@unlink("$DAEMON_PATH/mods-enabled/setenvif.load");
	@unlink("$DAEMON_PATH/mods-enabled/ssl.load");
	@unlink("$DAEMON_PATH/mods-enabled/ldap.load");
	@unlink("$DAEMON_PATH/mods-enabled/authnz_ldap.load");
	
	$sock=new sockets();
	$FreeWebsDisableMOdQOS=$sock->GET_INFO("FreeWebsDisableMOdQOS");
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [DEBUG] Apache FreeWebsDisableMOdQOS = $FreeWebsDisableMOdQOS ...\n";}
	if(!is_numeric($FreeWebsDisableMOdQOS)){$FreeWebsDisableMOdQOS=0;}
	if($FreeWebsEnableModQOS==0){$FreeWebsDisableMOdQOS=1;}
	
	
	if($FreeWebsDisableMOdQOS==1){
		unset($array["qos_module"]);
		@unlink("$DAEMON_PATH/mods-enabled/qos_module.load");
	}
	


	
if($FreeWebsEnableModEvasive==1){
		if(is_file("$APACHE_MODULES_PATH/mod_evasive20.so")){
			$a[]="LoadModule evasive20_module $APACHE_MODULES_PATH/mod_evasive20.so";
			echo "Starting......: ".date("H:i:s")." [INIT]: Apache module 'mod_evasive2' enabled\n";
			@file_put_contents("$DAEMON_PATH/mods-enabled/mod_evasive.load",@implode("\n",$a));
		}else{
			echo "Starting......: ".date("H:i:s")." [INIT]: Apache $APACHE_MODULES_PATH/mod_evasive20.so no such file\n";
		}
	}else{echo "Starting......: ".date("H:i:s")." [INIT]: Apache module 'mod_evasive2' disabled\n";}


	$sql="SELECT COUNT(servername) as tcount FROM freeweb WHERE UseReverseProxy=1";
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [DEBUG] Apache $sql\n";}
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache ". $ligne["tcount"]." Reverse Proxy\n";
	
		$proxys_mods["proxy_module"]="mod_proxy.so";
		$proxys_mods["proxy_http_module"]="mod_proxy_http.so";
		$proxys_mods["proxy_ftp_module"]="mod_proxy_ftp.so";
		$proxys_mods["proxy_connect_module"]="mod_proxy_connect.so";
		$proxys_mods["headers_module"]="mod_headers.so";
		$proxys_mods["deflate_module"]="mod_deflate.so";
		$proxys_mods["xml2enc_module"]="mod_xml2enc.so";
		$proxys_mods["proxy_html_module"]="mod_proxy_html.so";
		
		$proxys_orgs[]="proxy_ajp.load";  
		$proxys_orgs[]="proxy_balancer.load";   
		$proxys_orgs[]="proxy.conf";   
		$proxys_orgs[]="proxy_connect.load";   
		$proxys_orgs[]="proxy_ftp.load";   
		$proxys_orgs[]="proxy_html.conf";  
		$proxys_orgs[]="proxy_html.load";   
		$proxys_orgs[]="proxy_http.load";   
		$proxys_orgs[]="proxy.load";   
		$proxys_orgs[]="proxy_scgi.load"; 
		
		if(is_file("/etc/httpd/conf.d/proxy_ajp.conf")){@unlink("/etc/httpd/conf.d/proxy_ajp.conf");}
		
		while (list ($module, $lib) = each ($proxys_orgs) ){if(is_file("$DAEMON_PATH/mods-enabled/$lib")){@unlink("$DAEMON_PATH/mods-enabled/$lib");}}
		while (list ($module, $lib) = each ($proxys_mods) ){if(is_file("$DAEMON_PATH/mods-enabled/$module.load")){@unlink("$DAEMON_PATH/mods-enabled/$module.load");}}
			
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache {$ligne["tcount"]} reverse proxy(s)\n";
	$countDeProxy=$ligne["tcount"];
	if($FreeWebsEnableOpenVPNProxy==1){if($FreeWebsOpenVPNRemotPort<>null){$countDeProxy=$countDeProxy+1;}}
	
	
	if($users->EJABBERD_INSTALLED){if($countDeProxy==0){$countDeProxy=1;}}
	
	
	if($countDeProxy>0){
		reset($proxys_mods);
		while (list ($module, $lib) = each ($proxys_mods) ){
			if(!is_file("$APACHE_MODULES_PATH/$lib")){echo "Starting......: ".date("H:i:s")." [INIT]: Apache module '$module' '$lib' no such file\n";continue;}
			echo "Starting......: ".date("H:i:s")." [INIT]: Apache module '$module' enabled\n";
			$final_proxys[]="LoadModule $module $APACHE_MODULES_PATH/$lib";
		}
		
		@file_put_contents("$DAEMON_PATH/mods-enabled/proxy_module.load", @implode("\n", $final_proxys));
	}		
	
	
	while (list ($module, $lib) = each ($array) ){
		if(!is_file("$APACHE_MODULES_PATH/$lib")){echo "Starting......: ".date("H:i:s")." [INIT]: Apache module '$module' '$lib' no such file\n";continue;}
		echo "Starting......: ".date("H:i:s")." [INIT]: Apache module '$module' enabled\n";
		@file_put_contents("$DAEMON_PATH/mods-enabled/$module.load","LoadModule $module $APACHE_MODULES_PATH/$lib");
		
	}
	ZarafaWebAccessInFrontEnd($DAEMON_PATH);
	
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache testing configuration file\n";
	if(!TestingApacheConfigurationFile()){
		echo "Starting......: ".date("H:i:s")." [INIT]: Apache testing configuration file FAILED !! restore old config\n";
		while (list ($index, $restorefile) = each ($GLOBALS["ToRestore"]) ){
			$basename=basename($restorefile);
			echo "Starting......: ".date("H:i:s")." [INIT]: Apache Removing $DAEMON_PATH/$basename\n";
			@unlink("$DAEMON_PATH/$basename");
			echo "Starting......: ".date("H:i:s")." [INIT]: Apache Restoring $restorefile\n";
			@copy($restorefile, "$DAEMON_PATH/$basename");
		}
	}
	


	
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache terminated... next process\n";
	
}	

function mod_security(){
	$sock=new sockets();
	$users=new usersMenus();
	$httpdconf=$GLOBALS["CLASS_UNIX"]->LOCATE_APACHE_CONF_PATH();
	$d_path=$GLOBALS["CLASS_UNIX"]->APACHE_DIR_SITES_ENABLED();
	$DAEMON_PATH=$GLOBALS["CLASS_UNIX"]->getmodpathfromconf($httpdconf);
	$APACHE_MODULES_PATH=$GLOBALS["CLASS_UNIX"]->APACHE_MODULES_PATH();
	
	@unlink("$DAEMON_PATH/mods-enabled/mod_security.load");
	
	
	
	
	$free=new freeweb();
	if(!$free->mod_security_ifvailable()){
		echo "Starting......: ".date("H:i:s")." [INIT]: Apache module 'mod_security2' cannot be added\n";
		return;
	}
	
	

	$a[]="LoadFile /usr/lib/libxml2.so.2";
	$a[]="LoadModule security2_module $APACHE_MODULES_PATH/mod_security2.so";
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache module 'mod_security2' enabled\n";
	@file_put_contents("$DAEMON_PATH/mods-enabled/mod_security.load",@implode("\n",$a));
	unset($a);
	
	$u[]="LoadModule unique_id_module   $APACHE_MODULES_PATH/mod_unique_id.so\n";
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache module 'mod_unique_id' enabled\n";
	@file_put_contents("$DAEMON_PATH/mods-enabled/mod_unique_id.load",@implode("\n",$u));
	unset($u);
	
	$SecServerSignature=$sock->GET_INFO("SecServerSignature");
	$f[]="<IfModule security2_module>";
	$f[]="   SecRuleEngine On";
	if($SecServerSignature<>null){
		$f[]="   SecServerSignature\t\"{$SecServerSignature}\"";
	}
	//$f[]="   #SecFilterCheckURLEncoding {$Params["SecFilterCheckURLEncoding"]}";
	//$f[]="   #SecFilterCheckUnicodeEncoding {$Params["SecFilterCheckUnicodeEncoding"]}";
	//$f[]="   SecFilterForceByteRange 1 255";
	//$f[]="   SecAuditEngine RelevantOnly";
	$f[]="   SecAuditEngine RelevantOnly";
	$f[]="   SecAuditLog /var/log/apache2/modsec_audit_log";
	$f[]="   SecDebugLog /var/log/apache2/modsec_debug_log";
	$f[]="   SecDebugLogLevel 0";
	$f[]="   SecRequestBodyAccess Off";
	$f[]="   SecDefaultAction \"phase:2,deny,log,status:'Hello World!'\"";
	$f[]="</IfModule>\n\n";
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache $DAEMON_PATH/mod_security.conf\n";
	@file_put_contents("$DAEMON_PATH/mod_security.conf",@implode("\n",$f));
	unset($f);
	
	
	if(is_file("$DAEMON_PATH/mod_security.conf")){
		@unlink("$DAEMON_PATH/BackupConf/mod_security.conf");
		@copy("$DAEMON_PATH/ports.conf", "$DAEMON_PATH/BackupConf/mod_security.conf");
		$GLOBALS["ToRestore"][]="$DAEMON_PATH/BackupConf/mod_security.conf";
	}
	
	
	
}

function mod_bw_module_must_be_enabled(){
	$q=new mysql();
	$sql="SELECT COUNT(*) as tcount FROM freeweb WHERE enabled=1 AND bandlimit=1";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$sum=$ligne["tcount"];
	if($sum>0){return true;}
}


function YfiAdds(){
	if(!is_file("/var/www/c2/index.php")){return;}
	$f[]="## -- YFi begin";
	$f[]="<Directory  /var/www/c2>";
	$f[]="    AllowOverride All";
	$f[]="</Directory>";
	$f[]="#-------COMPRESS CONTENT-----------";
	$f[]="# place filter 'DEFLATE' on all outgoing content";
	$f[]="SetOutputFilter DEFLATE";
	$f[]="# exclude uncompressible content via file type";
	$f[]="SetEnvIfNoCase Request_URI \.(?:exe|t?gz|jpg|png|pdf|zip|bz2|sit|rar)$ no-gzip";
	$f[]="#dont-vary";
	$f[]="# Keep a log of compression ratio on each request";
	$f[]="DeflateFilterNote Input instream";
	$f[]="DeflateFilterNote Output outstream";
	$f[]="DeflateFilterNote Ratio ratio";
	$f[]="LogFormat '\"%r\" %{outstream}n/%{instream}n (%{ratio}n%%)' deflate";
	$f[]="CustomLog /var/log/apache2/deflate.log deflate";
	$f[]="# Properly handle old browsers that do not support compression";
	$f[]="BrowserMatch ^Mozilla/4 gzip-only-text/html";
	$f[]="BrowserMatch ^Mozilla/4\.0[678] no-gzip";
	$f[]="BrowserMatch \bMSIE !no-gzip !gzip-only-text/html";
	$f[]="#----------------------------------";
	$f[]="";
	$f[]="#------ADD EXPIRY DATE-------------";
	$f[]="<FilesMatch \"\.(ico|pdf|flv|jpg|jpeg|png|gif|js|css|swf)$\">";
	$f[]="    Header set Expires \"Thu, 15 Apr 2012 20:00:00 GMT\"";
	$f[]="</FilesMatch>";
	$f[]="#----------------------------------";
	$f[]="";
	$f[]="#--------Remove ETags --------------------";
	$f[]="FileETag none";
	$f[]="#-----------------------------------------";
	$f[]="## -- YFi end";	
	
	$unix=new unix();
	$APACHE_SRC_ACCOUNT=$unix->APACHE_SRC_ACCOUNT();
	$APACHE_SRC_GROUP=$unix->APACHE_SRC_GROUP();
	$unix->chmod_func(0755, "/var/www/c2/*");
	$unix->chown_func($APACHE_SRC_ACCOUNT, $APACHE_SRC_GROUP,"/var/www/c2/*");
	$unix->chown_func($APACHE_SRC_ACCOUNT, $APACHE_SRC_GROUP,"/var/www/c2/yfi_cake/*");
	
	
	return @implode("\n", $f);
	
}

function apache_permissions(){
	$unix=new unix();
	$APACHE_SRC_ACCOUNT=$unix->APACHE_SRC_ACCOUNT();
	$APACHE_SRC_GROUP=$unix->APACHE_SRC_GROUP();
	
	$f[]="/var/log/apache2";
	$f[]="/var/cache/apache2";
	$f[]="/var/cache/apache2/mod_pagespeed";
	$f[]="/etc/apache2/logs";
	$f[]="/var/lib/apache2/fastcgi";
	$f[]="/var/run/apache2";
	$f[]="/var/www";
	while (list ($index, $dir) = each ($f)){
		echo "Starting......: ".date("H:i:s")." [INIT]: apache2 apply permissions on `$dir`\n";
		if(!is_dir($dir)){@mkdir($dir,0755,true);}
		@chown($dir,$APACHE_SRC_ACCOUNT);
		@chgrp($dir, $APACHE_SRC_GROUP);
		$unix->chown_func($APACHE_SRC_ACCOUNT,$APACHE_SRC_GROUP,"$dir/*");
		$unix->chmod_func(0755, $dir);
	}
	
}

function apache_security($DAEMON_PATH){
	$sock=new sockets();
	$unix=new unix();
	
	$APACHE_SRC_ACCOUNT=$unix->APACHE_SRC_ACCOUNT();
	$APACHE_SRC_GROUP=$unix->APACHE_SRC_GROUP();
	
	
	apache_permissions();
	$ApacheServerTokens=$sock->GET_INFO("ApacheServerTokens");
	$ApacheServerSignature=$sock->GET_INFO("ApacheServerSignature");
	if(!is_numeric($ApacheServerSignature)){$ApacheServerSignature=1;}
	if($ApacheServerTokens==null){$ApacheServerTokens="Full";}	
	if($ApacheServerSignature==1){$ServerSignature="On";}else{$ServerSignature="Off";}
	
	$httpd[]="ServerTokens $ApacheServerTokens";
	$httpd[]="ServerSignature $ServerSignature";
	$httpd[]="";
	
	if(is_file("$DAEMON_PATH/security.conf")){
		@unlink("$DAEMON_PATH/BackupConf/security.conf");
		@copy("$DAEMON_PATH/security.conf", "$DAEMON_PATH/BackupConf/security.conf");
		$GLOBALS["ToRestore"][]="$DAEMON_PATH/BackupConf/security.conf";
	}
	
	
	@file_put_contents("$DAEMON_PATH/security.conf",@implode("\n",$httpd));
	
}


function EnableMods(){
	$sock=new sockets();
	$FreeWebDisableSSL=intval(trim($sock->GET_INFO("FreeWebDisableSSL")));
	@unlink("/etc/apache2/mods-enabled/ssl.load");
	@unlink("/etc/apache2/mods-enabled/ssl.conf");
	if($FreeWebDisableSSL==1){return;}
	
	
	if(is_file("/etc/apache2/mods-available/ssl.load")){
		shell_exec("/bin/ln -s /etc/apache2/mods-available/ssl.load /etc/apache2/mods-enabled/ssl.load >/dev/null 2>&1");
	}
	if(is_file("/etc/apache2/mods-available/ssl.conf")){
		shell_exec("/bin/ln -s /etc/apache2/mods-available/ssl.conf /etc/apache2/mods-enabled/ssl.conf >/dev/null 2>&1");
	}	
}

function CheckLibraries(){
	$prefixOutput="Starting......: ".date("H:i:s")." [INIT]: Apache \"Engine\"";
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	if(!isset($GLOBALS["CLASS_USERS_MENUS"])){$GLOBALS["CLASS_USERS_MENUS"]=new usersMenus();}
	if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
	if(!isset($GLOBALS["CLASS_LDAP"])){$GLOBALS["CLASS_LDAP"]=new clladp();}
	if(!isset($GLOBALS["ECHO_BIN"])){$GLOBALS["ECHO_BIN"]=$GLOBALS["CLASS_UNIX"]->find_program("echo");}
	if(!isset($GLOBALS["MD5SUM_BIN"])){$GLOBALS["MD5SUM_BIN"]=$GLOBALS["CLASS_UNIX"]->find_program("md5sum");}
	if(!isset($GLOBALS["CUT_BIN"])){$GLOBALS["CUT_BIN"]=$GLOBALS["CLASS_UNIX"]->find_program("cut");}
	
	$EnableOpenLDAP=1;
	if(is_file("/etc/artica-postfix/settings/Daemons/EnableOpenLDAP")){
		$EnableOpenLDAP=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableOpenLDAP"));
	}

	
	 if($EnableOpenLDAP==1){
		if($GLOBALS["CLASS_LDAP"]->ldapFailed){
			echo "$prefixOutput [".__LINE__."] OpenLDAP system not ready...\n";
			build_progress("OpenLDAP system not ready", 110);
			die();
		}
	 }
	
	$q=new mysql();
	if(!$q->TestingConnection()){
		echo "$prefixOutput [".__LINE__."] MySQL system not ready...\n";
		build_progress("MySQL system not ready", 110);
		die();	
	}
	
}

function TestingApacheConfigurationFile(){
	CheckLibraries();
	$unix=new unix();
	$prefixOutput="Starting......: ".date("H:i:s")." [INIT]: Apache Check";
	$httpdconf=$GLOBALS["CLASS_UNIX"]->LOCATE_APACHE_CONF_PATH();
	$apache2ctl=$GLOBALS["CLASS_UNIX"]->LOCATE_APACHE_CTL();
	$unix->chmod_func(0755, "/etc/apache2/sites-enabled/*");
	
	
	if(!is_file($apache2ctl)){return true;}
	exec("$apache2ctl -f $httpdconf -S 2>&1",$results);
	echo "$prefixOutput [".__LINE__."] verify configuration...\n";
	
	while (list ($index, $line) = each ($results) ){
		echo "$prefixOutput [".__LINE__."] `$line`\n";
		
		if(preg_match("#Syntax error on line ([0-9]+) of (.+?):#",$line,$re)){
			@copy($re[2], "/root/".basename($re[2]));
			echo "$prefixOutput [".__LINE__."] `".basename($re[2])."` as been copied in /root...\n";
		}
		
		if(preg_match("#Syntax OK#i", $line)){
			echo "$prefixOutput [".__LINE__."] Syntax OK...\n";
			return true;}
	}
	echo "$prefixOutput [".__LINE__."] Syntax Failed !!!...\n";
	@file_put_contents("/etc/artica-postfix/settings/Daemons/ApacheLastError", @implode("\n", $results));
	
}

function buildHost($uid=null,$hostname,$ssl=null,$d_path=null,$Params=array()){
	$prefixOutput="Starting......: ".date("H:i:s")." [INIT]: Apache \"$hostname\"";
	echo "$prefixOutput [".__LINE__."] Building \"$hostname\"\n";
	build_progress("create_cron_task()", 6);
	create_cron_task();
	build_progress("CheckLibraries()", 7);
	CheckLibraries();
	$unix=$GLOBALS["CLASS_UNIX"];
	$sock=$GLOBALS["CLASS_SOCKETS"];
	$users=$GLOBALS["CLASS_USERS_MENUS"];
	$AuthLDAP=0;$mod_pagespedd=null;
	$EnableLDAPAllSubDirectories=0;
	$APACHE_MOD_AUTHNZ_LDAP=$users->APACHE_MOD_AUTHNZ_LDAP;
	$APACHE_MOD_PAGESPEED=$users->APACHE_MOD_PAGESPEED;
	build_progress("Loading $hostname configuration", 8);
	$freeweb=new freeweb($hostname);
	$Params=$freeweb->Params;
	
	
	
	if($freeweb->servername==null){
		build_progress("Fatal $hostname no such servername", 110);
		echo "$prefixOutput [".__LINE__."] freeweb->servername no such servername \n";return;}
	
	$FreeWebsEnableOpenVPNProxy=$sock->GET_INFO("FreeWebsEnableOpenVPNProxy");
	$FreeWebsOpenVPNRemotPort=trim($sock->GET_INFO("FreeWebsOpenVPNRemotPort"));
	$FreeWebDisableSSL=trim($sock->GET_INFO("FreeWebDisableSSL"));

	if(!is_numeric($FreeWebsEnableOpenVPNProxy)){$FreeWebsEnableOpenVPNProxy=0;}
	if(!is_numeric($FreeWebDisableSSL)){$FreeWebDisableSSL=0;}
	if($FreeWebDisableSSL==1){if($freeweb->SSL_enabled){echo "$prefixOutput [".__LINE__."] SSL is globally disabled \n";}$freeweb->SSL_enabled=false;}

	
	$d_path=$freeweb->APACHE_DIR_SITES_ENABLED;
	
	
	if(isset($Params["LDAP"]["enabled"])){$AuthLDAP=$Params["LDAP"]["enabled"];}
	if(isset($Params["LDAP"]["EnableLDAPAllSubDirectories"])){$EnableLDAPAllSubDirectories=$Params["LDAP"]["EnableLDAPAllSubDirectories"];}

	
	//server signature.
	if(!isset($Params["SECURITY"])){$Params["SECURITY"]["ServerSignature"]=null;}
	if(!isset($Params["SECURITY"]["ServerSignature"])){$Params["SECURITY"]["ServerSignature"]=null;}
	$ServerSignature=$Params["SECURITY"]["ServerSignature"];
	if($ServerSignature==null){$ServerSignature=$sock->GET_INFO("ApacheServerSignature");}
	if(!is_numeric($ServerSignature)){$ServerSignature=1;}
	if($ServerSignature==1){$ServerSignature="On";}else{$ServerSignature="Off";}
	
	
	
	
	if(!$APACHE_MOD_AUTHNZ_LDAP){$AuthLDAP=0;}
	
	$apache_usr=$unix->APACHE_SRC_ACCOUNT();
	$apache_group=$unix->APACHE_SRC_GROUP();
	$FreeWebListenPort=$sock->GET_INFO("FreeWebListenPort");
	$FreeWebListenSSLPort=$sock->GET_INFO("FreeWebListenSSLPort");
	$FreeWebListen=$unix->APACHE_ListenDefaultAddress();
	$FreeWebsDisableSSLv2=$sock->GET_INFO("FreeWebsDisableSSLv2");
	build_progress("Building $hostname configuration [".__LINE__."]", 10);
	
	if($apache_usr==null){
		build_progress("Fatal could not find apache username", 110);
		echo "WARNING !!! could not find apache username!!!\n";
		return false;
	}
	
	if($FreeWebListen==null){$FreeWebListen="*";}
	if($FreeWebListen<>"*"){$FreeWebListenApache="$FreeWebListen";}	
	if($FreeWebListenSSLPort==null){$FreeWebListenSSLPort=443;}
	
	if(!is_numeric($FreeWebListenSSLPort)){$FreeWebListenSSLPort=443;}
	if(!is_numeric($FreeWebListenPort)){$FreeWebListenPort=80;}
	if(!is_numeric($FreeWebsDisableSSLv2)){$FreeWebsDisableSSLv2=0;}
	$unix=new unix();
	if($unix->isNGnx()){
		$FreeWebListenPort=82;
		$FreeWebListenSSLPort=447;
		$FreeWebListen="127.0.0.1";
	}
	
	if($unix->IsSquidReverse()){
		$FreeWebListenPort=82;
		$FreeWebListenSSLPort=447;
		$FreeWebListen="127.0.0.1";
	}

	$port=$FreeWebListenPort;
	if($uid<>null){
		$u=new user($uid);
		$ServerAdmin=$u->mail;
	}
	if(!isset($ServerAdmin)){$ServerAdmin="webmaster@$hostname";}
	$DirectoryIndex=$freeweb->DirectoryIndex();
	if($hostname=="_default_"){$FreeWebListen="_default_";}
	$LoadModules=$freeweb->LoadModules();
	
	if($unix->isNGnx()){$freeweb->SSL_enabled=0;}
	if($FreeWebDisableSSL==1){$freeweb->SSL_enabled=0;}
	build_progress("Building $hostname configuration [".__LINE__."]", 11);
	
	echo "$prefixOutput [".__LINE__."] SSL_enabled = $freeweb->SSL_enabled\n";
	
	if($freeweb->SSL_enabled){
		
		$port=$FreeWebListenSSLPort;
		if($freeweb->ServerPort>0){$FreeWebListenPort=$freeweb->ServerPort;}
		$conf[]="<VirtualHost $FreeWebListen:$FreeWebListenPort>";
		if($hostname<>"_default_"){$conf[]="\tServerName $hostname";}
		$conf[]="\tServerSignature $ServerSignature";
		$conf[]="\tRewriteEngine On";
		if($freeweb->Forwarder==0){$conf[]="\tRewriteCond %{HTTPS} off";}
		$IsSquidReverse=false;
		if($unix->IsSquidReverse()){$IsSquidReverse=true;}
		if($unix->isNGnx()){$IsSquidReverse=true;}
		
		if($freeweb->Forwarder==0){
			$redirectPage=null;
			
			if($IsSquidReverse){
				if($FreeWebListenSSLPort<>443){
					$conf[]="\tRewriteRule (.*) https://%{HTTP_HOST}:$FreeWebListenSSLPort$redirectPage";
				}else{
					$conf[]="\tRewriteRule (.*) https://%{HTTP_HOST}$redirectPage";
				}
			}else{
				$conf[]="\tRewriteRule (.*) https://%{HTTP_HOST}$redirectPage";
			}
		}
			
			
		if($freeweb->Forwarder==1){$conf[]="\tRewriteRule (.*) $freeweb->ForwardTo";}
		$conf[]="</VirtualHost>";
		$conf[]="";
		$FreeWebListenPort=$FreeWebListenSSLPort;
	}
	build_progress("Building $hostname configuration [".__LINE__."]", 12);
	$freeweb->CheckDefaultPage();
	build_progress("Building $hostname configuration [".__LINE__."]", 13);
	$freeweb->CheckWorkingDirectory();
	build_progress("Building $hostname configuration [".__LINE__."]", 14);
	$ServerAlias=$freeweb->ServerAlias();
	
	
	build_progress("Building $hostname configuration [".__LINE__."]", 15);
	echo "$prefixOutput [".__LINE__."] Listen $FreeWebListen:$FreeWebListenPort\n";
	echo "$prefixOutput [".__LINE__."] Directory $freeweb->WORKING_DIRECTORY\n";
	echo "$prefixOutput [".__LINE__."] Groupware \"$freeweb->groupware\"\n";
	if(!preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $freeweb->ServerIP)){$freeweb->ServerIP=null;}
	
	if($LoadModules<>null){$conf[]="$LoadModules";}
	
	
	if($freeweb->ServerIP==null){
			if($freeweb->ServerPort>0){
				$conf[]="<VirtualHost $FreeWebListen:$freeweb->ServerPort>";
			}else{
				$conf[]="<VirtualHost $FreeWebListen:$FreeWebListenPort>";
			}
	}else{
		if($freeweb->ServerPort>0){
				$conf[]="<VirtualHost $freeweb->ServerIP:$freeweb->ServerPort>";
			}else{
				$conf[]="<VirtualHost $freeweb->ServerIP:$FreeWebListenPort>";
			}
		
	}
	build_progress("Building $hostname configuration [".__LINE__."]", 16);
	$AddType=$freeweb->AddType();
	if($AddType<>null){$conf[]=$AddType;}	
	
	if($freeweb->SSL_enabled){
		$conf[]="\tSetEnvIf User-Agent \".*MSIE.*\" nokeepalive ssl-unclean-shutdown downgrade-1.0 force-response-1.0";
		$conf[]="\tSSLEngine on";
		echo "$prefixOutput [".__LINE__."] SSLEngine()\n";
		$certificates=$freeweb->SSLEngine();
		if($certificates<>null){$conf[]=$certificates;}
		if($FreeWebsDisableSSLv2==1){
			$conf[]="\tSSLProtocol -ALL +SSLv3 +TLSv1";
			$conf[]="\tSSLCipherSuite ALL:!aNULL:!ADH:!eNULL:!LOW:!EXP:RC4+RSA:+HIGH:+MEDIUM";
		}			
	}

	$unix=new unix();
	if($hostname<>"_default_"){
		$conf[]="\tServerName $hostname";
		
		
		
		if($ServerAlias<>null){$conf[]=$ServerAlias;}
		$sock=new sockets();
		$FreeWebsEnableOpenVPNProxy=$sock->GET_INFO("FreeWebsEnableOpenVPNProxy");
		$FreeWebsOpenVPNRemotPort=trim($sock->GET_INFO("FreeWebsOpenVPNRemotPort"));
		if(!is_numeric($FreeWebsEnableOpenVPNProxy)){$FreeWebsEnableOpenVPNProxy=0;}
		if(!is_numeric($FreeWebsOpenVPNRemotPort)){$FreeWebsOpenVPNRemotPort=0;}
		if($FreeWebsEnableOpenVPNProxy==1){
			if($FreeWebsOpenVPNRemotPort>0){
				$conf[]="\tProxyRequests On";
				$conf[]="\tProxyVia On";
				$conf[]="\tAllowCONNECT 1194";
				$conf[]="\tKeepAlive On";
			}
		}
	}
		build_progress("Building $hostname configuration [".__LINE__."]", 17);
		$content_plus=$freeweb->content_plus;
		$php_open_base_dir=$freeweb->open_basedir();
		$geoip=$freeweb->mod_geoip();
		$mod_status=$freeweb->mod_status();
		$mod_evasive=$freeweb->mod_evasive();
		$Charsets=$freeweb->Charsets();
		$php_values=$freeweb->php_values();
		$WebdavHeader=$freeweb->WebdavHeader();
		$QUOS=$freeweb->QUOS();
		$Aliases=$freeweb->Aliases();
		$mod_cache=$freeweb->mod_cache();
		$mod_fcgid=$freeweb->mod_fcgid();
		$RewriteEngine=$freeweb->RewriteEngine();
		$mod_bw=$freeweb->mod_bw();
		$mpm_itk_module=$freeweb->mpm_itk_module();
		$ErrorDocument=$freeweb->ErrorDocument();
		$Apache2_AuthenNTLM=$freeweb->Apache2_AuthenNTLM();
		
		if($APACHE_MOD_PAGESPEED){$mod_pagespedd=$freeweb->mod_pagespeed();}
		$conf[]="\tServerAdmin $ServerAdmin";
		$conf[]="\tServerSignature $ServerSignature";
		$conf[]="\tDocumentRoot $freeweb->WORKING_DIRECTORY";
		
		
		if($content_plus<>null){$conf[]="\n############## personal content #############\n$content_plus\n############################\n";}
		if($ErrorDocument<>null){$conf[]=$ErrorDocument;}
		if($mpm_itk_module<>null){$conf[]=$mpm_itk_module;}
		if($mod_evasive<>null){   $conf[]=$mod_evasive;}
		if($Charsets<>null){      $conf[]=$Charsets;}
		if($php_values<>null){    $conf[]=$php_values;}
		if($WebdavHeader<>null){  $conf[]=$WebdavHeader;}
		if($QUOS<>null){	      $conf[]=$QUOS;}
		if($mod_bw<>null){	      $conf[]=$mod_bw;}		
		if($Aliases<>null){	      $conf[]=$Aliases;}
		if($mod_cache<>null){	  $conf[]=$mod_cache;}
		if($geoip<>null){	      $conf[]=$geoip;}
		if($mod_pagespedd<>null){ $conf[]=$mod_pagespedd;
		
		shell_exec("/bin/chown -R $apache_usr:$apache_group /var/cache/apache2/mod_pagespeed/$hostname");}
		if($mod_status<>null){    $conf[]=$mod_status;}
		build_progress("Building $hostname configuration [".__LINE__."]", 18);
		
		$ldapRule=null;
		
			if($freeweb->groupware=="ZARAFA"){
				$ZarafaWebNTLM=$sock->GET_INFO("ZarafaWebNTLM");	
				if(!is_numeric($ZarafaWebNTLM)){$ZarafaWebNTLM=0;}
				$PARAMS=$freeweb->Params["ZARAFAWEB_PARAMS"];
				if(!isset($PARAMS["ZarafaWebNTLM"])){$PARAMS["ZarafaWebNTLM"]=$ZarafaWebNTLM;}
				if(!is_numeric($PARAMS["ZarafaWebNTLM"])){$PARAMS["ZarafaWebNTLM"]=$ZarafaWebNTLM;}
				$ZarafaWebNTLM=$PARAMS["ZarafaWebNTLM"];				
				if($ZarafaWebNTLM==1){$AuthLDAP=1;}
			}		
		
			build_progress("Building $hostname configuration [".__LINE__."]", 19);
		if($AuthLDAP==1){
			echo "Starting......: ".date("H:i:s")." [INIT]: Apache \"$hostname\" ldap authentication enabled\n";
			$ldap=$GLOBALS["CLASS_LDAP"];
			$dn_master_branch="dc=organizations,$ldap->suffix";
			if($uid<>null){
				$usr=new user($uid);
				$dn_master_branch="ou=users,ou=$usr->ou,dc=organizations,$ldap->suffix";
			}
			
			$authentication_banner=base64_decode($freeweb->Params["LDAP"]["authentication_banner"]);
			if($authentication_banner==null){$authentication_banner="$hostname auth:";}
			
		    $ldapAuth[]="\t\tAuthName \"$authentication_banner\"";
		    $ldapAuth[]="\t\tAuthType Basic";
		    $ldapAuth[]="\t\tAuthLDAPURL ldap://$ldap->ldap_host:$ldap->ldap_port/$dn_master_branch?uid";
		   	$ldapAuth[]="\t\tAuthLDAPBindDN cn=$ldap->ldap_admin,$ldap->suffix";
		   	$ldapAuth[]="\t\tAuthLDAPBindPassword $ldap->ldap_password";
			$ldapAuth[]="\t\tAuthLDAPGroupAttribute memberUid";
			$ldapAuth[]="\t\tAuthBasicProvider ldap";
		    $ldapAuth[]="\t\tAuthzLDAPAuthoritative off";
		    $AuthUsers=$freeweb->AuthUsers();
		    if($AuthUsers<>null){$ldapAuth[]=$AuthUsers;}else{$ldapAuth[]="\t\trequire valid-user";}	
		    $ldapAuth[]="";	
		    $ldapRule=@implode("\n", $ldapAuth);
		}		
	
	
	//DIRECTORY
	build_progress("Building $hostname configuration [".__LINE__."]", 20);
	$OptionExecCGI=null;
	$allowFrom=$freeweb->AllowFrom();
	$JkMount=$freeweb->JkMount();	
	if($JkMount<>null){$conf[]=$JkMount;}
	$WebDav=$freeweb->WebDav();
	$AllowOverride=$freeweb->AllowOverride();
	$mod_rewrite=$freeweb->mod_rewrite();
	$IndexIgnores=$freeweb->IndexIgnores();
	$DirectorySecond=$freeweb->DirectorySecond();
	if($mod_fcgid<>null){$OptionExecCGI=" +ExecCGI";}
	$DirectoryContent=$freeweb->DirectoryContent();
	$SymLinksIfOwnerMatch=null;
	$FollowSymLinks=null;
	
		$Indexes=" Indexes";
		if($freeweb->Params["SECURITY"]["FreeWebsDisableBrowsing"]==1){$Indexes=" -Indexes";}
		if($freeweb->Params["SECURITY"]["SymLinksIfOwnerMatch"]==1){$SymLinksIfOwnerMatch=" +SymLinksIfOwnerMatch";}
		if($freeweb->Params["SECURITY"]["FollowSymLinks"]==1){$FollowSymLinks=" +FollowSymLinks";}
		
		
		
		build_progress("Building $hostname configuration [".__LINE__."]", 21);
		$conf[]="\n\t<Directory \"$freeweb->WORKING_DIRECTORY/\">";
		if($Apache2_AuthenNTLM<>null){
			$conf[]=$Apache2_AuthenNTLM;
		}
		
		
		if($DirectoryContent==null){
			$DirectoryIndex=$freeweb->DirectoryIndex();
			$conf[]="\t\tDirectoryIndex $DirectoryIndex";
	   		$conf[]="\t\tOptions{$Indexes}{$FollowSymLinks}{$SymLinksIfOwnerMatch} MultiViews$OptionExecCGI";
	   		if($IndexIgnores<>null){$conf[]=$IndexIgnores;}
		   	if($WebDav<>null){$conf[]=$WebDav;}
			if($AllowOverride<>null){$conf[]=$AllowOverride;}else{$conf[]="\t\tAllowOverride AuthConfig FileInfo";}
			$conf[]="\t\tOrder allow,deny";
			if($allowFrom<>null){$conf[]=$allowFrom;}
		}else{
			$conf[]=$DirectoryContent;
		}
		build_progress("Building $hostname configuration [".__LINE__."]", 22);
		if($geoip<>null){$conf[]="\t\tDeny from env=BlockCountry";}
		if($mod_rewrite<>null){$conf[]=$mod_rewrite;}
		if($ldapRule<>null){$conf[]=$ldapRule;}
		if($RewriteEngine<>null){ $conf[]=$RewriteEngine;}
		
		
		$conf[]="\t</Directory>\n";
		if($mod_fcgid<>null){    $conf[]=$mod_fcgid;}
		if($DirectorySecond<>null){$conf[]=$DirectorySecond;}
		build_progress("Building $hostname configuration [".__LINE__."]", 23);
		$zarafaProxy=$freeweb->ZarafaProxyJabberd();
		if($zarafaProxy<>null){$conf[]=$zarafaProxy;}
		build_progress("Building $hostname configuration [".__LINE__."]", 24);
		$WebDavFree=$freeweb->WebDavTable();
		if($WebDavFree<>null){$conf[]=$WebDavFree;}
		if($freeweb->UseReverseProxy==1){
	
		$conf[]=$freeweb->ReverseProxy();
		$conf[]="\t<Proxy *>";
			$conf[]="\t\tOrder allow,deny";
			$conf[]=$freeweb->AllowFrom();		
			if($AuthLDAP==1){
				echo "Starting......: ".date("H:i:s")." [INIT]: Apache \"$hostname\" ldap authentication enabled\n";
				$ldap=$GLOBALS["CLASS_LDAP"];
				$dn_master_branch="dc=organizations,$ldap->suffix";
				if($uid<>null){
					$usr=new user($uid);
					$dn_master_branch="ou=users,ou=$usr->ou,dc=organizations,$ldap->suffix";
				}
				if($freeweb->Params["LDAP"]["authentication_banner"]==null){$freeweb->Params["LDAP"]["authentication_banner"]="Please Logon";}
				$conf[]="";
			    $conf[]="\t\tAuthName \"". base64_decode($freeweb->Params["LDAP"]["authentication_banner"])."\"";
			    $conf[]="\t\tAuthType Basic";
			    $conf[]="\t\tAuthLDAPURL ldap://$ldap->ldap_host:$ldap->ldap_port/$dn_master_branch?uid";
			   	$conf[]="\t\tAuthLDAPBindDN cn=$ldap->ldap_admin,$ldap->suffix";
			   	$conf[]="\t\tAuthLDAPBindPassword $ldap->ldap_password";
			   	$conf[]="\t\tAuthLDAPGroupAttributeIsDN off";
			   	$conf[]="\t\tAuthLDAPGroupAttribute memberUid";
			    $conf[]="\t\tAuthBasicProvider ldap";
			    $conf[]="\t\tAuthzLDAPAuthoritative off";
		    	$AuthUsers=$freeweb->AuthUsers();
		    	if($AuthUsers<>null){$conf[]=$AuthUsers;}else{$conf[]="\t\trequire valid-user";}	
			    $conf[]="";	
		}
		$conf[]="\t</Proxy>";
	
	}
	build_progress("Building $hostname configuration [".__LINE__."]", 25);
	$conf[]=$freeweb->FilesRestrictions();	
	build_progress("Building $hostname configuration [".__LINE__."]", 26);
	$conf[]=$freeweb->mod_security();
	build_progress("Building $hostname configuration [".__LINE__."]", 27);
	$ScriptAliases=$freeweb->ScriptAliases();
	build_progress("Building $hostname configuration [".__LINE__."]", 28);
	
	
	if(!is_dir("/var/log/apache2/$hostname")){@mkdir("/var/log/apache2/$hostname",0755,true);}
	if($ScriptAliases<>null){$conf[]=$ScriptAliases;}
	$conf[]="\tLogFormat \"%h %{X-Forwarded-For}i %l %u %t \\\"%r\\\" %>s %b \\\"%{Referer}i\\\" \\\"%{User-Agent}i\\\" %V\" combinedv";
	$conf[]="\tCustomLog /var/log/apache2/common-access.log combinedv";
	$conf[]="\tErrorLog /var/log/apache2/error.log";
	$conf[]="\tLogLevel warn";
	$conf[]="</VirtualHost>";
	$conf[]="";
	
	
	
	$prefix_filename="artica-";
	$suffix_filename=".conf";
	$middle_filename=$hostname;
	
	build_progress("Building $hostname configuration [".__LINE__."]", 29);
	if($hostname=="_default_"){
		$prefix_filename="000-";
		$middle_filename="default";
		$suffix_filename=null;
		if($freeweb->SSL_enabled){
			$prefix_filename=null;
			$middle_filename="default-ssl";
			@file_put_contents("/etc/apache2/sites-enabled/default-ssl", @implode("\n", $conf));
		}
	}
		
	build_progress("Building $hostname configuration [".__LINE__."]", 30);
	$FileConfigurationPath="$d_path/$prefix_filename$middle_filename$suffix_filename";
	$FileConfigurationBackupPath="/root/$prefix_filename$middle_filename$suffix_filename";
	
	if(is_file($FileConfigurationPath)){
		@unlink($FileConfigurationBackupPath);
		echo "Starting......: ".date("H:i:s")." [INIT]: Apache backup old configuration\n";
		@copy($FileConfigurationPath, $FileConfigurationBackupPath);
	}
	
	if($GLOBALS["VERBOSE"]){
		echo "Starting......: ".date("H:i:s")." [INIT]: Apache saving *** $d_path/$prefix_filename$middle_filename$suffix_filename *** line ".__LINE__."\n";
	}
	
	build_progress("Building $hostname configuration [".__LINE__."]", 31);
	@file_put_contents($FileConfigurationPath,@implode("\n",$conf));
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache \"$hostname\" filename: '". basename("$d_path/$prefix_filename$middle_filename$suffix_filename")."' done\n";
	
	
	build_progress("Building $hostname configuration [".__LINE__."]", 32);
	$freeweb->phpmyadmin();
	if(!is_dir("$freeweb->WORKING_DIRECTORY")){
		@mkdir("$freeweb->WORKING_DIRECTORY",0755,true);
	}
	
	if(is_file("/etc/apache2/sites-enabled/000-default")){
		@touch("/etc/apache2/sites-enabled/000-default");
	}
	
	build_progress("Building $hostname {testing_configuration}", 33);
	if(!TestingApacheConfigurationFile()){
		build_progress("Building $hostname {testing_configuration} {failed}", 110);
		$freeweb->SetError(1);
		@unlink($FileConfigurationPath);
		if(is_file($FileConfigurationBackupPath)){
			echo "Starting......: ".date("H:i:s")." [INIT]: Apache restore old configuration file\n";
			@copy($FileConfigurationBackupPath,$FileConfigurationPath);
		}
		return false;
	}else{
		$freeweb->SetError(0);
	}
	
	

	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$chown=$unix->find_program("chown");
	$php=$unix->LOCATE_PHP5_BIN();
	
	if(is_file("/etc/php5/apache2/php.ini")){
		$timephpini=$unix->file_time_min("/etc/php5/apache2/php.ini");
		if($timephpini>60){shell_exec("$php /usr/share/artica-postfix/exec.php.ini.php");}
	}
	
	build_progress("Building $hostname {apply_permissions} - $freeweb->groupware -", 34);
	if($freeweb->groupware=="EYEOS"){install_EYEOS($hostname);}
	if($freeweb->groupware=="GROUPOFFICE"){group_office_install($hostname,true);}
	if($freeweb->groupware=="PIWIK"){install_PIWIK($hostname,true);}
	if($freeweb->groupware=="DRUPAL"){shell_exec("$nohup $php /usr/share/artica-postfix/exec.freeweb.php --drupal-infos \"$hostname\" >/dev/null 2>&1 &"); }
	if($freeweb->groupware=="WORDPRESS"){
		build_progress("Building $hostname verify wordpress website..", 35);
		system("$php /usr/share/artica-postfix/exec.wordpress.php \"$hostname\"");
		if(!is_file("$freeweb->WORKING_DIRECTORY/wp-config.php")){return false;}
	
	}
	
	
	
	
	build_progress("Building $hostname {apply_permissions}", 60);
	$freeweb->ApplyPermissions();
	
	build_progress("Building $hostname {checking_reverse_proxy}", 65);
	$freeweb->update_groupware_version();
	if($GLOBALS["NGINX_CONFIGURE"]){
		$EnableNginx=$sock->GET_INFO("EnableNginx");
		if(!is_numeric($EnableNginx)){$EnableNginx=1;}
		if($EnableNginx==1){
			$php=$unix->LOCATE_PHP5_BIN();
			build_progress("Building $hostname {checking_reverse_proxy} - NGINX", 70);
			system("$php /usr/share/artica-postfix/exec.nginx.php --reconfigure \"$hostname\"");
			build_progress("Building $hostname {checking_reverse_proxy} OK", 75);
		}
	}
	build_progress("Building $hostname -> DONE", 80);
	return true;
}

function remove_host($hostname){
	$freeweb=new freeweb($hostname);
	if(is_dir("/var/www/$hostname")){shell_exec("/bin/rm -rf /var/www/$hostname");}
	if($freeweb->IsGroupWareFromArtica()){
		$freeweb->delete();
		return;
	}
	
	if($freeweb->WebCopyID>0){
		$freeweb->delete();
		return;		
	}
	
	$mysql_database=$freeweb->mysql_database;
	$q=new mysql();
	if($q->DATABASE_EXISTS($mysql_database)){$q->DELETE_DATABASE($mysql_database);}
	if($freeweb->groupware=="POWERADMIN"){$freeweb->delete();return;}
	if($freeweb->groupware=="ARKEIA"){$freeweb->delete();return;}
	if($freeweb->groupware=="UPDATEUTILITY"){$freeweb->delete();return;}
	if($freeweb->groupware=="SARG"){$freeweb->delete();return;}
	if($hostname=="_default_"){$freeweb->delete();return;}
	if($freeweb->Forwarder==0){$freeweb->delete();return;}
	
	if(is_dir($freeweb->WORKING_DIRECTORY)){shell_exec("/bin/rm -rf $freeweb->WORKING_DIRECTORY");}
	$freeweb->delete();
	
}

function FDpermissions($servername=null){
	$servername=trim($servername);
	if($servername<>null){
		$pidfile="/usr/share/artica-postfix/pids/" .basename(__FILE__).".".__FUNCTION__.".$servername.pid";
		$sqq=" AND servername='$servername'";
		
	}else{
		$pidfile="/usr/share/artica-postfix/pids/" .basename(__FILE__).".".__FUNCTION__.".pid";
	}
	$unix=new unix();
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){
		echo "Already exists $pid\n";
		return;
	}
	@file_put_contents($pidfile,getmypid());
	
	
	if($GLOBALS["VERBOSE"]){echo "\n";}
	
	$alreadydir=array();
	$alreadyFiles=array();
	$sql="SELECT servername,EnbaleFDPermissions,FDPermissions FROM freeweb WHERE EnbaleFDPermissions=1$sqq";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){if($GLOBALS["VERBOSE"]){echo $q->mysql_error."\n";return;}}
	$count=mysql_num_rows($results);
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache checking permission web sites count:$count\n";
	if($count==0){return;}
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$FDPermissions=unserialize(base64_decode($ligne["FDPermissions"]));	
		if(!is_numeric($FDPermissions["SCHEDULE"])){$FDPermissions["SCHEDULE"]=60;}
		$servername=$ligne["servername"];
		if(!is_array($FDPermissions)){continue;}
		$timefile="/usr/share/artica-postfix/pids/" .basename(__FILE__).".".__FUNCTION__.".$servername.time";
		if(!$GLOBALS["FORCE"]){
			$time=$unix->file_time_min($timefile);
			if($GLOBALS["VERBOSE"]){echo "$servername::Timefile: $timefile -> $time minutes/{$FDPermissions["SCHEDULE"]} minutes\n";}
			if($time<$FDPermissions["SCHEDULE"]){
				if($GLOBALS["VERBOSE"]){echo "$servername::Timefile: -> NEXT;\n";}
				continue;
			}
		}
		
		
		@unlink($timefile);
		@file_put_contents($timefile,time());
		$freeweb=new freeweb($servername);
		$basePath=$freeweb->WORKING_DIRECTORY;
		if($GLOBALS["VERBOSE"]){echo "$servername::WORKING_DIRECTORY -> $basePath\n";}
		while (list ($index, $array) = each ($FDPermissions["PERMS"])){
		
			$ruleid=$index;
			$array["directory"]=trim($array["directory"]);
			if(substr($array["directory"],strlen($array["directory"]),1)=='/'){$array["directory"]=substr($array["directory"],0,strlen($array["directory"])-1);}
			$array["directory"]=str_replace("./","",$array["directory"]);
			$array["directory"]=str_replace("../","",$array["directory"]);
			if(trim($array["directory"])==null){$array["directory"]=$basePath;}else{$array["directory"]="$basePath/{$array["directory"]}";}
			if(!is_dir($array["directory"])){
				if($GLOBALS["VERBOSE"]){echo "$servername::{$array["directory"]} -> no such directory\n";}
				continue;
			}
			
			if($array["ext"]==null){$array["ext"]="*";}		
			$array["ext"]=str_replace("*.","",$array["ext"]);
			$array["ext"]=str_replace(".","",$array["ext"]);
			
			if(!is_numeric($array["chmoddir"])){$array["chmoddir"]="2570";}
			if(!is_numeric($array["chmodfile"])){$array["chmodfile"]="0460";}
			
			
			if(!isset($alreadydir[$array["directory"]])){
				if($GLOBALS["VERBOSE"]){echo "$servername::{$array["directory"]} -> chmod({$array["chmoddir"]})\n";}
				chmod_directories($array["directory"],$array["chmoddir"]);
			}
			
			if(!isset($alreadyFiles["{$array["directory"]}/*.{$array["ext"]}"])){
				if(strpos($array["ext"],",")>0){
						$newExts=@explode(",",$array["ext"]);
						while (list ($i, $ext2) = each ($newExts)){
							if($GLOBALS["VERBOSE"]){echo "$servername::{$array["directory"]}/*.$ext2 -> chmod({$array["chmodfile"]})\n";}
							chmod_files($array["directory"],$ext2,$array["chmodfile"]);
							$alreadyFiles["{$array["directory"]}/*.$ext2"]=true;
						}
				}else{
					if($GLOBALS["VERBOSE"]){echo "$servername::{$array["directory"]}/*.{$array["ext"]} -> chmod({$array["chmodfile"]})\n";}
					chmod_files($array["directory"],$array["ext"],$array["chmodfile"]);
					$alreadyFiles["{$array["directory"]}/*.{$array["ext"]}"]=true;
				}
			}
			$alreadydir[$array["directory"]]=true;
			
		
		}
		
	}
}

function VirtualHostsIPAddresses($StandardPort,$listenAddr,$SSLPORT){
	$q=new mysql();
	$already=array();
	$sock=new sockets();
	$NameVirtualHostSSL=array();
	$NameVirtualHost=array();
	$unix=new unix();
	$ss=array();
	
	if($unix->IsSquidReverse()){
		$SSLPORT=447;
		$StandardPort=82;
		$listenAddr="127.0.0.1";
	}
	if($unix->isNGnx()){
		$SSLPORT=447;
		$StandardPort=82;
		$listenAddr="127.0.0.1";		
	}
	
	$hashListenAddr=unserialize(base64_decode($sock->GET_INFO("FreeWebsApacheListenTable")));
	
	if(is_array($hashListenAddr)){
		while (list ($ipport, $array) = each ($hashListenAddr)){
			if($GLOBALS["VERBOSE"]){echo "DEBUG:: FreeWebsApacheListenTable: $ipport Line:".__LINE__."\n";}
			if($ligne["SSL"]==1){
				$NameVirtualHostSSL[]="\tListen $ipport";
				continue;
			}
			$Listen[]="Listen $ipport";
		}
	}
	
	if(count($NameVirtualHost)==0){
		if($GLOBALS["VERBOSE"]){echo "DEBUG:: listenAddr: $listenAddr Line:".__LINE__."\n";}
		if($listenAddr<>"*"){
			$Listen[]="Listen $listenAddr:$StandardPort";
		}else{$Listen[]="Listen $StandardPort";}
	}
	
	if(count($NameVirtualHostSSL)==0){
		if($listenAddr<>"*"){
			if($GLOBALS["VERBOSE"]){echo "DEBUG:: listenAddr: $listenAddr:$SSLPORT Line:".__LINE__."\n";}
			$NameVirtualHostSSL[]="\tListen $listenAddr:$SSLPORT";
		}else{$NameVirtualHostSSL[]="\tListen $SSLPORT";}
	}	
	

	$sql="SELECT servername,ServerIP,ServerPort,useSSL FROM freeweb";
	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){
		if($GLOBALS["VERBOSE"]){echo $q->mysql_error."\n";}
		return array($Listen,$NameVirtualHost,$NameVirtualHostSSL);
	}
	
	
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		//if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $ligne["servername"])){
			//$ligne["ServerIP"]=$ligne["servername"];
		//}
		
		if($ligne["ServerPort"]>0){
			if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $ligne["ServerIP"])){
				if($GLOBALS["VERBOSE"]){echo "DEBUG:: listenAddr: {$ligne["ServerIP"]}:{$ligne["ServerPort"]} Line:".__LINE__."\n";}
				$NameVirtualHost[]="NameVirtualHost {$ligne["ServerIP"]}:{$ligne["ServerPort"]}";
				continue;	
				}
				
			if($GLOBALS["VERBOSE"]){echo "DEBUG:: listenAddr: $listenAddr:{$ligne["ServerPort"]} Line:".__LINE__."\n";}
			$NameVirtualHost[]="NameVirtualHost $listenAddr:{$ligne["ServerPort"]}";
			continue;				
				
			}
				
			if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $ligne["ServerIP"])){
					$NameVirtualHost[]="NameVirtualHost {$ligne["ServerIP"]}:$StandardPort";
					if($ligne["useSSL"]==1){
						if($GLOBALS["VERBOSE"]){echo "DEBUG:: listenAddr: {$ligne["ServerIP"]}:$SSLPORT Line:".__LINE__."\n";}
						$NameVirtualHostSSL[]="\tNameVirtualHost {$ligne["ServerIP"]}:$SSLPORT";
					}
					continue;
				}
				
			$NameVirtualHost[]="NameVirtualHost $listenAddr:$StandardPort";	
		}
		
		while (list ($index, $line) = each ($Listen)){$ff[$line]=$line;}	
		$Listen=array();
		while (list ($index, $line) = each ($ff)){$Listen[]=$index;}
		
		while (list ($index, $line) = each ($NameVirtualHost)){$ss[$line]=$line;}
		$NameVirtualHost=array();
		while (list ($index, $line) = each ($ss)){$NameVirtualHost[]=$index;}
		
		$ssl=array();
		while (list ($index, $line) = each ($NameVirtualHostSSL)){$ssl[$line]=$line;}
		$NameVirtualHostSSL=array();
		while (list ($index, $line) = each ($ssl)){$NameVirtualHostSSL[]=$index;}
	
		return array($Listen,$NameVirtualHost,$NameVirtualHostSSL);
}



function chmod_directories($path, $filemode=755) {
    
	if(!is_dir($path)){return;}
	if($GLOBALS["VERBOSE"]){echo "DIR: $path -> chmod:$filemode\n";}
	chmod($path,$filemode);
    $dh = opendir($path);
    while (($file = readdir($dh)) !== false) {
        if($file != '.' && $file != '..') {
        	$fullpath = $path.'/'.$file;
        	if(!is_dir($fullpath)){continue;}
        	if(is_link($fullpath)){continue;}
        	if(is_file($fullpath)){continue;}
        	if($GLOBALS["VERBOSE"]){echo "DIR: $fullpath -> chmod:$filemode\n";}
        	shell_exec("/bin/chmod $filemode $fullpath");
        	chmod_directories($fullpath,$filemode);
          }
    }

    closedir($dh);
	return TRUE;
	
    
}
function chmod_files($path, $ext="*",$filemode=755) {
    if (!is_dir($path)){
    	if(is_link($path)){return;}
    	if(is_file($path)){
    		$info=pathinfo($path);
    		if($ext<>"*"){
            	if(!isset($info["extension"])){return;}
            	if(strtolower($ext)==$info["extension"]){
            		if($GLOBALS["VERBOSE"]){echo "FILE:".__LINE__.":$ext $path -> chmod:$filemode\n";}
            		shell_exec("/bin/chmod $filemode $path");
            		return;
            	}
            	
            }else{
            	if($GLOBALS["VERBOSE"]){echo "FILE:".__LINE__.":$ext $path -> chmod:$filemode\n";}
            	shell_exec("/bin/chmod $filemode $path");
            	return;
            }
    	}
    return;}

    $dh = opendir($path);
    while (($file = readdir($dh)) !== false) {
        if($file != '.' && $file != '..') {
        	
            $fullpath = $path.'/'.$file;
        	if(is_dir($fullpath)){
        		if($GLOBALS["VERBOSE"]){echo "chmod_files($fullpath,$ext,$filemode);\n";}
        		chmod_files($fullpath,$ext,$filemode);
        		continue;
        		
        	}
        	
            
            if($ext=="*"){
            	if(!is_file($fullpath)){continue;}
            	if($GLOBALS["VERBOSE"]){echo "FILE:".__LINE__.":$ext $fullpath -> chmod:$filemode (*)\n";}
            	shell_exec("/bin/chmod $filemode $fullpath");
            	
            	continue;
            }
            
            
            
            if(is_link($fullpath)){continue;}
           	if(is_file($fullpath)){
           		if(!preg_match("#.+?\.(.+?)$#",basename($fullpath),$re)){continue;}
           		$extr=$re[1];
           		if($ext<>$extr){continue;}
           		if($GLOBALS["VERBOSE"]){echo "FILE:".__LINE__.":$ext $fullpath -> chmod:$filemode ($extr)\n";}
           		shell_exec("/bin/chmod $filemode $fullpath");
				continue;
           	}     
           	
            
           	
        }
    }

    closedir($dh);

}

function CheckFailedStart(){
	$unix=new unix();
	$sock=new sockets();
	$apache2ctl=$unix->find_program("apache2ctl");
	if(!is_file($apache2ctl)){$apache2ctl=$unix->find_program("apachectl");}
	if(!is_file($apache2ctl)){echo "Starting......: ".date("H:i:s")." [INIT]: Apache apache2ctl no such file\n";}

	exec("$apache2ctl -k start 2>&1",$results);
	while (list ($index, $line) = each ($results)){
		
		
		if(apachectl_line_skip($ligne)){continue;}
		
		if(preg_match("#Cannot load .+?mod_qos\.so#", $line)){
			echo "Starting......: ".date("H:i:s")." [INIT]: Apache error on qos module, disable it..\n";
			echo "Starting......: ".date("H:i:s")." [INIT]: Apache error \"$line\"\n";
			$sock->SET_INFO("FreeWebsDisableMOdQOS",1);
			CheckHttpdConf();
			$unix->send_email_events("FreeWebs: QOS is disabled, cannot be loaded on your server","Apache claim $line,using this module is disabled","system");
			shell_exec("/etc/init.d/artica-postfix start apachesrc --no-repair");
			return;
		}
		
		if(preg_match("#Could not open configuration file (.+?)sites-enabled#",$line,$re)){
			echo "Starting......: ".date("H:i:s")." [INIT]: Apache error {$re[1]}/sites-enabled\n";
			echo "Starting......: ".date("H:i:s")." [INIT]: Apache error \"$line\"\n";
			$apacheusername=$unix->APACHE_SRC_ACCOUNT();
			echo "Starting......: ".date("H:i:s")." [INIT]: Apache creating directory {$re[1]}/sites-enabled\n";
			@mkdir("{$re[1]}/sites-enabled");
			
			echo "Starting......: ".date("H:i:s")." [INIT]: Apache checking permissions on {$re[1]}/sites-enabled with user $apacheusername\n";
			@chown("{$re[1]}/sites-enabled",$apacheusername);
			@chmod("{$re[1]}/sites-enabled",0755);
			shell_exec("/etc/init.d/artica-postfix start apachesrc --no-repair");
			return;
		}
		
	 echo "Starting......: ".date("H:i:s")." [INIT]: Apache $line\n";	
	}
	
}

function install_groupware($servername,$rebuild=false){
	
	$free=new freeweb($servername);
	if($free->groupware==null){
		 writelogs("Starting......: ".date("H:i:s")." [INIT]: Apache \"$servername\" no groupware set",__FUNCTION__,__FILE__,__LINE__);
		 return;
	}
	
	writelogs("Starting......: ".date("H:i:s")." [INIT]: Apache \"$servername\" -> \"$free->groupware\"",__FUNCTION__,__FILE__,__LINE__);
	
	switch ($free->groupware) {
		case "ARTICA_USR":
			install_groupware_ARTICA_USR($servername);
			return;
			break;
		
		case "ARTICA_ADM":
			install_groupware_ARTICA_ADM($servername);
			return;
			break;
			
		case "EYEOS":
			install_EYEOS($servername);
			return;
			break;
		
		case "GROUPOFFICE":
			writelogs("group_office_install($servername,false,$rebuild)",__FUNCTION__,__FILE__,__LINE__);
			if($rebuild){buildHost(null,$servername);};
			group_office_install($servername,false,$rebuild);
			break;
		
		case "JOOMLA17":
			writelogs("install_JOOMLA17($servername)",__FUNCTION__,__FILE__,__LINE__);
			install_JOOMLA17($servername);
			return;
			break;

		case "WORDPRESS":
			writelogs("install_wordpress($servername)",__FUNCTION__,__FILE__,__LINE__);
			install_wordpress($servername);
			return;
			break;		
		
		case "ROUNDCUBE":
			writelogs("install_roundcube($servername)",__FUNCTION__,__FILE__,__LINE__);
			install_roundcube($servername);
			return;
			break;	
			
		case "ZARAFA":
			writelogs("install_zarafa($servername)",__FUNCTION__,__FILE__,__LINE__);
			install_zarafa($servername);
			return;
			break;	
			
		case "WEBAPP":
			writelogs("install_zarafawebapp($servername)",__FUNCTION__,__FILE__,__LINE__);
			install_zarafawebapp($servername);
			return;
			break;				
			
		case "CONCRETE5":
			writelogs("install_concrete5($servername)",__FUNCTION__,__FILE__,__LINE__);
			install_concrete5($servername);
			return;
			break;				
			
		case "DOTCLEAR":
			writelogs("install_dotclear($servername)",__FUNCTION__,__FILE__,__LINE__);
			install_dotclear($servername);
			return;
			break;	
			
		case "SUGAR":
			writelogs("install_sugarcrm($servername)",__FUNCTION__,__FILE__,__LINE__);
			install_sugarcrm($servername);
			return;
			break;	
			
		case "POWERADMIN":
			writelogs("install_poweradmin($servername)",__FUNCTION__,__FILE__,__LINE__);
			install_poweradmin($servername);
			return;
			break;	

		case "XAPIAN":
			writelogs("install_xapian($servername)",__FUNCTION__,__FILE__,__LINE__);
			install_xapian($servername);
			return;
			break;

		case "PIWIGO":
			writelogs("install_piwigo($servername)",__FUNCTION__,__FILE__,__LINE__);
			install_piwigo($servername);
			return;
			break;	
			
		case "OWNCLOUD":
			writelogs("install_owncloud($servername)",__FUNCTION__,__FILE__,__LINE__);
			install_owncloud($servername);
			return;
			break;	
			
		case "APP_FILEZ_WEB":
			writelogs("install_filezweb($servername)",__FUNCTION__,__FILE__,__LINE__);
			install_filezweb($servername);
			return;
			break;			
			
			
		default:
			;
		break;
	}
	
	
	
}

function install_groupware_ARTICA_USR($hostname){
	$sql="SELECT * FROM freeweb WHERE servername='$hostname'";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));	
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache \"$hostname\" Rebuilding host configuration file\n";	
	buildHost($ligne["uid"],$hostname);
	reload_apache();
	shell_exec("/bin/ln -s /usr/share/artica-postfix/ressources/settings.inc /usr/share/artica-postfix/user-backup/ressources/settings.inc >/dev/null 2>&1");
}

function install_groupware_ARTICA_ADM($hostname){
	$sql="SELECT * FROM freeweb WHERE servername='$hostname'";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));	
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache \"$hostname\" Rebuilding host configuration file\n";
	buildHost($ligne["uid"],$hostname);
	reload_apache();
}

function install_EYEOS($hostname){
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache \"$hostname\" Checking eyeOS installation....\n";
	
	$freeweb=new freeweb($hostname);
	$freeweb->CheckWorkingDirectory();
	
	
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache \"$hostname\" Checking eyeOS installation....\n";
	if(!is_file(dirname(__FILE__)."/ressources/class.eyeos.inc")){echo "Fatal ".dirname(__FILE__)."/ressources/class.eyeos.inc no such file\n";}
	include_once(dirname(__FILE__)."/ressources/class.eyeos.inc");
	$eye=new eyeos($hostname);

	if($eye->ValidateInstallation25()){
		echo "Starting......: ".date("H:i:s")." [INIT]: Apache \"$hostname\" Installing EyeOS (already installed)\n";
		$eye->Build_SettingsPHP();
		return;
	}
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache \"$hostname\" Installing EyeOS in $freeweb->WORKING_DIRECTORY\n";
	$unix=new unix();
	$cp=$unix->find_program("cp");
	shell_exec("$cp -rf /usr/local/share/artica/eyeos_src/* $freeweb->WORKING_DIRECTORY/");
	if($eye->ValidateInstallation25($freeweb->WORKING_DIRECTORY)){
		echo "Starting......: ".date("H:i:s")." [INIT]: Apache \"$hostname\" Installing EyeOS (FAILED)\n";
	}	
	
}

function resolv_servers(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".__FUNCTION__.".".__FILE__.".pid";
	$filetime="/etc/artica-postfix/pids/".__FUNCTION__.".".__FILE__.".time";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid)){return;}
	@file_put_contents($pidfile, getmypid());
	if(!$GLOBALS["FORCE"]){
		$time=$unix->file_time_min($filetime);
		if($time<30){return;}
	}
	
	@unlink($filetime);
	@file_put_contents($filetime, time());
	$nohup=$unix->find_program("nohup");
	$drupal_cron=trim("$nohup ". $unix->LOCATE_PHP5_BIN()." " .__FILE__." --drupal-cron >/dev/null 2>&1 &");
	shell_exec($drupal_cron);
	
	$sql="SELECT servername,resolved_ipaddr FROM freeweb ORDER BY servername";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){if($GLOBALS["VERBOSE"]){echo "ERROR IN QUERY \"$q->mysql_error\"\n";}}
	if(preg_match("#Unknown column#", $q->mysql_error)){$q->BuildTables();$results=$q->QUERY_SQL($sql,'artica_backup');}
	
	$count=mysql_num_rows($results);
	
	if($count==0){return;}
	
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		if($ligne["servername"]=='_default_'){$sql="UPDATE freeweb SET `resolved_ipaddr`='{$ligne["servername"]}' WHERE servername='{$ligne["servername"]}'";$q->QUERY_SQL($sql,"artica_backup");continue;}
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", trim($ligne["servername"]))){$sql="UPDATE freeweb SET `resolved_ipaddr`='{$ligne["servername"]}' WHERE servername='{$ligne["servername"]}'";$q->QUERY_SQL($sql,"artica_backup");continue;}
		if($GLOBALS["VERBOSE"]){echo "check {$ligne["servername"]}\n";}
		
		$ipaddr=gethostbyname($ligne["servername"]);
		if($GLOBALS["VERBOSE"]){echo "$ipaddr\n";}
		if($ipaddr==null){
			//$unix->send_email_events("FreeWeb: http(s)://{$ligne["servername"]} unable to resolve","Artica tried to resolve the {$ligne["servername"]}, no ip address is returned, so it's means that this website will be not available", "system");
			continue;
		}
		
		if($ipaddr==$ligne["servername"]){
			//$unix->send_email_events("FreeWeb: http(s)://{$ligne["servername"]} unable to resolve","Artica tried to resolve the {$ligne["servername"]}, no ip address is returned, so it's means that this website will be not available", "system");
			$sql="UPDATE freeweb SET `resolved_ipaddr`='' WHERE servername='{$ligne["servername"]}'";
			$q->QUERY_SQL($sql,"artica_backup");			
			continue;
		}		
		
		if($ipaddr<>$ligne["resolved_ipaddr"]){
			$sql="UPDATE freeweb SET `resolved_ipaddr`='$ipaddr' WHERE servername='{$ligne["servername"]}'";
			$q->QUERY_SQL($sql,"artica_backup");
			//$unix->send_email_events("FreeWeb: http(s)://{$ligne["servername"]} resolved to $ipaddr","Artica tried to resolve the {$ligne["servername"]}, old ip was [{$ligne["resolved_ipaddr"]}] new ip is $ipaddr", "system");
		}
		
	}	
	
}

function createdupal($servername){
	if($servername==null){return;}
	$f=new drupal_vhosts($servername);
	$f->install();
	
}

function drupal_infos($servername){
	if($servername==null){return;}
	$f=new drupal_vhosts($servername);
	$f->populate_infos();	
}
function drupal_add_user($uid,$servername){
	if($servername==null){return;}
	if($uid==null){return;}
	$f=new drupal_vhosts($servername);
	$f->add_user($uid);	
}
function drupal_deluser($uid,$servername){
	if($servername==null){return;}
	if($uid==null){return;}	
	$f=new drupal_vhosts($servername);
	$f->del_user($uid);		
}
function drupal_enuser($uid,$enable,$servername){
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [INIT]: Apache \"$servername\" drupal_enuser() $uid enable->[$enable]\n";}
	if($servername==null){return;}
	if($uid==null){return;}	
	$f=new drupal_vhosts($servername);
	$f->active_user($uid,$enable);	
}
function drupal_privuser($uid,$priv,$servername){
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [INIT]: Apache \"$servername\" drupal_privuser() $uid enable->[$priv]\n";}
	if($servername==null){return;}
	if($uid==null){return;}	
	$f=new drupal_vhosts($servername);
	$f->priv_user($uid,$priv);	
}
function drupal_dump_modules($servername){
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [INIT]: Apache \"$servername\" drupal_dump_modules()\n";}
	if($servername==null){return;}
	$f=new drupal_vhosts($servername);
	$f->dump_modules();
	
}
function drupal_cron(){
	$users=new usersMenus();
	if(!$users->DRUPAL7_INSTALLED){die();}
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	$unix=new unix();
	$drush7=$unix->find_program("drush7");
	if(!is_file($drush7)){die();}
	if($unix->process_exists($pid,basename(__FILE__))){die();}
	if($unix->file_time_min($pidtime)<60){die();}
	@file_put_contents($pidfile, getmypid());
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	$sql="SELECT servername FROM freeweb WHERE groupware='DRUPAL'";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){if($GLOBALS["VERBOSE"]){echo $q->mysql_error."\n";return;}}
	$count=mysql_num_rows($results);
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache checking drupal cron web sites count:$count\n";
	if($count==0){return;}
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){	
		$dd=new drupal_vhosts($ligne["servername"]);
		$dd->install_modules();	
		shell_exec("$drush7 --root=$dd->www_dir cron >/dev/null 2>&1");
	}
}
function drupal_install_modules($servername){
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [INIT]: Apache \"$servername\" drupal_install_modules()\n";}
	if($servername==null){return;}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".$servername.pid";
	$pid=@file_get_contents($pidfile);
	$unix=new unix();
	$drush7=$unix->find_program("drush7");
	if(!is_file($drush7)){die();}
	if($unix->process_exists($pid,basename(__FILE__))){die();}	
	@file_put_contents($pidfile, getmypid());
	
	$f=new drupal_vhosts($servername);
	$f->install_modules();	
}
function drupal_reinstall($servername){
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [INIT]: Apache \"$servername\" drupal_install_modules()\n";}
	if($servername==null){return;}	
	$unix=new unix();
	$drush7=$unix->find_program("drush7");
	if(!is_file($drush7)){die();}	
	$f=new drupal_vhosts($servername);
	$f->DrushInstall();
}
function drupal_schedules(){
	$q=new mysql();
	$sql="SELECT * FROM drupal_queue_orders ORDER BY ID";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$uid=null;$password=null;$value=null;
		if($ligne["value"]<>null){$data=unserialize(base64_decode($ligne["value"]));}
		$order=$ligne["ORDER"];
		writelogs("order:{$ligne["ORDER"]} ID:{$ligne["ID"]}",__FUNCTION__,__FILE__,__LINE__);
		$servername=$ligne["servername"];
		if(isset($data["USER"])){$uid=$data["USER"];}
		if(isset($data["PASSWORD"])){$password=$data["USER"];}
		if(isset($data["value"])){$value=$data["value"];}
		$ID=$ligne["ID"];
		writelogs("order:$order servername:$servername (uid=$uid)",__FUNCTION__,__FILE__,__LINE__);
		
		switch ($order){
			
			case "REFRESH_INFOS":
				$sql="DELETE FROM drupal_queue_orders WHERE ID=$ID";
				$q->QUERY_SQL($sql,"artica_backup");					
				if($servername<>null){
					$f=new drupal_vhosts($servername);
					$f->populate_infos();
				}
			break;
			
			case "REFRESH_MODULES":
				$sql="DELETE FROM drupal_queue_orders WHERE ID=$ID";
				$q->QUERY_SQL($sql,"artica_backup");					
				if($servername<>null){
					$f=new drupal_vhosts($servername);
					$f->dump_modules();
					$f->install_modules();	
				}
			break;			
			
			
			
			case "DELETE_USER":
				$sql="DELETE FROM drupal_queue_orders WHERE ID=$ID";
				$q->QUERY_SQL($sql,"artica_backup");					
				if($servername<>null){
					$f=new drupal_vhosts($servername);
					$f->del_user($uid);	
				}
			break;	

			case "CREATE_USER":
				if($servername<>null){
					$f=new drupal_vhosts($servername);
					$f->add_user($uid,$password);	
				}
			break;	

			case "ENABLE_USER":
				$sql="DELETE FROM drupal_queue_orders WHERE ID=$ID";
				$q->QUERY_SQL($sql,"artica_backup");					
				if($servername<>null){
					$f=new drupal_vhosts($servername);
					$f->active_user($uid,$value);	
				}
			break;			

			case "PRIV_USER":
				$sql="DELETE FROM drupal_queue_orders WHERE ID=$ID";
				$q->QUERY_SQL($sql,"artica_backup");					
				writelogs("PRIV_USER: servername:$servername (uid=$uid, value=$value)",__FUNCTION__,__FILE__,__LINE__);
				if($servername<>null){
					$f=new drupal_vhosts($servername);
					$f->priv_user($uid,$value);	
				}
			break;	

			case "DELETE_FREEWEB":
				$sql="DELETE FROM drupal_queue_orders WHERE ID=$ID";
				$q->QUERY_SQL($sql,"artica_backup");					
				writelogs("DELETE_FREEWEB: servername:$servername (uid=$uid, value=$value)",__FUNCTION__,__FILE__,__LINE__);
				remove_host($servername);
				break;
				
			case "INSTALL_GROUPWARE":
				$sql="DELETE FROM drupal_queue_orders WHERE ID=$ID";
				$q->QUERY_SQL($sql,"artica_backup");					
				writelogs("INSTALL_GROUPWARE: servername:$servername (uid=$uid, value=$value)",__FUNCTION__,__FILE__,__LINE__);
				install_groupware($servername);
				break;
				
			case "REBUILD_GROUPWARE":
				$sql="DELETE FROM drupal_queue_orders WHERE ID=$ID";
				$q->QUERY_SQL($sql,"artica_backup");				
				writelogs("INSTALL_GROUPWARE: servername:\"$servername\" (uid=$uid, value=$value)",__FUNCTION__,__FILE__,__LINE__);
				install_groupware($servername,true);
				break;				
			
		}
		

		
	}
		
	
}
function group_office_install($servername,$nobuildHost=false,$rebuild=false){
	$sources="/usr/local/share/artica/group-office";
	$unix=new unix();
	$cp=$unix->find_program("cp");
	$freeweb=new freeweb($servername);
	if(!is_dir($sources)){writelogs("[$servername] $sources no such directory",__FUNCTION__,__FILE__,__LINE__);return;}
	if(!is_dir($freeweb->WORKING_DIRECTORY)){writelogs("[$servername] $freeweb->WORKING_DIRECTORY no such directory",__FUNCTION__,__FILE__,__LINE__);return;}
	
	if(!is_file("$freeweb->WORKING_DIRECTORY/functions.inc.php")){$mustrebuild=true;}
	if(!$mustrebuild){$mustrebuild=$rebuild;}
	
	if($mustrebuild){
		writelogs("[$servername] copy sources...",__FUNCTION__,__FILE__,__LINE__);
		shell_exec("/bin/cp -rf $sources/* $freeweb->WORKING_DIRECTORY/");
		@file_put_contents("$freeweb->WORKING_DIRECTORY/config.php", "");
	}
	shell_exec("/bin/chmod 666 $freeweb->WORKING_DIRECTORY/config.php");
	
	$apacheusername=$unix->APACHE_SRC_ACCOUNT();
	$apachegroup=$unix->APACHE_SRC_GROUP();
	$freeweb->chown($freeweb->WORKING_DIRECTORY);
	if(!is_dir("/home/$servername")){@mkdir("/home/$servername");}
	include_once(dirname(__FILE__)."/ressources/class.group-office.php");
	$gpoffice=new group_office($servername);
	$gpoffice->www_dir=$freeweb->WORKING_DIRECTORY;
	$gpoffice->rebuildb=$rebuild;
	writelogs("[$servername] gpoffice->writeconfigfile() $freeweb->WORKING_DIRECTORY",__FUNCTION__,__FILE__,__LINE__);
	$gpoffice->writeconfigfile();
	
	$freeweb->chown("/home/$servername");

	
	
	
	//a la find chmod 644 /var/www/office.touzeau.com/group-office/config.php 
	
	if(!$nobuildHost){buildHost(null,$servername);}
	
	
}
function install_JOOMLA17($servername){
	include_once(dirname(__FILE__)."/ressources/class.joomla17.inc");
	$joom=new joomla17($servername);
	$joom->installsite();
	
}
function install_wordpress($servername){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php /usr/share/artica-postfix/exec.wordpress.php \"$servername\"");
}
function install_concrete5($servername){
	include_once(dirname(__FILE__)."/ressources/class.concrete5.inc");
	$word=new concrete5($servername);
	$word->CheckInstall();	
}

function install_dotclear($servername){
	include_once(dirname(__FILE__)."/ressources/class.dotclear.inc");
	$dot=new dotclear($servername);
	$dot->CheckInstall();	
}

function install_roundcube($servername){
	include_once(dirname(__FILE__)."/ressources/class.roundcube.freewebs.inc");
	$rond=new roundcube_freewebs($servername);
	$rond->build();
}

function install_zarafawebapp($servername){
	$free=new freeweb($servername);
	if($free->groupware<>"WEBAPP"){
		writelogs("[$servername] $free->groupware <> WEBAPP, aborting",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	$free->InstallZarafaWebAPP($servername);
	
}

function install_owncloud($servername){
	include_once(dirname(__FILE__)."/ressources/class.owncloud.inc");
	$cld=new owncloud_www($servername);
	$cld->verifinstall();
}

function install_filezweb($servername){
	include_once(dirname(__FILE__)."/ressources/class.filezweb.inc");
	$cld=new filez_www($servername);
	$cld->verifinstall();	
}

function install_sugarcrm($servername){
	$free=new freeweb($servername);
	if($free->groupware<>"SUGAR"){
		writelogs("[$servername] $free->groupware <> SUGAR, aborting",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	$sugar=new SugarCRM_install($servername);
	$sugar->CheckInstall();
	
}

function install_poweradmin($servername){
	$unix=new unix();
	$chown=$unix->find_program("chown");
	$apacheusername=$unix->APACHE_SRC_ACCOUNT();
	$apachegroup=$unix->APACHE_SRC_GROUP();	
	writelogs("[$servername] Chown /usr/share/poweradmin $apacheusername:$apachegroup",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec("$chown -R $apacheusername:$apachegroup /usr/share/poweradmin");
	buildHost(null,$servername);
}

function install_xapian($servername){buildHost(null,$servername);}

function install_zarafa($servername){
	$free=new freeweb($servername);
	if($free->groupware<>"ZARAFA"){
		writelogs("[$servername] $free->groupware <> ZARAFA, aborting",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	$free->InstallZarafa($servername);
}

function install_piwigo($servername){
	include_once(dirname(__FILE__)."/class.piwigo.inc");
	if($this->AS_ROOT){echo "Starting......: ".date("H:i:s")." [INIT]: Apache \"$servername\" Testing Piwigo installation\n";}
	$sugar=new piwigo($servername);
	$sugar->verifinstall();
}
function install_PIWIK($servername){
	$sources="/usr/share/piwik";
	$unix=new unix();
	$cp=$unix->find_program("cp");
	$freeweb=new freeweb($servername);	
	if(!is_dir($sources)){writelogs("[$servername] $sources no such directory",__FUNCTION__,__FILE__,__LINE__);return;}
	if(!is_dir($freeweb->WORKING_DIRECTORY)){writelogs("[$servername] $freeweb->WORKING_DIRECTORY no such directory",__FUNCTION__,__FILE__,__LINE__);return;}
	include_once(dirname(__FILE__)."/ressources/class.piwik.inc");
	$piwik=new piwik();
	if($piwik->checkWebsite($freeweb->WORKING_DIRECTORY)){return;}
	writelogs("[$servername] copy sources...",__FUNCTION__,__FILE__,__LINE__);
	shell_exec("$cp -rf $sources/* $freeweb->WORKING_DIRECTORY/");
	@unlink("$freeweb->WORKING_DIRECTORY/config/config.ini.php");
	@mkdir('/usr/share/piwik/tmp/assets',0777,true);
    @mkdir('/usr/share/piwik/tmp/templates_c',0777,true);
    @mkdir('/usr/share/piwik/tmp/cache',0777,true);
    @mkdir('/usr/share/piwik/tmp/assets',0777,true);
    shell_exec('/bin/chmod 0777 /usr/share/piwik/tmp');
    shell_exec('/bin/chmod 0777 /usr/share/piwik/tmp/templates_c/');
    shell_exec('/bin/chmod 0777 /usr/share/piwik/tmp/cache/');
    shell_exec('/bin/chmod 0777 /usr/share/piwik/tmp/assets/');
    shell_exec('/bin/chmod a+w /usr/share/piwik/config'); 	
	$apacheusername=$unix->APACHE_SRC_ACCOUNT();
	$apachegroup=$unix->APACHE_SRC_GROUP();	
	$freeweb->chown($freeweb->WORKING_DIRECTORY);
	
	
}
function mod_status_htaccess($filename,$pattern){
	$exp=explode("\n", @file_get_contents("$filename"));
	while (list ($num, $ligne) = each ($exp) ){if(preg_match("#$pattern#",$ligne)){return;}}

	reset($exp);
	while (list ($num, $ligne) = each ($exp) ){	
		if(preg_match("#^RewriteRule#",$ligne)){
			if($GLOBALS["VERBOSE"]){echo "RewriteRule -> {$exp[$num]}\n";}
			$exp[$num]="RewriteCond %{REQUEST_URI} !$pattern\n".$exp[$num];
			@file_put_contents($filename, @implode("\n", $exp));
			return;
		}
	}	
	
}
function mod_status_all(){
	$unix=new unix();
	if(!$GLOBALS["VERBOSE"]){
		
		$pidfile="/etc/artica-postfix/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pidtime="/etc/artica-postfix/".basename(__FILE__).".".__FUNCTION__.".time";
		if($unix->file_time_min($pidtime)<15){die();}
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){return;}
		@unlink($pidtime);
		@file_put_contents($pidtime, time());
		@file_put_contents($pidfile, getmypid());
	}
	
	$table_name="apache_stats_".date('Ym');
	$q=new mysql();
	
	
	$sql="CREATE TABLE  IF NOT EXISTS `artica_events`.`$table_name` (
	`zDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
	`servername` VARCHAR( 255 ) NOT NULL ,
	`UPTIME` VARCHAR( 255 ) NOT NULL ,
	`total_traffic` INT UNSIGNED ,
	`total_memory` INT UNSIGNED ,
	`requests_second` DOUBLE( 100, 2 ) NOT NULL ,
	`traffic_second` INT UNSIGNED ,
	`traffic_request` INT UNSIGNED ,
	 INDEX ( `zDate` , `total_traffic` , `total_memory` , `requests_second` , `traffic_second` , `traffic_request`),
	 KEY `servername` (`servername`))
	";
	$q->QUERY_SQL($sql,"artica_events");
	
	
	$ssq=mod_status();
	if($ssq==null){return;}
	if($GLOBALS["VERBOSE"]){echo "TABLE = $table_name\n";}
	$sql="INSERT IGNORE INTO `$table_name` (`servername`,`total_traffic`,`total_memory`,`requests_second`,`traffic_second`,`traffic_request`,`UPTIME`) VALUES $ssq";
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){echo $q->mysql_error;}
}

function UnitToBytes($int,$unit){
	$unit=strtoupper($unit);
	if($unit=="KB"){$int=$int*1024;}
	if($unit=="MB"){$int=$int*1024000;}
	if($unit=="GB"){$int=$int*1024000000;}
	if($unit=="TB"){$int=$int*10240000000000;}	
	return $int;
	
}


function mod_status($servername=null){
	$unix=new unix();
	if($servername=="_default_"){return;}
	$unix->hostname_g();
	$hostname=$unix->hostname_g();
	$hosnenc=md5($unix->hostname_g());
	$conf[]="# Status from ".$unix->hostname_g();
	$conf[]="<Location >";
	$curl=$unix->find_program("curl");
	$TEMP_FILE=$unix->FILE_TEMP();
	
	$cmdline="$curl --interface 127.0.0.1 http://localhost/$hosnenc/$hosnenc-status >$TEMP_FILE";
	if($GLOBALS["VERBOSE"]){echo $cmdline."\n";}
	shell_exec($cmdline);
	
	$q=new mysql();
	$pid=array();
	
	
	$access=null;
	$total_traffic=null;
	$total_traffic_unit=null;
	$traffic_sec=0;
	$traffic_request=0;
	$request_s=0;
	$UPTIME=null;
	$total_mem=0;
	
	$datas=explode("\n",@file_get_contents($TEMP_FILE));
	@unlink($TEMP_FILE);
	while (list ($num, $ligne) = each ($datas) ){
		
		
		
		if($GLOBALS["VERBOSE"]){echo "Parsing line...`$ligne`\n";}

		
		
		if(preg_match("#<dt>Server uptime:\s+(.+?)</dt>#",$ligne,$re)){
			$UPTIME=trim($re[1]);
			if($GLOBALS["VERBOSE"]){echo "*************\nUPTIME = $UPTIME\n";}
			continue;
		}

		
		
		if(preg_match("#Total accesses:\s+([0-9]+)\s+-\s+Total Traffic:\s+([0-9\.]+)\s+([a-zA-Z]+)#i",$ligne,$re)){
			$access=$re[1];
			$total_traffic=$re[2];
			$total_traffic=UnitToBytes($total_traffic,strtoupper($re[3]));
			if($GLOBALS["VERBOSE"]){echo "*************\n$access - $total_traffic\n";}
			
			
			continue;		
			
			
		}
		
		if(preg_match("#([0-9\.]+)\s+requests\/sec\s+-\s+([0-9\.]+)\s+(.+)\/second\s+-\s+([0-9\.]+)\s+(.+?)\/request#", $ligne,$re)){
			$request_s=$re[1];
			if(substr($request_s,0,1)=="."){$request_s="0$request_s";}
			$traffic_sec=UnitToBytes($re[2],$re[3]);
			$traffic_request=UnitToBytes($re[4],$re[5]);
				
			continue;
		}
		
		if(preg_match("#<td><b>[0-9]+-[0-9]+</b></td><td>([0-9]+)</td><td>#", $ligne,$re)){
			$pid[$re[1]]=$re[1];
		}
		
		
		
	
		
	}
	
	if(count($pid)>0){
		while (list ($num, $ligne) = each ($pid) ){
		
		$total=$unix->PROCESS_MEMORY($num,true);

		if($GLOBALS["VERBOSE"]){echo "Current PID: $num  $total KB\n";}
		
		$total_mem=$total_mem+$total;
		}	
	}
	
	if($GLOBALS["VERBOSE"]){
			echo "\n\n ***************************************************************************************************************";
			echo "Access: $access total-traffic:$total_traffic bytes UPTIME=$UPTIME Total memory used: $total_mem Bytes\n";
			echo "Access: requests/seconds: $request_s traffic/sec:$traffic_sec trafic per request:$traffic_request bytes:\n";
			echo "\n\n ***************************************************************************************************************";

	}

	if(!is_numeric($total_traffic)){
		if($GLOBALS["VERBOSE"]){echo "No traffic return null\n";}
		return;}
		
	$UPTIME=str_replace("</td>", "", $UPTIME);
	$UPTIME=str_replace("</dt>", "", $UPTIME);
	
	if($GLOBALS["VERBOSE"]){echo "MEMORY: ".FormatBytes($total_mem)."\n";}
	
	$HASH["total_traffic"]=$total_traffic;
	$HASH["total_mem"]=$total_mem*1024;
	$HASH["requests_second"]=$request_s;
	$HASH["traffic_second"]=$traffic_sec;
	$HASH["traffic_request"]=$traffic_request;
	$HASH["UPTIME"]=$UPTIME;
	
	$table_name="apache_stats_".date('Ym');
	$q=new mysql();
	$sql="SELECT AVG(total_traffic) as total_traffic,
			AVG(total_memory) as total_memory,
			AVG(requests_second) as requests_second,
			AVG(traffic_second) as traffic_second,
			AVG(traffic_request) as traffic_request FROM `$table_name`";
	
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	
	$HASH["AVG"]["total_traffic"]=$ligne["total_traffic"];
	$HASH["AVG"]["total_memory"]=$ligne["total_memory"];
	$HASH["AVG"]["requests_second"]=$ligne["requests_second"];
	$HASH["AVG"]["traffic_second"]=$ligne["traffic_second"];
	$HASH["AVG"]["traffic_request"]=$ligne["traffic_request"];
	
	
	$sql="SELECT MAX(total_traffic) as total_traffic FROM `$table_name`";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	$HASH["MAX"]["total_traffic"]=$ligne["total_traffic"];
	
	$sql="SELECT MAX(total_memory) as total_memory FROM `$table_name`";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	$HASH["MAX"]["total_memory"]=$ligne["total_memory"];	
	
	$sql="SELECT MAX(requests_second) as requests_second FROM `$table_name`";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	$HASH["MAX"]["requests_second"]=$ligne["requests_second"];	
	
	$sql="SELECT MAX(traffic_second) as traffic_second FROM `$table_name`";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	$HASH["MAX"]["traffic_second"]=$ligne["traffic_second"];	
	
	$sql="SELECT MAX(traffic_request) as traffic_request FROM `$table_name`";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	$HASH["MAX"]["traffic_request"]=$ligne["traffic_request"];
		
	if($GLOBALS["VERBOSE"]){print_r($HASH);}
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/APACHE_HASH", serialize($HASH));
	@chmod("/usr/share/artica-postfix/ressources/logs/web/APACHE_HASH",0755);
	return "('$hostname','$total_traffic','$total_mem','$request_s','$traffic_sec','$traffic_request','$UPTIME')";


}
function roundcube_plugins($servername){
	$free=new freeweb($servername);
	$unix=new unix();
	$dirs=$unix->dirdir("$free->WORKING_DIRECTORY/plugins");
	while (list ($num, $ligne) = each ($dirs) ){
		echo basename($num)."\n";
	}
}
function build_monit(){
	$settings=new settings_inc();
	$sock=new sockets();
	$monit_file="/etc/monit/conf.d/apache.monitrc";
	$start_file="/etc/init.d/apache2 start --monit";
	$stop_file="/etc/init.d/apache2 stop --monit";
	$processMonitName="APP_APACHE_SRC";
	
	if(!$settings->MONIT_INSTALLED){
		echo "Starting......: ".date("H:i:s")." [INIT]: Apache Monit is not installed\n";
		return;
	}
	
	$unix=new unix();
	$pidfile=$unix->APACHE_PID_PATH();
	$chmod=$unix->find_program("chmod");
	
	
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache PidFile = `$pidfile`\n";
	if($pidfile==null){
		echo "Starting......: ".date("H:i:s")." [INIT]: Apache PidFile unable to locate\n";
		return ;
	}
	

	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("ApacheWatchdogMonitConfig")));
	if(!is_numeric($MonitConfig["watchdog"])){$MonitConfig["watchdog"]=1;}
	if(!is_numeric($MonitConfig["watchdogCPU"])){$MonitConfig["watchdogCPU"]=95;}
	if(!is_numeric($MonitConfig["watchdogMEM"])){$MonitConfig["watchdogMEM"]=1500;}	
	$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}
	if($EnableFreeWeb==0){$MonitConfig["watchdog"]=0;}
	
	if($MonitConfig["watchdog"]==0){
		echo "Starting......: ".date("H:i:s")." [INIT]: Apache Monit is not enabled ($q->watchdog)\n";
		if(is_file($monit_file)){
			@unlink($monit_file);
			@unlink($start_file);
			@unlink($stop_file);
			$reloadmonit=true;}
	}
	
	if($MonitConfig["watchdog"]==1){
		echo "Starting......: ".date("H:i:s")." [INIT]: Apache Monit is enabled check pid `$pidfile`\n";
		$reloadmonit=true;
		$f[]="check process $processMonitName";
   		$f[]="with pidfile $pidfile";
   		$f[]="start program = \"$start_file\"";
   		$f[]="stop program =  \"$stop_file\"";
   		if($MonitConfig["watchdogMEM"]){
  			$f[]="if totalmem > {$MonitConfig["watchdogMEM"]} MB for 5 cycles then alert";
   		}
   		if($MonitConfig["watchdogCPU"]>0){
   			$f[]="if cpu > {$MonitConfig["watchdogCPU"]}% for 5 cycles then alert";
   		}
	   $f[]="if 5 restarts within 5 cycles then timeout";
	   
	   @file_put_contents($monit_file, @implode("\n", $f));
	   $f=array();
	   $f[]="#!/bin/sh";
	   $f[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin";
	   $f[]="/etc/init.d/artica-postfix start apachesrc";
	   $f[]="exit 0\n";
 	   @file_put_contents($start_file, @implode("\n", $f));
 	   shell_exec("$chmod 777 $start_file");
	   $f=array();
	   $f[]="#!/bin/sh";
	   $f[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin";
	   $f[]="/etc/init.d/artica-postfix stop apachesrc";
	   $f[]="exit 0\n";
 	   @file_put_contents($stop_file, @implode("\n", $f));
 	   shell_exec("$chmod 777 $stop_file");	   
	}
	
	if($reloadmonit){
		$unix->THREAD_COMMAND_SET("/usr/share/artica-postfix/bin/artica-install --monit-check");
	}	
}
function watchdog($direction){
	
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".".$direction.".pid";
	$unix=new unix();
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		writelogs("Already executed $pid",__FUNCTION__,__FILE__,__LINE__);
		die();
	}
	@file_put_contents($pidfile, getmypid());
	$mybase=$unix->LOCATE_PHP5_BIN()." ".__FILE__;
	
	if($direction=="start"){
		exec("$mybase --start 2>&1",$results);
		$unix->send_email_events("Warning: watchdog require to start Apache engine", @implode("\n", $results), "watchdog");
	}
	
	if($direction=="stop"){
		exec("$mybase --stop 2>&1",$results);
		$unix->send_email_events("Warning: watchdog require to stop Apache engine", @implode("\n", $results), "watchdog");
	}
	
}
function dumpconf($servername){
	$free=new freeweb($servername);
	echo $free->BackupConfig();
	
}
function restore_container($servername,$path,$instance_id){
	$unix=new unix();
	$tmppath="/var/tmp/".time();
	$t1=time();
	if(!is_numeric($instance_id)){$instance_id=0;}
	if(!is_file($path)){
		writelogs("[$servername] fatal $path no such file...",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$cp=$unix->find_program("cp");
	$mysql=$unix->find_program("mysql");
	@mkdir($tmppath,0755,true);
	writelogs("[$servername] Uncompress $path to $tmppath",__FUNCTION__,__FILE__,__LINE__);
	exec("$tar -xf $path -C $tmppath/ 2>&1",$results);
	while (list ($num_line, $evenement) = each ($results)){if(trim($evenement)<>null){writelogs("[$servername] $evenement",__FUNCTION__,__FILE__,__LINE__);}}$results=array();
	if(!is_file("$tmppath/artica.restore")){
		writelogs("[$servername] fatal $tmppath/artica.restore no such file...",__FUNCTION__,__FILE__,__LINE__);
		shell_exec("$rm -rf $tmppath");
		return;
	}
	
	$CONF=unserialize(base64_decode(@file_get_contents("$tmppath/artica.restore")));
	if(!is_array($CONF)){
		writelogs("[$servername] fatal $tmppath/artica.restore no such array...",__FUNCTION__,__FILE__,__LINE__);
		shell_exec("$rm -rf $tmppath");
		return;		
	}
	
	$CONF["mysql_instance_id"]=$instance_id;
	if($servername=="DEFAULT"){$servername=$CONF["servername"];}else{$CONF["servername"]=$servername;}
	
	while (list ($key, $value) = each ($CONF)){
		
		$fields[]="`$key`";
		$values[]="'".addslashes($value)."'";
		$edit[]="`$key` = '".addslashes($value)."'";
		
	}
	$sqlAdd="INSERT IGNORE INTO freeweb (".@implode(",", $fields).") VALUES (".@implode(",", $values).")";
	$sqledit="UPDATE freeweb SET ".@implode(",", $edit)." WHERE servername='$servername'";
	
	
	writelogs("[$servername] restore settings",__FUNCTION__,__FILE__,__LINE__);
	$sql="SELECT servername from freeweb WHERE servername='$servername'";
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
	if($ligne["servername"]==null){
		$sql=$sqlAdd;
		writelogs("[$servername] Create the new website",__FUNCTION__,__FILE__,__LINE__);
	}else{
		writelogs("[$servername] restore the website settings",__FUNCTION__,__FILE__,__LINE__);
		$sql=$sqledit;
	}
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		writelogs("[$servername] fatal $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		shell_exec("$rm -rf $tmppath");
		return;				
	}
	@unlink("$tmppath/artica.restore");
	
	$free=new freeweb($servername);
	if(is_dir("$tmppath/MySQL")){
		$filesArr=$unix->DirRecursiveFiles("$tmppath/MySQL","*.sql");
		$sql_file=$filesArr[0];
	}
	writelogs("[$servername] database dump = `$sql_file`",__FUNCTION__,__FILE__,__LINE__);
	if(is_file($sql_file)){
		writelogs("[$servername] Restoring database $free->mysql_database instance $free->mysql_instance_id",__FUNCTION__,__FILE__,__LINE__);
		$host=" --host=$q->mysql_server --port=$q->mysql_port";
		
		if($instance_id>0){$q=new mysql_multi($instance_id);$host=" --socket=$q->SocketPath";}
		
		
		$user=$q->mysql_admin;
		if($q->mysql_password<>null){
			$adminpassword=$unix->shellEscapeChars($q->mysql_password);
			$adminpassword=str_replace("'", "", $adminpassword);
			$adminpassword=str_replace('$', '\$', $adminpassword);
			$adminpassword=str_replace("'", '', $adminpassword);
			$adminpassword=" --password=$adminpassword";
			$adminpassword_text=" --password=*****";
		}			
		
		if($q->DATABASE_EXISTS($free->mysql_database)){writelogs("[$servername] removing old database $free->mysql_database...",__FUNCTION__,__FILE__,__LINE__);$q->DELETE_DATABASE($free->mysql_database,true);}
		if(!$q->DATABASE_EXISTS($free->mysql_database)){writelogs("[$servername] Creating database $free->mysql_database...",__FUNCTION__,__FILE__,__LINE__);$q->CREATE_DATABASE($free->mysql_database,true);}

		if(!$q->DATABASE_EXISTS($free->mysql_database)){
			writelogs("[$servername] fatal Creating database $free->mysql_database failed...",__FUNCTION__,__FILE__,__LINE__);
			shell_exec("$rm -rf $tmppath");
			return;		
		}	
		
		$cmdline="$mysql --user=$user$adminpassword$host \"$free->mysql_database\" < $sql_file 2>&1";
		$cmdlineVer="$mysql --user=$user$adminpassword_text$host \"$free->mysql_database\" < $sql_file 2>&1";
		writelogs("[$servername] $cmdlineVer",__FUNCTION__,__FILE__,__LINE__);
		exec($cmdline,$results);
		while (list ($num_line, $evenement) = each ($results)){if(trim($evenement)<>null){writelogs("[$servername] $evenement",__FUNCTION__,__FILE__,__LINE__);}}$results=array();
		
		if($free->mysql_username<>"root"){
			writelogs("[$servername] Setting privileges for $free->mysql_username on $free->mysql_database",__FUNCTION__,__FILE__,__LINE__);
			$q->PRIVILEGES($free->mysql_username, $free->mysql_password, $free->mysql_database);
		}
		
		
	}
	if(is_dir("$tmppath/MySQL")){@unlink($sql_file);}
	writelogs("[$servername] restoring $free->WORKING_DIRECTORY",__FUNCTION__,__FILE__,__LINE__);
	@mkdir($free->WORKING_DIRECTORY,0755,true);
	if(!is_dir($free->WORKING_DIRECTORY)){
		writelogs("[$servername] fatal $free->WORKING_DIRECTORY permission denied...",__FUNCTION__,__FILE__,__LINE__);
		shell_exec("$rm -rf $tmppath");
		return;		
	}
	$cmdline="$cp -rf $tmppath/* $free->WORKING_DIRECTORY/ 2>&1";
	writelogs("[$servername] $cmdline",__FUNCTION__,__FILE__,__LINE__);
	exec($cmdline,$results);
	while (list ($num_line, $evenement) = each ($results)){if(trim($evenement)<>null){writelogs("[$servername] $evenement",__FUNCTION__,__FILE__,__LINE__);}}$results=array();		
	
	writelogs("[$servername] Cleaning temporary directory",__FUNCTION__,__FILE__,__LINE__);
	shell_exec("$rm -rf $tmppath");
	writelogs("[$servername] rebuild the website",__FUNCTION__,__FILE__,__LINE__);
	buildHost(null,$servername);
	if(!$GLOBALS["NO_HTTPD_CONF"]){CheckHttpdConf();}
	if(!$GLOBALS["NO_HTTPD_RELOAD"]){reload_apache();}	
		
	$t2=time();$took=$unix->distanceOfTimeInWords($t1,$t2,true);
	writelogs("[$servername] Finish restoring the website took:$took",__FUNCTION__,__FILE__,__LINE__);
	
}
function backupsite($servername){
	$unix=new unix();
	$free=new freeweb($servername);
	$tempdir="/var/tmp/webget/$servername";
	$targetpackage=dirname(__FILE__)."/ressources/logs/web/$servername.tar.gz";
	if(is_file($targetpackage)){@unlink($targetpackage);}
	
	writelogs("[$servername] Starting backup this website",__FUNCTION__,__FILE__,__LINE__);
	$date_start=time();
	
	if(!is_dir("$free->WORKING_DIRECTORY")){
		writelogs("[$servername] Directory:`$free->WORKING_DIRECTORY` no such directory",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	
	@mkdir($tempdir,0755,true);
	writelogs("[$servername] Copy website content to $tempdir",__FUNCTION__,__FILE__,__LINE__);
	$cp=$unix->find_program("cp");
	$rm=$unix->find_program("rm");
	writelogs("[$servername] Copy website $free->WORKING_DIRECTORY content to $tempdir...",__FUNCTION__,__FILE__,__LINE__);
	writelogs("[$servername] Copy Configuration File $tempdir/artica.restore...",__FUNCTION__,__FILE__,__LINE__);
	@file_put_contents("$tempdir/artica.restore", $free->BackupConfig());
	shell_exec("$cp -rf $free->WORKING_DIRECTORY/* $tempdir/");
	writelogs("[$servername] Copy website $free->WORKING_DIRECTORY content to $tempdir done...",__FUNCTION__,__FILE__,__LINE__);
	
	if($free->mysql_database<>null){
		$q=new mysql();
		if($free->mysql_instance_id>0){$q=new mysql_multi($free->mysql_instance_id);}
		if(!$q->DATABASE_EXISTS($free->mysql_database)){
			writelogs("[$servername] $free->mysql_database no such database",__FUNCTION__,__FILE__,__LINE__);
			$date_end=time();
			backupsite_compress($tempdir,$targetpackage);
			$calculate=$unix->distanceOfTimeInWords($date_start,$date_end);
			writelogs("[$servername] done...time: $calculate",__FUNCTION__,__FILE__,__LINE__);		
			return;
		}
		
		backupsite_mysql_database_mysqldump($free->mysql_database,$tempdir."/MySQL",$free->mysql_instance_id,$servername);
	}	
	
	backupsite_compress($tempdir,$targetpackage);
	$date_end=time();
	$calculate=$unix->distanceOfTimeInWords($date_start,$date_end);
	writelogs("[$tempdir] done...time: $calculate",__FUNCTION__,__FILE__,__LINE__);	
	if(!is_file($targetpackage)){
		writelogs("[$tempdir] failed, $targetpackage no such file",__FUNCTION__,__FILE__,__LINE__);	
		return;
	}
	@chmod($targetpackage, 0755);
	if(is_dir($tempdir)){
		writelogs("[$tempdir] cleaning...",__FUNCTION__,__FILE__,__LINE__);	
		shell_exec("$rm -rf $tempdir");
	}
	
	
}

function backupsite_compress($directory,$targetpackage){
	if(!is_dir($directory)){
		writelogs("[$directory] No such directory",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	$date_start=time();
	$unix=new unix();
	$tar=$unix->find_program("tar");
	chdir($directory);
	$cmd="$tar -czf $targetpackage *";
	writelogs("[$directory] $cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	$date_end=time();
	$calculate=$unix->distanceOfTimeInWords($date_start,$date_end);
	writelogs("[$directory] done...time: $calculate",__FUNCTION__,__FILE__,__LINE__);	
}

function backupsite_mysql_database_mysqldump($database,$temporarySourceDir,$instance_id,$servername){
	include_once(dirname(__FILE__).'/ressources/class.mysql-multi.inc');
	$date_start=time();
	$q=new mysql();
	$TpmPrefix=null;
	$Socket=null;
	$RemotePathSuffix=null;
	$instancename=null;
	
	if($instance_id>0){
		$q=new mysql_multi($instance_id);
		$instancename=" ($mysql->MyServer) ";	
		$TpmPrefix=$instance_id;
		$Socket=" --socket=$q->SocketPath";
		$RemotePathSuffix="-$q->MyServerCMDLINE";
	}	
	
	$sock=new sockets();
	$NoBzipForBackupDatabasesDump=$sock->GET_INFO("NoBzipForBackupDatabasesDump");
	if($NoBzipForBackupDatabasesDump==null){$NoBzipForBackupDatabasesDump=1;}
	if($temporarySourceDir==null){$temporarySourceDir="/home/mysqlhotcopy";}
	if($q->mysql_password<>null){$password=" -p$q->mysql_password";}
	if($q->mysql_admin<>null){$user=" -u $q->mysql_admin";}
	
	if(!is_dir($temporarySourceDir)){@mkdir($temporarySourceDir,0755,true);}
	
	
	$unix=new unix();
	$mysqldump=$unix->find_program("mysqldump");
	$bzip2=$unix->find_program("bzip2");
	if($mysqldump==null){
		writelogs("ERROR,[$servername] {$instancename} Unable to find mysqldump",__FUNCTION__,__FILE__,__LINE__);	
		return;
	}
	$target_file="$temporarySourceDir/$servername.sql.tar.bz2";
	
	if(!is_dir(dirname($target_file))){@mkdir(dirname($target_file),0755,true);}
	$bzip2_cmd="| $bzip2 ";
	
	if($NoBzipForBackupDatabasesDump==1){
		$bzip2_cmd=null;
		$target_file="$temporarySourceDir/$servername.sql";
	}
	
	$cmd="$mysqldump$Socket$user$password --single-transaction --skip-add-locks --skip-lock-tables $database $bzip2_cmd> $target_file 2>&1";
	if($GLOBALS["VERBOSE"]){writelogs(str_replace($password, "****", $cmd),__FUNCTION__,__FILE__,__LINE__);}
	writelogs("INFO,{$instancename} Dumping $database mysql database",__FUNCTION__,__FILE__,__LINE__);	
	exec($cmd,$results);
	$date_end=time();
	
	$calculate=distanceOfTimeInWords($date_start,$date_end);
	writelogs("INFO,{$instancename} $database $calculate",__FUNCTION__,__FILE__,__LINE__);	
	
	
	while (list ($num_line, $evenement) = each ($results)){
			if($GLOBALS["VERBOSE"]){writelogs("{$instancename}$evenement",__FUNCTION__,__FILE__,__LINE__);}
			if(preg_match("#Error\s+([0-9]+)#",$evenement)){
				backup_events($ID,"mysql","ERROR,{$instancename} $evenement",__LINE__);
				writelogs("ERROR,{$instancename} $evenement",__FUNCTION__,__FILE__,__LINE__);	
				return;
			}
		}	
	
	
	if(!is_file("$target_file")){
		writelogs("ERROR,{$instancename} Dumping $database mysql database failed, $target_file no such file or directory",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	$size=$unix->file_size_human("$target_file");
	writelogs("INFO,{$instancename} END dumping $database mysql database ($size)",__FUNCTION__,__FILE__,__LINE__);
	
}

function ScanSize(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/tests.". __FUNCTION__.".time";
	$pid=@file_get_contents("$pidfile");
	if($unix->process_exists($pid,basename(__FILE__))){system_admin_events("Already executed PID $pid",__FUNCTION__,__FILE__,__LINE__,"freewebs");die();}
	@file_put_contents($pidfile, getmypid());
	$time=$unix->file_time_min($timefile);
	if($time<15){system_admin_events("No less than 15mn or delete $timefile file",__FUNCTION__,__FILE__,__LINE__,"freewebs");die();}
	@unlink($timefile);
	@file_put_contents($timefile, time());		
	
	$t=time();
	$q=new mysql();
	$sql="SELECT servername FROM freeweb";
	$results=$q->QUERY_SQL($sql,'artica_backup');
	$GLobalSize=0;
	if(mysql_num_rows($results)==0){return;}
	$sitesNumber=mysql_num_rows($results);
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$free=new freeweb($ligne["servername"]);
		if($free->IsGroupWareFromArtica()){
			$q->QUERY_SQL("UPDATE freeweb SET DirectorySize=0 WHERE servername='{$ligne["servername"]}'","artica_backup");
			continue;
		}
		$free->CheckWorkingDirectory();
		if(is_link($free->WORKING_DIRECTORY)){$free->WORKING_DIRECTORY=@readlink($free->WORKING_DIRECTORY);}
		if(!is_dir($free->WORKING_DIRECTORY)){
			$q->QUERY_SQL("UPDATE freeweb SET DirectorySize=0 WHERE servername='{$ligne["servername"]}'","artica_backup");
			if(!$q->ok){system_admin_events("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"freewebs");}
			continue;
		}
		$size=$unix->DIRSIZE_BYTES($free->WORKING_DIRECTORY);
		$GLobalSize=$GLobalSize+$size;
		if($GLOBALS["VERBOSE"]){echo "{$ligne["servername"]} $size Bytes\n";}
		$q->QUERY_SQL("UPDATE freeweb SET DirectorySize=$size WHERE servername='{$ligne["servername"]}'","artica_backup");
		if(!$q->ok){system_admin_events("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"freewebs");}
		
	}
	
	if($GLobalSize>0){
		$took=$unix->distanceOfTimeInWords($t,time(),true);
		$GLobalSize=round($GLobalSize/1024,2);
		$GLobalSize=$GLobalSize/1000;
		system_admin_events("$sitesNumber web site(s) scanned {$GLobalSize}M took:$took",__FUNCTION__,__FILE__,__LINE__,"freewebs");
	}
}
function ZarafaWebAccessInFrontEnd($DAEMON_PATH){
	$sock=new sockets();
	$users=new usersMenus();
	$unix=new unix();
	$free=new freeweb("__default__");
	@unlink("/etc/apache2/sites-enabled/default-www");
	@unlink("/etc/apache2/sites-enabled/default-ssl");
	if($free->servername<>null){
		echo "Starting......: ".date("H:i:s")." [INIT]: A default website is already setup.\n";
		return;
	}
	
	
	
	if(!$users->ZARAFA_INSTALLED){
		echo "Starting......: ".date("H:i:s")." [INIT]: Apache Zarafa not Installed\n";
		return null;}
	$ZarafaWebAccessInFrontEnd=$sock->GET_INFO("ZarafaWebAccessInFrontEnd");
	if(!is_numeric($ZarafaWebAccessInFrontEnd)){$ZarafaWebAccessInFrontEnd=1;}
	if($ZarafaWebAccessInFrontEnd==0){
		echo "Starting......: ".date("H:i:s")." [INIT]: Apache Zarafa in frontend is disabled\n";
	}
	
	$FreeWebListenSSLPort=$sock->GET_INFO("FreeWebListenSSLPort");
	$FreeWebListenPort=$sock->GET_INFO("FreeWebListenPort");
	if(!is_numeric($FreeWebListenSSLPort)){$FreeWebListenSSLPort=443;}
	if(!is_numeric($FreeWebListenPort)){$FreeWebListenPort=80;}
	$FreeWebDisableSSL=intval(trim($sock->GET_INFO("FreeWebDisableSSL")));
	
	if($unix->isNGnx()){
		$FreeWebListenSSLPort=447;
		$FreeWebListenPort=82;
	
	}
	
	if($unix->IsSquidReverse()){
		$FreeWebListenSSLPort=447;
		$FreeWebListenPort=82;
	}
	
	

	
	if($unix->IsSquidReverse()){$SquidActHasReverse=1;}
	if($unix->isNGnx()){$SquidActHasReverse=1;}
	
	if($SquidActHasReverse==1){
		$FreeWebListenSSLPort=447;
	}
	$f[]="# ". basename(__FILE__)." ZarafaWebAccessInFrontEnd=1 FreeWebDisableSSL=$FreeWebDisableSSL L.".__LINE__;
	$f[]="<VirtualHost _default_:$FreeWebListenPort>";
	$f[]="\tServerAdmin webmaster@_default_";
	$f[]="\tDocumentRoot /usr/share/zarafa-webaccess";
	$f[]="";
	$f[]="\t<Directory /usr/share/zarafa-webaccess/>";
	$f[]="\t\tOptions -Indexes +FollowSymLinks";
	$f[]="\t\tphp_flag register_globals off";
	$f[]="\t\tphp_flag magic_quotes_gpc off";
	$f[]="\t\tphp_flag magic_quotes_runtime off";
	$f[]="\t\tphp_value post_max_size 50M";
	$f[]="\t\tphp_value upload_max_filesize 50M";
	$f[]="\t\tphp_flag short_open_tag on";
	$f[]="\t\tphp_flag safe_mode off";
	$f[]="\t</Directory>";
	$f[]="</VirtualHost>";

	
	if($FreeWebDisableSSL==0){
		$f[]="<IfModule mod_ssl.c>";
		$f[]="\t<VirtualHost _default_:$FreeWebListenSSLPort>";
		$f[]="\t\tServerAdmin webmaster@_default_";
		$f[]="\t\tDocumentRoot /usr/share/zarafa-webaccess";
		$f[]="\t<Directory />";
		$f[]="\t\tOptions FollowSymLinks";
		$f[]="\t\tAllowOverride None";
		$f[]="\t</Directory>";
		$f[]="\t<Directory /usr/share/zarafa-webaccess>";
		$f[]="\t\tOptions -Indexes +FollowSymLinks";
		$f[]="\t\tphp_flag register_globals off";
		$f[]="\t\tphp_flag magic_quotes_gpc off";
		$f[]="\t\tphp_flag magic_quotes_runtime off";
		$f[]="\t\tphp_value post_max_size 50M";
		$f[]="\t\tphp_value upload_max_filesize 50M";
		$f[]="\t\tphp_flag short_open_tag on";
		$f[]="\t\tphp_flag safe_mode off";
		

		
		$f[]="\t</Directory>";
		$f[]="\tErrorLog /var/log/apache2/error.log";
	
		$f[]="\tLogLevel warn";
		$f[]="\tCustomLog /var/log/apache2/ssl_access.log combined";
		$f[]="\tSSLEngine on";
		$apache_certificate=new apache_certificate();
		$f[]=$apache_certificate->build();
		$f[]="\t</VirtualHost>";
		$f[]="</IfModule>";
	}
	echo "Starting......: ".date("H:i:s")." [INIT]: Apache /etc/apache2/sites-enabled/000-default done\n";
	@file_put_contents("/etc/apache2/sites-enabled/000-default", @implode("\n", $f));	
	
	
	
}


function restart_progress(){
	
	$sock=new sockets();
	$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
	
	build_progress_restart("EnableFreeWeb: $EnableFreeWeb",20);
	if($EnableFreeWeb==0){
		build_progress_restart("{stopping_service}",80);
		system("/etc/init.d/apache2 stop");
		build_progress_restart("{restarting_service}",90);
		system("/etc/init.d/artica-status restart --force");
		build_progress_restart("{restarting_service} {done}",100);
		return;
	}
	
	build_progress_restart("{restarting_service}",80);
	system("/etc/init.d/apache2 restart");
	build_progress_restart("{restarting_service}",90);
	system("/etc/init.d/artica-status restart --force");
	build_progress_restart("{restarting_service} {done}",100);
	
}

?>