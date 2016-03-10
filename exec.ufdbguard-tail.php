<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ufdbguard-tools.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__)."/ressources/class.influx.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
if(!isset($GLOBALS["ARTICALOGDIR"])){
	$GLOBALS["ARTICALOGDIR"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaLogDir"); 
	if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } 
}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["CLASS_UNIX"]=new unix();
$GLOBALS["CLASS_SOCKET"]=new sockets();
$GLOBALS["CLASS_FAMILY"]=new squid_familysite();

$pidfile="/etc/artica-postfix/".basename(__FILE__).".pid";
$pid=getmypid();
$pid=@file_get_contents($pidfile);
events("Found old PID $pid");
if($pid<>$pid){
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid,basename(__FILE__))){events("Already executed PID: $pid.. aborting the process");die();}
}
if(is_file("{$GLOBALS["ARTICALOGDIR"]}/ufdbguard-tail.debug")){@unlink("{$GLOBALS["ARTICALOGDIR"]}/ufdbguard-tail.debug");}
file_put_contents($pidfile,$pid);
events("ufdbtail starting PID $pid...");
$GLOBALS["ufdbGenTable"]=$GLOBALS["CLASS_UNIX"]->find_program("ufdbGenTable");
$GLOBALS["chown"]=$GLOBALS["CLASS_UNIX"]->find_program("chown");
$GLOBALS["nohup"]=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
$GLOBALS["PHP5_BIN"]=$GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN();
$GLOBALS["SBIN_ARP"]=$GLOBALS["CLASS_UNIX"]->find_program("arp");
$GLOBALS["SBIN_ARPING"]=$GLOBALS["CLASS_UNIX"]->find_program("arping");
$GLOBALS["SBIN_RM"]=$GLOBALS["CLASS_UNIX"]->find_program("rm");
$GLOBALS["SQUID_PERFORMANCE"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidPerformance"));
$GLOBALS["UfdbEnableParanoidMode"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/UfdbEnableParanoidMode"));
$GLOBALS["UfdbEnableParanoidBlockW"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/UfdbEnableParanoidBlockW"));
$GLOBALS["UfdbEnableParanoidBlockC"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/UfdbEnableParanoidBlockC"));
$GLOBALS["UfdbEnableParanoidBlockU"]=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/UfdbEnableParanoidBlockU"));


$GLOBALS["UfdbguardSMTPNotifs"]=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/UfdbguardSMTPNotifs")));
if(!isset($GLOBALS["UfdbguardSMTPNotifs"]["ENABLED"])){$GLOBALS["UfdbguardSMTPNotifs"]["ENABLED"]=0;}
if(!isset($GLOBALS["UfdbguardSMTPNotifs"]["BLOCK_NOTIFS"])){$GLOBALS["UfdbguardSMTPNotifs"]["BLOCK_NOTIFS"]=0;}

if($GLOBALS["UfdbguardSMTPNotifs"]["ENABLED"]==0){$GLOBALS["UfdbguardSMTPNotifs"]["BLOCK_NOTIFS"]=0;}
events("SMTP Notification: {$GLOBALS["UfdbguardSMTPNotifs"]["ENABLED"]}");
events("Threats Notifications: {$GLOBALS["UfdbguardSMTPNotifs"]["BLOCK_NOTIFS"]}");
events("SQUID_PERFORMANCE: {$GLOBALS["SQUID_PERFORMANCE"]}");



$GLOBALS["RELOADCMD"]="{$GLOBALS["nohup"]} {$GLOBALS["PHP5_BIN"]} ".dirname(__FILE__)."/exec.squidguard.php --reload-ufdb";
if($argv[1]=='--date'){echo date("Y-m-d H:i:s")."\n";}
@mkdir("{$GLOBALS["ARTICALOGDIR"]}/squid-stats",0666,true);
@mkdir("{$GLOBALS["ARTICALOGDIR"]}/pagepeeker",600,true);

ToSyslog("Watchdog started pid $pid");
events("ufdbGenTable = {$GLOBALS["ufdbGenTable"]}");
if(!is_dir("/home/ufdb/relatime-events")){@mkdir("/home/ufdb/relatime-events",0755,true);}
shell_exec("{$GLOBALS["chown"]} -R squid:squid /home/ufdb/relatime-events");



$pipe = fopen("php://stdin", "r");
while(!feof($pipe)){
	$buffer .= fgets($pipe, 4096);
	try {Parseline($buffer);}
	catch(Exception $e){ufdbguard_admin_events("Fatal error on $buffer: ".$e->getMessage(),"MAIN",__FILE__,__LINE__,"ufdbguard-service");}
	$buffer=null;
}

fclose($pipe);

events_ufdb_exec("Artica ufdb-tail shutdown");
events("Shutdown...");
die();



function Parseline($buffer){
$buffer=trim($buffer);
if($buffer==null){return null;}
$mdbuff=md5($buffer);
if(isset($GLOBALS['MDBUFF'][$mdbuff])){return;}
$GLOBALS['MDBUFF'][$mdbuff]=true;
if(count($GLOBALS['MDBUFF'])>1000){$GLOBALS['MDBUFF']=array();}


if(strpos($buffer,"] PASS ")>0){return ;}
if(strpos($buffer,"UFDBinitHTTPSchecker")>0){return ;}
if(strpos($buffer,"IP socket port")>0){return ;}
if(strpos($buffer,"listening on interface")>0){return ;}
if(strpos($buffer,"yielding")>0){return ;}
if(strpos($buffer,"system:")>0){return ;}
if(strpos($buffer,"URL verification threads and")>0){return ;}
if(strpos($buffer,"worker threads")>0){return ;}
if(strpos($buffer,"license status")>0){return ;}
if(strpos($buffer,"redirect-fatal-error")>0){return ;}
if(strpos($buffer,"using OpenSSL library")>0){return ;}
if(strpos($buffer,"CA certificates are")>0){return ;}
if(strpos($buffer,"Failure to load the CA database")>0){return ;}
if(strpos($buffer,"CA file is")>0){return ;}
if(strpos($buffer,"ufdbHandleAlarmForTimeEvents")>0){return ;}
if(strpos($buffer,"Changing daemon status")>0){return ;}
if(strpos($buffer,"UFDBchangeStatus")>0){return ;}
if(strpos($buffer,"url-lookup-delay-during-database-reload")>0){return ;}
if(strpos($buffer,"url-lookup-result-during-database-reload")>0){return ;}
if(strpos($buffer,"url-lookup-result-when-fatal-error")>0){return ;}
if(strpos($buffer,"no http-server")>0){return ;}
if(strpos($buffer,"upload-stats")>0){return ;}
if(strpos($buffer,"analyse-uncategorised-urls")>0){return ;}
if(strpos($buffer,"redirect-loading-database")>0){return ;}
if(strpos($buffer,"ufdb-expression-debug")>0){return ;}
if(strpos($buffer,"ufdb-debug-filter")>0){return ;}
if(strpos($buffer,"database status: up to date")>0){return ;}
if(strpos($buffer,"ufdbGenTable should be called with the")>0){return ;}
if(strpos($buffer,"is deprecated and ignored")>0){return ;}
if(strpos($buffer,"init domainlist")>0){return ;}
if(strpos($buffer,"is empty !")>0){return ;}
if(strpos($buffer,"init expressionlist")>0){return ;}
if(strpos($buffer,"is optimised to one expression")>0){return ;}
if(strpos($buffer,"be analysed since there is no proper database")>0){return ;}
if(strpos($buffer,"REDIRECT 302")>0){return ;}
if(strpos($buffer,"close fd")>0){return ;}
if(strpos($buffer,": open fd ")>0){return ;}
if(strpos($buffer,"acl {")>0){return ;}
if(strpos($buffer,"URL verifications")>0){return ;}
if(strpos($buffer,"must be part of the security")>0){return ;}
if(strpos($buffer,"}")>0){return ;}
if(strpos($buffer,"finished retrieving")>0){return ;}

if(strpos($buffer,"loading URL table from")>0){return ;}
if(strpos($buffer,"]    option")>0){return ;}
if(strpos($buffer,"{")>0){return ;}
if(strpos($buffer,"] category \"")>0){return ;}
if(strpos($buffer,"]    domainlist     \"")>0){return ;}
if(strpos($buffer,"]       pass ")>0){return ;}
if(strpos($buffer,"] safe-search")>0){return ;}
if(strpos($buffer,"configuration file")>0){return ;}
if(strpos($buffer,"refreshdomainlist")>0){return ;}
if(strpos($buffer,"software suite is free and Open Source Software")>0){return ;}
if(strpos($buffer,"by URLfilterDB")>0){return ;}
if(strpos($buffer,"] configuration status")>0){return ;}
if(strpos($buffer,'expressionlist "')>0){return ;}
if(strpos($buffer,'is newer than')>0){return ;}
if(strpos($buffer,'source "')>0){return ;}
if(strpos($buffer,'youtube-edufilter-id')>0){return ;}
if(trim($buffer)==null){return;}
if(strpos($buffer,'max-logfile-size')>0){return ;}
if(strpos($buffer,'check-proxy-tunnels')>0){return ;}
if(strpos($buffer,'seconds to allow worker')>0){return ;}
if(strpos($buffer,'] loading URL category')>0){return ;}
if(preg_match("#\] REDIR\s+#", $buffer)){return;}
if(strpos($buffer,'execdomainlist for')>0){return ;}
if(strpos($buffer,'dynamic_domainlist_updater_main')>0){return ;}


if(preg_match("#FATAL ERROR: connection queue is full#",$buffer)){
	$TimeFile="/etc/artica-postfix/pids/webfiltering-connection.queue.full";
	if(!IfFileTime($TimeFile,5)){return;}
	$Threads=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/UfdbGuardThreads"));
	$ThreadNew=$Threads+5;
	if($ThreadNew>128){$ThreadNew=128;}
	squid_admin_mysql(0, "Webfiltering Service connection queue is full increase Threads from $Threads to $ThreadNew [action=restart]", $buffer,__FILE__,__LINE__);
	@file_put_contents("/etc/artica-postfix/settings/Daemons/UfdbGuardThreads", $ThreadNew);
	shell_exec("{$GLOBALS["nohup"]} /etc/init.d/ufdb restart --force >/dev/null 2>&1 &");
	return;
}

if(stripos(" $buffer","HUP signal received to reload the configuration")>0){
	squid_admin_mysql(1, "Webfiltering Service was reloaded - reloading databases [action=notify]", $buffer,__FILE__,__LINE__);
	events_ufdb_exec("Webfiltering Service was reloaded, wait 15 seconds");
	return;
}

if(stripos(" $buffer","ufdbGuard daemon stopped")>0){
	squid_admin_mysql(1, "Webfiltering Service was stopped [action=notify]", $buffer,__FILE__,__LINE__);
	events_ufdb_exec("Webfiltering Service was stopped, wait 15 seconds");
	return;
}

if(stripos(" $buffer",'Changing daemon status to "started"')>0){
	squid_admin_mysql(1, "Webfiltering Service was started [action=notify]", $buffer,__FILE__,__LINE__);
	events_ufdb_exec("Webfiltering Service was started, wait 15 seconds");
	return;
}


if(preg_match("#thread socket-handler caught signal 11#",$buffer,$re)){
	$TimeFile="/etc/artica-postfix/pids/webfiltering-emergency";
	if(!IfFileTime($TimeFile,5)){return;}
	squid_admin_mysql(0, "Webfiltering crash [action=Webfiltering Emergency]", $buffer,__FILE__,__LINE__);
	shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.squid.urgency.remove.php --ufdb-on >/dev/null 2>&1 &");
	return;
}


if(preg_match("#Changing daemon status to \"error\"#",$buffer,$re)){
	$TimeFile="/etc/artica-postfix/pids/webfiltering-emergency";
	if(!IfFileTime($TimeFile,5)){return;}
	squid_admin_mysql(0, "Webfiltering service error [action=Webfiltering Emergency]", $buffer,__FILE__,__LINE__);
	shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.ufdb.emergency.php --ufdb-on >/dev/null 2>&1 &");
	return;
}


if(preg_match("#FATAL ERROR: cannot open configuration file\s+\/etc\/squid3\/ufdbGuard\.conf#i",$buffer,$re)){
	squid_admin_mysql(0, "Webfiltering error, Open Configuration File failed [action=restart service]", $buffer,__FILE__,__LINE__);
	shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.ufdb.php --restart --force --ufdbtail --fatal-error >/dev/null 2>&1 &");
	return;
}

if(preg_match("#FATAL.*?read failed on \"(.+?)\".*?Bad address#i",$buffer,$re)){
	squid_admin_mysql(0, "Webfiltering service error on database: {$re[1]}  [action=Webfiltering Emergency]", $buffer,__FILE__,__LINE__);
	shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.ufdb.emergency.php --ufdb-on >/dev/null 2>&1 &");
	return;
}


if(preg_match("#FATAL ERROR: cannot read from.*?No such file or directory#", $buffer,$re)){
	squid_admin_mysql(0, "Webfiltering error: a database is missing [action=reconfigure]", $buffer,__FILE__,__LINE__);
	shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.squidguard.php --build --force >/dev/null 2>&1 &");
	return;
}

if(preg_match("#There are no sources and there is no default ACL#i", $buffer)){
	events("Seems not to be defined -> build compilation.");
	xsyslog("{reconfigure} ufdb service...");
	shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.squidguard.php --build --force >/dev/null 2>&1 &");
	return;
}
	
	if(preg_match("#ERROR: cannot write to PID file\s+(.+)#i", $buffer,$re)){
		xsyslog("Apply permissions on {$re[1]}");
		$pidfile=$re[1];
		$pidpath=dirname($pidfile);
		@mkdir($pidpath,0755,true);
		@chown($pidpath,"squid");
		@chmod($pidpath,0755);
		return;
	}
	
	
	if(preg_match("#\] Changing daemon status to.*?error#",$buffer,$re)){
		squid_admin_mysql(0, "Fatal! Webfilter daemon is turned to error", $buffer,__FILE__,__LINE__);
		return;
		
	}
	
	if(preg_match("#\] Changing daemon status to.*?terminated#",$buffer,$re)){
		squid_admin_mysql(1, "Webfilter daemon is turned to OFF", $buffer,__FILE__,__LINE__);
		return;
	
	}	
	
	
	if(preg_match("#can't execute command of execdomainlist.*?popen failed: Cannot allocate memory#",$buffer,$re)){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/UfdbExecDomainList", 0);
		squid_admin_mysql(0, "Not Enough memory to use execdomainlist feature [action=reconfigure]", "$buffer\nexecdomainlist feature will be disabled..",__FILE__,__LINE__);
		shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.squidguard.php --build --force >/dev/null 2>&1 &");
		return;
	
	}	
	
	
	
	if(preg_match('#FATAL ERROR: table "(.+?)"\s+could not be parsed.*?error code = [0-9]+#',$buffer,$re)){
		$direname=dirname($re[1]);
		squid_admin_mysql(0, "Database $direname corrupted", $buffer."\nReconfigure ufdb service after removing $direname...",__FILE__,__LINE__);
		events("Webfiltering engine error on $direname");
		if(!is_dir($direname)){return;}
		shell_exec("{$GLOBALS["SBIN_RM"]} -rf $direname >/dev/null 2>&1");
		xsyslog("{reconfigure} ufdb service after removing $direname...");
		shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.squidguard.php --build --force >/dev/null 2>&1 &");
		return;
	}

	if(preg_match("#BLOCK-FATAL\s+#",$buffer,$re)){
		$TimeFile="/etc/artica-postfix/pids/UFDB_BLOCK_FATAL";
		if(!IfFileTime($TimeFile,10)){return;}
		events("Webfiltering engine error, reload service");
		events_ufdb_exec("service was restarted, $buffer");
		squid_admin_mysql(0, "Fatal, Web filtering engine error", $buffer."\nThe service will be reloaded",__FILE__,__LINE__);
		xsyslog("Reloading ufdb service...");
		shell_exec("{$GLOBALS["nohup"]} /etc/init.d/ufdb reload >/dev/null 2>&1 &");
		return;
	}
	
	if(preg_match("#FATAL ERROR: connection queue is full#",$buffer,$re)){
		$TimeFile="/etc/artica-postfix/pids/UFDB_QUEUE_IS_FULL";
		$Threads=@file_get_contents("/etc/artica-postfix/settings/Daemons/UfdbGuardThreads");
		if(!is_numeric($Threads)){$Threads=48;}
		$Threads=$Threads+1;
		if($Threads>140){$Threads=140;}
		@file_put_contents("/etc/artica-postfix/settings/Daemons/UfdbGuardThreads", $Threads);
		if(!IfFileTime($TimeFile,2)){return;}
		squid_admin_mysql(0, "Fatal, Web filtering connection queue is full", $buffer."\nThe service will be restarted and threads are increased to $Threads",__FILE__,__LINE__);
		xsyslog("Restarting ufdb service after connection queue is full...");
		shell_exec("{$GLOBALS["nohup"]} /etc/init.d/ufdb restart >/dev/null 2>&1 &");
		return;
	}
	

	if(preg_match('#FATAL\*\s+table\s+"(.+?)"\s+could not be parsed.+?14#',$buffer,$re)){
		events("Table on {$re[1]} crashed");
		squid_admin_mysql(0, "Database {$re[1]} corrupted", $buffer,__FILE__,__LINE__);
		ufdbguard_admin_events("Table on {$re[1]} crashed\n$buffer",__FUNCTION__,__FILE__,__LINE__,"ufdbguard-service");
		events_ufdb_exec("$buffer");
		$GLOBALS["CLASS_UNIX"]->send_email_events("ufdbguard: {$re[1]} could not be parsed","Ufdbguard claim: $buffer\n
		You need to compile this database","proxy");
		return;		
	}
	
	
	
	

	
	if(preg_match("#FATAL ERROR: cannot bind daemon socket: Address already in use#", $buffer)){
		events_ufdb_exec("ERROR DETECTED : $buffer `cannot bind daemon socket`");
		squid_admin_mysql(1, "Fatal ERROR: cannot bind daemon socket: Address already in use [action=restart]", $buffer,__FILE__,__LINE__);
		ufdbguard_admin_events("Fatal ERROR: cannot bind daemon socket: Address already in use",__FUNCTION__,__FILE__,__LINE__,"ufdbguard-service");
		xsyslog("Restarting ufdb service...");
		shell_exec("{$GLOBALS["nohup"]} /etc/init.d/ufdb restart >/dev/null 2>&1 &");
		return;
	}
	

	
	if(preg_match('#\] FATAL ERROR: cannot read from "(.+?)".*?No such file or directory#', $buffer,$re)){
		squid_admin_mysql(0, "Database {$re[1]} missing", $buffer,__FILE__,__LINE__);
		events("cannot read '{$re[1]}' -> \"$buffer\"");
		squid_admin_mysql(2,"Web filtering issue on {$re[1]}","Launch recover_a_database()",__FILE__,__LINE__);
		recover_a_database($re[1]);
		return;
	}
	
	if(preg_match('#\*FATAL.+? cannot read from "(.+?)".+?: No such file or directory#', $buffer,$re)){
		squid_admin_mysql(0, "Database {$re[1]} missing", $buffer,__FILE__,__LINE__);
		events("cannot read '{$re[1]}' -> \"$buffer\"");
		squid_admin_mysql(2,"Web filtering issue on {$re[1]}","Launch recover_a_database()",__FILE__,__LINE__);
		recover_a_database($re[1]);
		return;
		
	}
	
	
	if(preg_match('#\*FATAL\*\s+cannot read from\s+"(.+?)"#',$buffer,$re)){
		squid_admin_mysql(0, "Database {$re[1]} missing", $buffer,__FILE__,__LINE__);
		events("Problem on {$re[1]}");
		
		events_ufdb_exec("$buffer");
		squid_admin_mysql(2,"Web filtering issue on {$re[1]}","Launch recover_a_database()",__FILE__,__LINE__);
		recover_a_database($re[1]);
		$GLOBALS["CLASS_UNIX"]->send_email_events("ufdbguard: {$re[1]} Not compiled..","Ufdbguard claim: $buffer\nYou need to compile your databases");
		return;		
	}
	
	if(preg_match("#\*FATAL\*\s+cannot read from\s+\"(.+?)\.ufdb\".+?No such file or directory#",$buffer,$re)){
		squid_admin_mysql(0, "Database {$re[1]} missing", $buffer."\n Problem on {$re[1]}\n\nYou need to compile your databases",__FILE__,__LINE__);
		events("UFDB database missing : Problem on {$re[1]}");
		
		if(!is_file($re[1])){
			@mkdir(dirname($re[1]),666,true);
			shell_exec("/bin/touch {$re[1]}");
		}
		
		$GLOBALS["CLASS_UNIX"]->send_email_events("ufdbguard: {$re[1]} Not compiled..","Ufdbguard claim: $buffer\nYou need to compile your databases","ufdbguard-service");
		return;		
	}
	
	
	if(preg_match("#thread worker-[0-1]+.+?caught signal\s+[0-1]+#",$buffer,$re)){
		squid_admin_mysql(0, "Webfiltering Daemon as crashed - Start a new one", $buffer,__FILE__,__LINE__);
		
		$GLOBALS["CLASS_UNIX"]->send_email_events("ufdbguard: crashed","Ufdbguard claim: $buffer\n","proxy");
		shell_exec("/etc/init.d/ufdb start &");
	}
	
	
	
	if(preg_match("#\*FATAL\*\s+expression list\s+(.+?): Permission denied#",$buffer,$re)){
		squid_admin_mysql(0, "Database {$re[1]} permission denied", $buffer."\nProblem on '{$re[1]}' -> chown squid:squid",__FILE__,__LINE__);
		
		events("UFDB expression permission issue : Problem on '{$re[1]}' -> chown squid:squid");
		shell_exec("{$GLOBALS["chown"]} -R squid:squid ".dirname($re[1]));
		return;
	}
	
	if(preg_match("#\*FATAL.+?expression list\s+(.+?):\s+No such file or directory#", $buffer,$re)){
		squid_admin_mysql(0, "Database {$re[1]} missing", $buffer."\nProblem on '{$re[1]}' -> Try to repair",__FILE__,__LINE__);
		
		events("Expression list: Problem on {$re[1]} -> \"$buffer\"");
		events("Creating directory ".dirname($re[1]));
		@mkdir(dirname($re[1]),0755,true);
		events("Creating empty file '".$re[1]."'");
		@file_put_contents($re[1], "\n");
		events("ufdbguard tail: Service will be reloaded");
		$GLOBALS["CLASS_UNIX"]->send_email_events(basename(__FILE__).":Service ufdb will be reloaded ",  "Cause:$buffer", "ufdbguard-service");
		squid_admin_mysql(2, "Ask to reload the Web filtering service","Cause:$buffer");
		ufdbguard_admin_events("ufdbguard tail: Service will be reloaded",__FUNCTION__,__FILE__,__LINE__,"watchdog");
		shell_exec("{$GLOBALS["RELOADCMD"]} --function==".__FUNCTION__ ." --line=".__LINE__." ". "--filename=".basename(__FILE__)." >/dev/null 2>&1 &");
		return;
	}
	
	if(preg_match("#database table \/var\/lib\/squidguard\/(.+?)\/domains\s+is empty#",$buffer,$re)){
		//ufdbguard_admin_events("Database {$re[1]} as no datas, you should recompile your databases",__FUNCTION__,__FILE__,__LINE__,"ufdbguard-service");
		//$GLOBALS["CLASS_UNIX"]->send_email_events("ufdbguard: {$re[1]} database is empty, please compile your databases","Ufdbguard claim: $buffer\nYou need to compile your databases","proxy");
		return;
	}
	


	if(preg_match("#the new configuration and database are loaded for ufdbguardd ([0-9\.]+)#",$buffer,$re)){
		squid_admin_mysql(2, "Web Filtering engine service v{$re[1]} has reloaded new configuration and databases","");
		$GLOBALS["CLASS_UNIX"]->send_email_events("UfdbGuard v{$re[1]} has reloaded new configuration and databases",null,"ufdbguard-service");
		return;
	}
	
	if(preg_match("#statistics:(.+)#",$buffer,$re)){
		if(preg_match("#blocked ([0-9]+) times#", $re[1],$ri)){
			if($ri[1]>0){
				//squid_admin_mysql(2, "{$re[1]}","");
			}
		}
		
		return;
	}
	
	if(preg_match("#BLOCK (.*?)\s+(.+?)\s+(.+?)\s+(.+?)\s+(|http|https|ftp|ftps)://(.+?)myip=(.+)$#",$buffer,$re)){
		
		
		
		
		$user=trim($re[1]);
		$local_ip=$re[2];
		$rulename=$re[3];
		$category=$re[4];
		$www=$re[6];
		$public_ip=$re[7];
		
		//events("BLOCK[".__LINE__."]: $user/$local_ip - $www");
		
		
		if(strpos($www,"/")>0){$tb=explode("/",$www);$www=$tb[0];}
		if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}
		if(preg_match("#([0-9]+)\.addr#", $www)){$www=long2ip($re[1]);}
		
		if(preg_match("#^([0-9\.]+)#", $local_ip,$re)){$local_ip=$re[1];}
		$date=time();
		$table=date('Ymd')."_blocked";
		$category=CategoryCodeToCatName($category);
		if($user=="-"){$user=null;}
		$MAC=$GLOBALS["CLASS_UNIX"]->IpToMac($local_ip);
		$time=time();
		
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $www)){$public_ip=$www;$www=$GLOBALS["CLASS_UNIX"]->IpToHostname($www);}
		
		
		$Clienthostname=$GLOBALS["CLASS_UNIX"]->IpToHostname($local_ip);
		if($Clienthostname==null){$Clienthostname=$local_ip;}
		
		paranoidmode($local_ip,$www);
		
		
		$q=new influx();
		if($GLOBALS["UfdbguardSMTPNotifs"]["BLOCK_NOTIFS"]==1){
			events("Write notif");
			$line_notif=date("H:i:s")." [$www]: blocked domain: User: $user/$local_ip/$Clienthostname, Category: $category, Rule: $rulename";
			$q->insert_ufdb_notif($line_notif);
		}
		
		if($GLOBALS["SQUID_PERFORMANCE"]>2){return;}
		
		
		
		$line="$time:::$user:::$category:::$rulename:::$public_ip:::blocked domain:::blocked domain:::$Clienthostname:::$www:::$local_ip";
		
		
		$q->insert_ufdb($line);
		

		
		return;
		
	}
	
	if(preg_match("#BLOCK\s+(.*?)\s+(.+?)\s+(.*?)\s+(.+?)\s+(.+?)\s+[A-Z]+#", $buffer,$re)){
		
		$date=time();
		$user=trim($re[1]);
		$local_ip=$re[2];
		$rulename=$re[3];
		$category=$re[4];
		$uri=$re[5];
		
		//events("BLOCK[".__LINE__."]: $user/$local_ip - $www Notif:{$GLOBALS["UfdbguardSMTPNotifs"]["BLOCK_NOTIFS"]}");
		
		if(preg_match("#^([0-9\.]+)#", $local_ip,$re)){$local_ip=$re[1];}
		$time=time();
		$array=parse_url($uri);	
		$www=$array["host"];
		if(strpos($www, ":")>0){$t=explode(":", $www);$www=$t[0];}
		if(preg_match("#([0-9]+)\.addr#", $www)){$www=long2ip($re[1]);}
		
		$category=CategoryCodeToCatName($category);
		$MAC=$GLOBALS["CLASS_UNIX"]->IpToMac($local_ip);
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $www)){$public_ip=$www;$www=$GLOBALS["CLASS_UNIX"]->IpToHostname($www);}else{$public_ip=HostnameToIp($www);}
		if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}
		$Clienthostname=$GLOBALS["CLASS_UNIX"]->IpToHostname($local_ip);
		if($Clienthostname==null){$Clienthostname=$local_ip;}
		if($user=="-"){$user=null;}
		
	
		CreateCounter($www,$local_ip,$user,$category);
		paranoidmode($local_ip,$www);
		$q=new influx();
		
		if($GLOBALS["UfdbguardSMTPNotifs"]["BLOCK_NOTIFS"]==1){
			$line_notif=date("H:i:s")." [$www]: blocked domain: User: $user/$local_ip/$Clienthostname, Category: $category, Rule: $rulename";
			$q->insert_ufdb_notif($line_notif);
		}
		
		if($GLOBALS["SQUID_PERFORMANCE"]>2){return;}
		
		$q=new influx();
		$line="$time:::$user:::$category:::$rulename:::$public_ip:::blocked domain:::blocked domain:::$Clienthostname:::$www:::$local_ip";
		$q->insert_ufdb($line);
		return;
		
	}
	
	
	
	events("Not filtered: $buffer");

}

function HostnameToIp($hostname){
	if(isset($GLOBALS["IPNAMES2"][$hostname])){return $GLOBALS["IPNAMES2"][$hostname];}
	$GLOBALS["IPNAMES2"][$hostname]=gethostbyname($hostname);
	return $GLOBALS["IPNAMES2"][$hostname];
}

function CreateCounter($domain,$ip,$userid,$category){
	$category=str_replace("/", "_", $category);
	if(count($GLOBALS["MEMORY_FAMILY"])>1000){$GLOBALS["MEMORY_FAMILY"]=array();}
	
	if(!isset($GLOBALS["MEMORY_FAMILY"][$domain])){
		$f=new squid_familysite();
		$GLOBALS["MEMORY_FAMILY"][$domain]=$f->GetFamilySites($domain);
	}
	
	$domain=$GLOBALS["MEMORY_FAMILY"][$domain];
	
	$Y=date("Y");
	$M=date("M");
	$D=date("d");
	$H=date("H");
	$dirCreate="/home/artica/ufdbcounters/$Y/$M/$D/$H";
	if(!is_dir($dirCreate)){@mkdir($dirCreate,0755,true);}
	
	$YearC=intval(@file_get_contents("/home/artica/ufdbcounters/$Y/TOT"));
	$YearC=$YearC+1;
	@file_put_contents("/home/artica/ufdbcounters/$Y/TOT", $YearC);
	
	$MONTHC=intval(@file_get_contents("/home/artica/ufdbcounters/$Y/$M/TOT"));
	$MONTHC=$MONTHC+1;
	@file_put_contents("/home/artica/ufdbcounters/$Y/$M/TOT", $MONTHC);	
	
	
	$DAYC=intval(@file_get_contents("/home/artica/ufdbcounters/$Y/$M/$D/TOT"));
	$DAYC=$DAYC+1;
	@file_put_contents("/home/artica/ufdbcounters/$Y/$M/$D/TOT", $DAYC);
	
	$HOURC=intval(@file_get_contents("/home/artica/ufdbcounters/$Y/$M/$D/$H/TOT"));
	$HOURC=$HOURC+1;
	@file_put_contents("/home/artica/ufdbcounters/$Y/$M/$D/$H/TOT", $HOURC);
	

	if(!is_dir("/home/artica/ufdbcounters/$Y/$M/$D/$H/$domain")){@mkdir("/home/artica/ufdbcounters/$Y/$M/$D/$H/$domain",0755,true);}
	$YearC=intval(@file_get_contents("/home/artica/ufdbcounters/$Y/$domain/TOT"));
	$YearC=$YearC+1;
	@file_put_contents("/home/artica/ufdbcounters/$Y/$domain/TOT", $YearC);
	
	$MONTHC=intval(@file_get_contents("/home/artica/ufdbcounters/$Y/$M/$domain/TOT"));
	$MONTHC=$MONTHC+1;
	@file_put_contents("/home/artica/ufdbcounters/$Y/$M/$domain/TOT", $MONTHC);
	
	$DAYC=intval(@file_get_contents("/home/artica/ufdbcounters/$Y/$M/$D/$domain/TOT"));
	$DAYC=$DAYC+1;
	@file_put_contents("/home/artica/ufdbcounters/$Y/$M/$D/$domain/TOT", $DAYC);
	
	$HOURC=intval(@file_get_contents("/home/artica/ufdbcounters/$Y/$M/$D/$H/$domain/TOT"));
	$HOURC=$HOURC+1;
	@file_put_contents("/home/artica/ufdbcounters/$Y/$M/$D/$H/$domain/TOT", $HOURC);
	
	
	if($userid<>null){
		$YearC=intval(@file_get_contents("/home/artica/ufdbcounters/$Y/$userid"));
		$YearC=$YearC+1;
		@file_put_contents("/home/artica/ufdbcounters/$Y/$userid", $YearC);
		
		$MONTHC=intval(@file_get_contents("/home/artica/ufdbcounters/$Y/$M/$userid"));
		$MONTHC=$MONTHC+1;
		@file_put_contents("/home/artica/ufdbcounters/$Y/$M/$userid", $MONTHC);
		
		
		$DAYC=intval(@file_get_contents("/home/artica/ufdbcounters/$Y/$M/$D/$userid"));
		$DAYC=$DAYC+1;
		@file_put_contents("/home/artica/ufdbcounters/$Y/$M/$D/$userid", $DAYC);
		
		$HOURC=intval(@file_get_contents("/home/artica/ufdbcounters/$Y/$M/$D/$H/$userid"));
		$HOURC=$HOURC+1;
		@file_put_contents("/home/artica/ufdbcounters/$Y/$M/$D/$H/$userid", $HOURC);
		
	}

	
	$YearC=intval(@file_get_contents("/home/artica/ufdbcounters/$Y/$ip"));
	$YearC=$YearC+1;
	@file_put_contents("/home/artica/ufdbcounters/$Y/$ip", $YearC);
	
	$MONTHC=intval(@file_get_contents("/home/artica/ufdbcounters/$Y/$M/$ip"));
	$MONTHC=$MONTHC+1;
	@file_put_contents("/home/artica/ufdbcounters/$Y/$M/$ip", $MONTHC);
	
	$DAYC=intval(@file_get_contents("/home/artica/ufdbcounters/$Y/$M/$D/$ip"));
	$DAYC=$DAYC+1;
	@file_put_contents("/home/artica/ufdbcounters/$Y/$M/$D/$ip", $DAYC);

	$HOURC=intval(@file_get_contents("/home/artica/ufdbcounters/$Y/$M/$D/$H/$ip"));
	$HOURC=$HOURC+1;
	@file_put_contents("/home/artica/ufdbcounters/$Y/$M/$D/$H/$ip", $HOURC);
	
	
	$YearC=intval(@file_get_contents("/home/artica/ufdbcounters/$Y/$category"));
	$YearC=$YearC+1;
	@file_put_contents("/home/artica/ufdbcounters/$Y/$category", $YearC);
	
	$MONTHC=intval(@file_get_contents("/home/artica/ufdbcounters/$Y/$M/$category"));
	$MONTHC=$MONTHC+1;
	@file_put_contents("/home/artica/ufdbcounters/$Y/$M/$category", $MONTHC);
	
	$DAYC=intval(@file_get_contents("/home/artica/ufdbcounters/$Y/$M/$D/$category"));
	$DAYC=$DAYC+1;
	@file_put_contents("/home/artica/ufdbcounters/$Y/$M/$D/$category", $DAYC);
	
	$HOURC=intval(@file_get_contents("/home/artica/ufdbcounters/$Y/$M/$D/$H/$category"));
	$HOURC=$HOURC+1;
	@file_put_contents("/home/artica/ufdbcounters/$Y/$M/$D/$H/$category", $HOURC);	
	
	
	
	
	$YearC=intval(@file_get_contents("/home/artica/ufdbcounters/$Y/$domain/$ip"));
	$YearC=$YearC+1;
	@file_put_contents("/home/artica/ufdbcounters/$Y/$domain/$ip", $YearC);
	
	$MONTHC=intval(@file_get_contents("/home/artica/ufdbcounters/$Y/$M/$domain/$ip"));
	$MONTHC=$MONTHC+1;
	@file_put_contents("/home/artica/ufdbcounters/$Y/$M/$domain/$ip", $MONTHC);
	
	$DAYC=intval(@file_get_contents("/home/artica/ufdbcounters/$Y/$M/$D/$domain/$ip"));
	$DAYC=$DAYC+1;
	@file_put_contents("/home/artica/ufdbcounters/$Y/$M/$D/$domain/$ip", $DAYC);
	
	$HOURC=intval(@file_get_contents("/home/artica/ufdbcounters/$Y/$M/$D/$H/$domain/$ip"));
	$HOURC=$HOURC+1;
	@file_put_contents("/home/artica/ufdbcounters/$Y/$M/$D/$H/$domain/$ip", $HOURC);	
	
	
	
}

function paranoidmode($local_ip,$www){
	if($GLOBALS["UfdbEnableParanoidMode"]==0){return;}
	if(intval($GLOBALS["UfdbEnableParanoidBlockW"])<1000){$GLOBALS["UfdbEnableParanoidBlockW"]=5000;}
	if(intval($GLOBALS["UfdbEnableParanoidBlockC"])<1000){$GLOBALS["UfdbEnableParanoidBlockW"]=5000;}
	if(intval($GLOBALS["UfdbEnableParanoidBlockU"])==0){$GLOBALS["UfdbEnableParanoidBlockU"]=100;}
	
	if(isset($GLOBALS["UfdbEnableParanoidModeCounter"])){$GLOBALS["UfdbEnableParanoidModeCounter"]=0;}
	$GLOBALS["UfdbEnableParanoidModeCounter"]=$GLOBALS["UfdbEnableParanoidModeCounter"]+1;

	
	if(!isset($GLOBALS["MEMORY_FAMILY"][$www])){
		$f=new squid_familysite();
		$GLOBALS["MEMORY_FAMILY"][$www]=$f->GetFamilySites($www);
	}
	
	$www=$GLOBALS["MEMORY_FAMILY"][$www];
	
	$Y=date("Y");
	$M=date("M");
	$D=date("d");
	$H=date("H");
	
	$DAYC=intval(@file_get_contents("/home/artica/ufdbcounters/$Y/$M/$D/$www/$local_ip"));
//	$HOURC=intval(@file_get_contents("/home/artica/ufdbcounters/$Y/$M/$D/$H/$www/$local_ip"));
	
	
	if($DAYC>$GLOBALS["UfdbEnableParanoidBlockU"]){
		$pattern="$local_ip/$www";
		$sql="INSERT IGNORE INTO `webfilters_paranoid` (pattern,object,zDate) VALUES ('$pattern','dstdomainsrc',NOW())";
		$md5=md5($sql);
		if(!isset($GLOBALS["SQLSAVED"][$md5])){
			$q=new mysql_squid_builder();
			$q->QUERY_SQL($sql);
			if(!$q->ok){squid_admin_mysql(0, "Paranoid mode FATAL! $q->mysql_error", null,__FILE__,__LINE__);return;}
			$GLOBALS["SQLSAVED"][$md5]=true;
			squid_admin_mysql(0, "Paranoid mode bann $local_ip to $www  after $DAYC events", null,__FILE__,__LINE__);
			shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.squid.paranoid.php >/dev/null 2>&1 &");
		}
	}
	
	$DAYC=intval(@file_get_contents("/home/artica/ufdbcounters/$Y/$M/$D/$www/TOT"));
//	$HOURC=intval(@file_get_contents("/home/artica/ufdbcounters/$Y/$M/$D/$H/$www/TOT"));
	
	if($DAYC>$GLOBALS["UfdbEnableParanoidBlockW"]-1){
		$sql="INSERT IGNORE INTO `webfilters_paranoid` (pattern,object,zDate) VALUES ('$www','dstdomain',NOW())";
		$md5=md5($sql);
		if(!isset($GLOBALS["SQLSAVED"][$md5])){
			$q=new mysql_squid_builder();
			$q->QUERY_SQL($sql);
			if(!$q->ok){squid_admin_mysql(0, "Paranoid mode FATAL! $q->mysql_error", null,__FILE__,__LINE__);return;}
			$GLOBALS["SQLSAVED"][$md5]=true;
			squid_admin_mysql(0, "Paranoid mode $www is banned! after $DAYC events", null,__FILE__,__LINE__);
			shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.squid.paranoid.php >/dev/null 2>&1 &");
		}

	}
	
	$DAYC=intval(@file_get_contents("/home/artica/ufdbcounters/$Y/$M/$D/$local_ip/TOT"));
//	$HOURC=intval(@file_get_contents("/home/artica/ufdbcounters/$Y/$M/$D/$H/$local_ip/TOT"));
	
	
	if($DAYC>$GLOBALS["UfdbEnableParanoidBlockC"]-1){
		$sql="INSERT IGNORE INTO `webfilters_paranoid` (pattern,object,zDate) VALUES ('$local_ip','src',NOW())";
		$md5=md5($sql);
		if(!isset($GLOBALS["SQLSAVED"][$md5])){
			$q=new mysql_squid_builder();
			$q->QUERY_SQL($sql);
			if(!$q->ok){squid_admin_mysql(0, "Paranoid mode FATAL! $q->mysql_error", null,__FILE__,__LINE__);return;}
			$DZ=array();
			squid_admin_mysql(0, "Paranoid mode $local_ip is banned! after $DAYC events", null,__FILE__,__LINE__);
			$GLOBALS["SQLSAVED"][$md5]=true;
			shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.squid.global.access.php >/dev/null 2>&1 &");
		}
	}
	
	if($GLOBALS["UfdbEnableParanoidModeCounter"]>5){

		if(isset($GLOBALS["MEMORY_FAMILY"])){
			if(count($GLOBALS["MEMORY_FAMILY"])>5000){$GLOBALS["MEMORY_FAMILY"]=array();}
		}
		$GLOBALS["UfdbEnableParanoidModeCounter"]=0;
	}
	
}


function IfFileTime($file,$min=10){
	if(file_time_min($file)>$min){
		@unlink($file);
		@file_put_contents($file, time());
		return true;}
	return false;
}
function WriteFileCache($file){
	@unlink("$file");
	@unlink($file);
	@file_put_contents($file,"#");	
}
function events($text){
		$pid=@getmypid();
		$date=@date("H:i:s");
		events_tail($text);
		$logFile="{$GLOBALS["ARTICALOGDIR"]}/ufdbguard-tail.debug";
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		@fwrite($f, "$date [$pid]:: ".basename(__FILE__)." $text\n");
		@fclose($f);	
		}
		
function events_tail($text){
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	$pid=@getmypid();
	$date=@date("H:i:s");
	$logFile="{$GLOBALS["ARTICALOGDIR"]}/auth-tail.debug";
	$size=@filesize($logFile);
	if($size>1000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');
	$GLOBALS["CLASS_UNIX"]->events(basename(__FILE__)." $date $text");
	@fwrite($f, "$pid ".basename(__FILE__)." $date $text\n");
	@fclose($f);
}		
		
function events_ufdb_exec($text){
		events("ufdbguard tail: $text");
		$pid=@getmypid();
		$date=@date("H:i:s");
		$logFile="{$GLOBALS["ARTICALOGDIR"]}/ufdbguard-compilator.debug";
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		$textnew="$date [$pid]:: ".basename(__FILE__)." $text\n";
		@fwrite($f,$text );
		@fclose($f);	
		}		
	
function xsyslog($text){
	echo $text."\n";
	if(function_exists("WriteToSyslogMail")){WriteToSyslogMail($text, basename(__FILE__));}
	
	
}
function recover_a_database($filename){
	
	if(!is_dir(dirname($filename))){
		@mkdir(dirname($filename),0755,true);
		shell_exec("{$GLOBALS["chown"]} -R squid:squid ".dirname($filename));
	}
	
	$newfile=str_replace(".ufdb", "", $filename);
	if(!is_file($newfile)){
		events("cannot '$newfile' no such file, create it");
		@file_put_contents($newfile, "\n");
	}
	if(!is_file(dirname($newfile)."/urls")){
		@file_put_contents(dirname($newfile)."/urls", "\n");
	}
	
	if(!is_file(dirname($newfile)."/expressions")){
		@file_put_contents(dirname($newfile)."/expressions", "\n");
	}
	
	$category=str_replace("/var/lib/squidguard/", "", dirname($newfile));
	$category=str_replace("web-filter-plus/BL/", "", $category);
	$category=str_replace("blacklist-artica/", "", $category);
	$category=str_replace("personal-categories/", "", $category);
	if(strpos($category,"/phishing")>0){$category="phishing";}
	
	if(strpos($category,"/")>0){$category=basename($category);}
	
	if(preg_match("#\/(.+?)$#", $category,$re)){$category=$re[1];}
	if(strlen($category)>15){
		$category=str_replace("recreation_","recre_",$category);
		$category=str_replace("automobile_","auto_",$category);
		$category=str_replace("finance_","fin_",$category);
		if(strlen($category)>15){
			$category=str_replace("_", "", $category);
			$category=substr($category, strlen($category)-15,15);
		}
	}
	$cmd="{$GLOBALS["ufdbGenTable"]} -n -D -W -t $category -d $newfile -u ". dirname($newfile)."/urls";
	events("Category $category ".strlen($category). "chars -> $cmd");
	shell_exec($cmd);
	shell_exec("/bin/chown -R squid:squid ". dirname($newfile)." >/dev/null 2>&1 &");	
	squid_admin_mysql(0,"Ask to restart Web filtering after error on a category","$filename",__FILE__,__LINE__);
	shell_exec("{$GLOBALS["nohup"]} /etc/init.d/ufdb restart >/dev/null 2>&1 &");
	
}
function ToSyslog($text){

	$LOG_SEV=LOG_INFO;
	if(function_exists("openlog")){openlog("ufdbguard-tail", LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
	if(function_exists("closelog")){closelog();}
}


?>