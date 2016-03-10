<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	include_once('ressources/class.compile.ufdbguard.inc');
	
	if(isset($_POST["servername_squidguard"])){Save();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	js();
	
	
function js(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{webpage_deny_url}");
	header("content-type: application/x-javascript");
	$html="YahooWin5('990','$page?popup=yes','$title');";
	echo $html;	
	
	
}	
	
function popup(){
	$page=CurrentPageName();
	$sock=new sockets();
	$users=new usersMenus();
	$t=time();
	$SquidGuardIPWeb=$sock->GET_INFO("SquidGuardIPWeb");
	$fulluri=$sock->GET_INFO("SquidGuardIPWeb");
	$SquidGuardServerName=$sock->GET_INFO("SquidGuardServerName");
	
	if(preg_match("#\/(.+?):([0-9]+)\/#",$SquidGuardIPWeb,$re)){$SquidGuardIPWeb="{$re[1]}:{$re[2]}";}
	
	if(preg_match("#(.+?):([0-9]+)#",$SquidGuardIPWeb,$re)){
		$SquidGuardServerName=$re[1];
		$SquidGuardApachePort=$re[2];
	}
	
	if($SquidGuardServerName=="/"){$SquidGuardServerName=null;}
	if(preg_match("#(.+?)\/#", $SquidGuardServerName)){$SquidGuardServerName=$re[1];}
	if(preg_match("#^\/(.+)#", $SquidGuardServerName)){$SquidGuardServerName=$re[1];}
	$SquidGuardServerName=str_replace("/", "", $SquidGuardServerName);
	
	$html="<div style='width:98%' class=form>
	<div class=explain style='font-size:18px'>{servername_squidguard_explain}</div>
	<table style='width:100%'>
	". Field_text_table("servername_squidguard-$t", "{hostname}",$SquidGuardServerName,35,null,450).
	Field_button_table_autonome("{apply}", "Save$t()",35)."</table>	
	</div>
<script>
var xSave$t=function(obj){
		YahooWin5Hide();
		RefreshTab('main_dansguardian_mainrules');
	 	Loadjs('dansguardian2.compile.php');
}

function Save$t(){
     var XHR = new XHRConnection();
	 XHR.appendData('servername_squidguard',document.getElementById('servername_squidguard-$t').value);
     XHR.sendAndLoad('$page', 'POST',xSave$t);     	
}
</script>			
";
	
$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}


function Save(){
	$sock=new sockets();
	$SquidGuardApachePort=intval($sock->GET_INFO("SquidGuardApachePort"));
	$SquidGuardApacheSSLPort=intval($sock->GET_INFO("SquidGuardApacheSSLPort"));
	
	$_POST["servername_squidguard"]=str_replace("http://", "", $_POST["servername_squidguard"]);
	$_POST["servername_squidguard"]=str_replace("https://", "", $_POST["servername_squidguard"]);
	if(preg_match("#^(.+?)\/#",$_POST["servername_squidguard"],$re)){$_POST["servername_squidguard"]="{$re[1]}";}
	
	if(preg_match("#(.+?):([0-9]+)#",$_POST["servername_squidguard"],$re)){
		$_POST["servername_squidguard"]="{$re[1]}";
	}
	
	
	if($SquidGuardApacheSSLPort==0){$SquidGuardApacheSSLPort=9025;}
	if($SquidGuardApachePort==0){$SquidGuardApachePort=9020;}
	
	$sock->SET_INFO("SquidGuardServerName", $_POST["servername_squidguard"]);
	$SquidGuardIPWeb="http://".$_POST["servername_squidguard"].":$SquidGuardApachePort/ufdbguardd.php";
	$SquidGuardIPWebSSL="https://".$_POST["servername_squidguard"].":$SquidGuardApacheSSLPort/ufdbguardd.php";
	$sock->SET_INFO("SquidGuardIPWeb",$SquidGuardIPWeb);
	$sock->SET_INFO("SquidGuardIPWebSSL",$SquidGuardIPWebSSL);
}