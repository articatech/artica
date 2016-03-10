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
if(isset($_GET["popup2"])){item_popup2();exit;}

if(isset($_GET["items"])){items();exit;}
if(isset($_GET["delete-js"])){items_delete_js();exit;}

if(isset($_GET["move-item-js"])){move_items_js();exit;}
if(isset($_POST["move-item"])){move_items();exit;}


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
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `pattern` FROM transparent_networks WHERE ID='$linkid'"));
		$t=$_GET["t"];
		$tt=time();
		$pattern=$tpl->javascript_parse_text("{network}: {$ligne["pattern"]}");
	}else{
		$pattern=$tpl->javascript_parse_text("{new_network}");
	}
	
	$html="YahooWin3('700','$page?item-popup=yes&ID=$linkid&t={$_GET["t"]}','$pattern',true);";
	echo $html;

}
function items_delete_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$q=new mysql_squid_builder();
	$linkid=$_GET["delete-js"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `pattern` FROM transparent_networks WHERE ID='$linkid'"));
	$t=$_GET["t"];
	$tt=time();
	$pattern=$tpl->javascript_parse_text("{delete} {network}: {$ligne["pattern"]} ?");
	$html="
var xSave$tt= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#flexRT{$_GET["t"]}').flexReload();
	if(document.getElementById('flexRT-refresh-1')){ $('#'+document.getElementById('flexRT-refresh-1').value).flexReload();}
	ExecuteByClassName('SearchFunction');
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
	$table="transparent_networks";
	if(!$q->TABLE_EXISTS($table)){$q->CheckTables();}
	$q->QUERY_SQL("DELETE FROM transparent_networks WHERE ID={$_POST["delete"]}");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?restart-firewall=yes");
}
function move_items(){
	$q=new mysql_squid_builder();
	$ID=$_POST["move-item"];
	$t=$_POST["t"];
	$dir=$_POST["dir"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT zOrder FROM transparent_networks WHERE ID='$ID'"));
	if(!$q->ok){echo "Line:".__LINE__.":".$q->mysql_error;}

	
	$CurrentOrder=$ligne["zOrder"];

	if($dir==0){
		$NextOrder=$CurrentOrder-1;
	}else{
		$NextOrder=$CurrentOrder+1;
	}

	$sql="UPDATE transparent_networks SET zOrder=$CurrentOrder WHERE zOrder='$NextOrder'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo  "Line:".__LINE__.":".$q->mysql_error;}


	$sql="UPDATE transparent_networks SET zOrder=$NextOrder WHERE ID='$ID'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo  "Line:".__LINE__.":".$q->mysql_error;}

	$results=$q->QUERY_SQL("SELECT ID FROM transparent_networks ORDER by zOrder","artica_backup");
	if(!$q->ok){echo "Line:".__LINE__.":".$q->mysql_error;}
	$c=1;
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$sql="UPDATE transparent_networks SET zOrder=$c WHERE ID='$ID'";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo "Line:".__LINE__.":".$q->mysql_error;}
		$c++;
	}


}

function item_popup(){
	if($_GET["ID"]==0){item_popup2();return;}
	$page=CurrentPageName();
	$tpl=new templates();

	$array["popup2"]="{rule}";
	$array["acl-items"]="{groups2}";
	
	while (list ($num, $ligne) = each ($array) ){
		if($num=="acl-items"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:14px'><a href=\"squid.transparent.items.php?$num=yes&aclid={$_GET["ID"]}&t={$_GET["t"]}\"><span>$ligne</span></a></li>\n");
			continue;
				
		}
		
		$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:14px'><a href=\"$page?$num=yes&ID={$_GET["ID"]}&t={$_GET["t"]}\"><span>$ligne</span></a></li>\n");
	
	}
	
	
	echo build_artica_tabs($html, "main_transparent_{$_GET["ID"]}");	
	
}


function item_popup2(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$button="{add}";
	$ID=$_GET["ID"];
	if($ID>0){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM transparent_networks WHERE ID='$ID'"));
		$button="{apply}";
	}
	
	if(!is_numeric($ligne["transparent"])){$ligne["transparent"]=1;}
	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
	if(!is_numeric($ligne["destination_port"])){$ligne["destination_port"]=80;}
	if(!is_numeric($ligne["zOrder"])){$ligne["zOrder"]=0;}
	
	$nics=new networking();
	$Z=$nics->Local_interfaces(true);
	unset($Z["lo"]);
	
	$ETHZ[null]="{all}";
	while (list ($int, $none) = each ($Z) ){
		$nic=new system_nic($int);
		$ETHZ[$int]="{$int} - $nic->NICNAME - $nic->IPADDR";
	
	}
	
	
	$t=time();
	$html="<div class=form style='width:95%'>
	<div class=explain style='font-size:14.5px'>{transparent_pattern_explain}</div>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{interface}:</td>
		<td>". Field_array_Hash($ETHZ,"eth-$t",$ligne["eth"],"style:font-size:18px")."</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:18px'>{source}:</td>
		<td>". Field_text("pattern-$t",$ligne["pattern"],"font-size:18px;font-weight:bold;width:300px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{inverse}:</td>
		<td>". Field_checkbox("isnot-$t",1,$ligne["isnot"])."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:18px'>{destination}:</td>
		<td>". Field_text("destination-$t",$ligne["destination"],"font-size:18px;font-weight:bold;width:300px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{destination_port}:</td>
		<td>". Field_text("destination_port-$t",$ligne["destination_port"],"font-size:18px;font-weight:bold;width:90px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{remote_proxy_server}:</td>
		<td>". Field_text("remote_proxy-$t",$ligne["remote_proxy"],"font-size:18px;font-weight:bold;width:300px")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:18px'>{transparent}:</td>
		<td>". Field_checkbox("transparent-$t",1,$ligne["transparent"])."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{deny}:</td>
		<td>". Field_checkbox("block-$t",1,$ligne["block"],"ChkDeny$t()")."</td>
	</tr>
				
				
	<tr>
		<td class=legend style='font-size:18px'>{UseSSL}:</td>
		<td>". Field_checkbox("ssl-$t",1,$ligne["ssl"])."</td>
	</tr>	
				
	<tr>
		<td class=legend style='font-size:18px'>{enabled}:</td>
		<td>". Field_checkbox("enabled-$t",1,$ligne["enabled"],"ChkEna$t()")."</td>
	</tr>	
<tr>
	<td colspan=2 align='right'><hr>". button($button,"Save$t();",22)."</td>
</tr>
				
	</table></div>	
<script>
var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	var ID='$ID';
	$('#flexRT{$_GET["t"]}').flexReload();
	$('#flexRT{$_GET["tt"]}').flexReload();
	if(document.getElementById('flexRT-refresh-1')){ $('#'+document.getElementById('flexRT-refresh-1').value).flexReload();}
	ExecuteByClassName('SearchFunction');
	if(ID==0){YahooWin3Hide();}
}

function SaveCHK$t(e){
	if(!checkEnter(e)){return;}
	Save$t();
}

function ChkDeny$t(){
	
	if(document.getElementById('block-$t').checked){
		document.getElementById('transparent-$t').disabled=true;
	}else{
		document.getElementById('transparent-$t').disabled=false;
	}

}
function ChkEna$t(){
	
	if(!document.getElementById('enabled-$t').checked){
		document.getElementById('transparent-$t').disabled=true;
		document.getElementById('block-$t').disabled=true;
		document.getElementById('pattern-$t').disabled=true;
		document.getElementById('destination_port-$t').disabled=true;
		document.getElementById('remote_proxy-$t').disabled=true;
		document.getElementById('ssl-$t').disabled=true;
		document.getElementById('eth-$t').disabled=true;
		document.getElementById('isnot-$t').disabled=true;
		document.getElementById('destination-$t').disabled=true;
	}else{
		document.getElementById('transparent-$t').disabled=false;
		document.getElementById('block-$t').disabled=false;
		document.getElementById('pattern-$t').disabled=false;
		document.getElementById('destination_port-$t').disabled=false;
		document.getElementById('remote_proxy-$t').disabled=false;
		document.getElementById('ssl-$t').disabled=false;
		document.getElementById('eth-$t').disabled=false;
		document.getElementById('isnot-$t').disabled=false;
		document.getElementById('destination-$t').disabled=false;
		
		
		
	}

}	

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ID',  '$ID');
	XHR.appendData('pattern',  document.getElementById('pattern-$t').value);
	XHR.appendData('destination',  document.getElementById('destination-$t').value);
	XHR.appendData('eth',  document.getElementById('eth-$t').value);
	XHR.appendData('destination_port',  document.getElementById('destination_port-$t').value);
	XHR.appendData('remote_proxy',  document.getElementById('remote_proxy-$t').value);
	if(document.getElementById('enabled-$t').checked){ XHR.appendData('enabled',  1); }else{ XHR.appendData('enabled',  0); }
	if(document.getElementById('transparent-$t').checked){ XHR.appendData('transparent',  1); }else{ XHR.appendData('transparent',  0); }
	if(document.getElementById('ssl-$t').checked){ XHR.appendData('ssl',  1); }else{ XHR.appendData('ssl',  0); }	
	if(document.getElementById('block-$t').checked){ XHR.appendData('block',  1); }else{ XHR.appendData('block',  0); }
	if(document.getElementById('isnot-$t').checked){ XHR.appendData('isnot',  1); }else{ XHR.appendData('isnot',  0); }
	
	
	
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

ChkDeny$t();
ChkEna$t();
</script>	";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function Save(){
	writelogs("Saving rule",__FUNCTION__,__FILE__,__LINE__);
	$q=new mysql_squid_builder();
	$table="transparent_networks";
	if(!$q->TABLE_EXISTS($table)){$q->CheckTables(null,true);}
	if(!$q->FIELD_EXISTS("transparent_networks", "block")){$q->QUERY_SQL("ALTER TABLE `transparent_networks` ADD `block` smallint( 1 ) NOT NULL ,ADD INDEX ( `block`)");}

	$editF=false;
	$ID=$_POST["ID"];
	unset($_POST["ID"]);

	if(preg_match("#[0-9A-Za-z]+-[0-9A-Za-z]+-[0-9A-Za-z]+-[0-9A-Za-z]+-[0-9A-Za-z]+-[0-9A-Za-z]+#",$_POST["pattern"])){
		$_POST["pattern"]=strtolower($_POST["pattern"]);
		$_POST["pattern"]=str_replace("-", ":", $_POST["pattern"]);
	}
	
	


	while (list ($key, $value) = each ($_POST) ){
		$value=url_decode_special_tool($value);
		$fields[]="`$key`";
		$values[]="'".mysql_escape_string2($value)."'";
		$edit[]="`$key`='".mysql_escape_string2($value)."'";

	}

	$sql_edit="UPDATE `$table` SET ".@implode(",", $edit)." WHERE ID='$ID'";
	$sql="INSERT IGNORE INTO `$table` (".@implode(",", $fields).") VALUES (".@implode(",", $values).")";
	if($ID>0){$sql=$sql_edit;}
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo "Mysql error: `$q->mysql_error`";writelogs($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);return;}
	$tpl=new templates();


}

function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$tt=time();
	$t=$_GET["t"];
	$_GET["ruleid"]=$_GET["ID"];
	$groups=$tpl->javascript_parse_text("{groups}");
	$from=$tpl->_ENGINE_parse_body("{from}");
	$to=$tpl->javascript_parse_text("{to}");
	$rule=$tpl->javascript_parse_text("{rule}");
	$delete=$tpl->javascript_parse_text("{delete} {zone} ?");
	$rewrite_rules_fdb_explain=$tpl->javascript_parse_text("{rewrite_rules_fdb_explain}");
	$new_network=$tpl->javascript_parse_text("{new_network}");
	$comment=$tpl->javascript_parse_text("{comment}");
	$rules=$tpl->javascript_parse_text("{rules}");
	$rule=$tpl->javascript_parse_text("{rule}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$action=$tpl->javascript_parse_text("{action}");
	$restricted_ports=$tpl->javascript_parse_text("{restricted_ports}");
	$networks=$tpl->javascript_parse_text("{networks}");
	$restricted=$tpl->javascript_parse_text("{restricted}");
	$transaprent=$tpl->javascript_parse_text("{mode}");
	$destination=$tpl->_ENGINE_parse_body("{destination}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$order=$tpl->_ENGINE_parse_body("{order}");
	$direct_to_internet=$tpl->_ENGINE_parse_body("{direct_to_internet}");
	$title=$networks;
	$tt=time();
	$error=null;
	$EnableTransparent27=intval($sock->GET_INFO("EnableTransparent27"));
	if($EnableTransparent27==1){
		$error="<p class=text-error>{nat_firewall_activated_error}</p>";
	}
	
	$buttons="
	buttons : [
	{name: '$new_network', bclass: 'add', onpress : NewRule$tt},
	{name: '$apply', bclass: 'Reconf', onpress : Apply$tt},
	],";

	$html=$tpl->_ENGINE_parse_body("$error
	<center style='margin-bottom:20px'><table style='width:80%'>
	<tr>
	<td width=33% align='center'><img src='img/cloud-goto-48.png'></td>
	<td width=33% align='center'><img src='img/cloud-filtered-48.png'></td>
	<td width=33% align='center'><img src='img/cloud-deny-48.png'></td>
	</tr>
	<td width=33% align='center' style='font-size:14px'>$direct_to_internet</td>
	<td width=33% align='center' style='font-size:14px'>{pass_trough_proxy}</td>
	<td width=33% align='center' style='font-size:14px'>{deny_redirected}</td>
	</tr>
	</table>
	</center>")."
	
	
	
	<input type='hidden' id='flexRT-refresh-1' value='flexRT$tt'>
	<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:100%'></table>
	<script>
	function Start$tt(){
	$('#flexRT$tt').flexigrid({
	url: '$page?items=yes&t=$tt&tt=$tt',
	dataType: 'json',
	colModel : [
	{display: '$order', name : 'zOrder', width :45, sortable : true, align: 'center'},
	{display: 'PROTO', name : 'ssl', width :66, sortable : true, align: 'left'},
	{display: '$networks', name : 'pattern', width :273, sortable : true, align: 'left'},
	{display: '&nbsp;', name : 'none1', width :31, sortable : false, align: 'center'},
	{display: '$destination', name : 'destination', width :273, sortable : true, align: 'left'},
	{display: '$transaprent', name : 'transparent', width :100, sortable : false, align: 'center'},
	{display: '$enabled', name : 'enabled', width :54, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'delete', width : 54, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'up', width : 54, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'down', width : 54, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$networks', name : 'pattern'},
	{display: '$destination', name : 'destination'},
	],
	sortname: 'zOrder',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
}

var xNewRule$tt= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#flexRT$t').flexReload();
	$('#flexRT$tt').flexReload();
}

function Apply$tt(){
	Loadjs('squid.transparent.networks.progress.php');
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
	$('#flexRT$t').flexReload();
	$('#flexRT$tt').flexReload();
}


function INOUT$tt(ID){
	var XHR = new XHRConnection();
	XHR.appendData('INOUT', ID);
	XHR.sendAndLoad('$page', 'POST',xINOUT$tt);
}

function rports(){
	Loadjs('squid.webauth.hotspots.restricted.ports.php',true);
}

function reverse$tt(ID){
	var XHR = new XHRConnection();
	XHR.appendData('reverse', ID);
	XHR.sendAndLoad('$page', 'POST',xINOUT$tt);
}

var x_LinkAclRuleGpid$tt= function (obj) {
var res=obj.responseText;
if(res.length>3){alert(res);return;}
$('#table-$t').flexReload();
$('#flexRT$tt').flexReload();
ExecuteByClassName('SearchFunction');
}
function FlexReloadRulesRewrite(){
$('#flexRT$t').flexReload();
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

function move_items_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	/*$users=new usersMenus();
	if(!$users->CORP_LICENSE){
		echo "alert('".$tpl->javascript_parse_text("{this_feature_is_disabled_corp_license}")."');";
		die();
	}
	*/
	
	$t=time();
	
	$html="

var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){ alert(results); return; }
	$('#flexRT{$_GET["t"]}').flexReload();
}
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('move-item','{$_GET["ID"]}');	
	XHR.appendData('t','{$_GET["t"]}');
	XHR.appendData('dir','{$_GET["dir"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

Save$t();

	";

	echo $html;

}
function items(){
	include_once(dirname(__FILE__)."/ressources/class.squid.inc");
	$tpl=new templates();
	$squid=new squidbee();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	$t=$_GET["t"];
	$search='%';
	$table="transparent_networks";
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	$SSL=$squid->SSL_BUMP;
	if(!$q->TABLE_EXISTS("transparent_networks")){$q->CheckTables(null,true);}
	

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
	$arrow_right="<img src='img/arrow-right-32.png'>";
	$AllSystems=$tpl->_ENGINE_parse_body("{AllSystems}");
	$AllDestinations=$tpl->_ENGINE_parse_body("{all_destinations}");
	$local_proxy=$tpl->_ENGINE_parse_body("{local_proxy}");
	$proxy=$local_proxy;
	$port=$tpl->_ENGINE_parse_body("{port}");
	
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql",1);}
	if(mysql_num_rows($results)==0){
		
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>0</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>HTTP</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>* - $AllSystems</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$arrow_right</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>* - $AllDestinations $port 80<div style='font-size:12px;text-align:right'>$proxy</div></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$check32</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$check32</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>&nbsp;</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>&nbsp;</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>&nbsp;</span>",)
		);
		
		if($SSL==0){
			$color="#8a8a8a";
			$check32="<img src='img/check-32-grey.png'>";
			$arrow_right="<img src='img/arrow-right-32-grey.png'>";
			
		}
			$data['rows'][] = array(
					'id' => $ligne['ID'],
					'cell' => array(
							"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>0</a></span>",
							"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>HTTPS</a></span>",							
							"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>* - $AllSystems</a></span>",
							"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$arrow_right</a></span>",
							"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>* - $AllDestinations $port 443<div style='font-size:12px;text-align:right'>$proxy</div></span>",
							"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$check32</a></span>",
							"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$check32</a></span>",
							"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>&nbsp;</span>",
							"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>&nbsp;</span>",
							"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>&nbsp;</span>",)
			);
			
			
		
		
		$data['total'] = 1;
		echo json_encode($data);
	return;}
	
	$all=$tpl->_ENGINE_parse_body("{all}");
	$fontsize="18";
	$color="black";
	$check32="<img src='img/check-32.png'>";
	$local_proxy=$tpl->_ENGINE_parse_body("{local_proxy}");
	$redirect_to=$tpl->_ENGINE_parse_body("{redirect_to}");
	$not=$tpl->_ENGINE_parse_body("{not} ");
	
	$AVAILABLE_MACROS["google"]=true;
	$AVAILABLE_MACROS["teamviewer"]=true;
	$AVAILABLE_MACROS["office365"]=true;
	$AVAILABLE_MACROS["skype"]=true;
	$AVAILABLE_MACROS["dropbox"]=true;
	$AVAILABLE_MACROS["whatsapp"]=true;
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$check32="<img src='img/check-32.png'>";
		$check32T="<img src='img/cloud-filtered-42.png'>";
		$arrow_right="<img src='img/arrow-right-32.png'>";
		$proxy=$local_proxy;
		$ligne["remote_proxy"]=trim($ligne["remote_proxy"]);
		if(!preg_match("#^(.+?):(.+)#", $ligne["remote_proxy"])){$ligne["remote_proxy"]=null;}
		$eth=$ligne["eth"];
		if(is_numeric($eth)){$eth=null;}
		if($eth<>null){$eth="$eth:";}
		$isnot=null;
		if($ligne["destination_port"]==443){$ligne["ssl"]=1;}
		if($ligne["destination_port"]==80){$ligne["ssl"]=0;}
		if($ligne["destination_port"]==0){
			$ligne["destination_port"]=80;
			if($ligne["ssl"]==1){$ligne["destination_port"]==443;}
		}
		
		
		
		$proto="HTTP";
		$destination_port="$port {$ligne["destination_port"]}";
		
		if($ligne["ssl"]==1){
			$proto="HTTPS";$destination_port="$port {$ligne["destination_port"]}";
			if($SSL==0){$ligne["enabled"]=0;}
		}
		
		if($ligne["enabled"]==0){
			$color="#8a8a8a";
			$check32="<img src='img/check-32-grey.png'>";
			$check32T="<img src='img/cloud-filtered-42-grey.png'>";
			if($ligne["block"]==1){$check32T="<img src='img/webpage-settings-32-grey.png'>";}
			$arrow_right="<img src='img/arrow-right-32-grey.png'>";
		}
		
		if($ligne["transparent"]==0){
			$check32T="<img src='img/cloud-goto-42.png'>";
			$proxy=null;
		}

		$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?delete-js={$ligne["ID"]}&t={$_GET["t"]}',true)");
		$pattern=$ligne["pattern"];
		if($ligne["remote_proxy"]=="*"){$ligne["remote_proxy"]=null;}
		if($ligne["remote_proxy"]<>null){
			$proxy="$redirect_to {$ligne["remote_proxy"]}";
		}
		
		$link="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?item-js=yes&ID={$ligne["ID"]}&t={$_GET["t"]}',true)\"
		style='font-size:{$fontsize}px;font-weight:normal;color:$color;text-decoration:underline'>";
		
		if($ligne["destination_port"]>0){
			$destination_port="$port {$ligne["destination_port"]}";
		}
		
		if($ligne["destination"]=="*"){$ligne["destination"]=null;}
		$destination_clean=trim(strtolower($ligne["destination"]));
		if(isset($AVAILABLE_MACROS[$destination_clean])){
			$ligne["destination"]=$tpl->javascript_parse_text("{macro}: $destination_clean {websites}");
		}
		
		
		if($ligne["destination"]==null){$ligne["destination"]="$AllDestinations $destination_port";$proxy=null;}
		if($ligne["isnot"]==1){$isnot=$not;}
		
		$up=imgsimple("arrow-up-32.png",null,"Loadjs('$MyPage?move-item-js=yes&ID={$ligne["ID"]}&dir=0&t={$_GET["t"]}')");
		$down=imgsimple("arrow-down-32.png",null,"Loadjs('$MyPage?move-item-js=yes&ID={$ligne["ID"]}&dir=1&t={$_GET["t"]}')");
		$groups=groups($ligne["ID"]);
		if($groups[0]<>null){
			$ligne["pattern"]=$groups[0];
		}
		if($groups[1]<>null){
			$ligne["destination"]=$groups[1];
		}
		
		if($ligne["block"]==1){
			$check32T="<img src='img/cloud-deny-42.png'>";
			if($ligne["enabled"]==0){	$check32T="<img src='img/cloud-deny-42-grey.png'>";}
		}
		
		if($ligne["pattern"]==null){$ligne["pattern"]="* - $AllSystems";}
		if($ligne["pattern"]=="*"){$ligne["pattern"]="* - $AllSystems";}
		
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>{$ligne["zOrder"]}</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$proto</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$isnot$link$eth{$ligne["pattern"]}</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$arrow_right</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>{$ligne["destination"]}</a><div style='font-size:12px;text-align:right'>$proxy</div></span></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$check32T</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$check32</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$up</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$down</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$delete</span>",)
		);
	}


	echo json_encode($data);

}


function groups($ID){
	$q=new mysql_squid_builder();
	if($q->COUNT_ROWS("transparent_networks_groups")==0){return array(null,null);}
	$sql="SELECT transparent_networks_groups.gpid,
	transparent_networks_groups.zmd5 as mkey,
	webfilters_sqgroups.* FROM transparent_networks_groups,webfilters_sqgroups
	WHERE transparent_networks_groups.gpid=webfilters_sqgroups.ID 
	AND transparent_networks_groups.ruleid=$ID
	AND webfilters_sqgroups.enabled=1
	AND transparent_networks_groups.enabled=1
	";
	$results=$q->QUERY_SQL($sql);
	$acl=new squid_acls_groups();
	while ($ligne = mysql_fetch_assoc($results)) {
		$arrayF=$acl->FlexArray($ligne['ID']);
		$GroupType=$ligne["GroupType"];
		if($GroupType=="dst"){
			$f1[]=$arrayF["ROW"];
			continue;
		}
		
		if($GroupType=="port"){
			$f1[]=$arrayF["ROW"];
			continue;
		}
		
		$f[]=$arrayF["ROW"];
	}
	
	return array(@implode($f, "\n"),@implode($f1, "\n"));
	
}