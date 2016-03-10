#!/usr/bin/php -q
<?php
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="IDS Daemon";
$GLOBALS["PROGRESS"]=true;
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
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');


if($argv[1]=="--install"){$GLOBALS["OUTPUT"]=true;install($argv[2],$argv[3]);die();}

function build_progress_idb($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/mailsecurity.install.progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

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
			build_progress_idb("{downloading} {$downloaded_size}/$download_size",$progress);
		}
		$GLOBALS["previousProgress"]=$progress;
			
	}
}

function DebianVersion(){

	$ver=trim(@file_get_contents("/etc/debian_version"));
	preg_match("#^([0-9]+)\.#",$ver,$re);
	if(preg_match("#squeeze\/sid#",$ver)){return 6;}
	return "debian{$re[1]}";

}

function install($filekey=0,$OS){
	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
	$unix=new unix();
	$filename=null;
	$MD5=null;
	$DebianVersion=DebianVersion();
	if($OS<>$DebianVersion){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, influxdb Debian version incompatible!\n";}
		build_progress_idb("Incompatible system $OS<>$DebianVersion!",110);
		die();
	}

	if($filekey<>0){
		$sock=new sockets();
		$ArticaTechNetHaProxyRepo=unserialize(base64_decode($sock->GET_INFO("ArticaTechNetMailSecurityRepo")));
		$version=$ArticaTechNetHaProxyRepo[$filekey][$OS]["VERSION"];
		$filename=$ArticaTechNetHaProxyRepo[$filekey][$OS]["FILENAME"];
		$MD5=$ArticaTechNetHaProxyRepo[$filekey][$OS]["MD5"];
		$URL=$ArticaTechNetHaProxyRepo[$filekey][$OS]["URL"];
	}

	$rmmod=$unix->find_program("rmmod");
	$depmod=$unix->find_program("depmod");
	$modprobe=$unix->find_program("modprobe");
	$ldconfig=$unix->find_program("ldconfig");
	echo "Downloading $URL\n";
	$curl=new ccurl($URL);
	$tmpdir=$unix->TEMP_DIR();
	$php=$unix->LOCATE_PHP5_BIN();

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


		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, HaProxy unable to install....\n";}
		@unlink("$tmpdir/$filename");
		return;
	}

	if($MD5<>null){
		$DESTMD5=md5_file("$tmpdir/$filename");
		if($DESTMD5<>$MD5){
			echo "$DESTMD5<>$MD5\n";
			@unlink("$tmpdir/$filename");
			build_progress_idb("{install_failed} {corrupted_package}",110);
			return;
				
		}

	}

	build_progress_idb("{stopping_service}",95);
	
	if(!is_file("/etc/init.d/clamav-milter")){
		system("$php /usr/share/artica-postfix/exec.initslapd.php --clamav-milter");
	}
	

	
	build_progress_idb("{extracting}",96);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, extracting....\n";}
	$tar=$unix->find_program("tar");
	

	shell_exec("$tar xvf $tmpdir/$filename -C /");

	build_progress_idb("{installing} 1/1",96);
	system("$ldconfig");
	


	if($GLOBALS["PROGRESS"]){
		build_progress_idb("{restarting_service}",97);
		system("$php /usr/share/artica-postfix/exec.status.php --clamav >/dev/null");
		system("/etc/init.d/artica-status restart --force");
		build_progress_idb("{restarting_service}",98);
		system("/etc/init.d/clamav-milter restart");
		system("/etc/init.d/clamav-daemon restart");
		system("/etc/init.d/milter-regex restart");
		system("/etc/init.d/spamassassin restart");
	}

	build_progress_idb("{refresh_status}",98);
	build_progress_idb("{done}",100);


}