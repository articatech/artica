<?php
ini_set('error_reporting', E_ALL);
if(isset($_GET["verbose"])){
	ini_set('display_errors', 1);	
	ini_set('html_errors',0);
	ini_set('display_errors', 1);
	
	$GLOBALS["VERBOSE"]=true;
}
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");

if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }


if(isset($_GET["reload-progress"])){reload_progress();exit;}
if(isset($_GET["dump-hour-progress"])){DUMP_HOUR_PROGRESS();exit;}
if(isset($_GET["paranoid-progress"])){paranoid_progress();exit;}
if(isset($_GET["paranoid-emergency-progress"])){paranoid_emergency_progress();exit;}
if(isset($_GET["windows-update-delete"])){windows_update_delete();exit;}
if(isset($_GET["not-categorize-progress"])){not_categorized_progress();exit;}

if(isset($_GET["quotarules-status-progress"])){quotarules_status_progress();exit;}
if(isset($_GET["clean-logs-emergency"])){clean_logs_emergency();exit;}
if(isset($_GET["rebuild-and-restart"])){rebuild_and_restart();exit;}
if(isset($_GET["CategoriesRate"])){ufdbcat_check_categories();exit;}
if(isset($_GET["emergency-activedirectory-progress"])){activedirectory_emergency_progress();exit;}
if(isset($_GET["SquidReloadInpublicAlias"])){SquidReloadInpublicAlias();exit;}
if(isset($_GET["import-old-logs-files"])){import_old_logs_files();exit;}
if(isset($_GET["build-templates-background"])){squid_templates_background();exit;}
if(isset($_GET["squidclient-mgr-storedir"])){squidclient_mgr_storedir();exit;}
if(isset($_GET["watchdog-bandwidth"])){watchdog_bandwidth();exit;}
if(isset($_GET["remove-influxdb"])){remove_influx_db();exit;}
if(isset($_GET["disable-influxdb"])){disable_influx_db();exit;}
if(isset($_GET["enable-influxdb"])){enable_influx_db();exit;}
if(isset($_GET["test-ssl-port"])){test_ssl_port();exit;}
if(isset($_GET["cached-kerberos-tickets"])){cached_kerberos_tickets();exit;}

if(isset($_GET["squid-conf-ports"])){squid_ports_conf();exit;}
if(isset($_GET["squid-conf-ssl"])){squid_ssl_conf();exit;}
if(isset($_GET["squid-conf-externals"])){squid_externals_conf();exit;}
if(isset($_GET["bandwidth-progress"])){bandwidth_progress();exit;}

if(isset($_GET["saveSquidPortContent"])){squid_ports_conf_save();exit;}
if(isset($_GET["saveSquidSSLContent"])){squid_ssl_conf_save();exit;}

if(isset($_GET["saveSquidExternalContent"])){squid_external_conf_save();exit;}
if(isset($_GET["wccp-reconfigure"])){wccp_reconfigure();exit;}

if(isset($_GET["global-access-center"])){global_access_center();exit;}
if(isset($_GET["disable-ufdb-urgency"])){squid_disable_ufdbemergency();exit;}
if(isset($_GET["ufdb-update-settings"])){ufdb_save_update_settings();exit;}
if(isset($_GET["ufdb-ini-status-write"])){ufdb_ini_status();exit;}
if(isset($_GET["ssl-rules"])){squid_ssl_rules();exit;}
if(isset($_GET["create-cache-wizard"])){create_cache_wizard();exit;}
if(isset($_GET["itchart-build"])){it_chart_build();exit;}
if(isset($_GET["allow-80443-port"])){allow_8083_port();exit;}
if(isset($_GET["ecap-progress"])){ecap_progress();exit;}

while (list ($num, $line) = each ($_GET)){$f[]="$num=$line";}

writelogs_framework("unable to understand query !!!!!!!!!!!...`" .@implode(",",$f)."`","main()",__FILE__,__LINE__);
die();

//-----------------------------------------------------------------------------------------------------------------------------------
function squidclient_mgr_storedir(){
	$unix=new unix();
	$data=$unix->squidclient("storedir",true);
	@unlink("/usr/share/artica-postfix/ressources/logs/web/storedir.cache");
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/storedir.cache", $data);
	
	
}
//-----------------------------------------------------------------------------------------------------------------------------------
function watchdog_bandwidth(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$nohup $php /usr/share/artica-postfix/exec.squid.watchdog.php --bandwidth-cron >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}
function squid_templates_background(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$nohup $php php5 /usr/share/artica-postfix/exec.squid.templates.php --force --progress >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}




function ufdbcat_check_categories(){
	$CategoriesRate=$_GET["CategoriesRate"];
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/ufdbcat.check.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/ufdbcat.check.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.ufdbcat.check.php \"$CategoriesRate\" >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function reload_progress(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.reload.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/squid.reload.progress.log";
	
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.php --kreconfigure --force --verbose >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
	
}

function DUMP_HOUR_PROGRESS(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/DUMP_HOUR_PROGRESS";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/DUMP_HOUR_PROGRESS.txt";
	
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.interface-size.php --dump-hour --force >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}


function not_categorized_progress(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.statistics.not-categorized.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/squid.statistics.not-categorized.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.stats.categorize.php --progress >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function rebuild_and_restart(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/squid.build.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/squid_reconfigure.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["CACHEFILE"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.rebuild-restart.php >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}



function clean_logs_emergency(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/squid.cleanlogs.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/squid.cleanlogs.progress.txt";
	@mkdir("/usr/share/artica-postfix/ressources/logs/web",0755,true);
	@unlink($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["CACHEFILE"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.cleanlogs-emergency.php >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
	
}


function activedirectory_emergency_progress(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.urgency.disable.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/squid.ad.emergency.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["CACHEFILE"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));

	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.urgency.remove.php --ad-on >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}


function ecap_progress(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.ecap.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/squid.ecap.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.ecap.php --progress >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}


//-----------------------------------------------------------------------------------------------------------------------------------
function remove_influx_db(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/influxdb.remove.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/influxdb.remove.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.influxdb.php --remove-db >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}
//-----------------------------------------------------------------------------------------------------------------------------------
function test_ssl_port(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/squid.testssl.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/squid.testssl.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.testssslports.php {$_GET["ID"]} >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function wccp_reconfigure(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/squid.wccp.interface.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/squid.wccp.interface.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.wccp.php --reconfigure --progress >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	}
	
	
function paranoid_progress(){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/squid.paranoid.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/squid.paranoid.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.paranoid.php --frame >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function paranoid_emergency_progress(){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/squid.paranoid.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/squid.paranoid.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.paranoid.php --emergency >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}
	
function bandwidth_progress(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/squid.bandwww.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/squid.bandwww.progress.txt";
	
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.bandwidth.php >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function quotarules_status_progress(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/squid.quotasband.status.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/squid.quotasband.status.progress.txt";
	
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.quotaband.php --force >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}


function SquidReloadInpublicAlias(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/SquidReloadInpublicAlias.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/SquidReloadInpublicAlias.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.php --SquidReloadInpublicAlias >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function disable_influx_db(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/influxdb.remove.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/influxdb.remove.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.influxdb.php --disable-db >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function enable_influx_db(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/influxdb.remove.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/influxdb.remove.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.influxdb.php --enable-db >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}



function allow_8083_port(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/squid.ports.80.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/squid.ports.80.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid80443.php >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}


function import_old_logs_files(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/squid.statistics.import.local.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/squid.statistics.import.local.progress.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.influx.import.php --scandir >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}



function it_chart_build(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/itchart.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/itchart.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.itchart.php --build-rules >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}

function global_access_center(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/squid.access.center.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/squid.access.center.progress.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.global.access.php >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function squid_ssl_rules(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/squid.ssl.rules.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/squid.ssl.rules.progress.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.ssl.rules.php >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
		
	
}

function squid_disable_ufdbemergency(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.urgency.disable.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/squid.urgency.disable.progress.txt";

	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");

	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.urgency.remove.php --ufdb-off >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function squid_ports_conf(){
	@unlink("/usr/share/artica-postfix/ressources/logs/web/squid-ports.conf");
	@copy("/etc/squid3/listen_ports.conf","/usr/share/artica-postfix/ressources/logs/web/squid-ports.conf");
	@chmod("/usr/share/artica-postfix/ressources/logs/web/squid-ports.conf",0755);
}
function squid_ssl_conf(){
	@unlink("/usr/share/artica-postfix/ressources/logs/web/squid-ssl.conf");
	@copy("/etc/squid3/ssl.conf","/usr/share/artica-postfix/ressources/logs/web/squid-ssl.conf");
	@chmod("/usr/share/artica-postfix/ressources/logs/web/squid-ssl.conf",0755);	
	
}
function squid_externals_conf(){
	@unlink("/usr/share/artica-postfix/ressources/logs/web/squid-extern.conf");
	@copy("/etc/squid3/external_acls.conf","/usr/share/artica-postfix/ressources/logs/web/squid-extern.conf");
	@chmod("/usr/share/artica-postfix/ressources/logs/web/squid-extern.conf",0755);
}

function squid_external_conf_save(){
	$unix=new unix();
	$datas=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/externals.conf");
	writelogs_framework(strlen($datas)/1024 ." Ko",__FUNCTION__,__FILE__,__LINE__);
	if($datas==null){
	
		echo "<articadatascgi>". base64_encode("Fatal NO CONTENT!!")."</articadatascgi>";
		return;
	}
	@unlink("/usr/share/artica-postfix/ressources/logs/web/externals.conf");
	@unlink("/etc/squid3/external_acls.bak");
	@copy("/etc/squid3/external_acls.conf","/etc/squid3/external_acls.bak");
	@file_put_contents("/etc/squid3/external_acls.conf", $datas);
	
	if(!test_squid_conf()){
		@unlink("/etc/squid3/external_acls.conf");
		@copy("/etc/squid3/external_acls.bak","/etc/squid3/external_acls.conf");
		return;
	}
	
	@unlink("/etc/squid3/external_acls.bak");
	$cmd="/etc/init.d/squid reload --script=".basename(__FILE__);
	shell_exec("$cmd >/dev/null 2>&1");
	
}

function test_squid_conf(){
	$unix=new unix();
	$squidbin=$unix->find_program("squid");
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
	$SQUID_CONFIG_PATH=$unix->SQUID_CONFIG_PATH();
	writelogs_framework("$squidbin -f /etc/squid3/squid.conf -k parse 2>&1",__FUNCTION__,__FILE__,__LINE__);
	exec("$squidbin -f /etc/squid3/squid.conf -k parse 2>&1",$results);
	while (list ($index, $ligne) = each ($results) ){
		if(strpos($ligne,"| WARNING:")>0){continue;}
		if(preg_match("#ERROR: Failed#", $ligne)){
			writelogs_framework("$ligne ->FALSE",__FUNCTION__,__FILE__,__LINE__);
			echo "<articadatascgi>". base64_encode("Squid `$ligne`, aborting configuration")."</articadatascgi>";
			return false;
		}
		
		if(preg_match("#Segmentation fault#", $ligne)){
			writelogs_framework("$ligne ->FALSE",__FUNCTION__,__FILE__,__LINE__);
			echo "<articadatascgi>". base64_encode("Squid `$ligne`, aborting configuration")."</articadatascgi>";
			return false;
		}
			
		if(preg_match("#(unrecognized|FATAL|Bungled)#", $ligne)){
			$f[]="Squid `$ligne`, aborting configuration, keep the old one...\n";
			writelogs_framework("$ligne ->FALSE",__FUNCTION__,__FILE__,__LINE__);
			if(preg_match("#line ([0-9]+):#", $ligne,$ri)){
				$Buggedline=$ri[1];
				$tt=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
				for($i=$Buggedline-2;$i<$Buggedline+2;$i++){
					$lineNumber=$i+1;
					if(trim($tt[$i])==null){continue;}
					$f[]="[line:$lineNumber]: {$tt[$i]}";
				}
			}
			
			echo "<articadatascgi>". base64_encode(@implode("\n", $f))."</articadatascgi>";
			return false;
		}
	}

	return true;
		
}

function squid_ssl_conf_save(){
	$unix=new unix();
	$datas=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/ssl.conf");
	writelogs_framework(strlen($datas)/1024 ." Ko",__FUNCTION__,__FILE__,__LINE__);
	if($datas==null){
	
		echo "<articadatascgi>". base64_encode("Fatal NO CONTENT!!")."</articadatascgi>";
		return;
	}
	@unlink("/usr/share/artica-postfix/ressources/logs/web/ssl.conf");
	
	@unlink("/etc/squid3/ssl.conf.bak");
	@copy("/etc/squid3/listen_ports.conf","/etc/squid3/ssl.conf.bak");
	
	
	@file_put_contents("/etc/squid3/ssl.conf", $datas);
	@chown("/etc/squid3/ssl.conf", "squid");
	$squidbin=$unix->find_program("squid");
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
	$SQUID_CONFIG_PATH=$unix->SQUID_CONFIG_PATH();
	writelogs_framework("$squidbin -f /etc/squid3/squid.conf -k parse 2>&1",__FUNCTION__,__FILE__,__LINE__);
	exec("$squidbin -f /etc/squid3/squid.conf -k parse 2>&1",$results);
	while (list ($index, $ligne) = each ($results) ){
		if(strpos($ligne,"| WARNING:")>0){continue;}
		if(preg_match("#ERROR: Failed#", $ligne)){
			writelogs_framework("$ligne ->FALSE",__FUNCTION__,__FILE__,__LINE__);
			@unlink("/etc/squid3/ssl.conf");
			@copy("/etc/squid3/ssl.conf.bak","/etc/squid3/ssl.conf");
			echo "<articadatascgi>". base64_encode("Squid `$ligne`, aborting configuration")."</articadatascgi>";
			return ;
		}
	
		if(preg_match("#Segmentation fault#", $ligne)){
			writelogs_framework("$ligne ->FALSE",__FUNCTION__,__FILE__,__LINE__);
			@unlink("/etc/squid3/ssl.conf");
			@copy("/etc/squid3/ssl.conf.bak","/etc/squid3/ssl.conf");
			echo "<articadatascgi>". base64_encode("Squid `$ligne`, aborting configuration")."</articadatascgi>";
			return ;
		}
			
			
		if(preg_match("#(unrecognized|FATAL|Bungled)#", $ligne)){
			$f[]="Squid `$ligne`, aborting configuration, keep the old one...\n";
			writelogs_framework("$ligne ->FALSE",__FUNCTION__,__FILE__,__LINE__);
			if(preg_match("#line ([0-9]+):#", $ligne,$ri)){
				$Buggedline=$ri[1];
				$tt=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
				for($i=$Buggedline-2;$i<$Buggedline+2;$i++){
					$lineNumber=$i+1;
					if(trim($tt[$i])==null){continue;}
					$f[]="[line:$lineNumber]: {$tt[$i]}";
				}
			}
			@unlink("/etc/squid3/ssl.conf");
			@copy("/etc/squid3/ssl.conf.bak","/etc/squid3/ssl.conf");
			echo "<articadatascgi>". base64_encode(@implode("\n", $f))."</articadatascgi>";
			return;
		}
	
	}
	@unlink("/etc/squid3/listen_ports.conf.bak");
	$cmd="/etc/init.d/squid reload --script=".basename(__FILE__);
	shell_exec("$cmd >/dev/null 2>&1");	
	
	
}

function squid_ports_conf_save(){
	$unix=new unix();
	$datas=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/squid_ports.conf");
	writelogs_framework(strlen($datas)/1024 ." Ko",__FUNCTION__,__FILE__,__LINE__);
	if($datas==null){

		echo "<articadatascgi>". base64_encode("Fatal NO CONTENT!!")."</articadatascgi>";
		return;
	}
	@unlink("/usr/share/artica-postfix/ressources/logs/web/squid_ports.conf");
	
	@unlink("/etc/squid3/listen_ports.conf.bak");
	@copy("/etc/squid3/listen_ports.conf","/etc/squid3/listen_ports.conf.bak");
	
	
	@file_put_contents("/etc/squid3/listen_ports.conf", $datas);
	@chown("/etc/squid3/listen_ports.conf", "squid");
	$squidbin=$unix->find_program("squid");
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
	$SQUID_CONFIG_PATH=$unix->SQUID_CONFIG_PATH();
	writelogs_framework("$squidbin -f /etc/squid3/squid.conf -k parse 2>&1",__FUNCTION__,__FILE__,__LINE__);
	exec("$squidbin -f /etc/squid3/squid.conf -k parse 2>&1",$results);
	while (list ($index, $ligne) = each ($results) ){
		if(strpos($ligne,"| WARNING:")>0){continue;}
		if(preg_match("#ERROR: Failed#", $ligne)){
			writelogs_framework("$ligne ->FALSE",__FUNCTION__,__FILE__,__LINE__);
			@unlink("/etc/squid3/listen_ports.conf");
			@copy("/etc/squid3/listen_ports.conf.bak","/etc/squid3/listen_ports.conf");
			echo "<articadatascgi>". base64_encode("Squid `$ligne`, aborting configuration")."</articadatascgi>";
			return ;
		}

		if(preg_match("#Segmentation fault#", $ligne)){
			writelogs_framework("$ligne ->FALSE",__FUNCTION__,__FILE__,__LINE__);
			@unlink("/etc/squid3/listen_ports.conf");
			@copy("/etc/squid3/listen_ports.conf.bak","/etc/squid3/listen_ports.conf");
			echo "<articadatascgi>". base64_encode("Squid `$ligne`, aborting configuration")."</articadatascgi>";
			return ;
		}
			
			
		if(preg_match("#(unrecognized|FATAL|Bungled)#", $ligne)){
			$f[]="Squid `$ligne`, aborting configuration, keep the old one...\n";
			writelogs_framework("$ligne ->FALSE",__FUNCTION__,__FILE__,__LINE__);
			if(preg_match("#line ([0-9]+):#", $ligne,$ri)){
				$Buggedline=$ri[1];
				$tt=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
				for($i=$Buggedline-2;$i<$Buggedline+2;$i++){
					$lineNumber=$i+1;
					if(trim($tt[$i])==null){continue;}
					$f[]="[line:$lineNumber]: {$tt[$i]}";
				}
			}
			@unlink("/etc/squid3/listen_ports.conf");
			@copy("/etc/squid3/listen_ports.conf.bak","/etc/squid3/listen_ports.conf");
			echo "<articadatascgi>". base64_encode(@implode("\n", $f))."</articadatascgi>";
			return;
		}

	}
	@unlink("/etc/squid3/listen_ports.conf.bak");
	$cmd="/etc/init.d/squid reload --script=".basename(__FILE__);
	shell_exec("$cmd >/dev/null 2>&1");

}


function ufdb_save_update_settings(){
	$unix=new unix();
	$CategoriesDatabasesByCron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/CategoriesDatabasesByCron"));
	if($CategoriesDatabasesByCron==0){
		if(is_file("/etc/cron.d/webfiltering-update")){
			@unlink("/etc/cron.d/webfiltering-update");
			system("/etc/init.d/cron reload");
			
		}
		return;
	}
	
	$WebFUpdateH=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WebFUpdateH"));
	$WebFUpdateM=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WebFUpdateM"));
	$NICE=$unix->EXEC_NICE();
	$php=$unix->LOCATE_PHP5_BIN();
	$f[]="MAILTO=\"\"";
	$f[]="$WebFUpdateM $WebFUpdateH * * * root $NICE $php /usr/share/artica-postfix/exec.squid.blacklists.php --update --bycron >/dev/null 2>&1";
	$f[]="";
	@file_put_contents("/etc/cron.d/webfiltering-update", @implode("\n", $f));
	system("/etc/init.d/cron reload");
	
}
function ufdb_ini_status(){
	$unix=new unix();
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --ufdb --nowachdog >/usr/share/artica-postfix/ressources/interface-cache/UFDB_STATUS 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}

function create_cache_wizard(){
	
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.newcache.center.progress";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.cache.wizard.php > /usr/share/artica-postfix/ressources/logs/web/squid.newcache.center.log 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}
function cached_kerberos_tickets(){
	$unix=new unix();
	$klist=$unix->find_program("klist");
	exec("$klist -k /etc/squid3/PROXY.keytab -t 2>&1",$results);
	$c=0;
	while (list ($num, $line) = each ($results)){
		$line=trim($line);
		$tr=explode(" ",$line);
		if(!is_numeric($tr[0])){continue;}
		$num=trim($tr[0]);
		$date=trim($tr[1])." ".trim($tr[2]);
		$tickets=trim($tr[3]);
		$array[$c]["NUM"]=$num;
		$array[$c]["DATE"]=$date;
		$array[$c]["ticket"]=$tickets;
		$c++;
	}
	
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/kerberos-tickets-squid", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/web/kerberos-tickets-squid",0755);
}

function windows_update_delete(){
	$path=$_GET["windows-update-delete"];
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$php5 /usr/share/artica-postfix/exec.windowsupdate.php --delete \"$path\"";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}



?>