<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
session_start();
include_once('ressources/class.templates.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.dhcpd.inc');
include_once('ressources/class.system.nics.inc');



if(!GetRights()){		
	$tpl=new templates();
	echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
	die();exit();
	}
	
if(isset($_GET["service-cmds"])){service_cmds_js();exit;}
if(isset($_GET["service-cmds-peform"])){service_cmds_perform();exit;}	
if(isset($_GET["index_dhcp"])){dhcp_index_js();exit;}	
if(isset($_GET["script"])){index_script();exit;}
if(isset($_GET["index_popup"])){index_page();exit;}
if(isset($_GET["dhcp-tab"])){dhcp_switch();exit;}
if(isset($_GET["dhcp-status"])){dhcp_status();exit;}
if(isset($_GET["index_dhcp_popup"])){dhcp_tabs();exit;}


if(isset($_GET["dhcp-enable-js"])){dhcp_enable_js();exit;}
if(isset($_GET["dhcp_enable_popup"])){dhcp_enable();exit;}
if(isset($_GET["dhcp_form"])){echo dhcp_form();exit;}
if(isset($_GET["dhcp-list"])){echo dhcp_computers_scripts();exit;}
if(isset($_GET["dhcp-pxe"])){echo dhcp_pxe_form();exit;}
if(isset($_GET["pxe_enable"])){echo dhcp_pxe_save();exit;}

if(isset($_GET["SaveDHCPSettings"])){dhcp_save();exit;}
if(isset($_POST["EnableDHCPServer"])){dhcp_enable_save();exit;}
if(isset($_GET["AsGatewayForm"])){echo gateway_page();exit;}
if(isset($_GET["gayteway_enable"])){echo gateway_enable();exit;}
if(isset($_POST["EnableArticaAsGateway"])){gateway_save();exit;}
if(isset($_GET["popup-network-masks"])){popup_networks_masks();exit;}
if(isset($_GET["show-script"])){dhcp_scripts();exit;}
if(isset($_POST["RestartDHCPService"])){RestartDHCPService();exit;}
if(isset($_POST["OnlySetGateway"])){OnlySetGateway_save();exit;}


function GetRights(){
	$users=new usersMenus();
	if($users->AsSystemAdministrator){return true;}
	if($users->ASDCHPAdmin){return true;}
}

function index_script(){
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body('{APP_ARTICA_GAYTEWAY}');
	$html="
		YahooWin0(550,'$page?index_popup=yes','$title');
	
	
	";
	
	echo $html;
}


function service_cmds_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$cmd=$_GET["service-cmds"];
	$mailman=$tpl->_ENGINE_parse_body("{APP_DHCP}");
	$title=$tpl->javascript_parse_text("$mailman::{{$cmd}}");
	$html="YahooWin4('650','$page?service-cmds-peform=$cmd','$title');";
	echo $html;
}
function service_cmds_perform(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$datas=unserialize(base64_decode($sock->getFrameWork("dhcpd.php?service-cmds={$_GET["service-cmds-peform"]}")));

	$html="
<div style='width:100%;height:350px;overflow:auto'>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
	<th>{events}</th>
	</tr>
</thead>
<tbody class='tbody'>";

	while (list ($key, $val) = each ($datas) ){
		if(trim($val)==null){continue;}
		if(trim($val=="->")){continue;}
		if(isset($alread[trim($val)])){continue;}
		$alread[trim($val)]=true;
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$val=htmlentities($val);
		$html=$html."
		<tr class=$classtr>
		<td width=99%><code style='font-size:12px'>$val</code></td>
		</tr>
		";


	}

	$html=$html."
	</tbody>
</table>
</div>
<script>
	LoadAjax('dhcp-status','$page?dhcp-status=yes');
</script>

";
	echo $tpl->_ENGINE_parse_body($html);
}

function dhcp_index_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body('{APP_DHCP}');
	$pxe=$tpl->_ENGINE_parse_body('{APP_DHCP} {PXE}');	
	$enable=$tpl->_ENGINE_parse_body("{EnableDHCPServer}");
	
	$sock=new sockets();
	$tpl=new templates();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		
		
	if(isset($_GET["newinterface"])){$newinterface="&newinterface=yes";}
	
	$start="DHCPDGBCONF();";
	if(isset($_GET["in-front-ajax"])){
		$start="DHCPDGBCONF2();";
	}
	
	$html="
		function DHCPDGBCONF(){
		YahooWin2(790,'$page?index_dhcp_popup=yes','$title');
		setTimeout(\"DHCPCOmputers()\",800);
		}
		
		function DHCPDGBCONF2(){
		$('#BodyContent').load('$page?index_dhcp_popup=yes$newinterface');
		setTimeout(\"DHCPCOmputers()\",800);
		}		

		function EnableDHCPServerForm(){
			YahooWin3(650,'$page?dhcp_enable_popup=yes','$enable');
		}
		
		function PxeConfig(){
		YahooWin3(710,'$page?dhcp-pxe=yes','$pxe');
		
		}
		


	function DHCPCOmputers(){
			if(!document.getElementById('dhcpd_lists')){
				setTimeout(\"DHCPCOmputers()\",800);
			}
			LoadAjax('dhcpd_lists','$page?dhcp-list=yes');
		}
		

	
	var x_RestartDHCPService= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
		LoadAjax('dhcp-status','$page?dhcp-status=yes');
		}		
	
	
	function RestartDHCPService(){
		var XHR = new XHRConnection();
		XHR.appendData('RestartDHCPService','yes');
		AnimateDiv('dhcp-status');
		XHR.sendAndLoad('$page', 'POST',x_RestartDHCPService);	
	}
	
		
var x_SavePXESettings= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	PxeConfig();
	}		
	
	
	function SavePXESettings(){
		var DisableNetworksManagement=$DisableNetworksManagement;	
		if(DisableNetworksManagement==1){alert('$ERROR_NO_PRIVS');return;}	
		var XHR = new XHRConnection();
		XHR.appendData('pxe_enable',document.getElementById('pxe_enable').value);
		XHR.appendData('pxe_file',document.getElementById('pxe_file').value);
		XHR.appendData('pxe_server',document.getElementById('pxe_server').value);
		document.getElementById('dhcppxeform').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>';
		XHR.sendAndLoad('$page', 'GET',x_SavePXESettings);	

	}
		
	$start";
	
	echo $html;	
}


function index_page(){
	$bind9=Paragraphe('folder-64-bind9-grey.png','{APP_BIND9}','{APP_BIND9_TEXT}',"",null,210,null,0,false);
	$openvpn=Paragraphe('64-openvpn-grey.png','{APP_OPENVPN}','{APP_OPENVPN_TEXT}',"",null,210,null,0,false);	
	$users=new usersMenus();
	
	
	if($users->dhcp_installed){
		$dhcp=Buildicon64('DEF_ICO_DHCP');
		}
		
	if($users->BIND9_INSTALLED==true){
		$bind9=ICON_BIND9();
	}
	
	
	
	$gateway=Buildicon64('DEF_ICO_GATEWAY');
	
	$tr[]=$gateway;
	$tr[]=$dhcp;
	$tr[]=$bind9;
	$tr[]=$comp;
	$tr[]=$openvpn;
	
	$html=CompileTr2($tr,"form");

	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html,"system.index.php");
}

function dhcp_pxe_form(){
	$sock=new sockets();
	$EnableDHCPFixPxeThinClient=$sock->GET_INFO("EnableDHCPFixPxeThinClient");
	if($EnableDHCPFixPxeThinClient==1){
		echo "<script>
		YahooWin3Hide();
		Loadjs('artica.has.pxe.php');
		</script>
		";return;}
		
		
	$dhcp=new dhcpd();
	$enable=Paragraphe_switch_img('{enable}','{EnablePXEDHCP}',"pxe_enable",$dhcp->pxe_enable);
	
	$form="<div id='dhcppxeform'>
	<table style='width:100%'>
			<tr>
				<td valign='top'>$enable</td>
			<td valign='top'>
			<table style='width:100%'>
			<tr>
				<td class=legend nowrap style='font-size:16px'>{pxe_file}:</td>
				<td>".Field_text('pxe_file',$dhcp->pxe_file,'width:130px;font-size:16px;padding:3px')."</td>
				<td>&nbsp;</td>
			</tr>	
			<tr>
				<td class=legend nowrap style='font-size:16px'>{pxe_server}:</td>
				<td>".Field_text('pxe_server',$dhcp->pxe_server,'width:130px;font-size:16px;padding:3px')."</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td colspan=3 align='right'><hr>
				". button("{apply}","SavePXESettings()")."
					
				
				</td>
			</tr>					
			
			</table>
			</td>
		</tr>
		</table>";
	$html="
	<div class=explain>{PXE_DHCP_MINI_TEXT}</div>
	$form
	
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}

function dhcp_form(){
	$ldap=new clladp();
	writelogs("Get all domains...",__FUNCTION__,__FILE__,__LINE__);
	$domains=$ldap->hash_get_all_domains();
	writelogs(" -> dhcpd();",__FUNCTION__,__FILE__,__LINE__);
	$dhcp=new dhcpd(0,1);
	writelogs(" -> dhcpd(); FINISH",__FUNCTION__,__FILE__,__LINE__);
	$page=CurrentPageName();
	
	$users=new usersMenus();
	$sock=new sockets();
	$EnableDHCPServer=$sock->GET_INFO('EnableDHCPServer');
	
	$EnableDHCPUseHostnameOnFixed=$sock->GET_INFO('EnableDHCPUseHostnameOnFixed');
	$IncludeDHCPLdapDatabase=$sock->GET_INFO('IncludeDHCPLdapDatabase');
	if(!is_numeric($IncludeDHCPLdapDatabase)){$IncludeDHCPLdapDatabase=1;}
	
	
	
	if(count($domains)==0){
		$dom=Field_text('ddns_domainname',$dhcp->ddns_domainname,"font-size:22px;");
	}else{
		$domains[null]="{select}";
		$dom=Field_array_Hash($domains,'ddns_domainname',$dhcp->ddns_domainname,null,null,null,";font-size:22px;padding:3px");
	}
	
	$nic=$dhcp->array_tcp;
	if($dhcp->listen_nic==null){$dhcp->listen_nic="eth0";}
	
	
	while (list ($num, $val) = each ($nic) ){
		if($num==null){continue;}
		if($num=="lo"){continue;}
		$nics[$num]=$num;
	}
	if($dhcp->listen_nic<>null){
		$nics[$dhcp->listen_nic]=$dhcp->listen_nic;
	}
	$nics[null]='{select}';
	$dnsmasq_installed=0;
	$EnableArticaAsDNSFirst_enabled=0;
	if($users->dnsmasq_installed){ $dnsmasq_installed=1; }
	
	if(($users->BIND9_INSTALLED) OR ($users->POWER_DNS_INSTALLED) OR ($users->dnsmasq_installed) ){
		$EnableArticaAsDNSFirst_enabled=1;
	}	
	
	
	$nicz=new system_nic($dhcp->listen_nic);
	$ipaddrEX=explode(".",$nicz->IPADDR);
	unset($ipaddrEX[3]);
	
	if($dhcp->subnet==null){
		$dhcp->subnet=@implode(".", $ipaddrEX).".0";
	}
	if($dhcp->netmask==null){
		$dhcp->netmask=$nicz->NETMASK;
	}
	if($dhcp->gateway==null){
		$dhcp->gateway=$nicz->GATEWAY;
	}
	if($dhcp->range1==null){
		$dhcp->range1=@implode(".", $ipaddrEX).".50";
	}
	if($dhcp->range2==null){
		$dhcp->range2=@implode(".", $ipaddrEX).".254";
	}
	if($dhcp->broadcast==null){
		$dhcp->broadcast=@implode(".", $ipaddrEX).".255";
	}	
		
		
	$tpl=new templates();
	$DisableNetworksManagement=intval($sock->GET_INFO("DisableNetworksManagement"));
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$EnableArticaAsDNSFirst=Field_checkbox_design("EnableArticaAsDNSFirst",1,$dhcp->EnableArticaAsDNSFirst);
	$EnableDHCPUseHostnameOnFixed=Field_checkbox_design("EnableDHCPUseHostnameOnFixed",1,$EnableDHCPUseHostnameOnFixed);
	$IncludeDHCPLdapDatabase=Field_checkbox_design("IncludeDHCPLdapDatabase",1,$IncludeDHCPLdapDatabase,"OnlySetGatewayFCheck()");
	$authoritative=Field_checkbox_design("DHCPauthoritative",1,$dhcp->authoritative);
	$ping_check=Field_checkbox_design("DHCPPing_check",1,$dhcp->ping_check);
	$get_lease_hostnames=Field_checkbox_design("get_lease_hostnames",1,$dhcp->get_lease_hostnames);
	$html="

			<div id='dhscpsettings' class=form>
				<div class='BodyContent'>
				<input type='hidden' id='EnableDHCPServer' value='$EnableDHCPServer' name='EnableDHCPServer'>
				<table style='width:98%'>

				<tr>
					<td class=legend style='font-size:22px'>". texttooltip("{deny_unkown_clients}","{deny_unkown_clients_explain}").":</td>
					<td>". Field_checkbox_design("deny_unkown_clients", 1,$dhcp->deny_unkown_clients)."</td>
					<td>&nbsp;</td>
				</tr>				
				<tr>
					<td class=legend style='font-size:22px'>". texttooltip("{IncludeDHCPLdapDatabase}","{IncludeDHCPLdapDatabase_explain}").":</td>
					<td>$IncludeDHCPLdapDatabase</td>
					<td>&nbsp;</td>
				</tr>				
				
				<tr>
					<td class=legend style='font-size:22px'>".texttooltip("{EnableDHCPUseHostnameOnFixed}","{EnableDHCPUseHostnameOnFixed_explain}").":</td>
					<td>$EnableDHCPUseHostnameOnFixed</td>
					<td>&nbsp;</td>
				</tr>
				<tr>
					<td class=legend style='font-size:22px'>".texttooltip("{authoritative}","{authoritativeDHCP_explain}").":</td>
					<td>$authoritative</td>
					<td>&nbsp;</td>
				</tr>								
				<tr>
					<td class=legend style='font-size:22px'>".texttooltip("{DHCPPing_check}","{DHCPPing_check_explain}").":</td>
					<td>$ping_check</td>
					<td>&nbsp;</td>
				</tr>
				<tr>
					<td class=legend style='font-size:22px'>".texttooltip("{get_lease_hostnames}","{get_lease_hostnames_text}").":</td>
					<td>$get_lease_hostnames</td>
					<td>&nbsp;</td>
				</tr>	
<tr>
	<td colspan=3>
				<div style='margin:10px;border:1px solid #CCCCCC;pading:10px'>
				<table style='width:100%'>
				<tr>
					<td class=legend style='font-size:22px' colspan=3>{do_no_verify_range}</span>:</td>
					<td>".Field_checkbox_design('do_no_verify_range',1,$dhcp->do_no_verify_range)."&nbsp;</td>
					
				</tr>
				<tr>
					<td class=legend style='font-size:22px;font-weight:bold;width:622px'>{ipfrom}:</td>
					<td>".field_ipv4('range1',$dhcp->range1,'font-size:22px;padding:3px;font-weight:bold')."&nbsp;</td>
					<td>&nbsp;</td>
				</tr>
				<tr>
					<td class=legend style='font-size:22px;font-weight:bold'>{ipto}:</td>
					<td>".field_ipv4('range2',$dhcp->range2,'font-size:22px;padding:3px;font-weight:bold')."&nbsp;</td>
					<td>&nbsp;</td>
				</tr>
				</table>
				</div>					
		</td>
</tr>			

				<tr>
					<td class=legend style='font-size:22px'>{ddns_domainname}:</td>
					<td>$dom</td>
					<td width=1% nowrap>". imgtootltip("plus-16.png",null,"Loadjs('domains.edit.domains.php?js-all-localdomains=yes')")."</td>
				</tr>
				<tr>
					<td class=legend style='font-size:22px'>".texttooltip("{max_lease_time}","{max_lease_time_text}").":</td>
					<td style='font-size:16px'>".Field_text('max_lease_time',$dhcp->max_lease_time,'width:90px;font-size:22px;padding:3px')."&nbsp;{seconds}</td>
					<td>&nbsp;</td>
				</tr>	
				
				<tr>
					<td class=legend style='font-size:22px'>".texttooltip("{wpad_label}","{wpad_label_text}").":</td>
					<td>".Field_text('local-pac-server',$dhcp->local_pac_server,'width:500px;font-size:22px;padding:3px',false)."</td>
					<td>&nbsp;</td>
				</tr>		
				<tr>
					<td class=legend style='font-size:22px'>".texttooltip("{portal_page}","{portal_page_explain}").":</td>
					<td>".Field_text('browser-portal-page',$dhcp->browser_portal_page ,'width:500px;font-size:22px;padding:3px',false)."</td>
					<td>&nbsp;</td>
				</tr>								
				
				<tr>
					<td class=legend style='font-size:22px'>{subnet}:</td>
					<td>".field_ipv4('subnet',$dhcp->subnet,"font-size:22px;padding:3px;font-weight:bold",false)."</td>
					<td>&nbsp;</td>
				</tr>			
				<tr>
					<td class=legend style='font-size:22px'>{netmask}:</td>
					<td>".field_ipv4('netmask',$dhcp->netmask,'font-size:22px;padding:3px;font-weight:bold')."&nbsp;</td>
					<td>&nbsp;</td>
				</tr>
				<tr>
					<td class=legend style='font-size:22px'>{gateway}:</td>
					<td>".field_ipv4('gateway',$dhcp->gateway,'font-size:22px;padding:3px;font-weight:bold')."&nbsp;</td>
					<td>&nbsp;</td>
				</tr>
				<tr>
					<td class=legend style='font-size:22px'>{broadcast}:</td>
					<td>".field_ipv4('broadcast_dhcp_main',$dhcp->broadcast,'font-size:22px;padding:3px')."&nbsp;</td>
					<td>&nbsp;</td>
				</tr>						
				<tr>
					<td class=legend style='font-size:22px'>{DNSServer} 1:</td>
					<td>".field_ipv4('DNS_1',$dhcp->DNS_1,'font-size:22px;padding:3px;font-weight:bold')."&nbsp;</td>
					<td>&nbsp;</td>
				</tr>
				<tr>
					<td class=legend style='font-size:22px'>{DNSServer} 2:</td>
					<td>".field_ipv4('DNS_2',$dhcp->DNS_2,'font-size:22px;padding:3px;font-weight:bold')."&nbsp;</td>
					<td>&nbsp;</td>
				</tr>
				<tr>
					<td class=legend style='font-size:22px'>{wins_server}:</td>
					<td>".field_ipv4('WINSDHCPSERV',$dhcp->WINS,'font-size:22px;padding:3px')."&nbsp;</td>
					<td>&nbsp;</td>
				</tr>					
				<tr>
					<td class=legend style='font-size:22px'>{ntp_server} <span style='font-size:10px'>({optional})</span>:</td>
					<td>".Field_text('ntp_server',$dhcp->ntp_server,'width:228px;font-size:22px;padding:3px')."&nbsp;</td>
					<td>&nbsp;</td>
				</tr>
		
					
				<tr>
					<td colspan=3 align='right'><hr>
					". button("{apply}","SaveDHCPSettings()",40)."
						
					
					</td>
				</tr>		
				</table>
				</div>
			</div>
			<br>
<script>
var x_SaveDHCPSettings= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	UnlockPage();
	Loadjs('dhcpd.progress.php');
	}		
		
	function SaveDHCPSettings(){
		var DisableNetworksManagement=$DisableNetworksManagement;	
		if(DisableNetworksManagement==1){alert('$ERROR_NO_PRIVS');return;}	
		var XHR = new XHRConnection();
		XHR.appendData('SaveDHCPSettings','yes');
		XHR.appendData('range1',document.getElementById('range1').value);
		XHR.appendData('range2',document.getElementById('range2').value);
		XHR.appendData('gateway',document.getElementById('gateway').value);
		XHR.appendData('netmask',document.getElementById('netmask').value);
		XHR.appendData('DNS_1',document.getElementById('DNS_1').value);
		XHR.appendData('DNS_2',document.getElementById('DNS_2').value);
		XHR.appendData('max_lease_time',document.getElementById('max_lease_time').value);
		XHR.appendData('EnableDHCPServer',document.getElementById('EnableDHCPServer').value);
		XHR.appendData('ntp_server',document.getElementById('ntp_server').value);
		XHR.appendData('subnet',document.getElementById('subnet').value);
		XHR.appendData('broadcast',document.getElementById('broadcast_dhcp_main').value);
		XHR.appendData('WINS',document.getElementById('WINSDHCPSERV').value);
		XHR.appendData('local-pac-server',document.getElementById('local-pac-server').value);
		XHR.appendData('browser-portal-page',document.getElementById('browser-portal-page').value);
		
		
		
		XHR.appendData('EnableArticaAsDNSFirst',0);
		
		if(document.getElementById('do_no_verify_range')){
			if(document.getElementById('do_no_verify_range').checked){XHR.appendData('do_no_verify_range',1);}else{XHR.appendData('do_no_verify_range',0);}
		}else{
			XHR.appendData('do_no_verify_range',0);
		}		
		
		
		if(document.getElementById('deny_unkown_clients').checked){XHR.appendData('deny_unkown_clients',1);}else{XHR.appendData('deny_unkown_clients',0);}
		if(document.getElementById('IncludeDHCPLdapDatabase').checked){XHR.appendData('IncludeDHCPLdapDatabase',1);}else{XHR.appendData('IncludeDHCPLdapDatabase',0);}
		if(document.getElementById('EnableDHCPUseHostnameOnFixed').checked){XHR.appendData('EnableDHCPUseHostnameOnFixed',1);}else{XHR.appendData('EnableDHCPUseHostnameOnFixed',0);}
		
		if(document.getElementById('DHCPPing_check').checked){XHR.appendData('DHCPPing_check',1);}else{XHR.appendData('DHCPPing_check',0);}
		if(document.getElementById('DHCPauthoritative').checked){XHR.appendData('DHCPauthoritative',1);}else{XHR.appendData('DHCPauthoritative',0);}
		XHR.appendData('ddns_domainname',document.getElementById('ddns_domainname').value);
		
		LockPage();
		XHR.sendAndLoad('$page', 'GET',x_SaveDHCPSettings);	

	}
</script>							
		
	";

	
	
	return  $tpl->_ENGINE_parse_body($html);		
	
}

function dhcp_computers_scripts(){
	$dhc=new dhcpd();
	$array=$dhc->LoadfixedAddresses();
	if(!is_array($array)){return null;}
	
	$html="
	<table style='width:100%'>
	";
	
	
		$html="
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:99.5%'>
<thead class='thead'>
	<tr>
	<th colspan=4>&nbsp;{fixedHosts}</th>
	</tr>
</thead>
<tbody class='tbody'>";	

	
	while (list ($num, $ligne) = each ($array) ){
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", trim($num))){continue;}
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$ligne["MAC"]=str_replace("hardware ethernet","",$ligne["MAC"]);
		$js=MEMBER_JS("$num$",1,1);
		$html=$html . "
		<tr  class=$classtr>
			<td valign='top'><img src='img/computer-32.png'></td>
			<td><strong><a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:14px'>$num</a></td>
			<td><strong><a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:14px'>{$ligne["MAC"]}</a></td>
			<td><strong><a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:14px'>{$ligne["IP"]}</a></td>
		</tr>
			
		";
		
	}
	
	$html=$html."</tbody></table>";
	$tpl=new templates();
	return RoundedLightWhite($tpl->_ENGINE_parse_body($html));
	
}

function dhcp_scripts(){
	$sock=new sockets();
	
	$datas=base64_decode($sock->getFrameWork("services.php?dhcpd-conf=yes"));
	
	if(trim($datas)==null){
		$dhcp->conf="{ERROR_NO_CONFIG_SAVED}";
	}
	
	$html="
	<textarea style='width:100%;height:500px;border:2px solid #CCCCCC;background-color:white;font-size:16px !important'>$datas</textarea>";
	$tpl=new templates();
	echo  $tpl->_ENGINE_parse_body($html);		
	
}

function dhcp_switch(){
	switch ($_GET["dhcp-tab"]) {
		case "status":dhcp_index();break;
		case "config":echo dhcp_form();break;
		case "tabs":echo dhcp_subtabs();break;
		case "hosts":echo dhcp_computers_scripts();break;
	}
	
	
}

function dhcp_status(){
	$ini=new Bs_IniHandler();
	$page=CurrentPageName();
	$sock=new sockets();
	$ini->loadString(base64_decode($sock->getFrameWork("cmd.php?dhcpd-status=yes")));
	$status=DAEMON_STATUS_ROUND("DHCPD",$ini);
	$restart="<div style='margin-top:15px'><center>". button("{restart_service}","RestartDHCPService()")."</center></div>";
	if($ini->_params["DHCPD"]["running"]==0){$restart=null;}
	

	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body(
	"$status
	<div style='text-align:right'>". imgtootltip("refresh-24.png","{refresh}","LoadAjax('dhcp-status','$page?dhcp-status=yes');")."
	
	");
}

function dhcp_subtabs(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$dhcp=new dhcpd(0,1);
	if($dhcp->listen_nic==null){$dhcp->listen_nic="eth0";}
	$q=new system_nic($dhcp->listen_nic);
	$array["config"]="$dhcp->listen_nic $q->NICNAME - $q->netzone";
	
	$nic=$dhcp->array_tcp;
	
	
	
	while (list ($num, $val) = each ($nic) ){
		if($num==null){continue;}
		if($num=="lo"){continue;}
		if($num==$dhcp->listen_nic){continue;}
		$q=new system_nic($num);
		$array["config-$num"]="$num $q->NICNAME - $q->netzone";
	}
	
	
	$fontsize="font-size:20px";
	
	while (list ($num, $ligne) = each ($array) ){
		
		if(preg_match("#config-(.+)#", $num,$re)){
			$html[]= "<li><a href=\"dhcpd.nic.php?nic={$re[1]}\"><span style='$fontsize'>$ligne</span></a></li>\n";
			continue;
			
		}
		$html[]= "<li><a href=\"$page?dhcp-tab=$num\"><span style='$fontsize'>$ligne</span></a></li>\n";
	}
	
	
	echo build_artica_tabs($html, "main_config_subdhcpd");
	
	
	
	
}

function dhcp_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$array["status"]='{status}';
	$array["tabs"]='{settings}';
	//$array["multi"]='Multi';
	$array["routes"]='{APP_DHCP_ROUTES_CONF}';
	$array["shared-network"]='{groups2}';
	$array["hosts"]='{fixedHosts}';
	$array["requests"]='{requests}';
	$array["leases"]='{leases}';
	$array["events"]='{events}';
	
	
	if(isset($_GET["newinterface"])){
		$newinterface="&newinterface=yes";$newinterfacesuffix="?newinterface=yes";$fontsize="font-size:14px;";}
		$fontsize="font-size:20px";
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="shared-network"){
			$html[]= "<li><a href=\"dhcpd.shared-networks.php$newinterfacesuffix\"><span style='$fontsize'>$ligne</span></a></li>\n";
			continue;
		}
		
		if($num=="multi"){
			$html[]= "<li><a href=\"dhcpd.multi.php$newinterfacesuffix\"><span style='$fontsize'>$ligne</span></a></li>\n";
			continue;
		}		
		
		if($num=="leases"){
			$html[]= "<li><a href=\"dhcpd.leases.php$newinterfacesuffix\"><span style='$fontsize'>$ligne</span></a></li>\n";
			continue;
		}
		
		if($num=="requests"){
			$html[]= "<li><a href=\"dhcpd.requests.php$newinterfacesuffix\"><span style='$fontsize'>$ligne</span></a></li>\n";
			continue;
		}		
		
		if($num=="routes"){
			$html[]= "<li><a href=\"dhcpd-routes.php$newinterfacesuffix\"><span style='$fontsize'>$ligne</span></a></li>\n";
			continue;
		}		

		if($num=="events"){
			$html[]= "<li><a href=\"dhcpd.events-sql.php$newinterfacesuffix\"><span style='$fontsize'>$ligne</span></a></li>\n";
			continue;
		}

		if($num=="hosts"){
			$html[]= "<li><a href=\"dhcpd.fixed.hosts.php$newinterfacesuffix\"><span style='$fontsize'>$ligne</span></a></li>\n";
			continue;
		}
		
		$html[]= "<li><a href=\"$page?dhcp-tab=$num$newinterface\"><span style='$fontsize'>$ligne</span></a></li>\n";
	}
	
	
	echo build_artica_tabs($html, "main_config_dhcpd")."<script>LeftDesign('net-server-white-256-opac20.png');</script>";
}	
	


function dhcp_index(){
	$page=CurrentPageName();
	$config=Paragraphe("64-settings.png","{APP_DHCP_MAIN_CONF}","{APP_DHCP_MAIN_CONF_TEXT}","javascript:YahooWin3(850,'index.gateway.php?show-script=yes','{APP_DHCP_MAIN_CONF}');");
	$pxe=	Paragraphe("pxe-64.png","{PXE}","{PXE_DHCP_MINI_TEXT}","javascript:PxeConfig();");
	
	
	$pcs=Buildicon64('DEF_ICO_BROWSE_COMP');
	$enable=Paragraphe("check-64.png","{EnableDHCPServer}","{EnableDHCPServer_text}",
	"javascript:Loadjs('$page?dhcp-enable-js=yes');","{EnableDHCPServer_text}");
	$title="<div style='font-size:42px;'>{APP_DHCP}</div>";
	$class_from="form";
	
	
	
	
	$html="
$title
	<table style='width:99%' class=$class_from>
		<tr>
			<td valign='top' $statuswidth>
				
				<div id='dhcp-status'></div>
			</td>
			<td valign='top' style='padding:30px'>
					<div id='dhcp-enabled' style='margin-bottom:20px'></div>
			
					<table style='width:100%'>
						<tr>
							
							<td valign='top'>$config</td>
							<td valign='top'>$pxe</td>
							<td valign='top'>$pcs</td>
							
							
						</tr>
						<tr>
							
							
						</tr>
					</table>
			</td>
		</tr>
	</table>


<script>
	LoadAjax('dhcp-status','$page?dhcp-status=yes');
	LoadAjax('dhcp-enabled','$page?dhcp_enable_popup=yes');
</script>


	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function dhcp_pxe_save(){
	
	$sock=new sockets();
	$tpl=new templates();
	
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		
	if($DisableNetworksManagement==1){echo $ERROR_NO_PRIVS;return;}		
	
	$dhcp=new dhcpd();
	while (list ($index, $line) = each ($_GET) ){
		$dhcp->$index=$line;
	}
	$dhcp->Save();
	
}

function  dhcp_enable_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{EnableDHCPServer}");
	echo "YahooWin3('650','$page?dhcp_enable_popup=yes','$title');";
	
}


function dhcp_enable(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$form=Paragraphe_switch_img("{EnableDHCPServer}","{EnableDHCPServer_text}","EnableDHCPServer-$t",
	intval($sock->GET_INFO("EnableDHCPServer")),"EnableDHCPServer_text",890);
	$DisableNetworksManagement=intval($sock->GET_INFO("DisableNetworksManagement"));
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$dhcp=new dhcpd(0,1);
	$nic=$dhcp->array_tcp;
	if($dhcp->listen_nic==null){$dhcp->listen_nic="eth0";}
	
	$EnableDHCPServer=intval($sock->GET_INFO("EnableDHCPServer"));
	
	if($EnableDHCPServer==0){
		
		$button="<center style='margin:50px'>".button("{EnableDHCPServer}","Loadjs('dhcpd.wizard.php')",42)."</center>";
		echo $tpl->_ENGINE_parse_body($button);
		return;
	}
	
	
	while (list ($num, $val) = each ($nic) ){
		if($num==null){continue;}
		if($num=="lo"){continue;}
		$nicz=new system_nic($num);
		$nics[$num]="[$num]: $nicz->NICNAME ($nicz->netzone)";
	}
	
	
	$html="

		$form
		<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:26px'>{interface}:</td>
			<td>".Field_array_Hash($nics, "listen-$t",$dhcp->listen_nic,"style:font-size:26px")."</td>
		</tr>
		</table>
		<div style='text-align:right;width:100%'>
			<HR>
			". button("{apply}","EnableDHCPServerSave$t()",30)."
		</div>

	<script>
		var x_EnableDHCPServerSave$t= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue);}
			if(document.getElementById('main_config_dhcpd')){RefreshTab('main_config_dhcpd');}
			YahooWin3Hide();
			Loadjs('dhcpd.progress.php');
		}			
		
		
		function EnableDHCPServerSave$t(){
			var DisableNetworksManagement=$DisableNetworksManagement;	
			if(DisableNetworksManagement==1){alert('$ERROR_NO_PRIVS');return;}	
			var XHR = new XHRConnection();
			XHR.appendData('EnableDHCPServer',document.getElementById('EnableDHCPServer-$t').value);
			XHR.appendData('listen_nic',document.getElementById('listen-$t').value);
			XHR.sendAndLoad('$page', 'POST',x_EnableDHCPServerSave$t);	
		}
</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function dhcp_enable_save(){
	
	$sock=new sockets();
	$tpl=new templates();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		
	if($DisableNetworksManagement==1){echo $ERROR_NO_PRIVS;return;}		
	
	$dhcp=new dhcpd();
	$sock=new sockets();
	$sock->SET_INFO('EnableDHCPServer',$_POST["EnableDHCPServer"]);
	$dhcp->listen_nic=$_POST["listen_nic"];
	$dhcp->Save(true);
}

function dhcp_save(){
	
	$sock=new sockets();
	$tpl=new templates();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		
	if($DisableNetworksManagement==1){echo $ERROR_NO_PRIVS;return;}		
	
	
	$dhcp=new dhcpd(0,1);
	$sock=new sockets();
	$sock->SET_INFO('EnableDHCPServer',$_GET["EnableDHCPServer"]);
	$sock->SET_INFO('EnableDHCPUseHostnameOnFixed',$_GET["EnableDHCPUseHostnameOnFixed"]);
	$sock->SET_INFO("IncludeDHCPLdapDatabase", $_GET["IncludeDHCPLdapDatabase"]);
	
	
	
	
	
	
	$dhcp->deny_unkown_clients=$_GET["deny_unkown_clients"];
	$dhcp->ddns_domainname=$_GET["ddns_domainname"];
	$dhcp->max_lease_time=$_GET["max_lease_time"];
	$dhcp->get_lease_hostnames=$_GET["get_lease_hostnames"];
	$dhcp->netmask=$_GET["netmask"];
	$dhcp->range1=$_GET["range1"];
	$dhcp->range2=$_GET["range2"];
	$dhcp->subnet=$_GET["subnet"];
	$dhcp->broadcast=$_GET["broadcast"];
	$dhcp->WINS=$_GET["WINS"];
	$dhcp->ping_check=$_GET["DHCPPing_check"];
	$dhcp->authoritative=$_GET["DHCPauthoritative"];
	$dhcp->local_pac_server=$_GET["local-pac-server"];
	$dhcp->browser_portal_page=$_GET["browser-portal-page"];
	 
	
	$tpl=new templates();

	$dhcp->gateway=$_GET["gateway"];
	$dhcp->DNS_1=$_GET["DNS_1"];
	$dhcp->DNS_2=$_GET["DNS_2"];
	$dhcp->ntp_server=$_GET["ntp_server"];

	$dhcp->EnableArticaAsDNSFirst=$_GET["EnableArticaAsDNSFirst"];
	$dhcp->do_no_verify_range=$_GET["do_no_verify_range"];
	$dhcp->Save();
	
	}
	
	
	
function OnlySetGateway_save(){
	$sock=new sockets();
	$sock->SET_INFO("DHCPOnlySetGateway", $_POST["OnlySetGateway"]);
}	
	
	
function gateway_enable(){
	
	$sock=new sockets();
	$tpl=new templates();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		
	if($DisableNetworksManagement==1){echo $ERROR_NO_PRIVS;return;}		
	$EnableArticaAsGateway=$sock->GET_INFO("EnableArticaAsGateway");
	
	$enable=Paragraphe_switch_img('{ARTICA_AS_GATEWAY}','{ip_forward_text}',
			'EnableArticaAsGateway',$EnableArticaAsGateway,null,850);
	$tpl=new templates();
	return $tpl->_ENGINE_parse_body($enable);		
}
function gateway_page(){
	$t=time();
	$page=CurrentPageName();
	$html="
<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td valign='top'><div id='gayteway_enable'>" . gateway_enable()."</div></td>
	</tr>
	<tr>
		<td valign='top' align='right'>
		<hr>". button("{apply}", "Save$t()","30")."
		</td>
	</tr>
	</table>
	</div>
	<script>
var xSave$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	YahooWinHide();
	LoadAjaxRound('system-main-status','admin.dashboard.system.php');
}			
		
		
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('EnableArticaAsGateway',document.getElementById('EnableArticaAsGateway').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);	
}
</script>
	";
	
	$tpl=new templates();
	return $tpl->_ENGINE_parse_body($html);			
}

function gateway_save(){
	
	$sock=new sockets();
	$tpl=new templates();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		
	if($DisableNetworksManagement==1){echo $ERROR_NO_PRIVS;return;}		
	
	$sock=new sockets();
	$sock->SET_INFO("EnableArticaAsGateway", $_POST["EnableArticaAsGateway"]);
	if($_POST["EnableArticaAsGateway"]==1){$sock->getFrameWork("cmd.php?sysctl-setvalue=1&key=".base64_encode("net.ipv4.ip_forward"));}
	
	
}

function popup_networks_masks(){
	include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
	include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
	$net=new networking();
	$class_ip=new IP();
	$array=$net->ALL_IPS_GET_ARRAY();
	while (list ($index, $line) = each ($array) ){
		$ip=$index;
		if(preg_match('#(.+?)\.([0-9]+)$#',$ip,$re)){
			$ip_start=$re[1].".0";
			$ip_end=$re[1].".255";
			$cdir=$class_ip->ip2cidr($ip_start,$ip_end);
			if(preg_match("#(.+)\/([0-9]+)#",$cdir,$ri)){
				$ipv4=new ipv4($ri[1],$ri[2]);
				$netmask=$ipv4->netmask();
				$hosts=$class_ip->HostsNumber($index,$netmask);
				$html=$html."
				<tr>
					<td style='font-size:16px;font-weight:bold'>$ip_start</td>
					<td style='font-size:16px;font-weight:bold'>$netmask</td>
					<td style='font-size:16px;font-weight:bold'>$hosts</td>
					
				</tr>";
			}
		}
		
		
	}
	

	
	$html="<H1>{newtork_help_me}</H1>
	<p class=caption>{you_should_use_one_of_these_network}</p>
	<table style='width:99%' class=form>
	<tr>
		<th>{from_ip_address}</th>
		<th>{netmask}</th>
		<th>{hosts_number}</th>
	</tr>
	$html
	</table>
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	}
function RestartDHCPService(){
	$sock=new sockets();
	$sock->getFrameWork("services.php?restart-dhcpd=yes");
	
}


?>