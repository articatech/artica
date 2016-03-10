#!/usr/bin/php
<?php

if($argv[1]=="--delete"){
	DeleteRules();
	return;
}


CreateRules();
function CreateRules(){

$iptables=find_program("iptables");
$iptables_save="/sbin/iptables-save";
$iptables_restore="/sbin/iptables-restore";
$MIKROTIK_FIREWALL=unserialize(@file_get_contents("/etc/squid3/MIKROTIK_FIREWALL.array"));
DeleteRules();
if(count($MIKROTIK_FIREWALL)==0){return;}



$suffixTables="-m comment --comment \"ArticaMikroTik\"";
$SquidMikrotikMaskerade=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidMikrotikMaskerade"));

while (list ($INDEX, $ARRAY) = each ($MIKROTIK_FIREWALL) ){
	$PORT=$ARRAY["PORT"];
	$SRC_PORT=$ARRAY["SRC_PORT"];
	$IPADDR=$ARRAY["IPADDR"];
	
	$cmd="$iptables -t mangle -I PREROUTING -p tcp --dport $SRC_PORT -j TPROXY --tproxy-mark 0x1/0x1 --on-port $PORT $suffixTables";
	echo "$cmd\n";
	exec("$cmd >/dev/null 2>&1");

	$cmd="$iptables -t nat -I PREROUTING -s $IPADDR -p tcp --dport $SRC_PORT -j ACCEPT $suffixTables";
	//$cmd="$iptables -t nat -I PREROUTING -p tcp --dport $SRC_PORT -j ACCEPT $suffixTables";
	echo "$cmd\n";exec("$cmd >/dev/null 2>&1");
}

$cmd="$iptables -t mangle -N DIVERT $suffixTables >/dev/null 2>&1";
echo "$cmd\n";
system("$cmd");

if($SquidMikrotikMaskerade==1){
	exec("$iptables -t nat -I POSTROUTING -j MASQUERADE $suffixTables");
}

$cmd="$iptables -t mangle -I PREROUTING -p tcp -m socket -j DIVERT $suffixTables";
echo "$cmd\n";exec("$cmd >/dev/null 2>&1");

$cmd="$iptables -t mangle -I DIVERT -j ACCEPT $suffixTables";
echo "$cmd\n";
system("$cmd");

$cmd="$iptables -t mangle -I DIVERT -j MARK --set-mark 1 $suffixTables";
echo "$cmd\n";
system("$cmd");



shell_exec("/sbin/sysctl -w net.ipv4.ip_forward=1 >/dev/null 2>&1");
shell_exec("/sbin/sysctl -w net.ipv4.conf.default.send_redirects=0 >/dev/null 2>&1");
shell_exec("/sbin/sysctl -w net.ipv4.conf.all.send_redirects=0 >/dev/null 2>&1");
shell_exec("/sbin/sysctl -w net.ipv4.conf.all.accept_redirects=0 >/dev/null 2>&1");
shell_exec("modprobe ip_tables >/dev/null 2>&1");
shell_exec("modprobe nf_conntrack_ipv4 >/dev/null 2>&1");
shell_exec("modprobe xt_tcpudp >/dev/null 2>&1");
shell_exec("modprobe nf_tproxy_core >/dev/null 2>&1");
shell_exec("modprobe xt_MARK2 >/dev/null 2>&1");
shell_exec("modprobe xt_TPROXY2 >/dev/null 2>&1");
shell_exec("modprobe xt_socket2 >/dev/null 2>&1");


}
function DeleteRules(){
	$d=0;
	
	$iptables_save=find_program("iptables-save");
	exec("$iptables_save > /etc/artica-postfix/iptables-mikrotik.conf");
	
	$data=file_get_contents("/etc/artica-postfix/iptables-mikrotik.conf");
	$datas=explode("\n",$data);
	$pattern2="#.+?ArticaMikroTik#";

	$iptables_restore=find_program("iptables-restore");
	while (list ($num, $ligne) = each ($datas) ){
		if($ligne==null){continue;}
		if(preg_match($pattern2,$ligne)){
			echo "Remove $ligne\n";
			$d++;continue;}

		$conf=$conf . $ligne."\n";
	}
	file_put_contents("/etc/artica-postfix/iptables-mikrotik.new.conf",$conf);
	system("$iptables_restore < /etc/artica-postfix/iptables-mikrotik.new.conf");
	

}
function find_program($strProgram){
	global $addpaths;
	$arrPath = array('/bin', '/sbin', '/usr/bin', '/usr/sbin', '/usr/local/bin',
			'/usr/local/sbin','/usr/kerberos/bin','/usr/libexec');
	if (function_exists("is_executable")) {
		foreach($arrPath as $strPath) {$strProgrammpath = $strPath . "/" . $strProgram;if (is_executable($strProgrammpath)) {return $strProgrammpath;}}
	} else {
		return strpos($strProgram, '.exe');
	}
}