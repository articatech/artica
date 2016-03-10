<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	include_once('ressources/class.compile.ufdbguard.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}
	if(isset($_GET["service-status"])){service_status();exit;}
	if(isset($_GET["main"])){main();exit;}
	if(isset($_POST["EnableGoogleSafeSearch"])){EnableGoogleSafeSearch_save();exit;}
	if(isset($_POST["EnableGoogleSafeBrowsing"])){EnableGoogleSafeBrowsing_save();exit;}
	if(isset($_GET["GoogleSafeBrowsingApiKey-js"])){GoogleSafeBrowsingApiKey_js();exit;}
	if(isset($_GET["GoogleSafeBrowsingApiKey-popup"])){GoogleSafeBrowsingApiKey_popup();exit;}
	if(isset($_POST["GoogleSafeBrowsingApiKey"])){GoogleSafeBrowsingApiKey_save();exit;}
	
	if(isset($_GET["PhishTankApiKey-js"])){PhishTankApiKey_js();exit;}
	if(isset($_GET["PhishTankApiKey-popup"])){PhishTankApiKey_popup();exit;}
	if(isset($_POST["PhishTankApiKey"])){PhishTankApiKey_save();exit;}
	if(isset($_POST["EnableSquidPhishTank"])){EnableSquidPhishTank_save();exit;}
	if(isset($_POST["EnableHTTPSURBL"])){EnableHTTPSURBL_save();exit;}
	if(isset($_POST["WebFilteringRansomware"])){WebFilteringRansomware_save();exit;}
	
	
	
page();


function service_cmds_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$cmd=$_GET["service-cmds"];
	$mailman=$tpl->_ENGINE_parse_body("{APP_UFDBGUARD}");
	$html="YahooWin4('650','$page?service-cmds-popup=$cmd','$mailman::$cmd');";
	echo $html;	
}
function service_cmds_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$cmd=$_GET["service-cmds-popup"];
	$t=time();
	$html="
	<div id='pleasewait-$t''><center><div style='font-size:22px;margin:50px'>{please_wait}</div><img src='img/wait_verybig_mini_red.gif'></center></div>
	<div id='results-$t'></div>
	<script>LoadAjax('results-$t','$page?service-cmds-perform=$cmd&t=$t');</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function GoogleSafeBrowsingApiKey_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{API_KEY}");
	header("content-type: application/x-javascript");
	$sock=new sockets();
	$t=time();
	
	echo "
		YahooWin6('850','$page?GoogleSafeBrowsingApiKey-popup=yes','$title');
	";
}

function PhishTankApiKey_save(){
	$sock=new sockets();
	$sock->SET_INFO("PhishTankApiKey",$_POST["PhishTankApiKey"]);
	$sock->SET_INFO("EnableSquidPhishTank",1);
	
}
function EnableSquidPhishTank_save(){
	$sock=new sockets();
	$sock->SET_INFO("EnableSquidPhishTank",$_POST["EnableSquidPhishTank"]);
}
function EnableHTTPSURBL_save(){
	$sock=new sockets();
	$sock->SET_INFO("EnableHTTPSURBL",$_POST["EnableHTTPSURBL"]);
}

function WebFilteringRansomware_save(){
	$sock=new sockets();
	$sock->SET_INFO("WebFilteringRansomware",$_POST["WebFilteringRansomware"]);	
}

function PhishTankApiKey_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{API_KEY}");
	header("content-type: application/x-javascript");
	$sock=new sockets();
	$t=time();
	
	echo "
	YahooWin6('850','$page?PhishTankApiKey-popup=yes','$title');
	";	
	
}


function PhishTankApiKey_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$PhishTankApiKey=$sock->GET_INFO("PhishTankApiKey");
	$t=time();
	
	$html="<div style='width:98%' class=form>
<table style='width:100%'>
<tr>
	<td class=legend style='font-size:18px'>{API_KEY}:</td>
	<td>". Field_text("PhishTankApiKey-$t",$PhishTankApiKey,"font-size:28px;width:650px")."</td>
</tR>
<tr>
	
". Field_button_table_autonome("{apply}", "Save$t()",36).
"</table>
</div>
<script>
var xSave$t= function (obj) {
	var res=obj.responseText;
	if(res.length>0){alert(res);}
	LoadAjaxRound('main-ufdb-frontend','ufdbguard.status.php');
	Loadjs('squid.reload.php');

}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('PhishTankApiKey', document.getElementById('PhishTankApiKey-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
";
echo $tpl->_ENGINE_parse_body($html);

		
	
}



function GoogleSafeBrowsingApiKey_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$GoogleSafeBrowsingApiKey=$sock->GET_INFO("GoogleSafeBrowsingApiKey");
	
	$GoogleSafeBrowsingCacheTime=intval($sock->GET_INFO("GoogleSafeBrowsingCacheTime"));
	if($GoogleSafeBrowsingCacheTime==0){$GoogleSafeBrowsingCacheTime=10080;}
	$GoogleSafeBrowsingDNS=$sock->GET_INFO("GoogleSafeBrowsingDNS");
	$GoogleSafeBrowsingInterface=$sock->GET_INFO("GoogleSafeBrowsingInterface");
	if($GoogleSafeBrowsingDNS==null){$GoogleSafeBrowsingDNS="8.8.8.8,4.4.4.4";}
	
	$ip=new networking();
	
	while (list ($eth, $cip) = each ($ip->array_TCP) ){
		if($cip==null){continue;}
		$arrcp[$cip]=$cip;
	}
	
	$arrcp[null]="{default}";
	
	$TTCACHE[1440]="1 {day}";
	$TTCACHE[2880]="2 {days}";
	$TTCACHE[10080]="7 {days}";
	$TTCACHE[20160]="1 {week}";
	$TTCACHE[43200]="1 {month}";
	$t=time();
	
	
	$WgetBindIpAddress=Field_array_Hash($arrcp,"GoogleSafeBrowsingInterface-$t",$GoogleSafeBrowsingInterface
			,null,null,0,"font-size:18px;padding:3px;");
	
	$html="<div style='width:98%' class=form>
<table style='width:100%'>
<tr>
	<td class=legend style='font-size:18px'>{GoogleSafeBrowsingApiKey_ask}:</td>		
	<td>". Field_text("GoogleSafeBrowsingApiKey-$t",$GoogleSafeBrowsingApiKey,"font-size:18px;width:450px")."</td>
</tR>
<tr>
	<td class=legend style='font-size:18px'>{nameserver}:</td>		
	<td>". Field_text("GoogleSafeBrowsingDNS-$t",$GoogleSafeBrowsingDNS,"font-size:18px;width:250px")."</td>
</tR>	
<tr>
	<td class=legend style='font-size:18px'>{WgetBindIpAddress}:</td>		
	<td>$WgetBindIpAddress</td>
</tR>					
<tr>
	<td class=legend style='font-size:18px'>{local_cache_time}:</td>		
	<td>". Field_array_Hash($TTCACHE, "GoogleSafeBrowsingCacheTime-$t",$GoogleSafeBrowsingCacheTime,null,null,0,"font-size:18px")."</td>
</tR>	
". Field_button_table_autonome("{apply}", "Save$t()",32).
"</table>		
	</div>	
<script>
	var xSave$t= function (obj) {
		var res=obj.responseText;
		Loadjs('dansguardian2.compile.php');
		RefreshTab('main_dansguardian_mainrules');
	}

	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('GoogleSafeBrowsingApiKey', document.getElementById('GoogleSafeBrowsingApiKey-$t').value);
		XHR.appendData('GoogleSafeBrowsingCacheTime', document.getElementById('GoogleSafeBrowsingCacheTime-$t').value);
		XHR.appendData('GoogleSafeBrowsingDNS', document.getElementById('GoogleSafeBrowsingDNS-$t').value);
		XHR.appendData('GoogleSafeBrowsingInterface', document.getElementById('GoogleSafeBrowsingInterface-$t').value);
		XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
</script>		
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}
function GoogleSafeBrowsingApiKey_save(){
	$sock=new sockets();
	$sock->SET_INFO("GoogleSafeBrowsingApiKey", $_POST["GoogleSafeBrowsingApiKey"]);
	$sock->SET_INFO("GoogleSafeBrowsingCacheTime", $_POST["GoogleSafeBrowsingCacheTime"]);
	$sock->SET_INFO("GoogleSafeBrowsingDNS", $_POST["GoogleSafeBrowsingDNS"]);
	$sock->SET_INFO("GoogleSafeBrowsingInterface", $_POST["GoogleSafeBrowsingInterface"]);
	$sock->SET_INFO("EnableGoogleSafeBrowsing", 1);
	
	

}

function service_cmds_perform(){
	$sock=new sockets();
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$datas=unserialize(base64_decode($sock->getFrameWork("ufdbguard.php?service-cmds={$_GET["service-cmds-perform"]}")));
	$html="<textarea style='height:450px;overflow:auto;width:100%;font-size:14px'>".@implode("\n", $datas)."</textarea>
<script>
	 document.getElementById('pleasewait-$t').innerHTML='';
	var flexRT;
	if( document.getElementById('WebFilteringMainTableID') ){
		flexRT=document.getElementById('WebFilteringMainTableID').value;
		$('#flexRT'+flexRT).flexReload();
	}
</script>

";
	echo $tpl->_ENGINE_parse_body($html);
}
function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	
	$SquidUFDBUrgency=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidUFDBUrgency"));
	
	
	if($SquidUFDBUrgency==1){
		echo $tpl->_ENGINE_parse_body("<center style='margin:90px'>
				<p style='font-size:20px'>{proxy_in_webfiltering_emergency_mode_explain}</p>
		
				".button("{proxy_in_webfiltering_emergency_mode}",
				"Loadjs('squid.urgency.php?ufdb=yes');",40)."</center>");
		
		
		
		return;
		
		
	}
	
	
	if($sock->EnableUfdbGuard()==0){
		if($q->COUNT_ROWS("webfilter_rules")==0){
			echo $tpl->_ENGINE_parse_body("<center style='margin:90px'>".button("{activate_the_webfiltering_engine_wizard}","Loadjs('dansguardian2.wizard.rule.php')",40)."</center>");
			return;
		}
		
		
		echo $tpl->_ENGINE_parse_body("<center style='margin:90px'>
				<p style='font-size:20px'>{warn_ufdbguard_not_activated_explain}</p>
				
				".button("{activate_webfilter_engine}",
						"Loadjs('ufdbguard.enable.progress.php')",40)."</center>");
				
				
		
		return;
		
	}
	
	
	
	
	
	
	
	$t=time();
	$WEBFILTERING_TOP_MENU=WEBFILTERING_TOP_MENU();
	$html="
	<div style='font-size:30px;margin-bottom:20px'>$WEBFILTERING_TOP_MENU</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td style='vertical-align:top;width:390px'><div id='service-status-$t'></div></td>
		<td style='vertical-align:top;width:99%;padding-left:20px'>
				<center id='rules-toolbox' style='margin:bottom:15px'></center>
				<div id='main-status-$t'></div>
		</td>
	</tr>
	</table>
	</div>
	
	<script>
		LoadAjax('service-status-$t','$page?service-status=yes&t=$t');
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function EnableGoogleSafeSearch_save(){
	$sock=new sockets();
	$sock->SET_INFO("EnableGoogleSafeSearch", $_POST["EnableGoogleSafeSearch"]);
	$sock->getFrameWork("squid.php?google-no-ssl-progress=yes");
	
}

function EnableGoogleSafeBrowsing_save(){
	$sock=new sockets();
	$sock->SET_INFO("EnableGoogleSafeBrowsing", $_POST["EnableGoogleSafeBrowsing"]);	
	
}

function main(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();	
	$sock=new sockets();
	$t=time();
	$SquidGuardIPWeb=$sock->GET_INFO("SquidGuardIPWeb");
	$SquidGuardApachePort=intval($sock->GET_INFO("SquidGuardApachePort"));
	if($SquidGuardApachePort==0){$SquidGuardApachePort=9020;}
	
	$UseRemoteUfdbguardService=intval($sock->GET_INFO("UseRemoteUfdbguardService"));
	$UfdbGuardThreads=$sock->GET_INFO("UfdbGuardThreads");
	
	$ufdbclass=new compile_ufdbguard();
	$UFDB=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
	$UFDB=$ufdbclass->SetDefaultsConfig($UFDB);
	$datas=$UFDB;
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	$EnableGoogleSafeSearch=$sock->GET_INFO("EnableGoogleSafeSearch");
	$EnableHTTPSURBL=intval($sock->GET_INFO("EnableHTTPSURBL"));
	$WebFilteringRansomware=intval($sock->GET_INFO("WebFilteringRansomware"));
	$EnableGoogleSafeBrowsing=intval($sock->GET_INFO("EnableGoogleSafeBrowsing"));
	$EnableGoogleSafeBrowsing_popupon=$tpl->javascript_parse_text("{EnableGoogleSafeBrowsing_popupon}");
	$EnableGoogleSafeBrowsing_popupoff=$tpl->javascript_parse_text("{EnableGoogleSafeBrowsing_popupoff}");
	$DisableGoogleSSL=intval($sock->GET_INFO("DisableGoogleSSL"));
	$PhishTankApiKey=$sock->GET_INFO("PhishTankApiKey");
	$EnableSquidPhishTank=intval($sock->GET_INFO("EnableSquidPhishTank"));
	
	if(!is_numeric($EnableGoogleSafeSearch)){$EnableGoogleSafeSearch=1;}

	$SquidUrgency=intval($sock->GET_INFO("SquidUrgency"));
	$ufdbguardConfig=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
	if(!is_numeric($ufdbguardConfig["DebugAll"])){$ufdbguardConfig["DebugAll"]=0;}
	if($ufdbguardConfig["DebugAll"]==1){
		echo FATAL_ERROR_SHOW_128("<strong>{webfiltering_in_debug_mode}</strong><p>&nbsp;</p>{webfiltering_in_debug_mode_text}
				<div style='margin:20px;text-align:right'>". button("{disable}","Loadjs('ufdbguard.debug.php')",20)."</div>
				
				");
		
		
	}
	
	if($SquidUrgency==1){
		echo FATAL_ERROR_SHOW_128("<strong>{proxy_in_emergency_mode}</strong><p>&nbsp;</p>{proxy_in_emergency_mode_explain}
		<div style='margin:20px;text-align:right'>". 
		button("{urgency_mode}","Loadjs('squid.urgency.php?justbutton=yes')",20)
		."</div>
		");
			
			
	}
	$GoogleSafeBrowsingApiKey=$sock->GET_INFO("GoogleSafeBrowsingApiKey");
	$UfdbUseArticaClient=$sock->GET_INFO("UfdbUseArticaClient");
	if(!is_numeric($UfdbUseArticaClient)){$UfdbUseArticaClient=1;}	
	$GoogleSafeBrowsingApiKey_color="#04A910";
	$GoogleSafeBrowsingApiKey_link=$tpl->_ENGINE_parse_body("{defined}");
	
	
	$PhishTankApiKey_color="#04A910";
	$PhishTankApiKey_link=$tpl->_ENGINE_parse_body("{defined}");
	
	
	
	if(strlen($PhishTankApiKey)<10){
		$PhishTankApiKey_color="#A90404";
		$PhishTankApiKey_link=$tpl->_ENGINE_parse_body("{not_set}");
	}
	
	
	if(strlen($GoogleSafeBrowsingApiKey)<10){
		$GoogleSafeBrowsingApiKey_color="#A90404";
		$GoogleSafeBrowsingApiKey_link=$tpl->_ENGINE_parse_body("{not_set}");
	}
	
	$EnableGoogleSafeBrowsing_field=Paragraphe_switch_img("{EnableGoogleSafeBrowsing}", 
				"{EnableGoogleSafeBrowsing_explain}
				<div style='margin-top:10px;text-align:right'>
					<a href=\"javascript:Loadjs('$page?GoogleSafeBrowsingApiKey-js=yes')\"
					style='font-size:22px;color:$GoogleSafeBrowsingApiKey_color;text-decoration:underline'>{API_KEY}:$GoogleSafeBrowsingApiKey_link</a>
			
					</div>
			<div style='margin-top:10px;text-align:right'>
					<a href=\"https://developers.google.com/safe-browsing/key_signup\"
					style='font-size:18px;text-decoration:underline' target=_new>Google:&nbsp;{free_register}</a>
					</div>
				"
					,"EnableGoogleSafeBrowsing-$t",$EnableGoogleSafeBrowsing,null,750
			);
	
	$EnableHTTPSURBL_field=Paragraphe_switch_img("{EnableHTTPSURBL}",
			"{EnableHTTPSURBL_explain}"
			,"EnableHTTPSURBL-$t",$EnableHTTPSURBL,null,750
	);
	
	$WebFilteringRansomware_field=Paragraphe_switch_img("{WebFilteringRansomware}",
			"{WebFilteringRansomware_explain}"
			,"WebFilteringRansomware-$t",$WebFilteringRansomware,null,750
	);
	
	
	
	$EnablePhishtank=Paragraphe_switch_img("{PhishTank_enable}",
			"{PhishTank_about}
			<div style='margin-top:10px;text-align:right'>
			<a href=\"javascript:Loadjs('$page?PhishTankApiKey-js=yes')\"
			style='font-size:22px;color:$PhishTankApiKey_color;text-decoration:underline'>{API_KEY}:$PhishTankApiKey_link</a>
				
			</div>
			<div style='margin-top:10px;text-align:right'>
			<a href=\"https://www.phishtank.com/api_register.php\"
			style='font-size:18px;text-decoration:underline' target=_new>PhishTank:&nbsp;{free_register}</a>
			</div>
			"
			,"EnableSquidPhishTank-$t",$EnableSquidPhishTank,null,750
	);	
	
	
	
	$EnableGoogleSafeBrowsing_button=button("{apply}","EnableGoogleSafeBrowsing$t()",26);
	$EnablePhishtank_button=button("{apply}","EnablePhishtank$t()",26);
	$EnableHTTPSURBL__button=button("{apply}","EnableHTTPSURBL$t()",26);
	$WebFilteringRansomware__button=button("{apply}","EnableRansomware$t()",26);
	
	if($UfdbUseArticaClient==0){
		$EnableGoogleSafeBrowsing_field=Paragraphe_switch_disable("{EnableGoogleSafeBrowsing}", 
				"{EnableGoogleSafeBrowsing_explain}"
				,"EnableGoogleSafeBrowsing-$t",$EnableGoogleSafeBrowsing,null,750
		);
		
		$WebFilteringRansomware_field=Paragraphe_switch_disable("{WebFilteringRansomware}",
				"{WebFilteringRansomware_explain}"
				,"WebFilteringRansomware-$t",$WebFilteringRansomware,null,750
		);		
		
		$EnablePhishtank=Paragraphe_switch_disable("{PhishTank_enable}",
				"{PhishTank_about}"
				,"EnableSquidPhishTank-$t",$EnableSquidPhishTank,null,750
		);
		
		$EnableHTTPSURBL_field=Paragraphe_switch_disable("{EnableHTTPSURBL}",
				"{EnableHTTPSURBL_explain}"
				,"EnableHTTPSURBL-$t",$EnableHTTPSURBL,null,750
		);
		
		
		$EnableGoogleSafeBrowsing_button=null;
		$EnablePhishtank_button=null;
		$EnableHTTPSURBL__button=null;
		$WebFilteringRansomware__button=null;
	}
	
	if($UFDB["UseRemoteUfdbguardService"]==1){
		$sock->SET_INFO("UseRemoteUfdbguardService", 1);
		$UseRemoteUfdbguardService=1;
	}
	
	
	if($DisableGoogleSSL==1){$DisableGoogleSSL_text="{enabled}";}else{$DisableGoogleSSL_text="{disabled}";}
	
	
	echo $tpl->_ENGINE_parse_body("<div style='margin-bottom:20px;margin-top:20px;width:98%' class=form>
			$EnablePhishtank
			<div style='text-align:right'>$EnablePhishtank_button</div>
			</div>");	
	
	echo $tpl->_ENGINE_parse_body("<div style='margin-bottom:20px;margin-top:20px;width:98%' class=form>
			$EnableHTTPSURBL_field
			<div style='text-align:right'>$EnableHTTPSURBL__button</div>
			</div>");
	
	echo $tpl->_ENGINE_parse_body("<div style='margin-bottom:20px;margin-top:20px;width:98%' class=form>
			$WebFilteringRansomware_field
			<div style='text-align:right'>$WebFilteringRansomware__button</div>
			</div>");		
	
	
	
if($UseRemoteUfdbguardService==0){	
	
	
	
	
	echo $tpl->_ENGINE_parse_body("<div style='margin-bottom:20px;margin-top:20px;width:98%' class=form>".
		Paragraphe_switch_img("{EnableGoogleSafeSearch}", "{safesearch_explain}
				<div style='margin-top:10px;text-align:right'>
					<a href=\"javascript:Loadjs('squid.google.ssl.php')\" 
					style='font-size:22px;text-decoration:underline'>{disable_google_ssl} ($DisableGoogleSSL_text)</a></div>
				
				"
			,"EnableGoogleSafeSearch-$t",$EnableGoogleSafeSearch,null,750	
			)."
					
		<div style='text-align:right'>". button("{apply}","EnableGoogleSafeSearch$t()",26)."</div>			
		</div>");
}



echo $tpl->_ENGINE_parse_body("<div style='margin-bottom:20px;margin-top:20px;width:98%' class=form>
		$EnableGoogleSafeBrowsing_field
		<div style='text-align:right'>$EnableGoogleSafeBrowsing_button</div>
		</div>");
	
	
	if($SquidGuardIPWeb==null){
		$SquidGuardIPWeb="http://".$_SERVER['SERVER_ADDR'].':'.$SquidGuardApachePort."/ufdbguardd.php";
		$fulluri="http://".$_SERVER['SERVER_ADDR'].':'.$SquidGuardApachePort."/ufdbguardd.php";
		$sock->SET_INFO("SquidGuardIPWeb", $fulluri);
	}else{
		$fulluri=$SquidGuardIPWeb;
	}
	
	if(!$users->CORP_LICENSE){
		$MyVersion="{license_error}";
	}else{
		$q=new mysql_squid_builder();
		$MyVersion=trim($sock->getFrameWork("ufdbguard.php?articawebfilter-database-version=yes"));
		$MyVersion=$q->time_to_date($MyVersion,true);
	}
	
	
	if($UseRemoteUfdbguardService==0){
	
		$wizard="
		<tr><td colspan=3>&nbsp;</td></tr>
		<tr>
			<td colspan=3 align='center'>". button("{wizard_rule}", "Loadjs('dansguardian2.wizard.rule.php')",26)."
					<div style='font-size:14px;margin-top:15px'>{wizard_rule_ufdb_explain}</div>
					
					</td>
			
		</tr>";		
		
		$build_rules="<tr><td colspan=3>&nbsp;</td></tr>
		<tr>
			<td colspan=3 align='center'>". button("{compile_rules}", "Loadjs('dansguardian2.compile.php');",26)."
					<div style='font-size:14px;margin-top:15px'>{wizard_rule_ufdb_compile_explain}</div>
					
					</td>
			
		</tr>";		
	
	}
	
	
	$t=time();
	$html="
	<div style='width:98%' class=form>
		<div style='font-size:28px;font-weight:bold'>
		<table style='width:100%;margin-top:30px'>
		<tr>
			<td style='vertical-align:middle;font-size:18px' class=legend>{listen_address}:</td>
			<td style='vertical-align:middle;font-size:18px'>{$datas["listen_addr"]}:{$datas["listen_port"]}</td>
			<td>&nbsp;</td>
		</tr>	
		<tr><td colspan=2>&nbsp;</td></tr>	
		<tr>
			<td style='vertical-align:middle;font-size:18px' class=legend>{webpage_deny_url}:</td>
			<td style='vertical-align:middle;font-size:18px'>$fulluri</td>
			<td>". button("{options}","Loadjs('ufdbguard.urichange.php')",16)."</td>
		</tr>
		<tr><td colspan=2>&nbsp;</td></tr>
		<tr>
			<td style='vertical-align:middle;font-size:18px' class=legend>{artica_databases}:</td>
			<td style='vertical-align:middle;font-size:18px'>$MyVersion</td>
			<td>". button("{options}","LoadAjax('BodyContent','artica.update.php?webfiltering-tabs=yes&from-ufdbguard=yes')",16)."</td>
		</tr>

		$wizard
		$build_rules
		
		
		</table>

		
		<script>
		
	var xEnableGoogleSafeSearch$t= function (obj) {
		var res=obj.responseText;
		Loadjs('dansguardian2.compile.php');
		
	}		
		
	function EnableGoogleSafeSearch$t(){
		if(!document.getElementById('EnableGoogleSafeSearch-$t')){ alert('Error in field, please refresh...'); return; }
		var XHR = new XHRConnection();
		XHR.appendData('EnableGoogleSafeSearch', document.getElementById('EnableGoogleSafeSearch-$t').value);
		XHR.sendAndLoad('$page', 'POST',xEnableGoogleSafeSearch$t);  
	}
	
	var xEnableGoogleSafeBrowsing$t= function (obj) {
		var res=obj.responseText;
		LoadAjaxRound('main-ufdb-frontend','ufdbguard.status.php');
		Loadjs('squid.compile.progress.php');
		
	}	
	
	var xEnableHTTPSURBL$t= function (obj) {
		var res=obj.responseText;
		LoadAjaxRound('main-ufdb-frontend','ufdbguard.status.php');
		Loadjs('squid.reload.progress.php');
		
	}	
	
	
	function EnableRansomware$t(){
		if(!document.getElementById('WebFilteringRansomware-$t')){ alert('Error in field, please refresh...'); return; }
		var XHR = new XHRConnection();
		XHR.appendData('WebFilteringRansomware', document.getElementById('WebFilteringRansomware-$t').value);
		XHR.sendAndLoad('$page', 'POST',xEnableHTTPSURBL$t);  
	}
	
	
	function EnableGoogleSafeBrowsing$t(){
		if(!document.getElementById('EnableGoogleSafeBrowsing-$t')){alert('Error in field, please refresh...'); return; }
		var XHR = new XHRConnection();
		var EnableGoogleSafeBrowsing=document.getElementById('EnableGoogleSafeBrowsing-$t').value;
		if(EnableGoogleSafeBrowsing==1){ if(!confirm('$EnableGoogleSafeBrowsing_popupon')){return;} }
		if(EnableGoogleSafeBrowsing==0){if(!confirm('$EnableGoogleSafeBrowsing_popupoff')){return;} }
		XHR.appendData('EnableGoogleSafeBrowsing', EnableGoogleSafeBrowsing);
		XHR.sendAndLoad('$page', 'POST',xEnableGoogleSafeBrowsing$t); 	
	
	}
	
	function EnableHTTPSURBL$t(){
		if(!document.getElementById('EnableHTTPSURBL-$t')){ alert('Error in field, please refresh...'); return; }
		var XHR = new XHRConnection();
		XHR.appendData('EnableHTTPSURBL', document.getElementById('EnableHTTPSURBL-$t').value);
		XHR.sendAndLoad('$page', 'POST',xEnableHTTPSURBL$t);
	
	}
	
	function  EnablePhishtank$t(){
		if(!document.getElementById('EnableSquidPhishTank-$t')){alert('Error in field, please refresh...'); return; }
		var XHR = new XHRConnection();
		var EnableSquidPhishTank=document.getElementById('EnableSquidPhishTank-$t').value;
		XHR.appendData('EnableSquidPhishTank', EnableSquidPhishTank);
		XHR.sendAndLoad('$page', 'POST',xEnableGoogleSafeBrowsing$t); 
	}
	
</script>
		
		";
	
	
	echo $tpl->_ENGINE_parse_body($html);	
}


function service_status(){
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();	
	$ini=new Bs_IniHandler();
	$sock=new sockets();
	$ini->loadFile("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS");
	
	$tr[]=DAEMON_STATUS_ROUND("APP_UFDBGUARD",$ini,null,1);
	$tr[]=DAEMON_STATUS_ROUND("APP_UFDBGUARD_CLIENT",$ini,null,1);
	$tr[]=DAEMON_STATUS_ROUND("APP_SQUIDGUARD_HTTP",$ini,null,1);
	$tr[]=DAEMON_STATUS_ROUND("APP_UFDBCAT",$ini,null,1);
	
	
	
	$status=@implode("\n", $tr);
	
	$html="
	<div id='rules-toolbox-left' style='margin-bottom:15px'></div>
	$status
	<script>
		LoadAjax('rules-toolbox-left','dansguardian2.mainrules.php?rules-toolbox-left=yes');
		LoadAjaxTiny('rules-toolbox','dansguardian2.mainrules.php?rules-toolbox=yes');
		LoadAjax('main-status-$t','$page?main=yes&t=$t');
	</script>	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}
