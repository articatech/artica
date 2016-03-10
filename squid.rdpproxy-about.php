<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.tcpip.inc');
	include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
	include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
	include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
	include_once(dirname(__FILE__) . "/ressources/class.pdns.inc");
	include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	

page();	
	
function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	
	$about=$tpl->_ENGINE_parse_body("{wallix_redemption}");
	
	
	$about=str_replace("%ChristopheGrosjean", "<a href=\"https://us.linkedin.com/pub/christophe-grosjean/8/5b6/8a8\" style='text-decoration:underline;font-weight:bold'>Christophe Grosjean</a>", $about);
	$about=str_replace("%Wallix","<a href=\"http://www.wallix.com\" style='text-decoration:underline;font-weight:bold'>Wallix</a>",$about);

	$html="
	<div style='font-size:30px;margin-bottom:30px'>{about2} Wallix proxyRDP ReDemPtion</div>		
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td valign='top' style='width:280px'>
			<center>
				<img src='img/logowallix.png'>
			</center>
		</td>
		<td valign='top'>
		<div style='margin:30px;font-size:18px'>$about</div>
		</td>
		</tr>
	</table>
	</div>";
	
	echo $tpl->_ENGINE_parse_body($html);
		
	
	
	
}
