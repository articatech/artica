<?php

if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["NOREBOOT"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){
	$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
}

if(preg_match("#noreboot#",implode(" ",$argv))){
	$GLOBALS["NOREBOOT"]=true;
}

include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/ressources/class.resolv.conf.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");

if($argv[1]=="--automation"){automation();exit;}
if($argv[1]=="--articaweb"){create_articaweb($argv["2"]);die();}
if($argv[1]=="--genuid"){
		$unix=new unix();
		echo "Dynamic: ";
		echo $unix->gen_uuid()."\nCurrent: ".$unix->GetUniqueID()."\n";die();}
if($argv[1]=="--tests-network"){testnetworks($argv["2"]);die();}

WizardExecute();


function testnetworks(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){die();}	
	@file_get_contents($pidfile,getmypid());
	
	shell_exec("/etc/init.d/mysql restart --force --bywizard --framework=".__FILE__);
	
	$users=new usersMenus();
	$q=new mysql();
	$q->BuildTables();
	$sock=new sockets();
	$savedsettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
	
	if(!is_array($savedsettings)){die();}
	if(count($savedsettings)<4){die();}

	if($q->COUNT_ROWS("nics", "artica_backup")==0){
		WizardExecute();
		@file_put_contents("/etc/artica-postfix/TESTS_NETWORK_EXECUTED", time());
	}
	
	
}

function debug_logs($text){
	$unix=new unix();
	
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}
			
	}
	
	
	$unix->events("$text","/var/log/artica-wizard.log",$sourcefunction,$sourceline,$sourcefile);
}

function writeprogress($perc,$text){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/wizard.progress";
	$array["POURC"]=$perc;
	$array["TEXT"]=$text;
	echo "$text\n";
	@mkdir("/usr/share/artica-postfix/ressources/logs/web",true,0755);
	@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);
	$unix=new unix();
	
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}
			
	}
	
	
	$unix->events("$perc} $text","/var/log/artica-wizard.log",$sourcefunction,$sourceline,$sourcefile);
	
}

function automation(){
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	$sock=new sockets();
	$users=new usersMenus();
	$unix=new unix();
	@chmod("/usr/share/artica-postfix/bin/process1",0755);
	
	if(!is_file("/usr/share/artica-postfix/ressources/logs/web/AutomationScript.conf")){
		echo "AutomationScript.conf no such file...\n";
		writeprogress(110,"AutomationScript.conf no such file...");
		return;
	}
	
	$ipClass=new IP();
	$users=new usersMenus();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));	
	$php=$unix->LOCATE_PHP5_BIN();
	
	$AutomationScript=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/AutomationScript.conf");
	if(preg_match("#<SQUIDCONF>(.*?)</SQUIDCONF>#is", $AutomationScript,$rz)){
		$squidconf=$rz[1];
		if(strlen($squidconf)>10){
			echo "Squid.conf = ".strlen($squidconf)." bytes\n";
			$AutomationScript=str_replace("<SQUIDCONF>{$rz[1]}</SQUIDCONF>", "", $AutomationScript);
			@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/SquidToImport.conf", $squidconf);
			$squidconf=null;
			writeprogress(5,"Importing old Squid.conf");
			system("$php /usr/share/artica-postfix/exec.squid.import.conf.php --import \"/usr/share/artica-postfix/ressources/logs/web/SquidToImport.conf\" --verbose");
			@unlink("/usr/share/artica-postfix/ressources/logs/web/SquidToImport.conf");
		}
	}
	
	
	
	
	$data=explode("\n",$AutomationScript);
	$WizardStatsAppliance=unserialize(base64_decode($sock->GET_INFO("WizardStatsAppliance")));
	writeprogress(5,"Analyze configuration file...");
	
	while (list ($num, $ligne) = each ($data) ){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
	
		if(preg_match("#^\##", trim($ligne))){continue;}
		if(!preg_match("#(.+?)=(.+)#", $ligne,$re)){continue;}
		$key=trim($re[1]);
		$value=trim($re[2]);
		writeprogress(5,"Parsing [$key] = \"$value\"");
		if(preg_match("#BackupSquidLogs#", $key)){$sock->SET_INFO($key, $value);}
		if($key=="caches"){ $WizardSavedSettings["CACHES"][]=$value; continue; }
		$WizardSavedSettings[$key]=$value;
		$KerbAuthInfos[$key]=$value;
	}
	writeprogress(6,"Analyze configuration file...");
	$sock->SaveConfigFile(base64_encode(serialize($WizardStatsAppliance)), "WizardStatsAppliance");
	$sock->SaveConfigFile(base64_encode(serialize($KerbAuthInfos)), "KerbAuthInfos");
	$WizardSavedSettings["ARTICAVERSION"]=$users->ARTICA_VERSION;
	
	if(isset($WizardSavedSettings["RootPassword"])){
		writeprogress(6,"Change ROOT Password....");
		$unix->ChangeRootPassword($WizardSavedSettings["RootPassword"]);
		unset($WizardSavedSettings["RootPassword"]);
		sleep(2);
		
	}
	
	$WizardWebFilteringLevel=$sock->GET_INFO("WizardWebFilteringLevel");
	
	
	
	if(is_numeric($WizardWebFilteringLevel)){
		$WizardSavedSettings["EnableWebFiltering"]=1;
	}
	
	
	$ProxyDNSCount=0;
	if(isset($WizardSavedSettings["EnableKerbAuth"])){
		$sock->SET_INFO("EnableKerbAuth", intval($WizardSavedSettings["EnableKerbAuth"]));
		$sock->SET_INFO("UseADAsNameServer", $WizardSavedSettings["UseADAsNameServer"]);
		$sock->SET_INFO("NtpdateAD", $WizardSavedSettings["NtpdateAD"]);
		if($WizardSavedSettings["UseADAsNameServer"]==1){
			if($ipClass->isValid($WizardSavedSettings["ADNETIPADDR"])){
				$WizardSavedSettings["DNS1"]=$WizardSavedSettings["ADNETIPADDR"];
				$q=new mysql_squid_builder();
				$q->QUERY_SQL("INSERT INTO dns_servers (dnsserver,zOrder) VALUES ('{$WizardSavedSettings["ADNETIPADDR"]}','$ProxyDNSCount')");
			}
		}
	
	}	
	writeprogress(7,"Analyze configuration file...");
	if(isset($WizardSavedSettings["ProxyDNS"])){
		$ProxyDNS=explode(",",$WizardSavedSettings["ProxyDNS"]);
		$c=1;
		while (list ($num, $nameserver) = each ($ProxyDNS) ){
			if(!$ipClass->isValid($nameserver)){continue;}
			$ProxyDNSCount++;
			$q=new mysql_squid_builder();
			$q->QUERY_SQL("INSERT INTO dns_servers (dnsserver,zOrder) VALUES ('{$WizardSavedSettings["ADNETIPADDR"]}','$ProxyDNSCount')");
		}
	}	
	
	if(isset($WizardSavedSettings["EnableArticaMetaClient"])){$sock->SET_INFO("EnableArticaMetaClient",$WizardSavedSettings["EnableArticaMetaClient"]);}
	if(isset($WizardSavedSettings["ArticaMetaUsername"])){$sock->SET_INFO("ArticaMetaUsername",$WizardSavedSettings["ArticaMetaUsername"]);}
	if(isset($WizardSavedSettings["ArticaMetaPassword"])){$sock->SET_INFO("ArticaMetaPassword",$WizardSavedSettings["ArticaMetaPassword"]);}
	if(isset($WizardSavedSettings["ArticaMetaHost"])){$sock->SET_INFO("ArticaMetaHost",$WizardSavedSettings["ArticaMetaHost"]);}
	if(isset($WizardSavedSettings["ArticaMetaPort"])){$sock->SET_INFO("ArticaMetaPort",$WizardSavedSettings["ArticaMetaPort"]);}
	

	
	
	writeprogress(8,"Analyze configuration file...");
	if(isset($WizardSavedSettings["ENABLE_PING_GATEWAY"])){
		if(!isset($WizardSavedSettings["PING_GATEWAY"])){$WizardSavedSettings["PING_GATEWAY"]=null;}
		$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
		$MonitConfig["ENABLE_PING_GATEWAY"]=$WizardSavedSettings["ENABLE_PING_GATEWAY"];
		$MonitConfig["PING_GATEWAY"]=$WizardSavedSettings["PING_GATEWAY"];
		$MonitConfig["MAX_PING_GATEWAY"]=$WizardSavedSettings["MAX_PING_GATEWAY"];
		$MonitConfig["PING_FAILED_RELOAD_NET"]=$WizardSavedSettings["PING_FAILED_RELOAD_NET"];
		$MonitConfig["PING_FAILED_REBOOT"]=$WizardSavedSettings["PING_FAILED_REBOOT"];
		$MonitConfig["PING_FAILED_REPORT"]=$WizardSavedSettings["PING_FAILED_REPORT"];
		$MonitConfig["PING_FAILED_FAILOVER"]=$WizardSavedSettings["PING_FAILED_FAILOVER"];
		$sock->SaveConfigFile(base64_encode(serialize($MonitConfig)), "SquidWatchdogMonitConfig");
	}	
	writeprogress(9,"Analyze configuration file...");
	$sock->SET_INFO("timezones",$WizardSavedSettings["timezones"]);
	$nic=new system_nic();
	
	$Encoded=base64_encode(serialize($WizardSavedSettings));
	$sock->SaveConfigFile($Encoded,"WizardSavedSettings");
	
	writeprogress(10,"Analyze configuration file...");
	$TuningParameters=unserialize(base64_decode($sock->GET_INFO("MySQLSyslogParams")));
	if(isset($WizardSavedSettings["MySQLSyslogUsername"])){$TuningParameters["username"]=$WizardSavedSettings["MySQLSyslogUsername"];}
	if(isset($WizardSavedSettings["MySQLSyslogPassword"])){$TuningParameters["password"]=$WizardSavedSettings["MySQLSyslogPassword"];}
	if(isset($WizardSavedSettings["MySQLSyslogServer"])){$TuningParameters["mysqlserver"]=$WizardSavedSettings["MySQLSyslogServer"];}
	if(isset($WizardSavedSettings["MySQLSyslogServerPort"])){$TuningParameters["RemotePort"]=$WizardSavedSettings["MySQLSyslogServerPort"];}
	if(isset($WizardSavedSettings["MySQLSyslogWorkDir"])){$TuningParameters["MySQLSyslogWorkDir"]=$WizardSavedSettings["MySQLSyslogWorkDir"];}
	if(isset($WizardSavedSettings["MySQLSyslogType"])){$TuningParameters["MySQLSyslogType"]=$WizardSavedSettings["MySQLSyslogType"];}
	$sock->SaveConfigFile(base64_encode(serialize($TuningParameters)), "MySQLSyslogParams");
	$sock->SET_INFO("MySQLSyslogType", $WizardSavedSettings["MySQLSyslogType"]);
	$sock->SET_INFO("MySQLSyslogWorkDir", $WizardSavedSettings["MySQLSyslogWorkDir"]);
	$sock->SET_INFO("EnableSyslogDB", $WizardSavedSettings["EnableSyslogDB"]);
	
	if(!isset($WizardSavedSettings["SquidPerformance"])){$WizardSavedSettings["SquidPerformance"]=1;}
	
	$sock->SET_INFO("SquidPerformance", $WizardSavedSettings["SquidPerformance"]);
	
	
	if(isset($WizardSavedSettings["EnableCNTLM"])){
		$sock->SET_INFO("EnableCNTLM", $WizardSavedSettings["EnableCNTLM"]);
		$sock->SET_INFO("CnTLMPORT", $WizardSavedSettings["CnTLMPORT"]);
	}
	
	if(isset($WizardSavedSettings["DisableSpecialCharacters"])){
		$sock->SET_INFO("DisableSpecialCharacters", $WizardSavedSettings["DisableSpecialCharacters"]);
	}
	
	if(isset($WizardSavedSettings["SambaBindInterface"])){
		$sock->SET_INFO("SambaBindInterface", $WizardSavedSettings["SambaBindInterface"]);
	}
	
	writeprogress(11,"Analyze configuration file...");
	if(isset($WizardSavedSettings["EnableSNMPD"])){
		$sock->SET_INFO("EnableSNMPD", $WizardSavedSettings["EnableSNMPD"]);
		$sock->SET_INFO("SNMPDCommunity", $WizardSavedSettings["SNMPDCommunity"]);
		$sock->SET_INFO("SNMPDNetwork", $WizardSavedSettings["SNMPDNetwork"]);
		$sock->getFrameWork("snmpd.php?restart=yes");
	}
	
	
	writeprogress(12,"Analyze configuration file...");
	if(isset($WizardSavedSettings["DisableArticaProxyStatistics"])){$sock->SET_INFO("DisableArticaProxyStatistics", $WizardSavedSettings["DisableArticaProxyStatistics"]);}
	if(isset($WizardSavedSettings["EnableProxyLogHostnames"])){$sock->SET_INFO("EnableProxyLogHostnames", $WizardSavedSettings["EnableProxyLogHostnames"]);}
	if(isset($WizardSavedSettings["EnableSargGenerator"])){$sock->SET_INFO("EnableSargGenerator", $WizardSavedSettings["EnableSargGenerator"]);}
	
	if(isset($WizardSavedSettings["CACHES"])){
		if(count($WizardSavedSettings["CACHES"])>0){
			
			$order=1;
			while (list ($index, $line) = each ($WizardSavedSettings["CACHES"]) ){
				$order++;
				$CONFCACHE=explode(",",$line);
				$cachename=$CONFCACHE[0];
				$CPU=$CONFCACHE[1];
				$cache_directory=$CONFCACHE[2];
				$cache_type=$CONFCACHE[3];
				$size=$CONFCACHE[4];
				$cache_dir_level1=$CONFCACHE[5];
				$cache_dir_level2=$CONFCACHE[6];
				if($cache_type=="tmpfs"){ $users=new usersMenus(); $memMB=$users->MEM_TOTAL_INSTALLEE/1024; $memMB=$memMB-1500; if($size>$memMB){ $size=$memMB-100; }}
				$q=new mysql();
				$q->QUERY_SQL("INSERT IGNORE INTO squid_caches_center
						(cachename,cpu,cache_dir,cache_type,cache_size,cache_dir_level1,cache_dir_level2,enabled,percentcache,usedcache,zOrder)
						VALUES('$cachename',$CPU,'$cache_directory','$cache_type','$size','$cache_dir_level1','$cache_dir_level2',1,0,0,$order)","artica_backup");
			}
		}
	}
	
	
// ********************************* WEB FILTERING **********************************************************************
	$WizardWebFilteringLevel=$sock->GET_INFO("WizardWebFilteringLevel");
	if(is_numeric($WizardWebFilteringLevel)){$WizardSavedSettings["EnableWebFiltering"]=1;}
	writeprogress(13,"Analyze configuration file...");
	if(isset($WizardSavedSettings["Blacklists"])){
		if($WizardSavedSettings["EnableWebFiltering"]==1){
			$tp=explode(",",$WizardSavedSettings["Blacklists"]);
			$q=new mysql_squid_builder();
			while (list ($key, $category) = each ($tp) ){
				if(trim($category)==null){continue;}
				$sql="INSERT IGNORE INTO webfilter_blks (webfilter_id,category,modeblk) VALUES ('0','$category','0')";
				$q->QUERY_SQL($sql);
	
			}
		}
	
	}
	
	

	$squid=new squidbee();
	writeprogress(14,"Analyze configuration file...");
	
	$q=new mysql_squid_builder();
	$sql="CREATE TABLE IF NOT EXISTS `proxy_ports` (
			`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`PortName` VARCHAR(128) NULL,
			`zMD5` VARCHAR(90) NOT NULL,
			`xnote` TEXT NULL ,
			`Params` TEXT NULL ,
			`TProxy` smallint(1) NOT NULL ,
			`ipaddr` VARCHAR(128) NOT NULL,
			`AuthForced` smallint(1) NOT NULL,
			`AuthPort` smallint(1) NOT NULL,
			`port` INT NOT NULL,
			`transparent` smallint(1) NOT NULL DEFAULT '0' ,
			`enabled` smallint(1) NOT NULL DEFAULT '1' ,
			 KEY `ipaddr` (`ipaddr`),
			 KEY `TProxy` (`TProxy`),
			 KEY `AuthForced` (`AuthForced`),
			 KEY `AuthPort` (`AuthPort`),
			 KEY `enabled` (`enabled`),
			 KEY `port` (`port`)
			)  ENGINE = MYISAM AUTO_INCREMENT = 20;";
	$q->QUERY_SQL($sql);
	
	$SQLSZ[]=$sql;
	
	
	if(isset($WizardSavedSettings["EnableTransparent"])){
		if( intval($WizardSavedSettings["EnableTransparent"])==1){
			if(intval($WizardSavedSettings["TransparentPort"])>80){
				$sql="INSERT IGNORE INTO proxy_ports (ID,PortName,ipaddr,port,enabled,transparent) 
				VALUES (1,'Transparent Port','0.0.0.0','{$WizardSavedSettings["TransparentPort"]}',1,1)";
				$q->QUERY_SQL($sql);
				$SQLSZ[]=$sql;
			}
		}
	}
	if(intval($WizardSavedSettings["proxy_listen_port"])>80){
		$sql="INSERT IGNORE INTO proxy_ports (ID,PortName,ipaddr,port,enabled,transparent,AuthPort)
		VALUES (1,'Connected Port','0.0.0.0','{$WizardSavedSettings["proxy_listen_port"]}',1,0,".intval($WizardSavedSettings["EnableKerbAuth"]).")";
		$q->QUERY_SQL($sql);
		$SQLSZ[]=$sql;
	}
	
	writeprogress(15,"Analyze configuration file...");
	if(isset($WizardSavedSettings["cache_mem"])){
		$squid=new squidbee();
		if(!isset($WizardSavedSettings["ipcache_high"])){$WizardSavedSettings["ipcache_high"]=95;}
		$squid->global_conf_array["cache_mem"]=$WizardSavedSettings["cache_mem"];
		$squid->global_conf_array["fqdncache_size"]=$WizardSavedSettings["fqdncache_size"];
		$squid->global_conf_array["ipcache_size"]=$WizardSavedSettings["ipcache_size"];
		$squid->global_conf_array["ipcache_low"]=$WizardSavedSettings["ipcache_low"];
		$squid->global_conf_array["ipcache_high"]=$WizardSavedSettings["ipcache_high"];
		$squid->SaveToLdap(true);
	}
	
	
	if(isset($WizardSavedSettings["swappiness"])){
		$swappiness_saved=unserialize(base64_decode($sock->GET_INFO("kernel_values")));
		$swappiness_saved["swappiness"]=$WizardSavedSettings["swappiness"];
		$sock->SaveConfigFile( base64_encode(serialize($swappiness_saved)),"kernel_values");
		$sock->getFrameWork("cmd.php?sysctl-setvalue={$WizardSavedSettings["swappiness"]}&key=".base64_encode("vm.swappiness"));
	}
	
	writeprogress(16,"Analyze configuration file...");
	if(isset($WizardSavedSettings["ManagerAccount"])){
		if($WizardSavedSettings["ManagerAccount"]<>null){
			if($WizardSavedSettings["ManagerPassword"]<>null){
				@mkdir("/etc/artica-postfix/ldap_settings",0755,true);
				@file_put_contents("/etc/artica-postfix/ldap_settings/admin", $WizardSavedSettings["ManagerAccount"]);
				@file_put_contents("/etc/artica-postfix/ldap_settings/password", $WizardSavedSettings["ManagerPassword"]);
			}
		}
		
	}
	

	
	writeprogress(17,"Analyze configuration file...");
	

	
	
	$sock->SET_INFO("EnableUfdbGuard", $WizardSavedSettings["EnableWebFiltering"]);
	$sock->SET_INFO("EnableArpDaemon", $WizardSavedSettings["EnableArpDaemon"]);
	$sock->SET_INFO("EnablePHPFPM",0);
	$sock->SET_INFO("EnableFreeWeb",$WizardSavedSettings["EnableFreeWeb"]);
	$sock->SET_INFO("SlapdThreads", $WizardSavedSettings["SlapdThreads"]);
	$sock->SET_INFO("AsCategoriesAppliance", intval($WizardSavedSettings["AsCategoriesAppliance"]));
	$sock->SET_INFO("AsHapProxyAppliance", intval($WizardSavedSettings["AsHapProxyAppliance"]));
	
	$sock->SET_INFO("AsMetaServer", intval($WizardSavedSettings["AsMetaServer"]));
	$sock->SET_INFO("EnableVnStat", 0);
	$sock->SET_INFO("WizardSavedSettingsSend", 1);	
	
	
	$savedsettings["ARTICAVERSION"]=$users->ARTICA_VERSION;
	$Encoded=base64_encode(serialize($WizardSavedSettings));
	@file_put_contents("/etc/artica-postfix/settings/Daemons/WizardSavedSettings", $Encoded);
	
	@file_put_contents("/etc/artica-postfix/settings/Daemons/WizardSqlWait", serialize($SQLSZ));
	writeprogress(18,"Analyze configuration file...{finish}");
	WizardExecute(true);
		
}


function WizardExecute($aspid=false){
	
	$unix=new unix();
	$sock=new sockets();
	@chmod("/usr/share/artica-postfix/bin/process1",0755);
	@mkdir("/etc/artica-postfix/settings/Daemons",0755,true);
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){die();}
		$pid=$unix->PIDOF_PATTERN(basename(__FILE__));
		if($pid<>getmypid()){return;}
	}
	
	@file_put_contents($pidfile, getmypid());
	$unix->CREATE_NEW_UUID();
	$uuid=$unix->GetUniqueID();
	$php5=$unix->LOCATE_PHP5_BIN();
	$php=$php5;
	$nohup=$unix->find_program("nohup");
	$squidbin=$unix->LOCATE_SQUID_BIN();
	
	
	$DEBUG_LOG="/var/log/artica-wizard.log";
	@mkdir("/etc/artica-postfix/ldap_settings",0755,true);
	@mkdir("/var/lib/ldap",0755,true);
	
	$rmbin=$unix->find_program("rm");
	
	writeprogress(5,"{set_permissions}...");
	shell_exec("$php /usr/share/artica-postfix/exec.checkfolder-permissions.php --force --wizard");
	writeprogress(10,"{uuid}: $uuid");
	sleep(2);
	$savedsettings=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/WizardSavedSettings")));
	
	if(!is_array($savedsettings)){
		writeprogress(110,"No saved settings Corrupted Array...");
		die();
	}
	if(count($savedsettings)<4){
		writeprogress(110,"No saved settings no enough element...");
		die();
	}
	
	
	$smtp_domainname=trim($savedsettings["smtp_domainname"]);
	if($smtp_domainname==null){
		if(isset($savedsettings["domain"])){
			$smtp_domainname=$savedsettings["domain"];
		}
	}
	if(strlen($smtp_domainname)<3){$smtp_domainname="my-domain.com";}
	if($smtp_domainname=="."){$smtp_domainname="my-domain.com";}
	if($smtp_domainname==null){$smtp_domainname="my-domain.com";}
	if(strpos($smtp_domainname,".")==0){$smtp_domainname="my-domain.com";}
	
	writeprogress(12,"Using `$smtp_domainname` as LDAP suffix");
	
	
	if(strpos($smtp_domainname, ".")>0){
		$smtp_domainname_exploded=explode(".",$smtp_domainname);
		writeprogress(12,"$smtp_domainname ".count($smtp_domainname_exploded)." items");
		$suffix="dc=".@implode(",dc=", $smtp_domainname_exploded);
	}else{
		$suffix="dc=$smtp_domainname";
	}
	
	$SQUIDEnable=1;
	$AsCategoriesAppliance=intval($savedsettings["AsCategoriesAppliance"]);
	$AsTransparentProxy=intval($savedsettings["AsTransparentProxy"]);
	$AsReverseProxyAppliance=intval($savedsettings["AsReverseProxyAppliance"]);
	$AsMetaServer=intval($savedsettings["AsMetaServer"]);
	$AsHapProxyAppliance=intval($savedsettings["AsHapProxyAppliance"]);

	
	$WizardWebFilteringLevel=$sock->GET_INFO("WizardWebFilteringLevel");
	if(is_numeric($WizardWebFilteringLevel)){
		$WizardSavedSettings["EnableWebFiltering"]=1;
	}
	@file_put_contents("/etc/artica-postfix/settings/Daemons/DisableBWMng",1);
	@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidDatabasesUtlseEnable",1);
	@file_put_contents("/etc/artica-postfix/settings/Daemons/AsMetaServer", $AsMetaServer);
	@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableOpenLDAP", 1);
	@file_put_contents("/etc/artica-postfix/settings/Daemons/AsCategoriesAppliance", $AsCategoriesAppliance);
	if($AsCategoriesAppliance==1){
		$savedsettings["EnableWebFiltering"]=0;
		@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableUfdbGuard", 0);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/SQUIDEnable", 0);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/ProxyUseArticaDB",0);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableArpDaemon",0);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableFreeWeb",0);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/SlapdThreads",2);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/DisableBWMng",1);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/DisableNetDiscover",1);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/SambaEnabled",0);
		$SQUIDEnable=0;
	}
	
	if($AsHapProxyAppliance==1){
		$savedsettings["EnableWebFiltering"]=0;
		$savedsettings["adminwebserver"]=null;
		$savedsettings["second_webadmin"]=null;
		@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableUfdbGuard", 0);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/SQUIDEnable", 0);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/ProxyUseArticaDB",0);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableArpDaemon",0);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableFreeWeb",0);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/SlapdThreads",2);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/DisableBWMng",1);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/DisableNetDiscover",1);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/SambaEnabled",0);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableHaProxy",1);
		$SQUIDEnable=0;
	}
	
	
	if($AsMetaServer==1){
		$savedsettings["EnableWebFiltering"]=0;
		@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableUfdbGuard", 0);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/SQUIDEnable", 0);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/ProxyUseArticaDB",0);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableArpDaemon",0);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableFreeWeb",0);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/SlapdThreads",2);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/DisableBWMng",1);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/DisableNetDiscover",1);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/SambaEnabled",0);
		$SQUIDEnable=0;
	}	
	
	if($AsReverseProxyAppliance==1){
		$AsCategoriesAppliance=0;
		$AsTransparentProxy=0;
		$savedsettings["EnableWebFiltering"]=0;
		$savedsettings["adminwebserver"]=null;
		$savedsettings["second_webadmin"]=null;
		$SQUIDEnable=0;
		@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableUfdbGuard", 0);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/SQUIDEnable", 0);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/ProxyUseArticaDB",0);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableArpDaemon",0);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableFreeWeb",0);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/SlapdThreads",2);
		
		@file_put_contents("/etc/artica-postfix/settings/Daemons/DisableNetDiscover",1);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/SambaEnabled",0);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableFreeWeb",0);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableNginx",1);
		
	}	
	
	if($savedsettings["administrator"]<>null){
		writeprogress(13,"{creating_accounts} {artica_manager}: {$savedsettings["administrator"]}");
		sleep(2);
		@mkdir("/etc/artica-postfix/ldap_settings",0755,true);
		@file_put_contents("/etc/artica-postfix/ldap_settings/admin", $savedsettings["administrator"]);
		@file_put_contents("/etc/artica-postfix/ldap_settings/password", $savedsettings["administratorpass"]);
		sleep(1);
		@unlink("/etc/artica-postfix/no-ldap-change");
		@chmod("/usr/share/artica-postfix/bin/artica-install", 0755);
		writeprogress(14,"{building_openldap_configuration_file}");
		system("/usr/share/artica-postfix/bin/artica-install --slapdconf >>$DEBUG_LOG 2>&1");
	}else{
		writeprogress(13,"{creating_accounts} {artica_manager}: {default} Manager");
		sleep(2);
	}
	
	
	
	writeprogress(15,"{creating_domain} LDAP {suffix}:$suffix ");
	@file_put_contents("/etc/artica-postfix/ldap_settings/suffix", $suffix);
	sleep(3);
	shell_exec("$rmbin -rf /var/lib/ldap/*");
	@file_put_contents("/etc/artica-postfix/WIZARD_INSTALL_EXECUTED", time());
	
	writeprogress(16,"{reconfigure}: {openldap_server}");
	@unlink("/etc/artica-postfix/no-ldap-change");
	@chmod("/usr/share/artica-postfix/bin/artica-install", 0755);
	@chmod("/usr/share/artica-postfix/bin/process1", 0755);
	writeprogress(17,"{building_openldap_configuration_file}");
	system("/usr/share/artica-postfix/bin/artica-install --slapdconf >>$DEBUG_LOG 2>&1");
	
	writeprogress(18,"{restarting_service} {openldap_server} [$suffix] (1/3)");
	shell_exec("$php5 /usr/share/artica-postfix/exec.initslapd.php --ldapd-conf --verbose >>$DEBUG_LOG 2>&1");
	system("/etc/init.d/slapd restart --force --framework=". basename(__FILE__)."-".__LINE__." >>$DEBUG_LOG 2>&1");
	usleep(800);
	writeprogress(19,"{restarting_service} {openldap_server} [$suffix] (2/3)");
	system("/etc/init.d/slapd restart --force --framework=". basename(__FILE__)."-".__LINE__." >>$DEBUG_LOG 2>&1");
	usleep(800);
	writeprogress(20,"{restarting_service} {openldap_server} [$suffix] (3/3)");
	system("/etc/init.d/slapd restart --force --framework=". basename(__FILE__)."-".__LINE__." >>$DEBUG_LOG 2>&1");
	sleep(2);
	writeprogress(22,"{refresh_global_settings}");
	system('/usr/share/artica-postfix/bin/process1 --checkout --force --verbose '. time());
	writeprogress(23,"{scanning_hardware_software}");
	system('/usr/share/artica-postfix/bin/process1 --force --verbose '. time());
	
	$SUBNIC=null;
	
	FINAL___();
	@file_get_contents($pidfile,getmypid());
	
	writeprogress(24,"{restarting_service}: {mysql_server}");
	system('/etc/init.d/mysql restart --force');
	sleep(1);
	
	$users=new usersMenus();
	$q=new mysql();
	writeprogress(25,"{creating_databases}");
	sleep(1);
	$q->BuildTables();
	$sock=new sockets();
	
	
		$CPU_NUMBERS=$unix->CPU_NUMBER();
		if($CPU_NUMBERS==0){$CPU_NUMBERS=4;}
		$MEMORY=$unix->MEM_TOTAL_INSTALLEE();
		$MEMORY_TEXT=FormatBytes($MEMORY);
		$INTEL_CELERON=FALSE;
		writeprogress(25,"CPUs $CPU_NUMBERS - {memory}: $MEMORY_TEXT");
		sleep(2);
		if($MEMORY>1){
			if($unix->MEM_TOTAL_INSTALLEE()<624288){
				@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron", 1);
				@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidPerformance", 3);
				writeprogress(25,"$MEMORY_TEXT = Enable Intel Celeron mode....");
				shell_exec("$php5 /usr/share/artica-postfix/exec.intel.celeron.php");
				$INTEL_CELERON=true;
			}
			
		}
		
		if(!$INTEL_CELERON){
			if($CPU_NUMBERS<2){
				@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron", 1);
				@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidPerformance", 3);
				writeprogress(25,"CPUs:$CPU_NUMBERS = Intel Celeron mode....");
				shell_exec("$php5 /usr/share/artica-postfix/exec.intel.celeron.php");
				$INTEL_CELERON=true;
			}
		}
		if(!$INTEL_CELERON){	
			if($CPU_NUMBERS<3){	
				@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidPerformance", 2);
				writeprogress(25,"CPUs:$CPU_NUMBERS = {features}: {no_statistics}");
				sleep(1);
			}
		}
		
	
		
	writeprogress(26,"{creating_services}");
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.initslapd.php  --force >/dev/null 2>&1 &");
	
	if(is_file($squidbin)){
		writeprogress(27,"{RestartingProxyStatisticsDatabase}");
		shell_exec("/etc/init.d/squid-db restart >>$DEBUG_LOG 2>&1");
	}
	
	$cyrus=$unix->LOCATE_CYRUS_DAEMON();
	if(is_file($cyrus)){
		writeprogress(28,"{restarting_service} SaslAuthd Daemon");
		shell_exec("/etc/init.d/saslauthd restart");
		writeprogress(29,"{restarting_service} Cyrus IMAP Daemon");
		shell_exec("/etc/init.d/cyrus-imapd restart");
		writeprogress(30,"{restarting_service} Postfix Daemon");
		shell_exec("/etc/init.d/postfix restart");
	}
	
	if(isset($savedsettings["GoldKey"])){	
		if(!$sock->IsGoldKey($savedsettings["GoldKey"])){unset($savedsettings["GoldKey"]);}
	}
	
	if(isset($savedsettings["GoldKey"])){
		if($sock->IsGoldKey($savedsettings["GoldKey"])){
			$WORKDIR=base64_decode("L3Vzci9sb2NhbC9zaGFyZS9hcnRpY2E=");
			$WORKFILE=base64_decode('LmxpYw==');
			$WORKPATH="$WORKDIR/$WORKFILE";
			@file_put_contents($WORKPATH, "TRUE");
			$LicenseInfos=unserialize(base64_decode($sock->GET_INFO("LicenseInfos")));
			$LicenseInfos["UUID"]=$savedsettings["UUID_FIRST"];
			$LicenseInfos["TIME"]=time();
			$sock->SaveConfigFile(base64_encode(serialize($LicenseInfos)), "LicenseInfos");
			writeprogress(31,"{register_license}");
			shell_exec("$php5 /usr/share/artica-postfix/exec.web-community-filter.php --register >/dev/null 2>&1");
			writeprogress(32,"{saving_license}");
			shell_exec("$php5 /usr/share/artica-postfix/exec.web-community-filter.php --register-lic >/dev/null 2>&1");
			
		}
	}
	
	
	$ldap=new clladp();
	writeprogress(40,"{building_organization} {$savedsettings["organization"]}");
	if(!$ldap->AddOrganization($savedsettings["organization"])){
		debug_logs("Building organization failed $ldap->ldap_last_error");
		sleep(2);
		if(!$ldap->AddOrganization($savedsettings["organization"])){
			debug_logs("Building organization failed 2/2 $ldap->ldap_last_error");
		}
	}
	sleep(2);
	
	writeprogress(40,"{creating_domain} {$savedsettings["smtp_domainname"]}");
	if(!$ldap->AddDomainEntity($savedsettings["organization"],$savedsettings["smtp_domainname"])){debug_logs("AddDomainEntity failed $ldap->ldap_last_error");}
	sleep(2);
	
	$timezone=$savedsettings["timezones"];
	$sourcefile="/usr/share/zoneinfo/$timezone";
	if(is_file($sourcefile)){
		writeprogress(60,"{timezone} $timezone");
		@unlink("/etc/localtime");
		@copy($sourcefile, "/etc/localtime");
		@file_put_contents("/etc/timezone", $timezone);
	}else{
		writeprogress(60,"$sourcefile no such file");
	}
	sleep(2);
	BUILD_NETWORK();
	
	
	shell_exec("$nohup /etc/init.d/artica-status restart >/dev/null 2>&1 &");
	shell_exec("$nohup /etc/init.d/monit restart >/dev/null 2>&1 &");
	
	$unix->THREAD_COMMAND_SET("$php5 /usr/share/artica-postfix/exec.postfix.maincf.php --reconfigure");
	$unix->THREAD_COMMAND_SET("/usr/share/artica-postfix/bin/artica-install --reconfigure-cyrus");

	$FreeWebAdded=false;
	sleep(3);
	
	if(!is_file("/etc/artica-postfix/WIZARD_INSTALL_EXECUTED")){
		if(!$GLOBALS["NOREBOOT"]){$reboot=true;}
		$rebootWarn=null;
	}

	if(is_file($squidbin)){
		include_once(dirname(__FILE__)."/ressources/class.squid.inc");
		if($SQUIDEnable==1){
			$squid=new squidbee();
			if($AsTransparentProxy==1){
				$squid->hasProxyTransparent=1;
			}
			
			@file_put_contents("/etc/artica-postfix/settings/Daemons/HyperCacheStoreID",1);
			
			
			
			$q=new mysql();
			if($q->COUNT_ROWS("squid_caches_center", "artica_backup")==0){
				$cachename=basename($squid->CACHE_PATH);
				$q->QUERY_SQL("INSERT IGNORE INTO `squid_caches_center` (cachename,cpu,cache_dir,cache_type,cache_size,cache_dir_level1,cache_dir_level2,enabled,percentcache,usedcache,remove)
				VALUES('$cachename',1,'$squid->CACHE_PATH','$squid->CACHE_TYPE','2000','128','256',1,0,0,0)","artica_backup");
			}
			
			$zipfile="/usr/share/artica-postfix/ressources/conf/upload/squid-zip-import.zip";
			
			if(is_file($zipfile)){
				writeprogress(63,"Analyze old squid.conf");
				system("$php5 /usr/share/artica-postfix/exec.squid.import.conf.php --zip");
			}
			
			$squid->SaveToLdap(true);
			writeprogress(65,"{ReconfiguringProxy} {please_wait} 1/2");
			shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --build --force");
		}else{
			writeprogress(63,"{stopping} {proxy_service}");
			shell_exec("/etc/init.d/squid stop");
		}
	}
	
	if($AsCategoriesAppliance==1){
		writeprogress(65,"{starting} Categories service");
		shell_exec("/etc/init.d/ufdbcat start");
	}
	
	if($AsReverseProxyAppliance==1){
		writeprogress(65,"{starting} Reverse Proxy service...");
		system("$php5 /usr/share/artica-postfix/exec.nginx.php --build");
		shell_exec("/etc/init.d/nginx restart");
	}
	

	if(isset($savedsettings["EnablePDNS"])){
		$sock->SET_INFO("EnablePDNS",$savedsettings["EnablePDNS"]);
	}
	
	if(isset($savedsettings["EnableDHCPServer"])){
		$sock->SET_INFO("EnableDHCPServer",$savedsettings["EnableDHCPServer"]);
	}
	if(isset($savedsettings["EnableFreeRadius"])){
		$sock->SET_INFO("EnableFreeRadius",$savedsettings["EnableFreeRadius"]);
		$sock->getFrameWork("freeradius.php?restart=yes");
	}
	

	
	$restart_artica_status=false;
	if($savedsettings["adminwebserver"]<>null){
		writeprogress(67,"{creating_webservices}$rebootWarn");
		$sock->SET_INFO("EnableFreeWeb", 1);
		writeprogress(60,"{restarting_artica_status}");
		$restart_artica_status=true;
		restart_artica_status();
		writeprogress(68,"{restarting_webservices}");
		restart_apache_src();
		writeprogress(69,"{creating_default_website} {$savedsettings["adminwebserver"]}");
		include_once(dirname(__FILE__)."/ressources/class.freeweb.inc");
		$free=new freeweb($savedsettings["adminwebserver"]);
		$free->servername=$savedsettings["adminwebserver"];
		$free->groupware="ARTICA_MINIADM";
		$free->CreateSite();
		writeprogress(69,"{creating_default_website} {$savedsettings["adminwebserver"]}");
		rebuild_vhost($savedsettings["adminwebserver"]);
	}

	if($savedsettings["second_webadmin"]<>null){
		$sock->SET_INFO("EnableFreeWeb", 1);
		if(!$restart_artica_status){
			writeprogress(70,"{creating_webservices}$rebootWarn");
			restart_artica_status();
			restart_apache_src();
		}
		include_once(dirname(__FILE__)."/ressources/class.freeweb.inc");
		$free=new freeweb($savedsettings["second_webadmin"]);
		$free->servername=$savedsettings["second_webadmin"];
		$free->groupware="ARTICA_ADM";
		$free->CreateSite();
		rebuild_vhost($savedsettings["second_webadmin"]);
	}




	if($savedsettings["statsadministrator"]<>null){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT id FROM radgroupcheck WHERE groupname='WebStatsAdm' LIMIT 0,1","artica_backup"));
		$gpid=$ligne["id"];
		if(!is_numeric($gpid)){$gpid=0;}
		if($gpid==0){
			$sql="INSERT IGNORE INTO radgroupcheck  (`groupname`, `attribute`,`op`, `value`) VALUES ('WebStatsAdm', 'Auth-Type',':=', 'Accept');";
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){$gpid=0;}else{$gpid=$q->last_id;}
	
			if($gpid>0){
				$savedsettings["statsadministrator"]=mysql_escape_string2($savedsettings["statsadministrator"]);
				$administratorpass=mysql_escape_string2(url_decode_special_tool($savedsettings["statsadministratorpass"]));
				$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT value FROM radcheck WHERE username='{$savedsettings["statsadministrator"]}' LIMIT 0,1","artica_backup"));
				if(trim($ligne["value"])==null){
					$sql="INSERT IGNORE INTO radcheck (`username`, `attribute`, `value`) VALUES ('{$savedsettings["statsadministrator"]}', 'Cleartext-Password', '{$savedsettings["statsadministratorpass"]}');";
					$q->QUERY_SQL($sql,"artica_backup");
				}else{
					$sql="UPDATE radcheck SET `value`='{$savedsettings["statsadministratorpass"]}' WHERE username='{$savedsettings["statsadministrator"]}'";
					$q->QUERY_SQL($sql,"artica_backup");
					if(!$q->ok){echo $q->mysql_error;}
				}
					
				$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT username FROM radcheck WHERE username='{$savedsettings["statsadministrator"]}' AND groupname='WebStatsAdm' LIMIT 0,1","artica_backup"));
				if(trim($ligne["username"])==null){
					$sql="insert into radusergroup (username, groupname, priority,gpid) VALUES ('{$savedsettings["statsadministrator"]}', 'WebStatsAdm', 1,$gpid);";
					$q->QUERY_SQL($sql,"artica_backup");
					if(!$q->ok){echo $q->mysql_error;}
				}
			}
		}
	}
	$reboot=false;
	writeprogress(80,"{checking_parameters}$rebootWarn");
	
	
	if(!is_file("/etc/artica-postfix/WIZARD_INSTALL_EXECUTED")){
		@file_put_contents("/etc/artica-postfix/WIZARD_INSTALL_EXECUTED", time());

	}
	
	$unix->THREAD_COMMAND_SET("$php5 /usr/share/artica-postfix/exec.initslapd.php");
	

	
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	if($EnableKerbAuth==1){
		writeprogress(82,"{LaunchActiveDirectoryConnection}...");
		system("$php5 /usr/share/artica-postfix/exec.kerbauth.php --build --force --verbose >>$DEBUG_LOG 2>&1");
	}
	
	$WizardWebFilteringLevel=$sock->GET_INFO("WizardWebFilteringLevel");
	if(is_numeric($WizardWebFilteringLevel)){$savedsettings["EnableWebFiltering"]=1;}
	
	if($savedsettings["EnableWebFiltering"]==1){
		writeprogress(82,"{activate_webfiltering_service}...");
		sleep(2);
		EnableWebFiltering();
		
	}else{
		writeprogress(82,"{no_web_filtering}");
		sleep(2);
		
	}

	
	if($users->POSTFIX_INSTALLED){
		$unix->THREAD_COMMAND_SET("$php5 /usr/share/artica-postfix/exec.postfix.maincf.php --build --force >>$DEBUG_LOG 2>&1");
	}
	
	
	writeprogress(83,"{RestartingArticaStatus}");
	system("/etc/init.d/artica-status restart --force");
	
	
	$serverbin=$unix->find_program("zarafa-server");
	if(is_file($serverbin)){
		writeprogress(85,"{restarting_zarafa_services}$rebootWarn");
		shell_exec("$php5 /usr/share/artica-postfix/exec.initdzarafa.php");
		shell_exec("$php5 /usr/share/artica-postfix/exec.zarafa-db.php --init");
		shell_exec("/etc/init.d/zarafa-db restart");
		shell_exec("/etc/init.d/zarafa-server restart");
		shell_exec("/etc/init.d/zarafa-web restart");
	}
	
	writeprogress(90,"{restarting_services}$rebootWarn");
	shell_exec("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");
	shell_exec("$nohup /etc/init.d/monit restart >/dev/null 2>&1 &");
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.monit.php --build >/dev/null 2>&1");
	shell_exec("$nohup /usr/share/artica-postfix/exec.web-community-filter.php --register  >/dev/null 2>&1 &");
	
	$EnableArticaMetaClient=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaMetaClient"));
	$EnableArticaMetaServer=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaMetaServer"));
	if($EnableArticaMetaServer==1){$EnableArticaMetaClient=0;}
	
	if($EnableArticaMetaClient==1){shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-client.php --ping --force >/dev/null 2>&1 &"); }
	
	if(is_file($squidbin)){
		if($SQUIDEnable==1){
			
			$q=new mysql_squid_builder();
			if($q->COUNT_ROWS("proxy_ports")==0){
				$WizardSqlWait=unserialize(@file_get_contents("/etc/artica-postfix/settings/Daemons/WizardSqlWait"));
				while (list ($none, $sql) = each ($WizardSqlWait)){
					$q->QUERY_SQL($sql);
				}
			}
			
			@mkdir("/var/log/squid",0755,true);
			@chown("/var/log/squid","squid");
			@chgrp("/var/log/squid", "squid");
			@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidUrgency", 0);
			@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidUFDBUrgency", 0);
			@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidSSLUrgency", 0);
			@file_put_contents("/etc/artica-postfix/settings/Daemons/LogsWarninStop", 0);
			@file_put_contents("/etc/artica-postfix/settings/Daemons/ActiveDirectoryEmergency", 0);
			writeprogress(95,"{ReconfiguringProxy} {please_wait} 2/2");
			shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --build --force");
			writeprogress(97,"{checking_hypercache_feature} {please_wait}");
			shell_exec("$php5 /usr/share/artica-postfix/exec.hypercache-dedup.php --wizard");
		}
	}
	
	writeprogress(98,"{empty_watchdog_events} {please_wait}");
	$q=new mysql();
	$q->QUERY_SQL("TRUNCATE TABLE squid_admin_mysql","artica_events");

	$time=$unix->file_time_min("/etc/artica-postfix/WIZARD_INSTALL_EXECUTED");
	if(!$reboot){
		writeprogress(100,"{done}");
		FINAL___();
		return;
	}
	writeprogress(100,"Rebooting");
	FINAL___();
	sleep(10);
	shell_exec($unix->find_program("reboot"));
}

function FINAL___(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.web-community-filter.php --register >/dev/null 2>&1 &");
	shell_exec($cmd);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.schedules.php --output >/dev/null 2>&1 &");
	shell_exec($cmd);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.php --build-schedules >/dev/null 2>&1 &");
	shell_exec($cmd);
	
	$articafiles=$unix->SquidPHPFiles();

	while (list ($num, $filename) = each ($articafiles) ){
		$filepath="/usr/share/artica-postfix/$num";
		@chmod($filepath,0755);
		@chown($filepath,"squid");
		@chgrp($filepath,"squid");
	
	}
	
	$files=$unix->DirFiles("/usr/share/artica-postfix/bin");
	while (list ($filename,$line) = each ($files)){
		@chmod("/usr/share/artica-postfix/bin/$filename",0755);
		@chown("/usr/share/artica-postfix/bin/$filename","root");
	}
	
	system("/etc/init.d/slapd restart --force --framework=". basename(__FILE__)."-".__LINE__);
	
}

function BUILD_NETWORK(){
	$SUBNIC=null;
	$unix=new unix();
	$sock=new sockets();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	
	
	$savedsettings=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/WizardSavedSettings")));
	$KEEPNET=$savedsettings["KEEPNET"];
	if($KEEPNET==1){return;}
	
	
	$netbiosname=$savedsettings["netbiosname"];
	
	if(strlen($netbiosname)>15){$netbiosname=substr(0, 15,$netbiosname);}
	
	
	if(isset($savedsettings["domain"])){
			$domainname=$savedsettings["domain"];
			$SEARCH_DOMAIN=$domainname;
	}
	
	
	$EnableKerbAuth=intval($sock->GET_INFO("EnableKerbAuth"));
	
	if($EnableKerbAuth==1){
		$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
		if(isset($array["WINDOWS_DNS_SUFFIX"])){
			$SEARCH_DOMAIN=$array["WINDOWS_DNS_SUFFIX"];
			$domainname=$SEARCH_DOMAIN;
		}
	}
	
	
	
	$Encoded=base64_encode(serialize($savedsettings));
	@file_put_contents("/etc/artica-postfix/settings/Daemons/WizardSavedSettings", $Encoded);
	
	
	
	if(!isset($savedsettings["NIC"])){$savedsettings["NIC"]="eth0";}
	
	$NIC=$savedsettings["NIC"];
	
	if(preg_match("#(.+?):([0-9]+)#", $savedsettings["NIC"],$re)){
		$NIC=trim($re[1]);
		$SUBNIC=$re[2];
	
	}
	
	writeprogress(60,"{building_networks}");
	$nics=new system_nic($NIC);
	$nics->CheckMySQLFields();
	
	$dhclient=$unix->find_program("dhclient");
	if(is_file($dhclient)){
		$pid=$unix->PIDOF($dhclient);
		if($unix->process_exists($pid)){
			$unix->KILL_PROCESS($pid,9);
		}
	}
	
	$nics->eth=$NIC;
	if($SUBNIC<>null){
		$nics->IPADDR="127.0.0.2";
		$nics->NETMASK="255.255.255.255";
		$nics->GATEWAY="0.0.0.0";
		$nics->BROADCAST="0.0.0.0";
		$nics->DNS1=$savedsettings["DNS1"];;
		$nics->DNS2=$savedsettings["DNS2"];;
		$nics->dhcp=0;
		$nics->metric=$savedsettings["metric"];
		$nics->enabled=1;
		$nics->defaultroute=1;
	}else{
		$nics->IPADDR=$savedsettings["IPADDR"];
		$nics->NETMASK=$savedsettings["NETMASK"];;
		$nics->GATEWAY=$savedsettings["GATEWAY"];;
		$nics->BROADCAST=$savedsettings["BROADCAST"];;
		$nics->DNS1=$savedsettings["DNS1"];;
		$nics->DNS2=$savedsettings["DNS2"];;
		$nics->dhcp=0;
		$nics->metric=$savedsettings["metric"];
		$nics->enabled=1;
		$nics->defaultroute=1;
	}
	writeprogress(60,"{saving_network}");
	$nics->SaveNic();
	
	
	if($SUBNIC<>null){
		$q=new mysql();
	
		$sql="INSERT INTO nics_virtuals (ID,nic,org,ipaddr,netmask,cdir,gateway,ForceGateway,failover,metric)
		VALUES('$SUBNIC','$NIC','','{$savedsettings["IPADDR"]}','{$savedsettings["NETMASK"]}',
		'','{$savedsettings["GATEWAY"]}',0,0,1);";
		$q->QUERY_SQL($sql,"artica_backup");
	
	
		$sql="UPDATE nics_virtuals SET nic='$NIC',
		org='',
		ipaddr='{$savedsettings["IPADDR"]}',
		netmask='{$savedsettings["NETMASK"]}',
		cdir='',
				gateway='{$savedsettings["GATEWAY"]}',
				ForceGateway='0',
				failover='0',
				metric='1'
				WHERE ID=$SUBNIC";
				$q->QUERY_SQL($sql,"artica_backup");
	
	}
	
	
	
	
	writeprogress(60,"Loading resolv library");
		$resolv=new resolv_conf();
		$arrayNameServers[0]=$savedsettings["DNS1"];
		$arrayNameServers[1]=$savedsettings["DNS2"];
		$resolv->MainArray["DNS1"]=$arrayNameServers[0];
		$resolv->MainArray["DNS2"]=$arrayNameServers[1];
		$resolv->MainArray["DOMAINS1"]=$SEARCH_DOMAIN;
		
		
	writeprogress(60,"Saving DNS settings");
		$resolv->save();
	
		
	
	
	
	
		$nic=new system_nic();
		writeprogress(60,"{set_new_hostname} $netbiosname.$domainname");
		$nic->set_hostname("$netbiosname.$domainname");
		$php=$unix->LOCATE_PHP5_BIN();
		$nohup=$unix->find_program("nohup");
	
		writeprogress(60,"{building_resolv_configuration}");
		shell_exec(trim("$nohup ".$unix->LOCATE_PHP5_BIN(). " /usr/share/artica-postfix/exec.virtuals-ip.php --resolvconf >/dev/null 2>&1"));
	
	
	writeprogress(60,"{building_networks_scripts}");
		shell_exec("$php5 /usr/share/artica-postfix/exec.virtuals-ip.php >/dev/null 2>&1");	
	
}



function create_articaweb($websitename){
	if($websitename==null){return;}
	$sock=new sockets();
	$sock->SET_INFO("EnableFreeWeb", 1);
	restart_artica_status();
	restart_apache_src();
	include_once(dirname(__FILE__)."/ressources/class.freeweb.inc");
	$free=new freeweb($websitename);
	$free->servername=$websitename;
	$free->groupware="ARTICA_ADM";
	$free->CreateSite();
	rebuild_vhost($websitename);
}
function rebuild_vhost($servername){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.freeweb.php --sitename $servername >/dev/null 2>&1");
	shell_exec($cmd);
	$unix->THREAD_COMMAND_SET("$php /usr/share/artica-postfix/exec.freeweb.php --sitename $servername");

}
function restart_artica_status(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");	
	shell_exec("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");
	
}

function restart_apache_src(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup /etc/init.d/artica-postfix restart apachesrc >/dev/null 2>&1 &");	
	
}
function EnableWebFiltering(){
	$q=new mysql_squid_builder();
	$q->CheckTables();
	$unix=new unix();
	$sock=new sockets();
	writeprogress(82,"{activate_webfiltering_service} {check_tables}");
	$q->CheckTables();	
	//$q->QUERY_SQL("INSERT INTO `webfilter_rules` (`ID`, `groupmode`, `enabled`, `groupname`, `BypassSecretKey`, `endofrule`, `blockdownloads`, `naughtynesslimit`, `searchtermlimit`, `bypass`, `deepurlanalysis`, `UseExternalWebPage`, `ExternalWebPage`, `freeweb`, `sslcertcheck`, `sslmitm`, `GoogleSafeSearch`, `TimeSpace`, `TemplateError`, `TemplateColor1`, `TemplateColor2`, `RewriteRules`, `zOrder`, `AllSystems`, `UseSecurity`, `embeddedurlweight`) VALUES (1, 1, 1, 'Everybody', '', 'any', 0, 50, 30, 0, 0, 0, '', '', 0, 0, 0, '', '', NULL, NULL, '', 0, 1, 0, NULL);");
	//$q->QUERY_SQL("INSERT INTO `webfilter_blkgp` (`ID`, `groupname`, `enabled`) VALUES (1, 'Dangerous surf', 1);");
	//$q->QUERY_SQL("INSERT INTO `webfilter_blklnk` (`ID`, `zmd5`, `webfilter_blkid`, `webfilter_ruleid`, `blacklist`) VALUES (1, '5f93f983524def3dca464469d2cf9f3e', 1, 1, 0);");
	//$q->QUERY_SQL("INSERT INTO `webfilter_blkcnt` (`ID`, `webfilter_blkid`, `category`) VALUES (1, 1, 'hacking'), (2, 1, 'phishtank'), (3, 1, 'phishing'), (4, 1, 'proxy'), (5, 1, 'malware'), (6, 1, 'spyware'), (7, 1, 'suspicious'), (8, 1, 'tracker'), (9, 1, 'warez');");
	@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableUfdbGuard", 1);
	$php=$unix->LOCATE_PHP5_BIN();
	$WizardWebFilteringLevel=$sock->GET_INFO("WizardWebFilteringLevel");
	
	
	$ARRAYF[0]="{block_sexual_websites}";
	$ARRAYF[1]="{block_susp_websites}";
	$ARRAYF[2]="{block_multi_websites}";
	writeprogress(82,$ARRAYF[2]);
	sleep(2);
	
	$array["malware"]=true;
	$array["warez"]=true;
	$array["hacking"]=true;
	$array["phishing"]=true;
	$array["spyware"]=true;
	
	$array["weapons"]=true;
	$array["violence"]=true;
	$array["suspicious"]=true;
	$array["paytosurf"]=true;
	$array["sect"]=true;
	$array["proxy"]=true;
	$array["gamble"]=true;
	$array["redirector"]=true;
	$array["tracker"]=true;
	$array["publicite"]=true;
	
	if($WizardWebFilteringLevel==0){
		$array["porn"]=true;
		$array["agressive"]=true;
		$array["dynamic"]=true;
	
		$array["alcohol"]=true;
		$array["astrology"]=true;
		$array["dangerous_material"]=true;
		$array["drugs"]=true;
		$array["hacking"]=true;
		$array["tattooing"]=true;
		$array["terrorism"]=true;
	
		$array["dating"]=true;
		$array["mixed_adult"]=true;
		$array["sex/lingerie"]=true;
		
		
		$array["marketingware"]=true;
		$array["mailing"]=true;
		$array["downloads"]=true;
		$array["gamble"]=true;
	}
	
	
	if($WizardWebFilteringLevel==1){
		$array["porn"]=true;
		$array["dating"]=true;
		$array["mixed_adult"]=true;
		$array["sex/lingerie"]=true;
	}
	if($WizardWebFilteringLevel==2){
		$array["publicite"]=true;
		$array["tracker"]=true;
		$array["marketingware"]=true;
		$array["mailing"]=true;
	}
	if($WizardWebFilteringLevel==3){
		$array["youtube"]=true;
		$array["audio-video"]=true;
		$array["webtv"]=true;
		$array["music"]=true;
		$array["movies"]=true;
		$array["games"]=true;
		$array["gamble"]=true;
		$array["socialnet"]=true;
		$array["webradio"]=true;
		$array["chat"]=true;
		$array["webphone"]=true;
		$array["downloads"]=true;
	}	
	$ruleid=0;
	
	writeprogress(82,"{activate_webfiltering_service}: {creating_rules}");
	
	while (list ($key, $val) = each ($array) ){
		$q=new mysql_squid_builder();
		$q->QUERY_SQL("DELETE FROM webfilter_blks WHERE category='$key' AND modeblk=0 AND webfilter_id='$ruleid'");
		$q->QUERY_SQL("INSERT IGNORE INTO webfilter_blks (webfilter_id,category,modeblk) VALUES ('$ruleid','$key','0')");
		if(!$q->ok){echo $q->mysql_error_html();return;}
	}
	$q->QUERY_SQL("DELETE FROM webfilter_blks WHERE category='liste_bu' AND modeblk=1 AND webfilter_id='$ruleid'");
	$q->QUERY_SQL("INSERT IGNORE INTO webfilter_blks (webfilter_id,category,modeblk) VALUES ('$ruleid','liste_bu','1')");
	
	
	
	@file_put_contents("/etc/artica-postfix/settings/Daemons/SquidUrgency", 0);
	@chmod("/etc/artica-postfix/settings/Daemons/SquidUrgency",0755);
	writeprogress(82,"{activate_webfiltering_service}: {building_settings}");
	shell_exec("$php /usr/share/artica-postfix/exec.squidguard.php --build --force >/dev/null 2>&1");
	
	writeprogress(82,"{activate_webfiltering_service}: {reconfiguring_proxy_service}");
	shell_exec("$php /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1");
	writeprogress(82,"{activate_webfiltering_service} {restarting_proxy_service}");
	shell_exec("/etc/init.d/squid restart --force");
	writeprogress(82,"{activate_webfiltering_service} {restarting_webfiltering_service}");
	shell_exec("/etc/init.d/ufdb restart --force");
	writeprogress(82,"{activate_webfiltering_service} {done}");
}

?>