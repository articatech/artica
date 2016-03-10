<?php
include_once(dirname(__FILE__)."/class.products-ip-ranges.inc");
include_once(dirname(__FILE__)."/class.firehole.rules.inc");

class firehol{
	public $services_array;
	private $services_available=array();
	private $InterfaceSet=array();
	private $ip_forward=0;
	private $rp_filter=1;
	private $ipset_installed=false;
	private $BUILD_INTERFACE_SERVER_LOGS=array();
	private $EnableDHCPServer=0;
	private $DHCPNic=null;
	
	public function firehol(){
		$this->fillServices();
		$this->isipset_installed();
		$this->LoadLocalDHCP();
		
	}
	
	private function LoadLocalDHCP(){
		$this->EnableDHCPServer=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableDHCPServer"));
		$EnableArticaAsGateway=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaAsGateway"));
		if($this->EnableDHCPServer==0){return;}
		include_once(dirname(__FILE__)."/class.dhcpd.inc");
		$dhcp=new dhcpd();
		$this->DHCPNic=$dhcp->listen_nic;
		if($EnableArticaAsGateway==1){$this->ip_forward=1;}
		
	}
	
	
	public function isipset_installed(){
		if(!class_exists("unix")){return;}
		$unix=new unix();
		$ipset=$unix->find_program("ipset");
		if(is_file($ipset)){$this->ipset_installed=true;}
		
	}
	
	public function check_xt_ndpi(){
		$unix=new unix();
		$KERNEL_VERSION=$unix->KERNEL_VERSION();
		$LOG_PREFIX="Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]}";
		if(!is_file("/lib/modules/$KERNEL_VERSION/extra/xt_ndpi.ko")){return;}
		$modinfo=$unix->find_program("modinfo");
		$depmod=$unix->find_program("depmod");
		exec("$modinfo xt_ndpi 2>&1",$results);
		
		while (list ($index, $line) = each ($results)){
			if(preg_match("#Error.*?missing#", $line)){
				echo "$LOG_PREFIX xt_ndpi: Please wait, running depmod\n";
				system("$depmod -a");
				return;
			}
			
			if(preg_match("#description:\s+(.+)?#", $line,$re)){
				echo "$LOG_PREFIX xt_ndpi: {$re[1]} OK\n";
				return;
			}
			
		}
		
		
		
		
	}

	
	
	public function build(){
			$this->CheckDirs();
			$sock=new sockets();
			$unix=new unix();
			$FireHolRouter=intval($sock->GET_INFO("FireHolRouter"));
			$KERNEL_VERSION=$unix->KERNEL_VERSION();
			$Interfaces=$unix->NETWORK_ALL_INTERFACES();
			$this->BUILD_SERVICES();
			$this->ipset_bumbsslwl();
			$this->IPSET_ALL_IPS_LIST();
			$MODE_ROUTER=$this->MODE_ROUTER();
			$FireHolConf=unserialize(base64_decode($sock->GET_INFO("FireHolConf")));
			$sysctl=$unix->find_program("sysctl");
			$CONF[]="# DHCP: $this->EnableDHCPServer ($this->DHCPNic)";
			if(!$this->ipset_compatible()){
				$CONF[]="#ipset v.".$this->ipset_version()." incompatible turn to old way";
				$CONF[]="IPSET_SRC_DST_OPTIONS=";
			}else{
				$CONF[]="#ipset v.".$this->ipset_version()." compatible";
			}
			$CONF[]="version 5";
			$CONF[]="#Trusted Networks";
			
			if(is_file("/lib/modules/$KERNEL_VERSION/extra/xt_ndpi.ko")){
				$CONF[]="#xt_ndpi installed";
				$this->check_xt_ndpi();
			}else{
				$CONF[]="#xt_ndpi not installed";
			}
			
			$CONF[]="FIREHOL_AUTOSAVE=\"/home/artica/firewall/firehol-saved-ipv4.txt\"";
			$CONF[]="FIREHOL_LOG_PREFIX=\"FIREHOL:\"";
			$CONF[]="FIREHOL_TPROXY_MARK=\"0xffff\"";
			$CONF[]="FIREHOL_TPROXY_IP_ROUTE_TABLE=\"999\"";
			$CONF[]="FIREHOL_TPROXY_ROUTE_DEVICE=\"eth0\"";
			$CONF[]="MDPI=\"-m  ndpi \"";
			 
			
		
			if($this->ipset_installed){
				$CONF[]="ipv4 ipset create proxy_white_ssl hash:ip";
				$CONF[]="ipv4 ipset addfile proxy_white_ssl ips proxy_ssl_whitelist";
				$CONF[]="ipv4 ipset create MyIPs hash:ip";
				$CONF[]="ipv4 ipset addfile MyIPs ips MyIPs";
			}
			
			$CONF[]="";
			
			$CONF[]="# * * * * Transparent Proxy * * * *";
			$CONF[]=$this->transparent_squid($FireHolConf["IF_LAN"]);
			$CONF[]=$this->transparent_proxy();
			$CONF[]=$this->transparent_socks();
			$CONF[]=$this->transparent_ftp();
			$CONF[]="# * * * * ip_forward =  $this->ip_forward * * * *";
			$CONF[]="# * * * * rp_filter  =  $this->rp_filter * * * *";
			$unix->sysctl("net.ipv4.conf.default.rp_filter",$this->rp_filter);
			$unix->sysctl("net.ipv4.conf.all.rp_filter",$this->rp_filter);
			while (list ($Interface, $dr) = each ($Interfaces)){
				$unix->sysctl("net.ipv4.conf.$Interface.rp_filter",$this->rp_filter);
				$CONF[]="$sysctl -w net.ipv4.conf.$Interface.rp_filter=$this->rp_filter";
			}
			$CONF[]="$sysctl -w net.ipv4.conf.default.rp_filter=$this->rp_filter >/dev/null 2>&1";
			$CONF[]="$sysctl -w net.ipv4.conf.all.rp_filter=$this->rp_filter >/dev/null 2>&1";
			$unix->sysctl("net.ipv4.ip_forward", $this->ip_forward);
			$CONF[]="$sysctl -w net.ipv4.ip_forward=$this->ip_forward >/dev/null 2>&1";
			$CONF[]="";
			$CONF[]="# * * * * NAT Rules * * * *";
			$CONF[]=$this->BUILD_NAT();
			
			
			$results=$this->MODE_SINGLE();
			if(!$results){
				if($GLOBALS["VERBOSE"]){echo " **** MODE_SINGLE FAILED ! ****\n";}
				@unlink("/etc/firehol/firehol.conf");
				return false; 
			}
			$CONF[]=$results;
			$CONF[]=$MODE_ROUTER;
			$CONF[]=$this->BUILD_ROUTERS_INFACE_ITSELF();
			$CONF[]="";
			
			
			
			@file_put_contents("/etc/firehol/firehol.conf", @implode("\n", $CONF));
			
	}
	
	private function ipset_compatible(){
		$ipset_version=$this->ipset_version();
		$V=explode(".",$ipset_version);
		$major=$V[0];
		$minor=$V[1];
		if($major>0){
			if($minor>=19){return true;}
		}
		return false;
	}

	private function MODE_SINGLE(){
		$BUILD_INTERFACES=$this->BUILD_INTERFACES();
		if(!$BUILD_INTERFACES){return false;}
		$CONF[]=$BUILD_INTERFACES;
		return @implode("\n", $CONF);
		
		
	}
	
	private function BUILD_NAT_DEST($ipaddr,$port=null){
		$port=intval($port);
		$IP=new IP();
		if(!$IP->isIPAddress($ipaddr)){$ipaddr=gethostbyaddr($ipaddr);}
		if($port==0){return $ipaddr;}
		return "$ipaddr:$port";
		
	}
	
	private function ipset_version(){
		$unix=new unix();
		$ipset=$unix->find_program("ipset");
		if(!is_file($ipset)){$GLOBALS["ipset_version"]=0;return $GLOBALS["ipset_version"];}
		$results=exec("$ipset -v 2>&1");
		if(preg_match("#v([0-9]+)\.([0-9]+)#", $results,$re)){
			$GLOBALS["ipset_version"]="{$re[1]}.{$re[2]}";
			return $GLOBALS["ipset_version"];
		}
	}
	
	private function BUILD_NAT(){
		$q=new mysql();
		$sql="SELECT * FROM `pnic_nat`  WHERE enabled=1";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){return null;}
		$CONF=array();
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			
			$NAT_TYPE=$ligne["NAT_TYPE"];
			$nic=trim($ligne["nic"]);
			$srcaddr=trim($ligne["srcaddr"]);
			$dport=intval($ligne["dstport"]);
			$dstaddrTarget=$ligne["dstaddrTarget"];
			$proto=$ligne["proto"];
			if($dstaddrTarget==null){$dstaddrTarget="0.0.0.0/0";}
			if($proto==null){$proto="tcp";}
			$dport_r=null;
			$dstaddrTarget_r=null;
			
			$CONF[]="# Rule {$ligne["ID"]} [eth:{$nic}] $dstaddrTarget:{$ligne["dstport"]} -> {$ligne["dstaddr"]}:{$ligne["dstaddrport"]}";
			
			
			
			$destination=$this->BUILD_NAT_DEST($ligne["dstaddr"],$ligne["dstaddrport"]);
			if($NAT_TYPE==0){
				if($srcaddr<>null){$srcaddr=" src $srcaddr ";}
				if($nic<>null){$nic=" inface $nic ";}
				if($dport>0){$dport_r=" dport $dport";}
				if($dstaddrTarget<>"0.0.0.0/0"){
					$dstaddrTarget_r=" dst $dstaddrTarget";
				}
				$CONF[]="nat to-destination $destination{$nic}{$srcaddr} proto {$proto}{$dport_r}{$dstaddrTarget_r}";
				continue;
			}
			
		}
		
		return @implode("\n", $CONF);
		
		
	}
	
	
	private function BUILD_INTERFACES(){
		$unix=new unix();
		$Interfaces=$unix->NETWORK_ALL_INTERFACES();
		
		//$BEHA2[0]="{not_defined}";
		//$BEHA2[1]="{act_as_lan}";
		//firewall_behavior[2]= act_as_wan
		
		$ipdeny=$this->ipdeny();
		while (list ($Interface, $dr) = each ($Interfaces)){
			
			$NicName="NET{$Interface}";
			$nic=new system_nic($Interface);
			if($nic->netzone<>null){$NicName=$nic->netzone;}
			if($nic->firewall_policy==null){$nic->firewall_policy="accept";}
			$BUILD_INTERFACE_SERVER=$this->BUILD_INTERFACE_SERVER($Interface,$nic->firewall_behavior);
			$BUILD_INTERFACE_CLIENT=$this->BUILD_INTERFACE_CLIENT($Interface);
			
			
			$NicName=str_replace(" ", "", $NicName);
			$NicName=str_replace("-", "", $NicName);
			$NicName=str_replace(".", "", $NicName);
			$NicName=str_replace("(", "", $NicName);
			$NicName=str_replace(")", "", $NicName);
			$NicName=str_replace("[", "", $NicName);
			$NicName=str_replace("]", "", $NicName);
						
			$this->InterfaceSet[$Interface]=true;
			$CONF[]="#$Interface Name:$nic->NICNAME/$nic->netzone/$NicName $nic->IPADDR [".__LINE__."]";
			$CONF[]=@implode("\n", $this->BUILD_INTERFACE_SERVER_LOGS);
			$CONF[]="interface4 $Interface $NicName "; 
			$CONF[]="\tpolicy $nic->firewall_policy";
			if($nic->firewall_behavior==2){
				$CONF[]="\tprotection strong 10/sec 10";
			}
			
			if($nic->firewall_artica==1){
				$CONF[]="\tserver artica accept";
			}
			
			if($this->EnableDHCPServer==1){
				if($this->DHCPNic==$Interface){
					$CONF[]="\tserver dhcp accept";
				}
			}
			
			$PersonalRules=new firehol_rules($Interface);
			$CONF[]=$PersonalRules->buildrules();
			if($GLOBALS["VERBOSE"]){
				$CONF[]=@implode("\n", $PersonalRules->logs);
			}			
			if($nic->DenyCountries==1){
				if(count($ipdeny["SERVERS"])>0){
					$CONF[]=@implode("\n", $ipdeny["SERVERS"]);
				}
			}
			
			
			if($nic->firewall_behavior==1){
				$CONF[]="client any accept";
				$CONF[]="server any accept";
				
			}
			
			if($nic->firewall_behavior==0){
				$CONF[]="client any accept";
				$CONF[]=$BUILD_INTERFACE_SERVER;
				
			}
			
			if($nic->firewall_behavior==2){
				$CONF[]=$BUILD_INTERFACE_SERVER;
				if($BUILD_INTERFACE_CLIENT==null){$CONF[]="\tclient any accept";}else{$CONF[]=$BUILD_INTERFACE_CLIENT;}
			}else{
				$CONF[]=$BUILD_INTERFACE_SERVER;
				if($BUILD_INTERFACE_CLIENT==null){$CONF[]="\tclient any accept";}else{$CONF[]=$BUILD_INTERFACE_CLIENT;}
			}
			
			
			
			$CONF[]="";

		}
		
		return @implode("\n", $CONF)."\n";
		
	}
	
	
	private function BUILD_ROUTERS_INFACE_ITSELF(){
		$unix=new unix();
		
		$masquerade[0]="";
		$masquerade[1]="masquerade";
		$masquerade[2]="masquerade reverse";
		
		$CONF[]="";
		$CONF[]="router4 lo2lo inface lo outface lo";
		$CONF[]="\troute any accept";
		$CONF[]="\tclient any accept";
		$CONF[]="\tpolicy accept";
		$CONF[]="";
		$EnableSecureGateway=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableSecureGateway"));
		$policy="accept";
		$route="\troute any accept";
		$ipdeny=$this->ipdeny();
		$secure_gateway=null;
		if($EnableSecureGateway==1){
			$policy="reject";
			$secure_gateway=$this->secure_gateway();
			$route="$secure_gateway\n\troute any deny";
		}
		
		
		
		
		$Interfaces=$unix->NETWORK_ALL_INTERFACES();
		while (list ($Interface, $dr) = each ($Interfaces)){
			if($Interface=="lo"){continue;}
			if(!isset($this->InterfaceSet["{$Interface}2{$Interface}"])){
				$nic=new system_nic($Interface);
				$CONF[]="";
				$CONF[]="router4 {$Interface}2{$Interface} inface $Interface outface $Interface";
				
				if($nic->firewall_artica==1){
					$CONF[]="\tserver artica accept";
				}
				
				if($this->EnableDHCPServer==1){
					if($this->DHCPNic==$Interface){
						$CONF[]="\tserver dhcp accept";
					}else{
						$CONF[]="#\tDHCP $this->DHCPNic !=$Interface";
					}
				}else{
					$CONF[]="#\tDHCP disabled";
				}
				
				
				$PersonalRules=new firehol_rules("{$Interface}2{$Interface}",1);
				$CONF[]=$PersonalRules->buildrules();
				
				if($nic->DenyCountries==1){
					$CONF[]="#\t$Interface DenyCountries=1";
					if(count($ipdeny["ROUTERS"])>0){$CONF[]=@implode("\n", $ipdeny["ROUTERS"]);}
					
				}
				
				
				$CONF[]=$route;
				$CONF[]="\tclient any accept";
				$CONF[]="\tpolicy $policy";
				if($nic->firewall_masquerade>0){
					$CONF[]="\t{$masquerade[$nic->firewall_masquerade]}";
				}
				$CONF[]="";
				$this->InterfaceSet["{$Interface}2{$Interface}"]=true;
			}
		
		}
		return @implode("\n", $CONF)."\n";
	}
	
	private function BUILD_INTERFACE_SERVER($Interface,$act_as_lan){
		$this->BUILD_INTERFACE_SERVER_LOGS=array();
		$nic=new system_nic($Interface);
		
		$q=new mysql();
		
		if($act_as_lan==1){
			return "\tserver any accept";
		}
		
		if($nic->firewall_artica==1){
			$CONF[]="\tserver artica accept";
		}
		
		if($this->EnableDHCPServer==1){
			if($this->DHCPNic==$Interface){
				$MAIN["dhcp"]="\tserver dhcp accept";
			}
			
		}
		
		$allow_type[0]="reject";
		$allow_type[1]="accept";
		$CONF=array();
		$MAIN=array();
		if($GLOBALS["VERBOSE"]){echo "BUILD_INTERFACE_SERVER($Interface)\n";}
		//1 = Allow, 0 = Deny
		$sql="SELECT `service`,`allow_type`,`interface` FROM `firehol_services` WHERE `enabled`=1 AND `interface`='$Interface'";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){
			if($GLOBALS["VERBOSE"]){echo " **** Interface $Interface $q->mysql_error ".__FUNCTION__."/".__LINE__." ***\n";}
			return false;}
		if($GLOBALS["VERBOSE"]){echo "BUILD_INTERFACE_SERVER($Interface) ->". mysql_num_rows($results)."\n";}
		
		$this->BUILD_INTERFACE_SERVER_LOGS[]="# $Interface: act_as_lan = $act_as_lan";
		
		
		$unifi=$this->unifi_services($Interface);
		if($act_as_lan<>2){
			if($unifi<>null){$MAIN["unifi"]="\tserver unifi accept";}
			if($this->isSquid()){$MAIN["squidports"]="\tserver squidports accept";}
		}
		
		$nginx=$this->nginx_service();
		if($nginx<>null){
			$MAIN["nginx"]="\tserver nginx accept";
		}
		

		$this->BUILD_INTERFACE_SERVER_LOGS[]="# $Interface: ".mysql_num_rows($results)." services available";
		
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			if(!is_file("/etc/firehol/services/{$ligne["service"]}.conf")){
				$this->BUILD_INTERFACE_SERVER_LOGS[]="# {$ligne["service"]} /etc/firehol/services/{$ligne["service"]}.conf not available";
				continue;
			}
			$MAIN[$ligne["service"]]="\tserver {$ligne["service"]} {$allow_type[$ligne["allow_type"]]}";
		}
		

		
		
		while (list ($service, $dr) = each ($MAIN)){$CONF[]=$dr;}
		$this->BUILD_INTERFACE_SERVER_LOGS[]="# $Interface: final services = ".count($CONF);
		
		if(count($CONF)==0){
			if($act_as_lan==0){
				return "\tserver any accept";
			}
		}
		
		if(count($CONF)>0){
			return @implode("\n", $CONF);
		}
		
	}
	
	private function squid_ports_include($include,$portid,$META_PORT){
		
		$q=new mysql_squid_builder();		
		$sql="SELECT pattern  FROM `proxy_ports_wbl` WHERE include=$include AND portid=$portid";
		$results = $q->QUERY_SQL($sql);
		$Meta=$this->transparent_squid_meta($include,$META_PORT);
		if(is_array($Meta)){$f=$Meta;}
		if(mysql_num_rows($results)>0){
			while ($ligne = mysql_fetch_assoc($results)) {
				$f[]=$ligne["pattern"];
			}
		}
		
		if(count($f)==0){return null;}
		
		if($include==1){
			return " dst not \"".@implode(" ", $f)."\" ";
		}
		return " src not \"".@implode(" ", $f)."\" ";
	}
	
	private function BUILD_INTERFACE_CLIENT($Interface){
		$q=new mysql();
		$CONF=array();
		$allow_type[0]="reject";
		$allow_type[1]="accept";
		$sock=new sockets();
		

		
	
		if($GLOBALS["VERBOSE"]){echo "BUILD_INTERFACE_SERVER($Interface)\n";}
		//1 = Allow, 0 = Deny
		$sql="SELECT `service`,`allow_type`,`interface` FROM `firehol_client_services` WHERE `enabled`=1 AND `interface`='$Interface'";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){
			if($GLOBALS["VERBOSE"]){echo " **** Interface $Interface $q->mysql_error ".__FUNCTION__."/".__LINE__." ***\n";}
			return false;
		}
		if($GLOBALS["VERBOSE"]){echo "BUILD_INTERFACE_SERVER($Interface) ->". mysql_num_rows($results)."\n";}
	
		$CountOfRows=mysql_num_rows($results);
		$CONF[]="#\t$Interface: $CountOfRows clients rule(s) ( accept all services )[".__LINE__."]";
		if($CountOfRows==0){
			$CONF[]="\tclient any accept";
			return @implode("\n", $CONF);
		}
		
		
	
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			if(!isset($this->services_available[$ligne["service"]])){continue;}
			$CONF[]="\tclient {$ligne["service"]} {$allow_type[$ligne["allow_type"]]}";
		}
		if($GLOBALS["VERBOSE"]){echo " **** Interface $Interface ".count($CONF)." rows ".__FUNCTION__."/".__LINE__." ***\n";}
		if(count($CONF)>0){
			return @implode("\n", $CONF);
		}
		$CONF[]="\tclient any accept";
		return @implode("\n", $CONF);
		
	
	}	
			
			
	private function MODE_ROUTER(){		
		$sock=new sockets();
		$q=new mysql();
		$ipdeny=$this->ipdeny();
		$q=new mysql();
		$FireHolConf=unserialize(base64_decode($sock->GET_INFO("FireHolConf")));
		if(!isset($FireHolConf["IF_LAN"])){$FireHolConf["IF_LAN"]=null;}
		if(!isset($FireHolConf["IF_WAN"])){$FireHolConf["IF_WAN"]=null;}
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ID FROM pnic_bridges WHERE nic_from='{$FireHolConf["IF_LAN"]}' AND nic_to='{$FireHolConf["IF_WAN"]}'","artica_backup"));
		if(intval($ligne)>0){$FireHolConf["IF_LAN"]=null;$FireHolConf["IF_WAN"]=null;}
			
			
			
			if($FireHolConf["IF_LAN"]<>null && $FireHolConf["IF_WAN"]<>null){
				
				if(!isset($this->InterfaceSet[$FireHolConf["IF_LAN"]])){
					$CONF[]="interface4 {$FireHolConf["IF_LAN"]} LAN";
					$CONF[]="\tpolicy accept";
					$PersonalRules=new firehol_rules("{$FireHolConf["IF_LAN"]}",0);
					$CONF[]=$PersonalRules->buildrules();
					$CONF[]="\tclient any accept";
					$CONF[]="";
				}
				
				if(!isset($this->InterfaceSet[$FireHolConf["IF_WAN"]])){
					$CONF[]="# Wizard mode.";
					$nic=new system_nic($FireHolConf["IF_WAN"]);
					$CONF[]="interface4 {$FireHolConf["IF_WAN"]} WAN src not \"\${UNROUTABLE_IPS}\"";					
					if($nic->firewall_artica==1){ $CONF[]="\tserver artica accept"; }
					$PersonalRules=new firehol_rules("{$FireHolConf["IF_WAN"]}",0);
					$CONF[]=$PersonalRules->buildrules();
					$CONF[]="\tprotection strong 10/sec 10";
					$CONF[]="\tclient any accept";
				}
				
				if(!isset($this->InterfaceSet["{$FireHolConf["IF_LAN"]}2{$FireHolConf["IF_WAN"]}"])){
					$CONF[]="# Lan2Wan - Wizard mode.";
					$CONF[]="router Lan2Wan inface {$FireHolConf["IF_LAN"]} outface {$FireHolConf["IF_WAN"]}";
					$CONF[]="\tpolicy accept";
					$PersonalRules=new firehol_rules("{$FireHolConf["IF_LAN"]}2{$FireHolConf["IF_WAN"]}",1);
					$CONF[]=$PersonalRules->buildrules();
					$CONF[]="\tclient any accept";
					$CONF[]="";
					$this->InterfaceSet["{$FireHolConf["IF_LAN"]}2{$FireHolConf["IF_LAN"]}"]=true;
				}				
			
				if(!isset($this->InterfaceSet["{$FireHolConf["IF_LAN"]}2{$FireHolConf["IF_LAN"]}"])){
					$CONF[]="# Lan2Lan - Wizard mode.";
					$CONF[]="router Lan2Lan inface {$FireHolConf["IF_LAN"]} outface {$FireHolConf["IF_LAN"]}";
					$CONF[]="\tpolicy accept";
					$PersonalRules=new firehol_rules("{$FireHolConf["IF_LAN"]}2{$FireHolConf["IF_LAN"]}",1);
					$CONF[]=$PersonalRules->buildrules();
					$CONF[]="\tclient any accept";
					$CONF[]="";
					$this->InterfaceSet["{$FireHolConf["IF_LAN"]}2{$FireHolConf["IF_LAN"]}"]=true;
				}
			
				if(!isset($this->InterfaceSet["{$FireHolConf["IF_WAN"]}2{$FireHolConf["IF_LAN"]}"])){
					$nic=new system_nic($FireHolConf["IF_WAN"]);
					$CONF[]="# Wan2Lan - Wizard mode.";
					$CONF[]="router4 Wan2Lan inface {$FireHolConf["IF_WAN"]} outface {$FireHolConf["IF_LAN"]}";
					$CONF[]="\tmasquerade";
					if($nic->firewall_artica==1){ $CONF[]="\troute artica accept"; }
					if(count($ipdeny["ROUTERS"])>0){$CONF[]=@implode("\n", $ipdeny["ROUTERS"]);}
					$CONF[]="\tclient any accept";
					$PersonalRules=new firehol_rules("{$FireHolConf["IF_WAN"]}2{$FireHolConf["IF_LAN"]}",1);
					$CONF[]=$PersonalRules->buildrules();
					$CONF[]="\tserver ident reject with tcp-reset";
					$CONF[]="";
					$this->InterfaceSet["{$FireHolConf["IF_WAN"]}2{$FireHolConf["IF_LAN"]}"]=true;
				}
			}
			
			if($q->TABLE_EXISTS("pnic_bridges", "artica_backup")){
				$sql="SELECT * FROM `pnic_bridges` WHERE `enabled`=1";
				$results = $q->QUERY_SQL($sql,"artica_backup");
				$RTRS=0;
				while ($ligne = mysql_fetch_assoc($results)) {
					$RTRS++;
					$nic_from=$ligne["nic_from"];
					$nic_to=$ligne["nic_to"];
					$routerid=$ligne["ID"];
					$opts=array();
					
					if($ligne["masquerading"]==1){$opts[]="\tmasquerade";}
					if($ligne["masquerading_invert"]==1){$opts[]="\tmasquerade reverse";}
					if($ligne["DenyDHCP"]==1){$opts[]="\tserver dhcp deny";}
					$nic=new system_nic($nic_from);
					
				
					$RouterName="{$nic_from}2{$nic_to}";
					$CONF[]="# pnic_bridges: $RouterName; Artica allowed=$nic->firewall_artica;";
					$CONF[]="router4 $RouterName inface $nic_from outface $nic_to";
					if($nic->firewall_artica==1){ $CONF[]="\troute artica accept"; }
					
					$PersonalRules=new firehol_rules($RouterName,1);
					$CONF[]=$PersonalRules->buildrules();
					
					if($ligne["DenyCountries"]==1){
						if(count($ipdeny["ROUTERS"])>0){$CONF[]=@implode("\n", $ipdeny["ROUTERS"]);}
					}
					
					if(count($opts)>0){
						$CONF[]=@implode("\n", $opts);
					}
					
					$services=$this->router_services($routerid);
					if($services<>null){
						$CONF[]=$services;
					}
					
					$CONF[]="\troute any accept";
					$CONF[]="\tclient any accept";
					$CONF[]="";
				}
				
				
				if($RTRS>0){
					$this->ip_forward=1;
					
				}
			}
			
		
			$CONF[]="";
			return @implode("\n", $CONF);
		
		}
		
		
	private function router_services($routerid){
		$q=new mysql();
		$sql="SELECT firehol_services_routers.service,firehol_services_routers.allow,firehol_services_routers.zOrder,
		firehol_services_def.enabled
		  FROM `firehol_services_routers`,`firehol_services_def` WHERE firehol_services_routers.routerid={$routerid} 
		  AND firehol_services_def.service=firehol_services_routers.service 
		  AND `firehol_services_def`.enabled=1
		  ORDER BY zOrder";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(mysql_num_rows($results)==0){return null;}
		
		$allow_type[0]="reject";
		$allow_type[1]="accept";
		
		while ($ligne = mysql_fetch_assoc($results)) {
			
			if($ligne["enabled"]==0){
				$CONF[]="# {$ligne["service"]} Disabled...";
				continue;
			}
			
			
			$whitelisted_destination_networks=$this->router_services_excludes($ligne["service"],$routerid,1);
			$whitelisted_src_networks=$this->router_services_excludes($ligne["service"],$routerid,0);
			
			$CONF[]="\tserver {$ligne["service"]} {$allow_type[$ligne["allow"]]}{$whitelisted_destination_networks}{$whitelisted_src_networks}";
		}
		
		if(count($CONF)==0){return null;}
		return @implode("\n",$CONF);
	}
	
	private function router_services_excludes($service,$routerid,$destination){
		$q=new mysql();
		$sql="SELECT pattern  FROM `firehol_routers_exclude` WHERE destination=$destination 
		AND service='$service' AND routerid='$routerid'";
		$results = $q->QUERY_SQL($sql,"artica_backup");
		if(mysql_num_rows($results)==0){return;}
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$f[]=$ligne["pattern"];
		}
		
		if(count($f)==0){return null;}
		if($destination==1){ return " dst not \"".@implode(" ", $f)."\" "; }
		return " src not \"".@implode(" ", $f)."\" ";
	}
		
		
		
	private function CheckDirs(){
		$dirs[]="/home/artica/firewall";
		while (list ($num, $dr) = each ($dirs)){
			if(!is_dir($dr)){
				@mkdir($dr,0755,true);
			}
			
		}
		
	}
	
	public function interface_count_local_services($Interface){
		$q=new mysql();
		$sql="SELECT COUNT(`service`) as tcount FROM `firehol_client_services` WHERE `enabled`=1 AND `interface`='$Interface'";
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		return $ligne["tcount"];
		
	}
	
	public function interface_count_allowed_services($Interface){
		$q=new mysql();
		$sql="SELECT COUNT(`service`) as tcount FROM `firehol_services` WHERE `enabled`=1 AND `interface`='$Interface'";
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		return $ligne["tcount"];
	
	}	
	
	public function checkTables(){
		$q=new mysql();
		
		$sql="CREATE TABLE IF NOT EXISTS `firehol_services` (
				`ID` int(11) NOT NULL AUTO_INCREMENT,
				`zmd5` VARCHAR( 90 ),
				`interface` VARCHAR( 90 ),
				`service` VARCHAR( 20 ),
				`enabled` smallint(1) not null,
				`allow_type` smallint(1) not null,
				 PRIMARY KEY (`ID`),
				  UNIQUE KEY (`zmd5`),
				  KEY `interface` (`interface`),
				  KEY `service` (`service`),
				  KEY `enabled` (`enabled`),
				  KEY `allow_type` (`allow_type`)
				) ENGINE=MYISAM;";
		
		
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error."$sql\n";return false;}
		
		$sql="CREATE TABLE IF NOT EXISTS `firehol_client_services` (
				`ID` int(11) NOT NULL AUTO_INCREMENT,
				`zmd5` VARCHAR( 90 ),
				`interface` VARCHAR( 90 ),
				`service` VARCHAR( 20 ),
				`enabled` smallint(1) not null,
				`allow_type` smallint(1) not null,
				 PRIMARY KEY (`ID`),
				  UNIQUE KEY (`zmd5`),
				  KEY `interface` (`interface`),
				  KEY `service` (`service`),
				  KEY `enabled` (`enabled`),
				  KEY `allow_type` (`allow_type`)
				) ENGINE=MYISAM;";
		
		
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error."$sql\n";return false;}

		$sql="CREATE TABLE IF NOT EXISTS `firehol_services_def` (
				`service` VARCHAR( 90 ),
				`server_port` VARCHAR( 256 ),
				`client_port` VARCHAR( 256 ),
				`enabled` smallint(1) not null,
				`helper` VARCHAR(90),
				 PRIMARY KEY (`service`),
 			      KEY `server_port` (`server_port`),
				  KEY `client_port` (`client_port`),
				  KEY `enabled` (`enabled`)
				) ENGINE=MYISAM;";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error."$sql\n";return false;}
		
		
		$sql="CREATE TABLE IF NOT EXISTS `firehol_services_routers` (
				`ID` BIGINT(100) NOT NULL AUTO_INCREMENT,
				`routerid` INT(10) NOT NULL,
				`service` VARCHAR( 90 ),
				`zOrder` smallint(10) NOT NULL,
				`allow` smallint(1) NOT NULL,
				 PRIMARY KEY (`ID`),
 			     KEY `routerid` (`routerid`),
				 KEY `service` (`service`),
				 KEY `zOrder` (`zOrder`),
				 KEY `allow` (`allow`)
				) ENGINE=MYISAM;";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error."$sql\n";return false;}	

		
		$sql="CREATE TABLE IF NOT EXISTS `firehol_routers_exclude` (
		`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		 `routerid` INT(10) NOT NULL,
		 `service` VARCHAR( 90 ),
		 `pattern` VARCHAR(128) NOT NULL,
		 `destination`  smallint(1) NOT NULL DEFAULT '0' ,
		 KEY `pattern` (`pattern`),
		 KEY `destination` (`destination`),
		 KEY `routerid` (`routerid`),
		 KEY `service` (`service`)
		)  ENGINE = MYISAM;";
		
		$q->QUERY_SQL($sql,"artica_backup");
		
		
		
		
		
		if($q->COUNT_ROWS("firehol_services_def", "artica_backup")==0){
			$data=base64_decode(@file_get_contents("/usr/share/artica-postfix/ressources/databases/firehol.services.db"));
			$services=unserialize($data);
			if(count($services)<2){
				echo "<H2 style='color:#d32d2d'>FATAL no services database</H2><hr>$data";
				return false;
			}
			
			while (list ($service, $array) = each ($services) ){	
				$server_port=mysql_escape_string2($array["server"]["ports"]);
				$client_port=mysql_escape_string2($array["client"]["ports"]);
				$helper=mysql_escape_string2($array["helper"]);
				$f[]="('$service','$server_port','$client_port','$helper',1)";
				}	
			$sql="INSERT IGNORE INTO `firehol_services_def` (service,server_port,client_port,helper,enabled) VALUES ". @implode(",", $f);
			$q->QUERY_SQL($sql,'artica_backup');
			if(!$q->ok){echo $q->mysql_error."<hr>\n$sql\n";return fasle;}
			
		}
		
		return true;
		
	}
	
	private function isSquid(){
		if(isset($GLOBALS["isSquid"])){return $GLOBALS["isSquid"];}
		$unix=new unix();
		$squidbin=$unix->LOCATE_SQUID_BIN();
		if(!is_file($squidbin)){$GLOBALS["isSquid"]=false;return $GLOBALS["isSquid"];}
		$sock=new sockets();
		
		
		$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
		if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
		if($SQUIDEnable==0){$GLOBALS["isSquid"]=false;return $GLOBALS["isSquid"];}
		
		$GLOBALS["isSquid"]=true;
		return $GLOBALS["isSquid"];
		
	}
	
	private function isUfdbguardHTTP(){
		if(isset($GLOBALS["isUfdbguardHTTP"])){return $GLOBALS["isUfdbguardHTTP"];}
		if(!$this->isSquid()){$GLOBALS["isUfdbguardHTTP"]=false;return $GLOBALS["isUfdbguardHTTP"];}
		
		$sock=new sockets();
		$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
		if($SquidPerformance>2){$GLOBALS["isUfdbguardHTTP"]=false;return $GLOBALS["isUfdbguardHTTP"];}
		$EnableSquidGuardHTTPService=$sock->GET_INFO("EnableSquidGuardHTTPService");
		if(!is_numeric($EnableSquidGuardHTTPService)){$EnableSquidGuardHTTPService=1;}
		if($EnableSquidGuardHTTPService==0){$GLOBALS["isUfdbguardHTTP"]=false;return $GLOBALS["isUfdbguardHTTP"];}
		
		$EnableUfdbGuard=intval($sock->EnableUfdbGuard());
		if($EnableUfdbGuard==0){$GLOBALS["isUfdbguardHTTP"]=false;return $GLOBALS["isUfdbguardHTTP"];}
		$GLOBALS["isUfdbguardHTTP"]=true;
		return $GLOBALS["isUfdbguardHTTP"];
	}
	
	private function secure_gateway_defaults(){
		$q=new mysql();
		
		$q->QUERY_SQL("INSERT IGNORE INTO `gateway_secure` (portname,dport,dproto,enabled)
			VALUES
			('HTTP service','80','0','1'),
			('HTTPs service','443','0','1'),
			('SMTP service','25','0','1'),
			('SMTP service','587','0','1'),
			('SMTPs service','465','0','1'),
			('IMAP service','143','0','1'),
			('IMAPs service','995','0','1'),
			('POP3 service','110','0','1'),
			('POP3s service','995','0','1'),
			('FTP service','21','0','1'),
			('FTP service -2','20','0','1'),
			('SSH service','22','0','1'),
			('Telnet service','23','0','1'),
			('DNS service','53','0','1'),
			('DNS service','53','1','1'),
			('NNTP service','119','0','1'),
			('NETBIOS service','137','0','1'),
			('NETBIOS service','138','0','1'),
			('NETBIOS service','139','0','1'),
			('TSE','3389','0','1'),
			('iTunes','3689','0','1'),
			('iTunes','3689','1','1')","artica_backup");
		
	}
	
	
	private function secure_gateway(){
		if(isset($GLOBALS["secure_gateway"])){return $GLOBALS["secure_gateway"];}
		$q=new mysql();
		$sql="CREATE TABLE IF NOT EXISTS `gateway_secure`
	(  ID INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	 `dport` INT(10) NOT NULL, `portname` varchar(256) NOT NULL, `dproto` smallint(1) NOT NULL, `enabled` smallint(1) NOT NULL, KEY `portname` (`portname`), KEY `enabled` (`enabled`) ) ENGINE=MYISAM;";
		$q->QUERY_SQL($sql,"artica_backup");
		if($q->COUNT_ROWS("gateway_secure", "artica_backup")==0){$this->secure_gateway_defaults();}
		
		
		$sql="SELECT * FROM `gateway_secure` WHERE enabled=1";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		$PROTO[0]="tcp";
		$PROTO[1]="udp";
		$EX[]="\tserver ICMP accept";
		while ($ligne = mysql_fetch_assoc($results)) {
			$dport=$ligne["dport"];
			$proto=$PROTO[$ligne["dproto"]];
			$service="Service{$proto}{$dport}";
				
			$EX[]="\troute {$service} accept";
			$f[]="#FHVER: 1:213";
			$f[]="server_{$service}_ports=\"$proto/$dport\"";
			$f[]="client_{$service}_ports=\"default\"";
			$f[]="";
			@file_put_contents("/etc/firehol/services/{$service}.conf",@implode("\n", $f));
			$f=array();
		}
		$GLOBALS["secure_gateway"]=@implode("\n",$EX);
		return $GLOBALS["secure_gateway"];
	}
	
	
	
	private function BUILD_SERVICES(){
		$q=new mysql();
		$sock=new sockets();
		$unix=new unix();
		$SS5_SOCKS_PORT_TEXT=null;
		$ArticaHttpsPort=intval($sock->GET_INFO("ArticaHttpsPort"));
		if($ArticaHttpsPort==0){$ArticaHttpsPort=9000;}
		if($q->TABLE_EXISTS("firehol_services_def", "artica_backup")){$this->checkTables();}
		if($q->COUNT_ROWS("firehol_services_def", "artica_backup")==0){$this->checkTables();}
		$LOG_PREFIX="Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]}";
		

		
		$this->services_available["webmanagement"]=true;
		$this->services_available["artica"]=true;
		$this->services_available["squidports"]=false;
		
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} creating service webmanagement on port $ArticaHttpsPort..\n";
		$f[]="#FHVER: 1:213";
		$f[]="server_webmanagement_ports=\"tcp/$ArticaHttpsPort\"";
		$f[]="client_webmanagement_ports=\"default\"";
		$f[]="";
		@file_put_contents("/etc/firehol/services/webmanagement.conf",@implode("\n", $f));
		
		echo "$LOG_PREFIX Creating service webmanagement\n";
		$f=array();
		$f[]="#FHVER: 1:213";
		$f[]="server_artica_ports=\"tcp/$ArticaHttpsPort\"";
		$f[]="client_artica_ports=\"default\"";
		$f[]="";
		@file_put_contents("/etc/firehol/services/artica.conf",@implode("\n", $f));
		
		// NTOPNG **********************************************************************
		$arrayConf=unserialize(base64_decode($sock->GET_INFO("ntopng")));
		if(!is_numeric($arrayConf["HTTP_PORT"])){$arrayConf["HTTP_PORT"]=3000;}
		$f=array();
		$f[]="#FHVER: 1:213";
		$f[]="server_ntopng_ports=\"tcp/{$arrayConf["HTTP_PORT"]}\"";
		$f[]="client_ntopng_ports=\"default\"";
		$f[]="";
		echo "$LOG_PREFIX Creating service ntopng tcp/{$arrayConf["HTTP_PORT"]}\n";
		@file_put_contents("/etc/firehol/services/ntopng.conf",@implode("\n", $f));
		$f=array();		
		
		// Proxy Web Page error **********************************************************************
		$SquidGuardApachePort=intval($sock->GET_INFO("SquidGuardApachePort"));
		$SquidGuardApacheSSLPort=intval($sock->GET_INFO("SquidGuardApacheSSLPort"));
		if($SquidGuardApachePort==0){$SquidGuardApachePort=9020;}
		if($SquidGuardApacheSSLPort==0){$SquidGuardApacheSSLPort=9025;}
		$f=array();
		$f[]="#FHVER: 1:213";
		$f[]="server_ufdbhttp_ports=\"tcp/$SquidGuardApachePort tcp/$SquidGuardApacheSSLPort\"";
		$f[]="client_ufdbhttp_ports=\"default\"";
		$f[]="";
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Creating service ufdbhttp tcp/$SquidGuardApachePort tcp/$SquidGuardApacheSSLPort\n";
		@file_put_contents("/etc/firehol/services/ufdbhttp.conf",@implode("\n", $f));
		$f=array();
		
		// Proxy Ports **********************************************************************
		$SquidGuardApachePort=intval($sock->GET_INFO("SquidGuardApachePort"));
		$SquidGuardApacheSSLPort=intval($sock->GET_INFO("SquidGuardApacheSSLPort"));
		if($SquidGuardApachePort==0){$SquidGuardApachePort=9020;}
		if($SquidGuardApacheSSLPort==0){$SquidGuardApacheSSLPort=9025;}
		$f=array();
		$f[]="#FHVER: 1:213";
		$f[]="server_ufdbhttp_ports=\"tcp/$SquidGuardApachePort tcp/$SquidGuardApacheSSLPort\"";
		$f[]="client_ufdbhttp_ports=\"default\"";
		$f[]="";
		echo "$LOG_PREFIX Creating service Web filtering page tcp/{$SquidGuardApachePort}\n";
		@file_put_contents("/etc/firehol/services/ufdbhttp.conf",@implode("\n", $f));
		$f=array();
		// Influx Ports **********************************************************************
		$InfluxAdminPort=intval($sock->GET_INFO("InfluxAdminPort"));
		$SS5_SOCKS_PORT=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SS5_SOCKS_PORT"));
		if($InfluxAdminPort==0){$InfluxAdminPort=8083;}
		$f=array();
		$f[]="#FHVER: 1:213";
		$f[]="server_influxdb_ports=\"tcp/$InfluxAdminPort\"";
		$f[]="client_influxdb_ports=\"default\"";
		$f[]="";
		echo "$LOG_PREFIX Creating service InfluxDB tcp/{$InfluxAdminPort}\n";
		@file_put_contents("/etc/firehol/services/influxdb.conf",@implode("\n", $f));
		$f=array();
		
		
		// bitorrent Ports **********************************************************************
		$f[]="#FHVER: 1:213";
		$f[]="server_torrent_ports=\"tcp/6881:6999\"";
		$f[]="client_torrent_ports=\"default\"";
		$f[]="";
		@file_put_contents("/etc/firehol/services/torrent.conf",@implode("\n", $f));
		$f=array();
		
		// ss5 Ports **********************************************************************
		if($SS5_SOCKS_PORT>0){$SS5_SOCKS_PORT_TEXT="tcp/$SS5_SOCKS_PORT";}
		$f[]="#FHVER: 1:213";
		$f[]="server_ss5_ports=\"tcp/31337 $SS5_SOCKS_PORT_TEXT\"";
		$f[]="client_ss5_ports=\"default\"";
		$f[]="";
		echo "$LOG_PREFIX Creating service Socks Proxy $SS5_SOCKS_PORT_TEXT/31337\n";
		@file_put_contents("/etc/firehol/services/ss5.conf",@implode("\n", $f));
		$f=array();
		
		
		@unlink("/etc/firehol/services/squidports.conf");
		if($this->isSquid()){
			$f=array();
			$q=new mysql_squid_builder();
			$sql="SELECT port FROM proxy_ports WHERE enabled=1";
			$results = $q->QUERY_SQL($sql);
			$pp=array();
			while ($ligne = mysql_fetch_assoc($results)) {
				$pp[]="tcp/".$ligne["port"];
			}
			if(count($pp)>0){
				$this->services_available["squidports"]=true;
				$f[]="#FHVER: 1:213";
				$f[]="server_squidports_ports=\"".@implode(" ", $pp)."\"";
				$f[]="client_squidports_ports=\"default\"";
				$f[]="";
				echo "$LOG_PREFIX Creating service HTTP Proxy Ports\n";
				@file_put_contents("/etc/firehol/services/squidports.conf",@implode("\n", $f));
				$f=array();
			}
		}
		
		
	
		
		if(!is_file("/etc/firehol/services/dhcp.conf")){
			$f=array();
			echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} creating service dhcp.conf\n";
			$f[]="#FHVER: 1:213";
			$f[]="server_dhcp_ports=\"udp/67\"";
			$f[]="client_dhcp_ports=\"68\"";
			$f[]="";
			@file_put_contents("/etc/firehol/services/dhcp.conf",@implode("\n", $f));
		}
		

		
		
		
		$q=new mysql();
		$sql="SELECT * FROM `firehol_services_def` WHERE enabled=1";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error."\n";return false;}
		
		
		$blacklists["all"]=true;
		$blacklists["AH"]=true;
		$blacklists["ESP"]=true;
		$blacklists["GRE"]=true;
		$blacklists["ICMP"]=true;
		$blacklists["ICMPV6"]=true;
		$blacklists["OSPF"]=true;
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} firehol_services_def=". mysql_num_rows($results)."\n";
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$service=$ligne["service"];
			if(isset($blacklists[$service])){continue;}
			
			$this->services_available[$ligne["service"]]=true;
			$server_port=$ligne["server_port"];
			$client_port=$ligne["client_port"];
			$f=array();
			$f[]="#FHVER: 1:213";
			$this->services_available[$ligne["service"]]=true;
			echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} creating service {$service}\n";
			if($server_port<>null){
				$f[]="server_{$service}_ports=\"{$server_port}\"";
			}
			if($client_port<>null){
				$f[]="client_{$service}_ports=\"{$client_port}\"";
			}
			if($ligne["helper"]<>null){
				$f[]="helper_{$service}=\"{$ligne["helper"]}\"";
			}
				
				
			$f[]="";
			@file_put_contents("/etc/firehol/services/{$service}.conf",@implode("\n", $f));
			
		}
		
		while (list ($service, $array) = each ($blacklists) ){
			if(is_file("/etc/firehol/services/{$service}.conf")){
				@unlink("/etc/firehol/services/{$service}.conf");
				$q->QUERY_SQL("DELETE FROM firehol_services_def WHERE service='$service'","artica_backup");
				
			}
			
		}
		
		$f=$unix->DirFiles("/etc/firehol/services");
		while (list ($filename, $array) = each ($f) ){
			if(!preg_match("#\.conf$#", $filename)){
				@unlink("/etc/firehol/services/$filename");
			}
		}
			
		
		
		
		
	}
	
	
	private function unifi_services($interface){
		if($interface=="lo"){return null;}
		$sock=new sockets();
		$UnifiListenInterface=$sock->GET_INFO("UnifiListenInterface");
		$EnableUnifiController=intval($sock->GET_INFO("EnableUnifiController"));
		if($EnableUnifiController==0)
		if($UnifiListenInterface<>null){
			if($UnifiListenInterface<>$interface){return null;}
		}
		$UnifiHTTPPort=intval($sock->GET_INFO("UnifiHTTPPort"));
		$UnifiHTTPSPort=intval($sock->GET_INFO("UnifiHTTPSPort"));
		$UnifiPortalPort=intval($sock->GET_INFO("UnifiPortalPort"));
		$UnifiPortalSSLPort=intval($sock->GET_INFO("UnifiPortalSSLPort"));
		$UnifiUDPPort=intval($sock->GET_INFO("UnifiUDPPort"));
		
		if($UnifiHTTPPort==0){$UnifiHTTPPort=8088;}
		if($UnifiHTTPSPort==0){$UnifiHTTPSPort=8443;}
		if($UnifiPortalPort==0){$UnifiPortalPort=8880;}
		if($UnifiPortalSSLPort==0){$UnifiPortalSSLPort=8943;}
		if($UnifiUDPPort==0){$UnifiUDPPort=3478;}
		
		$ports[$UnifiHTTPPort]=true;
		$ports[$UnifiHTTPSPort]=true;
		$ports[$UnifiPortalPort]=true;
		$ports[$UnifiPortalSSLPort]=true;
		
		while (list ($pp, $dr) = each ($ports)){
			echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Unifi: port = tcp/$pp\n";
			$t[]="tcp/$pp";
		}
		
		@unlink("/etc/firehol/services/unifi.conf");
		$f[]="#FHVER: 1:213";
		$f[]="server_unifi_ports=\"".@implode(" ", $t)." udp/$UnifiUDPPort\"";
		$f[]="client_unifi_ports=\"default\"";
		$f[]="";
		@file_put_contents("/etc/firehol/services/unifi.conf",@implode("\n", $f));
		$f=array();
		return "unifi";
		
		
		
	} 
	
	
	public function edit_service($interface,$service,$allow,$enabled){
		$md5=md5("$interface$service");
		$q=new mysql();
		// 1 = Allow, 0 = Deny
		$sql="SELECT `zmd5`,ID FROM `firehol_services` WHERE `zmd5`='$md5'";
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!is_numeric($ligne["ID"])){$ligne["ID"]=0;}
		if($ligne["ID"]>0){
			$q->QUERY_SQL("UPDATE firehol_services 
					SET `allow_type`='$allow',`enabled`='$enabled' WHERE ID={$ligne["ID"]}","artica_backup");
			if(!$q->ok){echo $q->mysql_error;return false;}
			if($GLOBALS["VERBOSE"]){echo "edit_service($interface,$service,$allow,$enabled)=TRUE<br>\n";}
			return true;
		}
		
		$sql="INSERT IGNORE INTO `firehol_services`(`zmd5`,`service`,`allow_type`,`interface`,`enabled`)
		VALUES('$md5','$service','$allow','$interface','$enabled')";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
		if($GLOBALS["VERBOSE"]){echo "edit_service($interface,$service,$allow,$enabled)=TRUE<br>\n";}
		return true;
		
	}
	public function edit_client_service($interface,$service,$allow,$enabled){
		$md5=md5("$interface$service");
		$q=new mysql();
		// 1 = Allow, 0 = Deny
		$sql="SELECT `zmd5`,ID FROM `firehol_client_services` WHERE `zmd5`='$md5'";
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!is_numeric($ligne["ID"])){$ligne["ID"]=0;}
		if($ligne["ID"]>0){
			$q->QUERY_SQL("UPDATE firehol_services
					SET `allow_type`='$allow',`enabled`='$enabled' WHERE ID={$ligne["ID"]}","artica_backup");
					if(!$q->ok){echo $q->mysql_error;return false;}
					return true;
		}
	
		$sql="INSERT IGNORE INTO `firehol_client_services`(`zmd5`,`service`,`allow_type`,`interface`,`enabled`)
		VALUES('$md5','$service','$allow','$interface','$enabled')";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
		return true;
	
	}	
	
	
	private function fillServices(){
		$this->services_array["AH"]=true;
		$this->services_array["artica"]=true;
		$this->services_array["all"]=true;
		$this->services_array["amanda"]=true;
		$this->services_array["any"]=true;
		$this->services_array["anystateless"]=true;
		$this->services_array["apcupsd"]=true;
		$this->services_array["apcupsdnis"]=true;
		$this->services_array["aptproxy"]=true;
		$this->services_array["asterisk"]=true;
		$this->services_array["echo"]=true;
		$this->services_array["emule"]=true;
		$this->services_array["eserver"]=true;
		$this->services_array["ESP"]=true;
		$this->services_array["iax"]=true;
		$this->services_array["iax2"]=true;
		$this->services_array["icmp"]=true;
		$this->services_array["ICMP"]=true;
		$this->services_array["icmpv6"]=true;
		$this->services_array["ICMPV6"]=true;
		$this->services_array["icp"]=true;
		$this->services_array["ident"]=true;
		$this->services_array["imap"]=true;
		$this->services_array["imaps"]=true;
		$this->services_array["ipsecnatt"]=true;
		$this->services_array["ipv6error"]=true;
		$this->services_array["ipv6neigh"]=true;
		$this->services_array["ipv6router"]=true;
		$this->services_array["irc"]=true;
		$this->services_array["isakmp"]=true;
		$this->services_array["microsoftds"]=true;
		$this->services_array["mms"]=true;
		$this->services_array["msn"]=true;
		$this->services_array["msnp"]=true;
		$this->services_array["msds"]=true;
		$this->services_array["multicast"]=true;
		$this->services_array["mysql"]=true;
		$this->services_array["upnp"]=true;
		$this->services_array["uucp"]=true;
		$this->services_array["finger"]=true;
		$this->services_array["jabber"]=true;
		$this->services_array["jabberd"]=true;
		$this->services_array["netbackup"]=true;
		$this->services_array["netbiosdgm"]=true;
		$this->services_array["netbiosns"]=true;
		$this->services_array["netbiosssn"]=true;
		$this->services_array["nfs"]=true;
		$this->services_array["nis"]=true;
		$this->services_array["nntp"]=true;
		$this->services_array["nntps"]=true;
		$this->services_array["nrpe"]=true;
		$this->services_array["ntp"]=true;
		$this->services_array["nut"]=true;
		$this->services_array["nxserver"]=true;
		$this->services_array["radius"]=true;
		$this->services_array["radiusold"]=true;
		$this->services_array["radiusoldproxy"]=true;
		$this->services_array["radiusproxy"]=true;
		$this->services_array["rdp"]=true;
		$this->services_array["rndc"]=true;
		$this->services_array["rsync"]=true;
		$this->services_array["rtp"]=true;
		$this->services_array["vmware"]=true;
		$this->services_array["vmwareauth"]=true;
		$this->services_array["vmwareweb"]=true;
		$this->services_array["vnc"]=true;
		$this->services_array["cups"]=true;
		$this->services_array["custom"]=true;
		$this->services_array["gift"]=true;
		$this->services_array["giftui"]=true;
		$this->services_array["gkrellmd"]=true;
		$this->services_array["GRE"]=true;
		$this->services_array["cvspserver"]=true;
		$this->services_array["openvpn"]=true;
		$this->services_array["oracle"]=true;
		$this->services_array["OSPF"]=true;
		$this->services_array["samba"]=true;
		$this->services_array["sane"]=true;
		$this->services_array["sip"]=true;
		$this->services_array["smtp"]=true;
		$this->services_array["smtps"]=true;
		$this->services_array["snmp"]=true;
		$this->services_array["snmptrap"]=true;
		$this->services_array["socks"]=true;
		$this->services_array["squid"]=true;
		$this->services_array["ssh"]=true;
		$this->services_array["stun"]=true;
		$this->services_array["submission"]=true;
		$this->services_array["sunrpc"]=true;
		$this->services_array["swat"]=true;
		$this->services_array["syslog"]=true;
		$this->services_array["webcache"]=true;
		$this->services_array["webmin"]=true;
		$this->services_array["whois"]=true;
		$this->services_array["darkstat"]=true;
		$this->services_array["daytime"]=true;
		$this->services_array["dcc"]=true;
		$this->services_array["dcpp"]=true;
		$this->services_array["dhcp"]=true;
		$this->services_array["dhcprelay"]=true;
		$this->services_array["dhcpv6"]=true;
		$this->services_array["dict"]=true;
		$this->services_array["distcc"]=true;
		$this->services_array["dns"]=true;
		$this->services_array["h323"]=true;
		$this->services_array["heartbeat"]=true;
		$this->services_array["http"]=true;
		$this->services_array["httpalt"]=true;
		$this->services_array["https"]=true;
		$this->services_array["hylafax"]=true;
		$this->services_array["l2tp"]=true;
		$this->services_array["ldap"]=true;
		$this->services_array["ldaps"]=true;
		$this->services_array["lpd"]=true;
		$this->services_array["ping"]=true;
		$this->services_array["pop3"]=true;
		$this->services_array["pop3s"]=true;
		$this->services_array["portmap"]=true;
		$this->services_array["postgres"]=true;
		$this->services_array["pptp"]=true;
		$this->services_array["privoxy"]=true;
		$this->services_array["telnet"]=true;
		$this->services_array["tftp"]=true;
		$this->services_array["time"]=true;
		$this->services_array["timestamp"]=true;
		$this->services_array["tomcat"]=true;
		$this->services_array["xbox"]=true;
		$this->services_array["xdmcp"]=true;
		$this->services_array["ntopng"]=true;
		$this->services_array["ss5"]=true;
		
		ksort($this->services_array);
		}
	
		
	private function IpToInface($ipaddr){
		if($ipaddr=="0.0.0.0"){return null;}
		$unix=new unix();
		
		if(!isset($GLOBALS["NETWORK_ALL_INTERFACES"])){$GLOBALS["NETWORK_ALL_INTERFACES"]=$unix->NETWORK_ALL_INTERFACES();}
		$IPADDRS=$GLOBALS["NETWORK_ALL_INTERFACES"];
		while (list ($interface, $dr) = each ($IPADDRS)){
			$IPADDRZ=$dr["IPADDR"];
			if($IPADDRZ==$ipaddr){return $interface;}
		}
		
	}
	
	private function IPSET_ALL_IPS_LIST(){
		if(!isset($GLOBALS["NETWORK_ALL_INTERFACES"])){$unix=new unix();$GLOBALS["NETWORK_ALL_INTERFACES"]=$unix->NETWORK_ALL_INTERFACES();}
		$IPADDRS=$GLOBALS["NETWORK_ALL_INTERFACES"];
		while (list ($interface, $dr) = each ($IPADDRS)){
			$IPADDRZ=$dr["IPADDR"];
			$f[$IPADDRZ]=$IPADDRZ;
		}
		
		while (list ($net,$none) = each ($FF)){
			$FF_1[]=$net;
				
		}
		
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} ".count($FF_1)." MyIPs items.\n";
		@file_put_contents("/etc/firehol/MyIPs", @implode("\n", $FF_1));
	
		
	}
	
	private function transparent_ftp($IF_LAN=null){
		if(!isset($GLOBALS["NETWORK_ALL_INTERFACES"])){$unix=new unix();$GLOBALS["NETWORK_ALL_INTERFACES"]=$unix->NETWORK_ALL_INTERFACES();}
		$unix=new unix();
		$sock=new sockets();
		$squidbin=$unix->LOCATE_SQUID_BIN();
		if(!is_file($squidbin)){return;}
		$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
		if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
		$SquidSSLUrgency=intval($sock->GET_INFO("SquidSSLUrgency"));
		if($SQUIDEnable==0){return;}
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM proxy_ports WHERE enabled=1 AND FTP_TRANSPARENT=1";
		$results = $q->QUERY_SQL($sql);
	
		while ($ligne = mysql_fetch_assoc($results)) {
			$eth=$ligne["nic"];
			$ID=$ligne["ID"];
			$META_PORT=21;
			$UseSSL=intval($ligne["UseSSL"]);
			$incoming_port="transparent_proxy 21";
			if($eth<>null){
				$inface_text=" inface $eth";
			}
				
			if($UseSSL==1){
				if($SquidSSLUrgency==1){continue;}
				$incoming_port="transparent_proxy 21";
				$META_PORT=21;
			}
				
			$port=$ligne["port"];
			//dst not ipset:proxy_white_ssl"
			$this->ip_forward=1;
			$squid_ports_include=$this->squid_ports_include(1,$ID,$META_PORT);
			$squid_ports_exclude=$this->squid_ports_include(0,$ID,$META_PORT);
				
				
			$CONF[]="{$incoming_port} $port squid{$inface_text}{$squid_ports_include}{$squid_ports_exclude}";
	
				
		}
	
		return @implode("\n",$CONF);
	}	
	
	private function transparent_socks($IF_LAN=null){
		if(!isset($GLOBALS["NETWORK_ALL_INTERFACES"])){$unix=new unix();$GLOBALS["NETWORK_ALL_INTERFACES"]=$unix->NETWORK_ALL_INTERFACES();}
		$unix=new unix();
		$sock=new sockets();
		$EnableSS5=intval($sock->GET_INFO("EnableSS5"));
		if($EnableSS5==0){return null;}
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM ss5_transparent WHERE enabled=1";
		$results = $q->QUERY_SQL($sql);
	
		while ($ligne = mysql_fetch_assoc($results)) {
			$eth=$ligne["eth"];
			$ID=$ligne["ID"];
			$port=$ligne["dst_port"];
			$dst=null;
			$src=null;
			if($eth<>null){$inface_text=" inface $eth";}
			
			
			$ligne["src_host"]=trim($ligne["src_host"]);
			if($ligne["src_host"]=="0.0.0.0"){$ligne["src_host"]=null;}
			if($ligne["src_host"]=="0.0.0.0/0"){$ligne["src_host"]=null;}
				
			$ligne["dst_host"]=trim($ligne["dst_host"]);
			if($ligne["dst_host"]=="0.0.0.0"){$ligne["dst_host"]=null;}
			if($ligne["dst_host"]=="0.0.0.0/0"){$ligne["dst_host"]=null;}
				
			$incoming_port="transparent_proxy $port";
				
			if($ligne["dst_host"]<>null){
				$dst=" dst {$ligne["dst_host"]}";
			}
			if($ligne["src_host"]<>null){
				$src=" src {$ligne["src_host"]}";
			}
				
			$CONF[]="{$incoming_port} 31337 squid{$inface_text}{$src}{$dst}";
	
				
		}
	
		return @implode("\n",$CONF);
	}	
	
	private function transparent_squid($IF_LAN=null){
		if(!isset($GLOBALS["NETWORK_ALL_INTERFACES"])){$unix=new unix();$GLOBALS["NETWORK_ALL_INTERFACES"]=$unix->NETWORK_ALL_INTERFACES();}
		$unix=new unix();
		$sock=new sockets();
		$squidbin=$unix->LOCATE_SQUID_BIN();
		if(!is_file($squidbin)){return;}
		$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
		if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
		$SquidSSLUrgency=intval($sock->GET_INFO("SquidSSLUrgency"));
		if($SQUIDEnable==0){return;}
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM proxy_ports WHERE enabled=1 AND transparent=1";
		$results = $q->QUERY_SQL($sql);
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$eth=$ligne["nic"];
			$ID=$ligne["ID"];
			$META_PORT=80;
			$UseSSL=intval($ligne["UseSSL"]);
			$incoming_port="transparent_squid";
			if($eth<>null){
				$inface_text=" inface $eth";
			}
			
			if($UseSSL==1){
				if($SquidSSLUrgency==1){continue;}
				$incoming_port="transparent_proxy 443";
				$META_PORT=443;
			}
			
			$port=$ligne["port"];
			//dst not ipset:proxy_white_ssl"
			$this->ip_forward=1;
			$squid_ports_include=$this->squid_ports_include(1,$ID,$META_PORT);
			$squid_ports_exclude=$this->squid_ports_include(0,$ID,$META_PORT);
			
			
			$CONF[]="{$incoming_port} $port squid{$inface_text}{$squid_ports_include}{$squid_ports_exclude}";

			
		}
		
		return @implode("\n",$CONF);
	}
	
	private function transparent_squid_meta($xinclude,$port){
		if(!is_file("/etc/squid3/meta-transparent-whitelist.array")){return null;}
		$data=unserialize(@file_get_contents("/etc/squid3/meta-transparent-whitelist.array"));

		$f=array();
		while (list ($index, $ligne) = each ($data) ){
			$pattern=$ligne["pattern"];
			$destport=$ligne["destport"];
			$include=$ligne["include"];
			if($destport<>$port){continue;}
			if($include<>$xinclude){continue;}
			$f[]=$pattern;
			
		}
		
		if(count($f)>0){return $f;}
		
	}
	
	
	
	private function transparent_proxy($IF_LAN=null){
		$unix=new unix();
		$sock=new sockets();
		$squidbin=$unix->LOCATE_SQUID_BIN();
		if(!is_file($squidbin)){return "# Unable to find Squid Binary";}
		$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
		if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
		$SquidSSLUrgency=intval($sock->GET_INFO("SquidSSLUrgency"));
		if($SQUIDEnable==0){return "# Tproxy: Proxy use is disabled";}
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM proxy_ports WHERE enabled=1 AND Tproxy=1";
		$results = $q->QUERY_SQL($sql);
		$CONF[]="# Tproxy: ".mysql_num_rows($results);
		

		
		while ($ligne = mysql_fetch_assoc($results)) {
			$eth=$ligne["eth"];
			$ID=$ligne["ID"];
			$port=$ligne["port"];
			$UseSSL=intval($ligne["UseSSL"]);
			$incoming_port=80;
			$ipaddr_text=null;
			$inface_text=null;
			
			if($eth<>null){
				$inface_text=" inface $eth";
				$ipaddr=$GLOBALS["NETWORK_ALL_INTERFACES"][$eth]["IPADDR"];
			}
			
			if($UseSSL==1){
				if($SquidSSLUrgency==1){continue;}
				$incoming_port=443;
			}
			
			
			
			
			if($ipaddr<>null){$ipaddr_text=" ip $ipaddr";}
			$this->ip_forward=1;
			$this->rp_filter=0;
			
			$squid_ports_include=$this->squid_ports_include(1,$ID);
			$squid_ports_exclude=$this->squid_ports_include(0,$ID);
			
			// src not ipset:MyIPs dst not ipset proxy_ssl_whitelist
			// src not ipset:MyIPs dst not ipset proxy_ssl_whitelist
			$CONF[]="tproxy {$incoming_port} port $port{$ipaddr_text}{$inface_text}{$squid_ports_include}{$squid_ports_exclude}";
				
		}
		
		return @implode("\n",$CONF);
	}
	
	private function nginx_service(){
		
		$EnableNginx=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableNginx"));
		if($EnableNginx==0){return null;}
		$q=new mysql_squid_builder();
		$results=$q->QUERY_SQL("SELECT port FROM reverse_www WHERE enabled=1");
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$PORTS["tcp/{$ligne["port"]}"]=true;
		}
		
		while (list ($index, $iptcp) = each ($PORTS)){
			$t[]=$index;
		}
		
		
		@unlink("/etc/firehol/services/nginx.conf");
		$f[]="#FHVER: 1:213";
		$f[]="server_nginx_ports=\"".@implode(" ", $t)."\"";
		$f[]="client_nginx_ports=\"default\"";
		$f[]="";
		@file_put_contents("/etc/firehol/services/nginx.conf",@implode("\n", $f));
		$f=array();
		return "nginx";
		
		
		
	}
	
	private function ipdeny(){
		if(isset($GLOBALS["IPDENYS"])){return $GLOBALS["IPDENYS"];}
		$GLOBALS["IPDENYS"]=array();
		$sock=new sockets();
		$EnableIpBlocks=$sock->GET_INFO("EnableIpBlocks");
		if($EnableIpBlocks<>1){return array();}
		$sql="SELECT * FROM ipblocks_set";
		$q=new mysql();
		$list=array("af"=>"afghanistan","al"=>"albania","dz"=>"algeria","as"=>"samoa","ad"=>"andorra","ao"=>"angola","ai"=>"anguilla","ag"=>"barbuda","ar"=>"argentina","am"=>"armenia","aw"=>"aruba","au"=>"australia","at"=>"austria","az"=>"azerbaijan","bs"=>"bahamas","bh"=>"bahrain","bd"=>"bangladesh","bb"=>"barbados","by"=>"belarus","be"=>"belgium","bz"=>"belize","bj"=>"benin","bm"=>"bermuda","bt"=>"bhutan","bo"=>"bolivia","ba"=>"herzegovina","bw"=>"botswana","br"=>"brazil","io"=>"territory","bn"=>"darussalam","bg"=>"bulgaria","bf"=>"faso","bi"=>"burundi","kh"=>"cambodia","cm"=>"cameroon","ca"=>"canada","ky"=>"islands","cf"=>"republic","cl"=>"chile","cn"=>"china","co"=>"colombia","cd"=>"the","ck"=>"islands","cr"=>"rica","ci"=>"ivoire","hr"=>"croatia","cu"=>"cuba","cy"=>"cyprus","cz"=>"republic","dk"=>"denmark","dj"=>"djibouti","do"=>"republic","ec"=>"ecuador","eg"=>"egypt","sv"=>"salvador","er"=>"eritrea","ee"=>"estonia","et"=>"ethiopia","fo"=>"islands","fj"=>"fiji","fi"=>"finland","fr"=>"france","gf"=>"guiana","pf"=>"polynesia","ga"=>"gabon","gm"=>"gambia","ge"=>"georgia","de"=>"germany","gh"=>"ghana","gi"=>"gibraltar","gr"=>"greece","gl"=>"greenland","gd"=>"grenada","gu"=>"guam","gt"=>"guatemala","gw"=>"bissau","gy"=>"guyana","ht"=>"haiti","hn"=>"honduras","hk"=>"kong","hu"=>"hungary","is"=>"iceland","in"=>"india","id"=>"indonesia","ir"=>"of","iq"=>"iraq","ie"=>"ireland","il"=>"israel","it"=>"italy","jm"=>"jamaica","jp"=>"japan","jo"=>"jordan","kz"=>"kazakhstan","ke"=>"kenya","ki"=>"kiribati","kr"=>"of","kw"=>"kuwait","kg"=>"kyrgyzstan","la"=>"republic","lv"=>"latvia","lb"=>"lebanon","ls"=>"lesotho","lr"=>"liberia","ly"=>"jamahiriya","li"=>"liechtenstein","lt"=>"lithuania","lu"=>"luxembourg","mo"=>"macao","mk"=>"of","mg"=>"madagascar","mw"=>"malawi","my"=>"malaysia","mv"=>"maldives","ml"=>"mali","mt"=>"malta","mr"=>"mauritania","mu"=>"mauritius","mx"=>"mexico","fm"=>"of","md"=>"of","mc"=>"monaco","mn"=>"mongolia","ma"=>"morocco","mz"=>"mozambique","mm"=>"myanmar","na"=>"namibia","nr"=>"nauru","np"=>"nepal","nl"=>"netherlands","an"=>"antilles","nc"=>"caledonia","nz"=>"zealand","ni"=>"nicaragua","ne"=>"niger","ng"=>"nigeria","nu"=>"niue","nf"=>"island","mp"=>"islands","no"=>"norway","om"=>"oman","pk"=>"pakistan","pw"=>"palau","ps"=>"occupied","pa"=>"panama","pg"=>"guinea","py"=>"paraguay","pe"=>"peru","ph"=>"philippines","pl"=>"poland","pt"=>"portugal","pr"=>"rico","qa"=>"qatar","ro"=>"romania","ru"=>"federation","rw"=>"rwanda","kn"=>"nevis","lc"=>"lucia","ws"=>"samoa","sm"=>"marino","sa"=>"arabia","sn"=>"senegal","sc"=>"seychelles","sl"=>"leone","sg"=>"singapore","sk"=>"slovakia","si"=>"slovenia","sb"=>"islands","za"=>"africa","es"=>"spain","lk"=>"lanka","sd"=>"sudan","sr"=>"suriname","sz"=>"swaziland","se"=>"sweden","ch"=>"switzerland","sy"=>"republic","tw"=>"china","tj"=>"tajikistan","tz"=>"of","th"=>"thailand","tg"=>"togo","to"=>"tonga","tt"=>"tobago","tn"=>"tunisia","tr"=>"turkey","tm"=>"turkmenistan","tv"=>"tuvalu","ug"=>"uganda","ua"=>"ukraine","ae"=>"emirates","gb"=>"kingdom","us"=>"states","uy"=>"uruguay","uz"=>"uzbekistan","vu"=>"vanuatu","ve"=>"venezuela","vn"=>"nam","vg"=>"british","ye"=>"yemen","zm"=>"zambia","zw"=>"zimbabwe");
		$results=$q->QUERY_SQL($sql,'artica_backup');
		if(!$q->ok){echo "$q->mysql_error\n";}
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			echo "Ban country {$list[$ligne["country"]]} {$ligne["country"]}\n";
			$this->ipdeny_perform($ligne["country"]);
		}
	}
	
	private function ipdeny_perform($country){
	
		
		$sql="SELECT * FROM ipblocks_db WHERE country='$country'";
		$list=array("af"=>"afghanistan","al"=>"albania","dz"=>"algeria","as"=>"samoa","ad"=>"andorra","ao"=>"angola","ai"=>"anguilla","ag"=>"barbuda","ar"=>"argentina","am"=>"armenia","aw"=>"aruba","au"=>"australia","at"=>"austria","az"=>"azerbaijan","bs"=>"bahamas","bh"=>"bahrain","bd"=>"bangladesh","bb"=>"barbados","by"=>"belarus","be"=>"belgium","bz"=>"belize","bj"=>"benin","bm"=>"bermuda","bt"=>"bhutan","bo"=>"bolivia","ba"=>"herzegovina","bw"=>"botswana","br"=>"brazil","io"=>"territory","bn"=>"darussalam","bg"=>"bulgaria","bf"=>"faso","bi"=>"burundi","kh"=>"cambodia","cm"=>"cameroon","ca"=>"canada","ky"=>"islands","cf"=>"republic","cl"=>"chile","cn"=>"china","co"=>"colombia","cd"=>"the","ck"=>"islands","cr"=>"rica","ci"=>"ivoire","hr"=>"croatia","cu"=>"cuba","cy"=>"cyprus","cz"=>"republic","dk"=>"denmark","dj"=>"djibouti","do"=>"republic","ec"=>"ecuador","eg"=>"egypt","sv"=>"salvador","er"=>"eritrea","ee"=>"estonia","et"=>"ethiopia","fo"=>"islands","fj"=>"fiji","fi"=>"finland","fr"=>"france","gf"=>"guiana","pf"=>"polynesia","ga"=>"gabon","gm"=>"gambia","ge"=>"georgia","de"=>"germany","gh"=>"ghana","gi"=>"gibraltar","gr"=>"greece","gl"=>"greenland","gd"=>"grenada","gu"=>"guam","gt"=>"guatemala","gw"=>"bissau","gy"=>"guyana","ht"=>"haiti","hn"=>"honduras","hk"=>"kong","hu"=>"hungary","is"=>"iceland","in"=>"india","id"=>"indonesia","ir"=>"of","iq"=>"iraq","ie"=>"ireland","il"=>"israel","it"=>"italy","jm"=>"jamaica","jp"=>"japan","jo"=>"jordan","kz"=>"kazakhstan","ke"=>"kenya","ki"=>"kiribati","kr"=>"of","kw"=>"kuwait","kg"=>"kyrgyzstan","la"=>"republic","lv"=>"latvia","lb"=>"lebanon","ls"=>"lesotho","lr"=>"liberia","ly"=>"jamahiriya","li"=>"liechtenstein","lt"=>"lithuania","lu"=>"luxembourg","mo"=>"macao","mk"=>"of","mg"=>"madagascar","mw"=>"malawi","my"=>"malaysia","mv"=>"maldives","ml"=>"mali","mt"=>"malta","mr"=>"mauritania","mu"=>"mauritius","mx"=>"mexico","fm"=>"of","md"=>"of","mc"=>"monaco","mn"=>"mongolia","ma"=>"morocco","mz"=>"mozambique","mm"=>"myanmar","na"=>"namibia","nr"=>"nauru","np"=>"nepal","nl"=>"netherlands","an"=>"antilles","nc"=>"caledonia","nz"=>"zealand","ni"=>"nicaragua","ne"=>"niger","ng"=>"nigeria","nu"=>"niue","nf"=>"island","mp"=>"islands","no"=>"norway","om"=>"oman","pk"=>"pakistan","pw"=>"palau","ps"=>"occupied","pa"=>"panama","pg"=>"guinea","py"=>"paraguay","pe"=>"peru","ph"=>"philippines","pl"=>"poland","pt"=>"portugal","pr"=>"rico","qa"=>"qatar","ro"=>"romania","ru"=>"federation","rw"=>"rwanda","kn"=>"nevis","lc"=>"lucia","ws"=>"samoa","sm"=>"marino","sa"=>"arabia","sn"=>"senegal","sc"=>"seychelles","sl"=>"leone","sg"=>"singapore","sk"=>"slovakia","si"=>"slovenia","sb"=>"islands","za"=>"africa","es"=>"spain","lk"=>"lanka","sd"=>"sudan","sr"=>"suriname","sz"=>"swaziland","se"=>"sweden","ch"=>"switzerland","sy"=>"republic","tw"=>"china","tj"=>"tajikistan","tz"=>"of","th"=>"thailand","tg"=>"togo","to"=>"tonga","tt"=>"tobago","tn"=>"tunisia","tr"=>"turkey","tm"=>"turkmenistan","tv"=>"tuvalu","ug"=>"uganda","ua"=>"ukraine","ae"=>"emirates","gb"=>"kingdom","us"=>"states","uy"=>"uruguay","uz"=>"uzbekistan","vu"=>"vanuatu","ve"=>"venezuela","vn"=>"nam","vg"=>"british","ye"=>"yemen","zm"=>"zambia","zw"=>"zimbabwe");
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,'artica_backup');
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$GLOBALS["IPDENYS"]["ROUTERS"][]="\troute all deny src {$ligne["cdir"]} loglimit  'IPDENY {$list["$country"]} - $country DROP:'";
			$GLOBALS["IPDENYS"]["SERVERS"][]="\tserver all deny src {$ligne["cdir"]} loglimit  'IPDENY {$list["$country"]} - $country DROP:'";
		}

	
	}
	
	
	private function ipset_bumbsslwl(){
		
		if(is_file("/etc/artica-postfix/settings/Daemons/SQUIDEnable")){
			$SQUIDEnable=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SQUIDEnable"));
		}
		
		if($SQUIDEnable==0){
			@file_put_contents("/etc/firehol/proxy_ssl_whitelist", "\n");
			return null;
		}
		
		$sock=new sockets();
		@file_put_contents("/etc/firehol/proxy_ssl_whitelist", "\n");
		$AllowSquidSSLDropBox=intval($sock->GET_INFO('AllowSquidSSLDropBox'));
		$AllowSquidSSLSkype=intval($sock->GET_INFO('AllowSquidSSLSkype'));
		$IPS=array();
		$FF=array();
		
	
		
		$q=new mysql_squid_builder();
		
		if(!$q->FIELD_EXISTS("proxy_ports","UseSSL")){
			$sql="ALTER TABLE `proxy_ports` ADD `UseSSL` smallint(1) NOT NULL DEFAULT '0' ,ADD INDEX ( `UseSSL` )";
			$q->QUERY_SQL($sql,'artica_backup');
		}
		
		
		$sql="SELECT count(*) as tcount FROM proxy_ports WHERE enabled=1 AND Tproxy=1 AND UseSSL=1";
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$tcount1=intval($ligne["tcount"]);
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $tcount1 Tproxy services\n";
		
		
		$sql="SELECT count(*) as tcount FROM proxy_ports WHERE enabled=1 AND transparent=1 AND UseSSL=1";
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){echo "FATAL $q->mysql_error\n";}
		$tcount2=intval($ligne["tcount"]);
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $tcount2 transparent services\n";
		$FINAL=$tcount2+$tcount1;
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $FINAL transparent ports services that use SSL\n";
		
		if($FINAL==0){return;}

		$ips=new products_ip_ranges();
		if($AllowSquidSSLDropBox==1){
			$IPS=$ips->dropbox_networks();
			while (list ($index, $iptcp) = each ($IPS)){
				$FF[$iptcp]=true;
			}
		}
		if($AllowSquidSSLSkype==1){
			$IPS=$ips->skype_networks();
			while (list ($index, $iptcp) = each ($IPS)){
				$FF[$iptcp]=true;
			}
		}
		

		
		$q=new mysql();
		$ipClass=new IP();
		$sql="SELECT *  FROM `squid_ssl` WHERE `type`='ssl-bump-wl' AND enabled=1";
		$results = $q->QUERY_SQL($sql,"artica_backup");
		
		while ($ligne = mysql_fetch_assoc($results)) {
			if(!$ipClass->isIPAddressOrRange($ligne["website_name"])){
				continue;
			}
			
			$FF[$ligne["website_name"]]=true;
			
		}
		
		while (list ($net,$none) = each ($FF)){
			$FF_1[]=$net;
				
		}
		
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} ".count($FF_1)." whitelisted items.\n";
		@file_put_contents("/etc/firehol/proxy_ssl_whitelist", @implode("\n", $FF_1));
		
	}
	
	
	
	
}