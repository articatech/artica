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
	
	
	if(isset($_GET["ByJs"])){ByJs();exit;}
	if(isset($_GET["delete-js"])){delete_js();exit;}
	if(isset($_GET["rules-list"])){rules_list();exit;}
	if(isset($_GET["rule-id-js"])){ruleid_js();exit;}
	if(isset($_GET["ruleid"])){ruleid_popup();exit;}
	if(isset($_POST["ruleid"])){ruleid_save();exit;}
	if(isset($_POST["delete"])){delete();exit;}
	table();
	
function delete_js(){
		$page=CurrentPageName();
		$ID=$_GET["delete-js"];
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `value`,`type` FROM webfilter_aclsdynamic WHERE ID={$ID}"));
		$tpl=new templates();
		$title=$tpl->javascript_parse_text("{delete}")." {$ligne["value"]} ?";
	
$t=time();
$html="
var xSave$t=function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#ACL_DYNAMIC_ACLS_TABLE').flexReload();
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
	$q->QUERY_SQL("DELETE FROM webfilter_aclsdynamic WHERE ID={$_POST["delete"]}");
	if(!$q->ok){echo $q->mysql_error;}
}
	
function ruleid_save(){
	$q=new mysql_squid_builder();
	
	$tpl=new templates();
	$gpid=$_POST["gpid"];
	$ruleid=$_POST["ruleid"];
	$function=__FUNCTION__;
	$file=__FILE__;
	$lineNumber=__LINE__;
	$sock=new sockets();
	$hostname=$sock->getFrameWork("system.php?hostname-g=yes");
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT GroupName FROM webfilters_sqgroups WHERE ID=$gpid"));
	$GroupName=$ligne["GroupName"];
	
	if($_POST["description"]==null){
		$_POST["description"]=$tpl->javascript_parse_text("{$q->acl_GroupTypeDynamic[$_POST["type"]]} = {$_POST["value"]}");
	}
	
	if(!$q->FIELD_EXISTS("webfilter_aclsdynamic", "maxtime")){
		$q->QUERY_SQL("ALTER TABLE `webfilter_aclsdynamic` ADD `maxtime` INT UNSIGNED ,
		ADD INDEX ( `maxtime` )");
	}
	if(!$q->FIELD_EXISTS("webfilter_aclsdynamic", "duration")){
		$q->QUERY_SQL("ALTER TABLE `webfilter_aclsdynamic` ADD `duration` INT UNSIGNED ,
		ADD INDEX ( `duration` )");
	}
	
	if(!$q->TABLE_EXISTS("webfilter_aclsdynamic")){$q->CheckTables();}
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT params FROM webfilters_sqgroups WHERE ID='$gpid'"));
	$tpl=new templates();
	$params=unserialize(base64_decode($ligne["params"]));
	
	$finaltime=0;
	$duration=0;
	if(isset($_POST["duration"])){
		if($params["allow_duration"]==1){
			if($_POST["duration"]>0){
				$duration=$_POST["duration"];
				$finaltime = strtotime("+{$_POST["duration"]} minutes", time());
			}
		}
	}
	
	
	if($params["allow_duration"]==0){
		if($params["duration"]>0){
			$duration=$params["duration"];
			$finaltime = strtotime("+{$params["duration"]} minutes", time());
		}
	}
	$q=new mysql_squid_builder();
	$uid=mysql_escape_string2($_SESSION["uid"]);
	$_POST["value"]=url_decode_special_tool($_POST["value"]);
	if($ruleid>0){$logtype="Update item";}else{$logtype="Create item";}
	

	
	$description_log="{$q->acl_GroupTypeDynamic[$ligne["type"]]} {$_POST["value"]} {$_POST["description"]}";
	$zdate=date("Y-m-d H:i:s");
	
		
		$q2=new mysql();
		$description_log=mysql_escape_string2($description_log);
		$q2->QUERY_SQL("INSERT IGNORE INTO `squid_admin_mysql`
		(`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`,`hostname`) VALUES
		('$zdate','$description_log','{$logtype} in proxy object $GroupName ','$function','$file','$lineNumber','2','$hostname')","artica_events");
	
		
		$_POST["description"]=url_decode_special_tool($_POST["description"]);
		$_POST["description"]=mysql_escape_string2($_POST["description"]);
		
		$_POST["value"]=url_decode_special_tool($_POST["value"]);
		$_POST["value"]=mysql_escape_string2($_POST["value"]);
		
		if($ruleid>0){
			$sql="UPDATE webfilter_aclsdynamic
			SET `type`='{$_POST["type"]}',
			`value`='{$_POST["value"]}',
			`description`='{$_POST["description"]}',
			`maxtime`='$finaltime',
			`duration`='$duration',
			`enabled`='{$_POST["enabled"]}'
			WHERE ID=$ruleid
			";
	
		}else{
			
			$sql="INSERT IGNORE INTO webfilter_aclsdynamic 
			(`gpid`,`type`,`value`,`description`,`who`,`maxtime`,`duration`,`enabled`)
			VALUES ('$gpid','{$_POST["type"]}','{$_POST["value"]}','{$_POST["description"]}',
			'$uid','{$finaltime}','$duration','{$_POST["enabled"]}')";
		}
	
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error."\n$sql\n";return;}
	}	
	
function ruleid_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$id=$_GET["ruleid"];
	if(!is_numeric($id)){$id=0;}
	$gpid=$_GET["gpid"];
	$buttonname="{add}";
	if($id>0){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilter_aclsdynamic WHERE `ID`='$id'"));
		$buttonname="{apply}";
		$gpid=$ligne["gpid"];
	}
	
	$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT params FROM webfilters_sqgroups WHERE ID='$gpid'"));
	$params=unserialize(base64_decode($ligne2["params"]));
	if($id==0){
		$ligne["duration"]=$params["duration"];
		$ligne["enabled"]=1;
	}
	
	
	$t=time();
	
	
	if(!is_numeric($gpid)){$gpid=0;}
	$ligne["description"]=stripslashes($ligne["description"]);
	
	
		$durations[0]="{unlimited}";
		$durations[5]="05 {minutes}";
		$durations[10]="10 {minutes}";
		$durations[15]="15 {minutes}";
		$durations[30]="30 {minutes}";
		$durations[60]="1 {hour}";
		$durations[120]="2 {hours}";
		$durations[240]="4 {hours}";
		$durations[480]="8 {hours}";
		$durations[720]="12 {hours}";
		$durations[960]="16 {hours}";
		$durations[1440]="1 {day}";
		$durations[2880]="2 {days}";
		$durations[5760]="4 {days}";
		$durations[10080]="1 {week}";
		$durations[20160]="2 {weeks}";
		$durations[43200]="1 {month}";
	
	
	
		
		
		
		$html[]="<div style='width:98%' class=form>
		<div style='font-size:18px' class=explain>{dynaacl_howto}</div>
		<table style='width:100%'>	
		<tr>
			<td class=legend style='font-size:22px'>{enabled}:</td>
			<td>". Field_checkbox_design("enabled-$t",1,$ligne["enabled"], "EnableCheck$t()")."</td>
		</tr>					
		<tr>
			<td class=legend style='font-size:22px'>{type}:</td>
			<td>". Field_array_Hash($q->acl_GroupTypeDynamic,"type-$t",$ligne["type"], "style:font-size:22px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>{value}:</td>
			<td>". Field_text("value-$t",$ligne["value"],"font-size:22px;width:98%")."</td>
		</tr>
		";
		if($params["allow_duration"]==1){
			$html[]="		<tr>
			<td class=legend style='font-size:22px'>{time_duration}:</td>
			<td>". Field_array_Hash($durations,$ligne["duration"], "duration-$t",$ligne["type"])."</td>
		</tr>";
			
		}
		$html[]="<tr>
			<td class=legend style='font-size:22px'>{description}:</td>
			<td>". Field_text("description-$t",utf8_encode($ligne["description"]),"font-size:22px;width:98%")."</td>
		</tr>";
	
		
		$html[]="<tr>
		<td colspan=2 align='right'><hr>". button($buttonname,"Save$t()",30)."</td></tr>";

	
		
		$html[]="
<script>
var xSave$t=function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#ACL_DYNAMIC_ACLS_TABLE').flexReload();	
	YahooWin5Hide();
}	
	
function Save$t(){
	var XHR = new XHRConnection();
	var enabled=0;
	if(document.getElementById('enabled-$t').checked){enabled=1;}
	XHR.appendData('type',document.getElementById('type-$t').value);
	XHR.appendData('value',encodeURIComponent(document.getElementById('value-$t').value));
	XHR.appendData('gpid','{$_GET["gpid"]}');
	XHR.appendData('ruleid','{$_GET["ruleid"]}');
	XHR.appendData('enabled',enabled);
	if(document.getElementById('duration-$t')){
		XHR.appendData('duration',document.getElementById('duration-$t').value);
	}
	XHR.appendData('description',encodeURIComponent(document.getElementById('description-$t').value));
	XHR.sendAndLoad('$page', 'POST',xSave$t);		
}				

function EnableCheck$t(){
	document.getElementById('type-$t').disabled=true;
	document.getElementById('value-$t').disabled=true;
	if(document.getElementById('duration-$t')){
		document.getElementById('duration-$t').disabled=true;
	}
	document.getElementById('description-$t').disabled=true;
	
	if(!document.getElementById('enabled-$t').checked){return;}
	
	
	document.getElementById('type-$t').disabled=false;
	document.getElementById('value-$t').disabled=false;
	if(document.getElementById('duration-$t')){
		document.getElementById('duration-$t').disabled=false;
	}
	document.getElementById('description-$t').disabled=false;	

}
EnableCheck$t();
</script>				
";
		
		echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	}	
	
function ByJs(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$gpid=$_GET["gpid"];	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT GroupName FROM webfilters_sqgroups WHERE ID=$gpid"));
	echo "YahooWin4('1495','$page?gpid=$gpid','{$ligne["GroupName"]}');";
	
}
	
function ruleid_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$id=$_GET["rule-id-js"];
	$users=new usersMenus();
	
	if($id==0){
		$title=$tpl->_ENGINE_parse_body("{new_rule}");
	}else{
		if(!$users->AsDansGuardianAdministrator){
			if(!isset($_SESSION["SQUID_DELEGATE_ACLS"][$_GET["gpid"]])){
				echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
				die();
			
			}
		}
		
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT type,value FROM webfilter_aclsdynamic WHERE `ID`='$id'"));
		$title=$tpl->_ENGINE_parse_body("{$q->acl_GroupTypeDynamic[$ligne["type"]]}::{$ligne["value"]}");
	}
	
	echo "YahooWin5('600','$page?ruleid=$id&t={$_GET["t"]}&gpid={$_GET["gpid"]}','$title')";
}	

function table(){
	$gpid=intval($_GET["gpid"]);
	$users=new usersMenus();
	if($gpid==0){
		echo FATAL_ERROR_SHOW_128("{please_choose_a_correct_rule}");
		die();
	}
	
	if(!$users->AsDansGuardianAdministrator){
		if(!isset($_SESSION["SQUID_DELEGATE_ACLS"][$gpid])){
			echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
			die();		
			
		}
	}
	
	$Adbig=null;
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT GroupName FROM webfilters_sqgroups WHERE ID=$gpid"));
	$q->CheckTables();
	$type=$tpl->_ENGINE_parse_body("{type}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$new_group=$tpl->_ENGINE_parse_body("{new_proxy_object}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$title=$tpl->javascript_parse_text("{rule}: {$ligne["GroupName"]}");
	$delete_group_ask=$tpl->javascript_parse_text("{inputbox delete group}");
	$rules=$tpl->javascript_parse_text("{rules}");
	$new_rule=$tpl->javascript_parse_text("{new_rule}");
	$value=$tpl->javascript_parse_text("{value}");
	$description=$tpl->javascript_parse_text("{description}");
	$t=time();




	$html="
	<table class='ACL_DYNAMIC_ACLS_TABLE' style='display: none' id='ACL_DYNAMIC_ACLS_TABLE' style='width:99%'></table>
	<script>
	var DeleteSquidAclGroupTemp=0;
	$(document).ready(function(){
	$('#ACL_DYNAMIC_ACLS_TABLE').flexigrid({
	url: '$page?rules-list=yes&gpid=$gpid',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:18px>$type</span>', name : 'type', width : 236, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$value</span>', name : 'value', width : 310, sortable : false, align: 'left'},
	{display: '<span style=font-size:18px>$description</span>', name : 'description', width : 736, sortable : false, align: 'left'},
	{display: '&nbsp;', name : 'none3', width : 80, sortable : false, align: 'center'},

	],
	buttons : [
	{name: '<strong style=font-size:16px>$new_rule</strong>', bclass: 'add', onpress : Addrule$t},

	],
	searchitems : [
	{display: '$value', name : 'value'},
	],
	sortname: 'value',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true

});
});
function Addrule$t() {
Loadjs('$page?rule-id-js=0&gpid={$_GET["gpid"]}');
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

function rules_list(){
	$durations[0]="{unlimited}";
	$durations[5]="05 {minutes}";
	$durations[10]="10 {minutes}";
	$durations[15]="15 {minutes}";
	$durations[30]="30 {minutes}";
	$durations[60]="1 {hour}";
	$durations[120]="2 {hours}";
	$durations[240]="4 {hours}";
	$durations[480]="8 {hours}";
	$durations[720]="12 {hours}";
	$durations[960]="16 {hours}";
	$durations[1440]="1 {day}";
	$durations[2880]="2 {days}";
	$durations[5760]="4 {days}";
	$durations[10080]="1 {week}";
	$durations[20160]="2 {weeks}";
	$durations[43200]="1 {month}";
	$q=new mysql_squid_builder();
	$table="webfilter_aclsdynamic";
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



	while ($ligne = mysql_fetch_assoc($results)) {
		$md5=md5(@implode(" ", $ligne));
		$type=$ligne["type"];
		$pattern=$ligne["pattern"];
		$ID=$ligne["ID"];
		$duration=null;
		$finish=null;
		$color="black";
		
		$js="Loadjs('$MyPage?rule-id-js={$ligne["ID"]}&gpid={$_GET["gpid"]}');";
		$delete=imgtootltip("delete-32.png",null,"Loadjs('$MyPage?delete-js=$ID&gpid={$_GET["gpid"]}')");
		
		$type=$tpl->_ENGINE_parse_body("{$q->acl_GroupTypeDynamic[$ligne["type"]]}");
		$ligne["who"]=str_replace("-100", "SuperAdmin", $ligne["who"]);
		if($ligne["who"]<>null){$ligne["who"]="By:{$ligne["who"]}";}
		
		if($ligne["duration"]>0){
			if($ligne["maxtime"]>time()){
				$finish=distanceOfTimeInWords(time(),$ligne["maxtime"]);
			}
			$duration="&nbsp;<span style='font-weight:bold;font-size:16px'><i>{$durations[$ligne["duration"]]} ({delete}: {$finish})</i></span>";
				
		}
		$duration=$tpl->_ENGINE_parse_body($duration);
		$description="{$ligne["description"]}$duration";
		$pattern=$ligne["value"];
		$href="href=\"javascript:blur();\" OnClick=\"javascript:$js\"";
		if($ligne["enabled"]==0){$color="#8a8a8a";}

		$data['rows'][] = array(
				'id' => $md5,
				'cell' => array(
						"<a $href style='font-size:22px;text-decoration:underline;color:$color'>{$type}</span>",
						"<a $href style='font-size:22px;text-decoration:underline;color:$color'>$pattern</span>",
						"<span style='font-size:22px;color:$color'>$description</span>",
						"<center style='font-size:22px'>$delete</center>",


				)
		);
	}


	echo json_encode($data);
}