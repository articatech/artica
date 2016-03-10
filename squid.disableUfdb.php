<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_POST["Disable"])){Disable();exit;}


js();


function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$ask_disable=$tpl->javascript_parse_text("{ask_disable_ufdbguard}");
	$t=time();
	$html="
		var xDisable$t= function (obj) {
			var res=obj.responseText;
			if (res.length>3){alert(res);}
			if(document.getElementById('rules-toolbox-left')){
				LoadAjaxTiny('rules-toolbox-left','dansguardian2.mainrules.php?rules-toolbox-left=yes');
			}
			if(document.getElementById('ufdb-main-toolbox-status')){
				LoadAjaxTiny('ufdb-main-toolbox-status','dansguardian2.mainrules.php?rules-toolbox-left=yes');
			}
			Loadjs('squid.compile.progress.php');
			LoadMainDashProxy();
			CacheOff();
			
		}				
	
	
		
		function Disable$t(){
			if(!confirm('$ask_disable')){return;}
			var XHR = new XHRConnection();
		    XHR.appendData('Disable','yes');
		   	XHR.sendAndLoad('$page', 'POST',xDisable$t);			
		
		}
			
		Disable$t();	
	";
	echo $html;
}

function Disable(){
	$sock=new sockets();
	$sock->SET_INFO("EnableUfdbGuard", 0);
	$sock->SET_INFO("EnableUfdbGuard2", 0);
	$sock->getFrameWork("cmd.php?restart-artica-status=yes");
	
}

