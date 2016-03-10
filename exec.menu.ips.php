<?php
//http://ftp.linux.org.tr/slackware/slackware_source/n/network-scripts/scripts/netconfig
if(preg_match("#--verbose#",implode(" ",$argv))){
	$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__)."/ressources/class.resolv.conf.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");

if($argv[1]=="--network-menu"){network_menu();exit;}
if($argv[1]=="--interface"){interface_menu($argv[2]);exit;}
if($argv[1]=="--savenic"){savenic($argv[2]);exit;}
if($argv[1]=="--savedns"){savedns();exit;}
if($argv[1]=="--reconfigure"){reconfigure();exit;}
if($argv[1]=="--uuid"){new_uuid();exit;}
if($argv[1]=="--stopfw"){stopfw();exit;}
if($argv[1]=="--EnableInterface"){interface_enable($argv[2]);exit;}
if($argv[1]=="--DisableInterface"){interface_disable($argv[2]);exit;}
if($argv[1]=="--removerrules"){routing_rules_delete($argv[2]);exit;}

display_menu();


function display_menu(){
	
	$unix=new unix();
	$results=$unix->NETWORK_ALL_INTERFACES();
	$f=array();
	while (list ($Interface, $ligne) = each ($results) ){
		if($Interface=="lo"){continue;}
		$f[]="On $Interface: https://{$ligne["IPADDR"]}:9000";
		
	}
	if(count($f)==0){
		system("dhclient -v eth0 >/dev/null 2>&1 &");
		return;
		
	}
	
	echo "Web interface URIs:\\n".@implode("\\n", $f);
	
}

function interface_enable($eth){
	
	
	$nicClass=new system_nic($eth);
	$nicClass->enabled=1;
	$nicClass->SaveNic();
	network_menu();
	
}
function interface_disable($eth){


	$nicClass=new system_nic($eth);
	$nicClass->enabled=0;
	$nicClass->SaveNic();
	network_menu();

}

function routing_rules_delete($eth){
	
	
	$q=new mysql();
	$results=$q->QUERY_SQL("SELECT ID FROM routing_rules WHERE nic='$eth'","artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$q->QUERY_SQL("DELETE FROM routing_rules WHERE ID='$ID'","artica_backup");
		$q->QUERY_SQL("DELETE FROM routing_rules_dest WHERE ruleid='$ID'","artica_backup");
		$q->QUERY_SQL("DELETE FROM routing_rules_src WHERE ruleid='$ID'","artica_backup");
		
	}
	network_menu();
}


function reconfigure(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	system("$php /usr/share/artica-postfix/exec.virtuals-ip.php --build");	
	system("/etc/init.d/artica-ifup stop");
	system("/etc/init.d/artica-ifup start");
	
	
}

function stopfw(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	if(is_file("/etc/init.d/firehol")){
		echo "Stopping FireWall service\n";
		system("/etc/init.d/firehol stop");
	}
	echo "Cleaning firewall rules\n";
	system("$php /usr/share/artica-postfix/exec.chilli.php --iptablesx");	
	echo "Done\n";
}

function new_uuid(){
	$unix=new unix();
	$chattr=$unix->find_program("chattr");
	echo "Old uuid:".@file_get_contents("/etc/artica-postfix/settings/Daemons/SYSTEMID")."\n";
	shell_exec("$chattr -i /etc/artica-postfix/settings/Daemons/SYSTEMID");
	$uuid=trim($unix->gen_uuid());
	echo "New uuid: $uuid\n";

	if(strlen($uuid)>5){
		@file_put_contents("/etc/artica-postfix/settings/Daemons/SYSTEMID", $uuid);
		@file_put_contents("/etc/artica-postfix/settings/Daemons/SYSTEMID_CREATED", time());
		@chmod("/etc/artica-postfix/settings/Daemons/SYSTEMID", 0777);
		shell_exec("$chattr +i /etc/artica-postfix/settings/Daemons/SYSTEMID");

	}
	echo "\nSuccess\n\n";
	
	

}


function network_menu(){
$ARTICAVERSION=@file_get_contents("/usr/share/artica-postfix/VERSION");
$unix=new unix();
$HOSTNAME=$unix->hostname_g();
$DIALOG=$unix->find_program("dialog");	
$php=$unix->LOCATE_PHP5_BIN();

$f=explode("\n",@file_get_contents("/etc/resolv.conf"));


while (list ($gpid, $val) = each ($f) ){
	if(preg_match("#^nameserver\s+(.+)#", $val,$re)){
		$DNS[]=$re[1];
		if(count($DNS)>1){break;}
	}

}


$diag[]="$DIALOG --clear  --nocancel --backtitle \"Software version $ARTICAVERSION on $HOSTNAME\"";
$diag[]="--title \"[ N E T W O R K - M E N U ]\"";
$diag[]="--menu \"You can use the UP/DOWN arrow keys\nChoose the TASK\" 20 100 50";
$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();


$tt=explode("\n", @file_get_contents("/proc/net/dev"));


$diag[]="RECONF \"Reconfigure/Rebuild Network\"";
$diag[]="DNS \"DNS setup\"";

while (list ($index, $line) = each ($tt) ){
	if(!preg_match("#^(.*?):\s+#", $line,$re)){continue;}
	$re[1]=trim($re[1]);
	if($re[1]=="lo"){continue;}
	if(preg_match("#^gre#", $re[1])){continue;}
	if(preg_match("#^wccp#", $re[1])){continue;}
	$DEFAULT=$NETWORK_ALL_INTERFACES[$re[1]]["IPADDR"];
	$NETMASK=$NETWORK_ALL_INTERFACES[$re[1]]["NETMASK"];
	$GATEWAY=$NETWORK_ALL_INTERFACES[$re[1]]["GATEWAY"];
	
	$nicClass=new system_nic($re[1]);
	$q=new mysql();
	$conf="$DEFAULT/$NETMASK";
	if($nicClass->enabled==0){$conf=" * disabled *";}
	
	
	if($nicClass->enabled==1){
		
		$diag[]="{$re[1]} \"Modify {$re[1]} interface ($conf)\"";
		$case[]="{$re[1]}) modify$index;;";
		
		
		$funct[]="function modify$index(){";
		$funct[]="\t$php ".__FILE__." --interface {$re[1]}";
		$funct[]="\t/tmp/bash_network_menu_interface.sh";
		$funct[]="}";
		
		
		$diag[]="{$re[1]}D \"Disable {$re[1]} interface\"";
		$case[]="{$re[1]}D) Disable$index;;";
		
		$RoutesRules=mysql_fetch_array($q->QUERY_SQL("SELECT count(*) as tcount FROM routing_rules WHERE nic='{$re[1]}'","artica_backup"));
		if($RoutesRules["tcount"]>0){
			$diag[]="{$re[1]}R \"Remove {$re[1]} routing rules\"";
			$case[]="{$re[1]}R) RemoveRRules$index;;";
		}
		
		$funct[]="function RemoveRRules$index(){";
		$funct[]="\t$php ".__FILE__." --removerrules {$re[1]}";
		$funct[]="\t/tmp/bash_network_menu.sh";
		$funct[]="\texit";
		$funct[]="}";
		
		$funct[]="function Disable$index(){";
		$funct[]="\t$php ".__FILE__." --DisableInterface {$re[1]}";
		$funct[]="\t/tmp/bash_network_menu.sh";
		$funct[]="\texit";
		$funct[]="}";
		
		
	}else{
		$diag[]="{$re[1]}E \"Activate {$re[1]} interface * disabled *\"";
		$case[]="{$re[1]}E) Enable$index;;";
		$funct[]="function Enable$index(){";
		$funct[]="\t$php ".__FILE__." --EnableInterface {$re[1]}";
		$funct[]="\t/tmp/bash_network_menu.sh";
		$funct[]="\texit";
		$funct[]="}";
	}
	
	

	
	
}

$diag[]="STOPFW \"Stop the FireWall\"";
$diag[]="UUID \"Generate a new Unique identifier\"";
$diag[]="Broadcom \"Install Broadcom driver\"";

$diag[]="Quit \"Return to main menu\" 2>\"\${INPUT}\"";

$f[]="#!/bin/bash";
$f[]="INPUT=/tmp/menu.sh.$$";
$f[]="OUTPUT=/tmp/output.sh.$$";
$f[]="trap \"rm \$OUTPUT; rm \$INPUT; exit\" SIGHUP SIGINT SIGTERM";
$f[]="DIALOG=\${DIALOG=dialog}";

$f[]="function change_dns(){";
$f[]="\t$DIALOG --clear --title \"ENTER IP ADDRESS FOR THE DNS 1\" --inputbox \"Enter your IP address for the DNS number 1.\\nExample: 255.255.255.0\" 10 68 {$DNS[0]} 2> /etc/artica-postfix/WIZARDMASK_DNS1";
$f[]="\tif [ $? = 1 -o $? = 255 ]; then";
$f[]="\t\trm -f /etc/artica-postfix/WIZARDMASK_DNS1";
$f[]="\t\treturn";
$f[]="\tfi";		
$f[]="\t$DIALOG --clear --title \"ENTER IP ADDRESS FOR THE DNS 2\" --inputbox \"Enter your IP address for the DNS number 2.\\nExample: 255.255.255.0\" 10 68 {$DNS[1]} 2> /etc/artica-postfix/WIZARDMASK_DNS2";
$f[]="\tif [ $? = 1 -o $? = 255 ]; then";
$f[]="\t\trm -f /etc/artica-postfix/WIZARDMASK_DNS2";
$f[]="\t\treturn";
$f[]="\tfi";	
$f[]="\tif [ -f /tmp/dns.log ]; then";
$f[]="\t\trm /tmp/dns.log";
$f[]="\tfi";
$f[]="\t$php ".__FILE__." --savedns >/tmp/dns.log &";
$f[]="\t$DIALOG --tailbox /tmp/dns.log  25 150";	
$f[]="}";
$f[]="";
$f[]="function reconfigure_network(){";
$f[]="\tif [ -f /tmp/dns.log ]; then";
$f[]="\t\trm /tmp/dns.log";
$f[]="\tfi";
$f[]="\t$php ".__FILE__." --reconfigure >/tmp/dns.log &";
$f[]="\t$DIALOG --tailbox /tmp/dns.log  25 150";
$f[]="}";
$f[]="";
$f[]="function stop_firewall(){";
$f[]="\t$DIALOG --title \"STOP the Firewall\" --yesno \"Warning, this operation will remove all NAT/REDIRECT methods.\\nDo you need to perform this operation ? Press 'Yes' to continue, or 'No' to exit\" 0 0";
$f[]="\tcase $? in";
$f[]="\t\t0)";
$f[]="\tif [ -f /tmp/dns.log ]; then";
$f[]="\t\trm /tmp/dns.log";
$f[]="\tfi";
$f[]="\t$php ".__FILE__." --stopfw >/tmp/dns.log &";
$f[]="\t$DIALOG --tailbox /tmp/dns.log  25 150";
$f[]="\t\treturn;;";
$f[]="\t1)";
$f[]="\t\treturn;;";
$f[]="\t255)";
$f[]="\t\treturn;;";
$f[]="\tesac";
$f[]="}";
$f[]="";
$f[]="function change_uuid(){";
$f[]="\t$DIALOG --title \"Generate a new uuid\" --yesno \"Warning, this operation should break the associated license\\nDo you need to perform this operation ? Press 'Yes' to continue, or 'No' to exit\" 0 0";
$f[]="\tcase $? in";
$f[]="\t\t0)";
$f[]="\tif [ -f /tmp/dns.log ]; then";
$f[]="\t\trm /tmp/dns.log";
$f[]="\tfi";
$f[]="\t$php ".__FILE__." --uuid >/tmp/dns.log &";
$f[]="\t$DIALOG --tailbox /tmp/dns.log  25 150";
$f[]="\t\treturn;;";
$f[]="\t1)";
$f[]="\t\treturn;;";
$f[]="\t255)";
$f[]="\t\treturn;;";
$f[]="\tesac";
$f[]="}";
$f[]="function BroadcomInstall(){";
$f[]="\tif [ -f /tmp/dns.log ]; then";
$f[]="\t\trm /tmp/dns.log";
$f[]="\tfi";
$f[]="\t$php /usr/share/artica-postfix/exec.bnx2.enable.php >/tmp/dns.log &";
$f[]="\t$DIALOG --tailbox /tmp/dns.log  25 150";
$f[]="}";
$f[]="";









$f[]=@implode("\n", $funct);
$f[]="while true";
$f[]="do";
$f[]=@implode(" ", $diag);
$f[]="menuitem=$(<\"\${INPUT}\")";
$f[]="case \$menuitem in";
$f[]="RECONF) reconfigure_network;;";
$f[]="UUID) change_uuid;;";
$f[]="DNS) change_dns;;";
$f[]="STOPFW) stop_firewall;;";
$f[]="Broadcom) BroadcomInstall;;";


$f[]=@implode("\n", $case);
$f[]="Quit) break;;";
$f[]="esac";
$f[]="done\n";

if($GLOBALS["VERBOSE"]){echo "Writing /tmp/bash_network_menu.sh\n";}
@file_put_contents("/tmp/bash_network_menu.sh", @implode("\n",$f));
@chmod("/tmp/bash_network_menu.sh",0755);
	
}

function interface_menu($eth){
	$ARTICAVERSION=@file_get_contents("/usr/share/artica-postfix/VERSION");
	$unix=new unix();
	$HOSTNAME=$unix->hostname_g();
	$DIALOG=$unix->find_program("dialog");
	$php=$unix->LOCATE_PHP5_BIN();	
	$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
	$DEFAULT=$NETWORK_ALL_INTERFACES[$eth]["IPADDR"];
	$NETMASK=$NETWORK_ALL_INTERFACES[$eth]["NETMASK"];
	$GATEWAY=$NETWORK_ALL_INTERFACES[$eth]["GATEWAY"];
	
	$f[]="#!/bin/bash";
	$f[]="INPUT=/tmp/menu.sh.$$";
	$f[]="OUTPUT=/tmp/output.sh.$$";
	$f[]="trap \"rm \$OUTPUT; rm \$INPUT; exit\" SIGHUP SIGINT SIGTERM";
	$f[]="DIALOG=\${DIALOG=dialog}";	
	@unlink("/etc/artica-postfix/WIZARDIP_$eth");
	@unlink("/etc/artica-postfix/WIZARDMASK_$eth");
	$f[]="$DIALOG --clear --title \"ENTER IP ADDRESS FOR '$eth'\" --inputbox \"Enter your IP address for the $eth Interface.\\nExample: 111.112.113.114\" 10 68 $DEFAULT 2> /etc/artica-postfix/WIZARDIP_$eth";
	
	$f[]="if [ $? = 1 -o $? = 255 ]; then";
	$f[]="rm -f /etc/artica-postfix/WIZARDIP_$eth";
	$f[]="\treturn";
	$f[]="fi";
	
	
	$f[]="$DIALOG --clear --title \"ENTER IP ADDRESS FOR '$eth'\" --inputbox \"Enter your netmask for the $eth Interface.\\nExample: 255.255.255.0\" 10 68 $NETMASK 2> /etc/artica-postfix/WIZARDMASK_$eth";
	$f[]="if [ $? = 1 -o $? = 255 ]; then";
	$f[]="rm -f /etc/artica-postfix/WIZARDMASK_$eth";
	$f[]="\treturn";
	$f[]="fi";
	
	$f[]="$DIALOG --clear --title \"ENTER IP ADDRESS FOR '$eth'\" --inputbox \"Enter your gateway for the $eth Interface.\\nExample: 111.112.113.114\\nIf this interface is the main gateway of your network, set 0.0.0.0 here\" 10 68 $GATEWAY 2> /etc/artica-postfix/WIZARDGATEWAY_$eth";
	$f[]="if [ $? = 1 -o $? = 255 ]; then";
	$f[]="rm -f /etc/artica-postfix/WIZARDGATEWAY_$eth";
	$f[]="\treturn";
	$f[]="fi";	
	
	$f[]="WIZARDIP=`cat /etc/artica-postfix/WIZARDIP_$eth`";
	$f[]="WIZARDMASK=`cat /etc/artica-postfix/WIZARDMASK_$eth`";
	$f[]="WIZARDGATEWAY=`cat /etc/artica-postfix/WIZARDGATEWAY_$eth`";
	
	$f[]="$DIALOG --title \"NETWORK SETUP COMPLETE\" --yesno \"Your networking system is now configured to use:\\n\$WIZARDIP/\$WIZARDMASK Gateway \$WIZARDGATEWAY\\nIs this correct?  Press 'Yes' to continue, or 'No' to exit\" 0 0";
  	$f[]="case $? in";
  	$f[]="0)";
  	$f[]="\techo \"$php ".__FILE__." --savenic $eth\"";
    $f[]="\t$php ".__FILE__." --savenic $eth >/tmp/$eth.log &";
    $f[]="\t$DIALOG --tailbox /tmp/$eth.log  25 150"; 
    
    $f[]="\tWIZARDRESULTS=`cat /etc/artica-postfix/WIZARDRESULT_$eth`";
  	$f[]="\tif [ \"\$WIZARDRESULTS\" eq 0 ]; then";
  	$f[]="\t$DIALOG --title \"$eth failed\" --msgbox \"Sorry, An error has occured\" 9 70";
  	$f[]="\tfi";
    $f[]="\treturn;;";
	$f[]="1)";
   	$f[]="\treturn;;";
  	$f[]="255)";
  	$f[]="\treturn;;";
	$f[]="esac";
	
	
	$f[]="\n";
	@file_put_contents("/tmp/bash_network_menu_interface.sh", @implode("\n",$f));
	@chmod("/tmp/bash_network_menu_interface.sh",0755);
	
}

function savenic($NIC){
	$unix=new unix();
	$ipClass=new IP();
	$ETH_IP=trim(@file_get_contents("/etc/artica-postfix/WIZARDIP_$NIC"));
	$NETMASK=trim(@file_get_contents("/etc/artica-postfix/WIZARDMASK_$NIC"));
	$GATEWAY=trim(@file_get_contents("/etc/artica-postfix/WIZARDGATEWAY_$NIC"));
	
	if(!$ipClass->isIPAddress($ETH_IP)){
		echo "* * * * $ETH_IP * * * * WRONG !!!!\n";
		@file_put_contents("/etc/artica-postfix/WIZARDRESULT_$NIC", 0);
		return;
	}
	if(!$ipClass->isIPAddress($GATEWAY)){
		echo "* * * * $GATEWAY * * * * WRONG !!!!\n";
		@file_put_contents("/etc/artica-postfix/WIZARDRESULT_$NIC", 0);
		return;
	}	
	
	$nics=new system_nic($NIC);
	$nics->eth=$NIC;
	$nics->IPADDR=$ETH_IP;
	$nics->NETMASK=$NETMASK;
	$nics->GATEWAY=$GATEWAY;
	$nics->dhcp=0;
	$nics->metric=1;
	$nics->defaultroute=1;
	$nics->enabled=1;	
	
	if(!$nics->SaveNic()){
		echo "* * * * MYSQL ERROR !!! * * * * WRONG !!!!\n";
		@file_put_contents("/etc/artica-postfix/WIZARDRESULT_$NIC", 0);
		return;
	}
	
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$php5=$php;
	system("$php5 ".dirname(__FILE__)." /exec.virtuals-ip.php --build --force >/dev/null 2>&1");
	echo "20%] Please Wait, apply network configuration....\n";
	system("/etc/init.d/artica-ifup start");
	echo "30%] Please Wait, restarting services....\n";
	
	$unix->THREAD_COMMAND_SET("$php5 /usr/share/artica-postfix/exec.postfix.maincf.php --reconfigure");
	$unix->THREAD_COMMAND_SET("/usr/share/artica-postfix/bin/artica-install --reconfigure-cyrus");
	system("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");
	system("$nohup /etc/init.d/nginx restart >/dev/null 2>&1 &");
	system("$nohup /etc/init.d/monit restart >/dev/null 2>&1 &");
	echo "30%] Please Wait, Changing IP address to $NIC....\n";
	$ifconfig=$unix->find_program("ifconfig");
	system("$ifconfig $NIC down");
		system("$ifconfig $NIC $ETH_IP netmask $NETMASK up");
		system("/bin/ip route add 127.0.0.1 dev lo");
		if($GATEWAY<>"0.0.0.0"){
		echo "31%] Please Wait, Define default gateway to $GATEWAY....\n";
		system("/sbin/route add $GATEWAY dev $NIC");
		$route=$unix->find_program("route");
		shell_exec("$route add -net 0.0.0.0 gw $GATEWAY dev $NIC metric 1");
		}
		echo "95%] Restarting Web Console\n";
		system("/etc/init.d/artica-webconsole restart");
		echo "100%] Configuration done.\n";
		@file_put_contents("/etc/artica-postfix/WIZARDRESULT_$NIC", 1);
	
		echo "###################################################\n";
		echo "############                          #############\n";
		echo "############         SUCCESS          #############\n";
		echo "############                          #############\n";
		echo "###################################################\n\n\n\n";
}

function savedns(){
	
	$DNS1=@file_get_contents("/etc/artica-postfix/WIZARDMASK_DNS1");
	$DNS2=@file_get_contents("/etc/artica-postfix/WIZARDMASK_DNS2");
	
	$resolv=new resolv_conf();
	echo "92%] Set DNS to $DNS1 - $DNS2\n";
	$resolv->MainArray["DNS1"]=$DNS1;
	$resolv->MainArray["DNS2"]=$DNS1;
	$resolv->output=true;
	echo "93%] Saving config\n";
	$resolvDatas=$resolv->build();
	echo "94%] Saving /etc/resolv.conf\n";
	@file_put_contents("/etc/resolv.conf", $resolvDatas);
	echo "###################################################\n";
	echo "############                          #############\n";
	echo "############         SUCCESS          #############\n";
	echo "############                          #############\n";
	echo "###################################################\n\n\n\n";
}

