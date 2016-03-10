<?php
$GLOBALS["ICON_FAMILY"]="SYSTEM";
session_start();
include_once('ressources/class.templates.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.openvpn.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.tcpip.inc');

$users=new usersMenus();
if(!$users->AsSystemAdministrator){die("alert('no access');");}

	if(isset($_GET["popup"])){tabs();exit();}
	if(isset($_GET["server"])){server();exit();}
	if(isset($_GET["clients"])){clients();exit();}
	if(isset($_POST["ENABLE_SERVER"])){SAVE_SERVER();exit;}
	if(isset($_POST["IP_START"])){SAVE_SERVER();exit;}
	if(isset($_POST["EnableOpenVPNEndUserPage"])){EnableOpenVPNEndUserPageSave();exit;}
	
	
js();



function js(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{OPENVPN_SERVER_SETTINGS}");
	$html="YahooWin4('1050','$page?popup=yes','$title')";
	echo $html;
	
}


function tabs(){
	
	$page=CurrentPageName();
	if($html<>null){echo $html;}
	$array["server"]="{server}";
	$array["clients"]="{clients}";
	//$array["adv"]="{clients}";


	
	
	while (list ($num, $ligne) = each ($array) ){
		$tab[]="<li><a href=\"$page?$num=yes\"><span style='font-size:22px'>$ligne</span></a></li>\n";
			
		}
	$tpl=new templates();
	
	

	$html="
		<div id='main_openvpn_config2' style='background-color:white;'>
		<ul>
		". implode("\n",$tab). "
		</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_openvpn_config2').tabs();
			

			});
		</script>
	
	";
		
	$tpl=new templates();
	$html=$tpl->_ENGINE_parse_body($html);
	
	echo $html;	
	
	
}


function SAVE_SERVER(){
	$tpl=new templates();
   if(trim($vpn->main_array["GLOBAL"]["IP_START"])==null){$vpn->main_array["GLOBAL"]["IP_START"]="10.8.0.0";}
   if(preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)\.#", $vpn->main_array["GLOBAL"]["IP_START"],$re)){
   	$IP_START_CONFIG="{$re[1]}.{$re[2]}.{$re[3]}";
   }
	
	if(isset($_POST["IP_START"])){
		preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)\.#", $_POST["IP_START"],$re);
		$startip="{$re[1]}.{$re[2]}.{$re[3]}";
		$ips=new networking();
		$ipsArray=$ips->ALL_IPS_GET_ARRAY();
		while (list ($localip, $ligne) = each ($ipsArray) ){
			preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)\.#", $localip,$re);
			$currentIP="{$re[1]}.{$re[2]}.{$re[3]}";
			if($currentIP==$IP_START_CONFIG){continue;}
			if($currentIP==$startip){
				echo $tpl->javascript_parse_text("{ERROR_OPENVPN_CLIENTISSAMEIPSERVER}\n`$currentIP` = `$startip`");
				$_POST["IP_START"]="10.8.0.0";
				return;
			}
		}
	}


	
	$vpn=new openvpn();
	while (list ($num, $ligne) = each ($_POST) ){
		$vpn->main_array["GLOBAL"][$num]=$ligne;
		
	}
	$vpn->Save(true);	

}

function server(){
	$vpn=new openvpn();
	$nic=new networking();
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$OpenVpnPasswordCert=$sock->GET_INFO("OpenVpnPasswordCert");
	if($OpenVpnPasswordCert==null){$OpenVpnPasswordCert="MyKey";}	
	
	
while (list ($num, $ligne) = each ($nic->array_TCP) ){
	if($ligne==null){continue;}
	if(preg_match("#^tun[0-9]+#", $num)){continue;}
	$ips[$ligne]="$ligne ($num)";
	$arr[$num]=$num;
	$ipeth[$num]="$num ($ligne)"; 
	}
	$ips["127.0.0.1"]="{loopback}";
$ips[null]="{all}";	
$ipeth[null]="{none}";	
$nics=Field_array_Hash($arr,'BRIDGE_ETH',$vpn->main_array["GLOBAL"]["BRIDGE_ETH"],'OpenVPNChangeNIC()');
$ips=Field_array_Hash($ips,'LOCAL_BIND',$vpn->main_array["GLOBAL"]["LOCAL_BIND"],null,null,0,'font-size:22px;padding:3px');
$IPTABLES_ETH=Field_array_Hash($ipeth,'IPTABLES_ETH',$vpn->main_array["GLOBAL"]["IPTABLES_ETH"],null,null,0,'font-size:14px;padding:3px');
$protocol=Field_array_Hash(array("tcp"=>"TCP","udp"=>"UDP"),"LISTEN_PROTO",$vpn->main_array["GLOBAL"]["LISTEN_PROTO"],null,null,0,'font-size:22px;padding:3px');
$CLIENT_NAT_PORT=$vpn->main_array["GLOBAL"]["CLIENT_NAT_PORT"];
if($CLIENT_NAT_PORT==null){$CLIENT_NAT_PORT=$vpn->main_array["GLOBAL"]["LISTEN_PORT"];}

if(intval($vpn->main_array["GLOBAL"]["LISTEN_PORT"])==0){$vpn->main_array["GLOBAL"]["LISTEN_PORT"]=1194;}

$sql="SELECT CommonName FROM sslcertificates ORDER BY CommonName";
$q=new mysql();
include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");
$squid_reverse=new squid_reverse();
$sslcertificates=$squid_reverse->ssl_certificates_list();
$sslcertificateF=Field_array_Hash($sslcertificates,"OPENVPN_CERTIFICATE", $vpn->main_array["GLOBAL"]["CERTIFICATE"],"style:font-size:22px");

//
$html="

<div id='openvpnserverform'>
<div style='font-size:32px'><strong>{service_parameters}</strong></div>
<div style='width:98%' class=form>
		<input type='hidden' id='ENABLE_SERVER' value='{$vpn->main_array["GLOBAL"]["ENABLE_SERVER"]}'>
<table style='width:99%'>
<tr>
	<td class=legend style='font-size:22px' id='LOCAL_BIND_FIELD'>{openvpn_local}:</td>
	<td>$ips</td>
	<td>". help_icon("{openvpn_local_text}"). "</td>
</tr>
<tr>
	<td class=legend style='font-size:22px' id='LISTEN_PORT_FIELD'>{listen_port}:</td>
	<td>" . Field_text('LISTEN_PORT',intval($vpn->main_array["GLOBAL"]["LISTEN_PORT"]),'width:190px;font-size:22px;padding:3px')."</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class=legend style='font-size:22px' id='LISTEN_PROTO_FIELD'>{protocol}:</td>
	<td>$protocol</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class=legend style='font-size:22px'>{certificate}:</td>
	<td>$sslcertificateF</td>
	<td>&nbsp;</td>
</tr>
<tr><td colspan=3 align='right'><hr>". button("{apply}","SaveOpenVpnServerParams()",32)."</td></tr>
</table>
<div style='font-size:32px'><strong>{service_informations}</strong></div>
<div class=explain style='font-size:18px'>{openvpn_ippub_explain}</div>
<table style='width:99%' class=form>
<tr>
	<td class=legend style='font-size:22px' id='PUBLIC_IP_FIELD'>{public_ip_addr}:</td>
	<td style='font-size:22px;'>" .Field_text('PUBLIC_IP',$vpn->main_array["GLOBAL"]["PUBLIC_IP"],'width:220px;;font-size:22px;padding:3px')."</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class=legend style='font-size:22px' id='useProxy'>{reverse_proxy}:</td>
	<td style='font-size:22px;'>" .Field_checkbox('USE_RPROXY',1,$vpn->main_array["GLOBAL"]["USE_RPROXY"],"CheckUseProxy()")."</td>
	<td>".help_icon("{OPENVPN_EXPLAIN_PROXY}")."</td>
</tr>
<tr>
	<td class=legend style='font-size:22px'>{proxy_addr}:</td>
	<td style='font-size:14px;'>" .Field_text('PROXYADDR',$vpn->main_array["GLOBAL"]["PROXYADDR"],'width:220px;font-size:22px;padding:3px')."</td>
	<td></td>
</tr>
<tr>
	<td class=legend style='font-size:22px'>{proxy_port}:</td>
	<td style='font-size:14px;'>" .Field_text('PROXYPORT',$vpn->main_array["GLOBAL"]["PROXYPORT"],'width:110px;font-size:22px;padding:3px')."</td>
	<td></td>
</tr>

<tr>
	<td class=legend style='font-size:22px' id='CLIENT_NAT_PORT_FIELD'>{listen_port}:</td>			
	<td style='font-size:14px;'>" .Field_text('CLIENT_NAT_PORT',$CLIENT_NAT_PORT,'width:110px;;font-size:22px;padding:3px')."</td>
	<td>&nbsp;</td>
</tr>	
<tr><td colspan=3 align='right'><hr>". button("{apply}","SaveOpenVpnServerParams()",32)."</td></tr>
</table>
</div>
</div>
<script>

var x_SaveOpenVpnServerParams= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	Loadjs('index.openvpn.enable.progress.php');

}


	function SaveOpenVpnServerParams(){
		var XHR = new XHRConnection();
		
		if(document.getElementById('USE_RPROXY').checked){XHR.appendData('USE_RPROXY','1');}else{XHR.appendData('USE_RPROXY','0');}
		XHR.appendData('ENABLE_SERVER',document.getElementById('ENABLE_SERVER').value);
		XHR.appendData('CERTIFICATE',document.getElementById('OPENVPN_CERTIFICATE').value);
		XHR.appendData('LOCAL_BIND',document.getElementById('LOCAL_BIND').value);	
		XHR.appendData('LISTEN_PORT',document.getElementById('LISTEN_PORT').value);	
		XHR.appendData('LISTEN_PROTO',document.getElementById('LISTEN_PROTO').value);
		XHR.appendData('PUBLIC_IP',document.getElementById('PUBLIC_IP').value);
		XHR.appendData('CLIENT_NAT_PORT',document.getElementById('CLIENT_NAT_PORT').value);
		XHR.appendData('PROXYADDR',document.getElementById('PROXYADDR').value);
		XHR.appendData('PROXYPORT',document.getElementById('PROXYPORT').value);
		XHR.sendAndLoad('$page', 'POST',x_SaveOpenVpnServerParams);				
	}
	
	function CheckUseProxy(){
		document.getElementById('PROXYADDR').disabled=true;
		document.getElementById('PROXYPORT').disabled=true;
		if(document.getElementById('USE_RPROXY').checked){
			document.getElementById('PROXYADDR').disabled=false;
			document.getElementById('PROXYPORT').disabled=false;
		}		
	}
	
	
	function DisableOpenVPNFields(){
		var ENABLE_SERVER=document.getElementById('ENABLE_SERVER').value;
		document.getElementById('LOCAL_BIND').disabled=true;
		document.getElementById('LISTEN_PORT').disabled=true;
		document.getElementById('LISTEN_PROTO').disabled=true;
		document.getElementById('PUBLIC_IP').disabled=true;
		document.getElementById('CLIENT_NAT_PORT').disabled=true;
		document.getElementById('OPENVPN_CERTIFICATE').disabled=true;
		
		document.getElementById('LISTEN_PORT_FIELD').style.color='#CCCCCC';
		document.getElementById('LISTEN_PROTO_FIELD').style.color='#CCCCCC';
		document.getElementById('LOCAL_BIND_FIELD').style.color='#CCCCCC';
		document.getElementById('PUBLIC_IP_FIELD').style.color='#CCCCCC';
		document.getElementById('CLIENT_NAT_PORT_FIELD').style.color='#CCCCCC';
		
		
		
		
		if(ENABLE_SERVER==1){
			document.getElementById('LOCAL_BIND').disabled=false;
			document.getElementById('LISTEN_PORT').disabled=false;
			document.getElementById('LISTEN_PROTO').disabled=false;
			document.getElementById('PUBLIC_IP').disabled=false;
			document.getElementById('CLIENT_NAT_PORT').disabled=false;
			document.getElementById('OPENVPN_CERTIFICATE').disabled=false;
			
			
			
			document.getElementById('LISTEN_PORT_FIELD').style.color='#4C535C';
			document.getElementById('LISTEN_PROTO_FIELD').style.color='#4C535C';
			document.getElementById('LOCAL_BIND_FIELD').style.color='#4C535C';
			document.getElementById('PUBLIC_IP_FIELD').style.color='#4C535C';
			document.getElementById('CLIENT_NAT_PORT_FIELD').style.color='#4C535C';
			
		}
	}
DisableOpenVPNFields();
CheckUseProxy();
</script>
";

echo $tpl->_ENGINE_parse_body($html);
	
}

function clients(){
	$vpn=new openvpn();
	$nic=new networking();
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();	
	
	$EnableOpenVPNEndUserPage=$sock->GET_INFO("EnableOpenVPNEndUserPage");
	

while (list ($num, $ligne) = each ($nic->array_TCP) ){
	if($ligne==null){continue;}
	if(preg_match("#^tun[0-9]+#", $num)){continue;}
	$ips[$ligne]="$ligne ($num)";
	$arr[$num]=$num;
	$ipeth[$num]="$num ($ligne)"; 
	}
	$ipeth[null]="{none}";
	
	$IPTABLES_ETH=Field_array_Hash($ipeth,'IPTABLES_ETH',$vpn->main_array["GLOBAL"]["IPTABLES_ETH"],null,null,
			0,'font-size:22px;padding:3px');
   if(trim($vpn->main_array["GLOBAL"]["IP_START"])==null){$vpn->main_array["GLOBAL"]["IP_START"]="10.8.0.0";}
   if(trim($vpn->main_array["GLOBAL"]["NETMASK"])==null){$vpn->main_array["GLOBAL"]["NETMASK"]="255.255.255.0";}
	
	
	
	$html="
<div class=explain id='openvpnserverform2'>{LOCAL_NETWORK} {SERVER_MODE_TUNE}</div>
<div style='width:98%' class=form>	
			<div style='font-size:32px;margin-bottom:30px'><strong>{DHCP_SERVICE_OPENVPN}</strong></div>		
<table style='width:99%' >
<tr>
		<td colspan=3>". Paragraphe_switch_img("{EnableOpenVPNEndUserPage}", 
				"{EnableOpenVPNEndUserPage_explain}","ENABLE_SERVER",$EnableOpenVPNEndUserPage,null,850,
				"EnableOpenVPNEndUserPageCheck()")."</td>
</tr>			
<tr>
		<td colspan=3>". Paragraphe_switch_img("{enable_authentication}", 
				"{enable_authentication_vpn_explain}","LDAP_AUTH",$vpn->main_array["GLOBAL"]["LDAP_AUTH"],null,850
				)."</td>
</tr>	
<tr>
	<td class=legend style='font-size:22px'>{from_ip_address}:</td>
	<td>" . field_ipv4('IP_START',$vpn->main_array["GLOBAL"]["IP_START"],'font-size:22px;')."</td>
	<td>&nbsp;</td>
<tr>
<tr>
	<td class=legend style='font-size:22px'>{netmask}:</td>
	<td>" . field_ipv4('NETMASK',$vpn->main_array["GLOBAL"]["NETMASK"],'font-size:22px;')."</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class=legend style='font-size:22px'>{dns_server} 1:</td>
	<td>" . field_ipv4('VPN_DNS_DHCP_1',$vpn->main_array["GLOBAL"]["VPN_DNS_DHCP_1"],'font-size:22px;')."</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class=legend style='font-size:22px'>{dns_server} 2:</td>
	<td>" . field_ipv4('VPN_DNS_DHCP_2',$vpn->main_array["GLOBAL"]["VPN_DNS_DHCP_2"],'font-size:22px;')."</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class=legend style='font-size:22px'>{remove_server_route}:</td>
	<td>" . Field_checkbox('REMOVE_SERVER_DEFAULT_ROUTE',1,$vpn->main_array["GLOBAL"]["REMOVE_SERVER_DEFAULT_ROUTE"])."</td>
	<td>". help_icon("{remove_server_route_vpn_explain}")."</td>
</tr>
<tr>
	<td class=legend style='font-size:22px'>{openvpn_access_interface}:</td>
	<td>$IPTABLES_ETH</td>
	<td>".help_icon("{openvpn_access_interface_text}")."</td>
</tr>
<tr>
	<td class=legend style='font-size:22px'>{wake_up_ip}:</td>
	<td>" . field_ipv4('WAKEUP_IP',$vpn->main_array["GLOBAL"]["WAKEUP_IP"],'font-size:22px')."</td>
	<td>". help_icon("{vpn_server_wakeupip_client_explain}")."</td>
</tr>
<tr>
	<td colspan=3 align='right'>
		<hr>". button("{apply}","SaveOpenVpnClientsParams()",32)."
	</td>
</tr>			
</table>

<script>

		var x_SaveOpenVpnClientsParams= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue);}
			RefreshTab('main_openvpn_config2');
			RefreshTab('main_openvpn_config');
			}
			
	function EnableOpenVPNEndUserPageCheck(){
		var XHR = new XHRConnection();
		XHR.appendData('EnableOpenVPNEndUserPage',document.getElementById('EnableOpenVPNEndUserPage').value);	
		XHR.sendAndLoad('$page', 'POST');	
	}

	function SaveOpenVpnClientsParams(){
		var XHR = new XHRConnection();
		
		if(document.getElementById('REMOVE_SERVER_DEFAULT_ROUTE').checked){XHR.appendData('REMOVE_SERVER_DEFAULT_ROUTE','1');}else{XHR.appendData('REMOVE_SERVER_DEFAULT_ROUTE','0');}
		XHR.appendData('LDAP_AUTH',document.getElementById('LDAP_AUTH').value);	
		
		
		XHR.appendData('IP_START',document.getElementById('IP_START').value);	
		XHR.appendData('NETMASK',document.getElementById('NETMASK').value);	
		XHR.appendData('IPTABLES_ETH',document.getElementById('IPTABLES_ETH').value);
		XHR.appendData('WAKEUP_IP',document.getElementById('WAKEUP_IP').value);
		XHR.appendData('VPN_DNS_DHCP_1',document.getElementById('VPN_DNS_DHCP_1').value);
		XHR.appendData('VPN_DNS_DHCP_2',document.getElementById('VPN_DNS_DHCP_2').value);
		AnimateDiv('openvpnserverform2');
		XHR.sendAndLoad('$page', 'POST',x_SaveOpenVpnClientsParams);				
	}
	
	function CheckFields(){
		var IPTABLES_ETH='{$vpn->main_array["GLOBAL"]["LOCAL_BIND"]}';
		if(IPTABLES_ETH=='127.0.0.1'){document.getElementById('REMOVE_SERVER_DEFAULT_ROUTE').disabled=true;}
	}
	
	CheckFields();
</script>	

";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function EnableOpenVPNEndUserPageSave(){
	$sock=new sockets();
	$sock->SET_INFO("EnableOpenVPNEndUserPage",$_POST["EnableOpenVPNEndUserPage"]);
	
}
