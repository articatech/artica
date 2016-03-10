<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.squid.watchdog.inc');
	
	$users=new usersMenus();
	if(!$users->AsProxyMonitor){
		
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		die();
		
	}
	if(isset($_GET["SWAP"])){SWAP_PAGE();exit;}
	if(isset($_GET["PING"])){PING_PAGE();exit;}
	if(isset($_GET["DNS"])){DNS_PAGE();exit;}
	if(isset($_GET["LOAD"])){LOAD_PAGE();exit;}
	if(isset($_GET["MEMORY"])){MEMORY_PAGE();exit;}
	if(isset($_GET["AD"])){ACIVE_DIRECTORY_PAGE();exit;}
	if(isset($_GET["external-page"])){EXTERNAL_PAGE();exit;}
	if(isset($_GET["performance"])){PERFORMANCE_PAGE();exit;}
	if(isset($_GET["settings"])){SETTINGS_PAGE();exit;}
	if(isset($_GET["smtp"])){SMTP_PAGE();exit;}
	if(isset($_POST["SAVEGLOBAL"])){SAVE();exit;}
	if(isset($_POST["SAVESMTP"])){SAVE_SMTP();exit;}
	if(isset($_GET["tabs"])){tabs();exit;}
	
js();

function js(){
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{watchdog_settings}");
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	echo "YahooWin4('1200','$page?tabs=yes','$title')";
	
	
}


function tabs(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	
	$array["settings"]="{global_settings}";
	$array["bandwidth"]="{bandwidth}";
	$array["performance"]="{performance}";
	$array["LOAD"]="{load}";
	$array["MEMORY"]="{memory}";
	$array["SWAP"]="SWAP";
	$array["PING"]="PING";
	$array["DNS"]="DNS";
	$array["external-page"]="{external_page}";
	$array["smtp"]="{smtp_notifications}";
	$style="style='font-size:22px';";
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="bandwidth"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.proxy.watchdog.bandwidth.php\"><span $style>$ligne</span></a></li>\n");
			continue;
		}
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span $style>$ligne</span></a></li>\n");
		
	}
	
	
	
	
	echo build_artica_tabs($html, "watchdog_settings_tabs");
	
	
	
}

	
	


function LOAD_PAGE(){
	$t=time();
	$MonitConfig=defaults_values();
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	
	
	$array["none"]="{none}";
	$array["restart"]="{restart}";
	$array["failover"]="{failover}";
	$array["reboot"]="{reboot}";
	
	$html="<div style='width:98%' class=form>
			". Paragraphe_switch_img("{server_load}", "{server_watchdog_load_explain}","LOAD_TESTS",$MonitConfig["LOAD_TESTS"],null,850)."

	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{if_overloaded}:</td>
		<td	style='font-size:22px'>". Field_text("LOAD_WARNING",$MonitConfig["LOAD_WARNING"],
				"font-size:22px;width:90px")."&nbsp;</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{if_overloaded} (MAX):</td>
		<td	style='font-size:22px'>". Field_text("LOAD_MAX",$MonitConfig["LOAD_MAX"],
				"font-size:22px;width:90px")."&nbsp;</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{action}:</td>
		<td	style='font-size:22px'>". Field_array_Hash($array,"LOAD_MAX_ACTION",$MonitConfig["LOAD_MAX_ACTION"],null,'',0,
				"font-size:22px")."&nbsp;</td>
		<td width=1%></td>
	</tr>												
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","Save$t()",36)."</td>
	</tr>
</table>
</div>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	UnlockPage();
	RefreshTab('watchdog_settings_tabs');
}
	
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('SAVEGLOBAL','yes');
	XHR.appendData('LOAD_TESTS',document.getElementById('LOAD_TESTS').value);
	XHR.appendData('LOAD_WARNING',document.getElementById('LOAD_WARNING').value);
	XHR.appendData('LOAD_MAX',document.getElementById('LOAD_MAX').value);
	XHR.appendData('LOAD_MAX_ACTION',document.getElementById('LOAD_MAX_ACTION').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function MEMORY_PAGE(){
	$t=time();
	$MonitConfig=defaults_values();
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	
	
	$array[5]="5 {minutes}";
	$array[10]="10 {minutes}";
	$array[15]="15 {minutes}";
	$array[30]="30 {minutes}";
	$array[60]="1 {hour}";
	
	
	if(!isset($MonitConfig["MEMORY_TEST"])){$MonitConfig["MEMORY_TEST"]=1;}
	
	if(!isset($MonitConfig["MAX_MEM_ALERT"])){$MonitConfig["MAX_MEM_ALERT"]=90;}
	if(!isset($MonitConfig["MAX_MEM_PRC"])){$MonitConfig["MAX_MEM_PRC"]=95;}
	if(!isset($MonitConfig["MAX_MEM_MNS"])){$MonitConfig["MAX_MEM_MNS"]=5;}
	if(!isset($MonitConfig["MAX_MEM_RST_MYSQL"])){$MonitConfig["MAX_MEM_RST_MYSQL"]=1;}
	if(!isset($MonitConfig["MAX_MEM_RST_UFDB"])){$MonitConfig["MAX_MEM_RST_UFDB"]=1;}
	if(!isset($MonitConfig["MAX_MEM_RST_APACHE"])){$MonitConfig["MAX_MEM_RST_APACHE"]=1;}
	if(!isset($MonitConfig["MAX_MEM_RST_SQUID"])){$MonitConfig["MAX_MEM_RST_SQUID"]=1;}
	
	$html="<div style='width:98%' class=form>
			". Paragraphe_switch_img("{check_memory_use}", "{server_watchdog_memory_explain}","MEMORY_TEST",$MonitConfig["MEMORY_TEST"],null,850)."
	
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{alert_on_memory}:</td>
		<td	style='font-size:22px'>". Field_text("MAX_MEM_ALERT",$MonitConfig["MAX_MEM_ALERT"],
					"font-size:22px;width:90px")."&nbsp;%</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{if_memory_exceed} (MAX):</td>
		<td	style='font-size:22px'>". Field_text("MAX_MEM_PRC",$MonitConfig["MAX_MEM_PRC"],
					"font-size:22px;width:90px")."&nbsp;%</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{during}:</td>
		<td	style='font-size:22px'>". Field_array_Hash($array,"MAX_MEM_MNS",$MonitConfig["MAX_MEM_MNS"],null,'',0,
					"font-size:22px")."&nbsp;</td>
		<td width=1%></td>
	</tr>							
	<tr>
		<td class=legend style='font-size:22px'>{action} {restart_databases}:</td>
		<td	style='font-size:22px'>". Field_checkbox_design("MAX_MEM_RST_MYSQL", 1,$MonitConfig["MAX_MEM_RST_MYSQL"])."</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{action} {restart_webfiltering_service}:</td>
		<td	style='font-size:22px'>". Field_checkbox_design("MAX_MEM_RST_UFDB", 1,$MonitConfig["MAX_MEM_RST_UFDB"])."</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{action} {restart_web_services}:</td>
		<td	style='font-size:22px'>". Field_checkbox_design("MAX_MEM_RST_APACHE", 1,$MonitConfig["MAX_MEM_RST_APACHE"])."</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{action} {restart_proxy_service}:</td>
		<td	style='font-size:22px'>". Field_checkbox_design("MAX_MEM_RST_SQUID", 1,$MonitConfig["MAX_MEM_RST_SQUID"])."</td>
		<td width=1%></td>
	</tr>												
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","Save$t()",36)."</td>
	</tR>
</table>
</div>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	UnlockPage();
	RefreshTab('watchdog_settings_tabs');
}
	
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('SAVEGLOBAL','yes');
	XHR.appendData('MEMORY_TEST',document.getElementById('MEMORY_TEST').value);
	XHR.appendData('MAX_MEM_ALERT',document.getElementById('MAX_MEM_ALERT').value);
	XHR.appendData('MAX_MEM_PRC',document.getElementById('MAX_MEM_PRC').value);
	XHR.appendData('MAX_MEM_MNS',document.getElementById('MAX_MEM_MNS').value);
	XHR.appendData('LOAD_MAX_ACTION',document.getElementById('LOAD_MAX_ACTION').value);
	if(document.getElementById('MAX_MEM_RST_MYSQL').checked){XHR.appendData('MAX_MEM_RST_MYSQL',1); }else{ XHR.appendData('MAX_MEM_RST_MYSQL',0); }
	if(document.getElementById('MAX_MEM_RST_UFDB').checked){XHR.appendData('MAX_MEM_RST_UFDB',1); }else{ XHR.appendData('MAX_MEM_RST_UFDB',0); }
	if(document.getElementById('MAX_MEM_RST_APACHE').checked){XHR.appendData('MAX_MEM_RST_APACHE',1); }else{ XHR.appendData('MAX_MEM_RST_APACHE',0); }
	if(document.getElementById('MAX_MEM_RST_SQUID').checked){XHR.appendData('MAX_MEM_RST_SQUID',1); }else{ XHR.appendData('MAX_MEM_RST_SQUID',0); }
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
}


function defaults_values(){
	$watchdog=new squid_watchdog();
	return $watchdog->MonitConfig;
		
}

function SAVE(){
	$MonitConfig=defaults_values();
	$sock=new sockets();
	
	if(isset($_POST["MonitSquidMaxCPU"])){
		$sock->SET_INFO("MonitSquidMaxCPU",$_POST["MonitSquidMaxCPU"]);
		$sock->SET_INFO("MonitSquidMaxRestartMem",$_POST["MonitSquidMaxRestartMem"]);
		
		
	}
	 
	
	
	
	if(isset($_POST["StopMaxTTL"])){
		$squid=new squidbee();
		$squid->shutdown_lifetime=$_POST["shutdown_lifetime"];
		$squid->SaveToLdap(true);
		if($_POST["StopMaxTTL"]<$_POST["shutdown_lifetime"]){
			$_POST["StopMaxTTL"]=$_POST["shutdown_lifetime"]+1;
		}
		
	}
	
	
	if(isset($_POST["EnableFailover"])){$sock->SET_INFO("EnableFailover",$_POST["EnableFailover"]);}
	if(isset($_POST["SquidCacheReloadTTL"])){$sock->SET_INFO("SquidCacheReloadTTL",$_POST["SquidCacheReloadTTL"]);}
	
	
	
	while (list ($num, $ligne) = each ($_POST) ){
		$MonitConfig[$num]=$ligne;
		
	}
	
	$newparam=base64_encode(serialize($MonitConfig));
	$sock=new sockets();
	$sock->SaveConfigFile($newparam, "SquidWatchdogMonitConfig");
	$sock->getFrameWork("squid2.php?watchdog-bandwidth=yes");
	
}

function SAVE_SMTP(){
	
	if(isset($_POST["smtp_auth_passwd"])){$_POST["smtp_auth_passwd"]=url_decode_special_tool($_POST["smtp_auth_passwd"]);}
	
	$UfdbguardSMTPNotifs=smtp_defaults();
	while (list ($num, $ligne) = each ($_POST) ){
		$UfdbguardSMTPNotifs[$num]=$ligne;
	
	}
	$newparam=base64_encode(serialize($UfdbguardSMTPNotifs));
	$sock=new sockets();
	$sock->SaveConfigFile($newparam, "UfdbguardSMTPNotifs");
	
}


function SWAP_PAGE(){
	$t=time();
	$MonitConfig=defaults_values();
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$html="<div style='width:98%' class=form>
			". Paragraphe_switch_img("SWAP {enable}", "{SWAP_MONITOR_EXPLAIN}","SWAP_MONITOR",$MonitConfig["SWAP_MONITOR"],null,960)."

	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{SWAP_MIN}:</td>
		<td	style='font-size:22px'>". Field_text("SWAP_MIN",$MonitConfig["SWAP_MIN"],"font-size:22px;width:90px")."&nbsp;%</td>
		<td width=1%>". help_icon("{SWAP_MONITOR_EXPLAIN}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{SWAP_MAX}:</td>
		<td	style='font-size:22px'>". Field_text("SWAP_MAX",$MonitConfig["SWAP_MAX"],"font-size:22px;width:90px")."&nbsp;%</td>
		<td width=1%>". help_icon("{SWAP_MONITOR_EXPLAIN}")."</td>
	</tr>	
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","Save$t(false)",36)."</td>	
</tr>
</table>				
	</div>		
<script>
		var xSave$t= function (obj) {
			var results=obj.responseText;
			UnlockPage();
			RefreshTab('watchdog_settings_tabs');
		}
		var xSave2$t= function (obj) {
			var results=obj.responseText;
			UnlockPage();
			RefreshTab('watchdog_settings_tabs');
			Loadjs('squid.proxy.watchdog.smtp.progress.php');
		}		
		


		function Save$t(test){
			var XHR = new XHRConnection();
			XHR.appendData('SAVEGLOBAL','yes');
			XHR.appendData('SWAP_MONITOR',document.getElementById('SWAP_MONITOR').value);
			XHR.appendData('SWAP_MIN',document.getElementById('SWAP_MIN').value);
			XHR.appendData('SWAP_MAX',document.getElementById('SWAP_MAX').value);
			if(test){
				XHR.sendAndLoad('$page', 'POST',xSave2$t);
				return;
			}
			XHR.sendAndLoad('$page', 'POST',xSave$t);
			
		}
</script>
				
";
	
echo $tpl->_ENGINE_parse_body($html);
		
	
	
}

function PING_PAGE(){
	$t=time();
	$MonitConfig=defaults_values();
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$html="<div style='width:98%' class=form>
			". Paragraphe_switch_img("{ENABLE_PING_GATEWAY}", "{ENABLE_PING_GATEWAY_EXPLAIN}","ENABLE_PING_GATEWAY",$MonitConfig["ENABLE_PING_GATEWAY"],null,960)."
			". Paragraphe_switch_img("{reload_network}", "{PING_FAILED_RELOAD_NET_EXPLAIN}",
					"PING_FAILED_RELOAD_NET",$MonitConfig["PING_FAILED_RELOAD_NET"],null,960)."
			". Paragraphe_switch_img("{send_report}", "{PING_FAILED_REPORT_EXPLAIN}",
					"PING_FAILED_REPORT",$MonitConfig["PING_FAILED_REPORT"],null,960)."	
			". Paragraphe_switch_img("{switch_to_failover}", "{PING_FAILED_FAILOVER_EXPLAIN}",
					"PING_FAILED_FAILOVER",$MonitConfig["PING_FAILED_FAILOVER"],null,960)."	
			". Paragraphe_switch_img("{reboot_system}", "{reboot_system_explain}",
					"PING_FAILED_REBOOT",$MonitConfig["PING_FAILED_REBOOT"],null,960)."								

	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{MAX_PING_GATEWAY}:</td>
		<td	style='font-size:22px'>". Field_text("MAX_PING_GATEWAY",$MonitConfig["MAX_PING_GATEWAY"],"font-size:22px;width:90px")."&nbsp;</td>
		<td width=1%>". help_icon("{MAX_PING_GATEWAY_EXPLAIN}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{ipaddr}:</td>
		<td	style='font-size:22px'>". field_ipv4("PING_GATEWAY",$MonitConfig["PING_GATEWAY"],"font-size:22px;width:90px")."&nbsp;%</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","Save$t()",36)."</td>
</tr>
</table>		
			</div>
			<script>
			var xSave$t= function (obj) {
			var results=obj.responseText;
			UnlockPage();
			RefreshTab('watchdog_settings_tabs');
	}
	
	
	function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('SAVEGLOBAL','yes');
	XHR.appendData('ENABLE_PING_GATEWAY',document.getElementById('ENABLE_PING_GATEWAY').value);
	XHR.appendData('PING_GATEWAY',document.getElementById('PING_GATEWAY').value);
	XHR.appendData('MAX_PING_GATEWAY',document.getElementById('MAX_PING_GATEWAY').value);
	XHR.appendData('PING_FAILED_RELOAD_NET',document.getElementById('PING_FAILED_RELOAD_NET').value);
	XHR.appendData('PING_FAILED_REPORT',document.getElementById('PING_FAILED_REPORT').value);
	XHR.appendData('PING_FAILED_FAILOVER',document.getElementById('PING_FAILED_FAILOVER').value);
	XHR.appendData('PING_FAILED_REBOOT',document.getElementById('PING_FAILED_REBOOT').value);
	
	
	
	XHR.sendAndLoad('$page', 'POST',xSave$t);
		
	}
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function DNS_PAGE(){
	$t=time();
	$MonitConfig=defaults_values();
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$html="<div style='width:98%' class=form>
			". Paragraphe_switch_img("{NotifyDNSIssues}", "{NotifyDNSIssues_explain}","NotifyDNSIssues",$MonitConfig["NotifyDNSIssues"],null,960)."

	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{DNSIssuesMAX}:</td>
		<td	style='font-size:22px'>". Field_text("DNSIssuesMAX",$MonitConfig["DNSIssuesMAX"],
				"font-size:22px;width:90px")."&nbsp;{minutes}</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","Save$t()",36)."</td>
</tr>
</table>		
</div>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	UnlockPage();
	RefreshTab('watchdog_settings_tabs');
}
	
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('SAVEGLOBAL','yes');
	XHR.appendData('NotifyDNSIssues',document.getElementById('NotifyDNSIssues').value);
	XHR.appendData('DNSIssuesMAX',document.getElementById('DNSIssuesMAX').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
";
	
	echo $tpl->_ENGINE_parse_body($html);	
}
function EXTERNAL_PAGE(){
	$t=time();
	$MonitConfig=defaults_values();
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	
	$tcp=new networking();
	$ALL_IPS_GET_ARRAY=$tcp->ALL_IPS_GET_ARRAY();
	
	
	$html="<div style='width:98%' class=form>
			". Paragraphe_switch_img("{TestExternalWebPage}", "{squid_TestExternalWebPage_explain}",
					"TestExternalWebPage",$MonitConfig["TestExternalWebPage"],null,960)."

	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{page_to_check}:</td>
		<td	style='font-size:22px'>". Field_text("ExternalPageToCheck",$MonitConfig["ExternalPageToCheck"],
					"font-size:22px;width:350px")."&nbsp;</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{addr}:</td>
		<td	style='font-size:22px'>". Field_array_Hash($ALL_IPS_GET_ARRAY,"ExternalPageListen",$MonitConfig["ExternalPageToCheck"],
					"style:font-size:22px;")."&nbsp;</td>
		<td width=1%></td>
	</tr>							
	<tr>
		<td class=legend style='font-size:22px'>{username}:</td>
		<td	style='font-size:22px'>". Field_text("ExternalPageUsername",$MonitConfig["ExternalPageUsername"],
					"font-size:22px;width:190px")."&nbsp;</td>
		<td width=1%>". help_icon("{ExternalPageUsername_EXPLAIN}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{password}:</td>
		<td	style='font-size:22px'>". Field_password("ExternalPagePassword",$MonitConfig["ExternalPagePassword"],
					"font-size:22px;width:190px")."&nbsp;</td>
		<td width=1%>". help_icon("{ExternalPageUsername_EXPLAIN}")."</td>
	</tr>										
	<tr>
<td colspan=3 align='right'><hr>". button("{apply}","Save$t()",36)."</td>
</tr>
</table>
</div>
<script>
	var xSave$t= function (obj) {
	var results=obj.responseText;
	UnlockPage();
	RefreshTab('watchdog_settings_tabs');
}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('SAVEGLOBAL','yes');
	
	XHR.appendData('ExternalPageToCheck',document.getElementById('ExternalPageToCheck').value);
	XHR.appendData('TestExternalWebPage',document.getElementById('TestExternalWebPage').value);
	XHR.appendData('ExternalPageUsername',document.getElementById('ExternalPageUsername').value);
	XHR.appendData('ExternalPagePassword',document.getElementById('ExternalPagePassword').value);
	XHR.appendData('ExternalPageListen',document.getElementById('ExternalPageListen').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
}

function PERFORMANCE_PAGE(){
	
	$t=time();
	$MonitConfig=defaults_values();
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	
	$tcp=new networking();
	$ALL_IPS_GET_ARRAY=$tcp->ALL_IPS_GET_ARRAY();
	if(!isset($MonitConfig["TEST_PORT_TIMEOUT"])){$MonitConfig["TEST_PORT_TIMEOUT"]=2;}
	
	$html="<div style='width:98%' class=form>
".
		 Paragraphe_switch_img("{RestartWhenCrashes}", "{RestartWhenCrashes_explain}",
						"RestartWhenCrashes",$MonitConfig["RestartWhenCrashes"],null,960)."

	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{notify_when_cpu_exceed}:</td>
		<td	style='font-size:22px'>". Field_text("watchdogCPU",$MonitConfig["watchdogCPU"],
					"font-size:22px;width:190px")."&nbsp;%</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{notify_when_memory_exceed}:</td>
			<td	style='font-size:22px'>". Field_text("watchdogMEM",$MonitConfig["watchdogMEM"],
			"font-size:22px;width:190px")."&nbsp;MB</td>
		<td width=1%>". help_icon("{squid_notify_when_memory_exceed}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{restart_when_memory_exceed}:</td>
			<td	style='font-size:22px'>". Field_text("watchdogRestart",$MonitConfig["watchdogRestart"],
			"font-size:22px;width:190px")."&nbsp;% {of_total_memory}</td>
		<td width=1%></td>
	</tr>					
	<tr>
		<td class=legend style='font-size:22px'>{MaxSwapPourc}:</td>
		<td	style='font-size:22px'>". Field_text("MaxSwapPourc",$MonitConfig["MaxSwapPourc"],
					"font-size:22px;width:190px")."&nbsp;%</td>
		<td width=1%>". help_icon("{MaxSwapPourc_explain}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{MinFreeMem}:</td>
		<td	style='font-size:22px'>". Field_text("MinFreeMem",$MonitConfig["MinFreeMem"],
					"font-size:22px;width:190px")."&nbsp;MB</td>
		<td width=1%>". help_icon("{MinFreeMem_squid_explain}")."</td>
	</tr>							
	<tr>
				
	<tr><td colspan=3 style='font-size:24px'>{when_fetching_proxy_informations}</td></tr>
	<tr><td colspan=3 ><div class=explain style='font-size:16px'>{when_fetching_proxy_informations_explain}</div>		

				
	<tr>
		<td class=legend style='font-size:22px'>{enabled}:</td>
		<td	style='font-size:22px'>". Field_checkbox_design("TEST_PORT",1,$MonitConfig["TEST_PORT"],"TEST_PORT_CHECK()")."</td>
		<td width=1%></td>
	</tr>				
				
				
	<tr>
		<td class=legend style='font-size:22px'>{tests_timeout}:</td>
		<td	style='font-size:22px'>". Field_text("TEST_PORT_TIMEOUT",$MonitConfig["TEST_PORT_TIMEOUT"],
					"font-size:22px;width:190px")."&nbsp;{seconds}</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{max_failed_before_action}:</td>
		<td	style='font-size:22px'>". Field_text("TEST_PORT_MAX",$MonitConfig["TEST_PORT_MAX"],
					"font-size:22px;width:90px")."&nbsp;{times}</td>
		<td width=1%></td>
	</tr>							
	<tr>
		<td class=legend style='font-size:22px'>{restart_if_failed}:</td>
		<td	style='font-size:22px'>". Field_checkbox_design("TEST_PORT_RESTART",1,$MonitConfig["TEST_PORT_RESTART_FAILED"])."&nbsp;</td>
		<td width=1%></td>
	</tr>
<td colspan=3 align='right'><hr>". button("{apply}","Save$t()",36)."</td>
</tr>
</table>
	</div>
	<script>
	var xSave$t= function (obj) {
	var results=obj.responseText;
	UnlockPage();
	RefreshTab('watchdog_settings_tabs');
	}
	
	function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('SAVEGLOBAL','yes');
	XHR.appendData('RestartWhenCrashes',document.getElementById('RestartWhenCrashes').value);
	XHR.appendData('watchdogCPU',document.getElementById('watchdogCPU').value);
	XHR.appendData('watchdogMEM',document.getElementById('watchdogMEM').value);
	XHR.appendData('MaxSwapPourc',document.getElementById('MaxSwapPourc').value);
	XHR.appendData('watchdogRestart',document.getElementById('watchdogRestart').value);
	
	if(document.getElementById('TEST_PORT').checked){XHR.appendData('TEST_PORT',1); }else{ XHR.appendData('TEST_PORT',0); }
	if(document.getElementById('TEST_PORT_RESTART').checked){XHR.appendData('TEST_PORT_RESTART',1); }else{XHR.appendData('TEST_PORT_RESTART',0); }	
	XHR.appendData('TEST_PORT_TIMEOUT',document.getElementById('TEST_PORT_TIMEOUT').value);
	XHR.appendData('TEST_PORT_MAX',document.getElementById('TEST_PORT_MAX').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
	
function TEST_PORT_CHECK(){
	document.getElementById('TEST_PORT_TIMEOUT').disabled=true;
	document.getElementById('TEST_PORT_MAX').disabled=true;
	document.getElementById('TEST_PORT_RESTART').disabled=true;
	
	if(document.getElementById('TEST_PORT').checked){
		document.getElementById('TEST_PORT_TIMEOUT').disabled=false;
		document.getElementById('TEST_PORT_MAX').disabled=false;
		document.getElementById('TEST_PORT_RESTART').disabled=false;
	
	}
	
}
TEST_PORT_CHECK();	
	</script>
	";	
	echo $tpl->_ENGINE_parse_body($html);
}

function SETTINGS_PAGE(){
	$squid=new squidbee();
	$t=time();
	$MonitConfig=defaults_values();
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$EnableFailover=$sock->GET_INFO("EnableFailover");
	if(!is_numeric($EnableFailover)){$EnableFailover=1;}	
	$SquidCacheReloadTTL=$sock->GET_INFO("SquidCacheReloadTTL");
	if(!is_numeric($SquidCacheReloadTTL)){$SquidCacheReloadTTL=10;}
	$MonitSquidMaxRestartMem=intval($sock->GET_INFO("MonitSquidMaxRestartMem"));
	$MonitSquidMaxCPU=intval($sock->GET_INFO("MonitSquidMaxCPU"));
	
	
	$CPU[0]="{disabled}";
	for($i=50;$i<101;$i++){
		$CPU[$i]="{$i}%";
	}
	
	
	$html="<div style='width:98%' class=form>
			". Paragraphe_switch_img("{enable_watchdog}", "{enable_watchdog_squid_explain}",
			"watchdog",$MonitConfig["watchdog"],null,960)."
										

	<table style='width:100%'>
	<tr><td colspan=2 style='font-size:30px'>{restart_service}</td></tr>
	<tr>				
		<td style='font-size:22px;' class=legend>{if_system_cpu_exceed}:</td>
		<td>". Field_array_Hash($CPU, "MonitSquidMaxCPU",$MonitSquidMaxCPU,"style:font-size:22px")."</td>				
	</tr>
	<tr>
		<td style='font-size:22px;' class=legend>{if_system_memory_exceed}:</td>
		<td	style='font-size:22px'>". Field_text("MonitSquidMaxRestartMem",$MonitSquidMaxRestartMem,
		"font-size:22px;width:190px")."&nbsp;MB</td>
	</tr>
	<tr><td colspan=2 style='font-size:30px'><hr></td></tr>		
	
	<tr>
		<td class=legend style='font-size:22px'>".texttooltip("{failover}","{EnableFailover_explain}").":</td>
		<td	style='font-size:22px'>". Field_checkbox_design("EnableFailover",1,$EnableFailover)."</td>
	</tr>								
	<tr>
		<td class=legend style='font-size:22px'>".texttooltip("{ALLOW_RETURN_1CPU}","{ALLOW_RETURN_1CPU_EXPLAIN}").":</td>
		<td	style='font-size:22px'>". Field_checkbox_design("ALLOW_RETURN_1CPU",1,$MonitConfig["ALLOW_RETURN_1CPU"])."</td>
	</tr>									
	<tr>
		<td class=legend style='font-size:22px'>".texttooltip("{DisableWebFilteringNetFailed}","{DisableWebFilteringNetFailed_explain}").":</td>
		<td	style='font-size:22px'>". Field_checkbox_design("DisableWebFilteringNetFailed",1,$MonitConfig["DisableWebFilteringNetFailed"])."</td>
	</tr>								
								
	<tr>
		<td class=legend style='font-size:22px'>".texttooltip("{failover_ttl}","{failover_ttl_explain}").":</td>
		<td	style='font-size:22px'>". Field_text("MinTimeFailOverSwitch",$MonitConfig["MinTimeFailOverSwitch"],
		"font-size:22px;width:190px")."&nbsp;{minutes}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{max_attempts} (CPU):</td>
		<td	style='font-size:22px'>". Field_text("WEBPROCISSUE",$MonitConfig["WEBPROCISSUE"],
		"font-size:22px;width:190px")."&nbsp;{errors}</td>
		
	</tr>
<tr><td colspan=2 style='font-size:30px'><hr></td></tr>	
<tr><td colspan=2 style='font-size:30px'>{services_timeout}</td></tr>
					
					
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{StopMaxTTL}","{StopMaxTTL_explain}").":</td>
		<td	style='font-size:22px'>". Field_text("StopMaxTTL",$MonitConfig["StopMaxTTL"],
		"font-size:22px;width:90px")."&nbsp;{seconds}</td>
		
	</tr>	
		<tr>
			<td align='right' class=legend style='font-size:22px'>". texttooltip("{shutdown_lifetime}","{shutdown_lifetime_text}")."</strong>:</td>
			<td align='left' style='font-size:22px'>" . Field_text("shutdown_lifetime-$t",$squid->shutdown_lifetime,'width:90px;font-size:22px')."&nbsp;{seconds}</td>
		</tr>
				
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{minimum_reload_interval}","{SquidCacheReloadTTL_explain}").":</td>
		<td	style='font-size:22px'>". Field_text("SquidCacheReloadTTL",$SquidCacheReloadTTL,
		"font-size:22px;width:190px")."&nbsp;{minutes}</td>
		
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{minimum_reboot_interval}","{minimum_reboot_interval_explain}").":</td>
		<td	style='font-size:22px'>". Field_text("REBOOT_INTERVAL",$MonitConfig["REBOOT_INTERVAL"],
					"font-size:22px;width:190px")."&nbsp;{minutes}</td>
		
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{SQUID_MAX_RESTART}","{SQUID_MAX_RESTART_EXPLAIN}").":</td>
		<td	style='font-size:22px'>". Field_text("MAX_RESTART",$MonitConfig["MAX_RESTART"],
					"font-size:22px;width:190px")."&nbsp;</td>
		
	</tr>
				
	<tr><td colspan=2 style='font-size:30px'><hr></td></tr>				
	<tr><td colspan=2 style='font-size:28px'>". texttooltip("{when_fetching_proxy_informations}","{when_fetching_proxy_informations_explain}")."</td></tr>
		
	<tr>
		<td class=legend style='font-size:22px'>{tests_timeout}:</td>
		<td	style='font-size:22px'>". Field_text("MgrInfosMaxTimeOut",$MonitConfig["MgrInfosMaxTimeOut"],
					"font-size:22px;width:190px")."&nbsp;{seconds}</td>
		
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{max_failed_before_action}:</td>
		<td	style='font-size:22px'>". Field_text("MgrInfosMaxFailed",$MonitConfig["MgrInfosMaxFailed"],
					"font-size:22px;width:90px")."&nbsp;{times}</td>
		
	</tr>							
	<tr>
		<td class=legend style='font-size:22px'>{restart_if_failed}:</td>
		<td	style='font-size:22px'>". Field_checkbox_design("MgrInfosRestartFailed",1,$MonitConfig["MgrInfosRestartFailed"])."&nbsp;</td>
		
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{failover_if_failed}:</td>
		<td	style='font-size:22px'>". Field_checkbox_design("MgrInfosFaileOverFailed",1,$MonitConfig["MgrInfosFaileOverFailed"])."&nbsp;</td>
		
	</tr>				
				
							
	<tr>
<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",40)."</td>
</tr>
</table>
	</div>
	<script>
	var xSave$t= function (obj) {
	var results=obj.responseText;
	UnlockPage();
	RefreshTab('watchdog_settings_tabs');
	Loadjs('monit.restart.progress.php');
	}
	
	function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('SAVEGLOBAL','yes');
	XHR.appendData('watchdog',document.getElementById('watchdog').value);
	XHR.appendData('WEBPROCISSUE',document.getElementById('WEBPROCISSUE').value);
	XHR.appendData('MonitSquidMaxCPU',document.getElementById('MonitSquidMaxCPU').value);
	XHR.appendData('MonitSquidMaxRestartMem',document.getElementById('MonitSquidMaxRestartMem').value);
	XHR.appendData('SquidCacheReloadTTL',document.getElementById('SquidCacheReloadTTL').value);
	XHR.appendData('REBOOT_INTERVAL',document.getElementById('REBOOT_INTERVAL').value);
	XHR.appendData('MAX_RESTART',document.getElementById('MAX_RESTART').value);
	XHR.appendData('MgrInfosMaxTimeOut',document.getElementById('MgrInfosMaxTimeOut').value);
	XHR.appendData('MgrInfosMaxFailed',document.getElementById('MgrInfosMaxFailed').value);
	XHR.appendData('shutdown_lifetime',document.getElementById('shutdown_lifetime-$t').value);
	XHR.appendData('StopMaxTTL',document.getElementById('StopMaxTTL').value);
	
	
	if(document.getElementById('DisableWebFilteringNetFailed').checked){
		XHR.appendData('DisableWebFilteringNetFailed',1);
	}else{
		XHR.appendData('DisableWebFilteringNetFailed',0);
	}
	if(document.getElementById('ALLOW_RETURN_1CPU').checked){
		XHR.appendData('ALLOW_RETURN_1CPU',1);
	}else{
		XHR.appendData('ALLOW_RETURN_1CPU',0);
	}	
	if(document.getElementById('EnableFailover').checked){
		XHR.appendData('EnableFailover',1);
	}else{
		XHR.appendData('EnableFailover',0);
	}		
	
	
	
	
	if(document.getElementById('MgrInfosRestartFailed').checked){ XHR.appendData('MgrInfosRestartFailed',1); }else{ XHR.appendData('MgrInfosRestartFailed',0); }
	if(document.getElementById('MgrInfosFaileOverFailed').checked){ XHR.appendData('MgrInfosFaileOverFailed',1); }else{ XHR.appendData('MgrInfosFaileOverFailed',0); }
	
	
	
	
	XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);	
}
function smtp_defaults(){
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$ini->loadString($sock->getFrameWork("cmd.php?SmtpNotificationConfigRead=yes"));
	if($ini->_params["SMTP"]["smtp_server_port"]==null){$ini->_params["SMTP"]["smtp_server_port"]=25;}
	if($ini->_params["SMTP"]["smtp_sender"]==null){$users=new usersMenus();$ini->_params["SMTP"]["smtp_sender"]="artica@$users->fqdn";}
	$t=time();
	$UfdbguardSMTPNotifs=unserialize(base64_decode($sock->GET_INFO("UfdbguardSMTPNotifs")));
	if(!isset($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"])){$UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]=0;}
	if(!is_numeric($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"])){$UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]=0;}
	if(!isset($UfdbguardSMTPNotifs["smtp_server_name"])){$UfdbguardSMTPNotifs["smtp_server_name"]=$ini->_params["SMTP"]["smtp_server_name"];}
	if(!isset($UfdbguardSMTPNotifs["smtp_server_port"])){$UfdbguardSMTPNotifs["smtp_server_port"]=$ini->_params["SMTP"]["smtp_server_port"];}
	if(!isset($UfdbguardSMTPNotifs["smtp_sender"])){$UfdbguardSMTPNotifs["smtp_server_port"]=$ini->_params["SMTP"]["smtp_sender"];}
	if(!isset($UfdbguardSMTPNotifs["smtp_dest"])){$UfdbguardSMTPNotifs["smtp_dest"]=$ini->_params["SMTP"]["smtp_dest"];}
	if(!isset($UfdbguardSMTPNotifs["smtp_auth_user"])){$UfdbguardSMTPNotifs["smtp_dest"]=$ini->_params["SMTP"]["smtp_auth_user"];}
	if(!isset($UfdbguardSMTPNotifs["smtp_auth_passwd"])){$UfdbguardSMTPNotifs["smtp_auth_passwd"]=$ini->_params["SMTP"]["smtp_auth_passwd"];}
	if(!isset($UfdbguardSMTPNotifs["tls_enabled"])){$UfdbguardSMTPNotifs["tls_enabled"]=$ini->_params["SMTP"]["tls_enabled"];}
	if(!isset($UfdbguardSMTPNotifs["ssl_enabled"])){$UfdbguardSMTPNotifs["ssl_enabled"]=$ini->_params["SMTP"]["ssl_enabled"];}
	if(!is_numeric($UfdbguardSMTPNotifs["smtp_server_port"])){$UfdbguardSMTPNotifs["smtp_server_port"]=25;}	
	return $UfdbguardSMTPNotifs;
	
}
function SMTP_PAGE(){
	$t=time();
	$UfdbguardSMTPNotifs=smtp_defaults();
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	
	
	
	$html="<div style='width:98%' class=form>
			". Paragraphe_switch_img("{smtp_enabled}", "{smtp_enabled_watchdog_explain}",
			"ENABLED_SQUID_WATCHDOG",$UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"],null,1390)."
			

	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{warning_events}:</td>
			<td style='vertical-align:middle'>". Field_checkbox_design("warning_events",$UfdbguardSMTPNotifs["warning_events"],
			"font-size:22px;width:110px")."&nbsp;</td>
		<td width=1%></td>
	</tr>								
								
								
	<tr>
		<td class=legend style='font-size:22px'>{smtp_server_name}:</td>
		<td	style='font-size:22px'>". Field_text("smtp_server_name",$UfdbguardSMTPNotifs["smtp_server_name"],
					"font-size:22px;width:500px")."&nbsp;</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{smtp_server_port}:</td>
			<td	style='font-size:22px'>". Field_text("smtp_server_port",$UfdbguardSMTPNotifs["smtp_server_port"],
			"font-size:22px;width:110px")."&nbsp;</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{smtp_sender}:</td>
		<td	style='font-size:22px'>". Field_text("smtp_sender",$UfdbguardSMTPNotifs["smtp_sender"],
					"font-size:22px;width:500px")."&nbsp;</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{smtp_dest}:</td>
		<td	style='font-size:22px'>". Field_text("smtp_dest",$UfdbguardSMTPNotifs["smtp_dest"],
					"font-size:22px;width:500px")."&nbsp;</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{smtp_auth_user}:</td>
		<td	style='font-size:22px'>". Field_text("smtp_auth_user",$UfdbguardSMTPNotifs["smtp_auth_user"],
					"font-size:22px;width:500px")."&nbsp;</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{smtp_auth_passwd}:</td>
		<td	style='font-size:22px'>". Field_password("smtp_auth_passwd",$UfdbguardSMTPNotifs["smtp_auth_passwd"],
					"font-size:22px;width:500px")."&nbsp;</td>
		<td width=1%></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>".texttooltip("{tls_enabled}","{tls_enabled_explain}").":</td>
		<td	style='font-size:22px'>". Field_checkbox_design("tls_enabled",1,$UfdbguardSMTPNotifs["tls_enabled"])."</td>
		<td width=1%></td>
	</tr>													
	<tr>
<td colspan=3 align='right' style='font-size:22px;'><hr>". button("{test_message}","Save$t(true)",36)."&nbsp;|&nbsp;". button("{apply}","Save$t(false)",36)."</td>
</tr>
</table>
	</div>
	<script>
	var xSave$t= function (obj) {
	var results=obj.responseText;
	UnlockPage();
	RefreshTab('watchdog_settings_tabs');
	}
		var xSave2$t= function (obj) {
			var results=obj.responseText;
			UnlockPage();
			RefreshTab('watchdog_settings_tabs');
			Loadjs('squid.proxy.watchdog.smtp.progress.php');
		}		
	
	
	function Save$t(test){
	var XHR = new XHRConnection();
	XHR.appendData('SAVESMTP','yes');
	XHR.appendData('ENABLED_SQUID_WATCHDOG',document.getElementById('ENABLED_SQUID_WATCHDOG').value);
	XHR.appendData('smtp_server_name',document.getElementById('smtp_server_name').value);
	XHR.appendData('smtp_server_port',document.getElementById('smtp_server_port').value);
	XHR.appendData('smtp_sender',document.getElementById('smtp_sender').value);
	XHR.appendData('smtp_dest',document.getElementById('smtp_dest').value);
	XHR.appendData('smtp_auth_user',document.getElementById('smtp_auth_user').value);
	XHR.appendData('smtp_auth_passwd',encodeURIComponent(document.getElementById('smtp_auth_passwd').value));
	
	
	if(document.getElementById('tls_enabled').checked){
		XHR.appendData('tls_enabled',1);
	}else{
		XHR.appendData('tls_enabled',0);
	}
	
	if(document.getElementById('warning_events').checked){
		XHR.appendData('warning_events',1);
	}else{
		XHR.appendData('warning_events',0);
	}
	if(!test){
		XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
	
	XHR.sendAndLoad('$page', 'POST',xSave2$t);
	
	}
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);	
}

