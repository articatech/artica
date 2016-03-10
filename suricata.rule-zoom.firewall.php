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

if(isset($_POST["filename"])){enable();exit;}
if(isset($_POST["delete"])){delete();exit;}
if(isset($_GET["list"])){rules_list();exit;}
table();
function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$dnsmasq_address_text=$tpl->_ENGINE_parse_body("{dnsmasq_address_text}");
	$hosts=$tpl->_ENGINE_parse_body("{hosts}");
	$addr=$tpl->_ENGINE_parse_body("{addr}");
	$new_interface=$tpl->_ENGINE_parse_body("{new_interface}");
	$rulename=$tpl->_ENGINE_parse_body("{signature}");
	$explain=$tpl->javascript_parse_text("{rule}");
	$title=$tpl->_ENGINE_parse_body("{signatures}: {firewall}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$firewall=$tpl->_ENGINE_parse_body("{firewall}");
	$zdate=$tpl->_ENGINE_parse_body("date");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$q=new postgres_sql();
	$q->suricata_tables();
	$src_ip=$tpl->javascript_parse_text("src_ip");
	
	$apply=$tpl->javascript_parse_text("{apply}");
	
	$buttons="
	buttons : [
	
	{name: '<strong style=font-size:18px>$apply</strong>', bclass: 'Apply', onpress : Apply$t},
	],";
	$buttons=null;
	$html="
	
	
	<table class='TABLE_SURICATA_MAIN_FIREWALL_ZOOM' style='display: none' id='TABLE_SURICATA_MAIN_FIREWALL_ZOOM'
	style='width:100%'></table>
	<script>
	$(document).ready(function(){
	var md5H='';
	$('#TABLE_SURICATA_MAIN_FIREWALL_ZOOM').flexigrid({
	url: '$page?list=yes&sig={$_GET["sig"]}',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:22px>$zdate</span>', name : 'zdate', width : 200, sortable : true, align: 'left'},
	{display: '<span style=font-size:22px>$explain</span>', name : 'src_ip', width : 491, sortable : true, align: 'left'},
	{display: '<span style=font-size:22px>$delete</span>', name : 'none', width : 90, sortable : false, align: 'center'},
	

	],
	$buttons
searchitems : [
		{display: '$rulename', name : 'signature'},
		{display: '$src_ip', name : 'src_ip'},
		

	],	
	sortname: 'zdate',
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
var xSuricataFwDelete= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	$('#TABLE_SURICATA_MAIN_FIREWALL_ZOOM').flexReload();
}
	
function SuricataSignatureEnabled(filename){
	var XHR = new XHRConnection();
	XHR.appendData('filename',filename);
	XHR.sendAndLoad('$page', 'POST',xSuricataSignatureEnabled);
}

function SuricataFwDelete(sig){
	var XHR = new XHRConnection();
	XHR.appendData('delete',sig);
	XHR.sendAndLoad('$page', 'POST',xSuricataFwDelete);
}

function Apply$t(){
	Loadjs('suricata.progress.php');
}
</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	}
	
function enable(){
	
	$sig=$_POST["filename"];
	$q=new postgres_sql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT enabled FROM suricata_sig WHERE signature='$filename'","artica_backup"));
	$enabled=intval($ligne["enabled"]);
	if($enabled==0){$enabled=1;}else{$enabled=0;}
	$q->QUERY_SQL("UPDATE suricata_sig SET enabled='$enabled' WHERE signature='$filename'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	if($enabled==0){
		$q->QUERY_SQL("DELETE FROM suricata_events WHERE signature='{$_POST["sig"]}'");
		if(!$q->ok){echo $q->mysql_error;return;}
		$sock->getFrameWork("suricata.php?disable-sid=yes&sig={$_POST["sig"]}");
	}else{
		$sock->getFrameWork("suricata.php?enable-sid=yes&sig={$_POST["sig"]}");
	}
	
	
}

function delete(){
	$sig=$_POST["delete"];
	$q=new postgres_sql();
	$sock=new sockets();
	$q->QUERY_SQL("DELETE FROM  suricata_firewall WHERE id='$sig'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock->getFrameWork("suricata.php?firewall-build=yes");
}
	
function rules_list(){
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
	
	
			$sql="SELECT COUNT(*) AS tcount FROM suricata_firewall WHERE signature='{$_GET["sig"]}' AND $searchstring";
			$ligne=pg_fetch_assoc($q->QUERY_SQL($sql,"artica_backup"));
			$total = $ligne["tcount"];
	
		}else{
			$sql="SELECT COUNT(*) AS tcount FROM suricata_firewall WHERE signature='{$_GET["sig"]}'";
			$ligne=pg_fetch_assoc($q->QUERY_SQL($sql,"artica_backup"));
			$total = $ligne["tcount"];
		}
	
		if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
		$pageStart = ($page-1)*$rp;
		$limitSql = "LIMIT $rp OFFSET $pageStart";
	
	
	
		$sql="SELECT * FROM suricata_firewall WHERE signature='{$_GET["sig"]}' AND $searchstring $ORDER $limitSql";
		writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	
		$results = $q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){json_error_show($q->mysql_error,1);}
	
	
	
		$data = array();
		$data['page'] = $page;
		$data['total'] = $total;
		$data['rows'] = array();
		if(pg_num_rows($results)==0){json_error_show("No data",1);}
	
	
	
		while ($ligne = pg_fetch_assoc($results)) {
			$color="black";
			$signature=intval($ligne["signature"]);
			$explain="{block} {from} {$ligne["src_ip"]} {$ligne["proto"]}";
			if($ligne["dst_port"]>0){$explain=$explain." {port} {$ligne["dst_port"]}";}
			
			$explain=$tpl->javascript_parse_text($explain);
			
			$signature_js="<a href=\"javascript:blur();\"
			OnClick=\"javascript:Loadjs('suricata.rule-zoom.php?sig={$ligne["signature"]}');\"
			style='font-size:18px;color:$color;text-decoration:underline'>";
			

			
			
			$data['rows'][] = array(
					'id' => $ligne['ID'],
					'cell' => array(
							"<strong style='font-size:18px;color:$color'>{$ligne["zdate"]}</strong>",
							"<span style='font-size:18px;color:$color'>$explain</span>",
							"<center>". imgsimple("delete-24.png",null,"SuricataFwDelete('{$ligne["id"]}')")."</center>",
							
						)
			);
	
	
		}
		echo json_encode($data);
	
	}	