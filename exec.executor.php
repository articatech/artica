<?php
$GLOBALS["VERBOSE"]=false;

if(preg_match("#--verbose#",implode(" ",$argv))){
	$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;
	ini_set('html_errors',0);ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	print "Starting......: ".date("H:i:s")." artica-executor debug mode\n";
}



if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if($GLOBALS["VERBOSE"]){print "Starting......: ".date("H:i:s")." artica-executor instantiate classes\n";}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
if($GLOBALS["VERBOSE"]){print "Starting......: ".date("H:i:s")." artica-executor instantiate class frame.class.inc\n";}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
if($GLOBALS["VERBOSE"]){print "Starting......: ".date("H:i:s")." artica-executor instantiate class class.os.system.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
if($GLOBALS["VERBOSE"]){print "Starting......: ".date("H:i:s")." artica-executor instantiate class class.system.network.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
if($GLOBALS["VERBOSE"]){print "Starting......: ".date("H:i:s")." artica-executor instantiate class framework/class.settings.inc\n";}
include_once(dirname(__FILE__)."/framework/class.settings.inc");
if($GLOBALS["VERBOSE"]){print "Starting......: ".date("H:i:s")." artica-executor instantiate instantiate classes done...\n";}
$GLOBALS["EXEC_PID_FILE"]="/etc/artica-postfix/".basename(__FILE__).".daemon.pid";

if($GLOBALS["VERBOSE"]){print "Starting......: ".date("H:i:s")." artica-executor pid file:{$GLOBALS["EXEC_PID_FILE"]}\n";}
$unix=new unix();
if($GLOBALS["VERBOSE"]){print "Starting......: ".date("H:i:s")." artica-executor checking {$GLOBALS["EXEC_PID_FILE"]}\n";}
if($unix->process_exists(@file_get_contents($GLOBALS["EXEC_PID_FILE"]))){
	print "Starting......: ".date("H:i:s")." artica-executor Already executed pid ". @file_get_contents($GLOBALS["EXEC_PID_FILE"])."...\n";
	die();
}

if(is_file("/etc/artica-postfix/FROM_ISO")){
	if($unix->file_time_min("/etc/artica-postfix/FROM_ISO")<1){return;}
}


print "Starting......: ".date("H:i:s")." artica-executor filling memory\n";
FillMemory();
if($argv[1]=='--mails-archives'){mailarchives();die();}
if($argv[1]=='--stats-console'){stats_console();die();}

if($argv[1]=='--all'){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/".basename(__FILE__).".pid";
	$pidtime="/etc/artica-postfix/".basename(__FILE__).".time";
	if($unix->file_time_min($pidtime)<3){die();}
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$ProcessTime=$unix->PROCCESS_TIME_MIN($pid);
		events("Process $pid  already in memory since $ProcessTime minutes","MAIN",__LINE__);
		die();
	}
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	@file_put_contents($pidfile, getmypid());
	launch_all_status();die();
}

if(preg_match("#--(.+)#", $argv[1],$re)){if(function_exists($re[1])){events("Execute {$re[1]}() -> \"{$argv[1]}\"" ,"MAIN");call_user_func($re[1]);die();}}


if($argv[1]<>null){events("Unable to understand ". implode(" ",$argv),"MAIN",__LINE__);die();}

$nofork=false;
if(!function_exists("pcntl_signal")){$nofork=true;}
if($GLOBALS["TOTAL_MEMORY_MB"]<400){$nofork=true;}
$MEMORY=$unix->MEM_TOTAL_INSTALLEE();
if($MEMORY<624288){$nofork=true;}

if($nofork){
	print "Starting......: ".date("H:i:s")." artica-status pcntl_fork module not loaded !\n";
	$pidfile="/etc/artica-postfix/".basename(__FILE__).".pid";
	
	
	$childpid=posix_getpid();
	@file_put_contents($pidfile,$childpid);	
	
	$timefile="/etc/artica-postfix/".basename(__FILE__).".time";
	if(file_time_min($timefile)>1){
		@unlink($timefile);
		launch_all_status();
		@file_put_contents($timefile,"#");
	}
	
	die();
	
}


if(!$nofork){
	pcntl_signal(SIGTERM,'sig_handler');
	pcntl_signal(SIGINT, 'sig_handler');
	pcntl_signal(SIGCHLD,'sig_handler');
	pcntl_signal(SIGHUP, 'sig_handler');
}else{
	print "Starting......: ".date("H:i:s")." artica-executor undefined function \"pcntl_signal\"\n";
}

set_time_limit(0);
ob_implicit_flush();
declare(ticks = 1);
$stop_server=false;
$reload=false;
$pid=pcntl_fork();


	if ($pid == -1) {
	     die("Starting......: ".date("H:i:s")." artica-executor fork() call asploded!\n");
	} else if ($pid) {
	     print "Starting......: ".date("H:i:s")." artica-executor fork()ed successfully.\n";
	     die();
	}

	
	$childpid=posix_getpid();
	@file_put_contents($GLOBALS["EXEC_PID_FILE"],$childpid);
	FillMemory();
	
	$renice_bin=$unix->find_program("renice");
	if(is_file($renice_bin)){
		events("$renice_bin 19 $childpid",__FUNCTION__,__LINE__);
		shell_exec("$renice_bin 19 $childpid &");
	}
	$GLOBALS["CLASS_SOCKETS"]=new sockets();
	$GLOBALS["CLASS_USERS"]=new settings_inc();
	$GLOBALS["CLASS_UNIX"]=new unix();	
	
	while ($stop_server==false) {
		
		sleep(3);
		launch_all_status();
		if($reload){
			$reload=false;
			events("reload daemon",__FUNCTION__,__LINE__);
			FillMemory();			
		}
	}
	

function sig_handler($signo) {
    global $stop_server;
    global $reload;
    switch($signo) {
        case SIGTERM: {$stop_server = true;break;}        
        case 1: {$reload=true;}
        default: {
        	if($signo<>17){events("Receive sig_handler $signo",__FUNCTION__,__LINE__);}
        }
    }
}


function FillMemory(){
	$unix=new unix();
	$GLOBALS["TIME"]=unserialize(@file_get_contents("/etc/artica-postfix/pids/".basename(__FILE__).".GLOBALS"));
	
	
	if(GET_INFO_DAEMON("cpuLimitEnabled")==1){$GLOBALS["cpuLimitEnabled"]=true;}else{$GLOBALS["cpuLimitEnabled"]=false;}
	$_GET["NICE"]=$unix->EXEC_NICE();
	$GLOBALS["EXEC_NICE"]=$_GET["NICE"];
	$GLOBALS["PHP5"]=$unix->LOCATE_PHP5_BIN();
	$GLOBALS["SU"]=$unix->find_program("su");
	$GLOBALS["NOHUP"]=$unix->find_program("nohup");
	
	$users=new settings_inc();
	$sock=new sockets();
	$DisableArticaStatusService=$sock->GET_INFO("DisableArticaStatusService");
	$EnableArticaExecutor=$sock->GET_INFO("EnableArticaExecutor");
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableArticaBackground=$sock->GET_INFO("EnableArticaBackground");

	$SambaEnabled=$sock->GET_INFO("SambaEnabled");
	if(!is_numeric($SambaEnabled)){$SambaEnabled=1;}
	if($SambaEnabled==0){$users->SAMBA_INSTALLED=false;}	
	
	if(!is_numeric($DisableArticaStatusService)){$DisableArticaStatusService=0;}
	if(!is_numeric($EnableArticaExecutor)){$EnableArticaExecutor=1;}
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=1;}
	if(!is_numeric($EnableArticaBackground)){$EnableArticaBackground=1;}
	
	$GLOBALS["KAV4PROXY_INSTALLED"]=false;
	$GLOBALS["AS_WEB_STATISTICS_APPLIANCE"]=$EnableWebProxyStatsAppliance;
	$GLOBALS["EnableArticaBackground"]=true;
	if($EnableArticaBackground==0){$GLOBALS["EnableArticaBackground"]=false;}
	$GLOBALS["SPAMASSASSIN_INSTALLED"]=$users->spamassassin_installed;
	$GLOBALS["ARTICA_STATUS_DISABLED"]=$DisableArticaStatusService;
	$GLOBALS["EXECUTOR_DAEMON_ENABLED"]=$EnableArticaExecutor;
	$GLOBALS["SQUID_INSTALLED"]=$users->SQUID_INSTALLED;
	$GLOBALS["KAV4PROXY_INSTALLED"]=$users->KAV4PROXY_INSTALLED;
	$GLOBALS["MILTER_GREYLIST_INSTALLED"]=$users->MILTERGREYLIST_INSTALLED;
	$GLOBALS["POSTFIX_INSTALLED"]=$users->POSTFIX_INSTALLED;
	$GLOBALS["SAMBA_INSTALLED"]=$users->SAMBA_INSTALLED;
	$GLOBALS["GREYHOLE_INSTALLED"]=$users->GREYHOLE_INSTALLED;
	$GLOBALS["MUNIN_CLIENT_INSTALLED"]=$users->SAMBA_INSTALLED;
	$GLOBALS["CYRUS_IMAP_INSTALLED"]=$users->cyrus_imapd_installed;
	$_GET["MIME_DEFANGINSTALLED"]=$users->MIMEDEFANG_INSTALLED;
	$GLOBALS["DANSGUARDIAN_INSTALLED"]=$users->DANSGUARDIAN_INSTALLED;
	$GLOBALS["OPENVPN_INSTALLED"]=$users->OPENVPN_INSTALLED;
	$GLOBALS["OCS_INSTALLED"]=$users->OCSI_INSTALLED;
	$GLOBALS["UFDBGUARD_INSTALLED"]=$users->APP_UFDBGUARD_INSTALLED;
	$GLOBALS["KAS_INSTALLED"]=$users->kas_installed;
	$GLOBALS["ZARAFA_INSTALLED"]=$users->ZARAFA_INSTALLED;
	$GLOBALS["XAPIAN_PHP_INSTALLED"]=$users->XAPIAN_PHP_INSTALLED;
	$GLOBALS["AUDITD_INSTALLED"]=$users->APP_AUDITD_INSTALLED;
	$GLOBALS["VIRTUALBOX_INSTALLED"]=$users->VIRTUALBOX_INSTALLED;
	$GLOBALS["DRUPAL7_INSTALLED"]=$users->DRUPAL7_INSTALLED;
	$GLOBALS["CGROUPS_INSTALLED"]=$users->CGROUPS_INSTALLED;
	$GLOBALS["NMAP_INSTALLED"]=$users->nmap_installed;

	if($GLOBALS["VERBOSE"]){writelogs("DANSGUARDIAN_INSTALLED={$GLOBALS["DANSGUARDIAN_INSTALLED"]}","MAIN",__FILE__,__LINE__);}
	$GLOBALS["EnableArticaWatchDog"]=GET_INFO_DAEMON("EnableArticaWatchDog");
	if($GLOBALS["VERBOSE"]){if($GLOBALS["POSTFIX_INSTALLED"]){events("Postfix is installed...");}}
	if($GLOBALS["VERBOSE"]){events("Nice=\"\", php5 {$GLOBALS["PHP5"]}",__FUNCTION__,__LINE__);}	
	$GLOBALS["EnableInterfaceMailCampaigns"]=$sock->GET_INFO("EnableInterfaceMailCampaigns");
	$GLOBALS["CLASS_SOCKETS"]=$sock;
	$GLOBALS["TOTAL_MEMORY_MB"]=$unix->TOTAL_MEMORY_MB();
	
	if(!$GLOBALS["KAV4PROXY_INSTALLED"]){if(is_file("/etc/artica-postfix/KASPERSKY_WEB_APPLIANCE")){$GLOBALS["KAV4PROXY_INSTALLED"]=true;}}
	
	$sock=null;
	$unix=null;
	$users=null;
	
	
	}
	
function watchdog_artica_status(){
	if(is_file("/var/log/artica-postfix/status-daemon.log")){
		$time=file_time_min("/var/log/artica-postfix/status-daemon.log");
		if($time>5){
			events("artica-status seems freeze, restart daemon",__FUNCTION__,__LINE__);
			sys_THREAD_COMMAND_SET("/etc/init.d/artica-status reload");
			@unlink("/var/log/artica-postfix/status-daemon.log");
			events("done...",__FUNCTION__,__LINE__);
		}
	}
	
}	






die();

function stats_console(){
	$array[]="exec.admin.smtp.flow.status.php";
	$array[]="exec.postfix.iptables.php";
	
	
	while (list ($index, $file) = each ($array) ){
		$cmd="{$GLOBALS["PHP5"]} /usr/share/artica-postfix/$file";
		$GLOBALS["CMDS"][]=$cmd;
	}	
		
}

function launch_all_status(){
	$functions=array("group5","group10","group30s","group10s","group0","group2","group300","group120","group30","group60mn","group5h","group24h","watchdog_artica_status");
	$system_is_overloaded=system_is_overloaded();
	$systemMaxOverloaded=systemMaxOverloaded();
	FillMemory();
	
	while (list ($num, $func) = each ($functions) ){
		if($system_is_overloaded){
				events("System is overloaded: ({$GLOBALS["SYSTEM_INTERNAL_LOAD"]}}, pause 10 seconds",__FUNCTION__,__LINE__);
				sleep(10);
				continue;
			}else{
				if($systemMaxOverloaded){
					events("System is very overloaded, pause stop",__FUNCTION__,__LINE__);
					return;
					continue;
				}
			}
			
			
			sleep(1); 
			call_user_func($func);
	}
	$already=array();
	$AlreadyTests=array();
	if(count($GLOBALS["CMDS"])>0){
		events("scheduling ".count($GLOBALS["CMDS"])." commands",__FUNCTION__,__LINE__);
		$FileDataCommand=@file_get_contents('/etc/artica-postfix/background');
  		$tbl=explode("\n",$FileDataCommand);
  		while (list ($num, $zcommands) = each ($GLOBALS["CMDS"]) ){
			if(trim($zcommands)==null){continue;}
			
	  		if(preg_match("#^(.+?)\s+#",$zcommands,$re)){
	  			if(!$AlreadyTests[$fileTests]){
					$fileTests=trim("{$re[1]}");
					if(!is_file($fileTests)){
						events("running $fileTests No such file",__FUNCTION__,__LINE__);
						continue;
					}else{
						$AlreadyTests[$fileTests]=true;
					}
	  			}
			}
			
			
			if(!$already[$zcommands]){
				$tbl[]=$zcommands;
				$already[$zcommands]=true;
			}
  		}
  		
  		
		@file_put_contents('/etc/artica-postfix/background',implode("\n",$tbl));  
		unset($GLOBALS["CMDS"]);		
		$mem=round(((memory_get_usage()/1024)/1000),2);
		if($GLOBALS["EnableArticaBackground"]){$EnableArticaBackground="Has daemon mode...";}else{$EnableArticaBackground="Has cmdline mode...";}
		
		
		events("Saving /etc/artica-postfix/background done... Memory of this computer={$GLOBALS["TOTAL_MEMORY_MB"]}M Process memory at the end=$mem Mb EnableArticaBackground=`$EnableArticaBackground`",__FUNCTION__,__LINE__);
		
		
		
		
		
	}
	
	@file_put_contents("/etc/artica-postfix/pids/".basename(__FILE__).".GLOBALS",serialize($GLOBALS["TIME"]));
	
	
}



// sans vérifications, toutes les 5 minutes
function group5(){
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	$filetime="/etc/artica-postfix/pids/".md5(__FILE__.__FUNCTION__).".time";
	$time=$GLOBALS["CLASS_UNIX"]->file_time_min($filetime);
	if($time<6){return;}
	@unlink($filetime);
	@file_put_contents($filetime, time());	
			
	$unix=new unix();
	if($GLOBALS["POSTFIX_INSTALLED"]){
		$array["exec.watchdog.postfix.queue.php"]="exec.watchdog.postfix.queue.php";
		$array["exec.postfix.iptables.php"]="exec.postfix.iptables.php --parse-queue";
		$array["exec.postfix.iptables.php --export-drop"]="exec.postfix.iptables.php --export-drop";
		$array["exec.smtp-senderadv.php"]="exec.smtp-senderadv.php";
	}
	
	
	if($GLOBALS["VIRTUALBOX_INSTALLED"]){$array["exec.virtualbox.php --maintenance"]="exec.virtualbox.php --maintenance";}
	if($GLOBALS["KAV4PROXY_INSTALLED"]){$array["exec.kaspersky-update-logs.php --av-uris"];}
	
	


	
	if($GLOBALS["OPENVPN_INSTALLED"]){
		$array["exec.openvpn.php --schedule"]="exec.openvpn.php --schedule";
	}
	
	
	
	if($GLOBALS["SQUID_INSTALLED"]){
		$array["exec.squid.logs.migrate.php"]="exec.squid.logs.migrate.php";
	}

	if($GLOBALS["SAMBA_INSTALLED"]){
		$array["exec.samba.php --smbtree"]="exec.samba.php --smbtree";
		$array["exec.samba.php --smbstatus"]="exec.samba.php --smbstatus";
			
	}
	
	
	if(is_file("/usr/sbin/glusterfsd")){
		$array["exec.gluster.php"]="exec.gluster.php --notify-server";
	}
	
	if($GLOBALS["EnableArticaWatchDog"]==1){
		$array2[]="artica-install --start-minimum-daemons";
	}
	
	if($GLOBALS["POSTFIX_INSTALLED"]){
		if($GLOBALS["KAS_INSTALLED"]){
			$array2[]="artica-update --kas3";
		}
		
	}
	

	
	$array2[]="artica-install --generate-status";
	
	if($GLOBALS["OVERLOADED"]){
		
		unset($array["exec.admin.status.postfix.flow.php"]);
		
		
		unset($array["exec.admin.smtp.flow.status.php"]);
	}
	
	
	while (list ($index, $file) = each ($array) ){
		$cmd="{$GLOBALS["PHP5"]} /usr/share/artica-postfix/$file";
		events("schedule $cmd",__FUNCTION__,__LINE__);
		$GLOBALS["CMDS"][]=$cmd;
	}
	
	if($GLOBALS["POSTFIX_INSTALLED"]){
		mailarchives();
	}
	
	if(is_array($array2)){
	while (list ($index, $file) = each ($array2) ){
		$cmd="/usr/share/artica-postfix/bin/$file";
		events("schedule $cmd",__FUNCTION__,__LINE__);
		$GLOBALS["CMDS"][]=$cmd;
	}}		
	@file_put_contents("/etc/artica-postfix/pids/".basename(__FILE__).".GLOBALS",serialize($GLOBALS["TIME"]));
}
//sans vérifications toutes les 30mn
function group30(){
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	$filetime="/etc/artica-postfix/pids/".md5(__FILE__.__FUNCTION__).".time";
	$time=$GLOBALS["CLASS_UNIX"]->file_time_min($filetime);
	if($time<31){return;}
	@unlink($filetime);
	@file_put_contents($filetime, time());
	
	$array["exec.activedirectory-import.php"];
	
	

	if($GLOBALS["SAMBA_INSTALLED"]){
		$array[]="exec.picasa.php";
		$array[]="exec.samba.php --ScanTrashs";
	
	
	}
	

		
	if($GLOBALS["DRUPAL7_INSTALLED"]){$array[]="exec.freeweb.php --drupal-cron";}

	
	
	
	
	$array[]="exec.emerging.threats.php";
	$array[]="exec.my-rbl.check.php --checks";
	$array[]="exec.clamavsig.php";
	
	
	while (list ($index, $file) = each ($array) ){
		$cmd="{$GLOBALS["PHP5"]} /usr/share/artica-postfix/$file";
		events("schedule $cmd",__FUNCTION__,__LINE__);
		$GLOBALS["CMDS"][]=$cmd;
	}	
	@file_put_contents("/etc/artica-postfix/pids/".basename(__FILE__).".GLOBALS",serialize($GLOBALS["TIME"]));
}


//sans vérifications toutes les 10mn
function group10(){
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	$filetime="/etc/artica-postfix/pids/".md5(__FILE__.__FUNCTION__).".time";
	$time=$GLOBALS["CLASS_UNIX"]->file_time_min($filetime);
	if($time<11){return;}
	@unlink($filetime);
	@file_put_contents($filetime, time());
	
	
	$EnablePhileSight=GET_INFO_DAEMON("EnablePhileSight");
	if($EnablePhileSight==null){$EnablePhileSight=1;}
	
	

	
	if($GLOBALS["OCS_INSTALLED"]){$array[]="exec.ocsweb.php --injection";	}
	if($GLOBALS["AUDITD_INSTALLED"]){$array[]="exec.auditd.php --import";}
	
	if($GLOBALS["EnableArticaWatchDog"]==1){$array2[]="artica-install --startall";}
	if($GLOBALS["ZARAFA_INSTALLED"]){$array[]="exec.zarafa.adbookldap.php --all";	}

	
	
	$array[]="exec.test-connection.php";
	$array[]="exec.kaspersky-update-logs.php";
	$array[]="exec.emailrelay.php --notifier-queue";
	$array[]="exec.watchdog.php --queues";
	$array[]="exec.freeweb.php --perms";
	$array[]="exec.freeweb.php --all-status";
	$array[]="exec.patchs.php";
	
	if($GLOBALS["UFDBGUARD_INSTALLED"]){$array[]="exec.web-community-filter.php --groupby";}

	
	$array2[]="artica-install --check-virus-logs";
	$array2[]="artica-install --monit-check";
	
	
	
	while (list ($index, $file) = each ($array) ){
		$cmd="{$GLOBALS["PHP5"]} /usr/share/artica-postfix/$file";
		events("schedule $cmd",__FUNCTION__,__LINE__);
		$GLOBALS["CMDS"][]=$cmd;
	}

	while (list ($index, $file) = each ($array2) ){
		
		$cmd="/usr/share/artica-postfix/bin/$file";
		
		events("schedule $cmd",__FUNCTION__,__LINE__);
		$GLOBALS["CMDS"][]=$cmd;
	}

	if($GLOBALS["MUNIN_CLIENT_INSTALLED"]){
		$GLOBALS["CMDS"][]="{$GLOBALS["SU"]} - munin --shell=/bin/bash munin-cron";
	}
	@file_put_contents("/etc/artica-postfix/pids/".basename(__FILE__).".GLOBALS",serialize($GLOBALS["TIME"]));
	
}

//toutes les minutes
function group0(){
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	$filetime="/etc/artica-postfix/pids/".md5(__FILE__.__FUNCTION__).".time";
	$time=$GLOBALS["CLASS_UNIX"]->file_time_min($filetime);
	if($time<2){return;}
	@unlink($filetime);
	@file_put_contents($filetime, time());
	
	

	events("Starting {$GLOBALS["TIME"]["GROUP0"]} 1mn",__FUNCTION__,__LINE__);
	$GLOBALS["TIME"]["GROUP0"]=time();

	if($GLOBALS["POSTFIX_INSTALLED"]){
		$array[]="exec.whiteblack.php";
		
	}
	

	if(is_array($array)){
		while (list ($index, $file) = each ($array) ){
			if(system_is_overloaded()){events(__FUNCTION__. ":: die, overloaded");die();}
			$cmd="{$GLOBALS["PHP5"]} /usr/share/artica-postfix/$file";
			events("schedule $cmd",__FUNCTION__,__LINE__);
			$GLOBALS["CMDS"][]=$cmd;
		}
	}

	@file_put_contents("/etc/artica-postfix/pids/".basename(__FILE__).".GLOBALS",serialize($GLOBALS["TIME"]));

}
//toutes les 2 minutes
function group2(){
	
	
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	$filetime="/etc/artica-postfix/pids/".md5(__FILE__.__FUNCTION__).".time";
	$time=$GLOBALS["CLASS_UNIX"]->file_time_min($filetime);
	if($time<3){return;}
	@unlink($filetime);
	@file_put_contents($filetime, time());
	
	if(!is_file("/usr/share/artica-postfix/ressources/usb.scan.inc")){$array2[]="artica-install --usb-scan-write";}
	
	
	
	
	if($GLOBALS["POSTFIX_INSTALLED"]){
		$array[]="exec.mailbackup.php";

	}
	

	if($GLOBALS["OCSI_INSTALLED"]){$array[]="exec.remote-agent-install.php";}
	if(!function_exists("pcntl_fork")){$array[]="exec.status.php";}
	if($GLOBALS["CYRUS_IMAP_INSTALLED"]){$array[]="exec.cyrus-restore.php --ad-sync";}
	
	
	if(is_array($array)){
		while (list ($index, $file) = each ($array) ){
			$cmd="{$GLOBALS["PHP5"]} /usr/share/artica-postfix/$file";
			events("schedule $cmd",__FUNCTION__,__LINE__);
			$GLOBALS["CMDS"][]=$cmd;
		}	
	}
	
	
	if(is_array($array2)){
		while (list ($index, $file) = each ($array2) ){
			$cmd="/usr/share/artica-postfix/bin/$file";
			events("schedule $cmd",__FUNCTION__,__LINE__);
			$GLOBALS["CMDS"][]=$cmd;
		}	
	}
@file_put_contents("/etc/artica-postfix/pids/".basename(__FILE__).".GLOBALS",serialize($GLOBALS["TIME"]));
	
}

function group10s(){}
function group30s(){}

//5H
function group5h(){
	$array=array();
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	$filetime="/etc/artica-postfix/pids/".md5(__FILE__.__FUNCTION__).".time";
	$time=$GLOBALS["CLASS_UNIX"]->file_time_min($filetime);
	if($time<301){return;}
	@unlink($filetime);
	@file_put_contents($filetime, time());

	
	
	
	if($GLOBALS["POSTFIX_INSTALLED"]){$array[]="exec.postfix.iptables.php --parse-sql";}
	
	while (list ($index, $file) = each ($array) ){
		$cmd="{$GLOBALS["PHP5"]} /usr/share/artica-postfix/$file";
		events("schedule $cmd Minutes=$mins",__FUNCTION__,__LINE__);
		$GLOBALS["CMDS"][]=$cmd;
	}

	@file_put_contents("/etc/artica-postfix/pids/".basename(__FILE__).".GLOBALS",serialize($GLOBALS["TIME"]));
	
	
}


//2H
function group300(){
	
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	$filetime="/etc/artica-postfix/pids/".md5(__FILE__.__FUNCTION__).".time";
	$time=$GLOBALS["CLASS_UNIX"]->file_time_min($filetime);
	if($time<121){return;}
	@unlink($filetime);
	@file_put_contents($filetime, time());
	
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/HdparmInfos")){sys_THREAD_COMMAND_SET(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.hdparm.php");}
	$array[]="exec.mysql.build.php --tables";
	
	
	if($GLOBALS["POSTFIX_INSTALLED"]){
		$array[]="exec.organization.statistics.php";
		$array[]="exec.quarantine-clean.php";
		$array[]="exec.smtp-hack.export.php --export";
		$array[]="exec.smtp.events.clean.php";
		$array[]="exec.roundcube.php --verifyTables";
	}
	
		
	
	$array2[]="artica-install -geoip-updates";
	  
	while (list ($index, $file) = each ($array) ){
		$cmd="{$GLOBALS["PHP5"]} /usr/share/artica-postfix/$file";
		events("schedule $cmd",__FUNCTION__,__LINE__);
		$GLOBALS["CMDS"][]=$cmd;
	}

	while (list ($index, $file) = each ($array2) ){
		$cmd="/usr/share/artica-postfix/bin/$file";
		events("schedule $cmd",__FUNCTION__,__LINE__);
		$GLOBALS["CMDS"][]=$cmd;
	}   
	@file_put_contents("/etc/artica-postfix/pids/".basename(__FILE__).".GLOBALS",serialize($GLOBALS["TIME"]));
}

//24H 1440mn
function group24h(){
	
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	$filetime="/etc/artica-postfix/pids/".md5(__FILE__.__FUNCTION__).".time";
	$time=$GLOBALS["CLASS_UNIX"]->file_time_min($filetime);
	if($time<1441){return;}
	@unlink($filetime);
	@file_put_contents($filetime, time());
	
	if($GLOBALS["GREYHOLE_INSTALLED"]){
		$array[]="exec.greyhole.php --fsck";
	}
	
	
	
		if(is_array($array)){	  
			while (list ($index, $file) = each ($array) ){
				$cmd="{$GLOBALS["PHP5"]} /usr/share/artica-postfix/$file";
				events("schedule $cmd",__FUNCTION__,__LINE__);
				$GLOBALS["CMDS"][]=$cmd;
			}
		}

	if(is_array($array2)){
		while (list ($index, $file) = each ($array2) ){
			$cmd="/usr/share/artica-postfix/bin/$file";
			events("schedule $cmd",__FUNCTION__,__LINE__);
			$GLOBALS["CMDS"][]=$cmd;
		} 
	}  
	@file_put_contents("/etc/artica-postfix/pids/".basename(__FILE__).".GLOBALS",serialize($GLOBALS["TIME"]));
}

function group60mn(){

}


function group120(){
	
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	$filetime="/etc/artica-postfix/pids/".md5(__FILE__.__FUNCTION__).".time";
	$time=$GLOBALS["CLASS_UNIX"]->file_time_min($filetime);
	if($time<121){return;}
	@unlink($filetime);
	@file_put_contents($filetime, time());
	
	
	
	
	
	if($GLOBALS["POSTFIX_INSTALLED"]){
		$array[]="exec.smtp.export.users.php --sync";
		$array[]="exec.quarantine-clean.php";
		$array[]="exec.postfix.finder.php --logrotate";
	}
	
	
	if($GLOBALS["CYRUS_IMAP_INSTALLED"]){
		$array["exec.cyrus.php --DirectorySize"]="exec.cyrus.php --DirectorySize";
	}
	
	
	
	
	
	if($GLOBALS["KAV4PROXY_INSTALLED"]){$array["exec.kav4proxy.buildstats.php --days"];}
	
	while (list ($index, $file) = each ($array) ){
		$cmd="{$GLOBALS["PHP5"]} /usr/share/artica-postfix/$file";
		events("schedule $cmd",__FUNCTION__,__LINE__);
		$GLOBALS["CMDS"][]=$cmd;
	}	
	
	$array2[]="artica-install --awstats-generate";
	$array2[]="artica-update";
	$array2[]="artica-install --cups-drivers";
	$array2[]="artica-update --spamassassin-bl";
	$array2[]="artica-install -watchdog daemon";
	if(!is_file("/usr/lib/apache2/modules/mod_qos.so")){
		$array2[]="artica-make APP_MOD_QOS";
	}
	
	if($GLOBALS["EnableArticaWatchDog"]==1){$array2[]="artica-install --urgency-start";}
	
	

	while (list ($index, $file) = each ($array2) ){
		events("schedule $cmd",__FUNCTION__,__LINE__);
		$cmd="/usr/share/artica-postfix/bin/$file";
		$GLOBALS["CMDS"][]=$cmd;
	}		
	$GLOBALS["CMDS"][]="/etc/init.d/artica-postfix restart clamd";
	@file_put_contents("/etc/artica-postfix/pids/".basename(__FILE__).".GLOBALS",serialize($GLOBALS["TIME"]));
}


function mailarchives(){
	if(!$GLOBALS["POSTFIX_INSTALLED"]){return;}
	$array[]="exec.mailarchive.php";
	$array[]="exec.mailbackup.php";
	$array[]="exec.fetchmail.sql.php";

	while (list ($index, $file) = each ($array) ){
		if(system_is_overloaded()){events(__FUNCTION__. ":: die, overloaded");die();}
			$cmd="{$GLOBALS["PHP5"]} /usr/share/artica-postfix/$file";
			events("schedule $cmd",__FUNCTION__,__LINE__);
			$GLOBALS["CMDS"][]=$cmd;
		}
		
	if($GLOBALS["VERBOSE"]){events(__FUNCTION__. ":: die...");}
}



function events($text=null,$function=null,$line=0){
		$l=new debuglogs();
		$filename=basename(__FILE__);
		$l->events("$filename $function:: $text (L.$line)","/var/log/artica-postfix/executor-daemon.log");
		}
?>