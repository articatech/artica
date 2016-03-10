<?php
	if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	

	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "<H2>$alert</H2>";
	die();	
}

if(isset($_GET["items-list"])){items_list();exit;}
if(isset($_POST["acl-rule-link"])){items_link();exit;}
if(isset($_POST["acl-rule-link-delete"])){items_unlink();exit;}
if(isset($_POST["acl-rule-link-negation"])){items_negation();exit;}
if(isset($_POST["acl-rule-link-order"])){items_link_order();exit;}

items_js();

function item_delete(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_POST["ID"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE ID='$ID'");	
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-smooth=yes");	
}


function item_save(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_POST["item-id"];
	$gpid=$_POST["ID"];
	$q=new mysql_squid_builder();

	$sqladd="INSERT INTO webfilters_sqitems (pattern,gpid,enabled) 
	VALUES ('{$_POST["item-pattern"]}','$gpid','1');";
	
	$sql="UPDATE webfilters_sqitems SET pattern='{$_POST["item-pattern"]}' WHERE ID='$ID'";	
	if($ID<1){$sql=$sqladd;}
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();	
	$sock->getFrameWork("squid.php?build-smooth=yes");	
}
	


function items_js(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$objects=$tpl->_ENGINE_parse_body("{objects}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$new_item=$tpl->_ENGINE_parse_body("{link_object}");
	$new_group=$tpl->_ENGINE_parse_body("{new_proxy_object}");
	$reverse=$tpl->_ENGINE_parse_body("{reverse}");
	$t=$_GET["t"];
	$html="
	<input type='hidden' id='TABLE_ITEMS_LIST_ACLS' value='table-items-$t'>
	<table class='table-items-$t' style='display: none' id='table-items-$t' style='width:99%'></table>
<script>
var DeleteAclKey=0;
function LoadTable$t(){
$('#table-items-$t').flexigrid({
	url: '$page?items-list=yes&ID=$ID&t=$t&aclid={$_GET["aclid"]}',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'zOrder', width :20, sortable : true, align: 'center'},	
		{display: '$objects', name : 'gpid', width : 311, sortable : true, align: 'left'},
		{display: '$reverse', name : 'negation', width : 31, sortable : false, align: 'center'},
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
	width: 605,
	height: 350,
	singleSelect: true
	
	});   
}
function LinkAclItem() {
	Loadjs('squid.BrowseAclGroups.php?callback=LinkAclRuleGpid{$_GET["aclid"]}');
	
}	

function LinkAddAclItem(){
	Loadjs('squid.acls.groups.php?AddGroup-js=-1&link-acl={$_GET["aclid"]}&table-acls-t=$t');
}

var x_LinkAclRuleGpid{$_GET["aclid"]}= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	
	if(document.getElementById('GLOBAL_SSL_CENTER_ID')){
		$('#'+document.getElementById('GLOBAL_SSL_CENTER_ID').value).flexReload();
	}
	if(document.getElementById('SSL_RULES_GROUPS_ID')){
		
		$('#'+document.getElementById('SSL_RULES_GROUPS_ID').value).flexReload();
	}	
	
	
	
	$('#table-items-$t').flexReload();
	$('#table-$t').flexReload();
	ExecuteByClassName('SearchFunction');
}	

function LinkAclRuleGpid{$_GET["aclid"]}(gpid){
		var XHR = new XHRConnection();
		XHR.appendData('acl-rule-link', '{$_GET["aclid"]}');
		XHR.appendData('gpid', gpid);
		XHR.sendAndLoad('$page', 'POST',x_LinkAclRuleGpid{$_GET["aclid"]});  		
	}
	
	function DeleteObjectLinks(mkey){
		DeleteAclKey=mkey;
		var XHR = new XHRConnection();
		XHR.appendData('acl-rule-link-delete', mkey);
		XHR.sendAndLoad('$page', 'POST',x_DeleteObjectLinks);
		  		
	}
	
	function ChangeNegation(mkey){
		var value=0;
		var XHR = new XHRConnection();
		if(document.getElementById('negation-'+mkey).checked){value=1;}
		XHR.appendData('acl-rule-link-negation', mkey);
		XHR.appendData('value', value);
		XHR.sendAndLoad('$page', 'POST',x_LinkAclRuleGpid{$_GET["aclid"]});
	}
	
function AclGroupUpDown(mkey,direction){
	var XHR = new XHRConnection();
	XHR.appendData('acl-rule-link-order', mkey);
	XHR.appendData('direction', direction);
	XHR.appendData('aclid', '{$_GET["aclid"]}');
	XHR.sendAndLoad('$page', 'POST',x_LinkAclRuleGpid{$_GET["aclid"]});

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
	$sql="UPDATE webfilters_sqacllinks SET negation={$_POST["value"]} WHERE zmd5='$md5'";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
}

function items_unlink(){
	$md5=$_POST["acl-rule-link-delete"];
	$sql="DELETE FROM webfilters_sqacllinks WHERE zmd5='$md5'";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	
}

function items_link(){
	$aclid=$_POST["acl-rule-link"];
	$gpid=$_POST["gpid"];
	$md5=md5($aclid.$gpid);
	$sql="INSERT IGNORE INTO webfilters_sqacllinks (zmd5,aclid,gpid,zOrder) VALUES('$md5','$aclid','$gpid',1)";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}

}


function items_list(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$ID=$_GET["aclid"];
	$acl=new squid_acls();
	$t0=$_GET["t"];
	

	
	$search='%';
	$table="(SELECT webfilters_sqacllinks.gpid,webfilters_sqacllinks.negation,
	webfilters_sqacllinks.zOrder,webfilters_sqacllinks.zmd5 as mkey,
	webfilters_sqgroups.* FROM webfilters_sqacllinks,webfilters_sqgroups 
	WHERE webfilters_sqacllinks.gpid=webfilters_sqgroups.ID AND webfilters_sqacllinks.aclid=$ID
	ORDER BY webfilters_sqacllinks.zOrder
	) as t";
	
	$page=1;

	if($q->COUNT_ROWS("webfilters_sqacllinks")==0){json_error_show("No datas");}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show($q->mysql_error."\n$sql");}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show($q->mysql_error);}
	$rules=$tpl->_ENGINE_parse_body("{rules}");
	$acl=new squid_acls_groups();
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$mkey=$ligne["mkey"];
		$arrayF=$acl->FlexArray($ligne['ID']);
		$delete=imgsimple("delete-24.png",null,"DeleteObjectLinks('$mkey')");
		$negation=Field_checkbox("negation-$mkey", 1,$ligne["negation"],"ChangeNegation('$mkey')");
		$up=imgsimple("arrow-up-16.png","","AclGroupUpDown('$mkey',0)");
		$down=imgsimple("arrow-down-18.png","","AclGroupUpDown('$mkey',1)");
		if($ligne["zOrder"]==1){$up=null;}
		if($ligne["zOrder"]==0){$up=null;}
		
	$data['rows'][] = array(
		'id' => "$mkey",
		'cell' => array($ligne["zOrder"],$arrayF["ROW"],
		$negation,"<span style='font-size:14px;font-weight:bold'>{$arrayF["ITEMS"]}</span>",
		$up,$down,$delete)
		);
	}
	
	
	echo json_encode($data);	
}

function items_link_order(){
	$mkey=$_POST["acl-rule-link-order"];
	$direction=$_POST["direction"];
	$aclid=$_POST["aclid"];
	$table="webfilters_sqacllinks";
	//up =1, Down=0
	$q=new mysql_squid_builder();
	$sql="SELECT zOrder FROM webfilters_sqacllinks WHERE zmd5='$mkey'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	
	$OlOrder=$ligne["zOrder"];
	if($direction==1){$NewOrder=$OlOrder+1;}else{$NewOrder=$OlOrder-1;}
	$sql="UPDATE webfilters_sqacllinks SET zOrder='$OlOrder' WHERE zOrder='$NewOrder' AND aclid='$aclid'";
//	echo $sql."\n";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	$sql="UPDATE webfilters_sqacllinks SET zOrder='$NewOrder' WHERE zmd5='$mkey'";
	$q->QUERY_SQL($sql);
//	echo $sql."\n";
	if(!$q->ok){echo $q->mysql_error;}
	
	$results=$q->QUERY_SQL("SELECT zmd5 FROM webfilters_sqacllinks WHERE aclid='$aclid' ORDER BY zOrder");
	$c=1;
	while ($ligne = mysql_fetch_assoc($results)) {
		$zmd5=$ligne["zmd5"];
		$q->QUERY_SQL("UPDATE webfilters_sqacllinks SET zOrder='$c' WHERE zmd5='$zmd5'");
		$c++;
		
	}
	
	
}

