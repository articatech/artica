<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.system.nics.inc');

	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "<H1>". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."</H1>";
		die();exit();
	}
	
	if(isset($_GET["nets"])){networks_list();exit;}
	if(isset($_GET["networks-js"])){network_js();exit;}
	if(isset($_GET["delete-item-js"])){network_js_delete();exit;}
	if(isset($_POST["network-add"])){network_add();exit;}
	if(isset($_POST["delete-network"])){network_delete();exit;}
	
table();	

function network_js_delete(){
	header("content-type: application/x-javascript");
	$t=$_GET["t"];
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	
	
	
	
	
	$q=new mysql_squid_builder();
	$sql="SELECT `pattern` FROM `webauth_rules_nets` WHERE ID='{$_GET["delete-item-js"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo "alert('$q->mysql_error');";return;}
	$pattern=$ligne["pattern"];
	$delete=$tpl->javascript_parse_text("{delete} {item}");
	$time=time();
	$html="
	var xDelete$time = function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#HOTSPOT_NETWORK').flexReload();
	$('#HOSTPOT_RULES').flexReload();
}

function Delete$time(){
	if( !confirm('$delete {$_GET["delete-item-js"]} - $pattern - ?') ){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-network','{$_GET["delete-item-js"]}');
	XHR.sendAndLoad('$page', 'POST',xDelete$time,true);
}
Delete$time();";
echo $html;
}

function network_js(){
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	$tpl=new templates();
	$recordid=$_GET["recordid"];
	$page=CurrentPageName();
	$explain=$tpl->javascript_parse_text("{acl_src_text}");
	$explain=str_replace("\n\n", "", $explain);
	$time=time();
	
$html="
var xAdd$time = function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#HOTSPOT_NETWORK').flexReload();
	$('#HOSTPOT_RULES').flexReload();
	
}

function Add$time(){
	var host=prompt('$explain');
	if(!host){return;}
	var XHR = new XHRConnection();
	XHR.appendData('network-add',host);
	XHR.appendData('ruleid','{$_GET["ID"]}');
	XHR.sendAndLoad('$page', 'POST',xAdd$time,true);
}
Add$time();";
	echo $html;
}

function network_add(){
	
	
	$tpc=new IP();
	if(!$tpc->isValidBlock($_POST["network-add"])){
		
		echo "{$_POST["network-add"]} invalid";
		return;
	}
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("INSERT IGNORE INTO webauth_rules_nets (pattern,ruleid) VALUES ('{$_POST["network-add"]}','{$_POST["ruleid"]}')");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("hotspot.php?remove-cache=yes");
}

function network_delete(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM webauth_rules_nets WHERE ID='{$_POST["delete-network"]}'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("hotspot.php?remove-cache=yes");
	
}


function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$dnsmasq_address_text=$tpl->_ENGINE_parse_body("{dnsmasq_address_text}");
	$hosts=$tpl->_ENGINE_parse_body("{domains}");
	$networks=$tpl->_ENGINE_parse_body("{networks}");
	$new_network=$tpl->_ENGINE_parse_body("{new_network}");
	$blacklist=$tpl->_ENGINE_parse_body("{blacklist}");
	$aliases=$tpl->_ENGINE_parse_body("{aliases}");
	$appy=$tpl->_ENGINE_parse_body("{apply}");
	$apply_params=$tpl->_ENGINE_parse_body("{apply}");
	$apply_paramsbt="{separator: true},{name: '$apply_params', bclass: 'apply', onpress : SquidBuildNow$t},";
	$buttons="
	buttons : [
		{name: '<strong style=font-size:18px>$new_network</strong>', bclass: 'add', onpress : AddHost$t},
	],";

	$html="
	<table class='HOTSPOT_NETWORK' style='display: none' id='HOTSPOT_NETWORK' style='width:100%'></table>
	<script>
$(document).ready(function(){
	$('#HOTSPOT_NETWORK').flexigrid({
	url: '$page?nets=yes&ID={$_GET["ID"]}',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:22px>$networks</span>', name : 'pattern', width : 778, sortable : false, align: 'left'},
	{display: '&nbsp;', name : 'delete', width : 80, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$networks', name : 'pattern'},

	],
	sortname: 'pattern',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:18px>$networks</span>',
	useRp: true,
	rp: 150,
	showTableToggleBtn: false,
	width: '99%',
	height: 300,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
});


	function SquidBuildNow$t(){
		Loadjs('squid.compile.php');
	}

function AddHost$t(){
	Loadjs('$page?networks-js=yes&ID={$_GET["ID"]}',true);

}
</script>
";

echo $tpl->_ENGINE_parse_body($html);
}

function networks_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$FORCE_FILTER="AND ruleid={$_GET["ID"]}";
	$t=$_GET["t"];
	$search='%';
	$table="webauth_rules_nets";
	
	if(!$q->TABLE_EXISTS($table)){
		$sql="CREATE TABLE IF NOT EXISTS `webauth_rules_nets` (
			`ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`ruleid` BIGINT UNSIGNED NOT NULL,
			`pattern` VARCHAR(128) NOT NULL,
			 PRIMARY KEY (`ID`),
			 KEY `ruleid` (`ruleid`)	
		)  ENGINE = MYISAM;";
		$q->QUERY_SQL($sql);
		if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}
	}


	$page=1;
	

	$total=0;


	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY `{$_POST["sortname"]}` {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	if(is_numeric($rp)){$limitSql = "LIMIT $pageStart, $rp";}

	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql);

	$no_rule=$tpl->_ENGINE_parse_body("{no data}");

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(!$q->ok){
		if(strpos($q->mysql_error, "doesn't exist")>0){$q->CheckTables();$results = $q->QUERY_SQL($sql);}
	}

	if(!$q->ok){	json_error_show($q->mysql_error."<br>$sql");}
	if(mysql_num_rows($results)==0){json_error_show("no data");}

	$fontsize="24";

	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$network_item=$ligne["pattern"];
		$ID=$ligne["ID"];
		$encoded=urlencode($network_item);
		$delete=imgsimple("delete-48.png",null,"Loadjs('$MyPage?delete-item-js=$ID&t=$t')");

		$editjs="<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('$MyPage?host-js=yes&ID={$ligne["ID"]}&t=$t',true);\"
		style='font-size:{$fontsize}px;font-weight:bold;color:$color;text-decoration:underline'>";
		$editjs=null;

		
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<span style='font-size:{$fontsize}px;font-weight:bold;color:$color'>$editjs$network_item</a><br><i style='font-size:12px'>&nbsp;$grouptype</i></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$delete</span>",)
		);
	}

	echo json_encode($data);
}