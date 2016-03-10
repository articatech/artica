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

	popup();

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$sock=new sockets();
	$squid=new squidbee();
	$EnableSS5=intval($sock->GET_INFO("EnableSS5"));
	$EnableSS5P=Paragraphe_switch_img("{EnableSS5}","{APP_SS5_ABOUT}","EnableSS5",$EnableSS5,null,900);
	$SS5_SOCKS_PORT=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SS5_SOCKS_PORT"));
	$SS5_SOCKS_INTERFACE=@file_get_contents("/etc/artica-postfix/settings/Daemons/SS5_SOCKS_INTERFACE");

	$ip=new networking();
	
	$interfaces=$ip->Local_interfaces();
	unset($interfaces["lo"]);
	
	$array[null]="{all}";
	if($SS5_SOCKS_PORT==0){$SS5_SOCKS_PORT=rand(1024,63000);}
	
	while (list ($eth, $none) = each ($interfaces) ){
		if(preg_match("#^gre#", $eth)){continue;}
		$nic=new system_nic($eth);
		$array[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
		
	
	}
	
	

$html="
	<div style='font-size:32px;margin-bottom:30px'>{APP_SS5}</div>
	<div style=width:98% class=form>
	<table style='width:100%'>
	<tr>
	<td style='vertical-align:top;width:285px'><div id='services-ss5-status'></div></td>
	<td style='vertical-align:top;width:915px'>
	<div style='width:98%' class=form>
	$EnableSS5P
	<hr>
	
	
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:24px;font-wieght:bold'>{listen_interface}:</td>
		<td style='font-size:20px'>". Field_array_Hash($array, "SS5_SOCKS_INTERFACE",
				$SS5_SOCKS_INTERFACE,"style:font-size:24px;font-wieght:bold")."</td>
		
	</tr>
		<tr>
		<td class=legend style='font-size:24px;font-wieght:bold'>{listen_port}:</td>
		<td style='font-size:20px'>". field_text("SS5_SOCKS_PORT", $SS5_SOCKS_PORT,"font-size:24px;width:90px;font-wieght:bold")."</td>
	</tr>
	</table>
	
	<div style='text-align:right;margin-top:50px'>". button("{apply}", "Save$t()",40)."</div>
	</div>
	</td>
	</tr>
	</table>
	<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	Loadjs('ss5.progress.php');
}
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('EnableSS5', document.getElementById('EnableSS5').value);
	XHR.appendData('SS5_SOCKS_INTERFACE', document.getElementById('SS5_SOCKS_INTERFACE').value);
	XHR.appendData('SS5_SOCKS_PORT', document.getElementById('SS5_SOCKS_PORT').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
	LoadAjax('services-ss5-status','$page?services-ss5-status=yes',false);
</script>";
echo $tpl->_ENGINE_parse_body($html);
}

function EnableSS5(){
	$sock=new sockets();
	$sock->SET_INFO("EnableSS5", $_POST["EnableSS5"]);
	$sock->SET_INFO("SS5_SOCKS_INTERFACE", $_POST["SS5_SOCKS_INTERFACE"]);
	$sock->SET_INFO("SS5_SOCKS_PORT", $_POST["SS5_SOCKS_PORT"]);
	
}

function status(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$sock->getFrameWork("ss5.php?service-status=yes");
	$ini=new Bs_IniHandler();
	$ini->loadFile("/usr/share/artica-postfix/ressources/logs/APP_SS5.status");
	$status=DAEMON_STATUS_ROUND("APP_SS5", $ini);
	$redsocks=DAEMON_STATUS_ROUND("APP_REDSOCKS", $ini);
	$html="$status$redsocks<div style='text-align:right;height:40px;'>". imgtootltip("refresh-32.png","{refresh}","RefreshTab('influxdb_main_table');","right")."</div>";
	echo $tpl->_ENGINE_parse_body($html);

}