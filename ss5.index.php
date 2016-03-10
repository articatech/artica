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
	include_once('ressources/class.os.system.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["services-ss5-status"])){status();exit;}
	if(isset($_POST["EnableSS5"])){EnableSS5();exit;}

tabs();
function tabs(){
	$tpl=new templates();
	$array["index"]='{parameters}';
	$array["rules"]='{rules}';
	$array["transparent"]='{transparent_rules}';
	$array["events"]='{events}';
	
	//$array["plugins"]='{squid_plugins}';

	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();

	$style="style='font-size:22px'";
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		if($num=="index"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"ss5.php\" $style><span>$ligne</span></a></li>\n");
			continue;
		}
		if($num=="events"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"ss5.events.php\" $style><span>$ligne</span></a></li>\n");
			continue;
		
		}
		
		if($num=="rules"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"ss5.rules.php\" $style><span>$ligne</span></a></li>\n");
			continue;
		}
		if($num=="transparent"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"ss5.transparent.php\" $style><span>$ligne</span></a></li>\n");
			continue;
		}
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\" $style><span>$ligne</span></a></li>\n");
	}
	echo build_artica_tabs($html, "ss5_main",1490);



}

