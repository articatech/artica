<?php
$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.urgency.disable.progress";
$GLOBALS["FORCE"]=false;
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
include_once(dirname(__FILE__)."/ressources/class.resolv.conf.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.booster.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.watchdog.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
$GLOBALS["META"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#--meta#",implode(" ",$argv))){$GLOBALS["META"]=true;$GLOBALS["FORCE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if($argv[1]=="--disable"){$GLOBALS["OUTPUT"]=true;xdisable();exit;}
if($argv[1]=="--enable"){$GLOBALS["OUTPUT"]=true;action_disable_ActiveDirectory();exit;}
xrun();



function xdisable(){
	
	$unix=new unix();
	$sock=new sockets();
	$php=$unix->LOCATE_PHP5_BIN();
	
	build_progress("{checking} Join to the Active Directory Domain",15);
	system("$php /usr/share/artica-postfix/exec.kerbauth.php --join");
	
	for($i=1;$i<4;$i++){
		build_progress("{checking} {waiting} $i/3 {seconds}",20);
		sleep(1);
	}
	
	build_progress("{checking} Active Directory with wbinfo...",25);
	$php=$unix->LOCATE_PHP5_BIN();
	wbinfo_if_available();
	if(!wbinfo(true)){build_progress("{checking} Active Directory {failed}",110);return;}
	build_progress("{checking} Active Directory...",30);
	if(!testjoin()){build_progress("{checking} Active Directory {failed}",110);return;}
	
	build_progress("Active Directory...",50);
	$sock->SET_INFO("KerbAuthWatchEv", 0);
	$sock->SET_INFO("ActiveDirectoryEmergency", 0);
	$sock->SET_INFO("ActiveDirectoryEmergencyReboot", 0);
	$sock->SET_INFO("ActiveDirectoryEmergencyNone", 0);
	build_progress("{reconfigure}...",80);
	system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
	build_progress("{done}...",100);
	if($GLOBALS["META"]){
		$unix->THREAD_COMMAND_SET("$php /usr/share/artica-postfix/exec.artica-meta-client.php --ping --force");
	}
	
}

function xrun(){
	$unix=new unix();
	
	if($GLOBALS["VERBOSE"]){$GLOBALS["FORCE"]=true;}
	$pidtime="/etc/artica-postfix/pids/exec.squid.watchdog.ad.start_watchdog.time";
	$pidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	$watch=new squid_watchdog();
	$sock=new sockets();
	$MonitConfig=$watch->MonitConfig;
	
	
	$CHECK_AD_INTERVAL=intval($MonitConfig["CHECK_AD_INTERVAL"]);
	if($CHECK_AD_INTERVAL<1){$CHECK_AD_INTERVAL=5;}
	
	$unix=new unix();
	$pid=$unix->get_pid_from_file($pidFile);
	if($unix->process_exists($pid)){
		$pptime=$unix->PROCCESS_TIME_MIN($pid,10);
		events("[INFO]:Process already running PID $pid since {$pptime}Mn");
		return;
	}
	
	
	@file_put_contents($pidFile, getmypid());
	if(!$GLOBALS["FORCE"]){
		$time=$unix->file_time_min($pidtime);
		if($time<$CHECK_AD_INTERVAL){
			events("[INFO]: Currently {$time}Mn need to wait {$CHECK_AD_INTERVAL}Mn");
			return;
		}
	}
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	
	$sock=new sockets();
	$WaitWinbindPID=2;
	
	$EnableKerbAuth=intval($sock->GET_INFO("EnableKerbAuth"));
	if($EnableKerbAuth==0){return;}
	$KerbAuthWatchEv=intval($sock->GET_INFO("KerbAuthWatchEv"));
	$ActiveDirectoryEmergency=intval($sock->GET_INFO("ActiveDirectoryEmergency"));
	$ActiveDirectoryEmergencyReboot=intval($sock->GET_INFO("ActiveDirectoryEmergencyReboot"));
	$ActiveDirectoryEmergencyFailOver=intval($sock->GET_INFO("ActiveDirectoryEmergencyFailOver"));
	$ActiveDirectoryEmergencyNone=intval($sock->GET_INFO("ActiveDirectoryEmergencyNone"));
	
	
	if($ActiveDirectoryEmergency==1){
		events("[INFO]:Running into Active Directory Emergency mode [SKIP]");
		return;
	}
	
	if($ActiveDirectoryEmergencyReboot==1){
		events("[INFO]:Running into Active Directory after a reboot, wait winbind for 10mn [SKIP]");
		$WaitWinbindPID=10;
		return;
	}	
	
	if($ActiveDirectoryEmergencyFailOver>0){
		if($unix->time_min($ActiveDirectoryEmergencyFailOver)<10){
			events("[INFO]:Running into Active Directory after a failover, wait for 10mn [SKIP]");
			return;
		}
		$sock->SET_INFO("ActiveDirectoryEmergencyFailOver", 0);
	}
	if($ActiveDirectoryEmergencyNone>0){
		if($unix->time_min($ActiveDirectoryEmergencyNone)<10){
			events("[INFO]:Running into Active Directory after a NONE EMERGENCY, wait for 10mn [SKIP]");
			return;
		}
		$sock->SET_INFO("ActiveDirectoryEmergencyNone", 0);
	}	
	
	$pid=WINBIND_PID();
	events("[INFO]:Winbind PID: $pid");
	
	if(!$unix->process_exists($pid)){
		events("[FATAL]:Winbind is not running start it...");
		$KerbAuthWatchEv++;
		squid_admin_mysql(0, "[Active Directory]: $KerbAuthWatchEv) Winbind is not running, start it [action=start]", null,__FILE__,__LINE__);
		if(MAX_ATTEMPTS_AD($KerbAuthWatchEv)){return;}
		$sock->SET_INFO("KerbAuthWatchEv", $KerbAuthWatchEv);
		shell_exec("/etc/init.d/winbind start");
		return;
	}
	
	if(!ping_winbind()){
		$KerbAuthWatchEv++;
		if(MAX_ATTEMPTS_AD($KerbAuthWatchEv)){return;}
		$sock->SET_INFO("KerbAuthWatchEv", $KerbAuthWatchEv);
		return;
	}
	
	$pidtime=$unix->PROCCESS_TIME_MIN($pid);
	
	if($pidtime<$WaitWinbindPID){
		events("[INFO]:Winbind PID: $pid TTL = {$pidtime}Mn must wait minimal {$WaitWinbindPID}Mn");
		return;
	}
	
	events("[INFO]:Winbind PID: $pid TTL = {$pidtime}Mn");
	$wbinfo=$unix->find_program("wbinfo");
	$php=$unix->LOCATE_PHP5_BIN();
	$net=$unix->find_program("net");
	
	if(!wbinfo(true)){
		events("[INFO]:winbinfo -t \"FAILED\"");
		$text=build_report();
		$KerbAuthWatchEv++;
		if(MAX_ATTEMPTS_AD($KerbAuthWatchEv)){return;}
		$sock->SET_INFO("KerbAuthWatchEv", $KerbAuthWatchEv);
		squid_admin_mysql(0, "[Active Directory]: $KerbAuthWatchEv) Active Directory connection failed (winbinfo -t) [action=reconnect]", $text,__FILE__,__LINE__);
		exec("$php /usr/share/artica-postfix/exec.kerbauth.php --join --verbose 2>&1",$join);
		
		if(!wbinfo(true)){
			squid_admin_mysql(0, "[Active Directory]: Join Active Directory task failed", @implode("\n", $join),__FILE__,__LINE__);
		}else{
			squid_admin_mysql(1, "Join Active Directory task success", @implode("\n", $join),__FILE__,__LINE__);
			$sock->SET_INFO("KerbAuthWatchEv", 0);
			$sock->SET_INFO("ActiveDirectoryEmergency", 0);
			$sock->SET_INFO("ActiveDirectoryEmergencyReboot", 0);
			$sock->SET_INFO("ActiveDirectoryEmergencyNone", 0);
			action_enable_ActiveDirectory();
		}
		return;
	}

	
	
	if(!testjoin()){
		events("[INFO]:testjoin \"FAILED\"");
		$KerbAuthWatchEv++;
		if(MAX_ATTEMPTS_AD($KerbAuthWatchEv)){return;}
		$sock->SET_INFO("KerbAuthWatchEv", $KerbAuthWatchEv);
		$text=build_report();
		squid_admin_mysql(0, "[Active Directory]: $KerbAuthWatchEv) Active Directory failed (testjoin) [action=reconnect]", $text,__FILE__,__LINE__);
		exec("$php /usr/share/artica-postfix/exec.kerbauth.php --join --verbose 2>&1",$join);
		
		if(!testjoin()){
			squid_admin_mysql(0, "[Active Directory]: Join Active Directory task failed", @implode("\n", $join),__FILE__,__LINE__);
		}else{
			squid_admin_mysql(1, "Join Active Directory task success", @implode("\n", $join),__FILE__,__LINE__);
			$sock->SET_INFO("KerbAuthWatchEv", 0);
			$sock->SET_INFO("ActiveDirectoryEmergency", 0);
			$sock->SET_INFO("ActiveDirectoryEmergencyReboot", 0);
			$sock->SET_INFO("ActiveDirectoryEmergencyNone", 0);
			action_enable_ActiveDirectory();
		}
		return;
	}
	
	$sock->SET_INFO("KerbAuthWatchEv", 0);
	$sock->SET_INFO("ActiveDirectoryEmergency", 0);
	$sock->SET_INFO("ActiveDirectoryEmergencyReboot", 0);
	$sock->SET_INFO("ActiveDirectoryEmergencyNone", 0);
	action_enable_ActiveDirectory();
	
	
	
	
	
}

function ping_winbind(){
	$unix=new unix();
	$smbcontrol=$unix->find_program('smbcontrol');
	$chmod=$unix->find_program("chmod");
	shell_exec("$chmod 0750 /var/lib/samba/winbindd_privileged >/dev/null 2>&1");
	$results=exec("$smbcontrol winbindd ping 2>&1");
	events("[INFO]:Winbindd service ping \"$results\"");
	if(preg_match("#No replies received#i", $results)){
		events("[INFO]:Winbindd service ping failed $results");
		squid_admin_mysql(2, "Winbindd failed to ping [action=start]", $results,__FILE__,__LINE__);
		shell_exec("/etc/init.d/winbind restart --force >/dev/null 2>&1");
		return false;
	}
	events("[INFO]:Winbind service ping = OK");
	return true;
}


function build_progress($text,$pourc){



	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	sleep(1);

}

function wbinfo_if_available(){
	$unix=new unix();
	$wbinfo=$unix->find_program("wbinfo");
	$php=$unix->LOCATE_PHP5_BIN();
	$net=$unix->find_program("net");
	exec("$wbinfo -t 2>&1",$results);
	
	while (list ($md5, $line) = each ($results) ){
		if(preg_match("#WBC_ERR_WINBIND_NOT_AVAILABLE#",$line)){
			squid_admin_mysql(0, "Warning WBC_ERR_WINBIND_NOT_AVAILABLE [action=start winbindd]", @implode("\n", $results),__FILE__,__LINE__);
			shell_exec("/etc/init.d/winbind start");
			return;
		}
		
	}
	
}

function wbinfo($Reloadifneed=false){
	$unix=new unix();
	$wbinfo=$unix->find_program("wbinfo");
	$php=$unix->LOCATE_PHP5_BIN();
	$net=$unix->find_program("net");	
	exec("$wbinfo -t 2>&1",$results);
	
	
	while (list ($md5, $line) = each ($results) ){
		events("[INFO]:winbinfo -t \"$line\"");
		
		if(preg_match("#succeeded#", $line)){events("[INFO]:winbinfo -t \"OK\"");return true;}
		
		if($Reloadifneed){
			if(preg_match("#WBC_ERR_WINBIND_NOT_AVAILABLE#",$line)){
				if($GLOBALS["OUTPUT"]){echo "Starting winbind\n";}
				shell_exec("/etc/init.d/winbind start");
				sleep(3);
				return wbinfo();
				
			}
		}
		
		
		if(preg_match("#failed#", $line)){return false;}
	
	}
	

}
function testjoin(){
	$unix=new unix();
	$wbinfo=$unix->find_program("wbinfo");
	$php=$unix->LOCATE_PHP5_BIN();
	$net=$unix->find_program("net");
	$results=exec("$net ads testjoin 2>&1");
	events("[INFO]:$net ads testjoin \"$results\"");
	if(!preg_match("#OK#", $results)){return false;}
	return true;
}

function MAX_ATTEMPTS_AD($KerbAuthWatchEv){
	$unix=new unix();
	$watch=new squid_watchdog();
	$sock=new sockets();
	$MonitConfig=$watch->MonitConfig;
	
	if($MonitConfig["CHECK_AD_FAILED_PING"]==1){
		$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
		$ADNETIPADDR=$array["ADNETIPADDR"];
		$Ipclass=new IP();
		if($Ipclass->isValid($ADNETIPADDR)){
			if(!$unix->PingHost($ADNETIPADDR)){
				squid_admin_mysql(0, "[Active Directory]: Ping AD $ADNETIPADDR failed [action={$MonitConfig["CHECK_AD_ACTION"]}]", null,__FILE__,__LINE__);
				$KerbAuthWatchEv=$MonitConfig["CHECK_AD_MAX_ATTEMPTS"];
			}else{
				squid_admin_mysql(2, "Ping AD $ADNETIPADDR Success [action=notify]", null,__FILE__,__LINE__);
			}
		}
		
	}
	
	
	if($KerbAuthWatchEv>$MonitConfig["CHECK_AD_MAX_ATTEMPTS"]){
		events("[ACTION]: $KerbAuthWatchEv atempts, MAX={$MonitConfig["CHECK_AD_MAX_ATTEMPTS"]}");
		switch ($MonitConfig["CHECK_AD_ACTION"]) {
			case "disable_ad":action_disable_ActiveDirectory();break;
			case "reboot":action_reboot();break;
			case "failover":action_failover();break;
			case "none":action_none();break;
			
		}
		return true;
		
	}
	
	return false;
}

function action_disable_ActiveDirectory(){
	$sock=new sockets();
	$unix=new unix();
	
	events("[ACTION]: Disable Active Directory");
	
	
	$f[]="# Active Directory Emergency mode !";
	$f[]="http_access allow all";
	$f[]="";
	@file_put_contents("/etc/squid3/non_ntlm.access", @implode("\n", $f));
	$sock->SET_INFO("ActiveDirectoryEmergency", 1);
	squid_admin_mysql(0, "[Active Directory]: Active Directory Emergency mode! [action=disable_ad]", null,__FILE__,__LINE__);
	$unix=new unix();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	shell_exec("$squidbin -k reconfigure");
	$sock->SET_INFO("KerbAuthWatchEv", 0);
	
	if($GLOBALS["META"]){
		$php=$unix->LOCATE_PHP5_BIN();
		$unix->THREAD_COMMAND_SET("$php /usr/share/artica-postfix/exec.artica-meta-client.php --ping --force");
	}
	
}

function action_enable_ActiveDirectory(){
	
	$filez=explode("\n",@file_get_contents("/etc/squid3/non_ntlm.access"));
	
	$ENABLED=false;
	while (list ($md5, $line) = each ($filez) ){
		if(preg_match("#http_access allow all#", $line)){
			$ENABLED=true;
			break;
		}
		
	}
	
	
	$f[]="# Nothing to do... ";
	$f[]="";
	@file_put_contents("/etc/squid3/non_ntlm.access", @implode("\n", $f));
	
	if($ENABLED){
		events("[ACTION]:Re-Activate Authentication");
		squid_admin_mysql(1, "Re-Activate Authentication mode after Active Directory Emergency mode [action=reload proxy]", "",__FILE__,__LINE__);
		$unix=new unix();
		$squidbin=$unix->LOCATE_SQUID_BIN();
		shell_exec("$squidbin -k reconfigure");
	}
	
	
}

function action_reboot(){
	
	$unix=new unix();
	$sock=new sockets();
	squid_admin_mysql(0, "[Active Directory]: Active Directory Emergency mode! [action=reboot]", null,__FILE__,__LINE__);
	$sock->SET_INFO("ActiveDirectoryEmergencyReboot", 1);
	$shutdown=$unix->find_program("shutdown");
	shell_exec("$shutdown -rF now 2>&1");!
	$sock->SET_INFO("KerbAuthWatchEv", 0);
	
}

function action_none(){

	$unix=new unix();
	$sock=new sockets();
	squid_admin_mysql(0, "[Active Directory]: Active Directory Emergency mode! [action=none]", null,__FILE__,__LINE__);
	$sock->SET_INFO("ActiveDirectoryEmergencyNone", time());
	
}


function action_failover(){
	$sock=new sockets();
	$FailOverArtica=$sock->GET_INFO("FailOverArtica");
	if(!is_numeric($FailOverArtica)){$FailOverArtica=1;}
	if($FailOverArtica==0){
		$sock->SET_INFO("KerbAuthWatchEv", 0);
		return;}
	
	$watch=new squid_watchdog();
	$MonitConfig=$watch->MonitConfig;
	if($MonitConfig["EnableFailover"]==0){
		$sock->SET_INFO("KerbAuthWatchEv", 0);
		return;
	}
	
	if(!is_file("/etc/init.d/artica-failover")){
		$sock->SET_INFO("KerbAuthWatchEv", 0);
		return;
	}
	
	
	$users=new settings_inc();
	if(!$users->CORP_LICENSE){
		squid_admin_mysql(0, "[Active Directory]: Active Directory Emergency mode: Unable to switch to failover backup, license error", null,__FILE__,__LINE__);
		events("[INFO]:Unable to switch to failover backup, license error");
		$sock->SET_INFO("KerbAuthWatchEv", 0);
		return;
	}
	squid_admin_mysql(0, "[Active Directory]: Active Directory Emergency mode [action=failover]", null,__FILE__,__LINE__);
	events("[INFO]:Down failover network interface in order to switch to backup...");
	$sock->SET_INFO("ActiveDirectoryEmergencyFailOver", time());
	shell_exec("/etc/init.d/artica-failover stop");
	$sock->SET_INFO("KerbAuthWatchEv", 0);
	
}


function build_report(){
	$unix=new unix();
	$wbinfo=$unix->find_program("wbinfo");
	$tail=$unix->find_program("tail");
	$net=$unix->find_program("net");
	$report[]="Winbind report";
	$report[]="*************************";
	exec("$wbinfo -t 2>&1",$report);
	$report[]="";
	exec("$wbinfo --online-status 2>&1",$report);
	$report[]="";
	exec("$net ads info 2>&1",$report);
	$report[]="";
	$report[]="Lastlog winbind";
	$report[]="*************************";	
	exec("$tail -n 50 /var/log/samba/log.winbindd 2>&1",$report);
	return @implode("\n", $report);
}



function is_winbind_run(){
	$unix=new unix();
	$pid=WINBIND_PID();
	if(!$unix->process_exists($pid)){return false;}
	return true;

}

function WINBIND_PID(){
	$pidfile="/var/run/samba/winbindd.pid";
	$unix=new unix();
	$pid=$unix->get_pid_from_file($pidfile);
	if(!$unix->process_exists($pid)){
		$winbindbin=$unix->find_program("winbindd");
		$pid=$unix->PIDOF($winbindbin);
	}
	return $pid;
}

function events($text){

	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}else{
			if(isset($trace[0])){
				$sourcefile=basename($trace[0]["file"]);
				$sourcefunction=$trace[0]["function"];
				$sourceline=$trace[0]["line"];
			}
			
		}

	}

	$unix=new unix();
	if($GLOBALS["OUTPUT"]){echo "$text\n";}
	$unix->events($text,"/var/log/squid.activedirectory-watchdog.log",false,$sourcefunction,$sourceline);
}