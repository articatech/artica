<?php
	if(isset($_GET["verbose"])){
			$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;
			$GLOBALS["debug"]=true;ini_set('display_errors', 1);
			ini_set('error_reporting', E_ALL);
			ini_set('error_prepend_string',null);
			ini_set('error_append_string',null);
	}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	
	
	$user=new usersMenus();
	$sock=new sockets();
	$EnableSquidUrgencyPublic=$sock->GET_INFO("EnableSquidUrgencyPublic");
	if(!is_numeric($EnableSquidUrgencyPublic)){$EnableSquidUrgencyPublic=0;}
	
	if($EnableSquidUrgencyPublic==0){
		if(!$user->AsSquidAdministrator){
			$tpl=new templates();
			header("content-type: application/x-javascript");
			echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
			die();
		}	
	}
	
	if(isset($_GET["other-options"])){other_options();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["perfs"])){perfs();exit;}
	if(isset($_POST["SquidUrgency"])){SquidUrgency();exit;}
	if(isset($_POST["EnableSquidUrgencyPublic"])){EnableSquidUrgencyPublic();exit;}
	if(isset($_GET["justbutton"])){justbutton_js();exit;}
	if(isset($_GET["activedirectory"])){activedirectory_js();exit;}
	if(isset($_GET["eCAPClamavEmergency"])){eCAPClamavEmergency_js();exit;}
	if(isset($_GET["ParanoidBlockerEmergency"])){ParanoidBlockerEmergency_js();exit;}
	
	
	
	if(isset($_GET["ssl"])){ssl_js();exit;}
	if(isset($_GET["popup-ssl"])){ssl_popup();exit;}
	
	if(isset($_GET["ufdb"])){ufdb_js();exit;}
	if(isset($_GET["popup-ufdb"])){ufdb_popup();exit;}	
	
	if(isset($_GET["popup-justbutton"])){justbutton();exit;}
	if(isset($_GET["popup-activedirectory"])){activedirectory();exit;}
	if(isset($_GET["popup-eCAPClamavEmergency"])){eCAPClamavEmergency();exit;}
	if(isset($_GET["popup-ParanoidBlockerEmergency"])){ParanoidBlockerEmergency();exit;}
	
	
	js();

	
function js(){

	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{urgency_mode}");
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$html="
		jQuery(function(){
			jQuery('head').append('<link href=\"/css/styles_main.css\" rel=\"stylesheet\" type=\"text/css\" title=\"styles_main_css\"  />');
			jQuery('head').append('<link href=\"/ressources/templates/default/blurps.css\" rel=\"stylesheet\" type=\"text/css\" title=\"blurps_css\"/>');
			
			
		});

	
	
	YahooWin3('700','$page?popup=yes&justbutton={$_GET["justbutton"]}','$title');";
	echo $html;
}

function justbutton_js(){
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{urgency_mode}");
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	echo "YahooWin3Hide();YahooWin3('700','$page?popup-justbutton=yes','$title');";
	
}
function activedirectory_js(){
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{activedirectory_emergency_mode}");
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	echo "YahooWin3Hide();YahooWin3('700','$page?popup-activedirectory=yes','$title');";
}

function eCAPClamavEmergency_js(){
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{eCAPClamav_emergency_mode}");
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	echo "YahooWin3Hide();YahooWin3('700','$page?popup-eCAPClamavEmergency=yes','$title');";	
	
}
function ParanoidBlockerEmergency_js(){
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{paranoid_emergency_mode}");
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	echo "YahooWin3Hide();YahooWin3('700','$page?popup-ParanoidBlockerEmergency=yes','$title');";
}

function ssl_js(){
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{proxy_in_ssl_emergency_mode}");
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	echo "YahooWin3('700','$page?popup-ssl=yes','$title');";	
}
function ufdb_js(){
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{proxy_in_webfiltering_emergency_mode}");
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	echo "YahooWin3Hide();YahooWin3('700','$page?popup-ufdb=yes','$title');";
}



function justbutton(){
	$user=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$t=time();
	$EnableSquidUrgencyPublic=$sock->GET_INFO("EnableSquidUrgencyPublic");
	if(!is_numeric($EnableSquidUrgencyPublic)){$EnableSquidUrgencyPublic=0;}
	if(!$user->AsSquidAdministrator){return;}
	$SquidUrgency=$sock->GET_INFO("SquidUrgency");
	if(!is_numeric($SquidUrgency)){$SquidUrgency=0;}
	
	if(!$user->AsSquidAdministrator){echo FATAL_ERROR_SHOW_128('{ERROR_NO_PRIVS}');return;}
	
		echo $tpl->_ENGINE_parse_body("
				<center style='margin:20px' id='SQUID_URGENCY_FORM_ADM'>
				".button("{disable_emergency_mode}","Loadjs('squid.urgency.remove.progress.php')",32)."
				</center>");
				
	
	
	
	
}

function activedirectory(){
	$user=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();
	
	if(!$user->AsSquidAdministrator){echo FATAL_ERROR_SHOW_128('{ERROR_NO_PRIVS}');return;}
	echo $tpl->_ENGINE_parse_body("
		<center style='margin:20px' id='SQUID_URGENCY_FORM_ADM'>
		".button("{disable_emergency_mode}","Loadjs('squid.urgencyad.remove.progress.php')",32)."
	</center>");
	
	
	
}

function eCAPClamavEmergency(){
	$user=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();
	
	if(!$user->AsSquidAdministrator){echo FATAL_ERROR_SHOW_128('{ERROR_NO_PRIVS}');return;}
	echo $tpl->_ENGINE_parse_body("
		<center style='margin:20px' id='SQUID_URGENCY_FORM_ADM'>
		".button("{disable_emergency_mode}","Loadjs('squid.ecap.progress.php')",32)."
	</center>");
	
}

function ParanoidBlockerEmergency(){
	$user=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();
	
	if(!$user->AsSquidAdministrator){echo FATAL_ERROR_SHOW_128('{ERROR_NO_PRIVS}');return;}
	echo $tpl->_ENGINE_parse_body("
		<center style='margin:20px' id='SQUID_URGENCY_FORM_ADM'>
		".button("{disable_emergency_mode}","Loadjs('squid.paranoid.emergency.progress.php')",32)."
	</center>");	
	
}



function ssl_popup(){
	$user=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();
	
	if(!$user->AsSquidAdministrator){echo FATAL_ERROR_SHOW_128('{ERROR_NO_PRIVS}');return;}
	echo $tpl->_ENGINE_parse_body("
		<center style='margin:20px' id='SQUID_URGENCY_FORM_ADM'>
		".button("{disable_emergency_mode}","Loadjs('squid.urgencyssl.remove.progress.php')",32)."
	</center>");	
}

function ufdb_popup(){
	$user=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();
	
	if(!$user->AsSquidAdministrator){echo FATAL_ERROR_SHOW_128('{ERROR_NO_PRIVS}');return;}
	echo $tpl->_ENGINE_parse_body("
		<center style='margin:20px' id='SQUID_URGENCY_FORM_ADM'>
		".button("{disable_emergency_mode}","Loadjs('squid.urgencyssl.remove.progress.php?ufdb=yes')",32)."
	</center>");	
	
}

function SquidUrgency(){
	$sock=new sockets();
	$user=new usersMenus();
	$tpl=new templates();
	if(!$user->AsSquidAdministrator){
		$UrgenCyPass=$_POST["UrgenCyPass"];
		$UrgenCyPass2=$sock->GET_INFO("UrgenCyPass");
		if($UrgenCyPass<>$UrgenCyPass2){
			echo $tpl->javascript_parse_text("{bad_password}");
			return;
		}
	}
	
	
	$sock->SET_INFO("SquidUrgency", $_POST["SquidUrgency"]);
	$sock->getFrameWork("cmd.php?force-restart-squidonly=yes&ApplyConfToo=yes&force=yes");
	if($_POST["SquidUrgency"]==1){
		echo $tpl->javascript_parse_text("{SquidUrgency_save_explain}");
	}else{
		echo $tpl->javascript_parse_text("{SquidUrgency_nosave_explain}");
	}
	
}


function other_options(){
	$user=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$t=time();
	$EnableSquidUrgencyPublic=$sock->GET_INFO("EnableSquidUrgencyPublic");
	if(!is_numeric($EnableSquidUrgencyPublic)){$EnableSquidUrgencyPublic=0;}
	if(!$user->AsSquidAdministrator){return;}
	
	
	
	$html="
	<table style='width:100%;margin-top:20px'>
	<tbody>
		<tr>
			<td class=legend style='font-size:16px'>{enable_squid_urgency_access}:</td>
			<td style='font-size:16px'>". Field_checkbox("EnableSquidUrgencyPublic", 1,$EnableSquidUrgencyPublic,"EnableSquidUrgencyPublicCheck()")."</td>
			<td>". help_icon("{enable_squid_urgency_access_explain}")."</td>
		</tr>				
			
		<tr>
			<td class=legend style='font-size:16px'>{password}:</td>
			<td style='font-size:16px'>". Field_password("UrgenCyPass-$t",null,"style:font-size:18px")."<td>
			<td>&nbsp;</td>
		</tr>	
		</tr>
			<td align='right' colspan=3><hr>". button("{apply}","Save$t()",22)."</td>
		</tr>					
					
	</table>	

<script>
	var xSave$t=function (obj) {
		var tempvalue=obj.responseText;
		
	}	
	
	function Save$t(){
		var EnableSquidUrgencyPublic=0;
		var XHR = new XHRConnection();
		if(document.getElementById('EnableSquidUrgencyPublic').checked){EnableSquidUrgencyPublic=1;}
		XHR.appendData('EnableSquidUrgencyPublic',EnableSquidUrgencyPublic);
		XHR.appendData('UrgenCyPass',encodeURIComponent( document.getElementById('UrgenCyPass-$t').value ) );
		XHR.sendAndLoad('$page', 'POST',xSave$t);	
	}	

	function EnableSquidUrgencyPublicCheck(){
		var EnableSquidUrgencyPublic=0;
		if(document.getElementById('EnableSquidUrgencyPublic').checked){EnableSquidUrgencyPublic=1;}
		document.getElementById('UrgenCyPass-$t').disabled=true;
		if(EnableSquidUrgencyPublic==1){
			document.getElementById('UrgenCyPass-$t').disabled=false;
		}
	
	}
	EnableSquidUrgencyPublicCheck();
</script>
";			
	echo $tpl->_ENGINE_parse_body($html);
	
}



function popup(){
	$user=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$SquidUrgency=$sock->GET_INFO("SquidUrgency");
	if(!is_numeric($SquidUrgency)){$SquidUrgency=0;}
	
	$t=time();
	
	$SquidUrgency_admin="<div style='font-size:16px' class=explain>{squid_urgency_explain}</div>";
	
	if(!$user->AsSquidAdministrator){
		$askpassword="
			<tr>
				<td class=legend style='font-size:18px'>{password}:</td>
				<td style='font-size:16px'>". Field_password("UrgenCyPass-$t",null,"style:font-size:18px")."<td>
		</tr>		
		";
		$jsadd="location.reload();";
		
	}
	
	if($user->AsSquidAdministrator){
		if($SquidUrgency==1){
			
			$SquidUrgency_admin="<center style='margin:20px'>".button("{disable_emergency_mode}","Loadjs('squid.urgency.remove.progress.php')",26)."</center>";
			
		}
		
		
	}
	
	
	$html="
	<span id='SQUID_URGENCY_FORM_ADM'></span>
	<div id='$t'>
	
	
	
	$SquidUrgency_admin
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tbody>
	<tr>
		<td colspan=2>". Paragraphe_switch_img("{activate_squid_urgency}",
				"{enable_squid_urgency_access_explain}","SquidUrgency-$t",
				$SquidUrgency,null,590)."
		</td>
						
	$askpassword	
	</tr>
		<td align='right' colspan=2><hr>". button("{apply}","SaveUrg$t()",22)."</td>
	</tr>
	</table>	
		<div id='other-options-$t' style='width:100%;margin:0;padding:0;'></div>
	</div>
<script>
	var xSaveUrg$t=function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
		$jsadd
		if( document.getElementById('squid-status') ){
			LoadAjax('squid-status','squid.main.quicklinks.php?status=yes');
		}
		
		if( document.getElementById('main_dansguardian_mainrules') ){
			RefreshTab('main_dansguardian_mainrules');
		}
		if(document.getElementById('admin_perso_tabs-ID')){RefreshTab(document.getElementById('admin_perso_tabs-ID').value);}
		
	}	
	
	function SaveUrg$t(){
		var XHR = new XHRConnection();
		XHR.appendData('SquidUrgency',document.getElementById('SquidUrgency-$t').value);
		if( document.getElementById('UrgenCyPass-$t') ){
			XHR.appendData('UrgenCyPass',encodeURIComponent(document.getElementById('UrgenCyPass-$t').value));
		}
		
		
		XHR.sendAndLoad('$page', 'POST',xSaveUrg$t);	
	}	

	LoadAjax('other-options-$t','$page?other-options=yes',true);
	
</script>	
";
	echo $tpl->_ENGINE_parse_body($html);
}	
function EnableSquidUrgencyPublic(){
	$sock=new sockets();
	$sock->SET_INFO("EnableSquidUrgencyPublic", $_POST["EnableSquidUrgencyPublic"]);
	$UrgenCyPass=url_decode_special_tool($_POST["UrgenCyPass"]);
	$sock->SET_INFO("UrgenCyPass", $_POST["UrgenCyPass"]);
	
}
?>