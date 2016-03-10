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
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "<p class=text-error>". $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}")."</p>";
		die();exit();
	}
	
	if(isset($_GET["EnableNginx"])){EnableNginx_js();exit;}
	
	

tabs();


function EnableNginx_js(){
	header("content-type: application/x-javascript");
	echo "Loadjs('nginx.enable.progress.php')";
	
}



function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	
	$sock=new sockets();
	$EnableNginx=intval($sock->GET_INFO("EnableNginx"));
	$EnableNginxMail=intval($sock->GET_INFO("EnableNginxMail"));
	if($EnableNginx==0){
		
		echo FATAL_ERROR_SHOW_128("
		<center style='margin:50px'>". button("{enable_reverse_proxy_service}","Loadjs('$page?EnableNginx=yes')",50)."</center>");
		return;
		
	}

	$array["status"]="{status}";
	$array["websites"]="{websites}";
	$array["destinations"]='{destinations}';
	$array["caches"]='{caches}';
	if($EnableNginxMail==1){
		$array["mail"]='{mail}';
	}

	$array["events"]='{events}';
	$array["watchdog"]="{watchdog}";
	
	$array["backup"]='{backup_restore}';
	$array["options"]='{options}';
	


	$fontsize=22;
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="status"){
			$tab[]= $tpl->_ENGINE_parse_body("<li><a href=\"nginx.satus.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="watchdog"){
			$tab[]= $tpl->_ENGINE_parse_body("<li><a href=\"nginx.watchdog-events.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		}		

		if($num=="websites"){
			$tab[]= $tpl->_ENGINE_parse_body("<li><a href=\"nginx.www.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		
		if($num=="mail"){
			$tab[]= $tpl->_ENGINE_parse_body("<li><a href=\"nginx.mail.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		}		
		
		if($num=="events"){
			$tab[]= $tpl->_ENGINE_parse_body("<li><a href=\"nginx.events.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		}		
		
		if($num=="destinations"){
			$tab[]= $tpl->_ENGINE_parse_body("<li><a href=\"nginx.destinations.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="caches"){
			$tab[]= $tpl->_ENGINE_parse_body("<li><a href=\"nginx.caches.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		}		
		
		
		if($num=="backup"){
			$tab[]= $tpl->_ENGINE_parse_body("<li><a href=\"nginx.backup.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="options"){
			$tab[]= $tpl->_ENGINE_parse_body("<li><a href=\"nginx.options.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		}		

		$tab[]="<li style='font-size:{$fontsize}px'><a href=\"$page?$num=yes\"><span >$ligne</span></a></li>\n";
			
	}



	$t=time();
	//

	echo build_artica_tabs($tab, "main_artica_nginx",1490)."<script>LeftDesign('reverse-proxy-256-white.png');</script>";

}