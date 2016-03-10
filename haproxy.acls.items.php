<?php
	if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.haproxy.inc');
	
	if(isset($_GET["AddItem-js"])){item_popup_js();exit;}
	if(isset($_GET["AddItem-tab"])){item_tab();exit;}
	if(isset($_GET["AddItem-popup"])){item_form();exit;}
	if(isset($_GET["items-list"])){items_list();exit;}
	if(isset($_GET["AddItem-import"])){item_form_import();exit;}
	
	if(isset($_POST["item-import"])){item_import();exit;}
	if(isset($_POST["item-pattern"])){item_save();exit;}
	if(isset($_POST["DeleteItem"])){item_delete();exit;}
	if(isset($_POST["item-import"])){item_import();exit;}
	
items_table();	


function item_popup_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["item-id"];
	if($ID>0){
		$title="{item}:$ID";
	}
	
	if($ID<0){$title="{new_item}";}
	$title=$tpl->_ENGINE_parse_body($title);
	$html="YahooWin5(1027,'$page?AddItem-tab=yes&item-id=$ID&gpid={$_GET["gpid"]}','$title::{$_GET["gpid"]}')";
	echo $html;
}
function item_delete(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_POST["ID"];
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM haproxy_acls_items WHERE ID='$ID'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	

}
function item_tab(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["gpid"];
	$t=$_GET["t"];
	

	$array["AddItem-popup"]='{item}';
	$array["AddItem-import"]='{import}';
	if($_GET["item-id"]>0){
		unset($array["AddItem-import"]);
	}

	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT grouptype FROM haproxy_acls_groups WHERE ID=$ID","artica_backup"));
	if($ligne["GroupType"]=="method"){unset($array["AddItem-import"]);}
	if($ligne["GroupType"]=="dynamic_acls"){unset($array["AddItem-import"]);}
	if($ligne["GroupType"]=="categories"){unset($array["AddItem-import"]);}
	if($ligne["GroupType"]=="myportname"){unset($array["AddItem-import"]);}

	while (list ($num, $ligne) = each ($array) ){

		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&item-id={$_GET["item-id"]}&gpid={$_GET["gpid"]}\"><span style='font-size:18px'>$ligne</span></a></li>\n");

	}


	echo build_artica_tabs($html, "haproxy_aclm_item_add");

}

function items_table(){
	$ID=$_GET["gpid"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$items=$tpl->_ENGINE_parse_body("{items}");
	$new_item=$tpl->javascript_parse_text("{new_item}");
	$t=time();

	$Additem_js="Loadjs('$page?AddItem-js=yes&item-id=-1&gpid=$ID');";

	$html="
	<table class='HAPROXY_ACLS_ITEMS_TABLE' style='display: none' id='HAPROXY_ACLS_ITEMS_TABLE' style='width:99%'></table>
	<script>
	var DeleteHAPGroupItemTemp=0;
	$(document).ready(function(){
	$('#HAPROXY_ACLS_ITEMS_TABLE').flexigrid({
	url: '$page?items-list=yes&gpid=$ID',
	dataType: 'json',
	colModel : [
	{display: '$items', name : 'pattern', width : 386, sortable : true, align: 'left'},
	{display: '', name : 'none3', width : 36, sortable : false, align: 'center'},

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
	width: '99%',
	height: 250,
	singleSelect: true

});
});
function AddItem() {
$Additem_js

}

function RefreshSquidGroupItemsTable(){
$('#table-$t').flexReload();
}


var x_DeleteHAPGroupItem= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#rowitem'+DeleteHAPGroupItemTemp).remove();
	$('#HAPROXY_ACLS_TABLE').flexReload();
	$('#HAPROXY_OBJECTS_LIST_ACLS').flexReload();
	$('#HAPROXY_ACLS_ITEMS_TABLE').flexReload();
}

var x_EnableDisableGroup= function (obj) {
var res=obj.responseText;
if(res.length>3){alert(res);return;}
RefreshAllAclsTables();
ExecuteByClassName('SearchFunction');
}

function DeleteHAPGroupItem(ID){
DeleteHAPGroupItemTemp=ID;
var XHR = new XHRConnection();
XHR.appendData('DeleteItem', 'yes');
XHR.appendData('ID', ID);
XHR.sendAndLoad('$page', 'POST',x_DeleteHAPGroupItem);
}

var x_TimeRuleDansDelete= function (obj) {
var res=obj.responseText;
if(res.length>3){alert(res);return;}
if(document.getElementById('main_filter_rule_edit')){RefreshTab('main_filter_rule_edit');}
if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
$('#rowtime'+TimeRuleIDTemp).remove();
RefreshAllAclsTables();
ExecuteByClassName('SearchFunction');
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

function items_list(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$ID=$_GET["ID"];
	$FORCE_FILTER=null;
	$search='%';
	$table="(SELECT * FROM haproxy_acls_items WHERE groupid='{$_GET["gpid"]}') as t";
	$page=1;
	$rp=50;
	if($q->COUNT_ROWS("haproxy_acls_items","artica_backup")==0){
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

		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT *  FROM $table WHERE 1 $searchstring $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){json_error_show($q->mysql_error);}


	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("no data");}

	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$disable=Field_checkbox("itemid_{$ligne['ID']}", 1,$ligne["enabled"],"EnableDisableItem('{$ligne['ID']}')");
		$ligne['pattern']=utf8_encode($ligne['pattern']);
		$delete=imgtootltip("delete-24.png","{delete} {$ligne['pattern']}","DeleteHAPGroupItem('{$ligne['ID']}')");
	


		$data['rows'][] = array(
				'id' => "item{$ligne['ID']}",
				'cell' => array("<span style='font-size:13px;font-weight:bold'>{$ligne['pattern']}</span>",
				"<center>$delete</center>")
		);
	}


	echo json_encode($data);
}

function item_form(){
	$ID=$_GET["gpid"];
	$item_id=$_GET["item_id"];
	
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$acl=new haproxy();
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT grouptype FROM haproxy_acls_groups WHERE ID='$ID'","artica_backup"));
	$GroupType=$ligne["grouptype"];
	$GroupTypeText=$acl->acl_GroupType[$GroupType];
	$sock=new sockets();
	$label_form="{pattern}";
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM haproxy_acls_items WHERE ID='$item_id'","artica_backup"));
	$buttonname="{add}";$jsadd=null;
	if($ID<1){$buttonname="{add}";}
	$LOCK=0;
	$BLOCK_FIELD=0;


	if($GroupType=="src"){
		$explain="{acl_src_text}";
		$browse=button("{browse}...","Loadjs('squid.BrowseItems.php?field=$t-pattern&type=ipaddr')");
	}
	if($GroupType=="dst"){$explain="{acl_dst_text}";}
	if($GroupType=="arp"){$explain="{ComputerMacAddress}";}
	if($GroupType=="dstdomain"){$explain="{squid_ask_domain}";}
	if($GroupType=="maxconn"){$explain="{squid_aclmax_connections_explain}";}
	if($GroupType=="port"){$explain="{acl_squid_remote_ports_explain}";}
	if($GroupType=="ext_user"){$explain="{acl_squid_ext_user_explain}";}
	if($GroupType=="req_mime_type"){$explain="{req_mime_type_explain}";}
	if($GroupType=="rep_mime_type"){$explain="{rep_mime_type_explain}";}
	if($GroupType=="referer_regex"){$explain="{acl_squid_referer_regex_explain}";}
	if($GroupType=="srcdomain"){$explain="{acl_squid_srcdomain_explain}";}
	if($GroupType=="url_regex_extensions"){$explain="{url_regex_extensions_explain}";}
	if($GroupType=="max_user_ip"){$explain="<b>{acl_max_user_ip_title}</b><br>{acl_max_user_ip_text}";}
	if($GroupType=="quota_time"){$explain="{acl_quota_time_text}";}
	if($GroupType=="quota_size"){$explain="{acl_quota_size_text}";}
	if($GroupType=="ssl_sni"){$explain="{acl_ssl_sni_text}";}
	if($GroupType=="myportname"){$explain="{acl_myportname_text}";}
	if($GroupType=="hdr(host)"){$explain="{squid_ask_domain}";}


	$FIELD_SIZE=450;
	$MAIN_BUTTON=button($buttonname, "SaveItemsMode$t()",26);

	

	if($GroupType=="browser"){
		$explain="{acl_squid_browser_explain}";
		$browse=button("{list}..","Loadjs('squid.browsers.php?ShowOnly=1')",16);
	}

	$MAIN_FIELD=Field_text("$t-pattern",utf8_encode($ligne["pattern"]),"font-size:30px;width:{$FIELD_SIZE}px",null,null,null,false,"SaveItemsModeCheck(event)");






	$html="
	<div style='font-size:22px;margin-bottom:15px'>$GroupType:$GroupTypeText</div>
	<div class=explain style='font-size:18px'>$explain</div>
	<div id='$t'></div>
	<div style='width:98%' class=form>
	<table style='width:99%'>
	<tbody>
	<tr>
	<td class=legend style='font-size:30px' nowrap width=99% nowrap>$label_form:</td>
	<td>$MAIN_FIELD</td>
	<td width=1%>$browse</td>
	</tr>

	<tr>
	<td colspan=3 style='text-align:right;height:30px'><hr>$MAIN_BUTTON</td>
	</tr>
	<tr><td colspan=3 style='text-align:right;height:30px'>&nbsp;</td></tr>
	</table>
	</div>
	<div id='$t-to-add'></div>
	<script>
var x_SaveItemsMode$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	document.getElementById('$t-pattern').value='';
	$('#HAPROXY_ACLS_TABLE').flexReload();
	$('#HAPROXY_OBJECTS_LIST_ACLS').flexReload();
	$('#HAPROXY_ACLS_ITEMS_TABLE').flexReload();
}

function FillFieldMAC$t(realuid,mac,ip){
	document.getElementById('$t-pattern').value=mac;
	YahooWin6Hide();
}

function SaveItemsModeCheck(e){
	if(checkEnter(e)){SaveItemsMode$t();}
}

function SaveItemsMode$t(){
	var XHR = new XHRConnection();
	XHR.appendData('item-pattern', encodeURIComponent(document.getElementById('$t-pattern').value));
	XHR.appendData('item-id', '$item_id');
	XHR.appendData('gpid', '$ID');
	XHR.sendAndLoad('$page', 'POST',x_SaveItemsMode$t);
}



$jsadd

function CheckLock$t(){
	var LOCK=$LOCK;
	if(LOCK==1){
		document.getElementById('$t-pattern').disabled=true;
	}
}
CheckLock$t();
</script>

";
echo $tpl->_ENGINE_parse_body($html);
}
function item_save(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_POST["item-id"];
	$gpid=$_POST["gpid"];
	$MULTIPLE_SQL=array();
	$ECHO=false;
	if(isset($_POST["ECHO"])){$ECHO=true;}
	$_POST["item-pattern"]=url_decode_special_tool($_POST["item-pattern"]);
	$q=new mysql();

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname,grouptype FROM haproxy_acls_groups WHERE ID='$ID'","artica_backup"));
	$GroupType=$ligne["grouptype"];
	$GroupName=$ligne["groupname"];
	

	if($GroupType=="dst"){
		$ipClass=new IP();
		if(!$ipClass->isIPAddressOrRange($_POST["item-pattern"])){
			echo "Not a valid IP {$_POST["item-pattern"]}\n";
			return;
		}
	}
	if($GroupType=="src"){
		$ipClass=new IP();
		if(!$ipClass->isIPAddressOrRange($_POST["item-pattern"])){
			echo "Not a valid IP {$_POST["item-pattern"]}\n";
			return;
		}
	}

	


	if($GroupType=="arp"){
		$_POST["item-pattern"]=trim(strtoupper( $_POST["item-pattern"]));
		$_POST["item-pattern"]=str_replace("-", ":", $_POST["item-pattern"]);
	}


	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ID FROM haproxy_acls_items WHERE gpid='$gpid' AND pattern='{$_POST["item-pattern"]}'","artica_backup"));
	if(intval($ligne["ID"])>0){return;}

	$sqladd="INSERT INTO haproxy_acls_items (pattern,groupid)
	VALUES ('{$_POST["item-pattern"]}','$gpid');";

	$sql="UPDATE haproxy_acls_items SET pattern='{$_POST["item-pattern"]}' WHERE ID='$ID'";
	if($ID<1){$sql=$sqladd;
	if($ECHO){echo "{$_POST["item-pattern"]} -> $GroupName Type $GroupType OK\n";}
	}
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}

}
function item_form_import(){
	$ID=$_GET["gpid"];
	$item_id=$_GET["item_id"];
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$acl=new haproxy();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname,grouptype FROM haproxy_acls_groups WHERE ID='$ID'","artica_backup"));
	$GroupType=$ligne["grouptype"];
	$GroupTypeText=$acl->acl_GroupType[$GroupType];

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM haproxy_acls_items WHERE ID='$item_id'","artica_backup"));
	$buttonname="{add}";
	if($ID<1){$buttonname="{add}";}



	if($GroupType=="src"){
		$explain="{acl_src_text}";
		$browse="<input type='button' value='{browse}...'
		OnClick=\"javascript:Loadjs('squid.BrowseItems.php?field=$t-pattern&type=ipaddr');\" style='font-size:16px'>";
	}
	if($GroupType=="arp"){$explain="{ComputerMacAddress}";}
	if($GroupType=="dstdomain"){$explain="{squid_ask_domain}";}
	if($GroupType=="port"){$explain="{acl_squid_remote_ports_explain}";}
	if($GroupType=="dst"){$explain="{acl_squid_dst_explain}";}
	if($GroupType=="url_regex"){$explain="{acl_squid_url_regex_explain}";}
	if($GroupType=="referer_regex"){$explain="{acl_squid_referer_regex_explain}";}
	if($GroupType=="urlpath_regex"){$explain="{acl_squid_url_regex_explain}";}





	$html="
	<div style='font-size:22px'>$GroupTypeText</div>
	
	<div id='$t'></div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tbody>
	<tr>
	<td class=legend style='font-size:22px;text-align:left' nowrap width=99%>{pattern}:</td>
	</tr>
	<tr>
	<td><textarea style='margin-top:5px;
	font-family:Courier New;font-weight:bold;width:98%;height:150px;
	border:5px solid #8E8E8E;overflow:auto;font-size:18px !important'
	id='textToParseCats-$t'></textarea>
	</td>
	</tr>
	<tr>
	<td style='text-align:right;height:30px'><hr>". button($buttonname, "SaveItemsMode$t()",26)."</td>
	</tr>
	</table>
	</div>
	<script>
var x_SaveItemsMode$t= function (obj) {
	var res=obj.responseText;
	document.getElementById('$t').innerHTML='';
	if(res.length>3){alert(res);return;}
	document.getElementById('textToParseCats-$t').value='';
	$('#HAPROXY_ACLS_TABLE').flexReload();
	$('#HAPROXY_OBJECTS_LIST_ACLS').flexReload();
	$('#HAPROXY_ACLS_ITEMS_TABLE').flexReload();	
}

function SaveItemsModeCheck(e){
if(!checkEnter(e)){return;}
SaveItemsMode$t();
}

function SaveItemsMode$t(){
	var XHR = new XHRConnection();
	XHR.appendData('item-import', document.getElementById('textToParseCats-$t').value);
	XHR.appendData('item-id', '$item_id');
	XHR.appendData('gpid', '$ID');
	XHR.sendAndLoad('$page', 'POST',x_SaveItemsMode$t);
}

</script>

";
echo $tpl->_ENGINE_parse_body($html);
}
function item_import(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_POST["item-id"];
	$gpid=$_POST["ID"];
	$q=new mysql();

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname,grouptype FROM haproxy_acls_groups WHERE ID='$ID'","artica_backup"));
	$GroupType=$ligne["grouptype"];


	$t=array();
	$sqladd="INSERT IGNORE INTO haproxy_acls_items (pattern,groupid) VALUES ";
	$Patterns=array();
	$f=explode("\n",$_POST["item-import"]);
	$ipClass=new IP();
	while (list ($num, $pattern) = each (	$f)){
		if(trim($pattern)==null){continue;}

		if($GroupType=="url_regex_extensions"){
			if(preg_match("#\.(.+?)$#", $pattern,$re)){$pattern=$re[1];}
		}

		if($GroupType=="dstdomain"){
			if(preg_match("#\/\/#", $pattern)){
				$URLAR=parse_url($pattern);
				if(isset($URLAR["host"])){$pattern=$URLAR["host"];}
			}
			if(preg_match("#^www.(.*)#",$pattern,$re)){$pattern=$re[1];}
			if(preg_match("#(.*?)\/#", $pattern,$re)){$pattern=$re[1];}
		}

		if($GroupType=="arp"){
			$pattern=trim(strtoupper( $pattern));
			$pattern=str_replace("-", ":", $pattern);
		}


		if($GroupType=="dst"){if(!$ipClass->isIPAddressOrRange($pattern)){continue;}}
		if($GroupType=="src"){if(!$ipClass->isIPAddressOrRange($pattern)){continue;}}
			
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ID FROM webfilters_sqitems WHERE gpid='$gpid' AND pattern='$pattern'"));
		if(trim($ligne["ID"])>0){continue;}
		$Patterns[$pattern]=true;




	}

	if(count($Patterns)>0){
		while (list ($a, $b) = each (	$Patterns)){
			$t[]="('$a','$gpid')";
		}
	}



	if(count($t)>0){

		$sql=$sqladd.@implode(",", $t);
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error."\n***\n$sql\n****\n";return;}

	}
}
