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
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');

xstart();

function build_progress($text,$pourc){
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} {$pourc}% $text\n";}
	$cachefile="/usr/share/artica-postfix/ressources/logs/postgres.changedir.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}


function xstart(){
	
	$unix=new unix();
	$CurrentDirectory="/home/ArticaStatsDB";
	if(is_link($CurrentDirectory)){$CurrentDirectory=@readlink($CurrentDirectory);}
	
	
	$ChangePostGresSQLDir=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/ChangePostGresSQLDir"));
	
	echo "Current Directory....: $CurrentDirectory\n";
	echo "Destination Directory: $ChangePostGresSQLDir\n";
	
	if($ChangePostGresSQLDir==null){
		build_progress("{error} Destination Directory  = NULL",110);
		return;
		
	}
	if($ChangePostGresSQLDir==$CurrentDirectory){
		echo "Same directory....\n";
		build_progress("{success}",100);
		return;
	}
	
	if(!is_dir($ChangePostGresSQLDir)){
		@mkdir($ChangePostGresSQLDir,0755,true);
	}
	
	if(!is_dir($ChangePostGresSQLDir)){
		echo "$ChangePostGresSQLDir Permission denied or issue while creating the directory.\n";
		build_progress("{error} Destination Directory Permission denied",110);
		return;
	}
	
	$mv=$unix->find_program("mv");
	$ln=$unix->find_program("ln");
	build_progress("{stopping_service}",10);
	@file_put_contents("/etc/artica-postfix/settings/Daemons/FreeZePostGres", 1);
	system("/etc/init.d/artica-postgres stop");
	system("/etc/init.d/artica-postgres stop");
	build_progress("{moving_data}",20);
	system("$mv $CurrentDirectory/* $ChangePostGresSQLDir/");
	if(!@rmdir($CurrentDirectory)){
		build_progress("{moving_data} {failed}",30);
		sleep(3);
		system("$mv $ChangePostGresSQLDir/* $CurrentDirectory/");
		@file_put_contents("/etc/artica-postfix/settings/Daemons/FreeZePostGres", 0);
		build_progress("{starting_service} {moving_data} {failed}",90);
		sleep(3);
		build_progress("{moving_data} {failed}",110);
		
		return;
	}
	
	shell_exec("$ln -sf $ChangePostGresSQLDir /home/ArticaStatsDB");
	@file_put_contents("/etc/artica-postfix/settings/Daemons/FreeZePostGres", 0);
	sleep(3);
	build_progress("{starting_service}",90);
	sleep(3);
	build_progress("{moving_data} {success}",100);
}