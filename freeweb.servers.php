<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.freeweb.inc');
	include_once('ressources/class.awstats.inc');
	include_once('ressources/class.pdns.inc');
	include_once('ressources/class.squid.inc');
	

	$user=new usersMenus();
	if($user->AsWebMaster==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["enable-site-js"])){enable_site_js();exit;}
	if(isset($_GET["servers-list"])){servers_list();exit;}
	if(isset($_GET["freeweb-zarafa-choose"])){FreeWebsPopupZarafa();exit;}
	if(isset($_POST["FreeWebsEnableSite"])){FreeWebsEnableSite();exit;}
	
page();

function enable_site_js(){
	$servername=$_GET["servername"];
	$enabled=$_GET["enabled"];
	$t=$_GET["t"];
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$time=time();
	echo "
	var xFreeWebs$time=function (obj) {
			var results=obj.responseText;
			if(results.length>10){alert(results);return;}	
			$('#freewebs-table-$t').flexReload();
		}	
	
	function FreeWebs$time(){
		var XHR = new XHRConnection();
		XHR.appendData('FreeWebsEnableSite','$servername');
		XHR.sendAndLoad('$page', 'POST',xFreeWebs$time);	
	}		
			
	FreeWebs$time();		
	";
	
	
}



function page(){
	$users=new usersMenus();
	$page=CurrentPageName();
	$tpl=new templates();
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$context=$tpl->_ENGINE_parse_body("{context}");	
	$events=$tpl->_ENGINE_parse_body("{events}");	
	$empty=$tpl->_ENGINE_parse_body("{empty}");	
	$empty_events_text_ask=$tpl->javascript_parse_text("{empty_events_text_ask}");	
	$bt_klms_reset_pwd=null;
	$joomlaservername=$tpl->_ENGINE_parse_body("{joomlaservername}");
	$memory=$tpl->_ENGINE_parse_body("{memory}");
	$requests=$tpl->_ENGINE_parse_body("{requests}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$new_server=$tpl->_ENGINE_parse_body("{new_server}");
	$add_default_www=$tpl->_ENGINE_parse_body("{add_default_www}");
	$delete_freeweb_text=$tpl->javascript_parse_text("{delete_freeweb_text}");
	$delete_freeweb_dnstext=$tpl->javascript_parse_text("{delete_freeweb_dnstext}");	
	$WebDavPerUser=$tpl->_ENGINE_parse_body("{WebDavPerUser}");
	$rebuild_items=$tpl->_ENGINE_parse_body("{rebuild_items}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$help=$tpl->_ENGINE_parse_body("{help}");
	$restore=$tpl->_ENGINE_parse_body("{restore}");
	$status=$tpl->javascript_parse_text("{status}");
	$reset_admin_password=$tpl->javascript_parse_text("{reset_admin_password}");
	$choose_your_zarafa_webserver_type=$tpl->_ENGINE_parse_body("{choose_your_zarafa_webserver_type}");
	$freeweb_compile_background=$tpl->javascript_parse_text("{freeweb_compile_background}");
	$enable=$tpl->javascript_parse_text("{enable}");
	$bt_default_www="{name: '<strong style=font-size:18px>$add_default_www</strong>', bclass: 'add', onpress : FreeWebAddDefaultVirtualHost},";
	$bt_webdav="{name: '<strong style=font-size:18px>$WebDavPerUser</strong>', bclass: 'add', onpress : FreeWebWebDavPerUsers},";
	$bt_rebuild="{name: '<strong style=font-size:18px>$rebuild_items</strong>', bclass: 'Reconf', onpress : RebuildFreeweb},";
						
	$bt_restore="{name: '<strong style=font-size:18px>$restore</strong>', bclass: 'Restore', onpress : RestoreSite},";
	$bt_stats="{name: '<strong style=font-size:18px>$status</strong>', bclass: 'Network', onpress : ApacheAllstatus},";
	$MAIN_TITLE=null;
	$tablewidth=874;
	$servername_size=366;
	$bt_function_add="AddNewFreeWebServer";
	
	
	if($_GET["force-groupware"]<>null){	
		include_once(dirname(__FILE__)."/ressources/class.apache.inc");
		$default_www=null;
		$bt_webdav=null;
		$ach=new vhosts();
		$MAIN_TITLE="<span style=font-size:18px>".$tpl->_ENGINE_parse_body("{".$ach->TEXT_ARRAY[$_GET["force-groupware"]]["TITLE"]."}")."</span>";
		if($_GET["force-groupware"]=="KLMS"){
			$bt_klms_reset_pwd="{name: '$reset_admin_password', bclass: 'Restore', onpress : klmsresetwebpassword},";
		}
	
	
	}	
	if($_GET["tabzarafa"]=="yes"){
		$tablewidth=690;
		$servername_size=64;
		$bt_webdav=null;
		$bt_default_www=null;
		$bt_function_add="AddNewFreeWebServerZarafa";
		$bt_restore=null;
	}

	if($_GET["minimal-tools"]=="yes"){
		$bt_default_www=null;
		$bt_restore=null;
		$bt_webdav=null;
		$bt_help=null;
		
	}
	
	if(!$users->APACHE_MOD_STATUS){
		$bt_stats=null;
		
	}
	
	$users=new usersMenus();
	if($users->SQUID_INSTALLED){
		$sock=new sockets();
		$SquidActHasReverse=$sock->GET_INFO("SquidActHasReverse");
		if(!is_numeric($SquidActHasReverse)){$SquidActHasReverse=0;}
		$squid=new squidbee();
		if($squid->isNGnx()){$SquidActHasReverse=1;}
		
		if($SquidActHasReverse==1){
			$explainSquidActHasReverse=$tpl->_ENGINE_parse_body("<div class=explain style='font-size:14px'>{explain_freewebs_reverse}</div>");
		}
	}
	
	
	$t=time();
	
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$new_server</strong>', bclass: 'add', onpress : $bt_function_add},
	$bt_default_www$bt_webdav$bt_rebuild$bt_restore$bt_klms_reset_pwd$bt_help$bt_stats
	
		],";
	$html="
	$explainSquidActHasReverse
	<input type='hidden' id='freewebs-table-id' value='freewebs-table-$t'>
	<table class='freewebs-table-$t' style='display: none' id='freewebs-table-$t' style='width:100%;margin:-10px'></table>
<script>
FreeWebIDMEM='';

$('#freewebs-table-$t').flexigrid({
	url: '$page?servers-list=yes&t=$t&force-groupware={$_GET["force-groupware"]}&ForceInstanceZarafaID={$_GET["ForceInstanceZarafaID"]}&t=$t&tabzarafa={$_GET["tabzarafa"]}',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'icon', width : 31, sortable : false, align: 'center'},
		{display: '$joomlaservername', name : 'servername', width :$servername_size, sortable : true, align: 'left'},
		{display: 'compile', name : 'compile', width :80, sortable : false, align: 'center'},
		{display: '$enable', name : 'enabled', width :80, sortable : true, align: 'center'},
		{display: '$size', name : 'DirectorySize', width :80, sortable : true, align: 'center'},
		{display: '$memory', name : 'memory', width :80, sortable : false, align: 'center'},
		{display: '$requests', name : 'requests', width : 72, sortable : false, align: 'center'},
		{display: 'SSL', name : 'useSSL', width : 31, sortable : true, align: 'center'},
		{display: 'RESOLV', name : 'resolved_ipaddr', width : 31, sortable : true, align: 'center'},
		{display: 'DNS', name : 'dns', width : 31, sortable : false, align: 'center'},
		{display: '$member', name : 'member', width : 31, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'none1', width : 80, sortable : false, align: 'center'},
	],
	$buttons

	searchitems : [
		{display: '$joomlaservername', name : 'servername'},
		],
	sortname: 'servername',
	sortorder: 'desc',
	usepager: true,
	title: '$MAIN_TITLE',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true
	
	});   


	function HelpSection(){
		LoadHelp('freewebs_explain','',false);
	}

	function AddNewFreeWebServer(){
		 Loadjs('freeweb.edit.php?hostname=&force-groupware={$_GET["force-groupware"]}&t=$t')
	}
	
	function AddNewFreeWebServerZarafa(){
		YahooWin('650','$page?freeweb-zarafa-choose=yes&t=$t','$choose_your_zarafa_webserver_type');
	}
	
	
	function ApacheAllstatus(){
		Loadjs('freeweb.status.php');
	}
	
	
	function FreeWebWebDavPerUsers(){
		Loadjs('freeweb.webdavusr.php?t=$t')
	}
	
	function RestoreSite(){
		Loadjs('freeweb.restoresite.php?t=$t')
	}
	
	function FreeWebsRefreshWebServersList(){
		$('#freewebs-table-$t').flexReload();
	}
	
	
	var x_EmptyEvents= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		$('#freewebs-table-$t').flexReload();
		//$('#grid_list').flexOptions({url: 'newurl/'}); 
		// $('#fgAllPatients').flexOptions({ query: 'blah=qweqweqwe' }).flexReload();
		
	}	
	
	var x_FreeWebsRebuildvHostsTable= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		alert('$freeweb_compile_background');
		$('#freewebs-table-$t').flexReload();
		//$('#grid_list').flexOptions({url: 'newurl/'}); 
		// $('#fgAllPatients').flexOptions({ query: 'blah=qweqweqwe' }).flexReload();
		}

	
	var x_klmsresetwebpassword$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		$('#freewebs-table-$t').flexReload();
	}	
	
	var x_FreeWebDelete=function (obj) {
			var results=obj.responseText;
			if(results.length>10){alert(results);return;}	
			$('#row'+FreeWebIDMEM).remove();
			if(document.getElementById('container-www-tabs')){	RefreshTab('container-www-tabs');}
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

	var x_FreeWebRefresh=function (obj) {
			var results=obj.responseText;
			if(results.length>10){alert(results);return;}	
			$('#freewebs-table-$t').flexReload();
		}		
		
		function FreeWebAddDefaultVirtualHost(){
			var XHR = new XHRConnection();
			XHR.appendData('AddDefaultOne','yes');
    		XHR.sendAndLoad('freeweb.php', 'POST',x_FreeWebRefresh);		
		}
		
		function FreeWeCheckVirtualHost(){
			var XHR = new XHRConnection();
			XHR.appendData('CheckAVailable','yes');
    		XHR.sendAndLoad('freeweb.php', 'POST',x_FreeWebDelete);			
		}
		
		var x_RebuildFreeweb$t=function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}			
			$('#freewebs-table-$t').flexReload();
		}			
		
		function RebuildFreeweb(){
			var XHR = new XHRConnection();
			XHR.appendData('rebuild-items','yes');
    		XHR.sendAndLoad('freeweb.php', 'GET',x_RebuildFreeweb$t);
		
		}

		function klmsresetwebpassword(){
		  if(confirm('$reset_admin_password ?')){
				var XHR = new XHRConnection();
				XHR.appendData('klms-reset-password','yes');
    			XHR.sendAndLoad('klms.php', 'POST',x_klmsresetwebpassword$t);
    		}		
		}
		
	function FreeWebsRebuildvHostsTable(servername){
		var XHR = new XHRConnection();
		XHR.appendData('FreeWebsRebuildvHosts',servername);
		XHR.sendAndLoad('freeweb.edit.php', 'POST',x_FreeWebsRebuildvHostsTable);
	}


		
	
</script>";
	
	echo $html;	
	
}




function servers_list(){
	include_once(dirname(__FILE__).'/ressources/class.apache.inc');
	unset($_SESSION["MYSQL_PARAMETERS"]);
	$vhosts=new vhosts();
	$search=$_GET["search"];
	$MyPage=CurrentPageName();
	$page=1;
	$users=new usersMenus();
	$DNS_INSTALLED=false;
	$tpl=new templates();	
	$GLOBALS["CLASS_TPL"]=$tpl;
	$sock=new sockets();	
	$where=null;
	$query_groupware=null;
	$ZarafaWebAccessInFrontEnd=$sock->GET_INFO("ZarafaWebAccessInFrontEnd");
	if(!is_numeric($ZarafaWebAccessInFrontEnd)){$ZarafaWebAccessInFrontEnd=1;}
	if(!$users->ZARAFA_INSTALLED){$ZarafaWebAccessInFrontEnd=0;}
	$FreeWebDisableSSL=intval(trim($sock->GET_INFO("FreeWebDisableSSL")));
	
	$addg=imgtootltip("plus-24.png","{add} {joomlaservername}","Loadjs('freeweb.edit.php?hostname=&force-groupware={$_GET["force-groupware"]}')");
	if($_POST["query"]<>null){$search=$_POST["query"];}
	
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM freeweb WHERE servername=''",'artica_backup');
	
	if($_GET["force-groupware"]<>null){
		if($_GET["force-groupware"]=="ZARAFA-WEBS"){
			if($_GET["ForceInstanceZarafaID"]>0){$ForceInstanceZarafaIDQ=" AND ForceInstanceZarafaID={$_GET["ForceInstanceZarafaID"]}";}
			$query_groupware=" AND ((groupware='ZARAFA'$ForceInstanceZarafaIDQ) OR (groupware='ZARAFA_MOBILE'$ForceInstanceZarafaIDQ) OR (groupware='Z-PUSH'$ForceInstanceZarafaIDQ) OR (groupware='WEBAPP'$ForceInstanceZarafaIDQ))";
			
		}
		if($query_groupware==null){
			$query_groupware=" AND groupware='{$_GET["force-groupware"]}'";
		}
	}
	
	if(!$users->AsSystemAdministrator){
		$whereOU="  AND ou='{$_SESSION["ou"]}'";
		$ou="&nbsp;&raquo;&nbsp;{$_SESSION["ou"]}";
	}
	
	if(strlen($search)>1){
		$search="*$search*";
		$search=str_replace("*","%",$search);
		$search=str_replace("%%","%",$search);
		$whereOU="AND (servername LIKE '$search' $whereOU$query_groupware) OR (domainname LIKE '$search' $whereOU$query_groupware)";
	}else{
		$query_groupware_single=$query_groupware;
	}
	
	if($users->dnsmasq_installed){$DNS_INSTALLED=true;}
	if($users->POWER_DNS_INSTALLED){$DNS_INSTALLED=true;}
	
	$data = array();
	$data['rows'] = array();	
	
	
	if(strlen($search)<2){
		if($_GET["force-groupware"]<>"ZARAFA-WEBS"){
		$sock=new sockets();
		$EnableWebDavPerUser=$sock->GET_INFO("EnableWebDavPerUser");
		if(!is_numeric($EnableWebDavPerUser)){$EnableWebDavPerUser=0;}
		$WebDavPerUserSets=unserialize(base64_decode($sock->GET_INFO("WebDavPerUserSets")));
		
		if($EnableWebDavPerUser==1){
				$icon="webdav-32.png";
				$groupware=div_groupware("WebDav");
				$href="<a href=\"javascript:blur();\" 
				OnClick=\"javascript:Loadjs('freeweb.webdavusr.php')\" 
				style='font-size:13px;text-decoration:underline;font-weight:bold'>";
				$edit=imgtootltip($icon,"{apply} *.{$WebDavPerUserSets["WebDavSuffix"]}","Loadjs('freeweb.webdavusr.php')");
				if($WebDavPerUserSets["EnableSSL"]==1){$ssl="20-check.png";}else{$ssl="none-20.png";}
				
				
				
			$data['rows'][] = array(
				'id' => '-200',
				'cell' => array(
					$icon, 
					"$href*.{$WebDavPerUserSets["WebDavSuffix"]}</a>",
					"&nbsp;",
					"&nbsp;",
					"&nbsp;",
					"&nbsp;",
					"&nbsp;",
					"&nbsp;",
					"&nbsp;",
					"&nbsp;",
					"&nbsp;",
					"&nbsp;",
					)
				);
				
			}
		}
	}	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}	
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	if(!is_numeric($rp)){$rp=50;}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	

	$q=new mysql();
	
	$sqlCount="SELECT COUNT(*) AS TCOUNT FROM freeweb WHERE 1 $whereOU$query_groupware_single";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sqlCount,"artica_backup"));	
	if(!$q->ok){
		writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		json_error_show($q->mysql_error,1);
		}
	$countDeRows=$ligne["TCOUNT"];
	writelogs($sqlCount." $countDeRows rows",__FUNCTION__,__FILE__,__LINE__);	
	if($countDeRows==0){json_error_show("");}
	
	

	$total =$countDeRows;
	$data['page'] = $page;
	$data['total'] = $total;	
	$members_text=$tpl->_ENGINE_parse_body("{members}");
	
	if(!isset($_SESSION["CheckTableWebsites"])){$q->BuildTables();$_SESSION["CheckTableWebsites"]=true;}
	$sql="SELECT * FROM freeweb WHERE 1 $whereOU$query_groupware_single $ORDER $limitSql";
	$results=$q->QUERY_SQL($sql,'artica_backup');
	writelogs($sql." ".mysql_num_rows($results)." rows",__FUNCTION__,__FILE__,__LINE__);	
	
	if(!$q->ok){writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
	$vgservices=unserialize(base64_decode($sock->GET_INFO("vgservices")));
	$duplicate=$tpl->javascript_parse_text("{duplicate}");
	$pdns=new pdns();
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		if($ligne["useSSL"]==1){$ssl="20-check.png";}else{$ssl="20-check-grey.png";}
		if($FreeWebDisableSSL==1){$ssl="20-check-grey.png";}
		$DirectorySize=FormatBytes($ligne["DirectorySize"]/1024);
		$WebCopyID=$ligne["WebCopyID"];
		$groupware_duplicate=null;
		$statistics="&nbsp;";
		$exec_statistics="&nbsp;";
		$Members=null;
		$groupware=null;
		$forward_text=null;
		$checkDNS="<img src='img/20-check-grey.png'>";
		$checkMember="<img src='img/20-check-grey.png'>";
		$JSDNS=0;
		if($DNS_INSTALLED){
			$ip=$pdns->GetIpDN($ligne["servername"]);
			if($ip<>null){
				$checkDNS="<img src='img/20-check.png'>";
				$JSDNS=1;
			}
		}
		$ServerAlias=null;
		$Params=@unserialize(base64_decode($ligne["Params"]));
		$f=array();
		if(isset($Params["ServerAlias"])){
			while (list ($host,$num) = each ($Params["ServerAlias"]) ){
				$f[]=$host;
			}
			$ServerAlias=div_groupware("<a href=\"javascript:blur();\" 
			OnClick=\"javascript:Loadjs('freeweb.edit.ServerAlias.php?servername={$ligne["servername"]}')\" 
			style='text-decoration:underline'><i>".@implode(", ", $f)."</i>");
		}
		
		
		
		if($ligne["uid"]<>null){$checkMember="<img src='img/20-check.png'>";}
		
		$added_port=null;
		$icon=build_icon($ligne,$ligne["servername"]);
		
		if($vgservices["freewebs"]<>null){
			if($ligne["lvm_size"]>0){
				$ligne["lvm_size"]=$ligne["lvm_size"]*1024;
				$sizevg="&nbsp;<i style='font-size:11px'>(".FormatBytes($ligne["lvm_size"]).")</i>";
				
			}
		}
		$ServerPort=$ligne["ServerPort"];
		if($ServerPort>0){$added_port=":$ServerPort";}
		if($ligne["groupware"]<>null){$groupware=div_groupware("({{$vhosts->TEXT_ARRAY[$ligne["groupware"]]["TITLE"]}})",$ligne["enabled"]);}
		
		if($ligne["Forwarder"]==1){$forward_text=div_groupware("{www_forward} <b>{$ligne["ForwardTo"]}</b>",$ligne["enabled"]);}
		$js_edit="Loadjs('freeweb.edit.php?hostname={$ligne["servername"]}&t={$_GET["t"]}')";
		
		
		$servername_text=$ligne["servername"];
		if($servername_text=="_default_"){
			$servername_text="{all}";
			if($ZarafaWebAccessInFrontEnd==1){$servername_text="{APP_ZARAFA}";}
			if($FreeWebDisableSSL==0){$ssl="20-check.png";}
			
			$groupware=div_groupware("({default_website})",$ligne["enabled"]);
		}else{
			$checkResolv="<img src='img/20-check.png'>";
				
			if(trim($ligne["resolved_ipaddr"])==null){
				$error_text="{could_not_find_iphost}";
				$checkResolv="<img src='img/20-check-grey.png'>";
			}
		
			
	}
		$colorhref=null;
		if($ligne["enabled"]==0){$colorhref="color:#8C8C8C";}
		
		$href="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('freeweb.edit.php?hostname={$ligne["servername"]}&t={$_GET["t"]}')\"
		style='font-size:16px;text-decoration:underline;font-weight:bold;$colorhref'>";
		$color="black";
		$md5S=md5($ligne["servername"]);
		$delete=icon_href("delete-32.png","FreeWebDelete('{$ligne["servername"]}',$JSDNS,'$md5S')");
		
		$sql="SELECT ID FROM drupal_queue_orders WHERE `ORDER`='DELETE_FREEWEB' AND `servername`='{$ligne["servername"]}'";
		$ligneDrup=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));	
		if($ligne["ID"]>0){
			$edit=imgtootltip("folder-tasks-32.png","{delete}");
			$color="#8a8a8a";
			$delete=imgtootltip("delete-32-grey.png","{delete} {scheduled}");
			
		}
		$sql="SELECT ID FROM drupal_queue_orders WHERE `ORDER`='INSTALL_GROUPWARE' AND `servername`='{$ligne["servername"]}'";
		if($ligne["ID"]>0){
			$edit=icon_href("folder-tasks-32.png","Loadjs('freeweb.edit.php?hostname={$ligne["servername"]}')");
			$color="#8a8a8a";
			$delete=icon_href("delete-32-grey.png");
			$groupware=div_groupware("({installing} {{$vhosts->TEXT_ARRAY[$ligne["groupware"]]["TITLE"]}})",$ligne["enabled"]);
			
		}
		
		

		
		$Params=@unserialize(base64_decode($ligne["Params"]));
		$IsAuthen=false;
		if($Params["LDAP"]["enabled"]==1){$IsAuthen=true;}
		if($Params["NTLM"]["enabled"]==1){$IsAuthen=true;}
		
		$color_orange="#B64B13";
		if($ligne["enabled"]==0){$color_orange="#8C8C8C";}
		
		if($IsAuthen){
			$Members="<span style='font-size:11px;font-weight:bold;color:$color_orange;'>&nbsp;&laquo;<a href=\"javascript:blur();\" 
			OnClick=\"javascript:Loadjs('freeweb.edit.ldap.users.php?servername={$ligne["servername"]}');\"
			style='font-size:11px;font-weight:bold;color:$color_orange;text-decoration:underline;font-style:italic'>$members_text</a>
			&nbsp;&raquo;</span>";
		}

		$memory="-";$requests_second="-";$traffic_second="-";$uptime=null;
		$table_name_stats="apache_stats_".date('Ym');
		$sql="SELECT * FROM $table_name_stats WHERE servername='{$ligne["servername"]}' ORDER by zDate DESC LIMIT 0,1";
		$ligneStats=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		if($ligneStats["total_memory"]>0){
			$memory=FormatBytes($ligneStats["total_memory"]/1024);
			$requests_second="{$ligneStats["requests_second"]}/s";
			$traffic_second=FormatBytes($ligneStats["traffic_second"]/1024)."/s";
			$uptime=div_groupware("{uptime}:{$ligneStats["UPTIME"]}",$ligne["enabled"]);
			
		}
		
		$groupware=$tpl->_ENGINE_parse_body($groupware);
		$forward_text=$tpl->_ENGINE_parse_body($forward_text);
		$servername_text=$tpl->_ENGINE_parse_body($servername_text);
		$ServerAlias=$tpl->_ENGINE_parse_body($ServerAlias);
		$uptime=$tpl->_ENGINE_parse_body($uptime);
		$memory=$tpl->_ENGINE_parse_body($memory);
		$requests_second=$tpl->_ENGINE_parse_body("$requests_second");
		$traffic_second=$tpl->_ENGINE_parse_body($traffic_second);
		$checkResolv=$tpl->_ENGINE_parse_body($checkResolv);
		$checkDNS=$tpl->_ENGINE_parse_body($checkDNS);
		$checkMember=$tpl->_ENGINE_parse_body($checkMember);
		$delete=$tpl->_ENGINE_parse_body($delete);
		if($WebCopyID>0){
			$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT sitename FROM httrack_sites WHERE ID=$WebCopyID","artica_backup"));
			$groupware=div_groupware("WebCopy: {$ligne2["sitename"]}",$ligne["enabled"]);
		}
		

		
		$servername_enc=urlencode($ligne["servername"]);
		$color_span="#5F5656";
		if($ligne["enabled"]==0){$color_span="#8C8C8C";}
		$compile=imgsimple("apply-config-32.png",null,"Loadjs('freeweb.rebuild.progress.php?servername=$servername_enc')");
		
		
		
		$enable=Field_checkbox("enable_$md5S", 1,$ligne["enabled"],"Loadjs('freeweb.servers.php?enable-site-js=yes&t={$_GET["t"]}&servername=$servername_enc&enabled={$ligne["enabled"]}');");
		
		if($ligne["groupware_duplicate"]<>null){
			$groupware_duplicate="<br><span style='color:#d32d2d'>{$duplicate} :{$ligne["groupware_duplicate"]}</span>";
		}
		
		if($ligne["enabled"]==0){
			$requests_second="-";
			$traffic_second="-";
			$memory="-";
			$color="#8C8C8C";$color_span=$color;$icon="status_disabled.gif";$compile="&nbsp;";}
		
		$spanStyle1="<span style='font-size:11px;font-weight:bold;color:#5F5656;'>";
		
			$data['rows'][] = array(
				'id' => $md5S,
				'cell' => array(
					"<img src='img/$icon'>", 
					"<strong style='font-size:13px;style='color:$color'>$href$servername_text</a>$groupware_duplicate$iconPlus$groupware$forward_text
					$added_port$Members$sizevg</strong></span>$ServerAlias$uptime",
					"<center>$compile</center>",
					"<center>$enable</center>",	
					"$spanStyle1$DirectorySize</span>",
					"$spanStyle1$memory</span>",
					"$spanStyle1$requests_second&nbsp;|&nbsp;$traffic_second</span>",
					"<center><img src='img/$ssl'></center>",
					"<center>$checkResolv</center>",
					"<center>$checkDNS</center>",
					"<center>$checkMember</center>",
					"<center>$delete</center>"
					)
				);		
		

		}

	echo json_encode($data);		
}

function build_icon($ligne,$servername=null){
	$icon="free-web-24.png";
	if($ligne["UseReverseProxy"]){$icon="Firewall-Move-Right-32.png";}
	if($servername=="_default_"){return $icon;}
	if(trim($ligne["resolved_ipaddr"])==null){
		if(!preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $servername)){$icon="warning-panneau-32.png";}
	}	
	return $icon;
	
}


function FreeWebsPopupZarafa(){
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$zpush=imgtootltip("64-localdomain-add.png","{add}:Z-Push","Loadjs('freeweb.edit.php?hostname=&force-groupware=Z-PUSH')");
	$WebApp=imgtootltip("64-localdomain-add.png","{add}:WebApp","Loadjs('freeweb.edit.php?hostname=&force-groupware=WEBAPP')");
	
	$users=new usersMenus();
	if(!$users->Z_PUSH_INSTALLED){$zpush=imgtootltip("64-localdomain-add-grey.png","{not_installed}","");}
	if(!$users->ZARAFA_WEBAPP_INSTALLED){$WebApp=imgtootltip("64-localdomain-add-grey.png","{not_installed}","");}
	$toolbarr="
	
	<table style='width:99%' class=form>
	<tbody>
		<tr>
			<td width=25% align='center'>". imgtootltip("64-localdomain-add.png","{add}:WebAccess","Loadjs('freeweb.edit.php?hostname=&force-groupware=ZARAFA&ForceInstanceZarafaID={$_GET["ForceInstanceZarafaID"]}&t=$t')")."</td>
			<td width=25% align='center'>". imgtootltip("64-localdomain-add.png","{add}:Mobile-access","Loadjs('freeweb.edit.php?hostname=&force-groupware=ZARAFA_MOBILE&ForceInstanceZarafaID={$_GET["ForceInstanceZarafaID"]}&t=$t')")."</td>
			<td width=25% align='center'>". $zpush."</td>
			<td width=25% align='center'>". $WebApp. "</td>
		</tr>
		<tr>
			<td width=25% align='center' style='font-size:13px;font-weight:bold'>WebAccess</td>
			<td width=25% align='center' style='font-size:13px;font-weight:bold'>Mobile-access</td>
			<td width=25% align='center' style='font-size:13px;font-weight:bold'>Z-Push</td>
			<td width=25% align='center' style='font-size:13px;font-weight:bold'>WebApp</td>
		</tr>		
	</tbody>
	</table>";		
		
	echo $tpl->_ENGINE_parse_body($toolbarr);
		
	
}




function div_groupware($text,$enabled){
	$color_orange="#B64B13";
	if($enabled==0){$color_orange="#8C8C8C";}
	
	return $GLOBALS["CLASS_TPL"]->_ENGINE_parse_body("<div style=\"font-size:11px;font-weight:bold;font-style:italic;color:$color_orange;margin:0px;padding:0px\">$text</div>");
	}

function FreeWebsEnableSite(){
	$servername=$_POST["FreeWebsEnableSite"];
	$frr=new freeweb($servername);
	$frr->EnableDisableSwitch();
	
}
