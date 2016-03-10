<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	session_start();
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');

	$user=new usersMenus();
	if($user->AsDansGuardianAdministrator==false){
		$tpl=new templates();
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		
		die();exit();
	}
	
	if(isset($_GET["page"])){page();exit;}
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["PrivoxyEnabled"])){save();exit;}
tabs();


function tabs(){
	$fontsize=26;
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$sock=new sockets();
	$time=time();
	
	$array["page"]="{parameters}";
	$array["template"]="{error_page}";
	$array["whitelist"]="{whitelist}";
	
	$CountOfTabs=count($array);
	
	if($CountOfTabs>9){
		$fontsize="16";
	}
	
	if($CountOfTabs>10){
		$fontsize="14";
	}
	
	while (list ($num, $ligne) = each ($array) ){
		if($num=="whitelist"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"privoxy.whitelist.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
	
		}
		if($num=="template"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"privoxy.template.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		
		}	
		
	
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$time\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
	}
	
	echo build_artica_tabs($html, "main_privoxy_tabs",1493)."<script>LeftDesign('logs-white-256-opac20.png');</script>";
	
	
	}

function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$PrivoxyVersion=$sock->GET_INFO("PrivoxyVersion");
	
	$html="
	<div style='font-size:30px;margin-bottom:30px'>{APP_PRIVOXY} v$PrivoxyVersion</div>		
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td valign=top' style='width:440px;vertical-align:top'><div id='privoxy-status'></div></td>
		<td valign='top'><div id='privoxy-options'></div>
	</tr>
	</table>
	</div>

	<script>
		LoadAjax('privoxy-status','$page?status=yes');
		LoadAjax('privoxy-options','$page?popup=yes');
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
function status(){
	$tpl=new templates();
	$page=CurrentPageName();
	$script=null;
	$sock=new sockets();
	$sock->getFrameWork('privoxy.php?status=yes');
	$ini=new Bs_IniHandler("ressources/logs/web/privoxy.status");
	$status=DAEMON_STATUS_ROUND("APP_PRIVOXY",$ini,null,0);
	
	$html="$status<div style='margin-top:10px;text-align:right'>". imgtootltip("refresh-24.png","{refresh}","LoadAjax('privoxy-status','$page?status=yes');")."</div>";

	echo $tpl->_ENGINE_parse_body($html);

}



function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$PrivoxyEnabled=intval($sock->GET_INFO("PrivoxyEnabled"));
	$t=time();
	
	
	$p=Paragraphe_switch_img("{ENABLE_APP_PRIVOXY}","{privoxy_explain}","PrivoxyEnabled",$PrivoxyEnabled,null,970);

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
			Loadjs('privoxy.progress.php');
		}	
		
		function EnableClamavDaemonSave$t(){
			var XHR = new XHRConnection();
    		XHR.appendData('PrivoxyEnabled',document.getElementById('PrivoxyEnabled').value);
    		XHR.sendAndLoad('$page', 'POST',x_EnableClamavDaemonSave$t);
			
		}	
	
	
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function save(){
	$sock=new sockets();
	
	if($_POST["PrivoxyEnabled"]==1){
		$sock->SET_INFO("PrivoxyEnabled",1);
	}else{
		$sock->SET_INFO("PrivoxyEnabled",0);
	}
}
