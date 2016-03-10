<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
if(isset($_GET["VERBOSE"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_GET["VERBOSE"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.artica.graphs.inc');
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}
if(isset($_GET["popup"])){popup();exit;}

js();


function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{update_events}");
	
	echo "
	YahooWinBrowseHide();
	RTMMail('1200','$page?popup=yes','$title');

";



}

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$restart=null;
	$t=time();
	@unlink("/usr/share/artica-postfix/ressources/logs/web/artica-ufdb.log");
	$sock->getFrameWork("artica.php?webfiltering-events=yes");
	$f=explode("\n",@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/artica-ufdb.log"));
	krsort($f);
	$html="
	
	<p>&nbsp;</p>
	<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:98%;height:446px;border:5px solid #8E8E8E;
	overflow:auto;font-size:11px' id='text-$t'>".@implode("\n", $f)."</textarea>

	
";
	@unlink("/usr/share/artica-postfix/ressources/logs/web/artica-ufdb.log");
	echo $html;
}
