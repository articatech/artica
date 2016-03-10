<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.openvpn.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');

$GLOBALS["server-conf"]=false;
$GLOBALS["IPTABLES_ETH"]=null;


xrun();


function xrun(){

	$unix=new unix();
	build_progress("{enable_service}",15);
	$vpn=new openvpn();
	$vpn->main_array["GLOBAL"]["ENABLE_SERVER"]=1;
	@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableOPenVPNServerMode", 1);
	$vpn->Save(true);
	build_progress("{building_configuration}",50);
	$php=$unix->LOCATE_PHP5_BIN();
	system("$php /usr/share/artica-postfix/exec.openvpn.php --server-conf");
	system("$php /usr/share/artica-postfix/exec.initslapd.php --openvpn-server");
	
	build_progress("{restart_service}",90);
	system("/etc/init.d/openvpn-server restart");
	
	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		build_progress("{done}",100);
	}else{
		build_progress("{failed}",100);
	}

}

function PID_NUM(){

	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/openvpn/openvpn-server.pid");
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("openvpn");
	return $unix->PIDOF_PATTERN("$Masterbin --port.+?--dev");

}




function build_progress($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/openvpn.enable.progress";
	echo "[{$pourc}%] $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
	if($GLOBALS["OUTPUT"]){sleep(1);}
}


?>