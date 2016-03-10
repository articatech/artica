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


if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["balancer-backends-list"])){backends_list();exit;}

js();


function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$gpid=$_GET["gpid"];
	$servicename=$_GET["servicename"];
	$CallBack=$_GET["CallBack"];
	$title=$tpl->javascript_parse_text("{browse}: {backends}: $servicename");
	echo "YahooWin6('650','$page?popup=yes&gpid=$gpid&servicename=$servicename&CallBack=$CallBack','$title');";
	
	
	}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$q->BuildTables();
	$servicename=$tpl->_ENGINE_parse_body("{servicename}");
	$interface=$tpl->_ENGINE_parse_body("{interface}");
	$backends=$tpl->_ENGINE_parse_body("{backends}");
	$enabled=$tpl->_ENGINE_parse_body("{enable}");
	$weight=$tpl->_ENGINE_parse_body("{weight}");
	$select=$tpl->_ENGINE_parse_body("{select}");
	$delete_rule_ask=$tpl->javascript_parse_text("{delete_rule_ask}");
	$new_backend=$tpl->_ENGINE_parse_body("{new_backend}");
	$delete_service=$tpl->javascript_parse_text("{delete_this_service}");
	$delete_backend=$tpl->javascript_parse_text("{delete_backend}");
	$CallBack=$_GET["CallBack"];
	$servicename=$_GET["servicename"];
	$tt=$_GET["tt"];
	$t=time();

	$html="
	<table class='HAPROXY_BACKENDS_BROWSE' style='display: none' id='HAPROXY_BACKENDS_BROWSE' style='width:99%'></table>
	<script>
	var tmp$t='';
	$(document).ready(function(){
	$('#HAPROXY_BACKENDS_BROWSE').flexigrid({
	url: '$page?balancer-backends-list=yes&t=$t&servicename=$servicename',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:16px>$backends</span>', name : 'backendname', width : 192, sortable : true, align: 'left'},
	{display: '<span style=font-size:16px>$interface</span>', name : 'listen_ip', width : 172, sortable : false, align: 'left'},
	{display: '<span style=font-size:16px>$weight</span>', name : 'bweight', width : 52, sortable : true, align: 'center'},
	{display: '<span style=font-size:16px>$select</span>', name : 'delete', width : 119, sortable : false, align: 'center'},

	],
	searchitems : [
	{display: '$backends', name : 'backendname'},
	{display: '$interface', name : 'listen_ip'},
	],
	sortname: 'bweight',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:22px>$servicename</span>',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 350,
	singleSelect: true

});
});
function BackendCallBack(ID) {
	YahooWin6Hide();
	$CallBack(ID);
	

}




var x_DeleteSquidAclGroup= function (obj) {
var res=obj.responseText;
if(res.length>3){alert(res);return;}
if(document.getElementById('main_filter_rule_edit')){RefreshTab('main_filter_rule_edit');}
if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
$('#rowtime'+TimeRuleIDTemp).remove();
}



var x_EnableDisableBackendSilent= function (obj) {
var res=obj.responseText;
if(res.length>3){alert(res);return;}

}

var x_BackendDelete= function (obj) {
var res=obj.responseText;
if(res.length>3){alert(res);return;}
$('#rowTF'+tmp$t).remove();
$('#HAPROXY_BACKENDS_TABLE').flexReload();
$('#MAIN_HAPROXY_BALANCERS_TABLE').flexReload();
}


function BackendDelete(backendname,servicename,md){
tmp$t=md;
if(confirm('$delete_backend :$servicename/'+backendname+' ?')){
var XHR = new XHRConnection();
XHR.appendData('backends-delete', backendname);
XHR.appendData('servicename', '$servicename');
XHR.sendAndLoad('$page', 'POST',x_BackendDelete);
}
}



function EnableDisableBackend(backendname){
var XHR = new XHRConnection();
XHR.appendData('backends-enable', backendname);
XHR.appendData('servicename', '$servicename');
if(document.getElementById('HaProxBckDisable_'+backendname).checked){XHR.appendData('enable', '1');}else{XHR.appendData('enable', '0');}
XHR.sendAndLoad('$page', 'POST',x_EnableDisableBackendSilent);
}




</script>";
echo $html;
}
function backends_list(){
//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$database="artica_backup";
	$RULEID=$_GET["RULEID"];

	$search='%';
	$table="haproxy_backends";
	$FORCE_FILTER=" AND servicename='{$_GET["servicename"]}'";
	$page=1;

	if($q->COUNT_ROWS($table,$database)==0){json_error_show("No rules....");}

	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
				$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}

	if (isset($_POST['page'])) {$page = $_POST['page'];}


	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
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
						if(!$q->ok){json_error_show("$q->mysql_error");}


						$data = array();
						$data['page'] = $page;
						$data['total'] = $total;
						$data['rows'] = array();
						if(mysql_num_rows($results)==0){json_error_show("No rules....");}



						while ($ligne = mysql_fetch_assoc($results)) {
							$val=0;
							$color="black";
							$select_img="arrow-right-24.png";
							$md5=md5($ligne['servicename'].$ligne['backendname']);
							
							if($ligne["enabled"]==0){$color="#8a8a8a";
							$select_img="arrow-right-24-grey.png";
							}
							
							$select=imgsimple($select_img,null,"BackendCallBack('{$ligne['ID']}')");
							$listen_ip=$ligne["listen_ip"];
							$listen_port=$ligne["listen_port"];
							$interface="$listen_ip:$listen_port";




							$data['rows'][] = array(
									'id' => "TF$md5",
									'cell' => array(
									"<span style='font-size:18px;color:$color'>{$ligne['backendname']}</span>",
									"<span style='font-size:18px;color:$color'>$interface</span>",
									"<span style='font-size:18px;color:$color'>{$ligne['bweight']}</span>",
									"<center>$select</center>")
							);
						}


						echo json_encode($data);
}