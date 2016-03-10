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
	include_once('ressources/class.tcpip.inc');
	$usersmenus=new usersMenus();
	if($usersmenus->AsSystemAdministrator==false){exit();}
	if(isset($_GET["virtual-list"])){nics_list();exit;}
	
	page();
	
function page(){
	$page=CurrentPageName();
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
	$new_virtual_ip=$tpl->_ENGINE_parse_body("{new_virtual_ip}");
	$add_default_www=$tpl->_ENGINE_parse_body("{add_default_www}");
	$delete_freeweb_text=$tpl->javascript_parse_text("{delete_freeweb_text}");
	$delete_freeweb_dnstext=$tpl->javascript_parse_text("{delete_freeweb_dnstext}");	
	$NoGatewayForVirtualNetWork=$tpl->_ENGINE_parse_body("{NoGatewayForVirtualNetWork}");
	$apply_network_configuration=$tpl->_ENGINE_parse_body("{apply_network_configuration}");
	$help=$tpl->_ENGINE_parse_body("{help}");
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$apply_network_configuration_warn=$tpl->javascript_parse_text("{apply_network_configuration_warn}");
	$virtual_interfaces=$tpl->_ENGINE_parse_body("{virtual_interfaces}");
	$reboot_network=$tpl->javascript_parse_text("{reboot_network}");
	$users=new usersMenus();
	$title=$tpl->javascript_parse_text("{virtual_interfaces}");

	$tablewidth=874;
	$servername_size=412;
	
	$t=time();
	
	if($EnableipV6==1){
		$v4=" (v4)";
		$new_virtual_ipv6="{name: '<b>$new_virtual_ip (v6)</b>', bclass: 'add', onpress : VirtualIPAddv6$t},";
	}
	
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$new_virtual_ip$v4</strong>', bclass: 'add', onpress : VirtualIPAdd$t},$new_virtual_ipv6
	{name: '<strong style=font-size:18px>$apply_network_configuration</strong>', bclass: 'Reconf', onpress : BuildNetConf$t},

	],";
	$html="
	<table class='SYSTEM_NICS_VIRTUALS_LIST' style='display: none' id='SYSTEM_NICS_VIRTUALS_LIST' style='width:100%'></table>
		<table class=form>
		<tr>
			<td class=legend>$broadcast_has_ipaddr</td>
			<td>". Field_checkbox("NetWorkBroadCastAsIpAddr",1,$sock->GET_INFO("NetWorkBroadCastAsIpAddr"),"NetWorkBroadCastAsIpAddrSave()")."</td>
		</tr>
		<tr>
			<td class=legend>$NoGatewayForVirtualNetWork</td>
			<td>". Field_checkbox("NoGatewayForVirtualNetWork",1,$sock->GET_INFO("NoGatewayForVirtualNetWork"),"NetWorkBroadCastAsIpAddrSave()")."</td>
		</tr>		
		</table>	
<script>
VirtualIPMem='';
$(document).ready(function(){
$('#SYSTEM_NICS_VIRTUALS_LIST').flexigrid({
	url: '$page?virtual-list=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'icon', width : 60, sortable : false, align: 'center'},
		{display: '<span style=font-size:22px>$nic</span>', name : 'ID', width :230, sortable : true, align: 'left'},
		{display: '<span style=font-size:22px>$tcp_address</span>', name : 'ipaddr', width :230, sortable : true, align: 'left'},
		{display: '<span style=font-size:22px>$netmask</span>', name : 'netmask', width : 230, sortable : true, align: 'left'},
		{display: '<span style=font-size:22px>$organization</span>', name : 'org', width : 313, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'none2', width : 60, sortable : false, align: 'center'},
		
	],
	$buttons

	searchitems : [
		{display: '$tcp_address', name : 'ipaddr'},
		{display: '$organization', name : 'org'},
		],
	sortname: 'nic',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true
	
	});   
});

function VirtualIPAdd$t(){
	YahooWin2(725,'system.nic.config.php?virtual-popup-add=yes&default-datas={$_GET["default-datas"]}&t=$t&function-after={$_GET["function-after"]}','$virtual_interfaces');
}

		
function VirtualIPAddv6$t(){
	YahooWin2(725,'system.nic.config.php?virtual-popup-addv6=yes&default-datas={$_GET["default-datas"]}&t=$t&function-after={$_GET["function-after"]}','$virtual_interfaces ipV6');
}
		
function VirtualsEdit$t(ID){
	YahooWin2(725,'system.nic.config.php?virtual-popup-add=yes&t=$t&ID='+ID,'$virtual_interfaces');
}
		
function VirtualsEdit6$t(ID){
	YahooWin2(725,'system.nic.config.php?virtual-popup-addv6=yes&t=$t&ID='+ID,'$virtual_interfaces');
}		

var X_VirtualIPAddSave$t=function (obj) {
	var results=obj.responseText;
	if(results.length>5){alert(results);return;}	
	$('#rowVirtualNic'+VirtualIPMem).remove();
}		

function VirtualsDelete$t(ID){
	var DisableNetworksManagement=$DisableNetworksManagement;
	if(DisableNetworksManagement==1){alert('$ERROR_NO_PRIVS');return;}		
	VirtualIPMem=ID;
	var XHR = new XHRConnection();
	XHR.appendData('virt-del',ID);
	XHR.sendAndLoad('system.nic.config.php', 'GET',X_VirtualIPAddSave$t);
}

		
function FreeWebDelete(server,dns,md){
	FreeWebIDMEM=md;
	if(confirm('$delete_freeweb_text')){
		var XHR = new XHRConnection();
		if(dns==1){if(confirm('$delete_freeweb_dnstext')){XHR.appendData('delete-dns',1);}else{XHR.appendData('delete-dns',0);}}
		XHR.appendData('delete-servername',server);
    	XHR.sendAndLoad('freeweb.php', 'GET',x_FreeWebDelete);
	}
}	
		
var X_BuildNetConf$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#SYSTEM_NICS_VIRTUALS_LIST').flexReload();
}		

		function BuildNetConf$t(){
			
			Loadjs('network.restart.php?t=$t');	
		
		}
		
		var X_NetWorkBroadCastAsIpAddrSave= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			
		}
		
		function NetWorkBroadCastAsIpAddrSave(){
			var DisableNetworksManagement=$DisableNetworksManagement;
			if(DisableNetworksManagement==1){alert('$ERROR_NO_PRIVS');return;}
			var XHR = new XHRConnection();
			if(document.getElementById('NetWorkBroadCastAsIpAddr').checked){
			XHR.appendData('NetWorkBroadCastAsIpAddr',1);}else{XHR.appendData('NetWorkBroadCastAsIpAddr',0);}
			
			if(document.getElementById('NoGatewayForVirtualNetWork').checked){
			XHR.appendData('NoGatewayForVirtualNetWork',1);}else{XHR.appendData('NoGatewayForVirtualNetWork',0);}
			XHR.sendAndLoad('system.nic.config.php', 'GET',X_NetWorkBroadCastAsIpAddrSave);
		}		
	
</script>";
	
	echo $html;	
		
	
	
	
}	

function nics_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$database="artica_backup";
	$table="nics_virtuals";
	$t=$_GET["t"];
	$search='%';
	$sock=new sockets();
	$interfaces=unserialize(base64_decode($sock->getFrameWork("cmd.php?ifconfig-interfaces=yes")));
	$page=1;
	$FORCE_FILTER=null;
	
	if($q->COUNT_ROWS($table,$database)==0){
		json_error_show("No interface");
		return ;
	}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(mysql_num_rows($results)==0){json_error_show('no data');}
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);return;}	
	
	$net=new networking();

	while ($ligne = mysql_fetch_assoc($results)) {
		$id=md5($ligne["ID"]);
			
		
		$eth="{$ligne["nic"]}:{$ligne["ID"]}";
		$eth_text="{$ligne["nic"]}:{$ligne["ID"]}";
		if($ligne["cdir"]==null){
			$ligne["cdir"]=$net->array_TCP[$ligne["nic"]];
			$eth=$ligne["nic"];
		}
		$img="port-off.png";
		$color="#B7B7B7";
		
		if($interfaces[$eth_text]<>null){
			$img="port-on.png";
			$color="black";
		}
		
		if($ligne["ipv6"]==1){
			$img="port-on.png";
			$color="black";			
		}
		
		$ligne["org"]=str_replace("LXC-INTERFACES","{APP_LXC}",$ligne["org"]);
		
		if(trim($ligne["org"])==null){
			$ligne["org"]=$tpl->_ENGINE_parse_body("<span style='color:#A30000'>{no_organization}</strong>");
		}
		
		if($ligne["org"]=="crossroads"){
			$ligne["org"]=$tpl->_ENGINE_parse_body("<a href=\"javascript:blur();\" 
			OnClick=\"javascript:Loadjs('postfix.multiple.crossroads.php?ipaddr=". urlencode($ligne["ipaddr"])."');\" 
			style='font-size:24px;text-decoration:underline;font-weight:bold'>{load_balancer}</a>");
			$img="folder-dispatch-22-grey.png";
			if($interfaces[$eth]<>null){$img="folder-dispatch-22.png";}
			
		}
		
	
		
		$edit="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:VirtualsEdit$t({$ligne["ID"]})\" style='font-size:24px;font-weight:bold;color:$color;text-decoration:underline'>";
		$delete="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:VirtualsDelete$t({$ligne["ID"]})\" 
		style='font-size:24px;text-decoration:underline'><img src='img/delete-32.png'></a>";
		
		if($ligne["ipv6"]==1){
			$edit="<a href=\"javascript:blur();\" 
			OnClick=\"javascript:VirtualsEdit6$t({$ligne["ID"]})\" 
			style='font-size:24px;font-weight:bold;color:$color;text-decoration:underline'>";
			$ligne["netmask"]="/{$ligne["netmask"]}";
		}
		
		
	$data['rows'][] = array(
		'id' => "VirtualNic{$ligne['ID']}",
		'cell' => array(
		"<center><img src='img/$img'></center>"
		,"<span style='font-size:24px;color:$color;font-weight:bold'>$eth_text</span>",
		"<span style='font-size:24px'>$edit{$ligne["ipaddr"]}</a></span>",
		"<span style='font-size:24px'>$edit{$ligne["netmask"]}</a></span>",
		"<span style='font-size:24px;color:$color'>{$ligne["org"]}</span>",
		"<center>$delete</center>"
		)
		);
	}
	
	
echo json_encode($data);		

}

	
