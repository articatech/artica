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

if(isset($_POST["filename"])){enable();exit;}
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
	$rulename=$tpl->_ENGINE_parse_body("{rulename}");
	$explain=$tpl->javascript_parse_text("{explain}");
	$title=$tpl->_ENGINE_parse_body("{rules}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$q=new mysql();
	
	
	if(!$q->TABLE_EXISTS("suricata_rules_packages", "artica_backup")){
		$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`suricata_rules_packages` (
		`rulefile` VARCHAR(128) NOT NULL PRIMARY KEY ,
		`category` VARCHAR(40) NOT NULL,
		`enabled` smallint(1) NOT NULL DEFAULT 0,
		INDEX ( `category`),
		INDEX ( `enabled`)
		)";
		
		$q->QUERY_SQL($sql,'artica_backup');
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	
	if($q->COUNT_ROWS("suricata_rules_packages", "artica_backup")==0){
		$sql="INSERT IGNORE INTO suricata_rules_packages (rulefile,enabled,category) VALUES 
				('botcc.rules',0,'DMZ'),('ciarmy.rules',0,'DMZ'),('compromised.rules','0','DMZ'),
				('drop.rules',1,'DMZ'),
				('dshield.rules',1,'DMZ'),('snort.rules',1,'ALL'),
				('emerging-activex.rules',1,'WEB'),
				('emerging-attack_response.rules',1,'ALL'),
				('emerging-chat.rules',0,'WEB'),
				('emerging-current_events.rules',0,'ALL'),
				('emerging-dns.rules',0,'DMZ'),
				('emerging-dos.rules',0,'DMZ'),
				('emerging-exploit.rules',0,'DMZ'),
				('emerging-ftp.rules',0,'DMZ'),
				('emerging-games.rules',0,'ALL'),
				('emerging-icmp_info.rules',0,'ALL'),
				('emerging-icmp.rules',0,'ALL'),
				('emerging-imap.rules',0,'DMZ'),
				('emerging-inappropriate.rules',0,'WEB'),
				('emerging-malware.rules',1,'WEB'),
				('emerging-mobile_malware.rules',0,'WEB'),
				('emerging-netbios.rules',0,'ALL'),
				('emerging-p2p.rules',0,'WEB'),
				('emerging-policy.rules',1,'WEB'),
				('emerging-pop3.rules',0,'DMZ'),
				('emerging-rpc.rules',0,'ALL'),
				('emerging-scada.rules',0,'ALL'),
				('emerging-scan.rules',1,'ALL'),
				('emerging-shellcode.rules',1,'ALL'),
				('emerging-smtp.rules',0,'DMZ'),
				('emerging-snmp.rules',0,'ALL'),
				('emerging-sql.rules',0,'ALL'),
				('emerging-telnet.rules',0,'ALL'),
				('emerging-tftp.rules',0,'ALL'),
				('emerging-trojan.rules',1,'ALL'),
				('emerging-user_agents.rules',0,'ALL'),
				('emerging-voip.rules',0,'ALL'),
				('emerging-web_client.rules',1,'HTTP'),
				('emerging-web_server.rules',0,'HTTP'),
				('emerging-web_specific_apps.rules',0,'HTTP'),
				('emerging-worm.rules',1,'ALL'),
				('tor.rules',0,'ALL'),
				('decoder-events.rules',0,'ALL'),
				('stream-events.rules',0,'ALL'),
				('http-events.rules',0,'HTTP'),
				('smtp-events.rules',0,'DMZ'),
				('dns-events.rules',0,'DMZ'),
				('tls-events.rules',0,'DMZ')";
		$q->QUERY_SQL($sql,'artica_backup');
	}
	$apply=$tpl->javascript_parse_text("{apply}");
	
	$buttons="
	buttons : [
	
	{name: '<strong style=font-size:18px>$apply</strong>', bclass: 'Apply', onpress : Apply$t},
	],";
	
	$html="
	
	
	<table class='TABLE_SURICATA_MAIN_RULES' style='display: none' id='TABLE_SURICATA_MAIN_RULES'
	style='width:100%'></table>
	<script>
	$(document).ready(function(){
	var md5H='';
	$('#TABLE_SURICATA_MAIN_RULES').flexigrid({
	url: '$page?list=yes',
	dataType: 'json',
	colModel : [
	
	{display: '<span style=font-size:22px>$rulename</span>', name : 'rulefile', width : 300, sortable : true, align: 'left'},
	{display: '<span style=font-size:22px>$category</span>', name : 'category', width : 156, sortable : true, align: 'center'},
	{display: '<span style=font-size:22px>$explain</span>', name : 'none', width : 833, sortable : false, align: 'left'},
	{display: '<span style=font-size:22px>$enabled</span>', name : 'enabled', width : 105, sortable : true, align: 'center'},

	],
	$buttons
searchitems : [
		{display: '$rulename', name : 'rulefile'},
		{display: '$category', name : 'category'},
		

	],	
	sortname: 'rulefile',
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
	});
	
	
function Add$t(){
	Loadjs('$page?add-interface-js=yes&t=$t');
}
var xSuricataRuleEnabled= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	$('#TABLE_SURICATA_MAIN_RULES').flexReload();
}
	
function SuricataRuleEnabled(filename){
	var XHR = new XHRConnection();
	XHR.appendData('filename',filename);
	XHR.sendAndLoad('$page', 'POST',xSuricataRuleEnabled);
}
function Apply$t(){
	Loadjs('suricata.progress.php');
}
</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	}
	
function enable(){
	
	$filename=$_POST["filename"];
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT enabled FROM suricata_rules_packages WHERE rulefile='$filename'","artica_backup"));
	$enabled=intval($ligne["enabled"]);
	if($enabled==0){$enabled=1;}else{$enabled=0;}
	$q->QUERY_SQL("UPDATE suricata_rules_packages SET `enabled`='$enabled' WHERE rulefile='$filename'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
}
	
function rules_list(){
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
	
	
			$sql="SELECT COUNT(*) AS TCOUNT FROM suricata_rules_packages WHERE 1 $searchstring";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
			$total = $ligne["TCOUNT"];
	
		}else{
			$sql="SELECT COUNT(*) AS tcount FROM suricata_rules_packages";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
			$total = $ligne["TCOUNT"];
		}
	
		if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
		$pageStart = ($page-1)*$rp;
		$limitSql = "LIMIT $pageStart, $rp";
	
	
	
		$sql="SELECT * FROM suricata_rules_packages WHERE 1 $searchstring $ORDER $limitSql";
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
			$icon="checkbox-on-24.png";

	
	
			if($ligne["enabled"]==0){
				$icon="checkbox-off-24.png";
				$color="#8a8a8a";
			}
			
			$explain=$tpl->_ENGINE_parse_body("{{$ligne["rulefile"]}}");
			$explain=wordwrap($explain,110,"<br>");
			
			$data['rows'][] = array(
					'id' => $ligne['ID'],
					'cell' => array(
							"<strong style='font-size:18px;color:$color'>{$ligne["rulefile"]}</strong>",
							"<center style='font-size:18px;color:$color'>{$ligne["category"]}</center>",
							"<span style='font-size:16px;color:$color'>$explain</span>",
							"<center>". imgsimple($icon,null,"SuricataRuleEnabled('{$ligne["rulefile"]}')")."</center>"
						)
			);
	
	
		}
		echo json_encode($data);
	
	}	