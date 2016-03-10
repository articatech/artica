<?php
	session_start();
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.pure-ftpd.inc');
	include_once('ressources/class.apache.inc');
	include_once('ressources/class.freeweb.inc');
	include_once('ressources/class.user.inc');
	include_once('ressources/class.system.network.inc');
	$user=new usersMenus();
	if($user->AsWebMaster==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	
	if(isset($_POST["enable_ldap_authentication"])){SaveConfig();exit;}
	if(isset($_GET["query"])){Query();exit;}
	if(isset($_POST["authip-add"])){authip_add();exit;}
	if(isset($_POST["authip-del"])){authip_del();exit;}
	if(isset($_POST["authip-list"])){authip_list();exit;}
	if(isset($_POST["LimitByIp"])){authip_enable();exit;}	
	params();
	





function params(){
	$sql="SELECT * FROM freeweb WHERE servername='{$_GET["servername"]}'";
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$sock=new sockets();
	$FreeWebsEnableModSecurity=$sock->GET_INFO("FreeWebsEnableModSecurity");
	$FreeWebsEnableModEvasive=$sock->GET_INFO("FreeWebsEnableModEvasive");
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));	
	$Params=unserialize(base64_decode($ligne["Params"]));
	$apache_auth_ip_explain=$tpl->javascript_parse_text("{apache_auth_ip_explain}");
	$users=new usersMenus();
	$APACHE_MOD_AUTHNZ_LDAP=0;
	$APACHE_MOD_GEOIP=0;
	if($users->APACHE_MOD_AUTHNZ_LDAP){$APACHE_MOD_AUTHNZ_LDAP=1;}
	if($users->APACHE_MOD_GEOIP){$APACHE_MOD_GEOIP=1;}
	$ServerSignature=$sock->GET_INFO("ApacheServerSignature");
	if(!is_numeric($ServerSignature)){$ServerSignature=1;}	
	if(!is_numeric($FreeWebsEnableModSecurity)){$FreeWebsEnableModSecurity=0;}
	if(!is_numeric($FreeWebsEnableModEvasive)){$FreeWebsEnableModEvasive=0;}
	$ZarafaWebNTLM=0;
	$t=time();


	$add=$tpl->_ENGINE_parse_body("{add}");
	$address=$tpl->_ENGINE_parse_body("{address}");	
$enable_limit_by_addresses=$tpl->_ENGINE_parse_body("{enable_limit_by_addresses}");	
	

		$title=$tpl->javascript_parse_text("{clients_restrictions}");
		
$buttons="buttons : [
	{name: '<strong style=font-size:18px>$add</strong>', bclass: 'Add', onpress : AuthIpAdd},
		],	";


	
echo "
<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:18px'>$enable_limit_by_addresses:</td>
		<td>". Field_checkbox_design("LimitByIp",1,$Params["LimitByIp"]["enabled"],"enable_ip_authentication_save$t()")."</td>
	</tr>	
</table>
<table class='$t' style='display: none' id='$t' style='width:99%'></table>
<script>
var MEMMDAUTH='';
$(document).ready(function(){
$('#$t').flexigrid({
	url: '$page?query=yes&servername={$_GET["servername"]}',
	dataType: 'json',
	colModel : [
			{display: '&nbsp;', name : 'zDate', width : 40, sortable : false, align: 'left'},	
			{display: '$address', name : 'address', width :685, sortable : false, align: 'left'},
			{display: '&nbsp;', name : 'none2', width : 40, sortable : false, align: 'left'},
		
	],
$buttons
	searchitems : [
		{display: '$address', name : 'address'}
		],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 300,
	singleSelect: true
	
	});   
});
	

	
		function RefreshAuthIp(){
			$('#$t').flexReload();
		}
		
		function enable_ip_authentication_save$t(){
			var XHR = new XHRConnection();
			if(document.getElementById('LimitByIp').checked){XHR.appendData('LimitByIp',1);}else{XHR.appendData('LimitByIp',0);}
			XHR.appendData('servername','{$_GET["servername"]}');
    		XHR.sendAndLoad('$page', 'POST',x_AuthIpAdd$t);
		}
		
		var x_AuthIpAdd$t=function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			RefreshAuthIp();			
		}

		var x_AuthIpDel=function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);return;}
			$('#row'+MEMMDAUTH).remove();		
		}		
		
		function AuthIpAdd(){
			var ip=prompt('$apache_auth_ip_explain');
			if(ip){
				var XHR = new XHRConnection();
				XHR.appendData('authip-add',ip);
				XHR.appendData('servername','{$_GET["servername"]}');
				XHR.sendAndLoad('$page', 'POST',x_AuthIpAdd$t);
			}
		}
		
		function AuthIpDel(ip,id){
				MEMMDAUTH=id;
				var XHR = new XHRConnection();
				XHR.appendData('authip-del',ip);
				XHR.appendData('servername','{$_GET["servername"]}');
				XHR.sendAndLoad('$page', 'POST',x_AuthIpDel);
			
		}			

	</script>
	
	
	
	";

	}
	
	
	function authip_add(){
	$freeweb=new freeweb($_POST["servername"]);
	$freeweb->LimitByIp_add($_POST["authip-add"]);
	
}

function authip_del(){
	$freeweb=new freeweb($_POST["servername"]);
	$freeweb->LimitByIp_del($_POST["authip-del"]);	
}

function authip_enable(){
	$freeweb=new freeweb($_POST["servername"]);
	$freeweb->Params["LimitByIp"]["enabled"]=$_POST["LimitByIp"];
	$freeweb->SaveParams();
	
}

function authip_list(){
	$freeweb=new freeweb($_GET["servername"]);
	$page=CurrentPageName();
	$tpl=new templates();
	$hash=$freeweb->LimitByIp_list();
	
	$html="
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
	<th>&nbsp;</th>
	<th>{ipaddr}</th>
	<th>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>";		
	if(is_array($hash)){
		while (list ($num, $ligne) = each ($hash) ){
			
			if($ligne==null){continue;}
			if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}	
			
		$html=$html."
			<tr class=$classtr>
			<td width=1%><img src='img/folder-network-32.png'></td>
			<td nowrap><strong style='font-size:14px'>$ligne</strong></td>
			<td width=1%>". imgtootltip("delete-32.png","{delete}","AuthIpDel('$ligne')")."</td>
			</tr>
			";	
		}
	}

		$html=$html."</table>
		<script>
	
		</script>
		";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function Query(){
	$MyPage=CurrentPageName();
	$freeweb=new freeweb($_GET["servername"]);
	$page=CurrentPageName();
	$tpl=new templates();
	$hash=$freeweb->LimitByIp_list();	

	
	if($_POST["query"]<>null){
		$_POST["query"]="*{$_POST["query"]}*";
		$_POST["query"]=str_replace(".", "\.", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", ".*?", $_POST["query"]);	
		
	}
	$page=1;
	$COUNT_ROWS=count($hash);
if($COUNT_ROWS==0){json_error_show("no data");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	$_POST["query"]=trim($_POST["query"]);
	$total = $COUNT_ROWS;
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	
	if($COUNT_ROWS==0){json_error_show("no data");}
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	$c=0;
	while (list ($num, $ligne) = each ($hash) ){
		if($ligne==null){continue;}
		if($_POST["query"]<>null){if(!preg_match("#{$_POST["query"]}#", $ligne)){continue;}}
		$c++;
		
		$added=null;
		$id=md5($ligne);
		$delete=imgtootltip("delete-24.png","{delete}","AuthIpDel('$ligne','$id')");
		
		
$jscat="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('squid.categorize.php?www={$ligne["pattern"]}',true);\"
		style='font-size:14px;text-decoration:underline'>";		
		
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array("
		<img src='img/folder-network-32.png'>",
		"<span style='font-size:18px'>$ligne</a></span>",
		$delete)
		);
	}
	if($c==0){json_error_show("no data");}
	
echo json_encode($data);	
}