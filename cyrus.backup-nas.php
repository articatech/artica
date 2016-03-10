<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.autofs.inc');
	include_once('ressources/class.computers.inc');
	
	
	$user=new usersMenus();
	if($user->AsMailBoxAdministrator==false){
		$tpl=new templates();
		echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();
	}

	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["nas-parameters"])){nas_parameters();exit;}
	if(isset($_POST["parameters-save"])){nas_parameters_save();exit;}
	if(isset($_GET["test-nas-popup"])){test_nas_popup();exit;}
	if(isset($_GET["containers"])){containers();exit;}
	if(isset($_GET["containers-search"])){containers_search();exit;}
	if(isset($_GET["test-nas-js"])){test_nas_js();exit;}
js();

function test_nas_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{test_connection}");
	echo "YahooWin3('650','$page?test-nas-popup=yes','$title');";
}
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{APP_CYRUS_BACKUP}");
	header("content-type: application/x-javascript");
	echo "YahooWin(930,'$page?tabs=yes','$title');";
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$array["nas-parameters"]="{NAS_storage}";
	$array["schedules"]='{schedules}';
	$array["containers"]='{containers}';
	$array["events"]='{events}';
	
	while (list ($num, $ligne) = each ($array) ){
		if($num=="schedules"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"schedules.php?ForceTaskType=69\" style='font-size:14px'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="events"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"system.mysql.events.php?flexgrid-artica=yes&filename=exec.cyrus.backup.php\" style='font-size:14px'><span>$ligne</span></a></li>\n");
			continue;
		}		
		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\" style='font-size:14px'><span>$ligne</span></a></li>\n");
	}
	echo build_artica_tabs($html, "main_cyrus_backup");
}

function nas_parameters(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$CyrusBackupNas=unserialize(base64_decode($sock->GET_INFO("CyrusBackupNas")));
	if(!is_numeric($CyrusBackupNas["maxcontainer"])){$CyrusBackupNas["maxcontainer"]=3;}
	$test_nas=$tpl->javascript_parse_text("{test_connection}");
	
	
	$CyrusBackupSMTP=$sock->FillSMTPNotifsDefaults($CyrusBackupNas);
	
	
	
	
	
$t=time();
$html="
	<div style='width:98%' class=form>
	<table style='width:100%'>
		
	<tr>
		<td class=legend style='font-size:16px'>{hostname}:</td>
		<td>".Field_text("hostname-$t",$CyrusBackupNas["hostname"],"font-size:16px;width:300px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{shared_folder}:</td>
		<td>".Field_text("folder-$t",$CyrusBackupNas["folder"],"font-size:16px;width:300px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{username}:</td>
		<td>".Field_text("username-$t",$CyrusBackupNas["username"],"font-size:16px;width:200px")."</td>
	</tr>
	
	<tr>
		<td class=legend style='font-size:16px'>{password}:</td>
		<td>".Field_password("password-$t",$CyrusBackupNas["password"],"font-size:16px;width:200px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{max_containers}:</td>
		<td>".Field_text("maxcontainer-$t",$CyrusBackupNas["maxcontainer"],"font-size:16px;width:90px")."</td>
	</tr>	
	<tr>
		<td colspan=2><div style='font-size:22px;margin-bottom:15px'>{smtp_notifications}</div></td>	
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{enable_smtp_notifications}:</td>
		<td>". Field_checkbox("$t-notifs", 1,$CyrusBackupNas["notifs"],"notifsCheck{$t}()")."</td>
	</tr>				
	<tr>
		<td nowrap class=legend style='font-size:16px'>{smtp_server_name}:</strong></td>
		<td>" . Field_text("smtp_server_name-$t",trim($CyrusBackupSMTP["smtp_server_name"]),'font-size:16px;padding:3px;width:250px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:16px'>{smtp_server_port}:</strong></td>
		<td>" . Field_text("smtp_server_port-$t",trim($CyrusBackupSMTP["smtp_server_port"]),'font-size:16px;padding:3px;width:40px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:16px'>{smtp_sender}:</strong></td>
		<td>" . Field_text("smtp_sender-$t",trim($CyrusBackupSMTP["smtp_sender"]),'font-size:16px;padding:3px;width:290px')."</td>
			</tr>
	<tr>
		<td nowrap class=legend style='font-size:16px'>{smtp_dest}:</strong></td>
		<td>" . Field_text("smtp_dest-$t",trim($CyrusBackupSMTP["smtp_dest"]),'font-size:16px;padding:3px;width:290px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:16px'>{smtp_auth_user}:</strong></td>
		<td>" . Field_text("smtp_auth_user-$t",trim($CyrusBackupSMTP["smtp_auth_user"]),'font-size:16px;padding:3px;width:200px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:16px'>{smtp_auth_passwd}:</strong></td>
		<td>" . Field_password("smtp_auth_passwd-$t",trim($CyrusBackupSMTP["smtp_auth_passwd"]),'font-size:16px;padding:3px;width:200px')."</td>
			</tr>
	<tr>
		<td nowrap class=legend style='font-size:16px'>{tls_enabled}:</strong></td>
		<td>" . Field_checkbox("tls_enabled-$t",1,$CyrusBackupSMTP["tls_enabled"])."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:16px'>{UseSSL}:</strong></td>
		<td>" . Field_checkbox("ssl_enabled-$t",1,$CyrusBackupSMTP["ssl_enabled"])."</td>
	</tr>				
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",18)."&nbsp;". button("$test_nas","Loadjs('$page?test-nas-js=yes');",18)."</td>	
	</tr>
	</table>
	</div>
	<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	UnlockPage();
}
	
	
function Save$t(){
	LockPage();
	var XHR = new XHRConnection();
	XHR.appendData('parameters-save','yes');
	XHR.appendData('hostname',encodeURIComponent(document.getElementById('hostname-$t').value));
	XHR.appendData('folder',encodeURIComponent(document.getElementById('folder-$t').value));
	XHR.appendData('username',encodeURIComponent(document.getElementById('username-$t').value));
	XHR.appendData('password',encodeURIComponent(document.getElementById('password-$t').value));
	
	var tls_enabled=0;
	var ssl_enabled=0;
	var notifs=0;
	
	if(document.getElementById('tls_enabled-$t').checked){tls_enabled=1;}
	if(document.getElementById('ssl_enabled-$t').checked){ssl_enabled=1;}
	if(document.getElementById('notifs-$t').checked){notifs=1;}
	XHR.appendData('smtp_server_name',encodeURIComponent(document.getElementById('smtp_server_name-$t').value));
	XHR.appendData('smtp_server_port',encodeURIComponent(document.getElementById('smtp_server_port-$t').value));
	XHR.appendData('smtp_sender',encodeURIComponent(document.getElementById('smtp_sender-$t').value));
	XHR.appendData('smtp_auth_user',encodeURIComponent(document.getElementById('smtp_auth_user-$t').value));
	XHR.appendData('smtp_auth_passwd',encodeURIComponent(document.getElementById('smtp_auth_passwd-$t').value));
	
	XHR.appendData('tls_enabled',tls_enabled);
	XHR.appendData('ssl_enabled',ssl_enabled);
	XHR.appendData('notifs',notifs);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

function notifsCheck{$t}(){
	document.getElementById('smtp_auth_passwd-$t').disabled=true;
	document.getElementById('smtp_auth_user-$t').disabled=true;
	document.getElementById('smtp_dest-$t').disabled=true;
	document.getElementById('smtp_sender-$t').disabled=true;
	document.getElementById('smtp_server_port-$t').disabled=true;
	document.getElementById('smtp_server_name-$t').disabled=true;
	document.getElementById('tls_enabled-$t').disabled=true;
	document.getElementById('ssl_enabled-$t').disabled=true;

	if( document.getElementById('$t-notifs').checked){
		document.getElementById('smtp_auth_passwd-$t').disabled=false;
		document.getElementById('smtp_auth_user-$t').disabled=false;
		document.getElementById('smtp_dest-$t').disabled=false;
		document.getElementById('smtp_sender-$t').disabled=false;
		document.getElementById('smtp_server_port-$t').disabled=false;
		document.getElementById('smtp_server_name-$t').disabled=false;
		document.getElementById('tls_enabled-$t').disabled=false;
		document.getElementById('ssl_enabled-$t').disabled=false;
	}	
	
}
notifsCheck{$t}();

</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function nas_parameters_save(){
	$sock=new sockets();
	$_POST["password"]=url_decode_special_tool($_POST["password"]);
	$sock->SaveConfigFile(base64_encode(serialize($_POST)), "CyrusBackupNas");
	
	
}
function test_nas_popup(){
	$sock=new sockets();
	$t=time();
	$datas=unserialize(base64_decode($sock->getFrameWork("cyrus.php?backup-test-nas=yes")));
	echo "<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:98%;height:446px;border:5px solid #8E8E8E;
	overflow:auto;font-size:11px' id='textToParseCats-$t'>".@implode("\n", $datas)."</textarea>";
}

function containers(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$q=new mysql();
	$sock=new sockets();
	$date=$tpl->_ENGINE_parse_body("{date}");
	$rules=$tpl->javascript_parse_text("{rules}");
	$rule=$tpl->_ENGINE_parse_body("{rule}");
	$nastype=$tpl->javascript_parse_text("{type}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$type=$tpl->javascript_parse_text("{type}");
	$hostname=$tpl->javascript_parse_text("{hostname}");
	$duration=$tpl->javascript_parse_text("{duration}");
	$directory=$tpl->javascript_parse_text("{container}");
	$tablewidht=883;
	$title=$tpl->javascript_parse_text("{containers}");
	
	
	$t=time();
	
	$buttons="buttons : [
	{name: '$add', bclass: 'Add', onpress : NewRule$t},
	],	";
	
	$buttons=null;
	
	echo "<table class='$t' style='display: none' id='flexRT$t' style='width:99%;text-align:left'></table>
	<script>
	var MEMM$t='';
	$(document).ready(function(){
			$('#flexRT$t').flexigrid({
			url: '$page?containers-search=yes&t={$_GET["t"]}&tt={$_GET["tt"]}&ttt=$t&mainid={$_GET["ID"]}&SourceT={$_GET["SourceT"]}',
					dataType: 'json',
					colModel : [
					{display: '$date', name : 'zDate', width : 137, sortable : false, align: 'left'},
					{display: '$hostname', name : 'hostname', width : 150, sortable : true, align: 'left'},
					{display: '$directory', name : 'directory', width : 160, sortable : true, align: 'left'},
					{display: '$duration', name : 'duration', width : 341, sortable : false, align: 'left'},
					
					],
					$buttons
					searchitems : [
					{display: '$hostname', name : 'hostname'},
					{display: '$directory', name : 'directory'},
					{display: '$duration', name : 'duration'},
	
	
					],
					sortname: 'zDate',
					sortorder: 'asc',
					usepager: true,
					title: '$title',
					useRp: true,
					rp: 50,
					showTableToggleBtn: false,
					width: '99%',
					height: 450,
					singleSelect: true
	});
	});
	
	function RefreshTable$t(){
	$('#flexRT$t').flexReload();
	$('#flexRT{$_GET["t"]}').flexReload();
	$('#flexRT{$_GET["tt"]}').flexReload();
	$('#flexRT{$_GET["SourceT"]}').flexReload();
	
	}
	
	var x_Refresh$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	RefreshTable$t();
	}
	
	var x_ConnectionDelete$t=function (obj) {
	var results=obj.responseText;
	if(results.length>2){alert(results);return;}
	RefreshTable$t();
	}
	
	function NewRule$t(){
	Loadjs('$page?item-js=yes&ID=0&t={$_GET["t"]}&tt={$_GET["t"]}&ttt=$t&mainid={$_GET["ID"]}&mainid2={$_GET["mainid"]}&SourceT={$_GET["SourceT"]}');
	}
	
	function MoveItem$t(mkey,direction){
	var XHR = new XHRConnection();
	XHR.appendData('item-move', mkey);
	XHR.appendData('ruleid', '{$_GET["ID"]}');
	XHR.appendData('direction', direction);
	XHR.sendAndLoad('$page', 'POST',RefreshTable$t);
	}
	
	function MoveRuleDestinationAsk$t(mkey,def){
	var zorder=prompt('Order',def);
			if(!zorder){return;}
			var XHR = new XHRConnection();
			XHR.appendData('item-move', mkey);
			XHR.appendData('ruleid', '{$_GET["ID"]}');
			XHR.appendData('item-destination-zorder', zorder);
			XHR.sendAndLoad('$page', 'POST',RefreshTable$t);
	}
	
	function EnableDisable$t(ID){
	var XHR = new XHRConnection();
	XHR.appendData('item-enable',ID);
	XHR.sendAndLoad('$page', 'POST',x_Refresh$t);
	}
	
	function ItemDelete$t(ID){
	MEMM$t=ID;
	if(confirm('$delete ?')){
	var XHR = new XHRConnection();
	XHR.appendData('item-delete',ID);
	XHR.sendAndLoad('$page', 'POST',x_ConnectionDelete$t);
	}
	}
	</script>
	";	
	
}
function containers_search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$sock=new sockets();
	$q=new mysql();
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	$search='%';
	$table="cyrus_backup";
	$page=1;
	$data = array();
	$data['rows'] = array();
	$FORCE_FILTER=1;
	if(!$q->TABLE_EXISTS($table,"artica_events")){json_error_show("no data");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		if(!$q->ok){json_error_show("$q->mysql_error",1);}
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		if(!$q->ok){json_error_show("$q->mysql_error",1);}
		$total = $ligne["TCOUNT"];
			
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(is_numeric($rp)){
		$pageStart = ($page-1)*$rp;
		$limitSql = "LIMIT $pageStart, $rp";
	}
	
	$sql="SELECT *  FROM `$table` WHERE $FORCE_FILTER $searchstring $ORDER $limitSql";
	
	
	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){json_error_show("$q->mysql_error",1);}
	
	
	
	$data['page'] = $page;
	$data['total'] = $total;
	
	if(mysql_num_rows($results)==0){json_error_show("no rule",1);}
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$md5=md5(serialize($ligne));
		$span="<span style='font-size:14px'>";
		$ligne['duration']=$tpl->_ENGINE_parse_body($ligne['duration']);
		$size=$ligne["size"]/1024;
		$size=FormatBytes($size);
		$data['rows'][] = array(
				'id' => $md5,
				'cell' => array(
						"$span{$ligne['zDate']}</a></span>",
						"$span{$ligne['hostname']}</a></span>",
						"$span{$ligne['directory']} $size</a></span>",
						"$span{$ligne['duration']}</a></span>",
						
				)
		);
	
	}
	
	
	echo json_encode($data);
}