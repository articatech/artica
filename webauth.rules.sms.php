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

if(isset($_POST["mobile"])){tests_smtp();exit;}
if(isset($_POST["ruleid"])){Save();exit;}
if(isset($_GET["test-smtp-js"])){tests_ask_smtp();exit;}

Page();


function tests_ask_smtp(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$mobile=$tpl->javascript_parse_text("{mobile}");
	echo "
			
	var xSave$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
	}
	
function Save$t(){		
	var mobile=prompt('$mobile ?');
	if(!mobile){alert('cancel...');return;}
	var XHR = new XHRConnection();
	XHR.appendData('mobile',mobile);
	XHR.appendData('ruleid','{$_GET["ruleid"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
	Save$t();
	";
	
}

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
	
	
	
	$html="
	<div style='width:100%;font-size:30px;margin-bottom:20px'>{self_register} SMS {rule}:$ruleid</div>		
	<div style='font-size:18px' class=explain>{hotspot_sms_mailexp}</div>
	
	<div style='width:98%' class=form>
	<table style='width:100%'>
	
	<tr>
		<td class=legend style='font-size:22px'>{form_message}:</td>
		<td style='width:860px'><textarea 
			style='width:100%;height:140px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='SMS_INTRO-$t'>".$wifidog_templates->SMS_INTRO."</textarea>
		</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:22px'>{font_size}:</strong></td>
		<td>" . Field_text("SMS_FONT_SIZE-$t",$wifidog_templates->SMS_FONT_SIZE,'font-size:22px;padding:3px;width:120px')."</td>
	</tr>					
	<tr>
		<td nowrap class=legend style='font-size:22px'>{field_label}:</strong></td>
		<td>" . Field_text("SMS_FIELD-$t",$wifidog_templates->SMS_FIELD,'font-size:22px;padding:3px;width:450px')."</td>
	</tr>					
	<tr>
		<td nowrap class=legend style='font-size:22px'>{button_label}:</strong></td>
		<td>" . Field_text("SMS_BUTTON-$t",$wifidog_templates->SMS_BUTTON,'font-size:22px;padding:3px;width:450px')."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{wrong_code_message}:</td>
		<td><textarea 
			style='width:100%;height:90px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='SMS_CODE_ERROR-$t'>". $wifidog_templates->SMS_CODE_ERROR."</textarea>
		</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:22px'>{smtp_register_subject}:</td>
		<td style='width:860px'><textarea 
			style='width:100%;height:40px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='SMS_SMTP_SUBJECT-$t'>".$wifidog_templates->SMS_SMTP_SUBJECT."</textarea>
		</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{message}:</td>
		<td><textarea 
			style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='SMS_SMTP_BODY-$t'>". $wifidog_templates->SMS_SMTP_BODY."</textarea>
		</td>
	</tr>	
		
	<tr>
		<td class=legend style='font-size:22px'>{smtp_confirm}:</td>
		<td style='width:860px'><textarea 
			style='width:100%;height:140px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='SMS_CONFIRM_MESSAGE-$t'>".$wifidog_templates->SMS_CONFIRM_MESSAGE."</textarea>
		</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:22px'>{smtp_server_name}:</strong></td>
		<td>" . Field_text("sms_smtp_server_name-$t",trim($sock->GET_INFO("sms_smtp_server_name")),'font-size:22px;padding:3px;width:450px')."</td>
	</tr>				
	<tr>
		<td nowrap class=legend style='font-size:22px'>{smtp_server_port}:</strong></td>
		<td>" . Field_text("sms_smtp_server_port-$t",trim($sock->GET_INFO("sms_smtp_server_port")),'font-size:22px;padding:3px;width:110px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:22px'>{smtp_sender}:</strong></td>
		<td>" . Field_text("sms_smtp_sender-$t",trim($sock->GET_INFO("sms_smtp_sender")),'font-size:22px;padding:3px;width:450px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:22px'>{recipient}:</strong></td>
		<td>" . Field_text("sms_smtp_recipient-$t",trim($sock->GET_INFO("sms_smtp_recipient")),'font-size:22px;padding:3px;width:450px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:22px'>{smtp_auth_user}:</strong></td>
		<td>" . Field_text("sms_smtp_auth_user-$t",trim($sock->GET_INFO("sms_smtp_auth_user")),'font-size:22px;padding:3px;width:450px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:22px'>{smtp_auth_passwd}:</strong></td>
		<td>" . Field_password("sms_smtp_auth_passwd-$t",trim($sock->GET_INFO("sms_smtp_auth_passwd")),'font-size:22px;padding:3px;width:450px')."</td>
				</tr>
	<tr>
		<td nowrap class=legend style='font-size:22px'>{tls_enabled}:</strong></td>
		<td>" . Field_checkbox_design("sms_tls_enabled-$t",1,$sock->GET_INFO("sms_tls_enabled"))."</td>
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
		var pp=encodeURIComponent(document.getElementById('sms_smtp_auth_passwd-$t').value);
		var XHR = new XHRConnection();
		XHR.appendData('ruleid',$ruleid);
		XHR.appendData('SMS_SMTP_BODY',encodeURIComponent(document.getElementById('SMS_SMTP_BODY-$t').value));
		XHR.appendData('SMS_SMTP_SUBJECT',encodeURIComponent(document.getElementById('SMS_SMTP_SUBJECT-$t').value));
		XHR.appendData('SMS_INTRO',encodeURIComponent(document.getElementById('SMS_INTRO-$t').value));
		XHR.appendData('SMS_BUTTON',encodeURIComponent(document.getElementById('SMS_BUTTON-$t').value));
		XHR.appendData('SMS_FIELD',encodeURIComponent(document.getElementById('SMS_FIELD-$t').value));
		XHR.appendData('SMS_CONFIRM_MESSAGE',encodeURIComponent(document.getElementById('SMS_CONFIRM_MESSAGE-$t').value));

		XHR.appendData('SMS_FONT_SIZE',encodeURIComponent(document.getElementById('SMS_FONT_SIZE-$t').value));
		XHR.appendData('SMS_CODE_ERROR',encodeURIComponent(document.getElementById('SMS_CODE_ERROR-$t').value));
		if(document.getElementById('sms_tls_enabled-$t').checked){XHR.appendData('sms_tls_enabled',1);}else{XHR.appendData('sms_tls_enabled',0); }
		XHR.appendData('sms_smtp_server_name',encodeURIComponent(document.getElementById('sms_smtp_server_name-$t').value));
		XHR.appendData('sms_smtp_server_port',encodeURIComponent(document.getElementById('sms_smtp_server_port-$t').value));
		XHR.appendData('sms_smtp_sender',encodeURIComponent(document.getElementById('sms_smtp_sender-$t').value));
		XHR.appendData('sms_smtp_recipient',encodeURIComponent(document.getElementById('sms_smtp_recipient-$t').value));
		XHR.appendData('sms_smtp_auth_user',encodeURIComponent(document.getElementById('sms_smtp_auth_user-$t').value));
		XHR.appendData('sms_smtp_auth_passwd',pp);
		XHR.appendData('smtp_notifications-$t','yes');
		XHR.sendAndLoad('$page', 'POST',xSave$t);
		
	}
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
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	
	$tpl=new templates();
	$sock=new sockets();
	$sock=new wifidog_settings($_POST["ruleid"]);
	$mobile=$_POST["mobile"];
	$CODE_NUMBER="1234";
	
	$wifidog_templates=new wifidog_templates($_POST["ruleid"]);
	$wifidog_templates->SMS_SMTP_SUBJECT=str_replace("%MOBILE%", $mobile, $wifidog_templates->SMS_SMTP_SUBJECT);
	$wifidog_templates->SMS_SMTP_SUBJECT=str_replace("%CODE%", $CODE_NUMBER, $wifidog_templates->SMS_SMTP_SUBJECT);
	$wifidog_templates->SMS_SMTP_SUBJECT=str_replace("%TIME%", time(), $wifidog_templates->SMS_SMTP_SUBJECT);
	
	$wifidog_templates->SMS_SMTP_BODY=str_replace("%MOBILE%", $mobile, $wifidog_templates->SMS_SMTP_BODY);
	$wifidog_templates->SMS_SMTP_BODY=str_replace("%CODE%", $CODE_NUMBER, $wifidog_templates->SMS_SMTP_BODY);
	$wifidog_templates->SMS_SMTP_BODY=str_replace("%TIME%", time(), $wifidog_templates->SMS_SMTP_BODY);
	$wifidog_templates->SMS_SMTP_BODY=str_replace("\n","\r\n",$wifidog_templates->SMS_SMTP_BODY);
		
	echo "Rule: {$_POST["ruleid"]}\n";
		
	$smtp_sender=$sock->GET_INFO("sms_smtp_sender");
	$smtp_senderTR=explode("@",$smtp_sender);
	$instance=$smtp_senderTR[1];
	$sms_smtp_recipient=$sock->GET_INFO("sms_smtp_recipient");
		
		
		$random_hash = md5(date('r', time()));
		$boundary="$random_hash/$instance";
		$body[]="Return-Path: <$smtp_sender>";
		$body[]="Date: ". date("D, d M Y H:i:s"). " +0100 (CET)";
		$body[]="From: $smtp_sender";
		$body[]="Subject: {$wifidog_templates->SMS_SMTP_SUBJECT}";
		$body[]="To: $sms_smtp_recipient";
		$body[]="Auto-Submitted: auto-replied";
		$body[]="MIME-Version: 1.0";
		$body[]="Content-Type: multipart/mixed;";
		$body[]="	boundary=\"$boundary\"";
		$body[]="Content-Transfer-Encoding: 8bit";
		$body[]="Message-Id: <$random_hash@$instance>";
		$body[]="--$boundary";
		$body[]="Content-Description: Notification";
		$body[]="Content-Type: text/plain; charset=us-ascii";
		$body[]="";
		$body[]=$wifidog_templates->SMS_SMTP_BODY;
		$body[]="";
		$body[]="";
		$body[]="--$boundary";
		$finalbody=@implode("\r\n", $body);
		
		include_once(dirname(__FILE__)."/ressources/class.webauth-sms-msmtp.inc");
		
		$webauth_msmtp=new webauth_sms_msmtp($finalbody,$_POST["ruleid"]);
		if($webauth_msmtp->Send()){
			echo $tpl->javascript_parse_text("{$wifidog_templates->SMS_SMTP_SUBJECT}\nTo $sms_smtp_recipient: {success}",1);
			return;

		}


	$smtp=new smtp();
	if($sock->GET_INFO("sms_smtp_auth_user")<>null){
		$params["auth"]=true;
		$params["user"]=$sock->GET_INFO("sms_smtp_auth_user");
		$params["pass"]=$sock->GET_INFO("sms_smtp_auth_passwd");
	}
	$params["host"]=$sock->GET_INFO("sms_smtp_server_name");
	$params["port"]=$sock->GET_INFO("sms_smtp_server_port");
	
	
	if(!$smtp->connect($params)){
		echo $tpl->javascript_parse_text("{error_while_sending_message} {error} $smtp->error_number $smtp->error_text",1);
		return;
	}


	if(!$smtp->send(array("from"=>$smtp_sender,"recipients"=>$sms_smtp_recipient,"body"=>$finalbody,"headers"=>null))){
		$smtp->quit();
		echo $tpl->javascript_parse_text("{error_while_sending_message} {error} $smtp->error_number $smtp->error_text",1);
		return;
	}

	echo $tpl->javascript_parse_text("{$wifidog_templates->SMS_SMTP_SUBJECT}\nTo $sms_smtp_recipient: {success}",1);
	$smtp->quit();

}