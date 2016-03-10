<?php

	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.system.nics.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once('ressources/class.tcpip.inc');
	$usersmenus=new usersMenus();
	if($usersmenus->AsSystemAdministrator==false){exit();}
	if(isset($_GET["bridge-list"])){bridge_list();exit;}
	if(isset($_GET["network-bridge-js"])){network_bridge_js();exit;}
	if(isset($_GET["network-bridge-delete-js"])){network_bridge_delete_js();exit;}
	if(isset($_GET["network-bridge-associates-js"])){network_bridge_associates_js();exit;}
	if(isset($_GET["network-associates-delete-js"])){network_associates_delete_js();exit;}
	
	if(isset($_GET["network-bridge"])){network_bridge_popup();exit;}
	if(isset($_POST["Create"])){network_bridge_save();exit;}
	if(isset($_POST["Delete"])){network_bridge_del();exit;}
	if(isset($_GET["network-bridge-associates-popup"])){network_bridge_associates_popup();exit;}
	if(isset($_POST["associates"])){network_bridge_associates_save();exit;}
	if(isset($_POST["associates-delete"])){network_associates_delete();exit;}
	
	// nics_bridge
	
	Bridge_table();
	
function network_bridge_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	if($ID==0){
		$title=$tpl->javascript_parse_text("{new_net_bridge}");
	}else{
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT name FROM nics_bridge WHERE ID='$ID'","artica_backup"));
		$title=$tpl->javascript_parse_text("{network_bridge} {$ligne["name"]}");
	}
	echo "YahooWin2('850','$page?network-bridge=yes&ID=$ID&t={$_GET["t"]}','$title',true);";
}	




function network_bridge_associates_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	if($ID==0){
		$title=$tpl->javascript_parse_text("{associate_interface}");
	}else{
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT name FROM nics_bridge WHERE ID='$ID'","artica_backup"));
		$title=$tpl->javascript_parse_text("{associate_interface} {$ligne["name"]}");
	}
	echo "YahooWin2('850','$page?network-bridge-associates-popup=yes&ID=$ID&t={$_GET["t"]}','$title',true);";	
	
}

function network_bridge_popup(){
	$page=CurrentPageName();
	$t=time();
	$tpl=new templates();
	$sock=new sockets();
	$ID=$_GET["ID"];
	if(!is_numeric($ID)){$ID=0;}
	$q=new mysql();

	if($_GET["ID"]>0){
		$sql="SELECT * FROM nics_bridge WHERE ID='$ID'";
		$q=new mysql();
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$title_button="{apply}";
	}
	
	$nics_array[null]="{select}";
	$ous[null]="{select}";
	
	$but="{add}";
	$title="{new_network_bridge}";
	if($ID>0){
		$but="{apply}";
		$title="{network_bridge} {$ligne["name"]}";
	}

	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
	if(!is_numeric($ligne["STP"])){$ligne["STP"]=1;}
$html="
<div style='font-size:32px;margin-bottom:28px'>$title</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px' nowrap>{name}:</td>
		<td>". Field_text("name-$t",$ligne["name"],"font-size:22px;width:250px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' nowrap>Spanning Tree Protocol:</td>
		<td>". Field_checkbox_design("STP-$t",1,$ligne["STP"])."</td>
	</tr>				
				
				
	<tr>
		<td class=legend style='font-size:22px' nowrap>{tcp_address}:</td>
		<td>". field_ipv4("ipaddr-$t",$ligne["ipaddr"],"font-size:22px;width:250px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px' nowrap>{netmask}:</td>
		<td>". field_ipv4("netmask-$t",$ligne["netmask"],"font-size:22px;width:250px")."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:22px' nowrap>{cdir}:</td>
		<td>". Field_text("cdir-$t",$ligne["cdir"],"font-size:22px;width:250px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' nowrap>{broadcast}:</td>
		<td>". field_ipv4("broadcast-$t",$ligne["broadcast"],"font-size:22px;width:250px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' nowrap>{gateway}:</td>
		<td>". field_ipv4("gateway-$t",$ligne["gateway"],"font-size:22px;width:250px")."</td>
	</tr>																			
<tr>
	<td colspan=2 align='right'><hr>". button($but,"Save$t();","30")."</td>
</tr>
</table>
<script>
	var xSave$t= function (obj) {
	var ID='$ID';
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#flexRT{$_GET["t"]}').flexReload();
	ExecuteByClassName('SearchFunction');
	if(ID==0){YahooWin2Hide();}
}


function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('Create', '$ID');
	XHR.appendData('ID', '$ID');
	XHR.appendData('name', document.getElementById('name-$t').value);
	XHR.appendData('ipaddr', document.getElementById('ipaddr-$t').value);
	XHR.appendData('netmask', document.getElementById('netmask-$t').value);
	XHR.appendData('cdir', document.getElementById('cdir-$t').value);
	XHR.appendData('broadcast', document.getElementById('broadcast-$t').value);
	XHR.appendData('gateway', document.getElementById('gateway-$t').value);
	if(document.getElementById('STP-$t').checked){ XHR.appendData('STP', 1); }else{ XHR.appendData('STP', 0); }
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
";
echo $tpl->_ENGINE_parse_body($html);

}
function network_bridge_delete_js(){
	header("content-type: application/x-javascript");
	$ID=$_GET["network-bridge-delete-js"];
	if($ID==0){die();}
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql();
	$t=$_GET["t"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT name FROM nics_bridge WHERE ID='$ID'","artica_backup"));
	$confirm=$tpl->javascript_parse_text("{delete_network_bridge_warning}");
	$confirm=$tpl->javascript_parse_text(str_replace("%s", $ligne["name"], $confirm));
	
	echo "
var xSaveR$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#flexRT{$_GET["t"]}').flexReload();
	ExecuteByClassName('SearchFunction');

}


function SaveR$t(){
	if(!confirm('$confirm')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('Delete', '$ID');
	XHR.sendAndLoad('$page', 'POST',xSaveR$t);
}

SaveR$t();";
}
function network_associates_delete_js(){
	header("content-type: application/x-javascript");
	$ID=$_GET["network-associates-delete-js"];
	$nic=$_GET["nic"];
	if($ID==0){die();}
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql();
	$t=$_GET["t"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT name FROM nics_bridge WHERE ID='$ID'","artica_backup"));
	$confirm=$tpl->javascript_parse_text("{delete_network_bridge_warning}");
	$confirm=$tpl->javascript_parse_text(str_replace("%s", $ligne["name"], $confirm));
	
echo "
var xSaveR$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#flexRT{$_GET["t"]}').flexReload();
	ExecuteByClassName('SearchFunction');
}
	
	
function SaveR$t(){
	if(!confirm('$confirm')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('associates-delete', '$ID');
	XHR.appendData('associates-nic', '$nic');
	XHR.sendAndLoad('$page', 'POST',xSaveR$t);
}
	
	SaveR$t();";	
}

function network_associates_delete(){
	$eth=$_POST["associates-nic"];
	$nic=new system_nic($eth);
	$nic->Bridged=0;
	$nic->BridgedTo=null;
	$nic->SaveNic();	
	
}



function network_bridge_del(){
	$ID=$_POST["Delete"];
	if($ID==0){die();}
	$q=new mysql();
	
	$sql="SELECT `Interface` FROM `nics` WHERE `BridgedTo`='br{$ID}'";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		$nic=new system_nic($ligne["Interface"]);
		$nic->Bridged=0;
		$nic->BridgedTo=null;
		$nic->SaveNic();
	}
	
	
	$q->QUERY_SQL("DELETE FROM nics_bridge WHERE ID='$ID'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
}

function network_bridge_save(){
	$ID=$_POST["ID"];
	unset($_POST["ID"]);
	unset($_POST["Create"]);
	$q=new mysql();
	if(!isset($_POST["STP"])){$_POST["STP"]=1;}
	if(!$q->FIELD_EXISTS("nics_bridge", "STP", "artica_backup")){
		$q->QUERY_SQL("ALTER TABLE nics_bridge ADD STP smallint(1) DEFAULT 1","artica_backup");
		if(!$q->ok){echo "ALTER TABLE STP failed\n$q->mysql_error\n";return;}
	}

	if($_POST["netmask"]=='___.___.___.___'){$_POST["netmask"]="0.0.0.0";}
	if($_POST["gateway"]=='___.___.___.___'){$_POST["gateway"]="0.0.0.0";}
	if($_POST["ipaddr"]=='___.___.___.___'){$_POST["ipaddr"]="0.0.0.0";}


	while (list ($key, $value) = each ($_POST) ){
		$fields[]="`$key`";
		$values[]="'".mysql_escape_string2($value)."'";
		$edit[]="`$key`='".mysql_escape_string2($value)."'";

	}

	if($ID>0){
		$sql="UPDATE nics_bridge SET ".@implode(",", $edit)." WHERE ID=$ID";
	}else{
		$sql="INSERT IGNORE INTO nics_bridge (".@implode(",", $fields).") VALUES (".@implode(",", $values).")";

	}

	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	
}
	
	
function Bridge_table(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	$net=new networking();
	$interfaces=$net->Local_interfaces();
	unset($interfaces["lo"]);
	
	if(count($interfaces)<2){
		echo FATAL_ERROR_SHOW_128("{error_need_at_lease_2_pvinterfaces}");
		return;
	}
	
	
	$network_bridges=$tpl->_ENGINE_parse_body("{interfaces_bridges}");
	$nic_from=$tpl->javascript_parse_text("{nic_from}");
	$nic_to=$tpl->javascript_parse_text("{nic_to}");
	$tcp_address=$tpl->_ENGINE_parse_body("{tcp_address}");
	$netmask=$tpl->javascript_parse_text("{netmask}");
	$bridge=$network_bridges;
	$to=$tpl->_ENGINE_parse_body("{to}");
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$tablesize=868;
	$descriptionsize=705;
	$bts=array();
	$add=$tpl->_ENGINE_parse_body("{new_net_bridge}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$reconstruct=$tpl->javascript_parse_text("{build_the_network}");
	$bts[]="{name: '<strong style=font-size:18px>$add</strong>', bclass: 'add', onpress :RuleAdd$t},";
	$bts[]="{name: '<strong style=font-size:18px>$reconstruct</strong>', bclass: 'apply', onpress : BuildVLANs$t},";
	
	if(!$users->APP_EBTABLES_INSTALLED){
		$error="<p class=text-error>{APP_EBTABLES_NOT_INSTALLED}</p>";
	}
	
	
	
	
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}
	
	if(count($bts)>0){
			$buttons="buttons : [".@implode("\n", $bts)." ],";
		}
		$reboot_network_explain=$tpl->javascript_parse_text("{reboot_network_explain}");
		$html="$error
		<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	
		<script>
		var mm$t=0;
		$(document).ready(function(){
		$('#flexRT$t').flexigrid({
		url: '$page?bridge-list=yes&t=$t',
		dataType: 'json',
		colModel : [
		{display: '<span style=font-size:22px>ID</span>', 	      name : 'ID', width : 65, sortable : true, align: 'center'},
		{display: '<span style=font-size:22px>-</span>', 		      name : 'nic', width : 65, sortable : false, align: 'center'},
		{display: '<span style=font-size:22px>$bridge</span>', 	  name : 'name', width : 518, sortable : false, align: 'left'},
		{display: '<span style=font-size:22px>$tcp_address</span>', name : 'ipaddr', width : 222, sortable : false, align: 'right'},
		{display: '<span style=font-size:22px>$netmask</span>', 	  name : 'netmask', width : 222, sortable : false, align: 'right'},
		{display: '<span style=font-size:22px>-</span>', 	  		  name : 'delete', width : 65, sortable : false, align: 'center'},
		],$buttons
		searchitems : [
		{display: '$bridge', name : 'name'},
		{display: '$tcp_address', name : 'ipaddr'},
	
		],
		sortname: 'ID',
		sortorder: 'desc',
		usepager: true,
		title: '<span style=font-size:30px>$network_bridges</span>',
		useRp: true,
		rp: 25,
		showTableToggleBtn: false,
		width: '99%',
		height: 550,
		singleSelect: true
	
	});
	});
	
	function RuleAdd$t(){
	Loadjs('$page?network-bridge-js=yes&ID=0&t=$t',true);
	}
	
	function BuildVLANs$t(){
	Loadjs('network.restart.php?t=$t');
	
	}
	
	
	
	function EmptyTask$t(){
	if(confirm('$empty::{$_GET["taskid"]}')){
	
	}
	}
	var X_VLANDelete$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	$('#row'+mm$t).remove();
	}
	
	
	function VLANDelete(id){
	if(confirm('$delete '+id+'?')){
	var DisableNetworksManagement=$DisableNetworksManagement;
	if(DisableNetworksManagement==1){alert('$ERROR_NO_PRIVS');return;}
	mm$t=id;
	var XHR = new XHRConnection();
	XHR.appendData('vlan-del',id);
	XHR.sendAndLoad('$page', 'GET',X_VLANDelete$t);
	}
	}
	
	</script>
	
	";
	
		echo $tpl->_ENGINE_parse_body($html);
	}
	
function network_bridge_associates_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$net=new networking();
	$interfaces=$net->Local_interfaces();
	$ID=$_GET["ID"];
	$t=time();
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT name FROM nics_bridge WHERE ID='$ID'","artica_backup"));
	$title=$tpl->javascript_parse_text("{associate_interface} {$ligne["name"]}");
	
	unset($interfaces["lo"]);
	$t=$_GET["t"];
	while (list ($eth, $none) = each ($interfaces) ){
		$nic=new system_nic($eth);
		if($nic->Bridged==0){
		if($nic->BridgedTo==null){
			$array[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
			$array2[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
			}
		}
	}
	
$associate_interface_explain=$tpl->_ENGINE_parse_body("{associate_interface_explain}");
$associate_interface_explain=str_replace("%s", $ligne["name"], $associate_interface_explain);
$html="
<div style='font-size:32px;margin-bottom:20px'>$title</div>
<div class=explain style='font-size:16px'>$associate_interface_explain</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px' nowrap>{nic}:</td>
		<td>". Field_array_Hash($array, "associates-$t",null,"style:font-size:22px")."</td>
	</tr>
													
<tr>
	<td colspan=2 align='right'><hr>". button("{add}","Save$t();","24")."</td>
</tr>
</table>
<script>
	var xSave$t= function (obj) {
	var ID='$ID';
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#flexRT{$_GET["t"]}').flexReload();
	ExecuteByClassName('SearchFunction');
	YahooWin2Hide();
}


function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('associates', '$ID');
	XHR.appendData('ID', '$ID');
	XHR.appendData('eth', document.getElementById('associates-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
";
echo $tpl->_ENGINE_parse_body($html);
	
}

function network_bridge_associates_save(){
	$eth=$_POST["eth"];
	$nic=new system_nic($eth);
	$nic->Bridged=1;
	$nic->BridgedTo="br{$_POST["ID"]}";
	$nic->SaveNic();
	
}
	
	
function bridge_list(){
	$q=new mysql();
	$tpl=new templates();
	$database="artica_backup";
	$table="nics_bridge";
	$MyPage=CurrentPageName();
	$t=$_GET["t"];
	
	if(!$q->TABLE_EXISTS("nics_bridge", "artica_backup")){
		json_error_show("nics_bridge no such table...",1);
	}
	
	$sock=new sockets();
	$net=new networking();
	$ip=new IP();
	$interfaces=unserialize(base64_decode($sock->getFrameWork("cmd.php?ifconfig-interfaces=yes")));
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show($q->mysql_error."<hr>".$sql,1);}
		$total = $ligne["tcount"];
	
	}else{
		$total = $q->COUNT_ROWS($table, $database);
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($page)){$page=1;}
	if(!is_numeric($rp)){$rp=50;}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	
	$sql="SELECT * FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show($q->mysql_error."<hr>".$sql,1);}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("No rule set...",1);}
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$md=md5(serialize($ligne));
		$color="black";
		$ip=new IP();
		$cdir=$ligne["cdir"];
		$eth="br{$ligne["ID"]}";
		$eth_text="br{$ligne["ID"]}";
		
		if($ligne["cdir"]==null){
			$ligne["cdir"]=$net->array_TCP[$ligne["nic"]];
			$eth=$ligne["nic"];
		}
		
		if($ligne["cdir"]==null){
			$ligne["cdir"]=$net->array_TCP[$ligne["nic"]];
			$eth=$ligne["nic"];
		}
		$img="folder-network-48.png";
		
			
		$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?network-bridge-delete-js={$ligne['ID']}&t=$t',true)");
		
		$js="Loadjs('$MyPage?network-bridge-js=yes&ID={$ligne['ID']}&t=$t',true);";
		$href="<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:22px;color:$color;font-weight:normal;text-decoration:underline'>";
	
		
		$a=$ip->parseCIDR($cdir);
		if($a[0]==0){
			$img="warning-panneau-24.png";
			$cdir="<span style='color:#d32d2d'>$cdir</span>";
		}
		$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?network-bridge-delete-js={$ligne['ID']}&t=$t',true)");
	
		$js="Loadjs('$MyPage?network-bridge-js=yes&ID={$ligne['ID']}&t=$t',true);";
		$href="<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:22px;color:$color;font-weight:normal;text-decoration:underline'>";
		$bridgedTo=bridgedTo($ligne["ID"]);
		$data['rows'][] = array(
			'id' => $ligne['ID'],
			'cell' => array(
				"<center style='font-size:22px;font-weight:bold;color:$color'>{$ligne['ID']}</center>",
				"<center style='font-size:22px;font-weight:normal;color:$color'>$href$eth_text</a></center>",
				"
				<span style='margin:5px;float:right'>
					<a href=\"javascript:blur();\" style=\"font-size:18px\" OnClick=\"javascript:Loadjs('$MyPage?network-bridge-associates-js=yes&ID={$ligne['ID']}&t=$t',true);\">
					<img src='img/add-32.png'></a>
				</span>		
				<span style='font-size:22px;font-weight:normal;color:$color'>

				{$ligne["name"]}
				$bridgedTo
				
				</span>",
				"<span style='font-size:22px;font-weight:normal;color:$color'>{$ligne["ipaddr"]}</span>",
				"<span style='font-size:22px;font-weight:normal;color:$color'>{$ligne["netmask"]}</span>",
				"<center>$delete</center>"
			)
		);
	}
	
	
echo json_encode($data);
}	


function bridgedTo($ID){
	$MyPage=CurrentPageName();

	$t=$_GET["t"];
	$q=new mysql();
	$tpl=new templates();
	$sql="SELECT `Interface` FROM `nics` WHERE `BridgedTo`='br{$ID}'";
$delete_t=$tpl->javascript_parse_text("{delete}");
	
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(mysql_num_rows($results)==0){
		return $tpl->_ENGINE_parse_body("<br><i style=\"font-size:12px\">{click_on_plus_to_link_interface}</i>");
	}
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		
		$delete="<a href=\"javascript:blur();\" 
		OnClick=\"Loadjs('$MyPage?network-associates-delete-js=$ID&t=$t&nic={$ligne["Interface"]}',true)\" style=text-decoration:underline>";
		
		$nic=new system_nic($ligne["Interface"]);
		$html[]="
		<span style=\"font-size:18px;border:0px;vertical-align:middle\">{$ligne["Interface"]} $nic->IPADDR - $nic->NICNAME&nbsp;[$delete$delete_t</a>]</span>";
				
		
	}
	if(count($html)>0){
	return "<br>".@implode("<br>", $html);}

}
