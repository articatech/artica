<?php
include_once(dirname(__FILE__)."/class.squid.familysites.inc");
class familysite{
	
	function familysite(){
		$GLOBALS["TCP_CLASS"]=new IP();
		
	}
	
	public function GetComputerName($ip){
		if(!$GLOBALS["TCP_CLASS"]->isIPAddress($ip)){return;}
		$this->LoadResolveCache();
		if(isset($GLOBALS["resvip"][$ip])){
			if(strlen($GLOBALS["resvip"][$ip])>3){
				return $GLOBALS["resvip"][$ip];
			}
		}
	
		$name=gethostbyaddr($ip);
		$GLOBALS["resvip"][$ip]=$name;
		$this->SaveResolveCache();
		return $name;
	}

	private function SaveResolveCache(){
		$fname="/etc/squid3/ipcaches.db";
		@file_put_contents($fname, serialize($GLOBALS["resvip"]));
	}	
	private function LoadResolveCache(){
		if(isset($GLOBALS["resvip"])){return;}
		$unix=new unix();
		$fname="/etc/squid3/ipcaches.db";
		if($unix->file_time_min($fname)>15){$GLOBALS["resvip"]=array();}
		$GLOBALS["resvip"]=unserialize(@file_get_contents($fname));
	
	}	
	
	public function GetFamilySites($sitename){
		$fam=new squid_familysite();
		return $fam->GetFamilySites($sitename);
	}
	
	private function getRegisteredDomain($signingDomain) {
		if(!isset($GLOBALS["tldTree"])){
			$GLOBALS["tldTree"]=unserialize(@file_get_contents(dirname(__FILE__)."/tldree.db"));
		}
	
		$signingDomainParts = explode('.', $signingDomain);
	
		$result = $this->findRegisteredDomain($signingDomainParts, $GLOBALS["tldTree"]);
	
		if ($result===NULL || $result=="") {
			// this is an invalid domain name
			return NULL;
		}
	
		// assure there is at least 1 TLD in the stripped signing domain
		if (!strpos($result, '.')) {
			$cnt = count($signingDomainParts);
			if ($cnt==1 || $signingDomainParts[$cnt-2]=="") return NULL;
			return $signingDomainParts[$cnt-2].'.'.$signingDomainParts[$cnt-1];
		}
		return $result;
	}


	// recursive helper method
	private function findRegisteredDomain($remainingSigningDomainParts, &$treeNode) {
	
		$sub = array_pop($remainingSigningDomainParts);
	
		$result = NULL;
		if (isset($treeNode['!'])) {
			return '#';
		} else if (is_array($treeNode) && array_key_exists($sub, $treeNode)) {
			$result = $this->findRegisteredDomain($remainingSigningDomainParts, $treeNode[$sub]);
		} else if (is_array($treeNode) && array_key_exists('*', $treeNode)) {
			$result = $this->findRegisteredDomain($remainingSigningDomainParts, $treeNode['*']);
		} else {
			return $sub;
		}
	
		// this is a hack 'cause PHP interpretes '' as NULL
		if ($result == '#') {
			return $sub;
		} else if (strlen($result)>0) {
			return $result.'.'.$sub;
		}
		return NULL;
	}	
	
	private function GetFamilySitestt($domain,$getpartOnly=false){
		$tlds = array(
				'com'=>array('ya'=>true,"com"=>true,"gr"=>true),
				'name'=>true,
				're'=>true,
				'ru'=>true,
				'ws'=>true,
				'org'=>true,
				'net'=>true,
				'cn'=>array('com'=>true,'net'=>true),
				'ar'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true),
				'at'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true),
				'ci'=>array('gouv'=>true,"com"=>true),
				'biz'=>true,
				'uk' => array('co' => true,"ac"=>true,"gov"=>true,"org"=>true,"me"=>true,"gov"=>true),
				'id' => array('net' => true,"web"=>true,"ac"=>true,"co"=>true,"or"=>true,"gov"=>true),
				'ua' => array('dn' => true,"dp"=>true,"od"=>true,"gov"=>true),
				'au' => array('net' => true,"com"=>true,"gov"=>true),
				'ar' => array('gob' => true),
				'pt' => array('com' => true,"gov"=>true,"uc"=>true,"ua"=>true,"gov"=>true),
				'ph'=> array('com'=>true,"gov"=>true),
				'tw' => array('co' => true,"go"=>true,"in"=>true,"gov"=>true,'ac'=>true,"com"=>true,"net"=>true),
				'th' => array('co' => true,"go"=>true,"in"=>true,"gov"=>true,'ac'=>true),
				'tr' => array('com' => true,"org"=>true,"co"=>true,"gov"=>true),
				'co' => array('gov' => true,"za"=>true,"gov"=>true,"com"=>true,"cc"=>true),
				'gi' => array('gov' => true),
				'ca' => array('qc' => true,"gov"=>true),
				'ch' =>true,
				'cn' => array('com' => true,"gov"=>true),
				'cz' =>true,
				'ee' =>true,
				'hk'=> array('co' => true),
				'il' => array('co' => true),
				'in' => array('co' => true),
				'io' => true,
				'pl' =>array('com'=>true),
				'info'=>true,
				'jp'=>array('ne'=>true,'org' => true,"co"=>true,"gov"=>true),
				'na'=>array('com'=>true),
				'net'=>array('fr'=>true),
				'no'=>true,
				'nu'=>true,
				'bz'=>true,
				'nz' => array('org' => true,"co"=>true,"gov"=>true),
				'br' => array('com' => true,"org"=>true,"gov"=>true),
				'ec' => array('com' => true,"gov"=>true),
				'eg' => array('gov' => true,'com'=>true,"gov"=>true),
				'fi'=>true,
				'fm'=>true,
				'fr' => array('gouv' => true,'net'=>true),
				'me'=>true,
				'mx'=> array('com' => true,"gov"=>true),
				'my'=> array('com' => true,"gov"=>true),
				'fr'=>array('gouv' => true,"gov"=>true),
				'pk'=>array('edu'=>true,"com"=>true,"gov"=>true,"org"=>true),
				'ua'=>array('net'=>true,"com"=>true,"gov"=>true),
				'kz'=>array('co'=>true,'com'=>true,"gov"=>true,"co"=>true),
				'kr'=>array('or'=>true,"gov"=>true),
				've'=>array('co'=>true,'com'=>true,"gov"=>true),
				'vn'=>array('co'=>true,'com'=>true,"gov"=>true),
				'za'=>array('co'=>true,'com'=>true,"gov"=>true),
				'rs'=>array('co'=>true,'com'=>true,"gov"=>true),
	
	
	
		);
		if($getpartOnly){
			foreach ($tlds as $key => $part) {
				$doms[$key]=true;
				if(is_array($part)){
					while (list ($a, $b) = each ($part) ){
						$doms["$a.$key"]=true;
					}
					continue;
				}
			}
			return $doms;
		}
	
		$parts = explode('.', $domain);
		$tmp = $tlds;
		// travers the tree in reverse order, from right to left
		foreach (array_reverse($parts) as $key => $part) {
			if (isset($tmp[$part])) {
				$tmp = $tmp[$part];
			} else {
				break;
			}
		}
		if($getpartOnly){return $tmp;}
	
		$get=implode('.', array_slice($parts, - $key - 1));
		if(substr($get, 0,1)=="."){$get=substr($get, 1,strlen($get)); }
		return $get;
	}	
	
	
}