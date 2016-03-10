<?php
include_once(dirname(__FILE__).'/class.system.network.inc');
include_once(dirname(__FILE__).'/class.system.nics.inc');

class FireQOS{
	private $CONF=array();
	
	
	function FireQOS(){
		
		
	}

	function build(){
		$q=new mysql();
		$sql="SELECT `Interface` FROM `nics` WHERE `enabled`=1 AND `FireQOS`=1";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$this->build_interface($ligne["Interface"]);	
			
		}
		
		@unlink("/etc/firehol/fireqos.conf");
		if(count($this->CONF)>0){
			@file_put_contents("/etc/firehol/fireqos.conf", @implode("\n", $this->CONF));
		}
	}
	
	private function build_interface($eth){
		
		$nic=new system_nic($eth);
		$LINKTYPE=null;
		
		
		
		
		if($nic->ModemType<>null){
			$ModemType=explode(":",$nic->ModemType);
			$adsl=$ModemType[0];
			$zType=$ModemType[1]; 
			$LINKTYPE="$adsl remote $zType";
		}else{
			$LINKTYPE="ethernet";
		}
		
		$InputSpeed=round($nic->InputSpeed*0.9);
		
		$this->CONF[]="interface $eth $eth-in input rate $nic->InputSpeed{$nic->SpeedUnit} $LINKTYPE";
		$this->build_classes("$eth-in");
		$this->CONF[]="interface $eth $eth-out output rate $nic->OutputSpeed{$nic->SpeedUnit} $LINKTYPE";

		
	}
	
	private function build_classes($eth){
		$q=new mysql();
		$ayDscp = array(0 => '0x00',8 => '0x20',10 => '0x28',12 => '0x30',14 => '0x38',16 => '0x40',18 => '0x48',20 => '0x50',22 => '0x58',24 => '0x60',26 => '0x68',28 => '0x70',30 => '0x78',32 => '0x80',34 => '0x88',36 => '0x90',38 => '0x98',40 => '0xA0',46 => '0xB8',48 => '0xC0',56 => '0xE0');
		$results=$q->QUERY_SQL("SELECT * FROM qos_containers WHERE eth='$eth' ORDER by prio AND enabled=1","artica_backup");
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$name=$ligne["name"];
			$commit=null;
			if($ligne["rate"]>0){
				$commit=" commit {$ligne["rate"]}{$ligne["rate_unit"]}";
			}
			if($ligne["ceil"]>0){
				$ceil=" ceil {$ligne["ceil"]}{$ligne["ceil_unit"]}";
				
			}
			
			$this->CONF[]="\tclass $name{$commit}{$ceil}";
			$this->build_rules($ligne["ID"]);
			
			// TOS values.
			$dscp=unserialize($ligne["dscp"]);
			if(count($dscp)>0){
				while (list ($tos, $justnone) = each ($dscp) ){
					if(!is_numeric($tos)){continue;}
					if(!isset($ayDscp[$tos])){continue;}
					$tosval=$ayDscp[$tos];
					$this->CONF[]="\t\tmatch tos $tosval";
						
					
				}
				
			}
			
			
		}
		
		
	}
	
	
	private function build_rules($aclid){
		$q=new mysql_squid_builder();
		$sql="SELECT qos_sqacllinks.gpid,qos_sqacllinks.negation,
		qos_sqacllinks.zOrder,qos_sqacllinks.zmd5 as mkey,
		webfilters_sqgroups.* FROM qos_sqacllinks,webfilters_sqgroups
		WHERE qos_sqacllinks.gpid=webfilters_sqgroups.ID 
		AND webfilters_sqgroups.enabled=1
		AND qos_sqacllinks.aclid=$aclid
		ORDER BY qos_sqacllinks.zOrder";
		$results=$q->QUERY_SQL($sql);
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$gpid=$ligne["gpid"];
			$GroupType=$ligne["GroupType"];
			$this->build_items($gpid,$GroupType);
		}
		
		
	}
	
	
	
	private function build_items($gpid,$GroupType){
		
		if($GroupType=="teamviewer"){
			include_once(dirname(__FILE__)."/ressources/class.products-ip-ranges.inc");
			$products_ip_ranges=new products_ip_ranges();
			$array=$products_ip_ranges->teamviewer_networks();
			if($GLOBALS["VERBOSE"]){echo "teamviewer_networks ->".count($array)." items [".__LINE__."]\n";}
			while (list ($a, $b) = each ($array) ){
				$this->CONF[]="\t\tmatch host $b";
			}
		return;}
		
		
		if($GroupType=="office365"){
			include_once(dirname(__FILE__)."/ressources/class.products-ip-ranges.inc");
			$products_ip_ranges=new products_ip_ranges();
			$array=$products_ip_ranges->office365_networks();
			if($GLOBALS["VERBOSE"]){echo "office365 ->".count($array)." items [".__LINE__."]\n";}
			while (list ($a, $b) = each ($array) ){
				$this->CONF[]="\t\tmatch host $b";
			}
		return;}		
		
		if($GroupType=="skype"){
			include_once(dirname(__FILE__)."/ressources/class.products-ip-ranges.inc");
			$products_ip_ranges=new products_ip_ranges();
			$array=$products_ip_ranges->skype_networks();
			if($GLOBALS["VERBOSE"]){echo "skype_networks ->".count($array)." items [".__LINE__."]\n";}
			while (list ($a, $b) = each ($array) ){
				$this->CONF[]="\t\tmatch host $b";
			}
		return;}
		
		if($GroupType=="youtube"){
			include_once(dirname(__FILE__)."/ressources/class.products-ip-ranges.inc");
			$products_ip_ranges=new products_ip_ranges();
			$array=$products_ip_ranges->youtube_networks();
			if($GLOBALS["VERBOSE"]){echo "youtube_networks ->".count($array)." items [".__LINE__."]\n";}
			while (list ($a, $b) = each ($array) ){
				$this->CONF[]="\t\tmatch host $b";
			}
			return;}		

		
		if($GroupType=="google"){
			include_once(dirname(__FILE__)."/ressources/class.products-ip-ranges.inc");
			$products_ip_ranges=new products_ip_ranges();
			$array=$products_ip_ranges->google_networks();
			if($GLOBALS["VERBOSE"]){echo "google_networks ->".count($array)." items [".__LINE__."]\n";}
					while (list ($a, $b) = each ($array) ){
				$this->CONF[]="\t\tmatch host $b";
			}
		return;}
		
		if($GroupType=="google_ssl"){
			include_once(dirname(__FILE__)."/ressources/class.products-ip-ranges.inc");
			$products_ip_ranges=new products_ip_ranges();
			$array=$products_ip_ranges->google_ssl();
			if($GLOBALS["VERBOSE"]){echo "google_networks ->".count($array)." items [".__LINE__."]\n";}
			while (list ($a, $b) = each ($array) ){
				$this->CONF[]="\t\tmatch host $b";
			}
		return;}
		
		if($GroupType=="dropbox"){
			include_once(dirname(__FILE__)."/ressources/class.products-ip-ranges.inc");
			$products_ip_ranges=new products_ip_ranges();
			$array=$products_ip_ranges->dropbox_networks();
			if($GLOBALS["VERBOSE"]){echo "dropbox_networks ->".count($array)." items [".__LINE__."]\n";}
			while (list ($a, $b) = each ($array) ){
				$this->CONF[]="\t\tmatch host $b";
			}
			return;}		
		
		
		$IpClass=new IP();
		$q=new mysql_squid_builder();
		$sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
		$f=array();
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){ echo "[".__LINE__."]: $q->mysql_error\n";}
		while ($ligne = mysql_fetch_assoc($results)) {
			$pattern=trim($ligne["pattern"]);if($pattern==null){continue;}
			
			if($GroupType=="arp"){
				if(!$IpClass->IsvalidMAC($pattern)){continue;}
				$this->CONF[]="\t\tmatch srcmac $pattern";
				continue;
			}			
			
			
			if($GroupType=="src"){
				$this->CONF[]="\t\tmatch src $pattern";
				continue;
			}	

			if($GroupType=="dst"){
				$this->CONF[]="\t\tmatch host $pattern";
				continue;
			}	

			
			if($GroupType=="port"){
				$this->CONF[]="\t\tmatch dport $pattern";
				continue;
			}
			
			
		}
		
	}


}