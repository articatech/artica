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
	if(isset($_POST["authip-add"])){proxies_add();exit;}
	if(isset($_POST["proxy-delete"])){proxies_delete();exit;}
	if(isset($_POST["proxy-enable"])){proxies_enabled();exit;}
	popup();
	
	
	
function popup(){
	$tpl=new templates();
	$sock=new sockets();
	
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	
	
	$sql="CREATE TABLE IF NOT EXISTS `nginx_exchecp` (
			`ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`hostname` VARCHAR( 255 ) NOT NULL ,
			`ipsrc` VARCHAR( 255 ) NOT NULL ,
			`enabled` INT( 1 ) NOT NULL DEFAULT '1',
			INDEX ( `enabled`,`ipsrc`,`hostname` )) ENGINE=MYISAM;";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html();}
	
	$enabled=$tpl->_ENGINE_parse_body("{enable}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$new_proxy=$tpl->javascript_parse_text("{new_host}");
	$files=$tpl->_ENGINE_parse_body("{files}");
	$hosts=$tpl->javascript_parse_text("{hosts}");
	$apply_params=$tpl->_ENGINE_parse_body("{apply}");
	$title=$tpl->javascript_parse_text("ECP:{clients_restrictions}");
	$remove=$tpl->javascript_parse_text("{delete} {host}");
	$new_host=$tpl->javascript_parse_text("{new_host}");
	$apache_auth_ip_explain=$tpl->javascript_parse_text("{apache_auth_ip_explain}");
	$tt=$_GET["tt"];
	$servernameenc=urlencode($_GET["servername"]);
	$t=time();		

	$html="
	<table class='NGINX_EXCHECP_TABLE' style='display: none' id='NGINX_EXCHECP_TABLE' style='width:99%'></table>
<script>
var tmp$t='';
$(document).ready(function(){
$('#NGINX_EXCHECP_TABLE').flexigrid({
	url: '$page?proxies-list=yes&t=$t&servername=$servernameenc',
	dataType: 'json',
	colModel : [
		{display: '<span style=font-size:18px>$hosts</span>', name : 'ipsrc', width : 936, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 60, sortable : true, align: 'center'},
		
	],
buttons : [
{name: '<strong style=font-size:22px>$new_host</strong>', bclass: 'Add', onpress : AuthIpAdd$t},	
{name: '<strong style=font-size:22px >$apply_params</strong>', bclass: 'apply', onpress : Apply$t},
		],	
	searchitems : [
		{display: '$hosts', name : 'zDate'},
		
		
		],
	sortname: 'ipsrc',
	sortorder: 'asc',
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
	var x_EnableDisableProxyClient$t= function (obj) {
			var results=obj.responseText;
			if(results.length>2){alert(results);return;}
			$('#NGINX_EXCHECP_TABLE').flexReload();
		}	

function AuthIpAdd$t(){
	var ip=prompt('$apache_auth_ip_explain');
	if(ip){
		var XHR = new XHRConnection();
		XHR.appendData('authip-add',ip);
		XHR.appendData('servername','{$_GET["servername"]}');
		XHR.sendAndLoad('$page', 'POST',x_EnableDisableProxyClient$t);
	}
}
function Apply$t(){
	Loadjs('nginx.single.progress.php?servername=$servernameenc');
}



function LocalForwardedFor(){
	Loadjs('squid.forwarded_for.php');
}

	var x_DeleteSquidChild$t= function (obj) {
			var results=obj.responseText;
			if(results.length>2){alert(results);return;}
			$('#rowTSC'+tmp$t).remove();
		}		

	function DeleteSquidChild(ID){
		tmp$t=ID;
		if(confirm('$remove '+ID+' ?')){
			var XHR = new XHRConnection();
			XHR.appendData('proxy-delete',ID);
			XHR.sendAndLoad('$page', 'POST',x_DeleteSquidChild$t);
		}
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
	
</script>
";
	
	echo $html;
	
	
}

function proxies_enabled(){
	$sql="UPDATE nginx_exchecp SET enabled={$_POST["enable"]} WHERE ID={$_POST["proxy-enable"]}";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}

}

function proxies_delete(){
	$sql="DELETE FROM nginx_exchecp WHERE ID={$_POST["proxy-delete"]}";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}

}

function proxies_list(){
//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$t=$_GET["t"];
	$search='%';
	$table="nginx_exchecp";
	
	
	$FORCE_FILTER="`hostname`='{$_GET["servername"]}'";
	$page=1;

	if($q->COUNT_ROWS($table)==0){json_error_show("No rules....");}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	
	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE $FORCE_FILTER $searchstring  $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show("$q->mysql_error \\n$sql");}
	
	$denyall=$tpl->javascript_parse_text("{deny} {all}");
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total+1;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("No data....");}
	
	$allow=$tpl->javascript_parse_text("{allow}");
	$c=0;
	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$icon="42-server.png";
		$color="black";
		
		$size=FormatBytes($ligne["filesize"]/1024);
		$localpath=basename($ligne["localpath"]);
		$uri=$ligne["zUri"];
		$date=$ligne["zDate"];
		
		$delete=imgsimple("delete-42.png",null,"DeleteSquidChild('{$ligne['ID']}')");
		if($ligne["enabled"]==0){
			//$color="#8a8a8a";
			//$icon="42-server-grey.png";
		}
		
		$c++;
	
	$data['rows'][] = array(
			
		'id' => "TSC{$ligne['ID']}",
		'cell' => array(
				"<span style='font-size:26px;color:$color;margin-top:4px'>$allow {$ligne["ipsrc"]}</span>",
				"<center style='margin-top:4px'>$delete</center>","$delete")
		);
	}
	if($c>1){
		$data['rows'][] = array(
					
				'id' => "TSC{$ligne['ID']}",
				'cell' => array(
						"<span style='font-size:26px;color:$color;margin-top:4px'>$denyall</span>",
						"<center style='margin-top:4px'></center>")
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
			$('#NGINX_EXCHECP_TABLEt').flexReload();
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
	
	if($_POST["authip-add"]==null){echo "Denied NULL Value!\n";return;}
	if($_POST["servername"]==null){echo "Denied NULL HOST Value!\n";return;}
	
	$sql="INSERT IGNORE INTO nginx_exchecp (ipsrc,enabled,hostname) 
	VALUES ('{$_POST["authip-add"]}',1,'{$_POST["servername"]}')";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}

}


