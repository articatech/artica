<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.status.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.artica.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/class.monit.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
$GLOBALS["FORCE"]=false;
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if($argv[1]=="--start-process"){startprocess($argv[2],$argv[3]);exit;}
if($argv[1]=="--monit"){monit();die(0);}
if($argv[1]=="--squid-mem"){squid_memory_monitor();die();}

if(!$GLOBALS["FORCE"]){
	if(systemMaxOverloaded()){error_log(basename(__FILE__)."::Fatal: Aborting report, this system is too many overloaded...");die();}
}

$unix=new unix();
$GLOBALS["CLASS_UNIX"]=$unix;
$pidfile="/etc/artica-postfix/".basename(__FILE__)."pid";
$currentpid=trim(@file_get_contents($pidfile));
if($unix->process_exists($currentpid)){die();}
@file_put_contents($pidfile,getmypid());


if($argv[1]=="--loadavg"){loadavg();die();}
if($argv[1]=="--mem"){loadmem();die();}
if($argv[1]=="--cpu"){loadcpu();die();}
if($argv[1]=="--queues"){ParseLoadQeues();die();}
if($argv[1]=="--loadavg-notif"){loadavg_notif();die();}





checkProcess1();

function startprocess($APP_NAME,$cmd){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__)."/pids/".__FUNCTION__.".".$APP_NAME.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){writelogs("Already process $pid exists",__FUNCTION__,__FILE__,__LINE__);return;}
	@file_put_contents($pidfile, getmypid());
	
	writelogs("RUNNING: $cmd",__FUNCTION__,__FILE__,__LINE__);
	
	exec("/etc/init.d/artica-postfix start $cmd 2>&1",$results);
	if($GLOBALS["VERBOSE"]){echo "\n".@implode("\n",$results)."\n";return;}
	//$unix->send_email_events("$APP_NAME stopped","Artica tried to start it:\n".@implode("\n",$results),"system");

}


function events($text,$function=null,$line=0){
		$filename=basename(__FILE__);
		if(!isset($GLOBALS["CLASS_UNIX"])){
			include_once(dirname(__FILE__)."/framework/class.unix.inc");
			$GLOBALS["CLASS_UNIX"]=new unix();
		}
		$GLOBALS["CLASS_UNIX"]->events("$filename $function:: $text (L.$line)","/usr/share/artica-postfix/ressources/logs/launch.watchdog.task");	
		}	
function checkProcess1(){
	
	$unix=new unix();
	$pid=$unix->PIDOF_PATTERN("bin/process1");
	if($pid<5){return null;}
	$process1=$unix->PROCCESS_TIME_MIN($pid);
	$mem=$unix->PROCESS_MEMORY($pid);
	Myevents("process1: $pid ($process1 mn) memory:$mem Mb",__FUNCTION__);
	
	if($mem>30){
		@copy("/var/log/artica-postfix/process1.debug","/var/log/artica-postfix/process1.killed".time().".debug");
		system("/bin/kill -9 $pid");
		$unix->send_email_events(
		"artica process1 (process1) Killed",
		"Process1 use too much memory $mem MB","watchdog"); 		
	}
	
	if($process1>2){
		@copy("/var/log/artica-postfix/process1.debug","/var/log/artica-postfix/process1.killed".time().".debug");
		system("/bin/kill -9 $pid");
		$unix->send_email_events(
		"artica process1 (process1) Killed",
		"Process1 run since $process1 Pid: $pid and exceed 2 minutes live","watchdog"); 
	}

}

function Myevents($text=null,$function=null){
			$pid=getmypid();
			$file="/var/log/artica-postfix/watchdog.debug";
			@mkdir(dirname($file));
		    $logFile=$file;
		 
   		if (is_file($logFile)) { 
   			$size=filesize($logFile);
		    	if($size>100000){unlink($logFile);}
   		}
		$date=date('Y-m-d H:i:s'). " [$pid]: ";
		$f = @fopen($logFile, 'a');
		@fwrite($f, "$date $function:: $text\n");
		@fclose($f);
}


function ParseLoadQeues(){
	$unix=new unix();
	if(system_is_overloaded(basename(__FILE__))){return;}
	$du=$unix->find_program("du");
	$rm=$unix->find_program("rm");
	$EXEC_NICE=EXEC_NICE();
	exec("$EXEC_NICE$du -b -s /etc/artica-postfix/loadavg.queue 2>&1",$results);
	$tmp=trim(@implode("", $results));
	if(preg_match("#[0-9]+\s+#", $tmp,$re)){
		$size=$re[1]/1024;
		$size=$size/1000;
		if($size>100){
			shell_exec("/bin/rm -rf /etc/artica-postfix/loadavg.queue/*");
			return;
		}
	}
	
	if(!is_dir('/etc/artica-postfix/loadavg.queue')){@mkdir("/etc/artica-postfix/loadavg.queue",true);}
	if ($handle = opendir("/etc/artica-postfix/loadavg.queue")) {
		while (false !== ($file = readdir($handle))) {
			if ($file == "." && $file == "..") {continue;}
			$filename="/etc/artica-postfix/loadavg.queue/$file";
			$filebase=basename($filename);
			if($GLOBALS["VERBOSE"]){echo "parse $filename\n";}
			sleep(1);
			if(preg_match("#^([0-9]+)\.([0-9]+)\.queue$#",$filebase,$re)){$filebase="{$re[1]}.{$re[2]}.0.queue";}
			
		
			if(preg_match("#([0-9]+)\.([0-9]+)\.([0-9]+)\.queue$#",$filebase,$re)){
				if(system_is_overloaded()){$unix->events(basename(__FILE__).": ParseLoadQeues() system is overloaded aborting for $filename");return;}
				
				$datas=loadavg_table($filename,$lsof);
				if(is_file("$filename.lsof")){$lsofTEXT=ParseLsof("$filename.lsof");@unlink("$filename.lsof");}else{if($GLOBALS["VERBOSE"]){echo "$filename.lsof no such file\n";}}
				if(is_file("$filename.iotop")){$IoText=ParseIotOp("$filename.lsof");@unlink("$filename.iotop");}else{if($GLOBALS["VERBOSE"]){echo "$filename.iotop no such file\n";}}
				$time=date("Y-m-d H:i:s",$re[1]);
				$load="{$re[2]},{$re[3]}";
				$q=new mysql();
				$datas=mysql_escape_string2($datas);
				$lsofTEXT=mysql_escape_string2($lsofTEXT);
				$IoText=mysql_escape_string2($IoText);
				$sql="INSERT IGNORE INTO avgreports (`zDate`,`loadavg`,`psreport`,`lsofreport`,`iotopreport`) VALUES ('$time','$load','$datas','$lsofTEXT','$IoText')";
				$q->QUERY_SQL($sql,"artica_events");
				if($GLOBALS["VERBOSE"]){echo "$time: $load\n";}
				
				$unix->send_email_events("System Load - $load - exceed rule (processes)",$datas,"system",$time);
				if(strlen($lsofTEXT)>50){$unix->send_email_events("System Load - $load - exceed rule (opened files)",$lsofTEXT,"system",$time);}
				if(strlen($IoText)>50){$unix->send_email_events("System Load - $load - exceed rule (Disk perfs)",$IoText,"system",$time);}			
				@unlink($filename);
			}else{
				echo "$filebase did not match ([0-9]+)\.([0-9]+)\.([0-9]+)\.queue\n";
				@unlink($filename);
			}

	}
	
	}
	
	
}

function loadavg_notif(){
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];		
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__)."/pids/".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__)."/pids/".__FUNCTION__.".time";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){writelogs("Already process $pid exists",__FUNCTION__,__FILE__,__LINE__);return;}	
	@file_put_contents($pidfile, getmypid());

	$time=$unix->file_time_min($pidTime);
	if($time<5){writelogs("Max 1 report each 5 minutes (current {$time}Mn)",__FUNCTION__,__FILE__,__LINE__);return;}
	@file_put_contents($pidTime, time());
	
	$ps=$unix->find_program("ps");
	$tail=$unix->find_program("tail");
	$lsof=$unix->find_program("lsof");
	$wc=$unix->find_program("wc");
	$awk=$unix->find_program("awk");
	$grep=$unix->find_program("grep");
	$iostat=$unix->find_program("iostat");
	exec("$lsof|$wc -l 2>&1",$locfa);
	$lsof_text="Number of opened files: ".@implode("", $locfa);
	
	exec("$ps -elf | $awk '{print $2}' | $grep ^Z | $wc -l 2>&1",$locfa2);
	$zombies_text="Number of zombies processes: ".@implode("", $locfa2);
	
	if(is_file($iostat)){
		exec("$iostat -tmdx 2>&1",$iostata);
		$iostata[]="\nCpu:\n------------------------------\n"; 
		exec("$iostat -tmcx 2>&1",$iostata);
		$iostat_text="\n\nIostat report:\n--------------------------\n".@implode("\n", $iostata);
	}
	
	
	exec("$ps aux --sort %cpu|$tail -n 20 2>&1",$psaux);
	krsort($psaux);
	
	$mysql=new mysql();
	$mysqladmin=$unix->find_program("mysqladmin");
	if(is_file($mysqladmin)){
		if(($mysql->mysql_server=="localhost") OR ($mysql->mysql_server=="127.0.0.1")){
			$serv=" --socket=/var/run/mysqld/mysqld.sock";
			$servtext="Local";
		}else{
			$serv=" --host=$mysql->mysql_server --port=$mysql->mysql_port";
			$servtext="$mysql->mysql_server:$mysql->mysql_port";
		}
		
		if($mysql->mysql_password<>null){
			$password=" --password=".$unix->shellEscapeChars($mysql->mysql_password);
		}
		
		exec("$mysqladmin$serv --user=$mysql->mysql_admin$password processlist 2>&1",$mysqladmin_results);
		$mysqladmin_text="Mysql ($servtext) processes report:\n---------------------------\n".@implode("\n", $mysqladmin_results);
		
	}
	
	
	$text[]="This is a report that provide system informations about a suspicous system load ($internal_load)";
	$text[]=$lsof_text;
	$text[]="Processes that consume CPU:";
	$text[]="---------------------------";
	$text[]=@implode("\n", $psaux);
	$text[]=$iostat_text;
	$text[]=$mysqladmin_text;
	
	$textfinal=@implode("\n", $text);
	$subject="System notification: Load exceed rule: [$internal_load]";
	if($GLOBALS["VERBOSE"]){
		echo "$subject\n$textfinal\n";
		return;
	}
		
	$unix->send_email_events($subject , $textfinal, "system");
	
	
	
	
}




function ParseLsof($filename){
	$results=@explode("\n",@file_get_contents($filename));
	while (list ($num, $ligne) = each ($results) ){
		usleep(1000);
		if(preg_match("#^(.+?)\s+[0-9]+#",$ligne,$re)){
			if(!isset($array[$re[1]])){$array[$re[1]]=0;}
			$array[$re[1]]=$array[$re[1]]+1;
		}
	
	}
$htm[]="<html><head></head><body>";
$htm[]="<table style='width:100%'>";
	$htm[]="<tr>";
	$htm[]="<th>Process</th>";
	$htm[]="<th>Files NB</th>";
	$htm[]="</tr>";

while (list ($prc, $count) = each ($array) ){
	$htm[]="<tr>";
	$htm[]="<td><strong>$prc</strong></td>";
	$htm[]="<td><strong>$count</strong></td>";
	$htm[]="</tr>";
}	
	$htm[]="</table></body></html>";
	if($GLOBALS["VERBOSE"]){echo "$filename ". count($htm)." rows\n";}
	return @implode("\n",$htm);
}
function ParseIotOp($filename){
	
	$htm[]="<html><head></head><body>";
	$htm[]="<table style='width:100%'>";
	$htm[]="<tbody><tr>";
	$htm[]="<th>TID</th>";
	$htm[]="<th>PRIO</th>";
	$htm[]="<th>USER</th>";
	$htm[]="<th>READ</th>";
	$htm[]="<th>WRITE</th>";
	$htm[]="<th>SWAPIN</th>";
	$htm[]="<th>IO</th>";
	$htm[]="<th>COMMAND</th>";
	$htm[]="</tr>";	
	
   
	$results=@explode("\n",@file_get_contents($filename));
	while (list ($num, $ligne) = each ($results) ){
		usleep(1000);
		if(preg_match("#^([0-9]+)\s+(.+?)\s+(.+?)\s+([0-9\.]+)\s+(.+?)\s+([0-9\.]+)\s+(.+?)\s+([0-9\.]+)\s+(.+?)\s+([0-9\.]+)\s+(.+?)\s+(.+)#",$ligne,$re)){
			$htm[]="<tr>";
			$htm[]="<td><strong>{$re[1]}</strong></td>";
			$htm[]="<td><strong>{$re[2]}</strong></td>";
			$htm[]="<td><strong>{$re[3]}</strong></td>";
			$htm[]="<td><strong>{$re[4]}&nbsp;{$re[5]}</strong></td>";
			$htm[]="<td><strong>{$re[6]}&nbsp;{$re[7]}</strong></td>";
			$htm[]="<td><strong>{$re[8]}&nbsp;{$re[9]}</strong></td>";
			$htm[]="<td><strong>{$re[10]}&nbsp;{$re[11]}</strong></td>";
			$htm[]="<td><strong>{$re[12]}</strong></td>";
			$htm[]="</tr>";
			
		}	
	}
		$htm[]="</tbody></table></body></html>";
		if($GLOBALS["VERBOSE"]){echo "$filename ". count($htm)." rows\n";}
		return @implode("\n",$htm);
}



function loadcpu(){
	
	$timefile="/etc/artica-postfix/croned.1/".basename(__FILE__).__FUNCTION__;
	if(file_time_min($timefile)<15){die();}
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__)."/pids/".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){writelogs("Already process $pid exists",__FUNCTION__,__FILE__,__LINE__);return;}		
	@file_put_contents($pidfile, getmypid());
	
	$timefile="/etc/artica-postfix/croned.1/".basename(__FILE__).__FUNCTION__;
	if(file_time_min($timefile)<15){return null;}
	@unlink($timefile);
	@file_put_contents($timefile,time());	
	$datas=loadavg_table();
	if($GLOBALS["VERBOSE"]){echo strlen($datas)." bytes body text\n";}
	$unix->send_email_events("System CPU exceed rule",$datas,"system");
	checkProcess1();
}
function loadmem(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__)."/pids/".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){writelogs("Already process $pid exists",__FUNCTION__,__FILE__,__LINE__);return;}	
	@file_put_contents($pidfile, getmypid());
		
	include_once("ressources/class.os.system.tools.inc");
	$unix=new unix();
	$timefile="/etc/artica-postfix/croned.1/".basename(__FILE__).__FUNCTION__;
	if(file_time_min($timefile)<15){return null;}
	@unlink($timefile);
	@file_put_contents($timefile,time());	
	$sys=new os_system();
	$mem=$sys->realMemory();
	
	$pourc=$mem["ram"]["percent"];
	$ram_used=$mem["ram"]["used"];
	$ram_total=$mem["ram"]["total"];	
	
	$datas=loadavg_table();
	if($GLOBALS["VERBOSE"]){echo strlen($datas)." bytes body text\n";}	
	$unix->send_email_events("System Memory $pourc% used exceed rule",$datas,"system");
	checkProcess1();
}





function loadavg(){
	if(is_file("/etc/artica-postfix/loadavg.lock")){die();}
	@file_put_contents("/etc/artica-postfix/loadavg.lock", time());
	
	$sock=new sockets();
	$DisableLoadAVGQueue=$sock->GET_INFO("DisableLoadAVGQueue");
	if(!is_numeric($DisableLoadAVGQueue)){$DisableLoadAVGQueue=0;}
	if($DisableLoadAVGQueue==1){return;}
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__)."/pids/".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		writelogs("Already process $pid exists",__FUNCTION__,__FILE__,__LINE__);
		@unlink("/etc/artica-postfix/loadavg.lock");
		return;
	}	
	@file_put_contents($pidfile, getmypid());
	
	@mkdir("/etc/artica-postfix/croned.1",0666,true);
	$pidfile="/etc/artica-postfix/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=trim(@file_get_contents($pidfile));
	if($unix->process_exists($pid)){die();}
	$array_load=sys_getloadavg();
	$pid=getmypid();
	@file_put_contents($pidfile,$pid);
	$timefile="/etc/artica-postfix/croned.1/".basename(__FILE__).__FUNCTION__;
	$timeMin=file_time_min($timefile);
	if(!$GLOBALS["FORCE"]){
		if($timeMin<5){
			writelogs("{$timeMin}Mn, aborting",__FUNCTION__,__FILE__,__LINE__);
			@unlink("/etc/artica-postfix/loadavg.lock");
			return null;
		}
	}
	@unlink($timefile);
	@file_put_contents($timefile,time());	
	
	
	
	$ps=$unix->find_program("ps");	
	$lsof=$unix->find_program("lsof");
	$iotop=$unix->find_program("iotop");		
	mkdir("/etc/artica-postfix/loadavg.queue",0666,true);
	$internal_load=$array_load[0];
	$time=time();			
	shell_exec("$ps -aux >/etc/artica-postfix/loadavg.queue/$time.$internal_load.queue 2>&1");
	shell_exec("$lsof -r 0 >/etc/artica-postfix/loadavg.queue/$time.$internal_load.queue.lsof 2>&1");
	if(strlen($iotop)>3){shell_exec("iotop -o -b -n 1 >/etc/artica-postfix/loadavg.queue/$time.$internal_load.queue.iotop 2>&1");}
	@unlink("/etc/artica-postfix/loadavg.lock");
}

function loadavg_old(){
	
	$unix=new unix();
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__)."/pids/".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){writelogs("Already process $pid exists",__FUNCTION__,__FILE__,__LINE__);return;}	

	
	@mkdir("/etc/artica-postfix/croned.1",0666,true);
	$unix=new unix();
	$pidfile="/etc/artica-postfix/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=trim(@file_get_contents($pidfile));
	if($unix->process_exists($pid)){die();}

	$pid=getmypid();
	@file_put_contents($pidfile,$pid);
	
	$timefile="/etc/artica-postfix/croned.1/".basename(__FILE__).__FUNCTION__;
	if(file_time_min($timefile)<15){return null;}
	@unlink($timefile);
	@file_put_contents($timefile,time());
	
	
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];		
	$datas=loadavg_table();
	if($GLOBALS["VERBOSE"]){echo strlen($datas)." bytes body text\n";}	
	$unix->send_email_events("System Load - $internal_load - exceed rule ",$datas,"system");
	checkProcess1();
}

function loadavg_table($filepath=null,$lsof=null){
	$array=array();
	if($filepath==null){
		$unix=new unix();
		$ps=$unix->find_program("ps");
		exec("$ps -aux",$results);
	}else{
		$results=explode("\n",@file_get_contents($filepath));
	}
	while (list ($index, $line) = each ($results) ){
	usleep(2000);
	if(!preg_match("#(.+?)\s+([0-9]+)\s+([0-9\.]+)\s+([0-9\.]+)\s+([0-9]+)\s+([0-9\.]+)\s+.+?\s+.+?\s+([0-9\:]+)\s+([0-9\:]+)\s+(.+?)$#",$line,$re)){
			if(preg_match("#(.+?)\s+([0-9]+)\s+([0-9\.]+)\s+([0-9\.]+)\s+([0-9]+)\s+([0-9]+)\s+.+?\s+.+?\s+([a-zA-Z0-9]+)\s+([0-9\:]+)\s+(.+?)$#",$line,$re)){
			$user=$re[1];
			$pid=$re[2];
			$pourcCPU=$re[3];
			$purcMEM=$re[4];
			$VSZ=$re[5];
			$RSS=$re[6];
			$START=$re[7];
			$TIME=$re[8];
			$cmd=$re[9];	
			$key="$pourcCPU$purcMEM";
			$key=str_replace(".",'',$key);
			
	$array[$key][]=array(
			"PID"=>$pid,
			"CPU"=>$pourcCPU,
			"MEM"=>$purcMEM,
			"START"=>$START,
			"TIME"=>$TIME,
			"CMD"=>$cmd
		);			
			
			continue;		
			
		}		
		
		
		continue;}	
	$user=$re[1];
	$pid=$re[2];
	$pourcCPU=$re[3];
	$purcMEM=$re[4];
	$VSZ=$re[5];
	$RSS=$re[6];
	$START=$re[7];
	$TIME=$re[8];
	$cmd=$re[9];
	
	$pourcCPU=str_replace("0.0","0",$pourcCPU);
	$purcMEM=str_replace("0.0","0",$purcMEM);
	
	$key="$pourcCPU$purcMEM";
	$key=str_replace(".",'',$key);
	
	$array[$key][]=array(
			"PID"=>$pid,
			"CPU"=>$pourcCPU,
			"MEM"=>$purcMEM,
			"START"=>$START,
			"TIME"=>$TIME,
			"CMD"=>$cmd
		);
	
	
	
	
		
	}
	
	if(count($array)<5){return @file_get_contents($filepath);}
	
	krsort($array);
	$htm[]="<html><head></head><body>";
	$htm[]="<table style='width:100%'>";
	$htm[]="<tr>";
	$htm[]="<th>PID</th>";
	$htm[]="<th>CPU</th>";
	$htm[]="<th>MEM</th>";
	$htm[]="<th>START</th>";
	$htm[]="<th>TIME</th>";
	$htm[]="<th>CMD</th>";
	$htm[]="</tr>";
	while (list ($index, $line) = each ($array) ){
		usleep(200000);
		while (list ($a, $barray) = each ($line) ){
			$htm[]="<tr>";
			$htm[]="<td style='font-size:10px;font-weight:bold'>{$barray["PID"]}</td>";
			$htm[]="<td style='font-size:10px;font-weight:bold'>{$barray["CPU"]}%</td>";
			$htm[]="<td style='font-size:10px;font-weight:bold'>{$barray["MEM"]}%</td>";
			$htm[]="<td style='font-size:10px;font-weight:bold'>{$barray["START"]}</td>";
			$htm[]="<td style='font-size:10px;font-weight:bold'>{$barray["TIME"]}</td>";
			$htm[]="<td style='font-size:10px;font-weight:bold'><code>{$barray["CMD"]}</code></td>";
			$htm[]="</tr>";
		}
	}
	
	$htm[]="</table></body></html>";
	return implode("",$htm);
}

function monit(){
	$monit=new monit_unix();
	$monit->WAKEUP();
	$unix=new unix();
	$unix->chmod_func(0755, "/etc/artica-postfix/settings/Daemons/*");
	
	
	
}




?>