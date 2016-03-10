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

if(isset($_GET["route-add-js"])){ route_add_js();exit;}
if(isset($_GET["route-add-popup"])){ route_add_popup();exit;}
if(isset($_GET["search"])){ routes_list();exit;}
if(isset($_POST["ROUTE_SHOULD_BE"])){ROUTE_SHOULD_BE();exit;}
if(isset($_POST["ROUTE_FROM"])){routes_add();exit;}
if(isset($_POST["DELETE_ROUTE_FROM"])){routes_delete();exit;}
routes();


function route_add_js(){
	
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$new_route=$tpl->javascript_parse_text("{new_route}");
	echo "YahooWin2('700','$page?route-add-popup=yes&t={$_GET["t"]}','$new_route')";
}
function routes_add(){
	$vpn=new openvpn();
	$vpn->routes[$_POST["ROUTE_FROM"]]=$_POST["ROUTE_MASK"];
	$vpn->Save();

}
function routes_delete(){
	$vpn=new openvpn();
	unset($vpn->routes[$_POST["DELETE_ROUTE_FROM"]]);
	$vpn->Save();

}
function ROUTE_SHOULD_BE(){
		$ip=$_POST["ROUTE_SHOULD_BE"];
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

function route_add_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$html="
	<div class=explain style='font-size:18px'>{routes_explain}</div>
	<div style='font-size:98%' class=form>
	<table style='width:99%'>
	 <tr>
	 	<td class=legend style='font-size:32px'>{from_ip_address}:</td>
	 	<td>" . Field_text("ROUTE_FROM$t",null,'width:300px;font-size:32px;padding:3px',null,"RouteShouldbe$t()",null,false,"RouteShouldbe$t()")."</td>
	 </tr>
	<tr>
	 	<td class=legend style='font-size:32px'>{netmask}:</td>
	 	<td>" . Field_text("ROUTE_MASK$t",null,'width:300px;font-size:32px;padding:3px')."</td>
	 </tr>
	<tr>
	<td colspan=2 class='legend' style='padding-right:50px;font-size:32px'><span id='shouldbe$t'></span></td>
	<tr>
		<td colspan=2 align='right' ><hr>". button("{add}","OpenVpnAddRoute$t()",40)."</td>
	</tr>
</table>
				
<script>
var x_RouteShouldbe$t= function (obj) {
	var tempvalue=obj.responseText;
	document.getElementById('shouldbe$t').innerHTML=tempvalue;
}					
		
function RouteShouldbe$t(){
	var ROUTE_FROM=document.getElementById('ROUTE_FROM$t').value;
	var XHR = new XHRConnection();
	XHR.setLockOff();
	XHR.appendData('ROUTE_SHOULD_BE',ROUTE_FROM);
	XHR.sendAndLoad('$page', 'POST',x_RouteShouldbe$t);	
}		

var x_OpenVpnAddRoute$t= function (obj) {
	var tempvalue=obj.responseText;
	$('#flexRT{$_GET["t"]}').flexReload();	
	YahooWin2Hide();
}				
		
function OpenVpnAddRoute$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ROUTE_FROM',document.getElementById('ROUTE_FROM$t').value);
	XHR.appendData('ROUTE_MASK',document.getElementById('ROUTE_MASK$t').value);
	XHR.sendAndLoad('$page', 'POST',x_OpenVpnAddRoute$t);
}
</script>	
";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
}



function routes(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$nic=$tpl->javascript_parse_text("{nic}");
	$pattern=$tpl->_ENGINE_parse_body("{pattern}");
	$netmask=$tpl->javascript_parse_text("{netmask}");
	$t=time();
	$tablesize=868;
	$descriptionsize=705;
	$bts=array();
	$add=$tpl->_ENGINE_parse_body("{new_route}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$additional_routes=$tpl->javascript_parse_text("{additional_routes}");
	$reconstruct_vlans=$tpl->javascript_parse_text("{OPENVPN_APPLY_CONFIG}");
	$bts[]="{name: '<strong style=font-size:18px>$add</strong>', bclass: 'add', onpress : RouteAdd$t},";
	$bts[]="{name: '<strong style=font-size:18px>$reconstruct_vlans</strong>', bclass: 'Reload', onpress : Build$t},";
	
	
	
	
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		

	if(count($bts)>0){
		$buttons="buttons : [".@implode("\n", $bts)." ],";
	}
	
	$html="
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
$(document).ready(function(){
	$('#flexRT$t').flexigrid({
		url: '$page?search=yes&t=$t',
		dataType: 'json',
		colModel : [
			{display: '<span style=font-size:18px>$ipaddr</span>', name : 'org', width : 300, sortable : true, align: 'left'},
			{display: '<span style=font-size:18px>$netmask</span>', name : 'org0', width : 300, sortable : true, align: 'left'},
			{display: '&nbsp;', name : 'none2', width : 70, sortable : false, align: 'center'},
		],$buttons
		searchitems : [
			{display: '$pattern', name : 'org'},
			],
		sortname: 'ID',
		sortorder: 'desc',
		usepager: true,
		title: '<span style=font-size:30px>$additional_routes</span>',
		useRp: true,
		rp: 25,
		showTableToggleBtn: false,
		width: '99%',
		height: 400,
		singleSelect: true
		
		});   
});

function RouteAdd$t(){
	Loadjs('$page?route-add-js&t=$t');
}

function Build$t(){
	Loadjs('index.openvpn.enable.progress.php');
}

var xOpenVPNRoutesDelete$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#flexRT$t').flexReload();		
}	
	

		
function OpenVPNRoutesDelete(index){
	var XHR = new XHRConnection();
	XHR.appendData('DELETE_ROUTE_FROM',index);
	XHR.sendAndLoad('$page', 'POST',xOpenVPNRoutesDelete$t);
}
</script>

";
	
echo $tpl->_ENGINE_parse_body($html);	
}

function routes_list(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql();
	$table="nics_vlan";
	$database="artica_backup";
	$search='%';
	$page=1;

	$sock=new sockets();
	$net=new networking();
	$ip=new IP();
	$interfaces=unserialize(base64_decode($sock->getFrameWork("cmd.php?ifconfig-interfaces=yes")));
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}

	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}

	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	
	$vpn=new openvpn();
	if(!is_array($vpn->routes)){json_error_show("no data");}
	reset($vpn->routes);
	$total=count($vpn->routes);

	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";



	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	

	$c=0;
	while (list ($num, $ligne) = each ($vpn->routes) ){

		
		$c++;
		$delete=imgsimple("delete-42.png",null,"OpenVPNRoutesDelete('$num')");

		$data['rows'][] = array(
				'id' => md5("$num$ligne"),
				'cell' => array(
					
						"<span style='font-size:22px;font-weight:normal'>{$num}</div>",
						"<span style='font-size:22px;font-weight:normal'>{$ligne}</div>",
						"<center>$delete</center>"
				)
		);
	}

	$data['total'] = $c;
	if($c==0){json_error_show("no data");}
	echo json_encode($data);
}
