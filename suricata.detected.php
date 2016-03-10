<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.dnsmasq.inc');
	include_once('ressources/class.main_cf.inc');
	include_once('ressources/class.system.network.inc');
	include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
	
	if(posix_getuid()<>0){
		$user=new usersMenus();
		if($user->AsDansGuardianAdministrator==false){
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
		$zDate=$tpl->javascript_parse_text("{zDate}");
		$src_ip=$tpl->javascript_parse_text("{src_ip}");
		$dst_ip=$tpl->javascript_parse_text("{dst_ip}");
		$proto=$tpl->javascript_parse_text("{proto}");
		$severity=$tpl->javascript_parse_text("{severity}");
		$rule=$tpl->javascript_parse_text("{rule}");
		$title=$tpl->_ENGINE_parse_body("{IDS} {events}");
		$events=$tpl->javascript_parse_text("{events}");
		$signature=$tpl->javascript_parse_text("{signature} (numeric)");
		$q=new postgres_sql();
		
		
		$apply=$tpl->javascript_parse_text("{apply}");
		
		$buttons="
		buttons : [
		{name: '<strong style=font-size:18px>$new_interface</strong>', bclass: 'add', onpress : Add$t},
		{name: '<strong style=font-size:18px>$apply</strong>', bclass: 'Apply', onpress : Apply$t},
		],";
	
		
		$buttons=null;
		
		$html="
	
		
<table class='TABLE_SURICATA_EVENTS' style='display: none' id='TABLE_SURICATA_EVENTS' 
style='width:100%'></table>
<script>
$(document).ready(function(){
	var md5H='';
	$('#TABLE_SURICATA_EVENTS').flexigrid({
		url: '$page?list=yes',
		dataType: 'json',
		colModel : [
		{display: '<span style=font-size:18px>$severity</span>', name : 'severity', width : 80, sortable : true, align: 'center'},
		{display: '<span style=font-size:18px>$zDate</span>', name : 'zDate', width : 178, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$src_ip</span>', name : 'src_ip', width : 133, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$dst_ip</span>', name : 'dst_ip', width : 184, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$events</span>', name : 'xcount', width : 80, sortable : true, align: 'center'},
		{display: '<span style=font-size:18px>$rule</span>', name : 'signature', width : 752, sortable : true, align: 'left'},

		],
		$buttons
		searchitems : [
			{display: '$src_ip', name : 'src_ip'},
			{display: '$dst_ip', name : 'dst_ip'},
			{display: '$signature', name : 'signature'},
			
			],
		sortname: 'zDate',
		sortorder: 'desc',
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
	});
	
	
function Add$t(){
	Loadjs('$page?add-interface-js=yes&t=$t');
}
var xSuricataEnableInterface= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	$('#TABLE_SURICATA_EVENTS').flexReload();
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
	$('#TABLE_SURICATA_EVENTS').flexReload();
}
function Apply$t(){
	Loadjs('suricata.progress.php');
}

</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}



function interfaces_list(){
	$search='%';
	$page=1;	
	
	$q=new postgres_sql();
	$tpl=new templates();
	
	$searchstring=string_to_flexPostGresquery();
	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	


	if($searchstring<>null){
		$sql="SELECT COUNT(*) AS tcount FROM suricata_events WHERE $searchstring";
		$ligne=pg_fetch_assoc($q->QUERY_SQL($sql));
		$total = $ligne["tcount"];
		
	}else{
		$sql="SELECT COUNT(*) AS tcount FROM suricata_events";
		$ligne=pg_fetch_assoc($q->QUERY_SQL($sql));
		$total = $ligne["tcount"];
	}
	$rp=50;
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $rp OFFSET $pageStart";
	
	
	
	$sql="SELECT * FROM suricata_events WHERE $searchstring $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show("$q->mysql_error - $sql",1);}
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] =$total;
	$data['rows'] = array();
	if(pg_num_rows($results)==0){json_error_show("No data - $sql",1);}
	
	$severity_array[1]="24-red.png";
	$severity_array[2]="warning24.png";
	$severity_array[3]="info-24.png";
	$severity_array[4]="ok24-none.png";
	$severity_array[5]="ok24-grey.png";
	
	while($ligne=@pg_fetch_assoc($results)){
		$color="black";
		$icon=$severity_array[$ligne["severity"]];
		$src_ip=$ligne["src_ip"];
		$zDate=$ligne["zdate"];
		$dst_ip=$ligne["dst_ip"];
		$dst_port=$ligne["dst_port"];
		$proto=$ligne["proto"];
		$signature=$ligne["signature"];
		$xcount=$ligne["xcount"];
		$ligne2=pg_fetch_assoc($q->QUERY_SQL("SELECT description FROM suricata_sig WHERE signature='$signature'"));
		if(!$q->ok){$ligne2["description"]=$q->mysql_error;}
		
		$description=$ligne2["description"];
		$signature_js="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('suricata.rule-zoom.php?sig=$signature');\"
		style='font-size:16px;color:$color;text-decoration:underline'>";
		
	$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => array(
		"<center><img src='img/$icon'></center>",
		"<span style='font-size:16px;color:$color'>$zDate</span>",
		"<span style='font-size:16px;color:$color'>$src_ip</span>",
		"<span style='font-size:16px;color:$color'>$proto $dst_ip:$dst_port</span>",
		"<center style='font-size:16px;color:$color'>$xcount</center>",
		"<span style='font-size:16px;color:$color'>[$signature_js$signature</a>]: $description</span>",
		
		)
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
	$q->QUERY_SQL("DELETE FROM WHERE interface='$interface'","artica_backup");
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