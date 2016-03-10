<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	
	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.squid.reverse.inc');
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "<p class=text-error>". $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}")."</p>";
		die();exit();
	}
	if(isset($_GET["website-script-tab"])){websites_script_tab();exit;}
	if(isset($_GET["website-script"])){websites_script();exit;}
	if(isset($_POST["nginxconf"])){websites_script_nginxconf();exit;}



websites_script_js();

function websites_script_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$servername=$_GET["servername"];
	$add="popup-webserver";

	$title="{new_website}";
	if($servername<>null){
		$title=$servername;
		echo "YahooWin6(1000,'$page?website-script-tab=yes&servername=$servername','$title')";
	}
}

function websites_script_tab(){
	$servername=$_GET["servername"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();

	
	$array["conf"]="{configuration}";
	


	$fontsize=18;
	while (list ($num, $ligne) = each ($array) ){

		if($num=="conf"){
			$tab[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?website-script=yes&servername=$servername\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		}

		if($num=="events"){
			$tab[]= $tpl->_ENGINE_parse_body("<li><a href=\"nginx.access-events.php?servername=$servername\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		}

		if($num=="destinations"){
			$tab[]= $tpl->_ENGINE_parse_body("<li><a href=\"nginx.destinations.php?ID=$ID\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		}


		$tab[]="<li style='font-size:{$fontsize}px'><a href=\"$page?$num=yes&ID=$ID\"><span >$ligne</span></a></li>\n";
			
	}



	$t=time();
	//

	echo build_artica_tabs($tab, "main_script_nginx_tabs");



}

function websites_script(){
	$sock=new sockets();
	$tpl=new templates();
	$t=time();
	$page=CurrentPageName();
	$servername=$_GET["servername"];
	$q=new mysql_squid_builder();
	if(!$q->FIELD_EXISTS("reverse_www", "DenyConf")){$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD `DenyConf` smallint(1) NOT NULL DEFAULT 0");if(!$q->ok){echo $q->mysql_error_html();}}
	$sock=new sockets();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_www WHERE servername='$servername'"));
	$datasR=$ligne["ConfBuilded"];


	$html="
	<div id='$t'></div>
	<div style='width:98%' class=form>
	<table>
	<tr>
	<td colspan=2 style='font-size:26px;padding-bottom:10px'>$servername</td>
	</tr>
	<tr>
	<td class=legend style='font-size:18px'>". $tpl->_ENGINE_parse_body("{deny_artica_to_write_config}")."</td>
	<td>". Field_checkbox_design("DenyConf$t", 1,$ligne["DenyConf"],"DenyConfSave$t()")."</td>
	</tr>
	</table>
	<textarea
		style='width:95%;height:550px;overflow:auto;border:5px solid #CCCCCC;font-size:14px;font-weight:bold;padding:3px'
		id='SQUID_CONTENT-$t'>$datasR</textarea>
		<center><hr>". $tpl->_ENGINE_parse_body(button("{apply}", "SaveUserConfFile$t()",22))."</center>
	</div>
	<script>
var xDenyConfSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
}

function DenyConfSave$t(){
	var XHR = new XHRConnection();
	var DenyConfSave=0;
	if(document.getElementById('DenyConf$t').checked){DenyConfSave=1;}
	XHR.appendData('DenyConfSave', DenyConfSave);
	XHR.appendData('servername', '$servername');
	XHR.sendAndLoad('$page', 'POST',xDenyConfSave$t);
}

var xSaveUserConfFile$t= function (obj) {
	var results=obj.responseText;
	document.getElementById('$t').innerHTML='';
	if(results.length>3){alert(results);return;}
}

function SaveUserConfFile$t(){
	var XHR = new XHRConnection();
	XHR.appendData('servername', '$servername');
	XHR.appendData('nginxconf', encodeURIComponent(document.getElementById('SQUID_CONTENT-$t').value));
	AnimateDiv('$t');
	XHR.sendAndLoad('$page', 'POST',xSaveUserConfFile$t);
}
</script>
		";
	echo $html;

}


function websites_script_DenyConfSave(){
	$q=new mysql_squid_builder();
	if(!$q->FIELD_EXISTS("reverse_www", "DenyConf")){$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD `DenyConf` smallint(1) NOT NULL DEFAULT 0");if(!$q->ok){echo $q->mysql_error_html();}}
	$q->QUERY_SQL("UPDATE reverse_www SET `DenyConf`='{$_POST["DenyConfSave"]}' WHERE `servername`='{$_POST["servername"]}'");
	if(!$q->ok){echo $q->mysql_error;}
}
function websites_script_nginxconf(){
	$nginxconf=url_decode_special_tool($_POST["nginxconf"]);
	$servername=urlencode($_POST["servername"]);
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("UPDATE reverse_www SET `ConfBuilded`='".mysql_escape_string2($nginxconf)."' WHERE `servername`='{$_POST["servername"]}'");
	$sock=new sockets();
	echo base64_decode($sock->getFrameWork("nginx.php?replic-conf=$servername"));


}