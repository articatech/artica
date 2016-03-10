<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');
$GLOBALS["SINGLE_DEBUG"]=false;
$GLOBALS["NOT_FORCE_PROXY"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["BYCRON"]=false;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["CHANGED"]=false;
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--force-nightly#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;$GLOBALS["FORCE"]=true;$GLOBALS["FORCE_NIGHTLY"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){
	$GLOBALS["VERBOSE"]=true;
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string'," Fatal..:");
	ini_set('error_append_string',"\n");
}

download();

function build_progress($text,$pourc){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/wordpress.reconfigure.progress", serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);

}

function download(){

	$unix=new unix();
	
	build_progress("{downloading}",10);
	
	$URI="http://wordpress.org/latest.tar.gz";
	
	$TMP_FILE=$unix->FILE_TEMP().".gz";
	$TMP_DIR=$unix->TEMP_DIR();
	echo "Downloading $URI\n";
	$curl=new ccurl($URI);
	$curl->WriteProgress=true;
	$curl->ProgressFunction="download_progress";
	if(!$curl->GetFile($TMP_FILE)){
		build_progress("{downloading} {failed}",110);
		echo $curl->error;
		return;
	}

echo "Extracting $TMP_FILE in $TMP_DIR\n";
$tar=$unix->find_program("tar");
$cmd="$tar xf $TMP_FILE -C $TMP_DIR/";
build_progress("{uncompress}",50);
shell_exec("$tar xf $TMP_FILE -C $TMP_DIR/");
@unlink($TMP_FILE);
$dirs=$unix->dirdir($TMP_DIR);
$WDP_DIR=null;
while (list ($num, $ligne) = each ($dirs) ){
	if(!is_file("$ligne/wp-admin/install.php")){continue;}
	$WDP_DIR=$ligne;
	break;
	echo "Find Directory $ligne\n";
}
if(!is_dir($WDP_DIR)){
	build_progress("Find directory failed",110);
	echo "Find directory failed\n";
	return;	
}
build_progress("{installing}",80);
@mkdir("/usr/share/wordpress-src",0755,true);
$cp=$unix->find_program("cp");
$rm=$unix->find_program("rm");
shell_exec("cp -rfv $WDP_DIR/* /usr/share/wordpress-src/");
if(is_dir($WDP_DIR)){
	echo "Removing $WDP_DIR\n";
	shell_exec("$rm -rf $WDP_DIR");
}
$sock=new sockets();
$sock->SET_INFO("EnableFreeWeb", 1);
@file_put_contents("/etc/artica-postfix/settings/Daemons/WordPressInstalled", 1);
system("/etc/init.d/artica-status restart --force");

build_progress("{success}",100);
$nohup=$unix->find_program("nohup");
$sock=new sockets();


shell_exec("$nohup /usr/share/artica-postfix/bin/process1 --verbose 654646 >/dev/null 2>&1 &");

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
			echo "Downloading {$progress}% {$downloaded_size}/$download_size";
		}
		$GLOBALS["previousProgress"]=$progress;
			
	}
}


