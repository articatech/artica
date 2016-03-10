<?php
if(!isset($GLOBALS["VERBOSE"])){$GLOBALS["VERBOSE"]=false;}
if(isset($_SESSION["TIMEZONES"])){if(function_exists("getLocalTimezone")){@date_default_timezone_set($_SESSION["TIMEZONES"]);}}
if(isset($GLOBALS["TIMEZONES"])){if(function_exists("getLocalTimezone")){@date_default_timezone_set($GLOBALS["TIMEZONES"]);}}
//error_reporting(E_ALL & ~E_NOTICE);
if(!isset($GLOBALS["DEBUG_INCLUDES"])){$GLOBALS["DEBUG_INCLUDES"]=false;}
if(!isset($GLOBALS["LOGON-PAGE"])){$GLOBALS["LOGON-PAGE"]=false;}
if(function_exists("debug_mem")){debug_mem();}
if(isset($GLOBALS["DEBUG_PROCESS"])){if(function_exists("writelogs")){writelogs("OK FOR THAT",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}}
include_once(dirname(__FILE__)."/class.semaphores.php");
if(function_exists("debug_mem")){debug_mem();}
if(isset($GLOBALS["DEBUG_PROCESS"])){if(function_exists("writelogs")){writelogs("OK FOR THAT",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}}
include_once(dirname(__FILE__)."/logs.inc");
include_once(dirname(__FILE__)."/class.stats-appliance.inc");
if(function_exists("debug_mem")){debug_mem();}
if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }



if(function_exists("posix_getuid")){
	if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}else{$GLOBALS["AS_ROOT"]=false;}}
	
	if(!$GLOBALS["AS_ROOT"]){
		if(!isset($GLOBALS["HERLPER_LOADED_BY_SQUID"])){
			if(!isset($GLOBALS["KAV4PROXY_NOSESSION"])){$GLOBALS["KAV4PROXY_NOSESSION"]=false;}
			if(!$GLOBALS["KAV4PROXY_NOSESSION"]){if(function_exists("debug_mem")){debug_mem();}
			if(!isset($_SESSION["uid"])){session_start();}
			}
		}
	}
}
class sockets{
	var $remote_ip;
	var $remote_port;
	var $error=false;
	var $semaphore_key=1376880653;
	var $semaphore_memory=9024000;
	var $ArticaMetaEnabled=0;
	public $SquidPerformance;
	public $EnableIntelCeleron;
	
	function sockets(){
		if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("OK FOR THAT",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		if(!isset($GLOBALS["VERBOSE_MASTER"])){$GLOBALS["VERBOSE_MASTER"]=false;}
		if(!isset($GLOBALS["DEBUG"])){$GLOBALS["DEBUG"]=false;}
		if(!isset($GLOBALS["DEBUG_SOCK"])){$GLOBALS["DEBUG_SOCK"]=false;}
		$this->MEMCACHE_ENABLED();
		
		if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("OK FOR THAT",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		$GLOBALS["posix_getuid"]=1000;
		if(function_exists("posix_getuid")){
			$GLOBALS["posix_getuid"]=posix_getuid();
			$name=posix_getpwuid($GLOBALS["posix_getuid"]);
		}
		if($GLOBALS["DEBUG"]){
			$trace=debug_backtrace();
			if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
		}
		
		
		/*$trace=debug_backtrace();
		$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";	
		include_once(dirname(__FILE__)."/logs.inc");
		writelogs("Instanciate sockets() {$GLOBALS["posix_getuid"]}={$name["name"]} $called",__CLASS__.'/'.__FUNCTION__,__FILE__);
		*/
		if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("OK FOR THAT",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}

		$this->ArticaMetaEnabled=$this->APC_GET("ArticaMetaEnabled");
		$this->SetTimeZone();
		if(file_exists(dirname(__FILE__) . '/settings.inc')){@include(dirname(__FILE__) . '/settings.inc');}
		
		if($this->remote_ip==null){
				$this->remote_ip='127.0.0.1';
				$this->remote_port=$_GLOBAL["ARTICA_DAEMON_PORT"];
				if($_GLOBAL["ARTICA_SECOND_PORT"]>0){$this->remote_port=$_GLOBAL["ARTICA_SECOND_PORT"];}
				$this->APC_SAVE("ARTICA_DAEMON_IP",$this->remote_ip);
				$this->APC_SAVE("ARTICA_DAEMON_PORT",$this->remote_port);
			}
		
			$this->SquidPerformance=intval($this->GET_INFO("SquidPerformance"));
			$this->EnableIntelCeleron=intval($this->GET_INFO("EnableIntelCeleron"));
			
	}
		

	function file_ext($filename){
	        return strtolower(str_replace(".", "", strrchr($filename, ".")));
	}	
	
	public function DisableUfdbCat(){
		
		
		
		if(!is_file("/etc/artica-postfix/settings/Daemons/AsCategoriesAppliance")){@file_put_contents("/etc/artica-postfix/settings/Daemons/AsCategoriesAppliance", 0);}
		if(!is_file("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron")){@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron", 0);}
		$AsCategoriesAppliance=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/AsCategoriesAppliance"));
		$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
		if(is_file("/etc/artica-postfix/STATS_APPLIANCE")){$AsCategoriesAppliance=1;}
		
		if($EnableIntelCeleron==1){
			$GLOBALS["DisableUfdbCat"]=1;
			return $GLOBALS["DisableUfdbCat"];
		}
		
		if($AsCategoriesAppliance==1){
			if($GLOBALS["VERBOSE"]){echo "DisableUfdbCat:: AsCategoriesAppliance ENABLED !\n";}
			$GLOBALS["DisableUfdbCat"]=0;
			return $GLOBALS["DisableUfdbCat"];
		}
		
		
		
		
		
		$EnableLocalUfdbCatService=intval($this->GET_INFO("EnableLocalUfdbCatService"));
		
		$GLOBALS["CLASS_SOCKETS_DisableUfdbCat"]=array();
		$GLOBALS["CLASS_SOCKETS_DisableUfdbCat"][]="EnableLocalUfdbCatService = $EnableLocalUfdbCatService";
		
		if($EnableLocalUfdbCatService==1){
			$GLOBALS["CLASS_SOCKETS_DisableUfdbCat"][]="EnableLocalUfdbCatService: Return OK, service must running";
			if($GLOBALS["VERBOSE"]){echo "DisableUfdbCat:: EnableLocalUfdbCatService = $EnableLocalUfdbCatService ENABLED !\n";}
			$GLOBALS["DisableUfdbCat"]=0;
			return $GLOBALS["DisableUfdbCat"];
		}
		
		if($GLOBALS["AS_ROOT"]){
			if(!is_file("/etc/artica-postfix/settings/Daemons/SquidPerformance")){
				$GLOBALS["CLASS_SOCKETS_DisableUfdbCat"][]="SquidPerformance No such file return BAD service must be stopped";
				if($GLOBALS["VERBOSE"]){echo "SquidPerformance No such file return 1\n";}
				return 1; }
		}
		
		if(isset($GLOBALS["DisableUfdbCat"])){
			$GLOBALS["CLASS_SOCKETS_DisableUfdbCat"][]="Return DisableUfdbCat By memory";
			if($GLOBALS["VERBOSE"]){echo "Return DisableUfdbCat By memory\n";}
			return $GLOBALS["DisableUfdbCat"];
		}
		$RemoteUfdbCat=intval($this->GET_INFO("RemoteUfdbCat"));
		$SquidPerformance=intval($this->GET_INFO("SquidPerformance"));
		$DisableLocalStatisticsTasks=intval($this->GET_INFO("DisableLocalStatisticsTasks"));
		
		$GLOBALS["CLASS_SOCKETS_DisableUfdbCat"][]="RemoteUfdbCat = $RemoteUfdbCat";
		$GLOBALS["CLASS_SOCKETS_DisableUfdbCat"][]="SquidPerformance = $SquidPerformance";
		$GLOBALS["CLASS_SOCKETS_DisableUfdbCat"][]="DisableLocalStatisticsTasks = $DisableLocalStatisticsTasks";
		
		
		if($RemoteUfdbCat==1){
			$GLOBALS["CLASS_SOCKETS_DisableUfdbCat"][]="DisableUfdbCat:: RemoteUfdbCat = $RemoteUfdbCat -> Service must be disabled";
			if($GLOBALS["VERBOSE"]){echo "DisableUfdbCat:: RemoteUfdbCat = $RemoteUfdbCat -> Service must be disabled\n";}
			$GLOBALS["DisableUfdbCat"]=1;return $GLOBALS["DisableUfdbCat"];
		}
		

		if($GLOBALS["VERBOSE"]){echo "DisableUfdbCat:: SquidPerformance = $SquidPerformance\n";}
		if($SquidPerformance>0){
			$GLOBALS["CLASS_SOCKETS_DisableUfdbCat"][]="SquidPerformance > 0 ( $SquidPerformance) -> Service must be disabled";
			if($GLOBALS["VERBOSE"]){echo "DisableUfdbCat:: YES\n";}
			$GLOBALS["DisableUfdbCat"]=1;
			return $GLOBALS["DisableUfdbCat"];
		}
		if($DisableLocalStatisticsTasks==1){
			$GLOBALS["CLASS_SOCKETS_DisableUfdbCat"][]="DisableLocalStatisticsTasks > 0  -> Service must be disabled";
			if($GLOBALS["VERBOSE"]){echo "DisableUfdbCat:: DisableLocalStatisticsTasks = $DisableLocalStatisticsTasks\n";}
			$GLOBALS["DisableUfdbCat"]=1;
			return $GLOBALS["DisableUfdbCat"];
		}
		
		$SQUIDEnable=$this->GET_INFO("SQUIDEnable");
		if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
		
		if($SQUIDEnable==0){
			$GLOBALS["DisableUfdbCat"]=1;
			$GLOBALS["CLASS_SOCKETS_DisableUfdbCat"][]="SQUIDEnable == 0  -> Service must be disabled";
			if($GLOBALS["VERBOSE"]){echo "DisableUfdbCat:: SQUIDEnable = $SQUIDEnable\n";}
			return $GLOBALS["DisableUfdbCat"];
		}
		
		if($SquidPerformance==0){
			$GLOBALS["DisableUfdbCat"]=0;
			return $GLOBALS["DisableUfdbCat"];
		}
		
		
		$EnableUfdbCat=intval($this->GET_INFO("EnableUfdbCat"));
		if($EnableUfdbCat==0){$GLOBALS["DisableUfdbCat"]=1;}
		if($GLOBALS["VERBOSE"]){echo "DisableUfdbCat:: EnableUfdbCat = $EnableUfdbCat\n";}
		
		
		return $GLOBALS["DisableUfdbCat"];
		
		
	}
	
	
	public function IsKerbAuth() {
		$EnableKerbAuth=0;
		$time=date("YmdHm");
		if(!isset($_SESSION["EnableKerbAuth"][$time])){
			$sock=new sockets();
			$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
			if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
			unset($_SESSION["EnableKerbAuth"]);
			$_SESSION["EnableKerbAuth"][$time]=$EnableKerbAuth;
		}
	
		if(!isset($_SESSION["EnableSambaActiveDirectory"][$time])){
			$sock=new sockets();
			$EnableSambaActiveDirectory=$sock->GET_INFO("EnableSambaActiveDirectory");
			if(!is_numeric($EnableSambaActiveDirectory)){$EnableSambaActiveDirectory=0;}
			unset($_SESSION["EnableSambaActiveDirectory"]);
			$_SESSION["EnableSambaActiveDirectory"][$time]=$EnableSambaActiveDirectory;
		}
	
		if($_SESSION["EnableKerbAuth"][$time]==1){$EnableKerbAuth=1;}
		if($_SESSION["EnableSambaActiveDirectory"][$time]==1){$EnableKerbAuth=1;}
		return $EnableKerbAuth;
	
	}	
	
	
	public function SquidStatisticsEnabled(){
		if(is_file("/etc/artica-postfix/STATS_APPLIANCE")){return true;}
		$SquidPerformance=intval($this->GET_INFO("SquidPerformance"));
		if(!is_file("/etc/artica-postfix/settings/Daemons/EnableInfluxDB")){@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableInfluxDB", 1);}
		$EnableInfluxDB=intval($this->GET_INFO("EnableInfluxDB"));
		if($SquidPerformance>1){return false;}
		if($EnableInfluxDB==0){return false;}
		return true;
		
	}
	
	public function Bandwidthd_enabled(){
		$TempMemInstalled=0;
		if(!is_file("/etc/artica-postfix/settings/Daemons/EnableBandwidthd")){@file_put_contents("/etc/artica-postfix/settings/Daemons/EnableBandwidthd",0);}
		$EnableBandwidthd=@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableBandwidthd");
		$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
		if(is_file("/etc/artica-postfix/settings/Daemons/TempMemInstalled")){
			$TempMemInstalled=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/TempMemInstalled"));
		}
		
		
		if($EnableIntelCeleron==1){$EnableBandwidthd=0;}
		if($TempMemInstalled>0){
			if($TempMemInstalled<624288){$EnableBandwidthd=0;}
		}
		return $EnableBandwidthd;
		
	}
	
	
	public function EnableUfdbGuardArtica(){
		
		$EnableGoogleSafeBrowsing=intval($this->GET_INFO("EnableGoogleSafeBrowsing"));
		if($EnableGoogleSafeBrowsing==1){return 1;}
		
		$EnableUfdbGuard=$this->EnableUfdbGuard();
		if($EnableUfdbGuard==0){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: EnableUfdbGuard = 0 return FALSE\n";}
			return 0;}
		$UfdbUseArticaClient=$this->GET_INFO("UfdbUseArticaClient");
		if(!is_numeric($UfdbUseArticaClient)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: UfdbUseArticaClient = $UfdbUseArticaClient return TRUE\n";}
			return 1;}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: RETURN $UfdbUseArticaClient\n";}
		return $UfdbUseArticaClient;
		
	}
	
	public function EnableUfdbGuard_local(){
		$EnableUfdbGuard=$this->EnableUfdbGuard();
		$SquidUrgency=intval($this->GET_INFO("SquidUrgency"));
		$UseRemoteUfdbguardService=intval($this->GET_INFO("UseRemoteUfdbguardService"));
		if($UseRemoteUfdbguardService==1){return 0;}
		if($SquidUrgency==1){return 0;}
		return $EnableUfdbGuard;
	}

	public function EnableUfdbGuard($aslog=false){
		if(!$aslog){
			if(isset($GLOBALS["EnableUfdbGuard"])){return intval($GLOBALS["EnableUfdbGuard"]);}
		}
		$SQUIDEnable=1;
		$EnableUfdbGuard=intval(@file_get_contents('/etc/artica-postfix/settings/Daemons/EnableUfdbGuard'));
		$EnableUfdbGuard2=intval(@file_get_contents('/etc/artica-postfix/settings/Daemons/EnableUfdbGuard2'));
		$SquidUrgency=intval($this->GET_INFO("SquidUrgency"));
		$UseRemoteUfdbguardService=intval($this->GET_INFO("UseRemoteUfdbguardService"));
		if(is_file("/etc/artica-postfix/settings/Daemons/SQUIDEnable")){
			$SQUIDEnable=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SQUIDEnable"));
		}
		
		if(is_file("/etc/artica-postfix/settings/Daemons/SquidUFDBUrgency")){
			$SquidUFDBUrgency=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidUFDBUrgency"));
			
		}
		
		$isLog[]="UseRemoteUfdbguardService: $UseRemoteUfdbguardService";
		$isLog[]="EnableUfdbGuard: $EnableUfdbGuard";
		$isLog[]="EnableUfdbGuard2: $EnableUfdbGuard2";
		$isLog[]="SquidUrgency: $SquidUrgency";
		$isLog[]="SQUIDEnable: $SQUIDEnable";
		$isLog[]="SquidUFDBUrgency: $SquidUFDBUrgency";
		

		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: UseRemoteUfdbguardService: $UseRemoteUfdbguardService\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: EnableUfdbGuard..........: $EnableUfdbGuard\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: EnableUfdbGuard2.........: $EnableUfdbGuard2\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: SquidUrgency.............: $SquidUrgency\n";}
		
		if($UseRemoteUfdbguardService==1){return 1;}
		
		
		if($EnableUfdbGuard==0){
			if($EnableUfdbGuard2==1){
				$this->SET_INFO("EnableUfdbGuard", 1);
				$EnableUfdbGuard=1;
			}
		}
		if($SquidUrgency==1){
			$isLog[]="* * * * Your Proxy is on Emergency mode ! * * * *";
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: * * * * Your Proxy is on Emergency mode ! * * * *\n";}
			$EnableUfdbGuard=0;
		}
		
		if($SquidUFDBUrgency==1){
			$isLog[]="* * * * Your Proxy is on Web filtering Emergency mode ! * * * *";
			$EnableUfdbGuard=0;
		}
		
		if($SQUIDEnable==0){
			$isLog[]="* * * * Squid is disabled * * * *";
		}
		
		$GLOBALS["EnableUfdbGuard"]=$EnableUfdbGuard;
		if($aslog){return $isLog;}
		return intval($EnableUfdbGuard);
	}

	function send_email_events_notroot($subject,$text,$context){
		$array=array("SUBJECT"=>$subject,"TEXT"=>$text,"CONTEXT"=>$context);
		$final=base64_encode(serialize($array));
		$this->getFrameWork("services.php?send-email-events=$final");
	}

	public function SetTimeZone(){
		if(!function_exists("getLocalTimezone")){return;}
		if(isset($GLOBALS["TIMEZONES"])){
			@date_default_timezone_set($GLOBALS["TIMEZONES"]);
			return;
		}
	
		if(isset($_SESSION["TIMEZONES"])){
			@date_default_timezone_set($_SESSION["TIMEZONES"]);
			$GLOBALS["TIMEZONES"]=$_SESSION["TIMEZONES"];
			return;
				
		}
	
		if(isset($GLOBALS["GET_TIME_ZONE"])){
			$GLOBALS["TIMEZONES"]=$GLOBALS["GET_TIME_ZONE"];
			$_SESSION["TIMEZONES"]=$GLOBALS["GET_TIME_ZONE"];
			@date_default_timezone_set($GLOBALS["TIMEZONES"]);
			return;
		}
	
		
		$timezones=$this->GET_INFO("timezones");
		if($timezones==null){$timezones="Europe/Dublin";}
		$GLOBALS["TIMEZONES"]=$timezones;
		$_SESSION["TIMEZONES"]=$timezones;
		@date_default_timezone_set($GLOBALS["TIMEZONES"]);
	}
	
	
	public function dnsmasq_enabled(){
		if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
		$EnableLocalDNSMASQ=intval(@file_get_contents('/etc/artica-postfix/settings/Daemons/EnableLocalDNSMASQ'));
		if($EnableLocalDNSMASQ==1){return 1;}
		return intval($this->GET_INFO("EnableDNSMASQ"));
		
	}	

public function SQUID_LOCAL_STATS_DISABLED(){
	$SquidPerformance=intval($this->GET_INFO("SquidPerformance"));
	$DisableLocalStatisticsTasks=intval($this->GET_INFO("DisableLocalStatisticsTasks"));
	
	$stats_appliance=new stats_appliance();
	if($stats_appliance->IsConnected()){return true;}
	
	$EnableRemoteSyslogStatsAppliance=$this->GET_INFO("EnableRemoteSyslogStatsAppliance");
	$squidEnableRemoteStatistics=$this->GET_INFO("squidEnableRemoteStatistics");
	$EnableRemoteStatisticsAppliance=$this->GET_INFO("EnableRemoteStatisticsAppliance");
	
	
	if(!is_numeric($EnableRemoteSyslogStatsAppliance)){$EnableRemoteSyslogStatsAppliance=0;}
	
	if(!is_numeric($squidEnableRemoteStatistics)){$squidEnableRemoteStatistics=0;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	
	if($SquidPerformance>1){$DisableLocalStatisticsTasks=1;}
	

	
	if($GLOBALS["VERBOSE"]){"SQUID_LOCAL_STATS_DISABLED():squidEnableRemoteStatistics....: $squidEnableRemoteStatistics\n";}
	if($GLOBALS["VERBOSE"]){"SQUID_LOCAL_STATS_DISABLED():EnableRemoteStatisticsAppliance: $EnableRemoteStatisticsAppliance\n";}
	
	if($GLOBALS["VERBOSE"]){"SQUID_LOCAL_STATS_DISABLED():EnableRemoteSyslogStatsAppliance: $EnableRemoteSyslogStatsAppliance\n";}
	if($GLOBALS["VERBOSE"]){"SQUID_LOCAL_STATS_DISABLED():DisableLocalStatisticsTasks.....: $DisableLocalStatisticsTasks\n";}
	
	if($squidEnableRemoteStatistics==1){return true;}
	if($EnableRemoteStatisticsAppliance==1){return true;}
	
	if($EnableRemoteSyslogStatsAppliance==1){return true;}
	if($DisableLocalStatisticsTasks==1){return true;}
	
}

public function shellEscapeChars($path){
	$path=str_replace(" ","\ ",$path);
	$path=str_replace('$','\$',$path);
	$path=str_replace("&","\&",$path);
	$path=str_replace("?","\?",$path);
	$path=str_replace("#","\#",$path);
	$path=str_replace("[","\[",$path);
	$path=str_replace("]","\]",$path);
	$path=str_replace("{","\{",$path);
	$path=str_replace("}","\}",$path);
	$path=str_replace("*","\*",$path);
	$path=str_replace('"','\\"',$path);
	$path=str_replace("'","\\'",$path);
	$path=str_replace("(","\(",$path);
	$path=str_replace(")","\)",$path);
	$path=str_replace("<","\<",$path);
	$path=str_replace(">","\>",$path);
	$path=str_replace("!","\!",$path);
	$path=str_replace("+","\+",$path);
	$path=str_replace(";","\;",$path);
	return $path;
}


public function SQUID_DISABLE_STATS_DIE(){
	if($this->SQUID_LOCAL_STATS_DISABLED()){
		if(function_exists("debug_backtrace")){
			$trace=debug_backtrace();
			if(isset($trace[1])){
				$file=basename($trace[1]["file"]);
				$function=$trace[1]["function"];
				$line=$trace[1]["line"];
				
			}
				
		}
		stats_admin_events(2,"$file/$function (line: $line) : Statsitics are disabled, shutdown the script" ,null,__FILE__,__LINE__);
		die();}
}
		
function download_attach($folder,$filename){
	$url_stuff="http://127.0.0.1:$this->remote_port/queue/bightml/$folder/$filename";
	$fileext=$this->file_ext($filename);
	
	$filemime=file_get_contents(dirname(__FILE__) . '/databases/extentions-mime.db');
	$tb=explode("\n",$filemime);
	while (list ($num, $ligne) = each ($tb) ){
		if(preg_match("#$fileext\s+(.+)#i",$ligne,$re)){
			$mime=$re[1];
		}
	}
	if($mime==null){$mime="application/octet-stream";}
	header("Content-type: $mime");
	header('Content-Disposition: attachment; filename="'.$filename.'"');
	$binary_data = file_get_contents($url_stuff);
	echo $binary_data;
	
}

function download_srvfile($path){
	include(dirname(__FILE__) . '/settings.inc');
	$md=md5($_GLOBAL["ldap_password"].$_GLOBAL["ldap_root_database"]);
	$url_stuff="http://127.0.0.1:$this->remote_port/$md$path";
	$pathbase=basename($path);
	$fileext=$this->file_ext($pathbase);
	
	$filemime=file_get_contents(dirname(__FILE__) . '/databases/extentions-mime.db');
	$tb=explode("\n",$filemime);
	while (list ($num, $ligne) = each ($tb) ){
		if(preg_match("#$fileext\s+(.+)#i",$ligne,$re)){
			$mime=$re[1];
		}
	}
	if($mime==null){$mime="application/octet-stream";}
	header("Content-type: $mime");
	header('Content-Disposition: attachment; filename="'.$pathbase.'"');
	$binary_data = file_get_contents($url_stuff);
	if(function_exists("writelogs")){writelogs("$url_stuff ->$mime (".strlen($binary_data).')',__CLASS__.'/'.__FUNCTION__,__FILE__);}
	echo $binary_data;
	
}
		
function downloadFile($url,$hostname)  {
   $url_stuff="http://$hostname:$this->remote_port/$url";
   header("Content-type: image/png");
   $binary_data=@file_get_contents($url_stuff);
   if(strlen($binary_data)==0){
   	$binary_data=@file_get_contents("/usr/share/artica-postfix/img/kas-graph-no-datas.png");
   }
   
   
   echo $binary_data;
}

   function SaveConfigFile($datas,$keyConfig){
   	if(!function_exists("writelogs")){include(dirname(__FILE__)."/logs.inc");}
   	
   	if(!is_file("/etc/artica-postfix/settings/Daemons/$keyConfig")){@touch("/etc/artica-postfix/settings/Daemons/$keyConfig");}
   	
   	if(is_writable("/etc/artica-postfix/settings/Daemons/$keyConfig")){
   		@file_put_contents("/etc/artica-postfix/settings/Daemons/$keyConfig",$datas);
   		if($GLOBALS["AS_ROOT"]){@chmod("/etc/artica-postfix/settings/Daemons/$keyConfig", 0755);}
   		return;
   }
   	
   	if($GLOBALS["AS_ROOT"]){
   		@mkdir("/etc/artica-postfix/settings/Daemons",0755,true);
   		@file_put_contents("/etc/artica-postfix/settings/Daemons/$keyConfig",$datas);
   		@chmod("/etc/artica-postfix/settings/Daemons/$keyConfig", 0755);
   		return;
   	}
   	
   	
   	 $tmp=md5($datas.$keyConfig);
   	 if(!$this->WriteConfigToFile($tmp,$datas)){
   	 	if(function_exists("writelogs")){writelogs("Writing $tmp ". strlen($tmp)." bytes FAILED",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
   	 	echo "Unable to save $tmp";
   	 	return false;
   	 }
   	 $this->getFrameWork("cmd.php?SaveConfigFile=$tmp&key=$keyConfig");
   	 $this->SET_APC_STORE($keyConfig,$datas);
   		if($this->ArticaMetaEnabled==1){
   			$this->getFrameWork("cmd.php?artica-meta-push=$keyConfig");
   		}
   	 
   	 $this->DeleteCache();
   	 return true;
   }
   
   function SaveClusterConfigFile($datas,$keyConfig){
   	 $tmp=md5($datas.$keyConfig);
   	 if(!$this->WriteConfigToFile($tmp,$datas)){
   	 	echo "Unable to save $tmp";
   	 	return false;
   	 }
   	 $this->getFrameWork("cmd.php?SaveClusterConfigFile=$tmp&key=$keyConfig");
   	 $this->DeleteCache();
   	 return true;
   }   
   
   function SaveDansGuardianFile($datas,$filename){
   		$tmp=md5($datas.$filename);
   	 	if(!$this->WriteConfigToFile($tmp,$datas)){
   	 		echo "Unable to save $tmp";
   	 		return false;
   	 	}  

   	 	$this->getfile("SaveDansGuardianFile:$tmp;$filename");
   	
   }
   
   
   function WriteConfigToFile($myFile,$stringData){
   	if(!function_exists("writelogs")){include(dirname(__FILE__)."/logs.inc");}
   	if(!is_dir(dirname(__FILE__).'/conf')){mkdir(dirname(__FILE__).'/conf');}
   	if(!is_dir(dirname(__FILE__).'/conf')){
   		echo "Unable to create dir ".dirname(__FILE__).'/conf'."\n";
   		return false;
   	}
   	
   	$myFile=dirname(__FILE__).'/conf/'.$myFile;
   	@file_put_contents($myFile,$stringData);
   	
   	if(!is_file($myFile)){
   		$this->getFrameWork("services.php?chown-medir=".base64_encode(dirname(__FILE__).'/conf'));
   		@file_put_contents($myFile,$stringData);
   	}
   	
   	if(!is_file($myFile)){
   		if(function_exists("writelogs")){
		writelogs("Writing $myFile ". strlen($stringData)." bytes failed",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		}
   		return false;
   	}
   	
   	
   	chmod($myFile,0755);
	//if(function_exists("writelogs")){
	//	writelogs("Writing $myFile ". strlen($stringData)." bytes ok",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
	//}
	return true;
   	
   }
   
  private function MEMCACHE_ENABLED(){
   		if(isset($GLOBALS["MEMCACHE_ENABLED"])){return $GLOBALS["MEMCACHE_ENABLED"];}
   		if(!class_exists("Memcached")){$GLOBALS["MEMCACHE_ENABLED"]=false;return false;}
		if(!$this->_is_socket("/var/run/memcached.sock")){$GLOBALS["MEMCACHE_ENABLED"]=false;return false;}
		$GLOBALS["MEMCACHE_ENABLED"]=true;return true;
		
   }
   
   private function _alt_stat($file) {
   	$ss=@stat($file);
   	if(!$ss) return false;
   	$ts=array( 0140000=>'ssocket', 0120000=>'llink', 0100000=>'-file', 0060000=>'bblock', 0040000=>'ddir', 0020000=>'cchar', 0010000=>'pfifo');
   	$p=$ss['mode'];
   	$t=decoct($ss['mode'] & 0170000); // File Encoding Bit
   	$str =(array_key_exists(octdec($t),$ts))?$ts[octdec($t)]{0}:'u';
   	$str.=(($p&0x0100)?'r':'-').(($p&0x0080)?'w':'-');
   	$str.=(($p&0x0040)?(($p&0x0800)?'s':'x'):(($p&0x0800)?'S':'-'));
   	$str.=(($p&0x0020)?'r':'-').(($p&0x0010)?'w':'-');
   	$str.=(($p&0x0008)?(($p&0x0400)?'s':'x'):(($p&0x0400)?'S':'-'));
   	$str.=(($p&0x0004)?'r':'-').(($p&0x0002)?'w':'-');
   	$str.=(($p&0x0001)?(($p&0x0200)?'t':'x'):(($p&0x0200)?'T':'-'));
   	$s=array(	'filetype'=>array( 'type'=>substr($ts[octdec($t)],1)));
   	return $s;
   }
   
   private function _is_socket($socketpath){
   	if(isset($GLOBALS["_is_socket"][$socketpath])){return $GLOBALS["_is_socket"][$socketpath];}
   	$stat=$this->_alt_stat($socketpath);
   	$type=$stat["filetype"]["type"];
   	if($type=="socket"){
   		$GLOBALS["_is_socket"][$socketpath]=true;
   		return true;
   	}
   	$GLOBALS["_is_socket"][$socketpath]=false;
   	return false;
   }   
   
   
   function MEMCACHE_GET($key){
   	if(!isset($GLOBALS["MEMCACHE_ENABLED"])){$this->MEMCACHE_ENABLED();}
   	if(!$GLOBALS["MEMCACHE_ENABLED"]){return;}
	$memcache = new Memcached();
	$memcache->addServer('unix:///var/run/memcached.sock', 0);
	$ARRAY=unserialize($memcache->get('ARTICA_INFOS'));
	if(isset($ARRAY[$key])){return trim($ARRAY[$key]);}
	return;

   	
   }
   function TOP_NOTIFY($content,$prio="warn"){
   		$notify=unserialize(base64_decode($this->GET_INFO("TOP_NOTIFY")));
   		$notify[]=array("TIME"=>time(),"CONTENT"=>$content,"PRIO"=>$prio);
   		$newArray=base64_encode(serialize($notify));
   		$this->SaveConfigFile($newArray, "TOP_NOTIFY");
   	
   }
   
   
   
   function EnableClamavDaemon(){
	   	$EnableClamavDaemon=$this->GET_INFO("EnableClamavDaemon");
	   	$EnableClamavDaemonForced=$this->GET_INFO("EnableClamavDaemonForced");
	   	$CicapEnabled=$this->GET_INFO("CicapEnabled");
	   	$SQUIDEnable=$this->GET_INFO("SQUIDEnable");
	   	$ClamavMilterEnabled=intval($this->GET_INFO("ClamavMilterEnabled"));
	   	$MimeDefangClamav=intval($this->GET_INFO("MimeDefangClamav"));
	   	$MimeDefangEnabled=intval($this->GET_INFO("MimeDefangEnabled"));
	   	
	   	if($MimeDefangEnabled==0){$MimeDefangClamav=0;}
	   	if(!is_numeric($EnableClamavDaemon)){$EnableClamavDaemon=0;}
	   	if(!is_numeric($EnableClamavDaemonForced)){$EnableClamavDaemonForced=0;}
	   	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	   	if(!is_numeric($CicapEnabled)){$CicapEnabled=0;}
	   	if($SQUIDEnable==1){if($CicapEnabled==1){$EnableClamavDaemon=1;}}
	   	if($ClamavMilterEnabled==1){$EnableClamavDaemon=1;}
	   	if($EnableClamavDaemonForced==1){$EnableClamavDaemon=1;}
	   	if($MimeDefangClamav==1){$EnableClamavDaemon=1;}
	   	return $EnableClamavDaemon;
   }
   
   
   private function BuildTemplatesConfig_find_rule($ARRAY){
   	
   	if($GLOBALS["VERBOSE"]){echo "<span style='color:yellow'>BuildTemplatesConfig_find_rule...</span>\n<br>";}
   	
   	$q=new mysql_squid_builder();
   	if(!$q->TABLE_EXISTS("ufdb_design")){
   		
   		if($GLOBALS["VERBOSE"]){echo "<span style='color:yellow'>ufdb_design NO SUCH TABLE!</span>\n<br>";}
   		return;
   	}
   	$ruleid=$ARRAY["RULE_ID"];
   	$category=$ARRAY["CATEGORY_KEY"];
   	if(isset($_SESSION["UfdbGuardHTTP"][$ruleid][$category])){
   		if($GLOBALS["VERBOSE"]){echo "<span style='color:yellow'>TEMPLATE MATCH -> MEMORY SESSION $category/$ruleid</span>\n<br>";}
   		return $_SESSION["UfdbGuardHTTP"][$ruleid][$category];
   	}
   	
   	$sql="SELECT zmd5 FROM ufdb_design WHERE category='$category' AND ruleid='$ruleid'";
   	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
   	if($ligne["zmd5"]<>null){
   		if($GLOBALS["VERBOSE"]){echo "<span style='color:yellow'>NO TEMPLATE MATCH $category/$ruleid</span>\n<br>";}
   		$_SESSION["UfdbGuardHTTP"][$ruleid][$category]=$ligne["zmd5"];
   		return $ligne["zmd5"];
   	}
   	
   	if($GLOBALS["VERBOSE"]){echo "<span style='color:yellow'>NO TEMPLATE MATCH $category/$ruleid</span>\n<br>";}
   	
   	$sql="SELECT * FROM ufdb_design WHERE category='*' AND ruleid='$ruleid'";
   	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
   	if($ligne["zmd5"]<>null){
   		$_SESSION["UfdbGuardHTTP"][$ruleid][$category]=$ligne["zmd5"];
   		return $ligne["zmd5"];
   	}
   	if($GLOBALS["VERBOSE"]){echo "<span style='color:yellow'>NO TEMPLATE MATCH */$ruleid</span>\n<br>";}
   	
   	$sql="SELECT * FROM ufdb_design WHERE category='$category' AND ruleid='0'";
   	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
   	if($ligne["zmd5"]<>null){
   		$_SESSION["UfdbGuardHTTP"][$ruleid][$category]=$ligne["zmd5"];
   		return $ligne["zmd5"];
   	}  	
   	if($GLOBALS["VERBOSE"]){echo "<span style='color:yellow'>NO TEMPLATE MATCH $category/0</span>\n<br>";}
   	
   	$sql="SELECT * FROM ufdb_design WHERE category='*' AND ruleid='0'";
   	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
   	if($ligne["zmd5"]<>null){
   		$_SESSION["UfdbGuardHTTP"][$ruleid][$category]=$ligne["zmd5"];
   		return $ligne["zmd5"];
   	}
   	if($GLOBALS["VERBOSE"]){echo "<span style='color:yellow'>NO TEMPLATE MATCH */0</span>\n<br>";}
   	
   	
   	$sql="SELECT * FROM ufdb_design WHERE category='' AND ruleid='0'";
   	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
   	if($ligne["zmd5"]<>null){
   		$_SESSION["UfdbGuardHTTP"][$ruleid][$category]=$ligne["zmd5"];
   		return $ligne["zmd5"];
   	}
   	if($GLOBALS["VERBOSE"]){echo "<span style='color:yellow'>NO TEMPLATE MATCH NULL/0</span>\n<br>";}
   	   	
   	
   	return null;
   	
   	
   	
   }
   
   private function BuildTemplatesConfig_from_mysql($ARRAY){
   		if(!$GLOBALS["CORP_LICENSE"]){
   			if($GLOBALS["VERBOSE"]){echo "<h1 style='color:yellow'>NO LICENSE!\n<br></H1>";}
   			return;}
   		if(!class_exists("mysql_squid_builder")){include_once(dirname(__FILE__)."/class.mysql.squid.builder.php");}
   		$md5=$this->BuildTemplatesConfig_find_rule($ARRAY);
		if($md5==null){
			if($GLOBALS["VERBOSE"]){echo "<span style='color:yellow'>NOT TEMPLATE MATCH $md5</span>\n<br>";}
			return;}
   		
		if($GLOBALS["VERBOSE"]){echo "<span style='color:yellow'>TEMPLATE MATCH $md5</span>\n<br>";}
		
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM ufdb_design WHERE zmd5='$md5'";
		if($GLOBALS["VERBOSE"]){echo "<span style='color:yellow'>$sql</span>\n<br>";}
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
   		
		$GLOBALS["UfdbGuardHTTP"]["AllowUnblock"]=intval($this->GET_INFO("UfdbGuardHTTPAllowUnblock"));
		$SquidHTTPTemplateLogoPath=$ligne["SquidHTTPTemplateLogoPath"];
		$SquidHTTPTemplateLogoEnable=intval($ligne["SquidHTTPTemplateLogoEnable"]);
		$SquidHTTPTemplateLogoPositionH=$ligne["SquidHTTPTemplateLogoPositionH"];
		$SquidHTTPTemplateLogoPositionL=$ligne["SquidHTTPTemplateLogoPositionL"];
		$SquidHTTPTemplateSmiley=intval($ligne["SquidHTTPTemplateSmiley"]);
		$SquidHTTPTemplateSmileyEnable=intval($ligne["SquidHTTPTemplateSmileyEnable"]);
		if($SquidHTTPTemplateSmiley==0){$SquidHTTPTemplateSmiley=2639;}
		$picturealign=$ligne["picturealign"];
		
		$UfdbGuardHTTPNoVersion=$ligne["UfdbGuardHTTPNoVersion"];
		
		if($GLOBALS["VERBOSE"]){echo "<div style='background-color:white'>";}
		if($GLOBALS["VERBOSE"]){echo "<li style='color:black'>".__CLASS__."/".__LINE__.":UfdbGuardHTTPNoVersion: $UfdbGuardHTTPNoVersion</li>";}
		if($GLOBALS["VERBOSE"]){echo "</div>";}
		
		
		$UfdbGuardHTTPBackgroundColor=$ligne["UfdbGuardHTTPBackgroundColor"];
		$UfdbGuardHTTPFamily=$ligne["UfdbGuardHTTPFamily"];
		$UfdbGuardHTTPFontColor=$ligne["UfdbGuardHTTPFontColor"];
		$UFDBGUARD_TITLE_1=$ligne["UFDBGUARD_TITLE_1"];
		$UFDBGUARD_PARA1=$ligne["UFDBGUARD_PARA1"];
		$UFDBGUARD_TITLE_2=$ligne["UFDBGUARD_TITLE_2"];
		$UFDBGUARD_PARA2=$ligne["UFDBGUARD_PARA2"];
		$UfdbGuardHTTPEnablePostmaster=$ligne["UfdbGuardHTTPEnablePostmaster"];
		
		$UFDBGUARD_UNLOCK_LINK=$ligne["UFDBGUARD_UNLOCK_LINK"];
		$UFDBGUARD_TICKET_LINK=$ligne["UFDBGUARD_TICKET_LINK"];
		
		$UfdbGuardHTTPEnablePostmaster=$ligne["UfdbGuardHTTPEnablePostmaster"];
		$UfdbGuardHTTPBackgroundColorBLK=$ligne["UfdbGuardHTTPBackgroundColorBLK"];
		
		if($GLOBALS["VERBOSE"]){echo "<h1 style='color:yellow'>UfdbGuardHTTPBackgroundColorBLK = $UfdbGuardHTTPBackgroundColorBLK\n<br></H1>";}
		
		$UfdbGuardHTTPBackgroundColorBLKBT=$ligne["UfdbGuardHTTPBackgroundColorBLKBT"];
		$UfdbGuardHTTPDisableHostname=intval($ligne["UfdbGuardHTTPDisableHostname"]);
		
		if($UFDBGUARD_TITLE_1==null){$UFDBGUARD_TITLE_1="{UFDBGUARD_TITLE_1}";}
		if($UFDBGUARD_PARA1==null){$UFDBGUARD_PARA1="{UFDBGUARD_PARA1}";}
		if($UFDBGUARD_PARA2==null){$UFDBGUARD_PARA2="{UFDBGUARD_PARA2}";}
		if($UFDBGUARD_TITLE_2==null){$UFDBGUARD_TITLE_2="{UFDBGUARD_TITLE_2}";}
		
		
		if(!is_numeric($UfdbGuardHTTPEnablePostmaster)){$UfdbGuardHTTPEnablePostmaster=1;}
		if($UfdbGuardHTTPBackgroundColor==null){$UfdbGuardHTTPBackgroundColor="#8c1919";}
		if($UfdbGuardHTTPBackgroundColorBLK==null){$UfdbGuardHTTPBackgroundColorBLK="#0300AC";}
		if($UfdbGuardHTTPBackgroundColorBLKBT==null){$UfdbGuardHTTPBackgroundColorBLKBT="#625FFD";}
		if($UfdbGuardHTTPFontColor==null){$UfdbGuardHTTPFontColor="#FFFFFF";}
		
		if(substr($UfdbGuardHTTPFontColor,0 ,1)<>"#"){$UfdbGuardHTTPFontColor="#$UfdbGuardHTTPFontColor";}
		if(substr($UfdbGuardHTTPBackgroundColor,0 ,1)<>"#"){$UfdbGuardHTTPBackgroundColor="#$UfdbGuardHTTPBackgroundColor";}
		if(substr($UfdbGuardHTTPBackgroundColorBLK,0 ,1)<>"#"){$UfdbGuardHTTPBackgroundColorBLK="#$UfdbGuardHTTPBackgroundColorBLK";}
		if(substr($UfdbGuardHTTPBackgroundColorBLKBT,0 ,1)<>"#"){$UfdbGuardHTTPBackgroundColorBLKBT="#$UfdbGuardHTTPBackgroundColorBLKBT";}
		
		if($UfdbGuardHTTPFamily==null){$UfdbGuardHTTPFamily="Calibri, Candara, Segoe, \"Segoe UI\", Optima, Arial, sans-serif";}
		
		
		
		if(!is_numeric($SquidHTTPTemplateLogoPositionH)){$SquidHTTPTemplateLogoPositionH=10;}
		if(!is_numeric($SquidHTTPTemplateLogoPositionL)){$SquidHTTPTemplateLogoPositionL=10;}
		
		if(!is_numeric($SquidHTTPTemplateSmiley)){$SquidHTTPTemplateSmiley=2639;}
		
		
		
		$GLOBALS["UfdbGuardHTTP"]["picturename"]=$ligne["picturename"];
		$GLOBALS["UfdbGuardHTTP"]["picturemode"]=$ligne["picturemode"];
		$GLOBALS["UfdbGuardHTTP"]["picturealign"]=$ligne["picturealign"];
		$GLOBALS["UfdbGuardHTTP"]["UfdbGuardHTTPDisableHostname"]=$UfdbGuardHTTPDisableHostname;
		
		
		
		$GLOBALS["UfdbGuardHTTP"]["Family"]=$UfdbGuardHTTPFamily;
		$GLOBALS["UfdbGuardHTTP"]["md5"]=$md5;
		$GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateLogoEnable"]=$SquidHTTPTemplateLogoEnable;
		$GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateLogoPositionH"]=$SquidHTTPTemplateLogoPositionH;
		$GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateLogoPositionL"]=$SquidHTTPTemplateLogoPositionL;
		
		if(!is_file("/usr/share/artica-postfix/ressources/logs/web/$md5{$ligne["picturename"]}")){
			@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/$md5{$ligne["picturename"]}", $ligne["picture"]);
			
		}
		$GLOBALS["UfdbGuardHTTP"]["picture_path"]="ressources/logs/web/$md5{$ligne["picturename"]}";
		$GLOBALS["UfdbGuardHTTP"]["NoVersion"]=$UfdbGuardHTTPNoVersion;
		
		if($GLOBALS["VERBOSE"]){echo "<div style='background-color:white'>";}
		if($GLOBALS["VERBOSE"]){echo "<li style='color:black'>".__CLASS__."/".__LINE__.":UfdbGuardHTTPNoVersion: {$GLOBALS["UfdbGuardHTTP"]["NoVersion"]}</li>";}
		if($GLOBALS["VERBOSE"]){echo "<li style='color:black'>".__CLASS__."/".__LINE__.":SquidHTTPTemplateSmileyEnable: $SquidHTTPTemplateSmileyEnable / $SquidHTTPTemplateSmiley</li>";}
		if($GLOBALS["VERBOSE"]){echo "</div>";}
		
		
		$GLOBALS["UfdbGuardHTTP"]["BackgroundColor"]=$UfdbGuardHTTPBackgroundColor;
		$GLOBALS["UfdbGuardHTTP"]["EnablePostmaster"]=$UfdbGuardHTTPEnablePostmaster;
		$GLOBALS["UfdbGuardHTTP"]["FontColor"]=$UfdbGuardHTTPFontColor;
		$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TITLE_1"]=$UFDBGUARD_TITLE_1;
		$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_PARA1"]=$UFDBGUARD_PARA1;
		$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TITLE_2"]=$UFDBGUARD_TITLE_2;
		$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_PARA2"]=$UFDBGUARD_PARA2;
		$GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLK"]=$UfdbGuardHTTPBackgroundColorBLK;
		$GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLKBT"]=$UfdbGuardHTTPBackgroundColorBLKBT;
		$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_UNLOCK_LINK"]=$UFDBGUARD_UNLOCK_LINK;
		$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TICKET_LINK"]=$UFDBGUARD_TICKET_LINK;
		$GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateSmiley"]=$SquidHTTPTemplateSmiley;
		$GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateSmileyEnable"]=$SquidHTTPTemplateSmileyEnable;
		
		
		
		
		
		
		
   		
   }
   
   private function BuildTemplatesConfig_defaults(){
   	$GLOBALS["UfdbGuardHTTP"]["FOOTER"]=null;
   	$GLOBALS["UfdbGuardHTTP"]["UnbblockMaxTime"]=30;
   	if(!isset($GLOBALS["UfdbGuardHTTP"]["NoVersion"])){$GLOBALS["UfdbGuardHTTP"]["NoVersion"]=0;}
   	if(!isset($GLOBALS["UfdbGuardHTTP"]["BackgroundColor"])){$GLOBALS["UfdbGuardHTTP"]["BackgroundColor"]=null;}
   	if(!isset($GLOBALS["UfdbGuardHTTP"]["FontColor"])){$GLOBALS["UfdbGuardHTTP"]["FontColor"]=null;}
   	if(!isset($GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLK"])){$GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLK"]=null;}
   	if(!isset($GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLKBT"])){$GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLKBT"]=null;}
   	if(!isset($GLOBALS["UfdbGuardHTTP"]["EnablePostmaster"])){$GLOBALS["UfdbGuardHTTP"]["EnablePostmaster"]=1;}
   	if(!isset($GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TITLE_1"])){$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TITLE_1"]=null;}
   	if(!isset($GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TITLE_2"])){$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TITLE_2"]=null;}
   	if(!isset($GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_PARA1"])){$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_PARA1"]=null;}
   	if(!isset($GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_PARA2"])){$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_PARA2"]=null;}
   	if(!isset($GLOBALS["UfdbGuardHTTP"]["Family"])){$GLOBALS["UfdbGuardHTTP"]["Family"]=null;}
   	if(!isset($GLOBALS["UfdbGuardHTTP"]["AllowUnblock"])){$GLOBALS["UfdbGuardHTTP"]["AllowUnblock"]=intval($this->GET_INFO("UfdbGuardHTTPAllowUnblock"));;}
   	if(!isset($GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_UNLOCK_LINK"])){$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_UNLOCK_LINK"]=null;}
   	
   	
   	if(!is_numeric($GLOBALS["UfdbGuardHTTP"]["NoVersion"])){$GLOBALS["UfdbGuardHTTP"]["NoVersion"]=0;}
   	
   	if($GLOBALS["UfdbGuardHTTP"]["BackgroundColor"]==null){
   		if($GLOBALS["CORP_LICENSE"]){$GLOBALS["UfdbGuardHTTP"]["BackgroundColor"]=$this->GET_INFO("UfdbGuardHTTPBackgroundColor");}
   	}
   	
   	if(!isset($GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateLogoEnable"])){
   		$GLOBALS["UfdbGuardHTTP"]["SquidHTTPTemplateLogoEnable"]=0;
   	}
   	
   	
   	if($GLOBALS["UfdbGuardHTTP"]["Family"]==null){
   		$GLOBALS["UfdbGuardHTTP"]["Family"]="Calibri, Candara, Segoe, \"Segoe UI\", Optima, Arial, sans-serif";
   		
   	}
   	if(	$GLOBALS["UfdbGuardHTTP"]["FontColor"]==null){
   		if($GLOBALS["CORP_LICENSE"]){	$GLOBALS["UfdbGuardHTTP"]["FontColor"]=$this->GET_INFO("UfdbGuardHTTPFontColor");}
   	}
   	if(!is_numeric($GLOBALS["UfdbGuardHTTP"]["EnablePostmaster"])){
   		if($GLOBALS["CORP_LICENSE"]){	$GLOBALS["UfdbGuardHTTP"]["EnablePostmaster"]=$this->GET_INFO("UfdbGuardHTTPEnablePostmaster");}
   	}
   	
   	
   	if(!is_numeric($GLOBALS["UfdbGuardHTTP"]["NoVersion"])){
   		if($GLOBALS["CORP_LICENSE"]){	$GLOBALS["UfdbGuardHTTP"]["NoVersion"]=$this->GET_INFO("UfdbGuardHTTPNoVersion");}
   	}
   	   	
   	if($GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLK"]==null){
   		if($GLOBALS["CORP_LICENSE"]){	$GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLK"]=$this->GET_INFO("UfdbGuardHTTPBackgroundColorBLK");}
   	}
   	
   	if($GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLKBT"]==null){
   		if($GLOBALS["CORP_LICENSE"]){$GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLKBT"]=$this->GET_INFO("UfdbGuardHTTPBackgroundColorBLKBT");}
   	}
   	
   	
   	if($GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TITLE_1"]==null){
   		if($GLOBALS["CORP_LICENSE"]){$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TITLE_1"]=$this->GET_INFO("UFDBGUARD_TITLE_1");}
   	}
   	 
   	if($GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TITLE_2"]==null){
   		if($GLOBALS["CORP_LICENSE"]){$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TITLE_2"]=$this->GET_INFO("UFDBGUARD_TITLE_2");}
   	}
   	 
   	if($GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_PARA1"]==null){
   		if($GLOBALS["CORP_LICENSE"]){$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_PARA1"]=$this->GET_INFO("UFDBGUARD_PARA1");}
   		
   	}
   	
   	if($GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_PARA2"]==null){
   		if($GLOBALS["CORP_LICENSE"]){$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_PARA2"]=$this->GET_INFO("UFDBGUARD_PARA2");}
   	} 
   	
   	if(!is_numeric($GLOBALS["UfdbGuardHTTP"]["AllowUnblock"])){
   		$GLOBALS["UfdbGuardHTTP"]["AllowUnblock"]=intval($this->GET_INFO("UfdbGuardHTTPAllowUnblock"));
   	}
   	
   	if($GLOBALS["UfdbGuardHTTP"]["AllowUnblock"]==1){
   		$GLOBALS["UfdbGuardHTTP"]["UnbblockText1"]=$this->GET_INFO("UfdbGuardHTTPUnbblockText1");
   		$GLOBALS["UfdbGuardHTTP"]["UnbblockText2"]=$this->GET_INFO("UfdbGuardHTTPUnbblockText2");
   		$GLOBALS["UfdbGuardHTTP"]["UnbblockMaxTime"]=intval($this->GET_INFO("UfdbGuardHTTPUnbblockMaxTime"));
   		if($GLOBALS["UfdbGuardHTTP"]["UnbblockMaxTime"]<5){$GLOBALS["UfdbGuardHTTP"]["UnbblockMaxTime"]=30;}
   	}
   	
   	// DEF DEF
   		if(!is_numeric($GLOBALS["UfdbGuardHTTP"]["EnablePostmaster"])){$GLOBALS["UfdbGuardHTTP"]["EnablePostmaster"]=1;}
   		if(!is_numeric($GLOBALS["UfdbGuardHTTP"]["NoVersion"])){$GLOBALS["UfdbGuardHTTP"]["NoVersion"]=0;}
   		if($GLOBALS["UfdbGuardHTTP"]["BackgroundColor"]==null){$GLOBALS["UfdbGuardHTTP"]["BackgroundColor"]="#8c1919";}
   		if($GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TITLE_1"]==null){$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TITLE_1"]="{UFDBGUARD_TITLE_1}";}
   		if($GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TITLE_2"]==null){$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_TITLE_2"]="{UFDBGUARD_TITLE_2}";}
   		if($GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_PARA1"]==null){$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_PARA1"]="{UFDBGUARD_PARA1}";}
   		if($GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_PARA2"]==null){$GLOBALS["UfdbGuardHTTP"]["UFDBGUARD_PARA2"]="{UFDBGUARD_PARA2}";}
   		if(	$GLOBALS["UfdbGuardHTTP"]["FontColor"]==null){$GLOBALS["UfdbGuardHTTP"]["FontColor"]="#FFFFFF";}
   		if($GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLK"]==null){$GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLK"]="#0300AC";}
   		if($GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLKBT"]==null){$GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLKBT"]="#625FFD";}
   		if($GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLK"]==null){$GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLK"]="#0300AC";}
   		if($GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLKBT"]==null){$GLOBALS["UfdbGuardHTTP"]["BackgroundColorBLKBT"]="#625FFD";}
   		if($GLOBALS["UfdbGuardHTTP"]["BackgroundColor"]==null){$GLOBALS["UfdbGuardHTTP"]["BackgroundColor"]="#8c1919";}
   		if($GLOBALS["UfdbGuardHTTP"]["Family"]==null){$GLOBALS["UfdbGuardHTTP"]["Family"]="Calibri, Candara, Segoe, \"Segoe UI\", Optima, Arial, sans-serif";}
   		if($GLOBALS["UfdbGuardHTTP"]["FontColor"]==null){$GLOBALS["UfdbGuardHTTP"]["FontColor"]="#FFFFFF";}
   		if($GLOBALS["UfdbGuardHTTP"]["BackgroundColor"]==null){$GLOBALS["UfdbGuardHTTP"]["BackgroundColor"]="#8c1919";}

   		if($GLOBALS["VERBOSE"]){echo "<hr><strong style='color:yellow'>CORP_LICENSE={$GLOBALS["CORP_LICENSE"]}</strong><hr>";}
   		if(!$GLOBALS["CORP_LICENSE"]){
   			$GLOBALS["UfdbGuardHTTP"]["FOOTER"]="
   			<table style='width:75%;border-top:1px solid {$GLOBALS["UfdbGuardHTTP"]["FontColor"]};margin-top:15px'>
   			<tr><td colspan=2>&nbsp;</td></tr>
   			<tr>
   			<td width=64px><img src='img/logo-artica-64.png'></td>
   			<td style='font-size:14px;padding-left:10px' width=99%>
   			You using Artica Proxy Appliance v{$GLOBALS["ARTICA_VERSION"]} in Community mode.<br>
   					<i>Visit our  <a href=\"http://artica-proxy.com\">website</a> for technical informations or to purchase an Entreprise Edition License</i>
   					</td>
   					</tr>
   					</table>
   					</div>";
   		}
   		
   }
   
   
   public function BuildTemplatesConfig($ARRAY=array()){
   		if(!isset($GLOBALS["CORP_LICENSE"])){
   			if(class_exists("usersMenus")){$users=new usersMenus();$GLOBALS["CORP_LICENSE"]=$users->CORP_LICENSE;}
   		}
   		if(!isset($GLOBALS["ARTICA_VERSION"])){$GLOBALS["ARTICA_VERSION"]=@file_get_contents("/usr/share/artica-postfix/VERSION");}
   		
   		if($GLOBALS["VERBOSE"]){echo "<p style='color:yellow'>BuildTemplatesConfig ARRAY=".count($ARRAY)." elements\n<br></p>";}
   		$this->BuildTemplatesConfig_from_mysql($ARRAY);
   		$this->BuildTemplatesConfig_defaults();
   		return $GLOBALS["UfdbGuardHTTP"];
	 }
   

   function GET_INFO($key,$nocache=false){
      	$uid=null;
      	$value=null;
      	if(!isset($GLOBALS["MEMCACHE_ENABLED"])){$this->MEMCACHE_ENABLED();}
  		if(isset($_SESSION["uid"])){ $uid=$_SESSION["uid"];}
  		if(!isset($GLOBALS["posix_getuid"])){$GLOBALS["posix_getuid"]=posix_getuid();}
  		if(!$nocache){
  			if(isset($GLOBALS["GET_INFO"][$key])){return $GLOBALS["GET_INFO"][$key];}
  		}
  		
  		if(is_file("/etc/artica-postfix/settings/Daemons/$key")){
  			if(is_readable("/etc/artica-postfix/settings/Daemons/$key")){
  				$value=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/$key"));
  				if($key=="SystemV5CacheEnabled"){if($value==null){$value=0;}}
  				$GLOBALS["GET_INFO"][$key]=$value;
  				if(!$nocache){$this->SET_APC_STORE($key,$value);}
  				return $value;
  			}
  		}
		
  		
  		
   if(!$nocache){
		if($GLOBALS["MEMCACHE_ENABLED"]){
			$X=$this->MEMCACHE_GET($key);
			if(!empty($X)){return $X;}
		}
   }
			
	if(!is_file("/etc/artica-postfix/settings/Daemons/$key")){return null;}

    	
   	$GLOBALS["GET_INFO"][$key]=$value;
   	if(!$nocache){$this->SET_APC_STORE($key,$value);}
   	return trim($value);
   
   	}
   	
   	
   function SQUID_IS_EXTERNAL_LDAP(){
   	if(isset($GLOBALS["SQUID_IS_EXTERNAL_LDAP"])){return $GLOBALS["SQUID_IS_EXTERNAL_LDAP"];}
   	$ArticaSquidParameters=@file_get_contents('/etc/artica-postfix/settings/Daemons/ArticaSquidParameters');
   	$ini=new Bs_IniHandler();
   	$ini->loadString($ArticaSquidParameters);
   	$LDAP_EXTERNAL_AUTH=intval($ini->_params["NETWORK"]["LDAP_EXTERNAL_AUTH"]);
   	if($LDAP_EXTERNAL_AUTH==1){
   		$GLOBALS["SQUID_IS_EXTERNAL_LDAP"]=true;
   	}else{
   		$GLOBALS["SQUID_IS_EXTERNAL_LDAP"]=false;
   	}
   	return $GLOBALS["SQUID_IS_EXTERNAL_LDAP"];
   	
   }
   	
   function GET_CLUSTER($key){
   		if(isset($GLOBALS["GET_CLUSTER"][$key])){
   			return $GLOBALS["GET_CLUSTER"][$key];
   		}
   	
   	
   		if(posix_getuid()==0){
   			$value=trim(@file_get_contents("/etc/artica-cluster/$key"));
   			$GLOBALS["GET_CLUSTER"][$key]=$value;
   			return $value;
   		}
   	

   	$value=trim($this->getFrameWork("getinfos.php?cluster-key=$key"));
    $GLOBALS["GET_CLUSTER"][$key]=$value;
   	return $value;
   
   	}   	
   	
   function SET_INFO($key,$value){
   	if(!isset($GLOBALS["MEMCACHE_ENABLED"])){$this->MEMCACHE_ENABLED();}
   
   	
   	$trace=debug_backtrace();
	if(isset($trace[1])){
		$xfile=null;
		$xline=null;
		$xfunction=null;
		if(isset($trace[1]["file"])){$xfile=$trace[1]["file"];}
		if(isset($trace[1]["line"])){$xline=$trace[1]["line"];}
		if(isset($trace[1]["function"])){$xfunction=$trace[1]["function"];}
		$called=" called by ". basename($xfile)." $xfunction() line {$xline}";
	
	}
   	
   		if($key=="SQUIDEnable"){
   			if($value==1){
				writelogs("SET SQUID $key $value $called",__CLASS__ . "/" . __FUNCTION__,__FILE__);				
				if(class_exists("unix")){ $unix=new unix(); $unix->send_email_events("SQUID was re-activated", $called, "squid"); }
			}
   		}
   	
   	
   	    if(!isset($GLOBALS["posix_getuid"])){$GLOBALS["posix_getuid"]=1000;if(function_exists("posix_getuid")){$GLOBALS["posix_getuid"]=posix_getuid();}}
   		
   		
   	    
   	   
   		if($GLOBALS["posix_getuid"]==0){
   			@file_put_contents("/etc/artica-postfix/settings/Daemons/$key",$value);
   			@chmod("/etc/artica-postfix/settings/Daemons/$key", 0755);
   			$this->SET_APC_STORE($key,$value);
   			if($this->ArticaMetaEnabled==1){$this->getFrameWork("cmd.php?artica-meta-push=$key");}
   			return true;
   		}
   		
   		if(strlen($value)<10){
   			writelogs("SET \"$key\"=\"$value\" $called",__CLASS__ . "=>" . __FUNCTION__,__FILE__,__LINE__);
   			
   		}else{
   			writelogs("SET $key=".strlen($value)." bytes $called",__CLASS__ . "=>" . __FUNCTION__,__FILE__,__LINE__);
   		}
   	   	
   		
   		//Valeur null, on supprime le fichier
   		if(trim($value)==null){
   			if($GLOBALS["posix_getuid"]==0){
   				@unlink("/etc/artica-postfix/settings/Daemons/$key");
   				return true;
   			}
   			$this->getFrameWork("setinfos.php?remove-path=$key");
   			return true;
   		}
   		
   		
   		
   		//on test l'écriture du fichier
   		$t=time();
   		@file_put_contents("/etc/artica-postfix/settings/Daemons/$key-$t", $value);
   		if(is_file("/etc/artica-postfix/settings/Daemons/$key-$t")){
   			// Fichier écrit... remplacement..
   			if(@copy("/etc/artica-postfix/settings/Daemons/$key-$t", "/etc/artica-postfix/settings/Daemons/$key")){
   				@unlink("/etc/artica-postfix/settings/Daemons/$key-$t");
   				$this->SET_APC_STORE($key,$value);
   				return true;
   			}
   		}else{
   			writelogs("SET $key=".strlen($value)." Permission denied...!!",__CLASS__ . "/" . __FUNCTION__,__FILE__,__LINE__);
   			$this->getFrameWork("services.php?chown-medir=yes");
   		}
   		
   		
   		
   		
   	   	$file=md5($value);
   	   	@mkdir(dirname(__FILE__)."/logs",0755,true);
   	   	$targetedFile=dirname(__FILE__)."/logs/$key";
   	    if(is_file($targetedFile)){$alors=@unlink($targetedFile);
	   	    if(!$alors){
	   	    	writelogs("Remove $targetedFile Permissions denied",__CLASS__ . "=>" . __FUNCTION__,__FILE__);
	   	    }
   	    }
   	   	
   	   	$alors=@file_put_contents($targetedFile,$value);
   	    
   	    if(!$alors){
   	    	writelogs("Permissions denied services.php?chown-medir=yes",__CLASS__ . "=>" . __FUNCTION__,__FILE__);
   	    	$this->getFrameWork("services.php?chown-medir=yes");
   	    	$alors=@file_put_contents($targetedFile,$value);
   	    	writelogs("$targetedFile Permissions denied",__CLASS__ . "=>" . __FUNCTION__,__FILE__);
   	    	if(!$alors){return false;}
   	    }
   	    
   	   	if(!is_file($targetedFile)){
   	   		writelogs("SET $key $targetedFile no such file",__CLASS__ . "=>" . __FUNCTION__,__FILE__);
   	   		return;
   	   	}
   		$this->getFrameWork("setinfos.php?key=$key&path=$targetedFile");
   		if($value==null){$value=' ';}
   		$this->SET_APC_STORE($key,$value);
   		if($this->ArticaMetaEnabled==1){
   			$this->getFrameWork("cmd.php?artica-meta-push=$key");
   		}
		$this->DeleteCache();
   		
   		
   		
   }
   function SET_CLUSTER($key,$value){
   	if(strlen($value)>0){
   		if(posix_getuid()==0){
   			file_put_contents("/etc/artica-cluster/$key",$value);
   			return true;
   		}
   		
   	   	writelogs("SET $key=".strlen($value)." bytes",__CLASS__ . "=>" . __FUNCTION__,__FILE__);
   	   	$file=md5($value);
   	   	
   	   	@mkdir(dirname(__FILE__)."/logs/cluster",0755,true);
   	   	@file_put_contents(dirname(__FILE__)."/logs/cluster/$key",$value);
   		$this->getFrameWork("setinfos.php?cluster-key=$key");
   		$this->DeleteCache();
   		}
   }   
   
   function MYSQL_INFO($key){
   	if(isset($GLOBALS["MYSQL_INFO"])){
   		if(isset($GLOBALS["MYSQL_INFO"][$key])){
   			if($GLOBALS["MYSQL_INFO"][$key]<>null){
   					return $GLOBALS["MYSQL_INFO"][$key];
   			}
   		}
   	}
   	$GLOBALS["MYSQL_INFO"][$key]=$this->GET_MYSQL_ROOT($key);
   	return $GLOBALS["MYSQL_INFO"][$key];
   	
   	}
   
   function DeleteCache(){
   	unset($GLOBALS["GET_INFO"]);
   	unset($GLOBALS["MYSQL_INFO"]);
   	unset($_SESSION["APC"]);
   	$this->DATA_CACHE_EMPTY();
   	if(!is_array($_SESSION)){return null;}
   	while (list ($num, $val) = each ($_SESSION) ){
   			if(preg_match('#CACHEINFOS_.+#',$num)){unset($_SESSION[$num]);}
			if(preg_match("#^cachepage_(.+)#",$num)){unset($_SESSION[$num]);}
		}   			
   		
   }
   
   
function GET_PERFS($key){
	$value=$this->APC_GET("PERFORMANCES_{$key}");
	if($value==null){
		if(!class_exists("Bs_IniHandler")){include_once(dirname(__FILE__)."/class.ini.inc");}
		$ini=new Bs_IniHandler("/etc/artica-postfix/performances.conf");
		$value=$ini->get("PERFORMANCES",$key);
		$this->SET_APC_STORE("PERFORMANCES_{$key}",$value);
	}
	return $value;
	
}
function GET_NOTIFS($key){
	$value=$this->APC_GET("NOTIFS_{$key}");
	if($value==null){
		if(!class_exists("Bs_IniHandler")){include_once(dirname(__FILE__)."/class.ini.inc");}
		$ini=new Bs_IniHandler("/etc/artica-postfix/smtpnotif.conf");
		$value=$ini->get("SMTP",$key);
		$this->SET_APC_STORE("NOTIFS_{$key}",$value);
	}
	return $value;
	
}

public function FillSMTPNotifsDefaults($array){
	$ini=new Bs_IniHandler();
	$ini->loadString($this->getFrameWork("cmd.php?SmtpNotificationConfigRead=yes"));
	if($ini->_params["SMTP"]["smtp_server_port"]==null){$ini->_params["SMTP"]["smtp_server_port"]=25;}
	if($ini->_params["SMTP"]["smtp_sender"]==null){
		if(class_exists("usersMenus")){
			$users=new usersMenus();
			$ini->_params["SMTP"]["smtp_sender"]="artica@$users->fqdn";
		}
	}
	
	if(!isset($array["smtp_server_name"])){$array["smtp_server_name"]=$ini->_params["SMTP"]["smtp_server_name"];}
	if(!isset($array["smtp_server_port"])){$array["smtp_server_port"]=$ini->_params["SMTP"]["smtp_server_port"];}
	if(!isset($array["smtp_sender"])){$array["smtp_server_port"]=$ini->_params["SMTP"]["smtp_sender"];}
	if(!isset($array["smtp_dest"])){$array["smtp_dest"]=$ini->_params["SMTP"]["smtp_dest"];}
	if(!isset($array["smtp_auth_user"])){$array["smtp_dest"]=$ini->_params["SMTP"]["smtp_auth_user"];}
	if(!isset($array["smtp_auth_passwd"])){$array["smtp_auth_passwd"]=$ini->_params["SMTP"]["smtp_auth_passwd"];}
	if(!isset($array["tls_enabled"])){$array["tls_enabled"]=$ini->_params["SMTP"]["tls_enabled"];}
	if(!isset($array["ssl_enabled"])){$array["ssl_enabled"]=$ini->_params["SMTP"]["ssl_enabled"];}
	if(!is_numeric($array["smtp_server_port"])){$array["smtp_server_port"]=$ini->_params["SMTP"]["smtp_server_port"];}
	if($array["smtp_server_name"]==null){$array["smtp_server_name"]=$ini->_params["SMTP"]["smtp_server_name"];}
	if($array["smtp_sender"]==null){$array["smtp_sender"]=$ini->_params["SMTP"]["smtp_sender"];}
	if(!is_numeric($array["smtp_server_port"])){$array["smtp_server_port"]=$ini->_params["SMTP"]["smtp_server_port"];}
	return $array;
}


private function stream_framework($uri,$called=null){
	
	if($GLOBALS["AS_ROOT"]){
		if(function_exists("writelogs")){writelogs("As root -> \"stream_framework($uri)\" $called",
		__CLASS__ . "=>" . __FUNCTION__,__FILE__);}
	}
	
	$fp = stream_socket_client("unix:///usr/share/artica-postfix/ressources/web/framework.sock", $errno, $errstr, 10);
	if (!$fp){
		if($GLOBALS["VERBOSE"]){echo "<p>Fatal ERROR: unable to open remote file ressources/web/framework.sock $errstr ($errno)</p>\n";}
		$this->ToSyslog("Fatal ERROR: unable to open remote file ressources/web/framework.sock $errstr ($errno)");
		if(function_exists("writelogs")){writelogs("ERROR: unable to open remote file http://127.0.0.1:47980/$uri",
		__CLASS__ . "=>" . __FUNCTION__,__FILE__);}
		$this->error=true;
		return false;
	}
	
	$header[]="GET /$uri HTTP/1.1";
	$header[]="User-Agent: Artica Framework";
	$header[]="Host: 127.0.0.1";
	$header[]="Accept: */*";

	fwrite($fp, @implode("\r\n",$header)."\r\n\r\n");
	@stream_set_timeout($fp, 10);
	$response =stream_get_contents($fp,-1);
	if($GLOBALS["VERBOSE"]){echo "<p>$response</p>\n";}

/*
 * 
 * $info = stream_get_meta_data($fp);
 * print_r($info);
 (
 		[stream_type] => unix_socket
 		[mode] => r+
 		[unread_bytes] => 0
 		[seekable] =>
 		[timed_out] =>
 		[blocked] => 1
 		[eof] => 1
 )
*/
	@fclose($fp);
	list($response,$datas) = preg_split("/\r\n\r\n|\n\n|\r\r/", $response, 2);
	$response = preg_split("/\r\n|\n|\r/", $response);
	list($protocol, $code, $status_message) = explode(' ', trim(array_shift($response)), 3);
	$headers = array();

	// Parse the response headers.
	while ($line = trim(array_shift($response))) {
		list($name, $value) = explode(':', $line, 2);
		$name = strtolower($name);
		if (isset($headers[$name]) && $name == 'set-cookie') {
			// RFC 2109: the Set-Cookie response header comprises the token Set-
			// Cookie:, followed by a comma-separated list of one or more cookies.
			$headers[$name] .= ',' . trim($value);
		}
		else {
			$headers[$name] = trim($value);
		}
	}

$responses = array(100 => 'Continue', 101 => 'Switching Protocols', 200 => 'OK', 201 => 'Created', 202 => 'Accepted', 203 => 'Non-Authoritative Information', 204 => 'No Content', 205 => 'Reset Content', 206 => 'Partial Content', 300 => 'Multiple Choices', 301 => 'Moved Permanently', 302 => 'Found',303 => 'See Other', 304 => 'Not Modified', 305 => 'Use Proxy', 307 => 'Temporary Redirect', 400 => 'Bad Request',401 => 'Unauthorized', 402 => 'Payment Required', 403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable', 407 => 'Proxy Authentication Required', 408 => 'Request Time-out', 409 => 'Conflict', 410 => 'Gone', 411 => 'Length Required', 412 => 'Precondition Failed',413 => 'Request Entity Too Large', 414 => 'Request-URI Too Large', 415 => 'Unsupported Media Type', 416 => 'Requested range not satisfiable', 417 => 'Expectation Failed', 500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway', 503 => 'Service Unavailable', 504 => 'Gateway Time-out', 505 => 'HTTP Version not supported', );
if (!isset($responses[$code])) {
	$code = floor($code / 100) * 100;
}

switch ($code) {
	case 0: 
		return false;
		break;
	case 200: // OK
	case 304: // Not modified
		break;
	case 301: // Moved permanently
	case 302: // Moved temporarily
	case 307: // Moved temporarily
		break;
	default:
		$this->ToSyslog("Fatal ERROR $code $uri $status_message");
		if(function_exists("writelogs")){writelogs("Fatal ERROR $code $uri $status_message",__CLASS__ . "=>" . __FUNCTION__,__FILE__);}
		$this->error=true;
		return false;
}
$this->error=false;
return $datas;

}

private function mem_getusage(){
		$unit="KB";
		$time=0;
		$mem=round(memory_get_usage(true)/1024);
		if($mem>1024){$mem=round($mem/1000,2);$unit="MB";}
    	$xtime=microtime(true);
    	if(!isset($GLOBALS["VERBOSE_MICROTIME"])){
    		$GLOBALS["VERBOSE_MICROTIME"]=$xtime;
    	}else{
    		$time = $xtime - $GLOBALS["VERBOSE_MICROTIME"];
    		$GLOBALS["VERBOSE_MICROTIME"]=$xtime;
    		$time=round($time,2);
    	}
		return $time."s ".$mem.$unit;
}

	
private function xfile_time_min($path){
	if(!is_dir($path)){
		if(!is_file($path)){return 100000;}
	}
	$last_modified = filemtime($path);
	$data1 = $last_modified;
	$data2 = time();
	$difference = ($data2 - $data1);
	return round($difference/60);
}	
	  
   
function GET_MYSQL_ROOT($key){
	if(isset($GLOBALS["MYSQL_INFO_ROOT"][$key])){
		if($GLOBALS["MYSQL_INFO_ROOT"][$key]<>null){return $GLOBALS["MYSQL_INFO_ROOT"][$key];}
	}
	if($key=='server'){$key='mysql_server';}
	if($key=='root'){ $key='database_admin';}
	if($key=='password'){ $key='database_password';}
	 if(is_file('/etc/artica-postfix/settings/Mysql/'.$key)){
	      $datas=trim(@file_get_contents('/etc/artica-postfix/settings/Mysql/'.$key));
	   }else{
	   $ini=new Bs_IniHandler("/etc/artica-postfix/artica-mysql.conf");
	   $datas=trim($ini->get("MYSQL",$key));
	  }
	   
	if ($key=='mysql_server'){if($datas==null){$datas="127.0.0.1";}}
	if ($key=='port'){if($datas==null){$datas="3306";}}
	if ($key=='database_admin'){if($datas==null){$datas="root";}}
	$GLOBALS["MYSQL_INFO_ROOT"][$key]=$datas;
	return $datas;   
}

private function GetUniqueID(){
	if(isset($GLOBALS["GetUniqueID"])){return $GLOBALS["GetUniqueID"];}
	$CACHE_KEY=md5("cmd.php?system-unique-id=yes");
	if(!$GLOBALS["AS_ROOT"]){
		if(isset($_SESSION["system-unique-id"])){return $_SESSION["system-unique-id"];}
	}
	$cachedatas=$this->MEMCACHE_GET($CACHE_KEY);
	if($cachedatas<>null){
		if(!$GLOBALS["AS_ROOT"]){$_SESSION["system-unique-id"]=$cachedatas;}
		return $cachedatas;
	}
			
	if($GLOBALS["AS_ROOT"]){
		if(!isset($GLOBALS["GetUniqueID"])){
			$unix=new unix();
			$GLOBALS["GetUniqueID"]=$unix->GetUniqueID();
		}
		$this->SET_APC_STORE($CACHE_KEY, $GLOBALS["GetUniqueID"]);
		return $GLOBALS["GetUniqueID"];
	}
			
	$data=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/SYSTEMID"));
	if($data<>null){
		if(!$GLOBALS["AS_ROOT"]){$_SESSION["system-unique-id"]=$data;}
		return $data;
	}
	
	$data=base64_decode($this->stream_framework("cmd.php?system-unique-id=yes"));
	if($data<>null){
		if(!$GLOBALS["AS_ROOT"]){$_SESSION["system-unique-id"]=$data;}
		return $data;
	}
				
	
}

	function IsGoldKey($key){
		$A=unserialize(base64_decode("YToyMDU6e3M6Mjc6IjJPWlRBWi1INU9HRzItWEQyRFJBLVlNN0lOWSI7YjoxO3M6Mjc6IldVRkhUNi1aT1VYTDktU00xWFFSLU9RTUVVQSI7YjoxO3M6Mjc6IktBRVFFMi1ZRUROUzEtSU5TSU9YLUJYNVdLNCI7YjoxO3M6Mjc6IlBQUEFUSi0ySDlWUkEtQTFMVktGLTlKTVZNMiI7YjoxO3M6Mjc6Ik9PVUk5TS1DN0RFUEEtSEdDRzFELUw2NEhKSSI7YjoxO3M6Mjc6IkNFUExGQi1GTjVMMTMtWFFVM1NBLVVWTEpLSiI7YjoxO3M6Mjc6Ik41NVlIUi1MQUYwQ1QtM0lWSlY5LTM5WllJQyI7YjoxO3M6Mjc6IkIxSklPSy1HWFNMUVMtRUFaMVhHLUxZOFNFUyI7YjoxO3M6Mjc6Ik9IVDZQVS00UElFUFgtOUZDTkJJLTlTREVWRiI7YjoxO3M6Mjc6Ikk5WEE2RC0yTk5BRjItNk1VQ0pPLTU5UFA4TCI7YjoxO3M6Mjc6IkU0TENXWi1WQlRKUVotTkFLWE5TLVNRT1JQMiI7YjoxO3M6Mjc6IlBaWE5LVS1VMkREQ1ktTUZPM0ZVLVFSRzhHTiI7YjoxO3M6Mjc6IlFETDVPRS0yMEhVOVctSlkyV05GLVpFR0JGRiI7YjoxO3M6Mjc6IlRBUk9EQS1TSkdRM0stR1lNRjBULThaSTlPVyI7YjoxO3M6Mjc6IlFBMUJBSy1YRFNWWFItWVUyRDdCLUNYNDdDWSI7YjoxO3M6Mjc6IkUwWlRZTi1PNlJSRkEtVDlNVTlaLUdaOFFBRCI7YjoxO3M6Mjc6IjhUM09OMS0xQTU5Qk0tR1dZRThULVRGR0ZXUyI7YjoxO3M6Mjc6IkZQWFZEMy1ORkMwR1YtVlFJVko5LVlJSFVMSyI7YjoxO3M6Mjc6IkJWUlRTSC1CSVdMVEUtQlJCMFhDLVJIV0RGQiI7YjoxO3M6Mjc6IkwxVlpJMC1VSlRTUU8tT1pFTkFHLVRVUVdPQiI7YjoxO3M6Mjc6IkhaVVFQRS1LV0VPRFctUUlDRE5SLUREV0pYNiI7YjoxO3M6Mjc6IklRRjUyRS1aTUZVOEUtMFdHQzdLLURRMktBQSI7YjoxO3M6Mjc6IjhEU1ZQWi03WTg2RUUtVUgxQTlULTVKSUhLQyI7YjoxO3M6Mjc6IlFRRElLUC1EOE5VSkYtOURGRFpaLUZJUk9QTyI7YjoxO3M6Mjc6IlpaUjg4OC1VRFdHVUgtRDhGTkxaLVNIT0ZHMyI7YjoxO3M6Mjc6IklEUDdTTS1INkJTUEotRlVSUUNMLUhBRjlNNCI7YjoxO3M6Mjc6IlJNR1VCVi1ESlJCTlMtWE1FVDlQLU5ISUlDUyI7YjoxO3M6Mjc6IlZOUFlOVS1RN1ZFWkItME5VOFBULUNaWEJEUCI7YjoxO3M6Mjc6IlBGREk2Uy1PQUVOV1UtNTFaNDFOLTI1TEk5WSI7YjoxO3M6Mjc6IjlLRUlCSC1FUkxIWUQtRkdDWk9VLVRVWEdWWSI7YjoxO3M6Mjc6IjNEOE5INy1IRzNMREktQkdSQU0yLTlGNUw1TiI7YjoxO3M6Mjc6Ikg4SVNVUS04T0dZTjktSUZPVU5OLVpOTVVPVSI7YjoxO3M6Mjc6IlpIVUhIUi00S05MNVotM0lZQVAwLVNCOVJENSI7YjoxO3M6Mjc6IlNWNVNQUi1JOU5VVFAtTkFMRE5JLUJOMkpVTiI7YjoxO3M6Mjc6IktOVU5HNC1OVktXQ04tWVI0WEk5LVZGRjYwRiI7YjoxO3M6Mjc6IllMTEtERi1KT0VYSTAtSUFTQTVaLVk1Q1BFRyI7YjoxO3M6Mjc6IklVWkhCRS1FUUZPUkEtT0xaUUI2LTFCQjNINCI7YjoxO3M6Mjc6Ik4ySzBaTi02SzhLSjAtOUpCRkZWLUY5VVNEVyI7YjoxO3M6Mjc6IlhGOFpROC1PRlpXUVgtV0xRQUZYLVNUVVNXTiI7YjoxO3M6Mjc6IkpTUlo1Vi1FRVBYU1gtMlNIRUhHLU5DOUlITyI7YjoxO3M6Mjc6IkVaUktQVS1FT1FVS0gtU1BTWEIxLVdTS1BQQSI7YjoxO3M6Mjc6IkZQRkUyQS02OUtGNkwtUEdOTFowLUlTMFlJQyI7YjoxO3M6Mjc6IjdCTU5UWC1FRUpWQUwtWUIzM0pLLUtOQlpGMSI7YjoxO3M6Mjc6IkJPOU5JSy1QUUJCNFotRkpLTVE0LVhTSzhERCI7YjoxO3M6Mjc6Ilg2UzZMRC01U1JYWlctUUhBNVFELVNCSDhBWSI7YjoxO3M6Mjc6IkdBVEE0Ui1SWDBJSkItRDFQV0ZFLTJUUDNVUCI7YjoxO3M6Mjc6IkJLMkdaTy1FQ0tXQU8tSFhBWU9RLTZYWUk3NiI7YjoxO3M6Mjc6IlZOVzBJVy1CTUs5VE4tWTkzR0FKLVdQUk1NUyI7YjoxO3M6Mjc6IkxaR0dPMC1COURITVItTTBQUDNCLU1ZRVlIVCI7YjoxO3M6Mjc6IjJVTzVMVC0yNkhXS0stNDVMR0RILU9BVlVWUyI7YjoxO3M6Mjc6Ik81SlAxVS1BRzlNUTQtWjlWRjVCLU1GTEFDUiI7YjoxO3M6Mjc6IjZYRFlDVy1LNFBISUEtNEYwVjVCLThXTVpRUCI7YjoxO3M6Mjc6Ik5UMVFOTC03WlVFTDQtOFBYSVdXLU1EWFRFUiI7YjoxO3M6Mjc6IlFENzhCVS01UklaU04tMTVHTEVCLVZIQVk4SCI7YjoxO3M6Mjc6IlRVSk1LRS1ZQ0hFRUwtRUZMTk1LLVhSVExQVyI7YjoxO3M6Mjc6IldIU0sxTC00VlhSV0YtTElQQ1dNLUFQUlhGUSI7YjoxO3M6Mjc6IkZBM1hQMi1USzgyRFItUFpXWUlELTgwVlA5TSI7YjoxO3M6Mjc6IklDQjZXSC1EU1hNWVEtTzJIMUVaLTVKSFpTViI7YjoxO3M6Mjc6IkhYRDlGRS01WFVBRFItQzROSFRJLUZXTE5aNSI7YjoxO3M6Mjc6IklXRk5FRi1OQUcyUDUtVU0yWFdTLVBFN1RTSyI7YjoxO3M6Mjc6IjE3TFpaQi00NDVYSFMtUElMVlhTLVQ3Nk9CRCI7YjoxO3M6Mjc6Iko2V1JUVy1NWVJOOEgtWFBCVzZNLUdVQ09ZTiI7YjoxO3M6Mjc6IlZCRUtXRi1OM1NEWUEtTjJZMU45LUxVTElOSCI7YjoxO3M6Mjc6IkJQS0RPSC1PRktKOU4tWUpRSE9ZLU1XRVdQWiI7YjoxO3M6Mjc6Ik9CR1o5US1SWUZCUlgtV1FQWEVQLVowRkJGUSI7YjoxO3M6Mjc6IlVZOU9RRS1CTTA0Q0wtVUNYTEhQLTZYQUQ0TiI7YjoxO3M6Mjc6IkFXR1lOUy1VQ1A4SEItQVcxRTJQLU9CR1hMWCI7YjoxO3M6Mjc6IksxQ0U0UC1JUTJGWTEtR1ZIVUJBLTFLTktGNCI7YjoxO3M6Mjc6IkFMU1lIUy1PRjk5TDktR1JWMlYwLVNIMkdaWSI7YjoxO3M6Mjc6IkFZSkM5TC1DT0lKRlItUElFWUpJLVcxVUxFUiI7YjoxO3M6Mjc6IkhXV0hKMS1WREZIUkYtTTJRUU1XLTNUU1ZHVSI7YjoxO3M6Mjc6Ikw5UUhPVS01OTU1OUwtTVhaWVc0LVpLUEpaTiI7YjoxO3M6Mjc6IldXQVhaTS1KM0oyTVEtMlI3SENJLTBDWkpPSyI7YjoxO3M6Mjc6IkRHWU1DMC0wR0dVRkctN0RPQVE0LU1XQ0ZZQyI7YjoxO3M6Mjc6IjlZQUk0Ri1XSUIxMUYtUFBTRENDLVM4N0dEOCI7YjoxO3M6Mjc6IlRIVkZWTS1BS1JXTk4tQkpQME1WLVpQWUVTMCI7YjoxO3M6Mjc6Ik9YRUM1Vi01S1NNRFQtWUpUVTA5LUpRUTcyRiI7YjoxO3M6Mjc6IlU4VFFPVS1ZQjFZM0otVUxSVVNDLVhWSVpCWSI7YjoxO3M6Mjc6IkVRTk9UNC1KS0cySzctRFAzUE5ULTdMUU5STyI7YjoxO3M6Mjc6IlRUVVI2Si1LTzNENDQtSUlXQUE5LVVKS1RNTiI7YjoxO3M6Mjc6IkVBRERUSy1MN0paTlUtWUdKSk1aLThBUDFaTSI7YjoxO3M6Mjc6IlZBUlhSVi05SVNUUTUtQUc0S09CLVgyTkM0QyI7YjoxO3M6Mjc6IlRVREdRMC1XS09TMlMtTEVESFdRLVVOQVdDViI7YjoxO3M6Mjc6IlBKS0c0VC1aUERNSFctV0JDUk9FLUhJSzhWVSI7YjoxO3M6Mjc6IlkyRUhFSi1RQVFRUU0tV1MzQk0yLUxZSFBLSSI7YjoxO3M6Mjc6IkpMRlBaOC1WOUdETkwtVDZJMVg5LUZYWjNZMyI7YjoxO3M6Mjc6IlhHVkNVQy1FTjROSkctMFdaWFVJLVhGSEJCVyI7YjoxO3M6Mjc6IlBEUzFKVS1UTkNXUFAtUU5CQTVMLUI3QktKSSI7YjoxO3M6Mjc6IkFEUDZJRS1XTU5JWUMtTFZSTk5LLTVITVhXTyI7YjoxO3M6Mjc6IkJTT1lNUC1DVDBGWVctSTRNTDVaLVQyQlE4UiI7YjoxO3M6Mjc6IlNBUk9FWi1LSUJUUEctUU5TSTk0LUE4QUNFUiI7YjoxO3M6Mjc6IkhMT1lUUC04VVNVS0stWldZNk1LLUpKOE8yTiI7YjoxO3M6Mjc6Ik80VkFZVy01SlQwSEYtTExVWEhYLVlZQzdKQiI7YjoxO3M6Mjc6IkxQRjNQVy1PNVBCR1UtMFA1RUtOLVhTQ09PWSI7YjoxO3M6Mjc6IlI5UE40Qi1JWFc4RVMtOUJJT0xHLThURFA0UiI7YjoxO3M6Mjc6IjhGVU5DVi1YVlNPMVQtTDNWUUdELUFKUEtQUCI7YjoxO3M6Mjc6IlJFUTJOSi0wWlRXWkwtNk9FUTlJLUpUWUFQRSI7YjoxO3M6Mjc6IkhGU0lBQy1NVElZTFQtWUlGUUlELVVKS1hHMiI7YjoxO3M6Mjc6IkxZQjFXVC1WVzNMNVktUEJMUExJLUdIRUNaUyI7YjoxO3M6Mjc6Ik1JSkdCMS1MTlQyRDItUk0wS0RKLVRaRVFMNyI7YjoxO3M6Mjc6IkJUV0VPTi0zWTNESVctVEdDV1NYLUtPRUhSTCI7YjoxO3M6Mjc6IlRPUEJBSS1QQ0tUUEctN0pSSlVKLUVTQU5HMSI7YjoxO3M6Mjc6IkY0QTNCTS1KTkZPUVktVFhOTUtTLTFMVjlHTiI7YjoxO3M6Mjc6IlM2Uk1QRS1GQ0I2Tk0tUkVDSVoyLTJOSlBNRCI7YjoxO3M6Mjc6IldUSU5YUS1BMVFCS0QtSVVQQ0swLUhTS1dDVyI7YjoxO3M6Mjc6IjJYSUJHMy1TVEJRSjgtTzRVQllYLVlTQ1hHQyI7YjoxO3M6Mjc6Ik4wVVVUNC1DUFgyOUQtT0VNQ0pMLURJOFdPOCI7YjoxO3M6Mjc6IkM5SkJLVS1MSFpXQVQtV0Y0WVhWLTMzNlhNUSI7YjoxO3M6Mjc6IjAyTkFIUS1FWTA3V1YtSk9NVEhULUxKVk1FOSI7YjoxO3M6Mjc6IjBZMUZMMi1NSDZKRUMtSlpDR05ILVpST0VUTiI7YjoxO3M6Mjc6Ikk1Sk5YTi1UQk5ITkstR1JBVDk2LUpRTzZJMSI7YjoxO3M6Mjc6IjU4V0pNTi1US1NUQkotSkdMWkcwLUVPU0NDMyI7YjoxO3M6Mjc6IjhGT0RXTy1SRk5GWDEtSFNLRldYLUZVS05QUCI7YjoxO3M6Mjc6Ik5aQ1pFWi1PRlUzWTItVzYwN0hELVVaUFlGVCI7YjoxO3M6Mjc6IlRBQ0dPSS01VEJQM1ktM1pCUk9PLUdCT1lTUCI7YjoxO3M6Mjc6IlEyUVVSQi1KMERBWUEtMTNWU1lRLTBKMEpVWSI7YjoxO3M6Mjc6IkhYUkNBWS00Q0owVk0tQlJJWVpTLVVWR04wRSI7YjoxO3M6Mjc6IkE4T1ZHNi1QWVNNTEEtTEJSWFZNLUpISTVJOCI7YjoxO3M6Mjc6IkFZOThaRi1WQ1ZEVU8tVjdDREVPLVRTTVZRNyI7YjoxO3M6Mjc6IlJZSkgzWC1IUUMwTU4tWkkyNjBKLUNTSUY2TSI7YjoxO3M6Mjc6IkUxRkNOWC1OV0NSMUQtTFlVNVRGLU8xREJBWCI7YjoxO3M6Mjc6IjRBTVNRVC1YQlowOEYtWU45WkVELTM4UkhIUSI7YjoxO3M6Mjc6Ik9URUZCSy1SSlBLN0wtOEVPRFg0LTlCTVZaMSI7YjoxO3M6Mjc6IkQxOUhYWC1SUEFaWkEtV1dVRUFXLVBTTjhBVyI7YjoxO3M6Mjc6Ik9LT0xLQy05Q0hKTkgtTE1BSEtFLVVISlpTRiI7YjoxO3M6Mjc6IlZXWlpWQy1VS1RaRlAtRU83MFFFLVpHTThDNiI7YjoxO3M6Mjc6IjJBRk4xUy1IREkySU8tSjRGWVhNLVVaMkdMRyI7YjoxO3M6Mjc6IllYRUJFSi1OMEhKVUMtWkJPWllRLVNEOUFXVCI7YjoxO3M6Mjc6IlJTVFlBRS1SUEVJU1MtVE9aQlFPLVBaUFE2QiI7YjoxO3M6Mjc6IjhCQVhGUi1LSVNJQUYtNVFVN0RYLTZPUlBYNCI7YjoxO3M6Mjc6IllWT1hLWi1UQ0EwWFItV0NWUk5YLVVFSVdYWSI7YjoxO3M6Mjc6IjBPQkxSVC1PTExBWkotQTlPMldILVhMQklNTyI7YjoxO3M6Mjc6IkROS1pSQS04MDNLSk4tVVU3WTRYLTg2RUdPNiI7YjoxO3M6Mjc6IkVSUUhZRi1HQkdDTkUtSk5QTEtLLVdWUENCVCI7YjoxO3M6Mjc6IlRHQ0ROSS1aVk1TRDktVzlKQlg0LTYwMEJQTCI7YjoxO3M6Mjc6Ik5FVUxRQi1YUk1VRlAtWEhMRE1JLVlJMFpaSSI7YjoxO3M6Mjc6Ikw1VjcxRy0wSEpUWlMtWUxNOU9OLUFaSExNOSI7YjoxO3M6Mjc6IjVFQ0dNRS1ZTktUQ0ktSkxYR0VILU5WWFJJTSI7YjoxO3M6Mjc6Ik5RQVVUTC1PREtHVTQtRVdQSVcyLUNHRU9YUSI7YjoxO3M6Mjc6IlFOSkRHOC1JRVRTSkotWFUwT1Q3LUJSNVlaRCI7YjoxO3M6Mjc6IlNFRkFIQy1ZUU9EQjAtUVFORUdPLVBTSEdFUyI7YjoxO3M6Mjc6IkxKUjhTUi1CSk1OQUMtWE04V0wzLUNZQ0dGRSI7YjoxO3M6Mjc6IkdPN1JORC1HSkk4RkYtQ1UzMUJQLUpETzdZSiI7YjoxO3M6Mjc6IkxGSkNLQy1USkdKMjAtTDlOSTI3LU9ONFVCSyI7YjoxO3M6Mjc6IkZCV0VCVS1CQ05NUEItQ1RQR0NULVNUUkdPViI7YjoxO3M6Mjc6IkFZVU5TUy1GVFpPQjUtWFJGSzQxLVBGR0ZIVCI7YjoxO3M6Mjc6Ik5MQU40Sy1KWEpEUU4tSU9XMk5ELVExN1JTUCI7YjoxO3M6Mjc6IldCVFhFTC1VTVhMUlotSENLRTdZLVhIMDVDRSI7YjoxO3M6Mjc6IjlIQ1NOMy1GU1M0UVctRElVNUFMLUEzT0dZUiI7YjoxO3M6Mjc6IkRBWEFSWi1UNEJaVVYtUEFNUkpJLVFKUDJPNyI7YjoxO3M6Mjc6IkpYMkNDWS1MNlZGWEEtQUJBS0hQLVpOMVBKQiI7YjoxO3M6Mjc6IktEWVVDTC1WUTJLWjgtM1FQWkhWLUFHV1NaMCI7YjoxO3M6Mjc6IlFYMkxOUi1RRVBHSFgtQ0lIUEdSLVg4WUNVSyI7YjoxO3M6Mjc6IlNLV1Q2TS1JR0FGNUEtTVpFWk1VLVZQU1dJTSI7YjoxO3M6Mjc6IklSQ1hUUC1TWDFCMUotRlhEQVk1LUZQUFJFTSI7YjoxO3M6Mjc6IjRQQ0hMRi1VQk4zSFgtTVNPSlMxLUM4WUNJRCI7YjoxO3M6Mjc6IlJITUhOSC1NRFdZWTMtOVBHSURGLVRLWlNTNyI7YjoxO3M6Mjc6IkxaTlVPOC1WM0lWMTgtUjRURk5GLVk4SlRNQSI7YjoxO3M6Mjc6IlBFUFZYNi1BVFozRUktUzgwNkNPLUpJRzJSSiI7YjoxO3M6Mjc6IkdTT0xLRy1aUFFLVVQtWlA3WllCLUhSUEtDMCI7YjoxO3M6Mjc6IlBTUkJRUS1ZRURZVUotSUdVWkZZLTBIR09VUCI7YjoxO3M6Mjc6IkVQRTlMWi1LSU1ENFotMkJOSFo1LUNEVVdXMSI7YjoxO3M6Mjc6IklaUEZTWS1FTVVDVE4tTzczS0JMLU9FSkFMTiI7YjoxO3M6Mjc6IlBSRUNCMC1CMFBXV0ctUllKUEJILTI1U1pPVSI7YjoxO3M6Mjc6IjVENlpTRy1HWlpTVlktR0ZVRFNNLU5VQjlTRSI7YjoxO3M6Mjc6IlNMVlRNTy1RQkFEMEwtVzJGWDNKLUdWSUZRTCI7YjoxO3M6Mjc6IktSQVlNUC1RUUJFMUMtQktIVVBZLUY3U0hVQSI7YjoxO3M6Mjc6IkxST0NHRC1SRVFLT08tNUozWkVaLU1WWUhEVSI7YjoxO3M6Mjc6IkNRQ0VTTi1STDJVQkotQ1I1U0VJLVdBUFVRWCI7YjoxO3M6Mjc6IjBDWldOSC1EQUJHV0ItRlFTUlAyLUs5UlNHSiI7YjoxO3M6Mjc6Ik1BRVFKRy1PRVBKODgtNUdQSVkyLUI5ODNaRiI7YjoxO3M6Mjc6IlRGVUZJNy1UUE9VNTAtNVNCSjNDLUJZRVlYSCI7YjoxO3M6Mjc6IkhIR1pZNi1DVElMNUstQk9OVkNILTk1WU5BVCI7YjoxO3M6Mjc6IjlFODJLRy04RTBaTUMtODNCV0RBLVY5TVhIQiI7YjoxO3M6Mjc6IkEyOVpCVy1USENDSDEtTFlQTVRJLU01RFBaUiI7YjoxO3M6Mjc6Ikg4SEdBWS1HVVQ3VlQtTE41NDlWLUpTTzVBSiI7YjoxO3M6Mjc6IjlPS0JGSC1KTkxRQ0wtTlVER1c5LUROV0s3VyI7YjoxO3M6Mjc6IllXR1VTVy1KR1hXQkItVDVNOTYxLUtPUlNINCI7YjoxO3M6Mjc6IkhVWUJINi1DUU1MQVMtTTdWTkpULUpVQ1QwMiI7YjoxO3M6Mjc6IkI0MElMUC1GSjVCVFctNUxIMThTLTgyVjFXTCI7YjoxO3M6Mjc6IjdXRjhQVC1URU9JT1ctSVc5NlVSLVI2R1BCNSI7YjoxO3M6Mjc6IkU1U0VEWi01TUNLN1AtS1JRT1hQLVBTQVZONiI7YjoxO3M6Mjc6Ilg2RU1JUy1TTThXSEwtSTJURE5RLVdNV1VPVCI7YjoxO3M6Mjc6Ik9UMzFDTi1BWUFISkYtRUcwUDZPLUJBOVNXWCI7YjoxO3M6Mjc6IloxQVdDQi0xUzZNQVYtQkVJS1ZHLUlETUNUUyI7YjoxO3M6Mjc6IkdNWlVaWC1XR1VJR1MtRlJPSDBLLTJRRzYwTyI7YjoxO3M6Mjc6IlVETVRORi1LUTNRVlgtRUJKNlFOLVhZNlA0RSI7YjoxO3M6Mjc6Ik5HUE1HNS00VFpaQ0UtRUlJMUVQLUZVNFlVQyI7YjoxO3M6Mjc6IjI4VUlaRC00SEdBTlctUFVTVUVRLUxRVFhSMCI7YjoxO3M6Mjc6IkJRV1lMVi1QSkJBNU0tSEdWTVZKLVZOTkdQTyI7YjoxO3M6Mjc6IkNaUklFTi01Vk1LRUQtNkxPTU01LVpPWUE4NiI7YjoxO3M6Mjc6IjNNVVhQMC1KR1pISVUtVEJMWENaLTdYSUJBVyI7YjoxO3M6Mjc6Ik02TElGRi1SWElGMVAtSFE2VVFCLTQ2STRDTSI7YjoxO3M6Mjc6IkNFWUpURS1ORkFWU1ctQUNQR1VJLVVTUlpTOSI7YjoxO3M6Mjc6IjdaT0pZQi1HOE5VNVAtRVlFNlVBLUFDRjJTRyI7YjoxO3M6Mjc6IlhSTEhSTS1PT01JUEYtVVQ4UDdLLVJFNkgzVCI7YjoxO3M6Mjc6IkpOS1NHQS1XQ0VJV00tR05JV1JNLVZMUDY4NSI7YjoxO3M6Mjc6IlgwV0dFVi1WSUNFS0UtTkRPQkpSLUxLUFFTTiI7YjoxO3M6Mjc6Ik5BQzFEOS1QQlJXTEctOTlZU1lCLVJVQ09XSSI7YjoxO3M6Mjc6IllXV1FGTi1RSlVMQlYtRlVUNlBQLUFYTlRFUyI7YjoxO3M6Mjc6IkhQVlVURC1XVTk3VEotTU1VUk1aLUZLOE1TVSI7YjoxO3M6Mjc6IkdQOFRSVC1aU1JENUUtVURBVVRMLVU4NlBZUiI7YjoxO3M6Mjc6IkdVWVdXRi0wRDE0WDktNUNCVVNaLVZHWVlOSSI7YjoxO3M6Mjc6Ik5MTUlUNC1ONlE3RTUtNkZLWTRFLThMWTFLMSI7YjoxO3M6Mjc6IllCSUVNUy1LSDNPUFUtVlFSUVJHLVlSOFY0RiI7YjoxO30="));
		return $A[$key];
	}
	
	
	function isFirehol(){
		if(intval($this->getFrameWork("firehol.php?is-installed=yes"))==0){return false;}
		if(intval($this->GET_INFO("FireHolConfigured"))==0){return false;}
		if(intval($this->GET_INFO("FireHolEnable"))==0){return false;}
		return true;
		
	}
	
	


	function getFrameWork($uri){
		$trace=debug_backtrace();
		if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
		
		
		$line=null;
		$CACHE_KEY=md5($uri);
		

		
		
		if($GLOBALS["AS_ROOT"]){
			if(!class_exists("unix")){
				include_once("/usr/share/artica-postfix/framework/class.unix.inc");
				include_once("/usr/share/artica-postfix/framework/frame.class.inc");
			}
			
			if($uri=="squid.php?IsIcapClient=yes"){
				$unix=new unix();
				if($unix->SQUID_ICAP_ENABLED()){ return "TRUE"; }
				$this->Runned=true;
				return;
			}
			
			include_once(dirname(__FILE__)."/class.socketAsRoot.inc");
			$socketAsRoot=new SocketsAsRoot($uri);
			$Val=$socketAsRoot->SocketsAsRoot_perform();
			if($socketAsRoot->Runned){return $Val;}
		}
		
		if($uri=="cmd.php?ldap-restart=yes"){
			if($this->SquidPerformance>2){return;}
			if($this->EnableIntelCeleron==1){return;}
			$timeF=dirname(__FILE__)."logs/web/ldap-restart.time";
			if($this->xfile_time_min($timeF)<3){return;}
			@unlink($timeF);
			@file_put_contents($timeF, time());
		}
		
		if($uri=="services.php?ARTICA-MAKE=yes"){
			
		}
		
		
		if($uri=="cmd.php?uri=artica_version"){
			if(isset($GLOBALS["artica_version"])){return $GLOBALS["artica_version"];}
			$GLOBALS["artica_version"]=@file_get_contents("/usr/share/artica-postfix/VERSION");
			return $GLOBALS["artica_version"];
		}
		
	
		
		
		if($uri=="cmd.php?system-unique-id=yes"){
			$GetUniqueID=$this->GetUniqueID();
			if($GetUniqueID<>null){return base64_encode($GetUniqueID);}
		}
		
		if($uri=="system.php?MEM_TOTAL_INSTALLEE=yes"){
			$cachedatas=$this->MEMCACHE_GET("MEM_TOTAL_INSTALLEE");
			if($cachedatas<>null){return $cachedatas;}
		}

		
		if($uri=="cmd.php?uri=artica_version"){
			$cachedatas=$this->MEMCACHE_GET($CACHE_KEY);
			if($cachedatas<>null){return $cachedatas;}
			if($GLOBALS["AS_ROOT"]){$dd= @file_get_contents("/usr/share/artica-postfix/VERSION");}
			if($dd<>null){$this->SET_APC_STORE($CACHE_KEY, $dd);}
		}
		
		

		if($GLOBALS["AS_ROOT"]){
				if(isset($GLOBALS[$CACHE_KEY])){return $GLOBALS[$CACHE_KEY];}
				if(preg_match("#getinfos\.php\?key=(.+?)&uid=#", $uri,$re)){
					$basedir="/etc/artica-postfix/settings/Daemons";
					$filename=$re[1];	
					$data=@file_get_contents("$basedir/$filename");
					$GLOBALS[$CACHE_KEY]=$data;
					return $data;
				}
		}
		
		
			
		if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>stream_framework($uri)</span><br>\n";}
		$line=$this->stream_framework($uri,$called);
		
		if(trim($line)==null){if($GLOBALS["VERBOSE"]){echo "[DEBUG SOCKET::".$this->mem_getusage()."] return null<br>\n";}return null;}
		if(trim($line)=="<articadatascgi></articadatascgi>"){return null;}

		if(preg_match('#<articadatascgi>(.*?)</articadatascgi>#s',$line,$regs)){
			$results=trim($regs[1]);
			if($uri=="cmd.php?uri=artica_version"){$this->SET_APC_STORE("artica_version",$results);}
			if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>$uri:(1) = ". strlen($results)."</span><br>\n";}
			return $results;
		}
		
		if(preg_match('#<articadatascgi>(.*?)</articadata#s',$line,$regs)){
			$results=trim($regs[1]);
			if($uri=="cmd.php?uri=artica_version"){$this->SET_APC_STORE("artica_version",$results);}
			if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>$uri:(2) = ". strlen($results)."</span><br>\n";}
			return $results;
		}
		
		if(preg_match("#500 - Internal Server Error#is", $line)){
			if($GLOBALS["AS_ROOT"]){
				$this->ToSyslog("Restarting framwork !!! 500 - Internal Server Error");
				writelogs("Restarting framework !!! 500 - Internal Server Error");
				shell_exec("/etc/init.d/php5-fpm restart >/dev/null 2>&1");
				shell_exec("/etc/init.d/artica-framework restart");
				die();
			}
		}
		
		if(preg_match("#503 - Service Not Available#is", $line)){
			if($GLOBALS["AS_ROOT"]){
				writelogs("Restarting framework !!! 500 - Internal Server Error");
				$this->ToSyslog("Restarting framework !!! 500 - Internal Server Error");
				shell_exec("/etc/init.d/php5-fpm restart >/dev/null 2>&1");
				shell_exec("/etc/init.d/artica-framework restart");
				die();
			}
		}
		
		if(preg_match('#<articadatascgi>(.+)</\s+articadatascgi>#s',$line,$regs)){$results=trim($regs[1]);return $results;}			
		if(preg_match('#<articadatascgi>(.+)</articadat#s',$line,$regs)){$results=trim($regs[1]);return $results;}
		if(preg_match('#<articadatascgi>(.+)</articada\s+tascgi>#s',$line,$regs)){$results=trim($regs[1]);return $results;}
		if(preg_match('#<articadatascgi>(.+)</articad\s+atascgi>#s',$line,$regs)){$results=trim($regs[1]);return $results;}	
		if(preg_match('#<articadatascgi>(.+)</artica\s+datascgi>#s',$line,$regs)){$results=trim($regs[1]);return $results;}			
		if(preg_match('#<articadatascgi>(.+)</articada\s+scgi>#s',$line,$regs)){$results=trim($regs[1]);return $results;}			
		if(preg_match('#<articadatascgi>(.+)</artic\s+adatascgi>#s',$line,$regs)){$results=trim($regs[1]);return $results;}
		if(preg_match('#<articadatascgi>(.+)</arti\s+cadatascgi>#s',$line,$regs)){$results=trim($regs[1]);return $results;}
		if(preg_match('#<articadatascgi>(.+)</art\s+icadatascgi>#s',$line,$regs)){$results=trim($regs[1]);return $results;}		
		if(preg_match('#<articadatascgi>(.+)</ar\s+ticadatascgi>#s',$line,$regs)){$results=trim($regs[1]);return $results;}
		if(preg_match('#<articadatascgi>(.+)</a\s+rticadatascgi>#s',$line,$regs)){$results=trim($regs[1]);return $results;}
		if($GLOBALS["VERBOSE"]){echo "Fatal:Unable to preg_match \"$line\" uri=$uri<br>\n";}
		$line=trim($line);
		
		if(preg_match("#cmd\.php\?SaveConfigFile#", $uri)){return;}
		
		writelogs("Fatal:Uri: uri=$uri Unable to preg_match \"$line\"",__FUNCTION__,__FILE__);
		
	}
	
	
	private function ToSyslog($text){
	
		$LOG_SEV=LOG_INFO;
		if(function_exists("openlog")){openlog("class.sockets.inc", LOG_PID , LOG_SYSLOG);}
		if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
		if(function_exists("closelog")){closelog();}
	}	
	
	private function SYSTEMV5_ENABLED(){
		return false;
		
	}
	
	
	function DATA_CACHE_SAVE($key,$value,$IsAnArray=false){
		$this->SET_APC_STORE($key, $value);
		return;
	}
		
	function DATA_CACHE($key,$IsAnArray=false,$maxtime=0){
		return $this->MEMCACHE_GET($key);	
   	
		
		
	}		
	
	function LANGUAGE_CACHE_IMPORT($array_keys,$lang){
		if(!isset($GLOBALS["MEMCACHE_ENABLED"])){$this->MEMCACHE_ENABLED();}
		
		
		if($GLOBALS["MEMCACHE_ENABLED"]){
			$memcache = new Memcache();
			$memcache->connect('unix:///var/run/memcached.sock', 0);
			if($memcache->set("ARTICA_LANG_$lang", serialize($lang), 0, 9999)){
				writelogs("Succes save language file $lang in MemCached",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			} 
			$memcache->close(); 
			return;
		}
		
		if($GLOBALS["LOGON-PAGE"]){error_log("[{$_SESSION["uid"]}]::LANGUAGE_CACHE_IMPORT() ". basename(__FILE__). " line ". __LINE__);}
		return null;
	}	
	



	public function LANGUAGE_DUMP($lang){
		if($GLOBALS["DEBUG_LANG"]){
		if(function_exists("debug_backtrace")){
			$trace=debug_backtrace();
			if(isset($trace[1])){
				$file=basename($trace[1]["file"]);
				$function=$trace[1]["function"];
				$line=$trace[1]["line"];
		
			}
		
		}
			echo __FUNCTION__."::".__LINE__."::<strong style='color:#d32d2d'>DUMP $lang by  $file/$function/$line</strong><br>\n";
		}
		
		
		if($GLOBALS["AS_ROOT"]){return;}
		if($lang=="undefined"){return;}
		$data=array();
		if(!isset($GLOBALS["MEMCACHE_ENABLED"])){$this->MEMCACHE_ENABLED();}
		if($GLOBALS["DEBUG_LANG"]){echo __FUNCTION__."::".__LINE__."::<strong style='color:#d32d2d'>LANGUAGE_DUMP MEMCACHE: {$GLOBALS["MEMCACHE_ENABLED"]}</strong><br>\n";}
		
		
		if($GLOBALS["MEMCACHE_ENABLED"]){
			$memcache = new Memcache();
			$memcache->connect('unix:///var/run/memcached.sock', 0);
			$ARRAY=unserialize($memcache->get("ARTICA_LANG_$lang"));
			if(count($ARRAY)>100){
				writelogs("Dumping language $lang FROM memcached SUCCESS!",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
				$memcache->close();
				return $ARRAY;
			}
		}		
		
		if(!isset($GLOBALS["translation_$lang"])){
			if(isset($_SESSION["translation_$lang"])){
				$GLOBALS["translation_$lang"]=$_SESSION["translation_$lang"];
			}
		}
		
		
		if(isset($GLOBALS["translation_$lang"])){
			if(count($GLOBALS["translation_$lang"])>100){
				if($GLOBALS["DEBUG_LANG"]){echo __FUNCTION__."::".__LINE__."::<strong style='color:#d32d2d'>RETURN BY MEM KEY=translation_$lang</strong><br>\n";}
				return $GLOBALS["translation_$lang"];
			}
		}
		

		
		
		if($GLOBALS["LOGON-PAGE"]){error_log("[{$_SESSION["uid"]}]::LANGUAGE_DUMP() ". basename(__FILE__). " line ". __LINE__);}
		if($GLOBALS["DEBUG_LANG"]){echo __FUNCTION__."::".__LINE__."::<strong style='color:#d32d2d'>LANGUAGE_DUMP Memory store ". count($GLOBALS["translation_$lang"])." rows\n</span><br>\n";}
		$filepath=dirname(__FILE__)."/language/$lang.db";
		if($GLOBALS["DEBUG_LANG"]){echo __FUNCTION__."::".__LINE__."::<strong style='color:#d32d2d'>Loading $filepath</strong><br>\n";}
		if(!is_file($filepath)){
				if($lang=="pt"){
					if($GLOBALS["DEBUG"]){echo "LANGUAGE_DUMP switch to br.db instead, $filepath no such file\n";}
					$lang="br";
					$filepath=dirname(__FILE__)."/language/br.db";
				}
			}
			
		if(!is_file($filepath)){
			if($GLOBALS["DEBUG_LANG"]){echo __FUNCTION__."::".__LINE__."::<strong style='color:#d32d2d'>$filepath No such file</strong><br>\n";}
			$filepath=dirname(__FILE__)."/language/en.db";
		}
		
		if($GLOBALS["DEBUG_LANG"]){echo __FUNCTION__."::".__LINE__."::<strong style='color:#d32d2d'>FINAL: $filepath</strong><br>\n";}
		$file=@file_get_contents($filepath);
		if($GLOBALS["DEBUG"]){echo "LANGUAGE_DUMP File: $filepath ". strlen($file)." bytes\n";}					

		$data=unserialize($file);
		if(!is_array($data)){ 
			if($GLOBALS["DEBUG_LANG"]){echo __FUNCTION__."::".__LINE__."::<strong style='color:#d32d2d'>$filepath No array</strong><br>\n";}
			writelogs("[$lang]:: Get language file from framework Not an array !",__CLASS__."/".__FUNCTION__,__FILE__); }
			
		if($GLOBALS["DEBUG"]){echo "LANGUAGE_DUMP: $lang:". count($data). " items\n";}
		if(count($data)<500){ writelogs("[$lang]:: Get language file from framework Not an array !",__CLASS__."/".__FUNCTION__,__FILE__); }
		if($GLOBALS["DEBUG"]){echo "LANGUAGE_DUMP serialized=". count($data)." rows ->LANGUAGE_CACHE_IMPORT($lang,...)\n";}
		$GLOBALS["translation_$lang"]=$data;
		$_SESSION["translation_$lang"]=$data;
		$this->LANGUAGE_CACHE_IMPORT($lang,$data);
		return $GLOBALS["translation_$lang"];
		
	}	
	
	
	function DATA_CACHE_EMPTY(){
		if(!$GLOBALS["MEMCACHE_ENABLED"]){return;}
		$memcache_obj = new Memcache();
		$memcache_obj->connect('unix:///var/run/memcached.sock', 0);
		$memcache_obj->flush();
		$memcache_obj->close();
	}
	
	function SET_APC_STORE($key,$value){
		if(!isset($GLOBALS["MEMCACHE_ENABLED"])){$this->MEMCACHE_ENABLED();}
		if(!$GLOBALS["MEMCACHE_ENABLED"]){return;}
		$memcache = new Memcache();
		$memcache->connect('unix:///var/run/memcached.sock', 0);
		$ARRAY=unserialize($memcache->get('ARTICA_INFOS'));
		$ARRAY[$key]=$value;
		if(!$memcache->set('ARTICA_INFOS', serialize($ARRAY), 0, 9999)){
			writelogs("[$key] --> \"$value\" Failed in Memcached",__CLASS__."/".__FUNCTION__,__FILE__); 
		}
	}
		
	function APC_CLEAN(){
		$this->DATA_CACHE_EMPTY();
	}
	
	function APC_GET($key,$maxtime=0){
		return $this->MEMCACHE_GET($key);	
	}
	
	function APC_SAVE($key,$value){
		$this->SET_APC_STORE($key,$value);
	}
   
		
	function getfile($uri,$hostname=null){
		if(function_exists("debug_backtrace")){
			$trace=debug_backtrace();
			if(isset($trace[1])){
				$file=basename($trace[1]["file"]);
				$function=$trace[1]["function"];
				$line=$trace[1]["line"];
		
			}
		
		}
		error_log("[{$_SESSION["uid"]}]::Fatal: $file/$function line:$line -> ASK an non-supported order $uri");
			
	}
		
	function GetFileCache($uri){
		$arr["artica_version"]=true;
		if(isset($arr[$uri])){
			return $arr[$uri];
		}
	}
		
	function TestArticaPort(){}
	function TestArticaFilterPort(){
		$fp = @fsockopen ($this->remote_ip,$this->remote_port , $errno, $errstr, 1);
		
		if(!$fp){
			$this->error=true;
			if(function_exists("writelogs")){writelogs("Bind remote LOCAL:29900  $errstr ($errno) ",__CLASS__ . "=>" . __FUNCTION__,__FILE__);}		
			if(function_exists("writelogs")){writelogs("ERROR: unable to connect remote remote LOCAL:29900  $errstr ($errno)" ,__CLASS__ . "=>" . __FUNCTION__,__FILE__);}		
			return false;
		}
		fclose($fp);
		return true;
		
	}
	
	function RandomPort(){
		$min='1024';
		$max='65534';
		while (true) {
			$port=rand($min,$max);
			if(!$this->PortExists($port)){
				return $port;
			}
		}
		
		
	}
	

	function PortExists($port_number){
		for($port = $from; $port <= $to; $port++){
		$fp =@fsockopen("127.0.0.1", $port);
		if ($fp){
			fclose($fp);
			return true;}
		
		}
		
	}
	
	

}
?>