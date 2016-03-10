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
if(isset($_GET["route-tabs"])){route_tabs();exit;}
if(isset($_GET["test-route-js"])){route_test_js();exit;}
if(isset($_GET["test-route-popup"])){route_test_popup();exit;}
if(isset($_POST["test-route"])){route_test_perform();exit;}
if(isset($_GET["route-move-js"])){route_move_js();exit;}
if(isset($_POST["move"])){route_move();exit;}
if(isset($_POST["RouteName"])){route_save();exit;}
if(isset($_GET["route-delete-js"])){route_delete_js();exit;}
if(isset($_POST["route-delete"])){route_delete();exit;}
if(isset($_GET["route-dump"])){route_dump();exit;}

table();

function route_move_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$zmd5=$_GET["zmd5"];
	
	
echo "
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>5){alert(results);return;}	
	$('#MAIN_TABLE_ROUTING_RULES').flexReload();
}
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('move', '{$_GET["zmd5"]}');
	XHR.appendData('dir', '{$_GET["dir"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}



Save$t();
";	
}

function route_delete(){
	$q=new mysql();
	$ID=$_POST["route-delete"];
	$database="artica_backup";
	$q->QUERY_SQL("DELETE FROM routing_rules WHERE ID='$ID'","artica_backup");
	$q->QUERY_SQL("DELETE FROM routing_rules_dest WHERE ruleid='$ID'","artica_backup");
	$q->QUERY_SQL("DELETE FROM routing_rules_src WHERE ruleid='$ID'","artica_backup");
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
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM routing_rules WHERE ID='$ID'","artica_backup"));
	
	
	echo "
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>5){alert(results);return;}
	$('#MAIN_TABLE_ROUTING_RULES').flexReload();
}
	
	
function Save$t(){
	if(!confirm('$delete {$ligne["RouteName"]} ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('route-delete', '$ID');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
	
Save$t();
";	
	
}

function route_move(){
	$zmd5=$_POST["move"];
	$dir=$_POST["dir"];
	$q=new mysql();
	$database="artica_backup";
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM routing_rules WHERE zmd5='$zmd5'","artica_backup"));	
	
	$zOrder=$ligne["zOrder"];
	if($dir=="up"){
		$NewzOrder=$zOrder-1;
	}else{
		$NewzOrder=$zOrder+1;
	}
	
	$q->QUERY_SQL("UPDATE routing_rules SET zOrder='$zOrder' WHERE zOrder='$NewzOrder' AND zmd5<>'$zmd5'",$database);
	$q->QUERY_SQL("UPDATE routing_rules SET zOrder='$NewzOrder' WHERE zmd5='$zmd5'",$database);
	
	$results=$q->QUERY_SQL("SELECT * FROM routing_rules ORDER BY zOrder",$database);
	$c=0;
	while ($ligne = mysql_fetch_assoc($results)) {
		$c++;
		$q->QUERY_SQL("UPDATE routing_rules SET zOrder='$c' WHERE zmd5='{$ligne["zmd5"]}'",$database);
		if(!$q->ok){echo $q->mysql_error;}
	}
	
	
}

function route_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{new_route}");
	$ID=intval($_GET["ID"]);
	$t=$_GET["t"];
	$token="route-popup";
	if($ID>0){
		$token="route-tabs";
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `RouteName` FROM routing_rules WHERE ID='$ID'","artica_backup"));
		$title=$ligne["RouteName"];
		
	}
	
	
	$YahooWin="YahooWin";
	echo "$YahooWin('890','$page?$token=yes&t=$t&ID=$ID&lock={$_GET["lock"]}','$title');";
	
}

function route_test_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{test_a_route}");
	$zmd5=$_GET["zmd5"];
	$YahooWin="YahooWin";
	echo "$YahooWin('700','$page?test-route-popup=yes','$title');";	
	
}

function route_test_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$btname="{test}";
	
	
	
	$html="
	<div style='font-size:22px;margin-bottom:20px'>{test_a_route}</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{item}:</td>
		<td>". Field_text("pattern-$t",$_SESSION["TEST_A_ROUTE"],"font-size:18px;width:95%",null,null,null,false,"SaveCk$t(event)")."</td>
	</tr>
	<tr>
	<td colspan=2><textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:99%;height:320px;border:5px solid #8E8E8E;overflow:auto;font-size:14px !important'
	id='textarea$t'></textarea></td>
	<tr>
		<td colspan=2 align='right'><hr>". button($btname,"Save$t()",22)."</td>
	</tr>
	</table>
	</div>
	<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	document.getElementById('textarea$t').value=results;
}

function SaveCk$t(e){
	if(!checkEnter(e)){return;}
	Save$t();
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('test-route',encodeURIComponent(document.getElementById('pattern-$t').value));
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
	
		";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function route_test_perform(){
	$_POST["test-route"]=url_decode_special_tool($_POST["test-route"]);
	$_SESSION["TEST_A_ROUTE"]=$_POST["test-route"];
	$item=urlencode($_POST["test-route"]);
	echo "$item\n******************\n";
	$sock=new sockets();
	echo base64_decode($sock->getFrameWork("system.php?test-a-route=$item"));
}


function route_tabs(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$fontsize=18;
	
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `RouteName` FROM routing_rules WHERE ID='{$_GET["ID"]}'","artica_backup"));
	$title=$ligne["RouteName"];
	
	$array["route-popup"]="{routing_table} $title";
	$array["route-src"]="{sources}";
	$array["route-dst"]="{destinations}";
	$array["route-dump"]="{configuration}";
	
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
	
		if($num=="route-src"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"system.routing.rules.sources.php?ruleid={$_GET["ID"]}\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
	
		}
		
		if($num=="route-dst"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"system.routing.rules.destinations.php?ruleid={$_GET["ID"]}\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
		
		}
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t&ID={$_GET["ID"]}&lock={$_GET["lock"]}\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
	}
	
	
	
	$html=build_artica_tabs($html,"main_iproute_{$_GET["ID"]}")."<script>LeftDesign('routes-opac20.png');</script>";
	
	echo $html;	
	
	
}

function route_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$btname="{add}";
	$LOCK=0;
	
	$title="{new_routing_table}";
	if($_GET["ID"]>0){
		$btname="{apply}";
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM routing_rules WHERE ID='{$_GET["ID"]}'","artica_backup"));
		if(!$q->ok){echo $q->mysql_error_html();}
		$title=$ligne["RouteName"];
	}else{
		$ligne["enabled"]=1;
	}
	
	if($_GET["lock"]=="yes"){
		$LOCK=1;
	}
	
	$net=new networking();
	
	$ETHs=$net->Local_interfaces();
	unset($ETHs["lo"]);
	while (list ($int, $none) = each ($ETHs) ){
		$nic=new system_nic($int);
		$ETHZ[$int]="{$int} - $nic->NICNAME - $nic->IPADDR";
		
	}
	
	
	$types[1]="{network_nic}";
	$types[2]="{host}";
	
	if(!is_numeric($ligne["zOrder"])){$ligne["zOrder"]=0;}
	if(!is_numeric($ligne["metric"])){$ligne["metric"]=0;}
	
	
	$html="
		<div style='font-size:24px;margin-bottom:20px'>{$ligne["pattern"]}</div>
		<div style='width:98%' class=form>
		<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:24px'>{nic}:</td>
			<td>". Field_array_Hash($ETHZ,"nic-$t",$ligne["nic"],"style:font-size:24px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:24px'>{enabled}:</td>
			<td>". Field_checkbox_design("enabled-$t",1,$ligne["enabled"],"check$t()")."</td>
		</tr>								
		<tr>
			<td class=legend style='font-size:24px'>{route_name}</span>:</td>
			<td>". Field_text("RouteName-$t",$ligne["RouteName"],"font-size:24px;width:95%")."</td>
		</tr>		
		<tr>
			<td colspan=2 align='right'><hr>". button($btname,"Save$t()",32)."</td>
		</tr>
		</table>
		</div>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	var ID='{$_GET["ID"]}';
	if(results.length>5){alert(results);return;}
	if(ID==0){YahooWinHide();}
	if(ID>0){RefreshTab('main_iproute_'+ID);}
	$('#MAIN_TABLE_ROUTING_RULES').flexReload();
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ID','{$_GET["ID"]}');
	var enabled=0;
	if(document.getElementById('enabled-$t').checked){enabled=1;}
	XHR.appendData('RouteName',document.getElementById('RouteName-$t').value);
	XHR.appendData('nic',document.getElementById('nic-$t').value);
	XHR.appendData('enabled',enabled);
	XHR.sendAndLoad('$page', 'POST',xSave$t);	
}

function check$t(){
	var ID='{$_GET["ID"]}';
	var LOCK=$LOCK;
	
	if(LOCK==1){
		document.getElementById('RouteName-$t').disabled=true;
		document.getElementById('nic-$t').disabled=true;
		return;
	}
	
	if(ID.length==0){return;}
	var enabled=0;
	if(document.getElementById('enabled-$t').checked){enabled=1;}
	document.getElementById('RouteName-$t').disabled=true;
	document.getElementById('nic-$t').disabled=true;
	if(enabled==0){return;}
	document.getElementById('RouteName-$t').disabled=false;
	document.getElementById('nic-$t').disabled=false;	
}



check$t();
</script>
		
	";
	
echo $tpl->_ENGINE_parse_body($html);
	
}

function route_save(){
	include_once(dirname(__FILE__)."/class.html.tools.inc");
	$html=new htmltools_inc();
	$ID=$_POST["ID"];
	$RouteName=trim(strtolower($html->replace_accents($_POST["RouteName"])));
	$RouteName=str_replace(" ", "", $RouteName);
	$RouteName=str_replace("/", "", $RouteName);
	$RouteName=str_replace("#", "", $RouteName);
	$RouteName=str_replace("$", "", $RouteName);
	$ADD=false;
	if($ID==0){
		$sql="INSERT INTO routing_rules (`RouteName`,`nic`,`enabled`)
		VALUES('{$RouteName}','{$_POST["nic"]}',1);";
		$eth=$_POST["nic"];
		$ADD=true;
	}else{
		$sql="UPDATE routing_rules SET
				`RouteName`='$RouteName',
				`nic`='{$_POST["nic"]}',
				`enabled`='{$_POST["enabled"]}'
				 WHERE `ID`='$ID'";
		
	}
	

	
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	
	if($ADD){
		$ruleid=$q->last_id;
		$nicClass=new system_nic($eth);
		$metric=$ruleid+1;
		
		$tr=explode(".",$nicClass->IPADDR);
		unset($tr[3]);
		$ipf=@implode(".", $tr).".0";
		$sql="INSERT INTO routing_rules_dest (`type`,`gateway`,`pattern`,`ruleid`,`nic`,`metric`,`zOrder`)
		VALUES('1','','$ipf/$nicClass->NETMASK','$ruleid','$eth','$metric','1');";
		$q->QUERY_SQL($sql,"artica_backup");
		
	}
	
	
}


function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$explain_section=$tpl->_ENGINE_parse_body("{routes_center_explain}");
	$t=time();
	$explain=$tpl->javascript_parse_text("{explain}");
	$type=$tpl->_ENGINE_parse_body("{type}");
	$gateway=$tpl->_ENGINE_parse_body("{gateway}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$nic=$tpl->javascript_parse_text("{nic}");
	$table=$tpl->javascript_parse_text("{table}");
	$title=$tpl->javascript_parse_text("{routing_rules_explain}");
	$new_route=$tpl->_ENGINE_parse_body("{new_routing_table}");
	$test_a_route=$tpl->_ENGINE_parse_body("{test_a_route}");
	$apply=$tpl->_ENGINE_parse_body("{apply}");
	
	$q=new mysql();
	$sql="CREATE TABLE IF NOT EXISTS `routing_rules` (
				  `ID` INT(11) NOT NULL AUTO_INCREMENT,
				  `RouteName` varchar(90) NOT NULL,
				  `enabled` smallint(1) NOT NULL,
				  `nic` varchar(20) NOT NULL,
				  PRIMARY KEY (`ID`),
				  UNIQUE KEY (`RouteName`),
				  KEY `nic` (`nic`)
				) ENGINE=MYISAM AUTO_INCREMENT=50;";
	$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){echo $q->mysql_error_html();}
	
	$buttons="
	buttons : [
	{name: '<strong style=font-size:20px>$new_route</strong>', bclass: 'add', onpress : Add$t},
	{name: '<strong style=font-size:20px>$test_a_route</strong>', bclass: 'Search', onpress : TestRoute$t},
	{name: '<strong style=font-size:20px>$apply</strong>', bclass: 'apply', onpress : Apply$t},
	
	
	],";
	
	$html="
			
	<table class='MAIN_TABLE_ROUTING_RULES' style='display: none' id='MAIN_TABLE_ROUTING_RULES' style='width:100%'></table>
	
	<script>
	var rowid=0;
	$(document).ready(function(){
	$('#MAIN_TABLE_ROUTING_RULES').flexigrid({
	url: '$page?icap-search=yes&t=$t',
	dataType: 'json',
	colModel : [
	
	{display: '<span style=font-size:18px>$nic</span>', name : 'nic', width : 90, sortable : true, align: 'center'},
	{display: '<span style=font-size:18px>$table</span>', name : 'RouteName', width : 343, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$explain</span>', name : 'gateway', width :901, sortable : false, align: 'left'},
	{display: '&nbsp;', name : 'del', width : 80, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$table', name : 'RouteName'},
	{display: '$gateway', name : 'gateway'},
	],
	sortname: 'RouteName',
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
		Loadjs('$page?route-js=yes&t=$t');
	}
	function TestRoute$t(){
		Loadjs('$page?test-route-js=yes');
	}	
	
	function Apply$t(){
		Loadjs('network.restart.php');
		
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
	$table="routing_rules";
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	


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
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,$database);
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error,1);}
	
	
	if(mysql_num_rows($results)==0){
		$array=routes_default();
		echo json_encode($array[1]);
		return;
	}
	
	$fontsize=22;
	
	$types[1]=$tpl->_ENGINE_parse_body("{network_nic}");
	$types[2]=$tpl->_ENGINE_parse_body("{host}");
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$RouteName=$ligne["RouteName"];
		if($ligne["enabled"]==0){$color="#8a8a8a";}
		$style="style='font-size:{$fontsize}px;color:$color;'";
		$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?route-delete-js=yes&ID={$ligne["ID"]}&t=$t');");
		
		$js="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?route-js=yes&ID={$ligne["ID"]}&t=$t');\"
		style='font-size:{$fontsize}px;color:$color;text-decoration:underline'>";
		
		$down=imgsimple("arrow-down-32.png",null,"Loadjs('$MyPage?route-move-js=yes&ID={$ligne["ID"]}&t=$t&dir=down');");
		$up=imgsimple("arrow-up-32.png",null,"Loadjs('$MyPage?route-move-js=yes&ID={$ligne["ID"]}&t=$t&dir=up');");
		
		
		if($ligne["gateway"]==null){$ligne["gateway"]=$ligne["nic"];}
		$EXPLAIN_THIS_RULE=EXPLAIN_THIS_RULE($ligne["ID"],$ligne["nic"]);
		
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<span $style>$js{$ligne["nic"]}</a></span>",
						"<span $style>{$js}{$RouteName}</a></span>",
						"<span style='font-size:16px;color:$color;'>$EXPLAIN_THIS_RULE</span>",
						"<center $style>$delete</center>",
				)
		);

	}
	
	
		echo json_encode($data);
	
}

function EXPLAIN_THIS_RULE($ruleid,$eth=null){
	$q=new mysql();
	$tpl=new templates();
	$D=array();
	$types[1]=$tpl->_ENGINE_parse_body("{network_nic}");
	$types[2]=$tpl->_ENGINE_parse_body("{host}");
	$types[3]=$tpl->_ENGINE_parse_body("NAT");
	$types[4]=$tpl->_ENGINE_parse_body("{blackhole}");
	$types[5]=$tpl->_ENGINE_parse_body("{iprouteprohibit}");
	
	if($eth==null){
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT nic FROM routing_rules WHERE ID='$ruleid'","artica_backup"));
	$nicClass=new system_nic($ligne["nic"]);
	$eth=$ligne["nic"];
	}else{
		$nicClass=new system_nic($eth);
	}
	
	if($nicClass->enabled==0){
		return $tpl->_ENGINE_parse_body("{do_nothing} $nicClass->NICNAME {disabled}");
	}
	if($nicClass->UseSPAN==1){
		return $tpl->_ENGINE_parse_body("{do_nothing} $nicClass->NICNAME {free_mode}");
	}
	
	$results=$q->QUERY_SQL("SELECT * FROM routing_rules_src WHERE ruleid='$ruleid' ORDER BY zOrder","artica_backup");
	$S[]="{when_packets_processed_by_this_interface}: &laquo;$eth&raquo; $nicClass->NICNAME";
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$S[]="{or} {from} {$types[$ligne["type"]]} {$ligne["pattern"]}";
		
	}
	
	$results=$q->QUERY_SQL("SELECT * FROM routing_rules_dest WHERE ruleid='$ruleid' AND gateway='' ORDER BY zOrder","artica_backup");
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$S[]="{or} {to} {$types[$ligne["type"]]} {$ligne["pattern"]}";
	
	}
	
	if($nicClass->GATEWAY<>null){
		if($nicClass->GATEWAY<>"0.0.0.0"){
			$S[]="{or} {from}/{to} {all_networks} {default_gateway} <strong>{$nicClass->GATEWAY}</strong>";
		}
	}
	
	
	if(count($S)==0){
		$S[]="{from}: {all_networks}";
	}
	
	
	

	
	
	$results=$q->QUERY_SQL("SELECT * FROM routing_rules_dest WHERE ruleid='$ruleid' ORDER BY zOrder,metric","artica_backup");
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		if($ligne["type"]==3){continue;}
		if($ligne["gateway"]==null){continue;}
		$S[]="{or} {to} {$types[$ligne["type"]]} {$ligne["pattern"]} {use_the_gateway} <strong>{$ligne["gateway"]}</strong>";
	
	}

	
	$results=$q->QUERY_SQL("SELECT * FROM routing_rules_dest WHERE ruleid='$ruleid' AND type=3 ORDER BY zOrder,metric","artica_backup");
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$S[]="{or} {then} {default_gateway}: {$ligne["gateway"]}";
	
	}
	
	if(count($S)<2){$S[]="{do_nothing}";}
	
	return $tpl->_ENGINE_parse_body(@implode("<br>", $S));
	
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


function route_dump(){
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM routing_rules WHERE ID='{$_GET["ID"]}'","artica_backup"));
	if(!$q->ok){echo $q->mysql_error_html();}
	$title=$ligne["RouteName"];
	
	
	exec("/sbin/ip rule show |grep $title 2>&1",$results1);
	
	exec("/sbin/ip route show table $title 2>&1",$results2);
	
	$route="# Rules *************************************************************************\n".@implode("\n", $results1)."\n\n\n# Table *************************************************************************\n\n".@implode("\n", $results2);
	
	echo "<textarea style='width: 843px; height: 373px;;font-family:Courier New;font-size:16px !important'>$route</textarea>";
	
	
	
	
	
	
}



