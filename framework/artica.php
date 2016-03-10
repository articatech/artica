<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
$GLOBALS["HOSTS_PATH"]="/usr/share/artica-postfix/ressources/conf/meta/hosts";

if(isset($_GET["stats-appliance-berekley"])){squid_stats_appliance_berekley();exit;}
if(isset($_GET["exec-squid-stats-appliance-disconnect"])){squid_stats_appliance_disconnect();exit;}
if(isset($_GET["meta-repair-tables"])){meta_repair_tables();exit;}
if(isset($_GET["webfiltering-events"])){webfiltering_events();exit;}
if(isset($_GET["snapshot-sql"])){snapshot_sql();exit;}
if(isset($_GET["snapshot"])){snapshot();exit;}
if(isset($_GET["meta-tests-smtp"])){artica_meta_server_test_smtp();exit;}
if(isset($_GET["uncompress"])){uncompress();exit;}
if(isset($_GET["save-client-config"])){save_client_config();exit;}
if(isset($_GET["set-backup-server"])){save_client_server();exit;}
if(isset($_GET["meta-client-register"])){artica_meta_client_register();exit;}
if(isset($_GET["meta-admin-orders"])){artica_meta_admin_orders();exit;}
if(isset($_GET["meta-proxy-config"])){artica_meta_proxy_config();exit;}
if(isset($_GET["meta-client-wakeup"])){artica_meta_client_wakeup();exit;}
if(isset($_GET["meta-status-uuid"])){artica_meta_client_statustgz();exit;}
if(isset($_GET["meta-syslog-uuid"])){artica_meta_client_syslog();exit;}
if(isset($_GET["meta-psaux-uuid"])){artica_meta_client_psaux();exit;}
if(isset($_GET["meta-philesight-uuid"])){artica_meta_client_philesight();exit;}
if(isset($_GET["meta-metaevents-uuid"])){artica_meta_client_metaevents();exit;}
if(isset($_GET["meta-sysalerts-uuid"])){artica_meta_client_sysalerts();exit;}
if(isset($_GET["meta-smtp-uuid"])){artica_meta_client_smtp();exit;}
if(isset($_GET["meta-snapshot-uuid"])){artica_meta_client_snapshot();exit;}
if(isset($_GET["meta-metaclientquotasize-uuid"])){artica_meta_client_squidquotasize();exit;}
if(isset($_GET["meta-metaclientsquidperf-uuid"])){artica_meta_client_squidperfs();exit;}
if(isset($_GET["meta-metaclienteventsmaster-uuid"])){artica_meta_client_eventsmaster();exit;}
if(isset($_GET["meta-metaclientSquidRealtimeev-uuid"])){artica_meta_client_squid_relatime_ev();exit;}
if(isset($_GET["meta-metaclient-dumpsql-uuid"])){artica_meta_client_mysql_dump();exit;}
if(isset($_GET["meta-metaclient-scan-upload-dirs"])){artica_meta_client_uploaddirs();exit;}

if(isset($_GET["meta-server-dumpgz"])){artica_meta_server_dump_gz();exit;}

if(isset($_GET["scan-categories"])){artica_meta_scan_categories();exit;}

if(isset($_GET["build-meta-schedules"])){artica_meta_client_build_schedules();exit;}
if(isset($_GET["meta-metaclient-clonesource"])){artica_meta_client_build_clone_source();exit;}
if(isset($_GET["snapshot-import"])){snapshot_import();exit;}
if(isset($_GET["lighttpd-reload"])){lighttpd_reload();exit;}



if(isset($_GET["meta-articadaemons-uuid"])){artica_meta_client_articadaemons();exit;}
if(isset($_GET["metaclientevents-uuid"])){artica_meta_client_metaevents2();exit;}



if(isset($_GET["meta-ping-group"])){artica_meta_server_ping_group();exit;}
if(isset($_GET["meta-ping-host"])){artica_meta_server_ping_host();exit;}


if(isset($_GET["sync-policies-group"])){artica_meta_server_sync_policies_group();exit;}
if(isset($_GET["apply-policy"])){artica_meta_server_sync_policy_single();exit;}




if(isset($_GET["meta-scan-update"])){artica_server_update_repo();exit;}
if(isset($_GET["meta-scan-repos"])){artica_server_scan_repo();exit;}
if(isset($_GET["meta-delete-repos"])){artica_server_del_repo();exit;}
if(isset($_GET["meta-add-node"])){artica_meta_add_node();exit;}
if(isset($_GET["delete-artica-meta-package"])){artica_meta_delete_artica_package();exit;}


while (list ($num, $line) = each ($_GET)){$f[]="$num=$line";}
meta_events("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die();

function uncompress(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$tar=$unix->find_program("tar");
	$filename=$_GET["uncompress"];
	
	$FilePath="/usr/share/artica-postfix/ressources/conf/upload/$filename";
	
	if(!is_file($FilePath)){
		echo "<articadatascgi>".base64_encode(serialize(array("R"=>false,"T"=>"{failed}: $FilePath no such file")))."</articadatascgi>";
	}
	writelogs_framework("$tar -xf $FilePath -C /usr/share/",__FUNCTION__,__FILE__,__LINE__);
	shell_exec("$tar -xf $FilePath -C /usr/share/");
	$VERSION=@file_get_contents("/usr/share/artica-postfix/VERSION");
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup /usr/share/artica-postfix/exec.initslapd.php --force >/dev/null 2>&1 &");
	shell_exec("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");
	shell_exec("$nohup /etc/init.d/monit restart >/dev/null 2>&1 &");
	
	
	
	echo "<articadatascgi>".base64_encode(serialize(array("R"=>true,"T"=>"{success}: v.$VERSION")))."</articadatascgi>";
	
}

function artica_meta_server_sync_policies_group(){
	
	
	$gpid=$_GET["sync-policies-group"];
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-server-policies.php --group $gpid >/dev/null 2>&1 &");
	meta_events("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}
function meta_repair_tables(){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/artica-meta.RepairTables.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/artica-meta.RepairTables.log";
	
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"],0777);
	@chmod($GLOBALS["LOG_FILE"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-server.php --repair-tables >{$GLOBALS["LOG_FILE"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function snapshot(){
		$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/backup.artica.progress";
		$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/backup.artica.progress.txt";
		@unlink($GLOBALS["PROGRESS_FILE"]);
		@unlink($GLOBALS["LOG_FILE"]);
		@touch($GLOBALS["PROGRESS_FILE"]);
		@touch($GLOBALS["LOG_FILE"]);
		@chmod($GLOBALS["PROGRESS_FILE"],0777);
		@chmod($GLOBALS["LOG_FILE"],0777);
		$unix=new unix();
		$php5=$unix->LOCATE_PHP5_BIN();
		$nohup=$unix->find_program("nohup");
		$cmd="$nohup $php5 /usr/share/artica-postfix/exec.backup.artica.php --snapshot >{$GLOBALS["LOG_FILE"]} 2>&1 &";
		writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);
}

function artica_meta_server_dump_gz(){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/artica-meta.proxy.acls.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/artica-meta.proxy.acls.progress.txt";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"],0777);
	@chmod($GLOBALS["LOG_FILE"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-server.php --export-tables >{$GLOBALS["LOG_FILE"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}


function snapshot_import(){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/snapshot.upload.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/snapshot.upload.progress.txt";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"],0777);
	@chmod($GLOBALS["LOG_FILE"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.backup.artica.php --snapshot-import >{$GLOBALS["LOG_FILE"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}


function snapshot_sql(){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/backup.artica.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/backup.artica.progress.txt";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"],0777);
	@chmod($GLOBALS["LOG_FILE"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.backup.artica.php --snapshot-id {$_GET["ID"]} >{$GLOBALS["LOG_FILE"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}



function artica_meta_server_sync_policy_single(){
	$policy_id=$_GET["policy-id"];
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-server-policies.php --policy $policy_id >/dev/null 2>&1 &");
	meta_events("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}

function save_client_config(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.amanda.php --comps >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function save_client_server(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.amanda.php --backup-server >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function squid_stats_appliance_disconnect(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");

	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.stats-appliance.disconnect.php.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/squid.stats-appliance.disconnect.php.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);

	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.stats-appliance.disconnect.php >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}

function squid_stats_appliance_berekley(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.interface-size.php --stats-app >/dev/null 2>&1 &");
	shell_exec($cmd);
}

function artica_meta_client_register(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/artica-meta.NewServ.php.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/artica-meta.NewServ.php.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	
	$cmd=trim("$nohup /etc/init.d/squid reload --force --script=".basename(__FILE__)." >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-client.php --ping --output --progress --force >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}
function artica_meta_admin_orders(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-server.php --build-orders >/dev/null 2>&1 &");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function artica_meta_proxy_config(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-server.php --build-proxy >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function artica_meta_client_wakeup(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-client.php --ping --force >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function artica_meta_client_statustgz(){
	$uuid=$_GET["uuid"];
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-server.php --extract $uuid >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}
function artica_meta_client_syslog(){
	$uuid=$_GET["uuid"];
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-server.php --syslog $uuid >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function artica_meta_client_psaux(){
	$uuid=$_GET["uuid"];
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-server.php --psaux $uuid >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}
function artica_meta_client_philesight(){
	$uuid=$_GET["uuid"];
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-server.php --philesight $uuid >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	meta_events($cmd);
	shell_exec($cmd);	
}

function  artica_meta_client_metaevents(){
	$uuid=$_GET["uuid"];
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-server.php --metaevents $uuid >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	meta_events($cmd);
	shell_exec($cmd);	
	
}

function artica_meta_client_sysalerts(){
	$uuid=$_GET["uuid"];
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-server.php --syslaerts $uuid >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	meta_events($cmd);
	shell_exec($cmd);	
}

function artica_meta_client_smtp(){
	$uuid=$_GET["uuid"];
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-server.php --smtp $uuid >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	meta_events($cmd);
	shell_exec($cmd);
}

function artica_meta_client_snapshot(){
	$uuid=$_GET["uuid"];
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-server.php --snapshot $uuid >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	meta_events($cmd);
	shell_exec($cmd);	
}

function artica_meta_client_squidquotasize(){
	$uuid=$_GET["uuid"];
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-server.php --squid-quota-size $uuid >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	meta_events($cmd);
	shell_exec($cmd);	
}

function artica_meta_client_squidperfs(){
	$uuid=$_GET["uuid"];
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-server.php --squid-perfs $uuid >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	meta_events($cmd);
	shell_exec($cmd);
}


function artica_meta_client_eventsmaster(){
	$uuid=$_GET["uuid"];
	$BaseWorkDir="{$GLOBALS["HOSTS_PATH"]}/uploaded/$uuid/EVENT_NOTIFY_MASTER";
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	
	if(!is_dir($BaseWorkDir)){return;}
	if (!$handle = opendir($BaseWorkDir)) {return;}
	
	$TARGET_DIR="/home/artica-meta/$uuid/EVENT_NOTIFY_MASTER";
	@mkdir($TARGET_DIR,0755,true);
		
		
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		if(is_dir($targetFile)){continue;}
		@copy($targetFile, "$TARGET_DIR/$filename");
		@unlink($targetFile);
	}
		
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-server.php --uuid-events $uuid >/dev/null 2>&1 &");
	meta_events($cmd);
	shell_exec($cmd);
}

function artica_meta_client_build_schedules(){
	$uuid=$_GET["uuid"];
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-server.php --uuid-scheduler $uuid --notify >/dev/null 2>&1 &");
	meta_events($cmd);
	shell_exec($cmd);
}
function artica_meta_client_build_clone_source(){
	$uuid=$_GET["uuid"];
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-server.php --uuid-clone-source $uuid --notify >/dev/null 2>&1 &");
	meta_events($cmd);
	shell_exec($cmd);
}

function artica_meta_client_uploaddirs(){
	$uuid=$_GET["uuid"];
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-server.php --purge-upload $uuid >/dev/null 2>&1 &");
	meta_events("ARTICA_DAEMONS $cmd");
	shell_exec($cmd);
}
function artica_meta_scan_categories(){
	$uuid=$_GET["uuid"];
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-server.php --scan-categories >/dev/null 2>&1 &");
	meta_events("SCAN_CATEGORIES $cmd");
	shell_exec($cmd);	
}

//
function artica_meta_client_mysql_dump(){
	$uuid=$_GET["uuid"];
	$BaseWorkDir="{$GLOBALS["HOSTS_PATH"]}/uploaded/$uuid";
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");


	if(!is_dir($BaseWorkDir)){return;}
	if (!$handle = opendir($BaseWorkDir)) {return;}

	$TARGET_DIR="/home/artica/squid/META_DUMP_QUEUE";
	@mkdir($TARGET_DIR,0755,true);


	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		if(is_dir($targetFile)){continue;}
		@copy($targetFile, "$TARGET_DIR/$uuid-$filename");
		@unlink($targetFile);
	}

	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-squid-parser.php >/dev/null 2>&1 &");
	meta_events($cmd);
	shell_exec($cmd);
}

function artica_meta_client_squid_relatime_ev(){
	$uuid=$_GET["uuid"];
	$BaseWorkDir="{$GLOBALS["HOSTS_PATH"]}/uploaded/$uuid/SQUID_RELATIME_EVENTS";
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	
	if(!is_dir($BaseWorkDir)){return;}
	if (!$handle = opendir($BaseWorkDir)) {return;}
	
	$TARGET_DIR="/home/artica/squid/META_EVENTS_QUEUE";
	@mkdir($TARGET_DIR,0755,true);
	
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		if(is_dir($targetFile)){continue;}
		@copy($targetFile, "$TARGET_DIR/$filename");
		@unlink($targetFile);
	}
	
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-squid-parser.php >/dev/null 2>&1 &");
	meta_events($cmd);
	shell_exec($cmd);
}

function artica_meta_client_articadaemons(){
	$uuid=$_GET["uuid"];
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-server.php --articadaemons $uuid >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	meta_events($cmd);
	shell_exec($cmd);	
	
}

function artica_meta_client_metaevents2(){
	$uuid=$_GET["uuid"];
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-server.php --metaevents2 $uuid >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	meta_events($cmd);
	shell_exec($cmd);	
	
}

function artica_meta_server_ping_host(){
	$uuid=$_GET["meta-ping-host"];
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	meta_events("Framework, send ping to $uuid");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-server.php --ping-host $uuid >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	meta_events($cmd);
	shell_exec($cmd);	
	
}
function artica_meta_server_ping_group(){
	$gpid=$_GET["meta-ping-group"];
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	meta_events("Framework, send ping to Group $gpid");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-server.php --ping-group $gpid >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	meta_events($cmd);
	shell_exec($cmd);	
	
}

function artica_server_update_repo(){
	
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/artica-meta.update.php.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/artica-meta.update.php.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$filename=$_GET["filename"];
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.nightly.php --meta-release \"/usr/share/artica-postfix/ressources/conf/upload/$filename\" --output >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function artica_server_scan_repo(){
	
	
	
	$filename=$_GET["filename"];
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.artica-meta-server.php --scan-repo \"$filename\" >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}
function artica_server_del_repo(){
	$filename=$_GET["filename"];
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.artica-meta-server.php --delete-repo \"$filename\" >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}
function artica_meta_delete_artica_package(){
	$filename=$_GET["filename"];
	$filetype=$_GET["filetype"];
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.artica-meta-server.php --delete-articapkg \"$filename\" \"$filetype\" >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function artica_meta_add_node(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/artica-meta.NewServ.php.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/artica-meta.NewServ.php.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-server.php --add-node --output >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}
function meta_events($text){
	$unix=new unix();
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();

		if(isset($trace[0])){
			$file=basename($trace[0]["file"]);
			$function=$trace[0]["function"];
			$line=$trace[0]["line"];
		}

		if(isset($trace[1])){
			$file=basename($trace[1]["file"]);
			$function=$trace[1]["function"];
			$line=$trace[1]["line"];
		}



	}
	$unix->events($text,"/var/log/artica-meta.log",false,$function,$line,$file);

}

function artica_meta_server_test_smtp(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.artica-meta-server.php --tests-notifs --verbose 2>&1");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	krsort($results);
	echo "<articadatascgi>".@implode("\n", $results)."</articadatascgi>";
}

function webfiltering_events(){
	
	@copy("/var/log/artica-ufdb.log","/usr/share/artica-postfix/ressources/logs/web/artica-ufdb.log");
	@chmod(0755,"/usr/share/artica-postfix/ressources/logs/web/artica-ufdb.log");
}

function lighttpd_reload(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.lighttpd.php --reload >/dev/null 2>&1 &");
}
