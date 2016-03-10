<?php
//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
include_once(dirname(__FILE__)."/class.postfix.inc");
while (list ($num, $ligne) = each ($_GET) ){$a[]="$num=$ligne";}
writelogs_framework(@implode(" - ",$a),__FUNCTION__,__FILE__,__LINE__);

if(isset($_GET["iptables-save"])){iptables_save();exit;}
if(isset($_GET["iptables-events"])){iptables_events();exit;}

if(isset($_GET["nmap-ping"])){nmap_ping();exit;}
if(isset($_GET["firewall-apply"])){firewall_apply();exit;}
if(isset($_GET["firewall-reconfigure"])){FIREWALL_RECONFIGURE();exit;}
if(isset($_GET["firewall-content"])){firewall_content();exit;}
if(isset($_GET["isFW"])){isFW();exit;}
if(isset($_GET["conntrack"])){conntrack();exit;}
if(isset($_GET["NetworkManager-check-redhat"])){NetworkManager_redhat();exit;}
if(isset($_GET["reconfigure-postfix-instances"])){postfix_reconfigures_multiples_instances();exit;}
if(isset($_GET["ping"])){pinghost();exit;}
if(isset($_GET["crossroads-restart"])){crossroads_restart();exit;}
if(isset($_GET["ipv6"])){ipv6();exit;}
if(isset($_GET["OpenVPNServerLogs"])){OpenVPN_ServerLogs();exit;}
if(isset($_GET["ipdeny"])){ipdeny();exit;}
if(isset($_GET["fw-inbound-rules"])){iptables_inbound();exit;}
if(isset($_GET["fw-spamhaus-rules"])){iptables_spamhausrules();exit;}
if(isset($_GET["fqdn"])){fqdn();exit;}
if(isset($_GET["iptaccount-installed"])){iptaccount_check();exit;}
if(isset($_GET["ifup-ifdown"])){ifup_ifdown();exit;}
if(isset($_GET["reconstruct-interface"])){reconstruct_interface();exit;}
if(isset($_GET["reconstruct-all-interfaces"])){reconstruct_all_interfaces();exit;}
if(isset($_GET["arp-delete"])){arptable_delete();exit;}
if(isset($_GET["arp-edit"])){arptable_edit();exit;}
if(isset($_GET["ifconfig"])){ifconfig();exit;}
if(isset($_GET["ifconfig6"])){ifconfig6();exit;}
if(isset($_GET["vde-restart"])){vde_restart();exit;}
if(isset($_GET["vde-status"])){vde_status();exit;}
if(isset($_GET["reconfigure-restart"])){reconfigure_restart_network();exit;}
if(isset($_GET["down-interface"])){down_interface();exit;}

if(isset($_GET["dhcpd-leases"])){dhcpd_leases_force();exit;}
if(isset($_GET["dhcpd-leases-script"])){dhcpd_leases_script();exit;}
if(isset($_GET["flush-arp-cache"])){flush_arp_cache();exit;}
if(isset($_GET["etc-hosts-default"])){etc_hosts_defaults();exit;}
if(isset($_GET["etc-hosts"])){etc_hosts();exit;}
if(isset($_GET["artica-ifup-content"])){artica_ifup_content();exit;}
if(isset($_GET["ucarp-down"])){ucarp_down();exit;}


while (list ($num, $ligne) = each ($_GET) ){$a[]="$num=$ligne";}
writelogs_framework("***** Unable to unserstand ".@implode("&",$a),__FUNCTION__,__FILE__,__LINE__);

function flush_arp_cache(){
	$eth=$_GET["flush-arp-cache"];
	$unix=new unix();
	$ip=$unix->find_program("ip");
	$results[]="$ip -s -s neigh flush all";
	exec("$ip -s -s neigh flush all 2>&1",$results);
	writelogs_framework("$ip -s -s neigh flush all 2>&1 ".count($results)." items",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
}


function NetworkManager_redhat(){
	$unix=new unix();
	$chkconfig=$unix->find_program("chkconfig");
	if(!is_file($chkconfig)){return;}
	exec("$chkconfig --list NetworkManager 2>&1",$results);
	echo "<articadatascgi>". @implode("\n",$results)."</articadatascgi>";
	
	
}

function nmap_ping(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/nmap.pingnet.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/nmap.pingnet.progress.txt";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);

	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.nmapscan.php --scan-ping >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}

function iptaccount_check(){
	$unix=new unix();
	$iptaccount=$unix->find_program("iptaccount");
	if(!is_file($iptaccount)){echo "<articadatascgi>FALSE</articadatascgi>";return;}
	exec("$iptaccount -a 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#failed: Can't get table names from kernel#", $ligne)){
			echo "<articadatascgi>FALSE</articadatascgi>";return;
		}
	}
	echo "<articadatascgi>TRUE</articadatascgi>";return;
}

function fqdn(){
	$unix=new unix();
	$hostname=$unix->FULL_HOSTNAME();
	echo "<articadatascgi>". base64_encode($hostname)."</articadatascgi>";
}

function postfix_reconfigures_multiples_instances(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	shell_exec(trim("$nohup $php /usr/share/artica-postfix/exec.virtuals-ip.php --postfix-instances >/dev/null 2>&1 &"));

}

function ifconfig(){
	$net=$_GET["ifconfig"];
	$unix=new unix();
	$ifconfig=$unix->find_program("ifconfig");
	exec("$ifconfig $net 2>&1",$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}
function ifconfig6(){
	$net=$_GET["ifconfig6"];
	$unix=new unix();
	$ip=$unix->find_program("ip");	
	$cmd="$ip -6 address show $net 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	$array=array();
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#inet6\s+(.*?)\s+scope#", $ligne,$re)){
			$array[$re[1]]=$re[1];
		}
	}
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
}

function pinghost(){
	$host=$_GET["ping"];
	$unix=new unix();
	if($unix->PingHost($host)){
		echo "<articadatascgi>TRUE</articadatascgi>";
	}
}


	
function ifup_ifdown(){
	$eth=$_GET["ifup-ifdown"];
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	@unlink("/etc/artica-postfix/MEM_INTERFACES");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.virtuals-ip.php --ifupifdown $eth >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
}

function crossroads_restart(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec(trim("$nohup $php /usr/share/artica-postfix/exec.crossroads.php --multiples-restart >/dev/null 2>&1 &"));	
	
}
function dhcpd_leases_force(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec(trim("$nohup $php /usr/share/artica-postfix/exec.dhcpd-leases.php --force >/dev/null 2>&1 &"));		

}

function dhcpd_leases_script(){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$unix=new unix();
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		echo "<articadatascgi>". base64_encode(serialize(array($pid,$time)))."</articadatascgi>";
	}

}

function ipv6(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.virtuals-ip.php --ipv6 >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function OpenVPN_ServerLogs(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$tail=$unix->find_program("tail");
	$cmd=trim("$tail -n 300 /var/log/openvpn/openvpn.log 2>&1 ");
	
	exec($cmd,$results);		
	writelogs_framework($cmd ." ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}

function ipdeny(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.ipblock.php >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
}

function iptables_inbound(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.postfix.iptables.php --perso >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	$cmd="$nohup $php /usr/share/artica-postfix/exec.iptables.php --dns >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function reconstruct_interface(){
	$eth=$_GET["reconstruct-interface"];
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	@unlink("/etc/artica-postfix/MEM_INTERFACES");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.virtuals-ip.php --reconstruct-interface $eth --sleep >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}
function reconstruct_all_interfaces(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	@unlink("/etc/artica-postfix/MEM_INTERFACES");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.virtuals-ip.php --sleep >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function arptable_edit(){
	$unix=new unix();
	$datas=unserialize(base64_decode($_GET["arp-edit"]));
	if(!is_array($datas)){
		writelogs_framework("Not an array",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$arpbin=$unix->find_program("arp");
	$host=$datas["ARP_IP"];
	$mac=$datas["ARP_MAC"];
	$cmd="$arpbin -d $host";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
			
	$cmd="$arpbin -s $host $mac";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	while (list ($num, $ligne) = each ($results) ){writelogs_framework($ligne,__FUNCTION__,__FILE__,__LINE__);}
	$cmd=trim("$php /usr/share/artica-postfix/exec.arpscan.php --tomysql >/dev/null 2>&1");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function arptable_delete(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$arpbin=$unix->find_program("arp");
	$host=$_GET["arp-delete"];
	$cmd="$arpbin -d $host";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
	$cmd=trim("$php /usr/share/artica-postfix/exec.arpscan.php --tomysql >/dev/null 2>&1");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
}

function vde_restart(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/vde.status.html";
	$cmd=trim("$php /usr/share/artica-postfix/exec.initslapd.php --vde-switch >/dev/null 2>&1");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	@unlink($cachefile);
	@touch($cachefile);
	@chmod($cachefile,0755);
	$cmd="$nohup /etc/init.d/vde_switch restart >$cachefile 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}
function vde_status(){
}
function etc_hosts_defaults(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$php /usr/share/artica-postfix/exec.virtuals-ip.php --hosts-defaults >/dev/null 2>&1");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	}
function etc_hosts(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$php /usr/share/artica-postfix/exec.virtuals-ip.php --hosts 2>&1");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
	}	
function artica_ifup_content(){
	$datas=@file_get_contents("/etc/init.d/artica-ifup");
	echo "<articadatascgi>". base64_encode($datas)."</articadatascgi>";
	
}


function iptables_save(){
	$unix=new unix();
	$iptables=$unix->find_program("iptables-save");
	shell_exec("$iptables >/usr/share/artica-postfix/ressources/logs/web/iptables.save.html");
	@chmod("/usr/share/artica-postfix/ressources/logs/web/iptables.save.html",0777);
	
}




function  reconfigure_restart_network(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	ToSyslog("kernel: [  Artica-Net] reconfigure Network [artica-ifup] (".basename(__FILE__)."/".__LINE__.")" );
	$cmd=trim("/etc/init.d/artica-ifup reconfigure");
	shell_exec("$nohup /etc/init.d/artica-ifup reconfigure --script=cmd.php/reconfigure_restart_network >/dev/null 2>&1 &");
}
function down_interface(){
	$down_interface=$_GET["down-interface"];
	$unix=new unix();
	$ifconfig=$unix->find_program("ifconfig");
	shell_exec("$ifconfig $down_interface down");
}
function iptables_spamhausrules(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.spamhausdrop.php --force >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}
function ucarp_down(){
	$unix=new unix();
	$interface=$_GET["ucarp-down"];
	$master=$_GET["master"];
	$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES(true);
	if(!isset($NETWORK_ALL_INTERFACES[$interface])){
		writelogs_framework("Interface $interface not up [OK]",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	$nohup=$unix->find_program("nohup");
	$MN=unserialize(@file_get_contents("/usr/share/ucarp/ETH_LIST"));
	while (list ($eth, $line) = each ($MN) ){
		writelogs_framework("Interface $eth down [OK]",__FUNCTION__,__FILE__,__LINE__);
		$cmd="$nohup /usr/share/ucarp/vip-eth0-down.sh >/dev/null 2>&1";
		writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);
		squid_admin_mysql(0, "Master [$master]: Ordered to shutdown $interface [OK]", null,__FILE__,__LINE__);
		echo "<articadatascgi>DOWN_OK</articadatascgi>";
	}
	
}

function conntrack(){
	
$handle=fopen("/proc/net/ip_conntrack",'r');
if(!$handle){die();}
if(!is_numeric($_GET["rp"])){$_GET["rp"]=25;}
$max=$_GET["rp"];
if($max<5){$max=25;}
$pattern=null;

if($_GET["qtype"]<>null){
	if($_GET["query"]<>null){
		$pattern="#{$_GET["qtype"]}={$_GET["query"]}#";
		writelogs_framework($pattern,__FUNCTION__,__FILE__,__LINE__);
	}else{
		writelogs_framework("{$_GET["qtype"]} pattern is null",__FUNCTION__,__FILE__,__LINE__);
	}
}

$c=1;
while (!feof($handle)) {
	$value=trim(fgets($handle));
	if($value==null){continue;}
	if($pattern<>null){
		if(!preg_match($pattern, $value)){continue;}
	}
	$md5=md5($value);
	$values=explode(" ",$value);
	$array[$md5]["LINE"]=$value;
	$array[$md5]["COUNT"]="$c/$max";
	
	
	while (list ($a, $line) = each ($values) ){
		if($line==null){continue;}
		
		
		
		if(preg_match("#(.+?)=(.+)#", $line,$rz)){
			$key=$rz[1]; $xval=$rz[2];
			if(!isset($array[$md5]["$key"])){
				$array[$md5]["$key"]=$xval;
				continue;
			}
			continue;
		}
		
		if(preg_match("#([a-z]+)#", $line,$ri)){
			$array[$md5]["proto"]=$ri[1];
		}
		
		if(!isset($array[$md5]["status"])){
			if(preg_match("#([A-Z\_]+)#", $line,$ri)){
				$array[$md5]["status"]=$ri[1];
				continue;
			}
		}
		
		if(preg_match("#\[(.+?)\]#", $line,$ra)){
			$array[$md5]["status"]=$ra[1];
			continue;
			
		}
		
		
		
	}
	$c++;
	if($c>=$max){break;}

}
fclose($handle);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/conntrack.inc", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/web/conntrack.inc", 0755);
	
}



function iptables_events(){
	$unix=new unix();
	$search=$_GET["search"];
	$rp=$_GET["rp"];
	$eth=$_GET["eth"];
	$logfile="/usr/share/artica-postfix/ressources/logs/web/iptables.log";
	
	if($eth<>null){
		if($search<>null){
			$search="($search.*?={$eth}|={$eth}.*?$search)";
		}else{
			$search="?={$eth}";
		}
	}
	
	$grep=$unix->find_program("grep");
	$tail=$unix->find_program("tail");
	if($search==null){
			$cmdline="$tail -n $rp /var/log/iptables.log >$logfile 2>&1";
			writelogs_framework($cmdline,__FUNCTION__,__FILE__,__LINE__);
			shell_exec($cmdline);
			@chmod($logfile,0777);
			return;
	}
	
	if($search<>null){
		$cmdline="$grep -E \"$search\" /var/log/iptables.log|$tail -n $rp  >$logfile 2>&1";
		writelogs_framework($cmdline,__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmdline);
		@chmod($logfile,0777);
		return;
		
	}
}

