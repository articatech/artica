<?php
$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
if($EnableIntelCeleron==1){die("EnableIntelCeleron==1\n");}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');

// Normal IDE : 82 seeks/second, 12.10 ms random access time
// SSD : 5351 seeks/second, 0.19 ms random access time 21404 KB/s / 20MB/s
// SSD :  VM Artica XEN 4074 seeks/second, 0.25 ms
xtart();

function events($text,$line=0){
	$unix=new unix();
	$unix->events($text,"/var/log/seeker.log",false,"MAIN",$line,basename(__FILE__));
	
}

function build_progress($text,$pourc){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/seeker.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);

}

function xtart(){
	if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }
	$unix=new unix();
	$binfile="/usr/share/artica-postfix/bin/seeker";
	if(!is_file($binfile)){
		build_progress("{failed}",110);
		events("Unable to stat $binfile");
		return;
	}
	

	$php=$unix->LOCATE_PHP5_BIN();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/exec.seeker.php.xtart.time";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		events("Already process executed pid $pid");
		return;
	}
	
	if(system_is_overloaded(basename(__FILE__))){
		events("Overloaded system, schedule it later",__LINE__);
		$unix->THREAD_COMMAND_SET("$php ".__FILE__);
		build_progress("{failed} Overloaded",110);
		return;
	}
	
	@file_put_contents($pidfile, getmypid());
	$timefile=$unix->file_time_min($pidTime);
	$DisksBenchs=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/DisksBenchs"));
	$DisksBenchs=$DisksBenchs*60;
	if(!$GLOBALS["FORCE"]){
		if($timefile<$DisksBenchs){
			events("{$timefile}mn, require at least {$DisksBenchs}mn",__LINE__);
			return;
		}
	}
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	
	build_progress("{scanning} {disks}",10);
	$fdisk=$unix->find_program("fdisk");
	exec("$fdisk -l 2>&1",$results);
	$DISKS=array();
	while (list ($index, $line) = each ($results) ){
		$line=trim($line);
		if($line==null){continue;}
		if(preg_match("#^(Disque|Disk)\s+\/([a-zA-Z0-9\-\_\/\.]+).*?:\s+[0-9]+.*?(bytes|octets)#", $line,$re)){
			$DISKS["/".$re[2]]=true;
		}
		
	}
	
	if(count($DISKS)==0){
		build_progress("{scanning} {disks} {failed}",110);
		events("Unable to detect disks");
		$unix->ToSyslog("Unable to detect disks");
		system_admin_events("Unable to detect disks\n".@implode("\n", $results),__FUNCTION__,__FILE__,__LINE__,"system");
		return;
	}
	
	
	$RUN=false;
	while (list ($disk, $line) = each ($DISKS) ){
		$results=array();
		@chmod("$binfile",0755);
		
		$cmd="$binfile \"".trim($disk)."\" 2>&1";
		build_progress("{scanning} $disk",60);
		events("$cmd");
		exec($cmd,$results);
		while (list ($index, $line) = each ($results) ){
			$line=trim($line);
			echo "***: $line\n";
			$md5=md5("$disk".time());
			if($line==null){continue;}
			if(!preg_match("#^Results:\s+([0-9]+)\s+seeks.*?,\s+([0-9\.]+)\s+ms#", $line,$re)){continue;}
			$seeks=$re[1];
			$ms=$re[2];
			events("$disk $seeks seeks, $ms ms",__LINE__);
			$array=array();
			@mkdir("{$GLOBALS["ARTICALOGDIR"]}/seeker-queue",0755,true);
			$array["SEEKS"]=$seeks;
			$array["DISK"]=$disk;
			$array["MS"]=$ms;
			$array["time"]=time();
			$unix->ToSyslog("Bench disk $disk $ms ms for $seeks seeks");
			events("{$GLOBALS["ARTICALOGDIR"]}/seeker-queue/$md5.ay",__LINE__);
			@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/seeker-queue/$md5.ay", serialize($array));
			$RUN=true;
			break;
		}
		
	}
	
	if($RUN){
		$php=$unix->LOCATE_PHP5_BIN();
		$nohup=$unix->find_program("nohup");
		build_progress("{analyze}",90);
		$cmd="$php ".dirname(__FILE__)."/exec.syslog-engine.php --seeker";
		events($cmd);
		system($cmd);
	}
	
	build_progress("{done}",100);
	
}






