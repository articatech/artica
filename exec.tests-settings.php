<?php
ini_set('html_errors',0);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

echo "web_settings(): testing code... ". __LINE__."\n"; 

if(!is_file("/usr/share/artica-postfix/ressources/settings.new.inc")){
	echo "web_settings(): /usr/share/artica-postfix/ressources/settings.new.inc no such file ".__LINE__."\n";
	die();
}

@mkdir("/etc/artica-postfix/pids",0755,true);
$cachefile="/etc/artica-postfix/pids/".basename(__FILE__).".time";
if(is_file($cachefile)){$time=_file_time_min($cachefile); if($time<2){
	echo "web_settings(): $cachefile need at least 2mn ". __LINE__."\n";
	@unlink("/usr/share/artica-postfix/ressources/settings.new.inc");
	die();
	} 
}
@unlink($cachefile);@file_put_contents($cachefile, time());


echo "web_settings(): Include new settings... ". __LINE__."\n";
include("/usr/share/artica-postfix/ressources/settings.new.inc");

echo "web_settings(): Include new settings DONE!... ". __LINE__."\n";

if(!isset($_GLOBAL["ldap_admin"])){
	
	echo "web_settings(): ldap_admin pattern is not found !!!!!\n";
	if(!is_file("/etc/init.d/artica-process1")){
		echo "/etc/init.d/artica-process1 not found\n";
		die();
	}

	shell_exec("/etc/init.d/artica-process1 start");
	die();
}

echo "web_settings(): Open Source file... ". __LINE__."\n";
$t=@file_get_contents("/usr/share/artica-postfix/ressources/settings.new.inc");
if(preg_match("#<\?php(.+?)\?>#is", $t,$re)){
	echo "web_settings(): OK ". __LINE__."\n";
	@file_put_contents("/usr/share/artica-postfix/ressources/settings.inc","<?php\n{$re[1]}\n?>");
	@chmod("/usr/share/artica-postfix/ressources/settings.inc",0755);
	@unlink("/usr/share/artica-postfix/ressources/settings.new.inc");
	@file_put_contents("/tmp/settings.ok", time());
}else{
	echo "web_settings(): Unable to preg_match !!!!\n";
}


function _file_time_min($path){
	$last_modified=0;

	if(is_dir($path)){return 10000;}
	if(!is_file($path)){return 100000;}
	$size=@filesize($path);
	if(strpos($path, "artica-postfix/")>0){
		if($size<15){
			$xtime=trim(@file_get_contents($path));
			if(is_numeric($xtime)){
				if($xtime>1000000000){$last_modified=$xtime;}
			}
		}
	}

	if($last_modified==0){$last_modified = filemtime($path);}
	$data1 = $last_modified;
	$data2 = time();
	$difference = ($data2 - $data1);
	$results=intval(round($difference/60));
	if($results<0){$results=1;}
	return $results;
}