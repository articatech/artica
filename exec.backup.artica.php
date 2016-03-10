<?php
ini_set('memory_limit','1000M');
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.samba.inc');
include_once(dirname(__FILE__).'/ressources/class.autofs.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.backup.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.cyrus.inc');
include_once(dirname(__FILE__).'/ressources/class.artica-meta.inc');
include_once(dirname(__FILE__).'/ressources/class.user.inc');
include_once(dirname(__FILE__).'/ressources/class.mount.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
$GLOBALS["COMMANDLINE"]=@implode(" ", $argv);
$GLOBALS["NOT_RESTORE_NETWORK"]=false;
$GLOBALS["SEND_META"]=false;
$GLOBALS["SNAPSHOT_NO_DELETE"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--nowachdog#",$GLOBALS["COMMANDLINE"])){$GLOBALS["DISABLE_WATCHDOG"]=true;}
if(preg_match("#--force#",$GLOBALS["COMMANDLINE"])){$GLOBALS["FORCE"]=true;}
if(preg_match("#--meta-ping#",$GLOBALS["COMMANDLINE"])){$GLOBALS["SEND_META"]=true;}
if(preg_match("#--not-remove#",$GLOBALS["COMMANDLINE"])){$GLOBALS["SNAPSHOT_NO_DELETE"]=true;}

if(preg_match("#--verbose#",$GLOBALS["COMMANDLINE"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if($argv[1]=="--restore"){restore();die();}
if($argv[1]=="--restore-squid"){restore_squidlogs($argv[2]);die();}



if($argv[1]=="--snapshot"){snapshot();die();}
if($argv[1]=="--snapshot-id"){snapshot_restore_sql($argv[2]);die();}
if($argv[1]=="--snapshot-file"){snapshot_restore($argv[2]);die();}
if($argv[1]=="--snapshot-import"){snapshot_import();die();}



execBackup();


function backupevents($text){
	$unix=new unix();
	$unix->events($text,"/var/log/artica-backup.log");
	
	
}

function TestNas(){
	$sock=new sockets();
	$unix=new unix();
	$hostname=$unix->hostname_g();
	$BackupArticaBackUseNas=intval($sock->GET_INFO("BackupArticaBackUseNas"));
	if($BackupArticaBackUseNas==0){if($GLOBALS["VERBOSE"]){echo "BackupArticaBackUseNas = $BackupArticaBackUseNas\n";} return false;}
	$BackupArticaBackNASIpaddr=$sock->GET_INFO("BackupArticaBackNASIpaddr");
	$BackupArticaBackNASFolder=$sock->GET_INFO("BackupArticaBackNASFolder");
	$BackupArticaBackNASUser=$sock->GET_INFO("BackupArticaBackNASUser");
	$BackupArticaBackNASPassword=$sock->GET_INFO("BackupArticaBackNASPassword");
	
	
	$mount=new mount("/var/log/artica-postfix/backup.debug");
	$mountPoint="/mnt/BackupArticaBackNAS";
	
	if($mount->ismounted($mountPoint)){ return true; }
	
	
	
	if(!$mount->smb_mount($mountPoint,$BackupArticaBackNASIpaddr,$BackupArticaBackNASUser,$BackupArticaBackNASPassword,$BackupArticaBackNASFolder)){
		system_admin_events("Mounting //$BackupArticaBackNASIpaddr/$BackupArticaBackNASFolder failed",__FUNCTION__,__FILE__,__LINE__);
		return false;
				
	}
	
	if(!$mount->ismounted($mountPoint)){
		return false;
	}
	
	$t=time();
	@file_put_contents("$mountPoint/$t", "#");
	if(!is_file("$mountPoint/$t")){
		system_admin_events("$BackupArticaBackNASUser@$BackupArticaBackNASIpaddr/$BackupArticaBackNASFolder/* permission denied.\n",__FUNCTION__,__FILE__,__LINE__);
		$mount->umount($mountPoint);
		return false;
	}
	@unlink("$mountPoint/$t");	
	@mkdir("$mountPoint/$hostname/system-backup",0755,true);
	return true;
}

function execBackup(){
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid)){
		$TTL=$unix->PROCESS_TTL($pid);
		if($TTL<240){return;}
		$kill=$unix->find_program("kill");
		unix_system_kill_force($pid);
	}
	
	@file_put_contents($pidfile, getmypid());
	$hostname=$unix->hostname_g();
	$sock=new sockets();
	$BackupArticaBackUseNas=intval($sock->GET_INFO("BackupArticaBackUseNas"));
	$BackupArticaBackLocalFolder=intval($sock->GET_INFO("BackupArticaBackLocalFolder"));
	$BackupArticaBackLocalDir=$sock->GET_INFO("BackupArticaBackLocalDir");
	if($BackupArticaBackLocalDir==null){$BackupArticaBackLocalDir="/home/artica/backup";}
	
	if($BackupArticaBackUseNas==0){
		if($BackupArticaBackLocalFolder==0){
			progress(100,"No destination defined");
		}
	}
	
	progress(10,"{mounting}");
	if($BackupArticaBackUseNas==1){
		if(!TestNas()){
			system_admin_events("Mounting NAS filesystem report false",__FUNCTION__,__FILE__,__LINE__);
			progress(100,"{disabled}");
			return;
		}
	}
	
	$t=time();
	
	$mountPoint="/mnt/BackupArticaBackNAS";
	

	
	$Workdir="$mountPoint/$hostname/system-backup/".date("Y-m-d-H");
	if($BackupArticaBackLocalFolder==1){
		$Workdir="$BackupArticaBackLocalDir/$hostname/system-backup/".date("Y-m-d-H");
	}
	
	@mkdir($Workdir,0755,true);
	progress(20,"{backup_ldap}");
	backup_ldap($Workdir);
	progress(30,"{backup_artica_settings}");
	backup_artica_settings($Workdir);
	progress(60,"{backup_artica_database}");
	backup_mysql_artica_backup($Workdir);
	progress(70,"{backup_proxy_databases}");
	backup_mysql_artica_squidlogs($Workdir);
	@file_put_contents("$Workdir/BKVERSION.txt", time());
	@file_put_contents("$Workdir/DURATION.txt", $unix->distanceOfTimeInWords($t,time(),true));
	progress(90,"{backup_done}");
	if($BackupArticaBackLocalFolder==0){
		$mount=new mount("/var/log/artica-postfix/backup.debug");
		$mountPoint="/mnt/BackupArticaBackNAS";
		if($mount->ismounted($mountPoint)){ $mount->umount($mountPoint);}
	}
	progress(100,"{done}");
}


function backup_ldap($Workdir){
	$unix=new unix();
	$slapcat=$unix->find_program("slapcat");
	$gzip=$unix->find_program("gzip");
	$nice=$unix->EXEC_NICE();
	if(!is_file($slapcat)){ system_admin_events("Error, slapcat, no such binary",__FUNCTION__,__FILE__,__LINE__); return false; }
	if(!is_file($gzip)){ system_admin_events("Error, gzip, no such binary",__FUNCTION__,__FILE__,__LINE__); return false; }	
	$cmd=trim("$nice $slapcat|$gzip >$Workdir/ldap_database.gz 2>&1");
	exec($cmd,$results);
	
	if($GLOBALS["VERBOSE"]){echo $cmd."\n".@implode("\n", $results)."\n";}
	
	$size=filesize("$Workdir/ldap_database.gz");
	$size=$size/1024;
	$size=round($size/1024,2);
	system_admin_events("ldap_database.gz ({$size}M)\n".@implode("\n", $results),__FUNCTION__,__FILE__,__LINE__);
	
	$SLAPD_CONF=$unix->SLAPD_CONF_PATH();
	$results=array();
	$cmd=trim("$nice $gzip -c $SLAPD_CONF > /$Workdir/ldap.conf.gz 2>&1");
	exec($cmd,$results);
	if($GLOBALS["VERBOSE"]){echo $cmd."\n".@implode("\n", $results)."\n";}
	
	$size=filesize("$Workdir/ldap.conf.gz");
	$GLOBALS["ARRAY_CONTENT"]["ldap.conf.gz"]=$size;
	$size=$size/1024;
	$size=round($size,2);
	system_admin_events("ldap.conf.gz ({$size}K)\n".@implode("\n", $results),__FUNCTION__,__FILE__,__LINE__);
}

function backup_nginx($BaseWorkDir){
	@chdir("/etc/nginx");
	$unix=new unix();
	$tar=$unix->find_program("tar");
	system("cd /etc/nginx");
	@mkdir("$BaseWorkDir/nginx",0755,true);
	shell_exec("$tar czf $BaseWorkDir/nginx/tarball.tgz *");
	
}

function backup_artica_settings($BaseWorkDir){
	$BLACKLIST["x86info_mhz"]=true;
	$BLACKLIST["WizardSavedSettings"]=true;
	$BLACKLIST["WifiCardOk"]=true;
	$BLACKLIST["SystemCpuNumber"]=true;
	$BLACKLIST["HOSTID"]=true;
	$BLACKLIST["PublicIPAddress"]=true;
	$BLACKLIST["WizardSavedSettingsSend"]=true;
	$BLACKLIST["LicenseInfos"]=true;
	$BLACKLIST["LinuxDistribution"]=true;
	$BLACKLIST["LinuxDistributionName"]=true;
	$BLACKLIST["myhostname"]=true;
	$BLACKLIST["WgetBindIpAddress"]=true;
	$BLACKLIST["SystemTotalSize"]=true;
	$BLACKLIST["SYSTEMID"]=true;
	$BLACKLIST["SoftwaresListCached"]=true;
	$BLACKLIST["TOP_NOTIFY"]=true;
	$BLACKLIST["HyperCacheStoreIDLicense"]=true;
	$BLACKLIST["HyperCacheLicStatus"]=true;
	$BLACKLIST["UpgradeTov10"]=true;
	$BLACKLIST["LinuxDistributionFullName"]=true;
	
	if (!$handle = opendir("/etc/artica-postfix/settings/Daemons")) {echo "Failed open /etc/artica-postfix/settings/Daemons\n";return;}
	@mkdir("$BaseWorkDir/Daemons",0755,true);
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="/etc/artica-postfix/settings/Daemons/$filename";
		if(preg_match("#-[0-9]+$#", $filename)){
			@unlink($targetFile);
			continue;
		}
		if(preg_match("#\{#", $filename)){
			@unlink($targetFile);
			continue;
		}
		
		
		if(is_dir($targetFile)){continue;}
		if(isset($BLACKLIST[$filename])){continue;}
		if(is_file("$BaseWorkDir/Daemons/$filename")){@unlink("$BaseWorkDir/Daemons/$filename");}
		if($GLOBALS["VERBOSE"]){echo "$targetFile -> $BaseWorkDir/Daemons/$filename\n";}
		copy($targetFile, "$BaseWorkDir/Daemons/$filename");
		$GLOBALS["ARRAY_CONTENT"]["Daemons/$filename"]=@filesize("$BaseWorkDir/Daemons/$filename");
	}
	echo "Backup /etc/artica-postfix/settings/Daemons done\n";
	system_admin_events("settings/Daemons done\n",__FUNCTION__,__FILE__,__LINE__);
	
}

function snapshot_restore_sql($ID){
	
	$unix=new unix();
	$q=new mysql();
	$sock=new sockets();
	$sock->SET_INFO("BackupArticaRestoreNetwork", 1);
	$sql="SELECT `snap` FROM `snapshots` WHERE ID='$ID'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_snapshots"));
	$FILE_TEMP=$unix->FILE_TEMP().".tar.gz";
	@file_put_contents($FILE_TEMP, $ligne["snap"]);
	snapshot_restore($FILE_TEMP);
}



function snapshot_import_progress($purc,$text){
	backupevents("$purc) $text");
	$array=array("POURC"=>$purc,"TEXT"=>$text);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/snapshot.upload.progress", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/web/snapshot.upload.progress",0755);
}

function snapshot_import(){
	$sock=new sockets();
	ini_set('memory_limit','1000M');
	$ARRAY=unserialize($sock->GET_INFO("SnapshotUpload"));
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/snapshot.upload.progress";
	$xdate=$ARRAY["xdate"];
	$size=$ARRAY["size"];
	$zmd5=$ARRAY["zmd5"];
	$filePath=$ARRAY["filepath"];
	
	echo "Date: $xdate\n";
	echo "Size: $size\n";
	echo "MD5.: $zmd5\n";
	echo "File: $filePath\n";
	
	if(!is_file($filePath)){
		echo "No such file!\n";
		snapshot_import_progress(110,"{failed}: ".basename($filePath));
		return;
	}
	
	snapshot_import_progress(15,"{importing}: ".basename($filePath));
	$data=mysql_escape_string2(@file_get_contents($filePath));
	@unlink($filePath);
	
	
	$q=new mysql();
	
	for($i=0;$i<6;$i++){
		snapshot_import_progress(50,"{importing}: ".basename($filePath)." $i/5");
		$q->QUERY_SQL("INSERT IGNORE INTO `snapshots` (zDate,snap,size,content,zmd5)
				VALUES ('$xdate','$data','$size','','$zmd5')","artica_snapshots");
		
		if($q->ok){
			snapshot_import_progress(100,"{success}: ".basename($filePath));
			return;
		}
		
		echo $q->mysql_error."\n";
		sleep(3);		
	}
	
	snapshot_import_progress(110,"{failed}: ".basename($filePath));
	
}


function snapshot_restore($tarball){
	backupevents("Restoring $tarball");
	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	$unix=new unix();
	$sock=new sockets();
	$rm=$unix->find_program("rm");
	$BaseWorkDir="/usr/share/artica-postfix/snapshots/".time();
	$tar=$unix->find_program("tar");
	$squidbin=$unix->LOCATE_SQUID_BIN();
	$php=$unix->LOCATE_PHP5_BIN();
	if($GLOBALS["SEND_META"]){$GLOBALS["NOT_RESTORE_NETWORK"]=true;}
	
	
	progress(10,"{restoring} $tarball");
	echo $tarball."\n";
	if(!is_file($tarball)){
		progress(110,"{failed}");
		echo $tarball." no such file\n";
		return;
	}
	
	@mkdir($BaseWorkDir,0755,true);
	progress(15,"{extracting}");
	echo $tarball." -> $BaseWorkDir\n";
	system("$tar xpf $tarball -C $BaseWorkDir/");
	if(!$GLOBALS["SNAPSHOT_NO_DELETE"]){
		@unlink($tarball);
	}
	
	if(is_file("$BaseWorkDir/TRUNCATE_TABLES")){
		$TRUNCATE_TABLES=unserialize(@file_get_contents("$BaseWorkDir/TRUNCATE_TABLES"));
		@unlink("$BaseWorkDir/TRUNCATE_TABLES");
		while (list ($database, $tables) = each ($TRUNCATE_TABLES)){
			progress(20,"{cleaning} $database");
			while (list ($tablename, $none) = each ($tables)){
				if($database=="artica_backup"){
					echo "Cleaning $tablename\n";
					$q=new mysql();
					$q->QUERY_SQL("TRUNCATE TABLE `$tablename`","artica_backup");
					continue;
				}
				if($database=="squidlogs"){
					echo "Cleaning $tablename\n";
					$q=new mysql_squid_builder();
					$q->QUERY_SQL("TRUNCATE TABLE `$tablename`");
					continue;
				}
			}
		}
	}else{
		echo "$BaseWorkDir/TRUNCATE_TABLES no such file\n";
	}
	
	progress(30,"{restoring} squidlogs");
	echo "-> restore_squidlogs....\n";
	restore_squidlogs($BaseWorkDir);
	progress(40,"{restoring} artica_backup");
	restore_artica_backup($BaseWorkDir);
	progress(50,"{restoring} Artica settings");
	restore_artica_settings($BaseWorkDir);
	progress(60,"{restoring} Open LDAP");
	Restore_ldap($BaseWorkDir);
	progress(70,"{restoring} Reverse Proxy");
	restore_nginx($BaseWorkDir);
	progress(75,"{restoring} PowerDNS");
	restore_powerdns($BaseWorkDir);
	progress(80,"{cleaning}...");
	shell_exec("$rm -rf $BaseWorkDir");
	
	progress(90,"{reconfigure_server}, {please_wait}...");
	
	
	if(is_file($squidbin)){system("$php /usr/share/artica-postfix/exec.squid.php --build --force"); }
	progress(100,"{success}...");
	if($GLOBALS["SEND_META"]){
		meta_admin_mysql(2, "Success restoring snapshot", null,__FILE__,__LINE__);
	}
	
}



function snapshot(){
	$unix=new unix();
	$password=null;
	$mysqldump=$unix->find_program("mysqldump");
	$gzip=$unix->find_program("gzip");
	$sock=new sockets();
	$rm=$unix->find_program("rm");
	$BaseWorkDir=$unix->TEMP_DIR()."/snapshots/".time();
	$tar=$unix->find_program("tar");
	@mkdir($BaseWorkDir,0755,true);
	
	$nice=$unix->EXEC_NICE();
	$q=new mysql();
	$LIST_TABLES_ARTICA_BACKUP=$q->LIST_TABLES_ARTICA_BACKUP();
	if($q->mysql_password<>null){$password=" -p".$unix->shellEscapeChars($q->mysql_password);}
	$prefix=trim("$nice $mysqldump --add-drop-table --single-transaction --force --insert-ignore -S /var/run/mysqld/mysqld.sock -u {$q->mysql_admin}$password artica_backup");
	
	$ARRAY["artica_backup_blacklists"]["ipblocks_db"]=true;
	$ARRAY["artica_backup_blacklists"]["adgroups"]=true;
	$ARRAY["artica_backup_blacklists"]["adusers"]=true;
	$ARRAY["artica_backup_blacklists"]["drupal_queue_orders"]=true;
	$ARRAY["artica_backup_blacklists"]["haarp"]=true;
	$ARRAY["artica_backup_blacklists"]["icons_db"]=true;
	$ARRAY["artica_backup_blacklists"]["setup_center"]=true;
	$ARRAY["artica_backup_blacklists"]["clamavsig"]=true;
	$ARRAY["artica_backup_blacklists"]["kav4proxy_license"]=true;
	$ARRAY["artica_backup_blacklists"]["getent_groups"]=true;
	$ARRAY["artica_backup_blacklists"]["zarafa_orphaned"]=true;
	
	
	$c=0;
	@mkdir("$BaseWorkDir/artica_backup",0755,true);
	while (list ($table_name, $val) = each ($LIST_TABLES_ARTICA_BACKUP)){
		$table_name=trim($table_name);
		if(isset($ARRAY["artica_backup_blacklists"][$table_name])){continue;}
		if(preg_match("#^activedirectory#", $table_name)){continue;}
		if(preg_match("#^amanda#", $table_name)){continue;}
		if($q->COUNT_ROWS($table_name, "artica_backup")==0){
			$GLOBALS["TRUNCATES"]["artica_backup"][$table_name]=true;
			continue;}
			progress(15,"{backup} $table_name");
		echo "$BaseWorkDir/artica_backup/$table_name.gz\n";
		$cmd="$prefix $table_name | $gzip > $BaseWorkDir/artica_backup/$table_name.gz 2>&1";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		exec($cmd,$results);
		if($unix->MYSQL_BIN_PARSE_ERROR($results)){
			echo "Failed to create snapshot\n ".@implode("\n", $results);
			system_admin_events("Failed to create snapshot ".@implode("\n", $results),__FUNCTION__,__FILE__,__LINE__);
			shell_exec("$rm -rf $BaseWorkDir");
			return;
		}
		$GLOBALS["ARRAY_CONTENT"]["artica_backup/$table_name.gz"]=@filesize("$BaseWorkDir/artica_backup/$table_name.gz");
		
		$c++;
			
	}
	

	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	if(is_file($squidbin)){
		if($unix->is_socket("/var/run/mysqld/squid-db.sock")){
			$q=new mysql_squid_builder();
			$LIST_TABLES_ARTICA_SQUIDLOGS=$q->LIST_TABLES_ARTICA_SQUIDLOGS();
			if($q->mysql_password<>null){$password=" -p".$unix->shellEscapeChars($q->mysql_password);}
			
			$prefix=trim("$nice $mysqldump --add-drop-table --single-transaction --force --insert-ignore -S /var/run/mysqld/squid-db.sock -u root squidlogs");
			@mkdir("$BaseWorkDir/squidlogs",0755,true);
			$BLACKLIST["tables_day"]=true;
			$BLACKLIST["quotachecked"]=true;
			$BLACKLIST["cached_total"]=true;
			$BLACKLIST["MySQLStats"]=true;
			$BLACKLIST["phraselists_weigthed"]=true;
			$BLACKLIST["squid_reports"]=true;
			$BLACKLIST["stats_appliance_events"]=true;
			$BLACKLIST["webfilter_catprivslogs"]=true;
			$BLACKLIST["webfilters_backupeddbs"]=true;
			$BLACKLIST["webfilters_bigcatzlogs"]=true;
			$BLACKLIST["FamilyCondensed"]=true;
			$BLACKLIST["catztemp"]=true;
			$BLACKLIST["hotspot_sessions"]=true;
			$BLACKLIST["instant_updates"]=true;
			$BLACKLIST["macscan"]=true;
			$BLACKLIST["members_uid"]=true;
			$BLACKLIST["members_macip"]=true;
			$BLACKLIST["members_mac"]=true;
			$BLACKLIST["webfilters_categories_caches"]=true;
			$BLACKLIST["webfilters_thumbnails"]=true;
			$BLACKLIST["wpad_events"]=true;
			$BLACKLIST["phishtankdb"]=true;
			$BLACKLIST["squid_storelogs"]=true;
			
			
			while (list ($table_name, $val) = each ($LIST_TABLES_ARTICA_SQUIDLOGS)){
				if(isset($BLACKLIST[$table_name])){continue;}
				
				if(preg_match("#[0-9]+#", $table_name)){continue;}
				if(preg_match("#[0-9]+#", $table_name)){continue;}
				if(preg_match("#updateev$#", $table_name)){continue;}
				if(preg_match("#^traffic#", $table_name)){continue;}
				if(preg_match("#^www_#", $table_name)){continue;}
				if(preg_match("#^visited_#", $table_name)){continue;}
				if(preg_match("#^youtube_#", $table_name)){continue;}
				if(preg_match("#^UserAgents#", $table_name)){continue;}
				if(preg_match("#^UserAutDB#", $table_name)){continue;}
				if(preg_match("#^UserAuthDays#", $table_name)){continue;}
				if(preg_match("#^UserAuthDaysGrouped#", $table_name)){continue;}
				if(preg_match("#^UserSizeRTT#", $table_name)){continue;}
				if(preg_match("#^UsersAgentsDB#", $table_name)){continue;}
				if(preg_match("#^UsersTMP#", $table_name)){continue;}
				if(preg_match("#^UsersToTal#", $table_name)){continue;}
				if(preg_match("#^allsizes#", $table_name)){continue;}
				if(preg_match("#^alluid#", $table_name)){continue;}
				if(preg_match("#^categorize#", $table_name)){continue;}
				if(preg_match("#^blocked_#", $table_name)){continue;}
				if(preg_match("#^sites$#", $table_name)){continue;}
				if(preg_match("#^users$#", $table_name)){continue;}
				if(preg_match("#^ufdbunlock$#", $table_name)){continue;}
				if(preg_match("#^updateblks_events$#", $table_name)){continue;}
				if(preg_match("#^main_websites#", $table_name)){continue;}
				if(preg_match("#^notcategorized#", $table_name)){continue;}
				if(preg_match("#^ngixattckd_#", $table_name)){continue;}
				if(preg_match("#^sizehour_#", $table_name)){continue;}
				if(preg_match("#^squidhour_#", $table_name)){continue;}
				
				
				
				if($q->COUNT_ROWS($table_name, "squidlogs")==0){
					$GLOBALS["TRUNCATES"]["squidlogs"][$table_name]=true;
					continue;
				}
				progress(30,"{backup} $table_name");
				echo "$BaseWorkDir/squidlogs/$table_name.gz\n";
				$cmd="$prefix $table_name | $gzip > $BaseWorkDir/squidlogs/$table_name.gz 2>&1";
				if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
				exec($cmd,$results);
				if($unix->MYSQL_BIN_PARSE_ERROR($results)){
					echo "Failed to create snapshot\n ".@implode("\n", $results);
					shell_exec("$rm -rf $BaseWorkDir");
					system_admin_events("Failed to create snapshot ".@implode("\n", $results),__FUNCTION__,__FILE__,__LINE__);
					return;
				}
				$GLOBALS["ARRAY_CONTENT"]["squidlogs/$table_name.gz"]=@filesize("$BaseWorkDir/squidlogs/$table_name.gz");
				$c++;
			}
		}
	}
	

	progress(35,"{backup} OpenDLAP server");
	backup_ldap($BaseWorkDir);
	progress(40,"{backup} Reverse Proxy");
	backup_nginx($BaseWorkDir);
	progress(45,"{backup} PowerDNS");
	backup_mysql_powerdns($BaseWorkDir);
	
	
	progress(50,"{backup} Artica settings");
	backup_artica_settings($BaseWorkDir);
	@file_put_contents("$BaseWorkDir/TRUNCATE_TABLES", serialize($GLOBALS["TRUNCATES"]));
	$temp=$unix->FILE_TEMP().".tar.gz";
	$tempdir=$unix->TEMP_DIR();
	chdir($BaseWorkDir);
	progress(60,"{compressing} {to} $temp");
	system("cd  $BaseWorkDir");
	system("$tar -czf $temp *");
	
	system("cd /root");
	chdir("/root");
	progress(65,"{removing} $BaseWorkDir");
	echo "Remove: $BaseWorkDir....\n";
	shell_exec("$rm -rf $BaseWorkDir");
	
	if(!is_file($temp)){
		echo "$temp, no such file...\n";
		progress(110,"{failed}");
		return;
	}

	
	$q=new mysql();
	$q->CREATE_DATABASE("artica_snapshots");
	
	
	
	$sql="CREATE TABLE IF NOT EXISTS `snapshots` (
	`ID` int(11) NOT NULL AUTO_INCREMENT,
	`zmd5` VARCHAR(90) NOT NULL,
	`size` INT UNSIGNED NOT NULL,
	`zDate` DATETIME NOT NULL,
	`snap` LONGBLOB NOT NULL,
	 `content` TEXT NOT NULL,
	 PRIMARY KEY (`ID`),
	 UNIQUE KEY `zmd5` (`zmd5`),
	 KEY `zDate` (`zDate`)
	) ENGINE=MyISAM";
	$q->QUERY_SQL($sql,'artica_snapshots');
	progress(70,"{saving}");
	
	if($GLOBALS["SEND_META"]){
		$articameta=new artica_meta();
		$filemeta=$tempdir."/snapshot.tar.gz";
		if(@copy($temp,$filemeta)){
			if(!$articameta->SendFile($filemeta,"SNAPSHOT")){
				$articameta->events("$temp unable to upload", __FUNCTION__,__FILE__,__LINE__);
			}
		}else{
			$articameta->events("$temp unable to copy $temp to $filemeta", __FUNCTION__,__FILE__,__LINE__);
		}
		@unlink($filemeta);
	}
	
	
	$zmd5=md5_file($temp);
	$size=@filesize($temp);
	@chmod($temp,0777);
	$final_array=mysql_escape_string2(serialize($GLOBALS["ARRAY_CONTENT"]));
	
	echo "********* INSERTING SNAPSHOT IN MYSQL DATABASE *************************\n";
	
	$q->QUERY_SQL("INSERT IGNORE INTO `snapshots` (zDate,snap,size,content,zmd5) 
			VALUES (NOW(),LOAD_FILE('$temp'),'$size','$final_array','$zmd5')","artica_snapshots");
	if(!$q->ok){
		echo "****************\n$q->mysql_error\n****************\n";
		progress(110,"{failed}");
		return;
	}
	@unlink($temp);
	shell_exec("$rm -rf /usr/share/artica-postfix/snapshots");
	progress(100,"{success}");
	
}

function snapshot_meta($tarball){
	
	
}



function backup_mysql_artica_backup($BaseWorkDir){
	$unix=new unix();
	$password=null;
	$mysqldump=$unix->find_program("mysqldump");
	$gzip=$unix->find_program("gzip");
	$sock=new sockets();
	$BackupArticaBackAllDB=intval($sock->GET_INFO("BackupArticaBackAllDB"));
	
	if(!is_file($gzip)){ system_admin_events("Error, gzip, no such binary",__FUNCTION__,__FILE__,__LINE__); return false; }
	if(!is_file($mysqldump)){ system_admin_events("Error, mysqldump, no such binary",__FUNCTION__,__FILE__,__LINE__); return false; }
	
	$nice=$unix->EXEC_NICE();
	$q=new mysql();
	$LIST_TABLES_ARTICA_BACKUP=$q->LIST_TABLES_ARTICA_BACKUP();
	if($q->mysql_password<>null){$password=" -p".$unix->shellEscapeChars($q->mysql_password);}
	
	$prefix=trim("$nice $mysqldump --add-drop-table --single-transaction --force --insert-ignore -S /var/run/mysqld/mysqld.sock -u {$q->mysql_admin}$password artica_backup");
	@mkdir("$BaseWorkDir/artica_backup",0755,true);
	$c=0;
	
	while (list ($table_name, $val) = each ($LIST_TABLES_ARTICA_BACKUP)){
		$cmd="$prefix $table_name | $gzip > $BaseWorkDir/artica_backup/$table_name.gz";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		shell_exec($cmd);
		$c++;
			
	}
	
	
	
	$LIST_TABLES_ARTICA_SQUIDLOGS=$q->LIST_TABLES_ARTICA_SQUIDLOGS();
	if($q->mysql_password<>null){$password=" -p".$unix->shellEscapeChars($q->mysql_password);}
	
	$prefix=trim("$nice $mysqldump --add-drop-table --single-transaction --force --insert-ignore -S /var/run/mysqld/mysqld.sock -u {$q->mysql_admin}$password squidlogs");
	@mkdir("$BaseWorkDir/squidlogs",0755,true);
	$BLACKLIST["tables_day"]=true;
	$BLACKLIST["quotachecked"]=true;
	$BLACKLIST["cached_total"]=true;
	
	while (list ($table_name, $val) = each ($LIST_TABLES_ARTICA_SQUIDLOGS)){
		if(preg_match("#[0-9]+#", $table_name)){continue;}
		if(preg_match("#[0-9]+#", $table_name)){continue;}
		if(preg_match("#^www_#", $table_name)){continue;}
		if(preg_match("#^visited_#", $table_name)){continue;}
		if(preg_match("#^youtube_#", $table_name)){continue;}
		if(isset($BLACKLIST[$table_name])){continue;}
		$cmd="$prefix $table_name | $gzip > $BaseWorkDir/squidlogs/$table_name.gz";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		shell_exec($cmd);
		$c++;
	}
	
	$LIST_TABLES_ARTICA_OCSWEB=$q->LIST_TABLES_ARTICA_OCSWEB();
	if($q->mysql_password<>null){$password=" -p".$unix->shellEscapeChars($q->mysql_password);}
	
	$prefix=trim("$nice $mysqldump --add-drop-table --single-transaction --force --insert-ignore -S /var/run/mysqld/mysqld.sock -u {$q->mysql_admin}$password ocsweb");
	@mkdir("$BaseWorkDir/ocsweb",0755,true);
	
	
	while (list ($table_name, $val) = each ($LIST_TABLES_ARTICA_OCSWEB)){
		$cmd="$prefix $table_name | $gzip > $BaseWorkDir/ocsweb/$table_name.gz";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		shell_exec($cmd);
		$c++;
	}

	if($BackupArticaBackAllDB==1){
		$DATABASE_LIST=$q->DATABASE_LIST();
		unset($DATABASE_LIST["squidlogs"]);
		unset($DATABASE_LIST["ocsweb"]);
		unset($DATABASE_LIST["artica_backup"]);
		unset($DATABASE_LIST["artica_events"]);
		unset($DATABASE_LIST["mysql"]);
		while (list ($database, $val) = each ($DATABASE_LIST)){
			$prefix=trim("$nice $mysqldump --add-drop-table --single-transaction --force --insert-ignore -S /var/run/mysqld/mysqld.sock -u {$q->mysql_admin}$password $database | $gzip > $BaseWorkDir/DB_$database.gz");
			if($GLOBALS["VERBOSE"]){echo "$prefix\n";}
			shell_exec($prefix);
		}
	}
	
	
	
	system_admin_events("Artica Databases $c tables done\n",__FUNCTION__,__FILE__,__LINE__);
	
}
function backup_mysql_artica_squidlogs($BaseWorkDir,$BackupArticaBackCategory=null){
	$unix=new unix();
	$sock=new sockets();
	$password=null;
	$mysqldump=$unix->find_program("mysqldump");
	$gzip=$unix->find_program("gzip");
	if(!is_numeric($BackupArticaBackCategory)){
		$BackupArticaBackCategory=$sock->GET_INFO("BackupArticaBackCategory");
		if(!is_numeric($BackupArticaBackCategory)){$BackupArticaBackCategory=1;}
	}

	if(!is_file($gzip)){ system_admin_events("Error, gzip, no such binary",__FUNCTION__,__FILE__,__LINE__); return false; }
	if(!is_file($mysqldump)){ system_admin_events("Error, mysqldump, no such binary",__FUNCTION__,__FILE__,__LINE__); return false; }

	if(!$unix->is_socket("/var/run/mysqld/squid-db.sock")){system_admin_events("Error,/var/run/mysqld/squid-db.sock no such socket",__FUNCTION__,__FILE__,__LINE__); return false; }
	
	
	$nice=$unix->EXEC_NICE();
	$q=new mysql_squid_builder();
	$LIST_TABLES_ARTICA_SQUIDLOGS=$q->LIST_TABLES_ARTICA_SQUIDLOGS();
	if($q->mysql_password<>null){$password=" -p".$unix->shellEscapeChars($q->mysql_password);}

	$prefix=trim("$nice $mysqldump --add-drop-table --force --single-transaction --insert-ignore -S /var/run/mysqld/squid-db.sock -u {$q->mysql_admin}$password squidlogs");
	@mkdir("$BaseWorkDir/squidlogs",0755,true);

	$BLACKLIST["tables_day"]=true;
	$BLACKLIST["quotachecked"]=true;
	$BLACKLIST["cached_total"]=true;
	$BLACKLIST["ufdb_smtp"]=true;
	$c=0;
	while (list ($table_name, $val) = each ($LIST_TABLES_ARTICA_SQUIDLOGS)){
		if(preg_match("#[0-9]+#", $table_name)){continue;}
		if(preg_match("#^www_#", $table_name)){continue;}
		if(preg_match("#^visited_#", $table_name)){continue;}
		if(preg_match("#^youtube_#", $table_name)){continue;}
		if($BackupArticaBackCategory==0){if(preg_match("#^category_#", $table_name)){continue;} }
		
		if(isset($BLACKLIST[$table_name])){continue;}
		if(is_file("$BaseWorkDir/squidlogs/$table_name.gz")){@unlink("$BaseWorkDir/squidlogs/$table_name.gz");}
		$cmd="$prefix $table_name | $gzip > $BaseWorkDir/squidlogs/$table_name.gz 2>&1";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		exec($cmd,$results);
		if($unix->MYSQL_BIN_PARSE_ERROR($results)){
			
		}
		$c++;
	}

	system_admin_events("Proxy Database $c tables done\n",__FUNCTION__,__FILE__,__LINE__);
	return true;
}

function backup_mysql_powerdns($BaseWorkDir){
	$unix=new unix();
	$sock=new sockets();
	$password=null;
	$mysqldump=$unix->find_program("mysqldump");
	$gzip=$unix->find_program("gzip");
	
	
	if(!is_file($gzip)){ system_admin_events("Error, gzip, no such binary",__FUNCTION__,__FILE__,__LINE__); return false; }
	if(!is_file($mysqldump)){ system_admin_events("Error, mysqldump, no such binary",__FUNCTION__,__FILE__,__LINE__); return false; }
	
	if(!$unix->is_socket("/var/run/mysqld/mysqld.sock")){
		system_admin_events("Error,/var/run/mysqld/mysqld.sock no such socket",__FUNCTION__,__FILE__,__LINE__); 
		return false; 
	}
	
	$q=new mysql();
	if(!$q->DATABASE_EXISTS("powerdns")){
		backupevents("Database PowerDNS doesn't exists...");
		return true;}
	$nice=$unix->EXEC_NICE();	
	
	
	$LIST_TABLES_POWERDNS=$q->LIST_TABLES_POWERDNS();
	backupevents(count($LIST_TABLES_POWERDNS)." tables to backup...");;
	if($q->mysql_password<>null){$password=" -p".$unix->shellEscapeChars($q->mysql_password);}
	
	$prefix=trim("$nice $mysqldump --add-drop-table --single-transaction --force --insert-ignore -S /var/run/mysqld/mysqld.sock -u {$q->mysql_admin}$password powerdns");
	@mkdir("$BaseWorkDir/powerdns",0755,true);
	
	$c=0;
	while (list ($table_name, $val) = each ($LIST_TABLES_POWERDNS)){
		$cmd="$prefix $table_name | $gzip > $BaseWorkDir/powerdns/$table_name.gz";
		backupevents("$cmd");;
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		shell_exec($cmd);
		$c++;
	}

	system_admin_events("PowerDNS Databases $c tables done\n",__FUNCTION__,__FILE__,__LINE__);	
	
	
	
	
}


function progress($purc,$text){
	backupevents("$purc) $text");
	$array=array("POURC"=>$purc,"TEXT"=>$text);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/backup.artica.progress", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/web/backup.artica.progress",0755);
}

function restore_TestNas(){
	$sock=new sockets();
	$unix=new unix();
	$sock=new sockets();
	
	$BackupArticaRestoreNASIpaddr=$sock->GET_INFO("BackupArticaRestoreNASIpaddr");
	$BackupArticaRestoreNASFolder=$sock->GET_INFO("BackupArticaRestoreNASFolder");
	$BackupArticaRestoreNASUser=$sock->GET_INFO("BackupArticaRestoreNASUser");
	$BackupArticaRestoreNASPassword=$sock->GET_INFO("BackupArticaRestoreNASPassword");
	$BackupArticaRestoreNASFolderSource=$sock->GET_INFO("BackupArticaRestoreNASFolderSource");
	$BackupArticaRestoreNetwork=$sock->GET_INFO("BackupArticaRestoreNetwork");


	$mount=new mount("/var/log/artica-postfix/backup.debug");
	$mountPoint="/mnt/BackupArticaRestoreNAS";

	if($mount->ismounted($mountPoint)){ return true; }

	@mkdir($mountPoint,0755,true);

	if(!$mount->smb_mount($mountPoint,$BackupArticaRestoreNASIpaddr,$BackupArticaRestoreNASUser,$BackupArticaRestoreNASPassword,$BackupArticaRestoreNASFolder)){
		system_admin_events("Mounting //$BackupArticaRestoreNASIpaddr/$BackupArticaRestoreNASFolder failed",__FUNCTION__,__FILE__,__LINE__);
		return false;

	}

	if(!$mount->ismounted($mountPoint)){
		return false;
	}
	return true;
}


function restore(){
	
	$sock=new sockets();
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	if($GLOBALS["VERBOSE"]){
		echo "PID: $pidfile\n";
	}
	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid)){
		$TTL=$unix->PROCESS_TTL($pid);
		if($TTL<240){return;}
		$kill=$unix->find_program("kill");
		unix_system_kill_force($pid);
	}
	
	@file_put_contents($pidfile, getmypid());
	$hostname=$unix->hostname_g();
	
	
	progress(10,"{mounting}");
	if(!restore_TestNas()){
		system_admin_events("Mounting NAS filesystem report false",__FUNCTION__,__FILE__,__LINE__);
		progress(100,"{failed}");
		return;
	
	}
	
	$BackupArticaRestoreNASIpaddr=$sock->GET_INFO("BackupArticaRestoreNASIpaddr");
	$BackupArticaRestoreNASFolder=$sock->GET_INFO("BackupArticaRestoreNASFolder");
	$BackupArticaRestoreNASUser=$sock->GET_INFO("BackupArticaRestoreNASUser");
	$BackupArticaRestoreNASPassword=$sock->GET_INFO("BackupArticaRestoreNASPassword");
	$BackupArticaRestoreNASFolderSource=$sock->GET_INFO("BackupArticaRestoreNASFolderSource");
	$BackupArticaRestoreNetwork=$sock->GET_INFO("BackupArticaRestoreNetwork");
	$mountPoint="/mnt/BackupArticaRestoreNAS";	
	$BackupArticaRestoreNASFolderSource=str_replace("\\", "/", $BackupArticaRestoreNASFolderSource);
	
	$sourceDir="$mountPoint/$BackupArticaRestoreNASFolderSource";
	$sourceDir=str_replace("//", "/", $sourceDir);
	
	if(!is_file("$sourceDir/BKVERSION.txt")){
		progress(100,"{failed} BKVERSION.txt no such file");
		$mount=new mount("/var/log/artica-postfix/backup.debug");
		if($mount->ismounted($mountPoint)){ $mount->umount($mountPoint);}
		return;
	}
	
	$time=trim(@file_get_contents("$sourceDir/BKVERSION.txt"));
	progress(15,"{backup} ".date("Y-m-d H:i:s"));
	progress(20,"{restoring_ldap_database}, {please_wait}...");
	Restore_ldap($sourceDir);
	progress(40,"{restoring_artica_settings}, {please_wait}...");
	restore_artica_settings($sourceDir);
	progress(50,"{restoring_artica_databases}, {please_wait}...");
	restore_artica_backup($sourceDir);	
	progress(60,"{restoring_artica_databases}, {please_wait}...");
	restore_ocsweb($sourceDir);	
	progress(80,"{restoring_artica_databases}, {please_wait}...");
	restore_squidlogs($sourceDir);	
	progress(82,"{restoring} PowerDNS, {please_wait}...");
	restore_powerdns($sourceDir);	
	
	progress(90,"{reconfigure_server}, {please_wait}...");
	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	$php=$unix->LOCATE_PHP5_BIN();
	if(is_file($squidbin)){shell_exec("$php /usr/share/artica-postfix/exec.squid.php --build --force"); }
	progress(100,"{success}");
	$mount=new mount("/var/log/artica-postfix/backup.debug");
	if($mount->ismounted($mountPoint)){ $mount->umount($mountPoint);}
	
	if($BackupArticaRestoreNetwork==1){
		$unix->THREAD_COMMAND_SET("$php /usr/share/artica-postfix/exec.virtuals-ip.php --build");
	}
	
	return;
	
}

function Restore_ldap($sourceDir){
	
	$unix=new unix();
	$gunzip=$unix->find_program("gunzip");
	$slapadd=$unix->find_program("slapadd");
	$rm=$unix->find_program("rm");
	$ldap_databases="/var/lib/ldap";
	$SLAPD_CONF=$unix->SLAPD_CONF_PATH();
	$SLAPD_CONF_GZ="$sourceDir/ldap.conf.gz";
	$LDAP_DB="$sourceDir/ldap_database.gz";
	$TMP=$unix->FILE_TEMP();
	if(!is_file($SLAPD_CONF_GZ)){
		system_admin_events("{failed} ldap.conf.gz no such file",__FUNCTION__,__FILE__,__LINE__);
		return false;
	}
	if($GLOBALS["VERBOSE"]){echo "Extract $LDAP_DB\n";}
	shell_exec("$gunzip $LDAP_DB -c >$TMP");
	
	
	if($GLOBALS["VERBOSE"]){echo "Stopping LDAP\n";}
	shell_exec("/etc/init.d/slapd stop --force");
	if($GLOBALS["VERBOSE"]){echo "Restoring slapd.conf\n";}
	shell_exec("$gunzip $SLAPD_CONF_GZ -c >$SLAPD_CONF");
	if($GLOBALS["VERBOSE"]){echo "Removing $ldap_databases\n";}
	shell_exec("$rm -f  $ldap_databases/* >/dev/null 2>&1");
	if($GLOBALS["VERBOSE"]){echo "Restoring database....\n";}
	shell_exec("$slapadd -v -c -l $TMP -f $SLAPD_CONF >/dev/null 2>&1");
	if($GLOBALS["VERBOSE"]){echo "Starting slapd\n";}
	shell_exec("/etc/init.d/slapd start --force");
	@unlink($TMP);
}

function restore_nginx($sourceDir){
	if(!is_dir($sourceDir."/nginx")){return;}
	if(!is_file("$sourceDir/nginx/tarball.tgz")){return;}
	$unix=new unix();
	$tar=$unix->find_program("tar");
	
	@mkdir("/etc/nginx",0755,true);
	shell_exec("$tar xpf $sourceDir/nginx/tarball.tgz -C /etc/nginx/");

}

function restore_artica_settings($sourceDir){
	if (!$handle = opendir("$sourceDir/Daemons")) {echo "Failed open $sourceDir/Daemons\n";return;}
	$sock=new sockets();
	$BackupArticaRestoreNetwork=intval($sock->GET_INFO("BackupArticaRestoreNetwork"));
	if($GLOBALS["NOT_RESTORE_NETWORK"]){$BackupArticaRestoreNetwork=0;}
	$BLACKLIST["WizardSavedSettings"]=true;
	$BLACKLIST["WizardSavedSettingsSend"]=true;
	$BLACKLIST["SYSTEMID"]=true;
	$BLACKLIST["BackupArticaRestoreNetwork"]=true;
	$BLACKLIST["LogsWarninStop"]=true;
	$BLACKLIST["LinuxDistributionFullName"]=true;
	
	if($BackupArticaRestoreNetwork==0){
		$BLACKLIST["EnableKerbAuth"]=true;
		$BLACKLIST["KerbAuthInfos"]=true;
		$BLACKLIST["SambaBindInterface"]=true;
		$BLACKLIST["SambaSMBConf"]=true;
		$BLACKLIST["NTPDConf"]=true;
		$BLACKLIST["ufdbCatInterface"]=true;
		$BLACKLIST["SquidGuardServerName"]=true;
		$BLACKLIST["SquidWCCPL3LocIP"]=true;
		$BLACKLIST["SambaSecondPartConf"]=true;
		$BLACKLIST["EnableSquidRemoteMySQL"]=true;
		$BLACKLIST["EnableRemoteStatisticsAppliance"]=true;
		$BLACKLIST["squidRemostatisticsServer"]=true;
		$BLACKLIST["squidRemostatisticsPort"]=true;
		$BLACKLIST["squidRemostatisticsUser"]=true;
		$BLACKLIST["squidRemostatisticsPassword"]=true;
		$BLACKLIST["UseRemoteUfdbguardService"]=true;
		$BLACKLIST["BackupArticaRestoreNASIpaddr"]=true;
		$BLACKLIST["BackupArticaRestoreNASFolder"]=true;
		$BLACKLIST["BackupArticaRestoreNASUser"]=true;
		$BLACKLIST["BackupArticaRestoreNASPassword"]=true;
		$BLACKLIST["BackupArticaRestoreNASFolderSource"]=true;
		$BLACKLIST["BackupArticaRestoreNetwork"]=true;
		$BLACKLIST["BackupSquidLogsUseNas"]=true;
		$BLACKLIST["BackupSquidLogsNASIpaddr"]=true;
		$BLACKLIST["BackupSquidLogsNASFolder"]=true;
		$BLACKLIST["BackupSquidLogsNASUser"]=true;
		$BLACKLIST["BackupSquidLogsNASPassword"]=true;
		$BLACKLIST["BackupSquidStatsUseNas"]=true;
		$BLACKLIST["BackupSquidStatsNASIpaddr"]=true;
		$BLACKLIST["BackupSquidStatsNASFolder"]=true;
		$BLACKLIST["BackupSquidStatsNASUser"]=true;
		$BLACKLIST["BackupSquidStatsNASPassword"]=true;
		$BLACKLIST["NetWorkBroadCastVLANAsIpAddr"]=true;
		$BLACKLIST["ArticaDHCPSettings"]=true;
		$BLACKLIST["HASettings"]=true;
		$BLACKLIST["resolvConf"]=true;
		$BLACKLIST["UseADAsNameServer"]=true;
		$BLACKLIST["OVHNetConfig"]=true;
		
	}
	
	
	$c=0;$size=0;
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		if(isset($BLACKLIST[$filename])){continue;}
		$c++;
		$SourceFile="$sourceDir/Daemons/$filename";
		$targetFile="/etc/artica-postfix/settings/Daemons/$filename";
		if(is_dir($SourceFile)){continue;}
		
		if(is_file($targetFile)){@unlink($targetFile);}
		if(!copy($SourceFile, $targetFile)){
			echo "Restoring $SourceFile Failed\n";
			continue;
		}
		
		$size=$size+@filesize($SourceFile);
	}
	
	$size=FormatBytes($size/1024,true);
	echo "Restoring $c Parameters ($size) done\n";
	
	
}
function restore_artica_backup($sourceDir){

	if (!$handle = opendir("$sourceDir/artica_backup")) {echo "Failed open $sourceDir/artica_backup\n";return;}
	$password=null;
	$unix=new unix();
	$sock=new sockets();
	$BackupArticaRestoreNetwork=intval($sock->GET_INFO("BackupArticaRestoreNetwork"));
	$gunzip=$unix->find_program("gunzip");
	$mysql=$unix->find_program("mysql");
	$BLACKLIST=array();
	$BLACKLIST["zarafa_orphaned.gz"]=true;
	
	if($GLOBALS["NOT_RESTORE_NETWORK"]){$BackupArticaRestoreNetwork=0;}
	
	if($BackupArticaRestoreNetwork==0){
		$BLACKLIST["nic_routes.gz"]=true;
		$BLACKLIST["nics.gz"]=true;
		$BLACKLIST["nics_bridge.gz"]=true;
		$BLACKLIST["nics_roles.gz"]=true;
		$BLACKLIST["nics_switch.gz"]=true;
		$BLACKLIST["nics_vde.gz"]=true;
		$BLACKLIST["nics_virtuals.gz"]=true;
		$BLACKLIST["nics_vlan.gz"]=true;
		$BLACKLIST["networks_infos.gz"]=true;
		$BLACKLIST["networks_infos.gz"]=true;
		$BLACKLIST["dhcpd_sharednets.gz"]=true;
		$BLACKLIST["dhcpd_fixed.gz"]=true;
		$BLACKLIST["iptables_bridge.gz"]=true;
		$BLACKLIST["net_hosts.gz"]=true;
		$BLACKLIST["arpcache.gz"]=true;
		
	}
	
	$nice=$unix->EXEC_NICE();
	$q=new mysql();
	
	if($q->mysql_password<>null){$password=" -p".$unix->shellEscapeChars($q->mysql_password);}
	$prefix=trim("$mysql --force -S /var/run/mysqld/mysqld.sock -u {$q->mysql_admin}$password artica_backup");

	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		if(isset($BLACKLIST[$filename])){continue;}
		$SourceFile="$sourceDir/artica_backup/$filename";
		if(is_dir($SourceFile)){continue;}
		$cmd=trim("$nice $gunzip -c $SourceFile |$prefix");
		echo "Restoring artica_backup/$filename\n";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		system($cmd);
	}

	system_admin_events("Restoring artica_backup done\n",__FUNCTION__,__FILE__,__LINE__);
}

function restore_powerdns($sourceDir){
	if(!is_dir("$sourceDir/powerdns")){
		backupevents("restore_powerdns:: $sourceDir/powerdns no such directory");
		echo "$sourceDir/powerdns no such directory\n";
		return true;
	}
	if (!$handle = opendir("$sourceDir/powerdns")) {
		backupevents("restore_powerdns:: Failed open $sourceDir/powerdns");
		echo "Failed open $sourceDir/powerdns\n";
		return;
	}
	$password=null;
	$unix=new unix();
	$sock=new sockets();
	$gunzip=$unix->find_program("gunzip");
	$mysql=$unix->find_program("mysql");
	$BLACKLIST=array();
	$nice=$unix->EXEC_NICE();
	$q=new mysql();
	
	if($q->mysql_password<>null){$password=" -p".$unix->shellEscapeChars($q->mysql_password);}
	$prefix=trim("$mysql --force -S /var/run/mysqld/mysqld.sock -u {$q->mysql_admin}$password powerdns");
	
	backupevents("Scanning ...$sourceDir/powerdns");
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		if(isset($BLACKLIST[$filename])){continue;}
		$SourceFile="$sourceDir/powerdns/$filename";
		backupevents("Importing $SourceFile");
		if(is_dir($SourceFile)){
			backupevents("$SourceFile is a directory, aborting");
			continue;
		}
		$cmd=trim("$nice $gunzip -c $SourceFile |$prefix");
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		backupevents("$cmd");
		shell_exec($cmd);
	}
	
	system_admin_events("Restoring PowerDNS done\n",__FUNCTION__,__FILE__,__LINE__);	
	
}


function restore_ocsweb($sourceDir){

	if (!$handle = opendir("$sourceDir/ocsweb")) {echo "Failed open $sourceDir/ocsweb\n";return;}
	$password=null;
	$unix=new unix();
	$sock=new sockets();
	$BackupArticaRestoreNetwork=intval($sock->GET_INFO("BackupArticaRestoreNetwork"));
	$gunzip=$unix->find_program("gunzip");
	$mysql=$unix->find_program("mysql");
	$BLACKLIST=array();
	$nice=$unix->EXEC_NICE();
	$q=new mysql();

	if($q->mysql_password<>null){$password=" -p".$unix->shellEscapeChars($q->mysql_password);}
	$prefix=trim("$mysql --force -S /var/run/mysqld/mysqld.sock -u {$q->mysql_admin}$password ocsweb");

	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		if(isset($BLACKLIST[$filename])){continue;}
		$SourceFile="$sourceDir/ocsweb/$filename";
		if(is_dir($SourceFile)){continue;}
		$cmd=trim("$nice $gunzip -c $SourceFile |$prefix");
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		shell_exec($cmd);
	}

	system_admin_events("Restoring ocsweb done\n",__FUNCTION__,__FILE__,__LINE__);
}
function restore_squidlogs($sourceDir){

	if (!$handle = opendir("$sourceDir/squidlogs")) {echo "Failed open $sourceDir/squidlogs\n";return;}
	$password=null;
	$unix=new unix();
	
	if(!$unix->is_socket("/var/run/mysqld/squid-db.sock")){system_admin_events("Error,/var/run/mysqld/squid-db.sock no such socket",__FUNCTION__,__FILE__,__LINE__); return false; }
	
	$sock=new sockets();
	$gunzip=$unix->find_program("gunzip");
	$mysql=$unix->find_program("mysql");
	$BLACKLIST=array();
	$nice=$unix->EXEC_NICE();
	$q=new mysql_squid_builder();
	
	$sock=new sockets();
	$BackupArticaRestoreNetwork=intval($sock->GET_INFO("BackupArticaRestoreNetwork"));
	if($GLOBALS["NOT_RESTORE_NETWORK"]){$BackupArticaRestoreNetwork=0;}
	if($BackupArticaRestoreNetwork==0){
		$BLACKLIST["dns_servers.gz"]=true;
		$BLACKLIST["dnsmasq_records.gz"]=true;
	}
	
	
	if($q->mysql_password<>null){$password=" -p".$unix->shellEscapeChars($q->mysql_password);}
	$prefix=trim("$mysql --force -S /var/run/mysqld/squid-db.sock -u {$q->mysql_admin}$password squidlogs");

	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		if(isset($BLACKLIST[$filename])){continue;}
		$SourceFile="$sourceDir/squidlogs/$filename";
		if(is_dir($SourceFile)){continue;}
		echo "Restoring Proxy database/$filename\n";
		$cmd=trim("$nice $gunzip -c $SourceFile |$prefix");
		system($cmd);
	}

	
}


