<?php
	include_once(dirname(__FILE__).'/class.mysql.inc');
	include_once(dirname(__FILE__).'/class.sockets.inc');
	include_once(dirname(__FILE__).'/class.resolv.conf.inc');
	include_once(dirname(__FILE__).'/class.tcpip.inc');
	include_once(dirname(__FILE__).'/class.system.network.inc');
	include_once(dirname(__FILE__).'/class.mysql.shorewall.inc');

class system_nic{
	var $eth;
	var $NETMASK;
	var $GATEWAY;
	var $BROADCAST;
	var $IPADDR;
	var $DNS1;
	var $DNS2;
	var $dhcp=0;
	var $ROUTES=array();
	var $UseSPAN=0;
	var $NICNAME=null;
	var $NETWORK;
	var $unconfigured=true;
	var $enabled=0;
	var $NoReboot=false;
	var $EnableipV6=0;
	var $ipv6=0;
	var $ipv6mask=0;
	var $ipv6addr=null;
	var $ipv6gw=null;
	var $metric=0;
	var $mtu=1500;
	var $ucarp_enabled=0;
	var $ucarp_master=0;
	var $ucarp_vid=3;
	var $ucarp_vip='';
	var $ucarp_advskew=1;
	var $ucarp_advbase=1;
	var $Bridged=0;	
	var $BridgedTo=null;
	var $defaultroute=0;
	var $DNS_1=null; 
	var $DNS_2=null;
	var $netzone=null;
	var $SourceBasedRouting=0;
	var $isFW=0;
	var $isFWAcceptNet=1;
	var $isFWAcceptArtica=1;
	var $isFWLogBlocked=0;
	var $QOSMAX=100;
	var $FireQOS=0;
	var $InputSpeed=0;
	var $OutputSpeed=0;
	var $QOS=1;
	var $mysql_error;
	var $firewall_policy="accept";
	var $DenyCountries=0;
	var $firewall_behavior=0;
	var $firewall_masquerade=0;
	var $firewall_artica=0;
	var $ModemType=null;
	var $SpeedUnit="kbit";
	
	var $ShoreWallServices=array();
	function system_nic($eth=null){
		if(!isset($GLOBALS["AS_ROOT"])){
			$GLOBALS["AS_ROOT"]=false;
			if(function_exists("posix_getuid")){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
		}
		if($GLOBALS["AS_ROOT"]){if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}}
		$sock=new sockets();
		$this->EnableipV6=$sock->GET_INFO("EnableipV6");
		if(!is_numeric($this->EnableipV6)){$this->EnableipV6=0;}		
		
		if($eth<>null){
			writelogs("init nic infos from $eth",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			$this->eth=$eth;
			$this->loadEthConfig();
		}

		$this->CheckUnconfigured();
		
		
		
	}
	
	public function IsConfigured(){
		$q=new mysql();	
		writelogs("Loading nic infos from $this->eth",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		$sql="SELECT `Interface` FROM `nics` WHERE `Interface`='$this->eth'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if($ligne["Interface"]<>null){return true;}
		return false;
	}
	
	public function SaveIpv6(){
		$q=new mysql();
		$sql="SELECT `Interface` FROM `nics` WHERE `Interface`='$this->eth'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){
			echo $q->mysql_error;
			writelogs("$q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return;
		}
		
		if($ligne["Interface"]==null){
			echo "You must save network configuration first...\n";
			return;
		}
		
		if(!$q->FIELD_EXISTS("nics","ipv6","artica_backup")){
			$q->QUERY_SQL("ALTER TABLE `nics` ADD `ipv6` smallint( 1 ) NOT NULL DEFAULT '0'",'artica_backup');
			if(!$q->ok){echo $q->mysql_error;return;}
		}	

		$q->QUERY_SQL("UPDATE `nics` SET ipv6=$this->ipv6 WHERE `Interface`='$this->eth'");
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	
	public function SetMiittools($data){
		$q=new mysql();
		if(!$q->FIELD_EXISTS("nics","miitool","artica_backup")){
			$q->QUERY_SQL("ALTER TABLE `nics` ADD `miitool` VARCHAR( 90 ) NULL",'artica_backup');
		}
		
		
		$q->QUERY_SQL("UPDATE nics SET `miitool`='$data' WHERE `Interface`='$this->eth'" ,'artica_backup');
		
		if(!$q->ok){echo $q->mysql_error;}
	}
	
	public function CheckMySQLFields(){
		$q=new mysql();
		if(!$q->FIELD_EXISTS("nics","ipv6mask","artica_backup")){
			$q->QUERY_SQL("ALTER TABLE `nics` ADD `ipv6mask` INT( 2 ) NOT NULL DEFAULT '0'",'artica_backup');
		}
		if(!$q->FIELD_EXISTS("nics","ucarp-vid","artica_backup")){
			$q->QUERY_SQL("ALTER TABLE `nics` ADD `ucarp-vid` INT( 1 ) NOT NULL DEFAULT '3'",'artica_backup');
		}
		
		if(!$q->FIELD_EXISTS("nics","ucarp-vip","artica_backup")){
			$q->QUERY_SQL("ALTER TABLE `nics` ADD `ucarp-vip` VARCHAR( 60 )",'artica_backup');
		}
		
		if(!$q->FIELD_EXISTS("nics","ucarp-advskew","artica_backup")){
			$q->QUERY_SQL("ALTER TABLE `nics` ADD `ucarp-advskew` smallint( 1 )",'artica_backup');
		}
		
		if(!$q->FIELD_EXISTS("nics","ucarp-advbase","artica_backup")){
			$q->QUERY_SQL("ALTER TABLE `nics` ADD `ucarp-advbase` smallint( 3 )",'artica_backup');
		}
		
		if(!$q->FIELD_EXISTS("nics","ucarp-enable","artica_backup")){
			$q->QUERY_SQL("ALTER TABLE `nics` ADD `ucarp-enable` smallint( 1 ) NULL DEFAULT '0'",'artica_backup');
		}
		
		if(!$q->FIELD_EXISTS("nics","ucarp-master","artica_backup")){
			$q->QUERY_SQL("ALTER TABLE `nics` ADD `ucarp-master` smallint( 1 ) NULL DEFAULT '0'",'artica_backup');
		}
		if(!$q->FIELD_EXISTS("nics","isFW","artica_backup")){
			$q->QUERY_SQL("ALTER TABLE `nics` ADD `isFW` smallint(1 ) ,ADD INDEX ( `isFW` )",'artica_backup');
		}
		
		if(!$q->FIELD_EXISTS("nics","QOS","artica_backup")){
			$q->QUERY_SQL("ALTER TABLE `nics` ADD `QOS` smallint(1 ) NOT NULL DEFAULT 0,ADD INDEX ( `QOS` )",'artica_backup');
		}

		if(!$q->FIELD_EXISTS("nics","QOSMAX","artica_backup")){
			$q->QUERY_SQL("ALTER TABLE `nics` ADD `QOSMAX` smallint(10) NOT NULL DEFAULT 100,ADD INDEX ( `QOSMAX` )",'artica_backup');
		}		
				
		if(!$q->FIELD_EXISTS("nics","defaultroute","artica_backup")){
			$q->QUERY_SQL("ALTER TABLE `nics` ADD `defaultroute` smallint(1 ) ,ADD INDEX ( `defaultroute` )",'artica_backup');
		}		
		if(!$q->FIELD_EXISTS("nics","ShoreWallServices","artica_backup")){
			$q->QUERY_SQL("ALTER TABLE `nics` ADD `ShoreWallServices` TEXT",'artica_backup');
		}		
		if(!$q->FIELD_EXISTS("nics_switch","ShoreWallServices","artica_backup")){
			$q->QUERY_SQL("ALTER TABLE `nics_switch` ADD `ShoreWallServices` TEXT",'artica_backup');
		}
		if(!$q->FIELD_EXISTS("nics","netzone","artica_backup")){
			$sql="ALTER TABLE `nics` ADD `netzone` VARCHAR( 10 ) ,ADD INDEX ( `netzone` )";
			$q->QUERY_SQL($sql,'artica_backup');
		}
		if(!$q->FIELD_EXISTS("nics","SourceBasedRouting","artica_backup")){
			$sql="ALTER TABLE `nics` ADD `SourceBasedRouting` smallint(1) ,ADD INDEX ( `SourceBasedRouting` )";
			$q->QUERY_SQL($sql,'artica_backup');
		}		
		if(!$q->FIELD_EXISTS("nics","isFWAcceptArtica","artica_backup")){
			$sql="ALTER TABLE `nics` ADD `isFWAcceptArtica` smallint(1) ,ADD INDEX ( `isFWAcceptArtica` )";
			$q->QUERY_SQL($sql,'artica_backup');
		}		
		if(!$q->FIELD_EXISTS("nics","firewall_policy","artica_backup")){
			$sql="ALTER TABLE `nics` ADD `firewall_policy` varchar(10) ,ADD INDEX ( `firewall_policy` )";
			$q->QUERY_SQL($sql,'artica_backup');
		}		
		if(!$q->FIELD_EXISTS("nics","firewall_behavior","artica_backup")){
			$sql="ALTER TABLE `nics` ADD `firewall_behavior` smallint(1) NOT NULL DEFAULT 0,ADD INDEX ( `firewall_behavior` )";
			$q->QUERY_SQL($sql,'artica_backup');
		}	
		
		if(!$q->FIELD_EXISTS("nics","firewall_masquerade","artica_backup")){
			$sql="ALTER TABLE `nics` ADD `firewall_masquerade` smallint(1) NOT NULL DEFAULT 0,ADD INDEX ( `firewall_behavior` )";
			$q->QUERY_SQL($sql,'artica_backup');
		}		
		if(!$q->FIELD_EXISTS("nics","DenyCountries","artica_backup")){
			$sql="ALTER TABLE `nics` ADD `DenyCountries` smallint(1) NOT NULL DEFAULT 0,ADD INDEX ( `DenyCountries` )";
			$q->QUERY_SQL($sql,'artica_backup');
		}
		if(!$q->FIELD_EXISTS("nics","firewall_artica","artica_backup")){
			$sql="ALTER TABLE `nics` ADD `firewall_artica` smallint(1) NOT NULL DEFAULT 0,ADD INDEX ( `firewall_artica` )";
			$q->QUERY_SQL($sql,'artica_backup');
		}	
		if(!$q->FIELD_EXISTS("nics","UseSPAN","artica_backup")){
			$sql="ALTER TABLE `nics` ADD `UseSPAN` smallint(1) NOT NULL DEFAULT 0,ADD INDEX ( `UseSPAN` )";
			$q->QUERY_SQL($sql,'artica_backup');
		}
		
		if(!$q->FIELD_EXISTS("nics","FireQOS","artica_backup")){
			$sql="ALTER TABLE `nics` ADD `FireQOS` smallint(1) NOT NULL DEFAULT 0,ADD INDEX ( `FireQOS` )";
			$q->QUERY_SQL($sql,'artica_backup');
		}		
		
		if(!$q->FIELD_EXISTS("nics","InputSpeed","artica_backup")){
			$sql="ALTER TABLE `nics` ADD `InputSpeed` INT(10) NOT NULL DEFAULT 0,ADD INDEX ( `InputSpeed` )";
			$q->QUERY_SQL($sql,'artica_backup');
		}	

		if(!$q->FIELD_EXISTS("nics","OutputSpeed","artica_backup")){
			$sql="ALTER TABLE `nics` ADD `OutputSpeed` INT(10) NOT NULL DEFAULT 0,ADD INDEX ( `OutputSpeed` )";
			$q->QUERY_SQL($sql,'artica_backup');
		}		
		if(!$q->FIELD_EXISTS("nics","ModemType","artica_backup")){
			$sql="ALTER TABLE `nics` ADD `ModemType` VARCHAR(128) NULL,ADD INDEX ( `ModemType` )";
			$q->QUERY_SQL($sql,'artica_backup');
		}		
		if(!$q->FIELD_EXISTS("nics","SpeedUnit","artica_backup")){
			$sql="ALTER TABLE `nics` ADD `SpeedUnit` VARCHAR(20) NULL,ADD INDEX ( `SpeedUnit` )";
			$q->QUERY_SQL($sql,'artica_backup');
		}		
		
		
		
		if(!$q->FIELD_EXISTS("nics","MTU","artica_backup")){
			$sql="ALTER TABLE `nics` ADD `MTU` smallint( 3 ) ,ADD INDEX ( `MTU` )";
			$q->QUERY_SQL($sql,'artica_backup');
		}		
		
	}
	
	private function loadEthVirtual(){
		$LOGPR=__CLASS__."/".__FUNCTION__;
		if(preg_match("#^virt([0-9]+)#", $this->eth,$re)){$ID=$re[1];}
		$sql="SELECT * FROM `nics_switch` WHERE `ID`='$ID'";
		if($GLOBALS["VERBOSE"]){echo "$LOGPR: $sql<br>\n";}
		$q=new mysql();
		
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		
		if(!$q->ok){echo "$q->mysql_error\n";}
		
		if(!isset($ligne["DNS1"])){$ligne["DNS1"]=null;}
		if(!isset($ligne["DNS2"])){$ligne["DNS2"]=null;}
		if(!isset($ligne["NETWORK"])){$ligne["NETWORK"]=null;}
		if(!isset($ligne["ipv6mask"])){$ligne["ipv6mask"]=null;}
		if(!isset($ligne["ipv6addr"])){$ligne["ipv6addr"]=null;}
		if(!isset($ligne["ipv6gw"])){$ligne["ipv6gw"]=null;}
		if(!isset($ligne["UseSPAN"])){$ligne["UseSPAN"]=0;}
		
		
		if(!isset($ligne["netzone"])){
			$q->QUERY_SQL("ALTER TABLE `nics_switch` ADD `netzone` VARCHAR( 10 ) ,ADD INDEX ( `netzone` )","artica_backup");
			$ligne["netzone"]=$this->eth;
		}
		if($ligne["netzone"]==null){$ligne["netzone"]=$this->eth;}
		$this->firewall_policy=$ligne["firewall_policy"];
		$this->netzone=$ligne["netzone"];
		$this->ipv6=$ligne["ipv6"];
		$this->NETMASK=$ligne["netmask"];
		$this->GATEWAY=$ligne["gateway"];
		$this->IPADDR=$ligne["ipaddr"];
		$this->DNS1=trim($ligne["DNS1"]);
		$this->DNS2=trim($ligne["DNS2"]);
		$this->NETWORK=$ligne["cdir"];
		$this->ipv6mask=$ligne["ipv6mask"];
		$this->ipv6addr=$ligne["ipv6addr"];
		$this->ipv6gw=$ligne["ipv6gw"];
		$this->metric=$ligne["metric"];
		$this->NICNAME=$ligne["NICNAME"];
		$this->UseSPAN=intval($ligne["UseSPAN"]);
		$this->enabled=intval($ligne["enabled"]);
		$this->isFW=$ligne["isFW"];
		$this->isFWAcceptNet=$ligne["isFWAcceptNet"];
		$this->isFWAcceptArtica=$ligne["isFWAcceptArtica"];
		$this->isFWLogBlocked=$ligne["isFWLogBlocked"];
		$this->firewall_behavior=$ligne["firewall_behavior"];
		$this->firewall_masquerade=$ligne["firewall_masquerade"];
		$this->firewall_artica=$ligne["firewall_artica"];
		$this->DenyCountries=$ligne["DenyCountries"];
		$this->ShoreWallServices=unserialize(base64_decode($ligne["ShoreWallServices"]));
		if($this->NICNAME==null){$this->NICNAME="Interface $this->eth";}
		if($this->NETWORK==null){$this->NETWORK=$this->GetCDIRNetwork($this->IPADDR,$this->NETMASK);}
		if($GLOBALS["VERBOSE"]){echo "$LOGPR: NETWORK:$this->NETWORK<br>\n";}
	}
	
	
	private function loadEthConfig(){
		$q=new mysql();
		$LOGPR=__CLASS__."/".__FUNCTION__;
		if($GLOBALS["VERBOSE"]){echo "$LOGPR: Loading $this->eth<br>\n";}
		if(preg_match("#^virt#", $this->eth)){
			$this->loadEthVirtual();
			return;
		}
		
		
		$sql="SELECT * FROM `nics` WHERE `Interface`='$this->eth'";
		if($GLOBALS["VERBOSE"]){echo "$sql<br>\n";}
		writelogs("loadEthConfig: Loading nic infos from `$this->eth` `$sql`",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){
			writelogs("$q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		}
		$this->dhcp=0;
		writelogs("$this->eth: {$ligne["Interface"]}",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		if($ligne["Interface"]==null){
			$ip=new networking();
			$ligne=$ip->GetNicInfos($this->eth);
			if(!isset($ligne["ROUTES"])){$ligne["ROUTES"]=null;}
			$this->ROUTES=$ligne["ROUTES"];
			if(($ligne["BOOTPROTO"]=='dhcp') OR ($ligne["BOOTPROTO"]==null)){$this->dhcp=1;}
			$ligne["enabled"]=1;
		
		}else{
			$this->dhcp=$ligne["dhcp"];
			writelogs("Routes \"{$ligne["routes"]}\"",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			$this->ROUTES=unserialize($ligne["routes"]);
		}
		
		if(!isset($ligne["DNS1"])){$ligne["DNS1"]=null;}
		if(!isset($ligne["DNS2"])){$ligne["DNS2"]=null;}
		if(!isset($ligne["NETWORK"])){$ligne["NETWORK"]=null;}
		if(!isset($ligne["ipv6mask"])){$ligne["ipv6mask"]=null;}
		if(!isset($ligne["ipv6addr"])){$ligne["ipv6addr"]=null;}
		if(!isset($ligne["ipv6gw"])){$ligne["ipv6gw"]=null;}
		if($ligne["netzone"]==null){$ligne["netzone"]=$this->eth;}
		
		$this->netzone=$ligne["netzone"];
		$this->ipv6=$ligne["ipv6"];
		$this->NETMASK=$ligne["NETMASK"];
		$this->GATEWAY=$ligne["GATEWAY"];
		$this->BROADCAST=$ligne["BROADCAST"];
		$this->IPADDR=$ligne["IPADDR"];
		$this->DNS1=trim($ligne["DNS1"]);
		$this->DNS2=trim($ligne["DNS2"]);
		$this->NETWORK=$ligne["NETWORK"];
		$this->enabled=intval($ligne["enabled"]);
		$this->ipv6mask=$ligne["ipv6mask"];
		$this->ipv6addr=$ligne["ipv6addr"];
		$this->ipv6gw=$ligne["ipv6gw"];
		$this->metric=$ligne["metric"];
		$this->UseSPAN=intval($ligne["UseSPAN"]);
		$this->ucarp_enabled=$ligne["ucarp-enable"];
		$this->ucarp_master=$ligne["ucarp-master"];
		$this->QOS=intval($ligne["QOS"]);
		$this->QOSMAX=intval($ligne["QOSMAX"]);
		$this->DenyCountries=intval($ligne["DenyCountries"]);
		$this->firewall_masquerade=intval($ligne["firewall_masquerade"]);
		if(isset($ligne["ucarp-vid"])){$this->ucarp_vid=$ligne["ucarp-vid"];}
		if(isset($ligne["ucarp-vip"])){$this->ucarp_vip=$ligne["ucarp-vip"];}
		if(isset($ligne["ucarp-advskew"])){$this->ucarp_advskew=$ligne["ucarp-advskew"];}
		if(isset($ligne["ucarp-advbase"])){$this->ucarp_advbase=$ligne["ucarp-advbase"];}
		if(isset($ligne["Bridged"])){$this->Bridged=$ligne["Bridged"];}
		if(isset($ligne["firewall_policy"])){$this->firewall_policy=$ligne["firewall_policy"];}
		if(isset($ligne["firewall_behavior"])){$this->firewall_behavior=$ligne["firewall_behavior"];;}
		if(isset($ligne["firewall_artica"])){$this->firewall_artica=$ligne["firewall_artica"];;}
		
		if(isset($ligne["FireQOS"])){$this->FireQOS=$ligne["FireQOS"];;}
		if(isset($ligne["InputSpeed"])){$this->InputSpeed=$ligne["InputSpeed"];;}
		if(isset($ligne["OutputSpeed"])){$this->OutputSpeed=$ligne["OutputSpeed"];;}
		if(isset($ligne["ModemType"])){$this->ModemType=$ligne["ModemType"];;}
		if(isset($ligne["SpeedUnit"])){$this->SpeedUnit=$ligne["SpeedUnit"];;}
		
		
		
		
		
		
		$this->BridgedTo=$ligne["BridgedTo"];
		if(isset($ligne["defaultroute"])){$this->defaultroute=$ligne["defaultroute"];}
		$this->NICNAME=$ligne["NICNAME"];
		$this->isFW=$ligne["isFW"];
		if(isset($ligne["isFWAcceptNet"])){$this->isFWAcceptNet=$ligne["isFWAcceptNet"];}
		if(isset($ligne["isFWLogBlocked"])){$this->isFWLogBlocked=$ligne["isFWLogBlocked"];}
		if(!isset($ligne["SourceBasedRouting"])){$ligne["SourceBasedRouting"]=0;}
		
		$this->SourceBasedRouting=intval($ligne["SourceBasedRouting"]);
		writelogs("$this->eth mtu= {$ligne["MTU"]}",__CLASS__,__FUNCTION__,__FILE__,__LINE__);
		if(isset($ligne["MTU"])){ $this->mtu=intval($ligne["MTU"]); }
		if(!is_numeric($this->ucarp_enabled)){$this->ucarp_enabled=0;}
		if(!is_numeric($this->ucarp_vid)){$this->ucarp_vid=0;}
		if(!is_numeric($this->ucarp_advbase)){$this->ucarp_advbase=0;}
		if(!is_numeric($this->mtu)){$this->mtu=1500;}
		if($this->ucarp_vid==0){$this->ucarp_vid=3;}
		if($this->ucarp_advbase==0){$this->ucarp_advbase=2;}
		if(isset($ligne["ShoreWallServices"])){ $this->ShoreWallServices=unserialize(base64_decode($ligne["ShoreWallServices"])); }
		if(!is_numeric($this->isFWAcceptNet)){$this->isFWAcceptNet=1;}
		if($this->metric==0){$this->metric=$this->lastmetric();}
		if($this->mtu==0){$this->mtu=1500;}
		if($this->firewall_policy==null){$this->firewall_policy="accept";}
		
		if($this->ipv6addr==null){
			$ip=new IP();
			$this->ipv6addr=$ip->IPv4To6($this->IPADDR);
		}
		
		if($this->ipv6gw==null){
			$ip=new IP();
			$this->ipv6gw=$ip->IPv4To6($this->GATEWAY);
		}		
		
		if($this->DNS1==null){
			$resolv=new resolv_conf();
			$this->DNS1=$resolv->MainArray["DNS1"];
			$this->DNS2=$resolv->MainArray["DNS2"];
		}
		if($this->EnableipV6==0){
				$this->ipv6=0;

		}
		
		if($this->ipv6==0){
				$this->ipv6addr=null;
				$this->ipv6mask=0;
				$this->ipv6gw=null;			
		}
		
		if($this->BROADCAST=="0.0.0.255"){
			$t=explode(".", $this->IPADDR);
			$this->BROADCAST="{$t[0]}.{$t[1]}.{$t[2]}.255";
		}
		
		if($this->NICNAME==null){$this->NICNAME="Interface $this->eth";}
		if($GLOBALS["VERBOSE"]){echo "<hr>$LOGPR: $this->NICNAME NETWORK:$this->NETWORK<br>\n";}
	}
	
	private function CheckUnconfigured($called=null){
		
		if($called==null){
			if(function_exists("debug_backtrace")){
				$trace=@debug_backtrace();
				if(isset($trace[1])){
					$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
				}
			}
		}
		
		
		$q=new mysql();
		if(!$q->TABLE_EXISTS("nics", "artica_backup")){
			$q->BuildNetworkTables();
			if(!$q->TABLE_EXISTS("nics", "artica_backup")){$this->writelogs("Fatal table nics doesn't exists Aborting function/$called",__FUNCTION__,__LINE__);return;}
		}
		
		
		if(!$q->FIELD_EXISTS("nics","enabled","artica_backup")){$sql="ALTER TABLE `nics` ADD `enabled` INT( 1 ) NOT NULL DEFAULT '0'";$q->QUERY_SQL($sql,'artica_backup');}
		if(!$q->FIELD_EXISTS("nics","MTU","artica_backup")){$sql="ALTER TABLE `nics` ADD `MTU` smallint( 3 ) NOT NULL DEFAULT '1500'";$q->QUERY_SQL($sql,'artica_backup');}
		$sql="SELECT COUNT(Interface) as tcount FROM `nics` WHERE enabled=1";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){
			$this->writelogs("Fatal $q->mysql_error $called",__FUNCTION__,__LINE__);
			return false;
		}
		$ligne=mysql_fetch_array($results);
		
		if($ligne["tcount"]>0){$this->unconfigured=false;}
	}
	
	private function writelogs($text=null,$function=null,$line=0){
		$file_source="/usr/share/artica-postfix/ressources/logs/web/mysql.debug";
		@mkdir('/usr/share/artica-postfix/ressources/logs/web');
		if(!is_numeric($line)){$line=0;}
		if(function_exists("writelogs")){writelogs("$text (L.$line)","$function",__FILE__,$line);}
		if(!$GLOBALS["VERBOSE"]){return;}
		$logFile=$file_source;
		if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
		if (is_file($logFile)) {
			$size=filesize($logFile);
			if($size>1000000){unlink($logFile);}
		}
	
		$f = @fopen($logFile, 'a');
		$date=date("Y-m-d H:i:s");
		@fwrite($f, "$date:[$function()][{$_SERVER['REMOTE_ADDR']}]:: $text (L.$line)\n");
		@fclose($f);
			
	}
	
	private function lastmetric(){
		$q=new mysql();
		$hash=array();
		$f=array();
		
		if(!$q->FIELD_EXISTS("nics","metric","artica_backup")){$sql="ALTER TABLE `nics` ADD `metric` INT( 5 ) ,ADD INDEX ( `metric` )";$q->QUERY_SQL($sql,'artica_backup');}
		
		if(!$q->FIELD_EXISTS("nics","enabled","artica_backup")){
			$sql="ALTER TABLE `nics` ADD `enabled` INT( 1 ) NOT NULL DEFAULT '0'";
			$q->QUERY_SQL($sql,'artica_backup');
		}	
		if(!$q->FIELD_EXISTS("nics","isFWAcceptNet","artica_backup")){
			$sql="ALTER TABLE `nics` ADD `isFWAcceptNet` smallint( 1 ) NOT NULL DEFAULT '0'";
			$q->QUERY_SQL($sql,'artica_backup');
		}
		
		
		
		if(!$q->FIELD_EXISTS("nics_virtuals","enabled","artica_backup")){
			$sql="ALTER TABLE `nics_virtuals` ADD `enabled` INT( 1 ) NOT NULL DEFAULT '1'";
			$q->QUERY_SQL($sql,'artica_backup');
		}		
		if(!$q->FIELD_EXISTS("nics_vlan","enabled","artica_backup")){
			$sql="ALTER TABLE `nics_vlan` ADD `enabled` INT( 1 ) NOT NULL DEFAULT '1'";
			$q->QUERY_SQL($sql,'artica_backup');
		}		
		
		
		$sql="SELECT metric as tcount FROM `nics` WHERE enabled=1 ORDER BY metric DESC LIMIT 0,1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));		
		$hash[$ligne["metric"]]=$ligne["metric"];
		
		$sql="SELECT metric as tcount FROM `nics_vlan` WHERE enabled=1 ORDER BY metric DESC LIMIT 0,1";
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$hash[$ligne["metric"]]=$ligne["metric"];

		$sql="SELECT metric as tcount FROM `nics_virtuals` WHERE enabled=1 ORDER BY metric DESC LIMIT 0,1";
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$hash[$ligne["metric"]]=$ligne["metric"];		
		
		if(is_array($hash)){
			if(count($hash)>0){
				@krsort($hash[$ligne["metric"]]);
				while (list ($a, $b) = each ($hash) ){
					$f[]=$b;
				}
			}
		}
		if(!is_numeric($f[0])){$f[0]=1;}
		return $f[0]+1;
		
	}
	
	
	public function CleanIPAddr($addr){
		$addr=str_replace("___.___.___.___", "", $addr);
		if($addr==null){return null;}
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $addr)){
			$pr=explode(".",$addr);
			while (list ($index, $number) = each ($pr) ){$pr[$index]=intval($number);}
			$addr=@implode(".", $pr);
		}		
		return $addr;
		
	}
	
	
	public function set_hostname($hostname){
		$sock=new sockets();
		$q=new mysql();
		
		if($GLOBALS["AS_ROOT"]){
			$unix=new unix();
			$php=$unix->LOCATE_PHP5_BIN();
			$nohup=$unix->find_program("nohup");
		}
		
		
		if($q->COUNT_ROWS("net_hosts", "artica_backup")==0){
			if(!$GLOBALS["AS_ROOT"]){$sock->getFrameWork("network.php?etc-hosts-default=yes");}
			if($GLOBALS["AS_ROOT"]){$cmd=trim("$php /usr/share/artica-postfix/exec.virtuals-ip.php --hosts-defaults >/dev/null 2>&1");}
		}
		
		$q->QUERY_SQL("DELETE FROM net_hosts WHERE `ipaddr` = '127.0.0.1'",'artica_backup');
		$md5=md5("127.0.0.1{$hostname}");
		$TR=explode(".",$hostname);
		$netbiosname=$TR[0];
		$sql="INSERT IGNORE INTO net_hosts (`zmd5`,`ipaddr`,`hostname`,`alias`) VALUES  ('$md5','127.0.0.1','$hostname','$netbiosname')";
		$q->QUERY_SQL($sql,"artica_backup");
		
		
		if(!$GLOBALS["AS_ROOT"]){
			$sock->SET_INFO("myhostname",$hostname);
			$sock->getFrameWork("cmd.php?ChangeHostName=$hostname");
			$users=new usersMenus();
			if($users->POSTFIX_INSTALLED){$sock->getFrameWork("cmd.php?postfix-others-values=yes");}
		}
		if($GLOBALS["AS_ROOT"]){
			@file_put_contents("/etc/artica-postfix/settings/Daemons/myhostname", $hostname);
			shell_exec("$nohup $php /usr/share/artica-postfix/exec.virtuals-ip.php --hosts >/dev/null 2>&1 &");
			$unix->CHANGE_HOSTNAME($hostname);
			if(is_file($unix->find_program("postconf"))){
				shell_exec("$nohup $php /usr/share/artica-postfix/exec.postfix.maincf.php --others-values >/dev/null 2>&1 &");
			}
		}
		
	}
	
	private function SaveNicFW_switch(){
		if(preg_match("#^virt([0-9]+)#", $this->eth,$re)){$ID=$re[1];}
		
		$ShoreWallServices=base64_encode(serialize($this->ShoreWallServices));
		$q=new mysql();
		$this->ToSyslog("Edit nics_switch:ShoreWallServices $ID");
		$q->QUERY_SQL("UPDATE nics_switch SET ShoreWallServices='$ShoreWallServices' WHERE ID='$ID'","artica_backup");
		if(!$q->ok){echo $q->mysql_error;}
	}
	
	function ToSyslog($text,$error=false){
	
		if(!$error){$LOG_SEV=LOG_INFO;}else{$LOG_SEV=LOG_ERR;}
		if(function_exists("openlog")){openlog("monit", LOG_PID , LOG_SYSLOG);}
		if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
		if(function_exists("closelog")){closelog();}
	}	
	
	public function SaveNicFW(){
		if(!is_array($this->ShoreWallServices)){
			echo "ShoreWallServices is not an array...\n";
			return;
		}
		
		if($this->eth==null){
			echo "No interface sepcified...\n";
			return;			
		}
		
		$LOGPR=__CLASS__."/".__FUNCTION__;
		if(preg_match("#^virt([0-9]+)#", $this->eth,$re)){$this->SaveNicFW_switch();return;}
		$ShoreWallServices=base64_encode(serialize($this->ShoreWallServices));
		$q=new mysql();
		$q->QUERY_SQL("UPDATE `nics` SET ShoreWallServices='$ShoreWallServices' WHERE `Interface`='$this->eth'","artica_backup");
		$q=new mysql_shorewall();
		$q->NIC_UPDATE($this->eth);
	}
	
	
	
	public function SaveNic(){
		$q=new mysql();
		$routes=addslashes(serialize($this->ROUTES));
		
		if($this->defaultroute==1){
			$q->QUERY_SQL("UPDATE `nics` SET defaultroute=0","artica_backup");
		}
		

		
		$this->eth=trim($this->eth);
		$this->eth=str_replace("\r\n", "", $this->eth);
		$this->eth=str_replace("\r", "", $this->eth);
		$this->eth=str_replace("\n", "", $this->eth);		
		if($this->NICNAME==null){$this->NICNAME="Interface $this->eth";}
		if($this->mtu==0){$this->mtu=1500;}
		if(!is_numeric($this->isFWAcceptNet)){$this->isFWAcceptNet=1;}
		writelogs("save nic infos : NIC: `$this->eth`;CheckMySQLFields()",__CLASS__."/".__FUNCTION__,__FILE__);
		$this->CheckMySQLFields();
		$sql_add="
		INSERT INTO `nics` (`Interface`,`IPADDR`,`NETMASK`,`GATEWAY`,`BROADCAST`,`DNS1`,`DNS2`,
		`dhcp`,`ROUTES`,`NETWORK`,`enabled`,`ipv6`,`ipv6mask`,`ipv6gw`,`ipv6addr`,`metric`,`Bridged`,
		`BridgedTo`,`defaultroute`,`NICNAME`,`netzone`,`isFW`,`isFWAcceptNet`,`isFWLogBlocked`,`MTU`,`QOSMAX`,`QOS`,`firewall_policy`,`firewall_behavior`,`firewall_masquerade`)
		VALUES('$this->eth','$this->IPADDR','$this->NETMASK','$this->GATEWAY','$this->BROADCAST','$this->DNS_1',
		'$this->DNS_2','$this->dhcp','$routes','$this->NETWORK',1,'$this->ipv6','$this->ipv6mask',
		'$this->ipv6gw','$this->ipv6addr','$this->metric','$this->Bridged','$this->BridgedTo',
		'$this->defaultroute','$this->NICNAME','$this->netzone','$this->isFW','$this->isFWAcceptNet',
		'$this->isFWLogBlocked','$this->mtu','$this->QOSMAX','$this->QOS','$this->firewall_policy','$this->firewall_behavior','$this->firewall_masquerade');
		";
		if($this->ucarp_vid==0){$this->ucarp_vid=3;}
		if($this->ucarp_advbase==0){$this->ucarp_advbase=2;}
		if($this->metric==0){$this->metric=$this->lastmetric();exit;}
		if(!is_numeric($this->ucarp_master)){$this->ucarp_master=0;}
		if(!is_numeric($this->ucarp_enabled)){$this->ucarp_enabled=0;}
		$this->eth=trim($this->eth);
		if(preg_match("#(.+?)\s+#", $this->eth,$re)){$this->eth=$re[1];}
		
		$this->IPADDR=$this->CleanIPAddr($this->IPADDR);
		$this->NETMASK=$this->CleanIPAddr($this->NETMASK);
		$this->GATEWAY=$this->CleanIPAddr($this->GATEWAY);
		$this->BROADCAST=$this->CleanIPAddr($this->BROADCAST);
		$this->NETWORK=$this->CleanIPAddr($this->NETWORK);
		$this->DNS1=$this->CleanIPAddr($this->DNS1);
		$this->DNS2=$this->CleanIPAddr($this->DNS2);
		$this->ucarp_vip=$this->CleanIPAddr($this->ucarp_vip);
		
		if($this->enabled==1){
			if($this->UseSPAN==0){
				$ip=new IP();
				if(!$ip->isIPAddress($this->IPADDR)){
					echo __CLASS__."/".__FUNCTION__."/".__LINE__.": \"$this->IPADDR\" not a valid IP address\n";
					return;
				}
			}
		}
		
		if($this->netzone==null){$this->netzone="$this->eth";}
		
		
		$sql_upd="UPDATE `nics` SET 
		`NICNAME`='".mysql_escape_string2($this->NICNAME)."',
		`netzone`='$this->netzone',
		`IPADDR`='$this->IPADDR',
		`NETMASK`='$this->NETMASK',
		`GATEWAY`='$this->GATEWAY',
		`BROADCAST`='$this->BROADCAST',
		`NETWORK`='$this->NETWORK',
		`UseSPAN`='$this->UseSPAN',
		`DNS1`='$this->DNS1',
		`DNS2`='$this->DNS2',
		`dhcp`='$this->dhcp',
		`enabled`='$this->enabled',
		`ipv6`='$this->ipv6',
		`ipv6mask`='$this->ipv6mask',
		`ipv6gw`='$this->ipv6gw',
		`ipv6addr`='$this->ipv6addr',
		`metric`='$this->metric',
		`ROUTES`='$routes',
		`ucarp-enable`='$this->ucarp_enabled',
		`ucarp-master`='$this->ucarp_master',
		`ucarp-vid`='$this->ucarp_vid',
		`ucarp-vip`='$this->ucarp_vip',
		`ucarp-advskew`='$this->ucarp_advskew',
		`ucarp-advbase`='$this->ucarp_advbase',
		`Bridged`='$this->Bridged',
		`BridgedTo`='$this->BridgedTo',
		`defaultroute`='$this->defaultroute',
		`isFW`='$this->isFW',
		`isFWAcceptNet`='$this->isFWAcceptNet',
		`isFWAcceptArtica`='$this->isFWAcceptArtica',
		`isFWLogBlocked`='$this->isFWLogBlocked',
		`MTU`='$this->mtu',
		`SourceBasedRouting`='$this->SourceBasedRouting',
		`QOSMAX`='$this->QOSMAX',
		`QOS`='$this->QOS',
		`firewall_policy`='$this->firewall_policy',
		`firewall_behavior`='$this->firewall_behavior',
		`firewall_masquerade`='$this->firewall_masquerade',
		`DenyCountries`='$this->DenyCountries',
		`firewall_artica`='$this->firewall_artica',
		`FireQOS`='$this->FireQOS',
		`InputSpeed`='$this->InputSpeed',
		`OutputSpeed`='$this->OutputSpeed',
		`ModemType`='$this->ModemType',
		`SpeedUnit`='$this->SpeedUnit',
		`ROUTES`='$routes'
		 WHERE `Interface`='$this->eth'";

		
		
		writelogs("save nic infos : NIC: MTU=$this->mtu; ipv6=$this->ipv6; `$this->eth`;$this->IPADDR;$this->NETMASK;$this->GATEWAY;$this->dhcp;$this->BROADCAST",__CLASS__."/".__FUNCTION__,__FILE__);
		
		if(!isset($GLOBALS["NIC_TABLE_CHECKED"])){
			$q->check_networks_tables();
			
			if(!$q->FIELD_EXISTS("nics","ipv6","artica_backup")){
				$q->QUERY_SQL("ALTER TABLE `nics` ADD `ipv6` smallint( 1 ) NOT NULL DEFAULT '0'",'artica_backup');
				if(!$q->ok){echo $q->mysql_error;return;}
			}	
			if(!$q->FIELD_EXISTS("nics","isFWLogBlocked","artica_backup")){
				$q->QUERY_SQL("ALTER TABLE `nics` ADD `isFWLogBlocked` smallint( 1 ) NOT NULL DEFAULT '0'",'artica_backup');
				if(!$q->ok){echo $q->mysql_error;return;}
			}
			
			if(!$q->FIELD_EXISTS("nics","isFWAcceptNet","artica_backup")){
				$q->QUERY_SQL("ALTER TABLE `nics` ADD `isFWAcceptNet` smallint( 1 ) NOT NULL DEFAULT '0'",'artica_backup');
				if(!$q->ok){echo $q->mysql_error;return;}
			}			
			
			if(!$q->FIELD_EXISTS("nics","ipv6mask","artica_backup")){
				$q->QUERY_SQL("ALTER TABLE `nics` ADD `ipv6mask` INT( 2 ) NOT NULL DEFAULT '0'",'artica_backup');
				if(!$q->ok){echo $q->mysql_error;return;}
			}	
			if(!$q->FIELD_EXISTS("nics","ipv6addr","artica_backup")){
				$q->QUERY_SQL("ALTER TABLE `nics` ADD `ipv6addr` VARCHAR( 50 ) NOT NULL",'artica_backup');
				if(!$q->ok){echo $q->mysql_error;return;}
			}	
			if(!$q->FIELD_EXISTS("nics","ipv6gw","artica_backup")){
				$q->QUERY_SQL("ALTER TABLE `nics` ADD `ipv6gw` VARCHAR( 50 ) NOT NULL",'artica_backup');
				if(!$q->ok){echo $q->mysql_error;return;}
			}			
			if(!$q->FIELD_EXISTS("nics","NICNAME","artica_backup")){
				$q->QUERY_SQL("ALTER TABLE `nics` ADD `NICNAME` VARCHAR( 90 ) NOT NULL DEFAULT 'My Interface'",'artica_backup');
				if(!$q->ok){echo $q->mysql_error;return;}
			}	
			if(!$q->FIELD_EXISTS("nics","UseSPAN","artica_backup")){
				$q->QUERY_SQL("ALTER TABLE `nics` ADD `UseSPAN` smallint(1) NOT NULL DEFAULT '0'",'artica_backup');
				if(!$q->ok){echo $q->mysql_error;return;}
			}			
			
			
			
			$GLOBALS["NIC_TABLE_CHECKED"]=true;
		}
		
		$sql="SELECT Interface FROM `nics` WHERE `Interface`='$this->eth'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$sql=$sql_add;
		
		if($ligne["Interface"]<>null){
			writelogs("save nic infos : UPDATE `{$ligne["Interface"]}`",__FUNCTION__,__FILE__);
			$sql=$sql_upd;
		}
		
		writelogs("save nic infos : $sql = > `{$ligne["Interface"]}`",__FUNCTION__,__FILE__);
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){
			$this->mysql_error=$q->mysql_error;
			echo $q->mysql_error."\n\nsql command:\n\n$sql\n\n";return false;}
		
		if(!$this->NoReboot){
			$sock=new sockets();
			$sock->getFrameWork("network.php?reconstruct-interface=$this->eth");
		}
		
		return true;
		
		
	}
	
	private function root_build_redhat_remove_scripts($dir){
		if(!is_dir($dir)){echo "Starting......: ".date("H:i:s")." IP mode RedHat $dir no such directory\n";return;}
		$unix=new unix();
		$ifconfig=$unix->find_program("ifconfig");
		
		foreach (glob("$dir/ifcfg-*") as $filename) {
			$fileconf=basename($filename);
			if($fileconf=="ifcfg-lo"){continue;}
			if(preg_match("#ifcfg-(.*)#",$fileconf,$re)){$nic=$re[1];}
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." IP mode RedHat;$fileconf\n";}
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." $ifconfig $nic down\n";}
			system("$ifconfig $nic down");
			echo "Starting......: ".date("H:i:s")." Network remove {$re[1]}\n";
			@unlink($filename);
			if(is_file("/etc/sysconfig/networking/devices/$fileconf")){
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." Delete /etc/sysconfig/networking/devices/$fileconf\n";}
				@unlink("/etc/sysconfig/networking/devices/$fileconf");
			}
			if(is_file("/etc/sysconfig/networking/profiles/default/$fileconf")){
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." Delete /etc/sysconfig/networking/profiles/default/$fileconf\n";}
				@unlink("/etc/sysconfig/networking/profiles/default/$fileconf");
			}
			
			if(is_file("/etc/sysconfig/network/scripts/ifup-$fileconf")){
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." Delete /etc/sysconfig/network/scripts/ifup-$fileconf\n";}
				@unlink("/etc/sysconfig/network/scripts/ifup-$fileconf");	
			}
				
		}		
		
	}
	
	private function root_build_redhat_ifcfg($eth,$datas){
		if(is_dir("/etc/sysconfig/network")){
			echo "Starting......: ".date("H:i:s")." IP mode RedHat $eth writing /etc/sysconfig/network/ifcfg-$eth\n";
			@file_put_contents("/etc/sysconfig/network/ifcfg-$eth",$datas);
		}
		
		if(is_dir("/etc/sysconfig/network-scripts")){
			echo "Starting......: ".date("H:i:s")." IP mode RedHat $eth writing /etc/sysconfig/network-scripts/ifcfg-$eth\n";
			@file_put_contents("/etc/sysconfig/network-scripts/ifcfg-$eth",$datas);
		}
		
		if(is_dir("/etc/sysconfig/networking/profiles/default")){
			echo "Starting......: ".date("H:i:s")." IP mode RedHat $eth writing profiles/default/ifcfg-$eth\n";
			@file_put_contents("/etc/sysconfig/networking/profiles/default/ifcfg-$eth",$datas);
		}		
		if(is_dir("/etc/sysconfig/networking/devices")){
			echo "Starting......: ".date("H:i:s")." IP mode RedHat $eth writing devices/ifcfg-$eth\n";
			@file_put_contents("/etc/sysconfig/networking/devices/ifcfg-$eth",$datas);
		}			
		
	}
	
	
	public function root_build_redhat_config(){
		$this->LoadTools();
		$sock=new sockets();
		$NetWorkBroadCastAsIpAddr=$sock->GET_INFO("NetWorkBroadCastAsIpAddr");
		$q=new mysql();
		$unix=new unix();
		$ifconfig=$unix->find_program("ifconfig");
		
		$sql="SELECT * FROM `nics`";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$eth=$ligne["Interface"];
			$GLOBALS["SCRIPTS"][]="{$GLOBALS["ifconfig"]} $eth down";
		}
		
		
		$sql="SELECT * FROM `nics` WHERE enabled=1";
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." Mysql error : $q->mysql_error\n";return;}
		$count=mysql_num_rows($results);
		if($count==0){echo "Starting......: ".date("H:i:s")." Network unconfigured\n";return;}
		$ROUTESCFG=array();
		$this->root_build_redhat_remove_scripts("/etc/sysconfig/network-scripts");
		$this->root_build_redhat_remove_scripts("/etc/sysconfig/network");
		

		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
			$eth=$ligne["Interface"];
			$MTU=intval($ligne["MTU"]);
			$ligne["IPADDR"]=trim($ligne["IPADDR"]);
			if($MTU==0){$MTU=1500;}
			if(trim($eth)==null){echo "Starting......: ".date("H:i:s")." no nic name !! \"{$ligne["IPADDR"]}\"\n";continue;}
			$GLOBALS["SAVED_INTERFACES"][]=$eth;
			
			echo "Starting......: ".date("H:i:s")." $eth \"{$ligne["IPADDR"]}\"\n";
			$dns=array();
			$array=array();
			if(trim($ligne["GATEWAY"])==null){$ligne["GATEWAY"]="0.0.0.0";}
			
						
			
					
			if($this->IsBridged($eth)){
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." $eth bridged to {$ligne["BridgedTo"]}\n";}	
				$array[]="DEVICE=$eth";
				$array[]="STARTMODE='auto'";
				$array[]="ONBOOT=yes";
				$array[]="BRIDGE={$ligne["BridgedTo"]}";
				$array[]="NM_CONTROLLED=no";
				$array[]="IPV6INIT=no";
				$GLOBALS["SCRIPTS"][]="{$GLOBALS["ifconfig"]} $eth down";
				$GLOBALS["SCRIPTS"][]="{$GLOBALS["ifconfig"]} $eth 0.0.0.0 promisc up";
				$GLOBALS["SAVED_INTERFACES"][]=$eth;
				$GLOBALS["MEM_INTERFACES"][$eth]="0.0.0.0";
				echo "Starting......: ".date("H:i:s")." $eth \" ** is bridged to {$ligne["BridgedTo"]} **\n";
				$f[]="iface $eth inet manual";
				$GLOBALS["SCRIPTS"][]="# [".__LINE__."] **** \"$eth\" IS BRIDGED ****";
				$GLOBALS["SCRIPTS"][]="{$GLOBALS["ifconfig"]} $eth down";
				$GLOBALS["SCRIPTS"][]="{$GLOBALS["ifconfig"]} $eth 0.0.0.0 promisc up";
				$GLOBALS["SCRIPTS_DOWN"][]="{$GLOBALS["ifconfig"]} $eth down";
				$GLOBALS["SAVED_INTERFACES"][]=$eth;
				$GLOBALS["MEM_INTERFACES"][$eth]="0.0.0.0";				
				$this->root_build_redhat_ifcfg($eth,@implode("\n",$array));
				continue;		
			}	
			
			$array[]="DEVICE=$eth";
			$array[]="STARTMODE='auto'";
			$array[]="IPADDR={$ligne["IPADDR"]}";
			$array[]="NETMASK={$ligne["NETMASK"]}";
			if($NetWorkBroadCastAsIpAddr==1){$ligne["BROADCAST"]=$ligne["IPADDR"];}
			if($ligne["BROADCAST"]<>"0.0.0.0"){$array[]="BROADCAST={$ligne["BROADCAST"]}";}
			if($ligne["GATEWAY"]<>"0.0.0.0"){$array[]="GATEWAY={$ligne["GATEWAY"]}";}	
			
			
			$GLOBALS["SCRIPTS"][]="# [".__LINE__."]";
			$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
			$GLOBALS["SCRIPTS"][]="# [".__LINE__."] **** SETTINGS for \"$eth\" SPAN= {$ligne["UseSPAN"]} ****";
			$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
			$GLOBALS["SCRIPTS"][]="# [".__LINE__."]";
			
			if(intval($ligne["UseSPAN"])==1){
				$GLOBALS["SCRIPTS"][]="# [".__LINE__."] UseSPAN == 1 , do nothing...";
				$GLOBALS["SCRIPTS"][]="{$GLOBALS["ifconfig"]} $eth down";
				$GLOBALS["SCRIPTS"][]="{$GLOBALS["ifconfig"]} $eth up";
				continue;
			}
			
			
			if($ligne["IPADDR"]=="0.0.0.0"){
				$GLOBALS["SCRIPTS"][]="# [".__LINE__."] {$ligne["IPADDR"]} bad format!";
				continue;
			}
			
			if($ligne["IPADDR"]==null){
				$GLOBALS["SCRIPTS"][]="# [".__LINE__."] No ip address defined!";
				continue;
			}
			
			$GLOBALS["SCRIPTS"][]="{$GLOBALS["ifconfig"]} $eth down";
			$GLOBALS["SCRIPTS"][]="# {$ligne["IPADDR"]}/{$ligne["NETMASK"]} Gateway: {$ligne["GATEWAY"]} mtu: $MTU BROADCAST: {$ligne["BROADCAST"]}";
			
			
			$GLOBALS["SCRIPTS"][]="{$GLOBALS["ifconfig"]} $eth {$ligne["IPADDR"]} netmask {$ligne["NETMASK"]} down";
			$GLOBALS["SCRIPTS"][]="{$GLOBALS["sleepbin"]} 1";
			$GLOBALS["SCRIPTS"][]="{$GLOBALS["ifconfig"]} $eth {$ligne["IPADDR"]} netmask {$ligne["NETMASK"]} mtu $MTU up";			
			$GLOBALS["SCRIPTS"][]="{$GLOBALS["ifconfig"]} $eth {$ligne["IPADDR"]} netmask {$ligne["NETMASK"]} down";
			$GLOBALS["SCRIPTS"][]="{$GLOBALS["sleepbin"]} 1";
			$GLOBALS["SCRIPTS"][]="{$GLOBALS["ifconfig"]} $eth {$ligne["IPADDR"]} netmask {$ligne["NETMASK"]} mtu $MTU up";			

			
			
			if($ligne["IsBridge"]==1){
				$unix=new unix();
				$php5=$unix->LOCATE_PHP5_BIN();
				$chmod=$unix->find_program("chmod");
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." $eth is a bridge\n";}	
				$array[]="BOOTPROTO=static";
				$array[]="NM_CONTROLLED=no";
				$array[]="DELAY=5";
				$array[]="TYPE=Bridge";
				$array[]="MTU=1500";
				$array[]="BRIDGE_PORTS='{$ligne["BRIDGE_PORTS"]}'";
				$array[]="BRIDGE='yes'";
				$array[]="BRIDGE_AGEINGTIME='300'";
				$array[]="BRIDGE_FORWARDDELAY='0'";
				$array[]="BRIDGE_HELLOTIME='2'";
				$array[]="BRIDGE_MAXAGE='20'";
				$array[]="BRIDGE_PATHCOSTS='19'";
				$array[]="BRIDGE_PORTPRIORITIES=";
				$array[]="BRIDGE_PRIORITY=";
				$array[]="BRIDGE_STP='on'";
				$ROUTESCFG["-"][]=array("GW"=>$ligne["GATEWAY"],"MASK"=>"-","IP"=>"default");
				
				//$array[]="PRE_UP_SCRIPT=\"ifup-$eth\"";
				$PRE_UP_SCRIPT[]="#! /bin/bash";
				$PRE_UP_SCRIPT[]="$php5 /usr/share/artica-postfix/exec.vservers.php --checkbridge $eth {$ligne["BRIDGE_PORTS"]}\n";
				if(is_dir("/etc/sysconfig/network/scripts")){
					@file_put_contents("/etc/sysconfig/network/scripts/ifup-$eth",@implode("\n",$PRE_UP_SCRIPT));
					shell_exec("$chmod 777 /etc/sysconfig/network/scripts/ifup-$eth >/dev/null 2>&1");
				}
				if(is_dir("/etc/sysconfig/network-scripts")){
					@file_put_contents("/etc/sysconfig/network-scripts/ifup-$eth",@implode("\n",$PRE_UP_SCRIPT));
					shell_exec("$chmod 777 /etc/sysconfig/network-scripts/ifup-$eth >/dev/null 2>&1");
				}
			}
			
			
			$iptble=explode(".",$ligne["IPADDR"]);
			$firstip="{$iptble[0]}.{$iptble[1]}.{$iptble[2]}.0";
			$ROUTESCFG[$eth][]=array("GW"=>$ligne["GATEWAY"],"MASK"=>$ligne["NETMASK"],"IP"=>$firstip);	
			$routes=unserialize($ligne["ROUTES"]);
			if(count($routes)>0){
				while (list ($ip, $ip_array) = each ($routes) ){
					$ROUTESCFG[$eth][]=array("GW"=>$ip_array["GATEWAY"],"MASK"=>$ip_array["NETMASK"],"IP"=>$ip);
				}	
			}			

			$array[]="ONBOOT=yes";
			
			if($ligne["DNS1"]<>null){$array[]="DNS1={$ligne["DNS1"]}";}
			if($ligne["DNS2"]<>null){$array[]="DNS2={$ligne["DNS2"]}";}			
			
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." {$ligne["IPADDR"]}/{$ligne["NETMASK"]}:=> ifcfg-$eth\n";}	
			$this->root_build_redhat_ifcfg($eth,@implode("\n",$array));		
		}
		
		
		// Fin des cartes standard....
		if(count($ROUTESCFG)>0){
			while (list ($ETHROUTE, $ROUTESGLOBALARRAY) = each ($ROUTESCFG) ){
				if(trim($ETHROUTE)==null){continue;}
				while (list ($INDEX, $ROUTESARRAY) = each ($ROUTESGLOBALARRAY) ){
					if($ROUTESARRAY["IP"]==null){$ROUTESARRAY["IP"]="default";}
					if($ROUTESARRAY["GW"]==null){$ROUTESARRAY["GW"]="-";}
					if($ROUTESARRAY["MASK"]==null){$ROUTESARRAY["MASK"]="-";}
					echo "Starting......: ".date("H:i:s")." $ETHROUTE route {$ROUTESARRAY["IP"]}/{$ROUTESARRAY["MASK"]} via {$ROUTESARRAY["GW"]}\n";
					$routesDatas[]="{$ROUTESARRAY["IP"]}  {$ROUTESARRAY["GW"]} {$ROUTESARRAY["MASK"]}  $ETHROUTE";
				}
			}
			
			if(is_dir("/etc/sysconfig/network")){
				@file_put_contents("/etc/sysconfig/network/routes",@implode("\n",$routesDatas));
			}
		}
		
		
		
		$this->BuildBridges();
		$this->BuildVirtIps();
		$this->BuildVlans();
		$GLOBALS["NoGatewayForVirtualNetWork"]=$sock->GET_INFO("NoGatewayForVirtualNetWork");	
		if(!is_numeric($GLOBALS["NoGatewayForVirtualNetWork"])){$GLOBALS["NoGatewayForVirtualNetWork"]=0;}
		$sql="SELECT * FROM nics_virtuals ORDER BY ID DESC";
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");	
				
// ------------------------------- DEBUT DES CARTES VIRTUELLES ----------------------------------------		
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
			
			$array=array();
			$arr=explode(".",$ligne["ipaddr"]);
			$arr[3]="255";
			$brd=implode(".",$arr);			
			$eth="{$ligne["nic"]}:{$ligne["ID"]}";
			echo "Starting......: ".date("H:i:s")." $eth \"{$ligne["ipaddr"]}\"\n";
			if(trim($ligne["GATEWAY"])==null){$ligne["GATEWAY"]="0.0.0.0";}
			$array[]="DEVICE=$eth";
			$array[]="STARTMODE='auto'";
			$array[]="IPADDR={$ligne["ipaddr"]}";
			$array[]="NETMASK={$ligne["netmask"]}";
			if($NetWorkBroadCastAsIpAddr==1){$brd=$ligne["ipaddr"];}
			$array[]="BROADCAST=$brd";
			if($ligne["GATEWAY"]<>"0.0.0.0"){
				if($GLOBALS["NoGatewayForVirtualNetWork"]==0){$array[]="GATEWAY={$ligne["gateway"]}";}
			}
			$array[]="ONBOOT=yes";
			$array[]="USERCTL=yes";	
			
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." {$ligne["ipaddr"]}/{$ligne["netmask"]}:=> ifcfg-$eth\n";}	
			$this->root_build_redhat_ifcfg($eth,@implode("\n",$array));			
		}
// ------------------------------- FIN DES CARTES VIRTUELLES ----------------------------------------


// ------------------------------- DEBUT DES CARTES VLAN ----------------------------------------		
		$sql="SELECT * FROM nics_vlan ORDER BY ID DESC";
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");	

		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
			$array=array();
			$arr=explode(".",$ligne["ipaddr"]);
			$arr[3]="255";
			$brd=implode(".",$arr);			
			$eth="vlan{$ligne["ID"]}";
			if(trim($ligne["GATEWAY"])==null){$ligne["GATEWAY"]="0.0.0.0";}
			echo "Starting......: ".date("H:i:s")." VLAN $eth {$ligne["ipaddr"]}/{$ligne["netmask"]} gateway {$ligne["gateway"]} ({$ligne["org"]})\n";

			echo "Starting......: ".date("H:i:s")." $eth \"{$ligne["ipaddr"]}\"\n";
			$array[]="DEVICE=$eth";
			$array[]="STARTMODE='auto'";
			$array[]="IPADDR={$ligne["ipaddr"]}";
			$array[]="NETMASK={$ligne["netmask"]}";
			if($NetWorkBroadCastAsIpAddr==1){$brd=$ligne["ipaddr"];}
			$array[]="BROADCAST=$brd";
			if($ligne["gateway"]<>"0.0.0.0"){
				$array[]="GATEWAY={$ligne["gateway"]}";
			}
			
			$array[]="ONBOOT=yes";
			$array[]="USERCTL=yes";	
			$array[]="VLAN_NAME_TYPE=VLAN_PLUS_VID_NO_PAD";
			$array[]="PHYSDEV={$ligne["nic"]}";
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." {$ligne["ipaddr"]}/{$ligne["netmask"]}:=> ifcfg-$eth\n";}	
			$this->root_build_redhat_ifcfg($eth,@implode("\n",$array));			
			
		}

// ------------------------------- FIN DES CARTES VLAN ----------------------------------------
	
	
}
	
	private function root_build_debian_failover($nic){
		$q=new mysql();
		if(!$q->FIELD_EXISTS("nics_virtuals","failover","artica_backup")){$sql="ALTER TABLE `nics_virtuals` ADD `failover` TINYINT( 1 ) NOT NULL,ADD INDEX ( `failover` )";$q->QUERY_SQL($sql,'artica_backup');if(!$q->ok){echo $q->mysql_error."\n\n";}}
		$sql="SELECT * FROM nics_virtuals WHERE failover=1 AND nic='$nic' ORDER BY ID DESC";
		$results=$q->QUERY_SQL($sql,"artica_backup");	
		$count=mysql_num_rows($results);
		if($count==0){return null;}
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$eth="$nic:{$ligne["ID"]}";
				$f[]="post-up /sbin/ifconfig $eth {$ligne["ipaddr"]} netmask 255.255.255.255 broadcast {$ligne["ipaddr"]}";
				$f[]="post-down /sbin/ifconfig $eth down";
							
			
		}			
		$f[]="";
		return @implode("\n", $f);
	}
	
	public function root_build_debian_config_ipv6($eth){
		$q=new mysql();
		$unix=new unix();
		$ipbin=$unix->find_program("ip");
		$sock=new sockets();
		$php5=$unix->LOCATE_PHP5_BIN();
		$OVHNetConfig=$sock->GET_INFO("OVHNetConfig");
		if(!is_numeric($OVHNetConfig)){$OVHNetConfig=0;}
		$sysctl=$unix->find_program("sysctl");
		$ip=new IP();		
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." Main interface `$eth`";}
		$echo=$unix->find_program("echo");
		$nic=new system_nic($eth);
		$route=$unix->find_program("route");
		$servicebin=$unix->find_program("update-rc.d");
		
		if($nic->dhcp==1){return "iface $eth inet6 dhcp";}
		if($OVHNetConfig==1){$ligne["GATEWAY"]=null;}
		echo "Starting......: ".date("H:i:s")." Main interface `$eth` enable ipv6 ($sysctl net.ipv6.conf.$eth.disable_ipv6)\n";
		shell_exec("$sysctl -w net.ipv6.conf.$eth.disable_ipv6=0 >/dev/null 2>&1");
		
		$ipv6mask=$nic->ipv6mask;
		$ipv6addr=$nic->ipv6addr;
		$ipv6Gwaddr=$nic->ipv6gw;
		if($ipv6mask==0){
			echo "Starting......: ".date("H:i:s")." Main interface `$eth` [$ipv6addr] ipv6mask is not a numeric value...\n"; 
			return "#[$eth] $ipv6addr: ipv6mask is not a numeric...\n";}
		$f[]="iface $eth inet6 static";
		$f[]="\taddress $ipv6addr";
		$f[]="\tgateway $ipv6Gwaddr";
		$f[]="\tnetmask $ipv6mask";
		$f[]="\tpre-up $sysctl -w net.ipv6.conf.$eth.disable_ipv6=0 >/dev/null 2>&1";
		$f[]="\tpost-up /etc/init.d/$eth-up start >/dev/null 2>&1";	
		if($ipv6Gwaddr<>null){
			$f[]="pre-down $route -A inet6 del default gw $ipv6Gwaddr dev $eth";
		}			
		
		$sh[]="#! /bin/sh";
		$sh[]="#";
		$sh[]="### BEGIN INIT INFO";
		$sh[]="# Provides:          $eth-up";
		$sh[]="# Required-Start:    \$network";
		$sh[]="# Required-Stop:     $syslog $network $remote_fs";
		$sh[]="# Should-Start:      ";
		$sh[]="# Should-Stop:       ";
		$sh[]="# Default-Start:     2 3 4 5";
		$sh[]="# Default-Stop:      0 1 6";
		$sh[]="# Short-Description: $eth-up";
		$sh[]="# Description:       $eth-up";
		$sh[]="#";                    
		$sh[]="#";                    
		$sh[]="### END INIT INFO";
		$sh[]="PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin";
		$sh[]="case \"\$1\" in";
		$sh[]="  start)";		
		$sh[]="$echo 0 > /proc/sys/net/ipv6/conf/$eth/disable_ipv6";		
		$sh[]="$echo 0 > /proc/sys/net/ipv6/conf/$eth/autoconf";
		$sh[]="$echo 0 > /proc/sys/net/ipv6/conf/$eth/accept_ra";
		$sh[]="$echo 0 > /proc/sys/net/ipv6/conf/$eth/accept_ra_defrtr";
		$sh[]="$echo 0 > /proc/sys/net/ipv6/conf/$eth/accept_ra_pinfo";
		$sh[]="$echo 0 > /proc/sys/net/ipv6/conf/$eth/accept_ra_rtr_pref";	

		if($ipv6Gwaddr<>null){
			$sh[]="$route -A inet6 add default gw $ipv6Gwaddr dev $eth";
		}		
		
		$sql="SELECT * FROM nics_virtuals WHERE ipv6=1 AND nic='$eth' ORDER BY ID DESC";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
			$ipv6addr=$ligne["ipaddr"];
			$netmask=$ligne["netmask"];
			if(!is_numeric($netmask)){$netmask=0;}
			if($netmask==0){continue;}
			if(!$ip->isIPv6($ipv6addr)){continue;}
  		    $sh[]="$ipbin addr add dev $eth $ipv6addr/$netmask";
  		    $f[]="\tpre-down $ipbin addr del dev $eth $ipv6addr/$netmask";
			shell_exec("$ipbin addr add dev $eth $ipv6addr/$netmask >/dev/null 2>&1");	
		}
		
		$sh[]=";;";		
		$sh[]="esac";	
		$sh[]="exit 0";	
		$f[]="";
		$sh[]="";
		echo "Starting......: ".date("H:i:s")." Building Ipv6 virtuals IP for `$eth` creating `/etc/init.d/$eth-up`\n";
		@file_put_contents("/etc/init.d/$eth-up", @implode("\n", $sh));
		$unix->chmod_func(0755, "/etc/init.d/$eth-up");
		shell_exec("$servicebin -f $eth-up defaults >/dev/null 2>&1");
		return @implode("\n", $f);
	}
	
	
	public function NicToOther($nic){
		if($this->IsBridged($nic)){
			$sql="SELECT Bridged,BridgedTo FROM `nics` WHERE Interface='$nic'";
			$q=new mysql();
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
			return $ligne["BridgedTo"];
		}
		return $nic;
	}
	
	
	public function IsBridged($eth){
		$key=__CLASS__.__FUNCTION__.$eth;
		if(isset($GLOBALS[$key])){return $GLOBALS[$key];}
		if($GLOBALS["AS_ROOT"]){
			$this->LoadTools();
			
			if(!is_file($GLOBALS["brctlbin"])){
				if($GLOBALS["AS_ROOT"]){
					echo "Starting......: ".date("H:i:s")." $eth brctl no such binary\n";
				}
				$GLOBALS[$key]=false;
				return false;
			}
		}
		$eth=trim($eth);
		if(preg_match("#^br[0-9]+#", $eth)){$GLOBALS[$key]=false;return false;}
		$sql="SELECT Bridged,BridgedTo FROM `nics` WHERE Interface='$eth'";
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!is_numeric($ligne["Bridged"])){
			if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." $eth Not enabled [NULL] ({$ligne["Bridged"]} to bridge\n";}
			$GLOBALS[$key]=false;return false;}
		if($ligne["Bridged"]==0){
			if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." $eth Not enabled [0] to bridge\n";}
			$GLOBALS[$key]=false;return FALSE;}
		if($ligne["BridgedTo"]==null){
			if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." $eth Not bridged to any interface\n";}
			$GLOBALS[$key]=false;return false;}
		if(!preg_match("#^br([0-9]+)#", $ligne["BridgedTo"],$re)){
			if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." $eth -> {$ligne["BridgedTo"]} incorrect\n";}
			$GLOBALS[$key]=false;return false;}
		$BridgeID=$re[1];
		
		if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." $eth Bridged To {$ligne["BridgedTo"]}\n";}
		$sql="SELECT ipaddr FROM `nics_bridge` WHERE ID='$BridgeID'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if($ligne["ipaddr"]==null){$GLOBALS[$key]=false;return false;}
		$ip=new IP();
		if(!$ip->isValid($ligne["ipaddr"])){$GLOBALS[$key]=false;return false;}
		$GLOBALS[$key]=true;
		return true;
	}
	
	
	public function LoadTools(){
		if(!isset($GLOBALS["PROC_NET_DEV"])){$this->LoadProcNetDev();}
		if(!class_exists("unix")){return;}
			
		if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
		if(!isset($GLOBALS["sysctl"])){$GLOBALS["sysctl"]=$GLOBALS["CLASS_UNIX"]->find_program("sysctl");}
		if(!isset($GLOBALS["ifconfig"])){$GLOBALS["ifconfig"]=$GLOBALS["CLASS_UNIX"]->find_program("ifconfig");}
		if(!isset($GLOBALS["routebin"])){$GLOBALS["routebin"]=$GLOBALS["CLASS_UNIX"]->find_program("route");}
		if(!isset($GLOBALS["ipbin"])){$GLOBALS["ipbin"]=$GLOBALS["CLASS_UNIX"]->find_program("ip");}
		if(!isset($GLOBALS["vconfigbin"])){$GLOBALS["vconfigbin"]=$GLOBALS["CLASS_UNIX"]->find_program("vconfig");}
		if(!isset($GLOBALS["moprobebin"])){$GLOBALS["moprobebin"]=$GLOBALS["CLASS_UNIX"]->find_program("modprobe");}
		if(!isset($GLOBALS["brctlbin"])){$GLOBALS["brctlbin"]=$GLOBALS["CLASS_UNIX"]->find_program("brctl");}
		if(!isset($GLOBALS["dhclientbin"])){$GLOBALS["dhclientbin"]=$GLOBALS["CLASS_UNIX"]->find_program("dhclient");}
		if(!isset($GLOBALS["tunctlbin"])){$GLOBALS["tunctlbin"]=$GLOBALS["CLASS_UNIX"]->find_program("tunctl");}
		if(!isset($GLOBALS["sleepbin"])){$GLOBALS["sleepbin"]=$GLOBALS["CLASS_UNIX"]->find_program("sleep");}
		if(!isset($GLOBALS["echobin"])){$GLOBALS["echobin"]=$GLOBALS["CLASS_UNIX"]->find_program("echo");}
		if(!isset($GLOBALS["rmbin"])){$GLOBALS["rmbin"]=$GLOBALS["CLASS_UNIX"]->find_program("rm");}
		if(!isset($GLOBALS["touchbin"])){$GLOBALS["touchbin"]=$GLOBALS["CLASS_UNIX"]->find_program("touch");}
		if(!isset($GLOBALS["dhclientbin"])){$GLOBALS["dhclientbin"]=$GLOBALS["CLASS_UNIX"]->find_program("dhclient");}
		if(!isset($GLOBALS["PHP5BIN"])){$GLOBALS["PHP5BIN"]=$GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN();}
		if(!isset($GLOBALS["MIITOOLBIN"])){$GLOBALS["MIITOOLBIN"]=$GLOBALS["CLASS_UNIX"]->find_program("mii-tool");}
		
		
		
	}
	
	
	public function networks_disabled(){
		
		$sql="SELECT * FROM `nics` WHERE enabled=0 ORDER BY Interface";
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");
		
		$count=mysql_num_rows($results);
		if($count>0){
			$GLOBALS["SCRIPTS_TOP"][]="# [".__LINE__."]";
			$GLOBALS["SCRIPTS_TOP"][]="# [".__LINE__."] *******************************";
			$GLOBALS["SCRIPTS_TOP"][]="# [".__LINE__."] **** SETTINGS for $count NICs Disabled  ***";
			$GLOBALS["SCRIPTS_TOP"][]="# [".__LINE__."] *******************************";
			$GLOBALS["SCRIPTS_TOP"][]="# [".__LINE__."]";
			
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$eth=trim($ligne["Interface"]);
				$eth=str_replace("\n", "", $eth);
				$eth=str_replace("\r", "", $eth);
				$GLOBALS["SCRIPTS_TOP"][]="# [".__LINE__."] **** \"$eth\" {$ligne["IPADDR"]} IS disabled ****";
				if(trim($eth)==null){echo "Starting......: ".date("H:i:s")." no nic name !! \"{$ligne["IPADDR"]}\"\n";continue;}
				$GLOBALS["SCRIPTS_TOP"][]="{$GLOBALS["ifconfig"]} $eth down";
				$GLOBALS["SCRIPTS_TOP"][]="{$GLOBALS["ipbin"]} route flush table $eth";
			}
			
			$GLOBALS["SCRIPTS_TOP"][]="# [".__LINE__."]";	
		}

		
		$sql="SELECT * FROM nics_vde";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		$count=mysql_num_rows($results);
		if($count>0){
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$eth="virt{$ligne["ID"]}";
				$GLOBALS["SCRIPTS_TOP"][]="# [".__LINE__."] disable virtual NIC $eth";
				$GLOBALS["SCRIPTS_TOP"][]="{$GLOBALS["ifconfig"]} $eth down";
				$GLOBALS["SCRIPTS_TOP"][]="{$GLOBALS["ipbin"]} route flush table $eth";
					
			}
		}
		
	}
	
	private function LoadProcNetDev(){
		$datas=explode("\n",@file_get_contents("/proc/net/dev"));
		while (list ($num, $line) = each ($datas) ){
			if(preg_match("#^(.+?):#",$line,$re)){
				$re[1]=trim($re[1]);
				$GLOBALS["SCRIPTS_TOP"][]="# [".__LINE__."] Found Network Interface <{$re[1]}>";
				$GLOBALS["PROC_NET_DEV"][$re[1]]=true;
			}
		}
		
		$GLOBALS["SCRIPTS_TOP"][]="# [".__LINE__."] Found ". count($GLOBALS["PROC_NET_DEV"])." Network Interface(s)";
	}

	
	private function root_build_debian_config_all_dhcpd(){
		if(!isset($GLOBALS["PROC_NET_DEV"])){$this->LoadProcNetDev();}
		while (list ($interface, $line) = each ($GLOBALS["PROC_NET_DEV"]) ){
			if($interface=="lo"){continue;}
			
			
		}
		
		
	}
	
	
	private function root_build_debian_config_dhcpd($eth){
		if(!is_file($GLOBALS["dhclientbin"])){return;}
		if($this->IsBridged($eth)){return;}
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."]";
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] **** SETTINGS DHCP for \"$eth\" ****";
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."]";
		$GLOBALS["SCRIPTS"][]="{$GLOBALS["dhclientbin"]} -nw $eth";
	
	}

	
	public function root_build_debian_config(){
		$sock=new sockets();
		$unix=new unix();
		$ipClass=new IP();
		$servicebin=$unix->find_program("update-rc.d");
		$NetWorkBroadCastAsIpAddr=$sock->GET_INFO("NetWorkBroadCastAsIpAddr");
		$EnableChilli=$sock->GET_INFO("EnableChilli");
		if(!is_numeric($EnableChilli)){$EnableChilli=0;}
		if(!isset($GLOBALS["PROC_NET_DEV"])){$this->LoadProcNetDev();}
		if(!isset($GLOBALS["ethtoolbin"])){$GLOBALS["ethtoolbin"]=$unix->find_program("ethtool");}
		if(!isset($GLOBALS["sysctl"])){$GLOBALS["sysctl"]=$unix->find_program("sysctl");}
		
		
		
		
		if($EnableChilli==1){
			$ChilliConf=unserialize(base64_decode($sock->GET_INFO("ChilliConf")));
			echo "Starting......: ".date("H:i:s")." Will skip {$ChilliConf["HS_LANIF"]} for HotSpot config\n";
			$eth_SKIP[$ChilliConf["HS_LANIF"]]=true;
		}
		
		$EnableipV6=$sock->GET_INFO("EnableipV6");
		$OVHNetConfig=$sock->GET_INFO("OVHNetConfig");
		if(!is_numeric($OVHNetConfig)){$OVHNetConfig=0;}
		if(!is_numeric($EnableipV6)){$EnableipV6=0;}
		$REMOVE_GATEWAY=false;
		if($OVHNetConfig==1){$NetWorkBroadCastAsIpAddr;}
		if($GLOBALS["AS_ROOT"]){
			$this->LoadTools();
			$unix=new unix();
			echo "Starting......: ".date("H:i:s")." route bin.....: {$GLOBALS["routebin"]}\n";
			echo "Starting......: ".date("H:i:s")." vconfig bin...: {$GLOBALS["vconfigbin"]}\n";
			echo "Starting......: ".date("H:i:s")." modprobe bin..: {$GLOBALS["moprobebin"]}\n";
			echo "Starting......: ".date("H:i:s")." dhclient......: {$GLOBALS["dhclientbin"]}\n";
			
		}
		$q=new mysql();

		$GLOBALS["SCRIPTS"][]="# **** MAIN INTERFACES  ****";
		$q->QUERY_SQL("DELETE FROM `nics` WHERE LENGTH(Interface)<3","artica_backup");
		$sql="SELECT COUNT(*) FROM `nics`";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		
		$CountDeInterfaces=mysql_num_rows($results);
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] $CountDeInterfaces Interface(s) set in database";
		if($CountDeInterfaces==0){
			$GLOBALS["SCRIPTS"][]="# [".__LINE__."] No interface set, using DHCP First";
			$this->root_build_debian_config_all_dhcpd();
			return;
		}
		
		
		$sql="SELECT * FROM `nics` WHERE enabled=1 ORDER BY Interface";
		$results=$q->QUERY_SQL($sql,"artica_backup");

		if(!$q->ok){echo "Starting......: ".date("H:i:s")." Mysql error : $q->mysql_error\n";return;}
		$count=mysql_num_rows($results);
		if($count==0){
			$GLOBALS["SCRIPTS"][]="# No interface defined or enabled";
			echo "Starting......: ".date("H:i:s")." Network Unconfigured\n";return;}
		
		
		
		$f[]="auto lo";
		$f[]="iface lo inet loopback";
		$f[]="";
		$ISREAL=0;
// ------------------------ DEBUT DES CARTES REELES ----------------------------------------------	

		
		$EnableArticaAsGateway=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableArticaAsGateway"));
		
		if($EnableArticaAsGateway==1){
			$GLOBALS["SCRIPTS"][]="# [".__LINE__."] **** ACT AS GATEWAY ****";
			$GLOBALS["SCRIPTS"][]="{$GLOBALS["sysctl"]} -w net.ipv4.ip_forward=1";
		}
		
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
			$eth=trim($ligne["Interface"]);
			$eth=str_replace("\n", "", $eth);
			$eth=str_replace("\r", "", $eth);		
			$MTU=intval($ligne["MTU"]);
			if($MTU==0){$MTU=1500;}	
			$brodcast_cmd=null;
			if(trim($eth)==null){echo "Starting......: ".date("H:i:s")." no nic name !! \"{$ligne["IPADDR"]}\"\n";continue;}
			if(isset($eth_SKIP[$eth])){
				$GLOBALS["SCRIPTS"][]="# [".__LINE__."]";
				$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
				$GLOBALS["SCRIPTS"][]="# [".__LINE__."] **** SETTINGS for \"$eth\" ****";
				$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
				$GLOBALS["SCRIPTS"][]="# [".__LINE__."]";	
				$GLOBALS["SCRIPTS"][]="# [".__LINE__."] BLACKLISTED - SKIPPED";
				echo "Starting......: ".date("H:i:s")." $eth skipping\n";
				continue;
			}
			
			
			
			
			if(!preg_match("#^([a-z]+)([0-9]+)$#", $eth)){
				$GLOBALS["SCRIPTS"][]="# [".__LINE__."] `$eth` bad format - SKIPPED";
				continue;
				
			}
			
			
			if(!isset($GLOBALS["PROC_NET_DEV"][$eth])){
				$GLOBALS["SCRIPTS"][]="# [".__LINE__."]";
				$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
				$GLOBALS["SCRIPTS"][]="# [".__LINE__."] **** SETTINGS for \"$eth\" ****";
				$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
				$GLOBALS["SCRIPTS"][]="# [".__LINE__."]";
				$GLOBALS["SCRIPTS"][]="# [".__LINE__."] HARDWARE ERROR, DID NOT FOUND...";
				continue;
			}
			
			
			$GLOBALS["SCRIPTS"][]="# [".__LINE__."]";
			$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
			$GLOBALS["SCRIPTS"][]="# [".__LINE__."] **** SETTINGS for \"$eth\" ****";
			$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
			$GLOBALS["SCRIPTS"][]="# [".__LINE__."]";
			$GLOBALS["SCRIPTS"][]="# [".__LINE__."] Reload/flush Interface $eth (MTU $MTU)";
			
			if(is_file($GLOBALS["ethtoolbin"])){
				$GLOBALS["SCRIPTS"][]="# [".__LINE__."] disabling GRO/LRO for Interface $eth";
				$GLOBALS["SCRIPTS"][]="{$GLOBALS["ethtoolbin"]} -K $eth gro off";
				$GLOBALS["SCRIPTS"][]="{$GLOBALS["ethtoolbin"]} -K $eth lro off";
			}
			
			if(intval($ligne["UseSPAN"])==1){
				$GLOBALS["SCRIPTS"][]="# [".__LINE__."] UseSPAN == 1 , do nothing...";
				$GLOBALS["SCRIPTS"][]="{$GLOBALS["ifconfig"]} $eth down";
				$GLOBALS["SCRIPTS"][]="{$GLOBALS["ifconfig"]} $eth up";
				continue;
			}
			
			$GLOBALS["SCRIPTS"][]="{$GLOBALS["ifconfig"]} $eth down";
			$GLOBALS["SCRIPTS"][]="{$GLOBALS["ifconfig"]} $eth up";
			$GLOBALS["SCRIPTS"][]="{$GLOBALS["ipbin"]} addr flush $eth";
			$GLOBALS["SCRIPTS"][]="# [".__LINE__."] Remove routes should added by OS during startup";
			$GLOBALS["SCRIPTS"][]="{$GLOBALS["ipbin"]} route del default dev $eth";
			$GLOBALS["SCRIPTS"][]="# [".__LINE__."]";
			if($this->IsBridged($eth)){
				echo "Starting......: ".date("H:i:s")." $eth \" ** is bridged to {$ligne["BridgedTo"]} **\n";
				$f[]="iface $eth inet manual";
				$GLOBALS["SCRIPTS"][]="# [".__LINE__."] **** \"$eth\" IS BRIDGED to {$ligne["BridgedTo"]} ****";
				$GLOBALS["SCRIPTS"][]="{$GLOBALS["ifconfig"]} $eth down";
				$GLOBALS["SCRIPTS"][]="{$GLOBALS["ifconfig"]} $eth 0.0.0.0 promisc up";
				$GLOBALS["SCRIPTS_DOWN"][]="{$GLOBALS["ifconfig"]} $eth down";
				$GLOBALS["SAVED_INTERFACES"][]=$eth;
				$GLOBALS["MEM_INTERFACES"][$eth]="0.0.0.0";
				continue;
			}else{
				$GLOBALS["SCRIPTS"][]="# [".__LINE__."] Not bridged";
				echo "Starting......: ".date("H:i:s")." $eth is not bridged\n";
			}
			
			$ipv6=$ligne["ipv6"];
			$scripts=array();
			if($EnableipV6==0){$ipv6=0;}
			
			
			if(isset($ASALREADY[$eth])){
				$GLOBALS["SCRIPTS"][]="# [".__LINE__."] $eth already set aborting";
				continue;
			}
			$ASALREADY[$eth]=true;
			
			$GLOBALS["SAVED_INTERFACES"][]=$eth;
			$GLOBALS["MEM_INTERFACES"][$eth]=$ligne["IPADDR"];
			$dns=array();
			$f[]="";
			
			$GLOBALS["SCRIPTS"][]="# [".__LINE__."] **** \"$eth\" {$ligne["IPADDR"]} ****";
			echo "Starting......: ".date("H:i:s")." \"$eth\" \"{$ligne["IPADDR"]}\"\n";
			$NETWORK_INTERFACE[]="auto $eth ".$this->auto_eth_virts($eth);	
			$ISREAL++;
			

			
			if($ligne["dhcp"]==1){
				$f[]="iface $eth inet dhcp";
				$GLOBALS["SCRIPTS"][]="{$GLOBALS["dhclientbin"]} -4 $eth";
				if($ipv6==1){$GLOBALS["SCRIPTS"][]="{$GLOBALS["dhclientbin"]} -6 -1 -nw $eth";}
				continue;
			}
			$f[]="iface $eth inet static";

			
			if($eth=="eth0"){
				if($OVHNetConfig==1){
					$REMOVE_GATEWAY=true;
					$GLOBALS["SCRIPTS_ROUTES"][]="# [eth0/".__CLASS__."/".__LINE__."]";
					$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["routebin"]} add -net 0.0.0.0 gw {$ligne["GATEWAY"]} dev eth0";

				}
			}
			
// BROADCAST Calculation ------------------------------------------------------------------------------------------			
			$brodcast_cmd=null;
			$ligne["IPADDR"]=trim($ligne["IPADDR"]);
			if($ligne["BROADCAST"]=="0.0.0.0"){$ligne["BROADCAST"]=null;}
			if($NetWorkBroadCastAsIpAddr==1){$ligne["BROADCAST"]=$ligne["IPADDR"];}
			
			
			if($REMOVE_GATEWAY==true){$ligne["GATEWAY"]="0.0.0.0";}
			$GLOBALS["SCRIPTS"][]="# {$ligne["IPADDR"]}/{$ligne["NETMASK"]} Gateway: {$ligne["GATEWAY"]} mtu:$MTU Broadcast:{$ligne["BROADCAST"]} ".__CLASS__."/".__FUNCTION__ ." in line ".__LINE__;
			
			if($ligne["BROADCAST"]<>null){
				if($ipClass->isValid($ligne["BROADCAST"])){
					$brodcast_cmd=" broadcast {$ligne["BROADCAST"]}";
				}
			}
			
			if($ligne["IPADDR"]=="0.0.0.0"){
				$GLOBALS["SCRIPTS"][]="# [".__LINE__."] `{$ligne["IPADDR"]}` bad format!";
				continue;
			}
				
			if($ligne["IPADDR"]==null){
				$GLOBALS["SCRIPTS"][]="# [".__LINE__."] No ip address defined IPADDR=NULL !";
				continue;
			}
			
// IPV4 Settings ---------------------------------------------------------------------------------------------------			
			$ifconfig="{$GLOBALS["ifconfig"]} $eth {$ligne["IPADDR"]} netmask {$ligne["NETMASK"]}$brodcast_cmd mtu $MTU up";
			$GLOBALS["SCRIPTS"][]="$ifconfig";
// MII-TOOL ---------------------------------------------------------------------------------------------------			
			$GLOBALS["SCRIPTS"][]="# [".__LINE__."] MII-TOOL {$ligne["miitool"]} Binary: {$GLOBALS["MIITOOLBIN"]}";
			if($ligne["miitool"]<>null){
				if($GLOBALS["MIITOOLBIN"]<>null){
					$GLOBALS["SCRIPTS"][]="{$GLOBALS["MIITOOLBIN"]} --force={$ligne["miitool"]} $eth";
				}
			}
			
			
			
// IPV6 Settings ---------------------------------------------------------------------------------------------------
			$GLOBALS["SCRIPTS"][]="# [".__LINE__."] EnableipV6=$EnableipV6 ipv6=$ipv6 {$ligne["ipv6addr"]}/{$ligne["ipv6mask"]} gw {$ligne["ipv6gw"]}";
			if($ipv6==1){
				$GLOBALS["SCRIPTS"][]="{$GLOBALS["ifconfig"]} $eth inet6 add {$ligne["ipv6addr"]}/{$ligne["ipv6mask"]}";
				
				if($ligne["ipv6gw"]<>null){
					$GLOBALS["SCRIPTS"][]="{$GLOBALS["ipbin"]} -6 route add default via {$ligne["ipv6gw"]} dev $eth";
				}
				
			}
			
			
			
			$f[]="\taddress {$ligne["IPADDR"]}";
			$f[]="\tnetmask {$ligne["NETMASK"]}";
			
			if($ligne["NETWORK"]<>null){$f[]="\tnetwork {$ligne["NETWORK"]}";}
			if(trim($ligne["GATEWAY"])==null){$ligne["GATEWAY"]="0.0.0.0";}
			
			if($ligne["BROADCAST"]<>null){
				if($ligne["GATEWAY"]<>"0.0.0.0"){
					$f[]="\tbroadcast {$ligne["BROADCAST"]}";
				}
			}
			$mainroute=null;
			if(!$REMOVE_GATEWAY){
				if($ligne["GATEWAY"]<>null){
					if($ligne["GATEWAY"]<>"0.0.0.0"){
						$f[]="\tgateway {$ligne["GATEWAY"]}";
						$mainroute="{$GLOBALS["routebin"]} add -net 0.0.0.0 gw {$ligne["GATEWAY"]} dev ".$this->NicToOther($eth);
						if(preg_match("#^([0-9\.]+)\.[0-9]+$#", $ligne["IPADDR"],$re)){
							if(!isset($ALREADYNETSROUTE["{$re[1]}.0"])){
								$ALREADYNETSROUTE["{$re[1]}.0"]=true;
								$mainroute="{$GLOBALS["routebin"]} add -net {$re[1]}.0 netmask {$ligne["NETMASK"]} gw {$ligne["GATEWAY"]} dev ".$this->NicToOther($eth);

							}
						}
					}
				}			
			}
		
			$failover=$this->root_build_debian_failover($eth);
			if($failover<>null){
				$f[]=$failover;
			}
		
			
			if($ligne["DNS1"]<>null){$dns[]=$ligne["DNS1"];}
			if($ligne["DNS2"]<>null){$dns[]=$ligne["DNS2"];}
			$unix=new unix();

				

			//$GLOBALS["SCRIPTS"][]=$mainroute;
			
			if($ligne["IsBridge"]==1){
				$php5=$unix->LOCATE_PHP5_BIN();
				$f[]="\tbridge_ports {$ligne["BRIDGE_PORTS"]}";
				$f[]="\tbridge_stp off";
				$f[]="\tbridge_maxwait 5";
				$GLOBALS["SCRIPTS"][]="$php5 /usr/share/artica-postfix/exec.vservers.php --checkbridge $eth {$ligne["BRIDGE_PORTS"]}";
  				$f[]="\tpre-up $php5 /usr/share/artica-postfix/exec.vservers.php --checkbridge $eth {$ligne["BRIDGE_PORTS"]}";
  				
  				$GLOBALS["SCRIPTS"][]="{$GLOBALS["ifconfig"]} {$ligne["BRIDGE_PORTS"]} up";
  				$f[]="\tpre-up ifconfig {$ligne["BRIDGE_PORTS"]} up";
  							
			}
					
			
			
			
			if(count($dns)>0){
				$f[]="\t# dns -* options are implemented by the resolvconf package, if installed";
				$f[]="\tdns-nameservers ". @implode(" ",$dns);	
			}

			$routes=unserialize($ligne["routes"]);
			if($OVHNetConfig==0){
			if(is_array($routes)){
				if(count($routes)>0){
	         		while (list ($ip, $ip_array) = each ($routes) ){
	         			if($ligne["GATEWAY"]=="0.0.0.0"){$ligne["GATEWAY"]=null;}
	         			
	         			
	         			
		         		if($ip=="default"){
		         			
							$scripts[]="\tup {$GLOBALS["ipbin"]} route add default via {$ligne["GATEWAY"]} dev ". $this->NicToOther($eth)."  metric 10{$ligne["metric"]}";
							$scripts[]="\tdown {$GLOBALS["ipbin"]} route del default via {$ligne["GATEWAY"]} dev ". $this->NicToOther($eth)."  metric 10{$ligne["metric"]}";
		         			continue;
		         		}
		         			
		         		if($ip_array["GATEWAY"]==null){
		         			if($ip_array["DEV"]<>null){
		         				
		         				$this->scriptaddroute($ip,$ip_array["NETMASK"],$ip_array["GATEWAY"],0,$ip_array["DEV"]);
		         				$scripts[]="\tup {$GLOBALS["routebin"]} add -net $ip netmask {$ip_array["NETMASK"]} dev ". $this->NicToOther($ip_array["DEV"]);
		         				$scripts[]="\tdown {$GLOBALS["routebin"]} del -net $ip netmask {$ip_array["NETMASK"]} dev ".$this->NicToOther($ip_array["DEV"]);
		         				continue;
		         			}
		         		}
		         		
		         		$scripts[]="\tup {$GLOBALS["ipbin"]} route add default via {$ligne["GATEWAY"]} dev ". $this->NicToOther($eth)."  metric 10{$ligne["metric"]}";
		         		
		         		$scripts[]="\tdown {$GLOBALS["ipbin"]} route del default via {$ligne["GATEWAY"]} dev ". $this->NicToOther($eth)."  metric 10{$ligne["metric"]}";
		         		$this->scriptaddroute($ip,$ip_array["NETMASK"],$ip_array["GATEWAY"],$ligne["metric"]);
						$scripts[]="\tup {$GLOBALS["routebin"]} add -net $ip netmask {$ip_array["NETMASK"]} gw {$ip_array["GATEWAY"]} metric 10{$ligne["metric"]}";
	   					$scripts[]="\tdown {$GLOBALS["routebin"]} del -net $ip netmask {$ip_array["NETMASK"]} gw {$ip_array["GATEWAY"]} metric 10{$ligne["metric"]}";
	   				}	
	        	}
			}
		}
		
		if(count($scripts)==0){
		if($ligne["GATEWAY"]<>null){
				
				if($ligne["metric"]==null){if($eth=="eth0"){$ligne["metric"]=1;}else{$ligne["metric"]=$this->lastmetric();}}
				$f[]="\tup {$GLOBALS["ipbin"]} route add default via {$ligne["GATEWAY"]} dev ". $this->NicToOther($eth)."  metric 10{$ligne["metric"]}";
				$f[]="\tdown {$GLOBALS["ipbin"]} route del default via {$ligne["GATEWAY"]} dev ". $this->NicToOther($eth)."  metric 10{$ligne["metric"]}";
			}		

		}
		//UCARP FailOver
		if($ligne["ucarp-enable"]==1){
			$f[]="";
			$f[]="iface $eth:ucarp inet static";
			$f[]="\taddress {$ligne["ucarp-vip"]}";
			$f[]="\tnetmask {$ligne["NETMASK"]}";
			$f[]="";
		}
		
		
		
		$f[]=@implode("\n", $scripts);
		
		
		if($ipv6==1){
				$f[]="\n\n# Ipv6 config for $eth";
				$f[]=$this->root_build_debian_config_ipv6($eth);
		}else{
			if(is_file("/etc/init.d/$eth-up")){
				shell_exec("$servicebin -f $eth-up remove >/dev/null 2>&1");
				@unlink("/etc/init.d/$eth-up");
			}				
		}

	    $f[]="";
	    $f[]="";
		
		}
		
		$GLOBALS["NoGatewayForVirtualNetWork"]=$sock->GET_INFO("NoGatewayForVirtualNetWork");	
		if(!is_numeric($GLOBALS["NoGatewayForVirtualNetWork"])){$GLOBALS["NoGatewayForVirtualNetWork"]=0;}	
		if($REMOVE_GATEWAY){$GLOBALS["NoGatewayForVirtualNetWork"]=1;}
		
		
		$f[]=$this->BuildBridges();
		$f[]=$this->BuildVirtIps();
		$f[]=$this->BuildVlans();
		if($ISREAL==0){return null;}
		return @implode("\n",$f);
	
	}
	
	public function scriptaddroute($ip,$netmask,$gateway,$metric=0,$eth=null,$default=false){ //depreciated
	}
	
	public function BuildBridges_getlinked($bridge){
		$q=new mysql();
		$f=array();
		$sql="SELECT Interface FROM `nics` WHERE Bridged=1 AND BridgedTo='$bridge'";
		$results = $q->QUERY_SQL($sql,"artica_backup");
		while ($ligne = mysql_fetch_assoc($results)) {
			$f[]=$ligne["Interface"];
		}
		
		return $f;
		
		
	}
	
	private function BuildBridges(){
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
		$GLOBALS["SCRIPTS"][]="# **** PHYSICALS BRIDGES **** [".__CLASS__."/".__LINE__."]";
		$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
		$q=new mysql();
		$this->LoadTools();
		if(!is_file($GLOBALS["brctlbin"])){
			$GLOBALS["SCRIPTS"][]="# **** brctlbin no such file...[".__LINE__."]";
			$GLOBALS["SCRIPTS"][]="{$GLOBALS["rmbin"]} -f /etc/artica-postfix/IPTABLES_BR_BRIDGE >/dev/null 2>&1";
			$GLOBALS["SCRIPTS"][]="{$GLOBALS["PHP5BIN"]} /usr/share/artica-postfix/exec.remove.iptablesbridges.php --remove-nics";
			return false;
		}
		$sql="SELECT * FROM nics_bridge";
		$results = $q->QUERY_SQL($sql,"artica_backup");
		
		if(mysql_num_rows($results)==0){
			$GLOBALS["SCRIPTS"][]="# **** nics_bridge no such items...[".__CLASS__."/".__LINE__."]";
			$GLOBALS["SCRIPTS"][]="{$GLOBALS["PHP5BIN"]} /usr/share/artica-postfix/exec.remove.iptablesbridges.php --remove-nics";
			return;
		}
		
		$ip=new IP();
		
		$GW=false;
		

		
		$GLOBALS["SCRIPTS"][]="{$GLOBALS["touchbin"]} /etc/artica-postfix/IPTABLES_BR_BRIDGE >/dev/null 2>&1";
		$GLOBALS["SCRIPTS"][]="{$GLOBALS["sysctl"]} -w net.ipv4.ip_forward=1";
		$GLOBALS["SCRIPTS"][]="# Table nics_bridge ". mysql_num_rows($results)." element(s) [".__CLASS__."/".__LINE__."]";
		while ($ligne = mysql_fetch_assoc($results)) {
			$ID=$ligne["ID"];
			$eth="br{$ligne["ID"]}";
			$Linked=$this->BuildBridges_getlinked($eth);
			$GLOBALS["SCRIPTS"][]="# br{$ligne["ID"]} Linked to ".count($Linked)." interface(s)";
			
			if(count($Linked)==0){continue;}
			$netup=null;
			$netmask=$ligne["netmask"];
			$gateway=$ligne["gateway"];
			$ipaddr=$ligne["ipaddr"];
			$broadcast=$ligne["broadcast"];
			if(!$ip->isValid($ipaddr)){continue;}
			if($gateway=="0.0.0.0"){$gateway=null;}
			if($netmask=="0.0.0.0"){$netmask=null;}
			if($broadcast=="0.0.0.0"){$broadcast=null;}
			if(!is_numeric($ligne["STP"])){$ligne["STP"]=1;}
			
			$f[]="iface $eth inet static";
			$f[]="\tbridge_ports ";@implode(" ", $Linked);
			$f[]="\taddress $ipaddr";
			
			if($netmask<>null){
				$f[]="\tnetmask $netmask";
				$netup=" netmask $netmask ";
			}
			
			$GLOBALS["SCRIPTS"][]="{$GLOBALS["brctlbin"]} addbr $eth";
			while (list ($a, $b) = each ($Linked) ){
				if(trim($b)==null){continue;}
				$GLOBALS["SCRIPTS"][]="{$GLOBALS["ifconfig"]} $b down";
				$GLOBALS["SCRIPTS"][]="{$GLOBALS["brctlbin"]} addif $eth $b";
				$GLOBALS["SCRIPTS"][]="{$GLOBALS["ifconfig"]} $b 0.0.0.0 promisc up";
				$GLOBALS["SCRIPTS_DOWN"][]="{$GLOBALS["brctlbin"]} delif $eth $b";
				$GLOBALS["SCRIPTS_DOWN"][]="{$GLOBALS["ifconfig"]} $b down";
			}
			
			
			$GLOBALS["SCRIPTS"][]="{$GLOBALS["ifconfig"]} $eth $ipaddr$netup up";
			if($ligne["STP"]==1){
				$GLOBALS["SCRIPTS"][]="{$GLOBALS["brctlbin"]} stp $eth 1";
			}
			$GLOBALS["SCRIPTS"][]="{$GLOBALS["brctlbin"]} setfd $eth 15";
			
			
			
			$GLOBALS["SCRIPTS_DOWN"][]="{$GLOBALS["ifconfig"]} $eth down";
			$GLOBALS["SCRIPTS_DOWN"][]="{$GLOBALS["brctlbin"]} delbr $eth";
			
			$GLOBALS["SAVED_INTERFACES"][]=$eth;
			$GLOBALS["MEM_INTERFACES"][$eth]="$ipaddr";
			if($broadcast<>null){
				$f[]="\tbroadcast $broadcast";
			}			
			
			if($gateway<>null){
				if($ip->isValid($gateway)){
					$f[]="\tgateway $gateway";
					if(!$GW){
						$GLOBALS["SCRIPTS_ROUTES"][]="#[$eth/".__CLASS__."/".__LINE__."] Default gateway $gateway\n";
						$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["routebin"]} add -net 0.0.0.0 gw $gateway dev $eth";
						$GW=true;
					}
				}
			}
		$f[]="";}
		
		$GLOBALS["SCRIPTS"][]="# **** Bridge configuration END ****";
		return @implode("\n",$f);
		
		
	}
	
	public function GetCDIRNetwork($ipaddr,$network){
		exec("/usr/share/artica-postfix/bin/ipcalc $ipaddr/$network 2>&1",$results);
		while (list ($index, $line) = each ($results) ){
			if(preg_match("#Network:\s+([0-9\.]+)\/([0-9]+)#i", $line,$re)){
				if($GLOBALS["VERBOSE"]){echo "$ipaddr/$network  = `{$re[1]}/{$re[2]}`\n";}
				return "{$re[1]}/{$re[2]}";}
		}
		
		$re=explode(".",$ipaddr);
		return "{$re[0]}.{$re[1]}.{$re[2]}.0/$network";
			
	}
	
	
	
	
	
	public function BuildVirtIps(){
		$LastMetric=$this->lastMetric();
		if($LastMetric==0){$LastMetric=1;}
		$sock=new sockets();
		$NetWorkBroadCastAsIpAddr=$sock->GET_INFO("NetWorkBroadCastAsIpAddr");
		$unix=new unix();
		if(!isset($GLOBALS["moprobebin"])){
			$GLOBALS["moprobebin"]=$unix->find_program("modprobe");
		}
		if(!isset($GLOBALS["vconfigbin"])){
			$GLOBALS["vconfigbin"]=$unix->find_program("vconfig");
		}
		if(!isset($GLOBALS["ifconfig"])){
			$GLOBALS["ifconfig"]=$unix->find_program("ifconfig");
		}
		if(!isset($GLOBALS["ipbin"])){
			$GLOBALS["ipbin"]=$unix->find_program("ip");
		}	
		if(!isset($GLOBALS["routebin"])){
			$GLOBALS["routebin"]=$unix->find_program("route");
		}
		if(!isset($GLOBALS["ethtoolbin"])){$GLOBALS["ethtoolbin"]=$unix->find_program("ethtool");}
		
		$sql="SELECT * FROM nics_virtuals WHERE failover=0 ORDER BY ID";
		$q=new mysql();
		if(!$q->FIELD_EXISTS("nics_virtuals","failover","artica_backup")){$sql="ALTER TABLE `nics_virtuals` ADD `failover` TINYINT( 1 ) NOT NULL,ADD INDEX ( `failover` )";$q->QUERY_SQL($sql,'artica_backup');if(!$q->ok){echo $q->mysql_error."\n\n";}}
		$results=$q->QUERY_SQL($sql,"artica_backup");
		$count=mysql_num_rows($results);
		if($count>0){
			$f[]="";
			$f[]="#Virtual interfaces...\n";
			
		}
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if($ligne["ipv6"]==1){continue;}
			$arr=explode(".",$ligne["ipaddr"]);
			$arr[3]="255";
			$brd=implode(".",$arr);
			$ligne["nic"]=trim($ligne["nic"]);
			$eth=str_replace("\n", "", $ligne["nic"]);
			$eth=str_replace("\r", "", $ligne["nic"]);
			$metric=$ligne["metric"];
			if($LastMetric>$metric){$metric=$LastMetric+1;$LastMetric=$metric;}
			
			$GLOBALS["SCRIPTS"][]="# [".__LINE__."]";
			$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
			$GLOBALS["SCRIPTS"][]="# [".__LINE__."] **** SETTINGS for VIRTAL INTERFACE \"{$ligne["nic"]}:{$ligne["ID"]} Metric:$metric\" ****";
			$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
			$GLOBALS["SCRIPTS"][]="# [".__LINE__."]";
			
			
			if($this->IsBridged($ligne["nic"])){
				$GLOBALS["SCRIPTS"][]="# [".__LINE__."] {$ligne["nic"]} is Bridged, skip it";
				echo "Starting......: ".date("H:i:s")." Virtual {$ligne["nic"]}:{$ligne["ID"]}  {$ligne["nic"]} is bridged, aborting\n";
				continue;
			}
			$eth="{$ligne["nic"]}:{$ligne["ID"]}";
			$ethName="Net{$ligne["nic"]}{$ligne["ID"]}";
			$CDIR=$ligne["cdir"];
			if($NetWorkBroadCastAsIpAddr==1){$brd=$ligne["ipaddr"];}
			if($ligne["gateway"]==null){$ligne["gateway"]="0.0.0.0";}
			$GLOBALS["MEM_INTERFACES"][$eth]=$ligne["ipaddr"];
				
			$ligne["cdir"]=$this->checkcdir($ligne["cdir"]);
			echo "Starting......: ".date("H:i:s")." Virtual $eth net:{$ligne["cdir"]} {$ligne["ipaddr"]}/{$ligne["netmask"]} gateway {$ligne["gateway"]} ({$ligne["org"]})\n";
			
			
			$GLOBALS["SCRIPTS_TOP"][]="# Stopping Interface $eth";
			$GLOBALS["SCRIPTS_TOP"][]="{$GLOBALS["ifconfig"]} $eth down";
			$GLOBALS["SCRIPTS"][]="# [".__LINE__."] **** $eth {$ligne["ipaddr"]} ****";
			$GLOBALS["SCRIPTS"][]="OUTPUT Linking $eth {$ligne["ipaddr"]}";
			
			$ligne["ipaddr"]=trim($ligne["ipaddr"]);
			if($ligne["ipaddr"]==null){
				$GLOBALS["SCRIPTS"][]="# [".__LINE__."] **** $eth Ip addr = null ! ****";
				continue;
			}
			if($ligne["ipaddr"]=="0.0.0.0"){
				$GLOBALS["SCRIPTS"][]="# [".__LINE__."] **** $eth {$ligne["ipaddr"]} bad format ****";
				continue;
			}
			
			$GLOBALS["SCRIPTS"][]="{$GLOBALS["ifconfig"]} $eth {$ligne["ipaddr"]} netmask {$ligne["netmask"]} up";
				
			$scripts=array();
			if($GLOBALS["NoGatewayForVirtualNetWork"]==0){
			if($ligne["gateway"]<>"0.0.0.0"){

				$GLOBALS["SCRIPTS_ROUTES"][]="#[$eth/".__CLASS__."/".__LINE__."] Force Gateway={$ligne["ForceGateway"]}/{$ligne["gateway"]}";
				if($ligne["ForceGateway"]==1){
					$GLOBALS["SCRIPTS_ROUTES_RTTABLE"][]="#";
					$GLOBALS["SCRIPTS_ROUTES_RTTABLE"][]="#";
					$ROUTE_NUMBER=250+intval($ligne["ID"]);
					if($ROUTE_NUMBER==299){$ROUTE_NUMBER=$ROUTE_NUMBER+1;}
					$GLOBALS["SCRIPTS_ROUTES_RTTABLE"][]="# ********************** TABLE $ROUTE_NUMBER $ethName **********************";
					$GLOBALS["SCRIPTS_ROUTES_RTTABLE"][]="#";
					$GLOBALS["SCRIPTS_ROUTES_RTTABLE"][]="#[$eth/".__CLASS__."/".__LINE__."] Default gateway {$ligne["gateway"]} $CDIR table number $ROUTE_NUMBER/$ethName\n";
					$GLOBALS["SCRIPTS_ROUTES_RTTABLE"][]="#";
					$GLOBALS["SCRIPTS_ROUTES_RTTABLE"][]="{$GLOBALS["echobin"]} \"$ROUTE_NUMBER	$ethName\" >> /etc/iproute2/rt_tables";
					if($CDIR<>null){
						$GLOBALS["SCRIPTS_ROUTES_RTTABLE"][]="{$GLOBALS["ipbin"]} route flush table $ethName";
						$GLOBALS["SCRIPTS_ROUTES_RTTABLE"][]="{$GLOBALS["ipbin"]} route add $CDIR dev $eth src {$ligne["ipaddr"]} table $ethName";
						$GLOBALS["SCRIPTS_ROUTES_RTTABLE"][]="{$GLOBALS["ipbin"]} route add {$ligne["gateway"]} dev $eth src {$ligne["ipaddr"]} table $ethName";
						
					
					}
					$GLOBALS["SCRIPTS_ROUTES_RTTABLE"][]="{$GLOBALS["ipbin"]} route add 0.0.0.0/0 dev $eth src {$ligne["ipaddr"]} table $ethName";
					$GLOBALS["SCRIPTS_ROUTES_RTTABLE"][]="{$GLOBALS["ipbin"]} route add broadcast $brd dev $eth src {$ligne["ipaddr"]} table $ethName";
					$GLOBALS["SCRIPTS_ROUTES_RTTABLE"][]="{$GLOBALS["ipbin"]} rule add from {$ligne["ipaddr"]} table $ethName";
					
				}else{
					if($ligne["cdir"]<>null){
						$mainroute="{$ligne["cdir"]} via {$ligne["gateway"]} src {$ligne["ipaddr"]} metric $metric";
						$GLOBALS["SCRIPTS_ROUTES"][]="#[$eth/".__CLASS__."/".__LINE__."] $mainroute";
						$GLOBALS["SCRIPTS_ROUTES"][]="{$GLOBALS["ipbin"]} route add $mainroute";
					}
				}
			}
		}
	}		
		
}
	
	
	
	public function BuildVlans(){
		$sock=new sockets();
		$NetWorkBroadCastAsIpAddr=$sock->GET_INFO("NetWorkBroadCastAsIpAddr");
		$unix=new unix();
		if(!isset($GLOBALS["moprobebin"])){
			$GLOBALS["moprobebin"]=$unix->find_program("modprobe");
		}
		if(!isset($GLOBALS["vconfigbin"])){
			$GLOBALS["vconfigbin"]=$unix->find_program("vconfig");
		}	
		if(!isset($GLOBALS["ifconfig"])){
			$GLOBALS["ifconfig"]=$unix->find_program("ifconfig");
		}
		if(!isset($GLOBALS["ipbin"])){
			$GLOBALS["ipbin"]=$unix->find_program("ip");
		}		
		if(!isset($GLOBALS["routebin"])){
			$GLOBALS["routebin"]=$unix->find_program("route");
		}
		
		$sql="SELECT * FROM nics_vlan ORDER BY ID DESC";
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");
		$count=mysql_num_rows($results);
		if($count>0){
			$this->module_8021q();
			$GLOBALS["SCRIPTS"][]="# [".__LINE__."]";
			$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
			$GLOBALS["SCRIPTS"][]="# [".__LINE__."] **** SETTINGS for VLANs ****";
			$GLOBALS["SCRIPTS"][]="# [".__LINE__."] *******************************";
			$GLOBALS["SCRIPTS"][]="# [".__LINE__."]";
			$GLOBALS["SCRIPTS"][]="{$GLOBALS["sysctl"]} -w net.ipv4.ip_forward=1";
		}
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$GLOBALS["SCRIPTS"][]="{$GLOBALS["moprobebin"]} 8021q >/dev/null 2>&1";
			if(!is_file($GLOBALS["vconfigbin"])){continue;}
			if($ligne["metric"]==0){$ligne["metric"]=$this->lastmetric();}
			$arr=explode(".",$ligne["ipaddr"]);
			$arr[3]="255";
			$vlanid_text=null;
			$brd=implode(".",$arr);
			$eth="{$ligne["nic"]}";
			$vlanid=$ligne["vlanid"];
			if($vlanid==0){continue;}
			if($vlanid>0){$vlanid_text=".$vlanid";}
				
			if(strlen($vlanid)==1){$vlansuffix="0$vlanid";$vlanprexif="2";}
			if(strlen($vlanid)==2){$vlansuffix=$vlanid;$vlanprexif="2";}
			$table_routeid="$vlanprexif$vlansuffix";
			if(strlen($vlanid)>2){
				if($vlanid>200){$table_routeid=$vlanid;}
				if($vlanid<200){$table_routeid=200+$vlanid;}
			}
			$table_routename="vlan{$ligne["vlanid"]}";
			$this->vlanTable($table_routename, $table_routeid);
			
			echo "Starting......: ".date("H:i:s")." VLAN $eth {$ligne["ipaddr"]}/{$ligne["netmask"]} gateway {$ligne["gateway"]} ({$ligne["org"]}) route ID $table_routeid/$table_routename\n";
			$f[]="";
			$GLOBALS["MEM_INTERFACES"]["$eth$vlanid_text"]=$ligne["ipaddr"];
					$NETWORK_INTERFACE[]="auto $eth$vlanid_text";
					$f[]="iface $eth$vlanid_text inet static";
					$f[]="\taddress {$ligne["ipaddr"]}";
					$f[]="\tnetmask {$ligne["netmask"]}";
					if($NetWorkBroadCastAsIpAddr==1){$brd=$ligne["ipaddr"];}
					$f[]="\tbroadcast $brd";
					$GLOBALS["SCRIPTS"][]="{$GLOBALS["ifconfig"]} $eth$vlanid_text down";
					$GLOBALS["SCRIPTS"][]="{$GLOBALS["vconfigbin"]} rem $eth$vlanid_text";
					$GLOBALS["SCRIPTS"][]="{$GLOBALS["vconfigbin"]} add $eth {$ligne["vlanid"]}";
					$GLOBALS["SCRIPTS"][]="{$GLOBALS["ifconfig"]} $eth$vlanid_text down";
					$GLOBALS["SCRIPTS"][]="{$GLOBALS["ifconfig"]} $eth$vlanid_text {$ligne["ipaddr"]} netmask {$ligne["netmask"]} up";
					$GLOBALS["SCRIPTS"][]="{$GLOBALS["ipbin"]} route flush cache";
					$f[]="\tpre-up {$GLOBALS["moprobebin"]} 8021q >/dev/null 2>&1";
					$f[]="\tpost-up {$GLOBALS["ipbin"]} route flush cache";
					$f[]="\tmtu 1500";
					$f[]="\tvlan-raw-device {$ligne["nic"]}";
					$f[]="";
					$f[]="";
						
			}
			
			return @implode("\n", $f);
	}
	
	
	
	private function checkcdir($cdir,$ip=null,$netmask=null){
		if(!preg_match("#(.+?)\/([0-9]+)#", $cdir,$re)){return null;}
		$bit=$re[2];
		$ip=$re[1];
		if(!preg_match("#([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)$#", $ip,$re)){return null;}
		$ip="{$re[1]}.{$re[2]}.{$re[3]}.0";
		if($bit==0){$bit=32;}
		return "$ip/$bit";
		
	}
	

	
	
	private function vlanTable($table_routename,$table_routeid){
		$f=explode("\n", @file_get_contents("/etc/iproute2/rt_tables"));
		while (list ($index, $line) = each ($f) ){
			if(preg_match("#([0-9]+)\s+(.+)#", $line,$re)){
				$tableid=$re[1];
				$tablename=trim($re[2]);
				if($tablename==$table_routename){return;}
			}
			
		}
		
		$f[]="$table_routeid\t$table_routename";
		@file_put_contents("/etc/iproute2/rt_tables", @implode("\n", $f)."\n");
		
	}
	
	private function module_8021q(){
		$f=explode("\n",@file_get_contents("/etc/modules"));
		$unix=new unix();
		$modprobe=$unix->find_program("modprobe");
		shell_exec("$modprobe 8021q >/dev/null 2>&1");
		while (list ($index, $line) = each ($f) ){
			if(preg_match("#^8021q#", $line)){return;}
		}
		$f[]="8021q";
		@file_put_contents("/etc/modules", @implode("\n", $f));
		
	}
	
	
	public function CreateLXCBridge($interface,$BridgeName){
		if(!isset($interface)){return false;}
		if($interface==null){return false;}
		$nic=new system_nic($interface);
		$routes=addslashes(serialize($nic->ROUTES));
		$q=new mysql();
		
		$sql="DELETE FROM `nics` WHERE `Interface`='$BridgeName'";
		$q->QUERY_SQL($sql,"artica_backup");
		
		
		$sql="
		INSERT IGNORE INTO `nics` (`Interface`,`IPADDR`,`NETMASK`,`GATEWAY`,`BROADCAST`,`DNS1`,`DNS2`,`dhcp`,`ROUTES`,
		`NETWORK`,`enabled`,`BRIDGE_PORTS`,`IsBridge`)
		VALUES('$BridgeName','$nic->IPADDR','$nic->NETMASK','$nic->GATEWAY','$nic->BROADCAST','$nic->DNS_1',
		'$nic->DNS_2','$nic->dhcp','$routes','$nic->NETWORK',1,'$interface',1);
		";
		
		if($GLOBALS["VERBOSE"]){echo $sql;}
		$q=new mysql();
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return false;}
		
		$sql="UPDATE `nics` SET Bridged=1,BridgedTo='$BridgeName' WHERE `Interface`='$interface'";
		$q=new mysql();
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return false;}	
		return true;
	}
	
	public function RemoveLXCBridge($interface,$BridgeName){
		$q=new mysql();
		$sql="DELETE FROM `nics` WHERE `Interface`='$BridgeName'";
		$q->QUERY_SQL($sql,"artica_backup");	
		
		$sql="UPDATE `nics` SET Bridged=0,BridgedTo='' WHERE `Interface`='$interface'";
		$q=new mysql();
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return false;}	
		return true;		
		
	}
	
	
	private function auto_eth_virts($eth){
		$q=new mysql();
		if(!$q->FIELD_EXISTS("nics_virtuals","ipv6","artica_backup")){
			$sql="ALTER TABLE `nics_virtuals` ADD `ipv6` TINYINT( 1 ) NOT NULL";
			$q->QUERY_SQL($sql,'artica_backup');
		}		
		
		$sql="SELECT ID FROM nics_virtuals WHERE `nic`='$eth' AND ipv6=0 ORDER BY ID DESC";
		
		
		
		$results=$q->QUERY_SQL($sql,"artica_backup");
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
			$f[]="$eth:{$ligne["ID"]}";
		}	
		return @implode(" ",$f);
	}
	
	function IfBridgeExists($br){
		$this->LoadTools();
		if(isset($GLOBALS["CLASS_SOCKETS"])){$sock=$GLOBALS["CLASS_SOCKETS"];}else{$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}
		exec("{$GLOBALS["brctlbin"]} showstp $br 2>&1",$results);
		if($GLOBALS["VERBOSE"]){echo count($results)." lines for {$GLOBALS["brctlbin"]} showstp $br\n";}
	
		while (list ($index, $line) = each ($results) ){
			if(preg_match("#^([a-zA-Z0-9]+)#",$line,$re)){
				if($GLOBALS["VERBOSE"]){echo "Found Bridge {$re[1]}\n";}
				if($re[1]=="$br"){return true;}
			}
		}
	
		return false;
	}	
	
	
	function ConstructVirtsIP(){
		$sock=new sockets();
		$sock->getFrameWork("network.php?crossroads-restart=yes");
		$sock->getFrameWork("cmd.php?virtuals-ip-reconfigure=yes");
	}

}
?>