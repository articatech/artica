#!/usr/bin/php -q
<?php
$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}



include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.dhcpd.inc');
include_once(dirname(__FILE__) . '/ressources/class.computers.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');
include_once(dirname(__FILE__) . '/ressources/class.postgres.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');

if($argv[1]=="--parse-leases"){parseLeases();die();}
if($argv[1]=="--empty-leases-progress"){empty_leases_progress();exit;}

if($argv[1]=="commit"){
	dhcpd_logs("commit: {$argv[2]} {$argv[3]} {$argv[4]}");
	update_commit($argv[2],$argv[3],$argv[4]);
	die(0);
}

if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
$cache_file="/etc/artica-postfix/dhcpd.leases.dmp";
$unix=new unix();
	
if($unix->process_exists(@file_get_contents($pidfile,basename(__FILE__)))){
	if($GLOBALS["VERBOSE"]){echo " --> Already executed.. ". @file_get_contents($pidfile). " aborting the process\n";}
	die();
}
@file_put_contents($pidfile, getmypid());

if(!$GLOBALS["FORCE"]){
	$TimeFile=$unix->file_time_min($cache_file);
	if($TimeFile<30){
		if($GLOBALS["VERBOSE"]){echo " {$TimeFile}Mn, require 30mn\n";}
		die();
	}
}

$pid=dhcpd_pid();
if(!$unix->process_exists($pid)){
	if($GLOBALS["VERBOSE"]){echo " --> DHCPD service not running...\n";}
	return;
}

$ptime=$unix->PROCCESS_TIME_MIN($pid);
if($ptime<2){
	if($GLOBALS["VERBOSE"]){echo " --> DHCPD service running before 2mn...\n";}
	return;
}



$GLOBALS["nmblookup"]=$unix->find_program("nmblookup");
if($argv[1]=="lookup"){echo "{$argv[2]}:".nmblookup($argv[2],$argv[3])."\n";die();}

if($argv[1]=='--single-computer'){die();}
if($GLOBALS["VERBOSE"]){
	echo " --> Argument={$argv[1]}\n";
	echo " --> Force={$GLOBALS["FORCE"]}\n";

}

$sock=new sockets();
$EnableDHCPServer=$sock->GET_INFO('EnableDHCPServer');
$ComputersAllowDHCPLeases=$sock->GET_INFO("ComputersAllowDHCPLeases");
if($ComputersAllowDHCPLeases==null){$ComputersAllowDHCPLeases=1;}
if($EnableDHCPServer==0){writelogs("EnableDHCPServer is disabled, aborting...","MAIN",__FILE__,__LINE__);die();}
if($ComputersAllowDHCPLeases==0){
	if($GLOBALS["VERBOSE"]){echo " -->ComputersAllowDHCPLeases is disabled -> die()\n";}
	writelogs("ComputersAllowDHCPLeases is disabled, aborting...","MAIN",__FILE__,__LINE__);
	die();
}










localsyslog("Check changed leases...");
if(!$GLOBALS["FORCE"]){if($GLOBALS["VERBOSE"]){echo " -->Changed()\n";}if(!Changed()){die();}}

$datas=@file_get_contents("/var/lib/dhcp3/dhcpd.leases");
$md5Tampon=md5_file("/var/lib/dhcp3/dhcpd.leases");
$md5Local=md5_file("/etc/artica-postfix/dhcpd.leases.dmp");

dhcpd_logs("dhcpd.leases.dmp: $md5Local / $md5Tampon");

if($GLOBALS["VERBOSE"]){echo " --> MD5LOCAL=$md5Local / MD5Tampon=$md5Tampon\n";}

if(!$GLOBALS["FORCE"]){
	if($md5Local==$md5Tampon){
		if($GLOBALS["VERBOSE"]){echo " --> $md5Local == $md5Tampon, abort\n";}
		die();
	}
}

@unlink($cache_file);
@file_put_contents($cache_file,$md5Tampon);


writelogs("LOCAL:$md5Local !== REMOTE:$md5Tampon","MAIN",__FILE__,__LINE__);
write_syslog("integrity of dhcpd.leases has been modified ( from $md5Local to $md5Tampon), analyze the leases",basename(__FILE__));

if($GLOBALS["VERBOSE"]){echo " --> CleanFile()\n";}
CleanFile();
if($GLOBALS["VERBOSE"]){echo " --> /var/lib/dhcp3/dhcpd.leases\n";}
$datas=@file_get_contents("/var/lib/dhcp3/dhcpd.leases");

$md5=md5($datas);
if(!preg_match_all("#lease\s+(.+?)\s+{(.+?)\}#is",$datas,$re)){
	if($GLOBALS["VERBOSE"]){echo " --> Unable to preg_match\n";}
	events("Unable to preg_match","main",__LINE__);
	die();
}

$dhcp=new dhcpd();
$unix=new unix();
$GLOBALS["nmblookup"]=$unix->find_program("nmblookup");


$GLOBALS["domain"]=$dhcp->ddns_domainname;
if($GLOBALS["VERBOSE"]){echo " -->domain $dhcp->ddns_domainname\n";}
if($GLOBALS["VERBOSE"]){echo " -->Table ".count($re[1])." rows\n";}
$sql="TRUNCATE TABLE `dhcpd_leases`";
$q=new mysql();
if($GLOBALS["VERBOSE"]){echo "$sql\n";}
$q->QUERY_SQL($sql,"artica_backup");
$GLOBALS["FIXIPHOST"]=false;

$c=0;
while (list ($num, $ligne) = each ($re[1]) ){
	if($GLOBALS["VERBOSE"]){echo "Checking $ligne\n";}
	$c++;
	$ip=$ligne;
	$HOST=null;
	$MAC=null;
	$starts="0000-00-00 00:00:00";
	$ends="0000-00-00 00:00:00";
	$tstp="0000-00-00 00:00:00";
	$atsfp="0000-00-00 00:00:00";
	$cltt="0000-00-00 00:00:00";
	if($GLOBALS["VERBOSE"]){echo "checking {$re[2][$num]}\n";}
	if(preg_match("#hardware ethernet\s+(.+?);\s+#is",$re[2][$num],$ri)){$MAC=trim($ri[1]);}
	if(preg_match("#client-hostname \"(.+?)\";#",$re[2][$num],$ri)){$HOST=trim($ri[1]);}
	if(preg_match("#starts\s+([0-9])\s+([0-9]+)\/([0-9]+)\/([0-9]+)\s+(.+?);#",$re[2][$num],$ri)){$starts="{$ri[2]}-{$ri[3]}-{$ri[4]} {$ri[5]}";}
	if(preg_match("#ends\s+([0-9])\s+([0-9]+)\/([0-9]+)\/([0-9]+)\s+(.+?);#",$re[2][$num],$ri)){$ends="{$ri[2]}-{$ri[3]}-{$ri[4]} {$ri[5]}";}
	if(preg_match("#tstp\s+([0-9])\s+([0-9]+)\/([0-9]+)\/([0-9]+)\s+(.+?);#",$re[2][$num],$ri)){$tstp="{$ri[2]}-{$ri[3]}-{$ri[4]} {$ri[5]}";}
	if(preg_match("#atsfp\s+([0-9])\s+([0-9]+)\/([0-9]+)\/([0-9]+)\s+(.+?);#",$re[2][$num],$ri)){$atsfp="{$ri[2]}-{$ri[3]}-{$ri[4]} {$ri[5]}";}
	if(preg_match("#cltt\s+([0-9])\s+([0-9]+)\/([0-9]+)\/([0-9]+)\s+(.+?);#",$re[2][$num],$ri)){$cltt="{$ri[2]}-{$ri[3]}-{$ri[4]} {$ri[5]}";}
	
	
	
	
	$MAC=trim($MAC);
	$HOST=trim($HOST);
	$ip=str_replace("lease",'',$ip);
	$ip=trim($ip);
	if($MAC<>null){
		if(isset($alredyDoneMAC[$MAC])){continue;}
		
	}
	
	
	
	$alredyDoneMAC[$MAC]=$MAC;
	$comp=new computers();
	if($GLOBALS["VERBOSE"]){echo "checking $uid comp->ComputerIDFromMAC($MAC)\n";}
	$uid=$comp->ComputerIDFromMAC($MAC);
	if($GLOBALS["VERBOSE"]){echo " LOOP --> $ip ($uid)=$HOST\n";}
	if($HOST==null){$HOST=trim($uid);}
	$dns=true;
	$ip=nmblookup($HOST,$ip);
	writelogs("$c/". count($re[1])."] ************************************************","MAIN",__FILE__,__LINE__);
	writelogs("CHECK $ip ($uid) $HOST","MAIN",__FILE__,__LINE__);
	$HOST_sql=$HOST;
	if($GLOBALS["VERBOSE"]){echo "$MAC \"{$HOST}\"[$ip] $starts -> $ends\n";}
	$HOST_sql=str_replace("$", "", $HOST_sql);
	$HOST_sql=trim(strtolower($HOST_sql));
	$sql="INSERT IGNORE INTO dhcpd_leases (`mac`,`hostname`,`starts`,`ends`,`cltt`,`tstp`,`atsfp`,`ipaddr`) VALUES
	('$MAC','$HOST_sql','$starts','$ends','$cltt','$tstp','$atsfp','$ip');";
	$q->QUERY_SQL($sql,"artica_backup");
	
	
	$sock=new sockets();
	
	$ping=$unix->PingHost($ip);
	
	if($uid==null){
		if($HOST==null){$uid=$ip.'$';}else{$uid=$HOST.'$';}
		$comp=new computers();
		$comp->ComputerRealName=$HOST;
		$comp->ComputerMacAddress=$MAC;
		if($ping){$comp->ComputerIP=$ip;}
		$comp->DnsZoneName=$GLOBALS["domain"];
		$comp->uid=$uid;
		$ComputerRealName=$HOST;
		writelogs("Adding new computer $uid",__FUNCTION__,__FILE__,__LINE__);
		$comp->Add();
	}else{
		$MustEdit=false;
		$comp=new computers($uid);
		if($ping){
			if($comp->ComputerIP<>$ip){
				writelogs("Editing $uid because $ip did not match $comp->ComputerIP",__FUNCTION__,__FILE__,__LINE__);
				$comp->ComputerIP=$ip;	
				$MustEdit=true;
			}
			
		}
		
		if(strtolower($comp->DnsZoneName)<>strtolower($GLOBALS["domain"])){
			writelogs("Editing $uid because $comp->DnsZoneName did not match {$GLOBALS["domain"]}",__FUNCTION__,__FILE__,__LINE__);
			$comp->DnsZoneName=$GLOBALS["domain"];
			$MustEdit=true;
		}
		if($MustEdit){$comp->Edit(basename(__FILE__));}
		
		
		
		if($comp->ComputerRealName==null){$ComputerRealName=$uid;}else{
			if(!preg_match("#[0-9]+\.[0-9]+\.#",$comp->ComputerRealName)){
				$ComputerRealName=$comp->ComputerRealName;
			}
		}
		
		$ComputerRealName=$comp->ComputerRealName;
		
		
	}
	
	if($ping){
		$unix=new unix();
		if($GLOBALS["VERBOSE"]){echo " --> /etc/hosts $ComputerRealName -> $ip\n";}
		$unix->del_EtcHosts($ip);
		$dns=new pdns($GLOBALS["domain"]);
		writelogs("EditIPName -> ComputerRealName=`$ComputerRealName` $ip $MAC",__FUNCTION__,__FILE__,__LINE__);
		if(trim($ComputerRealName)<>null){$dns->EditIPName(strtolower($ComputerRealName),$ip,'A',$MAC);}
		$GLOBALS["FIXIPHOST"]=true;
		
	}
	
}

if($GLOBALS["FIXIPHOST"]){
	writelogs("-> exec.samba.php --fix-etc-hosts",__FUNCTION__,__FILE__,__LINE__);
	shell_exec(LOCATE_PHP5_BIN2()." ". dirname(__FILE__)."/exec.samba.php --fix-etc-hosts");	
}

events("Set content cache has $md5","main",__LINE__);
$sock->SET_INFO('DHCPLeaseMD5',$md5);



function events($text,$function,$line){
		writelogs($text,$function,__FILE__,$line);
}


function Changed(){
	if(!is_file("/var/lib/dhcp3/dhcpd.leases")){
		if($GLOBALS["VERBOSE"]){echo " --> unable to stat /var/lib/dhcp3/dhcpd.leases\n";}
		return false;
	}
	$sock=new sockets();
	@chown("/var/lib/dhcp3/dhcpd.leases", "dhcpd");
	$DHCPLeaseMD5=$sock->GET_INFO('DHCPLeaseMD5');
	if($DHCPLeaseMD5==null){return true;}
	$datas=@file_get_contents("/var/lib/dhcp3/dhcpd.leases");
	$md5=md5($datas);
	if($GLOBALS["VERBOSE"]){echo " --> $DHCPLeaseMD5 Current: $md5\n";}
	if(trim($DHCPLeaseMD5)==$md5){
		if($GLOBALS["VERBOSE"]){echo " --> Not changed\n";}
		return false;
	}
	return true;
}

function CleanFile(){
	$datas=@file_get_contents("/var/lib/dhcp3/dhcpd.leases");
	if($GLOBALS["VERBOSE"]){echo " --> /var/lib/dhcp3/dhcpd.leases ". strlen($datas)." bytes\n";}
	$tbl=explode("\n",$datas);
	while (list ($num, $ligne) = each ($tbl) ){
		if(preg_match("#^\##",$ligne)){
			unset($tbl[$num]);
		}
	}
	writelogs("/var/lib/dhcp3/dhcpd.leases cleaned",__FUNCTION__,__FILE__,__LINE__);
	if($GLOBALS["VERBOSE"]){echo " --> /var/lib/dhcp3/dhcpd.leases cleaned...\n";}
	@file_put_contents("/var/lib/dhcp3/dhcpd.leases",implode("\n",$tbl));
}

function update_computer($ip,$mac,$name){
	$sock=new sockets();	
	$ComputersAllowDHCPLeases=$sock->GET_INFO("ComputersAllowDHCPLeases");
	if($ComputersAllowDHCPLeases==null){$ComputersAllowDHCPLeases=1;}
	if($ComputersAllowDHCPLeases==0){localsyslog("`ComputersAllowDHCPLeases` Aborting updating the LDAP database");return;}	
	
	$mac=trim($mac);
	$name=trim(strtolower($name));
	$ip=trim($ip);
	if($ip==null){return;}
	if($mac==null){return;}
	if($name==null){return;}
	$mac=strtolower(str_replace("-", ":", $mac));
	$ipClass=new IP();
	if($ipClass->isIPAddress($name)){
		localsyslog("`$name` is a TCP IP address, aborting updating the LDAP database");return;
	}
	
	
	
	$ip=nmblookup($name,$ip);
	$dhcp=new dhcpd();
	$GLOBALS["domain"]=$dhcp->ddns_domainname;	
	
	$comp=new computers();
	$uid=$comp->ComputerIDFromMAC($mac);
	
	if(strpos($name, ".")>0){
		$NAMETR=explode(".",$name);
		$name=$NAMETR[0];
		unset($NAMETR[0]);
		$GLOBALS["domain"]=@implode(".", $NAMETR);
	}
	
	if($ipClass->isIPAddress($uid)){	
		$comp=new computers($uid);
		localsyslog("Removing computer ($uid) $mac");
		$comp->DeleteComputer();
		$uid=null;
		$uid=$comp->ComputerIDFromMAC($mac);
	}
	
	localsyslog("$mac -> uid:`$uid`");
	
	if($uid==null){
		$add=true;
		$uid="$name$";
		$comp=new computers();
		$comp->ComputerRealName=$name;
		$comp->ComputerMacAddress=$mac;
		$comp->ComputerIP=$ip;
		$comp->DnsZoneName=$GLOBALS["domain"];
		$comp->uid=$uid;
		$ComputerRealName=$name;
		localsyslog("Create new computer $name[$ip] ($uid) $mac in domain $comp->DnsZoneName");
		$comp->Add();

	}else{
		$comp=new computers($uid);
		if(strpos($comp->ComputerRealName, ".")>0){
			$NAMETR=explode(".",$name);
			$comp->ComputerRealName=$NAMETR[0];
		}
		
		if($comp->ComputerRealName==null){$comp->ComputerRealName=$name;}
		if($ipClass->isIPAddress($comp->ComputerRealName)){$comp->ComputerRealName=$name;}
		$comp->ComputerIP=$ip;
		$comp->DnsZoneName=$GLOBALS["domain"];
		localsyslog("Update computer $comp->ComputerRealName[$ip] ($uid) $mac in domain $comp->DnsZoneName");
		$comp->Edit();
		
	}
	
	
	$dns=new pdns($GLOBALS["domain"]);
	$dns->EditIPName(strtolower($name),$ip,'A',$mac);	

}


function nmblookup($hostname,$ip){
	if(trim($hostname)==null){return $ip;}
	$hostname=str_replace('$','',$hostname);
	if($GLOBALS["nmblookup"]==null){
		$unix=new unix();
		$GLOBALS["nmblookup"]=$unix->find_program("nmblookup");
	}
	
	if($GLOBALS["nmblookup"]==null){
		if($GLOBALS["VERBOSE"]){echo " nmblookup:: --> Could not found binary\n";}
		return $ip;
	}
	if(preg_match("#^[0-9]+\.[0-9]+.[0-9]+\.[0-9]+$#",$hostname)){
		if($GLOBALS["VERBOSE"]){echo " nmblookup:: --> hostname match IP string, aborting\n";}
		return $ip;
	}
	
	if(preg_match("#([0-9]+)\.([0-9]+).([0-9]+)\.([0-9]+)#",$ip,$re)){
		$broadcast="{$re[1]}.{$re[2]}.{$re[3]}.255";
	}else{
		if($GLOBALS["VERBOSE"]){echo " nmblookup:: --> $ip not match for broadcast addr\n";}
		return $ip;
	}
	
	if($GLOBALS["VERBOSE"]){echo " nmblookup:: --> broadcast=$broadcast\n";}
	$cmd="{$GLOBALS["nmblookup"]} -B $broadcast $hostname";
	if($GLOBALS["VERBOSE"]){echo " nmblookup:: --> $cmd\n";}
	exec($cmd,$results);
	
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#Got a positive name query response from\s+([0-9\.]+)#",$ligne,$re)){
			if($GLOBALS["VERBOSE"]){echo " nmblookup:: --> {$re[1]}\n";}
			return $re[1];
		}
	}
	if($GLOBALS["VERBOSE"]){echo " nmblookup:: --> NO MATCH\n";}
	return $ip;
}


function localsyslog($text){
	dhcpd_logs($text);
	
}

function update_commit($ip,$mac,$hostname){
	
	$ipClass=new IP();
	
	if(!$ipClass->isValid($ip)){return;}
	
	$macZ=explode(":",$mac);
	while (list ($num, $ligne) = each ($macZ) ){
		if(strlen($ligne)==1){$macZ[$num]="0$ligne";}
		
	}
	$mac=@implode(":", $macZ);
	
	if(preg_match("#^(.+?)\.#", $hostname,$re)){$hostname=$re[1];}
	localsyslog("Commit: IP:$ip,$mac,$hostname");
	CreateComputerLogs($ip,$mac,$hostname);
}
function parseLeases(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".parseLeases.pid";
	$pidTime="/etc/artica-postfix/pids/exec.dhcpd-leases.php.parseLeases.time";
	
	
	if($unix->process_exists(@file_get_contents($pidfile,basename(__FILE__)))){
		if($GLOBALS["VERBOSE"]){echo " --> Already executed.. ". @file_get_contents($pidfile). " aborting the process\n";}
		die();
	}
	@file_put_contents($pidfile, getmypid());
	
	if(!$GLOBALS["FORCE"]){
		$TimeFile=$unix->file_time_min($pidTime);
		if($TimeFile<5){
			if($GLOBALS["VERBOSE"]){echo " {$TimeFile}Mn, require 5mn\n";}
			die();
		}
	}
	
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	
	
	$pid=dhcpd_pid();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["VERBOSE"]){echo " --> DHCPD service not running...\n";}
		return;
	}
	
	$ptime=$unix->PROCCESS_TIME_MIN($pid);
	if($ptime<2){
		if($GLOBALS["VERBOSE"]){echo " --> DHCPD service running before 2mn...\n";}
		return;
	}
	
	
	$BaseWorkDir="/var/log/artica-postfix/DHCP-LEASES";
	@mkdir($BaseWorkDir,0755,true);
	
	
	if (!$handle = opendir($BaseWorkDir)) {
		echo "Failed open $BaseWorkDir\n";
		return;
	}
	
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		$TimeFile=$unix->file_time_min($targetFile);
		if($TimeFile>240){@unlink($TimeFile);continue;}
		$array=unserialize(@file_get_contents($targetFile));
		@unlink($targetFile);
		if(!is_array($array)){continue;}
		$ip=$array["IP"];
		$mac=$array["MAC"];
		$hostname=$array["hostname"];
		if($ip==null){continue;}
		CreateComputerLogs($ip,$mac,$hostname);
		update_computer($ip,$mac,$hostname);
	}
	
	
}


function CreateComputerLogs($ip,$mac,$hostname){
	
	$q=new postgres_sql();
	if(!isset($GLOBALS["dhcpd_hosts_checked"])){$q->CREATE_DHCPD_TABLES();}
	
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT mac FROM dhcpd_hosts WHERE mac='$mac'"));
	
	
	$time=date("Y-m-d H:i:s");
	if($ligne["MAC"]==null){
		$q->QUERY_SQL("INSERT INTO dhcpd_hosts (MAC,created`,updated,ipaddr,hostname) VALUES('$mac','$time','$time','$ip','$hostname')");
		
	}else{
		$q->QUERY_SQL("UPDATE dhcpd_hosts SET ipaddr='$ip',hostname='$hostname',updated='$time' WHERE mac='$mac'");
		
	}
	
	
	
}

function empty_leases_progress(){
	
	
	build_progress_empty("{stopping_service}",20);
	shell_exec("/etc/init.d/isc-dhcp-server stop");
	build_progress_empty("{removing_caches}",50);
	@unlink("/var/lib/dhcp3/dhcpd.leases");
	@unlink("/var/lib/dhcp3/dhcpd.leases~");
	$q=new mysql();
	$q->QUERY_SQL("TRUNCATE TABLE `dhcpd_leases`","artica_backup");
	build_progress_empty("{starting_service}",90);
	shell_exec("/etc/init.d/isc-dhcp-server start");
	build_progress_empty("{done}",100);
	
}
function build_progress_empty($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/dhcdp.leases.empty.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
	sleep(1);

}


function dhcpd_logs($text){
	
	if(!function_exists("syslog")){return;}
	$LOG_SEV=LOG_INFO;
	openlog("dhcpd-leases", LOG_PID , LOG_SYSLOG);
	syslog($LOG_SEV, $text);
	closelog();
}
function dhcpd_pid(){
	$unix=new unix();
	$filename="/var/run/dhcpd.pid";
	$pid=$unix->get_pid_from_file($filename);
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF($unix->DHCPD_BIN_PATH());
}


?>