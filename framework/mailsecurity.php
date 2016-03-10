<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["status"])){status();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["systemusers"])){systemusers();exit;}
if(isset($_GET["restart-progress"])){restart_progress();exit;}
if(isset($_GET["install-tgz"])){install_tgz();exit;}
if(isset($_GET["chowndirs"])){chowndirs();exit;}

while (list ($num, $line) = each ($_GET)){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);


function status(){
	$unix=new unix();
	
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$php5 /usr/share/artica-postfix/exec.status.php --proftpd 2>&1";
	
	exec($cmd,$results);
	writelogs_framework("$cmd -> ".count($results)." line(s)",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(@implode("\n", $results))."</articadatascgi>";
	
}
function restart(){
	$unix=new unix();

	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.proftpd.php --restart >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function chowndirs(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.proftpd.php --chowndirs >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}
function install_tgz(){
	$migration=null;
	
	
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/mailsecurity.install.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/mailsecurity.install.progress.txt";

	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.mailsecurity.install.php --install {$_GET["key"]} {$_GET["OS"]} >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}

function restart_progress(){
	
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/proftpd.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/proftpd.progress.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["CACHEFILE"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.proftpd.php --restart --progress >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
		
	
}

function systemusers(){
	
	
	$f=explode("\n",@file_get_contents("/etc/passwd"));
	while (list ($num, $line) = each ($f)){
		if(!preg_match("#(.+?):x:([0-9]+):([0-9]+):#", $line,$re)){continue;}
		$ARRAYU["{$re[2]}:{$re[3]}"]=$re[1];
		
		
	}
	@file_put_contents("/etc/artica-postfix/settings/Daemons/SystemUsers", serialize($ARRAYU));
	@chmod("/etc/artica-postfix/settings/Daemons/SystemUsers",0755);
	
}

