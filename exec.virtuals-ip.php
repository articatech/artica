#!/usr/bin/php
<?php
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/ressources/class.templates.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/framework/class.tcpip-parser.inc');
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.nics.inc');
include_once(dirname(__FILE__) . '/ressources/class.os.system.inc');

$GLOBALS["NO_GLOBAL_RELOAD"]=false;
$GLOBALS["AFTER_REBUILD"]=false;
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["SLEEP"]=false;
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}

if(preg_match("#--sleep#",implode(" ",$argv))){$GLOBALS["SLEEP"]=true;}
if(preg_match("#--afterrebuild#",implode(" ",$argv))){$GLOBALS["AFTER_REBUILD"]=true;}

if($argv[1]=="--resolvconf"){resolvconf();exit;}
if($argv[1]=="--interfaces"){interfaces_show();die();}
//if(system_is_overloaded(basename(__FILE__))){writelogs("Fatal: Overloaded system,die()","MAIN",__FILE__,__LINE__);die();}
if($argv[1]=="--loopback"){loopback();die();}
if($argv[1]=="--just-add"){routes();die();}
if($argv[1]=="--articalogon"){articalogon();die();}
if($argv[1]=="--ifconfig"){ifconfig_tests();exit;}
if($argv[1]=="--bridges"){bridges_build();exit;}
if($argv[1]=="--parse-tests"){ifconfig_parse($argv[2]);exit;}
if($argv[1]=="--routes"){routes();exit;}
if($argv[1]=="--routes-del"){routes_del($argv[2]);exit;}
if($argv[1]=="--vlans"){build();exit;}
if($argv[1]=="--build"){build();exit;}
if($argv[1]=="--postfix-instances"){postfix_multiples_instances();exit;}
if($argv[1]=="--ping"){ping($argv[2]);exit;}
if($argv[1]=="--ipv6"){Checkipv6();exit;}
if($argv[1]=="--ifupifdown"){ifupifdown($argv[2]);exit;}
if($argv[1]=="--reconstruct-interface"){reconstruct_interface($argv[2]);exit;}
if($argv[1]=="--ucarp"){ucarp_build();exit;}
if($argv[1]=="--ucarp-start"){ucarp_start();exit;}
if($argv[1]=="--ucarp-stop"){ucarp_stop();exit;}
if($argv[1]=="--net-rules"){persistent_net_rules();exit;}
if($argv[1]=="--main-routes"){routes_main_build();exit;}
if($argv[1]=="--routes"){routes();exit;}
if($argv[1]=="--vlans-build"){vlan_build();exit;}
if($argv[1]=="--vlans-delete"){vlan_delete($argv[2]);exit;}
if($argv[1]=="--virtip-build"){virtip_build($argv[2]);exit;}
if($argv[1]=="--virtip-delete"){virtip_delete($argv[2]);exit;}
if($argv[1]=="--bridge-delete"){bridge_delete($argv[2]);exit;}
if($argv[1]=="--bridge-rm"){bridge_deletemanu($argv[2]);exit;}
if($argv[1]=="--hosts"){etc_hosts_exec();exit;}
if($argv[1]=="--hosts-defaults"){etc_hosts_defaults();exit;}
if($argv[1]=="--iptables-bridge-delete"){bridges_delete();exit;}
if($argv[1]=="--ucarp-notify"){ucarp_notify($argv[2],$argv[3],$argv[4],$argv[5],$argv[6]);exit;}
if($argv[1]=="--ucarp-notify-down"){ucarp_notify_down($argv[2],$argv[3],$argv[4],$argv[5],$argv[6]);exit;}
if($argv[1]=="--wccp-build"){nics_wccp_build(true);exit;}
if($argv[1]=="--ucarp-active"){print_r(ucarp_interface_active(true));exit;}





if($GLOBALS["SLEEP"]){sleep(2);}
dev_shm();
build();

//
//vconfig set_flag eth1.3 1 1
//vconfig set_flag eth1.4 1 1

//http://www.cyberciti.biz/tips/howto-configure-linux-virtual-local-area-network-vlan.html
//http://www.stg.net/vlanbridge


function ping($host){
	ini_set_verbosed();
	$unix=new unix();
	if($unix->PingHost($host)){
		echo "$host:TRUE\n";
	}else{
		echo "$host:FALSE\n";
	}
	
}

function interfaces_show(){
	$nic=new system_nic();
	$datas=$nic->root_build_debian_config();
	echo $datas;
}

function loopback(){
	$unix=new unix();
	$ifconfig=$unix->find_program("ifconfig");
	shell_exec("$ifconfig lo down");
	shell_exec("$ifconfig lo 127.0.0.1 netmask 255.255.255.0 up >/dev/null 2>&1");
	VirtualsIPSyslog("Restarting loopback...");
}

function VirtualsIPSyslog($text){
	if(!function_exists("syslog")){return;}
	$LOG_SEV=LOG_ERR;
	openlog("artica-ifup", LOG_PID , LOG_SYSLOG);
	syslog($LOG_SEV, $text);
	closelog();
}

function resolvconf(){
	$resolv=new resolv_conf();
	$resolvDatas=$resolv->build();
	@file_put_contents("/etc/resolv.conf", $resolvDatas);
	if(is_dir("/var/spool/postfix/etc")){@file_put_contents("/var/spool/postfix/etc/resolv.conf", $resolvDatas);}
	if(is_dir("/etc/resolvconf")){
		@mkdir("/etc/resolvconf/resolv.conf.d",0755,true);
		$f=array();
		if($resolv->MainArray["DNS1"]<>null){$f[]="nameserver {$resolv->MainArray["DNS1"]}";}
		if($resolv->MainArray["DNS2"]<>null){$f[]="nameserver {$resolv->MainArray["DNS2"]}";}
		if($resolv->MainArray["DNS3"]<>null){$f[]="nameserver {$resolv->MainArray["DNS3"]}";}
		if(count($f)>0){
			@file_put_contents("/etc/resolvconf/resolv.conf.d/base", @implode("\n", $f));
		}
	}
	
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $php5 ".dirname(__FILE__)."/exec.dnsmasq.php --build >/dev/null 2>&1 &");
	
	
}

function ucarp_notify($nic=null,$SQUIDIP=null,$trois=null,$quatre=null,$cinq=null){
	if($nic==null){
		VirtualsIPSyslog("[Failover] No nic, no IP...");
		return;
	}
	
	
	$unix=new unix();
	$LOCATE_SQUID_BIN=$unix->LOCATE_SQUID_BIN();
	if(!is_file($LOCATE_SQUID_BIN)){return;}
	$nohup=$unix->find_program("nohup");
	
	
	include_once(dirname(__FILE__)."/ressources/class.squid.inc");
	$sock=new sockets();
	$hasProxyTransparent=$sock->GET_INFO("hasProxyTransparent");
	if(!is_numeric($hasProxyTransparent)){$hasProxyTransparent=0;}
	VirtualsIPSyslog("[Failover] state UP detected $nic:$SQUIDIP Proxy Transparent mode: $hasProxyTransparent");
	
	if(is_file("/usr/share/ucarp/Master")){
		VirtualsIPSyslog("[Failover] UP mode Master... nothing to do...");
		return;
	}
	
	$MAIN=unserialize(base64_decode($sock->GET_INFO("HASettings")));
	if($MAIN["SLAVE"]<>null){
		VirtualsIPSyslog("[Failover] UP mode Master... nothing to do...");
		return;
	}
	
	
	if($hasProxyTransparent==0){return;}
	$squid=new squidbee();
	$ssl_port=$squid->get_ssl_port();
	if(!is_numeric($squid->listen_port)){$squid->listen_port=3128;}
	$listen_ssl_port=$squid->listen_port+1;
	$SSL_BUMP=$squid->SSL_BUMP;
	$iptables=$unix->find_program("iptables");
	$MARKLOG="-m comment --comment \"SquidFailOverTransparent\"";
	$SQUIDPORT=$squid->listen_port;
	
	VirtualsIPSyslog("[Failover] UP Redirect connections from $SQUIDIP:80/443 to port $SQUIDPORT/$ssl_port - if ssl enabled -");
	ucarp_notify_removeiptables();
	shell_exec("$iptables -t nat -A PREROUTING -s $SQUIDIP -p tcp --dport 80 -j ACCEPT $MARKLOG");
	
	if($SSL_BUMP==1){
		shell_exec("$iptables -t nat -A PREROUTING -s $SQUIDIP -p tcp --dport 443 -j ACCEPT $MARKLOG");
	}	
	
	shell_exec("$iptables -t nat -A PREROUTING -p tcp --dport 80 -j REDIRECT --to-port $SQUIDPORT $MARKLOG");
	if($SSL_BUMP==1){shell_exec("$iptables -t nat -A PREROUTING -p tcp --dport 443 -j REDIRECT --to-port $ssl_port $MARKLOG");}
	shell_exec("$iptables -t nat -A POSTROUTING -j MASQUERADE $MARKLOG");
	shell_exec("$iptables -t mangle -A PREROUTING -p tcp --dport $SQUIDPORT -j DROP $MARKLOG");
	if($SSL_BUMP==1){
		shell_exec("$iptables -t mangle -A PREROUTING -p tcp --dport $ssl_port -j DROP $MARKLOG");
	}
	
	$cmd="/etc/init.d/squid reload --script=".basename(__FILE__);
	shell_exec("$cmd >/dev/null 2>&1");
	shell_exec("$nohup /etc/init.d/snmpd restart >/dev/null 2>&1 &");
	
	
}

function ucarp_notify_removeiptables(){
	$unix=new unix();
	VirtualsIPSyslog("[Failover] Remove transparent rules...");
	$iptables_save=$unix->find_program("iptables-save");
	$iptables_restore=$unix->find_program("iptables-restore");
	system("$iptables_save > /etc/artica-postfix/iptables.conf");
	$data=file_get_contents("/etc/artica-postfix/iptables.conf");
	$datas=explode("\n",$data);
	$pattern="#.+?SquidFailOverTransparent#";
	$d=0;
	while (list ($num, $ligne) = each ($datas) ){
		if($ligne==null){continue;}
		if(preg_match($pattern,$ligne)){$d++;continue;}
		$conf=$conf . $ligne."\n";
	}
	file_put_contents("/etc/artica-postfix/iptables.new.conf",$conf);
	system("$iptables_restore < /etc/artica-postfix/iptables.new.conf");
	VirtualsIPSyslog("[Failover] Removed $d temporary transparent rules...");	
}

function ucarp_notify_down($nic=null,$ipaddr=null,$trois=null,$quatre=null,$cinq=null){
	
	if(is_file("/usr/share/ucarp/Master")){
		VirtualsIPSyslog("[Failover] DOWN mode Master... nothing to do...");
		return;
	}
	
	$unix=new unix();
	$LOCATE_SQUID_BIN=$unix->LOCATE_SQUID_BIN();
	if(!is_file($LOCATE_SQUID_BIN)){return;}
	$nohup=$unix->find_program("nohup");
	ucarp_notify_removeiptables();
	$cmd="/etc/init.d/squid reload --script=".basename(__FILE__);
	shell_exec("$nohup $cmd >/dev/null 2>&1 &");
	shell_exec("$nohup /etc/init.d/snmpd restart >/dev/null 2>&1 &");
}


function ucarp_stop(){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();
	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		echo "Starting......: ".date("H:i:s")." UCARP Start task already running PID: $pid\n";
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	$ucarp_bin=$unix->find_program("ucarp");
	if(!is_file($ucarp_bin)){echo "Starting......: ".date("H:i:s")." UCARP Not installed...\n";return;}	
	$pids=ucarp_all_pid();
	$kill=$unix->find_program("kill");
	
	echo "Starting......: ".date("H:i:s")." UCARP Found (".count($pids).") processe(s)\n";
	
	while (list ($pid, $line) = each ($pids) ){
		echo "Starting......: ".date("H:i:s")." UCARP checks PID:$pid processe(s)\n";
		ucarp_stop_single($pid);
	}
	
	
}

function ScriptInfo($line){
	$line=str_replace("\n", "", $line);
	$line=str_replace("\r", "", $line);
	$line=str_replace("\r\n", "", $line);
	return $line;
	
}

function dev_shm(){
	if(!is_dir("/etc/network")){return;}
	if(!is_dir("/dev/shm/network")){@mkdir("/dev/shm/network",0755,true);}
	
	if(!is_link("/etc/network/run")){
		$unix=new unix();
		$ln=$unix->find_program("ln");
		shell_exec("$ln -s /dev/shm/network /etc/network/run");
	}
	if($GLOBALS["VERBOSE"]){echo "Line:".__LINE__." dev_shm ->done\n";}
	
}

function vlan_build(){
	$nic=new system_nic();
	$nic->BuildVlans();
	if(count($GLOBALS["SCRIPTS"])>0){
		while (list ($index, $line) = each ($GLOBALS["SCRIPTS"]) ){
			$line=trim($line);
			if(substr($line, 0,1)=="#"){$sh[]=$line;continue;}
			if($line==null){continue;}
			$md=md5($line);
			if(isset($AL[$md])){continue;}
			$AL[$md]=true;			
			
			echo "Starting......: ".date("H:i:s")." `$line`\n";
			$sh[]="echo \"Starting......: ".date("H:i:s")." $line\"";
			events("$line",__FUNCTION__,__LINE__);
			system($line);
		}
		usleep(500);
	}
	BuildNetWorksDebian();
}
function virtip_build(){
	$nic=new system_nic();
	$nic->BuildVirtIps();
	if(count($GLOBALS["SCRIPTS"])>0){
		while (list ($index, $line) = each ($GLOBALS["SCRIPTS"]) ){
			$line=trim($line);
			if($line==null){continue;}
			$md=md5($line);
			if(substr($line, 0,1)=="#"){$sh[]=ScriptInfo($line);continue;}
			if(isset($AL[$md])){continue;}
			$AL[$md]=true;
				
			echo "Starting......: ".date("H:i:s")." `$line`\n";
			$sh[]="echo \"Starting......: ".date("H:i:s")." $line\"";
			events("$line",__FUNCTION__,__LINE__);
			system($line);
		}
		usleep(500);
	}
	BuildNetWorksDebian();
}

function etc_hosts_exec(){
	
	
	if(!etc_hosts()){return;}
	if(count($GLOBALS["SCRIPTS"])>0){
		while (list ($index, $line) = each ($GLOBALS["SCRIPTS"]) ){
			$line=trim($line);
			if($line==null){continue;}
			$md=md5($line);
			if(substr($line, 0,1)=="#"){continue;}
			if(isset($AL[$md])){continue;}
			$AL[$md]=true;
			echo "Starting......: ".date("H:i:s")." `$line`\n";
			events("$line",__FUNCTION__,__LINE__);
			system($line);
		}

	}
	
	
}

function etc_hosts(){
	$unix=new unix();
	$echo=$unix->find_program("echo");
	$sock=new sockets();
	$hostname=$sock->GET_INFO("myhostname");
	if($hostname==null){$hostname=$sock->getFrameWork("system.php?hostname-g=yes");$sock->SET_INFO($hostname,"myhostname");}
	$q=new mysql();
	$DisableEtcHosts=$sock->GET_INFO("DisableEtcHosts");
	if(!is_numeric($DisableEtcHosts)){$DisableEtcHosts=0;}
	if($DisableEtcHosts==1){
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] ****      HOSTS FILE       ****";
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] Disabled by DisableEtcHosts";
		return;
	}
	
	if($q->COUNT_ROWS("net_hosts", "artica_backup")==0){
		if(!$q->ok){echo $q->mysql_error."\n"; return; }
		etc_hosts_defaults();
	}
	if($q->COUNT_ROWS("net_hosts", "artica_backup")==0){
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] net_hosts issue on MySQL keep the file untouched";
		return;
	}
	
	if(strpos($hostname, ".")>0){
		$rre=explode(".",$hostname);
		$netbiosname=$rre[0];
	}
	
	
	$f[]="::1     localhost ip6-localhost ip6-loopback";
	$f[]="::1     $hostname\t$netbiosname";
	$f[]="fe00::0 ip6-localnet";
	$f[]="ff00::0 ip6-mcastprefix";
	$f[]="ff02::1 ip6-allnodes";
	$f[]="ff02::2 ip6-allrouters";
	$f[]="ff02::3 ip6-allhosts";
	$f[]="ff02::3 ip6-allhosts";

	$GLOBALS["SCRIPTS"][]="# [".__LINE__."]";
	$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
	$GLOBALS["SCRIPTS"][]="# [".__LINE__."] ****      HOSTS FILE       ****";
	$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
	$GLOBALS["SCRIPTS"][]="# [".__LINE__."]";
	$GLOBALS["SCRIPTS"][]="# this first line flush the host file";
	$GLOBALS["SCRIPTS"][]="$echo \"127.0.0.1     $hostname\t$netbiosname\" >/etc/hosts";	
	while (list ($index, $line) = each ($f) ){
		$GLOBALS["SCRIPTS"][]="$echo \"$line\" >> /etc/hosts";
	}
	
	$sql="SELECT * FROM net_hosts ORDER BY hostname";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n"; return; }
	$ip=new IP();
	while ($ligne = mysql_fetch_assoc($results)) {
		
		while (list ($a, $b) = each ($ligne) ){
			$ligne[$a]=str_replace("\r\n", " ", $ligne[$a]);
			$ligne[$a]=str_replace("\n", " ", $ligne[$a]);
			$ligne[$a]=str_replace("\r", " ", $ligne[$a]);
			$ligne[$a]=trim($ligne[$a]);
		}
		
		if(!$ip->isValid($ligne["ipaddr"])){
			$GLOBALS["SCRIPTS"][]="# [".__LINE__."]: {$ligne["ipaddr"]} not valid for {$ligne["hostname"]}";
			continue;
		}
		
		if(trim($ligne["hostname"])==null){continue;}
		if(preg_match("#^google\.#", $ligne["hostname"])){
			$ligne["alias"]="www.{$ligne["hostname"]}";
		}
		
		if(trim($ligne["alias"])<>null){$ligne["alias"]="\t{$ligne["alias"]}";}
		$lineExe=trim("{$ligne["ipaddr"]}\t{$ligne["hostname"]}{$ligne["alias"]}");
		$GLOBALS["SCRIPTS"][]="$echo \"$lineExe\" >> /etc/hosts";
		
		
	}
	
	
	$q=new mysql_squid_builder();
	
	$sql="SELECT * FROM dnsmasq_records";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n"; return; }
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$ipaddr=trim($ligne["ipaddr"]);
		$hostname=trim($ligne["hostname"]);
		
		if(strpos($hostname, ".")>0){
				$hostname_EXPLODED=explode(".",$hostname); 
				$alias=$hostname_EXPLODED[0]; 
		}
		
		$results2=$q->QUERY_SQL("SELECT hostname FROM dnsmasq_cname WHERE recordid={$ligne["ID"]}");
		$aliases=array();
		while ($ligne2 = mysql_fetch_assoc($results2)) {
			if(trim($ligne2["hostname"])==null){continue;}
			$alias=$ligne2["hostname"];
			break;
		}
		$lineExe=trim("{$ipaddr}\t{$hostname}\t{$alias}");
		$GLOBALS["SCRIPTS"][]="$echo \"$lineExe\" >> /etc/hosts";
	
	}	
	
	
	
	
	
	$GLOBALS["SCRIPTS"][]="#";
	return true;
	
}

function vlan_delete($ID){
	$sql="SELECT * FROM nics_vlan WHERE ID='$ID'";
	$q=new mysql();
	if(!is_numeric($ID)){return;}
	if($ID<1){return;}	
	$unix=new unix();
	if(!isset($GLOBALS["moprobebin"])){$GLOBALS["moprobebin"]=$unix->find_program("modprobe");}
	if(!isset($GLOBALS["vconfigbin"])){$GLOBALS["vconfigbin"]=$unix->find_program("vconfig");}
	if(!isset($GLOBALS["ifconfig"])){$GLOBALS["ifconfig"]=$unix->find_program("ifconfig");}
	if(!isset($GLOBALS["ethtoolbin"])){$GLOBALS["ethtoolbin"]=$unix->find_program("ethtool");}
	if(!isset($GLOBALS["ipbin"])){$GLOBALS["ipbin"]=$unix->find_program("ip");}	
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$eth="{$ligne["nic"]}";
	$vlanid=$ligne["vlanid"];
	if($vlanid==0){return;}
	if($vlanid>0){$vlanid_text=".$vlanid";}	
	shell_exec("{$GLOBALS["ifconfig"]} $eth$vlanid_text down");
	shell_exec("{$GLOBALS["vconfigbin"]} rem eth$vlanid_text");
	$q->QUERY_SQL("DELETE FROM nics_vlan WHERE ID='$ID'","artica_backup");
	BuildNetWorksDebian();
}
function virtip_delete($ID){
	if(!is_numeric($ID)){return;}
	if($ID<1){return;}
	$sql="SELECT * FROM nics_virtuals WHERE ID='$ID'";
	$q=new mysql();
	
	$unix=new unix();
	if(!isset($GLOBALS["moprobebin"])){$GLOBALS["moprobebin"]=$unix->find_program("modprobe");}
	if(!isset($GLOBALS["vconfigbin"])){$GLOBALS["vconfigbin"]=$unix->find_program("vconfig");}
	if(!isset($GLOBALS["ifconfig"])){$GLOBALS["ifconfig"]=$unix->find_program("ifconfig");}
	if(!isset($GLOBALS["ethtoolbin"])){$GLOBALS["ethtoolbin"]=$unix->find_program("ethtool");}
	if(!isset($GLOBALS["ipbin"])){$GLOBALS["ipbin"]=$unix->find_program("ip");}
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$eth="{$ligne["nic"]}:{$ligne["ID"]}";
	shell_exec("{$GLOBALS["ifconfig"]} $eth down");
	$q->QUERY_SQL("DELETE FROM nics_virtuals WHERE ID='$ID'","artica_backup");
	BuildNetWorksDebian();	
	
}

/*
 * iptables -A INPUT -p udp -m physdev --physdev-in eth1 -j LOG
iptables -A INPUT -p tcp -m physdev --physdev-in eth1 -j LOG
iptables -A INPUT -p icmp -m physdev --physdev-in eth1 -j LOG

# allow ssh, smtp and http on the router _itself_ (INPUT!)
iptables -A INPUT -p tcp --dport 22 -m physdev --physdev-in eth1 -j ACCEPT
iptables -A INPUT -p tcp --dport 25 -m physdev --physdev-in eth1 -j ACCEPT
iptables -A INPUT -p tcp --dport 80 -m physdev --physdev-in eth1 -j ACCEPT

# reject all other connections to the router
iptables -A INPUT -p tcp --syn -m physdev --physdev-in eth1 -J REJECT

# allow the some on the FORWARD chain
iptables -A FORWARD -p tcp --dport 22 -m physdev --physdev-in eth1 --physdev-out eth0 -j ACCEPT
iptables -A FORWARD -p tcp --dport 25 -m physdev --physdev-in eth1 --physdev-out eth0 -j ACCEPT
iptables -A FORWARD -p tcp --dport 80 -m physdev --physdev-in eth1 --physdev-out eth0 -j ACCEPT

# reject irc to anywhere
iptables -A FORWARD -p tcp --dport 6667 -m physdev --physdev-in eth1 -j REJECT

# reject all other connections to the internal lan
iptables -A FORWARD -p tcp --syn -m physdev --physdev-in eth1 --physdev-out eth0 -j REJECT

*/



function ucarp_stop_single($pid){
	$unix=new unix();
	$ucarp_bin=$unix->find_program("ucarp");
	if(!is_file($ucarp_bin)){echo "Starting......: ".date("H:i:s")." UCARP Not installed...\n";return;}
	$kill=$unix->find_program("kill");
	$ifconfig=$unix->find_program("ifconfig");
	if(!$unix->process_exists($pid)){
		echo "Starting......: ".date("H:i:s")." UCARP [$pid]: Not running...\n";
		return;
	}
	
	$cmdline=var_export(@file_get_contents("/proc/$pid/cmdline"),true);
	if(preg_match("#'--interface=(.+?)'#", $cmdline,$re)){
		echo "Starting......: ".date("H:i:s")." UCARP: [$pid]: Shutting down interface ucarp:{$re[1]}...\n";
		shell_exec("$ifconfig {$re[1]}:ucarp down");
	}
		
	echo "Starting......: ".date("H:i:s")." UCARP: [$pid]: Shutting down $pid...\n";
	for($i=0;$i<10;$i++){
		unix_system_kill($pid);
		sleep(1);
		if(!$unix->process_exists($pid)){break;}
	}
	
	if(!$unix->process_exists($pid)){
		echo "Starting......: ".date("H:i:s")." UCARP: [$pid]: Shutting down success...\n";
	}
}

function ucarp_build($nopid=false){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();
	$sock=new sockets();

	
	if(!$nopid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			echo "Starting......: ".date("H:i:s")." UCARP Start task already running PID: $pid\n";
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	@unlink("/usr/share/ucarp/ETH_LIST");
	@unlink("/usr/share/ucarp/Master");
	$rm=$unix->find_program("rm");
	shell_exec("$rm /usr/share/ucarp/vip-*.sh >/dev/null 2>&1");
	
	
	
	
	$users=new settings_inc();
	if(!$users->CORP_LICENSE){
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *** FAILOVER License error  ***";
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
		echo "Starting......: ".date("H:i:s")." UCARP No license set, aborting...\n";
		return;
	}
	$ucarp_bin=$unix->find_program("ucarp");
	
	if(!is_file($ucarp_bin)){echo "Starting......: ".date("H:i:s")." UCARP Not installed...\n";return;}
	
	$sql="SELECT * FROM `nics` WHERE enabled=1 AND `ucarp-enable`=1 ORDER BY Interface";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		echo "Starting......: ".date("H:i:s")." UCARP: MySQL Error: $q->mysql_error\n";
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] **** FAILOVER Mysql ERROR! ****";
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
		return;
	}
	
	$count=mysql_num_rows($results);
	@unlink("/etc/network/if-up.d/ucarp");
	if($count==0){
		echo "Starting......: ".date("H:i:s")." UCARP: Network Unconfigured\n";
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] **** FAILOVER Unconfigured ****";
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
		return;
	}
	
	$FINAL[]="#!/bin/sh";
	
	$pid=ucarp_pid();
	$kill=$unix->find_program("kill");
	$ifconfig=$unix->find_program("ifconfig");
	
	$EnableChilli=$sock->GET_INFO("EnableChilli");
	$chilli=$unix->find_program("chilli");
	$nohup=$unix->find_program("nohup");
	$ETHS=array();
	
	if(!is_numeric($EnableChilli)){$EnableChilli=0;}	
	if(is_file($chilli)){
		if($EnableChilli==1){
			$ChilliConf=unserialize(base64_decode($sock->GET_INFO("ChilliConf")));
			$SKIP_INTERFACE=strtolower(trim($ChilliConf["HS_LANIF"]));
			$GLOBALS["SCRIPTS"][]="# [".__LINE__."] **** Skip $SKIP_INTERFACE with chilli ****";
		}
		
	}
	

	
	$php5=$unix->LOCATE_PHP5_BIN();
	if(is_file("/etc/init.d/ssh")){$sshcmd="$nohup /etc/init.d/ssh restart >/dev/null 2>&1 &";}
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$eth=trim(strtolower($ligne["Interface"]));
		if($SKIP_INTERFACE==$eth){
			echo "Starting......: ".date("H:i:s")." UCARP: Skipping interface: $SKIP_INTERFACE\n";
			continue;
		}
		$downfile="/usr/share/ucarp/vip-$eth-down.sh";
		$upfile="/usr/share/ucarp/vip-$eth-up.sh";
		
		system_failover_events("Configure:<br>Building configuration for $eth",__FUNCTION__,basename(__FILE__),__LINE__);
		
		$ucarpcmd=array();
		$ucarpcmd[]=$ucarp_bin;
		$ucarpcmd[]="--interface=$eth";
		$ucarpcmd[]="--srcip={$ligne["IPADDR"]}";
		$ucarpcmd[]="--vhid={$ligne["ucarp-vid"]}";
		$ucarpcmd[]="--passfile=/etc/artica-postfix/ucarppass";
		$ucarpcmd[]="--addr={$ligne["ucarp-vip"]}";
		
		if($ligne["ucarp-master"]==0){
			$advAdd=$ligne["ucarp-advskew"]+5;
			if($advAdd>255){$advAdd=255;}
			$ucarpcmd[]="--advskew=$advAdd";
		}else{
			@file_put_contents("/usr/share/ucarp/Master", $eth);
			$ucarpcmd[]="--preempt";
			$ucarpcmd[]="--advskew=1";
			$ucarpcmd[]="--advbase=1";
			
		}
		$ucarpcmd[]="--neutral";
		$ucarpcmd[]="--ignoreifstate";
		$ucarpcmd[]="--upscript=$upfile";
		$ucarpcmd[]="--downscript=$downfile";
		$ucarpcmd[]="--daemonize";
		@file_put_contents("/etc/artica-postfix/ucarppass", "secret");
		@chmod("/etc/artica-postfix/ucarppass",0700);
		$ucarpcmdLINE=@implode(" ", $ucarpcmd);
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] ****    FAILOVER $eth     ****";
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
		$GLOBALS["SCRIPTS"][]=$ucarpcmdLINE;
		$GLOBALS["SCRIPTS"][]="/usr/share/ucarp/vip-$eth-up.sh";
		
		$FINAL[]=@implode(" ", $ucarpcmd);
		

		$down=array();
		$down[]="#!/bin/sh";
		$down[]="$ifconfig $eth:ucarp down";
		$down[]="$php5 ".__FILE__." --ucarp-notify-down $1 $2 $3 $4 $5 >/dev/null 2>&1";
		$down[]="$sshcmd";
		$down[]="exit 0\n";
		@file_put_contents($downfile, @implode("\n", $down));
		@chmod($downfile, 0755);
		
		$up=array();
		$up[]="#!/bin/sh";
		$up[]="$ifconfig $eth:ucarp {$ligne["ucarp-vip"]} netmask {$ligne["NETMASK"]} up";
		$up[]="$php5 ".__FILE__." --ucarp-notify $1 $2 $3 $4 $5 >/dev/null 2>&1";
		$up[]=$sshcmd;
		$up[]="exit 0\n";
		@file_put_contents($upfile, @implode("\n", $up));
		@chmod($upfile, 0755);	
		$ETHS[$eth]=$ucarpcmdLINE;
		
	}	
	
	$FINAL[]="";
	echo "Starting......: ".date("H:i:s")." UCARP: /etc/network/if-up.d/ucarp done..\n";
	shell_exec("$php5 ".__FILE__." --ucarp-start --afterrebuild");
	@file_put_contents("/etc/network/if-up.d/ucarp", @implode("\n", $FINAL));
	@file_put_contents("/usr/share/ucarp/ETH_LIST", serialize($ETHS));
	@chmod("/etc/network/if-up.d/ucarp", 0755);
}


function ucarp_interface_active(){
	
	$unix=new unix();
	$ifconfig=$unix->find_program("ifconfig");
	exec("$ifconfig -a 2>&1",$results);
	$interface=null;
	$ipaddr=null;
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#(.+?):ucarp.*?HWaddr\s+(.+)#",$ligne,$re)){
			$interface=$re[1];
			$MAC=$re[2];
			continue;
		}
		if($interface<>null){
			if(preg_match("#inet\s+addr:([0-9\.]+)\s+Bcast:#",$ligne,$re)){
				$ipaddr=$re[1];
				break;
			}
			
		}
		
	}
	
	return array("NIC"=>$interface,"MAC"=>$MAC,"IP"=>$ipaddr);
	
}




function ucarp_start(){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();
	$sock=new sockets();
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		echo "Starting......: ".date("H:i:s")." UCARP Start task already running PID: $pid\n";
		return;
	}
	@file_put_contents($pidfile, getmypid());

	$users=new settings_inc();
	if(!$users->CORP_LICENSE){echo "Starting......: ".date("H:i:s")." UCARP No license set, aborting...\n";return;}
	$ucarp_bin=$unix->find_program("ucarp");
	if(!is_file($ucarp_bin)){echo "Starting......: ".date("H:i:s")." UCARP Not installed...\n";return;}
	
	
	if(is_file("/etc/network/if-up.d/ucarp")){
		if(!is_file("/usr/share/ucarp/ETH_LIST")){
			if(!$GLOBALS["AFTER_REBUILD"]){ucarp_build(true);}
			return;
		}
	}
	if(!is_file("/usr/share/ucarp/ETH_LIST")){echo "Starting......: ".date("H:i:s")." UCARP Not configured, Apply network parameters first (1)...\n";return;}
	$ETHS=unserialize(@file_get_contents("/usr/share/ucarp/ETH_LIST"));
	if(!is_array($ETHS)){
		echo "Starting......: ".date("H:i:s")." UCARP Not configured (2 not an array), Apply network parameters first...\n";
		return;
	}
	if(count($ETHS)==0){echo "Starting......: ".date("H:i:s")." UCARP Not configured (3)...\n";return;}
	
	while (list ($eth, $ucarpcmdLINE) = each ($ETHS) ){
		$pid=ucarp_pid($eth);
		if($unix->process_exists($pid)){
			echo "Starting......: ".date("H:i:s")." UCARP `$eth` already running pid $pid\n";
			if(ucarp_eth_ucarped($eth)){echo "Starting......: ".date("H:i:s")." UCARP `$eth` alreaded linked\n";continue;}
			shell_exec("/usr/share/ucarp/vip-$eth-up.sh");
			continue;
		}
		
		shell_exec($ucarpcmdLINE);
		sleep(1);
		$pid=ucarp_pid($eth);
		echo "Starting......: ".date("H:i:s")." UCARP `$eth` PID:$pid\n";
		if(!$unix->process_exists($pid)){
			system_failover_events("Fatal:<br>Unable to start daemon for $eth",__FUNCTION__,basename(__FILE__),__LINE__);
			echo "Starting......: ".date("H:i:s")." UCARP `$eth` failed `$ucarpcmdLINE`\n";continue;}
		if(!ucarp_eth_ucarped($eth)){
			system_failover_events("Daemon:<br>`$eth` linking to network",__FUNCTION__,basename(__FILE__),__LINE__);
			echo "Starting......: ".date("H:i:s")." UCARP `$eth` linking to network...\n";
			shell_exec("/usr/share/ucarp/vip-$eth-up.sh");
			echo "Starting......: ".date("H:i:s")." UCARP `$eth` linking to network success...\n";
		}
	}
	
}

function IPTABLES_NETWORK_BRIDGES(){
	$unix=new unix();
	
	$iptables=$unix->find_program("iptables");
	$echo=$unix->find_program("echo");
	$php=$unix->LOCATE_PHP5_BIN();
	$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
	$GLOBALS["SCRIPTS"][]="# [".__LINE__."] ****    BRIDGES     ****";
	$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
	$GLOBALS["SCRIPTS"][]="# [".__LINE__."] Removing old rules";
	$GLOBALS["SCRIPTS"][]="$php ".dirname(__FILE__)."/exec.remove.iptablesbridges.php";
	
	
	
	$q=new mysql();
	if(!$q->TABLE_EXISTS("pnic_bridges", "artica_backup")){
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] Table pnic_bridges no such table";
		$GLOBALS["SCRIPTS"][]="# Enable TimeStamps";
		$GLOBALS["SCRIPTS"][]="$echo 1 > /proc/sys/net/ipv4/tcp_timestamps";		
		return;
	}
	
	
	
	$sql="SELECT * FROM `pnic_bridges` WHERE `enabled`=1";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	
	if(!$q->ok){
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] MySQL Error $sql $q->mysql_error";
		$GLOBALS["SCRIPTS"][]="# Enable TimeStamps";
		$GLOBALS["SCRIPTS"][]="$echo 1 > /proc/sys/net/ipv4/tcp_timestamps";
		return;		
		
	}
	
	if(mysql_num_rows($results)==0){
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] No rule set in `pnic_bridges` table";
		$GLOBALS["SCRIPTS"][]="# Enable TimeStamps";
		$GLOBALS["SCRIPTS"][]="$echo 1 > /proc/sys/net/ipv4/tcp_timestamps";
		
		return;
	}
	

	$NetBuilder=new system_nic();
	$comment=" -m comment --comment \"ArticaNetworkBridges\"";
	
	$GLOBALS["SCRIPTS"][]="# Disabling TimeStamps and enable forward packets";
	$GLOBALS["SCRIPTS"][]="$echo 1 > /etc/artica-postfix/IPTABLES_BRIDGE";
	

	
}


function ucarp_eth_ucarped($eth){
	if(!isset($GLOBALS["ucarp_eth_ucarped"])){
		$unix=new unix();
		$ip=$unix->find_program("ip");
		exec("$ip addr 2>&1",$GLOBALS["ucarp_eth_ucarped"]);
	}
	reset($GLOBALS["ucarp_eth_ucarped"]);
	while (list ($index, $line) = each ($GLOBALS["ucarp_eth_ucarped"]) ){
		if(preg_match("#inet\s+([0-9\.]+).*?secondary $eth:ucarp#",$line)){
			return true;
		}
		
	}
	return false;
	
}



function ucarp_pid($eth=null){
	$unix=new unix();
	$ucarp_bin=$unix->find_program("ucarp");
	if($eth<>null){$eth=".*?--interface=$eth";}
	return $unix->PIDOF_PATTERN("$ucarp_bin$eth");
	
}
function ucarp_all_pid($eth=null){
	$unix=new unix();
	$ucarp_bin=$unix->find_program("ucarp");
	if($eth<>null){$eth=".*?--interface=$eth";}
	return $unix->PIDOF_PATTERN_ALL("$ucarp_bin$eth");

}

function reconstruct_interface($eth){
	$GLOBALS["NO_GLOBAL_RELOAD"]=true;
	if($GLOBALS["SLEEP"]){sleep(10);}
	build();
	ifupifdown($eth);
}

function events($text,$function=null,$line=null){
	$unix=new unix();
	
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			if($function==null){$function=$trace[1]["function"];}
			if($line==null){$line=$trace[1]["line"];}
		}
			
	}
	
	
	$unix->events($text,"/var/log/artica-network.log",false,$function,$line);
	
	
}
function event($text,$function=null,$line=null){events($text,$function,$line);}


function LoadProcNetDev(){
	
	$datas=explode("\n",@file_get_contents("/proc/net/dev"));
	while (list ($num, $line) = each ($datas) ){
		if(preg_match("#^(.+?):#",$line,$re)){
			$re[1]=trim($re[1]);
			$GLOBALS["SCRIPTS_TOP"][]="# [".__LINE__."] Found Network Interface <{$re[1]}>";
			$GLOBALS["PROC_NET_DEV"][$re[1]]=true;
		}
	}	
}


function build(){
	$unix=new unix();
	$users=new usersMenus();
	$q=new mysql();
	$nohup=$unix->find_program("nohup");
	$hostname_bin=$unix->find_program("hostname");
	$php5=$unix->LOCATE_PHP5_BIN();
	$sock=new sockets();
	$Myhostname=$sock->GET_INFO("myhostname");
	$oom_kill_allocating_task=$sock->GET_INFO("oom_kill_allocating_task");
	if(!is_numeric($oom_kill_allocating_task)){$oom_kill_allocating_task=1;}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$pid=@file_get_contents($pidfile);
	$sysctl=$unix->find_program("sysctl");
	$ifconfig=$unix->find_program("ifconfig");
	$GLOBALS["ipbin"]=$unix->find_program("ip");
	$GLOBALS["SCRIPTS_DOWN"]=array();
	
	
	
	if($unix->process_exists($pid,basename(__FILE__))){
		event("Building networks already executed PID: $pid",__FUNCTION__,__LINE__);
		echo "Starting......: ".date("H:i:s")." Building networks already executed PID: $pid\n";
		die();
	}	
	$fqdn=@file_get_contents("/etc/artica-postfix/FULL_HOSTNAME");
	
	if(is_file("/etc/init.d/hostname.sh")){
		if(is_file("/usr/sbin/update-rc.d")){
			shell_exec("/usr/sbin/update-rc.d -f hostname remove >/dev/null 2>&1");
			@unlink("/etc/init.d/hostname.sh");
		}
		
	}
	
	
	if($oom_kill_allocating_task==1){
		echo "Starting......: ".date("H:i:s")." Kernel oom_kill_allocating_task is enabled\n";
		shell_exec("$sysctl -w \"vm.oom_dump_tasks=1\" >/dev/null 2>&1");
		shell_exec("$sysctl -w \"vm.oom_kill_allocating_task=1\" >/dev/null 2>&1");
		
	}else{
		echo "Starting......: ".date("H:i:s")." Kernel oom_kill_allocating_task is disabled\n";
		shell_exec("$sysctl -w \"vm.oom_dump_tasks=0\" >/dev/null 2>&1");
		shell_exec("$sysctl -w \"vm.oom_kill_allocating_task=0\" >/dev/null 2>&1");		
	}
	
	if($GLOBALS["VERBOSE"]){echo "Line:".__LINE__." persistent_net_rules()\n";}
	
	persistent_net_rules();
	if($GLOBALS["VERBOSE"]){echo "Line:".__LINE__." dev_shm()\n";}
	dev_shm();
	$ip=$unix->find_program("ip");
	$echobin=$unix->find_program("echo");
	$logger=$unix->find_program("logger");
	$IPROUTEFOUND=false;
	exec("$ip route",$results);
	events("IP route -> ".count($results)." lines",__FUNCTION__,__LINE__);
	
	while (list ($index, $line) = each ($results) ){
		events("IP route -> $line",__FUNCTION__,__LINE__);
	
		if(preg_match("#default via#", $line)){
			events("IP route found default via -> $line",__FUNCTION__,__LINE__);
			$IPROUTEFOUND=true;
		}
		
	}
	
	if(!$IPROUTEFOUND){@unlink("/etc/artica-postfix/MEM_INTERFACES");}
	
	if(is_file("/etc/artica-postfix/MEM_INTERFACES")){
		$MEM_INTERFACES=unserialize(@file_get_contents("/etc/artica-postfix/MEM_INTERFACES"));
	}
	
	if($GLOBALS["VERBOSE"]){echo "Line:".__LINE__." MEM_INTERFACES()\n";}
	$EXECUTE_CMDS=true;
	
	
	if(is_array($MEM_INTERFACES)){
		$EXECUTE_CMDS=false;
		if($GLOBALS["VERBOSE"]){echo "Line:".__LINE__." NETWORK_ALL_INTERFACES()\n";}
		$array=$unix->NETWORK_ALL_INTERFACES();
		while (list ($Interface, $ipaddr) = each ($MEM_INTERFACES) ){
			if($ipaddr==null){continue;}
			if($GLOBALS["VERBOSE"]){echo "Line:".__LINE__." $Interface Must be $ipaddr -> {$array[$Interface]["IPADDR"]}\n";}
			events("$Interface Must be $ipaddr -> {$array[$Interface]["IPADDR"]}",__FUNCTION__,__LINE__);
			if($ipaddr<>$array[$Interface]["IPADDR"]){
				events("Must rebuilded....",__FUNCTION__,__LINE__);
				$EXECUTE_CMDS=true;
				break;
			}
		}
	}
	
	if($q->mysql_server=="127.0.0.1"){
		if(!$unix->is_socket("/var/run/mysqld/mysqld.sock")){
			event("/var/run/mysqld/mysqld.sock no such socket",__FUNCTION__,__LINE__);
			echo "Starting......: ".date("H:i:s")." Building networks MySQL database not available starting MySQL service...\n";
			shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.initd-mysql.php >/dev/null 2>&1 &");
			shell_exec("$nohup /etc/init.d/mysql start >/dev/null 2>&1 &");
			sleep(1);
			for($i=0;$i<5;$i++){
				$q=new mysql();
				if(!is_file("/var/run/mysqld/mysqld.sock")){
					echo "Starting......: ".date("H:i:s")." Building networks waiting MySQL database to start...$i/4\n";
					sleep(1);
				}else{
					break;
				}
			}
			if(!$unix->is_socket("/var/run/mysqld/mysqld.sock")){
				event("/var/run/mysqld/mysqld.sock no such socket",__FUNCTION__,__LINE__);
				echo "Starting......: ".date("H:i:s")." Building networks MySQL database not available...\n";
				die();
			}
			
		}
	}
	
	
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.initd-mysql.php >/dev/null 2>&1 &");

	if(!$q->BD_CONNECT()){
		sleep(1);
		event("Building networks MySQL database not available starting MySQL service",__FUNCTION__,__LINE__);
		echo "Starting......: ".date("H:i:s")." Building networks MySQL database not available starting MySQL service...\n";
		shell_exec("$nohup /etc/init.d/mysql start >/dev/null 2>&1 &");
		
		for($i=0;$i<5;$i++){
			$q=new mysql();
			if(!$q->BD_CONNECT()){
				echo "Starting......: ".date("H:i:s")." Building networks waiting MySQL database to start...$i/4\n";
				sleep(1);
			}else{
				break;
			}
			
		}
		
		$q=new mysql();
		
		if(!$q->BD_CONNECT()){
			event("Building networks MySQL database not available...",__FUNCTION__,__LINE__);
			echo "Starting......: ".date("H:i:s")." Building networks MySQL database not available...\n";
			die();
		}
		
	}
	
	
	if(!$q->TABLE_EXISTS("nics","artica_backup",true)){
		echo "Starting......: ".date("H:i:s")." Building networks MySQL table is not yet builded..\n";
		die();
	}

	$GLOBALS["SAVED_INTERFACES"]=array();
	Checkipv6();
	@file_put_contents($pidfile,getmypid());

	
	
	echo "Starting......: ".date("H:i:s")." Building networks checking bridge\n";
	bridges_build();
	echo "Starting......: ".date("H:i:s")." Building networks checking IPV6\n";
	Checkipv6();
	
	$nic=new system_nic();
	$datas=$nic->root_build_debian_config();
	
	echo "Starting......: ".date("H:i:s")." Building networks Reloading ". count($GLOBALS["SAVED_INTERFACES"])." interface(s)\n";
	
	
	if(count($GLOBALS["SAVED_INTERFACES"])==0){
		echo "Starting......: ".date("H:i:s")." Building networks Building Ipv6 virtuals IP...\n";
		Checkipv6Virts();
	}
	
	$EXECUTE_CMDS=false;
	
	
	
	if(is_file("/etc/init.d/hostname.sh")){
		if(is_file("/usr/sbin/update-rc.d")){
			shell_exec("/usr/sbin/update-rc.d -f hostname remove >/dev/null 2>&1");
			@unlink("/etc/init.d/hostname.sh");
		}
	
	}
	
	LoadProcNetDev();
	$GLOBALS["SCRIPTS_TOP"][]="# [".__LINE__."]";
	$GLOBALS["SCRIPTS_TOP"][]="# [".__LINE__."] *******************************";
	$GLOBALS["SCRIPTS_TOP"][]="# [".__LINE__."] **** SETTINGS for LOOP BACK ***";
	$GLOBALS["SCRIPTS_TOP"][]="# [".__LINE__."] *******************************";
	$GLOBALS["SCRIPTS_TOP"][]="# [".__LINE__."]";	
	
	$ModeProbeAlx=intval($sock->GET_INFO("ModeProbeAlx"));
	$GLOBALS["SCRIPTS_TOP"][]="# [".__LINE__."] ALX driver: $ModeProbeAlx";
	if($ModeProbeAlx==1){
		$modprobe=$unix->find_program("modprobe");
		$GLOBALS["SCRIPTS_TOP"][]="$modprobe alx";
		
	}
	
	$GLOBALS["SCRIPTS_TOP"][]="$ifconfig lo 127.0.0.1 up";
	if($Myhostname<>null){$GLOBALS["SCRIPTS_TOP"][]="$hostname_bin \"$Myhostname\"";}
	$GLOBALS["SCRIPTS_TOP"][]="# [".__LINE__."]";
	$datas=$nic->networks_disabled();
	

	
	
	$sh=array();
	$sh[]="#!/bin/sh -e";
	$sh[]="### BEGIN INIT INFO";
	$sh[]="# Builded on ". date("Y-m-d H:i:s");
	$sh[]="# Provides:          artica-ifup";
	$sh[]="# Required-Start:    mountkernfs \$local_fs";
	$sh[]="# Required-Stop:     \$local_fs";
	$sh[]="# Should-Start:		ifupdown";
	$sh[]="# Should-Stop:		ifupdown";
	$sh[]="# Default-Start:     S";
	$sh[]="# Default-Stop:      0 6";
	$sh[]="# Short-Description: start and stop the network";
	$sh[]="# Description:       Artica ifup service Raise network interfaces";
	$sh[]="### END INIT INFO";
	$sh[]="case \"\$1\" in";
	$sh[]="start)";
	$sh[]="$logger \"kernel: [  Artica-Net] Artica network Script executed (start)\" || true";
	$mkdir=$unix->find_program("mkdir");
	$sh[]="mkdir -p /run/network >/dev/null 2>&1";
	$sh[]="$php5 /usr/share/artica-postfix/exec.virtuals-ip-notify.php --start $2 $3 || true";
	
	
	
	
	etc_hosts();
	routes_main();
	ucarp_build(true);
	bridges_build();
	
	IPTABLES_NETWORK_BRIDGES();
	
	
	$sh[]="$echobin \"\" > /var/log/net-start.log";
	$sh[]="$echobin \"  **** Apply Network configuration, please wait... ****\"";
	
	while (list ($index, $line) = each ($GLOBALS["SCRIPTS_TOP"]) ){
		$line=trim($line);
		if($line==null){continue;}
		if(substr($line, 0,1)=="#"){$sh[]=ScriptInfo($line);continue;}
		$md=md5($line);
		if(isset($AL[$md])){
			echo "Starting......: ".date("H:i:s")." SKIPING `$line`\n";
			continue;
		}
		$AL[$md]=true;
		echo "Starting......: ".date("H:i:s")." `$line`\n";
		
		if(strpos($line, "/etc/hosts")>0){
			$sh[]="$line";
			continue;
		}
		
		if(preg_match("#ifconfig\s+(.+?)\s+(.+?)netmask(.+?)\s+#", $line,$re)){
			$sh[]="$echobin \"adding {$re[2]}/{$re[3]} in {$re[1]} interface\"";
				
		}
		
		$sh[]="$echobin \"$line\" >>/var/log/net-start.log 2>&1";
		$sh[]="$line >>/var/log/net-start.log 2>&1 || true";	
		
	}


	while (list ($index, $line) = each ($GLOBALS["SCRIPTS"]) ){
		
			$line=trim($line);
			if($line==null){continue;}
			if(substr($line, 0,1)=="#"){$sh[]=ScriptInfo($line);continue;}
			
			if(preg_match("#^OUTPUT\s+(.+)#",$line,$re)){
				$line=str_replace('"' ,"'", $line);
				$sh[]="$echobin \"{$re[1]}\"";
				continue;
			}
				
			
			
			$md=md5($line);
			if(isset($AL[$md])){
				echo "Starting......: ".date("H:i:s")." SKIPING `$line`\n";
				continue;
			}
			$AL[$md]=true;		
			echo "Starting......: ".date("H:i:s")." `$line`\n";
			
			if(strpos($line, "/etc/hosts")>0){
				$sh[]="$line";
				continue;
			}			
		
		
		if(preg_match("#ifconfig\s+(.+?)\s+(.+?)netmask(.+?)\s+#", $line,$re)){
			$sh[]="$echobin \"adding {$re[2]}/{$re[3]} in {$re[1]} interface\"";
			
		}	
			
		if(strpos('echo "', $line)==0){
			$sh[]="$echobin \"$line\" >>/var/log/net-start.log 2>&1";
		}
		$sh[]="$line >>/var/log/net-start.log 2>&1 || true"; 
	}
	
	if(count($GLOBALS["SCRIPTS_ROUTES"])>0){
		$GLOBALS["START_ROUTES"][]="$echobin \"Apply network routes, please wait...\"";
		$sh[]="";
		$sh[]="# [".__LINE__."]";
		$sh[]="# [".__LINE__."] *******************************";
		$sh[]="# [".__LINE__."] ****     NETWORK ROUTES    ****";
		$sh[]="# [".__LINE__."] *******************************";
		$sh[]="# [".__LINE__."]";
		while (list ($index, $line) = each ($GLOBALS["SCRIPTS_ROUTES"]) ){
		
			$line=trim($line);
			if($line==null){continue;}
			if(substr($line, 0,1)=="#"){
				$ScriptInfo=ScriptInfo($line);
				$sh[]=$ScriptInfo;
				$GLOBALS["START_ROUTES"][]=$ScriptInfo;
				continue;
			}
			$md=md5($line);
			
			
			if(isset($AL[$md])){
				if(!preg_match("#^force#", $line)){
					echo "Starting......: ".date("H:i:s")." SKIPING `$line`\n";
					continue;
				}
			}
			
			if(preg_match("#^force:(.+)#", $line,$re)){$line=$re[1];$md=md5($line);}
			
			$AL[$md]=true;
			
			
			if(preg_match("#ip route add (.+?)\s+.*?src\s+(.+)#",$line,$re)){
				$GLOBALS["START_ROUTES"][]="$echobin \"Create route for network {$re[1]} for local address {$re[2]}\"";
				$sh[]="$echobin \"Create route for network {$re[1]} for local address {$re[2]}\"";
			}
			
			if(preg_match("#ip route add (.+?)\s+via(.+?)\s+src\s+([0-9\.]+)#",$line,$re)){
				$GLOBALS["START_ROUTES"][]="$echobin \"Create route for network {$re[1]} using gateway {$re[2]} for local address {$re[3]}\"";
				$sh[]="$echobin \"Create route for network {$re[1]} using gateway {$re[2]} for local address {$re[3]}\"";
			}
	
			$GLOBALS["START_ROUTES"][]="$echobin \"$line\" >>/var/log/net-start.log 2>&1";
			$sh[]="$echobin \"$line\" >>/var/log/net-start.log 2>&1";
			if(preg_match("#\/echo\s+#", $line)){$sh[]=$line;continue;}
			$sh[]="$line >>/var/log/net-start.log 2>&1 || true";
			$GLOBALS["START_ROUTES"][]="$line >>/var/log/net-start.log 2>&1 || true";
			
		}	
	
	}
	
	
	
	
		
	$sh[]="if [ -x /etc/init.d/artica-ifup-content.sh ] ; then";
	$sh[]="	/etc/init.d/artica-ifup-content.sh || true";
	$sh[]="fi";
	

	
	$sh[]=nics_vde_build();
	
	
	
	$EnablePDNS=$sock->GET_INFO("EnablePDNS");
	if(!is_numeric($EnablePDNS)){$EnablePDNS=0;}

	$unix=new unix();
	$squid=$unix->LOCATE_SQUID_BIN();
	$ip=$unix->find_program("ip");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	$monit=$unix->find_program("monit");
	$ifconfig=$unix->find_program("ifconfig");
	
	
	
	$php=$unix->LOCATE_PHP5_BIN();
	if(is_file($squid)){
		$sh[]="# [".__LINE__."] Reloading squid";
		$sh[]="$echo \"Reloading squid ( if exists )\"";
		$sh[]="$nohup $php /usr/share/artica-postfix/exec.squid.php --kreconfigure 2>&1 >>/var/log/net-start.log 2>&1 &";
	}
	
	$sh[]="# [".__LINE__."] Flushing ARP cache";
	$sh[]="$echo \"Flushing ARP cache...\"";
	$sh[]="ip -s -s neigh flush all >>/var/log/net-start.log 2>&1 || true";
	
	$sh[]="# [".__LINE__."] Tune the kernel";
	$sh[]="$echo \"Tuning the kernel...\"";
	$sh[]="$php5 /usr/share/artica-postfix/exec.sysctl.php --build >>/var/log/net-start.log 2>&1 || true";
	$sh[]="if [ -x /bin/artica-firewall.sh ] ; then";
	$sh[]="	/bin/artica-firewall.sh || true";
	$sh[]="fi";
	
	
	
	if(is_file("/etc/init.d/ssh")){
		$sh[]="# [".__LINE__."] Starting sshd";
		$sh[]="$echo \"Starting sshd\"";
		$sh[]="/etc/init.d/ssh start 2>&1 || true";
	}
	
	$sh[]="# [".__LINE__."] Starting FrameWork";
	$sh[]="$echo \"Starting FrameWork\"";
	$sh[]="$nohup $php5 /usr/share/artica-postfix/exec.framework.php --start >/dev/null 2>&1 &";
	
	$sh[]="# [".__LINE__."] Starting Meta Server Client";
	$sh[]="$echo \"Starting FrameWork\"";
	$sh[]="$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-client.php --ping --force >/dev/null 2>&1 &";
	
	
	if($EnablePDNS==1){
		$sh[]="# [".__LINE__."] Reloading PowerDNS...";
		$sh[]="$echo \"Reloading PowerDNS\"";
		$sh[]="$php5 /usr/share/artica-postfix/exec.pdns.php --reload 2>&1 || true";
	}
	
	if(is_file($monit)){
		$sh[]="# [".__LINE__."] Starting Monit in background";
		$sh[]="$echo \"Starting Monit in background\"";
		$sh[]="$nohup $monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid -s /var/run/monit/monit.state >/dev/null 2>&1 &";		
		
	}
	$mount=$unix->find_program("mount");
	if(is_file($mount)){
		$sh[]="# [".__LINE__."] Mount all system after network set";
		$sh[]="$echo \"Starting mount in background\"";
		$sh[]="$nohup $mount -a >/dev/null 2>&1 &";
	}
	
	$sh[]="if [ -x /bin/iptables-iproute2.sh ] ; then";
	$sh[]="	/bin/iptables-iproute2.sh || true";
	$sh[]="fi";
	
	
	
	
	
	
	$sh[]="# [".__LINE__."] Reloading DHCPD (if exists)";
	$sh[]="$echo \"Reloading DHCP server ( if exists )\"";
	$sh[]="$php5 /usr/share/artica-postfix/exec.dhcpd.compile.php --reload-if-run 2>&1 || true";	
	$sh[]="$echo \"  ****      Apply Network configuration, done      ****\"";
	$sh[]=";;";
	$sh[]="  stop)";
	$sh[]="$logger \"* * * * * * * * * * * * * * SUSPECTED STOPPED SERVER !!! * * * * * * * * * * * * * *\" || true";
	$sh[]="$logger \"kernel: [  Artica-Net] Artica network Script executed (stop)\" || true";
	if(is_array($GLOBALS["SCRIPTS_DOWN"])){
		while (list ($index, $line) = each ($GLOBALS["SCRIPTS_DOWN"]) ){	
			if(substr($line, 0,1)=="#"){$sh[]=ScriptInfo($line);continue;}
			$sh[]="$line >>/var/log/net-stop.log 2>&1 || true";
			
		}
	}

	
	$php=$unix->LOCATE_PHP5_BIN();
	$sh[]=";;";	
	$sh[]="reconfigure)";
	$sh[]="$logger \"kernel: [  Artica-Net] Artica network Script Executed (reconfigure)\" || true";
	$sh[]="$php ".__FILE__." --build --force $2 $3";
	$sh[]="/etc/init.d/artica-ifup start";
	$sh[]=";;";
	$sh[]="routes)";
	$sh[]="$logger \"kernel: [  Artica-Net] Artica network Script Executed (routes)\" || true";
	$sh[]="# Array of ".count($GLOBALS["START_ROUTES"]);
	$sh[]="$echobin \"Flushing routes tables...\"";
	$sh[]="{$GLOBALS["ipbin"]} route flush table all";
	$sh[]="$echobin \"$ifconfig lo 127.0.0.1 down\"";
	$sh[]="$ifconfig lo 127.0.0.1 down || true";
	$sh[]="$echobin \"$ifconfig lo 127.0.0.1 up\"";
	$sh[]="$ifconfig lo 127.0.0.1 up || true";
	$sh[]="$echobin \"Apply routes to the system\"";
	$sh[]="$echobin \"Running routes\" > /var/log/net-start.log 2>&1";
	$sh[]=@implode("\n", $GLOBALS["START_ROUTES"]);
	$sh[]="$echobin \"Routes applied to the system\"";
	
	
	
	$sh[]=";;";
	
	
	
	$sh[]="*)";
	$sh[]="$logger \"kernel: [  Artica-Net] Artica network Script executed (unknown)\" || true";
	$sh[]=" echo \"Usage: $0 {start or reconfigure only}\"";
	$sh[]="exit 1";
	$sh[]=";;";
	$sh[]="esac";
	$sh[]="exit 0\n";
	

	@file_put_contents("/etc/init.d/artica-ifup", @implode("\n", $sh));
	@chmod("/etc/init.d/artica-ifup",0755);
	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f artica-ifup defaults >/dev/null 2>&1");
		
		if(is_file('/etc/init.d/networking')){
			shell_exec("/usr/sbin/update-rc.d -f networking disable  >/dev/null 2>&1"); 
			@copy("/etc/init.d/networking","/etc/init.d/networking.back");
			@unlink("/etc/init.d/networking");
		}
	}
	
	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add artica-ifup >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 1234 artica-ifup on >/dev/null 2>&1");
	}
	
	if(is_file("/bin/iptables-iproute2.sh")){@unlink("/bin/iptables-iproute2.sh");}
	
	if(isset($GLOBALS["SCRIPTS_IPTABLES"])){
		$sh_iptables[]="#!/bin/sh -e";
		$sh_iptables[]=@implode("\n", $GLOBALS["SCRIPTS_IPTABLES"]);
		$sh_iptables[]="";
		@file_put_contents("/bin/iptables-iproute2.sh", @implode("\n", $sh_iptables));
		@chmod("/bin/iptables-iproute2.sh",0755);
	}

	
	$inter[]="# This file describes the network interfaces available on your system";
	$inter[]="## and how to activate them. For more information, see interfaces(5).";
	$inter[]="";
	$inter[]="## The loopback network interface";
	$inter[]="auto lo";
	$inter[]="iface lo inet loopback";
	$inter[]="";
	$inter[]="";
	if(is_file("/etc/network/interfaces")){ @file_put_contents("/etc/network/interfaces", @implode("\n", $inter)); }
	
	squid_admin_mysql(1, "Network script was rebuilded", null,__FILE__,__LINE__);
	
	echo "Starting......: ".date("H:i:s")." Building FireWall rules.\n";
	system("$php5 /usr/share/artica-postfix/exec.firehol.php --build");
	echo "Starting......: ".date("H:i:s")." done...\n";
	
}

function BuildNetWorksDebian(){
	if(!is_file("/etc/network/interfaces")){return;}
	
	
	
	
	echo "Starting......: ".date("H:i:s")." Building networks mode Debian\n";
	$nic=new system_nic();
	
	$datas=$nic->root_build_debian_config();
	if($datas==null){
		events("Not yet configured");
		echo "Starting......: ".date("H:i:s")." Not yet configured\n";
		return;
	}
	
	echo "Starting......: ".date("H:i:s")." ". strlen($datas)." bytes length\n";
	bridges_build();
	$unix=new unix();
	$unix->THREAD_COMMAND_SET($unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.ip-rotator.php --build");
	
	}

function BuildNetWorksRedhat(){
	
	echo "Starting......: ".date("H:i:s")." Building networks mode RedHat\n";
	$nic=new system_nic();
	$datas=$nic->root_build_redhat_config();
	bridges_build();
	$unix=new unix();
	$unix->THREAD_COMMAND_SET($unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.ip-rotator.php --build");
	if(!$GLOBALS["NO_GLOBAL_RELOAD"]){$unix->NETWORK_REDHAT_RESTART();}
	}


function ifconfig_tests(){
	$unix=new unix();
	$cmd=$unix->find_program("ifconfig")." -s";
	exec($cmd,$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#^(.+?)\s+[0-9]+#",$line,$re)){
			$array[trim($re[1])]=trim($re[1]);
		}
	}
	print_r($array);
	
}

function nics_vde_build(){
	if(isset($GLOBALS["nics_vde_build"])){return;}
	$GLOBALS["nics_vde_build"]=true;
}


	


function bridges_build(){
	if(isset($GLOBALS["bridges_build_executed"])){return;}
	$GLOBALS["bridges_build_executed"]=true;
	$unix=new unix();
	$iptables=$unix->find_program("iptables");
	
	$php5=$unix->LOCATE_PHP5_BIN();
	$iptables_rules=array();
	$sql="SELECT * FROM iptables_bridge ORDER BY ID DESC";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");	
	if(!$q->ok){return null;}
	
	$GLOBALS["SCRIPTS"][]="";
	$GLOBALS["SCRIPTS"][]="# [".__LINE__."]";
	$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
	$GLOBALS["SCRIPTS"][]="# [".__LINE__."] ****   Iptables Bridges    ****";
	$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
	$GLOBALS["SCRIPTS"][]="# [".__LINE__."]";
	$GLOBALS["SCRIPTS"][]="# [".__LINE__."]:". mysql_num_rows($results). " rule(s)";
	$GLOBALS["SCRIPTS"][]="$php5 ". __FILE__." --iptables-bridge-delete";
	if(mysql_num_rows($results)==0){return;}
	$sysctl=$unix->find_program("sysctl");
	$GLOBALS["SCRIPTS"][]="$sysctl -w net.ipv4.ip_forward=1";
	
	$NetBuilder=new system_nic();
	$NetBuilder->LoadTools();
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
		if($ligne["nics_virtuals_id"]>0){
			$array_virtual_infos=VirtualNicInfosIPaddr($ligne["nics_virtuals_id"]);
			$nicvirtual=$array_virtual_infos["IPADDR"];
			if($nicvirtual==null){continue;}
			
		}
		
		if($ligne["nic_inbound"]<>null){
			$nicvirtual=$ligne["nic_inbound"];
			$nicvirtual=$NetBuilder->NicToOther($nicvirtual);
		}
		
		$nic_linked=trim($ligne["nic_linked"]);
		if(trim($nic_linked)==null){continue;}
		if(trim($nicvirtual)==null){continue;}
		
		if(preg_match("#(.+?):([0-9]+)#",$nic_linked,$re)){
			$array_virtual_infos=VirtualNicInfosIPaddr($re[2]);
			$nic_linked=$array_virtual_infos["IPADDR"];
		}
		
		$id=$ligne["ID"];
		$nic_linked=$NetBuilder->NicToOther($nic_linked);
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."]: [$id] Virtuals bridge $nicvirtual to $nic_linked";
		$GLOBALS["SCRIPTS"][]="$iptables -A FORWARD -i $nicvirtual -o $nic_linked -m state --state ESTABLISHED,RELATED -j ACCEPT -m comment --comment \"ArticaBridgesVirtual:$id\" 2>&1";
		$GLOBALS["SCRIPTS"][]="$iptables -A FORWARD -i $nicvirtual -o $nic_linked -j ACCEPT -m comment --comment \"ArticaBridgesVirtual:$id\" 2>&1";
		$GLOBALS["SCRIPTS"][]="$iptables -t nat -A POSTROUTING -o $nic_linked -j MASQUERADE	-m comment --comment \"ArticaBridgesVirtual:$id\" 2>&1";	
		
	}

}

function bridges_delete(){
	$unix=new unix();
	echo "Starting......: ".date("H:i:s")." Virtuals bridge Deleting old rules\n";
	$iptables_save=$unix->find_program("iptables-save");
	$iptables_restore=$unix->find_program("iptables-restore");
	$conf=null;
	$cmd="$iptables_save > /etc/artica-postfix/iptables.conf";
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." $cmd\n";}		
	shell_exec($cmd);

	
	$data=file_get_contents("/etc/artica-postfix/iptables.conf");
	$datas=explode("\n",$data);
	$pattern="#.+?ArticaBridgesVirtual#";	
	$count=0;
while (list ($num, $ligne) = each ($datas) ){
		if($ligne==null){continue;}
		if(preg_match($pattern,$ligne)){
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." Delete $ligne\n";}		
			$count++;continue;}
			$conf=$conf . $ligne."\n";
		}

file_put_contents("/etc/artica-postfix/iptables.new.conf",$conf);
$cmd="$iptables_restore < /etc/artica-postfix/iptables.new.conf";
if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." $cmd\n";}
shell_exec("$cmd");
echo "Starting......: ".date("H:i:s")." Virtuals bridge cleaning iptables $count rules\n";	
}


function ifconfig_parse($path=null){
	$unix=new unix();
	print_r($unix->NETWORK_DEBIAN_PARSE_ARRAY($path));
	
}

function routes_fromfile(){
	
	if(!is_file("/etc/artica-postfix/ROUTES.CACHES.TABLES")){
		echo "Starting......: ".date("H:i:s")." Building routes, no cache file\n";
		return;
	}
	
	$unix=new unix();
	$route=$unix->find_program("route");
	$ip=$unix->find_program("ip");

	$f=explode("\n",@file_get_contents("/etc/iproute2/rt_tables"));
	while (list ($eth, $ligne) = each ($f) ){
		if(preg_match("#^([0-9]+)\s+(.+)#", $ligne,$re)){
			$tableID=$re[1];
			if($tableID==255){continue;}
			if($tableID==254){continue;}
			if($tableID==253){continue;}
			$array[$tableID]=$re[2];
		}
	
	}
	
	while (list ($id, $ligne) = each ($array) ){
		shell_exec("$ip route flush table $ligne");
	
	}	
	
	
	$array=unserialize("/etc/artica-postfix/ROUTES.CACHES.TABLES");
	$TABLES=$array["TABLES"];
	$NEXT=$array["NEXT"];
	$CMDS=$array["CMDS"];
	
	while (list ($id, $cmdline) = each ($CMDS) ){
		shell_exec($cmdline);
	}
	
	
	$f[]="255\tlocal";
	$f[]="254\tmain";
	$f[]="253\tdefault";
	$f[]="0\tunspec";
	$c=1;
	if(count($TABLES)>0){
		while (list ($id, $ligne) = each ($TABLES) ){
			$f[]="$c\t$ligne";
				
		}
	
	file_put_contents("/etc/iproute2/rt_tables", @implode("\n", $f));
			while (list ($id, $cmdline) = each ($NEXT) ){
				echo "$cmdline\n";
				shell_exec($cmdline);
			}
		}
		reset($TABLES);
		reset($NEXT);
		
	
}


function PARSECDR($pattern){
	if($pattern==null){return;}
	if(strpos($pattern, "/")==0){return $pattern;}
	
	$re=explode("/",$pattern);
	if(strpos($re[1], ".")>0){
		$tcp=new Unixipv4($re[0], $re[1]);
		return $tcp->NetMaskToCdir();
	}
	
	return $pattern;
	
	
}

function routes_main_build(){
	routes();
	routes_main();
	$unix=new unix();
	$route=$unix->find_program("route");
	$ip=$unix->find_program("ip");

	
	if(count($GLOBALS["SCRIPTS"])==0){echo "No route to build\n";return;}
	
	
	
		
	while (list ($index, $line) = each ($GLOBALS["SCRIPTS"]) ){
		$line=trim($line);
		if($line==null){continue;}
		$md=md5($line);
		if(isset($AL[$md])){continue;}
		$AL[$md]=true;
		echo "Starting......: ".date("H:i:s")." `$line`\n";
		system($line);
	}
	
	
	
		
}

function alphaToNum($name){
$array["a"]="11";
$array["b"]="12";	
$array["c"]="13";
$array["d"]="14";
$array["e"]="15";
$array["f"]="16";
$array["g"]="17";
$array["h"]="18";
$array["i"]="19";
$array["j"]=10;
$array["k"]=11;
$array["l"]=12;
$array["m"]=13;
$array["n"]=14;
$array["o"]=15;
$array["p"]=16;
$array["q"]=17;
$array["r"]=18;
$array["s"]=19;
$array["t"]=20;
$array["u"]=21;
$array["v"]=22;
$array["w"]=23;
$array["x"]=24;
$array["y"]=25;
$array["z"]=26;

$lengt=strlen($name);
for ($i = 0; $i < $lengt+1; $i++) {
	if(!isset($name[$i])){continue;}
	$Letter=$name[$i];
	if(is_numeric($Letter)){$tt[]=$Letter;continue;}
	$tt[]=$array[$Letter];
}

	return @implode("", $tt);
}

function isGatewayGood($ipaddr){
	if(trim($ipaddr)==null){return false;}
	if(trim($ipaddr)=="0.0.0.0"){return false;}
	return true;
}


function routes_main(){
	$MetricCount=0;
	$unix=new unix();
	LoadProcNetDev();
	$RULES_TABLES=array();
	$GLOBALS["ifconfig"]=$unix->find_program("ifconfig");
	$GLOBALS["routebin"]=$unix->find_program("route");
	$GLOBALS["echobin"]=$unix->find_program("echo");
	$GLOBALS["ipbin"]=$unix->find_program("ip");
	$GLOBALS["vconfigbin"]=$unix->find_program("vconfig");
	$GLOBALS["moprobebin"]=$unix->find_program("modprobe");	
	$GLOBALS["ethtoolbin"]=$unix->find_program("ethtool");
	
	
	
	$sock=new sockets();
	$OVHNetConfig=$sock->GET_INFO("OVHNetConfig");
	if(!is_numeric($OVHNetConfig)){$OVHNetConfig=0;}
	$NetWorkBroadCastAsIpAddr=$sock->GET_INFO("NetWorkBroadCastAsIpAddr");
	$EnableChilli=$sock->GET_INFO("EnableChilli");
	if(!is_numeric($EnableChilli)){$EnableChilli=0;}
	if($EnableChilli==1){
		$ChilliConf=unserialize(base64_decode($sock->GET_INFO("ChilliConf")));
		echo "Starting......: ".date("H:i:s")." Will skip {$ChilliConf["HS_LANIF"]} for HotSpot config\n";
		$eth_SKIP[$ChilliConf["HS_LANIF"]]=true;
	}	
	
	
	
	$route=$unix->find_program("route");
	$ip=$unix->find_program("ip");
	$types[1]="{network_nic}";
	$types[2]="{host}";
	
	$endcmdsline=array();
	$q=new mysql();
	if(!$q->FIELD_EXISTS("nics","UseSPAN","artica_backup")){$sql="ALTER TABLE `nics` ADD `UseSPAN` smallint(1) NOT NULL DEFAULT 0,ADD INDEX ( `UseSPAN` )";$q->QUERY_SQL($sql,'artica_backup');}
	
	$NetBuilder=new system_nic();
	$NetBuilder->LoadTools();
	$php=$unix->LOCATE_PHP5_BIN();
	
	$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."]";
	$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."] *******************************";
	$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."] ****     MAIN ROUTES       ****";
	$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."] *******************************";
	$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."]";
	
	$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["ipbin"]} route add 127.0.0.1 dev lo";
	//$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["routebin"]} add -net 127.0.0.0 netmask 255.255.255.0 lo";
	
	
	$sql="SELECT * FROM  `nics` WHERE defaultroute=1 AND enabled=1 and UseSPAN=0 ORDER BY Interface";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$eth=trim($ligne["Interface"]);
	$metric=$ligne["metric"];
	$SourceBasedRouting=intval($ligne["SourceBasedRouting"]);
	$eth=str_replace("\r\n", "", $eth);
	$eth=str_replace("\r", "", $eth);
	$eth=str_replace("\n", "", $eth);
	
	if(isset($eth_SKIP[$eth])){$eth=null;}
	if($ligne["GATEWAY"]==null){$eth=null;}
	if($ligne["GATEWAY"]=="0.0.0.0"){$eth=null;}
	if($ligne["NETMASK"]=="0.0.0.0"){$eth=null;}
	$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["echobin"]} \"\" > /etc/iproute2/rt_tables";
	$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["echobin"]} \"255	local\" >> /etc/iproute2/rt_tables";
	$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["echobin"]} \"254	main\" >> /etc/iproute2/rt_tables";
	$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["echobin"]} \"253	default\" >> /etc/iproute2/rt_tables";
	$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["echobin"]} \"0	unspec\" >> /etc/iproute2/rt_tables";
	
	
	$GLOBALS["SCRIPTS_ROUTES"][]="$php /usr/share/artica-postfix/exec.network.ip-rules.flush.php";
	
	
	/*$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["ipbin"]} rule add from all lookup local";
	$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["ipbin"]} rule add from all lookup main";
	$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["ipbin"]} rule add from all lookup default";
*/
	
	
	
	
	if($q->TABLE_EXISTS("routing_rules", "artica_backup")){
		$routing_rules=$q->QUERY_SQL("SELECT * FROM routing_rules WHERE enabled=1","artica_backup");
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." Mysql error : $q->mysql_error\n";return;}
		
		while ($routing_ligne = mysql_fetch_assoc($routing_rules)) {
			$RouteName=$routing_ligne["RouteName"];
			$nic=$routing_ligne["nic"];
			$ID=$routing_ligne["ID"];
			$RULES_TABLES[$nic]=$ID;
			$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."] Advanced route for $nic table ID $ID";
			$GLOBALS["SCRIPTS_ROUTES"][]="while ip rule delete from 0/0 to 0/0 table $RouteName 2>/dev/null; do true; done";
			$GLOBALS["SCRIPTS_ROUTES"][]="while ip rule delete dev $nic 2>/dev/null; do true; done";
			
			if($nic==$eth){$eth=null;}
			
			$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["echobin"]} \"$ID $RouteName\" >> /etc/iproute2/rt_tables";
		}
	}
	
	
	
	
	if($eth<>null){
		if(!isset($GLOBALS["DEFAULT_ROUTE_SET"])){
			$GLOBALS["SCRIPTS_ROUTES"][]="# [$eth/".__LINE__."] is set as default route Source Based Routing=$SourceBasedRouting";
			$GLOBALS["DEFAULT_ROUTE_SET"]=$eth;
			$NETMASK=$ligne["NETMASK"];
			$CDIR=$NetBuilder->GetCDIRNetwork($ligne["IPADDR"],$ligne["NETMASK"]);
			$md5net=md5($CDIR);
			$GLOBALS["MD5NET"][$md5net]=true;
			$metric=$ligne["metric"];
			$metric_text=null;
			if($metric>0){$metric_text=" metric $metric";}
			$MetricCount++;
			$GLOBALS["SCRIPTS_ROUTES"][]="#[$eth/".__FUNCTION__."/".__LINE__." IP:{$ligne["IPADDR"]}/$CDIR gateway:{$ligne["GATEWAY"]} Source Based Routing=$SourceBasedRouting";
			//$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["ifconfig"]} ".$NetBuilder->NicToOther($eth)." down";
			//$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["ifconfig"]} ".$NetBuilder->NicToOther($eth)." up";
			if($SourceBasedRouting==0){
				if(isGatewayGood($ligne["GATEWAY"])){
					$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["routebin"]} add -host {$ligne["GATEWAY"]} dev ".$NetBuilder->NicToOther($eth);
					$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["routebin"]} add -net 0.0.0.0 gw {$ligne["GATEWAY"]} dev ".$NetBuilder->NicToOther($eth) ." metric 1";
				}else{
					$GLOBALS["SCRIPTS_ROUTES"][]="#[$eth/".__FUNCTION__."/".__LINE__." {$ligne["GATEWAY"]} is a bad gateway but enabled as default";
					$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["routebin"]} add default dev ".$NetBuilder->NicToOther($eth);
				}
			}
			
			if($SourceBasedRouting==1){
				routes_source_add($NetBuilder->NicToOther($eth),$ligne["IPADDR"],$ligne["GATEWAY"],$CDIR,__LINE__);
			}
			
		}
	}
	
	
	if(!isset($GLOBALS["DEFAULT_ROUTE_SET"])){
			
			if(!isset($RULES_TABLES["eth0"])){
				$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."] [eth0] is set as default route metric `$metric`.";
				$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."] [eth0] IP:{$ligne["IPADDR"]}/$CDIR gateway:{$ligne["GATEWAY"]} Source Based Routing=$SourceBasedRouting";
				$nic=new system_nic("eth0");
				$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."] [eth0] UseSPAN: $nic->UseSPAN";
				
				if($nic->UseSPAN==1){$nic->GATEWAY=null;}
				if($nic->GATEWAY<>null){
					$eth="eth0";
					$GLOBALS["DEFAULT_ROUTE_SET"]="eth0";
					$CDIR=$NetBuilder->GetCDIRNetwork($nic->IPADDR,$nic->NETMASK);
					$SourceBasedRouting=$nic->SourceBasedRouting;
					$md5net=md5($CDIR);
					$GLOBALS["MD5NET"][$md5net]=true;
					$metric=$nic->metric;
					$metric_text=null;
					if($metric>0){
						if($MetricCount==0){$MetricCount++;$metric=1;}
						$metric_text=" metric $metric";
					}
		
					if($SourceBasedRouting==0){
						if(isGatewayGood($nic->GATEWAY)){
							$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["routebin"]} add -host {$nic->GATEWAY} dev ".$NetBuilder->NicToOther($eth);
							$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["routebin"]} add -net 0.0.0.0 gw {$nic->GATEWAY} dev ".$NetBuilder->NicToOther($eth).$metric_text;
						}
					}
					
					if($SourceBasedRouting==1){
						routes_source_add($NetBuilder->NicToOther($eth),$nic->IPADDR,$nic->GATEWAY,$CDIR,__LINE__);
					}
						
				}
		}else{
			$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."] DEFAULT_ROUTE_SET eth0 use a routing table ID {$RULES_TABLES["eth0"]}";
		}
	}

	$GLOBALS["rt_tables_number"]=0;
	$sql="SELECT * FROM `nics` WHERE enabled=1 AND UseSPAN=0 ORDER BY Interface";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "Starting......: ".date("H:i:s")." Mysql error : $q->mysql_error\n";return;}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$eth=trim($ligne["Interface"]);
		$eth=str_replace("\r\n", "", $eth);
		$eth=str_replace("\r", "", $eth);
		$eth=str_replace("\n", "", $eth);
		$eth=trim($eth);
		if($eth==null){continue;}
		if(isset($RULES_TABLES[$eth])){
			$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."] nic_routes $eth use a routing table ID {$RULES_TABLES[$eth]}";
			continue;
		}
		$SourceBasedRouting=intval($ligne["SourceBasedRouting"]);
		$IPADDR=$ligne["IPADDR"];
		$GLOBALS["SCRIPTS_ROUTES"][]="#";
		$GLOBALS["SCRIPTS_ROUTES"][]="#";
		
		if(!isset($GLOBALS["PROC_NET_DEV"][$eth])){
			$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."] $eth Not found in PROC_NET_DEV Hardware error";
			continue;
			
		}
		
		$ROUTES_ARRAY=unserialize($ligne["routes"]);
		$metric=$ligne["metric"];
		$metric_text=null;
		if($metric>0){
			if($MetricCount==0){$MetricCount++;$metric=1;}
			$metric_text=" metric $metric";
		}
		
		if(isset($GLOBALS["DEFAULT_ROUTE_SET"])){if($GLOBALS["DEFAULT_ROUTE_SET"]==$eth){continue;}}
		
		$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."] $eth SourceBasedRouting:$SourceBasedRouting Main route $eth gateway '{$ligne["GATEWAY"]}' netmask '{$ligne["NETMASK"]}' ipaddr: '{$ligne["IPADDR"]}'";
		
		if(isset($eth_SKIP[$eth])){echo "Starting......: ".date("H:i:s")." $eth skipping\n";$GLOBALS["SCRIPTS_ROUTES"][]="# [$eth] skipped";continue;}
		if($ligne["GATEWAY"]==null){$GLOBALS["SCRIPTS_ROUTES"][]="# [$eth/".__LINE__."] GATEWAY = null skipped";continue;}
		if($ligne["GATEWAY"]=="0.0.0.0"){$GLOBALS["SCRIPTS_ROUTES"][]="# [$eth/".__LINE__."] GATEWAY = 0.0.0.0 skipped";continue;}
		if($ligne["NETMASK"]=="0.0.0.0"){$GLOBALS["SCRIPTS_ROUTES"][]="# [$eth/".__LINE__."] NETMASK = 0.0.0.0 skipped";continue;}	
		if(trim($ligne["NETMASK"])==null){$GLOBALS["SCRIPTS_ROUTES"][]="# [$eth/".__LINE__."] NETMASK = null skipped";continue;}
		
		
		$CDIR=trim($NetBuilder->GetCDIRNetwork($ligne["IPADDR"],$ligne["NETMASK"]));
		$GLOBALS["SCRIPTS_ROUTES"][]="# [$eth/".__LINE__."] CDIR:$CDIR GATEWAY = {$ligne["GATEWAY"]} add in table (default route {$ligne["defaultroute"]})";
		
		if($ligne["defaultroute"]==0){
			
			if(!isset($GLOBALS["GATEWAYADDED"][$eth][$ligne["GATEWAY"]])){
				
				if($SourceBasedRouting==0){ routes_default_add($NetBuilder->NicToOther($eth),$IPADDR,$ligne["GATEWAY"],$CDIR,$metric_text,__LINE__); }
				if($SourceBasedRouting==1){ routes_source_add($NetBuilder->NicToOther($eth),$IPADDR,$ligne["GATEWAY"],$CDIR,__LINE__); }	
				$GLOBALS["GATEWAYADDED"][$eth][$ligne["GATEWAY"]]=true;
				
			}
		}	
		
		
		if($CDIR==null){$GLOBALS["SCRIPTS_ROUTES"][]="# GetCDIRNetwork ({$ligne["IPADDR"]},{$ligne["NETMASK"]} ) return null";}
		if(isset($ALREADYNETS[$CDIR])){$GLOBALS["SCRIPTS_ROUTES"][]="# [$eth] $CDIR already added skip it";continue;}
		
		$ALREADYNETS[$CDIR]=true;
		$eth=$NetBuilder->NicToOther($eth);
		$md5net=md5($CDIR);
		if(isset($GLOBALS["MD5NET"][$md5net])){ $GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."] [$eth] MD5NET already added skip it"; continue; }
		
		
		if($ligne["defaultroute"]==0){
			
			if(is_array($ROUTES_ARRAY)){
				$GLOBALS["SCRIPTS_ROUTES"][]="# [$eth/".__LINE__."] ".count($ROUTES_ARRAY)." Additionnal route(s)";
				if(count($ROUTES_ARRAY)>0){
					while (list ($ip, $ip_array) = each ($ROUTES_ARRAY) ){
						$NETMASK=$ip_array["NETMASK"];
						$GATEWAY=$ip_array["GATEWAY"];
						$GLOBALS["SCRIPTS_ROUTES"][]="# [$eth/".__LINE__."] $ip/$NETMASK -> $GATEWAY Table {$GLOBALS["RT_TABLES"][$eth]}/$eth";
						if(isGatewayGood($GATEWAY)){
							$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["routebin"]} add -host $GATEWAY dev $eth";
							$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["routebin"]} add -net $ip netmask $NETMASK gw $GATEWAY dev $eth";
						}
					}
				}
			}
			
			
			
			if(!isset($GLOBALS["GATEWAYADDED"][$eth][$ligne["GATEWAY"]])){
				$GLOBALS["SCRIPTS_ROUTES"][]="# [$eth/".__LINE__."] $CDIR Gateway:{$ligne["GATEWAY"]} SourceBasedRouting:$SourceBasedRouting";
				if($SourceBasedRouting==0){
					routes_default_add($NetBuilder->NicToOther($eth),$IPADDR,$ligne["GATEWAY"],$CDIR,$metric_text,__LINE__);
				}
				
				if($SourceBasedRouting==1){
					
					routes_source_add($NetBuilder->NicToOther($eth),$IPADDR,$ligne["GATEWAY"],$CDIR,__LINE__);
				}			
				
				
			}

		
		
		}else{
			
			
			if(is_array($ROUTES_ARRAY)){
				if(count($ROUTES_ARRAY)>0){
					while (list ($ip, $ip_array) = each ($ROUTES_ARRAY) ){
						$NETMASK=$ip_array["NETMASK"];
						$GATEWAY=$ip_array["GATEWAY"];
						$CDIR=$NetBuilder->GetCDIRNetwork($ip,$NETMASK);
						if(isset($ALREADYNETS[$CDIR])){$GLOBALS["SCRIPTS_ROUTES"][]="# [$eth] $ip/$NETMASK $CDIR already added skip it";continue;}
						$GLOBALS["SCRIPTS_ROUTES"][]="# [$eth/".__LINE__."] $ip/$NETMASK/$CDIR -> $GATEWAY main route";
						$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["routebin"]} add -net $ip netmask $NETMASK gw $GATEWAY dev ".$NetBuilder->NicToOther($eth);
						$ALREADYNETS[$CDIR]=true;
					}
				}
			}
			
			
		}
		
		$GLOBALS["MD5NET"][$md5net]=true;
		
	}
	
	$sql="SELECT * FROM nic_routes ORDER BY `zOrder`";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	
	$types[1]="{network_nic}";
	$types[2]="{host}";
	
	$GLOBALS["SCRIPTS_ROUTES"][]="#";
	$GLOBALS["SCRIPTS_ROUTES"][]="# nic_routes ". mysql_num_rows($results)." elements..";
	$GLOBALS["SCRIPTS_ROUTES"][]="#";
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$type=$ligne["type"];
		$ligne["nic"]=trim($ligne["nic"]);
		if(isset($RULES_TABLES[$ligne["nic"]])){
			$GLOBALS["SCRIPTS_ROUTES"][]="# nic_routes {$ligne["nic"]} use a routing table ID {$RULES_TABLES[$ligne["nic"]]} [".__LINE__."]";
			continue;
		}
		$nicz=new system_nic($ligne["nic"]);
		$ligne["SourceBasedRouting"]=intval($nic->SourceBasedRouting);
		
		
		
		if($type==2){
			$GLOBALS["SCRIPTS_ROUTES"][]="# [{$ligne["nic"]}/".__LINE__."] Route host {$ligne["pattern"]}";
			$data=routes_main_host($ligne);
			if($data<>null){
				$GLOBALS["SCRIPTS_ROUTES"][]=$data;
			}
				
			continue;
		}

		$GLOBALS["SCRIPTS_ROUTES"][]="# [{$ligne["nic"]}/".__LINE__."] Route network {$ligne["pattern"]} gw {$ligne["gateway"]}";
		$data=routes_main_network($ligne);
		if($data<>null){
			$GLOBALS["SCRIPTS_ROUTES"][]=$data;
		}
	}
	
	if(count($endcmdsline)>0){
		while (list ($index, $line) = each ($endcmdsline) ){ $GLOBALS["SCRIPTS_ROUTES"][]=$line; }
	}
	
	$GLOBALS["SCRIPTS_ROUTES"][]="# ".count($GLOBALS["SCRIPTS_ROUTES_RTTABLE"])." SCRIPTS_ROUTES_RTTABLE items";
	if(count($GLOBALS["SCRIPTS_ROUTES_RTTABLE"])>0){
		while (list ($index, $line) = each ($GLOBALS["SCRIPTS_ROUTES_RTTABLE"]) ){ $GLOBALS["SCRIPTS_ROUTES"][]=$line; }
	}
	routes_table();
}


function routes_table(){
	$unix=new unix();
	$q=new mysql();
	$iptables=$unix->find_program("iptables");
	$php=$unix->LOCATE_PHP5_BIN();
	$MARKLOG="-m comment --comment \"ArticaIpRoute2\"";
	$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."]";
	$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."]";
	$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."] ************************************************";
	$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."] ADVANCED ROUTING";
	$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."] ************************************************";
	
	if(!$q->TABLE_EXISTS("routing_rules", "artica_backup")){
		$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."] routing_rules table does not exists";
		return;
	}
	

	
	
	
	$sql="SELECT nic FROM routing_rules WHERE enabled=1 GROUP BY nic";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	
	$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."] routing_rules ".mysql_num_rows($results)." items";
	if(mysql_num_rows($results)==0){
		return;
	}
	
	$GLOBALS["SCRIPTS_IPTABLES"][]="$php /usr/share/artica-postfix/exec.iptables-deleteroutes.php || true";
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$nic=$ligne["nic"];
		$nicClass=new system_nic($nic);
		$IPADDR=$nicClass->IPADDR;
		$GLOBALS["SCRIPTS_IPTABLES"][]="$iptables -t nat -I POSTROUTING -o $nic $MARKLOG -j MASQUERADE || true";
		$GLOBALS["SCRIPTS_IPTABLES"][]="$iptables -t nat -I POSTROUTING -o $nic $MARKLOG -j SNAT --to-source $IPADDR || true";
		
			
	}
	
	
	$GLOBALS["SCRIPTS_ROUTES"][]="#";
	$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."]";
	$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."] Enable IP Forwarding and disable Reverse Path filter";
	$sysctl=$unix->find_program("sysctl");
	$GLOBALS["SCRIPTS_ROUTES"][]="$sysctl -w net.ipv4.ip_forward=1";
	$GLOBALS["SCRIPTS_ROUTES"][]="$sysctl -w net.ipv4.conf.default.rp_filter=0 >/dev/null 2>&1";
	$GLOBALS["SCRIPTS_ROUTES"][]="$sysctl -w net.ipv4.conf.all.rp_filter=0 >/dev/null 2>&1";
	$GLOBALS["SCRIPTS_ROUTES"][]="for f in /proc/sys/net/ipv4/conf/*/rp_filter; do echo 0 >| \$f ; done";
	$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."]";
	$GLOBALS["SCRIPTS_ROUTES"][]="#";
	
	

	
	while ($ligne = mysql_fetch_assoc($results)) {
		$nic=$ligne["nic"];
		$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."] routing_rules flush table and rules from $nic";
		$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["ipbin"]} route flush dev $nic || true";
		$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["ipbin"]} rule del dev $nic || true";
	}
	
	
		$sql="SELECT * FROM routing_rules WHERE enabled=1";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." Mysql error : $q->mysql_error\n";return;}
	
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$RouteName=$ligne["RouteName"];
			
			
			$ID=$ligne["ID"];
			$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."] routing_rules table $RouteName";
			routes_table_sources($ID,$nic,$RouteName);
			
			
			
			
			
		}
}

function routes_table_sources($ruleid,$eth,$RouteName){
	$unix=new unix();
	$q=new mysql();
	$route=$unix->find_program("route");
	$nicClass=new system_nic($eth);
	
	if($nicClass->enabled==0){
		$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."] $eth is disabled";
		return;
	}
	if($nicClass->UseSPAN==1){
		$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."] $eth is on SPAN MODE";
		return;
	}
	
	$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."] Default route for $eth $nicClass->GATEWAY";
	
	
	$GLOBALS["SCRIPTS_ROUTES"][]="while ip rule delete from $nicClass->IPADDR 2>/dev/null; do true; done";
	
	if($nicClass->GATEWAY<>null){
		if($nicClass->GATEWAY<>"0.0.0.0"){
			$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."] Add the default route defined for $eth";
			$GLOBALS["SCRIPTS_ROUTES"][]="$route add -host $nicClass->GATEWAY dev $eth";
			$GLOBALS["SCRIPTS_ROUTES"][]="$route add default gw $nicClass->GATEWAY dev $eth";
			$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["ipbin"]} route add default via $nicClass->GATEWAY dev $eth table $RouteName";
			$GLOBALS["SCRIPTS_ROUTES"][]="#";
		}
	}
	
	$GLOBALS["SCRIPTS_ROUTES"][]="# [".__LINE__."] All packets from $nicClass->IPADDR goes to $RouteName";
	$GLOBALS["SCRIPTS_ROUTES"][]="#";
	
	$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["ipbin"]} rule add from $nicClass->IPADDR lookup $RouteName prio $nicClass->metric";
	
	$results=$q->QUERY_SQL("SELECT * FROM routing_rules_dest WHERE ruleid='$ruleid' AND type=3 ORDER BY zOrder,metric","artica_backup");
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		if($ligne["gateway"]==$nic->GATEWAY){continue;}
		$GLOBALS["SCRIPTS_ROUTES"][]="$route add -host {$ligne["gateway"]} dev $eth";
		$GLOBALS["SCRIPTS_ROUTES"][]="$route add default gw {$ligne["gateway"]} dev $eth";
		$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["ipbin"]} route add default via {$ligne["gateway"]} dev $eth table $RouteName prio {$ligne["metric"]} || true";
	}
	
	
	
	$results=$q->QUERY_SQL("SELECT * FROM routing_rules_src WHERE ruleid='$ruleid' ORDER BY zOrder","artica_backup");
	$CuntOfSources=0;
	while ($ligne = mysql_fetch_assoc($results)) {
		$CuntOfSources++;
		$pattern=$ligne["pattern"];
		$gateway=$ligne["gateway"];
		$priority=$ligne["metric"];
		$priority_cmd=null;
		
		if($priority>0){$priority_cmd=" priority $priority";}
		$type=$ligne["type"];
		
		if($type==3){
			$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["ipbin"]} rule add nat $gateway from $pattern dev $eth table $RouteName{$priority_cmd} || true";
			continue;
		}
		if($type==4){
			$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["ipbin"]} rule add type blackhole from $pattern dev $eth table $RouteName{$priority_cmd} || true";
			continue;
		}
		
		if($type==5){
			$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["ipbin"]} rule add type prohibit from $pattern dev $eth table $RouteName{$priority_cmd} || true";
			continue;
		}		
		
		$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["ipbin"]} rule add from $pattern dev $eth lookup $RouteName{$priority_cmd} || true";
		
		
	}
	
	
	$results=$q->QUERY_SQL("SELECT * FROM routing_rules_dest WHERE ruleid='$ruleid' ORDER BY zOrder,metric","artica_backup");
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$pattern=$ligne["pattern"];
		$priority=$ligne["metric"];
		$priority_cmd=null;
		
		if($priority>0){$priority_cmd=" priority $priority";}
		
		if($ligne["gateway"]==null){
			$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["ipbin"]} route add $pattern dev $eth  proto kernel  scope link  src $nicClass->IPADDR {$priority_cmd}";
			$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["ipbin"]} rule add to $pattern dev $eth lookup $RouteName{$priority_cmd} || true";
			continue;
		}
		
		
		$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["ipbin"]} route add {$ligne["pattern"]} via {$ligne["gateway"]} dev $eth table $RouteName{$priority_cmd} || true";
	}
	
	$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["ipbin"]} route flush cache || true";
	
}



function routes_default_add($eth,$ipsrc,$gateway,$network,$metric_text,$calledByLine){
	$unix=new unix();
	if(!isset($GLOBALS["moprobebin"])){$GLOBALS["moprobebin"]=$unix->find_program("modprobe");}
	if(!isset($GLOBALS["vconfigbin"])){$GLOBALS["vconfigbin"]=$unix->find_program("vconfig");}
	if(!isset($GLOBALS["ifconfig"])){$GLOBALS["ifconfig"]=$unix->find_program("ifconfig");}
	if(!isset($GLOBALS["ipbin"])){$GLOBALS["ipbin"]=$unix->find_program("ip");}
	if(!isset($GLOBALS["echobin"])){$GLOBALS["echobin"]=$unix->find_program("echo");}
	if(!isset($GLOBALS["sysctl"])){$GLOBALS["sysctl"]=$unix->find_program("sysctl");}
	if(!isset($GLOBALS["routebin"])){$GLOBALS["routebin"]=$unix->find_program("route");}


	if($gateway=="0.0.0.0"){$gateway=null;}
	
	$GLOBALS["SCRIPTS_ROUTES"][]="#[$eth/".__FUNCTION__."/".__LINE__." Gateway: $gateway Standard routing - called by $calledByLine";
	
	if($network<>null){
		$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["routebin"]} add -net $network gw $gateway dev $eth $metric_text";
	}
	
	if($gateway==null){return;}
	if(isGatewayGood($gateway)){
		$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["routebin"]} add -host $gateway dev $eth $metric_text";
		$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["routebin"]} add -net 0.0.0.0 gw $gateway dev $eth $metric_text";
	}
	
	
	
	

}

function routes_source_add($eth,$ipsrc,$gateway,$network,$calledByLine){
	$unix=new unix();
		if(!isset($GLOBALS["moprobebin"])){$GLOBALS["moprobebin"]=$unix->find_program("modprobe");}
		if(!isset($GLOBALS["vconfigbin"])){$GLOBALS["vconfigbin"]=$unix->find_program("vconfig");}
		if(!isset($GLOBALS["ifconfig"])){$GLOBALS["ifconfig"]=$unix->find_program("ifconfig");}
		if(!isset($GLOBALS["ipbin"])){$GLOBALS["ipbin"]=$unix->find_program("ip");}
		if(!isset($GLOBALS["echobin"])){$GLOBALS["echobin"]=$unix->find_program("echo");}
		if(!isset($GLOBALS["sysctl"])){$GLOBALS["sysctl"]=$unix->find_program("sysctl");}

	
	if($gateway=="0.0.0.0"){$gateway=null;}
	
	$ip=$GLOBALS["ipbin"];
	$echo =$GLOBALS["echobin"];
	$table=alphaToNum($eth);
	$sysctl=$GLOBALS["sysctl"];
	if(!isset($GLOBALS["net.ipv4.conf.all.arp_filter"])){
		$GLOBALS["SCRIPTS_ROUTES"][]="$sysctl -w net.ipv4.conf.all.arp_filter=1";
		$GLOBALS["net.ipv4.conf.all.arp_filter"]=true;
	}
	
	
	
	$GLOBALS["SCRIPTS_ROUTES"][]="$echo \"*** TABLE $table $eth/$ipsrc/$network go to $gateway ****\"";
	$GLOBALS["SCRIPTS_ROUTES"][]="# Called by line $calledByLine routes_source_add() line ".__LINE__;
	$GLOBALS["SCRIPTS_ROUTES"][]="$ip route flush table $table";
	$GLOBALS["SCRIPTS_ROUTES"][]="$ip rule del dev $eth";
	$GLOBALS["SCRIPTS_ROUTES"][]="$ip rule del from $ipsrc";
	$GLOBALS["SCRIPTS_ROUTES"][]="$ip rule add from $ipsrc table $table";
	if($network<>null){
		$GLOBALS["SCRIPTS_ROUTES"][]="$ip route add $network dev $eth src $ipsrc table $table";
	}
	
	if($gateway<>null){
		$GLOBALS["SCRIPTS_ROUTES"][]="$ip route add default via $gateway table $table";
	}
	
	if($network<>null){
		$GLOBALS["SCRIPTS_ROUTES"][]="$ip route add $network dev $eth src $ipsrc";
	}
	
}


function routes_main_network($ligne){
	$NetBuilder=new system_nic();
	$NetBuilder->LoadTools();
	$ipClass=new IP();
	$pattern=$ligne["pattern"];
	$gateway=$ligne["gateway"];
	if($gateway=="0.0.0.0"){$gateway=null;}
	
	if(!isset($GLOBALS["PROC_NET_DEV"][$ligne["nic"]])){
		$GLOBALS["SCRIPTS_ROUTES"][]="# [{$ligne["nic"]}/".__LINE__."] INTERFACE MATERIAL ERROR";
		return;
	}
	$eth=$NetBuilder->NicToOther($ligne["nic"]);
	$metric=$ligne["metric"];	
	
	if(!$ipClass->isValidBlock($pattern)){
		$GLOBALS["SCRIPTS_ROUTES"][]="# [$eth/".__LINE__."] $pattern is not a valid block";
		return;
	}
	
	if($ligne["SourceBasedRouting"]==1){
		$table=alphaToNum($ligne["nic"]);
		$f[]="{$GLOBALS["ipbin"]} add $pattern";
		if($gateway<>null){ $f[]="via $gateway"; }
		$f[]="table $table";
		if($metric>0){ $f[]="metric $metric"; }
		return @implode(" ", $f);
	}	
	
	
	if($gateway==null){
		$GLOBALS["SCRIPTS_ROUTES"][]="# [{$ligne["nic"]}/".__LINE__."] No gateway set, add just the net $pattern on interface $eth";
		$f[]="{$GLOBALS["ipbin"]} route add $pattern dev $eth";
		if($metric>0){ $f[]="metric $metric"; }
		return @implode(" ", $f);
	}
	
	$GLOBALS["SCRIPTS_ROUTES"][]="# [{$ligne["nic"]}/".__LINE__."] $pattern via $gateway on interface $eth";
	$f[]="{$GLOBALS["routebin"]} add -net $pattern";
	$f[]="gw $gateway"; 
	$f[]="dev $eth";
	if($metric>0){ $f[]="metric $metric"; }
	
	return @implode(" ", $f);
	
}


function routes_main_host($ligne){
	$ipClass=new IP();
	$NetBuilder=new system_nic();
	$NetBuilder->LoadTools();
	$pattern=$ligne["pattern"];
	$gateway=$ligne["gateway"];
	$SourceBasedRouting=$ligne["SourceBasedRouting"];
	if($gateway=="0.0.0.0"){$gateway=null;}
	
	if(!isset($GLOBALS["PROC_NET_DEV"][$ligne["nic"]])){
		$GLOBALS["SCRIPTS_ROUTES"][]="# [{$ligne["nic"]}/".__LINE__."] INTERFACE MATERIAL ERROR";
		return;
	}
	
	
	if(preg_match("#(.+?)\/([0-9]+)#", $pattern,$re)){
		$pattern=$re[1];
	}
	
	
	if(!$ipClass->isValid($pattern)){
		$pattern=gethostbyname($pattern);
	
	}
	
	if(!$ipClass->isValid($pattern)){
		$GLOBALS["SCRIPTS_ROUTES"][]="# [{$ligne["nic"]}/".__LINE__."] `$pattern` ERROR - could not resolv";
		return;
	}
	
	$eth=$NetBuilder->NicToOther($ligne["nic"]);
	$metric=$ligne["metric"];
	
	
	if($ligne["SourceBasedRouting"]==1){
		$table=alphaToNum($ligne["nic"]);
		$f[]="{$GLOBALS["ipbin"]} add $pattern";
		if($gateway<>null){ $f[]="via $gateway"; }
		$f[]="table $table";
		if($metric>0){ $f[]="metric $metric"; }
		return @implode(" ", $f);
	}
	
	
	$f[]="{$GLOBALS["routebin"]} add -host $pattern";
	if($gateway<>null){ $f[]="gw $gateway"; }
	$f[]="dev $eth";
	if($metric>0){ $f[]="metric $metric"; }
	return @implode(" ", $f);

	
}


function routes(){
	$unix=new unix();
	$NetBuilder=new system_nic();
	$route=$unix->find_program("route");
	$ip=$unix->find_program("ip");
	$types[1]="{network_nic}";
	$types[2]="{host}";	
	$array=array();
	
	
	$q=new mysql();	
	
	$f=explode("\n",@file_get_contents("/etc/iproute2/rt_tables"));
	while (list ($eth, $ligne) = each ($f) ){
		if(preg_match("#^([0-9]+)\s+(.+)#", $ligne,$re)){
			$tableID=$re[1];
			if($tableID==255){continue;}
			if($tableID==254){continue;}
			if($tableID==253){continue;}
			if($tableID==0){continue;}
			$array[$tableID]=$re[2];
		}
		
	}
	
	if(count($array)>0){
		while (list ($id, $ligne) = each ($array) ){
			echo "Starting......: ".date("H:i:s")." Building routes, flush table `$ligne`\n";
			shell_exec("$ip route flush table $ligne");
			
		}
	}
	
	$sql="SELECT * FROM iproute_table WHERE enable=1 ORDER BY routename";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		echo "Starting......: ".date("H:i:s")." Building routes, $q->mysql_error\n";
		routes_fromfile();
		return;
	}
	
	
	$rtid=0;
	$countOfRoutes=mysql_num_rows($results);
	if($GLOBALS["VERBOSE"]){echo "Line:".__LINE__." $countOfRoutes routes\n";}
	if($countOfRoutes==0){return;}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$rtname=$ligne["routename"];
		$rtid++;		
		$eth=$ligne["interface"];
		$gw=$ligne["gateway"];
		$eth=$NetBuilder->NicToOther($eth);
		
		
		if($gw<>null){
			$NEXT[]="$ip route add $gw dev $eth";
			
		}
	
		$TABLES[]=$rtname;
		echo "Starting......: ".date("H:i:s")." Building routes, Group {$ligne["ID"]}\n";
		$sql="SELECT * FROM iproute_rules WHERE ruleid={$ligne["ID"]} AND enable=1 ORDER BY priority";
		$results2=$q->QUERY_SQL($sql,"artica_backup");
		$tt[]=array();
		
		while ($ligne2 = mysql_fetch_assoc($results2)) {
				$src=PARSECDR($ligne2["src"]);
				$destination=PARSECDR($ligne2["destination"]);
				$priority=$ligne2["priority"];
				echo "Starting......: ".date("H:i:s")." Building routes, source=$src, dest=$destination, GW=$gw\n";
				$POS=route_between_subnet($src,$destination,$priority,$eth,$rtname);
				if($POS<>null){
					$NEXT[]="$ip $POS";
					continue;
				}
				
				$POS=route_from($src,$destination,$priority,$eth,$rtname);
				if($POS<>null){
					$NEXT[]="$ip $POS";
					continue;
				}			
	
				$POS=route_desc($src,$destination,$priority,$eth,$rtname,$gw);
				if($POS<>null){
					$NEXT[]="$ip $POS";
					continue;
				}

				
				
				
		}
	
		
	
	}
	
	$f=array();
	$f[]="255\tlocal";
	$f[]="254\tmain";
	$f[]="253\tdefault";
	$f[]="299\tproxy";
	$f[]="0\tunspec";
	$c=1;
	if(count($TABLES)>0){
		while (list ($id, $ligne) = each ($TABLES) ){
			$f[]="$c\t$ligne";
			shell_exec("$ip route flush table $ligne");
			
		}
		@file_put_contents("/etc/iproute2/rt_tables", @implode("\n", $f));
		while (list ($id, $cmdline) = each ($NEXT) ){
			shell_exec("$cmdline >/dev/null 2>&1");
		}
	}
	reset($TABLES);
	reset($NEXT);
	$FINAL["TABLES"]=$TABLES;
	$FINAL["NEXT"]=$NEXT;

	
	
	@file_put_contents("/etc/artica-postfix/ROUTES.CACHES.TABLES", serialize($FINAL));
	
}
function route_desc($src,$destination,$priority,$eth,$rtname,$gw){
	if($src<>null){return;}
	if($destination==null){return;}
	if($gw==null){return;}
	$prioritytext=null;
	if($priority>0){
		$prioritytext=" priority $priority ";
	}
	
	return "route add$prioritytext to $destination via $gw dev $eth table $rtname";	
	
}

function route_from($src,$destination,$priority,$eth,$rtname){
	if($src==null){return;}
	if($destination<>null){return;}
	$prioritytext=null;
	if($priority>0){
		$prioritytext=" priority $priority ";
	}
	
	return "rule add$prioritytext from $src dev $eth table $rtname";	
	
}

function route_between_subnet($src,$destination,$priority,$eth,$rtname){
	if($src==null){return;}
	if($destination==null){return;}
	$prioritytext=null;
	if($priority>0){
		$prioritytext=" priority $priority ";
	}
	
	return "rule add$prioritytext from $src to $destination dev $eth table $rtname";
}

function routes_del($md5){
	$unix=new unix();
	$route=$unix->find_program("route");	
	$q=new mysql();
	$sql="SELECT * FROM nic_routes WHERE `zmd5`='$md5'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$type=$ligne["type"];
	$ttype="-net";
	if($type==1){$ttype="-net";}
	if($type==2){$ttype="-host";}
	
	$NetBuilder=new system_nic();
	if($NetBuilder->IsBridged($ligne["nic"])){
		$ligne["nic"]=$ligne["BridgedTo"];
	}
	
	if($ligne["nic"]<>null){$dev=" dev {$ligne["nic"]}";}
	
	
	
	$cmd="$route del $ttype {$ligne["pattern"]} gw {$ligne["gateway"]}$dev";
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}	
	shell_exec("$cmd >/dev/null 2>&1");
	$sql="DELETE FROM nic_routes WHERE `zmd5`='$md5'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
	
	
}


function postfix_multiples_instances(){
	build();
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	
	$sql="SELECT ou, ip_address, `key` , `value` FROM postfix_multi WHERE `key` = 'myhostname'";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
		$hostname=$ligne["value"];
		echo "Starting......: ".date("H:i:s")." reconfigure postfix instance $hostname\n";
		shell_exec("$php /usr/share/artica-postfix/exec.postfix-multi.php --instance-reconfigure \"$hostname\"");
	}
}

function Checkipv6Virts(){
	$unix=new unix();
	$sock=new sockets();
	$EnableipV6=$sock->GET_INFO("EnableipV6");
	$NetBuilder=new system_nic();
	
	if(!is_numeric($EnableipV6)){$EnableipV6=0;}	
	if($EnableipV6==0){return;}
	$q=new mysql();
	$sql="SELECT nic FROM nics_virtuals WHERE ipv6=1";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	$eths=array();
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){		
		
		
		
		$eths[$ligne["nic"]]=$ligne["nic"];
		
	}
	if(count($eths)==0){
		echo "Starting......: ".date("H:i:s")." Building Ipv6 virtuals IP -> 0 interface...\n";
		return;
	}
	
	
	$echo=$unix->find_program("echo");
	$ipbin=$unix->find_program("ip");
	$ip=new IP();
	$sh=array();
	while (list ($eth, $ligne) = each ($eths) ){
		echo "Starting......: ".date("H:i:s")." Building Ipv6 virtuals IP for `$eth` interface...\n";
		$sh[]="$echo 0 > /proc/sys/net/ipv6/conf/$eth/disable_ipv6";		
		$sh[]="$echo 0 > /proc/sys/net/ipv6/conf/$eth/autoconf";
		$sh[]="$echo 0 > /proc/sys/net/ipv6/conf/$eth/accept_ra";
		$sh[]="$echo 0 > /proc/sys/net/ipv6/conf/$eth/accept_ra_defrtr";
		$sh[]="$echo 0 > /proc/sys/net/ipv6/conf/$eth/accept_ra_pinfo";
		$sh[]="$echo 0 > /proc/sys/net/ipv6/conf/$eth/accept_ra_rtr_pref";	
		$sql="SELECT * FROM nics_virtuals WHERE ipv6=1 AND nic='$eth' ORDER BY ID DESC";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
			$ipv6addr=$ligne["ipaddr"];
			$netmask=$ligne["netmask"];
			if(!is_numeric($netmask)){$netmask=0;}
			if($netmask==0){continue;}
			
			
			if(!$ip->isIPv6($ipv6addr)){continue;}
			echo "Starting......: ".date("H:i:s")." Building Ipv6 virtuals IP for `$eth` [$ipv6addr/$netmask]...\n";
  		    $sh[]="$ipbin addr add dev $eth $ipv6addr/$netmask";
		}
		
	}
	
	if(count($sh)==0){return;}
	while (list ($num, $cmdline) = each ($sh) ){
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." Building Ipv6 virtuals $cmdline\n";}
		shell_exec($cmdline);
	}
	

}


function Checkipv6(){
	$unix=new unix();
	$sock=new sockets();
	$EnableipV6=$sock->GET_INFO("EnableipV6");
	if(!is_numeric($EnableipV6)){$EnableipV6=0;}
	
	if($EnableipV6==0){
		echo "Starting......: ".date("H:i:s")." Building networks IPv6 is disabled\n";
		$unix->sysctl("net.ipv6.conf.all.disable_ipv6",1);
		$unix->sysctl("net.ipv6.conf.default.disable_ipv6",1);
		$unix->sysctl("net.ipv6.conf.lo.disable_ipv6",1);
		@file_put_contents("/proc/sys/net/ipv6/conf/lo/disable_ipv6",1);
		@file_put_contents("/proc/sys/net/ipv6/conf/lo/disable_ipv6",1);
		@file_put_contents("/proc/sys/net/ipv6/conf/all/disable_ipv6",1);
		@file_put_contents("/proc/sys/net/ipv6/conf/default/disable_ipv6",1);
	}else{
		echo "Starting......: ".date("H:i:s")." Building networks IPv6 is enabled\n";
		$unix->sysctl("net.ipv6.conf.all.disable_ipv6",0);
		$unix->sysctl("net.ipv6.conf.default.disable_ipv6",0);
		$unix->sysctl("net.ipv6.conf.lo.disable_ipv6",0);
		@file_put_contents("/proc/sys/net/ipv6/conf/lo/disable_ipv6",0);
		@file_put_contents("/proc/sys/net/ipv6/conf/lo/disable_ipv6",0);
		@file_put_contents("/proc/sys/net/ipv6/conf/all/disable_ipv6",0);
		@file_put_contents("/proc/sys/net/ipv6/conf/default/disable_ipv6",0);
	}
	

	

	echo "Starting......: ".date("H:i:s")." Building networks IPv6 done...\n";
}

function ifupifdown($eth){
return;
}

function articalogon(){
	if(!is_file("/etc/artica-postfix/network.first.settings")){return;}
	$f=explode(";", @file_get_contents("/etc/artica-postfix/network.first.settings"));
	//l.Add(IP+';'+Gayteway+';'+netmask+';'+DNS); 
	$IPADDR=$f[0];
	$GATEWAY=$f[1];
	$NETMASK=$f[2];
	$DNS1=$f[3];
	$eth=$f[4];
	
	$nics=new system_nic($eth);
	$nics->eth=$eth;
	$nics->IPADDR=$IPADDR;
	$nics->NETMASK=$NETMASK;
	$nics->GATEWAY=$GATEWAY;
	$nics->DNS1=$DNS1;
	$nics->dhcp=0;
	$nics->enabled=1;
	$nics->NoReboot=true;
	$nics->SaveNic();
	dev_shm();
	build();
	echo "Settings $eth ($IPADDR) done...\n";
	
}
function persistent_net_rules(){
	if(!is_dir("/etc/udev/rules.d")){return;}
	$filename="/etc/udev/rules.d/70-persistent-net.rules";
	if(is_file($filename)){return;}
	
	
	$unix=new unix();
	$fz=$unix->dirdir("/sys/class/net");
	
	$final=array();
	while (list ($net, $line) = each ($fz) ){
		$line=basename($line);
		if(!preg_match("#eth[0-9]+#", $line)){continue;}
		$array=udevadm_eth($line);
		if(!$array){echo "Starting......: ".date("H:i:s")." Building persistent rule `FAILED` for `$line`\n";continue;}
		echo "Starting......: ".date("H:i:s")." Building persistent rule for `$line` {$array["MAC"]}\n";
		$final[]="SUBSYSTEM==\"net\", ACTION==\"add\", DRIVERS==\"?*\", ATTR{address}==\"{$array["MAC"]}\", ATTR{dev_id}==\"{$array["dev_id"]}\", ATTR{type}==\"{$array["TYPE"]}\", KERNEL==\"eth*\", NAME=\"$line\"";
		
	}
	
	if(count($final)>0){
		echo "Starting......: ".date("H:i:s")." Building $filename done\n";
		@file_put_contents($filename, @implode("\n", $final)."\n");
		
	}
	
	
}

function udevadm_eth($eth){
	$unix=new unix();
	$udevadm=$unix->find_program("udevadm");
	if(!is_file($udevadm)){return false;}
	$MAC=null;
	$dev_id=null;
	$type=null;
	exec("udevadm info -a -p /sys/class/net/$eth",$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match('#ATTR.*?address.*?=="(.+?)"#', $line,$re)){$MAC=$re[1];continue;}
		if(preg_match('#ATTR.*?dev_id.*?=="(.+?)"#', $line,$re)){$dev_id=$re[1];continue;}
		if(preg_match('#ATTR.*?type.*?=="(.+?)"#', $line,$re)){$type=$re[1];continue;}
		
	}
	if($MAC==null){return false;}
	if($dev_id==null){return false;}
	if($type==null){return false;}
	return array("MAC"=>$MAC,"DEV"=>$dev_id,"TYPE"=>$type); 
}

function bridge_delete($ID){
	
	$q=new mysql();
	$nicbr="br{$ID}";
	$NetBuilder=new system_nic();
	$NetBuilder->LoadTools();
	$NICS=$NetBuilder->BuildBridges_getlinked();
	while (list ($a, $b) = each ($NICS) ){
		$q->QUERY_SQL("UPDATE `nics` SET Bridged=0, BridgedTo='' WHERE Interface='$b'","artica_backup");
		$GLOBALS["SCRIPTS_DEL"][]="{$GLOBALS["brctlbin"]} delif $nicbr $b";
		$GLOBALS["SCRIPTS_DEL"][]="{$GLOBALS["ifconfig"]} $b down";
		
	}
	
	$GLOBALS["SCRIPTS_DEL"][]="{$GLOBALS["ifconfig"]} $nicbr down";
	$GLOBALS["SCRIPTS_DEL"][]="{$GLOBALS["brctlbin"]} delbr $nicbr";
	$q->QUERY_SQL("DELETE FROM `nics_bridge` WHERE ID='$ID'","artica_backup");
	
	
	
	while (list ($id, $ligne) = each ($GLOBALS["SCRIPTS_DEL"]) ){
		echo "Starting......: ".date("H:i:s")." `$ligne`\n";
		shell_exec("$ligne");
	
	}
	
	bridge_deletemanu($nicbr);
	BuildNetWorksDebian();
	shell_exec("/etc/init.d/artica-ifup start");
	
}

function bridge_deletemanu($eth){
	$NetBuilder=new system_nic();
	$NetBuilder->LoadTools();	
	if(!$NetBuilder->IfBridgeExists($eth)){return;}
	
	exec("{$GLOBALS["brctlbin"]} show $eth 2>&1",$result);
	while (list ($id, $ligne) = each ($result) ){
		if(preg_match("#.*\s+.*?\s+.*?\s+([a-z\.0-9]+)$#", $ligne,$re)){
			if(strtolower(trim($re[1])=="interfaces")){continue;}
			echo "Removing {$re[1]}\n";
			$GLOBALS["SCRIPTS_DEL"][]="{$GLOBALS["brctlbin"]} delif $eth {$re[1]}";
			$GLOBALS["SCRIPTS_DEL"][]="{$GLOBALS["ifconfig"]} {$re[1]} down";
			continue;
		}
		
		if(preg_match("#\s+\s+([a-z\.0-9]+)$#", $ligne,$re)){
			if(strtolower(trim($re[1])=="interfaces")){continue;}
			echo "Removing {$re[1]}\n";
			$GLOBALS["SCRIPTS_DEL"][]="{$GLOBALS["brctlbin"]} delif $eth {$re[1]}";
			$GLOBALS["SCRIPTS_DEL"][]="{$GLOBALS["ifconfig"]} {$re[1]} down";
			continue;			
		}
		
		
	}
	
	$GLOBALS["SCRIPTS_DEL"][]="{$GLOBALS["ifconfig"]} $eth down";
	$GLOBALS["SCRIPTS_DEL"][]="{$GLOBALS["brctlbin"]} delbr $eth";	
	

	while (list ($id, $ligne) = each ($GLOBALS["SCRIPTS_DEL"]) ){
		echo "Starting......: ".date("H:i:s")." `$ligne`\n";
		shell_exec("$ligne");
	
	}	
	
}
function etc_hosts_defaults(){

	$ip=new IP();
	$datas=explode("\n",@file_get_contents("/etc/hosts"));
	while (list ($num, $ligne) = each ($datas) ){
		if(substr($ligne, 0,1)=="#"){continue;}
		if(preg_match("#^([0-9\.\:]+)\s+(.+?)\s+(.+?)$#",$ligne,$re)){
			$array[]=array("name"=>$re[2],"alias"=>$re[3],"ip"=>$re[1],"md"=>md5($ligne));
			continue;
		}
	
		if(preg_match("#^([0-9\.\:]+)\s+(.+?)$#",$ligne,$re)){
			$array[]=array("name"=>$re[2],"ip"=>$re[1],"md"=>md5($ligne));
			continue;
		}
	
	}
	
	while (list ($num, $ligne) = each ($array) ){
		if($ligne["name"]==null){if($ligne["alias"]<>null){$ligne["name"]=$ligne["alias"];$ligne["alias"]=null;}}
		$md5=md5("{$ligne["ip"]}{$ligne["name"]}");
		if(!$ip->isValid($ligne["ip"])){continue;}
		$f[]="('$md5','{$ligne["ip"]}','{$ligne["name"]}','{$ligne["alias"]}')";
	}  
	
	$q=new mysql();
	$q->BuildTables();
	$q->QUERY_SQL("INSERT IGNORE INTO net_hosts (`zmd5`,`ipaddr`,`hostname`,`alias`) VALUES ".@implode(",", $f),"artica_backup");
	
	
}

//

?>
