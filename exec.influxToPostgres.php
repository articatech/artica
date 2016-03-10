<?php
$GLOBALS["VERBOSE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["FORCE"]=true;$GLOBALS["OUTPUT"]=true;}
if($GLOBALS["VERBOSE"]){
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
}

if(!is_file("/usr/local/ArticaStats/bin/postgres")){die();}
$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
if($EnableIntelCeleron==1){die();}

include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");


ACCESS_BACKUP();

function ACCESS_BACKUP(){
	
	
	$unix=new unix();
	
	$filetime="/etc/artica-postfix/pids/migrate_postgres.time";
	$pidfile="/etc/artica-postfix/pids/migrate_postgres.pid";
	$GLOBALS["LogFileDeamonLogDir"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/LogFileDeamonLogPostGresDir");
	if($GLOBALS["LogFileDeamonLogDir"]==null){$GLOBALS["LogFileDeamonLogDir"]="/home/artica/squid-postgres/realtime-events";}
	
	$unix=new unix();
	if($unix->process_exists($unix->get_pid_from_file($pidfile),basename(__FILE__))){die();}
	@file_put_contents($pidfile, getmypid());
	
	$TimeExe=$unix->file_time_min($filetime);
	if(!$GLOBALS["FORCE"]){
		if($TimeExe<60){die();}
	}
	
	@unlink($filetime);
	@file_put_contents($filetime, time());
	
	$FilesCount=0;
	$TargetDir="{$GLOBALS["LogFileDeamonLogDir"]}/access-InfluxToPostgresSQL";
	@mkdir($TargetDir,0755);
	
	if(system_is_overloaded(basename(__FILE__))){return;}
	
	$PossibleDirs[]="/home/artica/squid/realtime-events/access-failed";
	$PossibleDirs[]="/home/artica/squid/realtime-events/failed-backup-longtime";
	
	while (list ($index, $PossibleDirectory) = each ($PossibleDirs)){
		if(!is_dir($PossibleDirectory)){continue;}	
		
		$Files=$unix->DirFiles($PossibleDirectory);
		while (list ($filename, $none) = each ($Files)){
			squid_admin_mysql(1,"Starting importing $filename", null,__FILE__, __LINE__);
			if(ACCESS_BACKUP_SCAN("$PossibleDirectory/$filename")){
				$FilesCount++;
				$unix->compress("$PossibleDirectory/$filename", "$TargetDir/$filename.gz");
				@unlink("$PossibleDirectory/$filename");
			}
			if(system_is_overloaded(basename(__FILE__))){
				squid_admin_mysql(1,"Stopping injection after $FilesCount imported files (overloaded)", null,__FILE__, __LINE__);
				return;
			}
		
		}
	}
	

	
	
	$Directory="/home/artica/squid/realtime-events/access-backup";
	$Files=$unix->DirFiles($Directory,"\.gz$");
	if(!is_dir($Directory)){return;}
		
	while (list ($filename, $none) = each ($Files)){
			
		squid_admin_mysql(1,"Starting importing $filename", null,__FILE__, __LINE__);
		if(ACCESS_BACKUP_SCAN("$Directory/$filename")){
			$FilesCount++;
			@copy("$Directory/$filename", "$TargetDir/$filename");
			@unlink("$Directory/$filename");
		}
			
		if(system_is_overloaded(basename(__FILE__))){
			squid_admin_mysql(1,"Stopping injection after $FilesCount imported files (overloaded)", null,__FILE__, __LINE__);
			return;
		}
			
	}

		
	if(system_is_overloaded(basename(__FILE__))){
		squid_admin_mysql(1,"Stopping injection after $FilesCount imported files (overloaded)", null,__FILE__, __LINE__);
		return;
	}
		
	$Files=$unix->DirFiles($Directory,"\.back$");
	
		
	while (list ($filename, $none) = each ($Files)){
		squid_admin_mysql(1,"Starting importing $filename", null,__FILE__, __LINE__);
		if(ACCESS_BACKUP_SCAN("$Directory/$filename")){
			$FilesCount++;
			$unix->compress("$Directory/$filename", "$TargetDir/$filename.gz");
			@unlink("$Directory/$filename");
		}
		if(system_is_overloaded(basename(__FILE__))){
			squid_admin_mysql(1,"Stopping injection after $FilesCount imported files (overloaded)", null,__FILE__, __LINE__);
			return;
		}
				
	}
		
	$Files=$unix->DirFiles($Directory);
		
		
	while (list ($filename, $none) = each ($Files)){
		if(!preg_match("#^[0-9]+-[0-9]+-[0-9]+$#", $filename)){if(is_numeric($filename)){@unlink("$Directory/$filename");}continue;}
		
		squid_admin_mysql(1,"Starting importing $filename", null,__FILE__, __LINE__);
		if(ACCESS_BACKUP_SCAN("$Directory/$filename")){
			$FilesCount++;
			@copy("$Directory/$filename", "$TargetDir/$filename");
			@unlink("$Directory/$filename");
		}
		if(system_is_overloaded(basename(__FILE__))){
			squid_admin_mysql(1,"Stopping injection after $FilesCount imported files (overloaded)", null,__FILE__, __LINE__);
			return;
		}
		
	}		
		
	$Files=$unix->DirFiles($Directory);
	if(count($Files)==0){@rmdir($Directory);}
	

}

function ACCESS_BACKUP_SCAN($filename){
	$unix=new unix();
	$BaseName=basename($filename);
	$tempfile=$unix->FILE_TEMP();
	
	if(preg_match("#\.gz$#", $BaseName)){
		$zcat=$unix->find_program("zcat");
		shell_exec("$zcat $filename >$tempfile");
	}else{
		@copy($filename, $tempfile);
	}
	
	$handle = @fopen($tempfile, "r");
	if (!$handle) {echo "Failed to open file\n";return;}
	
	$prefix="INSERT INTO access_log (zdate,category,ipaddr,mac,sitename,familysite,proxyname,userid,size,rqs) VALUES ";
	$MASTER_C=0;
	$MAIN=array();
	$MASTER_G=0;
	while (!feof($handle)){
		$pattern =trim(fgets($handle));
		if($pattern==null){continue;}
		$ARRAY=LineToArray($pattern);
		if(count($ARRAY)<5){echo "$pattern no match\n";continue;}
		if(!isset($ARRAY["MAC"])){$ARRAY["MAC"]="00:00:00:00:00:00";}
		if($ARRAY["MAC"]===null){$ARRAY["MAC"]="00:00:00:00:00:00";}
		$IP=new IP();
		if(!$IP->isValid($ARRAY["IPADDR"])){continue;}
		if(!$IP->IsvalidMAC($ARRAY["MAC"])){$ARRAY["MAC"]="00:00:00:00:00:00";}
		if(!isset($ARRAY["USERID"])){$ARRAY["USERID"]=null;}
		if(!isset($ARRAY["CATEGORY"])){$ARRAY["CATEGORY"]=null;}
		
		
		if(strlen($ARRAY["SITE"])>128){$ARRAY["SITE"]=substr(0, 127,$ARRAY["SITE"]);}
		if(strlen($ARRAY["FAMILYSITE"])>128){$ARRAY["FAMILYSITE"]=substr(0, 127,$ARRAY["FAMILYSITE"]);}
		if(strlen($ARRAY["PROXYNAME"])>128){$ARRAY["PROXYNAME"]=substr(0, 127,$ARRAY["PROXYNAME"]);}

		
		
		
		$date=$ARRAY["ZDATE"];
		
		$MAIN[]="('$date','{$ARRAY["CATEGORY"]}','{$ARRAY["IPADDR"]}','{$ARRAY["MAC"]}','{$ARRAY["SITE"]}','{$ARRAY["FAMILYSITE"]}','{$ARRAY["PROXYNAME"]}','{$ARRAY["USERID"]}','{$ARRAY["SIZE"]}','{$ARRAY["RQS"]}')";
		
		$MASTER_C++;
		$CountOfMain=count($MAIN);

		if($CountOfMain>1000){
			$MASTER_G++;
			squid_admin_mysql(1,"$MASTER_G]: Injecting $CountOfMain events from $BaseName",null, __FILE__, __LINE__);
			$q=new postgres_sql();
			$q->QUERY_SQL($prefix.@implode(",", $MAIN));
			if(!$q->ok){
				$MAIN=array();
				squid_admin_mysql(0,"Failed Injecting $CountOfMain events from $BaseName", $q->mysql_error,__FILE__, __LINE__);
				echo $q->mysql_error;
				return false;
			}
			
			$MAIN=array();
			
		}
		
		
	}
	
	$CountOfMain=count($MAIN);
	if(count($MAIN)>0){
		
		$q=new postgres_sql();
		squid_admin_mysql(1,"(FINAL) Injecting $CountOfMain events from $BaseName",null, __FILE__, __LINE__);
		$q->QUERY_SQL($prefix.@implode(",", $MAIN));
		if(!$q->ok){
			squid_admin_mysql(0,"(FINAL) SQL error: $q->mysql_error",null, __FILE__, __LINE__);
			echo $q->mysql_error;
			return false;
		}
		
	}
	
	
	echo "$filename, Injected $MASTER_C items\n";
	echo "Vaccum....\n";
	squid_admin_mysql(1,basename($filename).": running  vacuumdb...", null,__FILE__, __LINE__);
	shell_exec("/usr/local/ArticaStats/bin/vacuumdb -h /var/run/ArticaStats --dbname=proxydb --username=ArticaStats");
	squid_admin_mysql(1,basename($filename).": Injected $MASTER_C items ", null,__FILE__, __LINE__);
	InfluxDbSize();
	return true;
}

function LineToArray($line){

	if(preg_match("#access_log.*?CATEGORY=(.+?),#",$line,$re)){
		$ARRAY["CATEGORY"]=$re[1];
	}
	if(preg_match("#access_log.*?IPADDR=([0-9\.]+),#",$line,$re)){
		$ARRAY["IPADDR"]=$re[1];
	}		
	if(preg_match("#access_log.*?MAC=(.*?),#",$line,$re)){
		$ARRAY["MAC"]=$re[1];
	}		
	if(preg_match("#access_log.*?SITE=(.+?),#",$line,$re)){
		$ARRAY["SITE"]=$re[1];
	}	
	if(preg_match("#access_log.*?FAMILYSITE=(.+?),#",$line,$re)){
		$ARRAY["FAMILYSITE"]=$re[1];
	}
	if(preg_match("#access_log.*?proxyname=(.*?)\s+#",strtolower($line),$re)){
		$proxyname=$re[1];
		$proxyname=str_replace(",", "", $proxyname);
		if(strpos($proxyname, " ")>0){
			$proxynameTR=explode(".",$proxyname);
			$proxyname=$proxynameTR[0];
		}
		$ARRAY["PROXYNAME"]=$proxyname;
	}	
	if(preg_match("#access_log.*?USERID=(.+?),#",$line,$re)){
		$ARRAY["USERID"]=$re[1];
	}
	if(preg_match("#access_log.*?ZDATE=([0-9]+)#",$line,$re)){
		$ARRAY["ZDATE"]=date("Y-m-d H:i:s",$re[1]);
	}
	if(preg_match("#access_log.*?SIZE=([0-9]+)#",$line,$re)){
		$ARRAY["SIZE"]=$re[1];
	}	
	if(preg_match("#access_log.*?RQS=([0-9]+)#",$line,$re)){
		$ARRAY["RQS"]=$re[1];
	}
	
	return $ARRAY;

}
function InfluxDbSize(){
	$dir="/home/ArticaStatsDB";
	$unix=new unix();
	$size=$unix->DIRSIZE_KO_nocache($dir);
	$partition=$unix->DIRPART_INFO($dir);

	$TOT=$partition["TOT"];
	$percent=($size/$TOT)*100;
	$percent=round($percent,3);

	echo "$dir: $size Partition $TOT\n";
	if($GLOBALS["VERBOSE"]){echo "$dir: $size Partition $TOT\n";}

	

	$ARRAY["PERCENTAGE"]=$percent;
	$ARRAY["SIZEKB"]=$size;
	$ARRAY["PART"]=$TOT;

	
	@unlink("/usr/share/artica-postfix/ressources/logs/web/InfluxDB.state");
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/InfluxDB.state", serialize($ARRAY));
	
}

