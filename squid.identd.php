<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
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
	include_once('ressources/class.ccurl.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	include_once('ressources/class.system.nics.inc');
	include_once('ressources/class.resolv.conf.inc');

	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "<H1>". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."</H1>";
		die();exit();
	}
	
	if(isset($_GET["status"])){status();exit;}
	if(isset($_POST["SquidEnableIdentdService"])){SquidEnableIdentdService();exit;}
	
	
	
tabs();


function status(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$SquidEnableIdentdService=intval($sock->GET_INFO("SquidEnableIdentdService"));
	$SquidEnableIdentdServiceOnly=intval($sock->GET_INFO("SquidEnableIdentdServiceOnly"));
	$SquidEnableIdentTimeout=intval($sock->GET_INFO("SquidEnableIdentTimeout"));
	if($SquidEnableIdentTimeout==0){$SquidEnableIdentTimeout=3;}
	
	$p=Paragraphe_switch_img("{activate_identd_lookup}", "{squid_identd_daemon_explain}","SquidEnableIdentdService-$t",$SquidEnableIdentdService,null,780);
	$p2=Paragraphe_switch_img("{allow_only_identified_members}", "{allow_only_identified_members_explain}",
	"SquidEnableIdentdServiceOnly-$t",$SquidEnableIdentdServiceOnly,null,780);
	
	
	$html="<div style='padding:30px;width:92%' class=form>
	$p
	$p2
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{timeout2} ({seconds}):</td>
		<td>". Field_text("SquidEnableIdentTimeout-$t",$SquidEnableIdentTimeout,"font-size:18px;width:110px")."</td>
	</tr>
	</table>
	
	<div style='margin-top:15px;text-align:right'><hr>". button("{apply}","Submit$t();",36)."</div>
	
		<div style='margin-top:20px;font-size:32px'>{microsoft_windows_softwares}</div>
		<ul style='margin-top:20px'>
			<li style='font-size:18px'><a href='http://articatech.net/download/retina-scan-0.3.0.exe' style='text-decoration:underline'>Retina Scan inetd</a></li>
			<li style='font-size:18px'><a href='http://rndware.info/products/windows-ident-server.html' style='text-decoration:underline'>rndware Windows Ident Server</a></li>
		</ul>
	
	</div>		
<script>
	var xSubmit$t= function (obj) {
		var results=obj.responseText;
		Loadjs('squid.restart.php?onlySquid=yes');
		
	}


	function Submit$t(){
		var XHR = new XHRConnection();	
		XHR.appendData('SquidEnableIdentTimeout',document.getElementById('SquidEnableIdentTimeout-$t').value);
		XHR.appendData('SquidEnableIdentdService',document.getElementById('SquidEnableIdentdService-$t').value);
		XHR.appendData('SquidEnableIdentdServiceOnly',document.getElementById('SquidEnableIdentdServiceOnly-$t').value);
		XHR.sendAndLoad('$page', 'POST',xSubmit$t);	
	}
</script>			
			
	";
	
echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function SquidEnableIdentdService(){
	$sock=new sockets();
	$sock->SET_INFO("SquidEnableIdentdService", $_POST["SquidEnableIdentdService"]);
	$sock->SET_INFO("SquidEnableIdentdServiceOnly", $_POST["SquidEnableIdentdServiceOnly"]);
	$sock->SET_INFO("SquidEnableIdentTimeout", $_POST["SquidEnableIdentTimeout"]);
	
}


function tabs(){
	$sock=new sockets();
	$compilefile="ressources/logs/squid.compilation.params";
	if(!is_file($compilefile)){$sock->getFrameWork("squid.php?compil-params=yes");}
	$COMPILATION_PARAMS=unserialize(base64_decode(@file_get_contents($compilefile)));
	
	
	if(!isset($COMPILATION_PARAMS["enable-ident-lookups"])){
		echo "<div id='squid-identd-upd-error'></div>".FATAL_ERROR_SHOW_128("{error_squid_ident_not_compiled}<center>
				".button("{update2}","Loadjs('squid.compilation.status.php');",32)."</center>");
		return;
	
	}
	
	$page=CurrentPageName();
	$users=new usersMenus();
	$array["status"]='{status}';
	$array["networks"]='{networks}';
	$sock=new sockets();
	
	
	$tpl=new templates();
	while (list ($num, $ligne) = each ($array) ){
	
		if($num=="networks"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"squid.identd.network.php\" style='font-size:20px'><span>$ligne</span></a></li>\n");
			continue;
		}
	
	
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\" style='font-size:20px'><span>$ligne</span></a></li>\n");
		//$html=$html . "<li><a href=\"javascript:LoadAjax('squid_main_config','$page?main=$num&hostname={$_GET["hostname"]}')\" $class>$ligne</a></li>\n";
			
	}
	echo build_artica_tabs($html, "debug_identd_config",1024)."<script>LeftDesign('users-white-256.png');</script>";
	
	
	
}