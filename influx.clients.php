<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsWebStatisticsAdministrator){
	$tpl=new templates();
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	die();	
}


if(isset($_GET["rule-tabs"])){rule_tab();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["rule-id-js"])){rule_js();exit;}
if(isset($_GET["rule-id"])){rule_popup();exit;}
if(isset($_GET["group-text"])){echo rule_group_text($_GET["group-text"]);exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["delete-rule-js"])){delete_rule_js();exit;}
if(isset($_POST["DELETERULE"])){delete_rule();exit;}
table();





function delete_rule_js(){
	$t=time();
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
echo "
var xSave$t=function (obj) {
	var tempvalue=obj.responseText;
	if (tempvalue.length>3){alert(tempvalue);return;}
	$('#INFLUX_STATS_CLIENTS').flexReload();
	
	
}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('DELETERULE','{$_GET["ID"]}');
 	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
Save$t();";
	
	
}

function rule_js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$ID=intval($_GET["rule-id-js"]);
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$title=$tpl->javascript_parse_text("{new_client}");

	if($ID>0){
		$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT hostname FROM influxIPClients WHERE ID=$ID"));
		$title=$tpl->javascript_parse_text("{rule}:{$ligne["hostname"]}");
		echo "YahooWin2Hide();YahooWin2('850','$page?rule-id=yes&ID=$ID&t={$_GET["t"]}','$title')";
		return;
	}
	echo "YahooWin2Hide();YahooWin2('850','$page?rule-id=yes&ID=$ID&t={$_GET["t"]}','$title')";
}

function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$t=time();
	$pattern=$tpl->_ENGINE_parse_body("{pattern}");
	$type=$tpl->_ENGINE_parse_body("{type}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$new_rule=$tpl->_ENGINE_parse_body("{new_client}");
	$title=$tpl->javascript_parse_text("{statistics_database_clients}");
	$cache_deny=$tpl->javascript_parse_text("{cache}");
	$global_access=$tpl->javascript_parse_text("{global_access}");
	$deny_auth=$tpl->javascript_parse_text("{authentication}");
	$deny_ufdb=$tpl->javascript_parse_text("{webfiltering}");
	$deny_icap=$tpl->javascript_parse_text("{antivirus}");
	$groupid=$tpl->javascript_parse_text("{SquidGroup}");
	$deny_ext=$tpl->javascript_parse_text("{dangerous_extensions}");
	$enabled=$tpl->javascript_parse_text("{enabled}");
	$hostname=$tpl->javascript_parse_text("{hostname}");
	
	$client=$tpl->javascript_parse_text("{client}");
	$t=time();
	$apply=$tpl->javascript_parse_text("{compile_rules}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");


	
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$new_rule</strong>', bclass: 'add', onpress : NewRule$t},
	{name: '<strong style=font-size:18px>$apply</strong>', bclass: 'apply', onpress : SquidBuildNow$t},
	
	],";	
	
	
	$html="
<table class='INFLUX_STATS_CLIENTS' style='display: none' id='INFLUX_STATS_CLIENTS' style='width:100%'></table>
<script>
function Load{$_GET["t"]}(){

	$('#INFLUX_STATS_CLIENTS').flexigrid({
	url: '$page?search=yes&t={$_GET["t"]}',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:22px>$hostname</span>', name : 'hostname', width :427, sortable : true, align: 'left'},
	{display: '<span style=font-size:22px>$ipaddr</span>', name : 'ipaddr', width :224, sortable : false, align: 'left'},
	{display: '<span style=font-size:22px>$client</span>', name : 'isServ', width : 118, sortable : true, align: 'center'},
	{display: '&nbsp;', name : 'delete', width : 70, sortable : false, align: 'center'},
	],
	$buttons

	sortname: 'hostname',
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
}

function NewRule$t(){
	Loadjs('$page?rule-id-js=0&t={$_GET["t"]}');
}

function SquidBuildNow$t(){
	Loadjs('postgres.progress.php');
}

function SSLOptions$t(){
	Loadjs('squid.ssl.center.php?js=yes');
}


Load{$_GET["t"]}();

</script>
";	
	
	echo $html;
	
}

function rule_save(){
	
	$ID=$_POST["ID"];
	unset($_POST["ID"]);
	
	
	while (list ($key, $val) = each ($_POST)){
		
		$add_fields[]="`$key`";
		$add_values[]="'$val'";
		$edit_fields[]="`$key`='$val'";
		
		
	}
	
	
	if($ID==0){
		$sql="INSERT IGNORE INTO influxIPClients (".@implode(",", $add_fields).") VALUES (".@implode(",", $add_values).")";
		
	}else{
		$sql="UPDATE influxIPClients SET ".@implode(",", $edit_fields)." WHERE ID='$ID'";
	}
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
}

function delete_rule(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM influxIPClients WHERE ID='{$_POST["DELETERULE"]}'");
	if(!$q->ok){echo $q->mysql_error;return;}
}

function rule_popup(){
	$ID=intval($_GET["ID"]);
	$tpl=new templates();
	$page=CurrentPageName();
	$btname="{add}";
	$t=time();
	$q=new mysql_squid_builder();
	$title=$tpl->javascript_parse_text("{new_client}");
	
	if($ID>0){
		$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT * FROM influxIPClients WHERE ID=$ID"));
		$btname="{apply}";
		$title="{rule}:{$ligne["hostname"]}";
	}
	
	if(!is_numeric($ligne["isServ"])){$ligne["isServ"]=1;}
	

$html="<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
	<td colspan=2><div style='font-size:32px;margin-bottom:15px'>$title</div></td>
	</tr>
	<tr>
		<td class=legend style='font-size:20px'>{hostname}:</td>
		<td style='font-size:20px'>". Field_text("hostname-$t", $ligne["hostname"],"font-size:20px;width:350px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:20px'>{ipaddr}:</td>
		<td style='font-size:20px'>". field_ipv4("ipaddr-$t", $ligne["ipaddr"],"font-size:20px")."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:20px'>{client}:</td>
		<td style='font-size:20px'>". Field_checkbox_design("isServ-$t", 1,$ligne["isServ"],"")."</td>
	</tr>
				
	<tr style='height:50px'>
		<td colspan=2 align='right'><hr>". button($btname,"Save$t()",32)."</td>
 </tr>
 </table>
	
 <script>
var xSave$t=function (obj) {
	var tempvalue=obj.responseText;
	if (tempvalue.length>3){alert(tempvalue);return;}
	var ID=$ID;
	if(ID==0){YahooWin2Hide();}
	$('#INFLUX_STATS_CLIENTS{$_GET["t"]}').flexReload();
	
}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ID','$ID');
	XHR.appendData('hostname',document.getElementById('hostname-$t').value);
	XHR.appendData('ipaddr',document.getElementById('ipaddr-$t').value);
 	if(document.getElementById('isServ-$t').checked){XHR.appendData('isServ',1);}else{XHR.appendData('isServ',0);}
 	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
	
	
 ";
	
 echo $tpl->_ENGINE_parse_body($html);
}


	
function search(){
		$tpl=new templates();
		$MyPage=CurrentPageName();
		$q=new mysql_squid_builder();
		$sock=new sockets();
		$t=$_GET["t"];
		$search='%';
		$table="influxIPClients";
		$page=1;
		$FORCE_FILTER=null;
		$total=0;


				
		
	
		if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
		if(isset($_POST['page'])) {$page = $_POST['page'];}
	
		$searchstring=string_to_flexquery();
		if($searchstring<>null){
			$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
			$total = $ligne["TCOUNT"];
	
		}else{
			$total = $q->COUNT_ROWS($table);
		}
	
		if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
		$pageStart = ($page-1)*$rp;
		if(is_numeric($rp)){$limitSql = "LIMIT $pageStart, $rp";}
	
		$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
		$results = $q->QUERY_SQL($sql);
	
		$no_rule=$tpl->_ENGINE_parse_body("{no_rule}");
	
		$data = array();
		$data['page'] = $page;
		$data['total'] = $total;
		$data['rows'] = array();
	
		if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}
		if(mysql_num_rows($results)==0){json_error_show("!!! no data");}
		$uncrypt_ssl=$tpl->javascript_parse_text("{uncrypt_ssl}");
		$pass_ssl=$tpl->javascript_parse_text("{pass_connect_ssl}");
		$error_firwall_not_configured=$tpl->javascript_parse_text("{error_firwall_not_configuredisquid}");
		$trust_ssl=$tpl->javascript_parse_text("{trust_ssl}");
		$tpl=new templates();
		$all=$tpl->javascript_parse_text("{all}");
		$and_text=$tpl->javascript_parse_text("{and}");
		
		$edit=$tpl->javascript_parse_text("{edit}");
		$squid_acls_groups=new squid_acls_groups();
		while ($ligne = mysql_fetch_assoc($results)) {
			$color="black";
			$ID=$ligne["ID"];
			$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?delete-rule-js=yes&ID=$ID&t={$_GET["t"]}',true)");
			$edit_group=null;
			$hostname=$ligne["hostname"];
			$ipaddr=utf8_encode($ligne["ipaddr"]);
			$img="ok32.png";

			if($ligne["isServ"]==0){
				$img="ok32-grey.png";
			}

			$EditJs="<a href=\"javascript:blur();\"
			OnClick=\"javascript:Loadjs('$MyPage?rule-id-js=$ID&t={$_GET["t"]}');\"
			style='font-size:26px;font-weight:normal;color:$color;text-decoration:underline'>";
			
				
			$data['rows'][] = array(
			 'id' => $ID,
			 'cell' => array(
			 		"<span style='font-size:26px;font-weight:normal;color:$color'>$EditJs$hostname</a></span>",
			 		"<span style='font-size:26px;font-weight:normal;color:$color'>$EditJs$ipaddr</a></span>",
			 		"<center><img src='img/$img'></a></center>",
			 		"<center style='margin-top:3px;font-size:30px;font-weight:normal;color:$color'>$delete</center>",)
						);
		}
	
		echo json_encode($data);
	}
