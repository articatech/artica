<?php
include_once(dirname(__FILE__).'/class.dansguardian.inc');
include_once(dirname(__FILE__).'/class.kav4proxy.inc');
include_once(dirname(__FILE__).'/class.sockets.inc');
include_once(dirname(__FILE__)."/class.c-icap-filter.inc");	




class icap{
	var $ASROOT=false;
	var $IS_30=false;
	var $IS_31=false;
	var $IS_32=false;
	var $IS_33=false;
	
	var $ICAP_SERVICES_COUNT=0;
	var $enable_kavproxy=0;
	var $enable_cicap=0;
	var $enable_squidguard=0;
	var $EnableClamavInCiCap=1;
	var $icap_final_string;
	var $EnableSquidGuardInCiCAP=0;
	var $EnableKavICAPRemote=0;
	var $KavICAPRemoteAddr=null;
	var $KavICAPRemotePort=0;	
	var $enable_metascanner=0;
	var $EnableRemoteStatisticsAppliance=0;
	var $RemoteApplianceServer=null;
	var $CicapEnabled=0;
	var $IcapServerAddress="127.0.0.1";
	public $icap_lines=array();
	public $CountDeServices=0;
	private $AntivirusEnabledCentral=1;
	private $Kav4ProxyEnabledCentral=0;
	private $SquidDisableAllFilters=0;
	private $SquidUrgency=0;
	private $SQUID_VERSION=0;
	
	function icap(){
		$users=new usersMenus();
		$ini=new Bs_IniHandler();
		$sock=new sockets();
		$SQUID_ICAP_ENABLED=$users->SQUID_ICAP_ENABLED;
		$KAV4PROXY_INSTALLED=$users->KAV4PROXY_INSTALLED;
		$ArticaSquidParameters=$sock->GET_INFO('ArticaSquidParameters');
		$ini->loadString($ArticaSquidParameters);
		$this->enable_cicap=$sock->GET_INFO('CicapEnabled');
		if(!is_numeric($this->enable_cicap)){$this->enable_cicap=0;}
		$this->enable_kavproxy=$sock->GET_INFO('kavicapserverEnabled');
		$this->enable_squidguard=$sock->GET_INFO('squidGuardEnabled');
		$this->EnableRemoteStatisticsAppliance=$sock->GET_INFO('EnableRemoteStatisticsAppliance');
		$this->AntivirusEnabledCentral=$sock->GET_INFO("AntivirusEnabledCentral");
		$this->Kav4ProxyEnabledCentral=$sock->GET_INFO("Kav4ProxyEnabledCentral");
		$this->SquidDisableAllFilters=intval($sock->GET_INFO("SquidDisableAllFilters"));
		$this->SquidUrgency=intval($sock->GET_INFO("SquidUrgency"));
		
		$this->EnableClamavInCiCap=$sock->GET_INFO("EnableClamavInCiCap");
		$this->EnableSquidGuardInCiCAP=$sock->GET_INFO("EnableSquidGuardInCiCAP");
		$this->EnableKavICAPRemote=$sock->GET_INFO("EnableKavICAPRemote");
		$this->KavICAPRemoteAddr=$sock->GET_INFO("KavICAPRemoteAddr");
		$this->KavICAPRemotePort=$sock->GET_INFO("KavICAPRemotePort");
		$this->enable_metascanner=$sock->GET_INFO("KavMetascannerEnable");
		
		if($this->KavICAPRemoteAddr==null){$this->EnableKavICAPRemote=0;}
		if(!is_numeric($this->AntivirusEnabledCentral)){$this->AntivirusEnabledCentral=1;}
		if(!is_numeric($this->KavICAPRemotePort)){$this->EnableKavICAPRemote=0;}
		if(!is_numeric($this->enable_kavproxy)){$this->enable_kavproxy=0;}
		if(!is_numeric($this->EnableKavICAPRemote)){$this->EnableKavICAPRemote=0;}
		if(!is_numeric($this->Kav4ProxyEnabledCentral)){$this->Kav4ProxyEnabledCentral=0;}
		if(!is_numeric($this->enable_metascanner)){$this->enable_metascanner=0;}			
		if(!is_numeric($this->EnableClamavInCiCap)){$this->EnableClamavInCiCap=1;}
		if(!is_numeric($this->EnableRemoteStatisticsAppliance)){$this->EnableRemoteStatisticsAppliance=0;}
		if(!is_numeric($this->EnableSquidGuardInCiCAP)){$this->EnableSquidGuardInCiCAP=0;$sock->SET_INFO("EnableSquidGuardInCiCAP","0");}
		
		
		
		
		if(posix_getuid()==0){$this->ASROOT=true;}
		if(isset($GLOBALS["AS_ROOT"])){if($GLOBALS["AS_ROOT"]){$this->ASROOT=true;}}
		
		$this->SQUID_VERSION=$users->SQUID_VERSION;
		if(preg_match('#^([0-9]+)\.([0-9]+)#',$this->SQUID_VERSION,$re)){
		if($re[1]>=3){
			$this->IS_30=true;
			if($re[2]==1){$this->IS_31=true;}
			if($re[2]>=2){$this->IS_32=true;$this->IS_31=true;}
			if($re[2]>=3){$this->IS_33=true;$this->IS_32=true;$this->IS_31=true;}
			if($re[2]>=4){$this->IS_40=true;$this->IS_33=true;$this->IS_32=true;$this->IS_31=true;}
			}
		}
		
		if(!$users->KAV4PROXY_INSTALLED){$this->enable_kavproxy=0;}
		if(!$users->C_ICAP_INSTALLED){$this->enable_cicap=0;}
		if(!$users->SQUIDGUARD_INSTALLED){$this->EnableSquidGuardInCiCAP=0;}
		if($this->EnableKavICAPRemote==1){$this->enable_kavproxy=1;}
		
		if($this->EnableRemoteStatisticsAppliance==1){
			$this->CicapEnabled=$sock->GET_INFO("CicapEnabled");
			if(!is_numeric($this->CicapEnabled)){$this->CicapEnabled=0;}
			if($this->CicapEnabled==1){$this->enable_cicap=1;}
			$RemoteStatisticsApplianceSettings=unserialize(base64_decode($sock->GET_INFO("RemoteStatisticsApplianceSettings")));
			$this->RemoteApplianceServer=$RemoteStatisticsApplianceSettings["SERVER"];
			$this->IcapServerAddress=$this->RemoteApplianceServer;
			$this->CicapEnabled=$this->AntivirusEnabledCentral;
			$this->enable_cicap=$this->AntivirusEnabledCentral;
			$this->enable_kavproxy=$this->Kav4ProxyEnabledCentral;
			$this->EnableKavICAPRemote=$this->Kav4ProxyEnabledCentral;
			
			if( ($this->CicapEnabled==1) OR ($this->enable_kavproxy==1)){
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ICAP]: Using remote appliance {$this->RemoteApplianceServer} as Web Antivirus engine\n";}
			}
		}

		
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ICAP]: ClamAV in ICAP:$this->EnableClamavInCiCap\n";}
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ICAP]: squidGuard in ICAP:$this->EnableSquidGuardInCiCAP\n";}
		if($this->EnableKavICAPRemote==1){
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ICAP]: Remote Kaspersky ICAP Server:$this->EnableKavICAPRemote ($this->KavICAPRemoteAddr:$this->KavICAPRemotePort)\n";}
		}
		
		$this->icap_lines=array();
		
		
		
	}
	
	public function build_services(){
		
		$sock=new sockets();
		$CicapEnabled=$sock->GET_INFO("CicapEnabled");
		if(!is_numeric($CicapEnabled)){$CicapEnabled=0;}
		
		if(preg_match('#^([0-9]+)\.([0-9]+)#',$this->SQUID_VERSION,$re)){
			if($re[1]>=3){
				if($re[2]==0){
					$this->icap_lines[]="# Squid 3.0x ( current $this->SQUID_VERSION ) branch no longer supported [".__LINE__."]";
					$conf[]=@implode("\n", $this->icap_lines)."\n";
					@file_put_contents("/etc/squid3/icap.conf", @implode("\n", $conf));
					return;
				}
			}
		}
		
		if($this->SquidUrgency==1){
			$this->icap_lines[]="# Emergency mode enabled!!";
			$conf[]=@implode("\n", $this->icap_lines)."\n";
			@file_put_contents("/etc/squid3/icap.conf", @implode("\n", $conf));
			return;
			
		}
		if($this->SquidDisableAllFilters==1){
			$this->icap_lines[]="# All filters as been disabled ( SquidDisableAllFilters )";
			$conf[]=@implode("\n", $this->icap_lines)."\n";
			@file_put_contents("/etc/squid3/icap.conf", @implode("\n", $conf));			
			return;
				
		}		

		$q=new mysql_squid_builder();
		if(!$q->TABLE_EXISTS("c_icap_services")){
			$this->icap_lines[]="# c_icap_services table does not exists";
			$conf[]=@implode("\n", $this->icap_lines)."\n";
			@file_put_contents("/etc/squid3/icap.conf", @implode("\n", $conf));
			return;
		}
		
		$sql="SELECT * FROM c_icap_services WHERE `enabled`=1 ORDER BY zOrder";
		$q=new mysql_squid_builder();
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){
			$q->mysql_error=str_replace("\n", " ", $q->mysql_error);
			$this->icap_lines[]="# MySQL error $q->mysql_error [".__LINE__."]";
			$conf[]=@implode("\n", $this->icap_lines)."\n";
			@file_put_contents("/etc/squid3/icap.conf", @implode("\n", $conf));
			return;
		}
		
		$CountDeServices=mysql_num_rows($results);
		$this->icap_lines[]="#  **** ICAP **** ( ".date("Y-m-d H:i:s")." )";
		$this->icap_lines[]="#";
		$this->icap_lines[]="# Table c_icap_services report $CountDeServices service(s) [".__LINE__."]";
		if($CountDeServices==0){
			$conf[]=@implode("\n", $this->icap_lines)."\n";
			@file_put_contents("/etc/squid3/icap.conf", @implode("\n", $conf));
			return;
		}
		$overload["block"]="{block}";
		$overload["bypass"]="{bypass}";
		$overload["wait"]="{wait}";
		$overload["force"]="{force}";
		
		// ID,service_name,service_key,respmod,routing,bypass,enabled,zOrder,ipaddr,listenport,icap_server,maxconn,overload
		//('1','C-ICAP Antivirus - LOCAL','service_antivir','respmod_precache',1,1,0,0,'127.0.0.1',12345,'srv_clamav',100,'bypass')
		while ($ligne = mysql_fetch_assoc($results)) {
			$ID=$ligne["ID"];
			$routing="off";
			$bypass="off";
			$on_overload="bypass";
			$maxconn_text=null;
			
			if($ligne["routing"]==1){$routing="on";}
			if(intval($ligne["bypass"])>0){$bypass="on";}
			$ipaddr=$ligne["ipaddr"];
			$this->icap_lines[]="#";
			$this->icap_lines[]="# Service ID $ID {$ligne["service_name"]} $ipaddr:$port [".__LINE__."]";
			$port=$ligne["listenport"];
			$on_overload=$ligne["overload"];
			if(!isset($overload[$on_overload])){$on_overload="bypass";}
			$maxconn=$ligne["maxconn"];
			if($maxconn>0){
				$maxconn_text="on-overload=$on_overload max-conn=$maxconn ";
			}
			
			$uriAddr="$ipaddr:$port";
			$icap_server=$ligne["icap_server"];
			
			
			
			if(($ID==1) OR ($ID==2)){
				if($CicapEnabled==0){
					$this->icap_lines[]="# Local Service C-ICAP is disabled (CicapEnabled=$CicapEnabled) [".__LINE__."]";
					continue;
				}
				$icap_server="srv_clamav";
				$uriAddr=$this->CICAP_URI();
			}
			
			$this->icap_lines[]="icap_service {$ligne["service_key"]} {$ligne["respmod"]} icap://$uriAddr/$icap_server {$maxconn_text}routing=$routing bypass=$bypass";
			$this->CountDeServices++;
			$this->icap_classes[$ligne["service_key"]]=$ID;
		}
		
		if($this->CountDeServices==0){
			$this->icap_lines[]="# No services after the MySQL loop";
			$conf[]=@implode("\n", $this->icap_lines)."\n";
			@file_put_contents("/etc/squid3/icap.conf", @implode("\n", $conf));
			return;
		}
		
		
		$this->icap_lines[]="";
		$this->icap_lines[]="# ICAP configs [".__LINE__."]";
		$this->icap_lines[]="icap_enable on";
		$this->icap_lines[]="icap_preview_size 1024";
		$this->icap_lines[]="icap_service_failure_limit -1";
		$this->icap_lines[]="icap_preview_enable on";
		$this->icap_lines[]="icap_send_client_ip on";
		$this->icap_lines[]="icap_send_client_username on";
		$this->icap_lines[]="icap_client_username_header X-Authenticated-User";
		$this->icap_lines[]="icap_client_username_encode on";
		
		$this->ADAPTATIONS();
		$conf[]="# --------- ICAP Services: $this->CountDeServices service(s) [".__LINE__."]\n";
		$conf[]=@implode("\n", $this->icap_lines)."\n";
		@file_put_contents("/etc/squid3/icap.conf", @implode("\n", $conf));
		
	}
	
	private function ADAPTATIONS(){
		$sock=new sockets();
		$SquidDebugPort=intval($sock->GET_INFO("SquidDebugPort"));
		
		
		
		$this->icap_lines[]="";
		$this->icap_lines[]="# Adaptations classes [".__LINE__."]";
		while (list ($servicekey, $ID) = each ($this->icap_classes) ){
			$ACL_AV=false;
			$this->icap_lines[]="# Adaptation for service $servicekey/$ID [".__LINE__."]";
			$this->icap_lines[]="adaptation_service_set class_{$servicekey} $servicekey";
			if($SquidDebugPort==1){$this->icap_lines[]="adaptation_access class_{$servicekey} deny MyTestPort";}
			$this->icap_lines[]="adaptation_access class_{$servicekey} deny ToArticaWWW";
			$this->icap_lines[]="adaptation_access class_{$servicekey} deny UrlRewriteDenyList";
			$this->icap_lines[]="adaptation_access class_{$servicekey} deny ArticaMetaWhiteDoms";
			$this->icap_lines[]="adaptation_access class_{$servicekey} deny ArticaMetaWhiteIPs";
			
			if($ID==1){$ACL_AV=true;}
			if($ID==2){$ACL_AV=true;}
			if($ID==3){$ACL_AV=true;}
			if($ID==4){$ACL_AV=true;}
			if($ID==5){$ACL_AV=true;}
			if($ID==6){$ACL_AV=true;}	
			if($ID==7){$ACL_AV=true;}
			if($ID==8){$ACL_AV=true;}	

			
			if($ACL_AV){
	//-------------------------------------------------------------------------------------------				
				$q=new mysql_squid_builder();
				$acls=new squid_acls();
				$acls->Build_Acls();
				$sql="SELECT *  FROM `global_whitelist` WHERE enabled=1 and deny_icap=1";
				$results = $q->QUERY_SQL($sql);
				while ($ligne = mysql_fetch_assoc($results)) {
					$acls_name="Group{$ligne["groupid"]}";
					$this->icap_lines[]="# Group {$ligne["groupid"]} not used";
					if(!$acls->availableGroups[$ligne["groupid"]]){
						$this->icap_lines[]="# Group {$ligne["groupid"]} not used";
						continue;
					}
					$this->icap_lines[]="adaptation_access class_{$servicekey} deny $acls_name";
					
				}
					
	//-------------------------------------------------------------------------------------------				
				
				if(count($GLOBALS["ACL_WHITELISTED_FOR_ANTIVIRUS"])>0){
					reset($GLOBALS["ACL_WHITELISTED_FOR_ANTIVIRUS"]);
					while (list ($index, $line) = each ($GLOBALS["ACL_WHITELISTED_FOR_ANTIVIRUS"]) ){
						$this->icap_lines[]="adaptation_access class_{$servicekey} deny $line";
					}
				}
				
				if(isset($GLOBALS["WHITELISTED_MIMETYPE"])){
					if(count($GLOBALS["WHITELISTED_MIMETYPE"])>0){
						$this->icap_lines[]="adaptation_access class_{$servicekey} deny StreamMimeWhite";
					}
				}
			
			}
			
			
			
			
			$this->icap_lines[]="adaptation_access class_{$servicekey} allow all";
			
		}
		
		
	}
	
	
	private function CICAP_URI(){
		$sock=new sockets();
		if(!class_exists("unix")){include_once('/usr/share/artica-postfix/framework/class.unix.inc');}
		if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
		$CICAPListenAddress=$sock->GET_INFO("CICAPListenAddress");
		if($CICAPListenAddress==null){$CICAPListenAddress="127.0.0.1";}
		$NETWORK_ALL_INTERFACES=$GLOBALS["CLASS_UNIX"]->NETWORK_ALL_INTERFACES(true);
		if(!isset($NETWORK_ALL_INTERFACES[$CICAPListenAddress])){$CICAPListenAddress="127.0.0.1";}
		return "$CICAPListenAddress:1345";
	}
	
	
	

	

	
	private function C_ICAP_30(){
		if($this->enable_cicap<>1){return null;}
		if($this->enable_kavproxy==1){
			$this->enable_cicap=0;
			return null;
		}
		$users=new usersMenus();
		$this->ICAP_SERVICES_COUNT=$this->ICAP_SERVICES_COUNT+1;
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." Squid activating C-ICAP 3.0.x\n";}
		
		if($this->enable_metascanner==1){$this->EnableClamavInCiCap=0;}
		
		$kav_rules[]="# --------- icap_service C-ICAP (mode 3.0.x)";
		
		if($this->EnableSquidGuardInCiCAP==1){$kav_rules[]="icap_service\tservice_req reqmod_precache O icap://$this->IcapServerAddress:1345/srv_url_check";}
		if(!$users->KASPERSKY_WEB_APPLIANCE){
			if($this->EnableClamavInCiCap==1){$kav_rules[]="icap_service\tservice_avi respmod_precache 1 icap://$this->IcapServerAddress:1345/srv_clamav";}
		}
		if($this->enable_metascanner==1){$kav_rules[]="icap_service\tservice_khse respmod_precache 1 icap://$this->IcapServerAddress:1345/khse";}		
		
		
		$kav_rules[]="";
		if($this->EnableClamavInCiCap==1){$kav_rules[]="icap_class\tclass_antivirus service_avi";}
		if($this->EnableSquidGuardInCiCAP==1){$kav_rules[]="icap_class\tclass_antivirus_req service_req";}	
		if($this->EnableClamavInCiCap==1){$kav_rules[]="icap_access\tclass_antivirus allow all";}
		if($this->EnableSquidGuardInCiCAP==1){$kav_rules[]="icap_access\tclass_antivirus_req allow all";}
		
		if($this->enable_metascanner==1){
			$kav_rules[]="icap_class\tclass_khse service_khse";
			$kav_rules[]="icap_access\tclass_khse allow all";
		}
		
		$kav_rules[]="";
		return implode("\n",$kav_rules);
	}
	

	private function KAV_ICAP_30(){
		if($this->enable_kavproxy<>1){
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." Squid Kaspersky ICAP is disabled\n";}
				return;
			}
		
		$IpAddrr=$this->IcapServerAddress;$port=1344;
		if($this->EnableKavICAPRemote==1){$IpAddrr=$this->KavICAPRemoteAddr;$port=$this->KavICAPRemotePort;}
		if($this->EnableKavICAPRemote==0){$kav=new Kav4Proxy();if(preg_match("#(.+?):([0-9]+)#", $kav->main_array["ListenAddress"],$re)){$IpAddrr=$re[1];$port=$re[2];}}
		
		$this->ICAP_SERVICES_COUNT=$this->ICAP_SERVICES_COUNT+1;			
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." Squid activating Kaspersky ICAP Mode\n";}
		$kav_rules[]="# --------- icap_service KASPERSKY mode 3.0.x";
		$kav_rules[]="";
		$kav_rules[]="icap_service\tis_kav_resp respmod_precache 0 icap://$IpAddrr:$port/av/respmod";
		$kav_rules[]="icap_service\tis_kav_req reqmod_precache 0 icap://$IpAddrr:$port/av/reqmod";
		$kav_rules[]="";
		$kav_rules[]="# --------- icap_class";
		$kav_rules[]="icap_class\tic_kav_resp is_kav_resp";
		$kav_rules[]="icap_class\tic_kav_req is_kav_req"; 
		$kav_rules[]="";
		$kav_rules[]="# --------- icap_access";
		$kav_rules[]="icap_access\tic_kav_resp allow all";
		$kav_rules[]="icap_access\tic_kav_req allow all !acl_kav_GET";			
		$kav_rules[]="";	
		return implode("\n",$kav_rules);	
	}
	
	
	private function ADAPTATION_31(){
		if($this->ICAP_SERVICES_COUNT<2){
			$r[]=$this->C_ICAP_ADAPTATION();
			$r[]=$this->KAV_ADAPTATION();
			return @implode("\n",$r);
		}
		
		return $this->ICAP_CHAINS();
		
		
	}
	
	private function C_ICAP_ADAPTATION(){
		if($this->enable_cicap<>1){return null;}
		$sock=new sockets();
		$DisableCicapDNBSL=$sock->GET_INFO("DisableCicapDNBSL");
		if(!is_numeric($DisableCicapDNBSL)){$DisableCicapDNBSL=1;}		
		$kav_rules[]="# --------- adaptation for C-ICAP service";
		$kav_rules[]="";
		$class_url_check=FALSE;
		
		if($this->enable_squidguard==1){	
			if($this->EnableSquidGuardInCiCAP==1){	
				$class_url_check=true;
				$kav_rules[]="adaptation_service_set  class_url_check  service_url_check";
				$kav_rules[]="adaptation_access  class_url_check  allow all";
			}
		}
		
		if($this->C_ICAP_31_icap_service()){
				$kav_rules[]="adaptation_service_set class_antivirus service_antivir";
				if(count($GLOBALS["ACL_WHITELISTED_FOR_ANTIVIRUS"])>0){
					reset($GLOBALS["ACL_WHITELISTED_FOR_ANTIVIRUS"]);
					while (list ($index, $line) = each ($GLOBALS["ACL_WHITELISTED_FOR_ANTIVIRUS"]) ){$kav_rules[]="adaptation_access class_antivirus deny $line";}
				}				
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ICAP]: C-ICAP, StreamMimeWhite:". count(count($GLOBALS["WHITELISTED_MIMETYPE"]))." exclusion(s)\n";}
				if(count($GLOBALS["WHITELISTED_MIMETYPE"])>0){$kav_rules[]="adaptation_access class_antivirus deny StreamMimeWhite";}				
				
				$kav_rules[]="adaptation_access class_antivirus allow all";
			
		}else{
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ICAP]: C-ICAP, adaptation_service, skipping\n";}
		}

		if(!$class_url_check){
			if($DisableCicapDNBSL==0){
				$kav_rules[]="adaptation_service_set  class_url_check  service_url_check";
				$kav_rules[]="adaptation_access  class_url_check  allow all";				
			}
			
		}
		
		
		return @implode("\n",$kav_rules);	
	}
	
	private function KAV_ADAPTATION(){
		if($this->enable_kavproxy<>1){return null;}
		$kav_rules[]="# --------- adaptation For Kaspersky Antivirus";
		$kav_rules[]="";
		$kav_rules[]="adaptation_service_set class_antivirus_kav_resp is_kav_resp";
		$kav_rules[]="adaptation_service_set class_antivirus_kav_req is_kav_req";	
		//$kav_rules[]="adaptation_access class_antivirus_kav_req deny MULTIMEDIA";
		
			$acls=new squid_acls_groups();
			$acls_deny=$acls->buildacls_bytype("adaptation_access_deny");
			if(count($acls_deny)>0){
				while (list ($index, $line) = each ($acls_deny) ){
					$kav_rules[]="adaptation_access class_antivirus_kav_req deny $line";
					$kav_rules[]="adaptation_access class_antivirus_kav_resp deny $line";
				}
			}		
		
		$kav_rules[]="adaptation_access class_antivirus_kav_req allow all";
		$kav_rules[]="adaptation_access class_antivirus_kav_resp allow all";
		$kav_rules[]="";			
		return @implode("\n",$kav_rules);	
	}	
	
	
	private function ICAP_CHAINS(){
		$f[]="# --------- adaptation chain services.";
		
		$class_url_check=FALSE;
		$kav_rules[]="";
		if($this->enable_cicap){
			$sock=new sockets();
			$DisableCicapDNBSL=$sock->GET_INFO("DisableCicapDNBSL");
			if(!is_numeric($DisableCicapDNBSL)){$DisableCicapDNBSL=0;}	
			
			if($this->enable_metascanner==1){$a[]="khse";}			
			if($this->EnableClamavInCiCap==1){$a[]="service_antivir";}
			
			if($this->enable_squidguard==1){
				if($this->EnableSquidGuardInCiCAP==1){
					$class_url_check=true;
					$b[]="service_url_check";
				}
			}
			

			
			if(!$class_url_check){if($DisableCicapDNBSL==0){$b[]="service_url_check";}}
	
		}
			
		if($this->enable_kavproxy){$a[]="is_kav_resp";$b[]="is_kav_req";}
		if(count($a)>0){
			$f[]="adaptation_service_chain ANTIVIRUS_CHAINS ".@implode(" ",$a);
			//$f[]="adaptation_access ANTIVIRUS_CHAINS deny MULTIMEDIA";
			if(isset($GLOBALS["ICAP"]["WHITEAUTH"])){
				if($GLOBALS["ICAP"]["WHITEAUTH"]<>null){
					$f[]=$GLOBALS["ICAP"]["WHITEAUTH"];
				}
			}
			
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ICAP]: ".count($GLOBALS["WHITELISTED_MIMETYPE"])." exclusion(s)\n";}
			if(count($GLOBALS["ACL_WHITELISTED_FOR_ANTIVIRUS"])>0){
				reset($GLOBALS["ACL_WHITELISTED_FOR_ANTIVIRUS"]);
				while (list ($index, $line) = each ($GLOBALS["ACL_WHITELISTED_FOR_ANTIVIRUS"]) ){$f[]="adaptation_access ANTIVIRUS_CHAINS deny $line";}
			}
			
			if(count($GLOBALS["WHITELISTED_MIMETYPE"])>0){
				$f[]="adaptation_access ANTIVIRUS_CHAINS deny StreamMimeWhite";
			}

			$acls=new squid_acls_groups();
			$acls_deny=$acls->buildacls_bytype("adaptation_access_deny");
			if(count($acls_deny)>0){
				while (list ($index, $line) = each ($acls_deny) ){$f[]="adaptation_access ANTIVIRUS_CHAINS deny $line";}
			}
			
			
			
			
			
			$f[]="adaptation_access ANTIVIRUS_CHAINS allow all";
		}
		
		if(is_array($b)){
		 if(count($b)>0){
				$f[]="";
				$f[]="adaptation_service_chain REQ_CHAINS ".@implode(" ",$b);
				$f[]="adaptation_access REQ_CHAINS allow all";
			}
		}
		return @implode("\n",$f);	
	}
	
	private function C_ICAP_31_icap_service(){
		if(!is_numeric($this->EnableRemoteStatisticsAppliance)){$this->EnableRemoteStatisticsAppliance=0;}
		if($this->EnableRemoteStatisticsAppliance==0){
			if($this->enable_metascanner==1){
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ICAP]: C-ICAP Metascanner is enabled, skipping\n";}
				return;}
			if($this->EnableClamavInCiCap==0){
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ICAP]: C-ICAP ClamAV is disabled inside, skipping\n";}
				return;}
			if(!$this->C_ICAP_31_is_scanner_exists()){
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ICAP]: C-ICAP, did not find any scanner, skipping\n";}
				return;
			}
		}
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ICAP]: C-ICAP OK all parameters done...\n";}
		return true;
		
	}
	
	
	private function C_ICAP_31_is_scanner_exists(){
		$modules_path=$this->LOCATE_C_ICAP_MODULES_DIR();
		$result=false;
		if(!is_dir($modules_path)){
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ICAP]: library path not found `$modules_path`\n";}
			return false;
		}
		if(is_file("$modules_path/srv_clamav.so")){$result=true;}
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ICAP]: $modules_path/srv_clamav.so no such file\n";}
		if(is_file("$modules_path/virus_scan.so")){$result=true;}
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ICAP]: $modules_path/virus_scan.so no such file\n";}
		if(!$result){if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ICAP]: C-ICAP Failed, no library found in `$modules_path`\n";}}
		if(!$result){
			if($this->ASROOT){
				$unix=new unix();
				$nohup=$unix->find_program("nohup");
				if($this->ASROOT){echo "\n***************\nStarting......: ".date("H:i:s")." [ICAP]: Installing C-ICAP application !!!!\n\n***************\n";}
				shell_exec("$nohup /usr/share/artica-postfix/bin/artica-make APP_C_ICAP >/dev/null 2>&1 &");
			}
		}
		
		return $result;
	}

	
	private function C_ICAP_31(){
		$users=new usersMenus();
		$class_url_check=FALSE;
		if($this->enable_cicap==0){
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ICAP]: C-ICAP disabled...\n";}
			return null;
		}
		$sock=new sockets();
		$DisableCicapDNBSL=$sock->GET_INFO("DisableCicapDNBSL");
		if(!is_numeric($DisableCicapDNBSL)){$DisableCicapDNBSL=1;}			
		
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ICAP]: Activating C-ICAP\n";}
		$kav_rules[]="# --------- icap_service C-ICAP mode 3.1.x";
		

		if($this->enable_squidguard==1){
			if($this->EnableSquidGuardInCiCAP==1){
				$this->ICAP_SERVICES_COUNT=$this->ICAP_SERVICES_COUNT+1;
				$class_url_check=true;
				$kav_rules[]="# --------- icap_service C-ICAP + SquidGuard";
				$kav_rules[]="icap_service\tservice_url_check reqmod_precache routing=on bypass=on icap://$this->IcapServerAddress:1345/url_check";
				}
			}
			
			
		if(!$class_url_check){
			if($DisableCicapDNBSL==0){
				$this->ICAP_SERVICES_COUNT=$this->ICAP_SERVICES_COUNT+1;
				$kav_rules[]="# --------- icap_service C-ICAP + DNSBL";
				$kav_rules[]="icap_service\tservice_url_check reqmod_precache routing=on bypass=on icap://$this->IcapServerAddress:1345/url_check";				
			}
		}

		if($this->EnableClamavInCiCap==0){
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ICAP]: EnableClamavInCiCap is disabled\n";}
		}
		
		
		
		if($this->C_ICAP_31_icap_service()){
			$this->ICAP_SERVICES_COUNT=$this->ICAP_SERVICES_COUNT+1;
			$kav_rules[]="icap_service  service_antivir respmod_precache routing=on bypass=on icap://$this->IcapServerAddress:1345/srv_clamav";
		}

			
			
	   if($this->enable_metascanner==1){
	   		if(is_file("/opt/kaspersky/khse/libexec/libms-icap.so")){
	   			$this->ICAP_SERVICES_COUNT=$this->ICAP_SERVICES_COUNT+1;
	   			$kav_rules[]="icap_service  khse respmod_precache routing=on bypass=on icap://$this->IcapServerAddress:1345/khse";
	   		}
	   }		
		return implode("\n",$kav_rules);		
	}
			

		
	private function KAV_ICAP_31(){
		if($this->enable_kavproxy<>1){
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." Squid Kaspersky ICAP is disabled\n";}
				return;
			}		
		$this->ICAP_SERVICES_COUNT=$this->ICAP_SERVICES_COUNT+1;
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." Squid adaptation enabled\n";}
		
		$IpAddrr=$this->IcapServerAddress;$port=1344;
		if($this->EnableKavICAPRemote==1){$IpAddrr=$this->KavICAPRemoteAddr;$port=$this->KavICAPRemotePort;}
		if($this->EnableKavICAPRemote==0){$kav=new Kav4Proxy();if(preg_match("#(.+?):([0-9]+)#", $kav->main_array["ListenAddress"],$re)){$IpAddrr=$re[1];$port=$re[2];}}
		
		$kav_rules[]="# --------- icap_service KASPERSKY mode 3.1.1";
		$kav_rules[]="";
		$kav_rules[]="icap_service\tis_kav_resp respmod_precache routing=on bypass=on icap://$IpAddrr:$port/av/respmod";
		$kav_rules[]="icap_service\tis_kav_req reqmod_precache routing=on bypass=on icap://$IpAddrr:$port/av/reqmod";

		return implode("\n",$kav_rules);	
	}

	public function LOCATE_C_ICAP_MODULES_DIR(){
		$f[]="virus_scan.so";
		$f[]="sys_logger.so";
		$f[]="srv_echo.so";
		
		$possibleDirs[]="/usr/lib/c_icap";
		$possibleDirs[]="/var/lib/c_icap";
		
		while (list ($index, $dir) = each ($possibleDirs) ){
			
			reset($f);
			while (list ($a, $filename) = each ($f) ){
				if(is_file("$dir/$filename")){
					if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ICAP]: $dir/$filename OK Find library `$filename` done...\n";}
					return $dir;
				}
				if($GLOBALS["VERBOSE"]){
					if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ICAP]: $dir/$filename no such file\n";}
				}
			}
		}
		
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ICAP]: Warning, return default C-ICAP folder `/usr/lib/c_icap`\n";}
		return "/usr/lib/c_icap";
	}	
	
	
	
}
?>