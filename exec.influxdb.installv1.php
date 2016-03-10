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
install();


function DebianVersion(){

	$ver=trim(@file_get_contents("/etc/debian_version"));
	preg_match("#^([0-9]+)\.#",$ver,$re);
	if(preg_match("#squeeze\/sid#",$ver)){return 6;}
	return $re[1];

}

function install($filekey=0){
	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
	$unix=new unix();
	$filename=null;
	$MD5=null;
	$DebianVersion=DebianVersion();
	if($DebianVersion<7){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, PostgreSQL Debian version incompatible!\n";}
		build_progress_idb("Incompatible system!",110);
		die();
	}
	
	$filename="postgres-debian7-64-9.6.0.tar.gz";
	$MD5="17cc6a54b750b35de709505520ebd669";
	
	
	$curl=new ccurl("http://articatech.net/download/postgres-debian7-64-9.6.0.tar.gz");
	$tmpdir=$unix->TEMP_DIR();
	$php=$unix->LOCATE_PHP5_BIN();
	$rm=$unix->find_program("rm");
	$sock=new sockets();
	
	build_progress_idb("{downloading}",1);
	$curl->WriteProgress=true;
	$curl->ProgressFunction="download_progress";
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Downloading $filename\n";}
	if(!$curl->GetFile("$tmpdir/$filename")){
		
		build_progress_idb("$curl->error",110);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $curl->error\n";}
		
		while (list ($key, $value) = each ($curl->errors) ){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $value\n";}	
		}
		
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, influxdb unable to install....\n";}
		@unlink("$tmpdir/$filename");
		return;
	}
	
	if($MD5<>null){
		$DESTMD5=md5_file("$tmpdir/$filename");
		if($DESTMD5<>$MD5){
			echo "$DESTMD5<>$MD5\n";
			@unlink("$tmpdir/$filename");
			build_progress_idb("PostgreSQL: {install_failed} {corrupted_package}",110);
			return;
					
		}
		
	}
	
	build_progress_idb("{cleaning_backup}",50);
	$InFluxBackupDatabaseDir=$sock->GET_INFO("InFluxBackupDatabaseDir");
	if($InFluxBackupDatabaseDir==null){$InFluxBackupDatabaseDir="/home/artica/influx/backup";}
	
	if(is_dir($InFluxBackupDatabaseDir)){
		shell_exec("$rm -rf $InFluxBackupDatabaseDir");
	}
	build_progress_idb("PostgreSQL: {stopping_service}",60);
	system("/etc/init.d/artica-postgres stop");
	build_progress_idb("PostgreSQL: {removing_databases}",70);
	shell_exec("$rm -rf /home/artica/squid/InfluxDB");
	shell_exec("$rm -rf /etc/artica-postfix/DIRSIZE_MB_CACHE/*");
	build_progress_idb("PostgreSQL: {extracting}",80);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, extracting....\n";}
	$tar=$unix->find_program("tar");
	shell_exec("$tar xvf $tmpdir/$filename -C /");
	
	
	build_progress_idb("{restarting_service} (1/2)",90);
	system("/etc/init.d/artica-postgres restart");
	@unlink("/etc/artica-postfix/settings/Daemons/ArticaTechNetInfluxRepo");
	build_progress_idb("{refresh_status}",95);
	shell_exec("$php /usr/share/artica-postfix/exec.squid.interface-size.php --force");
	build_progress_idb("{refresh_status}",96);
	shell_exec("$php php5 /usr/share/artica-postfix/exec.web-community-filter.php --squid-repo --verbose >/dev/null 2>&1");
	build_progress_idb("{refresh_status}",97);
	system("/etc/init.d/squid-tail restart");
	build_progress_idb("{refresh_status}",98);
	system("/etc/init.d/hypercache-tail restart");
	build_progress_idb("{refresh_status}",99);
	system("/etc/init.d/ufdb-tail restart");
	build_progress_idb("{done}",100);
	
	
}
function download_progress( $download_size, $downloaded_size, $upload_size, $uploaded_size ){
	if(!isset($GLOBALS["previousProgress"])){$GLOBALS["previousProgress"]= 0;}

	if ( $download_size == 0 ){
		$progress = 0;
	}else{
		$progress = round( $downloaded_size * 100 / $download_size );
	}

	if ( $progress > $GLOBALS["previousProgress"]){
			if($progress<95){
				build_progress_idb("{downloading}",$progress);
			}
			$GLOBALS["previousProgress"]=$progress;
			
	}
}

function create_db(){
	$GLOBALS["DEBUG_INFLUX"]=true;
	$GLOBALS["VERBOSE"]=true;
	$influx=new influx();
	
}




function InfluxDbSize(){
	$dir="/home/artica/squid/InfluxDB";
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
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/influxdb.install.progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}	
?>