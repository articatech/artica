<?php
$GLOBALS["ICON_FAMILY"]="VPN";
session_start();
include_once('ressources/class.templates.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.openvpn.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.tcpip.inc');
$users=new usersMenus();
if(!$users->AsSystemAdministrator){die("alert('no access');");}

if(isset($_GET["startpage"])){echo startpage();exit;}
if(isset($_GET["wizard"])){wizard();exit;}
if(isset($_GET["wizard-key"])){wizard_key();exit;}
if(isset($_GET["wizard-server"])){wizard_server();exit;}
if(isset($_GET["wizard-finish"])){wizard_finish();exit;}
if(isset($_GET["KEY_COUNTRY_NAME"])){SaveCertificate();exit;}
if(isset($_GET["ENABLE_SERVER"])){SaveServerConf();exit;}
if(isset($_GET["ENABLE_BRIDGE"])){SaveBridgeMode();exit;}
if(isset($_GET["VPN_DNS_DHCP_1"])){SaveServerConf();exit;}
if(isset($_GET["restart-server"])){RestartServer();exit;}
if(isset($_GET["server-settings"])){server_settings();exit;}
if(isset($_GET["server-settings-js"])){server_settings_js();exit;}
if(isset($_GET["routes"])){routes_settings();exit;}
if(isset($_GET["ROUTE_SHOULD_BE"])){routes_shouldbe();exit;}
if(isset($_GET["ROUTE_FROM"])){routes_add();exit;}
if(isset($_GET["routes-list"])){routes_list();exit;}
if(isset($_GET["DELETE_ROUTE_FROM"])){routes_delete();exit;}

if(isset($_GET["events-js"])){events_js();exit;}
if(isset($_GET["events"])){events();exit;}
if(isset($_GET["events-session"])){events_sessions();exit;}
if(isset($_GET["session-js"])){events_sessions_js();exit;}
if(isset($_GET["clients-settings"])){Clients_settings();exit;}
if(isset($_GET["ncc"])){ncc();exit;}
if(isset($_GET["OpenVPNChangeServerMode"])){OpenVPNChangeServerMode();exit;}
if(isset($_GET["BRIDGE_ETH_SHOW"])){echo ShowIPConfig($_GET["BRIDGE_ETH_SHOW"]);exit;}
if(isset($_GET["index"])){index_page();exit;}
if(isset($_GET["rebuild-certificate"])){rebuild_certificate();exit;}
if(isset($_GET["build-server-events"])){events_content();exit;}
if(isset($_GET["events-sessions-details"])){events_sessions_details();exit;}
if(isset($_GET["openvpn-status"])){status();exit;}

js();


function events_sessions_js(){
	$page=CurrentPageName();
	echo "$('#BodyContent').load('$page?events-session=yes');";
	
}
function server_settings_js(){
	$page=CurrentPageName();
	echo "$('#BodyContent').load('$page?server-settings=yes');";
}
function events_js(){
	$page=CurrentPageName();
	echo "$('#BodyContent').load('$page?events=yes');";
}

function routes_add(){
	$vpn=new openvpn();
	$vpn->routes[$_GET["ROUTE_FROM"]]=$_GET["ROUTE_MASK"];
	$vpn->Save();
	
}
function routes_delete(){
	$vpn=new openvpn();
	unset($vpn->routes[$_GET["DELETE_ROUTE_FROM"]]);
	$vpn->Save();
	
}

function routes_list($noecho=0){
	$vpn=new openvpn();
	if(!is_array($vpn->routes)){return null;}
	reset($vpn->routes);
	$html="
<center><table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:75%'>
<thead class='thead'>
	<tr>
	<th>&nbsp;</th>
	<th>{ipaddr}</th>
	<th>{netmask}</th>
	<th>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>";	
	$classtr=null;
	while (list ($num, $ligne) = each ($vpn->routes) ){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}	
		if(trim($ligne)==null){continue;}
		$html=$html ."
			<tr class=$classtr>
		 	<td width=1%><img src='img/fw_bold.gif'></td>
			<td style='font-size:14px;font-weight:bold'><code>$num</code></td>
			<td style='font-size:14px;font-weight:bold'><code>$ligne</code></td>
			<td width=1%>" . imgtootltip('delete-32.png','{delete}',"OpenVPNRoutesDelete('$num')")."</td>
			</tr>
			";
						
						
			
				}	

	$html=$html . "</table></center>";
	$tpl=new templates();
	if($noecho==1){return $tpl->_ENGINE_parse_body($html);}
	echo $tpl->_ENGINE_parse_body($html);
	
}

function events(){
	
	$page=CurrentPageName();
	
	$html="
	
	<div style='width:100%;height:750px;overflow:auto' id='build-server-events'></div>
	<script>
		LoadAjax('build-server-events','$page?build-server-events=yes');
	</script>
	
	
	
	";
		
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);		
	
}

function events_content(){
	$sock=new sockets();
	$page=CurrentPageName();
	$datas=unserialize(base64_decode($sock->getFrameWork("network.php?OpenVPNServerLogs=yes")));
	$tpl=new templates();
	$tbl=array_reverse($datas);
	
$html=$tpl->_ENGINE_parse_body("<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
	<th width=1%>". imgtootltip("refresh-24.png","{refresh}","LoadAjax('build-server-events','$page?build-server-events=yes');")."</th>
	<th>{events}</th>
	</tr>
</thead>
<tbody class='tbody'>");		
	
	while (list ($num, $ligne) = each ($tbl) ){
		if(trim($ligne)==null){continue;}
		if(strpos($ligne, "WWWWWW")>0){continue;}
		if(strpos($ligne, "WRWRWRW")>0){continue;}
		if(strpos($ligne, "rWRrWRw")>0){continue;}
		if(trim($ligne)=="WWWR"){continue;}
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		if(preg_match("#^([A-Za-z]+)\s+([A-Za-z]+)\s+([0-9]+)\s+([0-9\:]+)\s+([0-9]+)\s+us=.+?\s+(.+)#", $ligne,$re)){
			$ligne=$re[6];
			$time=$re[4];
		}
		$ligne=htmlentities($ligne);
		if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $ligne,$re)){
			$ligne=str_replace($re[0], "<b>{$re[0]}</b>", $ligne);
		}
		
		
		$html=$html . "<tr class=$classtr>
		<td style='font-size:11px'>$time</td>
		<td><code style='font-size:11px'>$ligne</td>
		</tr>";
	}
	
	echo "$html</table>";
	
}

function events_sessions_details(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tbl=unserialize(base64_decode($sock->getFrameWork('cmd.php?OpenVPNServerSessions=yes')));
	
	
$html="<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
	<th>". imgtootltip("refresh-24.png","{refresh}","LoadAjax('events_sessions_details','$page?events-sessions-details=yes');")."</th>
	<th>{username}</th>
	<th>{ip_address}</th>
	<th>{b_received}</th>
	<th>{b_sent}</th>
	<th>{time}</th>
	</tr>
</thead>
<tbody class='tbody'>";		

while (list ($num, $ligne) = each ($tbl) ){
	if(preg_match("#([0-9\.]+),(.+?),([0-9\.\:]+),(.+)#", $ligne,$re)){$array[$re[2]]["LOCAL_IP"]=$re[1];}
	if(preg_match('#(.+?),([0-9\.\:]+),([0-9]+),([0-9]+),(.+)#',$ligne,$re)){
		if(preg_match("#(.+?):#", $re[2],$ri)){$re[2]=$ri[1];}
		$array[$re[1]]["REMOTE_IP"]=$re[2];
		$array[$re[1]]["b_received"]=$re[3];
		$array[$re[1]]["b_sent"]=$re[4];
		$array[$re[1]]["time"]=$re[5];
		
		
	}

}
	
	
	while (list ($uid, $ligne) = each ($array) ){
		if($uid==null){continue;}
		if($ligne["REMOTE_IP"]==null){continue;}
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$ligne["b_received"]=$ligne["b_received"]/1024;
		$ligne["b_received"]=FormatBytes($ligne["b_received"]);
		
		$ligne["b_sent"]=$ligne["b_sent"]/1024;
		$ligne["b_sent"]=FormatBytes($ligne["b_sent"]);		
		
		$html=$html . "
		<tr class=$classtr>
			<td width=1%><img src='img/connect-32.png'></td>
			<td nowrap><strong style='font-size:14px'>$uid</strong></td>
			<td nowrap><strong style='font-size:14px'>{$ligne["REMOTE_IP"]}/{$ligne["LOCAL_IP"]}</strong></td>
			<td nowrap><strong style='font-size:14px'>{$ligne["b_received"]}</strong></td>
			<td nowrap><strong style='font-size:14px'>{$ligne["b_sent"]}</strong></td>
			<td nowrap><strong style='font-size:14px'>{$ligne["time"]}</strong></td>
		</tr>";
		
	}	
	

	
	$html=$html."</table>";
		
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body($html);
	
}

function events_sessions(){
	$page=CurrentPageName();
	
	$html="
	<div style='width:100%;height:250px;overflow:auto' id='events_sessions_details'></div>
	<script>
		LoadAjax('events_sessions_details','$page?events-sessions-details=yes');
	</script>
	";
		
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}






function routes_shouldbe(){
	$ip=$_GET["ROUTE_SHOULD_BE"];
	if(preg_match("#([0-9]+)$#",$ip,$re)){
		$calc_ip=$re[1].".0.0.0";
		$calc_ip_end=$re[1].".255.255.255";
	}
	
	if(preg_match("#([0-9]+)\.([0-9]+)$#",$ip,$re)){
		$calc_ip=$re[1].".{$re[2]}.0.0";
		$calc_ip_end=$re[1].".{$re[2]}.255.255";
	}

	if(preg_match("#([0-9]+)\.([0-9]+)\.([0-9]+)$#",$ip,$re)){
		$calc_ip=$re[1].".{$re[2]}.{$re[3]}.0";
		$calc_ip_end=$re[1].".{$re[2]}.{$re[3]}.255";
	}	

	
	$ip=new IP();
	$cdir=$ip->ip2cidr($calc_ip,$calc_ip_end);
	$arr=$ip->parseCIDR($cdir);
	$rang=$arr[0];
	$netbit=$arr[1];
	$ipv=new ipv4($calc_ip,$netbit);
	echo "<strong>$cdir {$ipv->address()} - {$ipv->netmask()}</strong>"; 
	
	
}


function js(){
	
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{APP_OPENVPN}');
	$OPENVPN_WIZARD=$tpl->_ENGINE_parse_body('{OPENVPN_WIZARD}');
	$OPENVPN_SERVER_SETTINGS=$tpl->_ENGINE_parse_body('{OPENVPN_SERVER_SETTINGS}');
	$events=$tpl->_ENGINE_parse_body('{events}');
	$NETWORK_CONTROL_CENTER=$tpl->_ENGINE_parse_body('{NETWORK_CONTROL_CENTER}');
	$page=CurrentPageName();
	$function="LoadOpenvpn();";
	if(isset($_GET["infront"])){
		
		$start="<script>";
		$function="LoadOpenVPNv2();";
		$end="</script>";
	
	}
	
	
	$html="
	$start
		function LoadOpenvpn(){
			YahooWin2('705','$page?startpage=yes','$title');
		
		}
		
		function LoadOpenVPNv2(){
			$('#network-OpenVPN').load('$page?startpage=yes&newinterface=yes');
		}
		
		function StartWizard(){
			LoadAjax('wizarddiv','$page?wizard-key=yes');
		
		}
		
		function StartWizardServer(){
			LoadAjax('wizarddiv','$page?wizard-server=yes');
		
		}

		function WizardFinish(){
			LoadAjax('wizarddiv','$page?wizard-finish=yes');
		
		}		
		
		var x_SaveWizardKey= function (obj) {
			var tempvalue=obj.responseText;
			StartWizardServer();

		}	
		
		var x_SaveWizardServer= function (obj) {
			var tempvalue=obj.responseText;
			WizardFinish();
			}
			


		var x_SaveServerSettings= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue);}
			RefreshTab('main_openvpn_config');
			}	

		var x_SaveClientsSettings= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue);}
			OpenVPNClientsSettings();
			}				
		
		function SaveWizardServer(){
		var XHR = new XHRConnection();
		XHR.appendData('ENABLE_SERVER',document.getElementById('ENABLE_SERVER').value);
		XHR.appendData('LISTEN_PORT',document.getElementById('LISTEN_PORT').value);
		XHR.appendData('IP_START',document.getElementById('IP_START').value);
		XHR.appendData('NETMASK',document.getElementById('NETMASK').value);
		document.getElementById('wizarddiv').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>';
		XHR.sendAndLoad('$page', 'GET',x_SaveWizardServer);
			
		}
		
		function SaveServerSettings(){
		var XHR = new XHRConnection();
		
		if(document.getElementById('ENABLE_SERVER')){
			if(document.getElementById('ENABLE_SERVER').checked){
				XHR.appendData('ENABLE_SERVER','1');
			}else{
				XHR.appendData('ENABLE_SERVER','0');
			}
		}

		if(document.getElementById('DEV_TYPE')){XHR.appendData('DEV_TYPE',document.getElementById('DEV_TYPE').value);}
		if(document.getElementById('LISTEN_PORT')){XHR.appendData('LISTEN_PORT',document.getElementById('LISTEN_PORT').value);}
		if(document.getElementById('IP_START')){XHR.appendData('IP_START',document.getElementById('IP_START').value);}
		if(document.getElementById('NETMASK')){XHR.appendData('NETMASK',document.getElementById('NETMASK').value);}
		if(document.getElementById('PUBLIC_IP')){XHR.appendData('PUBLIC_IP',document.getElementById('PUBLIC_IP').value);}
		if(document.getElementById('BRIDGE_ETH')){XHR.appendData('BRIDGE_ETH',document.getElementById('BRIDGE_ETH').value);}
		
		
		if(document.getElementById('VPN_SERVER_IP')){XHR.appendData('VPN_SERVER_IP',document.getElementById('VPN_SERVER_IP').value);}
		if(document.getElementById('VPN_DHCP_FROM')){XHR.appendData('VPN_DHCP_FROM',document.getElementById('VPN_DHCP_FROM').value);}
		if(document.getElementById('VPN_DHCP_TO')){XHR.appendData('VPN_DHCP_TO',document.getElementById('VPN_DHCP_TO').value);}
		if(document.getElementById('VPN_SERVER_IP')){XHR.appendData('VPN_SERVER_IP',document.getElementById('VPN_SERVER_IP').value);}
		if(document.getElementById('SERVER_IP_START')){XHR.appendData('SERVER_IP_START',document.getElementById('SERVER_IP_START').value);}
		if(document.getElementById('SERVER_IP_END')){XHR.appendData('SERVER_IP_END',document.getElementById('SERVER_IP_END').value);}
		if(document.getElementById('VPN_DHCP_FROM_END')){XHR.appendData('VPN_DHCP_FROM_END',document.getElementById('VPN_DHCP_FROM_END').value);}
		if(document.getElementById('VPN_DHCP_TO_END')){XHR.appendData('VPN_DHCP_TO_END',document.getElementById('VPN_DHCP_TO_END').value);}
		if(document.getElementById('VPN_SERVER_DHCP_MASK')){XHR.appendData('VPN_SERVER_DHCP_MASK',document.getElementById('VPN_SERVER_DHCP_MASK').value);}
		if(document.getElementById('LISTEN_PROTO')){XHR.appendData('LISTEN_PROTO',document.getElementById('LISTEN_PROTO').value);}
		
		if(document.getElementById('VPN_DNS_DHCP_1')){XHR.appendData('VPN_DNS_DHCP_1',document.getElementById('VPN_DNS_DHCP_1').value);}
		if(document.getElementById('VPN_DNS_DHCP_2')){XHR.appendData('VPN_DNS_DHCP_2',document.getElementById('VPN_DNS_DHCP_2').value);}
		if(document.getElementById('LOCAL_BIND')){XHR.appendData('LOCAL_BIND',document.getElementById('LOCAL_BIND').value);}
		if(document.getElementById('IPTABLES_ETH')){XHR.appendData('IPTABLES_ETH',document.getElementById('IPTABLES_ETH').value);}
		if(document.getElementById('OpenVpnPasswordCert')){XHR.appendData('OpenVpnPasswordCert',document.getElementById('OpenVpnPasswordCert').value);}
		if(document.getElementById('BRIDGE_ADDR')){XHR.appendData('BRIDGE_ADDR',document.getElementById('BRIDGE_ADDR').value);}
		if(document.getElementById('CLIENT_NAT_PORT')){XHR.appendData('CLIENT_NAT_PORT',document.getElementById('CLIENT_NAT_PORT').value);}
		
		
		
		
		
		if(document.getElementById('OPENVPN_CLIENT_SETTINGS')){
			document.getElementById('OPENVPN_CLIENT_SETTINGS').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>';
			XHR.sendAndLoad('$page', 'GET',x_SaveClientsSettings);
		}
		
		if(document.getElementById('OPENVPN_SERVER_SETTINGS')){
			document.getElementById('OPENVPN_SERVER_SETTINGS').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>';
			XHR.sendAndLoad('$page', 'GET',x_SaveServerSettings);
			}
			
		
	}
	
	function OpenVPNChangeServerMode(){
		var XHR = new XHRConnection();
		if(document.getElementById('ENABLE_BRIDGE').checked){
			XHR.appendData('ENABLE_BRIDGE',1);
		}else{
			XHR.appendData('ENABLE_BRIDGE',0);
		}
		document.getElementById('OPENVPN_SERVER_SETTINGS').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>';
		XHR.sendAndLoad('$page', 'GET',x_SaveServerSettings);
		
	}
	
		
		function SaveWizardKey(){
			var XHR = new XHRConnection();
        	XHR.appendData('KEY_COUNTRY_NAME',document.getElementById('KEY_COUNTRY_NAME').value);
        	XHR.appendData('KEY_PROVINCE',document.getElementById('KEY_PROVINCE').value);
			XHR.appendData('KEY_CITY',document.getElementById('KEY_CITY').value);
			XHR.appendData('KEY_ORG',document.getElementById('KEY_ORG').value);
			XHR.appendData('KEY_EMAIL',document.getElementById('KEY_EMAIL').value);	
			document.getElementById('wizarddiv').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>';
			XHR.sendAndLoad('$page', 'GET',x_SaveWizardKey);	
		
		}
		
	var x_RouteShouldbe= function (obj) {
			var tempvalue=obj.responseText;
			document.getElementById('shouldbe').innerHTML=tempvalue;
			}					
		
		function RouteShouldbe(){
			var ROUTE_FROM=document.getElementById('ROUTE_FROM').value;
			var XHR = new XHRConnection();
			XHR.appendData('ROUTE_SHOULD_BE',ROUTE_FROM);
			XHR.sendAndLoad('$page', 'GET',x_RouteShouldbe);	
		}
		
	var x_OpenVpnAddRoute= function (obj) {
			var tempvalue=obj.responseText;
			LoadAjax('routeslist','$page?routes-list=yes');
			}				
		
		function OpenVpnAddRoute(){
			var XHR = new XHRConnection();
			XHR.appendData('ROUTE_FROM',document.getElementById('ROUTE_FROM').value);
			XHR.appendData('ROUTE_MASK',document.getElementById('ROUTE_MASK').value);
			document.getElementById('routeslist').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>';
			XHR.sendAndLoad('$page', 'GET',x_OpenVpnAddRoute);
			}
		
		function OpenVPNRoutesDelete(index){
			var XHR = new XHRConnection();
			XHR.appendData('DELETE_ROUTE_FROM',index);
			XHR.sendAndLoad('$page', 'GET',x_OpenVpnAddRoute);
			}
			
	var x_OpenVPNChangeNIC= function (obj) {
			var tempvalue=obj.responseText;
			document.getElementById('nicvpninfo').innerHTML=tempvalue;
			}				
			
		function OpenVPNChangeNIC(){
			var XHR = new XHRConnection();
			XHR.appendData('BRIDGE_ETH_SHOW',document.getElementById('BRIDGE_ETH').value);
			XHR.sendAndLoad('$page', 'GET',x_OpenVPNChangeNIC);
		
		}
		
		
		
		function OpenVPNEventsServer(){YahooWin3('705','$page?events=yes','$events');}		
		function OpenVPNEventsSessions(){YahooWin3('705','$page?events-session=yes','$events');}
		function OpenVPNClientsSettings(){YahooWin4('600','$page?clients-settings=yes','$OPENVPN_CLIENT_SETTINGS');}
		function LoadOpenVpnServerSettings(){YahooWin4('700','$page?server-settings=yes','$OPENVPN_SERVER_SETTINGS');}
		function OpenVPNNCC(){YahooWin4('800','$page?ncc=yes','$NETWORK_CONTROL_CENTER');}
		
		
	
		$function
	
	$end";
		
	echo $html;
	
	
}

function rebuild_certificate(){
	
	$tpl=new templates();
	$text=$tpl->javascript_parse_text("{rebuild_openvpn_certificate_perform}");
	$html="
	
	alert('$text');
	
	";	
	echo $html;
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?openvpn-rebuild-certificate=yes");
	
	
	
}


function startpage(){
	$html=GET_CACHED(__FILE__,__FUNCTION__,$_GET["newinterface"],TRUE);
	if($html<>null){return $html;}
	$page=CurrentPageName();
	$users=new usersMenus();
	
	if(!$users->OPENVPN_INSTALLED){
		echo FATAL_ERROR_SHOW_128("{OPENVPN_NOT_INSTALLED}");
		return;
	}
	
	$array["index"]='{index}';
	//
	$array["server-settings"]="{service_parameters}";
	$array["clients-scripts"]="{clients_scripts}";
	$array["additional_routes"]="{additional_routes}";
	$array["remote-sites"]="{REMOTE_SITES_VPN}";
	
	$array["events-session"]="{sessions}";
	$array["OPENVPN_SCHEDULE_RUN"]="{OPENVPN_SCHEDULE_RUN}";
	$array["events"]="{events}";
	$width=755;
	
	if(isset($_GET["newinterface"])){
		$width="100%";
		
		$newinterface="&newinterface=yes";
	}
	$font="font-size:20px";
	
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="OPENVPN_SCHEDULE_RUN"){
			$tab[]="<li><a href=\"index.openvpn.schedule.php?popup=yes\"><span style='$font'>$ligne</span></a></li>\n";
			continue;
		
		}
		
		if($num=="clients-scripts"){
			$tab[]="<li><a href=\"index.openvpn.clients.php\"><span style='$font'>$ligne</span></a></li>\n";
			continue;
		
		}
		
		if($num=="additional_routes"){
			$tab[]="<li><a href=\"index.openvpn.routes.php\"><span style='$font'>$ligne</span></a></li>\n";
			continue;
		
		}		
		
		if($num=="remote-sites"){
			$tab[]="<li><a href=\"openvpn.remotesites.php?infront=yes$newinterface\"><span style='$font'>$ligne</span></a></li>\n";
			continue;
		}
		
		$tab[]="<li><a href=\"$page?$num=yes$newinterface\"><span style='$font'>$ligne</span></a></li>\n";
			
		}
	$tpl=new templates();
	
	

	$html=build_artica_tabs($tab, "main_openvpn_config",1490);
		
	$tpl=new templates();
	$html=$tpl->_ENGINE_parse_body($html);
	SET_CACHED(__FILE__,__FUNCTION__,null,$html);
	return $html;	
	
}	
	
function index_page(){	
	$page=CurrentPageName();
	$sock=new sockets();
	$enable_button=null;
	$wizard=Paragraphe('64-wizard.png','{OPENVPN_WIZARD}','{OPENVPN_WIZARD_TEXT}',
	"javascript:YahooWin3('500','$page?wizard=yes','{OPENVPN_WIZARD}')",null,210,null,0,false);	
	
	
	$clients_settings=Paragraphe('global-settings.png','{OPENVPN_CLIENT_SETTINGS}',
	'{OPENVPN_CLIENT_SETTINGS_TEXT}',"javascript:OpenVPNClientsSettings()",null,210,null,0,false);
	
	
	
	
	
	$ncc=Paragraphe('64-win-nic-loupe.png','{NETWORK_CONTROL_CENTER}',
	'{NETWORK_CONTROL_CENTER_TEXT}',"javascript:OpenVPNNCC()",null,210,null,0,false);
	

	
	
	
	$server_connect=Paragraphe('server-connect-64.png','{OPENVPN_SERVER_CONNECT}',
	'{OPENVPN_SERVER_CONNECT_TEXT}',"javascript:Loadjs('openvpn.servers-connect.php')",null,210,null,0,false);
	
	
		
	
	$artica=Buildicon64("DEF_ICO_OPENVPN_ARTICA_CLIENTS");
	
	
	$f[]=$server_connect;
	$f[]=$ncc;

	//$f[]=$artica;
	$q=new mysql();
	$sql="SELECT ID,enabled,servername,serverport,connexion_name,connexion_type,routes FROM vpnclient WHERE connexion_type=2 and enabled=1 ORDER BY ID DESC";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$running=$sock->getFrameWork("openvpn.php?is-client-running={$ligne["ID"]}");
		if($running=="TRUE"){$img_running="folder-network-64.png";}else{$img_running="folder-network-64-grey.png";}	
		if(preg_match("#(.+?):#", $ligne["connexion_name"],$re)){$ligne["connexion_name"]=$re[1];}	
		
		$f[]=Paragraphe($img_running, $ligne["connexion_name"], "{manage_your_vpn_client_connection_text}","javascript:Loadjs('index.openvpn.client.php?ID={$ligne["ID"]}&cname={$ligne["connexion_name"]}')",null,210,null,0,false);
		
	}
	

		
	

	$vpn=new openvpn();
	if(intval($vpn->main_array["GLOBAL"]["ENABLE_SERVER"])==0){
		$enable_button="<center style='margin:30px'>". button("{enable_service}","Loadjs('index.openvpn.enable.progress.php')",42)."</center>";
	}
	
	$status=status(1);
	
	$html="
	<div style='font-size:30px;margin-bottom:20px'>{APP_OPENVPN_TEXT}</div>
	<table style='width:100%'>
	<tr>
	<td valign='top' width=280px><div id='openvpn-status'></div></td>
	<td valign='top'>
	<div class=explain style='font-size:22px'>{openvpn_whatis}</div>
	<br>
	$enable_button
	</td>
	</tr>
	</table>
	<script>
		function RefreshOpenVPNStatus(){
			LoadAjax('openvpn-status','$page?openvpn-status=yes');
		}
		RefreshOpenVPNStatus();
	</script>
	
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function wizard(){
	
	$html="<H1>{WELCOME_WIZARD}</H1>
	<div class=explain>{WELCOME_WIZARD_TEXT}</div>
	" . RoundedLightWhite("
	<div id='wizarddiv'>
		<div style='text-align:right'><input type='button' OnClick=\"javascript:StartWizard()\" value='{START_WIZARD}&nbsp;&raquo;'></div>
	</div>");
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}

function wizard_key(){
	
	$vpn=new openvpn();
	$country=Field_array_Hash($vpn->array_country_codes,"KEY_COUNTRY_NAME",$vpn->main_array["GLOBAL"]["KEY_COUNTRY_NAME"]);
	
	$html="
	<table style='width:100%'>
	<tr>
		<td valign='top' width=1%><img src='img/64-key.png'></td>
		<td valign='top'>
		<H3>{PKI}</H3>
		<div class=explain>{WIZARD_STEP1}</div>
		</td>
		<table style='width:100%'>
			<tr>
				<td class=legend>{country}:</td>
				<td style='font-size:12px'>{$vpn->array_country_codes[$vpn->main_array["GLOBAL"]["KEY_COUNTRY"]]}</td>
			</tr>
			<tr>
				<td class=legend>{change}:</td>
				<td>$country</td>
			</tr>
			<tr>
				<td class=legend>{province}:</td>
				<td>" . Field_text('KEY_PROVINCE',$vpn->main_array["GLOBAL"]["KEY_PROVINCE"],'width:220px')."</td>
			</tr>	
			<tr>
				<td class=legend>{city}:</td>
				<td>" . Field_text('KEY_CITY',$vpn->main_array["GLOBAL"]["KEY_CITY"],'width:220px')."</td>
			</tr>
			<tr>
				<td class=legend>{organization}:</td>
				<td>" . Field_text('KEY_ORG',$vpn->main_array["GLOBAL"]["KEY_ORG"],'width:220px')."</td>
			</tr>
			<tr>
				<td class=legend>{email}:</td>
				<td>" . Field_text('KEY_EMAIL',$vpn->main_array["GLOBAL"]["KEY_EMAIL"],'width:220px')."</td>
			</tr>														
			<tr>
				<td colspan=2 align='right'><input type='button' OnClick=\"javascript:SaveWizardKey()\" value='{next}&nbsp;&raquo;'></td>
			</tr>
	</tr>
	</table>
";
	
		$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
}

function wizard_server(){
$vpn=new openvpn();



	$html="
	<table style='width:100%'>
	<tr>
		<td valign='top' width=1%><img src='img/routing-domain-relay.png'></td>
		<td valign='top'>
		<H3>{PKI}</H3>
		<p class=capion>{WIZARD_SERVER}</p>
		</td>
		<table style='width:100%'>
			<tr>
				<td class=legend>{enable_openvpn_server_mode}:</td>
				<td style='font-size:12px'>" . Field_checkbox('ENABLE_SERVER','1',$vpn->main_array["GLOBAL"]["ENABLE_SERVER"])."</td>
			</tr>
			<tr>
				<td class=legend>{listen_port}:</td>
				<td>" . Field_text('LISTEN_PORT',$vpn->main_array["GLOBAL"]["LISTEN_PORT"],'width:90px')." UDP</td>
			</tr>
			<tr>
				<td colspan=2><br>
					<p class=caption>{LOCAL_NETWORK}</p>
				</td>
			<tr>
				<td class=legend>{from_ip_address}:</td>
				<td>" . Field_text('IP_START',$vpn->main_array["GLOBAL"]["IP_START"],'width:210px')."</td>
			<tr>
			<tr>
				<td class=legend>{netmask}:</td>
				<td>" . Field_text('NETMASK',$vpn->main_array["GLOBAL"]["NETMASK"],'width:210px')."</td>
			<tr>
					
			<tr>
				<td colspan=2 align='right'>
					<input type='button' OnClick=\"javascript:WizardFindMyNetworksMask()\" value='{newtork_help_me}'>
				</td>
			</tr>
				
			
				<td align='left'><input type='button' OnClick=\"javascript:StartWizard()\" value='&laquo;&nbsp;{back}'></td>
				<td align='right'><input type='button' OnClick=\"javascript:SaveWizardServer()\" value='{next}&nbsp;&raquo;'></td>
			</tr>
	</tr>
	</table>
";
	
		$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}

function server_settings(){
	
$vpn=new openvpn();
$nic=new networking();
$sock=new sockets();
$OpenVpnPasswordCert=$sock->GET_INFO("OpenVpnPasswordCert");
if($OpenVpnPasswordCert==null){$OpenVpnPasswordCert="MyKey";}




//openvpn_access_interface

$DEV_TYPE=$vpn->main_array["GLOBAL"]["DEV_TYPE"];
	

$dev=Field_array_Hash(
	array("tun"=>"{routed_IP_tunnel}","tap0"=>"{ethernet_tunnel}"),
	"DEV_TYPE",$vpn->main_array["GLOBAL"]["DEV_TYPE"],
	"OpenVPNChangeServerMode()",null,0,'font-size:16px;padding:3px'
	);
	
	
$dev="{routed_IP_tunnel}<input type='hidden' name='DEV_TYPE' id='DEV_TYPE' value='tun'>";	
	

if($vpn->main_array["GLOBAL"]["IP_START"]==null){$vpn->main_array["GLOBAL"]["IP_START"]="10.8.0.0";}
if($vpn->main_array["GLOBAL"]["NETMASK"]==null){$vpn->main_array["GLOBAL"]["NETMASK"]="255.255.255.0";} 

			
				

if($vpn->main_array["GLOBAL"]["ENABLE_BRIDGE_MODE"]==1){$openvpn_local=null;}
$CLIENT_NAT_PORT=$vpn->main_array["GLOBAL"]["CLIENT_NAT_PORT"];

if($CLIENT_NAT_PORT==null){$CLIENT_NAT_PORT=$vpn->main_array["GLOBAL"]["LISTEN_PORT"];}
$vpn->main_array["GLOBAL"]["PUBLIC_IP"];
$vpn->main_array["GLOBAL"]["CLIENT_NAT_PORT"];
$vpn->main_array["GLOBAL"]["LISTEN_PORT"];
$vpn->main_array["GLOBAL"]["LISTEN_PROTO"];
$vpn->main_array["GLOBAL"]["ENABLE_SERVER"];
$vpn->main_array["GLOBAL"]["ENABLE_BRIDGE_MODE"];
$vpn->main_array["GLOBAL"]["IP_START"];
$vpn->main_array["GLOBAL"]["IPTABLES_ETH"];

if($vpn->main_array["GLOBAL"]["PUBLIC_IP"]==null){
$status="<p class=text-error style='font-size:18px'><strong>{MISSING_PARAMETER}</strong><br>{MISSING_PARAMETER_TEXT}<br><strong>{public_ip_addr}</strong></p>";
}
if($vpn->main_array["GLOBAL"]["LISTEN_PORT"]==null){
$status="<p class=text-error style='font-size:18px'><strong>{MISSING_PARAMETER}</strong><br>{MISSING_PARAMETER_TEXT}<br><strong>{listen_port}</strong></p>";
}
if($vpn->main_array["GLOBAL"]["CLIENT_NAT_PORT"]==null){
$status="<p class=text-error style='font-size:18px'><strong>{MISSING_PARAMETER}</strong><br>{MISSING_PARAMETER_TEXT}<br><strong>{listen_port}:{public_ip_addr}</strong></p>";
}
if($vpn->main_array["GLOBAL"]["IP_START"]==null){
$status="<p class=text-error style='font-size:18px'><strong>{MISSING_PARAMETER}</strong><br>{MISSING_PARAMETER_TEXT}<br><strong>{from_ip_address}</strong></p>";
}
if($vpn->main_array["GLOBAL"]["NETMASK"]==null){
$status="<p class=text-error style='font-size:18px'><strong>{MISSING_PARAMETER}</strong><br>{MISSING_PARAMETER_TEXT}<br><strong>{netmask}</strong></p>";
}
if($vpn->main_array["GLOBAL"]["LISTEN_PROTO"]==null){
$status="<p class=text-error style='font-size:18px'><strong>{MISSING_PARAMETER}</strong><br>{MISSING_PARAMETER_TEXT}<br><strong>{protocol}</strong></p>";
}
if($vpn->main_array["GLOBAL"]["IP_START"]==null){
$status="<p class=text-error style='font-size:18px'><strong>{MISSING_PARAMETER}</strong><br>{MISSING_PARAMETER_TEXT}<br><strong>{from_ip_address}</strong></p>";
}
if($vpn->main_array["GLOBAL"]["NETMASK"]==null){
$status="<p class=text-error style='font-size:18px'><strong>{MISSING_PARAMETER}</strong><br>{MISSING_PARAMETER_TEXT}<br><strong>{netmask}</strong></p>";
}
$LDAP_AUTH="{no}";
$EnableOpenVPNEndUserPage="{no}";
if($vpn->main_array["GLOBAL"]["LDAP_AUTH"]==1){$LDAP_AUTH="{yes}";}
if($sock->GET_INFO("EnableOpenVPNEndUserPage")==1){$EnableOpenVPNEndUserPage="{yes}";}

$wake_up_ip=$vpn->main_array["GLOBAL"]["WAKEUP_IP"];
$tcp_ip=new ip();
if(!$tcp_ip->isValid($wake_up_ip)){$wake_up_ip="{disabled}";}
if(!$tcp_ip->isValid($vpn->main_array["GLOBAL"]["PUBLIC_IP"])){$vpn->main_array["GLOBAL"]["PUBLIC_IP"]="0.0.0.0";}


$ahref_edit="<a href=\"javascript:blur();\" OnClick=\"Loadjs('index.openvpn.server.php');\" style='font-size:16px;text-decoration:underline'>";

$button_edit=button("{change_settings}", "Loadjs('index.openvpn.server.php')",34);


if($vpn->main_array["GLOBAL"]["LOCAL_BIND"]==null){$vpn->main_array["GLOBAL"]["LOCAL_BIND"]="{none}";}

$openvpn_local="
			<tr>
				<td class=legend style='font-size:20px'>{openvpn_local}</a>:</td>
				<td style='font-weight:bold;font-size:20px'>{$vpn->main_array["GLOBAL"]["LOCAL_BIND"]}</a></td>
			</tr>";	



$mandatories="<table style='width:99%'>
			<tr>
				<td class=legend style='font-size:20px'>{listen_port}:</a></td>
				<td style='font-weight:bold;font-size:20px;width:850px'>{$vpn->main_array["GLOBAL"]["LISTEN_PORT"]}&nbsp;{$vpn->main_array["GLOBAL"]["LISTEN_PROTO"]}</a></td>
			</tr>
			$openvpn_local
			<tr>
				<td class=legend style='font-size:20px'>{public_ip_addr}</a>:</td>
				<td style='font-weight:bold;font-size:20px;'>{$vpn->main_array["GLOBAL"]["PUBLIC_IP"]}:$CLIENT_NAT_PORT</a></td>
			<tr>	
			<tr>
				<td class=legend style='font-size:20px'>{password}:</a></td>
				<td style='font-weight:bold;font-size:20px'>*****</a></td>
			<tr>
			</table>";
$proxy="{no}";
if($vpn->main_array["GLOBAL"]["USE_RPROXY"]==1){
	$proxy="{$vpn->main_array["GLOBAL"]["PROXYADDR"]}:{$vpn->main_array["GLOBAL"]["PROXYPORT"]}";
}	

if($vpn->main_array["GLOBAL"]["IPTABLES_ETH"]==null){$vpn->main_array["GLOBAL"]["IPTABLES_ETH"]="{no}";}
				
$mode_tun="<table style='width:99%'>
	<tr>
		<td class=legend style='font-size:20px'>{enable_authentication}</a>:</td>
		<td style='font-weight:bold;font-size:20px;;width:850px'>{$LDAP_AUTH}</td>
	<tr>
	<tr>
		<td class=legend style='font-size:20px'>{EnableOpenVPNEndUserPage}</a>:</td>
		<td style='font-weight:bold;font-size:20px'>{$EnableOpenVPNEndUserPage}</td>
		<td>&nbsp;</td>
	<tr>	
	<tr>
		<td class=legend style='font-size:20px'>{reverse_proxy}</a>:</td>
		<td style='font-weight:bold;font-size:20px'>$proxy</td>
	<tr>	
	
	<tr>
		<td class=legend style='font-size:20px'>{from_ip_address}</a>:</td>
		<td style='font-weight:bold;font-size:20px'>{$vpn->main_array["GLOBAL"]["IP_START"]}</td>
	<tr>
	<tr>
		<td class=legend style='font-size:20px'>{netmask}</a>:</td>
		<td style='font-weight:bold;font-size:20px'>{$vpn->main_array["GLOBAL"]["NETMASK"]}</a></td>
	</tr>
	<tr>
		<td class=legend style='font-size:20px'>{openvpn_access_interface}</a>:</td>
		<td style='font-weight:bold;font-size:20px'>{$vpn->main_array["GLOBAL"]["IPTABLES_ETH"]}</a></td>
	</tr>
	<tr>
		<td class=legend style='font-size:20px'>{wake_up_ip}</a>:</td>
		<td style='font-weight:bold;font-size:20px'>$wake_up_ip</a></td>
	</tr>	
	
</table>";	

	$VPN_SERVER_IP=$vpn->main_array["GLOBAL"]["VPN_SERVER_IP"];
	$VPN_DHCP_FROM=$vpn->main_array["GLOBAL"]["VPN_DHCP_FROM"];
	$VPN_DHCP_TO=$vpn->main_array["GLOBAL"]["VPN_DHCP_TO"];
	

	$tcp=new networking();
	if($vpn->main_array["GLOBAL"]["BRIDGE_ETH"]==null){$vpn->main_array["GLOBAL"]["BRIDGE_ETH"]="eth0";}
	$array_ip=$tcp->GetNicInfos($vpn->main_array["GLOBAL"]["BRIDGE_ETH"]);
	if($vpn->main_array["GLOBAL"]["VPN_SERVER_IP"]==null){$vpn->main_array["GLOBAL"]["VPN_SERVER_IP"]=$array_ip["IPADDR"];}
	if($vpn->main_array["GLOBAL"]["NETMASK"]==null){$vpn->main_array["GLOBAL"]["NETMASK"]=$array_ip["NETMASK"];}
	
if($vpn->main_array["GLOBAL"]["ENABLE_BRIDGE_MODE"]==1){
	
	$nics=Field_array_Hash($vpn->virtual_ip_lists(),'BRIDGE_ETH',$vpn->main_array["GLOBAL"]["BRIDGE_ETH"]);
}

$mode_tap="
	<div style='width:100%;margin-bottom:5px'>
		<table style='width:100%'>
			<tr>
				<td valign='top'>
					<div id='nicvpninfo' style='float:right;margin:5px;'>".ShowIPConfig($vpn->main_array["GLOBAL"]["BRIDGE_ETH"])."</div>
				</td>
				<td valign='top'>
					<div class=explain>{SERVER_MODE_TAP}</div>
				</td>
			</tr>
		</table>
	</div>
<table style='width:100%'>
	<tr>
		<td class=legend nowrap style='font-size:20px'>{BRIDGE_ETH}:</td>
		<td width=1% nowrap>$nics</td>
		<td align='left' style='font-weight:bold;font-size:20px' width=1% nowrap>". texttooltip("{add_virtual_ip_address}","{add_virtual_ip_address}","Loadjs('system.nic.config.php?js-add-nic=yes')",null,0,"font-size:16px;padding:3px")."</td>
	<tr>
	<tr>
		<td class=legend nowrap style='font-size:20px'>{BRIDGE_ADDR}:</td>
		<td width=1% style='font-weight:bold;font-size:20px' nowrap >" . Field_text('BRIDGE_ADDR',$vpn->main_array["GLOBAL"]["BRIDGE_ADDR"],'width:120px;font-size:16px;padding:3px')."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend nowrap style='font-size:20px'>{VPN_DHCP_FROM}:</td>
		<td width=1% style='font-weight:bold;font-size:20px' nowrap>" . Field_text('VPN_DHCP_FROM',$vpn->main_array["GLOBAL"]["VPN_DHCP_FROM"],'width:120px;font-size:16px;padding:3px')."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend nowrap style='font-size:20px'>{VPN_DHCP_TO}:</td>
		<td width=1% style='font-weight:bold;font-size:20px' nowrap>" . Field_text('VPN_DHCP_TO',$vpn->main_array["GLOBAL"]["VPN_DHCP_TO"],'width:120px;font-size:16px;padding:3px')."</td>
		<td>&nbsp;</td>
	</tr>			
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","SaveServerSettings()")."</td>
	</tr>				
</table>
";

$mode=$mode_tun;
if($vpn->main_array["GLOBAL"]["DEV_TYPE"]=="tap0"){
	$mode=$mode_tap;
}

if($vpn->main_array["GLOBAL"]["ENABLE_BRIDGE_MODE"]==1){
	$mode=$mode_tap;
}


if($vpn->main_array["GLOBAL"]["ENABLE_SERVER"]<>1){
	$status=FATAL_ERROR_SHOW_128("<strong style='font-size:22px'>{OPENVPN_NOT_ENABLED}</strong><hr>
			{OPENVPN_NOT_ENABLED_TEXT}
			")."<center style='margin:30px'>". button("{enable_service}","Loadjs('index.openvpn.enable.progress.php')",42)."</center>";
}

if($status==null){
	$button_apply=button("{OPENVPN_APPLY_CONFIG}", "Loadjs('index.openvpn.enable.progress.php')",34)."&nbsp;|&nbsp;";
	
}

$html="
<div style='font-size:28px;margin-bottom:30px'>{OPENVPN_SERVER_SETTINGS}</div>
$status
<div style='width:98%' class=form>


<div id='OPENVPN_SERVER_SETTINGS'>
<div style='text-align:right'>$button_script</div>
$mandatories
$mode
<div style='margin-top:15px;text-align:right;font-size:34px'><hr>$button_apply$button_edit</div>	
</div>
";
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);		
// openvpn --remote touzeau.ath.cx --port 1194 --dev tun --comp-lzo --tls-client --ca /home/dtouzeau/ca.crt --cert /home/dtouzeau/dtouzeau.crt --key /home/dtouzeau/dtouzeau.key --verb 5 --pull	
}

function ShowIPConfig($eth){
	
	
	$openvpn=new openvpn();
	$array_ip=$openvpn->virtual_ip_information();
	
	
	$html="<table style='width:100%'>
	<tr>
		<td class=legend nowrap>{BRIDGE_ETH}:</td>
		<td><span style='font-weight:bold;font-size:11px'>$eth</span></td>
	</tr>	
	<tr>
		<td class=legend nowrap>{ip_address}:</td>
		<td><span style='font-weight:bold;font-size:11px'>{$array_ip["IPADDR"]}</span></td>
	</tr>
	<tr>
		<td class=legend nowrap>{netmask}:</td> 
		<td><span style='font-weight:bold;font-size:11px'>{$array_ip["NETMASK"]}</span></td>
	</tr>	
	<tr>
		<td class=legend nowrap>{gateway}:</td>
		<td><span style='font-weight:bold;font-size:11px'>{$array_ip["GATEWAY"]}</span></td>
	</tr>		
	</table>";
	$tpl=new templates();
	return RoundedLightGrey($tpl->_ENGINE_parse_body($html)); 
	
	
}


function Clients_settings(){
	$vpn=new openvpn();
	$VPN_SERVER_IP=Field_text('VPN_SERVER_IP',$vpn->main_array["GLOBAL"]["VPN_SERVER_IP"],'width:120px');
	$VPN_DHCP_FROM=Field_text('VPN_DHCP_FROM',$vpn->main_array["GLOBAL"]["VPN_DHCP_FROM"],'width:120px');
	$VPN_DHCP_TO=Field_text('VPN_DHCP_TO',$vpn->main_array["GLOBAL"]["VPN_DHCP_TO"],'width:120px');	
	
	$ip=new IP;
	if(preg_match('#(.+?)\.([0-9]+)$#',$vpn->main_array["GLOBAL"]["VPN_DHCP_FROM"],$re)){
		$cdir=$ip->ip2cidr("{$re[1]}.0","{$re[1]}.255");
		
	}
	
	
if($vpn->main_array["GLOBAL"]["VPN_SERVER_DHCP_MASK"]==null){$vpn->main_array["GLOBAL"]["VPN_SERVER_DHCP_MASK"]=$vpn->main_array["GLOBAL"]["NETMASK"];}	
	
$html=$html="
<H1>{OPENVPN_CLIENT_SETTINGS}</H1>
<table style='width:100%'>
<tr>
	<td valign='top'><img src='img/global-settings.png'></td>
	<td valign='top'>
<div id='OPENVPN_CLIENT_SETTINGS'>
<table style='width:100%'><tr>
				<td class=legend>{VPN_SERVER_IP}:</td>
				<td>$VPN_SERVER_IP</td>
			<tr>
			<tr>
				<td class=legend>{VPN_SERVER_DHCP}:</td>
				<td align='left'>
					<table style='width:90%'>
						<tr>
							<td class=legend>{from}:</td><td>$VPN_DHCP_FROM</td><td class=legend>{to}:</td><td>$VPN_DHCP_TO</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td class=legend>{VPN_SERVER_DHCP_MASK}:</td>
				<td>".Field_text('VPN_SERVER_DHCP_MASK',$vpn->main_array["GLOBAL"]["VPN_SERVER_DHCP_MASK"],'width:120px')."&nbsp;cdir:$cdir</td>
			<tr>
			<tr>
				<td class=legend>{dns_server} 1:</td>
				<td>".Field_text('VPN_DNS_DHCP_1',$vpn->main_array["GLOBAL"]["VPN_DNS_DHCP_1"],'width:120px')."</td>
			<tr>
			<tr>
				<td class=legend>{dns_server} 2:</td>
				<td>".Field_text('VPN_DNS_DHCP_2',$vpn->main_array["GLOBAL"]["VPN_DNS_DHCP_2"],'width:120px')."</td>
			<tr>																	
			<tr>
				<td colspan=2 align='right'>
					<hr>
					". button("{apply}",":SaveServerSettings()")."
					
				</td>
			</tr>
			</table></div>
			</td>
			</tr>
			</table>";

$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);				
	
}

function wizard_finish(){
	$apply=ICON_OPENVPN_APPLY();
	$html="<H3>{WIZARD_FINISH}</H3>
	<p class=caption>{WIZARD_FINISH_TEXT}</p>
	<center>$apply</center>
	";
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);	
	
}

function OpenVPNChangeServerMode(){
	$DEV_TYPE=$_GET["OpenVPNChangeServerMode"];
	$vpn=new openvpn();
	$vpn->main_array["GLOBAL"]["DEV_TYPE"]=$DEV_TYPE;
	$vpn->Save();
	$tpl=new templates();
	//echo $tpl->_ENGINE_parse_body('{success}: {switch}=> '.$DEV_TYPE);	
	}

function SaveServerConf(){
	$tpl=new templates();
	if(isset($_GET["OpenVpnPasswordCert"])){
		$sock=new sockets();
		$oldpassword=$sock->GET_INFO("OpenVpnPasswordCert");
		if($oldpassword==null){$oldpassword="MyKey";}
		if($oldpassword<>$_GET["OpenVpnPasswordCert"]){
			echo $tpl->javascript_parse_text("{OPENVPN_PASSWORD_CHANGED}");
			
		}
		
		$sock->SET_INFO("OpenVpnPasswordCert",$_GET["OpenVpnPasswordCert"]);
	}
	
	$vpn=new openvpn();
	while (list ($num, $ligne) = each ($_GET) ){
		$vpn->main_array["GLOBAL"][$num]=$ligne;
		
	}
	$vpn->Save();	
	
	
	}


function SaveCertificate(){
	
	$vpn=new openvpn();
	while (list ($num, $ligne) = each ($_GET) ){
		$vpn->main_array["GLOBAL"][$num]=$ligne;
		
	}
	$vpn->Save();
	$vpn->BuildCertificate();
	
}

function routes_settings(){
	$list=routes_list(1);
	$html="
	<div class=explain>{routes_explain}</div>
	<table style='width:99%' class=form>
	 <tr>
	 	<td class=legend>{from_ip_address}:</td>
	 	<td>" . Field_text('ROUTE_FROM',null,'width:110px;font-size:16px;padding:3px',null,'RouteShouldbe()',null,false,'RouteShouldbe()')."</td>
	 </tr>
	<tr>
	 	<td class=legend>{netmask}:</td>
	 	<td>" . Field_text('ROUTE_MASK',null,'width:110px;font-size:16px;padding:3px')."</td>
	 </tr>	
	<tr>
	<td colspan=2 class='legend' style='padding-right:50px'><span id='shouldbe'></span></td>
	<tr>
		<td colspan=2 align='right' ><hr>". button("{add}","OpenVpnAddRoute()")."</td>
	</tr>
	</table>
	<br>
	<div style='width:100%;height:150px;overflow:auto' id='routeslist'>$list</div>";
	
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);	
}

function RestartServer(){
	$sock=new sockets();
	$datas=base64_decode($sock->getFrameWork("openvpn.php?RestartOpenVPNServer=yes"));
	$tbl=explode("\n",$datas);
	if(is_array($tbl)){
	$tbl=array_reverse($tbl);
	
		while (list ($num, $ligne) = each ($tbl) ){
		if(trim($ligne)==null){continue;}
		$RRR[$ligne]=$ligne;
			
		}
	
	while (list ($num, $ligne) = each ($RRR) ){
		$l=$l."<div><code style='font-size:10px'>" . htmlentities($ligne)."</code></div>";
	}}
	
	
	$html="<div style='width:100%;height:200px;overflow:auto'>$l</div>";
	
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);
	
}

function status($noecho=0){
	$ini=new Bs_IniHandler();
	$sock=new sockets();
	$ini->loadString(base64_decode($sock->getFrameWork("cmd.php?openvpn-status=yes")));
	$TCP_NICS_STATUS_ARRAY=unserialize(base64_decode($sock->getFrameWork("cmd.php?TCP_NICS_STATUS_ARRAY=yes")));
	$status=DAEMON_STATUS_ROUND("OPENVPN_SERVER",$ini);
	$refresh="<div style='width:100%;text-align:right'>".imgtootltip("refresh-24.png","{refresh}","RefreshOpenVPNStatus()")."</div>";
	$radius="-webkit-border-radius: 5px;-moz-border-radius: 5px;border-radius: 5px;/* behavior:url(/css/border-radius.htc); */";
	$color="black";
	$icon="network-128-ok.png";
	
	while (list ($interface, $MAIN) = each ($TCP_NICS_STATUS_ARRAY) ){
		if(!isset($MAIN['PEER'])){continue;}
		$IPADDR=$MAIN["IPADDR"];
		$NETMASK=$MAIN["NETMASK"];
		$PEER=$MAIN["PEER"];
		
		$tr[]="
		<div style='margin-top:15px;margin-bottom:15px'>
		<table style='width:100%;border:2px solid #CCCCCC;margin-bottom:10x;$radius'>
		<tr>
				<td valign='top' style='padding-top:10px;padding-bottom:10px'><img src='img/$icon'></td>
				<td valign='top' style='padding-top:10px;padding-bottom:10px'>
				<table style='width:100%'>
				<tr>
				<td style='font-size:16px;color:$color' class=legend nowrap>{interface}:</td>
				<td style='font-weight:bold;font-size:16px;color:$color'>$interface</td>
				</tr>				
				<tr>
				<td style='font-size:16px;color:$color' class=legend>{tcp_address}:</td>
				<td style='font-weight:bold;font-size:16px;color:$color'>$IPADDR</td>
				</tr>
				<tr>
					<td class=legend nowrap style='color:$color;font-size:16px'>{netmask}:</td>
					<td style='font-weight:bold;font-size:16px;color:$color'>$NETMASK</a></td>
				</tr>	
				<tr>
					<td class=legend nowrap style='color:$color;font-size:16px'>Peer:</td>
					<td style='font-weight:bold;font-size:16px;color:$color'>$PEER</a></td>
				</tr>				
				</table>
			</td>
		</tr>
		</table></div>
		";		
	}
	
	$ncs=@implode("", $tr);
	
	$ini=new Bs_IniHandler();
	$ini->loadString(base64_decode($sock->getFrameWork("cmd.php?openvpn-clients-status=yes")));
	if(is_array($ini->_params)){
		while (list ($key, $ligne) = each ($ini->_params)){
		$status=$status.DAEMON_STATUS_ROUND($key,$ini);
		}			
			
	}
	
	$Certificate=$sock->getFrameWork("openvpn.php?ifAllcaExists=yes");
	if($Certificate<>"TRUE"){
		$status=$status.FATAL_ERROR_SHOW_128("{OPENSSL_NO_CERTIFICATE_BUILDED}");
		
	}
	
	
	if($noecho==1){return $status.$refresh;}else{
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body($status.$refresh.$ncs);
	}
		
}

function ncc(){
	
	$net=new networking();
	$ip=new IP();
	$vpn=new openvpn();
	
$nic=new networking();

while (list ($num, $ligne) = each ($nic->array_TCP) ){
	if($ligne==null){continue;}
		$ethi[$num]=$ligne;
	}  	
	
	// LOCAL_NETWORK IP_START NETMASK  
	$listen_eth=$vpn->main_array["GLOBAL"]["BRIDGE_ETH"];
	$local_ip=$net->array_TCP[$listen_eth];
	$listen_eth_ip=$local_ip;
	$public_ip=$vpn->main_array["GLOBAL"]["PUBLIC_IP"];
	$LISTEN_PORT=$vpn->main_array["GLOBAL"]["LISTEN_PORT"];
	$LISTEN_PROTO=$vpn->main_array["GLOBAL"]["LISTEN_PROTO"];
	$VPN_SERVER_IP=$vpn->main_array["GLOBAL"]["VPN_SERVER_IP"];
	$VPN_DHCP_FROM=$vpn->main_array["GLOBAL"]["VPN_DHCP_FROM"];
	$VPN_DHCP_TO=$vpn->main_array["GLOBAL"]["VPN_DHCP_TO"];
	$VPN_DNS_DHCP_1=$vpn->main_array["GLOBAL"]["VPN_DNS_DHCP_1"];
	$VPN_DNS_DHCP_2=$vpn->main_array["GLOBAL"]["VPN_DNS_DHCP_2"];
	$PUBLIC_IP=$vpn->main_array["GLOBAL"]["PUBLIC_IP"];
	$IPTABLES_ETH=$vpn->main_array["GLOBAL"]["IPTABLES_ETH"];
	$DEV_TYPE=$vpn->main_array["GLOBAL"]["DEV_TYPE"];
	$IP_START=$vpn->main_array["GLOBAL"]["IP_START"];
	$CLIENT_NAT_PORT=$vpn->main_array["GLOBAL"]["CLIENT_NAT_PORT"];
	
	$VPN_SERVER_DHCP_MASK=$vpn->main_array["GLOBAL"]["VPN_SERVER_DHCP_MASK"];
	if($local_ip==null){$listen_eth_ip="<span style='color:#d32d2d'>{error}</span>";}
	if($public_ip==null){$public_ip="<span style='color:white'>{error}</span>";}
	
	if($VPN_SERVER_IP==null){$VPN_SERVER_IP="<span style='color:#d32d2d'>{error}</span>";}
	if($VPN_DHCP_FROM==null){$VPN_DHCP_FROM="<span style='color:#d32d2d'>{error}</span>";}
	if($VPN_DHCP_TO==null){$VPN_DHCP_TO="<span style='color:#d32d2d'>{error}</span>";}
	if($VPN_SERVER_DHCP_MASK==null){$VPN_SERVER_DHCP_MASK="<span style='color:#d32d2d'>{error}</span>";}
	
	if($CLIENT_NAT_PORT==null){$CLIENT_NAT_PORT=$LISTEN_PORT;}
	
	
	if($IPTABLES_ETH<>null){$VPN_SERVER_IP=$ethi[$IPTABLES_ETH];}
	
	if($LISTEN_PORT==null){$LISTEN_PORT="<span style='color:#d32d2d'>{error}</span>";}
	
	$listen_eth="$listen_eth  (br0)<br>$listen_eth_ip";
	if($listen_eth==null){$listen_eth="<span style='color:#d32d2d'>{error}</span>";}
	
	if($DEV_TYPE=='tun'){
		$listen_eth=" $VPN_SERVER_IP <-> tun0 iptables";
		$VPN_DHCP_FROM=$IP_START;
		if(!preg_match('#([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#',$VPN_DHCP_FROM,$re)){
		$VPN_DHCP_FROM="<span style='color:#d32d2d'>{error}</span>";
		}else{
		$cdir=$ip->ip2cidr("{$re[1]}.{$re[2]}.{$re[3]}.0","{$re[1]}.{$re[2]}.{$re[3]}.255");
		$tb=explode("/",$cdir);
		$v4=new ipv4($tb[0],$tb[1]);
		$VPN_DHCP_FROM="{$re[1]}.{$re[2]}.{$re[3]}.2";
		$VPN_DHCP_TO="{$re[1]}.{$re[2]}.{$re[3]}.254";
		$VPN_SERVER_DHCP_MASK="{$tb[0]} - " . $v4->netmask();
		}
	}
	
	if($VPN_SERVER_IP==null){$VPN_SERVER_IP="<span style='color:#d32d2d'>{error}</span>";}
	if($VPN_DHCP_FROM==null){$VPN_DHCP_FROM="<span style='color:#d32d2d'>{error}</span>";}
	if($VPN_DHCP_TO==null){$VPN_DHCP_TO="<span style='color:#d32d2d'>{error}</span>";}
	if($VPN_SERVER_DHCP_MASK==null){$VPN_SERVER_DHCP_MASK="<span style='color:#d32d2d'>{error}</span>";}	
	
	
	if(!preg_match('#([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#',$local_ip,$re)){
		$local_network="<span style='color:#d32d2d'>{error}</span>";
	}else{
		$cdir=$ip->ip2cidr("{$re[1]}.{$re[2]}.{$re[3]}.0","{$re[1]}.{$re[2]}.{$re[3]}.255");
		$tb=explode("/",$cdir);
		$v4=new ipv4($tb[0],$tb[1]);
		$local_network="{$tb[0]} - " . $v4->netmask();
	}
	
	$sql="SELECT * FROM vpnclient WHERE connexion_type=1 ORDER BY sitename DESC";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$ip=$ligne["IP_START"];
		$mask=$ligne["netmask"];
		if(!preg_match('#([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#',$ip,$re)){continue;}
		$route[]="<span style='font-size:10px'>route {$re[1]}.{$re[2]}.{$re[3]}.0 $mask GW $VPN_SERVER_IP</span>";
		
	}
	
if(is_array($route)){
	$routes=implode("<br>",$route);
}

	
	$html="
	<H1>{NETWORK_CONTROL_CENTER}</H1>
	<div style='background-image:url(img/bg_vpn1.png);width:750px;height:420px;background-repeat:no-repeat;font-size:16px'></div>
	<div style='position:absolute;top:30px;left:700px;'><input type='button' OnClick=\"javascript:OpenVPNNCC()\" value='{refresh}'></div>
	<div style='position:absolute;top:240px;left:210px;font-size:14px;text-align:center'>{BRIDGE_ETH}<br>$listen_eth</div>
	<div style='position:absolute;top:450px;left:80px;font-size:14px;text-align:center'>{local_network}<br>$local_network<br>$routes</div>
	<div style='position:absolute;top:125px;left:410px;font-size:14px;text-align:center;color:black;background-color:#D7E4FB;padding:3px;border:1px solid black'>
		{public_ip_addr}<br>$public_ip<br>{listen_port}:$LISTEN_PORT:$CLIENT_NAT_PORT ($LISTEN_PROTO)
	</div>
	<div style='position:absolute;top:125px;left:230px;font-size:14px;text-align:center;'>{VPN_SERVER_IP}<br>$VPN_SERVER_IP</div>
	<div style='position:absolute;top:190px;left:580px;font-size:12px;text-align:center;;background-color:#FFFF99;border:1px solid black;padding:3px'>
		DHCP<br>$VPN_DHCP_FROM - $VPN_DHCP_TO
		<br>{netmask} $VPN_SERVER_DHCP_MASK<br>
		{dns_servers}:$VPN_DNS_DHCP_1 $VPN_DNS_DHCP_2
	</div>
	
	";
	

	
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);	
}

function SaveBridgeMode(){
	$vpn=new openvpn();
	$vpn->main_array["GLOBAL"]["ENABLE_BRIDGE_MODE"]=$_GET["ENABLE_BRIDGE"];
	$vpn->Save();
}

function LocalParagraphe($title,$text,$js,$img){
		$js=str_replace("javascript:","",$js);
		$id=md5($js);
		$img_id="{$id}_img";
		if(strpos($text,"}")==0){$text="{{$text}}";}
		
		
	$html="
	<table style='width:95%;'>
	<tr>
	<td width=1% valign='top'>" . imgtootltip($img,$text,"$js",null,$img_id)."</td>
	<td><a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:16px;text-decoration:underline;font-weight:bold'>{{$title}}</a><div style='font-size:12px;'>$text</div></td>
	</tr>
	</table>";
	

return RoundedLightGreen($html)."<p>&nbsp;</p>";
		
	
}


?>
