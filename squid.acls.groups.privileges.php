<?php
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');$GLOBALS["VERBOSE"]=true;}	
	if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');$GLOBALS["VERBOSE"]=true;}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	include_once('ressources/class.external.ad.inc');
	include_once('ressources/class.external_acl_squid_ldap.inc');
	include_once('ressources/class.groups.inc');

	
	
$usersmenus=new usersMenus();

if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "<H2>$alert</H2>";
	die();	
}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_GET["add-js"])){add_js();exit;}
if(isset($_GET["add-popup"])){add_popup();exit;}
if(isset($_GET["add-fillfield"])){add_fillfield();exit;}
if(isset($_GET["groups-list"])){group_list();exit;}
if(isset($_POST["pattern"])){save();exit;}
if(isset($_POST["delete"])){delete();exit;}

table();




function add_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT GroupName FROM webfilters_sqgroups WHERE ID={$_GET["gpid"]}"));
	$title=$tpl->_ENGINE_parse_body("{new_privilege} {$ligne["GroupName"]}");
	$html="YahooWin5(890,'$page?add-popup=yes&gpid={$_GET["gpid"]}','$title')";
	echo $html;	
}

function delete_js(){
	$page=CurrentPageName();
	$ID=$_GET["delete-js"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT pattern,`type` FROM webfilter_aclsdynamic_rights WHERE ID={$ID}"));
	$tpl=new templates();
	$TypeOf[0]="{select}";
	$TypeOf[1]="{active_directory_group}";
	$TypeOf[2]="{member}";
	$TypeOf[3]="{ldap_group}";
	
	$title=$tpl->javascript_parse_text("{delete}")." ". $tpl->javascript_parse_text($TypeOf[$ligne["type"]])." ".$ligne["pattern"]." ?";
	
	$t=time();
$html="
var xSave$t=function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#ACL_PRIVS_GROUP_TABLE').flexReload();	
}	
	
function Save$t(){
	if(!confirm('$title')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete','$ID');
	XHR.sendAndLoad('$page', 'POST',xSave$t);		
}
	
 Save$t();";


echo $html;
	
}

function delete(){
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM webfilter_aclsdynamic_rights WHERE ID='{$_POST["delete"]}'");
	if(!$q->ok){echo $q->mysql_error;}
}

function add_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$TypeOf[0]="{select}";
	$TypeOf[1]="{active_directory_group}";
	$TypeOf[2]="{member}";
	$TypeOf[3]="{ldap_group}";
	$t=time();
	
	$html="<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{type}:</td>
		<td>". Field_array_Hash($TypeOf,"type-$t",0,"Change$t()",'',0,"font-size:22px",false)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{attribute}:</td>
		<td><span id='$t'></span></td>
	</tr>
	<tr style='height:250px'>
		<td colspan=2 align='right'><hr>". button("{add}", "Save$t()",34)."</td>	
	</tr>
	</table>
	</div>
	<script>
function Change$t(){
	var type=document.getElementById('type-$t').value;
	LoadAjaxSilent('$t','$page?add-fillfield=yes&t=$t&type='+type);
}
	
var xSave$t=function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#ACL_PRIVS_GROUP_TABLE').flexReload();	
	YahooWin5Hide();
}	
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('type',document.getElementById('type-$t').value);
	XHR.appendData('pattern',document.getElementById('pattern-$t').value);
	XHR.appendData('gpid','{$_GET["gpid"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);		
}

function FillLDAPGroup$t(pattern){
	document.getElementById('pattern-$t').value=pattern;

}

</script>
	";
echo $tpl->_ENGINE_parse_body($html);
	
}

function add_fillfield(){
	$t=$_GET["t"];
	$type=$_GET["type"];
	$tpl=new templates();
	if($type==2){
		echo Field_text("pattern-$t",null,"font-size:22px;width:98%")."<br>".
		$tpl->_ENGINE_parse_body("<div style='font-size:18px;text-align:right'>{explain_whiteis_a_member}</div>");
		return;
	}
	
	if($type==1){
		echo Field_text("pattern-$t",null,"font-size:22px;width:440px",null,null,null,false,null,true,null)."&nbsp;".$tpl->_ENGINE_parse_body(button("{browse}", "Loadjs('browse-ad-groups.php?field-user=pattern-$t&field-type=2&CallBack2=');",22));
	}
	if($type==3){
		echo Field_text("pattern-$t",null,"font-size:22px;width:440px",null,null,null,false,null,true,null)."&nbsp;".$tpl->_ENGINE_parse_body(button("{browse}", "Loadjs('browse-ldap-groups.php?function=FillLDAPGroup$t')",22));
	}	
	
	
}

function save(){
	$type=$_POST["type"];
	
	$pattern=$_POST["pattern"];
	
	if($type==1){
		if(!preg_match("#AD:[0-9]+:(.+)#", $_POST["pattern"],$re)){
			echo "Pattern did not match !";
			return;
		}
		$pattern=base64_decode($re[1]);
		
		
	}
	
	if($pattern==null){echo "FALSE, need a privilege...\n";return;}
	
	$gpid=$_POST["gpid"];
	$pattern=mysql_escape_string2($pattern);
	$sql="INSERT INTO webfilter_aclsdynamic_rights (gpid,pattern,`type`) VALUES ('$gpid','$pattern','$type')";
	$q=new mysql_squid_builder();
	
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `webfilter_aclsdynamic_rights` (
			`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			`gpid` INT UNSIGNED,
			`type` smallint(1) NOT NULL,
			`pattern` VARCHAR(255) NOT NULL,
			KEY `type` (`type`),
			KEY `pattern` (`pattern`) ) ENGINE = MYISAM;");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	
	
}




function table(){
	$ID=$_GET["ID"];
	$Adbig=null;
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT GroupName FROM webfilters_sqgroups WHERE ID={$_GET["gpid"]}"));
	$q->CheckTables();
	$type=$tpl->_ENGINE_parse_body("{type}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$new_group=$tpl->_ENGINE_parse_body("{new_proxy_object}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$title=$tpl->javascript_parse_text("{$ligne["GroupName"]}:{privileges}");
	$delete_group_ask=$tpl->javascript_parse_text("{inputbox delete group}");
	$rules=$tpl->javascript_parse_text("{rules}");
	$new_privilege=$tpl->javascript_parse_text("{new_privilege}");
	$t=time();
	
	

	
	$html="
<table class='ACL_PRIVS_GROUP_TABLE' style='display: none' id='ACL_PRIVS_GROUP_TABLE' style='width:99%'></table>
<script>
var DeleteSquidAclGroupTemp=0;
	$(document).ready(function(){
	$('#ACL_PRIVS_GROUP_TABLE').flexigrid({
	url: '$page?groups-list=yes&gpid={$_GET["gpid"]}',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:16px>$type</span>', name : 'type', width : 142, sortable : true, align: 'left'},
	{display: '<span style=font-size:16px>$items</span>', name : 'pattern', width : 310, sortable : false, align: 'left'},
	{display: '&nbsp;', name : 'none3', width : 32, sortable : false, align: 'center'},
	
	],
	buttons : [
	{name: '<strong style=font-size:16px>$new_privilege</strong>', bclass: 'add', onpress : AddPriv$t},
	
	],
	searchitems : [
	{display: '$items', name : 'pattern'},
	],
	sortname: 'pattern',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:18px>$title</span>',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true
	
	});
	});
function AddPriv$t() {
	Loadjs('$page?add-js=yes&gpid={$_GET["gpid"]}');
}
	
	function RefreshSquidGroupTable(){
	$('#table-$t').flexReload();
	var tableorg='{$_GET["table-org"]}';
	if(tableorg.length>3){ $('#'+tableorg).flexReload();}
	}
	
	function RemoveAllAclsGroups(){
	if(!confirm('$remove_all_groups ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('DeleteAllGroup', 'yes');
	XHR.sendAndLoad('$page', 'POST',x_DeleteSquidAclGroup);
	}
	
	
	
	
	
	var x_DeleteSquidAclGroup= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	if(document.getElementById('main_filter_rule_edit')){RefreshTab('main_filter_rule_edit');}
	if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
	$('#rowtime'+TimeRuleIDTemp).remove();
	RefreshAllAclsTables();
	var tableorg='{$_GET["table-org"]}';
	if(tableorg.length>3){ $('#'+tableorg).flexReload();}
	
	ExecuteByClassName('SearchFunction');
	}
	
	var x_EnableDisableGroup= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	RefreshAllAclsTables();
	
	
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
	RefreshAllAclsTables();
	var tableorg='{$_GET["table-org"]}';
	if(tableorg.length>3){ $('#'+tableorg).flexReload();}
	
	
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
function group_list(){
	$q=new mysql_squid_builder();
	$table="webfilter_aclsdynamic_rights";
	$MyPage=CurrentPageName();
	$tpl=new templates();
	$page=1;
	$FORCE_FILTER="AND gpid='{$_GET["gpid"]}'";
	$total=0;
	if(!$q->TABLE_EXISTS($table)){json_error_show("$table, no such table",1);}
	if($q->COUNT_ROWS($table)==0){json_error_show("Table empty",1);}
	
	
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			if($_POST["sortname"]=="zDate"){$_POST["sortname"]="hour";}
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}
	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
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
	
		$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
		$results = $q->QUERY_SQL($sql);
		if(!$q->ok){json_error_show("$q->mysql_error");}
		
		if(mysql_num_rows($results)==0){json_error_show("no data");}
	
		$data = array();
		$data['page'] = $page;
		$data['total'] = $total;
		$data['rows'] = array();
		

		$TypeOf[1]=$tpl->javascript_parse_text("{active_directory_group}");
		$TypeOf[2]=$tpl->javascript_parse_text("{member}");
		$TypeOf[3]=$tpl->javascript_parse_text("{ldap_group}");
	
		while ($ligne = mysql_fetch_assoc($results)) {
			$md5=md5(@implode(" ", $ligne));
			$type=$ligne["type"];
			$pattern=$ligne["pattern"];
			$ID=$ligne["ID"];
			$js=null;
			$delete=imgtootltip("delete-24.png",null,"Loadjs('$MyPage?delete-js=$ID')");
			
			if($type==3){
				$gp=new groups($pattern);
				$js="<a href=\"javascript:blur()\"
				OnClick=\"javascript:Loadjs('domains.edit.group.php?js=yes&group-id=$pattern',true);\"
				style='font-size:14px;text-decoration:underline'>";
				$pattern=$gp->groupName;
				
				
			}
	
			$data['rows'][] = array(
					'id' => $md5,
					'cell' => array(
							"<span style='font-size:14px'>{$TypeOf[$type]}</span>",
							"<span style='font-size:14px'>$js$pattern</a></span>",
							"<center style='font-size:14px'>$delete</center>",
	
								
					)
			);
		}
	
	
		echo json_encode($data);
	}
