<?php
	if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	

	
$usersmenus=new usersMenus();
if(!$usersmenus->AsSystemAdministrator){
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
	$new_group=$tpl->_ENGINE_parse_body("{new_object}");
	$reverse=$tpl->_ENGINE_parse_body("{reverse}");
	$t=$_GET["t"];
	
	
	if($_GET["direction"]==1){
		$FilterType="FW-OUT";
		
	}else{
		$FilterType="FW-IN";
	}
	
	$LinkRuleName="$ID{$_GET["direction"]}".time();
	
	$html="
	
	<table class='FIREHOLE_ITEMS_LIST_ACLS{$_GET["direction"]}' style='display: none' id='FIREHOLE_ITEMS_LIST_ACLS{$_GET["direction"]}' style='width:99%'></table>
<script>
var DeleteAclKey=0;
function LoadTable$t(){
$('#FIREHOLE_ITEMS_LIST_ACLS{$_GET["direction"]}').flexigrid({
	url: '$page?items-list=yes&ID=$ID&t=$t&aclid={$ID}&direction={$_GET["direction"]}',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'zOrder', width :46, sortable : true, align: 'center'},	
		{display: '<span style=font-size:18px>$objects</span>', name : 'gpid', width : 521, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$reverse</span>', name : 'negation', width : 79, sortable : false, align: 'center'},
		{display: '<span style=font-size:18px>$items</span>', name : 'items', width : 95, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'up', width :46, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'down', width :46, sortable : false, align: 'center'},		
		{display: '&nbsp;', name : 'del', width : 46, sortable : false, align: 'center'},
		
	],
buttons : [
	{name: '<strong style=font-size:18px>$new_item</strong>', bclass: 'add', onpress : LinkAclItem},
	{name: '<strong style=font-size:18px>$new_group</strong>', bclass: 'add', onpress : LinkAddAclItem},
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
	width: '99%',
	height: 350,
	singleSelect: true
	
	});   
}
function LinkAclItem() {
	Loadjs('squid.BrowseAclGroups.php?callback=LinkFWRuleGpid$LinkRuleName&FilterType=$FilterType&TABLEID=FIREHOLE_ITEMS_LIST_ACLS{$_GET["direction"]}');
	
}	

function LinkAddAclItem(){
	Loadjs('squid.acls.groups.php?AddGroup-js=-1&fw-acl={$_GET["ID"]}&TABLEID=FIREHOLE_ITEMS_LIST_ACLS{$_GET["direction"]}&FilterType=$FilterType&fw-dir={$_GET["direction"]}');
}

var x_LinkFWRuleGpid$LinkRuleName= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#FIREHOLE_ITEMS_LIST_ACLS{$_GET["direction"]}').flexReload();
	$('#FIREWALL_NIC_RULES').flexReload();
	
}	

function LinkFWRuleGpid$LinkRuleName(gpid){
		var XHR = new XHRConnection();
		XHR.appendData('acl-rule-link', '{$_GET["ID"]}');
		XHR.appendData('direction', '{$_GET["direction"]}');
		XHR.appendData('gpid', gpid);
		XHR.sendAndLoad('$page', 'POST',x_LinkFWRuleGpid$LinkRuleName);  		
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
		XHR.sendAndLoad('$page', 'POST',x_LinkFWRuleGpid{$_GET["ID"]});
	}
	
function AclGroupUpDown(mkey,direction){
	var XHR = new XHRConnection();
	XHR.appendData('acl-rule-link-order', mkey);
	XHR.appendData('direction', direction);
	XHR.appendData('aclid', '{$_GET["ID"]}');
	XHR.sendAndLoad('$page', 'POST',x_LinkFWRuleGpid{$_GET["ID"]});

}

	var x_DeleteObjectLinks= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#row'+DeleteAclKey).remove();
		$('#table-$t').flexReload();
	}	
	
LoadTable$t();
</script>
	
	";
	
	echo $html;
	
}

function items_negation(){
	$md5=$_POST["acl-rule-link-negation"];
	$sql="UPDATE firewallfilter_sqacllinks SET negation={$_POST["value"]} WHERE zmd5='$md5'";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
}

function items_unlink(){
	$md5=$_POST["acl-rule-link-delete"];
	$sql="DELETE FROM firewallfilter_sqacllinks WHERE zmd5='$md5'";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	
}

function items_link(){
	$aclid=$_POST["acl-rule-link"];
	$gpid=$_POST["gpid"];
	$direction=$_POST["direction"];
	$md5=md5($aclid.$gpid.$direction);
	$sql="INSERT IGNORE INTO firewallfilter_sqacllinks (zmd5,aclid,gpid,zOrder,direction) VALUES('$md5','$aclid','$gpid',1,$direction)";
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
	$FORCE_FILTER=null;

	
	$search='%';
	$table="(SELECT firewallfilter_sqacllinks.gpid,firewallfilter_sqacllinks.negation,
	firewallfilter_sqacllinks.zOrder,firewallfilter_sqacllinks.zmd5 as mkey,
	webfilters_sqgroups.* FROM firewallfilter_sqacllinks,webfilters_sqgroups 
	WHERE firewallfilter_sqacllinks.gpid=webfilters_sqgroups.ID 
	AND firewallfilter_sqacllinks.aclid=$ID
	AND firewallfilter_sqacllinks.direction='{$_GET["direction"]}'
	ORDER BY firewallfilter_sqacllinks.zOrder
	) as t";
	
	$page=1;

	if($q->COUNT_ROWS("firewallfilter_sqacllinks")==0){json_error_show("No datas");}
	
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
		$arrayF=$acl->FlexArray($ligne['ID'],1,18);
		$delete=imgsimple("delete-32.png",null,"DeleteObjectLinks('$mkey')");
		$negation=Field_checkbox("negation-$mkey", 1,$ligne["negation"],"ChangeNegation('$mkey')");
		$up=imgsimple("arrow-up-32.png","","AclGroupUpDown('$mkey',0)");
		$down=imgsimple("arrow-down-32.png","","AclGroupUpDown('$mkey',1)");
		if($ligne["zOrder"]==1){$up=null;}
		if($ligne["zOrder"]==0){$up=null;}
		
	$data['rows'][] = array(
		'id' => "$mkey",
		'cell' => array(
		
		"<center style='font-size:18px;font-weight:bold'>{$ligne["zOrder"]}</center>",$arrayF["ROW"],
		$negation,"<span style='font-size:18px;font-weight:bold'>{$arrayF["ITEMS"]}</span>",
		"<center>$up</center>",
		"<center>$down</center>",
		"<center>$delete</center>")
		);
	}
	
	
	echo json_encode($data);	
}

function items_link_order(){
	$mkey=$_POST["acl-rule-link-order"];
	$direction=$_POST["direction"];
	$aclid=$_POST["aclid"];
	$table="firewallfilter_sqacllinks";
	//up =1, Down=0
	$q=new mysql_squid_builder();
	$sql="SELECT zOrder FROM firewallfilter_sqacllinks WHERE zmd5='$mkey'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	
	$OlOrder=$ligne["zOrder"];
	if($direction==1){$NewOrder=$OlOrder+1;}else{$NewOrder=$OlOrder-1;}
	$sql="UPDATE firewallfilter_sqacllinks SET zOrder='$OlOrder' WHERE zOrder='$NewOrder' AND aclid='$aclid'";
//	echo $sql."\n";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	$sql="UPDATE firewallfilter_sqacllinks SET zOrder='$NewOrder' WHERE zmd5='$mkey'";
	$q->QUERY_SQL($sql);
//	echo $sql."\n";
	if(!$q->ok){echo $q->mysql_error;}
	
	$results=$q->QUERY_SQL("SELECT zmd5 FROM firewallfilter_sqacllinks WHERE aclid='$aclid' ORDER BY zOrder");
	$c=1;
	while ($ligne = mysql_fetch_assoc($results)) {
		$zmd5=$ligne["zmd5"];
		$q->QUERY_SQL("UPDATE firewallfilter_sqacllinks SET zOrder='$c' WHERE zmd5='$zmd5'");
		$c++;
		
	}
	
	
}

