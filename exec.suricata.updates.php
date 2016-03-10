<?php
$GLOBALS["SERVICE_NAME"]="IDS service";
$GLOBALS["DEBUG"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){
	ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	$GLOBALS["DEBUG"]=true;
	$GLOBALS["VERBOSE"]=true;
}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.postgres.inc");

if($argv[1]=="--block"){BlockIPs();exit;}

echo "Starting....[".__LINE__."]\n";

xupdate();
function xupdate($aspid=false){
	
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidtime="/etc/artica-postfix/pids/exec.suricata.updates.php.update.time";
	
	if(!$GLOBALS["FORCE"]){
		if(system_is_overloaded()){die();}
		
		
		if(!$aspid){
			$pid=@file_get_contents($pidfile);
			if($pid<100){$pid=null;}
			if($unix->process_exists($pid,basename(__FILE__))){echo "PID: $pid Already exists....\n";die();}
			@file_put_contents($pidfile, getmypid());
		
			$pidExec=$unix->file_time_min($pidtime);
			if($pidExec<1439){return;}
		
			@unlink($pidtime);
			@file_put_contents($pidtime, time());
		
		}
	}
	
	echo "Starting....\n";
	$sock=new sockets();
	$CurrentEmergingRulesMD5=$sock->GET_INFO("CurrentEmergingRulesMD5");
	$tmpdir=$unix->TEMP_DIR();
	echo "CurrentEmergingRulesMD5=$CurrentEmergingRulesMD5 TMPDIR:$tmpdir\n";
	$curl=new ccurl("https://rules.emergingthreatspro.com/open/suricata/emerging.rules.tar.gz.md5");
	
	$targetpath="$tmpdir/emerging.rules.tar.gz.md5";
	if(!$curl->GetFile($targetpath)){
		echo "$targetpath failed\n";
		artica_update_event(0, "Unable to download emerging.rules.tar.gz.md5", $curl->errors,__FILE__,__LINE__);
		return;
	}
	echo "Open $targetpath\n";
	$f=explode("\n",@file_get_contents($targetpath));
	echo "$targetpath ".count($f)." lines\n";
	if(count($f)>2){
		artica_update_event(0, "Truncated emerging.rules.tar.gz.md5", $curl->errors,__FILE__,__LINE__);
		return;
	}
	@unlink($targetpath);
	$NewEmergingRulesMD5=trim($f[0]);
	
	if($NewEmergingRulesMD5==$CurrentEmergingRulesMD5){
		echo "No new updates...\n";
		die();
	}
	
	$curl=new ccurl("https://rules.emergingthreatspro.com/open/suricata/version.txt");
	$targetpath="$tmpdir/version.txt";
	if(!$curl->GetFile($targetpath)){
		echo "$targetpath failed\n";
		artica_update_event(0, "Unable to version.txt", $curl->errors,__FILE__,__LINE__);
		return;
	}
	
	$NextVersion=@file_get_contents($targetpath);
	@unlink($targetpath);
	
	$curl=new ccurl("https://rules.emergingthreatspro.com/open/suricata/emerging.rules.tar.gz");
	$targetpath="$tmpdir/emerging.rules.tar.gz";
	if(!$curl->GetFile($targetpath)){
		echo "$targetpath failed\n";
		artica_update_event(0, "Unable to download emerging.rules.tar.gz", $curl->errors,__FILE__,__LINE__);
		return;
	}
	$FileMD5=md5_file($targetpath);
	if($FileMD5<>$NewEmergingRulesMD5){
		artica_update_event(0, "Corrupted emerging.rules.tar.gz file", "$FileMD5<>$NewEmergingRulesMD5",__FILE__,__LINE__);
		return;
	}
	
	
	echo "Extracting rules\n";
	
	$tar=$unix->find_program("tar");
	shell_exec("$tar xf $targetpath -C /etc/suricata/");
	@unlink($targetpath);
	$sock->SET_INFO("CurrentEmergingRulesMD5", $NewEmergingRulesMD5);
	$sock->SET_INFO("CurrentEmergingRulesVersion", $NextVersion);
	
	$curl=new ccurl("https://rules.emergingthreatspro.com/open/suricata/classification.config");
	$targetpath="$tmpdir/classification.config";
	if(!$curl->GetFile($targetpath)){
		echo "$targetpath failed\n";
		artica_update_event(0, "Unable to download classification.config", $curl->errors,__FILE__,__LINE__);
		return;
	}
	@unlink("/etc/suricata/classification.config");
	@copy($targetpath, "/etc/suricata/classification.config");
	@unlink($targetpath);
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$php /usr/share/artica-postfix/exec.suricata.php --classifications");
	
	$q=new postgres_sql();
	$results=$q->QUERY_SQL("select signature FROM suricata_sig where enabled=0");
	$nice=$unix->EXEC_NICE();
	$SH[]="#!/bin/sh";
	
	while($ligne=@pg_fetch_assoc($results)){
		$sig=$ligne["signature"];
		echo "Disable signature $sig\n";
		$SH[]="$nice /usr/share/artica-postfix/bin/sidrule -d $sig || true";
	
	}
	
	$targetpath="$tmpdir/sidrule-remove.sh";
	$SH[]="rm -f $tmpdir/sidrule-remove.sh";
	$SH[]="/etc/init.d/suricata restart\n\n";
	
	
	@file_put_contents("$tmpdir/sidrule-remove.sh", @implode("\n", $SH));
	@chmod("$tmpdir/sidrule-remove.sh", 0755);
	shell_exec("$nohup $tmpdir/sidrule-remove.sh >/dev/null 2>&1 &");
	
	artica_update_event(2, "Success updating emergingthreatspro IDS patterns v$NextVersion", null,__FILE__,__LINE__);
	
	BlockIPs();
	
	
}

function BlockIPs(){
	$ipClass=new IP();
	$unix=new unix();
	$tmpdir=$unix->TEMP_DIR();
	$curl=new ccurl("https://rules.emergingthreatspro.com/fwrules/emerging-Block-IPs.txt");
	$targetpath="$tmpdir/emerging-Block-IPs.txt";
	
	if(!$curl->GetFile($targetpath)){
		echo "$targetpath failed\n";
		artica_update_event(0, "Unable to download emerging-Block-IPs.txt", $curl->errors,__FILE__,__LINE__);
		return;
	}
	
	$f=explode("\n",@file_get_contents($targetpath));
	$proxyname=$unix->hostname_g();
	$q=new postgres_sql();
	$q->suricata_tables();
	
	
	$tr=array();
	while (list ($num, $ligne) = each ($f) ){
		
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		if(strpos(" $ligne", "#")>0){continue;}
		if(!$ipClass->isIPAddressOrRange($ligne)){continue;}
		$zdate=date("Y-m-d H:i:s");
		$proto="TCP";
		$dest_port=0;
		$src_ip=$ligne;
		$uduniq=md5("0,$src_ip,$dest_port,$proto");
		if($GLOBALS["VERBOSE"]){echo "0,$src_ip,$dest_port,$proto\n";}
		
		$tr[]="('$zdate','$uduniq','0','$src_ip','$dest_port','$proto','$proxyname',1)";
	}
	
	
	if(count($tr)>0){
		$q->QUERY_SQL("DELETE FROM suricata_firewall WHERE xauto=1");
	}
	
	$content=@implode(",", $tr);
	$prefix="INSERT INTO suricata_firewall (zdate,uduniq,signature,src_ip,dst_port,proto,proxyname,xauto) VALUES ";
	$q->QUERY_SQL("$prefix $content ON CONFLICT DO NOTHING");
	if(!$q->ok){echo $q->mysql_error."\n";return;}
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php /usr/share/artica-postfix/exec.suricata-fw.php --run");
	
}


?>