<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.system.nics.inc');
	
	
	
	
	$usersmenus=new usersMenus();
	if($usersmenus->AsSystemAdministrator==false){exit;}
	if(isset($_POST["SourceBasedRouting"])){SourceBasedRoutingSave();exit;}
	if(isset($_POST["UseSnort"])){UseSnort();exit;}
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["ipconfig-nic"])){ipconfig_nic();exit;}
	if(isset($_POST["ipv6-eth"])){save_nic6();exit;}
	if(isset($_GET["save_nic"])){save_nic();exit;}
	if(isset($_GET["ipconfig-v6"])){ipconfig_nic6();exit;}
	if(isset($_GET["ipconfig-routes"])){ipconfig_routes();exit;}
	if(isset($_GET["ifconfig-route-list"])){ipconfig_routes_list();exit;}
	if(isset($_GET["ifconfig-route-add-js"])){ipconfig_routes_add_js();exit;}
	if(isset($_GET["ifconfig-route-add-popup"])){ipconfig_routes_add_popup();exit;}
	if(isset($_GET["ipconfig-routage"])){ipconfig_routage();exit;}
	if(isset($_GET["nic-delete-js"])){delete_nic_js();exit;}
	if(isset($_POST["delete-nic"])){delete_nic();exit;}
	
	if(isset($_GET["ipconfig-qos"])){ipconfig_qos();exit;}
	
	if(isset($_POST["QOSMAX"])){ipconfig_qos_save();exit;}
	if(isset($_POST["add-routes"])){ipconfig_routes_add();exit;}
	if(isset($_GET["del-routes"])){ipconfig_routes_del();exit;}	
	if(isset($_POST["ipv6-enable"])){UseIpv6();exit;}
	if(isset($_GET["ipconfig-tools"])){ipconfig_tools();exit;}
	if(isset($_GET["flush-arp-cache-js"])){flus_arp_cache_js();;exit;}
	if(isset($_GET["flush-arp-cache-popup"])){flus_arp_cache_popup();;exit;}
	if(isset($_POST["MII-TOOL"])){MII_TOOLS();exit;}
	js();

	
function js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$html="YahooWin2('650','$page?tabs=yes&netconfig={$_GET["nic"]}&button={$_GET["button"]}&noreboot={$_GET["noreboot"]}&OnLyQOS={$_GET["OnLyQOS"]}','{$_GET["nic"]}');";
	echo $html;
	
	
}	
function delete_nic_js(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$nic=$_GET["servername"];
	header("content-type: application/x-javascript");
	$explain=$tpl->javascript_parse_text("{delete} {interface} {$_GET["nic-delete-js"]}");
	
	$t=time();
	$html="
var xDeleteNic$t=function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	LoadAjaxRound('system-main-status','admin.dashboard.system.php');
	Loadjs('network.restart.php');
}
	
function DeleteNic$t(){
	if(!confirm('$explain')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-nic','{$_GET["nic-delete-js"]}');
	XHR.sendAndLoad('$page', 'POST',xDeleteNic$t);
}
DeleteNic$t();
";
	echo $html;
}


function delete_nic(){
	
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM nics WHERE Interface='{$_POST["delete-nic"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	$q->QUERY_SQL("DELETE FROM nic_routes WHERE nic='{$_POST["delete-nic"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	if($q->TABLE_EXISTS("dhcpd_routes", "artica_backup")){
		$q->QUERY_SQL("DELETE FROM dhcpd_routes WHERE nic='{$_POST["delete-nic"]}'","artica_backup");
	}
	if(!$q->ok){echo $q->mysql_error;}	 
	$q->QUERY_SQL("DELETE FROM nics_vlan WHERE nic='{$_POST["delete-nic"]}'","artica_backup");
	$q->QUERY_SQL("DELETE FROM nics_virtuals WHERE nic='{$_POST["delete-nic"]}'","artica_backup");
	$q->QUERY_SQL("DELETE FROM nics_vde WHERE nic='{$_POST["delete-nic"]}'","artica_backup");
	$q->QUERY_SQL("DELETE FROM nics_roles WHERE nic='{$_POST["delete-nic"]}'","artica_backup");
	$q->QUERY_SQL("DELETE FROM nics_switch WHERE nic='{$_POST["delete-nic"]}'","artica_backup");
	$q->QUERY_SQL("DELETE FROM qos_eth WHERE NIC='{$_POST["delete-nic"]}'","artica_backup");
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM transparent_networks where `eth`='{$_POST["delete-nic"]}'");
	$q->QUERY_SQL("DELETE FROM proxy_ports where `nic`='{$_POST["delete-nic"]}'");
	
}

function flus_arp_cache_js(){
	
	$nic=$_GET["nic"];
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("$nic::{flush_arp_cache}");
	echo "YahooWin3('650','$page?flush-arp-cache-popup=yes&nic=$nic','$title');";
}
function flus_arp_cache_popup(){
	$sock=new sockets();
	$nic=$_GET["nic"];
	$datas=unserialize(base64_decode($sock->getFrameWork("network.php?flush-arp-cache=$nic")));
	echo "<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:98%;height:446px;border:5px solid #8E8E8E;
	overflow:auto;font-size:11px' id='textToParseCats-$t'>".@implode("\n", $datas)."</textarea>";
}

function UseSnort(){
	$eth=$_POST["eth"];
	$value=$_POST["UseSnort"];
	$sock=new sockets();
	$snortInterfaces=unserialize(base64_decode($sock->GET_INFO("SnortNics")));
	if($value==0){
		unset($snortInterfaces[$eth]);
	}else{
		$snortInterfaces[$eth]=1;
	}
	
	$sock->SaveConfigFile(base64_encode(serialize($snortInterfaces)),"SnortNics");
	$sock->getFrameWork("cmd.php?restart-snort=yes");
}

function UseIpv6(){
	$eth=$_POST["eth"];
	$value=$_POST["ipv6-enable"];
	$nics=new system_nic($eth);
	$nics->eth=$eth;	
}

function ipconfig_tools(){
	$nic=$_GET["netconfig"];
	if(strlen($_GET["nic"])>3){$nic=$_GET["nic"];}
	$tpl=new templates();
	$sock=new sockets();
	$page=CurrentPageName();
	$tr[]=Paragraphe32("flush_arp_cache", "flush_arp_cache_all_explain","Loadjs('$page?flush-arp-cache-js=yes&nic=$nic')","cache-refresh-32.png");
	// 
	$users=new usersMenus();
	if($users->dnsmasq_installed){
		$tr[]=Paragraphe32("APP_DHCP", "DNSMASQ_DHCP_INSTALL_TEXT",
				"Loadjs('dnsmasq.dhcp.service.php?install-js=yes&nic=$nic')","32-dhcp.png");
	}
	
	
	$table=CompileTr2($tr,true);
	echo $tpl->_ENGINE_parse_body($table);
	
}



function tabs(){
	$nic=$_GET["netconfig"];
	if(strlen($_GET["nic"])>3){$nic=$_GET["nic"];}
	$tpl=new templates();
	$sock=new sockets();
	$EnableipV6=$sock->GET_INFO("EnableipV6");
	if(!is_numeric($EnableipV6)){$EnableipV6=0;}		
	$page=CurrentPageName();
	$array["ipconfig-nic"]='{parameters}';
	$nics_config=new system_nic($nic);
	
	
	if($EnableipV6==1){
		$array["ipconfig-v6"]='ipV6';
	}
	//$array["ipconfig-routes"]='{routes}';
	//$array["ipconfig-routage"]='{routing}';
	

	
	$array["ipconfig-qos"]='{Q.O.S}';
	$array["ipconfig-tools"]='{tools}';
	
	//ip neigh flush dev eth0
	
	if($_GET["OnLyQOS"]=="yes"){
		$array=array();
		$array["ipconfig-qos"]='{Q.O.S}';
	}
	
	
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="ipconfig-services"){
			$html[]= "<li><a href=\"firehol.nic.services.php?nic=$nic\"><span style='font-size:20px'>$ligne</span></a></li>\n";
			continue;
		}
		
		if($num=="ipconfig-qos"){
			$html[]= "<li><a href=\"system.nic.qos.php?nic=$nic\"><span style='font-size:20px'>$ligne</span></a></li>\n";
			continue;
		}		
		
		$html[]= "<li><a href=\"$page?$num=yes&nic=$nic&button={$_GET["button"]}&noreboot={$_GET["noreboot"]}\"><span style='font-size:20px'>$ligne</span></a></li>\n";
	}
	
	
	$html= build_artica_tabs($html, "main_config_$nic");

	echo $tpl->_ENGINE_parse_body($html);
}

function ipconfig_qos(){
	$nic=$_GET["nic"];
	$nic=new system_nic($_GET["nic"]);
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();
	
	$p=Paragraphe_switch_img("{enable_qos_fornic}", "{enable_qos_fornic_explain}",
			"QOS-$t",$nic->QOS,null,580);
	
	$html="
	<div style='width:98%' class=form>
	$p
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{nic_capacity}:</td>
		<td style='font-size:22px'>". Field_text("QOSMAX-$t",$nic->QOSMAX,"width:150px;font-size:22px")."&nbsp;Mib</td>
	</tr>
	</table>
	<div style='text-align:right'>
	<hr>".button("{apply}","Save$t()",32)."</div>
	<script>
	var xSave$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		if(document.getElementById('main_config_{$_GET["nic"]}')){RefreshTab('main_config_{$_GET["nic"]}');}
		if(document.getElementById('wizard-nic-list')){WizardRefreshNics();}
		if(document.getElementById('tabs_listnics2')){RefreshTab('tabs_listnics2');}
		if(document.getElementById('TABLEAU_MAIN_QOS_INTERFACES')){ $('#TABLEAU_MAIN_QOS_INTERFACES').flexReload();}
		LoadAjaxRound('nics-infos-system','admin.dashboard.system.php?nics-infos=yes');
	}
	
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('eth','{$_GET["nic"]}');
		XHR.appendData('QOSMAX',document.getElementById('QOSMAX-$t').value);
		XHR.appendData('QOS',document.getElementById('QOS-$t').value);
		XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function ipconfig_qos_save(){
	$nic=new system_nic($_POST["eth"]);
	$nic->QOS=$_POST["QOS"];
	$nic->QOSMAX=$_POST["QOSMAX"];
	$nic->SaveNic();
	
}

function ipconfig_routage(){
	$nic=$_GET["nic"];
	$nic=new system_nic($_GET["nic"]);
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();

	$p=Paragraphe_switch_img("{source_based_routing}", "{source_based_routing_explain}",
			"SourceBasedRouting-$t",$nic->SourceBasedRouting,null,580);
	
	$html="<div style='width:98%' class=form>
	$p
	<div style='text-align:right'>
	<hr>".button("{apply}","Save$t()",26)."</div>
<script>
var xSave$t= function (obj) {
var results=obj.responseText;
if(results.length>3){alert(results);}
if(document.getElementById('main_config_{$_GET["nic"]}')){RefreshTab('main_config_{$_GET["nic"]}');}
if(document.getElementById('wizard-nic-list')){WizardRefreshNics();}
if(document.getElementById('tabs_listnics2')){RefreshTab('tabs_listnics2');}

}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('eth','{$_GET["nic"]}');
	XHR.appendData('SourceBasedRouting',document.getElementById('SourceBasedRouting-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}				
</script>			
";
	
echo $tpl->_ENGINE_parse_body($html);	
}

function SourceBasedRoutingSave(){
	$nic=new system_nic($_POST["eth"]);
	$nic->CheckMySQLFields();
	$nic->SourceBasedRouting=$_POST["SourceBasedRouting"];
	$nic->SaveNic();
	
}


function ipconfig_nic6(){
	$eth=$_GET["nic"];
	$sock=new sockets();
	$tpl=new templates();
	$t=time();
	$page=CurrentPageName();
	$EnableipV6=$sock->GET_INFO("EnableipV6");
	if(!is_numeric($EnableipV6)){$EnableipV6=0;}	
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if(!is_numeric($DisableNetworksManagement)){$DisableNetworksManagement=0;}	
	$nic=new system_nic($_GET["nic"]);
	$array[0]="{select}";
	$array[12]="/12";$array[13]="/13";$array[14]="/14";$array[15]="/15";$array[16]="/16";$array[17]="/17";$array[18]="/18";$array[19]="/19";$array[20]="/20";$array[21]="/21";$array[22]="/22";$array[23]="/23";$array[24]="/24";$array[25]="/25";$array[26]="/26";$array[27]="/27";$array[28]="/28";$array[29]="/29";$array[30]="/30";$array[31]="/31";$array[32]="/32";$array[33]="/33";$array[34]="/34";$array[35]="/35";$array[36]="/36";$array[37]="/37";$array[38]="/38";$array[39]="/39";$array[40]="/40";$array[41]="/41";$array[42]="/42";$array[43]="/43";$array[44]="/44";$array[45]="/45";$array[46]="/46";$array[47]="/47";$array[48]="/48";$array[49]="/49";$array[50]="/50";$array[51]="/51";$array[52]="/52";$array[53]="/53";$array[54]="/54";$array[55]="/55";$array[56]="/56";$array[57]="/57";$array[58]="/58";$array[59]="/59";$array[60]="/60";$array[61]="/61";$array[62]="/62";$array[63]="/63";$array[64]="/64";$array[104]="/104";$array[120]="/120";$array[128]="/128";
	
$html="	
<div style='width:99.5%' class=form>
<table style='width:100%' >
	<tr>
		<td class=legend style='font-size:22px'>{use_ipv6}:</td>
		<td width=1%>" . Field_checkbox_design("ipv6-$t",1,$nic->ipv6,"SwitchIpv6$t()")."</td>
	</tr>		

		<tr>
			<td class=legend style='font-size:22px'>{tcp_address}:</td>
			<td>" . Field_text("ipv6addr-$t",$nic->ipv6addr,'padding:3px;font-size:22px;width:352px')."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>{netmask} ipv6:</td>
			<td>" . Field_array_Hash($array,"ipv6mask-$t",$nic->ipv6mask,"blur()",null,0,'padding:3px;font-size:22px')."</td>
		</tr>				
		<tr>
			<td class=legend style='font-size:22px'>{gateway}:</td>
			<td>" . Field_text("ipv6gw-$t",$nic->ipv6gw,'padding:3px;font-size:22px;width:352px')."</td>
		</tr>
		<tr>
			<td colspan=2 align='right'><hr>".button("{apply}","SaveNicSettings$t()","32")."</td>
		</tr>
	</table>
</div>	
<script>
		function SwitchIpv6$t(){
			var EnableipV6=$EnableipV6;
			var dgcp=$nic->dhcp;
			var enabled=$nic->enabled;
			document.getElementById('ipv6mask-$t').disabled=true;
			document.getElementById('ipv6addr-$t').disabled=true;
			document.getElementById('ipv6gw-$t').disabled=true;
			
			
			
			if(EnableipV6==1){	
				if(enabled==1){
					if(document.getElementById('ipv6-$t').checked){
						if(dgcp==0){
							document.getElementById('ipv6mask-$t').disabled=false;
							document.getElementById('ipv6addr-$t').disabled=false;
							document.getElementById('ipv6gw-$t').disabled=false;
						}
					}
				}
			}
				
		}

		
		var X_SaveNicSettings$t= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);}
			if(document.getElementById('main_config_$eth')){RefreshTab('main_config_$eth');}
			if(document.getElementById('wizard-nic-list')){WizardRefreshNics();}
			LoadAjaxRound('nics-infos-system','admin.dashboard.system.php?nics-infos=yes');
			}

		function SaveNicSettings$t(){
			var EnableipV6=$EnableipV6;
			var dgcp=$nic->dhcp;
			var enabled=$nic->enabled;
			if(enabled==0){return;}
		
			var XHR = new XHRConnection();
			var DisableNetworksManagement=$DisableNetworksManagement;
			var ipv6Mask=document.getElementById('ipv6mask-$t').value;
			var ipv6enabled=0;
			if(DisableNetworksManagement==1){alert('$ERROR_NO_PRIVS');return;}
			if(!document.getElementById('ipv6-$t')){alert('ipv6-$t no such id');return;}
			if(document.getElementById('ipv6-$t').checked){
				ipv6enabled=1;
				if(dgcp==0){
					if(ipv6Mask==0){
						alert('Please select the Ipv6 netmask');
						return;
					}
				}
			}
			XHR.appendData('ipv6-eth','$eth');
			XHR.appendData('ipv6-enable',ipv6enabled);
			XHR.appendData('ipv6addr',document.getElementById('ipv6addr-$t').value);
			XHR.appendData('ipv6mask',document.getElementById('ipv6mask-$t').value);
			XHR.appendData('ipv6gw',document.getElementById('ipv6gw-$t').value);
			XHR.sendAndLoad('$page', 'POST',X_SaveNicSettings$t);
			
		}		
	
	SwitchIpv6$t();
</script>";

echo $tpl->_ENGINE_parse_body($html);


	
}

function ipconfig_nic(){
	$sock=new sockets();
	$tpl=new templates();
	$t=time();
	$page=CurrentPageName();
	$EnableipV6=$sock->GET_INFO("EnableipV6");
	if(!is_numeric($EnableipV6)){$EnableipV6=0;}	
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if(!is_numeric($DisableNetworksManagement)){$DisableNetworksManagement=0;}	
	$eth=$_GET["nic"];
	
	$nic=new system_nic($eth);
	$users=new usersMenus();
	if($users->SNORT_INSTALLED){
		$EnableSnort=$sock->GET_INFO("EnableSnort");
		if($EnableSnort<>1){$jsSnort="DisableSnortInterface();";}
		$snortInterfaces=unserialize(base64_decode($sock->GET_INFO("SnortNics")));

	}
	if(!$users->SNORT_INSTALLED){$jsSnort="DisableSnortInterface();";}
	$button="{apply}";
	if($_GET["button"]=="confirm"){$button="{button_i_confirm_nic}";}
	
	$MIITOOLS=unserialize(base64_decode($sock->getFrameWork("system.php?mii-tools=yes&eth=$eth")));
	$form_miitolsA[null]="{select}";
	if(!$MIITOOLS["STATUS"]){
		$form_miitols="<p class=text-error>{hardware_error_nic}<br>{$MIITOOLS["ERROR"]}</p>";
		
	}else{
		$form_miitolsHT["HD"]="Half duplex";
		$form_miitolsHT["FD"]="Full duplex";
		
		while (list ($val, $b) = each ($MIITOOLS["CAP"])){
			$caption=$val;
			if(strpos($MIITOOLS["INFOS"], $val)>0){$MII_DEFAULT=$val;}
			if(preg_match("#([0-9]+)(.*?)-([A-Z]+)#", $val,$re)){
				$caption="{$re[1]} {$re[2]} {$form_miitolsHT[$re[3]]}";
			}
			
			$form_miitolsA[$val]=$caption;
			
		}
		
		if($MIITOOLS["FLOWC"]==1){$explflw=" {flow_control}";}
		$form_miitols="
		<p class=explain style='font-size:18px;font-weight:bold'>{$MIITOOLS["INFOS"]} $explflw</p>
		<table style='width:100%'>			
		<tr>
			<td class=legend style='font-size:18px'>Autonegotiation:</td>
			<td width=1%>" . Field_checkbox("autonegotiation-$t",1,$MIITOOLS["AUTONEG"])."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:18px'>{flow_control}:</td>
			<td width=1%>" . Field_checkbox("flow-control-$t",1,$MIITOOLS["FLOWC"])."</td>
		</tr>					
					
					
		<tr>
			<td class=legend style='font-size:18px'>{type}:</td>
			<td width=1%>" . Field_array_Hash($form_miitolsA, "media-$t",$MII_DEFAULT,"style:font-size:18px")."</td>
		</tr>
		</table>
		<div style='text-align:right'><hr> ". button("{apply}","SaveMII$t()",22)."</div>";		
	}
	
	
	
	$html="
	<div id='edit-config-$eth'>
	<form name='ffm$eth'>
	<input type='hidden' name='save_nic' id='save_nic' id='save_nic' value='$eth'>
	<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:22px'>{enabled}:</td>
			<td width=1%>" . Field_checkbox_design('enabled',1,$nic->enabled,'SwitchDHCP()')."</td>
		</tr>		
		<tr>
			<td class=legend style='font-size:22px'>{use_dhcp}:</td>
			<td width=1%>" . Field_checkbox_design('dhcp',1,$nic->dhcp,'SwitchDHCP()')."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>". texttooltip("{free_mode}","{free_mode_if_explain}").":</td>
			<td width=1%>" . Field_checkbox_design('UseSPAN',1,$nic->UseSPAN,'SwitchUseSPAN()')."</td>
		</tr>
	</table>
	
	
	<div style='width:98%' class=form>
	<table style='100%'>
		<tr>
			<td class=legend style='font-size:22px'>{netzone}:</td>
			<td>" . field_text("netzone-$t",$nic->netzone,'padding:3px;font-size:22px;width:325px',null,null,null,false,null,$DISABLED)."</td>
		</tr>				
		<tr>
			<td class=legend style='font-size:22px'>{name}:</td>
			<td>" . field_text("NICNAME-$t",$nic->NICNAME,'padding:3px;font-size:22px',null,null,null,false,null,$DISABLED)."</td>
		</tr>		
		<tr>
			<td class=legend style='font-size:22px'>{tcp_address}:</td>
			<td>" . field_ipv4("IPADDR",$nic->IPADDR,'padding:3px;font-size:22px',null,null,null,false,null,$DISABLED)."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>{netmask}:</td>
			<td>" . field_ipv4("NETMASK",$nic->NETMASK,'padding:3px;font-size:22px',null,null,null,false,null,$DISABLED)."</td>
		</tr>
			
		<tr>
			<td class=legend style='font-size:22px'>{gateway}:</td>
			<td>" . field_ipv4("GATEWAY",$nic->GATEWAY,'padding:3px;font-size:22px',null,null,null,false,null,$DISABLED)."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>{default_gateway}:</td>
			<td>" . Field_checkbox_design("defaultroute-$t",1,$nic->defaultroute)."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>{metric}:</td>
			<td>" . field_text("metric-$t",$nic->metric,'padding:3px;font-size:22px;width:90px',null,null,null,false,null,$DISABLED)."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>MTU:</td>
			<td>" . field_text("mtu-$t",$nic->mtu,'padding:3px;font-size:22px;width:90px',null,null,null,false,null,$DISABLED)."</td>
		</tr>										
		<tr>
			<td class=legend style='font-size:22px'>{broadcast}:</td>
			<td>" . field_ipv4("BROADCAST",$nic->BROADCAST,'padding:3px;font-size:22px',null,null,null,false,null,$DISABLED)."</td>
		</tr>	
			
	</table>
	<div style='text-align:right'><hr> ". button("$button","SaveNicSettings()",28)."</div>
	</div>
	
	
	</form>
	</div>
	<p>&nbsp;</p>
	<div style='width:98%' class=form>
	<div style='font-size:22px;margin-bottom:12px'>{net_mii_title}</div>
	$form_miitols
	</div>
	
	<script>
	
		var X_SaveNicSettings= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);}
			if(document.getElementById('main_config_$eth')){RefreshTab('main_config_$eth');}
			if(document.getElementById('wizard-nic-list')){WizardRefreshNics();}
			if(document.getElementById('FIREHOLE_INTERFACES_TABLES')){ $('#FIREHOLE_INTERFACES_TABLES').flexReload();}
			LoadAjaxRound('nics-infos-system','admin.dashboard.system.php?nics-infos=yes');
			}

		function logofff(){
			var ipaddr=document.getElementById('IPADDR').value;
			document.location.href='https://'+ipaddr+':{$_SERVER['SERVER_PORT']}';
		}
	
		function SaveNicSettings(){
			var XHR = new XHRConnection();
			var DisableNetworksManagement=$DisableNetworksManagement;
			if(DisableNetworksManagement==1){alert('$ERROR_NO_PRIVS');return;}
			if(document.getElementById('dhcp').checked){XHR.appendData('dhcp','1');}else{XHR.appendData('dhcp','0');}
			if(document.getElementById('UseSPAN').checked){XHR.appendData('UseSPAN','1');}else{XHR.appendData('UseSPAN','0');}
			
			
			if(document.getElementById('enabled').checked){XHR.appendData('enabled','1');}else{XHR.appendData('enabled','0');}
			if(document.getElementById('defaultroute-$t').checked){XHR.appendData('defaultroute','1');}else{XHR.appendData('defaultroute','0');}
			XHR.appendData('NICNAME',encodeURIComponent(document.getElementById('NICNAME-$t').value));
			XHR.appendData('netzone',document.getElementById('netzone-$t').value);
			XHR.appendData('IPADDR',document.getElementById('IPADDR').value);
			XHR.appendData('NETMASK',document.getElementById('NETMASK').value);
			XHR.appendData('GATEWAY',document.getElementById('GATEWAY').value);
			XHR.appendData('mtu',document.getElementById('mtu-$t').value);
			
			
			
			if(document.getElementById('DNS_1')){
				XHR.appendData('DNS_1',document.getElementById('DNS_1').value);
			}
			if(document.getElementById('DNS_2')){
				XHR.appendData('DNS_2',document.getElementById('DNS_2').value);
			}
			XHR.appendData('BROADCAST',document.getElementById('BROADCAST').value);
			XHR.appendData('metric',document.getElementById('metric-$t').value);
			XHR.appendData('save_nic',document.getElementById('save_nic').value);
			XHR.appendData('noreboot','{$_GET["noreboot"]}');
			if(document.getElementById('zlistnic-info-$eth')){AnimateDiv('zlistnic-info-$eth');}
			if(document.getElementById('edit-config-$eth')){AnimateDiv('edit-config-$eth');}
			if(document.getElementById('wizard-nic-list')){AnimateDiv('wizard-nic-list');}
			
			XHR.sendAndLoad('$page', 'GET',X_SaveNicSettings);
			
		}
		
		var x_SwitchSnort= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);return;}
			RefreshTab('tabs_listnics2');
			}		
		
		
		
		function SwitchSnort(){
			var XHR = new XHRConnection();
			var DisableNetworksManagement=$DisableNetworksManagement;
			if(DisableNetworksManagement==1){alert('$ERROR_NO_PRIVS');return;}	
			if(document.getElementById('UseSnort').checked){XHR.appendData('UseSnort','1');}else{XHR.appendData('UseSnort','0');}
			XHR.appendData('eth','$eth');
			XHR.sendAndLoad('$page', 'POST',x_SwitchSnort);
		}
		
		
		
		function SaveMII$t(){
			var XHR = new XHRConnection();
			var DisableNetworksManagement=$DisableNetworksManagement;
			if(DisableNetworksManagement==1){alert('$ERROR_NO_PRIVS');return;}
			if(document.getElementById('flow-control-$t').checked){XHR.appendData('flow-control','1');}else{XHR.appendData('flow-control','0');}
			if(document.getElementById('autonegotiation-$t').checked){XHR.appendData('autonegotiation','1');}else{XHR.appendData('autonegotiation','0');}
			XHR.appendData('duptype',document.getElementById('media-$t').value);
			XHR.appendData('MII-TOOL','$eth');
			XHR.sendAndLoad('$page', 'POST',X_SaveNicSettings)
		}
		
		function CheckMII$t(){
			if(document.getElementById('flow-control-$t')){
				document.getElementById('flow-control-$t').disabled=true;
			}
			
			if(document.getElementById('autonegotiation-$t')){
				document.getElementById('autonegotiation-$t').disabled=true;
			}
		}
		
		
		function LockNic(){
			var DisableNetworksManagement=$DisableNetworksManagement;
			document.getElementById('dhcp').disabled=true;
			document.getElementById('IPADDR').disabled=true;
			document.getElementById('NETMASK').disabled=true;
			document.getElementById('GATEWAY').disabled=true;
			document.getElementById('mtu-$t').disabled=true;
			if(document.getElementById('DNS_1')){document.getElementById('DNS_1').disabled=true;}
			if(document.getElementById('DNS_2')){document.getElementById('DNS_2').disabled=true;}
			document.getElementById('save_nic').disabled=true;
			document.getElementById('UseSPAN').disabled=true;
			if(DisableNetworksManagement==1){return;}
			document.getElementById('dhcp').disabled=false;
			document.getElementById('IPADDR').disabled=false;
			document.getElementById('NETMASK').disabled=false;
			document.getElementById('GATEWAY').disabled=false;
			document.getElementById('mtu-$t').disabled=false;
			document.getElementById('UseSPAN').disabled=false;
			if(document.getElementById('DNS_1')){document.getElementById('DNS_1').disabled=false;}
			if(document.getElementById('DNS_2')){document.getElementById('DNS_2').disabled=false;}
			document.getElementById('save_nic').disabled=false;	
			if(document.getElementById('zlistnic-info-$eth')){LoadAjax('zlistnic-info-$eth','system.nic.config.php?nic-builder=$eth');}
			SwitchDHCP();	
			SwitchUseSPAN();	
		
		}
		
	function SwitchDHCP(){
		document.getElementById('IPADDR').disabled=true;
		document.getElementById('NETMASK').disabled=true;
		document.getElementById('GATEWAY').disabled=true;
		document.getElementById('BROADCAST').disabled=true;
		document.getElementById('mtu-$t').disabled=true;
		document.getElementById('UseSPAN').disabled=true;
		if(document.getElementById('DNS_1')){document.getElementById('DNS_1').disabled=true;}
		if(document.getElementById('DNS_2')){document.getElementById('DNS_2').disabled=true;}
		document.getElementById('dhcp').disabled=true;
		
		if(document.getElementById('enabled').checked==false){return;}
		
		document.getElementById('dhcp').disabled=false;
		if(document.getElementById('dhcp').checked==true){return;}
		document.getElementById('UseSPAN').disabled=false;
		document.getElementById('IPADDR').disabled=false;
		document.getElementById('NETMASK').disabled=false;
		document.getElementById('GATEWAY').disabled=false;
		document.getElementById('BROADCAST').disabled=false;
		document.getElementById('mtu-$t').disabled=false;
		if(document.getElementById('DNS_1')){document.getElementById('DNS_1').disabled=false;}
		if(document.getElementById('DNS_2')){document.getElementById('DNS_2').disabled=false;}
	}	

	function SwitchUseSPAN(){
		if(!document.getElementById('enabled').checked){return;}
	
	
		if(document.getElementById('UseSPAN').checked==true){
			document.getElementById('IPADDR').disabled=true;
			document.getElementById('NETMASK').disabled=true;
			document.getElementById('GATEWAY').disabled=true;
			document.getElementById('BROADCAST').disabled=true;
			document.getElementById('mtu-$t').disabled=true;
			if(document.getElementById('DNS_1')){document.getElementById('DNS_1').disabled=true;}
			if(document.getElementById('DNS_2')){document.getElementById('DNS_2').disabled=true;}
			document.getElementById('dhcp').disabled=true;
		}else{
			SwitchDHCP();
		
		}
	}
	
	function DisableSnortInterface(){
		document.getElementById('UseSnort').disabled=true;
	}
		
		
	LockNic();
	CheckMII$t();
	SwitchUseSPAN();
	</script>	
	";
	
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	}
	
function save_nic6(){
$sock=new sockets();
	$tpl=new templates();
	$ip=new IP();

	$nic=$_POST["ipv6-eth"];
	$tpl=new templates();
	$nics=new system_nic($nic);
	if($nics->dhcp==0){
		if(!$ip->isIPv6($_POST["ipv6addr"])){
			echo "{$_POST["ipv6addr"]} not a valid ipv6 address...\n";
			return;
		}
	}
	$nics->eth=$nic;
	$nics->ipv6=$_POST["ipv6-enable"];
	$nics->ipv6addr=$_POST["ipv6addr"];
	$nics->ipv6mask=$_POST["ipv6mask"];
	$nics->ipv6gw=$_POST["ipv6gw"];
	$nics->SaveNic();
	
}
	
function save_nic(){
	$DNS_1=null;$DNS_2=null;
	$sock=new sockets();
	$tpl=new templates();
	$ip=new networking();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		
	if($DisableNetworksManagement==1){echo $ERROR_NO_PRIVS;return;}
	if(isset($_GET["NICNAME"])){
		$NICNAME=trim(url_decode_special_tool($_GET["NICNAME"]));
	}
	
	$nic=trim($_GET["save_nic"]);
	$sql="UPDATE `nics` SET `enabled`='{$_GET["enabled"]}' WHERE `Interface`='$nic'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup"); 
	if(!$q->ok){echo $q->mysql_error;}
	
	if($_GET["netzone"]==null){
		echo "Network Zone must be defined\n";
		return;
	}
	
	if($_GET["netzone"]<>null){
		if(strlen($_POST["netzone"])>5){echo "Network Zone {$_GET["netzone"]} at most 5 characters long\n";return;}
		if(is_numeric(substr($_GET["netzone"], 0,1))){echo "Network Zone Must start with a letter\n";return;}
	}
	
	$UseSPAN=intval($_GET["UseSPAN"]);
	$nic=trim($_GET["save_nic"]);
	$IPADDR=trim($_GET["IPADDR"]);
	$NETMASK=trim($_GET["NETMASK"]);
	$GATEWAY=trim($_GET["GATEWAY"]);
	$BROADCAST=trim($_GET["BROADCAST"]);
	if(isset($_GET["DNS_1"])){$DNS_1=$_GET["DNS_1"];}
	if(isset($_GET["DNS_2"])){$DNS_2=$_GET["DNS_2"];}
	$dhcp=trim($_GET["dhcp"]);
	$arrayNic=$ip->GetNicInfos($nic);
	
	
	
	
	
	
	$q=new mysql();
	$sql="SELECT ipaddr FROM nic_virtuals WHERE ipaddr='$IPADDR'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if($ligne["ipaddr"]<>null){
		echo $tpl->javascript_parse_text("{already_used}: $IPADDR (Virtual)");
		return;
	}
	
	$sql="SELECT ipaddr FROM nics_vlan WHERE ipaddr='$IPADDR'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if($ligne["ipaddr"]<>null){
		echo $tpl->javascript_parse_text("{already_used}: $IPADDR (VLAN)");
		return;
	}	
	
	$ROUTES=base64_encode(serialize($arrayNic["ROUTES"]));
	
	
	if(intval($_GET["enabled"])==1){
		
		if($UseSPAN<>1){
			if($_GET["dhcp"]<>1){
				if(!$ip->checkIP($IPADDR)){echo "CheckIP: Address: $IPADDR = False;\n";return;}
				if(!$ip->checkIP($NETMASK)){echo "CheckIP: NetMask $NETMASK = False;\n";return;}
				if($GATEWAY<>"0.0.0.0"){
					if(!$ip->checkIP($GATEWAY)){echo "CheckIP: Gateway $GATEWAY = False;\n";return;}
				}
			}
			if($DNS_1<>null){
				if(!$ip->checkIP($DNS_1)){echo "CheckIP: DNS 1 $DNS_1 = False;\nOr set null value to remove this message";return;}	
			}
			
			if($DNS_2<>null){
				if(!$ip->checkIP($DNS_2)){echo "CheckIP: DNS 2 $DNS_2 = False;\nOr set null value to remove this message";return;}	
			}
			
		}
	}

	$htmltools=new htmltools_inc();
	$_GET["netzone"]=$htmltools->StripSpecialsChars($_GET["netzone"]);
	
	$tpl=new templates();
	$nics=new system_nic($nic);
	$text[]="$NICNAME $nic $IPADDR";
	if($NICNAME<>null){ $nics->NICNAME=$NICNAME; }
	$nics->eth=$nic;
	$nics->IPADDR=$IPADDR;
	$nics->NETMASK=$NETMASK;
	$nics->GATEWAY=$GATEWAY;
	$nics->BROADCAST=$BROADCAST;
	$nics->UseSPAN=$UseSPAN;
	if($DNS_1<>null){$nics->DNS1=$DNS_1; }
	if($DNS_2<>null){ $nics->DNS2=$DNS_2; }
	$nics->dhcp=$_GET["dhcp"];
	$nics->metric=$_GET["metric"];
	$nics->enabled=intval($_GET["enabled"]);
	$nics->netzone=$_GET["netzone"];
	$nics->mtu=$_GET["mtu"];
	
	if(isset($_GET["defaultroute"])){
		$nics->defaultroute=$_GET["defaultroute"];
	}

	if(isset($_GET["Bridged"])){
		$nics->Bridged=$_GET["Bridged"];
		$text[]="Bridged, ";
	}
	if(isset($_GET["BridgedTo"])){
		$nics->BridgedTo=$_GET["BridgedTo"];
		$text[]="{$_GET["BridgedTo"]}";
	}	

	if(isset($_GET["defaultroute"])){
		$nics->defaultroute=$_GET["defaultroute"];
	}
	
	
	if($_GET["noreboot"]=="noreboot"){
		$nics->NoReboot=true;
		if($nics->SaveNic()){
			
			return;
		}
	}
	
	$nics->SaveNic();

}
function ipconfig_routes(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$webfilter=new webfilter_rules();
	$t=time();
	$new_route=$tpl->_ENGINE_parse_body("{new_route}");
	$rule_text=$tpl->_ENGINE_parse_body("{rule}");
	$TimeSpace=$webfilter->TimeToText(unserialize(base64_decode($ligne["TimeSpace"])));
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	$groups=$tpl->_ENGINE_parse_body("{groups}");
	$gateway=$tpl->_ENGINE_parse_body("{gateway}");
	$whitelists=$tpl->_ENGINE_parse_body("{whitelists}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$action_delete_rule=$tpl->javascript_parse_text("{action_delete_rule}");
	$from_ip_address=$tpl->_ENGINE_parse_body("{from_ip_address}");
	$service_events=$tpl->_ENGINE_parse_body("{service_events}");
	$global_parameters=$tpl->_ENGINE_parse_body("{global_parameters}");
	$ldap_parameters=$tpl->_ENGINE_parse_body("{ldap_parameters2}");
	$config_file=$tpl->_ENGINE_parse_body("{config_file}");
	$categories_group=$tpl->_ENGINE_parse_body("{categories_groups}");
	
	$error_ldap=null;
	$buttons="
	buttons : [
	{name: '<strong style=font-size:12px;>$new_route</strong>', bclass: 'add', onpress : NewRoute$t},
	
	
	],";
	
	
	
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	</div>
	<script>
var rowid$t=0;
function flexRTStart$t(){
	$('#flexRT$t').flexigrid({
		url: '$page?ifconfig-route-list=yes&t=$t&nic={$_GET["nic"]}',
		dataType: 'json',
		colModel : [
		{display: '$from_ip_address', name : 'from_ip_address', width : 221, sortable : false, align: 'left'},
		{display: '$gateway', name : 'gateway', width :115, sortable : false, align: 'left'},
		{display: '$delete', name : 'delete', width : 31, sortable : false, align: 'center'},
		],
		$buttons
		searchitems : [
		{display: '$from_ip_address', name : 'from_ip_address'},
		],
		sortname: 'zOrder',
		sortorder: 'asc',
		usepager: true,
		title: '',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: 430,
		height: 300,
		singleSelect: true,
		rpOptions: [10, 20, 30, 50,100,200]
		});
}
	
	
	function NewRoute$t(){
		Loadjs('$page?ifconfig-route-add-js=yes&t=$t&nic={$_GET["nic"]}');
	}
	

var xDeleteRoute$t= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		$('#row'+rowid$t).remove();
	}
	
function DeleteRoute$t(ip,id){
	rowid$t=id;
	if(confirm('$delete '+ip)){
		var XHR = new XHRConnection();		
		XHR.appendData('del-routes','yes');
		XHR.appendData('nic','{$_GET["nic"]}');
		XHR.appendData('IP',ip);
		XHR.sendAndLoad('$page', 'GET',xDeleteRoute$t);	
	}
}
	
setTimeout('flexRTStart$t()',800);
</script>
	
	";
	
	echo $html;
	
	}

function ipconfig_routes_add_popup(){
	$ip=new networking();
	$eth=$_GET["nic"];
	$nic=$_GET["nic"];
	$t=$_GET["t"];
	$page=CurrentPageName();
	$arrayNic=$ip->GetNicInfos($eth);
	$page=CurrentPageName();
	$tpl=new templates();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$users=new usersMenus();
	if($users->AsSystemAdministrator){$AsNetworksAdministrator=1;}else{$AsNetworksAdministrator=0;}

	$html="
	<center style='margin:20px'>
	<div id='id='routes-$eth'></div>
	<div style='width:98%' class=form>
	<table style='width:100%' >
		<tr>
			<td class=legend width=1% nowrap style='font-size:16px'>{from_ip_address}:</td>
			<td width=1% nowrap>" . field_ipv4("route-network-$t", null,"font-size:16px")."</td>
		 </tr>
		 <tr>
			<td class=legend style='font-size:16px'>{netmask}:</td>
			<td width=1% nowrap>" . field_ipv4("route-mask-$t", null,"font-size:16px")."</td>
		 </tr>
		<tr>
			<td class=legend width=1% nowrap style='font-size:16px'>{gateway}:</td>
			<td width=1% nowrap>" . field_ipv4("route-gateway-$t", null,"font-size:16px")."</td>	 
		</tr>	
			<td colspan=8 align='right' ><hr>". button("{add}","AddRouteIpNic$t()",18)."</td>
		</tr>		 				
	</table>	
	</div>
	</center>
<script>
var x_AddRouteIpNic$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	YahooWin6Hide();
	$('#flexRT$t').flexReload();
}		
	
	
function AddRouteIpNic$t(){
	var AsNetworksAdministrator='$AsNetworksAdministrator';
	if(AsNetworksAdministrator!=='1'){alert('$ERROR_NO_PRIVS');return;}	
	var XHR = new XHRConnection();				
	XHR.appendData('add-routes','yes');
	XHR.appendData('eth','{$_GET["nic"]}');
	XHR.appendData('nic','{$_GET["nic"]}');
	XHR.appendData('route-network',document.getElementById('route-network-$t').value);
	XHR.appendData('route-mask',document.getElementById('route-mask-$t').value);
	XHR.appendData('route-gateway',document.getElementById('route-gateway-$t').value);
	XHR.sendAndLoad('$page', 'POST',x_AddRouteIpNic$t);		
}
</script>";
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function ipconfig_routes_list(){
	$ip=new networking();
	$eth=$_GET["nic"];
	$nic=$_GET["nic"];
	$page=CurrentPageName();
	$nicClass=new system_nic($_GET["nic"]);
	writelogs("Loading routes from {$_GET["nic"]}",__FUNCTION__,__FILE__,__LINE__);
	$routes=$nicClass->ROUTES;
	$users=new usersMenus();
	if($users->AsSystemAdministrator){$AsNetworksAdministrator=1;}else{$AsNetworksAdministrator=0;}	
	$tpl=new templates();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");	
 	if(!is_array($routes)){return null;}
 	$page=CurrentPageName();
 	$t=$_GET["t"];
 	$c=0;
 	
 	$data = array();
 	$data['page'] = 1;
 	$data['total'] = $c;
 	$data['rows'] = array();
 	$searchstring=null;
 	if($_POST["query"]<>null){
 		$searchstring=str_replace(".", "\.", $_POST["query"]);
 		$searchstring=str_replace("*", ".*?", $_POST["query"]);
 		$searchstring=str_replace("/", "\/", $_POST["query"]);
 	}
 	
 	while (list ($ip, $ip_array) = each ($routes) ){	
 		$id=md5($ip);
 		$color="black";
 		$delete=imgtootltip("delete-24.png","{delete}","DeleteRoute$t('$ip','$id')");
 		if(isset($ip_array["DEV"])){$ip_array["GATEWAY"]=$ip_array["DEV"];}
 		
 		if($searchstring<>null){
 			if(!preg_match("#$searchstring#", "$ip/{$ip_array["NETMASK"]}")){continue;}
 		}
 		
 		$data['rows'][] = array(
 			'id' => $id,
 			'cell' => array(
 					"<span style='font-size:14px;color:$color;'>$ip/{$ip_array["NETMASK"]}</span>",
 					"<span style='font-size:14px;color:$color;'>{$ip_array["GATEWAY"]}</a></span>",
 					$delete )
 			);
 		$c++;
 	} 	
 	$data['total'] = $c;
 	echo json_encode($data); 	
}

function ipconfig_routes_add_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{new_route}");
	$html="YahooWin6('500','$page?ifconfig-route-add-popup=yes&t={$_GET["t"]}&nic={$_GET["nic"]}','$title');";
	echo $html;	
	
}

function ipconfig_routes_add(){
	$user=new usersMenus();
	$tpl=new templates();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	if(!$user->AsSystemAdministrator){echo $ERROR_NO_PRIVS;return;}
	$ip=new networking();

	$ip1=$_POST["route-network"];
	$mask=$_POST["route-mask"];
	$gw=$_POST["route-gateway"];
	
	if(!$ip->checkIP($ip1)){echo "IP $ip1\nFailed";return;}
	if(!$ip->checkIP($mask)){echo "MASK $mask\nFailed";return;}
	if(!$ip->checkIP($gw)){echo "Gateway $gw\nFailed";return;}	
	$nic=new system_nic($_POST["nic"]);
	writelogs("SaveNic:: {$_POST["nic"]} $ip1/$mask -> $gw",__FUNCTION__,__FILE__,__LINE__);
	$nic->ROUTES[$ip1]=array("NETMASK"=>$mask,"GATEWAY"=>$gw);
	$nic->SaveNic($_POST["nic"]);
	
}

function ipconfig_routes_del(){
	$user=new usersMenus();
	$tpl=new templates();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	if(!$user->AsSystemAdministrator){echo $ERROR_NO_PRIVS;return;}
	$ip=new networking();
	$nic=new system_nic($_GET["nic"]);
	unset($nic->ROUTES[$_GET["IP"]]);
	$nic->SaveNic();	
}

function MII_TOOLS(){
	$sock=new sockets();
	while (list ($a, $b) = each ($_POST)){
		$f[]="$a=$b";
		
	}
	
	$duptype=$_POST["duptype"];
	$eth=$_POST["MII-TOOL"];
	
	$nicClass=new system_nic($eth);
	$nicClass->SetMiittools($duptype);
	$data=trim(base64_decode($sock->getFrameWork("system.php?mii-tool-save=yes&".@implode("&", $f))));
	if($data<>null){echo $data;}
	
}

