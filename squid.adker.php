<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.ccurl.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	include_once('ressources/class.system.nics.inc');
	include_once('ressources/class.resolv.conf.inc');
	
	
	if(isset($_GET["status"])){status_kerb();exit;}
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	
	if(isset($_POST["auth_param_ntlm_children"])){ ntlmauthenticators_save();exit;}
	if(isset($_GET["settings-ad"])){settings_ad();exit;}
	
	if(isset($_GET["kerberos-hostname-js"])){kerberos_hostname_js();exit;}
	if(isset($_GET["proxyHostnameKerbDiv"])){kerberos_hostname_div();exit;}
	if(isset($_POST["ActiveDirectorySquidHTTPHostname"])){kerberos_hostname_save();exit;}
	if(isset($_GET["cntml-status"])){cntlm_status();exit;}
	if(isset($_GET["ldap-params"])){ldap_params();exit;}
	if(isset($_GET["schedule-params"])){schedule_params();exit;}
	if(isset($_POST["AdSchBuildProxy"])){schedule_save();exit;}
	if(isset($_GET["testauth-results"])){test_auth_results();exit;}
	if(isset($_POST["EnableCNTLM"])){EnableCNTLM_save();exit;}
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["settings"])){settings();exit;}
	
	if(isset($_POST["SAVE_RECURSIVE_GROUPS"])){SAVE_RECURSIVE_GROUPS();exit;}
	if(isset($_POST["EnableKerbAuth"])){settingsSave();exit;}
	if(isset($_POST["SambeReconnectAD"])){SambeReconnectAD();exit;}
	
	if(isset($_GET["kerbchkconf"])){kerbchkconf();exit;}
	
	if(isset($_GET["test-js"])){test_js();exit;}
	if(isset($_GET["test-popup"])){test_popup();exit;}
	if(isset($_GET["test-nettestjoin"])){test_testjoin();exit;}
	if(isset($_GET["test-netadsinfo"])){test_netadsinfo();exit;}
	if(isset($_GET["test-netrpcinfo"])){test_netrpcinfo();exit;}
	if(isset($_GET["test-wbinfoalldom"])){test_wbinfoalldom();exit;}
	if(isset($_GET["test-wbinfomoinst"])){test_wbinfomoinst();exit;}
	if(isset($_GET["test-wbinfomoinsa"])){test_wbinfomoinsa();exit;}
	if(isset($_GET["ntlmauthenticators"])){ntlmauthenticators();exit;}
	if(isset($_GET["statistics-by-group"])){statistics_groups();exit;}
	
	if(isset($_GET["test-auth"])){test_auth();exit;}
	if(isset($_POST["SaveSambaBindInterface"])){SaveSambaBindInterface();exit;}
	if(isset($_POST["TESTAUTHUSER"])){test_auth_perform();exit;}
	if(isset($_POST["LDAP_SUFFIX"])){ldap_params_save();exit;}
	if(isset($_GET["test-popup-js"])){test_popup_js();exit;}
	if(isset($_GET["intro"])){intro();exit;}
	if(isset($_GET["join-js"])){join_js();exit;}
	if(isset($_GET["join-popup"])){join_popup();exit;}
	if(isset($_GET["join-perform"])){join_perform();exit;}
	
	if(isset($_GET["diconnect-js"])){diconnect_js();exit;}
	if(isset($_GET["disconnect-popup"])){diconnect_popup();exit;}
	if(isset($_GET["disconnect-perform"])){diconnect_perform();exit;}
	if(isset($_GET["cntlm"])){cntlm();exit;}
	
js();

function join_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{restart_connection}");
	echo "YahooWin6('905','$page?join-popup=yes','$title')";
	
}
function diconnect_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{disconnect}");
	echo "YahooWin6('905','$page?disconnect-popup=yes','$title')";
	
}
function test_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{analyze}");
	echo "YahooWin6('905','$page?test-popup=yes','$title')";	
}

function kerberos_hostname_div(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$sock=new sockets();
	$ActiveDirectorySquidHTTPHostname=$sock->GET_INFO("ActiveDirectorySquidHTTPHostname");
	if($ActiveDirectorySquidHTTPHostname==null){
		$hostname=$sock->GET_INFO("myhostname");
		if($hostname==null){$hostname=$sock->getFrameWork("system.php?hostname-g=yes");}
		$ActiveDirectorySquidHTTPHostname=$hostname;
	}

	echo $tpl->_ENGINE_parse_body(texttooltip("HTTP/$ActiveDirectorySquidHTTPHostname","{kerberos_proxy_hostname_explain}","Loadjs('$page?kerberos-hostname-js=yes')"));
	
}

function kerberos_hostname_save(){
	$sock=new sockets();
	$sock->SET_INFO("ActiveDirectorySquidHTTPHostname", trim(strtolower($_POST["ActiveDirectorySquidHTTPHostname"])));
}

function kerberos_hostname_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$sock=new sockets();
	$ActiveDirectorySquidHTTPHostname=$sock->GET_INFO("ActiveDirectorySquidHTTPHostname");
	if($ActiveDirectorySquidHTTPHostname==null){
		$hostname=$sock->GET_INFO("myhostname");
		if($hostname==null){$hostname=$sock->getFrameWork("system.php?hostname-g=yes");}
		$ActiveDirectorySquidHTTPHostname=$hostname;
	}
	
	$explain=$tpl->javascript_parse_text("{kerberos_proxy_hostname_explain}");
	
echo"var xSave$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		LoadAjaxTiny('proxyHostnameKerbDiv','$page?proxyHostnameKerbDiv=yes');
	}
	
	function Save$t(){
		var host=prompt('$explain','$ActiveDirectorySquidHTTPHostname');
		if(!host){return;}
		var XHR = new XHRConnection();
		XHR.appendData('ActiveDirectorySquidHTTPHostname',host);
		XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
	
	Save$t();
";

}

function join_perform(){
	$sock=new sockets();
	$users=new usersMenus();
	$t=$_GET["t"];
	$datas=unserialize(base64_decode($sock->getFrameWork("squid.php?join-reste=yes&MyCURLTIMEOUT=300")));
	$text=@implode("\n", $datas);
	$html="<textarea style='width:100%;height:550px;font-size:11.5px;overflow:auto;border:1px solid #CCCCCC;padding:5px'>$text</textarea>
	<script>
		document.getElementById('$t-center').innerHTML='';
	</script>
	
	";	
	
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}	

	if($EnableWebProxyStatsAppliance==1){
		include_once(dirname(__FILE__)."/ressources/class.blackboxes.inc");
		$bb=new blackboxes();
		$bb->NotifyAll("AD_CONNECT");
	}
	
	echo $html;
}
function diconnect_perform(){
	$sock=new sockets();
	$t=$_GET["t"];
	$datas=unserialize(base64_decode($sock->getFrameWork("squid.php?disconnect-reste=yes&MyCURLTIMEOUT=300")));
	$text=@implode("\n", $datas);
	$html="<textarea style='width:100%;height:550px;font-size:11.5px;overflow:auto;border:1px solid #CCCCCC;padding:5px'>$text</textarea>
	<script>
		document.getElementById('$t-center').innerHTML='';
		RefreshTab('main_adker_tabs');
	</script>
	";

	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}	

	if($EnableWebProxyStatsAppliance==1){
		include_once(dirname(__FILE__)."/ressources/class.blackboxes.inc");
		$bb=new blackboxes();
		$bb->NotifyAll("AD_DISCONNECT");
	}	
	
	echo $html;	
}

function join_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$t=time();
	$html="
	<center style='font-size:18px' id='$t-center'>{please_wait}...<p>&nbsp;</p><p>&nbsp;</p></center>
	<div id='$t' style='margin-bottom:20px'></div>
	<script>
		LoadAjax('$t','$page?join-perform=yes&t=$t');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}
function diconnect_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$t=time();
	$html="
	<center style='font-size:18px' id='$t-center'>{please_wait}...<p>&nbsp;</p><p>&nbsp;</p></center>
	<div id='$t' style='margin-bottom:20px'></div>
	<script>
		LoadAjax('$t','$page?disconnect-perform=yes&t=$t');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);	
}

function status_kerb(){
	$sock=new sockets();
	$EnableKerbAuth=intval($sock->GET_INFO("EnableKerbAuth"));
	
	$tpl=new templates();
	$t=time();
	$squid=new squidbee();

	if($EnableKerbAuth==0){echo"<script>UnlockPage();</script>";return;}
	$ActiveDirectoryEmergency=intval($sock->GET_INFO("ActiveDirectoryEmergency"));
	
	
	
	if($ActiveDirectoryEmergency==0){
		$sock->getFrameWork("squid.php?ping-kdc=yes");
		$datas=unserialize(@file_get_contents("ressources/logs/kinit.array"));
		
		if(count($datas)==0){
			echo "
			<script>UnlockPage();LoadAjaxTiny('squid-adker-status','squid.adker.php?status=yes&t=squid-adker-status');</script>";
			return;
			
		}
		
		$img="img/error-24.png";
		$textcolor="#8A0D0D";
		$text=$datas["INFO"];
		if(preg_match("#Authenticated to#is", $text)){
			$img="img/ok24.png";$textcolor="black";
		}
		
		
		if(trim($text)<>null){$text=": $text";}
	
	}
	
	if($ActiveDirectoryEmergency==1){
		$img="img/warning-panneau-24.png";
		$textcolor="#8A0D0D";
		$text="{activedirectory_emergency_mode}";
	}
	
	
	$html="
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tbody>
	
	<tr>
		<td width=1% valign='top'><img src='$img'></td>
		<td nowrap style='font-size:18px' valign='top'><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.adker.php',true);\" style='color:$textcolor;font-weight:bold;text-decoration:underline'>Active Directory $text</strong></td>
		<td width=1%>".imgtootltip("refresh-24.png","{refresh}","LoadAjaxTiny('squid-adker-status','squid.adker.php?status=yes&t=squid-adker-status');")."</td>
	</tr>
	</tbody>
	</table>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
}



function test_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric("$EnableKerbAuth")){$EnableKerbAuth=0;}	
	$viaSmamba=null;
	$t=time();
	if(!isset($_GET["via-samba"])){
		if($EnableKerbAuth==0){
			echo $tpl->_ENGINE_parse_body("<p class=text-error>{EnableWindowsAuthentication}: {disabled}</p>");
			return;
		}
		$reconnectJS="SambeReconnectAD();";
		
		
	}else{
		$viaSmamba="&via-samba=yes";
		$reconnectJS="SambbReconnectAD();";
	}
	
	$html="
	<div id='animate-$t'></div>
	<div id='main-$t'>
	<table style='width:99%' class=form>
	<tr>
		<td valign='top' style='font-size:26px' nowrap class=legend>{is_connected}?:</td>
		<td width=99%><div id='$t-nettestjoin' style='margin-top:20px'></div></td>
	</tr>
	<tr>
		<td valign='top' style='font-size:26px;pading-top:15px' nowrap class=legend>Active Directory Infos:</td>
		<td width=99%><div id='$t-netadsinfo' style='margin-top:20px'></div></td>
	</tr>
	<tr>
		<td valign='top' style='font-size:26px;pading-top:15px' nowrap class=legend>RPC Infos:</td>
		<td width=99%><div id='$t-netrpcinfo' style='margin-top:20px'></div></td>
	</tr>
	<tr>
		<td valign='top' style='font-size:26px;pading-top:15px' nowrap class=legend>Domains:</td>
		<td width=99%><div id='$t-wbinfoalldom' style='margin-top:20px'></div></td>
	</tr>	
	<tr>
		<td valign='top' style='font-size:26px;pading-top:15px' nowrap class=legend>Check shared secret:</td>
		<td width=99%><div id='$t-wbinfomoinst' style='margin-top:20px'></div></td>
	</tr>	
	<tr>
		<td valign='top' style='font-size:26px;pading-top:15px' nowrap class=legend>NTLM Auth:</td>
		<td width=99%><div id='$t-wbinfomoinsa' style='margin-top:20px'></div></td>
	</tr>		
	<tr>
		<td colspan=2 align='right' style='padding-top:50px;text-align:right'>". imgtootltip("64-refresh.png","{refresh}","StartAgain()")."</td>
	</tr>		
	</tbody>
	</table>
	<center style='margin-top:20px'>". button("{restart_connection}","$reconnectJS",32)."</center>
	</div>
	<script>
		function StartAgain(){
			LoadAjaxTiny('$t-nettestjoin','$page?test-nettestjoin=yes&time=$t$viaSmamba');
		}
		
	var x_SambeReconnectAD= function (obj) {
		RefreshTab('main_adker_tabs');
	}		
	
		function SambeReconnectAD(){
			var XHR = new XHRConnection();
			XHR.appendData('SambeReconnectAD','yes');
			AnimateDiv('main-$t');
			XHR.sendAndLoad('$page', 'POST',x_SambeReconnectAD);
		
		}
		
	var x_SambbReconnectAD= function (obj) {
		document.getElementById('animate-$t').innerHTML='';
		StartAgain();
	}			
		
		function SambbReconnectAD(){
			Loadjs('squid.ad.progress.php');		
		}
		
StartAgain();
		
		
	</script>
		
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function test_netadsinfo(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	if(isset($_GET["via-samba"])){$viaSmamba="&via-samba=yes";}
	$datas=unserialize(base64_decode($sock->getFrameWork("samba.php?netadsinfo=yes")));
	$html="<hr><div style='font-size:18px'>";
	$html=$html.test_results($datas);
	$html=$html."</div>
	<script>
			LoadAjaxTiny('{$_GET["time"]}-netrpcinfo','$page?test-netrpcinfo=yes&time={$_GET["time"]}$viaSmamba');
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function test_testjoin(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	if(isset($_GET["via-samba"])){$viaSmamba="&via-samba=yes";}
	$datas=unserialize(base64_decode($sock->getFrameWork("samba.php?netrpctestjoin=yes")));
	$html="<hr><div style='font-size:18px'>";
	$html=$html.test_results($datas);
	$html=$html."</div>
	<script>
	LoadAjaxTiny('{$_GET["time"]}-netadsinfo','$page?test-netadsinfo=yes&time={$_GET["time"]}$viaSmamba');
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function test_netrpcinfo(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	if(isset($_GET["via-samba"])){$viaSmamba="&via-samba=yes";}
	$AR["USER"]=$array["WINDOWS_SERVER_ADMIN"];
	$AR["PASSWD"]=$array["WINDOWS_SERVER_PASS"];
	
	
	if(isset($_GET["via-samba"])){
		$viaSmamba="&via-samba=yes";
		$array=unserialize(base64_decode($sock->GET_INFO("SambaAdInfos")));
		$AR["USER"]=$array["ADADMIN"];
		$AR["PASSWD"]=$array["PASSWORD"];
	}

	$cmdline=base64_encode(serialize($AR));
		$html="<hr><div style='font-size:18px'>";
	$datas=unserialize(base64_decode($sock->getFrameWork("samba.php?netrpcinfo=yes&auth=$cmdline")));
	$html=$html.test_results($datas);
	
	$html=$html."</div>
	<script>
			LoadAjaxTiny('{$_GET["time"]}-wbinfoalldom','$page?test-wbinfoalldom=yes&time={$_GET["time"]}$viaSmamba');
	</script>";	
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
function test_wbinfoalldom(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	if(isset($_GET["via-samba"])){$viaSmamba="&via-samba=yes";}
	$AR["USER"]=$array["WINDOWS_SERVER_ADMIN"];
	$AR["PASSWD"]=$array["WINDOWS_SERVER_PASS"];
	
	if(isset($_GET["via-samba"])){
		$viaSmamba="&via-samba=yes";
		$array=unserialize(base64_decode($sock->GET_INFO("SambaAdInfos")));
		$AR["USER"]=$array["ADADMIN"];
		$AR["PASSWD"]=$array["PASSWORD"];
	}	
	
	
	$cmdline=base64_encode(serialize($AR));
		$html="<hr><div style='font-size:18px'>";
	$datas=unserialize(base64_decode($sock->getFrameWork("samba.php?wbinfoalldom=yes&auth=$cmdline")));
	$html=$html.test_results($datas);	
	$html=$html."</div>
	<script>
			LoadAjaxTiny('{$_GET["time"]}-wbinfomoinst','$page?test-wbinfomoinst=yes&time={$_GET["time"]}$viaSmamba');
	</script>";		
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function test_wbinfomoinst(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	if(isset($_GET["via-samba"])){$viaSmamba="&via-samba=yes";}
	
	$AR["USER"]=$array["WINDOWS_SERVER_ADMIN"];
	$AR["PASSWD"]=$array["WINDOWS_SERVER_PASS"];
	
	if(isset($_GET["via-samba"])){
		$viaSmamba="&via-samba=yes";
		$array=unserialize(base64_decode($sock->GET_INFO("SambaAdInfos")));
		$AR["USER"]=$array["ADADMIN"];
		$AR["PASSWD"]=$array["PASSWORD"];
	}	
	
	$cmdline=base64_encode(serialize($AR));
		$html="<hr><div style='font-size:18px'>";
	$datas=unserialize(base64_decode($sock->getFrameWork("samba.php?wbinfomoinst=yes&auth=$cmdline")));
	$html=$html.test_results($datas);
	$html=$html."</div>
	<script>
			LoadAjaxTiny('{$_GET["time"]}-wbinfomoinsa','$page?test-wbinfomoinsa=yes&time={$_GET["time"]}$viaSmamba');
	</script>";		
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function test_wbinfomoinsa(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	if(isset($_GET["via-samba"])){$viaSmamba="&via-samba=yes";}
	
	$AR["USER"]=$array["WINDOWS_SERVER_ADMIN"];
	$AR["PASSWD"]=$array["WINDOWS_SERVER_PASS"];
	
	if(isset($_GET["via-samba"])){
		$viaSmamba="&via-samba=yes";
		$array=unserialize(base64_decode($sock->GET_INFO("SambaAdInfos")));
		$AR["USER"]=$array["ADADMIN"];
		$AR["PASSWD"]=$array["PASSWORD"];
		$SambaWinbindUseDefaultDomain=$sock->GET_INFO("SambaWinbindUseDefaultDomain");
		if(!is_numeric($SambaWinbindUseDefaultDomain)){$SambaWinbindUseDefaultDomain=0;}
		if($SambaWinbindUseDefaultDomain==0){$AR["WORKGROUP"]=$array["WORKGROUP"];}
	}	
	
	$cmdline=base64_encode(serialize($AR));
		$html="<hr><div style='font-size:18px'>";
	$datas=unserialize(base64_decode($sock->getFrameWork("samba.php?wbinfomoinsa=yes&auth=$cmdline$viaSmamba")));
	$html=$html.test_results($datas)."</div>
	<script>
		LoadAjaxTiny('squid-adker-status','squid.adker.php?status=yes&t=squid-adker-status');
	</script>
			
			";

	echo $tpl->_ENGINE_parse_body($html);	
	
}


function test_results($array){
	$tpl=new templates();
	$html=null;
	while (list ($num, $ligne) = each ($array) ){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		$color="black";
		
		if(preg_match("#No logon#", $ligne)){$color="#D30F0F;font-weight:bold";
			$ligne=$ligne.$tpl->_ENGINE_parse_body("<br> {should_change_ad_dns}");
		}
		if(preg_match("#No trusted SAM#i", $ligne)){$color="#D30F0F;font-weight:bold";}
		if(preg_match("#is not valid#i", $ligne)){$color="#D30F0F;font-weight:bold";}
		if(preg_match("#Improperly#i", $ligne)){$color="#D30F0F;font-weight:bold";}
		if(preg_match("#(UNSUCCESSFUL|FAILURE|NO_TRUST)#i", $ligne)){$color="#D30F0F;font-weight:bold";}
		if(preg_match("#(invalid credential|not correct)#i", $ligne)){$color="#D30F0F;font-weight:bold";}
		if(preg_match("#is OK#", $ligne)){$color="#009809;font-weight:bold";}
		if(preg_match("#online#", $ligne)){$color="#009809";}
		if(preg_match("#Could not authenticate user\s+.+?\%(.+?)\s+with plaintext#i",$ligne,$re)){$ligne=str_replace($re[1], "*****", $ligne);}
		if(preg_match("#Could not#i", $ligne)){$color="#D30F0F";}
		if(preg_match("#failed#i", $ligne)){$color="#D30F0F";}
		if(preg_match("#_CANT_#i", $ligne)){$color="#D30F0F;font-weight:bold";}
		if(preg_match("#succeeded#i", $ligne)){$color="#009809;font-weight:bold";}
		if($color=="black"){
			if(preg_match("#^(.+?):\s+(.+)#", $ligne,$re)){$ligne="<span style='color:#656060;font-weight:bold;font-size:18px'>{$re[1]}:&nbsp;</span><span style='color:#009809;font-weight:bold'>{$re[2]}</span>";}
		}
		$html=$html."<div style='font-size:18px;color:$color'>$ligne</div>";
	}	
	return $html;
}


function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$DisableWinbindd=$sock->GET_INFO("DisableWinbindd");
	if(!is_numeric($DisableWinbindd)){$DisableWinbindd=0;}
	
	if($DisableWinbindd==1){
		echo "alert('".$tpl->javascript_parse_text("{DisableWinbindd_error}")."')";
		return;
	}
	
	
	$title=$tpl->_ENGINE_parse_body("{APP_SQUIDKERAUTH}");
	
	echo "AnimateDiv('BodyContent');LoadAjax('BodyContent','$page?tabs=yes');";
	return;
	
	$html="YahooWin4(650,'$page?tabs=yes','$title');";
	echo $html;
	}
	
function test_popup_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{analyze}");
	if(isset($_GET["via-samba"])){$e="&via-samba=yes";}
	$html="YahooWin6(600,'$page?test-popup=yes$e','$title');";
	echo $html;	
	
	
}
	
function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$sock=new sockets();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	$ActiveDirectoryEmergency=intval($sock->GET_INFO("ActiveDirectoryEmergency"));
	if($EnableKerbAuth==1){
		if($ActiveDirectoryEmergency==0){
			$array["active_directory_users"]="{active_directory_users}";
		}
	}
	
	
	if($users->AsSystemAdministrator){
		$array["popup"]='{activedirectory_connection}';
		$array["ldap-params"]='{ldap_paremeters}';
	}
	
	if($users->AsSquidAdministrator){
		$array["watchdog"]='{watchdog}';
	}
	
	$array["test-popup"]='{analyze}';
	$array["test-auth"]='{test_auth}';

	if($users->AsSquidAdministrator){
		$array["cntlm"]='{APP_CNTLM}';
	}
	
	
	
	$fontsize=20;

	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		if($num=="active_directory_users"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"browse-ad-groups.php?popup=yes\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
		}	
		
		if($num=="watchdog"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.adker.watchdog.php\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
			
		}
		
	
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
	}
	
	
	
	echo build_artica_tabs($html, "main_adker_tabs",1490);
	
}
	
function popup(){
$page=CurrentPageName();
$users=new usersMenus();
$sock=new sockets();

$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}	


$tpl=new templates();
	if(!$users->MSKTUTIL_INSTALLED){
		echo $tpl->_ENGINE_parse_body("
		<table style='width:99%' class=form>
		<tr>
			<td valign='top' width=1%><img src='img/error-64.png'></td>
			<td valign='top'>
				<div style='font-size:16px'>{error_missing_mskutil}<br>
				<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('setup.index.progress.php?product=APP_MSKTUTIL&start-install=yes')\" style='font-size:16px;text-decoration:underline'>{install}</a></div>
			</td>
		</tr>
		</table>
		");return;
	}
	if($EnableWebProxyStatsAppliance==0){
		if(strlen($users->squid_kerb_auth_path)<2){
			echo $tpl->_ENGINE_parse_body("
		<table style='width:99%' class=form>
		<tr>
			<td valign='top' width=1%><img src='img/error-64.png'></td>
			<td valign='top'><div style='font-size:16px'>{error_missing_kerbauth}</div></td>
		</tr>
		</table>
		");return;
		}   
	}

	$html="
	<div id='serverkerb-animated'></div>
	<div id='serverkerb-popup'></div>
	
	<script>
	function RefreshServerKerb(){
		LoadAjax('serverkerb-popup','$page?settings=yes');
	}
	RefreshServerKerb();
	</script>
	";
		
echo $html;		
}	

function intro(){
	
	$tpl=new templates();
	$intro="{APP_SQUIDKERAUTH_TEXT}<br>{APP_SQUIDKERAUTH_TEXT_REF}";
	if($_GET["switch-template"]=="samba"){$intro="{APP_SAMBAKERAUTH_TEXT}<br>{APP_SAMBAKERAUTH_TEXT_REF}";}	
	echo $tpl->_ENGINE_parse_body("<div class=explain style='font-size:18px'>$intro</div>");
}


function settings(){
	$page=CurrentPageName();
	$t=time();
	$html="
	<table style='width:100%'>
	<tr>
		<td valign='top' valign='top' style='width:380px'>	
			<span id='kerbchkconf'></span>		
			<div style='text-align:right'>". imgtootltip("refresh-24.png","{refresh}","LoadAjax('kerbchkconf','$page?kerbchkconf=yes');")."</div>
	</td>
	<td >	
		<div id='activedirectory-settings'><script>LoadAjaxRound('$page?settings-ad=yes')</script></div>	
	
	</td>
	</tr>
	</table>
<script>	
function startx$t(){
	LoadAjaxRound('activedirectory-settings','$page?settings-ad=yes');
}

LoadAjaxRound('kerbchkconf','$page?kerbchkconf=yes');
startx$t();

</script>

";	
	
echo $html;	
	
}


	
function settings_ad(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();	
	$sock=new sockets();
	$t=time();
	
	
	$hostname=$sock->getFrameWork("cmd.php?full-hostname=yes");
	if(strpos($hostname, ".")>0){
		$tre=explode(".",$hostname);
		$hostname=$tre[0];
	}
	
	if(strlen($hostname)>15){
		$hostname_exceed_15=$tpl->_ENGINE_parse_body("{hostname_exceed_15}");
		$hostname_exceed_15=str_replace("%s", "$hostname", $hostname_exceed_15);
		echo FATAL_ERROR_SHOW_128("$hostname_exceed_15
				<center style='margin:20px'>". button("{change_hostname}","Loadjs('system.nic.config.php?change-hostname-js=yes');",32)."</center>
				
				");
		return;
	}
	
	$severtype["WIN_2003"]="Windows 2000/2003";
	$severtype["WIN_2008AES"]="Windows 2008/2012";
	if(isset($_GET["switch-template"])){$_GET["switch-template"]=null;}
	$samba_version=$sock->getFrameWork("samba.php?fullversion=yes");
	$ldap_parameters=$tpl->_ENGINE_parse_body("{ldap_parameters2}");
	$about_this_section=$tpl->_ENGINE_parse_body("{about_this_section}");
	$schedule_parameters=$tpl->javascript_parse_text("{schedule_parameters}");
	$disconnect=$tpl->_ENGINE_parse_body("{disconnect}");
	$samba36=0;
	if(preg_match("#^3\.6\.#", $samba_version)){$samba36=1;}
	

	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	$configADSamba=unserialize(base64_decode($sock->GET_INFO("SambaAdInfos")));
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	$ActiveDirectoryEmergency=intval($sock->GET_INFO("ActiveDirectoryEmergency"));
	$LockKerberosAuthentication=$sock->GET_INFO("LockKerberosAuthentication");
	
	$KerbAuthDisableGroupListing=$sock->GET_INFO("KerbAuthDisableGroupListing");
	$KerbAuthDisableNormalizeName=$sock->GET_INFO("KerbAuthDisableNormalizeName");
	$KerbAuthMapUntrustedDomain=$sock->GET_INFO("KerbAuthMapUntrustedDomain");
	$SquidNTLMKeepAlive=$sock->GET_INFO("SquidNTLMKeepAlive");
	$UseADAsNameServer=$sock->GET_INFO("UseADAsNameServer");
	$WindowsActiveDirectoryKerberos=intval($sock->GET_INFO("WindowsActiveDirectoryKerberos"));
	$ActiveDirectorySquidHTTPHostname=$sock->GET_INFO("ActiveDirectorySquidHTTPHostname");
	
	$KerbAuthMethod=$sock->GET_INFO("KerbAuthMethod");
	$NtpdateAD=intval($sock->GET_INFO("NtpdateAD"));
	
	$arrayAuth[0]="{all_methods}";
	$arrayAuth[1]="{only_ntlm}";
	$arrayAuth[2]="{only_basic_authentication}";
	$arrayAuth[3]="{only_Kerberos}";
	
	
	$NTPDATE_INSTALLED=0;
	if($users->NTPDATE){$NTPDATE_INSTALLED=1;}
	$KerbAuthTrusted=$sock->GET_INFO("KerbAuthTrusted");
	
	
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	$DisableSpecialCharacters=$sock->GET_INFO("DisableSpecialCharacters");
	if(!is_numeric($DisableSpecialCharacters)){$DisableSpecialCharacters=0;}
	
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}		
	
	if(!is_numeric($KerbAuthMethod)){$KerbAuthMethod=0;}
	if(!is_numeric($KerbAuthTrusted)){$KerbAuthTrusted=1;}
	
	if(!is_numeric($KerbAuthDisableGroupListing)){$KerbAuthDisableGroupListing=0;}
	if(!is_numeric($KerbAuthDisableNormalizeName)){$KerbAuthDisableNormalizeName=1;}
	if(!is_numeric($KerbAuthMapUntrustedDomain)){$KerbAuthMapUntrustedDomain=1;}
	if(!is_numeric($SquidNTLMKeepAlive)){$SquidNTLMKeepAlive=1;}
	if(!is_numeric($UseADAsNameServer)){$UseADAsNameServer=0;}
	
	$SambaBindInterface=$sock->GET_INFO("SambaBindInterface");

	
	$net=new networking();
	$nics=$net->Local_interfaces();
	while (list ($interface, $val) = each ($nics) ){
		$ni=new system_nic($interface);
		if($ni->NICNAME<>null){
			$nics[$interface]="[$interface] $ni->NICNAME - $ni->netzone";
		}
	}
	$nics[null]="{all}";
	reset($nics);
	//interfaces = eth0 lo
	//bind interfaces only = yes
	
	
	
	
	
	
	if(!is_numeric("$EnableKerbAuth")){$EnableKerbAuth=0;}
	if(!is_numeric("$LockKerberosAuthentication")){$LockKerberosAuthentication=1;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	$samba_installed=1;
	if(!$users->SAMBA_INSTALLED){$samba_installed=0;}
	
	if(!isset($array["SAMBA_BACKEND"])){$array["SAMBA_BACKEND"]="tdb";}
	if(!isset($array["COMPUTER_BRANCH"])){$array["COMPUTER_BRANCH"]="CN=Computers";}
	if($array["COMPUTER_BRANCH"]==null){$array["COMPUTER_BRANCH"]="CN=Computers";}
	if($samba36==1){$arrayBCK["autorid"]="autorid";}
	$arrayBCK["ad"]="ad";
	$arrayBCK["rid"]="rid";
	$arrayBCK["tdb"]="tdb";
	
	$char_alert_error=$tpl->javascript_parse_text("{char_alert_error}");
	$no_sense_kerb_KerbAuthMethod=$tpl->javascript_parse_text("{no_sense_kerb_KerbAuthMethod}");
	

	

	
	if($samba_installed==0){
		
		echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{samba_is_not_installed}"));
		return;
	}
	
	$Myhostname=strtolower($sock->getFrameWork("cmd.php?full-hostname=yes"));	
	$error_dom1=$tpl->javascript_parse_text("{error}: {WINDOWS_DNS_SUFFIX}");
	$error_dom2=$tpl->javascript_parse_text("{is_not_a_part_of}");
	$error_dom3=$tpl->javascript_parse_text("{ask_change_hostname}");
	$do_want_to_perform_connection_to_ad=$tpl->javascript_parse_text("{do_want_to_perform_connection_to_ad}");
	$t_tmp=time();
	
	
	$evaluation_period_days=evaluation_period_days();
	
	if($EnableKerbAuth==0){
		
		$button_wizard="<div style='margin-top:20px;margin-bottom:20px;text-align:right'>
				". button("{quick_connect}","Loadjs('squid.adker.wizard.php')",26)."</div>";
				
				
		
	}
	$jshostname="Loadjs('system.nic.config.php?change-hostname-js=yes&newinterface=yes');";
	$sock=new sockets();
	
	$hostname=$sock->GET_INFO("myhostname");
	if($hostname==null){$hostname=$sock->getFrameWork("system.php?hostname-g=yes");}
	
	$Thostname=explode(".",$hostname);
	$TKHOST=$Thostname[0]."-k";
	$TKHOST2=$Thostname[0];
	
	if($ActiveDirectorySquidHTTPHostname==null){
		$ActiveDirectorySquidHTTPHostname=$hostname;
	}else{
		$Thostname=explode(".",$ActiveDirectorySquidHTTPHostname);
		$TKHOST=$Thostname[0]."-k";
		$TKHOST2=$Thostname[0];
	}
	
	

	
	
	$enabled_big_button=Paragraphe_switch_img("{EnableWindowsAuthentication}", 
			"{EnableWindowsAuthentication_text}","EnableKerbAuth",$EnableKerbAuth,null,850,"EnableKerbAuthCheck()");
	
	if($EnableKerbAuth==1){
		$mergencyjs="Loadjs('squid.ad.emergency.progress.php')";
		if($ActiveDirectoryEmergency==1){
			
			$mergencyjs="Loadjs('squid.urgency.php?activedirectory=yes')";
		}
		
		$enabled_big_button="
		<input type='hidden' name=\"EnableKerbAuth\" id=\"EnableKerbAuth\" value='$EnableKerbAuth'>
		<div style='margin-bottom:15px'>
		<table style='width:100%'>
		<tr>
			<td style='width:50%'><center>
				". button("{disconnect}","Loadjs('squid.ad.disconnect.progress.php')",28)."</center></td>
			<td style='font-size:28px'><center>&nbsp;|&nbsp;</center></td>
			<td><center>". button("{emergency2}",$mergencyjs,28)."</center></td>
		</tr>
		</table>
		</div>
		";
	}
	
	
	$html="

	
	
	<div style='width:98%' class=form>
	$button_wizard
	$enabled_big_button
	
	<div style='width:97%;margin:5px;padding:10px;border:1px solid #CCCCCC;border-radius: 5px 5px 5px 5px;'>
	<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:22px' colspan=3>{hostname}:".texttooltip($hostname,"{edit}",$jshostname)."</td>
		</tr>				
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{EnableRecursiveGroups}","{EnableRecursiveGroups_text}").":</td>
		<td>". Field_checkbox_design("RECURSIVE_GROUPS",1,$array["RECURSIVE_GROUPS"],"SaveRecursiveGroups()")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{authenticate_from_kerberos}:</td>
		<td>". Field_checkbox_design("WindowsActiveDirectoryKerberos",1,"$WindowsActiveDirectoryKerberos")."</td>
		<td>".button("{cached_Kerberos_tickets}","Loadjs('squid.adker.kerberos-tickets.php')",16)."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:22px' nowrap>{authentication_method}:</td>
		<td>". Field_array_Hash($arrayAuth, "KerbAuthMethod",$KerbAuthMethod,"HideSambaDiv()",null,0,"font-size:22px")."</td>
		<td>&nbsp;</td>
	</tr>
	</table>
	</div>
	<div id='SAMBA_DIV1' style='width:97%;margin:5px;padding:10px;border:1px solid #CCCCCC;border-radius: 5px 5px 5px 5px;'>
	<table style='width:100%'>	
	<tr>
		<td class=legend style='font-size:22px'>{KerbAuthTrusted}:</td>
		<td>". Field_checkbox_design("KerbAuthTrusted",1,"$KerbAuthTrusted")."</td>
		<td>&nbsp;</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:22px' nowrap>{KerbAuthDisableGroupListing}:</td>
		<td>". Field_checkbox_design("KerbAuthDisableGroupListing",1,"$KerbAuthDisableGroupListing")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{KerbAuthDisableNormalizeName}:</td>
		<td>". Field_checkbox_design("KerbAuthDisableNormalizeName",1,"$KerbAuthDisableNormalizeName")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px' nowrap>{map_untrusted_to_domain}:</td>
		<td>". Field_checkbox_design("KerbAuthMapUntrustedDomain",1,"$KerbAuthMapUntrustedDomain")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' nowrap>{interface}:</td>
		<td>". Field_array_Hash($nics,"SambaBindInterface",$SambaBindInterface,"style:font-size:22px;padding:3px")."</td>
		<td>". imgtootltip("disk-save-24.png","{save}","SaveSambaBindInterface()")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{database_backend}:</td>
		<td>". Field_array_Hash($arrayBCK,"SAMBA_BACKEND",$array["SAMBA_BACKEND"],"style:font-size:22px;padding:3px")."</td>
		<td>&nbsp;</td>
	</tr>
						
	</table>
	</div>	
	<div style='width:97%;margin:5px;padding:10px;border:1px solid #CCCCCC;border-radius: 5px 5px 5px 5px;'>	
	<table style='width:100%'>				
	<tr>
		<td class=legend style='font-size:22px' nowrap>". texttooltip("{keep_alive}","{SquidNTLMKeepAlive_explain}").":</td>
		<td>". Field_checkbox_design("SquidNTLMKeepAlive",1,"$SquidNTLMKeepAlive")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' nowrap>{synchronize_time_with_ad}:</td>
		<td>". Field_checkbox_design("NtpdateAD",1,"$NtpdateAD")."</td>
		<td>&nbsp;</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:22px' nowrap>{UseADAsNameServer}:</td>
		<td>". Field_checkbox_design("UseADAsNameServer",1,"$UseADAsNameServer")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr style='height:50px'>
		<td class=legend style='font-size:22px;' nowrap>{proxy_hostname}:</td>
		<td style='font-size:22px' colspan=2><span id='proxyHostnameKerbDiv'>". texttooltip("HTTP/$ActiveDirectorySquidHTTPHostname","{kerberos_proxy_hostname_explain}","Loadjs('$page?kerberos-hostname-js=yes')")."</span></td>
	</tr>
	
	
	<tr>
		<td class=legend style='font-size:22px'>{WINDOWS_DNS_SUFFIX}:</td>
		<td>". Field_text("WINDOWS_DNS_SUFFIX",$array["WINDOWS_DNS_SUFFIX"],"font-size:22px;padding:3px;width:390px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{WINDOWS_SERVER_NETBIOSNAME}:</td>
		<td>". Field_text("WINDOWS_SERVER_NETBIOSNAME",$array["WINDOWS_SERVER_NETBIOSNAME"],"font-size:22px;padding:3px;width:390px")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{ADNETBIOSDOMAIN}","{howto_ADNETBIOSDOMAIN}").":</td>
		<td>". Field_text("ADNETBIOSDOMAIN",$array["ADNETBIOSDOMAIN"],"font-size:22px;padding:3px;width:390px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{ADNETIPADDR}","{howto_ADNETIPADDR}").":</td>
		<td>". field_ipv4("ADNETIPADDR",$array["ADNETIPADDR"],"font-size:22px")."</td>
		<td>". button("{controllers}", 
			"Loadjs('squid.adker.controllers.php')",22)."</td>
	</tr>	
	
	<tr>
		<td class=legend style='font-size:22px'>{WINDOWS_SERVER_TYPE}:</td>
		<td>". Field_array_Hash($severtype,"WINDOWS_SERVER_TYPE",$array["WINDOWS_SERVER_TYPE"],"style:font-size:22px;padding:3px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{COMPUTERS_BRANCH}:</td>
		<td>". Field_text("COMPUTER_BRANCH",$array["COMPUTER_BRANCH"],"font-size:22px;padding:3px;width:390px")."</td>
		<td>&nbsp;</td>
	</tr>	

	
	<tr>
		<td class=legend style='font-size:22px'>{administrator}:</td>
		<td>". Field_text("WINDOWS_SERVER_ADMIN",$array["WINDOWS_SERVER_ADMIN"],"font-size:22px;padding:3px;width:390px")."</td>
		<td>&nbsp;</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:22px'>{password}:</td>
		<td>". Field_password("WINDOWS_SERVER_PASS",$array["WINDOWS_SERVER_PASS"],"font-size:22px;padding:3px;width:390px")."</td>
		<td>&nbsp;</td>
	</tr>
	<td colspan=3 align='right' style='font-size:40px'><p>&nbsp;</p>
				
				". button("{apply}","SaveKERBProxy(0)",40)."&nbsp;|&nbsp;". button("{save_and_connect}","SaveKERBProxy(1)",40)."</td>
	</tr>
	</table>
	</div>
	</div>
<script>
var ButtonXtype$t;
function HideSambaDiv(){
	var KerbAuthMethod=document.getElementById('KerbAuthMethod').value;
	if(KerbAuthMethod==3){
	    document.getElementById('SAMBA_DIV1').style.display = 'none';
	    return;
    }
    
    document.getElementById('SAMBA_DIV1').style.display = 'block';

}
	
	
	
function CheckHostname$t_tmp(){
	var domainz=trim(document.getElementById('WINDOWS_DNS_SUFFIX').value);
	thewhole='$Myhostname';
	var regexp = /([^.]+)\.(.*?)$/;
	var match = regexp.exec(thewhole);
	var domain = match[1];
	var ext = match[2];
	domainz=domainz.toLowerCase();
	domain=ext.toLowerCase();
	if(domain!==domainz){
		if(confirm('$error_dom1 '+domainz+' $error_dom2 ('+domain+')\\n$error_dom3')){
			Loadjs('system.nic.config.php?change-hostname-js=yes');
		}
		return false;
	}
	return true;
}
var xSaveRecursiveGroups= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	
}

function SaveRecursiveGroups(){
	var XHR = new XHRConnection();
	var RecursiveGroups=0;
	if(document.getElementById('RECURSIVE_GROUPS').checked){
		RecursiveGroups=1;
	}
	XHR.appendData('SAVE_RECURSIVE_GROUPS',RecursiveGroups);
	XHR.sendAndLoad('$page', 'POST',xSaveRecursiveGroups);
}
		
		
function EnableKerbAuthCheck(){
	var evalday=$evaluation_period_days;
	if(evalday<365){ if(evalday<1){ alert('Evaluation perdiod finish!');return;} }
	var EnableKerbAuth=0;
	EnableKerbAuth=document.getElementById('EnableKerbAuth').value;
			
			
	var NTPDATE_INSTALLED=$NTPDATE_INSTALLED;
	var samba_installed=$samba_installed;
			
			if(document.getElementById('WINDOWS_DNS_SUFFIX')){document.getElementById('WINDOWS_DNS_SUFFIX').disabled=true;}
			if(document.getElementById('WINDOWS_SERVER_NETBIOSNAME')){document.getElementById('WINDOWS_SERVER_NETBIOSNAME').disabled=true;}
			if(document.getElementById('WINDOWS_SERVER_TYPE')){document.getElementById('WINDOWS_SERVER_TYPE').disabled=true;}
			if(document.getElementById('WINDOWS_SERVER_ADMIN')){document.getElementById('WINDOWS_SERVER_ADMIN').disabled=true;}
			if(document.getElementById('WINDOWS_SERVER_PASS')){document.getElementById('WINDOWS_SERVER_PASS').disabled=true;}
			if(document.getElementById('ADNETBIOSDOMAIN')){document.getElementById('ADNETBIOSDOMAIN').disabled=true;}
			if(document.getElementById('ADNETIPADDR')){document.getElementById('ADNETIPADDR').disabled=true;}
			if(document.getElementById('SAMBA_BACKEND')){document.getElementById('SAMBA_BACKEND').disabled=true;}
			if(document.getElementById('COMPUTER_BRANCH')){document.getElementById('COMPUTER_BRANCH').disabled=true;}
			
			if(document.getElementById('KerbAuthDisableGroupListing')){document.getElementById('KerbAuthDisableGroupListing').disabled=true;}
			if(document.getElementById('KerbAuthDisableNormalizeName')){document.getElementById('KerbAuthDisableNormalizeName').disabled=true;}
			if(document.getElementById('KerbAuthMapUntrustedDomain')){document.getElementById('KerbAuthMapUntrustedDomain').disabled=true;}
			
			if(document.getElementById('NtpdateAD')){document.getElementById('NtpdateAD').disabled=true;}
			if(document.getElementById('KerbAuthMethod')){document.getElementById('KerbAuthMethod').disabled=true;}
			if(document.getElementById('SquidNTLMKeepAlive')){document.getElementById('SquidNTLMKeepAlive').disabled=true;}
			if(document.getElementById('UseADAsNameServer')){document.getElementById('UseADAsNameServer').disabled=true;}
			if(document.getElementById('SambaBindInterface')){document.getElementById('SambaBindInterface').disabled=true;}
			if(document.getElementById('SAMBA_BACKEND')){document.getElementById('SAMBA_BACKEND').disabled=true;}
			if(document.getElementById('RECURSIVE_GROUPS')){document.getElementById('RECURSIVE_GROUPS').disabled=true;}
			if(document.getElementById('WindowsActiveDirectoryKerberos')){document.getElementById('WindowsActiveDirectoryKerberos').disabled=true;}
			if(document.getElementById('KerbAuthTrusted')){document.getElementById('KerbAuthTrusted').disabled=true;}
			
			

			if(EnableKerbAuth==1){
				if(document.getElementById('WINDOWS_DNS_SUFFIX')){document.getElementById('WINDOWS_DNS_SUFFIX').disabled=false;}
				if(document.getElementById('WINDOWS_SERVER_NETBIOSNAME')){document.getElementById('WINDOWS_SERVER_NETBIOSNAME').disabled=false;}
				if(document.getElementById('WINDOWS_SERVER_TYPE')){document.getElementById('WINDOWS_SERVER_TYPE').disabled=false;}
				if(document.getElementById('WINDOWS_SERVER_ADMIN')){document.getElementById('WINDOWS_SERVER_ADMIN').disabled=false;}
				if(document.getElementById('WINDOWS_SERVER_PASS')){document.getElementById('WINDOWS_SERVER_PASS').disabled=false;}							
				if(document.getElementById('ADNETBIOSDOMAIN')){document.getElementById('ADNETBIOSDOMAIN').disabled=false;}
				if(document.getElementById('ADNETIPADDR')){document.getElementById('ADNETIPADDR').disabled=false;}
				if(document.getElementById('SAMBA_BACKEND')){document.getElementById('SAMBA_BACKEND').disabled=false;}
				if(document.getElementById('COMPUTER_BRANCH')){document.getElementById('COMPUTER_BRANCH').disabled=false;}
				
				if(document.getElementById('KerbAuthDisableGroupListing')){document.getElementById('KerbAuthDisableGroupListing').disabled=false;}
				if(document.getElementById('KerbAuthDisableNormalizeName')){document.getElementById('KerbAuthDisableNormalizeName').disabled=false;}
				if(document.getElementById('KerbAuthMapUntrustedDomain')){document.getElementById('KerbAuthMapUntrustedDomain').disabled=false;}
				if(document.getElementById('KerbAuthTrusted')){document.getElementById('KerbAuthTrusted').disabled=false;}
				if(document.getElementById('KerbAuthMethod')){document.getElementById('KerbAuthMethod').disabled=false;}
				if(document.getElementById('SquidNTLMKeepAlive')){document.getElementById('SquidNTLMKeepAlive').disabled=false;}
				if(document.getElementById('UseADAsNameServer')){document.getElementById('UseADAsNameServer').disabled=false;}
				if(document.getElementById('SambaBindInterface')){document.getElementById('SambaBindInterface').disabled=false;}
				if(document.getElementById('SAMBA_BACKEND')){document.getElementById('SAMBA_BACKEND').disabled=false;}
				if(document.getElementById('RECURSIVE_GROUPS')){document.getElementById('RECURSIVE_GROUPS').disabled=false;}
				if(document.getElementById('WindowsActiveDirectoryKerberos')){document.getElementById('WindowsActiveDirectoryKerberos').disabled=false;}
				if(NTPDATE_INSTALLED==1){ if(document.getElementById('KerbAuthTrusted')){document.getElementById('NtpdateAD').disabled=false;} }
					
		}
}
	
		
function RefreshAll(){
	RefreshServerKerb();
}

function replaceAll$t(string, find, replace) {
  return string.replace(new RegExp(find, 'g'), replace);
}
		
	var x_SaveKERBProxy= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		
		
		if(document.getElementById('AdSquidStatusLeft')){RefreshDansguardianMainService();}
		if(document.getElementById('squid-status')){LoadAjax('squid-status','squid.main.quicklinks.php?status=yes');}
		
		if(!document.getElementById('WINDOWS_SERVER_TYPE')){
			alert('WINDOWS_SERVER_TYPE???');
			return;
		}
		var WINDOWS_SERVER_TYPE=document.getElementById('WINDOWS_SERVER_TYPE').value;
		if(WINDOWS_SERVER_TYPE=='WIN_2003'){WINDOWS_SERVER_TYPE='Windows 2000/2003';}
		if(WINDOWS_SERVER_TYPE=='WIN_2008AES'){WINDOWS_SERVER_TYPE='Windows 2008/2012';}

		
		
		var xconfirm='$do_want_to_perform_connection_to_ad';
		xconfirm=replaceAll$t(xconfirm,'%s', WINDOWS_SERVER_TYPE);
		xconfirm=replaceAll$t(xconfirm,'%h', '$TKHOST/$TKHOST2');

		
		if(ButtonXtype$t==0){
			Loadjs('squid.compile.progress.php');
			return;
		}
		
		if(confirm(xconfirm)){
			RefreshServerKerb();
			Loadjs('squid.ad.progress.php');
		}
	}	

	var x_SaveSambaBindInterface= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		RefreshServerKerb();
		
		if(document.getElementById('AdSquidStatusLeft')){RefreshDansguardianMainService();}
		if(document.getElementById('squid-status')){LoadAjax('squid-status','squid.main.quicklinks.php?status=yes');}
		
	}	
	
	function SaveSambaBindInterface(){
		var XHR = new XHRConnection();
		XHR.appendData('SaveSambaBindInterface',document.getElementById('SambaBindInterface').value);
		XHR.sendAndLoad('$page', 'POST',x_SaveSambaBindInterface);
	}
	
	
		function SaveKERBProxy(xTYPE){
			ButtonXtype$t=xTYPE;
			if(!CheckHostname$t_tmp()){return;}
			var DisableSpecialCharacters=$DisableSpecialCharacters;
			var EnableRemoteStatisticsAppliance=$EnableRemoteStatisticsAppliance;
			if(EnableRemoteStatisticsAppliance==1){Loadjs('squid.newbee.php?error-remote-appliance=yes');return;}
			var pp=encodeURIComponent(document.getElementById('WINDOWS_SERVER_PASS').value);
			var KerbAuthMethod=document.getElementById('KerbAuthMethod').value;
			var WindowsActiveDirectoryKerberos=0;
			var XHR = new XHRConnection();
			
			if(document.getElementById('WindowsActiveDirectoryKerberos').checked){
				XHR.appendData('WindowsActiveDirectoryKerberos',1);
				WindowsActiveDirectoryKerberos=1;
			}else{
				XHR.appendData('WindowsActiveDirectoryKerberos',0);
			}
			
			if(KerbAuthMethod==3){
				if(WindowsActiveDirectoryKerberos==0){
					alert('$no_sense_kerb_KerbAuthMethod');
					return;
				}
			
			}
			
			if(document.getElementById('KerbAuthDisableGroupListing').checked){XHR.appendData('KerbAuthDisableGroupListing',1);}else{XHR.appendData('KerbAuthDisableGroupListing',0);}
			if(document.getElementById('KerbAuthDisableNormalizeName').checked){XHR.appendData('KerbAuthDisableNormalizeName',1);}else{XHR.appendData('KerbAuthDisableNormalizeName',0);}
			if(document.getElementById('KerbAuthTrusted').checked){XHR.appendData('KerbAuthTrusted',1);}else{XHR.appendData('KerbAuthTrusted',0);}
			if(document.getElementById('KerbAuthMapUntrustedDomain').checked){XHR.appendData('KerbAuthMapUntrustedDomain',1);}else{XHR.appendData('KerbAuthMapUntrustedDomain',0);}
			if(document.getElementById('NtpdateAD').checked){XHR.appendData('NtpdateAD',1);}else{XHR.appendData('NtpdateAD',0);}
			if(document.getElementById('SquidNTLMKeepAlive').checked){XHR.appendData('SquidNTLMKeepAlive',1);}else{XHR.appendData('SquidNTLMKeepAlive',0);}
			if(document.getElementById('UseADAsNameServer').checked){XHR.appendData('UseADAsNameServer',1);}else{XHR.appendData('UseADAsNameServer',0);}
			if(document.getElementById('RECURSIVE_GROUPS').checked){XHR.appendData('RECURSIVE_GROUPS',1); }else{ XHR.appendData('RECURSIVE_GROUPS',0); }
			
			
			XHR.appendData('EnableKerbAuth',document.getElementById('EnableKerbAuth').value);
			XHR.appendData('KerbAuthMethod',document.getElementById('KerbAuthMethod').value);
			
			
			XHR.appendData('SambaBindInterface',document.getElementById('SambaBindInterface').value);
			XHR.appendData('COMPUTER_BRANCH',document.getElementById('COMPUTER_BRANCH').value);
			XHR.appendData('SAMBA_BACKEND',document.getElementById('SAMBA_BACKEND').value);
			XHR.appendData('WINDOWS_DNS_SUFFIX',document.getElementById('WINDOWS_DNS_SUFFIX').value);
			XHR.appendData('WINDOWS_SERVER_NETBIOSNAME',document.getElementById('WINDOWS_SERVER_NETBIOSNAME').value);
			XHR.appendData('WINDOWS_SERVER_TYPE',document.getElementById('WINDOWS_SERVER_TYPE').value);
			XHR.appendData('WINDOWS_SERVER_ADMIN',document.getElementById('WINDOWS_SERVER_ADMIN').value);
			XHR.appendData('WINDOWS_SERVER_PASS',pp);
			XHR.appendData('ADNETBIOSDOMAIN',document.getElementById('ADNETBIOSDOMAIN').value);
			XHR.appendData('ADNETIPADDR',document.getElementById('ADNETIPADDR').value);
			XHR.sendAndLoad('$page', 'POST',x_SaveKERBProxy);
		
		}
		
		
		
		
		EnableKerbAuthCheck();
		HideSambaDiv();
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}	
function TestLDAPAD(){
	$sock=new sockets();
	$error=null;
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));

	if(!isset($array["LDAP_SERVER"])){
		if(isset($array["ADNETIPADDR"])){
			$array["LDAP_SERVER"]=$array["ADNETIPADDR"];
				
		}

		if(!isset($array["LDAP_SERVER"])){
			if(isset($array["WINDOWS_SERVER_NETBIOSNAME"])){
				$array["LDAP_SERVER"]=$array["WINDOWS_SERVER_NETBIOSNAME"].".".$array["WINDOWS_DNS_SUFFIX"];
			}
		}

	}

	if(!is_numeric($array["LDAP_PORT"])){$array["LDAP_PORT"]=389;}
	if($GLOBALS["VERBOSE"]){echo "{$array["LDAP_SERVER"]} Port: {$array["LDAP_PORT"]}<br>\n";}
	$ldap_connection=@ldap_connect($array["LDAP_SERVER"],$array["LDAP_PORT"]);
	$GotoAdConnection="GotoActiveDirectoryLDAPParams()";

	if(!$ldap_connection){
		$error="ldap://{$array["LDAP_SERVER"]}:{$array["LDAP_PORT"]}";
		if (@ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
			$error=$error."<br>$extended_error";
		}
		@ldap_close();
		echo FATAL_ERROR_SHOW_128("{error_ad_ldap}<br>ldap_connect<br>ldap://{$array["LDAP_SERVER"]}:{$array["LDAP_PORT"]}<br>$error<br>$extended_error");
		

	}

	if(preg_match("#^(.+?)\/(.+?)$#", $array["WINDOWS_SERVER_ADMIN"],$re)){$array["WINDOWS_SERVER_ADMIN"]=$re[1];}
	if(preg_match("#^(.+?)\\\\(.+?)$#", $array["WINDOWS_SERVER_ADMIN"],$re)){$array["WINDOWS_SERVER_ADMIN"]=$re[1];}


	if(!ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3)){
		$error=ldap_err2str(ldap_errno($ldap_connection));
		if (@ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
			echo FATAL_ERROR_SHOW_128("{error_ad_ldap}<br>ldap_set_option<br>ldap://{$array["LDAP_SERVER"]}:{$array["LDAP_PORT"]}<br>$error<br>$extended_error");
			return;
		}

	}
	ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0);
	@ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0);
	@ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3); // on passe le LDAP en version 3, necessaire pour travailler avec le AD



	if($GLOBALS["VERBOSE"]){echo "Username: {$array["WINDOWS_SERVER_ADMIN"]}@{$array["WINDOWS_DNS_SUFFIX"]} / {$array["WINDOWS_SERVER_PASS"]}<br>\n";}

	$LDAP_DN=$array["LDAP_DN"];
	$LDAP_PASSWORD=$array["LDAP_PASSWORD"];
	if($LDAP_DN==null){
		$LDAP_DN="{$array["WINDOWS_SERVER_ADMIN"]}@{$array["WINDOWS_DNS_SUFFIX"]}";
		$LDAP_PASSWORD=$array["WINDOWS_SERVER_PASS"];
	}
	
	$bind=ldap_bind($ldap_connection, "$LDAP_DN", $array["WINDOWS_SERVER_PASS"]);


	if(!$bind){

		$error=ldap_err2str(ldap_errno($ldap_connection));
		if (@ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
			$error=$error."<br>$extended_error";
		}
		@ldap_close();
		echo FATAL_ERROR_SHOW_128("{error_ad_ldap}<br>ldap_bind<br>$LDAP_DN<br>ldap://{$array["LDAP_SERVER"]}:{$array["LDAP_PORT"]}<br>$error<br>$extended_error");
		return;
	}

}


function ldap_params(){
	$page=CurrentPageName();
	$tpl=new templates();
	$active=new ActiveDirectory();
	$sock=new sockets();
	$char_alert_error=$tpl->javascript_parse_text("{char_alert_error}");
	$UseDynamicGroupsAcls=intval($sock->GET_INFO("UseDynamicGroupsAcls"));
	$DynamicGroupsAclsTTL=intval($sock->GET_INFO("DynamicGroupsAclsTTL"));
	if($DynamicGroupsAclsTTL==0){$DynamicGroupsAclsTTL=3600;}
	if($DynamicGroupsAclsTTL<5){$DynamicGroupsAclsTTL=5;}	
	$DisableSpecialCharacters=$sock->GET_INFO("DisableSpecialCharacters");
	if(!is_numeric($DisableSpecialCharacters)){$DisableSpecialCharacters=0;}
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	$WinBindMaxClients=intval($sock->GET_INFO("WinBindMaxClients"));
	if($WinBindMaxClients==0){$WinBindMaxClients=500;}
	
	
	$t=time();
	if($array["LDAP_DN"]==null){$array["LDAP_DN"]=$active->ldap_dn_user;}
	if($array["LDAP_SUFFIX"]==null){$array["LDAP_SUFFIX"]=$active->suffix;}
	if($array["LDAP_SERVER"]==null){$array["LDAP_SERVER"]=$active->ldap_host;}
	if($array["LDAP_PORT"]==null){$array["LDAP_PORT"]=$active->ldap_port;}
	if($array["LDAP_PASSWORD"]==null){$array["LDAP_PASSWORD"]=$active->ldap_password;}
	if(!is_numeric($array["LDAP_RECURSIVE"])){$array["LDAP_RECURSIVE"]=0;}

	
	$warn_squid_restart=$tpl->javascript_parse_text("{warn_squid_restart}");
	
	
	
	$t=time();
	$maxMem=500;
	$CPUS=0;
	$currentMem=intval($sock->getFrameWork("cmd.php?GetTotalMemMB=yes"));
	
	if($currentMem>0){
		$maxMem=$currentMem-500;
	}
	
	$users=new usersMenus();
	$CPUS=$users->CPU_NUMBER;
	$CPUS_TEXT=" (X $CPUS)";
	TestLDAPAD();
	$html="
	<div style='font-size:42px;margin-bottom:20px'>{ldap_parameters2}</div>
	<table style='width:100%'>
	<tr>
		<td valign='top'>
		<div class=explain style='font-size:20px' nowrap>{ldap_ntlm_parameters_explain}</div>
		</td>
		<td valign='middle'>". button("{alternate_servers}","Loadjs('squid.adker.ldaps.php')",26)."</td>
	</tr>
	</table>
	<div id='serverkerb-$t' style='width:98%' class=form>
	
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px' nowrap>{use_dynamic_groups_acls}:</td>
		<td>". Field_checkbox_design("UseDynamicGroupsAcls-$t",1,$UseDynamicGroupsAcls,"UseDynamicGroupsAclsCheck()")."</td>
	</tr>
				
	<tr>
		<td class=legend style='font-size:22px' nowrap>{WinBindMaxClients}:</td>
		<td style='font-size:18px'>". Field_text("WinBindMaxClients-$t",$WinBindMaxClients,"font-size:22px;padding:3px;width:90px")."&nbsp;{processes}</td>
	</tr>				
				
				
	<tr>
		<td class=legend style='font-size:22px' nowrap>{TTL_CACHE}:</td>
		<td style='font-size:18px'>". Field_text("DynamicGroupsAclsTTL-$t",$DynamicGroupsAclsTTL,"font-size:22px;padding:3px;width:90px")."&nbsp;{seconds}</td>
	</tr>
				
	<tr>
		<td class=legend style='font-size:22px' nowrap>{non_ntlm_domain}:</td>
		<td>". Field_text("LDAP_NONTLM_DOMAIN-$t",$array["LDAP_NONTLM_DOMAIN"],"font-size:22px;padding:3px;width:190px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{hostname}:</td>
		<td>". Field_text("LDAP_SERVER-$t",$array["LDAP_SERVER"],"font-size:22px;padding:3px;width:190px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{ldap_port}:</td>
		<td>". Field_text("LDAP_PORT-$t",$array["LDAP_PORT"],"font-size:22px;padding:3px;width:90px")."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:22px'>{suffix}:</td>
		<td>". Field_text("LDAP_SUFFIX-$t",$array["LDAP_SUFFIX"],"font-size:22px;padding:3px;width:580px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{bind_dn}:</td>
		<td>". Field_text("LDAP_DN-$t",$array["LDAP_DN"],"font-size:22px;padding:3px;width:580px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{password}:</td>
		<td>". Field_password("LDAP_PASSWORD-$t",$array["LDAP_PASSWORD"],"font-size:22px;padding:3px;width:510px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{recursive}:</td>
		<td>". Field_checkbox_design("LDAP_RECURSIVE-$t",1,$array["LDAP_RECURSIVE"])."</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","SaveLDAPADker$t()",46)."</td>
	</tr>
	</table>
	</div>
	</table>
	</div>		
	
<script>
	var x_SaveLDAPADker$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		RefreshTab('main_adker_tabs');
		Loadjs('squid.compile.progress.php');
		
		
	}		
	
		function SaveLDAPADker$t(){
			var UseDynamicGroupsAcls=0;
			var DisableSpecialCharacters=$DisableSpecialCharacters;
			
			if(!document.getElementById('UseDynamicGroupsAcls-$t')){alert('UseDynamicGroupsAcls !!');}
			if(!document.getElementById('LDAP_PASSWORD-$t')){alert('LDAP_PASSWORD !!');}
			if(!document.getElementById('WinBindMaxClients-$t')){alert('WinBindMaxClients !!');}
			if(!document.getElementById('LDAP_NONTLM_DOMAIN-$t')){alert('LDAP_NONTLM_DOMAIN !!');}
			if(!document.getElementById('LDAP_SERVER-$t')){alert('LDAP_SERVER !!');}
			if(!document.getElementById('LDAP_PORT-$t')){alert('LDAP_PORT !!');}
			if(!document.getElementById('LDAP_SUFFIX-$t')){alert('LDAP_SUFFIX !!');}
			if(!document.getElementById('LDAP_RECURSIVE-$t')){alert('LDAP_RECURSIVE !!');}
			
			var pp=encodeURIComponent(document.getElementById('LDAP_PASSWORD-$t').value);
			var XHR = new XHRConnection();
			if(document.getElementById('UseDynamicGroupsAcls-$t').checked){UseDynamicGroupsAcls=1;}
			XHR.appendData('UseDynamicGroupsAcls',UseDynamicGroupsAcls);
			XHR.appendData('WinBindMaxClients',document.getElementById('WinBindMaxClients-$t').value);
			XHR.appendData('LDAP_NONTLM_DOMAIN',document.getElementById('LDAP_NONTLM_DOMAIN-$t').value);
			XHR.appendData('LDAP_SERVER',document.getElementById('LDAP_SERVER-$t').value);
			XHR.appendData('LDAP_PORT',document.getElementById('LDAP_PORT-$t').value);
			XHR.appendData('LDAP_SUFFIX',document.getElementById('LDAP_SUFFIX-$t').value);
			XHR.appendData('LDAP_DN',document.getElementById('LDAP_DN-$t').value);
			if(document.getElementById('LDAP_RECURSIVE-$t').checked){XHR.appendData('LDAP_RECURSIVE',1);}else{XHR.appendData('LDAP_RECURSIVE',0);}
			XHR.appendData('LDAP_PASSWORD',pp);
			XHR.sendAndLoad('$page', 'POST',x_SaveLDAPADker$t);
		
		}
		
		function UseDynamicGroupsAclsCheck(){

		}
		UseDynamicGroupsAclsCheck();
	</script>	
	";
	
	echo $tpl->_ENGINE_parse_body($html);

}

function ldap_params_save(){
	$sock=new sockets();
	$tpl=new templates();
	
	$_POST["LDAP_PASSWORD"]=url_decode_special_tool($_POST["LDAP_PASSWORD"]);
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	
	
	// -------------------------------------------------------------------------------------
	$SquidClientParams=unserialize(base64_decode($sock->GET_INFO("SquidClientParams")));
	while (list ($num, $ligne) = each ($_POST) ){
		$SquidClientParams[$num]=$ligne;
	
	}
	$sock->SaveConfigFile(base64_encode(serialize($SquidClientParams)), "SquidClientParams");
	
	// -------------------------------------------------------------------------------------	
	
	if($_POST["UseDynamicGroupsAcls"]==1){
		if($_POST["LDAP_SERVER"]==null){echo $tpl->javascript_parse_text("LDAP: {hostname} Not set\n");return;}
		if(!is_numeric($_POST["LDAP_PORT"])){echo $tpl->javascript_parse_text("LDAP: {ldap_port} Not set\n");return;}			
		if($_POST["LDAP_SUFFIX"]==null){echo $tpl->javascript_parse_text("LDAP: {suffix} Not set\n");return;}
		if($_POST["LDAP_DN"]==null){echo $tpl->javascript_parse_text("LDAP: {bind_dn} Not set\n");return;}
		if($_POST["LDAP_PASSWORD"]==null){echo $tpl->javascript_parse_text("LDAP: {password} Not set\n");return;}		
		
	}
	
	$sock->SET_INFO("UseDynamicGroupsAcls", $_POST["UseDynamicGroupsAcls"]);
	$sock->SET_INFO("WinBindMaxClients", $_POST["WinBindMaxClients"]);
	
	
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	
	reset($_POST);
	while (list ($num, $ligne) = each ($_POST) ){

		$array[$num]=$ligne;
	}
	
	
	
	$sock->SaveConfigFile(base64_encode(serialize($array)), "KerbAuthInfos");
	
	
	$ldap_connection=@ldap_connect($_POST["LDAP_SERVER"],$_POST["LDAP_PORT"]);
	if(!$ldap_connection){
		echo "Connection Failed to connect to DC ldap://{$_POST["LDAP_SERVER"]}:{$_POST["LDAP_PORT"]}";
		if (@ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
			$error=$error."\n$extended_error";
		}		
		@ldap_close();
		return false;
	}
	
	ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
	ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0);
	$bind=ldap_bind($ldap_connection, $_POST["LDAP_DN"],$_POST["LDAP_PASSWORD"]);
	if(!$bind){
		
		$error=ldap_err2str(ldap_errno($ldap_connection));
		if (@ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
			$error=$error."\n$extended_error";
		}
		
		echo "Failed to login to DC {$_POST["LDAP_SERVER"]} - {$_POST["LDAP_DN"]} \n`$error`";
		return false;
	}	
	
	
	if($EnableWebProxyStatsAppliance==1){
		include_once("ressources/class.blackboxes.inc");
		$blk=new blackboxes();
		$blk->NotifyAll("BUILDCONF");
		return;
	}
	
	
	
}

function SaveSambaBindInterface(){
	$sock=new sockets();
	$sock->SET_INFO("SambaBindInterface", $_POST["SaveSambaBindInterface"]);
	$sock->getFrameWork("squid.php?samba-proxy=yes");
}

function SAVE_RECURSIVE_GROUPS(){
	$sock=new sockets();
	$tpl=new templates();
	$ArrayKerbAuthInfos=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	$ArrayKerbAuthInfos["RECURSIVE_GROUPS"]=$_POST["SAVE_RECURSIVE_GROUPS"];
	$sock->SaveConfigFile(base64_encode(serialize($ArrayKerbAuthInfos)), "KerbAuthInfos");
	
	$en="{disabled}";
	if($_POST["SAVE_RECURSIVE_GROUPS"]==1){
		$en="{enabled}";
	}
	
	
	$sock->getFrameWork("squid.php?reconfigure-squid=yes");
	echo $tpl->javascript_parse_text("{recursive_group_is} $en");
	
}

function settingsSave(){
	include_once(dirname(__FILE__)."/ressources/externals/Net_DNS2/DNS2.php");
	include_once(dirname(__FILE__)."/ressources/class.resolv.conf.inc");
	$ipClass=new IP();
	$sock=new sockets();
	$users=new usersMenus();
	$tpl=new templates();
	include_once(dirname(__FILE__)."/ressources/class.html.tools.inc");
	$_POST["WINDOWS_SERVER_PASS"]=url_decode_special_tool($_POST["WINDOWS_SERVER_PASS"]);
	
	if(strpos($_POST["WINDOWS_SERVER_ADMIN"], "@")>0){
		$trx=explode("@",$_POST["WINDOWS_SERVER_ADMIN"]);
		$_POST["WINDOWS_SERVER_ADMIN"]=$trx[0];
	}
	
	unset($_SESSION["EnableKerbAuth"]);
	
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}	
	
	
	$_POST["WINDOWS_DNS_SUFFIX"]=trim(strtolower($_POST["WINDOWS_DNS_SUFFIX"]));
	
	if($_POST["WINDOWS_DNS_SUFFIX"]==null){
		echo "Please set the DNS domain of your Active Directory server";
		return;
	}
	
	$Myhostname=$sock->getFrameWork("cmd.php?full-hostname=yes");	
	$MyhostnameTR=explode(".", $Myhostname);
	$MyNetbiosName=$MyhostnameTR[0];
	unset($MyhostnameTR[0]);
	$MyDomain=strtolower(@implode(".", $MyhostnameTR));
	if($MyDomain<>$_POST["WINDOWS_DNS_SUFFIX"]){
		$nic=new system_nic();
		$nic->set_hostname("$MyNetbiosName.{$_POST["WINDOWS_DNS_SUFFIX"]}");
	}
	
	$adhost="{$_POST["WINDOWS_SERVER_NETBIOSNAME"]}.{$_POST["WINDOWS_DNS_SUFFIX"]}";
	
	
	if(strtolower($adhost)==strtolower($Myhostname)){
		echo "Active Directory: $adhost as the same name of this server:$Myhostname\n";return;
		
	}
	
	if($_POST["ADNETIPADDR"]<>null){
		$ipaddrZ=explode(".",$_POST["ADNETIPADDR"]);
		while (list ($num, $a) = each ($ipaddrZ) ){
			$ipaddrZ[$num]=intval($a);
		}
		$_POST["ADNETIPADDR"]=@implode(".", $ipaddrZ);
	}
	
	$resolved=gethostbyname($adhost);
	if(!$ipClass->isValid($resolved)){
		if($ipClass->isValid($_POST["ADNETIPADDR"])){
			$resolved=CheckDNS($adhost,$_POST["ADNETIPADDR"]);
			if($ipClass->isValid($resolved)){$_POST["UseADAsNameServer"]=1;}
		}
	}
	

	
	
	
	
	
	$sock->SET_INFO("SambaBindInterface", $_POST["SambaBindInterface"]);
	$sock->SET_INFO("KerbAuthDisableNormalizeName", $_POST["KerbAuthDisableNormalizeName"]);
	$sock->SET_INFO("WindowsActiveDirectoryKerberos", $_POST["WindowsActiveDirectoryKerberos"]);
	
	$sock->SET_INFO("KerbAuthDisableGroupListing", $_POST["KerbAuthDisableGroupListing"]);
	$sock->SET_INFO("KerbAuthTrusted", $_POST["KerbAuthTrusted"]);
	$sock->SET_INFO("KerbAuthMapUntrustedDomain", $_POST["KerbAuthMapUntrustedDomain"]);
	$sock->SET_INFO("NtpdateAD", $_POST["NtpdateAD"]);
	$sock->SET_INFO("KerbAuthMethod", $_POST["KerbAuthMethod"]);
	$sock->SET_INFO("SquidNTLMKeepAlive", $_POST["SquidNTLMKeepAlive"]);
	$sock->SET_INFO("UseADAsNameServer", $_POST["UseADAsNameServer"]);
	$sock->SET_INFO("NET_RPC_INFOS",base64_encode(serialize(array())));
	
	$ArrayKerbAuthInfos=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	while (list ($num, $ligne) = each ($_POST) ){$ArrayKerbAuthInfos[$num]=$ligne;}
	$sock->SaveConfigFile(base64_encode(serialize($ArrayKerbAuthInfos)), "KerbAuthInfos");
	
	
	
	
	
		
	if($_POST["UseADAsNameServer"]==1){
		$resolve=new resolv_conf();
		$resolve->MainArray["DNS1"]=$_POST["ADNETIPADDR"];
		$resolve->save();
		
		$resolved=CheckDNS($adhost,$_POST["ADNETIPADDR"]);
		if(!$ipClass->isValid($resolved)){
			
			echo $tpl->javascript_parse_text("{error}: {unable_to_resolve} Active Directory: $adhost {with} {$_POST["ADNETIPADDR"]}",1);
			return;
		}
		
		
	}else{
		$resolved=gethostbyname($adhost);
		if(!$ipClass->isValid($resolved)){
			$tpl=new templates();
			if($EnableWebProxyStatsAppliance==0){$sock->SET_INFO("EnableKerbAuth", 0);}
			$sock->SaveConfigFile(base64_encode(serialize($_POST)), "KerbAuthInfos");
			echo $tpl->javascript_parse_text("{error}: {unable_to_resolve} Active Directory: $adhost",1);
			return;
		}
		
		if($resolved=="127.0.0.1"){
			echo $tpl->javascript_parse_text("{error}: $adhost lookup to 127.0.0.1 !\n");;
			return;
		}
		
		
	}
	
	
	if(strpos($_POST["ADNETBIOSDOMAIN"], ".")>0){
		echo "The netbios domain \"{$_POST["ADNETBIOSDOMAIN"]}\" is invalid.\n";
		$sock->SET_INFO("EnableKerbAuth", 0);
		return;
	}
	
	$sock->SET_INFO("EnableKerbAuth", $_POST["EnableKerbAuth"]);
	
}

function CheckDNS($hostname,$dns){

	
	$ipClass=new IP();
	$rs = new Net_DNS2_Resolver(array('nameservers' => array($dns)));
	try {
		$result = $rs->query($hostname, "A");
			
	} catch(Net_DNS2_Exception $e) {
		echo $e->getMessage();
		return null;
	}
	
	foreach($result->answer as $record){
		if($ipClass->isIPAddress($record->address)){return $record->address;}
	}
	
	
	
}


function SambeReconnectAD(){
	$sock=new sockets();
	$users=new usersMenus();

	$sock->getFrameWork("services.php?kerbauth=yes");
	$sock->getFrameWork("services.php?nsswitch=yes");
	$sock->getFrameWork("cmd.php?samba-reconfigure=yes");


	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}	

	if($EnableWebProxyStatsAppliance==1){
		include_once("ressources/class.blackboxes.inc");
		$blk=new blackboxes();
		$blk->NotifyAll("WINBIND_RECONFIGURE");
	}	
	
}

function test_auth(){
	include_once(dirname(__FILE__)."ressources/class.system.network.inc");
	include_once(dirname(__FILE__)."ressources/class.system.nics.inc");
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	$SquidBinIpaddr=trim($sock->GET_INFO("SquidBinIpaddr"));
	if($SquidBinIpaddr=="0.0.0.0"){$SquidBinIpaddr=null;}
	$q=new mysql_squid_builder();
	$t=time();
	$ip=new networking();
	$ips=$ip->ALL_IPS_GET_ARRAY();
	while (list ($num, $ligne) = each ($ips) ){if($num==null){continue;}if($num=="127.0.0.1"){continue;}$net[]=$num;}
	$BinIpaddrDefault=$net[0];
	
	
	$sql="SELECT *  FROM `proxy_ports` WHERE enabled=1";
	$results = $q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		$ipaddr=$BinIpaddrDefault;
		if($ligne["Parent"]==1){continue;}
		if($ligne["ICP"]==1){continue;}
		if($ligne["transparent"]==1){continue;}
		if($ligne["TProxy"]==1){continue;}
		if($ligne["is_nat"]==1){continue;}
		if($ligne["WCCP"]==1){continue;}
		$eth=$ligne["nic"];
		if($eth<>null){
			$nic=new system_nic($eth);
			$ipaddr=$nic->IPADDR;
		}
		$port=$ligne["port"];
		$PROXYS["$ipaddr:$port"]="$ipaddr:$port";
		
		
	}

	
	if (!extension_loaded('curl')) {echo "<H2>Fatal curl extension not loaded</H2>";die();}

	$html="
	
	<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
		<td class=legend style='font-size:24px;font-wieght:bold'>{listen_port}:</td>
		<td style='font-size:20px'>". Field_array_Hash($PROXYS, "TESTAUTHPROXY",$array["TESTAUTHPROXY"],"style:font-size:24px;font-wieght:bold")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
	<tr>
		<td class=legend  style='font-size:24px'>{username}:</td>
		<td>". Field_text("TESTAUTHUSER",$array["WINDOWS_SERVER_ADMIN"],"font-size:24px;padding:3px;width:490px")."</td>
	</tr>		
	<tr>
		<td class=legend  style='font-size:24px'>{password}:</td>
		<td>". Field_password("TESTAUTHPASS",$array["WINDOWS_SERVER_PASS"],"font-size:24px;padding:3px;width:490px")."</td>
	</tr>	
					
	<tr>
		<td colspan=2 align='right' style='padding-top:70px'>". button("{submit}","TestAuthPerform()",32)."</td>
	</tr>
	</table>
	</div>
	<div id='test-$t'></div>
	<script>
	var x_TestAuthPerform= function (obj) {
			LoadAjax('test-$t','$page?testauth-results=yes');	
		
	
			
			
		}		
		
		function TestAuthPerform(){
			var pp=encodeURIComponent(document.getElementById('TESTAUTHPASS').value);
			var XHR = new XHRConnection();
			XHR.appendData('TESTAUTHUSER',document.getElementById('TESTAUTHUSER').value);
			XHR.appendData('TESTAUTHPROXY',document.getElementById('TESTAUTHPROXY').value);
			XHR.appendData('TESTAUTHBIND','$BinIpaddrDefault');
			XHR.appendData('TESTAUTHPASS',pp);
			AnimateDiv('test-$t');
			XHR.sendAndLoad('$page', 'POST',x_TestAuthPerform);
		
		}
</script>	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function evaluation_period_days(){
	$Days=86400*30;
	$DayToLeft=30;
	$users=new usersMenus();
	if($users->CORP_LICENSE){return 365;}
	if(!is_file("/usr/share/artica-postfix/ressources/class.pinglic.inc")){return 365;}
	include_once("/usr/share/artica-postfix/ressources/class.pinglic.inc");
	$EndTime=$GLOBALS['ADLINK_TIME']+$Days;
	$seconds_diff = $EndTime - time();
	return(floor($seconds_diff/3600/24));
}


function test_auth_perform(){
	$tpl=new templates();
	
	$SquidBinIpaddr=$_POST["TESTPROXYIP"];
	if($SquidBinIpaddr=="0.0.0.0"){$SquidBinIpaddr="127.0.0.1";}
	$port=$_POST["TESTPROXYPORT"];
	$TESTAUTHPASS=url_decode_special_tool($_POST["TESTAUTHPASS"]);
	$TESTAUTHUSER=stripslashes($_POST["TESTAUTHUSER"]);
	$array["BIND"]=$_POST["TESTAUTHBIND"];
	$array["PROXY"]=$_POST["TESTAUTHPROXY"];
	$array["USER"]=$TESTAUTHUSER;
	$array["PASS"]=$TESTAUTHPASS;
	
	@file_put_contents("/usr/share/artica-postfix/ressources/conf/upload/NTLM_TESTS", serialize($array));
	$sock=new sockets();
	$sock->getFrameWork("squid.php?test-ntlm=yes");
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_INTERFACE,$_POST["TESTAUTHBIND"]);
	curl_setopt($ch, CURLOPT_URL, "http://www.google.com");
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);	
	curl_setopt($ch, CURLOPT_POST, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:10.0) Gecko/20100101 Firefox/10.0");
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Pragma: no-cache", "Cache-Control: no-cache"));
	curl_setopt($ch,CURLOPT_HTTPPROXYTUNNEL,FALSE); 
	curl_setopt ($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
	curl_setopt ($ch, CURLOPT_PROXY,"{$_POST["TESTAUTHPROXY"]}");
	curl_setopt ($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
	
	echo "Bind..........: {$_POST["TESTAUTHBIND"]}\n";
	echo "Proxy.........: {$_POST["TESTAUTHPROXY"]}\n";
	
	//curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
	//curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_NTLM);
	
	
	curl_setopt($ch, CURLOPT_PROXYUSERPWD, $TESTAUTHUSER.':'.$TESTAUTHPASS);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));		
	curl_setopt($ch, CURLOPT_NOBODY, true);
	$data=curl_exec($ch);
	
	if(preg_match("#X-Squid-Error:.*?([A-Z\_]+)#is", $data,$re)){echo "****  FAILED WITH ERROR \"{$re[1]}\" ***\n\n";}
	if(preg_match("#Proxy-Authenticate: NTLM\s+(.*?)\s+#",$data,$re)){$data=str_replace($re[1], "***", $data);}
	$error=curl_errno($ch);		
	$curl=new ccurl(null);
	
	if(!$curl->ParseError($error)){
		echo $error_text=$tpl->javascript_parse_text($curl->error)."\n";
	}
	$info = curl_getinfo($ch);
	curl_close($ch);	
	if(is_array($info)){
		while (list ($num, $ligne) = each ($info) ){
			$infos[]="$num: $ligne";
		}
	}
	$sep="\n------------------------------------------------------\n";
	echo "http://www.google.com return error $error$sep Datas:$sep$data\nInfos:$sep".@implode("\n", $infos);
}


function kerbchkconf(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();	
	$users=new usersMenus();
	$ActiveDirectoryEmergency=intval($sock->GET_INFO("ActiveDirectoryEmergency"));
	$AdminAsSeenNTLMPerfs=intval($sock->GET_INFO("AdminAsSeenNTLMPerfs"));
	$EnableKerbAuth=intval($sock->GET_INFO("EnableKerbAuth"));
	
	$ok="shield-ok-256.png";
	$grey="shield-grey-256.png";
	$warn="shield-warn-256.png";
	$red="shield-red-256.png";
	
	if($ActiveDirectoryEmergency==1){
		
		$html="<div style='margin-bottom:15px'>
				<center style='margin-bottom:10px'><img src='img/$warn'></center>
				<a href=\"javascript:blue();\" OnClick=\"javascript:Loadjs('squid.urgency.php?activedirectory=yes')\"
				style='font-size:18px;color:d32d2d;text-decoration:underline'>{activedirectory_emergency_mode}</a>
				<hr>
				<div style='font-size:14px'>{activedirectory_emergency_mode_explain}</div>
				</div>";
				
		echo $tpl->_ENGINE_parse_body($html);
		return;
	}
	
	
	
	if($users->SAMBA_INSTALLED){
		$samba_version=$sock->getFrameWork("samba.php?fullversion=yes");
		$samba_version_text=$tpl->_ENGINE_parse_body("<center><div style='font-size:18px;margin-bottom:10px'>{APP_SAMBA}:$samba_version</div></center>");
	}else{
		echo $tpl->_ENGINE_parse_body("
				<div style='margin-bottom:15px'>
				<center style='margin-bottom:10px'><img src='img/$red'></center>
				<center><div style='font-size:18px;color:d32d2d;'>{APP_SAMBA}: {NOT_INSTALLED}</div></center>
				</div>");
		return;
	}
	
	
	if(!$users->MSKTUTIL_INSTALLED){
		echo $tpl->_ENGINE_parse_body("
				<div style='margin-bottom:15px'>
				<center style='margin-bottom:10px'><img src='img/$red'></center>
				<center><div style='font-size:18px;color:d32d2d;'>{APP_MSKTUTIL_NOT_INSTALLED}</div></center>
				</div>");
			return;}
	
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	$error=array();
	
	if($AdminAsSeenNTLMPerfs==0){
		$error[]="
		<a href=\"javascript:blur();\"
				OnClick=\"javascript:LoadAjaxRound('activedirectory-settings','$page?ntlmauthenticators=yes');\"
				style='text-decoration:underline;font-size:16px;color:d32d2d;'>
		{NTLM_PERFORMANCES_NOT_DEFINED}</a>";
		
	}
	
	
	if($EnableKerbAuth==1){
		$DayToLeft=evaluation_period_days();
		if($DayToLeft<365){
				if($DayToLeft>0){
					$MAIN_ERROR=$tpl->_ENGINE_parse_body("{warn_no_license_activedirectory_30days}");
					$error[]=str_replace("%s", $DayToLeft, $MAIN_ERROR);
				}else{
					$error[]=$tpl->_ENGINE_parse_body("{warn_evaluation_period_end}");
				}
		}
	}
		
	$IsConnected=$sock->getFrameWork("squid.php?IsKerconnected=yes");
	if($IsConnected<>"TRUE"){$error[]="{proxy_is_not_configured_ad}";}
	

	
	
	if($array["ADNETBIOSDOMAIN"]==null){
		$error[]="{MISSING_PARAMETER}<br>{ADNETBIOSDOMAIN}";
	}
	if($array["WINDOWS_DNS_SUFFIX"]==null){
		$error[]="{MISSING_PARAMETER}<br>{WINDOWS_DNS_SUFFIX}";
	}
	if($array["WINDOWS_SERVER_NETBIOSNAME"]==null){
		$error[]="{MISSING_PARAMETER}<br>{WINDOWS_SERVER_NETBIOSNAME}";
	}
	if($array["WINDOWS_SERVER_TYPE"]==null){
		$error[]="{MISSING_PARAMETER}<br>{WINDOWS_SERVER_TYPE}";
		
	}
		
	if($array["WINDOWS_SERVER_ADMIN"]==null){
		$error[]="{MISSING_PARAMETER}<br>{administrator}";
	}
	if($array["WINDOWS_SERVER_PASS"]==null){
		$error[]="{MISSING_PARAMETER}<br>{password}";
	}
	
	$hostname=strtolower(trim($array["WINDOWS_SERVER_NETBIOSNAME"])).".".strtolower(trim($array["WINDOWS_DNS_SUFFIX"]));
	$ip=gethostbyname($hostname);
	if($ip==$hostname){
		$error[]="{WINDOWS_NAME_SERVICE_NOT_KNOWN}<br>$hostname";
		
	}
	
	if(count($error)>0){
		$html="<div style='margin-bottom:15px'>
		<center style='margin-bottom:10px'><img src='img/$warn'></center>
		<table style='width:100%'>
		";
		
		while (list ($disk, $log) = each ($error) ){
			$html=$html."<tr><td width=24px valign='top'><img src=img/arrow-right-24-red.png></td>
			<td><div style='font-size:16px;color:d32d2d;'>$log</div></td></tr>";
		}
		$html=$html."</table>";
	}else{
		$html="<div style='margin-bottom:15px'>
		<center style='margin-bottom:10px'><img src='img/$ok'></center>";
		
	}
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if($EnableKerbAuth==1){
		
		echo $tpl->_ENGINE_parse_body("
				
				
				
				
		<table style='width:100%;margin-top:10px'>
			<tr class=TableBouton2014 OnClick=\"javascript:LoadAjaxRound('activedirectory-settings','$page?settings-ad=yes');\">
				<td>
					<table style='width:100%'>
						<tr>
							<td style='width:35px'><img src='img/32-settings-white.png'></td>
							<td style='font-size:32px;color:white'>{parameters}</a></td>
						</tr>
					</table>
				</td>
			</tr>

			<tr class=TableBouton2014 OnClick=\"javascript:LoadAjaxRound('activedirectory-settings','$page?ntlmauthenticators=yes');\">
				<td>
					<table style='width:100%'>
						<tr>
							<td style='width:35px'><img src='img/processor-white-32.png'></td>
							<td style='font-size:32px;color:white'>{performance}</a></td>
						</tr>
					</table>
				</td>
			</tr>	

			<tr class=TableBouton2014 OnClick=\"javascript:LoadAjaxRound('activedirectory-settings','$page?statistics-by-group=yes');\">
				<td>
					<table style='width:100%'>
						<tr>
							<td style='width:35px'><img src='img/32-settings-white.png'></td>
							<td style='font-size:32px;color:white'>{statistics}</a></td>
						</tr>
					</table>
				</td>
			</tr>					
				
			<tr class=TableBouton2014 OnClick=\"javascript:LoadAjaxRound('activedirectory-settings','squid.adker.netads-status.php');\">
				<td>
					<table style='width:100%'>
						<tr>
							<td style='width:35px'><img src='img/processor-white-32.png'></td>
							<td style='font-size:32px;color:white'>{status}</a></td>
						</tr>
					</table>
				</td>
			</tr>				
			
				
				
				
			<tr class=TableBouton2014 OnClick=\"javascript:Loadjs('squid.ad.progress.php')\">
				<td>
					<table style='width:100%'>
						<tr>
							<td style='width:35px'><img src='img/reconfigure-32-white.png'></td>
							<td style='font-size:32px;color:white'>{restart_connection}</a></td>
						</tr>
					</table>
				</td>
			</tr>
				
				
			<tr class=TableBouton2014 OnClick=\"javascript:Loadjs('squid.compile.progress.php')\">
				<td>
					<table style='width:100%'>
						<tr>
							<td style='width:35px'><img src='img/apply-32-white.png'></td>
							<td style='font-size:32px;color:white'>{reconfigure}</a></td>
						</tr>
					</table>
				</td>
			</tr>				
		
		<tr class=TableBouton2014 OnClick=\"javascript:Loadjs('squid.ad.disconnect.progress.php')\">
		<td>
			<table style='width:100%'>
				<tr>
					<td style='width:35px'><img src='img/delete-32-white.png'></td>
					<td style='font-size:32px;color:white'>{disconnect}</a></td>
				</tr>
			</table>
		</td>
	</tr>				
				
	</table>		
				
		");
		
		
	}
	
	echo "<center style='font-size:16px;margin-top:15px'>$samba_version_text</center>";
}

function schedule_params(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	
	$AdSchBuildProxy=$sock->GET_INFO("AdSchBuildProxy");
	$AdSchBuildUfdb=$sock->GET_INFO("AdSchBuildUfdb");
	$AdSchRestartSquid=$sock->GET_INFO("AdSchRestartSquid");
	if(!is_numeric($AdSchBuildProxy)){$AdSchBuildProxy=0;}
	if(!is_numeric($AdSchBuildUfdb)){$AdSchBuildUfdb=0;}
	if(!is_numeric($AdSchRestartSquid)){$AdSchRestartSquid=0;}
	$html="<div class='explain' style='font-size:18px'>
	{ad_kerb_schedule_explain}
	</div>
	<div id='test-$t'></div>
	<div style='width:98%' class=form>
	<table>
	<tr>
		<td valign='top' class=legend style='font-size:18px'>{build_proxy_parameters}:</td>
		<td>". Field_checkbox("AdSchBuildProxy", 1,$AdSchBuildProxy)."</td>
	</tr>
	<tr>
		<td valign='top' class=legend style='font-size:18px'>{build_web_filtering_rules}:</td>
		<td>". Field_checkbox("AdSchBuildUfdb", 1,$AdSchBuildUfdb)."</td>
	</tr>	
	<tr>
		<td valign='top' class=legend style='font-size:18px'>{restart_the_web_proxy_service}:</td>
		<td>". Field_checkbox("AdSchRestartSquid", 1,$AdSchRestartSquid)."</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}", "AdSchBuildProxy$t()","16px")."</td>
	</tr>
	</table>
	</div>
	<script>
	var x_AdSchBuildProxy$t= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);}
			document.getElementById('test-$t').innerHTML='';
			
		}		
		
		function AdSchBuildProxy$t(){
			var XHR = new XHRConnection();
			AdSchBuildProxy=0;
			AdSchBuildUfdb=0;
			AdSchRestartSquid=0;
			if(document.getElementById('AdSchBuildProxy').checked){AdSchBuildProxy=1;}
			if(document.getElementById('AdSchBuildUfdb').checked){AdSchBuildUfdb=1;}
			if(document.getElementById('AdSchRestartSquid').checked){AdSchRestartSquid=1;}
			
			XHR.appendData('AdSchRestartSquid',AdSchRestartSquid);
			XHR.appendData('AdSchBuildUfdb',AdSchBuildUfdb);
			XHR.appendData('AdSchBuildProxy',AdSchBuildProxy);
			AnimateDiv('test-$t');
			XHR.sendAndLoad('$page', 'POST',x_AdSchBuildProxy$t);
		
		}		
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function schedule_save(){
	$sock=new sockets();
	while (list ($num, $ligne) = each ($_POST) ){
		$sock->SET_INFO("$num",$ligne);
	}
}


function cntlm(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	$q=new mysql_squid_builder();
	if(!$users->CNTLM_INSTALLED){
		echo "<p class=text-error>".$tpl->_ENGINE_parse_body("{CNTLM_NOT_INSTALLED}")."</p>";
	}
	$t=time();
	
	
	$sql="SELECT * FROM proxy_ports WHERE enabled=1 AND transparent=0";
	$results = $q->QUERY_SQL($sql);
	$HASH[null]="{select}";
	while ($ligne = mysql_fetch_assoc($results)) {
		$TProxy=$ligne["TProxy"];
		$NatProxy=$ligne["NatProxy"];
		if($TProxy==1){continue;}
		if($NatProxy==1){continue;}
		$port=intval($ligne["port"]);
		if($port<500){continue;}
		$PortName=$ligne["PortName"];
		$nic=$ligne["nic"];
		$HASH["$nic:$port"]="$PortName ($nic/$port)";
	}
	
	$EnableCNTLM=$sock->GET_INFO("EnableCNTLM");
	$CNTLMPort=$sock->GET_INFO("CnTLMPORT");
	$CnTLMDESTPORT=$sock->GET_INFO("CnTLMDESTPORT");
	
	if(!is_numeric($EnableCNTLM)){$EnableCNTLM=0;}
	if(!is_numeric($CNTLMPort)){$CNTLMPort=3155;}
	
	
	$html="
	<div id='test-$t'></div>
	<table style='width:100%'>
	<tr>
		<td valign='top' style='width:250px' nowrap><div id='cntml-status'></div></td>
		<td valign='top' style='width:100%'>
	<div style='width:98%' class=form>
	
	". Paragraphe_switch_img("{activate_CNTLM_service}", "{APP_CNTLM_EXPLAIN}",
			"EnableCNTLM",$EnableCNTLM,null,1150)."	
	<table style='width:100%'>

	<tr>
		<td valign='top' class=legend style='font-size:32px'>".texttooltip("{listen_port}","{CnTLMPORT_explain2}").":</td>
		<td>". Field_text("CnTLMPORT", $CNTLMPort,"font-size:32px;width:150px")."</td>
	</tr>
	<tr>
		<td valign='top' class=legend style='font-size:32px'>".texttooltip("{local_port}","{CnTLMPORT_localport_explain2}").":</td>
		<td>". Field_array_Hash($HASH,"CnTLMDESTPORT", $CnTLMDESTPORT,"style:font-size:32px")."</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}", "CNTLMSave$t()","40px")."</td>
	</tr>
	</table>
	</div>
	</td>
	</tr>
	</table>
	<script>
	var xCNTLMSave$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		Loadjs('cntlm.restart.progress.php');
		RefreshTab('squid_main_svc');
	}
	function CNTLMSave$t(){
		var XHR = new XHRConnection();
		XHR.appendData('EnableCNTLM',document.getElementById('EnableCNTLM').value);
		XHR.appendData('CnTLMPORT',document.getElementById('CnTLMPORT').value);
		XHR.appendData('CnTLMDESTPORT',document.getElementById('CnTLMDESTPORT').value);
		XHR.sendAndLoad('$page', 'POST',xCNTLMSave$t);
	
	}
	
	LoadAjax('cntml-status','$page?cntml-status=yes');
	
	
	</script>";
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function cntlm_status(){
	$sock=new sockets();
	$tpl=new templates();
	$ini=new Bs_IniHandler();
	$ini->loadString(base64_decode($sock->getFrameWork('cmd.php?squid-ini-status=yes')));
	$APP_CNTLM=DAEMON_STATUS_ROUND("APP_CNTLM",$ini,null,0);
	echo $tpl->_ENGINE_parse_body($APP_CNTLM);
}

function EnableCNTLM_save(){
	$sock=new sockets();
	$sock->SET_INFO("EnableCNTLM", $_POST["EnableCNTLM"]);
	$sock->SET_INFO("CnTLMPORT", $_POST["CnTLMPORT"]);
	$sock->SET_INFO("CnTLMDESTPORT", $_POST["CnTLMDESTPORT"]);
	//$sock->getFrameWork("squid.php?cntlm-restart=yes");
	
}

function test_auth_results(){
	$page=CurrentPageName();
	$tpl=new templates();
	$TESTS=false;
	$array=explode("\n",@file_get_contents("/usr/share/artica-postfix/ressources/conf/upload/NTLM_RESULTS"));
	while (list ($num, $ligne) = each ($array) ){
		if(preg_match("#(Proxy-Authorization|Proxy-Authenticate)#", $ligne)){continue;}
		if(preg_match("#HTTP\/[0-9\.]+\s+([0-9]+)\s+(.+)#", $ligne,$re)){
			$T[]="<hr>";
			$CODE_ERROR=$re[1];
			$CODE_TEXT=$re[2];
			if(intval($CODE_ERROR)<400){$TESTS=true;}
		}
		$T[]="<div><code style='font-size:12px'>".htmlspecialchars($ligne)."</code></div>";
		
	}
	
	$img="64-red.png";
	$img_text="{failed}";
	
	if($TESTS){
		$img="ok64.png";
		$img_text="{success}";
	}
	
	$html="
	<div style='width:98%' class=form>
	<center style='font-size:22px'>
		<div style='width:98%' class=form>
		<table style='width:40%'>
		<tr><td style='width:64px'><img src='img/$img' style='margin:15px;'></td><td style='font-size:22px'>$img_text
		<div style='font-size:14px;width:99%'>$CODE_TEXT ( $CODE_ERROR )</div>
		</td>
		</tr>
		</table>
		</div>
	</center>	
	".@implode($T)."</div>";
	echo $tpl->_ENGINE_parse_body($html);
}

function ntlmauthenticators(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	$warn_squid_reload=$tpl->javascript_parse_text("{warn_squid_reload}");
	$t=time();
	$DynamicGroupsAclsTTL=$sock->GET_INFO("DynamicGroupsAclsTTL");
	if(!is_numeric($DynamicGroupsAclsTTL)){$DynamicGroupsAclsTTL=3600;}
	if($DynamicGroupsAclsTTL<5){$DynamicGroupsAclsTTL=5;}
	
	
	
	$SquidClientParams=unserialize(base64_decode($sock->GET_INFO("SquidClientParams")));
	if(!is_numeric($SquidClientParams["auth_param_ntlm_children"])){$SquidClientParams["auth_param_ntlm_children"]=20;}
	if(!is_numeric($SquidClientParams["auth_param_ntlm_startup"])){$SquidClientParams["auth_param_ntlm_startup"]=0;}
	if(!is_numeric($SquidClientParams["auth_param_ntlm_idle"])){$SquidClientParams["auth_param_ntlm_idle"]=1;}
	
	if(!is_numeric($SquidClientParams["auth_param_ntlmgroup_children"])){$SquidClientParams["auth_param_ntlmgroup_children"]=15;}
	if(!is_numeric($SquidClientParams["auth_param_ntlmgroup_startup"])){$SquidClientParams["auth_param_ntlmgroup_startup"]=1;}
	if(!is_numeric($SquidClientParams["auth_param_ntlmgroup_idle"])){$SquidClientParams["auth_param_ntlmgroup_idle"]=1;}
	
	
	
	if(!is_numeric($SquidClientParams["auth_param_basic_children"])){$SquidClientParams["auth_param_basic_children"]=3;}
	if(!is_numeric($SquidClientParams["auth_param_basic_startup"])){$SquidClientParams["auth_param_basic_startup"]=2;}
	if(!is_numeric($SquidClientParams["auth_param_basic_idle"])){$SquidClientParams["auth_param_basic_idle"]=1;}
	
	if(intval($SquidClientParams["authenticate_cache_garbage_interval"])==0){$SquidClientParams["authenticate_cache_garbage_interval"]=18000;}
	if(intval($SquidClientParams["authenticate_ttl"])==0){$SquidClientParams["authenticate_ttl"]=14400;}
	if(intval($SquidClientParams["authenticate_ip_ttl"])==0){$SquidClientParams["authenticate_ip_ttl"]=$SquidClientParams["authenticate_ttl"];}
	
	
	
	if($SquidClientParams["authenticate_ttl"]>$SquidClientParams["authenticate_cache_garbage_interval"]){
		$SquidClientParams["authenticate_cache_garbage_interval"]=$SquidClientParams["authenticate_ttl"];
	}
	if(intval($SquidClientParams["credentialsttl"])==0){
		$SquidClientParams["credentialsttl"]=$SquidClientParams["authenticate_ttl"];
	}
	
	
	
	
	$ttl_interval[30]="30 {seconds}";
	$ttl_interval[60]="1 {minute}";
	$ttl_interval[300]="5 {minutes}";
	$ttl_interval[600]="10 {minutes}";
	$ttl_interval[900]="15 {minutes}";
	$ttl_interval[1800]="30 {minutes}";

	$ttl_interval[3600]="1 {hour}";
	$ttl_interval[7200]="2 {hours}";
	$ttl_interval[14400]="4 {hours}";
	$ttl_interval[18000]="5 {hours}";
	$ttl_interval[86400]="1 {day}";
	$ttl_interval[172800]="2 {days}";
	$ttl_interval[259200]="3 {days}";
	$ttl_interval[432000]="5 {days}";
	$ttl_interval[604800]="1 {week}";
	
	$start_up[1]=1;
	$start_up[2]=2;
	$start_up[3]=3;
	$start_up[4]=4;
	$start_up[5]=5;
	$start_up[10]=10;
	$start_up[15]=15;
	$start_up[20]=20;
	$start_up[25]=25;
	$start_up[30]=30;
	$start_up[35]=35;
	$start_up[40]=40;
	$start_up[45]=45;
	$start_up[50]=50;
	$start_up[55]=55;
	$start_up[60]=60;
	$start_up[65]=65;
	$start_up[100]=100;
	$start_up[150]=150;
	$start_up[200]=200;
	$start_up[300]=300;
	$start_up[500]=500;
	
	
	$CPUS=$users->CPU_NUMBER;
	$CPUS_TEXT=" ($CPUS)";
	
	$html="	<div style='font-size:42px;margin-top:20px'>{authentication_modules}</div>
	<div style='text-align:right;font-size:16px;margin-bottom:20px;'><i>{per_defined_processor} $CPUS_TEXT</i></div>
	
		
	<div style='font-size:16px;font-weight:bold;text-align:center;color:#E71010' id='$t-multi'></div>
	<div style='width:98%' class=form>
	<div class=explain style='font-size:14px;'>{SquidClientParams_text}</div>
	<table style='width:100%'>
	<tr style='height:70px'>
		<td colspan=3 style='font-size:32px'>{active_directory_authentication}</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>{max_processes}:</td>
		<td width=99%>". Field_array_Hash($start_up,"auth_param_ntlm_children-$t",$SquidClientParams["auth_param_ntlm_children"],null,null,0,"font-size:22px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>{preload_processes}:</td>
		<td width=99%>". Field_array_Hash($start_up,"auth_param_ntlm_startup-$t",$SquidClientParams["auth_param_ntlm_startup"],null,null,0,"font-size:22px")."</td>
		
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>{prepare_processes}:</td>
		<td width=99%>". Field_array_Hash($start_up,"auth_param_ntlm_idle-$t",$SquidClientParams["auth_param_ntlm_idle"],null,null,0,"font-size:22px")."</td>
		<td>&nbsp;</td>
	</tr>
				
	<tr style='height:70px'>
		<td colspan=3 style='font-size:32px'>{groups_checking}</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>{max_processes}:</td>
		<td width=99%>". Field_array_Hash($start_up,"auth_param_ntlmgroup_children-$t",$SquidClientParams["auth_param_ntlmgroup_children"],null,null,0,"font-size:22px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>{preload_processes}:</td>
		<td width=99%>". Field_array_Hash($start_up,"auth_param_ntlmgroup_startup-$t",$SquidClientParams["auth_param_ntlmgroup_startup"],null,null,0,"font-size:22px")."</td>
		
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>{prepare_processes}:</td>
		<td width=99%>". Field_array_Hash($start_up,"auth_param_ntlmgroup_idle-$t",$SquidClientParams["auth_param_ntlmgroup_idle"],null,null,0,"font-size:22px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' nowrap>{QUERY_GROUP_TTL_CACHE}:</td>
		<td width=99%>". Field_array_Hash($ttl_interval,"DynamicGroupsAclsTTL-$t",$DynamicGroupsAclsTTL,null,null,0,"font-size:22px")."</td>
		<td>&nbsp;</td>
	</tr>				
				
				
	<tr style='height:70px'>
		<td colspan=3 style='font-size:32px'>{sessions_cache}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>{authenticate_cache_garbage_interval}:</td>
		<td width=99%>". Field_array_Hash($ttl_interval,"authenticate_cache_garbage_interval-$t",$SquidClientParams["authenticate_cache_garbage_interval"],null,null,0,"font-size:22px")."</td>
		<td>&nbsp;</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>{authenticate_ttl_title}:</td>
		<td width=99%>". Field_array_Hash($ttl_interval,"authenticate_ttl-$t",$SquidClientParams["authenticate_ttl"],null,null,0,"font-size:22px")."</td>
		<td>&nbsp;</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>{authenticate_ip_ttl_title}:</td>
		<td width=99%>". Field_array_Hash($ttl_interval,"authenticate_ip_ttl-$t",$SquidClientParams["authenticate_ip_ttl"],null,null,0,"font-size:22px")."</td>
		<td>".help_icon("{authenticate_ip_ttl}")."</td>		
	</tr>
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>{credentialsttl}:</td>
		<td width=99%>". Field_array_Hash($ttl_interval,"credentialsttl-$t",$SquidClientParams["credentialsttl"],null,null,0,"font-size:22px")."</td>
		<td>&nbsp;</td>
	</tr>				
				
			
	<tr style='height:70px'>
		<td colspan=3 style='font-size:32px'>{basic_authentication}</td>
	</tr>				
				
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>Basic/LDAP {max_processes}:</td>
		
		<td width=99%>". Field_array_Hash($start_up,"auth_param_basic_children-$t",$SquidClientParams["auth_param_basic_children"],null,null,0,"font-size:22px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>Basic/LDAP {preload_processes}:</td>
		<td width=99%>". Field_array_Hash($start_up,"auth_param_basic_startup-$t",$SquidClientParams["auth_param_basic_startup"],null,null,0,"font-size:22px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>Basic/LDAP {prepare_processes}:</td>
		<td width=99%>". Field_array_Hash($start_up,"auth_param_basic_idle-$t",$SquidClientParams["auth_param_basic_idle"],null,null,0,"font-size:22px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}", "Save$t()","42")."</td>
	</tr>
	</table>
	</div>				
				
				
				
<script>
var xSave$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);return;}
    if(!confirm('$warn_squid_reload')){return;}
    Loadjs('squid.compile.progress.php');
}	

function Save$t(){
		
	var XHR = new XHRConnection();
	XHR.appendData('auth_param_ntlm_children',document.getElementById('auth_param_ntlm_children-$t').value);
	XHR.appendData('auth_param_ntlm_startup',document.getElementById('auth_param_ntlm_startup-$t').value);
	XHR.appendData('auth_param_ntlm_idle',document.getElementById('auth_param_ntlm_idle-$t').value);
	
	XHR.appendData('auth_param_ntlmgroup_children',document.getElementById('auth_param_ntlmgroup_children-$t').value);
	XHR.appendData('auth_param_ntlmgroup_startup',document.getElementById('auth_param_ntlmgroup_startup-$t').value);
	XHR.appendData('auth_param_ntlmgroup_idle',document.getElementById('auth_param_ntlmgroup_idle-$t').value);
	
	
	
	XHR.appendData('auth_param_basic_children',document.getElementById('auth_param_basic_children-$t').value);
	XHR.appendData('auth_param_basic_startup',document.getElementById('auth_param_basic_startup-$t').value);
	XHR.appendData('auth_param_basic_idle',document.getElementById('auth_param_basic_idle-$t').value);
	XHR.appendData('authenticate_cache_garbage_interval',document.getElementById('authenticate_cache_garbage_interval-$t').value);
	XHR.appendData('credentialsttl',document.getElementById('credentialsttl-$t').value);
	XHR.appendData('authenticate_ttl',document.getElementById('authenticate_ttl-$t').value);
	XHR.appendData('authenticate_ip_ttl',document.getElementById('authenticate_ip_ttl-$t').value);
	XHR.appendData('DynamicGroupsAclsTTL',document.getElementById('DynamicGroupsAclsTTL-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);		
}		
</script>";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function ntlmauthenticators_save(){
	$sock=new sockets();
	$SquidClientParams=unserialize(base64_decode($sock->GET_INFO("SquidClientParams")));
	
	while (list ($num, $ligne) = each ($_POST) ){
		$SquidClientParams[$num]=$ligne;
		
	}
	
	$sock->SaveConfigFile(base64_encode(serialize($SquidClientParams)), "SquidClientParams");
	$sock->SET_INFO("DynamicGroupsAclsTTL", $_POST["DynamicGroupsAclsTTL"]);
	$sock->SET_INFO("AdminAsSeenNTLMPerfs", 1);
}

function statistics_groups(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$AdStatsGroupPattern=$sock->GET_INFO("AdStatsGroupPattern");
	$AdStatsGroupAttribute=$sock->GET_INFO("AdStatsGroupAttribute");
	$AdStatsGroupMethod=$sock->GET_INFO("AdStatsGroupMethod");
	$attributes["title"]="title";
	$attributes["PhysicalDeliveryOfficeName"]="PhysicalDeliveryOfficeName";
	$attributes["department"]="department";
	$attributes["company"]="company";
	$attributes["businessCategory"]="businessCategory";
	$attributes["employeeType"]="employeeType";
	
	
	$types[0]="{use_first_group_found}";
	$types[1]="{find_a_group_regex}";
	$types[2]="{use_active_directory_attribute}";
	
	$html="<div style='font-size:30px;margin-bottom:20px'>{statistics_virtual_group}: {groups_retreiver}</div>
	<div class=explain style='font-size:18px'>{groups_retreiver_explain}</div>		
			
	<div style='width:98%' class=form>		
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{method}:</td>
		<td>". Field_array_Hash($types,"AdStatsGroupMethod-$t",$AdStatsGroupMethod,"Swich$t()",'',0,"font-size:22px")."</td>
	</tr>					
	<tr>
		<td class=legend style='font-size:22px'>{pattern}:</td>
		<td>". Field_text("AdStatsGroupPattern-$t",$AdStatsGroupPattern,"font-size:22px;
				font-weight:bold;width:600px")."</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:22px'>{attribute}:</td>
		<td>". Field_array_Hash($attributes,"AdStatsGroupAttribute-$t",$AdStatsGroupAttribute,"SwichDir$t()",'',0,"font-size:22px")."</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","Save$t();",28)."</td>
	</tr>
	</table>
	</div>
<script>
function Swich$t(){
	 document.getElementById('AdStatsGroupPattern-$t').disabled=true;
	 document.getElementById('AdStatsGroupAttribute-$t').disabled=true;
	 var selected=document.getElementById('AdStatsGroupMethod-$t').value;
	 if( selected == 1 ){
	 	 document.getElementById('AdStatsGroupPattern-$t').disabled=false;
	 	 return;
	 }
	 if( selected == 2 ){
	 	 document.getElementById('AdStatsGroupAttribute-$t').disabled=false;
	 	 return;
	 }
}

var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	Loadjs('squid.compile.progress.php');

}


function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('AdStatsGroupMethod',  document.getElementById('AdStatsGroupMethod-$t').value);
	XHR.appendData('AdStatsGroupAttribute',  document.getElementById('AdStatsGroupAttribute-$t').value);
	pp=encodeURIComponent(document.getElementById('AdStatsGroupPattern-$t').value);
	XHR.appendData('AdStatsGroupPattern',  pp);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
				
Swich$t();				
</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}




