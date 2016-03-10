<?php

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.artica.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.system.network.inc');
$usersmenus=new usersMenus();
if($usersmenus->AsArticaAdministrator==false){
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	die();
}

if(isset($_GET["popup"])){popup();exit;}
js();


function js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	echo "YahooWin5('990','$page?popup=yes','WhatsNew')";
	
	
}


function popup(){
	
	$VER=@file_get_contents("VERSION");
	
	$FILE="/usr/share/artica-postfix/ressources/logs/web/$VER.txt";
	
	echo "<textarea style='margin-top:5px;font-family:Courier New;
font-weight:bold;width:98%;height:446px;border:5px solid #8E8E8E;
overflow:auto;font-size:11px' id='text-$t'>".@file_get_contents($FILE)."</textarea>";
	
	
	
}