<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

	$dirname=dirname(__FILE__);
	
	include_once("$dirname/ressources/class.templates.inc");
	include_once("$dirname/ressources/class.ldap.inc");
	include_once("$dirname/ressources/class.users.menus.inc");
	include_once("$dirname/ressources/class.system.network.inc");
	include_once("$dirname/ressources/class.system.nics.inc");
	include_once("$dirname/ressources/class.maincf.multi.inc");
	include_once("$dirname/ressources/class.tcpip.inc");
	$usersmenus=new usersMenus();
	if($usersmenus->AsSystemAdministrator==false){exit();}

if(isset($argv[1])){
	if($argv[1]=="zlistnics"){
		$GLOBALS["VERBOSE"]=true;
		ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
		zlistnics();
		die();
	}
}	
	
if(isset($_GET["listnics"])){zlistnics_tabs();exit;}
if(isset($_GET["listnics2"])){zlistnics();exit;}
if(isset($_GET["nic-builder"])){zlistnics_builder();exit;}
if(isset($_GET["js-virtual-add"])){virtual_js_add();exit;}
if(isset($_POST["RebuildMyNic"])){RebuildMyNic();exit;}
if(isset($_POST["OVHNetConfig"])){OVHNetConfig();exit;}
if(isset($_GET["default-route-status"])){DefaultRouteStatus();exit;}


if(isset($_GET["BuildNetConf"])){BuildNetConf();exit;}
if($_GET["main"]=="listnics"){zlistnics_tabs();exit;}
if($_GET["main"]=="listnics2"){zlistnics();exit;}
if($_GET["main"]=="virtuals"){Virtuals();exit;}
if($_GET["main"]=="bridges"){Bridges();exit;}
if($_GET["main"]=="DNSServers"){DNS_SERVERS_POPUP();exit;}
if(isset($_POST["DOMAINS1"])){DNS_SERVERS_SAVE();exit;}

if(isset($_GET["NetworkManager-check"])){NetworkManager_check();exit;}



if(isset($_POST["CheckIpV4ToIp26"])){CheckIpV4ToIp26();exit;}
if(isset($_GET["virtuals-list"])){virtuals_list();exit;}
if(isset($_GET["virt-ipaddr"])){virtuals_add();exit;}
if(isset($_POST["virt-ipv6"])){virtuals_addv6();exit;}
if(isset($_GET["virt-del"])){virtuals_del();exit;}

if(isset($_GET["script"])){switch_script();exit;}

if(isset($_GET["netconfig"])){netconfig_popup();exit;}

if(isset($_GET["change-hostname-js"])){ChangeHostName_js();exit;}
if(isset($_GET["hostname"])){hostname();exit;}
if(isset($_GET["ChangeHostName"])){ChangeHostName();exit;}

if(isset($_GET["AddDNSServer"])){AddDNSServer();exit;}
if(isset($_GET["DeleteDNS"])){DeleteDNS();exit;}
if(isset($_GET["DNSServers"])){DNS_SERVERS_POPUP();}
if(isset($_GET["DNSServers-list"])){DNS_SERVERS_POPUP_LIST();}



if(isset($_GET["js"])){js();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["popup2"])){popup2();exit;}
if(isset($_GET["popup-tabs"])){tabs();exit;}
if(isset($_GET["popup-hostname"])){tabs_hostname();exit;}

if(isset($_GET["virtual-popup-add"])){virtual_add_form();exit;}
if(isset($_GET["virtual-popup-addv6"])){virtual_add_formv6();exit;}


if(isset($_GET["cdir-ipaddr"])){virtual_cdir();exit;}
if(isset($_GET["postfix-virtual"])){virtuals_js();exit;}
if(isset($_GET["js-add-nic"])){echo virtuals_js_datas();exit;}

if(isset($_GET["bridges-add-form"])){Bridges_form_add();exit;}
if(isset($_GET["bridges-list"])){Bridges_list();exit;}
if(isset($_GET["bridge-add"])){Bridges_add();exit;}
if(isset($_GET["bridge-del"])){Bridges_del();exit;}
if(isset($_GET["bridges-rules"])){Bridges_rules();exit;}

if(isset($_GET["NetWorkBroadCastAsIpAddr"])){NetWorkBroadCastAsIpAddr();exit;}



function popup(){
	if(isset($_GET["newinterface"])){$fontsize="font-size:14px";$linkadd="&newinterface=yes";}	
	$page=CurrentPageName();
	$html="<div id='MasterNetworkSection'></div>
	
	<script>
		LoadAjax('MasterNetworkSection','$page?popup2=yes$linkadd');
	</script>
	";

	echo $html;
	
}

function popup2(){
if(isset($_GET["newinterface"])){$fontsize="font-size:14px";$linkadd="&newinterface=yes";}		
$page=CurrentPageName();	
$html="
	<div class=explain style='font-size:16px;width:945px;margin-left:-2px'>{network_about}</div>
	<div id='hostname_cf'></div>
	<div id='nic_status'></div>
	<div id='nic_tabs'></div>
<script>
	LoadAjax('nic_tabs','$page?popup-tabs=yes$linkadd');
</script>
	
	";




$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);	
	
}

function tabs(){
	
	
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$array["listnics"]='{main_interfaces}';
	$array["hosts"]='{hosts}';
	$array["virtuals"]='{virtual_interfaces}';
	
	if($users->VLAN_INSTALLED){$array["vlan"]='VLAN';}
	
	$array["bridges"]='{bridges}';
	//$array["routes"]='{routes}';
	$array["hard"]='{hardware}';
	
	if($users->KASPERSKY_WEB_APPLIANCE){
		unset($array["vlan"]);
		
	}
	
	
	$tabwith="980";
	if(isset($_GET["newinterface"])){$fontsize="font-size:14px";
	$linkadd2="?newinterface=yes";$linkadd="&newinterface=yes";$tabwith="100%";}	
	$fontsize="font-size:20px";
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="hard"){
			$html[]= "<li><a href=\"system.nic.infos.php?popup=yes$linkadd\"><span style='$fontsize'>". $tpl->_ENGINE_parse_body($ligne)."</span></a></li>\n";
			continue;
		}
		
		if($num=="routes"){
			$html[]= "<li><a href=\"system.nic.routes.php$linkadd2\"><span style='$fontsize'>". $tpl->_ENGINE_parse_body($ligne)."</span></a></li>\n";
			continue;
		}
		
		if($num=="hosts"){
			$html[]= "<li><a href=\"system-etc-hosts.php?table=yes$linkadd2\"><span style='$fontsize'>". $tpl->_ENGINE_parse_body($ligne)."</span></a></li>\n";
			continue;
		}		

		if($num=="vlan"){
			$html[]= "<li><a href=\"system.nic.vlan.php$linkadd2\"><span style='$fontsize'>". $tpl->_ENGINE_parse_body($ligne)."</span></a></li>\n";
			continue;
		}
		
		if($num=="vde"){
			$html[]= "<li><a href=\"system.nic.vde.php\"><span style='$fontsize'>". $tpl->_ENGINE_parse_body($ligne)."</span></a></li>\n";
			continue;
		}		

		if($num=="snort"){
			$html[]= "<li><a href=\"system.nic.snort.php$linkadd2\"><span style='$fontsize'>". $tpl->_ENGINE_parse_body($ligne)."</span></a></li>\n";
			continue;
		}			
		
		$html[]= "<li><a href=\"$page?main=$num$linkadd\"><span style='$fontsize'>". $tpl->_ENGINE_parse_body($ligne)."</span></a></li>\n";
	}
	

	
	echo build_artica_tabs($html, "main_config_nics",990)."<script>LeftDesign('nic-white-256-opac20.png');</script>";;
	
	
	
}

function tabs_hostname(){
	$sock=new sockets();
	$page=CurrentPageName();
	$hostname=$hostname=$sock->GET_INFO("myhostname");
	if($hostname==null){$hostname=$sock->getFrameWork("system.php?hostname-g=yes");$sock->SET_INFO($hostname,"myhostname");}
	$tpl=new templates();
if(isset($_GET["newinterface"])){$fontsize="font-size:14px;";$linkadd="&newinterface=yes";$tabwidth="100%";}
	echo $tpl->_ENGINE_parse_body("
		<center>
		<table style='width:400px;margin:3px;padding:3px;'
		OnMouseOver=\";this.style.cursor='pointer';this.style.background='#F5F5F5';\"
		OnMouseOut=\";this.style.cursor='default';this.style.background='#FFFFFF';\"
		class=form
		>
		<tr>
			<td valign='top' width=1%><img src='img/64-server-script.png'></td>
			<td valign='top' style='padding:4px'>
				<div style='font-size:13px'>
						<a href=\"javascript:blur()\" 
						OnClick=\"javascript:Loadjs('$page?change-hostname-js=yes');\" 
						style='font-weight:bold;font-size:22px;font-weight:bold'>{hostname}:</a>
					<div style='margin-top:15px;margin-bottom:15px'>
					<a href=\"javascript:blur()\" 
					OnClick=\"javascript:Loadjs('$page?change-hostname-js=yes$linkadd');\" 
					style='text-decoration:underline;font-size:16px'>$hostname</a>
					</div>
					<div style='width:100%;text-align:right'>
						<i style='font-size:12px;color:black'>{click_to_edit}</i>
					</div>
					
				</div>
			</td>
		</tr>
		</table></center>
		");	
	
	
	
	
}


function js(){
	if(isset($_GET["newinterface"])){$fontsize="font-size:14px";$linkadd="&newinterface=yes";}	
	$add=js_addon()."\n".file_get_contents("js/system-network.js");
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{net_settings}');
	$page=CurrentPageName();
	$prefix=md5($page);
	$openjs="YahooWin(700,'$page?popup=yes','$title');";
	IF(isset($_GET["in-front-ajax"])){
		$openjs="$('#BodyContent').load('$page?popup=yes$linkadd');";
	}
	
	$html="
	$add
	$openjs
";
	
	echo $html;
}


function js_addon(){
	
	$sock=new sockets();
	$tpl=new templates();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}
	
	$page=CurrentPageName();
	return "



function NicSettingsChargeLogs(){
	RefreshTab('main_config_nics');
	setTimeout(\"NicSettingsChargeHostanme()\",1000);
	}
	
function NicSettingsChargeHostanme(){
	LoadAjax('hostname_cf','$page?hostname=yes');
}

";
	
}

function switch_script(){
	
	switch ($_GET["script"]) {
		case "netconfig":echo netconfig();break;
	
		default:
			break;
	}
	
}

function hostname(){
$nic=new networking();
$nameserver=$nic->arrayNameServers;
$dns_text="<table class=form>";


if(is_array($nameserver)){
	while (list ($num, $val) = each ($nameserver) ){
		$val=trim($val);
		$dns_text=$dns_text."<tr " . CellRollOver_jaune().">
			<td width=1%><img src='img/fw_bold.gif'>
			<td class=legend nowrap>{nameserver}:</td>
			<td width=99% nowrap><strong style='font-size:11px'>$val</strong></td>
			<td width=1%>" . imgtootltip('ed_delete.gif','{delete}',"DeleteDNS('$val');")."</td>
			</tr>";
		
		
	}
}

$dns_text=$dns_text."
<tr>
<td align='right' colspan=4><hr>". button("{add}","AddDNSServer();")."</td>
</tr>
</table>
<br>
<input type='hidden' name='ChangeHostName' id='ChangeHostName' value='{ChangeHostName}'>





<table class=form>
<tr>
	<td class=legend>{hostname}:</td>
	<td><strong style='font-size:12px'><strong>$nic->hostname</strong></td>
	<td width=1%>". button("{apply}","ChangeHostName('$nic->hostname');")."</td>
</tr>
</table>";

$tpl=new templates();
echo $tpl->_ENGINE_parse_body($dns_text);
}

function ChangeHostName_js(){
	$sock=new sockets();
	$tpl=new templates();
	$hostname=$sock->getFrameWork("cmd.php?full-hostname=yes");	
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}
	$changehostname_text=$tpl->javascript_parse_text("{ChangeHostName}");
	if(isset($_GET["newinterface"])){$fontsize="font-size:14px;";$linkadd="&newinterface=yes";$tabwidth="100%";}
	$page=CurrentPageName();
	
$html="
var x_ChangeHostName= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	UnlockPage();
	if(document.getElementById('MasterNetworkSection')){LoadAjax('MasterNetworkSection','$page?popup2=yes$linkadd');}
	if(document.getElementById('squidcklinks-host-infos')){LoadAjaxTiny('squidcklinks-host-infos','quicklinks.php?squidcklinks-host-infos=yes');}
	if(document.getElementById('main_adker_tabs')){RefreshTab('main_adker_tabs');}
	if(document.getElementById('template-top-menus')){ AjaxTopMenu('template-top-menus','admin.top.menus.php');}
	LoadAjaxSilent('chhostname-text','admin.dashboard.system.php?hostname-text=yes');
	ChangeHTMLTitle();
		
}

function ChangeHostName(){
		var DisableNetworksManagement=$DisableNetworksManagement;
		if(DisableNetworksManagement==1){alert('$ERROR_NO_PRIVS');return;}
		var hostname=prompt('$changehostname_text','$hostname');
		if(hostname){
			var XHR = new XHRConnection();
			XHR.appendData('ChangeHostName',hostname);
			if(document.getElementById('MasterNetworkSection')){document.getElementById('MasterNetworkSection').innerHTML=\"<center style='margin:10px'><img src='img/wait_verybig.gif'></center>\";}
			LockPage();
			XHR.sendAndLoad('$page', 'GET',x_ChangeHostName);
			}

}

ChangeHostName();
";	

echo $html;
	
}

function ChangeHostName(){
	
	$sock=new sockets();
	$tpl=new templates();
	$q=new mysql();
	
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}	
	if($DisableNetworksManagement==1){echo $ERROR_NO_PRIVS;}
	
	
	$tpl=new templates();
	if($_GET["ChangeHostName"]=='null'){
		echo $tpl->_ENGINE_parse_body('{cancel}');
		return null;}
	$_GET["ChangeHostName"]=trim(strtolower($_GET["ChangeHostName"]));
	$t=explode(".",$_GET["ChangeHostName"]);
	if(count($t)==1){echo $tpl->_ENGINE_parse_body("{$_GET["ChangeHostName"]}: {not_an_fqdn}");return;}
	
	
	$nic=new system_nic();
	$nic->set_hostname($_GET["ChangeHostName"]);
}

function zlistnics_tabs(){
	
	$array["listnics2"]='{nics}';
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();	
	
	$array["arpd"]="{arp_table}";
	
	if($users->ETTERCAP_INSTALLED){
		$array["arpspoof"]='ARP Spoofing';
	}	
		
	if($users->SNORT_INSTALLED){
		
		$APP_SNORT=$tpl->_ENGINE_parse_body("{APP_SNORT}");
		if(strlen($APP_SNORT)>42){$APP_SNORT=substr($APP_SNORT, 0,43);}
		$array["snort"]=$APP_SNORT;
	}
	
	

	

	
	$tabwidth="730px";
	if(isset($_GET["newinterface"])){$fontsize="font-size:14px;";$linkadd="&newinterface=yes";$tabwidth="100%";}
	$fontsize="font-size:22px";
	while (list ($num, $ligne) = each ($array) ){
			
		if($num=="arpspoof"){
			$html[]= "<li><a href=\"arp.spoof.php?none=yes$linkadd\"><span style='$fontsize'>". $tpl->_ENGINE_parse_body($ligne)."</span></a></li>\n";
			continue;
		}			
			
	

		if($num=="arpd"){
			$html[]= "<li><a href=\"arptable.php\"><span style='$fontsize'>". $tpl->_ENGINE_parse_body($ligne)."</span></a></li>\n";
			continue;
		}			
			
		if($num=="snort"){
			$html[]= "<li><a href=\"system.nic.snort.php?no=no$linkadd\"><span style='$fontsize'>". $tpl->_ENGINE_parse_body($ligne)."</span></a></li>\n";
			continue;
		}
		
			
		
		$html[]= "<li><a href=\"$page?main=$num$linkadd\"><span style='$fontsize'>". $tpl->_ENGINE_parse_body($ligne)."</span></a></li>\n";
	}
	
	$tab=time();
	
	echo build_artica_tabs($html, "tabs_listnics2");
	
	
}

function DefaultRouteStatus(){
	$q=new mysql();
	$tpl=new templates();
	$sql="SELECT * FROM  `nics` WHERE defaultroute=1 ORDER BY Interface";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$defaultEthRoute=trim($ligne["Interface"]);
	
	if($defaultEthRoute==null){
		echo $tpl->_ENGINE_parse_body("<p class=text-error>{error_no_default_route_explain}</p>");
	
	}else{
		echo $tpl->_ENGINE_parse_body("<p style='color: #3a87ad;	margin:5px;
	padding:3px;
	border:1px solid #3a87ad;
	border-radius:5px 5px 5px 5px;
	 -moz-border-radius:5px;
	-webkit-border-radius:5px;
    background-color: white;
 	font-weight:bold;
    font-size: 14px;
    margin-bottom: 20px;
    padding: 8px 35px 8px 14px;
    text-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);'>{default_gateway}: {interface}:$defaultEthRoute</p>");
	}
	
}

function zlistnics(){
	$page=CurrentPageName();
	$sock=new sockets();
	$users=new usersMenus();
	$t=time();
	$snortInterfaces=array();
	$LXCEthLocked=$sock->GET_INFO("LXCEthLocked");
	$EnableipV6=$sock->GET_INFO("EnableipV6");
	if(!is_numeric($EnableipV6)){$EnableipV6=0;}
	$ASDEBIAN=0;
	if($users->AS_DEBIAN_FAMILY){$ASDEBIAN=1;}
	if(!is_numeric($LXCEthLocked)){$LXCEthLocked=0;}
	
	$LXCInterface=$sock->GET_INFO("LXCInterface");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	$OVHNetConfig=$sock->GET_INFO("OVHNetConfig");
	if(!is_numeric($OVHNetConfig)){$OVHNetConfig=0;}
	
	if(!is_numeric($DisableNetworksManagement)){$DisableNetworksManagement=0;}
	$page=CurrentPageName();
	$tpl=new templates();
	$apply_network_configuration=$tpl->_ENGINE_parse_body("{apply_network_configuration}");
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$apply_network_configuration_warn=$tpl->javascript_parse_text("{apply_network_configuration_warn}");
	
	$users=new usersMenus();
	if($users->SNORT_INSTALLED){
		$EnableSnort=$sock->GET_INFO("EnableSnort");
		if($EnableSnort==1){
			$snortInterfaces=unserialize(base64_decode($sock->GET_INFO("SnortNics")));
		}
	}	
	
	if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: LOADING class networking<br>\n";}
	
	$tcp=new networking();
	
	
	if($GLOBALS["VERBOSE"]){echo "[".__LINE__.":".date("H:i:s")."]: cmd.php?list-nics=yes<br>\n";}
	$datas=unserialize(base64_decode($sock->getFrameWork("cmd.php?list-nics=yes")));
	if($GLOBALS["VERBOSE"]){echo "[".__LINE__.":".date("H:i:s")."]: cmd.php?list-nics=yes done<br>\n";}
	$count=0;
	writelogs(count($datas). " rows for nic infos",__FUNCTION__,__FILE__,__LINE__);
	if(isset($_GET["newinterface"])){$fontsize="font-size:14px;";$linkadd="&newinterface=yes";$tabwidth="100%";}
	
	
	
	$tr[]=$tpl->_ENGINE_parse_body("
		<table style='width:400px;margin:3px;padding:3px;'
		OnMouseOver=\";this.style.cursor='pointer';this.style.background='#F5F5F5';\"
		OnMouseOut=\";this.style.cursor='default';this.style.background='#FFFFFF';\"
		class=form
		>
		<tr>
			<td valign='top' width=1%><img src='img/ipv6-64.png'></td>
			<td valign='top' style='padding:4px'>
			<a href=\"javascript:blur()\" 
						OnClick=\"javascript:Loadjs('system.nic.ipv6.php')\" 
						style='text-decoration:underline;font-weight:bold;font-size:22px'>IPv6: {parameters}</a>	
					<div style='font-size:14px;;margin-top:15px;margin-bottom:15px'>
						
					<a href=\"javascript:blur()\" OnClick=\"javascript:Loadjs('system.nic.ipv6.php')\" 
					style='text-decoration:underline;font-size:16px'>{ipv6_explain_enable_text}</a>
					
				</div>
			</td>
		</tr>
		</table>
		");
	
	
	
	$tr[]="<div id='main_config_hostname'></div>
	<script>LoadAjax('main_config_hostname','$page?popup-hostname=yes$linkadd');</script>
	";
	
	
	
	while (list ($num, $val) = each ($datas) ){
		writelogs("Found: $val",__FUNCTION__,__FILE__,__LINE__);
		$val=trim($val);
		if(preg_match('#master#',$val)){continue;}
		if(preg_match("#^veth.+?#",$val)){continue;}
		if(preg_match("#^tunl[0-9]+#",$val)){continue;}
		if(preg_match("#^dummy[0-9]+#",$val)){continue;}
		if(preg_match("#^gre[0-9]+#",$val)){continue;}
		if(preg_match("#^ip6tnl[0-9]+#",$val)){continue;}
		if(preg_match("#^sit[0-9]+#",$val)){continue;}
		if(preg_match("#^vlan[0-9]+#",$val)){continue;}
		if(preg_match("#^virt[0-9]+#",$val)){continue;}
		
		
		$nic=new system_nic($val);
		
		
		if(!$nic->unconfigured){		
			if($LXCEthLocked==1){if($val==$LXCInterface){
				writelogs("LXCEthLocked:$LXCEthLocked; $val==$LXCInterface -> abort",__FUNCTION__,__FILE__,__LINE__);
				continue;
				}
			}
		}
		
		if(trim($val)==null){continue;}
		if($nic->Bridged==1){continue;}
		$tcp->ifconfig(trim($val));
		$text=listnicinfos(trim($val),"Loadjs('$page?script=netconfig&nic=$val')");
		$js="javascript:Loadjs('system.nic.edit.php?nic=$val')";
		
		if(preg_match("#^br([0-9]+)#", $val,$re)){
			$js="javascript:Loadjs('system.network.bridges.interfaces.php?network-bridge-js=yess&ID={$re[1]}')";
		}
		
		
		if(!$tcp->linkup){
			$img_on="net-card-64-grey.png";
			
		}else{
			$img_on="net-card-64.png";
			if($snortInterfaces[trim($val)]==1){$img_on="64-win-nic-snort.png";}
		}
		
		$tr[]="<div id='zlistnic-info-$val'></div>";
		$jsnics[]="LoadAjax('zlistnic-info-$val','$page?nic-builder=$val$linkadd');";

		}
		
$tables[]="<table style='width:100%'><tr>";
$t=0;
while (list ($key, $line) = each ($tr) ){
		$line=trim($line);
		if($line==null){continue;}
		$t=$t+1;
		$tables[]="<td valign='top'>$line</td>";
		if($t==2){$t=0;$tables[]="</tr><tr>";}
		}

if($t<2){
	for($i=0;$i<=$t;$i++){
		$tables[]="<td valign='top'>&nbsp;</td>";				
	}
}	

	$html=@implode("\n", $tables);
	$ovh_specific_config=$tpl->_ENGINE_parse_body("{ovh_specific_config}");	
	$ovh_specific_config_explain=$tpl->_ENGINE_parse_body("{ovh_specific_config_explain}");
	
	echo "
	<div id='defroute-$t'></div>
	<center style='margin-bottom:10px'>
			<table style='width:100%'>
			<tr>
				<td width=50% style='text-align:center'>". button($tpl->_ENGINE_parse_body("{network_status}"),"Loadjs('network.status.php')",22)."</td>
				<td width=50% style='text-align:center'>". 
				button("$apply_network_configuration","Loadjs('network.restart.php')",22)."</td>
				
			</tr>
		</table>
	</center>
	<script>
		LoadAjaxTiny('defroute-$t','$page?default-route-status=yes');
	</script>	
	
	
	
	<div id='NetworkManager-status'></div>
	$html
	
	
	<table style='width:99%' class=form>
	<tr>
	<td width=1%>". help_icon($ovh_specific_config_explain)."</td>
	<td widh=1% nowrap class=legend style='font-size:14px'>$ovh_specific_config:</td>
	<td>". Field_checkbox_design("OVHNetConfig", 1,$OVHNetConfig,"OVHNetConfigSave()")."</td>
	</tr>
	</table>
	
	<script>
		
		LoadAjax('NetworkManager-status','$page?NetworkManager-check=yes');
		
		var X_BuildNetConf= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			
		}	

		function OVHNetConfigSave(){
			var DisableNetworksManagement=$DisableNetworksManagement;
			if(DisableNetworksManagement==1){alert('$ERROR_NO_PRIVS');return;}	
			var XHR = new XHRConnection();
			if(document.getElementById('OVHNetConfig').checked){XHR.appendData('OVHNetConfig',1);}else{XHR.appendData('OVHNetConfig',0);}
			XHR.sendAndLoad('$page', 'POST',X_BuildNetConf);
		}
		
		function CheckDEB(){
			var ASDEBIAN=$ASDEBIAN;
			document.getElementById('OVHNetConfig').disabled=true;
			if(ASDEBIAN==1){document.getElementById('OVHNetConfig').disabled=false;}
		
		}		
		


		
		
		". @implode("\n", $jsnics)."

		CheckDEB();
	</script>
	
	";
	}
	
function zlistnics_builder(){
	$sock=new sockets();
	$snortInterfaces=array();
	$LXCEthLocked=$sock->GET_INFO("LXCEthLocked");
	$EnableipV6=$sock->GET_INFO("EnableipV6");
	if(!is_numeric($EnableipV6)){$EnableipV6=0;}
	if(!is_numeric($LXCEthLocked)){$LXCEthLocked=0;}
	$IPBANS=unserialize(base64_decode($sock->GET_INFO("ArticaIpListBanned")));
	$LXCInterface=$sock->GET_INFO("LXCInterface");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if(!is_numeric($DisableNetworksManagement)){$DisableNetworksManagement=0;}
	$page=CurrentPageName();
	$tpl=new templates();
	$apply_network_configuration=$tpl->_ENGINE_parse_body("{apply_network_configuration}");
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$apply_network_configuration_warn=$tpl->javascript_parse_text("{apply_network_configuration_warn}");
	
	$users=new usersMenus();
	if($users->SNORT_INSTALLED){
		$EnableSnort=$sock->GET_INFO("EnableSnort");
		if($EnableSnort==1){
			$snortInterfaces=unserialize(base64_decode($sock->GET_INFO("SnortNics")));
		}
	}	
	
	$tcp=new networking();
	

	
	
		$val=$_GET["nic-builder"];
		writelogs("Found: $val",__FUNCTION__,__FILE__,__LINE__);
		$val=trim($val);
		if(preg_match('#master#',$val)){return;}
		if(preg_match("#^veth.+?#",$val)){return;}
		if(preg_match("#^tunl[0-9]+#",$val)){return;}
		if(preg_match("#^dummy[0-9]+#",$val)){return;}
		if(preg_match("#^gre[0-9]+#",$val)){return;}
		if(preg_match("#^ip6tnl[0-9]+#",$val)){return;}
		if(preg_match("#^sit[0-9]+#",$val)){return;}
		if(preg_match("#^vlan[0-9]+#",$val)){return;}
		
		
		$nic=new system_nic();
		if(!$nic->unconfigured){		
			if($LXCEthLocked==1){if($val==$LXCInterface){
				writelogs("LXCEthLocked:$LXCEthLocked; $val==$LXCInterface -> abort",__FUNCTION__,__FILE__,__LINE__);
				continue;
				}
			}
		}
		
		$NIC_UP=false;
		
		if(trim($val)==null){continue;}
		if($GLOBALS["VERBOSE"]){echo "<strong>tcp->ifconfig($val)</strong><br>\n";}
		$tcp->ifconfig(trim($val));
		
		$jsEdit="Loadjs('system.nic.edit.php?nic=$val')";
		
		if(preg_match("#^br([0-9]+)#", $val,$re)){$jsEdit="Loadjs('system.network.bridges.interfaces.php?network-bridge-js=yess&ID={$re[1]}')";}
		
		$text=listnicinfos(trim($val),$jsEdit);
		
		
		

		
		$ipddr=$GLOBALS[trim($val)]["IP"];
		
		$js="javascript:$jsEdit";
		
		if(!$tcp->linkup){
			$img_on="net-card-64-grey.png";
			
		}else{
			$img_on="net-card-64.png";
			$NIC_UP=true;
			if($snortInterfaces[trim($val)]==1){$img_on="64-win-nic-snort.png";}
		}
		
		$icon1=imgtootltip("service-restart-16.png","{rebuild}","RebuildMyNic$val()");
		$icon2=imgtootltip("plus-16.png","{add_virtual_ip_addr_explain_js}","Loadjs('$page?js-add-nic=$val')");
		
		if($GLOBALS[trim($val)]["HAMACHI"]){
			$img_on="64-win-nic-hamachi.png";
			$js="javascript:Loadjs('hamachi.php')";
			$icon1=null;
			$icon2=null;
		}
		
		if($IPBANS[$ipddr]){
			$img_on="64-win-nic-off.png";
			$icon1=null;
			$icon2=null;
			$js=null;			
		}
		
		$html="
		<table style='width:400px;margin:3px;padding:3px;'
		OnMouseOver=\";this.style.cursor='pointer';this.style.background='#F5F5F5';\"
		OnMouseOut=\";this.style.cursor='default';this.style.background='#FFFFFF';\"
		class=form>
		<tr>
			<td valign='top' width=1%><img src='img/$img_on'></td>
			<td valign='top' style='padding:4px'>
				<div OnClick=\"$js\">$text</div>
				<table style='width:100%'>
					<tr>
						<td width=99%><div style='text-align:right'>$icon1</div></td>
						<td width=99%><div style='text-align:right'>". imgtootltip("16-refresh.png","{refresh}","RefreshMyNic$val()")."</div></td>
						<td width=99%><div style='text-align:right'>$icon2</div></td>
					</tr>
				</table>
			</td>
		</tr>
		</table>
		
		<script>
			function RefreshMyNic$val(){
				LoadAjax('zlistnic-info-$val','$page?nic-builder=$val');
			}

		var X_RebuildMyNic$val= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);}
			RefreshMyNic$val();
		}		

		function  RebuildMyNic$val(){
			var DisableNetworksManagement=$DisableNetworksManagement;
			if(DisableNetworksManagement==1){alert('$ERROR_NO_PRIVS');return;}	
			if(confirm('$apply_network_configuration_warn')){	
				var XHR = new XHRConnection();
				XHR.appendData('RebuildMyNic','$val');
				AnimateDiv('zlistnic-info-$val');
				XHR.sendAndLoad('$page', 'POST',X_RebuildMyNic$val);
			}
		}
		

		
		</script>
		
		
		";

		echo $tpl->_ENGINE_parse_body($html);

	}
	

function listnicinfos($nicname,$js=null){
	$SourceBasedRouting=null;
	$sock=new sockets();
	if($GLOBALS["VERBOSE"]){echo "<strong>cmd.php?nicstatus=$nicname</strong><br>\n";}
	$nicinfos=$sock->getFrameWork("cmd.php?nicstatus=$nicname");
	$EnableipV6=$sock->GET_INFO("EnableipV6");
	if(!is_numeric($EnableipV6)){$EnableipV6=0;}	
	$MUST_CHANGE=false;
	$MUST_CHANGE_TEXT=null;
	
	$IPBANS=unserialize(base64_decode($sock->GET_INFO("ArticaIpListBanned")));	
	$tbl=explode(";",$nicinfos);
	
	if($GLOBALS["VERBOSE"]){
		while (list ($num, $ligne) = each ($tbl) ){
			echo "<strong>$ligne<br>\n";
		}
	}
	
	$TCP_NIC_STATUS=$sock->getFrameWork("cmd.php?nicstatus=$nicname");
	
	
	
	$tpl=new templates();
	if($EnableipV6==1){
		$ip6s=unserialize(base64_decode($sock->getFrameWork("network.php?ifconfig6=$nicname")));
		while (list ($num, $ligne) = each ($ip6s) ){
			$ip6z[]="<tr>
					<td><i style='font-size:11px'>$num</i></td>
				</tr>";	
		}
	}
	
	$_netmask=html_entity_decode($tpl->_ENGINE_parse_body("{netmask}"));
	if(strlen($_netmask)>11){$_netmask=texttooltip(substr($_netmask,0,8)."...:",$tpl->_ENGINE_parse_body("{netmask}"));}else{$_netmask=$_netmask.":";}
	$wire='';
	if(trim($tbl[5])=="yes"){$wire=" (wireless)";}
	
	if(preg_match("#^5\.[0-9]+\.#", $tbl[0])){
		if($tbl[2]=="255.0.0.0"){
			$GLOBALS[$nicname]["HAMACHI"]=true;
			$js="javascript:Loadjs('hamachi.php')";
		}
	}
	
	$gateway=trim($tbl[4]);
	
	if($IPBANS[$tbl[0]]){$hidde_interface=true;}
	$nicz=new system_nic($nicname);
	if($gateway==null){
		$gateway=$nicz->GATEWAY;
	}
	
	$defaults_infos_array=base64_encode(serialize(array("IP"=>$tbl[0],"NETMASK"=>$tbl[2],"GW"=>$gateway,"NIC"=>$nicname)));
	
	
	if($nicz->defaultroute==1){
		$defaultroute_text="<div><i style='color:#C40000;font-size:11px;font-weight:normal;text-decoration:none'>{default_route}</i></div>";
	}
	
	if($nicz->enabled==0){$hidde_interface="#8E8E8E";}
	
	if($hidde_interface){$href=null;$textColor="#ACAAAA";}
	
	$GLOBALS[$nicname]["IP"]=$tbl[0];
	
	if($js<>null){$href="<a href=\"javascript:blur();\" 
	OnClick=\"javascript:$js\" style='font-weight:bold;font-size:16px;text-decoration:underline;
	color:$textColor'>";}
	
	$textColor="black";
	
	
	
	if($tbl[0]==null){$tbl[0]="<span style='color:#BA0000'>{waiting_network_reload}</span>";}
	
	if($nicz->SourceBasedRouting==1){
		$SourceBasedRouting="<div style='font-size:11px;text-align:right;font-weight:bolder'>{source_based_routing}</div>";
	}else{
		$SourceBasedRouting="<div style='font-size:11px;text-align:right;font-weight:bolder'>{default_based_routing}</div>";
	}
	
	
	
	if($nicz->IPADDR<>$tbl[0]){
		$MUST_CHANGE=true;
	}
	if($nicz->NETMASK<>$tbl[2]){
		$MUST_CHANGE=true;
	}	
	if($nicz->GATEWAY<>$gateway){
		$MUST_CHANGE=true;
	}	
	
	if($nicz->dhcp==1){
		$ip=new IP();
		if($ip->isValid($tbl[0])){$MUST_CHANGE=false;}
	}
	
	
	
	if($MUST_CHANGE){
		$MUST_CHANGE_TEXT2=$tpl->_ENGINE_parse_body("{need_to_apply_network_settings_interface}");
		$MUST_CHANGE_TEXT="<tr>
		<td class=legend nowrap style='color:$textColor;font-size:16px'><img src='img/warning-panneau-32.png'></td>
		<td><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('network.restart.php')\"
		style='font-weight:bold;font-size:14px;color:#C40000;text-decoration:underline'>$MUST_CHANGE_TEXT2</a></td>
		</tr>";
	}
		
	
	$html="
	<input type='hidden' id='infos_$nicname' value='$defaults_infos_array'>
	<div style='width:98%'>
	<div style='font-size:22px;font-weight:bold;margin-left:10px;margin-bottom:10px'>$nicz->NICNAME/$nicz->netzone [$nicname]</div>
	<table style='width:100%'>
	$MUST_CHANGE_TEXT
	<tr>
		<td class=legend nowrap style='color:$textColor;font-size:16px' valign='top'>{tcp_address}:</td>
		<td style='color:$textColor;font-size:16px'>$href{$tbl[0]}</a></td>
	</tr>
	". @implode("", $ip6z)."
	<tr>
		<td class=legend nowrap style='color:$textColor;font-size:16px'>$_netmask</td>
		<td style='font-weight:bold;font-size:16px;color:$textColor'>$href{$tbl[2]}</a></td>
	</tr>	
	<tr>
		<td class=legend nowrap style='color:$textColor;vertical-align:top;font-size:16px'>{gateway}:</td>
		<td style='font-weight:bold;font-size:16px;color:$textColor'>$href{$gateway}</a>
			<br>
				<div style='font-size:11px;text-align:right'>metric: $nicz->metric$defaultroute_text</div>
				
		</td>
	</tr>		
	<tr>
		<td class=legend nowrap style='color:$textColor;font-size:16px'>{mac_addr}:</td>
		<td style='font-weight:bold;font-size:16px;color:$textColor'>$href{$tbl[1]}</a></td>
	</tr>	
	</table>
	</div>
	";
	
	
	return $tpl->_ENGINE_parse_body($html);
	
	
}

function netconfig(){
	$page=CurrentPageName();
	$html="
	YahooWin2(300,'$page?netconfig={$_GET["nic"]}','{$_GET["nic"]}','');
	
	function ipconfig(eth){
		YahooWin2(390,'$page?ipconfig='+eth+'&nic='+eth,eth,'');
	}
	

	
	";
	return $html;
	}

function netconfig_popup(){
	$eth=$_GET["netconfig"];
	$text_ip=listnicinfos($eth);
	$NAMESERVERS=null;
	
	$ip=new networking();
	$page=CurrentPageName();
	$arrayNic=$ip->GetNicInfos($eth);
	

	$sock=new sockets();
	$type=$sock->getfile("SystemNetworkUse");
	$nicinfos=$sock->getFrameWork("cmd.php?nicstatus=$eth");
	
	$tbl=explode(";",$nicinfos);
	$wire=false;
	if(trim($tbl[5])=="yes"){$wire=true;}		
	

	
	$button=button("{properties}","ipconfig('$eth')");
	if($wire){
		$button="<div style='background-color:#F5F59F;border:1px solid #676767;padding:3px;margin:3px;font-weight:bold'>
		{warning_wireless_nic}
		</div>";
	}
	
	if(is_array($arrayNic["NAMESERVERS"])){
		$NAMESERVERS=implode(",",$arrayNic["NAMESERVERS"]);
	}

	$html="
	
	
	$text_ip
	
	<div class=explain>
	{network_style}:<strong>$type</strong>
	</div>
	<div class=form>
		<H3>{dns_servers}:</H3>
			$NAMESERVERS
			
		</div>	
	
	<div style='margin:4px;text-align:right;'>
		$button
	</div>
	
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}









function AddDNSServer(){
	$sock=new sockets();
	$tpl=new templates();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		
	if($DisableNetworksManagement==1){echo $ERROR_NO_PRIVS;return;}	
	
	$ip=new networking();
	$ip->nameserver_add($_GET["AddDNSServer"]);
	$tpl=new templates();

	
}

function DeleteDNS(){
	
	$sock=new sockets();
	$tpl=new templates();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		
	if($DisableNetworksManagement==1){echo $ERROR_NO_PRIVS;return;}	
	
	$ip=new networking();
	$ip->nameserver_delete($_GET["DeleteDNS"]);

	}
	
	
function virtuals_js(){

	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{virtual_interfaces}");
	$html="
	YahooWin(700,'$page?main=virtuals','$title');
	
	";
	
	echo $html;
	
}

function virtual_js_add(){
	$js=virtuals_js_datas();
	
	$html="
	$js
	VirtualIPAdd();
	";
	
	echo $html;
	
	
}


function virtuals_js_datas(){
	$page=CurrentPageName();
	$tpl=new templates();
	$virtual_interfaces=$tpl->_ENGINE_parse_body('{virtual_interfaces}');
	$tpl=new templates();
	$default_load="VirtualIPRefresh();";
	if(isset($_GET["js-add-nic"])){
		$default_load="VirtualIPJSAdd('{$_GET["js-add-nic"]}');";
	}
	
	
	$sock=new sockets();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	$NoGatewayForVirtualNetWork=$sock->GET_INFO("NoGatewayForVirtualNetWork");
	if(!is_numeric($NoGatewayForVirtualNetWork)){$NoGatewayForVirtualNetWork=0;}
	if(!is_numeric($DisableNetworksManagement)){$DisableNetworksManagement=0;}		
	
	
	
	if($_GET["function-after"]<>null){$function_after="{$_GET["function-after"]}();";}
	$apply_network_configuration_warn=$tpl->javascript_parse_text("{apply_network_configuration_warn}");
	
	$html="
		var windows_size=500;
		var MemFlexGrid=0;
		function VirtualIPAdd(){
			YahooWin2(windows_size,'$page?virtual-popup-add=yes&default-datas={$_GET["default-datas"]}&function-after={$_GET["function-after"]}','$virtual_interfaces');
		
		}
		
		function VirtualIPJSAdd(nic){
			var DisableNetworksManagement=$DisableNetworksManagement;
			if(DisableNetworksManagement==1){alert('$ERROR_NO_PRIVS');return;}
			var defaultDatas='';
			if(document.getElementById('infos_'+nic)){
				defaultDatas=document.getElementById('infos_'+nic).value;
			}
			YahooWin2(windows_size,'$page?virtual-popup-add=yes&default-datas='+defaultDatas,'$virtual_interfaces');
		}
		
		function VirtualsEdit(ID){
			YahooWin2(500,'$page?virtual-popup-add=yes&ID='+ID,'$virtual_interfaces');
		}
		
		var X_CalcCdirVirt= function (obj) {
			var results=obj.responseText;
			document.getElementById('cdir').value=results;
		}		
		
		function CalcCdirVirt(recheck){
			var cdir=document.getElementById('cdir').value;
			if(recheck==0){
				if(cdir.length>0){return;}
			}
			var XHR = new XHRConnection();
			
			XHR.appendData('cdir-ipaddr',document.getElementById('ipaddr').value);
			XHR.appendData('netmask',document.getElementById('netmask').value);
			XHR.sendAndLoad('$page', 'GET',X_CalcCdirVirt);
		}
		
		var X_VirtualIPAddSave= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			YahooWin2Hide();
			if(document.getElementById('main_openvpn_config')){RefreshTab('main_openvpn_config');}
			VirtualIPRefresh();
			$function_after
			
		}
		

		function VirtualIPRefresh(){
			if(MemFlexGrid>0){ $('#table-'+MemFlexGrid).flexReload();return; }
			if(document.getElementById('virtuals-list')){
				LoadAjax('virtuals-list','$page?virtuals-list=yes');
			}
		}
		
		function BuildVirtuals(){
			var DisableNetworksManagement=$DisableNetworksManagement;
			if(DisableNetworksManagement==1){alert('$ERROR_NO_PRIVS');return;}	
			if(confirm('$apply_network_configuration_warn')){
				if(document.getElementById('virtuals-list')){document.getElementById('virtuals-list').innerHTML='<center><img src=img/wait_verybig.gif></center>';}
				
				if(document.getElementById('virtuals-list')){
					LoadAjax('virtuals-list','$page?virtuals-list=yes&build=yes');
				}
			}
		}
		
		function VirtualsDelete(id){
			var DisableNetworksManagement=$DisableNetworksManagement;
			if(DisableNetworksManagement==1){alert('$ERROR_NO_PRIVS');return;}		
			document.getElementById('virtuals-list').innerHTML=\"<center style='margin:10px'><img src='img/wait_verybig.gif'></center>\";
			var XHR = new XHRConnection();
			XHR.appendData('virt-del',id);
			
			XHR.sendAndLoad('$page', 'GET',X_VirtualIPAddSave);
		}
		
		$default_load	
	";
		
	return $html;
}

	
function Virtuals(){
	$page=CurrentPageName();
	$tpl=new templates();
	$virtual_interfaces=$tpl->_ENGINE_parse_body('{virtual_interfaces}');
	$nics=new system_nic();
	if($nics->unconfigured){
		$error="<div class=explain style='color:#d32d2d'>{NIC_UNCONFIGURED_ERROR}</div>";
	}
	
	
	$html="$error
	<div style='float:left'>". imgtootltip("20-refresh.png","{refresh}","VirtualIPRefresh()")."</div>
	
	
	<div id='virtuals-list'></div>	
	<script>
	". virtuals_js_datas()."
	</script>";
	

	echo $tpl->_ENGINE_parse_body($html);	
	
}

function virtual_add_formv6(){
	$ldap=new clladp();
	$sock=new sockets();
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();	
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=0;}	
	$nics=unserialize(base64_decode($sock->getFrameWork("cmd.php?list-nics=yes")));
	$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
	$NoGatewayForVirtualNetWork=$sock->GET_INFO("NoGatewayForVirtualNetWork");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if(!is_numeric($NoGatewayForVirtualNetWork)){$NoGatewayForVirtualNetWork=0;}
	if(!is_numeric($DisableNetworksManagement)){$DisableNetworksManagement=0;}
	$FailOver=0;
	$NoGatewayForVirtualNetWorkExplain=$tpl->javascript_parse_text("{NoGatewayForVirtualNetWorkExplain}");	
	if($users->LinuxDistriCode=="DEBIAN"){
		if(preg_match("#Debian\s+([0-9]+)\.#",$users->LinuxDistriFullName,$re)){
			$DEBIAN_MAJOR=$re[1];
			if($DEBIAN_MAJOR==6){$FailOver=1;}
		}
		
	}
	
	
	$title_button="{add}";
	if(!is_numeric($_GET["ID"])){$_GET["ID"]=0;}
	
	if($_GET["ID"]>0){
		$sql="SELECT * FROM nics_virtuals WHERE ID='{$_GET["ID"]}'";
		$q=new mysql();
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$title_button="{apply}";
	}
	
	if(isset($_GET["default-datas"])){
			$default_array=unserialize(base64_decode($_GET["default-datas"]));
			if(is_array($default_array)){
				$ligne["nic"]=$default_array["NIC"];
			if(preg_match("#(.+?)\.([0-9]+)$#",$default_array["IP"],$re)){
				if($re[2]>254){$re[2]=1;}
				$re[2]=$re[2]+1;
				$ligne["ipaddr"]="{$re[1]}.{$re[2]}";
				$ligne["gateway"]=$default_array["GW"];
				$ligne["netmask"]=$default_array["NETMASK"];
			}
		}
	}

	
	$styleOfFields="font-size:16px;padding:3px";
	$ous=$ldap->hash_get_ou(true);
	$ous["openvpn_service"]="{APP_OPENVPN}";
	
	if($users->crossroads_installed){
		if($EnablePostfixMultiInstance==1){
			$ous["crossroads"]="{load_balancer}";
		}
	}
	
	$AsDebianSystem=1;
	while (list ($num, $val) = each ($nics) ){$nics_array[$val]=$val;}
	if(!$users->AsDebianSystem){$AsDebianSystem=0;}
	$nics_array[null]="{select}";
	
	$ous[null]="{select}";
	
	$nic_field=Field_array_Hash($nics_array,"nic",$ligne["nic"],null,null,0,"font-size:16px;padding:3px");
	$ou_fields=Field_array_Hash($ous,"org",$ligne["org"],null,null,0,"font-size:16px;padding:3px");
	
	$array[0]="{select}";
	$array[12]="/12";$array[13]="/13";$array[14]="/14";$array[15]="/15";$array[16]="/16";$array[17]="/17";$array[18]="/18";$array[19]="/19";$array[20]="/20";$array[21]="/21";$array[22]="/22";$array[23]="/23";$array[24]="/24";$array[25]="/25";$array[26]="/26";$array[27]="/27";$array[28]="/28";$array[29]="/29";$array[30]="/30";$array[31]="/31";$array[32]="/32";$array[33]="/33";$array[34]="/34";$array[35]="/35";$array[36]="/36";$array[37]="/37";$array[38]="/38";$array[39]="/39";$array[40]="/40";$array[41]="/41";$array[42]="/42";$array[43]="/43";$array[44]="/44";$array[45]="/45";$array[46]="/46";$array[47]="/47";$array[48]="/48";$array[49]="/49";$array[50]="/50";$array[51]="/51";$array[52]="/52";$array[53]="/53";$array[54]="/54";$array[55]="/55";$array[56]="/56";$array[57]="/57";$array[58]="/58";$array[59]="/59";$array[60]="/60";$array[61]="/61";$array[62]="/62";$array[63]="/63";$array[64]="/64";$array[104]="/104";$array[120]="/120";$array[128]="/128";
		
	
	
	$html="
	<div id='virtip'>
	". Field_hidden("ID","{$_GET["ID"]}")."
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{nic}:</td>
		<td colspan=2 >$nic_field</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{organization}:</td>
		<td colspan=2>$ou_fields</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{tcp_address} ipv6:</td>
		<td>" . field_text("ipaddr6",$ligne["ipaddr"],$styleOfFields.";width:220px",null,null,null,false)."</td>
		<td>". imgtootltip("arrow-blue-left-24.png","Ipv4 to ipv6","CheckIpV4ToIp26()")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:16px'>{netmask}:</td>
			<td colspan=2>" . Field_array_Hash($array,"netmask",$ligne["netmask"],"blur()",null,0,$styleOfFields)."</td>
		</tr>
		
		<tr>
			<td class=legend style='font-size:16px'>{gateway}:</td>
			<td>" . field_text("gateway_virtual",$ligne["gateway"],$styleOfFields.";width:220px")."</td>
			<td>". imgtootltip("arrow-blue-left-24.png","Ipv4 to ipv6","CheckIpV4ToIp262()")."</td>
		</tr>		
		<tr>
			<td class=legend style='font-size:16px'>{ForceGateway}:</td>
			<td colspan=2>" . Field_checkbox("ForceGateway",1,$ligne["ForceGateway"])."</td>
		</tr>
		
		
	</table>
	</div>
	<div id='infosVirtual' style='font-size:13px'></div>
	<div style='text-align:right'><hr>". button($title_button,"VirtualIPAdd6Save()",18)."</div>
	<script>
		var Netid={$_GET["ID"]};
		var FailOver=$FailOver;
		
		if(Netid>0){
			document.getElementById('ipaddr6').disabled=true;
		}
		
		
		function CheckGateway(){
			var NoGatewayForVirtualNetWork=$NoGatewayForVirtualNetWork;
			var AsDebianSystem=$AsDebianSystem;
			if(AsDebianSystem==0){
				document.getElementById('ForceGateway').disabled=true;
				document.getElementById('ForceGateway').checked=false;
			}
			document.getElementById('gateway_virtual').disabled=false;
			if(NoGatewayForVirtualNetWork==1){
				document.getElementById('gateway_virtual').disabled=true;
				document.getElementById('gateway_virtual').value='';
				document.getElementById('ForceGateway').disabled=true;
				document.getElementById('ForceGateway').checked=false;				
				document.getElementById('gateway_virtual').disabled=true;
				document.getElementById('infosVirtual').innerHTML='$NoGatewayForVirtualNetWorkExplain';
				
			}
			
		}
		
		var X_CheckIpV4ToIp26 = function (obj) {
			var results=obj.responseText;
			if(results.length>3){document.getElementById('ipaddr6').value=results;}
			
		}		
		var X_CheckIpV4ToIp262 = function (obj) {
			var results=obj.responseText;
			if(results.length>3){document.getElementById('gateway_virtual').value=results;}
			
		}			
		
		function CheckIpV4ToIp26(){
			var XHR = new XHRConnection();
			XHR.appendData('CheckIpV4ToIp26',document.getElementById('ipaddr6').value);
			XHR.sendAndLoad('$page', 'POST',X_CheckIpV4ToIp26);
		}
		
		function CheckIpV4ToIp262(){
			var XHR = new XHRConnection();
			XHR.appendData('CheckIpV4ToIp26',document.getElementById('gateway_virtual').value);
			XHR.sendAndLoad('$page', 'POST',X_CheckIpV4ToIp262);		
		}
		
		
		function VirtualIPAdd6Save(){
			var DisableNetworksManagement=$DisableNetworksManagement;
			var NoGatewayForVirtualNetWork=$NoGatewayForVirtualNetWork;
			if(DisableNetworksManagement==1){alert('$ERROR_NO_PRIVS');return;}		
			var XHR = new XHRConnection();
			XHR.appendData('virt-ipv6',1);
			XHR.appendData('virt-ipaddr',document.getElementById('ipaddr6').value);
			XHR.appendData('netmask',document.getElementById('netmask').value);
			if(NoGatewayForVirtualNetWork==0){XHR.appendData('gateway',document.getElementById('gateway_virtual').value);}
			if(NoGatewayForVirtualNetWork==1){XHR.appendData('gateway','');}
			XHR.appendData('nic',document.getElementById('nic').value);
			XHR.appendData('org',document.getElementById('org').value);
			XHR.appendData('ID',document.getElementById('ID').value);
			if(document.getElementById('ForceGateway').checked){XHR.appendData('ForceGateway',1);}else{XHR.appendData('ForceGateway',0);}
			if(document.getElementById('failover')){
				if(document.getElementById('failover').checked){XHR.appendData('failover',1);}else{XHR.appendData('failover',0);}
			}
			MemFlexGrid=$t;
			AnimateDiv('virtip');
			XHR.sendAndLoad('$page', 'POST',X_VirtualIPAddSave);
		}
		
	CheckGateway();
	</script>
	
	";

	echo $tpl->_ENGINE_parse_body($html);

}

function virtual_add_form(){
	$ldap=new clladp();
	$sock=new sockets();
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();	
	$t=$_GET["t"];
	if(!is_numeric($_GET["ID"])){$_GET["ID"]=0;}
	if(!is_numeric($t)){$t=time();}	
	$nics=unserialize(base64_decode($sock->getFrameWork("cmd.php?list-nics=yes")));
	$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
	$NoGatewayForVirtualNetWork=$sock->GET_INFO("NoGatewayForVirtualNetWork");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if(!is_numeric($NoGatewayForVirtualNetWork)){$NoGatewayForVirtualNetWork=0;}
	if(!is_numeric($DisableNetworksManagement)){$DisableNetworksManagement=0;}
	$FailOver=0;
	$NoGatewayForVirtualNetWorkExplain=$tpl->javascript_parse_text("{NoGatewayForVirtualNetWorkExplain}");	
	if($users->LinuxDistriCode=="DEBIAN"){
		if(preg_match("#Debian\s+([0-9]+)\.#",$users->LinuxDistriFullName,$re)){
			$DEBIAN_MAJOR=$re[1];
			if($DEBIAN_MAJOR==6){$FailOver=1;}
		}
		
	}
	
	
	$title_button="{add}";
	if(!is_numeric($_GET["ID"])){$_GET["ID"]=0;}
	
	if($_GET["ID"]>0){
		$sql="SELECT * FROM nics_virtuals WHERE ID='{$_GET["ID"]}'";
		$q=new mysql();
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$title_button="{apply}";
	}
	
	if(isset($_GET["default-datas"])){
			$default_array=unserialize(base64_decode($_GET["default-datas"]));
			if(is_array($default_array)){
				$ligne["nic"]=$default_array["NIC"];
			if(preg_match("#(.+?)\.([0-9]+)$#",$default_array["IP"],$re)){
				if($re[2]>254){$re[2]=1;}
				$re[2]=$re[2]+1;
				$ligne["ipaddr"]="{$re[1]}.{$re[2]}";
				$ligne["gateway"]=$default_array["GW"];
				$ligne["netmask"]=$default_array["NETMASK"];
			}
		}
	}

	if($ligne["metric"]==0){$ligne["metric"]=100+$_GET["ID"];}
	
	$styleOfFields="font-size:24px;padding:3px";
	$ous=$ldap->hash_get_ou(true);
	$ous["openvpn_service"]="{APP_OPENVPN}";
	
	if($users->crossroads_installed){
		if($EnablePostfixMultiInstance==1){
			$ous["crossroads"]="{load_balancer}";
		}
	}
	
	$AsDebianSystem=1;
	while (list ($num, $val) = each ($nics) ){$nics_array[$val]=$val;}
	if(!$users->AsDebianSystem){$AsDebianSystem=0;}
	$nics_array[null]="{select}";
	
	$ous[null]="{select}";
	
	$nic_field=Field_array_Hash($nics_array,"nic",$ligne["nic"],null,null,0,"font-size:24px;padding:3px");
	$ou_fields=Field_array_Hash($ous,"org",$ligne["org"],null,null,0,"font-size:24px;padding:3px");
	$html="
	<div id='virtip' style='width:98%' class=form>
	". Field_hidden("ID","{$_GET["ID"]}")."
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:24px'>{nic}:</td>
		<td>$nic_field</td>
	</tr>
	<tr>
		<td class=legend style='font-size:24px'>{organization}:</td>
		<td>$ou_fields</td>
	</tr>	
	<tr>
			<td class=legend style='font-size:24px'>{tcp_address}:</td>
			
			<td>" . field_ipv4("ipaddr-$t",$ligne["ipaddr"],$styleOfFields,false,"CalcCdirVirt$t(0)")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:24px'>{netmask}:</td>
			<td>" . field_ipv4("netmask-$t",$ligne["netmask"],$styleOfFields,false,"CalcCdirVirt$t(0)")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:24px'>CDIR:</td>
			<td style='padding:-1px;margin:-1px'>
			<table style='width:99%;padding:-1px;margin:-1px'>
			<tr>
			<td width=1%>
			" . Field_text("cdir-$t",$ligne["cdir"],"$styleOfFields;width:225px",null,null,null,false,null,$DISABLED)."</td>
			<td align='left'> ".imgtootltip("img_calc_icon.gif","cdir","CalcCdirVirt$t(1)") ."</td>
			</tr>
			</table></td>
		</tr>	
		<tr>
			<td class=legend style='font-size:24px'>{use_a_gateway}:</td>
			<td>" . Field_checkbox_design("ForceGateway-$t",1,$ligne["ForceGateway"],"CheckGateway$t()")."</td>
		</tr>							
		<tr>
			<td class=legend style='font-size:24px'>{gateway}:</td>
			<td>" . field_ipv4("gateway_virtual-$t",$ligne["gateway"],$styleOfFields,false)."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:24px'>{metric}:</td>
			<td>" . field_text("metric_virtual-$t",$ligne["metric"],"$styleOfFields;width:90px",false)."</td>
		</tr>					
		<tr>
			<td class=legend style='font-size:24px'>failover:</td>
			<td>" . Field_checkbox_design("failover-$t",1,$ligne["failover"],"FaileOverCheck()")."</td>
		</tr>		

		
		
	</table>
	</div>
	<div id='infosVirtual' style='font-size:24px'></div>
	<div style='text-align:right'><hr>". button($title_button,"VirtualIPAddSave$t()",30)."</div>
	<script>
var Netid={$_GET["ID"]};
var FailOver=$FailOver;
var cdir=document.getElementById('cdir-$t').value;
var netmask=document.getElementById('netmask-$t').value;
if(netmask.length>0){ if(cdir.length==0){ CalcCdirVirt$t(0); } }
if(Netid>0){ document.getElementById('ipaddr-$t').disabled=true; }
		
		
function CheckGateway$t(){
	var NoGatewayForVirtualNetWork=$NoGatewayForVirtualNetWork;
	var AsDebianSystem=$AsDebianSystem;
	document.getElementById('gateway_virtual-$t').disabled=true;
	document.getElementById('metric_virtual-$t').disabled=true;

	
	if(NoGatewayForVirtualNetWork==1){
		document.getElementById('ForceGateway-$t').disabled=true;
		document.getElementById('ForceGateway-$t').checked=false;	
		document.getElementById('infosVirtual-$t').innerHTML='$NoGatewayForVirtualNetWorkExplain';	
		return;
	}
	if(document.getElementById('ForceGateway-$t').checked){
		document.getElementById('gateway_virtual-$t').disabled=false;
		document.getElementById('metric_virtual-$t').disabled=false;
	}
			
	document.getElementById('failover-$t').disabled=true;
	if(FailOver==1){document.getElementById('failover-$t').disabled=false;}
}
		
var XVirtualIPAddSave$t=function (obj) {
	var results=obj.responseText;
	var Netid={$_GET["ID"]};
	if(results.length>5){alert(results);return;}	
	if(Netid==0){ YahooWin2Hide();}
	$('#SYSTEM_NICS_VIRTUALS_LIST').flexReload();
}			
		
		
function VirtualIPAddSave$t(){
	var DisableNetworksManagement=$DisableNetworksManagement;
	var NoGatewayForVirtualNetWork=$NoGatewayForVirtualNetWork;	
	var XHR = new XHRConnection();
	XHR.appendData('virt-ipaddr',document.getElementById('ipaddr-$t').value);
	XHR.appendData('netmask',document.getElementById('netmask-$t').value);
	XHR.appendData('cdir',document.getElementById('cdir-$t').value);
	XHR.appendData('metric',document.getElementById('metric_virtual-$t').value);

	if(NoGatewayForVirtualNetWork==0){XHR.appendData('gateway',document.getElementById('gateway_virtual-$t').value);}
	if(NoGatewayForVirtualNetWork==1){XHR.appendData('gateway','');}
	XHR.appendData('nic',document.getElementById('nic').value);
	XHR.appendData('org',document.getElementById('org').value);
	XHR.appendData('ID',document.getElementById('ID').value);
	if(document.getElementById('ForceGateway-$t').checked){XHR.appendData('ForceGateway',1);}else{XHR.appendData('ForceGateway',0);}
	if(document.getElementById('failover-$t')){
		if(document.getElementById('failover-$t').checked){XHR.appendData('failover',1);}else{XHR.appendData('failover',0);}
	}
	MemFlexGrid=$t;
	XHR.sendAndLoad('$page', 'GET',XVirtualIPAddSave$t);
}

function FaileOverCheck(){
	document.getElementById('netmask-$t').disabled=false;
	document.getElementById('gateway_virtual-$t').disabled=false;
	
	if(document.getElementById('failover-$t').checked){
		document.getElementById('netmask-$t').disabled=true;
		document.getElementById('gateway_virtual-$t').disabled=true;					
	}
}
		
		var X_CalcCdirVirt$t= function (obj) {
			var results=obj.responseText;
			document.getElementById('cdir-$t').value=results;
		}		
		
		function CalcCdirVirt$t(recheck){
			var cdir=document.getElementById('cdir-$t').value;
			if(recheck==0){
				if(cdir.length>0){return;}
			}
			var XHR = new XHRConnection();
			XHR.appendData('cdir-ipaddr',document.getElementById('ipaddr-$t').value);
			XHR.appendData('netmask',document.getElementById('netmask-$t').value);
			XHR.sendAndLoad('$page', 'GET',X_CalcCdirVirt$t);
		}		
		
		CheckGateway$t();
		FaileOverCheck();
	</script>
	
	";

	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function virtual_cdir(){
	$ipaddr=$_GET["cdir-ipaddr"];
	$newmask=$_GET["netmask"];
	$ip=new IP();
	
	if($newmask<>null){
		$ipaddr=$ip->maskTocdir($ipaddr, $newmask);
		
		if(preg_match("#([0-9\.]+)\/([0-9]+)#", $ipaddr,$re)){
			$zzr=explode(".",$re[1]);
			echo $zzr[0].".".$zzr[1].".".$zzr[2].".0/{$re[2]}";
		}
		
	}
	
}

function NetWorkBroadCastAsIpAddr(){
	$sock=new sockets();
	$tpl=new templates();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		
	if($DisableNetworksManagement==1){echo $ERROR_NO_PRIVS;return;}	

	$sock->SET_INFO("NetWorkBroadCastAsIpAddr",$_GET["NetWorkBroadCastAsIpAddr"]);
	$sock->SET_INFO("NoGatewayForVirtualNetWork",$_GET["NoGatewayForVirtualNetWork"]);
	
	
}

function virtuals_addv6(){
	$sock=new sockets();
	$tpl=new templates();
	$ipclass=new IP();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		
	if($DisableNetworksManagement==1){echo $ERROR_NO_PRIVS;return;}	
	$NoGatewayForVirtualNetWork=$sock->GET_INFO("NoGatewayForVirtualNetWork");
	if(!is_numeric($NoGatewayForVirtualNetWork)){$NoGatewayForVirtualNetWork=0;}	
	if($_POST["nic"]==null){echo $tpl->_ENGINE_parse_body("{nic}=null");exit;}
	if($_POST["failover"]==1){
		$_POST["gateway"]=$_POST["virt-ipaddr"];
		$_POST["ForceGateway"]=0;
		
	}	
	
	if(!$ipclass->isIPv6($_POST["virt-ipaddr"])){
		echo "{$_POST["virt-ipaddr"]} is not an ipv6 ip address...";
		return;
	}
	
	if($NoGatewayForVirtualNetWork==1){$_POST["gateway"]=null;}
	$q=new mysql();
	if(!is_numeric($_POST["failover"])){$_POST["failover"]=0;}
	if(!is_numeric($_POST["ForceGateway"])){$_POST["ForceGateway"]=0;}
	
	if(!$q->FIELD_EXISTS("nics_virtuals","ForceGateway","artica_backup")){$sql="ALTER TABLE `nics_virtuals` ADD `ForceGateway` TINYINT( 1 ) NOT NULL";$q->QUERY_SQL($sql,'artica_backup');if(!$q->ok){echo $q->mysql_error."\n$sql\n";return;}}		
	if(!$q->FIELD_EXISTS("nics_virtuals","failover","artica_backup")){$sql="ALTER TABLE `nics_virtuals` ADD `failover` TINYINT( 1 ) NOT NULL,ADD INDEX ( `failover` )";$q->QUERY_SQL($sql,'artica_backup');if(!$q->ok){echo $q->mysql_error."\n$sql\n\n";return;}}
	if(!$q->FIELD_EXISTS("nics_virtuals","ipv6","artica_backup")){$sql="ALTER TABLE `nics_virtuals` ADD `ipv6` TINYINT( 1 ) NOT NULL,ADD INDEX ( `ipv6` )";$q->QUERY_SQL($sql,'artica_backup');if(!$q->ok){echo $q->mysql_error."\n$sql\n\n";return;}}	
	$sql="INSERT INTO nics_virtuals (nic,org,ipaddr,netmask,ipv6,gateway,ForceGateway,failover)
	VALUES('{$_POST["nic"]}','{$_POST["org"]}','{$_POST["virt-ipaddr"]}',
	'{$_POST["netmask"]}','1','{$_POST["gateway"]}',{$_POST["ForceGateway"]},{$_POST["failover"]});
	";	

	if($_POST["ID"]>0){
		$sql="UPDATE nics_virtuals SET nic='{$_POST["nic"]}',
		org='{$_POST["org"]}',
		ipaddr='{$_POST["virt-ipaddr"]}',
		netmask='{$_POST["netmask"]}',
		ipv6='1',
		gateway='{$_POST["gateway"]}',
		ForceGateway='{$_POST["ForceGateway"]}',
		failover='{$_POST["failover"]}'
		WHERE ID={$_POST["ID"]}";
	}
	writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){if(preg_match("#Unknown col#i", $q->mysql_error)){$q->BuildTables();$q->QUERY_SQL($sql,"artica_backup");}}
	if(!$q->ok){echo $q->mysql_error."\n$sql\n";}	
	
}

function lastmetric(){
	$q=new mysql();
	$sql="SELECT metric as tcount FROM `nics` WHERE enabled=1 ORDER BY metric DESC LIMIT 0,1";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$hash[$ligne["metric"]]=$ligne["metric"];

	$sql="SELECT metric as tcount FROM `nics_vlan` WHERE enabled=1 ORDER BY metric DESC LIMIT 0,1";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$hash[$ligne["metric"]]=$ligne["metric"];

	$sql="SELECT metric as tcount FROM `nic_virtuals` WHERE enabled=1 ORDER BY metric DESC LIMIT 0,1";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$hash[$ligne["metric"]]=$ligne["metric"];

	krsort($hash[$ligne["metric"]]);
	while (list ($a, $b) = each ($hash) ){
		$f[]=$b;
	}

	return $f[0]+1;

}


function virtuals_add(){
	$sock=new sockets();
	$tpl=new templates();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		
	if($DisableNetworksManagement==1){echo $ERROR_NO_PRIVS;return;}	

	
	if($_GET["nic"]==null){echo $tpl->_ENGINE_parse_body("{nic}=null");exit;}
	$PING=trim($sock->getFrameWork("cmd.php?ping=".urlencode($_GET["virt-ipaddr"])));
	
	if($PING=="TRUE"){
		echo $tpl->javascript_parse_text("{$_GET["virt-ipaddr"]}:\n{ip_already_exists_in_the_network}");
		return;
	}
	
	if($_GET["failover"]==1){
		$_GET["gateway"]=$_GET["virt-ipaddr"];
		$_GET["netmask"]="255.255.255.255";
		$_GET["ForceGateway"]=0;
		
	}
	
	if($_GET["metric"]==0){$_GET["metric"]=lastmetric();}
	
	$NoGatewayForVirtualNetWork=$sock->GET_INFO("NoGatewayForVirtualNetWork");
	if(!is_numeric($NoGatewayForVirtualNetWork)){$NoGatewayForVirtualNetWork=0;}	
	
	if($NoGatewayForVirtualNetWork==1){$_GET["gateway"]=null;}
	$q=new mysql();
	if(!$q->FIELD_EXISTS("nics_virtuals","ForceGateway","artica_backup")){$sql="ALTER TABLE `nics_virtuals` ADD `ForceGateway` TINYINT( 1 ) NOT NULL";$q->QUERY_SQL($sql,'artica_backup');if(!$q->ok){echo $q->mysql_error."\n$sql\n";return;}}		
	if(!$q->FIELD_EXISTS("nics_virtuals","failover","artica_backup")){$sql="ALTER TABLE `nics_virtuals` ADD `failover` TINYINT( 1 ) NOT NULL,ADD INDEX ( `failover` )";$q->QUERY_SQL($sql,'artica_backup');if(!$q->ok){echo $q->mysql_error."\n$sql\n\n";return;}}
	
	$sql="INSERT INTO nics_virtuals (nic,org,ipaddr,netmask,cdir,gateway,ForceGateway,failover,metric)
	VALUES('{$_GET["nic"]}','{$_GET["org"]}','{$_GET["virt-ipaddr"]}','{$_GET["netmask"]}',
	'{$_GET["cdir"]}','{$_GET["gateway"]}',{$_GET["ForceGateway"]},{$_GET["failover"]},{$_GET["metric"]});
	";
	
	if($_GET["ID"]>0){
		$sql="UPDATE nics_virtuals SET nic='{$_GET["nic"]}',
		org='{$_GET["org"]}',
		ipaddr='{$_GET["virt-ipaddr"]}',
		netmask='{$_GET["netmask"]}',
		cdir='{$_GET["cdir"]}',
		gateway='{$_GET["gateway"]}',
		ForceGateway='{$_GET["ForceGateway"]}',
		failover='{$_GET["failover"]}',
		metric='{$_GET["metric"]}'
		WHERE ID={$_GET["ID"]}";
	}
	writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){if(preg_match("#Unknown col#i", $q->mysql_error)){$q->BuildTables();$q->QUERY_SQL($sql,"artica_backup");}}
	if(!$q->ok){echo $q->mysql_error."\n$sql\n";}
	
}

function BuildNetConf(){
	$sock=new sockets();
	writelogs("-> cmd.php?virtuals-ip-reconfigure=yes&stay=no",__FUNCTION__,__FILE__,__LINE__);
	$sock->getFrameWork("system.php?artica-ifup=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{network_sended_require_reboot}",1);
}

function virtuals_list(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$html="
	<div id='$t' style='width:100%;margin:-5px'></div>
	<script>
		LoadAjax('$t','system.nic.virtuals.php');
	</script>
	
	";
	echo $html;
}

function virtuals_del(){
	
	$sock=new sockets();
	$tpl=new templates();
	$q=new mysql();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		
	if($DisableNetworksManagement==1){echo $ERROR_NO_PRIVS;return;}		
	
		$sql="SELECT * FROM nics_virtuals WHERE ID='{$_GET["ID"]}'";
		
		$q=new mysql();
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
		$ipaddr=$ligne["ipaddr"];
		$eth="{$ligne["nic"]}:{$ligne["ID"]}";
		$main=new maincf_multi(null,null,$ipaddr);
		if($main->myhostname<>null){
			echo $tpl->javascript_parse_text("{cannot_delete_address_postfix_instance}:\n$main->myhostname\n{organization}\n$main->ou\n");
			return;
		}
		
		$sql="SELECT hostname,ou FROM samba_hosts WHERE ipaddr='$ipaddr'";
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
		if($ligne["hostname"]<>null){
			echo $tpl->javascript_parse_text("{cannot_delete_address_samba_instance}:\n{$ligne["hostname"]}\n{organization}\n{$ligne["ou"]}\n");
			return;
		}
	
		if(!is_numeric(trim($_GET["virt-del"]))){return ;}
		$sql="DELETE FROM nics_virtuals WHERE ID={$_GET["virt-del"]}";
		
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
		
		$sql="DELETE FROM iptables_bridge WHERE nics_virtuals_id={$_GET["virt-del"]}";
		$q=new mysql();
		$q->QUERY_SQL($sql,"artica_backup");
		
		$sql="DELETE FROM crossroads_smtp WHERE ipaddr='{$_GET["virt-del"]}'";
		$sock=new sockets();
		$sock->getFrameWork("network.php?down-interface=$eth");
		
		if(!$q->ok){echo $q->mysql_error;return;}
		
}


function ConstructVirtsIP(){
	$nic=new system_nic();
	$nic->ConstructVirtsIP();
}



function Bridges(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$users=new usersMenus();
	$TB_HEIGHT=500;
	$TB_WIDTH=870;
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		
		
	$t=time();
	$action_delete_rule=$tpl->javascript_parse_text("{action_delete_rule}");
	$enable=$tpl->_ENGINE_parse_body("{enable}");
	$compile_rules=$tpl->_ENGINE_parse_body("{compile_rules}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$error_want_operation=$tpl->javascript_parse_text("{error_want_operation}");
	$events=$tpl->javascript_parse_text("{events}");
	$new_rule=$tpl->javascript_parse_text("{new_rule}");
	$title=$tpl->javascript_parse_text("{video_title}");
	$size=$tpl->javascript_parse_text("{size}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$to=$tpl->_ENGINE_parse_body("{to}");
	$from=$tpl->_ENGINE_parse_body("{from}");
	$rules=$tpl->_ENGINE_parse_body("{rules}");
	//client,hostname,website,category,rulename
	$VIRTUAL_BRIDGES_EXPLAIN=$tpl->_ENGINE_parse_body("{VIRTUAL_BRIDGES_EXPLAIN}");
		
	$search="	searchitems : [
		{display: '$client', name : 'client'},
		{display: '$hostname', name : 'hostname'},
		{display: '$website', name : 'website'},
		{display: '$category', name : 'category'},
		{display: '$rulename', name : 'rulename'},
		

	],";
	
	$buttons="
	buttons : [
	
	{name: '$new_rule', bclass: 'Add', onpress : ItemAdd$t},
	
	],	";
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?bridges-list=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'hostname', width :31, sortable : false, align: 'center'},
		{display: '$from', name : 'nics_virtuals_id', width :340, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'hostname', width :31, sortable : false, align: 'center'},
		{display: '$to', name : 'nic_linked', width :317, sortable : true, align: 'left'},
		{display: '$rules', name : 'none', width :31, sortable : false, align: 'center'},
		{display: '$delete', name : 'del', width :31, sortable : true, align: 'center'},		
	],
	$buttons


	sortname: 'ID',
	sortorder: 'desc',
	usepager: true,
	title: '<span id=\"title-$t\"></span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});
	function ItemAdd$t(){
		YahooWin('700','$page?bridges-add-form=yes&t=$t','$new_rule');
	}

		function ItemHelp$t(){
			//s_PopUpFull('http://www.mail-appliance.org/index.php?cID=339','1024','900');
		}
		function BridgeRefresh(){
			$('#flexRT$t').flexReload();
			
		}
		
		function BridgeRules(ID){
			YahooWin('700','$page?bridges-rules='+ID,'$rules::'+ID);
		}
		


		var X_BridgeDelete= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);return;}
			$('#row'+mem$t).remove();
		}		
		
		function BridgeDelete(ID,mid){
			mem$t=mid;
			var DisableNetworksManagement=$DisableNetworksManagement;
			if(DisableNetworksManagement==1){alert('$ERROR_NO_PRIVS');return;}		
			var XHR = new XHRConnection();
			XHR.appendData('bridge-del',ID);
			XHR.sendAndLoad('$page', 'GET',X_BridgeDelete);
		}		

</script>";
	
	echo $html;
}



function Bridges_form_add(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sql="SELECT * FROM nics_virtuals ORDER BY ID DESC";
	$q=new mysql();
	$sock=new sockets();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	$nics_array[null]="{select}";
	$nics_virtual[null]="{select}";
	$t=$_GET["t"];
	$sock=new sockets();
	$tpl=new templates();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		
		
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
		
		$eth="{$ligne["nic"]}:{$ligne["ID"]}";
		$nics_virtual[$ligne["ID"]]="$eth ({$ligne["ipaddr"]})";
		$nics_array[$eth]=$eth;
	}
	
	$tcp=new networking();
	$datas=unserialize(base64_decode($sock->getFrameWork("cmd.php?list-nics=yes")));
	$tcp=new networking();
	
while (list ($num, $val) = each ($datas) ){
		$infos=$tcp->GetNicInfos($val);
		$nics_array[$val]=" $val ({$infos["IPADDR"]})";
	}
	$rules=$tpl->_ENGINE_parse_body("{rules}");
	$html="
	<div class=explain style='font-size:16px'>{VIRTUAL_BRIDGES_EXPLAIN}</div>
	<center id='id-$t'></center>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px' width=1% nowrap>{from}:</td>
		<td width=1% nowrap>". Field_array_Hash($nics_virtual,"VirtualID-$t",null,null,null,0,"font-size:16px;padding:3px")."</td>
		<td class=legend style='font-size:16px' width=1% nowrap>{to}:</td>
		<td width=1%>". Field_array_Hash($nics_array,"RealInterface-$t",null,null,null,0,"font-size:16px;padding:3px")."</td>
	</tr>
	<tr>
		<td colspan=4 align='right'><hr>". button("{add_bridge}","BridgeAdd$t()",18)."</td>
		
	</tr>
	</table>
	<script>
		var X_BridgeAdd$t= function (obj) {
			var results=obj.responseText;
			document.getElementById('id-$t').innerHTML='';
			if(results.length>5){alert(results);return;}
			$('#flexRT$t').flexReload();
			YahooWinHide();
		}
		
		function BridgeAdd$t(){
			var DisableNetworksManagement=$DisableNetworksManagement;
			if(DisableNetworksManagement==1){alert('$ERROR_NO_PRIVS');return;}
			var XHR = new XHRConnection();
			XHR.appendData('bridge-add','yes');
			XHR.appendData('VirtualID',document.getElementById('VirtualID-$t').value);
			XHR.appendData('RealInterface',document.getElementById('RealInterface-$t').value);
			AnimateDiv('id-$t');
			XHR.sendAndLoad('$page', 'GET',X_BridgeAdd$t);
		}
	</script>
	
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function Bridges_add(){
	
	$sock=new sockets();
	$tpl=new templates();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		
	if($DisableNetworksManagement==1){echo $ERROR_NO_PRIVS;return;}
	$_GET["RealInterface"]=trim($_GET["RealInterface"]);
	$_GET["VirtualID"]=trim($_GET["VirtualID"]);
	if(trim($_GET["VirtualID"])==null){return;}
	if(trim($_GET["RealInterface"])==null){return;}
	$md5=md5(trim($_GET["RealInterface"]).trim($_GET["VirtualID"]));
	
	$sql="INSERT INTO iptables_bridge (`nics_virtuals_id`,`nic_linked`,`zmd5`) VALUES ('{$_GET["VirtualID"]}','{$_GET["RealInterface"]}','$md5')";
	writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?virtual-ip-build-bridges=yes");	
	
	
}
function Bridges_del(){
	
	$sock=new sockets();
	$tpl=new templates();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		
	if($DisableNetworksManagement==1){echo $ERROR_NO_PRIVS;return;}	
	
	if(!is_numeric(trim($_GET["bridge-del"]))){echo "{$_GET["bridge-del"]} not a numeric";return;}
	$sql="DELETE FROM iptables_bridge WHERE ID={$_GET["bridge-del"]}";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?virtual-ip-build-bridges=yes");
}

function Bridges_list(){
	
	
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$users=new usersMenus();
	$sock=new sockets();
	$xtime=$_GET["xtime"];
	$table="iptables_bridge";
	$search='%';
	$database="artica_backup";	
	$page=1;
	$FORCE_FILTER=null;
	$tcp=new networking();
	
	if(!$q->TABLE_EXISTS($table, $database)){json_error_show("`$table` doesn't exists...");}
	if($q->COUNT_ROWS($table, $database)==0){json_error_show("No rule");}

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);}	

	while ($ligne = mysql_fetch_assoc($results)) {
	$zmd5=md5(serialize($ligne));
	$color="black";
	$ipaddrinfos=VirtualNicInfosIPaddr($ligne["nics_virtuals_id"]);
	$nic_linked=$ligne["nic_linked"];
	$infos=$tcp->GetNicInfos($nic_linked);	
	$rulesIcon=imgsimple("script-32.png","{rules}","BridgeRules({$ligne["ID"]})");
	$delete=imgsimple("delete-24.png","{delete}","BridgeDelete({$ligne["ID"]},'$zmd5')");
	$data['rows'][] = array(
		'id' => "$zmd5",
		'cell' => array(
			"<span style='font-size:16px;color:$color'><img src='img/folder-network-32.png'></span>",
			"<span style='font-size:16px;color:$color'>{$ipaddrinfos["ETH"]} ({$ipaddrinfos["IPADDR"]})</span>",
			"<span style='font-size:16px;color:$color'><img src='img/arrow-right-32.png'></span>",
			"<span style='font-size:16px;color:$color'>$nic_linked ({$infos["IPADDR"]})</strong></span>",
			"<span style='font-size:16px;color:$color'>$rulesIcon</span>",
			"<span style='font-size:16px;color:$color'>$delete</span>",
			)
		);
	}
	
	
echo json_encode($data);

}

function Bridges_rules(){
	$sock=new sockets();
	$tpl=new templates();
	$datas=unserialize(base64_decode($sock->getFrameWork("cmd.php?iptables-bridge-rules={$_GET["bridges-rules"]}")));
	$html="<table cellspacing='0' cellpadding='0' border='0' class='tableView'>
<thead class='thead'>
	<tr>
	<th>{rules}</th>
	</tr>
</thead>";	
	if(is_array($datas)){
	while (list ($num, $val) = each ($datas) ){
	$html=$html."
		<tr class=$classtr>
		<td><code style='font-size:12px'>$val</code></td>
		</tr>";	
		
	}
	}else{
		echo $tpl->_ENGINE_parse_body("<H2>{error_no_datas}</H2>");
	}
	
$html=$html."</table>";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}


function DNS_SERVERS_POPUP(){
$tpl=new templates();
$page=CurrentPageName();
$sock=new sockets();
$resolv=new resolv_conf();
$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
if(!is_numeric($DisableNetworksManagement)){$DisableNetworksManagement=0;}
$EnableDNSMASQ=intval($sock->GET_INFO("EnableDNSMASQ"));
$apply_to_the_system=$tpl->javascript_parse_text("{apply_to_the_system}");

//


$t=time();
	if(!$resolv->isValidDomain($resolv->MainArray["DOMAINS1"])){$resolv->MainArray["DOMAINS1"]="localhost.local";}
	
$PRIMARY_DNS="<tr>
		<td class=legend style='font-size:26px' nowrap>{primary_dns}:</td>
		<td>". field_ipv4("DNS1", $resolv->MainArray["DNS1"],"font-size:26px")."</td>
		</tr>";	
	
	$page=CurrentPageName();
	
	if($EnableDNSMASQ==1){
		$PRIMARY_DNS="<tr style='height:50px'>
		<td class=legend style='font-size:26px' nowrap>{primary_dns}:</td>
		
		<td>
		<a href=\"javascript:blur();\" 
		OnClick=\"javascript:GotoNetworkDNSMASQ();\"
		 style='font-size:26px;text-decoration:underline;font-weight:bold'>127.0.0.1</a>
		</td>
		</tr>
		<tr>
		<td class=legend style='font-size:26px' nowrap>{primary_dns}:</td>
		<td>". field_ipv4("DNS1", $resolv->MainArray["DNS1"],"font-size:26px")."</td>
		</tr>";
		
		
	}
	
	
	$html="
	<center id='$t' style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
	<td valign='top'>
		<table style='width:99%'>
		$PRIMARY_DNS
		<tr>
		<td class=legend style='font-size:26px' nowrap>{secondary_dns}:</td>
		<td>". field_ipv4("DNS2", $resolv->MainArray["DNS2"],"font-size:26px")."</td>
		</tr>
		<tr>
		<td class=legend style='font-size:26px' nowrap>{nameserver} 3:</td>
		<td>". field_ipv4("DNS3", $resolv->MainArray["DNS3"],"font-size:26px")."</td>
		</tr>	
		</tr>
		<tr><td colspan=2 style='font-size:26px'><p>&nbsp;</p></td></tr>
		<tr>
			<td class=legend style='font-size:26px' nowrap>{InternalDomain} 1:</td>
			<td>". Field_text("DOMAINS1", $resolv->MainArray["DOMAINS1"],"font-size:26px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:26px' nowrap>{InternalDomain} 2:</td>
			<td>". Field_text("DOMAINS2", $resolv->MainArray["DOMAINS2"],"font-size:26px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:26px' nowrap>{InternalDomain} 3:</td>
			<td>". Field_text("DOMAINS3", $resolv->MainArray["DOMAINS3"],"font-size:26px")."</td>
		</tr>
		<tr><td colspan=2 style='font-size:26px'><p>&nbsp;</p></td></tr>	
		<tr>
			<td class=legend style='font-size:26px'>{xtimeout}:</td>
			<td style='font-size:26px'>". Field_text("TIMEOUT", $resolv->MainArray["TIMEOUT"],"font-size:26px;width:60px")."&nbsp;{seconds}</td>
		</tr>
		<tr>
			<td class=legend style='font-size:26px'>{max-attempts}:</td>
			<td style='font-size:26px'>". Field_text("ATTEMPTS", $resolv->MainArray["ATTEMPTS"],"font-size:26px;width:60px")."&nbsp;{times}</td>
		</tr>
		<tr>
			<td class=legend style='font-size:26px'>{UseRotation}:</td>
			<td>". Field_checkbox_design("USEROTATION",1,$resolv->MainArray["USEROTATION"])."</td>
		</tr>	
		</table>
		</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>
			". button("{apply}", "SaveResolvConf()",40)."</td>
	</tr>				
	</table>
	
	</center>
	
	
	<script>
	
		var x_SaveResolvConf= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			if(!confirm('$apply_to_the_system')){return;}
			Loadjs('network.restart.php');
		}		
		function SaveResolvConf(){
			var XHR = new XHRConnection();
			XHR.appendData('DNS1',document.getElementById('DNS1').value);
			XHR.appendData('DNS2',document.getElementById('DNS2').value);
			XHR.appendData('DNS3',document.getElementById('DNS3').value);
			XHR.appendData('DOMAINS1',document.getElementById('DOMAINS1').value);
			XHR.appendData('DOMAINS2',document.getElementById('DOMAINS2').value);
			XHR.appendData('DOMAINS3',document.getElementById('DOMAINS3').value);
			
			XHR.appendData('TIMEOUT',document.getElementById('TIMEOUT').value);
			XHR.appendData('ATTEMPTS',document.getElementById('ATTEMPTS').value);
			if(document.getElementById('USEROTATION').checked){XHR.appendData('USEROTATION',1);}else{XHR.appendData('USEROTATION',0);}
			XHR.sendAndLoad('$page', 'POST',x_SaveResolvConf);
				
		}	
		
		
		
function LockServs(){
	var EnableDNSMASQ=$EnableDNSMASQ;
	if(EnableDNSMASQ==1){
		document.getElementById('DNS1').disabled=true;
		document.getElementById('DNS2').disabled=true;
		document.getElementById('DNS3').disabled=true;
	}
			
}
LockServs();
</script>";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function DNS_SERVERS_SAVE(){
	$resolv=new resolv_conf();
	
	while (list ($num, $val) = each ($_POST) ){
		$resolv->MainArray[$num]=$val;
		
	}
	
	$resolv->save();
	$sock=new sockets();
	$sock->getFrameWork("services.php?resolvConf=yes");
	
}


function NetworkManager_check(){
	
	$nic=new system_nic();
	if($nic->unconfigured){
		$tpl=new templates();
		$error="<div class=explain style='color:#d32d2d'>{NIC_UNCONFIGURED_ERROR}</div>";
		echo $tpl->_ENGINE_parse_body($error);
	}
	
}

function RebuildMyNic(){
	$eth=$_POST["RebuildMyNic"];
	$sock=new sockets();
	$sock->getFrameWork("network.php?ifup-ifdown=$eth");
	
}

function OVHNetConfig(){
	$sock=new sockets();
	$sock->SET_INFO("OVHNetConfig", $_POST["OVHNetConfig"]);
}
function CheckIpV4ToIp26(){
	$ipt=$_POST["CheckIpV4ToIp26"];
	$ip=new IP();
	if($ip->isIPv4($ipt)){
		echo $ip->IPv4To6($ipt);
	}
	
}
//if(isset($_GET["cdir-ipaddr"])){virtual_cdir();exit;}
	

