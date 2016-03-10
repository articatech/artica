<?php
if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}else{$GLOBALS["AS_ROOT"]=false;}}
include_once(dirname(__FILE__)."/class.resolv.conf.inc");

class networking{
	var $array_conf;
	var $array_TCP;
	var $array_local_aliases;
	var $ldap_error;
	var $arrayNameServers;
	var $hostname;
	
	var $tcp_addr="0.0.0.0";
	var $wireless=false;
	var $netmask="0.0.0.0";
	var $gateway="0.0.0.0";
	var $linkup=false;
	
	function networking(){
			include_once(dirname(__FILE__) . '/class.tcpip.inc');
			if(isset($GLOBALS["__CLASS-networking"])){
			foreach (get_object_vars($GLOBALS["__CLASS-networking"]) as $key => $value){$this->$key = $value;}
			if($GLOBALS["DEBUG_MEM"]){
				if(function_exists("events")){events("Memory: class.system.network.inc -> networking class() -> ".round(((memory_get_usage()/1024)/1000),2) ." line:".__LINE__);}
				}
			return null;
			}		
		
		
		$this->LoadNameServers();
		$this->LoadIPs();
		
		$GLOBALS["__CLASS-networking"]=$this;
		$trace=debug_backtrace();
		$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
		writelogs("Loading success $called",__CLASS__.'/'.__FUNCTION__,__FILE__);
	}
	
	
  public function NETWORK_ALL_INTERFACES(){
  	if(!$GLOBALS["AS_ROOT"]){
  		$sock=new sockets();
  		$INTERFACES=unserialize(base64_decode($sock->getFrameWork("cmd.php?TCP_NICS_STATUS_ARRAY=yes")));
  		 
  	}else{
  		$unix=new unix();
  		$INTERFACES=$unix->NETWORK_ALL_INTERFACES();
  	}
  	return $INTERFACES;
  	
  }
	
  public function Local_interfaces($filter=false) {
    $results = array();
    $bufr=null;
   
    if(!$GLOBALS["AS_ROOT"]){
    	$sock=new sockets();
    	$INTERFACES=unserialize(base64_decode($sock->getFrameWork("cmd.php?TCP_NICS_STATUS_ARRAY=yes")));
    	
    }else{
    	$unix=new unix();
    	$INTERFACES=$unix->NETWORK_ALL_INTERFACES();
    }
    
    while (list ($dev_name, $ligneAR) = each ($INTERFACES) ){
    	
    	if($GLOBALS["VERBOSE"]){echo "<span style='color:#d32d2d'>Receive interface name:$dev_name / {$ligneAR["IPADDR"]}</span><br>\n";}
    	if($dev_name=="lo"){continue;}
    	
    	
    	$INTERFACEZ[$dev_name]=true;
  		
         if($filter){
          	if(preg_match("#^veth#",trim($dev_name))){continue;}
          	if(preg_match("#^sit#",trim($dev_name))){continue;}
          	if(preg_match("#^lxc#",trim($dev_name))){continue;}
          	if(preg_match("#^lo#",trim($dev_name))){continue;}
          	if(preg_match("#^br[0-9]+#",trim($dev_name))){continue;}
          	if(preg_match("#^ham[0-9]+#",trim($dev_name))){continue;}
          	
          }
          $results[trim($dev_name)] = trim($dev_name);
        
      }
    
    
    return $results;
  }	
  
	 private function rfts($strFileName, &$strRet, $intLines = 0, $intBytes = 4096, $booErrorRep = true) {
	  $error = "";
	  $strFile = "";
	  $intCurLine = 1;
	  if (is_file($strFileName)) {
	    if ($fd = fopen($strFileName, 'r')) {
	      while (!feof($fd)) {
	        $strFile.= fgets($fd, $intBytes);
	        if ($intLines <= $intCurLine && $intLines != 0) {
	          break;
	        } else {
	          $intCurLine++;
	        }
	      }
	      fclose($fd);
	      $strRet = $strFile;
	    } else {
	      if ($booErrorRep) {
	        writelogs('fopen(' . $strFileName . ') file can not read',__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
	      }
	      return false;
	    }
	  } else {
	    if ($booErrorRep) {
	      writelogs('file_exists(' . $strFileName . ') the file does not exist on your machine',__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
	    }
	    return false;
	  }
	  return true;
	}  
	

	function ALL_IPS_GET_ARRAY(){
		include_once(dirname(__FILE__) . '/class.sockets.inc');
		if(isset($GLOBALS["ALL_IPS_GET_ARRAY"])){if(is_array($GLOBALS["ALL_IPS_GET_ARRAY"])){return $GLOBALS["ALL_IPS_GET_ARRAY"];}}
		if(isset($GLOBALS["CLASS_SOCKETS"])){$sock=$GLOBALS["CLASS_SOCKETS"];}else{$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}
		
	
		$IPBANS=unserialize(base64_decode($sock->GET_INFO("ArticaIpListBanned")));		
		//print_r($IPBANS);
		if($GLOBALS["AS_ROOT"]){
			$unix=new unix();
			$array=$unix->ifconfig_all_ips();
		}else{
			$array=unserialize(base64_decode($sock->getFrameWork("cmd.php?ifconfig-all-ips=yes")));
		}
		
		if(is_array($array)){
			while (list ($ip, $line) = each ($array) ){
				if(trim($line)==null){continue;}
				if(isset($IPBANS[trim($line)])){continue;}
				$ARRAY_IP[trim($line)]=trim($line);
			}
		}
		$GLOBALS["ALL_IPS_GET_ARRAY"]=$ARRAY_IP;
		return $ARRAY_IP;
	}
	
	
	function LoadIPs(){
		include_once(dirname(__FILE__) . '/class.sockets.inc');
		if(!class_exists("usersMenus")){include_once(dirname(__FILE__) . '/class.users.menus.inc');}
		$users=new usersMenus();
		if(isset($GLOBALS["CLASS_SOCKETS"])){$sock=$GLOBALS["CLASS_SOCKETS"];}else{$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}
		$this->array_TCP=$users->TCP_ARRAY;
		if($GLOBALS["AS_ROOT"]){
			if(class_exists('unix')){
				$unix=new unix();
				$this->hostname=$unix->FULL_HOSTNAME();
			}
		}else{
			$this->hostname=trim($sock->getFrameWork('cmd.php?full-hostname=yes'));
		}
		
	}
	
	public function SaveResolvconf($targetFile){
			
		
	}
	
	public function checkIP($ip){
		$cIP = ip2long($ip);
		$fIP = long2ip($cIP);
		if($fIP=="0.0.0.0"){return false;}
		return true;
		}	
		
	private function DNSLIST_AS_ROOT(){
		exec("/usr/share/artica-postfix/bin/artica-install --local-dns 2>&1",$results);
		return implode("",$results);
	}
	
	
		
	
	function LoadNameServers(){
		include_once(dirname(__FILE__) . '/class.sockets.inc');
		if(isset($GLOBALS["CLASS_SOCKETS"])){$sock=$GLOBALS["CLASS_SOCKETS"];}else{$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}
		if($GLOBALS["AS_ROOT"]){$dns=$this->DNSLIST_AS_ROOT();}else{
			$dns=$sock->getFrameWork('cmd.php?dnslist=yes');
			if($GLOBALS["VERBOSE"]){echo "cmd.php?dnslist=yes -> $dns in ".__CLASS__.'/'.__FUNCTION__. " Line:".__LINE__."\n";}
		}
		$dnslist=explode(";",$dns);
		$ip=new IP();
		if(is_array($dnslist)){
			while (list ($num, $val) = each ($dnslist) ){
				$val=trim(strtolower($val));
				if($val<>null){
					//Check if is a valid tcp/ip address.
					if($ip->isValid($val)){
						$arr[$val]=trim($val);
					}
					
				}}
			}
			
			if(!isset($arr)){return;}			
		    if(is_array($arr)){
				while (list ($num, $val) = each ($arr) ){
					if($ip->isValid($val)){
					$this->arrayNameServers[]=$val;
					}
				}
		   	}
		}
	
	
	function ifconfig($nic){
		include_once(dirname(__FILE__) . '/class.sockets.inc');
		if(isset($GLOBALS["CLASS_SOCKETS"])){
			$sock=$GLOBALS["CLASS_SOCKETS"];}
		else{
			$GLOBALS["CLASS_SOCKETS"]=new sockets();
			$sock=$GLOBALS["CLASS_SOCKETS"];
		}
		if(!$GLOBALS["AS_ROOT"]){
			if($GLOBALS["VERBOSE"]){echo "<strong style='color:blue'>cmd.php?nicstatus=$nic</strong><br>\n";}
			$nicinfos=$sock->getFrameWork("cmd.php?nicstatus=$nic");
		}else{
			$nicinfos=$this->GetNicStatusAsRoot($nic);
			
		}
		
		$tbl=explode(";",$nicinfos);
		$this->tcp_addr=$tbl[0];
		$this->mac_addr=$tbl[1];
		$this->netmask=$tbl[2];
		$this->gateway=$tbl[4];
		
		$ipBin="/bin/ip";
		if(!is_file($ipBin)){
			$ipBin="/sbin/ip";
		}
		
		exec("$ipBin link show $nic 2>&1",$results);
		while (list ($index, $line) = each ($results) ){
			if(preg_match("#LOWER_UP#", $line)){
				$this->linkup=true;
			}
		}
		
		if($this->netmask=="0.0.0.0"){
			$results=array();
			exec("$ipBin addr show $nic 2>&1",$results);
			while (list ($index, $line) = each ($results) ){
				if(preg_match("#inet\s+([0-9\.]+)\/([0-9]+)\s+#", $line,$re)){
					$net=$re[1]."/".$re[2];
					$results2=array();
					exec("/usr/share/artica-postfix/bin/ipcalc $net 2>&1");
					while (list ($index, $line) = each ($results2) ){
						if(preg_match("#Netmask:\s+([0-9\.]+)#", $line,$re)){$this->netmask=$re[1];break;}
						
					}
					break;
				}
			}
		}
		
		
		if(trim($tbl[5])=='yes'){$this->wireless=true;}else{$this->wireless=false;}
			
		
		}
		
		function BuildResolvConf(){
			$resolv=new resolv_conf();
			if($GLOBALS["AS_ROOT"]){
				@file_put_contents("/etc/resolv.conf", $resolv->build());
			}	
		}
		
	function nameserver_add($name){
		$this->BuildResolvConf();
		}
		
	function nameserver_delete($name){
		$this->BuildResolvConf();
	}
		
		private function GetNicInfosAsRoot($nic){
			if(isset($GLOBALS["GetNicInfosAsRoot$nic"])){
				if($GLOBALS["VERBOSE"]){echo "*** GetNicInfosAsRoot($nic) *** \n{$GLOBALS["GetNicInfosAsRoot$nic"]}\n";}
				return $GLOBALS["GetNicInfosAsRoot$nic"];
			}
			if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
			$unix=new unix();
			
			$MAIN=$unix->NETWORK_ALL_INTERFACES();
			$f[]="BOOTPROTO=";
			$f[]="METHOD=debian";
			$f[]="DEVICE=$nic";
			$f[]="MAC={$MAIN[$nic]["MAC"]}";
			$datas=trim(@implode("\n",$f));	
			if($GLOBALS["VERBOSE"]){echo "*** GetNicInfosAsRoot($nic) *** \n$datas\n";}
			$GLOBALS["GetNicInfosAsRoot$nic"]=$datas;
			return $datas;
		}
		
		private function GetNicStatusAsRoot($nic){
			if(isset($GLOBALS["GetNicStatusAsRoot$nic"])){return $GLOBALS["GetNicStatusAsRoot$nic"];}
			if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
			$unix=new unix();
			$ALLARRAY=$unix->NETWORK_ALL_INTERFACES();
			$outputz[]=$ALLARRAY[$_GET["nicstatus"]]["IPADDR"];
			$outputz[]=$ALLARRAY[$_GET["nicstatus"]]["MAC"];
			$outputz[]=$ALLARRAY[$_GET["nicstatus"]]["NETMASK"];
			$outputz[]=$ALLARRAY[$_GET["nicstatus"]]["SCOPE"];
			$outputz[]=$ALLARRAY[$_GET["nicstatus"]]["GATEWAY"];
			$outputz[]="no";
			$outputz[]=$ALLARRAY[$_GET["nicstatus"]]["STATE"];
			$sortie=@implode(";",$outputz);
			$GLOBALS["GetNicStatusAsRoot$nic"]=$sortie;
			return $sortie;
		}		
		
	
	function GetNicInfos($nic){
			if($GLOBALS["VERBOSE"]){echo "*** GetNicInfos($nic) *** \n";}
			if($nic==null){return;}
			include_once(dirname(__FILE__) . '/class.sockets.inc');
			if(isset($GLOBALS["CLASS_SOCKETS"])){
				$sock=$GLOBALS["CLASS_SOCKETS"];}else{$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}
			
			if(!isset($GLOBALS["NIC_INFOS_$nic"])){
				if($GLOBALS["AS_ROOT"]){$GLOBALS["NIC_INFOS_$nic"]=$this->GetNicInfosAsRoot($nic);}else{$GLOBALS["NIC_INFOS_$nic"]=$sock->getFrameWork("cmd.php?nic-infos=$nic");}
			}
			$datas=$GLOBALS["NIC_INFOS_$nic"];
			if($GLOBALS["VERBOSE"]){echo "*** GetNicInfos($nic) *** \n$datas\n";}
			
			$tbl=explode("\n",$datas);
			if(!is_array($tbl)){return array();}
			$array=array();
			while (list ($index, $ligne) = each ($tbl) ){
				$ligne=str_replace("'","",$ligne);
			    if(preg_match("#route add -net\s+([0-9\.]+)\s+netmask\s+([0-9\.]+)\s+gw\s+([0-9\.]+)#",$ligne,$re)){$array["ROUTES"][$re[1]]=array("NETMASK"=>$re[2],"GATEWAY"=>$re[3]);continue;}
			    if(preg_match("#route add -net\s+([0-9\.]+)\s+netmask\s+([0-9\.]+)\s+dev\s+(.+)#",$ligne,$re)){$array["ROUTES"][$re[1]]=array("NETMASK"=>$re[2],"DEV"=>$re[3]);continue;}
			    if(preg_match("#route add default gw ([0-9\.]+)#",$ligne,$re)){$array["ROUTES"]["default"]=array("GATEWAY"=>$re[1]);continue;}
			    if(preg_match('#([a-zA-Z0-9\-\_]+)=(.+)#',$ligne,$reg)){$array[$reg[1]]=$reg[2];}
				
			}
			
		return $this->GetInfosReturnDefault($array,$nic);
		}
		
	function default_network(){
		if($this->array_TCP){
		while (list ($nic, $ip) = each ($this->array_TCP) ){
			if(preg_match("#^vmnet#",$nic)){continue;}
			if($ip==null){continue;}
			if($ip=="0.0.0.0"){continue;}
			return $ip;
		}}
		
	}
	
	function GetInfosReturnDefault($array,$nic=null){
		$default_addr=null;
		$default_netmask=null;
		$default_gateway=null;
		$bradcast_default=null;
		if(count($this->arrayNameServers)==0){$this->LoadNameServers();}
		if($nic<>null){
			include_once(dirname(__FILE__) . '/class.sockets.inc');
			if(isset($GLOBALS["CLASS_SOCKETS"])){$sock=$GLOBALS["CLASS_SOCKETS"];}else{$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}
			if(!isset($GLOBALS["NIC_STATUS_$nic"])){if($GLOBALS["AS_ROOT"]){$GLOBALS["NIC_STATUS_$nic"]=$this->GetNicStatusAsRoot($nic);}else{$GLOBALS["NIC_STATUS_$nic"]=$sock->getFrameWork("cmd.php?nicstatus=$nic");}}
			$nicinfos=$GLOBALS["NIC_STATUS_$nic"];
			$tbl=explode(";",$nicinfos);
			$default_addr=$tbl[0];
			$default_netmask=$tbl[2];
			$default_gateway=$tbl[4];
			$bradcast_default=$tbl[3];
		}
				
		if(trim($default_addr)==null){$default_addr="0.0.0.0";}
		if(trim($default_netmask)==null){$default_netmask="0.0.0.0";}
		if(trim($default_gateway)==null){$default_gateway="0.0.0.0";}
		
		if($bradcast_default==null){
			if(preg_match("#([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#",$default_addr,$re)){
				$bradcast_default="{$re[1]}.{$re[2]}.{$re[3]}.255";
			}
		}
		
		if(!isset($array["ONBOOT"])){$array["ONBOOT"]=null;}
		if(!isset($array["METRIC"])){$array["METRIC"]=null;}
		if(!isset($array["NEEDHOSTNAME"])){$array["NEEDHOSTNAME"]=null;}
		if(!isset($array["DHCP_CLIENT"])){$array["DHCP_CLIENT"]=null;}
		if(!isset($array["PEERDNS"])){$array["PEERDNS"]=null;}
		if(!isset($array["PEERYP"])){$array["PEERYP"]=null;}
		if(!isset($array["PEERNTPD"])){$array["PEERNTPD"]=null;}
		if(!isset($array["GATEWAY"])){$array["GATEWAY"]=null;}
		if(!isset($array["BROADCAST"])){$array["BROADCAST"]=null;}
		if(!isset($array["IPADDR"])){$array["IPADDR"]=null;}
		if(!isset($array["NETMASK"])){$array["NETMASK"]=null;}
		if(!isset($array["BOOTPROTO"])){$array["BOOTPROTO"]=null;}
		if(!isset($array["ipv6"])){$array["ipv6"]=null;}
		
		
		
		
		if($array["ONBOOT"]==null){$array["ONBOOT"]="yes";}
		if($array["METRIC"]==null){$array["METRIC"]="10";}
		if($array["NEEDHOSTNAME"]==null){$array["NEEDHOSTNAME"]="yes";}	
		if($array["DHCP_CLIENT"]==null){$array["DHCP_CLIENT"]="dhclient";}	
		if($array["PEERDNS"]==null){$array["PEERDNS"]="yes";}	
		if($array["PEERYP"]==null){$array["PEERYP"]="no";}	
		if($array["PEERNTPD"]==null){$array["PEERNTPD"]="no";}		
		if($array["GATEWAY"]==null){$array["GATEWAY"]=$default_gateway;}
		if($array["IPADDR"]==null){$array["IPADDR"]=$default_addr;}
		if($array["NETMASK"]==null){$array["NETMASK"]=$default_netmask;}
		if($array["BOOTPROTO"]==null){$array["BOOTPROTO"]="static";}
		if($array["BROADCAST"]==null){$array["BROADCAST"]=$bradcast_default;}
		
		if(preg_match("#(.+?)\/([0-9]+)#",$array["IPADDR"],$re)){
			$array["IPADDR"]=$re[1];
			if($re[2]==24){$array["NETMASK"]="255.255.255.0";}
		}
		if(isset($array["dns-nameservers"])){
			if($array["dns-nameservers"]<>null){
				$t=explode(" ",$array["dns-nameservers"]);
				$array["NAMESERVERS"]=$t;
			}else{
				$array["NAMESERVERS"]=$this->arrayNameServers;;
			}
		}
		return $array;		
		
	}
	
	
function route_shouldbe($ip_start,$mask=null){
	
	$ip=$ip_start;
	if(preg_match("#([0-9]+)$#",$ip,$re)){
		$calc_ip=$re[1].".0.0.0";
		$calc_ip_end=$re[1].".255.255.255";
	}
	
	if(preg_match("#([0-9]+)\.([0-9]+)$#",$ip,$re)){
		$calc_ip=$re[1].".{$re[2]}.0.0";
		$calc_ip_end=$re[1].".{$re[2]}.255.255";
	}
	
	if(preg_match("#([0-9]+)\.([0-9]+)\.$#",$ip,$re)){
		$calc_ip=$re[1].".{$re[2]}.0.0";
		$calc_ip_end=$re[1].".{$re[2]}.255.255";
	}	

	if(preg_match("#([0-9]+)\.([0-9]+)\.([0-9]+)$#",$ip,$re)){
		$calc_ip=$re[1].".{$re[2]}.{$re[3]}.0";
		$calc_ip_end=$re[1].".{$re[2]}.{$re[3]}.255";
	}	
	
	if(preg_match("#([0-9]+)\.([0-9]+)\.([0-9]+)\.$#",$ip,$re)){
		$calc_ip=$re[1].".{$re[2]}.{$re[3]}.0";
		$calc_ip_end=$re[1].".{$re[2]}.{$re[3]}.255";
	}	

	if(preg_match("#([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)$#",$ip,$re)){
		$calc_ip=$re[1].".{$re[2]}.{$re[3]}.0";
		$calc_ip_end=$re[1].".{$re[2]}.{$re[3]}.255";
	}		

	
	
	if($mask<>null){
		if(preg_match("#([0-9]+)$#",$mask,$re)){
			$newmask=$re[1].".0.0.0";
		}
	
	if(preg_match("#([0-9]+)\.([0-9]+)$#",$mask,$re)){
		$newmask=$re[1].".{$re[2]}.0.0";
	}
	
		if(preg_match("#([0-9]+)\.([0-9]+)\.$#",$mask,$re)){
		$newmask=$re[1].".{$re[2]}.0.0";
	}	

	if(preg_match("#([0-9]+)\.([0-9]+)\.([0-9]+)$#",$mask,$re)){
		$newmask=$re[1].".{$re[2]}.{$re[3]}.0";
	}

	if(preg_match("#([0-9]+)\.([0-9]+)\.([0-9]+)\.$#",$mask,$re)){
		$newmask=$re[1].".{$re[2]}.{$re[3]}.0";
	}		
		

	if(preg_match("#([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)$#",$mask,$re)){
		$newmask=$re[1].".{$re[2]}.{$re[3]}.{$re[4]}";
	}		
		
	}
	
	$ip=new IP();
	
	if($newmask<>null){
		return $ip->maskTocdir($calc_ip, $newmask);
	}
	
	
	
	$cdir=$ip->ip2cidr($calc_ip,$calc_ip_end);
	$arr=$ip->parseCIDR($cdir);
	$rang=$arr[0];
	$netbit=$arr[1];
	$ipv=new ipv4($calc_ip,$netbit);
	return "<strong>$cdir {$ipv->address()} - {$ipv->netmask()}</strong>"; 	
	
}

	

	
	
	
	
	
}


class ifconfig{
	
	var $main_array=array();
	
	function ifconfig(){
		include_once(dirname(__FILE__) . '/class.sockets.inc');
		include_once(dirname(__FILE__) . '/class.ini.inc');
		if(isset($GLOBALS["CLASS_SOCKETS"])){$sock=$GLOBALS["CLASS_SOCKETS"];}else{$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}
		$ini=new Bs_IniHandler();
		$datas=$sock->getfile('netcardsinfo');
		$ini->loadString($datas);
		$this->main_array=$ini->_params;
		
		
	}
	
	
	
	
}

function VirtualNicInfosIPaddr($ID){
	if(!is_numeric($ID)){return;}
	$sql="SELECT * FROM nics_virtuals WHERE ID=$ID";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	$eth="{$ligne["nic"]}:{$ligne["ID"]}";
	return array("IPADDR"=>$ligne["ipaddr"],"ETH"=>$eth);
	
}

class MACAddress {
    private $_macPrefix = '00-';
    private $_macORGPrefix = '00-50-56-';
    private $_macAddress;
    private $_macChars;
    public $separator="-";
    public $MAC1;
    public $MAC2;
    public $MAC3;
    public $MAC4;
    public $MAC5;
    public $MAC6;

     public function _generateMAC(){
     	$this->MAC1=str_replace("-",$this->_macPrefix,$this->_macPrefix);
        $this->_macChars = md5(uniqid(mt_rand(), TRUE));
        $this->MAC2=substr($this->_macChars, 0,2);
        $this->MAC3=substr($this->_macChars, 8,2);
        $this->MAC4=substr($this->_macChars, 12,2);
        $this->MAC5=substr($this->_macChars, 16,2);
        $this->MAC6=substr($this->_macChars, 20,2);
            
        
        $this->_macAddress = substr($this->_macChars, 0,2) . '-';
        $this->_macAddress .= substr($this->_macChars, 8,2) . '-';
        $this->_macAddress .= substr($this->_macChars, 12,2) . '-';
        $this->_macAddress .= substr($this->_macChars, 16,2) . '-';
        $this->_macAddress .= substr($this->_macChars, 20,2);
        return "$this->MAC1$this->MAC2$this->separator$this->MAC3$this->separator$this->MAC4$this->separator$this->MAC5$this->separator$this->MAC6";
     }

     public function _generateMAC2(){
     	$this->_macORGPrefix=str_replace("-",$this->separator,$this->_macORGPrefix);
        $this->_macChars = md5(uniqid(mt_rand(), TRUE));
        $this->MAC4=substr($this->_macChars, 0,2);
        $this->MAC5=substr($this->_macChars, 8,2);
        $this->MAC6=substr($this->_macChars, 12,2);
        $this->_macAddress = "$this->_macORGPrefix$this->MAC4$this->separator$this->MAC5$this->separator$this->MAC6";
        return $this->_macAddress;
     }
} 

class networkscanner{
	var $networklist=array();
	var $DefaultNetworkList=array();
	var $Networks_disabled=array();
	
	
	function networkscanner(){
		$sock=new sockets();
		$datas=$sock->GET_INFO('NetworkScannerMasks');
		$tbl=explode("\n",$datas);
		$disabled=$sock->GET_INFO('NetworkScannerMasksDisabled');
		
		
		while (list ($num, $maks) = each ($tbl) ){
		if(trim($maks)==null){continue;}
			$arr[trim($maks)]=trim($maks);
		}
		
	if(is_array($arr)){
			while (list ($num, $net) = each ($arr)){
				$this->networklist[]=$net;
			}
		}

	
	$tbl=explode("\n",$disabled);	
	if(is_array($tbl)){
		while (list ($num, $maks) = each ($tbl) ){
			if(trim($maks)==null){continue;}
			$this->Networks_disabled[$maks]=true;
		}
	}
		
		
		$this->builddefault();
		
	}
	
	function disable_net($net){
		$sock=new sockets();
		$disabled=$sock->GET_INFO('NetworkScannerMasksDisabled');
		$disabled=$disabled."\n".$net;
		$sock=new sockets();
		$sock->SaveConfigFile($disabled,"NetworkScannerMasksDisabled");
	}
	
	function enable_net($net){
		$sock=new sockets();
		$disabled=$sock->GET_INFO('NetworkScannerMasksDisabled');
		$tbl=explode("\n",$disabled);	
		if(is_array($tbl)){
			while (list ($num, $maks) = each ($tbl) ){
				if(trim($maks)==null){continue;}
				$Networks_disabled[$maks]=$maks;
			}
		}

		unset($Networks_disabled[$net]);
		if(is_array($Networks_disabled)){
			while (list ($num, $maks) = each ($Networks_disabled) ){
				if(trim($maks)==null){continue;}
				$conf=$conf.$maks."\n";
			}
		}
		
		$sock->SaveConfigFile($conf,"NetworkScannerMasksDisabled");
		
		
		
	}
	
	
	function save(){
		if(is_array($this->networklist)){
			reset($this->networklist);
			while (list ($num, $maks) = each ($this->networklist)){
				if(trim($maks)==null){continue;}
				$arr[trim($maks)]=trim($maks);
				}
			}
		
		if(is_array($arr)){
			
			while (list ($num, $net) = each ($arr)){
				$conf=$conf . "$net\n";
			}
		}
		echo $conf;
		$sock=new sockets();
		$sock->SaveConfigFile($conf,"NetworkScannerMasks");
		$sock->getFrameWork("services.php?restart-ipband=yes");
		$sock->DeleteCache();
		
	}
	
	function builddefault(){
		
		$net=new networking();
		$cip=new IP();
		while (list ($num, $ip) = each ($net->array_TCP)){
			if(preg_match('#([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#',$ip,$re)){
				$ip_start="{$re[1]}.{$re[2]}.{$re[3]}.0";
				$ip_end="{$re[1]}.{$re[2]}.{$re[3]}.255";
				$cdir=$cip->ip2cidr($ip_start,$ip_end);
				if(trim($cdir)<>null){
					$this->DefaultNetworkList[trim($cdir)]=true;
					$this->networklist[]=$cdir;
				}
			}
			
		}
		
		
		
	}
	
	
		
	
}


?>