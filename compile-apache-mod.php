<?php
ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.ccurl.inc');

$unix=new unix();
$GLOBALS["SHOW_COMPILE_ONLY"]=false;
$GLOBALS["NO_COMPILE"]=false;
$GLOBALS["REPOS"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(isset($argv[1])){
	if($argv[1]=='--compile'){$GLOBALS["SHOW_COMPILE_ONLY"]=true;}
}
if(preg_match("#--no-compile#", @implode(" ", $argv))){$GLOBALS["NO_COMPILE"]=true;}
if(preg_match("#--verbose#", @implode(" ", $argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--repos#", @implode(" ", $argv))){$GLOBALS["REPOS"]=true;}
if(preg_match("#--force#", @implode(" ", $argv))){$GLOBALS["FORCE"]=true;}

$users=new usersMenus();
$APACHE_MODULES_PATH=$unix->APACHE_MODULES_PATH();
if(!is_dir($APACHE_MODULES_PATH)){
	echo "Unable to locate APACHE MODULES DIRECTORY...\n";
	die();
}

$timefile=$unix->file_time_min("/etc/artica-postfix/mod_rpaf-2.0.so.compile");
if($timefile<60){
	echo "Already executed since {$timefile}mn, need to wait 1h\n";
	die();
}

$wget=$unix->find_program("wget");
$tar=$unix->find_program("tar");
$rm=$unix->find_program("rm");
$cp=$unix->find_program("cp");
$dirsrc="mod_rpaf";
$Architecture=Architecture();
$apxs2=$unix->find_program("apxs2");
chdir("/root");
if(!is_file($apxs2)){
	echo "apxs2 no such binary...\n";
	die();
}
echo "Downloading mod_rpaf-0.6.tar.gz in order to compile in $APACHE_MODULES_PATH\n";
if(is_file("/root/mod_rpaf-0.6.tar.gz")){@unlink("/root/mod_rpaf-0.6.tar.gz");}
shell_exec("$wget http://www.articatech.net/download/mod_rpaf-0.6.tar.gz -O /root/mod_rpaf-0.6.tar.gz");
echo "Extracting mod_rpaf-0.6.tar.gz\n";
if(is_dir("/root/mod_rpaf")){shell_exec("$rm -rf /root/mod_rpaf");}
@mkdir("/root/mod_rpaf",0755,true);
shell_exec("$tar xf /root/mod_rpaf-0.6.tar.gz -C /root/mod_rpaf");
if(!is_dir("/root/mod_rpaf/mod_rpaf-0.6")){
	echo "/root/mod_rpaf/mod_rpaf-0.6 no such directory";
	die();
}
echo "Compiling mod_rpaf-0.6.tar.gz\n";
chdir("/root/mod_rpaf/mod_rpaf-0.6");
shell_exec("$apxs2 -i -c -n mod_rpaf-2.0.so mod_rpaf-2.0.c");

if(is_file("$APACHE_MODULES_PATH/mod_rpaf-2.0.so")){
	echo "Success, restart Web server...\n";
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php /usr/share/artica-postfix/exec.freeweb.php --build");
}

@file_put_contents("/etc/artica-postfix/mod_rpaf-2.0.so.compile", time());

die();







function Architecture(){
	$unix=new unix();
	$uname=$unix->find_program("uname");
	exec("$uname -m 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#i[0-9]86#", $val)){return 32;}
		if(preg_match("#x86_64#", $val)){return 64;}
	}
}
