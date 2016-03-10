<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.groups.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.ActiveDirectory.inc');
include_once(dirname(__FILE__).'/ressources/class.external.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');

if($argv[1]=="--dansguardian-status"){dansguardian_status(true);die();}

$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();
}

if(isset($_GET["status"])){status();exit;}

if(isset($_GET["dansguardian-status"])){dansguardian_status();exit;}
if(isset($_GET["groups"])){groups();exit;}
if(isset($_GET["dansguardian-service-status"])){dansguardian_service_status();exit;}
if(isset($_GET["dansguardian-service_status-nofilters"])){dansguardian_service_status_nofilters();exit;}

if(isset($_POST["Delete-Group"])){groups_delete();exit;}

if(isset($_GET["ufdbguard"])){ufdbguard_service_section();exit;}
if(isset($_GET["ufdbguard-options"])){ufdbguard_service_options();exit;}
if(isset($_GET["js-ufdbguard"])){ufdbguard_service_js();exit;}
if(isset($_POST["DisableAllFilters"])){DisableAllFilters();exit;}
if(isset($_POST["EnableMalWarePatrol"])){EnableMalWarePatrol();exit;}
if(isset($_GET["disable-haarp-js"])){Disable_haarp_js();exit;}
if(isset($_POST["disable-haarp"])){Disable_haarp_perform();exit;}
tabs();


function DisableAllFilters(){
	$sock=new sockets();
	$sock->SET_INFO("SquidDisableAllFilters", $_POST["value"]);
	$sock->getFrameWork("cmd.php?squid-reload=yes");
}
function EnableMalWarePatrol(){
	$sock=new sockets();
	$sock->SET_INFO("EnableMalwarePatrol", $_POST["value"]);
	$sock->getFrameWork("cmd.php?squid-reload=yes");
}

function Disable_haarp_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$confirm=$tpl->javascript_parse_text("{confirm_disable_haarp}");
	$t=time();
	$page=CurrentPageName();
	$html="
			
	var xaction$t= function (obj) {
		var results=obj.responseText;
		if(results.length>5){alert(results);}
		CacheOff();
		Loadjs('squid.restart.php?ApplyConfToo=yes&ask=yes');
	}
	
	function action$t(){
		if(!confirm('$confirm')){return;}
		var XHR = new XHRConnection();
		XHR.appendData('disable-haarp','yes');
		XHR.sendAndLoad('$page', 'POST',xaction$t);	
	}
	action$t();
	";
	echo $html;
	
}

function Disable_haarp_perform(){
	$sock=new sockets();
	$sock->SET_INFO("EnableHaarp",0);
	$sock->getFrameWork("haarp.php?restart=yes");
	
}


function tabs(){
	if(GET_CACHED(__FILE__, __FUNCTION__,null)){return ;}
	
	$squid=new squidbee();
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	
	$SquidActHasReverse=$sock->GET_INFO("SquidActHasReverse");
	if($squid->isNGnx()){$SquidActHasReverse=0;}
	$UfdbGuardHide=$sock->GET_INFO("UfdbGuardHide");
	$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	if(!is_numeric($SquidActHasReverse)){$SquidActHasReverse=0;}
	if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}	
	if(!is_numeric($UfdbGuardHide)){$UfdbGuardHide=0;}	

	if($users->SQUID_INSTALLED){
		if(!$users->PROXYTINY_APPLIANCE){
			if($DisableArticaProxyStatistics==0){
				$StatsPerfsSquidAnswered=$sock->GET_INFO("StatsPerfsSquidAnswered");
				if(!is_numeric($StatsPerfsSquidAnswered)){$StatsPerfsSquidAnswered=0;}
				if(!$users->WEBSTATS_APPLIANCE){if($StatsPerfsSquidAnswered==0){$CPU=$users->CPU_NUMBER;$MEM=$users->MEM_TOTAL_INSTALLEE;if(($CPU<4) AND (($MEM<3096088))){WARN_SQUID_STATS();die();}}}
			}
		}
	}

	if($UnlockWebStats==1){$EnableRemoteStatisticsAppliance=0;}



	if($EnableWebProxyStatsAppliance==1){$users->APP_UFDBGUARD_INSTALLED=true;$squid->enable_UfdbGuard=1;}

	if($EnableRemoteStatisticsAppliance==0){
	
		$array["acls"]='{acls}';
		$array["bandwidth"]='{bandwidth}';
	
	}

	$array["macros"]='{macros}';
	
	//$array["unveiltech"]="{WebFilter_SaaS}";
	
	
	
	
	//$array["quotas"]='{quotas}';
	
	$array["browser-rules"]="{browsers_rules}";
	

	if($EnableRemoteStatisticsAppliance==0){
		
		$array["groups"]='{proxy_objects}';
		
		
	}

	if($users->PROXYTINY_APPLIANCE){
		unset($array["ufdbguard"]);
		unset($array["rules"]);
		unset($array["databases"]);
	}
	
	if($SquidActHasReverse==1){
		unset($array["ufdbguard"]);
		unset($array["rules"]);
		unset($array["databases"]);		
	}
	
	if(isset($_GET["without-acl"])){unset($array["acls"]);}
	
	
	
	
	
	
	


	$fontsize=18;
	if(count($array)>7){$fontsize=18;}

	if(count($array)>8){$fontsize=16;}
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="bandwidth"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.bandwith.php\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;			
			
		}
		
		
		if($num=="browser-rules"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.browsers-rules.php?popup=yes\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="unveiltech"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"unveiltech.saas.php\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;			
			
		}
		
		if($num=="groups"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.acls.groups.php?as-big=yes\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
		
		}
		
		if($num=="c-icap"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"c-icap.index.php?main=index\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
		
		}		
		
		if($num=="macros"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.macros.php\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
		
		}		
		

		if($num=="rules"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"dansguardian2.mainrules.php\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
				
		}
		
		if($num=="pdns"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"pdns.filters.php\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
		
		}		

		if($num=="acls"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.acls-rules.php\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
				
		}

		if($num=="quotas"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"squid.helpers.quotas.tabs.php\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
		
		}


	$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
	}



	$html=build_artica_tabs($html,'main_dansguardian_tabs',1150)."<script>LeftDesign('webfiltering-white-256-opac20.png');</script>";
	
	echo $html;

}

function status_left(){

}


function groups(){
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	
	$array["section_basic_filters-groups"]='{proxy_objects}';
	$array["groups-filters"]='{groups_for_rules}';
	$time=time();

	if(!$users->APP_UFDBGUARD_INSTALLED){
		unset($array["groups-filters"]);
	}


	while (list ($num, $ligne) = each ($array) ){

		if($num=="groups-macs"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"domains.user.computer.php\"><span style='font-size:20px'>$ligne</span></a></li>\n");
			continue;
				
		}


		if($num=="section_basic_filters-groups"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.acls.groups.php\"><span style='font-size:20px'>$ligne</span></a></li>\n");
			continue;
				
		}


		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$time\"><span style='font-size:20px'>$ligne</span></a></li>\n");
	}



	echo build_artica_tabs($html, "main_dansguardiangroups_tabs");
	

}






function status(){
	$page=CurrentPageName();
	$tpl=new templates();
	$html="
	<table style='width:100%'>
	<tbody>
	<tr>
		<td valign='top'><div id='dansguardian-status'></div>
			<center id='dansguardian-statistics-status' class=form style='width:100%;margin:0px'></center>
		</td>
		<td valign='top'><div id='dansguardian-service-status'></div>
	</tr>
	</tbody>
	</table>
	<script>
		LoadAjax('dansguardian-status','$page?dansguardian-status=yes');
		
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);

}

function status_users(){
	
	$ldap=new clladp();
	$sock=new sockets();
	if($ldap->IsKerbAuth()){
		include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
		$ad=new external_ad_search();
		$users=$ad->NumUsers();
		return "<tr>
	<td width=1%><span id='AdSquidStatusLeft35'><img src='img/member-24.png'></span></td>
	<td class=legend nowrap style='font-size:12px'>{members}:</td>
	<td><div style='font-size:12px' nowrap>
	<a href=\"javascript:blur();\"
	OnClick=\"javascript:Loadjs('squid.adker.php',true);\"
	style='font-size:12px;font-weight:bold;text-decoration:underline'>$users</a></td>
	</tr>";
	
	}
	
	$EnableMacAddressFilter=$sock->GET_INFO("EnableMacAddressFilter");
	if(!is_numeric($EnableMacAddressFilter)){$EnableMacAddressFilter=1;}
	$q=new mysql_squid_builder();
	if($EnableMacAddressFilter==1){
		
		$sql="SELECT MAC FROM UserAutDB GROUP BY MAC";
		$results=$q->QUERY_SQL($sql);
		$users=mysql_num_rows($results);
		$js="Loadjs('squid.UserAutDB.php?filterby=MAC',true);";
	}else{
		$sql="SELECT ipaddr FROM UserAutDB GROUP BY ipaddr";
		$results=$q->QUERY_SQL($sql);
		$users=mysql_num_rows($results);
		$js="Loadjs('squid.UserAutDB.php?filterby=ipaddr',true);";
		
	}
	return
	
	"<tr>
	<td width=1%><span id='AdSquidStatusLeft35'><img src='img/member-24.png'></span></td>
	<td class=legend nowrap style='font-size:12px'>{members}:</td>
	<td><div style='font-size:12px' nowrap>
	<a href=\"javascript:blur();\"
	OnClick=\"javascript:$js\"
	style='font-size:12px;font-weight:bold;text-decoration:underline'>$users</a></td>
	</tr>";	
	
	
	
}


function dansguardian_status($asroot=false){
	
	$page=CurrentPageName();
	
	
	
	
	$users=new usersMenus();
	
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$categories=$q->LIST_TABLES_CATEGORIES();
	$sock=new sockets();
	$squid=new squidbee();
	$SquidGuardIPWeb=trim($sock->GET_INFO("SquidGuardIPWeb"));
	$SquidGuardServerName=$sock->GET_INFO("SquidGuardServerName");
	$SquidDisableAllFilters=$sock->GET_INFO("SquidDisableAllFilters");
	$SquideCapAVEnabled=$sock->GET_INFO("SquideCapAVEnabled");
	$kavicapserverEnabled=$sock->GET_INFO("kavicapserverEnabled");
	$EnableSquidRemoteMySQL=$sock->GET_INFO("EnableSquidRemoteMySQL");
	if(!is_numeric($EnableSquidRemoteMySQL)){$EnableSquidRemoteMySQL=0;}
	$EnableSplashScreen=$sock->GET_INFO("EnableArticaHotSpot");
	$PdnsHotSpot=$sock->GET_INFO("EnableSplashScreen");
	$EnableMalwarePatrol=$sock->GET_INFO("EnableMalwarePatrol");
	$AsSquidLoadBalancer=$sock->GET_INFO("AsSquidLoadBalancer");
	$SquidActHasReverse=$sock->GET_INFO("SquidActHasReverse");
	if($squid->isNGnx()){$SquidActHasReverse=0;}
	$UfdbEnabledCentral=$sock->GET_INFO('UfdbEnabledCentral');
	$AntivirusEnabledCentral=$sock->GET_INFO('AntivirusEnabledCentral');
	$EnableKerbAuthCentral=$sock->GET_INFO('EnableKerbAuthCentral');
	$EnableUfdbGuard=intval($sock->EnableUfdbGuard());
	$DnsFilterCentral=$sock->GET_INFO('DnsFilterCentral');
	$SquidBubbleMode=$sock->GET_INFO('SquidBubbleMode');
	
	$EnableCNTLM=$sock->GET_INFO("EnableCNTLM");
	$EnableRDPProxy=$sock->GET_INFO("EnableRDPProxy");
	$EnableLocalDNSMASQ=$sock->GET_INFO("EnableLocalDNSMASQ");
	$WizardStatsApplianceDisconnected=intval($sock->GET_INFO("WizardStatsApplianceDisconnected"));
	$SquidNoAccessLogs=intval($sock->GET_INFO("SquidNoAccessLogs"));
	
	$EnableFTPProxy=$sock->GET_INFO('EnableFTPProxy');
	
	
	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("SquidWatchdogMonitConfig")));
	$Watchdog=$MonitConfig["watchdog"];
	$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
	if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}
	if($UnlockWebStats==1){$EnableRemoteStatisticsAppliance=0;}

	$EnableHaarp=$sock->GET_INFO("EnableHaarp");
	if(!is_numeric($EnableHaarp)){$EnableHaarp=0;}
	
	// APP_HAARP $EnableHaarp
	
	
	if(!is_numeric($EnableRDPProxy)){$EnableRDPProxy=0;}
	if(!is_numeric($EnableFTPProxy)){$EnableFTPProxy=0;}
	$PDSNInUfdb=$sock->GET_INFO("PDSNInUfdb");
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	if(!is_numeric($EnableUfdbGuard)){$EnableUfdbGuard=0;}
	if(!is_numeric($SquideCapAVEnabled)){$SquideCapAVEnabled=0;}	
	if(!is_numeric($EnableMalwarePatrol)){$EnableMalwarePatrol=0;}
	if(!is_numeric($SquidDisableAllFilters)){$SquidDisableAllFilters=0;}
	if(!is_numeric($EnableSplashScreen)){$EnableSplashScreen=0;}
	if(!is_numeric($PdnsHotSpot)){$PdnsHotSpot=0;}
	if(!is_numeric($AsSquidLoadBalancer)){$AsSquidLoadBalancer=0;}
	if(!is_numeric($SquidActHasReverse)){$SquidActHasReverse=0;}
	if(!is_numeric($kavicapserverEnabled)){$kavicapserverEnabled=0;}
	if(!is_numeric($SquidBubbleMode)){$SquidBubbleMode=0;}
	if(!is_numeric($EnableCNTLM)){$EnableCNTLM=0;}

	if(!is_numeric($Watchdog)){$Watchdog=1;}
	$t=1;
	
	if(!is_numeric($DnsFilterCentral)){$DnsFilterCentral=0;}
	if(!is_numeric($EnableLocalDNSMASQ)){$EnableLocalDNSMASQ=0;}
	
	if($users->APP_CHILLI_INSTALLED){
		$EnableChilli=$sock->GET_INFO("EnableChilli");
		if(!is_numeric($EnableChilli)){$EnableChilli=0;}
		$EnableSplashScreen=$EnableChilli;
	}
	
	if(!is_numeric($PDSNInUfdb)){$PDSNInUfdb=0;}
	if($PdnsHotSpot==1){$EnableSplashScreen=1;}
	
	if($EnableRemoteStatisticsAppliance==1){
		if(is_numeric($EnableKerbAuthCentral)){$EnableKerbAuth=$EnableKerbAuthCentral;}
		if(is_numeric($DnsFilterCentral)){$PDSNInUfdb=$DnsFilterCentral;}
		if(is_numeric($UfdbEnabledCentral)){$EnableUfdbGuard=$UfdbEnabledCentral;}
		
		if(is_numeric($AntivirusEnabledCentral)){
				$SquideCapAVEnabled=$AntivirusEnabledCentral;
				$kavicapserverEnabled=$AntivirusEnabledCentral;
		}
	}
	
	
	
	if($SquidGuardIPWeb==null){$SquidGuardApachePort=$sock->GET_INFO("SquidGuardApachePort");if(!is_numeric($SquidGuardApachePort)){$SquidGuardApachePort=9020;}$fulluri="http://".$_SERVER['SERVER_ADDR'].':'.$SquidGuardApachePort."/ufdbguardd.php";$sock->SET_INFO("SquidGuardIPWeb", $fulluri);}
	if($SquidGuardServerName==null){$sock->SET_INFO("SquidGuardServerName",$_SERVER['SERVER_ADDR']);}
	$eCapClam=null;


	$pic="status_ok-grey.png";
	$picSplashScreen="status_ok-grey.png";
	
	$picSquidBubbleMode="status_ok-grey.png";
	$SquidBubbleModeText="{disabled}";
	
	
	// APP_HAARP $EnableHaarp
	
	
	$picFTPMode="status_ok-grey.png";
	$picDNSMode="status_ok-grey.png";
	
	$EnableFTPProxyText="<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('ftp.proxy.php');\"
		style='font-size:12px;font-weight:bold;text-decoration:underline'>{disabled}</a>";
	
	$EnableLocalDNSMASQText="<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('squid.popups.php?script=dns');\"
		style='font-size:12px;font-weight:bold;text-decoration:underline'>{disabled}</a>";
	
	$EnableHaarpText="<span style='font-size:12px;font-weight:bold;text-decoration:underline'>{disabled}</span>";
	$picHaarp="status_ok-grey.png";
	$picCNTLM="status_ok-grey.png";
	$picRDPProxy="status_ok-grey.png";
	$picAccessLogs="status_ok.png";
	$EnableAccessLogsText="<a href=\"javascript:blur();\"
			OnClick=\"javascript:Loadjs('squid.NoAccessLogs.php');\"
			style='font-size:12px;font-weight:bold;text-decoration:underline'>{enabled}</a>";
	
	
	$status_users=status_users();
	
	
	if(!$users->dnsmasq_installed){
		$EnableLocalDNSMASQText="-";
	}else{
		if($EnableLocalDNSMASQ==1){
			$picDNSMode="status_ok.png";
			$EnableLocalDNSMASQText="<a href=\"javascript:blur();\"
			OnClick=\"javascript:Loadjs('squid.popups.php?script=dns');\"
			style='font-size:12px;font-weight:bold;text-decoration:underline'>{enabled}</a>";
		}
	}	
	
	
	if(!$users->APP_FTP_PROXY){
		$EnableFTPProxyText="-";
	}else{
		if($EnableFTPProxy==1){
			$picFTPMode="status_ok.png";
			$EnableFTPProxyText="<a href=\"javascript:blur();\"
			OnClick=\"javascript:Loadjs('ftp.proxy.php');\"
			style='font-size:12px;font-weight:bold;text-decoration:underline'>{enabled}</a>";
		}
	}
	
	if(!$users->HAARP_INSTALLED){
		$EnableHaarpText="-";
	}else{
		if($EnableHaarp==1){
			$picHaarp="status_ok.png";
			$EnableHaarpText="<a href=\"javascript:blur();\"
			OnClick=\"javascript:Loadjs('$page?disable-haarp-js=yes',true);\"
			style='font-size:12px;font-weight:bold;text-decoration:underline'>{enabled}</a>";
		}
	}

	if(!$users->CNTLM_INSTALLED){
		$EnableCNTLMText="-";
		}else{
			$EnableCNTLMText="<a href=\"javascript:blur();\"
				OnClick=\"javascript:Loadjs('squid.adker.php',true);\"
				style='font-size:12px;font-weight:bold;text-decoration:underline'>{disabled}</a>";
			
			if($EnableCNTLM==1){
				$picCNTLM="status_ok.png";
				$EnableCNTLMText="<a href=\"javascript:blur();\"
				OnClick=\"javascript:Loadjs('squid.adker.php',true);\" 
				style='font-size:12px;font-weight:bold;text-decoration:underline'>{enabled}</a>";
			}
		}

	
		
	if($SquidNoAccessLogs==1){
		$picAccessLogs="status_ok-grey.png";
		$EnableAccessLogsText="<a href=\"javascript:blur();\"
			OnClick=\"javascript:Loadjs('squid.NoAccessLogs.php');\"
			style='font-size:12px;font-weight:bold;text-decoration:underline'>{disabled}</a>";
		
	}
	
	
	
	$EnableActiveDirectoryText="<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('squid.adker.php',true);\" 
		style='font-size:12px;font-weight:bold;text-decoration:underline'>{disabled}</a>";
	
	$EnableSplashScreenText="<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('squid.webauth.php',true);\" 
		style='font-size:12px;font-weight:bold;text-decoration:underline'>{disabled}</a>";


	

	if($SquidBubbleMode==1){
		$SquidBubbleModeText="{enabled}";		
		$picSquidBubbleMode="status_ok.png";
	}
	
	
	$SquidBubbleModeTR="<tr>
	<td width=1%><span id='AdSquidStatusLeft3'><img src='img/$picSquidBubbleMode'></span></td>
	<td class=legend nowrap style='font-size:12px'>{bubble_mode}:</td>
	<td><div style='font-size:12px' nowrap>
	<a href=\"javascript:blur();\"
	OnClick=\"javascript:Loadjs('squid.bubble.php');\"
	style='font-size:12px;font-weight:bold;text-decoration:underline'>$SquidBubbleModeText</a></td>
	</tr>";

	if($EnableKerbAuth==1){
		$pic="status_ok.png";
		$EnableActiveDirectoryText="<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('squid.adker.php',true);\" 
		style='font-size:12px;font-weight:bold;text-decoration:underline'>{enabled}</a>";		

	}
	
	if($EnableSplashScreen==1){
		$picSplashScreen="status_ok.png";
		$EnableSplashScreenText="<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('squid.webauth.php',true);\" 
		style='font-size:12px;font-weight:bold;text-decoration:underline'>{enabled}</a>";				
	}
	
	
	$EnableActiveDirectoryTextTR="<tr>
				<td width=1%><span id='AdSquidStatusLeft'><img src='img/$pic'></span></td>
				<td class=legend style='font-size:12px'>Active Directory:</td>
				<td><div style='font-size:12px' nowrap>$EnableActiveDirectoryText</td>
				</tr>";	

	
	$EnableFTPProxyTextTR="<tr>
	<td width=1%><span id='AdSquidStatusLeft'><img src='img/$picFTPMode'></span></td>
	<td class=legend style='font-size:12px'>{APP_FTP_PROXY}:</td>
	<td><div style='font-size:12px' nowrap>$EnableFTPProxyText</td>
	</tr>";	
	//APP_FTP_PROXY // $EnableFTPProxy
	
	
	$EnableHaarpTextTR="<tr>
	<td width=1%><span id='AdSquidStatusLeft'><img src='img/$picHaarp'></span></td>
	<td class=legend style='font-size:12px'>{APP_HAARP}:</td>
	<td><div style='font-size:12px' nowrap>$EnableHaarpText</td>
	</tr>";	
	
	$EnableCNTLMTextTR="<tr>
	<td width=1%><span id='AdSquidStatusLeft'><img src='img/$picCNTLM'></span></td>
	<td class=legend style='font-size:12px'>{APP_CNTLM}:</td>
	<td><div style='font-size:12px' nowrap>$EnableCNTLMText</td>
	</tr>";	
	

	
	
	


	
	if($AsSquidLoadBalancer==1){
		$t++;
		$AsSquidLoadBalancerText="<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('squid.loadbalancer.main.php?js=yes');\" 
		style='font-size:12px;font-weight:bold;text-decoration:underline'>{enabled}</a>";	
		$AsSquidLoadBalancerText="<tr>
				<td width=1%><span id='AdSquidStatusLeft3'><img src='img/status_ok.png'></span></td>
				<td class=legend style='font-size:12px'>Load-balancer:</td>
				<td><div style='font-size:12px' nowrap>$AsSquidLoadBalancerText</td>
				</tr>";		
		
	}
	// ----------------------------------------------------------------------------------------------------------------	
	$EnableRemoteStatisticsAppliancePic="status_ok-grey.png";
	$EnableRemoteStatisticsApplianceText="{disabled}";
	if($EnableSquidRemoteMySQL==1){
		$EnableRemoteStatisticsAppliancePic="status_ok.png";
		$EnableRemoteStatisticsApplianceText="{enabled}";
	}
	
	if($WizardStatsApplianceDisconnected==1){
		$EnableRemoteStatisticsAppliancePic="status_ok.png";
		$EnableRemoteStatisticsApplianceText="{disconnected_mode}";
		
	}
	
	
	$EnableRemoteStatisticsApplianceTextTR="<tr>
				<td width=1%><span id='AdSquidStatusLeft3'><img src='img/$EnableRemoteStatisticsAppliancePic'></span></td>
				<td class=legend style='font-size:12px'>Stats Appliance:</td>
				<td><div style='font-size:12px' nowrap>
				<a href=\"javascript:blur();\"
				OnClick=\"javascript:Loadjs('squid.stats-appliance.php');\" 
				style='font-size:12px;font-weight:bold;text-decoration:underline'>$EnableRemoteStatisticsApplianceText</a></td>
				</tr>";		
	
	// ----------------------------------------------------------------------------------------------------------------	
	$EnableWatchdogPic="status_ok-grey.png";
	$EnableWatchdogText="{disabled}";
	if($Watchdog==1){
		$EnableWatchdogPic="status_ok.png";
		$EnableWatchdogText="{enabled}";
	}	
	
	$t++;
	$EnableWatchdogTextTR="<tr>
	<td width=1%><span id='AdSquidStatusLeft3'><img src='img/$EnableWatchdogPic'></span></td>
	<td class=legend nowrap style='font-size:12px'>{squid_watchdog_mini}:</td>
	<td><div style='font-size:12px' nowrap>
	<a href=\"javascript:blur();\"
	OnClick=\"javascript:Loadjs('squid.watchdog.php');\"
	style='font-size:12px;font-weight:bold;text-decoration:underline'>$EnableWatchdogText</a></td>
	</tr>";	
		
// ----------------------------------------------------------------------------------------------------------------	
	
	if($users->SQUID_REVERSE_APPLIANCE){$SquidActHasReverse=1;}
	if($squid->isNGnx()){$SquidActHasReverse=0;}
	
	if($SquidActHasReverse==1){
		$SquidBubbleModeTR=null;
		$t++;
		$AsSquidLoadBalancerText="<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('squid.reverse.websites.php');\" 
		style='font-size:12px;font-weight:bold;text-decoration:underline'>{enabled}</a>";	
		$AsSquidLoadBalancerText="<tr>
				<td width=1%><span id='AdSquidStatusLeft2'><img src='img/status_ok.png'></span></td>
				<td class=legend style='font-size:12px'>{squid_reverse_proxy}:</td>
				<td><div style='font-size:12px' nowrap>$AsSquidLoadBalancerText</td>
				</tr>";			
		
	}
	
	if($users->SQUID_REVERSE_APPLIANCE){
		$AsSquidLoadBalancerText="<tr>
				<td width=1%><span id='AdSquidStatusLeft2'><img src='img/status_ok-grey.png'></span></td>
				<td class=legend style='font-size:12px'>{squid_reverse_proxy}:</td>
				<td><div style='font-size:12px' nowrap><a href=\"javascript:blur();\"
				OnClick=\"javascript:blur();\" 
				style='font-size:12px;font-weight:bold;text-decoration:underline'>{disabled}</a></td>
				</tr>";	
	}
	
	
	if($users->NGINX_INSTALLED){
		$EnableNginx=$sock->GET_INFO("EnableNginx");
		if(!is_numeric($EnableNginx)){$EnableNginx=1;}
		if($EnableNginx==1){
		$AsSquidLoadBalancerText="<tr>
				<td width=1%><span id='AdSquidStatusLeft2'><img src='img/status_ok.png'></span></td>
				<td class=legend style='font-size:12px'>{squid_reverse_proxy}:</td>
				<td><div style='font-size:12px' nowrap><a href=\"javascript:blur();\"
				OnClick=\"javascript:Loadjs('squid.nginx.php');\"
				style='font-size:12px;font-weight:bold;text-decoration:underline'>{enabled}</a></td>
				</tr>";		
		}else{
			$AsSquidLoadBalancerText="<tr>
				<td width=1%><span id='AdSquidStatusLeft2'><img src='img/status_ok-grey.png'></span></td>
				<td class=legend style='font-size:12px'>{squid_reverse_proxy}:</td>
				<td><div style='font-size:12px' nowrap><a href=\"javascript:blur();\"
				OnClick=\"javascript:Loadjs('squid.nginx.php');\"
				style='font-size:12px;font-weight:bold;text-decoration:underline'>{disabled}</a></td>
				</tr>";			
		}
		
	}
	
	// ----------------------------------------------------------------------------------------------------------------	
	
	$ufdb=null;$dansgu=null;
	
	$time=time();
	
	if($EnableRemoteStatisticsAppliance==1){
		$datas=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));	
		$users->APP_UFDBGUARD_INSTALLED=true;
		$UseRemoteUfdbguardService=$datas["UseRemoteUfdbguardService"];
		if(!is_numeric($UseRemoteUfdbguardService)){$UseRemoteUfdbguardService=0;}
		
	}	
	
	
	
	if($users->APP_UFDBGUARD_INSTALLED){
		$t++;
		$APP_UFDBGUARD_INSTALLED="{installed}";

		$pic="status_ok-grey.png";
		$EnableUfdbGuardText="<a href=\"javascript:blur();\"
		OnClick=\"javascript:EnableUfdbGuard(1);\" 
		style='font-size:12px;font-weight:bold;text-decoration:underline'>{disabled}</a>";

		
		if($EnableUfdbGuard==1){
			$pic="status_ok.png";
			$EnableUfdbGuardText="<a href=\"javascript:blur();\"
			OnClick=\"javascript:EnableUfdbGuard(0);\" 
			style='font-size:12px;font-weight:bold;text-decoration:underline'>{enabled}</a>";
		}



		$ufdb="
		<tr>
			<td width=1%><span id='ufd-$time'><img src='img/$pic'></span></td>
			<td class=legend nowrap style='font-size:12px'>{APP_UFDBGUARD}:</td>
			<td><div style='font-size:12px' nowrap><span id='ufd-$time'>$EnableUfdbGuardText</span></td>
		</tr>";	

		if($users->POWER_DNS_INSTALLED){
			$t++;
			if($EnableUfdbGuard==0){$PDSNInUfdb=0;}
			
			$pic="status_ok-grey.png";
			$EnableUfdbPDNSText="<a href=\"javascript:blur();\"
			OnClick=\"javascript:Loadjs('pdns.ufdb.php');\"
			style='font-size:12px;font-weight:bold;text-decoration:underline'>{disabled}</a>";			
			
			if($EnableUfdbGuard==1){
				if($PDSNInUfdb==1){
					$pic="status_ok.png";
					$EnableUfdbPDNSText="<a href=\"javascript:blur();\"
					OnClick=\"javascript:Loadjs('pdns.ufdb.php');\"
					style='font-size:12px;font-weight:bold;text-decoration:underline'>{enabled}</a>";
				}
			}			
			
		}
		
		$ufdbPDNS="
		<tr>
			<td width=1%><span id='ufdPDNS-$time'><img src='img/$pic'></span></td>
			<td class=legend nowrap style='font-size:12px'>{dns_filter}:</td>
			<td><div style='font-size:12px' nowrap><span id='ufd-$time'>$EnableUfdbPDNSText</span></td>
		</tr>";	
		
		

	}
	

	

	if($users->DANSGUARDIAN_INSTALLED){
		$t++;
		$pic=null;
		$DANSGUARDIAN_INSTALLED="{installed}";
		$DansGuardianEnabled=$sock->GET_INFO("DansGuardianEnabled");
		if(!is_numeric($DansGuardianEnabled)){$DansGuardianEnabled=0;}

		$DansGuardianEnabledText="<a href=\"javascript:blur();\"
		OnClick=\"javascript:EnableDansguardian(1);\" 
		style='font-size:12px;font-weight:bold;text-decoration:underline'>{disabled}</a>";
		$pic="status_ok-grey.png";


		if($DansGuardianEnabled==1){
			$DansGuardianEnabledText="<a href=\"javascript:blur();\"
			OnClick=\"javascript:EnableDansguardian(0);\" 
			style='font-size:12px;font-weight:bold;text-decoration:underline'>{enabled}</a>";			
			$pic="status_ok.png";
				
		}
		$dansgu="<tr>
			<td width=1%><span id='dans-$time'><img src='img/$pic'></td>
			<td class=legend nowrap style='font-size:12px'>{APP_DANSGUARDIAN}:</td>
			<td><div style='font-size:12px' nowrap>$DansGuardianEnabledText</td>
			</tr>";			

	}
	
	$SplashScreenFinal="<tr>
			<td width=1%><span id='spalsh-$time'><img src='img/$picSplashScreen'></td>
			<td class=legend nowrap style='font-size:12px'>HotSpot:</td>
			<td><div style='font-size:12px' nowrap>$EnableSplashScreenText</td>
			</tr>";	
	
	if(!$users->KASPERSKY_WEB_APPLIANCE){
		$pic="status_ok-grey.png";
		$eCapAVText="{not_installed}";


		if($users->ECAPAV_INSTALLED){
			if($SquideCapAVEnabled==0){
				$eCapAVText="<a href=\"javascript:blur();\"
				OnClick=\"javascript:EnableeCapAV(1);\" 
				style='font-size:12px;font-weight:bold;text-decoration:underline'>{disabled}</a>";	
			}else{
				$eCapAVText="<a href=\"javascript:blur();\"
				OnClick=\"javascript:EnableeCapAV(0);\" 
				style='font-size:12px;font-weight:bold;text-decoration:underline'>{enabled}</a>";			
				$pic="status_ok.png";

			}
				
				
				
		}

		$eCapClam="
			<tr>
				<td width=1%><span id='ecapav-$time'><img src='img/$pic'></span></td>
				<td class=legend style='font-size:12px'>{APP_ECAPAV}:</td>
				<td><div style='font-size:12px' nowrap>$eCapAVText</td>
			</tr>";			


	}


	if($users->KAV4PROXY_INSTALLED){
		$t++;
		$kavicapserverEnabledText="<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('kav4proxy.php?js-popup=yes');\" 
		style='font-size:12px;font-weight:bold;text-decoration:underline'>{disabled}</a>";
		
		$pic="status_ok-grey.png";

		if($kavicapserverEnabled==1){
			$kavicapserverEnabledText="<a href=\"javascript:blur();\"
			OnClick=\"javascript:Loadjs('kav4proxy.php?js-popup=yes');\" 
			style='font-size:12px;font-weight:bold;text-decoration:underline'>{enabled}</a>";			
			$pic="status_ok.png";

		}

		$kav="<tr>
			<td width=1%><span id='kav4-$time'><img src='img/$pic'></span></td>
			<td class=legend nowrap>Kaspersky:</td>
			<td><div style='font-size:12px' nowrap>$kavicapserverEnabledText</td>
			</tr>";			
	}else{
		$pic="status_ok-grey.png";
		$kavicapserverEnabledText="<a href=\"javascript:blur();\"
			OnClick=\"javascript:Loadjs('Kav4Proxy.install.php')\" 
			style='font-size:12px;font-weight:bold;text-decoration:underline'>{installation}</a>";
		$kav="<tr>
			<td width=1%><img src='img/$pic'></td>
			<td class=legend nowrap style='font-size:12px'>Kaspersky:</td>
			<td><div style='font-size:12px' nowrap>$kavicapserverEnabledText</td>
			</tr>";			
	}


	if($users->C_ICAP_INSTALLED){
		$CicapEnabled=$sock->GET_INFO("CicapEnabled");
		if(!is_numeric($CicapEnabled)){$CicapEnabled=0;}
		if($users->WEBSTATS_APPLIANCE){$CicapEnabled=1;}
		$pic="status_ok-grey.png";
		$CicapEnabledText="<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('c-icap.index.php',true);\" 
		style='font-size:12px;font-weight:bold;text-decoration:underline'>{disabled}</a>";


		if($CicapEnabled==1){
			$CicapEnabledText="<a href=\"javascript:blur();\"
			OnClick=\"javascript:Loadjs('c-icap.index.php',true);\" 
			style='font-size:12px;font-weight:bold;text-decoration:underline'>{enabled}</a>";			
			$pic="status_ok.png";

		}
		if($users->APP_KHSE_INSTALLED){
			$KavMetascannerEnable=$sock->GET_INFO("KavMetascannerEnable");
			if(!is_numeric($KavMetascannerEnable)){$KavMetascannerEnable=0;}
			if($KavMetascannerEnable==1){$CicapEnabledText="<span style='font-size:12px;font-weight:bold;'>{enabled}</span>";}
		}
			
		$cicap="<tr>
				<td width=1%><span id='cicap-$time'><img src='img/$pic'></span></td>
				<td class=legend style='font-size:12px'>Antivirus:</td>
				<td><div style='font-size:12px' nowrap>$CicapEnabledText</td>
				</tr>";


		if($users->APP_KHSE_INSTALLED){
			$t++;
			$pic="status_ok-grey.png";
			$KavMetascannerEnableText="<a href=\"javascript:blur();\"
			OnClick=\"javascript:EnableMetaScan(1);\" 
			style='font-size:12px;font-weight:bold;text-decoration:underline'>{disabled}</a>";			

			if($KavMetascannerEnable==1){
				$KavMetascannerEnableText="<a href=\"javascript:blur();\"
				OnClick=\"javascript:EnableMetaScan(0);\" 
				style='font-size:12px;font-weight:bold;text-decoration:underline'>{enabled}</a>";			
				$pic="status_ok.png";
			}
				

			$kavMeta="
				<tr>
				<td width=1%><span id='kavmeta-$time'><img src='img/$pic'></span></td>
				<td class=legend style='font-size:12px'>{APP_KAVMETASCANNER}:</td>
				<td><div style='font-size:12px' nowrap>$KavMetascannerEnableText</td>
				</tr>";			
		}else{
			$kavMeta="
					<tr>
					<td width=1%><img src='img/$pic'></td>
					<td class=legend style='font-size:12px'>{APP_KAVMETASCANNER}:</td>
					<td><div style='font-size:12px' nowrap>{not_installed}</td>
					</tr>";			
		}

	}else{
		$pic="status_ok-grey.png";
		$cicap="<tr>
				<td width=1%><img src='img/$pic'></td>
				<td class=legend style='font-size:12px'>Antivirus:</td>
				<td><div style='font-size:12px' nowrap>-</td>
				</tr>";			

	}


	
	//-------------------------- MALWARE PATROL --------------------------------------

	$pic="status_ok-grey.png";
	$SquidEnableMalWarePatrol="<a href=\"javascript:blur();\"
	OnClick=\"javascript:JSEnableMalWarePatrol(1);\" 
	style='font-size:12px;font-weight:bold;text-decoration:underline'>{disabled}";	


	if($EnableMalwarePatrol==1){
		$pic="status_ok.png";
		$SquidEnableMalWarePatrol="<a href=\"javascript:blur();\"
		OnClick=\"javascript:JSEnableMalWarePatrol(0);\" 
		style='font-size:12px;font-weight:bold;text-decoration:underline'>{enabled}</a>";		

	}

	$MalWarePatrol="<tr>
				<td width=1%><span id='malwarepatrol-$time'><img src='img/$pic'></span></td>
				<td class=legend style='font-size:12px'>Malware Patrol:</td>
				<td><div style='font-size:12px' nowrap>$SquidEnableMalWarePatrol</td>
			</tr>";	

	//-----------------------------------------------------------------------------------
	$MalWarePatrol=null;

	$SquidDisableAllFiltersText="<a href=\"javascript:blur();\"
		OnClick=\"javascript:JSDisableAllFilters(1);\" 
		style='font-size:12px;font-weight:bold;text-decoration:underline'>{disabled}</a>";	
	$pic="status_ok-grey.png";

	if($SquidDisableAllFilters==1){
		$pic="status_ok_red.png";
		$SquidDisableAllFiltersText="<a href=\"javascript:blur();\"
		OnClick=\"javascript:JSDisableAllFilters(0);\" 
		style='font-size:12px;font-weight:bold;text-decoration:underline'>{enabled}</a>";	


	}
	$DisableAllFilters="<tr>
					<td width=1%><span id='disableall-$time'><img src='img/$pic'></span></td>
					<td class=legend style='font-size:12px'>{disable_filters}:</td>
					<td><div style='font-size:12px' nowrap>$SquidDisableAllFiltersText</td>
					</tr>";	
	
	$EnableLocalDNSMASQTR="<tr>
					<td width=1%><span id='aaaaa-$time'><img src='img/$picDNSMode'></span></td>
					<td class=legend style='font-size:12px'>DNS:</td>
					<td><div style='font-size:12px' nowrap>$EnableLocalDNSMASQText</td>
					</tr>";	
			
			
	$EnableLocalAccessLogsTR="<tr>
					<td width=1%><span id='aaaaa-$time'><img src='img/$picAccessLogs'></span></td>
					<td class=legend style='font-size:12px'>{access_log}:</td>
					<td><div style='font-size:12px' nowrap>$EnableAccessLogsText</td>
					</tr>";	
	




	$eCapClam=null;
	if($squid->isNGnx()){$SquidActHasReverse=0;}
	
	if($SquidActHasReverse==1){
		$ufdb=null;$ufdbPDNS=null;
		$MalWarePatrol=null;
		
		$dansgu=null;
		$SplashScreenFinal=null;
		$SquidBubbleModeTR=null;
		$EnableActiveDirectoryTextTR=null;
		
	}

	if(!$users->APP_KHSE_INSTALLED){
		$kavMeta=null;
	}
	
	if($t>0){
		$table="
		<div style='width:93%' class=form>
		<table style='width:250px' class='TableRemove TableMarged'><tbody>
		$EnableWatchdogTextTR
		$EnableLocalAccessLogsTR
		$EnableActiveDirectoryTextTR
		$status_users
		$EnableCNTLMTextTR
		$EnableLocalDNSMASQTR
		$SquidBubbleModeTR
		$EnableRemoteStatisticsApplianceTextTR
		$AsSquidLoadBalancerText
		$SplashScreenFinal
		$ufdb
		$ufdbPDNS
		$eCapClam
		$dansgu
		$cicap
		$kav
		$kavMeta
		$MalWarePatrol
		$EnableHaarpTextTR
		$EnableFTPProxyTextTR
		
		$DisableAllFilters
		
		</tbody>
		</table></div>";

	}

	
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	$MEM_HIGER_1G=1;
	if(!$users->MEM_HIGER_1G){$MEM_HIGER_1G=0;}
	$t=time();
	$off="<script>UnlockPage();</script>";
	$html="
	$table
	
	<script>
		function RefreshDansguardianMainService(){
			LoadAjax('dansguardian-service-status','$page?dansguardian-service-status=yes');
		}
		
		var x_enable_plugins$t= function (obj) {
			var results=obj.responseText;
			if(results.length>5){alert(results);}
			Loadjs('squid.popups.php?x-save-plugins=yes');
		}

		function ThisisAClientStats(){
			var EnableRemoteStatisticsAppliance=$EnableRemoteStatisticsAppliance;
			if(EnableRemoteStatisticsAppliance){
				return true;
			}
			return false;
		}
		
		
		function EnableUfdbGuard(value){
			 if(ThisisAClientStats()){return 1;}
			 var XHR = new XHRConnection();
			 XHR.appendData('enable_plugins','yes');
			 XHR.appendData('enable_ufdbguardd',value);
			 XHR.sendAndLoad('squid.popups.php', 'GET',x_enable_plugins$t);	
		}
		function EnableKav4Proxy(value){
			 var MEM_HIGER_1G=$MEM_HIGER_1G;
			 if(MEM_HIGER_1G==0){alert('Not enough memory..');return;}
			 if(ThisisAClientStats()){return 1;}
			 var XHR = new XHRConnection();
			 XHR.appendData('enable_plugins','yes');
			 XHR.appendData('enable_kavproxy',value);
			 document.getElementById('kav4-$time').innerHTML='<center style=\"width:100%\"><img src=img/wait.gif></center>';
			 XHR.sendAndLoad('squid.popups.php', 'GET',x_enable_plugins$t);	
		}
		
		function EnableDansguardian(value){
			 if(ThisisAClientStats()){return 1;}
			 var XHR = new XHRConnection();
			 XHR.appendData('enable_plugins','yes');
			 XHR.appendData('enable_dansguardian',value);
			 document.getElementById('dans-$time').innerHTML='<center style=\"width:100%\"><img src=img/wait.gif></center>';
			 XHR.sendAndLoad('squid.popups.php', 'GET',x_enable_plugins$t);		
		}
		
		function JSDisableAllFilters(value){
			if(ThisisAClientStats()){return 1;}
			 var XHR = new XHRConnection();
			 XHR.appendData('DisableAllFilters','yes');
			 XHR.appendData('value',value);
			 document.getElementById('disableall-$time').innerHTML='<center style=\"width:100%\"><img src=img/wait.gif></center>';
			 XHR.sendAndLoad('$page', 'POST',x_enable_plugins);		
		}
		
		function JSEnableMalWarePatrol(value){
			if(ThisisAClientStats()){return 1;}
			 var XHR = new XHRConnection();
			 XHR.appendData('EnableMalWarePatrol','yes');
			 if(value==1){
			 	Loadjs('squid.newbee.php?warn-enable-malware-patrol-js=yes');
			 }
			 XHR.appendData('value',value);
			 document.getElementById('malwarepatrol-$time').innerHTML='<center style=\"width:100%\"><img src=img/wait.gif></center>';
			 XHR.sendAndLoad('$page', 'POST',x_enable_plugins);		
		}		

		
		function EnableeCapAV(value){
			if(ThisisAClientStats()){return 1;}
			 var XHR = new XHRConnection();
			 XHR.appendData('enable_plugins','yes');
			 XHR.appendData('enable_ecapav',value);
			 document.getElementById('ecapav-$time').innerHTML='<center style=\"width:100%\"><img src=img/wait.gif></center>';
			 XHR.sendAndLoad('squid.popups.php', 'GET',x_enable_plugins$t);	
		}			
		
		

		function EnableCiCap(value){
			if(ThisisAClientStats()){return 1;}
			 var MEM_HIGER_1G=$MEM_HIGER_1G;
			 if(MEM_HIGER_1G==0){alert('Not enough memory..');return;}
			 var XHR = new XHRConnection();
			 if(value==0){
			 	Loadjs('c-icap.index.php');
			 	return;
			 }
			 
			 
			 XHR.appendData('enable_plugins','yes');
			 XHR.appendData('enable_c_icap',value);
			 document.getElementById('cicap-$time').innerHTML='<center style=\"width:100%\"><img src=img/wait.gif></center>';
			 XHR.sendAndLoad('squid.popups.php', 'GET',x_enable_plugins$t);	
		}		
		function EnableMetaScan(value){
			 var MEM_HIGER_1G=$MEM_HIGER_1G;
			 if(MEM_HIGER_1G==0){alert('Not enough memory..');return;}
			 if(ThisisAClientStats()){return 1;}
			 var XHR = new XHRConnection();
			 XHR.appendData('enable_plugins','yes');
			 XHR.appendData('enable_metascanner',value);
			 document.getElementById('kavmeta-$time').innerHTML='<center style=\"width:100%\"><img src=img/wait.gif></center>';
			 XHR.sendAndLoad('squid.popups.php', 'GET',x_enable_plugins$t);	
		}		
		
		
		
		RefreshDansguardianMainService();
		UnlockPage();
	</script>	
	";
	
	SET_CACHED(__FILE__, __FUNCTION__, __FUNCTION__, $html);
	if($asroot){ return; }

	echo $tpl->_ENGINE_parse_body($html);
}

function ufdbguard_service_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{web_proxy}&nbsp;&nbsp;&raquo;&raquo;&nbsp;{APP_UFDBGUARD}&nbsp;&nbsp;&raquo;&raquo;&nbsp;{parameters}");
	echo "YahooWin('1000','$page?ufdbguard=yes&width=100%&service=yes','$title')";
}


function ufdbguard_service_section(){

	$squid=new squidbee();
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	$EnableSquidRemoteMySQL=$sock->GET_INFO("EnableSquidRemoteMySQL");
	if(!is_numeric($EnableSquidRemoteMySQL)){$EnableSquidRemoteMySQL=0;}
	$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
	if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}
	$EnableRemoteStatisticsAppliance=0;
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	
	if(isset($_GET["service"])){
		$array["ufdbguard-status"]="{status}";
	}
	
	$array["ufdbguard-options"]='{service_parameters}';
	

	if($EnableRemoteStatisticsAppliance==0){
		if($UnlockWebStats==0){
			if($SquidPerformance<3){
				$array["ufdbguard-blocked"]='{blocked_websites}';
				
			}
		}
	}


	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="ufdbguard-status"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"ufdbguard.status.php\" style='font-size:22px;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
		}		

		if($num=="databases"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"dansguardian2.databases.php\" style='font-size:22px;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
		}

		if($num=="ufdbguard-events"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"ufdbguard.admin.events.php\" style='font-size:22px'><span>$ligne</span></a></li>\n");
			continue;
				
		}


		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$time\" style='font-size:22px'><span>$ligne</span></a></li>\n");
	}



	echo build_artica_tabs($html, "main_ufdbguards_tabs");
	

}
function ufdbguard_service_options(){
	$sock=new sockets();
	$squid=new squidbee();
	$users=new usersMenus();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}	
	
	$width="650px";
	if(isset($_GET["width"])){$width="{$_GET["width"]}";}
	$template=Paragraphe("banned-template-64.png","{template_label}",'{template_explain}',"javascript:s_PopUp('dansguardian.template.php',800,800)");
	
	

	$recompile_all_database=Paragraphe("database-spider-compile2-64.png",
	"{recompile_all_db}","{recompile_all_db_ww_text}","javascript:Loadjs('ufdbguard.databases.php?scripts=recompile')");

	$compile_schedule=Paragraphe("clock-gold-64.png","{compilation_schedule}","{compilation_schedule_text}","javascript:Loadjs('ufdbguard.databases.php?scripts=compile-schedule')");

	$ufdbguard_conf=Paragraphe("script-64.png","ufdbguard.conf","{ufdbguard_conf_read_text}",
	"javascript:Loadjs('ufdbguard.conf.php')");

	

	$hide=Paragraphe("delete-64.png", "{hide}", "{hide_webfiltering_section}","javascript:Loadjs('ufdbguard.hide.php')");

	$youtubeSchools=Paragraphe('YoutubeSchools-64.png','Youtube For Schools','{YoutubeForSchoolsExplainT}',"javascript:Loadjs('squid.youtube-schools.php')");


	

	if($EnableWebProxyStatsAppliance==0){
		if($users->DANSGUARDIAN_INSTALLED){
			if($squid->enable_dansguardian==1){
				$template=null;
				$squidguardweb=null;
				$ufdbguard_settings=null;
				$ufdbguard_conf=null;
			}
				
		}

		if(!$users->APP_UFDBGUARD_INSTALLED){
			$squidguardweb=null;
			$ufdbguard_settings=null;
			$ufdbguard_conf=null;
			$hide=null;
				
		}

	}
	
	$PagePeeker=Paragraphe("pagepeeker-64.png","PagePeeker","{pagepeeker_icon_text}","javascript:Loadjs('squid.pagepeeker.php')");

	
	
	if($EnableRemoteStatisticsAppliance==1){
		if($UnlockWebStats==0){
			$ufdbguard_conf=null;
			$squidguardweb=null;
			$youtubeSchools=null;
			
			$PagePeeker=null;
			$recompile_all_database=null;
			$compile_schedule=null;
		}
		$unlock=Paragraphe("tables-lock-64.png","{lock_unlock}","{unlock_webstats_explain}","javascript:Loadjs('squid.webstats.unlock.php')");
	}




	
	$tr[]=$youtubeSchools;
	$tr[]=$PagePeeker;
	$tr[]=$recompile_all_database;
	$tr[]=$compile_schedule;
	$tr[]=$hide;
	$tr[]=$unlock;


	$html="
	<div class=explain style='font-size:14px'>{ufdbguard_options_explain}</div>		
	
	<center><div style='width:$width'>".CompileTr3($tr)."</div></center>";
	$tpl=new templates();
	$html= $tpl->_ENGINE_parse_body($html,'squid.index.php');
	echo $html;

}


function dansguardian_service_status(){
	$page=CurrentPageName();
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$users=new usersMenus();
	$ini->loadString(base64_decode($sock->getFrameWork('cmd.php?squid-ini-status=yes')));
	$squid=new squidbee();




	$CicapEnabled=$sock->GET_INFO('CicapEnabled');
	$EnableClamavInCiCap=$sock->GET_INFO("EnableClamavInCiCap");
	if(!is_numeric($CicapEnabled)){$CicapEnabled=0;}
	if(!is_numeric($EnableClamavInCiCap)){$EnableClamavInCiCap=1;}





	$squid_status=DAEMON_STATUS_ROUND("SQUID",$ini,null,1);
	$dansguardian_status=DAEMON_STATUS_ROUND("DANSGUARDIAN",$ini,null,1);
	$APP_SQUIDGUARD_HTTP=DAEMON_STATUS_ROUND("APP_SQUIDGUARD_HTTP",$ini,null,1);
	$APP_UFDBGUARD=DAEMON_STATUS_ROUND("APP_UFDBGUARD",$ini,null,1);
	$KAV4PROXY=DAEMON_STATUS_ROUND("KAV4PROXY",$ini,null,1);
	$CICAP=DAEMON_STATUS_ROUND("C-ICAP",$ini,null,1);


	$FRESHCLAM=DAEMON_STATUS_ROUND("FRESHCLAM",$ini,null,1);
	if($users->KASPERSKY_WEB_APPLIANCE){$FRESHCLAM=null;}
	if($users->KAV4PROXY_INSTALLED){$FRESHCLAM=null;}
	if(!$users->FRESHCLAM_INSTALLED){$FRESHCLAM=null;}
	if($CicapEnabled==0){$FRESHCLAM=null;}
	if($EnableClamavInCiCap==0){$FRESHCLAM=null;}


	$tr[]=$squid_status;
	$tr[]=$dansguardian_status;
	$tr[]=$APP_SQUIDGUARD_HTTP;
	$tr[]=$CICAP;
	$tr[]=$APP_UFDBGUARD;
	$tr[]=$KAV4PROXY;
	$tr[]=$FRESHCLAM;


	$html="$okFilterText
		<center style='padding-left:5px;margin-right:-5px'>
			<div id='nofilters'></div>
			<div style='width:550px'>".CompileTr2($tr)."</div>
		</center>
		<div style='text-align:right;width:100%'>". imgtootltip("refresh-24.png","{refresh}","RefreshDansguardianMainService()")."</div>
	<script>
		LoadAjax('nofilters','$page?dansguardian-service_status-nofilters=yes');
		
		UnlockPage();
	</script>
	
	";
	
	$off="<script>UnlockPage();</script>";
	$tpl=new templates();
	$html= $tpl->_ENGINE_parse_body($html,'squid.index.php');
	echo $html;

}
function dansguardian_service_status_nofilters(){
	$sock=new sockets();
	$users=new usersMenus();
	$okFilter=false;
	$squid=new squidbee();
	$kavicapserverEnabled=$sock->GET_INFO("kavicapserverEnabled");
	$EnableUfdbGuard=intval($sock->EnableUfdbGuard());
	if(!is_numeric($EnableUfdbGuard)){$EnableUfdbGuard=0;}
	$KAV4PROXY_INSTALLED=0;
	$APP_UFDBGUARD_INSTALLED=0;
	$KAV4PROXY_INSTALLED=0;
	if($users->DANSGUARDIAN_INSTALLED){$KAV4PROXY_INSTALLED=1;}
	if($users->APP_UFDBGUARD_INSTALLED){$APP_UFDBGUARD_INSTALLED=1;}
	if($users->KAV4PROXY_INSTALLED){$KAV4PROXY_INSTALLED=1;}

	writelogs("EnableUfdbGuard=$EnableUfdbGuard; KAV4PROXY_INSTALLED:$KAV4PROXY_INSTALLED APP_UFDBGUARD_INSTALLED:$APP_UFDBGUARD_INSTALLED KAV4PROXY_INSTALLED:$KAV4PROXY_INSTALLED",__FUNCTION__,__FILE__,__LINE__);

	if($users->DANSGUARDIAN_INSTALLED){if($squid->enable_dansguardian==1){$okFilter=true;}}
	if($users->APP_UFDBGUARD_INSTALLED){if($EnableUfdbGuard==1){$okFilter=true;}}
	if($users->KAV4PROXY_INSTALLED){if($kavicapserverEnabled==1){$okFilter=true;}}
	if($okFilter){return;}


	$okFilterText="
		<center style='margin-bottom:20px'>
		<table style='width:90%' class=form>
			<tbody>
			<tr>
				<td width=1%><img src='img/warning-panneau-64.png'></td>
				<td style='font-size:14px'>
						{dansguardian_no_filters_explain}
						<div style='text-align:right;font-size:12px'>
							<a href=\"javascript:blur();\" style='text-align:right;font-size:12px' 
							OnClick=\"Loadjs('squid.popups.php?script=plugins')\">{click_here_to_enable_filters}</a>
						</div>
				</td>
			</tr>
			</tbody>
		</tr>
		</table>
		</center>
		
		";

	$tpl=new templates();
	$html= $tpl->_ENGINE_parse_body($okFilterText,'squid.index.php');
	echo $html."<script>UnlockPage();</script>";


}
function WARN_SQUID_STATS(){$t=time();$html="<div id='$t'></div><script>LoadAjax('$t','squid.warn.statistics.php');</script>";echo $html;}

