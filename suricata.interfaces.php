<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.dnsmasq.inc');
	include_once('ressources/class.main_cf.inc');
	include_once('ressources/class.system.network.inc');

	
	if(posix_getuid()<>0){
		$user=new usersMenus();
		if($user->AsDnsAdministrator==false){
			$tpl=new templates();
			echo $tpl->_ENGINE_parse_body("alert('{ERROR_NO_PRIVS}');");
			die();exit();
		}
	}	
	
	if(isset($_GET["list"])){interfaces_list();exit;}
	if(isset($_GET["hosts"])){Loadaddresses();exit;}
	if(isset($_POST["SuricataEnableInterface"])){SuricataEnableInterface();exit();}
	if(isset($_POST["SuricataDeleteInterface"])){SuricataDeleteInterface();exit();}
	
	if(isset($_GET["add-interface-js"])){add_interface_js();exit;}
	if(isset($_GET["add-interface-popup"])){add_interface_popup();exit;}
	if(isset($_POST["eth"])){interfaces_add();exit;}
	table();
	
function add_interface_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	if(!isset($_GET["eth"])){$_GET["eth"]=null;}
	$title=$_GET["eth"];
	if($_GET["eth"]==null){$title=$tpl->_ENGINE_parse_body("{new_interface}");}
	$html="YahooWin4('550','$page?add-interface-popup=yes&eth={$_GET["eth"]}','$title');";
	echo $html;
}	
function table(){
		$page=CurrentPageName();
		$tpl=new templates();
		$t=time();
		$dnsmasq_address_text=$tpl->_ENGINE_parse_body("{dnsmasq_address_text}");
		$hosts=$tpl->_ENGINE_parse_body("{hosts}");
		$addr=$tpl->_ENGINE_parse_body("{addr}");
		$new_interface=$tpl->_ENGINE_parse_body("{new_interface}");
		$interface=$tpl->_ENGINE_parse_body("{interface}");
		$threads=$tpl->javascript_parse_text("{threads}");
		$title=$tpl->_ENGINE_parse_body("{listen_interface}");
		
		$q=new mysql();
		$sql="CREATE TABLE IF NOT EXISTS `suricata_interfaces` (
				  `interface` varchar(50) NOT NULL PRIMARY KEY,
				  `threads` smallint(10) NOT NULL DEFAULT 0,
				  `enable` smallint(1) NOT NULL,
				  KEY `interface` (`interface`),
				  KEY `threads` (`threads`),
				  KEY `enable` (`enable`)
				) ENGINE=MYISAM;";
		$q->QUERY_SQL($sql,"artica_backup");
		$apply=$tpl->javascript_parse_text("{apply}");
		
		$buttons="
		buttons : [
		{name: '<strong style=font-size:18px>$new_interface</strong>', bclass: 'add', onpress : Add$t},
		{name: '<strong style=font-size:18px>$apply</strong>', bclass: 'Apply', onpress : Apply$t},
		],";
	
		$html="
	
		
<table class='TABLE_SURICATA_INTERFACE' style='display: none' id='TABLE_SURICATA_INTERFACE' 
style='width:100%'></table>
<script>
$(document).ready(function(){
	var md5H='';
	$('#TABLE_SURICATA_INTERFACE').flexigrid({
		url: '$page?list=yes',
		dataType: 'json',
		colModel : [
		{display: '&nbsp;', name : 'none', width : 80, sortable : false, align: 'left'},
		{display: '<span style=font-size:22px>$interface</span>', name : 'interface', width : 300, sortable : true, align: 'left'},
		{display: '<span style=font-size:22px>$threads</span>', name : 'threads', width : 133, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'disable', width : 90, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'delete', width : 90, sortable : false, align: 'left'},
		],
		$buttons

		sortname: 'interface',
		sortorder: 'asc',
		usepager: true,
		title: '<span style=font-size:30px>$title</span>',
		useRp: true,
		rp: 10,
		showTableToggleBtn: false,
		width: '99%',
		height: 550,
		singleSelect: true,
		rpOptions: [10, 20, 30, 50,100,200]
	
	});
	});
	
	
function Add$t(){
	Loadjs('$page?add-interface-js=yes&t=$t');
}
var xSuricataEnableInterface= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	$('#TABLE_SURICATA_INTERFACE').flexReload();
}

function SuricataEnableInterface(interface){
	var XHR = new XHRConnection();	
	XHR.appendData('SuricataEnableInterface',interface);	
	XHR.sendAndLoad('$page', 'POST',xSuricataEnableInterface);	
}
	

function SuricataDeleteInterface(interface){
	var XHR = new XHRConnection();	
	XHR.appendData('SuricataDeleteInterface',interface);	
	XHR.sendAndLoad('$page', 'POST',xSuricataEnableInterface);	
	
	
}	
	
var xDnsmasqDeleteInterface= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	$('#TABLE_SURICATA_INTERFACE').flexReload();
}
function Apply$t(){
	Loadjs('suricata.progress.php');
}

</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function add_interface_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$nic=new networking();
	$nicZ=$nic->Local_interfaces();
	
	$NICS[null]="{select}";
	while (list ($yinter, $line) = each ($nicZ) ){
		$znic=new system_nic($yinter);
		if($znic->Bridged==1){continue;}
		$NICS[$yinter]="[$yinter] - $znic->NICNAME";
	}
	
	$interface_field="	<tr>
		<td class=legend style='font-size:22px' nowrap>{interface}:</td>
		<td>". Field_array_Hash($NICS,"eth-$t",$_GET["eth"],"style:font-size:22px")."</td>
	</tr>";
	
	if($_GET["eth"]<>null){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM suricata_interfaces WHERE interface='{$_GET["eth"]}'","artica_backup"));
		$title="{$_GET["eth"]}::".$tpl->javascript_parse_text($ligne["rulename"]);
		$interface_field="
		<tr>
			<td class=legend style='font-size:22px' nowrap>{interface}:</td>
			<td style='font-size:22px;font-weight:bold'>{$NICS[$_GET["eth"]]}
				<input type='hidden' id='eth-$t' value='{$_GET["eth"]}'>
			</td>
		</tr>";
	}else{
		
		$ligne["threads"]=2;
		$ligne["enable"]=1;
	}
	

	
	$html="
<center id='id-$t' style='width:98%' class=form>
	<table style=width:100%'>
	<tbody>
	$interface_field
	<tr>
		<td class=legend style='font-size:22px' nowrap>{enabled}:</td>
		<td>". Field_checkbox_design("enabled-$t", 1,$ligne["enable"])."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:22px'>{threads}</td>
		<td>" . Field_text("threads-$t",null,"font-size:22px;padding:3px;width:150px") . "</td>
	</tr>				
	<tr>
		<td colspan=2 align='right'><hr>". button("{add}","Save$t()",30)."</td>
	</tr>
	</tbody>
</table>
</center>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#TABLE_SURICATA_INTERFACE').flexReload();
	YahooWin4Hide();
}
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('eth',document.getElementById('eth-$t').value);
	XHR.appendData('threads',document.getElementById('threads-$t').value);
	if(document.getElementById('enabled-$t').checked){
		XHR.appendData('enabled',1); }else{ XHR.appendData('enabled',0); 
	}
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
";
echo $tpl->_ENGINE_parse_body($html);
}

function interfaces_list(){
	$search='%';
	$page=1;	
	
	$q=new mysql();
	$tpl=new templates();
	
	$searchstring=string_to_flexquery();
	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	


	if($searchstring<>null){
	
		
		$sql="SELECT COUNT(*) AS TCOUNT FROM suricata_interfaces WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) AS tcount FROM suricata_interfaces";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	
	$sql="SELECT * FROM suricata_interfaces WHERE 1 $searchstring $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){json_error_show($q->mysql_error,1);}
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = mysql_num_rows($results);
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("No data",1);}
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$icon="folder-network-42.png";
		$interface=$ligne['interface'];
		$int=new system_nic($interface);
		$InterfaceText="$interface &laquo;$int->NICNAME&raquo;";
		if($ligne["interface"]==null){continue;}
		
		
		if($ligne["enable"]==0){
			$icon="folder-network-42-grey.png";
			$color="#8a8a8a";
		}
		
		$disable=Field_checkbox("enabled$interface",1,$ligne["enable"],"SuricataEnableInterface('$interface')");
		$delete=imgsimple("delete-32.png","{delete}","SuricataDeleteInterface('$interface')");
		
	$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => array(
		"<center><img src='img/$icon'></center>",
		"<strong style='font-size:22px;color:$color'>$InterfaceText</strong>",
		"<center style='font-size:22px;color:$color'>{$ligne["threads"]}</center>",
		"<center>$disable</center>",
		"<center>$delete</center>")
		);		
		

	}	
	echo json_encode($data);	
	
}

function SuricataEnableInterface(){
	$interface=$_POST["SuricataEnableInterface"];
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT enable FROM suricata_interfaces WHERE interface='$interface'","artica_backup"));
	if($ligne["enable"]==0){$enable=1;}else{$enable=0;}
	$q->QUERY_SQL("UPDATE suricata_interfaces SET enable=$enable WHERE interface='$interface'","artica_backup");
}
function SuricataDeleteInterface(){
	$interface=$_POST["SuricataDeleteInterface"];
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM suricata_interfaces WHERE interface='$interface'","artica_backup");
}

function interfaces_add(){
	$eth=$_POST["eth"];
	$threads=$_POST["threads"];
	$enable=$_POST["enable"];
	
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM suricata_interfaces WHERE interface='$eth'","artica_backup");
	
	$q->QUERY_SQL("INSERT IGNORE INTO suricata_interfaces(interface,enable,threads) VALUES ('$eth','$enable','$threads')","artica_backup");
	
	if(!$q->ok){echo $q->mysql_error;}
		
}