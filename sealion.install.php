<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');


$users=new usersMenus();
if(!$users->AsSystemAdministrator){
	$tpl=new templates();
	$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	echo "alert('$error')";
	die();
}

if(isset($_POST["SealLionMD5"])){SealLionMD5();exit;}
if(isset($_GET["popup"])){popup();exit;}

js();



function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$title=$tpl->javascript_parse_text("{APP_SEALION_AGENT}");
	$html="YahooWin2(990,'$page?popup=yes','$title')";
	echo $html;

}

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$sock=new sockets();
	$SealLionMD5=$sock->GET_INFO("SealLionMD5");
	
	
	$html="<div class=explain style='font-size:18px'>{APP_SEALION_AGENT_INSTALL_EXPLAIN}</div>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:24px'>UUID:</td>
		<td class=legend style='font-size:24px'>". Field_text("SealLionMD5",$SealLionMD5,"font-size:24px;width:95%")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{start_install}","Save$t()",40)."</td>
	</tR>
	</table>		
<script>
var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){ alert(res); return; }
	YahooWin2Hide();
	Loadjs('sealion.install.progress.php');
	
}


function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('SealLionMD5',document.getElementById('SealLionMD5').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function SealLionMD5(){
	
	$sock=new sockets();
	$sock->SET_INFO("SealLionMD5",$_POST["SealLionMD5"]);
	
	
}
