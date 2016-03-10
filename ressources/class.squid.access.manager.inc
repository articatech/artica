<?php
include_once(dirname(__FILE__)."/class.squid.acls.cache_deny.inc");
include_once(dirname(__FILE__)."/class.squid.acls.inc");
include_once(dirname(__FILE__)."/class.squid.acls.groups.inc");
class squid_access_manager{
	private $acls;
	private $acldeny_ntlm_domains=array();
	private $SquidUrgency=0;
	public  $ACL_ARP_ENABLED=false;
	
	public function squid_access_manager(){
		if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
		$users=$GLOBALS["CLASS_USERS"];
		$this->ACL_ARP_ENABLED=$users->SQUID_ARP_ACL_ENABLED;
		
		
		if(is_file("/etc/artica-postfix/settings/Daemons/SquidUrgency")){
			$this->SquidUrgency=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidUrgency"));
		}
		$this->acls=new squid_acls();
		$this->acls->Build_Acls();
		
		
	}
	
	
	function build_all(){
		$this->deny_global();
		$this->allow_auth();
		$this->deny_ufdb();
		$this->deny_cache();
		$this->url_rewrite_access();
		
	}
	
	
	
	
	
	function deny_global(){
		$f=array();
		if($this->SquidUrgency){
			@file_put_contents("/etc/squid3/GlobalAccessManager_deny.conf", "#Emergency mode !!! \n");
			return;
		}
		$q=new mysql_squid_builder();
		$this->acl_extensions();
		$head1=null;
		$sql="SELECT *  FROM `global_whitelist` WHERE enabled=1 and deny_global=1";
		$results = $q->QUERY_SQL($sql);
		
		$f[]="# Deny auth ".mysql_num_rows($results)." rules";
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$acls_name="Group{$ligne["groupid"]}";
			if(!$this->acls->availableGroups[$ligne["groupid"]]){
				$f[]="# Group{$ligne["groupid"]} unavailable For rule {$ligne["groupid"]}";
				continue;}
			$f[]="http_access deny $acls_name all";
				
		}
		
		
		$sql="SELECT *  FROM `global_whitelist` WHERE enabled=1 and deny_ext=1";
		$results = $q->QUERY_SQL($sql);
		$ext=0;
		while ($ligne = mysql_fetch_assoc($results)) {
			$acls_name="Group{$ligne["groupid"]}";
			if(!$this->acls->availableGroups[$ligne["groupid"]]){continue;}
			$ext++;
			$f[]="http_access deny $acls_name BlockDangerousFiles all";
		
		}		

		
		if($ext>0){$head1="acl BlockDangerousFiles urlpath_regex -i \"/etc/squid3/dangerous_extensions.conf\"\n"; }
		
		
		@file_put_contents("/etc/squid3/GlobalAccessManager_deny.conf", $head1.@implode("\n",$f). "\n");
	}
	
	
	function acl_extensions(){
		$cachefile="/etc/squid3/dangerous_extensions.conf";
		$extz[]="exe";
		$extz[]="pif";
		$extz[]="application";
		$extz[]="gadget";
		$extz[]="msi";
		$extz[]="msu";
		$extz[]="msp";
		$extz[]="com";
		$extz[]="scr";
		$extz[]="hta";
		$extz[]="cpl";
		$extz[]="msc";
		$extz[]="bat";
		$extz[]="cmd";
		$extz[]="vb";
		$extz[]="vbs";
		$extz[]="vbe";
		$extz[]="jse";
		$extz[]="ws";
		$extz[]="wsf";
		$extz[]="wsc";
		$extz[]="wsh";
		$extz[]="ps1";
		$extz[]="ps1xml";
		$extz[]="ps2";
		$extz[]="ps2xml";
		$extz[]="psc1";
		$extz[]="psc2";
		$extz[]="msh";
		$extz[]="msh1";
		$extz[]="msh2";
		$extz[]="mshxml";
		$extz[]="msh2xml";
		$extz[]="scf";
		$extz[]="lnk";
		$extz[]="inf";
		$extz[]="reg";
		
		while (list ($index, $ext) = each ($extz) ){
			$f[]="\.{$ext}(\?.*)?$";
		}
		$f[]="";
		@file_put_contents("$cachefile", @implode("\n", $f));
		
	}
	
	function deny_ufdb(){
		$cachefile="/etc/squid3/GlobalAccessManager_url_rewrite.conf";
		
		if($this->SquidUrgency==1){
			@file_put_contents($cachefile, "# Emergency mode!\n");
			return;
		}
		
		
		$q=new mysql_squid_builder();
		$sql="SELECT *  FROM `global_whitelist` WHERE enabled=1 and deny_ufdb=1";
		$results = $q->QUERY_SQL($sql);
		
		if(mysql_num_rows($results)==0){@file_put_contents($cachefile, "\n");return;}
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$acls_name="Group{$ligne["groupid"]}";
			if(!$this->acls->availableGroups[$ligne["groupid"]]){continue;}
			$f[]="url_rewrite_access deny $acls_name";
		
		}
		@file_put_contents($cachefile, @implode("\n",$f). "\n");
		
	}
	
	private function url_rewrite_access_dropbox(){
		$sock=new sockets();
		$AllowSquidSSLDropBox=intval($sock->GET_INFO("AllowSquidSSLDropBox"));
		$AllowSquidDropBox=intval($sock->GET_INFO("AllowSquidDropBox"));
		if($AllowSquidDropBox==1){$AllowSquidSSLDropBox=1;}
		if($AllowSquidSSLDropBox==1){$AllowSquidDropBox=1;}
		if($AllowSquidDropBox==0){return;}
		$conf[]="url_rewrite_access deny dropbox_ips";
		$conf[]="url_rewrite_access deny dropbox_www";
		return @implode("\n", $conf);
	}
	
	private function url_rewrite_access(){
		$sock=new sockets();
		if($this->SquidUrgency==1){return null;}
		$SquidDebugPort=intval($sock->GET_INFO("SquidDebugPort"));
		$UfdbGuardHTTPAllowUnblock=intval($sock->GET_INFO("UfdbGuardHTTPAllowUnblock")); //UfdbAutoblock
		$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
		if($SquidPerformance>2){$UfdbGuardHTTPAllowUnblock=0;}
		$AllowSquidDropBox=intval($sock->GET_INFO("AllowSquidDropBox"));
		$AllowSquidSkype=intval($sock->GET_INFO("AllowSquidSkype"));
		$AllowSquidOffice365=intval($sock->GET_INFO("AllowSquidOffice365"));
		$AllowSquidGoogle=intval($sock->GET_INFO("AllowSquidGoogle"));
	
	
	
		$conf[]="# builded on ". date("Y-m-d H:i:s");
		
		
	
		if($SquidDebugPort==1){$conf[]="url_rewrite_access deny MyTestPort"; }
		$conf[]="acl UrlRewriteDenyList dstdomain \"/etc/squid3/url_rewrite_program.deny.db\"\n";
		if(!is_file("/etc/squid3/url_rewrite_program.deny.db")){@touch("/etc/squid3/url_rewrite_program.deny.db");}
	
		$conf[]="include /etc/squid3/GlobalAccessManager_url_rewrite.conf";
		
		$conf[]="url_rewrite_access deny ArticaMetaWhiteDoms";
		$conf[]="url_rewrite_access deny ArticaMetaWhiteIPs";
		$conf[]="url_rewrite_access deny MgRDest";
		$conf[]="url_rewrite_access deny ToArticaWWW";
		$conf[]="url_rewrite_access deny MgRPort";
		$conf[]="url_rewrite_access deny UrlRewriteDenyList";
		$conf[]="url_rewrite_access deny MyLocalIpsDest";
		$conf[]="url_rewrite_access deny BrowsersNoWebF";
	
			
	
	
		if($this->ACL_ARP_ENABLED){
			$conf[]="url_rewrite_access deny whitelisted_mac_computers";
	
		}
	
	
		$conf[]="#--------- AllowSquidDropBox:$AllowSquidDropBox, AllowSquidSkype:$AllowSquidSkype, AllowSquidOffice365:$AllowSquidOffice365 F:".__FUNCTION__." line:".__LINE__;
	
		$conf[]=$this->url_rewrite_access_dropbox();
	
		if($AllowSquidSkype==1){
			$conf[]="url_rewrite_access deny skype_ips";
			$conf[]="url_rewrite_access deny skype_www";
		}
		if($AllowSquidOffice365==1){
			$conf[]="url_rewrite_access deny office365_ips";
			$conf[]="url_rewrite_access deny office365_www";
		}
		if($AllowSquidGoogle==1){
			$conf[]="url_rewrite_access deny google_ips";
			$conf[]="url_rewrite_access deny google_www";
		}
	
		$conf[]="url_rewrite_access deny MgRPort localhost";
		$conf[]="url_rewrite_access deny MgRPort squidclient";
		if(isset($GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS"])){
			$conf[]="url_rewrite_access deny whitelisted_computers";
		}
	
	
		if(class_exists("squid_acls_groups")){
			$acls=new squid_acls_groups();
			
			$acls_allow=$acls->buildacls_bytype("url_rewrite_access_allow");
			$conf[]=@implode("\n", $acls->buildacls_bytype_logs);
			if(count($acls_allow)>0){
				while (list ($index, $line) = each ($acls_deny) ){
					$conf[]="url_rewrite_access allow $line";
				}
			}
			
			
			$acls_deny=$acls->buildacls_bytype("url_rewrite_access_deny");
			$conf[]=@implode("\n", $acls->buildacls_bytype_logs);
			if(count($acls_deny)>0){
				while (list ($index, $line) = each ($acls_deny) ){
					$conf[]="url_rewrite_access deny $line";
				}
			}
			

			

		}
	
		
		$conf[]="url_rewrite_access allow all";
		@file_put_contents("/etc/squid3/url_rewrite_access.conf", @implode("\n", $conf)."\n");
		return "include /etc/squid3/url_rewrite_access.conf\n";
	
	}
	
	
	function deny_cache(){
		
		$f=array();
		$cachefile="/etc/squid3/GlobalAccessManager_deny_cache.conf";
		
		if($this->SquidUrgency==1){
			@file_put_contents($cachefile, "# Emergency mode!\n");
			return;
		}
		
		
		$cache_deny=new acls_cache_deny();
		$cache_deny->build();
		$f=$cache_deny->acl;
		
		if(function_exists("debug_backtrace")){
			$trace=debug_backtrace();
			while (list ($index, $xtrace) = each ($trace) ){
				$file=basename($xtrace["file"]);
				$function=$xtrace["function"];
				$line=$xtrace["line"];
				$f[]="# Called by $function in line $line in $file";
			}
		}
		
		
		
		$f[]="# Builded On ".date("Y-m-d H:i:s");
		$f[]="# acls_cache_deny() ". count($f)." items";
		$q=new mysql_squid_builder();
		$sql="SELECT *  FROM `global_whitelist` WHERE enabled=1 and deny_cache=1";
		$results = $q->QUERY_SQL($sql);
		
		if(mysql_num_rows($results)>0){
			while ($ligne = mysql_fetch_assoc($results)) {
				$acls_name="Group{$ligne["groupid"]}";
				if(!$this->acls->availableGroups[$ligne["groupid"]]){continue;}
				$f[]="cache deny $acls_name";
			
			}
		}
		
		$RangeOffset=$this->RangeOffSetLimit();
		@file_put_contents($cachefile, @implode("\n",$f). "\n$RangeOffset\n");
		
		
		
	}
	

	private function RangeOffSetLimit(){
		$sock=new sockets();
		$EnableRangeOffset=intval($sock->GET_INFO("EnableRangeOffset"));
		if($EnableRangeOffset==0){return "\n# EnableRangeOffset is disabled\nrange_offset_limit 0\n";}
		$already=array();
		$IPADDR=array();
		$DOMAINS=array();
		$q=new mysql_squid_builder();
		$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `rangeoffsetlimit` ( `items` VARCHAR(256) NOT NULL PRIMARY KEY ) ENGINE=MYISAM;");
		if(!$q->TABLE_EXISTS("rangeoffsetlimit")){return;}
		$DOMAINS["adobe.com"]=true;
		$DOMAINS["cdn.mozilla.net"]=true;
		$DOMAINS["cdninstagram.com"]=true;
		$DOMAINS["hegre-art.com"]=true;
		$DOMAINS["lebenna.com"]=true;
		$DOMAINS["shiwaw.net"]=true;
		$DOMAINS["skype.com"]=true;
		$DOMAINS["stiffia.com"]=true;
		$DOMAINS["vimeocdn.com"]=true;
		$DOMAINS["windowsphone.com"]=true;
		$DOMAINS["amazonaws.com"]=true;
		$DOMAINS["y8.com"]=true;
		$DOMAINS["steamstatic.com"]=true;
		$DOMAINS["steamusercontent.com"]=true;
		$DOMAINS["gvt1.com"]=true;
		$DOMAINS["google.com"]=true;
		$DOMAINS["playstation.net"]=true;
		$DOMAINS["ubuntu.com"]=true;
		$DOMAINS["canonical.com"]=true;
		$DOMAINS["s-msn.com"]=true;
		$DOMAINS["akamaihd.net"]=true;
		
		$ACLS_ARTICA[]="acl rangeoffsetUPDT dstdomain .windowsupdate.com";
		$ACLS_ARTICA[]="range_offset_limit 4096 MB rangeoffsetUPDT";
		
		$sql="SELECT items FROM rangeoffsetlimit";
		$results=$q->QUERY_SQL($sql);
		$ACLS[]="# rangeoffsetlimit ". mysql_num_rows($results)." entries";
		$IPClass=new IP();

		$c=0;
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$www=trim($ligne["items"]);
				if($www==null){continue;}
				if($www=="windowsupdate.com"){continue;}
					
				if($IPClass->isIPAddressOrRange($www)){
					$c++;
					$IPADDR[$www]=$www;
					continue;
				}
					
					
				$www=$this->BaseNameWebSite($www);
				if($www==null){continue;}
				if(isset($DOMAINS[$www])){continue;}
				$DOMAINS[$www]=true;
				$c++;
		}
			
		$RANGE=array();
		$ACLS=array();
		$ACLS[]="# Rangeoffset ".count($IPADDR)." ip addresses";
		if(count($IPADDR)>0){
			while (list ($ip,$none) = each ($IPADDR)){
				$ACLS[]="acl rangeoffsetIP dst $ip";
			}
				$RANGE[]="range_offset_limit 128 MB rangeoffsetIP";
		}		
		$ACLS[]="# Rangeoffset ".count($DOMAINS)." Domains";

		if(count($DOMAINS)>0){
			while (list ($dom,$none) = each ($DOMAINS)){
				$ACLS[]="acl rangeoffsetDOM dstdomain .$dom";
			}
			$RANGE[]="range_offset_limit 128 MB rangeoffsetDOM";
			
		}
		$RANGE[]="range_offset_limit 0";
		
		return @implode("\n", $ACLS_ARTICA)."\n".@implode("\n", $ACLS)."\n".@implode("\n", $RANGE)."\n";
	}

		
		
	function deny_ntlm_domains(){
		$already=array();
		$q=new mysql_squid_builder();
		$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `deny_ntlm_domains` ( `items` VARCHAR(256) NOT NULL PRIMARY KEY ) ENGINE=MYISAM;");
		if(!$q->TABLE_EXISTS("deny_ntlm_domains")){return;}
		if($q->COUNT_ROWS("deny_ntlm_domains")==0){
			$this->acldeny_ntlm_domains[]="# deny_ntlm_domains - no rows";
			return;}
		
			$sql="SELECT items FROM deny_ntlm_domains";
			$results=$q->QUERY_SQL($sql);
			$IPClass=new IP();
			$IPADDR=array();
			$DOMAINS=array();
			$c=0;
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$www=trim($ligne["items"]);
				if($www==null){continue;}
					
				if($IPClass->isIPAddressOrRange($www)){
					$c++;
					$IPADDR[$www]=$www;
					continue;
				}
					
					
				$www=$this->BaseNameWebSite($www);
				if($www==null){continue;}
				if(isset($DOMAINS[$www])){continue;}
				$DOMAINS[$www]=true;
				$c++;
			}
			
			
			
			if(count($IPADDR)>0){
				while (list ($ip,$none) = each ($IPADDR)){
					$this->acldeny_ntlm_domains[]="acl DoNotAuthentIP dst $ip";
				}
				$this->acldeny_ntlm_domains[]="http_access allow DoNotAuthentIP";
			}

			
			
			if(count($DOMAINS)>0){
				while (list ($dom,$none) = each ($DOMAINS)){
					$this->acldeny_ntlm_domains[]="acl DoNotAuthent dstdomain .$dom";	
				}	
				$this->acldeny_ntlm_domains[]="http_access allow DoNotAuthent";
			}
				

			
			
			
	}
	
	
	
	
	function allow_auth(){
		
		if($this->SquidUrgency==1){
			@file_put_contents("/etc/squid3/GlobalAccessManager_auth.conf", "# Emergency mode!\n");
			return;
		}
		
		$q=new mysql_squid_builder();
		$sql="SELECT *  FROM `global_whitelist` WHERE enabled=1 and deny_auth=1";
		$results = $q->QUERY_SQL($sql);
		$this->deny_ntlm_domains();
		$f=$this->acldeny_ntlm_domains;
		
		if(mysql_num_rows($results)>0){
			
		
			while ($ligne = mysql_fetch_assoc($results)) {
				$acls_name="Group{$ligne["groupid"]}";
				if(!$this->acls->availableGroups[$ligne["groupid"]]){continue;}
				$f[]="http_access allow $acls_name";
				
			}
		
		}
		
		@file_put_contents("/etc/squid3/GlobalAccessManager_auth.conf", @implode("\n",$f). "\n");
		
	}
	
	private function BaseNameWebSite($www){
		if(trim($www)==null){return;}
		if(is_numeric(trim($www))){return;}
			
		if(strpos(" $www", "//")>0){
			$arrayURI=parse_url($www);
			$www=$arrayURI["host"];
	
		}
		if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}
		if(preg_match("#(.+):[0-9]+#", $www,$re)){$www=$re[1];}
		if(substr($www,0,1)<>"."){$www=".$www";}
		if(substr($www,strlen($www)-1,1)=="/"){$www=substr($www, 0,strlen($www)-1);}
		$exploded=explode(".", $www);
		$sitebase=$exploded[count($exploded)-2].".".$exploded[count($exploded)-1];
		if(substr($sitebase, 0,1)=="."){$sitebase=substr($sitebase, 1,strlen($sitebase));}
		$sitebase=str_replace('.*.', '.', $sitebase);
		$sitebase=str_replace('*', '.', $sitebase);
		$sitebase=str_replace('$', '', $sitebase);
		$sitebase=str_replace('(', '', $sitebase);
		$sitebase=str_replace(')', '', $sitebase);
		$sitebase=str_replace(';', '.', $sitebase);
		$sitebase=str_replace(',', '.', $sitebase);
		$sitebase=str_replace('%', '', $sitebase);
		$sitebase=str_replace('+', '', $sitebase);
		$sitebase=str_replace('/', '', $sitebase);
		$sitebase=str_replace('?', '', $sitebase);
		$sitebase=str_replace('!', '', $sitebase);
		$sitebase=str_replace(':', '', $sitebase);
		$sitebase=str_replace('{', '', $sitebase);
		$sitebase=str_replace('}', '', $sitebase);
		$sitebase=str_replace('^', '', $sitebase);
		$sitebase=str_replace('\\', '', $sitebase);
		$sitebase=str_replace('\'', '', $sitebase);
		$sitebase=str_replace('"', '', $sitebase);
		$sitebase=str_replace('&', '', $sitebase);
		$sitebase=str_replace('<', '', $sitebase);
		$sitebase=str_replace('>', '', $sitebase);
		$sitebase=str_replace('[', '', $sitebase);
		$sitebase=str_replace(']', '', $sitebase);
		return $sitebase;
	}	
	
}