<?php
	if(posix_getuid()==0){die();}
	session_start();
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.haproxy.inc');
	
	
	
	
	$user=new usersMenus();
	if($user->AsSystemAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_POST["http-use-proxy-header"])){save();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	
js();	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title="{$_GET["servicename"]}&raquo;$new_backend";
	echo "YahooWin6(550,'$page?popup=yes&servicename={$_GET["servicename"]}','$title')";
	
}

function popup(){
	
	$users=new usersMenus();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$servicename=$_GET["servicename"];	
	$hap=new haproxy_multi($servicename);
	
	if(!is_numeric($hap->MainConfig["http-use-proxy-header"])){$hap->MainConfig["http-use-proxy-header"]=1;}
	if(!is_numeric($hap->MainConfig["forwardfor"])){$hap->MainConfig["forwardfor"]=1;}
	if(!is_numeric($hap->MainConfig["originalto"])){$hap->MainConfig["originalto"]=1;}
	
	$html="
	
	<div id='$t-defaults' style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:20px'>{http_use_proxy_header}:</td>
		<td>". Field_checkbox_design("http-use-proxy-header-$t", 1,$hap->MainConfig["http-use-proxy-header"])."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:20px'>{xforwardfor}:</td>
		<td>". Field_checkbox_design("forwardfor-$t", 1,$hap->MainConfig["forwardfor"])."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:20px'>{xoriginalto}:</td>
		<td>". Field_checkbox_design("originalto-$t", 1,$hap->MainConfig["originalto"])."</td>
	</tr>		
	
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","SaveHTTPOptions$t()",'30px')."</td>
	</tr>
	
	</table>
	</div>
	<script>
		var x_SaveHTTPOptions$t=function (obj) {
		    var servicename='$servicename';
			var results=obj.responseText;
			document.getElementById('$t-defaults').innerHTML='';
			YahooWin6Hide();
		}	
	
	
		function SaveHTTPOptions$t(){
			var XHR = new XHRConnection();
			XHR.appendData('servicename','$servicename');
			if( document.getElementById('http-use-proxy-header-$t').checked){XHR.appendData('http-use-proxy-header',1);}else{XHR.appendData('http-use-proxy-header',0);}
			if( document.getElementById('forwardfor-$t').checked){XHR.appendData('forwardfor',1);}else{XHR.appendData('forwardfor',0);}
			if( document.getElementById('originalto-$t').checked){XHR.appendData('originalto',1);}else{XHR.appendData('originalto',0);}
			AnimateDiv('$t-defaults');
    		XHR.sendAndLoad('$page', 'POST',x_SaveHTTPOptions$t);
    		
    	}
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}


function save(){
	$hap=new haproxy_multi($_POST["servicename"]);
	$hap->MainConfig["http-use-proxy-header"]=$_POST["http-use-proxy-header"];
	$hap->MainConfig["forwardfor"]=$_POST["forwardfor"];
	$hap->MainConfig["originalto"]=$_POST["originalto"];
	$hap->save();
	
}


