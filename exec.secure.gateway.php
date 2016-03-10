#!/usr/bin/php
<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--verbose#",@implode(" ", $argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) .'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');



if($argv[1]=="--delete"){
	DeleteRules();
	return;
}


CreateRules();

function CreateRules(){
	$unix=new unix();
	$q=new mysql();	
	$iptables=$unix->find_program("iptables");
	$iptables_save="/sbin/iptables-save";
	$iptables_restore="/sbin/iptables-restore";
	$sql="SELECT *  FROM `gateway_secure` WHERE enabled=1";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){return;}
	DeleteRules();

	$suffixTables="-m comment --comment \"ArticaSecureGateway\"";
	$EnableSecureGateway=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableSecureGateway"));
	if($EnableSecureGateway==0){
		@unlink("/bin/artica-secure-gateway.sh");
		return;
	}

$PROTO[0]="tcp";
$PROTO[1]="udp";
$SH[]="#!/bin/sh";




$SH[]="$iptables -I FORWARD -p tcp -m tcp $suffixTables -j REJECT";
$SH[]="$iptables -I FORWARD -p udp -m udp $suffixTables -j REJECT";
$SH[]="$iptables -I FORWARD -p icmp -m conntrack --ctstate RELATED -j ACCEPT";

while ($ligne = mysql_fetch_assoc($results)) {
	$dport=$ligne["dport"];
	$xPROTO=$PROTO[$ligne["dproto"]];
	$SH[]="$iptables -I FORWARD -p $xPROTO -m $xPROTO --dport $dport $suffixTables -j ACCEPT >/dev/null 2>&1";
}


$net=new networkscanner();
while (list ($num, $maks) = each ($net->networklist)){
	if(trim($maks)==null){continue;}
	$SH[]="$iptables -I FORWARD -p tcp -m tcp -d $maks $suffixTables -j ACCEPT";
}



$SH[]="";

@file_put_contents("/bin/artica-secure-gateway.sh", @implode("\n", $SH));
@chmod("/bin/artica-secure-gateway.sh",0755);




}
function DeleteRules(){
	$d=0;
	
	$iptables_save=find_program("iptables-save");
	exec("$iptables_save > /etc/artica-postfix/iptables-securegw.conf");
	
	$data=file_get_contents("/etc/artica-postfix/iptables-securegw.conf");
	$datas=explode("\n",$data);
	$pattern2="#.+?ArticaSecureGateway#";
	$conf=null;
	$iptables_restore=find_program("iptables-restore");
	while (list ($num, $ligne) = each ($datas) ){
		if($ligne==null){continue;}
		if(preg_match($pattern2,$ligne)){
			echo "Remove $ligne\n";
			$d++;continue;}

		$conf=$conf . $ligne."\n";
	}
	file_put_contents("/etc/artica-postfix/iptables-securegw.new.conf",$conf);
	system("$iptables_restore < /etc/artica-postfix/iptables-securegw.new.conf");
	

}
