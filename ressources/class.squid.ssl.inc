<?php



class squid_ssl{
	private $sslproxy_version;
	private $sslcrtd_program;
	private $acls;
	private $ok;
	private $SSL_BUMP_WHITE_LIST=0;
	private $SquidUrgency=0;
	private $IS_SQUID4=false;
	public function squid_ssl(){
		
		
		
		$sock=new sockets();
		$this->sslcrtd_program=$this->sslcrtd_program_path();
		$ini=new Bs_IniHandler();
		$this->sslproxy_version=intval($sock->GET_INFO("sslproxy_version"));
		
		if(!isset($GLOBALS["SQUID_MEMORYCONF"]["INI_ARRAY"])){
			
			$ini->loadString($sock->GET_INFO('ArticaSquidParameters'));
			if(isset($ini->_params)){$GLOBALS["SQUID_MEMORYCONF"]["INI_ARRAY"]=$ini->_params;}
		}else{
			$ini->_params=$GLOBALS["SQUID_MEMORYCONF"]["INI_ARRAY"];
		}
		$this->SSL_BUMP_WHITE_LIST=intval($ini->_params["NETWORK"]["SSL_BUMP_WHITE_LIST"]);
		
		
	}
	private function sslcrtd_program_path(){
		if($this->SquidUrgency==1){return null;}
		if(isset($GLOBALS["sslcrtd_program"])){return $GLOBALS["sslcrtd_program"];}
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
		$GLOBALS["sslcrtd_program"] = $unix->squid_locate_generic_bin("ssl_crtd");
		return  $GLOBALS["sslcrtd_program"];
	}
	
	
	public function build(){
		$unix=new unix();
		$SQUID_VERSION=$unix->squid_version();
		$this->acls=new squid_acls();
		$this->acls->Build_Acls();
		
		if(!is_numeric($this->sslproxy_version)){$sslproxy_version=1;}
		if($this->sslproxy_version==0){$sslproxy_version=1;}
		$f[]="# SSL Proxy options  Proxy version:$SQUID_VERSION [".__LINE__."]";
		
		if(preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)#", $SQUID_VERSION,$re)){
			$MAJOR=intval($re[1]);
			$MINOR=intval($re[2]);
			$REV=intval($re[3]);
			if($MAJOR>3){$this->IS_SQUID4=true;}
			
		}
		
		$f[]="sslcrtd_program $this->sslcrtd_program -s /var/lib/squid/session/ssl/ssl_db -M 8MB";
		$f[]="sslcrtd_children 16 startup=5 idle=1";
		$f[]="# SSL_BUMP_WHITE_LIST = $this->SSL_BUMP_WHITE_LIST [".__CLASS__."/".__FUNCTION__."/".__LINE__."]";
		
		
		
		
		$f[]="acl ssl_step1 at_step SslBump1";
		$f[]="acl ssl_step2 at_step SslBump2";
		$f[]="acl ssl_step3 at_step SslBump3";
		$f[]="ssl_bump peek ssl_step1";


		
		
		if(!is_file("/etc/squid3/ssl.conf")){
			@file_put_contents("/etc/squid3/ssl.conf", @implode("\n",$f)."\nssl_bump splice all\n");
		}
		
		$SniGroups=$this->SniGroups();
		if(count($SniGroups)>0){
			$f[]="";
			$f[]=@implode("\n",$SniGroups);
		}
		
		
		$ssl_bump_bump=$this->ssl_bump_bump();
		$CountOfSSLBump=count($ssl_bump_bump);
		$f[]="# $CountOfSSLBump BUMP rules...";
		
		if(!$this->ok){return;}
		if(count($ssl_bump_bump)>0){
			$f[]=@implode("\n",$ssl_bump_bump);
		}	
		
		
		$ssl_bump_splice=$this->ssl_bump_splice();
		$CountOfSSLSplice=count($ssl_bump_splice);
		$f[]="# $CountOfSSLBump Splice rules...";
		
		if(!$this->ok){return;}
		if(count($ssl_bump_splice)>0){
			$f[]=@implode("\n",$ssl_bump_splice);
		}
		//$f[]="ssl_bump stare ssl_step2 all";
		$f[]="ssl_bump splice all";
		$f[]="";
		
		if(!$this->IS_SQUID4){
			//$f[]="sslproxy_cipher EECDH+ECDSA+AESGCM:EECDH+aRSA+AESGCM:EECDH+ECDSA+SHA384:EECDH+ECDSA+SHA256:EECDH+aRSA+SHA384:EECDH+aRSA+SHA256:EECDH+aRSA+RC4:EECDH:EDH+aRSA:HIGH:!RC4:!aNULL:!eNULL:!LOW:!3DES:!MD5:!EXP:!PSK:!SRP:!DSS";
			$f[]="sslproxy_version $this->sslproxy_version";
			$f[]="sslproxy_options NO_SSLv2:NO_SSLv3:ALL:SINGLE_DH_USE";
			$f[]="sslproxy_flags DONT_VERIFY_PEER";
		}
		
		
		$sslproxy_cert_error=$this->sslproxy_cert_error();
		if(count($sslproxy_cert_error)>0){
			$f[]="";
			$f[]=@implode("\n",$sslproxy_cert_error);
			$f[]="sslproxy_cert_error deny all";
		}else{
			$f[]="sslproxy_cert_error allow all";
		}
		
		
		@file_put_contents("/etc/squid3/ssl.conf", @implode("\n",$f)."\n");
		
	}
	
	private function ssl_office365(){
		$sock=new sockets();
		$SquidSSLUrgency=intval($sock->GET_INFO("SquidSSLUrgency"));
		if($SquidSSLUrgency==1){return;}
		$AllowSquidOffice365=intval($sock->GET_INFO("AllowSquidOffice365"));
			
		if($AllowSquidOffice365==0){return "# --------- Office365 not trusted [".__LINE__."]\n";}
		include_once(dirname(__FILE__)."/class.products-ip-ranges.inc");
	
		$f[]="# --------- no bumping SSL for Office365 [".__LINE__."]";
		$f[]="ssl_bump splice office365_ips";
		$f[]="ssl_bump splice office365_www";
		return @implode("\n", $f)."\n";
	}
	
	
	private function SniGroups(){
		$q=new mysql_squid_builder();
		$f=array();
		$sql="SELECT webfilters_sqgroups.*
		FROM webfilters_sqgroups, ssl_rules, sslrules_sqacllinks
		WHERE sslrules_sqacllinks.aclid = ssl_rules.ID
		AND sslrules_sqacllinks.gpid = webfilters_sqgroups.ID
		AND webfilters_sqgroups.GroupType = 'ssl_sni'
		AND webfilters_sqgroups.enabled =1
		AND ssl_rules.enabled =1";
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){
			$f[]="# !!!! MySQL Error !!!!";
			return $f;
		}
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$GroupName=$ligne["GroupName"];
			$ID=$ligne["ID"];
			$f[]="# SNI Group $GroupName";
			$f[]=$this->SniGroupsItems($ID);
		}
			
			
			
			return $f;
		
		}
		
		private function isaregex($pattern){
			$pattern=" $pattern";
			if(strpos($pattern, "*")>0){return true;}
			if(strpos($pattern, ".*")>0){return true;}
			if(strpos($pattern, "[")>0){return true;}
			if(strpos($pattern, "]")>0){return true;}
			if(strpos($pattern, "(")>0){return true;}
			if(strpos($pattern, ")")>0){return true;}
			if(strpos($pattern, "?")>0){return true;}
		}
		
		private function SniGroupsItems($ID){
			$q=new mysql_squid_builder();
			$f=array();
			$results=$q->QUERY_SQL("SELECT * FROM webfilters_sqitems WHERE gpid=$ID AND enabled=1");
			while ($ligne = mysql_fetch_assoc($results)) {
				if($this->isaregex($ligne["pattern"])){
					$f[]="acl SNIGroup$ID ssl::server_name_regex -i {$ligne["pattern"]}";
					continue;
				}
				$ligne["pattern"]=str_replace(".", "\." ,$ligne["pattern"]);
				$f[]="acl SNIGroup$ID ssl::server_name_regex -i {$ligne["pattern"]}";
				
			}
			
			if(count($f)==0){
				$f[]="acl SNIGroup$ID ssl::server_name nothing.net";
			}
			return @implode("\n",$f);
		}
		
		
		private function sslproxy_cert_error(){
			$this->ok=true;
			$squid_acls_groups=new squid_acls_groups();
			$q=new mysql_squid_builder();
			$f=array();
			$sql="SELECT * FROM ssl_rules WHERE enabled=1 AND trust=1 ORDER BY zOrder";
			$results=$q->QUERY_SQL($sql);
			if(!$q->ok){$this->ok=false;}
			
			while ($ligne = mysql_fetch_assoc($results)) {
				$aclid=$ligne["ID"];
				$groups=$squid_acls_groups->buildacls_bytype_items($aclid,0,"sslrules_sqacllinks");
				if(count($groups)==0){continue;}
				$f[]="sslproxy_cert_error allow ".@implode(" ", $groups);
			}
			
			if(count($squid_acls_groups->ErrorsArray)>0){
				$f[]=@implode("\n", $squid_acls_groups->ErrorsArray);
			}
			
			return $f;
			}
	
	private function ssl_bump_splice(){
		$this->ok=true;
		$squid_acls_groups=new squid_acls_groups();
		$q=new mysql_squid_builder();
		$f=array();
		$sql="SELECT * FROM ssl_rules WHERE enabled=1 AND crypt=0 ORDER BY zOrder";
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){$this->ok=false;}
		
		$c=0;
		while ($ligne = mysql_fetch_assoc($results)) {
			$aclid=$ligne["ID"];
			$groups=$squid_acls_groups->buildacls_bytype_items($aclid,0,"sslrules_sqacllinks");
			if(count($groups)==0){continue;}
			$f[]="ssl_bump splice ".@implode(" ", $groups);
			$c++;
		}
		
		if(count($squid_acls_groups->ErrorsArray)>0){
			$f[]=@implode("\n", $squid_acls_groups->ErrorsArray);
		}
		
		if($c>0){
			$ssl_office365=$this->ssl_office365();
			if($ssl_office365<>null){
				array_unshift($f,$ssl_office365);
			}
			
			$ssl_dropbox=$this->ssl_dropbox();
			if($ssl_dropbox<>null){
				array_unshift($f,$ssl_dropbox);
			}
			
			$ssl_skype=$this->ssl_skype();
			if($ssl_skype<>null){
				array_unshift($f,$ssl_skype);
			}
			
			
		}
		
		return $f;
	}
	
	private function ssl_dropbox(){
		$sock=new sockets();
		$SquidSSLUrgency=intval($sock->GET_INFO("SquidSSLUrgency"));
		if($SquidSSLUrgency==1){return;}
		$AllowSquidSSLDropBox=intval($sock->GET_INFO("AllowSquidSSLDropBox"));
		$AllowSquidDropBox=intval($sock->GET_INFO("AllowSquidDropBox"));
		if($AllowSquidDropBox==1){$AllowSquidSSLDropBox=1;}
		if($AllowSquidSSLDropBox==0){return "# --------- DropBox not trusted [".__LINE__."]\n";}
		include_once(dirname(__FILE__)."/class.products-ip-ranges.inc");
			
		$f[]="# --------- no bumping SSL for dropbox [".__LINE__."]";
		$f[]="ssl_bump splice dropbox_ips";
		$f[]="ssl_bump splice dropbox_www";
		return @implode("\n", $f)."\n";
	}
	private function ssl_skype(){
		$sock=new sockets();
		$AllowSquidSkype=intval($sock->GET_INFO("AllowSquidSkype"));
		if($AllowSquidSkype==0){return "# --------- Skype not trusted [".__LINE__."]\n";}
		$f[]="# --------- no bumping SSL for Skype [".__LINE__."]";
	
		$f[]="ssl_bump splice skype_ips";
		$f[]="ssl_bump splice skype_www";
		return @implode("\n", $f)."\n";
	}
	
	
	private function ssl_bump_bump(){
		$this->ok=true;
		$squid_acls_groups=new squid_acls_groups();
		$q=new mysql_squid_builder();
		$f=array();
		$sql="SELECT * FROM ssl_rules WHERE enabled=1 AND crypt=1 ORDER BY zOrder";
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){$this->ok=false;}
		
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$aclid=$ligne["ID"];
			$groups=$squid_acls_groups->buildacls_bytype_items($aclid,0,"sslrules_sqacllinks");
			if(count($groups)==0){
				$f[]="# Rule $aclid, no groups"; 
				continue;
			}
		//???//???	//$f[]="ssl_bump stare ssl_step2 ".@implode(" ", $groups);
			$f[]="ssl_bump bump ".@implode(" ", $groups);
		}
		if(count($squid_acls_groups->ErrorsArray)>0){
			$f[]=@implode("\n", $squid_acls_groups->ErrorsArray);
		}
		return $f;
	}	
	
	private function get_objects($ruleid){
		
		$table="(SELECT sslrules_sqacllinks.gpid,sslrules_sqacllinks.negation,
		sslrules_sqacllinks.zOrder,sslrules_sqacllinks.zmd5 as mkey,
		webfilters_sqgroups.* FROM sslrules_sqacllinks,webfilters_sqgroups
		WHERE sslrules_sqacllinks.gpid=webfilters_sqgroups.ID AND sslrules_sqacllinks.aclid=$ruleid
		ORDER BY sslrules_sqacllinks.zOrder
		) as t";
		
	}
	
	
	
	
	
	
	
	
}