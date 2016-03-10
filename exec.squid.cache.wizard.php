<?php
$GLOBALS["SCHEDULE_ID"]=0;
$GLOBALS["AD_PROGRESS"]=0;
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["ARGVS"]=implode(" ",$argv);
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if(preg_match("#--progress-activedirectory=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["AD_PROGRESS"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.mount.inc');


xstart();

function build_progress($text,$pourc){
	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/squid.newcache.center.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}

function checkIntegrated(){
	
	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	while (list ($index, $line) = each ($f) ){
		
		if(preg_match("#ssl\.conf#", $line)){return true;}
	
	}
	
	return false;
}





function xstart(){
	
	$unix=new unix();
	
	$sock=new sockets();
	build_progress("{starting} {creating_new_cache}",15);
	$Config=unserialize($sock->GET_INFO("NewCacheCenterWizard"));
	
	if(isset($Config["SaveHD"])){
		CreateHD();
		return;
	}
	
	if(isset($Config["SaveDir"])){
		CreateDir();
		return;
	}
	
}

function CreateDir(){
	$sock=new sockets();
	$unix=new unix();
	$Config=unserialize($sock->GET_INFO("NewCacheCenterWizard"));
	$DEFINED_SIZE=$Config["size"];
	$folder=$Config["folder"];
	$CPU=$Config["CPU"];
	
	if(substr($folder, 0,1)<>'/'){$folder="/$folder";}
	$folder=str_replace("//", "/", $folder);
	
	if(!is_numeric($CPU)){$CPU=1;}
	$oct_small=round($DEFINED_SIZE*0.3);
	$oct_big=round($DEFINED_SIZE*0.7);
	

	build_progress("{checking} $folder {$DEFINED_SIZE}MB",20);
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress("{building_caches} $folder {$DEFINED_SIZE}MB",20);
	
	@mkdir("$folder/proxy-caches",0755,true);
	if(!is_dir("$folder/proxy-caches")){
		build_progress("{checking} $folder {failed}",110);
		return;
	}
	
	@chown("$folder/proxy-caches", "squid");
	@chgrp("$folder/proxy-caches", "squid");
	
	echo "BIG: $DEFINED_SIZE*0.7; = $oct_big\n";

	
	if($oct_big==0){
		build_progress("{failed} Erro => 0 for big cache",110);
		return;
	}
	
	echo "Cache1 - Small {$oct_small}MB\n";
	echo "Cache2 - Big {$oct_big}MB\n";
	$folderName=basename($folder);
	$Cache_small_name="$folderName - small - CPU#$CPU";
	$Cache_small_path="$folder/proxy-caches/small-cpu$CPU";	
	
	
	$Cache_big_name="$folderName - big - CPU#$CPU";
	$Cache_big_path="$folder/proxy-caches/big-cpu$CPU";
	
	@chown($Cache_small_path, "squid");
	@chgrp($Cache_small_path, "squid");
	@chown($Cache_big_path, "squid");
	@chgrp($Cache_big_path, "squid");
	
	$q=new mysql();
	
	if(!$q->FIELD_EXISTS("squid_caches_center", "wizard", "artica_backup")){
		$q->QUERY_SQL($sql="ALTER TABLE `squid_caches_center` ADD `wizard` smallint(1) NOT NULL,ADD INDEX (wizard)","artica_backup");
		
	}
	
	
	$q->QUERY_SQL("INSERT IGNORE INTO squid_caches_center
			(cachename,cpu,cache_dir,cache_type,cache_size,cache_dir_level1,cache_dir_level2,enabled,percentcache,usedcache,zOrder,min_size,max_size,wizard)
			VALUES('$Cache_small_name',$CPU,'$Cache_small_path','aufs','$oct_small','16','256',1,0,0,1,0,512,1)","artica_backup");
	
	if(!$q->ok){
		echo "$q->mysql_error\n\n";
		build_progress("{failed} MySQL error",110);
		return;
	}
	
	$q->QUERY_SQL("INSERT IGNORE INTO squid_caches_center
	(cachename,cpu,cache_dir,cache_type,cache_size,cache_dir_level1,cache_dir_level2,enabled,percentcache,usedcache,zOrder,min_size,max_size,wizard)
	VALUES('$Cache_big_name',$CPU,'$Cache_big_path','aufs','$oct_big','16','256',1,0,0,1,512,3072000,1)","artica_backup");

	if(!$q->ok){
		echo "$q->mysql_error\n\n";
		build_progress("{failed} MySQL error",110);
		return;
	}
	build_progress("{building_caches}",90);
	system("$php /usr/share/artica-postfix/exec.squid.verify.caches.php --bywizard");
	system("$php /usr/share/artica-postfix/exec.squid.interface-size.php --force");
	
	build_progress("{building_caches} {success}",100);
	sleep(3);
	build_progress("{building_caches} {success}",100);
	
	
}


function CreateHD(){
	$sock=new sockets();
	$unix=new unix();
	$Config=unserialize($sock->GET_INFO("NewCacheCenterWizard"));
	
	if(!is_array($Config)){
		echo "Corrupted configuration...\n";
		build_progress("{failed}",110);
		return;
	}
	
	if(!isset($Config["dev"])){
		echo "Corrupted configuration...\n";
		build_progress("{failed}",110);
		return;
	}
	
	$dev=$Config["dev"];
	
	if($dev==null){
		echo "Corrupted configuration...\n";
		build_progress("{failed}",110);
		return;
	}
	
	
	$size=$Config["size"];
	$oct=intval($Config["oct"]);
	
	
	
	build_progress("{checking} $dev $size",20);
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress("{building_partition} $dev $size",20);
	$explodedDev=basename($dev);
	$Label="Cache{$explodedDev}";
	$mount=$unix->find_program("mount");
	$cpu=$Config["CPU"];
	$mount=new mount();
	
	$targetMountPoint=$unix->isDirInFsTab("/media/$Label");
	echo "Target in fstab = $targetMountPoint\n";
	if($targetMountPoint==null){
		system("$php /usr/share/artica-postfix/exec.system.build-partition.php --full \"$dev\" \"$Label\" \"ext4\" --bywizard");
	}else{
		build_progress("{building_partition} $targetMountPoint",20);
	}
	
	if(!$mount->ismounted("/media/$Label")){
		echo "/media/$Label is not mounted !!! (1/2)...\n";
		shell_exec("$mount /media/$Label");
		if(!$mount->ismounted("/media/$Label")){
			echo "/media/$Label is not mounted !!! (2/2)...\n";
			build_progress("{failed}",110);
			return;
		}
	}
	
	echo "Testing /media/$Label/proxy-caches\n";
	if(!is_dir("/media/$Label/proxy-caches")){
		@mkdir("/media/$Label/proxy-caches",0755,true);
	}
	
	if(!is_dir("/media/$Label/proxy-caches")){
		echo "/media/$Label/proxy-caches permissions denied\n";
		build_progress("{failed}",110);
		return;
	}
	
	@chown("/media/$Label/proxy-caches", "squid");
	@chgrp("/media/$Label/proxy-caches", "squid");
	
	
	
	$q=new mysql();
	if(!$q->FIELD_EXISTS("squid_caches_center","wizard","artica_backup")){
		$sql="ALTER TABLE `squid_caches_center` ADD `wizard` smallint NOT NULL DEFAULT 0";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){
			echo "$q->mysql_error\n$sql\n";
			build_progress("{failed} MySQL error",110);
			return;
		}
		
	}
	build_progress("{configuring_caches}",90);
	echo "Configuring caches\n";
	
	$oct_free=$oct*0.8;
	
	$oct_small=$oct_free*0.3;
	$oct_big=$oct_free*0.7;
	
	echo "BIG: $oct_free*0.7; = $oct_big\n";
	
	$oct_small=$oct_small/1000;
	$oct_small=round($oct_small/1000);
	
	$oct_big=$oct_big/1000;
	$oct_big=round($oct_big/1000);
	
	if($oct_big==0){
		echo "$q->mysql_error\n$sql\n";
		build_progress("{failed} Erro => 0 for big cache",110);
		return;
	}
	
	echo "Cache1 - Small {$oct_small}MB\n";
	echo "Cache2 - Big {$oct_big}MB\n";
	
	$Cache_small_name="$explodedDev - small - CPU#$cpu";
	$Cache_small_path="/media/$Label/proxy-caches/small-cpu$cpu";
	
	$Cache_big_name="$explodedDev - big - CPU#$cpu";
	$Cache_big_path="/media/$Label/proxy-caches/big-cpu$cpu";	
	
	@chown($Cache_small_path, "squid");
	@chgrp($Cache_small_path, "squid");
	@chown($Cache_big_path, "squid");
	@chgrp($Cache_big_path, "squid");
	
	$q->QUERY_SQL("INSERT IGNORE INTO squid_caches_center
	(cachename,cpu,cache_dir,cache_type,cache_size,cache_dir_level1,cache_dir_level2,enabled,percentcache,usedcache,zOrder,min_size,max_size,wizard)
	VALUES('$Cache_small_name',$cpu,'$Cache_small_path','aufs','$oct_small','16','256',1,0,0,1,0,512,1)","artica_backup");
	
	if(!$q->ok){
		echo "$q->mysql_error\n$sql\n";
		build_progress("{failed} MySQL error",110);
		return;
	}
	
	$q->QUERY_SQL("INSERT IGNORE INTO squid_caches_center
	(cachename,cpu,cache_dir,cache_type,cache_size,cache_dir_level1,cache_dir_level2,enabled,percentcache,usedcache,zOrder,min_size,max_size,wizard)
	VALUES('$Cache_big_name',$cpu,'$Cache_big_path','aufs','$oct_big','16','256',1,0,0,1,512,3072000,1)","artica_backup");
		
	if(!$q->ok){
		echo "$q->mysql_error\n$sql\n";
		build_progress("{failed} MySQL error",110);
		return;
	}
	build_progress("{building_caches}",90);
	system("$php /usr/share/artica-postfix/exec.squid.verify.caches.php --bywizard");
	build_progress("{building_caches} {success}",100);
	sleep(3);
	build_progress("{building_caches} {success}",100);
	
}


function Test_config(){
	$unix=new unix();
	$squidbin=$unix->find_program("squid");
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}

	exec("$squidbin -f /etc/squid3/squid.conf -k parse 2>&1",$results);
	while (list ($index, $ligne) = each ($results) ){
		if(strpos($ligne,"| WARNING:")>0){continue;}
		if(preg_match("#ERROR: Failed#", $ligne)){
			echo "`$ligne`, aborting configuration\n";
			return false;
		}
	
		if(preg_match("#Segmentation fault#", $ligne)){
			echo "`$ligne`, aborting configuration\n";
			return ;
		}
			
			
		if(preg_match("#(unrecognized|FATAL|Bungled)#", $ligne)){
			echo "`$ligne`, aborting configuration\n";
			
			if(preg_match("#line ([0-9]+):#", $ligne,$ri)){
				$Buggedline=$ri[1];
				$tt=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
				for($i=$Buggedline-2;$i<$Buggedline+2;$i++){
					$lineNumber=$i+1;
					if(trim($tt[$i])==null){continue;}
					echo "[line:$lineNumber]: {$tt[$i]}\n";
				}
			}

			return false;
		}
	
	}

	return true;
	
}


