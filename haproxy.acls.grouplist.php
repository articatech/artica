<?php
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.haproxy.inc');
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "<H2>$alert</H2>";
	die();	
}


if(isset($_GET["popup"])){page();exit;}
if(isset($_GET["groups-list"])){group_list();exit;}

if(isset($_GET["delete-group-js"])){delete_group_js();exit;}
if(isset($_POST["delete"])){delete();exit;}

if(isset($_GET["AddGroup-js"])){AddGroup_js();exit;}
if(isset($_GET["EditGroup-popup"])){EditGroup_popup();exit;}
if(isset($_POST["GroupName"])){EditGroup_save();exit;}
if(isset($_POST["DeleteTimeRule"])){EditTimeRule_delete();exit;}
if(isset($_POST["EnableGroup"])){EditGroup_enable();exit;}
if(isset($_POST["DeleteGroup"])){EditGroup_delete();exit;}



if(isset($_GET["items"])){items_js();exit;}
if(isset($_GET["items-list"])){items_list();exit;}
if(isset($_GET["AddItem-js"])){item_popup_js();exit;}
if(isset($_GET["AddItem-popup"])){item_form();exit;}
if(isset($_POST["item-pattern"])){item_save();exit;}
if(isset($_POST["EnableItem"])){item_enable();exit;}
if(isset($_POST["DeleteItem"])){item_delete();exit;}

page();


function delete_group_js(){
	$ID=$_GET["delete-group-js"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM haproxy_acls_groups WHERE ID='$ID'","artica_backup"));
	$t=time();
	$delete=$tpl->javascript_parse_text("{delete} {$ligne["groupname"]} ?");
	echo "
			
	var xSave$t= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#HAPROXY_BROWSE_ACL_GROUPS_TOT').flexReload();	
	}	

function Save$t(){
	if(!confirm('$delete')){return;}
 	var XHR = new XHRConnection();
	XHR.appendData('delete', '$ID');
	 XHR.sendAndLoad('$page', 'POST',xSave$t); 
}
Save$t();";
	
}

function item_popup_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_GET["item-id"];
	if($ID>0){
		$title="{item}:$ID";
	}
	
	if($ID<0){$title="{new_item}";}
	$title=$tpl->_ENGINE_parse_body($title);
	$html="YahooWin3(450,'$page?AddItem-popup=yes&item-id=$ID&ID={$_GET["ID"]}','$title')";
	echo $html;
}

function AddGroup_js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_GET["ID"];
	if(isset($_GET["wpad"])){$wpad="&wpad=yes";}
	if($ID>0){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM haproxy_acls_groups WHERE ID='$ID'"));
		$title="{group}:$ID&nbsp;&raquo;&nbsp;{$ligne["GroupName"]}&nbsp;&raquo;&nbsp;{$GLOBALS["GroupType"][$ligne["GroupType"]]}";
	}else{
		
		$title="{new_item}";
	}
	
	if($ID<0){$title="{new_item}";}
	$title=$tpl->_ENGINE_parse_body($title);
	$html="YahooWinT(450,'$page?EditGroup-popup=yes&ID=$ID&FilterType={$_GET["FilterType"]}$wpad','$title')";
	echo $html;	
	
}


function delete(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_POST["delete"];
	
	$q=new mysql();
	
	$q->QUERY_SQL("DELETE FROM haproxy_acls_items WHERE groupid='$ID'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$q->QUERY_SQL("DELETE FROM haproxy_acls_link WHERE groupid='$ID'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$q->QUERY_SQL("DELETE FROM haproxy_acls_groups WHERE ID='$ID'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}

}

function page(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();	
	$type=$tpl->_ENGINE_parse_body("{type}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$new_group=$tpl->_ENGINE_parse_body("{new_proxy_object}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$delete_group_ask=$tpl->javascript_parse_text("{inputbox delete group}");
	$type=$tpl->_ENGINE_parse_body("{type}");
	$proxy_objects=$tpl->_ENGINE_parse_body("{proxy_objects}");
	$rules=$tpl->_ENGINE_parse_body("{rules}");
	$wpad=null;
	if(isset($_GET["wpad"])){$wpad="&wpad=yes";}
	
	$t=time();	

		$buttons="
	buttons : [
	{name: '<strong style=font-size:22px>$new_group</strong>', bclass: 'add', onpress : AddGroup$t},
	],";

	$html="
	<table class='HAPROXY_BROWSE_ACL_GROUPS_TOT' style='display: none' id='HAPROXY_BROWSE_ACL_GROUPS_TOT' style='width:99%'></table>
<script>
var DeleteSquidAclGroupTemp=0;
$(document).ready(function(){
$('#HAPROXY_BROWSE_ACL_GROUPS_TOT').flexigrid({
	url: '$page?groups-list=yes&callback={$_GET["callback"]}&t=$t&FilterType={$_GET["FilterType"]}$wpad',
	dataType: 'json',
	colModel : [
		{display: '<span style=font-size:22px>$description</span>', name : 'groupname', width : 480, sortable : true, align: 'left'},
		{display: '<span style=font-size:22px>$type</span>', name : 'grouptype', width : 300, sortable : true, align: 'left'},
		{display: '<span style=font-size:22px>$rules</span>', name : 'xxx', width : 400, sortable : false, align: 'left'},
		{display: '<span style=font-size:22px>$items</span>', name : 'items', width : 90, sortable : false, align: 'center'},
		{display: '', name : 'none3', width : 90, sortable : false, align: 'center'},
		
	],
	$buttons
	searchitems : [
		{display: '$description', name : 'groupname'},
		],
	sortname: 'groupname',
	sortorder: 'asc',
	usepager: true,
	title: '<strong style=font-size:22px>$proxy_objects</strong>',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 350,
	singleSelect: true
	
	});   
});
function AddGroup$t() {
	Loadjs('haproxy.acls.group.setting.php?new-group-js=yes&link-acl={$_GET["aclid"]}');
	
}	

function RefreshSquidGroupTable(){

	if(document.getElementById('GLOBAL_SSL_CENTER_ID')){
		$('#'+document.getElementById('GLOBAL_SSL_CENTER_ID').value).flexReload();
	}
	if(document.getElementById('SSL_RULES_GROUPS_ID')){
		$('#'+document.getElementById('SSL_RULES_GROUPS_ID').value).flexReload();
	}	
	
	if(document.getElementById('flexRT-refresh-1')){ 
		$('#'+document.getElementById('flexRT-refresh-1').value).flexReload();
	}
	
	$('#table-$t').flexReload();
	
}


	var x_DeleteSquidAclGroup= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		if(document.getElementById('main_filter_rule_edit')){RefreshTab('main_filter_rule_edit');}
		if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
		$('#rowtime'+TimeRuleIDTemp).remove();
	}
	
	var x_EnableDisableGroup= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		
		
	}	
	
	function DeleteSquidAclGroup(ID){
		DeleteSquidAclGroupTemp=ID;
		if(confirm('$delete_group_ask :'+ID)){
			var XHR = new XHRConnection();
			XHR.appendData('DeleteGroup', 'yes');
			XHR.appendData('ID', ID);
			XHR.sendAndLoad('$page', 'POST',x_DeleteSquidAclGroup);
		}  		
	}

	var x_DeleteSquidAclGroup= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#rowgroup'+DeleteSquidAclGroupTemp).remove();
	}
	
	function EnableDisableGroup(ID){
		var XHR = new XHRConnection();
		XHR.appendData('EnableGroup', 'yes');
		XHR.appendData('ID', ID);
		if(document.getElementById('groupid_'+ID).checked){XHR.appendData('enable', '1');}else{XHR.appendData('enable', '0');}
		XHR.sendAndLoad('$page', 'POST',x_EnableDisableGroup);  		
	}		
	
	

	
</script>
	
	";
	
	echo $html;
	
}

function item_form(){
	$ID=$_GET["ID"];
	$item_id=$_GET["item_id"];
	
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT GroupType FROM haproxy_acls_groups WHERE ID='$ID'"));
	$GroupType=$ligne["GroupType"];
	$GroupTypeText=$GLOBALS["GroupType"][$GroupType];
	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_sqitems WHERE ID='$item_id'"));
	$buttonname="{apply}";
	if($ID<1){$buttonname="{add}";}
	
	$explain=$q->acl_GroupType[$GroupType];

	$t=time();
	

	$html="
	<div style='font-size:16px'>$GroupTypeText</div>
	<div class=explain style='font-size:14px'>$explain</div>
	<div id='$t'>
	
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend style='font-size:14px' nowrap width=99%>{pattern}:</td>
		<td>". Field_text("$t-pattern",utf8_encode($ligne["pattern"]),"font-size:14px;width:240px",null,null,null,false,"SaveItemsModeCheck(event)")."</td>
	</tr>
	<tr>
	<td colspan=2 align='right'><hr>". button($buttonname, "SaveItemsMode()",16)."</td>
	</tr>
	</table>
	</div>
	<script>
	var x_SaveItemsMode= function (obj) {
		var res=obj.responseText;
		YahooWin3Hide();
		RefreshSquidGroupTable();
		RefreshSquidGroupItemsTable();
	}
	
	function SaveItemsModeCheck(e){
		if(checkEnter(e)){SaveItemsMode();}
	}
	
	function SaveItemsMode(){
		      var XHR = new XHRConnection();
		      XHR.appendData('item-pattern', document.getElementById('$t-pattern').value);
		      XHR.appendData('item-id', '$item_id');
		      XHR.appendData('ID', '$ID');		      
		      AnimateDiv('$t');
		      XHR.sendAndLoad('$page', 'POST',x_SaveItemsMode);  		
		}	

	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);	
}

function EditGroup_enable(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_POST["ID"];
	$q=new mysql();
	$sql="UPDATE haproxy_acls_groups SET `enabled`='{$_POST["enable"]}' WHERE ID=$ID";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?squid-rebuild=yes");
}


function group_list(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$RULEID=$_GET["RULEID"];
	$t=$_GET["t"];
	$FORCE_FILTER=null;
	$search='%';
	$table="haproxy_acls_groups";
	$page=1;
	$wpad=false;
	if(isset($_GET["wpad"])){$_GET["FilterType"]="WPAD";}
	
	

	if($q->COUNT_ROWS($table,"artica_backup")==0){
		json_error_show("No data");
		
	}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){json_error_show($q->mysql_error."\n".$sql);}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){
		json_error_show("Query return no item...$sql");
	}
	

	$haproxy=new haproxy();
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		
		$ligne['groupname']=utf8_encode($ligne['groupname']);
		$GroupTypeText=$tpl->_ENGINE_parse_body($haproxy->acl_GroupType[$ligne["grouptype"]]);
		$delete=imgsimple("delete-32.png","","Loadjs('$MyPage?delete-group-js={$ligne['ID']}');");
		
		$editjs="<a href=\"javascript:Blurz();\" 
		OnClick=\"javascript:Loadjs('squid.acls.groups.php?AddGroup-js=yes&ID={$ligne['ID']}&table-acls-t=$t');\"
		style=\"font-size:20px;text-decoration:underline\">";
		
		
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(*) as tcount FROM haproxy_acls_items WHERE groupid='{$ligne['ID']}'","artica_backup"));
		$CountDeMembers=intval($ligne2["tcount"]);
		if(!$q->ok){$CountDeMembers=$q->mysql_error;}
		if($ligne["grouptype"]=="all"){$CountDeMembers="*";}
		$rules_list=rules_list($ligne["ID"]);
		
	$data['rows'][] = array(
		'id' => "group{$ligne['ID']}",
		'cell' => array("<span style='font-size:20px;'>$editjs{$ligne['groupname']}</a></span>",
		"<span style='font-size:20px;'>$GroupTypeText</span>",
		"<span style='font-size:20px;'>$rules_list</span>",
		"<span style='font-size:20px;'>$CountDeMembers</span>",
	
	"<center>$delete</center>")
		);
	}
	
	
	echo json_encode($data);	
}

function rules_list($gpid){
	
	$q=new mysql();
	$table="SELECT haproxy_acls_link.groupid,
	haproxy_acls_rules.rulename,
	haproxy_acls_rules.servicename,
	haproxy_acls_rules.ID as aclid FROM haproxy_acls_link,haproxy_acls_rules,haproxy_acls_groups
	WHERE haproxy_acls_link.ruleid=haproxy_acls_rules.ID AND 
	haproxy_acls_groups.ID=haproxy_acls_link.groupid AND
	haproxy_acls_link.groupid=$gpid";
	$results = $q->QUERY_SQL($table,"artica_backup");
	if(!$q->ok){return $q->mysql_error;}
	$f=array();
	while ($ligne = mysql_fetch_assoc($results)) {
		$servicename=$ligne["servicename"];
		$js="Loadjs('haproxy.acls.php?rule-js=yes&ruleid={$ligne["aclid"]}',true);";
		$JSRULE="<a href=\"javascript:blur();\" OnClick=\"javascript:$js\"
		style='font-size:20px;font-weight:bold;text-decoration:underline;color:black'>";
		$rulename=$ligne["rulename"];
		$f[]="$JSRULE$servicename/$rulename</a>";
	}
	
	return @implode("<br>", $f);
}



