#!/usr/bin/php
<?php
$GLOBALS["SERVICE_NAME"]="Local firewall";
$GLOBALS["PERIOD"]=null;
$GLOBALS["PROGRESS"]=false;
$GLOBALS["AS_ROOT"]=true;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--period=([0-9]+)([a-z])#", implode(" ",$argv),$re)){$GLOBALS["PERIOD"]=$re[1].$re[2];}
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/ressources/class.templates.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/framework/class.tcpip-parser.inc');
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.nics.inc');
include_once(dirname(__FILE__) . '/ressources/class.os.system.inc');
include_once(dirname(__FILE__) . '/ressources/class.squid.inc');
include_once(dirname(__FILE__) . '/ressources/class.firehol.inc');

if($argv[1]=="--reconfigure-progress"){$GLOBALS["PROGRESS"]=true;reconfigure_progress();exit;}
if($argv[1]=="--disable-progress"){$GLOBALS["PROGRESS"]=true;disable_progress();exit;}
if($argv[1]=="--enable-progress"){$GLOBALS["PROGRESS"]=true;enable_progress();exit;}



if($argv[1]=="--reconfigure"){$GLOBALS["PROGRESS"]=true;reconfigure();exit;}
if($argv[1]=="--build"){$GLOBALS["PROGRESS"]=true;reconfigure();exit;}
if($argv[1]=="--install-progress"){$GLOBALS["PROGRESS"]=true;install();exit;}
if($argv[1]=="--scan"){$GLOBALS["PROGRESS"]=true;scanservices();exit;}
if($argv[1]=="--stop"){$GLOBALS["PROGRESS"]=true;stop_service();exit;}
if($argv[1]=="--start"){$GLOBALS["PROGRESS"]=true;start_service();exit;}



build_init();


function build_init($noprogress=false){
	
	$unix=new unix();
	$sock=new sockets();
	$GLOBALS["echobin"]=$unix->find_program("echo");
	$php=$unix->LOCATE_PHP5_BIN();
	$FireHolEnable=intval($sock->GET_INFO("FireHolEnable"));
	$KERNEL_VERSION=$unix->KERNEL_VERSION();
	$modprobe=$unix->find_program("modprobe");
	$sh[]="#!/bin/sh -e";
	$sh[]="### BEGIN INIT INFO";
	$sh[]="# Builded on ". date("Y-m-d H:i:s");
	$sh[]="# Provides:          firehol";
	$sh[]="# Required-Start:    \$local_fs";
	$sh[]="# Required-Stop:     \$local_fs";
	$sh[]="# Should-Start:		";
	$sh[]="# Should-Stop:		";
	$sh[]="# Default-Start:     S";
	$sh[]="# Default-Stop:      0 6";
	$sh[]="# Short-Description: start and stop the firewall";
	$sh[]="# Description:       Firewall";
	$sh[]="### END INIT INFO";
	
	$sh[]="if [ ! -f \"/etc/artica-postfix/settings/Daemons/FireHolEnable\" ]; then";
	$sh[]="\techo 0 >/etc/artica-postfix/settings/Daemons/FireHolEnable || true";
	$sh[]="fi";
	$sh[]="";
	$sh[]="";
	$sh[]="if [ ! -f \"/etc/artica-postfix/settings/Daemons/EnableArticaHotSpot\" ]; then";
	$sh[]="\techo 0 >/etc/artica-postfix/settings/Daemons/EnableArticaHotSpot || true";
	$sh[]="fi";
	$sh[]="";
	$sh[]="";
	

	$sh[]="FireHolEnable=`cat /etc/artica-postfix/settings/Daemons/FireHolEnable`";
	$sh[]="EnableArticaHotSpot=`cat /etc/artica-postfix/settings/Daemons/EnableArticaHotSpot`";
	$sh[]="if [ \$EnableArticaHotSpot -eq 1 ]; then";
	$sh[]="\tFireHolEnable=0";
	$sh[]="fi";
	
	$sh[]="";
	$sh[]="";
	$sh[]="case \"\$1\" in";
	$sh[]="start)";
	$sh[]="\t{$GLOBALS["echobin"]} \"FireWall: FireHolEnable is '\$FireHolEnable'\"";
	$sh[]="\t{$GLOBALS["echobin"]} \"FireWall: EnableArticaHotSpot is '\$EnableArticaHotSpot'\"";
	$sh[]="if [ \$FireHolEnable -eq 0 ]; then";
		$sh[]="{$GLOBALS["echobin"]} \"FireWall: disabled, checking transparent rules.\"";
		$sh[]="if [ -f \"/usr/local/sbin/firehol\" ]; then";
			$sh[]="\t/usr/local/sbin/firehol stop";
		$sh[]="fi";
		$sh[]="\tif [ -e \"/etc/init.d/iptables-transparent\" ]; then";
			$sh[]="\t\t{$GLOBALS["echobin"]} \"FireWall: disabled, run Transparent rules\"";
			$sh[]="\t\t/etc/init.d/iptables-transparent start";
		$sh[]="\tfi";	
		$sh[]="\t$php /usr/share/artica-postfix/exec.secure.gateway.php";
		$sh[]="\tif [ -e \"/bin/artica-secure-gateway.sh\" ]; then";
		$sh[]="\t\t{$GLOBALS["echobin"]} \"FireWall: disabled, run Secure gateway rules\"";
		$sh[]="\t\t/bin/artica-secure-gateway.sh";
		$sh[]="\tfi";		
	
	
	
	$sh[]="fi";
	
	
	$sh[]="if [ \$FireHolEnable -eq 1 ]; then";
	
	if(is_file("/lib/modules/$KERNEL_VERSION/extra/xt_ndpi.ko")){
		$sh[]="#xt_ndpi installed";
		$sh[]="\t{$GLOBALS["echobin"]} \"FireWall: Loading xt_ndpi module.\"";
		$sh[]="$modprobe xt_ndpi || true";
	}else{
		$sh[]="#xt_ndpi not installed";
	}
	
	$sh[]="";
	
		$sh[]="\t{$GLOBALS["echobin"]} \"FireWall: Starting\"";
		$sh[]="if [ -f \"/usr/local/sbin/firehol\" ]; then";
		$sh[]="\t/usr/local/sbin/firehol start";
		$sh[]="fi";
		$sh[]="\t{$GLOBALS["echobin"]} \"FireWall: Started\"";
	$sh[]="fi";


	
	
	$sh[]="\tif [ -e \"/etc/init.d/proxy-wccp\" ]; then";
	$sh[]="\t\t{$GLOBALS["echobin"]} \"FireWall: Start WCCP rules\"";
	$sh[]="\t\t/etc/init.d/proxy-wccp verif || true";
	$sh[]="\tfi";
	
	$sh[]="\t\t{$GLOBALS["echobin"]} \"FireWall: Start MikrotiK rules\"";
	$sh[]="\t$php ".dirname(__FILE__)."/exec.mikrotik.php >/dev/null 2>&1";
	
	$sh[]="\tif [ -e \"/bin/suricata-fw.sh\" ]; then";
	$sh[]="\t\t{$GLOBALS["echobin"]} \"FireWall: Start IDS rules\"";
	$sh[]="\t\t/bin/suricata-fw.sh || true";
	$sh[]="\tfi";
	
	
	
	
	$sh[]="{$GLOBALS["echobin"]} \"FireWall: done\"";
	$sh[]=";;";
	$sh[]="  stop)";
	$sh[]="\t{$GLOBALS["echobin"]} \"FireWall: Stopping\"";
	$sh[]="if [ -f \"/usr/local/sbin/firehol\" ]; then";
	$sh[]="\t/usr/local/sbin/firehol stop";
	$sh[]="fi";
	$sh[]="if [ \$FireHolEnable -eq 1 ]; then";
		$sh[]="\tif [ -e \"/etc/init.d/iptables-transparent\" ]; then";
		$sh[]="\t\t{$GLOBALS["echobin"]} \"FireWall: disabled, down Transparent rules\"";
		$sh[]="\t\t/etc/init.d/iptables-transparent stop";
		$sh[]="\tfi";	
	$sh[]="fi";
	
	$sh[]="\tif [ -e \"/etc/init.d/iptables-statsapp\" ]; then";
	$sh[]="\t\t{$GLOBALS["echobin"]} \"FireWall: Stop Stats-Appliances rules\"";
	$sh[]="\t\t/etc/init.d/iptables-statsapp stop || true";
	$sh[]="\tfi";
	
	$sh[]="\tif [ -e \"/etc/init.d/proxy-wccp\" ]; then";
	$sh[]="\t\t{$GLOBALS["echobin"]} \"FireWall: Stop WCCP rules\"";
	$sh[]="\t\t/etc/init.d/proxy-wccp stop || true";
	$sh[]="\tfi";
	
	
	$sh[]="\t{$GLOBALS["echobin"]} \"FireWall: Stopped\"";
	$sh[]=";;";
	$sh[]="  reconfigure)";
	$sh[]="if [ \$FireHolEnable -eq 1 ]; then";
		$sh[]="\t{$GLOBALS["echobin"]} \"FireWall: Reconfiguring\"";
		$sh[]="\t$php ".__FILE__." --reconfigure >/dev/null 2>&1";
		$sh[]=" /usr/local/sbin/firehol condrestart";
	$sh[]="fi";
	
	$sh[]="if [ \$FireHolEnable -eq 0 ]; then";
		$sh[]="\t{$GLOBALS["echobin"]} \"FireWall: Reconfiguring Transparent rules\"";
		$sh[]="\t$php ".dirname(__FILE__)."/exec.squid.transparent.php >/dev/null 2>&1";
	$sh[]="fi";
	
	$sh[]="\t{$GLOBALS["echobin"]} \"FireWall: Reconfiguring Stats-Appliance rules\"";
	$sh[]="\t$php ".dirname(__FILE__)."/exec.iptables-stats-app.php >/dev/null 2>&1";
	
	$sh[]="\tif [ -e \"/etc/init.d/proxy-wccp\" ]; then";
	$sh[]="\t\t{$GLOBALS["echobin"]} \"FireWall: reconfigure WCCP rules\"";
	$sh[]="\t\t/etc/init.d/proxy-wccp restart || true";
	$sh[]="\tfi";
	
	$sh[]="\tif [ -e \"/etc/init.d/iptables-statsapp\" ]; then";
	$sh[]="\t\t{$GLOBALS["echobin"]} \"FireWall: reconfigure Stats-Appliances rules\"";
	$sh[]="\t\t/etc/init.d/iptables-statsapp restart || true";
	$sh[]="\tfi";
	
	$sh[]="\tif [ -e \"/bin/iptables-parents.sh\" ]; then";
	$sh[]="\t\t{$GLOBALS["echobin"]} \"FireWall: reconfigure Firewall (Proxy parent) rules\"";
	$sh[]="\t\t/bin/iptables-parents.sh || true";
	$sh[]="\tfi";
	
	$sh[]="\tif [ -e \"/bin/suricata-fw.sh\" ]; then";
	$sh[]="\t\t{$GLOBALS["echobin"]} \"FireWall: reconfigure IDS rules\"";
	$sh[]="\t\t/bin/suricata-fw.sh || true";
	$sh[]="\tfi";
	
	
	
	$sh[]=";;";
	
	$sh[]="  restart)";
	$sh[]="if [ \$FireHolEnable -eq 1 ]; then";
		$sh[]="\t{$GLOBALS["echobin"]} \"FireWall: Reconfiguring\"";
		$sh[]="\t$php ".__FILE__." --reconfigure >/dev/null 2>&1";
		$sh[]="\t{$GLOBALS["echobin"]} \"FireWall: Restarting\"";
		$sh[]="\t/usr/local/sbin/firehol stop || true";
		$sh[]="\t/usr/local/sbin/firehol start || true";
	$sh[]="fi";
	$sh[]="if [ \$FireHolEnable -eq 0 ]; then";
		$sh[]="{$GLOBALS["echobin"]} \"FireWall: disabled, down Transparent rules\"";
		$f[]="";
		$sh[]="\t$php ".dirname(__FILE__)."/exec.squid.transparent.delete.php";
		$sh[]="\t{$GLOBALS["echobin"]} \"FireWall: Reconfiguring Transparent rules\"";
		$sh[]="\t$php ".dirname(__FILE__)."/exec.squid.transparent.php >/dev/null 2>&1";
		$sh[]="\tif [ -e \"/etc/init.d/iptables-transparent\" ]; then";
		$sh[]="\t\t{$GLOBALS["echobin"]} \"FireWall: disabled, run Transparent rules\"";
		$sh[]="\t\t/etc/init.d/iptables-transparent start";
		$sh[]="\tfi";
	$sh[]="fi";
	
	$sh[]="\t{$GLOBALS["echobin"]} \"FireWall: Reconfiguring Stats-Appliance rules\"";
	$sh[]="\t$php ".dirname(__FILE__)."/exec.iptables-stats-app.php >/dev/null 2>&1";
	
	$sh[]="\tif [ -e \"/etc/init.d/iptables-statsapp\" ]; then";
	$sh[]="\t\t{$GLOBALS["echobin"]} \"FireWall: restart Stats-Appliances rules\"";
	$sh[]="\t\t/etc/init.d/iptables-statsapp restart || true";
	$sh[]="\tfi";
	
	$sh[]="\tif [ -e \"/etc/init.d/proxy-wccp\" ]; then";
	$sh[]="\t\t{$GLOBALS["echobin"]} \"FireWall: Start WCCP rules\"";
	$sh[]="\t\t/etc/init.d/proxy-wccp verif || true";
	$sh[]="\tfi";
	
	$sh[]="\tif [ -e \"/bin/iptables-parents.sh\" ]; then";
	$sh[]="\t\t{$GLOBALS["echobin"]} \"FireWall: restart Firewall (Proxy parent) rules\"";
	$sh[]="\t\t/bin/iptables-parents.sh || true";
	$sh[]="\tfi";
	$sh[]="\t$php ".dirname(__FILE__)."/exec.mikrotik.php >/dev/null 2>&1";
	
	$sh[]="\tif [ -e \"/bin/suricata-fw.sh\" ]; then";
	$sh[]="\t\t{$GLOBALS["echobin"]} \"FireWall: restart IDS rules\"";
	$sh[]="\t\t/bin/suricata-fw.sh || true";
	$sh[]="\tfi";
	
	$sh[]=";;";
	
	
	$sh[]="*)";
	$sh[]=" echo \"Usage: $0 {start ,restart,configure or stop only}\"";
	$sh[]="exit 1";
	$sh[]=";;";
	$sh[]="esac";
	$sh[]="exit 0\n";
	
		
	@file_put_contents("/etc/init.d/firehol", @implode("\n", $sh));
	@chmod("/etc/init.d/firehol",0755);
	if(!$noprogress){build_progress("{installing_default_script}...",90);}

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f firehol defaults >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add firehol >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 1234 firehol on >/dev/null 2>&1");
	}	
	
	
	build_progress("{default_script}...{done}",98);	
	
	
}


function build_progress($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/firehol.reconfigure.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
	if($GLOBALS["PROGRESS"]){sleep(1);}

}



function reconfigure(){
	
	$services=unserialize("a:110:{s:2:\"AH\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:6:\"51/any\";}s:6:\"client\";a:1:{s:5:\"ports\";s:3:\"any\";}}s:6:\"amanda\";a:3:{s:6:\"server\";a:1:{s:5:\"ports\";s:9:\"udp/10080\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}s:6:\"helper\";s:6:\"amanda\";}s:8:\"aptproxy\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:8:\"tcp/9999\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:7:\"apcupsd\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:8:\"tcp/6544\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:10:\"apcupsdnis\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:8:\"tcp/3551\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:8:\"asterisk\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:8:\"tcp/5038\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:4:\"cups\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:15:\"tcp/631 udp/631\";}s:6:\"client\";a:1:{s:5:\"ports\";s:3:\"any\";}}s:10:\"cvspserver\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:8:\"tcp/2401\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:8:\"darkstat\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:7:\"tcp/666\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:7:\"daytime\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:6:\"tcp/13\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:3:\"dcc\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:8:\"udp/6277\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:4:\"dcpp\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:17:\"tcp/1412 udp/1412\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:3:\"dns\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:13:\"udp/53 tcp/53\";}s:6:\"client\";a:1:{s:5:\"ports\";s:3:\"any\";}}s:9:\"dhcprelay\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:6:\"udp/67\";}s:6:\"client\";a:1:{s:5:\"ports\";s:2:\"67\";}}s:4:\"dict\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:8:\"tcp/2628\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:6:\"distcc\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:8:\"tcp/3632\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:7:\"eserver\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:26:\"tcp/4661 udp/4661 udp/4665\";}s:6:\"client\";a:1:{s:5:\"ports\";s:3:\"any\";}}s:3:\"ESP\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:6:\"50/any\";}s:6:\"client\";a:1:{s:5:\"ports\";s:3:\"any\";}}s:4:\"echo\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:5:\"tcp/7\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:6:\"finger\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:6:\"tcp/79\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:3:\"ftp\";a:3:{s:6:\"server\";a:1:{s:5:\"ports\";s:6:\"tcp/21\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}s:6:\"helper\";s:3:\"ftp\";}s:4:\"gift\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:35:\"tcp/4302 tcp/1214 tcp/2182 tcp/2472\";}s:6:\"client\";a:1:{s:5:\"ports\";s:3:\"any\";}}s:6:\"giftui\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:8:\"tcp/1213\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:8:\"gkrellmd\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:9:\"tcp/19150\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:3:\"GRE\";a:3:{s:6:\"server\";a:1:{s:5:\"ports\";s:6:\"47/any\";}s:6:\"client\";a:1:{s:5:\"ports\";s:3:\"any\";}s:6:\"helper\";s:9:\"proto_gre\";}s:4:\"h323\";a:3:{s:6:\"server\";a:1:{s:5:\"ports\";s:8:\"tcp/1720\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}s:6:\"helper\";s:4:\"h323\";}s:9:\"heartbeat\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:11:\"udp/690:699\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:4:\"http\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:6:\"tcp/80\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:5:\"https\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:7:\"tcp/443\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:7:\"httpalt\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:8:\"tcp/8080\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:3:\"iax\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:8:\"udp/5036\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:4:\"iax2\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:17:\"udp/5469 udp/4569\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:4:\"ICMP\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:8:\"icmp/any\";}s:6:\"client\";a:1:{s:5:\"ports\";s:3:\"any\";}}s:4:\"icmp\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:20:\"${server_ICMP_ports}\";}s:6:\"client\";a:1:{s:5:\"ports\";s:20:\"${client_ICMP_ports}\";}}s:6:\"ICMPV6\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:10:\"icmpv6/any\";}s:6:\"client\";a:1:{s:5:\"ports\";s:3:\"any\";}}s:6:\"icmpv6\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:22:\"${server_ICMPV6_ports}\";}s:6:\"client\";a:1:{s:5:\"ports\";s:22:\"${client_ICMPV6_ports}\";}}s:3:\"icp\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:8:\"udp/3130\";}s:6:\"client\";a:1:{s:5:\"ports\";s:4:\"3130\";}}s:5:\"ident\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:7:\"tcp/113\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:4:\"imap\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:7:\"tcp/143\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:5:\"imaps\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:7:\"tcp/993\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:3:\"irc\";a:3:{s:6:\"server\";a:1:{s:5:\"ports\";s:8:\"tcp/6667\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}s:6:\"helper\";s:3:\"irc\";}s:6:\"isakmp\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:7:\"udp/500\";}s:6:\"client\";a:1:{s:5:\"ports\";s:3:\"any\";}}s:9:\"ipsecnatt\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:8:\"udp/4500\";}s:6:\"client\";a:1:{s:5:\"ports\";s:3:\"any\";}}s:6:\"jabber\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:17:\"tcp/5222 tcp/5223\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:7:\"jabberd\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:26:\"tcp/5222 tcp/5223 tcp/5269\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:4:\"l2tp\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:8:\"udp/1701\";}s:6:\"client\";a:1:{s:5:\"ports\";s:3:\"any\";}}s:4:\"ldap\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:7:\"tcp/389\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:5:\"ldaps\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:7:\"tcp/636\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:3:\"lpd\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:7:\"tcp/515\";}s:6:\"client\";a:1:{s:5:\"ports\";s:3:\"any\";}}s:12:\"microsoft_ds\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:7:\"tcp/445\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:3:\"mms\";a:3:{s:6:\"server\";a:1:{s:5:\"ports\";s:17:\"tcp/1755 udp/1755\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}s:6:\"helper\";s:3:\"mms\";}s:5:\"ms_ds\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:28:\"${server_microsoft_ds_ports}\";}s:6:\"client\";a:1:{s:5:\"ports\";s:28:\"${client_microsoft_ds_ports}\";}}s:4:\"msnp\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:8:\"tcp/6891\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:3:\"msn\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:17:\"tcp/1863 udp/1863\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:5:\"mysql\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:8:\"tcp/3306\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:9:\"netbackup\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:69:\"tcp/13701 tcp/13711 tcp/13720 tcp/13721 tcp/13724 tcp/13782 tcp/13783\";}s:6:\"client\";a:1:{s:5:\"ports\";s:3:\"any\";}}s:10:\"netbios_ns\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:7:\"udp/137\";}s:6:\"client\";a:1:{s:5:\"ports\";s:3:\"any\";}}s:11:\"netbios_dgm\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:7:\"udp/138\";}s:6:\"client\";a:1:{s:5:\"ports\";s:3:\"any\";}}s:11:\"netbios_ssn\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:7:\"tcp/139\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:4:\"nntp\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:7:\"tcp/119\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:5:\"nntps\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:7:\"tcp/563\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:3:\"ntp\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:15:\"udp/123 tcp/123\";}s:6:\"client\";a:1:{s:5:\"ports\";s:3:\"any\";}}s:3:\"nut\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:17:\"tcp/3493 udp/3493\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:8:\"nxserver\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:13:\"tcp/5000:5200\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:7:\"openvpn\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:17:\"tcp/1194 udp/1194\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:6:\"oracle\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:8:\"tcp/1521\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:4:\"OSPF\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:6:\"89/any\";}s:6:\"client\";a:1:{s:5:\"ports\";s:3:\"any\";}}s:4:\"pop3\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:7:\"tcp/110\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:5:\"pop3s\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:7:\"tcp/995\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:7:\"portmap\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:15:\"udp/111 tcp/111\";}s:6:\"client\";a:1:{s:5:\"ports\";s:3:\"any\";}}s:8:\"postgres\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:8:\"tcp/5432\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:4:\"pptp\";a:3:{s:6:\"server\";a:1:{s:5:\"ports\";s:8:\"tcp/1723\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}s:6:\"helper\";s:14:\"pptp proto_gre\";}s:7:\"privoxy\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:8:\"tcp/8118\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:6:\"radius\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:17:\"udp/1812 udp/1813\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:11:\"radiusproxy\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:8:\"udp/1814\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:9:\"radiusold\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:17:\"udp/1645 udp/1646\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:14:\"radiusoldproxy\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:8:\"udp/1647\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:3:\"rdp\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:8:\"tcp/3389\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:4:\"rndc\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:7:\"tcp/953\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:5:\"rsync\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:15:\"tcp/873 udp/873\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:3:\"rtp\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:15:\"udp/10000:20000\";}s:6:\"client\";a:1:{s:5:\"ports\";s:3:\"any\";}}s:4:\"sane\";a:3:{s:6:\"server\";a:1:{s:5:\"ports\";s:8:\"tcp/6566\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}s:6:\"helper\";s:4:\"sane\";}s:3:\"sip\";a:3:{s:6:\"server\";a:1:{s:5:\"ports\";s:8:\"udp/5060\";}s:6:\"client\";a:1:{s:5:\"ports\";s:12:\"5060 default\";}s:6:\"helper\";s:3:\"sip\";}s:5:\"socks\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:17:\"tcp/1080 udp/1080\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:5:\"squid\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:8:\"tcp/3128\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:4:\"smtp\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:6:\"tcp/25\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:5:\"smtps\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:7:\"tcp/465\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:4:\"snmp\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:7:\"udp/161\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:8:\"snmptrap\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:7:\"udp/162\";}s:6:\"client\";a:1:{s:5:\"ports\";s:3:\"any\";}}s:4:\"nrpe\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:8:\"tcp/5666\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:3:\"ssh\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:6:\"tcp/22\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:4:\"stun\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:17:\"udp/3478 udp/3479\";}s:6:\"client\";a:1:{s:5:\"ports\";s:3:\"any\";}}s:10:\"submission\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:7:\"tcp/587\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:6:\"sunrpc\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:23:\"${server_portmap_ports}\";}s:6:\"client\";a:1:{s:5:\"ports\";s:23:\"${client_portmap_ports}\";}}s:4:\"swat\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:7:\"tcp/901\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:6:\"syslog\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:7:\"udp/514\";}s:6:\"client\";a:1:{s:5:\"ports\";s:14:\"syslog default\";}}s:6:\"telnet\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:6:\"tcp/23\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:4:\"tftp\";a:3:{s:6:\"server\";a:1:{s:5:\"ports\";s:6:\"udp/69\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}s:6:\"helper\";s:4:\"tftp\";}s:6:\"tomcat\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:23:\"${server_httpalt_ports}\";}s:6:\"client\";a:1:{s:5:\"ports\";s:23:\"${client_httpalt_ports}\";}}s:4:\"time\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:13:\"tcp/37 udp/37\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:4:\"upnp\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:17:\"udp/1900 tcp/2869\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:4:\"uucp\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:7:\"tcp/540\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:5:\"whois\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:6:\"tcp/43\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:6:\"vmware\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:7:\"tcp/902\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:10:\"vmwareauth\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:7:\"tcp/903\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:9:\"vmwareweb\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:17:\"tcp/8222 tcp/8333\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:3:\"vnc\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:13:\"tcp/5900:5903\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:8:\"webcache\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:23:\"${server_httpalt_ports}\";}s:6:\"client\";a:1:{s:5:\"ports\";s:23:\"${client_httpalt_ports}\";}}s:6:\"webmin\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:9:\"tcp/10000\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}s:5:\"xdmcp\";a:2:{s:6:\"server\";a:1:{s:5:\"ports\";s:7:\"udp/177\";}s:6:\"client\";a:1:{s:5:\"ports\";s:7:\"default\";}}}");
	
	
	$fire=new firehol();
	$fire->build();
}



function install(){
	
	$unix=new unix();
	build_progress("{installing_firewall_service}",10);
	
	$src_package="/usr/share/artica-postfix/bin/install/firehol-debian7-64.tar.gz";
	if(!is_file($src_package)){
		build_progress("{source_package_not_found}",110);
		return;
	}
	
	$tar=$unix->find_program("tar");
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress("{uncompress}",20);
	shell_exec("$tar xvf /usr/share/artica-postfix/bin/install/firehol-debian7-64.tar.gz -C /");
	build_progress("{installing_service}",30);
	$ipset=$unix->find_program("ipset");
	if(!is_file($ipset)){
		build_progress("{installing_service} - IPSET -",35);
		$unix->DEBIAN_INSTALL_PACKAGE("ipset");
	}
	
	build_init(true);
	build_progress("{refresh_settings}",60);
	system("/usr/share/artica-postfix/bin/process1 --force --verbose --".time());
	build_progress("{restart_status_service}",70);
	system("/etc/init.d/artica-status restart --force");
	build_progress("{done}",100);
	
}

function disable_progress(){
	build_progress("{stopping_firewall}",30);
	$sock=new sockets();
	$sock->SET_INFO("FireHolEnable", 0);
	shell_exec("/usr/local/sbin/firehol stop");
	build_progress("{building_init_script}",30);
	build_progress("{reconfiguring}",50);
	build_init();
	build_progress("{reconfiguring}",90);
	shell_exec("/etc/init.d/firehol reconfigure");
	build_progress("{done}",100);
}
function enable_progress(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress("{enable_firewall}",20);
	$sock=new sockets();
	$sock->SET_INFO("FireHolEnable", 1);
	shell_exec("$php ".dirname(__FILE__)."/exec.squid.transparent.delete.php");
	build_progress("{building_init_script}",30);
	build_progress("{reconfiguring}",50);
	$fire=new firehol();
	$fire->build();
	build_progress("{starting_firewall}",70);
	shell_exec("/etc/init.d/firehol start");
	build_init();
	build_progress("{done}",100);
	
}

function reconfigure_progress(){
	$sock=new sockets();
	$unix=new unix();
	
	$php=$unix->LOCATE_PHP5_BIN();
	
	
	if(!$sock->isFirehol()){
		build_progress("{building_init_script}",80);
		build_init();
		build_progress("{building_rules}",80);
		system("$php /usr/share/artica-postfix/exec.squid.transparent.php");
		build_progress("{building_rules}",81);
		system("$php /usr/share/artica-postfix/exec.secure.gateway.php");
		build_progress("{building_rules}",82);
		if(is_file("/bin/artica-secure-gateway.sh")){system("/bin/artica-secure-gateway.sh");}
		build_progress("FireWall service:{disabled}",100);

		return;
	}
	
	build_progress("{building_rules}",10);
	$fire=new firehol();
	$fire->build();
	build_progress("{stopping_firewall}",50);
	shell_exec("/usr/local/sbin/firehol stop");
	build_progress("{starting_firewall}",70);
	shell_exec("/usr/local/sbin/firehol start");
	build_progress("{building_init_script}",80);
	build_init();
	build_progress("{done}",100);
}

function stop_service(){
	
	build_progress("{stop_firewall}",10);
	if(!is_file("/usr/local/sbin/firehol")){
		echo "Not installed...\n";
		build_progress("{stop_firewall} {failed}",110);
		return;
	}
	
	system("/usr/local/sbin/firehol stop");
	build_progress("{stop_firewall} {success}",100);
	
}
function start_service(){
	$unix=new unix();
	build_progress("{start_firewall}",10);
	if(!is_file("/usr/local/sbin/firehol")){
		echo "Not installed...\n";
		build_progress("{start_firewall} {failed}",110);
		return;
	}
	
	$KERNEL_VERSION=$unix->KERNEL_VERSION();
	$modprobe=$unix->find_program("modprobe");
	
	if(is_file("/lib/modules/$KERNEL_VERSION/extra/xt_ndpi.ko")){shell_exec("$modprobe xt_ndpi");}
	system("/usr/local/sbin/firehol start");
	build_progress("{start_firewall} {success}",100);

}

function scanservices(){
	
	$f=explode("\n", @file_get_contents("/usr/local/sbin/firehol"));
	
	while (list ($ip, $line) = each ($f) ){
		if(preg_match('#server_(.+?)_ports="(.+?)"#', $line,$re)){
			if(preg_match("#CAT_CMD#", $re[2])){continue;}
			$array[$re[1]]["server"]["ports"]=$re[2];
			continue;
		}
		if(preg_match('#client_(.+?)_ports="(.+?)"#', $line,$re)){
			if(preg_match("#CAT_CMD#", $re[2])){continue;}
			$array[$re[1]]["client"]["ports"]=$re[2];
			continue;
		}
		if(preg_match('#helper_(.+)="(.+?)"#', $line,$re)){
			if(preg_match("#CAT_CMD#", $re[2])){continue;}
			$array[$re[1]]["helper"]=$re[2];
			continue;
		}		
		
		
	}
	@file_put_contents("/usr/share/artica-postfix/ressources/databases/firehol.services.db", base64_encode(serialize($array)));
	
}

