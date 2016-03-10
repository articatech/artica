<?php

include_once(dirname(__FILE__).'/class.users.menus.inc');
include_once(dirname(__FILE__).'/class.system.network.inc');
include_once(dirname(__FILE__).'/class.ldap.inc');
include_once(dirname(__FILE__).'/class.ini.inc');
include_once(dirname(__FILE__).'/class.bind9.inc');
include_once(dirname(__FILE__) .'/class.tcpip.inc');
include_once(dirname(__FILE__) .'/class.mysql.inc');
include_once(dirname(__FILE__) .'/class.dhcpd-sub.inc');
include_once(dirname(__FILE__) .'/class.computers.inc');

class dhcpd_hosts{
	public $uid;
	public $dn;
	public $dhcpHWAddress;
	public $dhcpStatements;
	public $dds_domain;
	public $hostname;
	public $IP;
	public $MAC;
	
	function dhcpd_hosts($uid=null){
		if(!isset($GLOBALS["ASROOT"])){$GLOBALS["ASROOT"]=false;}
		if($uid==null){
			writelogs("DHCP:: Resquest an uid null, aborting",__CLASS__.'/'.__FUNCTION__,__FILE__);
			return null;
		}
		$this->uid=$uid;	
		$this->load();
		
	}
	
	private function load(){
		if(substr($this->uid,0,1)=='$'){$this->uid=substr($this->uid,1,strlen($this->uid));}
		if(substr($this->uid,strlen($this->uid)-1,1)=='$'){$this->uid=substr($this->uid,0,strlen($this->uid)-1);}
		$ldap=new clladp();
		$this->dn="cn=$this->uid,cn=DhcpConfig,ou=dhcp,$ldap->suffix";
		if(!$ldap->ExistsDN($this->dn)){return null;}
		$hash=$ldap->Ldap_read($this->dn,"(objectclass=*)",array());
		$this->dhcpHWAddress=$hash[0][strtolower("dhcpHWAddress")][0];
		$this->dhcpStatements=$hash[0][strtolower("dhcpStatements")][0];
	
		
	}
	
function DeleteFixed(){
	$ldap=new clladp();
	if(!$ldap->ExistsDN($this->dn)){return true;}
	$tpl=new templates();
	
	include_once(dirname(__FILE__)."/class.mysql.inc");
	$sql="DELETE FROM dhcpd_fixed WHERE mac='$this->MAC'";
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error." in ".__CLASS__."/".__FUNCTION__." line ".__LINE__;}	
	
	if(!$ldap->ldap_delete($this->dn)){
		echo $tpl->_ENGINE_parse_body("DHCP:: {error} function:".__FUNCTION__."\nfile: ".__FILE__."\nline:".__LINE__."\n");
		echo $ldap->ldap_last_error;
		return false;
	}

	
	return true;
	
}	
function AddFixed(){
	$ldap=new clladp();
	$tpl=new templates();
	$tcp=new IP();
	
	if(!$tcp->isValid($this->IP)){
		echo $tpl->_ENGINE_parse_body("DHCP:: {error} IP: $this->IP {invalid}\n");
		return false;
	}	
	
	
	include_once(dirname(__FILE__)."/class.mysql.inc");
	$sql="INSERT IGNORE INTO dhcpd_fixed (mac,ipaddr,hostname,domain) VALUES ('$this->MAC','$this->IP','$this->uid','$this->dds_domain')";
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error." in ".__CLASS__."/".__FUNCTION__." line ".__LINE__;return false;}
	
	
	$sql="UPDATE dhcpd_fixed SET 
		ipaddr='$this->IP',
		hostname='$this->hostname',
		domain='$this->dds_domain' WHERE mac='$this->MAC'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error." in ".__CLASS__."/".__FUNCTION__." line ".__LINE__;return false;}
	
	
	$cmp=new computers();
	$uid=$cmp->ComputerIDFromMAC($this->MAC);
	$cmp->uid=$uid;
	$cmp->ComputerIP=$this->IP;
	$cmp->ComputerMacAddress=$this->MAC;
	$cmp->ComputerRealName=$this->hostname;
	$cmp->Add();
	return true;	
	
}
	
}


class dhcpd{
	public $ddns_domainname="";
	public $max_lease_time;
	public $array_tcp;
	public $listen_nic;
	public $netmask;
	public $range2;
	public $range1;
	public $DNS_1;
	public $DNS_2;
	public $EnableArticaAsGateway=0;
	public $EnableArticaAsDNSFirst=0;
	public $do_no_verify_range=0;
	public $gateway;
	public $pxe_enable=0;
	public $pxe_server=null;
	public $pxe_file=null;
	public $conf;
	public $routes=array();
	public $ntp_server=null;
	public $OnlySetGateway=0;
	public $subnet;
	public $broadcast=null;
	public $WINS=null;
	public $noload=0;
	public $authoritative=0;
	public $ping_check=0;
	public $noloadFixed=0;
	public $local_pac_server=null;
	public $browser_portal_page=null;
	public $get_lease_hostnames=0;
	
	function dhcpd($noload=0,$noloadFixed=0){
		if($noload<>0){$this->noload=$noload;return ;}
		$this->noloadFixed=$noloadFixed;
		$sock=new sockets();
		$ini=new Bs_IniHandler();
		$ipClass=new IP();

		
		$this->EnableArticaAsGateway=$sock->GET_INFO("EnableArticaAsGateway");
		$ArticaDHCPSettings=$sock->GET_INFO('ArticaDHCPSettings');
		if($ArticaDHCPSettings==null){
			$ArticaDHCPSettings="
			[SET]get_lease_hostnames=\n\nddns_domainname=\nmax_lease_time=\nnetmask=\nrange1=\nlisten_nic=\nrange1=\nrange2=\nEnableArticaAsDNSFirst=\ngateway=\nDNS_1=\nDNS_2=\nping_check=\n";
			
		}
		$ini->loadString($ArticaDHCPSettings);
		if(!is_array($ini->_params["SET"])){$ini->_params["SET"]=array();}
		if($GLOBALS["VERBOSE"]){while (list ($num, $ligne) = each ($ini->_params["SET"]) ){echo "Starting......: ".date("H:i:s")." [INIT]: DHCP Server server DEBUG: loading $num = $ligne\n";}}		
		
		$this->ddns_domainname=$ini->_params["SET"]["ddns_domainname"];
		$this->max_lease_time=$ini->_params["SET"]["max_lease_time"];
		$this->listen_nic=$ini->_params["SET"]["listen_nic"];
		$this->netmask=$ini->_params["SET"]["netmask"];
		$this->range1=$ini->_params["SET"]["range1"];
		$this->range2=$ini->_params["SET"]["range2"];
		$this->EnableArticaAsDNSFirst=trim($ini->_params["SET"]["EnableArticaAsDNSFirst"]);
		$this->gateway=$ini->_params["SET"]["gateway"];
		$this->DNS_1=$ini->_params["SET"]["DNS_1"];
		$this->DNS_2=$ini->_params["SET"]["DNS_2"];
		$this->pxe_server=$ini->_params["SET"]["pxe_server"];
		$this->pxe_file=$ini->_params["SET"]["pxe_file"];
		$this->pxe_enable=$ini->_params["SET"]["pxe_enable"];
		$this->ntp_server=$ini->_params["SET"]["ntp_server"];
		$this->subnet=$ini->_params["SET"]["subnet"];
		$this->broadcast=$ini->_params["SET"]["broadcast"];
		$this->WINS=$ini->_params["SET"]["WINS"];
		$this->authoritative=$ini->_params["SET"]["authoritative"];
		$this->local_pac_server=$ini->_params["SET"]["local_pac_server"];
		$this->get_lease_hostnames=$ini->_params["SET"]["get_lease_hostnames"];
		$this->deny_unkown_clients=intval($ini->_params["SET"]["deny_unkown_clients"]);
		$this->browser_portal_page=$ini->_params["SET"]["browser_portal_page"];
		
		
		
		$this->do_no_verify_range=$ini->_params["SET"]["do_no_verify_range"];
		
		if($this->listen_nic==null){$this->GetDefaultNic();}
		
		if(trim($this->subnet)==null){
			if($ipClass->isIPv4($this->gateway)){
				if(preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)#',trim($this->gateway),$re)){
					$this->subnet="{$re[1]}.{$re[2]}.{$re[3]}.0";
					if(trim($this->range1)==null){$this->range1="{$re[1]}.{$re[2]}.{$re[3]}.50";}
					if(trim($this->range2)==null){$this->range2="{$re[1]}.{$re[2]}.{$re[3]}.254";}
				}
			}
		}
		
		if(!$ipClass->isIPv4($this->range1)){
			if(preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)#',trim($this->subnet),$re)){
				$this->range1="{$re[1]}.{$re[2]}.{$re[3]}.50";
			}
		}
		if(!$ipClass->isIPv4($this->range2)){
			if(preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)#',trim($this->subnet),$re)){
				$this->range2="{$re[1]}.{$re[2]}.{$re[3]}.254";
			}			
		}
		
		$this->ping_check=$ini->_params["SET"]["ping_check"];
		if(!is_numeric($this->authoritative)){$this->authoritative=0;}
		if(!is_numeric($this->get_lease_hostnames)){$this->get_lease_hostnames=0;}
		if(!is_numeric($this->ping_check)){$this->ping_check=1;}
		
		$this->OnlySetGateway=0;
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [INIT]: DHCP Server server DEBUG: loading OnlySetGateway = [DHCPOnlySetGateway]  $this->OnlySetGateway\n";}
		$ip=new networking();
		$ip->LoadIPs();
		$this->array_tcp=$ip->Local_interfaces();
		if($this->pxe_file==null){$this->pxe_file="/tftpboot/pxelinux.0";}
		if($this->max_lease_time==null){$this->max_lease_time=3600;}
		if($this->netmask==null){$this->netmask="255.255.255.0";}
		
		$this->LoadRoutes();
		writelogs("LOADING RemoteVPN ",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		$this->RemoteVPN();
		if(!$GLOBALS["ASROOT"]){if(isset($GLOBALS["DHCPDCONF"])){$this->conf=$GLOBALS["DHCPDCONF"];}else{$this->conf=$this->BuildConf();$GLOBALS["DHCPDCONF"]=$this->conf;}}

	}
	
	function Save($norestart=false){
		writelogs("Save Config file ",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		if($this->noload<>0){return;}
		if(!is_numeric($this->authoritative)){$this->authoritative=0;}
		if(!is_numeric($this->ping_check)){$this->ping_check=1;}
		
		if(!preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $this->range1)){
			if($this->listen_nic<>null){
				$tcp=new networking();
				$tcp->ifconfig($this->listen_nic);
				$tcp_addr=$tcp->tcp_addr;
				preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)$#", $tcp_addr,$ri);
				$this->range1="{$ri[1]}.{$ri[2]}.{$ri[3]}.50";
				$this->range2="{$ri[1]}.{$ri[2]}.{$ri[3]}.253";
				
			}
			
		}
		
		if(!preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $this->gateway)){
			if($this->listen_nic<>null){
				$tcp=new networking();
				$tcp->ifconfig($this->listen_nic);
				$this->gateway=$tcp->gateway;
			}
		}
		
		if(!preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $this->DNS_1)){
			if($this->listen_nic<>null){
				$tcp=new networking();
				$tcp->ifconfig($this->listen_nic);
				$this->DNS_1=$tcp->tcp_addr;
			}
		}
		
		
		$ini=$ini."[SET]\n";
		$ini=$ini."ddns_domainname=$this->ddns_domainname\n";
		$ini=$ini."max_lease_time=$this->max_lease_time\n";
		$ini=$ini."listen_nic=$this->listen_nic\n";
		$ini=$ini."netmask=$this->netmask\n";
		$ini=$ini."range1=$this->range1\n";
		$ini=$ini."range2=$this->range2\n";
		$ini=$ini."gateway=$this->gateway\n";
		$ini=$ini."DNS_1=$this->DNS_1\n";
		$ini=$ini."DNS_2=$this->DNS_2\n";
		$ini=$ini."pxe_server=$this->pxe_server\n";
		$ini=$ini."pxe_file=$this->pxe_file\n";
		$ini=$ini."pxe_enable=$this->pxe_enable\n";
		$ini=$ini."EnableArticaAsDNSFirst=$this->EnableArticaAsDNSFirst\n";
		$ini=$ini."ntp_server=$this->ntp_server\n";
		$ini=$ini."subnet=$this->subnet\n";
		$ini=$ini."broadcast=$this->broadcast\n";
		$ini=$ini."WINS=$this->WINS\n";
		$ini=$ini."authoritative=$this->authoritative\n";
		$ini=$ini."ping_check=$this->ping_check\n";
		$ini=$ini."local_pac_server=$this->local_pac_server\n";
		$ini=$ini."get_lease_hostnames=$this->get_lease_hostnames\n";
		$ini=$ini."do_no_verify_range=$this->do_no_verify_range\n";
		$ini=$ini."deny_unkown_clients=$this->deny_unkown_clients\n";
		$ini=$ini."browser_portal_page=$this->browser_portal_page\n";
		
		
		$sock=new sockets();
		$sock->SaveConfigFile($ini,'ArticaDHCPSettings');
		$sock->SaveConfigFile($this->BuildConf(),'DHCP3ConfigurationFile');
		$sock->SET_INFO('DHCP3ListenNIC',$this->listen_nic);
		
		if(!$norestart){
			$sock->getFrameWork('cmd.php?restart-dhcpd=yes');
		}
		
		
		
	}
	
function DeleteFixed($COMPUTER_NAME){
	$dhcp=new dhcpd_hosts($COMPUTER_NAME);
	return $dhcp->DeleteFixed();
	
}

function AddFixed($COMPUTER_NAME,$MAC,$IP){
	$dhcp=new dhcpd_hosts($COMPUTER_NAME);
	$dhcp->MAC=$MAC;
	$dhcp->IP=$IP;
	return $dhcp->AddFixed();
	
}

function AddRoute($ip,$netmask,$gateway,$info=null){
	$this->LoadRoutes();
	if(!is_array($this->routes[$ip])){
		$this->routes[$ip]=array($netmask,$gateway,$info);
		$this->SaveRoutes();
		$this->Save();
	}
}

function DelRoute($ip){
	$this->LoadRoutes();
	while (list ($ipr, $array) = each ($this->routes) ){
		if($ipr==$ip){
			writelogs("Delete Gateway $ip (".$this->routes[$ip][0].")",__CLASS__.'/'.__FUNCTION__,__FILE__);
			continue;
		}
		$arrayF[]="$ipr;{$array[0]};{$array[1]}";
	}
	
	if(is_array($arrayF)){$conf=implode("\n",$arrayF);}
	$sock=new sockets();
	$sock->SaveConfigFile($conf,"DHCPDRoutesInfos");
}

private function LoadRoutes(){
	
	if(isset($GLOBALS["DHCPD_ROUTES"])){
		while (list ($num, $ligne) = each ($GLOBALS["DHCPD_ROUTES"]) ){
			$this->routes[$num]=$ligne;
		}
		return;
	}
	$GLOBALS["DHCPD_ROUTES"]=array();
	$sock=new sockets();
	$datas=explode("\n",$sock->GET_INFO("DHCPDRoutesInfos"));
	while (list ($num, $ligne) = each ($datas) ){
		$tbl=explode(";",$ligne);
		if(count($tbl)>1){
			$this->routes[$tbl[0]]=array($tbl[1],$tbl[2]);
			$GLOBALS["DHCPD_ROUTES"][$tbl[0]]=array($tbl[1],$tbl[2]);
		}
		
	}
	
}

private function SaveRoutes(){
	if(is_array($this->routes)){
	reset($this->routes);
	$conf=null;
	while (list ($ip, $array) = each ($this->routes) ){
		$ll="$ip;{$array[0]};{$array[1]};{$array[2]}";
		writelogs("$ll",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		$arrayF[]="$ip;{$array[0]};{$array[1]};{$array[2]}";
	}
		if(is_array($arrayF)){
			$conf=implode("\n",$arrayF);
		}
	}
	
	
	
	$sock=new sockets();
	$sock->SaveConfigFile($conf,"DHCPDRoutesInfos");
}


private function GetMyDNS(){
		$ip=new networking();
		if($this->listen_nic==null){$this->GetDefaultNic();}
		$mip=$ip->GetNicInfos($this->listen_nic);	
		$eth_IP=$mip["IPADDR"];
		if($eth_IP=="0.0.0.0"){$eth_IP=$ip->default_network();}
	
		if($this->EnableArticaAsDNSFirst==1){$arrayNameServers_tmp[$eth_IP]=$eth_IP;}
		if($this->DNS_2<>null){$arrayNameServers_tmp[$this->DNS_2]=$this->DNS_2;}
		if($this->DNS_1<>null){$arrayNameServers_tmp[$this->DNS_1]=$this->DNS_1;}
		
		if($GLOBALS["VERBOSE"]){echo "DEBUG: DNS server in array of ". count($arrayNameServers_tmp)."\n";}
		
		if(is_array($ip->arrayNameServers)){
			while (list ($index, $ipaddr) = each ($ip->arrayNameServers) ){
				if($ipaddr=="0.0.0.0"){continue;}
				if($ipaddr=="127.0.0.1"){continue;}
				$arrayNameServers_tmp[$ipaddr]=$ipaddr;
				if($GLOBALS["VERBOSE"]){echo "DEBUG: DNS: $ipaddr\n";}
			}
		}

			$arrayNameServers=array();
			while (list ($index, $ipaddr) = each ($arrayNameServers_tmp) ){
				if($ipaddr=="0.0.0.0"){continue;}
				if($ipaddr=="127.0.0.1"){continue;}
				if($GLOBALS["ASROOT"]){echo "Starting......: ".date("H:i:s")." [INIT]: DHCP Server server name server: \"$ipaddr\"\n";}
				$arrayNameServers[]=$ipaddr;
			}			
		if($GLOBALS["VERBOSE"]){echo "DEBUG: DNS server return array of ". count($arrayNameServers_tmp)." element(s)\n";}	
		return $arrayNameServers;
	
}


private function DomainNameServers(){
	
		$arrayNameServers=array();
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#",$this->DNS_1)){
			if($GLOBALS["ASROOT"]){echo "Starting......: ".date("H:i:s")." [INIT]: DHCP Server server domain name server 1]:$this->DNS_1\n";}
			$arrayNameServers[]=$this->DNS_1;
		}else{
			
		}
		
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#",$this->DNS_2)){
			if($GLOBALS["ASROOT"]){echo "Starting......: ".date("H:i:s")." [INIT]: DHCP Server server domain name server 2]:$this->DNS_2\n";}
			$arrayNameServers[]=$this->DNS_2;
		}		
	
		if(count($arrayNameServers)>0){return $arrayNameServers;}
	
			
		$arrayNameServers=$this->GetMyDNS();
		if($GLOBALS["VERBOSE"]){echo "DEBUG: ->GetMyDNS() ->". count($arrayNameServers)." items Line ".__LINE__."\n";}	
		if($GLOBALS["ASROOT"]){echo "Starting......: ".date("H:i:s")." [INIT]: DHCP Server server ". count($arrayNameServers)." domain name servers\n";}
		if(count($arrayNameServers)>0){return $arrayNameServers;}
		
		if($GLOBALS["ASROOT"]){
			echo "Starting......: ".date("H:i:s")." [INIT]: DHCP Server SERVER no name servers set, try to get defaults via resolv.conf\n";
			$resolv_conf=explode("\n",@file_get_contents("/etc/resolv.conf"));
			while (list ($index, $lines) = each ($resolv_conf) ){
				if(preg_match("#127\.0\.0\.1#",$lines)){continue;}
				if(preg_match("#^nameserver\s+(.+)#",$lines,$re)){
					$g=trim($re[1]);
					if($g=="127.0.0.1"){continue;}
					$arrayNameServers[]=$g;
					if($GLOBALS["ASROOT"]){echo "Starting......: ".date("H:i:s")." [INIT]: DHCP Server SERVER name server: $g\n";}
				}
			}
		}
	
		

		if($GLOBALS["VERBOSE"]){echo "DEBUG: Buiding domain-name-servers with array of ".count($arrayNameServers)." element(s)\n";}	
		return $arrayNameServers;
	
}



	private function GetDefaultNic(){
		$sock=new sockets();
		$devs=unserialize(base64_decode($sock->getFrameWork("cmd.php?list-nics")));
		if(!is_array($devs)){
			if($GLOBALS["VERBOSE"]){echo "WARN!!: \$dev not an array\n";}
			return null;}
			if($GLOBALS["VERBOSE"]){echo "DEBUG:: GetDefaultNic() -> {$devs[0]}\n";}
			$this->listen_nic=$devs[0];
		return $devs[0];
	}
	
	
	function BuildConf(){
		$gateway_array=array();
		if(function_exists("debug_backtrace")){
					try {
						$trace=@debug_backtrace();
						if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
					} catch (Exception $e) {writelogs("LoadfixedAddresses:: Fatal: ".$e->getMessage(),__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				}	

		writelogs("Building configuration $called",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		
		if($GLOBALS["ASROOT"]){if(!is_file("/var/lib/dhcp3/dhcpd.other")){@file_put_contents("/var/lib/dhcp3/dhcpd.other","");}}
		if($GLOBALS["ASROOT"]){@chmod("/var/lib/dhcp3/dhcpd.other",0755);}
		if($GLOBALS["ASROOT"]){@chmod("/var/lib/dhcp3",0755);}
		
		$ip=new networking();
		$users=new usersMenus();
		$users->LoadModulesEnabled();
		$conf=null;
		if($this->listen_nic==null){$this->GetDefaultNic();}
		$mip=$ip->GetNicInfos($this->listen_nic);	
		$eth_gateway=$mip["GATEWAY"];
		$eth_IP=$mip["IPADDR"];
		if(!is_numeric($this->pxe_enable)){$this->pxe_enable=0;}
		
		
		if($this->broadcast==null){
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [INIT]: DHCP Server broadcast =>  eth_IP = $eth_IP\n";}
			preg_match("#^([0-9\.]+)\.([0-9]+)$#", $eth_IP,$re);
			$this->broadcast="{$re[1]}.255";
			
		}
		
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [INIT]: DHCP Server broadcast =  $this->broadcast\n";}
		
		if($this->ddns_domainname<>null){
			$conf=$conf."ddns-domainname \"$this->ddns_domainname\";\n";
		}
		
		if($users->BIND9_INSTALLED){
			$sock=new sockets();
			$Bind9GlobalKey=$sock->GET_INFO('Bind9GlobalKey');
			$conf=$conf."\n";
			$conf=$conf.  "key globalkey {\n";
			$conf=$conf.  "\talgorithm hmac-md5;\n";
			$conf=$conf.  "\tsecret \"$Bind9GlobalKey\";\n";
			$conf=$conf.  "};\n";
			$conf=$conf."\n";
			
			$conf=$conf.  "ddns-update-style interim;\n";
			$conf=$conf . "update-static-leases on;\n";
			$conf=$conf . "do-forward-updates on;\n";
			$conf=$conf . "ddns-updates on;\n"; 
			$conf=$conf . "ignore client-updates;\n";
			$conf=$conf . "update-static-leases on;\n";
		}else{
			$conf=$conf."ddns-update-style none;\n";
		}
		
		if($this->deny_unkown_clients==1){
			$conf=$conf."deny unkown-clients;\n";
		}else{
			$conf=$conf."allow unknown-clients;\n";
		}
		

		
		//$conf=$conf."ddns-hostname = pick (option fqdn.hostname,option host-name,concat (\"dhcp-\", binary-to-ascii (10, 8, \"-\", leased-address)));";
		
		if($this->gateway<>null){
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [INIT]: DHCP Server DEBUG: gateway [". count($gateway_array)."] = $this->gateway set by admin\n";}
			$gateway_array[]=$this->gateway;
		}

		if(preg_match('#([0-9]+)\.([0-9]+)\.([0-9]+)#',$this->WINS)){
			$conf=$conf."option netbios-name-servers $this->WINS;\n";
		}
		
		if(preg_match("#http.*:\/\/.*#", $this->local_pac_server)){
			$conf=$conf."option local-pac-server code 252 = text;\n";
			$conf=$conf."option local-pac-server \"$this->local_pac_server\";\n";
		}
		
		$conf=$conf."# Portal page \"$this->browser_portal_page\"\n";
		
		if(preg_match("#http.*:\/\/.*#", $this->browser_portal_page)){
			$conf=$conf."option browser-portal-page code 224 = string;\n";
			$conf=$conf."option browser-portal-page  \"$this->browser_portal_page\";\n";
			$conf=$conf."option default-url \"$this->browser_portal_page\";\n";
			
		}
		
		

		

		if(preg_match('#([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#',$this->subnet,$re)){
			$SUBNET_1=$re[1];
			$SUBNET_2=$re[2];
			$SUBNET_3=$re[3];
			$SUBNET_4=$re[3];
			if($GLOBALS["ASROOT"]){echo "Starting......: ".date("H:i:s")." [INIT]: DHCP Server Subnet $SUBNET_1 - $SUBNET_2 - $SUBNET_3\n";}
			
		}
		
		
		
		if(!preg_match('#([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#',$this->subnet,$re)){
			if(!preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)#',$this->range1,$re)){
				preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)#',$this->broadcast,$re);
			}
		}
	
		$range_suffix=0;
		if(is_numeric($re[4])){if($re[4]>0){$range_suffix=$re[4];}}
		$final_subnet="{$re[1]}.{$re[2]}.{$re[3]}.$range_suffix";
		if($final_subnet=="...0"){$final_subnet="0.0.0.0";}
		if($GLOBALS["ASROOT"]){echo "Starting......: ".date("H:i:s")." [INIT]: DHCP Server Subnet final_subnet: $final_subnet\n";}
		if($GLOBALS["ASROOT"]){echo "Starting......: ".date("H:i:s")." [INIT]: DHCP Server Subnet Range 1.....: $this->range1\n";}
		
		$xdhcpSub=new dhcpd_sub($this->listen_nic);
		$conf=$conf.$xdhcpSub->BuildRoutes()."\n";		
		
		$conf=$conf."\n";
		$conf=$conf."subnet {$re[1]}.{$re[2]}.{$re[3]}.$range_suffix netmask $this->netmask {\n";
		$conf=$conf."\tinterface $this->listen_nic;\n";
		
		$conf=$conf."\tmax-lease-time $this->max_lease_time;\n";
		$conf=$conf."\tdefault-lease-time $this->max_lease_time;\n";
		
		if($this->authoritative==1){
			$conf=$conf."\tauthoritative;\n";
		}else{
			$conf=$conf."\tnot authoritative;\n";
		}
		
		
		if($this->get_lease_hostnames==1){
			$conf=$conf."\tget-lease-hostnames true;\n";
		}else{
			$conf=$conf."\tget-lease-hostnames false;\n";
		}
		
		
		if($this->ping_check==1){
			$conf=$conf."\tping-check = 1;\n";
		}else{
			$conf=$conf."\tping-check = 0;\n";
		}
		

		
		if($this->do_no_verify_range==0){
		if(preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#',$this->range1,$re)){
			$RANGE_1=$re[1];
			$RANGE_2=$re[2];
			$RANGE_3=$re[3];
			$RANGE_4=$re[4];
			if(is_numeric($SUBNET_1)){
				if($SUBNET_1<>$RANGE_1){$RANGE_1=$SUBNET_1;}
				if($SUBNET_2<>$RANGE_2){$RANGE_2=$SUBNET_2;}
				if($SUBNET_3<>$RANGE_3){$RANGE_3=$SUBNET_3;}
				$this->range1="$RANGE_1.$RANGE_2.$RANGE_3.$RANGE_4";
			}
			
		}
			
		if(!preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#',$this->range2,$re)){
				$this->range2="$SUBNET_1.$SUBNET_2.$SUBNET_3.254";
			}else{
				$RANGE_1=$re[1];
				$RANGE_2=$re[2];
				$RANGE_3=$re[3];
				$RANGE_4=$re[4];
					if(is_numeric($SUBNET_1)){
					if($SUBNET_1<>$RANGE_1){$RANGE_1=$SUBNET_1;}
					if($SUBNET_2<>$RANGE_2){$RANGE_2=$SUBNET_2;}
					if($SUBNET_3<>$RANGE_3){$RANGE_3=$SUBNET_3;}
					$this->range2="$RANGE_1.$RANGE_2.$RANGE_3.$RANGE_4";
				}
			}
			
		}
		

		
		
		
			
		
		if($GLOBALS["ASROOT"]){echo "Starting......: ".date("H:i:s")." [INIT]: DHCP Server Subnet Range ......: $this->range1 - $this->range2\n";}
		
		$conf=$conf."\trange $this->range1 $this->range2;\n";
		$conf=$conf."\n\ton commit {\n";
		$conf=$conf."\t\tset clip = binary-to-ascii(10, 8, \".\", leased-address);\n";
		$conf=$conf."\t\tset clhw = binary-to-ascii(16, 8, \":\", substring(hardware, 1, 6));\n";
		$conf=$conf."\t\tset ClientName = pick-first-value(option fqdn.hostname,option host-name,config-option host-name, noname);\n";
		$conf=$conf."\t\texecute(\"/usr/share/artica-postfix/exec.dhcpd-leases.php\", \"commit\", clip, clhw, ClientName);\n";
		$conf=$conf."\t}\n";		
		$conf=$conf."\n\ton release {\n";
		$conf=$conf."\t\tset clip = binary-to-ascii(10, 8, \".\", leased-address);\n";
		$conf=$conf."\t\tset clhw = binary-to-ascii(16, 8, \":\", substring(hardware, 1, 6));\n";
		$conf=$conf."\t\tset ClientName = pick-first-value(option fqdn.hostname,option host-name,config-option host-name, noname);\n";
		$conf=$conf."\t\texecute(\"/usr/share/artica-postfix/exec.dhcpd-leases.php\", \"release\", clip, clhw, ClientName);\n";
		$conf=$conf."\t}\n";		
		
		
		
		if(preg_match('#([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#',$this->broadcast,$re)){
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [INIT]: DHCP Server DEBUG: broadcast =  $this->broadcast -> {$re[1]}.{$re[2]}.{$re[3]}.{$re[4]}\n";}
			$conf=$conf."\toption broadcast-address {$re[1]}.{$re[2]}.{$re[3]}.{$re[4]};\n";
		}else{
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [INIT]: DHCP Server DEBUG: broadcast =  no match skip it\n";}
		}
		if($this->ntp_server<>null){
			$conf=$conf."\toption ntp-servers $this->ntp_server;\n";
			
		}
		
		
		
		if($GLOBALS["ASROOT"]){echo "Starting......: ".date("H:i:s")." [INIT]: DHCP Server server PXE enabled ? \"$this->pxe_enable\"\n";}
		if($this->pxe_enable==1){
			if($this->pxe_file<>null){
				$conf=$conf."\tfilename \"$this->pxe_file\";\n";
				if($this->pxe_server<>null){
					$conf=$conf."\tnext-server $this->pxe_server;\n";
				}
			}
		}
		if($GLOBALS["VERBOSE"]){echo "OnlySetGateway = $this->OnlySetGateway line ". __LINE__."\n";}
		if($this->OnlySetGateway==0){	
			 if($GLOBALS["VERBOSE"]){echo "DEBUG: OnlySetGateway is set to 0 , gateway_array() ->". count($gateway_array)." items  (OnlySetGateway 0 ) Line ".__LINE__."\n";}
			 if(is_array($gateway_array)){	
						reset($gateway_array);
						while (list ($index, $m_gw) = each ($gateway_array) ){
							if($m_gw==null){continue;}
							if($m_gw=="0.0.0.0"){continue;}
							$k[]=$m_gw;
						}
						if(count($k)>0){
							$conf=$conf."\toption routers ". implode(",",$k). ";\n";
						}
				}
		}else{
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [INIT]: DHCP Server OnlySetGateway = 1 adding only $this->gateway as gateway\n";}
			if(trim($this->gateway<>null)){
				$conf=$conf."\toption routers $this->gateway;\n";
			}	
		}
	
		$arrayNameServersB=array();
		$arrayNameServersA=$this->DomainNameServers();
		$arrayNameServersB=$arrayNameServersA;

		$ipClass=new IP();
		
		while (list ($index, $m_gw) = each ($arrayNameServersB) ){  if(!$ipClass->isValid($m_gw)){continue;} if($GLOBALS["VERBOSE"]){echo "NameServer: $m_gw<br>\n";} $BB[$m_gw]=$m_gw;  }
		while (list ($index, $m_gw) = each ($BB) ){ $arrayNameServers[]=$m_gw; }
		
		
		if(count($arrayNameServers)>0){
			if($GLOBALS["VERBOSE"]){echo "DEBUG: Buiding domain-name-servers ->option domain-name-servers ". implode(",",$arrayNameServers)."\n";}
			$conf=$conf ."\toption domain-name-servers ". implode(",",$arrayNameServers).";\n";
		}		
		
		if($this->ddns_domainname<>null){
			$conf=$conf."\toption domain-name \"$this->ddns_domainname\";\n";
		}		
		
		
		if($users->BIND9_INSTALLED){
			if($GLOBALS["VERBOSE"]){echo "DEBUG: ->Bind9 Installed -> Line ".__LINE__."\n";}
			if($this->ddns_domainname<>null){
			$conf=$conf."\tddns-rev-domainname \"in-addr.arpa\";\n\n";
			$conf=$conf."\tzone $this->ddns_domainname. {\n";
			$conf=$conf."\t\tprimary 127.0.0.1;\n";
			$conf=$conf."\t\tkey globalkey;\n";
			$conf=$conf."\t}\n";
			$conf=$conf."\n";
			if((is_numeric($re[3])) &&  (is_numeric($re[2])) && (is_numeric($re[1]))  ){
				$conf=$conf."\tzone {$re[3]}.{$re[2]}.{$re[1]}.in-addr.arpa. {\n";
				$conf=$conf."\t\tprimary 127.0.0.1;\n";
				$conf=$conf."\t\tkey globalkey;\n";
				$conf=$conf."\t}\n";	
			}
			
			$this->SetBind9Zone();
			}			
			
		}else{
			if($GLOBALS["VERBOSE"]){echo "DEBUG: ->Bind9 not Installed -> Line ".__LINE__."\n";}
			$conf=$conf."\tddns-updates off;\n"; 
			
		}
		$conf=$conf."}\n\n";
		
		
		$q=new mysql();
		$results=$q->QUERY_SQL("SELECT nic FROM dhcpd_nics WHERE EnableDHCPServer=1","artica_backup");
		if(!$q->ok){	$conf=$conf."# dhcpd_nics = $q->mysql_error\n";}
		$conf=$conf."# dhcpd_nics = ".mysql_num_rows($results)."\n";
		
		if(class_exists("unix")){
			$unix=new unix();
			$INTERFACES_AVAI=$unix->NETWORK_ALL_INTERFACES();
		}
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if($ligne["nic"]==$this->listen_nic){
				$conf=$conf."# dhcpd_nics: {$ligne["nic"]} = $this->listen_nic SKIP\n";
				continue;
			}
			
			if(is_array($INTERFACES_AVAI)){
				if(!isset($INTERFACES_AVAI[$ligne["nic"]])){
					$conf=$conf."# dhcpd_nics: {$ligne["nic"]} => NO INTERFACE\n";
					continue;
				}
			}
			$conf=$conf."# dhcpd_nics: {$ligne["nic"]} => Build\n";
			$xdhcpSub=new dhcpd_sub($ligne["nic"]);
			$conf=$conf.$xdhcpSub->build()."\n\n";
				
		}
		
		
		if($GLOBALS["VERBOSE"]){echo "DEBUG: ->SetFixedAddresses() -> Line ".__LINE__."\n";}
		$conf=$conf.$this->SetFixedAddresses();
		$conf=$conf.$this->shared_networks();
		
		
		if($GLOBALS["VERBOSE"]){echo "DEBUG: ->END ->". strlen($conf)." bytes Line ".__LINE__."\n";}
		$conf=$conf .@file_get_contents("/var/lib/dhcp3/dhcpd.other")."\n";
		return $conf;
		
	}
	
	
	function LoadfixedAddresses(){
		if(isset($GLOBALS[__CLASS__][__FUNCTION__])){return $GLOBALS[__CLASS__][__FUNCTION__];}
		if($this->noloadFixed==1){return;}
		$ARR=array();
		$sql="SELECT * FROM dhcpd_fixed";
		$q=new mysql();
		$results = $q->QUERY_SQL($sql,"artica_backup");
		while ($ligne = mysql_fetch_assoc($results)) {
			
			$ComputerMacAddress=$ligne["mac"];
			$hostname=$ligne["hostname"];
			$ip_addr=$ligne["ipaddr"];
			$DnsZoneName=$ligne["domain"];
			if(trim($DnsZoneName)==null){$DnsZoneName=$this->ddns_domainname;}
			if($ip_addr=="127.0.0.1"){continue;}
			if($ip_addr=="0.0.0.0"){continue;}
			if($GLOBALS["VERBOSE"]){echo "$ComputerMacAddress `$hostname` -> $ip_addr\n";}
	
			
			
			$ARR[$ComputerMacAddress]=array(
			"MAC"=>"hardware ethernet $ComputerMacAddress",
			"IP"=>"fixed-address $ip_addr",
			"hostname"=>$hostname,
			"domainname"=>$DnsZoneName,
			"routers"=>trim($ligne["routers"]),
			"time-servers"=>trim($ligne["time-servers"]),
			"domain-name-servers"=>trim($ligne["domain-name-servers"]),
			"domain-name-servers-2"=>trim($ligne["domain-name-servers-2"]),
			"ntp-servers"=>trim($ligne["ntp-servers"]),
			 "local-pac-server"=>trim($ligne["local-pac-server"]),
			);

			
		}
		
	
			

	$GLOBALS[__CLASS__][__FUNCTION__]=$ARR;
	return $ARR;
	
	}
	
	public function  IsPhysicalAddress($address){
		$address=strtoupper(trim($address));
		if($address=="UNKNOWN"){return null;}
		
		if(function_exists("debug_backtrace")){
					try {
						$trace=@debug_backtrace();
						if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
					} catch (Exception $e) {writelogs("IsPhysicalAddress:: Fatal: ".$e->getMessage(),__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				}			
		
		$address=str_replace(":","-",$address);
		If(strlen($address) > 18){
			writelogs("`$address` too many caracters, exceed 18 receive ".strlen($address)." $called",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
		If($address == ""){
			writelogs("$address is null !",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
		If(!preg_match("#^[0-9A-Z]+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)$#i",$address)){
			writelogs("$address did not match pattern $called",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
		$Array=explode("-",$address);
		If(strlen($Array[0]) != 2){
			writelogs("$address: array 0 did not match 2 $called",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
		If(strlen($Array[1]) != 2){
			writelogs("$address:array 1 did not match 2 $called",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
		If(strlen($Array[2]) != 2){
			writelogs("$address:array 2 did not match 2 $called",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
		If(strlen($Array[3]) != 2){
			writelogs("$address:array 3 did not match 2 $called",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
		If(strlen($Array[4]) != 2){
			writelogs("$address:array 4 did not match 2 $called",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
		If(strlen($Array[5]) != 2){
			writelogs("$address:array 5 did not match 2 $called",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
		
		return true;
	}	
	
	
	private function SetFixedAddresses(){
		$sock=new sockets();
		$conf=null;
		$EnableDHCPUseHostnameOnFixed=$sock->GET_INFO("EnableDHCPUseHostnameOnFixed");
		$IncludeDHCPLdapDatabase=Field_checkbox("IncludeDHCPLdapDatabase",1,$IncludeDHCPLdapDatabase);
		if(!is_numeric($EnableDHCPUseHostnameOnFixed)){$EnableDHCPUseHostnameOnFixed=0;}
		if(!is_numeric($IncludeDHCPLdapDatabase)){$IncludeDHCPLdapDatabase=1;}
		if($IncludeDHCPLdapDatabase==0){return;}
		$array=$this->LoadfixedAddresses();
		$ipClass=new IP();
		if(!is_array($array)){return null;}
		while (list ($num, $ligne) = each ($array) ){
				if($num==null){continue;}
				$hostname=$ligne["hostname"];
				$hostname=trim($hostname);$hostname=str_replace(" ", "", $hostname);
				$ligne["MAC"]=trim($ligne["MAC"]);
				if(isset($GLOBALS["HOSTNAME_ALREADY_SET"][$hostname])){continue;}
				if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", trim($num))){if($GLOBALS["VERBOSE"]){echo "Skip $num , match ^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$\n";}continue;}
				$IP=$ligne["IP"];
				$IP=trim(str_replace("fixed-address","",$IP));
				
				if($EnableDHCPUseHostnameOnFixed==1){
					$hostnameRes=gethostbyaddr($IP);
					if($GLOBALS["VERBOSE"]){echo "Resolved $IP = $hostname -> $hostnameRes\n";}
					if($hostnameRes<>$IP){
						$ligne["IP"]="fixed-address $hostnameRes";
					}
				}
				$domainname=$ligne["domainname"];
				if(preg_match("#localhost#",$domainname)){$domainname=null;}
				
				
				if($GLOBALS["VERBOSE"]){echo "SetFixedAddresses();$hostname -> {$ligne["MAC"]}\n";}

				$conf=$conf ."\thost $hostname{\n";
				$conf=$conf ."\t\t{$ligne["MAC"]};\n";
				$conf=$conf ."\t\t{$ligne["IP"]};\n";
				if(!preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $hostname)){
					$conf=$conf ."\t\toption host-name \"$hostname\";\n";
				}
				if($domainname<>null){$conf=$conf ."\t\toption domain-name \"$domainname\";\n";}
				
				if(!$ipClass->isIPv4($ligne["routers"])){$ligne["routers"]=null;}
				if(!$ipClass->isIPv4($ligne["domain-name-servers"])){$ligne["domain-name-servers"]=null;}
				if(!$ipClass->isIPv4($ligne["domain-name-servers-2"])){$ligne["domain-name-servers-2"]=null;}
				
				if(trim($ligne["routers"]<>null)){
					$conf=$conf ."\t\toption routers {$ligne["routers"]};\n";
				}
				if($ligne["time-servers"]<>null){
					$conf=$conf ."\t\toption time-servers {$ligne["time-servers"]};\n";
				}	

				if($ligne["domain-name-servers"]<>null){
					$conf=$conf ."\t\toption domain-name-servers {$ligne["domain-name-servers"]};\n";
				}

				if($ligne["ntp-servers"]<>null){
					$conf=$conf ."\t\toption ntp-servers {$ligne["ntp-servers"]};\n";
				}	
							
				if($GLOBALS["VERBOSE"]){echo "Add fixed address for $hostname {$ligne["MAC"]} = {$ligne["IP"]}\n";}
				$conf=$conf ."\t}\n";
				$GLOBALS["HOSTNAME_ALREADY_SET"][$hostname]=true;
		
			}
	
		return $conf;
		
	}
	
	
	function SetBind9Zone(){
	
		$bind=new Bind9Zone($this->ddns_domainname);
		$bind->SaveBind9Zone($this->range1);

	}
	
	
	function GetKey($domain){
		$sock=new sockets();
		$md=md5($domain);
		$datas=$sock->GET_INFO("Bind9DnsKey.$md.key");
		if($datas==null){
			$key=$sock->getfile("Bind9Key:$domain");
			$conf=$conf."[KEY]\n";
			$conf=$conf."";
		}
		
		
	}
	
	private function RemoteVPN(){
			if(isset($GLOBALS["DHCPD_RemoteVPN"])){return;}
			$GLOBALS["DHCPD_RemoteVPN"]=true;
			
			if($this->listen_nic==null){$this->GetDefaultNic();}
			$ip=new networking();
			$array=$ip->array_TCP;
			
			$gateway=$array[$this->listen_nic];
			$sql="SELECT IP_START,netmask,sitename  FROM vpnclient WHERE connexion_type=1 ORDER BY sitename DESC";
			$q=new mysql();
			$results=$q->QUERY_SQL($sql,"artica_backup");
			while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
				writelogs("find route on vpn=>{$ligne["IP_START"]}=>{$ligne["netmask"]}=>$gateway",__CLASS__.'/'.__FUNCTION__,__FILE__);
				$this->AddRoute($ligne["IP_START"],$ligne["netmask"],$gateway,"VPN ".$ligne["sitename"]);
			}
	}

	
	
	private function shared_networks(){
		$q=new mysql();
		$sql="SELECT sharednet_name FROM dhcpd_sharednets GROUP BY sharednet_name ORDER BY sharednet_name";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(mysql_num_rows($results)==0){return null;}
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$content=$this->shared_networks_sub($ligne["sharednet_name"]);
			if($content==null){continue;}
			$ligne["sharednet_name"]=trim($ligne["sharednet_name"]);
			if($ligne["sharednet_name"]==null){continue;}
			$ligne["sharednet_name"]=str_replace(" ", "-", $ligne["sharednet_name"]);
			$f[]="shared-network {$ligne["sharednet_name"]} {";
			$f[]=$content;
			$f[]="}\n";
		}
		
		return @implode("\n", $f);		
	}
	
	
	private function shared_networks_sub($mainet){
		if($GLOBALS["VERBOSE"]){echo "->shared_networks_sub() -> $mainet item Line ".__LINE__."\n";}
		$q=new mysql();
		$spacer="    ";
		$sql="SELECT * FROM dhcpd_sharednets WHERE sharednet_name='$mainet'";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(mysql_num_rows($results)==0){return null;}
		$TcpClass=new IP();
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$dns=array();
			while (list ($a, $b) = each ($ligne) ){$ligne[$a]=trim($b);}
			
			
			$subnet=$ligne["subnet"];
			$netmask=$ligne["subnet"];
			$range1=$ligne["range1"];
			$range2=$ligne["range2"];
			
			if(!$TcpClass->isIPAddress($subnet)){$subnet=null;}
			if(!$TcpClass->isIPAddress($netmask)){$netmask=null;}
			if(!$TcpClass->isIPAddress($range1)){$range1=null;}
			if(!$TcpClass->isIPAddress($range2)){$range2=null;}
			if(!$TcpClass->isIPAddress($ligne["domain-name-servers1"])){$ligne["domain-name-servers1"]=null;}
			if(!$TcpClass->isIPAddress($ligne["domain-name-servers2"])){$ligne["domain-name-servers2"]=null;}
			if(!$TcpClass->isIPAddress($ligne["wins-server"])){$ligne["wins-server"]=null;}
			if(!$TcpClass->isIPAddress($ligne["routers"])){$ligne["routers"]=null;}
			if(!$TcpClass->isIPAddress($ligne["routers"])){$ligne["routers"]=null;}
			
			if($subnet==null){continue;}
			if($netmask==null){continue;}
			if($range1==null){continue;}
			if($range2==null){continue;}	
			
			
			
			if(trim($ligne["domain-name-servers1"])<>null){$dns[]=trim($ligne["domain-name-servers1"]);}
			if(trim($ligne["domain-name-servers2"])<>null){$dns[]=trim($ligne["domain-name-servers2"]);}
			
			$f[]="#Scope \"{$ligne["scopename"]}\"";
			if($GLOBALS["VERBOSE"]){echo "->shared_networks_sub() -> [{$ligne["scopename"]}] subnet $subnet netmask $netmask Line ".__LINE__."\n";}
			$f[]="{$spacer}subnet $subnet netmask $netmask {";
			$f[]="{$spacer}{$spacer}range $range1 $range2;";
			if(trim($ligne["subnet-mask"])<>null){$f[]="{$spacer}{$spacer}option subnet-mask {$ligne["subnet-mask"]};";}
			if(trim($ligne["domain-name"])<>null){$f[]="{$spacer}{$spacer}option domain-name \"{$ligne["domain-name"]}\";";}
			if(trim($ligne["routers"])<>null){$f[]="{$spacer}{$spacer}option routers {$ligne["routers"]};";}
			
			if(trim($ligne["option-176"])<>null){
				$ligne["option-176"]=str_replace("\n", "", $ligne["option-176"]);
				$f[]="{$spacer}{$spacer}option option-176 \"{$ligne["option-176"]}\";";
			}
			if(count($dns)>0){$f[]="{$spacer}{$spacer}option domain-name-servers ". @implode(", ", $dns).";";}
			if(trim($ligne["wins-server"])<>null){$f[]="{$spacer}{$spacer}option netbios-name-servers {$ligne["wins-server"]};";}
			if(trim($ligne["tftp-server-name"])<>null){$f[]="{$spacer}{$spacer}option tftp-server-name \"{$ligne["tftp-server-name"]}\";";}
			if(trim($ligne["server-name"])<>null){$f[]="{$spacer}{$spacer}option server-name \"{$ligne["server-name"]}\";";}
			if(trim($ligne["next-server"])<>null){$f[]="{$spacer}{$spacer}option next-server {$ligne["next-server"]};";}
			if(trim($ligne["pxe_filename"])<>null){$f[]="{$spacer}{$spacer}option filename \"{$ligne["pxe_filename"]}\";";}
			if(trim($ligne["pxe_root-path"])<>null){$f[]="{$spacer}{$spacer}option root-path \"{$ligne["pxe_root-path"]}\";";}
			
			$f[]="{$spacer}}\n";
			}	
		
		return @implode("\n",$f);
	}
	
	
	
}

?>