<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["install-tgz"])){install_tgz();exit;}
if(isset($_GET["is-installed"])){is_installed();exit;}


if(isset($_GET["reload-progress"])){reload_progress();exit;}
if(isset($_GET["reconfigure-progress"])){reconfigure_progress();exit;}
if(isset($_GET["install-progress"])){install_progress();exit;}
if(isset($_GET["disable-progress"])){disable_progress();exit;}
if(isset($_GET["enable-progress"])){enable_progress();exit;}
if(isset($_GET["accesses"])){access_real();exit;}
if(isset($_GET["daemon-status"])){status();exit;}
if(isset($_GET["disable-sid"])){disable_sid();exit;}
if(isset($_GET["enable-sid"])){enable_sid();exit;}
if(isset($_GET["restart-tail"])){restart_tail();exit;}
if(isset($_GET["firewall-sid"])){firewall_sid();exit;}
if(isset($_GET["firewall-build"])){firewall_build();exit;}

while (list ($num, $line) = each ($_GET)){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die();


function install_tgz(){
	$migration=null;
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/suricata.install.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/suricata.install.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.suricata.install.php --install {$_GET["key"]} {$_GET["OS"]} >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}

function install_progress(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/firehol.reconfigure.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/firehol.reconfigure.progress.txt";
	
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"],0777);
	@chmod($GLOBALS["LOG_FILE"],0777);
	
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.firehol.php --install-progress >{$GLOBALS["LOG_FILE"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
	
}

function enable_progress(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/firehol.reconfigure.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/firehol.reconfigure.progress.txt";
	
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"],0777);
	@chmod($GLOBALS["LOG_FILE"],0777);
	
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.firehol.php --enable-progress >{$GLOBALS["LOG_FILE"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
	
}

function disable_progress(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/firehol.reconfigure.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/firehol.reconfigure.progress.txt";
	
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"],0777);
	@chmod($GLOBALS["LOG_FILE"],0777);
	
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.firehol.php --disable-progress >{$GLOBALS["LOG_FILE"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}

function restart_tail(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup /etc/init.d/suricata-tail restart >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function access_real(){
	$unix=new unix();
	$tail=$unix->find_program("tail");
	$targetfile="/usr/share/artica-postfix/ressources/logs/firehol.log.tmp";
	$query2=null;
	$sourceLog="/var/log/syslog";
	$rp=$_GET["rp"];
	writelogs_framework("access_real -> $rp search {$_GET["query"]}" ,__FUNCTION__,__FILE__,__LINE__);

	$query=$_GET["query"];
	$grep=$unix->find_program("grep");


	$cmd="$grep -E \"FIREHOL:.*?IN=\" $sourceLog|$tail -n $rp >$targetfile 2>&1";

	if($query<>null){
		if(preg_match("#regex:(.*)#", $query,$re)){$pattern=$re[1];}else{
			$pattern=str_replace(".", "\.", $query);
			$pattern=str_replace("*", ".*?", $pattern);
			$pattern=str_replace("/", "\/", $pattern);
		}
	}
	if($pattern<>null){
		$cmd="$grep -E \"FIREHOL:.*?IN=.*?$pattern\" $sourceLog|$tail -n $rp  >$targetfile 2>&1";
	}



	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	@chmod("$targetfile",0755);
}


function status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php5 /usr/share/artica-postfix/exec.status.php --suricata >/usr/share/artica-postfix/ressources/logs/web/suricata.status");
	
}

function reconfigure_progress(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/suricata.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/suricata.progress.txt";
	
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"],0777);
	@chmod($GLOBALS["LOG_FILE"],0777);
	
	$comand="--reconfigure-progress";
	
	
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.suricata.php {$comand} >{$GLOBALS["LOG_FILE"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function disable_sid(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$sig=intval($_GET["sig"]);
	if($sig==0){return;}
	shell_exec("$nohup /usr/share/artica-postfix/bin/sidrule -d $sig >/dev/null 2>&1 &");
}
function enable_sid(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$sig=intval($_GET["sig"]);
	if($sig==0){return;}
	shell_exec("$nohup /usr/share/artica-postfix/bin/sidrule -e $sig >/dev/null 2>&1 &");	
}
function firewall_sid(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$sig=intval($_GET["sig"]);
	if($sig==0){
		writelogs_framework("$sig == 0 Aborting !!!!",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.suricata.php --firewall $sig >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}
function firewall_build(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.suricata-fw.php --run --force >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}

function reload_progress(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/suricata.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/suricata.progress.txt";
	
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"],0777);
	@chmod($GLOBALS["LOG_FILE"],0777);
	
	$comand="--reload-progress";
	
	
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.suricata.php {$comand} >{$GLOBALS["LOG_FILE"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

