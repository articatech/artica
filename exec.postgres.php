<?php
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="PostgreSQL Daemon";
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
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');



$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--tables"){$GLOBALS["OUTPUT"]=true;checktables();die();}
if($argv[1]=="--dbsize"){$GLOBALS["OUTPUT"]=true;InfluxDbSize();die();}
if($argv[1]=="--remove-influx"){$GLOBALS["OUTPUT"]=true;remove_influx();die();}
if($argv[1]=="--install"){$GLOBALS["OUTPUT"]=true;install_postgres();die();}
if($argv[1]=="--restart-progress"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--vacuumdb"){$GLOBALS["OUTPUT"]=true;vacuumdb();die();}
if($argv[1]=="--php5-pgsql"){$GLOBALS["OUTPUT"]=true;php5_pgsql();die();}
if($argv[1]=="--remove-database"){$GLOBALS["OUTPUT"]=true;remove_database();die();}




echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Could not understand ?\n";

function php5_pgsql(){
	$unix=new unix();
	$unix->DEBIAN_INSTALL_PACKAGE("php5-pgsql");
	system("/usr/share/artica-postfix/exec.php.ini.php");
	system("/etc/init.d/artica-webconsole restart");
}

function restart() {
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		build_progress_restart("{failed}",110);
		
		return;
	}
	@file_put_contents($pidfile, getmypid());
	build_progress_restart("{stopping}",10);
	if(!stop(true)){return;}
	sleep(1);
	build_progress_restart("{starting}",26);
	if(!start(true)){return;}
	build_progress_restart("{status}...",80);
	InfluxDbSize();
	build_progress_restart("{restarting}: {success}...",100);
}

function build_progress_restart($text,$pourc){
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} {$pourc}% $text\n";}
	$cachefile="/usr/share/artica-postfix/ressources/logs/postgres.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}

function checktables(){
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} checktables...\n";
	$pg=new postgres_sql();
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} CREATE_TABLES...\n";
	$pg->CREATE_TABLES();
	
}

function remove_influx(){
	
	
	if(is_file("/etc/init.d/influx-db")){
		shell_exec("/etc/init.d/influx-db stop");
	
		if(is_file('/usr/sbin/update-rc.d')){
			shell_exec("/usr/sbin/update-rc.d -f influx-db remove >/dev/null 2>&1");
		}
	
		@unlink("/etc/init.d/influx-db");
		if(is_dir("/opt/influxdb")){shell_exec("/bin/rm -rf /opt/influxdb"); }
		system("/etc/init.d/monit restart");
		system("/etc/init.d/artica-status restart --force");
	
	}
	
	if(is_dir("/home/artica/squid/InfluxDB")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Uninstalling Influx database.\n";}
		shell_exec("/bin/rm -rf /home/artica/squid/InfluxDB");
	}
	
}

function fuser_port(){
	$unix=new unix();
	$kill=$unix->find_program("kill");
	build_progress_restart("{checking} TCP:5432",66);
	$PIDS=$unix->PIDOF_BY_PORT("5432");
	if(count($PIDS)==0){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} 0 PID listens 5432...\n";}

		return;}
		while (list ($pid, $b) = each ($PIDS) ){
			if($unix->process_exists($pid)){
				build_progress_restart("{killing} $pid : 5432",67);
				$cmdline=@file_get_contents("/proc/$pid/cmdline");
				if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} killing PID $pid that listens 53 UDP port\n";}
				if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmdline\n";}
				unix_system_kill_force($pid);
			}
		}
}


function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$users=new usersMenus();
	$Masterbin="/usr/local/ArticaStats/bin/postgres";

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
		build_progress_restart("{starting} {failed} no memory",110);
		return;
	}

	

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		build_progress_restart("{starting} {success}",30);
		return true;
	}
	
	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	$su=$unix->find_program("su");
	$rm=$unix->find_program("rm");
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	$EnableInfluxDB=intval($sock->GET_INFO("EnableInfluxDB"));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} EnableInfluxDB: $EnableInfluxDB\n";}
	$InfluxUseRemote=intval($sock->GET_INFO("InfluxUseRemote"));
	
	if($users->POSTFIX_INSTALLED){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Postfix installed: True\n";}
	}
	
	if($InfluxUseRemote==1){$EnableInfluxDB=0;}
	
	
	$FreeZePostGres=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/FreeZePostGres"));
	if($FreeZePostGres==1){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Freeze !!! Aborting...\n";}
		return;
	}
	

	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Use Remote statistics.: $InfluxUseRemote\n";}
	
	
	if(!$users->POSTFIX_INSTALLED){
		$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
		if($EnableIntelCeleron==1){$EnableInfluxDB=0;}
	}
	
	if(is_file("/etc/artica-postfix/STATS_APPLIANCE")){$EnableInfluxDB=1;$SquidPerformance=0;$EnableIntelCeleron=0;}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Use Statistics DB.....: $EnableInfluxDB\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Use Intel Celeron mode: $EnableIntelCeleron\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Use Performance.......: $SquidPerformance\n";}

	if($EnableInfluxDB==0){
		build_progress_restart("{starting} {failed} {disabled}",110);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableInflux)\n";}
		return;
	}
	
	if(!function_exists("pg_connect")){
		build_progress_restart("{starting} installing php5-pgsql",35);
		$unix->DEBIAN_INSTALL_PACKAGE("php5-pgsql");
		system("/usr/share/artica-postfix/exec.php.ini.php");
		if(!function_exists("pg_connect")){
			build_progress_restart("{starting} installing php5-pgsql {failed}",110);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} pg_connect no such function\n";}
			return;
		}
		system("/etc/init.d/artica-webconsole restart");
	}
	
	
	build_progress_restart("{starting}",40);
	
	if(!$unix->UnixUserExists("ArticaStats")){
		$unix->CreateUnixUser("ArticaStats","ArticaStats");
	}
	@mkdir("/var/run/ArticaStats",0755,true);
	@mkdir("/home/ArticaStatsDB",0700,true);
	@mkdir("/var/log/ArticaStatsDB",0755,true);
	@chown("/home/ArticaStatsDB","ArticaStats");
	@chgrp("/home/ArticaStatsDB","ArticaStats");
	@chown("/var/run/ArticaStats","ArticaStats");
	@chgrp("/var/run/ArticaStats","ArticaStats");	
	
	@chown("/var/log/ArticaStatsDB","ArticaStats");
	@chgrp("/var/log/ArticaStatsDB","ArticaStats");
	
	if(is_file("/var/log/ArticaStatsDB/ArticaStatsDB.log")){
		
		@unlink("/var/log/ArticaStatsDB/ArticaStatsDB.log");
		@touch("/var/log/ArticaStatsDB/ArticaStatsDB.log");
	}
	@chown("/var/log/ArticaStatsDB/ArticaStatsDB.log","ArticaStats");
	@chgrp("/var/log/ArticaStatsDB/ArticaStatsDB.log","ArticaStats");
	if(is_file("/var/run/ArticaStats/.s.PGSQL.8086")){
		@unlink("/var/run/ArticaStats/.s.PGSQL.8086");
	}
	
	$php=$unix->LOCATE_PHP5_BIN();
	if(!is_file("/etc/artica-postfix/locales.gen")){
		squid_admin_mysql(1, "Generating languages for the PostGreSQL compatibility", null,__FILE__,__LINE__);
		build_progress_restart("{generating_langs}",42);
		system("$php /usr/share/artica-postfix/exec.locale.gen.php");
	}
	
	
	if(!is_dir("/home/ArticaStatsDB/base/1")){
		squid_admin_mysql(0, "Creating a new PostgreSQL database in ArticaStatsDB", null,__FILE__,__LINE__);
		build_progress_restart("{starting}",45);
		$rm=$unix->find_program("rm");
		shell_exec("$rm -rf /home/ArticaStatsDB/*");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} initialize database...\n";}
		system("$su -c \"/usr/local/ArticaStats/bin/initdb --username=ArticaStats /home/ArticaStatsDB --no-locale -E UTF8\" ArticaStats");
	}
	
	if(!is_dir("/home/ArticaStatsDB/base/1")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} initialize database failed...\n";}
		$rm=$unix->find_program("rm");
		shell_exec("$rm -rf /home/ArticaStatsDB/*");
		return;
	}
	
	build_progress_restart("{starting}",50);
	xbuild();
	fuser_port();
	
	build_progress_restart("{starting} {permissions}",55);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Apply permissions on /home/ArticaStatsDB\n";}
	$chown=$unix->find_program("chown");
	$chmod=$unix->find_program("chmod");
	shell_exec("$chown -R ArticaStats:ArticaStats /home/ArticaStatsDB");
	shell_exec("$chmod 0700 /home/ArticaStatsDB");
	
	
	if(is_file("/home/ArticaStatsDB/postmaster.pid")){@unlink("/home/ArticaStatsDB/postmaster.pid");}
	
	$f[]="su -l ArticaStats -c '";
	$f[]="/usr/local/ArticaStats/bin/pg_ctl -o \"-k /tmp,/var/run/ArticaStats\"  -D /home/ArticaStatsDB -l /var/log/ArticaStatsDB/ArticaStatsDB.log start'";
	
	
	$cmd=@implode(" ", $f) ." >/dev/null 2>&1 &";
	
	
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	
	shell_exec($cmd);
	
	
	

	for($i=1;$i<5;$i++){
		build_progress_restart("{starting} {wait} $i/5",70);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		build_progress_restart("{starting} {success}",75);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		$pg=new postgres_sql();
		$pg->CREATE_TABLES();
		return true;
	}else{
		build_progress_restart("{starting} {failed}",110);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	}


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
		return true;
	}
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	
	build_progress_restart("{stopping}",15);
	$f[]="su -l ArticaStats -c '";
	$f[]="/usr/local/ArticaStats/bin/pg_ctl -D /home/ArticaStatsDB -l /var/log/ArticaStatsDB/ArticaStatsDB.log stop'";
	
	$cmd=@implode(" ", $f);

	system($cmd);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		build_progress_restart("{stopping} $i/5",15);
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		build_progress_restart("{stopping} {success}",20);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return true;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	build_progress_restart("{killing}...",25);
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		build_progress_restart("{killing}...$i/5",25);
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		build_progress_restart("{stopping}...{failed}",110);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}

	return true;
}

function build_progress_remove($text,$pourc){
	
	if($GLOBALS["OUTPUT"]){echo "Remove........: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} {$pourc}% $text\n";}
	$cachefile="/usr/share/artica-postfix/ressources/logs/postgres.remove.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}

function remove_database(){
	
	$unix=new unix();
	
	build_progress_remove("{stopping_service}",30);
	stop(true);
	$rm=$unix->find_program("rm");
	build_progress_remove("{removing}",40);
	shell_exec("$rm -rf  /home/ArticaStatsDB/*");
	build_progress_remove("{starting_service}",50);
	if(!start(true)){
		build_progress_remove("{starting_service} {failed}",110);
		return;
	}
	$q=new mysql_squid_builder();
	
	
	$f[]="dashboard_apache_sizes";
	$f[]="dashboard_size_day";
	$f[]="dashboard_countwebsite_day";
	$f[]="dashboard_user_day";
	$f[]="dashboard_volume_day";
	$f[]="USERAGENTS4H";
	$f[]="dashboard_notcached";
	
	while (list ($ipaddr, $tablename) = each ($f) ){
		build_progress_remove("{purge} $tablename",80);
		$q->QUERY_SQL("TRUNCATE TABLE $tablename");
	}
	build_progress_remove("{database_size}",90);
	InfluxDbSize();
	build_progress_remove("{done}",100);
	
}

function PID_NUM(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/home/ArticaStatsDB/postmaster.pid");
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF("/usr/local/ArticaStats/bin/postgres");
	
	
	
	
	
}
function GetInfluxListenIP(){
	$unix=new unix();
	$sock=new sockets();
	$STATS_APPLIANCE=false;
	if(is_file("/etc/artica-postfix/STATS_APPLIANCE")){$STATS_APPLIANCE=true;}
	$InfluxListenInterface=$sock->GET_INFO("InfluxListenInterface");
	$InfluxListenIP=null;

	if($STATS_APPLIANCE){
		if($InfluxListenInterface==null){$InfluxListenInterface="ALL";}
	}
	if($InfluxListenInterface==null){$InfluxListenInterface="lo";}

	if($InfluxListenInterface=="lo"){
		$InfluxListenIP="127.0.0.1";
		$InfluxApiIP="127.0.0.1";
	}
	if($InfluxListenInterface=="ALL"){
		$InfluxListenIP="0.0.0.0";
		$InfluxApiIP="127.0.0.1";
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Listen Interface $InfluxListenInterface\n";}

	if($InfluxListenIP==null){
		$unix=new unix();
		$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
		$InfluxListenIP=$NETWORK_ALL_INTERFACES[$InfluxListenInterface]["IPADDR"];
		$InfluxApiIP=$InfluxListenIP;
		if($InfluxListenIP=="0.0.0.0"){$InfluxApiIP="127.0.0.1";}
		if($InfluxListenIP=="127.0.0.1"){$InfluxApiIP="127.0.0.1";}
	}

	if($STATS_APPLIANCE){
		if($InfluxListenIP=="127.0.0.1"){$InfluxListenIP="0.0.0.0";}
	}
	$sock->SET_INFO("InfluxListenIP", $InfluxListenIP);
	return $InfluxListenIP;

}

function xBackup(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$InFluxBackupDatabaseInterval=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/InFluxBackupDatabaseInterval"));
	if($InFluxBackupDatabaseInterval==0){$InFluxBackupDatabaseInterval=10080;}
	if($InFluxBackupDatabaseInterval<1440){$InFluxBackupDatabaseInterval=1440;}
	
	$Intervals[60]="45 * * * * *";
	$Intervals[120]="45 0,2,4,6,8,10,12,14,16,18,20,22 * * *";
	$Intervals[240]="45 0,4,6,10,14,18,22 * * *";
	$Intervals[1440]="10 1 * * *";
	$Intervals[10080]="10 0 * * 6";
	
	$CRON[]="MAILTO=\"\"";
	$CRON[]="{$Intervals[$InFluxBackupDatabaseInterval]} root $php /usr/share/artica-postfix/exec.postgres.backup.php >/dev/null 2>&1";
	$CRON[]="";
	file_put_contents("/etc/cron.d/InfluxBackup",@implode("\n", $CRON));
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Backup each {$InFluxBackupDatabaseInterval} Minutes\n";}
	
	$CRON=array();
	chmod("/etc/cron.d/InfluxBackup",0640);
	chown("/etc/cron.d/InfluxBackup","root");
	system("/etc/init.d/cron reload");
	
}


function xbuild(){
	$STATS_APPLIANCE=false;
	$InfluxListenInterface["127.0.0.1"]=true;
	$InfluxListenInterface[GetInfluxListenIP()]=true;
	$unix=new unix();
	
	$php=$unix->LOCATE_PHP5_BIN();
	if(is_file("/etc/artica-postfix/STATS_APPLIANCE")){$STATS_APPLIANCE=true;}
	$SET_ALL=false;
	while (list ($ipaddr, $array) = each ($InfluxListenInterface) ){
		build_progress_restart("{starting} Listen $ipaddr",55);
		if($ipaddr=="0.0.0.0"){$ipaddr="*";$SET_ALL=true;}
		
		$IPADDRZ[]=$ipaddr;
	}
	
	if($SET_ALL){
		$IPADDRZ=array();
		$IPADDRZ[]="*";
	}
	
	xBackup();

	
	$PostgreSQLSharedBuffer=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/PostgreSQLSharedBuffer"));
	if($PostgreSQLSharedBuffer==0){$PostgreSQLSharedBuffer=32;}
	
	$PostgreSQLEffectiveCacheSize=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/PostgreSQLEffectiveCacheSize"));
	if($PostgreSQLEffectiveCacheSize==0){$PostgreSQLEffectiveCacheSize=256;}
	
	$PostgreSQLWorkMem=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/PostgreSQLWorkMem"));
	if($PostgreSQLWorkMem==0){$PostgreSQLWorkMem=4;}
	$f[]="# -----------------------------";
	$f[]="# PostgreSQL configuration file";
	$f[]="# -----------------------------";
	$f[]="#";
	$f[]="# This file consists of lines of the form:";
	$f[]="#";
	$f[]="#   name = value";
	$f[]="#";
	$f[]="# (The \"=\" is optional.)  Whitespace may be used.  Comments are introduced with";
	$f[]="# \"#\" anywhere on a line.  The complete list of parameter names and allowed";
	$f[]="# values can be found in the PostgreSQL documentation.";
	$f[]="#";
	$f[]="# The commented-out settings shown in this file represent the default values.";
	$f[]="# Re-commenting a setting is NOT sufficient to revert it to the default value;";
	$f[]="# you need to reload the server.";
	$f[]="#";
	$f[]="# This file is read on server startup and when the server receives a SIGHUP";
	$f[]="# signal.  If you edit the file on a running system, you have to SIGHUP the";
	$f[]="# server for the changes to take effect, or use \"pg_ctl reload\".  Some";
	$f[]="# parameters, which are marked below, require a server shutdown and restart to";
	$f[]="# take effect.";
	$f[]="#";
	$f[]="# Any parameter can also be given as a command-line option to the server, e.g.,";
	$f[]="# \"postgres -c log_connections=on\".  Some parameters can be changed at run time";
	$f[]="# with the \"SET\" SQL command.";
	$f[]="#";
	$f[]="# Memory units:  kB = kilobytes        Time units:  ms  = milliseconds";
	$f[]="#                MB = megabytes                     s   = seconds";
	$f[]="#                GB = gigabytes                     min = minutes";
	$f[]="#                TB = terabytes                     h   = hours";
	$f[]="#                                                   d   = days";
	$f[]="";
	$f[]="";
	$f[]="#------------------------------------------------------------------------------";
	$f[]="# FILE LOCATIONS";
	$f[]="#------------------------------------------------------------------------------";
	$f[]="";
	$f[]="# The default values of these variables are driven from the -D command-line";
	$f[]="# option or PGDATA environment variable, represented here as ConfigDir.";
	$f[]="";
	$f[]="data_directory = '/home/ArticaStatsDB'		# use data in another directory";
	$f[]="hba_file = '/home/ArticaStatsDB/pg_hba.conf'	# host-based authentication file";
	$f[]="#ident_file = '/home/ArticaStatsDB/pg_ident.conf'	# ident configuration file";
	$f[]="#external_pid_file = '/var/run/ArticaStats/postgres.pid'			# write an extra PID file";
	$f[]="";
	$f[]="";
	$f[]="#------------------------------------------------------------------------------";
	$f[]="# CONNECTIONS AND AUTHENTICATION";
	$f[]="#------------------------------------------------------------------------------";
	$f[]="";
	$f[]="# - Connection Settings -";
	$f[]="";
	$f[]="listen_addresses = '".@implode(",", $IPADDRZ)."'";
	$f[]="port = 5432";				
	$f[]="max_connections = 100";
	$f[]="# Note:  Increasing max_connections costs ~400 bytes of shared memory per";
	$f[]="# connection slot, plus lock space (see max_locks_per_transaction).";
	$f[]="#superuser_reserved_connections = 3	# (change requires restart)";
	$f[]="unix_socket_directories = '/tmp,/var/run/ArticaStats'";
	$f[]="#unix_socket_group = ''			# (change requires restart)";
	$f[]="unix_socket_permissions = 0777		# begin with 0 to use octal notation";
	$f[]="#bonjour = off";
	$f[]="#bonjour_name = ''";
	$f[]="";
	$f[]="# - Security and Authentication -";
	$f[]="";
	$f[]="#authentication_timeout = 1min";
	$f[]="#ssl = off";
	$f[]="#ssl_ciphers = 'HIGH:MEDIUM:+3DES:!aNULL'";
	$f[]="#ssl_prefer_server_ciphers = on		# (change requires restart)";
	$f[]="#ssl_ecdh_curve = 'prime256v1'		# (change requires restart)";
	$f[]="#ssl_cert_file = 'server.crt'		# (change requires restart)";
	$f[]="#ssl_key_file = 'server.key'		# (change requires restart)";
	$f[]="#ssl_ca_file = ''			# (change requires restart)";
	$f[]="#ssl_crl_file = ''			# (change requires restart)";
	$f[]="#password_encryption = on";
	$f[]="#db_user_namespace = off";
	$f[]="#row_security = on";
	$f[]="";
	$f[]="# GSSAPI using Kerberos";
	$f[]="#krb_server_keyfile = ''";
	$f[]="#krb_caseins_users = off";
	$f[]="";
	$f[]="# - TCP Keepalives -";
	$f[]="# see \"man 7 tcp\" for details";
	$f[]="";
	$f[]="#tcp_keepalives_idle = 0		# TCP_KEEPIDLE, in seconds;";
	$f[]="					# 0 selects the system default";
	$f[]="#tcp_keepalives_interval = 0		# TCP_KEEPINTVL, in seconds;";
	$f[]="					# 0 selects the system default";
	$f[]="#tcp_keepalives_count = 0		# TCP_KEEPCNT;";
	$f[]="					# 0 selects the system default";
	$f[]="";
	$f[]="";
	$f[]="#------------------------------------------------------------------------------";
	$f[]="# RESOURCE USAGE (except WAL)";
	$f[]="#------------------------------------------------------------------------------";
	$f[]="";
	$f[]="# - Memory -";
	$f[]="";
	$f[]="shared_buffers = {$PostgreSQLSharedBuffer}MB";
	$f[]="effective_cache_size = {$PostgreSQLEffectiveCacheSize}MB";
	$f[]="					# (change requires restart)";
	$f[]="#huge_pages = try			# on, off, or try";
	$f[]="					# (change requires restart)";
	$f[]="#temp_buffers = 8MB			# min 800kB";
	$f[]="#max_prepared_transactions = 0		# zero disables the feature";
	$f[]="					# (change requires restart)";
	$f[]="# Note:  Increasing max_prepared_transactions costs ~600 bytes of shared memory";
	$f[]="# per transaction slot, plus lock space (see max_locks_per_transaction).";
	$f[]="# It is not advisable to set max_prepared_transactions nonzero unless you";
	$f[]="# actively intend to use prepared transactions.";
	$f[]="work_mem = {$PostgreSQLWorkMem}MB				# min 64kB";
	$f[]="maintenance_work_mem = 64MB		# min 1MB";
	$f[]="autovacuum_work_mem = -1		# min 1MB, or -1 to use maintenance_work_mem";
	$f[]="#max_stack_depth = 2MB			# min 100kB";
	$f[]="#dynamic_shared_memory_type = posix	# the default is the first option";
	$f[]="					# supported by the operating system:";
	$f[]="					#   posix";
	$f[]="					#   sysv";
	$f[]="					#   windows";
	$f[]="					#   mmap";
	$f[]="					# use none to disable dynamic shared memory";
	$f[]="";
	$f[]="# - Disk -";
	$f[]="";
	$f[]="#temp_file_limit = -1			# limits per-session temp file space";
	$f[]="					# in kB, or -1 for no limit";
	$f[]="";
	$f[]="# - Kernel Resource Usage -";
	$f[]="";
	$f[]="#max_files_per_process = 1000		# min 25";
	$f[]="					# (change requires restart)";
	$f[]="#shared_preload_libraries = ''		# (change requires restart)";
	$f[]="";
	$f[]="# - Cost-Based Vacuum Delay -";
	$f[]="";
	$f[]="#vacuum_cost_delay = 0			# 0-100 milliseconds";
	$f[]="#vacuum_cost_page_hit = 1		# 0-10000 credits";
	$f[]="#vacuum_cost_page_miss = 10		# 0-10000 credits";
	$f[]="#vacuum_cost_page_dirty = 20		# 0-10000 credits";
	$f[]="#vacuum_cost_limit = 200		# 1-10000 credits";
	$f[]="";
	$f[]="# - Background Writer -";
	$f[]="";
	$f[]="#bgwriter_delay = 200ms			# 10-10000ms between rounds";
	$f[]="#bgwriter_lru_maxpages = 100		# 0-1000 max buffers written/round";
	$f[]="#bgwriter_lru_multiplier = 2.0		# 0-10.0 multiplier on buffers scanned/round";
	$f[]="";
	$f[]="# - Asynchronous Behavior -";
	$f[]="";
	$f[]="#effective_io_concurrency = 1		# 1-1000; 0 disables prefetching";
	$f[]="#max_worker_processes = 8";
	$f[]="#max_parallel_degree = 0		# max number of worker processes per node";
	$f[]="";
	$f[]="";
	$f[]="#------------------------------------------------------------------------------";
	$f[]="# WRITE AHEAD LOG";
	$f[]="#------------------------------------------------------------------------------";
	$f[]="";
	$f[]="# - Settings -";
	$f[]="";
	$f[]="#wal_level = minimal			# minimal, archive, hot_standby, or logical";
	$f[]="					# (change requires restart)";
	$f[]="#fsync = on				# turns forced synchronization on or off";
	$f[]="#synchronous_commit = on		# synchronization level;";
	$f[]="					# off, local, remote_write, or on";
	$f[]="#wal_sync_method = fsync		# the default is the first option";
	$f[]="					# supported by the operating system:";
	$f[]="					#   open_datasync";
	$f[]="					#   fdatasync (default on Linux)";
	$f[]="					#   fsync";
	$f[]="					#   fsync_writethrough";
	$f[]="					#   open_sync";
	$f[]="#full_page_writes = on			# recover from partial page writes";
	$f[]="#wal_compression = off			# enable compression of full-page writes";
	$f[]="#wal_log_hints = off			# also do full page writes of non-critical updates";
	$f[]="					# (change requires restart)";
	$f[]="#wal_buffers = -1			# min 32kB, -1 sets based on shared_buffers";
	$f[]="					# (change requires restart)";
	$f[]="#wal_writer_delay = 200ms		# 1-10000 milliseconds";
	$f[]="";
	$f[]="#commit_delay = 0			# range 0-100000, in microseconds";
	$f[]="#commit_siblings = 5			# range 1-1000";
	$f[]="";
	$f[]="# - Checkpoints -";
	$f[]="";
	$f[]="#checkpoint_timeout = 5min		# range 30s-1h";
	$f[]="#max_wal_size = 1GB";
	$f[]="#min_wal_size = 80MB";
	$f[]="#checkpoint_completion_target = 0.5	# checkpoint target duration, 0.0 - 1.0";
	$f[]="#checkpoint_warning = 30s		# 0 disables";
	$f[]="";
	$f[]="# - Archiving -";
	$f[]="";
	$f[]="#archive_mode = off		# enables archiving; off, on, or always";
	$f[]="				# (change requires restart)";
	$f[]="#archive_command = ''		# command to use to archive a logfile segment";
	$f[]="				# placeholders: %p = path of file to archive";
	$f[]="				#               %f = file name only";
	$f[]="				# e.g. 'test ! -f /mnt/server/archivedir/%f && cp %p /mnt/server/archivedir/%f'";
	$f[]="#archive_timeout = 0		# force a logfile segment switch after this";
	$f[]="				# number of seconds; 0 disables";
	$f[]="";
	$f[]="";
	$f[]="#------------------------------------------------------------------------------";
	$f[]="# REPLICATION";
	$f[]="#------------------------------------------------------------------------------";
	$f[]="";
	$f[]="# - Sending Server(s) -";
	$f[]="";
	$f[]="# Set these on the master and on any standby that will send replication data.";
	$f[]="";
	$f[]="#max_wal_senders = 0		# max number of walsender processes";
	$f[]="				# (change requires restart)";
	$f[]="#wal_keep_segments = 0		# in logfile segments, 16MB each; 0 disables";
	$f[]="#wal_sender_timeout = 60s	# in milliseconds; 0 disables";
	$f[]="";
	$f[]="#max_replication_slots = 0	# max number of replication slots";
	$f[]="				# (change requires restart)";
	$f[]="#track_commit_timestamp = off	# collect timestamp of transaction commit";
	$f[]="				# (change requires restart)";
	$f[]="";
	$f[]="# - Master Server -";
	$f[]="";
	$f[]="# These settings are ignored on a standby server.";
	$f[]="";
	$f[]="#synchronous_standby_names = ''	# standby servers that provide sync rep";
	$f[]="				# comma-separated list of application_name";
	$f[]="				# from standby(s); '*' = all";
	$f[]="#vacuum_defer_cleanup_age = 0	# number of xacts by which cleanup is delayed";
	$f[]="";
	$f[]="# - Standby Servers -";
	$f[]="";
	$f[]="# These settings are ignored on a master server.";
	$f[]="";
	$f[]="#hot_standby = off			# \"on\" allows queries during recovery";
	$f[]="					# (change requires restart)";
	$f[]="#max_standby_archive_delay = 30s	# max delay before canceling queries";
	$f[]="					# when reading WAL from archive;";
	$f[]="					# -1 allows indefinite delay";
	$f[]="#max_standby_streaming_delay = 30s	# max delay before canceling queries";
	$f[]="					# when reading streaming WAL;";
	$f[]="					# -1 allows indefinite delay";
	$f[]="#wal_receiver_status_interval = 10s	# send replies at least this often";
	$f[]="					# 0 disables";
	$f[]="#hot_standby_feedback = off		# send info from standby to prevent";
	$f[]="					# query conflicts";
	$f[]="#wal_receiver_timeout = 60s		# time that receiver waits for";
	$f[]="					# communication from master";
	$f[]="					# in milliseconds; 0 disables";
	$f[]="#wal_retrieve_retry_interval = 5s	# time to wait before retrying to";
	$f[]="					# retrieve WAL after a failed attempt";
	$f[]="";
	$f[]="";
	$f[]="#------------------------------------------------------------------------------";
	$f[]="# QUERY TUNING";
	$f[]="#------------------------------------------------------------------------------";
	$f[]="";
	$f[]="# - Planner Method Configuration -";
	$f[]="";
	$f[]="#enable_bitmapscan = on";
	$f[]="#enable_hashagg = on";
	$f[]="#enable_hashjoin = on";
	$f[]="#enable_indexscan = on";
	$f[]="#enable_indexonlyscan = on";
	$f[]="#enable_material = on";
	$f[]="#enable_mergejoin = on";
	$f[]="#enable_nestloop = on";
	$f[]="#enable_seqscan = on";
	$f[]="#enable_sort = on";
	$f[]="#enable_tidscan = on";
	$f[]="";
	$f[]="# - Planner Cost Constants -";
	$f[]="";
	$f[]="#seq_page_cost = 1.0			# measured on an arbitrary scale";
	$f[]="#random_page_cost = 4.0			# same scale as above";
	$f[]="#cpu_tuple_cost = 0.01			# same scale as above";
	$f[]="#cpu_index_tuple_cost = 0.005		# same scale as above";
	$f[]="#cpu_operator_cost = 0.0025		# same scale as above";
	$f[]="#parallel_tuple_cost = 0.1		# same scale as above";
	$f[]="#parallel_setup_cost = 1000.0	# same scale as above";
	
	$f[]="";
	$f[]="# - Genetic Query Optimizer -";
	$f[]="";
	$f[]="#geqo = on";
	$f[]="#geqo_threshold = 12";
	$f[]="#geqo_effort = 5			# range 1-10";
	$f[]="#geqo_pool_size = 0			# selects default based on effort";
	$f[]="#geqo_generations = 0			# selects default based on effort";
	$f[]="#geqo_selection_bias = 2.0		# range 1.5-2.0";
	$f[]="#geqo_seed = 0.0			# range 0.0-1.0";
	$f[]="";
	$f[]="# - Other Planner Options -";
	$f[]="";
	$f[]="#default_statistics_target = 100	# range 1-10000";
	$f[]="#constraint_exclusion = partition	# on, off, or partition";
	$f[]="#cursor_tuple_fraction = 0.1		# range 0.0-1.0";
	$f[]="#from_collapse_limit = 8";
	$f[]="#join_collapse_limit = 8		# 1 disables collapsing of explicit";
	$f[]="					# JOIN clauses";
	$f[]="";
	$f[]="";
	$f[]="#------------------------------------------------------------------------------";
	$f[]="# ERROR REPORTING AND LOGGING";
	$f[]="#------------------------------------------------------------------------------";
	$f[]="";
	$f[]="# - Where to Log -";
	$f[]="";
	$f[]="#log_destination = 'stderr'		# Valid values are combinations of";
	$f[]="					# stderr, csvlog, syslog, and eventlog,";
	$f[]="					# depending on platform.  csvlog";
	$f[]="					# requires logging_collector to be on.";
	$f[]="";
	$f[]="# This is used when logging to stderr:";
	$f[]="#logging_collector = off		# Enable capturing of stderr and csvlog";
	$f[]="					# into log files. Required to be on for";
	$f[]="					# csvlogs.";
	$f[]="					# (change requires restart)";
	$f[]="";
	$f[]="# These are only used if logging_collector is on:";
	$f[]="#log_directory = 'pg_log'		# directory where log files are written,";
	$f[]="					# can be absolute or relative to PGDATA";
	$f[]="#log_filename = 'postgresql-%Y-%m-%d_%H%M%S.log'	# log file name pattern,";
	$f[]="					# can include strftime() escapes";
	$f[]="#log_file_mode = 0600			# creation mode for log files,";
	$f[]="					# begin with 0 to use octal notation";
	$f[]="#log_truncate_on_rotation = off		# If on, an existing log file with the";
	$f[]="					# same name as the new log file will be";
	$f[]="					# truncated rather than appended to.";
	$f[]="					# But such truncation only occurs on";
	$f[]="					# time-driven rotation, not on restarts";
	$f[]="					# or size-driven rotation.  Default is";
	$f[]="					# off, meaning append to existing files";
	$f[]="					# in all cases.";
	$f[]="#log_rotation_age = 1d			# Automatic rotation of logfiles will";
	$f[]="					# happen after that time.  0 disables.";
	$f[]="#log_rotation_size = 10MB		# Automatic rotation of logfiles will";
	$f[]="					# happen after that much log output.";
	$f[]="					# 0 disables.";
	$f[]="";
	$f[]="# These are relevant when logging to syslog:";
	$f[]="#syslog_facility = 'LOCAL0'";
	$f[]="#syslog_ident = 'postgres'";
	$f[]="";
	$f[]="# This is only relevant when logging to eventlog (win32):";
	$f[]="#event_source = 'PostgreSQL'";
	$f[]="";
	$f[]="# - When to Log -";
	$f[]="";
	$f[]="#client_min_messages = notice		# values in order of decreasing detail:";
	$f[]="					#   debug5";
	$f[]="					#   debug4";
	$f[]="					#   debug3";
	$f[]="					#   debug2";
	$f[]="					#   debug1";
	$f[]="					#   log";
	$f[]="					#   notice";
	$f[]="					#   warning";
	$f[]="					#   error";
	$f[]="";
	$f[]="#log_min_messages = warning		# values in order of decreasing detail:";
	$f[]="					#   debug5";
	$f[]="					#   debug4";
	$f[]="					#   debug3";
	$f[]="					#   debug2";
	$f[]="					#   debug1";
	$f[]="					#   info";
	$f[]="					#   notice";
	$f[]="					#   warning";
	$f[]="					#   error";
	$f[]="					#   log";
	$f[]="					#   fatal";
	$f[]="					#   panic";
	$f[]="";
	$f[]="#log_min_error_statement = error	# values in order of decreasing detail:";
	$f[]="					#   debug5";
	$f[]="					#   debug4";
	$f[]="					#   debug3";
	$f[]="					#   debug2";
	$f[]="					#   debug1";
	$f[]="					#   info";
	$f[]="					#   notice";
	$f[]="					#   warning";
	$f[]="					#   error";
	$f[]="					#   log";
	$f[]="					#   fatal";
	$f[]="					#   panic (effectively off)";
	$f[]="";
	$f[]="#log_min_duration_statement = -1	# -1 is disabled, 0 logs all statements";
	$f[]="					# and their durations, > 0 logs only";
	$f[]="					# statements running at least this number";
	$f[]="					# of milliseconds";
	$f[]="";
	$f[]="";
	$f[]="# - What to Log -";
	$f[]="";
	$f[]="#debug_print_parse = off";
	$f[]="#debug_print_rewritten = off";
	$f[]="#debug_print_plan = off";
	$f[]="#debug_pretty_print = on";
	$f[]="#log_checkpoints = off";
	$f[]="#log_connections = off";
	$f[]="#log_disconnections = off";
	$f[]="#log_duration = off";
	$f[]="#log_error_verbosity = default		# terse, default, or verbose messages";
	$f[]="#log_hostname = off";
	$f[]="#log_line_prefix = ''			# special values:";
	$f[]="					#   %a = application name";
	$f[]="					#   %u = user name";
	$f[]="					#   %d = database name";
	$f[]="					#   %r = remote host and port";
	$f[]="					#   %h = remote host";
	$f[]="					#   %p = process ID";
	$f[]="					#   %t = timestamp without milliseconds";
	$f[]="					#   %m = timestamp with milliseconds";
	$f[]="					#   %n = timestamp with milliseconds (as a Unix epoch)";
	$f[]="					#   %i = command tag";
	$f[]="					#   %e = SQL state";
	$f[]="					#   %c = session ID";
	$f[]="					#   %l = session line number";
	$f[]="					#   %s = session start timestamp";
	$f[]="					#   %v = virtual transaction ID";
	$f[]="					#   %x = transaction ID (0 if none)";
	$f[]="					#   %q = stop here in non-session";
	$f[]="					#        processes";
	$f[]="					#   %% = '%'";
	$f[]="					# e.g. '<%u%%%d> '";
	$f[]="#log_lock_waits = off			# log lock waits >= deadlock_timeout";
	$f[]="#log_statement = 'none'			# none, ddl, mod, all";
	$f[]="#log_replication_commands = off";
	$f[]="#log_temp_files = -1			# log temporary files equal or larger";
	$f[]="					# than the specified size in kilobytes;";
	$f[]="					# -1 disables, 0 logs all temp files";
	$f[]="#log_timezone = 'GMT'";
	$f[]="";
	$f[]="";
	$f[]="# - Process Title -";
	$f[]="";
	$f[]="#cluster_name = ''			# added to process titles if nonempty";
	$f[]="					# (change requires restart)";
	$f[]="#update_process_title = on";
	$f[]="";
	$f[]="";
	$f[]="#------------------------------------------------------------------------------";
	$f[]="# RUNTIME STATISTICS";
	$f[]="#------------------------------------------------------------------------------";
	$f[]="";
	$f[]="# - Query/Index Statistics Collector -";
	$f[]="";
	$f[]="track_activities = on";
	$f[]="track_counts = on";
	$f[]="#track_io_timing = off";
	$f[]="#track_functions = none			# none, pl, all";
	$f[]="#track_activity_query_size = 1024	# (change requires restart)";
	$f[]="#stats_temp_directory = 'pg_stat_tmp'";
	$f[]="";
	$f[]="";
	$f[]="# - Statistics Monitoring -";
	$f[]="";
	$f[]="#log_parser_stats = off";
	$f[]="#log_planner_stats = off";
	$f[]="#log_executor_stats = off";
	$f[]="#log_statement_stats = off";
	
	
	$f[]="";
	$f[]="";
	$f[]="#------------------------------------------------------------------------------";
	$f[]="# AUTOVACUUM PARAMETERS";
	$f[]="#------------------------------------------------------------------------------";
	$f[]="";
	$f[]="autovacuum = on";
	$f[]="log_autovacuum_min_duration = -1";
	$f[]="autovacuum_max_workers = 3";
	$f[]="#autovacuum_naptime = 1min		# time between autovacuum runs";
	$f[]="#autovacuum_vacuum_threshold = 50	# min number of row updates before vacuum";
	$f[]="#autovacuum_analyze_threshold = 50	# min number of row updates before analyze";
	$f[]="#autovacuum_vacuum_scale_factor = 0.2	# fraction of table size before vacuum";
	$f[]="#autovacuum_analyze_scale_factor = 0.1	# fraction of table size before analyze";
	$f[]="#autovacuum_freeze_max_age = 200000000	# maximum XID age before forced vacuum";
	$f[]="#autovacuum_multixact_freeze_max_age = 400000000	# maximum multixact age";
	$f[]="#autovacuum_vacuum_cost_delay = 20ms	# default vacuum cost delay for";
	$f[]="#autovacuum_vacuum_cost_limit = -1	# default vacuum cost limit for";
	$f[]="";
	$f[]="";
	$f[]="#------------------------------------------------------------------------------";
	$f[]="# CLIENT CONNECTION DEFAULTS";
	$f[]="#------------------------------------------------------------------------------";
	$f[]="";
	$f[]="# - Statement Behavior -";
	$f[]="";
	$f[]="#search_path = '\"\$user\", public'	# schema names";
	$f[]="#default_tablespace = ''		# a tablespace name, '' uses the default";
	$f[]="#temp_tablespaces = ''			# a list of tablespace names, '' uses";
	$f[]="					# only default tablespace";
	$f[]="#check_function_bodies = on";
	$f[]="#default_transaction_isolation = 'read committed'";
	$f[]="#default_transaction_read_only = off";
	$f[]="#default_transaction_deferrable = off";
	$f[]="#session_replication_role = 'origin'";
	$f[]="#statement_timeout = 0			# in milliseconds, 0 is disabled";
	$f[]="#lock_timeout = 0			# in milliseconds, 0 is disabled";
	$f[]="#vacuum_freeze_min_age = 50000000";
	$f[]="#vacuum_freeze_table_age = 150000000";
	$f[]="#vacuum_multixact_freeze_min_age = 5000000";
	$f[]="#vacuum_multixact_freeze_table_age = 150000000";
	$f[]="#bytea_output = 'hex'			# hex, escape";
	$f[]="#xmlbinary = 'base64'";
	$f[]="#xmloption = 'content'";
	$f[]="#gin_fuzzy_search_limit = 0";
	$f[]="#gin_pending_list_limit = 4MB";
	$f[]="";
	$f[]="# - Locale and Formatting -";
	$f[]="";
	$f[]="#datestyle = 'iso, mdy'";
	$f[]="#intervalstyle = 'postgres'";
	$f[]="#timezone = 'GMT'";
	$f[]="#timezone_abbreviations = 'Default'     # Select the set of available time zone";
	$f[]="					# abbreviations.  Currently, there are";
	$f[]="					#   Default";
	$f[]="					#   Australia (historical usage)";
	$f[]="					#   India";
	$f[]="					# You can create your own file in";
	$f[]="					# share/timezonesets/.";
	$f[]="#extra_float_digits = 0			# min -15, max 3";
	$f[]="#client_encoding = sql_ascii		# actually, defaults to database";
	$f[]="					# encoding";
	$f[]="";
	$f[]="# These settings are initialized by initdb, but they can be changed.";
	$f[]="#lc_messages = 'C'			# locale for system error message";
	$f[]="					# strings";
	$f[]="#lc_monetary = 'C'			# locale for monetary formatting";
	$f[]="#lc_numeric = 'C'			# locale for number formatting";
	$f[]="#lc_time = 'C'				# locale for time formatting";
	$f[]="";
	$f[]="# default configuration for text search";
	$f[]="#default_text_search_config = 'pg_catalog.simple'";
	$f[]="";
	$f[]="# - Other Defaults -";
	$f[]="";
	$f[]="dynamic_library_path = '/usr/local/ArticaStats/lib'";
	$f[]="#local_preload_libraries = ''";
	$f[]="#session_preload_libraries = ''";
	$f[]="";
	$f[]="";
	$f[]="#------------------------------------------------------------------------------";
	$f[]="# LOCK MANAGEMENT";
	$f[]="#------------------------------------------------------------------------------";
	$f[]="";
	$f[]="#deadlock_timeout = 1s";
	$f[]="#max_locks_per_transaction = 64		# min 10";
	$f[]="					# (change requires restart)";
	$f[]="# Note:  Each lock table slot uses ~270 bytes of shared memory, and there are";
	$f[]="# max_locks_per_transaction * (max_connections + max_prepared_transactions)";
	$f[]="# lock table slots.";
	$f[]="#max_pred_locks_per_transaction = 64	# min 10";
	$f[]="					# (change requires restart)";
	$f[]="";
	$f[]="";
	$f[]="#------------------------------------------------------------------------------";
	$f[]="# VERSION/PLATFORM COMPATIBILITY";
	$f[]="#------------------------------------------------------------------------------";
	$f[]="";
	$f[]="# - Previous PostgreSQL Versions -";
	$f[]="";
	$f[]="#array_nulls = on";
	$f[]="#backslash_quote = safe_encoding	# on, off, or safe_encoding";
	$f[]="#default_with_oids = off";
	$f[]="#escape_string_warning = on";
	$f[]="#lo_compat_privileges = off";
	$f[]="#operator_precedence_warning = off";
	$f[]="#quote_all_identifiers = off";
	$f[]="#sql_inheritance = on";
	$f[]="#standard_conforming_strings = on";
	$f[]="#synchronize_seqscans = on";
	$f[]="";
	$f[]="# - Other Platforms and Clients -";
	$f[]="";
	$f[]="#transform_null_equals = off";
	$f[]="";
	$f[]="";
	$f[]="#------------------------------------------------------------------------------";
	$f[]="# ERROR HANDLING";
	$f[]="#------------------------------------------------------------------------------";
	$f[]="";
	$f[]="#exit_on_error = off			# terminate session on any error?";
	$f[]="#restart_after_crash = on		# reinitialize after backend crash?";
	$f[]="";
	$f[]="";
	$f[]="#------------------------------------------------------------------------------";
	$f[]="# CONFIG FILE INCLUDES";
	$f[]="#------------------------------------------------------------------------------";
	$f[]="";
	$f[]="# These options allow settings to be loaded from files other than the";
	$f[]="# default postgresql.conf.";
	$f[]="";
	$f[]="#include_dir = 'conf.d'			# include files ending in '.conf' from";
	$f[]="					# directory 'conf.d'";
	$f[]="#include_if_exists = 'exists.conf'	# include file only if it exists";
	$f[]="#include = 'special.conf'		# include file";
	$f[]="";
	$f[]="";
	$f[]="#------------------------------------------------------------------------------";
	$f[]="# CUSTOMIZED OPTIONS";
	$f[]="#------------------------------------------------------------------------------";
	$f[]="";
	$f[]="# Add settings for extensions here";
	
	build_progress_restart("{starting}",60);
	@file_put_contents("/home/ArticaStatsDB/postgresql.conf", @implode("\n", $f)."\n");
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /home/ArticaStatsDB/postgresql.conf done\n";}
	$f=array();
	$f[]="@authcomment@";
	$f[]="";
	$f[]="# TYPE  DATABASE        USER            ADDRESS                 METHOD";
	$f[]="";
	$f[]="local   all             all                                     trust";
	$f[]="host    all             all             127.0.0.1/32            trust";
	
	$q=new mysql_squid_builder();
	$Ipclass=new IP();
	$sql="SELECT * FROM influxIPClients";
	$results = $q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		$ipaddr=$ligne["ipaddr"];
		$isServ=intval($ligne["isServ"]);
		if(!$Ipclass->isIPAddressOrRange($ipaddr)){continue;}
		
		if(strpos($ipaddr, "/")==0){
			$ipaddr="$ipaddr/32";
		}
		
		if($isServ==1){
			$f[]="host    all             all             $ipaddr            trust";
		}
		
	}
	
	
	build_progress_restart("{starting}",65);
	@file_put_contents("/home/ArticaStatsDB/pg_hba.conf", @implode("\n", $f)."\n");
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /home/ArticaStatsDB/pg_hba.conf done\n";}
}

function InfluxDbSize(){
	$dir="/home/ArticaStatsDB";
	if(is_link($dir)){$dir=@readlink($dir);}
	$unix=new unix();
	$size=$unix->DIRSIZE_KO_nocache($dir);
	$partition=$unix->DIRPART_INFO($dir);

	$TOT=$partition["TOT"];
	$percent=($size/$TOT)*100;
	$percent=round($percent,3);

	echo "$dir: $size Partition $TOT\n";
	if($GLOBALS["VERBOSE"]){echo "$dir: $size Partition $TOT\n";}
	
	build_progress_restart("{status}: $size...",85);

	$ARRAY["PERCENTAGE"]=$percent;
	$ARRAY["SIZEKB"]=$size;
	$ARRAY["PART"]=$TOT;

	if($GLOBALS["VERBOSE"]){print_r($ARRAY);};
	@unlink("/usr/share/artica-postfix/ressources/logs/web/InfluxDB.state");
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/InfluxDB.state", serialize($ARRAY));
	build_progress_restart("{status}: {done}...",90);
}

function vacuumdb(){
	$unix=new unix();
	

	
	$TimeFile="/usr/local/ArticaStats/bin/vacuumdb.forced.time";
	$pidfile="/usr/local/ArticaStats/bin/vacuumdb.forced.pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){return;}
	
	@file_put_contents($pidfile, getmypid());
	$TimeExec=$unix->file_time_min($TimeFile);
	if($TimeExec<10080){return;}
	
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	
	$postgres=new postgres_sql(true);
	if($postgres->isRemote){return;}
	if(!class_exists("usersMenus")){include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");}
	$users=new usersMenus();
	
	$InfluxAdminRetentionTime=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/InfluxAdminRetentionTime"));
	if($InfluxAdminRetentionTime==0){$InfluxAdminRetentionTime=365;}
	
	if(!$users->CORP_LICENSE){$InfluxAdminRetentionTime=5;}
	$postgres->QUERY_SQL("DELETE FROM access_log WHERE time < NOW() - INTERVAL '$InfluxAdminRetentionTime days'");
	$postgres->QUERY_SQL("DELETE FROM main_size WHERE zdate < NOW() - INTERVAL '$InfluxAdminRetentionTime days'");
	$postgres->QUERY_SQL("DELETE FROM system WHERE zdate < NOW() - INTERVAL '30 days'");
	
	
	$t1=time();
	$NICE=$unix->EXEC_NICE();
	exec("$NICE /usr/local/ArticaStats/bin/vacuumdb -f -v -h /var/run/ArticaStats --dbname=proxydb --username=ArticaStats 2>&1",$results);
	$Took=$unix->distanceOfTimeInWords($t1,time());
	squid_admin_mysql(2, "Indexing Statistics Database took: $Took", @implode("\n", $results),__FILE__,__LINE__);
	InfluxDbSize();
  
}

function install_postgres(){
	$filetime="/etc/artica-postfix/pids/install_postgres.time";
	$pidfile="/etc/artica-postfix/pids/install_postgres.pid";
	
	
	$unix=new unix();
	if($unix->process_exists($unix->get_pid_from_file($pidfile),basename(__FILE__))){die();}
	
	@file_put_contents($pidfile, getmypid());
	
	$TimeExe=$unix->file_time_min($filetime);
	if(!$GLOBALS["FORCE"]){
		if($TimeExe<60){die();}
	}
	
	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
	
	@unlink($filetime);
	@file_put_contents($filetime, time());
	$curl=new ccurl("http://articatech.net/download/postgres-debian7-64-9.6.0.tar.gz");
	$TEMP_DIR=$unix->TEMP_DIR();
	
	if(is_file("$TEMP_DIR/postgres-debian7-64-9.6.0.tar.gz")){@unlink("$TEMP_DIR/postgres-debian7-64-9.6.0.tar.gz");}
	
	if(!$curl->GetFile("$TEMP_DIR/postgres-debian7-64-9.6.0.tar.gz")){
		squid_admin_mysql(0, "Unable to download postgres-debian7-64-9.6.0.tar.gz", $curl->error,__FILE__,__LINE__);
		die();
	}
	
	$tar=$unix->find_program("tar");
	shell_exec("$tar xf $TEMP_DIR/postgres-debian7-64-9.6.0.tar.gz -C /");
	@unlink("$TEMP_DIR/postgres-debian7-64-9.6.0.tar.gz");
	if(!is_file("/usr/local/ArticaStats/bin/postgres")){
		squid_admin_mysql(0, "Failed to extract postgres-debian7-64-9.6.0.tar.gz", $curl->error,__FILE__,__LINE__);
		die();
	}
	
	remove_influx();
	shell_exec("/etc/init.d/postgres restart");
	shell_exec("/etc/init.d/artica-status restart");
	shell_exec("/etc/init.d/squid-tail restart");
	
}


?>