<?php
if(!isset($GLOBALS["AS_ROOT"])){$GLOBALS["AS_ROOT"]=false;}
include_once(dirname(__FILE__).'/class.dansguardian.inc');
include_once(dirname(__FILE__).'/class.kav4proxy.inc');
include_once(dirname(__FILE__).'/class.mysql.inc');
include_once(dirname(__FILE__).'/class.sockets.inc');
include_once(dirname(__FILE__).'/class.samba.inc');	
include_once(dirname(__FILE__)."/class.c-icap-filter.inc");
include_once(dirname(__FILE__)."/class.icap.inc");		
include_once(dirname(__FILE__)."/class.squid.bandwith.inc");
include_once(dirname(__FILE__)."/class.computers.inc");
include_once(dirname(__FILE__)."/class.squid.acls.inc");
include_once(dirname(__FILE__)."/class.ecap.inc");
include_once(dirname(__FILE__)."/class.system.network.inc");
include_once(dirname(__FILE__)."/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/class.squid.acls.useragents.inc");
include_once(dirname(__FILE__)."/class.squid.booster.inc");
include_once(dirname(__FILE__)."/class.squidworkers.inc");
include_once(dirname(__FILE__)."/class.system.nics.inc");
include_once(dirname(__FILE__)."/class.haarp.inc");
include_once(dirname(__FILE__)."/class.squid.caches.rules.inc");
include_once(dirname(__FILE__)."/class.squid.caches.inc");
include_once(dirname(__FILE__)."/class.products-ip-ranges.inc");
include_once(dirname(__FILE__)."/class.tcpip.inc");
include_once(dirname(__FILE__)."/class.squid.meta.inc");
include_once(dirname(__FILE__)."/class.compile.ufdbguard.inc");
include_once(dirname(__FILE__)."/class.squid.access.manager.inc");
include_once(dirname(__FILE__)."/class.squid.external.ldap.inc");
include_once(dirname(__FILE__)."/class.squid.ssl.inc");
include_once(dirname(__FILE__)."/class.squid.freewebs.inc");
include_once(dirname(__FILE__)."/class.squid.externals.acls.inc");
include_once(dirname(__FILE__)."/class.squid.childs.inc");
include_once(dirname(__FILE__)."/class.squid.acls.cache_deny.inc");
include_once(dirname(__FILE__)."/class.squid.ecap.inc");
include_once(dirname(__FILE__)."/class.squid.hypercache.inc");
include_once(dirname(__FILE__)."/class.squid.checks.inc");
class squid{
	

}
class squidbee{
	public $CURRENT_PROGRESS=0;
	private $CORP_LICENSE=false;
	public $MAX_PROGRESS=0;
	private $IS_VERS_LOGS=null;
	public $dn;
	public $network_array=array();
	public $dns_array=array();
	public $squid_conf;
	public $ArticaSquidParameters;
	private $SquidReverseDefaultWebSite=null;
	private $EnableSquidSSLCRTD=0;
	public $ldap_error;
	public $listen_port;
	public $second_listen_port=0;
	public $smartphones_port=0;
	public $visible_hostname;
	public $enable_kavproxy;
	public $enable_cicap;
	public $enable_dansguardian;
	public $enable_squidguard;
	public $enable_UfdbGuard;
	public $LDAP_AUTH=0;
	public $NTLM_AUTH=0;
	public $SSL_BUMP=0;
	public $EnableClamavInCiCap=1;
	public $CACHE_SIZE=2000;
	public $CACHE_PATH;
	public $CACHE_TYPE="aufs";
	public $alt_listen_port;
	public $kav_accept=false;
	private $AdminAsSeenNTLMPerfs=0;
	public $kav_accept_why='';
	public $SquidBlockSites='';
	public $array_block_istes=array();
	public $global_conf_array=array();
	public $cache_list=array();
	public $acl_times=array();
	public $hasProxyTransparent;
	private $SquidUsersNumber=0;
	public $EnableTransparent27=0;
	public $ASROOT=false;
	public $SQUID_BIN_VERSION=0;
	public $sslbump_token_debug=array();
	public $SQUID_ICAP_ENABLED=false;
	public $SQUID_VERSION="";
	public $KAV4PROXY_INSTALLED=false;
	public $EnableParentProxy=0;
	public $prefer_direct=0;
	public $nonhierarchical_direct=0;
	public $LDAP_EXTERNAL_AUTH=0;
	public $EXTERNAL_LDAP_AUTH_PARAMS=array();
	public $IS_27=false;
	public $IS_30=false;
	public $IS_31=false;
	public $IS_32=false;
	public $IS_33=false;
	public $IS_34=false;
	public $IS_35=false;
	public $IS_40=false;
	public $intvalVersion=false;
	public $ICAP_SERVICES_COUNT=0;
	public $FTP_PARAMS=array();
	public $wccp2_enabled=0;
	public $SquidWCCPL3Enabled=0;
	public $wccp2_router=null;
	public $wccp2_forwarding_method=1;
	public $wccp2_return_method=1;
	public $wccp2_assignment_method="hash";
	public $enable_ftp_restrictions=0;
	public $EnableUserAgentBanAll=0;
	public $enable_adzapper=0;
	public $enable_squidclamav=0;
	public $enable_metascanner=0;
	public $enable_streamcache=0;
	public $ACL_ARP_ENABLED=false;
	public $EnableChangeRequestSize=0;
	public $allow_squid_localhost=0;
	public $ignore_expect_100=0;
	public $EnableKerbAuth=0;
	public $EnableKavICAPRemote=0;
	public $KavICAPRemoteAddr=null;
	public $KavICAPRemotePort=0;
	public $SquidDisableAllFilters=0;
	public $url_rewrite_bypass=0;
	public $ICP_PORT=0;
	public $HTCP_PORT=0;
	public $enable_ecapav=0;
	public $CPU_NUMBER;
	public $uuid;
	public $EnableICPPort=0;
	public $UseTProxyMode=0;
	public $store_dir_minsize=null;
	public $SquidBoosterOnly=0;
	public $ssl_port=0;
	public $certificate_center=null;
	public $second_listen_portForTransparent=0;
	public $EnableKerberosAuthentication=0;
	public $SSL_BUMP_WHITE_LIST=0;
	public $EnableSquidCSV=0;
	public $SquidActHasReverse=0;
	public $EnableSplashScreen=0;
	public $AsSquidLoadBalancer=0;
	Public $LogsWarninStop=0;
	
	public $max_filedesc=8192;
	public $snmp_community=null;
	public $snmp_enable=0;
	public $snmp_port=3401;
	public $snmp_access_ip=null;
	public $SquidLdapAuthEnableGroups=0;
	public $EnableRemoteStatisticsAppliance=0;
	private $TRANSPARENT_PORTS_LIST=array();
	public $forwarded_for="transparent";
	private $ProxyDedicateMicrosoftRules=0;
	private $AntivirusEnabledCentral=1;
	private $UfdbEnabledCentral=1;	
	private $Kav4ProxyEnabledCentral=0;
	private $EnableMacAddressFilterCentral=1;
	private $EnableMacAddressFilter=1;
	private $SquidBubbleMode=0;
	private $MyPortsArray=array();
	private $MyStandPort=null;
	private $SquidClientParams=array();
	private $ForceWindowsUpdateCaching=0;
	private $MGR_LISTEN_PORT=0;
	private $ChilliEnabled=false;
	private $DisableAnyCache=0;
	private $DisableSSLStandardPort=0;
	public $SquidBinIpaddr=null;
	private $WCCPListenPort=0;
	private $SquidUrgency=0;
	private $ActiveDirectoryEmergency=0;
	private $BasicAuthenticatorEmergency=0;
	private $CacheManagement2=1;
	private $ArticaHotSpotPort=0;
	private $ArticaSSLHotSpotPort=0;
	private $UtDNSEnable=0;
	private $SquidEnableIdentdService=0;
	private $SquidEnableIdentdServiceOnly=0;
	private $SquidSimpleConfig=0;
	private $KerbAuthInfos=array();
	
	public $peer_connect_timeout=30;
	public $connect_timeout=120;
	public $persistent_request_timeout=60;
	public $client_lifetime=60000;
	public $forward_max_tries=30;
	public $forward_timeout=240;
	public $pconn_timeout=120;
	public $dns_timeout=120;
	public $dead_peer_timeout=10;
	public $shutdown_lifetime=1;
	public $read_timeout=900;
	public $incoming_rate=30;	
	private $SQUID_REVERSE_APPLIANCE;
	private $SquidExternalAclQuota=0;
	private $CnTLMPORT=0;
	private $EnableCNTLM=0;
	private $second_port_transparent=null;
	private $transparent=null;
	private $sslcrtd_program=null;
	private $DisableWinbindd=0;
	private $WCCPHash=array();
	private $SQUID_VERSION_CALC;
	private $SQUID_VERSIONS_LOGS=array();
	private $BuildSquidCertificateCRTD_LOGS=array();
	
	public $nodes_names=array();
	
	function squidbee(){
		
		if(posix_getuid()==0){$this->ASROOT=true;}
		$GLOBALS["VERBOSED"]=false;
		if(!$this->ASROOT){if($GLOBALS["VERBOSE"]){$GLOBALS["VERBOSED"]=true;}}
		$this->VerifyLdapBranch();
		if($GLOBALS["VERBOSED"]){echo __LINE__.":: ->ParseConfig()<br>\n";}
		$this->ParseConfig();
		$this->kav_accept=$this->isicap();
		$sock=new sockets();
		$this->uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));
		if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
		$users=$GLOBALS["CLASS_USERS"];
		$this->CPU_NUMBER=$users->CPU_NUMBER;
		$this->SQUID_BIN_VERSION=$users->SQUID_BIN_VERSION;
		if($this->ASROOT){
				$this->SQUID_VERSION=$this->root_squid_version_nocache();
				$this->SQUID_VERSION_CALC="As root";
			}else{$this->SQUID_VERSION=@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidVersion");}
		$this->SQUID_ICAP_ENABLED=$users->SQUID_ICAP_ENABLED;
		$this->KAV4PROXY_INSTALLED=$users->KAV4PROXY_INSTALLED;
		$this->MEMORY_INSTALLEE=$users->MEM_TOTAL_INSTALLEE;
		$this->CORP_LICENSE=$users->CORP_LICENSE;

		$EnableClamavInCiCap=$sock->GET_INFO("EnableClamavInCiCap");
		if($EnableClamavInCiCap==null){$EnableClamavInCiCap=1;$sock->SET_INFO("EnableClamavInCiCap","1");}
		$this->enable_UfdbGuard=$this->EnableUfdbGuard();
		$this->enable_adzapper=$sock->GET_INFO("EnableAdZapper");
		$this->enable_squidclamav=$sock->GET_INFO("EnableSquidClamav");
		$this->EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
		$this->EnableKavICAPRemote=$sock->GET_INFO("EnableKavICAPRemote");
		$this->KavICAPRemoteAddr=$sock->GET_INFO("KavICAPRemoteAddr");
		$this->KavICAPRemotePort=$sock->GET_INFO("KavICAPRemotePort");
		$this->enable_metascanner=$sock->GET_INFO("KavMetascannerEnable");
		$this->enable_streamcache=$sock->GET_INFO("SquidEnableStreamCache");
		$this->SquidDisableAllFilters=$sock->GET_INFO("SquidDisableAllFilters");
		$this->enable_ecapav=$sock->GET_INFO("SquideCapAVEnabled");
		$this->UseTProxyMode=$sock->GET_INFO("UseTProxyMode");
		$this->EnableSquidCSV=$sock->GET_INFO("EnableSquidCSV");
		$this->EnableKerberosAuthentication=$sock->GET_INFO("EnableKerberosAuthentication");
		$this->EnableSplashScreen=$sock->GET_INFO("EnableSplashScreen");
		$this->SquidActHasReverse=$sock->GET_INFO("SquidActHasReverse");
		$this->AsSquidLoadBalancer=$sock->GET_INFO("AsSquidLoadBalancer");
		$this->UfdbEnabledCentral=$sock->GET_INFO("UfdbEnabledCentral");
		$this->AntivirusEnabledCentral=$sock->GET_INFO("AntivirusEnabledCentral");
		$this->Kav4ProxyEnabledCentral=$sock->GET_INFO("Kav4ProxyEnabledCentral");
		$this->EnableMacAddressFilter=$sock->GET_INFO("EnableMacAddressFilter");
		$this->EnableMacAddressFilterCentral=$sock->GET_INFO("EnableMacAddressFilterCentral");
		$this->SquidBubbleMode=$sock->GET_INFO("SquidBubbleMode");
		$this->LogsWarninStop=intval($sock->GET_INFO("LogsWarninStop"));
		$this->AdminAsSeenNTLMPerfs=intval($sock->GET_INFO("AdminAsSeenNTLMPerfs"));
		$this->SquidClientParams=unserialize(base64_decode($sock->GET_INFO("SquidClientParams")));
		$this->ForceWindowsUpdateCaching=$sock->GET_INFO("ForceWindowsUpdateCaching");
		$this->SquidReverseDefaultWebSite=$sock->GET_INFO("SquidReverseDefaultWebSite");
		$this->SquidExternalAclQuota=$sock->GET_INFO("SquidExternalAclQuota");
		$this->DisableSSLStandardPort=$sock->GET_INFO("DisableSSLStandardPort");
		
		$this->smartphones_port=$sock->GET_INFO("smartphones_port");
		$this->EnableSquidSSLCRTD=$sock->GET_INFO("EnableSquidSSLCRTD");
		if($this->SquidReverseDefaultWebSite==null){$this->SquidReverseDefaultWebSite=$this->visible_hostnameF();}
		$this->DisableWinbindd=$sock->GET_INFO("DisableWinbindd");
		$this->SquidUrgency=$sock->GET_INFO("SquidUrgency");
		$this->ActiveDirectoryEmergency=intval($sock->GET_INFO("ActiveDirectoryEmergency"));
		$this->BasicAuthenticatorEmergency=intval($sock->GET_INFO("BasicAuthenticatorEmergency"));
		$this->ProxyDedicateMicrosoftRules=$sock->GET_INFO("ProxyDedicateMicrosoftRules");
		$this->WCCPListenPort=$sock->GET_INFO("WCCPListenPort");
		$this->CacheManagement2=1;
		$this->UtDNSEnable=intval($sock->GET_INFO("UtDNSEnable"));
		$this->SquidEnableIdentdService=intval($sock->GET_INFO("SquidEnableIdentdService"));
		$this->SquidEnableIdentdServiceOnly=intval($sock->GET_INFO("SquidEnableIdentdServiceOnly"));
		$this->SquidSimpleConfig=$sock->GET_INFO("SquidSimpleConfig");
		$this->SquidUsersNumber=intval($sock->GET_INFO("SquidUsersNumber"));
		if(!is_numeric($this->SquidSimpleConfig)){$this->SquidSimpleConfig=1;}
		$this->KerbAuthInfos=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
		
		
		if(!is_numeric($this->ProxyDedicateMicrosoftRules)){$this->ProxyDedicateMicrosoftRules=0;}
		if(!is_numeric($this->DisableWinbindd)){$this->DisableWinbindd=0;}
		if($this->KavICAPRemoteAddr==null){$this->EnableKavICAPRemote=0;}
		if(!is_numeric($this->SquidExternalAclQuota)){$this->SquidExternalAclQuota=0;}
		if(!is_numeric($this->DisableSSLStandardPort)){$this->DisableSSLStandardPort=1;}
		if(!is_numeric($this->KavICAPRemotePort)){$this->EnableKavICAPRemote=0;}
		if(!is_numeric($this->AsSquidLoadBalancer)){$this->AsSquidLoadBalancer=0;}
		if(!is_numeric($this->SquidActHasReverse)){$this->SquidActHasReverse=0;}
		if(!is_numeric($this->EnableKavICAPRemote)){$this->EnableKavICAPRemote=0;}
		if(!is_numeric($this->EnableKerbAuth)){$this->EnableKerbAuth=0;}
		if(!is_numeric($this->SquidBubbleMode)){$this->SquidBubbleMode=0;}
		if(!is_numeric($this->enable_metascanner)){$this->enable_metascanner=0;}
		if(!is_numeric($this->SquidDisableAllFilters)){$this->SquidDisableAllFilters=0;}
		if(!is_numeric($this->UseTProxyMode)){$this->UseTProxyMode=0;}
		if(!is_numeric($this->EnableSquidCSV)){$this->EnableSquidCSV=0;}
		if(!is_numeric($this->UfdbEnabledCentral)){$this->UfdbEnabledCentral=1;}
		if(!is_numeric($this->AntivirusEnabledCentral)){$this->AntivirusEnabledCentral=1;}
		if(!is_numeric($this->EnableKerberosAuthentication)){$this->EnableKerberosAuthentication=0;}
		if(!is_numeric($this->EnableSplashScreen)){$this->EnableSplashScreen=0;}
		if(!is_numeric($this->Kav4ProxyEnabledCentral)){$this->Kav4ProxyEnabledCentral=0;}
		if(!is_numeric($this->EnableMacAddressFilter)){$this->EnableMacAddressFilter=1;}
		if(!is_numeric($this->EnableMacAddressFilterCentral)){$this->EnableMacAddressFilterCentral=1;}
		
		if(!is_numeric($this->smartphones_port)){$this->smartphones_port=0;}
		if(!is_numeric($this->EnableSquidSSLCRTD)){$this->EnableSquidSSLCRTD=1;}
		if(!is_numeric($this->CacheManagement2)){$this->CacheManagement2=1;}
		if(!is_numeric($this->UtDNSEnable)){$this->UtDNSEnable=0;}
		
		
		
		if($this->smartphones_port>0){$this->SquidAllowSmartPhones=0;}
		if($this->EnableKerberosAuthentication==1){$this->EnableKerbAuth=0;}
		
		if(!is_numeric($this->SquidUrgency)){$this->SquidUrgency=0;}
		if(!isset($this->KerbAuthInfos["LDAP_NONTLM_DOMAIN"])){$this->KerbAuthInfos["LDAP_NONTLM_DOMAIN"]=null;}
		if(!isset($this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"])){$this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]=null;}
		
		
		
		if(!isset($this->SquidClientParams["auth_param_ntlm_children"])){$this->SquidClientParams["auth_param_ntlm_children"]=20;}
		if(!isset($this->SquidClientParams["auth_param_ntlm_startup"])){$this->SquidClientParams["auth_param_ntlm_startup"]=1;}
		if(!isset($this->SquidClientParams["auth_param_ntlm_idle"])){$this->SquidClientParams["auth_param_ntlm_idle"]=1;}
		
		if(!isset($this->SquidClientParams["auth_param_basic_children"])){$this->SquidClientParams["auth_param_basic_children"]=3;}
		if(!isset($this->SquidClientParams["auth_param_basic_startup"])){$this->SquidClientParams["auth_param_basic_startup"]=1;}
		if(!isset($this->SquidClientParams["auth_param_basic_idle"])){$this->SquidClientParams["auth_param_basic_idle"]=1;}
		
		if(!isset($this->SquidClientParams["url_rewrite_children"])){$this->SquidClientParams["url_rewrite_children"]=10;}
		if(!isset($this->SquidClientParams["url_rewrite_startup"])){$this->SquidClientParams["url_rewrite_startup"]=1;}
		if(!isset($this->SquidClientParams["url_rewrite_idle"])){$this->SquidClientParams["url_rewrite_idle"]=1;}
		
		if(!isset($this->SquidClientParams["external_acl_children"])){$this->SquidClientParams["external_acl_children"]=5;}
		if(!isset($this->SquidClientParams["external_acl_startup"])){$this->SquidClientParams["external_acl_startup"]=1;}
		if(!isset($this->SquidClientParams["external_acl_idle"])){$this->SquidClientParams["external_acl_idle"]=1;}		
		
		if(!is_numeric($this->SquidClientParams["auth_param_ntlm_children"])){$this->SquidClientParams["auth_param_ntlm_children"]=20;}
		if(!is_numeric($this->SquidClientParams["auth_param_ntlm_startup"])){$this->SquidClientParams["auth_param_ntlm_startup"]=1;}
		if(!is_numeric($this->SquidClientParams["auth_param_ntlm_idle"])){$this->SquidClientParams["auth_param_ntlm_idle"]=1;}
		
		if(!is_numeric($this->SquidClientParams["auth_param_basic_children"])){$this->SquidClientParams["auth_param_basic_children"]=3;}
		if(!is_numeric($this->SquidClientParams["auth_param_basic_startup"])){$this->SquidClientParams["auth_param_basic_startup"]=2;}
		if(!is_numeric($this->SquidClientParams["auth_param_basic_idle"])){$this->SquidClientParams["auth_param_basic_idle"]=1;}
		
		if(!is_numeric($this->SquidClientParams["url_rewrite_children"])){$this->SquidClientParams["url_rewrite_children"]=10;}
		if(!is_numeric($this->SquidClientParams["url_rewrite_startup"])){$this->SquidClientParams["url_rewrite_startup"]=1;}
		if(!is_numeric($this->SquidClientParams["url_rewrite_idle"])){$this->SquidClientParams["url_rewrite_idle"]=1;}
		
		if(!is_numeric($this->SquidClientParams["external_acl_children"])){$this->SquidClientParams["external_acl_children"]=5;}
		if(!is_numeric($this->SquidClientParams["external_acl_startup"])){$this->SquidClientParams["external_acl_startup"]=1;}
		if(!is_numeric($this->SquidClientParams["external_acl_idle"])){$this->SquidClientParams["external_acl_idle"]=1;}		
		if($this->SquidClientParams["external_acl_startup"]<1){$this->SquidClientParams["external_acl_startup"]=2;}
		
		if($this->ASROOT){
			$this->sslcrtd_program=$this->sslcrtd_program_path();
		}
		
		//Disable StreamCache Patch 2012-11-20
		$this->enable_streamcache=0;
		$this->WCCPHash=unserialize(base64_decode($sock->GET_INFO("WCCPHash")));
		
		if( (!is_numeric($this->WCCPListenPort)) OR ($this->WCCPListenPort<1024) ) {
			$this->WCCPListenPort=rand(18999, 65550);
			$sock->SET_INFO("WCCPListenPort", $this->WCCPListenPort);
		}
		
		
		$users=new usersMenus();
		if($users->SQUID_REVERSE_APPLIANCE){
			$this->SquidActHasReverse=1;
			$this->ssl_port=443;
			$this->SSL_BUMP=1;
		}
		
		
		
		
		$wks=new squidworkers();
		$this->CheckVersion();
		$this->hasProxyTransparent=intval($sock->GET_INFO("hasProxyTransparent"));
		$this->EnableTransparent27=intval($sock->GET_INFO("EnableTransparent27"));
		
		$this->SquidBoosterOnly=$wks->zcheck_squidbooster_value();
		$this->EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
		if(!is_numeric($this->EnableRemoteStatisticsAppliance)){$this->EnableRemoteStatisticsAppliance=0;}	
		if($this->EnableRemoteStatisticsAppliance==1){
			$this->enable_UfdbGuard=$this->UfdbEnabledCentral;
			$this->EnableKavICAPRemote=$this->Kav4ProxyEnabledCentral;
			$this->enable_cicap=$this->AntivirusEnabledCentral;
			$this->enable_kavproxy=$this->Kav4ProxyEnabledCentral;
			$this->EnableMacAddressFilter=$this->EnableMacAddressFilterCentral;
			$this->enable_squidclamav=0;
		}
		if($this->SquidActHasReverse==1){$this->enable_UfdbGuard=0;}
		if($this->AsSquidLoadBalancer==1){$this->EnableParentProxy=1;}
		
		
		if(!$this->IS_33){
			if($this->hasProxyTransparent==1){$this->EnableSquidSSLCRTD=0;}
		}
		if($this->SSL_BUMP==1){
			if($this->ssl_port==0){
				$this->ssl_port=$this->get_ssl_port();
			}
		}
		
		
		// Urgency ********************* always at end.
		if($this->SquidUrgency==1){
			$this->SquidEnableIdentdService=0;
			$this->enable_UfdbGuard=0;
			$this->DisableAnyCache=1;
			$this->SquidBubbleMode=0;
			$this->SquidDisableAllFilters=1;
		}
		// Urgency ********************* always at end.
		
		
		if($this->SquidEnableIdentdService==0){$this->SquidEnableIdentdServiceOnly=0;}
		
		
	}
	
	private function EnableUfdbGuard(){
		$sock=new sockets();
		$LogsWarninStop=$sock->GET_INFO("LogsWarninStop");
		if($LogsWarninStop==1){return 0;}
		if($this->EnableTransparent27==1){return 0;}
		return $sock->EnableUfdbGuard();
		
	}

	private function sslcrtd_program_path(){
		if($this->SquidUrgency==1){return null;}
		if(isset($GLOBALS["sslcrtd_program"])){return $GLOBALS["sslcrtd_program"];}
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
		 $GLOBALS["sslcrtd_program"] = $unix->squid_locate_generic_bin("ssl_crtd");
		return  $GLOBALS["sslcrtd_program"];
	}
	
	
	function VerifStreamProxyBindIP(){
		$unix=new unix();
		$sock=new sockets();
		$StreamCacheBindProxy_src=$sock->GET_INFO("StreamCacheBindProxy");
		if($StreamCacheBindProxy_src=="127.0.0.1"){return "127.0.0.1";}
		if($StreamCacheBindProxy_src==null){return "127.0.0.1";}
		$StreamCacheBindProxy=$StreamCacheBindProxy_src;
		$IpClass=new IP();
		if(!$IpClass->isIPAddress($StreamCacheBindProxy)){$StreamCacheBindProxy=null;}
		$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES(true);
		unset($NETWORK_ALL_INTERFACES["127.0.0.1"]);
		unset($NETWORK_ALL_INTERFACES["0.0.0.0"]);
		if(!isset($NETWORK_ALL_INTERFACES[$StreamCacheBindProxy])){$StreamCacheBindProxy=null;}
	
		if($GLOBALS["VERBOSE"]){echo "[".__LINE__."] StreamCacheBindProxy:$StreamCacheBindProxy\n";}
		
		
		if($StreamCacheBindProxy<>null){
			if($StreamCacheBindProxy<>$StreamCacheBindProxy_src){$sock->SET_INFO("StreamCacheBindProxy",$StreamCacheBindProxy);}
			return $StreamCacheBindProxy;
		}
	
		if($StreamCacheBindProxy==null){$StreamCacheBindProxy=$unix->NETWORK_DEFAULT_IP_ADDR();}		
		if($GLOBALS["VERBOSE"]){echo "[".__LINE__."] StreamCacheBindProxy:$StreamCacheBindProxy\n";}
		
		
		
		if($StreamCacheBindProxy<>null){
			if($StreamCacheBindProxy<>$StreamCacheBindProxy_src){$sock->SET_INFO("StreamCacheBindProxy",$StreamCacheBindProxy);}
		}
	
		return $StreamCacheBindProxy;
	}	
	
	
	private function compilation_params(){
		if(!$this->ASROOT){return;}
		if(isset($GLOBALS["COMPILE_SQUID_TOKENS"])){return $GLOBALS["COMPILE_SQUID_TOKENS"];}
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
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

	

	
	private function log_access_acls_csv(){
		if($this->LogsWarninStop==1){return;}
		if($this->SquidUrgency==1){return;}
		$f[]="#--------- CVS log rules";
		$q=new mysql_squid_builder();
		$MACFILER="%>eui";
		$CLIENTSOURCE="%<a";
		if($this->EnableMacAddressFilter==0){$MACFILER='00:00:00:00:00:00';}
		if($this->SquidBubbleMode==1){$prefixacls=" MyStandPort ";}
		if(($this->IS_31) && (!$this->IS_32)){$MACFILER='00:00:00:00:00:00';$CLIENTSOURCE="0.0.0.0";}
		
		$f[]="logformat csv_acls \"%{%Y-%m-%d}tl\";\"%{%H:%M:%S}tl\";\"%>a\";\"%>A\";\"$MACFILER\";\"$CLIENTSOURCE\";\"%<A\";\"%[un\";\"%rm\";\"%ru\";\"%rv\";\"%>Hs\";\"%<st\";\"%Ss:%Sh\";\"%{User-Agent}>h\";\"%{X-Forwarded-For}>h\"";
		$acls=new squid_acls_groups();
		$denylogsTXT=null;
		$log_access=$acls->buildacls_bytype("log_access");
		
		
		echo "Starting......: ".date("H:i:s")." [LOG ]: Engine access_log allow ".count($log_access)." rules..\n";
		if(count($log_access)>0){
			while (list ($index, $line) = each ($log_access) ){
				$f[]="access_log $line $prefixacls";
			}
		}else{
			echo "Starting......: ".date("H:i:s")." [LOG ]: ACL Engine access_log allow No rules..\n";
		}

		if($this->SquidBubbleMode==1){
			$sql="SELECT * FROM webfilters_sqaclsports WHERE enabled=1 ORDER BY aclport";
			$results = $q->QUERY_SQL($sql);
			while ($ligne = mysql_fetch_assoc($results)) {
				$denylogsTXT=null;
				$log_access=$acls->buildacls_bytype("log_access",$ligne["aclport"]);
				$denylogs=$acls->buildacls_bytype("deny_log",$ligne["aclport"]);
				if(count($denylogs)>0){
					while (list ($index, $line) = each ($denylogs) ){
						$f[]="access_log none $line\n";
					}
				}
					
				
				echo "Starting......: ".date("H:i:s")." [LOG ]: Engine access_log allow Port:{$ligne["aclport"]}: ".count($log_access)." rules..\n";
				if(count($log_access)>0){
					while (list ($index, $line) = each ($log_access) ){
						$f[]="access_log $line MyPort{$ligne["aclport"]} $denylogsTXT";
					}
				}
			}
		}				
		
		
		
		return @implode("\n", $f)."\n\n";
	}
	
	

	
	
	private function SNMP(){
		$params=$this->compilation_params();
		if(!isset($params["enable-snmp"])){return "#--------- SNMP\n#--------- Not compiled\n";}
		if($this->SquidUrgency==1){return "#--------- SNMP\n#--------- Urgency Enabled\n";}
		$sock=new sockets();
		$DisableSquidSNMPAll=intval($sock->GET_INFO("DisableSquidSNMPAll"));

		$SquidSNMPPort=intval($sock->GET_INFO("SquidSNMPPort"));
		$SquidSNMPComunity=$sock->GET_INFO("SquidSNMPComunity");
		if($SquidSNMPPort==0){$SquidSNMPPort=$this->snmp_port;}
		if($SquidSNMPComunity==null){$SquidSNMPComunity=$this->snmp_community;}
		if($SquidSNMPComunity==null){$SquidSNMPComunity="public";}
			
		$aclfront=null;
		$f[]="#--------- SNMP";
		$f[]="#--------- DisableSquidSNMPAll:$DisableSquidSNMPAll ";
		$f[]="snmp_port $SquidSNMPPort";
		$f[]="snmp_incoming_address 0.0.0.0";
		$f[]="snmp_outgoing_address 0.0.0.0";
		$f[]="acl snmppublic snmp_community $SquidSNMPComunity";
		$aclfront=" snmppublic";
		
		$Allowed=0;
		if($DisableSquidSNMPAll==1){
			
			if($this->snmp_access_ip==null){
				$f[]="#--------- SNMP\n#--------- No IP access defined";
			}
			
			if($this->snmp_access_ip<>null){
				include_once(dirname(__FILE__)."/class.tcpip.inc");
				$tcp=new IP();
				if(!$tcp->isValid($this->snmp_access_ip)){
					$f[]="#--------- SNMP\n# `$this->snmp_access_ip` not an ipv4 or ipv6 address\n";
				}else{
					$Allowed++;
					$f[]="acl snmpConsole src $this->snmp_access_ip";
					$f[]="snmp_access allow{$aclfront} snmpConsole";
				}
			}
		}
			
		$acls=new squid_acls_groups();
		$snmp_access_allow=$acls->buildacls_bytype("snmp_access_allow");
		
		if(count($snmp_access_allow)>0){
			
			echo "Starting......: ".date("H:i:s")." [SNMP]: Engine snmp_access allow ".count($snmp_access_allow)." rules..\n";
			while (list ($index, $line) = each ($snmp_access_allow) ){
				$Allowed++;
				$f[]="snmp_access allow{$aclfront} $line";
			}
		}

		if($Allowed>0){
			$f[]="snmp_access allow{$aclfront} MgRClient";
			$f[]="snmp_access deny all";
		}else{
			$f[]="snmp_access allow all";
		}
		
		return @implode("\n", $f)."\n\n";
	}
	
	
	private function request_header_add(){
		if($this->SquidUrgency==1){"#--------- Add Headers:\n#--------- Urgency enable\n";return;}
		if(!$this->IS_33){"#--------- Add Headers:\n#--------- Not a 3.3x version\n";return;}
		$f[]="#--------- Add Headers:";
		if($this->SquidBubbleMode==1){$prefixacls=" MyStandPort ";}
		$acls=new squid_acls_groups();
		$request_header_add=$acls->buildacls_bytype("request_header_add");
		if(count($request_header_add)>0){
			echo "Starting......: ".date("H:i:s")." [ACLS]: Engine request_header_add ".count($request_header_add)." rules..\n";
			while (list ($index, $line) = each ($request_header_add) ){
				$f[]="request_header_add $line";
			}			
		}else{
			echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine request_header_add No rules..\n";
		}
		return @implode("\n", $f)."\n\n";
		
	}
	
	
	public function RockStore($maincachedir=null){
		if($this->SquidUrgency==1){return;}
		if(!$this->IS_32){return;}
		if(!$this->ASROOT){return;}
		$sock=new sockets();
		$SquidUseRockStore=$sock->GET_INFO("SquidUseRockStore");
		$EnableRockCache=intval($sock->GET_INFO("EnableRockCache"));
		if(!is_numeric($SquidUseRockStore)){$SquidUseRockStore=0;}
		 
		
		if($SquidUseRockStore==0){
			$s=new squidworkers();
			$Workers=$s->Workers($maincachedir);
			if($s->CPU_FINAL==0){return null;}
			$SquidEnableRockStore=$sock->GET_INFO("SquidEnableRockStore");
			$SquidRockStoreSize=$sock->GET_INFO("SquidRockStoreSize");
			$SquidRockStorePath=$sock->GET_INFO("SquidRockStorePath");
			if(!is_numeric($SquidEnableRockStore)){$SquidEnableRockStore=0;}
			if(!is_numeric($SquidRockStoreSize)){$SquidRockStoreSize=2000;}
			if($SquidRockStorePath==null){$SquidRockStorePath="/home/squid";}
			if($SquidEnableRockStore==0){return $Workers;}
			
			
			$f[]="# --------- Rock Store: EnableRockCache = $EnableRockCache; $SquidEnableRockStore - {$SquidRockStoreSize}M";
			if($EnableRockCache==1){
				if(!is_dir("$SquidRockStorePath/cache-rock")){
					@mkdir("$SquidRockStorePath/cache-rock",0755);
					@chown("$SquidRockStorePath/cache-rock", "squid");
					@chgrp("$SquidRockStorePath/cache-rock", "squid");
				}
				$f[]="cache_dir rock $SquidRockStorePath/cache-rock $SquidRockStoreSize min-size=0 max-size=32768\n";
			}
			
			$f[]=$Workers;
			
			return @implode("\n", $f);
			
		}

	}
	
	
	private function root_squid_version_nocache(){
		if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
		$unix=new unix();
		
		$squidbin=$unix->LOCATE_SQUID_BIN();
		if($squidbin==null){$squidbin=trim($unix->find_program("squid3"));}
		if(!is_file($squidbin)){return;}
		exec("$squidbin -v 2>&1",$results);
		
		$this->SQUID_VERSIONS_LOGS[]="# Binary $squidbin";
		
		
		while (list ($num, $val) = each ($results)){
			if(preg_match("#Squid Cache: Version.*?([0-9\.]+)#", $val,$re)){
				$this->SQUID_VERSIONS_LOGS[]="# Version {$re[1]}";
				if($re[1]=="2.7."){$re[1]="2.7.0";}
				if($re[1]=="3.0."){$re[1]="3.0.0";}
				if($re[1]=="3.1."){$re[1]="3.1.0";}
				if($re[1]=="3.2."){$re[1]="3.2.0";}
				if($re[1]=="3.3."){$re[1]="3.3.0";}
				if($re[1]=="3.4."){$re[1]="3.4.0";}
				if($re[1]=="3.5."){$re[1]="3.5.0";}
		
				if(!isset($GLOBALS["NOSQUIDOUTPUT"])){
					echo "Starting......: ".date("H:i:s")." Squid : Version (as root) '{$re[1]}'\n";
				}
				$GLOBALS[__FUNCTION__]=$re[1];
				return $re[1];
			}
		}
		
	}
	
	
	private function root_squid_version(){return $this->root_squid_version_nocache();	}
	
	public function visible_hostnameF(){
		if(preg_match("#^\.(.+)#", $this->visible_hostname,$re)){
			$this->visible_hostname="localhost.".$this->visible_hostname;
			$this->visible_hostname=str_replace("..", ".", $this->visible_hostname);
		}
		if($this->EnableRemoteStatisticsAppliance==0){return $this->visible_hostname;}
		$hostid=$this->hostid();
		if(isset($this->nodes_names[$hostid])){return $this->nodes_names[$hostid];}
		return $this->visible_hostname;
	}
	private function hostid(){
		if(!$this->ASROOT){$sock=new sockets();return $sock->GET_INFO("HOSTID");}
		$hostid=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/HOSTID"));
		if($hostid<>null){return $hostid;}
			
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
		$hostidbin=$unix->find_program("hostid");
		if(is_file($hostidbin)){
			exec("$hostidbin 2>&1",$results);
			$hostid=md5(trim(@implode("", $results).time()));
			if($hostid<>"00000000"){@file_put_contents("/etc/artica-postfix/settings/Daemons/HOSTID", $hostid);return $hostid;}
		}
			
		$hostid=md5(time());
		@file_put_contents("/etc/artica-postfix/settings/Daemons/HOSTID", $hostid);
		return $hostid;
	}	
	
	
	private function CheckVersion(){
		if(!isset($GLOBALS["intvalVersion"])){$GLOBALS["intvalVersion"]=null;}
		if((isset($GLOBALS["SQUID_IS_VERSION"])) && (trim($GLOBALS["intvalVersion"])<>null)   ){
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [VER]: {$GLOBALS["intvalVersion"]} From memory\n";}
			$this->IS_30=$GLOBALS["SQUID_IS_VERSION"][30];
			$this->IS_31=$GLOBALS["SQUID_IS_VERSION"][31];
			$this->IS_32=$GLOBALS["SQUID_IS_VERSION"][32];
			$this->IS_35=$GLOBALS["SQUID_IS_VERSION"][35];
			$this->IS_40=$GLOBALS["SQUID_IS_VERSION"][40];
			$this->intvalVersion=$GLOBALS["intvalVersion"];
			return;
		}
		$this->intvalVersion=0;
		if(preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)#',$this->SQUID_VERSION,$re)){	
			$this->intvalVersion=intval($re[1]).intval($re[2]).intval($re[3]);
		}
		if($this->intvalVersion==0){
			if(preg_match('#^([0-9]+)\.([0-9]+)#',$this->SQUID_VERSION,$re)){	
				$this->intvalVersion=intval($re[1]).intval($re[2]).'0';	
			}
			
		}
		if($this->intvalVersion==0){
			if(!$GLOBALS["NOSQUIDOUTPUT"]){
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [VER]: FATAL UNABLE TO DETERMINE VERSION\n";}
			}
		}
		
		$VERLOGS[]="intvalVersion:$this->intvalVersion";
		
		if($this->intvalVersion>0){
			$VERLOGS[]="FIRST:{$re[1]};SECOND:{$re[2]}";
			
		if($re[1]==3){
			if(!isset($GLOBALS["NOSQUIDOUTPUT"])){
				if(!isset($GLOBALS["OUTPUTED_VER"])){if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [VER]: Is a 3.{$re[2]} version ($this->intvalVersion)\n";}}
			}
			if($re[2]==0){
				$this->IS_30=true;
				$this->IS_31=false;
				$this->IS_32=false;
				$this->IS_33=false;
				$this->IS_34=false;
				$GLOBALS["SQUID_IS_VERSION"][30]=true;
				$GLOBALS["SQUID_IS_VERSION"][31]=false;
				$GLOBALS["SQUID_IS_VERSION"][32]=false;
				$GLOBALS["SQUID_IS_VERSION"][33]=false;
				$GLOBALS["SQUID_IS_VERSION"][34]=false;		
				$GLOBALS["SQUID_IS_VERSION"][35]=false;
				return;
			}
			
			
			if($re[2]==1){
				$this->IS_30=false;
				$this->IS_31=true;
				$this->IS_32=false;
				$this->IS_33=false;
				$this->IS_34=false;
				$GLOBALS["SQUID_IS_VERSION"][30]=false;
				$GLOBALS["SQUID_IS_VERSION"][31]=true;
				$GLOBALS["SQUID_IS_VERSION"][32]=false;		
				$GLOBALS["SQUID_IS_VERSION"][33]=false;
				$GLOBALS["SQUID_IS_VERSION"][34]=false;
				$GLOBALS["SQUID_IS_VERSION"][35]=false;
				return;
			}
			if($re[2]==2){
				$this->IS_30=false;
				$this->IS_31=true;
				$this->IS_32=true;
				$this->IS_33=false;
				$this->IS_34=false;
				$GLOBALS["SQUID_IS_VERSION"][30]=false;
				$GLOBALS["SQUID_IS_VERSION"][31]=true;
				$GLOBALS["SQUID_IS_VERSION"][32]=true;
				$GLOBALS["SQUID_IS_VERSION"][33]=false;
				$GLOBALS["SQUID_IS_VERSION"][34]=false;
				return;
			}
			if($re[2]==3){
				$this->IS_30=false;
				$this->IS_31=false;
				$this->IS_32=true;
				$this->IS_33=true;
				$this->IS_34=false;
				$GLOBALS["SQUID_IS_VERSION"][30]=false;
				$GLOBALS["SQUID_IS_VERSION"][31]=false;
				$GLOBALS["SQUID_IS_VERSION"][32]=true;
				$GLOBALS["SQUID_IS_VERSION"][33]=true;
				$GLOBALS["SQUID_IS_VERSION"][34]=false;
				return;
			}	

			if($re[2]==4){
				$this->IS_30=false;
				$this->IS_31=false;
				$this->IS_32=true;
				$this->IS_33=true;
				$this->IS_34=true;
				$GLOBALS["SQUID_IS_VERSION"][30]=false;
				$GLOBALS["SQUID_IS_VERSION"][31]=false;
				$GLOBALS["SQUID_IS_VERSION"][32]=true;
				$GLOBALS["SQUID_IS_VERSION"][33]=true;
				$GLOBALS["SQUID_IS_VERSION"][34]=true;
				return;
			}			
			if($re[2]>=5){
				$VERLOGS[]="->3.5x";
				$this->IS_30=false;
				$this->IS_31=false;
				$this->IS_32=true;
				$this->IS_33=true;
				$this->IS_34=true;
				$this->IS_35=true;
				$GLOBALS["SQUID_IS_VERSION"][30]=false;
				$GLOBALS["SQUID_IS_VERSION"][31]=false;
				$GLOBALS["SQUID_IS_VERSION"][32]=true;
				$GLOBALS["SQUID_IS_VERSION"][33]=true;
				$GLOBALS["SQUID_IS_VERSION"][34]=true;
				$GLOBALS["SQUID_IS_VERSION"][35]=true;
				return;
			}			
			
		}
		
		if($re[1]==4){
			$GLOBALS["SQUID_IS_VERSION"][30]=false;
			$GLOBALS["SQUID_IS_VERSION"][31]=false;
			$GLOBALS["SQUID_IS_VERSION"][32]=true;
			$GLOBALS["SQUID_IS_VERSION"][33]=true;
			$GLOBALS["SQUID_IS_VERSION"][34]=true;
			$GLOBALS["SQUID_IS_VERSION"][35]=true;
			$GLOBALS["SQUID_IS_VERSION"][40]=true;
			
		}
		
		if($re[1]==2){
			if(!isset($GLOBALS["OUTPUTED_VER"])){
				if(!isset($GLOBALS["NOSQUIDOUTPUT"])){
					if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [VER]: Is a 2.{$re[2]} version ($this->intvalVersion)\n";}
				}
			}
			$this->IS_30=false;
			$this->IS_31=false;
			$this->IS_32=false;
			$this->IS_33=false;
			$this->IS_34=false;
			if($re[2]>=7){
				$this->IS_27=true;
			}
		}
		
		
		$this->IS_VERS_LOGS=@implode(" ", $VERLOGS);
		
		if(preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)#',$this->SQUID_VERSION,$re)){
			$this->intvalVersion=intval($re[1]).intval($re[2]).intval($re[3]);
			$GLOBALS["intvalVersion"]=$this->intvalVersion;
		}else{

			if(preg_match('#^([0-9]+)\.([0-9]+)\.STABLE#',$this->SQUID_VERSION,$re)){
				$this->intvalVersion=intval($re[1]).intval($re[2]).'0';
				$GLOBALS["intvalVersion"]=$this->intvalVersion;
			}	

		}
		
	}
			$GLOBALS["OUTPUTED_VER"]=true;
		
	}
	
	
	function isicap(){
		if(trim($this->SQUID_VERSION)==null){
			if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
			$users=$GLOBALS["CLASS_USERS"];
			if($this->ASROOT){$this->SQUID_VERSION=$this->root_squid_version();}else{$this->SQUID_VERSION=$users->SQUID_VERSION;}
			$this->SQUID_ICAP_ENABLED=$users->SQUID_ICAP_ENABLED;
			$this->KAV4PROXY_INSTALLED=$users->KAV4PROXY_INSTALLED;
		}
		
		if(preg_match('#([0-9\.]+)#',$this->SQUID_VERSION,$re)){$this->SQUID_VERSION=$re[1];}
		if($this->SQUID_VERSION>=3){
			if($this->SQUID_ICAP_ENABLED){
				if($this->EnableKavICAPRemote==1){return true;}
				
				if($this->KAV4PROXY_INSTALLED){
					return true;
				}else{
					
					$this->kav_accept_why='{error_kavproxy_not_installed}';
				}
			}else{
				
				$this->kav_accept_why='{error_squid_icap_not_compliance}';
			}
		}else{
			writelogs("SQUID:: error_squid_upto_three::version=$this->SQUID_VERSION",__CLASS__.'/'.__FUNCTION__,__FILE__);
			$this->kav_accept_why='{error_squid_upto_three}<br><strong>'.$this->SQUID_VERSION."</strong>";
		}
		
		
		
	}
	
	
	private function KavICAPremote(){
		if($this->EnableKavICAPRemote==0){return false;}
		
		
	}
	
	function VerifyLdapBranch(){
		if(isset($GLOBALS["SQUID_MEMORYCONF"])){
			if(isset($GLOBALS["SQUID_MEMORYCONF"]["GlobalSquidConf"])){
				$this->squid_conf=$GLOBALS["SQUID_MEMORYCONF"]["GlobalSquidConf"];
			}
			
			if(isset($GLOBALS["SQUID_MEMORYCONF"]["ArticaSquidParameters"])){
				$this->ArticaSquidParameters=$GLOBALS["SQUID_MEMORYCONF"]["ArticaSquidParameters"];
			}
			
			if(isset($GLOBALS["SQUID_MEMORYCONF"]["SquidBlockSites"])){
				$this->SquidBlockSites=$GLOBALS["SQUID_MEMORYCONF"]["SquidBlockSites"];
			}
			if(isset($GLOBALS["SQUID_MEMORYCONF"]["ArticaEnableKav4ProxyInSquid"])){
				$this->EnableKav4Proxy=$GLOBALS["SQUID_MEMORYCONF"]["ArticaEnableKav4ProxyInSquid"];
			}
			return;
		}
		
		$sock=new sockets();
		$this->squid_conf=$sock->GET_INFO('GlobalSquidConf');
		if($this->squid_conf==null){$this->squid_conf=$this->LOAD_CONF_ROOT();}
			
		$this->ArticaSquidParameters=$sock->GET_INFO('ArticaSquidParameters');
		$this->SquidBlockSites=$sock->GET_INFO('SquidBlockSites');
		$this->EnableKav4Proxy=$sock->GET_INFO('ArticaEnableKav4ProxyInSquid');
		
		$GLOBALS["SQUID_MEMORYCONF"]["GlobalSquidConf"]=$this->squid_conf;
		$GLOBALS["SQUID_MEMORYCONF"]["ArticaSquidParameters"]=$this->ArticaSquidParameters;
		$GLOBALS["SQUID_MEMORYCONF"]["SquidBlockSites"]=$this->SquidBlockSites;
		$GLOBALS["SQUID_MEMORYCONF"]["ArticaEnableKav4ProxyInSquid"]=$this->EnableKav4Proxy;
		
		}
		
		private function LOAD_CONF_ROOT(){
			if(!$GLOBALS["AS_ROOT"]){$sock=new sockets();return base64_decode($sock->getFrameWork('cmd.php?squid-GetOrginalSquidConf'));}
			if(class_exists("unix")){if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();return @file_get_contents($unix->LOCATE_SQUID_CONF());}
		}		
	
	
	function ParseConfig(){

		
		$ini=new Bs_IniHandler();
		$sock=new sockets();
		if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
		$users=$GLOBALS["CLASS_USERS"];
		$this->ACL_ARP_ENABLED=$users->SQUID_ARP_ACL_ENABLED;
		$this->EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
		if(!is_numeric($this->EnableRemoteStatisticsAppliance)){$this->EnableRemoteStatisticsAppliance=0;}
		
		if($this->ACL_ARP_ENABLED){
			if($this->ASROOT){
					if(!isset($GLOBALS["NOSQUIDOUTPUT"])){
					if(!isset($GLOBALS["OUTPUTED_ARP"])){echo "Starting......: ".date("H:i:s")." [MAC]: ARP OK, acls with Mac addresses enabled\n";}}
					}else{
						if($this->ASROOT){
							if(!isset($GLOBALS["OUTPUTED_ARP"])){echo "Starting......: ".date("H:i:s")." [MAC]: ARP Not enabled, acls with Mac addresses disabled\n";}
						}
				}
		}
		
		
		$network=null;
		$dns=null;
		$GLOBALS["OUTPUTED_ARP"]=true;
		if($this->ArticaSquidParameters==null){$this->ArticaSquidParameters=$sock->GET_INFO('ArticaSquidParameters');}
		
		if($GLOBALS["VERBOSED"]){echo __LINE__." ArticaSquidParameters ->". strlen($this->ArticaSquidParameters)." bytes<br>\n";}
		
		if(!isset($GLOBALS["SQUID_MEMORYCONF"]["INI_ARRAY"])){
			$ini->loadString($this->ArticaSquidParameters);
			if(isset($ini->_params)){
				$GLOBALS["SQUID_MEMORYCONF"]["INI_ARRAY"]=$ini->_params;
			}
		}else{
			$ini->_params=$GLOBALS["SQUID_MEMORYCONF"]["INI_ARRAY"];
		}
		if(isset($ini->_params)){
			if(!isset($ini->_params["NETWORK"]["HTCP_PORT"])){$ini->_params["NETWORK"]["HTCP_PORT"]=0;}
			if(!isset($ini->_params["NETWORK"]["NODES_NAME"])){$ini->_params["NETWORK"]["NODES_NAME"]=null;}
			
			
			$network=explode(';',$ini->_params["NETWORK"]["cdir"]);
			
			$this->listen_port=$ini->_params["NETWORK"]["LISTEN_PORT"];
			$this->second_listen_port=$ini->_params["NETWORK"]["SECOND_PORT"];
			$this->ICP_PORT=intval(trim($ini->_params["NETWORK"]["ICP_PORT"]));
			$this->HTCP_PORT=intval(trim($ini->_params["NETWORK"]["HTCP_PORT"]));
			$this->visible_hostname=$ini->_params["NETWORK"]["visible_hostname"];
			$this->visible_hostname=str_replace("..", ".", $this->visible_hostname);
			$this->LDAP_AUTH=$ini->_params["NETWORK"]["LDAP_AUTH"];
			
			$this->SquidLdapAuthEnableGroups=$sock->GET_INFO("SquidLdapAuthEnableGroups");
			$this->LDAP_AUTH=$sock->GET_INFO("SquidStandardLDAPAuth");
			$EnableOpenLDAP=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableOpenLDAP"));
			if($EnableOpenLDAP==0){$this->LDAP_AUTH=0;}
			
			if($this->ASROOT){
				include_once("/usr/share/artica-postfix/framework/class.unix.inc");
				if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
				$nginx=$unix->find_program("nginx");
				if(is_file($nginx)){
					$sock=new sockets();
					$DisableNginxProxy=$sock->GET_INFO("DisableNginxProxy");
					if(!is_numeric($DisableNginxProxy)){$DisableNginxProxy=0;}
					if($DisableNginxProxy==0){
						if($this->listen_port==80){$this->listen_port=8080;}
						if($this->ssl_port==443){$this->ssl_port=4443;}
					}
				}
			}
			
			
			
			if($this->ASROOT){
				if(!isset($GLOBALS["NOSQUIDOUTPUT"])){
					echo "Starting......: ".date("H:i:s")." [AUTH]: LDAP_AUTH = $this->LDAP_AUTH\n";
				}
			}
			
			$this->NTLM_AUTH=$ini->_params["NETWORK"]["NTLM_AUTH"];
		
			$this->LDAP_EXTERNAL_AUTH=$ini->_params["NETWORK"]["LDAP_EXTERNAL_AUTH"];
			$this->EnableParentProxy=$ini->_params["NETWORK"]["EnableParentProxy"];
			if(isset($ini->_params["NETWORK"]["prefer_direct"])){$this->prefer_direct=$ini->_params["NETWORK"]["prefer_direct"];}
			if(isset($ini->_params["NETWORK"]["nonhierarchical_direct"])){$this->nonhierarchical_direct=$ini->_params["NETWORK"]["nonhierarchical_direct"];}
			if(isset($ini->_params["NETWORK"]["max_filedesc"])){$this->max_filedesc=$ini->_params["NETWORK"]["max_filedesc"];}
			if(!isset($ini->_params["NETWORK"]["forwarded_for"])){$ini->_params["NETWORK"]["forwarded_for"]="on";}
			
			if(!isset($ini->_params["NETWORK"]["snmp_community"])){$ini->_params["NETWORK"]["snmp_community"]="";}
			if(!isset($ini->_params["NETWORK"]["snmp_enable"])){$ini->_params["NETWORK"]["snmp_enable"]=0;}
			if(!isset($ini->_params["NETWORK"]["snmp_port"])){$ini->_params["NETWORK"]["snmp_port"]=null;}
			if(!isset($ini->_params["NETWORK"]["snmp_access_ip"])){$ini->_params["NETWORK"]["snmp_access_ip"]=null;}
			if(!isset($ini->_params["NETWORK"]["certificate_center"])){$ini->_params["NETWORK"]["certificate_center"]=null;}
			
			
			$this->snmp_community=$ini->_params["NETWORK"]["snmp_community"];
			$this->snmp_enable=$ini->_params["NETWORK"]["snmp_enable"];
			$this->snmp_port=$ini->_params["NETWORK"]["snmp_port"];
			$this->snmp_access_ip=$ini->_params["NETWORK"]["snmp_access_ip"];
			
			$this->certificate_center=$ini->_params["NETWORK"]["certificate_center"];
			$this->ssl_port=$ini->_params["NETWORK"]["SSL_PORT"];
			$this->SSL_BUMP_WHITE_LIST=intval($ini->_params["NETWORK"]["SSL_BUMP_WHITE_LIST"]);
			$this->enable_kavproxy=$ini->_params["KAV"]["enabled"];
			$this->enable_dansguardian=$ini->_params["DANSGUARDIAN"]["enable_dansguardian"];
			$this->alt_listen_port=$ini->_params["NETWORK"]["ALT_PORT"];
			$this->CACHE_SIZE=$ini->_params["CACHE"]["CACHE_SIZE"];
			$this->CACHE_PATH=$ini->_params["CACHE"]["CACHE_PATH"];
			$this->CACHE_PATH=str_replace(" ", "_", $this->CACHE_PATH);
			$this->CACHE_TYPE=$ini->_params["CACHE"]["CACHE_TYPE"];
			$this->EXTERNAL_LDAP_AUTH_PARAMS=unserialize(base64_decode($sock->GET_INFO("SquidExternalAuth")));
			$this->FTP_PARAMS=unserialize(base64_decode($sock->GET_INFO("SquidFTPParams")));
			$this->SSL_BUMP=$ini->_params["NETWORK"]["SSL_BUMP"];
			
			$this->wccp2_enabled=$sock->GET_INFO("SquidWCCPEnabled");
			$this->wccp2_router=$ini->_params["NETWORK"]["wccp2_router"];
			$this->wccp2_forwarding_method=$ini->_params["NETWORK"]["wccp2_forwarding_method"];
			$this->wccp2_return_method=$ini->_params["NETWORK"]["wccp2_return_method"];
			$this->wccp2_assignment_method=$ini->_params["NETWORK"]["wccp2_assignment_method"];
			$this->enable_ftp_restrictions=$ini->_params["NETWORK"]["enable_ftp_restrictions"];
			$this->EnableUserAgentBanAll=$ini->_params["NETWORK"]["EnableUserAgentBanAll"];
			$this->EnableChangeRequestSize=$ini->_params["NETWORK"]["EnableChangeRequestSize"];
			$this->allow_squid_localhost=$ini->_params["NETWORK"]["allow_squid_localhost"];
			$this->EnableICPPort=$ini->_params["NETWORK"]["EnableICPPort"];
			$this->url_rewrite_bypass=$ini->_params["NETWORK"]["url_rewrite_bypass"];
			$this->forwarded_for=$ini->_params["NETWORK"]["forwarded_for"];
			
			if(isset($ini->_params["NETWORK"]["ignore_expect_100"])){$this->ignore_expect_100=$ini->_params["NETWORK"]["ignore_expect_100"];}
			
			if(!isset($ini->_params["NETWORK"]["peer_connect_timeout"])){$ini->_params["NETWORK"]["peer_connect_timeout"]=30;}
			$this->peer_connect_timeout=$ini->_params["NETWORK"]["peer_connect_timeout"];
			if(!is_numeric($this->peer_connect_timeout)){$this->peer_connect_timeout=30;}
			
			if(!isset($ini->_params["NETWORK"]["connect_timeout"])){$ini->_params["NETWORK"]["connect_timeout"]=30;}
			$this->connect_timeout=$ini->_params["NETWORK"]["connect_timeout"];
			if(!is_numeric($this->connect_timeout)){$this->connect_timeout=120;}

			if(!isset($ini->_params["NETWORK"]["persistent_request_timeout"])){$ini->_params["NETWORK"]["persistent_request_timeout"]=60;}
			$this->persistent_request_timeout=$ini->_params["NETWORK"]["persistent_request_timeout"];
			if(!is_numeric($this->persistent_request_timeout)){$this->persistent_request_timeout=60;}

			if(!isset($ini->_params["NETWORK"]["client_lifetime"])){$ini->_params["NETWORK"]["client_lifetime"]=60000;}
			$this->client_lifetime=$ini->_params["NETWORK"]["client_lifetime"];
			if(!is_numeric($this->client_lifetime)){$this->client_lifetime=60000;}	
			
			if(!isset($ini->_params["NETWORK"]["dns_timeout"])){$ini->_params["NETWORK"]["dns_timeout"]=120;}
			$this->dns_timeout=$ini->_params["NETWORK"]["dns_timeout"];
			if(!is_numeric($this->dns_timeout)){$this->dns_timeout=120;}

			if(!isset($ini->_params["NETWORK"]["pconn_timeout"])){$ini->_params["NETWORK"]["pconn_timeout"]=120;}
			$this->pconn_timeout=$ini->_params["NETWORK"]["pconn_timeout"];
			if(!is_numeric($this->pconn_timeout)){$this->pconn_timeout=120;}

			
			if(!isset($ini->_params["NETWORK"]["forward_max_tries"])){$ini->_params["NETWORK"]["forward_max_tries"]=30;}
			$this->forward_max_tries=$ini->_params["NETWORK"]["forward_max_tries"];
			if(!is_numeric($this->forward_max_tries)){$this->forward_max_tries=30;}

			if(!isset($ini->_params["NETWORK"]["forward_timeout"])){$ini->_params["NETWORK"]["forward_timeout"]=240;}
			$this->forward_timeout=$ini->_params["NETWORK"]["forward_timeout"];
			if(!is_numeric($this->forward_timeout)){$this->forward_timeout=240;}			
			

			

			if(!isset($ini->_params["NETWORK"]["dead_peer_timeout"])){$ini->_params["NETWORK"]["dead_peer_timeout"]=10;}
			$this->dead_peer_timeout=$ini->_params["NETWORK"]["dead_peer_timeout"];
			if(!is_numeric($this->dead_peer_timeout)){$this->dead_peer_timeout=10;}			
			
			if(!isset($ini->_params["NETWORK"]["shutdown_lifetime"])){$ini->_params["NETWORK"]["shutdown_lifetime"]=5;}
			$this->shutdown_lifetime=$ini->_params["NETWORK"]["shutdown_lifetime"];
			if(!is_numeric($this->shutdown_lifetime)){$this->shutdown_lifetime=10;}		
			if($this->shutdown_lifetime<10){$this->shutdown_lifetime=10;}	
			
			
			if(!isset($ini->_params["NETWORK"]["read_timeout"])){$ini->_params["NETWORK"]["read_timeout"]=900;}
			$this->read_timeout=$ini->_params["NETWORK"]["read_timeout"];
			if(!is_numeric($this->read_timeout)){$this->read_timeout=900;}

			if(!isset($ini->_params["NETWORK"]["incoming_rate"])){$ini->_params["NETWORK"]["incoming_rate"]=30;}
			$this->incoming_rate=$ini->_params["NETWORK"]["incoming_rate"];
			if(!is_numeric($this->incoming_rate)){$this->incoming_rate=30;}	
			
			
			
			
			if(!is_numeric($this->SquidLdapAuthEnableGroups)){$this->SquidLdapAuthEnableGroups=0;}
			if(!is_numeric($this->enable_kavproxy)){$this->enable_kavproxy=0;}
			if(!is_numeric($this->second_listen_port)){$this->second_listen_port=0;}
			if(!is_numeric($this->url_rewrite_bypass)){$this->url_rewrite_bypass=0;}
			if(!is_numeric($this->ICP_PORT)){$this->ICP_PORT=0;}
			if(!is_numeric($this->HTCP_PORT)){$this->HTCP_PORT=0;}
			if(!is_numeric($this->SSL_BUMP_WHITE_LIST)){$this->SSL_BUMP_WHITE_LIST=0;}
			if(!is_numeric($this->nonhierarchical_direct)){$this->nonhierarchical_direct=1;}
			if(!is_numeric($this->prefer_direct)){$this->prefer_direct=0;}
			if(!is_numeric($this->snmp_enable)){$this->snmp_enable=0;}
			if(!is_numeric($this->snmp_port)){$this->snmp_port=3401;}
			if(!isset($ini->_params["NODES_NAME"])){$ini->_params["NODES_NAME"]=null;}
			
			$this->nodes_names=$ini->_params["NODES_NAME"];
			
				if(is_array($ini->_params)){
					reset($ini->_params);
					
					while (list ($num, $val) = each ($ini->_params)){
						if(preg_match('#cache:(.+)#',$num,$re)){
							if($GLOBALS["VERBOSE"]){echo "Found cache: {$re[1]}<br>\n";}		
							$re[1]=str_replace(" ", "_", $re[1]);
							
							$this->cache_list[$re[1]]=array(
							"cache_type"=>$ini->_params[$num]["cache_type"],
							"cache_dir_level1"=>$ini->_params[$num]["cache_dir_level1"],
							"cache_dir_level2"=>$ini->_params[$num]["cache_dir_level2"],
							"cache_size"=>$ini->_params[$num]["cache_size"],
							"cache_maxsize"=>$ini->_params[$num]["cache_maxsize"],
							);
						}
						
						if(preg_match('#time:([0-9]+):(.+)#',$num,$re)){
							while (list ($a, $b) = each ($ini->_params[$num])){
								$this->acl_times[$num][$a]=$b;
							}
						}
					}
				}			
			
		}
		
		if($this->ignore_expect_100==null){$this->ignore_expect_100="0";}
		if($this->wccp2_enabled==null){$this->wccp2_enabled="0";}
		if($this->wccp2_forwarding_method==null){$this->wccp2_forwarding_method="1";}
		if($this->wccp2_return_method==null){$this->wccp2_return_method="1";}
		if($this->wccp2_assignment_method==null){$this->wccp2_assignment_method="hash";}
		if($this->enable_ftp_restrictions==null){$this->enable_ftp_restrictions="0";}
		if($this->EnableChangeRequestSize==null){$this->EnableChangeRequestSize="0";}
		if($this->enable_cicap==null){$this->enable_cicap=0;}
		if($this->listen_port==null){$this->listen_port=3128;}
		if($this->visible_hostname=="proxyweb"){$this->visible_hostname=null;}
		if($this->visible_hostname==null){if(class_exists("unix")){if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();$this->visible_hostname=$unix->hostname_g();}}
		if($this->visible_hostname==null){$this->visible_hostname=$users->hostname;}
		if(!is_numeric($this->LDAP_AUTH)){$this->LDAP_AUTH=0;}
		if(!is_numeric($this->NTLM_AUTH)){$this->NTLM_AUTH=0;}
		if($this->enable_kavproxy==null){$this->enable_kavproxy=0;}
		if($this->enable_dansguardian==null){$this->enable_dansguardian=0;}
		if($this->CACHE_SIZE==null){$this->CACHE_SIZE=2000;}
		if($this->CACHE_PATH==null){$this->CACHE_PATH="/home/squid/cache-default";}
		if($this->CACHE_TYPE==null){$this->CACHE_TYPE="aufs";}
		if($this->SSL_BUMP==null){$this->SSL_BUMP=0;}
		
		if(preg_match("#([0-9]+)\.([0-9]+)#",$users->SQUID_VERSION,$re)){
    		if($re[1]<3){$this->SSL_BUMP=0;}else{if($re[2]<1){$this->SSL_BUMP=0;}}
		}
		
		

		if(is_array($network)){
			while (list ($num, $cidr) = each ($network)){
					if(trim($cidr)==null){continue;}
					$this->network_array[]=$cidr;
				}
		}
		
		$this->FILL_DNS();
		
		
		
		
		if($this->squid_conf==null){$this->squid_conf=$sock->GET_INFO("GlobalSquidConf");}
		
		$this->global_conf_array=unserialize(base64_decode($sock->GET_INFO("SquidGlobalConfArray")));
		if(!is_array($this->global_conf_array)){
			$tb=explode("\n",$this->squid_conf);
			writelogs("global_conf_array is not an array, parsing ". count($tb).
		 " lines.. ". strlen($this->squid_conf)." bytes",__CLASS__.'/'.__FUNCTION__,__FILE__);
			
			while (list ($num, $val) = each ($tb)){
			if(preg_match('#^([a-z\_]+)\s+(.+)#',$val,$re)){
				if($re[1]=="acl"){continue;}
				if($re[1]=="http_access"){continue;}
				if($re[1]=="auth_param"){continue;}
				$this->global_conf_array[$re[1]]=$re[2];
				}
			}
		}	
		
		
		if(!isset($this->global_conf_array["request_body_max_size"])){$this->global_conf_array["request_body_max_size"]="5000";}
		if(!isset($this->global_conf_array["request_header_max_size"])){$this->global_conf_array["request_header_max_size"]="64 KB";}
		if(!isset($this->global_conf_array["reply_header_max_size"])){$this->global_conf_array["reply_header_max_size"]="64 KB";}
		if(!isset($this->global_conf_array["client_request_buffer_max_size"])){$this->global_conf_array["client_request_buffer_max_size"]="512 KB";}
		if(!isset($this->global_conf_array["request_body_max_size"])){$this->global_conf_array["request_body_max_size"]=0;}
		
		if(!isset($this->global_conf_array["reply_body_max_size"])){$this->global_conf_array["reply_body_max_size"]=0;}
		if(!isset($this->global_conf_array["positive_dns_ttl"])){$this->global_conf_array["positive_dns_ttl"]="6 hours";}
		if(!isset($this->global_conf_array["negative_dns_ttl"])){$this->global_conf_array["negative_dns_ttl"]="300 seconds";}
		if(!isset($this->global_conf_array["read_ahead_gap"])){$this->global_conf_array["read_ahead_gap"]="1 MB";}
		
		if(!is_numeric($this->global_conf_array["request_body_max_size"])){$this->global_conf_array["request_body_max_size"]="5000";}
		if($this->global_conf_array["maximum_object_size"]==null){$this->global_conf_array["maximum_object_size"]="300 MB";}
		if($this->global_conf_array["cache_mem"]==null){$this->global_conf_array["cache_mem"]="256 MB";}
		
		if($this->global_conf_array["read_ahead_gap"]==null){$this->global_conf_array["read_ahead_gap"]="1 MB";}
		if($this->global_conf_array["cache_swap_high"]==null){$this->global_conf_array["cache_swap_high"]="90";}
		if($this->global_conf_array["cache_swap_low"]==null){$this->global_conf_array["cache_swap_low"]="95";}
		if($this->global_conf_array["minimum_object_size"]==null){$this->global_conf_array["minimum_object_size"]="0 KB";}
		if($this->global_conf_array["maximum_object_size_in_memory"]==null){$this->global_conf_array["maximum_object_size_in_memory"]="8 KB";}
		if($this->global_conf_array["ipcache_size"]==null){$this->global_conf_array["ipcache_size"]=1024;}
		if($this->global_conf_array["ipcache_low"]==null){$this->global_conf_array["ipcache_low"]=90;}
		if($this->global_conf_array["ipcache_high"]==null){$this->global_conf_array["ipcache_high"]=95;}
		if($this->global_conf_array["fqdncache_size"]==null){$this->global_conf_array["fqdncache_size"]=1024;}
		if($this->global_conf_array["positive_dns_ttl"]==null){$this->global_conf_array["positive_dns_ttl"]="6 hours";}
		if($this->global_conf_array["negative_dns_ttl"]==null){$this->global_conf_array["negative_dns_ttl"]="300 seconds";}	
		if($this->global_conf_array["maximum_object_size_in_memory"]==null){$this->global_conf_array["maximum_object_size_in_memory"]="100 M";}
		if($this->global_conf_array["cache_mem"]==null){$this->global_conf_array["cache_mem"]="8 M";}
		if($this->global_conf_array["cache_swap_high"]==null){$this->global_conf_array["cache_swap_high"]="97";}
		if($this->global_conf_array["cache_swap_low"]==null){$this->global_conf_array["cache_swap_low"]="95";}

		
		
		
		
		if(!is_numeric($this->global_conf_array["persistent_request_timeout"])){$this->global_conf_array["persistent_request_timeout"]=60;}
		
		if($this->global_conf_array["request_header_max_size"]==null){$this->global_conf_array["request_header_max_size"]="64 KB";}
		if(!is_numeric($this->global_conf_array["request_body_max_size"])){$this->global_conf_array["request_body_max_size"]="0";}
		if($this->global_conf_array["client_request_buffer_max_size"]==null){$this->global_conf_array["client_request_buffer_max_size"]="512 KB";}
		if($this->global_conf_array["reply_header_max_size"]==null){$this->global_conf_array["reply_header_max_size"]="64 KB";}
		
		
		
		if($this->global_conf_array["reply_body_max_size"]==null){$this->global_conf_array["reply_body_max_size"]="0";}
		
		$CacheMemCentral=0;
		
		$this->EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
		if(!is_numeric($this->EnableRemoteStatisticsAppliance)){$this->EnableRemoteStatisticsAppliance=0;}		
		
		if($this->EnableRemoteStatisticsAppliance==1){
			$CacheMemCentral=$sock->GET_INFO("CacheMemCentral");
			if(!is_numeric($CacheMemCentral)){$CacheMemCentral=0;}
			if($CacheMemCentral>0){
				$this->global_conf_array["cache_mem"]="$CacheMemCentral MB";
				
			}
		}
		
		$this->enable_kavproxy=$sock->GET_INFO('kavicapserverEnabled');
		$this->enable_cicap=$sock->GET_INFO('CicapEnabled');
		$this->enable_squidguard=$sock->GET_INFO('squidGuardEnabled');
		$this->UfdbEnabledCentral=$sock->GET_INFO("UfdbEnabledCentral");
		$this->AntivirusEnabledCentral=$sock->GET_INFO("AntivirusEnabledCentral");
		$this->SquidWCCPL3Enabled=intval($sock->GET_INFO("SquidWCCPL3Enabled"));
		
		if(!is_numeric($this->enable_kavproxy)){$this->enable_kavproxy=0;}
		if(!is_numeric($this->enable_cicap)){$this->enable_cicap=0;}
		if(!is_numeric($this->enable_squidguard)){$this->enable_squidguard=0;}
		if(!is_numeric($this->UfdbEnabledCentral)){$this->UfdbEnabledCentral=1;}
		if(!is_numeric($this->AntivirusEnabledCentral)){$this->AntivirusEnabledCentral=1;}
		
		if($this->EnableRemoteStatisticsAppliance==1){
			$this->enable_UfdbGuard=$this->UfdbEnabledCentral;
			$this->EnableKavICAPRemote=$this->AntivirusEnabledCentral;
			$this->enable_cicap=$this->AntivirusEnabledCentral;
			$this->enable_kavproxy=$this->AntivirusEnabledCentral;
			$this->enable_squidclamav=0;
		}		
		if($this->DisableWinbindd==1){$this->EnableKerbAuth=0;$this->NTLM_AUTH=0;}
		if($this->SquidWCCPL3Enabled==1){$this->wccp2_enabled=0;}
		
		$SquidWCCPL3SSLEnabled=intval($sock->GET_INFO("SquidWCCPL3SSLEnabled"));
		if($SquidWCCPL3SSLEnabled==1){
			$this->SSL_BUMP=1;
			$this->EnableSquidSSLCRTD=1;
		}
		$EnableArticaHotSpot=intval($sock->GET_INFO("EnableArticaHotSpot"));
		if($EnableArticaHotSpot==1){
			$ArticaHotSpotEnableMIT=$sock->GET_INFO("ArticaHotSpotEnableMIT");
			if(!is_numeric($ArticaHotSpotEnableMIT)){$ArticaHotSpotEnableMIT=1;}
			if($ArticaHotSpotEnableMIT==1){
				$this->SSL_BUMP=1;
				$this->EnableSquidSSLCRTD=1;
			}
		}
		
		if($this->ASROOT){
			if(!isset($GLOBALS["NOSQUIDOUTPUT"])){
				echo "Starting......: ".date("H:i:s")." [MEM]: Cache Memory: {$this->global_conf_array["cache_mem"]} remote appliance:$this->EnableRemoteStatisticsAppliance,Cache Central:$CacheMemCentral\n";
			}
		}
		
		if($this->SquidUrgency==1){
			$this->enable_cicap=0;
			$this->enable_kavproxy=0;
			$this->enable_squidclamav=0;
			$this->NTLM_AUTH=0;
		}
		
		if($this->SSL_BUMP==0){
			
			$this->EnableSquidSSLCRTD=0;
		}
		
		
	}
	
	
	

	
	private function FILL_DNS(){
		if(isset($GLOBALS["squid_dns_array"])){
			if(count($GLOBALS["squid_dns_array"])>0){
				$this->dns_array=$GLOBALS["squid_dns_array"];
				return;
			}
		}
		
		
		
		
		
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [DNS]: Filling DNS...\n";}
		$filldef=false;
		$dns_array=array();
		$sock=new sockets();
		$this->UtDNSEnable=intval($sock->GET_INFO("UtDNSEnable"));
		
		$q=new mysql_squid_builder();
		
		if($this->UtDNSEnable==1){
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [DNS]: Web filter SaaS enabled\n";}
			$sock=new sockets();
			$UtDNSArticaUser=json_decode(base64_decode($sock->GET_INFO("UtDNSArticaUser")));
			if($UtDNSArticaUser->success){
				$dns_array[]=$UtDNSArticaUser->prim;
				$dns_array[]=$UtDNSArticaUser->sec;
				
			}else{
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [DNS]: Web filter SaaS report failed\n";}
			}
		}
		
		
		if(!$q->TABLE_EXISTS("dns_servers")){
			$filldef=true;
			$q->QUERY_SQL("CREATE TABLE `squidlogs`.`dns_servers` ( `ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY , `dnsserver` VARCHAR( 90 ) NOT NULL , `zOrder` SMALLINT( 2 ) NOT NULL,  INDEX (`zOrder`), UNIQUE KEY `dnsserver` (`dnsserver`) ) ENGINE=MyISAM;" );
			
		}
		
		if($q->COUNT_ROWS("dns_servers")==0){$filldef=true;}
		
		if($filldef){
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [DNS]: Filling DNS - DEFAULT -...\n";}
			if(isset($GLOBALS["SQUID_MEMORYCONF"]["INI_ARRAY"])){
				$dns=explode(';',$GLOBALS["SQUID_MEMORYCONF"]["INI_ARRAY"]["NETWORK"]["dns_servers"]);
				if(is_array($dns)){
					while (list ($num, $serv) = each ($dns)){
						if(trim($serv)==null){continue;}
						$dns_array[]=$serv;
						$q->QUERY_SQL("INSERT INTO `dns_servers` (`dnsserver`,`zOrder`) VALUES ('$serv','$num')");
					}
				}
			}
		}
		
		$sql="SELECT dnsserver FROM `dns_servers` ORDER BY `zOrder`";
		$results=$q->QUERY_SQL($sql);
		if($q->ok){
			while ($ligne = mysql_fetch_assoc($results)) {
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [DNS]: Filling DNS - {$ligne["dnsserver"]} -...\n";}
				$dns_array[]=$ligne["dnsserver"];
			}
		}
		
		if(count($dns_array)==0){
			
			$f=explode("\n",@file_get_contents("/etc/resolv.conf"));
			$this->build_progress("Open resolv.conf ".count($f)." lines");
			while (list ($index, $line) = each ($f) ){
				$line=trim($line);
				if(!preg_match("#^nameserver\s+(.+)#", $line,$re)){continue;}
				$dns_array[$re[1]]=$re[1];
			}
				
				
		}
		
		
		$nameserver=array();
		while (list ($num, $ip) = each ($dns_array) ){$nameserver[$ip]=true;}
		while (list ($ip, $none) = each ($nameserver) ){
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [DNS]: $ip\n";}
			$GLOBALS["squid_dns_array"][]=$ip;
			$this->dns_array[]=$ip;
		}
		

		
		$this->build_progress(count($this->dns_array)." DNS items...");
		
		
	}
	

	
	private function append_domain(){
		if(!$this->ASROOT){return;}
		$sock=new sockets();
		$SquidAppendDomain=trim($sock->GET_INFO("SquidAppendDomain"));
		$SquidAppendDomainDisabled=intval($sock->GET_INFO("SquidAppendDomainDisabled"));
		if($SquidAppendDomainDisabled==1){return;}
		
		$f[]="# Append domain: SquidAppendDomain = $SquidAppendDomain [".__LINE__."]";
		
		if($SquidAppendDomain==null){
			$MainArray=unserialize(base64_decode($sock->GET_INFO("resolvConf")));
			$SquidAppendDomain=trim($MainArray["DOMAINS1"]);
			$f[]="# Append domain: resolvConf = $SquidAppendDomain [".__LINE__."]";
			if($SquidAppendDomain=="localhost.local"){$SquidAppendDomain=null;}
		}
		
		if($SquidAppendDomain==null){
			$unix=new unix();
			$hostname=trim($unix->hostname_g());
			$hostname2=explode('.',$hostname);
			unset($hostname2[0]);
			$SquidAppendDomain=trim(@implode(".", $hostname2));
			$f[]="# Append domain: $hostname = $SquidAppendDomain [".__LINE__."]";
			
		}
		
		echo "Starting......: ".date("H:i:s")." [ CONF ]: Append netbios names with \"$SquidAppendDomain\"\n";
		if($SquidAppendDomain==null){return @implode("\n", $f);}
		
		$f[]="append_domain .$SquidAppendDomain";
		return @implode("\n", $f);
	}
	
	
	public function cache_deny_array(){
		$sock=new sockets();
		$SquidCacheLevel=$sock->GET_INFO("SquidCacheLevel");
		if(!is_numeric($SquidCacheLevel)){$SquidCacheLevel=4;}
		if($SquidCacheLevel==0){
			return "# Do not cache ALL domains: SquidCacheLevel:$SquidCacheLevel\n";
		}
		
		
		if($this->SquidUrgency==1){
			$acl[]="# Emergency enabled, deny ACL `DoNotCache`";
			return null;
		}
		include_once(dirname(__FILE__)."/class.squid.acls.inc");
		$already=array();
		$acl=array();
		$sql="SELECT pattern,PatternType FROM webfilters_blkwhlts WHERE blockType=4 AND enabled=1";
		$q=new mysql_squid_builder();
		$results=$q->QUERY_SQL($sql);
		
		$acl[]="# Do not cache domains: ". mysql_num_rows($results)." items in ".__CLASS__."/".__FUNCTION__." Line:".__LINE__;
		$acl[]="acl DoNotCache MgRPort";
		$acl[]="send_hit deny MgRPort";
		$acl[]="acl DoNotCache dstdomain s497977761.onlinehome.fr";
		$acl[]="acl DoNotCache dstdomain .articatech.com";
		$acl[]="acl DoNotCache dstdomain .unveiltech.com";
		$acl[]="acl DoNotCache dstdomain .artica.fr";
		$acl[]="acl DoNotCache dstdomain .articatech.net";
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$www=trim($ligne["pattern"]);
			if($www==null){continue;}
			if($ligne["PatternType"]==2){
				$sq=new squid_acls();
				$sqgroups=$sq->GetItems($www,"dstdomain");
				while (list ($indexed, $www) = each ($sqgroups) ){$www=$this->BaseNameWebSite($www);if($www==null){continue;}if(isset($already[$www])){continue;}$already[$www]=true;$acl[]="acl DoNotCache dstdomain .$www";continue;}
				continue;
			}
			if($ligne["PatternType"]<>0){continue;}
			$www=$this->BaseNameWebSite($www);
			if($www==null){continue;}
			if(isset($already[$www])){continue;}
			$already[$www]=true;
			$acl[]="acl DoNotCache dstdomain .$www";
			
		}

		return $acl;
		
	}
	

	
	

	
	
	
	private function external_acl_childrens($MacProc=0,$NoNegCache=false,$ForceConf=array()){
		
		$array["CACHE_TIME"]=360;
		$external_acl_children=$this->external_acl_children();
		$MacProc=$external_acl_children;
		$external_acl_startup=$this->SquidClientParams["external_acl_startup"];
		$external_acl_idle=$this->SquidClientParams["external_acl_idle"];
		
		$negative_ttl=$array["CACHE_TIME"];
		$ttl=$array["CACHE_TIME"];
		
		
		
		if(isset($ForceConf["ttl"])){$ttl=$ForceConf["ttl"];}
		if(isset($ForceConf["negative_ttl"])){$negative_ttl=$ForceConf["ttl"];}
		if(isset($ForceConf["children-startup"])){$external_acl_startup=$ForceConf["children-startup"];}
		if(isset($ForceConf["children-idle"])){$external_acl_idle=$ForceConf["children-idle"];}
		if($external_acl_startup==1){$external_acl_startup=20;}
		
		$cache=new SquidCacheCenter();
		$CPUS=$cache->WORKERS_NUMBER();
		
		
		if($CPUS>1){
			
			$external_acl_startup=round($external_acl_startup/$CPUS);
			$external_acl_idle=round($external_acl_idle/$CPUS);
			if($external_acl_startup<2){$external_acl_startup=2;}
			if($external_acl_idle<1){$external_acl_idle=1;}
			
			if($MacProc>0){
				$MacProc=round($MacProc/$CPUS);
				if($MacProc<1){$MacProc=2;}
				if($MacProc<$external_acl_startup){$MacProc=$external_acl_startup;}
			}
		}
		
		
		$f[]="ttl=$ttl";
		if(!$NoNegCache){ 
			$f[]="negative_ttl=$negative_ttl";
		}else{
			$f[]="negative_ttl=0";
		}
		if($this->IS_32){
			$f[]="children-startup={$external_acl_startup}";
			$f[]="children-idle={$external_acl_idle}";
			if($MacProc>0){
				$f[]="children-max=$MacProc";
			}
			$f[]="ipv4";
		}else{
			
			if($external_acl_startup>1){
				$f[]="children={$external_acl_startup}";
			}
		}
		
		return @implode(" ", $f);
		
	}
	

	
	
	
	private function UfdbGuardSquidGuardIPWeb(){
		if(isset($GLOBALS["SquidGuardIPWebMEM"])){return $GLOBALS["SquidGuardIPWebMEM"];}
		$sock=new sockets();
		if($sock->EnableUfdbGuard()==0){
			$GLOBALS["SquidGuardIPWebMEM"]=null;
			return null;
		}
		include_once(dirname(__FILE__)."/class.compile.ufdbguard.inc");
		$conf=new compile_ufdbguard();
		$SquidGuardIPWeb=unserialize(@file_get_contents("/var/log/squid/SquidGuardIPWeb"));
		if(is_array($SquidGuardIPWeb)){
			$GLOBALS["SquidGuardIPWebMEM"]=$SquidGuardIPWeb["SquidGuardIPWeb"];
		}
		
		return $GLOBALS["SquidGuardIPWebMEM"];
		
	}
	
	
	
	private function external_acl_helper_timequota(){
		$GLOBALS["ArtcExtrnQuotaTimeScanner"]=false;
		if(!$this->CORP_LICENSE){return "# No license set\n";}
		$sock=new sockets();
		if($this->SquidUrgency==1){return null;}
		if(!$this->ASROOT){return;}
		$array["CACHE_TIME"]=360;
		$q=new mysql_squid_builder();
		$sql="SELECT COUNT( * ) as tcount
			FROM webfilters_sqgroups, webfilters_sqacls, webfilters_sqacllinks
			WHERE webfilters_sqacllinks.aclid = webfilters_sqacls.ID
			AND webfilters_sqacllinks.gpid = webfilters_sqgroups.ID
			AND webfilters_sqgroups.GroupType = 'quota_time'
			AND webfilters_sqgroups.enabled =1
			AND webfilters_sqacls.enabled =1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [CATZ]: quota_time $q->mysql_error\n";}return;}
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [CATZ]: quota_time {$ligne["tcount"]} items\n";}
		if($ligne["tcount"]==0){return;}
		
		if($this->is_auth()){$L[]="%LOGIN";}else{$L[]="%%";}
		$SRCEUI48="%SRCEUI48";
		if(!$this->ACL_ARP_ENABLED){$SRCEUI48=null;}
		if($this->IS_31){$SRCEUI48=null;}
		if($SRCEUI48==null){$SRCEUI48="%%";}
		
		$L[]="%SRC";
		$L[]=$SRCEUI48;
		$L[]="%>ha{X-Forwarded-For}";
		$CMDILES=@implode(" ", $L);
		
		$GLOBALS["ArtcExtrnQuotaTimeScanner"]=true;
		$external_acl_children=$this->SquidClientParams["external_acl_children"];
		$external_acl_startup=$this->SquidClientParams["external_acl_startup"];
		$external_acl_idle=$this->SquidClientParams["external_acl_idle"];
		$external_acl_childrens=$this->external_acl_childrens(0);
		$sql="SELECT webfilters_sqgroups.*,webfilters_sqacls.aclname
			FROM webfilters_sqgroups, webfilters_sqacls, webfilters_sqacllinks
			WHERE webfilters_sqacllinks.aclid = webfilters_sqacls.ID
			AND webfilters_sqacllinks.gpid = webfilters_sqgroups.ID
			AND webfilters_sqgroups.GroupType = 'quota_time'
			AND webfilters_sqgroups.enabled =1
			AND webfilters_sqacls.enabled =1";
		$results=$q->QUERY_SQL($sql);
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$rulename=$ligne["aclname"];
			$GLOBALS["LoadingExtrnQuotaTime"][$ligne["ID"]]=true;
			$count=$this->external_acl_helper_timequota_items($ligne["ID"]);
			$f[]="#Group id {$ligne["ID"]} $count item(s)";
			$f[]="external_acl_type ArtTime{$ligne["ID"]}  ttl=60 negative_ttl=30 children-startup=1 children-idle=2 $CMDILES %DST /usr/share/artica-postfix/external_acl_time_quota.php --gpid {$ligne["ID"]}\n";
		}
		
		
		@chmod("/usr/share/artica-postfix/external_acl_time_quota.php", 0755);
		@chown("/usr/share/artica-postfix/external_acl_time_quota.php", "squid");
		
		return @implode("\n", $f);
		
	}	
	
	private function external_acl_helper_timequota_items($gpid){
		$q=new mysql_squid_builder();
		$sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
		$results = $q->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {
			$rules[]=$ligne["pattern"];
			
		}
	
		@file_put_contents("/etc/squid3/acls/time_gpid{$gpid}.acl", serialize($rules));
		@chmod("/etc/squid3/acls/time_gpid{$gpid}.acl", 0755);
		@chown("/etc/squid3/acls/time_gpid{$gpid}.acl", "squid");
		return count($rules);
	
	}
	
	
	
	
	

	
	private function external_acl_helper_splash_interface(){
		$ArticaHotSpotInterface=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaHotSpotInterface");
		$ArticaSplashHotSpotPort=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaSplashHotSpotPort");
		$ArticaSplashHotSpotPortSSL=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaSplashHotSpotPortSSL");
		if($ArticaHotSpotInterface==null){$ArticaHotSpotInterface="eth0";}
		
		if(!is_numeric($ArticaSplashHotSpotPort)){$ArticaSplashHotSpotPort=16080;}
		if(!is_numeric($ArticaSplashHotSpotPortSSL)){$ArticaSplashHotSpotPortSSL=16443;}
		
		
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
		$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
		$ipaddr=$NETWORK_ALL_INTERFACES[$ArticaHotSpotInterface]["IPADDR"];
		return $ipaddr;
	}
	
	
	
	private function external_acl_helper_splash_screen(){
		return;
		if($this->SquidUrgency==1){return null;}
		if($this->EnableSplashScreen==0){return;}
		
		
		if(!$this->ASROOT){return;}
	
		$sock=new sockets();
		$EnableArticaHotSpot=$sock->GET_INFO("EnableArticaHotSpot");
		if(!is_numeric($EnableArticaHotSpot)){$EnableArticaHotSpot=0;}
		if($EnableArticaHotSpot==0){return;}
		
		
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
		$array=unserialize(base64_decode($sock->GET_INFO("HotSpotConfig")));
		$EnableSplashScreenAsObject=$sock->GET_INFO("EnableSplashScreenAsObject");
		if(!is_numeric($EnableSplashScreenAsObject)){$EnableSplashScreenAsObject=0;}
		$external_acl_helper_splash_interface=$this->external_acl_helper_splash_interface();
		
		if(!is_numeric($array["CACHE_TIME"])){$array["CACHE_TIME"]=120;}
		if($this->is_auth()){$L[]="%LOGIN";}
		$L[]="%SRC";
		if($this->ACL_ARP_ENABLED){$L[]="%SRCEUI48";}
		$L[]="%>ha{X-Forwarded-For}";
		if($this->IS_32){$ipv4=" ipv4";}	
		$acl_SplashPage="acl SplashPage dst $external_acl_helper_splash_interface";
		
		
		$external_acl_children=$this->SquidClientParams["external_acl_children"];
		$external_acl_startup=$this->SquidClientParams["external_acl_startup"];
		$external_acl_idle=$this->SquidClientParams["external_acl_idle"];		
	
		$helper="/usr/share/artica-postfix/external_acl_squid.php";
		@chmod($helper,0755);
		
		@mkdir("/var/lib/squid/SplashScreenSessions",0755,true);
		$unix->chown_func("squid","squid", "/var/lib/squid/SplashScreenSessions/*");
		$f[]="\n#----------- Splash Screen authentication mode ----------------";
		
		$external_acl_childrens=$this->external_acl_childrens(0,true);
		$f[]="external_acl_type SplashScreenAuthDef  $external_acl_childrens ".@implode(" ", $L)." %URI $helper --splash session-time={$array["CACHE_TIME"]}";
		$f[]="acl SplashScreenAuthAcl external SplashScreenAuthDef";
		$f[]="$acl_SplashPage";
		
		
		
		$f[]="deny_info 303:$SplashScreenURI?fromproxy=yes&request=%U SplashScreenAuthAcl";
		$GLOBALS["HTTP_ACCESS_SPLASH_SCREEN"]="http_access allow SplashScreenAuthAcl";
		$f[]="#-----------------------------------------------------------------\n";
		
		if($EnableSplashScreenAsObject==0){
			$accesses[]="# ------ SplashScreen Rules ------";
			//$accesses[]="http_access allow SplashPage";
			$accesses[]="http_access deny $this->MyStandPort!SplashScreenAuthAcl CONNECT";
			$accesses[]="http_access deny $this->MyStandPort!SplashPage !SplashScreenAuthAcl";
			$accesses[]="#\n";
		}
		
		$GLOBALS["HTTP_ACCESS"]["SPLASH"]=@implode("\n", $accesses);
		return @implode("\n", $f);
	}
	
	
	
	
	
	private function external_acl_helper_quota(){
		if($this->SquidUrgency==1){return null;}
		$q=new mysql_squid_builder();
		
		$sql="SELECT COUNT(*) as tcount FROM webfilters_quotas WHERE enabled=1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if($ligne["tcount"]==0){return;}
		$sock=new sockets();
		$acls=new squid_acls_groups();
		$acls->external_acl_helper_quota_builder();
		$array=unserialize(base64_decode($sock->GET_INFO("SquidQuotasParams")));
		if(!is_numeric($array["CACHE_TIME"])){$array["CACHE_TIME"]=120;}
		if(!is_numeric($array["DISABLE_MODULE"])){$array["DISABLE_MODULE"]=0;}
		if($array["DISABLE_MODULE"]==1){return;}
		if($array["TEMPLATE"]==null){$array["TEMPLATE"]="ERR_ACCESS_DENIED";}		
		
		
		if($this->is_auth()){$L[]="%LOGIN";}else{$L[]="%%";}
		$L[]="%SRC";
		if($this->ACL_ARP_ENABLED){$L[]="%SRCEUI48";}else{$L[]="%SRCEUI48";}
		$L[]="%>ha{X-Forwarded-For}";
		if($this->IS_32){$ipv4=" ipv4";}
		

			
		if(preg_match("#^ERR_(.+)#", $array["TEMPLATE"],$re)){
			$array["TEMPLATE"]=$re[1];
		}
		

		$L[]="%DST";
		$external_acl_childrens=$this->external_acl_childrens();
		$f[]="# ***** Quotas {$ligne["tcount"]} enabled rules ****";
		$f[]="external_acl_type quotas $external_acl_childrens ".@implode(" ", $L)." /usr/share/artica-postfix/external_acl_quota.php";
		$f[]="acl quotas_acl external quotas\n";
		$f[]="deny_info ERR_{$array["TEMPLATE"]} quotas_acl\n";
		$GLOBALS["HTTP_ACCESS"]["QUOTAS"]="http_access deny $this->MyStandPort!quotas_acl all";
		@chmod("/usr/share/artica-postfix/external_acl_quota.php", 0755);
		return @implode("\n", $f);
		
	}
	
	private function request_header_access(){
		if($this->SquidUrgency==1){return null;}
		if($this->IS_27){return "#request_header_access/request_header_replace not supported in 2.7x\n";}
		if(!$this->ASROOT){return;}
		$results=$this->_compile_parmz();
		$enabled=false;
		while (list($num,$val)=each($results)){if(preg_match("#enable-http-violations#", $val)){$enabled=true;}}
		if(!$enabled){return "# --------- enable-http-violations no such compilation option in ".count($results)." rows";}	
		$q=new mysql();
		$tr=array();
		$ALLOWED=array();
		$BANNED=array();
		$CHANGE=array();
		$final=null;
		$t=array();
		$sql="SELECT * FROM squid_header_access WHERE active=1";
		$results = $q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){return "# --------- MySql error detected\n";}
		if(mysql_num_rows($results)==0){return "# --------- 0 active entry\n";}	
		while ($ligne = mysql_fetch_assoc($results)) {
			
			if($ligne["allow"]==1){
				if($ligne["header"]=="All"){$final="request_header_access {$ligne["header"]} allow all";continue;}
				$ALLOWED[]="request_header_access {$ligne["header"]} allow all";
				
			}
			if($ligne["allow"]==0){
				if($ligne["header"]=="All"){$final="request_header_access {$ligne["header"]} deny all";continue;}
				$BANNED[]="request_header_access {$ligne["header"]} deny all";
			}
			
			if(strlen(trim($ligne["replacewith"]))>2){
				$ligne["replacewith"]=trim($ligne["replacewith"]);
				$CHANGE[]="request_header_replace {$ligne["header"]} {$ligne["replacewith"]}";
			}
			
		}
		
		$t[]="# --------- allow: ".count($ALLOWED)." deny:".count($BANNED)." modify: ".count($CHANGE);
		
		if(count($ALLOWED)>0){
			$t[]=@implode("\n", $ALLOWED);
		}
		if(count($BANNED)>0){
			$t[]=@implode("\n", $BANNED);
		}		
		if($final<>null){
			$t[]=$final;
		}
		if(count($CHANGE)>0){
			$t[]=@implode("\n", $CHANGE);
		}
		if(count($t)>0){return @implode("\n", $t);}
		
	}
	
	
	private function _compile_parmz(){
		if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
		if(!$this->ASROOT){return;}
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
		$squidbin=$unix->find_program("squid3");
		if(strlen($squidbin)<5){$squidbin=$unix->find_program("squid");}
		exec("$squidbin -v 2>&1",$results);
		if($GLOBALS["VERBOSE"]){echo "[DEBUG]: $squidbin -v -> ".count($results). "rows\n";}
		$GLOBALS[__FUNCTION__]=$results;
		return $results;
	}
	


	
	
	public function BuildBlockedSites(){
			if($this->SquidUrgency==1){return null;}
			$sql="SELECT uri FROM squid_block ORDER BY ID DESC";
			$q=new mysql();
			$tb=array();
			$results=$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){if(posix_getuid()==0){echo "Starting......: ".date("H:i:s")." [ERR]: Mysql database, error, skip blocked sites\n";}}
			
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				if(trim($ligne["uri"])==null){continue;}
				$pattern=$ligne["uri"];
				$pattern=str_replace(".","\.",$pattern);
				$pattern=str_replace("*",".*",$pattern);
				
				if(!preg_match("#^(http|ftp|ftps)\:#",$pattern)){
					$pattern="^.+?$pattern";
				}else{
					$pattern="^$pattern";
				}			
				$tb[]=$pattern;
			}
			
			$countdesite=count($tb);
			writelogs("$countdesite rows",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			if(posix_getuid()==0){echo "Starting......: ".date("H:i:s")." [ACLS]: $countdesite blocked site(s)\n";}
			
			if(is_array($tb)){$conf=implode("\n",$tb);}else{$conf="";}
			@file_put_contents("/etc/squid3/squid-block.acl",$conf);
			return $conf;
	}
		
function SaveToLdap($norestart=false){
			if(!$this->kav_accept){
				writelogs("SQUID:: kavaccept = FALSE",__CLASS__.'/'.__FUNCTION__,__FILE__);
				$this->enable_kavproxy=0;
			}
	
			$sock=new sockets();
			if(!is_numeric($this->snmp_port)){$this->snmp_port=3401;}
			$ini=null;
			$ini=$ini."[NETWORK]\n";
			$ini=$ini."cdir=".implode(";",$this->network_array)."\n";
			$ini=$ini."dns_servers=".implode(";",$this->dns_array)."\n";
			$ini=$ini."LISTEN_PORT=$this->listen_port\n";
			$ini=$ini."SECOND_PORT=$this->second_listen_port\n";
			$ini=$ini."ICP_PORT=$this->ICP_PORT\n";
			$ini=$ini."HTCP_PORT=$this->HTCP_PORT\n";
			$ini=$ini."SSL_PORT=$this->ssl_port\n";
			$ini=$ini."certificate_center=$this->certificate_center\n";
			$ini=$ini."SSL_BUMP_WHITE_LIST=$this->SSL_BUMP_WHITE_LIST\n";

			$ini=$ini."peer_connect_timeout=$this->peer_connect_timeout\n";
			$ini=$ini."connect_timeout=$this->connect_timeout\n";
			$ini=$ini."persistent_request_timeout=$this->persistent_request_timeout\n";
			$ini=$ini."client_lifetime=$this->client_lifetime\n";
			$ini=$ini."pconn_timeout=$this->pconn_timeout\n";
			$ini=$ini."dns_timeout=$this->dns_timeout\n";
			
			$ini=$ini."dead_peer_timeout=$this->dead_peer_timeout\n";
			$ini=$ini."shutdown_lifetime=$this->shutdown_lifetime\n";
			$ini=$ini."read_timeout=$this->read_timeout\n";
			$ini=$ini."incoming_rate=$this->incoming_rate\n";
			
			
			
			$ini=$ini."EnableParentProxy=$this->EnableParentProxy\n";
			$ini=$ini."prefer_direct=$this->prefer_direct\n";
			$ini=$ini."nonhierarchical_direct=$this->nonhierarchical_direct\n";
			$ini=$ini."max_filedesc=$this->max_filedesc\n";
			$ini=$ini."snmp_community=$this->snmp_community\n";
			$ini=$ini."snmp_enable=$this->snmp_enable\n";
			$ini=$ini."snmp_port=$this->snmp_port\n";
			$ini=$ini."snmp_access_ip=$this->snmp_access_ip\n";
	
			
			if($this->enable_dansguardian==1){
					$this->alt_listen_port=$sock->RandomPort();
					$dansguardian=new dansguardian();
					$dansguardian->SaveSettings();
			}
			$ini=$ini."wccp2_enabled=$this->wccp2_enabled\n";
			$ini=$ini."wccp2_router=$this->wccp2_router\n";
			$ini=$ini."wccp2_forwarding_method=$this->wccp2_forwarding_method\n";
			$ini=$ini."wccp2_return_method=$this->wccp2_return_method\n";
			$ini=$ini."wccp2_assignment_method=$this->wccp2_assignment_method\n";
			$ini=$ini."enable_ftp_restrictions=$this->enable_ftp_restrictions\n";
			$ini=$ini."EnableUserAgentBanAll=$this->EnableUserAgentBanAll\n";
			$ini=$ini."EnableChangeRequestSize=$this->EnableChangeRequestSize\n";
			$ini=$ini."allow_squid_localhost=$this->allow_squid_localhost\n";
			$ini=$ini."url_rewrite_bypass=$this->url_rewrite_bypass\n";
			$ini=$ini."EnableICPPort=$this->EnableICPPort\n";
			$ini=$ini."forwarded_for=$this->forwarded_for\n";
			
			if($this->IS_30){if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [VER]: is 3.x OK\n";}}
			if(!$this->IS_32){if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [VER]: is NOT 3.2.x OK\n";}}
			if($this->IS_32){if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [VER]: is 3.2.x OK\n";}}
			
			if(($this->IS_30) && (!$this->IS_32)){
				$ini=$ini."ignore_expect_100=$this->ignore_expect_100\n";
			}
			
			
			
			
			$ini=$ini."ALT_PORT=$this->alt_listen_port\n";
			$ini=$ini."visible_hostname=$this->visible_hostname\n";
			
			
			
			writelogs("Save LDAP_AUTH=$this->LDAP_AUTH",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			$sock->SET_INFO("SquidStandardLDAPAuth", $this->LDAP_AUTH);
			$sock->SET_INFO("SquidLdapAuthEnableGroups", $this->SquidLdapAuthEnableGroups);
			$ini=$ini."LDAP_AUTH=$this->LDAP_AUTH\n";
			$ini=$ini."NTLM_AUTH=$this->NTLM_AUTH\n";
			$ini=$ini."LDAP_EXTERNAL_AUTH=$this->LDAP_EXTERNAL_AUTH\n";
			$ini=$ini."SSL_BUMP=$this->SSL_BUMP\n";
			
			
			$this->CACHE_PATH=str_replace(" ", "_", $this->CACHE_PATH);
			$ini=$ini."[KAV]\n";
			$ini=$ini."enabled=$this->enable_kavproxy\n";
			$ini=$ini."[DANSGUARDIAN]\n";
			$ini=$ini."enable_dansguardian=$this->enable_dansguardian\n";
			$ini=$ini."[CACHE]\n";
			$ini=$ini."CACHE_SIZE=$this->CACHE_SIZE\n";
			$ini=$ini."CACHE_PATH=$this->CACHE_PATH\n";
			$ini=$ini."CACHE_TYPE=$this->CACHE_TYPE\n";
			
			if(is_array($this->nodes_names)){
				reset($this->nodes_names);
				$ini=$ini."[NODES_NAME]\n";
				while (list ($num, $val) = each ($this->nodes_names)){
					$ini=$ini."$num=$val\n";
				}
			}
							
	

			if(is_array($this->cache_list)){
				reset($this->cache_list);
				$num=null;
				$val=null;
				while (list ($num, $val) = each ($this->cache_list)){
					$num=str_replace(" ", "_", $num);
					$ini=$ini."[cache:$num]\n";
					$ini=$ini."cache_type={$val["cache_type"]}\n";
					$ini=$ini."cache_dir_level1={$val["cache_dir_level1"]}\n";
					$ini=$ini."cache_dir_level2={$val["cache_dir_level2"]}\n";
					$ini=$ini."cache_size={$val["cache_size"]}\n";
					$ini=$ini."cache_maxsize={$val["cache_maxsize"]}\n";
				}
			}else{
				writelogs("cache_list, not an array...",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			}
			
			if(is_array($this->acl_times)){
				reset($this->acl_times);
				while (list ($num, $array) = each ($this->acl_times)){
					$ini=$ini."[$num]\n";
					while (list ($a, $b) = each ($array)){
						$ini=$ini."$a=$b\n";
					}
					
				}
				
			}
			
			writelogs("Save SquidGlobalConfArray ".count($this->global_conf_array)." parameters",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			$sock->SaveConfigFile(base64_encode(serialize($this->global_conf_array)),"SquidGlobalConfArray");
			$sock->SET_INFO("DansGuardianEnabled",$this->enable_dansguardian);
			$sock->SET_INFO('kavicapserverEnabled',$this->enable_kavproxy);
			$sock->SET_INFO("hasProxyTransparent",$this->hasProxyTransparent);
			$sock->SaveConfigFile($ini,"ArticaSquidParameters");
			//$sock->SaveConfigFile($this->BuildSquidConf(),"GlobalSquidConf");
			$sock->SET_INFO("ArticaEnableKav4ProxyInSquid",$this->enable_kavproxy);
			$sock->SET_INFO("CicapEnabled",$this->enable_cicap);
			$sock->SET_INFO("squidGuardEnabled",$this->enable_squidguard);
			$sock->SET_INFO("EnableAdZapper",$this->enable_adzapper);
			$sock->SET_INFO("EnableSquidClamav",$this->enable_squidclamav);
			$sock->SET_INFO("KavMetascannerEnable",$this->enable_metascanner);
			$sock->SET_INFO("SquidEnableStreamCache",$this->enable_streamcache);
			$sock->SET_INFO("SquideCapAVEnabled",$this->enable_ecapav);
			
			
			
			
			$sock->SaveConfigFile(base64_encode(serialize($this->EXTERNAL_LDAP_AUTH_PARAMS)),"SquidExternalAuth");
			$sock->SaveConfigFile(base64_encode(serialize($this->FTP_PARAMS)),"SquidFTPParams");
			$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
			if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
			
			if(class_exists("usersMenus")){
				$users=new usersMenus();
				if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}
			}
			
			if($this->enable_dansguardian==1){
				writelogs("",__FUNCTION__,__FILE__);
				writelogs("Save Dansguardian configuration...",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$dans=new dansguardian();
				$dans->SaveSettings();
			}
			
			if($this->enable_kavproxy==1){
				$sock->getFrameWork("cmd.php?kav4proxy-reconfigure=yes");
			}
			
			if($EnableWebProxyStatsAppliance==1){
				$sock=new sockets();
				$sock->getFrameWork("squid.php?notify-remote-proxy=yes");
				return true;
			}	
			
			if(!$norestart){
				$sock->getFrameWork("squid.php?build-smooth=yes");
			}
			return true;
			
		}
		
		function SaveToServer($norestart=false){
			$sock=new sockets();
			$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
			if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
			
			if(class_exists("usersMenus")){
				$users=new usersMenus();
				if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}
			}		

			if($EnableWebProxyStatsAppliance==1){
				$sock=new sockets();
				$sock->getFrameWork("squid.php?notify-remote-proxy=yes");
				return;
			}
			
			if(!$norestart){
				$sock=new sockets();
				$sock->getFrameWork("cmd.php?squidnewbee=yes");	
			}
		}
		
		

		
		public function SquidGuardDatabasesStatus($all=0){
			$datas=explode("\n",@file_get_contents("/etc/squid/squidGuard.conf"));
			while (list ($a, $b) = each ($datas)){
				if(preg_match("#domainlist.+?(.+)#",$b,$re)){
					$file="/var/lib/squidguard/{$re[1]}";
					if(filesize($file)==0){continue;}
					$f[]=$file;
					continue;
				}
						
				if(preg_match("#urllist.+?(.+)#",$b,$re)){
					$file="/var/lib/squidguard/{$re[1]}";
					if(filesize($file)==0){continue;}
					$f[]=$file;
					continue;
				}
				
				
			}
			
			if($all==0){while (list ($a, $b) = each ($f)){
				if(!is_file("$b.db")){
					$array[]="$b.db";
				}
				
			}}else{
				while (list ($a, $b) = each ($f)){
					$array[]="$b.db";
				}
			}
			
			
			return $array;
			
		}

		
		public function is_auth(){
			if($this->SquidUrgency==1){return false;}
			if(isset($GLOBALS["squid_is_auth"])){return $GLOBALS["squid_is_auth"];}
			$GLOBALS["squid_is_auth"]=$this->_is_auth();
			
		}

		private function _is_auth(){
			if($this->SquidUrgency==1){return false;}
			if($this->LDAP_AUTH==1){return true;}
			if($this->LDAP_EXTERNAL_AUTH==1){return true;}
			if($this->EnableKerbAuth==1){return true;}
			$sock=new sockets();
			$EnableArticaHotSpot=$sock->GET_INFO("EnableArticaHotSpot");
			if(!is_numeric($EnableArticaHotSpot)){$EnableArticaHotSpot=0;}
			if($EnableArticaHotSpot==1){return true;}
			$acl=new squid_acls();
			if($acl->IsObjectAuth()){return true;}
			return false;
		}
		
		
		private function ldap_auth_conf($NokerbCheck=false){
			if($this->BasicAuthenticatorEmergency==1){
				return "# ldap_auth_conf Emergency !!!!\n";
			}
			if(isset($GLOBALS["ldap_auth_conf[$NokerbCheck]"])){return;}
			$GLOBALS["ldap_auth_conf[$NokerbCheck]"]=true;
			if($this->SquidUrgency==1){return null;}
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [AUTH]: LDAP_AUTH = $this->LDAP_AUTH\n";}
			if(!is_array($this->global_conf_array)){$this->ParseConfig();}
			if($GLOBALS["VERBOSE"]){if(!$this->ASROOT){$GLOBALS["VERBOSE"]=false;}}
			$users=new usersMenus();
			$sock=new sockets();
			$childrens=$this->SquidClientParams["auth_param_basic_children"];
			$startup=$this->SquidClientParams["auth_param_basic_startup"];
			$idle=$this->SquidClientParams["url_rewrite_idle"];
			$auth_param_basic_children=$this->auth_param_basic_children();
			$cacheCenter=new SquidCacheCenter();
			if($childrens<10){$childrens=10;}
			if($auth_param_basic_children<10){$auth_param_basic_children=10;}
			if($startup<1){$startup=1;}
			if($idle<1){$idle=1;}
			if($startup>$childrens){$startup=$childrens;}
			if($idle>$childrens){$idle=1;}
				
			
			
			if($this->IS_32){$auth_param_child=" startup={$startup} idle={$idle}";}
			$EnableKerbAuth=$this->EnableKerbAuth;
			if($this->ActiveDirectoryEmergency==1){
				$EnableKerbAuth=0;
				$conf[]= "#". __FUNCTION__."(); Active Directory Emergency is enabled, Aborting...[".__LINE__."]\n";
			}
			
			if(!$NokerbCheck){
				if($EnableKerbAuth==1){
						if($this->LDAP_AUTH==0){
							if(is_file($users->SQUID_NTLM_AUTH)){
								$conf[]= "#Basic authentication for other browser that did not supports NTLM BasicAuthenticatorEmergency=$this->BasicAuthenticatorEmergency";
								if($this->BasicAuthenticatorEmergency==0){
									$conf[]= "auth_param basic program $users->SQUID_NTLM_AUTH --helper-protocol=squid-2.5-basic";
									$conf[]= "auth_param basic children {$auth_param_basic_children}$auth_param_child";
									$conf[]= "auth_param basic realm Basic Identification";
									$conf[]= "auth_param basic credentialsttl 4 hours\n";
								}
								$conf[]= "";
							}else{
								$conf[]= "#Basic authentication SQUID_NTLM_AUTH no such binary\n";
								
							}
						return @implode("\n", $conf);
						}
					
				}
				if($GLOBALS["VERBOSE"]){
					echo "DEBUG : LDAP_AUTH:$this->LDAP_AUTH\n";
					echo "DEBUG : LDAP_EXTERNAL_AUTH:$this->LDAP_EXTERNAL_AUTH\n";
				}
			}
			
			if($this->LDAP_AUTH==0){
				if($this->LDAP_EXTERNAL_AUTH<>1){
					if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [AUTH]: LDAP authentication method is disabled\n";}
					return "#--------- LDAP authentication method is disabled\n";
					return null;
				}
			}
			
			
			if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
			$users=$GLOBALS["CLASS_USERS"];
			$ldap=new clladp();
			if($ldap->ldap_host==null){$ldap->ldap_host="127.0.0.1";}
			
			$ldap_host=$ldap->ldap_host;
			$ldap_port=$ldap->ldap_port;
			$suffix=$ldap->suffix;
			$ldap_admin="cn=$ldap->ldap_admin,$ldap->suffix";
			$ldap_password=$ldap->ldap_password;
			$user_filter="(&(objectClass=userAccount)(uid=%s))";
			$group_filter="(&(objectClass=posixGroup)(gidNumber=%a)(memberUid=%v))";
			$auth_banner="Basic credentials, Please logon...";
			$SquidLdapAuthBanner=$sock->GET_INFO("SquidLdapAuthBanner");
			if($SquidLdapAuthBanner<>null){$auth_banner=$SquidLdapAuthBanner;}
			
			
			$conf[]= "#--------- External LDAP database = \"$this->LDAP_EXTERNAL_AUTH\"\n";
			
			if($this->LDAP_EXTERNAL_AUTH==1){
				$squid_external_ldap_builder=new squid_external_ldap_builder();
				$conf[]=$squid_external_ldap_builder->Build();
				$conf[]="";
				$this->SquidLdapAuthEnableGroups=1;
				return @implode("\n", $conf);					
			}
			
			

					

			
			$SQUID_LDAP_AUTH=$this->squid_ldap_auth_path();
			$squid_ldap_group_path=$this->squid_ldap_group_path();
			$conf[]= "#--------- LDAP AUTH settings\n";
			$conf[]= "#Authentification mode, building using squid compiled for $ldap_host:$ldap_port";
			if(trim($SQUID_LDAP_AUTH)<>null){
				
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [AUTH]: Using User:`$SQUID_LDAP_AUTH`, Group `$squid_ldap_group_path`\n";}
				$conf[]= "# BasicAuthenticatorEmergency = $this->BasicAuthenticatorEmergency";
				$conf[]= "auth_param basic program $SQUID_LDAP_AUTH -b \"$suffix\" -D \"$ldap_admin\" -w \"$ldap_password\" -f \"$user_filter\" -v 3 -h $ldap_host -p $ldap_port";
				
				$conf[]= "#--------- GLOBAL";
				$conf[]= "external_acl_type ldap_group ipv4 %LOGIN " . $this->squid_ldap_group_path()." -D \"$ldap_admin\" -w \"$ldap_password\" -b \"$suffix\"  -f \"$group_filter\" -S -v 3 -h $ldap_host -p $ldap_port";
				
				$conf[]= "auth_param basic children {$childrens}$auth_param_child";
				$conf[]= "auth_param basic credentialsttl 4 hour";
				$conf[]= "auth_param basic realm $auth_banner";
				
				$conf[]= "authenticate_ttl 4 hour";
				$conf[]= "authenticate_ip_ttl 4 hour";	
				$conf[]= "acl ldapauth proxy_auth REQUIRED";
				
				$conf[]=$this->WHITELISTED_AUTH_BROWSERS();
				$GLOBALS["HTTP_ACCESS"]["LDAP_AUTH"]="http_access allow {$this->MyStandPort}ldapauth";
				
				$conf[]="";
			}else{
				$conf[]= "#No LDAP auth_param basic program found !\n";
				$conf[]="";
			}

			return implode("\n",$conf);
			
		}
		
		private function ACL_FTP_RESTRICTIONS(){
			if($this->SquidUrgency==1){return null;}
			if($this->enable_ftp_restrictions<>1){return;}
			return "acl clients_ftp src \"/etc/squid3/clients_ftp.acl\"\n";
		}
		
		private function squid_ldap_group_path(){
			if($this->SquidUrgency==1){return null;}
			$basic=$this->squid_ldap_auth_path();
			if(strlen($basic)==0){return;}
			$basic=dirname($basic);
			if(is_file("$basic/squid_ldap_group")){return "$basic/squid_ldap_group";}
			if(is_file("$basic/ext_ldap_group_acl")){return "$basic/ext_ldap_group_acl";}
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [AUTH]: WARNING : no ext group found in `$basic` path !!!\n";}
			
			
			
		}
		
		private function squid_ldap_auth_path(){
			if(is_file('/lib/squid3/basic_ldap_auth')){return '/lib/squid3/basic_ldap_auth';}
			if(is_file('/usr/lib/squid3/squid_ldap_auth')){return '/usr/lib/squid3/squid_ldap_auth';}
			if(is_file('/usr/lib64/squid3/squid_ldap_auth')){return '/usr/lib64/squid3/squid_ldap_auth';}
			if(is_file('/lib/squid3/squid_ldap_auth')){return '/lib/squid3/squid_ldap_auth';}
			if(is_file('/lib64/squid3/squid_ldap_auth')){return '/lib64/squid3/squid_ldap_auth';}
			if(is_file('/usr/lib/squid/ldap_auth')){return '/usr/lib/squid/ldap_auth';}
			if(is_file('/usr/lib/squid/squid_ldap_auth')){return '/usr/lib/squid/squid_ldap_auth';}
			if(is_file('/usr/lib64/squid/squid_ldap_auth')){return '/usr/lib64/squid/squid_ldap_auth';}
			if(is_file('/usr/lib64/squid/ldap_auth')){return '/usr/lib64/squid/ldap_auth';}
			if(is_file('/usr/local/lib/squid/ldap_auth')){return '/usr/local/lib/squid/ldap_auth';}
			if(is_file('/usr/local/lib64/squid/ldap_auth')){return '/usr/local/lib64/squid/ldap_auth';}
			if(is_file('/opt/artica/libexec/squid_ldap_auth')){return '/opt/artica/libexec/squid_ldap_auth';} 			
	
		}
		
		
		private function squid_ext_session_acl_path(){
			if(is_file('/lib/squid3/ext_session_acl')){return '/lib/squid3/ext_session_acl';}
			if(is_file('/usr/lib/squid3/ext_session_acl')){return '/usr/lib/squid3/ext_session_acl';}
			if(is_file('/usr/lib64/squid3/ext_session_acl')){return '/usr/lib64/squid3/ext_session_acl';}
			if(is_file('/lib/squid3/ext_session_acl')){return '/lib/squid3/ext_session_acl';}
			if(is_file('/lib64/squid3/ext_session_acl')){return '/lib64/squid3/ext_session_acl';}
			if(is_file('/usr/lib/squid/ext_session_acl')){return '/usr/lib/squid/ext_session_acl';}
			if(is_file('/usr/lib/squid/ext_session_acl')){return '/usr/lib/squid/ext_session_acl';}
			if(is_file('/usr/lib64/squid/ext_session_acl')){return '/usr/lib64/squid/ext_session_acl';}
			if(is_file('/usr/lib64/squid/ext_session_acl')){return '/usr/lib64/squid/ext_session_acl';}
			if(is_file('/usr/local/lib/squid/ext_session_acl')){return '/usr/local/lib/squid/ext_session_acl';}
			if(is_file('/usr/local/lib64/squid/ext_session_acl')){return '/usr/local/lib64/squid/ext_session_acl';}
			if(is_file('/opt/artica/libexec/ext_session_acl')){return '/opt/artica/libexec/ext_session_acl';} 				
		}
	
		

		
		
		private function build_progress($text){
			
			if(!isset($GLOBALS["PRC"])){$GLOBALS["PRC"]=$this->CURRENT_PROGRESS;}
			if(!$this->ASROOT){return;}
			
				///--wccp-build
			
			$GLOBALS["PRC"]++;
			echo "Starting......: ".date("H:i:s")." $text {$GLOBALS["PRC"]}%\n";
			if(!function_exists("build_progress")){return;}
			if($this->CURRENT_PROGRESS==0){return;}
			if($GLOBALS["PRC"]>$this->MAX_PROGRESS){$GLOBALS["PRC"]=$this->MAX_PROGRESS;}
			build_progress("{reconfigure} $text",$GLOBALS["PRC"]);
		}

		
		
		private function WCCP(){
			$ipClass=new IP();
			
			
			if($this->wccp2_enabled<>1){return "# --------- Cisco's Web Cache Coordination Protocol is not enabled\n";}
			$conf[]="";	
			$sock=new sockets();
			
			$wccp2_router=array();
			$wccp2_routers=$this->WCCPHash["wccp2_router"];
			if(strpos($wccp2_routers, ",")>0){
				$wccp2_router=explode(",",$wccp2_routers);
			}else{
				$wccp2_router[]=$wccp2_routers;
			}
			
			$conf[]="# --------- Cisco's Web Cache Coordination Protocol";
			while (list ($none, $ipaddr) = each ($wccp2_router) ){
				if($ipClass->isValid($ipaddr)){
					$conf[]="wccp2_router $ipaddr";
				}
				
			}
			
			$conf[]="wccp2_address {$this->WCCPHash["listen_address"]}";
			$conf[]="wccp2_forwarding_method {$this->WCCPHash["wccp2_forwarding_method"]}";
			$conf[]="wccp2_return_method {$this->WCCPHash["wccp2_return_method"]}";
			$conf[]="wccp2_assignment_method {$this->WCCPHash["wccp2_assignment_method"]}";
			$conf[]="";	
			return implode("\n",$conf);
		}
		
		private function find_program($binaryname){
			$f["/lib/squid3"]=true; 
			$f["/usr/lib/squid3"]=true; 
			$f["/usr/lib64/squid3"]=true; 
			$f["/lib/squid3"]=true; 
			$f["/lib64/squid3"]=true; 
			$f["/usr/lib/squid"]=true; 
			$f["/usr/lib64/squid"]=true; 
			$f["/usr/local/lib/squid"]=true; 
			$f["/usr/local/lib64/squid"]=true; 
			$f["/opt/artica/libexec"]=true; 
			while (list ($dir, $none) = each ($f) ){if(is_file("$dir/$binaryname")){return "$dir/$binaryname";}}			
			
			
		}
		
		
		private function pinger(){
			if(!$this->IS_34){return;}
			if(!$this->ASROOT){return;}
			$sock=new sockets();
			$SquidEnablePinger=intval($sock->GET_INFO("SquidEnablePinger"));
			if($SquidEnablePinger==0){
				$f[]="#ICMP Pinger ------------- (". __FUNCTION__." L.".__LINE__.")";
				$f[]="pinger_enable off";
				$f[]="";
				return implode("\n",$f);
			}
			
			$pinger=$this->find_program("pinger");
			if(!is_file($pinger)){return;}
			$f[]="#ICMP Pinger ------------- (". __FUNCTION__." L.".__LINE__.")";
			$f[]="pinger_program $pinger";
			$f[]="pinger_enable on";
			$f[]="";
			if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
			$unix->chown_func("root","root" ,$pinger);
			$unix->chmod_func(04755, $pinger);
			return implode("\n",$f);
		}
		
		
		public function ACL_MESSENGERS(){
			if($this->SquidUrgency==1){return null;}
			$sock=new sockets();
			$SquidMessengers=unserialize(base64_decode($sock->GET_INFO("SquidMessengers")));
			if($SquidMessengers["AOL"]==1){
				$conf[]="";
				$conf[]="# AOL Instant Messenger to connect to oscar.aol.com";
				$conf[]="acl AIM_ports port 5190 9898";
				$conf[]="acl AIM_domains dstdomain .oscar.aol.com .blue.aol.com";
				$conf[]="acl AIM_domains dstdomain .messaging.aol.com .aim.com";
				$conf[]="acl AIM_hosts dstdomain login.oscar.aol.com login.glogin.messaging.aol.com toc.oscar.aol.com";
				$conf[]="acl AIM_nets dst 64.12.0.0/255.255.0.0";
				$conf[]="acl AIM_methods method CONNECT";
				$GLOBALS["HTTP_ACCESS"]["AOL_MESSENGERS"]="http_access allow {$this->MyStandPort}AIM_methods AIM_ports AIM_nets\nhttp_access allow AIM_methods AIM_ports AIM_hosts";				
			}
			
			if($SquidMessengers["IRC"]==1){	
				$conf[]="";		
				$conf[]="# Permit IRC";
				$conf[]="acl IRC_ports port 6667";
				$conf[]="acl IRC_domains dstdomain .freenode.net";
				$conf[]="acl IRC_hosts dstdomain  irc.freenode.net";
				$conf[]="acl IRC_methods method CONNECT";		
				$GLOBALS["HTTP_ACCESS"]["IRC_MESSENGERS"]="http_access allow {$this->MyStandPort}IRC_methods IRC_ports IRC_hosts\nhttp_access allow IRC_methods IRC_ports IRC_domains";
				}
		
			if($SquidMessengers["YAHOO"]==1){
				$conf[]="";				
				$conf[]="# Permit Yahoo Messenger";
				$conf[]="acl YIM_ports port 5050";
				$conf[]="acl YIM_domains dstdomain .yahoo.com .yahoo.co.jp";
				$conf[]="acl YIM_hosts dstdomain scs.msg.yahoo.com cs.yahoo.co.jp";
				$conf[]="acl YIM_methods method CONNECT";
				$GLOBALS["HTTP_ACCESS"]["YAHOO_MESSENGERS"]="http_access allow {$this->MyStandPort}YIM_methods YIM_ports YIM_hosts\nhttp_access allow YIM_methods YIM_ports YIM_domains";
			}
			
			if($SquidMessengers["GOOGLE"]==1){
				$conf[]="";
				$conf[]="# Permit Google Talk";
				$conf[]="acl GTALK_ports port 5222 5050 443";
				$conf[]="acl GTALK_domains dstdomain .google.com";
				$conf[]="acl GTALK_hosts dstdomain talk.google.com";
				$conf[]="acl GTALK_methods method CONNECT";		

				
				
				$GLOBALS["HTTP_ACCESS"]["GOOGLE_MESSENGERS"]="http_access allow  {$this->MyStandPort}GTALK_ports GTALK_hosts GTALK_methods\n
				http_access allow {$this->MyStandPort}GTALK_methods GTALK_ports GTALK_domains";
			}
			if($SquidMessengers["MSN"]==1){
				$conf[]="";
				$conf[]="# Permit MSN";
				$conf[]="acl MSN_ports port 1863 443 1503";
				$conf[]="acl MSN_domains dstdomain .microsoft.com .hotmail.com .live.com .msft.net .msn.com .passport.com";
				//$conf[]="acl MSN_hosts dstdomain messenger.hotmail.com";
				//$conf[]="acl MSN_nets dst 207.46.111.0/255.255.255.0";
				$conf[]="acl MSN_methods method CONNECT";	
				//$SquidMessengers_httpaccess[]="http_access allow MSN_methods MSN_ports MSN_hosts";
				//$SquidMessengers_httpaccess[]="http_access allow MSN_hosts";
				$SquidMessengers_httpaccess[]="http_access allow {$this->MyStandPort}MSN_ports MSN_domains MSN_methods";
				//$SquidMessengers_httpaccess[]="http_access allow MSN_domains";
				//$SquidMessengers_httpaccess[]="http_access allow MSN_methods";
				//$SquidMessengers_httpaccess[]="http_access allow MSN_methods MSN_ports MSN_domains";
				//$SquidMessengers_httpaccess[]="http_access allow MSN_methods MSN_ports MSN_nets";
				$SquidMessengers_httpaccess[]="";
				
				$GLOBALS["HTTP_ACCESS"]["MSN_MESSENGERS"]=@implode("\n",$SquidMessengers_httpaccess);
			}
			
			if(!isset($conf)){return null;}
			if(!is_array($conf)){return null;}
			return @implode("\n",$conf)."\n\n";
			
		}
		
    private function transparent_token(){
    	$_transparent=" transparent";
    	if($this->IS_30){$_transparent=" transparent";}
    	if($this->IS_31){$_transparent=" intercept";}
    	if($this->IS_32){$_transparent=" intercept";}
    	if($this->IS_33){$_transparent=" intercept";}
    	if($this->IS_34){$_transparent=" intercept";}
    	if($this->IS_40){$_transparent=" intercept";}
    	if($this->UseTProxyMode==1){$_transparent=" tproxy";}   	
    	return $_transparent;
    }		

 
    
    private function myport_token(){
    	$token="myport";
    	if($this->IS_30){$token="myport";}
    	if($this->IS_33){$token="localport";}
    	if($this->IS_34){$token="localport";}
    	if($this->IS_40){$token="localport";}
    	return $token;
    }
    
    private function memory_pools(){
    	$sock=new sockets();
    	$SquidMemoryPools=intval($sock->GET_INFO("SquidMemoryPools"));
    	if($SquidMemoryPools==0){return "memory_pools off";}
    	
    	$memory_pools_limit_suffix=null;
    	$SquidMemoryPoolsLimit=intval($sock->GET_INFO("SquidMemoryPoolsLimit"));
    	if($SquidMemoryPoolsLimit>0){$memory_pools_limit_suffix=" MB";}
    	$f[]="memory_pools on";
    	if($SquidMemoryPoolsLimit==0){
    		$f[]="memory_pools_limit none";
    	}else{
    		$f[]="memory_pools_limit $SquidMemoryPoolsLimit$memory_pools_limit_suffix";
    	}
    	return @implode("\n", $f);
    }
    
    
    
  
    
    private function Mikrotik_tcp_outgoing_address(){
    	$sock=new sockets();
    	$MikrotikTransparent=intval($sock->GET_INFO('MikrotikTransparent'));
    	if($MikrotikTransparent==0){return;}
    	$SquidNetworkSwitch=$sock->GET_INFO("SquidNetworkSwitch");
    	$MikrotikVirtualIP=$sock->GET_INFO('MikrotikVirtualIP');
    	
    	if($SquidNetworkSwitch<>null){
    		$f[]="# Mikrotik outgoing Interface: [$MikrotikVirtualIP] [".__LINE__."]";
    		$f[]="# Disabled by SquidNetworkSwitch item";
    		return @implode("\n", $f);
    		
    	}
    	$this->global_conf_array["tcp_outgoing_address"]=null;
    	$MikrotikHTTPSquidPort=intval($sock->GET_INFO('MikrotikHTTPSquidPort'));
    	
    	$f[]="# Mikrotik outgoing Interface: [$MikrotikVirtualIP] [".__LINE__."]";
    	$f[]="tcp_outgoing_address $MikrotikVirtualIP all";
    	return @implode("\n", $f);
    	
    }
    
    private function tcp_outgoing_address_switch(){
    	if(!$this->ASROOT){return;}
    	$sock=new sockets();
    	$SquidNetworkSwitch=$sock->GET_INFO("SquidNetworkSwitch");
    	if($SquidNetworkSwitch==null){return;}
    	$unix=new unix();
    	if(!$unix->NETWORK_INTERFACE_OK($SquidNetworkSwitch)){
    		$f[]="# Quick Network switch outgoing Interface: [$SquidNetworkSwitch] unavailable [".__LINE__."]";
    		return @implode("\n", $f);
    	}
    	
    	$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
    	$ipaddr=$NETWORK_ALL_INTERFACES[$SquidNetworkSwitch]["IPADDR"];
    	if(!$unix->NETWORK_IS_LISTEN_ADDR_EXISTS($ipaddr)){
    		$f[]="# Quick Network switch outgoing Interface: $SquidNetworkSwitch [$ipaddr] unavailable [".__LINE__."]";
    		return @implode("\n", $f);
    	}
    	
    	$f[]="#  Quick Network switch Interface: [$SquidNetworkSwitch] [".__LINE__."]";
    	$f[]="tcp_outgoing_address $ipaddr all";
    	return @implode("\n",$f);
    	
    }

 	


		
	private function CoovaChilliEnabled(){
		if($this->SquidUrgency==1){return false;}
		if($this->ChilliEnabled){return true;}
		if(isset($GLOBALS["ChilliEnabled"])){return $GLOBALS["ChilliEnabled"];}
		if(!$this->ASROOT){return;}
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
		$chilli=$unix->find_program("chilli");
		if(!is_file($chilli)){$GLOBALS["ChilliEnabled"]=false;return;}
		$sock=new sockets();
		$EnableChilli=$sock->GET_INFO("EnableChilli");
		if(!is_numeric($EnableChilli)){$EnableChilli=0;}
		if($EnableChilli==1){$GLOBALS["ChilliEnabled"]=true;$this->ChilliEnabled=true;return true;}
		$GLOBALS["ChilliEnabled"]=false;
		return false;
	}
			

		
		
		public function StringToRegex($pattern){
			$pattern=str_replace("/", "\/", $pattern);
			$pattern=str_replace(".", "\.", $pattern);
			$pattern=str_replace("(", "\(", $pattern);
			$pattern=str_replace(")", "\)", $pattern);
			$pattern=str_replace("+", "\+", $pattern);
			$pattern=str_replace("?", "\?", $pattern);
			$pattern=str_replace("[", "\[", $pattern);
			$pattern=str_replace("]", "\]", $pattern);
			$pattern=str_replace("*", ".*", $pattern);
			return $pattern;
			
		}
		
	
		
		

		
	private function ACL_HOTSPOT_URIS(){
		if($this->SquidUrgency==1){return null;}
		if(!$this->ASROOT){return;}
		$acls=array();
		if(isset($GLOBALS[__CLASS__][__FUNCTION__])){return @implode("\n",$GLOBALS[__CLASS__][__FUNCTION__]);}
				
		$sql="SELECT servername FROM freeweb WHERE groupware='SPLASHSQUID'";
		
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");
		
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$servername=$ligne["servername"];
			$acls[]=".$servername";
		}
		if(count($acls)==0){$GLOBALS[__CLASS__][__FUNCTION__]=null;return;}
		@file_put_contents("/etc/squid3/acls/SplashScreens.txt", @implode("\n", $acls)."\n");
		$results="acl SplashScreens dstdomain \"/etc/squid3/acls/SplashScreens.txt\"\n";
		$GLOBALS["HTTP_ACCESS_ALLOW_SPLASHS"]="http_access allow {$this->MyStandPort}SplashScreens";
		$GLOBALS[__CLASS__][__FUNCTION__]=$results;
		return $results;
		
	}
		
	private function ACL_TEMPLATES_URIS(){
		if($this->SquidUrgency==1){return null;}
		if(!$this->ASROOT){return;}
		if(isset($GLOBALS[__CLASS__][__FUNCTION__])){return @implode("\n",$GLOBALS[__CLASS__][__FUNCTION__]);}
		$q=new mysql_squid_builder();
		$sql="SELECT template_uri FROM squidtpls WHERE template_link=1";
		$results = $q->QUERY_SQL($sql);
		$acls=array();
		$ALREADY=array();
		while ($ligne = mysql_fetch_assoc($results)) {
			if(trim($ligne["template_uri"])==null){return;}
			
			$uri=$this->ACL_TEMPLATES_URIS_TO_DSTDOMAIN($ligne["template_uri"]);
			if(isset($ALREADY[$uri])){continue;}$ALREADY[$uri]=true;
			$acls[]=$uri;
		}
		
		$GetUfdBguardRedirect=$this->GetUfdBguardRedirect();
		
		while (list ($tpl, $line) = each ($GetUfdBguardRedirect)){
			$uri=$this->ACL_TEMPLATES_URIS_TO_DSTDOMAIN($tpl);
			if(isset($ALREADY[$uri])){continue;}$ALREADY[$uri]=true;
			$acls[]=$uri;
		}
		
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ACLS]: ".count($acls)." error templates URI(s)\n";}
		if(count($acls)==0){$GLOBALS[__CLASS__][__FUNCTION__]=null;return;}
		@file_put_contents("/etc/squid3/acls/templatesacls.txt", @implode("\n", $acls)."\n");
		$GLOBALS["HTTP_ACCESS_ALLOW_TEMPLATES"]="http_access allow {$this->MyStandPort}AllowedUrisTemplates";
		$results="acl AllowedUrisTemplates dstdomain \"/etc/squid3/acls/templatesacls.txt\"\n";
		$GLOBALS[__CLASS__][__FUNCTION__]=$results;
		return $results;
		
	}
	
	private function ACL_TEMPLATES_URIS_VERIF($uri){
		$uri=str_replace("%FREEWEBS%", "", $uri);
		if(strpos("  $uri", "http")==0){$uri="http://$uri";}
		if(preg_match("#^([0-9]+):(.+)#", $uri,$re)){$uri=$re[2];}
		$URLAR=parse_url($uri);
		$scheme=$URLAR["scheme"];
		$sitename=$URLAR["host"];
		return "$scheme://$sitename";
		
	}
	
	private function ACL_TEMPLATES_URIS_TO_DSTDOMAIN($uri){
		$uri=str_replace("%FREEWEBS%", "", $uri);
		if(strpos("  $uri", "http")==0){$uri="http://$uri";}
		if(preg_match("#^([0-9]+):(.+)#", $uri,$re)){$uri=$re[2];}
		$URLAR=parse_url($uri);
		$scheme=$URLAR["scheme"];
		$sitename=$URLAR["host"];
		if($sitename==null){return;}
		if(preg_match("#^(.+?):[0-9]+$#", $sitename)){return $re[1];}
		return $sitename;
	
	}	
	
	private function GetUfdBguardRedirect(){
		if(!is_file("/etc/squid3/ufdbGuard.conf")){return array();}
		$array=array();
		$f=explode("\n",@file_get_contents("/etc/squid3/ufdbGuard.conf"));
		while (list ($num, $line) = each ($f)){
			if(preg_match("#redirect\s+(.+)#", $line,$re)){
				$uri=trim($re[1]);if(preg_match("#^[0-9]+:(.+)#", $uri,$re)){$uri=$re[1];}
				$array[$uri]=$uri;
				continue;
				}
				
			}
		
		return $array;
	}
	
	
	private function string_to_regex($pattern){
		if(trim($pattern)==null){return null;}
		$pattern=str_replace(".", "\.", $pattern);
		$pattern=str_replace("[", "\[", $pattern);
		$pattern=str_replace("]", "\]", $pattern);
		$pattern=str_replace("(", "\(", $pattern);
		$pattern=str_replace(")", "\)", $pattern);
		$pattern=str_replace("$", "\$", $pattern);
		$pattern=str_replace("?", "\?", $pattern);
		$pattern=str_replace("#", "\#", $pattern);
		$pattern=str_replace("{", "\{", $pattern);
		$pattern=str_replace("}", "\}", $pattern);
		$pattern=str_replace("!", "\!", $pattern);
	
		return $pattern;
		
	}		


		
		
		private function WHITELISTED_AUTH_BROWSERS(){
			if($this->SquidUrgency==1){return null;}
			if(isset($GLOBALS[__CLASS__][__FUNCTION__])){return @implode("\n",$GLOBALS[__CLASS__][__FUNCTION__]);}
			$arrayUserAgents=array();
			$sql="SELECT uri FROM squid_white WHERE task_type='AUTH_WL_USERAGENTS'";
			$q=new mysql();
			$results=$q->QUERY_SQL($sql,"artica_backup");
			while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
				if(trim($ligne["uri"])==null){continue;}
				$ligne["uri"]=$this->StringToRegex($ligne["uri"]);
				if(isset($arrayUserAgents[$ligne["uri"]])){continue;}
				$arrayUserAgents[$ligne["uri"]]=1;
			}
			
			
			$q=new mysql_squid_builder();
			$sql="SELECT pattern,PatternType FROM webfilters_blkwhlts WHERE blockType=5 and enabled=1";
			$results=$q->QUERY_SQL($sql);
			if($GLOBALS["VERBOSE"]){echo "DEBUG: WHITELISTED_AUTH webfilters_blkwhlts=".mysql_num_rows($results)." rows\n";}
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$www=trim($ligne["pattern"]);
				if($www==null){if($GLOBALS["VERBOSE"]){echo "DEBUG: WHITELISTED_AUTH webfilters_blkwhlts skip `{$ligne["pattern"]}` is null\n";}continue;}
				if(preg_match("#regex:(.+)#", $www,$re)){$www=$re[1];}else{$www=$this->StringToRegex($www);}
				if(isset($arrayUserAgents[$www])){continue;}
				$arrayUserAgents[$www]=1;
			}		
			
			$f=array();
			while (list ($UserAgent, $Notused) = each ($arrayUserAgents) ){$f[]="acl WhiteListedUserAgents browser $UserAgent";}					
			
			if(count($f)>0){
				$GLOBALS[__CLASS__][__FUNCTION__]=$f;
				$GLOBALS["HTTP_ACCESS"]["WHITELISTED_BROWSERS"]="http_access allow {$this->MyStandPort}WhiteListedUserAgents";
				return @implode("\n",$GLOBALS[__CLASS__][__FUNCTION__]);
			}
			$GLOBALS[__CLASS__][__FUNCTION__]=null;
			return null;
			
		}
		
		private function my_full_hostname(){
			if(isset($GLOBALS["my_full_hostname"])){return  $GLOBALS["my_full_hostname"];}
			if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
			$unix=new unix();
			$hostname_bin=$unix->find_program("hostname");
			exec("$hostname_bin -f 2>&1",$results);
			$GLOBALS["my_full_hostname"]=trim(@implode("", $results));
			if($GLOBALS["my_full_hostname"]==null){$GLOBALS["my_full_hostname"]="artica-proxy";}
			$GLOBALS["my_full_hostname"]=str_replace("..", ".", $GLOBALS["my_full_hostname"]);
			return $GLOBALS["my_full_hostname"]; 
		}
		
		
		private function krb_auth_conf(){
			if($this->SquidUrgency==1){return null;}
			if(!$this->ASROOT){return;}
			if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
			$sock=new sockets();
			$myFullHostname=$this->my_full_hostname();
			if($this->EnableKerberosAuthentication==0){return null;}
			if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
			$users=$GLOBALS["CLASS_USERS"];
			$ntlm_auth=$unix->find_program("ntlm_auth");
			$binary=$users->squid_kerb_auth_path;
			$SquidNTLMKeepAlive=$sock->GET_INFO("SquidNTLMKeepAlive");
			if(!is_numeric($SquidNTLMKeepAlive)){$SquidNTLMKeepAlive=1;}
			if(strlen($binary)<2){return "#No squid_kerb_auth in compilation !\n";}
			
			echo "Starting......: ".date("H:i:s")." Kerberos Authentification enabled\n";
			if(is_file("/etc/krb5.keytab")){
				$chown=$unix->find_program("chown");
				$chmod=$unix->find_program("chmod");
				shell_exec("$chown squid:squid /etc/krb5.keytab");
				shell_exec("$chmod 666 /etc/krb5.keytab");
			}
			
			if(is_file("/usr/local/bin/negotiate_wrapper")){
				if(is_file("$ntlm_auth")){$ntlm=" --ntlm /usr/bin/ntlm_auth";}
				$t[]="auth_param negotiate program /usr/local/bin/negotiate_wrapper -d $ntlm --diagnostics --helper-protocol=squid-2.5-ntlmssp --domain=EXAMPLE --kerberos /usr/lib/squid3/squid_kerb_auth -d -s GSS_C_NO_NAME";
				
			}
			
			$keep_alive="off";
			if($SquidNTLMKeepAlive==1){$keep_alive="on";}
			$children=$this->SquidClientParams["auth_param_ntlm_children"];
	
			$conf[]= "\n\n#--------- kerberos AUTH settings myhostname $myFullHostname\n";
			//$conf[]= "auth_param negotiate program $users->squid_kerb_auth_path -d -s HTTP/$myFullHostname";
			$conf[]= "auth_param negotiate program $users->squid_kerb_auth_path -d";
			$conf[]= "auth_param negotiate children {$children}";
			$conf[]= "auth_param negotiate keep_alive $keep_alive";
			$conf[]= "auth_param basic realm Squid proxy-caching web server";
			$conf[]= "auth_param basic credentialsttl 2 hour";
			$conf[]= "authenticate_ip_ttl 60 seconds";			
			$conf[]= "authenticate_cache_garbage_interval 10 seconds";
			$conf[]= "authenticate_ttl 0 hour";
			$GLOBALS["ACL_AUTHENTICATED"]="acl AUTHENTICATED proxy_auth REQUIRED";
			$conf[]="";	
			$conf[]="";
			return @implode("\n", $conf);		
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
		
		private function ntlm_params_defaults(){
			
			if(!isset($this->SquidClientParams["authenticate_ttl"])){$this->SquidClientParams["authenticate_ttl"]=14400;}
			if(!isset($this->SquidClientParams["authenticate_ip_ttl"])){$this->SquidClientParams["authenticate_ip_ttl"]=14400;}
			if(!isset($this->SquidClientParams["credentialsttl"])){$this->SquidClientParams["credentialsttl"]=14400;}
			if(!isset($this->SquidClientParams["authenticate_cache_garbage_interval"])){$this->SquidClientParams["authenticate_cache_garbage_interval"]=18000;}
			
			if(!is_numeric($this->SquidClientParams["auth_param_ntlm_children"])){$this->SquidClientParams["auth_param_ntlm_children"]=20;}
			if(!is_numeric($this->SquidClientParams["auth_param_ntlm_startup"])){$this->SquidClientParams["auth_param_ntlm_startup"]=0;}
			if(!is_numeric($this->SquidClientParams["auth_param_ntlm_idle"])){$this->SquidClientParams["auth_param_ntlm_idle"]=1;}
			
			if(!is_numeric($this->SquidClientParams["auth_param_basic_children"])){$this->SquidClientParams["auth_param_basic_children"]=3;}
			if(!is_numeric($this->SquidClientParams["auth_param_basic_startup"])){$this->SquidClientParams["auth_param_basic_startup"]=2;}
			if(!is_numeric($this->SquidClientParams["auth_param_basic_idle"])){$this->SquidClientParams["auth_param_basic_idle"]=1;}
			
			if(intval($this->SquidClientParams["authenticate_cache_garbage_interval"])==0){$this->SquidClientParams["authenticate_cache_garbage_interval"]=18000;}
			if(intval($this->SquidClientParams["authenticate_ttl"])==0){$this->SquidClientParams["authenticate_ttl"]=14400;}
			if(intval($this->SquidClientParams["authenticate_ip_ttl"])==0){$this->SquidClientParams["authenticate_ip_ttl"]=$this->SquidClientParams["authenticate_ttl"];}
			
			
			
			if($this->SquidClientParams["authenticate_ttl"]>$this->SquidClientParams["authenticate_cache_garbage_interval"]){
				$this->SquidClientParams["authenticate_cache_garbage_interval"]=$this->SquidClientParams["authenticate_ttl"];
			}
			if(intval($this->SquidClientParams["credentialsttl"])==0){
				$this->SquidClientParams["credentialsttl"]=$this->SquidClientParams["authenticate_ttl"];
			}
		}
		
		
		
		private function negotiate_kerberos_auth(){
			$unix=new unix();
			$sock=new sockets();
			$WINDOWS_DNS_SUFFIX=strtoupper($this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]);
			$ActiveDirectorySquidHTTPHostname=$sock->GET_INFO("ActiveDirectorySquidHTTPHostname");
			$myFullHostname=$unix->hostname_g();
			
			if(is_file("/etc/squid3/PROXY.keytab")){
				@chown("/etc/squid3/PROXY.keytab","squid");
				@chgrp("/etc/squid3/PROXY.keytab","squid");
			}
			@chmod("/etc/squid3/PROXY.keytab",0755);
			
			if(!is_file("/lib/squid3/negotiate_kerberos_auth")){
				if($GLOBALS["VERBOSE"]){echo "!!!! FATAL /lib/squid3/negotiate_kerberos_auth No such file !!!\n";}
				return null;
				
			}
			
			if($ActiveDirectorySquidHTTPHostname<>null){
				$myFullHostname=$ActiveDirectorySquidHTTPHostname;
			}
			
			
			$f[]="/lib/squid3/negotiate_kerberos_auth";
			$f[]="-r -s HTTP/$myFullHostname@$WINDOWS_DNS_SUFFIX";
			if($this->IS_35){
				$f[]="-k /etc/squid3/PROXY.keytab";
			}
			return @implode(" ",$f);
		}
		
		private function negotiate_ntlm_auth(){
			
			if(is_file("/usr/bin/ntlm_auth")){
				$SQUID_NTLM_AUTH="/usr/bin/ntlm_auth";
			}else{
				$users=new usersMenus();
				$SQUID_NTLM_AUTH=$users->SQUID_NTLM_AUTH;
			}
			
			$WINDOWS_DNS_SUFFIX=strtoupper($this->KerbAuthInfos["WINDOWS_DNS_SUFFIX"]);
			$LDAP_NONTLM_DOMAIN=strtoupper(trim($this->KerbAuthInfos["LDAP_NONTLM_DOMAIN"]));
			
			if($WINDOWS_DNS_SUFFIX<>null){
				$basicDomain=" --domain=$WINDOWS_DNS_SUFFIX";
				$basicNTLMDomain=" --domain=$WINDOWS_DNS_SUFFIX";
			}
			if($LDAP_NONTLM_DOMAIN=="NONE"){$basicDomain=null;$LDAP_NONTLM_DOMAIN=null;}
			if($LDAP_NONTLM_DOMAIN<>null){$basicDomain=" --domain=$LDAP_NONTLM_DOMAIN";}
				
			return "{$SQUID_NTLM_AUTH} $basicNTLMDomain --helper-protocol=squid-2.5-ntlmssp";
			
		}
		
		
		private function ntlm_kerberos_wrapper(){
			$sock=new sockets();
			$unix=new unix();
			$negotiate_wrapper="/lib/squid3/negotiate_wrapper_auth";
			if(!is_file("$negotiate_wrapper")){
				$f[]="# ntlm_kerberos_wrapper(): $negotiate_wrapper no such binary";
				return @implode("\n", $f);
			}
			
			
			$negotiate_ntlm_auth=$this->negotiate_ntlm_auth();
			
			$wrap[]=$negotiate_wrapper;
			//$wrap[]="-d";
			if($negotiate_ntlm_auth<>null){
				$wrap[]="--ntlm $negotiate_ntlm_auth";
			}
			$negotiate_kerberos_auth=$this->negotiate_kerberos_auth();
			if($negotiate_kerberos_auth<>null){
				$wrap[]="--kerberos $negotiate_kerberos_auth";
			}
			
			
			$f[]="### negotiate kerberos and ntlm authentication [".__LINE__."]";
			$f[]="auth_param negotiate program ".@implode(" ", $wrap);
			$f[]="auth_param negotiate children ".$this->ntlm_auth_childs();
			$f[]="auth_param negotiate keep_alive ".$this->ntlm_auth_keepalive();
			return @implode("\n", $f);
			
			
		}
		
		
		
		private function ntlm_kerberos_conf(){
			if($this->SquidUrgency==1){
				return "# ntlm_kerberos_conf(); SquidUrgency is enabled, Aborting...[".__LINE__."]\n";
			}
			$sock=new sockets();
			$WindowsActiveDirectoryKerberos=intval($sock->GET_INFO("WindowsActiveDirectoryKerberos"));
			if($WindowsActiveDirectoryKerberos==0){return null;}
			
			
			$negotiate_kerberos_auth=$this->negotiate_kerberos_auth();
			if($negotiate_kerberos_auth==null){return  null;}
			
			$f[]="### negotiate kerberos [".__LINE__."]";
			$f[]="auth_param negotiate program $negotiate_kerberos_auth";
			$f[]="auth_param negotiate children ".$this->ntlm_auth_childs();
			$f[]="auth_param negotiate keep_alive ".$this->ntlm_auth_keepalive();
			return @implode("\n", $f);
		}
		
		
		private function ntlm_pure_conf(){
			$negotiate_ntlm_auth=$this->negotiate_ntlm_auth();
			$conf[]= "auth_param ntlm program $negotiate_ntlm_auth";
			$conf[]= "auth_param ntlm children ".$this->ntlm_auth_childs();
			$conf[]= "auth_param ntlm keep_alive ".$this->ntlm_auth_keepalive();
			$conf[]= "";
			return @implode("\n", $conf);
			
			
		}
		
		
		private function ntlm_auth_childs(){
			if(isset($GLOBALS["ntlm_auth_childs"])){return $GLOBALS["ntlm_auth_childs"];}
			$auth_param_ntlm_children=$this->auth_param_ntlm_children();
			$auth_param_ntlm_startup=$this->auth_param_ntlm_startup();
			$auth_param_ntlm_idle=intval($this->SquidClientParams["auth_param_ntlm_idle"]);
			if($auth_param_ntlm_idle>=$auth_param_ntlm_startup){$auth_param_ntlm_idle=round($auth_param_ntlm_startup/2);}
			if($auth_param_ntlm_idle==0){$auth_param_ntlm_idle=1;}
			$auth_param_child=" startup=$auth_param_ntlm_startup idle=$auth_param_ntlm_idle";
			$GLOBALS["ntlm_auth_childs"]= "$auth_param_ntlm_children$auth_param_child";
			return $GLOBALS["ntlm_auth_childs"];
		}
		
		private function ntlm_auth_keepalive(){
			$sock=new sockets();
			$SquidNTLMKeepAlive=intval($sock->GET_INFO("SquidNTLMKeepAlive"));
			$keep_alive="off";
			if($SquidNTLMKeepAlive==1){$keep_alive="on";}
			return $keep_alive;
			
		}
		
		public function ntlm_auth_conf(){			
			if($this->SquidUrgency==1){
				return "# ntlm_auth_conf(); SquidUrgency is enabled, Aborting...[".__LINE__."]\n";
			}
			
			if($this->ActiveDirectoryEmergency==1){
				return "# ntlm_auth_conf(); Active Directory Emergency is enabled, Aborting...[".__LINE__."]\n";
			}
			
			if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
			$users=$GLOBALS["CLASS_USERS"];
			$sock=new sockets();
			$UseDynamicGroupsAcls=1;
			$DynamicGroupsAclsTTL=intval($sock->GET_INFO("DynamicGroupsAclsTTL"));
			if($DynamicGroupsAclsTTL==0){$DynamicGroupsAclsTTL=3600;}
			if($DynamicGroupsAclsTTL<5){$DynamicGroupsAclsTTL=5;}
			$KerbAuthMethod=intval($sock->GET_INFO("KerbAuthMethod"));
			$WindowsActiveDirectoryKerberos=intval($sock->GET_INFO("WindowsActiveDirectoryKerberos"));
			/*
			 * $arrayAuth[0]="{all_methods}";
	$arrayAuth[1]="{only_ntlm}";
	$arrayAuth[2]="{only_basic_authentication}";
	$arrayAuth[3]="{only_Kerberos}";
			 */
			if($this->DisableWinbindd==1){
				$conf[]= "# DisableWinbindd is turned to 1 All will be disabled ! [".__LINE__."]";
				$this->EnableKerbAuth=0;$this->NTLM_AUTH=0;
			}
			$this->ntlm_params_defaults();
			if($this->EnableKerbAuth==1){$UseDynamicGroupsAcls=1;}
			
	
			
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [AUTH] ntlm_auth_conf(): DisableWinbindd=$this->DisableWinbindd\n";}
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [AUTH] ntlm_auth_conf(): UseDynamicGroupsAcls=$UseDynamicGroupsAcls\n";}
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [AUTH] ntlm_auth_conf(): DynamicGroupsAclsTTL=$DynamicGroupsAclsTTL\n";}
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [AUTH] ntlm_auth_conf(): KerbAuthMethod=$KerbAuthMethod\n";}	
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [AUTH] ntlm_auth_conf(): EnableKerbAuth=$this->EnableKerbAuth\n";}
			
			
			// voir permissions on /var/lib/samba/winbindd_privileged
			// http://www.cyberciti.biz/faq/squid-ntlm-authentication-configuration-howto/
			$conf[]= "#--------- EnableKerbAuth = $this->EnableKerbAuth Method:$KerbAuthMethod [".__LINE__."]";
			$conf[]= "#--------- Use kerberos: $WindowsActiveDirectoryKerberos [".__LINE__."]";
			
			
			if($this->EnableKerbAuth==1){
				if(!$users->WINBINDD_INSTALLED){
					if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [AUTH] ntlm_auth_conf(): unable to stat winbind\n";}
					$conf[]= "#--------- NTLM AUTH settings unable to stat winbind";
					return @implode("\n", $conf);
				}
				$this->NTLM_AUTH=1;
			}
			
			
			if($this->NTLM_AUTH==0){
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [AUTH] ntlm_auth_conf(): NTLM_AUTH=0, no ntlm is set\n";}
				$conf[]= "#--------- NTLM NTLM_AUTH=0 !/ NTLM_AUTH=0, no ntlm is set DisableWinbindd=$this->DisableWinbindd [".__LINE__."]";
				return @implode("\n", $conf);
			}
			
			if($this->ASROOT){
				if(is_file("/usr/bin/ntlm_auth")){
					$users->SQUID_NTLM_ENABLED=true;
					$users->SQUID_NTLM_AUTH="/usr/bin/ntlm_auth";
				}
				if(!is_file("/etc/squid3/net_ads_group.pl")){
					if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
					$php=$unix->LOCATE_PHP5_BIN();
					shell_exec("$php /usr/share/artica-postfix/exec.squid.netads.php >/dev/null 2>&1");
				}
			}
			
			if(!$users->SQUID_NTLM_ENABLED){
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [AUTH] ntlm_auth_conf(): No NTLM in compilation\n";}
				$this->NTLM_AUTH=0;return "#No NTLM in compilation !\n";
			}
			if(trim($users->SQUID_NTLM_AUTH)==null){
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [AUTH] ntlm_auth_conf(): No NTLM auth_param basic program found\n";}
				$this->NTLM_AUTH=0;return "#No NTLM auth_param basic program found !\n";
			}
			
			if($this->IS_32){$external_acl_childrens=$this->external_acl_childrens(); }
			
			$conf[]= "# --------- NTLM AUTH settings (line:".__LINE__.") ---------";
			$conf[]= "# Authentification mode, building using squid compiled for artica";
			$conf[]= "# Kerberos/NTLM authentication: (KerbAuthMethod = $KerbAuthMethod)";
			
			$NOT_NTLM=false;
			
			if($KerbAuthMethod==0){
				if($WindowsActiveDirectoryKerberos==1){
					$conf[]=$this->ntlm_kerberos_wrapper();
					$NOT_NTLM=true;
				}
				
			}
			
			if($KerbAuthMethod==3){
				if($WindowsActiveDirectoryKerberos==0){
					$KerbAuthMethod=0;
				}else{
					$conf[]=$this->ntlm_kerberos_conf();
					$NOT_NTLM=true;
				}
				
			}
			

			if(($KerbAuthMethod==0) OR ($KerbAuthMethod==1)){
				if(!$NOT_NTLM){
					$conf[]=$this->ntlm_pure_conf();
					$conf[]= "";
				}
			}
			
			if($KerbAuthMethod==2){
				$conf[]="# Only basic authentication [".__LINE__."]";
				$conf[]=$this->auth_param_basic();
				
			}
			
			
			
			$conf[]= "";
			$conf[]= "#Dynamic ACLs groups";
			$auth_param_ntlm_children=$this->auth_param_ntlm_children();
			$auth_param_ntlm_startup=$this->auth_param_ntlm_startup();
			$auth_param_ntlm_idle=intval($this->SquidClientParams["auth_param_ntlm_idle"]);
			if($auth_param_ntlm_idle>=$auth_param_ntlm_startup){$auth_param_ntlm_idle=round($auth_param_ntlm_startup/2);}
			if($auth_param_ntlm_idle==0){$auth_param_ntlm_idle=1;}
			if(!is_numeric($this->SquidClientParams["auth_param_ntlmgroup_children"])){$this->SquidClientParams["auth_param_ntlmgroup_children"]=15;}
			if(!is_numeric($this->SquidClientParams["auth_param_ntlmgroup_startup"])){$this->SquidClientParams["auth_param_ntlmgroup_startup"]=1;}
			if(!is_numeric($this->SquidClientParams["auth_param_ntlmgroup_idle"])){$this->SquidClientParams["auth_param_ntlmgroup_idle"]=1;}
			
			if($this->SquidClientParams["auth_param_ntlmgroup_startup"]>$this->SquidClientParams["auth_param_ntlmgroup_children"]){
				$this->SquidClientParams["auth_param_ntlmgroup_startup"]=$this->SquidClientParams["auth_param_ntlmgroup_children"];
			}
			if($this->SquidClientParams["auth_param_ntlmgroup_idle"]>$this->SquidClientParams["auth_param_ntlmgroup_children"]){
				$this->SquidClientParams["auth_param_ntlmgroup_idle"]=1;
			}
			$auth_param_ntlmgroup_idle=$this->SquidClientParams["auth_param_ntlmgroup_idle"];
			$auth_param_ntlmgroup_children=$this->SquidClientParams["auth_param_ntlmgroup_children"];
			$auth_param_ntlmgroup_startup=$this->SquidClientParams["auth_param_ntlmgroup_startup"];
			
			$conf[]= "external_acl_type ads_group ttl=$DynamicGroupsAclsTTL negative_ttl=$DynamicGroupsAclsTTL children-startup=$auth_param_ntlmgroup_startup children-idle=$auth_param_ntlmgroup_idle children-max=$auth_param_ntlmgroup_children ipv4 %LOGIN /usr/share/artica-postfix/external_acl_squid_ldap.php";
			
			if(!is_file("/etc/squid3/non_ntlm.acl")){@touch("/etc/squid3/non_ntlm.acl");}
			if(!is_file("/etc/squid3/non_ntlm.access")){@touch("/etc/squid3/non_ntlm.access");}
			@chown("/etc/squid3/non_ntlm.access", "squid");
			@chgrp("/etc/squid3/non_ntlm.access", "squid");
			@chown("/etc/squid3/non_ntlm.acl", "squid");
			@chgrp("/etc/squid3/non_ntlm.acl", "squid");
			
			
			$conf[]= "#Other settings";
			$conf[]= "authenticate_ttl {$this->SquidClientParams["authenticate_ttl"]} seconds\n";
			$conf[]= "authenticate_cache_garbage_interval {$this->SquidClientParams["authenticate_cache_garbage_interval"]} seconds";
			$conf[]= "authenticate_ip_ttl {$this->SquidClientParams["authenticate_ip_ttl"]} seconds\n";	
			$conf[]="# END NTLM Parameters --------------------------------\n";
			
			$GLOBALS["PROXY_NTLM_SWITCH"]=true;
			$GLOBALS["ACL_AUTHENTICATED"]="#--------- ACL AUTHENTICATED for authentication method\nacl AUTHENTICATED proxy_auth REQUIRED\ninclude /etc/squid3/non_ntlm.acl\n";
			return implode("\n",$conf);

		}
		
		
	private function auth_param_basic_children_byadmin(){
		$auth_param_basic_children=intval($this->SquidClientParams["auth_param_basic_children"]);
		if($auth_param_basic_children==0){$auth_param_basic_children=15;}
		return $auth_param_basic_children;
	}
		
	private function auth_param_basic_children(){
		if($this->AdminAsSeenNTLMPerfs==1){return $this->auth_param_basic_children_byadmin();}
		if(isset($GLOBALS["auth_param_basic_children"])){return $GLOBALS["auth_param_basic_children"];}
		$cacheCenter=new SquidCacheCenter();
		$CPU_NUMBER=intval($cacheCenter->WORKERS_NUMBER());
		$auth_param_basic_children=$this->SquidClientParams["auth_param_basic_children"];
		if($this->SquidUsersNumber==0){
				$GLOBALS["auth_param_basic_children"]=$auth_param_basic_children;
				return $GLOBALS["auth_param_basic_children"];
		}
		$multiplicator=5;
		if($this->SquidUsersNumber>49){$multiplicator=8;}
		if($this->SquidUsersNumber>499){$multiplicator=10;}
		if($this->SquidUsersNumber>1000){$multiplicator=15;}
		if($this->SquidUsersNumber>1500){$multiplicator=20;}
		
		$FINAL= $CPU_NUMBER*$multiplicator;
		if($auth_param_basic_children>$FINAL){
			$GLOBALS["auth_param_basic_children"]=$auth_param_basic_children;
			return $auth_param_basic_children;
		}
		
		$GLOBALS["auth_param_basic_children"]=$FINAL;
		return $FINAL;
		
	}
	
	
	private function auth_param_basic_startup_byadmin(){
		$auth_param_basic_startup=intval($this->SquidClientParams["auth_param_basic_startup"]);
		if($auth_param_basic_startup==0){$auth_param_basic_startup=5;}
		return $auth_param_basic_startup;
	}
	
	private function auth_param_basic_startup(){
		if($this->AdminAsSeenNTLMPerfs==1){return $this->auth_param_basic_startup_byadmin();}
		$cacheCenter=new SquidCacheCenter();
		$CPU_NUMBER=intval($cacheCenter->WORKERS_NUMBER());
		$auth_param_basic_children=$this->auth_param_basic_children()/$CPU_NUMBER;
		$FINAL=round($auth_param_basic_children*0.3);
		$auth_param_basic_startup=intval($this->SquidClientParams["auth_param_basic_startup"]);
		if($auth_param_basic_startup>$FINAL){return $auth_param_basic_startup;}
		return round($FINAL*$CPU_NUMBER);
	}
	
	private function auth_param_ntlm_children_byadmin(){
		$auth_param_ntlm_children=intval($this->SquidClientParams["auth_param_ntlm_children"]);
		if($auth_param_ntlm_children==0){$auth_param_ntlm_children=20;}
		return $auth_param_ntlm_children;
		
	}
	
	public function auth_param_ntlm_children(){
		if($this->AdminAsSeenNTLMPerfs==1){return $this->auth_param_ntlm_children_byadmin();}
		if(isset($GLOBALS["auth_param_ntlm_children"])){
			if(intval($GLOBALS["auth_param_ntlm_children"])>0){return $GLOBALS["auth_param_ntlm_children"];}
		}
		
		$auth_param_ntlm_children=$this->SquidClientParams["auth_param_ntlm_children"];
		if($this->SquidUsersNumber==0){
			$GLOBALS["auth_param_ntlm_children"]=$auth_param_ntlm_children;
			return $GLOBALS["auth_param_ntlm_children"];
		}
		
		$cacheCenter=new SquidCacheCenter();
		$CPU_NUMBER=intval($cacheCenter->WORKERS_NUMBER());
		
		$multiplicator=20;
		if($this->SquidUsersNumber>49){$multiplicator=25;}
		if($this->SquidUsersNumber>499){$multiplicator=40;}
		if($this->SquidUsersNumber>1000){$multiplicator=60;}
		if($this->SquidUsersNumber>1500){$multiplicator=80;}
		
		
		$FINAL= $CPU_NUMBER*$multiplicator;
		if($auth_param_ntlm_children>$FINAL){
			$GLOBALS["auth_param_ntlm_children"]=$auth_param_ntlm_children;
			return $auth_param_ntlm_children;
		}
		
		$GLOBALS["auth_param_ntlm_children"]=$FINAL;
		return $FINAL;		
		
		
	}
	
	private function auth_param_ntlm_startup_byadmin(){
		$auth_param_ntlm_children=$this->auth_param_ntlm_children();
		$auth_param=intval($this->SquidClientParams["auth_param_ntlm_startup"]);
		if($auth_param==0){$auth_param=1;}
		if($auth_param>1){
			if($auth_param>$auth_param_ntlm_children){
				$auth_param=round($auth_param_ntlm_children/3);
				if($auth_param==0){$auth_param=1;}
			}
		}
		return $auth_param;
	}
	
	private function auth_param_ntlm_startup(){
		if($this->AdminAsSeenNTLMPerfs==1){return $this->auth_param_ntlm_startup_byadmin();}
		$cacheCenter=new SquidCacheCenter();
		$CPU_NUMBER=intval($cacheCenter->WORKERS_NUMBER());
		$auth_param_ntlm_children=$this->auth_param_ntlm_children()/$CPU_NUMBER;
		$FINAL=round($auth_param_ntlm_children*0.3);
		$auth_param_basic_startup=$this->SquidClientParams["auth_param_ntlm_startup"];
		if($auth_param_basic_startup>$FINAL){return $auth_param_basic_startup;}
		return round($FINAL*$CPU_NUMBER);
		
	}
	
	private function external_acl_children(){
		if(isset($GLOBALS["external_acl_children"])){return $GLOBALS["external_acl_children"];}
		$external_acl_children=$this->SquidClientParams["external_acl_children"];
		if($this->SquidUsersNumber==0){
			$GLOBALS["external_acl_children"]=$external_acl_children;
			return $GLOBALS["external_acl_children"];
		}
		
		$cacheCenter=new SquidCacheCenter();
		$CPU_NUMBER=intval($cacheCenter->WORKERS_NUMBER());
		
		$multiplicator=5;
		if($this->SquidUsersNumber>49){$multiplicator=8;}
		if($this->SquidUsersNumber>499){$multiplicator=10;}
		if($this->SquidUsersNumber>1000){$multiplicator=15;}
		if($this->SquidUsersNumber>1500){$multiplicator=20;}
		
		
		$FINAL= $CPU_NUMBER*$multiplicator;
		if($external_acl_children>$FINAL){
			$GLOBALS["external_acl_children"]=$external_acl_children;
			return $external_acl_children;
		}
		
		$GLOBALS["external_acl_children"]=$FINAL;
		return $FINAL;
		
		
		$external_acl_startup=$this->SquidClientParams["external_acl_startup"];
		$external_acl_idle=$this->SquidClientParams["external_acl_idle"];
		
		
		
	}
	
		
	private function auth_param_basic($direct=false){
		if($this->BasicAuthenticatorEmergency==1){
			return "# auth_param_basic(); BasicAuthenticatorEmergency = 1; Emergency is enabled, Aborting...[".__LINE__."]\n";
		}
		if($this->SquidUrgency==1){return null;}
		
		if($this->ActiveDirectoryEmergency==1){
			return "# auth_param_basic(); Active Directory Emergency is enabled, Aborting...[".__LINE__."]\n";
		}
		
		
		
		$conf=array();
		$sock=new sockets();
		if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}

		$users=$GLOBALS["CLASS_USERS"];
		if(!is_file($users->SQUID_NTLM_AUTH)){
			if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
			$users->SQUID_NTLM_AUTH=$unix->find_program("ntlm_auth");
		}
		echo "Starting......: ".date("H:i:s")." [AUTH]: Basic: get definitions\n";
		
		
		$KerbAuthMethod=$sock->GET_INFO("KerbAuthMethod");
		if(!is_numeric($KerbAuthMethod)){$KerbAuthMethod=0;}
		

		$auth_param_basic_children=$this->auth_param_basic_children();
		$auth_param_basic_startup=$this->auth_param_basic_startup();
		$auth_param_basic_idle=intval($this->SquidClientParams["auth_param_basic_idle"]);
		if($auth_param_basic_idle==0){$auth_param_basic_idle=1;}
		
		$external_acl_children=$this->SquidClientParams["external_acl_children"];
		$external_acl_startup=$this->SquidClientParams["external_acl_startup"];
		$external_acl_idle=$this->SquidClientParams["external_acl_idle"];	

		
		$cacheCenter=new SquidCacheCenter();
		
		
		
		if($this->IS_32){$auth_param_child=" startup=$auth_param_basic_startup idle=$auth_param_basic_idle";}
		if($this->IS_33){$auth_param_child=" startup=$auth_param_basic_startup idle=$auth_param_basic_idle";}
		$Multiple=false;
		
		$conf[]="# NTLM_AUTH = $this->NTLM_AUTH / KerbAuthMethod = $KerbAuthMethod [".__LINE__."]";
		if($this->NTLM_AUTH==1){
			$conf[]="# NTLM_AUTH = Check Direct: $direct [".__LINE__."]";
			if(!$direct){
				if( ($KerbAuthMethod==0) OR ($KerbAuthMethod=2) ){
					if(!isset($GLOBALS["auth_param_basic_sended"])){
						$conf[]=$this->ldap_auth_conf(false);
						$GLOBALS["auth_param_basic_sended"]=true;
					}
				}else{
					$conf[]= "#Basic authentication for other browser is disabled KerbAuthMethod=$KerbAuthMethod";
				}
			}
		}
		
		$acl=new squid_acls();
		if($acl->IsObjectAuth()){
			echo "Starting......: ".date("H:i:s")." [AUTH]: Basic: Objects authentication enabled\n";
			$Multiple=true;
		}else{
			echo "Starting......: ".date("H:i:s")." [AUTH]: Basic: Objects authentication is disabled\n";
		}
		if($this->LDAP_AUTH==1){return ;}
		if($this->LDAP_EXTERNAL_AUTH==1){return ;}
		
		if($Multiple){
			$conf=array();
			
			echo "Starting......: ".date("H:i:s")." [AUTH]: Basic: multiple authentication methods enabled\n";
			$basic_fake_auth=$this->locate_generic_bin("basic_fake_auth");
			if($this->NTLM_AUTH==1){$conf[]= "#Basic authentication for other browsers that did not supports NTLM:";}
			if($this->NTLM_AUTH==0){$conf[]= "#Basic authentication for other browsers";}
			$conf[]= "auth_param basic program $basic_fake_auth";
			$conf[]= "auth_param basic children {$auth_param_basic_children}$auth_param_child";
			$conf[]= "auth_param basic realm Basic Identification";
			$conf[]= "auth_param basic credentialsttl 2 hours";	
			
			$external_acl_childrens=$this->external_acl_childrens(0,true);	
			$conf[]= "external_acl_type BasicFakeAuth $external_acl_childrens %SRC %LOGIN %{Proxy-Authorization} /usr/share/artica-postfix/external_acl_basic_auth.php\n";
		}
		
		echo "Starting......: ".date("H:i:s")." [AUTH]: Basic: ".count($conf)." rows\n";
		
		return @implode("\n", $conf)."\n";
		
	}
	
	private function locate_generic_bin($program){
		if(!$this->ASROOT){return;}
		$possibleDirs[]="/lib/squid3";
		$possibleDirs[]="/lib64/squid3";
		$possibleDirs[]="/lib/squid";
		$possibleDirs[]="/lib64/squid";
		$possibleDirs[]="/usr/lib/squid";
		$possibleDirs[]="/usr/lib64/squid";
		$possibleDirs[]="/usr/local/squid/libexec";
		while (list ($num, $directory) = each ($possibleDirs) ){
			if(is_file("$directory/$program")){return "$directory/$program";}
		}
		
		echo "\n!!!!! ******************* !!!!!\nStarting......: ".date("H:i:s")." [ERR]: locate_generic_bin() Unable to find $program\n!!!!! ******************* !!!!!\n";
	
	}	
			
			
	private function ISFiltersEnabled(){
		if($this->SquidUrgency==1){return false;}
		if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
		$users=$GLOBALS["CLASS_USERS"];
		if($users->DANSGUARDIAN_INSTALLED){if($this->enable_dansguardian==1){return true;}}
		if($users->SQUIDGUARD_INSTALLED){if($this->enable_squidguard==1){return true;}}
	}
	

	

	
	private function zapchain_streamcache(){
		// Disabled...
		return;
		if($this->enable_streamcache==0){return;}
		if(!$this->ASROOT){return;}
		shell_exec("/bin/cp /usr/share/artica-postfix/streamcache.php /usr/sbin/streamcache.php");
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
		$chmod=$unix->find_program("chmod");
		$chmod=$unix->find_program("chmod");
		shell_exec("$chmod 775 /usr/sbin/streamcache.php");
		return "/usr/sbin/streamcache.php";
		
		
	}
	
	public function ufdbguard_value($key){
		if(!is_file("/etc/squid3/ufdbGuard.conf")){return null;}
		if(isset($GLOBALS[__FUNCTION__][$key])){return $GLOBALS[__FUNCTION__][$key];}
		if(!isset($GLOBALS["UFDGUARDDATAFILE"])){$GLOBALS["UFDGUARDDATAFILE"]=file("/etc/squid3/ufdbGuard.conf");}
		if(!is_array($GLOBALS["UFDGUARDDATAFILE"])){$GLOBALS["UFDGUARDDATAFILE"]=file("/etc/squid3/ufdbGuard.conf");}
		while (list ($num, $ligne) = each ($GLOBALS["UFDGUARDDATAFILE"]) ){
			if(preg_match("#^$key\s+(.*)#", $ligne,$re)){
				$GLOBALS[__FUNCTION__][$key]=$re[1];
				return $re[1];}
		}
		
	}
	
	public function zapchain_ufdbguard(){
		$sock=new sockets();
		if($this->SquidUrgency==1){return null;}
		$SquidUFDBUrgency=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidUFDBUrgency"));
		if($SquidUFDBUrgency==1){return null;}
		
		if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
		if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
		$users=$GLOBALS["CLASS_USERS"];
		if(!$users->APP_UFDBGUARD_INSTALLED){return null;}
		
		
		if($this->enable_UfdbGuard==null){$this->enable_UfdbGuard=0;}
		if($this->enable_UfdbGuard==0){return null;}
		if(strlen($users->ufdbgclient_path)==0){return null;}

		
		$moinsC=null;
		
		$sock=new sockets();
		$datas=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
		$UfdbUseArticaClient=$sock->GET_INFO("UfdbUseArticaClient");
		if(!is_numeric($UfdbUseArticaClient)){$UfdbUseArticaClient=1;}
		$squidversion=$this->SQUID_VERSION;
		if($UfdbUseArticaClient==1){
			@chmod("/usr/share/artica-postfix/ufdbgclient.php", 0755);
			@chown("/usr/share/artica-postfix/ufdbgclient.php", "squid");
			
			return "/usr/share/artica-postfix/ufdbgclient.php";
		}
		$ufdbClass=new compile_ufdbguard();
		$datas=$ufdbClass->SetDefaultsConfig($datas);
		

		if(!isset($datas["url_rewrite_children_concurrency"])){$datas["url_rewrite_children_concurrency"]=2;}
		if(!isset($datas["url_rewrite_children_startup"])){$datas["url_rewrite_children_startup"]=5;}
		if(!isset($datas["url_rewrite_children_idle"])){$datas["url_rewrite_children_idle"]=5;}		
		
		if(!is_numeric($datas["url_rewrite_children_concurrency"])){$datas["url_rewrite_children_concurrency"]=2;}
		if($datas["url_rewrite_children_concurrency"]>1){$moinsC="-C ";}
		
		if($this->EnableRemoteStatisticsAppliance==1){
			$RemoteStatisticsApplianceSettings=unserialize(base64_decode($sock->GET_INFO("RemoteStatisticsApplianceSettings")));
			if($this->ASROOT){
				echo "Starting......: ".date("H:i:s")." [UFDB]: Using remote appliance {$RemoteStatisticsApplianceSettings["SERVER"]}:{$datas["listen_port"]} as Web filtering engine\n";
			}
			
			$datas["remote_server"]=$RemoteStatisticsApplianceSettings["SERVER"];
			$datas["UseRemoteUfdbguardService"]=1;
			$datas["remote_port"]=$datas["listen_port"];
		}		
		
		$binary=$users->ufdbgclient_path;
		
		//$binary="/usr/share/artica-postfix/ufdbgclient.php";
		$binary=$users->ufdbgclient_path;
		$log="-l /var/log/squid";
 		
 		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
		$major=$unix->UFDBGUARDD_MAJOR();
		$minor=$unix->UFDBGUARDD_MINOR();
		
		

		
		
		if(!is_numeric($datas["listen_port"])){$datas["listen_port"]="3977";}
		if(!is_numeric($datas["tcpsockets"])){$datas["tcpsockets"]=1;}			
		if(!is_numeric($datas["remote_port"])){$datas["remote_port"]=3977;}
		
		if($datas["remote_port"]==null){$datas["UseRemoteUfdbguardService"]=0;}
		if($datas["listen_addr"]==null){$datas["listen_addr"]="127.0.0.1";}
		if($datas["listen_addr"]=="all"){$datas["listen_addr"]="127.0.0.1";}
		
		
		
		
		
		echo "Starting......: ".date("H:i:s")." ufdbguardd: Major:$major, Minor:$minor use TCP socket:{$datas["tcpsockets"]}\n";
		if($datas["UseRemoteUfdbguardService"]==1){
			if(trim($datas["remote_server"]==null)){$datas["remote_server"]="127.0.0.1";}
			$address="-S {$datas["remote_server"]} -p {$datas["remote_port"]} ";	
			echo "Starting......: ".date("H:i:s")." ufdbguardd: Use remote ufdbguard service: {$datas["remote_server"]}:{$datas["remote_port"]}\n";
			$GLOBALS[__FUNCTION__]="$binary $moinsC$address $log";	
			return "$binary $moinsC$address $log";
		}
		
		$effective_port=$this->ufdbguard_value("port");
		echo "Starting......: ".date("H:i:s")." ufdbguardd: Effective port:`$effective_port`\n";
		if(is_numeric($effective_port)){$datas["tcpsockets"]=1;}
		
		
		if($major>0){
			if($minor>26){
				if($datas["tcpsockets"]==1){
					if(trim($datas["listen_addr"]==null)){$datas["listen_addr"]="127.0.0.1";}
					echo "Starting......: ".date("H:i:s")." ufdbguardd: Use remote ufdbguard service: {$datas["listen_addr"]}:{$datas["remote_port"]}\n";
					$address="-S {$datas["listen_addr"]} -p {$datas["listen_port"]} ";	
					$GLOBALS[__FUNCTION__]="$binary $moinsC$address $log";	
					return "$binary $moinsC$address $log";		
				}	
			}
		}
		echo "Starting......: ".date("H:i:s")." ufdbguardd: Use remote ufdbguard service: $binary $moinsC$log\n";
		$GLOBALS[__FUNCTION__]="$binary $moinsC$log";
		return "$binary $moinsC$log";	
	}
	
	
private function WebFilteringRulesAllowed(){
		$sock=new sockets();
		$SquidGuardApachePort=intval($sock->GET_INFO("SquidGuardApachePort"));
		$SquidGuardApacheSSLPort=intval($sock->GET_INFO("SquidGuardApacheSSLPort"));
		if($SquidGuardApacheSSLPort==0){$SquidGuardApacheSSLPort=9025;}
		if($SquidGuardApachePort==0){$SquidGuardApachePort=9020;}
		$f[]="acl DenyWebPagePorts port $SquidGuardApacheSSLPort $SquidGuardApachePort";
		$f[]="url_rewrite_access deny MgRPort MgRClient all";
		$f[]="url_rewrite_access deny DenyWebPagePorts\n";
		return @implode("\n", $f);
}
	

	

	
	
	private function zapchain_squidguard(){
		if($this->SquidUrgency==1){return null;}
		if($this->EnableUfdbGuard()==0){return null;}
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
	$users=$GLOBALS["CLASS_USERS"];
		if(!$users->SQUIDGUARD_INSTALLED){
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." zapchain: squidGuard Installed: FALSE\n";}
			return null;
		}
		if($this->enable_squidguard==0){
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." zapchain: squidGuard enabled: FALSE\n";}
			return null;
		}
		
		
		$sock=new sockets();
		if($users->C_ICAP_INSTALLED){
			if($this->enable_cicap==1){
				$EnableSquidGuardInCiCAP=$sock->GET_INFO("EnableSquidGuardInCiCAP");
				if($EnableSquidGuardInCiCAP==null){$EnableSquidGuardInCiCAP=1;}
				if($EnableSquidGuardInCiCAP==1){$this->enable_squidguard=0;}
			}
		}

		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." zapchain: squidGuard in C-ICAP=$EnableSquidGuardInCiCAP\n";}
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." zapchain: squidGuard Enabled=$this->enable_squidguard\n";}
		
		if($users->APP_UFDBGUARD_INSTALLED){if($this->enable_UfdbGuard==1){$this->enable_squidguard=0;}}
		if($this->enable_squidguard==0){return null;}
		$bin=trim($users->SQUIDGUARD_BIN_PATH);
		if($bin==null){
			$sock=new sockets();
			$bin=base64_decode($sock->getFrameWork("cmd.php?find-program=squidGard"));
			if(trim($bin)==null){
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." Unable to activate squidGuard no path found\n";}
				return null;
			}
		}
		return "$bin -c /etc/squid/squidGuard.conf";
	}
	
	
	private function tweeks_performances(){
		$conf[]="log_icp_queries off";
		$conf[]="client_db on";
		$conf[]="buffered_logs on";
		$conf[]="#--------- TWEEKS PERFORMANCES";
		$conf[]="quick_abort_min 0 KB";
		$conf[]="quick_abort_max 0 KB";
		$conf[]="quick_abort_pct 95";
		
		if($this->SquidSimpleConfig==1){
			$conf[]="## # # ABORTED SIMPLE CONFIG ENABLED # # #";
			return @implode("\n", $conf)."\n";
		}
		if($this->SquidUrgency==1){
			$conf[]="## # # ABORTED PROXY IN EMERGENCY MODE # # #";
			return @implode("\n", $conf)."\n";
		}
		
		$conf[]="# http://blog.last.fm/2007/08/30/squid-optimization-guide";
		$conf[]=$this->memory_pools()."";
		

		
		
		$conf[]="half_closed_clients off";
		return @implode("\n", $conf)."\n";
	}
	
	

		
	private function ufdbguard_concurrency(){
		return false;
		$sock=new sockets();
		$RedirectorsArray=unserialize(base64_decode($sock->GET_INFO("SquidRedirectorsOptions")));
		if(!isset($RedirectorsArray["url_rewrite_concurrency"])){$RedirectorsArray["url_rewrite_concurrency"]=0;}
		if(!is_numeric($RedirectorsArray["url_rewrite_concurrency"])){$RedirectorsArray["url_rewrite_concurrency"]=0;}
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." redirectors rewrite concurrency \"{$RedirectorsArray["url_rewrite_concurrency"]}\"\n";}
		if($RedirectorsArray["url_rewrite_concurrency"]>0){return true;}
		return false;
	}
	
	
	public function url_ufdb_children(){
		$sock=new sockets();
		$datas=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
		$caches_center=new SquidCacheCenter();
		$CPU_NUMBER=$caches_center->WORKERS_NUMBER();
		if(!isset($datas["url_rewrite_children_concurrency"])){$datas["url_rewrite_children_concurrency"]=2;}
		if(!isset($datas["url_rewrite_children_startup"])){$datas["url_rewrite_children_startup"]=5;}
		if(!isset($datas["url_rewrite_children_idle"])){$datas["url_rewrite_children_idle"]=5;}
		if(!isset($datas["url_rewrite_children_max"])){$datas["url_rewrite_children_max"]=30;}
		
		
		$url_rewrite_children=$datas["url_rewrite_children_max"];
		$url_rewrite_startup=$datas["url_rewrite_children_startup"];
		$url_rewrite_idle=$datas["url_rewrite_children_idle"];
		
		if($CPU_NUMBER>1){
			$url_rewrite_children=round($url_rewrite_children/$CPU_NUMBER);
			$url_rewrite_startup=round($url_rewrite_startup/$CPU_NUMBER);
			$url_rewrite_idle=round($url_rewrite_idle/$CPU_NUMBER);
			if($url_rewrite_children==0){$url_rewrite_children=2;}
			if($url_rewrite_startup==0){$url_rewrite_startup=1;}
			if($url_rewrite_idle==0){$url_rewrite_idle=1;}
			if($url_rewrite_startup>$url_rewrite_children){$url_rewrite_startup=$url_rewrite_children;}
			if($url_rewrite_idle>$url_rewrite_children){$url_rewrite_idle=1;}
		
		}
		
		$conf[]="url_rewrite_children $url_rewrite_children startup=$url_rewrite_startup idle=$url_rewrite_idle concurrency={$datas["url_rewrite_children_concurrency"]}";
		
		if($this->IS_35){
			$conf[]="# Is a 3.5x version...[".__LINE__."]";
			
			$url_rewrite_extras[]="%>a/%>A %un %>rm myip=%la myport=%lp";
			
			
			if($this->ACL_ARP_ENABLED){
				$url_rewrite_extras[]="mac=%>eui";
			}
			
			$url_rewrite_extras[]="sni=%ssl::>sni";
			$conf[]="url_rewrite_extras \"".@implode(" ", $url_rewrite_extras)."\"";
			
		}else{
			$conf[]="# Not a 3.5x version...[".__LINE__."]";
		}
		$FinalLine=@implode("\n", $conf);
		
		$GLOBALS["squimem_url_rewrite_children"]=$FinalLine;
		return $FinalLine;
		
		
		
	}
		
	public function url_rewrite_children($ufdb=false){
		if(isset($GLOBALS["squimem_url_rewrite_children"])){return $GLOBALS["squimem_url_rewrite_children"];}
		$sock=new sockets();
		$caches_center=new SquidCacheCenter();
		$CPU_NUMBER=$caches_center->WORKERS_NUMBER();
		$RedirectorsArray=unserialize(base64_decode($sock->GET_INFO("SquidRedirectorsOptions")));
		if(!isset($RedirectorsArray["url_rewrite_children"])){$RedirectorsArray["url_rewrite_children"]=20;}
		if(!isset($RedirectorsArray["url_rewrite_startup"])){$RedirectorsArray["url_rewrite_startup"]=5;}
		if(!isset($RedirectorsArray["url_rewrite_idle"])){$RedirectorsArray["url_rewrite_idle"]=1;}
		if(!isset($RedirectorsArray["url_rewrite_concurrency"])){$RedirectorsArray["url_rewrite_concurrency"]=0;}
		if(!is_numeric($RedirectorsArray["url_rewrite_children"])){$RedirectorsArray["url_rewrite_children"]=20;}
		if(!is_numeric($RedirectorsArray["url_rewrite_startup"])){$RedirectorsArray["url_rewrite_startup"]=5;}
		if(!is_numeric($RedirectorsArray["url_rewrite_idle"])){$RedirectorsArray["url_rewrite_idle"]=1;}
		if(!is_numeric($RedirectorsArray["url_rewrite_concurrency"])){$RedirectorsArray["url_rewrite_concurrency"]=0;}	
		
		

		if($this->enable_UfdbGuard==1){$RedirectorsArray["url_rewrite_concurrency"]=0;}
		
		$url_rewrite_children=$RedirectorsArray["url_rewrite_children"];
		$url_rewrite_startup=$RedirectorsArray["url_rewrite_startup"];
		$url_rewrite_idle=$RedirectorsArray["url_rewrite_idle"];
		
		if($CPU_NUMBER>1){
			$url_rewrite_children=round($url_rewrite_children/$CPU_NUMBER);
			$url_rewrite_startup=round($url_rewrite_startup/$CPU_NUMBER);
			$url_rewrite_idle=round($url_rewrite_idle/$CPU_NUMBER);
			if($url_rewrite_children==0){$url_rewrite_children=2;}
			if($url_rewrite_startup==0){$url_rewrite_startup=1;}
			if($url_rewrite_idle==0){$url_rewrite_idle=1;}
			if($url_rewrite_startup>$url_rewrite_children){$url_rewrite_startup=$url_rewrite_children;}
			if($url_rewrite_idle>$url_rewrite_children){$url_rewrite_idle=1;}
				
		}
		
		
		
		
		$FinalLine="url_rewrite_children $url_rewrite_children startup=$url_rewrite_startup idle=$url_rewrite_idle concurrency={$RedirectorsArray["url_rewrite_concurrency"]}";
		$GLOBALS["squimem_url_rewrite_children"]=$FinalLine;		
		return $FinalLine;
		}
		
		
	
	private function squidGuard(){
		$SquidUFDBUrgency=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidUFDBUrgency"));
		if($SquidUFDBUrgency==1){return "\n# * * * * Web filtering emergency!!! * * * *\n";}
		if($this->SquidUrgency==1){return "\# * * * * Web Proxy emergency!!! * * * *\n";}
		if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
		$users=$GLOBALS["CLASS_USERS"];
		$sock=new sockets();
		$unix=new unix();
		$SquidDebugPort=intval($sock->GET_INFO("SquidDebugPort"));
		$sock=new sockets();
		$this->enable_UfdbGuard=$sock->EnableUfdbGuard();
		$EnableStreamCache=intval($sock->GET_INFO("EnableStreamCache"));
		if(!$users->APP_UFDBGUARD_INSTALLED){$this->enable_UfdbGuard=0;}
		$UfdbUseArticaClient=$sock->GET_INFO("UfdbUseArticaClient");
		if(!is_numeric($UfdbUseArticaClient)){$UfdbUseArticaClient=1;}
		$SquidEnforceRules=intval($sock->GET_INFO("SquidEnforceRules"));
		$HyperCacheStoragePath=$sock->GET_INFO("HyperCacheStoragePath");
		$EnableGoogleSafeBrowsing=intval($sock->GET_INFO("EnableGoogleSafeBrowsing"));
		if($HyperCacheStoragePath==null){$HyperCacheStoragePath="/home/artica/proxy-cache";}
		$squidversion=$this->SQUID_VERSION;
		$conf[]= "\n#--------- UfdbGuard [".__LINE__."]";
		
		@file_put_contents("/var/log/squid/ufdbgclient.version", $squidversion);
		@chown("/var/log/squid/ufdbgclient.version", "squid");
		
		if($SquidEnforceRules==1){
			$EnableStreamCache=0;
			$this->enable_UfdbGuard=1;
		}
		
		if($EnableGoogleSafeBrowsing==1){
			$EnableStreamCache=0;
			$this->enable_UfdbGuard=1;
		}
	
		
		if($this->enable_UfdbGuard==0){
			if($EnableStreamCache==1){
				$python=$unix->find_program("python");
				if(is_file("/usr/share/videocache/videocache.py")){
					$conf[]= "url_rewrite_program /usr/share/artica-postfix/ufdbgclient.php";
					$conf[]=$this->url_ufdb_children();
					return implode("\n",$conf);
				}
			}
		}
		
	
		if($this->enable_UfdbGuard==0){
			$conf[]= "\n#Disabled enable_UfdbGuard=$this->enable_UfdbGuard";
			return implode("\n",$conf);
		}
		
		if($EnableStreamCache==1){
			$conf[]= "url_rewrite_program /usr/share/artica-postfix/ufdbgclient.php";
			$conf[]=$this->url_ufdb_children();
			return implode("\n",$conf);
		}
		
		if($SquidEnforceRules==1){
			$conf[]= "url_rewrite_program /usr/share/artica-postfix/ufdbgclient.php";
			$conf[]=$this->url_ufdb_children();
			@mkdir($HyperCacheStoragePath,0755,true);
			@chown($HyperCacheStoragePath, "squid");
			@chgrp($HyperCacheStoragePath, "squid");
			return implode("\n",$conf);		
		}
		
		
		
			
		$this->enable_dansguardian=0;
		$conf[]= "\n#--------- UfdbGuard / UfdbUseArticaClient=$UfdbUseArticaClient";
		$zapchain_ufdbguard=$this->zapchain_ufdbguard();
		if($zapchain_ufdbguard==null){	
			$conf[]= "\n#--------- zapchain_ufdbguard return a null value";
			return implode("\n",$conf);
		}
		$datas=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
		if(!isset($datas["url_rewrite_children_concurrency"])){$datas["url_rewrite_children_concurrency"]=2;}
		if(!isset($datas["url_rewrite_children_startup"])){$datas["url_rewrite_children_startup"]=5;}
		if(!isset($datas["url_rewrite_children_idle"])){$datas["url_rewrite_children_idle"]=5;}
		if(!isset($datas["url_rewrite_children_max"])){$datas["url_rewrite_children_max"]=30;}
		$conf[]= "url_rewrite_program $zapchain_ufdbguard";	
		$conf[]=$this->url_ufdb_children();
		$conf[]=$this->WebFilteringRulesAllowed();
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." ufdbguardd: \"$zapchain_ufdbguard\"\n";}
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." ufdbguardd: Max processes        : \"{$datas["url_rewrite_children_max"]}\"\n";}
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." ufdbguardd: Startup processes    : \"{$datas["url_rewrite_children_startup"]}\"\n";}
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." ufdbguardd: Idle processes       : \"{$datas["url_rewrite_children_idle"]}\"\n";}
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." ufdbguardd: Concurrency processes: \"{$datas["url_rewrite_children_concurrency"]}\"\n";}
		return implode("\n",$conf);
	}		


	

	
	
	private function cache_peer_extract_options($optionsDecoded){
		$array=$optionsDecoded;
		if(!is_array($array)){return null;}
		if(count($array)==0){return null;}
		$login=null;$parmType=null;
		while (list($num,$val)=each($array)){
			
			if($num=="loginPASSTHRU"){$login="login=PASSTHRU";unset($array[$num]);continue;}
			
			if($num=="login"){
				if(!preg_match("#(.+?):(.+)#", $val)){unset($array[$num]);continue;}
				$login="login=$val";
				unset($array[$num]);
				continue;
			}
			if($num=="loginPASS"){$login="login=PASS";unset($array[$num]);continue;}
			if($num=="connection-auth"){
				$val=strtolower($val);
				$val=str_replace($val,"1","on");
				$val=str_replace($val,"0","off");
				$val=str_replace($val,"yes","on");
				$val=str_replace($val,"no","off");
				if(!preg_match("#(on|off)#i", $val)){$val="on";}
				$array[$num]=$val;
			}
			if ($num=="proxy-only"){$parmType=$num;unset($array[$num]);continue;}
			if ($num=="round-robin"){$parmType=$num;unset($array[$num]);continue;}
			if ($num=="userhash"){$parmType=$num;unset($array[$num]);continue;}
			if ($num=="sourcehash"){$parmType=$num;unset($array[$num]);continue;}
			if ($num=="multicast-siblings"){$parmType=$num;unset($array[$num]);continue;}
			if ($num=="weighted-round-robin"){$parmType=$num;unset($array[$num]);continue;}
		
		
		}
		if($login<>null){$array[$login]=null;}
		if($parmType<>null){$parmType=" $parmType ";}
		reset($array);
		
		while (list($num,$val)=each($array)){
			if(trim($num)==null){continue;}
			if($num=="carp"){$num="carp carp-key=host,port,path";}
			if(trim($val)<>null){$z[]="$num=$val";}else{$z[]=$num;}
		}
		return implode(" ",$z);		
		
	}
	
	
	public function cache_peer_acls(){
		$f=array();
		$acls=new squid_acls_groups();
		$cache_parents=$acls->buildacls_bytype("cache_parent");
		if(count($cache_parents)==0){
			$f[]="# --------- SQUID Parents in ACLS = No row [".__LINE__."]";
			return @implode("\n", $f);
		}
			
		while (list ($index, $MAINS) = each ($cache_parents) ){
			$f[]=$MAINS["LINE"];
			$PortsAcls=$MAINS["PORTS_ACLS"];
			$PeerName=$MAINS["NAME"];
			$f[]="# --------- SQUID Parents in ACLS $PeerName [".__LINE__."]";
			if($PortsAcls<>null){$PortsAcls="$PortsAcls ";}
				while (list ($index, $groups) = each ($MAINS["GROUPS"]) ){
						$f[]="cache_peer_access $PeerName allow $PortsAcls".@implode(" ", $groups);
				}
				$f[]="cache_peer_access $PeerName deny all";
			}
			
	
		return @implode("\n", $f);
		
		
		
	}
	
	
	
	
	
	private function cache_peer_ntlm(){
		$sock=new sockets();
		$f[]="#";
		$f[]="# --------- SQUID NTLM PARENT --------- [".__LINE__."]";
		$SquidParentNTLMPort=intval($sock->GET_INFO("SquidParentNTLMPort"));
		$f[]="cache_peer 127.0.0.1 parent $SquidParentNTLMPort 0 default no-query login=PASSTHRU name=NTLMParent";
		$f[]="prefer_direct off";
		$f[]="nonhierarchical_direct off";
		$f[]="never_direct allow all";
		$f[]="forwarded_for on";
		$this->forwarded_for=null;
		$f[]="";
		return @implode("\n", $f);
	}
	
	
	private function cache_tweaks(){
		
		if($this->DisableAnyCache==1){
			return "# # # CACHES ARE DISABLED # # # [".__LINE__."]\n";
		}
		
		$sock=new sockets();
		$SquidCacheLevel=intval($sock->GET_INFO("SquidCacheLevel"));
		if($SquidCacheLevel>0){
			
			$conf[]="cache_swap_high {$this->global_conf_array["cache_swap_high"]}";
			$conf[]="cache_swap_low {$this->global_conf_array["cache_swap_low"]}";
			if($this->SquidSimpleConfig==0){
				$conf[]="read_ahead_gap {$this->global_conf_array["read_ahead_gap"]}";
				$conf[]="store_avg_object_size 64 KB";
				$conf[]="store_objects_per_bucket 50";
				
			}
		}
		
		
		if($this->SquidSimpleConfig==0){
			$CacheReplacementPolicyZ["lru"]="lru";
			$CacheReplacementPolicyZ["heap_GDSF"]="heap GDSF";
			$CacheReplacementPolicyZ["heap_LFUDA"]="heap LFUDA";
			$CacheReplacementPolicyZ["heap_LRU"]="heap LRU";
			$CacheReplacementPolicy=$sock->GET_INFO("CacheReplacementPolicy");
			if($CacheReplacementPolicy==null){$CacheReplacementPolicy="heap_LFUDA";}
			if(is_numeric($CacheReplacementPolicy)){$CacheReplacementPolicy="heap_LFUDA";}
			$CacheReplacementPolicyT=$CacheReplacementPolicyZ[$CacheReplacementPolicy];
			if($CacheReplacementPolicyT==null){$CacheReplacementPolicyT="heap LFUDA";}
		
			$conf[]="#Policy $CacheReplacementPolicy";
			$conf[]="cache_replacement_policy $CacheReplacementPolicyT";
		}

		return @implode("\n", $conf)."\n";		
		
	}
	
	
	
	
	public function cache_peer(){
		if(!$this->ASROOT){return;}
		$sock=new sockets();
		$ini=new Bs_IniHandler();
		$ArticaSquidParameters=$sock->GET_INFO('ArticaSquidParameters');
		$ini->loadString($sock->GET_INFO('ArticaSquidParameters'));
		$EnableParentProxy=intval($ini->_params["NETWORK"]["EnableParentProxy"]);
		$EnableParentProxy2=intval($sock->GET_INFO("EnableParentProxy"));
		$PrivoxyEnabled=intval($sock->GET_INFO("PrivoxyEnabled"));
		if($EnableParentProxy2==1){$EnableParentProxy=1;}
		if($PrivoxyEnabled==1){$EnableParentProxy=0;}
		$SquidEnableParentNTLM=intval($sock->GET_INFO("SquidEnableParentNTLM"));
		if($SquidEnableParentNTLM==1){
			return $this->cache_peer_ntlm();
		}
		
		$f[]="#";
		$f[]="# --------- SQUID PARENTS --------- [".__LINE__."]";
		$f[]="# --------- EnableParentProxy ini=NETWORK/EnableParentProxy = $EnableParentProxy [".__LINE__."]";
		$f[]=$this->cache_peer_acls();
		
		if($EnableParentProxy==0){
			echo "Starting......: ".date("H:i:s")." [ LB ]: Load-balancing mode is disabled\n";
			$f[]="# --------- (feature not enabled) EnableParentProxy = 0  / PrivoxyEnabled = $PrivoxyEnabled [".__LINE__."]\n";
			return @implode("\n", $f);
		}
		
		$sql="SELECT * FROM squid_parents WHERE enabled=1 ORDER BY weight DESC";
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");
		echo "Starting......: ".date("H:i:s")." [ LB ]: ". mysql_num_rows($results)." Proxy backends...\n";
		if(!$q->ok){
			if(preg_match("#Unknown column#", $q->mysql_error)){
				$q->QUERY_SQL("ALTER TABLE `squid_parents` ADD `enabled` INT( 1 ) NOT NULL DEFAULT '1',ADD INDEX ( `enabled` )","artica_backup");
				$q->QUERY_SQL("ALTER TABLE `squid_parents` ADD `htcp_port` INT( 3 ) NOT NULL","artica_backup");
				$q->QUERY_SQL("ALTER TABLE `squid_parents` ADD `weight` INT( 1 ) NOT NULL DEFAULT '1'","artica_backup");
				$q->QUERY_SQL("ALTER TABLE `squid_parents` ADD `zOrder` INT( 1 ) NOT NULL DEFAULT '1'","artica_backup");
				$results=$q->QUERY_SQL($sql,"artica_backup");
			}
		}
		
		
		if(!$q->ok){
			$q->mysql_error=str_replace("\n", " ", $q->mysql_error);
			if(!$q->ok){return "\n\n\n#--------- SQUID PARENTS (MySQL ERROR $q->mysql_error)\n";}
		}
		
		if( mysql_num_rows($results)==0){return "\n\n\n#--------- SQUID PARENTS 0 ITEM ENABLED\n";}
		
		$f[]="";
		$f[]="";
		$f[]="# using ssl ? http://hwoarang.silverarrow.org/2011/02/22/ssl-encryption-between-parent-and-child-squid-proxy/";
		
		if($this->prefer_direct==0){
			$f[]="prefer_direct off";
		}else{
			$f[]="prefer_direct on";
		}
		
		if($this->nonhierarchical_direct==0){
			$f[]="nonhierarchical_direct off";
		}else{
			$f[]="nonhierarchical_direct on";
		}		
		$acls=new squid_acls_groups();
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			
			
			if(trim($ligne["servername"])==null){continue;}
			if(trim($ligne["server_port"])==null){continue;}
			if(trim($ligne["server_type"])==null){$ligne["server_type"]="parent";}
			if(strlen(trim($ligne["icp_port"]))==0){$ligne["icp_port"]=0;}
			$MAIN_RESULTS=$acls->cache_peer_explode($ligne["ID"]);
			if(!is_array($MAIN_RESULTS)){continue;}
			if(!isset($MAIN_RESULTS["NET"])){continue;}
			$NET=$MAIN_RESULTS["NET"];
			$f[]=$MAIN_RESULTS["LINE"];
			

			}
			
			
		$cache_peer_domains=$this->cache_peer_domains();
		
			
		if($cache_peer_domains==null){
			$f[]="#--------- RULES THAT FORCE TO PASS trough parent proxies except FTP";
			$f[]="always_direct allow FTP";
			if($this->SSL_BUMP==0){$f[]="always_direct deny all";$f[]="prefer_direct off";}
			$f[]="never_direct allow all";
		}else{
			$f[]=$cache_peer_domains;
		}	
		$f[]="";
		
		return implode("\n",$f);
		
	}
	
	private function cache_peer_array(){
		$sql="SELECT ID,servername FROM squid_parents WHERE enabled=1";
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$name="Peer{$ligne["ID"]}";
			$servername=$ligne["servername"];
			echo "Starting......: ".date("H:i:s")." [PEER]: $servername = $name\n";
			$MAIN[$servername]=$name;
			
		}
		
		return $MAIN;
		
		
	}
	
	
	private function cache_peer_domains(){
		$DENYS=array();
		$PeerMain=$this->cache_peer_array();
		$AclDenyForPeerHost=array();
		
		if($this->SquidUrgency==1){return null;}
		$q=new mysql();
		$sql="SELECT * FROM cache_peer_domain WHERE `enabled`=1";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){
			return;
		}
		echo "Starting......: ".date("H:i:s")." [ LB ]: ". mysql_num_rows($results)." backends domains...\n";
		if(mysql_num_rows($results)==0){return;}
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$servername=trim($ligne["servername"]);
			if(!isset($PeerMain[$servername])){continue;}
			$PeerHost=$PeerMain[$servername];
			$dot=".";
			if(substr($ligne["domain"], 0,1)=="^"){
				$ligne["domain"]=substr($ligne["domain"], 1,strlen($ligne["domain"]));
				$dot=null;
			}
			
			if($ligne["reverse"]==1){
				$ARRAY[$PeerHost][]="!{$dot}".$ligne["domain"];
				$AclDenyForPeerHost[$PeerHost][]="acl Deny{$PeerHost} dstdomain {$dot}{$ligne["domain"]}";
				continue;
			}
			$ARRAY[$PeerHost][]=".".$ligne["domain"];
			$AclAllowForPeerHost[$PeerHost][]="acl Must{$PeerHost} dstdomain {$dot}{$ligne["domain"]}";
		}
		
		while (list($peer,$zvals)=each($ARRAY)){
			$f[]="cache_peer_domain $peer ".@implode(" ", $zvals);
		}
		
		if(count($AclDenyForPeerHost)>0){
			while (list($peer,$DenyAcls)=each($AclDenyForPeerHost)){
				$f[]=@implode("\n", $DenyAcls);
				$DENYS[]="cache_peer_access $peer deny Deny{$PeerHost}";
			}
		}
		
		if(count($AclAllowForPeerHost)>0){
			while (list($peer,$AllowAcls)=each($AclAllowForPeerHost)){
				$f[]=@implode("\n", $AllowAcls);
				$f[]="cache_peer_access $peer allow Must{$PeerHost}";
				$DENY_ALLS["Must{$PeerHost}"]=$peer;
			}

		}
		

		
		while (list($hostname,$PeerKey)=each($PeerMain)){
			reset($DENY_ALLS);
			while (list($AclToDeny,$PeerSource)=each($DENY_ALLS)){
				if($PeerSource==$PeerKey){continue;}
				$DENYS[]="cache_peer_access $PeerKey deny $AclToDeny";
			}
		}
		

		if(count($DENYS)>0){
			$f[]=@implode("\n", $DENYS);
		}
		
		
		return @implode("\n",$f);
		
	}
	
	
	private function x_forwarded_for(){
		if(!$this->ASROOT){return;}
		$enabled=false;
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
		$results=$this->_compile_parmz();
		while (list($num,$val)=each($results)){if(preg_match("#enable-follow-x-forwarded-for#", $val)){$enabled=true;}}
		if(!$enabled){if($GLOBALS["VERBOSE"]){echo "DEBUG : enable-follow-x-forwarded-for no such compilation option !\n";}return;}
		if($GLOBALS["VERBOSE"]){echo "DEBUG :forwarded_for enable-follow-x-forwarded-for OK\n";}
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ LB ]: enable-follow-x-forwarded-for OK\n";}
		
		
		
		$users=new usersMenus();
		$IpClass=new IP();
		$q=new mysql_squid_builder();
		$sql="SELECT *  FROM `squid_balancers` WHERE enabled=1";
		$results = $q->QUERY_SQL($sql);
		if(!$q->ok){$f[]="# squid_balancers mysql error";}
		if(mysql_num_rows($results)==0){$f[]="# squid_balancers no item";}
			
		$x=0;
		while ($ligne = mysql_fetch_assoc($results)) {
			if(!$IpClass->isIPAddressOrRange($ligne["ipsrc"])){
				$f[]="#{$ligne["ipsrc"]} invalid";
				continue;
			}
			$x++;
			$f[]="acl FollowXForwardedIP src {$ligne["ipsrc"]}";
		}
		
		if($x>0){
			$f[]="follow_x_forwarded_for allow FollowXForwardedIP";
		}
		
		
		if( ($users->DANSGUARDIAN_INSTALLED) OR ($this->EnableTransparent27==1)){
			$f[]="follow_x_forwarded_for allow localhost";
			$x++;
		}
		
		if($x>0){
			$f[]="follow_x_forwarded_for deny all";
		}
		
		$f[]="acl_uses_indirect_client         on";
		$f[]="delay_pool_uses_indirect_client  on";
		$f[]="log_uses_indirect_client         on";
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ LB ]: return a set of rules...\n";}
		return @implode("\n", $f)."\n";
	}


	
private function isRemoteStatsAppliance(){
	$sock=new sockets();
	$EnableRemoteSyslogStatsAppliance=$sock->GET_INFO("EnableRemoteSyslogStatsAppliance");
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	if(!is_numeric($EnableRemoteSyslogStatsAppliance)){$EnableRemoteSyslogStatsAppliance=0;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if(is_file("/etc/artica-postfix/PROXYTINY_APPLIANCE")){$DisableArticaProxyStatistics=1;}
	if($DisableArticaProxyStatistics==1){return true;}
	if($EnableRemoteSyslogStatsAppliance==1){return true;}
	if($EnableRemoteStatisticsAppliance==1){return true;}
	return false;
	
}	

private function access_logs_30($none,$denylogsTXT){
	$sock=new sockets();
	$stdio=null;
	@mkdir("/var/log/squid",0755,true);
	@chown("/var/log/squid","squid");
	@chgrp("/var/log/squid", "squid");
	$SquidStoreLogLogging=intval($sock->GET_INFO("SquidStoreLogLogging"));
	$SquidNoAccessLogs=intval($sock->GET_INFO("SquidNoAccessLogs"));
	
	
	
	if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [LOG ]: Build log for 3.0x: EnableMacAddressFilter=$this->EnableMacAddressFilter\n";}
	if($SquidStoreLogLogging==1){$conf[]="cache_store_log	/var/log/squid/store.log";}
	
	$conf[]="access_log syslog:authpriv.info common $denylogsTXT";
	
	if($this->LogsWarninStop==1){
		$conf[]="access_log none all";
		return @implode("\n", $conf);
	}

	if($SquidNoAccessLogs==1){
		@unlink("/var/log/squid/access.log");
		$conf[]="access_log none all";
		return @implode("\n", $conf);
	}
	
	$conf[]="access_log /var/log/squid/access.log squid $denylogsTXT";
	return @implode("\n", $conf);
	
	
}

private function access_logs_31($none,$denylogsTXT){
	$sock=new sockets();
	$stdio=null;
	@mkdir("/var/log/squid",0755,true);
	@chown("/var/log/squid","squid");
	@chgrp("/var/log/squid", "squid");
	$SquidStoreLogLogging=intval($sock->GET_INFO("SquidStoreLogLogging"));
	$SquidNoAccessLogs=intval($sock->GET_INFO("SquidNoAccessLogs"));



	if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [LOG ]: Build log for 3.1x: EnableMacAddressFilter=$this->EnableMacAddressFilter\n";}
	if($SquidStoreLogLogging==1){$conf[]="cache_store_log	/var/log/squid/store.log";}
	$MACFILER='00:00:00:00:00:00';
	$conf[]="# --------- on a 3.1x, MAC cannot be logged..\n";
	$conf[]="logformat $MACFILER:::%>a:::%[ui:::%[un:::%tl:::%rm:::%ru:::HTTP/%rv:::%>Hs:::%<st:::%Ss:%Sh:::%{User-Agent}>h:::%{X-Forwarded-For}>h%<A:::%>A:::%tr:::%mt";
	$conf[]="access_log syslog:authpriv.info common $denylogsTXT";
	
	if($this->LogsWarninStop==1){
		$conf[]="access_log none all";
		return @implode("\n", $conf);
	}
	
	if($SquidNoAccessLogs==1){
		@unlink("/var/log/squid/access.log");
		$conf[]="access_log none all";
		return @implode("\n", $conf);
	}
	
	
	
	$conf[]="access_log /var/log/squid/access.log squid $denylogsTXT";
	return @implode("\n", $conf)."\n";

}
private function logfile_daemon_tail(){
	$sock=new sockets();
	$EnableInfluxDB=intval($sock->GET_INFO("EnableInfluxDB"));
	$conf[]="# LOGGING FOR STATISTICS PURPOSE ". __FUNCTION__."/[".__LINE__."]";
	
	
	$InfluxUseRemote=intval($sock->GET_INFO("InfluxUseRemote"));
	$InfluxSyslogRemote=intval($sock->GET_INFO("InfluxSyslogRemote"));
	if($InfluxUseRemote==0){$InfluxSyslogRemote=0;}
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	$InfluxUseRemoteIpaddr=$sock->GET_INFO("InfluxUseRemoteIpaddr");
	$hostname=null;
	if($InfluxSyslogRemote==0){
		if($SquidPerformance>1){
			$conf[]="# SquidPerformance is set to NO STATISTICS";
			return @implode("\n", $conf);
		}
			
		if($this->SquidUrgency==1){
			$conf[]="# Proxy is in GLOBAL Emergency mode";
			return @implode("\n", $conf);
		}
		
		if($this->LogsWarninStop==1){
			$conf[]="# Proxy is in LOGS Emergency mode";
			return @implode("\n", $conf);
		}
		
	}
	
	
	$MACFILER="%>eui";
	$sni=null;
	if($this->IS_32){$stdio="stdio:";}
	$line35="logformat=common buffer-size=64KB on-error=drop ";
	if(!preg_match("#^3\.(4|5|6|7|8|9)\.#", $this->SQUID_VERSION)){
		$line35="common ";
	}
	$unix=new unix();
	$hostname="proxyname=".$unix->hostname_g();
	$logengine="$stdio/var/log/squid/squidtail.log";
	if($InfluxSyslogRemote==1){
		$logengine="syslog:local5.info";
		@unlink("/etc/rsyslog.d/artica-client-stats.conf");
		$f[]="local5.*\t@$InfluxUseRemoteIpaddr\n";
		@file_put_contents("/etc/rsyslog.d/artica-client-stats.conf", @implode("\n", $f));
		system("/etc/init.d/rsyslog restart");
	}
	
	
	if($this->IS_35){$sni="%ssl::>sni";}
	$conf[]="";
	$conf[]="# $this->SQUID_VERSION is 3.5x = $this->IS_35; InfluxSyslogRemote=$InfluxSyslogRemote";
	$conf[]="logformat common $MACFILER:::%>a:::%[ui:::%[un:::%tl:::%rm:::%ru:::HTTP/%rv:::%>Hs:::%<st:::%Ss:%Sh:::%{User-Agent}>h:::%{X-Forwarded-For}>h:::%<A:::%>A:::%tr:::%mt:::$sni:::{$hostname}:::%ea";
	$conf[]="access_log $logengine $line35";
	return @implode("\n", $conf);
}



private function access_logs32($none,$denylogsTXT){
	$stdio=null;
	$sock=new sockets();
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	$SquidStoreLogLogging=intval($sock->GET_INFO("SquidStoreLogLogging"));
	$SquidNoAccessLogs=intval($sock->GET_INFO("SquidNoAccessLogs"));
	$isRemoteStatsAppliance=$this->isRemoteStatsAppliance();
	@mkdir("/var/log/squid",0755,true);
	@chown("/var/log/squid","squid");
	@chgrp("/var/log/squid", "squid");
	
	
	
	
	$MACFILER="%>eui";
	
	$logfile_daemon_ext=null;
	if($this->SquidActHasReverse==1){$logfile_daemon_ext=" --reverse";}
	if($isRemoteStatsAppliance){$logfile_daemon_ext=" --no-disk";}
	
	if($this->EnableMacAddressFilter==0){$MACFILER='00:00:00:00:00:00';}
	if($this->IS_32){$stdio="stdio:";}
	$conf[]=$this->logfile_daemon_tail();
	
	if($this->SquidUrgency==1){
		
		$conf[]="#access_log !!!!!!!!!!!!!!!! Proxy service in Emergency mode !!!!!!!!!!!!!!!!";
		$conf[]="access_log none all";
		$conf[]="";
		$conf[]="# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #";
		$conf[]="#";
		return @implode("\n", $conf);
	}
	if($this->LogsWarninStop==1){
		
		$conf[]="#access_log !!!!!!!!!!!!!!!! Proxy service in Logs Warning mode !!!!!!!!!!!!!!!!";
		$conf[]="access_log none all";
		$conf[]="";
		$conf[]="# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #";
		$conf[]="#";
		return @implode("\n", $conf);
	}
	
	if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [LOG ]: Build log for 3.2x or 3.3x: EnableMacAddressFilter=$this->EnableMacAddressFilter\n";}
	$conf[]="# LOG FORMAT MODE 3.2/3.3/3.4 LogsWarninStop=$this->LogsWarninStop";
	if($SquidStoreLogLogging==1){$conf[]="cache_store_log	stdio:/var/log/squid/store.log\n";}
	
	
		

	

	
	
	
	if($SquidNoAccessLogs==1){
		@unlink("/var/log/squid/access.log");
		$conf[]="#access_log Deny local logging [".__LINE__."]";
		$conf[]="access_log none all";
		$conf[]="";
		$conf[]="# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #";
		$conf[]="#";
		return @implode("\n", $conf);
	}
	
	$line35="logformat=squid buffer-size=64KB on-error=drop ";
	if(!preg_match("#^3\.(4|5|6|7|8|9)\.#", $this->SQUID_VERSION)){$line35="squid ";}
	
	$conf[]="access_log none MgrInfoUri";
	$conf[]="access_log none squidclient";
	$conf[]="access_log $stdio/var/log/squid/access.log $line35";
	
	$conf[]="";
	$conf[]="# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #";
	$conf[]="#";
	return @implode("\n", $conf)."\n";
}

private function netdb_filename(){
	$compile_params=$this->compilation_params();
	if(!isset($compile_params["enable-icmp"])){
		$conf[]="# netdb_filename not compiled";
		return @implode("\n",$conf);
		
	}
	if($this->LogsWarninStop==1){return;}
	if($this->SquidUrgency==1){return;}
	$stdio=null;
	if($this->IS_32){$stdio="stdio:";}
	$conf[]="netdb_filename $stdio/var/log/squid/netdb.state";
	return @implode("\n",$conf);
	
}



private function access_logs(){
	
	$ExternalSyslog=$this->syslog_remote();
	$sock=new sockets();
	$stdio=null;
	$EnableSargGenerator=$sock->GET_INFO("EnableSargGenerator");
	$ActAsASyslogServer=$sock->GET_INFO("ActAsASyslogServer");
	$SquidStoreLogLogging=$sock->GET_INFO("SquidStoreLogLogging");
	$SquidAsMasterCacheChilds=$sock->GET_INFO("SquidAsMasterCacheChilds");
	if(!is_numeric($EnableSargGenerator)){$EnableSargGenerator=0;}
	if(!is_numeric($ActAsASyslogServer)){$ActAsASyslogServer=0;}
	if(!is_numeric($SquidStoreLogLogging)){$SquidStoreLogLogging=0;}
	
	$isRemoteStatsAppliance=$this->isRemoteStatsAppliance();
	$SquidAsMasterPeer=intval($sock->GET_INFO("SquidAsMasterPeer"));
	
	
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	$SquidNoAccessLogs=intval($sock->GET_INFO("SquidNoAccessLogs"));
	
	if($this->IS_32){$stdio="stdio:";}
	
	$acls=new squid_acls_groups();
	
	
	$Parents_proxy=new squid_childs();
	

	

	$conf[]="#";
	$conf[]="# # # # # # # # # # # # # # # # # # LOGGING # # # # # # # # # # # # # # # # # #";
	$conf[]="# --------- SquidNoAccessLogs 	     = $SquidNoAccessLogs [".__LINE__."]";
	$conf[]="# --------- ActAsASyslogServer 	 = $ActAsASyslogServer [".__LINE__."]";
	$conf[]="# --------- EnableSargGenerator	 = $EnableSargGenerator [".__LINE__."]";
	$conf[]="# --------- isRemoteStatsAppliance	 = $isRemoteStatsAppliance [".__LINE__."]";;
	$conf[]="# --------- SquidAsMasterPeer		 = $SquidAsMasterPeer [".__LINE__."]";
	$conf[]="# --------- EnableMacAddressFilter	 = $this->EnableMacAddressFilter [".__LINE__."]";
	$conf[]="# --------- Is a 3.4 version		 = $this->IS_34 [".__LINE__."]";
	$conf[]="# --------- Peformance Level 		 = $SquidPerformance [".__LINE__."]";
	
	$conf[]=$this->netdb_filename();
	if($this->IS_31){
		if($this->EnableMacAddressFilter==1){$conf[]="eui_lookup on";}
		if($this->EnableMacAddressFilter==0){$conf[]="eui_lookup off";}
	}
	$conf[]="logfile_rotate 0";
	
	$deny_logs_parents=$Parents_proxy->deny_logs();
	$denylogs=$acls->buildacls_bytype("deny_log");
	
	
	if(count($denylogs)>0){
		while (list ($index, $line) = each ($denylogs) ){
			$conf[]="access_log none $line\n";
		}
	}

	
	if($ExternalSyslog<>null){$conf[]=$ExternalSyslog;}
	
	if($this->IS_27){
		if($this->LogsWarninStop==0){
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [LOG ]: Build log for 2.7x: EnableMacAddressFilter=$this->EnableMacAddressFilter\n";}
			if($SquidStoreLogLogging==1){$conf[]="cache_store_log	/var/log/squid/store.log";}
		}
		$conf[]="access_log syslog:authpriv.info  common";
		return @implode("\n", $conf)."\n";
		
	}
	
	
	if(($this->IS_30) && (!$this->IS_32)){
		$conf[]=$this->access_logs_30(null);
		return @implode("\n", $conf)."\n";
		
	}
	
	$csvlog=$this->access_log_csv();
	if($csvlog<>null){
		$conf[]="# --------- CSVLOG line:".__LINE__;
		$conf[]="$csvlog";
	}
				
	$log_access_acls_csv=$this->log_access_acls_csv();
	if($log_access_acls_csv<>null){$conf[]=$log_access_acls_csv;}
	$squid_childs=new squid_childs();
	$log_childs=$squid_childs->access_log_childs();
	if($log_childs<>null){$conf[]=$log_childs;}	
	
	
	if(($this->IS_31) && (!$this->IS_32)){
		$conf[]="# --------- on a 3.1x, MAC cannot be logged..\n";
		$conf[]=$this->access_logs_31(null);
		return @implode("\n", $conf);
		
	}
	
	$conf[]=$this->access_logs32(null);
	return @implode("\n", $conf);
		
}

private function build_http_debug_port(){
	$this->NETWORK_ALL_INTERFACES();
	$sock=new sockets();
	$SquidDebugPort=intval($sock->GET_INFO("SquidDebugPort"));
	$conf[]="# --------- http port for debug = $SquidDebugPort [".__LINE__."]\n";
	if($SquidDebugPort==0){return @implode("\n", $conf);}
	$SquidDebugPortNum=intval($sock->GET_INFO("SquidDebugPortNum"));
	$SquidDebugPortInterface=intval($sock->GET_INFO("SquidDebugPortInterface"));
	if($SquidDebugPortNum==0){$SquidDebugPortNum=rand(55678, 65000);$sock->SET_INFO("SquidDebugPortInterface",$SquidDebugPortNum); }
	if(!isset($GLOBALS["NETWORK_ALL_INTERFACES"][$SquidDebugPortInterface])){$SquidDebugPortInterface="0.0.0.0";}
	$conf[]="http_port $SquidDebugPortInterface:$SquidDebugPortNum";
	$token=$this->myport_token();
	$conf[]="acl MyTestPort $token $SquidDebugPortNum";
	$conf[]="cache deny MyTestPort";
	return @implode("\n", $conf);
	
}


private function build_http_ports(){
	$sock=new sockets();
	$conf_outgoing_addr=array();
	$DenySquidWriteConf=intval($sock->GET_INFO("DenySquidWriteConf"));
	if($DenySquidWriteConf==1){return;}
	$q=new mysql_squid_builder();
	$http_port_sslbump=$this->ssl_bump_port();
	$http_port_sslbump_DIRECT=$http_port_sslbump;
	$http_port_sslbump_2=$http_port_sslbump;
	$http_port_sslbump_3=$http_port_sslbump;
	
	

	$ssl_bump_token=$this->ssl_bump_token();
	$transparent_token=$this->transparent_token();
	$SquidAsMasterPeer=intval($sock->GET_INFO("SquidAsMasterPeer"));
	$SquidAsMasterPeerPort=intval($sock->GET_INFO("SquidAsMasterPeerPort"));
	$SquidAsMasterPeerPortSSL=intval($sock->GET_INFO("SquidAsMasterPeerPortSSL"));
	$SquidAsMasterPeerIPAddr=$sock->GET_INFO("SquidAsMasterPeerIPAddr");
	$SquidWCCPL3Enabled=intval($sock->GET_INFO("SquidWCCPL3Enabled"));
	$SquidWCCPL3ProxPort=intval($sock->GET_INFO("SquidWCCPL3ProxPort"));
	$SquidWCCPL3SSLProxPort=intval($sock->GET_INFO("SquidWCCPL3SSLProxPort"));
	$EnableSSLOnStandardPort=intval($sock->GET_INFO("EnableSSLOnStandardPort"));
	
	$MikrotikTransparent=intval($sock->GET_INFO('MikrotikTransparent'));
	$MikrotikHTTPSquidPort=intval($sock->GET_INFO('MikrotikHTTPSquidPort'));
	$MikrotikSSLTransparent=intval($sock->GET_INFO("MikrotikSSLTransparent"));
	$MikrotikHTTPSSquidPort=intval($sock->GET_INFO("MikrotikHTTPSSquidPort"));

	$SquidSSLUrgency=intval($sock->GET_INFO("SquidSSLUrgency"));
	
	$SquidHotSpotPort=intval($sock->GET_INFO("SquidHotSpotPort"));
	$EnableArticaHotSpot=intval($sock->GET_INFO("EnableArticaHotSpot"));
	$SquidHotSpotSSLPort=intval($sock->GET_INFO("SquidHotSpotSSLPort"));
	$SquidWCCPL3SSLEnabled=intval($sock->GET_INFO("SquidWCCPL3SSLEnabled"));
	$SquidWCCPL3SSServiceID=intval($sock->GET_INFO("SquidWCCPL3SSServiceID"));
	$SquidWCCPL3SSCertificate=intval($sock->GET_INFO("SquidWCCPL3SSCertificate"));
	$DisableSSLStandardPort=$sock->GET_INFO("DisableSSLStandardPort");
	if($SquidWCCPL3SSServiceID==0){$SquidWCCPL3SSServiceID=70;}
	if(!is_numeric($DisableSSLStandardPort)){$DisableSSLStandardPort=1;}
	if($EnableSSLOnStandardPort==1){$this->DisableSSLStandardPort=0;$DisableSSLStandardPort=0;}
	if($DisableSSLStandardPort==1){$EnableSSLOnStandardPort=0;}
	$SquidAllow80Port=intval($sock->GET_INFO("SquidAllow80Port"));
	
	if($SquidHotSpotPort==0){$SquidHotSpotPort=rand(40000, 64000);$sock->SET_INFO("SquidHotSpotPort", $SquidHotSpotPort);}
	if($SquidHotSpotSSLPort==0){$SquidHotSpotSSLPort=rand(40500, 64000);$sock->SET_INFO("SquidHotSpotSSLPort", $SquidHotSpotSSLPort);}
	if($SquidWCCPL3ProxPort==0){$SquidWCCPL3ProxPort=rand(40600, 64000);$sock->SET_INFO("SquidWCCPL3ProxPort", $SquidWCCPL3ProxPort);}
	if($SquidWCCPL3SSLProxPort==0){$SquidWCCPL3SSLProxPort=rand(40700, 64000);$sock->SET_INFO("SquidWCCPL3SSLProxPort", $SquidWCCPL3SSLProxPort);}
	
	$this->NETWORK_ALL_INTERFACES();
	if( (!is_numeric($this->MGR_LISTEN_PORT)) OR ($this->MGR_LISTEN_PORT==0)){
		$this->MGR_LISTEN_PORT=$sock->GET_INFO("SquidMgrListenPort");
		if(!is_numeric($this->MGR_LISTEN_PORT)){
			$this->MGR_LISTEN_PORT=rand(50000,64000);
			$sock->SET_INFO("SquidMgrListenPort", $this->MGR_LISTEN_PORT);
		}
	}


	$conf[]="# saved on ".date("Y-m-d H:i:s");
	$conf[]="#";
	
	

	if($SquidSSLUrgency==1){
		$conf[]="# # # SSL EMERGENCY MODE TURN OFF SSL # # #";
		
	}
	

	
	
	$conf[]="# --------- Manager Port...........: $this->MGR_LISTEN_PORT [".__LINE__."]";
	$conf[]="# --------- NAT compatible.........: $this->EnableTransparent27 [".__LINE__."]";
	$conf[]="# --------- HotSpot rev 3..........: $EnableArticaHotSpot HTTP Port $SquidHotSpotPort SSL Port $SquidHotSpotSSLPort [".__LINE__."]";
	$myportToKen=$this->myport_token();
	
	$this->build_progress("Checking HotSpot ports");
	$transparent_token=$this->transparent_token();
	
	if($EnableArticaHotSpot==1){
		$this->EnableSquidSSLCRTD=1;
		$ArticaSplashHotSpotCertificate=$sock->GET_INFO("ArticaSplashHotSpotCertificate");
		$conf[]="# --------- http port for Artica hotspot Certificate: $ArticaSplashHotSpotCertificate [".__LINE__."]\n";
		$conf[]="http_port $SquidHotSpotPort $transparent_token";
		$certificates=$this->BuildSquidCertificate($ArticaSplashHotSpotCertificate);
		
		if(count($GLOBALS["BuildSquidCertificate"])>0){
			while (list ($x, $y) = each ($GLOBALS["BuildSquidCertificate"])){
				$conf[]="# $y";
			}
		}
		
		$conf[]="https_port $SquidHotSpotSSLPort intercept ssl-bump $certificates";
		
		$build_http_debug_port=$this->build_http_debug_port();
		if($build_http_debug_port<>null){$conf[]=$build_http_debug_port;}
		$conf[]="http_port 0.0.0.0:$this->MGR_LISTEN_PORT name=MyManagerPort";
		$StandardPorts[]="acl NormalPorts myportname MyManagerPort";
		
		
		$this->TRANSPARENT_PORTS_LIST[]=$SquidHotSpotPort;
		$this->TRANSPARENT_PORTS_LIST[]=$SquidHotSpotSSLPort;
		
		$conf[]="acl MyTransparentPort $myportToKen $SquidHotSpotPort $SquidHotSpotSSLPort";
		
		$conf[]="#";
		$conf[]="#";
		
		@file_put_contents("/etc/squid3/listen_ports.conf",  @implode("\n",$conf)."\n");
		return "include /etc/squid3/listen_ports.conf\n";
		
	
	}
	
	$StandardPorts[]="acl NormalPorts myportname MyManagerPort";

	
	if( $this->EnableTransparent27==1){
		if($this->ICP_PORT==0){$this->ICP_PORT=3130;}
	}
	$this->build_progress("[WCCP]: Checking WCCP Layer 3");
	if($SquidWCCPL3Enabled==1){
		$conf[]="# --------- WCCP Layer 3...........: Port $SquidWCCPL3ProxPort SSL:$SquidWCCPL3SSLEnabled [".__LINE__."]";
		$this->TRANSPARENT_PORTS_LIST[]=$SquidWCCPL3ProxPort;
		$conf[]="http_port $SquidWCCPL3ProxPort $transparent_token";
		if($SquidWCCPL3SSLEnabled==1){
			$this->build_progress("[WCCP]: Checking WCCP Layer 3 SSL");
			$conf[]=$this->WCCPL3_SSL();
		}
		
	}
	

	$this->build_progress("Checking Mikrotik ports");
	if($MikrotikTransparent==1){
		$conf[]="# --------- http port for MikroTik [".__LINE__."]";
		$this->TRANSPARENT_PORTS_LIST[]=$MikrotikHTTPSquidPort;
		$conf[]="http_port 0.0.0.0:$MikrotikHTTPSquidPort $transparent_token";
		$conf[]="acl MikroTikHTTPPort $myportToKen $MikrotikHTTPSquidPort";
	}
	
	$build_http_debug_port=$this->build_http_debug_port();
	if($build_http_debug_port<>null){$conf[]=$build_http_debug_port;}
	$conf[]="http_port 0.0.0.0:$this->MGR_LISTEN_PORT name=MyManagerPort";
	

	if($MikrotikSSLTransparent==1){
		$MikrotikSSLTransparent_sslbump=$this->ssl_bump_line($this->certificate_center);
		$conf[]="# --------- http'S' port for MikroTik [".__LINE__."]";
		$this->TRANSPARENT_PORTS_LIST[]=$MikrotikHTTPSSquidPort;
		$conf[]="https_port 0.0.0.0:$MikrotikHTTPSSquidPort $transparent_token $MikrotikSSLTransparent_sslbump";
		$conf[]="acl MikroTikHTTPSPort $myportToKen $MikrotikHTTPSSquidPort";
		
	}
	
	if(!isset($GLOBALS["NETWORK_ALL_INTERFACES"])){
			$unix=new unix();
			$GLOBALS["NETWORK_ALL_INTERFACES"]=$unix->NETWORK_ALL_INTERFACES();
	}
	
	if(!isset($GLOBALS["NETWORK_ALL_NICS"])){
		$unix=new unix();
		$GLOBALS["NETWORK_ALL_NICS"]=$unix->NETWORK_ALL_INTERFACES();
	}	
	
	
	$sql="SELECT * FROM proxy_ports WHERE enabled=1";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){
		if(!is_file("/etc/squid3/listen_ports.conf")){@touch("/etc/squid3/listen_ports.conf");}
		return "include /etc/squid3/listen_ports.conf\n";
	}
	
	
	$conf[]="# --------- proxy_ports ". mysql_num_rows($results)." ports [".__LINE__."]";
	
	$StandardPorts=array();
	$TransparentPorts=array();
	$conf_acls=array();
	$ConfiguredPorts=0;
	
	$ParentSquid27Port=intval($sock->GET_INFO("ParentSquid27Port"));
	if($ParentSquid27Port==0){$ParentSquid27Port=13298;}
	
	$MIKROTIK_FIREWALL=array();
	
	$PORTS_DUPLICATE=array();
	$PROXY_PROTOCOL_ENABLED=false;
	$DCP=false;
	while ($ligne = mysql_fetch_assoc($results)) {
		if(!isset($ligne["WCCP"])){$ligne["WCCP"]=0;}
		if(!isset($ligne["MIKROTIK_PORT"])){$ligne["MIKROTIK_PORT"]=0;}
		if(!isset($ligne["FTP"])){$ligne["FTP"]=0;}
		if(!isset($ligne["WANPROXY"])){$ligne["WANPROXY"]=0;}
		if(!isset($ligne["Parent"])){$ligne["Parent"]=0;}
		if(!isset($ligne["ICP"])){$ligne["ICP"]=0;}
		if(!isset($ligne["nic"])){$ligne["nic"]=null;}
		if(!isset($ligne["FTP_TRANSPARENT"])){$ligne["FTP_TRANSPARENT"]=null;}
		if(!isset($ligne["NoAuth"])){$ligne["NoAuth"]=0;}
		if(!isset($ligne["ProxyProtocol"])){$ligne["ProxyProtocol"]=0;}
		
	
		$ProxyProtocol=$ligne["ProxyProtocol"];
		$ipaddr=$ligne["ipaddr"];
		$port=intval($ligne["port"]);
		$eth=$ligne["nic"];
		$http_port_text="http_port";
		$UseSSL=intval($ligne["UseSSL"]);
		$ICP=intval($ligne["ICP"]);
		$ssl_bump_line=null;
		$AS_TRANSPARENT=false;
		$transparent_text=null;
		$Parent=$ligne["Parent"];
		$WANPROXY=$ligne["WANPROXY"];
		$FTP_TRANSPARENT=intval($ligne["FTP_TRANSPARENT"]);
		$FTP=intval($ligne["FTP"]);
		if($UseSSL==1){$ProxyProtocol=0;}
		$ProxyProtocol_text=null;
		
		if($port==$this->MGR_LISTEN_PORT){
			$conf[]="# --------- ID {$ligne["ID"]} $port==$this->MGR_LISTEN_PORT ( not allowed )";
			continue;
		}
		
		$NoAuth=intval($ligne["NoAuth"]);
		if($FTP==1){continue;}
		$AclPortName="MyPortNameID{$ligne["ID"]}";
		
		if($ProxyProtocol==1){
			$ProxyProtocol_text=" require-proxy-header";
			$PROXY_PROTOCOL_ENABLED=true;
		}
		
		$ipaddr="0.0.0.0";
		$conf[]="# --------- ID {$ligne["ID"]} NIC:$eth/$port/Out:{$ligne["outgoing_addr"]} FTP=$FTP,FTP_TRANSPARENT=$FTP_TRANSPARENT,SquidAllow80Port=$SquidAllow80Port";
		if($SquidAllow80Port==0){
			if($port==80){
				$conf[]="# --------- -> 80 is not allowed -> abort[".__LINE__."]\n";
				continue;
			}
			if($port==443){
				$conf[]="# --------- -> 443 is not allowed -> abort[".__LINE__."]\n";
				continue;
			}

			if($port==21){
				$conf[]="# --------- -> 21 is not allowed -> abort[".__LINE__."]\n";
				continue;
			}

		}
		
		
		if($WANPROXY==1){
			
			$sql="SELECT ID,PortName FROM proxy_ports WHERE port='{$ligne["WANPROXY_PORT"]}'";
			$ligneCheckPort=@mysql_fetch_array($q->QUERY_SQL($sql));
			
			$IDCheckPort=intval($ligneCheckPort["ID"]);
			$conf[]="# --------- {$ligne["WANPROXY_PORT"]} owned by $IDCheckPort {$ligneCheckPort["PortName"]}";
			if($IDCheckPort==0){
				if(!isset($PORTS_DUPLICATE[$ligne["WANPROXY_PORT"]])){
					$conf[]="http_port 127.0.0.1:{$ligne["WANPROXY_PORT"]} name=$AclPortName";
					$PORTS_DUPLICATE[$ligne["WANPROXY_PORT"]]=true;
				}
			}
			continue;
		}
		
		if($FTP_TRANSPARENT==1){
			$sql="SELECT ID,PortName FROM proxy_ports WHERE port='{$ligne["WANPROXY_PORT"]}'";
			$ligneCheckPort=@mysql_fetch_array($q->QUERY_SQL($sql));
			if(!$q->ok){echo $q->mysql_error."\n";die();}
			$IDCheckPort=intval($ligneCheckPort["ID"]);
			$conf[]="# --------- {$ligne["WANPROXY_PORT"]} owned by $IDCheckPort {$ligneCheckPort["PortName"]}";
			if($IDCheckPort==0){
				if(!isset($PORTS_DUPLICATE[$ligne["WANPROXY_PORT"]])){
					$conf[]="http_port 127.0.0.1:{$ligne["WANPROXY_PORT"]} name=$AclPortName";
					$PORTS_DUPLICATE[$ligne["WANPROXY_PORT"]]=true;
				}
			}
			continue;
		}		
		
		if($ICP==1){
			if(isset($PORTS_DUPLICATE[$port])){continue;}
			$PORTS_DUPLICATE[$port]=true;
			$conf[]="icp_port $port";
			continue;
			
		}
		
		if($eth<>null){
			$ipaddr=$GLOBALS["NETWORK_ALL_NICS"][$eth]["IPADDR"];
			$conf[]="# --------- table proxy_ports $eth $ipaddr:$port [".__LINE__."]\n";
			if($ipaddr==null){
				$conf[]="# --------- table proxy_ports $eth $ipaddr:$port -> Hardware Error [".__LINE__."]\n";
				$conf[]="# --------- http $ipaddr -> Hardware Error -> 0.0.0.0 [".__LINE__."]\n";
				$ipaddr="0.0.0.0";
			}
		}
		
		if($ligne["transparent"]==1){
			if(isset($PORTS_DUPLICATE[$port])){continue;}
			$PORTS_DUPLICATE[$port]=true;
			$this->TRANSPARENT_PORTS_LIST[]=$port;
			$transparent_text=$transparent_token;
			$AS_TRANSPARENT=true;
			$TransparentPorts[]=$AclPortName;
		}
		
		if($ligne["TProxy"]==1){
			if(isset($PORTS_DUPLICATE[$port])){continue;}
			$PORTS_DUPLICATE[$port]=true;
			$this->TRANSPARENT_PORTS_LIST[]=$port;
			$transparent_text="tproxy";
			$AS_TRANSPARENT=true;
			$TransparentPorts[]=$AclPortName;
		}
		if($ligne["MIKROTIK_PORT"]==1){
			if(isset($PORTS_DUPLICATE[$port])){continue;}
			$PORTS_DUPLICATE[$port]=true;
			$this->TRANSPARENT_PORTS_LIST[]=$port;
			$transparent_text="tproxy";
			$AS_TRANSPARENT=true;
			$TransparentPorts[]=$AclPortName;
			$MIKROTIK_SRCPORT=80;
			if($UseSSL==1){$MIKROTIK_SRCPORT=443;}
			$MIKROTIK_FIREWALL[$ligne["ID"]]["PORT"]=$port;
			$MIKROTIK_FIREWALL[$ligne["ID"]]["SRC_PORT"]=$MIKROTIK_SRCPORT;
			$MIKROTIK_FIREWALL[$ligne["ID"]]["IPADDR"]=$ipaddr;
			$DCP=true;
		}
		
		
		if($ligne["WCCP"]==1){
			if(isset($PORTS_DUPLICATE[$port])){continue;}
			$PORTS_DUPLICATE[$port]=true;
			$this->TRANSPARENT_PORTS_LIST[]=$port;
			$transparent_text=$transparent_token;
			$AS_TRANSPARENT=true;
			$TransparentPorts[]=$AclPortName;
		}		
		
		if($ligne["is_nat"]==1){
			$conf_acls[]="acl MyPort{$ligne["ID"]} myportname $AclPortName";
			continue;
		}

		if($AS_TRANSPARENT){if($UseSSL==1){$http_port_text="https_port";}}
		
		
		if($UseSSL==1){
			if($SquidSSLUrgency==0){
				$conf[]="# Use certificate \"{$ligne["sslcertificate"]}\" [".__LINE__."]";
				$ssl_bump_line=$this->ssl_bump_line($ligne["sslcertificate"]);

				
				if(count($GLOBALS["BuildSquidCertificate"])>0){
					while (list ($x, $y) = each ($GLOBALS["BuildSquidCertificate"])){
						$conf[]="# $y";
					}
				}
			}
			
		}
		
		if(!$AS_TRANSPARENT){
			if($Parent==0){
				if($NoAuth==0){
					$this->MyPortsArray[]=$port;
					$StandardPorts[]="acl NormalPorts myportname $AclPortName";
					
				}
			}
		}
		
		if($NoAuth==1){
			$conf_acls[]="acl NoAuthPort myportname $AclPortName";
			
		}
		
		$ConfiguredPorts++;
		$conf[]="{$http_port_text} $ipaddr:$port $transparent_text{$ProxyProtocol_text} name=$AclPortName{$ssl_bump_line} ";
		$conf_acls[]="acl MyPort{$ligne["ID"]} myportname $AclPortName";
		if($ligne["outgoing_addr"]<>null){
			$conf_outgoing_addr[]="# {$ligne["outgoing_addr"]} = {$GLOBALS["NETWORK_ALL_NICS"][$ligne["outgoing_addr"]]["IPADDR"]}\n";
			$tcp_outgoing_address=$GLOBALS["NETWORK_ALL_NICS"][$ligne["outgoing_addr"]]["IPADDR"];
			
			if($tcp_outgoing_address<>"0.0.0.0"){
				$conf_outgoing_addr[]="tcp_outgoing_address $tcp_outgoing_address MyPort{$ligne["ID"]} all";
			}else{
				$conf_outgoing_addr[]="# FATAL {$ligne["outgoing_addr"]}/$tcp_outgoing_address does not exists";
			}
			
		}
		
		
	}
	
	if($this->EnableTransparent27==1){
		$ParentSquid27Port=intval($sock->GET_INFO("ParentSquid27Port"));
		if($ParentSquid27Port==0){$ParentSquid27Port=13298;}
		$conf[]="http_port 127.0.0.1:$ParentSquid27Port name=Squid27Port";
		$TransparentPorts[]="Squid27Port";
		
	}
	
	
	
	
	if($this->SquidBubbleMode==1){
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM webfilters_sqaclsports WHERE enabled=1 ORDER BY aclport";
		$results = $q->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {
			$aclport=$ligne["aclport"];
			$Interface=$ligne["interface"];
			if($Interface==null){$Interface="0.0.0.0";}
			$ConfiguredPorts++;
			$AclPortName="MyBublePortName{$ligne["ID"]}";
			$StandardPorts[]="acl NormalPorts myportname $AclPortName";
			$conf[]="http_port {$Interface}:$aclport name=$AclPortName\n";
		}
	
	}

	
	$conf[]="# Configured ports = $ConfiguredPorts [".__LINE__."]";
	if($ConfiguredPorts==0){
		$conf[]="# No Configured port assume 3128 and 8080 by default [".__LINE__."]";
		$conf[]="http_port 0.0.0.0:3128 name=Port3128";
		$conf[]="http_port 0.0.0.0:8080 name=Port8080";
		$StandardPorts[]="acl NormalPorts myportname Port3128";
		$StandardPorts[]="acl NormalPorts myportname Port8080";
	}
	
	
	$conf[]="# --------- ICP Port: $this->ICP_PORT [".__LINE__."]";
	if($this->ICP_PORT>10){
		if($this->HTCP_PORT>10){
			if($this->ICP_PORT==$this->HTCP_PORT){$this->ICP_PORT=$this->ICP_PORT+1;}
			}
			$conf[]="icp_port $this->ICP_PORT";
		}
		if($this->HTCP_PORT>10){
		
		$conf[]="htcp_port $this->HTCP_PORT"; // should 4827
		}	
		
		
	$conf[]="# Standards ports count:".count($StandardPorts);
	if(count($StandardPorts)==0){
		$StandardPorts[]="acl NormalPorts myportname MyManagerPort";
	}
	
	$conf[]=@implode("\n", $StandardPorts);
		
	if(count($TransparentPorts)>0){
		$conf[]="acl MyTransparentPort myportname ".@implode(" ", $TransparentPorts);
	}	

	$conf[]=@implode("\n", $conf_acls);
	$conf[]=@implode("\n", $conf_outgoing_addr);
	
	if($DCP){
		$ayDscp = array(0 => '0x00',8 => '0x20',10 => '0x28',12 => '0x30',14 => '0x38',16 => '0x40',18 => '0x48',20 => '0x50',22 => '0x58',24 => '0x60',26 => '0x68',28 => '0x70',30 => '0x78',32 => '0x80',34 => '0x88',36 => '0x90',38 => '0x98',40 => '0xA0',46 => '0xB8',48 => '0xC0',56 => '0xE0');
		$SquidMikroTikTOS=intval($sock->GET_INFO("SquidMikroTikTOS"));
		if($SquidMikroTikTOS==0){$SquidMikroTikTOS=12;}
		$conf[]="qos_flows tos local-hit={$ayDscp[$SquidMikroTikTOS]}";
	}
	
	
	if($PROXY_PROTOCOL_ENABLED){
		$conf[]="proxy_protocol_access allow all";
	}
	
	
	$conf[]="#";
	$conf[]="#";
	
	@file_put_contents("/etc/squid3/listen_ports.conf",  @implode("\n",$conf)."\n");
	@file_put_contents("/etc/squid3/MIKROTIK_FIREWALL.array", serialize($MIKROTIK_FIREWALL));
	return "include /etc/squid3/listen_ports.conf\n";
	
	
}

public function dns_nameservers($dnsmasq=false){
	$sock=new sockets();
	$noresol=false;
	$IpClass=new IP();
	$EnableDNSMASQ=$sock->dnsmasq_enabled();
	$balance_on_multiple_ip=intval($sock->GET_INFO("balance_on_multiple_ip"));
	$balance_on_multiple_ip_text=null;
	if($balance_on_multiple_ip==1){$balance_on_multiple_ip_text="\nbalance_on_multiple_ip on\n";}
	
	$SquidDNSUseSystem=intval($sock->GET_INFO("SquidDNSUseSystem"));
	$SquidDNSUseLocalDNSService=intval($sock->GET_INFO("SquidDNSUseLocalDNSService"));
	
	if($SquidDNSUseSystem==1){return null;}
	if($SquidDNSUseLocalDNSService==0){$EnableDNSMASQ=0;}
	
	writelogs("dns server: ".count($this->dns_array) . ' rows',__CLASS__.'/'.__FUNCTION__,__FILE__);
	
	$dns_nameservers=array();
	if($EnableDNSMASQ==1){
		if($GLOBALS["VERBOSE"]){echo " ***** DNS: add 127.0.0.1 at the top\n";}
			$dns_nameservers[]="127.0.0.1";
		}
	
	
	if(count($this->dns_array)==0){
		$dns_nameservers_from_resolv=$this->dns_nameservers_from_resolv(true);
		while (list ($num, $val) = each ($dns_nameservers_from_resolv) ){
			$dns_nameservers[]=$val;
		}
		$noresol=true;
	}
	

		
	if(count($this->dns_array)>0){
		reset($this->dns_array);
		if($GLOBALS["VERBOSE"]){echo " ***** DNS: start loop ".count($this->dns_array) ." items\n";}
		while (list ($num, $val) = each ($this->dns_array) ){
			if(preg_match("#resolv#", $val)){
				if(!$noresol){
					$dns_nameservers_from_resolv=$this->dns_nameservers_from_resolv(true);
					while (list ($num, $val) = each ($dns_nameservers_from_resolv) ){
						$dns_nameservers[]=$val;
					}
				}
				continue;
			}
			if($GLOBALS["VERBOSE"]){echo " ***** DNS: $val\n";}
			if(!$IpClass->isIPAddress($val)){continue;}
			$dns_nameservers[]=$val;
		}
	}else{
		if($GLOBALS["VERBOSE"]){echo " ***** DNS: dns_array 0 items\n";}
	}
	
	while (list ($num, $val) = each ($dns_nameservers) ){
		if(!$IpClass->isIPAddress($val)){continue;}
		$CLEAN[$val]=$val;
	}
	
	$dns_nameservers=array();
	while (list ($num, $val) = each ($CLEAN) ){
		if(!$IpClass->isIPAddress($val)){continue;}
		$dns_nameservers[]=$num;
	}
	
	
	
	if(count($dns_nameservers)>0){
		return "dns_nameservers " . @implode(" ", $dns_nameservers) ."\n" .$balance_on_multiple_ip_text;
	}	
}

		
function BuildSquidConf(){
		$sock=new sockets();
		$this->CheckVersion();
		$artica_meta=new squid_meta();
		if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
		$users=$GLOBALS["CLASS_USERS"];
		$conf=null;
		$acl_access_time=null;
		$this->transparent=null;
		$this->second_port_transparent=null;
		$http_access_time=null;
		
		$prefixacls=null;
		$acl_groups=array();
		$ws=new squidworkers();
		$this->SquidBinIpaddr=trim($sock->GET_INFO("SquidBinIpaddr"));
		$EnableSplashScreenAsObject=$sock->GET_INFO("EnableSplashScreenAsObject");
		$SquidBoosterMem=$sock->GET_INFO("SquidBoosterMem");
		$SquidBoosterMemK=$sock->GET_INFO("SquidBoosterMemK");
		$SquidBoosterOnly=$ws->zcheck_squidbooster_value();
		$SquidCacheLevel=$sock->GET_INFO("SquidCacheLevel");
		$SquidClientPersistentConnections=intval($sock->GET_INFO("SquidClientPersistentConnections"));
		$SquidServerPersistentConnections=intval($sock->GET_INFO("SquidServerPersistentConnections"));
		if(!is_numeric($SquidCacheLevel)){$SquidCacheLevel=4;}
		$SquidUploadTimeouts=intval($sock->GET_INFO("SquidUploadTimeouts"));
		$SquidConnectRetries=intval($sock->GET_INFO("SquidConnectRetries"));
		$SquidNetworkSwitch=trim($sock->GET_INFO("SquidNetworkSwitch"));
		$EnableLogHostnames=intval($sock->GET_INFO("EnableLogHostnames"));
		
		$this->DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
		$this->EnableCNTLM=$sock->GET_INFO("EnableCNTLM");
		$this->CnTLMPORT=$sock->GET_INFO("CnTLMPORT");
		$this->ArticaHotSpotPort=$sock->GET_INFO("ArticaHotSpotPort");
		$this->ArticaSSLHotSpotPort=$sock->GET_INFO("ArticaSSLHotSpotPort");
		if(!is_numeric($this->DisableAnyCache)){$this->DisableAnyCache=0;}
		if(!is_numeric($SquidBoosterMem)){$SquidBoosterMem=0;}
		if(!is_numeric($SquidBoosterMemK)){$SquidBoosterMemK=50;}
		if(!is_numeric($this->max_filedesc)){$this->max_filedesc=8192;}
		if(!is_numeric($this->EnableCNTLM)){$this->EnableCNTLM=0;}
		if(!is_numeric($EnableSplashScreenAsObject)){$EnableSplashScreenAsObject=0;}
		if($this->isNGnx()){$this->SquidActHasReverse=0;}
		if(!is_numeric($this->CnTLMPORT)){$this->CnTLMPORT=0;}
		if(!is_numeric($this->ArticaHotSpotPort)){$this->ArticaHotSpotPort=0;}
		if($this->CnTLMPORT==0){$this->CnTLMPORT=rand(35000, 64000);$sock->SET_INFO("CnTLMPORT", $this->CnTLMPORT);}
		if($this->ArticaHotSpotPort==0){$this->ArticaHotSpotPort=rand(38000, 64000);$sock->SET_INFO("ArticaHotSpotPort", $this->ArticaHotSpotPort);}
		if($this->ArticaSSLHotSpotPort==0){$this->ArticaSSLHotSpotPort=rand(38500, 64000);$sock->SET_INFO("ArticaSSLHotSpotPort", $this->ArticaSSLHotSpotPort);}
		$SquidAsMasterCacheChilds=$sock->GET_INFO("SquidAsMasterCacheChilds");
		$SquidAsMasterPeer=intval($sock->GET_INFO("SquidAsMasterPeer"));
		$SquidAsMasterPeerPort=intval($sock->GET_INFO("SquidAsMasterPeerPort"));
		$SquidAsMasterPeerPortSSL=intval($sock->GET_INFO("SquidAsMasterPeerPortSSL"));
		if(!is_numeric($SquidAsMasterCacheChilds)){$SquidAsMasterCacheChilds=1;}
		$SquidAsMasterFollowxForward=intval($sock->GET_INFO("SquidAsMasterFollowxForward"));
		$UseSimplifiedCachePattern=$sock->GET_INFO("UseSimplifiedCachePattern");
		if(!is_numeric($UseSimplifiedCachePattern)){$UseSimplifiedCachePattern=1;}
		
		$EnableMalwarePatrol=intval($sock->GET_INFO("EnableMalwarePatrol"));
		if(!is_numeric($EnableMalwarePatrol)){$EnableMalwarePatrol=0;}
		// Disable Malware Patrol.... Patch 2012-11-20
		$EnableMalwarePatrol=0;
		
		if($SquidCacheLevel==0){$this->DisableAnyCache=1;}
		if($this->SquidUrgency==1){
			$SquidCacheLevel=0;
			$this->DisableAnyCache=1;
		}

		
		$this->listen_port=intval(trim($this->listen_port));
		if($this->enable_dansguardian==1){$this->listen_port=$this->alt_listen_port;}
		
		if($this->hasProxyTransparent==1){
			if($this->EnableTransparent27==0){
				$this->transparent=$this->transparent_token();
				$this->second_listen_portForTransparent=$this->listen_port+1;
				if($this->second_listen_portForTransparent==$this->ssl_port){$this->second_port_transparent=$this->ssl_port+1;}
				$this->second_port_transparent="http_port $this->SquidBinIpaddr$this->second_listen_portForTransparent";
				$this->MyPortsArray[$this->second_listen_portForTransparent]=$this->second_listen_portForTransparent;
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." Squid : Transparent: Adding a second port $this->second_listen_portForTransparent\n";}
				$this->TRANSPARENT_PORTS_LIST[]=$this->listen_port;
			}
		}
		
		
		
		
		$compilation_params=$this->compilation_params();
		if(!isset($compilation_params["enable-arp-acl"])){
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [VER]: --enable-arp-acl no such token disable MAC features..\n";}
			$this->EnableMacAddressFilter=0;
		}
	
		
		if($this->DisableAnyCache==1){$SquidBoosterMem=0;}
		if($SquidBoosterMem>0){$SquidBoosterMemK=$SquidBoosterMemK*1024;$this->store_dir_minsize="min-size=$SquidBoosterMemK";}
		if($this->EnableUserAgentBanAll==1){$conf=$conf. "acl AllowedBrowsers browser \"/etc/squid3/allowed-user-agents.acl\"\n"; }
		
		if($this->ASROOT){
			$articaver=trim(@file_get_contents("/usr/share/artica-postfix/VERSION"));
			echo "Starting......: ".date("H:i:s")." Squid Version $this->SQUID_BIN_VERSION ($this->SQUID_VERSION_CALC) / Artica Version $articaver\n";
			$conf=$conf . "# Squid version $this->SQUID_BIN_VERSION ($this->SQUID_VERSION_CALC)\n";
			$conf=$conf.@implode("\n", $this->SQUID_VERSIONS_LOGS)."\n";
			$conf=$conf . "# Builded by Artica v.$articaver\n";
			$conf=$conf . "# Date: ".date("Y-m-d H:i:s")."\n";
			if($this->IS_30){if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." Squid : is 3.x OK\n";}}
			if(!$this->IS_32){if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." Squid : is NOT 3.2.x OK\n";}}
			if($this->IS_32){if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." Squid : is 3.2.x OK\n";}}
			if($this->IS_33){if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." Squid : is 3.3.x OK\n";}}
			if($this->IS_34){if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." Squid : is 3.4.x OK\n";}}
			if($this->IS_35){if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." Squid : is 3.5.x OK\n";}}
		}
		
		$conf=$conf . "# $this->IS_VERS_LOGS\n";
		if($this->IS_30){$conf=$conf . "# IS 3.0 YES\n";}
		if($this->IS_32){$conf=$conf . "# IS 3.2 YES\n";}
		if($this->IS_31){$conf=$conf . "# IS 3.1 YES\n";$this->store_dir_minsize=null;}
		if($this->IS_33){$conf=$conf . "# IS 3.3 YES\n";}  
		if($this->IS_34){$conf=$conf . "# IS 3.4 YES\n";}
		if($this->IS_35){$conf=$conf . "# IS 3.5 YES\n";}
		
		if($this->SquidActHasReverse<>1){
			if($this->SquidDisableAllFilters==0){
				$this->build_progress("Checks Webfiltering");
				$squidGuard=$this->squidGuard();
			}
		}
		
		if(!$this->IS_31){
			$conf=$conf . "#Squid version is under 3.1x or 3.2x version...\n";
			if($this->enable_kavproxy==1){
				if($this->enable_cicap==1){
					$conf=$conf . "#C-ICAP was removed, this squid version did not accept more that 1 ICAP server\n";
					$this->enable_cicap=0;
		}}}else{
			if($this->enable_kavproxy==1){$this->ICAP_SERVICES_COUNT=$this->ICAP_SERVICES_COUNT+1;}
			if($this->enable_cicap==1){$this->ICAP_SERVICES_COUNT=$this->ICAP_SERVICES_COUNT+1;}
		}
		

		
	if($this->SquidActHasReverse==1){
			$conf=$conf."\n# -> Reverse Proxy is enabled <-\n";
			$conf=$conf."include /etc/squid3/reverse.conf\n";
			if(!is_file("/etc/squid3/reverse.conf")){@file_put_contents("/etc/squid3/reverse.conf", "#\n");}
			if($this->SquidReverseDefaultWebSite<>null){$defaultWWW=" defaultsite=$this->SquidReverseDefaultWebSite ";}
			$this->transparent=" accel vhost vport $defaultWWW";
			$this->listen_port=intval(trim($sock->GET_INFO("SquidActHasReverseListenPort")));
			if($this->listen_port<3){$this->listen_port=80;}
			
			
			if($this->ASROOT){
				if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
				$nginx=$unix->find_program("nginx");
				if(is_file($nginx)){
					$sock=new sockets();
					$DisableNginxProxy=$sock->GET_INFO("DisableNginxProxy");
					if(!is_numeric($DisableNginxProxy)){$DisableNginxProxy=0;}
					if($DisableNginxProxy==0){
						echo "Starting......: ".date("H:i:s")." [REVERSE]: Nginx is installed and enabled...\n";
						if($this->listen_port==80){$this->listen_port=8080;}
						if($this->ssl_port==443){$this->ssl_port=4443;}
					}
				}
			}			
			
			
			$conf=$conf.$this->squid_reverse_websites();
		}		
		

		$this->build_progress("Checks SSL ports");
		$conf=$conf . "#--------- --------- --------- --------- --------- ---------\n";
		$conf=$conf.$this->build_http_ports();
		$conf=$conf."# --------- NormalPorts ".count($this->MyPortsArray)." Item(s) [".__LINE__."]\n";
		while (list ($port, $ligne) = each ($this->MyPortsArray) ){if(!is_numeric($port)){continue;}$d[]=$port;}
		$StandardPorts=@implode(" ", $d);
		$conf=$conf . "#--------- --------- --------- --------- --------- ---------\n";
		
		// Local port ( without transparent mode )
		
		
		if($this->hasProxyTransparent==0){
			
			$this->MyPortsArray[$this->listen_port]=$this->listen_port;
			$this->MyPortsArray[$this->get_ssl_port()]=true;
		}
		if($this->EnableTransparent27==1){
			$this->MyPortsArray[$this->listen_port]=$this->listen_port;
			$this->MyPortsArray[$this->get_ssl_port()]=true;			
		}
		
		
		$this->MyStandPort=null;
		$d=array();
		$token=$this->myport_token();

		
		$conf=$conf . "#--------- Authentication methods (v 2014 - Dec 9) --------- --------- ---------\n";
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [AUTH] Building NTLM Settings\n";}
		
		$this->build_progress("Checks NTLM");
		$ntlm_auth_conf=$this->ntlm_auth_conf();
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [AUTH] Building NTLM  = ".strlen($ntlm_auth_conf)." bytes\n";}
		if(strlen($ntlm_auth_conf)>0){$conf=$conf . $ntlm_auth_conf;}
		
		

		
		$this->build_progress("Checks LDAP");
		$conf=$conf . $this->ldap_auth_conf();
		$conf=$conf . $this->auth_param_basic(true);
		$conf=$conf . "#--------- --------- --------- --------- --------- ---------\n";
		

		$this->build_progress("Checks Externals ACLS");
		
		$conf=$conf."include /etc/squid3/external_acls.conf\n";
		$external_acls_squid=new external_acls_squid();
		$external_acls_squid->Build();
		
		
		$kav_acl="acl acl_kav_GET method GET\n";
		$conf=$conf. "acl FTP proto FTP\n";
		$conf=$conf. "acl GET method GET\n";
		if($this->IS_27){$conf=$conf."acl all src 0.0.0.0/0\n";}
		$conf=$conf."acl localhost src 127.0.0.1/32\n";
		$conf=$conf."acl to_localhost dst 127.0.0.0/8\n";
		$conf=$conf."acl internal_icons urlpath_regex −i /squid-internal-static/\n";
		$conf=$conf."http_access allow internal_icons\n";
		$conf=$conf."acl ToArticaWWW dstdomain .artica.fr .articatech.net .articatech.com\n";
		
		if(!is_file("/etc/squid3/ip-blacklists.db")){@touch("/etc/squid3/ip-blacklists.db");}
		if(!is_file("/etc/squid3/ChildsProxy.conf")){@touch("/etc/squid3/ChildsProxy.conf");}
		
		if(!is_file("/etc/squid3/url_rewrite_program.deny.db")){@touch("/etc/squid3/url_rewrite_program.deny.db");}
		$this->build_progress("Checks backends proxys");
		$squid_childs=new squid_childs();
		$squid_childs->build();
		
		
		$conf=$conf ."include /etc/squid3/ChildsProxy.conf\n";
		$conf=$conf .$this->ACL_MyLocalIpsDest();"\n";
		
		
		
		if($this->smartphones_port>0){
			$conf=$conf . "acl MyMobilesPort $token $this->smartphones_port\n";
		}
		
		
		$isp_networks=$this->isp_networks();
		if($isp_networks<>null){$conf=$conf."$isp_networks\n";}
		$this->build_progress("Checks ACLs");
		$kav_acl=$this->acls_multimedias();
		if($this->SquidDisableAllFilters==0){
			$acl_blocked_sites="acl blockedsites url_regex \"/etc/squid3/squid-block.acl\"\n";
		}
		$conf=$conf.$artica_meta->build_acls();
		$conf=$conf.$this->ACLS_DROPBOX()."\n";
		$conf=$conf.$this->ACLS_SKYPE()."\n";
		$conf=$conf.$this->ACLS_OFFICE365()."\n";
		$conf=$conf.$this->ACLS_GOOGLE()."\n";
		$conf=$conf.$this->ACLS_BROWSERS()."\n";
		$squid_sessions_builder=$this->squid_sessions_builder();
	


		


		//$conf=$conf . $this->krb_auth_conf();
		if(isset($GLOBALS["ACL_AUTHENTICATED"])){
			$conf=$conf.$GLOBALS["ACL_AUTHENTICATED"]."\n";
		}
		

		
		
		
		
		$conf=$conf.  $this->external_acl_helper_splash_screen();
		$conf=$conf.  $this->external_acl_helper_timequota();
		$conf=$conf . $this->external_acl_helper_quota();
		
		$conf=$conf . "#--------- Bubble Mode ($this->SquidBubbleMode)\n";
		
		if($this->SquidBubbleMode==1){
			$conf=$conf . "acl MyStandPort $token $StandardPorts\n";
			$this->MyStandPort=" MyStandPort ";
			$q=new mysql_squid_builder();
			$sql="SELECT * FROM webfilters_sqaclsports WHERE enabled=1 ORDER BY aclport";
			$results = $q->QUERY_SQL($sql);
			while ($ligne = mysql_fetch_assoc($results)) {
				$aclport=$ligne["aclport"];
				$conf=$conf."acl MyPort{$ligne["aclport"]} $token $aclport\n";
			}	
		}
		
		if($squid_sessions_builder<>null){$conf=$conf . $squid_sessions_builder."\n";}
		
		
		
		
		$conf=$conf.$this->tweeks_performances();
		

		$conf=$conf . "error_directory /usr/share/squid-langpack/templates\n";
		if($this->max_filedesc>1024 ){$conf=$conf . "max_filedesc $this->max_filedesc\n";}
		
		

		
		$conf=$conf . "#--------- User defined if /etc/squid3/myconf.conf exists [".__LINE__."]\n";
		if(is_file("/etc/squid3/myconf.conf")){
			@chown("/etc/squid3/myconf.conf", "squid");
			$conf=$conf."include /etc/squid3/myconf.conf\n";
		}

		
		
		if($this->SquidDisableAllFilters==0){
			$GLOBALS["HTTP_ACCESS"]["BLOCKEDSITES"]="http_access deny {$this->MyStandPort}blockedsites";
		}
		
		$conf=$conf.$this->mgr_port();
		// UfdbGuard web filter section
		$conf=$conf.$squidGuard."\n";
		if(!$this->IS_27){
			if($this->url_rewrite_bypass==1){$conf=$conf."url_rewrite_bypass on\n";}else{$conf=$conf."url_rewrite_bypass off\n";}
		}
		
		
		$conf=$conf. "acl CONNECT method CONNECT\n";
		$conf=$conf. "acl purge method PURGE\n";
		$conf=$conf . "#\n";
		$conf=$conf  ."\n#--------- acls\n";
		$conf=$conf  .$acl_blocked_sites;
		
		

		
		
		$ACL_identd_server=$this->ACL_identd_server();
		
		if($ACL_identd_server<>null){$conf=$conf. $ACL_identd_server."\n";}

		
			
		if(trim($users->SQUID_LDAP_AUTH)==1){
				if(is_array($this->acl_times)){
					$conf=$conf."#--------- TIME RESTRICTON\n";
					reset($this->acl_times);
					while (list ($num, $val) = each ($this->acl_times) ){
						if(!preg_match("#time:([0-9]+):(.+)#",$num,$re)){continue;}
							$re[2]=trim($re[2]);
							while (list ($a, $b) = each ($val)){
								$c[]="$a $b"; 
							}
							$conf=$conf."acl {$re[1]}_{$re[2]}_time time ". implode(" " ,$c)."\n";
							$http_access_time_array[]="http_access deny {$this->MyStandPort}ldap_{$re[1]}_{$re[2]} !{$re[1]}_{$re[2]}_time";
							
								if($this->SquidLdapAuthEnableGroups==1){
									$acgroup="acl ldap_{$re[1]}_{$re[2]} external ldap_group {$re[1]}";
									$acl_groups[$acgroup]=$acgroup;
								}
							
							}
							
					if(is_array($http_access_time_array)){
						$http_access_time=implode("\n",$http_access_time_array)."\n";
					}
				}
		}

		$conf=$conf . $kav_acl;
		$conf=$conf . "\n".$this->SQUID_LOCAL_NETWORKS()."\n";
		
	
		if($this->LDAP_AUTH==1){
			if($this->EnableKerbAuth==0){
				$conf=$conf ."# LDAP_AUTH =1  EnableKerbAuth = 0 SquidLdapAuthEnableGroups=$this->SquidLdapAuthEnableGroups\n";
				if($this->SquidLdapAuthEnableGroups==1){
					$conf=$conf ."acl group_password external ldap_group\n";
				}
			}
			
			if(count($acl_groups)>0){
				while (list ($a, $b) = each ($acl_groups)){
					$gprs[]=$b;
				}
				$conf=$conf ."\n#--------- GROUPS definition\n";
				$conf=$conf .implode("\n",$gprs);
			}else{
				$conf=$conf ."\n#--------- GROUPS definition\n";
				$conf=$conf ."#no groups";
			}
		}
		
		$conf=$conf."\n\n#--------- http_access: MAIN RULES... [".__LINE__."]\n";
		
		if($this->ProxyDedicateMicrosoftRules==1){
			$conf=$conf . "#--------- On TOP: Microsoft dedicated rules [".__LINE__."]\n";
			$this->MICROSOFT_ACLS();
			@chown("/etc/squid3/microsoft.conf", "squid");
			$conf=$conf."include /etc/squid3/microsoft.conf\n";
		}else{
			$conf=$conf . "#--------- http_access: Microsoft dedicated rules are disabled: `$this->ProxyDedicateMicrosoftRules` [".__LINE__."]\n";
		}
		
		
		
		

		$conf=$conf."\n\n#--------- http_access: on TOP: Authentication rules...\n";
		$NormalPorts=" NormalPorts ";
		if($this->MyStandPort<>null){$NormalPorts=$this->MyStandPort;}
		$MyMobilesPort=null;

		if($this->smartphones_port>0){$MobilePhones="!MyMobilesPort ";}
		
		if($this->LDAP_AUTH==1){
				$acl_access_time=$http_access_time;
				if($this->EnableKerbAuth==0){
					if($this->SquidLdapAuthEnableGroups==1){
						$GLOBALS["HTTP_ACCESS"]["LDAP_GROUP"]="http_access deny $NormalPorts!group_password";
					}
				}
				
				$GLOBALS["HTTP_ACCESS"]["LDAP_AUTH"]="http_access deny $NormalPorts!ldapauth\n";
		}
		
		if($this->NTLM_AUTH==1){
				$acl_access_time=$http_access_time;
				$GLOBALS["HTTP_ACCESS"]["LDAP_AUTH"]="http_access deny $NormalPorts!ntlm_users all\n";
				@mkdir("/var/cache/samba/winbindd_privileged",0750,true);
				$office_network=null;
		}

		if($this->SquidEnableIdentdServiceOnly==1){
			$GLOBALS["HTTP_ACCESS"]["IDENT_SERVICE"]="http_access deny $NormalPorts\nhttp_access deny $NormalPorts!identhosts all\n";
			
		}
		

		if($this->EnableKerbAuth==1){
			if($this->SquidUrgency==0){
				if($this->ActiveDirectoryEmergency==0){
					$acl_access_time=$http_access_time;
					$GLOBALS["HTTP_ACCESS"]["LDAP_AUTH"]="http_access deny $NormalPorts!AUTHENTICATED all\n";
					$office_network=null;
				}
			}
		}

		if($this->EnableKerberosAuthentication==1){
			if($this->SquidUrgency==0){
				if($this->ActiveDirectoryEmergency==0){
				$acl_access_time=$http_access_time;
				$GLOBALS["HTTP_ACCESS"]["LDAP_AUTH"]="http_access deny $NormalPorts\nhttp_access deny $NormalPorts!AUTHENTICATED all\n";
				$office_network=null;	
				}
			}		
		}
		

		
		if($this->SquidActHasReverse<>1){
			if($this->enable_dansguardian==1){
				$office_network=null;
			}
		}		
		


		
	
		
		$conf=$conf."\n# --------- SAFE ports\n";
		$conf=$conf.$this->SAFE_PORTS();
		

		$conf=$conf."#\n";

		/*if($EnableMalwarePatrol==1){
			if($this->SquidDisableAllFilters==0){
				//$GLOBALS["HTTP_ACCESS"]["MALWARE_PATROL"]="http_access deny malware_block_list";
			}
		}*/
		
		if(preg_match("#([0-9]+)#",$this->global_conf_array["request_header_max_size"],$re)){
				$request_header_max_size=$re[1];
				if(!is_numeric($request_header_max_size)){$request_header_max_size=0;}
				if($request_header_max_size>0){
					if($request_header_max_size<256){$request_header_max_size=256;}
					
				}
		}
		
	if(preg_match("#([0-9]+)#",$this->global_conf_array["client_request_buffer_max_size"],$re)){
				$client_request_buffer_max_size=$re[1];
				if(!is_numeric($client_request_buffer_max_size)){$client_request_buffer_max_size=512;}
				if($client_request_buffer_max_size>0){
					if($client_request_buffer_max_size<512){$client_request_buffer_max_size=512;}
				}
		}		
		
		
		
		
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." Squid request header max size {$this->global_conf_array["request_header_max_size"]} $request_header_max_size:KB\n";}			
		
		if(preg_match("#([0-9]+)#",$this->global_conf_array["reply_body_max_size"],$re)){
				$reply_body_max_size=$re[1];
				if($request_header_max_size==0){$reply_body_max_size=0;}
				if(!is_numeric($reply_body_max_size)){$reply_body_max_size=0;}
				if($reply_body_max_size<$request_header_max_size){$reply_body_max_size=$reply_body_max_size*2;}
			}
		
		if(preg_match("#([0-9]+)#",$this->global_conf_array["request_body_max_size"],$re)){
			$request_body_max_size=$re[1];
			if(!is_numeric($request_body_max_size)){$request_body_max_size=0;}			
		}
		if(preg_match("#([0-9]+)#",$this->global_conf_array["reply_header_max_size"],$re)){
			$reply_header_max_size=$re[1];
			if(!is_numeric($reply_header_max_size)){$reply_header_max_size=64;}			
		}
		
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." Squid maximum object size in memory {$this->global_conf_array["maximum_object_size_in_memory"]}\n";}
		
		
		
		if(preg_match("#([0-9]+)#",$this->global_conf_array["minimum_object_size"],$re)){
			$minimum_object_size=$re[1];
			if(preg_match("#([A-Z]+)#",$this->global_conf_array["minimum_object_size"],$re)){$minimum_object_size_unit=$re[1];}
			if($minimum_object_size_unit==null){$minimum_object_size_unit="KB";}
			if(!is_numeric($minimum_object_size)){$minimum_object_size=0;}
		}		
		
		
		
		if(preg_match("#([0-9]+)#",$this->global_conf_array["maximum_object_size"],$re)){
			$maximum_object_size=$re[1];
			if(preg_match("#([A-Z]+)#",$this->global_conf_array["maximum_object_size"],$re)){$maximum_object_size_unit=$re[1];}
			if($maximum_object_size_unit==null){$maximum_object_size_unit="KB";}
			if($maximum_object_size_unit=="KB"){
				if($maximum_object_size<4096){$maximum_object_size=4096;}
			}
				if($maximum_object_size_unit=="MB"){
					if($maximum_object_size<4){$maximum_object_size=4;}
				}
		}		
		
		
		$conf=$conf. $this->SSL_PORTS();
		if($this->ignore_expect_100==1){$ignore_expect_100="on";}else{$ignore_expect_100="off";}
		
		$request_header_access=$this->request_header_access();
		$conf=$conf ."# --------- Change HTTP headers:\n";
		if($request_header_access<>null){$conf=$conf."$request_header_access\n";}

		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ LB ]: checks declared Load-balancers...\n";}
		$x_forwarded_for=$this->x_forwarded_for();
		$conf=$conf ."# --------- X-Forwarded-For:\n";
		if($x_forwarded_for<>null){$conf=$conf .$x_forwarded_for."\n";}else{
			$conf=$conf ."# --------- X-Forwarded-For: No entry\n";
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ LB ]: NULL Rules...\n";}
		}
		
		$conf=$conf .$acl_access_time;
		
		$conf=$conf .$this->ACL_FTP_RESTRICTIONS();
		
		if($this->ACL_ARP_ENABLED){
			$conf=$conf ."\nacl whitelisted_mac_computers arp \"/etc/squid3/whitelisted-computers-by-mac.acl\"\n";
		}
		$ACL_TEMPLATES_URIS=$this->ACL_TEMPLATES_URIS();
		$ACL_HOTSPOT_URIS=$this->ACL_HOTSPOT_URIS();
		
		
		
		
		if($ACL_TEMPLATES_URIS<>null){$conf=$conf ."\n$ACL_TEMPLATES_URIS\n";}
		if($ACL_HOTSPOT_URIS<>null){$conf=$conf ."\n$ACL_HOTSPOT_URIS\n";}		
		
		if($this->SquidDisableAllFilters==0){
			$conf=$conf .$this->ACL_MESSENGERS();
		}
		
		$INCLUDES[]="acls_center_meta.conf";
		$INCLUDES[]="acls_center.conf";
		$INCLUDES[]="url_rewrite_access.conf";
		$INCLUDES[]="acls_bandwidth.conf";
		$INCLUDES[]="acls_ecap.conf";
		$INCLUDES[]="icap.conf";
		$INCLUDES[]="wccp.conf";
		$INCLUDES[]="privoxy.conf";
		
		
		while (list ($index, $included) = each ($INCLUDES) ){
			$ZFILENAME="/etc/squid3/$included";
			$conf=$conf ."\ninclude $ZFILENAME\n";
			if(!is_file($ZFILENAME)){@touch($ZFILENAME);}
			@chown($ZFILENAME, "squid");
			@chgrp($ZFILENAME, "squid");
			
		}

		$ecap=new squid_ecap_builder();
		$ecap->compile();
		
		if($this->ASROOT){
			$GLOBALS["aclGen"]=new squid_acls();
			$GLOBALS["aclGen"]->Build_Acls();
			echo "Starting......: ".date("H:i:s")." [ACLS]: (class.squid.inc) L.".__LINE__." ".count($GLOBALS["aclGen"]->acls_array)." ACLs\n";
			$CountOFAcls=count($GLOBALS["aclGen"]->acls_array);
			$ACLS_TO_ADD=@implode("\n",$GLOBALS["aclGen"]->acls_array);
			@file_put_contents("/etc/squid3/acls_center.conf", $ACLS_TO_ADD);
		
		}
		
		$conf=$conf ."\n\n";
		$bandwith=new squid_bandwith_builder();
		$bandwith->compile();
		
		
		$conf=$conf . "\n# # # # # # # # # # # # # # # # # # EXTERNAL CACHES # # # # # # # # # # # # # # # # # #\n";
		$conf=$conf . "#\n";
		
		
		$conf=$conf . $this->cache_peer()."\n";
		$conf=$conf . "#\n";
		$conf=$conf . "#\n";
		
		
		$conf=$conf."\n# # # # # # # # # # # # # # # # # # RULES DEFINITIONS # # # # # # # # # # # # # # # # # #[".__LINE__."]\n";
		$freewebs=new squid_freewebs();
		if(!is_file("/etc/squid3/freewebs.conf")){@file_put_contents("/etc/squid3/freewebs.conf", "#\n");}
		if(!is_file("/etc/squid3/wccp.conf")){@file_put_contents("/etc/squid3/wccp.conf", "#\n");}
		if(!is_file("/etc/squid3/url_rewrite_access.conf")){@file_put_contents("/etc/squid3/url_rewrite_access.conf", "#\n");}
		if(is_file("/etc/squid3/freewebs.conf")){
			$conf=$conf."include /etc/squid3/freewebs.conf\n";
		}
		
		
		
		
		
		$conf=$conf.$artica_meta->http_access();
		$conf=$conf.$this->http_access();
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [ICAP]: SquidDisableAllFilters=$this->SquidDisableAllFilters\n";}

		$icap=new icap();
		$icap->build_services();
		$this->build_progress("Checks SNMP ports");
		$conf=$conf.$this->SNMP();
		$conf=$conf.$this->request_header_add();
		$conf=$conf."\n";
		
		if(!$this->IS_35){
			$conf=$conf."# --------- ident_lookup_access\n";
		}
		$conf=$conf."\n";
		$conf=$conf."# --------- General settings \n";
		$conf=$conf."visible_hostname ".$this->visible_hostnameF()."\n";
		$conf=$conf."unique_hostname ".$this->my_full_hostname()."\n";
		$conf=$conf."mime_table /etc/squid3/mime.conf\n";
		$conf=$conf."icon_directory /usr/share/squid3/icons\n";
		$conf=$conf."global_internal_static on\n";
		$conf=$conf."short_icon_urls on\n";
		
		if(($this->IS_30) && (!$this->IS_32)){
			$conf=$conf."ignore_expect_100 $ignore_expect_100\n";
		}
		
		$EnableSplashScreenX=$this->EnableSplashScreen;
		if($EnableSplashScreenAsObject==1){$EnableSplashScreenX=1;}
		
		$conf=$conf."# --------- Add X-Forwarded-for in headers [$EnableSplashScreenX]? \n";
		if($EnableSplashScreenX==1){$this->forwarded_for="on";}
		
		if($EnableSplashScreenX==1){
			if($this->IS_33){
				if(isset($GLOBALS["HTTP_ACCESS_ALLOW_SPLASHS"])){
					$conf=$conf."# --------- SplashScreen enabled, add  X-forwarded-for for FreeWebs\n";
					$conf=$conf."request_header_add X-forwarded-for %>a SplashScreens\n";
				}
			}
		}
		
		if($this->forwarded_for<>null){
			$conf=$conf."forwarded_for $this->forwarded_for\n";
		}
		
		if($this->shutdown_lifetime<10){$this->shutdown_lifetime=10;}
		if($this->forward_max_tries<30){$this->forward_max_tries=30;}
		$this->build_progress("Checks TimeOut parameters");
		$conf=$conf."\n";
		$conf=$conf."\n";
		$conf=$conf."# --------- time-out \n";
		$conf=$conf."shutdown_lifetime $this->shutdown_lifetime seconds\n";
		$conf=$conf."forward_max_tries {$this->forward_max_tries}\n";
		if($SquidConnectRetries>0){
			$conf=$conf."connect_retries $SquidConnectRetries\n";
		}
		
		if($this->SquidSimpleConfig==0){
		if(isset($GLOBALS["dead_peer_timeout"])){
			$conf=$conf."dead_peer_timeout {$GLOBALS["dead_peer_timeout"]} seconds\n";	
		}else{
			$conf=$conf."dead_peer_timeout {$this->dead_peer_timeout} seconds\n";
		}
		
		if($SquidClientPersistentConnections==1){
			$conf=$conf."client_persistent_connections on\n";
		}else{
			$conf=$conf."client_persistent_connections off\n";
		}
		
		if($SquidServerPersistentConnections==1){
			$conf=$conf."server_persistent_connections on\n";
		}else{
			$conf=$conf."server_persistent_connections off\n";
		}		
		
		
		$conf=$conf."forward_timeout {$this->forward_timeout} seconds\n";
		$conf=$conf."connect_timeout {$this->connect_timeout} seconds\n";
		
		if($SquidUploadTimeouts==0){
			$conf=$conf."read_timeout $this->read_timeout seconds\n";
			$conf=$conf."pconn_timeout $this->pconn_timeout seconds\n";
			$conf=$conf."persistent_request_timeout $this->persistent_request_timeout seconds\n";
			
		}else{
			$conf=$conf."read_timeout $SquidUploadTimeouts minutes\n";
			$conf=$conf."pconn_timeout $SquidUploadTimeouts minutes\n";
			$conf=$conf."persistent_request_timeout $SquidUploadTimeouts minutes\n";
			$conf=$conf."request_timeout $SquidUploadTimeouts minutes\n";
			
		}
		
		if($this->IS_27){
			$conf=$conf."incoming_rate $this->incoming_rate\n";
		}
		
		$conf=$conf."\n";
		$conf=$conf."\n";

		}else{
			$conf=$conf."# # # ABORTED SIMPLE CONFIG ENABLED # # #\n";
			
		}
		
		if($this->EnableChangeRequestSize==1){
			$conf=$conf."# --------- Objects limits \n";
			$conf=$conf."request_body_max_size $request_body_max_size KB\n";
			$conf=$conf."reply_body_max_size $reply_body_max_size KB\n";
			$conf=$conf."request_header_max_size $request_header_max_size KB\n";
			$conf=$conf."reply_header_max_size $reply_header_max_size KB\n";
			$conf=$conf."client_request_buffer_max_size $client_request_buffer_max_size KB\n";
		}
		
		$maximum_object_size_in_memory_unit="KB";
		if($minimum_object_size_unit==null){$minimum_object_size_unit="MB";}
		if($maximum_object_size_unit==null){$maximum_object_size_unit="MB";}
		if(!is_numeric($maximum_object_size)){$maximum_object_size=34;}
		if(!is_numeric($minimum_object_size)){$minimum_object_size=0;}
		
		
		if($SquidCacheLevel==0){
			$maximum_object_size=0;
			$maximum_object_size_unit="KB";
			$maximum_object_size_in_memory=0;
			$maximum_object_size_in_memory_unit="KB";
		}
		
		
		$conf=$conf."maximum_object_size $maximum_object_size $maximum_object_size_unit\n";
		$conf=$conf."minimum_object_size $minimum_object_size $minimum_object_size_unit\n";
		$conf=$conf."maximum_object_size_in_memory $minimum_object_size $minimum_object_size_unit\n";		
		
		$conf=$conf."\n";
		$conf=$conf."\n";
		
		if(!isset($this->global_conf_array["tcp_outgoing_address"])){$this->global_conf_array["tcp_outgoing_address"]=null;}
		
		$Mikrotik_tcp_outgoing_address=$this->Mikrotik_tcp_outgoing_address();

		
		if($Mikrotik_tcp_outgoing_address<>null){
			$conf=$conf."$Mikrotik_tcp_outgoing_address\n";
		}
		
			
		
		if(trim($this->global_conf_array["tcp_outgoing_address"])<>null){
			
			$SquidNetworkSwitch=trim($sock->GET_INFO("SquidNetworkSwitch"));
			if($SquidNetworkSwitch<>null){
				$conf=$conf."# tcp_outgoing_address {$this->global_conf_array["tcp_outgoing_address"]} Disabled by SquidNetworkSwitch\n";
			
			}else{
				$conf=$conf."tcp_outgoing_address {$this->global_conf_array["tcp_outgoing_address"]}\n";
			}	
		}
		
		
		
		
		if($this->SquidBubbleMode==1){$prefixacls=" MyStandPort ";}
		$conf=$conf."\n";
		$conf=$conf ."# -----------------------------\n";
		$conf=$conf ."# --------- SSL Rules ---------[".__LINE__."]\n";
		$conf=$conf ."# -----------------------------\n";
		$this->build_progress("Checks SSL Rules");
		$conf=$conf .$this->ssl_bump_access();
		
		$conf=$conf."\n\n";
		
		$this->build_progress("Checks Caches");
		$conf=$conf ."# --------- Caches \n\n";
		
		$conf=$conf ."# --------- SquidCacheLevel	=  $SquidCacheLevel [". __LINE__."]\n";
		$conf=$conf ."# --------- DisableAnyCache 	=  $this->DisableAnyCache [". __LINE__."]\n";
		$conf=$conf ."# --------- SquidAsMasterPeer =  $SquidAsMasterPeer [". __LINE__."]\n";
		$conf=$conf ."# --------- SquidAsMasterCacheChilds =  $SquidAsMasterCacheChilds [". __LINE__."]\n";
		$conf=$conf ."# --------- SquidAsMasterFollowxForward =  $SquidAsMasterFollowxForward [". __LINE__."]\n";
		
		
		
		
		if($SquidCacheLevel==0){
			$conf=$conf ."# --------- Do not cache ALL SITES [". __LINE__."]\n";
			$conf=$conf ."cache deny all\n";
		}
		
		if($this->DisableAnyCache==0){


			$conf=$conf ."# --------- Do not cache from Browsers [". __LINE__."]\n";
			$conf=$conf ."cache deny BrowsersNoCache\n";
			if(!is_file("/etc/squid3/GlobalAccessManager_deny_cache.conf")){@touch('/etc/squid3/GlobalAccessManager_deny_cache.conf');}
			$conf=$conf ."include /etc/squid3/GlobalAccessManager_deny_cache.conf\n";
			$this->build_progress("Checks Deny Cache acls (1)");
			$conf=$conf ."acl DoNotCache dstdomain .articatech.net\n";
			$conf=$conf ."cache deny {$prefixacls}DoNotCache\n"; 
		
		
		
			$q=new mysql_squid_builder();
			$acls=new squid_acls_groups();
			$this->build_progress("Checks Deny Cache acls (2)");
			$acls_deny=$acls->buildacls_bytype("cache_deny");
			if(count($acls_deny)>0){
				while (list ($index, $line) = each ($acls_deny) ){$conf=$conf ."cache deny {$prefixacls}$line\n";}
			}
			if($this->SquidBubbleMode==1){
				$sql="SELECT * FROM webfilters_sqaclsports WHERE enabled=1 ORDER BY aclport";
				$results = $q->QUERY_SQL($sql);
				while ($ligne = mysql_fetch_assoc($results)) {
					$aclport=$ligne["aclport"];
					$MyPortObject="MyPort{$ligne["aclport"]}";
					$acls_deny=$acls->buildacls_bytype("cache_deny",$aclport);
					if(count($acls_deny)>0){
						while (list ($index, $line) = each ($acls_deny) ){$conf=$conf ."cache deny $MyPortObject$line\n";}
					}					
				}
			}
		}
	
		
		if(!preg_match("#^[0-9]+\s+[A-Z]+#", $this->global_conf_array["read_ahead_gap"])){
			$this->global_conf_array["read_ahead_gap"]="1 MB";
		}
		
		$this->build_progress("Checks Memory tuning");
		$conf=$conf . "cache_effective_user squid\n";
		$conf=$conf ."# --------- CACHE MANAGEMENT AND MEMORY [".__LINE__."]\n";
		if(!preg_match("#([0-9]+)\s+([A-Za-z]+)#", $this->global_conf_array["cache_mem"])){$this->global_conf_array["cache_mem"]="{$this->global_conf_array["cache_mem"]} MB";}
		if($this->MEMORY_INSTALLEE<624288){$this->global_conf_array["cache_mem"]="8 MB";}
		if($SquidCacheLevel==0){ $conf=$conf . "cache_mem 8 MB\n";}
		if($SquidCacheLevel>0){$conf=$conf . "cache_mem {$this->global_conf_array["cache_mem"]}\n";}
		//$conf=$conf . "refresh_all_ims on\n";
		
		
	

		
		$this->build_progress("DNS tuning");
		$conf=$conf."# --------- DNS AND IP CACHES [".__LINE__."]\n";
		$conf=$conf.$this->dns_tuning()."\n";
		$conf=$conf.$this->dns_nameservers()."\n";
		$conf=$conf.$this->cache_tweaks()."\n";
		$this->build_progress("Cache Manager...");
		$conf=$conf.$this->CacheManager()."\n";
		
		$conf=$conf."# --------- PERSONNAL SETTINGS [".__LINE__."]\n";
		$conf=$conf."# To add your own tokens, just create a file under /etc/squid3/squid-me.conf,\n";
		$conf=$conf."# it will be merged here \n";
		if(is_file("/etc/squid3/squid-me.conf")){$conf=$conf."\n". @file_get_contents("/etc/squid3/squid-me.conf")."\n";}
		
		

		$conf=$conf.$this->ftp_parameters();
		$this->build_progress("Debug options");
		$debug_options=$this->debug_options();
		// debug option 84,9  -> Helpers
		if($debug_options<>null){
			$conf=$conf."debug_options $debug_options\n";
		}else{
			$conf=$conf."debug_options ALL,1\n";
		}
			
		if(isset($GLOBALS["authenticate_ip_ttl"])){
			$authenticate_ip_ttl=intval($sock->GET_INFO("authenticate_ip_ttl"));
			if($authenticate_ip_ttl==0){$authenticate_ip_ttl=60;}
			$conf=$conf."authenticate_ip_ttl $authenticate_ip_ttl seconds\n";
		}
		
		$conf=$conf."# --------- CACHES RULES SquidCacheLevel:$SquidCacheLevel [".__LINE__."]\n";
		
		$SquidCacheLevel=$sock->GET_INFO("SquidCacheLevel");
		if(!is_numeric($SquidCacheLevel)){$SquidCacheLevel=4;}
		$conf=$conf."reply_header_access Content-Security-Policy deny all\n";
		$conf=$conf."reply_header_access ETag deny all\n";
		
		if($SquidCacheLevel>2){
			$conf=$conf."reply_header_access Cache-Control deny all\n";
			
			
			//$conf=$conf."include /etc/squid3/refresh_patterns.conf\n";
			//if(!is_file("/etc/squid3/refresh_patterns.conf")){@touch("/etc/squid3/refresh_patterns.conf");}
			//@chown("/etc/squid3/refresh_patterns.conf","squid");
		}
		
		$this->build_progress("Cache rules...");
		
		if(!is_file("/etc/squid3/StoreID.conf")){@touch("/etc/squid3/StoreID.conf");}
		@chown("/etc/squid3/StoreID.conf","squid");
		
		
		$conf=$conf."include /etc/squid3/StoreID.conf\n";
		$HyperCacheSquid=new HyperCacheSquid();
		$HyperCacheSquid->build();
		$refresh_pattern_list=$this->refresh_pattern_list();
		$conf=$conf.$refresh_pattern_list;
		
		

		
		if($this->EnableICPPort==1){$conf=$conf."icp_port 3130\n";}
		$conf=$conf."\n";
		$conf=$conf."\n";
		$conf=$conf."#Logs-------------------------------------------------\n";
		$conf=$conf."#We want to log the full URI for Youtube categorization.\n";
		$conf=$conf."strip_query_terms off\n";
		//$conf=$conf."emulate_httpd_log on\n";
		
		if(($this->IS_30) && (!$this->IS_32)){
			if($EnableLogHostnames==1){
				$conf=$conf."log_fqdn on\n";
			}else{
				$conf=$conf."log_fqdn off\n";
			}
		}
		if($this->ASROOT){
			if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
			$php=$unix->LOCATE_PHP5_BIN();
			$sh[]="#!/bin/sh";
			$sh[]="/bin/echo \"$1\n\" >>/var/log/squid/sslask.txt\n";
			$sh[]="/bin/echo `$php /usr/share/artica-postfix/exec.squid.sslpassword_program.php \$1`\n";
			@file_put_contents("/etc/squid3/ssldiscover", @implode("\n", $sh));
			@chmod("/etc/squid3/ssldiscover",0777);
		}
		
		if(isset($compilation_params["enable-ssl"])){
			$conf=$conf."sslpassword_program /etc/squid3/ssldiscover\n";
		}
		
		
		$conf=$conf."coredump_dir	/var/squid/cache\n";
		
		if($this->LogsWarninStop==0){
			$conf=$conf."cache_log	/var/log/squid/cache.log\n";
		}
		if($this->LogsWarninStop==1){
			$conf=$conf."cache_log	/dev/null\n";
			$conf=$conf."logfile_rotate 0\n";
		}
		$conf=$conf."pid_filename	/var/run/squid/squid.pid\n";
		$compile_params=$this->compilation_params();
		$pinger=$this->pinger();
		if($pinger<>null){$conf=$conf.$pinger;}
		$conf=$conf.$this->access_logs();		
		$conf=$conf."# --------- CacheManagement2 = $this->CacheManagement2 DisableAnyCache = \"$this->DisableAnyCache\"/Urgency=\"$this->SquidUrgency\" [". __LINE__."]\n";
		//if($this->DisableAnyCache==0){$conf=$conf."store_dir_select_algorithm round-robin\n";}
		$this->build_progress("Cache directory method 2...");
		$conf=$conf.$this->cache_dir_method_2()."\n";
		$conf=$conf."\n";
		$conf=str_replace("\n\n", "\n", $conf);
		$this->build_progress("Done...");
		return $conf;	
	}
	
	
	
	public function cache_dir_method_0(){
		$f[]=$this->cache_dir_method_2();
		return @implode("\n", $f);
	}
	
	
	private function cache_dir_method_2(){
		if($this->SquidUrgency==1){$this->DisableAnyCache=1;}
		$f[]="# --------- cache_dir_method_2 SquidCacheCenter/build [".__LINE__."]";
		
		$this->build_progress("Open cache center...");
		$cache=new SquidCacheCenter();
		$cache->CURRENT_PROGRESS=$this->CURRENT_PROGRESS;
		$cache->MAX_PROGRESS=$this->MAX_PROGRESS;
		$cache->ASROOT=$this->ASROOT;
		$f[]=$cache->build();
		return @implode("\n", $f);
	}
	
	

	
	public function CPU_NUMBER(){
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
		$cat=$unix->find_program("cat");
		$grep=$unix->find_program("grep");
		$cut=$unix->find_program("cut");
		$wc=$unix->find_program("wc");
		$cmd="$cat /proc/cpuinfo |$grep \"model name\" |$cut -d: -f2|$wc -l 2>&1";
		$CPUNUM=exec($cmd);
		return $CPUNUM;
	}	
	
	public function isNGnx(){
		if(isset($GLOBALS["isNGnx"])){return $GLOBALS["isNGnx"];}
		$users=new usersMenus();
	
		if(!$users->NGINX_INSTALLED){$GLOBALS["isNGnx"]=false;return $GLOBALS["isNGnx"];}
		$sock=new sockets();
		$EnableNginx=$sock->GET_INFO("EnableNginx");
		if(!is_numeric($EnableNginx)){$EnableNginx=1;}
		if($EnableNginx==1){
			$GLOBALS["isNGnx"]=true;
			return $GLOBALS["isNGnx"];
		}
			
		$GLOBALS["isNGnx"]=false;
		return $GLOBALS["isNGnx"];
		
	}
	

	
	
	private function access_log_csv(){
		if($this->SquidUrgency==1){return;}
		if($this->LogsWarninStop==1){return;}
		if($this->EnableSquidCSV==0){return;}
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." Squid create logs in csv enabed\n";}
		$sock=new sockets();
		$SquidCsvParams=unserialize(base64_decode($sock->GET_INFO("SquidCsvParams")));
		$SquidAsMasterPeer=intval($sock->GET_INFO("SquidAsMasterPeer"));
		$SquidAsMasterLogChilds=intval($sock->GET_INFO("SquidAsMasterLogChilds"));
		$SquidAsMasterFollowxForward=intval($sock->GET_INFO("SquidAsMasterFollowxForward"));
		
		$CSV["a1"]="\"%>a\"";
		$CSV["a2"]="\"%>A\"";
		$CSV["a3"]="\"%>eui\"";
		$CSV["a4"]="\"%<a\"";
		$CSV["a5"]="\"%<A\"";
		$CSV["a6"]="\"%[un\"";
		$CSV["a7"]="\"%rm\"";
		$CSV["a8"]="\"%ru\"";
		$CSV["a9"]="\"%rv\"";
		$CSV["a10"]="\"%>Hs\"";
		$CSV["a11"]="\"%<st\"";
		$CSV["a12"]="\"%Ss:%Sh\"";
		$CSV["a13"]="\"%{User-Agent}>h\"";
		$CSV["a14"]="\"%{X-Forwarded-For}>h\"";
	
		if(($this->IS_31) && (!$this->IS_32)){
			unset($CSV["a4"]);
			unset($CSV["a3"]);
			
		}
		
		
		if($this->EnableMacAddressFilter==0){unset($CSV["a3"]);}
		
		
		if( (count($SquidCsvParams)==0) OR !is_array($SquidCsvParams)){while (list ($code, $explain) = each ($CSV) ){$SquidCsvParams[$code]=1;}reset($CSV);}
	
		$access[]="\"%{%Y-%m-%d}tl\"";
		$access[]="\"%{%H:%M:%S}tl\"";
		
		while (list ($code, $value) = each ($CSV) ){
			if($SquidCsvParams[$code]==1){
				$ll[]=$code;
				$access[]=$value;
			}
		}
		//$conf[]="logformat csv \"%tl\",\"%>eui\",\"%>a\",\"%>A\",\"%[ui\",\"%[un\",\"[%tl]\",\"%rm %ru HTTP/%rv\" %>Hs %<st %Ss:%Sh UserAgent:\"%{User-Agent}>h\" Forwarded:\"%{X-Forwarded-For}>h\"\n";
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." Squid enabled CSV fields:".@implode(", ",$ll)."\n";}
		$conf[]="logformat csv ".@implode(",", $access);
		$acls=new squid_acls_groups();
		$denylogsTXT=null;
		$denylogs=$acls->buildacls_bytype("deny_log");
		if(count($denylogs)>0){$denylogsTXT=trim(@implode(" ", $denylogs));}
		if(strlen($denylogsTXT)>2){$denylogsTXT=" $denylogsTXT";}
		

		
		
		if(($this->IS_31) && (!$this->IS_32)){
			$conf[]="access_log /var/log/squid/access.csv csv $denylogsTXT\n";
		}
		
		if($this->IS_32){
			$conf[]="access_log stdio:/var/log/squid/access.csv csv !MgRPort$denylogsTXT\n";
		}
		return @implode("\n", $conf);
	}
	
	
	private function syslog_remote(){
		$sock=new sockets();
		if(!$this->ASROOT){return;}
		$restart=false;
		$array=unserialize(base64_decode($sock->GET_INFO("SquidSyslogAdd")));
		if(!isset($array["ENABLE"])){
			echo "Starting......: ".date("H:i:s")." Squid remote syslog is disabled\n";
			return;}
		if(!is_numeric($array["ENABLE"])){$array["ENABLE"]==0;}
		if($array["ENABLE"]==0){
			echo "Starting......: ".date("H:i:s")." Squid remote syslog is disabled\n";
			if(is_file("/etc/rsyslog.d/artica-client-local6.conf")){@unlink("/etc/rsyslog.d/artica-client-local6.conf");$restart=true;}
			return;
		}
		
		
		if($array["SERVER"]==null){
			echo "Starting......: ".date("H:i:s")." Squid remote syslog is disabled (no server set)\n";
			if(is_file("/etc/rsyslog.d/artica-client-local6.conf")){@unlink("/etc/rsyslog.d/artica-client-local6.conf");$restart=true;}
			return;
		}
		
		
		
		if(!is_file("/etc/rsyslog.d/artica-client-local6.conf")){$restart=true;}
		
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
		$php5=$unix->LOCATE_PHP5_BIN();
		$nohup=$unix->find_program("nohup");
		echo "Starting......: ".date("H:i:s")." Squid remote syslog exec.syslog-engine.php --squidsys\n";
		shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.syslog-engine.php --squidsys >/dev/null 2>&1 &");
		
		if(intval($array["ENABLE_PERSO_EVENT"])==1){
			$f[]="logformat Syslog6 {$array["PERSO_EVENT"]}";
			
			if($this->IS_34){
				$f[]="access_log syslog:local6.info logformat=Syslog6 on-error=drop buffer-size=64KB ";
				return @implode("\n", $f);
			}
			
			$f[]="access_log syslog:local6.info Syslog6";
			return @implode("\n", $f);
		}
		
		
		
		
		if($this->IS_34){
			$f[]="access_log syslog:local6.info logformat=common on-error=drop buffer-size=64KB ";
			return @implode("\n", $f);
		}
		
		return "access_log syslog:local6.info common\n";
	}
	
	
	private function isp_networks(){
		if($this->SquidUrgency==1){return null;}
		$sock=new sockets();
		$SquidEnableISPMode=$sock->GET_INFO("SquidEnableISPMode");
		if(!is_numeric($SquidEnableISPMode)){return null;}
		if($SquidEnableISPMode==0){return null;}
		$q=new mysql_squid_builder();
		$sql="SELECT publicip FROM usersisp WHERE enabled=1";
		$results=$q->QUERY_SQL($sql);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$f[]=$ligne["publicip"];
		}
		if(count($f)>0){
			@file_put_contents("/etc/squid3/isp_acls_src",@implode("\n", $f));
			$GLOBALS["HTTP_ACCESS"]["ISP_NETWORK"]="http_access allow {$this->MyStandPort}ISP_CLIENTS";
			return "acl ISP_CLIENTS src \"/etc/squid3/isp_acls_src\"";
		
		}
		
	}
	
	private function GoogleSafeBrowsing(){
		if($this->SquidUrgency==1){return null;}
		$sock=new sockets();
		if($sock->EnableUfdbGuardArtica()==0){return;}
		$EnableGoogleSafeBrowsing=intval($sock->GET_INFO("EnableGoogleSafeBrowsing"));
		$f[]="# SafeBrowsing EnableGoogleSafeBrowsing = $EnableGoogleSafeBrowsing [".__LINE__."]";
		if($EnableGoogleSafeBrowsing==0){return @implode("\n", $f);}
		$f[]="acl BanSafeBrowsing dstdomain safebrowsing.clients.google.com";
		$f[]="acl BanSafeBrowsing dstdomain safebrowsing-cache.google.com";
		$f[]="acl BanSafeBrowsing dstdomain safebrowsing.google.com";
		$f[]="http_access deny BanSafeBrowsing";
		return @implode("\n", $f);
		
	}
	
	
	

	
	private function check_malwares_acl(){
		if($this->SquidUrgency==1){return null;}
		$f=file("/etc/squid3/malwares.acl");
		echo "Starting......: ".date("H:i:s")." ACL malwares ". count($f)." rules..\n";
		$rebuild=false;
		while (list ($num, $val) = each ($f)){
			$val=str_replace("\r", "", $val);
			$val=str_replace("\n", "", $val);
			$val=trim($val);
			if($val==null){continue;$rebuild=true;}
			if(preg_match("#try\{#", $val)){
				echo "Starting......: ".date("H:i:s")." ACL malwares skipping line $num\n";
				$rebuild=true;
				continue;
			}
			$t[]=$val;
		}
		if($rebuild){
			echo "Starting......: ".date("H:i:s")." ACL malwares saving ". count($t)." rules..\n";
			@file_put_contents("/etc/squid3/malwares.acl", @implode("\n", $t));
		}
		
	}
	
	public function squid_sessions_builder(){
		return null;
		$sock=new sockets();
		$users=new usersMenus();
		$SquidEnableSessionEngine=$sock->GET_INFO("SquidEnableSessionEngine");
		$SquidSessionEngineTimeOut=$sock->GET_INFO("SquidSessionEngineTimeOut");
		$SquidSessionEngineExternalUrl=$sock->GET_INFO("SquidSessionEngineExternalUrl");
		if(!is_numeric($SquidEnableSessionEngine)){$SquidEnableSessionEngine=0;}
		if(!is_numeric($SquidSessionEngineTimeOut)){$SquidSessionEngineTimeOut=3600;}
		if($SquidEnableSessionEngine==0){return null;}
		$f[]="external_acl_type ArticaAclSessions concurrency=100 ttl=3 %SRC %URI %>{Host} %>{Cookie} %LOGIN $users->PHP_BIN_PATH /usr/share/artica-postfix/squid-helper.php";
		$f[]="acl ArticaAclAuthenticated external ArticaAclSessions LOGIN";
		$f[]="deny_info $SquidSessionEngineExternalUrl ArticaAclAuthenticated";
		$GLOBALS["HTTP_ACCESS"]["EXTERNAL_ARTICA_HELPER"]="http_access deny {$this->MyStandPort}!ArticaAclAuthenticated";
		return @implode("\n",$f);
		
	}
	
	
		private function debug_options(){
			if($this->SquidUrgency==1){return;}
			$sock=new sockets();
			$acldebg=null;
			$SquidDebugCacheProcdbg=null;
			//see http://wiki.squid-cache.org/KnowledgeBase/DebugSections
			$SquidDebugAcls=$sock->GET_INFO("SquidDebugAcls");
			$SquidDebugCacheProc=$sock->GET_INFO("SquidDebugCacheProc");
			
			if(!is_numeric($SquidDebugAcls)){$SquidDebugAcls=0;}
			if(!is_numeric($SquidDebugCacheProc)){$SquidDebugCacheProc=0;}
			
			
			if($SquidDebugAcls==1){
				$acldebg=" 33,2 28,9";
			}
			if($SquidDebugCacheProc==1){
				$SquidDebugCacheProcdbg=" 20,9";
			}

			
			return "ALL,1$acldebg$SquidDebugCacheProcdbg";
			
		}
		
		
		
		private function dns_tuning(){
			$sock=new sockets();
			$SquidIpv6DNSPrio=intval($sock->GET_INFO("SquidIpv6DNSPrio"));
			$EnableipV6=$sock->GET_INFO("EnableipV6");
			if(!is_numeric($EnableipV6)){$EnableipV6=0;}
			$conf[]="\n\n# --------- SPECIFIC DNS SERVERS [".__LINE__."]";
			$conf[]=$this->append_domain();
			
			if($EnableipV6==0){
				$SquidIpv6DNSPrio=0;
				@file_put_contents("/proc/sys/net/ipv6/conf/lo/disable_ipv6",1);
				@file_put_contents("/proc/sys/net/ipv6/conf/all/disable_ipv6",1);
				@file_put_contents("/proc/sys/net/ipv6/conf/default/disable_ipv6",1);
			}else{
				@file_put_contents("/proc/sys/net/ipv6/conf/lo/disable_ipv6",0);
				@file_put_contents("/proc/sys/net/ipv6/conf/all/disable_ipv6",0);
				@file_put_contents("/proc/sys/net/ipv6/conf/default/disable_ipv6",0);
			}
			
			if($SquidIpv6DNSPrio==1){$conf[]="dns_v4_first off";}else{$conf[]="dns_v4_first on";}
			$SquidClientDSTPassThru=$sock->GET_INFO("SquidClientDSTPassThru");
			if(!is_numeric($SquidClientDSTPassThru)){$SquidClientDSTPassThru=1;}
			
			if($this->IS_34){
				$conf[]="client_dst_passthru off";
				$conf[]="host_verify_strict off";
			}
			$conf[]="ignore_unknown_nameservers off";
			
			
			if($this->SquidUrgency==1){
				$conf[]="# # # ABORTED EMERGENCY MODE # # #";
				return @implode("\n", $conf);
			}
			
			$conf[]="dns_timeout {$this->dns_timeout} seconds";
			$conf[]="ipcache_size {$this->global_conf_array["ipcache_size"]}";
			$conf[]="ipcache_low {$this->global_conf_array["ipcache_low"]}";
			$conf[]="ipcache_high {$this->global_conf_array["ipcache_high"]}";
			$conf[]="fqdncache_size {$this->global_conf_array["fqdncache_size"]}";
			$conf[]="positive_dns_ttl {$this->global_conf_array["positive_dns_ttl"]}";
			$conf[]="negative_dns_ttl {$this->global_conf_array["negative_dns_ttl"]}";
			
			if($this->SquidSimpleConfig==1){
				 return @implode("\n", $conf);
			}
			
			
			
		}
	
		
		public function dns_nameservers_from_resolv($asArray=false){
			if($this->SquidUrgency==1){return null;}
			if(!$this->ASROOT){return;}
			$ClassTCPIP=new IP();
			$s=array();
			$this->build_progress("Checks resolv.conf - 2 -");
			$f=explode("\n",@file_get_contents("/etc/resolv.conf"));
			while (list ($num, $val) = each ($f)){
				if(preg_match("#nameserver\s+(.+)#", $val,$re)){
					$DNSServer=trim($re[1]);
					if($ClassTCPIP->isValid($DNSServer)){$s[]=trim($DNSServer);}
				}
			}
			
			if(count($s)==0){return array();}
			if($asArray){return $s;}
			if(count($s)>0){return @implode(" ", $s);}
			
		}
		
		public function CacheManager_default(){
			$sock=new sockets();
			$LicenseInfos=unserialize(base64_decode($sock->GET_INFO("LicenseInfos")));
			$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
			
			if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}
			if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]="contact@articatech.com";}
			$LicenseInfos["EMAIL"]=str_replace("'", "", $LicenseInfos["EMAIL"]);
			$LicenseInfos["EMAIL"]=str_replace('"', "", $LicenseInfos["EMAIL"]);
			$LicenseInfos["EMAIL"]=str_replace(' ', "", $LicenseInfos["EMAIL"]);
			$conf[]="";
			$conf[]="#Cache Manager Authentication";
			$conf[]="cache_mgr {$LicenseInfos["EMAIL"]}";
			$conf[]="";
			return @implode("\n", $conf);
		}
		
		private function CacheManager(){
			$users=new usersMenus();
			if(!$users->CORP_LICENSE){return $this->CacheManager_default();}
			
			
			$sock=new sockets();
			$cache_mgr_user=$sock->GET_INFO("cache_mgr_user");
			$cachemgr_passwd=$sock->GET_INFO("cachemgr_passwd");
			if($cache_mgr_user==null){return $this->CacheManager_default();}
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [MGR]: Cache Manager set has $cache_mgr_user\n";} 
				$conf[]="";
				$conf[]="#Cache Manager Authentication";
				$conf[]="cache_mgr $cache_mgr_user";
				if($cachemgr_passwd<>null){
					$conf[]="cachemgr_passwd $cachemgr_passwd all";
				}
				$conf[]="";
				return @implode("\n", $conf);
			
			
		}
		
		private function ACL_MANAGER(){
			if( ($this->IS_31) OR ($this->IS_30) OR ($this->IS_27)){
				if(!$this->IS_32){
					if(!$this->IS_33){
						return "acl manager proto cache_object";
					}
				}
			}
		}
		
		
		private function ACL_identd_server(){
			$sock=new sockets();
			
			$SquidEnableIdentdService=intval($sock->GET_INFO("SquidEnableIdentdService"));
			if($SquidEnableIdentdService==0){return null;}
			$f[]="acl identhosts ident REQUIRED";
			$q=new mysql_squid_builder();
			$sql="SELECT * FROM ident_networks";
			$results = $q->QUERY_SQL($sql);
			$network_items=array();
			$ip=new IP();
			while ($ligne = mysql_fetch_assoc($results)) {
				$network_item=trim($ligne["network_item"]);
				if($network_item==null){continue;}
				if(!$ip->isIPAddressOrRange($network_item)){continue;}
				$network_items[]=$network_item;
			}
			
			if(count($network_items)==0){
				$f[]="ident_lookup_access allow all";
			}else{
				@file_put_contents("/etc/squid3/ident_aware_hosts.db", @implode("\n", $network_items)."\n");
				@chown("/etc/squid3/ident_aware_hosts.db", "squid");
				@chgrp("/etc/squid3/ident_aware_hosts.db", "squid");
				$f[]="acl ident_aware_hosts src \"/etc/squid3/ident_aware_hosts.db\"";
				$f[]="ident_lookup_access allow ident_aware_hosts";
				$f[]="ident_lookup_access deny all";
			}
			$SquidEnableIdentTimeout=intval($sock->GET_INFO("SquidEnableIdentTimeout"));
			if($SquidEnableIdentTimeout==0){$SquidEnableIdentTimeout=3;}
			$f[]="ident_timeout $SquidEnableIdentTimeout seconds";
			return @implode("\n",$f);
			
			
			
		}
		
		
		

		
		private function ACL_MyLocalIpsDest(){
			$NETWORK_ALL_INTERFACES=$this->NETWORK_ALL_INTERFACES();
			unset($NETWORK_ALL_INTERFACES["0.0.0.0"]);
			unset($NETWORK_ALL_INTERFACES["127.0.0.1"]);
			$tcp=new IP();
			while (list ($ipaddr, $line) = each ($NETWORK_ALL_INTERFACES) ){
				if(!$tcp->isIPAddress($ipaddr)){continue;}
				$f[]=$ipaddr;
			}
			
			if(count($f)==0){
				return "acl MyLocalIpsDest dst 127.0.0.1\n"; 
			}
			
			
			return "acl MyLocalIpsDest dst ".@implode(" ", $f)."\n";
			
		}
		
		
		private function http_access_debugport(){
			$sock=new sockets();
			$q=new mysql_squid_builder();
			$SquidDebugPort=intval($sock->GET_INFO("SquidDebugPort"));
			if($SquidDebugPort==0){return;}
			$f[]="#";
			$f[]="#--------- Disable Web filtering for the Test Port [".__LINE__."]";
			
			if(!$q->TABLE_EXISTS("debugport_addr")){
				$f[]="#--------- debugport_addr no such table";
				$f[]="http_access allow MyTestPort all";
				return @implode("\n", $f);
			}
			
			if($q->COUNT_ROWS("debugport_addr")==0){
				$f[]="#--------- debugport_addr no item";
				$f[]="http_access allow MyTestPort all";
				return @implode("\n", $f);
			}
			
			$sql="SELECT *  FROM debugport_addr";
			$results = $q->QUERY_SQL($sql);
			$f[]="#--------- debugport_addr ".mysql_numrows($results)." item(s)";
			while ($ligne = mysql_fetch_assoc($results)) { $t[]=$ligne["ipaddr"]; }
			$f[]="acl MyTestPortAdrr src ".@implode(" ", $t);
			$f[]="http_access deny MyTestPort !MyTestPortAdrr";
			$f[]="http_access allow MyTestPort";
			$f[]="#------------------------------------[".__LINE__."]";
			$f[]="#";
			return @implode("\n", $f);
		}
		
		private function NoAuthPorts(){
			$q=new mysql_squid_builder();
			$sql="SELECT COUNT(*) as TCOUNT FROM proxy_ports WHERE enabled=1 and NoAuth=1";
			$ligne=@mysql_fetch_array($q->QUERY_SQL($sql));
			if(intval($ligne["TCOUNT"])==0){return null;}
			$GLOBALS["NoAuthPorts_event"]="# {$ligne["TCOUNT"]} ports whit not auth set..";
			return "NoAuthPort";
		}
		
		private function http_access(){
			$sock=new sockets();
			$SquidAsMasterPeer=intval($sock->GET_INFO("SquidAsMasterPeer"));
			$SquidAsMasterPeerPort=intval($sock->GET_INFO("SquidAsMasterPeerPort"));
			$SquidAsMasterPeerPortSSL=intval($sock->GET_INFO("SquidAsMasterPeerPortSSL"));
			$SquidDebugPort=intval($sock->GET_INFO("SquidDebugPort"));
			$SquidAsMasterFollowxForward=intval($sock->GET_INFO("SquidAsMasterFollowxForward"));
			$EnableSquidPortsRestrictions=intval($sock->GET_INFO("EnableSquidPortsRestrictions"));
			$UfdbGuardHTTPAllowUnblock=intval($sock->GET_INFO("UfdbGuardHTTPAllowUnblock")); //UfdbAutoblock
			$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
			if($SquidPerformance>2){$UfdbGuardHTTPAllowUnblock=0;}
			$AllowSquidOtherProtocols=intval($sock->GET_INFO("AllowSquidOtherProtocols"));
			$AllowSquidHSTS=intval($sock->GET_INFO("AllowSquidHSTS"));
			$SquidAllowSmartPhones=intval($sock->GET_INFO("SquidAllowSmartPhones"));
			$q=new mysql_squid_builder();
			
			$conf[]="#";
			$conf[]="#";
			$conf[]="# # # # # # # # # # # # # # # # # # # ## # # # # # [".__LINE__."]";
			$conf[]="# # # # # # # # # # MAIN RULES # # # # # # # # # # [".__LINE__."]";
			$conf[]="# # # # # # # # # # # # # # # # # # # ## # # # # # [".__LINE__."]";
			$conf[]="#--------- Dedicated rule that allow Access to Artica repositories";
			$conf[]="#--------- in any cases for updates [".__LINE__."]";
			$conf[]="#";
			$conf[]="#";
			if($this->IS_35){
				$conf[]="#--------- HTTP 301/302 ACL";
				$conf[]="acl http302 http_status 302";
				$conf[]="acl http301 http_status 301";
				$conf[]="";
				$conf[]="#--------- Apple playlists ( no cache index files )";
				$conf[]="acl mimenocache rep_mime_type -i application/x-apple-plist";
				$conf[]="acl mimenocache rep_mime_type -i application/vnd.apple.mpegurl";
				$conf[]="acl mimenocache rep_mime_type -i video/abst";
				$conf[]="";
				$conf[]="store_miss deny http302";
				$conf[]="store_miss deny http301";
				$conf[]="send_hit deny http302";
				$conf[]="send_hit deny http301";
				$conf[]="";
				$conf[]="store_miss deny mimenocache";
				$conf[]="send_hit deny mimenocache";
				$conf[]="";
				if($AllowSquidOtherProtocols==0){
					$conf[]="#--------- Wont push the client to use udp 443 or udp 80";
					$conf[]="reply_header_access alternate-protocol deny all";
					
				}
				if($AllowSquidHSTS==0){
					$conf[]="#--------- Wont push the client to use HSTS sent by the web site";
					$conf[]="reply_header_access Strict-Transport-Security deny all";					
					
				}
				
				
			}
			
	
			
			$conf[]="#";
			$conf[]="http_access allow ToArticaWWW";
			$conf[]="http_access allow MyLocalIpsDest";
			$conf[]="http_access allow FTP";
			$cont[]=$this->tcp_outgoing_address_switch();
			$conf[]=$this->GoogleSafeBrowsing();
			
			if($this->SquidUrgency==0){
				if($this->SquidUrgency==0){
					$conf[]="#--------- Global Access Manager for Deny Global";
					$conf[]="include /etc/squid3/GlobalAccessManager_deny.conf";
				}

			}
			
			if($this->SquidUrgency==0){
				$conf[]="#--------- http_access: Dedicated rule that deny UserAgents [".__LINE__."]";
				$conf[]="http_access deny BrowsersDeny all";
				
			}

			
			$AllowSquidDropBox=intval($sock->GET_INFO("AllowSquidDropBox"));
			$AllowSquidSkype=intval($sock->GET_INFO("AllowSquidSkype"));
			$AllowSquidOffice365=intval($sock->GET_INFO("AllowSquidOffice365"));
			$AllowSquidGoogle=intval($sock->GET_INFO("AllowSquidGoogle"));
			$conf[]="#--------- AllowSquidDropBox:$AllowSquidDropBox, AllowSquidSkype:$AllowSquidSkype, AllowSquidOffice365:$AllowSquidOffice365 F:".__FUNCTION__." line:".__LINE__;
			if($AllowSquidDropBox==1){
				$conf[]="http_access allow dropbox_ips";
				$conf[]="http_access allow dropbox_www";

			}
			if($AllowSquidSkype==1){
				$conf[]="http_access allow skype_ips";
				$conf[]="http_access allow skype_www";

			}
			if($AllowSquidOffice365==1){
				$conf[]="http_access allow office365_ips";
				$conf[]="http_access allow office365_www";

			}
			if($AllowSquidGoogle==1){
				$conf[]="http_access allow google_ips";
				$conf[]="http_access allow google_www";

			}			
			
			
			if($this->SquidUrgency==1){
				$conf[]="#--------- URGENCY ENABLED !!! F:".__FUNCTION__." line:".__LINE__;
				$conf[]="http_access allow all\n";
				$GLOBALS[__FUNCTION__]=@implode("\n", $conf);
				return $GLOBALS[__FUNCTION__];
			}
			if($this->enable_dansguardian==1){$this->allow_squid_localhost=1;}
			$this->allow_squid_localhost=1;
			$ACL_MANAGER=$this->ACL_MANAGER();
	
			$conf[]="#--------- ACL_MANAGER F:".__FUNCTION__." line:".__LINE__;
			if($ACL_MANAGER<>null){$conf[]=$ACL_MANAGER;}
			
			if(isset($GLOBALS["HTTP_ACCESS_ALLOW_TEMPLATES"])){
				if($GLOBALS["HTTP_ACCESS_ALLOW_TEMPLATES"]<>null){
					$conf[]="#--------- Allow remote error pages in all ways...F:".__FUNCTION__." line:".__LINE__;
					$conf[]=$GLOBALS["HTTP_ACCESS_ALLOW_TEMPLATES"];
				}
			}
			
			if(isset($GLOBALS["HTTP_ACCESS_ALLOW_SPLASHS"])){
				if($GLOBALS["HTTP_ACCESS_ALLOW_SPLASHS"]<>null){
					$conf[]="#--------- Allow Splash authentications websites for HotSpot...F:".__FUNCTION__." line:".__LINE__;
					$conf[]=$GLOBALS["HTTP_ACCESS_ALLOW_SPLASHS"];
				}
			}
			
			
			if(isset($GLOBALS["HTTP_ACCESS"]["ArticaRestrictAccess"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["ArticaRestrictAccess"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["ExtrnQuota"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["ExtrnQuota"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["MacToUid"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["MacToUid"];}
			
			if($this->ACL_ARP_ENABLED){
				$conf[]="http_access allow {$this->MyStandPort}whitelisted_mac_computers";
				
				
			}
			if(isset($GLOBALS["PROXY_NTLM_SWITCH"])){
				$conf[]="include /etc/squid3/non_ntlm.access";
			}
			
			$conf[]="http_access allow squidclient manager";
			$conf[]="http_access allow squidclient MgrInfoUri";

			$acls=new squid_acls_groups();
			
			$tcp_outgoing_tos=$acls->buildacls_bytype("tcp_outgoing_tos");
			if(count($tcp_outgoing_tos)>0){
				echo "Starting......: ".date("H:i:s")." [ACLS]: Engine tcp_outgoing_tos ".count($tcp_outgoing_tos)." rules..\n";
				while (list ($index, $line) = each ($tcp_outgoing_tos) ){$conf[]="tcp_outgoing_tos $line";}
			}else{
				echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine tcp_outgoing_tos No rules..\n";
			}	
			
			
			$reply_body_max_size=$acls->buildacls_bytype("reply_body_max_size");
			if(count($reply_body_max_size)>0){
				echo "Starting......: ".date("H:i:s")." [ACLS]: reply_body_max_size ".count($reply_body_max_size)." rules..\n";
				while (list ($index, $line) = each ($reply_body_max_size) ){$conf[]="reply_body_max_size $line";}
			}else{
				echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine reply_body_max_size No rules..\n";
			}			
			
			$tcp_outgoing_address=$acls->buildacls_bytype("tcp_outgoing_address");
			$conf[]="# tcp_outgoing_address ".count($tcp_outgoing_address)." rule(s) [".__LINE__."]";
			if(isset($GLOBALS["tcp_outgoing_address_errors"])){
				if(count($GLOBALS["tcp_outgoing_address_errors"])>0){while (list ($index, $line) = each ($GLOBALS["tcp_outgoing_address_errors"]) ){$conf[]="# $line";}}
			}
			
			if(count($tcp_outgoing_address)>0){
				echo "Starting......: ".date("H:i:s")." [ACLS]: Engine tcp_outgoing_address ".count($tcp_outgoing_address)." rules..\n";
				while (list ($index, $line) = each ($tcp_outgoing_address) ){$conf[]="tcp_outgoing_address $line";}
			}else{
				echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine tcp_outgoing_address No rules..\n";
			}
			
			$q=new mysql_squid_builder();
			if(!$q->FIELD_EXISTS("proxy_ports", "outgoing_addr")){$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `outgoing_addr` varchar(90) NULL");if(!$q->ok){echo $q->mysql_error."\n";}}
			if(!isset($GLOBALS["NETWORK_ALL_INTERFACES"])){$unix=new unix();$GLOBALS["NETWORK_ALL_INTERFACES"]=$unix->NETWORK_ALL_INTERFACES();}
			$sql="SELECT enabled,ID,outgoing_addr FROM proxy_ports WHERE enabled=1";
			$results=$q->QUERY_SQL($sql);
			if(!$q->ok){echo " * * * * FATAL $q->mysql_error * * * *\n";}
			$conf[]="# tcp_outgoing_address for listen ports ". mysql_num_rows($results)." items [".__LINE__."]";
			while ($ligne = mysql_fetch_assoc($results)) {
				$aclName="MyPort{$ligne["ID"]}";
				$outgoing_addr=trim($ligne["outgoing_addr"]);
				if($outgoing_addr==null){continue;}
				$ipaddr=$GLOBALS["NETWORK_ALL_INTERFACES"][$outgoing_addr]["IPADDR"];
				if($ipaddr==null){continue;}
				$conf[]="tcp_outgoing_address $outgoing_addr $aclName";
			}

			if(isset($GLOBALS["HTTP_ACCESS"]["QUOTAS"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["QUOTAS"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["SPLASH"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["SPLASH"];}
			if(!isset($GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_REDIRECTOR"])){$GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_REDIRECTOR"]=array();}
			
			
			$conf[]="http_access allow to_localhost";

			
			
			
			if(count($GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_REDIRECTOR"])>0){
				$conf[]=implode("\n",$GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_REDIRECTOR"]);
			}
		
			
			
	
			
			if(isset($GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_MAC"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_MAC"];}			
			if(isset($GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["BANNED_COMPUTERS_MAC"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["BANNED_COMPUTERS_MAC"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["BANNED_COMPUTERS"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["BANNED_COMPUTERS"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["AOL_MESSENGERS"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["AOL_MESSENGERS"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["IRC_MESSENGERS"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["IRC_MESSENGERS"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["YAHOO_MESSENGERS"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["YAHOO_MESSENGERS"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["GOOGLE_MESSENGERS"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["GOOGLE_MESSENGERS"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["MSN_MESSENGERS"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["MSN_MESSENGERS"];}
			
		   if($this->enable_ftp_restrictions){
				$conf[]="http_access allow {$this->MyStandPort}FTP clients_ftp";
				$conf[]="http_access allow {$this->MyStandPort}CONNECT clients_ftp";
			}	

			$conf[]="http_access deny purge";			
			if($this->EnableUserAgentBanAll==1){$conf[]="http_access deny !AllowedBrowsers";}		
			if(isset($GLOBALS["HTTP_ACCESS"]["BLOCKEDSITES"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["BLOCKEDSITES"];}	
			if(isset($GLOBALS["HTTP_ACCESS"]["MALWARE_PATROL"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["MALWARE_PATROL"];}			
			if(isset($GLOBALS["HTTP_ACCESS"]["ItChart"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["ItChart"];}
			
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ACLS]: Building http_access rules... (Line ".__LINE__.")\n";}
			$conf[]="#";
			$conf[]="#";
			$conf[]="# # # # # # # # # # # # # # # # # # RULES ADDED BY ADMINISTRATORS # # # # # # # # # # # # # # # # # # F:".__FUNCTION__." line:".__LINE__;
			$conf[]="#";
			if($this->SquidUrgency==0){
				$conf[]="#--------- Global Access Manager for NON-NTLM";
				$conf[]="include /etc/squid3/GlobalAccessManager_auth.conf";
			}
			
			
			$conf[]=$acls->buildacls_order(0);
			
			if($this->SquidBubbleMode==1){
				$conf[]="#--------- BUBBLE MODE:";
				$q=new mysql_squid_builder();
				$sql="SELECT * FROM webfilters_sqaclsports WHERE enabled=1 ORDER BY aclport";
				$results = $q->QUERY_SQL($sql);
				while ($ligne = mysql_fetch_assoc($results)) {
					$conf[]="#--------- BUBBLE: ACLS {$ligne["portname"]}";
					$conf[]=$acls->buildacls_order($ligne["aclport"]);
				}
			}
					
			$conf[]="#--------- Others mandatories ACLS";
			if($EnableSquidPortsRestrictions==1){
				$conf[]="http_access deny {$this->MyStandPort}!Safe_ports";
				$conf[]="http_access deny {$this->MyStandPort}CONNECT !SSL_ports";
			}
				
			
			if(isset($GLOBALS["HTTP_ACCESS"]["WHITELISTED_BROWSERS"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["WHITELISTED_BROWSERS"];}
			if(isset($GLOBALS["HTTP_ACCESS_SPLASH_SCREEN"])){
				$conf[]="#--------- HotSpot allowed accesses";
				$conf[]=$GLOBALS["HTTP_ACCESS_SPLASH_SCREEN"];
			}
				
			$conf[]="#--------- Is Transparent ? ". count($this->TRANSPARENT_PORTS_LIST)." items";
			if(count($this->TRANSPARENT_PORTS_LIST)>0){
				if(isset($GLOBALS["HTTP_ACCESS"]["OFFICE_NETWORK"])){
					$conf[]="http_access allow office_network MyTransparentPort";
				}else{
					$conf[]="http_access allow MyTransparentPort";
				}
					
			}
				
			if($this->smartphones_port>0){
				$conf[]="#--------- Dedicated rule that allow Mobile phones (SSL is allowed for all)";
				$conf[]="http_access allow MyMobilesPort SSL_ports all";
				if($this->IS_33){
					$conf[]="http_access deny MyMobilesPort !MobilePhones";
				}
			}
			$conf[]="#--------- http_access: Dedicated rule that allow UserAgents [".__LINE__."]";
			$conf[]="http_access allow BrowsersByPass";
			if($SquidAllowSmartPhones==1){
				$conf[]="#--------- Allow mobiles phones to not be authenticated.";
				$conf[]="http_access allow MobilePhones";
			}
			
			

			
			$conf[]="#--------- Some ports Without auth ?";
			$NoAuthPorts=$this->NoAuthPorts();
			if($NoAuthPorts<>null){
				$conf[]=$GLOBALS["NoAuthPorts_event"];
				$conf[]="http_access allow $NoAuthPorts all";
			}
	
			$conf[]="#--------- Is must be authenticated ?";
			$squid_childs=new squid_childs();
			$conf[]=$squid_childs->final_access();
			if(isset($GLOBALS["HTTP_ACCESS"]["EXTERNAL_ARTICA_HELPER"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["EXTERNAL_ARTICA_HELPER"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["LDAP_AUTH"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["LDAP_AUTH"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["LDAP_GROUP"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["LDAP_GROUP"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["IDENT_SERVICE"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["IDENT_SERVICE"];}
										
			if($this->ASROOT){
				$acls_rules=$GLOBALS["aclGen"]->build_http_access(0);
				echo "Starting......: ".date("H:i:s")." [ACLS]: ". count($acls_rules)." rule(s) from engine (Line ".__LINE__.")\n";}
				if(count($acls_rules)>0){
					$conf[]="# Builded acls from engine...";
					$conf[]=@implode("\n", $acls_rules);
				}
					
				if($this->SquidBubbleMode==1){
					$q=new mysql_squid_builder();
					$sql="SELECT * FROM webfilters_sqaclsports WHERE enabled=1 ORDER BY aclport";
					$results = $q->QUERY_SQL($sql);
					while ($ligne = mysql_fetch_assoc($results)) {
						$conf[]="#--------- BUBBLE: ACLS build_http_access() {$ligne["portname"]} (line ".__LINE__.")";
						$acls_rules=$GLOBALS["aclGen"]->build_http_access($ligne["aclport"]);
						$DENYBUBBLE[]="http_access deny MyPort{$ligne["aclport"]} all";
						if(count($acls_rules)>0){
							$conf[]="# Builded acls from engine {$ligne["portname"]}...";
							$conf[]=@implode("\n", $acls_rules);
						}
					}
				}
					
				if(isset($GLOBALS["HTTP_ACCESS"]["ISP_NETWORK"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["ISP_NETWORK"];}
				if(isset($GLOBALS["HTTP_ACCESS"]["OFFICE_NETWORK"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["OFFICE_NETWORK"];}
				if(isset($GLOBALS["HTTP_ACCESS_REVERSE"])){$conf[]=$GLOBALS["HTTP_ACCESS_REVERSE"];}
				
				$conf[]="http_access deny {$this->MyStandPort}all";
				if($this->SquidUrgency==0){
					//$conf[]="http_reply_access deny FileWatcher all";
					//$conf[]="http_reply_access allow all";
				}
				
				
				while (list ($num, $val) = each ($conf) ){if(trim($val)==null){continue;}$f[]=$val;}
				if($this->SquidBubbleMode==1){
					while (list ($num, $val) = each ($DENYBUBBLE) ){
						$f[]=$val;
					}
				}
			
			$final="\n".@implode("\n",$f)."\n";
			$GLOBALS[__FUNCTION__]=$final;
			return $final;
		}
		
		private function SaveCertificateDefault($nginx=false,$coova=false,$squid27=false){
			$prefixOut="Starting......: ".date("H:i:s")." [  SSL  ]: Default";
			if($nginx){$prefixOut="Starting......: ".date("H:i:s")." [INIT]: nginx SSL: Default";}

			
			@mkdir("/etc/squid3/ssl",0755,true);
			if(!is_file("/etc/squid3/ssl/cacert.pem")){
				if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
				echo "$prefixOut invoke to create a faked Self-signed certificate\n";
				shell_exec($unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.squid.php --certificate");
			}
			
			$CertToken="cert=/etc/squid3/ssl/cacert.pem";
			$KeyToken="key=/etc/squid3/ssl/privkey.pem";
			
			$certificates=" $CertToken $KeyToken";
			if($coova){
				return  "HS_SSLCERTFILE=/etc/squid3/ssl/cacert.pem\nHS_SSLKEYFILE=/etc/squid3/ssl/privkey.pem";
			}
			
			if($nginx){return  "\tssl_certificate /etc/squid3/ssl/cacert.pem;\n\tssl_certificate_key /etc/squid3/ssl/privkey.pem;";}
			
			if($squid27){
				$tt[]="sslproxy_client_certificate /etc/squid3/ssl/cacert.pem";
				$tt[]="sslproxy_client_key  /etc/squid3/ssl/privkey.pem";
				$tt[]="sslproxy_version 1";
				$tt[]="sslproxy_flags DONT_VERIFY_PEER";
				echo "$prefixOut return 2.7v\n";
				return array(@implode("\n", $tt),array("certificate"=>"/etc/squid3/ssl/cacert.pem","key"=>"/etc/squid3/ssl/privkey.pem"));
			}
			
			
			return $certificates;
			
		}
		
		private function https_port_reverse(){
			if(!$this->ASROOT){return;}
			
			$params=$this->compilation_params();
			if(!isset($params["enable-ssl"])){
				echo "Starting......: ".date("H:i:s")." [SSL]: FATAL: Not compiled...\n";
				return null;
			}
			
			if($this->ssl_port==0){$this->ssl_port=443;}
			$certificate=trim($this->certificate_center);
			
			
			if($this->isNGnx()){
				echo "Starting......: ".date("H:i:s")." [REVERSE]: Nginx is installed and enabled...\n";
				if($this->listen_port==80){$this->listen_port=8080;}
				if($this->ssl_port==443){$this->ssl_port=4443;}
			}
				
						
			
			
			$defaultWWW=null;
			$cert=null;
			echo "Starting......: ".date("H:i:s")." [REVERSE]: Port:$this->ssl_port certificate:`$certificate`\n";
			if($certificate<>null){$tokens=$this->SaveCertificate($certificate);}
			if($certificate==null){
				echo "Starting......: ".date("H:i:s")." [REVERSE]: No certificate set for the proxy itself, set the default one\n";
				$tokens=$this->SaveCertificateDefault();
			}
				
			if($this->SquidReverseDefaultWebSite<>null){$defaultWWW=" defaultsite=$this->SquidReverseDefaultWebSite ";}
			return "https_port $this->ssl_port accel $tokens vhost vport$defaultWWW";
		}
		
		
private function build_dynamic_certificate($pathToCert){
	$f=@file_get_contents($pathToCert);
	if(!preg_match("#-----BEGIN CERTIFICATE-----(.*?)-----END CERTIFICATE-----#s", $f,$re)){
		echo "Starting......: ".date("H:i:s")." [SSL] :: Unable to stat Certificate in $pathToCert\n";
		return;}
	$certificate="-----BEGIN CERTIFICATE-----{$re[1]}-----END CERTIFICATE-----";
	if(!preg_match("#-----BEGIN RSA PRIVATE KEY-----(.*?)-----END RSA PRIVATE KEY-----#s", $f,$re)){
		echo "Starting......: ".date("H:i:s")." [SSL] :: Unable to stat PRIVATE KEY in $pathToCert\n";
		return;}
	$privatekey="-----BEGIN RSA PRIVATE KEY-----{$re[1]}-----END RSA PRIVATE KEY-----";
	@file_put_contents("$pathToCert.dyn","$privatekey\n$certificate\n");
	return "$pathToCert.dyn";
	
	
}

private function BuildSquidCertificateCRTD_default($commname=null){
	if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
	$sock=new sockets();
	if($commname==null){$commname=$unix->hostname_g();}
	$commname=strtolower($commname);
	$openssl=$unix->find_program("openssl");
	$certificate_filename=md5($commname);
	
	$keyout="/etc/squid3/ssl/$certificate_filename.dyn";
	if(is_file($keyout)){
		echo "Starting......: ".date("H:i:s")." [SSL]: $keyout OK\n";
		if(!is_file("/usr/share/artica-postfix/ressources/squid/certificate.der")){
			echo "Starting......: ".date("H:i:s")." [SSL]: create certificate.der\n";
			$cmd="$openssl x509 -in $keyout -outform DER -out /usr/share/artica-postfix/ressources/squid/certificate.der";
			echo "Starting......: ".date("H:i:s")." [SSL]: $cmd\n";
			shell_exec($cmd);
			if(!is_file("/usr/share/artica-postfix/ressources/squid/certificate.der")){ echo "Starting......: ".date("H:i:s")." [SSL]: Warning /usr/share/artica-postfix/ressources/squid/certificate.der no such file\n"; }
		}
		if(is_file("/usr/share/artica-postfix/ressources/squid/certificate.der")){
			if(@unlink("/usr/share/artica-postfix/ressources/squid/hotspot-$certificate_filename.der")){
				@copy("/usr/share/artica-postfix/ressources/squid/certificate.der", "/usr/share/artica-postfix/ressources/squid/hotspot-$certificate_filename.der");
			}
		}
		
		echo "Starting......: ".date("H:i:s")." [SSL]: Already generated OK\n";
		return " generate-host-certificates=on dynamic_cert_mem_cache_size=4MB cert=$keyout";
	}
	
	$DefaultSSLParams=unserialize(base64_decode($sock->GET_INFO("DefaultSSLParams")));
	$LicenseInfos=unserialize(base64_decode($sock->GET_INFO("LicenseInfos")));
	$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
	if($DefaultSSLParams["countryName"]==null){$DefaultSSLParams["countryName"]="US";}
	if($DefaultSSLParams["stateOrProvinceName"]==null){$DefaultSSLParams["stateOrProvinceName"]=$WizardSavedSettings["city"];}
	if($DefaultSSLParams["localityName"]==null){$DefaultSSLParams["localityName"]=$WizardSavedSettings["city"];}
	if($DefaultSSLParams["organizationName"]==null){$DefaultSSLParams["organizationName"]=$LicenseInfos["COMPANY"];}
	if($DefaultSSLParams["organizationalUnitName"]==null){$DefaultSSLParams["organizationalUnitName"]=$WizardSavedSettings["organization"];}
	if(!is_numeric($DefaultSSLParams["CertificateMaxDays"])){$DefaultSSLParams["CertificateMaxDays"]=730;}
	
	$openssl=$unix->find_program("openssl");
	$countryName=$DefaultSSLParams["countryName"];
	$stateOrProvinceName=$DefaultSSLParams["stateOrProvinceName"];
	$localityName=$DefaultSSLParams["localityName"];
	$OrganizationName=$DefaultSSLParams["organizationName"];
	$OrganizationalUnit=$DefaultSSLParams["organizationalUnitName"];
	$CertificateMaxDays=$DefaultSSLParams["CertificateMaxDays"];

	$DefaultSubject="-subj \"/C=$countryName/ST=$stateOrProvinceName/L=$localityName/O=$OrganizationName/OU=$OrganizationalUnit/CN=*\"";
	echo "Starting......: ".date("H:i:s")." [SSL]: Building certificate $DefaultSubject\n";
	
	$cmd="$openssl req -new -newkey rsa:1024 $DefaultSubject -days 365 -nodes -x509 -keyout $keyout -out $keyout";
	shell_exec($cmd);
	
	echo "Starting......: ".date("H:i:s")." [SSL]: $cmd\n";
	
	if(!is_file("$keyout")){
		echo "Starting......: ".date("H:i:s")." [SSL]: Warning !!! $keyout no such file\n";
		return null;}
	
	echo "Starting......: ".date("H:i:s")." [SSL]: create certificate.der\n";
	$cmd="$openssl x509 -in $keyout -outform DER -out /usr/share/artica-postfix/ressources/squid/certificate.der";
	echo "Starting......: ".date("H:i:s")." [SSL]: $cmd\n";
	shell_exec($cmd);
	if(!is_file("/usr/share/artica-postfix/ressources/squid/certificate.der")){
		echo "Starting......: ".date("H:i:s")." [SSL]: Warning /usr/share/artica-postfix/ressources/squid/certificate.der no such file\n";
	}
	
	if(@unlink("/usr/share/artica-postfix/ressources/squid/hotspot-$certificate_filename.der")){
		@copy("/usr/share/artica-postfix/ressources/squid/certificate.der", "/usr/share/artica-postfix/ressources/squid/hotspot-$certificate_filename.der");
	}
	
	if(!is_file("/etc/squid3/ssl/dhparam.pem")){
		system("$openssl dhparam -outform PEM -out /etc/squid3/ssl/dhparam.pem 2048");
		
	}
	
	return " generate-host-certificates=on dynamic_cert_mem_cache_size=4MB cert=$keyout dhparams=/etc/squid3/ssl/dhparam.pem";
}


private function  BuildSquidCertificateCRTD($commname){
	$ketMEM="BuildSquidCertificateCRTD$commname";
	if(isset($GLOBALS[$ketMEM])){return $GLOBALS[$ketMEM];}
	
	if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
	$openssl=$unix->find_program("openssl");
	$commname=strtolower($commname);
	$certificate_filename=md5($commname);
	$keyout="/etc/squid3/ssl/$certificate_filename.dyn";
	
	$q=new mysql();
	$sql="SELECT `UsePrivKeyCrt`,`SquidCert`,`crt`,`privkey`,`Squidkey`,`bundle` FROM sslcertificates WHERE CommonName='$commname'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	
	if(!isset($ligne["UsePrivKeyCrt"])){$ligne["UsePrivKeyCrt"]=0;}
	if(!isset($ligne["bundle"])){$ligne["bundle"]=null;}
	if(!isset($ligne["privkey"])){$ligne["privkey"]=null;}
	if(!isset($ligne["crt"])){$ligne["crt"]=null;}
	
	
	if(!$q->ok){
			$this->BuildSquidCertificateCRTD_LOGS[]="# MySQL ERROR!!! [".__LINE__."]";
			$this->BuildSquidCertificateCRTD_LOGS[]="#". str_replace("\n", " ", $q->mysql_error);
	}
	$this->BuildSquidCertificateCRTD_LOGS[]="# Key out = $keyout  [".__LINE__."]";
	$this->BuildSquidCertificateCRTD_LOGS[]="# CommonName = $commname UsePrivKeyCrt={$ligne["UsePrivKeyCrt"]} [".__LINE__."]";
	
	$ligne["privkey"]=trim($ligne["privkey"]);
	$ligne["crt"]=trim($ligne["crt"]);
	$ligne["bundle"]=trim($ligne["bundle"]);
	
	if(intval($ligne["UsePrivKeyCrt"])==1){
		$strlen=strlen($ligne["crt"]);
		$strlen2=strlen($ligne["privkey"]);
		$strlen3=strlen($ligne["bundle"]);
		$this->BuildSquidCertificateCRTD_LOGS[]="# CRT = $strlen Bytes";
		$this->BuildSquidCertificateCRTD_LOGS[]="# privkey = $strlen2 Bytes";
		$this->BuildSquidCertificateCRTD_LOGS[]="# bundle = $strlen3 Bytes";
		if($strlen<20){$ligne["UsePrivKeyCrt"]=0;}
		if($strlen2<20){$ligne["UsePrivKeyCrt"]=0;}
		if($strlen3>20){
			@file_put_contents("$keyout", "{$ligne["crt"]}\n{$ligne["bundle"]}\n{$ligne["privkey"]}");
		}else{
			@file_put_contents("$keyout", "{$ligne["crt"]}\n{$ligne["privkey"]}\n");
		}
		
		
	}
	
	if(intval($ligne["UsePrivKeyCrt"])==0){
		$GLOBALS["BuildSquidCertificate"][]="UsePrivKeyCrt = 0 [".__LINE__."]";
		$sql="SELECT `UsePrivKeyCrt`,`SquidCert`,`Squidkey`,`crt`,`privkey`,`srca` FROM sslcertificates WHERE CommonName='$commname'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$privatekey=$ligne["srca"];
		$strlen=strlen($ligne["SquidCert"]);
		$strlen2=strlen($privatekey);
		$strlen3=strlen($ligne["Squidkey"]);
		$this->BuildSquidCertificateCRTD_LOGS[]="# CRT = $strlen Bytes";
		$this->BuildSquidCertificateCRTD_LOGS[]="# srca = $strlen2 Bytes";
		$this->BuildSquidCertificateCRTD_LOGS[]="# Squidkey = $strlen3 Bytes";
		
		if($strlen2==0){
			if($strlen3>20){$privatekey=$ligne["Squidkey"];}
		}
		
		if($ligne["SquidCert"]==null){
			$GLOBALS["BuildSquidCertificate"][]="$commname SquidCert IS NULL in `sslcertificates` table !!? [".__LINE__."]";
			echo "Starting......: ".date("H:i:s")." [SSL] :: $commname SquidCert IS NULL in `sslcertificates` table\n";
			echo "Starting......: ".date("H:i:s")." [SSL] :: $commname Build a default one\n";
			$GLOBALS[$ketMEM]= $this->BuildSquidCertificateCRTD_default();
			return $GLOBALS[$ketMEM];
		}
		
		
		$GLOBALS["BuildSquidCertificate"][]="$commname -> $keyout [".__LINE__."]";
		@file_put_contents("$keyout", "$privatekey\n".$ligne["SquidCert"]."\n");
	}
	
	
	$cmd="$openssl x509 -in $keyout -outform DER -out /usr/share/artica-postfix/ressources/squid/certificate.der";
	shell_exec($cmd);
	
	
	if(@unlink("/usr/share/artica-postfix/ressources/squid/hotspot-$certificate_filename.der")){
		@copy("/usr/share/artica-postfix/ressources/squid/certificate.der", "/usr/share/artica-postfix/ressources/squid/hotspot-$certificate_filename.der");
	}
	
	if(!is_file("/etc/squid3/ssl/dhparam.pem")){
		system("$openssl dhparam -outform PEM -out /etc/squid3/ssl/dhparam.pem 2048");
	
	}
	
	$GLOBALS[$ketMEM]=" generate-host-certificates=on dynamic_cert_mem_cache_size=4MB cert=$keyout dhparams=/etc/squid3/ssl/dhparam.pem";
	
	return $GLOBALS[$ketMEM];

	
}


public function BuildSquidCertificate($commname=null){
	if(!$this->ASROOT){return;}
	$GLOBALS["BuildSquidCertificate"]=array();
	@mkdir("/etc/squid3",0755,true);
	@mkdir("/etc/squid3/ssl",0755,true);
	@mkdir("/usr/share/artica-postfix/ressources/squid",0755,true);
	if($commname==null){
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
		$commname=$unix->hostname_g();
	}
	
	$GLOBALS["BuildSquidCertificate"][]="sslcrtd_program = $this->sslcrtd_program EnableSquidSSLCRTD=$this->EnableSquidSSLCRTD [".__LINE__."]";
	
	if(strlen($this->sslcrtd_program)==0){$this->EnableSquidSSLCRTD=0;}
	
	echo "Starting......: ".date("H:i:s")." [SSL]: CommonName: `$commname`, EnableSquidSSLCRTD=$this->EnableSquidSSLCRTD\n";
	
	$this->BuildSquidCertificateCRTD_LOGS[]="# EnableSquidSSLCRTD = $this->EnableSquidSSLCRTD [".__LINE__."]";
	if($this->EnableSquidSSLCRTD==1){
		$GLOBALS["BuildSquidCertificate"][]="CommonName: `$commname` -> BuildSquidCertificateCRTD($commname)";
		$line= $this->BuildSquidCertificateCRTD($commname);
		$GLOBALS["BuildSquidCertificate"][]="Return: $line [".__LINE__."]";
		$this->BuildSquidCertificateCRTD_LOGS[]="# Return: $line  [".__LINE__."]";
		return $line;
	}
	
	
	$this->BuildSquidCertificateCRTD_LOGS[]="# Passed -> `$commname` -> SaveCertificate($commname) [".__LINE__."]";
	$GLOBALS["BuildSquidCertificate"][]="CommonName: `$commname` -> SaveCertificate($commname)";
	return $this->SaveCertificate($commname);
	
}

		
public function SaveCertificate($commname=null,$cache_peer=false,$nginx=false,$coova=false,$squid27=false){
		if(!$this->ASROOT){return;}
		if($commname==null){
			if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
			$commname=$unix->hostname_g();
		}
		$EnableSquidSSLCRTD=$this->EnableSquidSSLCRTD;
		
		$prefixOut="Starting......: ".date("H:i:s")." [  SSL  ]: $commname";
		if($nginx){
			$prefixOut="Starting......: ".date("H:i:s")." [INIT]: nginx SSL: $commname";
			$EnableSquidSSLCRTD=0;
		}
		
		
		@mkdir("/etc/squid3/ssl",0755,true);
		$cert_token="cert";
		$key_token="key";
		if($cache_peer){$cert_token="sslcert";$key_token="sslkey";}
		$q=new mysql();
		if(!$q->FIELD_EXISTS("sslcertificates","DynamicCert","artica_backup")){$sql="ALTER TABLE `sslcertificates` ADD `DynamicCert` TEXT NOT NULL";$q->QUERY_SQL($sql,'artica_backup');}
		if(!$q->FIELD_EXISTS("sslcertificates","UsePrivKeyCrt","artica_backup")){$sql="ALTER TABLE `sslcertificates` ADD `UsePrivKeyCrt` smallint(1) DEFAULT 0";$q->QUERY_SQL($sql,'artica_backup');}
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
		
		
		$certificate_filename=md5($commname);
		$sql="SELECT `UsePrivKeyCrt`,`crt`,`privkey`,`SquidCert`,`Squidkey`,`bundle` FROM sslcertificates WHERE CommonName='$commname'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		
		if($ligne["UsePrivKeyCrt"]==1){
			@file_put_contents("/etc/squid3/$certificate_filename.pem", $ligne["crt"]);
			@file_put_contents("/etc/squid3/$certificate_filename.key", $ligne["privkey"]);
		}
		
		
		
		if(is_file("/etc/squid3/$certificate_filename.pem")){
				if($unix->file_time_min("/etc/squid3/$certificate_filename.pem")<5){
					
					if($coova){
						return  "HS_SSLCERTFILE=/etc/squid3/$certificate_filename.pem\nHS_SSLKEYFILE=/etc/squid3/$certificate_filename.key";
					}
					
					if($nginx){
						echo "Starting......: ".date("H:i:s")." [INIT]: nginx, using certificate /etc/squid3/$certificate_filename.pem\n";
						echo "Starting......: ".date("H:i:s")." [INIT]: nginx, using Key /etc/squid3/$certificate_filename.key\n";
						return  "\tssl_certificate /etc/squid3/$certificate_filename.pem;\n\tssl_certificate_key /etc/squid3/$certificate_filename.key;"; 
					}
					$KeyToken="key=/etc/squid3/$certificate_filename.key";
					$CertToken="cert=/etc/squid3/$certificate_filename.pem";
					
					return " $CertToken $KeyToken";
				}
			}
			
			
			
		
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." [SSL] :: $commname = $q->mysql_error\n";}
			
			if(strlen($ligne["SquidCert"])==0){
				if(strlen($ligne["crt"])>10){
					$ligne["SquidCert"]=$ligne["crt"];
					$ligne["Squidkey"]=$ligne["privkey"];
				}
			}
			
			
			echo "$prefixOut certificate: ". strlen($ligne["SquidCert"])." bytes...\n";
			echo "$prefixOut key........: ". strlen($ligne["Squidkey"])." bytes...\n";
			echo "$prefixOut Chain......: ". strlen($ligne["bundle"])." bytes...\n";
			
			$certificate_filename=md5($commname);
			$GLOBALS["BuildSquidCertificate"][]="OK $commname = $certificate_filename [".__LINE__."]";
			
			if(strlen($ligne["SquidCert"])==0){
				if($cache_peer){return null;}
				echo "$prefixOut [FATAL]: Certificate not set for $commname, return default one\n";
				$GLOBALS["BuildSquidCertificate"][]="Certificate not set for $commname, return default one [".__LINE__."]";
				return $this->SaveCertificateDefault($nginx,$coova,$squid27);
			}
			
			if( strlen($ligne["Squidkey"])==0 ){
				echo "$prefixOut [FATAL]: Key does not exists swith to default one\n";
				$GLOBALS["BuildSquidCertificate"][]="Key does not exists swith to default one [".__LINE__."]";
				return $this->SaveCertificateDefault($nginx,$coova,$squid27);
			}			
		
			if( strlen($ligne["bundle"])>10 ){
				echo "$prefixOut Integrate chain certificate inside certificate\n";
				$GLOBALS["BuildSquidCertificate"][]="OK Integrate chain certificate inside certificate [".__LINE__."]";
				$ligne["SquidCert"]=$ligne["SquidCert"].$ligne["Squidkey"];
			}
			@file_put_contents("/etc/squid3/$certificate_filename.crt", $ligne["SquidCert"]);
			@file_put_contents("/etc/squid3/$certificate_filename.key", $ligne["Squidkey"]);
			
			if($nginx){return  "\tssl_certificate /etc/squid3/$certificate_filename.crt;\n\tssl_certificate_key /etc/squid3/$certificate_filename.key;";}
			
			if($coova){
				return  "HS_SSLCERTFILE=/etc/squid3/$certificate_filename.crt\nHS_SSLKEYFILE=/etc/squid3/$certificate_filename.key";
			}
			
			if($squid27){
				$tt[]="sslproxy_client_certificate /etc/squid3/$certificate_filename.crt";
				$tt[]="sslproxy_client_key  /etc/squid3/$certificate_filename.key";
				$tt[]="sslproxy_version 1";
				$tt[]="sslproxy_flags DONT_VERIFY_PEER";
				return array(@implode("\n", $tt),array("certificate"=>"/etc/squid3/$certificate_filename.crt","key"=>"/etc/squid3/$certificate_filename.key"));
			}
			
			$KeyToken2="key=/etc/squid3/$certificate_filename.key";
			$CertToken2="$cert_token=/etc/squid3/$certificate_filename.crt";
			return " $CertToken2 $KeyToken2";		
		}
		
		private function NETWORK_ALL_INTERFACES(){
			if(isset($GLOBALS["NETWORK_ALL_INTERFACES"])){return $GLOBALS["NETWORK_ALL_INTERFACES"];}
			if(!$this->ASROOT){return;}
			if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
			$GLOBALS["NETWORK_ALL_INTERFACES"]=$unix->NETWORK_ALL_INTERFACES(true);
			$GLOBALS["NETWORK_ALL_INTERFACES"]["0.0.0.0"]=true;
		}
		
		

		

		
		
		private function https_certificates_token(){
			echo "Starting......: ".date("H:i:s")." [SSL]: $this->certificate_center\n";
			return $this->BuildSquidCertificate($this->certificate_center);
		}
		
		
		private function mgr_port(){
			if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
			$token=$this->myport_token();
			
			
			if($this->MGR_LISTEN_PORT==0){
				$sock=new sockets();
				$this->MGR_LISTEN_PORT=$sock->GET_INFO("SquidMgrListenPort");
				if(!is_numeric($this->MGR_LISTEN_PORT)){
					$this->MGR_LISTEN_PORT=rand(50000,64000);
					$sock->SET_INFO("SquidMgrListenPort", $this->MGR_LISTEN_PORT);
				}
			}
			$f[]="#";
			$f[]="# --------- Serve Artica for status and checkup Squid service ---------";
			$f[]="acl MgRPort myportname MyManagerPort";
			$f[]="acl MgrInfoUri urlpath_regex /squid-internal-dynamic/";
			$f[]="acl MgrInfoUri urlpath_regex /squid-internal-mgr/";
			$f[]="acl MgrInfoUri2 urlpath_regex /squid-internal-periodic/";
			$f[]="acl squidclient proto cache_object";
			$f[]="acl PURGE method PURGE";
			$f[]="acl MgRClient src 127.0.0.1";
			$ips=$unix->NETWORK_ALL_INTERFACES(true);
			while (list ($ipI, $ligne) = each ($ips) ){
				if($ipI=="127.0.0.1"){continue;}
				$f[]="acl MgRClient src $ipI";
				
			}
			
			
			$f[]="acl MgRDest dst 127.0.0.1\n";
			$f[]="http_access allow MgrInfoUri2 all";
			$f[]="http_access allow MgRPort squidclient all";
			$f[]="http_access allow MgRPort PURGE all";
			$f[]="http_access allow MgRPort MgRClient all";
			$f[]="http_access deny  MgRPort !MgRClient";
			
			$http_access_debugport=$this->http_access_debugport();
			if($http_access_debugport<>null){$f[]=$http_access_debugport."\n";}
			$f[]="";
			$f[]="";
			$f[]="# ------------------------------------------------------------------------";
			$f[]="";
			$f[]="";
			return @implode("\n", $f);
			
		}
		
		private function ssl_bump_token(){

			if($this->IS_32){return "ssl-bump";}
			if($this->IS_33){return "ssl-bump";}
			if($this->IS_34){return "ssl-bump";}
			if($this->intvalVersion>316){return "ssl-bump";}
			return "sslBump";
			
		}
		
		
		public function ssl_bump_line($certificate_center){
			if(!is_numeric($this->intvalVersion)){$this->CheckVersion();}
			if($this->IS_27){
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SSL]: SSLBUMP:: Not supported in 2.7x\n";}
				return null;
			}
				
			if($this->intvalVersion<290){
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SSL]: SSLBUMP:: Not supported in 2.x\n";}
				return null;
			}
			
			$ADD=array();
			$token=" ".$this->ssl_bump_token();
			$certificates=$this->BuildSquidCertificate($certificate_center);
			if(strlen($this->sslcrtd_program)>3){
					if($this->EnableSquidSSLCRTD==1){
						if(!isset($GLOBALS["sslcrtd_program_added"])){
							@mkdir("/var/lib/squid/session/ssl",0755,true);
							@chown("/var/lib/squid/session/ssl","squid");
							@chgrp("/var/lib/squid/session/ssl", "squid");
							$ADD[]="# Added by ".__CLASS__."/".__FUNCTION__." Line:".__LINE__;
							$GLOBALS["sslcrtd_program_added"]=true;
							$ADD[]="sslcrtd_program $this->sslcrtd_program -s /var/lib/squid/session/ssl/ssl_db -M 4MB";
							shell_exec("$this->sslcrtd_program -c -s /var/lib/squid/session/ssl/ssl_db >/dev/null 2>&1");
						}
					}
				}
				
			return "$token $certificates\n".@implode("\n", $ADD);
				
		}
		
		
		private function ssl_bump_port(){
			if($this->SquidUrgency==1){return null;}
			$addedlog=null;
			$this->sslbump_token_debug=array();
			if(!is_numeric($this->intvalVersion)){$this->CheckVersion();}
			if($this->IS_32){$addedlog=";is 3.2x:yes";}
			if($this->IS_31){$addedlog=$addedlog.";is 3.1x:yes";}
			if($this->IS_27){$addedlog=$addedlog.";is 2.7x:yes";}
			if($this->IS_33){$addedlog=$addedlog.";is 3.3x:yes";}
			
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SSL]: Squid Check *** SSL BUMP - [$this->intvalVersion$addedlog] - ***\n";}
			
			if($this->IS_27){
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SSL]: SSLBUMP:: Not supported in 2.7x\n";}
				return null;
			}
			
			if($this->intvalVersion<290){
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SSL]: SSLBUMP:: Not supported in 2.x\n";}
				return null;		
			}			
			$sock=new sockets();
			$EnableSSLOnStandardPort=intval($sock->GET_INFO("EnableSSLOnStandardPort"));
			$this->sslbump_token_debug[]="# EnableSSLOnStandardPort = $EnableSSLOnStandardPort [".__LINE__."]";
			
			if($EnableSSLOnStandardPort==0){
				$this->sslbump_token_debug[]="# SSL_BUMP = $this->SSL_BUMP [".__LINE__."]";
				if(!is_numeric($this->SSL_BUMP)){return null;}
				if($this->SSL_BUMP==null){return null;}
				if($this->SSL_BUMP==0){return null;}
			}
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SSL]: SSLBUMP:: ENABLED ($this->SSL_BUMP)\n";}
			
			writelogs("SQUID INTVAL: $this->intvalVersion",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			
			$this->sslbump_token_debug[]="# Certificate = $this->certificate_center [".__LINE__."]";
			
			$certificates=$this->BuildSquidCertificate($this->certificate_center);
			
			$token=" ".$this->ssl_bump_token();
			$this->sslbump_token_debug[]="# Token = $token [".__LINE__."]";
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SSL]: SSLBUMP:: Token $token L.".__LINE__."\n";}
			$f[]="$token $certificates";
			if(strlen($this->sslcrtd_program)>3){
				if($this->EnableSquidSSLCRTD==1){
					if(!isset($GLOBALS["sslcrtd_program_added"])){
						@mkdir("/var/lib/squid/session/ssl",0755,true);
						@chown("/var/lib/squid/session/ssl","squid");
						@chgrp("/var/lib/squid/session/ssl", "squid");
						$f[]="# Added by ".__CLASS__."/".__FUNCTION__." Line:".__LINE__;
						$GLOBALS["sslcrtd_program_added"]=true;
						$f[]="sslcrtd_program $this->sslcrtd_program -s /var/lib/squid/session/ssl/ssl_db -M 4MB";
						shell_exec("$this->sslcrtd_program -c -s /var/lib/squid/session/ssl/ssl_db >/dev/null 2>&1");
					}
				}
			}
			
			return @implode("\n", $f);
			
		}
		
		
		private function ChildsProxyACls(){
			$q=new mysql();
			
		}
		
		
		
		private function ACLS_GOOGLE(){
			$sock=new sockets();
			$AllowSquidGoogle=intval($sock->GET_INFO("AllowSquidGoogle"));
			if($AllowSquidGoogle==0){return;}
			$ips=new products_ip_ranges();
			$ips->google_networks();
			$f[]="# --------- AllowSquidGoogle = $AllowSquidGoogle [".__LINE__."]";
			$f[]="acl google_ips dst \"/etc/squid3/acls/google-nets.acl\"";
			$f[]="acl google_www dstdomain  .google.com .google.fr .google.de .google.be .google.co.uk";
			return @implode("\n", $f)."\n";
		}

		
		private function ACLS_BROWSERS(){
			$f=new useragents();
			return $f->ACLS_ALL()."\n";
			
		}
		
		private function ACLS_DROPBOX(){
			$sock=new sockets();
			$AllowSquidSSLDropBox=intval($sock->GET_INFO("AllowSquidSSLDropBox"));
			$AllowSquidDropBox=intval($sock->GET_INFO("AllowSquidDropBox"));
			if($AllowSquidDropBox==1){$AllowSquidSSLDropBox=1;}
			if($AllowSquidSSLDropBox==1){$AllowSquidDropBox=1;}
			if($AllowSquidDropBox==0){return;}
			$ips=new products_ip_ranges();
			$ips->dropbox_networks();
			$f[]="# --------- AllowSquidSSLDropBox = $AllowSquidDropBox AllowSquidDropBox = $AllowSquidDropBox [".__LINE__."]";
			$f[]="acl dropbox_ips dst \"/etc/squid3/acls/dropbox-nets.acl\"";
			$f[]="acl dropbox_www dstdomain  .dropbox.com";
			return @implode("\n", $f)."\n";
		}
		
		private function ACLS_SKYPE(){
			$sock=new sockets();
			$AllowSquidSSLSkype=intval($sock->GET_INFO("AllowSquidSSLSkype"));
			$AllowSquidSkype=intval($sock->GET_INFO("AllowSquidSkype"));
			if($AllowSquidSkype==1){$AllowSquidSSLSkype=1;}
			if($AllowSquidSSLSkype==1){$AllowSquidSkype=1;}
			if($AllowSquidSkype==0){return;}
			$ips=new products_ip_ranges();
			$ips->skype_networks();
			$f[]="# --------- AllowSquidSSLSkype = $AllowSquidSSLSkype AllowSquidSSLSkype = $AllowSquidSSLSkype [".__LINE__."]";
			$f[]="acl skype_www dstdomain  .live.com .skypeassets.com .skype.net .skype.com .trouter.io";
			$f[]="acl skype_ips dst \"/etc/squid3/acls/skype-nets.acl\"";
			return @implode("\n", $f)."\n";
		}

		private function ACLS_OFFICE365(){
			$sock=new sockets();
			
			$AllowSquidOffice365=intval($sock->GET_INFO("AllowSquidOffice365"));
			if($AllowSquidOffice365==0){return;}
			$ips=new products_ip_ranges();
			
			$ips->office365_networks(); 
			$ips->office365_domains(); 
			
			
			$f[]="# --------- Office365 [".__LINE__."]";
			$f[]="acl office365_www dstdomain \"/etc/squid3/acls/office365-domains.acl\"";
			$f[]="acl office365_ips dst \"/etc/squid3/acls/office365-nets.acl\"";
			return @implode("\n", $f)."\n";
		}	

		
		
		
	

		
		
		
		
		public function get_ssl_port(){
			
			if($this->ssl_port>0){
				if($this->isNGnx()){if($this->ssl_port==443){$this->ssl_port=10443;}}
				return $this->ssl_port;
			}
			
			if($this->second_listen_portForTransparent>0){ 
				$this->ssl_port=$this->second_listen_portForTransparent+1;
				$this->SaveToLdap();
				return $this->ssl_port;
			}
			
			if($this->ssl_port==0){
				$this->ssl_port=$this->listen_port+2;
				return $this->ssl_port;
			}
		}
		
		private function MICROSOFT_ACLS(){
			if($this->SquidUrgency==1){return;}
			$f[]="acl wuCONNECT dstdomain .update.microsoft.com";
			$f[]="acl windowsupdate dstdomain .update.microsoft.com";
			$f[]="acl windowsupdate dstdomain .metaservices.microsoft.com";
			$f[]="acl windowsupdate dstdomain c.microsoft.com";
			$f[]="acl windowsupdate dstdomain .windowsupdate.com";
			$f[]="acl windowsupdate dstdomain wustat.windows.com";
			$f[]="acl windowsupdate dstdomain ntservicepack.microsoft.com";
			$f[]="acl windowsupdate dstdomain windowsupdate.microsoft.com";
			$f[]="acl windowsupdate dstdomain office.microsoft.com";
			$f[]="acl windowsupdate dstdomain crl.microsoft.com";
			$f[]="";
			$f[]="http_access allow CONNECT wuCONNECT all";
			$f[]="http_access allow windowsupdate all";
			$f[]="";
			@file_put_contents("/etc/squid3/microsoft.conf", @implode("\n", $f));
			
		}
		
		
private function ssl_bump_no_decrypt(){
	
	
	$deny="deny";
	$allow="allow";
	
	if($this->IS_33){
		$deny="none";
		$allow="client-first";
		if($this->EnableSquidSSLCRTD==1){
			$allow="server-first";
		}
	}
	
	$sql="SELECT website_name FROM squid_ssl WHERE `type`='ssl-bump-wl' AND enabled=1 ORDER BY website_name LIMIT 0,50";
	$q=new mysql();	
	$results=$q->QUERY_SQL($sql,"artica_backup");
	$c=0;
	if(mysql_num_rows($results)>0){
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if($ligne["website_name"]==null){continue;}
			$c=$c+1;
			$www=$this->BaseNameWebSite($ligne["website_name"]);
			if($www==null){continue;}
			if(isset($ALREADY[$www])){continue;}
			$ALREADY[$www]=true;
			$www_table[]=$www;
		}
	}
	
	
	$acl=new squid_acls();
	$array_www=$acl->clean_dstdomains($www_table);
	$array[]="# no_ssl_bump ".count($array_www)." items (".count($www_table)." source items) [".__LINE__."]";
	@file_put_contents("/etc/squid3/no_ssl_bump.acl", @implode("\n", $array_www));
	if($this->ASROOT){echo "Starting......: ".date("H:i:s")." SSLBUMP:: not bumped for ". count($array_www)." domains\n";}
	$array[]="acl no_ssl_bump dstdomain \"/etc/squid3/no_ssl_bump.acl\"";
	$array[]="ssl_bump $deny no_ssl_bump";
	$array[]="ssl_bump $allow all";
	return @implode("\n", $array);
	
}
		
private function ssl_bump_to_decrypt(){
	$sock=new sockets();
	$SquidSSLUrgency=intval($sock->GET_INFO("SquidSSLUrgency"));
	if($SquidSSLUrgency==1){return;}
	$sql="SELECT website_name FROM squid_ssl WHERE `type`='ssl-bump-enc' AND enabled=1 ORDER BY website_name LIMIT 0,50";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	$c=0;
	
	$deny="deny";
	$allow="allow";
		
	if($this->IS_33){
		$deny="none";
		$allow="client-first";
		if($this->EnableSquidSSLCRTD==1){
			$allow="server-first";
		}
	}
	
	
	if(mysql_num_rows($results)>0){
		$array[]="# ssl_bump_to_decrypt ".mysql_num_rows($results)." cleaned websites stored in squid_ssl [".__LINE__."]";
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if($ligne["website_name"]==null){continue;}
			$c=$c+1;
			$www=$this->BaseNameWebSite($ligne["website_name"]);
			if($www==null){continue;}
			if(isset($ALREADY[$www])){continue;}
			$ALREADY[$www]=true;
			$www_table[]=$www;
		}
	}else{
		$array[]="# ssl_bump_to_decrypt no website stored in squid_ssl [".__LINE__."]";
	}	
	
	
	$acl=new squid_acls();
	$array[]="# ssl_bump_to_decrypt ".count($www_table)." cleaned websites stored in squid_ssl [".__LINE__."]";
	$destTable=$acl->clean_dstdomains($www_table);
	if(count($destTable)>0){
		@file_put_contents("/etc/squid3/ssl_to_bump.acl", @implode("\n", $destTable));
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." SSLBUMP:: to bumped for ". count($destTable)." domains\n";}
		$array[]="acl ssl_to_bump dstdomain \"/etc/squid3/ssl_to_bump.acl\"";
		$array[]="ssl_bump $allow ssl_to_bump";

	}
	$array[]="ssl_bump $deny all";
	return @implode("\n", $array);
	
}		
		

		
		private function ssl_bump_access(){
			
			$sock=new sockets();
			$SquidSSLUrgency=intval($sock->GET_INFO("SquidSSLUrgency"));
			if($SquidSSLUrgency==1){return;}
			if($this->IS_35){
				$squid_ssl=new squid_ssl();
				$squid_ssl->build();
				$array[]="# [SSL]: Mode 3.5 [".__LINE__."]";
				$array[]="include /etc/squid3/ssl.conf";
				return @implode("\n",$array);
			}
			
			
			
			$SquidWCCPL3SSLEnabled=intval($sock->GET_INFO("SquidWCCPL3SSLEnabled"));
			if($SquidWCCPL3SSLEnabled==1){
				$this->SSL_BUMP=1;
				$this->EnableSquidSSLCRTD=1;
			}
			
			$sock=new sockets();
			$EnableSSLOnStandardPort=intval($sock->GET_INFO("EnableSSLOnStandardPort"));
			
			if($EnableSSLOnStandardPort==0){
				if($this->SSL_BUMP==0){return null;}
			}
			
			if($this->IS_27){return null;}
			if(!$this->ASROOT){return;}
			
			$params=$this->compilation_params();
			if(!isset($params["enable-ssl"])){
				$array[]="# [SSL]: FATAL: Not compiled [".__LINE__."]";
				$array[]="";
				echo "Starting......: ".date("H:i:s")." [SSL]: FATAL: Not compiled...\n";
				return @implode("\n", $array);
			}			
			
			$deny="deny";
			$allow="allow";
			
			if($this->IS_33){
				$deny="none";
				$allow="client-first";
				if($this->EnableSquidSSLCRTD==1){
					$allow="server-first";
				}
			}
			
			if($this->EnableSquidSSLCRTD==1){
				if(strlen($this->sslcrtd_program)>3){
					if(!isset($GLOBALS["sslcrtd_program_added"])){
						$GLOBALS["sslcrtd_program_added"]=true;
						$array[]="# Added by ".__CLASS__."/".__FUNCTION__." Line:".__LINE__;
						$array[]="sslcrtd_program $this->sslcrtd_program -s /var/lib/squid/session/ssl/ssl_db -M 4MB";
					}
					
				}
			}
			
			
			
			$www_table=array();
			if($this->SSL_BUMP_WHITE_LIST==1){
				$array[]="# ssl_bump_to_decrypt [".__LINE__."]";
				$array[]=$this->ssl_bump_to_decrypt();
				return @implode("\n",$array);
			}else{
				$array[]="# ssl_bump_no_decrypt [".__LINE__."]";
				$array[]=$this->ssl_bump_no_decrypt();
			}
			

			$array[]="ssl_bump $allow all";
			
			$array[]="";
			return @implode("\n",$array);
		}
		
		private function if_isssl(){
			$sock=new sockets();
			$SquidSSLUrgency=intval($sock->GET_INFO("SquidSSLUrgency"));
			if($SquidSSLUrgency==1){return false;}
			$SquidWCCPL3SSLEnabled=intval($sock->GET_INFO("SquidWCCPL3SSLEnabled"));
			if($SquidWCCPL3SSLEnabled==1){return true;}
			$EnableSSLOnStandardPort=intval($sock->GET_INFO("EnableSSLOnStandardPort"));
			if($EnableSSLOnStandardPort==1){return true;}
			$EnableArticaHotSpot=intval($sock->GET_INFO("EnableArticaHotSpot"));
			if($EnableArticaHotSpot==1){return true;}
			$MikrotikSSLTransparent=intval($sock->GET_INFO("MikrotikSSLTransparent"));
			if($MikrotikSSLTransparent==1){return true;}
			if($this->SSL_BUMP==1){return true;}
			return false;
			
			
		}
		
	

		
		
		public function ftp_parameters(){	
				$conf[]="";
				$conf[]="#--------- FTP specific parameters";
			
				if($this->SquidSimpleConfig){
					$conf[]="# # # ABORTED SIMPLE CONFIG ENABLED # # #";
					return @implode("\n", $conf)."\n";
				}
				if($this->SquidUrgency==1){
					$conf[]="# # # ABORTED EMERGENCY MODE # # #";
					return @implode("\n", $conf)."\n";
				}
				if(!isset($this->FTP_PARAMS["ftp_list_width"])){$this->FTP_PARAMS["ftp_list_width"]=32;}
				if(!isset($this->FTP_PARAMS["ftp_sanitycheck"])){$this->FTP_PARAMS["ftp_sanitycheck"]='yes';}
				if(!isset($this->FTP_PARAMS["ftp_epsv"])){$this->FTP_PARAMS["ftp_epsv"]='yes';}
				if(!isset($this->FTP_PARAMS["ftp_epsv_all"])){$this->FTP_PARAMS["ftp_epsv_all"]='no';}
				if(!isset($this->FTP_PARAMS["ftp_telnet_protocol"])){$this->FTP_PARAMS["ftp_telnet_protocol"]='yes';}		
				if(!isset($this->FTP_PARAMS["ftp_user"])){$this->FTP_PARAMS["ftp_user"]=null;}
				if(!isset($this->FTP_PARAMS["ftp_passive"])){$this->FTP_PARAMS["ftp_passive"]=1;}						
				
				
				if(!is_numeric($this->FTP_PARAMS["ftp_list_width"])){$this->FTP_PARAMS["ftp_list_width"]=32;}
				if($this->FTP_PARAMS["ftp_sanitycheck"]==null){$this->FTP_PARAMS["ftp_sanitycheck"]='yes';}
				if($this->FTP_PARAMS["ftp_epsv"]==null){$this->FTP_PARAMS["ftp_epsv"]='yes';}
				if($this->FTP_PARAMS["ftp_epsv_all"]==null){$this->FTP_PARAMS["ftp_epsv_all"]='no';}
				if($this->FTP_PARAMS["ftp_telnet_protocol"]==null){$this->FTP_PARAMS["ftp_telnet_protocol"]='yes';}		
				if(!isset($this->FTP_PARAMS["ftp_user"])){$this->FTP_PARAMS["ftp_user"]=null;}			
				
				$ftp_passive=$this->FTP_PARAMS["ftp_passive"];
				$ftp_sanitycheck=$this->FTP_PARAMS["ftp_sanitycheck"];
				$ftp_epsv=$this->FTP_PARAMS["ftp_sanitycheck"];
				$ftp_epsv_all=$this->FTP_PARAMS["ftp_epsv_all"];
				$ftp_telnet_protocol=$this->FTP_PARAMS["ftp_telnet_protocol"];
				
				if($ftp_passive==null){$ftp_passive=1;}	
				if($ftp_passive==1){$ftp_passive="on";}else{$ftp_passive="off";}
				
				if($ftp_sanitycheck==null){$ftp_sanitycheck=1;}	
				if($ftp_sanitycheck==1){$ftp_sanitycheck="on";}else{$ftp_sanitycheck="off";}				
				
				if($ftp_epsv==null){$ftp_epsv=1;}	
				if($ftp_epsv==1){$ftp_epsv="on";}else{$ftp_epsv="off";}				
				
				if($ftp_epsv_all==null){$ftp_epsv_all=0;}	
				if($ftp_epsv_all==1){$ftp_epsv_all="on";}else{$ftp_epsv_all="off";}				
								
				if($ftp_telnet_protocol==null){$ftp_telnet_protocol=0;}	
				if($ftp_telnet_protocol==1){$ftp_telnet_protocol="on";}else{$ftp_telnet_protocol="off";}		

				

				if(preg_match("#(.+?)@#",$this->FTP_PARAMS["ftp_user"])){
					$conf[]="ftp_user {$this->FTP_PARAMS["ftp_user"]}";
				}
				
				if(($this->IS_30) && (!$this->IS_32)){
					$conf[]="ftp_list_width {$this->FTP_PARAMS["ftp_list_width"]}";
				}
				$conf[]="ftp_passive $ftp_passive";
				$conf[]="ftp_sanitycheck $ftp_sanitycheck";
				if($this->IS_31){
					$conf[]="ftp_epsv $ftp_epsv";
					$conf[]="ftp_epsv_all $ftp_epsv_all";
				}
				$conf[]="ftp_telnet_protocol $ftp_telnet_protocol";
				$conf[]="";
				$conf[]="";
				return @implode("\n",$conf);
		}
		
		
	private function squid_reverse_websites_vhosts($keyname,$content){
		$array=unserialize(base64_decode($content));
		if(count($array)==0){return null;}
		$acl_addwww=array();
		while (list ($website_name, $cidr) = each ($array)){
			$website_name=str_replace("http://","",$website_name);
			$website_name=str_replace("https://","",$website_name);			
			$acl_addwww[]=$website_name;
		}
		if(count($acl_addwww)>0){
			$GLOBALS["Rvhosts"][$keyname]=" ".@implode(" ", $acl_addwww);
			return "cache_peer_domain $keyname ".@implode(" ", $acl_addwww);
			
		}
		
	}
	
	private function squid_reverse_websites_buildkey($website_name){
		$maxChar=31-4;
		$website_name=str_replace("http://","",$website_name);
		$website_name=str_replace("https://","",$website_name);		
		$website_name=str_replace(".","",$website_name);
		$website_name=str_replace("-","",$website_name);
		if(strlen($website_name)>$maxChar){
			$website_name=substr($website_name, 0,$maxChar);
		}
		return $website_name;
	}
		
	private function squid_reverse_websites(){
			if(!$this->ASROOT){return;}
			$sql="SELECT * FROM squid_accel WHERE `enabled`=1 ORDER BY ID DESC ";
			$q=new mysql();
			$results=$q->QUERY_SQL($sql,"artica_backup");
			
			echo "Starting......: ".date("H:i:s")." [REVERSE]: UseSSL=0 ". mysql_num_rows($results)." items\n";
			
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
				
				
				$arrayOPTS=unserialize(base64_decode($ligne["options"]));
				$ExtraOptions=$this->cache_peer_extract_options($arrayOPTS);
				if($ExtraOptions==null){$ExtraOptions="no-query no-digest";}
				if($ExtraOptions<>null){$ExtraOptions=" $ExtraOptions ";}
				
				if($ligne["UseSSL"]==1){
					echo "Starting......: ".date("H:i:s")." [REVERSE]:NON-SSL UseSSL={$ligne["UseSSL"]} Skip {$ligne["website_name"]} item\n";
					continue;
				}
				$frontend_options=null;
				$website_name=$ligne["website_name"];
				$website_name=str_replace("http://","",$website_name);
				$website_name=str_replace("https://","",$website_name);
				//if(preg_match("#^.+?\.(.+?)\.(.+?)$#",$website_name,$re)){$acl_addwww=" .{$re[1]}.{$re[2]}";}else{$acl_addwww=$website_name;}
				$acl_addwww=$website_name;
				$ip=$ligne["website_ip"];
				$port=$ligne["website_port"];
				if(!is_numeric($port)){$port=80;}
				if($port==0){$port=80;}
				if($website_name==null){continue;}
				if($ip==null){continue;}
				$website_name_name=$this->squid_reverse_websites_buildkey($website_name);
				if(isset($ALREDYPEER[$website_name_name])){continue;}
				if($port==443){
					$frontend_options="front-end-https=on ssl sslflags=DONT_VERIFY_PEER".$this->SaveCertificate($website_name,true);
				}
				$ALREDYPEER[$website_name_name]=true;
				$conf[]="cache_peer $ip parent $port 0 $ExtraOptions originserver $frontend_options name=$website_name_name ";
				if($ligne["UseVirtualHosts"]==1){
					$UseVirtualHosts=$this->squid_reverse_websites_vhosts($website_name_name,$ligne["virtualhosts"]);
					if($UseVirtualHosts<>null){
						$conf[]=$UseVirtualHosts;
					}
				}
				
				if(!isset($GLOBALS["Rvhosts"][$website_name_name])){$GLOBALS["Rvhosts"][$website_name_name]=null;}
				
				$acls[]="acl acl_$website_name_name dstdomain $acl_addwww{$GLOBALS["Rvhosts"][$website_name_name]}";
				$cache_peer_domain[]="cache_peer_domain $website_name_name $website_name";
				$cache_peer_access[]="cache_peer_access $website_name_name allow acl_$website_name_name";
				$cache_peer_access[]="cache_peer_access $website_name_name deny all\n";
				$http_access[]="http_access allow acl_$website_name_name all";
				
			}
			
			
			$sql="SELECT * FROM squid_accel WHERE UseSSL='1' AND `enabled`=1 ORDER BY ID DESC";
			$q=new mysql();
			$results=$q->QUERY_SQL($sql,"artica_backup");
			echo "Starting......: ".date("H:i:s")." [REVERSE]: UseSSL=1 ". mysql_num_rows($results)." items\n";
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
				if(!is_numeric($ligne["UseSSL"])){$ligne["UseSSL"]=0;}
				$commonname=$ligne["certificate"];
				echo "Starting......: ".date("H:i:s")." [REVERSE]:SSL `{$ligne["website_name"]}` Cert:`$commonname`\n";
				if($ligne["UseSSL"]==0){echo "Starting......: ".date("H:i:s")." [REVERSE]:SSL UseSSL={$ligne["UseSSL"]} Skip {$ligne["website_name"]} item\n";continue;}
				
				
				$arrayOPTS=unserialize(base64_decode($ligne["options"]));
				$ExtraOptions=$this->cache_peer_extract_options($arrayOPTS);
				if($ExtraOptions==null){$ExtraOptions="no-query no-digest";}
				if($ExtraOptions<>null){$ExtraOptions=" $ExtraOptions ";}				
				
				
				$website_name=$ligne["website_name"];
				$website_name=str_replace("http://","",$website_name);
				$website_name=str_replace("https://","",$website_name);
				$acl_addwww=$website_name;
				//if(preg_match("#^.+?\.(.+?)\.(.+?)$#",$website_name,$re)){$acl_addwww=" .{$re[1]}.{$re[2]}";}else{$acl_addwww=$website_name;}
				$ip=$ligne["website_ip"];
				$port=$ligne["website_port"];
				if(!is_numeric($port)){$port=433;}
				if($port==0){$port=443;}
				
				if($website_name==null){continue;}
				if($ip==null){continue;}
				
				$website_name_name="S".$this->squid_reverse_websites_buildkey($website_name);
				if(isset($ALREDYPEER[$website_name_name])){continue;}
				$ALREDYPEER[$website_name_name]=true;				
				$frontend_options="front-end-https=on ssl sslflags=DONT_VERIFY_PEER".$this->SaveCertificate($commonname,true);
			    //if($port==80){$frontend_options=null;}
				
				if($ligne["UseVirtualHosts"]==1){
					$UseVirtualHosts=$this->squid_reverse_websites_vhosts("$website_name_name",$ligne["virtualhosts"]);
					if($UseVirtualHosts<>null){
						$conf[]=$UseVirtualHosts;
					}
				}				
				
				if(!isset($GLOBALS["Rvhosts"]["$website_name_name"])){$GLOBALS["Rvhosts"]["$website_name_name"]=null;}
				$conf[]="cache_peer $ip parent $port 0 $ExtraOptions $frontend_options originserver name=$website_name_name";
				$acls[]="acl acl_$website_name_name dstdomain $acl_addwww{$GLOBALS["Rvhosts"][$website_name_name]}";
				$cache_peer_access[]="cache_peer_access $website_name_name allow acl_$website_name_name";
				$cache_peer_access[]="cache_peer_access $website_name_name deny all\n";
				$cache_peer_domain[]="cache_peer_domain $website_name_name $website_name";
				$http_access[]="http_access allow acl_$website_name_name all";
				
			}			
			
			$GLOBALS["HTTP_ACCESS_REVERSE"]=implode("\n",$http_access);
		
			if(is_array($conf)){
				$ii[]=implode("\n",$conf);
				$ii[]="";
				$ii[]=implode("\n",$acls);
				$ii[]="";
				
				$ii[]=implode("\n",$cache_peer_domain);	
				$ii[]="";
				$ii[]=implode("\n",$cache_peer_access);	
				$ii[]="";
						
				return "\n".implode("\n",$ii)."\n";						
			}
		
	}
		
public function refresh_pattern_list(){
	
	$sock=new sockets();
	$users=new usersMenus();
	$this->DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
	$SquidCacheLevel=$sock->GET_INFO("SquidCacheLevel");
	$UseSimplifiedCachePattern=$sock->GET_INFO("UseSimplifiedCachePattern");
	$refresh_pattern_def_opts=unserialize(base64_decode($sock->GET_INFO("refresh_pattern_def_opts")));
	$refresh_pattern_def_opts2=array();
	$reload_into_ims=$sock->GET_INFO("SquidReloadIntoIMS");
	if(!is_numeric($reload_into_ims)){$reload_into_ims=1;}
	
	if(count($refresh_pattern_def_opts)>0){
		if($reload_into_ims==0){unset($refresh_pattern_def_opts["reload-into-ims"]);}
		$DEFCMD=array();
		if(is_array($refresh_pattern_def_opts)){
			while (list ($key, $val) = each ($refresh_pattern_def_opts) ){
				if($key==null){continue;}
				$refresh_pattern_def_opts2[$key]=$val;
					
			}
		}
	}
	
	if(!is_numeric($SquidCacheLevel)){$SquidCacheLevel=4;}
	if(!is_numeric($UseSimplifiedCachePattern)){$UseSimplifiedCachePattern=1;}
	
	if($SquidCacheLevel==0){
		$this->DisableAnyCache=1;
		$conf[]="reload_into_ims off";
		$conf[]="refresh_pattern -i . 0 100% 0";
		return @implode("\n", $conf);
	}

	if(!is_numeric($this->DisableAnyCache)){$this->DisableAnyCache=0;}
	if($this->DisableAnyCache==1){return;}
	

	
	$refresh_pattern_def_min=$sock->GET_INFO("refresh_pattern_def_min");
	$refresh_pattern_def_max=$sock->GET_INFO("refresh_pattern_def_max");
	$refresh_pattern_def_perc=$sock->GET_INFO("refresh_pattern_def_perc");
	
	if(!is_numeric($refresh_pattern_def_min)){$refresh_pattern_def_min=0;}
	if(!is_numeric($refresh_pattern_def_max)){$refresh_pattern_def_max=129600;}
	if(!is_numeric($refresh_pattern_def_perc)){$refresh_pattern_def_perc=20;}
	
	
	
	
	
	
	if($SquidCacheLevel>2){
		$compilation_params=$this->compilation_params();
		if(isset($compilation_params["enable-http-violations"])){
			if($reload_into_ims==1){
				$conf[]="reload_into_ims on";
			}else{
				$conf[]="reload_into_ims off";
			}
		}
	}
	
	
	if($SquidCacheLevel>1){
		if($reload_into_ims==1){
			$DEFCMD[]="reload-into-ims";
		}
		$DEFCMD[]="refresh-ims";
	}
	
	if($SquidCacheLevel>2){
		$DEFCMD[]="ignore-reload";
	}
	
	if($SquidCacheLevel>3){
		$conf[]="vary_ignore_expire on";
		$conf[]="minimum_expiry_time 10080 second";
		$DEFCMD[]="ignore-no-store";
		$DEFCMD[]="ignore-must-revalidate";
		$DEFCMD[]="override-expire";
		$DEFCMD[]="override-lastmod";
		
	}
	
	
	
	
	if(count($refresh_pattern_def_opts2)>0){
		if($reload_into_ims==0){unset($refresh_pattern_def_opts["reload-into-ims"]);}
		$DEFCMD=array();
		while (list ($key, $val) = each ($refresh_pattern_def_opts) ){
			$DEFCMD[]=$key;
			
		}
	}
	if(is_file("/etc/squid3/refresh_pattern_domains.conf")){
		$conf[]="include /etc/squid3/refresh_pattern_domains.conf";
	}
	$conf[]="refresh_pattern . $refresh_pattern_def_min {$refresh_pattern_def_perc}% $refresh_pattern_def_max ".@implode(" ", $DEFCMD);
	return implode("\n",$conf)."\n";

}

private function refresh_pattern_domains(){
	$q=new mysql();
	$sql="SELECT * FROM `squid_speed` WHERE `domain` IS NOT NULL";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){return;}
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$pattern=trim($ligne["domain"]);
		if($pattern=='.'){continue;}
		if(strpos("  $pattern  ", " -i ")==0){$pattern="-i $pattern";}
		if(isset($already[".$pattern"])){continue;}
	
		if($this->IS_33){
			if($ligne["refresh_pattern_perc"]>100){$ligne["refresh_pattern_perc"]=100;}
			$ligne["refresh_pattern_options"]=trim(str_replace("", "", $ligne["refresh_pattern_options"]));
		}
	
		if(!$this->is_regex($pattern)){
			$pattern=str_replace(".","\.",$pattern);
			$pattern=str_replace("*",".*",$pattern);
		}
		$conf[]="refresh_pattern $pattern  {$ligne["refresh_pattern_min"]}    {$ligne["refresh_pattern_perc"]}%     {$ligne["refresh_pattern_max"]}  {$ligne["refresh_pattern_options"]}";
	
	}
	
	@file_put_contents("/etc/squid3/refresh_pattern_domains.conf", @implode("\n", $conf));
	
	
}

private function is_regex($pattern){
	$f[]="{";
	$f[]="[";
	$f[]="+";
	$f[]="\\";
	$f[]="?";
	$f[]="$";
	$f[]=".*";
	
	while (list ($key, $val) = each ($f) ){
		if(strpos(" $pattern", $val)>0){return true;}
	}
}


private function refresh_pattern_defaults($OnlyDefaults=false){
	$sock=new sockets();
	$final="refresh_pattern -i . 0 75% 5760 reload-into-ims ignore-reload";
	$SquidCacheLevel=$sock->GET_INFO("SquidCacheLevel");
	if(!is_numeric($SquidCacheLevel)){$SquidCacheLevel=4;}
	
	if($SquidCacheLevel<4){
		$final="refresh_pattern -i . 0 75% 4320";
	}
	
	if($this->ForceWindowsUpdateCaching==1){
		$f[]="# --------- Make Windows Updates cache";
		$f[]="refresh_pattern -i microsoft.com/.*\.(cab|exe|ms[i|u|f]|asf|wm[v|a]|dat|zip) 4320 80% 43200 reload-into-ims";
		$f[]="refresh_pattern -i windowsupdate.com/.*\.(cab|exe|ms[i|u|f]|asf|wm[v|a]|dat|zip) 4320 80% 43200 reload-into-ims";
		$f[]="refresh_pattern -i windows.com/.*\.(cab|exe|ms[i|u|f]|asf|wm[v|a]|dat|zip) 4320 80% 43200 reload-into-ims";
	}
	
	$ignore_no_cache=" ";
	if($this->IS_33){$ignore_no_cache=null;}
	if($SquidCacheLevel>2){
		if($this->ForceWindowsUpdateCaching==0){
			$f[]="# --------- Make Windows Updates cache";
			$f[]="refresh_pattern -i microsoft.com/.*\.(cab|exe|ms[i|u|f]|asf|wm[v|a]|dat|zip) 4320 80% 43200 reload-into-ims";
			$f[]="refresh_pattern -i windowsupdate.com/.*\.(cab|exe|ms[i|u|f]|asf|wm[v|a]|dat|zip) 4320 80% 43200 reload-into-ims";
			$f[]="refresh_pattern -i windows.com/.*\.(cab|exe|ms[i|u|f]|asf|wm[v|a]|dat|zip) 4320 80% 43200 reload-into-ims";
		}		
		
		
		$f[]="# -- refresh pattern for extension -- #";
		$f[]="refresh_pattern -i \.js$ 1400 80% 10080 ignore-no-store override-expire ignore-reload reload-into-ims ignore-private";
		$f[]="refresh_pattern -i \.js\? 1400 80% 10080 ignore-no-store override-expire ignore-reload reload-into-ims ignore-private";
		$f[]="refresh_pattern -i \.(jpeg|jpg|jpe|jp2|gif|tiff?|pcx|png|bmp|pic|ico|bif|ver|pict)(\?.*|$) 600 80% 10080 ignore-no-store override-expire ignore-reload reload-into-ims ignore-private";
		$f[]="refresh_pattern -i \.(jpeg|jpg|jpe|jp2|gif|tiff?|pcx|png|bmp|pic|ico|bif|ver|pict)$ 600 80% 10080 ignore-no-store override-expire ignore-reload reload-into-ims ignore-private";
		
		$f[]="refresh_pattern -i \.(class|svc|jar|exe|gz|bz|bz2|tar|tgz|zip|gzip|arj|ace|bin|cab|msi|rar)(\?.*|$) 600 75% 10080 override-expire ignore-reload reload-into-ims ignore-private";
		$f[]="refresh_pattern -i \.(class|svc|jar|exe|gz|bz|bz2|tar|tgz|zip|gzip|arj|ace|bin|cab|msi|rar)$ 600 75% 10080 override-expire ignore-reload reload-into-ims ignore-private";
		
		$f[]="refresh_pattern -i \.(mp2|mp3|mid|midi|mp[234]|wav|ram|ra|rm|au|3gp|m4r|m4a)(\?.*|$) 600 75% 10080 override-expire ignore-reload reload-into-ims ignore-private";
		$f[]="refresh_pattern -i \.(mp2|mp3|mid|midi|mp[234]|wav|ram|ra|rm|au|3gp|m4r|m4a)(\?.*|$) 600 75% 10080 override-expire ignore-reload reload-into-ims ignore-private";
		
		$f[]="refresh_pattern -i \.(mpg|mpeg|mp4|m4v|mov|avi|asf|wmv|wma|dat|flv|swf)(\?.*|$) 600 75% 10080 override-expire ignore-reload reload-into-ims ignore-private";
		$f[]="refresh_pattern -i \.(mpg|mpeg|mp4|m4v|mov|avi|asf|wmv|wma|dat|flv|swf)$ 600 75% 10080 override-expire ignore-reload reload-into-ims ignore-private";
		
		$f[]="refresh_pattern -i \.(chm|dll|doc|docx|xls|xlsx|ppt|pptx|pps|ppsx|mdb|mdbx)(\?.*|$) 0 75% 10080 override-expire ignore-reload reload-into-ims ignore-private";
		$f[]="refresh_pattern -i \.(chm|dll|doc|docx|xls|xlsx|ppt|pptx|pps|ppsx|mdb|mdbx)$ 0 75% 10080 override-expire ignore-reload reload-into-ims ignore-private";
		
		$f[]="refresh_pattern -i \.(txt|conf|cfm|psd|wmf|emf|vsd|pdf|rtf|odt)(\?.*|$) 0 75% 10080 override-expire ignore-reload reload-into-ims ignore-private";
		$f[]="refresh_pattern -i \.(txt|conf|cfm|psd|wmf|emf|vsd|pdf|rtf|odt)$ 0 75% 10080 override-expire ignore-reload reload-into-ims ignore-private";
		
		$f[]="refresh_pattern -i \.(htm|html|mhtml|css|js|crl)(\?.*|$) 1440 90% 86400 override-expire ignore-reload reload-into-ims";
		$f[]="refresh_pattern -i \.(htm|html|mhtml|css|js|crl)$ 1440 90% 86400 override-expire ignore-reload reload-into-ims";
		
		$f[]="refresh_pattern -i \.(swf|wav|css|class|dat|zsci|do|ver|advcs|woff|eps|ttf|svg|svgz|ps|acsm)(\?.*|$) 0 75% 10080 override-expire reload-into-ims ignore-reload ignore-no-store ignore-private";
		$f[]="refresh_pattern -i \.(swf|wav|css|class|dat|zsci|do|ver|advcs|woff|eps|ttf|svg|svgz|ps|acsm)$ 0 75% 10080 override-expire reload-into-ims ignore-reload ignore-no-store ignore-private";
		
		$f[]="refresh_pattern -i \.(xml|flow|json)(\?.*|$) 0 75% 100000 reload-into-ims";
		$f[]="refresh_pattern -i \.(xml|flow|json)$ 0 75% 100000 reload-into-ims";
		
		
		
		
		
		$f[]="refresh_pattern \.android\.clients\.google\.com\/market\/GetBinary\/ 20	80%	40 override-expire override-lastmod ignore-private";
		$f[]="refresh_pattern \.(youtube|googlevideo)\.com\/videoplayback\? 20	80%	40 override-expire override-lastmod ignore-private";
		$f[]="refresh_pattern \.(youtube|googlevideo)\.com\/videoplayback\/ 20	80%	40 override-expire override-lastmod ignore-private";
		$f[]="refresh_pattern stream\.aol\.com\/(.*)/[a-zA-Z0-9]+\/(.*)\.(flv|mp4) 20	80%	40 override-expire override-lastmod ignore-private";
		$f[]="refresh_pattern videos\.5min\.com\/(.*)/[0-9_]+\.(mp4|flv) 20	80%	40 override-expire override-lastmod ignore-private";
		$f[]="refresh_pattern \.blip\.tv\/(.*)\.(m4v|mp4|flv) 20	80%	40 override-expire override-lastmod ignore-private";
		$f[]="refresh_pattern proxy[a-z0-9\-]?[a-z0-9]?[a-z0-9]?[a-z0-9]?\.dailymotion\.com\/(.*)\.(flv|on2|mp4|avi|mkv|mp3|rm|rmvb|m4v|mov|wmv|3gp|mpg|mpeg) 20	80%	40 override-expire override-lastmod ignore-private";
		$f[]="refresh_pattern vid\.akm\.dailymotion\.com\/(.*)\.(flv|on2|mp4|avi|mkv|mp3|rm|rmvb|m4v|mov|wmv|3gp|mpg|mpeg) 20	80%	40 override-expire override-lastmod ignore-private";
		$f[]="refresh_pattern \.dmcdn\.net\/(.*)\.(flv|on2|mp4|avi|mkv|mp3|rm|rmvb|m4v|mov|wmv|3gp|mpg|mpeg) 20	80%	40 override-expire override-lastmod ignore-private";
		$f[]="refresh_pattern video\.(.*)\.fbcdn\.net\/(.*)/[0-9_]+\.(mp4|flv|avi|mkv|m4v|mov|wmv|3gp|mpg|mpeg) 20	80%	40 override-expire override-lastmod ignore-private";
		$f[]="refresh_pattern (.*)\.myspacecdn\.com\/(.*)\/[a-zA-Z0-9]+\/vid\.(flv|mp4|avi|mkv|mp3|rm|rmvb|m4v|mov|wmv|3gp|mpg|mpeg) 20	80%	40 override-expire override-lastmod ignore-private";
		$f[]="refresh_pattern (.*)\.myspacecdn\.(.*)\.footprint\.net\/(.*)\/[a-zA-Z0-9]+\/vid\.(flv|mp4|avi|mkv|mp3|rm|rmvb|m4v|mov|wmv|3gp|mpg|mpeg) 20	80%	40 override-expire override-lastmod ignore-private";
		$f[]="refresh_pattern c\.wrzuta\.pl\/w[a-zA-Z0-9]+\/[a-zA-Z0-9]+$ 20	80%	40 override-expire override-lastmod ignore-private";
		$f[]="refresh_pattern \.hardsextube\.com\/.*\/.*\.(flv|mp4|avi|mkv|mp3|rm|rmvb|m4v|mov|wmv|3gp|mpg|mpeg) 20	80%	40 override-expire override-lastmod ignore-private";
		$f[]="refresh_pattern -xh\.clients\.cdn[0-9a-zA-Z]?[0-9a-zA-Z]?[0-9a-zA-Z]?\.com\/data\/(.*)\.flv 20	80%	40 override-expire override-lastmod ignore-private";		
		$f[]="refresh_pattern (get_video\?|videoplayback\?|videodownload\?|\.flv\?|\.fid\?) 0 75% 43200 override-expire ignore-reload ignore-must-revalidate ignore-private";
		$f[]="refresh_pattern -i (get_video\?|videoplayback\?|videodownload\?) 0 75% 10080 override-expire ignore-reload reload-into-ims ignore-private";
		$f[]="# -- refresh pattern for specific sites -- #";
		$f[]="refresh_pattern ^http://*.jobstreet.com.*/.* 0 70% 10080 override-expire override-lastmod ";
		$f[]="refresh_pattern ^http://*.indowebster.com.*/.* 0 70% 10080 override-expire override-lastmod reload-into-ims ignore-reload ignore-auth";
		$f[]="refresh_pattern ^http://*.21cineplex.*/.* 0 70% 10080 override-expire override-lastmod reload-into-ims ignore-reload ignore-auth";
		$f[]="refresh_pattern ^http://*.atmajaya.*/.* 0 70% 10080 override-expire ignore-auth";
		$f[]="refresh_pattern ^http://*.kompas.*/.* 0 70% 10080 override-expire override-lastmod reload-into-ims ignore-auth";
		$f[]="refresh_pattern ^http://*.theinquirer.*/.* 0 70% 10080 override-expire ignore-auth";
		$f[]="refresh_pattern ^http://*.blogspot.com/.* 0 70% 10080 override-expire override-lastmod reload-into-ims ignore-auth";
		$f[]="refresh_pattern ^http://*.wordpress.com/.* 0 70% 10080 override-expire override-lastmod reload-into-ims ";
		$f[]="refresh_pattern ^http://*.photobucket.com/.* 0 70% 10080 override-expire override-lastmod reload-into-ims ignore-auth";
		$f[]="refresh_pattern ^http://*.tinypic.com/.* 0 70% 10080 override-expire override-lastmod reload-into-ims ignore-auth";
		$f[]="refresh_pattern ^http://*.imageshack.us/.* 0 70% 10080 override-expire override-lastmod reload-into-ims ignore-auth";
		$f[]="refresh_pattern ^http://*.kaskus.*/.* 720 100% 28800 override-expire override-lastmod reload-into-ims ignore-auth";
		$f[]="refresh_pattern ^http://www.kaskus.com/.* 720 100% 28800 override-expire override-lastmod reload-into-ims ignore-auth";
		$f[]="refresh_pattern ^http://*.detik.*/.* 720 50% 2880 override-expire override-lastmod reload-into-ims ignore-auth";
		$f[]="refresh_pattern ^http://*.detiknews.*/*.* 720 50% 2880 override-expire override-lastmod reload-into-ims ignore-auth";
		$f[]="refresh_pattern ^http://video.liputan6.com/.* 0 70% 10080 override-expire override-lastmod reload-into-ims ignore-auth";
		$f[]="refresh_pattern ^http://static.liputan6.com/.* 0 70% 10080 override-expire override-lastmod reload-into-ims ignore-auth";
		$f[]="refresh_pattern ^http://*.friendster.com/.* 0 70% 10080 override-expire override-lastmod ignore-auth";
		$f[]="refresh_pattern ^http://*.facebook.com/.* 0 70% 10080 override-expire override-lastmod reload-into-ims ignore-auth";
		$f[]="refresh_pattern ^http://apps.facebook.com/.* 0 70% 10080 override-expire override-lastmod reload-into-ims ignore-auth";
		$f[]="refresh_pattern ^http://*.fbcdn.net/.* 0 70% 10080 override-expire override-lastmod reload-into-ims ignore-auth";
		$f[]="refresh_pattern ^http://profile.ak.fbcdn.net/.* 0 70% 10080 override-expire override-lastmod reload-into-ims ignore-auth";
		$f[]="refresh_pattern ^http://static.playspoon.com/.* 0 70% 10080 override-expire override-lastmod reload-into-ims ignore-auth";
		$f[]="refresh_pattern ^http://cooking.game.playspoon.com/.* 0 70% 10080 override-expire override-lastmod reload-into-ims ignore-auth";
		$f[]="refresh_pattern -i http://[^a-z\.]*onemanga\.com/? 0 75% 10080 override-expire override-lastmod reload-into-ims ignore-auth";
		$f[]="refresh_pattern ^http://media?.onemanga.com/.* 0 75% 10080 override-expire override-lastmod reload-into-ims ignore-auth";
		$f[]="refresh_pattern ^http://*.yahoo.com/.* 0 75% 10080 override-expire override-lastmod reload-into-ims ignore-auth";
		$f[]="refresh_pattern ^http://*.google.com/.* 0 75% 10080 override-expire override-lastmod reload-into-ims ignore-auth";
		$f[]="refresh_pattern ^http://*.forummikrotik.com/.* 0 75% 10080 override-expire override-lastmod reload-into-ims ignore-auth";
		$f[]="refresh_pattern ^http://*.linux.or.id/.* 0 70% 10080 override-expire override-lastmod reload-into-ims ignore-auth";
		
		$f[]="# -- refresh pattern for Apple -- #";
		$f[]="refresh_pattern -i appldnld\.apple\.com 129600 100% 129600 ignore-reload ignore-no-store override-expire override-lastmod ignore-must-revalidate";
		$f[]="refresh_pattern -i phobos\.apple\.com 129600 100% 129600 ignore-reload ignore-no-store override-expire override-lastmod ignore-must-revalidate";
		
		
		$f[]="refresh_pattern -i microsoft.com/.*\.(cab|exe|ms[i|u|f]|asf|wm[v|a]|dat|zip) 0 75% 43200 reload-into-ims";
		$f[]="refresh_pattern -i windowsupdate.com/.*\.(cab|exe|ms[i|u|f]|asf|wm[v|a]|dat|zip) 0 75% 43200 reload-into-ims";
		$f[]="refresh_pattern -i my.windowsupdate.website.com/.*\.(cab|exe|ms[i|u|f]|asf|wm[v|a]|dat|zip) 0 75% 43200 reload-into-ims";
		$f[]="";
		$f[]="##images facebook";
		$f[]="refresh_pattern ((facebook.com)|(173.252.110.27)).*\.(jpg|png|gif) 0 75% 10080 ignore-reload  override-expire ignore-no-store";
		$f[]="refresh_pattern -i \.fbcdn.net.*\.(jpg|gif|png|swf|mp3) 0 75% 10080 ignore-reload  override-expire ignore-no-store";
		$f[]="refresh_pattern  static\.ak\.fbcdn\.net*\.(jpg|gif|png) 0 75% 10080 ignore-reload  override-expire ignore-no-store";
		$f[]="refresh_pattern ^http:\/\/profile\.ak\.fbcdn.net*\.(jpg|gif|png) 0 75% 10080 ignore-reload  override-expire ignore-no-store";
		$f[]="";
		$f[]="## ANTI VIRUS";
		$f[]="refresh_pattern guru.avg.com/.*\.(bin) 0 75% 10080 ignore-no-store  ignore-reload  reload-into-ims";
		$f[]="refresh_pattern (avgate|avira).*(idx|gz)$ 0 75% 10080 ignore-no-store  ignore-reload  reload-into-ims";
		$f[]="refresh_pattern kaspersky.*\.avc$ 0 75% 10080 ignore-no-store  ignore-reload  reload-into-ims";
		$f[]="refresh_pattern kaspersky 0 75% 10080 ignore-no-store  ignore-reload  reload-into-ims";
		$f[]="refresh_pattern update.nai.com/.*\.(gem|zip|mcs) 0 75% 10080 ignore-no-store  ignore-reload  reload-into-ims";
		$f[]="refresh_pattern ^http:\/\/liveupdate.symantecliveupdate.com 0 75% 10080 ignore-no-store  ignore-reload  reload-into-ims";
		$f[]="";
		
		$f[]="refresh_pattern -i ^ftp: 0 90% 200000";
		$f[]="refresh_pattern -i ^gopher: 1440 0% 1440";
	}
	
	if($OnlyDefaults){return @implode("\n", $f);}
	
	if($SquidCacheLevel<3){
		$f[]="refresh_pattern -i ^ftp: 1440 20% 10080";
		$f[]="refresh_pattern -i ^gopher: 1440 0% 1440";
		$f[]="refresh_pattern -i (/cgi-bin/|\?) 0 0% 0";
	}	
	
	$f[]=$final;
	return @implode("\n", $f);
}



public function SQUID_LOCAL_NETWORKS(){
		$sock=new sockets();
		$k=array();
		$conf=array();
		$AllowAllNetworksInSquid=$sock->GET_INFO("AllowAllNetworksInSquid");
		if(!is_numeric($AllowAllNetworksInSquid)){$AllowAllNetworksInSquid=1;}
		if($AllowAllNetworksInSquid==1){
			$GLOBALS["HTTP_ACCESS"]["OFFICE_NETWORK"]="http_access allow {$this->MyStandPort}office_network";
			$conf[]="acl office_network src all";
			return @implode("\n",$conf);
		}
		$NetworkScannerMasks=$sock->GET_INFO('NetworkScannerMasks');
		$tbl=explode("\n",$NetworkScannerMasks);	
		if(is_array($tbl)){
			while (list ($num, $cidr) = each ($tbl)){
				if(trim($cidr)==null){continue;}
				$k[$cidr]=$cidr;
			}
		}

	if(count($this->network_array)>0){
			while (list ($num, $val) = each ($this->network_array)){
				if($val==null){continue;}
				$k[$val]=$val;
			}
		}
		
	if(count($k)==0){return null;}
	while (list ($m, $l) = each ($k)){$s[]=$l;}
	$GLOBALS["HTTP_ACCESS"]["OFFICE_NETWORK"]="http_access allow {$this->MyStandPort}office_network";
	$conf[]="acl office_network src " . implode(" ",$s);
	return @implode("\n",$conf);
}
		
		
		
function SAFE_PORTS(){
	$sock=new sockets();
	$add=false;
	$ports=unserialize(base64_decode($sock->GET_INFO("SquidSafePortsList")));
	if(!is_array($ports)){$add=true;}
	if(count($ports)<2){$add=true;}
	if($add){
		$ports["80"]="http";
		$ports["22"]="ssh";
		$ports["443 563"]="https, snews";
		$ports["1863"]="msn";
		$ports["70"]="gopher";
		$ports["210"]="wais";
		$ports["1025-65535"]="unregistered ports";
		$ports["280"]="http-mgmt";
		$ports["488"]="gss-http";
		$ports["591"]="filemaker";
		$ports["777"]="multiling http";
		$ports["631"]="cups";
		$ports["873"]="rsync";
		$ports["901"]="SWAT";		
	}		
	
	
	if($this->enable_ftp_restrictions<>1){
		$ports["20"]="ftp-data";
		$ports["21"]="ftp";
	}
	
	$portsSSL=unserialize(base64_decode($sock->GET_INFO("SquidSafePortsSSLList")));
	if(is_array($portsSSL)){
		while (list ($num, $val) = each ($portsSSL) ){$ports[$num]=$val;}
	}	
	
	

	while (list ($num, $val) = each ($ports) ){
		$conf[]="acl Safe_ports port $num\t#$val";		
	}
	
	return implode("\n",$conf);
	
}


private function SSL_PORTS(){
	$sock=new sockets();
	$ports=unserialize(base64_decode($sock->GET_INFO("SquidSafePortsSSLList")));
	if(!is_array($ports)){
		$ports["9000"]="Artica";
		$ports["443"]="HTTPS";
		$ports["563"]="https, snews";
		$ports["6667"]="tchat";
	}
	
	if(count($ports)==0){
		$ports["9000"]="Artica";
		$ports["443"]="HTTPS";
		$ports["563"]="https, snews";
		$ports["6667"]="tchat";	
	}
	
	while (list ($num, $val) = each ($ports) ){
		if(!is_numeric($num)){continue;}
		$ports2[]=$num;
	}
	$conf[]="";
	@file_put_contents("/etc/squid3/acls/SSLPorts", @implode("\n", $ports2));
	return "acl SSL_ports port \"/etc/squid3/acls/SSLPorts\"\n";
	
}

		
function acls_multimedias(){
		return null;
		$conf=null;
		$conf=$conf."acl MULTIMEDIA rep_mime_type -i ^(audio\/x-mpegurl|audio\/mpeg|video\/flv|video\/x-flv|application\/x-shockwave-flash|audio\/ogg|video\/ogg|application\/ogg)$\n";
		$conf=$conf."acl multimedia_rep rep_mime_type -i ^video/x-ms-asf$\n";
		$conf=$conf."acl multimedia_rep rep_mime_type -i ^application/vnd.ms.wms-hdr.asfv1$\n";
		$conf=$conf."acl multimedia_rep rep_mime_type -i ^application/x-mms-framed$\n";
		$conf=$conf."acl multimedia_rep rep_mime_type -i ^image/\n";
		$conf=$conf."acl multimedia_rep rep_mime_type -i ^video\n";
		$conf=$conf."acl multimedia_rep rep_mime_type -i ^audio\n";
		$conf=$conf."acl multimedia_rep rep_mime_type -i ^application/x-dvi$\n";
		$conf=$conf."acl multimedia_rep rep_mime_type -i ^application/x-isoview\n";
		$conf=$conf."acl multimedia_browsers browser -i ^.*player\n";
		$conf=$conf."acl bigfiles_types urlpath_regex -i \.(deb|rpm|iso|tar\.gz|gz|bz|tar|cue|nrg|crf|bwi|bwt|lcd|ccd|mdf|mds|vcd|cif|vdi|img)((\?|&).*)?$";

		return $conf;
		}
		
function external_ldap_ou($path){
	$ldap=new clladp();
	$ou_arr=$ldap->hash_get_ou();
	
	if(is_array($ou_arr)){
		while (list ($num, $val) = each ($ou_arr)){		
			$conf=$conf . "external_acl_type ldap_{$val} %LOGIN " . dirname($path)."/squid_ldap_group -D \"cn=$ldap->ldap_admin,$ldap->suffix\"";
			$conf=$conf . " -w $ldap->ldap_password -b \"ou=$val,$ldap->suffix\"  -f \"(&(objectClass=posixGroup)(gidNumber=%a)(memberUid=%v))\" -S -v 3 -h 127.0.0.1\n";
			}
		return "$conf\n";
		}
	}
	

	
}




class cicap{
	public $main_array=array();
	public $EnableClamavInCiCap=1;
	public $EnableCicapToSyslog=0;
	public $EnableSquidGuardInCiCAP=1;
	public $EnableUfdbGuard=0;
	public $enable_metascanner=0;
	private $EnableRemoteStatisticsAppliance=0;
	private $AntivirusEnabledCentral=1;
	private $UfdbEnabledCentral=1;
	
	function cicap(){
		
		$ini=new Bs_IniHandler();
		$sock=new sockets();
		$ini->loadString($sock->GET_INFO('CicapInternalConfiguration'));
		$this->main_array=$ini->_params;
		$this->BuildDefaults();
		$this->EnableClamavInCiCap=$sock->GET_INFO("EnableClamavInCiCap");
		$this->EnableCicapToSyslog=$sock->GET_INFO("EnableCicapToSyslog");
		$this->EnableSquidGuardInCiCAP=$sock->GET_INFO("EnableSquidGuardInCiCAP");
		$this->EnableUfdbGuard=$sock->EnableUfdbGuard();
		$this->enable_metascanner=$sock->GET_INFO("KavMetascannerEnable");
		$this->EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
		$this->UfdbEnabledCentral=$sock->GET_INFO("UfdbEnabledCentral");
		$this->AntivirusEnabledCentral=$sock->GET_INFO("AntivirusEnabledCentral");
		
		
		if(!is_numeric($this->EnableRemoteStatisticsAppliance)){$this->EnableRemoteStatisticsAppliance=0;}
		if(!is_numeric($this->UfdbEnabledCentral)){$this->UfdbEnabledCentral=1;}
		if(!is_numeric($this->AntivirusEnabledCentral)){$this->AntivirusEnabledCentral=1;}
		
		if(!is_numeric($this->EnableSquidGuardInCiCAP)){$this->EnableSquidGuardInCiCAP=1;}
		if(!is_numeric($this->EnableClamavInCiCap)){$this->EnableClamavInCiCap=1;}
		if(!is_numeric($this->EnableCicapToSyslog)){$this->EnableCicapToSyslog=1;}
		
		if($this->EnableRemoteStatisticsAppliance==1){
			$this->EnableUfdbGuard=$this->UfdbEnabledCentral;
		}
		
		if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
		$users=$GLOBALS["CLASS_USERS"];
		if(!$users->SQUIDGUARD_INSTALLED){$this->EnableSquidGuardInCiCAP=0;}
		if($users->APP_UFDBGUARD_INSTALLED){
			if($this->EnableUfdbGuard==1){
				if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." c-icap disabling squidGuard databases, UfdbGuard is enabled\n";}
				$this->EnableSquidGuardInCiCAP=0;
			}
		}

		if(!$users->APP_KHSE_INSTALLED){$this->enable_metascanner=0;}
			
			
			
		}
	
	function BuildDefaults(){
		if($this->main_array["CONF"]["Timeout"]==null){$this->main_array["CONF"]["Timeout"]=300;}
		if($this->main_array["CONF"]["KeepAlive"]==null){$this->main_array["CONF"]["KeepAlive"]="on";}
		if($this->main_array["CONF"]["MaxKeepAliveRequests"]==null){$this->main_array["CONF"]["MaxKeepAliveRequests"]="100";}
		if($this->main_array["CONF"]["KeepAliveTimeout"]==null){$this->main_array["CONF"]["KeepAliveTimeout"]="600";}
		if($this->main_array["CONF"]["StartServers"]==null){$this->main_array["CONF"]["StartServers"]="3";}
		if($this->main_array["CONF"]["MaxServers"]==null){$this->main_array["CONF"]["MaxServers"]="30";}
		if($this->main_array["CONF"]["MinSpareThreads"]==null){$this->main_array["CONF"]["MinSpareThreads"]="10";}
		if($this->main_array["CONF"]["MaxSpareThreads"]==null){$this->main_array["CONF"]["MaxSpareThreads"]="20";}
		if($this->main_array["CONF"]["ThreadsPerChild"]==null){$this->main_array["CONF"]["ThreadsPerChild"]="0";}
		if($this->main_array["CONF"]["MaxRequestsPerChild"]==null){$this->main_array["CONF"]["MaxRequestsPerChild"]="0";}
		if($this->main_array["CONF"]["srv_clamav.SendPercentData"]==null){$this->main_array["CONF"]["srv_clamav.SendPercentData"]="5";}
		if($this->main_array["CONF"]["srv_clamav.StartSendPercentDataAfter"]==null){$this->main_array["CONF"]["srv_clamav.StartSendPercentDataAfter"]="2";}
		if($this->main_array["CONF"]["srv_clamav.MaxObjectSize"]==null){$this->main_array["CONF"]["srv_clamav.MaxObjectSize"]="5";}
		if($this->main_array["CONF"]["srv_clamav.ClamAvMaxFilesInArchive"]==null){$this->main_array["CONF"]["srv_clamav.ClamAvMaxFilesInArchive"]="10";}
		if($this->main_array["CONF"]["srv_clamav.ClamAvMaxFileSizeInArchive"]==null){$this->main_array["CONF"]["srv_clamav.ClamAvMaxFileSizeInArchive"]="100";}		
		if($this->main_array["CONF"]["srv_clamav.ClamAvMaxRecLevel"]==null){$this->main_array["CONF"]["srv_clamav.ClamAvMaxRecLevel"]="5";}
		if($this->main_array["CONF"]["ThreadsPerChild"]==0){$this->main_array["CONF"]["ThreadsPerChild"]=10;}
		if($this->main_array["CONF"]["VirSaveDir"]==null){$this->main_array["CONF"]["VirSaveDir"]="/opt/artica/share/www/squid-attachments";}
		if($this->main_array["CONF"]["VirHTTPServer"]==null){$this->main_array["CONF"]["VirHTTPServer"]="https:///exec.cicap.php?usename=%f&remove=1&file=";}
		if($this->main_array["CONF"]["DebugLevel"]==null){$this->main_array["CONF"]["DebugLevel"]="3";}
		if($this->main_array["CONF"]["ViralatorMode"]==null){$this->main_array["CONF"]["ViralatorMode"]="0";}
		
		
		
	
	}
	
	private function LOCATE_CLAMD_CONF(){
		if(is_file("/etc/clamav/clamd.conf")){return "/etc/clamav/clamd.conf";}
		if(is_file("/etc/clamd.conf")){return "/etc/clamd.conf";}
	}
	
	public function LOCATE_MODULES_DIR(){
		if(is_file("/usr/lib/c_icap/sys_logger.so")){return "/usr/lib/c_icap";}
		if(is_file("/var/lib/c_icap/sys_logger.so")){return "/var/lib/c_icap";}
	}
	
	private function CLAMAV_USER(){
		$tbl=@explode("\n",@file_get_contents($this->LOCATE_CLAMD_CONF()));
		while (list ($num, $val) = each ($tbl)){	
			if(preg_match("#User\s+(.+)#",$val,$re)){return trim($re[1]);}
		}
	}
	
	private function DNBSL_LIST(){
		include_once(dirname(__FILE__)."/class.mysql.squid.builder.php");
		$sock=new sockets();
		$datas=explode("\n",$sock->GET_INFO("CicapDNSBL"));
		while (list ($num, $line) = each ($datas)){
			if(strlen($line)<4){continue;}
			$ALLRD[$line]=true;
			$servicename=str_replace(".","",$line);
			$conf[]="url_check.LookupTableDB $servicename  domain dnsbl:$line";
			$servicenames[]=$servicename;
			}
			
			
		$sql="SELECT dnsbl FROM webfilter_dnsbl WHERE enabled=1";
		$q=new mysql_squid_builder();
		$results = $q->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {
			if(isset($ALLRD[$line])){continue;}
			$servicename=str_replace(".","",$ligne["dnsbl"]);
			$conf[]="url_check.LookupTableDB $servicename  domain dnsbl:{$ligne["dnsbl"]}";
			$servicenames[]=$servicename;
			
		}
			
		if(!is_array($conf)){return array();}
		
		$conf[]="url_check.Profile default block ". implode(" ",$servicenames);
		
		return $conf;
	}
	
	private function syslogger(){
		if($this->EnableCicapToSyslog==0){return;}
		$modules_path=$this->LOCATE_MODULES_DIR();
		if(!is_file("$modules_path/sys_logger.so")){return;}
		$conf[]="";
		$conf[]="#Sysloger";
		$conf[]="Module logger sys_logger.so";
		$conf[]="Logger sys_logger";
		$conf[]="sys_logger.Prefix \"C-ICAP:\"";
		$conf[]="sys_logger.Facility local1";
		# TAG: sys_logger.Facility
		# Format: sys_logger.Facility daemon|user|local1|local2|local3|local4|local5|local6|local7
		# Description:
		#	specifies the facility type of syslog.
		# Default:
		#	sys_logger.Facility daemon
		
		# TAG: sys_logger.access_priority
		# Format: sys_logger.access_priority alert|crit|debug|emerg|err|info|notice|warning
		# Description:
		#	determines  the  importance  of the access log message
		# Default:
		#	sys_logger.access_priority info
		$conf[]="sys_logger.access_priority debug";
		
		# TAG: sys_logger.server_priority
		# Format: sys_logger.server_priority alert|crit|debug|emerg|err|info|notice|warning
		# Description:
		#	determines  the  importance  of the server log message
		# Default:
		#	sys_logger.server_priority crit
		$conf[]="sys_logger.server_priority debug";
		# TAG: sys_logger.LogFormat
		# Format: sys_logger.LogFormat LOGFORMAT
		# Description:
		#	The log format to use. If no log format defined then
		#	the following will be used:
		#	    "%la %a %im %iu %is"
		# Default:
		#	None set
		# Example:
		#	Logformat BasicFormat "%la %a %im %iu %is"
		#	sys_logger.LogFormat BasicFormat
		$conf[]="sys_logger.LogFormat \"%tl;%a;%un;%iu;%is;%huo\"";
		# TAG: sys_logger.access
		# Format: sys_logger.access [!]acl1 ...
		# Description:
		#	Allow selecting ICAP requests to be logged using acls.
		#	By default all requests will be logged.
		# Default:
		#	None set
		# Example:
		$conf[]="acl URLCHECK service url_check";
		$conf[]="acl URLBLOCKED icap_resp_header{X-Response-Info} /BLOCKED/";
		$conf[]="sys_logger.access URLCHECK URLBLOCKED";
		$conf[]="sys_logger.access all";
		
		$conf[]="# End module: sys_logger";
		$conf[]="";
		return @implode("\n", $conf);	
		
	}
	
	
	public function buildconf(){
			$this->BuildDefaults();
			include_once(dirname(__FILE__))."/class.c-icap.virus_scan.inc";
			$sock=new sockets();
			$CICAPEnableSquidGuard=intval($sock->GET_INFO("CICAPEnableSquidGuard"));
			
			$DisableCicapDNBSL=$sock->GET_INFO("DisableCicapDNBSL");
			$CICAPListenAddress=$sock->GET_INFO("CICAPListenAddress");
			if(!is_numeric($DisableCicapDNBSL)){$DisableCicapDNBSL=1;}
			if($CICAPListenAddress==null){$CICAPListenAddress="127.0.0.1";}
			
			if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
			if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
			$users=$GLOBALS["CLASS_USERS"];
			$user="squid";
			$modules_path=$this->LOCATE_MODULES_DIR();
			if(!$users->KASPERSKY_WEB_APPLIANCE){
				echo "Starting......: ".date("H:i:s")." c-icap using modules path in $modules_path\n";
				if(is_file("$modules_path/srv_clamav.so")){
					echo "Starting......: ".date("H:i:s")." c-icap using ClamAV antivirus\n";
					$user=$this->CLAMAV_USER();
				
				}
			}
			
			if(is_dir($GLOBALS["guarddb"])){shell_exec("/bin/chown -R $user:$user {$GLOBALS["guarddb"]}/*");}
			if(trim($usermenus->hostname==null)){
				if($GLOBALS["AS_ROOT"]){
					if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
					$usermenus->hostname=$unix->hostname_g();
				}
			}
			
			
			if($GLOBALS["AS_ROOT"]){
				$NETWORK_ALL_INTERFACES=$GLOBALS["CLASS_UNIX"]->NETWORK_ALL_INTERFACES(true);
				if(!isset($NETWORK_ALL_INTERFACES[$CICAPListenAddress])){$CICAPListenAddress="127.0.0.1";}
			}
			
			echo "Starting......: ".date("H:i:s")." c-icap using User $user ($usermenus->hostname)\n";
			
			
			
			
			$conf[]="PidFile /var/run/c-icap/c-icap.pid";
			$conf[]="CommandsSocket /var/run/c-icap/c-icap.ctl";
			$conf[]="Timeout {$this->main_array["CONF"]["Timeout"]}";
			//$conf[]="KeepAlive Off";
			$conf[]="MaxKeepAliveRequests {$this->main_array["CONF"]["MaxKeepAliveRequests"]}";
			$conf[]="KeepAliveTimeout {$this->main_array["CONF"]["KeepAliveTimeout"]}";
			$conf[]="StartServers {$this->main_array["CONF"]["StartServers"]}";
			$conf[]="MaxServers {$this->main_array["CONF"]["MaxServers"]}";
			$conf[]="MinSpareThreads     {$this->main_array["CONF"]["MinSpareThreads"]}";
			$conf[]="MaxSpareThreads     {$this->main_array["CONF"]["MaxSpareThreads"]}";
			$conf[]="ThreadsPerChild     {$this->main_array["CONF"]["ThreadsPerChild"]}";
			$conf[]="MaxRequestsPerChild  {$this->main_array["CONF"]["MaxRequestsPerChild"]}";
			$conf[]="MaxMemObject 131072";
			$conf[]="Port 1345 ";
			$conf[]="ListenAddress $CICAPListenAddress";
			$conf[]="User $user";
			$conf[]="Group $user";
			$conf[]="ServerAdmin you@your.address";
			$conf[]="ServerName $usermenus->hostname";
			$conf[]="TmpDir /var/lib/c_icap/temporary";
			$conf[]="DebugLevel {$this->main_array["CONF"]["DebugLevel"]}"; #11 whas very verbose
			$conf[]="ModulesDir $modules_path";
			$conf[]="ServicesDir $modules_path";
			$conf[]="TemplateDir /usr/share/c_icap/templates/";		
			$conf[]="LoadMagicFile /etc/c-icap.magic";
			$conf[]="TemplateDefaultLanguage en";
			$conf[]="#TemplateReloadTime 360";
			$conf[]="#TemplateCacheSize 20";
			$conf[]="#TemplateMemBufSize 8192";
			
			
			$conf[]="";
			
			$conf[]="RemoteProxyUsers on";
			$conf[]="RemoteProxyUserHeader X-Authenticated-User";
			$conf[]="RemoteProxyUserHeaderEncoded on";
			$conf[]="LogFormat allFormat \"%tl;%a;%un;%iu;%is;%huo\"";
			
			if(is_file("/etc/c-icap-perso.conf")){
				$conf[]="Include /etc/c-icap-perso.conf";
			}
			
			if($CICAPEnableSquidGuard==1){
				$conf[]="# Web Filtering is enabled";
				$conf[]="Include /etc/srv_url_check.conf";
			}else{
				$conf[]="# Web Filtering is disabled";
			}
			
			$clamd=new cicap_av($modules_path);
			
			$conf[]=$clamd->build();
			$conf[]="";
			
			$conf[]="acl all src 0.0.0.0/0.0.0.0";
			$conf[]="icap_access allow all";
			$conf[]="";
			$conf[]=$this->syslogger();
			
			if($this->enable_metascanner==1){
				$conf[]="Service khse /opt/kaspersky/khse/libexec/libms-icap.so";
			}			

				
		$conf[]="";
				@mkdir("/var/log/c-icap",0666,true);
				@mkdir("/var/lib/c_icap/temporary",0666,true);
				@mkdir("/var/run/c-icap",0666,true);
				@mkdir("/var/tmp",0666,true);
				@mkdir("/usr/share/c_icap/templates",0666,true);
				//if(!is_file("/var/log/c-icap/server.log")){@file_put_contents("/var/log/c-icap/server.log"," ");}
				//if(!is_file("/var/log/c-icap/access.log")){@file_put_contents("/var/log/c-icap/access.log"," ");}
				
				shell_exec("/bin/chmod -R 0755 /var/log/c-icap");
				shell_exec("/bin/chmod -R 0755 /var/run/c-icap");
				shell_exec("/bin/chmod -R 0755 /var/lib/c_icap");
				shell_exec("/bin/chmod -R 0755 /var/tmp");
				shell_exec("/bin/chmod -R 0755 /usr/share/c_icap");
				
				shell_exec("/bin/chown -R $user:$user /var/log/c-icap");
				shell_exec("/bin/chown -R $user:$user /var/run/c-icap");
				shell_exec("/bin/chown -R $user:$user /var/lib/c_icap");
				shell_exec("/bin/chown -R $user:$user /var/tmp");
				shell_exec("/bin/chown -R $user:$user /usr/share/c_icap");
				if(is_dir($this->main_array["CONF"]["VirSaveDir"])){
					shell_exec("/bin/chown -R $user:$user {$this->main_array["CONF"]["VirSaveDir"]}");
				}
				
				echo "Starting......: ".date("H:i:s")." c-icap apply securities on user \"$user\" done\n";
				@file_put_contents("/etc/c-icap.conf",@implode("\n",$conf));
		
	}
	
	
	
	private function cicap_whitelisted_servers(){
		$sql="SELECT * FROM dansguardian_files WHERE filename='exceptionsitelist' AND RuleID=1 AND enabled=1";
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$t[]=$ligne["pattern"];
		}
		
		@file_put_contents("/etc/squid3/c-icap.whitelist.txt",@implode("\n",$t));
		
	}
	
	
	
	
	
	function Save(){
		$ini=new Bs_IniHandler();
		$ini->_params=$this->main_array;
		$confArtica=$ini->toString();
		$sock=new sockets();
		$sock->SaveConfigFile($confArtica,'CicapInternalConfiguration');
		$sock->getFrameWork("cmd.php?cicap-reconfigure=yes");
		$sock->getFrameWork("cmd.php?cicap-reload=yes");
		}
	
}
?>