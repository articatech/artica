<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	session_start();
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');

	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		
		die();exit();
	}
	
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["ClamavMilterEnabled"])){save();exit;}
page();



function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$ClamAVMilterVersion=$sock->GET_INFO("ClamAVMilterVersion");
	
	$html="
	<div style='font-size:30px;margin-bottom:30px'>{APP_CLAMAV_MILTER} v$ClamAVMilterVersion</div>		
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td valign=top' style='width:440px'><div id='milter-clamav-status'></div></td>
		<td valign='top'><div id='milter-clamav-options'></div>
	</tr>
	</table>
	</div>

	<script>
		LoadAjax('milter-clamav-status','$page?status=yes');
		LoadAjax('milter-clamav-options','$page?popup=yes');
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
function status(){
	$tpl=new templates();
	$page=CurrentPageName();
	$script=null;
	$sock=new sockets();
	$sock->getFrameWork('cmd.php?clamav-status=yes');
	$ini=new Bs_IniHandler("ressources/logs/web/clamav.status");
	$status=DAEMON_STATUS_ROUND("CLAMAV",$ini,null,0);
	$status_clamd=DAEMON_STATUS_ROUND("CLAMAV_MILTER",$ini,null,0);
	$html="<div style='width:100%'>$status_clamd<br>$status</div>";

	echo $tpl->_ENGINE_parse_body($html);

}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$ClamavMilterEnabled=intval($sock->GET_INFO("ClamavMilterEnabled"));
	$t=time();
	
	
	$p=Paragraphe_switch_img("{ENABLE_CLAMAV}","{APP_CLAMAV_MILTER_TEXT}<br>{APP_CLAMAV_MILTER_DEFS}","ClamavMilterEnabled",$ClamavMilterEnabled,null,970);

	$html="
	<table style='width:100%'>
		<tr>
			<td colspan=2>$p</td>
		</tr>
		<tr>
			<td colspan=2><div style='width:100%;text-align:right'>". button("{apply}","EnableClamavDaemonSave$t()","40px")."</div></td>
		</tr>
	</table>
	<script>
	var x_EnableClamavDaemonSave$t=function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);return;}			
			Loadjs('postfix.clamav-milter.progress.php');
		}	
		
		function EnableClamavDaemonSave$t(){
			var XHR = new XHRConnection();
    		XHR.appendData('ClamavMilterEnabled',document.getElementById('ClamavMilterEnabled').value);
    		XHR.sendAndLoad('$page', 'POST',x_EnableClamavDaemonSave$t);
			
		}	
	
	
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function save(){
	$sock=new sockets();
	
	if($_POST["ClamavMilterEnabled"]==1){
		$sock->SET_INFO("EnableClamavDaemon",1);
		$sock->SET_INFO("ClamavMilterEnabled",1);
	}else{
		$sock->SET_INFO("ClamavMilterEnabled",0);
	}
}
