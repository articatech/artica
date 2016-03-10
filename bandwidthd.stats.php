<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.artica.inc');
include_once('ressources/class.ini.inc');
include_once('ressources/class.squid.inc');
$users=new usersMenus();
if(!$users->AsWebStatisticsAdministrator){echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die();}

if(isset($_GET["list"])){table_list();exit;}
if(isset($_GET["ipaddr-js"])){ipaddr_js();exit;}
if(isset($_GET["ipaddr-popup"])){ipaddr_popup();exit;}
if(isset($_GET["TotalR"])){TotalR();exit;}
if(isset($_GET["TotalS"])){TotalS();exit;}
table();

function ipaddr_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$ipaddr=$_GET["IPADDR"];
	echo "YahooWin('1400','$page?ipaddr-popup=yes&IPADDR=".urlencode($ipaddr)."','$ipaddr');";
}



function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$traffic_sent=$tpl->_ENGINE_parse_body("{traffic_sent}");
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$familysite=$tpl->_ENGINE_parse_body("{familysite}");
	$title=$tpl->javascript_parse_text("{APP_BANDWIDTHD_TITLE} {today}");
	$delete_all=$tpl->javascript_parse_text("{delete_all}");
	$traffic_received=$tpl->javascript_parse_text("{traffic_received}");
	$date=$tpl->javascript_parse_text("{date}");
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$delete_all</strong>', bclass: 'Delz', onpress : DeleteAll$t},
	],";
	
	$buttons=null;
	
	$html="
	<table class='BANDWIDTH_STATISTICS' style='display: none' id='BANDWIDTH_STATISTICS' style='width:99%'></table>
	<script>
	$(document).ready(function(){
	$('#BANDWIDTH_STATISTICS').flexigrid({
	url: '$page?list=yes&report_type={$_GET["report_type"]}&t={$_GET["t"]}',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:22px>$ipaddr</span>', name : 'ipaddr', width :867, sortable : true, align: 'left'},
	{display: '<span style=font-size:22px>$traffic_received</span>', name : 'TotalR', width :249, sortable : true, align: 'right'},
	{display: '<span style=font-size:22px>$traffic_sent</span>', name : 'TotalS', width :249, sortable : true, align: 'right'},
	],
	$buttons
	
	searchitems : [
	{display: '$ipaddr', name : 'ipaddr'},
	],
	
	sortname: 'TotalR',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:22px>$title</span>',
	useRp: true,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200],
	rp:50,
	
	});
	});
	
	var xDeleteAll$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#BROWSE_STATISTICS_CACHES2').flexReload();
	}
	
	function DeleteAll$t(){
	if(!confirm('$delete_all ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('DeleteAll', 'yes');
	XHR.sendAndLoad('$page', 'POST',xDeleteAll$t);
	}
	
	</script>
	
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
	}
function table_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$table="bandwidthd_today";
	
	$page=1;
	
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	$searchstring=string_to_flexquery();
	$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$total = $ligne["TCOUNT"];
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	
		if(!$q->ok){json_error_show($q->mysql_error);}
		if(mysql_num_rows($results)==0){json_error_show("no data $sql" );}
	
		$data = array();
		$data['page'] = $page;
		$data['total'] = $total;
		$data['rows'] = array();
		
		
		$all=$tpl->javascript_parse_text("{all}");
	
	
		while ($ligne = mysql_fetch_assoc($results)) {
			$zmd5=$ligne["zmd5"];
			$ipaddr=$ligne["ipaddr"];
			
			$hostname=null;
			$hostnametext=null;
			
			if($ipaddr=="0.0.0.0"){$ipaddr=$all;}else{
				$hostname=gethostbyaddr($ipaddr);
				if($hostname<>$ipaddr){$hostnametext="($hostname)";}
			}
			$TotalR=$ligne["TotalR"];
			$TotalS=$ligne["TotalS"];
			$ahref=null;
			if($TotalR>1024){
				$TotalR=FormatBytes($TotalR/1024);
			}else{
				$TotalR="{$TotalR} Bytes";
			}
			if($TotalS>1024){
				$TotalS=FormatBytes($TotalS/1024);
			}else{
				$TotalS="{$TotalS} Bytes";
			}
			
			$ahref="
			<a href=\"javascript:Loadjs('$MyPage?ipaddr-js=yes&IPADDR=". urlencode($ligne["ipaddr"])."');\"
			style='font-size:18px;text-decoration:underline'>";
	
			$data['rows'][] = array(
					'id' => $zmd5,'cell' => array(
					"<span style='font-size:18px'>$ahref{$ipaddr}</a> $hostnametext</span>",
					"<span style='font-size:18px'>{$TotalR}</a></span>",
					"<span style='font-size:18px'>{$TotalS}</a></span>",
					)
						
			);
				
		}
	
	
		echo json_encode($data);
	
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}
	

function ipaddr_popup(){
	$table_name="bandwidthd_".strtotime(date("Y-m-d 00:00:00"));
	$ipaddr=$_GET["IPADDR"];
	$ipaddrenc=urlencode($ipaddr);
	$page=CurrentPageName();
	$time=time();
	$q=new mysql_squid_builder();
	$zmd5=$_GET["zmd5"];
	$t=time();
	$results=$q->QUERY_SQL("SELECT * FROM $table_name WHERE `ipaddr`='$ipaddr'");
	while ($ligne = mysql_fetch_assoc($results)) {
		$date=strtotime($ligne["zDate"]);
		$xdate=date("H:i",$date);
		$TotalR=$ligne["TotalR"];
		$TotalS=$ligne["TotalS"];
		$TotalR=$TotalR/1024;
		$TotalR=round($TotalR/1024,2);
		
		$TotalS=$TotalS/1024;
		$TotalS=round($TotalS/1024,2);
		
		
		$xdata[]=$xdate;
		$ydata[]=$TotalR;
		$ydata2[]=$TotalS;
		
		
	}

	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/xTotalR.db", serialize($xdata));
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/TotalR.db", serialize($ydata));
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/TotalS.db", serialize($ydata2));
	
	
	echo "
	<div id='TotalR-$t' style='width:1350;height:450'>
		<center><img src='img/wait_verybig_mini_red.gif'></center>
	</div>
	<div id='TotalS-$t' style='width:1350;height:450'>
		<center><img src='img/wait_verybig_mini_red.gif'></center>
	</div>		
	<script>
		Loadjs('$page?TotalR=yes&div=TotalR-$t&IPADDR=$ipaddrenc&t=$t');
		
	
	</script>";
}

function TotalR(){
	
	

	
	$ipaddr=$_GET["IPADDR"];
	$ipaddrenc=urlencode($ipaddr);
	$title="$ipaddr - {traffic_received} (MB)";
	$timetext=$_GET["interval"];
	$highcharts=new highcharts();
	$highcharts->container=$_GET["div"];
	$highcharts->xAxis=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/xTotalR.db"));
	$highcharts->Title=$title;

	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="MB";
	$highcharts->xAxis_labels=true;
	
	$highcharts->LegendSuffix="MB";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("{size}"=>unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/TotalR.db")));
	echo $highcharts->BuildChart();
	
	$page=CurrentPageName();
	echo "\nLoadjs('$page?TotalS=yes&div=TotalS-{$_GET["t"]}&IPADDR=$ipaddrenc');";
	
}
function TotalS(){
	$ipaddr=$_GET["IPADDR"];
	$title="$ipaddr - {traffic_sent} (MB)";
	$timetext=$_GET["interval"];
	$highcharts=new highcharts();
	$highcharts->container=$_GET["div"];
	$highcharts->xAxis=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/xTotalR.db"));
	$highcharts->Title=$title;

	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="MB";
	$highcharts->xAxis_labels=true;

	$highcharts->LegendSuffix="MB";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("{size}"=>unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/TotalS.db")));
	echo $highcharts->BuildChart();
}
