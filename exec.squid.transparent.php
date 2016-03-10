<?php
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["EBTABLES"]=false;
$GLOBALS["OUTPUT"]=true;
$GLOBALS["PROGRESS"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');



	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	$unix=new unix();
	if($unix->process_exists($pid,basename(__FILE__))){echo "Starting......: ".date("H:i:s")." Squid Transparent mode: $pid -> DIE\n";die();}
	if($argv[1]=="--iptables-delete"){iptables_delete_all();exit;}
	if($argv[1]=="--progress"){restart_progress();exit;}

$users=new usersMenus();
if($users->WEBSTATS_APPLIANCE){iptables_delete_all();die();}
if(!$users->SQUID_INSTALLED){iptables_delete_all();die();}

$sysctl=$unix->find_program("sysctl");

$pids=$unix->PIDOF_PATTERN_ALL("exec.squid.transparent.php");
if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." instances:".count($pids)."\n";}
if(count($pids)>2){
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." Too many instances ". count($pids)." starting squid, kill them!\n";}
	$mypid=getmypid();
	while (list ($pid, $ligne) = each ($pids) ){
		if($pid==$mypid){continue;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." killing $pid\n";}
		unix_system_kill_force($pid);
	}

}

$sock=new sockets();
$SquidWCCPEnabled=$sock->GET_INFO("SquidWCCPEnabled");
$hasProxyTransparent=MYSQL_AS_PROXY_TRANSPARENT();
$EnableArticaHotSpot=$sock->GET_INFO("EnableArticaHotSpot");
$EnableTransparent27=intval($sock->GET_INFO("EnableTransparent27"));
$MikrotikTransparent=intval($sock->GET_INFO('MikrotikTransparent'));
$UseTProxyMode=intval($sock->GET_INFO("UseTProxyMode"));
$FireHolEnable=intval($sock->GET_INFO("FireHolEnable"));
if(!is_numeric($SquidWCCPEnabled)){$SquidWCCPEnabled=0;}
if(!is_numeric($hasProxyTransparent)){$hasProxyTransparent=0;}
if(!is_numeric($EnableArticaHotSpot)){$EnableArticaHotSpot=0;}


echo "Starting......: ".date("H:i:s")." Squid Check hasProxyTransparent key ($hasProxyTransparent)...\n";
echo "Starting......: ".date("H:i:s")." Squid Check EnableArticaHotSpot key ($EnableArticaHotSpot)...\n";
echo "Starting......: ".date("H:i:s")." Squid Check EnableTransparent27 key ($EnableTransparent27)...\n";
echo "Starting......: ".date("H:i:s")." Squid Check MikrotikTransparent key ($MikrotikTransparent)...\n";
echo "Starting......: ".date("H:i:s")." Squid Check UseTProxyMode key ($UseTProxyMode)...\n";
echo "Starting......: ".date("H:i:s")." Squid Check FireHolEnable key ($FireHolEnable)...\n";

if($FireHolEnable==1){
	disable_transparent();
	return;
}


if($UseTProxyMode==1){
	disable_transparent();
	$php=$unix->LOCATE_PHP5_BIN();
	echo "Starting......: ".date("H:i:s")." Squid running Tproxy Mode\n";
	system("$php /usr/share/artica-postfix/exec.squid.tproxy.php");
	echo "Starting......: ".date("H:i:s")." Squid running TProxy script...\n";
	shell_exec("/etc/init.d/iptables-transparent start");
	return;
	
}

if($EnableArticaHotSpot==1){disable_transparent();return;}
if($EnableTransparent27==1){disable_transparent();return;}
if($hasProxyTransparent==1){enable_transparent();}else{disable_transparent();}

if(is_file("/etc/init.d/iptables-transparent")){
	echo "Starting......: ".date("H:i:s")." Squid running Iptables script...\n";
	shell_exec("/etc/init.d/iptables-transparent start");
}




function disable_transparent(){
	$unix=new unix();
	
	build_progress("Transparent is disabled", 110);
	
	$php=$unix->LOCATE_PHP5_BIN();
	$sh[]=script_startfile();
	$sh[]="{$GLOBALS["echobin"]} \"Squid Transparent mode: Disabled\"";
	$sh[]=$php." ".basename(__FILE__)."/exec.squid.transparent.delete.php >/dev/null 2>&1";
	$sh[]=script_endfile();
	@file_put_contents("/etc/init.d/iptables-transparent", @implode("\n", $sh));
	script_install();
	shell_exec($php." ".dirname(__FILE__)."/exec.squid.transparent.delete.php");
	
}




function ebtables_rules(){
	$unix=new unix();
	$ebtables=$unix->find_program("ebtables");
	$aptget=$unix->find_program("apt-get");
	if($GLOBALS["VERBOSE"]){echo "EBTABLES: [".__LINE__."] ebtables = $ebtables\n";}
	$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
	$squid=new squidbee();
	$SSL_BUMP=$squid->SSL_BUMP;
	if($GLOBALS["VERBOSE"]){echo "EBTABLES: [".__LINE__."] SSL_BUMP = $SSL_BUMP\n";}
	if(!is_file($ebtables)){ 
		if($GLOBALS["VERBOSE"]){echo "EBTABLES: [".__LINE__."] NO BINARY apt-get = \"$aptget\"\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." ebtables no such binary...\n";}
		$unix->DEBIAN_INSTALL_PACKAGE("ebtables");
	}
	
	$ebtables=$unix->find_program("ebtables");
	
	if(!is_file($ebtables)){return "# ebtables, no such binary"; }
	
	$q=new mysql();

	$sql="SELECT `Interface` FROM `nics` WHERE `Bridged`=1";
	if($GLOBALS["VERBOSE"]){echo "[".__LINE__."] $sql\n";}
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "$q->mysql_error\n";}
	$count=mysql_num_rows($results);
	if($count==0){
		return "# ebtables, no bridge defined...";
	}
	$GLOBALS["EBTABLES"]=true;
	if($GLOBALS["VERBOSE"]){echo "[".__LINE__."] EBTABLES = TRUE\n";}
	//iptables -t nat -A PREROUTING -i br0 -p tcp --dport 80 -j REDIRECT --to-port 3128
	$f[]="# ebtables, $count Interfaces SSL_BUMP = $SSL_BUMP";
	$f[]="$ebtables -t broute -X";
	$f[]="$ebtables -t broute -F";
	$f[]="$ebtables -t broute -A BROUTING -p IPv4 --ip-protocol 6 --ip-destination-port 80 -j redirect --redirect-target ACCEPT";
	if($SSL_BUMP==1){
		$f[]="$ebtables -t broute -A BROUTING -p IPv4 --ip-protocol 6 --ip-destination-port 443 -j redirect --redirect-target ACCEPT";
		
	}
	return @implode("\n", $f);
}

function enable_transparent(){
	$unix=new unix();
	$q=new mysql_squid_builder();
	$sock=new sockets();
	$iptables=$unix->find_program("iptables");
	$GLOBALS["MASKERADE_DONE"]=false;
	$sysctl=$unix->find_program("sysctl");
	$ips=$unix->ifconfig_interfaces_list();
	$php=$unix->LOCATE_PHP5_BIN();
	$KernelSendRedirects=$sock->GET_INFO("KernelSendRedirects");
	$EnableArticaHotSpot=intval($sock->GET_INFO("EnableArticaHotSpot"));
	if(!is_numeric($KernelSendRedirects)){$KernelSendRedirects=1;}
	$sh[]=script_startfile();
	
	
	if($EnableArticaHotSpot==1){
		build_progress("HotSpot is enabled, aborting",110);
		$sh[]="{$GLOBALS["echobin"]} \"Squid Transparent mode: HotSpot system is enabled\"";
		$sh[]="$php /usr/share/artica-postfix/exec.squid.transparent.delete.php || true";
		$sh[]=script_endfile();
		@file_put_contents("/etc/init.d/iptables-transparent", @implode("\n", $sh));
		script_install();
		return;
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." ebtables...\n";}
	build_progress("Checking ebtables rules",20);
	$sh[]=ebtables_rules();
	build_progress("Checking ebtables rules {done}",25);
	
	

	build_progress("Building default script...",35);	
	
	

	$sh[]="{$GLOBALS["echobin"]} \"Transparent mode: enable the gateway mode...\"";
	$sh[]="{$GLOBALS["echobin"]} \"Squid Transparent mode: KernelSendRedirects = $KernelSendRedirects...\"";
	$sh[]="{$GLOBALS["echobin"]} \"Squid Transparent mode:Removing Iptables rules\"";
	$sh[]="$php /usr/share/artica-postfix/exec.squid.transparent.delete.php || true";
	$sh[]="{$GLOBALS["echobin"]} \"Squid Transparent mode: Patching kernel\"";
	$sh[]="$sysctl -w net.ipv4.ip_forward=1 2>&1";
	$sh[]="$sysctl -w net.ipv4.conf.default.send_redirects=$KernelSendRedirects 2>&1";
	$sh[]="$sysctl -w net.ipv4.conf.all.send_redirects=$KernelSendRedirects 2>&1";
	if(is_file("/proc/sys/net/ipv4/conf/eth0/send_redirects")){
		$sh[]="$sysctl -w net.ipv4.conf.eth0.send_redirects=$KernelSendRedirects 2>&1";
	}
	
	$sql="SELECT * FROM proxy_ports WHERE enabled=1 AND transparent=1";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){
		$results=unserialize(@file_get_contents("/etc/artica-postfix/proxy_ports_transparent"));
	}else{
		@file_put_contents("/etc/artica-postfix/proxy_ports_transparent", serialize($results));
	}
	
	
	$sh[]="# ".mysql_num_rows($results)." ports to configure..";
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$eth=$ligne["nic"];
		$ssl_port=0;
		$http_port=0;
		$ID=$ligne["ID"];
		$UseSSL=intval($ligne["UseSSL"]);
		if($UseSSL==1){
			$ssl_port=intval($ligne["port"]);
		}else{
			$http_port=intval($ligne["port"]);
		}
		build_progress("Building rule for ports HTTP:$http_port SSL Port:$ssl_port...",35);
		$sh[]="# Interface $eth SSL:$UseSSL, HTTP:$http_port SSL Port:$ssl_port [".__LINE__."]";
		$sh[]=enable_transparent_byport($eth,$http_port,$ssl_port,$ID);
	}
	

	$sh[]=script_endfile();
	@file_put_contents("/etc/init.d/iptables-transparent", @implode("\n", $sh));
	build_progress("{installing_default_script}...",40);
	script_install();
	build_progress("{installing_default_script}...{done}",50);

}

function whitelist_transparent_bymeta(){
	
	
	
	if(!is_file("/etc/squid3/meta-transparent-whitelist.array")){
		echo "Starting......: ".date("H:i:s")." Transparent list META does not exists.\n";
		return;
	}
	$unix=new unix();
	echo "Starting......: ".date("H:i:s")." Transparent list META ( checking ).\n";
	$data=unserialize(@file_get_contents("/etc/squid3/meta-transparent-whitelist.array"));
	$iptables=$unix->find_program("iptables");
	$MARKLOG="-m comment --comment \"ArticaSquidTransparent\"";
	
	while (list ($index, $ligne) = each ($data) ){
		$pattern=$ligne["pattern"];
		$destport=$ligne["destport"];
		$include=$ligne["include"];
		if($include==0){$object=" -s $pattern";}
		if($include==1){$object=" -d $pattern";}
		$f[]=Output_iptables("$iptables -t nat -I PREROUTING $object -p tcp -m tcp --dport $destport -j RETURN $MARKLOG || true");
	}
	
	if(count($f)>0){return @implode("\n", $f);}
	

}

function whitelist_transparent_byport($include,$portid,$SQUIDPORT=0,$ssl_port=0){
	$unix=new unix();
	
	$iptables=$unix->find_program("iptables");
	$MARKLOG="-m comment --comment \"ArticaSquidTransparent\"";
	
	$q=new mysql_squid_builder();		
	$sql="SELECT pattern  FROM `proxy_ports_wbl` WHERE include=$include AND portid=$portid";
	$results = $q->QUERY_SQL($sql);
	if(mysql_num_rows($results)==0){
		build_progress("Include:$include Port $portid/$SQUIDPORT/$ssl_port no white rule...",35);
		return;
	}
		
	while ($ligne = mysql_fetch_assoc($results)) {
		$f[]="# include:$include 1=destination, 0= source";
		if($include==0){$object=" -s {$ligne["pattern"]}";}
		if($include==1){$object=" -d {$ligne["pattern"]}";}
		if($SQUIDPORT>2){$dport=80;}
		if($ssl_port>2){$dport=443;}
		$f[]=Output_iptables("$iptables -t nat -I PREROUTING $object -p tcp -m tcp --dport $dport -j RETURN $MARKLOG || true");
		
		
			
			
	}
	return @implode("\n", $f);
		
}



function enable_transparent_byport($InterfaceDefined=null,$SQUIDPORT=0,$ssl_port=0,$portid){
	
	$unix=new unix();
	$sock=new sockets();
	$SQUIDPORT=intval($SQUIDPORT);
	$UseTProxyMode=$sock->GET_INFO("UseTProxyMode");
	if(!is_numeric($UseTProxyMode)){$UseTProxyMode=0;}	
	$EnableArticaHotSpot=$sock->GET_INFO("EnableArticaHotSpot");
	
	$iptables=$unix->find_program("iptables");	
	$sysctl=$unix->find_program("sysctl");
	$ips=$unix->ifconfig_interfaces_list();
	$php=$unix->LOCATE_PHP5_BIN();
	

	build_progress("Interface: $InterfaceDefined Port $portid/$SQUIDPORT/$ssl_port no white rule...",35);
	$sh[]="{$GLOBALS["echobin"]} \"Squid Transparent mode: Enable rules\"";
	
	unset($ips["127.0.0.1"]);
	unset($ips["lo"]);
	
	
	
	
	 //Attention, transféré vers firehole
	if($UseTProxyMode==1){
		$sh[]="{$GLOBALS["echobin"]} \"Squid Transparent mode: Activate TProxy mode...\"";	
	}
	
	if($UseTProxyMode==1){
		$sh[]="$iptables -t mangle -N DIVERT -m comment --comment \"ArticaSquidTransparent\" || true";
		$sh[]="$iptables -t mangle -A DIVERT -j MARK --set-mark 1 -m comment --comment \"ArticaSquidTransparent\" || true";
		$sh[]="$iptables -t mangle -A DIVERT -j ACCEPT -m comment --comment \"ArticaSquidTransparent\" || true";
		$sh[]="$iptables -t mangle -A PREROUTING -p tcp -m socket -j DIVERT -m comment --comment \"ArticaSquidTransparent\" || true";
		if($SQUIDPORT>2){
			$sh[]="$iptables -t mangle -A PREROUTING -p tcp --dport 80 -j TPROXY --tproxy-mark 0x1/0x1 --on-port $SQUIDPORT -m comment --comment \"ArticaSquidTransparent\" || true";
			return @implode("\n", $sh);
		}
		
		if($ssl_port>2){
			
			
		}
		
		return;
	}
	
	
$IPTABLES=$iptables;
$INPUTINTERFACE="eth0";
$MARKLOG="-m comment --comment \"ArticaSquidTransparent\"";




$JREDIRECT_TEXT="-j REDIRECT --to-port $SQUIDPORT";
$JREDIRECTSSL_TEXT="-j REDIRECT --to-port $ssl_port";

$INTERFACE_TEXT=null;
if($InterfaceDefined<>null){
	$INTERFACE_TEXT=" -i $InterfaceDefined";
}


$sh[]="{$GLOBALS["echobin"]} \"Starting......: ".date("H:i:s")." Squid Transparent Interface:$InterfaceDefined  HTTP Port:$SQUIDPORT / SSL Port:$ssl_port\"";
$sh[]=whitelist_transparent_byport(0,$portid,$SQUIDPORT,$ssl_port);
$sh[]=whitelist_transparent_byport(1,$portid,$SQUIDPORT,$ssl_port);
$sh[]=whitelist_transparent_bymeta();

	while (list ($interface, $ip) = each ($ips) ){
		$SQUIDIP=$ip;
		if($InterfaceDefined<>null){
			if($interface<>$InterfaceDefined){continue;}
		}
		
		if(preg_match("#^ham#", $interface)){
			$sh[]="{$GLOBALS["echobin"]} \"Starting......: ".date("H:i:s")." Squid Transparent mode: Squid Transparent mode: skipping $interface interface\"";continue;
		}
		
		
			
		if($SQUIDPORT>2){
			if(!$GLOBALS["EBTABLES"]){$sh[]="$iptables -t nat -A PREROUTING -s $SQUIDIP -p tcp --dport 80 -j ACCEPT $MARKLOG || true";}
		}

		if(!$GLOBALS["EBTABLES"]){
			if($ssl_port>2){
				$sh[]="$iptables -t nat -A PREROUTING -s $SQUIDIP -p tcp --dport 443 -j ACCEPT $MARKLOG || true";
			}else{
				$sh[]="# $SQUIDIP ssl_port = $ssl_port abort";
			}
		}
		
	}
	
	
	
	if($SQUIDPORT>2){
			$sh[]="# $SQUIDPORT > 2, Enable HTTP (80) To $SQUIDPORT [".__LINE__."]";
			$sh[]="$iptables -t nat -A PREROUTING{$INTERFACE_TEXT} -p tcp --dport 80 $JREDIRECT_TEXT $MARKLOG || true";
		}
	
	
	if($ssl_port>2){
		$sh[]="# $ssl_port > 2, Enable SSL (443) To $ssl_port [".__LINE__."]";
		$sh[]="$iptables -t nat -A PREROUTING{$INTERFACE_TEXT} -p tcp --dport 443 $JREDIRECTSSL_TEXT $MARKLOG || true";}
	
	if(!$GLOBALS["MASKERADE_DONE"]){
		if(!$GLOBALS["EBTABLES"]){
			$GLOBALS["MASKERADE_DONE"]=true;
			$sh[]="$iptables -t nat -A POSTROUTING -j MASQUERADE $MARKLOG || true";
		}
	}
	
	
	if($SQUIDPORT>2){if(!$GLOBALS["EBTABLES"]){$sh[]="$iptables -t mangle -A PREROUTING -p tcp --dport $SQUIDPORT -j DROP $MARKLOG || true";}}
	
	
	if(!$GLOBALS["EBTABLES"]){
		if($ssl_port>2){
			if(!$GLOBALS["EBTABLES"]){
				$sh[]="$iptables -t mangle -A PREROUTING -p tcp --dport $ssl_port -j DROP $MARKLOG || true";
			}
		}
	}
	
	return @implode("\n", $sh);
	

		

}



function shell_exec2($cmd){
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." Squid Transparent mode: EXECUTE `$cmd`\n";}
	exec($cmd,$results);
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." Squid Transparent mode:". count($results)." rows\n";}
	while (list ($index, $row) = each ($results) ){if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." Squid Transparent mode: \"$row\"\n";}}
	
	reset($results);
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." OK: `$cmd`\n";}
	return $results;
	
	
}

function script_install(){
	
	
	@chmod("/etc/init.d/iptables-transparent",0755);
	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f iptables-transparent defaults >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add iptables-transparent >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 1234 iptables-transparent on >/dev/null 2>&1");
	}
	
}

function script_endfile(){
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$sock=new sockets();
	
	$sh[]="\t$php ".dirname(__FILE__)."/exec.mikrotik.php >/dev/null 2>&1";
	$sh[]="{$GLOBALS["echobin"]} \"Transparent proxy done.\"";
	$sh[]=";;";
	$sh[]="  stop)";
	if($MikrotikTransparent==1){
		$sh[]="{$GLOBALS["echobin"]} \"Removing MiKroTiK Iptables rules\"";
		$sh[]=$php." ".dirname(__FILE__)."/exec.squid.transparent.delete.php --mikrotik >/dev/null 2>&1";
	}
	$sh[]="{$GLOBALS["echobin"]} \"Removing Iptables rules\"";
	$sh[]=$php." ".__FILE__." --iptables-delete >/dev/null 2>&1";
	$sh[]=";;";
	$sh[]="  reconfigure)";
	$sh[]="{$GLOBALS["echobin"]} \"Removing Iptables rules\"";
	$sh[]=$php." ".__FILE__." --iptables-delete >/dev/null 2>&1";
	$sh[]="{$GLOBALS["echobin"]} \"Installing Iptables rules\"";
	$sh[]=$php." ".__FILE__." --iptables >/dev/null 2>&1";
	$sh[]="{$GLOBALS["echobin"]} \"Running builded script\"";
	$sh[]="/etc/init.d/iptables-transparent start";
	$sh[]="\tif [ -e \"/bin/iptables-parents.sh\" ]; then";
	$sh[]="\t\t{$GLOBALS["echobin"]} \"FireWall: restart Firewall (Proxy parent) rules\"";
	$sh[]="\t\t/bin/iptables-parents.sh || true";
	$sh[]="\tfi";	
	
	
	$sh[]=";;";
	
	$sh[]="  restart)";
	$sh[]="{$GLOBALS["echobin"]} \"Removing Iptables rules\"";
	$sh[]=$php." ".__FILE__." --iptables-delete >/dev/null 2>&1";
	$sh[]="{$GLOBALS["echobin"]} \"Installing Iptables rules\"";
	$sh[]=$php." ".__FILE__." --iptables >/dev/null 2>&1";
	$sh[]="{$GLOBALS["echobin"]} \"Running builded script\"";
	$sh[]="/etc/init.d/iptables-transparent start";
	$sh[]="\tif [ -e \"/bin/iptables-parents.sh\" ]; then";
	$sh[]="\t\t{$GLOBALS["echobin"]} \"FireWall: restart Firewall (Proxy parent) rules\"";
	$sh[]="\t\t/bin/iptables-parents.sh || true";
	$sh[]="\tfi";
	$sh[]="\t$php ".dirname(__FILE__)."/exec.mikrotik.php >/dev/null 2>&1";
	$sh[]="{$GLOBALS["echobin"]} \"Restarting Iptables rules success\"";
	$sh[]=";;";
	
	
	$sh[]="*)";
	$sh[]=" echo \"Usage: $0 {start ,restart,configure or stop only}\"";
	$sh[]="exit 1";
	$sh[]=";;";
	$sh[]="esac";
	$sh[]="exit 0\n";
	return @implode("\n", $sh);
	
	
}

function script_startfile(){
	$unix=new unix();
	$sock=new sockets();
	$GLOBALS["echobin"]=$unix->find_program("echo");
	$php=$unix->LOCATE_PHP5_BIN();
	$sh=array();
	
	$SquidWCCPEnabled=$sock->GET_INFO("SquidWCCPEnabled");
	$hasProxyTransparent=$sock->GET_INFO("hasProxyTransparent");
	$EnableArticaHotSpot=$sock->GET_INFO("EnableArticaHotSpot");
	if(!is_numeric($SquidWCCPEnabled)){$SquidWCCPEnabled=0;}
	if(!is_numeric($hasProxyTransparent)){$hasProxyTransparent=0;}
	if(!is_numeric($EnableArticaHotSpot)){$EnableArticaHotSpot=0;}

	
	
	$sh[]="#!/bin/sh -e";
	$sh[]="### BEGIN INIT INFO";
	$sh[]="# Builded on ". date("Y-m-d H:i:s");
	$sh[]="# Provides:          tproxy";
	$sh[]="# Required-Start:    \$local_fs";
	$sh[]="# Required-Stop:     \$local_fs";
	$sh[]="# Should-Start:		";
	$sh[]="# Should-Stop:		";
	$sh[]="# Default-Start:     S";
	$sh[]="# Default-Stop:      0 6";
	$sh[]="# Short-Description: start and stop the tproxy";
	$sh[]="# Description:       Artica tproxy service Raise transparent proxy";
	$sh[]="### END INIT INFO";
	$sh[]="case \"\$1\" in";
	$sh[]="start)";	
	$sh[]="{$GLOBALS["echobin"]} \"Removing Iptables rules\"";
	$sh[]=$php." ".dirname(__FILE__)."/exec.squid.transparent.delete.php >/dev/null || true";
	$sh[]="{$GLOBALS["echobin"]} \"hasProxyTransparent key ($hasProxyTransparent)...\"";
	$sh[]="{$GLOBALS["echobin"]} \"SquidWCCPEnabled key ($SquidWCCPEnabled)...\"";
	$sh[]="{$GLOBALS["echobin"]} \"EnableArticaHotSpot key ($EnableArticaHotSpot)...\"";
	$sh[]=MikrotikTransparent();
	
	return @implode("\n", $sh);
}

function iptables_delete_all(){
$unix=new unix();
$iptables_save=$unix->find_program("iptables-save");
$iptables_restore=$unix->find_program("iptables-restore");
system("$iptables_save > /etc/artica-postfix/iptables.conf");
$data=file_get_contents("/etc/artica-postfix/iptables.conf");
$datas=explode("\n",$data);
$pattern="#.+?ArticaSquidTransparent#";	
$d=0;
while (list ($num, $ligne) = each ($datas) ){
		if($ligne==null){continue;}
		if(preg_match($pattern,$ligne)){$d++;continue;}
		$conf=$conf . $ligne."\n";
		}
	file_put_contents("/etc/artica-postfix/iptables.new.conf",$conf);
	system("$iptables_restore < /etc/artica-postfix/iptables.new.conf");
	echo "Starting......: ".date("H:i:s")." Squid Check Transparent mode: removing $d iptables rule(s) done...\n";	
}


function iptables_rules(){
	
	$squid=new squidbee();
	$unix=new unix();
	$sock=new sockets();
	$SquidBinIpaddr=trim($sock->GET_INFO("SquidBinIpaddr"));
	if($SquidBinIpaddr==null){$SquidBinIpaddr="0.0.0.0";}
	$UseTProxyMode=intval($sock->GET_INFO("UseTProxyMode"));
	$EnableArticaHotSpot=$sock->GET_INFO("EnableArticaHotSpot");
	$ssl_port=$squid->get_ssl_port();
	if(!is_numeric($squid->listen_port)){$squid->listen_port=3128;}
	$listen_ssl_port=$squid->listen_port+1;
	$SSL_BUMP=$squid->SSL_BUMP;
	$iptables=$unix->find_program("iptables");
	$GLOBALS["IPTABLESBIN"]=$iptables;
	$sysctl=$unix->find_program("sysctl");
	$ips=$unix->ifconfig_interfaces_list();
	$KernelSendRedirects=$sock->GET_INFO("KernelSendRedirects");
	if(!is_numeric($KernelSendRedirects)){$KernelSendRedirects=1;}
	if(!is_numeric($EnableArticaHotSpot)){$EnableArticaHotSpot=0;}	
	$EnableNatProxy=intval($sock->GET_INFO("EnableNatProxy"));
	$NatProxyServer=$sock->GET_INFO("NatProxyServer");
	$NatProxyPort=intval($sock->GET_INFO("NatProxyPort"));
	
	echo "Starting......: ".date("H:i:s")." Squid iptables Rules: UseTProxyMode.....:$UseTProxyMode\n";
	
	if($UseTProxyMode==1){
		disable_transparent();
		iptables_wccp_delete_all();
		$php=$unix->LOCATE_PHP5_BIN();
		echo "Starting......: ".date("H:i:s")." Squid running Tproxy Mode\n";
		system("$php /usr/share/artica-postfix/exec.squid.tproxy.php");
		echo "Starting......: ".date("H:i:s")." Squid running TProxy script...\n";
		shell_exec("/etc/init.d/iptables-transparent start");
		return;
	
	}
	
	
	
	
	$php=$unix->LOCATE_PHP5_BIN();
	$GLOBALS["echobin"]=$unix->find_program("echo");
	$MARKLOG="-m comment --comment \"ArticaSquidTransparent\"";
	$sh=array();
	$sh[]=script_startfile();
	
	
	build_progress("Creating rules...",35);
	$sh[]="{$GLOBALS["echobin"]} \"Patching kernel\"";
	$sh[]="$sysctl -w net.ipv4.ip_forward=1 2>&1";
	$sh[]="$sysctl -w net.ipv4.conf.default.send_redirects=$KernelSendRedirects 2>&1";
	$sh[]="$sysctl -w net.ipv4.conf.all.send_redirects=$KernelSendRedirects 2>&1";
	
	if(is_file("/proc/sys/net/ipv4/conf/eth0/send_redirects")){
		$sh[]="$sysctl -w net.ipv4.conf.eth0.send_redirects=$KernelSendRedirects 2>&1";
	}
	
	
	$sh[]="$php /usr/share/artica-postfix/exec.squid.transparent.delete.php || true";
	$sh[]=ebtables_rules();
	$sh[]="{$GLOBALS["echobin"]} \"Enable rules\"";
	$sh[]="$iptables -A FORWARD -m state --state ESTABLISHED,RELATED -j ACCEPT $MARKLOG  || true";
	
	
	
	if(!$GLOBALS["EBTABLES"]){
		$sh[]="{$GLOBALS["echobin"]} \"Add internetT dictionary\"";
		$sh[]="$iptables -t mangle -N internetT $MARKLOG  || true";
		$sh[]="{$GLOBALS["echobin"]} \"Add internsslT dictionary\"";
		$sh[]="$iptables -t mangle -N internsslT $MARKLOG  || true";
		$sh[]="{$GLOBALS["echobin"]} \"Add mangle MARK 97 for internsslT\"";
		$sh[]="$iptables -t mangle -A internsslT -j MARK --set-mark 97 $MARKLOG  || true";
		$sh[]="{$GLOBALS["echobin"]} \"Add mangle MARK 96 for internetT\"";
		$sh[]="$iptables -t mangle -A internetT -j MARK --set-mark 96 $MARKLOG  || true";
	}
	$sh[]="$iptables -t nat -A OUTPUT --match owner --uid-owner squid -p tcp -j ACCEPT $MARKLOG";
	$sh[]="$iptables -t nat -A OUTPUT --match owner --uid-owner squid -p tcp -j ACCEPT $MARKLOG";
	$sh[]="$iptables -t nat -I POSTROUTING -p tcp --dport 80 -j MASQUERADE $MARKLOG";
	$sh[]="$iptables -t nat -I POSTROUTING -p tcp --dport 443 -j MASQUERADE $MARKLOG";
	
	$sql="SELECT *  FROM transparent_networks WHERE `enabled`=1 ORDER BY zOrder";
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL($sql);
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$transparent=$ligne["transparent"];
		$block=$ligne["block"];
		if($ligne["destination_port"]==0){
			$ligne["destination_port"]=80;
			if($ligne["ssl"]==1){$ligne["destination_port"]==443;}
		}		
		
		if($ligne["destination_port"]==443){$ligne["ssl"]=1;}
		if($ligne["destination_port"]==80){$ligne["ssl"]=0;}
		if($block==1){$sh[]=pattern_to_www($ligne);continue;}
		if($transparent==0){$sh[]=pattern_to_direct($ligne);continue;}
		$sh[]=pattern_to_proxy($ligne,$squid->listen_port,$ssl_port);
	}
	
	
	
	
	if($EnableNatProxy==1){
		$sh[]="$iptables -t nat -I PREROUTING -s $NatProxyServer/32 -p tcp -m tcp --dport 80 $MARKLOG -j RETURN";
		$sh[]="$iptables -t nat -I PREROUTING -s $NatProxyServer/32 -p tcp -m tcp --dport 443 $MARKLOG -j RETURN";
	}
	$sh[]=ChildsProxys();
	$sh[]=script_endfile();
	build_progress("Writing script...",45);
	@file_put_contents("/etc/init.d/iptables-transparent", @implode("\n", $sh));
	build_progress("Installing script...",48);
	script_install();
	
}

function GetSourcesAndDestinations($ligne){
	
	
	
	$PortsZ=src_groups($ligne,"port");
	if($GLOBALS["VERBOSE"]){echo "GetSourcesAndDestinations ID:{$ligne["ID"]} {$ligne["pattern"]} -> {$ligne["destination"]} - {$ligne["eth"]} - {$ligne["destination_port"]} Ports Number=". count($PortsZ)."\n";}
	
	if(count($PortsZ)==0){
		$PortsZ[$ligne["destination_port"]]=$ligne["destination_port"];
	}
	
	$AVAILABLE_MACROS["google"]=true;
	$AVAILABLE_MACROS["teamviewer"]=true;
	$AVAILABLE_MACROS["office365"]=true;
	$AVAILABLE_MACROS["skype"]=true;
	$AVAILABLE_MACROS["dropbox"]=true;
	$AVAILABLE_MACROS["whatsapp"]=true;
	$AVAILABLE_MACROS["youtube"]=true;
	
	

	while (list ($Destport, $b) = each ($PortsZ) ){
		
		if(isset($AVAILABLE_MACROS[trim(strtolower($ligne["destination"]))])){
			$destinations=destinations_macro(trim(strtolower($ligne["destination"])),$destinations,$ligne["eth"],$Destport,1);
			continue;
		}
		
		$destinations[pattern_item($ligne["destination"],$ligne["eth"],$Destport,1)]=true;
	}
	$sources[pattern_item($ligne["pattern"],$ligne["eth"],0,0)]=true;
	
	
	$D=src_groups($ligne,"dst");
	if(count($D)>0){
		unset($destinations);
		while (list ($linZ, $b) = each ($D) ){
			reset($PortsZ);
			while (list ($Destport, $b) = each ($PortsZ) ){
				$destinations[pattern_item($linZ,$ligne["eth"],$Destport,1)]=true;
			}
		}
	}
	
	$S=src_groups($ligne,"src");
	if(count($S)>0){
		unset($sources);
		while (list ($linZ, $b) = each ($D) ){
			$sources[pattern_item($linZ,$ligne["eth"],0,0)]=true;
		}
	}
	
	$S=src_groups($ligne,"arp");
	if(count($S)>0){
		unset($sources);
		while (list ($linZ, $b) = each ($D) ){
			$sources[pattern_item($linZ,$ligne["eth"],0,0)]=true;
		}
	}

	return array($sources,$destinations);
	
}


function pattern_to_www($ligne){
	$FUNCTION=__FUNCTION__;
	$unix=new unix();
	$ipClass=new IP();
	$BLOCK=false;
	$MARKLOG="-m comment --comment \"ArticaSquidTransparent\"";
	if($ligne["remote_proxy"]=="*"){$ligne["remote_proxy"]=null;}
	
	$sock=new sockets();
	$EnableNatProxy=intval($sock->GET_INFO("EnableNatProxy"));
	$NatProxyServer=$sock->GET_INFO("NatProxyServer");
	$NatProxyPort=intval($sock->GET_INFO("NatProxyPort"));
	if($EnableNatProxy==1){$ligne["remote_proxy"]="$NatProxyServer:$NatProxyPort"; }
	
	
	if($ligne["remote_proxy"]<>null){
		if(!preg_match("#(.+?):([0-9]+)#", $ligne["remote_proxy"],$re)){
			if($ligne["ssl"]==1){
				$ligne["remote_proxy"]="{$ligne["remote_proxy"]}:443";}
			else{
				$ligne["remote_proxy"]="{$ligne["remote_proxy"]}:80";
				}
		}else{
			if(!$ipClass->isIPAddress($re[1])){
				$ligne["remote_proxy"]=gethostbyname($re[1]).":{$re[2]}";
			}
			
		}
		$JREDIRECT="DNAT --to-destination {$ligne["remote_proxy"]}";
	}else{
		$BLOCK=true;
		$JREDIRECT="RETURN";
		//iptables  -t nat -I POSTROUTING  -d 93.104.193.187 -p tcp --dport 443 -j RETURN
	}
	
	
	
	$iptables=$unix->find_program("iptables");
	$ssl=$ligne["ssl"];
	$ERROR=null;
	$internet="internetT";
	$mark=96;
	$not=null;
	$suffixTables=$MARKLOG;
	if($ssl==1){ $internet="internsslT"; $mark=97; }
	if($ligne["isnot"]==1){$not=" !";}
	$f[]="#";
	
	
	$SourcesAndDestinations=GetSourcesAndDestinations($ligne);
	$SOURCES=$SourcesAndDestinations[0];
	$DESTINATIONS=$SourcesAndDestinations[1];

	
	$f[]="{$GLOBALS["echobin"]} \"Rule:{$ligne["ID"]} transfert to web server {$ligne["remote_proxy"]} connection from {$ligne["pattern"]} -> {$ligne["destination"]}:{$ligne["destination_port"]} $ERROR\"";
	while (list ($source, $none) = each ($SOURCES) ){
		reset($DESTINATIONS);
		if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: $FUNCTION SOURCE = \"$source\"\n";}
		while (list ($destination, $none) = each ($DESTINATIONS) ){	
			if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: $FUNCTION DESTINATION = \"$destination\"\n";}
			if(is_numeric($source)){continue;}
			if(is_numeric($destination)){continue;}
			if(!$BLOCK){
				if(!$GLOBALS["EBTABLES"]){
					$f[]=Output_iptables("$iptables -t mangle -I PREROUTING $not$source-p tcp -m tcp $not$destination $MARKLOG || true");
				}
			}
					
			if(!$GLOBALS["EBTABLES"]){
				$f[]=Output_iptables("$iptables -t nat -I PREROUTING $not$source-p tcp -m tcp $not$destination -j $JREDIRECT $MARKLOG || true");
			}
			if($BLOCK){$f[]=Output_iptables("$iptables -I FORWARD $not$source-p tcp -m tcp $not$destination -j REJECT $MARKLOG || true");}
		}
	}
	
	return @implode("\n", $f);
}




function pattern_to_proxy($ligne,$squid_http_port,$squid_ssl_port){
	$FUNCTION=__FUNCTION__;
	$MARKLOG="-m comment --comment \"ArticaSquidTransparent\"";
	if($ligne["remote_proxy"]=="*"){$ligne["remote_proxy"]=null;}
	$unix=new unix();
	$sock=new sockets();
	$iptables=$unix->find_program("iptables");
	$ssl=$ligne["ssl"];
	$ERROR=null;
	$internet="internetT";
	$mark=96;
	$not=null;
	$suffixTables=$MARKLOG;
	$JREDIRECT="REDIRECT --to-port $squid_http_port";
	
	
	$EnableNatProxy=intval($sock->GET_INFO("EnableNatProxy"));
	
	if($EnableNatProxy==1){
		$NatProxyServer=$sock->GET_INFO("NatProxyServer");
		$NatProxyPort=intval($sock->GET_INFO("NatProxyPort"));
		$f[]="# ".__LINE__." EnableNatProxy [$EnableNatProxy]: \"$NatProxyServer:$NatProxyPort\"";
		$ligne["remote_proxy"]="$NatProxyServer:$NatProxyPort"; }
	
	
	
	if($ssl==1){
		$JREDIRECT="REDIRECT --to-port $squid_ssl_port";
		$squid_port=$squid_ssl_port;
		$internet="internsslT";
		$mark=97;
	}
	
	if(preg_match("#^(.+?):(.+)#", $ligne["remote_proxy"])){
		$JREDIRECT="DNAT --to-destination {$ligne["remote_proxy"]}";
	}
	
	
	$EXTMARK="-m mark --mark $mark";
	if($GLOBALS["EBTABLES"]){
		$internet=null;
		$EXTMARK=null;
	}
	
	

	if($ligne["isnot"]==1){$not=" !";}
	$destination=pattern_item($ligne["destination"],$ligne["eth"],$ligne["destination_port"],1);
	$source=pattern_item($ligne["pattern"],$ligne["eth"],0,0);
	$f[]="#";
	
	$SourcesAndDestinations=GetSourcesAndDestinations($ligne);
	$SOURCES=$SourcesAndDestinations[0];
	$DESTINATIONS=$SourcesAndDestinations[1];
	

	
	$f[]="{$GLOBALS["echobin"]} \"Rule:{$ligne["ID"]} transfert directly to Internet {$ligne["pattern"]} -> {$ligne["destination"]} $ERROR\"";
	
	while (list ($source, $none) = each ($SOURCES) ){
		reset($DESTINATIONS);
		if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: $FUNCTION SOURCE = \"$source\"\n";}
		while (list ($destination, $none) = each ($DESTINATIONS) ){	
			if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: $FUNCTION DESTINATION = \"$destination\"\n";}
			if(is_numeric($source)){continue;}
			if(is_numeric($destination)){continue;}
			if(!$GLOBALS["EBTABLES"]){$f[]=Output_iptables("$iptables -t mangle -A PREROUTING $not$source-p tcp -m tcp $not$destination -j $internet $MARKLOG || true");}
			$f[]=Output_iptables("$iptables -t nat -A PREROUTING $not$source-p tcp $EXTMARK -m tcp $not$destination -j $JREDIRECT $MARKLOG || true");
		}
	}
	

	return @implode("\n", $f);	
	
}

function Output_iptables($line){
	$trace=@debug_backtrace();
	if(isset($trace[1])){
		$called="in ". basename($trace[1]["file"])." function {$trace[1]["function"]}() line {$trace[1]["line"]}";
	}
	
	if($GLOBALS["VERBOSE"]){echo "$line \n";}
	//echo "$line - $called\n";
	if(preg_match("#^\##", $line)){return $line;}
	$lineT=$line;
	$lineT=str_replace('"', "'", $lineT);
	return "{$GLOBALS["echobin"]} \"$lineT\"\n$line";
	
}


function pattern_to_direct($ligne){
	$MARKLOG="-m comment --comment \"ArticaSquidTransparent\"";
	$unix=new unix();
	$not=null;
	$ERROR=null;
	$iptables=$unix->find_program("iptables");
	$ssl=$ligne["ssl"];
	$internet="internetT";
	$mark=96;
	$suffixTables=$MARKLOG;
	
	if($ssl==1){
		$internet="internsslT";
		$mark=97;
	}
	
	
	$SourcesAndDestinations=GetSourcesAndDestinations($ligne);
	$SOURCES=$SourcesAndDestinations[0];
	$DESTINATIONS=$SourcesAndDestinations[1];
	

	
	$f[]="{$GLOBALS["echobin"]} \"Rule:{$ligne["ID"]} [".__FUNCTION__."/".__LINE__."] transfert directly to Internet {$ligne["pattern"]} -> {$ligne["destination"]} $ERROR\"";
	while (list ($source, $none) = each ($SOURCES) ){
		
		reset($DESTINATIONS);
		while (list ($destination, $none) = each ($DESTINATIONS) ){
			
			if(is_numeric($source)){continue;}
			if(is_numeric($destination)){continue;}		
			if(trim($source)==null){if(trim($destination)==null){continue; } }	
			if(!$GLOBALS["EBTABLES"]){$f[]=Output_iptables("$iptables -t mangle -I PREROUTING $not$source-p tcp -m tcp $not$destination -j RETURN $MARKLOG || true");}
			$f[]=Output_iptables("$iptables -t nat -I PREROUTING $not$source-p tcp -m tcp $not$destination -j RETURN $MARKLOG || true");
			
		}
	}

	return @implode("\n", $f);
	
}

function ChildsProxys(){
	$MARKLOG="-m comment --comment \"ArticaSquidTransparent\"";
	$unix=new unix();
	$not=null;
	$ERROR=null;
	$iptables=$unix->find_program("iptables");
	$sock=new sockets();
	$SquidAsMasterPeer=intval($sock->GET_INFO("SquidAsMasterPeer"));
	if($SquidAsMasterPeer==0){return ;}
	$sql="SELECT ipsrc FROM squid_balancers WHERE enabled=1";
	$q=new mysql();
	$results = $q->QUERY_SQL($sql,"artica_backup");

	while ($ligne = mysql_fetch_assoc($results)) {
		if($ligne["ipsrc"]==null){continue;}
		if(!$GLOBALS["EBTABLES"]){$f[]=Output_iptables("$iptables -t mangle -I PREROUTING -s {$ligne["ipsrc"]} -p tcp -m tcp --dport 443 -j RETURN $MARKLOG || true");}
		if(!$GLOBALS["EBTABLES"]){$f[]=Output_iptables("$iptables -t mangle -I PREROUTING -s {$ligne["ipsrc"]} -p tcp -m tcp --dport 80 -j RETURN $MARKLOG || true");}
		$f[]=Output_iptables("$iptables -t nat -I PREROUTING -s {$ligne["ipsrc"]} -p tcp -m tcp --dport 443 -j RETURN $MARKLOG || true");
		$f[]=Output_iptables("$iptables -t nat -I PREROUTING -s {$ligne["ipsrc"]} -p tcp -m tcp --dport 80 -j RETURN $MARKLOG || true");
	}
	
	return @implode("\n", $f);
	
	
	
	
}


function destinations_macro($macro,$destinations=array(),$eth,$port=0,$destinationProto=0){
	$nic=new system_nic();
	$interface=null;
	$portText=null;
	$trace=@debug_backtrace();
	if(isset($trace[1])){
		$called="in ". basename($trace[1]["file"])." function {$trace[1]["function"]}() line {$trace[1]["line"]}";
	}
	
	if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: Destination: ". count($destinations)." items eth=$eth port=$port destinationProto=$destinationProto - $called\n";}
	
	if(is_numeric($eth)){$eth=null;}	
	if($port>0){
		$portText="--srcport $port";
		if($destinationProto==1){
			$portText=" --dport $port";
		}
	}
	$pdest="-s ";
	if($eth<>null){
		if($destinationProto==0){
			$interface=" -m physdev --physdev-in $eth ";
		}
	}
	if($destinationProto==1){
		$pdest="-d ";
	}
	
	include_once(dirname(__FILE__)."/ressources/class.products-ip-ranges.inc");
	
	$nets=new products_ip_ranges();
	if($macro=="google"){
		$mains=$nets->google_ssl();
	}
	if($macro=="teamviewer"){
		$mains=$nets->teamviewer_networks();
	}
	
	if($macro=="whatsapp"){
		$mains=$nets->whatsapp_networks();
		
	}

	if($macro=="dropbox"){
		$mains=$nets->dropbox_networks();
	}
	

	if($macro=="skype"){
		$mains=$nets->skype_networks();
	}	
	
	if($macro=="office365"){
		$mains=$nets->office365_networks();
		$mains=$nets->office365_domains($mains);
	}
	if($macro=="youtube"){
		$mains=$nets->youtube_networks();
		
	}	


	
	
	
	
	if($destinationProto==1){
		$rangeText="--dst-range";
	}else{
		$rangeText="--src-range";
	}
	
	
	$ipClass=new IP();
	while (list ($b,$www) = each ($mains) ){ 
		
		if($ipClass->IsARange($www)){
			$destinations["$interface-m iprange $rangeText '$www'$portText"]=true;
			continue;
		}
		
		if($ipClass->IsACDIR($www)){
			$destinations["$interface$pdest$www$portText"]=true;
			continue;
		}
		
		if(!$ipClass->isValid($www)){
			$ipaddr=gethostbyname($www);
		}
		
		if(!$ipClass->isValid($ipaddr)){continue;}
		$destinations["$interface$pdest$ipaddr$portText"]=true;
	}
	
	
	
	
return $destinations;
	
	
}

function pattern_item($destination,$eth=null,$port=0,$destinationProto=0){
	$nic=new system_nic();
	$trace=@debug_backtrace();
	$interface=null;
	$portText=null;
	if(isset($trace[1])){
		$called="in ". basename($trace[1]["file"])." function {$trace[1]["function"]}() line {$trace[1]["line"]}";
	}
	
	if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: Destination: $destination eth=$eth port=$port destinationProto=$destinationProto - $called\n";}
	
	if(is_numeric($eth)){$eth=null;}
	if($port>0){
		$portText="--srcport $port";
		if($destinationProto==1){
			$portText=" --dport $port";
		}
	}
	
	$pdest="-s ";
	if($eth<>null){
		if($destinationProto==0){
			$interface=" -m physdev --physdev-in $eth ";
		}
	}
	
	if($destinationProto==1){
		$pdest="-d ";
	}
	
	if($destination=="*"){$destination="0.0.0.0/0";}
	if(trim($destination)==null){
		if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: Destination: return *** $interface$portText ***\n";}
		return "$interface$portText";
	}
	
	if(preg_match("#[0-9\.]+-[0-9\.]+#", $destination)){
		
		if($destinationProto==1){
			if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: Destination: return *** $interface-m iprange --dst-range '$destination'$portText  ***\n";}
			return "$interface-m iprange --dst-range '$destination'$portText ";
		}
		
		if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: Destination: return *** $interface-m iprange --src-range '$destination'$portText   ***\n";}
		return "$interface-m iprange --src-range '$destination'$portText ";
		
	}
	
	$ipClass=new IP();
	
	if($ipClass->IsvalidMAC($destination)){
		if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: Destination: return *** $interface-m mac --mac-source $destination$portText   ***\n";}
		return "$interface-m mac --mac-source $destination$portText ";
	}	
	
	
	if($ipClass->isIPAddressOrRange($destination)){
		return "$interface$pdest$destination$portText ";
	}
	

	
}

function src_groups($ligne,$GroupType){
	
	//["src"]="{addr}";
	//["dst"]="{dst}";
	//["arp"]="{ComputerMacAddress}";
	
	$ID=$ligne["ID"];
	$q=new mysql_squid_builder();
	$MARKLOG="-m comment --comment \"ArticaSquidTransparent\"";
	$f=array();
	if($q->COUNT_ROWS("transparent_networks_groups")==0){return array();}
	$sql="SELECT transparent_networks_groups.gpid,
	transparent_networks_groups.zmd5 as mkey,
	webfilters_sqgroups.* FROM transparent_networks_groups,webfilters_sqgroups
	WHERE transparent_networks_groups.gpid=webfilters_sqgroups.ID
	AND transparent_networks_groups.ruleid=$ID
	AND webfilters_sqgroups.enabled=1
	AND transparent_networks_groups.enabled=1
	AND webfilters_sqgroups.GroupType='$GroupType'
	";
	$results=$q->QUERY_SQL($sql);	
	if(mysql_num_rows($results)==0){
		if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: src_groups:$GroupType No defined group\n";}
		return array();}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$items=src_items($ligne["gpid"],$ligne["GroupType"]);
		if(!is_array($items)){
			if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: src_groups:{$ligne["gpid"]} No item\n";}
			continue;}
		while (list ($a, $b) = each ($items) ){ $f[$a]=true; }
		
	} 
	
	return $f;
	
}

function src_items($gpid,$GroupType){
	
	$q=new mysql_squid_builder();
	$sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
	if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: src_items:$gpid -> $GroupType Get items.\n";}
	$results=$q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		$pattern=trim($ligne["pattern"]);
		
		if($pattern==null){continue;}
		if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: src_items:$gpid -> $pattern item.\n";}
		$f[$pattern]=true;
		}
	
	return $f;
	
	
}

function restart_progress(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}: Already Artica task running PID $pid since {$time}mn\n";}
		build_progress("{failed}",110);
		return;
	}
	
	$GLOBALS["OUTPUT"]=true;
	$GLOBALS["PROGRESS"]=true;
	
	@file_put_contents($pidfile, getmypid());
	$sock=new sockets();
	$SquidWCCPEnabled=$sock->GET_INFO("SquidWCCPEnabled");
	$hasProxyTransparent=$sock->GET_INFO("hasProxyTransparent");
	$EnableArticaHotSpot=$sock->GET_INFO("EnableArticaHotSpot");
	$EnableTransparent27=intval($sock->GET_INFO("EnableTransparent27"));
	if(!is_numeric($SquidWCCPEnabled)){$SquidWCCPEnabled=0;}
	if(!is_numeric($hasProxyTransparent)){$hasProxyTransparent=0;}
	if(!is_numeric($EnableArticaHotSpot)){$EnableArticaHotSpot=0;}
	echo "Starting......: ".date("H:i:s")." Squid Check hasProxyTransparent key ($hasProxyTransparent)...\n";
	echo "Starting......: ".date("H:i:s")." Squid Check SquidWCCPEnabled key ($SquidWCCPEnabled)...\n";
	echo "Starting......: ".date("H:i:s")." Squid Check EnableArticaHotSpot key ($EnableArticaHotSpot)...\n";
	echo "Starting......: ".date("H:i:s")." Squid Check EnableTransparent27 key ($EnableTransparent27)...\n";
	

	echo "Starting......: ".date("H:i:s")." Squid running TProxy script...\n";
	build_progress("{checking}",5);
	if($GLOBALS["PROGRESS"]){sleep(4);}
	
	build_progress("{building_firewall_rules}", 10);
	MikrotikTransparent();
	if($EnableArticaHotSpot==1){disable_transparent();iptables_wccp_delete_all();return;}
	if($EnableTransparent27==1){disable_transparent();iptables_wccp_delete_all();return;}
	if($SquidWCCPEnabled==1){enable_wccp();}else{iptables_wccp_delete_all();}
	if($hasProxyTransparent==1){enable_transparent();}else{disable_transparent();}
	
	build_progress("Executing firewall script",55);
	system("/etc/init.d/iptables-transparent start");
	build_progress("Executing firewall script {done}",100);
	if($GLOBALS["PROGRESS"]){sleep(5);}
	
}
function build_progress($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.transparent.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
	if($GLOBALS["PROGRESS"]){sleep(1);}

}

function MikrotikTransparent(){
	$MARKLOG="-m comment --comment \"ArticaMikroTikTransparent\"";

	$sock=new sockets();
	$unix=new unix();
	$MikrotikTransparent=intval($sock->GET_INFO('MikrotikTransparent'));
	
	if($MikrotikTransparent==0){
		$sh[]="# MikroTik disabled";
		return @implode("\n", $sh);
	}
	
	build_progress("MikroTik {enabled}", 10);
	
	$MikrotikHTTPSquidPort=intval($sock->GET_INFO('MikrotikHTTPSquidPort'));
	$MikrotikVirtualIP=$sock->GET_INFO('MikrotikVirtualIP');
	$MikrotikNetMask=$sock->GET_INFO('MikrotikNetMask');
	$MikrotikIPAddr=$sock->GET_INFO('MikrotikIPAddr');
	$MikrotikLAN=$sock->GET_INFO('MikrotikLAN');
	$MikrotikLocalInterface=$sock->GET_INFO('MikrotikLocalInterface');
	if($MikrotikLocalInterface==null){$MikrotikLocalInterface="eth0";}
	$MikrotikSSLTransparent=intval($sock->GET_INFO("MikrotikSSLTransparent"));
	$MikrotikHTTPSSquidPort=intval($sock->GET_INFO("MikrotikHTTPSSquidPort"));
	
	$ifconfig=$unix->find_program("ifconfig");
	$route=$unix->find_program("route");
	$iptables=$unix->find_program("iptables");
	$php=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");

	build_progress("MikroTik $MikrotikVirtualIP netmask $MikrotikNetMask", 15);
	
	$cmd="$ifconfig $MikrotikLocalInterface:mikrotik $MikrotikVirtualIP netmask $MikrotikNetMask up";
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
	
	$sh[]="$php /usr/share/artica-postfix/exec.squid.transparent.delete.php --mikrotik || true";
	$sh[]=$cmd." || true";
	$sh[]="$sysctl -w net.ipv4.ip_forward=1 2>&1";
	$sh[]="$route add default gateway $MikrotikIPAddr dev $MikrotikLocalInterface:mikrotik || true";
	$JREDIRECT="-j REDIRECT --to-port $MikrotikHTTPSquidPort";
	$JREDIRECT_SSL="-j REDIRECT --to-port $MikrotikHTTPSSquidPort";
	
	
	$MikrotikLANs=explode("\n",$MikrotikLAN);
	while (list ($num, $val) = each ($MikrotikLANs) ){
		$val=trim($val);
		if($val==null){continue;}
		if(substr($val, 0,1)=="!"){continue;}
		$sh[]="$iptables -A PREROUTING -t nat -p tcp -s $val -d 0/0 --dport 80 $MARKLOG $JREDIRECT || true";
		if($MikrotikSSLTransparent==1){
			$sh[]="$iptables -A PREROUTING -t nat -p tcp -s $val -d 0/0 --dport 443 $MARKLOG $JREDIRECT_SSL || true";
		}
	
	}
	
	
	
	$sh[]="$iptables -A INPUT -p tcp -s 0.0.0.0/0 -d $MikrotikVirtualIP -m state --state NEW,ESTABLISHED $MARKLOG -j ACCEPT || true";
	$sh[]="$iptables -A OUTPUT -p tcp -s $MikrotikVirtualIP --sport $MikrotikHTTPSquidPort -d 0.0.0.0/0 -m state --state ESTABLISHED $MARKLOG -j ACCEPT || true";
	
	if($MikrotikSSLTransparent==1){
		$sh[]="$iptables -A OUTPUT -p tcp -s $MikrotikVirtualIP --sport $MikrotikHTTPSSquidPort -d 0.0.0.0/0 -m state --state ESTABLISHED $MARKLOG -j ACCEPT || true";
	}
	
	build_progress("MikroTik {done}", 15);
	return @implode("\n", $sh);
	
}
function MikrotikRemoveIpaddr(){
	$unix=new unix();
	$ip=$unix->find_program("ip");
	exec("$ip addr show 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(!preg_match("#inet\s+([0-9\.]+)\/([0-9]+).*?scope global\s+(.+?):mikrotik#", $ligne,$re)){continue;}
		echo "Starting......: ".date("H:i:s")." Squid Check MikroTik mode: removing {$re[1]}/{$re[2]} interface\n";
		shell_exec("$ip addr del {$re[1]}/{$re[2]} dev {$re[3]}");
		break;
		
	}
	
	
}


function MikrotikRemoveIptables(){
	$unix=new unix();
	$iptables_save=$unix->find_program("iptables-save");
	$iptables_restore=$unix->find_program("iptables-restore");
	
	system("$iptables_save > /etc/artica-postfix/iptables.conf");
	$data=file_get_contents("/etc/artica-postfix/iptables.conf");
	$datas=explode("\n",$data);
	$pattern="#.+?ArticaMikroTikTransparent#";
	$d=0;
	while (list ($num, $ligne) = each ($datas) ){
		if($ligne==null){continue;}
		if(preg_match($pattern,$ligne)){$d++;continue;}
		$conf=$conf . $ligne."\n";
	}
	file_put_contents("/etc/artica-postfix/iptables.new.conf",$conf);
	system("$iptables_restore < /etc/artica-postfix/iptables.new.conf");
	build_progress("MikroTik remove $d iptables rule(s)", 11);
	
	echo "Starting......: ".date("H:i:s")." Squid Check MikroTik mode: removing $d iptables rule(s) done...\n";
	
	
}

function MYSQL_AS_PROXY_TRANSPARENT(){
	if(isset($GLOBALS["MYSQL_AS_PROXY_TRANSPARENT"])){return $GLOBALS["MYSQL_AS_PROXY_TRANSPARENT"];}
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM proxy_ports WHERE enabled=1 AND transparent=1";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){
		if(is_file("/etc/artica-postfix/MYSQL_AS_PROXY_TRANSPARENT")){
			$GLOBALS["MYSQL_AS_PROXY_TRANSPARENT"]=intval(@file_get_contents("/etc/artica-postfix/MYSQL_AS_PROXY_TRANSPARENT"));
			
		}
		
	}
	
	
	
	if(mysql_num_rows($results)>0){
			$GLOBALS["MYSQL_AS_PROXY_TRANSPARENT"]=1;
			@file_put_contents("/etc/artica-postfix/MYSQL_AS_PROXY_TRANSPARENT",intval($GLOBALS["MYSQL_AS_PROXY_TRANSPARENT"]));
			return 1;
	}
	
	@file_put_contents("/etc/artica-postfix/MYSQL_AS_PROXY_TRANSPARENT",0);
	$GLOBALS["MYSQL_AS_PROXY_TRANSPARENT"]=0;
	return 0;
	
}





