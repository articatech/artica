<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.squid.inc');
	
	$user=new usersMenus();
	if($user->AsDansGuardianAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	if(isset($_POST["ENABLED"])){Save();exit;}
	if(isset($_GET["js"])){js();exit;}
	
	page();
	function js(){
		header("content-type: application/x-javascript");
		$t=$_GET["t"];
		$page=CurrentPageName();
		$tpl=new templates();
		$title=$tpl->javascript_parse_text("{smtp_notifications}");
		echo "LoadWinORG2('705','$page','$title')";
	
	}	
	
function page(){

	$users=new usersMenus();
	$ini=new Bs_IniHandler();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$ini->loadString($sock->getFrameWork("cmd.php?SmtpNotificationConfigRead=yes"));
	if($ini->_params["SMTP"]["smtp_server_port"]==null){$ini->_params["SMTP"]["smtp_server_port"]=25;}
	if($ini->_params["SMTP"]["smtp_sender"]==null){$users=new usersMenus();$ini->_params["SMTP"]["smtp_sender"]="artica@$users->fqdn";}
	$t=time();
	$UfdbguardSMTPNotifs=unserialize(base64_decode($sock->GET_INFO("UfdbguardSMTPNotifs")));

	if(!isset($UfdbguardSMTPNotifs["ENABLED"])){$UfdbguardSMTPNotifs["ENABLED"]=0;}
	if(!is_numeric($UfdbguardSMTPNotifs["ENABLED"])){$UfdbguardSMTPNotifs["ENABLED"]=0;}	
	$UseRemoteUfdbguardService=$sock->GET_INFO("UseRemoteUfdbguardService");
	if(!is_numeric($UseRemoteUfdbguardService)){$UseRemoteUfdbguardService=0;}
	
	
	if(!isset($UfdbguardSMTPNotifs["smtp_server_name"])){$UfdbguardSMTPNotifs["smtp_server_name"]=$ini->_params["SMTP"]["smtp_server_name"];}
	if(!isset($UfdbguardSMTPNotifs["smtp_server_port"])){$UfdbguardSMTPNotifs["smtp_server_port"]=$ini->_params["SMTP"]["smtp_server_port"];}
	if(!isset($UfdbguardSMTPNotifs["smtp_sender"])){$UfdbguardSMTPNotifs["smtp_sender"]=$ini->_params["SMTP"]["smtp_sender"];}
	if(!isset($UfdbguardSMTPNotifs["smtp_dest"])){$UfdbguardSMTPNotifs["smtp_dest"]=$ini->_params["SMTP"]["smtp_dest"];}
	if(!isset($UfdbguardSMTPNotifs["smtp_auth_user"])){$UfdbguardSMTPNotifs["smtp_dest"]=$ini->_params["SMTP"]["smtp_auth_user"];}
	if(!isset($UfdbguardSMTPNotifs["smtp_auth_passwd"])){$UfdbguardSMTPNotifs["smtp_auth_passwd"]=$ini->_params["SMTP"]["smtp_auth_passwd"];}
	if(!isset($UfdbguardSMTPNotifs["tls_enabled"])){$UfdbguardSMTPNotifs["tls_enabled"]=$ini->_params["SMTP"]["tls_enabled"];}
	if(!isset($UfdbguardSMTPNotifs["ssl_enabled"])){$UfdbguardSMTPNotifs["ssl_enabled"]=$ini->_params["SMTP"]["ssl_enabled"];}
		
	if(!is_numeric($UfdbguardSMTPNotifs["smtp_server_port"])){$UfdbguardSMTPNotifs["smtp_server_port"]=25;}
		//Switchdiv
	
$html="
	<div class=explain style='font-size:22px'>{smtp_ufdbguard_notifications_text}</div>
	<div id='notif1-$t'></div>

	<table style='width:99%' class=form id='main-form-$t'>
	<tr>
		<td nowrap class=legend style='font-size:22px'>{smtp_enabled}:</strong></td>
		<td>" . Field_checkbox_design("ENABLED",1,$UfdbguardSMTPNotifs["ENABLED"],"SMTPNotifArticaEnableSwitch$t()")."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:22px'>{notify_blocks}:</strong></td>
		<td>" . Field_checkbox_design("BLOCK_NOTIFS",1,$UfdbguardSMTPNotifs["BLOCK_NOTIFS"])."</td>
	</tr>				
				
	<tr>
		<td nowrap class=legend style='font-size:22px'>{smtp_server_name}:</strong></td>
		<td>" . Field_text('smtp_server_name',trim($UfdbguardSMTPNotifs["smtp_server_name"]),'font-size:22px;padding:3px;width:550px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:22px'>{smtp_server_port}:</strong></td>
		<td>" . Field_text('smtp_server_port',trim($UfdbguardSMTPNotifs["smtp_server_port"]),'font-size:22px;padding:3px;width:120px')."</td>
	</tr>	
	<tr>
		<td nowrap class=legend style='font-size:22px'>{smtp_sender}:</strong></td>
		<td>" . Field_text('smtp_sender',trim($UfdbguardSMTPNotifs["smtp_sender"]),'font-size:22px;padding:3px;width:550px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:22px'>{smtp_dest}:</strong></td>
		<td>" . Field_text('smtp_dest',trim($UfdbguardSMTPNotifs["smtp_dest"]),'font-size:22px;padding:3px;width:550px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:22px'>{smtp_auth_user}:</strong></td>
		<td>" . Field_text('smtp_auth_user',trim($UfdbguardSMTPNotifs["smtp_auth_user"]),'font-size:22px;padding:3px;width:550px')."</td>
	</tr>	
	<tr>
		<td nowrap class=legend style='font-size:22px'>{smtp_auth_passwd}:</strong></td>
		<td>" . Field_password("smtp_auth_passwd-$t",trim($UfdbguardSMTPNotifs["smtp_auth_passwd"]),'font-size:22px;padding:3px;width:550px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:22px'>{tls_enabled}:</strong></td>
		<td>" . Field_checkbox_design("tls_enabled",1,$UfdbguardSMTPNotifs["tls_enabled"])."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:22px'>{UseSSL}:</strong></td>
		<td>" . Field_checkbox_design("ssl_enabled",1,$UfdbguardSMTPNotifs["ssl_enabled"])."</td>
	</tr>					
	<tr>
		<td align='right' colspan=2>".button('{apply}',"SaveArticaSMTPNotifValues$t();",40)."</td>
	</tr>

	</tr>
</table>
<script>
	var x_SaveArticaSMTPNotifValues$t= function (obj) {
		var results=obj.responseText;
		document.getElementById('notif1-$t').innerHTML='';
		if(results.length>0){alert(results);}
		RefreshTab('main_ufdbguard_config');
	}

	function SaveArticaSMTPNotifValues$t(){
		var UseRemoteUfdbguardService=$UseRemoteUfdbguardService;
		if(UseRemoteUfdbguardService==1){return;}
		var XHR = new XHRConnection();
		var pp=encodeURIComponent(document.getElementById('smtp_auth_passwd-$t').value);
		if(document.getElementById('ENABLED').checked){XHR.appendData('ENABLED',1);}else {XHR.appendData('ENABLED',0);}
		if(document.getElementById('BLOCK_NOTIFS').checked){XHR.appendData('BLOCK_NOTIFS',1);}else {XHR.appendData('BLOCK_NOTIFS',0);}
		if(document.getElementById('tls_enabled').checked){XHR.appendData('tls_enabled',1);}else {XHR.appendData('tls_enabled',0);}
		if(document.getElementById('ssl_enabled').checked){XHR.appendData('ssl_enabled',1);}else {XHR.appendData('ssl_enabled',0);}
		XHR.appendData('smtp_server_name',document.getElementById('smtp_server_name').value);
		XHR.appendData('smtp_server_port',document.getElementById('smtp_server_port').value);
		XHR.appendData('smtp_sender',document.getElementById('smtp_sender').value);
		XHR.appendData('smtp_dest',document.getElementById('smtp_dest').value);
		XHR.appendData('smtp_auth_user',document.getElementById('smtp_auth_user').value);
		XHR.appendData('smtp_auth_passwd',pp);
		XHR.appendData('smtp_notifications','yes');
		XHR.sendAndLoad('$page', 'POST',x_SaveArticaSMTPNotifValues$t);
	}
	
	function SMTPNotifArticaEnableSwitch$t(){
		document.getElementById('smtp_auth_passwd-$t').disabled=true;
		document.getElementById('smtp_auth_user').disabled=true;
		document.getElementById('smtp_dest').disabled=true;
		document.getElementById('smtp_sender').disabled=true;
		document.getElementById('smtp_server_port').disabled=true;
		document.getElementById('smtp_server_name').disabled=true;
		document.getElementById('tls_enabled').disabled=true;
		document.getElementById('ssl_enabled').disabled=true;
		
		
		
		if(!document.getElementById('ENABLED').checked){return;}
		
		document.getElementById('smtp_auth_passwd-$t').disabled=false;
		document.getElementById('smtp_auth_user').disabled=false;
		document.getElementById('smtp_dest').disabled=false;
		document.getElementById('smtp_sender').disabled=false;
		document.getElementById('smtp_server_port').disabled=false;
		document.getElementById('smtp_server_name').disabled=false;
		document.getElementById('tls_enabled').disabled=false;
		document.getElementById('ssl_enabled').disabled=false;	
		var UseRemoteUfdbguardService=$UseRemoteUfdbguardService;
		if(UseRemoteUfdbguardService==1){
			DisableFieldsFromId('main-form-$t');	
		}		
		
	}
	SMTPNotifArticaEnableSwitch$t();
</script>";

echo $tpl->_ENGINE_parse_body($html);

}

function Save(){
	$_POST["smtp_auth_passwd"]=url_decode_special_tool($_POST["smtp_auth_passwd"]);
	$sock=new sockets();
	$sock=new sockets();
	$UfdbguardSMTPNotifs=unserialize(base64_decode($sock->GET_INFO("UfdbguardSMTPNotifs")));
	while (list ($num, $ligne) = each ($_POST) ){
		$UfdbguardSMTPNotifs[$num]=$ligne;
	}
	$sock->SaveConfigFile(base64_encode(serialize($UfdbguardSMTPNotifs)), "UfdbguardSMTPNotifs");
	$sock->getFrameWork("ufdbguard.php?ufdbtail-restart=yes");
	
}

