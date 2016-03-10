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


if(isset($_GET["search"])){search();exit;}
if(isset($_GET["download"])){download();exit;}
if(isset($_GET["delete-uid-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete();exit;}
page();

function delete_js(){
	header("content-type: application/x-javascript");
	$q=new mysql_meta();
	$tpl=new templates();
	$ID=$_GET["delete-uid-js"];


	
	$text=$tpl->javascript_parse_text("{delete} $ID ?");

	$page=CurrentPageName();
	$t=time();
	$html="
var xcall$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#OPENVPN_CLIENT_SCRIPTS').flexReload();
}

function xFunct$t(){
	if(!confirm('$text')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete','$ID');
	XHR.sendAndLoad('$page', 'POST',xcall$t);
}

xFunct$t();
";
	echo $html;

}

function delete(){
	$uid=$_POST["delete"];
	$q=new mysql();
	$sql="DELETE FROM openvpn_clients WHERE uid='$uid'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!$q->ok){echo $q->mysql_error;}
	
}

function page(){
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
	$add=$tpl->_ENGINE_parse_body("{BUILD_OPENVPN_CLIENT_CONFIG}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$ComputerOS=$tpl->javascript_parse_text("{ComputerOS}");
	$userid=$tpl->javascript_parse_text("{connections}");
	$download=$tpl->javascript_parse_text("{download2}");
	$run=$tpl->javascript_parse_text("{run}");
	$bts[]="{name: '<strong style=font-size:18px>$add</strong>', bclass: 'add', onpress : RouteAdd$t},";
	$title=$tpl->_ENGINE_parse_body("{clients_scripts}");

	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}

	if(count($bts)>0){
		$buttons="buttons : [".@implode("\n", $bts)." ],";
	}

	$html="
	<table class='OPENVPN_CLIENT_SCRIPTS' style='display: none' id='OPENVPN_CLIENT_SCRIPTS' style='width:99%'></table>
	<script>
	$(document).ready(function(){
	$('#OPENVPN_CLIENT_SCRIPTS').flexigrid({
	url: '$page?search=yes&t=$t',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:18px>$userid</span>', name : 'uid', width : 300, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$ComputerOS</span>', name : 'ComputerOS', width : 240, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$run</span>', name : 'run', width : 90, sortable : false, align: 'left'},
	{display: '&nbsp;', name : 'none2', width : 70, sortable : false, align: 'center'},
	],$buttons
	searchitems : [
	{display: '$userid', name : 'userid'},
	],
	sortname: 'uid',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rp: 25,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true

});
});

function RouteAdd$t(){
	Loadjs('index.openvpn.build.client.php');
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

function search(){
	//1.4.010916
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	
	$search='%';
	$table="openvpn_clients";
	$database="artica_backup";
	$page=1;
	$FORCE_FILTER="";
	
	if(!$q->TABLE_EXISTS("openvpn_clients", $database)){$q->BuildTables();}
	

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
	
	if(mysql_num_rows($results)==0){json_error_show("no data",1);}
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$zipsize_text=null;
		$zmd5=md5(serialize($ligne));
		$zipsize=intval($ligne["zipsize"]);
		$delete=imgsimple("delete-42.png",null,"Loadjs('$MyPage?delete-uid-js={$ligne["uid"]}')");
		
		
		if($zipsize>0){$zipsize_text=" (".FormatBytes($zipsize/1024).")";}
		$run=imgsimple("32-run.png",null,"Loadjs('index.openvpn.client.progress.php?uid={$ligne["uid"]}')");
	
		$jsEdit="$MyPage?download=yes&uid={$ligne["uid"]}&t=$t'";
		$urljs="<a href=\"$jsEdit;\" style='font-size:20px;color:$color;text-decoration:underline;font-weight:bold'>";
		if($zipsize==0){$urljs=null;}
	
	$data['rows'][] = array(
		'id' => "$zmd5",
		'cell' => array(
			"<span style='font-size:20px;color:$color'>$urljs{$ligne["uid"]}$zipsize_text</span>",
			"<span style='font-size:20px;color:$color'>$urljs{$ligne["ComputerOS"]}</a></span>",
			"<span style='font-size:20px;color:$color'><center>$run</center></a></span>",
			"<span style='font-size:20px;color:$color'><center>$delete</center></a></span>",
			)
		);
	}
	
	
echo json_encode($data);	
	

}

function download(){
	$uid=$_GET["uid"];
	header("Content-Type:  application/zip");
	header("Content-Disposition: attachment; filename=$uid.zip");
	header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
	header("Pragma: no-cache"); // HTTP 1.0
	header("Expires: 0"); // Proxies
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
	
	$q=new mysql();
	
	$sql="SELECT zipcontent,zipsize FROM openvpn_clients WHERE uid='$uid'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!$q->ok){exit;}
	header("Content-Length: ".$ligne["zipsize"]);
	ob_clean();
	flush();
	echo $ligne["zipcontent"];
}
