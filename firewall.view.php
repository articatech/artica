<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	$GLOBALS["LOGFILE"]="/usr/share/artica-postfix/ressources/logs/web/exec.virtuals-ip.php.html";
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	

	
	if(isset($_GET["popup"])){popup();exit;}
	
js();

function js(){
	$page=CurrentPageName();
	$t=$_GET["t"];
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{firewall_rules}");
	$sock=new sockets();
	$sock->getFrameWork("network.php?iptables-save=yes");
	echo "YahooWin3('998','$page?popup=yes&t=$t','$title');";
	
	
}

function popup(){
	
	$data=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/iptables.save.html");
	@unlink("/usr/share/artica-postfix/ressources/logs/web/iptables.save.html");
	echo "<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:98%;height:446px;border:5px solid #8E8E8E;
	overflow:auto;font-size:11px' id='procedure3-text'>$data</textarea>";
	
}

