<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.wifidog.settings.inc');

$usersmenus=new usersMenus();
if(!$usersmenus->AsSquidAdministrator){echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die();}

if(isset($_POST["ruleid"])){Save();exit;}


Page();
function Page(){
	$ruleid=$_GET["ID"];
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	
	$sock=new wifidog_templates($ruleid);
	$this_feature_is_disabled_corp_license=$tpl->javascript_parse_text("{this_feature_is_disabled_corp_license}");
	$users=new usersMenus();
	$CORP=0;
	if($users->CORP_LICENSE){$CORP=1;}
	
	$html="<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{title2}:</td>
		<td style='width:860px'><textarea 
			style='width:100%;height:40px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='MainTitle-$t'>". $sock->MainTitle."</textarea>
		</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{label} {username}:</td>
		<td style='width:860px'><textarea 
			style='width:100%;height:40px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='LabelUsername-$t'>".utf8_encode($sock->LabelUsername)."</textarea>
		</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{label} {password}:</td>
		<td style='width:860px'><textarea 
			style='width:100%;height:40px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='LabelPassword-$t'>".utf8_encode($sock->LabelPassword)."</textarea>
		</td>
	</tr>	
	
	<tr>
		<td class=legend style='font-size:22px'>{header_form}:</td>
		<td style='width:860px'><textarea 
			style='width:100%;height:40px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='FORM_HEAD-$t'>".utf8_encode($sock->FORM_HEAD)."</textarea>
		</td>
	</tr>	
	
	<tr>
		<td class=legend style='font-size:22px'>{welcome_message}:</td>
		<td style='width:860px'><textarea 
			style='width:100%;height:70px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='WelcomeMessage-$t'>".utf8_encode($sock->WelcomeMessage)."</textarea>
		</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{footer_text}:</td>
		<td style='width:860px'><textarea 
			style='width:100%;height:70px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='FooterText-$t'>".$sock->FooterText."</textarea>
		</td>
	</tr>	
	

	
	<tr>
		<td class=legend style='font-size:22px'>{connection} {button}:</td>
		<td style='width:860px'><textarea 
			style='width:100%;height:70px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='ConnectionButton-$t'>$sock->ConnectionButton</textarea>
		</td>
	</tr>	
	
	
		<tr>
		<td class=legend style='font-size:22px'>{Terms_Conditions_explain}:</td>
		<td style='width:860px'><textarea 
			style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='TERMS_EXPLAIN-$t'>".$sock->TERMS_EXPLAIN."</textarea>
		</td>
	</tr>	
	
	<tr>
		<td class=legend style='font-size:22px'>{Terms_Conditions}:</td>
		<td style='width:860px'><textarea 
			style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='TERMS_CONDITIONS-$t'>".utf8_encode($sock->TERMS_CONDITIONS)."</textarea>
		</td>
	</tr>	
	
	<tr>
		<td class=legend style='font-size:22px'>{register} {title2}:</td>
		<td style='width:860px'><textarea 
			style='width:100%;height:40px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='RegisterTitle-$t'>".utf8_encode($sock->RegisterTitle)."</textarea>
		</td>
	</tr>
		
	<tr>
		<td class=legend style='font-size:22px'>{register_explain}:</td>
		<td style='width:860px'><textarea 
			style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='REGISTER_MESSAGE_EXPLAIN-$t'>".$sock->REGISTER_MESSAGE_EXPLAIN."</textarea>
		</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:22px'>{smtp_register_message_success}:</td>
		<td style='width:860px'><textarea 
			style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='REGISTER_MESSAGE_SUCCESS-$t'>".$sock->REGISTER_MESSAGE_SUCCESS."</textarea>
		</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{ArticaSplashHotSpotRedirectText}:</td>
		<td style='width:860px'><textarea 
			style='width:100%;height:40px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='ArticaSplashHotSpotRedirectText-$t'>".utf8_encode($sock->ArticaSplashHotSpotRedirectText)."</textarea>
		</td>
	</tr>	
	
	

	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","Save$t()","42px")."</td>
	</tr>
	</table>
	<script>
	
	var xSave$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
	}	
	
	function Save$t(){
		var CORP=$CORP;
		if(CORP==0){alert('$this_feature_is_disabled_corp_license');return;}
	
	
		var XHR = new XHRConnection();
		XHR.appendData('ruleid',$ruleid);
		XHR.appendData('TERMS_EXPLAIN',encodeURIComponent(document.getElementById('TERMS_EXPLAIN-$t').value));
		XHR.appendData('TERMS_CONDITIONS',encodeURIComponent(document.getElementById('TERMS_CONDITIONS-$t').value));
		XHR.appendData('MainTitle',encodeURIComponent(document.getElementById('MainTitle-$t').value));
		XHR.appendData('WelcomeMessage',encodeURIComponent(document.getElementById('WelcomeMessage-$t').value));
		XHR.appendData('REGISTER_MESSAGE_EXPLAIN',encodeURIComponent(document.getElementById('REGISTER_MESSAGE_EXPLAIN-$t').value));
		XHR.appendData('RegisterTitle',encodeURIComponent(document.getElementById('RegisterTitle-$t').value));
		XHR.appendData('REGISTER_MESSAGE_SUCCESS',encodeURIComponent(document.getElementById('REGISTER_MESSAGE_SUCCESS-$t').value));
		XHR.appendData('ConnectionButton',encodeURIComponent(document.getElementById('ConnectionButton-$t').value));
		XHR.appendData('ArticaSplashHotSpotRedirectText',encodeURIComponent(document.getElementById('ArticaSplashHotSpotRedirectText-$t').value));
		XHR.appendData('FooterText',encodeURIComponent(document.getElementById('FooterText-$t').value));
		XHR.appendData('LabelUsername',encodeURIComponent(document.getElementById('LabelUsername-$t').value));
		XHR.appendData('LabelPassword',encodeURIComponent(document.getElementById('LabelPassword-$t').value));
		XHR.appendData('FORM_HEAD',encodeURIComponent(document.getElementById('FORM_HEAD-$t').value));
		
		
		
		
		XHR.sendAndLoad('$page', 'POST',xSave$t);
		
	}
</script>";	
	
	echo $tpl->_ENGINE_parse_body($html);
}


function Save(){
	$sock=new wifidog_settings($_POST["ruleid"]);
	unset($_POST["ruleid"]);
	while (list ($key, $value) = each ($_POST) ){
		$value=url_decode_special_tool($value);
		$sock->SET_INFO($key, $value);
		
	}
	$sock=new sockets();
	$sock->getFrameWork("hotspot.php?remove-cache=yes");
}