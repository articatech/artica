<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.firehol.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.system.nics.inc');
	$usersmenus=new usersMenus();
	if($usersmenus->AsSystemAdministrator==false){
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		die();
	}
	
	if(isset($_GET["delete-js"])){delete_js();exit;}
	if(isset($_POST["delete"])){delete();exit;}
	
	if(isset($_GET["revert-js"])){revert_js();exit;}
	if(isset($_POST["revert"])){revert();exit;}
	if(isset($_GET["firehol_status"])){firehol_status();exit;}
	if(isset($_GET["table"])){table();exit;}
	if(isset($_GET["interfaces"])){search();exit;}
	if(isset($_POST["isFW"])){isFW();exit;}
	
tabs();	

function firehol_status(){
	$page=CurrentPageName();
	$t=time();
	$tpl=new templates();
	$nic=new system_nic($_GET["nic"]);
	$p=Paragraphe_switch_img("{activate_firewall_nic}", "{activate_firewall_nic_explain}","isFW-$t",$nic->isFW,null,550);
	
	$html="
<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
			<td colspan=2>$p</td>
		</tr>
		
		<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",22)."</td>
		</tr>
	</table>
</div>
<script>
var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	RefreshTab('main_config_{$_GET["nic"]}_services');
	if(document.getElementById('FIREHOLE_INTERFACES_TABLES')){ $('#FIREHOLE_INTERFACES_TABLES').flexReload();}
	Loadjs('firehol.progress.php');
	}
	
	
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('nic',  '{$_GET["nic"]}');
	XHR.appendData('isFW',  document.getElementById('isFW-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
";
echo $tpl->_ENGINE_parse_body($html);	

}


function tabs(){
	$page=CurrentPageName();
	$array["firehol_status"]="{status}";
	$array["firehol_services"]="{services}";
	$array["firehol_client_services"]="{local_services}";
	$nic=$_GET["nic"];
	
	while (list ($num, $ligne) = each ($array) ){
		if($num=="firehol_status"){
			$html[]= "<li><a href=\"$page?firehol_status=yes&nic=$nic\"><span style='font-size:20px'>$ligne</span></a></li>\n";
			continue;
		}
		$html[]= "<li><a href=\"$page?table=$num&nic=$nic\"><span style='font-size:20px'>$ligne</span></a></li>\n";
	}
	
	
	echo build_artica_tabs($html, "main_config_{$nic}_services");
}

function isFW(){
	$nic=new system_nic($_POST["nic"]);
	$nic->isFW=$_POST["isFW"];
	$nic->SaveNic();
	
}

function delete_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$zmd5=$_GET["zmd5"];
	$nic=$_GET["nic"];
	$xtable=$_GET["xtable"];
	echo "
	var xAdd$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#FIREHOLE_{$_GET["xtable"]}{$_GET["nic"]}').flexReload();
	
	}
	function Add$t(){
	var XHR = new XHRConnection();
	XHR.appendData('delete', '$zmd5');
	XHR.appendData('xtable', '$xtable');
	XHR.sendAndLoad('$page', 'POST',xAdd$t);
	}
	Add$t();";
	
	
	
}


function revert_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$zmd5=$_GET["zmd5"];
	$nic=$_GET["nic"];
	$xtable=$_GET["xtable"];
	echo "
var xAdd$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#FIREHOLE_{$_GET["xtable"]}{$_GET["nic"]}').flexReload();
	
}
function Add$t(){
	var XHR = new XHRConnection();
	XHR.appendData('revert', '$zmd5');
	XHR.appendData('xtable', '$xtable');
	XHR.sendAndLoad('$page', 'POST',xAdd$t);
}
Add$t();";
	
	
}

function revert(){
	$q=new mysql();
	
	$sql="SELECT `allow_type` FROM `{$_POST["xtable"]}` WHERE `zmd5`='{$_POST["revert"]}'";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!$q->ok){echo $q->mysql_error;}
	if($ligne["allow_type"]==1){$allow_type=0;}else{$allow_type=1;}
	$q->QUERY_SQL("UPDATE `{$_POST["xtable"]}` SET allow_type=$allow_type WHERE `zmd5`='{$_POST["revert"]}'","artica_backup");
	
	
}
function delete(){
	$q=new mysql();

	$sql="DELETE FROM `{$_POST["xtable"]}` WHERE `zmd5`='{$_POST["delete"]}'";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!$q->ok){echo $q->mysql_error;}
	

}	

function table(){
	
	$users=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();
	$rule=$tpl->_ENGINE_parse_body("{rule}");
	$nic=new system_nic($_GET["nic"]);
	
	
	if($nic->firewall_behavior==1){
		echo FATAL_ERROR_SHOW_128("{error_firewall_behavior_lan}");
		return;		
	}
	
	
	
	
	$t=time();
	$service2=$tpl->_ENGINE_parse_body("{service2}");
	$netzone=$tpl->_ENGINE_parse_body("{netzone}");
	$local_services=$tpl->_ENGINE_parse_body("{local_services}");
	$log=$tpl->_ENGINE_parse_body("{LOG}");
	$new_rule=$tpl->_ENGINE_parse_body("{zDate}");
	$new_rule=$tpl->_ENGINE_parse_body("{link_service}");
	$name=$tpl->_ENGINE_parse_body("{name}");
	$allow_rules=$tpl->_ENGINE_parse_body("{allow_rules}");
	$banned_rules=$tpl->_ENGINE_parse_body("{banned_rules}");
	$empty_all_firewall_rules=$tpl->javascript_parse_text("{empty_all_firewall_rules}");
	$services=$tpl->_ENGINE_parse_body("{services}");
	$current_rules=$tpl->_ENGINE_parse_body("{current_rules}");
	$options=$tpl->_ENGINE_parse_body("{options}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$ERROR_IPSET_NOT_INSTALLED=$tpl->javascript_parse_text("{ERROR_IPSET_NOT_INSTALLED}");
	$apply_firewall_rules=$tpl->javascript_parse_text("{apply_firewall_rules}");
	$IPSET_INSTALLED=0;
	if($users->IPSET_INSTALLED){$IPSET_INSTALLED=1;}

	$TB_HEIGHT=450;
	$TABLE_WIDTH=920;
	$TB2_WIDTH=400;
	$ROW1_WIDTH=629;
	$ROW2_WIDTH=163;

	$t=time();

	$buttons="
	buttons : [
	
	{name: '<strong style=font-size:18px>$new_rule</strong>', bclass: 'Add', onpress : NewRule$t},
	{name: '<strong style=font-size:18px>$apply_firewall_rules</strong>', bclass: 'Apply', onpress : FW$t},
	
	],	";
	$html="
	<table class='FIREHOLE_{$_GET["xtable"]}{$_GET["nic"]}' style='display: none' id='FIREHOLE_{$_GET["xtable"]}{$_GET["nic"]}' style='width:99%'></table>
	<script>
	var IptableRow='';
	$(document).ready(function(){
	$('#FIREHOLE_{$_GET["xtable"]}{$_GET["nic"]}').flexigrid({
	url: '$page?interfaces=yes&t=$t&xtable={$_GET["xtable"]}&nic={$_GET["nic"]}',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:22px>$service2</span>', name : 'service', width :364, sortable : true, align: 'left'},
	{display: '<span style=font-size:22px>$allow</span>', name : 'allow_type', width :70, sortable : true, align: 'center'},
	{display: '&nbsp;', name : 'delete', width :70, sortable : false, align: 'center'},

	],
	$buttons

	searchitems : [
	{display: '$service2', name : 'service'},
	],

	sortname: 'service',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: $TB_HEIGHT,
	singleSelect: true

});
});

function block_countries(){
	var IPSET_INSTALLED=$IPSET_INSTALLED;
	if(IPSET_INSTALLED==0){alert('$ERROR_IPSET_NOT_INSTALLED');return;}
	Loadjs('system.ipblock.php')
}

function current_rules(){
Loadjs('system.iptables.save.php');
}

function FW$t(){
	Loadjs('firehol.progress.php');
}
	

var x_EmptyRules= function (obj) {
var tempvalue=obj.responseText;
if(tempvalue.length>3){alert(tempvalue);return;}
IpTablesInboundRuleResfresh();
}

function EmptyRules(){
if(confirm('$empty_all_firewall_rules ?')){
var XHR = new XHRConnection();
XHR.appendData('EmptyAll','yes');
XHR.sendAndLoad('$page', 'POST',x_EmptyRules);
}
}

function NewRule$t(){
	Loadjs('firehol.BrowseService.php?xtable={$_GET["xtable"]}&nic={$_GET["nic"]}');
}

function IpTablesInboundRuleResfresh(){
$('#table-$t').flexReload();
}

function AllowRules(){
$('#table-$t').flexOptions({ url: '$page?iptables_rules=yes&t=$t&allow=1' }).flexReload();
}
function BannedRules(){
$('#table-$t').flexOptions({ url: '$page?iptables_rules=yes&t=$t&allow=0' }).flexReload();
}

var x_IptableDelete= function (obj) {
var tempvalue=obj.responseText;
if(tempvalue.length>3){alert(tempvalue);return;}
$('#row'+IptableRow).remove();

}

function options$t(){
Loadjs('$page?options=yes&table=table-$t',true);
}

function IptableDelete(key){
IptableRow=key;
var XHR = new XHRConnection();
XHR.appendData('DeleteIptableRule',key);
XHR.sendAndLoad('$page', 'POST',x_IptableDelete);
}

var x_FirewallDisableRUle= function (obj) {
var tempvalue=obj.responseText;
if(tempvalue.length>3){alert(tempvalue);}
}

function iptables_edit_rules(num){
YahooWin5('800','$page?edit_rule=yes&t=$t&rulemd5='+num,'$rule');

}


function FirewallDisableRUle(ID){
var XHR = new XHRConnection();
XHR.appendData('ID',ID);
if(document.getElementById('enabled_'+ID).checked){XHR.appendData('EnableFwRule',0);}else{XHR.appendData('EnableFwRule',1);}
XHR.sendAndLoad('$page', 'POST',x_FirewallDisableRUle);
}

function EnableLog(ID){
var XHR = new XHRConnection();
XHR.appendData('ID',ID);
if(document.getElementById('enabled_'+ID).checked){XHR.appendData('EnableLog',1);}else{XHR.appendData('EnableLog',0);}
XHR.sendAndLoad('$page', 'POST',x_FirewallDisableRUle);

}

</script>";

	echo $html;
}



function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$database="artica_backup";
	$search='%';
	$table="nics";
	$page=1;
	$ORDER=null;
	$allow=null;

	$total=0;
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("no data");;}

	
	$searchstring=string_to_flexquery();
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$table="(select * from `{$_GET["xtable"]}` WHERE `interface`='{$_GET["nic"]}') as t";
	$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
	$total = $ligne["TCOUNT"];


	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}

	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT * FROM $table  WHERE 1 $searchstring $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show($q->mysql_error_html(),1);}
	if(mysql_num_rows($results)==0){json_error_show("no data $sql");}

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(!$q->ok){json_error_show($q->mysql_error);}
	$fontsize=18;
	$firehole=new firehol();
	
	$allow_type[0]="cloud-deny-42.png";
	$allow_type[1]="cloud-goto-42.png";
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$mouse="OnMouseOver=\"this.style.cursor='pointer'\" OnMouseOut=\"this.style.cursor='default'\"";
		$linkstyle="style='text-decoration:underline'";
		$service=$ligne["service"];
		$allow_typez=$ligne["allow_type"];
		$netzone=$ligne["netzone"];
		$zmd5=$ligne["zmd5"];
		$icon=$allow_type[$allow_typez];

		
		$allow=imgsimple($icon,null,"Loadjs('$MyPage?revert-js=yes&zmd5=$zmd5&nic={$_GET["nic"]}&xtable={$_GET["xtable"]}')");
		$delete=imgsimple("delete-42.png",null,"Loadjs('$MyPage?delete-js=yes&zmd5=$zmd5&nic={$_GET["nic"]}&xtable={$_GET["xtable"]}')");
		
		$data['rows'][] = array(
				'id' => $service,
				'cell' => array(
						
						"<span style='font-size:22px'>$service</a></span>",
						"<center style='font-size:18px'>$allow</center>",
						"<center style='font-size:18px'>$delete</center>",

							

				)
		);
	}


	echo json_encode($data);

}