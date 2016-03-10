<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	$usersmenus=new usersMenus();
	if(!$usersmenus->AsDansGuardianAdministrator){
		$tpl=new templates();
		$alert=$tpl->javascript_parse_text('{ERROR_NO_PRIVS}');
		echo "alert('$alert');";
		die();	
	}
	
	if(isset($_POST["aclid"])){save();exit;}
	
js();

function js(){
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$aclid=$_GET["aclid"];
	$t=time();
	$title=$tpl->javascript_parse_text("{use_parent_proxy}");
	$page=CurrentPageName();
	
echo "function Start$t(){
	YahooWin3('1000','squid.parent.proxy.php?popup=yes&browser=yes&callback=choose$t','$title');
}

var xchoose$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	if(document.getElementById('ACL_ID_MAIN_TABLE')){
		$('#'+document.getElementById('ACL_ID_MAIN_TABLE').value).flexReload();
	}
	YahooWin3Hide();
	ExecuteByClassName('SearchFunction');
}	

function choose$t(ID){
	var XHR = new XHRConnection();
	XHR.appendData('aclid', '$aclid');
	XHR.appendData('parentid', ID);      
	XHR.sendAndLoad('$page', 'POST',xchoose$t);  
}



Start$t()";	
	
	
	
	
	
}

function save(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("UPDATE webfilters_sqaclaccess SET httpaccess_data='{$_POST["parentid"]}' WHERE aclid='{$_POST["aclid"]}' AND httpaccess='cache_parent'");
	if(!$q->ok){echo $q->mysql_error;}
	
}



