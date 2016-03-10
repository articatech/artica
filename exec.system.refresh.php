<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="Kernel Optimization";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--reboot#",implode(" ",$argv),$re)){$GLOBALS["REBOOT"]=true;}
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["REBOOT"]=false;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');


startx();

function build_progress($pourc,$text){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/system.refreshcpu.progress", 
			serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/web/system.refreshcpu.progress",0755);
	sleep(1);

}

function startx(){
	$unix=new unix();
	build_progress(50,"{refresh} CPUS");
	@unlink("/etc/artica-postfix/CPU_NUMBER");
	build_progress(55,"{refresh} CPUS");
	@unlink("/usr/share/artica-postfix/ressources/interface-cache/CPU_NUMBER");
	build_progress(60,"{rescan-disk-system}");
	
	$dirs=$unix->dirdir("/sys/class/scsi_host");
	$echo=$unix->find_program("echo");
	$udevadm=$unix->find_program("udevadm");
	$php=$unix->LOCATE_PHP5_BIN();
	
	while (list ($dirpath, $line) = each ($dirs)){
		$basename=basename($dirpath);
		if(!preg_match("#host[0-9]+#", $basename)){continue;}
		$cmd="$echo \"- - -\" >$dirpath/scan";
		build_progress(65,"{rescan-disk-system}" .dirname($dirpath));
		shell_exec($cmd);
	}
	
	build_progress(70,"{rescan-disk-system}");
	$cmdline="$php /usr/share/artica-postfix/exec.usb.scan.write.php --verbose";
	system($cmd);
	
	build_progress(80,"{rescan-network-system}");
	system("$udevadm control --reload-rules");
	system("$udevadm trigger --attr-match=subsystem=net");
	
	sleep(3);
	system("/usr/share/artica-postfix/bin/process1 --force --verbose --".time());
	build_progress(100,"{refresh} {done}");
}



