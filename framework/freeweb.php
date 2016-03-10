<?php
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["mode-security-log"])){mod_security_logs();exit;}
if(isset($_GET["reconfigure"])){freeweb_reconfigure();exit;}
if(isset($_GET["loaded-modules"])){freeweb_modules();exit;}
if(isset($_GET["force-resolv"])){force_resolv();exit;}
if(isset($_GET["rebuild-vhost"])){rebuild_vhost();exit;}
if(isset($_GET["getidof"])){getidof();exit;}
if(isset($_GET["ApacheAccount"])){ApacheAccount();exit;}
if(isset($_GET["rouncube-plugins"])){roundcube_plugins_list();exit;}

if(isset($_GET["checks-site"])){FreeWebsCheck();exit;}
if(isset($_GET["apache-cmds"])){apache_service_cmds();exit;}
if(isset($_GET["users-webdav"])){apache_webdavusrs();exit;}
if(isset($_GET["watchdog-config"])){apache_watchdog();exit;}
if(isset($_GET["changeinit-on"])){change_init_on();exit;}
if(isset($_GET["changeinit-off"])){change_init_off();exit;}
if(isset($_GET["articaget"])){articaget();exit;}
if(isset($_GET["restore-site"])){restore_site();exit;}
if(isset($_GET["ScanSize"])){ScanSize();exit;}
if(isset($_GET["roudce-replic-host"])){roundcube_replic_single();exit;}
if(isset($_GET["display-config"])){display_config();exit;}
if(isset($_GET["reconfigure-webapp"])){reconfigure_webapp();exit;}
if(isset($_GET["query-logs"])){query_logs();exit;}
if(isset($_GET["remove-disabled"])){remove_disabled();exit;}
if(isset($_GET["status"])){freewebs_status();exit;}
if(isset($_GET["reconfigure-updateutility"])){updateutility();exit;}
if(isset($_GET["reconfigure-wpad"])){wpad();exit;}
if(isset($_GET["restart-progress"])){restart_progress();exit;}





while (list ($num, $line) = each ($_GET)){$f[]="$num=$line";}

writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die();

function apache_service_cmds(){
	$cmds=$_GET["apache-cmds"];
	exec("/etc/init.d/artica-postfix $cmds apachesrc --verbose 2>&1",$results);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
}

function force_resolv(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.freeweb.php --resolv --force >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
	
}

function restart_progress(){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/freeweb.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/freeweb.progress.txt";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.freeweb.php --restart-progress >{$GLOBALS["LOG_FILE"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function reconfigure_webapp(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.freeweb.php --reconfigure-webapp >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}
function updateutility(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.freeweb.php --reconfigure-updateutility >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function wpad(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.freeweb.php --reconfigure-wpad >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}


function apache_watchdog(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.freeweb.php --monit >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
}

function apache_webdavusrs(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.webdav.users.php >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function rebuild_vhost(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$servername=$_GET["servername"];
	@unlink("/usr/share/artica-postfix/ressources/logs/web/freeweb.rebuild.progress.txt");
	@touch("/usr/share/artica-postfix/ressources/logs/web/freeweb.rebuild.progress.txt");
	@chmod("/usr/share/artica-postfix/ressources/logs/web/freeweb.rebuild.progress.txt",0777);
	
	@unlink("/usr/share/artica-postfix/ressources/logs/freeweb.rebuild.progress");
	@touch("/usr/share/artica-postfix/ressources/logs/freeweb.rebuild.progress");
	@chmod("/usr/share/artica-postfix/ressources/logs/freeweb.rebuild.progress",0777);
	
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.freeweb.php --sitename $servername --restart --no-httpd-conf >/usr/share/artica-postfix/ressources/logs/web/freeweb.rebuild.progress.txt 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
	
}

function ScanSize(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.freeweb.php --ScanSize >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
}

function getidof(){
	$unix=new unix();
	$uid=trim(base64_decode($_GET["getidof"]));
	if($uid==null){return;}
	$id=$unix->find_program("id");
	exec("$id \"$uid\" 2>&1",$results);
	writelogs_framework("$id \"$uid\" 2>&1 ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	$datas=trim(@implode("", $results));
	if(!preg_match("#uid=([0-9]+).+?#", $datas)){echo "<articadatascgi>FALSE</articadatascgi>";}else{echo "<articadatascgi>TRUE</articadatascgi>";}
}

function ApacheAccount(){
	$unix=new unix();
	$array=array($unix->APACHE_SRC_ACCOUNT(),$unix->APACHE_SRC_GROUP());
	echo "<articadatascgi>".base64_encode(serialize($array))."</articadatascgi>";
	return;
}

function mod_security_logs(){
	$servername=$_GET["servername"];
	$unix=new unix();
	$tail=$unix->find_program("tail");
	$cmd="$tail -n 500 /var/log/apache2/$servername/modsec_debug_log 2>&1";
	exec("$cmd",$results);
	writelogs_framework("$cmd ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";	
}

function freeweb_reconfigure(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.freeweb.php --build >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function freeweb_modules(){
	$unix=new unix();
	$apache2ctl=$unix->find_program("apache2ctl");
	if(!is_file($apache2ctl)){$apache2ctl=$unix->find_program("apachectl");}
	if(!is_file($apache2ctl)){echo "<articadatascgi>".base64_encode(serialize(array("apache2ctl no such file")))."</articadatascgi>";return;}
	$cmd="$apache2ctl -t -D DUMP_MODULES 2>&1";
	exec("$cmd",$results);
	writelogs_framework("$cmd ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
}

function roundcube_plugins_list(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$php /usr/share/artica-postfix/exec.freeweb.php --rouncube-plugins {$_GET["servername"]} 2>&1";
	exec("$cmd",$results);
	writelogs_framework("$cmd ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);	
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
}

function FreeWebsCheck(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$sitename=$_GET["sitename"];
	$cmd="$php /usr/share/artica-postfix/exec.freeweb.php --sitename \"$sitename\" --no-httpd-conf --noreload --verbose";
	$results[]=$cmd;
	exec($cmd,$results);
	writelogs_framework("$cmd ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);	
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";	
}
function change_init_on(){
	$unix=new unix();
	$debianbin=$unix->find_program("update-rc.d");
	$redhatbin=$unix->find_program("chkconfig");
	
	
	if(is_file("/etc/init.d/apache")){$service="apache";}
	if(is_file("/etc/init.d/httpd")){$service="httpd";}
	if($service==null){return;}
	if(is_file($debianbin)){shell_exec("$debianbin -f $service remove >/dev/null 2>&1");}
	if(is_file($redhatbin)){shell_exec("$redhatbin --del $service >/dev/null 2>&1");}
	
	
}

function change_init_off(){
	$unix=new unix();
	$debianbin=$unix->find_program("update-rc.d");
	$redhatbin=$unix->find_program("chkconfig");
	
	
	if(is_file("/etc/init.d/apache")){$service="apache";}
	if(is_file("/etc/init.d/httpd")){$service="httpd";}
	if($service==null){return;}
	if(is_file($debianbin)){shell_exec("$debianbin -f $service defaults >/dev/null 2>&1");}
	if(is_file($redhatbin)){shell_exec("$redhatbin --add $service >/dev/null 2>&1");}	
	
}
function articaget(){
	$sitename=$_GET["articaget"];
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$results[]="FRAMEWORK ORDER TO BACKUP [$sitename]";
	$cmd="$php /usr/share/artica-postfix/exec.freeweb.php --backupsite \"$sitename\" --verbose 2>&1";	
	exec($cmd,$results);
	writelogs_framework("$cmd ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);	
	echo "<articadatascgi>".base64_encode(@implode("\n",$results))."</articadatascgi>";		
}

function restore_site(){
	$path=trim(base64_decode($_GET["path"]));
	$sitename=trim(base64_decode($_GET["sitename"]));
	if($sitename==null){$sitename="DEFAULT";}
	$instance_id=trim($_GET["instance-id"]);
	if(!is_numeric($instance_id)){$instance_id=0;}
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();	
	$cmd="$nohup $php /usr/share/artica-postfix/exec.freeweb.php --restore \"$sitename\" \"$path\" $instance_id --verbose >>/usr/share/artica-postfix/ressources/logs/web/freewebs.restore 2>&1 &";	
	shell_exec($cmd);
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);	
}
function roundcube_replic_single(){
	$serv=$_GET["servername"];
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.freeweb.rdcube-replic.php --host $serv >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function display_config(){
	
	$servername=$_GET["servername"];
	
	$conf="/etc/apache2/sites-enabled/artica-$servername.conf";
	
	if(preg_match("#_default_#",$servername)){
		$conf="/etc/apache2/sites-enabled/000-default";
	}
	
	if(!is_file($conf)){
		echo "<articadatascgi>".base64_encode("$conf no such file")."</articadatascgi>";	
		return;	
	}
	
	echo "<articadatascgi>".base64_encode("#$conf")."</articadatascgi>";
	
	@unlink("/usr/share/artica-postfix/ressources/logs/web/vhost.conf");
	@copy($conf, "/usr/share/artica-postfix/ressources/logs/web/vhost.conf");
	
	
	
}

function remove_disabled(){
	$serv=$_GET["servername"];
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.freeweb.php --remove-disabled >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
	
}

function query_logs(){
	$unix=new unix();
	$tail=$unix->find_program("tail");
	$grep=$unix->find_program("grep");
	$servername=$_GET["servername"];
	$filter=base64_decode($_GET["filter"]);
	$type=$_GET["type"];
	if($type=="errors"){
		$logfile="/var/log/apache2/$servername/error.log";
		
	}else{
		$logfile="/var/log/apache2/$servername/access.log";
	}
	$rp=$_GET["rp"];
	
	
	
	if($filter<>null){
		$cmd="$grep -i -E \"$filter\" $logfile|$tail -n $rp";
	}else{
		$cmd="$tail -n $rp $logfile";
		}		
			
	$cmd="$cmd 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	krsort($results);
	echo "<articadatascgi>".base64_encode(@implode("\n",$results))."</articadatascgi>";
}
function freewebs_status(){
	$hostname=$_GET["hostname"];
	$hosnenc=md5($hostname);
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_INTERFACE,"127.0.0.1");
	curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1/$hosnenc/$hosnenc-status");
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 0);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
	$datas=curl_exec($ch);
	
	$error=curl_errno($ch);
	if($error>0){
		writelogs_framework("error number $error http://127.0.0.1/$hosnenc/$hosnenc-status",__FUNCTION__,__FILE__,__LINE__);
	}
	curl_close($ch);	
	echo "<articadatascgi>".base64_encode($datas)."</articadatascgi>";
}
?>