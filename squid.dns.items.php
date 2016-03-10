<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.tcpip.inc');
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsSquidAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_GET["hosts"])){hosts();exit;}
if(isset($_GET["host-js"])){hosts_js();exit;}
if(isset($_GET["host-popup"])){hosts_popup();exit;}
if(isset($_GET["host-tab"])){hosts_tab();exit;}
if(isset($_GET["delete-host-js"])){hosts_js_delete();exit;}
if(isset($_POST["delete-host-id"])){hosts_delete();exit;}
if(isset($_POST["ID"])){hosts_save();exit;}

if(isset($_GET["host-aliases"])){aliases_table();exit;}
if(isset($_GET["host-aliases-list"])){aliases_list();exit;}
if(isset($_GET["alias-js"])){aliases_js();exit;}
if(isset($_POST["alias-add"])){aliases_add();exit;}
if(isset($_GET["delete-alias-js"])){alias_js_delete();exit;}
if(isset($_POST["delete-alias-id"])){alias_delete();exit;}



table();

function alias_delete(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM dnsmasq_cname WHERE ID={$_POST["delete-alias-id"]}");
	if(!$q->ok){echo $q->mysql_error;}
	
}

function aliases_table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$dnsmasq_address_text=$tpl->_ENGINE_parse_body("{dnsmasq_address_text}");
	$hosts=$tpl->_ENGINE_parse_body("{hosts}");
	$addr=$tpl->_ENGINE_parse_body("{addr}");
	$new_alias=$tpl->_ENGINE_parse_body("{new_alias}");
	$aliases=$tpl->_ENGINE_parse_body("{aliases}");
	$buttons="
	buttons : [
	{name: '$new_alias', bclass: 'add', onpress : Adalias$t},
	],";

	$html="

	
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>


	<script>
	$(document).ready(function(){
	var md5H='';
	$('#flexRT$t').flexigrid({
	url: '$page?host-aliases-list=yes&t=$t&tt={$_GET["tt"]}&recordid={$_GET["ID"]}',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:18px>$hosts</span>', name : 'hostname', width : 406, sortable : false, align: 'left'},
	{display: '&nbsp;', name : 'delete', width : 46, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$hosts', name : 'hostname'},
	],
	sortname: 'hostname',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 150,
	showTableToggleBtn: false,
	width: '99%',
	height: 300,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
});


function Adalias$t(){
Loadjs('$page?alias-js=yes&t=$t&tt={$_GET["t"]}&recordid={$_GET["ID"]}',true);

}


</script>
";

echo $tpl->_ENGINE_parse_body($html);
}

function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$dnsmasq_address_text=$tpl->_ENGINE_parse_body("{dnsmasq_address_text}");
	$hosts=$tpl->_ENGINE_parse_body("{hosts}");
	$addr=$tpl->_ENGINE_parse_body("{addr}");
	$new_computer=$tpl->_ENGINE_parse_body("{new_host}");
	
	$aliases=$tpl->_ENGINE_parse_body("{aliases}");
	$appy=$tpl->_ENGINE_parse_body("{apply}");
	$title=$tpl->javascript_parse_text("{dns_items}");
	
	
	
	
	$q=new mysql_squid_builder();
	$sql="CREATE TABLE IF NOT EXISTS `dnsmasq_records` (
				`ID` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				`ipaddrton` BIGINT UNSIGNED,
				`ipaddr` VARCHAR( 90 ) NOT NULL,
				`hostname` VARCHAR(128),
				 KEY `ipaddr` (`ipaddr`),
				 KEY `ipaddrton` (`ipaddrton`),
				 KEY `hostname` (`hostname`)
				 )  ENGINE = MYISAM;
			";
	$q->QUERY_SQL($sql);
	
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$new_computer</strong>', bclass: 'add', onpress : AddHost$t},
	
	
	{name: '<strong style=font-size:18px>$appy</strong>', bclass: 'apply', onpress : Apply$t},
	],";
	
	$html="
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
<script>
$(document).ready(function(){
	var md5H='';
	$('#flexRT$t').flexigrid({
	url: '$page?hosts=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '<span style=font-size:18px>$hosts</span>', name : 'hostname', width : 481, sortable : false, align: 'left'},
		{display: '<span style=font-size:18px>$addr</span>', name : 'ipaddrton', width :193, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$aliases</span>', name : 'aliases', width :193, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'delete', width : 100, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$hosts', name : 'hostname'},
	{display: '$addr', name : 'ipaddr'},
	],
	sortname: 'hostname',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rp: 150,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});
	});
function FlexReloadDNSMASQHOSTS(){
	$('#flexRT$t').flexReload();
}

	
	
function DnsmasqDeleteAddress(md5,num){
	md5H=md5;
	var XHR = new XHRConnection();
	XHR.appendData('DnsmasqDeleteAddress',num);
	XHR.sendAndLoad('$page', 'GET',x_AddDnsMasqHostT);
}
	
function AddHost$t(){
	Loadjs('$page?host-js=yes&ID=0&t=$t',true);
	
}

function Apply$t(){
	Loadjs('dnsmasq.restart.progress.php');
}
	
var x_AddDnsMasqHostT= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	$('#row'+md5H).remove();
}
	
</script>
";

	echo $tpl->_ENGINE_parse_body($html);
}	

function hosts_save(){
	$ID=$_POST["ID"];
	$ipClass=new IP();
	if(!$ipClass->isValid($_POST["ipaddr"])){echo "Invalid IP address:{$_POST["ipaddr"]}\n";return;}
	
	$ip2Long2=ip2Long2($_POST["ipaddr"]);
	if($ID>0){
		$sql="UPDATE dnsmasq_records SET `hostname`='{$_POST["hostname"]}',`ipaddr`='{$_POST["ipaddr"]}',`ipaddrton`='$ip2Long2' WHERE ID='$ID'";
	}else{
		$sql="INSERT IGNORE INTO dnsmasq_records(`hostname`,`ipaddr`,`ipaddrton`)
		VALUES ('{$_POST["hostname"]}',	'{$_POST["ipaddr"]}','$ip2Long2');
		";
	}
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n***$sql\n****\n";}
	
}

function hosts_tab(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `hostname` FROM dnsmasq_records WHERE ID='$ID'"));
	
	$array["host-popup"]=$ligne["hostname"];
	$array["host-aliases"]='{aliases}';

	while (list ($num, $ligne) = each ($array) ){
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&ID=$ID&t=$t\"><span style='font-size:14px'>$ligne</span></a></li>\n");
	}

	echo build_artica_tabs($html, "dns_records_$ID");

}



function hosts_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$time=time();
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	$btname="{add}";
	if($ID>0){
		$btname="{apply}";
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM dnsmasq_records WHERE ID='$ID'"));
	}
	
	
	$html="
	<center id='id-$time' class=form style='width:95%'>
	<table style='width:99%' >
	<tbody>
	<tr>
		<td class=legend style='font-size:18px'>{hostname}</td>
		<td>" . Field_text("hostname-$time",$ligne["hostname"],"font-size:18px;padding:3px;width:270px") . "</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{ipaddr}</td>
		<td>" . field_ipv4("ipaddr-$time",$ligne["ipaddr"],"font-size:18px",false,"SaveCK$time(event)") . "</td>
	</tr>
	<tr>
	<td colspan=2 align='right'><hr>". button($btname,"Save$time()",22)."</td>
	</tr>
	</tbody>
	</table>
	</center>
<script>
var xSave$time= function (obj) {
	var ID=$ID;
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	FlexReloadDNSMASQHOSTS();
	$('#flexRT$t').flexReload();
	if(ID==0){YahooWin3Hide();}
}
function Save$time(){
	var XHR = new XHRConnection();
	XHR.appendData('ID','$ID');
	XHR.appendData('hostname',document.getElementById('hostname-$time').value);
	XHR.appendData('ipaddr',document.getElementById('ipaddr-$time').value);
	XHR.sendAndLoad('$page', 'POST',xSave$time,true);
}
function SaveCK$time(e){
	if(checkEnter(e)){ Save$time(); }
}

</script>
";
echo $tpl->_ENGINE_parse_body($html);
}

function alias_js_delete(){
	header("content-type: application/x-javascript");
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	
	$html="
	var xDelete{$t}{$ID} = function (obj) {
	var ID=$ID;
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#flexRT$t').flexReload();
	$('#flexRT$tt').flexReload();
	}
	
	function Delete{$t}{$ID}(){
	
	var XHR = new XHRConnection();
	XHR.appendData('delete-alias-id','$ID');
	XHR.sendAndLoad('$page', 'POST',xDelete{$t}{$ID},true);
	}
	Delete{$t}{$ID}();";
	echo $html;	
	
	
}

function hosts_js_delete(){
	header("content-type: application/x-javascript");
	$ID=$_GET["ID"];
	$t=$_GET["t"];	
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$delete=$tpl->javascript_parse_text("{remove}");
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `hostname`,`ipaddr` FROM dnsmasq_records WHERE ID='$ID'"));
	$html="
var xDelete{$t}{$ID} = function (obj) {
	var ID=$ID;
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#flexRT$t').flexReload();
}			
	
function Delete{$t}{$ID}(){
	if( !confirm('$delete {$ligne["hostname"]}/{$ligne["ipaddr"]} ?') ){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-host-id','$ID');
	XHR.sendAndLoad('$page', 'POST',xDelete{$t}{$ID},true);	
}
Delete{$t}{$ID}();";
echo $html;
}

function hosts_delete(){
	$ID=$_POST["delete-host-id"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM dnsmasq_records WHERE ID='$ID'");
	$q->QUERY_SQL("DELETE FROM dnsmasq_cname WHERE recordid=$ID");
}

function hosts_js(){
	header("content-type: application/x-javascript");
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	if($ID==0){
		$title=$tpl->javascript_parse_text("{new_host}");
		echo "YahooWin3('550','$page?host-popup=yes&ID=$ID&t=$t','$title');";
	}else{
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `hostname` FROM dnsmasq_records WHERE ID='$ID'"));
		$title=$tpl->javascript_parse_text("{host}:{$ligne["hostname"]}");
		echo "YahooWin3('550','$page?host-tab=yes&ID=$ID&t=$t','$title');";
	}
}

function aliases_js(){
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	$tpl=new templates();
	$recordid=$_GET["recordid"];
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `hostname` FROM dnsmasq_records WHERE ID='$recordid'"));
	$hostname=$ligne["hostname"];
	$time=time();
	$alias=$tpl->javascript_parse_text("{alias}");
	$html="
var xAdd$time = function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#flexRT$t').flexReload();
	$('#flexRT$tt').flexReload();
}
	
function Add$time(){
	var host=prompt('$hostname $alias ?');
	if(!host){return;}
	var XHR = new XHRConnection();
	XHR.appendData('alias-add',host);
	XHR.appendData('recordid',$recordid);
	XHR.sendAndLoad('$page', 'POST',xAdd$time,true);
}
Add$time();";
echo $html;	
}
function aliases_add(){
	
	$sql="INSERT IGNORE INTO dnsmasq_cname (recordid,hostname) VALUES ('{$_POST["recordid"]}','{$_POST["alias-add"]}');";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
}

function aliases_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	$t=$_GET["t"];
	$search='%';
	$table="(SELECT * FROM dnsmasq_cname WHERE recordid={$_GET["recordid"]}) as t";
	
	
	$page=1;
	$FORCE_FILTER=null;
	
	$total=0;
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
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
	
	$fontsize="16";
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?delete-alias-js=yes&ID={$ligne["ID"]}&t=$t')");
	
		$editjs="<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('$MyPage?host-js=yes&ID={$ligne["ID"]}&t=$t',true);\"
		style='font-size:{$fontsize}px;font-weight:bold;color:$color;text-decoration:underline'>";
		$editjs=null;
	
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount FROM dnsmasq_cname WHERE recordid='{$ligne["ID"]}'"));
		$hostname=$ligne["hostname"];
		$data['rows'][] = array(
			'id' => $ligne['ID'],
			'cell' => array(
				"<span style='font-size:{$fontsize}px;font-weight:bold;color:$color'>$editjs$hostname</a><br><i style='font-size:12px'>&nbsp;$grouptype</i></span>",
				"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$delete</span>",)
		);
	}
	
echo json_encode($data);
}
	
	
function hosts(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	$t=$_GET["t"];
	$search='%';
	$table="dnsmasq_records";
	
	
	$page=1;
	$FORCE_FILTER=null;
	
	$total=0;
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	
	}else{
		$total = $q->COUNT_ROWS("dnsmasq_records");
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
	
	$fontsize="22";
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?delete-host-js=yes&ID={$ligne["ID"]}&t=$t&tt={$_GET["tt"]}')");
	
		$editjs="<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('$MyPage?host-js=yes&ID={$ligne["ID"]}&t=$t',true);\"
		style='font-size:{$fontsize}px;font-weight:bold;color:$color;text-decoration:underline'>";
	
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount FROM dnsmasq_cname WHERE recordid='{$ligne["ID"]}'"));
		$hostname=$ligne["hostname"];
		$ipaddr=$ligne["ipaddr"];
		$Items=$ligne2["tcount"];
	
	$data['rows'][] = array(
			'id' => $ligne['ID'],
			'cell' => array(
					"<span style='font-size:{$fontsize}px;font-weight:bold;color:$color'>$editjs$hostname</a><br><i style='font-size:12px'>&nbsp;$grouptype</i></span>",
					"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$ipaddr</span>",
					"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$Items</span>",
					"<center style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$delete</center>",)
	);
	}
	
	
	echo json_encode($data);
	
	}