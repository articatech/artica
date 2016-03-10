<?php
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.status.inc');
include_once('ressources/class.artica.graphs.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.influx.inc');
$users=new usersMenus();
$tpl=new templates();
if(!$users->AsSystemAdministrator){
	echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."')";
	die();
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["ChangePostGresSQLDir"])){ChangePostGresSQLDir();exit;}
popup_js();


function popup_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$title=$tpl->javascript_parse_text("{database_storage_path}");
	$html="YahooWin3(681,'$page?popup=yes','$title');";
	echo $html;
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$sock=new sockets();
	$sock->getFrameWork("postgres.php?PostGresSQLDatabaseDirectory=yes");
	$PostGresSQLDatabaseDirectory=$sock->GET_INFO("PostGresSQLDatabaseDirectory");
	
	$html="<div style=width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td valign='middle' style='font-size:18px'>{database_storage_path}:</td>
		<td valign='middle'>". Field_text("database-dir-$t","$PostGresSQLDatabaseDirectory","font-size:18px;width:98%")."</td>
		<td valign='middle'>". button_browse("database-dir-$t",18)."</td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","Save$t()",32)."</td>
	</tR>
</table>
</div>
<script>
var xSave$t= function (obj) {	
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	YahooWin3Hide();
	Loadjs('postgres.changedir.progress.php');
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ChangePostGresSQLDir', encodeURIComponent(document.getElementById('database-dir-$t').value));
	XHR.sendAndLoad('$page', 'POST',xSave$t);  

}
</script>			
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function ChangePostGresSQLDir(){
	$sock=new sockets();
	$_POST["ChangePostGresSQLDir"]=url_decode_special_tool($_POST["ChangePostGresSQLDir"]);
	$sock->SET_INFO("ChangePostGresSQLDir", $_POST["ChangePostGresSQLDir"]);
	
}