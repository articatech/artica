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
	echo FATAL_ERROR_SHOW_128($error);
	die();
}

if(isset($_GET["sealion-agent-status"])){service_status();exit();}


page();


function page(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$sock->getFrameWork("ssealion.php?version=yes");
	$version=$sock->GET_INFO("SealionAgentVersion");
	$SealLionMD5=$sock->GET_INFO("SealLionMD5");
	
	$html="
		<div style='font-size:35px;'>{APP_SEALION_AGENT} v$version</div>
		<div style='font-size:18px;width:100%;margin-bottom:20px;text-align:right'><i>UUID:$SealLionMD5</i></div>
		<div style='width:98%' class=form>
		<table style='width:100%'>	
			<tr>
				<td valign='top' style='width:400px'>
					<div id='sealion-agent-status'></div>
				</td>
				<td valign='top'>
					<center style='margin:50px'>". button("{uninstall}", "Loadjs('sealion.uninstall.progress.php');",40)."</center>
				</td>
			</tr>
			</table>
		</div>
<script>
	LoadAjax('sealion-agent-status','$page?sealion-agent-status=yes');
</script>

							
";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function service_status(){
	
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$sock->getFrameWork("sealion.php?service-status=yes");
	$ini=new Bs_IniHandler();
	$ini->loadFile("/usr/share/artica-postfix/ressources/logs/APP_SEALION_AGENT.status");
	$status=DAEMON_STATUS_ROUND("APP_SEALION_AGENT", $ini);
	$html="$status<div style='text-align:right;height:40px;'>". imgtootltip("refresh-32.png","{refresh}","LoadAjax('sealion-agent-status','$page?sealion-agent-status=yes');","right")."</div>";
	echo $tpl->_ENGINE_parse_body($html);	
	
}


