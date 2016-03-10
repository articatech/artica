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
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.squid.reverse.inc');
	include_once('ressources/class.nginx.interface-tools.php');
	
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "<p class=text-error>". $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}")."</p>";
		die();exit();
	}
	
	if(isset($_POST["NginxBehindLB"])){Save();exit;}
	
	
page();



function page(){
	$sock=new sockets();
	$tpl=new templates();
	$t=time();
	$page=CurrentPageName();
	$NginxBehindLB=intval($sock->GET_INFO("NginxBehindLB"));
	$NginxLBIpaddr=$sock->GET_INFO("NginxLBIpaddr");
	$html="<div style='font-size:40px;margin-bottom:30px'>{options}</div>
	
	<div style='width:98%' class=form>	
	<div style='font-size:30px;margin-bottom:20px'>Load-balancer</div>	
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{enable}:</td>
		<td>". Field_checkbox_design("NginxBehindLB", 1,$NginxBehindLB,"Check$t()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{lb_ipaddr}:</td>
		<td>". field_ipv4("NginxLBIpaddr", $NginxLBIpaddr,"font-size:22px")."</td>
	</tr>				
	<tr>
		<td colspan=2 align='right'>". button("{apply}","Submit$t()",32)."</td>
	</tr>
	</table>
	</div>
	<script>
	var xSubmit$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		Loadjs('nginx.restart.progress.php');
	}
	
	
	function Submit$t(){
		var XHR = new XHRConnection();
		var NginxBehindLB=0;
		if(document.getElementById('NginxBehindLB').checked){NginxBehindLB=1;}
		XHR.appendData('NginxBehindLB',NginxBehindLB);
		XHR.appendData('NginxLBIpaddr',document.getElementById('NginxLBIpaddr').value);
		XHR.sendAndLoad('$page', 'POST',xSubmit$t);
	}
	
	function Check$t(){
		
		document.getElementById('NginxLBIpaddr').disabled=true;
		var NginxBehindLB=0;
		if(document.getElementById('NginxBehindLB').checked){NginxBehindLB=1;}
		if(NginxBehindLB==1){
			document.getElementById('NginxLBIpaddr').disabled=false;
		}
	}
	Check$t();
	</script>			
			
	";
	
echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function Save(){
	$sock=new sockets();
	while (list ($key, $value) = each ($_POST) ){
		$sock->SET_INFO($key, $value);
	}
}
