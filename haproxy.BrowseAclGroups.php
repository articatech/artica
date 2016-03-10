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


js();



function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$wpad=null;
	$title=$tpl->_ENGINE_parse_body("{proxy_objects}");
	if(isset($_GET["wpad"])){$wpad="&wpad=yes";}
	$html="YahooWinBrowse('600','$page?popup=yes&callback={$_GET["callback"]}&FilterType={$_GET["FilterType"]}&aclid={$_GET["aclid"]}','$title')";
	echo $html;
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

function EditGroup_popup(){
	$ID=$_GET["ID"];
	if(!is_numeric($ID)){$ID=0;}
	if($ID>0){if(!isset($_GET["tab"])){EditGroup_tabs();return;}}
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM haproxy_acls_groups WHERE ID='$ID'"));
	$buttonname="{apply}";
	if($ID<1){$buttonname="{add}";}
	
	$t=time();
	$GroupType["src"]="{addr}";
	$GroupType["arp"]="{ComputerMacAddress}";
	$GroupType["dstdomain"]="{dstdomain}";
	$GroupType["proxy_auth"]="{members}";
	
	$html="
	<div id='$t'>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend style='font-size:14px' nowrap width=99%>{groupname}:</td>
		<td>". Field_text("GroupName",utf8_encode($ligne["GroupName"]),"font-size:14px;width:240px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px' nowrap>{group_type}:</td>
		<td>". Field_array_Hash($GroupType,"GroupType",$ligne["GroupType"],"style:font-size:14px")."</td>
	</tr>	
	
	
	<tr>
	<td colspan=2 align='right'><hr>". button($buttonname, "SaveAclGroupMode()",16)."</td>
	</tr>
	</table>
	</div>
	<script>
	var x_SaveAclGroupMode= function (obj) {
		var res=obj.responseText;
		YahooWinTHide();
		if(document.getElementById('formulaire-choix-groupe-proxy')){RefreshFormulaireChoixGroupeProxy();}
		if(document.getElementById('flexRT-refresh-1')){ $('#'+document.getElementById('flexRT-refresh-1').value).flexReload();}
		RefreshSquidGroupTable();
	}
	
	function SaveAclGroupMode(){
		      var XHR = new XHRConnection();
		      XHR.appendData('GroupName', document.getElementById('GroupName').value);
		      XHR.appendData('GroupType', document.getElementById('GroupType').value);
		      XHR.appendData('ID', '$ID');	      
		      AnimateDiv('$t');
		      XHR.sendAndLoad('$page', 'POST',x_SaveAclGroupMode);  		
		}	
		
	function CheckGrouform$t(){
		var id=$ID;
		if(id>0){document.getElementById('GroupType').disabled=true;}
	}
CheckGrouform$t();
	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);	
}

function EditGroup_delete(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_POST["ID"];
	
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE gpid='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}

	$q->QUERY_SQL("DELETE FROM haproxy_acls_groups WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?squid-rebuild=yes");
}
function item_delete(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_POST["ID"];
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE ID='$ID'");	
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?squid-rebuild=yes");
}

function EditGroup_save(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_POST["ID"];
	$q=new mysql();
		
	$sqladd="INSERT INTO haproxy_acls_groups (GroupName,GroupType,enabled) 
	VALUES ('{$_POST["GroupName"]}','{$_POST["GroupType"]}','1');";
	
	$sql="UPDATE haproxy_acls_groups SET GroupName='{$_POST["GroupName"]}' WHERE ID='$ID'";

	
	if($ID<1){$sql=$sqladd;}
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}

}
function item_save(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_POST["item-id"];
	$gpid=$_POST["ID"];
	$q=new mysql();

	$sqladd="INSERT INTO webfilters_sqitems (pattern,gpid,enabled) 
	VALUES ('{$_POST["item-pattern"]}','$gpid','1');";
	
	$sql="UPDATE webfilters_sqitems SET pattern='{$_POST["item-pattern"]}' WHERE ID='$ID'";	
	if($ID<1){$sql=$sqladd;}
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();	
	$sock->getFrameWork("cmd.php?squid-rebuild=yes");
}
	
	
	
function EditGroup_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_GET["ID"];
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT GroupType FROM haproxy_acls_groups WHERE ID='$ID'"));
	$GroupType=$ligne["GroupType"];
	
	if(!isset($q->acl_ARRAY_NO_ITEM[$GroupType])){
		$array["items"]='{items}';
	}
	$array["EditGroup-popup"]='{settings}';
	

	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:14px'><a href=\"$page?$num=yes&ID=$ID&tab=yes\"><span>$ligne</span></a></li>\n");
	
	}

	echo build_artica_tabs($html, "main_content_rule_editsquidgroup");
	
}

function items_js(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();	
	$items=$tpl->_ENGINE_parse_body("{items}");
	$new_item=$tpl->_ENGINE_parse_body("{new_item}");
	$t=time();		
	$html="
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
var DeleteGroupItemTemp=0;
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?items-list=yes&ID=$ID',
	dataType: 'json',
	colModel : [
		{display: '$items', name : 'pattern', width : 304, sortable : true, align: 'left'},
		{display: '', name : 'none2', width : 22, sortable : false, align: 'left'},
		{display: '', name : 'none3', width : 36, sortable : false, align: 'left'},
		
	],
buttons : [
	{name: '$new_item', bclass: 'add', onpress : AddItem},
		],	
	searchitems : [
		{display: '$items', name : 'pattern'},
		],
	sortname: 'pattern',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: 400,
	height: 250,
	singleSelect: true
	
	});   
});
function AddItem() {
	Loadjs('$page?AddItem-js=yes&item-id=-1&ID=$ID');
	
}	

function RefreshSquidGroupItemsTable(){
	$('#table-$t').flexReload();
	if(document.getElementById('flexRT-refresh-1')){ $('#'+document.getElementById('flexRT-refresh-1').value).flexReload();}
}


	var x_DeleteGroupItem= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#rowitem'+DeleteGroupItemTemp).remove();
		RefreshSquidGroupTable();
	}
	
	var x_EnableDisableGroup= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		
		if(document.getElementById('GLOBAL_SSL_CENTER_ID')){
			$('#'+document.getElementById('GLOBAL_SSL_CENTER_ID').value).flexReload();
		}
		if(document.getElementById('SSL_RULES_GROUPS_ID')){
			$('#'+document.getElementById('SSL_RULES_GROUPS_ID').value).flexReload();
		}
		
	}	
	
	function DeleteGroupItem(ID){
		DeleteGroupItemTemp=ID;
		var XHR = new XHRConnection();
		XHR.appendData('DeleteItem', 'yes');
		XHR.appendData('ID', ID);
		XHR.sendAndLoad('$page', 'POST',x_DeleteGroupItem);  		
	}

	var x_TimeRuleDansDelete= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		if(document.getElementById('main_filter_rule_edit')){RefreshTab('main_filter_rule_edit');}
		if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
		$('#rowtime'+TimeRuleIDTemp).remove();
	}
	
	function EnableDisableItem(ID){
		var XHR = new XHRConnection();
		XHR.appendData('EnableItem', 'yes');
		XHR.appendData('ID', ID);
		if(document.getElementById('itemid_'+ID).checked){XHR.appendData('enable', '1');}else{XHR.appendData('enable', '0');}
		XHR.sendAndLoad('$page', 'POST',x_EnableDisableGroup);  		
	}		
	
	

	
</script>
	
	";
	
	echo $html;
	
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
	$wpad=null;
	if(isset($_GET["wpad"])){$wpad="&wpad=yes";}
	
	$t=time();	

		$buttons="
	buttons : [
	{name: '$new_group', bclass: 'add', onpress : AddGroup$t},
	],";

	$html="
	<table class='HAPROXY_BROWSE_ACL_GROUPS_ID' style='display: none' id='HAPROXY_BROWSE_ACL_GROUPS_ID' style='width:99%'></table>
<script>
var DeleteSquidAclGroupTemp=0;
$(document).ready(function(){
$('#HAPROXY_BROWSE_ACL_GROUPS_ID').flexigrid({
	url: '$page?groups-list=yes&callback={$_GET["callback"]}&t=$t&FilterType={$_GET["FilterType"]}$wpad',
	dataType: 'json',
	colModel : [
		{display: '$description', name : 'groupname', width : 277, sortable : true, align: 'left'},
		{display: '$time', name : 'GroupType', width : 119, sortable : true, align: 'left'},
		{display: '$items', name : 'items', width : 67, sortable : false, align: 'center'},
		{display: '', name : 'none3', width : 31, sortable : false, align: 'left'},
		
	],
	$buttons
	searchitems : [
		{display: '$description', name : 'GroupName'},
		],
	sortname: 'GroupName',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: 570,
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
function item_enable(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_POST["ID"];
	$q=new mysql();
	$sql="UPDATE webfilters_sqitems SET `enabled`='{$_POST["enable"]}' WHERE ID=$ID";
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
	
	if($_GET["FilterType"]<>null){
			switch ($_GET["FilterType"]) {
			case "src":
				$FORCE_FILTER="AND GroupType='src'";
				break;
			case "dstdomain":
				$FORCE_FILTER="AND GroupType='dstdomain'";
				break;				
			case "MAC":
				$FORCE_FILTER="AND GroupType='arp'";
				break;
			case "uid":
				$FORCE_FILTER="AND ( GroupType='ext_user' OR GroupType='proxy_auth_ads' OR GroupType='proxy_auth' OR GroupType='proxy_auth_statad')";
				break;
			case "ADMBR":
				$FORCE_FILTER="AND ( GroupType='proxy_auth_ads' OR GroupType='proxy_auth'  OR GroupType='proxy_auth_statad')";
				break;	
			case "IPTABLES":
				$f=$q->acl_GroupType_iptables;
				while (list($a,$b)=each($f)){ $tz[]="GroupType='$a'"; }
				$FORCE_FILTER="AND ( ".@implode(" OR ", $tz).")";
				break;
				
			case "FW-IN":
				$f=$q->acl_GroupType_Firewall_in;
				while (list($a,$b)=each($f)){ $tz[]="GroupType='$a'"; }
				$FORCE_FILTER="AND ( ".@implode(" OR ", $tz).")";
				break;	

			case "FW-OUT":
				$f=$q->acl_GroupType_Firewall_out;
				while (list($a,$b)=each($f)){ $tz[]="GroupType='$a'"; }
				$FORCE_FILTER="AND ( ".@implode(" OR ", $tz).")";
				break;	

			case "FW-PORT":
				$f=$q->acl_GroupType_Firewall_port;
				while (list($a,$b)=each($f)){ $tz[]="GroupType='$a'"; }
				$FORCE_FILTER="AND ( ".@implode(" OR ", $tz).")";
				break;				
				
			case "WPAD":
				$f=$q->acl_GroupType_WPAD;
				while (list($a,$b)=each($f)){ $tz[]="GroupType='$a'"; }
				$FORCE_FILTER="AND ( ".@implode(" OR ", $tz).")";
				break;				
				
		}
	}

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
		$select=imgsimple("arrow-right-24.png","","{$_GET["callback"]}('{$ligne['ID']}');YahooWinBrowseHide();");
		
		$editjs="<a href=\"javascript:Blurz();\" 
		OnClick=\"javascript:Loadjs('squid.acls.groups.php?AddGroup-js=yes&ID={$ligne['ID']}&table-acls-t=$t');\"
		style=\"font-size:14px;text-decoration:underline\">";
		
		
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(*) as tcount FROM haproxy_acls_items WHERE groupid='{$ligne['ID']}'","artica_backup"));
		$CountDeMembers=intval($ligne2["tcount"]);
		if(!$q->ok){$CountDeMembers=$q->mysql_error;}
		if($ligne["grouptype"]=="all"){$CountDeMembers="*";}
		
		
	$data['rows'][] = array(
		'id' => "group{$ligne['ID']}",
		'cell' => array("<span style='font-size:14px;'>$editjs{$ligne['groupname']}</a></span>",
		"<span style='font-size:14px;'>$GroupTypeText</span>",
		"<span style='font-size:14px;'>$CountDeMembers</span>",
	
	"<center>$select</center>")
		);
	}
	
	
	echo json_encode($data);	
}
function items_list(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$ID=$_GET["ID"];
	$FORCE_FILTER=null;
	$search='%';
	$table="webfilters_sqitems";
	$page=1;

	if($q->COUNT_ROWS($table)==0){json_error_show("no item");}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE gpid=$ID $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE gpid=$ID $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE gpid=$ID $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){json_error_show($q->mysql_error);}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("no item");}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$disable=Field_checkbox("itemid_{$ligne['ID']}", 1,$ligne["enabled"],"EnableDisableItem('{$ligne['ID']}')");
		$macname=$q->MAC_TO_NAME($ligne['pattern']);
		$ligne['pattern']=utf8_encode($ligne['pattern']);
		$delete=imgtootltip("delete-24.png","{delete} {$ligne['pattern']}","DeleteGroupItem('{$ligne['ID']}')");
		$additional_text=null;
		
		if($macname){
			$additional_text="<div style='font-size:10px'>$macname</div>";
		}
			
		
		
		
	$data['rows'][] = array(
		'id' => "item{$ligne['ID']}",
		'cell' => array("<span style='font-size:14px;font-weight:bold'>{$ligne['pattern']}</span>$additional_text",
		"<div style='padding-top:5px'>$disable</div>",
		$delete)
		);
	}
	
	
	echo json_encode($data);	
}


