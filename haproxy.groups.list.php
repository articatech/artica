<?php
if(posix_getuid()==0){die();}
session_start();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.haproxy.inc');




$user=new usersMenus();
if($user->AsDansGuardianAdministrator==false){
	$tpl=new templates();
	echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
	die();exit();
}
if(isset($_GET["list"])){backends_list();exit;}
if(isset($_POST["backends-add"])){backend_add();exit;}

table();

function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$interface=$tpl->_ENGINE_parse_body("{interface}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$link_server=$tpl->_ENGINE_parse_body("{link_server}");
	$backends=$tpl->_ENGINE_parse_body("{backends}");
	$delete=$tpl->_ENGINE_parse_body("{unlink}");
	$delete_rule_ask=$tpl->javascript_parse_text("{delete_rule_ask}");
	$new_group=$tpl->_ENGINE_parse_body("{new_group}");
	$delete_service=$tpl->javascript_parse_text("{delete_this_service}");
	$delete_backend=$tpl->javascript_parse_text("{delete_backend}");
	$title=$tpl->javascript_parse_text("{resources_groups}");
	$servicename=$_GET["servicename"];
	$ID=$_GET["ID"];
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT servicename FROM haproxy_backends_groups WHERE ID='$ID'","artica_backup"));
	$servicename=$ligne["servicename"];
	
	$t=time();
	
	$html="
	<table class='HAPROXY_GROUPS_BACKENDS_TABLE' style='display: none' id='HAPROXY_GROUPS_BACKENDS_TABLE' style='width:99%'></table>
	<script>
	var tmp$t='';
	$(document).ready(function(){
	$('#HAPROXY_GROUPS_BACKENDS_TABLE').flexigrid({
	url: '$page?list=yes&ID=$ID',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:18px>$backends</span>', name : 'backendname', width : 635, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$delete</span>', name : 'delete', width : 119, sortable : false, align: 'center'},
	
	],
	buttons : [
	{name: '<strong style=font-size:18px>$link_server</strong>', bclass: 'add', onpress : HaBackendLink},
	
	],
	searchitems : [
	{display: '$backends', name : 'backendname'},
	
	],
	sortname: 'bweight',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:30px>$backends</span>',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 350,
	singleSelect: true
	
	});
	});
function HaBackendLink() {
	Loadjs('haproxy.backends.browse.php?gpid=$ID&servicename=$servicename&CallBack=Link$t');
}
var xLink$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#HAPROXY_BACKENDS_TABLE').flexReload();
	$('#MAIN_HAPROXY_BALANCERS_TABLE').flexReload();
	$('#HAPROXY_GROUPS_BACKENDS_TABLE').flexReload();
	
}	
	
function Link$t(ID){
	var XHR = new XHRConnection();
	XHR.appendData('backends-add', ID);
	XHR.appendData('ID', '$ID');
	XHR.sendAndLoad('$page', 'POST',xLink$t);	
}
	
function UnlinkGroupBackend(ID){
	var XHR = new XHRConnection();
	XHR.appendData('backends-remove', ID);
	XHR.appendData('ID', '$ID');
	XHR.sendAndLoad('$page', 'POST',xLink$t);
}
</script>";
echo $html;
}

function backend_add(){
	$backend_id=$_POST["backends-add"];
	$gpid=$_POST["ID"];
	$q=new mysql();
	$q->QUERY_SQL("INSERT IGNORE INTO haproxy_backends_link (gpid,backendid) VALUES ('$gpid','$backend_id')","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("services.php?reload-haproxy=yes");
}

function backends_list(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$database="artica_backup";
	$ID=$_GET["ID"];

	$search='%';
	$table="(SELECT haproxy_backends_link.ID as tid, 
	haproxy_backends.* FROM haproxy_backends_link,haproxy_backends WHERE 
	haproxy_backends.ID=haproxy_backends_link.backendid AND
	haproxy_backends_link.gpid='$ID' ORDER BY bweight) as t";
	
	$page=1;
	if(!$q->TABLE_EXISTS("haproxy_backends_link", $database)){$q->BuildTables();}
	if($q->COUNT_ROWS("haproxy_backends_link",$database)==0){json_error_show("No rules....");}

	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}

	if (isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT *  FROM $table WHERE 1 $searchstring $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show("$q->mysql_error");}


	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("No rules....");}



	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$color="black";
		$disable=Field_checkbox("HaProxBckDisable_{$ligne['backendname']}", 1,$ligne["enabled"],"EnableDisableBackend('{$ligne['backendname']}')");
		$md5=md5($ligne['servicename'].$ligne['backendname']);
		$delete=imgsimple("delete-32.png",null,"BackendDelete('{$ligne['backendname']}','{$_GET['servicename']}','$md5')");
		if($ligne["enabled"]==0){$color="#8a8a8a";}
		$listen_ip=$ligne["listen_ip"];
		$listen_port=$ligne["listen_port"];
		$interface="$listen_ip:$listen_port";




		$data['rows'][] = array(
				'id' => "TF$md5",
				'cell' => array("<span style='font-size:22px;color:$color'>[{$ligne['bweight']}]</span>&nbsp;
				<a href=\"javascript:blur();\"  OnClick=\"javascript:Loadjs('$MyPage?backend-js=yes&backendname={$ligne['backendname']}&servicename={$_GET["servicename"]}&t={$_GET["t"]}');\"
				style='font-size:22px;text-decoration:underline;color:$color'>{$ligne['backendname']}</a>&nbsp;<span style='font-size:22px;color:$color'>$interface</span>",
				"<center style='font-size:22px;color:$color'>$delete</span>",
				)
		);
	}


	echo json_encode($data);
}