#!/usr/bin/php -q
<?php
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="InfluxDB Daemon";
$GLOBALS["PROGRESS"]=false;
$GLOBALS["MIGRATION"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;
$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--migration#",implode(" ",$argv),$re)){$GLOBALS["MIGRATION"]=true;}

$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.influx.inc');

$GLOBALS["ARGVS"]=implode(" ",$argv);
backup();


function DebianVersion(){

	$ver=trim(@file_get_contents("/etc/debian_version"));
	preg_match("#^([0-9]+)\.#",$ver,$re);
	if(preg_match("#squeeze\/sid#",$ver)){return 6;}
	return $re[1];

}


function backup(){
	build_progress_idb("{backup_database}",20);
	$unix=new unix();
	$targetFilename="/home/ArticaStatsBackup/backup.db";
	$su=$unix->find_program("su");
	@mkdir("/home/ArticaStatsBackup",0777,true);
	@chmod("/home/ArticaStatsBackup",0777);
	if(is_file($targetFilename)){@unlink($targetFilename);}
	
	$InFluxBackupDatabaseDir=@file_get_contents("/etc/artica-postfix/settings/Daemons/InFluxBackupDatabaseDir");
	if($InFluxBackupDatabaseDir==null){$InFluxBackupDatabaseDir="/home/artica/influx/backup";}
	$CompressFileName="$InFluxBackupDatabaseDir/snapshot.".date("Y-m-d-H-i").".gz";
	
	@mkdir($InFluxBackupDatabaseDir,0755,true);
	
	
	if(is_file($CompressFileName)){
		build_progress_idb("{backup_database} already exists",110);
	}
	
	$cmdline="$su -c \"/usr/local/ArticaStats/bin/pg_dumpall -c --if-exists -S ArticaStats -f $targetFilename -h /var/run/ArticaStats\" ArticaStats";
	echo $cmdline."\n";
	
	exec($cmdline,$results);
	build_progress_idb("{backup_database}",30);

	if(!is_file($targetFilename)){
		echo "$targetFilename No such file\n";
		while (list ($num, $val) = each ($results)){
			echo "$val\n";
			
		}
		
		build_progress_idb("{backup_database} {failed}",110);
		return;
	}
	
	
	
	
	build_progress_idb("{compressing}",50);
	echo "Compress $targetFilename\n";
	echo "Destination $CompressFileName\n";
	if(!$unix->compress($targetFilename, $CompressFileName)){
		build_progress_idb("{compressing} {failed}",110);
		squid_admin_mysql(0, "Snaphost BigData database {failed} ( compress )", null,__FILE__,__LINE__);
		@unlink($targetFilename);
		@unlink($CompressFileName);
		return;
		
	}
	@unlink($targetFilename);
	$size=FormatBytes(@filesize($CompressFileName)/1024);
	squid_admin_mysql(2, "Backup [".basename($CompressFileName)."] BigData database ($size) done", null,__FILE__,__LINE__);
	build_progress_idb("{scanning}",80);
	ScanBackup();
	build_progress_idb("{backup_database} {success}",100);
}

function ScanBackup(){
	
	$q=new mysql();
	$InFluxBackupDatabaseDir=@file_get_contents("/etc/artica-postfix/settings/Daemons/InFluxBackupDatabaseDir");
	if($InFluxBackupDatabaseDir==null){$InFluxBackupDatabaseDir="/home/artica/influx/backup";}
	$PostGresBackupMaxContainers=@file_get_contents("/etc/artica-postfix/settings/Daemons/PostGresBackupMaxContainers");
	
	
	
	
	$sql="CREATE TABLE IF NOT EXISTS `postgres_backups` (
				`filename` VARCHAR( 90 ),
				`filepath` VARCHAR( 250 ),
				`filesize` BIGINT UNSIGNED,
				`filetime` BIGINT UNSIGNED,
				 PRIMARY KEY (`filepath`),
				  KEY `filesize` (`filesize`),
				  KEY `filetime` (`filetime`)
				) ENGINE=MYISAM;";
	
	$q->QUERY_SQL($sql,"artica_backup");
	$unix=new unix();
	$patnz=$unix->DirFiles($InFluxBackupDatabaseDir,"\.gz$");
	
	$q->QUERY_SQL("TRUNCATE TABLE postgres_backups");
	
	while (list ($filepath, $none) = each ($patnz) ){
		$filepath="$InFluxBackupDatabaseDir/$filepath";
		
		
		$filename=basename($filepath);
		$filesize=@filesize($filepath);
		$filetime=filemtime($filepath);
		$ARRAY[$filepath]=$filesize;
		$q->QUERY_SQL("INSERT IGNORE INTO postgres_backups (`filename`,`filepath`,`filesize`,`filetime`) VALUES ('$filename','$filepath','$filesize','$filetime')","artica_backup");
		
	}
	@file_put_contents("/etc/artica-postfix/settings/Daemons/InfluxDBRestoreArray", serialize($ARRAY));
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(*) as tcount FROM postgres_backups","artica_backup"));
	$containers=$ligne["tcount"];
	echo "Containers:$containers\n";
	if($containers>$PostGresBackupMaxContainers){
		
		$results=$q->QUERY_SQL("SELECT filepath FROM postgres_backups ORDER BY filetime LIMIT 0,$ContainersToDelete","artica_backup");
		$c=0;
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$c++;
			if($c==$PostGresBackupMaxContainers){break;}
			if($c>$PostGresBackupMaxContainers){break;}
			@unlink($ligne["filepath"]);
			$q->QUERY_SQL("DELETE FROM postgres_backups WHERE filepath='{$ligne["filepath"]}'");
		}
		
		
	}
	
}


function InfluxDbSize(){
	$dir="/home/ArticaStatsDB";
	$unix=new unix();
	$size=$unix->DIRSIZE_KO($dir);
	$partition=$unix->DIRPART_INFO($dir);
	
	$TOT=$partition["TOT"];
	$percent=($size/$TOT)*100;
	$percent=round($percent,3);
	
	
	if($GLOBALS["VERBOSE"]){echo "$dir: $size Partition $TOT\n";}
	
	$ARRAY["PERCENTAGE"]=$percent;
	$ARRAY["SIZEKB"]=$size;
	$ARRAY["PART"]=$TOT;
	
	if($GLOBALS["VERBOSE"]){print_r($ARRAY);};
	@unlink("/usr/share/artica-postfix/ressources/logs/web/InfluxDB.state");
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/InfluxDB.state", serialize($ARRAY));
	
}
function build_progress_idb($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/postgres.backup.progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}	
?>