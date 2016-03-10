<?php
include_once(dirname(__FILE__)."/class.sockets.inc");

class squid_watchdog{
	
	public $MonitConfig;
	
	
	function squid_watchdog(){
		$this->getConfig();
		
		
	}	
	
	public function getConfig(){
		$this->sock=new sockets();
		$this->MonitConfig=unserialize(base64_decode($this->sock->GET_INFO("SquidWatchdogMonitConfig")));
		$this->MonitConfig=$this->watchdog_config_default($this->MonitConfig);
		
		
	} 
	
	private function CPU_NUMBER(){
		if(class_exists("unix")){
			$unix=new unix();
			return $unix->CPU_NUMBER();
		}
		
		if(!is_file("/usr/share/artica-postfix/ressources/interface-cache/CPU_NUMBER")){
			$sock=new sockets();
			$cpunum=intval($sock->getFrameWork("services.php?CPU-NUMBER=yes"));
		}else{
			$cpunum=intval(@file_get_contents("/usr/share/artica-postfix/ressources/interface-cache/CPU_NUMBER"));
		}
		
		return intval($cpunum);
	}
	
	private function watchdog_config_default($MonitConfig){
		
	
		if(!is_file("/etc/artica-postfix/settings/Daemons/DisableLogFileDaemonMySQL")){@file_put_contents("/etc/artica-postfix/settings/Daemons/DisableLogFileDaemonMySQL", 1);}
	
		if(!isset($MonitConfig["MEMORY_TEST"])){$MonitConfig["MEMORY_TEST"]=1;}
		
		if(!isset($MonitConfig["MAX_MEM_ALERT"])){$MonitConfig["MAX_MEM_ALERT"]=90;}
		if(!isset($MonitConfig["MAX_MEM_PRC"])){$MonitConfig["MAX_MEM_PRC"]=95;}
		if(!isset($MonitConfig["MAX_MEM_MNS"])){$MonitConfig["MAX_MEM_MNS"]=5;}
		if(!isset($MonitConfig["MAX_MEM_RST_MYSQL"])){$MonitConfig["MAX_MEM_RST_MYSQL"]=1;}
		if(!isset($MonitConfig["MAX_MEM_RST_UFDB"])){$MonitConfig["MAX_MEM_RST_UFDB"]=1;}
		if(!isset($MonitConfig["MAX_MEM_RST_APACHE"])){$MonitConfig["MAX_MEM_RST_APACHE"]=1;}
		if(!isset($MonitConfig["MAX_MEM_RST_SQUID"])){$MonitConfig["MAX_MEM_RST_SQUID"]=1;}
		
		if(!isset($MonitConfig["CHECK_BANDWITDH"])){$MonitConfig["CHECK_BANDWITDH"]=0;}
		if(!isset($MonitConfig["CHECK_BANDWITDH_INTERVAL"])){$MonitConfig["CHECK_BANDWITDH_INTERVAL"]=60;}
		if(!isset($MonitConfig["CHECK_BANDWITDH_SIZE"])){$MonitConfig["CHECK_BANDWITDH_SIZE"]=500;}
		
		if(!is_numeric($MonitConfig["CHECK_BANDWITDH"])){$MonitConfig["CHECK_BANDWITDH"]=0;}
		if(!is_numeric($MonitConfig["CHECK_BANDWITDH_SIZE"])){$MonitConfig["CHECK_BANDWITDH_SIZE"]=500;}
		
		
		
		if(!is_numeric($MonitConfig["MEMORY_TEST"])){$MonitConfig["MEMORY_TEST"]=1;}
		if(!is_numeric($MonitConfig["MAX_MEM_PRC"])){$MonitConfig["MAX_MEM_PRC"]=90;}
		if(!is_numeric($MonitConfig["MAX_MEM_MNS"])){$MonitConfig["MAX_MEM_MNS"]=5;}
		
		if(!is_numeric($MonitConfig["MAX_MEM_RST_MYSQL"])){$MonitConfig["MAX_MEM_RST_MYSQL"]=1;}
		if(!is_numeric($MonitConfig["MAX_MEM_RST_UFDB"])){$MonitConfig["MAX_MEM_RST_UFDB"]=1;}
		if(!is_numeric($MonitConfig["MAX_MEM_RST_APACHE"])){$MonitConfig["MAX_MEM_RST_APACHE"]=1;}
		if(!is_numeric($MonitConfig["MAX_MEM_RST_SQUID"])){$MonitConfig["MAX_MEM_RST_SQUID"]=1;}
		
		if(!is_numeric($MonitConfig["WEBPROCISSUE"])){$MonitConfig["WEBPROCISSUE"]=3;}
		
		
		
	
		if(!isset($MonitConfig["LOAD_TESTS"])){$MonitConfig["LOAD_TESTS"]=1;}
		if(!isset($MonitConfig["LOAD_MAX_ACTION"])){$MonitConfig["LOAD_MAX_ACTION"]="none";}
		if($MonitConfig["LOAD_MAX_ACTION"]==null){$MonitConfig["LOAD_MAX_ACTION"]="none";}
		if(!isset($MonitConfig["LOAD_WARNING"])){$MonitConfig["LOAD_WARNING"]=0;}
		if(!is_numeric($MonitConfig["LOAD_WARNING"])){$MonitConfig["LOAD_WARNING"]=0;}
		if(!isset($MonitConfig["LOAD_WARNING"])){$MonitConfig["LOAD_WARNING"]=0;}
		if(!is_numeric($MonitConfig["LOAD_WARNING"])){$MonitConfig["LOAD_WARNING"]=0;}
		if(!isset($MonitConfig["LOAD_MAX"])){$MonitConfig["LOAD_MAX"]=0;}
		if(!is_numeric($MonitConfig["LOAD_MAX"])){$MonitConfig["LOAD_MAX"]=0;}
	
		if($MonitConfig["LOAD_WARNING"]==0){
			$CPU_NUMBER=$this->CPU_NUMBER();
			$Load=$CPU_NUMBER*1.7;
			$Load=round($Load,2);
			$MonitConfig["LOAD_WARNING"]=$Load;
		}
	
		if($MonitConfig["LOAD_MAX"]==0){$MonitConfig["LOAD_MAX"]=30;}
		
		
		if(!isset($MonitConfig["CHECK_AD"])){$MonitConfig["CHECK_AD"]=1;}
		if(!isset($MonitConfig["CHECK_AD_INTERVAL"])){$MonitConfig["CHECK_AD_INTERVAL"]=5;}
		
		
		
		if(!isset($MonitConfig["CHECK_AD_ACTION"])){$MonitConfig["CHECK_AD_ACTION"]="disable_ad";}
		if(!isset($MonitConfig["CHECK_AD_MAX_ATTEMPTS"])){$MonitConfig["CHECK_AD_MAX_ATTEMPTS"]=3;}
		if(!isset($MonitConfig["CHECK_AD_FAILED_PING"])){$MonitConfig["CHECK_AD_FAILED_PING"]=0;}
	
		if(!is_numeric($MonitConfig["CHECK_AD_INTERVAL"])){$MonitConfig["CHECK_AD_INTERVAL"]=5;}
		if(!is_numeric($MonitConfig["CHECK_AD"])){$MonitConfig["CHECK_AD"]=1;}
		if(!is_numeric($MonitConfig["CHECK_AD_MAX_ATTEMPTS"])){$MonitConfig["CHECK_AD_MAX_ATTEMPTS"]=3;}
		if(!is_numeric($MonitConfig["CHECK_AD_FAILED_PING"])){$MonitConfig["CHECK_AD_FAILED_PING"]=0;}
		if($MonitConfig["CHECK_AD_MAX_ATTEMPTS"]<1){$MonitConfig["CHECK_AD_MAX_ATTEMPTS"]=3;}
		
	
		if(!isset($MonitConfig["TEST_PORT"])){$MonitConfig["TEST_PORT"]=1;}
		if(!isset($MonitConfig["TEST_PORT_MAX"])){$MonitConfig["TEST_PORT_MAX"]=5;}
		if(!isset($MonitConfig["TEST_PORT_INTERVAL"])){$MonitConfig["TEST_PORT_INTERVAL"]=2;}
		if(!isset($MonitConfig["TEST_PORT_RESTART"])){$MonitConfig["TEST_PORT_RESTART"]=1;}
		if(!isset($MonitConfig["TEST_PORT_TIMEOUT"])){$MonitConfig["TEST_PORT_TIMEOUT"]=2;}
		if(!isset($MonitConfig["MAX_RESTART"])){$MonitConfig["MAX_RESTART"]=2;}
		if(!isset($MonitConfig["MaxLoad"])){$MonitConfig["MaxLoad"]=30;}
		if(!isset($MonitConfig["MaxLoadReboot"])){$MonitConfig["MaxLoadReboot"]=0;}
		if(!isset($MonitConfig["MaxLoadFailOver"])){$MonitConfig["MaxLoadFailOver"]=0;}
		if(!isset($MonitConfig["MinFreeMem"])){$MonitConfig["MinFreeMem"]=50;}
	
		if(!isset($MonitConfig["MgrInfosFaileOverFailed"])){$MonitConfig["MgrInfosFaileOverFailed"]=1;}
		if(!isset($MonitConfig["MgrInfosMaxTimeOut"])){$MonitConfig["MgrInfosMaxTimeOut"]=120;}
		if(!isset($MonitConfig["MgrInfosRestartFailed"])){$MonitConfig["MgrInfosRestartFailed"]=1;}
		if(!is_numeric($MonitConfig["MgrInfosRestartFailed"])){$MonitConfig["MgrInfosRestartFailed"]=1;}
		if(!is_numeric($MonitConfig["MgrInfosFaileOverFailed"])){$MonitConfig["MgrInfosFaileOverFailed"]=1;}
	
		if(!isset($MonitConfig["StopMaxTTL"])){$MonitConfig["StopMaxTTL"]=90;}
		if(!is_numeric($MonitConfig["StopMaxTTL"])){$MonitConfig["StopMaxTTL"]=90;}
		if($MonitConfig["StopMaxTTL"]<5){$MonitConfig["StopMaxTTL"]=5;}
	
		if(!isset($MonitConfig["MgrInfosMaxFailed"])){$MonitConfig["MgrInfosMaxFailed"]=2;}
		if(!is_numeric($MonitConfig["MgrInfosMaxFailed"])){$MonitConfig["MgrInfosMaxFailed"]=2;}
		if($MonitConfig["MgrInfosMaxFailed"]==0){$MonitConfig["MgrInfosMaxFailed"]=1;}
	
	
		if(!isset($MonitConfig["MIN_INTERVAL"])){$MonitConfig["MIN_INTERVAL"]=5;}
		if(!isset($MonitConfig["MaxSwapPourc"])){$MonitConfig["MaxSwapPourc"]=10;}
		if(!isset($MonitConfig["REBOOT_INTERVAL"])){$MonitConfig["REBOOT_INTERVAL"]=30;}
		if(!isset($MonitConfig["MinTimeFailOverSwitch"])){$MonitConfig["MinTimeFailOverSwitch"]=15;}
	
		if(!isset($MonitConfig["watchdog"])){$MonitConfig["watchdog"]=1;}
		if(!isset($MonitConfig["watchdogCPU"])){$MonitConfig["watchdogCPU"]=95;}
		if(!isset($MonitConfig["watchdogMEM"])){$MonitConfig["watchdogMEM"]=1500;}
		if(!isset($MonitConfig["MgrInfosMaxTimeOut"])){$MonitConfig["MgrInfosMaxTimeOut"]=120;}
	
		if(!isset($MonitConfig["watchdogRestart"])){$MonitConfig["watchdogRestart"]=80;}
		if(!is_numeric($MonitConfig["watchdogRestart"])){$MonitConfig["watchdogRestart"]=80;}
		if($MonitConfig["watchdogRestart"]==0){$MonitConfig["watchdogRestart"]=80;}
	
		if(!isset($MonitConfig["TestExternalWebPage"])){$MonitConfig["TestExternalWebPage"]=0;}
		if(!isset($MonitConfig["ExternalPageToCheck"])){$MonitConfig["ExternalPageToCheck"]="http://www.google.fr/search?q=%T";}
		if(!isset($MonitConfig["SWAP_MONITOR"])){$MonitConfig["SWAP_MONITOR"]=1;}
		if(!isset($MonitConfig["SWAP_MIN"])){$MonitConfig["SWAP_MIN"]=5;}
		if(!isset($MonitConfig["SWAP_MAX"])){$MonitConfig["SWAP_MAX"]=75;}
		if(!isset($MonitConfig["NotifyDNSIssues"])){$MonitConfig["NotifyDNSIssues"]=0;}
		
		if(!is_numeric($MonitConfig["SWAP_MIN"])){$MonitConfig["SWAP_MIN"]=5;}
		if(!is_numeric($MonitConfig["SWAP_MAX"])){$MonitConfig["SWAP_MAX"]=75;}
	
		if(!is_numeric($MonitConfig["MinFreeMem"])){$MonitConfig["MinFreeMem"]=50;}
		if(!is_numeric($MonitConfig["watchdog"])){$MonitConfig["watchdog"]=1;}
		if(!is_numeric($MonitConfig["watchdogCPU"])){$MonitConfig["watchdogCPU"]=95;}
		if(!is_numeric($MonitConfig["watchdogMEM"])){$MonitConfig["watchdogMEM"]=1500;}
		if(!is_numeric($MonitConfig["MIN_INTERVAL"])){$MonitConfig["MIN_INTERVAL"]=5;}
		if(!is_numeric($MonitConfig["MgrInfosMaxTimeOut"])){$MonitConfig["MgrInfosMaxTimeOut"]=120;}
		if(!is_numeric($MonitConfig["REBOOT_INTERVAL"])){$MonitConfig["REBOOT_INTERVAL"]=30;}
		if(!is_numeric($MonitConfig["MinTimeFailOverSwitch"])){$MonitConfig["MinTimeFailOverSwitch"]=15;}
	
		if(!is_numeric($MonitConfig["MAX_RESTART"])){$MonitConfig["MAX_RESTART"]=2;}
		if(!is_numeric($MonitConfig["TestExternalWebPage"])){$MonitConfig["TestExternalWebPage"]=0;}
	
		if(!isset($MonitConfig["DNSIssuesMAX"])){$MonitConfig["DNSIssuesMAX"]=1;}
		if(!is_numeric($MonitConfig["MaxSwapPourc"])){$MonitConfig["MaxSwapPourc"]=10;}
		if(!is_numeric($MonitConfig["NotifyDNSIssues"])){$MonitConfig["NotifyDNSIssues"]=0;}
		if(!is_numeric($MonitConfig["DNSIssuesMAX"])){$MonitConfig["DNSIssuesMAX"]=1;}
	
		if(!is_numeric($MonitConfig["MaxSwapPourc"])){$MonitConfig["MaxSwapPourc"]=10;}
	
		if(!is_numeric($MonitConfig["MaxLoad"])){$MonitConfig["MaxLoad"]=30;}
		if(!is_numeric($MonitConfig["MaxLoadReboot"])){$MonitConfig["MaxLoadReboot"]=0;}
		if(!is_numeric($MonitConfig["MaxLoadFailOver"])){$MonitConfig["MaxLoadFailOver"]=0;}
	
	
		if($MonitConfig["MgrInfosMaxTimeOut"]<5){$MonitConfig["MgrInfosMaxTimeOut"]=15;}
		if($MonitConfig["MIN_INTERVAL"]<3){$MonitConfig["MIN_INTERVAL"]=3;}
		if($MonitConfig["MaxSwapPourc"]<5){$MonitConfig["MaxSwapPourc"]=5;}
		if($MonitConfig["DNSIssuesMAX"]<1){$MonitConfig["DNSIssuesMAX"]=1;}
		if($MonitConfig["REBOOT_INTERVAL"]<10){$MonitConfig["REBOOT_INTERVAL"]=10;}
		if($MonitConfig["MinTimeFailOverSwitch"]<5){$MonitConfig["MinTimeFailOverSwitch"]=5;}
		if($MonitConfig["CHECK_AD_INTERVAL"]==0){$MonitConfig["CHECK_AD_INTERVAL"]=5;}
	
		if($MonitConfig["ExternalPageToCheck"]==null){$MonitConfig["ExternalPageToCheck"]="http://www.google.fr/search?q=%T";}
		if(!isset($MonitConfig["EnableFailover"])){
			
			$MonitConfig["EnableFailover"]=$this->sock->GET_INFO("EnableFailover");
			if(!is_numeric($MonitConfig["EnableFailover"])){$MonitConfig["EnableFailover"]=1;}
	
		}
		$MonitConfig=$this->PING_GATEWAY_DEFAULT_PARAMS($MonitConfig);
		return $MonitConfig;
	}
	
	
	private function PING_GATEWAY_DEFAULT_PARAMS($MonitConfig){
		if(!isset($MonitConfig["ENABLE_PING_GATEWAY"])){$MonitConfig["ENABLE_PING_GATEWAY"]=0;}
		if(!isset($MonitConfig["MAX_PING_GATEWAY"])){$MonitConfig["MAX_PING_GATEWAY"]=10;}
		if(!isset($MonitConfig["PING_FAILED_RELOAD_NET"])){$MonitConfig["PING_FAILED_RELOAD_NET"]=0;}
		if(!isset($MonitConfig["PING_FAILED_REPORT"])){$MonitConfig["PING_FAILED_REPORT"]=1;}
		if(!isset($MonitConfig["PING_FAILED_REBOOT"])){$MonitConfig["PING_FAILED_REBOOT"]=0;}
		if(!isset($MonitConfig["PING_FAILED_FAILOVER"])){$MonitConfig["PING_FAILED_FAILOVER"]=0;}
		if(!is_numeric($MonitConfig["ENABLE_PING_GATEWAY"])){$MonitConfig["ENABLE_PING_GATEWAY"]=0;}
		if(!is_numeric($MonitConfig["MAX_PING_GATEWAY"])){$MonitConfig["MAX_PING_GATEWAY"]=10;}
		if(!is_numeric($MonitConfig["PING_FAILED_RELOAD_NET"])){$MonitConfig["PING_FAILED_RELOAD_NET"]=0;}
		if(!is_numeric($MonitConfig["PING_FAILED_REPORT"])){$MonitConfig["PING_FAILED_REPORT"]=1;}
		if(!is_numeric($MonitConfig["PING_FAILED_REBOOT"])){$MonitConfig["PING_FAILED_REBOOT"]=0;}
		if(!is_numeric($MonitConfig["PING_FAILED_FAILOVER"])){$MonitConfig["PING_FAILED_FAILOVER"]=0;}
		return $MonitConfig;
	}	
	
	
}