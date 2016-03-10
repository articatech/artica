<?php
$GLOBALS["ICON_FAMILY"]="SYSTEM";
//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.shorewall.inc');
include_once('ressources/class.system.nics.inc');
$usersmenus=new usersMenus();
if(!$usersmenus->AsArticaAdministrator){die();}	
if(isset($_GET["item-js"])){item_js();exit;}
if(isset($_GET["item-popup"])){item_popup();exit;}
if(isset($_GET["items"])){items();exit;}
if(isset($_GET["delete-js"])){items_delete_js();exit;}
if(isset($_POST["delete"])){items_delete();exit;}
if(isset($_POST["ID"])){save();exit;}
table();

function item_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$q=new mysql_squid_builder();
	$linkid=$_GET["ID"];
	if($linkid>0){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `hostname` FROM hotspot_whitelist WHERE ID='$linkid'"));
		$t=$_GET["t"];
		$tt=time();
		$pattern=$tpl->javascript_parse_text("{hostname}: {$ligne["hostname"]}");
	}else{
		$pattern=$tpl->javascript_parse_text("{new_website}");
	}
	
	$html="YahooWin3('650','$page?item-popup=yes&ID=$linkid&t={$_GET["t"]}','$pattern',true);";
	echo $html;

}
function items_delete_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$q=new mysql_squid_builder();
	$linkid=$_GET["delete-js"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `hostname`,`ipaddr` FROM hotspot_whitelist WHERE ID='$linkid'"));
	$t=$_GET["t"];
	$tt=time();
	$pattern=$tpl->javascript_parse_text("{delete} {hostname}: {$ligne["hostname"]} - {$ligne["ipaddr"]} ?");
	$html="
var xSave$tt= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#HOTSPOT_TRUSTED_WEBSITES_TABLE').flexReload();
	
}

	

function Save$tt(){
	var XHR = new XHRConnection();
	if(!confirm('$pattern')){return;} 
	XHR.appendData('delete',  '$linkid');
	XHR.sendAndLoad('$page', 'POST',xSave$tt);
}			
	
Save$tt();	
	";
	echo $html;

}

function items_delete(){
	$q=new mysql_squid_builder();
	$table="hotspot_networks";
	if(!$q->TABLE_EXISTS($table)){$q->CheckTables();}
	$q->QUERY_SQL("DELETE FROM hotspot_whitelist WHERE ID={$_POST["delete"]}");
	if(!$q->ok){echo $q->mysql_error;return;}

}

function item_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$button="{add}";
	$ID=$_GET["ID"];
	if($ID>0){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM hotspot_whitelist WHERE ID='$ID'"));
		$ipaddr=$ligne["ipaddr"];
		$hostname=$ligne["hostname"];
		$button="{apply}";
	}
	
	$t=time();
	$html="<div class=form style='width:95%'>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{hostname} {or} {ipaddr}:</td>
		<td>". Field_text("hostname-$t",$hostname,"font-size:22px;font-weight:bold;width:300px",null,null,null,false,"SaveCHK$t(event)")."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:22px'>{hostname}:</td>
		<td style='font-size:22px'>$hostname</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{ipaddr}:</td>
		<td style='font-size:22px'>$ipaddr</td>
	</tr>				
<tr>
	<td colspan=2 align='right'><hr>". button($button,"Save$t();",22)."</td>
</tr>
				
	</table></div>	
<script>
var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	var ID='$ID';
	$('#HOTSPOT_TRUSTED_WEBSITES_TABLE').flexReload();
	if(ID==0){YahooWin3Hide();}
}

function SaveCHK$t(e){
	if(!checkEnter(e)){return;}
	Save$t();
}
	

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ID',  '$ID');
	XHR.appendData('hostname',  document.getElementById('hostname-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>	";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function Save(){
	
	$q=new mysql_squid_builder();
	$table="hotspot_whitelist";
	if(!$q->TABLE_EXISTS($table)){$q->CheckTables();}
	$ipClass=new IP();
	$sock=new sockets();
	$hostname=$_POST["hostname"];
	if($ipClass->isValid($hostname)){
		$_POST["ipaddr"]=$hostname;
	}else{
		$_POST["ipaddr"]=gethostbyname($hostname);
	}
	
	if(!$ipClass->isValid($_POST["ipaddr"])){
		echo "$hostname, unable to resolve '{$_POST["ipaddr"]}'...\n";
		return;
	}

	$editF=false;
	$ID=$_POST["ID"];
	unset($_POST["ID"]);

	


	while (list ($key, $value) = each ($_POST) ){
		$value=url_decode_special_tool($value);
		$fields[]="`$key`";
		$values[]="'".mysql_escape_string2($value)."'";
		$edit[]="`$key`='".mysql_escape_string2($value)."'";

	}

	$sql_edit="UPDATE `$table` SET ".@implode(",", $edit)." WHERE ID='$ID'";
	$sql="INSERT IGNORE INTO `$table` (".@implode(",", $fields).") VALUES (".@implode(",", $values).")";
	if($ID>0){$sql=$sql_edit;}

	$q->QUERY_SQL($sql);
	if(!$q->ok){echo "Mysql error: `$q->mysql_error`";;return;}


}

function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$tt=time();
	$t=$_GET["t"];
	$_GET["ruleid"]=$_GET["ID"];
	$groups=$tpl->javascript_parse_text("{groups}");
	$from=$tpl->_ENGINE_parse_body("{from}");
	$to=$tpl->javascript_parse_text("{to}");
	$rule=$tpl->javascript_parse_text("{rule}");
	$delete=$tpl->javascript_parse_text("{delete} {zone} ?");
	$rewrite_rules_fdb_explain=$tpl->javascript_parse_text("{rewrite_rules_fdb_explain}");
	$new_website=$tpl->javascript_parse_text("{new_website}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$rules=$tpl->javascript_parse_text("{rules}");
	$rule=$tpl->javascript_parse_text("{rule}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$action=$tpl->javascript_parse_text("{action}");
	$website=$tpl->javascript_parse_text("{website}");
	$restricted=$tpl->javascript_parse_text("{restricted}");
	$title=$tpl->_ENGINE_parse_body("{trusted_websites}");
	$About=$tpl->javascript_parse_text("{about2}");
	$hotspot_whitelist_www_explain=$tpl->javascript_parse_text("{hotspot_whitelist_www_explain}");
	$tt=time();
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$new_website</strong>', bclass: 'add', onpress : NewRule$tt},
	{name: '<strong style=font-size:18px>$About</strong>', bclass: 'Reconf', onpress : About$tt},
	{name: '<strong style=font-size:18px>$apply</strong>', bclass: 'Reconf', onpress : Apply$tt},
	

	],";

	$html="
	<table class='HOTSPOT_TRUSTED_WEBSITES_TABLE' style='display: none' id='HOTSPOT_TRUSTED_WEBSITES_TABLE' style='width:100%'></table>
	<script>
	function Start$tt(){
	$('#HOTSPOT_TRUSTED_WEBSITES_TABLE').flexigrid({
	url: '$page?items=yes&t=$tt&tt=$tt&t-rule={$_GET["t"]}&ruleid={$_GET["ruleid"]}',
	dataType: 'json',
	colModel : [
	
	{display: '<span style=font-size:22px>$website</span>', name : 'hostname', width :989, sortable : true, align: 'left'},
	{display: '<span style=font-size:22px>&nbsp;</span>', name : 'delete', width : 100, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$website', name : 'hostname'},
	{display: '$ipaddr', name : 'ipaddr'},
	],
	sortname: 'hostname',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
}

var xNewRule$tt= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#HOTSPOT_TRUSTED_WEBSITES_TABLE').flexReload();
	
}

function About$tt(){
	alert('$hotspot_whitelist_www_explain');
}

function Apply$tt(){
	Loadjs('squid.hostspot.reconfigure.php');
}


function NewRule$tt(){
	Loadjs('$page?item-js=yes&ID=0&t=$tt',true);
}
function Delete$tt(zmd5){
	if(confirm('$delete')){
		var XHR = new XHRConnection();
		XHR.appendData('policy-delete', zmd5);
		XHR.sendAndLoad('$page', 'POST',xNewRule$tt);
	}
}

var xINOUT$tt= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#HOTSPOT_TRUSTED_WEBSITES_TABLE').flexReload();
	
}


function INOUT$tt(ID){
	var XHR = new XHRConnection();
	XHR.appendData('INOUT', ID);
	XHR.sendAndLoad('$page', 'POST',xINOUT$tt);
}

function reverse$tt(ID){
	var XHR = new XHRConnection();
	XHR.appendData('reverse', ID);
	XHR.sendAndLoad('$page', 'POST',xINOUT$tt);
}

var x_LinkAclRuleGpid$tt= function (obj) {
var res=obj.responseText;
if(res.length>3){alert(res);return;}
$('#HOTSPOT_TRUSTED_WEBSITES_TABLE').flexReload();
ExecuteByClassName('SearchFunction');
}
function FlexReloadRulesRewrite(){
$('#HOTSPOT_TRUSTED_WEBSITES_TABLE').flexReload();
}

function MoveRuleDestination$tt(mkey,direction){
var XHR = new XHRConnection();
XHR.appendData('rules-destination-move', mkey);
XHR.appendData('direction', direction);
XHR.sendAndLoad('$page', 'POST',x_LinkAclRuleGpid$tt);
}

function MoveRuleDestinationAsk$tt(mkey,def){
var zorder=prompt('Order',def);
if(!zorder){return;}
var XHR = new XHRConnection();
XHR.appendData('rules-destination-move', mkey);
XHR.appendData('rules-destination-zorder', zorder);
XHR.sendAndLoad('$page', 'POST',x_LinkAclRuleGpid$tt);
}
Start$tt();

</script>
";
echo $html;

}
function items(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();

	$t=$_GET["t"];
	$search='%';
	$table="hotspot_whitelist";
	$page=1;
	$FORCE_FILTER=null;
	$total=0;


	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];

	}else{
		$total = $q->COUNT_ROWS($table);
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	if(is_numeric($rp)){$limitSql = "LIMIT $pageStart, $rp";}

	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql);

	$no_rule=$tpl->_ENGINE_parse_body("{no_item}");

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$fontsize="18";
	$color="black";
	$check32="<img src='img/check-32.png'>";
	$AllSystems=$tpl->_ENGINE_parse_body("{AllSystems}");
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql",1);}
	if(mysql_num_rows($results)==0){json_error_show("no data"); return;}

	$fontsize="26";
	$check32="<img src='img/check-32.png'>";
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		
		$delete=imgsimple("delete-48.png",null,"Loadjs('$MyPage?delete-js={$ligne["ID"]}&t={$_GET["t"]}',true)");
		$hostname=$ligne["hostname"];
		$ipaddr=$ligne["ipaddr"];
		$port=$ligne["port"];
		$link="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?item-js=yes&ID={$ligne["ID"]}&t={$_GET["t"]}',true)\"
		style='font-size:{$fontsize}px;font-weight:normal;color:$color;text-decoration:underline'>";
		
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$link$hostname&nbsp;-&nbsp;$ipaddr</a></span>",
						"<center style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$delete</center>",)
		);
	}


	echo json_encode($data);

}