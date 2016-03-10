<?php
include_once(dirname(__FILE__)."/class.ActiveDirectory.inc");
include_once(dirname(__FILE__)."/class.tcpip.inc");
class squid_acls{
	public $acls_array=array();
	public $availableGroups=array();
	public $http_access=array();
	public $ACL_ARP_ENABLED=false;
	private $LDAP_EXTERNAL_AUTH=0;
	public $SQUID_GROUPS_ITEMS_TYPE=array();
	public $RequestedGroupeType=null;
	public $UseDynamicGroupsAcls=1;
	public $EnableKerbAuth=0;
	public $AUTH=0;
	public $instancetime=null;
	public $ASROOT=false;
	private $hasProxyTransparent=0;
	private $EnableTransparent27=0;
	private $SquidUrgency=0;
	private $ActiveDirectoryEmergency=0;
	private $EnableArticaHotSpot=0;

	public function squid_acls(){
		if(posix_getuid()==0){$this->ASROOT=true;}
		if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
		$users=$GLOBALS["CLASS_USERS"];
		$sock=new sockets();
		$this->ACL_ARP_ENABLED=$users->SQUID_ARP_ACL_ENABLED;
		$this->hasProxyTransparent=intval($sock->GET_INFO("hasProxyTransparent"));
		$this->EnableTransparent27=intval($sock->GET_INFO("EnableTransparent27"));
		$this->UseDynamicGroupsAcls=1;
		$this->SquidUrgency=intval($sock->GET_INFO("SquidUrgency"));
		$this->ActiveDirectoryEmergency=intval($sock->GET_INFO("ActiveDirectoryEmergency"));
		$this->EnableArticaHotSpot=intval($sock->GET_INFO("EnableArticaHotSpot"));
		
		$this->EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
		if(!is_numeric($this->EnableKerbAuth)){$this->EnableKerbAuth=0;}
		$squid=new squidbee();
		$this->LDAP_EXTERNAL_AUTH=$this->LDAP_EXTERNAL_AUTH_PARSE();
		if($squid->LDAP_AUTH==1){$this->AUTH=1;}
		if($squid->LDAP_EXTERNAL_AUTH==1){$this->AUTH=1;}
		if($squid->EnableKerbAuth==1){$this->AUTH=1;}
		$this->instancetime=time();
		if(!isset($GLOBALS["COMPILE_SQUID_TOKENS"])){$this->compilation_params();}
		if(isset($GLOBALS["COMPILE_SQUID_TOKENS"])){
			if(count($GLOBALS["COMPILE_SQUID_TOKENS"])<3){$this->compilation_params();}
		}
		
	}
	
	
	
	private function LDAP_EXTERNAL_AUTH_PARSE(){
		$ini=new Bs_IniHandler();
		if(!isset($GLOBALS["SQUID_MEMORYCONF"]["INI_ARRAY"])){
			
			$ini->loadString($this->ArticaSquidParameters);
			if(isset($ini->_params)){
				$GLOBALS["SQUID_MEMORYCONF"]["INI_ARRAY"]=$ini->_params;
			}
		}else{
			$ini->_params=$GLOBALS["SQUID_MEMORYCONF"]["INI_ARRAY"];
		}
		
		return intval($ini->_params["NETWORK"]["LDAP_EXTERNAL_AUTH"]);
		
	}
	
	
	private function AllowSmartPhones(){
		$sock=new sockets();
		$SquidAllowSmartPhones=$sock->GET_INFO("SquidAllowSmartPhones");
		$this->acls_array[]="acl MobilePhones browser -i (CFNetwork|iPhone|iPad|Mobile|Android|BlackBerry|PalmSource|SymbianOS|IEMobile|Opera Mobi|Apple-iPhone|Apple-iPad|Apple-iPod)\n";
	}

	
	public function Build_Acls($force=false,$AsMeta=false){
		echo "Starting......: ".date("H:i:s")." [ACLS]: Building ACLS (patched on 2012-10-08)\n";
		$this->defaults_acls();
		$this->Build_Acls_Groups($force,$AsMeta);
		$this->Build_Acls_time($force);
		if($this->EnableArticaHotSpot==1){
			$this->hotspot_outgoing_tos();
		}
		if(!$AsMeta){
			$this->AllowSmartPhones();
		}
	}
	
	private function isDynamicAcls(){
		$q=new mysql_squid_builder();
		$sql="SELECT COUNT( * ) AS TCOUNT FROM (
				SELECT webfilters_sqaclaccess.aclid, webfilters_sqacllinks.gpid, webfilters_sqgroups.ID
				FROM webfilters_sqaclaccess, webfilters_sqacllinks, webfilters_sqgroups
				WHERE webfilters_sqaclaccess.aclid = webfilters_sqacllinks.aclid
				AND webfilters_sqacllinks.gpid = webfilters_sqgroups.ID
				AND webfilters_sqgroups.GroupType = 'dynamic_acls'
				AND webfilters_sqgroups.enabled = 1
		) AS t";		
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		
		if($ligne["TCOUNT"]==0){return false;}
		return true;
		
	}
	
	public function IsObjectAuth(){
		if($this->isRADIUSAuth()){return true;}
		if($this->isADAuth()){return true;}
		if($this->isOpenLDAPAuth()){return true;}
		return false;
	}
	
	public function isValidIPAddress($pattern){
		$IP=new IP();
		if($IP->isIPAddressOrRange($pattern)){return true;}
		if(preg_match("#[0-9\.]+\/[0-9]+-[0-9\.]+$#",$pattern )){return true;}
		if(preg_match("#[0-9\.]+\/[0-9]+-[0-9\.]+\/[0-9]+$#",$pattern )){return true;}
		if(preg_match("#[0-9\.]+-[0-9\.]+\/[0-9]+$#",$pattern )){return true;}
		if(preg_match("#[0-9\.]+-[0-9\.]+$#",$pattern )){return true;}
		return false;
		
	}
	
	public function isRADIUSAuth(){
		if(isset($GLOBALS["squid_acl_isRADIUSAuth"])){return $GLOBALS["squid_acl_isRADIUSAuth"];}
		$q=new mysql_squid_builder();
		$sql="SELECT COUNT( * ) AS TCOUNT FROM (
				SELECT webfilters_sqaclaccess.aclid, webfilters_sqacllinks.gpid, webfilters_sqgroups.ID,webfilters_sqgroups.params
				FROM webfilters_sqaclaccess, webfilters_sqacllinks, webfilters_sqgroups
				WHERE webfilters_sqaclaccess.aclid = webfilters_sqacllinks.aclid
				AND webfilters_sqacllinks.gpid = webfilters_sqgroups.ID
				AND webfilters_sqgroups.GroupType = 'radius_auth'
				AND webfilters_sqgroups.enabled = 1
		) AS t";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		
		if($ligne["TCOUNT"]==0){$GLOBALS["squid_acl_isRADIUSAuth"]=false;return $GLOBALS["squid_acl_isRADIUSAuth"];}
		$GLOBALS["squid_acl_isRADIUSAuth"]=true;
		return true;		
		
	}
	
	public function isADAuth(){
		if(isset($GLOBALS["squid_acl_".__FUNCTION__])){return $GLOBALS["squid_acl_".__FUNCTION__];}
		$q=new mysql_squid_builder();
		$sql="SELECT COUNT( * ) AS TCOUNT FROM (
				SELECT webfilters_sqaclaccess.aclid, webfilters_sqacllinks.gpid, webfilters_sqgroups.ID,webfilters_sqgroups.params
				FROM webfilters_sqaclaccess, webfilters_sqacllinks, webfilters_sqgroups
				WHERE webfilters_sqaclaccess.aclid = webfilters_sqacllinks.aclid
				AND webfilters_sqacllinks.gpid = webfilters_sqgroups.ID
				AND webfilters_sqgroups.GroupType = 'ad_auth'
				AND webfilters_sqgroups.enabled = 1
		) AS t";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	
		if($ligne["TCOUNT"]==0){$GLOBALS["squid_acl_".__FUNCTION__]=false;return $GLOBALS["squid_acl_".__FUNCTION__];}
		$GLOBALS["squid_acl_".__FUNCTION__]=true;
		return true;
	
	}

	public function isOpenLDAPAuth(){
		if(isset($GLOBALS["squid_acl_".__FUNCTION__])){return $GLOBALS["squid_acl_".__FUNCTION__];}
		$q=new mysql_squid_builder();
		$sql="SELECT COUNT( * ) AS TCOUNT FROM (
				SELECT webfilters_sqaclaccess.aclid, webfilters_sqacllinks.gpid, webfilters_sqgroups.ID,webfilters_sqgroups.params
				FROM webfilters_sqaclaccess, webfilters_sqacllinks, webfilters_sqgroups
				WHERE webfilters_sqaclaccess.aclid = webfilters_sqacllinks.aclid
				AND webfilters_sqacllinks.gpid = webfilters_sqgroups.ID
				AND webfilters_sqgroups.GroupType = 'ldap_auth'
				AND webfilters_sqgroups.enabled = 1
		) AS t";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	
		if($ligne["TCOUNT"]==0){$GLOBALS["squid_acl_".__FUNCTION__]=false;return $GLOBALS["squid_acl_".__FUNCTION__];}
		$GLOBALS["squid_acl_".__FUNCTION__]=true;
		return true;
	
	}	
	private function LoadingArtcatz(){
		if(isset($GLOBALS["LoadingArtcatz"])){return $GLOBALS["LoadingArtcatz"];}
		$q=new mysql_squid_builder();
		$sql="SELECT webfilters_sqgroups.*,webfilters_sqacls.aclname
			FROM webfilters_sqgroups, webfilters_sqacls, webfilters_sqacllinks
			WHERE webfilters_sqacllinks.aclid = webfilters_sqacls.ID
			AND webfilters_sqacllinks.gpid = webfilters_sqgroups.ID
			AND webfilters_sqgroups.GroupType = 'categories'
			AND webfilters_sqgroups.enabled =1
			AND webfilters_sqacls.enabled =1";
		$results=$q->QUERY_SQL($sql);
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$GLOBALS["LoadingArtcatz"][$ligne["ID"]]=true;
			
		}
		
		if(!isset($GLOBALS["LoadingArtcatz"])){$GLOBALS["LoadingArtcatz"]=array();}
		
		return $GLOBALS["LoadingArtcatz"];
		
		
	}
	
	public function GetRulesFromADGroup($GroupName){
		$q=new mysql_squid_builder();
		$RULES=array();
		$GroupName=mysql_escape_string2($GroupName);
		$sql="SELECT ID FROM webfilters_sqgroups WHERE (GroupType='proxy_auth_ads' OR GroupType='proxy_auth_statad') AND (GroupName='$GroupName') AND enabled=1";
		
		
		
		$results=$q->QUERY_SQL($sql);
		if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>GetRulesFromADGroup(".mysql_num_rows($results)."):: $sql</span><br>\n";}
		
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$sql="SELECT webfilters_sqacls.aclname,webfilters_sqacls.ID FROM webfilters_sqacls,webfilters_sqacllinks WHERE webfilters_sqacllinks.aclid = webfilters_sqacls.ID AND webfilters_sqacls.enabled =1 AND webfilters_sqacllinks.gpid ={$ligne["ID"]}";
			
			$results2=$q->QUERY_SQL($sql);
			if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>GetRulesFromADGroup(".mysql_num_rows($results2)."):: $sql</span><br>\n";}
			while ($ligne2 = mysql_fetch_assoc($results2)) {
				if($GLOBALS["VERBOSE"]){echo "<span style='color:blue;font-size:22px'>GetRulesFromADGroup:: {$ligne2["aclname"]}!</span><br>\n";}
				$RULES[$ligne2["ID"]]=$ligne2["aclname"];
				
			}
		}
		
		
		$sql="SELECT webfilters_sqgroups.ID FROM webfilters_sqitems,webfilters_sqgroups WHERE webfilters_sqitems.pattern='$GroupName' AND webfilters_sqitems.gpid=webfilters_sqgroups.ID AND webfilters_sqgroups.GroupType='proxy_auth_multiad'";
		$results=$q->QUERY_SQL($sql);
		if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>GetRulesFromADGroup(".mysql_num_rows($results)."):: $sql</span><br>\n";}
		while ($ligne = mysql_fetch_assoc($results)) {
			$sql="SELECT webfilters_sqacls.aclname,webfilters_sqacls.ID FROM webfilters_sqacls,webfilters_sqacllinks WHERE webfilters_sqacllinks.aclid = webfilters_sqacls.ID AND webfilters_sqacls.enabled =1 AND webfilters_sqacllinks.gpid ={$ligne["ID"]}";
			$results2=$q->QUERY_SQL($sql);
			if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>GetRulesFromADGroup(".mysql_num_rows($results2)."):: $sql</span><br>\n";}
			while ($ligne2 = mysql_fetch_assoc($results2)) {
				if($GLOBALS["VERBOSE"]){echo "<span style='color:blue;font-size:22px'>GetRulesFromADGroup:: {$ligne2["aclname"]}!</span><br>\n";}
				$RULES[$ligne2["ID"]]=$ligne2["aclname"];
		
			}
		}
		if($GLOBALS["VERBOSE"]){echo "<span style='color:red;font-size:22px'>GetRulesFromADGroup:: ".count($RULES)." RULES</span><br>\n";}
		return $RULES;
		
	}
	
	
	public function GetWebfilteringRulesFromADGroup($GroupName){
		
		$q=new mysql_squid_builder();
		$sql="SELECT webfilter_group.ID FROM webfilter_group WHERE localldap=2 AND groupname='$GroupName'";
		$results=$q->QUERY_SQL($sql);
		if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>GetWebfilteringRulesFromADGroup(".mysql_num_rows($results)."):: $sql</span><br>\n";}
		while ($ligne = mysql_fetch_assoc($results)) {
			
			$id=$ligne["ID"];
			$sql="SELECT webfilter_rules.groupname,webfilter_rules.ID
					FROM webfilter_assoc_groups,webfilter_rules
					WHERE webfilter_assoc_groups.group_id=$id
					AND webfilter_assoc_groups.webfilter_id=webfilter_rules.ID";
			
			$results2=$q->QUERY_SQL($sql);
			if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>GetWebfilteringRulesFromADGroup(".mysql_num_rows($results2)."):: $sql</span><br>\n";}
			while ($ligne2 = mysql_fetch_assoc($results2)) {
				if($GLOBALS["VERBOSE"]){echo "<span style='color:blue;font-size:22px'>GetWebfilteringRulesFromADGroup:: {$ligne2["aclname"]}!</span><br>\n";}
				$RULES[$ligne2["ID"]]=$ligne2["groupname"];
			
			}
		}
		
		if($GLOBALS["VERBOSE"]){echo "<span style='color:red;font-size:22px'>GetWebfilteringRulesFromADGroup:: ".count($RULES)." RULES</span><br>\n";}
		return $RULES;
		
	}
	
	private function defaults_acls(){
		
		
		
	}
	
	
	private function Build_Acls_Groups($force=false,$asMeta=false){
		if(!$force){
			if(isset($GLOBALS["BUILD_ACLS_EXECUTED"])){
				$this->acls_array=$GLOBALS["BUILD_ACLS_EXECUTED"]["acls_array"];
				$this->availableGroups=$GLOBALS["BUILD_ACLS_EXECUTED"]["availableGroups"];
				return;
			}
		}
		
		$sock=new sockets();
		$EnableLocalUfdbCatService=$sock->GET_INFO("EnableLocalUfdbCatService");
		$EnableSplashScreen=$sock->GET_INFO("EnableSplashScreen");
		$EnableSplashScreenAsObject=$sock->GET_INFO("EnableSplashScreenAsObject");
		if(!is_numeric($EnableSplashScreen)){$EnableSplashScreen=0;}
		if(!is_numeric($EnableSplashScreenAsObject)){$EnableSplashScreenAsObject=0;}
		$SplashScreenURI=$sock->GET_INFO("SplashScreenURI");
		if(trim($SplashScreenURI)==null){$EnableSplashScreen=0;}
		$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
		$RemoteUfdbCat=intval($sock->GET_INFO("RemoteUfdbCat"));
		$URLAR=parse_url($SplashScreenURI);
		if(isset($URLAR["host"])){$SplashScreenURI="http://$SplashScreenURI";}
		if(!preg_match("#^http.*#", $SplashScreenURI)){$SplashScreenURI="http://$SplashScreenURI";}
		
		$CATEGORY_UNSET=false;
		if($SquidPerformance>0){$CATEGORY_UNSET=true;}
		if($EnableLocalUfdbCatService==1){$CATEGORY_UNSET=false;}
		if($RemoteUfdbCat==1){$CATEGORY_UNSET=false;}
		
		
		
		
		$isDynamicAcls=$this->isDynamicAcls();
		$this->LoadingArtcatz();
		
		$ARRAY_AUTH["radius_auth"]=true;
		$ARRAY_AUTH["ad_auth"]=true;
		$ARRAY_AUTH["ldap_auth"]=true;		
		
		$q=new mysql_squid_builder();
		$DirectorySuffix="acls";
		$PrefixGroup="Group";
		
		
		if($asMeta){
			$q=new mysql();
			$DirectorySuffix="acls-meta";
			$PrefixGroup="MetGroup";
		}
		@mkdir("/etc/squid3/$DirectorySuffix",0755,true);
		$sql="SELECT ID,GroupName,GroupType,params FROM webfilters_sqgroups WHERE enabled=1";
		$results = $q->QUERY_SQL($sql,"metaclient");
		$ReturnFirstValue=false;
		while ($ligne = mysql_fetch_assoc($results)) {
			$ItemsPrefix=null;
			$ItemsSuffix=null;
			$acls_name="{$PrefixGroup}{$ligne["ID"]}";
			if($GLOBALS["VERBOSE"]){echo "#\n#\n";}
			echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] $acls_name `{$ligne["GroupName"]}` GroupType {$ligne["GroupType"]}\n";
			
			if($ligne["GroupType"]=="maxconn"){
				$AclsValue=$this->buildItems($ligne["ID"],true);
				$this->availableGroups[$ligne["ID"]]=true;
				echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group: $acls_name `{$ligne["GroupName"]}` maxconn $AclsValue max connection(s)\n";	
				$this->acls_array[]="acl $acls_name {$ligne["GroupType"]} $AclsValue";
				$TEMPLATE=$this->acl_tpl($ligne["ID"]);
				if($TEMPLATE<>null){$this->acls_array[]="deny_info $TEMPLATE $acls_name";}
				continue;
			}
			
			if($ligne["GroupType"]=="arp"){
				if(!$this->ACL_ARP_ENABLED){
					echo "Starting......: ".date("H:i:s")." !! *****\n";
					echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] $acls_name `{$ligne["GroupName"]}` GroupType {$ligne["GroupType"]} will be not available (ARP is not compiled)\n";
					echo "Starting......: ".date("H:i:s")." !! *****\n";
				}
			}			
			
			if($ligne["GroupType"]=="proxy_auth_ads"){
				if($this->EnableKerbAuth==0){echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` GroupType {$ligne["GroupType"]} -> SKIP EnableKerbAuth = 0\n";continue;}
				if($this->SquidUrgency==1){echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` GroupType {$ligne["GroupType"]} -> SKIP Emergency mdode !\n";continue;}
				if($this->ActiveDirectoryEmergency==1){echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` GroupType {$ligne["GroupType"]} -> SKIP Emergency mdode !\n";continue;}
				echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` - {$ligne["ID"]}:FIXED:proxy_auth_ads $acls_name is available\n";
				$this->availableGroups[$ligne["ID"]]=true;
				$this->acls_array[]="acl $acls_name external ads_group ". utf8_encode($ligne["GroupName"]);
				$TEMPLATE=$this->acl_tpl($ligne["ID"]);
				if($TEMPLATE<>null){$this->acls_array[]="deny_info $TEMPLATE $acls_name";}
				continue;				
			}
			
			if($ligne["GroupType"]=="proxy_auth_tagad"){
				if($this->EnableKerbAuth==0){echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` GroupType {$ligne["GroupType"]} -> SKIP EnableKerbAuth = 0\n";continue;}
				if($this->SquidUrgency==1){echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` GroupType {$ligne["GroupType"]} -> SKIP Emergency mdode !\n";continue;}
				if($this->ActiveDirectoryEmergency==1){echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` GroupType {$ligne["GroupType"]} -> SKIP Emergency mdode !\n";continue;}
				echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` - {$ligne["ID"]}:FIXED:proxy_auth_ads $acls_name is available\n";
				$this->availableGroups[$ligne["ID"]]=true;
				$this->acls_array[]="acl tag_{$acls_name} external ads_group TAG:". utf8_encode($ligne["GroupName"]);
				$this->acls_array[]="http_access deny !tag_{$acls_name}";
				$this->acls_array[]="acl {$acls_name} tag ". strtolower(utf8_encode($ligne["GroupName"]));
				$TEMPLATE=$this->acl_tpl($ligne["ID"]);
				if($TEMPLATE<>null){$this->acls_array[]="deny_info $TEMPLATE $acls_name";}
				continue;
			}			
			
			
			
			if($ligne["GroupType"]=="clt_conn_tag"){
				$this->availableGroups[$ligne["ID"]]=true;
				$this->acls_array[]="acl $acls_name note clt_conn_tag ".utf8_encode($ligne["GroupName"]);
				$TEMPLATE=$this->acl_tpl($ligne["ID"]);
				if($TEMPLATE<>null){$this->acls_array[]="deny_info $TEMPLATE $acls_name";}
				continue;
			}
			
			
			if($ligne["GroupType"]=="proxy_auth_multiad"){
				if($this->EnableKerbAuth==0){echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` GroupType {$ligne["GroupType"]} -> SKIP EnableKerbAuth = 0\n";continue;}
				if($this->SquidUrgency==1){echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` GroupType {$ligne["GroupType"]} -> SKIP Emergency mdode !\n";continue;}
				if($this->ActiveDirectoryEmergency==1){echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` GroupType {$ligne["GroupType"]} -> SKIP Emergency mdode !\n";continue;}
				$AclsItemsCount=$this->buildItems($ligne["ID"]);
				if(count($AclsItemsCount)>0){
					echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` - {$ligne["ID"]}:FIXED:proxy_auth_ads $acls_name is available\n";
					$this->availableGroups[$ligne["ID"]]=true;
					$this->acls_array[]="acl $acls_name external ads_group {$ligne["ID"]}";
					$TEMPLATE=$this->acl_tpl($ligne["ID"]);
					if($TEMPLATE<>null){$this->acls_array[]="deny_info $TEMPLATE $acls_name";}
				}
				continue;
			}			
			
			
			if($ligne["GroupType"]=="proxy_auth_ldap"){
				if($this->LDAP_EXTERNAL_AUTH==0){echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` GroupType {$ligne["GroupType"]} -> SKIP EnableKerbAuth = 0\n";continue;}
				if($this->SquidUrgency==1){echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` GroupType {$ligne["GroupType"]} -> SKIP Emergency mdode !\n";continue;}
				echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` - {$ligne["ID"]}:proxy_auth_ldap $acls_name is available\n";
				$this->availableGroups[$ligne["ID"]]=true;
				$this->acls_array[]="# LDAP_EXTERNAL_AUTH=$this->LDAP_EXTERNAL_AUTH [".__LINE__."]";
				$this->acls_array[]="acl $acls_name external ldap_group {$ligne["GroupName"]}";
				$TEMPLATE=$this->acl_tpl($ligne["ID"]);
				if($TEMPLATE<>null){$this->acls_array[]="deny_info $TEMPLATE $acls_name";}
				continue;
			}

			if($ligne["GroupType"]=="ssl_sni"){
				$AclsItemsCount=$this->buildItems($ligne["ID"]);
				if(!isset($GLOBALS["external_acl_type"]["SNIGroup{$ligne["ID"]}"])){continue;}
				$this->availableGroups[$ligne["ID"]]=true;
				continue;
			}
				
			if($ligne["GroupType"]=="proxy_auth_statad"){
				if($this->ActiveDirectoryEmergency==1){echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` GroupType {$ligne["GroupType"]} -> SKIP Emergency mdode !\n";continue;}
				$AclsItemsCount=$this->buildItems($ligne["ID"]);
				if($AclsItemsCount>0){
					if($this->EnableKerbAuth==0){echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` GroupType {$ligne["GroupType"]} -> SKIP EnableKerbAuth = 0\n";continue;}
					echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` - {$ligne["ID"]}:FIXED:proxy_auth_ads $acls_name is available\n";
					$this->availableGroups[$ligne["ID"]]=true;
					$this->acls_array[]="acl $acls_name proxy_auth -i \"/etc/squid3/acls/container_{$ligne["ID"]}.txt\"";
					$TEMPLATE=$this->acl_tpl($ligne["ID"]);
					if($TEMPLATE<>null){$this->acls_array[]="deny_info $TEMPLATE $acls_name";}
				}
				continue;
			}

			
			
			if(preg_match("#time_quota:([0-9]+)#", $ligne["GroupType"],$re)){
				$AclsItemsCount=$this->buildItems_time_quota($ligne["ID"],$re[1]);
				echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` - {$ligne["ID"]}: $acls_name is available ".__CLASS__."/".__FUNCTION__."/".__LINE__."\n";
				$this->availableGroups[$ligne["ID"]]=true;
				$this->acls_array[]="acl $acls_name external ExTimeQuota{$re[1]}";
				$TEMPLATE=$this->acl_tpl($ligne["ID"]);
				if($TEMPLATE<>null){$this->acls_array[]="deny_info $TEMPLATE $acls_name";}
				continue;
			}
			
			if(preg_match("#time_session:([A-Z]+):([0-9]+)#", $ligne["GroupType"],$re)){
				echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` - {$ligne["ID"]}: $acls_name is available ".__CLASS__."/".__FUNCTION__."/".__LINE__."\n";
				$this->availableGroups[$ligne["ID"]]=true;
				$SUFFIX=$re[1];
				if($SUFFIX=="ACTIVE"){$SUFFIX=null;}
				$this->acls_array[]="acl $acls_name external SessionTrack{$re[2]} $SUFFIX";
				$TEMPLATE=$this->acl_tpl($ligne["ID"]);
				if($TEMPLATE<>null){$this->acls_array[]="deny_info $TEMPLATE $acls_name";}
				continue;
			}			
			
			
			
			
			if($ligne["GroupType"]=="maxconn"){
				$AclsItem=$this->buildItems($ligne["ID"],true);
				echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` - {$ligne["ID"]}:FIXED:proxy_auth_ads $acls_name is available\n";
				if($AclsItem<>null){
					$this->availableGroups[$ligne["ID"]]=true;
					$this->acls_array[]="acl $acls_name maxconn $AclsItem";
					$TEMPLATE=$this->acl_tpl($ligne["ID"]);
					if($TEMPLATE<>null){$this->acls_array[]="deny_info $TEMPLATE $acls_name";}
				}
				continue;
			}

			if($ligne["GroupType"]=="max_user_ip"){
				$GLOBALS["authenticate_ip_ttl"]=true;
				$AclsItem=$this->buildItems($ligne["ID"],true);
				echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` - {$ligne["ID"]}:FIXED:proxy_auth_ads $acls_name is available\n";
				if($AclsItem<>null){
					$this->availableGroups[$ligne["ID"]]=true;
					$this->acls_array[]="acl $acls_name max_user_ip $AclsItem";
					$TEMPLATE=$this->acl_tpl($ligne["ID"]);
					if($TEMPLATE<>null){$this->acls_array[]="deny_info $TEMPLATE $acls_name";}
				}
				continue;
			}			
			
			
			if($ligne["GroupType"]=="dynamic_acls"){
				if(!$isDynamicAcls){
					echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` - {$ligne["ID"]}:FIXED:dynamic_acls No dynamic acl available\n";
					continue;
				}
				echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` - {$ligne["ID"]}:FIXED:dynamic_acls $acls_name is available\n";
				$this->availableGroups[$ligne["ID"]]=true;
				$this->acls_array[]="acl $acls_name external DynamicAcls ID{$ligne["ID"]}";
				$TEMPLATE=$this->acl_tpl($ligne["ID"]);
				if($TEMPLATE<>null){$this->acls_array[]="deny_info $TEMPLATE $acls_name";}
				continue;
				
			}		
			
			
			if(isset($ARRAY_AUTH[$ligne["GroupType"]])){
				if($this->SquidUrgency==1){echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` - {$ligne["ID"]}:AUTH: {$ligne["GroupType"]} EMERGENCY ENABLED\n";continue;}
				echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` - {$ligne["ID"]}:AUTH: {$ligne["GroupType"]} $acls_name is available\n";
				if($this->IsObjectAuth()){
					$this->availableGroups[$ligne["ID"]]=true;
					$this->acls_array[]="acl $acls_name external BasicFakeAuth ID{$ligne["ID"]}";
					$TEMPLATE=$this->acl_tpl($ligne["ID"]);
					if($TEMPLATE<>null){$this->acls_array[]="deny_info $TEMPLATE $acls_name";}
					@file_put_contents("/etc/squid3/AuthParams{$ligne[ID]}.conf", $ligne["params"]);
				}
				continue;
			
			}	

			if($ligne["GroupType"]=="categories"){
				
				if($CATEGORY_UNSET){
					$this->acls_array[]="# Group:[$this->instancetime] Categories service is disabled, aborting [".__LINE__."]";
					echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` - Categories service is disabled, aborting\n";
					continue;	
				}				
				
				
				if(!$GLOBALS["ArtcExtrnCategoryScanner"]){
					$this->acls_array[]="# Group:[$this->instancetime] ArtcExtrnCategoryScanner is disabled !!! Why ???, aborting [".__LINE__."]";
					echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` - ArtcExtrnCategoryScanner is disabled !!! Why ???\n";
					continue;
				}
				
				if(!isset($GLOBALS["LoadingArtcatz"][$ligne["ID"]])){
					$this->acls_array[]="# Group:[$this->instancetime] `{$ligne["GroupName"]}` NOT AVAILABLE, aborting [".__LINE__."]";
					echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` NOT AVAILABLE\n";
					continue;
				}
				
				$this->availableGroups[$ligne["ID"]]=true;
				$this->acls_array[]="acl $acls_name external ArtCategories {$ligne["ID"]}";
				$TEMPLATE=$this->acl_tpl($ligne["ID"]);
				if($TEMPLATE<>null){$this->acls_array[]="deny_info $TEMPLATE $acls_name";}
				continue;
			}

			if($ligne["GroupType"]=="tag_categories"){
				if($CATEGORY_UNSET){
					$this->acls_array[]="# Group:[$this->instancetime] Categories service is disabled, aborting [".__LINE__."]";
					echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` - Categories service is disabled, aborting\n";
					continue;
				}
				$AclsItemsCount=$this->buildItems($ligne["ID"],$ReturnFirstValue,$asMeta);
				if($AclsItemsCount>0){
					$this->availableGroups[$ligne["ID"]]=true;
					$this->acls_array[]="acl $acls_name tag \"/etc/squid3/$DirectorySuffix/container_{$ligne["ID"]}.txt\" ";
					$TEMPLATE=$this->acl_tpl($ligne["ID"]);
					if($TEMPLATE<>null){$this->acls_array[]="deny_info $TEMPLATE $acls_name";}
				}
				continue;
				
			}
			
			
			
			if($ligne["GroupType"]=="Smartphones"){
				$this->availableGroups[$ligne["ID"]]=true;
				$this->acls_array[]="acl $acls_name browser -i (CFNetwork|iPhone|iPad|Mobile|Android|BlackBerry|PalmSource|SymbianOS|IEMobile|Opera Mobi|Apple-iPhone|Apple-iPad|Apple-iPod)";
				$TEMPLATE=$this->acl_tpl($ligne["ID"]);
				if($TEMPLATE<>null){$this->acls_array[]="deny_info $TEMPLATE $acls_name";}
				continue;
			}

			
			if($ligne["GroupType"]=="quota_time"){
				if(!$GLOBALS["ArtcExtrnQuotaTimeScanner"]){
					echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` - ArtcExtrnQuotaTimeScanner is disabled !!! Why ???\n";
					continue;
				}
				
				if(!isset($GLOBALS["LoadingExtrnQuotaTime"][$ligne["ID"]])){
					echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` NOT AVAILABLE\n";
					continue;
				}
				
				$this->availableGroups[$ligne["ID"]]=true;
				$this->acls_array[]="acl $acls_name external ArtTime{$ligne["ID"]}";
				$TEMPLATE=$this->acl_tpl($ligne["ID"]);
				if($TEMPLATE<>null){$this->acls_array[]="deny_info $TEMPLATE $acls_name";}
				continue;
		 }
		 
		 if($ligne["GroupType"]=="quota_size"){
		 	if($this->is_ArtQuotaSize()){
		 		$this->availableGroups[$ligne["ID"]]=true;
		 		$this->acls_array[]="acl $acls_name external ArtQuotaSize {$ligne["ID"]}";
		 		$TEMPLATE=$this->acl_tpl($ligne["ID"]);
		 		if($TEMPLATE<>null){$this->acls_array[]="deny_info $TEMPLATE $acls_name";}
		 	}
		 	continue;
		 }		 
			
			
			
			if($ligne["GroupType"]=="NudityScan"){
				if(!$GLOBALS["ArtcExtrnNudeScanner"]){
					echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` - ArtcExtrnNudeScanner is disabled !!! Why ???\n";
					continue;
				}
				
				echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` - {$ligne["ID"]}:FIXED:NudityScan $acls_name is available\n";
				$this->availableGroups[$ligne["ID"]]=true;
				$this->acls_array[]="acl $acls_name external ArtcExtrnNudeScanner";
				$TEMPLATE=$this->acl_tpl($ligne["ID"]);
				if($TEMPLATE<>null){$this->acls_array[]="deny_info $TEMPLATE $acls_name";}
				continue;								
			}
			
			if($ligne["GroupType"]=="teamviewer"){
				$t=new products_ip_ranges(); 
				$t->teamviewer_networks($ligne["ID"]);
				$this->availableGroups[$ligne["ID"]]=true;
				$this->acls_array[]="acl $acls_name dst \"/etc/squid3/acls/teamviewer-nets.acl\"";
				continue;
				
			}
			
			if($ligne["GroupType"]=="whatsapp"){
				$t=new products_ip_ranges();
				$t->whatsapp_networks($ligne["ID"]);
				$this->availableGroups[$ligne["ID"]]=true;
				$this->acls_array[]="acl $acls_name dst \"/etc/squid3/acls/whatsapp-nets.acl\"";
				continue;
			
			}			
			
			if($ligne["GroupType"]=="office365"){
				$t=new products_ip_ranges();
				$t->office365_networks($ligne["ID"]);
				$this->availableGroups[$ligne["ID"]]=true;
				$this->acls_array[]="acl $acls_name dst \"/etc/squid3/acls/office365-nets.acl\"";
				continue;
			
			}			
			
			
			
			if($ligne["GroupType"]=="FTP"){
				$this->acls_array[]="acl $acls_name proto FTP";
				$this->availableGroups[$ligne["ID"]]=true;
				continue;
			}
			
			if($ligne["GroupType"]=="AntiTrack"){
				$t=new products_ip_ranges();
				$t->AntiTrack();
				$this->availableGroups[$ligne["ID"]]=true;
				$this->acls_array[]="acl $acls_name url_regex -i \"/etc/squid3/acls/AntiTrack.acl\"";
				continue;
			}
			
			
			
			
			
			if($ligne["GroupType"]=="skype"){
				$t=new products_ip_ranges();
				$t->skype_networks($ligne["ID"]);
				$this->availableGroups[$ligne["ID"]]=true;
				$this->acls_array[]="acl $acls_name dst \"/etc/squid3/acls/skype-nets.acl\"";
				continue;
			}		

			if($ligne["GroupType"]=="youtube"){
				$t=new products_ip_ranges();
				$t->youtube_networks($ligne["ID"]);
				$this->availableGroups[$ligne["ID"]]=true;
				$this->acls_array[]="acl $acls_name dst \"/etc/squid3/acls/youtube-nets.acl\"";
				continue;
			}			
			
			if($ligne["GroupType"]=="google"){
				$t=new products_ip_ranges();
				$t->google_networks($ligne["ID"]);
				$this->availableGroups[$ligne["ID"]]=true;
				$this->acls_array[]="acl $acls_name dst \"/etc/squid3/acls/google-nets.acl\"";
				continue;
			}
			
			if($ligne["GroupType"]=="dropbox"){
				$t=new products_ip_ranges();
				$t->dropbox_networks($ligne["ID"]);
				$this->availableGroups[$ligne["ID"]]=true;
				$this->acls_array[]="acl $acls_name dst \"/etc/squid3/acls/dropbox-nets.acl\"";
				continue;
			}

			if($ligne["GroupType"]=="google_ssl"){
				$t=new products_ip_ranges();
				$t->google_networks($ligne["ID"]);
				$this->availableGroups[$ligne["ID"]]=true;
				$this->acls_array[]="acl $acls_name dst \"/etc/squid3/acls/google-ssl.acl\"";
				continue;	
			}			
			
			
			if($ligne["GroupType"]=="hotspot_auth"){
					if($EnableSplashScreen==0){
						echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` - hotspot_auth is disabled !!! EnableSplashScreen =0 ???\n";
						continue;
					}
					
					if($EnableSplashScreenAsObject==0){
						echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` - hotspot_auth is disabled !!! EnableSplashScreenAsObject =0 ???\n";
						continue;
					}
				
					echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` - {$ligne["ID"]}:FIXED:NudityScan $acls_name is available\n";
					$this->availableGroups[$ligne["ID"]]=true;
					$this->acls_array[]="acl $acls_name external SplashScreenAuthDef";
					$this->acls_array[]="deny_info 303:$SplashScreenURI?request=%o $acls_name";
					continue;
				}			
			
			
			
			
			if($ligne["GroupType"]=="time"){
					echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` - {$ligne["ID"]}:FIXED:Time \"$acls_name\" is available\n";
					$time=$this->ParseTimeAclsGroup($ligne["ID"]);
					if($time<>null){
						$this->availableGroups[$ligne["ID"]]=true;
						if($GLOBALS["VERBOSE"]){echo "{$ligne["ID"]} \"acl $acls_name time $time\"";}
						$this->acls_array[]="acl $acls_name time $time";
						$TEMPLATE=$this->acl_tpl($ligne["ID"]);
						if($TEMPLATE<>null){$this->acls_array[]="deny_info $TEMPLATE $acls_name";}
					}else{
						echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:[$this->instancetime] `{$ligne["GroupName"]}` SKIP with error\n";
					}
				continue;								
			}			
			
			if($ligne["GroupType"]=="proxy_auth"){
				$ligne["GroupType"]="proxy_auth -i";
				if($this->AUTH==0){
					echo "Starting......: ".date("H:i:s")." [ACLS]: `{$ligne["GroupName"]}` GroupType {$ligne["GroupType"]} -> SKIP AUTH = 0\n";
					continue;
				}				
				
			}
			if($ligne["GroupType"]=="browser"){
				$ligne["GroupType"]="browser -i";
			}
			
			if($ligne["GroupType"]=="req_mime_type"){
				$ligne["GroupType"]="req_mime_type -i";
			}
			if($ligne["GroupType"]=="rep_mime_type"){
				$ligne["GroupType"]="rep_mime_type -i";
			}			
			
			
			if($ligne["GroupType"]=="url_regex"){
				$ligne["GroupType"]="url_regex -i";
			}	
			if($ligne["GroupType"]=="referer_regex"){
				$ligne["GroupType"]="referer_regex -i";
			}
			
			if($ligne["GroupType"]=="url_regex_extensions"){
				$ligne["GroupType"]="url_regex -i";
			}
			
			if($ligne["GroupType"]=="rep_header_filename"){
				$ligne["GroupType"]="rep_header Content-Disposition -i";
				$ItemsPrefix="attachment; filename=.*?";
				$ItemsSuffix="(\"|$)";
			}

			
			$AclsItemsCount=$this->buildItems($ligne["ID"],$ReturnFirstValue,$asMeta,$ItemsPrefix,$ItemsSuffix);
			
			if($ligne["GroupType"]=="all"){
				$this->availableGroups[$ligne["ID"]]=true;
				continue;
			}
							
			
			echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group: $acls_name `{$ligne["GroupName"]}` `$AclsItemsCount` item(s)\n";	
			
			
			if($AclsItemsCount>0){
				$this->availableGroups[$ligne["ID"]]=true;
				echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group: $acls_name is available\n";
				$this->acls_array[]="acl $acls_name {$ligne["GroupType"]} \"/etc/squid3/$DirectorySuffix/container_{$ligne["ID"]}.txt\" ";
				$TEMPLATE=$this->acl_tpl($ligne["ID"]);
				if($TEMPLATE<>null){$this->acls_array[]="deny_info $TEMPLATE $acls_name";}
				$GLOBALS["BUILD_ACLS_EXECUTED"]["acls_array"]=$this->acls_array;
				$GLOBALS["BUILD_ACLS_EXECUTED"]["availableGroups"]=$this->availableGroups;
				
				
			}else{
				echo "Starting......: ".date("H:i:s")." !! *****\n";
				echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group: $acls_name `{$ligne["GroupName"]}` GroupType {$ligne["GroupType"]} will be not available (no item)\n";
				echo "Starting......: ".date("H:i:s")." !! *****\n";
			}
		}
	}
	

	private function is_ArtQuotaSize(){
		if(isset($GLOBALS["is_ArtQuotaSize"])){return $GLOBALS["is_ArtQuotaSize"];}
		$q=new mysql_squid_builder();
		$sql="SELECT webfilters_sqgroups.*,webfilters_sqacls.aclname
			FROM webfilters_sqgroups, webfilters_sqacls, webfilters_sqacllinks
			WHERE webfilters_sqacllinks.aclid = webfilters_sqacls.ID
			AND webfilters_sqacllinks.gpid = webfilters_sqgroups.ID
			AND webfilters_sqgroups.GroupType = 'quota_size'
			AND webfilters_sqgroups.enabled =1
			AND webfilters_sqacls.enabled =1";
		$results=$q->QUERY_SQL($sql);
		$GLOBALS["is_ArtQuotaSize"]=mysql_num_rows($results);
	}

	
	private function acl_tpl($aclid){
		
		$q=new mysql_squid_builder();
		if(!$q->FIELD_EXISTS("webfilters_sqgroups", "tplreset")){
			$q->QUERY_SQL("ALTER TABLE `webfilters_sqgroups` ADD `tplreset` smallint(1) ,ADD INDEX ( `tplreset` ) ");
		}
	
		
		
		if($GLOBALS["VERBOSE"]){echo "Group {$aclid} Checks acltpl...\n";}
		$sql="SELECT acltpl,tplreset FROM webfilters_sqgroups WHERE ID='$aclid'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		
		if(intval($ligne["tplreset"])==1){return "TCP_RESET";}
		if($ligne["acltpl"]==null){if($GLOBALS["VERBOSE"]){echo "Group {$aclid} Checks acltpl == NULL...\n";}return null;}
		

		
		
		if($ligne["acltpl"]=="ARTICA_SLASH_SCREEN"){
			$sock=new sockets();
			$EnableSplashScreen=$sock->GET_INFO("EnableSplashScreen");
			$EnableSplashScreenAsObject=$sock->GET_INFO("EnableSplashScreenAsObject");
			if(!is_numeric($EnableSplashScreen)){$EnableSplashScreen=0;}
			if(!is_numeric($EnableSplashScreenAsObject)){$EnableSplashScreenAsObject=0;}
			$SplashScreenURI=$sock->GET_INFO("SplashScreenURI");
			if(trim($SplashScreenURI)==null){$EnableSplashScreen=0;}
			$URLAR=parse_url($SplashScreenURI);
			if(isset($URLAR["host"])){$SplashScreenURI="http://$SplashScreenURI";}
			if(!preg_match("#^http.*#", $SplashScreenURI)){$SplashScreenURI="http://$SplashScreenURI";}			
			return "303:$SplashScreenURI";
		}
		
		
		$acltpl=$ligne["acltpl"];
		$sql="SELECT template_name,lang,template_link,template_uri,emptytpl FROM squidtpls WHERE `zmd5`='$acltpl'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$tplname=$ligne["template_name"];
		if($GLOBALS["VERBOSE"]){echo "Group {$aclid} Checks acltpl == $tplname...\n";}
		
		
		if(is_numeric($ligne["lang"])){$ligne["lang"]="en";}
		if(!preg_match("#^ERR_.+#", $ligne["template_name"])){$ligne["template_name"]="ERR_".$ligne["template_name"];}
		$template_path="/usr/share/squid-langpack/templates/{$ligne["lang"]}/{$ligne["template_name"]}";
		if($GLOBALS["VERBOSE"]){echo "Group {$aclid} Checks acltpl == $template_path...\n";}
		
		if($ligne["emptytpl"]==1){
			if(!is_file($template_path)){return null;}
			if($GLOBALS["VERBOSE"]){echo "Group {$aclid} Checks acltpl == 200:{$ligne["template_name"]}..\n";}
			return "200:{$ligne["template_name"]}";
		}
		
		
		
		
		if($ligne["template_link"]==1){
			$freeWebsLink="?a=%a&e=%e&ee=%E&www=%H&url=%R&x=%x&T=$acltpl&M=%M";
			$ligne["template_uri"]=str_replace("%FREEWEBS%", $freeWebsLink, $ligne["template_uri"]);
			return $ligne["template_uri"];
		
		}

		
	}
	
	private function ParseTimeAclsGroup($ID){
		$sql="SELECT pattern,other FROM webfilters_sqitems WHERE gpid='$ID'";
		$q=new mysql_squid_builder();
		
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$pattern_data=trim($ligne["pattern"]);
		$t=time();
		$pattern=trim(base64_decode($ligne["other"]));
		if($GLOBALS["VERBOSE"]){echo "ParseTimeAclsGroup(): Group{$ID} Time: pattern = $pattern Alternate: $pattern_data\n";}
		if($pattern==null){
			if($pattern_data<>null){
				return $pattern_data;
			}
		}
		$TimeSpace=unserialize(base64_decode($ligne["other"]));
		if(!is_array($TimeSpace)){return null;}	
		$fromtime=$TimeSpace["H1"];
		$tottime=$TimeSpace["H2"];
		if($fromtime<>null && $tottime<>null){
			$acltime=" $fromtime-$tottime";
		}
		if($fromtime=="00:00" && $tottime=="00:00"){
			$acltime=null;
		}
		
		$days=array("0"=>"M","1"=>"T","2"=>"W","3"=>"H","4"=>"F","5"=>"A","6"=>"S");
		$dd=array();
		while (list ($key, $ligne) = each ($TimeSpace) ){
			if(preg_match("#^day_([0-9]+)#", $key,$re)){
				$dayT=$re[1];
				if($ligne<>1){continue;}
				$dd[]=$days[$dayT];
			}
		}
		if(count($dd)>0){$days_text=@implode($dd, "");}
		
		if($acltime==null && $days_text==null){
			echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group: $ID No time set.. SKIP!!!\n";
			return;
		}
		return "$days_text$acltime";
		
	}

	private function buildItems_time_quota($gpid,$timeid){
		$filename2="/etc/squid3/acls/container_$gpid.txt";
		$filename="/etc/squid3/acls/time_{$timeid}.txt";
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM webfilters_sqitems WHERE enabled=1 AND gpid=$gpid";
		$c=0;
		$results = $q->QUERY_SQL($sql);
		$DATA2=array();
		@unlink($filename2);
		
		$suffix="TIME:$gpid:$timeid ";
		
		if(!$q->ok){
			echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group: $gpid $q->mysql_error !! ". __CLASS__."/".__FUNCTION__."/".__LINE__."\n";
			return 0;
		}
		echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group: $gpid ". mysql_num_rows($results)." items ". __CLASS__."/".__FUNCTION__."/".__LINE__."\n";
		while ($ligne = mysql_fetch_assoc($results)) {
			$ligne["pattern"]=trim($ligne["pattern"]);
			if($ligne["pattern"]==null){continue;}
			if($GLOBALS["VERBOSE"]){echo "Group $gpid {$ligne["pattern"]}\n";}
			if(!preg_match("#^(.*?)\s+(.+)#", $ligne["pattern"],$re)){
				echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group: {$ligne["pattern"]} No match as Time Quota !! ". __CLASS__."/".__FUNCTION__."/".__LINE__."\n";
				continue;
			}
			$identifier=trim($re[1]);
			$budget=trim($re[2]);
			if(strpos($budget,"/")==0){
				echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group: $gpid {$ligne["pattern"]} no budget!!! ". __CLASS__."/".__FUNCTION__."/".__LINE__."\n";
				continue;
			}
			
			if($GLOBALS["VERBOSE"]){echo "$suffix: identifier: $identifier\n";}
			
			$EXPLODED=explode("/",$budget);
			$EXPLODED[0]=trim($EXPLODED[0]);
			$EXPLODED[1]=trim($EXPLODED[1]);
			
			if(preg_match("#AD:(.+)#", $identifier,$re)){
				$ad=new ActiveDirectory();
				if($GLOBALS["VERBOSE"]){echo "$suffix: Get Members from: {$re[1]}\n";}
				$entries=$ad->search_users_from_groupName($re[1]);
				
				if(count($entries)==0){
					if($GLOBALS["VERBOSE"]){echo "$suffix: Active directory {$re[1]} no user found\n";}
					$DATA2[]="# Active directory {$re[1]} no user found";
					continue;
				}
				
				while (list ($num, $uid) = each ($entries) ){
					if($GLOBALS["VERBOSE"]){echo "$suffix:$uid\n";}
					$DATA2[]="$uid {$EXPLODED[0]}/{$EXPLODED[1]}";
					$GLOBALS["buildItems_time_quota"][$timeid][$identifier]="{$EXPLODED[0]}/{$EXPLODED[1]}";
					
				}
				continue;
			}
			
			$DATA2[]="$identifier {$EXPLODED[0]}/{$EXPLODED[1]}";
			$GLOBALS["buildItems_time_quota"][$timeid][$identifier]="{$EXPLODED[0]}/{$EXPLODED[1]}";
		}
		
		if(count($GLOBALS["buildItems_time_quota"][$timeid])==0){
			@unlink($filename);
			@touch($filename);
			return 0;
		}
		@unlink($filename);
		reset($GLOBALS["buildItems_time_quota"][$timeid]);
		while (list ($num, $val) = each ($GLOBALS["buildItems_time_quota"][$timeid])){
			$TT[]="$num $val";
		}
		
		@file_put_contents($filename, @implode("\n", $TT)."\n");
		@file_put_contents($filename2, @implode("\n", $DATA2)."\n");
		return count($TT);
		
		
	}
	
	
	private function buildItems($gpid,$returnFirstValue=false,$asMeta=false,$ItemsPrefix=null,$ItemsSuffix=null){
		
		$filename="/etc/squid3/acls/container_$gpid.txt";
		if($asMeta){$filename="/etc/squid3/acls-meta/container_$gpid.txt";}
		
		@unlink($filename);
		$f=$this->GetItems($gpid,null,$asMeta,$ItemsPrefix,$ItemsSuffix);
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [ACLS]: Group$gpid, ".count($f)." items\n";}
		if($returnFirstValue){
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [ACLS]: Group$gpid, returnFirstValue = true return ".$f[0]." items\n";}
			return $f[0];
		}
		$c=count($f);
		if($c>0){
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [ACLS]: Group$gpid, save $filename\n";}
			@file_put_contents($filename, @implode("\n", $f)."\n");}
		return $c;
	}
	
	private function Build_Acls_time(){
		$days=array("0"=>"M","1"=>"T","2"=>"W","3"=>"H","4"=>"F","5"=>"A","6"=>"S");
		$sql="SELECT * FROM webfilters_sqtimes_rules WHERE enabled=1";
		$q=new mysql_squid_builder();
		$results = $q->QUERY_SQL($sql);	
		while ($ligne = mysql_fetch_assoc($results)) {	
			$TimeSpace=unserialize($ligne["TimeCode"]);
			if(strlen($TimeSpace["BEGINH"])==1){$TimeSpace["BEGINH"]="0{$TimeSpace["BEGINH"]}";}
			if(strlen($TimeSpace["BEGINM"])==1){$TimeSpace["BEGINM"]="0{$TimeSpace["BEGINM"]}";}
			if(strlen($TimeSpace["ENDH"])==1){$TimeSpace["ENDH"]="0{$TimeSpace["ENDH"]}";}
			if(strlen($TimeSpace["ENDM"])==1){$TimeSpace["ENDM"]="0{$TimeSpace["ENDM"]}";}			
			$timeHour="{$TimeSpace["BEGINH"]}:{$TimeSpace["BEGINM"]}-{$TimeSpace["ENDH"]}:{$TimeSpace["ENDM"]}";
			
			$f=array();
			while (list ($num, $val) = each ($TimeSpace["DAYS"]) ){	
				if($num==array()){continue;}
				if(!isset($days[$num])){continue;}
				if($days[$num]==array()){continue;}
				if($val<>1){continue;}
				$f[]= $days[$num];
			}	
			$days_text=trim(@implode("", $f));
			if($days_text==null){$days_text="MTWHFAS";}
			$acls_name="Time{$ligne["ID"]}";
			$this->acls_array[]="acl $acls_name time $days_text $timeHour";
			
		}
	}
	
	
	public function build_http_access(){
		echo "Starting......: ".date("H:i:s")." [ACLS]: build_http_access() ACL Engine Rules:[$this->instancetime] ". count($this->http_access)." items (before)\n"; 
		$this->build_http_access_time();
		echo "Starting......: ".date("H:i:s")." [ACLS]: build_http_access() ACL Engine Rules:[$this->instancetime] ". count($this->http_access)." items (after)\n"; 
		return $this->http_access;
		
	}
	
	private function string_to_regex($pattern){
		if(preg_match("#^regex:(.+)#", trim($pattern),$re)){return $re[1];}
		$pattern=str_replace(".", "\.", $pattern);
		$pattern=str_replace("(", "\(", $pattern);
		$pattern=str_replace(")", "\)", $pattern);
		$pattern=str_replace("+", "\+", $pattern);
		$pattern=str_replace("|", "\|", $pattern);
		$pattern=str_replace("{", "\{", $pattern);
		$pattern=str_replace("}", "\}", $pattern);
		$pattern=str_replace("?", "\?", $pattern);
		$pattern=str_replace("*", ".*?", $pattern);
		$pattern=str_replace("http://", "^http://", $pattern);
		$pattern=str_replace("https://", "^https://", $pattern);
		$pattern=str_replace("ftp://", "^ftp://", $pattern);
		$pattern=str_replace("ftps://", "^ftps://", $pattern);
		return $pattern;
	}
	
	public function dstdomain_parse($website){
		include_once(dirname(__FILE__).'/class.tcpip.inc');
		$website=trim(strtolower($website));
		if($website==null){return null;}
		$ip=new IP();
		if($ip->isIPAddress($website)){return null;}
		if($GLOBALS["VERBOSE"]){echo "dstdomain_parse:: $website\n";}
		

		if(preg_match("#(.*?)\.http$#", $website,$re)){
			if($GLOBALS["VERBOSE"]){echo "dstdomain_parse::".__LINE__." $website -> {$re[1]}\n";}
			$website=$re[1];
		}
		if(preg_match("#^http:\/\/(.*)#", $website,$re)){
			if($GLOBALS["VERBOSE"]){echo "dstdomain_parse::".__LINE__." $website -> {$re[1]}\n";}
			$website=$re[1];
		}
		if(preg_match("#^https:\/\/(.*)#", $website,$re)){
			if($GLOBALS["VERBOSE"]){echo "dstdomain_parse::".__LINE__." $website\n";}
			$website=$re[1];
		}
		if(preg_match("#^ftp:\/\/(.*)#", $website,$re)){
			if($GLOBALS["VERBOSE"]){echo "dstdomain_parse::".__LINE__." $website\n";}
			$website=$re[1];
		}
		if(preg_match("#^ftps:\/\/(.*)#", $website,$re)){
			if($GLOBALS["VERBOSE"]){echo "dstdomain_parse::".__LINE__." $website\n";}
			$website=$re[1];
		}
		if(preg_match("#^www\.(.*)#", $website,$re)){
			if($GLOBALS["VERBOSE"]){echo "dstdomain_parse::".__LINE__." $website\n";}
			$website=$re[1];
		}
		if(preg_match("#^(.+?)\/$#", $website,$re)){
			if($GLOBALS["VERBOSE"]){echo "dstdomain_parse::".__LINE__." $website -> {$re[1]}\n";}
			$website=$re[1];
		}
		if(preg_match("#^(.+?):([0-9]+)#", $website,$re)){$website=$re[1];}
		if(preg_match("#^http.*?:(.+)#", $website,$re)){
			if($GLOBALS["VERBOSE"]){echo "dstdomain_parse::".__LINE__." $website -> {$re[1]}\n";}
			$website=$re[1];
		}
		if(preg_match("#^\.(.+)#", $website,$re)){
			if($GLOBALS["VERBOSE"]){echo "dstdomain_parse::".__LINE__." $website -> {$re[1]}\n";}
			$website=$re[1];
		}	
		
		if(preg_match("#(.+?):(.+)#", $website,$re)){
			if($GLOBALS["VERBOSE"]){echo "dstdomain_parse::".__LINE__." $website\n";}
			$website=$re[1];
		}
		
		if($GLOBALS["VERBOSE"]){echo "dstdomain_parse::".__LINE__." $website\n";}
		$website=str_replace('.*.', '.', $website);
		$website=str_replace('*', '.', $website);
		$website=str_replace('$', '', $website);
		$website=str_replace('(', '', $website);
		$website=str_replace(')', '', $website);
		$website=str_replace(';', '.', $website);
		$website=str_replace(',', '.', $website);
		$website=str_replace('%', '', $website);
		$website=str_replace('+', '', $website);
		$website=str_replace('/', '', $website);
		$website=str_replace('?', '', $website);
		$website=str_replace('!', '', $website);
		$website=str_replace(':', '', $website);
		$website=str_replace('{', '', $website);
		$website=str_replace('}', '', $website);
		$website=str_replace('^', '', $website);
		$website=str_replace('\\', '', $website);
		$website=str_replace('\'', '', $website);
		$website=str_replace('"', '', $website);
		$website=str_replace('&', '', $website);
		$website=str_replace('<', '', $website);
		$website=str_replace('>', '', $website);
		$website=str_replace('[', '', $website);
		$website=str_replace(']', '', $website);
		
		if(strpos($website, ".")==0){return null;}
		
		if($GLOBALS["VERBOSE"]){echo "dstdomain_parse:: $website\n";}
		return $website;
		
	}
	
	
	public function clean_dstdomains($array=array()){
		if(!is_array($array)){return array();}
		if(count($array)==0){return array();}
		$q=new mysql_squid_builder();
		$f=array();
		$MAINARRAY=array();
		$zMasterDomains=array();
		$zsubDomains=array();
		$TRI2=array();

		while (list ($index, $domain) = each ($array) ){
			$domain=strtolower($domain);
			$CLEANEDDOMS[$domain]=$domain;
		}
		ksort($CLEANEDDOMS);
		
		
		while (list ($index, $domain) = each ($CLEANEDDOMS) ){
			$domain=trim(strtolower($domain));
			if($domain==null){continue;}
			$ORG_DOM=$domain;
			$chapeau=0;
			if(substr($domain, 0,1)=="^"){$domain=substr($domain,1,strlen($domain));$chapeau=1;}
			$domain=$this->dstdomain_parse($domain);
			if($domain==null){continue;}
			
			if(isset($zMasterDomains[$domain])){continue;}
			if(isset($zsubDomains[$domain])){continue;}
			$MASTERDOMAIN=$q->GetFamilySites($domain);
			if($GLOBALS["VERBOSE"]){echo "* * [clean_dstdomains] [\"{$ORG_DOM}\"]: BECOME:'$domain' MASTER:$MASTERDOMAIN; CHAP:\"$chapeau\"\n";}
			
			if($MASTERDOMAIN==$domain){
				$MAIN_MASTER[$MASTERDOMAIN][$MASTERDOMAIN]=$chapeau;
				continue;
			}
			
			if(isset($MAIN_MASTER[$MASTERDOMAIN][$MASTERDOMAIN])){continue;}
			$domain=str_replace(".$MASTERDOMAIN", "", $domain);
			if($GLOBALS["VERBOSE"]){echo "* * [clean_dstdomains] [\"{$ORG_DOM}\"]:$domain (".__LINE__.")\n";}
			
			if(strpos($domain, ".")==0){
				$suffix=$domain;
				if(isset($MAIN_MASTER[$MASTERDOMAIN][$suffix])){continue;}
				$MAIN_MASTER[$MASTERDOMAIN][$suffix]=$chapeau;
				continue;
			}
			
			if(strpos($domain, ".")>0){
				$xzdom=explode(".",$domain);
				$suffix=$xzdom[count($xzdom)-1];
				if($GLOBALS["VERBOSE"]){echo "* * [clean_dstdomains] [\"{$ORG_DOM}\"]:$domain -> $suffix (".__LINE__.")\n";}
				if(isset($MAIN_MASTER[$MASTERDOMAIN][$suffix])){continue;}
				$MAIN_MASTER[$MASTERDOMAIN][$suffix]=$chapeau;
				
			}
			
			
			
			$domain=str_replace("$suffix", "", $domain);
			$domain=str_replace("..", ".", $domain);
			if(substr($domain, strlen($domain)-1,1)=="."){$domain=substr($domain, 0,strlen($domain)-1);}
			if($GLOBALS["VERBOSE"]){echo "* * [clean_dstdomains] [\"{$ORG_DOM}\"]:$domain (".__LINE__.")\n";}
			
			if(strpos($domain, ".")==0){
				$suffix2=$domain;
				$chapeau=0;
				if(isset($MAIN_MASTER[$MASTERDOMAIN][$suffix][$suffix2])){continue;}
				$MAIN_MASTER[$MASTERDOMAIN][$suffix][$suffix2]=$chapeau;
				if($GLOBALS["VERBOSE"]){echo "* * [clean_dstdomains] [\"{$ORG_DOM}\"]: [$MASTERDOMAIN][$suffix][$suffix2] (".__LINE__.")\n";}
				continue;
			}
			
		}
			
		while (list ($mainsite, $array) = each ($MAIN_MASTER) ){
			while (list ($subsite, $array2) = each ($array) ){
				if($subsite==$mainsite){
					if($array2==0){$f[]=".$mainsite";continue;}
					$f[]="$mainsite";
					continue;
				}
				
				if(is_array($array2)){
					while (list ($subsite2, $array3) = each ($array2) ){
						if(is_array($array3)){
							while (list ($subsite3, $array4) = each ($array2) ){
								if($array4==0){$f[]=".$subsite3.$subsite2.$subsite.$mainsite";continue;}
								$f[]="$subsite3.$subsite2.$subsite.$mainsite";
								continue;
							}
							continue;
						}
						
						
						
						if($array3==0){$f[]=".$subsite2.$subsite.$mainsite";continue;}
						$f[]="$subsite2.$subsite.$mainsite";
						continue;
						
						
					}
				continue;
				}
				
				
				if($array2==0){$f[]=".$subsite.$mainsite";continue;}
				$f[]="$subsite.$mainsite";
				continue;
				
			}
			
			
			
		}
		
		return $f;
		
		
	}
	
	
	private function GetItems_dstdomain($gpid,$asMeta=false){
		$q=new mysql_squid_builder();
		if($asMeta){$q=new mysql();}
		$array=array();
		$sql="SELECT * FROM webfilters_sqitems WHERE enabled=1 AND gpid=$gpid";
		$results = $q->QUERY_SQL($sql,"metaclient");
		while ($ligne = mysql_fetch_assoc($results)) {
			$ligne["pattern"]=trim(strtolower($ligne["pattern"]));
			$array[]=$ligne["pattern"];
			
		}
			
		return $this->clean_dstdomains($array);
	}
		
	
	public function GetItems($gpid,$required=null,$asMeta=false,$ItemsPrefix=null,$ItemsSuffix=null){
		$q=new mysql_squid_builder();
		if($asMeta){$q=new mysql();}
		$sql="SELECT GroupName,GroupType,enabled FROM webfilters_sqgroups WHERE ID='$gpid'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"metaclient"));
		
		if(!$q->ok){
			echo "Starting......: ".date("H:i:s")." [ACLS]: AsMeta: $asMeta ACL $q->mysql_error \n";
			echo "Starting......: ".date("H:i:s")." [ACLS]: ACL $sql\n";
		}
		$GroupeType=$ligne["GroupType"];
		
		
		if($GroupeType=="proxy_auth_statad"){
			include_once(dirname(__FILE__)."/class.external.ad.inc");
			$GroupName=$ligne["GroupName"];
			echo "Starting......: ".date("H:i:s")." [ACLS]: ACL $GroupeType, parsing $GroupName Active Directory\n";
			$ad=new external_ad_search();
			return $ad->MembersFromGroupName($GroupName);
			
		}
		
		$this->RequestedGroupeType=$GroupeType;
		if($ligne["enabled"]==0){if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:$gpid is disabled skip..\n";}return array();}
		if($required<>null){if($GroupeType<>$required){if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:$gpid {$ligne["GroupType"]} is different than required \"$required\" skip..\n";}return array();}}
		if($GroupeType=="arp"){if(!$this->ACL_ARP_ENABLED){if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group:$gpid ARP is not compiled, skip..\n";}return array();}}
		$sql="SELECT * FROM webfilters_sqitems WHERE enabled=1 AND gpid=$gpid";
		$c=0;
		$f=array();
		$Ipclass=new IP();
		
		if($GroupeType=="dstdomain"){
			return $this->GetItems_dstdomain($gpid,$asMeta);
		}
		
		$already=array();
		$MASTERDOMAINAL=array();
		$results = $q->QUERY_SQL($sql,"metaclient");
		while ($ligne = mysql_fetch_assoc($results)) {
			$ligne["pattern"]=trim($ligne["pattern"]);
			if($ligne["pattern"]==null){continue;}
			if($GLOBALS["VERBOSE"]){echo "Group $gpid {$ligne["pattern"]} Type: $GroupeType\n";}
			if(trim($ligne["pattern"])==null){if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Group: $gpid pattern is null, skip..\n";}continue;}
			
			
			if($GroupeType=="port"){
				if(preg_match("#[a-z]+#", $ligne["pattern"])){
					if($GLOBALS["VERBOSE"]){echo "! ! [$GroupeType] \"{$ligne["pattern"]}\"\n";}
					continue;
				}
				if(!preg_match("#[0-9\-]+#", $ligne["pattern"])){
					if($GLOBALS["VERBOSE"]){echo "! ! [$GroupeType] \"{$ligne["pattern"]}\"\n";}
					continue;
				}
				
			}
			
			if($GroupeType=="arp"){
				$ligne["pattern"]=strtolower($ligne["pattern"]);
				$ligne["pattern"]=str_replace("-", ":", $ligne["pattern"]);
				if(!$Ipclass->IsvalidMAC($ligne["pattern"])){
					if($GLOBALS["VERBOSE"]){echo "! ! [$GroupeType] \"{$ligne["pattern"]}\"\n";}
					continue;
				}
				
			}
			
			

			
			if($GroupeType=="url_regex_extensions"){
				$ligne["pattern"]=str_replace(".", "", $ligne["pattern"]);
				$ligne["pattern"]=$this->string_to_regex($ligne["pattern"]);
				$ligne["pattern"]="\.{$ligne["pattern"]}$";
			}
			
			
			
			if($GroupeType=="arp"){
				$ligne["pattern"]=str_replace("-", ":", $ligne["pattern"]);
			}
			if($GroupeType=="src"){
				if(trim($ligne["pattern"])=="0.0.0.0/0"){$ligne["pattern"]="all";}
				if(trim($ligne["pattern"])=="0.0.0.0"){$ligne["pattern"]="all";}
			}
			
			if($GroupeType=="req_mime_type"){
				if(preg_match("#^regex:(.+)#", $ligne["pattern"],$re)){$ligne["pattern"]=$re[1];}else{	
					$ligne["pattern"]="^{$ligne["pattern"]}$";
				}
			}
			

			if($GroupeType=="url_regex"){
				$ligne["pattern"]=$this->string_to_regex($ligne["pattern"]);
			}
			
			if($GroupeType=="dstdom_regex"){
				$ligne["pattern"]=$this->string_to_regex($ligne["pattern"]);
			}
			
			if($GroupeType=="urlpath_regex"){
				$ligne["pattern"]=$this->string_to_regex($ligne["pattern"]);
			}
			
			if($GroupeType=="referer_regex"){
				$ligne["pattern"]=$this->string_to_regex($ligne["pattern"]);
			}
			
			if($GroupeType=="dst"){
				if(!$Ipclass->isIPAddressOrRange($ligne["pattern"])){
					if($GLOBALS["VERBOSE"]){echo "! ! [$GroupeType] \"{$ligne["pattern"]}\"\n";}
					continue;}
				
			}
			if($GroupeType=="src"){
				if(!$Ipclass->isIPAddressOrRange($ligne["pattern"])){
					if($GLOBALS["VERBOSE"]){echo "! ! [$GroupeType] \"{$ligne["pattern"]}\"\n";}
					continue;}
			
			}
			
			
			if(isset($already[$ligne["pattern"]])){
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [ACLS]: Group$gpid, $GroupeType: {$ligne["pattern"]} SKIP (".__LINE__.")\n";}
				continue;}
			$already[$ligne["pattern"]]=true;
			
			if(preg_match("#AD:(.*?):(.+)#", $ligne["pattern"],$re)){
				$entries=$this->members_from_activedirectory($ligne["pattern"]);
				if($entries==null){continue;}
				$ligne["pattern"]=$entries;
			}	
			if($GLOBALS["VERBOSE"]){echo "* * [$GroupeType] \"{$ligne["pattern"]}\"\n";}		
			$f[]="{$ItemsPrefix}{$ligne["pattern"]}{$ItemsSuffix}";
			$c++;
		}
		return $f;		
	}
	
	private function badIP($ipaddr){
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $ipaddr)){return true;}
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $ipaddr)){return false;}
		return true;
		
	}
	
	private function members_from_activedirectory($dn){
		if(trim($dn)==null){return;}
		if(preg_match("#AD:(.*?):(.+)#", $dn,$re)){
				$dnEnc=$re[2];
				$LDAPID=$re[1];
				$dn=base64_decode($dnEnc);
				if($GLOBALS["VERBOSE"]){writelogs("LDAPID:$LDAPID DN=`$dn`",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				$ad=new ActiveDirectory($LDAPID);
				$USERS=$ad->search_users_from_group($dn,0);
		}else{
			ufdbguard_admin_events("Failed to lookup $dn", __FUNCTION__, __FILE__, __LINE__, "activedirectory");
			return null;
		}
		
		if($GLOBALS["VERBOSE"]){writelogs("Array(USERS) = ".count($USERS)." items",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		
		if(count($USERS)==0){
			ufdbguard_admin_events("Failed to lookup $dn no such user", __FUNCTION__, __FILE__, __LINE__, "activedirectory");
			return null;
		}
		
		while (list ($dn, $Props) = each ($USERS) ){
			$type=$Props["TYPE"];
			if($GLOBALS["VERBOSE"]){writelogs("DN=$dn TYPE=`$type`",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			
			
			
			if($type=="group"){
				if($ad->LDAP_RECURSIVE==1){
					writelogs("Group -> ParseLDAPSubUsersGroups($LDAPID,$dn)",__FUNCTION__,__FILE__,__LINE__);
					$newrow=$this->ParseLDAPSubUsersGroups($LDAPID,$dn);
					if(count($newrow>0)){
						while (list ($a, $b) = each ($newrow) ){$f[]=$b;}
					}
				}
				continue;
			}			
			
			if(trim($Props["uid"]==null)){continue;}
			$Props["uid"]=trim(strtolower($Props["uid"]));
			
			$f[]=$Props["uid"];
			
		}
		
		return @implode("\n", $f)."\n";
	}	
	
	private function ParseLDAPSubUsersGroups($LDAPID,$dn){
		$f=array();
		if($GLOBALS["VERBOSE"]){writelogs("LDAPID: $LDAPID, Parsing DN=$dn ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		$ad=new ActiveDirectory($LDAPID);
		$Array=$ad->search_users_from_group($dn);
		if($GLOBALS["VERBOSE"]){writelogs("DN=$dn items=`". count($Array)."`",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		if($ad->ldap_last_error<>null){writelogs("FAILED to lookup dn $dn",__FUNCTION__,__FILE__,__LINE__);}
		
		if(count($Array)==0){
			writelogs("Lookup dn $dn return no user !!!",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return array();
		}
		
		
		if($GLOBALS["VERBOSE"]){writelogs("DN=$dn START LOOP",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		
		while (list ($dn, $GPARR) = each ($Array) ){
			$dnEnc=base64_encode($dn);
			$type=$GPARR["TYPE"];
			if($GLOBALS["VERBOSE"]){writelogs("DN=$dn type=`$type`",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			
			if($type=="group"){
				if($ad->LDAP_RECURSIVE==1){
					writelogs("Group -> ParseUsersGroups($LDAPID,$dn)",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					$newrow=$this->ParseLDAPSubUsersGroups($LDAPID,$dn);
					if(is_array($newrow)){
						if(count($newrow>0)){
							while (list ($a, $b) = each ($newrow) ){$f[]=$b;}
						}
					}
				}
				continue;
			}
				if($GLOBALS["VERBOSE"]){writelogs("DN=$dn ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				$cn=trim($GPARR["uid"]);
				if(trim($cn)==null){continue;}
				$cn=str_replace(" ", "%20", $cn);
				$f[]=$cn;
			}
		
		
		return $f;
	}		
	
	
	
	private function build_http_access_time(){
		$sql="SELECT webfilters_sqtimes_assoc . * , `webfilters_sqtimes_rules`.`Allow`
		FROM `webfilters_sqtimes_assoc` , `webfilters_sqgroups` , `webfilters_sqtimes_rules`
		WHERE 
			webfilters_sqtimes_assoc.gpid = webfilters_sqgroups.ID
			AND webfilters_sqtimes_rules.ID = webfilters_sqtimes_assoc.TimeRuleID
			AND webfilters_sqgroups.enabled =1
			AND webfilters_sqtimes_rules.enabled =1
			
		ORDER BY webfilters_sqtimes_rules.`Allow` DESC";
		$q=new mysql_squid_builder();
		if(!$q->TABLE_EXISTS("webfilters_sqtimes_assoc")){$q->CheckTables(null,$force=true);}
		$results = $q->QUERY_SQL($sql);	
		
		if(!$q->ok){if(preg_match("#doesn't exist#", $q->mysql_error)){$q->CheckTables();$results = $q->QUERY_SQL($sql);}}
		$alreadyTpl=array();
		if(!$q->ok){echo "\n---------------------\n$q->mysql_error\n$sql\n\nLine: ".__LINE__."\nClass:".__CLASS__."\nFunction: ".__FUNCTION__."\nFile:".__FILE__."\n---------------------\n";}
		while ($ligne = mysql_fetch_assoc($results)) {	
			$Access="deny";
			if($ligne["Allow"]==1){$Access="allow";}
			if(!isset($this->availableGroups[$ligne["gpid"]])){
				echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine Rules Group{$ligne["gpid"]} is empty for Time rule Time{$ligne["TimeRuleID"]}, skip it\n";
				continue;}
			$this->http_access[]="http_access $Access Group{$ligne["gpid"]} Time{$ligne["TimeRuleID"]}";
			if($ligne["Allow"]==0){
				if(!isset($alreadyTpl[$ligne["TimeRuleID"]])){
					$this->deny_info_time($ligne["TimeRuleID"]);
					$this->http_access[]="deny_info ERROR_TIMERULE_{$ligne["TimeRuleID"]} Time{$ligne["TimeRuleID"]}";
					$alreadyTpl[$ligne["TimeRuleID"]]=true;
				}
			}
				
		}
		
		
	}
	
	private function deny_info_time($ID){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT TemplateError,`Allow` FROM webfilters_sqtimes_rules WHERE ID='$ID'"));
		$filename="/usr/share/squid-langpack/en/ERROR_TIMERULE_$ID";
		$filename2="/usr/share/squid-langpack/templates/ERROR_TIMERULE_$ID";
		@unlink($filename);
		if($ligne["Allow"]==1){return;}
	
		$array=unserialize(base64_decode($ligne["TemplateError"]));
		if(!isset($array["TITLE"])){$array["TITLE"]="Access denied for this time";}
		if(!isset($array["ERROR"])){$array["ERROR"]="The requested access could not be allowed";}
		if(!isset($array["EXPLAIN"])){$array["EXPLAIN"]="Your are not allowed to acces to internet at this time";}
		if(!isset($array["REASON"])){$array["REASON"]="Surfing to internet banned";}	
		
		@mkdir("/usr/share/squid-langpack/en",0755,true);
		if(!is_dir("/usr/share/squid-langpack/en/templates")){@mkdir("/usr/share/squid-langpack/en/templates",0755,true);}
		if(!is_dir("/usr/share/squid-langpack/templates")){@mkdir("/usr/share/squid-langpack/templates",0755,true);}
		
		$content="<table class=\"w100 h100\">
		<tr>
		<td class=\"c m\">
		<table style=\"margin:0 auto;border:solid 1px #560000\">
		<tr>
		<td class=\"l\" style=\"padding:1px\">
		<div style=\"width:346px;background:#E33630\">
			<div style=\"padding:3px\">
				<div style=\"background:#BF0A0A;padding:8px;border:solid 1px #FFF;color:#FFF\">
					<div style=\"background:#BF0A0A;padding:8px;border:solid 1px #FFF;color:#FFF\">
						<h1>ERROR: {$array["TITLE"]}</h1>
					</div>
					<div class=\"c\" style=\"font:bold 13px arial;text-transform:uppercase;color:#FFF;padding:8px 0\">Proxy Error</div>
					<div style=\"background:#F7F7F7;padding:20px 28px 36px\"> 
						<div id=\"titles\"> 
							<h1>ERROR</h1> <h2>{$array["ERROR"]}</h2> 
						</div> <hr>  
							<div id=\"content\"> <p>{$array["EXPLAIN"]}</p>  
							<blockquote id=\"error\"> <p><b>{$array["REASON"]}</b></p> </blockquote>  
							<p>Access control configuration prevents your request from being allowed at this time. Please contact your service provider if you feel this is incorrect.</p>  <p>Your cache administrator is <a href=\"mailto:%w%W\">%w</a>.</p> <br> </div>  <hr> <div id=\"footer\"> <p>Generated %T by %h (%s)</p> <!-- %c --> </div> </div></div>
								</div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>";	
		$header=@file_get_contents(dirname(__FILE__)."/databases/squid.default.header.db");
		$newheader=str_replace("{TITLE}", $array["TITLE"], $header);
		$templateDatas="$newheader$content</body></html>";		
		@file_put_contents($filename, $templateDatas);
		@file_put_contents($filename2, $templateDatas);
		
		
	}
	
	
	private function hotspot_outgoing_tos(){
		$MAIN=array();
		$q=new mysql_squid_builder();
		$sql="SELECT ruleid,MasterValue FROM webauth_settings WHERE MasterKey='TOS_VALUE';";
		$results=$q->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {
			$MasterValue=intval($ligne["MasterValue"]);
			$ruleid=$ligne["ruleid"];
			if($MasterValue==0){continue;}
			$MAIN[$ruleid]=$MasterValue;
		}
		if(count($MAIN)==0){return;}
		$ayDscp = array(0 => '{default}',8 => '0x20',10 => '0x28',12 => '0x30',14 => '0x38',16 => '0x40',18 => '0x48',20 => '0x50',22 => '0x58',24 => '0x60',26 => '0x68',28 => '0x70',30 => '0x78',32 => '0x80',34 => '0x88',36 => '0x90',38 => '0x98',40 => '0xA0',46 => '0xB8',48 => '0xC0',56 => '0xE0');
		while (list ($ruleid, $tos) = each ($MAIN)){
			$acls_name="HotSpot{$ruleid}{$tos}";
			$this->acls_array[]="acl $acls_name tag HotspotRule$ruleid";
			$tosHex=$ayDscp[$tos];
			$HTTP_ACCESS[]="tcp_outgoing_tos $tosHex $acls_name" ;
		}
		
		while (list ($ruleid, $tos) = each ($HTTP_ACCESS)){
			$this->acls_array[]=$tos;
		}
		
	}
	
	
	private function compilation_params(){
		if(!$this->ASROOT){return;}
		if(isset($GLOBALS["COMPILE_SQUID_TOKENS"])){return $GLOBALS["COMPILE_SQUID_TOKENS"];}
		$unix=new unix();
		$squidbin=$unix->find_program("squid");
		if($squidbin==null){$squidbin=$unix->find_program("squid3");}
		exec("$squidbin -v 2>&1",$results);
		$text=@implode("\n", $results);
		if(preg_match("#configure options:\s+(.+)#is", $text,$re)){$text=$re[1];}
		if(preg_match_all("#'(.+?)'#is", $text, $re)){
			while (list ($index, $line) = each ($re[1])){
				if(preg_match("#(.+?)=(.*)#", $line,$ri)){
					$key=$ri[1];
					$value=$ri[2];
					$key=str_replace("--", "", $key);
					if(trim($value)==null){$value=true;}
					$GLOBALS["COMPILE_SQUID_TOKENS"][$key]=$value;
					continue;
				}
				$key=$line;
				$value=1;
				$key=str_replace("--", "", $key);
				if($GLOBALS["VERBOSE"]){echo "squid -v [$key] = `$value`\n";}
				$GLOBALS["COMPILE_SQUID_TOKENS"][$key]=$value;
			}
				
		}
		return $GLOBALS["COMPILE_SQUID_TOKENS"];
	}	
	
	

}