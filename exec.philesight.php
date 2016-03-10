<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["META_PING"]=false;
$GLOBALS["MECMDS"]=@implode(" ", $argv);
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--meta-ping#",implode(" ",$argv))){$GLOBALS["META_PING"]=true;}



if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.samba.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');

if($argv[1]=='--check'){check();die();}
if($argv[1]=='--exists'){InMemQUestion();die();}
if($argv[1]=='--rebuild'){run();die();}
if($argv[1]=='--pid'){echo getPID()."\n";die();}
if($argv[1]=='--run'){echo run()."\n";die();}
if($argv[1]=='--directories'){scan_directories()."\n";die();}
if($argv[1]=='--diskof'){diskof($argv[2])."\n";die();}



function check(){
	
	$unix=new unix();
	$MEMORY=$unix->MEM_TOTAL_INSTALLEE();
	
	if($MEMORY<624288){
		writelogs(basename(__FILE__).":Too low memory, die();",basename(__FILE__),__FILE__,__LINE__);
		die();
	}	
	
	if(!is_file("/usr/bin/ruby1.8")){
		$unix->DEBIAN_INSTALL_PACKAGE("ruby1.8");
	}
	
$EnablePhileSight=GET_INFO_DAEMON("EnablePhileSight");
if($EnablePhileSight==null){$EnablePhileSight=0;}

	if($EnablePhileSight==0){
		writelogs("feature disabled, aborting...",__FUNCTION__,__FILE__,__LINE__);
		die();
	}
	
	
	if(system_is_overloaded()){
		writelogs("System overloaded, aborting this feature for the moment",__FUNCTION__,__FILE__,__LINE__);
		die();
	}
	@mkdir("/opt/artica/philesight");

	$unix=new unix();
	$min=$unix->file_time_min("/opt/artica/philesight/database.db");
	$sock=new sockets();
	$rr=$sock->GET_INFO("PhileSizeRefreshEach");
	if($rr==null){$rr=120;}
	if($rr=="disable"){die();}
	writelogs("/opt/artica/philesight/database.db = $min minutes, $rr minutes to run",__FUNCTION__,__FILE__,__LINE__);
	if($min>=$rr){
		run();
	}
}


function InMemQUestion(){
	$unix=new unix();
	$pid=$unix->PIDOF_PATTERN("philesight --db");
	if($unix->process_exists($pid)){return true;}
	return false;
}
function run(){
	$unix=new unix();
	$sock=new sockets();
	$PhileSizeCpuLimit=$sock->GET_INFO("PhileSizeCpuLimit");
	if($PhileSizeCpuLimit==null){$PhileSizeCpuLimit=0;}
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	$unix=new unix();
	if($unix->process_exists($pid,basename(__FILE__))){
		system_admin_events("Already executed PID $pid", __FILE__, __FUNCTION__, __LINE__, "disks");
		die();
	}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);		
	
	$t=time();
	chdir("/usr/share/artica-postfix/bin");
	$NICE=EXEC_NICE();
	$unix=new unix();
	$tmpfile=$unix->FILE_TEMP();
	
	
	if(!is_file("/usr/bin/ruby1.8")){
		$unix->DEBIAN_INSTALL_PACKAGE("ruby1.8");
	}
	
	$cmd="$NICE /usr/share/artica-postfix/bin/philesight --db /opt/artica/philesight/database.db --index / 2>&1";
	
	exec($cmd,$results);
	$database_size=$unix->file_size_human("/opt/artica/philesight/database.db");
	$took=$unix->distanceOfTimeInWords($t,time(),true);
	system_admin_events("Scanning the root directory done took $took: ".@implode("\n", $results), __FILE__, __FUNCTION__, __LINE__, "disks");
	
	sleep(3);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#run database recovery#",$ligne)){
			system_admin_events("Database is corrupted, delete it", __FILE__, __FUNCTION__, __LINE__, "disks");
			$corrupted=true;
		}
	}
	
	if($corrupted){
		@unlink("/opt/artica/philesight/database.db");
		$unix->THREAD_COMMAND_SET($GLOBALS["MECMDS"]);
	}
	
}

function getPID(){
	$unix=new unix();
	exec($unix->find_program("pgrep"). " -l -f \"/usr/share/artica-postfix/bin/philesight\"",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#pgrep#",$ligne)){continue;}
		if(preg_match("#^([0-9]+).+?philesight#",$ligne,$re)){return $re[1];}
	}	
}

function scan_directories(){
	$UPDATED=false;
	if($GLOBALS["VERBOSE"]){$GLOBALS["FORCE"]=true;}
	if($GLOBALS["FORCE"]){
		$UPDATED=true;
		ini_set('html_errors',0);
		ini_set('display_errors', 1);
		ini_set('error_reporting', E_ALL);
	}
	$unix=new unix();
	$pidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$TimeFile="/etc/artica-postfix/pids/exec.philesight.php.scan_directories.time";
	
	$pid=$unix->get_pid_from_file($pidFile);
	if($unix->process_exists($pid)){return;}
	$START_TIME=time();
	
	if(!$GLOBALS["FORCE"]){
		$time=$unix->file_time_min($TimeFile);
		if($time<120){return;}
		if(system_is_overloaded(__FILE__)){ return; }
	}
	
	

	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	
	
	if(!is_file("/usr/bin/ruby1.8")){
		build_progress("{please_wait}, /usr/bin/ruby1.8 no such binary [installing]...",5);
		$unix->DEBIAN_INSTALL_PACKAGE("ruby1.8"); 
	}
	if(!is_file("/usr/bin/ruby1.8")){
		build_progress("{failed}, /usr/bin/ruby1.8 no such binary...",110);
		system_admin_events("/usr/bin/ruby1.8 no such binary, philesight cannot be used!",__FUNCTION__,__FILE__,__LINE__);
	}
	$sock=new sockets();
	$EnableArticaMetaClient=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaMetaClient"));
	$EnableArticaMetaServer=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaMetaServer"));
	if($EnableArticaMetaServer==1){$EnableArticaMetaClient=0;}
	
	if($EnableArticaMetaClient==1){
		meta_events("Meta Client Enabled",__FUNCTION__,__LINE__);
	}
	
	$q=new mysql();
	$results=$q->QUERY_SQL("SELECT * FROM philesight WHERE enabled=1","artica_backup");
	@mkdir("/usr/share/artica-postfix/img/philesight",0755,true);
	@mkdir("/home/artica/philesight",0755,true);
	$NICE=$unix->EXEC_NICE();
	
	build_progress("{please_wait}, {scaning_directories}...",10);
	$pr=10;
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$directory=$ligne["directory"];
		$partition=$ligne["partition"];
		$md5=md5($directory);
		$maxtime=$ligne["maxtime"];
		if($maxtime==0){continue;}
		$lastscan=$ligne["lastscan"];
		if($lastscan==0){$lastscan=1000000;}
		$sql_time_min=sql_time_min($lastscan);
		
		$partition=$unix->DIRPART_OF($directory);
		$hd=$unix->DIRDISK_OF($directory);
		$ARRAY=$unix->DF_SATUS_K($directory);
		$USED=$ARRAY["POURC"];
		$FREEMB=round($ARRAY["AIVA"]/1024);
		
		$ARRAY_META[$directory]["MD5"]=$md5;
		$ARRAY_META[$directory]["lastscan"]=$lastscan;
		$ARRAY_META[$directory]["HD"]=$hd;
		$ARRAY_META[$directory]["PARTITION"]=$partition;
		$ARRAY_META[$directory]["USED"]=$USED;
		$ARRAY_META[$directory]["FREEMB"]=$FREEMB;
		
		$directoryXXX=mysql_escape_string2($directory);
		$sql="UPDATE `philesight` SET `hd`='$hd',
		partition='$partition',
		hd='$hd',
		USED='$USED',
		FREEMB='$FREEMB'
		WHERE `directory`='$directoryXXX'";
		$q->QUERY_SQL($sql,'artica_backup');
		
		
		if(is_file("/home/artica/philesight/$md5.db")){
			if(!$GLOBALS["FORCE"]){
				if($sql_time_min<$maxtime){continue;}
			}
		}
		$pr++;
		
		if($GLOBALS["FORCE"]){echo "Partition............: $partition\n";}
		if($GLOBALS["FORCE"]){echo "Hard disk............: $hd\n";}
		if($GLOBALS["FORCE"]){echo "Used.................: {$USED}%\n";}
		if($GLOBALS["FORCE"]){echo "Free.................: {$FREEMB}MB\n";}
		
		build_progress("{please_wait}, {scaning_directory} $directory...",$pr);
		$UPDATED=true;
		$cmd="$NICE /usr/share/artica-postfix/bin/philesight --db /home/artica/philesight/$md5.db --only-dirs --index \"$directory\" 2>&1";
		if($GLOBALS["FORCE"]){echo "$cmd\n";}
		system($cmd);
		
		if(!is_file("/home/artica/philesight/$md5.db")){
			if($GLOBALS["FORCE"]){echo "/home/artica/philesight/$md5.db no such file.\n";}
			
			
		}
		
		build_progress("{please_wait}, Generating report on $directory...",$pr);
		$cmd="$NICE /usr/share/artica-postfix/bin/philesight --db /home/artica/philesight/$md5.db --path \"$directory\" --draw /usr/share/artica-postfix/img/philesight/$md5.png";
		if($GLOBALS["FORCE"]){echo "$cmd\n";}
		
		system($cmd);

		
		
		$directory=mysql_escape_string2($directory);
		$sql="UPDATE `philesight` SET `hd`='$hd',
		partition='$partition',
		lastscan=".time().",
		hd='$hd',
		USED='$USED',
		FREEMB='$FREEMB'
		WHERE `directory`='$directory'";

		$q->QUERY_SQL($sql,'artica_backup');
		if(!$q->ok){
			echo $q->mysql_error."\n";
			build_progress("{failed}, MySQL error",110);
			return;
		}
		
		
	}
	
	
	build_progress("{success}",100);
	
	
	$Took=$unix->distanceOfTimeInWords($START_TIME,time());
	squid_admin_mysql(2, "$pr directories size scanned ( took $Took) ", null,__FILE__,__LINE__);
	system_admin_mysql(2, "$pr directories size scanned ( took $Took) ", null,__FILE__,__LINE__);
	
	
	
	if($EnableArticaMetaClient){
		meta_events("UPDATED=$UPDATED",__FUNCTION__,__LINE__);
	}
	
	if($UPDATED){
		$sock=new sockets();
		$EnableArticaMetaClient=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaMetaClient"));
		$EnableArticaMetaServer=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaMetaServer"));
		if($EnableArticaMetaServer==1){$EnableArticaMetaClient=0;}
	
		if($EnableArticaMetaClient==1){
			$cp=$unix->find_program("cp");
			$tar=$unix->find_program("tar");
			$rm=$unix->find_program("rm");
			@mkdir("/home/artica/metaclient/upload/philesight",true,0755);
			@file_put_contents("/home/artica/metaclient/upload/philesight/dump.db", serialize($ARRAY_META));
			shell_exec("$cp -f /usr/share/artica-postfix/img/philesight/* /home/artica/metaclient/upload/philesight/");
			@chdir("/home/artica/metaclient/upload/philesight");
			system("cd /home/artica/metaclient/upload/philesight");
			if(is_file("/home/artica/metaclient/upload/philesight.tgz")){@unlink("/home/artica/metaclient/upload/philesight.tgz");}
			shell_exec("$tar -cf /home/artica/metaclient/upload/philesight.tgz *");
			shell_exec("$rm -rf /home/artica/metaclient/upload/philesight/*");
		}
		
	}
	
	if($GLOBALS["META_PING"]){
		if(is_file("/home/artica/metaclient/upload/philesight.tgz")){
			$php=$unix->LOCATE_PHP5_BIN();
			system("$php /usr/share/artica-postfix/exec.artica-meta-client.php --ping --force >/dev/null 2>&1 &");
		}
		
	}
	
}

function meta_events($text,$function,$line=0){
	$file=basename(__FILE__);
	$pid=@getmypid();
	$date=@date("H:i:s");
	$logFile="/var/log/artica-meta-agent.log";
	$size=@filesize($logFile);
	if($size>1000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');
	$text="[$file][$pid] $date $function:: $text (L.$line)\n";
	if($GLOBALS["VERBOSE"]){echo $text;}
	@fwrite($f, $text);
	@fclose($f);
}

function build_progress($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/system.dirmon.progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}

function diskof($path){
	$unix=new unix();
	echo $path ." = ".$unix->DIRDISK_OF($path)."\n";
	
}

function sql_time_min($time){
	$data1 = $time;
	$data2 = time();
	$difference = ($data2 - $data1);
	$results=intval(round($difference/60));
	if($results<0){$results=1;}
	return $results;
}



?>