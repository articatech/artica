<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.system.nics.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once('ressources/class.mysql.shorewall.inc');
	include_once('ressources/class.tcpip.inc');
	$usersmenus=new usersMenus();
	if(!$usersmenus->AsArticaAdministrator){die();}
	
	
	if(isset($_GET["switch-tab"])){switch_tab();exit;}
	
	if(isset($_GET["switch-status-section"])){main_switch_status();exit;}
	if(isset($_GET["switch-status"])){switch_status();exit;}
	if(isset($_GET["MainParams-js"])){MainParams_js();exit;}
	if(isset($_GET["MainParams-tab"])){MainParams_tab();exit;}
	if(isset($_GET["MainParams-popup"])){MainParams_popup();exit;}
	
	
	if(isset($_GET["port-js"])){port_js();exit;}
	if(isset($_GET["port-tab"])){port_tab();exit;}
	if(isset($_GET["port-popup"])){port_popup();exit;}
	
	
	
	if(isset($_GET["delete-virtual-js"])){delete_virtual_js();exit;}
	if(isset($_POST["delete-virtual-perform"])){delete_virtual_perform();exit;}
	
	if(isset($_GET["ports-list"])){main_switch_ports();exit;}
	if(isset($_GET["switch-ports-table"])){main_switch();exit;}
	if(isset($_POST["ipaddr"])){port_save();exit;}
	if(isset($_POST["Uninstall"])){Uninstall();exit;}
	
	if(isset($_POST["VirtualSwitchEnabled"])){VirtualSwitchEnabled();exit;}
	
	
tabs();


function main_switch_status(){
	$switch=$_GET["eth"];
	$tpl=new templates();
	$sock=new sockets();
	$datas=$sock->getFrameWork("vde.php?switch-main-status=$switch");
	$ini=new Bs_IniHandler();
	$ini->loadString($datas);
	$f[]=DAEMON_STATUS_ROUND("VDE_$switch",$ini,null,0);
	$f[]=DAEMON_STATUS_ROUND("VDHOOK_$switch",$ini,null,0);
	
	$install=button("{install_virtual_switch}",
	"Loadjs('virtualswitch.install.php?switch=$switch')",30);
	
	$html="
	<div style='font-size:22px' class=explain>{vde_switch_explain}</div>		
	<center style='margin:50px'>$install</center>
	".CompileTr2($f);
	echo $tpl->_ENGINE_parse_body($html);
	
}


function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();

	$sock=new sockets();
	$nics=unserialize(base64_decode($sock->getFrameWork("cmd.php?list-nics=yes")));
	while (list ($i, $nic) = each ($nics) ){
		if(preg_match("#^virt#", $nic)){continue;}
		$array[$nic]="{virtual_switch} $nic";
	}
	
	
	
	$fontsize=22;
	ksort($array);
	
	while (list ($num, $ligne) = each ($array) ){
		$tab[]="<li><a href=\"$page?switch-tab=$num\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
		
	
	}
	
	echo build_artica_tabs($tab, "main_virtualswitch",1490)."<script>LeftDesign('nic-white-255-opac20.png');</script>";
	
	
}

function switch_tab(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$fontsize=22;
	$eth=$_GET["switch-tab"];
	
	$array["switch-status-section"]="{status}";
	$array["switch-ports-table"]="{ports}";
	
	while (list ($num, $ligne) = each ($array) ){
		$tab[]="<li><a href=\"$page?$num=yes&eth=$eth\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
	
	
	}
	
	echo build_artica_tabs($tab, "main_virtualswitchtab$eth");	
	
}


function delete_virtual_js(){
	header("content-type: application/x-javascript");
	$ID=$_GET["delete-virtual-js"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$t=$_GET["t"];
	$tt=time();
	$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT * FROM nics_switch WHERE ID=$ID","artica_backup"));
	$ask=$tpl->javascript_parse_text("{delete_this_virtual_address}");
	$virtname="virt{$ID}: {$ligne["ipaddr"]} port {$ligne["port"]}";
	echo "
	var xDelete$tt=function (obj) {
		var results=obj.responseText;
		if(results.length>5){alert(results);return;}
		$('#table-$t').flexReload();
	}			
	
	
	function Delete$tt(){
		if(! confirm('$ask $virtname ?') ){return false;}
		var XHR = new XHRConnection();
		XHR.appendData('delete-virtual-perform','{$ID}');
		XHR.sendAndLoad('$page', 'POST',xDelete$tt);
	}
		
	Delete$tt();";
	
}
function delete_virtual_perform(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$sock=new sockets();
	$ID=$_POST["delete-virtual-perform"];
	$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT * FROM nics_switch WHERE ID=$ID","artica_backup"));
	$virtname="virt{$ID}";
	$nic=$ligne["nic"];
	echo $sock->getFrameWork("vde.php?virtual-delete=$virtname&nic=$nic");
	$q->QUERY_SQL("DELETE FROM nics_switch WHERE ID=$ID","artica_backup");
	$sock=new sockets();
	$sock->getFrameWork("vde.php?port-delete=$ID");
	
}


function port_tab(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$sock=new sockets();
	$t=$_GET["t"];
	$port=$_GET["port"];
	$switch=$_GET["switch"];
	$users=new usersMenus();
	$new_virtual_ip=$tpl->javascript_parse_text("{new_virtual_ip}");
	$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT ipaddr,ID FROM nics_switch WHERE port='$port' AND nic='$switch'","artica_backup"));
	if($ligne["ipaddr"]<>null){$new_virtual_ip=$ligne["ipaddr"];}
	$title=$tpl->javascript_parse_text("$new_virtual_ip");
	$ID=$ligne["ID"];
	if(!is_numeric($ID)){$ID=0;}
	$fontsize=20;
	$array["port-popup"]=$title;
	
	
	
/*	if($ID>0){
		$array["port-roles"]="{roles}";
	}
	
*/	
	
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="port-roles"){
			$tab[]="<li><a href=\"virtualswitch.roles.php?eth=virt$ID\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}
		
		if($num=="eth.services"){
			$tab[]="<li><a href=\"shorewall.eth.services.php?eth=virt$ID\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}
		
		$tab[]="<li><a href=\"$page?$num=yes&port=$port&switch=$switch&t=$t&ID=$ID\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
	
	
	}
	
	echo build_artica_tabs($tab, "main_virtualport{$port}");	
	
}



function MainParams_js(){
	$q=new mysql();
	$tpl=new templates();
	$page=CurrentPageName();
	$t=$_GET["t"];
	header("content-type: application/x-javascript");
	$switch=$_GET["switch"];
	$title=$tpl->javascript_parse_text("{virtual_switch}:$switch");
	
	echo "YahooWin('750','$page?MainParams-tab=yes&switch=$switch&t=$t','$title')";
}
function MainParams_tab(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$sock=new sockets();
	$t=$_GET["t"];
	$switch=$_GET["switch"];
	
	$new_virtual_ip=$tpl->javascript_parse_text("{virtual_switch}:$switch");
	$title=$tpl->javascript_parse_text("$new_virtual_ip");
	$fontsize=14;
	$array["MainParams-popup"]=$title;
	
	while (list ($num, $ligne) = each ($array) ){
		$tab[]="<li><a href=\"$page?$num=yes&switch=$switch&t=$t\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
	
	
	}
	
	echo build_artica_tabs($tab, "main_switch{$switch}");	
}

function MainParams_popup(){
	$page=CurrentPageName();
	$switch=$_GET["switch"];
	$tpl=new templates();
	$sock=new sockets();
	$vde_remove_ask=$tpl->javascript_parse_text("{vde_remove_ask}");
	$VirtualSwitchEnabled=$sock->GET_INFO("VirtualSwitchEnabled{$switch}");
	if(!is_numeric($VirtualSwitchEnabled)){$VirtualSwitchEnabled=1;}
	
	$ARRAY=unserialize(base64_decode($sock->getFrameWork("vde.php?switch-status=yes&switch=$switch")));
	$t=time();
	if(!$ARRAY["INSTALLED"]){
		$error=FATAL_ERROR_SHOW_128("{error_virtual_switch_not_installed}").
		"<center style='margin:20px'>".button("{install_virtual_switch}",
				"Loadjs('virtualswitch.install.php?switch=$switch')",18)."</center>";
		echo $tpl->_ENGINE_parse_body($error);
		return;
	}
	
	$p=Paragraphe_switch_img("{enable_disable_virtualswitch}", 
			"{enable_disable_virtualswitch_text}",
			"VirtualSwitchEnabled{$switch}",$VirtualSwitchEnabled
		);
	
	
	$html="
	<div style='width:98%' class=form>
		<div style='font-size:18px;margin:15px'>{virtual_switch} $switch</div>		
		<table style='width:100%'>
			<tr>
				<td style='vertical-align:top'><div id='status-$t'></div></td>
				<td style='vertical-align:top'>
						<div style='width:98%' class=form>
							$p
							<hr><div style='text-align:right'>". button("{apply}","Save$t()",16)."</div>
						</div>
					</td>
			</tr>
		</table>
	</div>	
	<div style='width:98%' class=form>
		<center>". button("{uninstall}","Uninstall$t()",16)."</center>
	</div>
	
	<script>
		LoadAjax('status-$t','$page?switch-status=yes&switch=$switch');
		
	var xSave$t=function (obj) {
		var results=obj.responseText;
		if(results.length>5){alert(results);return;}
		Loadjs('$page?MainParams-js=yes&switch=$switch&t={$_GET["t"]}')
		
	}			
	
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('VirtualSwitchEnabled','{$switch}');
		XHR.appendData('enabled',document.getElementById('VirtualSwitchEnabled{$switch}').value);
		XHR.sendAndLoad('$page', 'POST',xSave$t);
	}

	function Uninstall$t(){
		if(!confirm('$switch: $vde_remove_ask')){return;}
		var XHR = new XHRConnection();
		XHR.appendData('Uninstall','{$switch}');
		XHR.sendAndLoad('$page', 'POST',xSave$t);
	}		
		
	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function VirtualSwitchEnabled(){
	$switch=$_POST["VirtualSwitchEnabled"];
	$enabled=$_POST["enabled"];
	$sock=new sockets();
	$sock->SET_INFO("VirtualSwitchEnabled{$switch}",$enabled);
	$sock->getFrameWork("vde.php?switch-reconfigure=$switch");
	$sock->getFrameWork("vde.php?switch-restart=$switch");
}
function Uninstall(){
	
	$switch=$_POST["Uninstall"];
	$sock=new sockets();
	$sock->getFrameWork("vde.php?switch-remove=$switch");
	
	$sql="SELECT ID FROM nics_switch WHERE nic='$switch'";
	$q=new mysql();
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(mysql_num_rows($results)==0){return;}
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$virtname="virt{$ligne["ID"]}";
		echo $sock->getFrameWork("vde.php?virtual-delete=$virtname&nic=$switch");
		$q->QUERY_SQL("DELETE FROM nics_switch WHERE ID={$ligne["ID"]}","artica_backup");
		$sh=new mysql_shorewall();
		$sh->INTERFACE_DELETE($virtname);
		
	}
	
	
}

function port_js(){
	header("content-type: application/x-javascript");
	$port=$_GET["port"];
	$switch=$_GET["switch"];
	$t=$_GET["t"];
	$q=new mysql();
	$tpl=new templates();
	$page=CurrentPageName();
	$new_virtual_ip=$tpl->javascript_parse_text("{new_virtual_ip}");
	$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT ipaddr FROM nics_switch WHERE port='$port' AND nic='$switch'","artica_backup"));
	if($ligne["ipaddr"]<>null){$new_virtual_ip=$ligne["ipaddr"];}
	$title=$tpl->javascript_parse_text("{virtual_switch}:$switch {port}:$port $new_virtual_ip");
	echo "YahooWin('750','$page?port-tab=yes&port=$port&switch=$switch&t=$t','$title')";
	
}

function port_save(){
	$ID=$_POST["ID"];
	$shore=new mysql_shorewall();
	$ip=new IP();
	if(!$ip->isIPAddress($_POST["gateway"])){$_POST["gateway"]="";}
	if(!$ip->isIPAddress($_POST["ipaddr"])){echo "{$_POST["ipaddr"]} -> FALSE\n";return;}
	if(!preg_match("#(.+?)\/(.+)#", $_POST["cdir"])){$_POST["cdir"]="";}
	
	
	
	
	
	$q=new mysql();
	if(!$q->FIELD_EXISTS("nics_switch", "vlan", "artica_backup")){
		$sql="ALTER TABLE `nics_switch` ADD `vlan` smallint( 2 ) NOT NULL,ADD INDEX(`vlan`)";
		$q->QUERY_SQL($sql,'artica_backup');
	}
	if(!$q->FIELD_EXISTS("nics_switch", "netzone", "artica_backup")){
		$sql="ALTER TABLE `nics_switch` ADD `netzone` VARCHAR( 10 ) NOT NULL,ADD UNIQUE KEY `netzone` (`netzone`)";
		$q->QUERY_SQL($sql,'artica_backup');
	}	
	
	if($_POST["netzone"]==null){
		echo "Network Zone must be defined\n";
		return;
	}
	
	if($_POST["netzone"]<>null){
		if(strlen($_POST["netzone"])>5){
			echo "NetZone {$_POST["netzone"]} at most 5 characters long\n";
			return;
		}
		
		if(is_numeric(substr($_POST["netzone"], 0,1))){
			echo "Network Zone Must start with a letter\n";
			return;
		}
		
		if($ID==0){
			$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT netzone FROM nics_switch WHERE netzone='{$_POST["netzone"]}'","artica_backup"));
			if($ligne["netzone"]<>null){
				echo "Network Zone {$ligne["netzone"]} already defined\n";
				return;
			}
		}
	}
	
	if($_POST["netzone"]==null){
		if($ID>0){
		$_POST["netzone"]="virt{$ID}";}
	}
		
	
	
	$sql="INSERT INTO nics_switch (`nic`,`netzone`, `ipaddr`,`netmask`,`cdir`,`gateway`,`metric`,`port`,`vlan`) 
	VALUES
	('{$_POST["nic"]}','{$_POST["netzone"]}','{$_POST["ipaddr"]}','{$_POST["netmask"]}','{$_POST["cdir"]}',
	'{$_POST["gateway"]}','{$_POST["metric"]}','{$_POST["port"]}','{$_POST["vlan"]}')";
	
	$sql_edit="UPDATE nics_switch SET `nic`='{$_POST["nic"]}',
	`ipaddr`='{$_POST["ipaddr"]}',
	`netzone`='{$_POST["netzone"]}',
	`netmask`='{$_POST["netmask"]}',
	`cdir`='{$_POST["cdir"]}',
	`gateway`='{$_POST["gateway"]}',
	`port`='{$_POST["port"]}',
	`vlan`='{$_POST["vlan"]}',
	`metric`='{$_POST["metric"]}' WHERE ID='{$_POST["ID"]}'";
	
	if($_POST["ID"]>0){$sql=$sql_edit;}
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	if($_POST["netzone"]==null){
		$_POST["netzone"]="virt{$q->last_id}";
		$q->QUERY_SQL("UPDATE nics_switch SET netzone='{$_POST["netzone"]}' WHERE ID=$q->last_id");
	}
	
	if($_POST["ID"]>0){
		$shore->NIC_UPDATE("virt{$_POST["ID"]}");
	}else{
		$shore->NIC_UPDATE("virt{$q->last_id}");
	}
		
	if($_POST["ID"]>0){$ID=$_POST["ID"];}else{$ID=$q->last_id;}
	
	$sock=new sockets();
	$sock->getFrameWork("vde.php?port-reconfigure=$ID");
		
	
}


function main_switch(){
	$page=CurrentPageName();
	$t=time();
	$tpl=new templates();
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$organization=$tpl->_ENGINE_parse_body("{organization}");
	$context=$tpl->_ENGINE_parse_body("{context}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$empty=$tpl->_ENGINE_parse_body("{empty}");
	$empty_events_text_ask=$tpl->javascript_parse_text("{empty_events_text_ask}");
	$sock=new sockets();
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}
	$EnableipV6=$sock->GET_INFO("EnableipV6");
	if(!is_numeric($EnableipV6)){$EnableipV6=0;}
	
	$nic=$tpl->_ENGINE_parse_body("{nic}");
	$netmask=$tpl->_ENGINE_parse_body("{netmask}");
	$tcp_address=$tpl->_ENGINE_parse_body("{tcp_address}");
	$broadcast_has_ipaddr=$tpl->_ENGINE_parse_body("{broadcast_has_ipaddr}");
	$new_virtual_ip=$tpl->javascript_parse_text("{new_virtual_ip}");
	$add_default_www=$tpl->_ENGINE_parse_body("{add_default_www}");
	$delete_freeweb_text=$tpl->javascript_parse_text("{delete_freeweb_text}");
	$delete_freeweb_dnstext=$tpl->javascript_parse_text("{delete_freeweb_dnstext}");
	$NoGatewayForVirtualNetWork=$tpl->_ENGINE_parse_body("{NoGatewayForVirtualNetWork}");
	$apply_network_configuration=$tpl->_ENGINE_parse_body("{apply_network_configuration}");
	$help=$tpl->_ENGINE_parse_body("{help}");
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$apply_network_configuration_warn=$tpl->javascript_parse_text("{apply_network_configuration_warn}");
	$virtual_interfaces=$tpl->_ENGINE_parse_body("{virtual_interfaces}");
	$gateway=$tpl->javascript_parse_text("{gateway}");
	$delete_ipaddr_ask=$tpl->javascript_parse_text("{delete_ipaddr_ask}");
	$title=$tpl->_ENGINE_parse_body("{Ethernet_switch}");
	$switch_port=$tpl->javascript_parse_text("{switch_port}");
	$users=new usersMenus();
	$vde_switch_explain=$tpl->_ENGINE_parse_body("{vde_switch_explain}");
	$virtual_switch=$tpl->_ENGINE_parse_body("{virtual_switch}");
	$virtual_click_add_explain=$tpl->javascript_parse_text("{virtual_click_add_explain}");
	
	$mac=$tpl->_ENGINE_parse_body("{MAC}");
	$tablewidth=874;
	$servername_size=412;
	$switch=$_GET["eth"];
	

	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$new_virtual_ip$v4</strong>', bclass: 'add', onpress : VirtualIPAdd$t},
	{name: '<strong style=font-size:18px>$apply_network_configuration</strong>', bclass: 'Reconf', onpress : BuildNetConf$t},
	{name: '<strong style=font-size:18px>$virtual_switch</strong>', bclass: 'Settings', onpress : MainParams$t},
	
	],";
	$html="
	
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
	<script>
	VirtualIPMem$t='';
	function LoadTable$t(){
	$('#table-$t').flexigrid({
	url: '$page?ports-list=yes&switch=$switch&t=$t',
	dataType: 'json',
	colModel : [
	{display: '&nbsp;', name : 'icon', width : 72, sortable : false, align: 'center'},
	{display: '<span style=font-size:18px>$switch_port</span>', name : 'port', width :135, sortable : false, align: 'center'},
	{display: '<span style=font-size:18px>$nic</span>', name : 'ID', width :135, sortable : true, align: 'center'},
	{display: '<span style=font-size:18px>$tcp_address</span>', name : 'ipaddr', width :169, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$mac</span>', name : 'mac', width :189, sortable : false, align: 'left'},
	{display: '<span style=font-size:18px>$gateway</span>', name : 'gateway', width :135, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$netmask</span>', name : 'netmask', width : 169, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>VLAN</span>', name : 'vlan', width : 135, sortable : true, align: 'center'},
	{display: '&nbsp;', name : 'none3', width : 72, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'none2', width : 72, sortable : false, align: 'center'},
		
	],
	$buttons
	
	searchitems : [
	{display: '$tcp_address', name : 'ipaddr'},
	{display: 'NIC', name : 'ID'},
	],
	sortname: 'ID',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 32,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true
	
	});
	}
	
	function VirtualIPAdd$t(){
		alert('$virtual_click_add_explain');
	}
	
	function MainParams$t(){
		Loadjs('$page?MainParams-js=yes&t=$t&switch=$switch');
	}
	
	var X_VirtualIPAddSave$t=function (obj) {
	var results=obj.responseText;
	if(results.length>5){alert(results);return;}
	$('#rowvirt'+VirtualIPMem$t).remove();
	}
	
	function VirtualsDelete$t(ID){
	if(!confirm('$delete_ipaddr_ask')){return;}
	VirtualIPMem$t=ID;
	var XHR = new XHRConnection();
	XHR.appendData('vde-del',ID);
	XHR.sendAndLoad('$page', 'POST',X_VirtualIPAddSave$t);
	}
	
	
	
	function BuildNetConf$t(){
		Loadjs('virtualswitch.reconfigure.php?t=$t&switch=$switch');
	}
	
LoadTable$t();
</script>";
	
			echo $html;	
	
}



function main_switch_ports(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$database="artica_backup";
	$table="nics_switch";
	$t=$_GET["t"];
	$tTable=$_GET["t"];
	$search='%';
	$sock=new sockets();
	$switch=$_GET["switch"];
	$GLOBALS["interfaces"]=unserialize(base64_decode($sock->getFrameWork("cmd.php?TCP_NICS_STATUS_ARRAY=yes")));
	$STATUS=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/vde_status"));
	$sock->getFrameWork("network.php?vde-status=yes");
	
	$VirtualSwitchEnabled=$sock->GET_INFO("VirtualSwitchEnabled{$switch}");
	if(!is_numeric($VirtualSwitchEnabled)){$VirtualSwitchEnabled=1;}
	
	$page=1;
	$FORCE_FILTER=null;

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$mac_text=$tpl->javascript_parse_text("{mac}");

	$data = array();
	$data['page'] = $page;
	$data['total'] = 32;
	$data['rows'] = array();

	
	$net=new networking();

	for($port=1;$port<33;$port++){
		
	

		
	
		$explode_port=explode_port($port,$switch);
		$eth_text=$explode_port["ETH"];
		$color=$explode_port["COLOR"];
		$gateway=$explode_port["gateway"];
		$netmask=$explode_port["netmask"];
		$ipaddr=$explode_port["ipaddr"];
		$img=$explode_port["IMG"];
		$delete=$explode_port["delete"];
		$TCP_VLAN=$explode_port["TCP_VLAN"];
		
		if($VirtualSwitchEnabled==0){
			$img="port-on.png";
			$color="#B7B7B7";
			$explode_port["plug2tap_icon"]="port-off.png";
		}
		
		$edit="<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('$MyPage?port-js=yes&port=$port&switch=$switch&t={$_GET["t"]}')\"
		style='font-size:22px;font-weight:bold;color:$color;text-decoration:underline'>";
		$plug2tap_text=null;
		
		$plug2tap_icon=$explode_port["plug2tap_icon"];
		if($explode_port["plug2tap_text"]<>null){
			$plug2tap_text="<br><i style='font-size:18px;font-weight:normal;text-decoration:none'>".$tpl->_ENGINE_parse_body("{$explode_port["plug2tap_text"]}</i>");
		}

		$data['rows'][] = array(
		'id' => "virt{$ligne['ID']}",
			'cell' => array(
				"<center><img src='img/$img'></center>"
				,"<center style='font-size:22px;color:$color;font-weight:bold'>$edit{$port}</a></center>"
				,"<span style='font-size:22px;color:$color;font-weight:bold'>$edit$eth_text</a></span>",
				"<span style='font-size:22px'>{$edit}$ipaddr</a></span>$plug2tap_text$mac",
				"<span style='font-size:22px;color:$color;font-weight:bold'>$edit{$explode_port["MAC"]}</a></span>",
				"<span style='font-size:22px'>{$edit}$gateway</a></span>",
				"<span style='font-size:22px'>{$edit}$netmask</a></span>",
				"<span style='font-size:22px'>{$edit}$TCP_VLAN</a></span>",
				"<center><img src='img/$plug2tap_icon'></center>",
				"<center>$delete</center>"
				 )
		);
	}


echo json_encode($data);

}

function explode_port($portNum,$switch){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$img="port-grey.png";
	$color="#B7B7B7";
	$danger="port-off.png";
	$grey="port-grey.png";
	$t=$_GET["t"];
	$free=$tpl->_ENGINE_parse_body("{free}");
	$q=new mysql();
	$sock=new sockets();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM nics_switch WHERE port='$portNum' AND `nic`='$switch'",'artica_backup'));
	if($ligne["ID"]==0){
		return array(
				"ETH"=>"$free",
				"IMG"=>$img,
				"gateway"=>"-",
				"MAC"=>"-",
				"ipaddr"=>"-",
				"netmask"=>"-",
				"COLOR"=>$color,
				"delete"=>"-",
				"plug2tap_icon"=>$img,
				
				);
		
	}
	
	$ethname="virt{$ligne["ID"]}";
	$delete="<a href=\"javascript:blur();\"
	OnClick=\"javascript:Loadjs('$MyPage?delete-virtual-js={$ligne['ID']}&t=$t');\"
	style='font-size:14px;text-decoration:underline'><img src='img/delete-32.png'></a>";
	if($ligne["vlan"]>0){$TCP_VLAN="{$ligne["vlan"]}";}
	if(!isset($GLOBALS["interfaces"][$ethname])){
		$color="#B7B7B7";
		return array(
				"ETH"=>"virt{$ligne["ID"]}",
				"ipaddr"=>$ligne["ipaddr"],
				"MAC"=>"-",
				"gateway"=>$ligne["gateway"],
				"netmask"=>$ligne["netmask"],
				"IMG"=>"port-off.png",
				"plug2tap_icon"=>$img,
				"COLOR"=>$color,
				"delete"=>"$delete","TCP_VLAN"=>$TCP_VLAN
		);
		
		
	}
	
	
	$color="black";
	
	$status=unserialize(base64_decode($sock->getFrameWork("vde.php?plug2tap-status=$ethname")));
	$plug2tap_icon="port-on.png";
	if(!$status["RUNNING"]){
		$plug2tap_icon=$danger;
	}
	$plug2tap_text=$status["RUNNING_SINCE"];
	$MAC=$GLOBALS["interfaces"][$ethname]["MAC"];
	
	return array(
			"ETH"=>"virt{$ligne["ID"]}",
			"MAC"=>"$MAC",
			"ipaddr"=>$ligne["ipaddr"],
			"gateway"=>$ligne["gateway"],
			"netmask"=>$ligne["netmask"],
			"IMG"=>"port-on.png",
			"MAC"=>"$MAC",
			"plug2tap_icon"=>$plug2tap_icon,
			"plug2tap_text"=>$plug2tap_text,
			"COLOR"=>$color,
			"delete"=>"$delete","TCP_VLAN"=>$TCP_VLAN
	);	
	
}
function port_popup(){
	$ldap=new clladp();
	$sock=new sockets();
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$tSource=$_GET["t"];
	$ID=$_GET["ID"];
	$port=$_GET["port"];
	$switch=$_GET["switch"];
	$t=time();
	
	$nics=unserialize(base64_decode($sock->getFrameWork("cmd.php?list-nics=yes")));
	$GLOBALS["interfaces"]=unserialize(base64_decode($sock->getFrameWork("cmd.php?TCP_NICS_STATUS_ARRAY=yes")));
	
	
	
	$title_button="{add}";
	if(!is_numeric($_GET["ID"])){$_GET["ID"]=0;}

	if($ID>0){
		$sql="SELECT * FROM nics_switch WHERE ID='{$_GET["ID"]}'";
		$q=new mysql();
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$title_button="{apply}";
		$switch=$ligne["nic"];
		$port=$ligne["port"];
	}


	for($i=1;$i<33;$i++){
		$ports[$i]=$i;
	}
	$vlans[0]="{none}";
	for($i=1;$i<256;$i++){
		$vlans[$i]=$i;
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
	
	
	$MAIN_TITLE=$tpl->_ENGINE_parse_body("{switch_port} $port, {virtual_switch} $switch");

	$styleOfFields="font-size:22px;padding:3px";
	$vlan_field=Field_array_Hash($vlans,"vlan-$t",$ligne["vlan"],null,null,0,"font-size:22px;padding:3px");
	$html="
		<div id='animate-$t'></div>
		<div id='virtip'>". Field_hidden("ID","{$_GET["ID"]}").
		Field_hidden("port-$t","$port").Field_hidden("nic-$t","$switch")."
		<div style='width:98%' class=form>
			<table style='width:99%'>
			<tr>
				<td class=legend style='font-size:22px'>{netzone}:</td>
				<td>" . field_text("netzone-$t",$ligne["netzone"],"$styleOfFields;width:220px",false)."</td>
			</tr>
			<tr>
				<td class=legend style='font-size:22px'>VLAN:</td>
				<td>$vlan_field</td>
			</tr>						
			<tr>
				<td class=legend style='font-size:22px'>{tcp_address}:</td>
				<td>" . field_ipv4("ipaddr-$t",$ligne["ipaddr"],$styleOfFields,false,"CalcCdirVirt$t(0)")."</td>
			</tr>			
			<tr>
				<td class=legend style='font-size:22px'>{netmask}:</td>
				<td>" . field_ipv4("netmask-$t",$ligne["netmask"],$styleOfFields,false,"CalcCdirVirt$t(0)")."</td>
			</tr>
			<tr>
			<td class=legend style='font-size:22px'>CDIR:</td>
				<td style='padding:-1px;margin:-1px'>
					<table style='width:99%;padding:-1px;margin:-1px'>
					<tr>
					<td width=1%>
						" . Field_text("cdir-$t",$ligne["cdir"],"$styleOfFields;width:190px",null,null,null,false,null,$DISABLED)."</td>
					<td align='left'> ".imgtootltip("img_calc_icon.gif","cdir","CalcCdirVirt$t(1)") ."</td>
					</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td class=legend style='font-size:22px'>{gateway}:</td>
				<td>" . field_ipv4("gateway-$t",$ligne["gateway"],$styleOfFields,false)."</td>
			</tr>
			<tr>
				<td class=legend style='font-size:22px'>{metric}:</td>
				<td>" . field_text("metric-$t",$ligne["metric"],"$styleOfFields;width:90px",false)."</td>
			</tr>
	</table>
	</div>

	<div id='infosVirtual' style='font-size:22px'></div>
	<div style='text-align:right'><hr>". button($title_button,"Save$t()",30)."</div>
</div>
<script>
var Netid{$t}={$_GET["ID"]};
var cdir=document.getElementById('cdir-$t').value;
var netmask=document.getElementById('netmask-$t').value;
if(netmask.length>0){if(cdir.length==0){CalcCdirVirt$t(0);}}

var X_CalcCdirVirt$t= function (obj) {
	var results=obj.responseText;
	document.getElementById('cdir-$t').value=results;
}

var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#table-$tSource').flexReload();
	if( Netid{$t}==0 ){
		YahooWinHide();
	}
}

function CalcCdirVirt$t(recheck){
	var cdir=document.getElementById('cdir-$t').value;
	if(recheck==0){if(cdir.length>0){return;}}
	var XHR = new XHRConnection();
	
	XHR.setLockOff();
	XHR.appendData('cdir-ipaddr',document.getElementById('ipaddr-$t').value);
	XHR.appendData('netmask',document.getElementById('netmask-$t').value);
	XHR.sendAndLoad('artica.settings.php', 'GET',X_CalcCdirVirt$t);
}


function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('nic','$switch');
	XHR.appendData('port','$port');
	XHR.appendData('ID','{$_GET["ID"]}');
	XHR.appendData('netzone',document.getElementById('netzone-$t').value);
	XHR.appendData('ipaddr',document.getElementById('ipaddr-$t').value);
	XHR.appendData('netmask',document.getElementById('netmask-$t').value);
	XHR.appendData('cdir',document.getElementById('cdir-$t').value);
	XHR.appendData('metric',document.getElementById('metric-$t').value);
	XHR.appendData('gateway',document.getElementById('gateway-$t').value);
	XHR.appendData('vlan',document.getElementById('vlan-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
";
echo $tpl->_ENGINE_parse_body($html);
}

function switch_status(){
	$switch=$_GET["switch"];
	$tpl=new templates();
	$sock=new sockets();
	$datas=$sock->getFrameWork("vde.php?switch-main-status=$switch");
	$ini=new Bs_IniHandler();
	$ini->loadString($datas);
	$tpl=new templates();

	
	echo $tpl->_ENGINE_parse_body(DAEMON_STATUS_ROUND("VDE_$switch",$ini,null,0));
}
