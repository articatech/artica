<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.tcpip.inc');
	
	$user=new usersMenus();
	
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	

	
	if(isset($_GET["proxies-list"])){proxies_list();exit;}
	if(isset($_GET["add-proxy"])){proxies_add_popup();exit;}
	if(isset($_POST["ipsrc"])){proxies_add();exit;}
	if(isset($_POST["file-delete"])){windowsupdate_delete();exit;}
	if(isset($_POST["proxy-enable"])){proxies_enabled();exit;}
	popup();
	
	
	
function popup(){
	$tpl=new templates();
	$sock=new sockets();
	
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	
	$enabled=$tpl->_ENGINE_parse_body("{enable}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$new_proxy=$tpl->javascript_parse_text("{new_host}");
	$files=$tpl->_ENGINE_parse_body("{files}");
	$date=$tpl->javascript_parse_text("{date}");
	$apply_params=$tpl->_ENGINE_parse_body("{apply}");
	$title=$tpl->javascript_parse_text("{storage}");
	$remove=$tpl->javascript_parse_text("{remove}");
	$size=$tpl->javascript_parse_text("{size}");
	$local_forwarded_for=$tpl->javascript_parse_text("{local_forwarded_for}");
	$tt=$_GET["tt"];
	$t=time();		

	$html="
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
var tmp$t='';
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?proxies-list=yes&t=$t&ID={$_GET["ID"]}',
	dataType: 'json',
	colModel : [
		{display: '<span style=font-size:18px>$date</span>', name : 'zDate', width : 180, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$files</span>', name : 'localpath', width : 1022, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$size</span>', name : 'filesize', width : 110, sortable : true, align: 'right'},
		{display: '&nbsp;', name : 'delete', width : 60, sortable : true, align: 'center'},
		
	],
buttons : [
	
		],	
	searchitems : [
		{display: '$files', name : 'localpath'},
		
		],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '<strong style=font-size:30px>$title</strong>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 600,
	singleSelect: true
	
	});   
});	
	function SquidBuildNow$t(){
		Loadjs('squid.windowsupdate.whitelist.progress.php');
	}
	
function AddProxyChild(){
	YahooWin5('750','$page?add-proxy=yes&t=$t','$new_proxy');

}

function LocalForwardedFor(){
	Loadjs('squid.forwarded_for.php');
}

var xDeleteWindowUpdateFile$t= function (obj) {
	var results=obj.responseText;
	if(results.length>2){alert(results);return;}
	$('#table-$t').flexReload();
}		

function DeleteWindowUpdateFile(base,path){
	if(!confirm('$remove '+base+' ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('file-delete',path);
	XHR.sendAndLoad('$page', 'POST',xDeleteWindowUpdateFile$t);
	
}
	
var x_EnableDisableProxyClient$t= function (obj) {
	var results=obj.responseText;
	if(results.length>2){alert(results);return;}
	$('#table-$t').flexReload();
}		
	
function EnableDisableProxyClient(ID){
	var XHR = new XHRConnection();
	XHR.appendData('proxy-enable',ID);
	if(document.getElementById('ProxyClient_'+ID).checked){
		XHR.appendData('enable',1);
	}else{
		XHR.appendData('enable',0);
	}
	XHR.sendAndLoad('$page', 'POST',x_EnableDisableProxyClient$t);	
}
	
document.getElementById('WINDOWSUPDATEPACK').innerHTML='';

</script>
";
	
	echo $html;
	
	
}

function proxies_enabled(){
	$sql="UPDATE windowsupdates_white SET enabled={$_POST["enable"]} WHERE ID={$_POST["proxy-enable"]}";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}

}

function windowsupdate_delete(){
	
	$sock=new sockets();
	$path=urlencode($_POST["file-delete"]);
	$sock->getFrameWork("squid2.php?windows-update-delete=$path");
}

function proxies_list(){
//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	

	
	
	$t=$_GET["t"];
	
	$search='%';
	$table="windowsupdate";
	
	
	$FORCE_FILTER=null;
	$page=1;

	if($q->COUNT_ROWS($table,$database)==0){json_error_show("No rules....");}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	
	$searchstring=string_to_flexquery();

	if($searchstring<>null){
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
	if(mysql_num_rows($results)==0){json_error_show("No data....");}
	
	

	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$icon="42-server.png";
		$color="black";
		
		$size=FormatBytes($ligne["filesize"]/1024);
		$localpath=basename($ligne["localpath"]);
		$localpathEnc=urlencode($localpath);
		$uri=$ligne["zUri"];
		$date=$ligne["zDate"];
		
		$delete=imgsimple("delete-24.png",null,"DeleteWindowUpdateFile('$localpath','{$ligne["localpath"]}')");
		if($ligne["enabled"]==0){
			//$color="#8a8a8a";
			//$icon="42-server-grey.png";
		}
		
		
		
	$data['rows'][] = array(
		'id' => "TSC{$ligne['ID']}",
		'cell' => array(
				"<span style='font-size:18px;color:$color;margin-top:4px'>$date</span>",
				"<a href=\"$uri\" style='font-size:18px;color:$color;margin-top:4px;text-decoration:underline' target=_new>$localpath</a>",
				"<span style='font-size:18px;color:$color;margin-top:4px'>$size</span>",
		"<center style='margin-top:4px'>$delete</center>")
		);
	}
	
	
	echo json_encode($data);		
	
}

function proxies_add_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$t=time();	
	$tt=$_GET["t"];
	$html="
	<div id='$t' style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:26px'>{ipaddr}:</td>
		<td>". field_ipv4("ipsrc-$t", null,"font-size:26px",false,"ChildEventAddCK$t(event)")."</td>
	</tr>
	<tr>
		<td colspan=2 align=right><hr>". button("{add}","ChildEventAdd$t()","32px")."</td>
	</tr>
	</table>
	<script>
		var x_ChildEventAdd$t= function (obj) {
			$('#table-$tt').flexReload();
			var results=obj.responseText;
			if(results.length>3){alert(results);return;}
			
			YahooWin5Hide();
		}		

		function ChildEventAdd$t(){
			var XHR = new XHRConnection();
			XHR.appendData('ipsrc',document.getElementById('ipsrc-$t').value);
			XHR.sendAndLoad('$page', 'POST',x_ChildEventAdd$t);
		}
		
		function ChildEventAddCK$t(e){
			if(!checkEnter(e)){return;}
			ChildEventAdd$t();
		}
		
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function proxies_add(){
	$sql="INSERT IGNORE INTO windowsupdates_white (ipsrc,enabled) VALUES ('{$_POST["ipsrc"]}',1)";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}

}


