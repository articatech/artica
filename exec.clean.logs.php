<?php
if(is_file("/usr/bin/cgclassify")){if(is_dir("/cgroups/blkio/php")){shell_exec("/usr/bin/cgclassify -g cpu,cpuset,blkio:php ".getmypid());}}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__)."/ressources/class.mysql.syslogs.inc");



if($GLOBALS["VERBOSE"]){
	echo "Starting ".@implode("; ", $argv)."\n";
}

if($argv[1]=='--clean-rotate'){clean_rotate_events();die();}
if($argv[1]=='--clean-space'){clean_space();die();}
if($argv[1]=='--apache'){Clean_apache_logs(true);die();}
if($argv[1]=='--urgency'){UrgencyChecks();exit;}
if($argv[1]=='--logrotatelogs'){logrotatelogs(true);die();}
if($argv[1]=='--squid-store-logs'){die();}
if($argv[1]=='--used-space'){used_space();die();}
if($argv[1]=='--cleandb'){CleanLogsDatabases(true);die();}
if($argv[1]=='--clean-logs'){CleanLOGSF();}
if($argv[1]=='--buidsh'){buidsh();exit;}
if($argv[1]=='--rttsize'){RTTSizes(true);exit;}
if($argv[1]=='--suricata'){CleanSuricataLogs();exit;}
if($argv[1]=='--artica-meta'){ArticaMeta();exit;}


if(!$GLOBALS["FORCE"]){
	if(system_is_overloaded(__FILE__)){
		if($GLOBALS["VERBOSE"]){echo "This system is overloaded, die()\n";}
		writelogs("This system is overloaded, die()",__FUNCTION__,__FILE__,__LINE__);
		die();
	}
}


if($GLOBALS["VERBOSE"]){
	echo "Starting LINE:".__LINE__." ->".@implode("; ", $argv)."\n";
}

if($argv[1]=='--squid-urgency'){squidlogs_urgency(true);die();}
if($argv[1]=='--logs-urgency'){logs_urgency(true);die();}
if($argv[1]=='--clean-tmp1'){Clean_tmp_path(true);}
if($argv[1]=='--clean-tmp2'){Clean_tmp_path(true);logrotatelogs(true);die();}
if($argv[1]=='--clean-tmp'){CleanLogs();logrotatelogs(true);Clean_tmp_path(true);die();}
if($argv[1]=='--clean-sessions'){sessions_clean();Clean_tmp_path(true);logrotatelogs(true);die();}
if($argv[1]=='--clean-install'){CleanOldInstall();die();}
if($argv[1]=='--paths-status'){PathsStatus();die();}
if($argv[1]=='--maillog'){maillog();die();}
if($argv[1]=='--wrong-numbers'){wrong_number();die();}
if($argv[1]=='--DirectoriesSize'){die();}
if($argv[1]=='--cleanbin'){Cleanbin();die();}
if($argv[1]=='--zarafa-locks'){ZarafaLocks();die();}
if($argv[1]=='--squid-caches'){squidClean();die();}
if($argv[1]=='--rotate'){CleanRotatedFiles();die();}
if($argv[1]=='--squid'){squidClean();Clean_tmp_path(true);die();}
if($argv[1]=='--artica-logs'){artica_logs();die();}
if($argv[1]=='--squidLogs'){die();}
if($argv[1]=='--nginx'){nginx();die();}
if($argv[1]=='--attachs'){Clean_attachments();die();}
if($argv[1]=='--access-logs'){die();}




echo "Could not understand your query ???\n";


if(systemMaxOverloaded()){
	writelogs("This system is too many overloaded, die()",__FUNCTION__,__FILE__,__LINE__);
	die();
}


function CleanLOGSF(){
	
	$unix=new unix();
	$Pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$PidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	// /etc/artica-postfix/pids/exec.clean.logs.php.squidClean.time
	
	
	
	if($GLOBALS["VERBOSE"]){echo "Pidfile: $Pidfile\n";}
	if($GLOBALS["VERBOSE"]){echo "PidTime: $PidTime\n";}
	
	$pid=$unix->get_pid_from_file($Pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
	if($GLOBALS["VERBOSE"]){echo "Aborting Task already running pid $pid ".__FUNCTION__."()\n";} return;}
	
	
	@file_put_contents($Pidfile, getmypid());
	
	if(!$GLOBALS["VERBOSE"]){
		if(!$GLOBALS["FORCE"]){
			$time=$unix->file_time_min($PidTime);
			echo "$PidTime = {$time}mn\n";
			if($time<15){echo "Only each 15mn\n";die();}
			@unlink($PidTime);
			@file_put_contents($PidTime, time());
		}
	}
	
	
	Clean_tmp_path(true);
	varlog();
	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	if(is_file($squidbin)){
		squidClean();
	}
	
	CleanSuricataLogs();
	CleanKav4ProxyLogs();
	CleanLogs(true);
	Cleanbin();
	logrotatelogs(true);
	Clean_tmp_path(true);
	artica_logs();
	sessions_clean();
	ArticaMeta(true);
	
	
	if(is_dir("/var/log/influxdb")){
		shell_exec("rm -rf /var/log/influxdb");
	}
	
	
	
	$dir="/usr/share/artica-postfix/ressources/conf/upload/StatsApplianceLogs";
	if(is_dir($dir)){
		$dirs=$unix->dirdir($dir);
		while (list ($directory,$filename) = each ($dirs)){
			StatsApplianceLogs($directory);
	
		}
	}
	
	die();	
	
	
}


function ArticaMeta($aspid=false){
	$unix=new unix();
	
	$Pidfile="/etc/artica-postfix/pids/cleanArticaMetaRepos.pid";
	$PidTime="/etc/artica-postfix/pids/cleanArticaMetaRepos.time";
	
	if(!$aspid){
		$pid=$unix->get_pid_from_file($Pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			if($GLOBALS["VERBOSE"]){echo "Aborting Task already running pid $pid ".__FUNCTION__."()\n";} 
			return;
		}
	}
	@file_put_contents($Pidfile, getmypid());
	
	if(!$GLOBALS["VERBOSE"]){
		$time=$unix->file_time_min($PidTime);
		if($time<240){echo "Only each 240mn\n";die();}
		@unlink($PidTime);
		@file_put_contents($PidTime, time());
	}
	
	$dir="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded";
	if(is_dir($dir)){
		$dirs=$unix->dirdir($dir);
		while (list ($directory,$filename) = each ($dirs)){
			ArticaMeta_uploads($directory);
			
		}
	}
	

	
	
}

function StatsApplianceLogs($directory){
	$unix=new unix();
	if (!$handle = opendir($directory)) {return;}
	while (false !== ($fileZ = readdir($handle))) {
		if($fileZ=="."){continue;}
		if($fileZ==".."){continue;}
		$path="$directory/$fileZ";
		if($unix->file_time_min($path)>240){
			echo "Removing $path\n";
			@unlink($path);
		}
	}
	
}

function ArticaMeta_uploads($directory){
	
	
	$unix=new unix();
	$TargetDir="$directory/SQUID_QUOTASIZE";
	
	
	if(is_dir($TargetDir)){
		if ($handle = opendir($TargetDir)) {
			while (false !== ($fileZ = readdir($handle))) {
				if($fileZ=="."){continue;}
				if($fileZ==".."){continue;}
				$path="$TargetDir/$fileZ";
				if($unix->file_time_min($path)>240){
					echo "Removing $path\n";
					@unlink($path);
				}
			}
		}
	}
	
	
}


function CleanSuricataLogs(){
	if(!is_dir("/var/log/suricata")){return;}
	$unix=new unix();
	$sock=new sockets();
	$LogsRotateDefaultSizeRotation=$sock->GET_INFO("LogsRotateDefaultSizeRotation");
	if(!is_numeric($LogsRotateDefaultSizeRotation)){$LogsRotateDefaultSizeRotation=100;}
	$BackupMaxDaysDir=$sock->GET_INFO("BackupMaxDaysDir");
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
	@mkdir($BackupMaxDaysDir,0755,true);
	$echo=$unix->find_program("echo");
	@unlink("/etc/artica-postfix/pids/CleanSuricataLogs.time");
	@file_put_contents("/etc/artica-postfix/pids/CleanSuricataLogs.time", time());
	
	if ($handle = opendir("/var/log/suricata")) {
		while (false !== ($fileZ = readdir($handle))) {
				if($fileZ=="."){continue;}
				if($fileZ==".."){continue;}
				$path="/var/log/suricata/$fileZ";;
				
				if(preg_match("#unified2\.alert\.#", $fileZ)){
					if($unix->file_time_min($path)>30){@unlink($path);}
					continue;
				}
					
		}
	}
	
	
	$f[]="fast.log";
	$f[]="http.log";
	$f[]="keyword_perf.log";
	$f[]="packet_stats.log";
	$f[]="rule_perf.log";
	$f[]="sid_changes.log";
	$f[]="stats.log";
	$RELOAD=false;
	$pathRange="$BackupMaxDaysDir/".date("Y")."/".date("m")."/" .date("d");
	
	while (list ($index,$filename) = each ($f)){
		$filepath="/var/log/suricata/$filename";
		$size=(@filesize($filepath)/1024)/1000;
		echo "$filepath {$size}MB <> {$LogsRotateDefaultSizeRotation}M\n";
		if($size>$LogsRotateDefaultSizeRotation){
			@mkdir($pathRange,0755,true);
			$unix->compress($filepath, $pathRange."/IDS-".time().".$filename.gz");
			system_admin_mysql(2, "Rotate file $filepath $size>$LogsRotateDefaultSizeRotation", null,__FILE__,__LINE__);
			shell_exec("$echo \"\">$filepath");
			$RELOAD=true;
		}
		
	}
	
	$size=(@filesize("/var/log/suricata/eve.json")/1024)/1000;
	if($size>$LogsRotateDefaultSizeRotation){
		system_admin_mysql(2, "Rotate file /var/log/suricata/eve.json $size>$LogsRotateDefaultSizeRotation", null,__FILE__,__LINE__);
		@mkdir($pathRange,0755,true);
		$unix->compress($filepath, $pathRange."/IDS-".time().".eve.json.gz");
		shell_exec("$echo \"\">/var/log/suricata/eve.json");
		$RELOAD=true;
		
	}

	if($RELOAD){
		shell_exec("/etc/init.d/suricata reload");
	}
	
}



function squidClean($nopid=false){
	
	
	$unix=new unix();
	$Pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$PidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	// /etc/artica-postfix/pids/exec.clean.logs.php.squidClean.time
	
	
	
	if($GLOBALS["VERBOSE"]){echo "Pidfile: $Pidfile\n";}
	if($GLOBALS["VERBOSE"]){echo "PidTime: $PidTime\n";}
	
	if(!$nopid){
		$pid=$unix->get_pid_from_file($Pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			if($GLOBALS["VERBOSE"]){echo "Aborting Task already running pid $pid ".__FUNCTION__."()\n";}
			return;
		}
		@file_put_contents($Pidfile, getmypid());
	}
	if(!$GLOBALS["VERBOSE"]){
		$time=$unix->file_time_min($PidTime);
		if($time<15){echo "Only each 15mn\n";die();}
		@unlink($PidTime);
		@file_put_contents($PidTime, time());
	}
	

	CleanCacheStores(true);
	squidlogs_urgency();
	CleanKav4ProxyLogs();
	CleanSquidClamav();
	
}


function CleanSyslogsStore(){
	$unix=new unix();
	if(!$unix->is_socket("/var/run/syslogdb.sock")){return;}
	
	
}


function init(){
	$sock=new sockets();
	$ArticaMaxLogsSize=$sock->GET_PERFS("ArticaMaxLogsSize");
	if($ArticaMaxLogsSize<1){$ArticaMaxLogsSize=300;}
	$ArticaMaxLogsSize=$ArticaMaxLogsSize*1000;	
	$GLOBALS["ArticaMaxLogsSize"]=$ArticaMaxLogsSize;
	$GLOBALS["logs_cleaning"]=$sock->GET_NOTIFS("logs_cleaning");
	$GLOBALS["MaxTempLogFilesDay"]=$sock->GET_INFO("MaxTempLogFilesDay");
	if($GLOBALS["MaxTempLogFilesDay"]==null){$GLOBALS["MaxTempLogFilesDay"]=5;}
	
	
}

function varlog(){
	$unix=new unix();
	
	echo "******* varlog () *********\n";
	
	$rsync=$unix->find_program("rsync");
	$rm=$unix->find_program("rm");
	if(!is_file($rsync)){
		$unix->DEBIAN_INSTALL_PACKAGE("rsync");
	}
	
	//$GLOBALS["ArticaMaxLogsSize"]=$ArticaMaxLogsSize;
	//$GLOBALS["logs_cleaning"]=$sock->GET_NOTIFS("logs_cleaning");
	//$GLOBALS["MaxTempLogFilesDay"]=$sock->GET_INFO("MaxTempLogFilesDay");
	//if($GLOBALS["MaxTempLogFilesDay"]==null){$GLOBALS["MaxTempLogFilesDay"]=5;}	
	if(!is_numeric($GLOBALS["ArticaMaxLogsSize"])){init();}
	$sock=new sockets();
	$LogsRotateDefaultSizeRotation=$sock->GET_INFO("LogsRotateDefaultSizeRotation");
	if(!is_numeric($LogsRotateDefaultSizeRotation)){$LogsRotateDefaultSizeRotation=100;}
	$LogsDirectoryStorage=$sock->GET_INFO("LogsDirectoryStorage");
	if(trim($LogsDirectoryStorage)==null){$LogsDirectoryStorage="/home/logs-backup";}
	$echo=$unix->find_program("echo");
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
	$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	
	$REMOVE_PHP_SCRIPTS[]="/usr/share/artica-postfix/miniadm.logon.php";
	$REMOVE_PHP_SCRIPTS[]="/usr/share/artica-postfix/miniadm.index.php";
	$REMOVE_PHP_SCRIPTS[]="/usr/share/artica-postfix/miniadm.logoff.php";
	
	while (list ($index,$filepath) = each ($REMOVE_PHP_SCRIPTS)){
		if(is_file($filepath)){@unlink($filepath);}
		
	}
	
	$RESTART_SQUID=false;
	$syslog[]="/var/log/syslog";
	$syslog[]="/var/log/messages";
	$syslog[]="/var/log/daemon.log";
	$syslog[]="/var/log/auth.log";
	$syslog[]="/var/log/kern.log";
	$syslog[]="/var/log/user.log";
	
	$other[]="/var/log/perfs_queue.log";
	$other[]="/var/log/artica-status.log";
	$other[]="/var/log/php.log";
	$other[]="/var/log/artica-postfix/framework.log";
	$other[]="/usr/share/artica-postfix/ressources/logs/php.log";
	$other[]="/var/log/artica-postfix/logrotate.debug";
	$other[]="/var/log/ArticaProc.log";
	$other[]="/var/log/clamav/clamav.log";
	$other[]="/var/log/clamav/clamd.log";
	$other[]="/var/log/clamav/freshclam.log";
	$other[]="/var/log/lighttpd/access.log";
	$other[]="/var/log/lighttpd/apache-access.log";
	$other[]="/var/log/apt/history.log";
	$other[]="/var/log/apt/term.log";
	$other[]="/var/log/redis/redis-server.log";
	$other[]="/var/log/clamav-unofficial-sigs.log";
	$other[]="/var/log/influxdb/influxdb.startup";  
	$other[]="/var/log/influxdb/influxd.log";
	$other[]="/var/log/influxdb/influxd.service.log";
	$other[]="/var/log/clamav-unofficial-sigs/clamav-unofficial-sigs.log";
	$other[]="/var/log/artica-postfix/postfix-logger.debug";
	$other[]="/var/log/artica-postfix/postfix-logger-start.log";
	$other[]="/var/log/lighttpd/apache-hotspot-error.log";
	$other[]="/var/log/suricata/tail.debug";
	$other[]="/var/log/ArticaStatsDB/ArticaStatsDB.log";
	
	if(is_dir("/usr/share/artica-postfix/ressources/ressources")){
		shell_exec("$rm -rf /usr/share/artica-postfix/ressources/ressources");
	}
	
	if(is_file("/var/log/artica-postfix/squid-logger-start.log")){
		$size=(@filesize("/var/log/squid/squidtail.log")/1024)/1000;
		if($size>$LogsRotateDefaultSizeRotation){
			shell_exec("$echo \"\">/var/log/artica-postfix/squid-logger-start.log");
		}
	}
	
	if(is_file("/var/log/artica-postfix/exec.syslog-engine.php.log")){
		shell_exec("$echo \"\">/var/log/artica-postfix/exec.syslog-engine.php.log");
	}
	
	if(is_file("/var/log/squid/squidtail.log")){
		$size=(@filesize("/var/log/squid/squidtail.log")/1024)/1000;
		if($size>$LogsRotateDefaultSizeRotation){
			if(@copy("/var/log/squid/squidtail.log", "$LogRotatePath/squidtail.log.".time())){
				shell_exec("$echo \"\">/var/log/squid/squidtail.log");
				$RESTART_SYSLOG=true;
				$RESTART_SQUID=true;
			}
		}
	}
	
	if(is_file("/var/log/squid/logfile_daemon.debug")){
		$size=(@filesize("/var/log/squid/logfile_daemon.debug")/1024)/1000;
		if($size>$LogsRotateDefaultSizeRotation){
		shell_exec("$echo \"\">/var/log/squid/logfile_daemon.debug");
		$RESTART_SYSLOG=true;
		}
	}
	
	if(is_file("/var/log/squid/ext_time_quota_acl.log")){
		$size=(@filesize("/var/log/squid/ext_time_quota_acl.log")/1024)/1000;
		if($size>$LogsRotateDefaultSizeRotation){
			shell_exec("$echo \"\">/var/log/squid/ext_time_quota_acl.log");
		}
	}
	
	
	$RESTART_SYSLOG=false;
	$checks=array();
	while (list ($index,$filepath) = each ($syslog)){
		$size=(@filesize($filepath)/1024)/1000;
		echo "$filepath {$size}MB <> {$LogsRotateDefaultSizeRotation}M\n";
		if($size>$LogsRotateDefaultSizeRotation){
			$nextfile="$LogsDirectoryStorage/".basename($filepath)."-".time();
			if(!@copy($filepath, $nextfile)){ @unlink($nextfile); continue;}
			$checks[]=$nextfile;
			shell_exec("$echo \"\" >$filepath");
			$RESTART_SYSLOG=true;
		}
	}
	
	if($RESTART_SQUID){
		$squidbin=$unix->LOCATE_SQUID_BIN();
		if(is_file($squidbin)){
			if($SQUIDEnable==1){
				$restart_squid_stamp="/etc/artica-postfix/pids/".basename(__FILE__).".".__LINE__;
				if($unix->file_time_min($restart_squid_stamp)>240){
					system_admin_mysql(1, "Ask to rotate logs after cleaning log files", null,__FILE__,__LINE__);
					shell_exec("$squidbin -f /etc/squid3/squid.conf -k reconfigure");
					shell_exec("$squidbin -f /etc/squid3/squid.conf -k rotate");
					@unlink($restart_squid_stamp);
					@file_put_contents($restart_squid_stamp, time());
				}
			}
		}
		
	}
	
	
	if($RESTART_SYSLOG){
		squid_admin_mysql(1, "Restarting Syslog after a rotation", null,__FILE__,__LINE__);
		$unix->RESTART_SYSLOG();
	}
	
	
	while (list ($index,$filepath) = each ($other)){
		if(!is_file($filepath)){continue;}
		$size=(@filesize($filepath)/1024)/1000;
		echo "$filepath {$size}MB <> {$LogsRotateDefaultSizeRotation}M\n";
		if($size>50){
			shell_exec("$echo \"\" >$filepath");
		}
 }	
	
	
	$q=new mysql_storelogs();
	echo " * * * $LogsDirectoryStorage * * *\n";
	if(is_dir($LogsDirectoryStorage)){
		if ($handle = opendir($LogsDirectoryStorage)) {
			while (false !== ($fileZ = readdir($handle))) {
				if($fileZ=="."){continue;}
				if($fileZ==".."){continue;}
				$filename="$LogsDirectoryStorage/$fileZ";
				$q->events("Injecting $filename",__FUNCTION__,__LINE__);
				$q->InjectFile($filename,null);
					
			}
		}
	}
	echo " * * * /var/log * * *\n";
	if ($handle = opendir("/var/log")) {
		while (false !== ($fileZ = readdir($handle))) {
			if($fileZ=="."){continue;}
			if($fileZ==".."){continue;}
			$path="/var/log/$fileZ";
			if(is_dir($path)){continue;}
			if(!preg_match("#artica-status\.log\.[0-9]+$#", $fileZ)){continue;}
			if($unix->file_time_min($path)>1440){@unlink($path);}
		}
	}
	
	echo " * * * /var/log/influxdb * * *\n";
	if(is_dir("/var/log/influxdb")){
		if ($handle = opendir("/var/log/influxdb")) {
			while (false !== ($fileZ = readdir($handle))) {
				if($fileZ=="."){continue;}
				if($fileZ==".."){continue;}
				$path="/var/log/influxdb/$fileZ";
				if(is_dir($path)){continue;}
				if(preg_match("#\.log\.[0-9]+$#", $fileZ)){
					@unlink($path);
					continue;
				}
				
			}
		}
	}
	echo " * * * /var/log/squid * * *\n";
	if(is_dir("/var/log/squid")){
		if ($handle = opendir("/var/log/squid")) {
			while (false !== ($fileZ = readdir($handle))) {
				if($fileZ=="."){continue;}
				if($fileZ==".."){continue;}
				$path="/var/log/squid/$fileZ";
				if(is_dir($path)){continue;}
				if(!preg_match("#ufdbguardd\.log\.[0-9]+$#", $fileZ)){continue;}
				echo "$path remove \n";
				@unlink($path);continue;
			}
		}	
	}
	
	
	
	
	
	
	
	$BackupMaxDays=$sock->GET_INFO("BackupMaxDays");
	$BackupMaxDaysDir=$sock->GET_INFO("BackupMaxDaysDir");
	$BackupMaxDaysAccess=$sock->GET_INFO("BackupMaxDaysAccess");
	if(!is_numeric($BackupMaxDaysAccess)){$BackupMaxDaysAccess=365;}
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	if(!is_numeric($BackupMaxDays)){$BackupMaxDays=30;}
	$BackupMaxHours=$BackupMaxDays*24;
	$BackupMaxMins=$BackupMaxHours*60;
	
	$BackupMaxDaysAccess=$BackupMaxDaysAccess*24;
	$BackupMaxDaysAccess=$BackupMaxDaysAccess*60;
	
	
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
	
	
	
	if(is_dir($BackupMaxDaysDir)){
		if ($handle = opendir($BackupMaxDaysDir)) { 
			while (false !== ($fileZ = readdir($handle))) {
				if($fileZ=="."){continue;}
				if($fileZ==".."){continue;}
				$filename="$BackupMaxDaysDir/$fileZ";
				$mins=$unix->file_time_min($filename);
				
				if(preg_match("#^access\.#", $filename)){
					if($mins>=$BackupMaxDaysAccess){
						$q->events("Removing $filename $mins>=BackupMaxDaysAccess:$BackupMaxDaysAccess",__FUNCTION__,__LINE__);
						echo "Removing $filename\n";
						@unlink($filename);
					}
					continue;
				}
				
				
				if($GLOBALS["VERBOSE"]){echo "$filename = {$mins}Mn\n";}
				if($mins>=$BackupMaxMins){
					$q->events("Removing $filename $mins>=BackupMaxMins:$BackupMaxMins",__FUNCTION__,__LINE__);
					echo "Removing $filename\n";
					@unlink($filename);
				}
				$q->events("Injecting $filename",__FUNCTION__,__LINE__);
				$q->InjectFile($filename);
			}
		
		}
	}	
	
	if(is_dir($LogRotatePath)){
		if ($handle = opendir($LogRotatePath)) {
			while (false !== ($fileZ = readdir($handle))) {
				if($fileZ=="."){continue;}
				if($fileZ==".."){continue;}
				$filename="$LogRotatePath/$fileZ";
				$mins=$unix->file_time_min($filename);
				if($GLOBALS["VERBOSE"]){echo "$filename = {$mins}Mn\n";}
				
				if($mins>=$BackupMaxMins){
					$q->events("Removing $filename $mins>=BackupMaxMins:$BackupMaxMins",__FUNCTION__,__LINE__);
					echo "Removing $filename\n";
					@unlink($filename);
				}
				$q->events("Injecting $filename",__FUNCTION__,__LINE__);
				$q->InjectFile($filename);
			}
	
		}
	}	
	
	$LogRotatePath=$LogRotatePath."/work";
	if(is_dir($LogRotatePath)){
		
		if ($handle = opendir($LogRotatePath)) {
			while (false !== ($fileZ = readdir($handle))) {
				if($fileZ=="."){continue;}
				if($fileZ==".."){continue;}
				$filename="$LogRotatePath/$fileZ";
				$filemd5=md5_file($filename);
				
				if(isset($ARRAYMD[$filemd5])){
					@unlink($filename);
					continue;
				}
				
				$ARRAYMD[$filemd5]=$filename;
			}
		}
		
		
		
		if ($handle = opendir($LogRotatePath)) {
			while (false !== ($fileZ = readdir($handle))) {
				if($fileZ=="."){continue;}
				if($fileZ==".."){continue;}
				$filename="$LogRotatePath/$fileZ";
				$mins=$unix->file_time_min($filename);
				if($GLOBALS["VERBOSE"]){echo "$filename = {$mins}Mn\n";}
				
				if(preg_match("#^access\.#", $filename)){
					if($mins>=$BackupMaxDaysAccess){
						echo "Removing $filename\n";
						$q->events("Removing $filename $mins>=BackupMaxDaysAccess:$BackupMaxDaysAccess",__FUNCTION__,__LINE__);
						@unlink($filename);
						continue;
					}
					$q->events("Injecting $filename",__FUNCTION__,__LINE__);
					$q->InjectFile($filename);
					continue;
				}
				
				
				if($mins>=$BackupMaxMins){
					$q->events("Removing $filename $mins>=BackupMaxMins:$BackupMaxMins",__FUNCTION__,__LINE__);
					echo "Removing $filename\n";
					@unlink($filename);
				}
				$q->events("Injecting $filename",__FUNCTION__,__LINE__);
				$q->InjectFile($filename);
			}
	
		}
	}		
	
}



function CleanCacheStores($aspid=false){
	$unix=new unix();
	$TimeFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$Pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	
	if(!$GLOBALS["FORCE"]){
		$timefile=$unix->file_time_min($TimeFile);
		if($timefile<60){return;}
	}
	if($aspid){
		$pid=$unix->get_pid_from_file($Pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){return;}
		@file_put_contents($Pidfile, getmypid());
	}
	@unlink($TimeFile);
	@file_put_contents($TimeFile, getmypid());
	
	$users=new usersMenus();
	if(!$users->SQUID_INSTALLED){if($GLOBALS["VERBOSE"]){echo "Squid is not installed...\n";}return;}
	$rm=$unix->find_program("rm");
	$f=file("/etc/squid3/squid.conf");
	while (list ($index, $line) = each ($f)){
		if(preg_match("#^cache_dir\s+(.*?)\s+(.+?)\s+#" , $line,$re)){
			if($GLOBALS["VERBOSE"]){echo "Found Cache `{$re[2]}`\n";}
			$effective[$re[2]]=true;
		}
		
	}
	$dirs=$unix->dirdir("/var/cache");
	while (list ($directory, $line) = each ($dirs)){
		if(isset($effective[$directory])){
			if($GLOBALS["VERBOSE"]){echo "Checking Directory $directory is used by squid...\n";}
			continue;}
		$dirname=basename($directory);
		if($GLOBALS["VERBOSE"]){echo "Checking Directory [$directory] => $dirname\n";}
		if(preg_match("#^squid.*#", $dirname)){
			if($GLOBALS["VERBOSE"]){echo "Removing dir `$dirname`\n";}
			system_admin_events("Old squid cache directory $dirname will be deleted", __FUNCTION__, __FILE__, __LINE__, "clean");
			squid_admin_mysql(2,"Deleting Old squid cache directory $dirname","Old squid cache directory $dirname will be deleted\nRemoving: `$directory`");
			
			shell_exec("$rm -rf $directory >/dev/null 2>&1");
		}
	}
	
	
}





	
function squidlogs_urgency(){
	$unix=new unix();
	$BaseWorkDir="/home/artica/squidlogs_urgency";
	
	if(!is_dir($BaseWorkDir)){return;}
	
	$sock=new sockets();
	$EnableSargGenerator=intval($sock->GET_INFO("EnableSargGenerator"));
	
	if (!$handle = opendir($BaseWorkDir)) {return;}
	
	while (false !== ($fileZ = readdir($handle))) {
		if($fileZ=="."){continue;}
		if($fileZ==".."){continue;}
		$filename="$BaseWorkDir/$fileZ";
		$mysql_storelogs=new mysql_storelogs();
		$mysql_storelogs->ROTATE_ACCESS_TOMYSQL($filename);
	}
	
}

function CleanSquidClamav(){
	$unix=new unix();
	if(!is_dir("/home/squid")){return;}
	if (!$handle = opendir("/home/squid")) {return;}
	
	while (false !== ($fileZ = readdir($handle))) {
		if($fileZ=="."){continue;}
		if($fileZ==".."){continue;}
		if(!preg_match("#clamav_tmp#", $fileZ)){continue;}
		$filename="/home/squid/$fileZ";
		$time=$unix->file_time_min($filename);
		if($time>120){@unlink($filename);}
	}
	
}

function LogRotateTimeAndSize($BaseWorkDir){
	if(!is_dir($BaseWorkDir)){return;}
	$unix=new unix();
	$sock=new sockets();
	$php5=$unix->LOCATE_PHP5_BIN();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	$echo=$unix->find_program("echo");
	$syslog=new mysql_storelogs();
	$LogsRotateDefaultSizeRotation=$sock->GET_INFO("LogsRotateDefaultSizeRotation");
	$SquidRotateOnlySchedule=intval($sock->GET_INFO("SquidRotateOnlySchedule"));
	if(!is_numeric($LogsRotateDefaultSizeRotation)){$LogsRotateDefaultSizeRotation=100;}

	$syslog=new mysql_storelogs();
	
	if($BaseWorkDir=="/var/log/squid"){return; }
	
	if (!$handle = opendir($BaseWorkDir)) {return;}
	
	
	
	while (false !== ($fileZ = readdir($handle))) {
		if($fileZ=="."){continue;}
		if($fileZ==".."){continue;}
		$filename="$BaseWorkDir/$fileZ";
		if(is_dir($filename)){continue;}
		$size=$unix->file_size($filename);
		$sizePHP=round(unix_file_size($filename)/1024);
		$size=round(($size/1024)/1000,2);
		
	
	
		if($GLOBALS["VERBOSE"]){echo "Found file: $filename {$size}M !== {$LogsRotateDefaultSizeRotation}M\n";}
	
		if($fileZ=="cache.log"){
			if($size>$LogsRotateDefaultSizeRotation){
				if($SquidRotateOnlySchedule==0){
					exec("$php5 /usr/share/artica-postfix/exec.squid.php --rotate --force 2>&1",$results);
					squid_admin_mysql(2, "$fileZ {$size}M Exceed {$LogsRotateDefaultSizeRotation}M", "Artica flush a log rotation\n".@implode("\n", $results));
					return;
				}
			}
			continue;
		}
		if($fileZ=="external-acl.log"){continue;}
		if($fileZ=="ufdbguardd.log"){continue;}
		
		
		if($fileZ=="access.log"){
			if($size>$LogsRotateDefaultSizeRotation){
				if($SquidRotateOnlySchedule==0){
					$syslog->events("$php5 /usr/share/artica-postfix/exec.squid.php --rotate --smooth",__FUNCTION__,__LINE__);
					exec("$php5 /usr/share/artica-postfix/exec.squid.php --rotate --smooth 2>&1",$results);
					rotate_admin_events("$fileZ {$size}M Exceed {$LogsRotateDefaultSizeRotation}M perform a Proxy log rotation",__FUNCTION__,__FILE__,__LINE__,"proxy",$GLOBALS["SCHEDULE_ID"]);
					squid_admin_mysql(2, "$fileZ {$size}M Exceed {$LogsRotateDefaultSizeRotation}M", "Artica flush a log rotation\n".@implode("\n", $results));
					return;
				}
			}
		}
		if($fileZ=="squidtail.log"){
			if($size>$LogsRotateDefaultSizeRotation){
				if($SquidRotateOnlySchedule==0){
					$syslog->events("$php5 /usr/share/artica-postfix/exec.squid.php --rotate --smooth",__FUNCTION__,__LINE__);
					exec("$php5 /usr/share/artica-postfix/exec.squid.php --rotate --smooth 2>&1",$results);
					rotate_admin_events("$fileZ {$size}M Exceed {$LogsRotateDefaultSizeRotation}M perform a Proxy log rotation",__FUNCTION__,__FILE__,__LINE__,"proxy",$GLOBALS["SCHEDULE_ID"]);
					squid_admin_mysql(2, "$fileZ {$size}M Exceed {$LogsRotateDefaultSizeRotation}M", "Artica flush a log rotation\n".@implode("\n", $results));
					return;
				}
			}
		}
		
		
		
		if($fileZ=="netdb.state"){continue;}
		$time=$unix->file_time_min($filename);
		$filedate=date('Y-m-d H:i:s',filemtime($filename));
		
		if(preg_match("#access\.log[0-9]+$#", $filename)){
			continue;
		}		
	
		if(preg_match("#access\.log\.[0-9]+$#", $filename)){
			continue;
		}
	
		if(preg_match("#sarg\.log\.[0-9]+$#", $filename)){
			@mkdir("/home/squid/sarg_logs");
			$syslog->events("copy $filename -> /home/squid/sarg_logs/".basename($filename).".".filemtime($filename),__FUNCTION__,__LINE__);
			if(@copy($filename, "/home/squid/sarg_logs/".basename($filename).".".filemtime($filename))){
				@unlink($filename);
			}
	
			continue;
		}
	
	
		$extension = pathinfo($filename, PATHINFO_EXTENSION);
	
		if($GLOBALS["VERBOSE"]){echo "Analyze $filename ($extension) $filedate\n";}
	
		if(is_numeric($extension)){
			$syslog->events("ROTATE_TOMYSQL $filename",__FUNCTION__,__LINE__);
			$syslog->ROTATE_TOMYSQL($filename, $filedate);
			continue;
		}
		if($extension=="gz"){
			$syslog->events("ROTATE_TOMYSQL $filename",__FUNCTION__,__LINE__);
			$syslog->ROTATE_TOMYSQL($filename, $filedate);
			continue;
		}
	
		if($extension=="state"){continue;}
	
		if($extension=="bz2"){
			$syslog->events("ROTATE_TOMYSQL $filename",__FUNCTION__,__LINE__);
			$syslog->ROTATE_TOMYSQL($filename, $filedate);
			continue;
		}
	
			
		$time=$unix->file_time_min($filename);
		echo "$filename {$time}Mn\n";
		$syslog->events("ROTATE_TOMYSQL $filename",__FUNCTION__,__LINE__);
		$syslog->ROTATE_TOMYSQL($filename, $filedate);
	
	}
	
	
		
}





function ZarafaLocks(){
	$unix=new unix();
	$tmpdir=$unix->TEMP_DIR();
	if ($handle = opendir("/tmp")) {
			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != "..") {
					$path="/tmp/$file";
					if(preg_match("#-write\.lock$#", $file)){
						$time=$unix->file_time_min($path);
						if($GLOBALS["VERBOSE"]){echo "$path -> {$time}Mn > 300 ?\n";}
						if($time>300){if($GLOBALS["VERBOSE"]){echo "$path -> KILLED\n";}@unlink($path);}
						continue;
					}
					
				if(preg_match("#-commit\.lock$#", $file)){
						$time=$unix->file_time_min($path);
						if($GLOBALS["VERBOSE"]){echo "$path -> {$time}Mn > 300 ?\n";}
						if($time>300){if($GLOBALS["VERBOSE"]){echo "$path -> KILLED\n";}@unlink($path);}
						continue;
					}				
			}
		}
	}

	if ($handle = opendir($tmpdir)) {
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				$path="$tmpdir/$file";
				if(preg_match("#-write\.lock$#", $file)){
					$time=$unix->file_time_min($path);
					if($GLOBALS["VERBOSE"]){echo "$path -> {$time}Mn > 300 ?\n";}
					if($time>300){if($GLOBALS["VERBOSE"]){echo "$path -> KILLED\n";}@unlink($path);}
					continue;
				}
					
				if(preg_match("#-commit\.lock$#", $file)){
					$time=$unix->file_time_min($path);
					if($GLOBALS["VERBOSE"]){echo "$path -> {$time}Mn > 300 ?\n";}
					if($time>300){if($GLOBALS["VERBOSE"]){echo "$path -> KILLED\n";}@unlink($path);}
					continue;
				}
			}
		}
	}
}


function maillog(){
	init();
	foreach (glob("/usr/share/artica-postfix/*") as $filename) {
		if(is_numeric(basename($filename))){@unlink($filename);}
		if(preg_match("#_[0-9]+_tmp#", $filename)){@unlink($filename);continue;}
		if(is_numeric(basename($filename))){@unlink($filename);continue;}
	}
	
	foreach (glob("/usr/share/artica-postfix/framework/*") as $filename) {
		if(is_numeric(basename($filename))){@unlink($filename);}
		if(preg_match("#_[0-9]+_tmp#", $filename)){@unlink($filename);continue;}
		if(is_numeric(basename($filename))){@unlink($filename);continue;}
	}

	
	

}

function artica_logs(){
	$unix=new unix();
	if ($handle = opendir($Dir)) {
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				$path="$Dir/$file";
				if(is_dir($path)){continue;}
				if(preg_match("#_[0-9]+_tmp#", $file)){@unlink($path);continue;}
				
				
				if(!is_file($path)){if($GLOBALS["VERBOSE"]){echo "$path, no file...\n";}continue;}
					if(preg_match("#artica-update-[0-9\-]+\.debug#", $file)){
						$timefile=$unix->file_time_min($path);
						if($timefile>2880){
							$size=@filesize($path)/1024;
							$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
							$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;
							@unlink($path);
							continue;
							
						}
					}
					
					
					if(preg_match("#backup-starter-[0-9\-]+\.log#", $file)){
						$timefile=$unix->file_time_min($path);
						if($timefile>2880){
							$size=@filesize($path)/1024;
							$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
							$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;
							@unlink($path);
							continue;
							
						}
					}
					
					
					if(preg_match("#exec..*?\.[0-9\-]+\.log#", $file)){
						$timefile=$unix->file_time_min($path);
						if($timefile>2880){
							$size=@filesize($path)/1024;
							$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
							$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;
							@unlink($path);
							continue;
							
						}
					}
					if(preg_match("#(process1|artica).*?\.(debug|log)#", $file)){
						$timefile=$unix->file_time_min($path);
						if($timefile>2880){
							$size=@filesize($path)/1024;
							$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
							$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;
							@unlink($path);
							continue;
								
						}
					}					
					
					
					
					
				}
			}
	
	}	
	
}


function CleanAllindDir($DirPath,$maxtime=180){
	if(!is_dir($DirPath)){return;}
	$unix=new unix();
	if (!$handle = opendir($DirPath)) {return;}
	while (false !== ($file = readdir($handle))) {
		if ($file == "."){continue;}
		if ($file == ".."){continue;}		
		if(is_dir($DirPath)){continue;}
		$path="$DirPath/$file";
		if(preg_match("#_[0-9]+_tmp#", $file)){@unlink($path);continue;}
		if(preg_match("#^krb5#", $file)){continue;}
		if(preg_match("#\.winbindd#", $file)){continue;}
		if($unix->is_socket($path)){continue;}
		$time=$unix->file_time_min($path);
		if($time<$maxtime){continue;}
		$size=@filesize($path)/1024;
		$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
		$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;
		if($GLOBALS["VERBOSE"]){echo "$path - > DELETE\n";}
		@unlink($path);

	}
}

function logs_urgency($aspid=false){
	$unix=new unix();
	
	
	$TimeFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$Pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if($GLOBALS["VERBOSE"]){echo "TimeFile: $TimeFile\n";}
	if($GLOBALS["VERBOSE"]){echo "Pidfile: $Pidfile\n";}
	
	if(!$GLOBALS["FORCE"]){
		$timefile=$unix->file_time_min($TimeFile);
		if($timefile<60){return;}
	}
	
	
	if($aspid){
		$pid=$unix->get_pid_from_file($Pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$timeprc=$unix->PROCCESS_TIME_MIN($pid);
			if($timeprc>60){
				$unix->KILL_PROCESS($pid,9);
			}else{
				if($GLOBALS["VERBOSE"]){echo "Aborting Task already running pid $pid ".__FUNCTION__."()\n";}
				return;
			}
		}
		@file_put_contents($Pidfile, getmypid());
	}
	
	
	$df=$unix->find_program("df");
	$php=$unix->LOCATE_PHP5_BIN();
	exec("$df -h /var/log 2>&1",$results);
	
	while (list ($index, $line) = each ($results) ){
		if(!preg_match("#^(.+?)\s+([0-9\.,]+)([A-Z])\s+([0-9\.,]+).*?\s+([0-9\.,]+)%\s+#", $line,$re)){continue;}
		$purc=$re[5];
		break;
	}
	
	if($purc<100){return;}
	$echo=$unix->find_program("echo");
	$logf["artica-router.log"]=true;
	$logf["artica-smtp.log"]=true;
	$logf["auth.log"]=true;
	$logf["daemon.log"]=true;
	$logf["debug"]=true;
	$logf["dpkg.log"]=true;
	$logf["fetchmail.log"]=true;
	$logf["kern.log"]=true;
	$logf["mail.err"]=true;
	$logf["mail.log"]=true;
	$logf["mail.warn"]=true;
	$logf["messages"]=true;
	
	$logf["syslog"]=true;
	$logf["user.log"]=true;
	$logf["lighttpd/access.log"]=true;
	$logf["squid/store.log"]=true;
	$logf["apache2/unix-varrunnginx-authenticator.sock/nginx.access.log"]=true;
	$logf["apache2/unix-varrunnginx-authenticator.sock/nginx.error.log"]=true;
	$logf["artica-postfix/framework.log"]=true;
	$logf["samba/log.winbindd"]=true;
	$logf["samba/log.winbindd.old"]=true;
	$logf["clamav/clamav.log"]=true;
	$logf["clamav/clamd.log"]=true;
	$logf["clamav/freshclam.log"]=true;

	$sock=new sockets();
	$BaseWorkDirUrgency="/home/artica/squidlogs_urgency";
	$LogsRotateDefaultSizeRotation=$sock->GET_INFO("LogsRotateDefaultSizeRotation");
	if(!is_numeric($LogsRotateDefaultSizeRotation)){$LogsRotateDefaultSizeRotation=100;}
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
	$LogRotatePath="$LogRotatePath/work/cache_logs";
	
	
	while (list ($filname, $line) = each ($logf) ){
		$path="/var/log/$filname";
		if(!is_file($path)){continue;}
		shell_exec("$echo \" \" > $path 2>&1");
	}
	

	
	
}

function buidsh(){
	
	$unix=new unix();
	$array=array("a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","x","y","z");
	$f=$unix->dirdir("/var/log/artica-postfix");
	
	$z[]="rm -rf /var/log/artica-postfix/* >/dev/null 2>&1";
	while (list ($dir, $line) = each ($f) ){
		reset($array);
		$z[]="echo \"Removing content of $dir\"";
		while (list ($a, $b) = each ($array) ){
			$z[]="rm -rf $dir/$b* >/dev/null 2>&1";
		}
		
		for($i=0;$i<10;$i++){
			$z[]="rm -rf $dir/$i* >/dev/null 2>&1";
		}
	}
	
	echo @implode("\n", $z);
}


function Clean_attachments(){
	$unix=new unix();
	
	CleanAllindDir("/opt/artica/share/www/attachments");
	CleanAllindDir("/var/virusmail");
	
}

function Clean_apache_logs(){
	$sock=new sockets();
	$LogsRotateRemoveApacheMaxSize=$sock->GET_INFO("LogsRotateRemoveApacheMaxSize");
	if(!is_numeric($LogsRotateRemoveApacheMaxSize)){$LogsRotateRemoveApacheMaxSize=50;}
	$LogsRotateDefaultSizeRotation=$sock->GET_INFO("LogsRotateDefaultSizeRotation");
	if(!is_numeric($LogsRotateDefaultSizeRotation)){$LogsRotateDefaultSizeRotation=100;}
	$unix=new unix();
	$dirs=$unix->dirdir("/var/log/apache2");
	$echo=$unix->find_program("echo");
	$FILZ["access.log"]=true;  
	$FILZ["error.log"]=true;    
	$FILZ["nginx.access.log"]=true;    
	$FILZ["nginx.error.log"]=true;    
	$FILZ["php.log"]=true;  

	
	$syslog=new mysql_storelogs();
	
	while (list ($dirpath,$none) = each ($dirs) ){
		reset($FILZ);
		while (list ($filename,$none2) = each ($FILZ) ){
			$filepath="$dirpath/$filename";
			if(!is_file($filepath)){continue;}
			if(is_dir($filepath)){continue;}
			$timef=$unix->file_time_min($filepath);
			if($GLOBALS["VERBOSE"]){echo "$filepath {$timef}Mn\n";}
			if($timef>2880){@unlink($filepath);continue;}
			$size=@filesize($filepath);
			$size=$size/1024;
			$size=round($size/1000,2);
			if($GLOBALS["VERBOSE"]){echo "$filepath {$size}MB\n";}
			if($LogsRotateRemoveApacheMaxSize>0){
				if($size>$LogsRotateRemoveApacheMaxSize){ 
					if($GLOBALS["VERBOSE"]){echo "$filepath -> clean\n";}
					shell_exec("$echo \" \" >$filepath");
					continue; 
				}
			}
			
			if($size>$LogsRotateDefaultSizeRotation){
				if($GLOBALS["VERBOSE"]){echo "$filepath -> rotate\n";}
				$syslog->ROTATE_TOMYSQL($filepath);
				continue;
			}
			if($GLOBALS["VERBOSE"]){echo "$filepath -> NOTHING\n";}		
		}
		
	}
	
}



function Clean_tmp_path($aspid=false){
	$unix=new unix();
	
	$PidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	// /etc/artica-postfix/pids/exec.clean.logs.php.Clean_tmp_path.time
	if($GLOBALS["VERBOSE"]){echo "PidTime: $PidTime\n";}
	
	if(!$GLOBALS["VERBOSE"]){
		$timed=$unix->file_time_min($PidTime);
		if($timed<60){return;}
	}
	
	if($aspid){
		$pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidpath);
		if($unix->process_exists($pid)){
			$unix->events(basename(__FILE__).":: ".__FUNCTION__." Already process $pid running.. Aborting");
			return;
		}
		
		@file_put_contents($pidpath, getmypid());
	}
	
	$sock=new sockets();
	$EnableRemoteSyslogStatsAppliance=$sock->GET_INFO("EnableRemoteSyslogStatsAppliance");
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");	
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	if($DisableArticaProxyStatistics==1){$EnableRemoteStatisticsAppliance=1;}
	if($EnableRemoteSyslogStatsAppliance==1){$EnableRemoteSyslogStatsAppliance=1;}
	if($EnableRemoteSyslogStatsAppliance==1){clean_squid_users_size(true);}
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
	
	@unlink($PidTime);
	@file_put_contents($PidTime, time());
	
	
	CleanAllindDir("/etc-artica-postfix/artica-postfix/cron.1",2880);
	CleanAllindDir("/tmp",2880);
	CleanAllindDir("/var/lib/c_icap/temporary",2880);
	CleanAllindDir("/var/log/clamav",2880);
	CleanAllindDir("/etc/artica-postfix/artica-postfix/cron.2",2880);
	CleanAllindDir("/etc/artica-postfix/artica-postfix/croned.1",2880);
	CleanAllindDir("/etc/artica-postfix/artica-postfix/croned.2",2880);
	CleanAllindDir("/etc/artica-postfix/artica-postfix/pids",2880);
	CleanAllindDir("/etc/artica-postfix/artica-postfix/pids.3",2880);
	CleanAllindDir("/etc/artica-postfix/artica-postfix/loadavg.queue",750);
	CleanAllindDir("/var/log/artica-postfix/sys_mem",750);
	CleanAllindDir("/var/log/artica-postfix/sys_loadavg",750);
	CleanAllindDir("/var/log/artica-postfix/events",240);
	CleanAllindDir("/var/log/artica-postfix/postqueue",240);
	CleanAllindDir("/var/log/artica-postfix/system_failover_events",240);
	CleanAllindDir("/var/log/artica-postfix/squid-brut",2880);
	CleanAllindDir("/var/log/artica-postfix/ufdbguard_admin_events",240);
	CleanAllindDir("/usr/share/artica-postfix/ressources/logs",180);
	CleanAllindDir("/usr/share/artica-postfix/ressources/logs/web",180);
	CleanAllindDir("/usr/share/artica-postfix/ressources/conf/upload",240);
	CleanAllindDir("/usr/share/artica-postfix/ressources/support",60);
	CleanAllindDir("/home/nginx/logsWork",7200);
	CleanAllindDir("/opt/artica/ldap-backup",7200);
	CleanAllindDir("/var/log/artica-wifidog",2880);
	
	
	ZarafaLocks();
	clean_rotate_events();
	CleanCacheStores();
	CleanRotatedFiles();
	CleanLogsDatabases();
	sessions_clean();
	clean_artica_workfiles("/var/log/artica-postfix/Postfix-sql-error");
	clean_artica_workfiles("/usr/share/artica-postfix/ressources/conf/upload",60);
	clean_artica_workfiles("/usr/share/artica-postfix/ressources/logs/web",240);
	clean_artica_workfiles("/usr/share/artica-postfix/ressources/logs/web/cache",240);
	clean_artica_workfiles("/usr/share/artica-postfix/ressources/logs/web/help",240);
	clean_artica_workfiles("/usr/share/artica-postfix/ressources/logs/web/queue/sessions",240);
	clean_artica_workfiles("/usr/share/artica-postfix/ressources/logs/categorize-tables",240);
	clean_artica_workfiles("/usr/share/artica-postfix/ressources/logs/jGrowl",240);
	clean_artica_workfiles("/usr/share/artica-postfix/ressources/support",240);
	clean_artica_workfiles("/usr/share/artica-postfix/ressources/logs",240);
	clean_squid_users_size(true);
	artica_logs();
	
	
	
	$syslog_sql=new mysql_storelogs();
	$LogsDirectoryStorage=$sock->GET_INFO("LogsDirectoryStorage");
	if(trim($LogsDirectoryStorage)==null){$LogsDirectoryStorage="/home/logs-backup";}
	if(is_dir($LogsDirectoryStorage)){
		if ($handle = opendir($LogsDirectoryStorage)){
			while (false !== ($file = readdir($handle))) {
				if($file=="."){continue;}
				if($file==".."){continue;}
				$path="$LogsDirectoryStorage/$file";
				if(is_dir($path)){continue;}
				if($GLOBALS["VERBOSE"]){echo "$path -> INJECT\n";}
				if(!$syslog_sql->ROTATE_ACCESS_TOMYSQL($path)){continue;}
				@unlink($path);
			}
		}
	}
	
	if(is_dir("/etc/artica-postfix/pids")){
		if ($handle = opendir("/etc/artica-postfix/pids")){
			while (false !== ($file = readdir($handle))) {
				if($file=="."){continue;}
				if($file==".."){continue;}
				$path="/etc/artica-postfix/pids/$file";
				if(is_dir($path)){continue;}
				if($unix->file_time_min($path)>72000){ @unlink($path); }
			}
		}
	}
	
	
	
	
	logs_urgency();
	Clean_attachments();
	Clean_apache_logs();
	$tmpdir=$unix->TEMP_DIR();
	$mysql_storelogs=new mysql_storelogs();
	$echo=$unix->find_program("echo");
	if(is_file("/var/log/apache2/unix-varrunnginx-authenticator.sock/nginx.access.log")){ shell_exec("$echo \" \" > /var/log/apache2/unix-varrunnginx-authenticator.sock/nginx.access.log"); }
	if(is_file("/var/log/apache2/unix-varrunnginx-authenticator.sock/nginx.error.log")){ shell_exec("$echo \" \" > /var/log/apache2/unix-varrunnginx-authenticator.sock/nginx.error.log"); }	
	
		 

	if(!is_dir("/home/logrotate/work")){@mkdir("/home/logrotate/work",0755,true);}
	if(!is_dir("/var/log/artica-postfix/postqueue")){@mkdir("/var/log/artica-postfix/postqueue",0755,true);}
	$q=new mysql_storelogs();

	
	
	
	if ($handle = opendir("/home/logrotate/work")){
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				$path="/home/logrotate/work/$file";
				if(preg_match("#^php\.log-#", $file)){@unlink($path);continue;}
				if(preg_match("#^daemon\.log#", $file)){@unlink($path);continue;}
				if(preg_match("#^debug-#", $file)){@unlink($path);continue;}
				if(preg_match("#^kern\.log#", $file)){@unlink($path);continue;}
				if(preg_match("#^messages-[0-9]+\.gz#", $file)){@unlink($path);continue;}
				if(preg_match("#^access\.#", $file)){continue;}
				if(preg_match("#^cache\.#", $file)){continue;}
				if($unix->maillog_to_backupdir($path)){continue;}
				$timef=$unix->file_time_min($path);
				if($timef>5760){
					$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;
					$q->events("Removing $path, exceed 5760mn",__FUNCTION__,__LINE__);
					@unlink($path);
				}
			}
		}
	}
	
	if($LogRotatePath<>"/home/logrotate"){
		if ($handle = opendir("$LogRotatePath/work")){
			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != "..") {
					$path="$LogRotatePath/work/$file";
					if(preg_match("#^php\.log-#", $file)){@unlink($path);continue;}
					if(preg_match("#^daemon\.log#", $file)){@unlink($path);continue;}
					if(preg_match("#^debug-#", $file)){@unlink($path);continue;}
					if(preg_match("#^kern\.log#", $file)){@unlink($path);continue;}
					if(preg_match("#^messages-[0-9]+\.gz#", $file)){@unlink($path);continue;}
					if(preg_match("#^access\.#", $file)){continue;}
					if(preg_match("#^cache\.#", $file)){continue;}
					if($unix->maillog_to_backupdir($path)){continue;}
					$timef=$unix->file_time_min($path);
					if($timef>5760){
						$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;
						$q->events("Removing $path, exceed 5760mn",__FUNCTION__,__LINE__);
						@unlink($path);
					}
				}
			}
		}		
	}
	
	
	
	if(is_dir("/home/logs-backup/olds")){
		if ($handle = opendir("/home/logs-backup/olds")){
			while (false !== ($file = readdir($handle))) {
				if ($file == "."){continue;}
				if ($file == ".."){continue;}
				$path="/home/logs-backup/olds/$file";
				if(preg_match("#^php\.log-#", $file)){@unlink($path);continue;}
				if(preg_match("#^daemon\.log#", $file)){@unlink($path);continue;}
				if(preg_match("#^debug-#", $file)){@unlink($path);continue;}
				if(preg_match("#^kern\.log#", $file)){@unlink($path);continue;}
				if($unix->maillog_to_backupdir($path)){continue;}
				if($GLOBALS["VERBOSE"]){echo "$file {$timef}mn\n";}
				$timef=$unix->file_time_min($path);
				if($timef>1880){
					$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;
					$q->events("Removing $path, exceed 1880mn",__FUNCTION__,__LINE__);
					@unlink($path);
				}
		
			}
		}
	}
	
	
	
	
	if ($handle = opendir("/root")){
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				$path="/root/$file";
				if(is_dir($path)){continue;}
				if(is_numeric($file)){@unlink($path);continue;}
				if(preg_match("#_[0-9]+_tmp#", $file)){@unlink($path);continue;}
			}
		}
	}

	if(is_dir("/opt/artica/ldap-backup")){
		if ($handle = opendir("/opt/artica/ldap-backup")){
				while (false !== ($file = readdir($handle))) {
					if ($file != "." && $file != "..") {
						$path="/opt/artica/ldap-backup/$file";
						if(preg_match("#[0-9]+-0-9]+-0-9]+-0-9]+\.tar\.gz$#",$file)){
							$time=$unix->file_time_min($path);
							if($time>7200){@unlink($path);}
							continue;
						}
						
						if(is_numeric($file)){@unlink($path);continue;}
						}
					}
			}
	}
	
	
	if ($handle = opendir($tmpdir)) {
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				$path="$tmpdir/$file";
				if(is_dir($path)){
					if(preg_match("#^category_#", $file)){
						$time=$unix->dir_time_min($path);
						if($time>120){ @rmdir($path); continue;}
					}
					
					if(preg_match("#^[0-9]+\.[0-9]+.[0-9]+$#", $file)){
						$time=$unix->dir_time_min($path);
						if($time>120){ @rmdir($path); continue;}
					}
					
					continue;
				}
				
				if($GLOBALS["VERBOSE"]){echo "$path ?\n";} 
				if(is_dir($path)){if($GLOBALS["VERBOSE"]){echo "$path is a directory\n";} continue;}
				
				if(preg_match("#\.gif$#", $file)){
					$time=$unix->file_time_min($path);
					if($time>1){
						$size=@filesize($path)/1024;
						$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
						$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;
						if($GLOBALS["VERBOSE"]){echo "$path - > DELETE\n";}
						@unlink($path);
						continue;
					}
					
				}
				
				if(preg_match("#process1-(.+?)\.tmp$#", $file)){
					$time=$unix->file_time_min($path);
					if($time>1){
						$size=@filesize($path)/1024;
						$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
						$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;
						if($GLOBALS["VERBOSE"]){echo "$path - > DELETE\n";}
						@unlink($path);
						continue;
					}
					
					continue;
					
				}
				
		if(preg_match("#^artica-.+?\.tmp#", $file)){
					$time=$unix->file_time_min($path);
					if($GLOBALS["VERBOSE"]){echo "$path - > {$time}Mn\n";}
					if($time>10){
						$size=@filesize($path)/1024;
    					$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
    					$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;						
						if($GLOBALS["VERBOSE"]){echo "$path - > DELETE\n";}
						@unlink($path);
						continue;
					}
				}else{
					if($GLOBALS["VERBOSE"]){echo "$file -> NO MATCH ^artica-.+?\.tmp \n";} 
				}
				
			if(preg_match("#^artica-php#", $file)){
					$time=$unix->file_time_min($path);
					if($GLOBALS["VERBOSE"]){echo "$path - > {$time}Mn\n";}
					if($time>10){
						$size=@filesize($path)/1024;
    					$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
    					$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;						
						if($GLOBALS["VERBOSE"]){echo "$path - > DELETE\n";}
						@unlink($path);
						continue;
					}
				}else{
					if($GLOBALS["VERBOSE"]){echo "$file -> NO MATCH ^artica-php \n";} 
				}				
			}
		}
		
	}else{
		if($GLOBALS["VERBOSE"]){echo "$tmpdir failed...\n";} 
	}
	if($GLOBALS["VERBOSE"]){echo "$tmpdir done..\n";}
	

	
}

function Cleanbin(){
		if ($handle = opendir("/usr/share/artica-postfix/bin")) {
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				$filepath="/usr/share/artica-postfix/bin/$file";
				if(is_dir($filepath)){continue;}
				if(!preg_match("#^st[0-9A-Za-z]+$#", $file)){continue;}
				echo "Remove $filepath\n";
				@unlink($filepath);
			}
		}
	}
	
}

function IfReallyExists(){
	$unix=new unix();
	$pgrep=$unix->find_program("pgrep");
	$me=basename(__FILE__);
	$meRegx=str_replace(".", "\.", $me);
	$MyPID=getmypid();
	exec("pgrep -l -f $me 2>&1",$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#pgrep#", $line)){continue;}
		if(preg_match("#^([0-9]+)\s+$meRegx#", $line,$re)){
			if($GLOBALS["VERBOSE"]){echo "IfReallyExists() {$re[1]} <> $MyPID\n";}
			if($re[1]==$MyPID){continue;}
			return true;
		}
		
	}
	
	return false;
	
}


function clean_squid_users_size($nopid=false){
	$lockfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".lck";
	
	
	
	$unix=new unix();
	
	if(is_file($lockfile)){
		$lockfileTime=$unix->file_time_min($lockfile);
		if($lockfileTime<20){
			if(IfReallyExists()){
				if($GLOBALS["VERBOSE"]){echo "Lock file since {$lockfileTime}mn\n";}
				return;
			}
		}
	}
	

	
	if(!$nopid){
		$pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidpath);
		if($unix->process_exists($pid)){
			if($GLOBALS["VERBOSE"]){echo "Already process $pid running.. aborting";}
			system_admin_events("Already process $pid running.. aborting",__FUNCTION__,__FILE__,__LINE__,"clean-logs");
			return;
		}
	
		@file_put_contents($pidpath, getmypid());
	}
	
	@unlink($lockfile);
	@file_put_contents($lockfile, time());	
	
	
	if(!isset($GLOBALS["EnableRemoteStatisticsAppliance"])){
		$GLOBALS["EnableRemoteStatisticsAppliance"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableRemoteStatisticsAppliance");
		if(!is_numeric($GLOBALS["EnableRemoteStatisticsAppliance"])){$GLOBALS["EnableRemoteStatisticsAppliance"]=0;}
	}	
	if(!isset($GLOBALS["DisableArticaProxyStatistics"])){
		$GLOBALS["DisableArticaProxyStatistics"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/DisableArticaProxyStatistics");
		if(!is_numeric($GLOBALS["DisableArticaProxyStatistics"])){$GLOBALS["DisableArticaProxyStatistics"]=0;}
	}	
	
	$disable=0;
	$c=0;
	if($GLOBALS["EnableRemoteStatisticsAppliance"]==1){$disable=1;}
	if($GLOBALS["DisableArticaProxyStatistics"]==1){$disable=1;}
	
	$dirs[]="/var/log/artica-postfix/squid-users";
	
	while (list ($index, $Directory) = each ($dirs) ){
		if($GLOBALS["VERBOSE"]){echo "Scan; $Directory \n";}
		if(!is_dir($Directory)){if($GLOBALS["VERBOSE"]){echo "$Directory doesn't exists, continue;\n";}continue;}
		if (!$handle = opendir($Directory)) {if($GLOBALS["VERBOSE"]){echo "$Directory fatal error\n";}continue;}
		if($GLOBALS["VERBOSE"]){echo "LOOP -> $Directory \n";}
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				$c++;
				$size=$size+@filesize("$Directory/$file");
				if($GLOBALS["VERBOSE"]){echo "removing $Directory/$file\n";}
				@unlink("$Directory/$file");
			}
		}
	}
	
	if($GLOBALS["VERBOSE"]){echo "Scan; DONE, next \n";}
	
	
	if($c>0){
		$size=$size/1024;
		$size=round($size/1000,2);
		system_admin_events("$c deleted files ({$size}MB cleaned)",__FUNCTION__,__FILE__,__LINE__,"clean-logs");
	}
	
	
	
	
	if($disable==0){return;@unlink($lockfile);}
	$dirs[]="/var/log/artica-postfix/searchwords";
	$dirs[]="/var/log/artica-postfix/squid-usersize";
	$dirs[]="/var/log/artica-postfix/squid-brut";
	$dirs[]="/var/log/artica-postfix/squid-reverse";
	
	
	

	
	while (list ($index, $Directory) = each ($dirs) ){
		if(!is_dir($Directory)){continue;}
		if (!$handle = opendir($Directory)) {continue;}
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				$c++;
				$size=$size+@filesize("$Directory/$file");
				events_tail_squid("WARNING! DELETING $Directory/$file");
				@unlink("$Directory/$file");
				}
			}
		}	
		
	
	
		@unlink($lockfile);
	if($c>0){
		$size=$size/1024;
		$size=round($size/1000,2);
		system_admin_events("$c deleted files ({$size}MB cleaned)",__FUNCTION__,__FILE__,__LINE__,"clean-logs");
	}
}

function events_tail_squid($text){
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	//if($GLOBALS["VERBOSE"]){echo "$text\n";}
	$pid=@getmypid();
	$date=@date("H:i:s");
	$logFile="/var/log/artica-postfix/auth-tail.debug";
	$size=@filesize($logFile);
	if($size>1000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');
	$GLOBALS["CLASS_UNIX"]->events(basename(__FILE__)." $date $text");
	@fwrite($f, "$pid ".basename(__FILE__)." $date $text\n");
	@fclose($f);
}


function CleanLogsDatabases($nopid=false){
	include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
	
	$unix=new unix();
	if($nopid){
		$pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidpath);
		if($unix->process_exists($pid)){
			$pidtime=$unix->PROCCESS_TIME_MIN($pid);
			system_admin_events(basename(__FILE__).":: ".__FUNCTION__." Already process $pid running since $pidtime Mn.. Aborting",__FUNCTION__,__FILE__,__LINE__);
			return;
		}	
	 @file_put_contents($pidpath, getmypid());
	}
	
	$sock=new sockets();
	$q=new mysql();
	$settings=unserialize(base64_decode($sock->GET_INFO("FcronSchedulesParams")));
	if(!is_numeric($settings["max_events"])){$settings["max_events"]="10000";}	
	if($GLOBALS["VERBOSE"]){echo "max_events::{$settings["max_events"]}\n";}
	$tables=array();
	$tables["dhcpd_logs"]=true;
	$tables["ps_mem"]=true;
	$tables["update_events"]=true;
	$tables["nmap_events"]=true;
	$tables["backup_events"]=true;
	$tables["computers_available"]=true;
	$tables["auth_events"]=true;
	$tables["events"]=true;
	
	$ff=$q->LIST_TABLES_EVENTS_SYSTEM();
	while (list ($tbl, $ligne) = each ($ff) ){$tables[$tbl]=true;}
	$ff=$q->LIST_TABLES_EVENTS_SQUID();	
	while (list ($tbl, $ligne) = each ($ff) ){$tables[$tbl]=true;}
		
	while (list ($tbl, $ligne) = each ($tables)){
		_CleanLogsDatabases($tbl,$settings["max_events"]);
	}
}

function _CleanLogsDatabases($tablename,$max_events){
	$q=new mysql();
	$NumRows=$q->COUNT_ROWS($tablename, "artica_events");
	if($GLOBALS["VERBOSE"]){echo "$tablename::$NumRows/$max_events\n";}
	if($NumRows>$max_events){
		$toDelete=$NumRows-$max_events;
		$q->QUERY_SQL("DELETE FROM $tablename ORDER BY zDate LIMIT $toDelete","artica_events");
	}	
}

function clean_rotate_events(){
	$unix=new unix();
	
	$filesNumber=$unix->DIR_COUNT_OF_FILES("/var/log/artica-postfix/rotate_events");
	
	if($filesNumber<500){return;}
	if (!$handle = opendir("/var/log/artica-postfix/rotate_events")){
		return;
	
	}
	
	while (false !== ($file = readdir($handle))) {
		if ($file == "."){continue;}
		if ($file == ".."){continue;}
		$path="/var/log/artica-postfix/rotate_events/$file";
		if(is_dir("$path")){continue;}
		@unlink($path);
	}
	
	
	
}


function CleanRotatedFiles(){
	include_once(dirname(__FILE__)."/ressources/class.mysql.syslog.inc");
	$unix=new unix();
	$sock=new sockets();
	$LogRotateCompress=1;
	
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	$ApacheLogRotate=$sock->GET_INFO("ApacheLogRotate");
	$cpbin=$unix->find_program("cp");
	$php5=$unix->LOCATE_PHP5_BIN();
	$tmpdir=$unix->TEMP_DIR();
	if(!is_numeric($ApacheLogRotate)){$ApacheLogRotate=1;}
	if(!is_numeric($LogRotatePath)){$LogRotatePath="/home/logrotate";}	
	
	
	$DirsToScan["/var/log"]=true;
	$DirsToScan["/var/log/apache2"]=true;
	$DirsToScan["/var/log/lighttpd"]=true;
	$DirsToScan["/var/log/ejabberd"]=true;
	
	
	$apache2=$unix->dirdir("/var/log/apache2");
	while (list ($WorkingDir, $ligne) = each ($apache2) ){	
		$DirsToScan[$WorkingDir]=true;
	}

	
	
	$q=new mysql_storelogs();
	
	while (list ($WorkingDir, $ligne) = each ($DirsToScan) ){
	$RotateSquid=false;
	if($WorkingDir=="/var/log/squid"){continue;}
	$table=$unix->DirFiles($WorkingDir,"(\.|-)[0-9]+.*?$");
	$compressed["gz"]=true;
	$compressed["bz"]=true;
	$compressed["bz2"]=true;
	
	while (list ($filename, $ligne) = each ($table) ){
		$path="$WorkingDir/$filename";
		if($unix->file_time_min($path)<1440){continue;}
		$filedate=date('Y-m-d H:i:s',filemtime($path));
		$q->events("Injecting $path $filedate");
		if(!$q->ROTATE_TOMYSQL($path,$filedate)){continue;}
		
	}
}
	
	
}

function UrgencyChecks(){
	$unix=new unix();
	$sock=new sockets();
	
	$pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidpath);
	if($unix->process_exists($pid)){
		$pidtime=$unix->PROCCESS_TIME_MIN($pid);
		$unix->events("UrgencyChecks():: ".__FUNCTION__." Already process $pid running since $pidtime Mn.. Aborting");
		return;
	}
	@file_put_contents($pidpath, getmypid());
	$echo=$unix->find_program("echo");

	
	Clean_tmp_path(true);
	$f=$unix->DirFiles("/var/log");
	$f[]="syslog";
	$f[]="messages";
	$f[]="user.log";
	varlog();
			
	while (list ($num, $filename) = each ($f) ){
		if($filename=="mail.log"){continue;}
		$filepath="/var/log/$filename";
		if(!is_file($filepath)){continue;}
		$size=$unix->file_size($filepath);
		$size=$size/1024;
		$size=round($size/1000,2);
		$unix->events("UrgencyChecks():: $filepath {$size}M");
		$ARRAY[$filepath]=$size;
	}
	
	$restart=false;
	
	
	if($restart){
		@chmod("/etc/init.d/syslog",0755);
		shell_exec("/etc/init.d/syslog restart");
		shell_exec("/etc/init.d/artica-syslog restart");
		shell_exec("/etc/init.d/auth-tail restart");
		shell_exec("/etc/init.d/postfix-logger restart");
	}	

	
}


function logrotatelogs($nopid=false){
	$unix=new unix();
	$sock=new sockets();
	
	if($nopid){
		$pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidpath);
		if($unix->process_exists($pid)){
			$pidtime=$unix->PROCCESS_TIME_MIN($pid);
			system_admin_events(basename(__FILE__).":: ".__FUNCTION__." Already process $pid running since $pidtime Mn.. Aborting",__FUNCTION__,__FILE__,__LINE__);
			return;
		}
		@file_put_contents($pidpath, getmypid());
	}	
	
	
	$echo=$unix->find_program("echo");

	include_once(dirname(__FILE__)."/ressources/class.mysql.syslog.inc");
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__." line:".__LINE__."\n";}
	$q=new mysql_syslog();
	if($q->COUNT_ROWS("logrotate")==0){
		$q->CheckDefaults();
	}
	$sql="SELECT RotateFiles FROM logrotate WHERE enabled=1";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	while ($ligne = mysql_fetch_assoc($results)) {
		$filepath=$ligne["RotateFiles"];
		if(strpos($filepath, "*")>0){
			if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":: Scanning $filepath line:".__LINE__."\n";}
			foreach (glob($filepath) as $filename) {
				if($filename=="/var/log/squid/access.log"){continue;}
				if($filename=="/var/log/mail.log"){continue;}
				$size=$unix->file_size($filename);
				$size=$size/1024;
				$size=round($size/1000,2);
				$ARRAY[$filename]=$size;
				
				
			}
			
		}else{
			if(is_file($filepath)){
				if($filepath=="/var/log/mail.log"){continue;}
				if($filepath=="/var/log/squid/access.log"){continue;}
				$size=$unix->file_size($filepath);
				$size=$size/1024;
				$size=round($size/1000,2);
				$ARRAY[$filepath]=$size;
			}
			if(is_dir($filepath)){
				while (list ($num, $filename) = each ($f) ){
					if($filename=="mail.log"){continue;}
					$filepath="/var/log/$filename";
					if($filepath=="/var/log/squid/access.log"){continue;}
					$f=$unix->DirFiles("$filepath");
					$size=$unix->file_size($filepath);
					$size=$size/1024;
					$size=round($size/1000,2);
					$ARRAY[$filepath]=$size;
				}
			}
						
		}
		
	}
	$f=$unix->DirFiles("/var/log");
	while (list ($num, $filename) = each ($f) ){
		if($filename=="mail.log"){continue;}
		$filepath="/var/log/$filename";
		$size=$unix->file_size($filepath);
		$size=$size/1024;
		$size=round($size/1000,2);
		$ARRAY[$filepath]=$size;		
	}
	
	$f=$unix->DirFiles("/var/log/artica-postfix");
	while (list ($num, $filename) = each ($f) ){
		$filepath="/var/log/artica-postfix/$filename";
		$size=$unix->file_size($filepath);
		$size=$size/1024;
		$size=round($size/1000,2);
		$ARRAY[$filepath]=$size;
	}	
	
	$restart=false;

	if($restart){
		shell_exec("/etc/init.d/syslog restart");
		shell_exec("/etc/init.d/artica-syslog restart");
		shell_exec("/etc/init.d/auth-tail restart");
		shell_exec("/etc/init.d/postfix-logger restart");
	}
	
}

function cleanRoot(){
	
	$BaseWorkDir="/root";
	if (!$handle = opendir($BaseWorkDir)) {echo "Failed open $BaseWorkDir\n";return;}
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		if(is_dir($targetFile)){continue;}
		if(!is_file($targetFile)){continue;}
		if(!is_numeric($filename)){continue;}
		@unlink($targetFile);
	}

	

	
}

function CheckSingle_logfile($path,$maxsize){
	if(!is_file($path)){return;}
	$unix=new unix();
	$echo=$unix->find_program("echo");
	
	
	$sizePHP=round(@filesize($path)/1024);
	writelogs("$path = $sizePHP Ko",__FUNCTION__,__FILE__,__LINE__);
	if($sizePHP>$maxsize){
		shell_exec("$echo \"\" > \"$path\"");
		$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$sizePHP;
		$GLOBALS["DELETED_FILES"]++;
		$GLOBALS["DELETED_FILES"][]=$path;
	}
	
	
}


function CleanLogs($aspid=false){
	$unix=new unix();
	$timefile="/etc/artica-postfix/pids/exec.clean.logs.php.CleanLogs.time";
	if(!$aspid){
		$maxtime=480;
		$pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidpath);
		if($unix->process_exists($pid)){
			$unix->events(basename(__FILE__).":: ".__FUNCTION__." Already process $pid running.. Aborting");
			return;
		}
		
		@file_put_contents($pidpath,getmypid());
		
		
		$timeOfFile=$unix->file_time_min($timefile);
		$unix->events("CleanLogs():: Time $timeOfFile/$maxtime");
		if($timeOfFile<$maxtime){
			$unix->events("CleanLogs():: Aborting");
			return;
		}
	
	}
	
	@unlink($timefile);
	@file_put_contents($timefile,time());
	
	cleanRoot();
	maillog();
	MakeSpace();
	cleanRoot();
	RTTSizes();
	
	
	CheckSingle_logfile("/var/log/php.log",102400);
	CheckSingle_logfile("/opt/squidsql/error.log",51200);
	CheckSingle_logfile("/var/log/atop.log",1024);
	CheckSingle_logfile("/var/log/php5-fpm.log",1024);
	CheckSingle_logfile("/var/log/artica-squid-stats.log",10240);
	CheckSingle_logfile("/var/log/atop.log",10240);
	CheckSingle_logfile("/var/log/apache2/common-access.log",204800);
	
	wrong_number();
	Clean_tmp_path();
	CleanOldInstall();
	LogRotateTimeAndSize("/var/log/samba");
	
	
	if(system_is_overloaded(dirname(__FILE__))){
		$unix->send_email_events("logs cleaner task aborting, system is overloaded", "stopped after CleanOldInstall()\nWill restart in next cycle...", "system");
		return;
	}
	
	CleanBindLogs();
	if(system_is_overloaded(dirname(__FILE__))){
		$unix->send_email_events("logs cleaner task aborting, system is overloaded", "stopped after CleanBindLogs()\nWill restart in next cycle...", "system");
		return;
	}
	
	$unix->events(basename(__FILE__).":: ".__FUNCTION__." Cleaning Clamav bases");
	CleanClamav();
	if(system_is_overloaded(dirname(__FILE__))){
		$unix->send_email_events("logs cleaner task aborting, system is overloaded", "stopped after CleanClamav()\nWill restart in next cycle...", "system");
		return;
	}	
	
	$size=str_replace("&nbsp;"," ",FormatBytes($GLOBALS["DELETED_SIZE"]));
	echo "$size cleaned :  {$GLOBALS["DELETED_FILES"]} files\n";
	if($GLOBALS["DELETED_SIZE"]>500){
		send_email_events("$size logs files cleaned",
		"{$GLOBALS["DELETED_FILES"]} files cleaned for $size free disk space:\n
		".@implode("\n",$GLOBALS["UNLINKED"]),"logs_cleaning");
	}	
	$GLOBALS["DELETED_SIZE"]=0;
	$GLOBALS["DELETED_FILES"]=0;
	
	$unix->events(basename(__FILE__).":: ".__FUNCTION__." initalize");
	init();
	$unix->events(basename(__FILE__).":: ".__FUNCTION__." cleanTmplogs()");
	cleanTmplogs();
	if(system_is_overloaded(dirname(__FILE__))){$unix->send_email_events("logs cleaner task aborting, system is overloaded",
	 "stopped after cleanTmplogs()\nWill restart in next cycle...", "system");return;}	
	

	
	$unix->events(basename(__FILE__).":: ".__FUNCTION__." Cleaning /opt/artica/tmp");
	CleanDirLogs('/opt/artica/tmp');
	if(system_is_overloaded(dirname(__FILE__))){$unix->send_email_events("logs cleaner task aborting, system is overloaded",
	 "stopped after CleanDirLogs(/opt/artica/tmp)\nWill restart in next cycle...", "system");return;}		
	
	$unix->events(basename(__FILE__).":: ".__FUNCTION__." Cleaning /opt/artica/install");
	CleanDirLogs('/opt/artica/install');
	if(system_is_overloaded(dirname(__FILE__))){$unix->send_email_events("logs cleaner task aborting, system is overloaded",
	 "stopped after CleanDirLogs(/opt/artica/install)\nWill restart in next cycle...", "system");return;}		
	$unix->events(basename(__FILE__).":: ".__FUNCTION__." Cleaning phplogs");
	phplogs();
	if(system_is_overloaded(dirname(__FILE__))){$unix->send_email_events("logs cleaner task aborting, system is overloaded",
	 "stopped after phplogs()\nWill restart in next cycle...", "system");return;}		
	
	$unix->events(basename(__FILE__).":: ".__FUNCTION__." Cleaning /opt/openemm/tomcat/logs");
	CleanDirLogs('/opt/openemm/tomcat/logs');
	

	$unix->events(basename(__FILE__).":: ".__FUNCTION__." Cleaning PHP Sessions");
	sessions_clean();
	$unix->events(basename(__FILE__).":: ".__FUNCTION__." Cleaning old install sources packages");

	
	$size=str_replace("&nbsp;"," ",FormatBytes($GLOBALS["DELETED_SIZE"]));
	echo "$size cleaned :  {$GLOBALS["DELETED_FILES"]} files\n";
	if($GLOBALS["DELETED_SIZE"]>500){
		send_email_events("$size logs files cleaned",
		"{$GLOBALS["DELETED_FILES"]} files cleaned for $size free disk space:\n
		".@implode("\n",$GLOBALS["UNLINKED"]),"logs_cleaning");
	}
	
	
}


function RTTSizes($aspid=false){
	
	
	if($aspid){
		$unix=new unix();
		$Pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$PidTime="/etc/artica-postfix/pids/exec.clean.logs.php.RTTSizes.time";
		if($GLOBALS["VERBOSE"]){echo "Pidfile: $Pidfile\n";}
		if($GLOBALS["VERBOSE"]){echo "PidTime: $PidTime\n";}
		
		$pid=$unix->get_pid_from_file($Pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
		if($GLOBALS["VERBOSE"]){echo "Aborting Task already running pid $pid ".__FUNCTION__."()\n";} return;}
		
		
		@file_put_contents($Pidfile, getmypid());
		
		if(!$GLOBALS["VERBOSE"]){
			$time=$unix->file_time_min($PidTime);
			if($time<1440){echo "Only each 1440mn\n";die();}
			@unlink($PidTime);
			@file_put_contents($PidTime, time());
		}
		
	}
	
	$base="/home/squid/rttsize";
	$YEAR=date("Y");
	$MONTH=date("m");
	$DAY=date("d");
	$HOUR=date("H");
	$WEEK=date("W");
	$unix=new unix();
	$rm=$unix->find_program("rm");
	
	$path="/home/squid/rttsize";
	if(is_dir($path)){
		$directory=opendir($path);
		while ($file = readdir($directory)) {
			if($file=="."){continue;}
			if($file==".."){continue;}
			if(!is_dir("$path/$file")){continue;}
			if(!is_numeric($file)){continue;}
			if($file==$YEAR){continue;}
			shell_exec("$rm -rf $path/$file");
		}
	}
		
	
	$path="/home/squid/rttsize/$YEAR/$MONTH/$WEEK/$DAY";
	
	
	if(is_dir($path)){
		$directory=opendir($path);
		while ($file = readdir($directory)) {
			if($file=="."){continue;}
			if($file==".."){continue;}
			if(!is_dir("$path/$file")){continue;}
			if(!is_numeric($file)){continue;}
			if(intval($file)==intval($HOUR)){continue;}
			$dirpath="$path/$file";
			echo "$rm -rf \"$dirpath\"\n";
			shell_exec("$rm -rf \"$dirpath\"");
		}
	
	}
	
	
	$path="/home/squid/rttsize/$YEAR/$MONTH/$WEEK";
	$unix=new unix();
	$rm=$unix->find_program("rm");
	if(is_dir($path)){
		$directory=opendir($path);
		while ($file = readdir($directory)) {
			if($file=="."){continue;}
			if($file==".."){continue;}
			if(!is_dir("$path/$file")){continue;}
			if(!is_numeric($file)){continue;}
			if(intval($file)==intval($DAY)){continue;}
			$dirpath="$path/$file";
			echo "$rm -rf \"$dirpath\"\n";
			shell_exec("$rm -rf \"$dirpath\"");
		}
	}	
	
	
	$path="/home/squid/rttsize/$YEAR/$MONTH";
	$unix=new unix();
	$rm=$unix->find_program("rm");
	if(is_dir($path)){
		$directory=opendir($path);
		while ($file = readdir($directory)) {
			if($file=="."){continue;}
			if($file==".."){continue;}
			if(!is_dir("$path/$file")){continue;}
			if(!is_numeric($file)){continue;}
			if(intval($file)==intval($WEEK)){continue;}
			$dirpath="$path/$file";
			echo "$rm -rf \"$dirpath\"\n";
			shell_exec("$rm -rf \"$dirpath\"");
		}
	}	
	$path="/home/squid/rttsize/$YEAR";
	$unix=new unix();
	$rm=$unix->find_program("rm");
	if(is_dir($path)){
		$directory=opendir($path);
		while ($file = readdir($directory)) {
			if($file=="."){continue;}
			if($file==".."){continue;}
			if(!is_dir("$path/$file")){continue;}
			if(!is_numeric($file)){continue;}
			if(intval($file)==intval($MONTH)){continue;}
			$dirpath="$path/$file";
			echo "$rm -rf \"$dirpath\"\n";
			shell_exec("$rm -rf \"$dirpath\"");
		}
	}
	
	
	$path="/home/artica/ufdbcounters/$YEAR/$MONTH/$DAY";
	
	
	if(is_dir($path)){
		$directory=opendir($path);
		while ($file = readdir($directory)) {
			if($file=="."){continue;}
			if($file==".."){continue;}
			if(!is_dir("$path/$file")){continue;}
			if(!is_numeric($file)){continue;}
			if(intval($file)==intval($HOUR)){continue;}
			$dirpath="$path/$file";
			echo "$rm -rf \"$dirpath\"\n";
			shell_exec("$rm -rf \"$dirpath\"");
		}
	}

	$path="/home/artica/ufdbcounters/$YEAR/$MONTH";
	
	
	if(is_dir($path)){
		$directory=opendir($path);
		while ($file = readdir($directory)) {
			if($file=="."){continue;}
			if($file==".."){continue;}
			if(!is_dir("$path/$file")){continue;}
			if(!is_numeric($file)){continue;}
			if(intval($file)==intval($DAY)){continue;}
			$dirpath="$path/$file";
			echo "$rm -rf \"$dirpath\"\n";
			shell_exec("$rm -rf \"$dirpath\"");
		}
	}	
	
	$path="/home/artica/ufdbcounters/$YEAR";
	
	
	if(is_dir($path)){
		$directory=opendir($path);
		while ($file = readdir($directory)) {
			if($file=="."){continue;}
			if($file==".."){continue;}
			if(!is_dir("$path/$file")){continue;}
			if(!is_numeric($file)){continue;}
			if(intval($file)==intval($MONTH)){continue;}
			$dirpath="$path/$file";
			echo "$rm -rf \"$dirpath\"\n";
			shell_exec("$rm -rf \"$dirpath\"");
		}
	}	

	$path="/home/artica/ufdbcounters";
	
	
	if(is_dir($path)){
		$directory=opendir($path);
		while ($file = readdir($directory)) {
			if($file=="."){continue;}
			if($file==".."){continue;}
			if(!is_dir("$path/$file")){continue;}
			if(!is_numeric($file)){continue;}
			if(intval($file)==intval($YEAR)){continue;}
			$dirpath="$path/$file";
			echo "$rm -rf \"$dirpath\"\n";
			shell_exec("$rm -rf \"$dirpath\"");
		}
	}	
}


function cleanTmplogs(){
	
	$f["st16WgqD"]=true;
	$f["st2rbmZC"]=true;
	$f["st7lHTjd"]=true;
	$f["st9q9qku"]=true;
	$f["stbBnixv"]=true;
	$f["stDPuj6u"]=true;
	$f["stFAEeXx"]=true;
	$f["stH9DKQm"]=true;
	$f["stjFocrx"]=true;
	$f["stLKCNJm"]=true;
	$f["stneMj5n"]=true;
	$f["stok2sFA"]=true;
	$f["stRgTmRp"]=true;
	$f["stvKhBOk"]=true;
	$f["stWpdmxs"]=true;
	$f["stXBCQtr"]=true;
	$f["stynhnC7"]=true;
	$f["styU74dl"]=true;
	$f["st2BrkvI"]=true;
	$f["st3b2g3y"]=true;
	$f["st9LHbOZ"]=true;
	$f["stau5VhG"]=true;
	$f["stBOljyq"]=true;
	$f["stE1SkGz"]=true;
	$f["stghVBHr"]=true;
	$f["sthZJVUE"]=true;
	$f["stjy3MtG"]=true;
	$f["stMrVlvh"]=true;
	$f["stojwuux"]=true;
	$f["stPrJoES"]=true;
	$f["stSk5lXh"]=true;
	$f["stvrDRNN"]=true;
	$f["stX9eicQ"]=true;
	$f["styKwAAu"]=true;
	$f["stYpIvJf"]=true;
	$f["stIFOQ6A"]=true;
	$f["stMSOCis"]=true;
	while (list ($num, $ligne) = each ($f) ){
		if(is_file("/usr/share/artica-postfix/bin/$num")){
			@unlink("/usr/share/artica-postfix/bin/$num");
		}
	
	}
	$f=array();	
	
	
	
$badfiles["100k"]=true;
$badfiles["2"]=true;
$badfiles["size"]=true;
$badfiles["versions"]=true;
$badfiles["3"]=true;
$badfiles["named_dump.db"]=true;
$badfiles["named.stats"]=true;
$badfiles["log-queries.info"]=true;
$badfiles["log-named-auth.info"]=true;
$badfiles["log-lame.info"]=true;
$badfiles["bind.pid"]=true;
$badfiles["ipp.txt"]=true;
$badfiles["debug"]=true;
$badfiles["log-update-debug.log"]=true;
$badfiles["ldap.ppu"]=true;
$badfiles["#"]=true;	
$baddirs["2000"]=true;



	while (list ($num, $ligne) = each ($badfiles) ){
		if($num==null){continue;}
		if(is_file("/usr/share/artica-postfix/$num")){@unlink("/usr/share/artica-postfix/$num");}
	}
	
	while (list ($num, $ligne) = each ($baddirs) ){
		if($num==null){continue;}
		if(is_dir("/usr/share/artica-postfix/$num")){shell_exec("/bin/rm -rf /usr/share/artica-postfix/$num");}
	}	
	
	$unix=new unix();
	$countfile=0;
	foreach (glob("/tmp/artica*") as $filename) {
		
	$countfile++;
		if($countfile>500){
			if(is_overloaded()){
				$unix->send_email_events("Clean Files: [/tmp/artica*]: System is overloaded ({$GLOBALS["SYSTEM_INTERNAL_LOAD"]}",
				"The clean logs function is stopped and wait a new schedule with best performances",
				"logs_cleaning");
				die();
			}
			$countfile=0;
		}		
		
    	$time=$unix->file_time_min($filename);
    	if($time>2){
    		$size=@filesize($filename)/1024;
    		$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
    		$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;
    		if($GLOBALS["VERBOSE"]){echo "Delete $filename\n";}
    		$unix->events(basename(__FILE__)." Delete $filename");
    		@unlink($filename);
    	}else{
    	if($GLOBALS["VERBOSE"]){echo "$filename TTL:$time \n";}
    	}
	}
	
	foreach (glob("/var/log/artica-postfix/postfix.awstats.log.*") as $filename){
		$countfile++;
		$size=@filesize($filename)/1024;
		$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
		$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1; 
		@unlink($filename);  
	}
	
	
	$countfile=0;
if($GLOBALS["VERBOSE"]){echo "/tmp/process1*\n";}
	foreach (glob("/tmp/process1*") as $filename) {
		
	$countfile++;
		if($countfile>500){
			if(is_overloaded()){
				$unix->send_email_events("Clean Files: [/tmp/process1*]: System is overloaded ({$GLOBALS["SYSTEM_INTERNAL_LOAD"]}",
				"The clean logs function is stopped and wait a new schedule with best performances",
				"logs_cleaning");
				die();
			}
			$countfile=0;
		}				
		
    	$time=$unix->file_time_min($filename);
    	if($time>1){
    		$size=@filesize($filename)/1024;
    		$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size; 
    		$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;   
    		if($GLOBALS["VERBOSE"]){echo "Delete $filename\n";}
    		$unix->events(basename(__FILE__)." Delete $filename");	
    		@unlink($filename);
    	}else{
    		if($GLOBALS["VERBOSE"]){echo "$filename TTL:$time \n";}
    	}
	}
	
}


function sessions_clean_parse($directory,$CleanPHPSessionTime,$APACHE_SRC_ACCOUNT=null,$APACHE_SRC_GROUP=null){
	$CleanPHPSessionTime=$CleanPHPSessionTime-1;
	if(!is_dir($directory)){return;}
	
	if (!$handle = opendir($directory)) {return;}
	$unix=new unix();
	while (false !== ($fileZ = readdir($handle))) {
		if($fileZ=="."){continue;}
		if($fileZ==".."){continue;}
		$filename="$directory/$fileZ";
		if(is_dir($filename)){continue;}
		$time=$unix->file_time_min($filename);
		if($time>$CleanPHPSessionTime){@unlink($filename);continue;}
		if($APACHE_SRC_ACCOUNT<>null){
			$unix->chown_func($APACHE_SRC_ACCOUNT,$APACHE_SRC_GROUP,$filename);
		}
	}
		
	
}


function sessions_clean(){
	$unix=new unix();
	$sock=new sockets();
	$TimeFile="/etc/artica-postfix/pids/exec.clean.logs.php.sessions_clean.time";
	if($unix->file_time_min($TimeFile)<60){return;}
	
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	
	$APACHE_SRC_ACCOUNT=$unix->APACHE_SRC_ACCOUNT();
	$APACHE_SRC_GROUP=$unix->APACHE_SRC_GROUP();
	$CleanPHPSessionTime=$sock->GET_INFO("CleanPHPSessionTime");
	if(!is_numeric($CleanPHPSessionTime)){$CleanPHPSessionTime=1440;}
	
	sessions_clean_parse("/var/lib/php5",$CleanPHPSessionTime,$APACHE_SRC_ACCOUNT,$APACHE_SRC_GROUP);
	sessions_clean_parse("/var/lib/php5-zarafa",$CleanPHPSessionTime,$APACHE_SRC_ACCOUNT,$APACHE_SRC_GROUP);
	sessions_clean_parse("/home/artica/hotspot/sessions",$CleanPHPSessionTime,$APACHE_SRC_ACCOUNT,$APACHE_SRC_GROUP);
	
	sessions_clean_parse("/home/squid/error_page_sessions",$CleanPHPSessionTime,$APACHE_SRC_ACCOUNT,$APACHE_SRC_GROUP);
	sessions_clean_parse("/usr/share/artica-postfix/ressources/logs/jGrowl",360,$APACHE_SRC_ACCOUNT,$APACHE_SRC_GROUP);
	sessions_clean_parse("/usr/share/artica-postfix/ressources/conf",360,$APACHE_SRC_ACCOUNT,$APACHE_SRC_GROUP);
	sessions_clean_parse("/home/squid/error_page_cache",60);
}
function wrong_number(){
	$unix=new unix();
	foreach (glob("/usr/share/artica-postfix/*") as $filename) {
		$name=basename($filename);
		if($name=="?"){@unlink($filename);continue;}
		if(preg_match("#^[0-9]+$#", $name)){@unlink($filename);}
	}
	foreach (glob("/root/*") as $filename) {
		$name=basename($filename);
		if($name=="?"){@unlink($filename);continue;}
		if(preg_match("#^[0-9]+$#", $name)){@unlink($filename);}
	}
	
}

function phplogs(){
	$filename="/usr/share/artica-postfix/ressources/logs/php.log";
	$size=@filesize($filename)/1024;
	if($GLOBALS["VERBOSE"]){echo "php.log size:{$size}Ko \n";}
	if($size>50681){
		$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1; 
		$GLOBALS["DELETED_SIZE"]=$size;
		@unlink($filename);
	}
}


function CleanClamav(){
	$unix=new unix();
	
	foreach (glob("/var/lib/clamav/clamav-*") as $filename) {
		$time=$unix->file_time_min($filename);
		if($time>60){
			if(is_dir($filename)){
				$size=dirsize($filename)/1024;
				$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1; 
				$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
				shell_exec($unix->find_program("rm")." -rf $filename");
				if($GLOBALS["VERBOSE"]){echo "Delete directory $filename ($size Ko) TTL:$time\n";}
				continue;
				
			}
			$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1; 
			$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
			if($GLOBALS["VERBOSE"]){echo "Delete $filename ($size Ko)\n";}		
			unlink($filename);
		}
		
	}
}

function CleanBindLogs(){
	$f["/var/cache/bind/log-lame.info"]=1;
	$f["/var/cache/bind/log-queries.info"]=1;
	while (list ($filepath, $none) = each ($f) ){
		$size=round(unix_file_size("$filepath")/1024);
		if($size>51200000){
			@unlink($filepath);
			$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1; 
			$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
		}
		
		
	}
	
}


function PathsStatus(){
	$f[]="/root";
	foreach (glob("/usr/share/*",GLOB_ONLYDIR) as $filename) {
		$f[]=$filename;
	}
	
	while (list ($num, $dir) = each ($f) ){
		echo "$dir\t".str_replace("&nbsp;"," ",FormatBytes(dirsize($dir)/1024))."\n";
	}
	
}




// /var/log/samba

function dirsize($path){
	$unix=new unix();
	
	exec($unix->find_program("du")." -b $path",$results);
	$tt=implode("",$results);
	if(preg_match("#([0-9]+)\s+#",$tt,$re)){return $re[1];}
	
}

function CleanOldInstall(){
	
	foreach (glob("/root/APP_*",GLOB_ONLYDIR) as $dirname) {
		if(!is_dir($dirname)){return;}
		$time=file_get_time_min($dirname);
		
		if($time>2880){
			echo "Removing $dirname\n";
			$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+dirsize($dirname);
			shell_exec("/bin/rm -rf $dirname");}
		}
	
}
function is_overloaded($file=null){
	if(!isset($GLOBALS["CPU_NUMBER"])){
			$users=new usersMenus();
			$GLOBALS["CPU_NUMBER"]=intval($users->CPU_NUMBER);
	}
	
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];
	$cpunum=$GLOBALS["CPU_NUMBER"]+1.5;
	if($file==null){$file=basename(__FILE__);}

	if($internal_load>$cpunum){
		$GLOBALS["SYSTEM_INTERNAL_LOAD"]=$internal_load;
		return true;
		
	}
	return false;

	
}

function clean_artica_workfiles($dir,$maxtime=0){
	$unix=new unix();
	if(!is_dir($dir)){return;}
	if($maxtime==0){$maxtime=240;}
	foreach (glob("$dir/*") as $filepath) {
		$time=$unix->file_time_min($filepath);
		if($time>$maxtime){@unlink($filepath);}
		
	}
}

function CleanKav4ProxyLogs(){
	if(!is_dir("/var/log/kaspersky/kav4proxy")){return;}
	$unix=new unix();
	$echo =$unix->find_program("echo");
	$RELOAD=false;
	if (!$handle = opendir("/var/log/kaspersky/kav4proxy")) {return;}
	$unix=new unix();
	while (false !== ($fileZ = readdir($handle))) {
		if($fileZ=="."){continue;}
		if($fileZ==".."){continue;}
		$filename="/var/log/kaspersky/kav4proxy/$fileZ";
		if(is_dir($filename)){continue;}
		$time=$unix->file_time_min($filename);
		if($time>120){@unlink($filename);continue;}
		if(preg_match("#\.gz$#", $fileZ)){@unlink($filename);continue;}
		$size=@filesize($filename);
		$size=$size/1024;
		$size=$size/1024;
		if($size>100){
			shell_exec("$echo \"\" >$filename");
			$RELOAD=true;
		}
		
	}
	if (!$handle = opendir("/var/log/kaspersky/kav4proxy/core")) {return;}
	$unix=new unix();
	while (false !== ($fileZ = readdir($handle))) {
		if($fileZ=="."){continue;}
		if($fileZ==".."){continue;}
		$filename="/var/log/kaspersky/kav4proxy/core/$fileZ";
		if(is_dir($filename)){continue;}
		$time=$unix->file_time_min($filename);
		if($time>120){@unlink($filename);continue;}
		if(preg_match("#\.gz$#", $fileZ)){@unlink($filename);continue;}
		$size=@filesize($filename);
		$size=$size/1024;
		$size=$size/1024;
		if($size>100){
			shell_exec("$echo \"\" >$filename");
			$RELOAD=true;
		}
	
	}
	
	if($RELOAD){
		
		if(is_file("/etc/init.d/kav4proxy")){
			squid_admin_mysql(1, "Reloading Kaspersky For Proxy server after cleaned logs",null,__FILE__,__LINE__);
			system("/etc/init.d/kav4proxy reload");
		}
	}
	
	
	
	
}


function CleanDirLogs($path){
	return;
	if($GLOBALS["VERBOSE"]){echo "CleanDirLogs($path)\n";}
	$BigSize=false;
	if($path=='/var/log'){$BigSize=true;}
	if($GLOBALS["ArticaMaxLogsSize"]<100){$GLOBALS["ArticaMaxLogsSize"]=100;}
	$maxday=$GLOBALS["MaxTempLogFilesDay"]*24;
	$maxday=$maxday*60; 
	$users=new usersMenus();
	$maillog_path=$users->maillog_path;	
	

	$unix=new unix();
	$sock=new sockets();

	$restartSyslog=false;
	if($path==null){return;}

	$countfile=0;
	foreach (glob("$path/*") as $filepath) {
		if($filepath==null){continue;}
		if(is_link($filepath)){continue;}
		if(is_dir($filepath)){continue;}
		if($filepath==$maillog_path){continue;}
		if($filepath=="/var/log/mail.log"){continue;}
		if(preg_match("#\/log\/artica-postfix\/#",$filepath)){continue;}
		
		$countfile++;
		if($countfile>500){
			if(is_overloaded()){
				$unix->send_email_events("Clean Files: [$path/*] System is overloaded ({$GLOBALS["SYSTEM_INTERNAL_LOAD"]}",
			"The clean logs function is stopped and wait a new schedule with best performances",
			"logs_cleaning");
			die();
			}
			$countfile=0;
		}
		
		
		usleep(300);
		$size=round(unix_file_size("$filepath")/1024);
		$time=$unix->file_time_min($filepath);
		$unix->events("$filepath $size Ko, {$time}Mn/{$maxday}Mn TTL");
		if($size>$GLOBALS["ArticaMaxLogsSize"]){
				if($GLOBALS["VERBOSE"]){echo "Delete $filepath\n";}
				$restartSyslog=true;
				$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
				$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;
				$GLOBALS["UNLINKED"][]=$filepath;
				@unlink($filepath);
				continue;
		}
		
		if($time>$maxday){
			$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
			$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;
			if($GLOBALS["VERBOSE"]){echo "Delete $filepath\n";}
			@unlink($filepath);
			$GLOBALS["UNLINKED"][]=$filepath;
			$restartSyslog=true;
			continue;
		}
		
		
	}
	


		if($restartSyslog){
			$unix->send_email_events("System log will be restarted",
			"Logs files was deleted and log daemons will be restarted
			".@implode("\n",$GLOBALS["UNLINKED"]),
			"logs_cleaning");
			$unix->RESTART_SYSLOG();
		}

}


function systemLogs(){
$f[]="/var/log/daemons/errors.log";
$f[]="/var/log/daemons/info.log";
$f[]="/var/log/daemons/warnings.log";

}



function unix_file_size($path){
	$unix=new unix();
	if($GLOBALS["stat"]==null){$GLOBALS["stat"]=$unix->find_program("stat");}
	$path=$unix->shellEscapeChars($path);
	exec("{$GLOBALS["stat"]} $path ",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#Size:\s+([0-9]+)\s+Blocks#",$line,$re)){
			$res=$re[1];break;
		}
	}
	if(!is_numeric($res)){$res=0;}
	return $res;
}

function MakeSpace(){
	if(is_dir("/usr/share/doc")){shell_exec("/bin/rm -rf /usr/share/doc");}
	$sock=new sockets();
	$EnableBackupPc=$sock->GET_INFO("EnableBackupPc");
	if(!is_numeric($EnableBackupPc)){$EnableBackupPc=0;}
	if($EnableBackupPc==0){
		if(is_dir("/var/lib/backuppc")){shell_exec("/bin/rm -rf /var/lib/backuppc");}
	}
	
	if(is_dir("/var/log/fai")){shell_exec("/bin/rm -rf /var/log/fai");}
	
}


function used_space(){
	$GLOBALS["OUTPUT"]=true;

}

function clean_space(){
	
	$unix=new unix();
	$Pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$PidTime="/etc/artica-postfix/pids/exec.clean.logs.php.clean_space.time";
	// /etc/artica-postfix/pids/exec.clean.logs.php.squidClean.time

	
	$pid=$unix->get_pid_from_file($Pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		if($GLOBALS["VERBOSE"]){echo "Aborting Task already running pid $pid ".__FUNCTION__."()\n";}
		return;
	}
	@file_put_contents($Pidfile, getmypid());

	if(!$GLOBALS["VERBOSE"]){
		$time=$unix->file_time_min($PidTime);
		if($time<240){echo "Only each 240mn\n";die();}

	}
	@unlink($PidTime);
	@file_put_contents($PidTime, time());
	
	$unix=new unix();
	$rm=$unix->find_program("rm");
	$CLEANED=array();
	
	$home_remove[]="/home/bwm-ng";
	$home_remove[]="/home/ntopng";
	$home_remove[]="/home/c-icap";
	
	$home_remove_files[]="/home/artica/tmp";
	
	$percent=$unix->DIRECTORY_USEPERCENT("/home");
	
	if($GLOBALS["VERBOSE"]){echo "Percent $percent\n";}
	
	if($percent>90){
		
		while (list ($a, $dirPath) = each ($home_remove)){
			if(!is_dir($dirPath)){continue;}
			if(is_link($dirPath)){continue;}
			if($unix->DIRECTORY_MountedOnDirAndDismount($dirPath)>0){
				if($unix->DIRECTORY_MountedOnDirAndDismount($dirPath)>0){ continue;}
			}
			shell_exec("$rm -rf $dirPath/*");
			$CLEANED[]=$dirPath;
			
		}
		

		while (list ($a, $dirPath) = each ($home_remove_files)){
			if(!is_dir($dirPath)){continue;}
			if(is_link($dirPath)){continue;}
			if($unix->DIRECTORY_MountedOnDirAndDismount($dirPath)>0){
				if($unix->DIRECTORY_MountedOnDirAndDismount($dirPath)>0){ continue;}
			}
			shell_exec("$rm -f $dirPath/*.tmp");
			shell_exec("$rm -f $dirPath/*.log");
			shell_exec("$rm -f $dirPath/*.txt");
			shell_exec("$rm -f $dirPath/*.gz");
			shell_exec("$rm -f $dirPath/*.tgz");
			shell_exec("$rm -f $dirPath/artica-*");
			$CLEANED[]=$dirPath;
				
		}		
		
		if(count($CLEANED)>0){
			$percent2=$unix->DIRECTORY_USEPERCENT("/home");
			if($percent2<$percent){
				squid_admin_mysql(2, "/home partition exceed 90% ({$percent}%) down to {$percent2}%", 
				"Cleaned directories was ".@implode("\n", $CLEANED),__FILE__,__LINE__);
			}
		}else{
			squid_admin_mysql(2, "/home partition exceed 90% ({$percent}%)",null,__FILE__,__LINE__);
		}
		
		
	}
}





?>