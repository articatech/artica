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
	if($usersmenus->AsSystemAdministrator==false){echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die();}
	
	if(isset($_GET["popup"])){table();exit;}
	if(isset($_GET["rules-list"])){rules_list();exit;}
	if(isset($_GET["network-bridge-js"])){network_bridge_js();exit;}
	if(isset($_GET["network-bridge-delete-js"])){network_bridge_delete_js();exit;}
	if(isset($_GET["bridge-wizard-delete-js"])){bridge_wizard_delete_js();exit;}
	if(isset($_POST["Delete-wizard"])){bridge_wizard_delete();exit;}
	
	if(isset($_GET["network-bridge"])){network_bridge_tab();exit;}
	if(isset($_GET["network-bridge-popup"])){network_bridge_popup();exit;}
	if(isset($_POST["Create"])){network_bridge_save();exit;}
	if(isset($_POST["Delete"])){network_bridge_del();exit;}
	tabs();
	
	
	
function bridge_wizard_delete_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$q=new mysql();
	$t=$_GET["t"];
	$FireHolConf=unserialize(base64_decode($sock->GET_INFO("FireHolConf")));
	if(!isset($FireHolConf["IF_LAN"])){$FireHolConf["IF_LAN"]=null;}
	if(!isset($FireHolConf["IF_WAN"])){$FireHolConf["IF_WAN"]=null;}
	
	
	$confirm=$tpl->javascript_parse_text("{delete} {packets_from} {$FireHolConf["IF_LAN"]} {should_be_forwarded_to} {$FireHolConf["IF_WAN"]} ?");
	
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
	XHR.appendData('Delete-wizard', 'yes');
	XHR.sendAndLoad('$page', 'POST',xSaveR$t);  			
}
	
SaveR$t();";
}	

function bridge_wizard_delete(){
	$sock=new sockets();
	$FireHolConf=unserialize(base64_decode($sock->GET_INFO("FireHolConf")));
	if(!isset($FireHolConf["IF_LAN"])){$FireHolConf["IF_LAN"]=null;}
	if(!isset($FireHolConf["IF_WAN"])){$FireHolConf["IF_WAN"]=null;}
	$FireHolConf["IF_LAN"]=null;
	$FireHolConf["IF_WAN"]=null;
	$sock->SaveConfigFile(base64_encode(serialize($FireHolConf)), "FireHolConf");
}
	
function network_bridge_delete_js(){
	header("content-type: application/x-javascript");
	$ID=$_GET["network-bridge-delete-js"];
	if($ID==0){die();}
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql();
	$t=$_GET["t"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM pnic_bridges WHERE ID='$ID'","artica_backup"));
	$confirm=$tpl->javascript_parse_text("{delete} {packets_from} {$ligne["nic_from"]} {should_be_forwarded_to} {$ligne["nic_to"]} ?");
	
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
	
function network_bridge_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	if($ID==0){
		$title=$tpl->javascript_parse_text("{new_router}");
	}else{
		$title=$tpl->javascript_parse_text("{router} ID:$ID");
	}
		
	echo "YahooWin2('900','$page?network-bridge=yes&ID=$ID&t={$_GET["t"]}','$title',true);";
	
}
function network_bridge_del(){
	$ID=$_POST["Delete"];
	if($ID==0){die();}
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM pnic_bridges WHERE ID='$ID'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$q->QUERY_SQL("DELETE FROM `firehol_routers_exclude` WHERE `routerid`='$ID'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
	$q->QUERY_SQL("DELETE FROM `firehol_services_routers` WHERE `routerid`='$ID'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}	
}


function network_bridge_save(){
	$ID=$_POST["ID"];
	$tpl=new templates();
	$q=new mysql();
	$nic_from=$_POST["nic_from"];
	$nic_to=$_POST["nic_to"];
	if(!isset($_POST["STP"])){$_POST["STP"]=1;}
	if($nic_from==$nic_to){
		echo $tpl->javascript_parse_text("{cannot_route_the_same_interface}");
		return;
	}
	
	if(!$q->FIELD_EXISTS("pnic_bridges", "zMD5", "artica_backup")){
		$q->QUERY_SQL("ALTER TABLE pnic_bridges ADD zMD5 varchar(90), ADD UNIQUE KEY (`zMD5`)","artica_backup");
		if(!$q->ok){echo "ALTER TABLE pnic_bridges failed\n$q->mysql_error\n";return;}
	}
	
	if(!$q->FIELD_EXISTS("pnic_bridges", "STP", "artica_backup")){
		$q->QUERY_SQL("ALTER TABLE pnic_bridges ADD STP smallint(1) DEFAULT 1","artica_backup");
		if(!$q->ok){echo "ALTER TABLE STP failed\n$q->mysql_error\n";return;}
	}	
	
	if(!$q->FIELD_EXISTS("pnic_bridges", "DenyDHCP", "artica_backup")){
		$q->QUERY_SQL("ALTER TABLE pnic_bridges ADD DenyDHCP smallint(1) DEFAULT 1","artica_backup");
		if(!$q->ok){echo "ALTER TABLE DenyDHCP failed\n$q->mysql_error\n";return;}
	}	
	
	if(!$q->FIELD_EXISTS("pnic_bridges", "DenyCountries", "artica_backup")){
		$q->QUERY_SQL("ALTER TABLE pnic_bridges ADD DenyCountries smallint(1) DEFAULT 0","artica_backup");
		if(!$q->ok){echo "ALTER TABLE DenyCountries failed\n$q->mysql_error\n";return;}
	}	
	
	$zMD5=md5($nic_from.$nic_to);
	if($ID==0){$sql="INSERT INTO pnic_bridges (zMD5,nic_from,nic_to,enabled,STP,DenyDHCP,masquerading,masquerading_invert) 
	VALUES ('$zMD5','$nic_from','$nic_to','{$_POST["enabled"]}','{$_POST["STP"]}','{$_POST["DenyDHCP"]}','{$_POST["masquerading"]}','{$_POST["masquerading_invert"]}')";}
	if($ID>0){$sql="UPDATE pnic_bridges SET `zMD5`='$zMD5',
	`nic_from`='$nic_from',nic_to='$nic_to',enabled={$_POST["enabled"]},
	`STP`='{$_POST["STP"]}',
	`DenyDHCP` ='{$_POST["DenyDHCP"]}',
	`masquerading` ='{$_POST["masquerading"]}',
	`masquerading_invert` ='{$_POST["masquerading_invert"]}',
	`DenyCountries`='{$_POST["DenyCountries"]}'
	WHERE ID=$ID";}
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
}

function network_bridge_tab(){
	$ID=$_GET["ID"];
	if(!is_numeric($ID)){$ID=0;}
	if($ID==0){network_bridge_popup();exit;}
	$page=CurrentPageName();
	$tpl=new templates();
	$tt=time();
	$t=$_GET["t"];
	
	$array["network-bridge-popup"]='{router}';
	$array["services"]='{services}';
	
	
	
	while (list ($num, $ligne) = each ($array) ){
		if($num=="services"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"system.network.bridges.services.php?routerid=$ID\" 
					style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}
	

		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&ID=$ID\" 
				style='font-size:18px'><span>$ligne</span></a></li>\n");
	}
	echo build_artica_tabs($html, "main_network_bridge_popup");
	
	
}


function network_bridge_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	$sock=new sockets();
	$EnableIpBlocks=intval($sock->GET_INFO("EnableIpBlocks"));
	if(!is_numeric($ID)){$ID=0;}
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM pnic_bridges WHERE ID='$ID'","artica_backup"));
	$net=new networking();
	$interfaces=$net->Local_interfaces();
	unset($interfaces["lo"]);
	$t=$_GET["t"];
	while (list ($eth, $none) = each ($interfaces) ){
		$nic=new system_nic($eth);
		$array[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
		$array2[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
		
	}
	
	$but="{add}";
	$title="{new_router}";
	if($ID>0){
			$but="{apply}";
			$title="{router} {$ligne["nic_from"]}2{$ligne["nic_to"]}";
		}
	
	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
	if(!is_numeric($ligne["DenyDHCP"])){$ligne["DenyDHCP"]=1;}
	if(!is_numeric($ligne["masquerading"])){$ligne["masquerading"]=0;}
	if(!is_numeric($ligne["masquerading_invert"])){$ligne["masquerading_invert"]=0;}
	if(!is_numeric($ligne["DenyCountries"])){$ligne["DenyCountries"]=0;}
	
	
	
	
	$html="
	<div style='font-size:32px;margin-bottom:20px'>$title</div>		
	<div style='width:98%' class=form>
	
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px' nowrap>{enabled}:</td>
		<td>". Field_checkbox_design("enabled-$t",1, $ligne["enabled"])."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px' nowrap>{packets_from}:</td>
		<td>". Field_array_Hash($array, "nic_from-$t",$ligne["nic_from"],"style:font-size:18px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px' nowrap>{should_be_forwarded_to}:</td>
		<td>". Field_array_Hash($array2, "nic_to-$t",$ligne["nic_to"],"style:font-size:18px")."</td>
	</tr>	

	<tr>
		<td class=legend style='font-size:18px' nowrap>{deny_dhcp_requests}:</td>
		<td>". Field_checkbox_design("DenyDHCP-$t", 1,$ligne["DenyDHCP"])."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px' nowrap>{enable_ipblocks}:</td>
		<td>". Field_checkbox_design("DenyCountries-$t", 1,$ligne["DenyCountries"])."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:18px' nowrap>{masquerading}:</td>
		<td>". Field_checkbox_design("masquerading-$t", 1,$ligne["masquerading"],"masqueradingCheck()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px' nowrap>{masquerading_invert}:</td>
		<td>". Field_checkbox_design("masquerading_invert-$t", 1,$ligne["masquerading_invert"],"masquerading_invertcheck()")."</td>
	</tr>				
	
	<tr>
		<td colspan=2 align='right'><hr>". button($but,"Save$t();","24")."</td>
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
	XHR.appendData('nic_from', document.getElementById('nic_from-$t').value);	
	XHR.appendData('nic_to', document.getElementById('nic_to-$t').value);	
	if(document.getElementById('enabled-$t').checked){XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);};	      
	if(document.getElementById('DenyDHCP-$t').checked){XHR.appendData('DenyDHCP',1);}else{XHR.appendData('DenyDHCP',0);};
	if(document.getElementById('masquerading-$t').checked){XHR.appendData('masquerading',1);}else{XHR.appendData('masquerading',0);};
	if(document.getElementById('masquerading_invert-$t').checked){XHR.appendData('masquerading_invert',1);}else{XHR.appendData('masquerading_invert',0);};
	
	
	XHR.sendAndLoad('$page', 'POST',xSave$t);  			
}

function masqueradingCheck(){
	if(document.getElementById('masquerading-$t').checked){
		document.getElementById('masquerading_invert-$t').checked=false;
	}
}
function masquerading_invertcheck(){
	if(document.getElementById('masquerading_invert-$t').checked){
		document.getElementById('masquerading-$t').checked=false;
	}
}



</script>	
";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}


	
function tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
		
	$array["popup"]="{network_bridges}";
	$array["bro"]="{interfaces_bridges}";
	
	
	$fontsize="font-size:22px";
	if(isset($_GET["newinterface"])){$fontsize="font-size:14px;";$linkadd="&newinterface=yes";$tabwidth="100%";}
	
	while (list ($num, $ligne) = each ($array) ){
			
		if($num=="arpspoof"){
			$html[]= "<li><a href=\"arp.spoof.php?none=yes$linkadd\"><span style='$fontsize'>". $tpl->_ENGINE_parse_body($ligne)."</span></a></li>\n";
			continue;
		}	

		if($num=="bro"){
			$html[]= "<li><a href=\"system.network.bridges.interfaces.php\"><span style='$fontsize'>". $tpl->_ENGINE_parse_body($ligne)."</span></a></li>\n";
			continue;
		}		

		$html[]= "<li><a href=\"$page?$num=yes\"><span style='$fontsize'>". $tpl->_ENGINE_parse_body($ligne)."</span></a></li>\n";
	}
	
	$tab=time();
	
	echo build_artica_tabs($html, "tabs_network_bridges")."<script>LeftDesign('bridge-network-256-opac20.png');</script>";
}


function table(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$net=new networking();
	$interfaces=$net->Local_interfaces();
	unset($interfaces["lo"]);
	
	if(count($interfaces)<2){
		echo FATAL_ERROR_SHOW_128("{error_need_at_lease_2_pvinterfaces}");
		return;
	}


		$network_bridges=$tpl->_ENGINE_parse_body("{routers}");
		$nic_from=$tpl->javascript_parse_text("{nic_from}");
		$nic_to=$tpl->javascript_parse_text("{nic_to}");
		$tcp_address=$tpl->_ENGINE_parse_body("{tcp_address}");
		$netmask=$tpl->javascript_parse_text("{netmask}");
		$to=$tpl->_ENGINE_parse_body("{to}");
		$t=$_GET["t"];
		if(!is_numeric($t)){$t=time();}
		$tablesize=868;
		$descriptionsize=705;
		$bts=array();
		$add=$tpl->_ENGINE_parse_body("{new_router}");
		$delete=$tpl->javascript_parse_text("{delete}");
		$about=$tpl->javascript_parse_text("{about2}");
		$reconstruct=$tpl->javascript_parse_text("{apply_firewall_rules}");
		$bts[]="{name: '<strong style=font-size:18px>$add</strong>', bclass: 'add', onpress :RuleAdd$t},";
		$bts[]="{name: '<strong style=font-size:18px>$reconstruct</strong>', bclass: 'apply', onpress : BuildVLANs$t},";
		$bts[]="{name: '<strong style=font-size:18px>$about</strong>', bclass: 'help', onpress : About$t},";
	
	
	
	
		$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
		if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}
	
		if(count($bts)>0){
			$buttons="buttons : [".@implode("\n", $bts)." ],";
		}
		$reboot_network_explain=$tpl->javascript_parse_text("{bridges_iptables_explain}\n\n{reboot_network_explain}");
		$html="
		
		<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
		
		<script>
		var mm$t=0;
		$(document).ready(function(){
		$('#flexRT$t').flexigrid({
		url: '$page?rules-list=yes&t=$t',
		dataType: 'json',
		colModel : [
		{display: 'ID', name : 'ID', width : 31, sortable : true, align: 'center'},
		{display: '$nic_from', name : 'nic_from', width : 514, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'none1', width : 50, sortable : false, align: 'center'},
		{display: '$nic_to', name : 'nic_to', width : 736, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 70, sortable : false, align: 'center'},
		],$buttons
		searchitems : [
		{display: '$nic_from', name : 'nic_from'},
		{display: '$nic_to', name : 'nic_to'},

		],
		sortname: 'ID',
		sortorder: 'desc',
		usepager: true,
		title: '<span style=font-size:22px>$network_bridges</span>',
		useRp: true,
		rp: 25,
		showTableToggleBtn: false,
		width: '99%',
		height: 400,
		singleSelect: true
	
	});
	});
	
	function RuleAdd$t(){
		Loadjs('$page?network-bridge-js=yes&ID=0&t=$t',true);
	}
	
	function BuildVLANs$t(){
		Loadjs('firehol.progress.php?t=$t');
	
	}
	
	function About$t(){
		alert('$reboot_network_explain');
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

function rules_list(){
	$q=new mysql();
	$tpl=new templates();
	$database="artica_backup";
	$table="pnic_bridges";
	$MyPage=CurrentPageName();
	$t=$_GET["t"];
	$sock=new sockets();
	$FireHoleTrue=false;
	$FireHolConf=unserialize(base64_decode($sock->GET_INFO("FireHolConf")));
	if(!isset($FireHolConf["IF_LAN"])){$FireHolConf["IF_LAN"]=null;}
	if(!isset($FireHolConf["IF_WAN"])){$FireHolConf["IF_WAN"]=null;}
	
	if($FireHolConf["IF_LAN"]<>null && $FireHolConf["IF_WAN"]<>null){
		$FireHoleTrue=true;
	}
	
	if(!$q->TABLE_EXISTS("pnic_bridges", "artica_backup")){
		$sql="CREATE TABLE IF NOT EXISTS `pnic_bridges` (
		`ID` INT(10) NOT NULL AUTO_INCREMENT,
		`zMD5` varchar(90) NOT NULL,
		`nic_from` varchar(50) NOT NULL,
		`nic_to` varchar(50) NOT NULL,
		`enabled` smallint(1) NOT NULL DEFAULT 1,
		`DenyDHCP` smallint(1) NOT NULL DEFAULT 1,
		PRIMARY KEY (`ID`),
		UNIQUE KEY (`zMD5`),
		KEY `nic_from` (`nic_from`),
		KEY `nic_to` (`nic_to`),
		KEY `DenyDHCP` (`DenyDHCP`),
		KEY `enabled` (`enabled`)
		) ENGINE=MYISAM;";
		$q->QUERY_SQL($sql,$database);
	}
	
	if(!$q->FIELD_EXISTS("pnic_bridges", "DenyDHCP", "artica_backup")){
		$q->QUERY_SQL("ALTER TABLE pnic_bridges ADD DenyDHCP smallint(1) DEFAULT 1","artica_backup");
		if(!$q->ok){echo "ALTER TABLE DenyDHCP failed\n$q->mysql_error\n";return;}
	}
	if(!$q->FIELD_EXISTS("pnic_bridges", "masquerading", "artica_backup")){
		$q->QUERY_SQL("ALTER TABLE pnic_bridges ADD masquerading smallint(1) DEFAULT 0","artica_backup");
		if(!$q->ok){echo "ALTER TABLE masquerading failed\n$q->mysql_error\n";return;}
	}	
	if(!$q->FIELD_EXISTS("pnic_bridges", "masquerading_invert", "artica_backup")){
		$q->QUERY_SQL("ALTER TABLE pnic_bridges ADD masquerading_invert smallint(1) DEFAULT 0","artica_backup");
		if(!$q->ok){echo "ALTER TABLE masquerading_invert failed\n$q->mysql_error\n";return;}
	}	
	
	
	if(!$q->TABLE_EXISTS("pnic_bridges", "artica_backup")){
		json_error_show("Unable to create table...",1);
	}
	
	$sock=new sockets();
	$net=new networking();
	$ip=new IP();
	$interfaces=unserialize(base64_decode($sock->getFrameWork("cmd.php?ifconfig-interfaces=yes")));
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}
	$EnableIpBlocks=intval($sock->GET_INFO("EnableIpBlocks"));
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	
	$searchstring=string_to_flexquery();
	$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE 1 $searchstring";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
	if(!$q->ok){json_error_show($q->mysql_error."<hr>".$sql,1);}
	$total = intval($ligne["tcount"]);
	
	
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	$deny_dhcp_requests=$tpl->_ENGINE_parse_body("{deny_dhcp_requests}");
	$sql="SELECT * FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show($q->mysql_error."<hr>".$sql,1);}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){
		if(!$FireHoleTrue){json_error_show("No rule set...",1);}
	}
	
	if($searchstring==null){
		if($FireHoleTrue){
			$data['total']++;
			$nic_from=$FireHolConf["IF_LAN"];
			$nic_to=$FireHolConf["IF_WAN"];
			$arrow="arrow-right-32.png";
			$color="black";
			$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?bridge-wizard-delete-js={$ligne['ID']}&t=$t',true)");
			
			$data['rows'][] = array(
					'id' => $ligne['ID'],
					'cell' => array(
							"<span style='font-size:18px;font-weight:bold;color:$color'>0</span>",
							"<span style='font-size:18px;font-weight:normal;color:$color'>$nic_from - LAN -</a></span>",
							"<center><img src=\"img/$arrow\"></center>",
							"<span style='font-size:18px;font-weight:normal;color:$color'>$nic_to - WAN -</span>",
							"<center>$delete</center>"
					)
			);
		}
	}
	
	
	$BEHA["reject"]="{strict_mode}";
	$BEHA["accept"]="{trusted_mode}";
	
	
	$BEHA2[0]="{not_defined}";
	$BEHA2[1]="{act_as_lan}";
	$BEHA2[2]="{act_as_wan}";
	
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$deny_dhcp_requeststxt=null;
		$arrow="arrow-right-32.png";
		if($ligne["enabled"]==0){$color="#ABABAB";$arrow="arrow-right-32-grey.png";}
		$ip=new IP();
		$nic_from=$ligne["nic_from"];
		$nic_to=$ligne["nic_to"];
		$masquerading=null;
		$DenyCountries=null;
		if($ligne["DenyDHCP"]==1){
			$deny_dhcp_requeststxt=$deny_dhcp_requests;
		}
		
		if($ligne["masquerading"]==1){
			$masquerading=$tpl->javascript_parse_text(", {masquerading}");
		}
		if($ligne["masquerading_invert"]==1){
			$masquerading=$tpl->javascript_parse_text(", {masquerading_invert}");
		}	

		if($EnableIpBlocks==1){
			if($ligne["DenyCountries"]==1){
				$DenyCountries=$tpl->javascript_parse_text(", {enable_ipblocks}");
			}
		}
		
		$nic=new system_nic($nic_from);
		$b0=$BEHA2[$nic->firewall_behavior]."/".$BEHA[$nic->firewall_policy];
		$nic_from_text="<strong style='color:$color'>$nic_from</strong> $nic->IPADDR/$nic->NETMASK $nic->NICNAME";
		$b0=$tpl->_ENGINE_parse_body($b0);
		
		
		$nic=new system_nic($nic_to);
		$b1=$BEHA2[$nic->firewall_behavior]."/".$BEHA[$nic->firewall_policy];
		$nic_to_text="<strong style='color:$color'>$nic_to</strong> $nic->IPADDR/$nic->NETMASK $nic->NICNAME";
		$b1=$tpl->_ENGINE_parse_body($b1);
		
	
		$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?network-bridge-delete-js={$ligne['ID']}&t=$t',true)");
		
		$js="Loadjs('$MyPage?network-bridge-js=yes&ID={$ligne['ID']}&t=$t',true);";
		$href="<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:18px;color:$color;font-weight:normal;text-decoration:underline'>";
	
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<span style='font-size:18px;font-weight:bold;color:$color'>{$ligne['ID']}</span>",
						"<span style='font-size:18px;font-weight:normal;color:$color'>$href{$nic_from_text}</a>
						<br></span><span style='font-size:14px;font-weight:bold'>$b0</span>",
						"<center><img src=\"img/$arrow\"></center>",
						"<span style='font-size:18px;font-weight:normal;color:$color'>$nic_to_text<br><span style='font-size:14px;font-weight:bold'>$b1</span><br><span style='font-size:18px'><i>$deny_dhcp_requests$masquerading</span></span>",
						"<center>$delete</center>"
				)
		);
	}
	
	
	echo json_encode($data);	
	
	
}

