<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--verbose#",@implode(" ", $argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.milter.greylist.inc');


include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/ressources/class.fetchmail.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");

$GLOBALS["deflog_start"]="Starting......: ".date("H:i:s")." [INIT]: Milter Greylist Daemon";
$GLOBALS["deflog_sstop"]="Stopping......: ".date("H:i:s")." [INIT]: Milter Greylist Daemon";
$GLOBALS["ROOT"]=true;
$GLOBALS["FORCE"]=false;
if(preg_match("#--verbose#",@implode(" ", $argv))){$GLOBALS["FORCE"]=true;}
$GLOBALS["WHOPROCESS"]="daemon";

$unix=new unix();
$_GLOBAL["miltergreylist_bin"]=$unix->find_program("milter-greylist");
if(!is_file($_GLOBAL["miltergreylist_bin"])){if($GLOBALS["VERBOSE"]){echo "Not installed !!\n";}die();}
$sock=new sockets();
$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
if(!is_numeric($EnablePostfixMultiInstance)){$EnablePostfixMultiInstance=0;}

if($EnablePostfixMultiInstance==0){
	if($argv[1]=="--start-single"){$GLOBALS["OUTPUT"]=true;SingleInstance_start();die();}
	if($argv[1]=="--stop-single"){$GLOBALS["OUTPUT"]=true;SingleInstance_stop();die();}
	if($argv[1]=="--restart-single"){$GLOBALS["OUTPUT"]=true;SingleInstance_restart();die();}
	if($argv[1]=="--reload-single"){$GLOBALS["OUTPUT"]=true;SingleInstance_reload();die();}
	if($argv[1]=="--AutoWhiteList"){SingleInstance_whitelist();die();}
}

parsecmdlines($argv);


if($argv[1]=="--startall"){if($EnablePostfixMultiInstance==1){$GLOBALS["START_ONLY"]==1;MultiplesInstancesFound(true,true);die();}}

if($EnablePostfixMultiInstance==1){
	
	if($GLOBALS["STATUS"]){	MultiplesInstances_status();die();}
	if($GLOBALS["START_ONLY"]==1){	MultiplesInstances_start($GLOBALS["hostname"],$GLOBALS["ou"]);die();}
	if($GLOBALS["STOP_ONLY"]==1){	MultiplesInstances_stop($GLOBALS["hostname"],$GLOBALS["ou"]);die();}
	
	if($argv[1]=="--database"){parse_multi_databases();die();}
	MultiplesInstances($GLOBALS["hostname"],$GLOBALS["ou"]);exit;
}


if($argv[1]=="--database"){parse_database("/var/milter-greylist/greylist.db","master");die();}



SingleInstance();

function parsecmdlines($argv){
	$GLOBALS["hostname"]=null;
	$GLOBALS["ou"]=null;
	$GLOBALS["STOP_ONLY"]=null;
	$GLOBALS["NORESTART"]=false;
	$GLOBALS["START_ONLY"]=0;
	$GLOBALS["STATUS"]=false;
	$GLOBALS["COMMANDLINE"]=implode(" ",$argv);
	if($GLOBALS["postconf"]==null){$unix=new unix();$GLOBALS["postconf"]=$unix->find_program("postconf");}
	if($GLOBALS["postmulti"]==null){$unix=new unix();$GLOBALS["postmulti"]=$unix->find_program("postmulti");}	
	//echo "{$GLOBALS["deflog_start"]} Milter-greylist multiple instance `". @implode(";", $argv)."\n";
	if(strpos($GLOBALS["COMMANDLINE"],"--verbose")>0){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;$GLOBALS["DEBUG"]=true;}
	if(preg_match("#--who=([A-Za-z]+)#", $GLOBALS["COMMANDLINE"],$re)){$GLOBALS["WHOPROCESS"]=$re[1];}
	
	while (list ($num, $ligne) = each ($argv) ){
		
		if(preg_match("#--verbose#",$ligne)){
			$GLOBALS["DEBUG"]=true;
			$GLOBALS["VERBOSE"]=true;
			ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
		}
		
	if(preg_match("#--norestart#",$ligne)){
			$GLOBALS["NORESTART"]=true;
		}		
	
		
		if(preg_match("#--hostname=(.+)#",$ligne,$re)){
			$GLOBALS["hostname"]=$re[1];
			continue;
		}
		
		if(preg_match("#--ou=(.+)#",$ligne,$re)){
				if(is_base64_encoded($re[1])){$re[1]=base64_decode($re[1]);}
				$GLOBALS["ou"]=$re[1];
				continue;
		}	

		if(preg_match("#--start#",$ligne)){
			$GLOBALS["START_ONLY"]=1;
			continue;
		}	

		if(preg_match("#--stop#",$ligne)){
			$GLOBALS["STOP_ONLY"]=1;
			continue;
		}	

		if(preg_match("#--status#",$ligne)){
			$GLOBALS["STATUS"]=true;
		}		
		
	}
	
if($GLOBALS["DEBUG"]){echo "parsecmdlines ou={$GLOBALS["ou"]} hostname={$GLOBALS["hostname"]} STOP={$GLOBALS["STOP_ONLY"]} START={$GLOBALS["START_ONLY"]}\n";}
}


function TestConfigFile($path){
	echo "{$GLOBALS["deflog_start"]} Testing $path\n";
	$unix=new unix();
	$bin=$unix->find_program("milter-greylist");
	copy($path, "$path.bak");
	exec("$bin -f $path -c 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#config error at line\s+([0-9]+)#", $ligne,$re)){
			$tt=file($path);
			$line=$re[1];
			echo "{$GLOBALS["deflog_start"]}  error line {$line}: `{$tt[$line]}`\n";
		}
		
		echo "{$GLOBALS["deflog_start"]} $ligne\n";
		
	}
	
	
	
}

function SingleInstance_restart(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__."pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){
		echo "{$GLOBALS["deflog_start"]} already Artica Starting process exists $pid\n";
		return;
	}
	@file_put_contents($pidfile,getmypid());
	
	SingleInstance_stop(true);
	SingleInstance();
	SingleInstance_start(true);
	
}



function SingleInstance_whitelist(){
	$sock=new sockets();
	$timefile="/etc/artica-postfix/pids/exec.milter-greylist.php.SingleInstance_whitelist.pid";
	$MimeDefangEnabled=intval($sock->GET_INFO('MimeDefangEnabled'));
	$MimeDefangAutoWhiteList=intval($sock->GET_INFO("MimeDefangAutoWhiteList"));
	$MilterGreyListEnabled=intval($sock->GET_INFO("MilterGreyListEnabled"));
	if($MimeDefangEnabled==0){$MimeDefangAutoWhiteList=0;}
	if($MimeDefangAutoWhiteList==0){return;}
	if($MilterGreyListEnabled==0){return;}
	
	$unix=new unix();
	$timeExec=$unix->file_time_min($timefile);
	if($timeExec<10){return;}
	@unlink($timefile);
	@file_put_contents($timefile, time());
	$q=new mysql();
	$LastCount=intval($sock->GET_INFO('MimeDefangAutoWhiteListGreyCount'));
	$NeWCount=$q->COUNT_ROWS("autowhite", "artica_backup");
	if($LastCount==$NeWCount){return;}
	SingleInstance_reload();
	
}


function SingleInstance_reload(){
	$unix=new unix();
	SingleInstance();
	$pid=SingleInstance_pid();
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "{$GLOBALS["deflog_start"]} reloading executed $pid since {$timepid}Mn...\n";}
		unix_system_HUP($pid);
		sleep(2);
		$pid=SingleInstance_pid();
		if(!$unix->process_exists($pid)){ SingleInstance_start(true);}
		return;
	}
	$PHP=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $PHP /usr/share/artica-postfix/exec.spamassassin.php --whitelist >/dev/null 2>&1 &");
	SingleInstance_start(true);
	

}
function SingleInstance_start($nopid=false){
	
	$sock=new sockets();
	$unix=new unix();
	
	
	if(!$nopid){
		$unix=new unix();
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__."pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){
			echo "{$GLOBALS["deflog_start"]} already Artica Starting process exists $pid\n";
			return;
		}
		
		@file_put_contents($pidfile,getmypid());		
		
	}
	
	$miltergreybin=$unix->find_program("milter-greylist");
	$MilterGreyListEnabled=$sock->GET_INFO("MilterGreyListEnabled");
	if(!is_numeric($MilterGreyListEnabled)){$MilterGreyListEnabled=0;}
	

	$pid=SingleInstance_pid();
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "{$GLOBALS["deflog_start"]} Service already started $pid since {$timepid}Mn...\n";}
		return;
	}
	if($MilterGreyListEnabled==0){echo "{$GLOBALS["deflog_start"]} is not enabled ( see MilterGreyListEnabled)\n";return;}
	
	$dirs[]="/var/run/milter-greylist";
	$dirs[]="/var/spool/postfix/var/run/milter-greylist";
	$dirs[]="/var/milter-greylist";
	$dirs[]="/var/lib/milter-greylist";
	$dirs[]="/usr/local/var/milter-greylist/";
	
	while (list ($num, $directory) = each ($dirs)){
		@mkdir($directory,0755,true);
		@chown($directory, "postfix");
		@chgrp($directory, "postfix");
		@chmod($directory,0755);
	}
	
	$FullSocketPath="/var/run/milter-greylist/milter-greylist.sock";
	$pidpath="/var/run/milter-greylist/milter-greylist.pid";
	$dbpath="/var/milter-greylist/greylist.db";
	$confpath=SingleInstanceConfPath();
	
	
	$files[]="/var/milter-greylist/greylist.db";
	$files[]="/usr/local/var/milter-greylist/greylist.db";
	
	while (list ($num, $filename) = each ($files)){
		if(!is_file($filename)){@touch($filename);}
		@chown($filename, "postfix");
		@chgrp($filename, "postfix");
	}
	
	$MilterGreyListUseTCPPort=$sock->GET_INFO("MilterGreyListUseTCPPort");
	$MilterGeryListTCPPort=$sock->GET_INFO("MilterGeryListTCPPort");
	if(!is_numeric($MilterGeryListTCPPort)){$MilterGeryListTCPPort=0;}
	if(!is_numeric($MilterGreyListUseTCPPort)){$MilterGreyListUseTCPPort=0;}
	if($MilterGeryListTCPPort==0){$MilterGreyListUseTCPPort=0;}
	
	if(!is_file($confpath)){SingleInstance();}
	if(!is_file($dbpath)){@touch($dbpath);}
	@chown($dbpath, "postfix");
	@chgrp($dbpath, "postfix");
	if($MilterGreyListUseTCPPort==1){
		$FullSocketPath="inet:{$MilterGeryListTCPPort}";
	}
	
	$tmpfile=$unix->FILE_TEMP();
	$nohup=$unix->find_program("nohup");
	if($GLOBALS["OUTPUT"]){echo "{$GLOBALS["deflog_start"]} running daemon $miltergreybin\n";}
	$cmd="$nohup $miltergreybin -u postfix -P $pidpath -p $FullSocketPath -f $confpath -d $dbpath >$tmpfile 2>&1 &";
	if($GLOBALS["VERBOSE"]){echo "**** \n $cmd \n ************\n";}
	shell_exec($cmd);
	
	$f=explode("\n",@file_get_contents("$tmpfile"));
	while (list ($num, $ligne) = each ($f) ){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		if($GLOBALS["OUTPUT"]){echo "{$GLOBALS["deflog_start"]} $ligne\n";}
	}
	
	
	if($GLOBALS["OUTPUT"]){echo "{$GLOBALS["deflog_start"]} waiting 5s\n";}
	for($i=1;$i<6;$i++){
		if($GLOBALS["OUTPUT"]){echo "{$GLOBALS["deflog_start"]} waiting $i/5\n";}
		sleep(1);
		$pid=SingleInstance_pid();
		if($unix->process_exists($pid)){break;}
	}
	
	$pid=SingleInstance_pid();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "{$GLOBALS["deflog_start"]} Success PID $pid\n";}
	}else{
		if($GLOBALS["OUTPUT"]){echo "{$GLOBALS["deflog_start"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "$cmd\n";}
		return;
	}
	
	for($i=1;$i<15;$i++){
		if(!$unix->is_socket($FullSocketPath)){
			$socketname=basename($FullSocketPath);
			if($GLOBALS["OUTPUT"]){echo "{$GLOBALS["deflog_start"]} Waiting $socketname socket $i/5...\n";}
			sleep(1);
			continue;
		}
		break;
		
	}
	
	
	if($unix->is_socket($FullSocketPath)){
		if($GLOBALS["OUTPUT"]){echo "{$GLOBALS["deflog_start"]} $FullSocketPath OK\n";}
		@chown("$FullSocketPath","postfix");
		@chgrp($FullSocketPath, "postfix");
		@chmod($FullSocketPath,0777);
	}
	
	
}

function SingleInstance_stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "{$GLOBALS["deflog_sstop"]} service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=SingleInstance_pid();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "{$GLOBALS["deflog_sstop"]} service already stopped...\n";}
		return;
	}
	$pid=SingleInstance_pid();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");




	if($GLOBALS["OUTPUT"]){echo "{$GLOBALS["deflog_sstop"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=SingleInstance_pid();
		if(!$unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "{$GLOBALS["deflog_sstop"]} stopped...\n";}
			break;
		}
		if($GLOBALS["OUTPUT"]){echo "{$GLOBALS["deflog_sstop"]} service wait $i/5...\n";}
	}

	$pid=SingleInstance_pid();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "{$GLOBALS["deflog_sstop"]} service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "{$GLOBALS["deflog_sstop"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);



	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "{$GLOBALS["deflog_sstop"]} service failed...\n";}
		return;
	}
}


function SingleInstance(){
	$sock=new sockets();
	$unix=new unix();
	
	
	
	$php5=$unix->LOCATE_PHP5_BIN();
	$miltergreybin=$unix->find_program("milter-greylist");
	$MilterGreyListEnabled=$sock->GET_INFO("MilterGreyListEnabled");
	if(!is_numeric($MilterGreyListEnabled)){$MilterGreyListEnabled=0;}
	if($MilterGreyListEnabled==0){echo "{$GLOBALS["deflog_start"]} Milter-greylist is not enabled\n";return;}
	
	$nohup=$unix->find_program("nohup");
	echo "{$GLOBALS["deflog_start"]} single instance execute exec.white-black-central.php\n";
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.white-black-central.php >/dev/null 2>&1 &");
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.postfix.maincf.php --body-checks >/dev/null 2>&1 &");
	
	$mg=new milter_greylist(false,"master","master");
	$datas=$mg->BuildConfig();
	if($datas<>null){
		$conf_path=SingleInstanceConfPath();
		@mkdir(dirname($conf_path),0666,true);
		echo "{$GLOBALS["deflog_start"]} single instance $conf_path\n";
		
		
		$tbl=explode("\n",$datas);
		echo "{$GLOBALS["deflog_start"]} cleaning $conf_path with ". count($tbl)." lines\n";
		while (list ($num, $ligne) = each ($tbl) ){
			$ligne=trim($ligne);
			if($ligne==null){continue;}
			$newf[]=$ligne;
		}
		$newf[]="";
		
		echo "{$GLOBALS["deflog_start"]} writing $conf_path (". count($newf)." lines)\n";
		@file_put_contents($conf_path,@implode("\n",$newf));
	}
	
	
	TestConfigFile($conf_path);
	echo "{$GLOBALS["deflog_start"]} notify administrator\n";
	$unix->send_email_events("Milter-greylist has been reconfigured", "By {$GLOBALS["WHOPROCESS"]}\nSettings:\n".@implode("\n",$newf), "postfix");
	echo "{$GLOBALS["deflog_start"]} done.\n";
}

function SingleInstance_pid(){
	$unix=new unix();
	$pidpath="/var/run/milter-greylist/milter-greylist.pid";
	$pid=$unix->get_pid_from_file($pidpath);
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF($unix->find_program("milter-greylist"));
}



function SingleInstanceConfPath(){
if(is_file('/etc/milter-greylist/greylist.conf')){return '/etc/milter-greylist/greylist.conf';}
if(is_file('/etc/mail/greylist.conf')){return '/etc/mail/greylist.conf';}
if(is_file('/opt/artica/etc/milter-greylist/greylist.conf')){return '/opt/artica/etc/milter-greylist/greylist.conf';}
return '/etc/mail/greylist.conf';
}

function parse_multi_databases(){
		$sock=new sockets();
		$uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));	
	
	$sql="SELECT ValueTEXT,ip_address,ou FROM postfix_multi WHERE `key`='PluginsEnabled' AND uuid='$uuid'";
	if($GLOBALS["DEBUG"]){echo "$sql\n";}
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo __FUNCTION__. " $q->mysql_error\n";}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$array_filters=unserialize(base64_decode($ligne["ValueTEXT"]));
		if($GLOBALS["DEBUG"]){echo "{$ligne["ip_address"]} APP_MILTERGREYLIST ->{$array_filters["APP_MILTERGREYLIST"]}  \n";}
		if($array_filters["APP_MILTERGREYLIST"]==null){continue;}
		if($array_filters["APP_MILTERGREYLIST"]==0){continue;}
		$hostname=MultiplesInstancesGetmyhostname($ligne["ip_address"]);
		$ou=$ligne["ou"];
		if($GLOBALS["DEBUG"]){echo "$hostname -> $ou\n";}
		$GLOBALS["hostnames"][$hostname]=$ou;
		$file="/var/milter-greylist/$hostname/greylist.db";
		parse_database($file,$hostname);
	}	
	
}

function MultiplesInstances($hostname=null,$ou=null){
	echo "{$GLOBALS["deflog_start"]} milter-greylist: MultiplesInstances() `$hostname` and ou `$ou`\n";
	if(($ou==null) && ($hostname==null)){MultiplesInstancesFound();return;}
	
	if($hostname==null){echo __FUNCTION__." unable to get hostname name\n";return;}	
	$mg=new milter_greylist(false,$hostname,$ou);
	$datas=$mg->BuildConfig();
	@mkdir("/etc/milter-greylist/$hostname",0666,true);
	@mkdir("/var/spool/$hostname/run/milter-greylist",0666,true);
	
		$tbl=explode("\n",$datas);
		while (list ($num, $ligne) = each ($tbl) ){
			$ligne=trim($ligne);
			if($ligne==null){continue;}
			$newf[]=$ligne;
		}
		$newf[]="";
		echo "{$GLOBALS["deflog_start"]} milter-greylist $hostname: writing /etc/milter-greylist/$hostname/greylist.conf\n";
		$datas=@implode("\n",$newf);	
	
	@file_put_contents("/etc/milter-greylist/$hostname/greylist.conf",$datas);
	echo "{$GLOBALS["deflog_start"]} milter-greylist $hostname: or=$ou START_ONLY={$GLOBALS["START_ONLY"]},STOP_ONLY={$GLOBALS["STOP_ONLY"]}\n";
	if($GLOBALS["STOP_ONLY"]==1){MultiplesInstances_stop($hostname,$ou);}
	if($GLOBALS["START_ONLY"]==1){MultiplesInstances_start($hostname,$ou);}

}

function MultiplesInstancesGetmyhostname($ip_address){
		$sock=new sockets();
		$uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));	
		$sql="SELECT `value` FROM postfix_multi WHERE `key`='myhostname' AND uuid='$uuid' AND ip_address='$ip_address'";
		if($GLOBALS["DEBUG"]){echo "$sql\n";}
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){
			echo __FUNCTION__. " $q->mysql_error\n";
		}
		if($GLOBALS["DEBUG"]){echo "$ip_address -> {$ligne["value"]}\n";}
		return $ligne["value"];
}


function MultiplesInstancesFound($pid=false,$onlystart=false){
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".time";
	$unix=new unix();
	
	if(!$GLOBALS["FORCE"]){
		if($pid){
			if($unix->file_time_min($pidtime)<2){
				if($GLOBALS["VERBOSE"]){echo "Minimal 2mn\n";}
				return;}
			$pid=$unix->get_pid_from_file($pidfile);
			if($unix->process_exists($pid,basename(__FILE__))){
				$processtime=$unix->PROCCESS_TIME_MIN($pid);
				if($GLOBALS["VERBOSE"]){echo "Already running pid $pid\n";}
				if($processtime<5){return;}
				$kill=$unix->find_program("kill");
				unix_system_kill_force($pid);
			}
		}
	}
	if(!$GLOBALS["FORCE"]){
		@unlink($pidtime);
		@file_put_contents($pidtime, time());
		@file_put_contents($pidfile, getmypid());
	}
	
	$sock=new sockets();
	$uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));	
	
	$sql="SELECT ValueTEXT,ip_address,ou FROM postfix_multi WHERE `key`='PluginsEnabled' AND uuid='$uuid'";
	if($GLOBALS["DEBUG"]){echo "$sql\n";}
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo __FUNCTION__. " $q->mysql_error\n";}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$array_filters=unserialize(base64_decode($ligne["ValueTEXT"]));
		if(!isset($array_filters["APP_MILTERGREYLIST"])){$array_filters["APP_MILTERGREYLIST"]=null;}
		if($GLOBALS["DEBUG"]){echo "{$ligne["ip_address"]} APP_MILTERGREYLIST -> `{$array_filters["APP_MILTERGREYLIST"]}`  \n";}
		if($array_filters["APP_MILTERGREYLIST"]==null){continue;}
		if($array_filters["APP_MILTERGREYLIST"]==0){continue;}
		$hostname=MultiplesInstancesGetmyhostname($ligne["ip_address"]);
		$ou=$ligne["ou"];
		if($GLOBALS["DEBUG"]){echo "$hostname -> $ou\n";}
		$GLOBALS["hostnames"][$hostname]=$ou;
		if($onlystart){MultiplesInstances_start($hostname,$ou);continue;}
		MultiplesInstances($hostname,$ou);
		
	}
	
	
	
}


function MultiplesInstances_start($hostname,$ou){
	$hostname=trim($hostname);
	if($hostname==null){
		if($GLOBALS["VERBOSE"]){echo "{$GLOBALS["deflog_start"]} milter-greylist (".__FUNCTION__.") return -> hostname is null\n";}
		return;
	}
	
	
	$unix=new unix();
	echo "{$GLOBALS["deflog_start"]} milter-greylist hostname:$hostname OU:($ou) line: ".__LINE__."\n";
	$main=new maincf_multi($hostname,$ou);
	
	$array_filters=unserialize(base64_decode($main->GET_BIGDATA("PluginsEnabled")));
	if($array_filters["APP_MILTERGREYLIST"]==0){$enabled=false;}	
	
	$pid=MultiplesInstancesPID($hostname);
	if($unix->process_exists($pid)){echo "{$GLOBALS["deflog_start"]} milter-greylist $hostname already running PID $pid\n";
		return;
	}
	echo "{$GLOBALS["deflog_start"]} milter-greylist hostname \"$hostname\" line: ".__LINE__."\n";
	$bin_path=$unix->find_program("milter-greylist");
	
	@mkdir("/var/spool/postfix/var/run/milter-greylist/$hostname",0755,true);
	@mkdir("/var/milter-greylist/$hostname",666,true);
	if(!is_file("/var/milter-greylist/$hostname/greylist.db")){@file_put_contents("/var/milter-greylist/$hostname/greylist.db"," ");}
	shell_exec("/bin/chmod 644 /var/milter-greylist/$hostname/greylist.db");
	
	
	if(!is_file("/etc/milter-greylist/$hostname/greylist.conf")){
		echo "{$GLOBALS["deflog_start"]} milter-greylist $hostname /etc/milter-greylist/$hostname/greylist.conf does not exists\n";
		MultiplesInstances($hostname,$ou);return ;
	}

	
	$cmdline="$bin_path -P /var/spool/postfix/var/run/milter-greylist/$hostname/greylist.pid";
	$cmdline=$cmdline." -p /var/spool/postfix/var/run/milter-greylist/$hostname/greylist.sock";
	$cmdline=$cmdline." -d /var/milter-greylist/$hostname/greylist.db";
	$cmdline=$cmdline." -f /etc/milter-greylist/$hostname/greylist.conf";
	
	if($GLOBALS["VERBOSE"]){echo "{$GLOBALS["deflog_start"]} milter-greylist $cmdline\n";}
	
	system($cmdline);
	
	for($i=0;$i<20;$i++){
		$pid=MultiplesInstancesPID($hostname);
		if($unix->process_exists($pid)){
			echo "{$GLOBALS["deflog_start"]} milter-greylist $hostname started PID $pid\n";
			break;
		}
		sleep(1);	
	}
	
	$pid=MultiplesInstancesPID($hostname);
	if($unix->process_exists($pid)){
			$main->ConfigureMilters();	
		}
		
	for($i=0;$i<10;$i++){
		if(is_file("/var/spool/postfix/var/run/milter-greylist/$hostname/greylist.sock")){break;}
		echo "{$GLOBALS["deflog_start"]} milter-greylist waiting greylist.sock ($i/10)\n";
		sleep(1);
	}	
	
	@chown("/var/spool/postfix/var/run/milter-greylist", "postfix");
	@chgrp("/var/spool/postfix/var/run/milter-greylist", "postfix");
	@chown("/var/spool/postfix/var/run/milter-greylist/$hostname/greylist.sock", "postfix");
	@chmod("/var/spool/postfix/var/run/milter-greylist/$hostname/greylist.sock",0777);	
	@chmod("/var/spool/postfix/var/run/milter-greylist",0755);	
	$unix->chown_func("postfix","postfix","/var/spool/postfix/var/run/milter-greylist/*");
	$unix->chown_func("postfix","postfix","/var/spool/postfix/var/run/milter-greylist/$hostname");
	$unix->chown_func("postfix","postfix","/var/spool/postfix/var/run/milter-greylist/$hostname/greylist.sock");
	
}

function MultiplesInstances_stop($hostname){
	$unix=new unix();
	$pid=MultiplesInstancesPID($hostname);
	
	if(!$unix->process_exists($pid)){
		echo "{$GLOBALS["deflog_sstop"]} $hostname already stopped\n";
		return;
	}
	
	echo "{$GLOBALS["deflog_sstop"]} $hostname stopping pid $pid\n";
	unix_system_kill($pid);

	for($i=0;$i<20;$i++){
		$pid=MultiplesInstancesPID($hostname);
		if(!$unix->process_exists($pid)){
			echo "{$GLOBALS["deflog_sstop"]} $hostname stopped\n";
			break;
		}
		
		echo "{$GLOBALS["deflog_sstop"]} $hostname waiting pid $pid\n";
			if($unix->process_exists($pid)){
				unix_system_kill_force($pid);
				sleep(1);
				continue;
			}
			break;
		}

}
function MultiplesInstancesPID($hostname){$unix=new unix();return $unix->get_pid_from_file("/var/spool/postfix/var/run/milter-greylist/$hostname/greylist.pid");}

function MultiplesInstances_status(){
	$unix=new unix();
	$users=new usersMenus();
	$sock=new sockets();

	
	if(!$users->MILTERGREYLIST_INSTALLED){
	if($GLOBALS["DEBUG"]){echo __FUNCTION__ ." NoT installed\n";}
		return null;
	}
	$main=new maincf_multi($GLOBALS["hostname"],$GLOBALS["ou"]);
	$array_filters=unserialize(base64_decode($main->GET_BIGDATA("PluginsEnabled")));
	$enabled=$array_filters["APP_MILTERGREYLIST"];
	$pid_path="/var/spool/postfix/var/run/milter-greylist/{$GLOBALS["hostname"]}/greylist.pid";
	if($GLOBALS["DEBUG"]){echo __FUNCTION__ ."{$GLOBALS["hostname"]} ({$GLOBALS["ou"]}) -> enabled=$enabled\n";}
	$master_pid=trim(@file_get_contents($pid_path));
	if($GLOBALS["DEBUG"]){echo __FUNCTION__ ."master_pid=$master_pid\n";}
		
		$l[]="[MILTER_GREYLIST]";
		$l[]="service_name=APP_MILTERGREYLIST";
	 	$l[]="master_version=".GetVersionOf("milter-greylist");
	 	$l[]="service_cmd=mgreylist";	
	 	$l[]="service_disabled=$enabled";
	 	$l[]="pid_path=$pid_path";
	 	
	 	$l[]="remove_cmd=--milter-grelist-remove";
		if(!$unix->process_exists($master_pid)){$l[]="running=0";$l[]="";echo implode("\n",$l);exit;}	
		$l[]="running=1";
		$l[]=GetMemoriesOf($master_pid);
		$l[]="";
		if($GLOBALS["DEBUG"]){echo __FUNCTION__ ."FINISH\n";}
	echo implode("\n",$l);	
	
}
function GetVersionOf($name){
	exec("/usr/share/artica-postfix/bin/artica-install --export-version $name",$results);
	$version=trim(implode("",$results));
	$version=trim(implode("",$results));
	return $version;	
}
function GetMemoriesOf($pid){
	$unix=new unix();
	$rss=$unix->PROCESS_MEMORY($pid,true);
	$vm=$unix->PROCESS_CACHE_MEMORY($pid,true);
	exec("pgrep -P $pid",$results);
	$count=0;
	while (list ($num, $ligne) = each ($results) ){
		$ligne=trim($ligne);
		if($ligne<1){continue;}
		$count=$count+1;
		$rss=$rss+$unix->PROCESS_MEMORY($ligne,true);
		$vm=$vm+$unix->PROCESS_CACHE_MEMORY($ligne,true);		
		
	}
	if($count==0){$count=1;}
	$l[]="master_pid=$pid";	
    $l[]="master_memory=$rss";
    //$l[]="master_cached_memory=$vm";
    $l[]="processes_number=$count";
	return implode("\n",$l);
	
}

function parse_database($filename,$hostname){
	if(!is_file($filename)){writelogs("Failed to open $filename no such file",__FUNCTION__,__FILE__,__LINE__);return ;}
	$users=new usersMenus();
	$handle = @fopen($filename, "r"); // Open file form read.
	if (!$handle) {writelogs("Fatal errror while open $filename",__FUNCTION__,__FILE__,__LINE__);return ;}
	$sqlA="DELETE FROM greylist_turples WHERE hostname='$hostname'";
	$prefix="INSERT IGNORE INTO greylist_turples(zmd5,ip_addr,mailfrom,mailto,stime,hostname,whitelisted) VALUES ";
	$q=new mysql();
	$q->Check_smtp_logs_table();
	$q->QUERY_SQL($sqlA,"artica_events");
	$sql=array();
	while (!feof($handle)){
		$buffer = fgets($handle, 4096);
		if(trim($buffer)==null){continue;}
		if(preg_match("#(.+?)\s+(.*?)\s+(.+?)\s+([0-9]+)#", $buffer,$re)){
			$ip=$re[1];
			$from=$re[2];
			$from=str_replace("<", "", $from);
			$from=str_replace(">", "", $from);
			$to=$re[3];
			$to=str_replace("<", "", $to);
			$to=str_replace(">", "", $to);			
			$time=$re[4];
			$whitelisted=0;
			if(preg_match("#[0-9]+\s+AUTO#", $buffer)){$whitelisted=1;}
			$md5=md5("$ip$from$to$time$hostname$whitelisted");

			$sql[]="('$md5','$ip','$from','$to','$time','$hostname',$whitelisted)";
			if(count($sql)>500){
				if($GLOBALS["VERBOSE"]){echo "{$GLOBALS["deflog_start"]} milter-greylist Finally save ".count($sql)." events\n";}
				$newsql=$prefix." ".@implode(",", $sql);
				$q->QUERY_SQL($newsql,"artica_events");
				if(!$q->ok){echo $q->mysql_error."\n";return ;}
				$sql=array();
			}
			
			continue;
		}else{
			if($GLOBALS["VERBOSE"]){echo "{$GLOBALS["deflog_start"]} milter-greylist no match $buffer\n";}
		}
	}
		
if(count($sql)>0){
	if($GLOBALS["VERBOSE"]){echo "{$GLOBALS["deflog_start"]} milter-greylist Finally save ".count($sql)." events\n";}
	$newsql=$prefix." ".@implode(",", $sql);$q->QUERY_SQL($newsql,"artica_events");$sql=array();}
	if(!$q->ok){echo $q->mysql_error."\n";return ;}	
	$unix=new unix();
	$tail=$unix->find_program("tail");
	$chmod=$unix->find_program("chmod");
	exec("$tail -n 2 $filename 2>&1",$tails);

	while (list ($num, $ligne) = each ($tails) ){
			if(preg_match("#Summary:\s+([0-9]+)\s+records,\s+([0-9]+)\s+greylisted,\s+([0-9]+)\s+whitelisted,\s+([0-9]+)\s+tarpitted#", $ligne,$re)){
				$array["RECORDS"]=$re[1];
				$array["GREYLISTED"]=$re[2];
				$array["WHITELISTED"]=$re[3];
				$array["TARPITED"]=$re[4];
				@file_put_contents("/usr/share/artica-postfix/ressources/logs/greylist-count-$hostname.tot", serialize($array));
				shell_exec("$chmod 755 /usr/share/artica-postfix/ressources/logs/greylist-count-$hostname.tot");
			}else{
				if($GLOBALS["VERBOSE"]){echo "{$GLOBALS["deflog_start"]} milter-greylist no match $ligne\n";}
			}
	}
			
}

// -P pidfile


?>