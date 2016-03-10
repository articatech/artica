<?php
if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}else{$GLOBALS["AS_ROOT"]=false;}}
include_once(dirname(__FILE__)."/class.squid.inc");
include_once(dirname(__FILE__)."/class.resolv.conf.inc");

class dnsmasq{
	var $configuration_file_datas;
	var $main_array;
	var $array_address;
	var $array_interface;
	var $array_listenaddress;
	var $array_ldap_addresses=array();
	var $array_mxhost;
	var $EnableDNSMASQLDAPDB=0;
	var $EnableDNSMASQOCSDB=1;
	
	var $ARTICA_ARRAY=array();

	function dnsmasq($noload=false){
		include_once(dirname(__FILE__) . '/class.ldap.inc');
		include_once(dirname(__FILE__).'/class.sockets.inc');
		$sock=new sockets();
		$this->EnableDNSMASQLDAPDB=intval($sock->GET_INFO("EnableDNSMASQLDAPDB"));
		$this->EnableDNSMASQOCSDB=$sock->GET_INFO("EnableDNSMASQOCSDB");
		
		
		
		if(!is_numeric($this->EnableDNSMASQOCSDB)){$this->EnableDNSMASQOCSDB=1;}
		
		$this->configuration_file_datas=$sock->GET_INFO("DnsMasqConfigurationFile");
		if(trim($this->configuration_file_datas)==null){
			$this->configuration_file_datas=$this->LoadConf();
			writelogs("DnsMasqConfigurationFile = null -> original conf = ".strlen($this->configuration_file_datas)." bytes",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		}
		
		$this->ARTICA_ARRAY=unserialize(base64_decode($sock->GET_INFO("DNSMasqArticaParams")));
		
		if(!$noload){
			$this->ParseConf();
			$this->cache_dns_hosts();
			$this->ldap_addesses();
			$this->ParseAddress();
			$this->ParseListenAddress();
			$this->ParseInterfaces();
			$this->Parsemxhost();
			$this->Mainunset();
		}
	}
	
	private function LoadConf(){
		if($GLOBALS["AS_ROOT"]){return @file_get_contents("/etc/dnsmasq.conf");}
		$sock=new sockets();
		return base64_decode($sock->getFrameWork('cmd.php?LoaddnsmasqConf=yes'));
	}
	
	
	function ParseConf(){
		$tpl=explode("\n",$this->configuration_file_datas);
		if(!is_array($tpl)){
			writelogs("tpl not an Array() !",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return null;
		}
		while (list ($index, $line) = each ($tpl) ){
			if(trim($line)==null){continue;}
			if(substr(trim($line),0,1)=="#"){continue;}
			if(preg_match("#^server=#", $line)){continue;}
			if(preg_match("#^local=#", $line)){continue;}
			if(preg_match("#^ptr-record=#", $line)){continue;}
			writelogs("Parsing $line",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			if(preg_match('#^([a-zA-Z\-\_]+)=(.+)#',$line,$regs)){
				if(trim($regs[1])=="EnableDNSMASQ"){continue;}
				$regs[1]=trim($regs[1]);
				$regs[2]=trim($regs[2]);
				$this->main_array[$regs[1]]=$regs[2];
				continue;
			}
			if(preg_match('#^([a-zA-Z\-\_]+)#',$line,$regs)){
					$this->main_array[trim($regs[1])]="yes";
			}
			
			
		}
		writelogs("Parsing ". count($tpl)." lines done and get an array of ".count($this->main_array)." items",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		
		if(!isset($this->main_array["dns-forward-max"])){$this->main_array["dns-forward-max"]=1000;}
		
		if($this->main_array["resolv-file"]==null){$this->main_array["resolv-file"]="/etc/dnsmasq.resolv.conf";}
		if($this->main_array["resolv-file"]=="/etc/resolv.conf"){$this->main_array["resolv-file"]="/etc/dnsmasq.resolv.conf";}
		if(intval($this->main_array["cache-size"])==0){$this->main_array["cache-size"]=1500;}
		if(intval($this->main_array["dns-forward-max"])==0){$this->main_array["dns-forward-max"]=1000;}
		
		
		
		if($this->main_array["domain"]==null){
			$resolv=new resolv_conf();
			if($resolv->MainArray["DOMAINS1"]<>null){$this->main_array["domain"]=$resolv->MainArray["DOMAINS1"];}
			
		}
		
		
		

	}
	function Mainunset(){
		unset($this->main_array["mxrecdomainfrom"]);
		unset($this->main_array["mxrecdomainto"]);
		unset($this->main_array["mxheight"]);
		unset($this->main_array["address"]);
		unset($this->main_array["listen-address"]);
		unset($this->main_array["interface"]);
		unset($this->main_array["mx-host"]);	
		
	}
	
	public function ParseAddress(){
		return;
		$tpl=explode("\n",$this->configuration_file_datas);
		if(!is_array($tpl)){return null;}
		$filter='#^address=/(.+)?/(.+)?#';
		while (list ($index, $line) = each ($tpl) ){
			if(preg_match($filter,$line,$regs)){
				if(isset($this->array_ldap_addresses[$regs[1]])){continue;}
				$this->array_address[$regs[1]]=$regs[2];
			}
			
		}
		
	}
	
function Parsemxhost(){
	$tpl=explode("\n",$this->configuration_file_datas);
		if(!is_array($tpl)){return null;}
		$filter='#^mx-host=(.+)?#';
		while (list ($index, $line) = each ($tpl) ){
			if(preg_match($filter,$line,$regs)){
				$list=$regs[1];
				$this->array_mxhost[]=$regs[1];	
				}
			}
		}			

	
	function ParseListenAddress(){
		$tpl=explode("\n",$this->configuration_file_datas);
		if(!is_array($tpl)){return null;}
		$filter='#^listen-address=(.+)?#';
		while (list ($index, $line) = each ($tpl) ){
			if(preg_match($filter,$line,$regs)){
				$list=$regs[1];
				if(strpos($list,",")>0){
					$this->array_listenaddress=explode(',',$list);
				}else{$this->array_listenaddress[]=$list;}
			}
		}	
	}
	function ParseInterfaces(){
		$tpl=explode("\n",$this->configuration_file_datas);
		if(!is_array($tpl)){return null;}
		$filter='#^interface=(.+)?#';
		while (list ($index, $line) = each ($tpl) ){
			if(preg_match($filter,$line,$regs)){
				$this->array_interface[]=$regs[1];
			}
			
		}
	}
	
	private function KeyOnlyArray(){
		$f["domain-needed"]=true;
		$f["expand-hosts"]=true;
		$f["bogus-priv"]=true;
		$f["filterwin2k"]=true;
		$f["filterwin"]=true;
		$f["strict-order"]=true;
		$f["no-resolv"]=true;
		$f["no-poll"]=true;
		$f["no-hosts"]=true;
		$f["no-negcache"]=true;
		$f["log-queries"]=true;
		return $f;
	}

	
	function SaveConf($OnlyReload=false){
		$sock=new sockets();
		$sock->SaveConfigFile(base64_encode(serialize($this->ARTICA_ARRAY)), "DNSMasqArticaParams");
		

		
		
		
		$onlykey=$this->KeyOnlyArray();
		while (list ($key, $value) = each ($this->main_array) ){
			if($value=="EnableDNSMASQ"){continue;}
			if($GLOBALS["VERBOSE"]){echo __FUNCTION__."() parsing \"$key\" value=$value\n";}
			if($key=="filterwin"){$key="filterwin2k";}
			if($value<>'nokey'){
				if($value=='justkey'){
					$conf=$conf . "$key\n";
					continue;
				}
				
				if(isset($onlykey[$key])){
					
					if($value=="no"){
						if($GLOBALS["VERBOSE"]){echo __FUNCTION__."() $key is a single key, value=$value\n";}
						continue;
					}
					$conf=$conf . "$key\n";
					continue;
				}
				
				$conf=$conf . "$key=$value\n";				
				
				
			}
			
		}
		
		//LOCAL DOMAINS
		if(isset($this->ARTICA_ARRAY["LOCALNET"])){
			if(is_array($this->ARTICA_ARRAY["LOCALNET"])){
				while (list ($domain, $enabled) = each ($this->ARTICA_ARRAY["LOCALNET"]) ){
					if($enabled==0){continue;}
					$conf=$conf . "local=/$domain/\n";
				}
			}
		}
		
			
		// DNS WPAD 
		
		if($GLOBALS["AS_ROOT"]){
			echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} WPAD = {$this->ARTICA_ARRAY["WPAD"]["ENABLE"]}\n";
		}			
			
		if($this->ARTICA_ARRAY["WPAD"]["ENABLE"]==1){
			$wpadhost="wpad.{$this->ARTICA_ARRAY["WPAD"]["HOST"]}";
			$wpadport=$this->ARTICA_ARRAY["WPAD"]["PORT"];
			$wpaduri=$this->ARTICA_ARRAY["WPAD"]["URI"];
			$conf=$conf . "txt-record=wpad,\"service: wpad:!http://$wpadhost:$wpadport/$wpaduri\"\n";
			$conf=$conf . "txt-record=wpad.$wpadhost,\"service: wpad:!http://$wpadhost:$wpadport/$wpaduri\"\n";
			$conf=$conf . "srv-host=wpad.tcp.$wpadhost,wpad.$wpadhost,$wpadport\n";
			$ips=explode(".", $this->ARTICA_ARRAY["WPAD"]["IP_ADDR"]);
			$conf=$conf . "ptr-record={$ips[3]}.{$ips[2]}.{$ips[1]}.{$ips[0]}.in-addr.arpa,\"wpad\"\n";
			$conf=$conf . "ptr-record={$ips[3]}.{$ips[2]}.{$ips[1]}.{$ips[0]}.in-addr.arpa,\"$wpadhost\"\n";
			$conf=$conf . "address=/$wpadhost/{$this->ARTICA_ARRAY["WPAD"]["IP_ADDR"]}\n";
			$conf=$conf . "address=/wpad/{$this->ARTICA_ARRAY["WPAD"]["IP_ADDR"]}\n";
		
		}
		
		
		//listen-address ---------------------------------------------------------------
		if(is_array($this->array_listenaddress)){
				while (list ($index, $value) = each ($this->array_listenaddress) ){if(trim($value)==null){continue;}$tt[]=$value;}
				if(count($tt)>0){
					$conf=$conf . "listen-address=" . implode(",",$tt) . "\n";
				}
			}
		
		//interfaces ---------------------------------------------------------------
		if(is_array($this->array_interface)){
			$nic=new system_nic();
		while (list ($index, $value) = each ($this->array_interface) ){
			if($value==null){continue;}
			$value=$nic->NicToOther($value);
			if(isset($ALDEFINT[$value])){continue;}
			$ALDEFINT[$value]=true;
			$conf=$conf . "interface=".$nic->NicToOther($value)."\n";
			
		}}		
		//address ---------------------------------------------------------------
		
		if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} ". count($this->array_address)." addresses\n"; }
		
		if(count($this->array_address)>0){
		reset($this->array_address);
		while (list ($index, $value) = each ($this->array_address) ){
			if($value==null){
				if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $index value = NULL invalid!\n";}
				continue;
			}
				
				
			if($index==null){
				if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} index is null\n";}
				continue;
			}
			
			if(isset($GLOBALS["ARRAY_ADRESSES_DONE"][$index])){continue;}
			$GLOBALS["ARRAY_ADRESSES_DONE"][$index]=true;
			$re=explode(".", $value);
			if(!$this->isHostValid($index)){continue;}
				
				
			$conf=$conf . "address=/$index/$value\n";
			$conf=$conf . "ptr-record={$re[3]}.{$re[2]}.{$re[1]}.{$re[0]}.in-addr.arpa,\"$index\"\n";
		}}
		
		$ldapAddresses=$this->ldap_addesses();
		if($GLOBALS["VERBOSE"]){echo "ldap_addesses:: return ".strlen($ldapAddresses)." Bytes\n";}
		if($ldapAddresses<>null){
			$conf=$conf . "$ldapAddresses\n";
		}
		
		$ocs_addresses=$this->ocs_addresses();		
		if($GLOBALS["VERBOSE"]){echo "ocs_addresses:: return ".strlen($ocs_addresses)." Bytes\n";}
		if($ocs_addresses<>null){
			$conf=$conf . "$ocs_addresses\n";
		}		
		
		
		//mx-host ---------------------------------------------------------------
		if(is_array($this->array_mxhost)){
			
		while (list ($index, $value) = each ($this->array_mxhost) ){
			if($value==null){continue;}
			if($index==null){continue;}
			$array[$value]=$value;
		}
		while (list ($index, $value) = each ($array) ){	
		if($value==null){continue;}
			$conf=$conf . "mx-host=$value\n";
		}}else{writelogs("mx-host : no array...",__CLASS__ . '/'. __FUNCTION__, __FILE__);	}
		//-----------------------------------------------------------------------------
		
		$sock=new sockets();
		
		
		
		if(!$GLOBALS["AS_ROOT"]){
			$sock->SaveConfigFile($conf."\n","DnsMasqConfigurationFile");
		}else{
			unset($GLOBALS["ARRAY_ADRESSES_DONE"]);
			if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Saving `/etc/dnsmasq.conf` {".__LINE__."}\n";}
			@file_put_contents("/etc/dnsmasq.conf", $conf."\n");
			@file_put_contents("/etc/artica-postfix/settings/Daemons/DnsMasqConfigurationFile", $conf."\n");
			@file_put_contents("/usr/share/artica-postfix/ressources/databases/dnsmasq.conf", $conf."\n");
			$unix=new unix();
			$dnsmasqbin=$unix->find_program("dnsmasq");
			$chmod=$unix->find_program("chmod");
			shell_exec("$chmod 770 /usr/share/artica-postfix/ressources/databases/dnsmasq.conf");
			if(is_file($dnsmasqbin)){
				$pid=$unix->PIDOF($dnsmasqbin);
				if(is_numeric($pid)){
					echo "Starting......: ".date("H:i:s")." dnsmasq reloading PID:`$pid`\n";
					$kill=$unix->find_program("kill");
					unix_system_HUP($pid);
				}
			}
			
		}
		
	}
	
private function cache_dns_hosts(){
	include_once(dirname(__FILE__)."/class.mysql.squid.builder.php");
	if(isset($GLOBALS["cache_dns_hosts21"])){return;}
	$GLOBALS["cache_dns_hosts"]=true;
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM dnsmasq_records";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){return;}
	while ($ligne = mysql_fetch_assoc($results)) {
		
		if(isset($GLOBALS["ARRAY_ADRESSES_DONE"][$ligne["hostname"]])){
			if($GLOBALS["VERBOSE"]){echo "ocs_addresses:: MYSQL -> {$ligne["name"]} already added...{{$GLOBALS["ARRAY_ADRESSES_DONE"][$ligne["name"]]}}\n";}
			continue;
		}
		
		$ipaddr=$ligne["ipaddr"];
		$hostname=$ligne["hostname"];
		$this->array_address[$hostname]=$ipaddr;
	}
	$GLOBALS["cache_dns_hosts21"]=true;
}	
	
	private function ocs_addresses(){
		include_once(dirname(__FILE__).'/class.mysql.inc');
		if($this->EnableDNSMASQOCSDB==0){if($GLOBALS["VERBOSE"]){echo "ocs_addresses:: OCS Database is not enabled...\n";}return;}
		$f=array();
		$sql="SELECT networks.IPADDRESS,hardware.name FROM networks,hardware WHERE 	networks.HARDWARE_ID=hardware.ID 
		AND networks.IPADDRESS!='0.0.0.0' AND networks.IPADDRESS REGEXP '^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$'";
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"ocsweb");
		if($GLOBALS["VERBOSE"]){if(!$q->ok){echo $q->mysql_error."\n";}}
		
		if($GLOBALS["VERBOSE"]){echo "[".__LINE__."] ocs_addresses:: MYSQL -> ".mysql_num_rows($results)." entries\n";}
		
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
				
				if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+\.#", $ligne["name"])){
					if($GLOBALS["VERBOSE"]){echo "[".__LINE__."] ocs_addresses:: MYSQL -> {$ligne["name"]} BAD entry\n";}
					continue;					
				}
			
				if(isset($GLOBALS["ARRAY_ADRESSES_DONE"][$ligne["name"]])){
					if($GLOBALS["VERBOSE"]){echo "[".__LINE__."] ocs_addresses:: MYSQL -> {$ligne["name"]} already added...{{$GLOBALS["ARRAY_ADRESSES_DONE"][$ligne["name"]]}}\n";}
					continue;
				}
				
				
				if($GLOBALS["VERBOSE"]){echo "[".__LINE__."] ocs_addresses:: MYSQL -> {$ligne["name"]}\n";}
				
				
				$GLOBALS["ARRAY_ADRESSES_DONE"][$ligne["name"]]=true;
				$arecord=$ligne["IPADDRESS"];
				$hostname=$ligne["name"];
				$rz=explode(".", $arecord);
				if($GLOBALS["VERBOSE"]){echo "PTR {$rz[3]}.{$rz[2]}.{$rz[1]}.{$rz[0]}.in-addr.arpa (OCS)\n";}
				$reverse["{$rz[3]}.{$rz[2]}.{$rz[1]}.{$rz[0]}.in-addr.arpa"]=$hostname;
				$f[]="address=/$hostname/$arecord";							
			
			
		}
		
		$q=new mysql_squid_builder();
		$results=$q->QUERY_SQL("SELECT `hostname`,`ipaddr` FROM dnsmasq_records");
		
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		
			if(isset($GLOBALS["ARRAY_ADRESSES_DONE"][$ligne["hostname"]])){
				if($GLOBALS["VERBOSE"]){echo "ocs_addresses:: MYSQL -> {$ligne["hostname"]} already added...{{$GLOBALS["ARRAY_ADRESSES_DONE"][$ligne["name"]]}}\n";}
				continue;
			}
			$GLOBALS["ARRAY_ADRESSES_DONE"][$ligne["hostname"]]=true;
			$arecord=$ligne["ipaddr"];
			$hostname=$ligne["hostname"];
			$rz=explode(".", $arecord);
			if($GLOBALS["VERBOSE"]){echo "PTR {$rz[3]}.{$rz[2]}.{$rz[1]}.{$rz[0]}.in-addr.arpa (DNSITEMS)\n";}
			$reverse["{$rz[3]}.{$rz[2]}.{$rz[1]}.{$rz[0]}.in-addr.arpa"]=$hostname;
			$f[]="address=/$hostname/$arecord";
				
				
		}		
		
		
		if($GLOBALS["VERBOSE"]){echo "ocs_addresses:: MYSQL -> ".count($f)." entries (". count($reverse)." reverses)\n";}
		
		if(isset($reverse)){
			if(count($reverse)>0){
				while (list ($a, $b) = each ($reverse) ){
					if(!$this->isHostValid($b)){continue;}
					$f[]="ptr-record=$a,\"$b\"";
				}
			}
		}		
		
		
	if(count($f)>0){return @implode("\n", $f);}
		
		
	}
	
	private function isHostValid($hostname){
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $hostname)){return false;}
		if(strpos($hostname, " ")>0){return false;}
		if(preg_match("#^\.#", $hostname)){return false;}
		return true;
	}
	
	
	public function ldap_addesses(){

		
		
		if($this->EnableDNSMASQLDAPDB==0){
			if($GLOBALS["VERBOSE"]){echo "EnableDNSMASQLDAPDB -> SKIP\n";}
			return;}
		
		if(count($this->array_interface)==0){
			include_once(dirname(__FILE__)."/class.system.network.inc");
			$net=new networking();
			while (list ($eth, $ip_addr) = each ($net->array_TCP) ){
				if($ip_addr=="127.0.0.1"){continue;}
				if($ip_addr=="0.0.0.0"){continue;}
				$array_interface[]=$ip_addr;
			}
		}else{
			$array_interface=$this->array_interface;
		}	
		
		$reverse_ip=$array_interface[0];
		
		if(count($this->array_ldap_addresses)>0){
			reset($this->array_ldap_addresses);
			while (list ($hostname, $arecord) = each ($this->array_ldap_addresses) ){
				$hostname=trim(strtolower($hostname));
				if(!$this->isHostValid($hostname)){continue;}
				
				$f[]="address=/$hostname/$arecord";	
				$re=explode(".", $arecord);
				//if($GLOBALS["VERBOSE"]){echo "PTR {$re[3]}.{$re[2]}.{$re[1]}.{$re[0]}.in-addr.arpa (LDAP)\n";}
				$reverse["{$re[3]}.{$re[2]}.{$re[1]}.{$re[0]}.in-addr.arpa"]=$hostname;
				
			}
			
			if(count($f)>0){
				
				if(isset($reverse)){
					if(count($reverse)>0){
						while (list ($a, $b) = each ($reverse) ){
							if(!$this->isHostValid($b)){continue;}
							$f[]="ptr-record=$a,\"$b\"";
						}
					}
				}
				return @implode("\n", $f);}
		}
		
		
		
		
		$ldap=new clladp();
		$pattern="(&(objectclass=dNSDomain2)(|(aRecord=*)(associatedDomain=*)(dc=*)))";
		$attr=array("associatedDomain","MacRecord","aRecord","sOARecord");
		$sr =ldap_search($ldap->ldap_connection,$ldap->suffix,$pattern,$attr);
		if($sr){
			$hash=ldap_get_entries($ldap->ldap_connection,$sr);
				for($i=0;$i<$hash["count"];$i++){
					$dn=$hash[$i]["dn"];
					$arecord=$hash[$i]["arecord"][0];
					$macrecord=$hash[$i]["macrecord"][0];
					$sOARecord=$hash[$i]["soarecord"][0];
					if($arecord=="127.0.0.1"){continue;}
					if($sOARecord<>null){continue;}
					if($arecord==null){continue;}
						
					for($z=0;$z<$hash[$i]["associateddomain"]["count"];$z++){
						$hostname=trim(strtolower($hash[$i]["associateddomain"][$z]));
						if(isset($GLOBALS["ARRAY_ADRESSES_DONE"][$hostname])){continue;}
						if(strpos($hostname, " ")>0){continue;}
						if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $hostname)){continue;}
						$this->array_ldap_addresses[$hostname]=$arecord;
						$GLOBALS["ARRAY_ADRESSES_DONE"][$hostname]=true;
						$rz=explode(".", $arecord);
						$reverse["{$rz[3]}.{$rz[2]}.{$rz[1]}.{$rz[0]}.in-addr.arpa"]=$hostname;
						$f[]="address=/$hostname/$arecord";				
					}
				}

			if(isset($reverse)){
				if(count($reverse)>0){
					while (list ($a, $b) = each ($reverse) ){
						if(!$this->isHostValid($b)){continue;}
						$f[]="ptr-record=$a,\"$b\"";
					}
				}
			}	
			
			
			
			
		}
		$reterned=null;
		
		if(count($f)>0){$reterned=@implode("\n", $f);}
		
		if($GLOBALS["VERBOSE"]){echo "ldap: return .....\n$reterned\n\n";}
		return $reterned;
		
	}
	
	
	public function SaveConfToServer(){$this->SaveConf();}
}

?>