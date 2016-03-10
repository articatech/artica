#!/usr/bin/php -q
<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["PROGRESS"]=true;
$GLOBALS["CLI"]=false;
$GLOBALS["TITLENAME"]="Kaspersky Update Utility";


$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');

xinstall();

function build_progress($text,$pourc){
	$echotext=$text;
	if(is_numeric($text)){$old=$pourc;$pourc=$text;$text=$old;}
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/UpdateUtility.install.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}


function xinstall(){
	$unix=new unix();
	$curl=new ccurl();

	$tmpfile=$unix->FILE_TEMP();
	$tmpdir=$unix->TEMP_DIR();
	build_progress("{downloading} v3.1.0-25",15);
	$curl=new ccurl("http://articatech.net/download/UpdateUtility/updateutility-3.1.0-25.tar.gz");
	if(!$curl->GetFile($tmpfile)){
		@unlink($tmpfile);
		build_progress("{downloading} {failed}",110);
		return;
	}
	
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	@mkdir("$tmpdir/updateutility",0755);
	build_progress("{uncompress}",20);
	shell_exec("$tar -xf $tmpfile -C $tmpdir/updateutility/");
	build_progress("{find_source_directory}",25);
	$dirs=$unix->dirdir("$tmpdir/updateutility");
	$SOURCE_DIRECTORY=null;
	
	while (list ($num, $ligne) = each ($dirs) ){
		build_progress("{scanning} $ligne",25);
		if(is_file("$ligne/UpdateUtility-Console")){
			
			$SOURCE_DIRECTORY=$ligne;
			break;
		}
	}

	if($SOURCE_DIRECTORY==null){
		echo "Unable to find source directory\n";
		build_progress("{installing} {failed}",110);
		shell_exec("$rm -rf $tmpdir/updateutility");
		return;
	}
	
	echo "Using directory $SOURCE_DIRECTORY\n";
	build_progress("{installing}...",80);
	$cp=$unix->find_program("cp");
	@mkdir("/etc/UpdateUtility",0755,true);
	shell_exec("$cp -rfv $SOURCE_DIRECTORY/* /etc/UpdateUtility/");
	shell_exec("$rm -rf $tmpdir/updateutility");
	
	if(!is_file("/etc/UpdateUtility/UpdateUtility-Console")){
		echo "/etc/UpdateUtility/UpdateUtility-Console no such binary\n";
		build_progress("{installing} {failed}",110);
		
	}
		
	build_progress("{installing} {success}",100);
	
	
	
	
}