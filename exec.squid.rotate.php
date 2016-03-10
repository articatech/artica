<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.acls.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.mysql.syslogs.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
$GLOBALS["FORCE"]=false;
$GLOBALS["PROGRESS"]=false;
if(preg_match("#--progress#",implode(" ",$argv))){$GLOBALS["PROGRESS"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["FORCE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
writelogs("Task::{$GLOBALS["SCHEDULE_ID"]}:: Executed with ".@implode(" ", $argv)." ","MAIN",__FILE__,__LINE__);


if($argv[1]=="--test-nas"){BackupToNas_tests();die();}

build();

// A scanner /home/squid/access_logs

function build_progress_rotation($text,$pourc){
	if(!$GLOBALS["PROGRESS"]){return;}
	echo "{$pourc}% $text\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/squid.rotate.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	sleep(1);
}


function ifdirMounted($directory){
	
	$f=explode("\n",@file_get_contents("/proc/mounts"));
	while (list ($index, $line) = each ($f) ){
		if(strpos("    $line", "$directory")>0){return true;}
		
	}
}


function ss5_log($BackupMaxDaysDir){
	
	if(!is_file("/var/log/ss5/ss5.log")){return;}
	$sock=new sockets();
	$unix=new unix();
	$syslog=new mysql_storelogs();
	
	$LogsRotateDefaultSizeRotation=$sock->GET_INFO("LogsRotateDefaultSizeRotation");
	$SquidRotateOnlySchedule=intval($sock->GET_INFO("SquidRotateOnlySchedule"));
	if(!is_numeric($LogsRotateDefaultSizeRotation)){$LogsRotateDefaultSizeRotation=100;}
	if($SquidRotateOnlySchedule==1){$LogsRotateDefaultSizeRotation=0;}
	$FinalDirectory="$BackupMaxDaysDir/proxy/".date("Y")."/".date("m")."/".date("d");
	
	$size=@filesize("/var/log/ss5/ss5.log");
	$size=$size/1024;
	$size=$size/1024;
	$syslog->events("/var/log/ss5/ss5.log........: {$size}M",__FUNCTION__,__LINE__);
	if($size<$LogsRotateDefaultSizeRotation){
		$syslog->events("/var/log/ss5/ss5.log........: {$size}M but need {$LogsRotateDefaultSizeRotation}M, aborting",__FUNCTION__,__LINE__);
		return;
	}
	$destfile="$FinalDirectory/ss5.".time().".gz";
	$syslog->events("/var/log/ss5/ss5.log........: Compress to $destfile",__FUNCTION__,__LINE__);
	if(!$unix->compress("/var/log/ss5/ss5.log", $destfile)){
		squid_admin_mysql(1, "Unable to compress /var/log/ss5/ss5.log", null,__FILE__,__LINE__);
		return;
	}
	squid_admin_mysql(1, "Restart Socks Proxy for log rotation task.", null,__FILE__,__LINE__);
	@unlink("/var/log/ss5/ss5.log");
	shell_exec("/etc/init.d/ss5 restart");
	
}


function build(){
	
	$timefile="/etc/artica-postfix/pids/exec.squid.rotate.php.build.time";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	// /etc/artica-postfix/pids/exec.squid.rotate.php.build.time
	
	$sock=new sockets();
	$unix=new unix();
	$ls=$unix->find_program("ls");
	
	
	$pid=$unix->PIDOF_PATTERN(basename(__FILE__));
	$MyPid=getmypid();
	if($MyPid<>$pid){
		if($unix->process_exists($pid)){
			$timeFile=$unix->PROCESS_TIME_INT($pid);
			$pidCmdline=@file_get_contents("/proc/$pid/cmdline");
			if($timeFile<30){
				echo "Already PID $pid is running since {$timeFile}Mn\n";
				squid_admin_mysql(1, "[LOG ROTATION]: Skip task, already running $pid since {$timeFile}Mn", "Running: $pidCmdline",__FILE__,__LINE__);
				die();
			}else{
				squid_admin_mysql(1, "[LOG ROTATION]: Killing old task $pid running more than 30mn ({$timeFile}Mn)", "Running: $pidCmdline",__FILE__,__LINE__);
				$unix->KILL_PROCESS($pid);
			}
		}
	}
		
	@file_put_contents($pidfile, getmypid());
	
	if(!$GLOBALS["FORCE"]){
		$time=$unix->file_time_min($timefile);
		if($time<60){echo "Only each 60mn\n";die();}
		@unlink($timefile);
		@file_put_contents($timefile, time());
	}
	$syslog=new mysql_storelogs();
	$SquidLogRotateFreq=intval($sock->GET_INFO("SquidLogRotateFreq"));
	if($SquidLogRotateFreq<10){$SquidLogRotateFreq=1440;}
	$LastRotate=$unix->file_time_min("/etc/artica-postfix/pids/squid-rotate-cache.time");
	$LogsRotateDefaultSizeRotation=$sock->GET_INFO("LogsRotateDefaultSizeRotation");
	$SquidRotateOnlySchedule=intval($sock->GET_INFO("SquidRotateOnlySchedule"));
	if(!is_numeric($LogsRotateDefaultSizeRotation)){$LogsRotateDefaultSizeRotation=100;}

	$BackupMaxDaysDir=$sock->GET_INFO("BackupMaxDaysDir");
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
	$BackupSquidLogsUseNas=intval($sock->GET_INFO("BackupSquidLogsUseNas"));
	
	$SquidRotateAutomount=intval($sock->GET_INFO("SquidRotateAutomount"));
	$SquidRotateClean=intval($sock->GET_INFO("SquidRotateClean"));
	$SquidRotateAutomountRes=$sock->GET_INFO("SquidRotateAutomountRes");
	$SquidRotateAutomountFolder=$sock->GET_INFO("SquidRotateAutomountFolder");
	
	
	if($SquidRotateAutomount==1){
		shell_exec("$ls /automounts/$SquidRotateAutomountRes >/dev/null 2>&1");
		if(ifdirMounted("/automounts/$SquidRotateAutomountRes")){
			$BackupSquidLogsUseNas=0;
			$BackupMaxDaysDir="/automounts/$SquidRotateAutomountRes/$SquidRotateAutomountFolder";
				
		}else{
			$syslog->events("/automounts/$SquidRotateAutomountRes not mounted",__FUNCTION__,__LINE__);
			squid_admin_mysql(1, "[ROTATE],Auto-mount $SquidRotateAutomountRes not mounted", null,__FILE__,__LINE__);
		}
		
	}
	
	
	$BackupMaxDaysDir=str_replace("//", "/", $BackupMaxDaysDir);
	$BackupMaxDaysDir=str_replace("\\", "/", $BackupMaxDaysDir);
	if(!is_dir($BackupMaxDaysDir)){@mkdir($BackupMaxDaysDir,0755,true);}

	if(!is_dir($BackupMaxDaysDir)){
		$syslog->events("$BackupMaxDaysDir not such directory or permission denied",__FUNCTION__,__LINE__);
		squid_admin_mysql(1, "[ROTATE],$BackupMaxDaysDir not such directory or permission denied", null,__FILE__,__LINE__);
		if($SquidRotateAutomount==1){
			$BackupMaxDaysDir=$sock->GET_INFO("BackupMaxDaysDir");
			if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
			if(!is_dir($BackupMaxDaysDir)){@mkdir($BackupMaxDaysDir,0755,true);}
			$syslog->events("Return back to $BackupMaxDaysDir",__FUNCTION__,__LINE__);
		}else{
			return;
		}
	}
	
	
	$php=$unix->LOCATE_PHP5_BIN();
	$hostname=$unix->hostname_g();
	$InFluxBackupDatabaseDir=$sock->GET_INFO("InFluxBackupDatabaseDir");
	if($InFluxBackupDatabaseDir==null){
		$InFluxBackupDatabaseDir="/home/artica/influx/backup";
	}
	
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
	$LogRotateAccess="$LogRotatePath/access";
	$LogRotateTail="$LogRotatePath/tail";
	$LogRotateCache="$LogRotatePath/cache";
	$syslog->events("Launch rotation only by schedule.: $SquidRotateOnlySchedule",__FUNCTION__,__LINE__);
	$syslog->events("SquidLogRotateFreq...............: {$SquidLogRotateFreq}Mn",__FUNCTION__,__LINE__);
	$syslog->events("LastRotate.......................: {$LastRotate}Mn",__FUNCTION__,__LINE__);
	$syslog->events("Working directory................: $LogRotatePath",__FUNCTION__,__LINE__);
	$syslog->events("Launch rotation when exceed......: {$LogsRotateDefaultSizeRotation}M",__FUNCTION__,__LINE__);
	$syslog->events("Final storage directory..........: {$BackupMaxDaysDir}",__FUNCTION__,__LINE__);
	$syslog->events("Backup files to a NAS............: {$BackupSquidLogsUseNas}",__FUNCTION__,__LINE__);
	
	ss5_log($BackupMaxDaysDir);
	
	
	if ($handle = opendir("/var/run/squid")){
		while (false !== ($file = readdir($handle))) {
			if ($file == "." ){continue;}
			if($file == "..") {continue;}
			$path="/var/run/squid/$file";
			if(preg_match("#\.[0-9]+\.status$#", $file)){
				$time=$unix->file_time_min($path);
				if($time>1440){
					$syslog->events("Removing $path",__FUNCTION__,__LINE__);
					@unlink($path);
				}
				continue;
			}
			if(preg_match("#\.[0-9]+\.state$#", $file)){
				$time=$unix->file_time_min($path);
				if($time>1440){
					$syslog->events("Removing $path",__FUNCTION__,__LINE__);
					@unlink($path);
				}
				continue;
			}			
		}
		
		
	}
	
	
	
	
	
	$size=@filesize("/var/log/squid/access.log");
	$size=$size/1024;
	$size=$size/1024;
	$syslog->events("/var/log/squid/access.log........: {$size}M",__FUNCTION__,__LINE__);
	
	
	$syslog->events("Analyze /var/log/squid directory for cache.log",__FUNCTION__,__LINE__);
	if (!$handle = opendir("/var/log/squid")){
		build_progress_rotation("Unable to open /var/log/squid",110);
		$syslog->events("Unable to open /var/log/squid directory.",__FUNCTION__,__LINE__);
		return;
		
	}
	
	build_progress_rotation("Scanning /var/log/squid",40);
	while (false !== ($file = readdir($handle))) {
		if ($file != "." && $file != "..") {
			$path="/var/log/squid/$file";
			if(is_dir($path)){continue;}
			if(!preg_match("#^cache\.log\.[0-9]+$#", $file)){continue;}
			@mkdir("$LogRotateCache",0755,true);
			$size=@filesize($path);
			$size=$size/1024;
			$size=$size/1024;
			$destfile="$LogRotateCache/$file.".time().".log";
			if(!@copy($path, $destfile)){
				$syslog->events("Unable to copy $path to $destfile",__FUNCTION__,__LINE__);
				@unlink($destfile);
				continue;			
			}
			$syslog->events("Removed $path",__FUNCTION__,__LINE__);
			@unlink($path);
		}
	}
	
	$syslog->events("Analyze /var/log/squid directory for access.log",__FUNCTION__,__LINE__);
	if (!$handle = opendir("/var/log/squid")){
		$syslog->events("Unable to open /var/log/squid directory.",__FUNCTION__,__LINE__);
		return;
	
	}
	@mkdir($LogRotateAccess,0755,true);
	@mkdir($LogRotateTail,0755,true);
	
	
	while (false !== ($file = readdir($handle))) {
		if ($file != "." && $file != "..") {
			$path="/var/log/squid/$file";
			if(is_dir($path)){continue;}
			
			if(preg_match("#^childs-access\.log\.[0-9]+$#", $file)){
				$destfile="$LogRotateAccess/$file.".time().".log";
				if(!@copy($path, $destfile)){
					$syslog->events("Unable to copy $path to $destfile",__FUNCTION__,__LINE__);
					@unlink($destfile);
					continue;
				}
				$syslog->events("Removed $path",__FUNCTION__,__LINE__);
				@unlink($path);
				continue;
			}
			
			$syslog->events("Analyze $file ^squidtail\.log\.[0-9]+$",__FUNCTION__,__LINE__);
			if(preg_match("#^squidtail\.log\.[0-9]+$#", $file)){
				$destfile="$LogRotateTail/$file";
				if(!@copy($path, $destfile)){
					$syslog->events("Unable to copy $path to $destfile",__FUNCTION__,__LINE__);
					@unlink($destfile);
					continue;
				}
				$syslog->events("Removed $path",__FUNCTION__,__LINE__);
				@unlink($path);
				continue;
			}
			$syslog->events("Analyze $file ^access\.log\.[0-9]+$",__FUNCTION__,__LINE__);
			
			if(!preg_match("#^access\.log\.[0-9]+$#", $file)){continue;}
			@mkdir("$LogRotatePath",0755,true);
			
			$destfile="$LogRotateAccess/$file.".time().".log";
			if(!@copy($path, $destfile)){
				$syslog->events("Unable to copy $path to $destfile",__FUNCTION__,__LINE__);
				@unlink($destfile);
				continue;
			}
			$syslog->events("Removed $path",__FUNCTION__,__LINE__);
			@unlink($path);
		}
	}
	
	
	$syslog->events("Analyze $LogRotateAccess for access.log",__FUNCTION__,__LINE__);
	
	if (!$handle = opendir($LogRotateAccess)){
		$syslog->events("Unable to open $LogRotateAccess directory.",__FUNCTION__,__LINE__);
		return;
	}
	
	$ROTATED=false;
	
	while (false !== ($file = readdir($handle))) {
		if ($file != "." && $file != "..") {
			$path="$LogRotateAccess/$file";
			echo "OPEN $path\n";
			if(is_dir($path)){continue;}
			if(!preg_match("#^access\.log#", $file)){continue;}
			range_fichier_source($path,$BackupMaxDaysDir);
			$ROTATED=true;
		}
	}
	
	
	if ($handle = opendir($LogRotateTail)){
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				$path="$LogRotateTail/$file";
				echo "OPEN $path\n";
				if(is_dir($path)){continue;}
				if(!preg_match("#^squidtail\.log#", $file)){continue;}
				range_fichier_tail($path,$BackupMaxDaysDir);
				$ROTATED=true;
			}
		}	
			
	}
	
	
	
	if(!$ROTATED){return;}
	
	$q=new mysql();
	
	$sql="CREATE TABLE IF NOT EXISTS `backuped_logs` (
			  `path` CHAR(255)  NOT NULL,
			  `zDate`  DATETIME,
			  `size` INT UNSIGNED NOT NULL,
			  PRIMARY KEY (`path`),
			  KEY `zDate` (`zDate`)
			)  ENGINE = MYISAM;";
	$q->QUERY_SQL($sql,"artica_events");
	
	$syslog->events("Analyze /home/logrotate/work for access.log",__FUNCTION__,__LINE__);
	build_progress_rotation("Scanning /home/logrotate/work",45);
	analyze_directory("/home/logrotate/work",$BackupMaxDaysDir);
	
	$BackupMaxDaysDir2=$sock->GET_INFO("BackupMaxDaysDir");
	if($BackupMaxDaysDir2==null){$BackupMaxDaysDir2="/home/logrotate_backup";}
	
	if($BackupMaxDaysDir2<>$BackupMaxDaysDir){
		build_progress_rotation("Scanning $BackupMaxDaysDir2",46);
		$syslog->events("$BackupMaxDaysDir2 is different of $BackupMaxDaysDir",__FUNCTION__,__LINE__);
		analyze_directory($BackupMaxDaysDir2,$BackupMaxDaysDir);
	}
	
	
	build_progress_rotation("Scanning /home/logrotate/merged",47);
	analyze_garbage_directory("/home/logrotate/merged",$BackupMaxDaysDir,1440);
	
	build_progress_rotation("Scanning $LogRotateCache",48);
	analyze_cache_directory($LogRotateCache,$BackupMaxDaysDir);
	
	build_progress_rotation("Scanning /home/logrotate/work",49);
	analyze_cache_directory("/home/logrotate/work",$BackupMaxDaysDir);
	
	build_progress_rotation("Scanning /home/squid/cache-logs",49);
	analyze_cache_directory("/home/squid/cache-logs",$BackupMaxDaysDir);
	

	if($GLOBALS["VERBOSE"]){echo "TRUNCATE TABLE backuped_logs !!!\n";}
	$q->QUERY_SQL("TRUNCATE TABLE backuped_logs","artica_events");
	if($BackupSquidLogsUseNas==1){
		build_progress_rotation("Backup to N.A.S",50);
		BackupToNas($BackupMaxDaysDir);
		build_progress_rotation("Backup to N.A.S BigData backups",50);
		BackupToNas($InFluxBackupDatabaseDir,false);
				
	}else{
		build_progress_rotation("Scanning $BackupMaxDaysDir",50);
		analyze_destination_directory($BackupMaxDaysDir);
	}
	
	@file_put_contents("/etc/artica-postfix/settings/Daemons/BackupMaxDaysDirCurrentSize", $unix->DIRSIZE_KO($BackupMaxDaysDir));
	@chmod("/etc/artica-postfix/settings/Daemons/BackupMaxDaysDirCurrentSize",0777);
	return;
}

function analyze_destination_directory($path){
	$unix=new unix();
	$find=$unix->find_program("find");
	$sock=new sockets();
	$SquidRotateClean=intval($sock->GET_INFO("SquidRotateClean"));
	
	if($GLOBALS["VERBOSE"]){echo "$find \"$path\" 2>&1\n";}
	exec("$find \"$path\" 2>&1",$results);
	$q=new mysql();
	$q->QUERY_SQL("TRUNCATE TABLE backuped_logs","artica_events");
	$f=array();
	while (list ($index, $filepath) = each ($results) ){
		
		if($GLOBALS["VERBOSE"]){echo "analyze_destination_directory $filepath\n";}
		
		if(is_dir($filepath)){continue;}
		$size=filesize($filepath);
		$basename=basename($filepath);
		//echo $basename." $size\n";
		
		
		
		
		if(!preg_match("#([0-9]+)-([0-9]+)-([0-9]+)_([0-9]+)-([0-9]+)-([0-9]+)--([0-9]+)-([0-9]+)-([0-9]+)_([0-9]+)-([0-9]+)-([0-9]+)\.gz$#", $basename,$re)){
			if($GLOBALS["VERBOSE"]){echo "$basename NO MATCH!\n";}
			continue;
		}
		
		
		
		$zdate=("{$re[1]}-{$re[2]}-{$re[3]} {$re[4]}:{$re[5]}:{$re[6]}");
		if($GLOBALS["VERBOSE"]){echo "('$filepath','$zdate','$size')\n";}
		$f[]="('$filepath','$zdate','$size')";
		
	}
	if(count($f)>0){
		$q->QUERY_SQL("INSERT IGNORE INTO `backuped_logs` (`path`,`zDate`,`size`) VALUES ".@implode(",", $f),"artica_events");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	if($SquidRotateClean==0){return;}
	$BackupMaxDays=intval($sock->GET_INFO("BackupMaxDays"));
	if($BackupMaxDays<5){$BackupMaxDays=30;}
	
	$sql="SELECT `path` FROM `backuped_logs` WHERE zDate < DATE_SUB(NOW(),INTERVAL $BackupMaxDays DAY) ";
	$results=$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){
		squid_admin_mysql(0, "MySQL error", "$q->mysql_error",__FILE__,__LINE__);
		return;
	}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$path=$ligne["path"];
		if(preg_match("#^\/mnt\/#", $path)){continue;}
		$basename=basename($path);
		if(!is_file($path)){$q->QUERY_SQL("DELETE FROM backuped_logs WHERE `path`='$path'","artica_events");continue;}
		@unlink($path);
		squid_admin_mysql(2, "$basename was deleted ( exceed $BackupMaxDays days)", null,__FILE__,__LINE__);
		
	}
	
	
}


function analyze_garbage_directory($directory,$BackupMaxDaysDir,$maxtime){
	$syslog=new mysql_storelogs();
	$unix=new unix();
	if(!is_dir($directory)){
		$syslog->events("$directory is not a directory, aborting",__FUNCTION__,__LINE__);
		return;
	}
	if (!$handle = opendir($directory)){
		$syslog->events("$directory failed to parse",__FUNCTION__,__LINE__);
		return;
	}
	
	while (false !== ($file = readdir($handle))) {
		if ($file == "." ){continue;}
		if ($file == ".." ){continue;}
		$path="$directory/$file";
		if(is_dir($path)){continue;}
		if(!preg_match("#^access\.log#", $file)){continue;}
		$time=$unix->file_time_min($path);
		if($path<$maxtime){continue;}
		range_fichier_source($path,$BackupMaxDaysDir,true);
	}
	
	
}






function analyze_directory($directory,$BackupMaxDaysDir){
	$syslog=new mysql_storelogs();
	if(!is_dir($directory)){
		$syslog->events("$directory is not a directory, aborting",__FUNCTION__,__LINE__);
		return;
	}
	if (!$handle = opendir($directory)){
		$syslog->events("$directory failed to parse",__FUNCTION__,__LINE__);
		return;
	}
	
	while (false !== ($file = readdir($handle))) {
		if ($file == "." ){continue;}
		if ($file == ".." ){continue;}
		$path="$directory/$file";
		if(is_dir($path)){continue;}
		if(preg_match("#^dmesg\.[0-9]+\.gz$#", $file)){@unlink($path);continue;}
		if(!preg_match("#^access\.log#", $file)){continue;}
		range_fichier_source($path,$BackupMaxDaysDir,true);
	}
	
}

function analyze_cache_directory($directory,$BackupMaxDaysDir){
	$syslog=new mysql_storelogs();
	if(!is_dir($directory)){
		$syslog->events("$directory is not a directory, aborting",__FUNCTION__,__LINE__);
		return;
	}
	if (!$handle = opendir($directory)){
		$syslog->events("$directory failed to parse",__FUNCTION__,__LINE__);
		return;
	}

	while (false !== ($file = readdir($handle))) {
		if ($file == "." ){continue;}
		if ($file == ".." ){continue;}
		$path="$directory/$file";
		if(is_dir($path)){continue;}
		if(!preg_match("#^cache\.log#", $file)){continue;}
		range_fichier_cache($path,$BackupMaxDaysDir,true);
	}

}


function range_fichier_cache($filepath,$BackupMaxDaysDir){
	$syslog=new mysql_storelogs();
	$unix=new unix();
	$ext=$unix->file_extension($filepath);
	$hostname=$unix->hostname_g();
	$sock=new sockets();
	
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
	$LogRotateCache="$LogRotatePath/cache";
	$LogRotateCacheFailed="$LogRotatePath/cache-failed";
	@mkdir($LogRotateCache,0755,true);
	$basename=basename($filepath);
	$syslog->events("Analyze $filepath [$ext] ",__FUNCTION__,__LINE__);
	if($ext=="gz"){
		if(preg_match("#\.tar\.gz$#", $basename)){
			$syslog->events("$filepath is a tarball!",__FUNCTION__,__LINE__);
			return;
		}
	
		$syslog->events("Extract $filepath",__FUNCTION__,__LINE__);
		$ExtractedFile="$LogRotateCache/$basename.log";
		if(!$unix->uncompress($filepath,$ExtractedFile )){
			@unlink($ExtractedFile);
			$syslog->events("Unable to extract $filepath to $ExtractedFile",__FUNCTION__,__LINE__);
			return;
		}
		$syslog->events("Removing $filepath [$ext] ",__FUNCTION__,__LINE__);
		@unlink($filepath);
		$filepath=$ExtractedFile;
	}
	
	$unix=new unix();
	$ztimes=cache_logs_getdates($filepath);
	if(!$ztimes){
		$syslog->events("Failed to parse $filepath",__FUNCTION__,__LINE__);
		@mkdir($LogRotateCacheFailed,0755,true);
		if(@copy($filepath, "$LogRotateCacheFailed/$basename")){
			@unlink($filepath);
		}
		return false;
	}
	
	
	
	
	$xdatefrom=$ztimes[0];
	$xdateTo=$ztimes[1];
	$dateFrom=date("Y-m-d_H-i-s",$xdatefrom);
	$dateTo=date("Y-m-d_H-i-s",$xdateTo);
	$NewFileName="cache-".filename_from_arraydates($ztimes);	
	
	$FinalDirectory="$BackupMaxDaysDir/proxy/".date("Y",$xdatefrom)."/".date("m",$xdatefrom)."/".date("d",$xdatefrom);
	@mkdir($FinalDirectory,0755,true);
	
	if(!is_dir($FinalDirectory)){
		$syslog->events("Unable to create $FinalDirectory directory permission denied",__FUNCTION__,__LINE__);
		return;
	}
	
	if(!$unix->compress($filepath, "$FinalDirectory/$NewFileName")){
		@unlink("$FinalDirectory/$NewFileName");
		$syslog->events("Unable to compress $FinalDirectory/$NewFileName permission denied",__FUNCTION__,__LINE__);
		return;
	}
	
	$syslog->events("Success to create $FinalDirectory/$NewFileName",__FUNCTION__,__LINE__);
	$syslog->events("Removing source file $filepath",__FUNCTION__,__LINE__);
	@unlink($filepath);	
	
	

}




function filename_from_arraydates($ztimes,$suffix=null){
	$unix=new unix();
	$hostname=$unix->hostname_g();
	$xdatefrom=$ztimes[0];
	$xdateTo=$ztimes[1];
	$dateFrom=date("Y-m-d_H-i-s",$xdatefrom);
	$dateTo=date("Y-m-d_H-i-s",$xdateTo);
	if($suffix<>null){$suffix=".$suffix.";}
	return "$hostname.{$suffix}$dateFrom--$dateTo.gz";
}

function CLEAN_OLD_LOGS(){
	
	
}


function range_fichier_tail($filepath,$BackupMaxDaysDir,$EXTERN=false){
	$syslog=new mysql_storelogs();
	$unix=new unix();
	$ext=$unix->file_extension($filepath);
	$hostname=$unix->hostname_g();
	$basename=basename($filepath);
	$sock=new sockets();
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
	$LogRotateAccess="$LogRotatePath/access";
	$LogRotateAccessFailed="$LogRotatePath/failed";
	$LogRotateAccessMerged="$LogRotatePath/merged";
	$SquidRotateMergeFiles=$sock->GET_INFO("SquidRotateMergeFiles");
	if(!is_numeric($SquidRotateMergeFiles)){$SquidRotateMergeFiles=0;}	
	$syslog->events("Analyze $filepath [$ext] ",__FUNCTION__,__LINE__);
	if($ext=="gz"){
		if(preg_match("#\.tar\.gz$#", $basename)){
			$syslog->events("$filepath is a tarball!",__FUNCTION__,__LINE__);
			return;
		}
	
		$syslog->events("Extract $filepath",__FUNCTION__,__LINE__);
		$ExtractedFile="$LogRotateAccess/$basename.log";
		if(!$unix->uncompress($filepath,$ExtractedFile )){
			@unlink($ExtractedFile);
			$syslog->events("Unable to extract $filepath to $ExtractedFile",__FUNCTION__,__LINE__);
			return;
		}
		$syslog->events("Removing $filepath [$ext] ",__FUNCTION__,__LINE__);
		@unlink($filepath);
		$filepath=$ExtractedFile;
	}	
	
	
	$unix=new unix();
	$ztimes=access_tail_getdates($filepath);
	if(!$ztimes){
		$syslog->events("Failed to parse $filepath",__FUNCTION__,__LINE__);
		@mkdir($LogRotateAccessFailed,0755,true);
		if(@copy($filepath, "$LogRotateAccessFailed/$basename")){
			@unlink($filepath);
		}
		return false;
	}
	
	
	$xdatefrom=$ztimes[0];
	$xdateTo=$ztimes[1];
	$dateFrom=date("Y-m-d_H-i-s",$xdatefrom);
	$dateTo=date("Y-m-d_H-i-s",$xdateTo);
	$NewFileName="access-tail.".filename_from_arraydates($ztimes);
	
	$FinalDirectory="$BackupMaxDaysDir/proxy/".date("Y",$xdatefrom)."/".date("m",$xdatefrom)."/".date("d",$xdatefrom);
	@mkdir($FinalDirectory,0755,true);
	
	if(!is_dir($FinalDirectory)){
		$syslog->events("Unable to create $FinalDirectory directory permission denied",__FUNCTION__,__LINE__);
		return;
	}
	
	if(!$unix->compress($filepath, "$FinalDirectory/$NewFileName")){
		@unlink("$FinalDirectory/$NewFileName");
		$syslog->events("Unable to compress $FinalDirectory/$NewFileName permission denied",__FUNCTION__,__LINE__);
		return;
	}
	
	$syslog->events("Success to create $FinalDirectory/$NewFileName",__FUNCTION__,__LINE__);
	$syslog->events("Removing source file $filepath",__FUNCTION__,__LINE__);
	@unlink($filepath);
	
}


function access_tail_getdates($file){
	$syslog=new mysql_storelogs();
	$unix=new unix();

	$YEAROK["2012"]=true;
	$YEAROK["2013"]=true;
	$YEAROK["2014"]=true;
	$YEAROK["2015"]=true;
	$YEAROK[date("Y")]=true;


	$array=$unix->readlastline($file,8);
	if(!is_array($array)){return false;}
	$Ttime=0;
	while (list ($filname, $line) = each ($array) ){
		$re=explode(":::", $line);
		$xtime=strtotime($re[4]);
		if(count($re)<4){continue;}
		$zdate=date("Y-m-d H:i:s",$xtime);
		$zDyear=date("Y",$xtime);
		if(!isset($YEAROK[$zDyear])){continue;}
		$time=strtotime($zdate);
		if($time>$Ttime){$Ttime=$time;}
	}

	if($Ttime==0){return false;}
	echo "$file Last Time $Ttime: ".date("Y-m-d H:i:s",$Ttime)."\n";
	$LAST_TIME=$Ttime;

	$array=$unix->readFirstline($file,8);
	if(!is_array($array)){return false;}
	$MyTime=time();
	$Ttime=$MyTime;
	while (list ($filname, $line) = each ($array) ){
		$re=explode(":::", $line);
		if(count($re)<4){continue;}
		$xtime=strtotime($re[4]);
		$zdate=date("Y-m-d H:i:s",$xtime);
		$zDyear=date("Y",$xtime);
		if(!isset($YEAROK[$zDyear])){continue;}
		$time=strtotime($zdate);
		if($time<$Ttime){$Ttime=$time;}
	}

	if($Ttime==0){return false;}


	if($Ttime==$MyTime){return false;}
	echo "$file First Time $Ttime: ".date("Y-m-d H:i:s",$Ttime)."\n";
	$FIRST_TIME=$Ttime;


	return array($FIRST_TIME,$LAST_TIME);
}


function range_fichier_source($filepath,$BackupMaxDaysDir,$EXTERN=false){
	$syslog=new mysql_storelogs();
	$unix=new unix();
	$ext=$unix->file_extension($filepath);
	$hostname=$unix->hostname_g();
	$sock=new sockets();
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
	$LogRotateAccess="$LogRotatePath/access";
	$LogRotateAccessFailed="$LogRotatePath/failed";
	$LogRotateAccessMerged="$LogRotatePath/merged";
	$SquidRotateMergeFiles=$sock->GET_INFO("SquidRotateMergeFiles");
	if(!is_numeric($SquidRotateMergeFiles)){$SquidRotateMergeFiles=0;}

	$basename=basename($filepath);
	if($basename=="access.merged.log"){return;}
	
	$syslog->events("Analyze $filepath [$ext] ",__FUNCTION__,__LINE__);
	if($ext=="gz"){
		if(preg_match("#\.tar\.gz$#", $basename)){
			$syslog->events("$filepath is a tarball!",__FUNCTION__,__LINE__);
			return;
		}
		
		$syslog->events("Extract $filepath",__FUNCTION__,__LINE__);
		$ExtractedFile="$LogRotateAccess/$basename.log";
		if(!$unix->uncompress($filepath,$ExtractedFile )){
			@unlink($ExtractedFile);
			$syslog->events("Unable to extract $filepath to $ExtractedFile",__FUNCTION__,__LINE__);
			return;
		}
		$syslog->events("Removing $filepath [$ext] ",__FUNCTION__,__LINE__);
		@unlink($filepath);
		$filepath=$ExtractedFile;
	}
	
	$unix=new unix();
	$ztimes=access_logs_getdates($filepath);
	if(!$ztimes){
		$syslog->events("Failed to parse $filepath",__FUNCTION__,__LINE__);
		@mkdir($LogRotateAccessFailed,0755,true);
		if(@copy($filepath, "$LogRotateAccessFailed/$basename")){
			@unlink($filepath);
		}
		return false;
	}
	
	
	
	
	$xdatefrom=$ztimes[0];
	$xdateTo=$ztimes[1];
	$dateFrom=date("Y-m-d_H-i-s",$xdatefrom);
	$dateTo=date("Y-m-d_H-i-s",$xdateTo);
	$NewFileName=filename_from_arraydates($ztimes);
	
	if($SquidRotateMergeFiles==1){
		@mkdir($LogRotateAccessMerged,0755,true);
		if(!is_dir($LogRotateAccessMerged)){
			$syslog->events("Unable to create Merged directory $LogRotateAccessMerged",__FUNCTION__,__LINE__);
		}else{
			if(!@copy($filepath, "$LogRotateAccessMerged/$basename")){
				@unlink("$LogRotateAccessMerged/$basename");
				$syslog->events("Unable to copy $filepath -> $LogRotateAccessMerged/$basename",__FUNCTION__,__LINE__);
			}
		}
		
	}
	
	
	$FinalDirectory="$BackupMaxDaysDir/proxy/".date("Y",$xdatefrom)."/".date("m",$xdatefrom)."/".date("d",$xdatefrom);
	@mkdir($FinalDirectory,0755,true);
		
	if(!is_dir($FinalDirectory)){
		$syslog->events("Unable to create $FinalDirectory directory permission denied",__FUNCTION__,__LINE__);
		return;
	}
	
	if(!$unix->compress($filepath, "$FinalDirectory/$NewFileName")){
		@unlink("$FinalDirectory/$NewFileName");
		$syslog->events("Unable to compress $FinalDirectory/$NewFileName permission denied",__FUNCTION__,__LINE__);
		return;
	}
	
	$syslog->events("Success to create $FinalDirectory/$NewFileName",__FUNCTION__,__LINE__);
	$syslog->events("Removing source file $filepath",__FUNCTION__,__LINE__);
	@unlink($filepath);
}

function cache_logs_getdates($file){
	$syslog=new mysql_storelogs();
	$unix=new unix();
	
	$Ttime=0;
	$array=$unix->readlastline($file,5);
	while (list ($filname, $line) = each ($array) ){
		if(!preg_match("#^([0-9\/]+)\s+([0-9:]+)#", $line,$re)){continue;}
		
		$time=strtotime("{$re[1]} {$re[2]}");
		if($time>$Ttime){$Ttime=$time;}
	}
	if($Ttime==0){return false;}
	echo "$file Last Time $Ttime: ".date("Y-m-d H:i:s",$Ttime)."\n";
	$LAST_TIME=$Ttime;
	
	$array=$unix->readFirstline($file,5);
	if(!is_array($array)){return false;}
	$MyTime=time();
	$Ttime=$MyTime;
	while (list ($filname, $line) = each ($array) ){
		if(!preg_match("#^([0-9\/]+)\s+([0-9:]+)#", $line,$re)){continue;}
		$time=strtotime("{$re[1]} {$re[2]}");
		if($time<$Ttime){$Ttime=$time;}
	}
	if($Ttime==$MyTime){return false;}
	echo "$file First Time $Ttime: ".date("Y-m-d H:i:s",$Ttime)."\n";
	$FIRST_TIME=$Ttime;
	
	
	return array($FIRST_TIME,$LAST_TIME);
	
}

function access_logs_getdates($file){
	$syslog=new mysql_storelogs();
	$unix=new unix();
	
	$YEAROK["2012"]=true;
	$YEAROK["2013"]=true;
	$YEAROK["2014"]=true;
	$YEAROK["2015"]=true;
	$YEAROK[date("Y")]=true;
	
	
	$array=$unix->readlastline($file,8);
	if(!is_array($array)){return false;}
	$Ttime=0;
	while (list ($filname, $line) = each ($array) ){
		if(!preg_match("#([0-9\.]+)\s+([\-0-9]+)\s+([0-9\.]+)#", $line,$re)){continue;}
		$zdate=date("Y-m-d H:i:s",$re[1]);
		$zDyear=date("Y",$re[1]);
		if(!isset($YEAROK[$zDyear])){continue;}
		$time=strtotime($zdate);
		if($time>$Ttime){$Ttime=$time;}
	}
	
	if($Ttime==0){return false;}
	echo "$file Last Time $Ttime: ".date("Y-m-d H:i:s",$Ttime)."\n";
	$LAST_TIME=$Ttime;
	
	$array=$unix->readFirstline($file,8);
	if(!is_array($array)){return false;}
	$MyTime=time();
	$Ttime=$MyTime;
	while (list ($filname, $line) = each ($array) ){
		if(!preg_match("#([0-9\.]+)\s+([\-0-9]+)\s+([0-9\.]+)#", $line,$re)){continue;}
		$zdate=date("Y-m-d H:i:s",$re[1]);
		$zDyear=date("Y",$re[1]);
		if(!isset($YEAROK[$zDyear])){continue;}
		$time=strtotime($zdate);
		if($time<$Ttime){$Ttime=$time;}
	}
	
	if($Ttime==0){return false;}
	
	
	if($Ttime==$MyTime){return false;}
	echo "$file First Time $Ttime: ".date("Y-m-d H:i:s",$Ttime)."\n";
	$FIRST_TIME=$Ttime;
	
	
	return array($FIRST_TIME,$LAST_TIME);
}

function BackupToNas_tests(){
	$sock=new sockets();
	$syslog=new mysql_storelogs();
	$users=new usersMenus();
	$unix=new unix();
	$myHostname=$unix->hostname_g();
	$mount=new mount("/var/log/artica-postfix/logrotate.debug");
	$BackupSquidLogsNASIpaddr=$sock->GET_INFO("BackupSquidLogsNASIpaddr");
	$BackupSquidLogsNASFolder=$sock->GET_INFO("BackupSquidLogsNASFolder");
	$BackupSquidLogsNASUser=$sock->GET_INFO("BackupSquidLogsNASUser");
	$BackupSquidLogsNASPassword=$sock->GET_INFO("BackupSquidLogsNASPassword");
	$BackupSquidLogsNASRetry=$sock->GET_INFO("BackupSquidLogsNASRetry");
	if(!is_numeric($BackupSquidLogsNASRetry)){$BackupSquidLogsNASRetry=0;}
	$mount=new mount("/var/log/artica-postfix/logrotate.debug");
	$BackupSquidLogsNASIpaddr=$sock->GET_INFO("BackupSquidLogsNASIpaddr");
	$BackupSquidLogsNASFolder=$sock->GET_INFO("BackupSquidLogsNASFolder");
	$BackupSquidLogsNASUser=$sock->GET_INFO("BackupSquidLogsNASUser");
	$BackupSquidLogsNASPassword=$sock->GET_INFO("BackupSquidLogsNASPassword");
	$BackupSquidLogsNASRetry=$sock->GET_INFO("BackupSquidLogsNASRetry");
	if(!is_numeric($BackupSquidLogsNASRetry)){$BackupSquidLogsNASRetry=0;}	
	
	$GLOBALS["OUPUT_MOUNT_CLASS"]=true;
	build_progress("{APP_SQUID}::{use_remote_nas}", 10);
	
	echo "smb://$BackupSquidLogsNASIpaddr/$BackupSquidLogsNASFolder [$BackupSquidLogsNASUser]\n";
	
	if($BackupSquidLogsNASIpaddr==null){
		build_progress("{APP_SQUID}::{use_remote_nas} {disabled}", 110);
		echo "Backup via NAS is disabled, skip\n";
		return false;
	}
	
	
	build_progress("{APP_SQUID}::{use_remote_nas} TEST -1-", 20);
	$mountPoint="/mnt/BackupSquidLogsUseNas";
	if(!$mount->smb_mount($mountPoint,$BackupSquidLogsNASIpaddr,$BackupSquidLogsNASUser,$BackupSquidLogsNASPassword,$BackupSquidLogsNASFolder)){
		echo "Unable to connect to NAS storage system (1): $BackupSquidLogsNASUser@$BackupSquidLogsNASIpaddr\n";
		build_progress("{APP_SQUID}::{use_remote_nas} {failed}", 110);
		if($BackupSquidLogsNASRetry==0){return;}
		sleep(3);
		build_progress("{APP_SQUID}::{use_remote_nas} TEST -2-", 30);
		$mount=new mount("/var/log/artica-postfix/logrotate.debug");
		if(!$mount->smb_mount($mountPoint,$BackupSquidLogsNASIpaddr,$BackupSquidLogsNASUser,$BackupSquidLogsNASPassword,$BackupSquidLogsNASFolder)){
			echo "Unable to connect to NAS storage system (1): $BackupSquidLogsNASUser@$BackupSquidLogsNASIpaddr\n";
			build_progress("{APP_SQUID}::{use_remote_nas} {failed}", 110);
			return;
		}
	
	}	
	build_progress("{APP_SQUID}::{use_remote_nas}", 40);
	echo "Hostname=$myHostname $BackupSquidLogsNASIpaddr/$BackupSquidLogsNASFolder\n";
	$BackupMaxDaysDir="$mountPoint/artica-backup-syslog";
	@mkdir("$BackupMaxDaysDir",0755,true);
	
	if(!is_dir($BackupMaxDaysDir)){
		echo "Fatal $BackupMaxDaysDir permission denied\n";
		build_progress("{APP_SQUID}::{use_remote_nas} {failed}", 110);
		$mount->umount($mountPoint);
		return false;
	}	
	build_progress("{APP_SQUID}::{use_remote_nas}", 50);
	
	$t=time();
	@file_put_contents("$BackupMaxDaysDir/$t", time());
	if(!is_file("$BackupMaxDaysDir/$t")){
		echo "Fatal $BackupMaxDaysDir permission denied ($BackupMaxDaysDir/$t) test failed\n";
		$mount->umount($mountPoint);
		build_progress("{APP_SQUID}::{use_remote_nas} {failed}", 110);
		return false;
	}
	build_progress("{APP_SQUID}::{use_remote_nas} {success}", 95);
	build_progress("{APP_SQUID}::{use_remote_nas} {success}", 96);
	build_progress("{APP_SQUID}::{use_remote_nas} {success}", 97);
	build_progress("{APP_SQUID}::{use_remote_nas} {success}", 98);
	build_progress("{APP_SQUID}::{use_remote_nas} {success}", 99);
	build_progress("{APP_SQUID}::{use_remote_nas} {success}", 100);
	@unlink("$BackupMaxDaysDir/$t");
	$mount->umount($mountPoint);
	sleep(5);
		
	
	
}
function build_progress($text,$pourc){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "{$pourc}% $text\n";
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/squid.nas.storage.progress", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/web/squid.nas.storage.progress",0755);
	sleep(1);
}

function BackupToNas($directory,$AnalyzeDestination=true){
		if(!is_dir($directory)){return;}
		$syslog=new mysql_storelogs();
		$sock=new sockets();
		$users=new usersMenus();
		$unix=new unix();
		$myHostname=$unix->hostname_g();
		$DirSuffix=basename($directory);
		$mount=new mount("/var/log/artica-postfix/logrotate.debug");
		$BackupSquidLogsNASIpaddr=$sock->GET_INFO("BackupSquidLogsNASIpaddr");
		$BackupSquidLogsNASFolder=$sock->GET_INFO("BackupSquidLogsNASFolder");
		$BackupSquidLogsNASUser=$sock->GET_INFO("BackupSquidLogsNASUser");
		$BackupSquidLogsNASPassword=$sock->GET_INFO("BackupSquidLogsNASPassword");
		$BackupSquidLogsNASRetry=$sock->GET_INFO("BackupSquidLogsNASRetry");
		if(!is_numeric($BackupSquidLogsNASRetry)){$BackupSquidLogsNASRetry=0;}
		$mount=new mount("/var/log/artica-postfix/logrotate.debug");
		$BackupSquidLogsNASIpaddr=$sock->GET_INFO("BackupSquidLogsNASIpaddr");
		$BackupSquidLogsNASFolder=$sock->GET_INFO("BackupSquidLogsNASFolder");
		$BackupSquidLogsNASUser=$sock->GET_INFO("BackupSquidLogsNASUser");
		$BackupSquidLogsNASPassword=$sock->GET_INFO("BackupSquidLogsNASPassword");
		$BackupSquidLogsNASRetry=$sock->GET_INFO("BackupSquidLogsNASRetry");
		$BackupSquidLogsNASFolder2=$sock->GET_INFO("BackupSquidLogsNASFolder2");
		if(!is_numeric($BackupSquidLogsNASRetry)){$BackupSquidLogsNASRetry=0;}
		
		if($BackupSquidLogsNASFolder2==null){$BackupSquidLogsNASFolder2="artica-backup-syslog";}
		
		
		$mv=$unix->find_program("mv");

		if($BackupSquidLogsNASIpaddr==null){
			$this->events("Backup via NAS is disabled, skip",__FUNCTION__,__FILE__,__LINE__,"logrotate");
			return false;
		}

		$mountPoint="/mnt/BackupSquidLogsUseNas";
		if(!$mount->smb_mount($mountPoint,$BackupSquidLogsNASIpaddr,$BackupSquidLogsNASUser,$BackupSquidLogsNASPassword,$BackupSquidLogsNASFolder)){
			$syslog->events("Unable to connect to NAS storage system (1): $BackupSquidLogsNASUser@$BackupSquidLogsNASIpaddr",__FUNCTION__,__FILE__,__LINE__,"logrotate");
				
			if($BackupSquidLogsNASRetry==0){return;}
			sleep(3);
			$mount=new mount("/var/log/artica-postfix/logrotate.debug");
			if(!$mount->smb_mount($mountPoint,$BackupSquidLogsNASIpaddr,$BackupSquidLogsNASUser,$BackupSquidLogsNASPassword,$BackupSquidLogsNASFolder)){
				$syslog->events("Unable to connect to NAS storage system (1): $BackupSquidLogsNASUser@$BackupSquidLogsNASIpaddr",__FUNCTION__,__FILE__,__LINE__,"logrotate");
				return;
			}

		}

		
		$syslog->events("Hostname=$myHostname Suffix = $DirSuffix $BackupSquidLogsNASIpaddr/$BackupSquidLogsNASFolder",__FUNCTION__,__LINE__);
		
		if($BackupSquidLogsNASFolder2<>null){
			$BackupMaxDaysDir="$mountPoint/$BackupSquidLogsNASFolder2";
		}else{
			$BackupMaxDaysDir=$mountPoint;
		}
		
		
		@mkdir("$BackupMaxDaysDir",0755,true);

		if(!is_dir($BackupMaxDaysDir)){
			$syslog->events("Fatal $BackupMaxDaysDir permission denied",__FUNCTION__,__LINE__);
			if($GLOBALS["VERBOSE"]){echo "Fatal $BackupMaxDaysDir permission denied\n";}
			squid_admin_mysql(0,"SYSLOG: FATAL $BackupMaxDaysDir permission denied",null,__FILE__,__LINE__);
			$mount->umount($mountPoint);
			return false;
		}


		$t=time();
		@file_put_contents("$BackupMaxDaysDir/$t", time());
		if(!is_file("$BackupMaxDaysDir/$t")){
			$syslog->events("Fatal $BackupMaxDaysDir permission denied ($BackupMaxDaysDir/$t) test failed",__FUNCTION__,__LINE__);
			squid_admin_mysql(0,"SYSLOG: FATAL $BackupMaxDaysDir permission denied",null,__FILE__,__LINE__);
			$mount->umount($mountPoint);
			return false;
		}

		
		@unlink("$BackupMaxDaysDir/$t");
		moveAllFiles("$directory",$BackupMaxDaysDir);
		
		
		if($AnalyzeDestination){
			analyze_destination_directory($BackupMaxDaysDir."/proxy");
		}
		$mount->umount($mountPoint);
		return true;
}

function moveAllFiles($directory_from,$directoryTo){
	$unix=new unix();
	$find=$unix->find_program("find");
	$sock=new sockets();
	if($GLOBALS["VERBOSE"]){echo "$find \"$directory_from\" 2>&1\n";}
	exec("$find \"$directory_from/\" 2>&1",$results);
	while (list ($index, $filepath) = each ($results) ){
		if(is_dir($filepath)){continue;}
		$filename=basename($filepath);
		$dirname=dirname($filepath);
		$dirname=str_replace($directory_from, "", $dirname);
		$nextDir="$directoryTo/$dirname";
		$nextDir=str_replace("//", "/", $nextDir);
		if($GLOBALS["VERBOSE"]){echo "moveAllFiles: $filepath -> $nextDir\n";}
		if(!is_dir($nextDir)){@mkdir($nextDir,0755,true);}
		if(!is_dir($nextDir)){
			squid_admin_mysql(0,"SYSLOG: FATAL $nextDir permission denied",null,__FILE__,__LINE__);
			return;
		}
		$NextFile="$nextDir/$filename";
		$NextFile=str_replace("//", "/", $NextFile);
		$md5FileSource=md5_file($filepath);
		if(is_file($NextFile)){
			$md5FileDest=md5_file($NextFile);
			if($md5FileDest==$md5FileSource){
				if($GLOBALS["VERBOSE"]){echo "moveAllFiles: $filepath -> Already copied remove source\n";}
				@unlink($filepath);
				continue;
			}else{
				squid_admin_mysql(0,"SYSLOG: FATAL $filename cannot be copied (same file exists but integrity differ)",null,__FILE__,__LINE__);
				continue;
			}
		}
		
		
		
		@copy($filepath,$NextFile);
		if(!is_file($NextFile)){
			squid_admin_mysql(0,"SYSLOG: FATAL $filename permission denied or disk full (task aborted)",null,__FILE__,__LINE__);
			return false;
		}
		$md5FileDest=md5_file($NextFile);
		if($md5FileDest<>$md5FileSource){
			squid_admin_mysql(0,"SYSLOG: FATAL $filename corrupted, aborting (task aborted)",null,__FILE__,__LINE__);
			return false;
		}
		if($GLOBALS["VERBOSE"]){ echo "moveAllFiles: $filepath -> $NextFile Success\n";}
		@unlink($filepath);
	}
}


?>