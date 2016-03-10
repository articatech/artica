<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.wifidog.settings.inc');
include_once('ressources/class.webauth-msmtp.inc');
include_once(dirname(__FILE__).'/ressources/smtp/smtp.php');

$usersmenus=new usersMenus();
if(!$usersmenus->AsSquidAdministrator){echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die();}

if(isset($_POST["ruleid"])){Save();exit;}
if(isset($_GET["test-smtp-js"])){tests_smtp();exit;}

Page();
function Page(){
	$ruleid=$_GET["ID"];
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	
	$sock=new wifidog_settings($ruleid);
	$wifidog_templates=new wifidog_templates($ruleid);
	$ArticaHotSpotNowPassword=intval($sock->GET_INFO("ArticaHotSpotNowPassword"));
	$ENABLED_REDIRECT_LOGIN=intval($sock->GET_INFO("ENABLED_REDIRECT_LOGIN"));
	$USE_TERMS=intval($sock->GET_INFO("USE_TERMS"));
	$REGISTER_MAX_TIME=$sock->GET_INFO("REGISTER_MAX_TIME");
	$smtp_server_port=intval(trim($sock->GET_INFO("smtp_server_port")));
	if($smtp_server_port==0){$smtp_server_port=25;}
	
	if($REGISTER_MAX_TIME<5){$REGISTER_MAX_TIME=5;}
	
	$Timez[5]="5 {minutes}";
	$Timez[10]="10 {minutes}";
	$Timez[15]="15 {minutes}";
	$Timez[30]="30 {minutes}";
	$Timez[60]="1 {hour}";
	$Timez[120]="2 {hours}";
	$Timez[180]="3 {hours}";
	$Timez[360]="6 {hours}";
	$Timez[720]="12 {hours}";
	$Timez[1440]="1 {day}";
	$Timez[2880]="2 {days}";
	$Timez[4320]="3 {days}";
	$Timez[5760]="4 {days}";
	$Timez[7200]="5 {days}";
	$Timez[10080]="1 {week}";
	$Timez[20160]="2 {weeks}";
	$html="
	<div style='width:100%;font-size:30px;margin-bottom:20px'>{self_register} SMTP {rule}:$ruleid</div>		
	<div style='width:98%' class=form>
			
". Paragraphe_switch_img("{enable_hotspot_smtp}", 
			"{enable_hotspot_smtp_explain}","ENABLED_SMTP-$t",intval($sock->GET_INFO("ENABLED_SMTP")),null,1090)."			
			
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px' style='width:600px'>{max_time_register}:</td>
		<td>". Field_array_Hash($Timez,"REGISTER_MAX_TIME-$t", $REGISTER_MAX_TIME,"style:font-size:22px")."</td>
	</tr>

	<tr>
		<td class=legend style='font-size:22px' style='width:600px'>{passphrase}:</td>
		<td style='width:760px'>". Field_checkbox_design("REGISTER_GENERIC_PASSWORD-$t",1, 
				$wifidog_templates->REGISTER_GENERIC_PASSWORD,"REGISTER_GENERIC_PASSWORDCheck$t()")."</td>
	</tr>
	</table>
	<div id='REGISTER_GENERIC_PASSWORD-DIV$t'>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{passphrase_label}:</td>
		<td><textarea 
			style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='REGISTER_GENERIC_LABEL-$t'>". $wifidog_templates->REGISTER_GENERIC_LABEL."</textarea>
		</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{passphrase}:</td>
		<td><textarea 
			style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='REGISTER_GENERIC_PASSTXT-$t'>". $wifidog_templates->REGISTER_GENERIC_PASSTXT."</textarea>
		</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{error_text}:</td>
		<td><textarea 
			style='width:540px;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='REGISTER_GENERIC_PASSERR-$t'>". $wifidog_templates->REGISTER_GENERIC_PASSERR."</textarea>
		</td>
	</tr>
	</table>
	</div>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{smtp_register_message}:</td>
		<td><textarea 
			style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='REGISTER_MESSAGE-$t'>". $wifidog_templates->REGISTER_MESSAGE."</textarea>
		</td>
	</tr>					
	<tr>
		<td class=legend style='font-size:22px'>{smtp_register_subject}:</td>
		<td style='width:860px'><textarea 
			style='width:100%;height:40px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='REGISTER_SUBJECT-$t'>".$wifidog_templates->REGISTER_SUBJECT."</textarea>
		</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{smtp_confirm}:</td>
		<td style='width:860px'><textarea 
			style='width:100%;height:140px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='CONFIRM_MESSAGE-$t'>".$wifidog_templates->CONFIRM_MESSAGE."</textarea>
		</td>
	</tr>
	
	
	
	<tr>
		<td class=legend style='font-size:22px'>{lost_password_text}:</td>
		<td style='width:860px'><textarea 
			style='width:100%;height:40px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='LostPasswordLink-$t'>".$wifidog_templates->LostPasswordLink."</textarea>
		</td>
	</tr>
	
	
					
		<tr>
		<td nowrap class=legend style='font-size:22px'>{smtp_server_name}:</strong></td>
		<td>" . Field_text("smtp_server_name-$t",trim($sock->GET_INFO("smtp_server_name")),'font-size:22px;padding:3px;width:450px')."</td>
	</tr>				
	<tr>
		<td nowrap class=legend style='font-size:22px'>{smtp_server_port}:</strong></td>
		<td>" . Field_text("smtp_server_port-$t",$smtp_server_port,'font-size:22px;padding:3px;width:110px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:22px'>{smtp_sender}:</strong></td>
		<td>" . Field_text("smtp_sender-$t",trim($sock->GET_INFO("smtp_sender")),'font-size:22px;padding:3px;width:450px')."</td>
	</tr>

	<tr>
		<td nowrap class=legend style='font-size:22px'>{smtp_auth_user}:</strong></td>
		<td>" . Field_text("smtp_auth_user-$t",trim($sock->GET_INFO("smtp_auth_user")),'font-size:22px;padding:3px;width:450px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:22px'>{smtp_auth_passwd}:</strong></td>
		<td>" . Field_password("smtp_auth_passwd-$t",trim($sock->GET_INFO("smtp_auth_passwd")),'font-size:22px;padding:3px;width:450px')."</td>
				</tr>
	<tr>
		<td nowrap class=legend style='font-size:22px'>{tls_enabled}:</strong></td>
		<td>" . Field_checkbox_design("tls_enabled-$t",1,$sock->GET_INFO("tls_enabled"))."</td>
	</tr>
	<tr>
		<td align='right' colspan=2>
				".button('{test}',"TestSMTP$t();",32)."&nbsp;".button('{apply}',"Save$t();",32)."</td>
	</tr>
	</table>
	<script>
	
	var xSave$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		$('#HOSTPOT_RULES').flexReload();
	}	
	
	function TestSMTP$t(){
		Save$t();
		Loadjs('$page?test-smtp-js=yes&ruleid=$ruleid');
	}
	
	function Save$t(){
		var pp=encodeURIComponent(document.getElementById('smtp_auth_passwd-$t').value);
		var XHR = new XHRConnection();
		XHR.appendData('ruleid',$ruleid);
		XHR.appendData('ENABLED_SMTP',encodeURIComponent(document.getElementById('ENABLED_SMTP-$t').value));
		XHR.appendData('REGISTER_SUBJECT',encodeURIComponent(document.getElementById('REGISTER_SUBJECT-$t').value));
		XHR.appendData('REGISTER_MESSAGE',encodeURIComponent(document.getElementById('REGISTER_MESSAGE-$t').value));
		XHR.appendData('LostPasswordLink',encodeURIComponent(document.getElementById('LostPasswordLink-$t').value));
		XHR.appendData('REGISTER_MAX_TIME',encodeURIComponent(document.getElementById('REGISTER_MAX_TIME-$t').value));
		XHR.appendData('CONFIRM_MESSAGE',encodeURIComponent(document.getElementById('CONFIRM_MESSAGE-$t').value));
		if(document.getElementById('REGISTER_GENERIC_PASSWORD-$t').checked){XHR.appendData('REGISTER_GENERIC_PASSWORD',1);}else{XHR.appendData('REGISTER_GENERIC_PASSWORD',0); }
		XHR.appendData('REGISTER_GENERIC_LABEL',encodeURIComponent(document.getElementById('REGISTER_GENERIC_LABEL-$t').value));
		XHR.appendData('REGISTER_GENERIC_PASSTXT',encodeURIComponent(document.getElementById('REGISTER_GENERIC_PASSTXT-$t').value));
		XHR.appendData('REGISTER_GENERIC_PASSERR',encodeURIComponent(document.getElementById('REGISTER_GENERIC_PASSERR-$t').value));
		
		
		
		if(document.getElementById('tls_enabled-$t').checked){XHR.appendData('tls_enabled',1);}else{XHR.appendData('tls_enabled',0); }
		XHR.appendData('smtp_server_name',encodeURIComponent(document.getElementById('smtp_server_name-$t').value));
		XHR.appendData('smtp_server_port',encodeURIComponent(document.getElementById('smtp_server_port-$t').value));
		XHR.appendData('smtp_sender',encodeURIComponent(document.getElementById('smtp_sender-$t').value));
		XHR.appendData('smtp_auth_user',encodeURIComponent(document.getElementById('smtp_auth_user-$t').value));
		XHR.appendData('smtp_auth_passwd',pp);
		XHR.appendData('smtp_notifications-$t','yes');
		XHR.sendAndLoad('$page', 'POST',xSave$t);
		
	}
function REGISTER_GENERIC_PASSWORDCheck$t(){

	document.getElementById('REGISTER_GENERIC_PASSWORD-DIV$t').style.display = 'none';  
	
	if(document.getElementById('REGISTER_GENERIC_PASSWORD-$t').checked){
		document.getElementById('REGISTER_GENERIC_PASSWORD-DIV$t').style.display = 'block';  

	}
	
}
REGISTER_GENERIC_PASSWORDCheck$t();	

</script>";	
	
	echo $tpl->_ENGINE_parse_body($html);
}


function Save(){
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	$sock=new wifidog_settings($_POST["ruleid"]);
	unset($_POST["ruleid"]);
	while (list ($key, $value) = each ($_POST) ){
		$value=url_decode_special_tool($value);
		$sock->SET_INFO($key, $value);
		
	}
	
	
	$sock=new sockets();
	$sock->getFrameWork("hotspot.php?remove-cache=yes");
	
}

function tests_smtp(){
	//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	
	include_once(dirname(__FILE__)."/ressources/externals/mime/mime.inc");
	
	header("content-type: application/x-javascript");
	$sock=new sockets();
	$sock=new wifidog_settings($_GET["ruleid"]);
	$wifidog_templates=new wifidog_templates($_GET["ruleid"]);
	$ArticaSplashHotSpotEndTime=$sock->GET_INFO("ArticaSplashHotSpotEndTime");
	$proto="http";
	$myHostname=$_SERVER["HTTP_HOST"];
	$page=CurrentPageName();
	if(isset($_SERVER["HTTPS"])){$proto="https";}
	$URL_REDIRECT="$proto://$myHostname/$page?wifidog-confirm=NONE";
	$tpl=new templates();
	$smtp_sender=$sock->GET_INFO("smtp_sender");
	
	if($GLOBALS["VERBOSE"]){echo "new Mail_mime....<br>\n";}
	
	
	include_once(dirname(__FILE__)."/ressources/externals/mime/mime.inc");
	$message = new Mail_mime("\r\n");
	

	$text = "<p style=font-size:18px>$wifidog_templates->REGISTER_MESSAGE</p><p>
	<hr><center><a href=\"$URL_REDIRECT\" style='font-size:22px;text-decoration:underline'>$URL_REDIRECT</a></center></p>";
	
	$message->setFrom($smtp_sender);
	$message->addTo($smtp_sender);
	$message->setSubject($wifidog_templates->REGISTER_SUBJECT);
	$message->setTXTBody(strip_tags($text)); // for plain-text
	$message->setHTMLBody($text);
	$finalbody = $message->getMessage();
	
	
	if($GLOBALS["VERBOSE"]){echo 
	$finalbody."<hr>\n";}
	
	


	$webauth_msmtp=new webauth_msmtp($smtp_sender, $finalbody,$smtp_sender,$_GET["ruleid"]);
	if($webauth_msmtp->Send()){
		echo "alert('Rule: {$_GET["ruleid"]} $smtp_sender ".$tpl->javascript_parse_text("{$wifidog_templates->REGISTER_SUBJECT}\nTo $smtp_sender: {success}")."');";
		return;

	}else{
		echo "alert('Rule: {$_GET["ruleid"]} $smtp_sender Method 1 ".$tpl->javascript_parse_text($webauth_msmtp->logs)."');";
		
	}


	$smtp=new smtp();
	if($sock->GET_INFO("smtp_auth_user")<>null){
		$params["auth"]=true;
		$params["user"]=$sock->GET_INFO("smtp_auth_user");
		$params["pass"]=$sock->GET_INFO("smtp_auth_passwd");
	}
	$params["host"]=$sock->GET_INFO("smtp_server_name");
	$params["port"]=$sock->GET_INFO("smtp_server_port");
	if(!$smtp->connect($params)){
		echo "alert('".$tpl->javascript_parse_text("{error_while_sending_message} {error} $smtp->error_number $smtp->error_text")."');";
		return;
	}


	if(!$smtp->send(array("from"=>$smtp_sender,"recipients"=>$smtp_sender,"body"=>$finalbody,"headers"=>null))){
		$smtp->quit();
		echo "alert('".$tpl->javascript_parse_text("{error_while_sending_message} {error} $smtp->error_number $smtp->error_text")."');";
		return;
	}

	echo "alert('".$tpl->javascript_parse_text("{$wifidog_templates->REGISTER_SUBJECT}\nTo $smtp_sender: {success}")."');";
	$smtp->quit();

}