<?php 
include_once(dirname(__FILE__)."/class.tcpip.inc");

class squid_familysite{
	
	
	public function filldomains($MAIN,$www){
		$array["com"]=true;
		$array["at"]=true;
		$array["es"]=true;
		$array["pt"]=true;
		$array["ua"]=true;
		$array["fr"]=true;
		$array["ru"]=true;
		$array["ph"]=true;
		$array["hu"]=true;
		$array["com.ar"]=true;
		$array["com.au"]=true;
		$array["com.br"]=true;
		$array["com.sg"]=true;
		$array["com.mx"]=true;
		$array["com.hk"]=true;
		$array["com.my"]=true;
		$array["com.cn"]=true;
		$array["com.tr"]=true;
		
		$array["de.nu"]=true;
		$array["co.za"]=true;
		$array["co.nz"]=true;
		$array["tn"]=true;
		$array["co.tz"]=true;
		$array["co.zw"]=true;
		$array["com.fj"]=true;
		$array["com.ly"]=true;
		$array["com.pe"]=true;
		$array["dz"]=true;
		$array["bf"]=true;
		$array["be"]=true;
		$array["si"]=true;
		$array["ht"]=true;
		$array["st"]=true;
		$array["cl"]=true;
		$array["com.sa"]=True;
		$array["com.sv"]=True;
		$array["com.bo"]=True;
		$array["com.cu"]=True;
		$array["com.pk"]=True;
		$array["com.do"]=true;
		$array["com.ni"]=true;
		$array["com.jm"]=true;
		$array["id"]=True;
		$array["co.jp"]=true;
		$array["co.za"]=true;
		$array["co.id"]=true;
		$array["co.uk"]=true;
		$array["co.mz"]=true;
		$array["co.in"]=true;
		$array["co.zm"]=true;
		$array["co.cr"]=true;
		$array["co.ve"]=true;
		
		$array["tj"]=true;
		$array["tt"]=true;
		$array["de"]=true;
		$array["de.tf"]=true;
		$array["de.vu"]=true;
		$array["in"]=true;
		$array["iq"]=true;
		$array["it"]=true;
		$array["ro"]=true;
		$array["pl"]=true;
		$array["dk"]=true;
		$array["tr"]=true;
		$array["kz"]=true;
		$array["ma"]=true;
		$array["net"]=true;
		$array["by"]=true;
		$array["com.my"]=true;
		$array["se"]=true;
		$array["lan"]=true;
		$array["ro"]=true;
		$array["cl"]=true;
		$array["com.ph"]=true;
		$array["de"]=true;
		$array["ca"]=true;
		$array["jp"]=true;
		
		while (list ($ext, $in) = each ($array) ){
			$MAIN["{$www}.$ext"]=true;
		
		}
		return $MAIN;
	}
	
	public function GetFamilySites($sitename){
		if(!isset($GLOBALS["TCP_CLASS"])){$GLOBALS["TCP_CLASS"]=new IP();}
		if(!isset($GLOBALS["DEBUG_FAMILIY"])){$GLOBALS["DEBUG_FAMILIY"]=false;}
		$sitename=str_replace("'", "", $sitename);
		if(isset($GLOBALS["GetFamilySites"][$sitename])){return $GLOBALS["GetFamilySites"][$sitename];}
		if(strpos(" $sitename", ".")==0){return $sitename;}
		if($GLOBALS["TCP_CLASS"]->isIPAddress($sitename)){return $sitename;}
	
	
		if(function_exists("idn_to_ascii")){
			$sitename = @idn_to_ascii($sitename, "UTF-8");
		}
	
		$tmp=trim($this->getRegisteredDomain($sitename));
		if($GLOBALS["DEBUG_FAMILIY"]){echo "getRegisteredDomain($sitename) -> $tmp\n";}
	
		$tt=$this->GetFamilySitestt(null,true);
		if($tmp<>null){
			if(isset($tt[$tmp])){
				if($GLOBALS["DEBUG_FAMILIY"]){echo "{$tt[$tmp]} tt[$tmp] -> $sitename\n";}
				$GLOBALS["GetFamilySites"][$sitename]=$sitename;
				return $sitename;
			}
				
			$exp=explode(".",$tmp);
			if(count($exp)>1){
				$tmp=null;
			}else{
				$GLOBALS["GetFamilySites"][$sitename]=$tmp;
				return $tmp;
			}
	
				
				
		}
	
		
	
		$tmp=$this->GetFamilySitestt($sitename);
		if(strpos($tmp, ".")>0){
			$GLOBALS["GetFamilySites"][$sitename]=$tmp;
			return $tmp;
		}
	
		
		if($GLOBALS["VERBOSE"]){echo "GetFamilySitestt -> ?? $sitename\n";}
		$bits = explode('.', $sitename);
		$idz=count($bits);
		$idz-=3;
		
		if( $idz+2 == -1){
			if($GLOBALS["VERBOSE"]){echo "?? $sitename -> $idz +2 = -1\n";}
		}
		
		if (strlen($bits[($idz+2)])==2) {
			$url=$bits[$idz].'.'.$bits[($idz+1)].'.'.$bits[($idz+2)];
		} else if (strlen($bits[($idz+2)])==0) {
			$url=$bits[($idz)].'.'.$bits[($idz+1)];
		} else {
			$url=$bits[($idz+1)].'.'.$bits[($idz+2)];
		}
		if(substr($url, 0,1)=="."){$url=substr($url, 1,strlen($url));}
		$GLOBALS["GetFamilySites"][$sitename]=$url;
		return $url;
	}
	
	private function getRegisteredDomain($signingDomain) {
		if(!isset($GLOBALS["tldTree"])){
			$GLOBALS["tldTree"]=file_get_contents(dirname(__FILE__)."/tldree.db");
		}
	
		$signingDomainParts = explode('.', $signingDomain);
		$tldTree=unserialize($GLOBALS["tldTree"]);
		$result = $this->findRegisteredDomain($signingDomainParts, $tldTree);
	
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

	
	public function GetFamilySitestt($domain,$getpartOnly=false){
		$tlds = array(
				'com'=>array('cn'=>true,"uk"=>true,'us'=>true,'ya'=>true,"com"=>true,"gr"=>true,"org"=>true,"eu"=>true),
				'ac'=>array('gob' => true,"com"=>true,"org"=>true,"co"=>true),
				'ai'=>array('gob' => true,"com"=>true,"org"=>true,"co"=>true),
				'al'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'ar' => array('net'=>true,'gob' => true,"com"=>true,"org"=>true,"uc"=>true,"ua"=>true,"co"=>true,"org"=>true),
				'as'=>array("com"=>true,"org"=>true),
				'am'=>array("com"=>true,"org"=>true),
				'ae'=>array("com"=>true,"org"=>true),
				'ag'=>array("com"=>true,"org"=>true),
				
				'ar'=>array('co'=>true,'net'=>true,"com"=>true,"org"=>true,"ua"=>true),
				'at'=>array('co'=>true,"com"=>true,"org"=>true,"ua"=>true),
				'az'=>array("com"=>true,"org"=>true),
				'name'=>array("com"=>true,"org"=>true),
				'bb'=>array("com"=>true,"org"=>true),
				'bd'=>array("com"=>true,"org"=>true,"net"=>true),
				'bg'=>array("com"=>true,"org"=>true),
				
				'be'=>array("com"=>true,"org"=>true,'de'=>true),
				'bh'=>array("com"=>true,"org"=>true),
				'bo'=>array("com"=>true,"org"=>true),
				'ba'=>array("com"=>true,"org"=>true),
				'br'=>array('com' => true,"org"=>true,"tv"=>true,"inf"=>true,'net'=>true),
				'by'=>array("com"=>true,"org"=>true),
				'ca'=>array('ab'=>true,'bc'=>true,'on'=>true,'qc'=>true,'co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'cc'=>array("za"=>true,"com"=>true,"org"=>true,'co'=>true,'cx'=>true,'on'=>true,'cz'=>true,'co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'cd'=>array('gouv'=>true,"com"=>true,"org"=>true),
				'cf'=>array('gouv'=>true,"com"=>true,"org"=>true),
				'ci'=>array('gouv'=>true,"com"=>true,"org"=>true),
				'cl'=>array("com"=>true,"org"=>true),
				'cn'=>array('sh'=>true,'com'=>true,'net'=>true,"org"=>true,"za"=>true),
				'cm'=>array('com'=>true,'net'=>true,"org"=>true,"za"=>true),
				'cx'=>array('com'=>true,'net'=>true,"org"=>true,"za"=>true),
				'co' => array('mz'=>true,'net'=>true,"za"=>true,"com"=>true,"org"=>true,'cc'=>true),
				
				'ffr'=>array("com"=>true,"org"=>true),
				're'=>array("com"=>true,"org"=>true),
				'ru'=>array('msk'=>true,'da'=>true,"com"=>true,"org"=>true),
				'ws'=>array("com"=>true,"org"=>true),
				'tv'=>array("com"=>true,"org"=>true),
				
				'org'=>array("com"=>true,"org"=>true,'us'=>true),
				'net'=>array('uk'=>true,"com"=>true,"org"=>true),
				'do'=>array("com"=>true,"gob"=>true,"org"=>true),
				'de'=>array('tf'=>true,"com"=>true,"org"=>true),
				'dk'=>array("com"=>true,"org"=>true),
				'edu'=>array("com"=>true,"org"=>true),
				'arpa'=>array("in-addr"=>true),
				'fo'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'fr' => array('net'=>true,'gouv'=>true),
				'ec'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'eg'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'ee' => array('com' => true,"uc"=>true,"ua"=>true,"co"=>true,"org"=>true),
				'es'=>array("com"=>true,"org"=>true,"nom"=>true,"gob"=>true),
				'click'=>true,
				'hosting'=>true,
				'help'=>true,
				'host'=>true,
				'solutions'=>true,
				
				
				
				'biz'=>array("com"=>true),
				
				'ie' => array('net' => true,"web"=>true,"co"=>true,"or"=>true,"org"=>true),
				'id' => array('net' => true,"web"=>true,"co"=>true,"or"=>true,"org"=>true),
				'is' => array('net' => true,"web"=>true,"co"=>true,"or"=>true,"org"=>true),
				
				'au' => array('net' => true,"com"=>true,"org"=>true),
				
				'pt' => array('com' => true,"uc"=>true,"ua"=>true,"co"=>true,"org"=>true,"gov"=>true),
				'gr' => array('com' => true,"uc"=>true,"ua"=>true,"co"=>true,"org"=>true),
				'lk' => array('com' => true,"uc"=>true,"ua"=>true,"co"=>true,"org"=>true),
				'lu' => array('com' => true,"uc"=>true,"ua"=>true,"co"=>true,"org"=>true),
				'np' => array('com' => true,"uc"=>true,"ua"=>true,"co"=>true,"org"=>true),
				'jo' => array('com' => true,"uc"=>true,"ua"=>true,"co"=>true,"org"=>true),
				'ph'=> array('com'=>true,"gov"=>true),
				'tw' => array('co' => true,"go"=>true,"in"=>true,"com"=>true,"net"=>true,"org"=>true),
				'th' =>array('go'=>true,'co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'tr' =>array('biz'=>true ,'com'=>true ,'info'=>true,'tv'=>true,'av'=>true,'bbs'=>true,'bel'=>true,'dr'=>true,'gen'=>true,'name'=>true,'nc'=>true,'net'=>true,'org'=>true,'pol'=>true,'tel'=>true,'tsk'=>true,'web'=>true,'co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'to' =>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				
				
				'gi' =>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'gq' =>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				
				'la'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				
				'ch'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'cl'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true,"gob"=>true),
				'cn'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'cz'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'cu'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'ee'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'eu'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'gl'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'gs'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'ge'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'hk'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'hu'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'hr'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'il'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'ir'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'it'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'im'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'in'=>array('ua'=>true,'co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true,"ind"=>true),
				'id'=>array('sch'=>true,'co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'io'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'is'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				
				'info'=>array('com' => true,"net"=>true,"org"=>true),
				'lb'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'li'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'kg'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				
				
				'ly'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'lv'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'lt'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'jp'=>array('ed'=>true,'gr'=>true,'or'=>true,'ne'=>true,'org' => true,"co"=>true),
				'na'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'ni'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'nl'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'net'=>array('fr'=>true,'it'=>true,"za"=>true),
				'mn'=>array('fr'=>true,'it'=>true),
				'jobs'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'invalid'=>true,
				'no'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'nu'=>array('de'=>true,'co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'bz'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'nz'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				
				'fi'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				
				'fm'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				

				'md'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'local'=>true,
				'team'=>true,
				'travel'=>true,
				'tj'=>true,
				
				'aero'=>array("com"=>true,"org"=>true),
				'ma'=>array('co'=>true,'cu'=>true,'com'=>true,"org"=>true,"net"=>true,'gov'=>true),
				'ms'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'me'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'mobi'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'gbl'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'mx'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true,"tv"=>true,"gob"=>true),
				'my'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'fr'=>array("org"=>true),
				'om'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'pe'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true,"gob"=>true),
				'pl'=>array('info'=>true,'za'=>true,'co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'pm'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'pg'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				
				'pk'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'ps'=>true,
				'pw'=>true,
				
				'kz'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'kr'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true,"or"=>true),
				've'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'vn'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'za'=>array('pl'=>true,'co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'rs'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'ro'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				
				'sa'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'se'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'sg'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'sq'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'sh'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'si'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'sk'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'st'=>array('fr'=>true,'co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'so'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'su'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'sy'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'tf'=>array('de'=>true,'co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'tl'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'tc'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'tk'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'to'=>array('uk'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'tt'=>true,
				'tv'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'ua'=>array('kh'=>true,'pl'=>true,'ck'=>true,'vn'=>true,'if'=>true,'pp'=>true,'dp'=>true,'dn' => true,"dp"=>true,"od"=>true,'at'=>true,'in'=>true,'co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true,"dn"=>true),
				'uk' => array('net'=>true,'co' => true,"org"=>true,"me"=>true,"org"=>true,'gov'=>true),
				'us'=>array('ca'=>true,'wi'=>true,"com"=>true,"org"=>true,'sd'=>true),
				'uz'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'uy'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true,'co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'vc'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'vi'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'vu'=>array('de'=>true,'co'=>true,'com'=>true,"ch"=>true,"org"=>true,"net"=>true),
				've'=>array('info'=>true,'co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'yu'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
					
	
	
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

?>