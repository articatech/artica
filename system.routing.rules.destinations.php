<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.groups.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.ActiveDirectory.inc');
include_once('ressources/class.external.ldap.inc');

$usersmenus=new usersMenus();
if(!$usersmenus->AsSystemAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();
}

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["icap-search"])){search();exit;}
if(isset($_GET["route-js"])){route_js();exit;}
if(isset($_GET["route-popup"])){route_popup();exit;}
if(isset($_GET["test-route-js"])){route_test_js();exit;}
if(isset($_GET["test-route-popup"])){route_test_popup();exit;}
if(isset($_POST["test-route"])){route_test_perform();exit;}
if(isset($_GET["route-move-js"])){route_move_js();exit;}
if(isset($_POST["move"])){route_move();exit;}
if(isset($_POST["ID"])){route_save();exit;}
if(isset($_GET["route-delete-js"])){route_delete_js();exit;}
if(isset($_POST["route-delete"])){route_delete();exit;}
table();

function route_move_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$ID=$_GET["ID"];
	
	
echo "
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>5){alert(results);return;}	
	$('#TABLE_IPDST_RULE_{$_GET["ruleid"]}').flexReload();
}
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('move', '$ID');
	XHR.appendData('ruleid', '{$_GET["ruleid"]}');
	XHR.appendData('dir', '{$_GET["dir"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}



Save$t();
";	
}

function route_delete(){
	$q=new mysql();
	$zmd5=$_POST["route-delete"];
	$database="artica_backup";
	$ligne=mysql_fetch_array($q->QUERY_SQL("DELETE FROM routing_rules_dest WHERE ID='$zmd5'","artica_backup"));
	if(!$q->ok){echo $q->mysql_error;}	
	
}

function  route_delete_js(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$ID=$_GET["ID"];
	$delete=$tpl->javascript_parse_text("{delete}");
	$t=time();
	$q=new mysql();
	$database="artica_backup";
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM routing_rules_dest WHERE ID='$ID'","artica_backup"));
	
	
	echo "
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>5){alert(results);return;}
	$('#TABLE_IPDST_RULE_{$_GET["ruleid"]}').flexReload();
}
	
	
function Save$t(){
	if(!confirm('$delete {$ligne["pattern"]} ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('route-delete', '$ID');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
	
Save$t();
";	
	
}

function route_move(){
	$ID=$_POST["move"];
	$dir=$_POST["dir"];
	$q=new mysql();
	$database="artica_backup";
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM routing_rules_dest WHERE ID='$ID'","artica_backup"));	
	
	$zOrder=$ligne["zOrder"];
	if($dir=="up"){
		$NewzOrder=$zOrder-1;
	}else{
		$NewzOrder=$zOrder+1;
	}
	if($NewzOrder<0){$NewzOrder=0;}
	
	$q->QUERY_SQL("UPDATE routing_rules_dest SET zOrder='$zOrder' WHERE zOrder='$NewzOrder' AND ID<>'$ID'",$database);
	$q->QUERY_SQL("UPDATE routing_rules_dest SET zOrder='$NewzOrder' WHERE ID='$ID'",$database);
	
	$results=$q->QUERY_SQL("SELECT * FROM routing_rules_dest WHERE ruleid={$_GET["ruleid"]} ORDER BY zOrder",$database);
	$c=0;
	while ($ligne = mysql_fetch_assoc($results)) {
		$c++;
		$q->QUERY_SQL("UPDATE routing_rules_dest SET zOrder='$c' WHERE ID='{$ligne["ID"]}'",$database);
		if(!$q->ok){echo $q->mysql_error;}
	}
	
	
}

function route_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{new_rule}");
	$ID=intval($_GET["ID"]);
	$ruleid=intval($_GET["ruleid"]);
	$t=$_GET["t"];
	if($ID>0){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `pattern` FROM routing_rules_dest WHERE ID='$ID'","artica_backup"));
		$title=$ligne["pattern"];
		if(!$q->ok){echo $q->mysql_error_html();}
	}
	
	
	$YahooWin="YahooWin2";
	echo "$YahooWin('800','$page?route-popup=yes&t=$t&ID=$ID&ruleid=$ruleid','$title',true);";
	
}





function route_popup(){
	$page=CurrentPageName();
	$q=new mysql();
	$tpl=new templates();
	$t=time();
	$btname="{add}";
	$ID=intval($_GET["ID"]);
	$ruleid=$_GET["ruleid"];
	$t=$_GET["t"];
	$title="{new_route}";
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `RouteName`,nic  FROM routing_rules WHERE ID='{$_GET["ruleid"]}'","artica_backup"));
	$RouteName=$ligne["RouteName"];
	$nic=$ligne["nic"];
	
	if($ID>0){
		$btname="{apply}";
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM routing_rules_dest WHERE ID='$ID'","artica_backup"));
		if(!$q->ok){echo $q->mysql_error_html();}
		$title=$ligne["pattern"];
	}

	
	
	$types[1]="{network_nic}";
	$types[2]="{host}";
	$types[3]="{default_gateway}";
	
	
	if(!is_numeric($ligne["zOrder"])){$ligne["zOrder"]=0;}
	if(!is_numeric($ligne["metric"])){$ligne["metric"]=0;}
	
	
	$html="
		<div style='font-size:24px;margin-bottom:20px'>$nic: $RouteName: {$ligne["pattern"]}</div>
		<div style='width:98%' class=form>
		<table style='width:100%'>

		<tr>
			<td class=legend style='font-size:24px'>{type}:</td>
			<td>". Field_array_Hash($types,"type-$t",$ligne["type"],"NextRuleCheck$t()",'',0,"font-size:24px")."</td>
		</tr>	
							
		<tr>
			<td class=legend style='font-size:24px'>{item} <span style='font-size:14px'>({address}/{network2})</span>:</td>
			<td>". Field_text("pattern-$t",$ligne["pattern"],"font-size:24px;width:95%")."</td>
		</tr>
				
		<tr>
			<td class=legend style='font-size:24px'>{gateway} <span style='font-size:14px'>({next_hope})</span>:</td>
			<td>". Field_ipv4("gateway-$t",$ligne["gateway"],"font-size:24px;width:95%")."</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:24px'>{order}:</td>
			<td>". Field_text("zOrder-$t",$ligne["zOrder"],"font-size:24px;width:90px")."</td>
		</tr>										
		<tr>
			<td class=legend style='font-size:24px'>{metric}:</td>
			<td>". Field_text("metric-$t",$ligne["metric"],"font-size:24px;width:90px")."</td>
		</tr>	
											
		<tr>
			<td colspan=2 align='right'><hr>". button($btname,"Save$t()",32)."</td>
		</tr>
		</table>
		</div>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	var ID=$ID;
	if(results.length>5){alert(results);return;}
	if(ID==0){YahooWin3Hide();}
	$('#TABLE_IPDST_RULE_{$_GET["ruleid"]}').flexReload();
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ID','$ID');
	XHR.appendData('zOrder',document.getElementById('zOrder-$t').value);
	XHR.appendData('type',document.getElementById('type-$t').value);
	XHR.appendData('pattern',document.getElementById('pattern-$t').value);
	XHR.appendData('gateway',document.getElementById('gateway-$t').value);
	XHR.appendData('metric',document.getElementById('metric-$t').value);
	XHR.appendData('ruleid','$ruleid');
	
	XHR.sendAndLoad('$page', 'POST',xSave$t);	
}

function NextRuleCheck$t(){
	var type=document.getElementById('type-$t').value;
	
	if(type==3){
		document.getElementById('pattern-$t').value='';
		document.getElementById('pattern-$t').disabled=true;
	}	
}
NextRuleCheck$t();
</script>
		
	";
	
echo $tpl->_ENGINE_parse_body($html);
	
}

function route_save(){
	include_once(dirname(__FILE__)."/class.html.tools.inc");
	$html=new htmltools_inc();
	$ID=$_POST["ID"];

	$q=new mysql();
	$sql="CREATE TABLE IF NOT EXISTS `routing_rules_dest` (
				  `ID` INT(11) NOT NULL AUTO_INCREMENT,
				  `ruleid` INT(11) NOT NULL,
				  `type` smallint(1) NOT NULL,
				  `gateway` varchar(90) NOT NULL,
				  `pattern` varchar(255) NOT NULL,
				  `status` smallint(1) NOT NULL,
				  `nic` varchar(20) NOT NULL,
				 `zOrder` INT(10) NOT NULL,
				  PRIMARY KEY (`ID`),
				  KEY `type` (`type`,`status`),
				  KEY `zOrder` (`zOrder`),
				  KEY `ruleid` (`ruleid`),
				  KEY `nic` (`nic`)
				) ENGINE=MYISAM;";
	$q->QUERY_SQL($sql,'artica_backup');
	
	
	
	if($ID==0){
		$md5=md5("{$_POST["nic"]}{$_POST["pattern"]}");
		$sql="INSERT INTO routing_rules_dest (`type`,`gateway`,`pattern`,`ruleid`,`nic`,`metric`,`zOrder`)
		VALUES('{$_POST["type"]}','{$_POST["gateway"]}','{$_POST["pattern"]}','{$_POST["ruleid"]}','{$_POST["nic"]}','{$_POST["metric"]}','{$_POST["zOrder"]}');";
	}else{
		$sql="UPDATE routing_rules_dest SET
				`metric`='{$_POST["metric"]}',
				`zOrder`='{$_POST["zOrder"]}',
				`type`='{$_POST["type"]}' WHERE `ID`='$ID'";
		
	}
	

	
	

	
	if(!$q->FIELD_EXISTS("routing_rules_dest", "metric", "artica_backup")){
		$q->QUERY_SQL("ALTER TABLE `routing_rules_dest` ADD `metric` INT(10) NOT NULL, ADD INDEX (`metric`)","artica_backup");
	}
	
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
	
}


function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$explain_section=$tpl->_ENGINE_parse_body("{routes_center_explain}");
	$t=time();
	$type=$tpl->_ENGINE_parse_body("{type}");
	$gateway=$tpl->_ENGINE_parse_body("{next_hope}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$nic=$tpl->javascript_parse_text("{nic}");
	$order=$tpl->javascript_parse_text("{order}");
	$title=$tpl->javascript_parse_text("{routes}");
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$test_a_route=$tpl->_ENGINE_parse_body("{test_a_route}");
	$apply=$tpl->_ENGINE_parse_body("{apply}");
	
	// 	$sql="INSERT INTO routing_rules_dest (`type`,`gateway`,`pattern`,`zmd5`,`nic`)
	// VALUES('$type','$gw','$pattern/$cdir','$md5','$route_nic');";
	
	$buttons="
	buttons : [
	{name: '<strong style=font-size:16px>$new_rule</strong>', bclass: 'add', onpress : Add$t},
	],";
	
	$html="
			
	<table class='TABLE_IPDST_RULE_{$_GET["ruleid"]}' style='display: none' id='TABLE_IPDST_RULE_{$_GET["ruleid"]}' style='width:100%'></table>
	
	<script>
	var rowid=0;
	$(document).ready(function(){
	$('#TABLE_IPDST_RULE_{$_GET["ruleid"]}').flexigrid({
	url: '$page?icap-search=yes&t=$t&ruleid={$_GET["ruleid"]}',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:16px>$order</span>', name : 'zOrder', width : 80, sortable : true, align: 'center'},
	{display: '<span style=font-size:16px>$items</span>', name : 'pattern', width : 163, sortable : true, align: 'right'},
	{display: '<span style=font-size:16px>$type</span>', name : 'type', width : 134, sortable : true, align: 'right'},
	{display: '<span style=font-size:16px>$gateway</span>', name : 'gateway', width :134, sortable : false, align: 'right'},
	{display: '&nbsp;', name : 'up', width : 70, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'down', width : 70, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'del', width : 70, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$items', name : 'pattern'},
	{display: '$gateway', name : 'gateway'},
	],
	sortname: 'zOrder',
	sortorder: 'asc',
	usepager: true,
	title: '',
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
		Loadjs('$page?route-js=yes&zmd5=&t=$t&ruleid={$_GET["ruleid"]}');
	}
	function TestRoute$t(){
		Loadjs('$page?test-route-js=yes');
	}	
	
	function Apply$t(){
		Loadjs('network.restart.php?t=$t')
		
	}
	
	var x_DansGuardianDelGroup= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#row'+rowid).remove();
	}
	
	function DansGuardianDelGroup(ID){
	if(confirm('$do_you_want_to_delete_this_group ?')){
	rowid=ID;
	var XHR = new XHRConnection();
	XHR.appendData('Delete-Group', ID);
	XHR.sendAndLoad('$page', 'POST',x_DansGuardianDelGroup);
	}
	}
	
	</script>
	";
	
	echo $html;
	
}

function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$database="artica_backup";
	
	$t=$_GET["t"];
	$search='%';
	$table="routing_rules_dest";
	$page=1;
	$fontsize=16;
	$FORCE_FILTER="AND ruleid='{$_GET["ruleid"]}'";
	$total=0;
	
	if(!$q->FIELD_EXISTS("routing_rules_dest", "zOrder", "artica_backup")){
		$q->QUERY_SQL("ALTER TABLE `routing_rules_dest` ADD `zOrder` INT(10) NOT NULL, ADD INDEX (`zOrder`)","artica_backup");
		if(!$q->ok){json_error_show($q->mysql_error,1);}
	}
	
	
	if(!$q->FIELD_EXISTS("routing_rules_dest", "metric", "artica_backup")){
		$q->QUERY_SQL("ALTER TABLE `routing_rules_dest` ADD `metric` INT(10) NOT NULL, ADD INDEX (`metric`)","artica_backup");
	}
		

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	
	
	if($searchstring<>null){
		$search=$_POST["query"];
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
	$pageStart = ($page-1)*$rp;
	if(!is_numeric($rp)){$rp=50;}
	$limitSql = "LIMIT $pageStart, $rp";
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	

	$types[1]=$tpl->_ENGINE_parse_body("{network_nic}");
	$types[2]=$tpl->_ENGINE_parse_body("{host}");
	$types[3]=$tpl->_ENGINE_parse_body("{default_gateway}");
	
	$style="style='font-size:{$fontsize}px;color:black;'";
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM routing_rules WHERE ID='{$_GET["ruleid"]}'","artica_backup"));
	$nic=new system_nic($ligne["nic"]);
	if($nic->GATEWAY<>null){
		if($nic->GATEWAY<>"0.0.0.0"){
			$data['total'] = $total+1;
			$data['rows'][] = array(
					'id' => $ligne['ID'],
					'cell' => array(
							"<span $style>0</a></span>",
							"<span $style>0.0.0.0/0</a></span>",
							"<span $style>". $types[3]."</a></span>",
							"<span $style>$nic->GATEWAY</span>",
							"<center $style>-</center>",
							"<center $style>-</center>",
							"<center $style>-</center>",
					)
			);
		}
	}
	
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,$database);
	
	
	

	
	if(!$q->ok){json_error_show($q->mysql_error,1);}
	
	
	
	
	

	
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		//if($ligne["enabled"]==0){$color="#8a8a8a";}
		$style="style='font-size:{$fontsize}px;color:$color;'";
		$delete=imgsimple("delete-24.png",null,"Loadjs('$MyPage?route-delete-js=yes&ID={$ligne["ID"]}&ruleid={$_GET["ruleid"]}&t=$t');");
		
		$js="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?route-js=yes&ID={$ligne["ID5"]}&t=$t');\"
		style='font-size:{$fontsize}px;color:$color;text-decoration:underline'>";
		
		$down=imgsimple("arrow-down-18.png",null,"Loadjs('$MyPage?route-move-js=yes&ID={$ligne["ID"]}&ruleid={$_GET["ruleid"]}&t=$t&dir=down');");
		$up=imgsimple("arrow-up-18.png",null,"Loadjs('$MyPage?route-move-js=yes&ID={$ligne["ID"]}&ruleid={$_GET["ruleid"]}&t=$t&dir=up');");
		
		
		if($ligne["gateway"]==null){$ligne["gateway"]="-";}
		if($ligne["type"]==3){$ligne["pattern"]="0.0.0.0/0";}
		
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<span $style>$js{$ligne["zOrder"]}</a></span>",
						"<span $style>{$js}{$ligne["pattern"]}</a></span>",
						"<span $style>$js". $types[$ligne["type"]]."</a></span>",
						"<span $style>{$ligne["gateway"]}</span>",
						"<center $style>$up</center>",
						"<center $style>$down</center>",
						"<center $style>$delete</center>",
				)
		);

	}
	
	
		echo json_encode($data);
	
}

function routes_default(){
	$tpl=new templates();
	$fontsize=26;
	$color="black";
	$delete="&nbsp;";
	$js=null;
	$style="style='font-size:{$fontsize}px;color:$color;'";
	$sql="SELECT * FROM `nics` WHERE enabled=1 ORDER BY Interface,metric";
	$default_route=utf8_decode($tpl->javascript_parse_text("{default_route}"));
	$network2=$tpl->_ENGINE_parse_body("{network2}");
	
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	$c=0;
	
	$data = array();
	$data['page'] = 1;
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$eth=trim($ligne["Interface"]);
		$ID=md5(serialize($ligne));
		$eth=str_replace("\r\n", "", $eth);
		$eth=str_replace("\r", "", $eth);
		$eth=str_replace("\n", "", $eth);
		$GATEWAY=$ligne["GATEWAY"];
		$NETMASK=$ligne["NETMASK"];
		$CDIR=$ligne["NETWORK"];
		if($ligne["GATEWAY"]==null){continue;}
		if($ligne["GATEWAY"]=="0.0.0.0"){continue;}
		$c++;
		
		if($GLOBALS["VERBOSE"]){echo " $eth $default_route $network2 $GATEWAY<br>\n";}
			
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<span $style>$js-</a></span>",
						"<span $style>$js{$eth}</a></span>",
						"<span $style>{$js}0.0.0.0/0 ( $default_route )</span>",
						"<span $style>$js{$network2}</span>",
						"<span $style>$js{$GATEWAY}</span>",
						"<span $style>$js&nbsp;</span>",
						"<span $style>$js&nbsp;</span>",
						"<span $style>&nbsp;</span>",
				)
		);		
		
		
	}
	

	$data['total'] = $c;
	
	return array($data['total'],$data);
	
	
}



