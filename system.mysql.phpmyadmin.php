<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',1);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.httpd.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.os.system.inc');
	include_once('ressources/class.mysql-server.inc');
	include_once('ressources/class.mysql-multi.inc');
	
	
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){die("no privs");}
	
page();


function page(){
	$sock=new sockets();
	$tpl=new templates();
	
	$IsInstalled=trim($sock->getFrameWork("system.php?phpmyadmin-installed=yes"));
	
	if($IsInstalled<>"TRUE"){
		$button=button("{reinstall_software}","Loadjs('system.mysql.phpmyadmin.install.php')",36);
		echo FATAL_ERROR_SHOW_128("<span style='font-size:26px'>{ERROR_SERVICE_NOT_INSTALLED}</span><center style='margin:20px'>$button</center>");
		
	}
	
	
	
	$version=trim($sock->getFrameWork("system.php?phpmyadpmin-version=yes"));
	
	$html="
	<div style='width:100%;text-align:center'>
	<center>
	<center style='width:70%;margin:30px' class=form>
	<div style='font-size:26px'>PHPMyAdmin v.$version</div>
	<p>&nbsp;</p>
	". button("PHPMyAdmin Front-end","PopUpFull('/mysql',2048,1024);",40)."<p>&nbsp;</p></center></center></div>";
		
			
			
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}
