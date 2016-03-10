<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die();}
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["hostname"])){save_hostname();exit;}
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["status-list"])){status_list();exit;}
	if(isset($_POST["delete-file"])){delete_file();exit;}
	if(isset($_POST["scan-now"])){scan_now();exit;}
	if(isset($_GET["test-nas-popup"])){test_nas_popup();exit;}
	if(isset($_GET["test-nas-js"])){test_nas_js();exit;}
	if(isset($_POST["import-now"])){import_now();exit;}
	if(isset($_GET["events"])){events();exit;}
	if(isset($_POST["delete-all"])){delete_all();exit;}
js();

function test_nas_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{test_connection}");
	echo "YahooWin3('650','$page?test-nas-popup=yes','$title');";
}


function js(){
	//echo "alert('".$tpl->javascript_parse_text("Beta mode")."')";return;
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	if($DisableArticaProxyStatistics==1){
		
		echo "alert('".$tpl->javascript_parse_text("{DisableArticaProxyStatistics_warn}")."')";
		return;
		
	}
	
	$title=$tpl->javascript_parse_text("{mysql_statistics_engine}");
	echo "YahooWin2('750','$page?tabs=yes','$title');";
	
}

function tabs(){
	$t=time();
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$array["popup"]='{settings}';
	$array["status"]='{status}';
	$array["events"]='{events}';
	$sock=new sockets();
	$font=" style='font-size:14px'";
	while (list ($num, $ligne) = each ($array) ){

		
		$html[]= "<li $font><a href=\"$page?$num=yes\"><span>$ligne</span></a></li>\n";
	}


	echo build_artica_tabs($html, "squid_old_logs_import","100%");

}


function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$SquidOldLogsNAS=unserialize(base64_decode($sock->GET_INFO("SquidOldLogsNAS")));
	$EnableImportWithSarg=$sock->GET_INFO("EnableImportWithSarg");
	$EnableImportOldSquid=$sock->GET_INFO("EnableImportOldSquid");
	if(!is_numeric($EnableImportOldSquid)){$EnableImportOldSquid=0;}
	if(!is_numeric($EnableImportWithSarg)){$EnableImportWithSarg=1;}
	
	$t=time();
	$html="<div style='font-size:16px' class=explain>{explain_oldsquidstats}</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px'>{enable_importation}:</td>
		<td>".Field_checkbox("EnableImportOldSquid-$t",1,$EnableImportOldSquid,"EnableImportOldSquidCK();")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{enable_sarg_report}:</td>
		<td>".Field_checkbox("EnableImportWithSarg-$t",1,$EnableImportWithSarg)."</td>
	</tr>
				
				
	<tr>
		<td class=legend style='font-size:16px'>{hostname}:</td>
		<td>".Field_text("hostname-$t",$SquidOldLogsNAS["hostname"],"font-size:16px;width:300px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{shared_folder}:</td>
		<td>".Field_text("folder-$t",$SquidOldLogsNAS["folder"],"font-size:16px;width:300px")."</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:16px'>{username}:</td>
		<td>".Field_text("username-$t",$SquidOldLogsNAS["username"],"font-size:16px;width:200px")."</td>
	</tr>

	<tr>
		<td class=legend style='font-size:16px'>{password}:</td>
		<td>".Field_password("password-$t",$SquidOldLogsNAS["password"],"font-size:16px;width:200px")."</td>
	</tr>				
	<tr>
		<td colspan=2 align='right'><hr>
				". button("{apply}","Save$t()",18)."</td>
	</tr>
	</table>
	</div>
	<script>
	
	
	var xSave$t= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);}
			UnlockPage();
		}
	
	
		function Save$t(){
			LockPage();
			var XHR = new XHRConnection();
			EnableImportOldSquid=0;
			EnableImportWithSarg=0;
			if(document.getElementById('EnableImportOldSquid-$t').checked){EnableImportOldSquid=1;}
			if(document.getElementById('EnableImportWithSarg-$t').checked){EnableImportWithSarg=1;}
			XHR.appendData('EnableImportOldSquid',EnableImportOldSquid);
			XHR.appendData('EnableImportWithSarg',EnableImportWithSarg);
			XHR.appendData('hostname',document.getElementById('hostname-$t').value);
			XHR.appendData('folder',document.getElementById('folder-$t').value);
			XHR.appendData('username',document.getElementById('username-$t').value);
			XHR.appendData('password',encodeURIComponent(document.getElementById('password-$t').value));
			XHR.sendAndLoad('$page', 'POST',xSave$t);
			
		}
		
		function EnableImportOldSquidCK(){
			document.getElementById('hostname-$t').disabled=true;
			document.getElementById('folder-$t').disabled=true;
			document.getElementById('username-$t').disabled=true;
			document.getElementById('password-$t').disabled=true;
			document.getElementById('EnableImportWithSarg-$t').disabled=true;
			
			
			
			if(document.getElementById('EnableImportOldSquid-$t').checked){
				document.getElementById('hostname-$t').disabled=false;
				document.getElementById('folder-$t').disabled=false;
				document.getElementById('username-$t').disabled=false;
				document.getElementById('password-$t').disabled=false;	
				document.getElementById('EnableImportWithSarg-$t').disabled=false;		
			}
		}
		EnableImportOldSquidCK();
</script>		
	";
echo $tpl->_ENGINE_parse_body($html);
	
}

function delete_all(){
	$q=new mysql_squid_builder();
	if($q->TABLE_EXISTS("accesslogs_import")){
		$q->QUERY_SQL("TRUNCATE TABLE accesslogs_import");
	}
	
}

function save_hostname(){
	$_POST["password"]=url_decode_special_tool($_POST["password"]);
	$sock=new sockets();
	$sock->SET_INFO("EnableImportOldSquid", $_POST["EnableImportOldSquid"]);
	$sock->SET_INFO("EnableImportWithSarg", $_POST["EnableImportWithSarg"]);
	$sock->SaveConfigFile(base64_encode(serialize($_POST)), "SquidOldLogsNAS");
	$sock->getFrameWork("cmd.php?restart-artica-status=yes");
}



function status(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$filename=$tpl->javascript_parse_text("{filename}");
	$size=$tpl->javascript_parse_text("{size}");
	$status=$tpl->javascript_parse_text("{status}");
	$scan=$tpl->javascript_parse_text("{scan_nas_folder}");
	$date=$tpl->javascript_parse_text("{date}");
	$lines=$tpl->javascript_parse_text("{lines}");
	$ask_delete_file=$tpl->javascript_parse_text("{ask_delete_file}");
	$test_nas=$tpl->javascript_parse_text("{test_connection}");
	$import_now=$tpl->javascript_parse_text("{import_now}");
	$delete_all_items=$tpl->javascript_parse_text("{delete_all_items}");
	$buttons="
	buttons : [
	{name: '<b>$scan</b>', bclass: 'Search', onpress : Scan$t},
	{name: '$test_nas', bclass: 'Select', onpress : TestsNas$t},
	{name: '$import_now', bclass: 'Reload', onpress : ImportNow$t},
	{name: '$delete_all_items', bclass: 'Delz', onpress : DeleteAll$t},
	
	],	";	
	
	$html="
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
	<script>
var mem$t='';

function Start$t(){
	$('#table-$t').flexigrid({
	url: '$page?status-list=yes&t=$t',
	dataType: 'json',
	colModel : [
	{display: '$date', name : 'zDate', width : 71, sortable : true, align: 'left'},
	{display: '$filename', name : 'filename', width : 112, sortable : true, align: 'left'},
	{display: '$size', name : 'size', width : 73, sortable : true, align: 'left'},
	{display: '$lines', name : 'lnumbers', width : 73, sortable : true, align: 'left'},
	{display: '$status', name : 'status', width : 183, sortable : false, align: 'left'},
	{display: '%', name : 'percent', width : 45, sortable : false, align: 'left'},
	{display: '', name : 'none3', width : 31, sortable : false, align: 'left'},
	
	],
	$buttons
	
	searchitems : [
		{display: '$filename', name : 'filename'},
	],
	sortname: 'zDate',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '100%',
	height: 350,
	singleSelect: true
	});
}
Start$t();	
function TestsNas$t(){
	Loadjs('$page?test-nas-js=yes');
}

function ImportNow$t(){
	LockPage();
	var XHR = new XHRConnection();
	XHR.appendData('import-now','yes');
	XHR.sendAndLoad('$page', 'POST',xDelete$t);	
}

	function DeleteAll$t(){
		if(confirm('$delete_all_items ?')){
			var XHR = new XHRConnection();
			XHR.appendData('delete-all','yes');
			XHR.sendAndLoad('$page', 'POST',xDelete$t);
		}
		
	}

var xDelete$t= function (obj) {
	UnlockPage();
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	UnlockPage();
	$('#table-$t').flexReload();
	
}

function Delete$t(md5){
	if(!confirm('$ask_delete_file')){return;}
	LockPage();
	mem$t=md5;
	var XHR = new XHRConnection();
	XHR.appendData('delete-file',md5);
	XHR.sendAndLoad('$page', 'POST',xDelete$t);	
	
	
}

function Scan$t(){
	LockPage();
	var XHR = new XHRConnection();
	XHR.appendData('scan-now','yes');
	XHR.sendAndLoad('$page', 'POST',xDelete$t);	

}


</script>
	
	";
	
	echo $html;
	
	
}

function delete_file(){
	$md5=$_POST["delete-file"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM accesslogs_import WHERE zmd5='$md5'");
	if(!$q->ok){echo $q->mysql_error;}
}


function status_list(){
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();	
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$table="accesslogs_import";
	$page=1;
	
	if(!$q->TABLE_EXISTS($table)){
		
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`$table` (
			`zmd5` VARCHAR(128) PRIMARY KEY,
			`filename` varchar(90) NOT NULL,
			`zDate` date NOT NULL,
			`size` BIGINT UNSIGNED NOT NULL,
			`status` smallint(1),
			`percent` SMALLINT(2),
			`lnumbers` BIGINT UNSIGNED NOT NULL,
			 KEY `filename` (`filename`),
			 KEY `zDate` (`zDate`),
			 KEY `percent` (`percent`),
			 KEY `lnumbers` (`lnumbers`),
			 KEY `status` (`status`)
			 ) ENGINE=MYISAM;";
			$q->QUERY_SQL($sql);
	}
	
	$FORCE_FILTER=null;
	
	
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	
	
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show("$q->mysql_error",1);}
		$total = $ligne["TCOUNT"];
	
	}else{
		$total = $q->COUNT_ROWS($table);
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	if($total==0){json_error_show("Perform Scan first...",1);}
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show($q->mysql_error);}
	$status[0]="{waiting}";
	$status[1]="{processing}";
	$status[3]="{analyzed}";
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$c=0;
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="#7A7A7A";
		$id=$ligne["zmd5"];
		$delete=imgtootltip("delete-24.png",null,"Delete$t('$id')");
		$size=FormatBytes($ligne["size"]/1024);
		$lnumbers=FormatNumber($ligne["lnumbers"]);
		if($ligne["status"]==3){$color="#009B30";}
		
		if($ligne["status"]<3){
			if($ligne["status"]>0){
				if($ligne["percent"]>0){
					$color="#870000";
				}
			}
		}
		
		$data['rows'][] = array(
			'id' => $id,
			'cell' => array(
					"<span style='font-size:13px;color:$color'>{$ligne["zDate"]}</span>",
					"<span style='font-size:13px;color:$color'>{$ligne["filename"]}</span>",
					"<span style='font-size:13px;color:$color'>{$size}</span>",
					"<span style='font-size:13px;color:$color'>{$lnumbers}</span>",
					"<span style='font-size:13px;color:$color'>".$tpl->_ENGINE_parse_body($status[$ligne["status"]])."</span>",
					"<span style='font-size:13px;color:$color'>{$ligne["percent"]}%</span>",
					"<span style='font-size:13px;color:$color'>{$delete}</span>")
			);
	}
	echo json_encode($data);
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}
function scan_now(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?old-logs-scan-nas=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{old-logs-scan-nas-explain}",1);
	
}

function test_nas_popup(){
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->getFrameWork("services.php?squidlogs-oldlogs-test-nas=yes")));
	echo "<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:98%;height:446px;border:5px solid #8E8E8E;
	overflow:auto;font-size:11px' id='textToParseCats-$t'>".@implode("\n", $datas)."</textarea>";
}
function import_now(){
$sock=new sockets();
	$sock->getFrameWork("squid.php?old-logs-import-nas=yes");
	$tpl=new templates();
	sleep(2);
	echo $tpl->javascript_parse_text("{old-logs-scan-nas-explain}",1);
}

function events(){
	
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->getFrameWork("services.php?squidlogs-oldlogs-logs-nas=yes")));
	echo "<textarea style='margin-top:5px;
	font-weight:bold;width:98%;height:446px;border:5px solid #8E8E8E;
	overflow:auto;font-size:11px' id='textToParseCats-$t'>".@implode("\n", $datas)."</textarea>";	
	
}
