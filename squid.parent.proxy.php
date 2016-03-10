<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
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
	
	
	
	if(isset($_GET["move-item-js"])){move_items_js();exit;}
	if(isset($_POST["move-item"])){move_items();exit;}
	if(isset($_POST["enable-item"])){enable_save();exit;}
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["parent-js"])){parent_js();exit;}
	if(isset($_GET["enable-js"])){enable_js();exit;}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["edit-proxy-parent"])){parent_tab();exit;}
	if(isset($_GET["parent-delete-js"])){parent_delete_js();exit;}
	
	
	if(isset($_GET["edit-proxy-parent-popup"])){parent_config();exit;}
	if(isset($_GET["SaveParentProxy"])){parent_save();exit;}
	
	if(isset($_GET["edit-proxy-parent-optionslist"])){parent_options_table();exit;}
	
	if(isset($_GET["edit-proxy-parent-options"])){parent_options_popup();exit;}
	if(isset($_GET["edit-proxy-parent-options-explain"])){parent_options_explain();exit;}
	if(isset($_GET["extract-options"])){extract_options();exit;}
	if(isset($_POST["AddSquidParentOptionOrginal"])){construct_options();exit;}
	if(isset($_POST["DeleteSquidOption"])){delete_options();exit;}
	if(isset($_GET["parent-list"])){popup_list();exit;}
	if(isset($_GET["DeleteSquidParent"])){parent_delete();exit;}
	if(isset($_GET["EnableParentProxy"])){EnableParentProxy();exit;}
	if(isset($_GET["parent-list-options"])){extract_options();exit;}
	
	tabs();
	
function tabs(){
	
	
	$tpl=new templates();
	$page=CurrentPageName();
	$array["status"]='{status}';
	$array["popup"]='{proxy_parents}';
	//$array["popup-ntlm"]='{APP_CNTLM_PARENT}';
	//$array["master"]='{master_proxy}';
	//$array["zipproxy"]='{http_compression}';
	
	$fontsize=22;
	
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
	
		if($num=="caches"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.caches.php?byQuicklinks=yes\" 
					style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
				
		}
		
		
		if($num=="popup-ntlm"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.master-ntlm.php?byQuicklinks=yes\" 
					style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		
		}		

		if($num=="zipproxy"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.zipproxy.php\" 
					style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		
		}		
	
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\" 
				style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
	}
	
	echo build_artica_tabs($html, "main_squid_prents_tabs",1490);
	
	
	
}

function enable_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	header("content-type: application/x-javascript");
	$html="
	var xSave$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){ alert(results); return; }
		$('#flexRT{$_GET["t"]}').flexReload();
	}
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('enable-item','{$_GET["ID"]}');
		XHR.appendData('t','{$_GET["t"]}');
		XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
	
Save$t();
";
echo $html;	
	
}

function move_items_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	header("content-type: application/x-javascript");
	$html="
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){ alert(results); return; }
	$('#flexRT{$_GET["t"]}').flexReload();
}
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('move-item','{$_GET["ID"]}');
	XHR.appendData('t','{$_GET["t"]}');
	XHR.appendData('dir','{$_GET["dir"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

Save$t();
";

	echo $html;

}

function enable_save(){
	$q=new mysql();
	$ID=$_POST["enable-item"];
	$t=$_POST["t"];
	$q=new mysql();
	$sql="SELECT enabled FROM squid_parents WHERE ID=$ID";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$enabled=$ligne["enabled"];
	if($enabled==0){$enabled=1;}else{$enabled=0;}
	$q->QUERY_SQL("UPDATE squid_parents SET enabled=$enabled WHERE ID=$ID","artica_backup");
	if(!$q->ok){echo "$q->mysql_error\n$sql";return;}
}

function move_items(){
	$q=new mysql();
	$ID=$_POST["move-item"];
	$t=$_POST["t"];
	$dir=$_POST["dir"];
	
	if(!$q->FIELD_EXISTS("squid_parents", "zOrder", "artica_backup")){
		$sql="ALTER TABLE `squid_parents` ADD `zOrder` SMALLINT( 20 ) NOT NULL DEFAULT '1',ADD INDEX ( `zOrder` )";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo "$q->mysql_error\n$sql";return;}
	}
	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT zOrder FROM squid_parents WHERE ID='$ID'","artica_backup"));
	if(!$q->ok){echo $q->mysql_error;}

	
	$CurrentOrder=$ligne["zOrder"];

	if($dir==0){
		$NextOrder=$CurrentOrder-1;
	}else{
		$NextOrder=$CurrentOrder+1;
	}

	$sql="UPDATE squid_parents SET weight=$CurrentOrder WHERE zOrder='$NextOrder'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}


	$sql="UPDATE squid_parents SET weight=$NextOrder WHERE ID='$ID'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}

	$results=$q->QUERY_SQL("SELECT ID FROM squid_parents ORDER by weight","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	$c=1;
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$sql="UPDATE squid_parents SET weight=$c WHERE ID='$ID'";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;}
		$c++;
	}


}


function parent_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	header("content-type: application/x-javascript");
	$title=$tpl->javascript_parse_text("{add_a_parent_proxy}");
	
	if($ID>0){
		$q=new mysql();
		$sql="SELECT servername FROM squid_parents WHERE ID=$ID";
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$title=$ligne["servername"];
	}
	
	
	echo "YahooWin4('750','$page?edit-proxy-parent=$ID&t={$_GET["t"]}','$title');";
	
}


function parent_delete_js(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	header("content-type: application/x-javascript");
	$title=$tpl->javascript_parse_text("{add_a_parent_proxy}");
	$ID=$_GET["ID"];
	$t=time();
	if($ID>0){
		$q=new mysql();
		$sql="SELECT servername FROM squid_parents WHERE ID=$ID";
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$title=$tpl->javascript_parse_text("{delete} {$ligne["servername"]} ?");
	}
echo "
var x_DeleteSquidParent$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	$('#flexRT{$_GET["t"]}').flexReload();
	$('#'+document.getElementById('proxy-parent-flexigrid').value).flexReload();
}
	
	
function DeleteSquidParent$t(){
	if(!confirm('$title')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('DeleteSquidParent','$ID');
	XHR.sendAndLoad('$page', 'GET',x_DeleteSquidParent$t);
	}	
	
DeleteSquidParent$t();";
	
}


function parent_tab(){
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["edit-proxy-parent"];
	$title=$tpl->javascript_parse_text("{add_a_parent_proxy}");
	
	if($ID>0){
		$q=new mysql();
		$sql="SELECT server_type,servername FROM squid_parents WHERE ID=$ID";
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$title=$ligne["servername"];
	}
	
	$array["popup"]=$title;
	
	if($ID>0){
		
		if($ligne["server_type"]=="wancompress"){
			$array["wancompress"]="{cache_settings}";
			
		}
		
		$array["optionslist"]="{options}";
		
	}
	
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
	
		if($num=="wancompress"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.parent.proxy.wancompress.php?ID=$ID&t={$_GET["t"]}\"
			style='font-size:20px'><span>$ligne</span></a></li>\n");
			continue;
		}
	
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?edit-proxy-parent-$num=$ID&t={$_GET["t"]}\" 
		style='font-size:20px'><span>$ligne</span></a></li>\n");
	}
	
	echo build_artica_tabs($html, "main_squid_prents_tabs_$ID");	
	
	
	
}


	
function js(){
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{squid_parent_proxy}");
	$title2=$tpl->_ENGINE_parse_body("{edit_squid_parent_parameters}");
	$title3=$tpl->_ENGINE_parse_body("{squid_parent_options}");
	$html="
	var PPROXYMEM=0;
	
	
		function SquidParentProxyStart(){
			YahooWin3('650','$page?popup=yes&t=$t','$title');
		
		}
function ExtractSquidOptions(){
	YahooWin5('500','$page?edit-proxy-parent-options='+document.getElementById('SquidParentOptions').value,'$title3');
}
		
		
		var x_AddSquidOption= function (obj) {
			var results=obj.responseText;
			if(results.length>0){
				document.getElementById('SquidParentOptions').value=results;
				RemplitLesOptionsParent();
				YahooWin5Hide();
			}
			
		}			
		

		

		
		function RemplitLesOptionsParent(){
			LoadAjax('squid_parents_options_list','$page?extract-options='+document.getElementById('SquidParentOptions').value);
		}
		
		function RefreshParentList(){
			$('#flexRT$t').flexReload();
			
		}
	";
		
		echo $html;

}

function parent_delete(){
	$ID=$_GET["DeleteSquidParent"];
	
	$sql="DELETE FROM squid_parents WHERE ID=$ID";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	

	
}

function parent_save(){
	
	
	
	
	$ID=$_GET["ID"];
	if(strlen(trim($_GET["icp_port"]))==null){$_GET["icp_port"]=0;}
	$sql_add="INSERT INTO squid_parents (servername,server_port,server_type,icp_port,local_port)
	VALUES('{$_GET["servername"]}','{$_GET["server_port"]}','{$_GET["server_type"]}','{$_GET["icp_port"]}',
	'{$_GET["local_port"]}')";
	
	$sql_edit="UPDATE squid_parents SET 
		servername='{$_GET["servername"]}',
		server_port='{$_GET["server_port"]}',
		server_type='{$_GET["server_type"]}',
		icp_port='{$_GET["icp_port"]}',
		local_port='{$_GET["local_port"]}',
		weight='{$_GET["weight"]}',
		zOrder='{$_GET["weight"]}'
		WHERE ID=$ID";
	
	
	$q=new mysql();
	
	if(!$q->FIELD_EXISTS("squid_parents", "zOrder", "artica_backup")){
		$sql="ALTER TABLE `squid_parents` ADD `zOrder` SMALLINT( 20 ) NOT NULL DEFAULT '1',ADD INDEX ( `zOrder` )";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo "$q->mysql_error\n$sql";}
	}
	
	if(!$q->FIELD_EXISTS("squid_parents", "local_port","artica_backup")){
		$q->QUERY_SQL("ALTER TABLE `squid_parents` ADD `local_port` smallint(5) NOT NULL DEFAULT '0'","artica_backup");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	
	
	if(!$q->FIELD_EXISTS("squid_parents", "weight", "artica_backup")){
		$sql="ALTER TABLE `squid_parents` ADD `weight` SMALLINT( 20 ) NOT NULL DEFAULT '1',ADD INDEX ( `weight` )";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo "$q->mysql_error\n$sql";}
	}	
	if(!$q->FIELD_EXISTS("squid_parents", "WanProxyMemory", "artica_backup")){
		$q->QUERY_SQL("ALTER TABLE `squid_parents` ADD `WanProxyMemory` SMALLINT(10) NOT NULL DEFAULT '256'", "artica_backup");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	if(!$q->FIELD_EXISTS("squid_parents", "WanProxyCache", "artica_backup")){
		$q->QUERY_SQL("ALTER TABLE `squid_parents` ADD `WanProxyCache` SMALLINT(10) NOT NULL DEFAULT '1'", "artica_backup");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	
	
	$sql=$sql_add;
	if($ID>0){$sql=$sql_edit;}
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){ echo $q->mysql_error."\n$sql"; return; }
}

function status(){
	$squid=new squidbee();
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	
	$EnableParentProxy=intval($sock->GET_INFO("EnableParentProxy"));
	$p=Paragraphe_switch_img("{enable_squid_parent}", "{EnableParentProxy_explain}","EnableParentProxy",$EnableParentProxy,null,1095);
	$p1=Paragraphe_switch_img("{prefer_direct}", "{squid_prefer_direct}","prefer_direct",$squid->prefer_direct,null,1095);
	$p2=Paragraphe_switch_img("{nonhierarchical_direct}", "{squid_nonhierarchical_direct}","nonhierarchical_direct",$squid->nonhierarchical_direct,null,1095);
	
	
	$arrayParams["on"]="{enabled}";
	$arrayParams["off"]="{unknown}";
	$arrayParams["transparent"]="{disabled}";
	$arrayParams["delete"]="{anonymous}";
	$arrayParams["truncate"]="{hide}";
	
	$html="
	<div class=form style='width:95%'>
		<table style='width:100%'>
			<tr>
			<td colspan=2>$p</td>
			</tr>
			<tr>
			<td colspan=2>$p1</td>
			</tr>
			<tr>			
			<td colspan=2>$p2</td>
			</tr>
			<tr>			
			<tr>
				<td class=legend style='font-size:24px;font-weight:bolder'>". texttooltip("x-Forwarded-For","{x-Forwarded-For_explain}").":</td>
				<td>". Field_array_Hash($arrayParams,"x-Forwarded-For-$t",$squid->forwarded_for,null,null,0,"font-size:24px")."</td>
			</tr>
		<tr>			
			
			<tr>			
			<td align='right' colspan=2><hr>". button("{apply}","Save$t()",40)."</td>
			</tr>
		</table>
	</div>
<script>

var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	Loadjs('squid.compile.php');
}


function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('EnableParentProxy',document.getElementById('EnableParentProxy').value);
	XHR.appendData('prefer_direct',document.getElementById('prefer_direct').value);
	XHR.appendData('nonhierarchical_direct',document.getElementById('nonhierarchical_direct').value);
	XHR.appendData('forwarded_for',document.getElementById('x-Forwarded-For-$t').value);
	XHR.sendAndLoad('$page', 'GET',xSave$t);
}		
</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}


function popup(){
	$ASBROWSER=false;
	$squid=new squidbee();
	$page=CurrentPageName();
	$tpl=new templates();
	$servername=$tpl->_ENGINE_parse_body("{hostname}");
	$listen_port=$tpl->_ENGINE_parse_body("{listen_port}");
	$server_type=$tpl->_ENGINE_parse_body("{server_type}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$add_a_parent_proxy=$tpl->_ENGINE_parse_body("{add_a_parent_proxy}");
	$enable_squid_parent=$tpl->_ENGINE_parse_body("{enable_squid_parent}");
	$apply_params=$tpl->_ENGINE_parse_body("{apply}");
	$status=$tpl->_ENGINE_parse_body("{status}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$select=$tpl->javascript_parse_text("{select}");
	$delete=$tpl->javascript_parse_text("{delete}");
	if(isset($_GET["t"])){ $t=$_GET["t"]; }
	if(!is_numeric($t)){$t=time();}
	
	$servername_width=424;
	
	if(isset($_GET["browser"])){
		$browser_uri="&browser=yes&callback={$_GET["callback"]}";
		$ASBROWSER=true;
		$servername_width=343;
	}
	
	
	if(!$ASBROWSER){$display[]="{display: '<span style=font-size:18px>$status</span>', name : 'icon', width :110, sortable : true, align: 'center'}";}
	if($ASBROWSER){$display[]="{display: '&nbsp;', name : 'icon', width :75, sortable : true, align: 'center'}";}
	$display[]="{display: '<span style=font-size:18px>$servername</span>', name : 'servername', width :$servername_width, sortable : true, align: 'left'}";
	$display[]="{display: '<span style=font-size:18px>$listen_port</span>', name : 'server_port', width : 175, sortable : true, align: 'center'}";
	$display[]="{display: '<span style=font-size:18px>$server_type</span>', name : 'server_type', width : 193, sortable : true, align: 'center'}";
	if(!$ASBROWSER){
		$display[]="{display: '<span style=font-size:18px>$enabled</span>', name : 'enabled', width : 110, sortable : true, align: 'center'}";
		$display[]="{display: '&nbsp;', name : '<span style=font-size:18px>zOrder', width : 50, sortable : true, align: 'center'}";
		$display[]="{display: '&nbsp;', name : '<span style=font-size:18px>down', width : 50, sortable : false, align: 'center'}";
		$display[]="{display: '<span style=font-size:18px>$delete</span>', name : 'delete', width : 110, sortable : false, align: 'center'}";
	}
	if($ASBROWSER){
		$display[]="{display: '<span style=font-size:18px>$select</span>', name : 'enabled', width : 50, sortable : false, align: 'center'}";
		$display[]="{display: '<span style=font-size:18px>$delete</span>', name : 'delete', width : 50, sortable : false, align: 'center'}";
	}
	
$html="
<input type='hidden' id='proxy-parent-flexigrid' value='flexRT$t'>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
</div>
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?parent-list=yes&t=$t$browser_uri',
	dataType: 'json',
	colModel : [
		".@implode(",\n", $display)."		
		],
		
buttons : [
		{name: '<strong style=font-size:18px>$add_a_parent_proxy</strong>', bclass: 'add', onpress : add_a_parent_proxy},
		{name: '<strong style=font-size:18px>$apply_params</strong>', bclass: 'apply', onpress : SquidBuildNow$t},
		],			
	
	searchitems : [
		{display: '$servername', name : 'servername'},
		],
	sortname: 'weight',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});
	function SquidBuildNow$t(){
		Loadjs('squid.compile.php');
	}

function add_a_parent_proxy(){
	Loadjs('$page?parent-js=yes&ID=0&t=$t');
	
}

</script>
";	
	echo $tpl->_ENGINE_parse_body($html);
}

function popup_list(){
	$tpl=new templates();
	$t=$_GET["t"];
	$MyPage=CurrentPageName();
	$users=new usersMenus();
	$q=new mysql();
	$squid=new squidbee();
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$ASBROWSER=false;
	if(isset($_GET["browser"])){
		$ASBROWSER=true;
	}
	
	
	if(!$q->FIELD_EXISTS("squid_parents", "weight", "artica_backup")){
		$sql="ALTER TABLE `squid_parents` ADD `weight` SMALLINT( 20 ) NOT NULL DEFAULT '1',ADD INDEX ( `weight` )";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo "$q->mysql_error\n$sql";return;}
	}
	
	$search='%';
	$table="squid_parents";
	$MySQLbase="artica_backup";
	$page=1;
	$ORDER="ORDER BY zOrder";
	
	$total=0;
	if($q->COUNT_ROWS($table,$MySQLbase)==0){json_error_show("no data");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$MySQLbase));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$MySQLbase));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	if(!is_numeric($rp)){$rp=50;}

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";	
	
	$results = $q->QUERY_SQL($sql,$MySQLbase);

	$ArticaSquidParameters=$sock->GET_INFO('ArticaSquidParameters');
	$ini->loadString($sock->GET_INFO('ArticaSquidParameters'));
	$EnableParentProxy=intval($ini->_params["NETWORK"]["EnableParentProxy"]);
	$EnableParentProxy2=intval($sock->GET_INFO("EnableParentProxy"));
	if($EnableParentProxy2==1){$EnableParentProxy=1;}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){ json_error_show("$q->mysql_error"); }	
	
	if(mysql_num_rows($results)==0){json_error_show("no data");}
	
	//if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	$domains=$tpl->_ENGINE_parse_body("{domains}");
	$all=$tpl->_ENGINE_parse_body("{all}");
	
	$cacheFile="/usr/share/artica-postfix/ressources/logs/web/squid.peers.db";
	$STATUS=unserialize(@file_get_contents($cacheFile));
	
	if($GLOBALS["VERBOSE"]){print_r($STATUS);}
	
	$fetchesWord=$tpl->_ENGINE_parse_body("{fetches}");
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		
		$fetches=null;
		$options=null;
		$domainsInfos=null;
		$arrayT=array();
		$img="48-server.png";
		$Check="check-48.png";
		
		$STATUS_KEY=$ligne["servername"];
		if($GLOBALS["VERBOSE"]){echo "<H3>STATUS_KEY = $STATUS_KEY</H3>\n";}
		
		
		if(!isset($STATUS[$STATUS_KEY]["STATUS"])){
			if(isset($STATUS["Peer{$ligne["ID"]}"])){
				$STATUS_KEY="Peer{$ligne["ID"]}";
			}
		}
		
		if($GLOBALS["VERBOSE"]){echo "<H3>STATUS_KEY = $STATUS_KEY</H3>\n";}
		
		if(is_numeric($STATUS[$STATUS_KEY]["FETCHES"])){
			$fetches="<span style='font-size:18px'>($fetchesWord: ". FormatNumber($STATUS[$STATUS_KEY]["FETCHES"]).")</span>";
		}
		
		if(!$ASBROWSER){
			if(!isset($STATUS[$STATUS_KEY]["STATUS"])){
				$img="42-server-grey.png";
				$STATUS[$STATUS_KEY]["STATUS"]="{unknown}";
			}else{
				if($STATUS[$STATUS_KEY]["STATUS"]=="Down"){
					$STATUS[$STATUS_KEY]["STATUS"]="{stopped}";
					$img="42-server-red.png";
				}else{
					$STATUS[$STATUS_KEY]["STATUS"]="{running}";
					$img="48-server.png";
				}
			}
			
			
			if($EnableParentProxy==0){
				$color="#CACACA";
				$img="42-server-grey.png";
				$STATUS[$STATUS_KEY]["STATUS"]="{disabled}";
			}
		}

		
		if($ligne["icp_port"]>0){$ligne["server_port"]=$ligne["server_port"]."/".$ligne["icp_port"];}
		
		if(!$ASBROWSER){
			$ligne3=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(md5) as tcount FROM cache_peer_domain WHERE `servername`='{$ligne["servername"]}'","artica_backup"));
			$countDeDomains=$ligne3["tcount"];
			if($countDeDomains==0){$countDeDomains=$all;}
		}
		
		$up=imgsimple("arrow-up-42.png",null,"Loadjs('$MyPage?move-item-js=yes&ID={$ligne["ID"]}&dir=0&t={$_GET["t"]}')");
		$down=imgsimple("arrow-down-42.png",null,"Loadjs('$MyPage?move-item-js=yes&ID={$ligne["ID"]}&dir=1&t={$_GET["t"]}')");
		
		if($ligne["enabled"]==0){
			$color="#CACACA";
			$Check="check-48-grey.png";
		}
		
		$arrayT=unserialize(base64_decode($ligne["options"]));
		if(is_array($arrayT)){
			if(count($arrayT)>0){
				$opts=array();
				while (list($num,$val)=each($arrayT)){ $opts[]=$num; }
				if(count($opts)>0){ $options="<br><span style='font-size:18px;font-style:italic;color:$color'>".@implode(", ", $opts)."</span>"; }
			}
		}
		
		$ahref="<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('$MyPage?parent-js=yes&ID={$ligne["ID"]}&t=$t');\"
		style='font-size:22px;text-decoration:underline;font-weight:bold;color:$color'>";
		
		$delete="<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('$MyPage?parent-delete-js=yes&ID={$ligne["ID"]}&t=$t');\">
		<img src='img/delete-42.png' style='border:0px;color:$color'>
		</a>";
		
		if(!$ASBROWSER){
		$domainsInfos="&nbsp;<a href=\"javascript:Loadjs('squid.cache_peer_domain.php?servername={$ligne["servername"]}&t=$t')\" 
				style='font-weight:bold;color:$color;text-decoration:underline;font-size:18px'>($countDeDomains $domains)</a>";
		}
		
		$enabled=imgsimple($Check,null,"Loadjs('$MyPage?enable-js=yes&ID={$ligne["ID"]}&t=$t')");
		if($ASBROWSER){
			$enabled="<div style='margin-top:4px'>".imgsimple("arrow-blue-left-32.png",null,"{$_GET["callback"]}({$ligne["ID"]})")."</div>";
		}
		
		$STATUS[$STATUS_KEY]["STATUS"]=$tpl->_ENGINE_parse_body($STATUS[$STATUS_KEY]["STATUS"]);
		
		$server_type=$ligne["server_type"];
		if($server_type=="wancompress"){
			$explainadd=$tpl->_ENGINE_parse_body("<br><span style='font-size:18px'>{forward_to_port}:127.0.0.1:{$ligne["local_port"]}&nbsp;</span>");
			if(!$users->WANPROXY){
				$explainadd=$tpl->_ENGINE_parse_body("<br><span style='font-size:18px;color:#d32d2d'>{wanproxy_not_installed}</span><br>");
			}
			
		}
		
		$CELLS=array();
		$CELLS[]="<center><img src='img/$img'><br><span style='font-size:18px;color:$color'>{$STATUS[$STATUS_KEY]["STATUS"]}</span></center>";
		$CELLS[]="$ahref{$ligne["servername"]}</a>$explainadd$domainsInfos$options";
		$CELLS[]="$ahref{$ligne["server_port"]}</a>";
		$CELLS[]="$ahref{$ligne["server_type"]}</a>";
		$CELLS[]="<center>$enabled</center>";
		
		
		if(!$ASBROWSER){
			
			$CELLS[]="<center>$up</center>";
			$CELLS[]="<center>$down</center>";
			
		}
		$CELLS[]="<center>$delete</center>";
		
		
		
	$data['rows'][] = array(
		'id' =>"PPROXY-". $ligne['ID'],
		'cell' => $CELLS
		);
	}
	
	
echo json_encode($data);	
}






function parent_config(){
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["edit-proxy-parent-popup"];
	$array_type["parent"]="parent";
	$array_type["sibling"]="sibling";
	$array_type["multicast"]="multicast";
	$array_type["wancompress"]="WAN Compressor";
	$q=new mysql();
	$sql="SELECT * FROM squid_parents WHERE ID=$ID";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$button="{apply}";
	$addoptions=$tpl->_ENGINE_parse_body("{squid_parent_options}");
	$add=$tpl->_ENGINE_parse_body("{add}");
	$t=time();
	if($ID<1){$button="{add}";$addoptions=null;}
	if(strlen(trim($ligne["icp_port"]))==0){$ligne["icp_port"]=0;}
	$options=$tpl->_ENGINE_parse_body("{options}");
	
	if(!is_numeric($ligne["weight"])){$ligne["weight"]=1;}
	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
	
	$html="
	
	<input type='hidden' id='SquidParentOptions' name='SquidParentOptions' value=\"{$ligne["options"]}\">
	<div id='EditSquidParentSaveID'>
	
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:22px'>{enabled}:</td>
		<td>". Field_checkbox_design("enabled-$t",1,$ligne["enabled"],"Enabled$t()")."</td>
		<td>&nbsp;</td>
	</tr>
					
	
	<tr>
		<td class=legend style='font-size:22px'>{hostname}:</td>
		<td>". Field_text("servername",$ligne["servername"],"font-size:22px;padding:3px;width:320px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{listen_port}:</td>
		<td>". Field_text("server_port",$ligne["server_port"],"font-size:22px;padding:3px;width:110px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{server_type}","{squid_parent_sibling_how_to}").":</td>
		<td>". Field_array_Hash($array_type,"server_type",$ligne["server_type"],"ServerTypeCheck()",null,0,"font-size:22px")."</td>
	</tr>								
	<tr>
		<td class=legend style='font-size:22px'>{weight}:</td>
		<td>". Field_text("weight-$t",$ligne["weight"],"font-size:22px;padding:3px;width:110px")."</td>
		<td>&nbsp;</td>
	</tr>				

	<tr>
		<td class=legend style='font-size:22px'>{local_port}:</td>
		<td>". Field_text("local_port",$ligne["local_port"],"font-size:22px;padding:3px;width:110px")."</td>
		<td>&nbsp;</td>
	</tr>				
				
				
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{icp_port}","{icp_port_explain}").":</td>
		<td>". Field_text("icp_port",$ligne["icp_port"],"font-size:22px;padding:3px;width:110px")."</td>
		<td></td>
	</tr>	

	<tr>
	
		<td colspan=3 align='right'><hr>". button("$button","EditSquidParentSave$t()",30)."
	</td>
	
	</table>
</div>
	<script>
var xEditSquidParentSave$t= function (obj) {
	var results=obj.responseText;
	var ID='$ID';
	if(results.length>0){alert(results);}
	
	if(document.getElementById('proxy-parent-flexigrid')){
		$('#'+document.getElementById('proxy-parent-flexigrid').value).flexReload();
	}
	$('#flexRT{$_GET["t"]}').flexReload();
	if(ID==0){ YahooWin4Hide(); }
}			
		
function EditSquidParentSave$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ID','$ID');
	XHR.appendData('SaveParentProxy','$ID');
	
	XHR.appendData('servername',document.getElementById('servername').value);
	XHR.appendData('server_port',document.getElementById('server_port').value);
	XHR.appendData('server_type',document.getElementById('server_type').value);
	XHR.appendData('weight',document.getElementById('weight-$t').value);
	XHR.appendData('icp_port',document.getElementById('icp_port').value);
	XHR.appendData('local_port',document.getElementById('local_port').value);
	XHR.appendData('enabled',document.getElementById('enabled-$t').value);
	XHR.sendAndLoad('$page', 'GET',xEditSquidParentSave$t);			
}	

function Enabled$t(){
	document.getElementById('servername').disabled=true;
	document.getElementById('local_port').disabled=true;
	document.getElementById('server_port').disabled=true;
	document.getElementById('server_type').disabled=true;
	document.getElementById('weight-$t').disabled=true;
	document.getElementById('icp_port').disabled=true;
	if(document.getElementById('enabled-$t').checked){
		document.getElementById('servername').disabled=false;
		document.getElementById('server_port').disabled=false;
		document.getElementById('server_type').disabled=false;
		document.getElementById('weight-$t').disabled=false;
		document.getElementById('icp_port').disabled=false;	
	}
}

function ServerTypeCheck(){
	document.getElementById('icp_port').disabled=false;
	document.getElementById('local_port').disabled=true;
	var wancompress=document.getElementById('server_type').value;
	if(wancompress=='wancompress'){
		document.getElementById('local_port').disabled=false;
		document.getElementById('icp_port').disabled=true;
	
	}
	
}

 Enabled$t();
 ServerTypeCheck();
</script>
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function parent_options_table(){
	$squid=new squidbee();
	$page=CurrentPageName();
	$tpl=new templates();
	$servername=$tpl->_ENGINE_parse_body("{hostname}");
	$listen_port=$tpl->_ENGINE_parse_body("{listen_port}");
	$server_type=$tpl->_ENGINE_parse_body("{server_type}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$options=$tpl->_ENGINE_parse_body("{options}");
	$new_option=$tpl->_ENGINE_parse_body("{new_option}");
	$apply_params=$tpl->_ENGINE_parse_body("{apply}");
	$title=$tpl->javascript_parse_text("{proxy_parent_options}");
	$t=time();
	$ID=$_GET["edit-proxy-parent-optionslist"];
	
$html="			

<table class='parent-options-$t' style='display: none' id='parent-options-$t' style='width:100%'></table>
<script>
var rowmem='';
$(document).ready(function(){
$('#parent-options-$t').flexigrid({
	url: '$page?parent-list-options=yes&t=$t&ID=$ID',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'none', width :48, sortable : true, align: 'center'},
		{display: '$options', name : 'server_port', width : 491, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 48, sortable : false, align: 'center'}

		],
		
buttons : [
		{name: '<strong style=font-size:18px>$new_option</strong>', bclass: 'add', onpress : add_a_parent_option},
		],			
	

	sortname: 'servername',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:22px>$title</span>',
	useRp: false,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 300,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});
function add_a_parent_option(){ 
	YahooWin5('650','$page?edit-proxy-parent-options=yes&ID=$ID&t=$t','$new_option');
}
		
var x_AddSquidOption$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	$('#row'+rowmem).remove();
	$('#parent-options-$t').flexReload();
	if(document.getElementById('proxy-parent-flexigrid')){ $('#'+document.getElementById('proxy-parent-flexigrid').value).flexReload(); }
	
}		

function DeleteSquidOption(key,ID){
	var rowmem=ID;
	var XHR = new XHRConnection();
	XHR.appendData('DeleteSquidOption',key);
	XHR.appendData('ID',$ID);
	XHR.sendAndLoad('$page', 'POST',x_AddSquidOption$t);
}
</script>
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);		
	
}



function parent_options_popup(){
	$tt=time();
	$t=$_GET["t"];
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$array=unserialize(base64_decode($_GET["edit-proxy-parent-options"]));
	$options[null]="{select}";
	$options[base64_encode("proxy-only")]="proxy-only";
	$options[base64_encode("Weight=n")]="Weight=n";
	$options[base64_encode("ttl=n")]="ttl=n";
	$options[base64_encode("no-query")]="no-query";
	$options[base64_encode("default")]="default";
	$options[base64_encode("round-robin")]="round-robin";
	$options[base64_encode("multicast-responder")]="multicast-responder";
	$options[base64_encode("closest-only")]="closest-only";
	$options[base64_encode("no-digest")]="no-digest";
	$options[base64_encode("no-tproxy")]="no-tproxy";
	
	$options[base64_encode("no-netdb-exchange")]="no-netdb-exchange";
	$options[base64_encode("no-delay")]="no-delay";
	$options[base64_encode("login=user:password")]="login=user:password";
	$options[base64_encode("login=PASSTHRU")]="login=PASSTHRU";
	$options[base64_encode("login=PASS")]="login=PASS";
	$options[base64_encode("connect-timeout=nn")]="connect-timeout=nn";
	$options[base64_encode("digest-url=url")]="digest-url=url";

	
	
	//$options[base64_encode("ssl")]="ssl";
	
	$html="
	<table style='width:100%'>
	<tr>	
		<td class=legend style='font-size:16px'>{squid_parent_options}:</td>
		<td>". Field_array_Hash($options,"squid_parent_options_f",base64_encode("proxy-only"),"FillSquidParentOptions$tt()",null,0,
		"font-size:16px;padding:5px")."</td>
	</tr>
	</table>
	<div id='squid_parent_options_filled'></div>
	<script>
	
	function FillSquidParentOptions$tt(){
			var selected=document.getElementById('squid_parent_options_f').value
			LoadAjax('squid_parent_options_filled','$page?edit-proxy-parent-options-explain='+selected+'&ID=$ID&tt=$tt');
		}
		
		var x_AddSquidOption$tt= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			YahooWin5Hide();
			$('#parent-options-$t').flexReload();
			if(document.getElementById('proxy-parent-flexigrid')){ $('#'+document.getElementById('proxy-parent-flexigrid').value).flexReload(); }
		}		
	
	
		function AddSquidOption$tt(){
			var XHR = new XHRConnection();
			XHR.appendData('AddSquidParentOptionOrginal',document.getElementById('SquidParentOptions').value);
			XHR.appendData('key',document.getElementById('squid_parent_options_f').value);
			XHR.appendData('ID',$ID);
			if(document.getElementById('parent_proxy_add_value')){
				XHR.appendData('value',encodeURIComponent(document.getElementById('parent_proxy_add_value').value));
			}
			
			XHR.sendAndLoad('$page', 'POST',x_AddSquidOption$tt);
		}
	
	
		FillSquidParentOptions$tt();
	</script>
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}

function parent_option_explain_text($key){
	$options[base64_encode("proxy-only")]="{parent_options_proxy_only}";
	$options[base64_encode("Weight=n")]="{parent_options_proxy_weight}";
	$options[base64_encode("ttl=n")]="{parent_options_proxy_ttl}";
	$options[base64_encode("no-query")]="{parent_options_proxy_no_query}";
	$options[base64_encode("default")]="{parent_options_proxy_default}";
	$options[base64_encode("round-robin")]="{parent_options_proxy_round_robin}";
	$options[base64_encode("multicast-responder")]="{parent_options_proxy_multicast_responder}";
	$options[base64_encode("closest-only")]="{parent_options_proxy_closest_only}";
	$options[base64_encode("no-digest")]="{parent_options_proxy_no_digest}";
	$options[base64_encode("no-netdb-exchange")]="{parent_options_proxy_no_netdb_exchange}";
	$options[base64_encode("no-delay")]="{parent_options_proxy_no_delay}";
	$options[base64_encode("login=user:password")]="{parent_options_proxy_login}";
	$options[base64_encode("login=PASSTHRU")]="{parent_options_login_passthru}";
	$options[base64_encode("login=PASS")]="{parent_options_login_pass}";
	$options[base64_encode("connect-timeout=nn")]="{parent_options_proxy_connect_timeout}";
	$options[base64_encode("digest-url=url")]="{parent_options_proxy_digest_url}";
	$options[base64_encode("no-tproxy")]="{parent_options_no_tproxy}";
	
	return $options[$key];
}



function parent_options_explain(){
	$tt=$_GET["tt"];
	if($_GET["edit-proxy-parent-options-explain"]==null){return null;}
	$page=CurrentPageName();


	
	$options_forms[base64_encode("digest-url=url")]=true;
	$options_forms[base64_encode("connect-timeout=nn")]=true;
	$options_forms[base64_encode("ttl=n")]=true;
	$options_forms[base64_encode("Weight=n")]=true;
	$options_forms[base64_encode("login=user:password")]=true;
	
	
	if($options_forms[$_GET["edit-proxy-parent-options-explain"]]){
		$form="
		<table style='width:99%' class=form>
		<tr>
			<td class=legend style='font-size:16px'>". base64_decode($_GET["edit-proxy-parent-options-explain"]).":</td>
			<td>". Field_text("parent_proxy_add_value",null,"font-size:16px;padding:3px")."</td>
		</tr>
		</table>";
		
	}
	
	$explain=parent_option_explain_text($_GET["edit-proxy-parent-options-explain"]);
	$html="<div class=explain style='font-size:16px'>$explain</div>
	$form
	<div style='text-align:right'><hr>
	". button("{add} ".base64_decode($_GET["edit-proxy-parent-options-explain"]),"AddSquidOption$tt()",22)."</div>";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}

function extract_options(){
	$ID=$_GET["ID"];
	$q=new mysql();
	$sql="SELECT options FROM squid_parents WHERE ID={$_GET["ID"]}";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$tpl=new templates();
	$array=unserialize(base64_decode($ligne["options"]));
	if(!is_array($array)){json_error_show("No data");}
	
	
	$data = array();
	$data['page'] = 1;
	
	$data['rows'] = array();
	
		$c=0;
		while (list($num,$val)=each($array)){
			$c++;	
			$md5=md5("PPROXY-OPTION-$ID-$num");
			
			$explain=$tpl->_ENGINE_parse_body(parent_option_explain_text(base64_encode($num)));
			
			$data['rows'][] = array(
					'id' =>"$md5",
					'cell' => array(
							"<img src='img/arrow-blue-left-32.png'>",
							"<strong style='font-size:22px'>$num</strong><div style='font-size:14px;font-weight:normal'>$explain</div>",
							imgsimple("delete-32.png","{delete}","DeleteSquidOption('$num','$md5')") )
					);			
			}
		
	
	
	$data['page'] = 1;
	$data['total'] = $c;
	
	echo json_encode($data);
	
}




function construct_options(){
	$ID=$_POST["ID"];
	$q=new mysql();
	$sql="SELECT options FROM squid_parents WHERE ID={$_POST["ID"]}";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
	$based=unserialize(base64_decode($ligne["options"]));
	$key=base64_decode($_POST["key"]);
	$nopreg=false;
	writelogs("$ID]decoded key:\"$key\"",__FUNCTION__,__FILE__,__LINE__);
	
	if($key=="login=PASSTHRU"){
	 	$nopreg=true;
	}
	
	if($key=="login=PASS"){
		$nopreg=true;
	}	
	
	if(!$nopreg){
		if(preg_match("#(.+?)=#",$key,$re)){
			$key=$re[1];
		}
	}
	

	
	
	if(!is_array($based)){
		$based[$key]=url_decode_special_tool($_POST["value"]);
		writelogs("$ID]send ". serialize($based),__FUNCTION__,__FILE__,__LINE__);
		$NewOptions=base64_encode(serialize($based));
		$q->QUERY_SQL("UPDATE squid_parents SET options='$NewOptions' WHERE ID='$ID'","artica_backup");
		return;
	}
	
	$based[$key]=$_POST["value"];
	
	while (list($num,$val)=each($based)){	
		if(trim($num)==null){continue;}
		$f[$num]=$val;
	}
	
	
	$NewOptions=base64_encode(serialize($f));
	$q->QUERY_SQL("UPDATE squid_parents SET options='$NewOptions' WHERE ID='$ID'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	

	
}

function delete_options(){
	$q=new mysql();
	$sql="SELECT options FROM squid_parents WHERE ID={$_POST["ID"]}";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
	$array=unserialize(base64_decode($ligne["options"]));
	$key=$_POST["DeleteSquidOption"];
	
	writelogs("DELETING $key FOR {$_POST["ID"]}",__FUNCTION__,__FILE__,__LINE__);
	
	if(!is_array($array)){
		writelogs("Not an array...",__FUNCTION__,__FILE__,__LINE__);
		echo "unable to unserialize $array\n";
		$array=array();
		return;
		}
	unset($array[$key]);
	$newarray=base64_encode(serialize($array));	
	$sql="UPDATE squid_parents SET options='$newarray' WHERE ID='{$_POST["ID"]}'";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	
}

function EnableParentProxy(){
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$ArticaSquidParameters=$sock->GET_INFO('ArticaSquidParameters');
	$ini->loadString($ArticaSquidParameters);
	
	if($GLOBALS["VERBOSE"]){
		echo "<span style='color:blue;font-weight:bold'>EnableParentProxy()::". __LINE__."EnableParentProxy:{$_GET["EnableParentProxy"]}</span><br>";
	}
	
	$ini->_params["NETWORK"]["EnableParentProxy"]=$_GET["EnableParentProxy"];
	$ini->_params["NETWORK"]["prefer_direct"]=$_GET["prefer_direct"];
	$ini->_params["NETWORK"]["nonhierarchical_direct"]=$_GET["nonhierarchical_direct"];
	$sock->SET_INFO("ArticaSquidParameters",$ini->toString());
	$sock->SET_INFO("EnableParentProxy", $_GET["EnableParentProxy"]);
	
	$squid=new squidbee();
	$squid->forwarded_for=$_GET["forwarded_for"];
	$squid->SaveToLdap(true);
	
	$sock->getFrameWork("cmd.php?squidnewbee=yes");	
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}



?>