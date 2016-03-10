<?php

include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.auth.tail.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.tail.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');

$GLOBALS["VERBOSE"]=false;
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$pid=getmypid();
$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
@mkdir("/etc/artica-postfix/pids",0755,true);
$pid=@file_get_contents($pidfile);
$unix=new unix();
$GLOBALS["WEBFISSUE"]=0;
$GLOBALS["WEBPROCISSUE"]=0;
$GLOBALS["ssl_crtd_crash"]=0;
$GLOBALS["NOHUP"]=$unix->find_program("nohup");
$GLOBALS["GREP"]=$unix->find_program("grep");
$GLOBALS["TAIL"]=$unix->find_program("tail");
$GLOBALS["PHP5"]=$unix->LOCATE_PHP5_BIN();
$GLOBALS["CHMOD"]=$unix->find_program("chmod");
$GLOBALS["CHOWN"]=$unix->find_program("chown");
$GLOBALS["UMOUNT"]=$unix->find_program("umount");
$GLOBALS["SCRIPT_SUFFIX"]="--script=".basename(__FILE__);
$GLOBALS["NICE"]=$unix->EXEC_NICE();
$GLOBALS["RM"]=$unix->find_program("rm");
$GLOBALS["DF"]=$unix->find_program("df");
$GLOBALS["IFCONFIG"]=$unix->find_program("ifconfig");
$GLOBALS["SQUIDBIN"]=$unix->LOCATE_SQUID_BIN();
$GLOBALS["CLASS_UNIX"]=$unix;
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if($unix->process_exists($pid,basename(__FILE__))){writelogs("Already running $pid, aborting","MAIN",__FILE__,__LINE__);events("Already running $pid, aborting ");die();}
$GLOBALS["HAARP_PORT"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaarpPort");
$GLOBALS["HAARP_ENABLE"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableHaarp");
$GLOBALS["sslcrtd_program"] = $unix->squid_locate_generic_bin("ssl_crtd");
if(!is_numeric($GLOBALS["HAARP_PORT"])){$GLOBALS["HAARP_PORT"]=0;}
if($GLOBALS["HAARP_PORT"]==0){$GLOBALS["HAARP_PORT"]=rand(35000, 64000);$GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaarpPort", $GLOBALS["HAARP_PORT"]);}


events("HAARP_ENABLE =  {$GLOBALS["HAARP_ENABLE"]}");
events("HAARP_PORT   =  {$GLOBALS["HAARP_PORT"]}");
events("Running $pid update $pidfile....");
file_put_contents($pidfile,$pid);
$sock=new sockets();
$GLOBALS["COUNTLINES"]=1;
$EnableRemoteSyslogStatsAppliance=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableRemoteSyslogStatsAppliance"));
$DisableArticaProxyStatistics=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/DisableArticaProxyStatistics"));
$EnableKavICAPRemote=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableKavICAPRemote"));
$kavicapserverEnabled=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableKavICAPRemote"));
if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
if(!is_numeric($EnableRemoteSyslogStatsAppliance)){$EnableRemoteSyslogStatsAppliance=0;}
if(!is_numeric($EnableKavICAPRemote)){$EnableKavICAPRemote=0;}
if(is_file("/etc/artica-postfix/PROXYTINY_APPLIANCE")){$DisableArticaProxyStatistics=1;}
$GLOBALS["MonitConfig"]=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidWatchdogMonitConfig")));
$UfdbguardSMTPNotifs=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/UfdbguardSMTPNotifs")));

if(!isset($GLOBALS["MonitConfig"]["ALLOW_RETURN_1CPU"])){$GLOBALS["MonitConfig"]["ALLOW_RETURN_1CPU"]=1;}
if(!isset($GLOBALS["MonitConfig"]["DisableWebFilteringNetFailed"])){$GLOBALS["MonitConfig"]["DisableWebFilteringNetFailed"]=1;}
if(!is_numeric($GLOBALS["MonitConfig"]["ALLOW_RETURN_1CPU"])){$GLOBALS["MonitConfig"]["ALLOW_RETURN_1CPU"]=1;}
if(!is_numeric($GLOBALS["MonitConfig"]["DisableWebFilteringNetFailed"])){$GLOBALS["MonitConfig"]["DisableWebFilteringNetFailed"]=1;}
if(!is_numeric($GLOBALS["MonitConfig"]["NotifyDNSIssues"])){$GLOBALS["MonitConfig"]["NotifyDNSIssues"]=0;}
if(!is_numeric($GLOBALS["MonitConfig"]["DNSIssuesMAX"])){$GLOBALS["MonitConfig"]["DNSIssuesMAX"]=1;}
if(!is_numeric($GLOBALS["MonitConfig"]["RestartWhenCrashes"])){$GLOBALS["MonitConfig"]["RestartWhenCrashes"]=1;}
if(!is_numeric($GLOBALS["MonitConfig"]["WEBPROCISSUE"])){$GLOBALS["MonitConfig"]["WEBPROCISSUE"]=3;}
if(!is_numeric($GLOBALS["MonitConfig"]["watchdog"])){$GLOBALS["MonitConfig"]["watchdog"]=1;}
if($GLOBALS["MonitConfig"]["DNSIssuesMAX"]==0){$GLOBALS["MonitConfig"]["DNSIssuesMAX"]=1;}
$GLOBAL["DNSISSUES"]=array();



$GLOBALS["EnableRemoteSyslogStatsAppliance"]=$EnableRemoteSyslogStatsAppliance;
$GLOBALS["DisableArticaProxyStatistics"]=$DisableArticaProxyStatistics;
$GLOBALS["EnableKavICAPRemote"]=$EnableKavICAPRemote;
if($GLOBALS["VERBOSE"]){events("waiting event in VERBOSE MODE....");}


$pipe = fopen("php://stdin", "r");
while(!feof($pipe)){
	$buffer .= fgets($pipe, 4096);
	try {
		if($GLOBALS["VERBOSE"]){events(" - > `$buffer`");}
		Parseline($buffer);
	} catch (Exception $e) {
		events("Fatal error on buffer $buffer");
	}
	
	$buffer=null;
}

fclose($pipe);
events("Shutdown daemon...");
die();


function Parseline($buffer){
	$buffer=trim($buffer);
	if($buffer==null){return null;}
	if(dustbin($buffer)){return null;}
	if(!isset($GLOBALS["CHMOD"])){$unix=new unix();$GLOBALS["CHMOD"]=$unix->find_program("chmod");}
	
	if($GLOBALS["VERBOSE"]){events(" - > ". __LINE__);}
	
	if(count($GLOBALS["DNSISSUES"])>1){$curdate=date("YmdHi");$curcount=count($GLOBALS["DNSISSUES"][$curdate]);unset($GLOBALS["DNSISSUES"]);$GLOBALS["DNSISSUES"][$curdate]=$curcount;}
	
	$GLOBALS["COUNTLINES"]++;
	if($GLOBALS["COUNTLINES"]>20){
		@unlink("/etc/artica-postfix/pids/squid-cache-logs.time");
		@file_put_contents("/etc/artica-postfix/pids/squid-cache-logs.time", time());
		$GLOBALS["COUNTLINES"]=0;
	}
	if(strpos($buffer, "Ignoring truncated 0-byte cache entry meta data at")>0){return;}
	if(preg_match("#FD [0-9]+ Closing HTTP connection#", $buffer)){return;}
	if(preg_match("#temporary disabling.*?digest from#", $buffer)){return;}
	if(preg_match("#kid[0-9]+\| .*?\/[0-9]+ exists#", $buffer)){return;}
	
	
	
if(preg_match("#WARNING: ParanoidBlocker.*?exited#", $buffer)){
	
	$file="/etc/artica-postfix/pids/WARNING.ParanoidBlocker.exited";
	$timefile=file_time_min($file);
	if($timefile>2){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/ParanoidBlockerEmergency", 1);
		squid_admin_mysql(0,"[Paranoid]: Paranoid mode issue, turn to Paranoid mode Emergency [action=Emergency]",$buffer,__FILE__,__LINE__);
		shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");
		@unlink($file);
		@file_put_contents($file, time());
	}
	
}	
	
if(preg_match("#ERROR: failed to start essential eCAP service: ecap:.*?clamav\?#",$buffer,$re)){
	squid_admin_mysql(0,"[eCAP]: Clamav link issue, turn to eCAP Clamav Emergency [action=Emergency]",$buffer,__FILE__,__LINE__);
	@file_put_contents("/etc/artica-postfix/settings/Daemons/eCAPClamavEmergency", 1);
	shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.ecap.php --progress --force >/dev/null 2>&1 &");
	return;
}
	
	
//*******************************************************************************************************************	
if(preg_match("#WARNING: DNS lookup for '(.+?)' failed#",$buffer,$re)){
	$dns=$re[1];
	squid_admin_mysql(0,"[DNS]: DNS issue with {$re[1]} [action=notify]","Please verify your DNS information for this host\n",__FILE__,__LINE__);
	return;
}		
	
//*******************************************************************************************************************
if(preg_match("#comm_udp_sendto: FD\s+[0-9]+,.*?\)\s+([0-9\.:]+)\s+.*?Network is unreachable#",$buffer,$re)){
	$dns=$re[1];
	squid_admin_mysql(0,"[DNS]: DNS issue with {$re[1]} Network is unreachable [action=notify]","Please verify your routing information or your gateway\n",__FILE__,__LINE__);
	return;
}	
//*******************************************************************************************************************
if(preg_match("#could not obtain winbind domain name#",$buffer,$re)){
		if(!isset($GLOBALS["could.not.obtain.winbind.domain.name"])){$GLOBALS["could.not.obtain.winbind.domain.name"]=0;}
		$GLOBALS["could.not.obtain.winbind.domain.name"]=$GLOBALS["could.not.obtain.winbind.domain.name"]+1;
		$file="/etc/artica-postfix/pids/could.not.obtain.winbind.domain.name";
		$timefile=file_time_min($file);
		
		if($GLOBALS["could.not.obtain.winbind.domain.name"]>4){
			$GLOBALS["could.not.obtain.winbind.domain.name"]=0;
			squid_admin_mysql(0,"[Active Directory]: Emergency!! could not obtain winbind domain name 5/5","Turn into Active Directory Emergency Mode",null,__FILE__,__LINE__);
			@file_put_contents("/etc/artica-postfix/settings/Daemons/ActiveDirectoryEmergency", 1);
			shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");
			@unlink($file);
			@file_put_contents($file, time());
			return;
		}
		
		
		if($timefile>1){
			squid_admin_mysql(0,"[Active Directory]: NTLM! could not obtain winbind domain name {$GLOBALS["could.not.obtain.winbind.domain.name"]}/5 [action=restart winbindd]","Artica detect this error\n$buffer\nNTLM (active directory connection) will be checked\n",__FILE__,__LINE__);
			shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} /etc/init.d/winbind restart --force");
			@unlink($file);
			@file_put_contents($file, time());
		}
		return;
	}
//*******************************************************************************************************************	
if(preg_match("#Login for user\s+\[(.+?)\].*?\[(.+?)\]@\[(.+?)\]\s+failed due to.*Access denied#",$buffer,$re)){
	$domain=$re[1];
	$user=$re[2];
	$comp=$re[3];
	$md5=md5("Access denied$domain$user$comp");
	$file="/etc/artica-postfix/pids/squid.ntml.watchdog";
	$timefile=file_time_min($file);
	if($timefile>5){
		squid_admin_mysql(1,"[Active Directory]: NTLM! Access denied for $user@$domain on computer $comp [action=verify-ntlm]","Artica detect this error\n$buffer\nNTLM (active directory connection)\n",__FILE__,__LINE__);
		shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.kerbauth.watchdog.php --force >/dev/null 2>&1 &");
		@unlink($file);
		@file_put_contents($file, time());
	}
	return;
}	
//*******************************************************************************************************************	
if(preg_match("#ipcCreate:\s+(.*?):.*?Permission denied#",$buffer,$re)){
	$filepath=trim($re[1]);
	$file="/etc/artica-postfix/pids/".md5($filepath)."Permission.denied";
	$timefile=file_time_min($file);
	if($timefile>2){
		$basename=basename($filepath);
		$dirname=dirname($filepath);
		@chmod($dirname, 0755);
		@chmod($filepath, 0755);
		@chown($filepath, "squid");
		@chgrp($filepath, "squid");
		squid_admin_mysql(1,"FATAL! Permission denied on $basename [action=verify privs]","Artica detect this error\n$buffer\nThis file will be checked\n",__FILE__,__LINE__);
		@unlink($file);
		@file_put_contents($file, time());
	}
	return;	
}
//*******************************************************************************************************************	
if(preg_match("#kid([0-9]+).*?assertion failed: Read\.cc.*#", $buffer,$re)){
	squid_admin_mysql(1,"Assertion failed on Cache, CPU#{$re[1]} [action=notify]","Artica detect this error\n$buffer\n",__FILE__,__LINE__);
	return;
}	
//*******************************************************************************************************************	
if(preg_match("#kid([0-9]+).*?assertion failed: store\.cc.*?isEmpty\(\)#", $buffer,$re)){
	squid_admin_mysql(1,"FATAL! assertion failed on Cache, CPU#{$re[1]} [action=notify]","Artica detect this error\n$buffer\n",__FILE__,__LINE__);
	return;
}
//*******************************************************************************************************************	
if(preg_match("#logfileHandleWrite: daemon:: error writing#i", $buffer,$re)){
	$file="/etc/artica-postfix/pids/logfileHandleWrite.daemon.error.writing";
	$timefile=file_time_min($file);
	if($timefile>5){
		@mkdir("/var/log/squid",0755,true);
		@chown("/var/log/squid","squid");
		@chgrp("/var/log/squid", "squid");
		squid_admin_mysql(0,"FATAL! logfileHandleWrite [action=Logs Emergency]","Artica detect this error\n$buffer\nthe Logs Emergency will be turned on\n",__FILE__,__LINE__);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/LogsWarninStop", 1);
		shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");
		@unlink($file);
		@file_put_contents($file, time());
	}
	return;
}	
//*******************************************************************************************************************
if(preg_match("#logfileOpen:.*?stdio:.*?\.log: couldn.*?t open#", $buffer,$re)){
	$file="/etc/artica-postfix/pids/logfileHandleWrite.daemon.error.writing";
	$timefile=file_time_min($file);
	if($timefile>5){
		@mkdir("/var/log/squid",0755,true);
		@chown("/var/log/squid","squid");
		@chgrp("/var/log/squid", "squid");
		squid_admin_mysql(0,"FATAL! logfileOpen [action=Logs Emergency]","Artica detect this error\n$buffer\nthe Logs Emergency will be turned on\n",__FILE__,__LINE__);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/LogsWarninStop", 1);
		shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");
		@unlink($file);
		@file_put_contents($file, time());
	}
	return;
}
//*******************************************************************************************************************

if(preg_match("#comm_open: socket failure.*?Too many open files#", $buffer,$re)){
	$file="/etc/artica-postfix/pids/comm_open.Too.many.open.files";
	$timefile=file_time_min($file);
	if($timefile>5){
		squid_admin_mysql(0,"FATAL! file descriptors issue [action=change-config]","Artica detect this error\n$buffer\nFile descriptors will be increased ( see others logs )\n",__FILE__,__LINE__);
		shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.filedescriptors.php >/dev/null 2>&1 &");
		@unlink($file);
		@file_put_contents($file, time());
	}
	return;
}
//*******************************************************************************************************************




if(preg_match("#ipcacheParse: No Address records in response to '(.*?)'#", $buffer,$re)){
	$EnableDNSMASQ=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableDNSMASQ"));
	if($EnableDNSMASQ==0){
		squid_admin_mysql(2,"DNS query failed for {$re[1]} [action=notify]","$buffer",__FILE__,__LINE__);
		return;
	}
	$file="/etc/artica-postfix/pids/ipcacheParse.No.Address.records.dnsmasq";
	$timefile=file_time_min($file);
	if($timefile>5){
		squid_admin_mysql(1,"DNS query failed for {$re[1]} [action=restart DNS service]","$buffer",__FILE__,__LINE__);
		@unlink($file);
		@file_put_contents($file, time());
		shell_exec("{$GLOBALS["NOHUP"]} /etc/init.d/dnsmasq restart >/dev/null 2>&1");
		return;
	}
	squid_admin_mysql(2,"DNS query failed for {$re[1]} [action=notify]","$buffer",__FILE__,__LINE__);
	return;
	
}

//*******************************************************************************************************************
if(preg_match("#The SSL certificate database.*?is corrupted. Please rebuild#", $buffer,$re)){
	$process=$re[1];
	$file="/etc/artica-postfix/pids/ssl_crtd.are.crashing.too.rapidly";
	@unlink($file);
	@file_put_contents($file, time());
	$file="/etc/artica-postfix/pids/Uninitialized.SSL.certificate.database.directory";
	@unlink($file);
	@file_put_contents($file, time());
	
	$file="/etc/artica-postfix/pids/SSL.certificate.database.corrupted";
	$timefile=file_time_min($file);
	if($timefile>2){
		squid_admin_mysql(0,"Corrupted SSL database [action=rebuild cache]","Artica detect this error\nthe SSL database will be rebuilded\n$buffer",__FILE__,__LINE__);
		shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.sslcrtd.flush.php >/dev/null 2>&1 &");
		@unlink($file);
		@file_put_contents($file, time());
	}	
	return;
}
//*******************************************************************************************************************
if(preg_match("#Error negotiating SSL on FD.*?error:.*?:SSL routines:SSL3_GET_SERVER_CERTIFICATE:certificate verify failed#", $buffer,$re)){
	$file="/etc/artica-postfix/pids/SSL.certificate.error.14090086";
	$timefile=file_time_min($file);
	if($timefile>2){
		squid_admin_mysql(2,"Notice: error SSL3_GET_SERVER_CERTIFICATE negotiating SSL - invalid remote certificate [action=notify]",
		"Artica detect this error\n$buffer\nThis means Proxy cannot validate SSL certificate of the remote server",__FILE__,__LINE__);
		//shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.sslcrtd.flush.php --restart >/dev/null 2>&1 &");
		@unlink($file);
		@file_put_contents($file, time());
	}	
	return;
}
//*******************************************************************************************************************
if(preg_match("#Error negotiating SSL connection on FD.*.SSL routines:SSL3_READ_BYTES:tlsv[0-9]+ alert unknown ca#", $buffer,$re)){
	$file="/etc/artica-postfix/pids/SSL.certificate.error.SSL3_READ_BYTES";
	$timefile=file_time_min($file);
	if($timefile>2){
		squid_admin_mysql(2,"Notice: error SSL3_READ_BYTES negotiating SSL - Unknown certificate [action=notify]",
		"Artica detect this error\n$buffer\nThis means Proxy cannot validate SSL certificate of the remote server",__FILE__,__LINE__);
		//shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.sslcrtd.flush.php --restart >/dev/null 2>&1 &");
		@unlink($file);
		@file_put_contents($file, time());
	}	
	return;
}
//*******************************************************************************************************************	
if(preg_match("#FATAL: The ssl_crtd helpers are crashing too rapidly, need help#", $buffer,$re)){
	$process=$re[1];
	$file="/etc/artica-postfix/pids/ssl_crtd.are.crashing.too.rapidly";
	$timefile=file_time_min($file);
	if($timefile>2){
		squid_admin_mysql(0,"ssl_crtd helpers are crashing too rapidly [action=SSL emergency]","Artica detect this error\nthe SSL method is disabled in turned to emergency mode\n$buffer",__FILE__,__LINE__);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidSSLUrgency", 1);
		shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");
		@unlink($file);
		@file_put_contents($file, time());
	}	
	return;
}
//*******************************************************************************************************************
if(preg_match("#FATAL: No valid signing SSL certificate configured for HTTPS_port\s+(.+)#", $buffer,$re)){
	$process=$re[1];
	$file="/etc/artica-postfix/pids/No.valid.signing.SSL.certificate.configured";
	$timefile=file_time_min($file);
	if($timefile>2){
		squid_admin_mysql(0,"No valid signing SSL certificate configured [action=SSL emergency]","Artica detect this error\nthe SSL method is disabled in turned to emergency mode\n$buffer",__FILE__,__LINE__);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidSSLUrgency", 1);
		shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");
		@unlink($file);
		@file_put_contents($file, time());
	}	
	return;
}
//*******************************************************************************************************************	
if(preg_match("#Uninitialized SSL certificate database directory#i", $buffer,$re)){
	$process=$re[1];
	$file="/etc/artica-postfix/pids/Uninitialized.SSL.certificate.database.directory";
	$timefile=file_time_min($file);
	if($timefile>2){
		squid_admin_mysql(0,"Uninitialized SSL certificate database directory [action=create]","Artica detect this error the SSL directory will be initialized\n$buffer",__FILE__,__LINE__);
		shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.sslcrtd.flush.php --init >/dev/null 2>&1 &");
		@unlink($file);
		@file_put_contents($file, time());
	}
	return;
}		
//*******************************************************************************************************************	
if(preg_match("#Logfile: daemon:: queue is too large; some log messages have been lost#", $buffer,$re)){
	$process=$re[1];
	$file="/etc/artica-postfix/pids/Logfile.daemon.queue.large.lost";
	$timefile=file_time_min($file);
	if($timefile>10){
		squid_admin_mysql(1,"Proxy take too many time to send log to Artica Logger [action=reload]","Artica detect this error that seems that the Artica logger take too many time to receive log\nIf this error is encountred many times, disable Artica statistics and contact our support team\n$buffer",__FILE__,__LINE__);
		shell_exec("{$GLOBALS["NOHUP"]} /etc/init.d/squid reload --cache-logs >/dev/null 2>&1");
		@unlink($file);
		@file_put_contents($file, time());
	}
	return;
}	
//*******************************************************************************************************************	
if(preg_match("#assertion failed:\s+store\.cc:.*?isEmpty\(\)#", $buffer,$re)){
	$process=$re[1];
	$file="/etc/artica-postfix/pids/assertion.failed.store.cc.isEmpty";
	$timefile=file_time_min($file);
	if($timefile>2){
		squid_admin_mysql(0,"Corrupted caches suggest to rebuild caches [action=notify]","Artica detect this error that seems some caches are corrupted\n$buffer",__FILE__,__LINE__);
		@unlink($file);
		@file_put_contents($file, time());
	}
	return;
}	
//*******************************************************************************************************************
if(preg_match("#assertion failed:\s+comm\.cc:.*?isOpen\(\)#", $buffer,$re)){
	$process=$re[1];
	$file="/etc/artica-postfix/pids/assertion.failed.comm.cc.isOpen";
	$timefile=file_time_min($file);
	if($timefile>2){
		squid_admin_mysql(0,"assertion Failed bug on isOpen [action=notify]","Artica detect this error\n$buffer",__FILE__,__LINE__);
		@unlink($file);
		@file_put_contents($file, time());
	}
	return;
}
//*******************************************************************************************************************	
if(preg_match("#Page faults with physical i\/o:#", $buffer,$re)){
	squid_admin_mysql(2,"Page faults with physical i/o, (Swap memory is used) [action=notify]",$buffer,__FILE__,__LINE__);
	return;
}			
//*******************************************************************************************************************	
if(preg_match("#WARNING: external ACL '(.+?)' queue overload#", $buffer,$re)){
	$process=$re[1];
	$file="/etc/artica-postfix/pids/external.ACL.$process.queue.overload";
	$timefile=file_time_min($file);
	if($timefile>5){
		squid_admin_mysql(1,"Plugin $process is overload [action=restart]",$buffer,__FILE__,__LINE__);
		shell_exec("{$GLOBALS["NOHUP"]} /etc/init.d/squid restart --force --cache-logs >/dev/null 2>&1 &");
		@unlink($file);
		@file_put_contents($file, time());
	}
	return;
}

//*******************************************************************************************************************
if(preg_match("#FATAL: Failed to rename log file\s+(.*?)\/swap\.state\.new#", $buffer,$re)){
	$file="/etc/artica-postfix/pids/Failed.rename.".md5($re[1]).".swap.state";
	$timefile=file_time_min($file);
	if($timefile>5){
		$dir=$re[1];
		if(is_file("{$re[1]}/swap.state.new")){
			squid_admin_mysql(0,"Proxy failed to rename cache index file [action=doit]","Artica as moved {$re[1]}/swap.state.new to {$re[1]}/swap.state\n$buffer",__FILE__,__LINE__);
			@unlink("{$re[1]}/swap.state");
			@copy("{$re[1]}/swap.state.new","{$re[1]}/swap.state");
			@chown("{$re[1]}/swap.state","squid");
			@chgrp("{$re[1]}/swap.state", "squid");
		}else{
			squid_admin_mysql(0,"Proxy failed to rename cache index file suggest to reconstruct cache",$buffer,__FILE__,__LINE__);
		}
		@unlink($file);
		@file_put_contents($file, time());
	}
	return;
}		
	
//*******************************************************************************************************************	
if(preg_match("#ERROR: cannot open \/home\/squid\/cache-default-rock\/rock:.*?No such file or directory#", $buffer,$re)){
	$file="/etc/artica-postfix/pids/default_rock";
	$timefile=file_time_min($file);
	if($timefile>5){
		squid_admin_mysql(1,"Default Rock store not created [action=create-cache]",$buffer,__FILE__,__LINE__);
		shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.rock.php --wizard >/dev/null 2>&1 &");
		@unlink($file);
		@file_put_contents($file, time());
	}
	return;
}		
//*******************************************************************************************************************	
if(preg_match("#FATAL: The ssl_crtd helpers are crashing too rapidly#",$buffer,$re)){
	$file="/etc/artica-postfix/pids/ssl_crtd.crashing";
	
	$timefile=file_time_min($file);
	
	if($timefile>20){$GLOBALS["ssl_crtd_crash"]=0;}
	
	if($timefile>2){
		if($GLOBALS["ssl_crtd_crash"]>1){
			squid_admin_mysql(0,"SSL issue on ssl_crtd after 2 attempts [action=Emergency]",$buffer,__FILE__,__LINE__);
			@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidUrgency", 1);
			shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");
			@unlink($file);
			@file_put_contents($file, time());
			return;
		}
	
		if(is_dir("/var/lib/squid/session/ssl/ssl_db")){
			squid_admin_mysql(0,"SSL issue on ssl_crtd [action=reconfigure]",$buffer."\n/var/lib/squid/session/ssl/ssl_db was removed\n",__FILE__,__LINE__);
			$GLOBALS["ssl_crtd_crash"]=$GLOBALS["ssl_crtd_crash"]+1;
			shell_exec("{$GLOBALS["RM"]} -rf /var/lib/squid/session/ssl/ssl_db/*");
			shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");
		}
	}
	
	return;
}
//*******************************************************************************************************************
if(preg_match("#FATAL: Too many queued ntlmauthenticator requests#",$buffer,$re)){
	$file="/etc/artica-postfix/pids/Too.many.queued.ntlmauthenticator.requests";
	$timefile=file_time_min($file);
	if($timefile>5){
		squid_admin_mysql(0,"NTLM: FATAL Too few ntlmauthenticator defined! [action=Reload]",$buffer,__FILE__,__LINE__);
		shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["SQUIDBIN"]} -k reconfigure");
	}

}
//*******************************************************************************************************************

if(preg_match("#FATAL: The basicauthenticator helpers are crashing too rapidly, need help#", $buffer,$re)){
	$file="/etc/artica-postfix/pids/basicauthenticator.helpers.crashing";
	$timefile=file_time_min($file);
	if($timefile>5){
		squid_admin_mysql(0,"NTLM/Basic: FATAL basicauthenticator crashed [action=Emergency]",$buffer,__FILE__,__LINE__);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/BasicAuthenticatorEmergency", 1);
		shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");
		@unlink($file);
		@file_put_contents($file, time());
	}
}
//*******************************************************************************************************************
if(preg_match("#comm_udp_sendto: FD 13,.*?family=2.*?([0-9\.]+):53.*?Invalid argument#", $buffer,$re)){
	$file="/etc/artica-postfix/pids/comm_udp_sendto.FD13.53.Invalid.argument";
	$timefile=file_time_min($file);
	if($timefile>5){
		squid_admin_mysql(1,"DNS issue on {$re[1]} [action=warn]",$buffer,__FILE__,__LINE__);
		@unlink($file);
		@file_put_contents($file, time());
	}
	return;
}	

if(preg_match("#FATAL: The store_id helpers are crashing too rapidly#", $buffer,$re)){
	$file="/etc/artica-postfix/pids/storeid_helper_crash";
	$timefile=file_time_min($file);
	if($timefile>5){
		squid_admin_mysql(1,"HyperCache Helper issue [action=disable]",$buffer,__FILE__,__LINE__);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/StoreIDUrgency", 1);
		@chmod("/etc/artica-postfix/settings/Daemons/StoreIDUrgency", 0777);
		shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");
		@unlink($file);
		@file_put_contents($file, time());
	}
	return;
}
//*******************************************************************************************************************
if(preg_match("#assertion failed: PeerConnector.*?sslContext#", $buffer,$re)){
	$file="/etc/artica-postfix/pids/squid.assertion.failed.PeerConnector.sslContext";
	$timefile=file_time_min($file);
	if($timefile>10){
		squid_admin_mysql(0,"SSL Issue, proxy is crashing, turn into emergency mode [action=Emergency]",$buffer,__FILE__,__LINE__);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidUrgency", 1);
		shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");
		@unlink($file);
		@file_put_contents($file, time());
		return;
	}
}
//*******************************************************************************************************************	
if(preg_match("#urlParse: Illegal hostname '(.+?)'#", $buffer,$re)){
	squid_admin_mysql(0,"Illegal hostname {$re[1]} please check the visible hostname [action=warn]",$buffer,__FILE__,__LINE__);
	return;
}	
//*******************************************************************************************************************	
if(preg_match("#logfileHandleWrite:.*?error(.+)#", $buffer,$re)){
	squid_admin_mysql(1,"Artica logger issue {$re[1]} [action=warn]",$buffer,__FILE__,__LINE__);
	return;
}	
//*******************************************************************************************************************	
if(preg_match("#FATAL: Cannot open SNMP receiving Port#", $buffer,$re)){
	squid_admin_mysql(0,"SNMP option issue! [action=disable SNMP]",$buffer,__FILE__,__LINE__);
	shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.watchdog.php --disable-snmp >/dev/null 2>&1");
	return;
	
}
//*******************************************************************************************************************
if(preg_match("#FATAL: The\s+(.*?)\s+helpers are crashing too rapidly, need help#", $buffer,$re)){
	
	if($re[1]=="redirector"){
		squid_admin_mysql(0,"Webfiltering client issue! [action=emergency!]",
		"The proxy claims about crashing Webilter client, Artica pass your proxy service into Web filtering emergency mode!\n$buffer",__FILE__,__LINE__);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidUFDBUrgency", 1);
		shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");
		return;
	}
	
	if(strtolower($re[1])=="mactouid"){
		squid_admin_mysql(0,"MacToUid client issue! [action=emergency!]",
		"The proxy claims about crashing MAC-TO-UID client, Artica pass your proxy service into Mac-to-uid emergency mode!\n$buffer",__FILE__,__LINE__);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/MacToUidUrgency", 1);
		shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");
		return;
		
	}
	
	
	
	squid_admin_mysql(0,"Helper: Generic [{$re[1]}] issue! [action=emergency!]",
	"The proxy claims about an helper that crashing, Artica pass your proxy service into emergency mode!\n$buffer"
	,__FILE__,__LINE__);
	@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidUrgency", 1);
	shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");
}
//*******************************************************************************************************************
if(preg_match("#FATAL: I don't handle this error well#", $buffer,$re)){
	$file="/etc/artica-postfix/pids/squid.did.handle.error.well";
	$timefile=file_time_min($file);
	if($timefile>10){
		squid_admin_mysql(0,"Error that proxy did not handle very well! [action=restart-forced]",$buffer,__FILE__,__LINE__);
		shell_exec("{$GLOBALS["NOHUP"]} /etc/init.d/squid restart --force --cache-logs >/dev/null 2>&1 &");
		@unlink($file);
		@file_put_contents($file, time());
	}
	return;
	
}
//*******************************************************************************************************************	
if(preg_match("#WARNING: All [0-9]+\/([0-9]+)\s+BasicFakeAuth processes are busy#", $buffer,$re)){
		$file="/etc/artica-postfix/pids/squid.external.BasicFakeAuth.queue.overload";
		$timefile=file_time_min($file);
		if($timefile>3){
			$SquidClientParams=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientParams")));
			if(!is_numeric($SquidClientParams["external_acl_children"])){$SquidClientParams["external_acl_children"]=5;}
			if(!is_numeric($SquidClientParams["external_acl_startup"])){$SquidClientParams["external_acl_startup"]=1;}
			if(!is_numeric($SquidClientParams["external_acl_idle"])){$SquidClientParams["external_acl_idle"]=1;}
			$SquidClientParams["external_acl_children"]=$SquidClientParams["external_acl_children"]+2;
			$SquidClientParams["external_acl_startup"]=$SquidClientParams["external_acl_startup"]+2;
			$SquidClientParams["external_acl_idle"]=$SquidClientParams["external_acl_idle"]+2;
			$GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(base64_encode(serialize($SquidClientParams)), "SquidClientParams");
			shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");
						
			$text="$buffer
			New parameters has been set and the proxy service was reconfigurer with:
			external_acl_children = {$SquidClientParams["external_acl_children"]}
			external_acl_startup  = {$SquidClientParams["external_acl_startup"]}
			external_acl_idle     = {$SquidClientParams["external_acl_idle"]}
			";
				
			squid_admin_mysql(1,"BasicFakeAuth ACL queue overloaded increase it","$text");
			@unlink($file);
			@file_put_contents($file, time());
			return;
		}
	return;
}	
//*******************************************************************************************************************

if(preg_match("#\/swap\.state:.*?Read-only file system#", $buffer,$re)){
	if(TimeStampTTL(__LINE__,2)){
		squid_admin_mysql(0,"{$re[1]} File system in read-only! suggest to reboot the server !",$buffer,__FILE__,__LINE__);
	}
	return;
}	
//*******************************************************************************************************************	
if(preg_match("#FATAL: Unable to open HTTP Socket#", $buffer,$re)){
		if(TimeStampTTL(__LINE__,5)){
			squid_admin_mysql(0,"Port conflicts ! [action: force-restart]",null,__FILE__,__LINE__);
			shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.watchdog.php --restart --force --kill-all {$GLOBALS["SCRIPT_SUFFIX"]} >/dev/null 2>&1 &");
			return;
		}
		squid_admin_mysql(0,"Port conflicts ! [action: none - Timed out]",null,__FILE__,__LINE__);
}
//*******************************************************************************************************************	
if(preg_match("#FATAL: UFSSwapDir::openLog: Failed to open swap log#", $buffer,$re)){
	squid_admin_mysql(0,"UFSSwapDir Proxy cannot open it's cache !! [action: None]",null,__FILE__,__LINE__);
	return;
}
//*******************************************************************************************************************
if(preg_match("#Ipc::Mem::Segment::open failed to shm_open.*?No such file or directory#", $buffer,$re)){
	squid_admin_mysql(0,"Ipc::Mem::Segment::open: SMP Proxy cannot be linked to the system !! [action=restart]",$buffer,__FILE__,__LINE__);
	shell_exec("{$GLOBALS["NOHUP"]} /etc/init.d/squid restart --cache-logs >/dev/null 2>&1 &");
	return;
}




//*******************************************************************************************************************
if(preg_match("#kid([0-9]+).*?Preparing for shutdown after\s+([0-9]+)\s+requests#", $buffer,$re)){
	if(TimeStampTTL(__LINE__,2)){
		squid_admin_mysql(2,"Process CPU.{$re[1]} is stopping after {$re[1]} requests [action: None]",$buffer,__FILE__,__LINE__);
	}
	return;
}
//*******************************************************************************************************************	
if(preg_match("#kid([0-9]+).*?Squid Cache.*?: Exiting normally#", $buffer,$re)){
	$file="/etc/artica-postfix/pids/kid.{$re[1]}.Exiting.normally";
	$timefile=file_time_min($file);
	if($timefile>3){
		squid_admin_mysql(2,"Process CPU.{$re[1]} was stopped [action: None]",$buffer,__FILE__,__LINE__);
		@unlink($timefile);
		@file_put_contents($timefile, time());
	}
	return;
}
//*******************************************************************************************************************
if(preg_match("#kid([0-9]+).*?Process Roles:\s+(.+?)#", $buffer,$re)){
	$file="/etc/artica-postfix/pids/kid.{$re[1]}.Process.Roles";
	$timefile=file_time_min($file);
	if($timefile>2){
		squid_admin_mysql(2,"Process CPU.{$re[1]} was started in {$re[2]} mode [action: None]",null,__FILE__,__LINE__);
		@unlink($timefile);
		@file_put_contents($timefile, time());
	}
	return;
}

//*******************************************************************************************************************
if(preg_match("#ERROR: NTLM Authentication validating user\. Error returned 'BH NT_STATUS_ACCESS_DENIED'#", $buffer,$re)){
	if(TimeStampTTL(__LINE__,10)){
		squid_admin_mysql(0,"Active directory link failed NT_STATUS_ACCESS_DENIED [action: Relink]",$buffer,__FILE__,__LINE__);
		shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.kerbauth.php --join --force >/dev/null 2>&1 &");
	}
	return;	
	
}
//*******************************************************************************************************************
if(preg_match("#Detected DEAD Parent: Peer([0-9]+)#", $buffer,$re)){
	if(TimeStampTTL(__LINE__,2)){
		squid_admin_mysql(0,"Parent proxy number {$re[1]} is dead [action: None]",null,__FILE__,__LINE__);
		shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.watchdog.php --peer-status --force >/dev/null 2>&1 &");
		shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.watchdog.php --dead-parent \"{$re[1]}\" >/dev/null 2>&1 &");
	}
	return;
}	
//*******************************************************************************************************************
if(preg_match("#TCP connection to (.+?)\/([0-9]+)\s+failed#", $buffer,$re)){
	if(TimeStampTTL(__LINE__,2)){
		squid_admin_mysql(1,"Connecting to Peer {$re[1]}:{$re[2]} failed [action: None]",null,__FILE__,__LINE__);
		shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.watchdog.php --peer-status --force >/dev/null 2>&1 &");
	}
	return;
}
//*******************************************************************************************************************	
if(preg_match("#commBind: Cannot bind socket FD [0-9]+ to ([0-9\.]+):.*?Cannot assign requested address#",$buffer,$re)){
	if(TimeStampTTL(__LINE__,5)){
		squid_admin_mysql(0,"Proxy service Unable to hook {$re[1]} network address [action: Reconfigure]",
		"Proxy claim\n$buffer\nArtica will reconfigure the service\nPlease follow this issue, newt check will be in 5mn",__FILE__,__LINE__);
		shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");
	}
	return;
}
//*******************************************************************************************************************
if(preg_match("#Squid Cache.*?:\s+Exiting normally#",$buffer,$re)){
	if(TimeStampTTL(__LINE__,1)){
		squid_admin_mysql(2,"Proxy service was normally stopped","Proxy claim\n$buffer\n",__FILE__,__LINE__);
	}
	return;
}
//*******************************************************************************************************************	
if(preg_match("#FATAL: Received Bus Error...dying#",$buffer,$re)){
	if(TimeStampTTL(__LINE__,5)){
		squid_admin_mysql(0,"Bus Error !","Proxy claim\n$buffer\nThis caused by an hardware issue or a proxy bug ( please contact our support team)\nArtica will try to start Proxy if it is not running",__FILE__,__LINE__);
		shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.watchdog.php --start {$GLOBALS["SCRIPT_SUFFIX"]} >/dev/null 2>&1 &");
	}
	return;	
}
//*******************************************************************************************************************	
if(preg_match("#FATAL: Unable to open HTTPS Socket#",$buffer,$re)){
	if(TimeStampTTL(__LINE__,5)){
		squid_admin_mysql(0,"Port conflict issue on HTTPS socket -> Restart proxy service","$buffer\n",__FILE__,__LINE__);
		shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.watchdog.php --restart --force {$GLOBALS["SCRIPT_SUFFIX"]} >/dev/null 2>&1 &");
	}
	return;	
}

if(preg_match("#ERROR: URL-rewrite produces invalid request:#",$buffer,$re)){
	squid_admin_mysql(0,"Redirector Web filter miss-configured","$buffer\n",__FILE__,__LINE__);
	return;	
}	
//*******************************************************************************************************************	
if(preg_match("#assertion failed: mem\.cc:([0-9]+):\s+\"(.+?)\"#",$buffer,$re)){
	if(TimeStampTTL(__LINE__,1)){
		squid_admin_mysql(1,"Memory assertion failed mem.cc Line {$re[1]} - {$re[2]}","$buffer\n",__FILE__,__LINE__);
	}
	return;	
}	
//*******************************************************************************************************************	
if(preg_match("#optional ICAP service is down after an options fetch failure:\s+icap:\/\/(.+?):([0-9]+)\/(.+?)\s+#",$buffer,$re)){
	if(TimeStampTTL(__LINE__,2)){
		squid_admin_mysql(1,"ICAP service {$re[3]} down {$re[1]}:{$re[2]}","$buffer\n",__FILE__,__LINE__);
	}
	return;	
}	
//*******************************************************************************************************************	
if(preg_match("#logfileHandleWrite: daemon:: error writing\s+(.*)#",$buffer,$re)){
	squid_admin_mysql(2,"Error writing to Artica statistics dameon","$buffer\n",__FILE__,__LINE__);
	return;	
}
//*******************************************************************************************************************
if(preg_match("#kid([0-9]+)\|\s+Reconfiguring Squid Cache#",$buffer,$re)){
	events("CPU.{$re[1]} Reconfiguring Proxy service",__LINE__);
	squid_admin_mysql(2,"CPU.{$re[1]} Reconfiguring Proxy service","$buffer\n",__FILE__,__LINE__);
}
//*******************************************************************************************************************
if(preg_match("#WARNING: Unexpected change of authentication scheme.*?client\s+(.*?)\)#",$buffer,$re)){
	events("{$re[1]} Unexpected change of authentication scheme",__LINE__);
	squid_admin_mysql(1,"Computer {$re[1]} Unexpected change of authentication scheme","$buffer\n",__FILE__,__LINE__);
	return;
}
//*******************************************************************************************************************	
if(preg_match("#WARNING: All [0-9]+\/([0-9]+) redirector processes are busy#",$buffer,$re)){
	events("{$re[1]} Process reached for Web filtering daemon, Artica will increase the value...",__LINE__);
	squid_admin_mysql(2,"{$re[1]} Process reached for Web filtering daemon, Artica will increase the value","$buffer\n",__FILE__,__LINE__);
	shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.watchdog.php --redirectors-more >/dev/null 2>&1 &");
	return;
}	
//*******************************************************************************************************************	
if(preg_match("#\| WARNING: HTTP header contains NULL characters \{Host:\s+(.+)#i", $buffer,$re)){
	events("{$re[1]} seems to crash",__LINE__);
	squid_admin_mysql(0,"Webserver \"{$re[1]}\" seems to crashing","script of this source server seems broken\n$buffer\n",__FILE__,__LINE__);
	return;
}	
// *******************************************************************************************************************	
if(preg_match("#squidaio_queue_request: WARNING - Queue congestion#i", $buffer)){
	events("squidaio_queue_request error issue ->`$buffer`",__LINE__);
	if(TimeStampTTL(__LINE__,2)){
		squid_admin_mysql(2,"Queue congestion","$buffer\n".@file_get_contents("/usr/share/artica-postfix/ressources/databases/SQUID_ERROR_QUEUE_CONGESTION.db"),__FILE__,__LINE__);
	}
	return;
}
// *******************************************************************************************************************	
	if(preg_match("#WARNING: Consider increasing the number of ads_group processes in your config file#i", $buffer)){
		events("ads_group error issue ->`$buffer`",__LINE__);
		if(TimeStampTTL(125,2)){
			squid_admin_mysql(0,"Queue overload on external ACL ads_group add more.","$buffer\n",__FILE__,__LINE__);
			shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.watchdog.php --external-acl-children-more >/dev/null 2>&1 &");
		}	
		return;	
	}
// *******************************************************************************************************************	
	if(preg_match("#WARNING: external ACL 'ads_group' queue overload. Using stale result#i", $buffer)){
			events("ads_group error issue ->`$buffer`",__LINE__);
			if(TimeStampTTL(125,2)){
				squid_admin_mysql(0,"Queue overload on external ACL ads_group add more.","$buffer\n",__FILE__,__LINE__);
				shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.watchdog.php --external-acl-children-more >/dev/null 2>&1 &");
			}
	}
// *******************************************************************************************************************	
	if(preg_match("#PHP Startup: Unable to load dynamic library#i", $buffer)){
		shell_exec("/usr/share/artica-postfix/bin/artica-install --php-include");
		shell_exec("{$GLOBALS["NOHUP"]} /etc/init.d/squid reload --cache-logs >/dev/null 2>&1");
	}
	
// *******************************************************************************************************************	
	if(preg_match("#errorTryLoadText:\s+'(.+?)':.*?Permission denied#",$buffer,$re)){
		@chown($re[1],"squid");
		return;
	}
// *******************************************************************************************************************	
	if(preg_match("#PHP Warning.*?Unable to load dynamic library '(.+?)'.*?undefined symbol#",$buffer,$re)){
		if(is_file($re[1])){
			@copy($re[1], $re[1].".".time().".bak");
			@unlink($re[1]);
			shell_exec("{$GLOBALS["NOHUP"]} /etc/init.d/artica-webconsole restart >/dev/null 2>&1");
		}
		return;
	}
	
// *******************************************************************************************************************	
	if(preg_match("#FATAL: Rock cache_dir at\s+(.+?)\s+failed to open db file#",$buffer,$re)){
		events("disk write error issue ->`$buffer`",__LINE__);
		if(TimeStampTTL(__LINE__,10)){
			squid_admin_mysql(0,"Rock cache_dir issue","$buffer\nrun cache -Z",__FILE__,__LINE__);
			shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.smp.php --squid-z-fly >/dev/null 2>&1 &");
		}
		return;
	}

// *******************************************************************************************************************	
	if(preg_match("#basic_ldap_auth: WARNING, could not bind to binddn.*?Can.*?contact LDAP server#",$buffer,$re)){
		events("basic_ldap_auth error issue ->`$buffer`",__LINE__);
		if(TimeStampTTL(__LINE__,2)){
			squid_admin_mysql(0,"Remote LDAP server issue unable to contact specified LDAP Port","$buffer\n",__FILE__,__LINE__);
		}
		return;
	}
	
// *******************************************************************************************************************	
	if(preg_match("#diskHandleWrite:\s+FD\s+.*?disk write error:.*?No space left on device#",$buffer,$re)){
		events("disk write error issue ->`$buffer`",__LINE__);
		if(TimeStampTTL(__LINE__,10)){
			squid_admin_mysql(0,"No space left on device, rebuild all caches","$buffer\nIt seems the one of caches or all caches handle your disk space.\nReconstruct caches procedure as been executed.",__FILE__,__LINE__);
			shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.rebuild.caches.php >/dev/null 2>&1 &");
		}
		return;
	}
	
	if(preg_match("#commBind:\s+Cannot bind socket FD\s+[0-9]+\s+to\s+\[::1\]#",$buffer,$re)){
		if(TimeStampTTL(__LINE__,1)){
			events("127.0.0.1 issue",__LINE__);
			squid_admin_mysql(1,"IPV6 Loopback interface issue - {$re[1]} [action=notify]","$buffer");
		}	
		return;
	}

	
// *******************************************************************************************************************	
	if(preg_match("#commBind:\s+Cannot bind socket FD\s+[0-9]+\s+to.*?(::1|127\.0\.0\.1)\]:.*?Cannot assign requested address#",$buffer,$re)){
		if(TimeStampTTL(__LINE__,1)){
			events("127.0.0.1 issue",__LINE__);
			squid_admin_mysql(0,"Loopback interface issue - {$re[1]}","$buffer\nArtica will reconfigure loopback");
			shell_exec("{$GLOBALS["IFCONFIG"]} lo 127.0.0.1 netmask 255.255.255.0 up >/dev/null 2>&1");
			shell_exec("/etc/init.d/squid reload {$GLOBALS["SCRIPT_SUFFIX"]}");
		}
		return;
	}
// *******************************************************************************************************************	
	if(preg_match("#Uninitialized SSL certificate database directory:\s+(.+?)\.#",$buffer,$re)){
		if(TimeStampTTL(__LINE__,1)){
			$Directory=$re[1];
			events("SSL issue on `$Directory`",__LINE__);
			squid_admin_mysql(0,"Uninitialized SSL certificate database directory","$buffer\nArtica will initalize `$Directory` with `{$GLOBALS["sslcrtd_program"]}`");
			shell_exec("{$GLOBALS["RM"]} -rf $Directory >/dev/null 2>&1");
			shell_exec("{$GLOBALS["sslcrtd_program"]} -c -s $Directory >/dev/null 2>&1");
			shell_exec("{$GLOBALS["CHOWN"]} -R squid:squid $Directory >/dev/null 2>&1");
			shell_exec("{$GLOBALS["NOHUP"]} /etc/init.d/squid start --cache-logs >/dev/null 2>&1 &");
			return;
		}
	}
// *******************************************************************************************************************	
	if(preg_match("#FATAL: Ipc::Mem::Segment::open failed to shm_open.*?squid-squid-page-pool\.shm.*?No such file or directory#",$buffer)){
		if(TimeStampTTL(__LINE__,1)){
			events("Ipc::Mem::Segment:: issue on squid-squid-page-pool.shm -> restart".__LINE__);
			squid_admin_mysql(0,"SMP Memory issue","$buffer\nThe proxy service will be restarted",__FILE__,__LINE__);
			shell_exec("{$GLOBALS["NOHUP"]} /etc/init.d/squid restart --cache-logs >/dev/null 2>&1 &");
		}
		events("Ipc::Mem::Segment:: issue on squid-squid-page-pool.shm need to wait 1mn".__LINE__);
	}
// *******************************************************************************************************************	
	if(preg_match("#TCP connection to\s+127\.0\.0\.1\/{$GLOBALS["HAARP_PORT"]}\s+failed#",$buffer)){
		events("HTTP connection failed to Haarp cache system times Line:".__LINE__);
		if(TimeStampTTL(__LINE__,3)){
			@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableHaarp", 0);
			shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --build >/dev/null 2>&1 &");
			squid_admin_mysql(0,"Haarp issues","Proxy service have issues with haarp,disable it\n$buffer\n the Haarp service will be restarted",__FILE__,__LINE__);
			shell_exec("{$GLOBALS["NOHUP"]} /etc/init.d/haarp restart >/dev/null 2>&1 &");
		}
		return;
	}
	
	// *******************************************************************************************************************	
	if(preg_match("#Detected DEAD Parent.*?HaarpPeer#",$buffer)){
		events("HTTP connection failed to Haarp cache system times Line:".__LINE__);
		if(TimeStampTTL(__LINE__,3)){
			@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableHaarp", 0);
			shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --build >/dev/null 2>&1 &",__FILE__,__LINE__);
			squid_admin_mysql(0,"Haarp issues","Proxy service have issues with haarp,service will be disabled\n$buffer\n the Haarp service will be restarted");
			shell_exec("{$GLOBALS["NOHUP"]} /etc/init.d/haarp restart >/dev/null 2>&1 &");
		}
		return;
	}
	
// *******************************************************************************************************************	

	if(preg_match("#cannot connect to ufdbguardd daemon socket#",$buffer,$re )){
		if($GLOBALS["MonitConfig"]["watchdog"]==0){
			squid_admin_mysql(1,"Web filtering issue -> ufdbguardd daemon socket [action=none]","$buffer\nWeb filtering will be disabled when reach 4",__FILE__,__LINE__);
			return;
		}
		if($GLOBALS["MonitConfig"]["DisableWebFilteringNetFailed"]==0){
			squid_admin_mysql(1,"Web filtering issue -> ufdbguardd daemon socket [action=none]","$buffer\nWeb filtering will be disabled when reach 4",__FILE__,__LINE__);
			return;
		}
		
		if(TimeStampTTL(__LINE__,5)){
			$GLOBALS["WEBFISSUE"]++;
			squid_admin_mysql(0,"Web filtering issue {$GLOBALS["WEBFISSUE"]}/4","$buffer\nWeb filtering will be disabled when reach 2 times each 5mn",__FILE__,__LINE__);
			if($GLOBALS["WEBFISSUE"]>2){
				squid_admin_mysql(0,"Web filtering issue MAX reached [action=remove]","$buffer\nWeb filtering will be disabled",__FILE__,__LINE__);
				shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --disableUFDB >/dev/null 2>&1 &");
				$GLOBALS["WEBFISSUE"]=0;
			}
		}
		return; 
	}
	
	// *******************************************************************************************************************	
	if(preg_match("#ERROR: URL-rewrite produces invalid request#",$buffer,$re )){
		if($GLOBALS["MonitConfig"]["watchdog"]==0){return;}
		if($GLOBALS["MonitConfig"]["DisableWebFilteringNetFailed"]==0){return;}
		if(TimeStampTTL(__LINE__,5)){
			squid_admin_mysql(0,"Web filtering compatiblity issue","Proxy claim:\n$buffer\nWeb filtering will be disabled for compatibilities issues\nreturn back to 3.3x versions\nWe currently investigate on the compatibility");
			shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --disableUFDB >/dev/null 2>&1 &");
		}
		return; 
	}
	
	// *******************************************************************************************************************	
	if(preg_match("#TCP connection to (.+?)\/([0-9]+)\s+failed#",$buffer,$re )){
		if(!isset($GLOBALS["CNXFAILED"][$re[1]])){$GLOBALS["CNXFAILED"][$re[1]]=0;}
		$GLOBALS["CNXFAILED"][$re[1]]=$GLOBALS["CNXFAILED"][$re[1]]+1;
		events("{$re[1]} HTTP connection failed ({$GLOBALS["CNXFAILED"][$re[1]]}) times Line:".__LINE__);
	}
// *******************************************************************************************************************	
	if(preg_match("#ipcacheParse: No Address records in response to '(.+?)'#", $buffer,$re)){
		if($GLOBALS["MonitConfig"]["NotifyDNSIssues"]==0){reset($GLOBAL["DNSISSUES"]);return;}
		$curdate=date("YmdHi");
		$GLOBALS["DNSISSUES"][$curdate][$re[1]]=true;
		if(count($GLOBALS["DNSISSUES"][$curdate]+1)>$GLOBALS["MonitConfig"]["DNSIssuesMAX"]){
			while (list ($num, $ligne) = each ($GLOBALS["DNSISSUES"][$curdate]) ){$t[]=$num;}
			reset($GLOBALS["DNSISSUES"]);
			$report=NETWORK_REPORT();
			squid_admin_mysql(1,"DNS issues","Proxy service have issues to Resolve these websites::\n".@implode("\n", $t)."\n$report",__FILE__,__LINE__);
			
		}
		return;
	}
// *******************************************************************************************************************	
	if(preg_match("#commBind: Cannot bind socket FD.*?Address already in use#", $buffer)){
		if(TimeStampTTL(__LINE__,5)){
			squid_admin_mysql(0,"Bind Socket issue","$buffer\nProxy service have issues to bind port\n$buffer\nArtica will restart the proxy service",__FILE__,__LINE__);
			
			shell_exec("{$GLOBALS["NOHUP"]} /etc/init.d/squid start --crashed >/dev/null 2>&1 &");
		}
		return;
	}	
	// *******************************************************************************************************************	
	
	if(preg_match("#FATAL: kid[0-9]+ registration timed out#", $buffer)){
		@mkdir("/var/run/squid",0755,true);
		shell_exec("{$GLOBALS["CHOWN"]} -R squid:squid /var/run/squid");
		$GLOBALS["WEBPROCISSUE"]++;
		

		if(TimeStampTTL(__LINE__,2)){
			squid_admin_mysql(1,"{$GLOBALS["WEBPROCISSUE"]} times Processor issue!!: SMP is disabled, you should consider return back to 1 CPU","The detected error was:\n$buffer\n",__FILE__,__LINE__);
				
		}
		return;
	}
	// *******************************************************************************************************************	

if(preg_match("#abandoning local=(.*?):.*?remote=(.*?):#", $buffer,$re)){
		$client=$re[2];
		$hostname=gethostbyaddr($re[2]);
		events("$client [$hostname] KeepAlive session was disconnected from this user Line:".__LINE__);
		return;
	}
	
//*******************************************************************************************************************	
	if(preg_match("#ERROR:\s+(.+?)\/00: \(2\) No such file or directory#", $buffer,$re)){
		if(TimeStampTTL(__LINE__,2)){
			$dirname=trim($re[1]);
			events("$dirname -> no cache created -> squid-z Line:".__LINE__);
			shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.smp.php --squid-z-fly >/dev/null 2>&1 &");
			return;
		}
	}
// *******************************************************************************************************************	

	if(preg_match("#FATAL:\s+Failed to make swap directory\s+(.+?):.*?13.*?Permission denied#", $buffer,$re)){
		$dirname=trim($re[1]);
		$basename=basename($dirname);
		if(is_numeric($basename)){$dirname=dirname($dirname);}
		events("$dirname -> squid:squid {$re[1]}/0755 Permisssions... Line:".__LINE__);
		@chown($dirname, "squid");
		@chgrp($dirname, "squid");
		@chmod($re[1], 0755);
		events("$dirname ($basename) Permisssions denied... Line:".__LINE__);
		return;
		
	}
// *******************************************************************************************************************	
	
	if(preg_match("#\/home\/squid\/cache\/MemBooster([0-9]+)\/.*?No space left on device#",$buffer,$re)){
		events("No space left on Memory Booster MemBooster{$re[1]}: line:".__LINE__);
		if(TimeStampTTL(__LINE__.$re[1],3)){
			squid_admin_mysql(1,"Memory cache full","The cache memory MemBooster({$re[1]}) will be flushed to 0 and the proxy service will be restarted",__FILE__,__LINE__);
			shell_exec("{$GLOBALS["UMOUNT"]} -l /home/squid/cache/MemBooster{$re[1]}");
			shell_exec("{$GLOBALS["NOHUP"]} /etc/init.d/squid restart --force --cache-logs 2>&1");
		}
		return;
	}
// *******************************************************************************************************************	
	if(preg_match("#FATAL: Write failure.*?check your disk space#", $buffer)){
		events("Fatal: Write failure: Disk space over limit (cannot determine which path) Line:".__LINE__);
		if(TimeStampTTL(__LINE__,10)){
			exec("{$GLOBALS["DF"]} -h 2>&1",$defres);
			squid_admin_mysql(0,"Write failure - disk space issue","check your disk space for Proxy cache service.\nHere the status of your storage system:".@implode("\n", $defres),__FILE__,__LINE__);
		}
		return;
	}
	
// *******************************************************************************************************************	

	
	
	if(preg_match("#WARNING: Disk space over limit#", $buffer)){
		if(TimeStampTTL(__LINE__,5)){
			squid_admin_mysql(1,"Disk space over limit","$buffer\nswapstate will be executed");
			events("Disk space over limit Line: refresh swap.state".__LINE__);
			shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.watchdog.php --swapstate >/dev/null 2>&1 &");
		}
		return;
	}
// *******************************************************************************************************************	
	if(preg_match("#errorTryLoadText: '(.+?)':\s+\(13\) Permission denied#i", $buffer,$re)){
		if(!is_file($re[1])){@file_put_contents($re[1], "\n");}
		@chmod($re[1], 0777);
		@chown($re[1], "squid");
		@chown(dirname($re[1]), "squid");
		return;
		
	}
// *******************************************************************************************************************	
	if(preg_match("#Squid Cache.*?Terminated abnormally#", $buffer)){
		
		if($GLOBALS["MonitConfig"]["RestartWhenCrashes"]==0){
			exec("{$GLOBALS["TAIL"]} -n 50 /var/log/squid/cache.log 2>&1",$results);
			squid_admin_mysql(0,"Squid Cache Terminated Abnormally","squid-cache claim\r\n$buffer\r\nThis just a notification, Artica will checks your settings and determine what is the issue...\n".@implode("\n", $results),__FILE__,__LINE__);
			return;
		}
		
		
		if(TimeStampTTL(__LINE__,5)){
			exec("{$GLOBALS["TAIL"]} -n 50 /var/log/squid/cache.log 2>&1",$results);
			squid_admin_mysql(1,"Squid Cache Terminated Abnormally","squid-cache claim\r\n$buffer\r\nArtica will restart the proxy service.\nPiece of logs:".@implode("\n", $results));
			shell_exec("{$GLOBALS["NOHUP"]} /etc/init.d/squid start --crashed >/dev/null 2>&1 &");
		}
		
		return;		
		
	}
// *******************************************************************************************************************	
	if(preg_match("#WARNING: external ACL.*?queue overload. Request rejected#i", $buffer,$re)){
		$file="/etc/artica-postfix/pids/squid.external.ACL.queue.overload";
		$timefile=file_time_min($file);
		if($timefile>3){
			$SquidClientParams=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientParams")));
			if(!is_numeric($SquidClientParams["external_acl_children"])){$SquidClientParams["external_acl_children"]=5;}
			if(!is_numeric($SquidClientParams["external_acl_startup"])){$SquidClientParams["external_acl_startup"]=1;}
			if(!is_numeric($SquidClientParams["external_acl_idle"])){$SquidClientParams["external_acl_idle"]=1;}
			$SquidClientParams["external_acl_children"]=$SquidClientParams["external_acl_children"]+1;
			$SquidClientParams["external_acl_startup"]=$SquidClientParams["external_acl_startup"]+1;
			$SquidClientParams["external_acl_idle"]=$SquidClientParams["external_acl_idle"]+1;
			$GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(base64_encode(serialize($SquidClientParams)), "SquidClientParams");
			shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");
			events("external ACL (queue overload) Increase: external_acl_children:{$SquidClientParams["external_acl_children"]},external_acl_startup:{$SquidClientParams["external_acl_startup"]}, external_acl_idle:{$SquidClientParams["external_acl_idle"]} Line:".__LINE__);
			
			$text="$buffer
			New parameters has been set and the proxy service was reconfigurer with:
			external_acl_children = {$SquidClientParams["external_acl_children"]}
			external_acl_startup  = {$SquidClientParams["external_acl_startup"]}
			external_acl_idle     = {$SquidClientParams["external_acl_idle"]}
			";
			
			
			squid_admin_mysql(1,"external ACL queue overload","$text",__FILE__,__LINE__);
			@unlink($file);
			@file_put_contents($file, time());
			return;
		}
		events("external ACL (queue overload) timeout ({$timefile}mn) line:".__LINE__);
		return;
		
	}
// *******************************************************************************************************************			
	
	if(preg_match("#kid[0-9]+.*?ERROR opening swap log\s+(.+?)\/swap\.state: \(13\) Permission denied#",$buffer,$re)){
		$dirname=$re[1];
		$file="/etc/artica-postfix/pids/opening.swap.state.".md5($dirname);
		events("$dirname ERROR opening swap log... Line:".__LINE__);
		$timefile=file_time_min($file);
		if($timefile>3){
			swapstate($dirname);
			if(!isset($GLOBALS["CHMOD"])){$unix=new unix();$GLOBALS["CHMOD"]=$unix->find_program("chmod");}
			if(!isset($GLOBALS["CHOWN"])){$unix=new unix();$GLOBALS["CHOWN"]=$unix->find_program("chown");}
			if(!isset($GLOBALS["SQUIDBIN"])){$unix=new unix();$GLOBALS["SQUIDBIN"]=$unix->LOCATE_SQUID_BIN();}
			$cmd="{$GLOBALS["NOHUP"]} {$GLOBALS["CHMOD"]} -R 0755 $dirname >/dev/null 2>&1 &";
			shell_exec($cmd);
			events("$cmd".__LINE__);
			squid_admin_mysql(1, "swap.state: Permission denied [action=permissions]","Permission as been set to $dirname");
			$cmd="{$GLOBALS["NOHUP"]} {$GLOBALS["CHOWN"]} -R squid:squid $dirname >/dev/null 2>&1 &";
			events("$cmd".__LINE__);
			shell_exec($cmd);			
			@unlink($file);
			@file_put_contents($file, time());			
		}else{
			events("$dirname Timeout {$timefile}Mn need to wait 3mn Line chown just swap.state :".__LINE__);
			swapstate($dirname);		
			if(!isset($GLOBALS["SQUIDBIN"])){$unix=new unix();$GLOBALS["SQUIDBIN"]=$unix->LOCATE_SQUID_BIN();}
			squid_admin_mysql(1, "swap.state: Permission denied [action=permissions]","Permission as been set to $dirname");
			events("$cmd".__LINE__);
			shell_exec($cmd);			
				
		}
		
		return;
	}
// *******************************************************************************************************************	
	if(preg_match("#ERROR opening swap log (.+?)\/swap\.state: \(13\) Permission denied#",$buffer,$re)){
		$dirname=$re[1];
		$file="/etc/artica-postfix/pids/opening.swap.state.".md5($dirname);
		events("$dirname ERROR opening swap log... Line:".__LINE__);
		$timefile=file_time_min($file);
		if($timefile>3){
			swapstate($dirname);
			if(!isset($GLOBALS["CHMOD"])){$unix=new unix();$GLOBALS["CHMOD"]=$unix->find_program("chmod");}
			if(!isset($GLOBALS["CHOWN"])){$unix=new unix();$GLOBALS["CHOWN"]=$unix->find_program("chown");}
			if(!isset($GLOBALS["SQUIDBIN"])){$unix=new unix();$GLOBALS["SQUIDBIN"]=$unix->LOCATE_SQUID_BIN();}
			$cmd="{$GLOBALS["NOHUP"]} {$GLOBALS["CHMOD"]} -R 0775 $dirname >/dev/null 2>&1 &";
			shell_exec($cmd);
			events("$cmd".__LINE__);
			squid_admin_mysql(1, "Permission denied: Reconfiguring squid-cache","Permission as been set to $dirname");
			$cmd="{$GLOBALS["NOHUP"]} {$GLOBALS["CHOWN"]} -R squid:squid $dirname && /etc/init.d/squid reload {$GLOBALS["SCRIPT_SUFFIX"]} >/dev/null 2>&1 &";
			events("$cmd".__LINE__);
			shell_exec($cmd);
			@unlink($file);
			@file_put_contents($file, time());
			
		}else{
			events("$dirname Timeout {$timefile}Mn need to wait 3mn Line:".__LINE__);
			swapstate($dirname);
				
		}
	
		return;
	}	
// *******************************************************************************************************************	
	if(preg_match("#Detected DEAD Parent:\s+(.+)#", $buffer,$re)){
		events("DEAD Parent {$re[1]} -> exec.squid.watchdog.php --dead-parent {$re[1]}".__LINE__);
		shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.watchdog.php --dead-parent \"{$re[1]}\" >/dev/null 2>&1 &");
		return;
	}
// *******************************************************************************************************************	
	if(preg_match("#kid[0-9]+.*?ERROR:\s+(.+):\s+\(2\)\s+No such file or directory#i", $buffer,$re)){
		if(TimeStampTTL(__LINE__,5)){
			squid_admin_mysql(2,"Missing object $dirname [action: None]","squid-cache claim\r\n$buffer\r\nArtica have automatically created this directory....");
		}
		return;
	}
// *******************************************************************************************************************	
	if(preg_match("#Preparing for shutdown after\s+([0-9]+)\s+requests#",$buffer,$re)){
		squid_admin_mysql(2,"Proxy will be stopped after {$re[1]} requests","$buffer");
		return;
	}
// *******************************************************************************************************************	
	if(preg_match("#Store rebuilding is\s+([0-9\.,]+)#",$buffer,$re)){
		if($re[1]=="0.00"){return;}
		squid_admin_mysql(2,"Store rebuilding {$re[1]}%","$buffer");
		$intval=intval($re[1]);
		if($intval>100){
			$file="/etc/artica-postfix/pids/Squid.Store.rebuilding";
			$timefile=file_time_min($file);
			if($timefile>10){
				squid_admin_mysql(0,"Store rebuilding task {$re[1]}% is over 100% - Reset caches","$buffer");
				shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.rebuild.caches.php --reindex >/dev/null 2>&1 &");
				@unlink($file);
				@file_put_contents($file, time());
				return;
			}
		}
		events("Store rebuilding is: {$re[1]}% = $intval swap.state [do nothing] line:".__LINE__);
		return;
	}
// *******************************************************************************************************************	
	if(preg_match("#commBind: Cannot bind socket FD [0-9]+ to\s+(.*?)\s+\([0-9]+\) No such file or directory#", $buffer,$re)){
		if(TimeStampTTL(__LINE__,2)){
			squid_admin_mysql(1,"Cannot bind socket to {$re[1]}","$buffer",__FILE__,__LINE__);
		}
		return;
	}
	
// *******************************************************************************************************************	
	if(preg_match("#\|\s+(.+?):\s+\(2\)\s+No such file or directory#", $buffer,$re)){
		if(preg_match("#\/usr\/share\/squid3#i",$buffer)){return;}
		if(TimeStampTTL(__LINE__,2)){
			squid_admin_mysql(2,"{$re[1]} No such file or directory","$buffer",__FILE__,__LINE__);
		}
		return;
	}
// *******************************************************************************************************************	
	if(preg_match("#kid[0-9]+\|\s+\/(.+?)\/[0-9]+\/[0-9A-Z]+$#", $buffer)){
		return;
	}
	
// *******************************************************************************************************************	
	if(preg_match("#storeDirClean:\s+(.+?):\s+\(2\)\s+No such file or directory#", $buffer,$re)){
		$file="/etc/artica-postfix/pids/".md5("storeDirCleanNo::NoSuchFileOrDirectory");
		$fileMail="/etc/artica-postfix/pids/squid.miss_dir";
		$timefile=file_time_min($file);
		$dirname=trim($re[1]);
		events("$dirname No such file or directory... Line:".__LINE__);
		@mkdir("$dirname",0755);
		@chown($dirname, "squid");
		@chgrp($dirname, "squid");	
		if($timefile>5){	
			$timefile=file_time_min($fileMail);
			if($timefile>10){
				squid_admin_mysql(1,"Suspicious removed object $dirname","Suspicious removed object $dirname\r\nsquid-cache claim\r\n$buffer\r\nIt seems that this cache directory was removed after the started service\r\nChecks that your have created your caches \"outside\" /var/cache/squid*\r\n",__FILE__,__LINE__);
				
				@unlink($fileMail);
				@file_put_contents($file, time());
			}
		@unlink($file);
		@file_put_contents($file, time());
	}
	return;
	}
// *******************************************************************************************************************	
	
	if(preg_match("#DiskThreadsDiskFile::openDone:.*?No such file or directory#", $buffer,$re)){
		$file="/etc/artica-postfix/pids/".md5("DiskThreadsDiskFile::openDone:NoSuchFileOrDirectory");
		$timefile=file_time_min($file);
		$fileMail="/etc/artica-postfix/pids/squid.miss_dir";
		events("DiskThreadsDiskFile:: \"$buffer\" [do nothing]");
		if($timefile<15){return;}
		squid_admin_mysql(1,"Cache object issue on disk","squid-cache claim\r\n$buffer\r\nIt seems that some caches objects was removed after the started service\r\nJust an information, nothing will be done.");
		$timefile=file_time_min($fileMail);
		@unlink($file);
		@file_put_contents($file, time());
		return;
	}
// *******************************************************************************************************************	
if(preg_match("#Failed to verify one of the swap directories#", $buffer,$re)){
		$file="/etc/artica-postfix/pids/".md5("Failed to verify one of the swap directories");
		$timefile=file_time_min($file);
		$fileMail="/etc/artica-postfix/pids/squid.miss_dir";
		events("Failed to verify one of the swap directories [ - squid -z ?]");
		if($timefile<5){return;}
		$timefile=file_time_min($fileMail);
		if($timefile>10){
			squid_admin_mysql(0,"Missing Caches !!","squid-cache claim\r\n$buffer\r\nIt seems that caches directory was removed after the started service\r\nArtica start the procedure to verify caches..\r\n",__FILE__,__LINE__);
			
			@unlink($file);
			@file_put_contents($file, time());
		}
				
		@unlink($file);
		@file_put_contents($file, time());
		shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.smp.php --squid-z-fly >/dev/null 2>&1 &");
		return;
}
// *******************************************************************************************************************
if(preg_match("#kid[0-9]+\|\s+\/home\/squid\/cache\/MemBooster[0-9]+\/#", $buffer,$re)){
	$file="/etc/artica-postfix/pids/".md5("MemBoosterFailed");
	$timefile=file_time_min($file);
	if($timefile<15){return;}
	squid_admin_mysql(1,"Cache object issue MemBoosters","squid-cache claim\r\n$buffer\r\nIt seems that some caches objects was removed after the started service\r\nJust an information, nothing will be done.");
	@unlink($file);
	@file_put_contents($file, time());
	//shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.watchdog.php --CleanMemBoosters >/dev/null 2>&1");
	}
// *******************************************************************************************************************	
	if(strpos($buffer,"Old swap file detected")>0){
		events("Old swap file detected...".__LINE__);
		return;
	}
// *******************************************************************************************************************	
	if(strpos($buffer,"Run 'squid -z' to create swap directories")){
		$file="/etc/artica-postfix/pids/".md5("Run 'squid -z' to create swap directories");
		$timefile=file_time_min($file);
		if($timefile>5){
			events("SQUID -Z !!!... Line:".__LINE__);
			squid_admin_mysql(0,"Missing Caches !!","squid-cache claim\r\n$buffer\r\nArtica will launch the directory creation",__FILE__,__LINE__);
			shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.smp.php --squid-z-fly >/dev/null 2>&1 &");
			@unlink($file);
			@file_put_contents($file, time());
		}else{
			events("SQUID -Z !!!... TIMEOUT Line:".__LINE__);
		}
		return;
	}
// *******************************************************************************************************************	
	if(strpos($buffer,"| Reconfiguring Squid Cache")>0){
		$file="/etc/artica-postfix/pids/".md5("Reconfiguring Squid Cache");
		$timefile=file_time_min($file);
		if($timefile>1){
			squid_admin_mysql(1,"Proxy service was reloaded","$buffer",__FILE__,__LINE__);
			@unlink($file);
			@file_put_contents($file, time());	
			
		}
		return;
	}
// *******************************************************************************************************************	
	if(strpos($buffer,"Fatal: Bungled squid.conf line")){
		events("Bad configuration file!".__LINE__);
		squid_admin_mysql(0,"FATAL!! Bad configuration file","squid-cache claim\r\n$buffer\r\nTry to run the configuration compilation on Artica or contact our support team...",__FILE__,__LINE__);
		return;
	}
// *******************************************************************************************************************	
	if(preg_match("#FATAL ERROR: cannot connect to ufdbguardd daemon socket: Connection timed out#",$buffer)){
		$file="/etc/artica-postfix/pids/".md5("Fatal:ufdbguardd daemon socket: Connection timed out");
		$timefile=file_time_min($file);
		if($timefile>5){
			events("Fatal: ufdbguardd daemon socket:timed out ".__LINE__);
			squid_admin_mysql(0,"Issue on Webfiltering Daemon!","squid-cache claim\r\n$buffer\r\nThe Webfiltering Dameon will disconnected from proxy service will be reloaded",__FILE__,__LINE__);
			@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableUfdbGuard",0);
			shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");
			shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/bin/artica-make APP_UFDBGUARD >/dev/null 2>&1 &");
			@unlink($file);
			@file_put_contents($file, time());	
			return;
		}	
		events("Fatal: ufdbguardd daemon socket:timed out ".__LINE__);
		return;
		
	}
// *******************************************************************************************************************	
	
	if(preg_match("#:\s+(.+?):\s+\(13\)\s+Permission denied#",$buffer,$re)){
		$file="/etc/artica-postfix/pids/13-permdenied.".md5($re[1]);
		if($timefile>2){
			squid_admin_mysql(0,"FATAL: {$re[1]} Permission denied [action=chown/chmod]","squid-cache claim\r\n$buffer\r\nArtica will repair privileges",__FILE__,__LINE__);
			@unlink($file);
			@file_put_contents($file, time());
		}
		$timefile=file_time_min($file);
		
		@chown($re[1],"squid");
		@chgrp($re[1],"squid");
		events("Add squid:squid permission on `{$re[1]}` ".__LINE__);
		if(is_dir($re[1])){
			$tt=explode("/",$re[1]);
			$pathX=null;
			while (list ($none, $subdir) = each ($tt) ){
				if($subdir==null){continue;}
				events("Add chmod 0755 on `$pathX` ".__LINE__);
				$pathX=$pathX."/$subdir";
				@chmod($pathX, 0755);
			
			}
		}
		
		
		
		if(preg_match("#pinger$#", $re[1])){
			events("Add chmod 04755 on `{$re[1]}` ".__LINE__);
			shell_exec("{$GLOBALS["CHOWN"]} root {$re[1]}");
			shell_exec("{$GLOBALS["CHMOD"]} 4755 {$re[1]}");
		}
		return;
	}
// *******************************************************************************************************************	
	
	
	if(preg_match("#FATAL: Received Segment Violation\.\.\.dying#",$buffer)){
		$file="/etc/artica-postfix/pids/".md5("Fatal: Received Segment Violation");
		$timefile=file_time_min($file);
		events("Fatal: Received Segment Violation ".__LINE__);
		if($GLOBALS["MonitConfig"]["RestartWhenCrashes"]==0){
			squid_admin_mysql(0,"FATAL!! Received Segment Violation process Dying !!","squid-cache claim\r\n$buffer",__FILE__,__LINE__);
			return;
		}

	}
	
	if(preg_match("#optional ICAP service is down after an options fetch failure:\s+icap:.*?1344\/av\/reqmod#",$buffer)){
		$file="/etc/artica-postfix/pids/".md5("KasperskyIcapDown");
		$timefile=file_time_min($file);
		
		if($timefile>2){
				squid_admin_mysql(1,"ICAP service is down, reloading squid-cache service","$buffer");
				port1344_notavailable();
				@unlink($file);
				@file_put_contents($file, time());
				shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.watchdog.php --reload --force --exec-status=".__LINE__." >/dev/null 2>&1 &");
				return;
			}
		events("ICAP service is down after an options (1344) -> timeout Line:".__LINE__);
		return;
	}
	
	
	if(strpos($buffer,"Terminated abnormally")){
		if($GLOBALS["MonitConfig"]["RestartWhenCrashes"]==0){
			squid_admin_mysql(1,"Squid Terminated abnormally","$buffer",__FILE__,__LINE__);
			
			return;
		}
		$file="/etc/artica-postfix/pids/".md5("Terminated abnormally");
		$timefile=file_time_min($file);
		if($timefile>1){
			events("Terminated abnormally ".__LINE__);
			squid_admin_mysql(0,"Squid Terminated abnormally","$buffer\nProxy will be restarted",__FILE__,__LINE__);
			
			@unlink($file);
			@file_put_contents($file, time());			
		}
		return;
	}
	events("Not Filtered \"$buffer\" Line:".__LINE__);
}

function port1344_notavailable($buffer){
	$EnableRemoteSyslogStatsAppliance=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableRemoteSyslogStatsAppliance"));
	$DisableArticaProxyStatistics=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/DisableArticaProxyStatistics"));
	$EnableKavICAPRemote=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableKavICAPRemote"));
	$kavicapserverEnabled=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/kavicapserverEnabled"));
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	if(!is_numeric($EnableRemoteSyslogStatsAppliance)){$EnableRemoteSyslogStatsAppliance=0;}
	if(!is_numeric($EnableKavICAPRemote)){$EnableKavICAPRemote=0;}
	if(!is_numeric($kavicapserverEnabled)){$kavicapserverEnabled=0;}	
	$Disabled=false;
	if(!is_file("/opt/kaspersky/kav4proxy/sbin/kav4proxy-kavicapserver")){$Disabled=true;}
	if($EnableRemoteSyslogStatsAppliance==1){$Disabled=true;}
	if($EnableKavICAPRemote==1){$Disabled=true;}
	if($kavicapserverEnabled==0){$Disabled=true;}
	
	events("Warning, Kaspersky ICAP server is down (port 1344). Disabled = $Disabled".__LINE__);
	
	if($Disabled){
		squid_admin_mysql(2,"Kaspersky ICAP service down","Squid-Cache claim\n$buffer\nBut it seems that the ICAP server is disabled...\nArtica will reconfigure the service",__FILE__,__LINE__);
		
		shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1 &");
		return;
	}
	squid_admin_mysql(1,"Kaspersky ICAP service down","Squid-Cache claim\n$buffer\nArtica will restart the Kaspersky ICAP server...\nArtica will reconfigure the service",__FILE__,__LINE__);
	shell_exec("{$GLOBALS["NOHUP"]} /etc/init.d/artica-postfix restart kav4proxy >/dev/null 2>&1 &");
	
	
	
	
}

function TimeStampTTL($line,$mins){
	
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}
	
	}
	
	$filename="/etc/artica-postfix/pids/".basename(__FILE__).".$line.time";
	$unix=new unix();
	$Time=$GLOBALS["CLASS_UNIX"]->file_time_min($filename);
	if($Time<$mins){
		events("TimeStampTTL(); = {$Time}mn, need to wait at least {$mins}mn Called by line:$line");
		return false;
	}
	
	@unlink($filename);
	@file_put_contents($filename, time());
	return true;
}

function swapstate($dirname){
	if(is_file("$dirname/swap.state")){
		events("Apply permissions on $dirname/swap.state Line:".__LINE__);
		@chown("$dirname/swap.state", "squid");
		@chgrp("$dirname/swap.state", "squid");
		@chmod("$dirname/swap.state", 0775);
	}
	
	if(is_file("$dirname/swap.state.last-clean")){
		events("Apply permissions on $dirname/swap.state.last-clean Line:".__LINE__);
		@chown("$dirname/swap.state.last-clean", "squid");
		@chgrp("$dirname/swap.state.last-clean", "squid");
		@chmod("$dirname/swap.state.last-clean", 0775);
	}	
	
}


function dustbin($buffer){
	$buffer="    $buffer";
	if(strpos($buffer, "Error negotiating SSL connection on")>1){return true;}
	if(strpos($buffer, "WARNING: newer swaplog entry for dirno")>1){return true;}
	if(strpos($buffer, "| Service Name:")>1){return true;}
	if(strpos($buffer, "Loading cache_dir #")>1){return true;}
	if(strpos($buffer, "Finished loading MIME types and icons")>1){return true;}
	if(strpos($buffer, "FD READ/WRITE")>1){return true;}
	if(strpos($buffer, "found KEY_PRIVATE")>1){return true;}
	if(strpos($buffer, "Open FD UNSTARTED")>1){return true;}
	if(strpos($buffer, "Using certificate")>1){return true;}
	if(strpos($buffer, "ACL is used but there is no HTTP reply")>1){return true;}
	if(strpos($buffer, "helperStatefulOpenServers: No")>1){return true;}
	if(strpos($buffer, "UFSSwapDir::openLog: Failed to open swap log")>1){return true;}
	if(strpos($buffer, "Waiting 30 seconds for active connections")>1){return true;}
	if(strpos($buffer, "Done scanning /")>1){return true;}
	if(strpos($buffer, "Closing HTTPS port")>1){return true;}
	if(strpos($buffer, "ipcacheParse: No Address records in response")>1){return true;}
	if(strpos($buffer, "Store rebuilding is: refresh swap.state")>1){return true;}
	if(strpos($buffer, "Store rebuilding : refresh swap.")>1){return true;}
	if(strpos($buffer, "Done scanning")>1){return true;}
	if(strpos($buffer, "Invalid entri")>1){return true;}
	if(strpos($buffer, "Maximum Resident Size")>1){return true;}
	if(strpos($buffer, "CPU Usage")>1){return true;}
	if(strpos($buffer, "Ordinary blocks")>1){return true;}
	if(strpos($buffer, "CPU Usage")>1){return true;}
	if(strpos($buffer, "Maximum Resident Size")>1){return true;}
	if(strpos($buffer, "total space in arena")>1){return true;}
	if(strpos($buffer, "Small blocks")>1){return true;}
	if(strpos($buffer, "Holding blocks")>1){return true;}
	if(strpos($buffer, "Free Small blocks")>1){return true;}
	if(strpos($buffer, "Free Ordinary blocks")>1){return true;}
	if(strpos($buffer, "Total in use")>1){return true;}
	if(strpos($buffer, "Total free")>1){return true;}
	if(strpos($buffer, "icmp_sock: (1) Operation not permitted")>1){return true;}
	if(strpos($buffer, "Fatal: pinger: Unable to open any ICMP sockets")>1){return true;}
	if(strpos($buffer, "helperOpenServers")>1){return true;}
	if(strpos($buffer, "Stop accepting HTCP on")>1){return true;}
	if(strpos($buffer, "| Adding")>1){return true;}
	if(strpos($buffer, "| WARNING:")>1){return true;}
	if(strpos($buffer, "| Logfile:")>1){return true;}
	if(strpos($buffer, "| DNS Socket")>1){return true;}
	if(strpos($buffer, "| pinger:")>1){return true;}
	if(strpos($buffer, "| Accepting")>1){return true;}
	if(strpos($buffer, "| Startup:")>1){return true;}
	if(strpos($buffer, "| Starting")>1){return true;}
	if(strpos($buffer, "| Pinger socket opened")>1){return true;}
	if(strpos($buffer, "Initializing")>1){return true;}
	if(strpos($buffer, "Icons.")>1){return true;}
	if(strpos($buffer, "| Squid plugin")>1){return true;}
	if(strpos($buffer, "| Warning:")>1){return true;}
	if(strpos($buffer, "| Processing")>1){return true;}
	if(strpos($buffer, "| Adaptation support")>1){return true;}
	if(strpos($buffer, "| Shutdown:")>1){return true;}
	if(strpos($buffer, "descriptors available")>1){return true;}
	if(strpos($buffer, "| Local cache digest enabled")>1){return true;}
	if(strpos($buffer, "| Closing Pinger")>1){return true;}
	if(strpos($buffer, "Store buckets")>1){return true;}
	if(strpos($buffer, "| Max Swap size")>1){return true;}
	if(strpos($buffer, "Target number of buckets")>1){return true;}
	if(strpos($buffer, "storeLateRelease: released")>1){return true;}
	if(strpos($buffer, "| HTCP Disabled")>1){return true;}
	if(strpos($buffer, "Stop receiving")>1){return true;}
	if(strpos($buffer, "| Sending ICP messages")>1){return true;}
	if(strpos($buffer, "| Stop sending ICP from")>1){return true;}
	if(strpos($buffer, "| Unlinkd pipe opened")>1){return true;}
	if(strpos($buffer, "| Swap maxSize")>1){return true;}
	if(strpos($buffer, "| Max Mem")>1){return true;}
	if(strpos($buffer, "| Rebuilding storage")>1){return true;}
	if(strpos($buffer, "| Using Least")>1){return true;}
	if(strpos($buffer, "| Set Current Directory")>1){return true;}
	if(strpos($buffer, "| Done reading /")>1){return true;}
	if(strpos($buffer, "| Beginning Validation")>1){return true;}
	if(strpos($buffer, "Completed Validation")>1){return true;}
	if(strpos($buffer, "|   Validated")>1){return true;}
	if(strpos($buffer, "|   store_swap_size")>1){return true;}
	if(strpos($buffer, "Squid is already running")>1){return true;}
	if(strpos($buffer, "| Process Roles")>1){return true;}
	if(strpos($buffer, "mallinfo")>1){return true;}
	if(strpos($buffer, "| Process ID")>1){return true;}
	if(strpos($buffer, "| Closing HTTP port")>1){return true;}
	if(strpos($buffer, "| storeDirWriteCleanLogs: Starting")>1){return true;}
	if(strpos($buffer, "|   Finished.  Wrote")>1){return true;}
	if(strpos($buffer, "|   Took")>1){return true;}
	if(strpos($buffer, "| NOTE:")>1){return true;}
	if(strpos($buffer, "fqdncacheParse: No PTR record for")>1){return true;}
	
	if(strpos($buffer, "Starting ext_time_quota_acl.cc")>1){return true;}
	if(strpos($buffer, "Sending SNMP messages from")>1){return true;}
	if(strpos($buffer, "Closing SNMP receiving")>1){return true;}
	if(strpos($buffer, "Stop sending HTCP")>1){return true;}
	if(strpos(" $buffer", "| Closing HTTP port")>1){return true;}
	if(strpos(" $buffer", "NETDB state saved")>1){return true;}
	if(strpos(" $buffer", "User-Agent:")>1){return true;}
	if(strpos(" $buffer", "Accept-Language:")>1){return true;}
	if(strpos(" $buffer", "Accept-Encoding:")>1){return true;}
	if(strpos(" $buffer", "Connection:")>1){return true;}
	if(strpos(" $buffer", "CPU Usage:")>1){return true;}
	if(strpos(" $buffer", "Entries scanned")>1){return true;}
	if(strpos(" $buffer", "Maximum Resident Size")>1){return true;}
	if(strpos(" $buffer", "With invalid flags")>1){return true;}
	if(strpos(" $buffer", "Objects loaded")>1){return true;}
	if(strpos(" $buffer", "Objects expired")>1){return true;}
	if(strpos(" $buffer", "Objects cancelled")>1){return true;}
	if(strpos(" $buffer", "Duplicate URLs purged")>1){return true;}
	if(strpos(" $buffer", "Swapfile clashes avoided")>1){return true;}
	if(strpos(" $buffer", "Sending HTCP messages from")>1){return true;}
	if(strpos(" $buffer", "Preparing for shutdown after")>1){return true;}
	if(strpos(" $buffer", "| Logfile Daemon")>1){return true;}
	if(strpos(" $buffer", "| ipcacheAddEntryFromHosts: Bad IP address")>1){return true;}
	if(strpos(" $buffer", "| Finished rebuilding storage from disk")>1){return true;}
	if(strpos(" $buffer", "| Disabling Authentication on port")>1){return true;}
	if(strpos(" $buffer", "| Logfile Daemon: closing log daemon")>1){return true;}
	if(strpos(" $buffer", "| Disabling IPv6 on port")>1){return true;}
	if(strpos(" $buffer", "recv: (111) Connection refused")>1){return true;}
	if(strpos(" $buffer", "INFO: ")>1){return true;}
	if(strpos(" $buffer", "calling free_func for 0x")>1){return true;}
	if(strpos(" $buffer", "avoid write on theFile")>1){return true;}
	if(strpos(" $buffer", "| Making directories in /")>1){return true;}
	if(strpos(" $buffer", "faults with physical i/o")>1){return true;}
	if(strpos(" $buffer", "| Configuring Parent")>1){return true;}
	if(strpos(" $buffer", "getsockopt(SO_ORIGINAL_DST)")>1){return true;}
	if(strpos(" $buffer", "xrename: Cannot rename")>1){return true;}
	if(strpos(" $buffer", "Squid modules loaded")>1){return true;}
	if(strpos(" $buffer", "Ready to serve requests")>1){return true;}
	if(strpos(" $buffer", "squid-internal-mgr")>1){return true;}
	if(strpos(" $buffer", "internalStart: unknown request")>1){return true;}
	if(strpos(" $buffer", "Waiting for requests")>1){return true;}
	if(strpos(" $buffer", "if needed, or if running Squid for the ")>1){return true;}
	if(strpos(" $buffer", "Store logging disabled")>1){return true;}
	if(strpos(" $buffer", "X-Real-IP")>1){return true;}
	if(strpos(" $buffer", "Via: 1.1")>1){return true;}
	if(strpos(" $buffer", "GET /")>1){return true;}
	if(strpos(" $buffer", "Cache-Control:")>1){return true;}
}
function events($text){
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}
		
	}	
	
	$unix=new unix();
	$unix->events($text,"/var/log/squid.watchdog.log",false,$sourcefunction,$sourceline);
}
function NETWORK_REPORT(){
	$unix=new unix();
	return $unix->NETWORK_REPORT();
	$results[]="Report....:";
	$ifconfig=$unix->find_program("ifconfig");
	exec("$ifconfig -a 2>&1",$results);
	$ip=$unix->find_program("ip");
	exec("$ip link show 2>&1",$results);
	exec("$ip addr 2>&1",$results);
	$results[]="Routes....:";
	exec("$ip route 2>&1",$results);
	return @implode("\r\n", $results);
}