<?php

$GLOBALS["VERBOSE"]=false;
if(is_array($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;echo "Starting verbose mode\n";}}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
$GLOBALS["FORCE"]=false;$GLOBALS["REINSTALL"]=false;

$GLOBALS["TITLENAME"]="Bind Linker";
$GLOBALS["OUTPUT"]=true;

start();


function start(){
	
	
	if(!is_file("/etc/artica-postfix/MOUNT_BINDS/BINDS.db")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} No mount\n";}
		return;
		
	}
	
	$DATAS=unserialize(@file_get_contents("/etc/artica-postfix/MOUNT_BINDS/BINDS.db"));
	
	if(!is_array($DATAS)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} No mount\n";}
		return;
	}
	
	if(count($DATAS)==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} No mount\n";}
		return;
	}
	
	while (list ($SourceDirectory, $MAINAR) = each ($DATAS)){
		$MountPoint=basename($SourceDirectory);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} About $SourceDirectory\n";}
		while (list ($destdirectory, $uid) = each ($MAINAR)){
			if(is_link($destdirectory)){$destdirectory=@readlink($destdirectory);}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} About $destdirectory ($uid)\n";}
			if(BindisMounted("$destdirectory/$MountPoint")){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} \"$destdirectory/$MountPoint\" Already mounted\n";}
				continue;
			}
			
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} not \"$destdirectory/$MountPoint\" Mounted\n";}
			MountBind($SourceDirectory,"$destdirectory/$MountPoint");
			if(!BindisMounted("$destdirectory/$MountPoint")){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} not \"$destdirectory/$MountPoint\" Failed to mount\n";}
			}
		}
		
	}
	
	
}

function MountBind($source,$dest){
	@mkdir($dest,0755,true);
	system("/bin/mount --bind $source $dest");
	unset($GLOBALS["F"]);
}


function BindisMounted($directory){
	
		$directory=str_replace(".", "\.", $directory);
		$directory=str_replace("/", "\/", $directory);
		$directory=str_replace("(", "\(", $directory);
		$directory=str_replace(")", "\)", $directory);
		$directory=str_replace("[", "\[", $directory);
		$directory=str_replace("]", "\]", $directory);
		$directory=str_replace("+", "\+", $directory);
		$directory=str_replace("{", "\{", $directory);
		$directory=str_replace("}", "\}", $directory);
		$directory=str_replace("=", "\=", $directory);
		$directory=str_replace("!", "\!", $directory);
		$directory=str_replace("?", "\?", $directory);
		$directory=str_replace("|", "\|", $directory);
		$directory=str_replace("$", "\$", $directory);
		$directory=str_replace("^", "\^", $directory);
		if(!isset($GLOBALS["F"])){
			$GLOBALS["F"]=explode("\n", @file_get_contents("/proc/mounts"));
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} mount: ".count($GLOBALS["F"]). " elements\n";}
		}
		reset($GLOBALS["F"]);
		$pattern="#^(.+?)\s+$directory\s+(tmpfs|sysfs|devpts|ext4|ext3|ext2|rpc_pipefs|btrfs|xfs)#";
		while (list ($num, $line) = each ($GLOBALS["F"])){
			$line=trim($line);
			if($line==null){continue;}
			if(!preg_match($pattern, $line)){continue;}
			return true;
		}
	
}