<?php

$GLOBALS["PROGRESS"]=false;
$GLOBALS["UPDATE_GRUB"]=false;
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/framework/class.settings.inc");


if($argv[1]=="--optimize"){optimize();die();}
if($argv[1]=="--rc-local"){rc_local();die();}


function optimize(){
	$unix=new unix();
	$GLOBALS["PROGRESS"]=true;
	$GLOBALS["UPDATE_GRUB"]=true;
	$sock=new sockets();
	$php=$unix->LOCATE_PHP5_BIN();
	$EnableSystemOptimize=intval($sock->GET_INFO("EnableSystemOptimize"));
	
	if($EnableSystemOptimize==1){
		build_progress("{enable_system_optimization}: ON",10);
		EnableScheduler();
		rc_local();
		$ARRAY=unserialize(base64_decode($sock->GET_INFO("kernel_values")));
		$ARRAY["swappiness"]=0;
		@file_put_contents("/etc/artica-postfix/settings/Daemons/kernel_values", serialize($ARRAY));
		build_progress("Build Kernel values....",35);
		system("$php /usr/share/artica-postfix/exec.sysctl.php --restart");
		build_progress("Optimize system disk partitions",50);
		system("$php /usr/share/artica-postfix/exec.patch.fstab.php");
		build_progress("{done}",100);
	}else{
		build_progress("{enable_system_optimization}: OFF",10);
		DisableScheduler();
		$ARRAY=unserialize(base64_decode($sock->GET_INFO("kernel_values")));
		$ARRAY["swappiness"]=60;
		@file_put_contents("/etc/artica-postfix/settings/Daemons/kernel_values", serialize($ARRAY));
		build_progress("Build Kernel values....",35);
		system("$php /usr/share/artica-postfix/exec.sysctl.php --restart");
		build_progress("Optimize system disk partitions",50);
		system("$php /usr/share/artica-postfix/exec.patch.fstab.php");
		build_progress("{done}",100);
	}
	
}

function rc_local(){
	echo "Starting......: ".date("H:i:s")." Optimize, Scanning HARD drives\n";
	$unix=new unix();
	$dirs=$unix->dirdir("/sys/class/scsi_generic");
	$echobin=$unix->find_program("echo");
	
	$f[]="#!/bin/sh -e";
	
	while (list ($num, $directory) = each ($dirs) ){
		$file="$directory/device/timeout";
		$basename=basename($directory);
		if(!is_file($file)){
			echo "Starting......: ".date("H:i:s")." Optimize, SKIP $file\n";
			continue;}
		echo "Starting......: ".date("H:i:s")." Optimize,$basename\n";
		$f[]="$echobin 180 >$file || true";
		shell_exec("$echobin 180 >$file");
		
		
	}
	
	$dirs=$unix->dirdir("/sys/block");
	while (list ($num, $directory) = each ($dirs) ){
		$file="$directory/device/timeout";
		$basename=basename($directory);
		if(!is_file($file)){
			echo "Starting......: ".date("H:i:s")." Optimize, SKIP $file\n";
			continue;}
		echo "Starting......: ".date("H:i:s")." Optimize,$basename\n";
		$f[]="$echobin 180 >$file || true";
		shell_exec("$echobin 180 >$file");
	}
	
	$f[]="";
	$f[]="exit 0";
	$f[]="";
	@file_put_contents("/etc/rc.local", @implode("\n", $f));
	@chmod("/etc/rc.local",0755);
	
}



EnableScheduler();


function build_progress($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/system.optimize.progress";
	if(!$GLOBALS["PROGRESS"]){return;}
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	sleep(1);

}


function EnableScheduler(){

$unix=new unix();

$dmidecode=$unix->find_program("dmidecode");
if(!is_file($dmidecode)){return;}


exec("$dmidecode 2>&1",$results);
$vmware=false;
while (list ($num, $ligne) = each ($results) ){
	if(preg_match("#Manufacturer.+?VMware#i", $ligne)){$vmware=true;break;}
}

if(!$vmware){
	echo "Starting......: ".date("H:i:s")." Not a VMware machine...\n";
	$EnableSystemOptimize=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableSystemOptimize"));
	if($EnableSystemOptimize==0){return;}
	
}
$echo=$unix->find_program("echo");
$array=$unix->dirdir("/sys/block");

build_progress("Optimize kernel to LOOP",15);

while (list ($num, $directory) = each ($array) ){
	if(is_file("$directory/queue/scheduler")){
		build_progress("Optimize ".basename($directory),20);
		echo "Starting......: ".date("H:i:s")." Optimize, turn scheduler to noop on ". basename($directory)."\n";
		shell_exec("$echo noop >$directory/queue/scheduler");
	}
	
}
$update=false;
$GRUB_DISABLE_OS_PROBER=false;
$GRUB_GFXMODE=false;


if(is_file("/etc/default/grub")){
	$grb=explode("\n",@file_get_contents("/etc/default/grub"));
	while (list ($num, $line) = each ($grb) ){
		if(preg_match("#^GRUB_CMDLINE_LINUX_DEFAULT#",$line)){
			if(strpos($line, "noop")==0){
				build_progress("GRUB_CMDLINE_LINUX_DEFAULT",25);
				echo "Starting......: ".date("H:i:s")." Optimize, Grub N1\n";
				$grb[$num]="GRUB_CMDLINE_LINUX_DEFAULT=\"quiet max_loop=256 elevator=noop\"";
				$update=true;
			}
				continue;
		}
		if(preg_match("#^GRUB_CMDLINE_LINUX#",$line)){
			if(strpos($line, "noop")==0){
				build_progress("GRUB_CMDLINE_LINUX",25);
				echo "Starting......: ".date("H:i:s")." Optimize, Grub N2\n";
				$grb[$num]="GRUB_CMDLINE_LINUX=\"quiet max_loop=256 elevator=noop\"";
				$update=true;
			}
			continue;
		}
		
		if(preg_match("#^GRUB_DISABLE_OS_PROBER#",$line)){
			$GRUB_DISABLE_OS_PROBER=true;
			if(strpos($line, "true")==0){
				$grb[$num]="GRUB_DISABLE_OS_PROBER=true";
				$update=true;
			}
			continue;
		}
		
		if(preg_match("#^GRUB_GFXMODE#",$line)){
			$GRUB_GFXMODE=true;
			if(strpos($line, "800")==0){
				$grb[$num]="GRUB_GFXMODE=800x600,640x480";
				$update=true;
			}
			continue;
		}
		
}
	
	
	if(!$GRUB_DISABLE_OS_PROBER){
		$grb[]="GRUB_DISABLE_OS_PROBER=true\n";
		$update=true;
	}
	if(!$GRUB_GFXMODE){
		$grb[]="GRUB_GFXMODE=800x600\n";
		$update=true;
	}	
	
	if($GLOBALS["UPDATE_GRUB"]){$update=true;}
	
	if($update){
		$tmp=$unix->FILE_TEMP();
		build_progress("Update GRUB....",30);
		echo "Starting......: ".date("H:i:s")." Optimize, Grub N2\n";
		@file_put_contents("/etc/default/grub", @implode("\n", $grb));
		shell_exec("/usr/sbin/update-grub >$tmp 2>&1");
		$f=explode("\n",@file_get_contents("$tmp"));
		while (list ($num, $line) = each ($f) ){echo "Starting......: ".date("H:i:s")." $line\n";}
	
	}
	
}

}



function DisableScheduler(){

	$unix=new unix();

	$echo=$unix->find_program("echo");
	$array=$unix->dirdir("/sys/block");

	build_progress("Optimize kernel to LOOP",15);

	while (list ($num, $directory) = each ($array) ){
		if(is_file("$directory/queue/scheduler")){
			build_progress("Optimize ".basename($directory),20);
			echo "Starting......: ".date("H:i:s")." Optimize, turn scheduler to noop and deadline on ". basename($directory)."\n";
			shell_exec("$echo \"noop deadline [cfq]\" >$directory/queue/scheduler");
		}

	}
	$update=false;
	$GRUB_DISABLE_OS_PROBER=false;
	$GRUB_GFXMODE=false;


	if(is_file("/etc/default/grub")){
		$grb=explode("\n",@file_get_contents("/etc/default/grub"));
		while (list ($num, $line) = each ($grb) ){
			if(preg_match("#^GRUB_CMDLINE_LINUX_DEFAULT#",$line)){
				if(strpos($line, "noop")==0){
					build_progress("GRUB_CMDLINE_LINUX_DEFAULT",25);
					echo "Starting......: ".date("H:i:s")." Optimize, Grub N1\n";
					$grb[$num]="GRUB_CMDLINE_LINUX_DEFAULT=\"quiet\"";
					$update=true;
				}
				continue;
			}
			if(preg_match("#^GRUB_CMDLINE_LINUX#",$line)){
				if(strpos($line, "noop")==0){
					build_progress("GRUB_CMDLINE_LINUX",25);
					echo "Starting......: ".date("H:i:s")." Optimize, Grub N2\n";
					$grb[$num]="GRUB_CMDLINE_LINUX=\"\"";
					$update=true;
				}
				continue;
			}

			if(preg_match("#^GRUB_DISABLE_OS_PROBER#",$line)){
				$GRUB_DISABLE_OS_PROBER=true;
				if(strpos($line, "true")==0){
					$grb[$num]="GRUB_DISABLE_OS_PROBER=true";
					$update=true;
				}
				continue;
			}

			if(preg_match("#^GRUB_GFXMODE#",$line)){
				$GRUB_GFXMODE=true;
				if(strpos($line, "800")==0){
					$grb[$num]="GRUB_GFXMODE=800x600,640x480";
					$update=true;
				}
				continue;
			}

		}


		if(!$GRUB_DISABLE_OS_PROBER){
			$grb[]="GRUB_DISABLE_OS_PROBER=true\n";
			$update=true;
		}
		if(!$GRUB_GFXMODE){
			$grb[]="GRUB_GFXMODE=800x600\n";
			$update=true;
		}

		if($GLOBALS["UPDATE_GRUB"]){$update=true;}

		if($update){
			build_progress("Update GRUB....",30);
			echo "Starting......: ".date("H:i:s")." Optimize, Grub N2\n";
			@file_put_contents("/etc/default/grub", @implode("\n", $grb));
			system("/usr/sbin/update-grub");

		}

	}

}

?>
