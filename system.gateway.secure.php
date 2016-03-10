<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
include_once(dirname(__FILE__).'/ressources/class.html.pages.inc');
include_once(dirname(__FILE__).'/ressources/class.cyrus.inc');
include_once(dirname(__FILE__).'/ressources/class.main_cf.inc');
include_once(dirname(__FILE__).'/ressources/charts.php');
include_once(dirname(__FILE__).'/ressources/class.syslogs.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.stats-appliance.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.tools.inc');

$users=new usersMenus();

if(!$users->AsSystemAdministrator OR !$users->AsSquidAdministrator){
	echo FATAL_ERROR_SHOW_128("{NO_PRIVS}");
	die();
}

if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_GET["rules"])){table();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["settings"])){settings();exit;}
if(isset($_POST["EnableSecureGateway"])){EnableSecureGatewaySave();exit;}
if(isset($_GET["port-js"])){port_js();exit;}
if(isset($_GET["port-popup"])){port_popup();exit;}
if(isset($_POST["dport"])){Save();exit;}
if(isset($_POST["delete"])){Delete();exit;}
tabs();

function delete_js(){
	$t=time();
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT portname FROM gateway_secure WHERE ID='{$_GET["ID"]}'","artica_backup"));
	$confirm=$tpl->javascript_parse_text("{delete} {$_GET["ID"]}:{$ligne["portname"]} ?");
	$html="
	var xDel$t= function (obj) {
	var res=obj.responseText;
	if (res.length>0){alert(res);return;}
	$('#TABLE_GWSECURE_OBJECT').flexReload();
}

function Del$t(){
if(!confirm('$confirm')){return;}
var XHR = new XHRConnection();
XHR.appendData('delete','{$_GET["ID"]}');
XHR.sendAndLoad('$page', 'POST',xDel$t);

}

Del$t();";

echo $html;
}

function tabs(){

	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$array["settings"]="{parameters}";
	$array["rules"]="{rules}";
	
	$fontsize=22;

	while (list ($num, $ligne) = each ($array) ){

		if($num=="ssl"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.sslbump.php?popup=yes&t=$t\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;

		}
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");

	}

	echo build_artica_tabs($html, "secure_gateway_tabs")."<script>LeftDesign('transparent-256-opac20.png');</script>";


}
function port_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	header("content-type: application/x-javascript");
	if($ID==0){
		$TITLE=$tpl->javascript_parse_text("{new_whitelist_port}");
	}else{
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT portname FROM gateway_secure WHERE ID='$ID'","artica_backup"));
		$TITLE=utf8_encode($ligne["portname"]);
	}

	echo "YahooWin2('750','$page?port-popup=yes&ID=$ID&t=$t','$TITLE',true);";

}

function port_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$btname="{add}";
	$q=new mysql();
	$ID=$_GET["ID"];
	$ligne["ttl"]="60";
	$ligne["enabled"]=1;
	if($ID>0){
		$btname="{apply}";
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM gateway_secure WHERE ID='$ID'","artica_backup"));

	}


	$PROTO[0]="TCP";
	$PROTO[1]="UDP";
	

	$html="
<div style='width:98%' class=form>
	<table style='width:99%'>
	<tr>
		<td class=legend style='font-size:22px'>{PortName}:</td>
		<td>". Field_text("portname-$t",$ligne["portname"],"font-size:22px;width:400px")."</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:22px'>{port}:</td>
		<td>". Field_text("dport-$t",$ligne["dport"],"font-size:22px;width:120px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{protocol}:</td>
		<td>". Field_array_Hash($PROTO, "dproto-$t",$ligne["dproto"],"style:font-size:22px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{enabled}:</td>
		<td>". Field_checkbox_design("enabled-$t",1,$ligne["enabled"])."</td>
	</tr>
	<tr>
		<td colspan=2 align=right><hr>".button("$btname","Save$t()",30)."</td>
	</tr>
</table>
</div>
<script>
var x_Save$t= function (obj) {
	var ID='$ID';
	var results=obj.responseText;
	if(results.length>3){alert(results);document.getElementById('$t').innerHTML='';return;}
	if(document.getElementById('$t')){document.getElementById('$t').innerHTML='';}
	if(document.getElementById('anim-$t')){document.getElementById('anim-$t').innerHTML='';}
	if(ID==0){YahooWin2Hide();}
	$('#TABLE_GWSECURE_OBJECT').flexReload();
}
function Save$t(){
	var XHR = new XHRConnection();
	var enabled=0;
	XHR.appendData('ID', $ID);
	XHR.appendData('dport', document.getElementById('dport-$t').value);
	XHR.appendData('dproto', document.getElementById('dproto-$t').value);
	if(document.getElementById('enabled-$t').checked){enabled=1;}
	XHR.appendData('enabled', enabled);
	XHR.appendData('portname', encodeURIComponent(document.getElementById('portname-$t').value));
	XHR.sendAndLoad('$page', 'POST',x_Save$t);
}
</script>
";

	echo $tpl->_ENGINE_parse_body($html);
}
function Delete(){

	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM gateway_secure WHERE ID='{$_POST["delete"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}

}

function Save(){
	$q=new mysql();
	$portname=mysql_escape_string2(url_decode_special_tool($_POST["portname"]));
	$dport=intval($_POST["dport"]);
	$dproto=intval($_POST["dproto"]);
	$enabled=intval($_POST["enabled"]);
	$ID=$_POST["ID"];
	
	$dport=intval($_POST["dport"]);
	if($ID==0){
	
	
		$q->QUERY_SQL("INSERT IGNORE INTO `gateway_secure` (portname,dport,dproto,enabled)
				VALUES ('$portname','$dport','$dproto','$enabled')","artica_backup");
	
	}else{
	
	$q->QUERY_SQL("UPDATE `gateway_secure`
			SET portname='$portname',
			dport='$dport',dproto='$dproto',
			enabled='$enabled' WHERE ID=$ID","artica_backup");
	
	}
	
	if(!$q->ok){echo $q->mysql_error;}
	
		
	
	
}

function settings(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	
	$q=new mysql();
	$sql="CREATE TABLE IF NOT EXISTS `gateway_secure`
	(  ID INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	 `dport` INT(10) NOT NULL, `portname` varchar(256) NOT NULL, `dproto` smallint(1) NOT NULL, `enabled` smallint(1) NOT NULL, KEY `portname` (`portname`), KEY `enabled` (`enabled`) ) ENGINE=MYISAM;";
	$q->QUERY_SQL($sql,"artica_backup");
	if($q->COUNT_ROWS("gateway_secure", "artica_backup")==0){AddDefaults();}
	
	
	$EnableSecureGateway=intval($sock->GET_INFO("EnableSecureGateway"));
	$p=Paragraphe_switch_img("{secure_gateway}", "{secure_gateway_explain}","EnableSecureGateway",$EnableSecureGateway,null,1450);
	
	$html="<div style='width:100%;margin-bottom:30px;font-size:40px'>{secure_gateway}</div>
	<div style='width:98%' class=form>
		$p
	<div style='text-align:right'>". button("{apply}", "Save$t()",40)."</div>
	</div>			
<script>
var xSaveEnable$t=function(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	RefreshTab('secure_gateway_tabs');
	Loadjs('firehol.progress.php');
	CacheOff();
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('EnableSecureGateway',document.getElementById('EnableSecureGateway').value);
	XHR.sendAndLoad('$page', 'POST',xSaveEnable$t);
}
</script>
";
	
echo $tpl->_ENGINE_parse_body($html);	
}
function EnableSecureGatewaySave(){
	$sock=new sockets();
	$sock->SET_INFO("EnableSecureGateway", $_POST["EnableSecureGateway"]);
	
}

function table(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$enabled=$tpl->javascript_parse_text("{enabled}");
	$port=$tpl->javascript_parse_text("{port}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$identifier=$tpl->javascript_parse_text("{identifier}");
	$add=$tpl->javascript_parse_text("{new_member}");
	$object_name=$tpl->javascript_parse_text("{PortName}");
	$new_quota_object=$tpl->_ENGINE_parse_body("{new_whitelist_port}");
	$title=$tpl->javascript_parse_text("{whitelisted_ports}");
	$apply_params=$tpl->_ENGINE_parse_body("{apply}");
	$protocol=$tpl->_ENGINE_parse_body("{protocol}");
	$apply_firewall_rules=$tpl->javascript_parse_text("{apply_firewall_rules}");
	$tablewidht=883;
	$t=time();

	$q=new mysql();
	$sql="CREATE TABLE IF NOT EXISTS `gateway_secure` 
	(  ID INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	 `dport` INT(10) NOT NULL, `portname` varchar(256) NOT NULL, `dproto` smallint(1) NOT NULL, `enabled` smallint(1) NOT NULL, KEY `portname` (`portname`), KEY `enabled` (`enabled`) ) ENGINE=MYISAM;";
	$q->QUERY_SQL($sql,"artica_backup");
	if($q->COUNT_ROWS("gateway_secure", "artica_backup")==0){AddDefaults();}
	if(!$q->ok){echo $q->mysql_error_html();}

	$apply_paramsbt="{separator: true},{name: '<strong style=font-size:18px>$apply_params</strong>', bclass: 'apply', onpress : SquidBuildNow$t},";

	$buttons="buttons : [
	{name: '<strong style=font-size:18px>$new_quota_object</strong>', bclass: 'Add', onpress : AddQuota$t},
	{name: '<strong style=font-size:18px>$apply_firewall_rules</strong>', bclass: 'Apply', onpress : FW$t},
	],	";
	echo "

	<table class='$t' style='display: none' id='TABLE_GWSECURE_OBJECT' style='width:99%;text-align:left'></table>
	<script>
	$(document).ready(function(){
	$('#TABLE_GWSECURE_OBJECT').flexigrid({
	url: '$page?search=yes&t=$t',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:22px>$port</span>', name : 'dport', width : 130, sortable : false, align: 'right'},
	{display: '<span style=font-size:22px>$object_name</span>', name : 'portname', width : 450, sortable : false, align: 'left'},
	{display: '<span style=font-size:22px>$protocol</span>', name : 'dproto', width : 138, sortable : false, align: 'left'},
	{display: '<span style=font-size:22px>$enabled</span>', name : 'enabled', width : 83, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'none3', width : 60, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$object_name', name : 'objectname'},


	],
	sortname: 'dport',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:26px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true
});
});

function SquidBuildNow$t(){
Loadjs('squid.compile.php');
}
function AddQuota$t(){
Loadjs('$page?port-js=yes&t=$t&ID=0');
}
function FW$t(){
	Loadjs('firehol.progress.php');
}
</script>
";
}


function AddDefaults(){
	
	$q=new mysql();
	
	$q->QUERY_SQL("INSERT IGNORE INTO `gateway_secure` (portname,dport,dproto,enabled)
			VALUES 
			('HTTP service','80','0','1'),
			('HTTPs service','443','0','1'),
			('SMTP service','25','0','1'),
			('SMTP service','587','0','1'),
			('SMTPs service','465','0','1'),
			('IMAP service','143','0','1'),
			('IMAPs service','995','0','1'),
			('POP3 service','110','0','1'),
			('POP3s service','995','0','1'),
			('FTP service','21','0','1'),
			('FTP service -2','20','0','1'),
			('SSH service','22','0','1'),
			('Telnet service','23','0','1'),
			('DNS service','53','0','1'),
			('DNS service','53','1','1'),
			('NNTP service','119','0','1'),
			('NETBIOS service','137','0','1'),
			('NETBIOS service','138','0','1'),
			('NETBIOS service','139','0','1'),
			('TSE','3389','0','1'),
			('iTunes','3689','0','1'),
			('iTunes','3689','1','1')
			","artica_backup");
	
	
	
}

function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$sock=new sockets();
	$q=new mysql();
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	$search='%';
	$table="gateway_secure";
	$page=1;
	$data = array();
	$data['rows'] = array();
	$FORCE_FILTER=1;

	
	if($q->COUNT_ROWS($table, "artica_backup")==0){AddDefaults();}
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}

	if (isset($_POST['page'])) {$page = $_POST['page'];}
	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){json_error_show("$q->mysql_error",1);}
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){json_error_show("$q->mysql_error",0);}
		$total = $ligne["TCOUNT"];
			
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}

	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT *  FROM `$table` WHERE $FORCE_FILTER $searchstring $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){json_error_show("$q->mysql_error,$sql",1);}



	$data['page'] = $page;
	$data['total'] = $total;

	if(mysql_num_rows($results)==0){json_error_show("no data",1);}

	$PROTO[0]="TCP";
	$PROTO[1]="UDP";
	
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$color="black";
		$download="&nbsp;";
		$imge="checkbox-off-24.png";
		$delete=imgsimple("delete-48.png",null,"Loadjs('$MyPage?delete-js=yes&ID={$ligne["ID"]}')");

		$href="<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('$MyPage?port-js=yes&ID={$ligne["ID"]}');\"
		style=\"font-size:22px;text-decoration:underline;color:$color\">";

		
		$objectname=utf8_encode($ligne["portname"]);
		$dport=$ligne["dport"];
		$xPROTO=$PROTO[$ligne["dproto"]];
		if($ligne["enabled"]==1){
			$imge="checkbox-on-24.png";
		}

		$data['rows'][] = array(
				'id' => "ACC{$ligne['ID']}",
				'cell' => array(
						"$href{$dport}</a>",
						"$href{$objectname}</a>",
						"$href{$xPROTO}</a>",
						"<center><img src='img/$imge'></center>",
						"<center>$delete</center>"
				)
		);
	}
	echo json_encode($data);
}