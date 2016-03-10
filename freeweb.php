<?php
	session_start();
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.freeweb.inc');
	include_once('ressources/class.awstats.inc');
	include_once('ressources/class.pdns.inc');
	include_once('ressources/class.squid.inc');
	
	

	$user=new usersMenus();
	if(!$user->AsWebMaster){
		if(!$user->AsSquidAdministrator){
			$tpl=new templates();
			echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
			die();exit();
		}
	}
	
	
	if(isset($_POST["FreeWebsScanSize"])){FreeWebsScanSize();exit;}
	if(isset($_POST["FreeWebChangeInit"])){changeInit();exit;}
	if(isset($_GET["watchdog-popup"])){watchdog_form();exit;}
	if(isset($_POST["watchdog"])){watchdog_save();exit;}
	if(isset($_GET["status"])){freewebs_status();exit;}
	if(isset($_POST["EnablePHPFPM"])){EnablePHPFPM();exit;}
	if(isset($_POST["EnableSPAWNFCGI"])){EnableSPAWNFCGI();exit;}
	
	if(isset($_POST["EnableNginx"])){EnableNginx();exit;}
	if(isset($_GET["log-rotate-js"])){log_rotate_js();exit;}
	if(isset($_GET["log-rotate-popup"])){log_rotate_popup();exit;}
	if(isset($_POST["RotateFreq"])){log_rotate_save();exit;}
	
	
	if(isset($_GET["apacheMasterconfig"])){SaveMacterConfig();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["index"])){index();exit;}
	if(isset($_GET["webs"])){popup_webs();exit;}
	if(isset($_GET["EnableFreeWeb"])){saveEnableFreeWeb();exit;}
	if(isset($_GET["listwebs"])){listwebs();exit;}
	if(isset($_GET["listwebs-search"])){listwebs_search();exit;}
	if(isset($_GET["delete-servername"])){delete();exit;}
	if(isset($_GET["FreeWebLeftMenu"])){FreeWebLeftMenuSave();exit;}
	if(isset($_GET["apache-src-status"])){apache_src_status();exit;}
	if(isset($_GET["mode-evasive-section"])){mode_evasive();exit;}
	if(isset($_GET["mod-evasive-default"])){mode_evasive_default_js();exit;}
	if(isset($_GET["mod-evasive-form"])){mode_evasive_form();exit;}
	if(isset($_POST["DOSHashTableSize"])){mode_evasive_save();exit;}
	if(isset($_GET["modules"])){modules_list();exit;}
	if(isset($_GET["apache_modules"])){modules_apache();exit;}
	if(isset($_GET["apache-modules-list"])){modules_apache_list();exit;}
	if(isset($_GET["DisableZarafaWebService"])){DisableZarafaWebService();exit;}
	if(isset($_POST["ZarafaWebAccessInFrontEnd"])){ZarafaWebAccessInFrontEnd();exit;}
	
	if(isset($_POST["AddDefaultOne"])){add_default_site();exit;}
	if(isset($_POST["CheckAVailable"])){CheckAVailable();exit;}
	
	if(isset($_GET["apache-cmds"])){apache_cmds_js();exit;}
	if(isset($_GET["apache-cmds-peform"])){apache_cmds_perform();exit;}
	
	if(isset($_GET["params"])){parameters_div();exit;}
	if(isset($_GET["params-div"])){parameters();exit;}
	
	if(isset($_GET["rebuild-items"])){rebuild_items();exit;}
	
js();

function log_rotate_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{logrotate}");
	$html="YahooWin4('650','$page?log-rotate-popup=yes','Freewebs::$title');";
	echo $html;		
}

function apache_cmds_js(){
	$page=CurrentPageName();
	$cmd=$_GET["apache-cmds"];
	$html="YahooWin4('650','$page?apache-cmds-peform=$cmd','Freewebs::$cmd');";
	echo $html;	
}

function apache_cmds_perform(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$datas=unserialize(base64_decode($sock->getFrameWork("freeweb.php?apache-cmds={$_GET["apache-cmds-peform"]}")));
	
		$html="
<div style='width:100%;height:350px;overflow:auto'>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
	<th>{events}</th>
	</tr>
</thead>
<tbody class='tbody'>";	
	
	while (list ($key, $val) = each ($datas) ){
		if(trim($val)==null){continue;}
		if(trim($val=="->")){continue;}
		if(isset($alread[trim($val)])){continue;}
		$alread[trim($val)]=true;
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$val=htmlentities($val);
			$html=$html."
			<tr class=$classtr>
			<td width=99%><code style='font-size:12px'>$val</code></td>
			</tr>
			";
	
	
}

$html=$html."
</tbody>
</table>
</div>
<script>
	RefreshTab('main_config_freeweb');
</script>

";
	echo $tpl->_ENGINE_parse_body($html);
}


function js(){
	
	$page=CurrentPageName();
	if(isset($_GET["newinterface"])){$newinterface="&newinterface=yes";}
	if(isset($_GET["in-front-ajax"])){
		echo "$('#BodyContent').load('$page?popup=yes$newinterface');";
		return;
	}	
	
	$html="YahooWin4('730','$page?popup=yes','Freewebs');";
	echo $html;
	}
function mode_evasive_default_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{DDOS_prevention}:{default}");
	$html="YahooWin3('650','$page?mod-evasive-form=yes','$title');";
	echo $html;
	}

	
function parameters_div(){
	$t=time();
	$page=CurrentPageName();
	$html="<div id='parametersdiv$t'></div>
	
	<script>
		function RefreshParametersDiv$t(){
			LoadAjax('parametersdiv$t','$page?params-div=yes&t=$t');
		
		}
		RefreshParametersDiv$t();
	</script>
	
	";
	echo $html;
}

function parameters(){
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$ApacheServerTokens=$sock->GET_INFO("ApacheServerTokens");
	$ApacheServerSignature=$sock->GET_INFO("ApacheServerSignature");
	$FreeWebsEnableModSecurity=$sock->GET_INFO("FreeWebsEnableModSecurity");
	$FreeWebsEnableModEvasive=$sock->GET_INFO("FreeWebsEnableModEvasive");
	$FreeWebsEnableModQOS=$sock->GET_INFO("FreeWebsEnableModQOS");
	$FreeWebsEnableOpenVPNProxy=$sock->GET_INFO("FreeWebsEnableOpenVPNProxy");
	$FreeWebsOpenVPNRemotPort=$sock->GET_INFO("FreeWebsOpenVPNRemotPort");
	$FreeWebsDisableSSLv2=$sock->GET_INFO("FreeWebsDisableSSLv2");
	$ApacheDisableModDavFS=$sock->GET_INFO("ApacheDisableModDavFS");
	$FreeWebPerformances=unserialize(base64_decode($sock->GET_INFO("FreeWebPerformances")));
	$FreeWebEnableModFcgid=$sock->GET_INFO("FreeWebEnableModFcgid");
	$FreeWebEnableModSUPhp=$sock->GET_INFO("FreeWebEnableModSUPhp");
	$SecServerSignature=$sock->GET_INFO("SecServerSignature");	
	$ApacheServerName=$sock->GET_INFO("ApacheServerName");
	$ApacheLogRotate=$sock->GET_INFO("ApacheLogRotate");
	if($ApacheServerName==null){$ApacheServerName=$users->fqdn;}
	
	
	
	$JSFreeWebsEnableModSecurity=1;
	$JSFreeWebsEnableModEvasive=1;
	$JSFreeWebsEnableModQOS=1;
	$JSFreeWebsEnableOpenVPNProxy=1;
	$JSFreeWebsEnableWebDav=1;
	$JSFreeWebsEnableModSUPhp=1;
	if(!is_numeric($ApacheServerSignature)){$ApacheServerSignature=1;}
	if(!is_numeric($FreeWebsEnableModSecurity)){$FreeWebsEnableModSecurity=0;}
	if(!is_numeric($FreeWebsEnableModQOS)){$FreeWebsEnableModQOS=0;}
	if(!is_numeric($FreeWebsEnableOpenVPNProxy)){$FreeWebsEnableOpenVPNProxy=0;}
	if(!is_numeric($FreeWebsDisableSSLv2)){$FreeWebsDisableSSLv2=0;}
	if(!is_numeric($ApacheDisableModDavFS)){$ApacheDisableModDavFS=0;}
	if(!is_numeric($FreeWebEnableModFcgid)){$FreeWebEnableModFcgid=0;}
	if(!is_numeric($FreeWebEnableModSUPhp)){$FreeWebEnableModSUPhp=0;}
	
	if(!is_numeric($ApacheLogRotate)){$ApacheLogRotate=1;}
	
	
	
	if($ApacheServerTokens==null){$ApacheServerTokens="Full";}
	$varWwwPerms=$sock->GET_INFO("varWwwPerms");
	if($varWwwPerms==null){$varWwwPerms=755;}
	$ApacheServerTokens_array["Full"]="{all_informations}";
	$ApacheServerTokens_array["OS"]="{operating_system}";
	$ApacheServerTokens_array["Min"]="{minimal_infos}";
	$ApacheServerTokens_array["Minor"]="{minor_version}";
	$ApacheServerTokens_array["Major"]="{major_version}";
	$ApacheServerTokens_array["Prod"]="{product_apache_name}";
	
	if(!$users->APACHE_MOD_SECURITY){$JSFreeWebsEnableModSecurity=0;}
	if(!$users->APACHE_MOD_EVASIVE){$JSFreeWebsEnableModEvasive=0;}
	if(!$users->APACHE_MOD_QOS){$JSFreeWebsEnableModQOS=0;}
	if(!$users->APACHE_PROXY_MODE){$JSFreeWebsEnableOpenVPNProxy=0;}
	if(!$users->APACHE_MODE_WEBDAV){$JSFreeWebsEnableWebDav=0;}
	if(!$users->APACHE_MOD_SUPHP){$JSFreeWebsEnableModSUPhp=0;}
	
	
	if(!is_numeric($FreeWebsOpenVPNRemotPort)){
		if($users->OPENVPN_INSTALLED){
			include_once(dirname(__FILE__).'/ressources/class.openvpn.inc');
			$vpn=new openvpn();
			$FreeWebsOpenVPNRemotPort=$vpn->main_array["GLOBAL"]["LISTEN_PORT"];
		}
	}
	
	if(!is_numeric($FreeWebPerformances["Timeout"])){$FreeWebPerformances["Timeout"]=300;}
	if(!is_numeric($FreeWebPerformances["KeepAlive"])){$FreeWebPerformances["KeepAlive"]=0;}
	if(!is_numeric($FreeWebPerformances["MaxKeepAliveRequests"])){$FreeWebPerformances["MaxKeepAliveRequests"]=100;}
	if(!is_numeric($FreeWebPerformances["KeepAliveTimeout"])){$FreeWebPerformances["KeepAliveTimeout"]=15;}
	if(!is_numeric($FreeWebPerformances["MinSpareServers"])){$FreeWebPerformances["MinSpareServers"]=5;}
	if(!is_numeric($FreeWebPerformances["MaxSpareServers"])){$FreeWebPerformances["MaxSpareServers"]=10;}
	if(!is_numeric($FreeWebPerformances["StartServers"])){$FreeWebPerformances["StartServers"]=5;}
	if(!is_numeric($FreeWebPerformances["MaxClients"])){$FreeWebPerformances["MaxClients"]=50;}
	if(!is_numeric($FreeWebPerformances["MaxRequestsPerChild"])){$FreeWebPerformances["MaxRequestsPerChild"]=10000;}
	
	
	$JSFreeWebEnableModFcgid=0;
	if($users->APACHE_MOD_FCGID && $users->APACHE_MOD_SUEXEC){$JSFreeWebEnableModFcgid=1;}
	
	$account=unserialize(base64_decode($sock->getFrameWork("freeweb.php?ApacheAccount=yes")));
	$ApacheSRCAccount=$account[0];
	$ApacheSRCGroups=$account[1];
	$t=$_GET["t"];

	
	
	
	
	$html="
	<div id='apacheMasterconfig'>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{hostname}:</td>
		<td>". Field_text("ApacheServerName",$ApacheServerName,"font-size:14px;padding:3px;width:220px")."</td>
		<td>&nbsp;</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:14px'>{account}:</td>
		<td>". Field_text("ApacheSRCAccount",$ApacheSRCAccount,"font-size:14px;padding:3px;width:120px")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{group}:</td>
		<td>". Field_text("ApacheSRCGroup",$ApacheSRCGroups,"font-size:14px;padding:3px;width:120px")."</td>
		<td>&nbsp;</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:14px'>{ApacheServerTokens}:</td>
		<td>". Field_array_Hash($ApacheServerTokens_array,"ApacheServerTokens",$ApacheServerTokens,"ModSecurityDisable2()",null,0,"font-size:14px;padding:3px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{SecServerSignature}:</td>
		<td>". Field_text("SecServerSignature",$SecServerSignature,"font-size:14px;padding:3px;width:220px")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{ApacheServerSignature}:</td>
		<td>". Field_checkbox("ApacheServerSignature",1,$ApacheServerSignature)."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px' nowrap>{ApacheLogRotate}:</td>
		<td>". Field_checkbox("ApacheLogRotate",1,$ApacheLogRotate)."</td>
		<td><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$page?log-rotate-js=yes')\" style='font-size:13px;text-decoration:underline'>{parameters}</a></td>
	</tr>	
	
	
	
	<tr>
		<td class=legend style='font-size:14px'>{VarWWWChmod}:</td>
		<td>". Field_text("varWwwPerms",$varWwwPerms,"font-size:14px;width:60px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{disableSSLv2}:</td>
		<td>". Field_checkbox("FreeWebsDisableSSLv2",1,$FreeWebsDisableSSLv2)."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{FreeWebsEnableModSecurity}:</td>
		<td>". Field_checkbox("FreeWebsEnableModSecurity",1,$FreeWebsEnableModSecurity,"ModSecurityDisable2()")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{FreeWebsEnableModSuPHP}:</td>
		<td>". Field_checkbox("FreeWebEnableModSUPhp",1,$FreeWebEnableModSUPhp,"")."</td>
		<td>&nbsp;</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:14px'>{FreeWebsEnableModEvasive}:</td>
		<td>". Field_checkbox("FreeWebsEnableModEvasive",1,$FreeWebsEnableModEvasive)."&nbsp;<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$page?mod-evasive-default=yes')\" style='font-size:13px;text-decoration:underline'>{default}</a></td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{FreeWebsEnableModQOS}:</td>
		<td>". Field_checkbox("FreeWebsEnableModQOS",1,$FreeWebsEnableModQOS)."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{enable_mod_fcgid}:</td>
		<td>". Field_checkbox("FreeWebEnableModFcgid",1,$FreeWebEnableModFcgid)."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{ApacheDisableModDavFS}:</td>
		<td>". Field_checkbox("ApacheDisableModDavFS",1,$ApacheDisableModDavFS)."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{FreeWebsEnableOpenVPNProxy}:</td>
		<td>". Field_checkbox("FreeWebsEnableOpenVPNProxy",1,$FreeWebsEnableOpenVPNProxy,"FreeWebsEnableOpenVPNProxyCheck()")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{OpenVPNRemotePort}:</td>
		<td>". Field_text("FreeWebsOpenVPNRemotPort",$FreeWebsOpenVPNRemotPort,"font-size:13px;width:90px;padding:3px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{webservers_status}:</td>
		<td><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('freeweb.mod.status.php')\" style='font-size:13px;text-decoration:underline'>{parameters}</a></td>
		<td>&nbsp;</td>
	</tr>	
</table>
<br>
<table style='width:99%' class=form>
	<tr><td colspan=3 style='font-size:16px'>{performances}</td></tr>
	<tr>
		<td class=legend style='font-size:14px'>{Timeout}:</td>
		<td>". Field_text("Timeout",$FreeWebPerformances["Timeout"],"font-size:13px;width:90px;padding:3px")."&nbsp;{seconds}</td>
		<td>". help_icon("{ApacheTimeout}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{KeepAlive}:</td>
		<td>". Field_checkbox("KeepAlive",1,$FreeWebPerformances["KeepAlive"])."</td>
		<td>". help_icon("{ApacheKeepAlive}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{MaxKeepAliveRequests}:</td>
		<td>". Field_text("MaxKeepAliveRequests",$FreeWebPerformances["MaxKeepAliveRequests"],"font-size:13px;width:90px;padding:3px")."&nbsp;{requests}</td>
		<td>". help_icon("{ApacheMaxKeepAliveRequests}")."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:14px'>{KeepAliveTimeout}:</td>
		<td>". Field_text("KeepAliveTimeout",$FreeWebPerformances["KeepAliveTimeout"],"font-size:13px;width:90px;padding:3px")."&nbsp;{seconds}</td>
		<td>". help_icon("{ApacheKeepAliveTimeout}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{StartServers}:</td>
		<td>". Field_text("StartServers",$FreeWebPerformances["StartServers"],"font-size:13px;width:90px;padding:3px")."&nbsp;</td>
		<td>". help_icon("{ApacheStartServers}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{MinSpareServers}:</td>
		<td>". Field_text("MinSpareServers",$FreeWebPerformances["MinSpareServers"],"font-size:13px;width:90px;padding:3px")."&nbsp;</td>
		<td>". help_icon("{ApacheMinSpareServers}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{MaxSpareServers}:</td>
		<td>". Field_text("MaxSpareServers",$FreeWebPerformances["MaxSpareServers"],"font-size:13px;width:90px;padding:3px")."&nbsp;</td>
		<td>". help_icon("{ApacheMinSpareServers}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{MaxClients}:</td>
		<td>". Field_text("MaxClients",$FreeWebPerformances["MaxClients"],"font-size:13px;width:90px;padding:3px")."&nbsp;</td>
		<td>". help_icon("{ApacheMaxClients}")."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:14px'>{MaxRequestsPerChild}:</td>
		<td>". Field_text("MaxRequestsPerChild",$FreeWebPerformances["MaxRequestsPerChild"],"font-size:13px;width:90px;padding:3px")."&nbsp;</td>
		<td>". help_icon("{ApacheMaxRequestsPerChild}")."</td>
	</tr>	

	
	
	
	
	<tr>
		<td colspan=3 align='right'>
		<hr>". button("{apply}","SaveApacheCentralSettings()","18px")."</td>
	</tr>
	</table>
	</div>
	
	
	
<script>
		var x_SaveApacheCentralSettings$t=function (obj) {
			var results=obj.responseText;
			if(results.length>2){alert(results);}			
			RefreshParametersDiv$t();
		}	
		
		function SaveApacheCentralSettings(){
			var XHR = new XHRConnection();
			XHR.appendData('apacheMasterconfig','yes');
    		XHR.appendData('ApacheServerTokens',document.getElementById('ApacheServerTokens').value);
    		XHR.appendData('varWwwPerms',document.getElementById('varWwwPerms').value);
    		XHR.appendData('FreeWebsOpenVPNRemotPort',document.getElementById('FreeWebsOpenVPNRemotPort').value);
    		XHR.appendData('ApacheServerName',document.getElementById('ApacheServerName').value);
    		
    		
    		
    		if(document.getElementById('ApacheServerSignature').checked){XHR.appendData('ApacheServerSignature',1);}else{XHR.appendData('ApacheServerSignature',0);}
			if(document.getElementById('FreeWebsEnableModSecurity').checked){XHR.appendData('FreeWebsEnableModSecurity',1);}else{XHR.appendData('FreeWebsEnableModSecurity',0);}
			if(document.getElementById('FreeWebsEnableModEvasive').checked){XHR.appendData('FreeWebsEnableModEvasive',1);}else{XHR.appendData('FreeWebsEnableModEvasive',0);}
			if(document.getElementById('FreeWebsEnableModQOS').checked){XHR.appendData('FreeWebsEnableModQOS',1);}else{XHR.appendData('FreeWebsEnableModQOS',0);}
			if(document.getElementById('FreeWebsEnableOpenVPNProxy').checked){XHR.appendData('FreeWebsEnableOpenVPNProxy',1);}else{XHR.appendData('FreeWebsEnableOpenVPNProxy',0);}
			if(document.getElementById('FreeWebsDisableSSLv2').checked){XHR.appendData('FreeWebsDisableSSLv2',1);}else{XHR.appendData('FreeWebsDisableSSLv2',0);}
			if(document.getElementById('ApacheDisableModDavFS').checked){XHR.appendData('ApacheDisableModDavFS',1);}else{XHR.appendData('ApacheDisableModDavFS',0);}
			if(document.getElementById('FreeWebEnableModFcgid').checked){XHR.appendData('FreeWebEnableModFcgid',1);}else{XHR.appendData('FreeWebEnableModFcgid',0);}
			if(document.getElementById('FreeWebEnableModSUPhp').checked){XHR.appendData('FreeWebEnableModSUPhp',1);}else{XHR.appendData('FreeWebEnableModSUPhp',0);}
			
			
			
			if(document.getElementById('KeepAlive').checked){XHR.appendData('KeepAlive',1);}else{XHR.appendData('KeepAlive',0);}
			XHR.appendData('Timeout',document.getElementById('Timeout').value);
			XHR.appendData('MaxKeepAliveRequests',document.getElementById('MaxKeepAliveRequests').value);
			XHR.appendData('KeepAliveTimeout',document.getElementById('KeepAliveTimeout').value);
			XHR.appendData('MinSpareServers',document.getElementById('MinSpareServers').value);
			XHR.appendData('MaxSpareServers',document.getElementById('MaxSpareServers').value);
			XHR.appendData('StartServers',document.getElementById('StartServers').value);
			XHR.appendData('MaxClients',document.getElementById('MaxClients').value);
			XHR.appendData('MaxRequestsPerChild',document.getElementById('MaxRequestsPerChild').value);
			XHR.appendData('ApacheSRCAccount',document.getElementById('ApacheSRCAccount').value);
			XHR.appendData('ApacheSRCGroup',document.getElementById('ApacheSRCGroup').value);
			XHR.appendData('SecServerSignature',document.getElementById('SecServerSignature').value);
			
			

 			AnimateDiv('apacheMasterconfig');
    		XHR.sendAndLoad('$page', 'GET',x_SaveApacheCentralSettings$t);
			
		}
		
		function ModSecurityDisable(){
			var JSFreeWebsEnableModSecurity=$JSFreeWebsEnableModSecurity;
			var JSFreeWebsEnableModEvasive=$JSFreeWebsEnableModEvasive;
			var JSFreeWebsEnableModQOS=$JSFreeWebsEnableModQOS;
			var JSFreeWebsEnableOpenVPNProxy=$JSFreeWebsEnableOpenVPNProxy;
			var JSFreeWebsEnableWebDav=$JSFreeWebsEnableWebDav;
			var JSFreeWebEnableModFcgid=$JSFreeWebEnableModFcgid;
			var JSFreeWebsEnableModSUPhp=$JSFreeWebsEnableModSUPhp;
			var ApacheServerTokens=document.getElementById('ApacheServerTokens').value;
			
			
			if(JSFreeWebsEnableModSUPhp==0){
				document.getElementById('FreeWebEnableModSUPhp').disabled=true;
			}
			
			if(JSFreeWebsEnableModSecurity==0){
				document.getElementById('SecServerSignature').disabled=true;
				document.getElementById('FreeWebsEnableModSecurity').checked=false;
				document.getElementById('FreeWebsEnableModSecurity').disabled=true;
			}else{
				document.getElementById('SecServerSignature').disabled=true;
				if(ApacheServerTokens=='Full'){document.getElementById('SecServerSignature').disabled=false;}
				
			
			}
			if(JSFreeWebsEnableModEvasive==0){
				document.getElementById('FreeWebsEnableModEvasive').checked=false;
				document.getElementById('FreeWebsEnableModEvasive').disabled=true;
			}
			if(JSFreeWebsEnableModQOS==0){
				document.getElementById('FreeWebsEnableModQOS').checked=false;
				document.getElementById('FreeWebsEnableModQOS').disabled=true;
			}
			if(JSFreeWebsEnableOpenVPNProxy==0){
				document.getElementById('FreeWebsEnableOpenVPNProxy').checked=false;
				document.getElementById('FreeWebsEnableOpenVPNProxy').disabled=true;
				document.getElementById('FreeWebsOpenVPNRemotPort').disabled=true;
			}
			
			if(JSFreeWebsEnableWebDav==0){
				document.getElementById('ApacheDisableModDavFS').checked=false;
				document.getElementById('ApacheDisableModDavFS').disabled=true;
				
			}	

			if(JSFreeWebEnableModFcgid==0){
				document.getElementById('FreeWebEnableModFcgid').checked=false;
				document.getElementById('FreeWebEnableModFcgid').disabled=true;			
			}
		}
		
		function FreeWebsEnableOpenVPNProxyCheck(){
			if(!document.getElementById('FreeWebsEnableOpenVPNProxy').checked){
				document.getElementById('FreeWebsOpenVPNRemotPort').disabled=true;
			}else{
				document.getElementById('FreeWebsOpenVPNRemotPort').disabled=false;
			}
		}
		
		function ModSecurityDisable2(){
			var ApacheServerTokens=document.getElementById('ApacheServerTokens').value;
			document.getElementById('SecServerSignature').disabled=true;
			if(document.getElementById('FreeWebsEnableModSecurity').checked){
				if(ApacheServerTokens=='Full'){
					document.getElementById('SecServerSignature').disabled=false;
				}
			
			}
		
		}
		
		ModSecurityDisable();
		ModSecurityDisable2();
		FreeWebsEnableOpenVPNProxyCheck();
	</script>
	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function SaveMacterConfig(){
	$sock=new sockets();
	$sock->SET_INFO("ApacheServerSignature",$_GET["ApacheServerSignature"]);
	$sock->SET_INFO("ApacheServerTokens",$_GET["ApacheServerTokens"]);
	$sock->SET_INFO("FreeWebsEnableModSecurity",$_GET["FreeWebsEnableModSecurity"]);
	$sock->SET_INFO("FreeWebsEnableModEvasive",$_GET["FreeWebsEnableModEvasive"]);
	$sock->SET_INFO("FreeWebsEnableModQOS",$_GET["FreeWebsEnableModQOS"]);
	$sock->SET_INFO("FreeWebsOpenVPNRemotPort",$_GET["FreeWebsOpenVPNRemotPort"]);
	$sock->SET_INFO("FreeWebsEnableOpenVPNProxy",$_GET["FreeWebsEnableOpenVPNProxy"]);
	$sock->SET_INFO("FreeWebsDisableSSLv2",$_GET["FreeWebsDisableSSLv2"]);
	$sock->SET_INFO("ApacheDisableModDavFS" ,$_GET["ApacheDisableModDavFS"]);
	$sock->SET_INFO("FreeWebEnableModSUPhp" ,$_GET["FreeWebEnableModSUPhp"]);
	
	
	
	
	$sock->SET_INFO("varWwwPerms",$_GET["varWwwPerms"]);
	$sock->SET_INFO("FreeWebEnableModFcgid",$_GET["FreeWebEnableModFcgid"]);
	$sock->SET_INFO("SecServerSignature", $_GET["SecServerSignature"]);
	if(isset($_GET["ApacheServerName"])){$sock->SET_INFO("ApacheServerName",$_GET["ApacheServerName"]);}
	if(isset($_GET["ApacheSRCAccount"])){$sock->SET_INFO("ApacheSRCAccount",$_GET["ApacheSRCAccount"]);}
	if(isset($_GET["ApacheSRCGroup"])){$sock->SET_INFO("ApacheSRCGroup",$_GET["ApacheSRCGroup"]);}
	if(isset($_GET["ApacheLogRotate"])){$sock->SET_INFO("ApacheLogRotate",$_GET["ApacheLogRotate"]);}
	
	$sock->SaveConfigFile(base64_encode(serialize($_GET)), "FreeWebPerformances");
	$sock->getFrameWork("cmd.php?restart-apache-src=yes");
}




function popup(){
	$tpl=new templates();	
	$page=CurrentPageName();
	$users=new usersMenus();
	$sock=new sockets();
	
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	if($SquidPerformance>2){
		echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{software_is_disabled_performance}"));
		return;
	
	}
	
	
	$array["index"]='{status}';
	$array["webs"]='{squid_accel_websites}';
	$array["params"]='{parameters}';
	$array["listen_addresses"]='{listen_addresses}';
	
	if($users->SQUID_INSTALLED){
		$SquidActHasReverse=$sock->GET_INFO("SquidActHasReverse");
		if(!is_numeric($SquidActHasReverse)){$SquidActHasReverse=0;}
		if($users->SQUID_REVERSE_APPLIANCE){$SquidActHasReverse=1;}
		
	}
	
	$squid=new squidbee();
	if($squid->isNGnx()){$SquidActHasReverse=1;}
	
	if($SquidActHasReverse==1){
		unset($array["listen_addresses"]);
	}
	
	$array["modules"]='{available_modules}';
	
	if($users->TOMCAT_INSTALLED){
		$array["tomcat"]='{APP_TOMCAT}';
	}
		

	$array["groupwares"]='{groupwares}';
	
	$array["events"]='{events}';
	

	
	
	
	if(isset($_GET["force-groupware"])){
		unset($array["index"]);
		unset($array["params"]);
		unset($array["modules"]);
		
		unset($array["tomcat"]);
		$force_groupware="&force-groupware={$_GET["force-groupware"]}&ForceInstanceZarafaID={$_GET["ForceInstanceZarafaID"]}";
	}
	
	$fontsize="style='font-size:18px'";
	if(count($array)<8){
		$fontsize="style='font-size:24px'";
	}
	
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="pure-ftpd"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"pureftp.index.php?pure-ftpd-page=yes\" $fontsize><span $fontsize>$ligne</span></a></li>\n");
			continue;
		}
		if($num=="events"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"apache.watchdog-events.php\" $fontsize><span $fontsize>$ligne</span></a></li>\n");
			continue;
		}
		
		
		if($num=="tomcat"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"tomcat.php\" $fontsize><span $fontsize>$ligne</span></a></li>\n");
			continue;
		}

		if($num=="groupwares"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"freewebs.install.php?full-expand=yes\" $fontsize><span $fontsize>$ligne</span></a></li>\n");
			continue;
		}

		if($num=="WebCopy"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"freewebs.HTTrack.php\" $fontsize><span $fontsize>$ligne</span></a></li>\n");
			continue;
		}

		if($num=="listen_addresses"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"freewebs.addresses.php?t=$t$force_groupware\" $fontsize><span $fontsize>$ligne</span></a></li>\n");
			continue;
		}		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes$force_groupware&tabzarafa={$_GET["tabzarafa"]}\" $fontsize><span $fontsize>$ligne</span></a></li>\n");
	}
	
	
	echo build_artica_tabs($html, "main_config_freeweb")."<script>LeftDesign('web-white-256-opac20.png');</script>";
	
}

function index(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$users=new usersMenus();
	$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
	$FreeWebListen=$sock->GET_INFO("FreeWebListen");
	$FreeWebListenPort=$sock->GET_INFO("FreeWebListenPort");
	$FreeWebListenSSLPort=$sock->GET_INFO("FreeWebListenSSLPort");
	$FreeWebListen=$sock->GET_INFO("FreeWebListen");
	$FreeWebLeftMenu=intval($sock->GET_INFO("FreeWebLeftMenu"));
	$FreeWebDisableSSL=$sock->GET_INFO("FreeWebDisableSSL");
	if($FreeWebListen==null){$FreeWebListen="*";}
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}
	if(!is_numeric($FreeWebDisableSSL)){$FreeWebDisableSSL=0;}
	if($FreeWebListenPort==null){$FreeWebListenPort=80;}
	if($FreeWebListenSSLPort==null){$FreeWebListenSSLPort=443;}
	
	$tcp=new networking();
	$APACHE_APPLIANCE=0;
	if($users->APACHE_APPLIANCE){$APACHE_APPLIANCE=1;}
	
	
	
	if($users->roundcube_installed){
		$sock=new sockets();
		$RoundCubeHTTPEngineEnabled=$sock->GET_INFO("RoundCubeHTTPEngineEnabled");
		if($RoundCubeHTTPEngineEnabled==1){
			include_once('ressources/class.roundcube.inc');
			$round=new roundcube();
			if($round->https_port==$FreeWebListenSSLPort){
				if($FreeWebDisableSSL==0){
					$warnRoundCubeSamePort="<table style=width:99%' class=form><tbody><tr><td width=1%><img src='img/warning-panneau-64.png'></td><td style='color:#C00505;font-size:14px'>{WARNING_ROUNDCUBEHTTP_SAMEHTTPS_PORT}</td></tr></tbody></table>";
				}
			}
		}
		
	}

	if($users->ZARAFA_INSTALLED){
		$ZarafaApacheEnable=$sock->GET_INFO("ZarafaApacheEnable");
		if(!is_numeric($ZarafaApacheEnable)){$ZarafaApacheEnable=1;}
		if($EnableFreeWeb==1){
			if($ZarafaApacheEnable==1){
			$warnZarafa="<table style='width:99%' class=form>
			<tr>
				<td valign='top' width=1%><img src='img/warning-panneau-64.png'>
				<td valign='top' width=99%>
					<div style='font-size:14px;color:#A70000;text-decoration:underline'>{ZARAFA_WEB_NOT_USEFUL}</div>
					<div style='text-align:right'><hr>". button("{disable_zarafa_web_service}",'disable_zarafa_web_service()',"13px")."</div>
				</td>
			</tr>
			</table> 
			
			";
			}
		}
		
	}
	
	
	$ips=$tcp->ALL_IPS_GET_ARRAY();
	$ips["*"]="{all}";
	
	$TOTAL_MEMORY_MB=$sock->getFrameWork("system.php?TOTAL_MEMORY_MB=yes");
	
	$p=Paragraphe_switch_img("{enable_freeweb}","{enable_freeweb_text}",
			"EnableFreeWeb",$EnableFreeWeb,null,570);
	
	if($TOTAL_MEMORY_MB<1500){
		$p_error=FATAL_ERROR_SHOW_128("{NO_ENOUGH_MEMORY_FOR_THIS_SECTION}<br><strong style='font-size:18px'>{require}:1500MB {current}:{$TOTAL_MEMORY_MB}MB</strong>",true,true)."<p>&nbsp;</p>";
	}
	
	

	$html="
	<table style='width:100%'>
	<tr>
		<td valign='top'>
			<div id='traffic-today2'></div>

				
			<hr>
			<div id='apache-src-status'></div>
		</td>
		<td valign='top'>
				<table class=form>
				<tr>
					<td class=legend style='font-size:18px'>{add_to_menu}:</td>
					<td>". Field_checkbox_design("FreeWebLeftMenu",1,$FreeWebLeftMenu,"FreeWebLeftMenuCheck()")."</td>
				</tr>
				<tr>
				<td colspan=2>
					$p_error
					$p
					<hr>
					<div style='width:100%;text-align:right'>". button("{apply}","EnableFreeWebSave()",30)."</div>
				</td>
				</tr>
				</table>
				<p>&nbsp;</p>		
		
		
		
			$warnZarafa
			$warnRoundCubeSamePort
			<div id='ApacheMonit'></div>
			
			
		</td>
	</tr>
	</table>
	
	<script>
	
	function LoadWatchdogConfig(){
		LoadAjax('ApacheMonit','$page?watchdog-popup=yes');
	}
	
	function statusRefresh(){
		LoadAjax('apache-src-status','$page?apache-src-status=yes');
	}
	
	var x_EnableFreeWebSave=function (obj) {
			var results=obj.responseText;
			Loadjs('freeweb.progress.php');
			
		}	
		
		function EnableFreeWebSave(){
			var XHR = new XHRConnection();
    		XHR.appendData('EnableFreeWeb',document.getElementById('EnableFreeWeb').value);
    		XHR.sendAndLoad('$page', 'GET',x_EnableFreeWebSave);
			
		}	
		
		var x_FreeWebLeftMenuCheck=function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			AjaxTopMenu('template-top-menus','admin.top.menus.php');			
			
		}

		function FreeWebDisableFreeWebLeftMenu(){
			var APACHE_APPLIANCE=$APACHE_APPLIANCE;
			if(APACHE_APPLIANCE==1){document.getElementById('FreeWebLeftMenu').disabled=true;}
		}


		
		function FreeWebLeftMenuCheck(){
			var XHR = new XHRConnection();
			if(document.getElementById('FreeWebLeftMenu').checked){
	    			XHR.appendData('FreeWebLeftMenu',1);
				}else{
					XHR.appendData('FreeWebLeftMenu',0);
				}
				XHR.sendAndLoad('$page', 'GET',x_FreeWebLeftMenuCheck);
		}
		
		function disable_zarafa_web_service(){
			var XHR = new XHRConnection();
			XHR.appendData('DisableZarafaWebService',1);
			XHR.sendAndLoad('$page', 'GET',x_EnableFreeWebSave);
		}
		
	statusRefresh();
	FreeWebDisableFreeWebLeftMenu();
	LoadWatchdogConfig();
	LoadAjax('traffic-today2','freeweb.edit.status.php?status=yes');
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function saveEnableFreeWeb(){
	$sock=new sockets();
	$sock->SET_INFO("EnableFreeWeb",$_GET["EnableFreeWeb"]);
	if($_GET["EnableFreeWeb"]==1){
		$sock->getFrameWork("freeweb.php?changeinit-on=yes");
	}else{
		$sock->getFrameWork("freeweb.php?changeinit-off=yes");
	}
	
	$sock->SET_INFO("EnableApacheSystem",$_GET["EnableFreeWeb"]);

	if($_GET["EnableFreeWeb"]==null){$_GET["EnableFreeWeb"]="*";}
	if($_GET["EnableFreeWeb"]==1){$sock->SET_INFO("PureFtpdEnabled",1);}
	$sock->getFrameWork("cmd.php?restart-artica-status=yes");
	$sock->getFrameWork("cmd.php?freeweb-restart=yes");
	
	}

function popup_webs(){

	$tpl=new templates();
	$page=CurrentPageName();
	$html="

	<div style='width:100%;'>
	
		
	<div id='freewebs_list' style='margin:5px'></div>
	
	<script>
		LoadAjax('freewebs_list','$page?listwebs=yes&force-groupware={$_GET["force-groupware"]}&ForceInstanceZarafaID={$_GET["ForceInstanceZarafaID"]}&tabzarafa={$_GET["tabzarafa"]}');
		
		var x_RebuildFreeweb=function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}			
			LoadAjax('freewebs_list','$page?listwebs=yes&force-groupware={$_GET["force-groupware"]}&ForceInstanceZarafaID={$_GET["ForceInstanceZarafaID"]}&tabzarafa={$_GET["tabzarafa"]}');
		}			
		
		function RebuildFreeweb(){
			var XHR = new XHRConnection();
			XHR.appendData('rebuild-items','yes');
			AnimateDiv('freewebs_list');
    		XHR.sendAndLoad('$page', 'GET',x_RebuildFreeweb);
		
		}
		
	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}	

function listwebs(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=time();
	
	$html="<div style='width:100%' id='listwebs-$t'></div>
	
	<script>
		LoadAjax('listwebs-$t','freeweb.servers.php?force-groupware={$_GET["force-groupware"]}&ForceInstanceZarafaID={$_GET["ForceInstanceZarafaID"]}&tabzarafa={$_GET["tabzarafa"]}');
	</script>
	
	
	";
	
	echo $html;
	return;
	
	
	$html="
	<center>
	<table style='width:99%' class=form style='width:50%'>
	<tr>
	<td class=legend style='font-size:14px'>{search}:</td>
	<td>". Field_text("freewebs-search",null,"font-size:14px;padding:3px",null,null,null,false,"FreeWebsSearchCheck(event)")."</td>
	</tr>
	</table>
	</center>
	<div id='$t'></div>
	<script>
		function FreeWebsSearchCheck(e){
			if(checkEnter(e)){FreeWebsSearch();}
		}
		function FreeWebsSearch(){
			var se=escape(document.getElementById('freewebs-search').value);
			LoadAjax('$t','$page?listwebs-search=yes&search='+se+'&force-groupware={$_GET["force-groupware"]}&ForceInstanceZarafaID={$_GET["ForceInstanceZarafaID"]}');
		}
			
		FreeWebsSearch();	
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}


function listwebs_search(){
	include_once(dirname(__FILE__).'/ressources/class.apache.inc');
	$vhosts=new vhosts();
	$search=$_GET["search"];
	$page=CurrentPageName();
	$users=new usersMenus();
	$DNS_INSTALLED=false;
	$where=null;
	$query_groupware=null;
	$addg=imgtootltip("plus-24.png","{add} {joomlaservername}","Loadjs('freeweb.edit.php?hostname=&force-groupware={$_GET["force-groupware"]}')");
	
	if($_GET["force-groupware"]<>null){
		if($_GET["force-groupware"]=="ZARAFA-WEBS"){
			if($_GET["ForceInstanceZarafaID"]>0){$ForceInstanceZarafaIDQ=" AND ForceInstanceZarafaID={$_GET["ForceInstanceZarafaID"]}";}
			$query_groupware=" AND ((groupware='ZARAFA'$ForceInstanceZarafaIDQ) OR (groupware='ZARAFA_MOBILE'$ForceInstanceZarafaIDQ) OR (groupware='Z-PUSH'$ForceInstanceZarafaIDQ))";
			$addg="&nbsp;";
		}
		if($query_groupware==null){
			$query_groupware=" AND groupware='{$_GET["force-groupware"]}'";
		}
	}
	
	if(!$users->AsSystemAdministrator){
		$whereOU="  AND ou='{$_SESSION["ou"]}'";$ou="&nbsp;&raquo;&nbsp;{$_SESSION["ou"]}";
	}
	
	if(strlen($search)>1){
		$search="*$search*";
		$search=str_replace("*","%",$search);
		$search=str_replace("%%","%",$search);
		$whereOU="AND (servername LIKE '$search' $whereOU$query_groupware) OR (domainname LIKE '$search' $whereOU$query_groupware)";
	}else{
		$query_groupware_single=$query_groupware;
	}
	
	if($users->dnsmasq_installed){$DNS_INSTALLED=true;}
	if($users->POWER_DNS_INSTALLED){$DNS_INSTALLED=true;}
	
	
	if(strlen($search)<2){
		$sock=new sockets();
		$EnableWebDavPerUser=$sock->GET_INFO("EnableWebDavPerUser");
		if(!is_numeric($EnableWebDavPerUser)){$EnableWebDavPerUser=0;}
		$WebDavPerUserSets=unserialize(base64_decode($sock->GET_INFO("WebDavPerUserSets")));
		
		if($EnableWebDavPerUser==1){
				$icon="webdav-32.png";
				$groupware="<span style='text-align:right;font-size:11px;font-weight:bold;font-style:italic;color:#B64B13;float:right'>&nbsp;WebDav</span>";
				$href="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('freeweb.webdavusr.php')\" style='font-size:13px;text-decoration:underline;font-weight:bold'>";
				$edit=imgtootltip($icon,"{apply} *.{$WebDavPerUserSets["WebDavSuffix"]}","Loadjs('freeweb.webdavusr.php')");
				if($WebDavPerUserSets["EnableSSL"]==1){$ssl="20-check.png";}else{$ssl="none-20.png";}
				
		$WebdavTR="
			<tr class=$classtr>
			<td width=1%>$edit</td>
			<td nowrap style='color:$color'><span style='float:left'>
			<strong style='font-size:13px;style='color:$color'>$href*.{$WebDavPerUserSets["WebDavSuffix"]}</a></strong></span>
			</td>
			
			<td width=1% style='font-size:11px;font-weight:bold;color:#5F5656;'>&nbsp;</td>
			<td width=1% style='font-size:11px;font-weight:bold;color:#5F5656;'>&nbsp;</td>
			<td width=1%><img src='img/$ssl'></td>
			<td width=1% align='center'>&nbsp;</td>
			<td width=1% align='center'>&nbsp;</td>
			<td width=1% align='center'>&nbsp;</td>
			<td width=1% align='center'>&nbsp;</td>
			<td width=1% align='center'>&nbsp;</td>
			<td width=1%>&nbsp;</td>
			</tr>
			";
		}
	}	
	
	$tpl=new templates();	
	$sock=new sockets();	
	
	$delete_freeweb_text=$tpl->javascript_parse_text("{delete_freeweb_text}");
	$delete_freeweb_dnstext=$tpl->javascript_parse_text("{delete_freeweb_dnstext}");
	$sql="SELECT * FROM freeweb WHERE 1 $whereOU$query_groupware_single ORDER BY servername";
	$q=new mysql();
	if(!isset($_SESSION["CheckTableWebsites"])){$q->BuildTables();$_SESSION["CheckTableWebsites"]=true;}
	
	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><code>$sql</code>";}
	$vgservices=unserialize(base64_decode($sock->GET_INFO("vgservices")));
	$html="
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
	<th width=1%>$addg</th>
	<th>{joomlaservername}:$ou</th>
	<th>{memory}</th>
	<th>{requests}</th>
	<th>SSL</th>
	<th>RESOLV</th>
	<th>DNS</th>
	<th>{member}</th>
	<th>&nbsp;</th>
	<th>&nbsp;</th>
	
	<th>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>$WebdavTR";	

	$pdns=new pdns();
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		if($ligne["useSSL"]==1){$ssl="20-check.png";}else{$ssl="none-20.png";}
		$statistics="&nbsp;";
		$exec_statistics="&nbsp;";
		$groupware=null;
		$forward_text=null;
		$checkDNS="&nbsp;";
		$checkMember="&nbsp;";
		$JSDNS=0;
		if($DNS_INSTALLED){
			$ip=$pdns->GetIpDN($ligne["servername"]);
			if($ip<>null){
				$checkDNS=imgtootltip("20-check.png","<span style=font-size:16px>{$ligne["servername"]}<hr>{dns}: $ip</span>");
				$JSDNS=1;
			}
		}
		$ServerAlias=null;
		$Params=@unserialize(base64_decode($ligne["Params"]));
		$f=array();
		if(isset($Params["ServerAlias"])){
			while (list ($host,$num) = each ($Params["ServerAlias"]) ){
				$f[]=$host;
			}
			$ServerAlias="<hr style='border: 1px'><div style='font-size:11px'><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('freeweb.edit.ServerAlias.php?servername={$ligne["servername"]}')\" style='text-decoration:underline'><i>".@implode(", ", $f)."</i></div>";
		}
		
		
		
		if($ligne["uid"]<>null){
			$checkMember=imgtootltip("20-check.png","<span style=font-size:16px>{$ligne["servername"]}<hr>{member}: {$ligne["uid"]}</span>");
		}
		
		$added_port=null;
		$icon="free-web-32.png";
		$aw=new awstats($ligne["servername"]);
		if($aw->getCountDePages()>0){
			$statistics= imgtootltip("status_statistics-22.png","{statistics}","Loadjs('awstats.view.php?servername={$ligne["servername"]}')");
		}
		
		if($aw->GET("AwstatsEnabled")){
			$exec_statistics=imgtootltip("22-recycle.png","{build_awstats_statistics}","Loadjs('awstats.php?servername={$ligne["servername"]}&execute=yes')");
		}
		
		if($vgservices["freewebs"]<>null){
			if($ligne["lvm_size"]>0){
				$ligne["lvm_size"]=$ligne["lvm_size"]*1024;
				$sizevg="&nbsp;<i style='font-size:11px'>(".FormatBytes($ligne["lvm_size"]).")</i>";
				
			}
		}
		$ServerPort=$ligne["ServerPort"];
		if($ServerPort>0){$added_port=":$ServerPort";}
		if($ligne["UseReverseProxy"]){$icon="Firewall-Move-Right-32.png";}
		
		if($ligne["groupware"]<>null){
			$groupware="<span style='text-align:right;font-size:11px;font-weight:bold;font-style:italic;color:#B64B13;float:right'>&nbsp;({{$vhosts->TEXT_ARRAY[$ligne["groupware"]]["TITLE"]}})</span>";
		}
		
		if($ligne["Forwarder"]==1){$forward_text="<span style='text-align:right;font-size:11px;font-weight:bold;font-style:italic;color:#B64B13;float:right'>{www_forward} <b>{$ligne["ForwardTo"]}</b></span>";}
		$edit=imgtootltip($icon,"{$ligne["resolved_ipaddr"]}<br>{apply}","Loadjs('freeweb.edit.php?hostname={$ligne["servername"]}')");
		
		
		$servername_text=$ligne["servername"];
		if($servername_text=="_default_"){
			$servername_text="{all}";
			$groupware="<span style='text-align:right;font-size:11px;font-weight:bold;font-style:italic;color:#B64B13;float:right'>&nbsp;({default_website})</span><br>";
		}else{
			$checkResolv=imgtootltip("20-check.png","<span style=font-size:16px>{$ligne["servername"]}<hr>{dns}: {$ligne["resolved_ipaddr"]}</span>");
				
			if(trim($ligne["resolved_ipaddr"])==null){
					$edit=imgtootltip("warning-panneau-32.png","{could_not_find_iphost}<br>{click_to_edit}","Loadjs('freeweb.edit.php?hostname={$ligne["servername"]}')");
					$checkResolv="&nbsp;";
			}
		
			
	}
		
		$href="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('freeweb.edit.php?hostname={$ligne["servername"]}')\"
		style='font-size:13px;text-decoration:underline;font-weight:bold'>";
		$color="black";
		$delete=imgtootltip("delete-24.png","{delete}","FreeWebDelete('{$ligne["servername"]}',$JSDNS)");
		$sql="SELECT ID FROM drupal_queue_orders WHERE `ORDER`='DELETE_FREEWEB' AND `servername`='{$ligne["servername"]}'";
		$ligneDrup=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));	
		if($ligne["ID"]>0){
			$edit=imgtootltip("folder-tasks-32.png","{delete}");
			$color="#8a8a8a";
			$delete=imgtootltip("delete-32-grey.png","{delete} {scheduled}");
			
		}
		$sql="SELECT ID FROM drupal_queue_orders WHERE `ORDER`='INSTALL_GROUPWARE' AND `servername`='{$ligne["servername"]}'";
		if($ligne["ID"]>0){
			$edit=imgtootltip("folder-tasks-32.png","{installing}","Loadjs('freeweb.edit.php?hostname={$ligne["servername"]}')");
			$color="#8a8a8a";
			$delete=imgtootltip("delete-32-grey.png","{installing}");
			$groupware="<span style='text-align:right;font-size:11px;font-weight:bold;font-style:italic;color:#B64B13;float:right'>&nbsp;({installing} {{$vhosts->TEXT_ARRAY[$ligne["groupware"]]["TITLE"]}})</span>";
			
		}	

		$memory="-";$requests_second="-";$traffic_second="-";$uptime=null;
		$table_name_stats="apache_stats_".date('Ym');
		$sql="SELECT * FROM $table_name_stats WHERE servername='{$ligne["servername"]}' ORDER by zDate DESC LIMIT 0,1";
		$ligneStats=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		if($ligneStats["total_memory"]>0){
			$memory=FormatBytes($ligneStats["total_memory"]/1024);
			$requests_second="{$ligneStats["requests_second"]}/s";
			$traffic_second=FormatBytes($ligneStats["traffic_second"]/1024)."/s";
			$uptime="<hr style='border:0px'><div style='text-align:left;font-size:11px;font-style:italic;color:#5F5656;float:clear'>{uptime}:{$ligneStats["UPTIME"]}</div>";
			
		}
		
		$html=$html."
			<tr class=$classtr>
			<td width=1%>$edit</td>
			<td nowrap style='color:$color'>$groupware$forward_text<span style='float:left'>
			<strong style='font-size:13px;style='color:$color'>$href$servername_text</a>$added_port$sizevg</strong></span>$ServerAlias
			$uptime
			</td>
			
			<td width=1% style='font-size:11px;font-weight:bold;color:#5F5656;'>$memory</td>
			<td width=1% style='font-size:11px;font-weight:bold;color:#5F5656;'>$requests_second&nbsp;|&nbsp;$traffic_second</td>
			<td width=1%><img src='img/$ssl'></td>
			<td width=1% align='center'>$checkResolv</td>
			<td width=1% align='center'>$checkDNS</td>
			<td width=1% align='center'>$checkMember</td>
			<td width=1% align='center'>$statistics</td>
			<td width=1% align='center'>$exec_statistics</td>
			<td width=1%>$delete</td>
			</tr>
			";
		}

		
$default_www="&nbsp;&nbsp;|&nbsp;&nbsp;".button("{add_default_www}","FreeWebAddDefaultVirtualHost()");	
if($_GET["force-groupware"]<>null){	$default_www=null;}
	
	$html=$html."
	</tbody>
	</table>
	<div style='text-align:right;margin-top:8px'>". button("{recheck_net_items}","FreeWeCheckVirtualHost()")."$default_www</div>
	<script>
	var x_FreeWebDelete=function (obj) {
			var results=obj.responseText;
			if(results.length>10){alert(results);}	
			if(document.getElementById('main_config_freeweb')){	RefreshTab('main_config_freeweb');}
			if(document.getElementById('container-www-tabs')){	RefreshTab('container-www-tabs');}
		}	
		
		function FreeWebDelete(server,dns){
			if(confirm('$delete_freeweb_text')){
				var XHR = new XHRConnection();
				if(dns==1){if(confirm('$delete_freeweb_dnstext')){XHR.appendData('delete-dns',1);}else{XHR.appendData('delete-dns',0);}}
				XHR.appendData('delete-servername',server);
				AnimateDiv('freewebs_list');
    			XHR.sendAndLoad('$page', 'GET',x_FreeWebDelete);
			}
		}	
		
		function FreeWebAddDefaultVirtualHost(){
			var XHR = new XHRConnection();
			XHR.appendData('AddDefaultOne','yes');
			AnimateDiv('freewebs_list');
    		XHR.sendAndLoad('$page', 'POST',x_FreeWebDelete);		
		}
		
		function FreeWeCheckVirtualHost(){
			var XHR = new XHRConnection();
			XHR.appendData('CheckAVailable','yes');
			AnimateDiv('freewebs_list');
    		XHR.sendAndLoad('$page', 'POST',x_FreeWebDelete);			
		}
		
	</script>	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function FreeWebLeftMenuSave(){
	$sock=new sockets();
	$sock->SET_INFO("FreeWebLeftMenu",$_GET["FreeWebLeftMenu"]);
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{success}");
	
}

function delete(){
	include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");
	$tpl=new templates();
	$servername=$_GET["delete-servername"];
	$q=new mysql_squid_builder();
	
	if(!$q->TABLE_EXISTS("reverse_www")){
		$nginx=new squid_reverse();
	}
	
	if($q->TABLE_EXISTS("reverse_www")){
		$q->QUERY_SQL("DELETE FROM reverse_www WHERE servername='$servername'");
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	
	if($q->TABLE_EXISTS("reverse_privs")){
		$q->QUERY_SQL("DELETE FROM reverse_privs WHERE servername='$servername'");
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	
	if($q->TABLE_EXISTS("nginx_replace_www")){
		$q->QUERY_SQL("DELETE FROM nginx_replace_www WHERE servername='$servername'");
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	
	if($q->TABLE_EXISTS("nginx_aliases")){
		$q->QUERY_SQL("DELETE FROM nginx_aliases WHERE servername='$servername'");
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	
	if($q->TABLE_EXISTS("nginx_exploits_fw")){
		$q->QUERY_SQL("DELETE FROM nginx_exploits_fw WHERE servername='$servername'");
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	if($q->TABLE_EXISTS("nginx_exploits")){	
		$q->QUERY_SQL("DELETE FROM nginx_exploits WHERE servername='$servername'");
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	if($q->TABLE_EXISTS("nginx_exchecp")){
		$q->QUERY_SQL("DELETE FROM nginx_exchecp WHERE hostname='$servername'");
		if(!$q->ok){echo $q->mysql_error;return;}
	}	
	

	$sock=new sockets();
	$sock->getFrameWork("squid.php?reverse-proxy-apply=yes");	
	
	$free=new freeweb($_GET["delete-servername"]);
	if($free->groupware=="MAILMAN"){
		$q=new mysql();
		$sql="SELECT `list` FROM mailmaninfos WHERE `urlhost`='{$_GET["delete-servername"]}'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if($ligne["list"]<>null){
			echo $tpl->javascript_parse_text("{unable_freeweb_delete_mailman}\n- - {$ligne["list"]} - -\n");
			return;
		}
	}
	
	
	writelogs("Delete server \"{$_GET["delete-servername"]}\" delete dns={$_GET["delete-dns"]}",__FUNCTION__,__FILE__,__LINE__);
	
	if(isset($_GET["delete-dns"])){
		if($_GET["delete-dns"]==1){
			$dns=new pdns();
			$dns->DelHostname($_GET["delete-servername"]);
		}
		
	}
	
	$sql="INSERT INTO drupal_queue_orders(`ORDER`,`servername`) VALUES('DELETE_FREEWEB','{$_GET["delete-servername"]}')";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("drupal.php?perform-orders=yes");	
	}

function apache_src_status(){
	$q=new mysql();
	$sock=new sockets();
	$tpl=new templates();
	$ini=new Bs_IniHandler();
	$page=CurrentPageName();	
	$datas=$sock->getFrameWork("cmd.php?apachesrc-ini-status=yes");
	writelogs(strlen($datas)." bytes for apache status",__CLASS__,__FUNCTION__,__FILE__,__LINE__);
	$confirm_scansize=$tpl->javascript_parse_text("{confirm_scan_size}");
	$sql="SELECT SUM(DirectorySize) as tsum FROM freeweb";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$size=FormatBytes(($ligne["tsum"]/1024));
	$ini->loadString(base64_decode($datas));
	

	
	$serv[]="<center style='font-size:16px'>
		<a href=\"javascript:blur();\" OnClick=\"javascript:FreeWebsScanSize();\" style='font-size:16px;text-decoration:underline'>{disk_usage}: $size</a></center>";
	$serv[]=DAEMON_STATUS_ROUND("APP_APACHE_SRC",$ini,null,0).$table;
	
	if(!isset($_GET["withoutftp"])){
		$serv[]=DAEMON_STATUS_ROUND("APP_PHPFPM",$ini,null,0);
		$serv[]=DAEMON_STATUS_ROUND("APP_SPWANFCGI",$ini,null,0);
		$serv[]=DAEMON_STATUS_ROUND("PUREFTPD",$ini,null,0);
		$serv[]=DAEMON_STATUS_ROUND("APP_TOMCAT",$ini,null,0);
		$serv[]=DAEMON_STATUS_ROUND("APP_NGINX",$ini,null,0);
	}
	
	$refresh="<div style='text-align:right;margin-top:8px'>".imgtootltip("refresh-24.png","{refresh}","RefreshTab('main_config_freeweb')")."</div>";

		
	while (list ($a,$b) = each ($serv) ){if(trim($b)==null){continue;}$statusT[]=$b;}	
	$status=@implode("<br>", $statusT);
	


	$script="
	<script>
		var x_FreeWebsScanSize=function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}			
		
		}	
	
	
		function FreeWebsScanSize(){
			if(confirm('$confirm_scansize ?')){
				var XHR = new XHRConnection();
				XHR.appendData('FreeWebsScanSize','yes');
		    	XHR.sendAndLoad('$page', 'POST',x_FreeWebsScanSize);				
			}
		
		}
	
	</script>
	";

	
	
	echo $tpl->_ENGINE_parse_body($status.$tips.$refresh.$script);	
	
}

function rebuild_items(){
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?freewebs-rebuild=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{operation_launched_in_background}");
	
}

function mode_evasive_form(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$Params=unserialize(base64_decode($sock->GET_INFO("modEvasiveDefault")));
	
	
	if(!is_numeric($Params["DOSHashTableSize"])){$Params["DOSHashTableSize"]=1024;}
	if(!is_numeric($Params["DOSPageCount"])){$Params["DOSPageCount"]=10;}
	if(!is_numeric($Params["DOSSiteCount"])){$Params["DOSSiteCount"]=150;}
	if(!is_numeric($Params["DOSPageInterval"])){$Params["DOSPageInterval"]=1.5;}
	if(!is_numeric($Params["DOSSiteInterval"])){$Params["DOSSiteInterval"]=1.5;}
	if(!is_numeric($Params["DOSBlockingPeriod"])){$Params["DOSBlockingPeriod"]=10.7;}
	
	
	
	
	$html="
	<div class=explain id='modeevasivedef'>{mod_evasive_explain}</div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{DOSHashTableSize}:</td>
		<td>". Field_text("DOSHashTableSize",$Params["DOSHashTableSize"],"font-size:13px;padding:3px;width:60px")."</td>
		<td>". help_icon("{DOSHashTableSize_explain}")."</td>
	</tr>	
	
	
	<tr>
		<td class=legend style='font-size:14px'>{threshold}:</td>
		<td>". Field_text("DOSPageCount",$Params["DOSPageCount"],"font-size:13px;padding:3px;width:60px")."</td>
		<td>". help_icon("{DOSPageCount_explain}")."</td>
	</tr>

	
	<tr>
		<td class=legend style='font-size:14px'>{total_threshold}:</td>
		<td>". Field_text("DOSSiteCount",$Params["DOSSiteCount"],"font-size:13px;padding:3px;width:60px")."</td>
		<td>". help_icon("{DOSSiteCount_explain}")."</td>
	</tr>

	<tr>
		<td class=legend style='font-size:14px'>{page_interval}:</td>
		<td>". Field_text("DOSPageInterval",$Params["DOSPageInterval"],"font-size:13px;padding:3px;width:60px")."</td>
		<td>". help_icon("{DOSPageInterval_explain}")."</td>
	</tr>	
	


	<tr>
		<td class=legend style='font-size:14px'>{site_interval}:</td>
		<td>". Field_text("DOSSiteInterval",$Params["DOSSiteInterval"],"font-size:13px;padding:3px;width:60px")."</td>
		<td>". help_icon("{DOSSiteInterval_explain}")."</td>
	</tr>

	<tr>
		<td class=legend style='font-size:14px'>{Blocking_period}:</td>
		<td>". Field_text("DOSBlockingPeriod",$Params["DOSBlockingPeriod"],"font-size:13px;padding:3px;width:60px")."</td>
		<td>". help_icon("{DOSBlockingPeriod_explain}")."</td>
	</tr>	
	
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","SaveModEvasiveDefault()")."</td>
	</tr>
	</table>
	
	<script>
	var x_SaveModEvasiveDef=function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}			
		YahooWin3Hide();
	}	
	
	function SaveModEvasiveDefault(){
		var XHR = new XHRConnection();
		XHR.appendData('DOSHashTableSize',document.getElementById('DOSHashTableSize').value);
		XHR.appendData('DOSPageCount',document.getElementById('DOSPageCount').value);
		XHR.appendData('DOSSiteCount',document.getElementById('DOSSiteCount').value);
		XHR.appendData('DOSPageInterval',document.getElementById('DOSPageInterval').value);
		XHR.appendData('DOSSiteInterval',document.getElementById('DOSSiteInterval').value);
		XHR.appendData('DOSBlockingPeriod',document.getElementById('DOSBlockingPeriod').value);
		AnimateDiv('modeevasivedef');
    	XHR.sendAndLoad('$page', 'POST',x_SaveModEvasiveDef);		
	}

	</script>	
	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}
function mode_evasive_save(){
	$sock=new sockets();
	$sock->SaveConfigFile(base64_encode(serialize($_POST)), "modEvasiveDefault");
	$sock->getFrameWork("freeweb.php?reconfigure=yes");
}

function modules_list(){
	
		$tpl=new templates();
		$array["apache_modules"]="Apache modules";
		$array["modules"]="{loaded_modules}";
		$page=CurrentPageName();

	
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="modules"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"phpconfig.php?modules=yes&tablesize=852&tableheight=453&rowsize=777\"><span style='font-size:14px'>$ligne</span></a></li>\n");
			continue;
			
		}
		
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span style='font-size:14px'>$ligne</span></a></li>\n");
	}
	
	
	echo build_artica_tabs($html, "main_config_freewbsphpadv");

	
}

function modules_apache(){
	
	$page=CurrentPageName();
	$t=time();

	

	
	$html="
	<table class='table-$t' style='display: none' id='table-$t' style='width:100%;margin:-10px'></table>
<script>
memedb$t='';
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?apache-modules-list=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'none', width : 31, sortable : false, align: 'center'},
		{display: 'Modules', name : 'Modules', width :750, sortable : true, align: 'left'},
		
	],
	
	$buttons

	searchitems : [
		{display: 'Modules', name : 'Modules'},
		
		],
	sortname: 'Modules',
	sortorder: 'asc',
	usepager: true,
	title: 'Apaches Modules',
	useRp: false,
	rp: 50,
	showTableToggleBtn: false,
	width: 832,
	height: 453,
	singleSelect: true
	
	});   
});
	
</script>
	";
		
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);		
}	
	


function modules_apache_list(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();	
	$results=unserialize(base64_decode($sock->getFrameWork("freeweb.php?loaded-modules=yes")));

	
if($_POST["query"]<>null){
		$search=$_POST["query"];
		$search=str_replace(".", "\.",$search);
		$search=str_replace("*", ".*?",$search);
	}	
	
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = $total;
	$data['rows'] = array();	
	
	while (list ($num, $ligne) = each ($results) ){
		if(!preg_match("#(.+?)\s+\((.+?)\)#",$ligne,$re)){continue;}
		
	if($search<>null){
			if(!preg_match("#$search#", $re[1])){continue;}
		}		
		
			$c++;	
	$data['rows'][] = array(
				'id' => $module,
				'cell' => array(
					"<img src='img/arrow-right-24.png'>",
					"<strong style='font-size:16px'>{$re[1]}</strong> ({$re[2]})",
					
					)
				);

			
	}
	$data['total'] = $c;
	echo json_encode($data);
}

function add_default_site(){
	$free=new freeweb();
	$free->AddDefaultSite();
}
function CheckAVailable(){
	$sock=new sockets();
	$sock->getFrameWork("freeweb.php?force-resolv=yes");
}


function watchdog_form(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	$FreeWebChangeInit=$sock->GET_INFO("FreeWebChangeInit");
	if(!is_numeric($FreeWebChangeInit)){$FreeWebChangeInit=0;}
	$MonitConfig=unserialize(base64_decode($sock->GET_INFO("ApacheWatchdogMonitConfig")));
	$ZarafaWebAccessInFrontEnd=$sock->GET_INFO("ZarafaWebAccessInFrontEnd");
	$EnableNginx_warn=$tpl->javascript_parse_text("{EnableNginx_warn}");
	
	
	$EnableNginx=intval($sock->GET_INFO("EnableNginx"));
	$EnableSPAWNFCGI=intval($sock->GET_INFO("EnableSPAWNFCGI"));
	$EnablePHPFPM=intval($sock->GET_INFO("EnablePHPFPM"));
	if(!is_numeric($EnablePHPFPM)){$EnablePHPFPM=0;}
	if(!is_numeric($EnableNginx)){$EnableNginx=1;}
	if(!is_numeric($ZarafaWebAccessInFrontEnd)){$ZarafaWebAccessInFrontEnd=1;}
	
	
	
	if(!is_numeric($MonitConfig["watchdog"])){$MonitConfig["watchdog"]=1;}
	if(!is_numeric($MonitConfig["watchdogCPU"])){$MonitConfig["watchdogCPU"]=95;}
	if(!is_numeric($MonitConfig["watchdogMEM"])){$MonitConfig["watchdogMEM"]=1500;}
	if(!is_numeric($MonitConfig["watchdogTTL"])){$MonitConfig["watchdogTTL"]=1440;}
	
	$ZARAFA_INSTALLED=0;
	$MONIT_INSTALLED=0;
	$NgnixInstalled=0;
	$users=new usersMenus();
	if($users->MONIT_INSTALLED){$MONIT_INSTALLED=1;}
	if($users->NGINX_INSTALLED){$NgnixInstalled=1;}
	if($users->ZARAFA_INSTALLED){$ZARAFA_INSTALLED=1;}
	
	$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}	
	if($EnableFreeWeb==0){$MONIT_INSTALLED=0;}
	
	$t=time();
	$html="
	<div id='$t' style='margin-top:20px'>
		<table style='width:99%' class=form>
		<tbody>
	";
	
	if($users->ZARAFA_INSTALLED){
			$html=$html."<tr>
				<td class=legend style='font-size:22px' nowrap>{ZarafaWebAccessInFrontEnd}:</td>
				<td>". Field_checkbox_design("$t-ZarafaWebAccessInFrontEnd", 1,$ZarafaWebAccessInFrontEnd,"ZarafaWebAccessInFrontEnd{$t}()")."</td>
				<td>&nbsp;</td>
			</tr>
		";
	}	
			$html=$html."<tr>
				<td class=legend style='font-size:22px' nowrap>{EnableNginx}:</td>
				<td>". Field_checkbox_design("$t-EnableNginx", 1,$EnableNginx,"EnableNginx{$t}()")."</td>
				<td>&nbsp;</td>
			</tr>		
			<tr>
				<td class=legend style='font-size:22px' nowrap>{EnablePHPFPM}:</td>
				<td>". Field_checkbox_design("$t-EnablePHPFPM", 1,$EnablePHPFPM,"EnableEnablePHPFPM{$t}()")."</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td class=legend style='font-size:22px' nowrap>{EnableSPAWNFCGI}:</td>
				<td>". Field_checkbox_design("$t-EnableSPAWNFCGI", 1,$EnableSPAWNFCGI,"EnableEnableSPAWNFCGI{$t}()")."</td>
				<td>&nbsp;</td>
			</tr>
			
		
			<tr>
				<td class=legend style='font-size:22px'>{enable_watchdog}:</td>
				<td>". Field_checkbox_design("$t-watchdog", 1,$MonitConfig["watchdog"],"InstanceCheckWatchdog{$t}()")."</td>
				<td>&nbsp;</td>
			</tr>		
			<tr>
				<td class=legend style='font-size:22px'>{notify_when_cpu_exceed}:</td>
				<td style='font-size:14px'>". Field_text("$t-watchdogCPU", $MonitConfig["watchdogCPU"],"font-size:22px;width:90px")."&nbsp;%</td>
				<td>&nbsp;</td>
			</tr>	
			<tr>
				<td class=legend style='font-size:22px'>{notify_when_memory_exceed} (&nbsp;MB):</td>
				<td style='font-size:14px'>". Field_text("$t-watchdogMEM", $MonitConfig["watchdogMEM"],"font-size:22px;width:90px")."</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td class=legend style='font-size:22px'>{restart_each}&nbsp;({minutes}):</td>
				<td style='font-size:14px'>". Field_text("$t-watchdogTTL", $MonitConfig["watchdogTTL"],"font-size:22px;width:90px")."</td>
				<td>". help_icon("{restart_each_explain}")."</td>
			</tr>						
			<tr>
				<td class=legend style='font-size:22px'>{change_initd}:</td>
				<td>". Field_checkbox_design("$t-FreeWebChangeInit", 1,$FreeWebChangeInit,"FreeWebChangeInit()")."</td>
				<td>". help_icon("{change_initd_explain}")."</td>
			</tr>			
			<tr>
				<td colspan=3 align='right'><hr>". button("{apply}", "SaveWatchdog{$t}()",30)."</td>
			</tr>	
		</tbody>
	</table>
</div>
<script>
	function InstanceCheckWatchdog{$t}(){
		var MONIT_INSTALLED=$MONIT_INSTALLED;
		document.getElementById('$t-watchdog').disabled=true;
		document.getElementById('$t-watchdogMEM').disabled=true;
		document.getElementById('$t-watchdogCPU').disabled=true;
		document.getElementById('$t-watchdogTTL').disabled=true;	
		if(MONIT_INSTALLED==0){return;}
		document.getElementById('$t-watchdog').disabled=false;
		if(!document.getElementById('$t-watchdog').checked){return;}
		document.getElementById('$t-watchdogMEM').disabled=false;
		document.getElementById('$t-watchdogCPU').disabled=false;
		document.getElementById('$t-watchdogTTL').disabled=false;			
	
	}
	
	var x_{$t}_SaveInstance= function (obj) {
			LoadWatchdogConfig();
		}	
	

	
	function ZarafaWebAccessInFrontEnd{$t}(){
		var XHR = new XHRConnection();
		ZarafaWebAccessInFrontEnd=0;
		if(document.getElementById('$t-ZarafaWebAccessInFrontEnd').checked){ZarafaWebAccessInFrontEnd=1;}
		XHR.appendData('ZarafaWebAccessInFrontEnd',ZarafaWebAccessInFrontEnd);
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_{$t}_SaveInstance);
		}
		
	function ZarafaWebAccessInFrontEnd{$t}Check(){
		if(!document.getElementById('$t-ZarafaWebAccessInFrontEnd')){return;}
		var ZARAFA_INSTALLED=$ZARAFA_INSTALLED;
		if(ZARAFA_INSTALLED==0){
			document.getElementById('$t-ZarafaWebAccessInFrontEnd').disabled=true;
		}
	}
	
	function EnableNginx{$t}(){
		var XHR = new XHRConnection();
		EnableNginx=0;
		if(document.getElementById('$t-EnableNginx').checked){EnableNginx=1;}
		XHR.appendData('EnableNginx',EnableNginx);
		if(EnableNginx==0){
			if(confirm('$EnableNginx_warn')){
				XHR.sendAndLoad('$page', 'POST',x_{$t}_SaveInstance);
				EnableNginxCheck{$t}
			}
			return;
		}
		XHR.sendAndLoad('$page', 'POST',x_{$t}_SaveInstance);
		EnableNginxCheck{$t}
	}
	
	function EnableEnablePHPFPM{$t}(){
		var XHR = new XHRConnection();
		var EnablePHPFPM=0;
		if(document.getElementById('$t-EnablePHPFPM').checked){EnablePHPFPM=1;}
		XHR.appendData('EnablePHPFPM',EnablePHPFPM);
		XHR.sendAndLoad('$page', 'POST',x_{$t}_SaveInstance);
		
	}
	function EnableEnableSPAWNFCGI{$t}(){
		var XHR = new XHRConnection();
		var EnableSPAWNFCGI=0;
		if(document.getElementById('$t-EnableSPAWNFCGI').checked){EnableSPAWNFCGI=1;}
		XHR.appendData('EnableSPAWNFCGI',EnableSPAWNFCGI);
		XHR.sendAndLoad('$page', 'POST',x_{$t}_SaveInstance);
		
	}	


	
	
	var x_{$t}_SaveInstance= function (obj) {
			LoadWatchdogConfig();
		}	
	
	function SaveWatchdog{$t}(){
		var XHR = new XHRConnection();	
		if(document.getElementById('$t-watchdog').checked){XHR.appendData('watchdog',1);}else{XHR.appendData('watchdog',0);}
		if(document.getElementById('$t-FreeWebChangeInit').checked){XHR.appendData('FreeWebChangeInit',1);}else{XHR.appendData('FreeWebChangeInit',0);}
		XHR.appendData('watchdogMEM',document.getElementById('$t-watchdogMEM').value);
		XHR.appendData('watchdogCPU',document.getElementById('$t-watchdogCPU').value);
		XHR.appendData('watchdogTTL',document.getElementById('$t-watchdogTTL').value);
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_{$t}_SaveInstance);
	}

	function FreeWebChangeInit(){
		var XHR = new XHRConnection();	
		if(document.getElementById('$t-FreeWebChangeInit').checked){XHR.appendData('FreeWebChangeInit',1);}else{XHR.appendData('FreeWebChangeInit',0);}
		XHR.sendAndLoad('$page', 'POST');
	}
	
	ZarafaWebAccessInFrontEnd{$t}Check();
</script>

";
	
	
echo $tpl->_ENGINE_parse_body($html);
	
}

function watchdog_save(){
	$sock=new sockets();
	$final=base64_encode(serialize($_POST));
	$sock->SaveConfigFile($final, "ApacheWatchdogMonitConfig");
	$sock->getFrameWork("freeweb.php?watchdog-config=yes");	
	$sock->SET_INFO("FreeWebChangeInit", $_POST["FreeWebChangeInit"]);

	
}

function changeInit(){
	$sock=new sockets();
	$sock->SET_INFO("FreeWebChangeInit", $_POST["FreeWebChangeInit"]);
	if($_POST["FreeWebChangeInit"]==1){
		
		$sock->getFrameWork("freeweb.php?changeinit-on=yes");	
	}else{
		$sock->getFrameWork("freeweb.php?changeinit-off=yes");	
	}	
	
}

function DisableZarafaWebService(){
	$sock=new sockets();
	$sock->SET_INFO("ZarafaApacheEnable","0");
	$sock->getFrameWork("cmd.php?zarafa-stop-web=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{ZARAFAWEBBESTOP}");
}

function log_rotate_save(){
	$sock=new sockets();
	$sock->SaveConfigFile(base64_encode(serialize($_POST)), "ApacheLogRotateParams");
	
}

function log_rotate_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$buttontext="{apply}";
	$sock=new sockets();
	$ligne=unserialize(base64_decode($sock->GET_INFO("ApacheLogRotateParams")));
	
	
		
	if(!is_numeric($ligne["RotateType"])){$ligne["RotateType"]=0;}
	$RotateFreq["daily"]="{daily}";
	$RotateFreq["weekly"]="{weekly}";
	if(!is_numeric($ligne["MaxSize"])){$ligne["MaxSize"]=100;}
	if(!is_numeric($ligne["RotateCount"])){$ligne["RotateCount"]=5;}
	$t=time();
	

	
	$html="
	<div class=explain style='font-size:14px'>{ApacheLogRotate_explain}</div>
	<div id='div-$t'>
	<table style='width:99%' class='form'>
	<tr>
		<td class=legend style='font-size:14px'>{interval}:</td>
		<td>". Field_array_Hash($RotateFreq,"RotateFreq",$ligne["RotateFreq"],"style:font-size:14px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{MaxRotation}:</td>
		<td>". Field_text("RotateCount", $ligne["RotateCount"],"font-size:14px;width:60px")."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:14px'>{MaxSize}:</td>
		<td style='font-size:14px'>". Field_text("MaxSize", $ligne["MaxSize"],"font-size:14px;width:90px")."&nbsp;M</td>
	</tr>	
	
	<tr>
		<td colspan=2 align='right'><hr>". button($buttontext,"SaveTaskLogRotate$t()",16)."</td>
	</tr>
	</table>
	</div>
	<script>
		
		
	var x_SaveTaskLogRotate$t=function (obj) {
		var ID='{$_GET["ID"]}';
		var results=obj.responseText;
		if(results.length>0){alert(results);}		
		YahooWin4Hide();
		
	}	


	function SaveTaskLogRotate$t(){
		var XHR = new XHRConnection();
		XHR.appendData('RotateFreq',document.getElementById('RotateFreq').value);
		XHR.appendData('RotateCount',document.getElementById('RotateCount').value);
		XHR.appendData('MaxSize',document.getElementById('MaxSize').value);
	  	AnimateDiv('div-$t');
	  	XHR.sendAndLoad('$page', 'POST',x_SaveTaskLogRotate$t);
	}		
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function FreeWebsScanSize(){
	$tpl=new templates();
	$success=$tpl->javascript_parse_text("{success}");
	$sock=new sockets();
	$sock->getFrameWork("freeweb.php?ScanSize=yes");
	echo $success;
}

function EnablePHPFPM(){
	$sock=new sockets();
	$sock->SET_INFO("EnablePHPFPM",$_POST["EnablePHPFPM"]);
	$sock->getFrameWork("nginx.php?restart=yes");	
	$sock->getFrameWork("services.php?restart-phpfpm=yes");
}
function EnableSPAWNFCGI(){
	$sock=new sockets();
	$sock->SET_INFO("EnableSPAWNFCGI",$_POST["EnableSPAWNFCGI"]);
	$sock->getFrameWork("nginx.php?restart=yes");
	$sock->getFrameWork("services.php?restart-SPAWNFCGI=yes");
}

function EnableNginx(){
	$sock=new sockets();
	$sock->SET_INFO("EnableNginx",$_POST["EnableNginx"]);
	$sock->getFrameWork("nginx.php?restart=yes&enable={$_POST["EnableNginx"]}");
	$sock->getFrameWork("services.php?restart-phpfpm=yes");	
}
function ZarafaWebAccessInFrontEnd(){
	$sock=new sockets();
	$sock->SET_INFO("ZarafaWebAccessInFrontEnd",$_POST["ZarafaWebAccessInFrontEnd"]);
}

?>
	
	