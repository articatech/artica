<?php
if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.ini.inc');
include_once('ressources/class.meta.squid.acls.inc');

$users=new usersMenus();
if(!$users->AsArticaMetaAdmin){$tpl=new templates();echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die();}


if(isset($_GET["acls-list"])){acl_list();exit;}
if(isset($_GET["acl-rule-delete-js"])){acl_rule_delete_js();exit;}
if(isset($_POST["acl-rule-delete"])){acl_rule_delete();exit;}
if(isset($_POST["acl-rule-enable"])){acl_rule_enable();exit;}
if(isset($_POST["acl-rule-move"])){acl_rule_move();exit;}
if(isset($_POST["acl-rule-order"])){acl_rule_order();exit;}

page();

function acl_rule_delete_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	header("content-type: application/x-javascript");
	$t=time();
	$ID=intval($_GET["acl-rule-delete-js"]);
	

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT aclname FROM meta_webfilters_acls WHERE ID='$ID'"));
	$title=utf8_encode($ligne["aclname"]);
	$ask=$tpl->javascript_parse_text("{delete_this_rule} - $title - ?");
	
	$html="
			
var xSave$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#META_PROXY_ACLS_MAIN').flexReload();
}	
	
function Save$t(){
	if(!confirm('$ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('acl-rule-delete', '$ID');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
Save$t();
	
";
	
	echo $html;
}
function acl_rule_delete(){
	
	$ID=$_POST["acl-rule-delete"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM meta_webfilters_sqacllinks WHERE aclid='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM meta_webfilters_acls WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}

}

function page(){
	
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$SquidDebugAcls=intval($sock->GET_INFO("SquidDebugAcls"));
	$description=$tpl->_ENGINE_parse_body("{description}");
	$rule=$tpl->_ENGINE_parse_body("{rule}");
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$groups=$tpl->_ENGINE_parse_body("{proxy_objects}");
	$delete_rule_ask=$tpl->javascript_parse_text("{delete_rule_ask}");
	$apply_params=$tpl->_ENGINE_parse_body("{apply}");
	$options=$tpl->_ENGINE_parse_body("{options}");
	$t=time();
	$order=$tpl->javascript_parse_text("{order}");
	$squid_templates_error=$tpl->javascript_parse_text("{squid_templates_error}");
	$bandwith=$tpl->javascript_parse_text("{bandwith}");
	$session_manager=$tpl->javascript_parse_text("{session_manager}");
	$new_group=$tpl->javascript_parse_text("{new_group_of_rules}");
	
	$session_manager="{name: '$session_manager', bclass: 'clock', onpress : SessionManager$t},";
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	$create_a_snapshot=$tpl->javascript_parse_text("{create_a_snapshot}");
	$delete_all_acls=$tpl->javascript_parse_text("{delete_all_acls}");
	$Table_title=$tpl->javascript_parse_text("{ACLS}");
	
	
	$table_width=905;
	$apply_paramsbt="{separator: true},{name: '<strong style=font-size:18px>$apply_params</strong>', bclass: 'apply', onpress : MetaCompileSquidAcls$t},";
	$optionsbt="{name: '<strong style=font-size:18px>$options</strong>', bclass: 'Settings', onpress : AclOptions$t},";
	
	// removed {name: '$squid_templates_error', bclass: 'Script', onpress : SquidTemplatesErrors$t},
	
	$fields_size=22;
	$aclname_size=363;
	$items_size=682;
	$icon_size=70;
	
	
	
	$html="
	
	<table class='META_PROXY_ACLS_MAIN' style='display: none' id='META_PROXY_ACLS_MAIN' style='width:99%'></table>
	<script>
	var DeleteSquidAclGroupTemp=0;
	function flexigridStart$t(){
	$('#META_PROXY_ACLS_MAIN').flexigrid({
	url: '$page?acls-list=yes&t=$t&toexplainorg=table-$t&t=$t&aclgroup-id={$_GET["aclgroup-id"]}',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:{$fields_size}px>$rule', name : 'aclname', width : $aclname_size, sortable : true, align: 'left'},
	{display: '<span style=font-size:{$fields_size}px>$description</span>', name : 'items', width : $items_size, sortable : false, align: 'left'},
	{display: '', name : 'up', width : $icon_size, sortable : false, align: 'center'},
	{display: '', name : 'xORDER', width : $icon_size, sortable : true, align: 'center'},
	{display: '', name : 'none2', width : $icon_size, sortable : true, align: 'center'},
	{display: '', name : 'none4', width : $icon_size, sortable : false, align: 'center'},
	
	],
	buttons : [
	{name: '<strong style=font-size:18px>$new_rule</strong>', bclass: 'add', onpress : AddAcl},
	$apply_paramsbt
	],
	searchitems : [
	{display: '$rule', name : 'aclname'},
	],
	sortname: 'xORDER',
	sortorder: 'asc',
	usepager: true,
	title: '<strpng style=font-size:30px>$Table_title</strong>',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 620,
	singleSelect: true
	
	});
	}
function AddAcl() {
	Loadjs('artica-meta.proxy.acls.edit.php?js=yes&ID=0');
}
function MetaCompileSquidAcls$t() {
	Loadjs('artica-meta.proxy.acls.progress.php');
}
	
	
function AddAclGroup(){
Loadjs('$page?Addacl-group=yes&ID=-1&t=$t');
	}
	
function SessionManager$t(){
Loadjs('squid.ext_time_quota_acl.php?t=$t')
	}
	
function GroupsSection$t(){
Loadjs('squid.acls.groups.php?js=yes&toexplainorg=table-$t');
	}
	
function BandwithSection$t(){
Loadjs('squid.bandwith.php?by-acls-js=yes&t=$t');
	
	}
	
function AclOptions$t(){
Loadjs('squid.acls.options.php?t=$t');
	}
	
var x_EnableDisableAclRule$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#META_PROXY_ACLS_MAIN').flexReload();
}
	
function AclUpDown(ID,dir,metagroup,metauuid){
	var XHR = new XHRConnection();
	XHR.appendData('acl-rule-move', ID);
	XHR.appendData('acl-rule-dir', dir);
	XHR.appendData('metagroup', metagroup);
	XHR.appendData('metauuid', metauuid);
	XHR.sendAndLoad('$page', 'POST',x_EnableDisableAclRule$t);
	}
	
function ChangeRuleOrder(ID,xdef){
	var neworder=prompt('$order',xdef);
	if(neworder){
		var XHR = new XHRConnection();
		XHR.appendData('acl-rule-order', ID);
		XHR.appendData('acl-rule-value', neworder);
		XHR.sendAndLoad('$page', 'POST',x_EnableDisableAclRule$t);
	}
}
	
function SquidTemplatesErrors$t(){
Loadjs('squid.templates.php');
	}
	
function DeleteAll$t(){
Loadjs('squid.acls.delete.php?t=$t');
	}
	
	
	
var x_DeleteSquidAclGroup= function (obj) {
var res=obj.responseText;
if(res.length>3){alert(res);return;}
if(document.getElementById('main_filter_rule_edit')){RefreshTab('main_filter_rule_edit');}
if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
$('#rowtime'+TimeRuleIDTemp).remove();
	}
	
	
	
var x_SquidBuildNow= function (obj) {
var res=obj.responseText;
if(res.length>3){alert(res);return;}
$('#table-$t').flexReload();
	}
	
	
function SquidBuildNow$t(){
Loadjs('squid.compile.php');
	}
	
var x_DeleteSquidAclRule$t= function (obj) {
var res=obj.responseText;
if(res.length>3){alert(res);return;}
$('#rowacl'+DeleteSquidAclGroupTemp).remove();
	}
	
	
function DeleteSquidAclRule(ID){
DeleteSquidAclGroupTemp=ID;
if(confirm('$delete_rule_ask :'+ID)){
	var XHR = new XHRConnection();
	XHR.appendData('acl-rule-delete', ID);
	XHR.sendAndLoad('$page', 'POST',x_DeleteSquidAclRule$t);
	}
	}
	
	
	
function EnableDisableAclRule$t(ID){
	var XHR = new XHRConnection();
	XHR.appendData('acl-rule-enable', ID);
	if(document.getElementById('aclid_'+ID).checked){XHR.appendData('enable', '1');}else{XHR.appendData('enable', '0');}
	XHR.sendAndLoad('$page', 'POST',x_EnableDisableAclRule$t);
}
	
	function EnableSquidPortsRestrictionsCK(){
var XHR = new XHRConnection();
XHR.appendData('EnableSquidPortsRestrictions', 'yes');
XHR.sendAndLoad('$page', 'POST',x_EnableDisableAclRule$t);
	}
function SquidAllowSmartPhones(){
var XHR = new XHRConnection();
if(document.getElementById('SquidAllowSmartPhones').checked){XHR.appendData('SquidAllowSmartPhones', '1');}else{XHR.appendData('SquidAllowSmartPhones', '0');}
		    XHR.sendAndLoad('$page', 'POST',x_EnableDisableAclRule$t);
		}
	
	
	
		setTimeout('flexigridStart$t()',800);
	
	</script>
	
		";
	
		echo $html;
}	


function acl_rule_move(){
	$q=new mysql_squid_builder();
	$sql="SELECT xORDER FROM meta_webfilters_acls WHERE `ID`='{$_POST["acl-rule-move"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$xORDER_ORG=$ligne["xORDER"];
	$metagroup=intval($ligne["metagroup"]);
	$metauuid=$ligne["metauuid"];

	if($metagroup>0){$add_sqz="AND metagroup=$metagroup";}
	if($metauuid<>null){$add_sqz="AND metauuid='$metauuid'";}


	$xORDER=$xORDER_ORG;
	if($_POST["acl-rule-dir"]==1){$xORDER=$xORDER_ORG-1;}
	if($_POST["acl-rule-dir"]==0){$xORDER=$xORDER_ORG+1;}
	if($xORDER<0){$xORDER=0;}
	$sql="UPDATE meta_webfilters_acls SET xORDER=$xORDER WHERE `ID`='{$_POST["acl-rule-move"]}'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;;return;}
	//echo $sql."\n";

	if($_POST["acl-rule-dir"]==1){
		$xORDER2=$xORDER+1;
		if($xORDER2<0){$xORDER2=0;}
		$sql="UPDATE webfilters_sqacls SET
		xORDER=$xORDER2 WHERE `ID`<>'{$_POST["acl-rule-move"]}'
		AND xORDER=$xORDER $add_sqz";
		$q->QUERY_SQL($sql);
		//echo $sql."\n";

		if(!$q->ok){echo $q->mysql_error;return;}
	}
	if($_POST["acl-rule-dir"]==0){
		$xORDER2=$xORDER-1;
		if($xORDER2<0){$xORDER2=0;}
		$sql="UPDATE meta_webfilters_acls SET xORDER=$xORDER2 WHERE `ID`<>'{$_POST["acl-rule-move"]}'
		AND xORDER=$xORDER  $add_sqz";
		$q->QUERY_SQL($sql);
		//echo $sql."\n";
		if(!$q->ok){echo $q->mysql_error;return;}
	}

	$c=0;
	$sql="SELECT ID FROM meta_webfilters_acls WHERE 1 $add_sqz ORDER BY xORDER";
	$results = $q->QUERY_SQL($sql);

	while ($ligne = mysql_fetch_assoc($results)) {
		$q->QUERY_SQL("UPDATE meta_webfilters_acls SET xORDER=$c WHERE `ID`={$ligne["ID"]}");
		$c++;
	}


}


function acl_rule_enable(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("UPDATE meta_webfilters_acls SET enabled={$_POST["enable"]} WHERE ID={$_POST["acl-rule-enable"]}");
	if(!$q->ok){echo $q->mysql_error;return;}

}
	
function acl_list(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$sock=new sockets();

	$RULEID=$_GET["RULEID"];
	$GROUPE_RULE_ID=$_GET["aclgroup-id"];
	if(!is_numeric($GROUPE_RULE_ID)){$GROUPE_RULE_ID=0;}
	$t=$_GET["t"];
	$search='%';
	$table="meta_webfilters_acls";
	$GROUPE_RULE_ID_NEW_RULE=null;
	$FORCE_FILTER=null;
	$page=1;
	$data = array();
	$data['rows'] = array();
	$sock=new sockets();
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}

	if (isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();


	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}
	
	
	$rp=50;
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";


	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(mysql_num_rows($results)==0){json_error_show("no rule");}
	if(!$q->ok){json_error_show("$q->mysql_error");}


	$font_size=18;
	$data['page'] = $page;
	$data['total'] = $total;


		$c=0;
	
	$order=$tpl->_ENGINE_parse_body("{order}:");
	while ($ligne = mysql_fetch_assoc($results)) {
		$c++;
		$val=0;
		$color="black";
		$disable=Field_checkbox("aclid_{$ligne['ID']}", 1,$ligne["enabled"],"EnableDisableAclRule$t('{$ligne['ID']}')");
		$ligne['aclname']=utf8_encode($ligne['aclname']);
		$delete=imgsimple("delete-42.png",null,"Loadjs('$MyPage?acl-rule-delete-js={$ligne['ID']}')");
		if($ligne["enabled"]==0){$color="#8a8a8a";}

		$httpaccess_value=$ligne["httpaccess_value"];
		$httpaccess_data=$ligne["httpaccess_data"];
		$httpaccess=$ligne["httpaccess"];

		$metagroup=intval($ligne["metagroup"]);
		$metauuid=$ligne["metauuid"];
		
		$up=imgsimple("arrow-up-42.png","","AclUpDown('{$ligne['ID']}',1,$metagroup,'$metauuid')");
		$down=imgsimple("arrow-down-42.png","","AclUpDown('{$ligne['ID']}',0,$metagroup,'$metauuid')");
		$meta_squid_acls=new meta_squid_acls();
		$httpaccess=$meta_squid_acls->explain_this_acl($ligne['ID'], $httpaccess,$ligne["enabled"]);

		$data['rows'][] = array(
					'id' => "acl{$ligne['ID']}",
					'cell' => array("<a href=\"javascript:blur();\"  OnClick=\"javascript:Loadjs('artica-meta.proxy.acls.edit.php?js=yes&ID={$ligne['ID']}');\"
					style='font-size:{$font_size}px;text-decoration:underline;color:$color'>{$ligne['aclname']}</span></A>
					<div style='font-size:14px'><i>$order&laquo;<a href=\"javascript:blur();\"
					Onclick=\"javascript:ChangeRuleOrder({$ligne['ID']},{$ligne["xORDER"]});\"
					style=\"text-decoration:underline\">{$ligne["xORDER"]}</a>&raquo;</i></div>",
					"<span style='font-size:16px;color:$color'>$httpaccess</span>",
					"<center>$up</center>",
					"<center>$down</center>",
					"<center>$disable</center>",
					"<center>$delete</center>")
		);
}

	
	echo json_encode($data);
}