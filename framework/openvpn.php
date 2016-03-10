<?php
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
include_once(dirname(__FILE__)."/class.postfix.inc");

if(isset($_GET["build-vpn-user"])){BuildWindowsClient();exit;}
if(isset($_GET["restart-clients"])){RestartClients();exit;}
if(isset($_GET["restart-clients-tenir"])){RestartClientsTenir();exit;}
if(isset($_GET["is-client-running"])){vpn_client_running();exit;}
if(isset($_GET["client-events"])){vpn_client_events();exit;}
if(isset($_GET["client-reconnect"])){vpn_client_hup();exit;}
if(isset($_GET["client-reconfigure"])){vpn_client_reconfigure();exit;}
if(isset($_GET["certificate-infos"])){certificate_infos();}
if(isset($_GET["ifAllcaExists"])){ifAllcaExists();exit;}
if(isset($_GET["RestartOpenVPNServer"])){RestartOpenVPNServer();exit;}
if(isset($_GET["enable"])){enable_service();exit;}



function certificate_infos(){
	$unix=new unix();
	$openssl=$unix->find_program("openssl");
	$l=$unix->FILE_TEMP();
	$cmd="$openssl x509 -in /etc/artica-postfix/openvpn/keys/vpn-server.key -text -noout >$l 2>&1";
	
	if($cmd<>null){
		shell_exec($cmd);
		$datas=explode("\n",@file_get_contents($l));
		writelogs_framework($cmd." =".count($datas)." rows",__FUNCTION__,__FILE__,__LINE__);
		@unlink($l);
	}
	echo "<articadatascgi>". base64_encode(serialize($datas))."</articadatascgi>";
}

function ifAllcaExists(){
	
	if(is_file("/etc/artica-postfix/openvpn/keys/openvpn-ca.crt")){
		echo "<articadatascgi>TRUE</articadatascgi>";
	}
}

function RestartOpenVPNServer(){

exec("/etc/init.d/artica-postfix restart openvpn --verbose",$results);
echo "<articadatascgi>". base64_encode(@implode("\n", $results))."</articadatascgi>";
}


function RestartClients(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup ".LOCATE_PHP5_BIN2() ." /usr/share/artica-postfix/exec.openvpn.php --client-restart >/dev/null 2>&1 &");
	shell_exec($cmd);
	}
	
function RestartClientsTenir(){
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.openvpn.php --client-restart",$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
	
}
function enable_service(){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/openvpn.enable.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/openvpn.enable.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.openvpn.enable.php >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
	
function vpn_client_running(){
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;}
	$id=$_GET["is-client-running"];
	$pid=trim(@file_get_contents("/etc/artica-postfix/openvpn/clients/$id/pid"));
	$unix=new unix();
	writelogs_framework("/etc/artica-postfix/openvpn/clients/$id/pid -> $pid",__FUNCTION__,__FILE__,__LINE__);
	
	if($unix->process_exists($pid)){
		echo "<articadatascgi>TRUE</articadatascgi>";
		return;
	}
	writelogs_framework("$id: pid $pid",__FUNCTION__,__FILE__,__LINE__);
	
	exec($unix->find_program("pgrep") ." -l -f \"openvpn.+?clients\/2\/settings.ovpn\" 1>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#^([0-9]+)\s+.*openvpn#",$ligne)){
			writelogs_framework("pid= preg_match= {$re[1]}",__FUNCTION__,__FILE__,__LINE__);
			echo "<articadatascgi>TRUE</articadatascgi>";
			return;
		}
	}
	writelogs_framework("$pid NOT RUNNING",__FUNCTION__,__FILE__,__LINE__);
}	


function BuildWindowsClient(){
	$uid=$_GET["build-vpn-user"];
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/openvpn.client.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/openvpn.client.log";
	
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.openvpn.build-client.php \"$uid\" >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}


function ChangeCommonName($commonname){

if(!is_file("/etc/artica-postfix/openvpn/openssl.cnf")){
	echo "<articadatascgi>ERROR: Unable to stat /etc/artica-postfix/openvpn/openssl.cnf</articadatascgi>";
	return false;
}
	
$tbl=explode("\n",@file_get_contents("/etc/artica-postfix/openvpn/openssl.cnf"));
while (list ($num, $ligne) = each ($tbl) ){
	if(preg_match("#^commonName_default#",$ligne)){
		$tbl[$num]="commonName_default=\t$commonname";
	}
}

@file_put_contents("/etc/artica-postfix/openvpn/openssl.cnf",implode("\n",$tbl));
return true;
}

function vpn_client_events(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$tail=$unix->find_program("tail");
	$cmd=trim("$tail -n 300 /etc/artica-postfix/openvpn/clients/{$_GET["ID"]}/log 2>&1 ");
	
	exec($cmd,$results);		
	writelogs_framework($cmd ." ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}

function vpn_client_hup(){
	$pid=@file_get_contents("/etc/artica-postfix/openvpn/clients/{$_GET["ID"]}/pid");
	$unix=new unix();
	$kill=$unix->find_program("kill");
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");		
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.openvpn.php --client-configure-start {$_GET["ID"]} 2>&1 &");
	if($unix->process_exists($pid)){unix_system_kill_force($pid);}
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec("$cmd");	
	
}

function vpn_client_reconfigure(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.openvpn.php --client-conf 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec("$cmd");
	
}


?>