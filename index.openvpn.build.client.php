<?php
session_start();
include_once('ressources/class.templates.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.openvpn.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.tcpip.inc');
$users=new usersMenus();
if(!$users->AsSystemAdministrator){die("alert('no access');");}

	if(isset($_GET["popup"])){tabs();exit();}
	if(isset($_GET["popup-main"])){popup();exit;}
	if(isset($_GET["help"])){help();exit;}
	if(isset($_POST["connection_name"])){buildconfig();exit;}
	
js();

function help(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$downloadvinvin=Paragraphe("setup-icon-64.png",
		"{DOWNLOAD_OPENVPN_CLIENT}",
		"{DOWNLOAD_OPENVPN_CLIENT_TEXT}",
		"javascript:s_PopUp('http://www.articatech.net/download/openvpn-2.1.4-install.exe')",
		"{DOWNLOAD_OPENVPN_CLIENT_TEXT}");

$downloadapple=Paragraphe("apple-logo-64.png",
		"{DOWNLOAD_OPENVPN_CLIENT_APPLE}",
		"{DOWNLOAD_OPENVPN_CLIENT_TEXT}",
		"javascript:s_PopUp('http://www.articatech.net/download/Tunnelblick_3.1.7.dmg')",
		"{DOWNLOAD_OPENVPN_CLIENT_TEXT}");


	$html="
	<div class=explain>{OPENVPN_ADMIN_HELP_TEXT}</div>
	<div style='width:98%' class=form>
	<table style='width:99%'>
	<tbody>
	<tr style='height:40px'>
		<td width=1% nowrap><strong style='font-size:18px'>v2.1.4</td>
		<td width=95%  style='font-size:18px'>". texttooltip("{DOWNLOAD_OPENVPN_CLIENT} XP 32bits","{DOWNLOAD_OPENVPN_CLIENT_TEXT}",
				"s_PopUp('http://www.articatech.net/download/openvpn-2.1.4-install.exe')").
		"</td>
		<td width=1% nowrap><strong style='font-size:18px'>1.6MB</td>	
	</tr>			
	<tr style='height:40px'>
		<td width=1% nowrap><strong style='font-size:18px'>v2.3.9</td>
		<td width=95%  style='font-size:18px'>". texttooltip("{DOWNLOAD_OPENVPN_CLIENT} XP 32bits","{DOWNLOAD_OPENVPN_CLIENT_TEXT}",
				"s_PopUp('http://www.articatech.net/download/openvpn-install-2.3.9-I001-i686.exe')").
		"</td>
		<td width=1% nowrap><strong style='font-size:18px'>1.7MB</td>	
	</tr>						
	<tr style='height:40px'>
		<td width=1% nowrap><strong style='font-size:18px'>v2.3.9</td>
		<td width=95%  style='font-size:18px'>". texttooltip("{DOWNLOAD_OPENVPN_CLIENT} XP 64bits","{DOWNLOAD_OPENVPN_CLIENT_TEXT}",
				"s_PopUp('http://www.articatech.net/download/openvpn-install-2.3.9-I001-x86_64.exe')").
		"</td>
		<td width=1% nowrap><strong style='font-size:18px'>1.8MB</td>	
	</tr>						
						
	<tr style='height:40px'>
		<td width=1% nowrap><strong style='font-size:18px'>v2.3.9</td>
		<td width=95%  style='font-size:18px'>". texttooltip("{DOWNLOAD_OPENVPN_CLIENT} 7/10 64Bits","{DOWNLOAD_OPENVPN_CLIENT_TEXT}",
				"s_PopUp('http://www.articatech.net/download/openvpn-install-2.3.9-I601-x86_64.exe')").
		"</td>
		<td width=1% nowrap><strong style='font-size:18px'>1.8MB</td>	
	</tr >
	<tr style='height:40px'>
		<td width=1% nowrap><strong style='font-size:18px'>v2.3.9</td>
		<td width=95%  style='font-size:18px'>". texttooltip("{DOWNLOAD_OPENVPN_CLIENT} 7 32Bits","{DOWNLOAD_OPENVPN_CLIENT_TEXT}",
				"s_PopUp('http://www.articatech.net/download/openvpn-install-2.3.9-I601-i686.exe')").
		"</td>
		<td width=1% nowrap><strong style='font-size:18px'>1.7MB</td>	
	</tr>	
	</tboy>
	</table>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	}

function js(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{BUILD_OPENVPN_CLIENT_CONFIG}");
	$html="YahooWin4('940','$page?popup=yes','$title')";
	echo $html;
	
}

function tabs(){
	$html=GET_CACHED(__FILE__,__FUNCTION__,$_GET["newinterface"],TRUE);
	if($html<>null){echo $html;}
	$page=CurrentPageName();
	$users=new usersMenus();
	
	
	$array["popup-main"]="{BUILD_OPENVPN_CLIENT_CONFIG}";

	
	$array["help"]="{help}";
	
		$font="font-size:18px";
		$newinterface="&newinterface=yes";
	
	
	
	while (list ($num, $ligne) = each ($array) ){
			if($num=="OPENVPN_SCHEDULE_RUN"){
				$tab[]="<li><a href=\"index.openvpn.schedule.php?popup=yes\"><span style='$font'>$ligne</span></a></li>\n";
				continue;
				
			}
		
			$tab[]="<li><a href=\"$page?$num=yes$newinterface\"><span style='$font'>$ligne</span></a></li>\n";
			
		}
	$tpl=new templates();
	
	

	$html=build_artica_tabs($tab, "main_openvpn_builclientconfig");
	$tpl=new templates();
	$html=$tpl->_ENGINE_parse_body($html);
	SET_CACHED(__FILE__,__FUNCTION__,null,$html);
	echo $html;	
	
}


function popup(){
	$tpl=new templates();
	$page=CurrentPageName();	
	
	$os["windowsXP"]="Windows XP";
	$os["windows2003"]="Windows 2003/7";
	$os["linux"]="Linux";
	$os["mac"]="OS X 10.4, 10.5, 10.6";
	$os["Windows7"]="Windows 7 (Seven)";
	$os[null]="{select}";
	
	$os=Field_array_Hash($os,"ComputerOS",null,"style:font-size:32px;padding:3px");
	
	$html="<div class=explain id='buildclientconfigdiv' style='font-size:18px'>{BUILD_OPENVPN_CLIENT_CONFIG_TEXT}</div>
	
	<center style='width:98%' class=form>
		<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:32px'>{connection_name}:</td>
			<td class=legend style='font-size:32px'>". Field_text("connection_name",null,"padding:20px;font-size:32px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:32px'>{ComputerOS}:</td>
			<td class=legend style='font-size:32px'>$os</td>
		</tr>		
		<tr>
			<td colspan=2 align='right'><div style='margin-top:100px'><hr>". button("{generate_parameters}","GenerateVPNConfig()",32)."</div></td>
		</tr>
		</table>
	</center>
	<div id='generate-vpn-events'></div>
	<script>
			
var x_GenerateVPNConfig= function (obj) {
	var tempvalue=obj.responseText;
	var uid=document.getElementById('connection_name').value;
	if(tempvalue.length>3){alert(tempvalue);return;}
	Loadjs('index.openvpn.client.progress.php?uid='+uid);
}
			

function GenerateVPNConfig(){
	var XHR = new XHRConnection();
	XHR.appendData('connection_name',document.getElementById('connection_name').value);
	XHR.appendData('ComputerOS',document.getElementById('ComputerOS').value);		
	XHR.sendAndLoad('$page', 'POST',x_GenerateVPNConfig);				
}
</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}


function buildconfig(){
	$vpn=new openvpn();
	$connection_name=trim(strtolower($_POST["connection_name"]));
	if($connection_name==null){$connection_name=time();}
	$connection_name=str_replace(" ", "-", $connection_name);
	$connection_name=replace_accents($connection_name);
	$connection_name=str_replace("/", "-", $connection_name);
	$connection_name=str_replace('\\', "-", $connection_name);
	$tools=new htmltools_inc();
	$connection_name=$tools->StripSpecialsChars($connection_name);
	
	$connection_name=mysql_escape_string2($connection_name);
	$q=new mysql();
	$q->QUERY_SQL("INSERT IGNORE INTO `openvpn_clients` (uid,ComputerOS) VALUES ('$connection_name','{$_POST["ComputerOS"]}')","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	

	
	
	
	
}
