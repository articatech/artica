<?php
	if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
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

if(isset($_GET["items-list"])){items_list();exit;}
if(isset($_POST["acl-rule-link"])){group_link();exit;}
if(isset($_POST["acl-rule-link-delete"])){group_unlink();exit;}
if(isset($_POST["acl-rule-link-negation"])){items_negation();exit;}
if(isset($_POST["acl-rule-link-order"])){items_link_order();exit;}
if(isset($_POST["acl-rule-or"])){items_operator();exit;}
items_js();

function item_delete(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_POST["ID"];
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE ID='$ID'");	
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
}
	


function items_js(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();	
	$objects=$tpl->_ENGINE_parse_body("{objects}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$new_item=$tpl->_ENGINE_parse_body("{link_object}");
	$new_group=$tpl->_ENGINE_parse_body("{new_proxy_object}");
	$reverse=$tpl->_ENGINE_parse_body("{reverse}");
	$operator=$tpl->_ENGINE_parse_body("{operator}");
	$t=$_GET["t"];
	$html="
	
	<table class='HAPROXY_OBJECTS_LIST_ACLS' style='display: none' id='HAPROXY_OBJECTS_LIST_ACLS' style='width:99%'></table>
<script>
var DeleteAclKey=0;
function LoadTable$t(){
$('#HAPROXY_OBJECTS_LIST_ACLS').flexigrid({
	url: '$page?items-list=yes&ID=$ID&t=$t&aclid=$ID',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'zorder', width :20, sortable : true, align: 'center'},	
		{display: '$objects', name : 'gpid', width : 311, sortable : true, align: 'left'},
		{display: '$reverse', name : 'negation', width : 60, sortable : false, align: 'center'},
		{display: '$operator', name : 'operator', width : 81, sortable : false, align: 'center'},
		{display: '$items', name : 'items', width : 69, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'up', width :20, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'down', width :20, sortable : false, align: 'center'},		
		{display: '&nbsp;', name : 'del', width : 31, sortable : false, align: 'center'},
		
	],
buttons : [
	{name: '$new_item', bclass: 'add', onpress : LinkAclItem},
	{name: '$new_group', bclass: 'add', onpress : LinkAddAclItem},
		],	
	searchitems : [
		{display: '$items', name : 'GroupName'},
		],
	sortname: 'zOrder',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: 750,
	height: 350,
	singleSelect: true
	
	});   
}
function LinkAclItem() {
	Loadjs('haproxy.BrowseAclGroups.php?callback=LinkAclHaProxyRuleGpid{$ID}&aclid={$ID}');
	
}	



function LinkAddAclItem(){
	Loadjs('haproxy.acls.group.setting.php?new-group-js=yes&linkacl={$ID}');
}

var x_LinkAclHaProxyRuleGpid{$ID}= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#HAPROXY_OBJECTS_LIST_ACLS').flexReload();
	$('#HAPROXY_ACLS_TABLE').flexReload();
	
}	

function LinkAclHaProxyRuleGpid{$ID}(gpid){
	var value=0;
	var XHR = new XHRConnection();
	XHR.appendData('acl-rule-link', '$ID');
	XHR.appendData('gpid', gpid);
	XHR.sendAndLoad('$page', 'POST',x_LinkAclHaProxyRuleGpid{$ID});
}

function AclGroupOperator(ID){
	var value=0;
	var XHR = new XHRConnection();
	XHR.appendData('acl-rule-or', ID);
	XHR.sendAndLoad('$page', 'POST',x_LinkAclHaProxyRuleGpid{$ID});
}

function DeleteObjectLinks(mkey){
	DeleteAclKey=mkey;
	var XHR = new XHRConnection();
	XHR.appendData('acl-rule-link-delete', mkey);
	XHR.sendAndLoad('$page', 'POST',x_LinkAclHaProxyRuleGpid{$ID});
}
	
function ChangeNegation(mkey){
	var value=0;
	var XHR = new XHRConnection();
	if(document.getElementById('negation-'+mkey).checked){value=1;}
	XHR.appendData('acl-rule-link-negation', mkey);
	XHR.appendData('value', value);
	XHR.sendAndLoad('$page', 'POST',x_LinkAclHaProxyRuleGpid{$ID});
}
	
function AclGroupUpDown(mkey,direction){
	var XHR = new XHRConnection();
	XHR.appendData('acl-rule-link-order', mkey);
	XHR.appendData('direction', direction);
	XHR.appendData('aclid', '{$ID}');
	XHR.sendAndLoad('$page', 'POST',x_LinkAclHaProxyRuleGpid{$ID});

}

	var x_DeleteObjectLinks= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#row'+DeleteAclKey).remove();
		$('#table-$t').flexReload();
		ExecuteByClassName('SearchFunction');
	}	
	
LoadTable$t();
</script>
	
	";
	
	echo $html;
	
}

function items_negation(){
	$md5=$_POST["acl-rule-link-negation"];
	$sql="UPDATE haproxy_acls_link SET revert={$_POST["value"]} WHERE ID='$md5'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
}
function items_operator(){
	$ID=$_POST["acl-rule-or"];
	$q=new mysql();
	$sql="SELECT operator FROM haproxy_acls_link WHERE ID='$ID'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	if($ligne["operator"]==0){$operator=1;}else{$operator=0;}
	$sql="UPDATE haproxy_acls_link SET operator=$operator WHERE ID='$ID'";
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
}

function group_unlink(){
	$md5=$_POST["acl-rule-link-delete"];
	$sql="DELETE FROM haproxy_acls_link WHERE ID='$md5'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	
}

function group_link(){
	$aclid=$_POST["acl-rule-link"];
	$gpid=$_POST["gpid"];
	
	$sql="INSERT IGNORE INTO haproxy_acls_link (ruleid,groupid,zorder) VALUES('$aclid','$gpid',1)";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}

}


function items_list(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$ID=$_GET["aclid"];
	$t0=$_GET["t"];
	$rp=50;

	
	$search='%';
	$table="(SELECT haproxy_acls_link.groupid,
	haproxy_acls_link.ID as tid,
	haproxy_acls_link.revert,
	haproxy_acls_link.operator,
	haproxy_acls_link.zorder as torder,
	haproxy_acls_groups.* FROM haproxy_acls_link,haproxy_acls_groups 
	WHERE haproxy_acls_link.groupid=haproxy_acls_groups.ID AND haproxy_acls_link.ruleid=$ID
	ORDER BY haproxy_acls_link.zorder
	) as t";
	
	$page=1;

	if($q->COUNT_ROWS("haproxy_acls_link","artica_backup")==0){json_error_show("No datas haproxy_acls_link Empty");}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){json_error_show($q->mysql_error."\n$sql");}
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){json_error_show($q->mysql_error."\n$sql");}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $ORDER $limitSql";	
	
	$results = $q->QUERY_SQL($sql,"artica_backup");
	
	if($GLOBALS["VERBOSE"]){echo "$sql<br>\n";}
	
	if(!$q->ok){json_error_show($q->mysql_error."\n$sql");}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$CountofRows=mysql_num_rows($results);
	if($GLOBALS["VERBOSE"]){echo "CountofRows = $CountofRows<br>\n";}
	if($CountofRows==0){json_error_show("No data");}
	$rules=$tpl->_ENGINE_parse_body("{rules}");
	$acl=new haproxy();
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$mkey=$ligne["tid"];
		$arrayF=$acl->FlexArray($ligne['ID']);
		$delete=imgsimple("delete-24.png",null,"DeleteObjectLinks('$mkey')");
		$negation=Field_checkbox("negation-$mkey", 1,$ligne["revert"],"ChangeNegation('$mkey')");
		$operator=$ligne["operator"];
		$operator=$acl->acl_operator[$operator];
		$operator=$tpl->_ENGINE_parse_body($operator);
		$up=imgsimple("arrow-up-16.png","","AclGroupUpDown('$mkey',0)");
		$down=imgsimple("arrow-down-18.png","","AclGroupUpDown('$mkey',1)");
		if($ligne["torder"]==1){$up=null;}
		if($ligne["torder"]==0){$up=null;}
		
		$operator="
				<a href=\"javascript:blur()\" OnClick=\"javascript:AclGroupOperator('$mkey')\"
				style='text-decoration:underline;font-weight:bolder'>$operator</a>";
		
	$data['rows'][] = array(
		'id' => "$mkey",
		'cell' => array($ligne["torder"],$arrayF["ROW"],
		"<center>$negation</center>",
		"<center style='font-size:14px;font-weight:bold'>$operator</center>",
		"<span style='font-size:14px;font-weight:bold'>{$arrayF["ITEMS"]}</span>",
		$up,$down,$delete)
		);
	}
	
	
	echo json_encode($data);	
}

function items_link_order(){
	$mkey=$_POST["acl-rule-link-order"];
	$direction=$_POST["direction"];
	$aclid=$_POST["aclid"];
	$table="haproxy_acls_link";
	//up =1, Down=0
	$q=new mysql();
	$sql="SELECT zorder FROM haproxy_acls_link WHERE ID='$mkey'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	
	$OlOrder=$ligne["zorder"];
	if($direction==1){$NewOrder=$OlOrder+1;}else{$NewOrder=$OlOrder-1;}
	$sql="UPDATE haproxy_acls_link SET zorder='$OlOrder' WHERE zorder='$NewOrder' AND ruleid='$aclid'";
//	echo $sql."\n";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	$sql="UPDATE haproxy_acls_link SET zorder='$NewOrder' WHERE ID='$mkey'";
	$q->QUERY_SQL($sql,"artica_backup");
//	echo $sql."\n";
	if(!$q->ok){echo $q->mysql_error;}
	
	$results=$q->QUERY_SQL("SELECT zmd5 FROM haproxy_acls_link WHERE ruleid='$aclid' ORDER BY zOrder");
	$c=1;
	while ($ligne = mysql_fetch_assoc($results)) {
		$zmd5=$ligne["ID"];
		$q->QUERY_SQL("UPDATE haproxy_acls_link SET zorder='$c' WHERE ID='$zmd5'");
		$c++;
		
	}
	
	
}

