<?php


class updateutilityv2{
var $MAIN_ARRAY=array();	
var $families=array();
var $ALL_PKEYS=array();
	
	function updateutilityv2(){
		if(!isset($GLOBALS["AS_ROOT"])){if(posix_geteuid()==0){$GLOBALS["AS_ROOT"]=true;}else{$GLOBALS["AS_ROOT"]=false;}}
		$sock=new sockets();
		$DefaultConfig=base64_decode($sock->GET_INFO("UpdateUtilityV2Config"));
		if($DefaultConfig==null){$DefaultConfig=$this->default_settings_string();}
		$ini= new Bs_IniHandler();
		$ini->loadString($DefaultConfig);
		$this->MAIN_ARRAY=$ini->_params;
		$this->ParseProductList();
	}
	
	
	
	private function default_settings_string(){
			$sock=new sockets();
			$UpdateUtilityStorePath=$sock->GET_INFO("UpdateUtilityStorePath");
			if($UpdateUtilityStorePath==null){$UpdateUtilityStorePath="/home/kaspersky/UpdateUtility";}
			$f[]="[ConnectionSettings]";
			$f[]="TimeoutConnection=60";
			$f[]="UsePassiveFtpMode=true";
			$f[]="UseProxyServer=false";
			$f[]="AutomaticallyDetectProxyServerSettings=true";
			$f[]="UseSpecifiedProxyServerSettings=false";
			$f[]="AddressProxyServer=";
			$f[]="PortProxyServer=8080";
			$f[]="UseAuthenticationProxyServer=false";
			$f[]="UserNameProxyServer=";
			$f[]="PasswordProxyServer=";
			$f[]="ByPassProxyServer=true";
			$f[]="";
			$f[]="[AdditionalSettings]";
			$f[]="CreateCrashDumpFile=true";
			$f[]="TurnTrace=false";
			$f[]="AddIconToTray=true";
			$f[]="MinimizeProgramUponTermination=true";
			$f[]="AnimateIcon=true";
			$f[]="LanguagesBox=0";
			$f[]="ReturnCodeDesc=";
			$f[]="";
		
			$f[]="[ReportSettings]";
			$f[]="DisplayReportsOnScreen=false";
			$f[]="SaveReportsToFile=true";
			$f[]="AppendToPreviousFile=true";
			$f[]="SizeLogFileValue=1048576";
			$f[]="ReportFileName=report.txt";
			$f[]="DeleteIfSize=true";
			$f[]="DeleteIfNumDay=false";
			$f[]="NoChangeLogFile=false";
			$f[]="NumDayLifeLOgFileValue=7";
			$f[]="";
			$f[]="[DirectoriesSettings]";
			$f[]="MoveToCurrentFolder=false";
			$f[]="MoveToCustomFolder=true";
			$f[]="UpdatesFolder=$UpdateUtilityStorePath/databases";
			$f[]="TempFolder=$UpdateUtilityStorePath/TempFolder";
			$f[]="ClearTempFolder=true";
			$f[]="";
			$f[]="[UpdatesSourceSettings]";
			$f[]="SourceCustomPath=";
			$f[]="SourceCustom=false";
			$f[]="SourceKlabServer=true";
			$f[]="";
			$f[]="[DownloadingSettings]";
			$f[]="DownloadDataBasesAndModules=true";
			$f[]="";
			$f[]="";
			$f[]="[ShedulerSettings]";
			$f[]="LastUpdate=@Variant(\0\0\0\x10\0\0\0\0\xff\xff\xff\xff\xff)";
			$f[]="ShedulerType=0";
			$f[]="PeriodValue=1";
			$f[]="UseTime=true";
			$f[]="Time=@Variant(\0\0\0\xf\0\0\0\0)";
			$f[]="Monday=true";
			$f[]="Tuesday=true";
			$f[]="Wednesday=true";
			$f[]="Thursday=true";
			$f[]="Friday=true";
			$f[]="Saturday=true";
			$f[]="Sunday=true";
			$f[]="";
			$f[]="[SdkSettings]";
			$f[]="PrimaryIndexFileName=u0607g.xml";
			$f[]="PrimaryIndexRelativeUrlPath=index";
			$f[]="LicensePath=";
			$f[]="SimpleModeLicensing=true";	
			return @implode("\n", $f);	
	}
	
	private function ParseProductList(){
		$sock=new sockets();
		if($GLOBALS["AS_ROOT"]){
			$localfile=@file_get_contents("/etc/UpdateUtility/locale.ini");
		}else{
			$localfile=base64_decode($sock->getFrameWork("services.php?updateutility-local=yes"));
		}
		$GLOBALS["LOCAL_FILES"]=explode("\n",$localfile);
		$GLOBALS["UPDATE_LOCAL"]=new Bs_IniHandler();
		$GLOBALS["UPDATE_LOCAL"]->loadString($localfile);
		
		$Array=$GLOBALS["UPDATE_LOCAL"]->_params["English"];
	
		while (list ($num, $ligne) = each ($Array) ){
			if(preg_match("#^(.+)_button#", $num,$re)){
				$this->families[$re[1]]["NAME"]=$Array[$re[1]];
				$this->families[$re[1]]["LIST"]=$this->find_product_codes($re[1]);
			}
			
		}

	}
	
	public function Save(){
		$ini=new Bs_IniHandler();
		$ini->_params=$this->MAIN_ARRAY;
		$sock=new sockets();
		$sock->SaveConfigFile(base64_encode($ini->toString()), "UpdateUtilityV2Config");
		
	}
	
	private function find_product_codes($PrefixKey){
		$Array=$GLOBALS["UPDATE_LOCAL"]->_params["English"];
		while (list ($num, $ligne) = each ($Array) ){
			if(preg_match("#^{$PrefixKey}_(.+?)_Header#", $num,$re)){
				if($ligne=="Administration Tools"){continue;}
				$FINAL[$re[1]]["NAME"]=$ligne;
				$FINAL[$re[1]]["PRODUCTS"]=$this->find_product_codes_key($re[1]);
			}
			
		}
		if($PrefixKey=="Workstations"){
			$FINAL["KasperskyAntiVirusWindowsWorkstation"]["NAME"]="Kaspersky Anti-Virus for Windows Workstations";
			$FINAL["KasperskyAntiVirusWindowsWorkstation"]["PRODUCTS"]=$this->find_product_codes_key("KasperskyAntiVirusWindowsWorkstation");
		}
		if($PrefixKey=="FileServers"){
			$FINAL["KasperskyAntiVirusWindowsServerEE"]["NAME"]="Kaspersky Anti-Virus for Windows Servers Enterprise Edition";
			$FINAL["KasperskyAntiVirusWindowsServerEE"]["PRODUCTS"]=$this->find_product_codes_key("KasperskyAntiVirusWindowsServerEE");
		}		
		
		
		
		
		
		if($PrefixKey=="PerimeterProtection"){	
			$FINAL["KasperskyAntiVirusLinuxMailServers"]["NAME"]="Kaspersky Anti-Virus for Unix Mail Servers";
			$FINAL["KasperskyAntiVirusLinuxMailServers"]["PRODUCTS"]=$this->find_product_codes_key("KasperskyAntiVirusLinuxMailServers");
			
			$FINAL["KasperskySendMailMilterApi"]["NAME"]="Kaspersky Anti-Virus for Sendmail with Milter API";
			$FINAL["KasperskySendMailMilterApi"]["PRODUCTS"]=$this->find_product_codes_key("KasperskySendMailMilterApi");
			
			$FINAL["KasperskyMailGateway"]["NAME"]="Kaspersky Mail Gateway";
			$FINAL["KasperskyMailGateway"]["PRODUCTS"]=$this->find_product_codes_key("KasperskyMailGateway");
						
			$FINAL["KasperskyAntiSpam"]["NAME"]="Kaspersky Anti-Spam";
			$FINAL["KasperskyAntiSpam"]["PRODUCTS"]=$this->find_product_codes_key("KasperskyAntiSpam");			
			
			$FINAL["KasperskyAntiVirusProxyServer"]["NAME"]="Kaspersky Anti-Virus for Proxy Server";
			$FINAL["KasperskyAntiVirusProxyServer"]["PRODUCTS"]=$this->find_product_codes_key("KasperskyAntiVirusProxyServer");				
						
			
			
			
			$FINAL["KasperskyAntiVirusMicrosoftIsaServers"]["NAME"]="Kaspersky Anti-Virus for Microsoft ISA Server";
			$FINAL["KasperskyAntiVirusMicrosoftIsaServers"]["PRODUCTS"]=$this->find_product_codes_key("KasperskyAntiVirusMicrosoftIsaServers");				
			
		}
		
		return $FINAL;
	}
	
	private function find_product_codes_key($PrefixKey){
		$Array=$GLOBALS["UPDATE_LOCAL"]->_params["English"];
		while (list ($num, $ligne) = each ($Array) ){
			if(preg_match("#^{$PrefixKey}(_|[0-9])#", $num,$re)){
				$key=$num;
				$this->ALL_PKEYS[$key]=true;
				$FINAL[$key]=$ligne;
			}
		}
		return $FINAL;
	}

	
}

