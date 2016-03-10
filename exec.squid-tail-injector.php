<?php
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if($GLOBALS["VERBOSE"]){echo "DEBUG::: ".@implode(" ", $argv)."\n";}
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["NOLOCK"]=true;
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.squid.tail.inc");
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
$GLOBALS["LOGFILE"]="/var/log/artica-postfix/dansguardian-logger.debug";
if(preg_match("#--simulate#",implode(" ",$argv))){$GLOBALS["SIMULATE"]=true;}
if(preg_match("#--nolock#",implode(" ",$argv))){$GLOBALS["NOLOCK"]=true;}

for($i=1;$i<count($argv[1]);$i++){
	$GLOBALS["PARSED_COMMANDS"]=$GLOBALS["PARSED_COMMANDS"]." {$argv[$i]}";
}

if($argv[1]=="--words"){WordScanners(true);die();}
if($argv[1]=="--unveiltech"){unveiltech();die();}
if($argv[1]=="--youtube"){youtube(true);die();}
if($argv[1]=="--users-agents"){useragents();die();}
if($argv[1]=="--users-size"){ParseUsersSize();die();}
if($argv[1]=="--squid"){ParseSquidLogMain();die();}
if($argv[1]=="--nudity"){nudityScan();die();}
if($argv[1]=="--brut"){ParseSquidLogBrut(true);die();}
if($argv[1]=="--squid-brut-proc"){die();}
if($argv[1]=="--squid-sql-proc"){die();}
if($argv[1]=="--users-auth"){ParseUserAuth(true);die();}
if($argv[1]=="--main"){ParseSquidLogMain(true);die();}
if($argv[1]=="--clean-squid-queues"){CleanSquidQueues();die();}






	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".time";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$RepairHourtimeFile="/etc/artica-postfix/pids/".basename(__FILE__).".repair-hour.time";
	$RepairHourYoutubetimeFile="/etc/artica-postfix/pids/".basename(__FILE__).".youtube-hour.time";
	$CategorizetimeFile="/etc/artica-postfix/pids/".basename(__FILE__).".SquidCategorizeTablestimeFile.time";
	$CategorizeAllTablestimeFile="/etc/artica-postfix/pids/".basename(__FILE__).".SquidCategorizeAllTablestimeFile.time";
	$UpdateCategoriesArticaTimeFile="/etc/artica-postfix/pids/".basename(__FILE__).".UpdateCategoriesArticaTimeFile.time";
	$RTTSizeTimeFile="/etc/artica-postfix/pids/".basename(__FILE__).".RTTSize.time";
	$CachePerfsFile="/etc/artica-postfix/pids/".basename(__FILE__).".CachePerfs.time";
	
	
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	$unix=new unix();
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		events("Already executed pid $pid since {$time}mn-> DIE");
		
		if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid since {$time}mn\n";}
		die();
	}
	
	$timeP=$unix->file_time_min($pidtime);
	if($timeP<5){
		events("Main::Line: ".__LINE__." 5Mn minimal current: {$timeP}mn-> DIE");
		die();
	}
	
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);	
	
	
	
	$sock=new sockets();
	$EnableRemoteSyslogStatsAppliance=$sock->GET_INFO("EnableRemoteSyslogStatsAppliance");
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	if($DisableArticaProxyStatistics==1){events("DIE, Artica Statistics are disabled");die();}
	if($EnableRemoteSyslogStatsAppliance==1){
		events("DIE, using remote statistics Appliance with Syslog..");
		ParseSquidLogBrut(false);
		die();
	}
	
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$nice=EXEC_NICE();
	
	events("Executed pid $mypid");
	events("Execute youtube()");
	youtube();
	events("Execute nudityScan()");
	nudityScan();
	events("Execute WordScanners()");
	WordScanners();
	

	$CachePerfs=$unix->file_time_min($CachePerfsFile);
	if($CachePerfs>800){
		$cmd=trim("$nohup $nice $php ".dirname(__FILE__)."/exec.squid.stats.days.cached.php --schedule-id={$GLOBALS["SCHEDULE_ID"]} >/dev/null 2>&1 &");
		events("$cmd");
		shell_exec($cmd);
		@unlink($CachePerfsFile);
		@file_put_contents($CachePerfsFile, time());
	}	

	events("FINISH....");
	
	



function GetComputerName($ip){
		if(isset($GLOBALS["resvip"][$ip])){
			if(strlen($GLOBALS["resvip"][$ip])>3){return $GLOBALS["resvip"][$ip];}
		}
		$name=gethostbyaddr($ip);
		$GLOBALS["resvip"][$ip]=$name;
		return $name;
		}
		
		
function nudityScan(){
	if(function_exists("system_is_overloaded")){
		if(system_is_overloaded()){
			
			return;
		}	
	}
	$SquidNuditScanParams=unserialize(base64_decode(@file_get_contents("/etc/squid3/SquidNudityScanParams")));
	$iPicScanVal = $SquidNuditScanParams['picscanval'];
	if(!is_numeric($iPicScanVal)){$iPicScanVal=70;}
	$iPicScanVal=intval($iPicScanVal);
	if($iPicScanVal>99){$iPicScanVal=99;}	
	
	if(!is_dir("/var/log/squid/nudity")){return;}
	if (!$handle = opendir("/var/log/squid/nudity")){return;}
	
	$countDeFiles=0;
	$FF=array();
	while (false !== ($filename = readdir($handle))) {
				if($filename=="."){continue;}
				if($filename==".."){continue;}
				$targetFile="/var/log/squid/nudity/$filename";
				$countDeFiles++;
				$array=unserialize(@file_get_contents($targetFile));
				
				while (list ($key, $val) = each ($array) ){
					$array[$key]=str_replace("'", "`", $val);
				}
				
				$zmd5=md5(serialize($array));
				$uid=addslashes($array["LOGIN"]);
				$ipaddr=$array["IPADDR"];
				$MAC=addslashes($array["MAC"]);
				if(!__IsPhysicalAddress($MAC)){$MAC=null;}
				$hostname=addslashes($array["HOST"]);
				$uri=addslashes($array["URI"]);
				$servername=addslashes($array["RHOST"]);
				$POURC=$array["POURC"];
				$time=filemtime($targetFile);
				$tablePrefix=date("YmdH",$time);
				$zDate=date("Y-m-d H:i:s");
				
				
				
				
				$sqline="('$zmd5','$servername','$uri','$ipaddr','$hostname','$zDate','$uid','$MAC','$POURC')";
				$FF[$tablePrefix][]=$sqline;
				@unlink($targetFile);
				
		}

		if(count($FF)==0){return;}
		
		$q=new mysql_squid_builder();
		while (list ($tablePrefix, $f) = each ($FF) ){
			if(count($f)>0){
				if($q->TableNudityHour($tablePrefix)){
					$tablename="znudehour_$tablePrefix";
					$prefix="INSERT IGNORE INTO $tablename (zMD5,sitename,uri,ipaddr,hostname,zDate,uid,MAC,POURC) VALUES ".@implode(",", $f);
					$q->QUERY_SQL($prefix);
					if(!$q->ok){echo $q->mysql_error;}
					}
				}
				
			}
		
}

function WordScanners_v2(){

}

function WordScanners(){

	
	if(isset($GLOBALS["WordScanners_executed"])){return;}
	$GLOBALS["WordScanners_executed"]=true;
	$workdir="/var/log/artica-postfix/searchwords";
	@mkdir($workdir,0755,true);
	if($GLOBALS["VERBOSE"]){echo "Open $workdir\n";}
	$handle = opendir($workdir);
	
	if(!$handle){
		if($GLOBALS["VERBOSE"]){echo "Fatal unable to opendir $workdir\n";}
		events("Fatal unable to opendir $workdir",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	if($GLOBALS["VERBOSE"]){events("WordScanners(".__LINE__."):Open $workdir done.. instanciate mysql_squid_builder()");}
	$q=new mysql_squid_builder();
	if($GLOBALS["VERBOSE"]){events("WordScanners(".__LINE__."):Start looping()");}
	$FF=array();
	while (false !== ($filename = readdir($handle))) {
				if($filename=="."){continue;}
				if($filename==".."){continue;}
				$targetFile="$workdir/$filename";
				if($GLOBALS["VERBOSE"]){echo "Scanning $targetFile\n";}
				$searchWords=unserialize(@file_get_contents($targetFile));
				while (list ($key, $val) = each ($searchWords) ){
					$searchWords[$key]=addslashes(str_replace("'", "`", $val));
				}
				$zmd5=md5(serialize($searchWords));						
				$ipaddr=$searchWords["ipaddr"];
				$date=$searchWords["date"];
				$Time=strtotime($date);
				$uid=$searchWords["uid"];
				if(strlen($uid)<3){$uid=null;}
				$mac=trim($searchWords["mac"]);
				if($mac==null){$mac=GetMacFromIP($ipaddr);}
				$hostname=$searchWords["hostname"];	
				$words=$searchWords["WORDS"];
				if(trim($words)==null){@unlink($targetFile);continue;}
				$sitename=$searchWords["SITENAME"];
				$sitename=addslashes($sitename);
				if(!isset($GLOBALS["familysite"][$sitename])){$GLOBALS["familysite"][$sitename]=$q->GetFamilySites($sitename);}
				$familysite=$GLOBALS["familysite"][$sitename];
				if($mac<>null){if($uid==null){$uid=$q->UID_FROM_MAC($mac);}}
				if($ipaddr<>null){if($uid==null){$uid=$q->UID_FROM_IP($ipaddr);}}
				$FF[date("Ymdh",$Time)][]="('$zmd5','$sitename','$date','$ipaddr','$hostname','$uid','$mac','0','$familysite','$words')";
				@unlink($targetFile);
		}
		
		events("WordScanners(".__LINE__."): End looping...\n");
		
		
		$q=new mysql_squid_builder();
		while (list ($tablePrefix, $f) = each ($FF) ){
			if(count($f)>0){
				if($q->check_SearchWords_hour($tablePrefix)){
					$tablename="searchwords_$tablePrefix";
					$prefix="INSERT IGNORE INTO $tablename (`zmd5`,`sitename`,`zDate`,`ipaddr`,`hostname`,`uid`,`MAC`,`account`,`familysite`,`words`) VALUES ".@implode(",", $f);
					$q->QUERY_SQL($prefix);
					if(!$q->ok){
						writelogs_squid("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"stats");
						@mkdir("/var/log/artica-postfix/searchwords-sql-errors",0755,true);
						@file_put_contents("/var/log/artica-postfix/searchwords-sql-errors/".md5($prefix), $prefix);
					}
				}
			}			
		}
	
	
}
	
function ParseUserAuthNew(){

}
function ParseUserAuthNewDir($directory){return;}


function ParseUserAuth($checkpid=false){
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	if($checkpid){
		$pid=@file_get_contents($pidfile);
		if($pid<100){$pid=null;}
		
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			writelogs_squid("Already executed pid $pid since {$time}mn-> DIE");
			if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid since {$time}mn\n";}
			die();
		}
		
		@file_put_contents($pidfile, getmypid());

	}
	
	
	$sock=new sockets();
	if(isset($GLOBALS["EnableMacAddressFilter"])){
		$GLOBALS["EnableMacAddressFilter"]=$sock->GET_INFO("EnableMacAddressFilter");
		if(!is_numeric($GLOBALS["EnableMacAddressFilter"])){$GLOBALS["EnableMacAddressFilter"]=1;}
	}
	
	$hostname=$unix->hostname_g();
	$MustContinue=false;
	ParseUserAuthNew();
	
	if(function_exists("system_is_overloaded")){
		
		$COUNT_FILES=$unix->COUNT_FILES("/var/log/artica-postfix/squid-users");
		if($COUNT_FILES<1000){
			if(system_is_overloaded()){
				return;
			}
		}else{
			
			$MustContinue=true;
		}	
	}
	

		$countDeFiles=0;
		if (!$handle = opendir("/var/log/artica-postfix/squid-users")) {@mkdir("/var/log/artica-postfix/squid-users",0755,true);die();}
			if(!$MustContinue){
				if(systemMaxOverloaded()){
					return;
				}
			}
			
	
		$countDeFiles=0;
		$prefix="INSERT IGNORE INTO UserAutDB (zmd5,MAC,ipaddr,uid,hostname,UserAgent) VALUES ";
		$f=array();
		while (false !== ($filename = readdir($handle))) {
				if($filename=="."){continue;}
				if($filename==".."){continue;}
				$targetFile="/var/log/artica-postfix/squid-users/$filename";
				$countDeFiles++;
				$content=@file_get_contents($targetFile);
				$array=unserialize($content);
				$ParseUserAuthArray=ParseUserAuthArray($array);
				if($ParseUserAuthArray<>null){
					$f[]=$ParseUserAuthArray;
				}
				@unlink($targetFile);
		}
			
		if(count($f)>0){$q=new mysql_squid_builder();$q->QUERY_SQL($prefix.@implode(",", $f));}
		nmap_scan();
}

function nmap_scan(){
	if(isset($GLOBALS["nmap_scan_executed"])){return;}
	$unix=new unix();
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".time";
	$timeF=$unix->file_time_min($pidTime);
	if($timeF<10){
		$GLOBALS["nmap_scan_executed"]=true;
		return;
	}
	$sock=new sockets();
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	if($SquidPerformance>2){return;}
	
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$exec_nice=$unix->EXEC_NICE();
	$cmdNmap="$exec_nice $nohup $php5 ".dirname(__FILE__)."/exec.nmapscan.php --scan-squid >/dev/null 2>&1 &";
	@file_put_contents($pidTime, time());
	shell_exec($cmdNmap);
	$GLOBALS["nmap_scan_executed"]=true;
}


function ParseUserAuthArray($array){
	
	
	if(isset($GLOBALS["EnableMacAddressFilter"])){
		$sock=new sockets();
		$GLOBALS["EnableMacAddressFilter"]=$sock->GET_INFO("EnableMacAddressFilter");
		if(!is_numeric($GLOBALS["EnableMacAddressFilter"])){$GLOBALS["EnableMacAddressFilter"]=1;}
	}
	
	$hostname=trim($array["HOSTNAME"]);
	$hostname=str_replace("$", "", $hostname);
	
	if(strlen($array["IP"])<4){return;}
	if(strlen($hostname)<3){return;}
	$MAC=trim($array["MAC"]);
	if(!__IsPhysicalAddress($MAC)){$MAC=null;}
	
	if($MAC==null){
		if($GLOBALS["EnableMacAddressFilter"]==1){
			$array["MAC"]=GetMacFromIP($array["IP"]);
		}
	}
	
	$array["HOSTNAME"]=$hostname;
	$array["MD5"]=md5(serialize($array));
	while (list ($key, $value) = each ($array) ){$value=str_replace("'", "`", $value);$array[$key]=addslashes(trim($value));}
	
	return "('{$array["MD5"]}','{$array["MAC"]}','{$array["IP"]}','{$array["USER"]}','{$array["HOSTNAME"]}','{$array["USERAGENT"]}')";	
	
}


function useragents(){
	$f=array();
	$q=new mysql_squid_builder();	
	if(!$q->TABLE_EXISTS("UserAgents")){$q->CheckTables();}
	if(!$q->TABLE_EXISTS("UserAgents")){ufdbguard_admin_events("Fatal, UserAgents no such table", __FUNCTION__, __FILE__, __LINE__, "stats");return;}

if (!$handle = opendir("/var/log/artica-postfix/squid-userAgent")) {@mkdir("/var/log/artica-postfix/squid-userAgent",0755,true);die();}
$c=0;$countDeFiles=0;
while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$c++;
		$targetFile="/var/log/artica-postfix/squid-userAgent/$filename";
		$countDeFiles++;
		$pattern=trim(@file_get_contents($targetFile));
		if(strlen($pattern)<3){@unlink($targetFile);continue;}
		
		$pattern=addslashes($pattern);
		$f[]="('$pattern')";
		@unlink($targetFile);
}


	

	if(count($f)>0){
		$sql="INSERT IGNORE INTO UserAgents (pattern) VALUES ".@implode(",", $f);
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			ufdbguard_admin_events("Fatal for  ".count($f)." files, $q->mysql_error\n$sql", __FUNCTION__, __FILE__, __LINE__, "stats");
		}
	}
	events("ParseUserAuth():: FINISH.... $c files");
}


function ParseSquidLogMain_sql_toarray($filename){
	if(!isset($GLOBALS["CLASS_SQUID_TAIL"])){$GLOBALS["CLASS_SQUID_TAIL"]=new squid_tail();}
	$data=trim(@file_get_contents($filename));
	$q=new mysql_squid_builder();
	$array=explode(",", $data);
	while (list ($num, $val) = each ($array) ){
		$val=str_replace("'", "`", $val);
		$val=stripslashes($val);
		$val=mysql_escape_string2($val);
		$array[$num]=$val;
	}
	reset($array);
	$array[0]=str_replace("(", "", $array[0]);
	$array[13]=str_replace(")", "", $array[13]);
	
	$sitename=$array[0];
	$uri=$array[1];
	$TYPE=$array[2];
	$REASON=$array[3];
	$CLIENT=$array[4];
	$date=$array[5];
	$zMD5=$array[6];
	$site_IP=$array[7];
	$Country=$array[8];
	$size=$array[9];
	$username=$array[10];
	$cached=$array[11];
	$mac=$array[12];
	$hostname=$array[13];
	if(!is_numeric($cached)){return $data;}
	if($cached>1){return $data;}
	if(strlen($username)<3){$username=null;}
	
	if(!__IsPhysicalAddress($mac)){$mac=null;}
	if($mac=="00:00:00:00:00:00"){$mac=null;}
	if($mac==null){$mac=GetMacFromIP($CLIENT);}
	if($username=="-"){$username=null;}
	if($mac<>null){if($username==null){$username=$q->UID_FROM_MAC($mac);}}
	if($hostname==null){$hostname=$GLOBALS["CLASS_SQUID_TAIL"]->GetComputerName($CLIENT);}
	if($CLIENT<>null){if($username==null){$username=$q->UID_FROM_IP($CLIENT);}}
	
	if($username<>null){
		$GLOBALS["USERSCACHE"][$CLIENT]=$username;
		if($mac<>null){
			$GLOBALS["USERSCACHE"][$mac]=$username;
		}
	}else{
		if(isset($GLOBALS["USERSCACHE"][$CLIENT])){
			if($GLOBALS["USERSCACHE"][$CLIENT]<>null){$username=$GLOBALS["USERSCACHE"][$CLIENT];}
		}
		if($username==null){
			if(isset($GLOBALS["USERSCACHE"][$mac])){
				if($GLOBALS["USERSCACHE"][$mac]<>null){$username=$GLOBALS["USERSCACHE"][$mac];}
			}
		}
			
	}

	
	
	
	
	$sitename=addslashes($sitename);
	$uri=addslashes($uri);
	$line="('$sitename','$uri','$TYPE','$REASON','$CLIENT','$date','$zMD5','$site_IP','$Country','$size','$username','$cached','$mac','$hostname')";
	return $line;

}

function ParseSquidLogBrut($nopid=false){
	
	@mkdir("/var/log/artica-postfix/squid-brut",0777,true);
	@chmod("/var/log/artica-postfix/squid-brut",0777);
	
	@mkdir("/var/log/artica-postfix/squid-reverse",0777,true);
	@chmod("/var/log/artica-postfix/squid-reverse",0777);	
	
	$unix=new unix();
	$lockfile="/etc/artica-postfix/pids/".basename(__FILE__).".0.".__FUNCTION__.".lck";
	if($nopid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".0.".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($pid<100){$pid=null;}
	
	
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			events_brut("ParseSquidLogBrut:: Already executed pid $pid since {$time}mn-> DIE",__LINE__);
			if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid since {$time}mn\n";}
			die();
		}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	
	}
	if(!$GLOBALS["NOLOCK"]){
		if(is_file($lockfile)){
			$timelock=$unix->file_time_min($lockfile);
			if($timelock<60){
				events_brut("ParseSquidLogBrut:: $lockfile exists, aborting",__LINE__);
				return;
			}
		}
		@unlink($lockfile);
	}

	
	if(systemMaxOverloaded()){
		return;
	}
	
	
	$php5=$unix->LOCATE_PHP5_BIN();
	$nice=EXEC_NICE();
	$nohup=$unix->find_program("nohup");
	$pgrep=$unix->find_program("pgrep");
	$Forked=0;
	$CurrentSubDir=date("Y-m-d-H");
	$GLOBALDIRS=array();
	
	
	
	$Year=date("Y");
	$ALREADYPROCS=array();
	exec("$pgrep -l -f \"\-\-squid\-brut-proc $Year\" 2>&1",$pgrep_results);
	while (list ($index,$line) = each ($pgrep_results) ){
		if(preg_match("#pgrep#", $line)){continue;}
		if(!preg_match("#^([0-9]+)\s+#", $line,$re)){continue;}
		$ALREADYPROCS[$re[1]]=true;
		if(preg_match("#squid-brut-proc\s+([0-9\-]+)#", $line,$re)){$ALREADYDIR[$re[1]]=true;}
	}	
	
	
	$dirs=$unix->dirdir("/var/log/artica-postfix/squid-brut");
	while (list ($dir, $val) = each ($dirs) ){
		
		$basename=basename($dir);
		if($basename=="--verbose"){continue;}
		if(!preg_match("#[0-9]+-[0-9]+-[0-9]+-[0-9]+#", $basename)){
			events_brut("Directory: `$basename` NO MATCH, aborting");
			continue;
		}
		
		if(isset($ALREADYDIR[$basename])){
			events_brut("$basename currently processing...");
			continue;
		}
		
		if(system_is_overloaded()){
			if(count($GLOBALDIRS)>1){
				
				break;
			}
		}
		
		
		$filesCount=$unix->COUNT_FILES($dir);
		if($filesCount==0){
			events_brut("[$basename]: Removing: No files...");
			if($basename<>$CurrentSubDir){
				@rmdir($dir);
				if(is_dir($dir)){events_brut("Removing directory:$basename !!! FAILED !!!");}
				continue;
			}
		}
		
		$intBase=str_replace("-", "", $basename);
		$GLOBALDIRS[$intBase]=$dir;

	}
	
	ksort($GLOBALDIRS);
	$CountDeDirInQueue=count($GLOBALDIRS);
	
	if($CountDeDirInQueue>5){$MaxForked=2;}
	if($CountDeDirInQueue>10){$MaxForked=4;}
	if($CountDeDirInQueue>50){$MaxForked=6;}
	if($CountDeDirInQueue>100){$MaxForked=8;}
	

	
	
	
	if(system_is_overloaded()){
		
		$MaxForked=3;
	}
	

	
	$Forked=count($ALREADYPROCS);
	events_brut("$CountDeDirInQueue directories in queue, Fork $MaxForked processes Current=$Forked...");
	while (list ($val,$dir) = each ($GLOBALDIRS) ){
		
		
		if($Forked>$MaxForked){
			events_brut("Exit loop, MAX forked processes reached ( $Forked processes)");
			break;
		}
		$basename=basename($dir);
		
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".$basename.".ParseSquidLogBrutProcess.pid";
		$pid=@file_get_contents($pidfile);
		events_brut("[$val]: Directory:$basename [$pidfile] PID:$pid");
		
		
		if($unix->process_exists($pid,basename(__FILE__))){
			$MNS=$unix->PROCCESS_TIME_MIN($pid);
			events_brut("[$val]: $basename: Already process running pid: $pid since {$MNS}mn");
			$Forked++;
			continue;
		}
		
		events_brut("[$val]: -> ParseSquidLogMainProcessCount() For $basename");
		$Procs=ParseSquidLogMainProcessCount("squid-brut-proc",$basename);
		
		
		events_brut("[$val]: $Procs processe(s) Running");
		if($Procs>0){
			$MNS=$unix->PROCCESS_TIME_MIN($pid);
			events_brut("[$val]: $Procs processe(s) Already process running");
			$Forked++;
			continue;			
		}
		
		$cmd="$nohup $php5 ".__FILE__." --squid-brut-proc $basename >/dev/null 2>&1 &";
		events_brut("ParseSquidLogBrut:: $cmd",__LINE__);
		shell_exec($cmd);
		sleep(2);
		$Forked++;
		if(system_is_overloaded()){
			
			$MaxForked=3;
		}
		
	}
	
	
	
	$filesCount=$unix->COUNT_FILES("/var/log/artica-postfix/squid-brut");
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".ParseSquidLogBrutProcess.pid";
	$pid=@file_get_contents($pidfile);
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$MNS=$unix->PROCCESS_TIME_MIN($pid);
		events_brut("ParseSquidLogBrut:: NULL: $filesCount files Already process running pid: $pid since {$MNS}mn");
		
	}else{	
	
		$cmd="$nohup $php5 ".__FILE__." --squid-brut-proc >/dev/null 2>&1 &";
		events_brut("ParseSquidLogBrut:: $filesCount files $cmd",__LINE__);
		shell_exec($cmd);
	}
	
	$cmd="$nohup $php5 ".__FILE__." --squid >/dev/null 2>&1 &";
	events_brut("ParseSquidLogMain:: $cmd",__LINE__);
	shell_exec($cmd);
	CleanSquidQueues();

}


function CleanSquidQueues(){
}








function ParseSquidLogMain(){
	
	
	if(systemMaxOverloaded()){
		return;
	}
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		events("ParseSquidLogBrutProcess:: Already executed pid $pid since {$time}mn-> DIE",__LINE__);
		if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid since {$time}mn\n";}
		die();
	}	
	
	$WORKDIR="/var/log/artica-postfix/dansguardian-stats2";
	$dirs=$unix->dirdir($WORKDIR);
	$php5=$unix->LOCATE_PHP5_BIN();
	$nice=EXEC_NICE();
	$nohup=$unix->find_program("nohup");
	while (list ($dir, $val) = each ($dirs) ){
		
		$basename=basename($dir);
		if($basename=="--verbose"){continue;}
		$pidfile="/etc/artica-postfix/pids/squidMysqllogs.$basename.lock.pid";
		$pid=@file_get_contents($pidfile);
		$filesCount=$unix->COUNT_FILES($dir);
		events_brut("ParseSquidLogMain:: $filesCount files, $basename: $pidfile PID:$pid ");
		
		if($unix->process_exists($pid,basename(__FILE__))){
			$MNS=$unix->PROCCESS_TIME_MIN($pid);
			events_brut("ParseSquidLogMain:: $basename: $filesCount files, Already process running pid: $pid since {$MNS}mn");
			continue;
		}		
		
		$Procs=ParseSquidLogMainProcessCount("squid-sql-proc",$basename);
		if($Procs>0){
			$MNS=$unix->PROCCESS_TIME_MIN($pid);
			events_brut("ParseSquidLogMain:: $Procs processe(s) already in memory");
			continue;			
		}
		
		$cmd="$nohup $php5 ".__FILE__." --squid-sql-proc $basename >/dev/null 2>&1 &";
		events_brut("ParseSquidLogMain:: $filesCount files Fork for $basename sub-dir",__LINE__);
		shell_exec($cmd);
	
	}
	
	
	$filesCount=$unix->COUNT_FILES($WORKDIR);
	$pidfile="/etc/artica-postfix/pids/squidMysqllogs.lock.pid";
	$pid=@file_get_contents($pidfile);
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$MNS=$unix->PROCCESS_TIME_MIN($pid);
		events_brut("ParseSquidLogMain:: NULL: $filesCount files Already process running pid: $pid since {$MNS}mn");
		
	}else{
		$cmd="$nohup $php5 ".__FILE__." --squid-sql-proc >/dev/null 2>&1 &";
		events_brut("ParseSquidLogMain:: $filesCount files, Fork for NULL DIR",__LINE__);
		shell_exec($cmd);
	}

	


}

function ParseSquidLogMainProcessCount($token,$dir=null){
	$unix=new unix();
	$getmypid=getmypid();
	$f=array();
	$pend=null;
	if($dir==null){$pend="$";}
	$pgrep=$unix->find_program("pgrep");
	
	$cmd="$pgrep -l -f \"injector.php.*?$token.*?$dir\" 2>&1";
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	exec($cmd,$results);
	while (list ($key, $val) = each ($results) ){
		if(preg_match("#pgrep#", $val)){continue;}
		if($GLOBALS["VERBOSE"]){echo "Found $val\n";}
		if($dir==null){if(preg_match("#[0-9]+-[0-9]+-[0-9]+-[0-9]+#", $val)){continue;}}
		if(preg_match("#^([0-9]+)\s+#", $val,$re)){
			$pid=$re[1];
			if($pid==$getmypid){continue;}
			
			events_brut("ParseSquidLogMainProcessCount:: (token:$token/ dir:$dir) Found pid $pid",__LINE__);
			$f[]=$pid;
		}
	}
	return count($f);
}


 







function GetCountry($sitename){
	if(!isset($GLOBALS["IPs"])){$GLOBALS["IPs"]=array();}
	if(!isset($GLOBALS["COUNTRIES"])){$GLOBALS["COUNTRIES"]=array();}
	if(trim($GLOBALS["IPs"][$sitename])==null){
		$site_IP=trim(gethostbyname($sitename));
		$GLOBALS["IPs"][$sitename]=$site_IP;
	}else{
		$site_IP=$GLOBALS["IPs"][$sitename];
	}
	
	if(count($GLOBALS["IPs"])>5000){unset($GLOBALS["IPs"]);}
	if(count($GLOBALS["COUNTRIES"])>5000){unset($GLOBALS["COUNTRIES"]);}
	
	
	if(trim($GLOBALS["COUNTRIES"][$site_IP])==null){
		if(function_exists("geoip_record_by_name")){
			if($site_IP==null){$site_IP=$sitename;}
			$record = @geoip_record_by_name($site_IP);
			if ($record) {
				$Country=$record["country_name"];
				$Country=str_replace("'", "`", $Country);
				$GLOBALS["COUNTRIES"][$site_IP]=$Country;
			}
		}else{
			$geoerror="geoip_record_by_name no such function...";
		}
	}else{
		$Country=$GLOBALS["COUNTRIES"][$site_IP];
	}
	$Country=str_replace("'", "`", $Country);
	return $Country;
	
}

function ParseSquidLogMainError(){
	if(!is_dir("/var/log/artica-postfix/dansguardian-stats2-errors")){return ;}
	if (!$handle = opendir("/var/log/artica-postfix/dansguardian-stats2-errors")) {return;}
	$unix=new unix();
	$c=0;
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}	
		$c++;
		$targetFile="/var/log/artica-postfix/dansguardian-stats2-errors/$filename";
		$minutes=$unix->file_time_min($targetFile);
		events("ParseSquidLogMainError():: $filename {$minutes}Mn");
		if($minutes>2880){@unlink($targetFile);continue;}
		$array["TABLES"]=unserialize(@file_get_contents($targetFile));
		@unlink($targetFile);
		echo "Inject file $targetFile\n";
		events("Inject file $targetFile on Line: ".__LINE__);
		inject_array($array["TABLES"]);
		unset($array["TABLES"]);
	}
	events("ParseSquidLogMainError():: Done... ($c files)");
}


function youtube_array_to_sql($array){
	$q=new mysql_squid_builder();
	while (list ($key, $val) = each ($array) ){
		$val=str_replace("'", "`", $val);
		$val=mysql_escape_string2($val);
		$array[$key]=addslashes($val);
	}
	
	$VIDEOID=$array["VIDEOID"];
	$clientip=$array["clientip"];
	$username=$array["username"];
	$time=$array["time"];
	$mac=$array["mac"];
	$hostname=$array["hostname"];
	if($username=="-"){$username=null;}
	if(strlen($username)<3){$username=null;}
	if(!__IsPhysicalAddress($mac)){$mac=null;}
	
	
	if($mac==null){$mac=GetMacFromIP($clientip);}
	if($GLOBALS["VERBOSE"]){echo "$mac:: $VIDEOID -> \n";}	
	if(!youtube_infos($VIDEOID)){
		youtube_events("youtube_infos:: $VIDEOID -> FAILED",__LINE__);
	}
	$timeint=strtotime($time);
	$timeKey=date('YmdH',$timeint);
	$account=0;
	if($mac<>null){if($username==null){$username=$q->UID_FROM_MAC($mac);}}
	if($clientip<>null){if($username==null){$username=$q->UID_FROM_IP($clientip);}}
	
	
	youtube_events("$timeKey => ('$time','$clientip','$hostname','$username','$mac','$account','$VIDEOID')", __LINE__);
	return array($timeKey,"('$time','$clientip','$hostname','$username','$mac','$account','$VIDEOID')");
		
}


function youtube_next(){

	
}

function youtube_next_dir($dir){
	$unix=new unix();
	$countDefile=$unix->COUNT_FILES($dir);
	youtube_events("$dir -> $countDefile files on Line: ",__LINE__);
	if($countDefile==0){
		youtube_events("youtube_next_dir():: $dir: no files... remove... ",__LINE__);
		@rmdir($dir);
		return;
	}
	$FINAL=array();
	if (!$handle = opendir($dir)) {
		youtube_events("youtube_next_dir():: Fatal: $dir no such directory",__LINE__);
		ufdbguard_admin_events("Fatal: $dir no such directory",__FUNCTION__,__FILE__,__LINE__,"stats");
		return;
	}
	$c=0;
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$dir/$filename";
		$arrayFile=unserialize(@file_get_contents($targetFile));
		if(!is_array($arrayFile)){
			youtube_events("youtube_next_dir()::$targetFile not an array, aborting",__LINE__);
			@unlink($targetFile);
			continue;
		}
		
		if($GLOBALS["VERBOSE"]){print_r($arrayFile);}
		
		while (list ($index,$RTTSIZEARRAY) = each ($arrayFile) ){
			$NewArray=youtube_array_to_sql($RTTSIZEARRAY);
			if(!is_array($NewArray)){
				youtube_events("youtube_next_dir():: youtube_array_to_sql() return not an array for $targetFile",__LINE__);
				@unlink($targetFile);
				continue;
			}
			
			youtube_events("youtube_next_dir():: {$NewArray[0]} -> {$NewArray[1]}",__LINE__);
			$FINAL[$NewArray[0]][]=$NewArray[1];
				
		}
		$c++;
		@unlink($targetFile);
		
	
	}
	youtube_events("youtube_inject() ".count($FINAL)." elements for $c scanned files...",__LINE__);
	youtube_inject($FINAL);
	if($c>0){events("youtube_next_dir():: $c deleted files...");}
	
}



function youtube($Aspid=false){
	
	$unix=new unix();
	if($Aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".youtube.pid";
		$pid=@file_get_contents($pidfile);
		$mypid=getmypid();
		
		if($unix->process_exists($pid,basename(__FILE__))){
			if($pid<>$mypid){
				$time=$unix->PROCCESS_TIME_MIN($pid);
				events("Already executed pid $pid since {$time}mn-> DIE");
				if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid since {$time}mn\n";}
				return;
			}
		}
		@file_put_contents($pidfile,$mypid);
	}
	
	
	
	
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS("youtube_objects")){$q->CheckTables();}
	if(!$q->TABLE_EXISTS("youtube_objects")){echo "youtube_objects no such table\n";return;}
	
	$array_sql=array();
	
	@mkdir("/var/log/artica-postfix/youtube",0755,true);
	@mkdir("/var/log/artica-postfix/youtube-errors",0755,true);
	
	youtube_next();
	
	if (!$handle = opendir("/var/log/artica-postfix/youtube")) {return;}
	$c=0;
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}	
		$c++;
		$targetFile="/var/log/artica-postfix/youtube/$filename";
		$array=unserialize(@file_get_contents($targetFile));
		if($GLOBALS["VERBOSE"]){print_r($array);}
			
		$NewArray=youtube_array_to_sql($array);
		if(!is_array($NewArray)){
			youtube_events("$targetFile = not an array...",__LINE__);
			@unlink($targetFile);
			continue;
		}
		
		$GLOBALS["YOUTUBE"][$NewArray[0]]=$NewArray[1];
		
		}

	if(count($GLOBALS["YOUTUBE"])==0){
		youtube_events("GLOBALS[\"YOUTUBE\"] = 0 aborting...",__LINE__);
		if($GLOBALS["VERBOSE"]){
			echo "array_sql no rows...\n";
			return;
		}
		
	}
	youtube_inject($GLOBALS["YOUTUBE"]);
		
	events("youtube():: Done... ($c files)");
}

function youtube_events($text,$line=0){
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	if($GLOBALS["VERBOSE"]){echo $text."\n";}
	$common="/var/log/artica-postfix/youtube.inject.log";
	$size=@filesize($common);
	if($size>100000){@unlink($common);}
	$pid=getmypid();
	$date=date("Y-m-d H:i:s");
	$GLOBALS["CLASS_UNIX"]->events(basename(__FILE__)."$date $text");
	$h = @fopen($common, 'a');
	$sline="[$pid] $text";
	$line="$date [$pid] $text [Line:$line]\n";
	@fwrite($h,$line);
	@fclose($h);	
	
}


function youtube_inject($array){
	$q=new mysql_squid_builder();
	if(!is_array($array)){return;}
	if(count($array)==0){return;}
	youtube_events("youtube_inject() array of ".count($array)." elements...",__LINE__);
	
	while (list ($timeKey, $rows) = each ($array) ){
		if(count($rows)==0){continue;}
		$q->check_youtube_hour($timeKey);
		youtube_events("youtubehours_$timeKey = ".count($rows)." elements...",__LINE__);
		if(count($rows)==1){
			youtube_events("youtubehours_$timeKey = '".$rows[0]."'",__LINE__);
		}
		$suffix=trim(@implode(",", $rows));
		if($suffix==null){
			youtube_events("youtubehours_$timeKey = suffix = null, abort",__LINE__);
			continue;
		}
		
		$sql="INSERT INTO youtubehours_$timeKey (zDate,ipaddr,hostname,uid,MAC,account,youtubeid) VALUES $suffix";
		youtube_events($sql,__LINE__);
			
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			youtube_events("youtubehours_$timeKey = $q->mysql_error ",__LINE__);
			if($GLOBALS["VERBOSE"]){echo "**** $q->mysql_error **** \n";}
			ufdbguard_admin_events("$q->mysql_error", __FUNCTION__, __FILE__, __LINE__, 'youtube');
			@file_put_contents("/var/log/artica-postfix/youtube-errors/".md5($sql), $sql);
			return;
		}
	}	
	
}

function youtube_infos($VIDEOID){
	
	if(isset($GLOBALS["youtubeid"][$VIDEOID])){return true;}
	$uri="https://gdata.youtube.com/feeds/api/videos/$VIDEOID?v=2&alt=jsonc";
	if($GLOBALS["VERBOSE"]){echo "$VIDEOID:: $uri -> \n";}	
	$curl=new ccurl($uri);
	$error=null;
	if(!$curl->GetFile("/tmp/jsonc.inc")){
		youtube_events("gdata.youtube.com = Failed = > $curl->error",__LINE__);
		return false;
	}
	$infox=@file_get_contents("/tmp/jsonc.inc");
	$infos=json_decode($infox);
	$uploaded=$infos->data->uploaded;
	$title=$infos->data->title;	
	if($title==null){
		$error=$infos->error->message;
		if($error==null){
			if($GLOBALS["VERBOSE"]){echo "data->title NULL ($error)\n";var_dump($infos);}
			return false;
		}else{
			$title=$error;
		}
	}
	$category=$infos->data->category;
	if($category==null){
		if($error<>null){
			$category=$error;
		}
	}
	
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `youtubeid` FROM youtube_objects WHERE `youtubeid`='$VIDEOID'"));
	
	if(!$q->ok){
		if(strpos("youtube_objects' doesn't exist", $q->mysql_error)>0){
			
			$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `youtubeid` FROM youtube_objects WHERE `youtubeid`='$VIDEOID'"));
		}
		if($GLOBALS["VERBOSE"]){echo "$q->mysql_error\n";}
		return;
	}
	
	if($ligne["youtubeid"]<>null){
		$GLOBALS["youtubeid"][$VIDEOID]=true;
		if($GLOBALS["VERBOSE"]){echo "$VIDEOID already exists in table\n";}
		return true;
	}
	
	$tumbnail=$infos->data->thumbnail->sqDefault;
	$curl=new ccurl($tumbnail);
	$curl->GetFile("/tmp/thumbnail");
	$CATZ["Autos & Vehicles"]="automobile/cars";
	$CATZ["Film & Animation"]="movies";
	$CATZ["Gaming"]="games";
	$CATZ["Education"]="recreation/schools";
	$CATZ["Music"]="music";
	$CATZ["News & Politics"]="news";
	$CATZ["People & Blogs"]="hobby/pets";
	$CATZ["Science & Technology"]="sciences";
	$CATZ["Sports"]="recreation/sports";
	$CATZ["Travel & Events"]="recreation/travel";
	if(isset($CATZ[$category])){$category=$CATZ[$category];}
	
	$date=strtotime($uploaded);
	$zDate=date("Y-m-d H:i:s");
	$infox_enc=base64_encode($infox);
	$title=addslashes($title);
	$category=addslashes($category);
	$duration=$infos->data->duration;
	
	
	$thumbnail=addslashes(@file_get_contents("/tmp/thumbnail"));
	$sql="INSERT INTO youtube_objects (youtubeid,category,title,content,uploaded,duration,thumbnail) 
	VALUES('$VIDEOID','$category','$title','$infox_enc','$zDate','$duration','$thumbnail')";
	$q->QUERY_SQL($sql,"artica");
	if(!$q->ok){
		if(strpos("youtube_objects' doesn", " $q->mysql_error")>0){
			$q->CheckTables();
			$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `youtubeid` FROM youtube_objects WHERE `youtubeid`='$VIDEOID'"));
		}
	}	
	if(!$q->ok){
		if($GLOBALS["VERBOSE"]){echo "$q->mysql_error\n";}
		return false;}
	return true;
	
	
	
}



function unveiltech(){
	$deleteForce=false;
	if(is_file("/etc/artica-postfix/nounveicloud")){$deleteForce=true;}
	if(!is_dir("/var/log/artica-postfix/unveiltech")){return ;}
	if (!$handle = opendir("/var/log/artica-postfix/unveiltech")) {return;}
	$unix=new unix();
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}	
		$targetFile="/var/log/artica-postfix/unveiltech/$filename";
		$minutes=$unix->file_time_min($targetFile);
		if($minutes>2880){@unlink($targetFile);continue;}
		if(!$deleteForce){
		//	unveiltech_SendSite(@file_get_contents($targetFile));
		}
		@unlink($targetFile);
	}
	
}




function unveiltech_SendSite($www){
		return;
		if($www==null){return;}
		$ch = curl_init();
		$uris="http://api.unveiltech.com/articapushed.php?apikey=GfNDdlP7AKvwUtVI&url=$www";
		if($GLOBALS["VERBOSE"]){echo "$uris\n";}
		curl_setopt($ch, CURLOPT_URL, $uris);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla"); 
		curl_setopt($ch, CURLOPT_POST, FALSE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));   
		$data = curl_exec($ch);
		if(preg_match("#<STOP>OK</STOP>#is", $data)){@file_put_contents("/etc/artica-postfix/nounveicloud", time());}
		curl_close($ch);
}

function GetMacFromIP($ipaddr){
	
	if(!isset($GLOBALS["EnableMacAddressFilter"])){
		$sock=new sockets();
		$GLOBALS["EnableMacAddressFilter"]=$sock->GET_INFO("EnableMacAddressFilter");
		if(!is_numeric($GLOBALS["EnableMacAddressFilter"])){$GLOBALS["EnableMacAddressFilter"]=1;}
		
	}
	
	if($GLOBALS["EnableMacAddressFilter"]==0){return;}
	
	
		$ipaddr=trim($ipaddr);
		$ttl=date('YmdH');
		if(count($GLOBALS["CACHEARP"])>3){unset($GLOBALS["CACHEARP"]);}
		if(isset($GLOBALS["CACHEARP"][$ttl][$ipaddr])){return $GLOBALS["CACHEARP"][$ttl][$ipaddr];}
		
		if(!isset($GLOBALS["SBIN_ARP"])){$unix=new unix();$GLOBALS["SBIN_ARP"]=$unix->find_program("arp");}
		if(strlen($GLOBALS["SBIN_ARP"])<4){return;}
		
		if(!isset($GLOBALS["SBIN_PING"])){$unix=new unix();$GLOBALS["SBIN_PING"]=$unix->find_program("ping");}
		if(!isset($GLOBALS["SBIN_NOHUP"])){$unix=new unix();$GLOBALS["SBIN_NOHUP"]=$unix->find_program("nohup");}
		
		$cmd="{$GLOBALS["SBIN_ARP"]} -n \"$ipaddr\" 2>&1";
		exec("{$GLOBALS["SBIN_ARP"]} -n \"$ipaddr\" 2>&1",$results);
		while (list ($num, $line) = each ($results)){
			if(preg_match("#^[0-9\.]+\s+.+?\s+([0-9a-z\:]+)#", $line,$re)){
				if($re[1]=="no"){continue;}
				if(__IsPhysicalAddress($re[1])){
					$GLOBALS["CACHEARP"][$ttl][$ipaddr]=$re[1];
					return $GLOBALS["CACHEARP"][$ttl][$ipaddr];
				}
			}
			
		}
		
		if(!isset($GLOBALS["PINGEDHOSTS"][$ipaddr])){
			shell_exec("{$GLOBALS["SBIN_NOHUP"]} {$GLOBALS["SBIN_PING"]} $ipaddr -c 3 >/dev/null 2>&1 &");
			$GLOBALS["PINGEDHOSTS"][$ipaddr]=true;
		}
			
		$GLOBALS["CACHEARP"][$ttl][$ipaddr]=null;
	}

   function __IsPhysicalAddress($address){
   	
		$address=strtoupper(trim($address));
		if($address=="UNKNOWN"){return null;}
		if($address=="00:00:00:00:00:00"){return false;}
		$address=str_replace(":","-",$address);
		
		If(strlen($address) > 18){return false;}
		If($address == ""){return false;}
		If(!preg_match("#^[0-9A-Z]+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)$#i",$address)){
	
			return false;
		}
		$Array=explode("-",$address);
		If(strlen($Array[0]) != 2){return false;}
		If(strlen($Array[1]) != 2){return false;}
		If(strlen($Array[2]) != 2){return false;}
		If(strlen($Array[3]) != 2){return false;}
		If(strlen($Array[4]) != 2){return false;}
		If(strlen($Array[5]) != 2){return false;}
	
		return true;
	}

function inject_array($array){
	
	if($GLOBALS["EnableRemoteStatisticsAppliance"]==1){
		events("Injecting -> inject_array_remote() Load:{$GLOBALS["SYSTEM_INTERNAL_LOAD"]}: on Line: ".__LINE__);
		inject_array_remote($array);
		return;
	}
	
	$q=new mysql_squid_builder();
	$q->CheckTables();
	if($q->MysqlFailed){
		events_tail("squid-injector:: Mysql connection failed, aborting.... Line: ".__LINE__);
		inject_failed($array);
	}
	
	while (list ($table, $contentArray) = each ($array) ){
	//	
		$prefixsql="INSERT IGNORE INTO $table (`sitename`,`uri`,`TYPE`,`REASON`,`CLIENT`,`zDate`,`zMD5`,`remote_ip`,`country`,`QuerySize`,`uid`,`cached`,`MAC`,`hostname`) VALUES ";
		$sql="$prefixsql".@implode(",",$contentArray);
		if($GLOBALS["VERBOSE"]){echo $sql."\n";}
		events("inject_array::Injecting -> table `$table` ".count($contentArray)." rows in line:".__LINE__);
		$q->QUERY_SQL($sql);

		if(!$q->ok){
			events("Fatal !!! inject_array::Injecting -> ERROR: $q->mysql_error : in line:".__LINE__);
			inject_failed($array);
			return;
		}
	}	
	
	
}




function inject_array_remote($array){
	$sock=new sockets();
	$ArticaHttpsPort=$sock->GET_INFO("ArticaHttpsPort");
	if(!is_numeric($ArticaHttpsPort)){$ArticaHttpsPort=9000;}
	
	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
	if($GLOBALS["REMOTE_SSL"]==1){$refix="https";}else{$refix="http";}
	$uri="$refix://{$GLOBALS["REMOTE_SSERVER"]}:{$GLOBALS["REMOTE_SPORT"]}/squid.stats.listener.php";
	events("Injecting -> $uri on line:".__LINE__);
	$curl=new ccurl($uri,true);
	$f=base64_encode(serialize($array));
	$curl->parms["STATS_LINE"]=$f;
	$curl->parms["MYSSLPORT"]=$ArticaHttpsPort;
	if(!$curl->get()){
		inject_failed($array);
		events("Injecting -> FAILED ".$curl->error." on line:".__LINE__);
		echo "FAILED ".$curl->error."\n";
		return;
	}
	
	if(preg_match("#<ANSWER>OK</ANSWER>#is",$curl->data)){return true;}	
	events("Injecting -> FAILED ".$curl->data." on line:".__LINE__);
	echo "FAILED ".$curl->data."\n";
	inject_failed($array);
}

function inject_failed($array){
	if(!is_dir("/var/log/artica-postfix/dansguardian-stats2-errors")){@mkdir("/var/log/artica-postfix/dansguardian-stats2-errors",0755,true);}
	$serialized=serialize($array);
	events("Fatal !!! save into /var/log/artica-postfix/dansguardian-stats2-errors in line:".__LINE__);
	@file_put_contents("/var/log/artica-postfix/dansguardian-stats2-errors/".md5($serialized),$serialized);
	
}
function events($text,$line=0){
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}
		
		if($line>0){$sourceline=$line;}
		$text="$text ($sourcefunction::$sourceline)";
	}
	
	
	events_tail($text);
}

function events_tail($text){
		if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
		//if($GLOBALS["VERBOSE"]){echo "$text\n";}
		$pid=@getmypid();
		$date=@date("H:i:s");
		$logFile="/var/log/artica-postfix/auth-tail.debug";
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		$GLOBALS["CLASS_UNIX"]->events(basename(__FILE__)." $date $text");
		@fwrite($f, "$pid ".basename(__FILE__)." $date $text\n");
		@fclose($f);	
		}	
function events_brut($text){
	
	$cmdlines=$GLOBALS["PARSED_COMMANDS"];
	if(function_exists("debug_backtrace")){
	$trace=@debug_backtrace();
	if(isset($trace[1])){
		$function="{$trace[1]["function"]}()";
		$line="{$trace[1]["line"]}";
		
	}
	}
	
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	$pid=@getmypid();
	$logFile="/var/log/artica-postfix/squid-brut.debug";
	//events($text,$logFile=null,$phplog=false,$sourcefunction=null,$sourceline=null)
	$GLOBALS["CLASS_UNIX"]->events(basename(__FILE__)." $text",$logFile,false,$function,$line,basename(__FILE__));
}	

		
function tables_status(){
	$q=new mysql_squid_builder();
	print_r($q->TABLE_STATUS("UserAutDB"));
	
}
