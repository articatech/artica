<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");

include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.artica.inc');
include_once('ressources/class.ini.inc');
include_once('ressources/class.squid.inc');
include(dirname(__FILE__)."/ressources/class.influx.inc");


	$user=new usersMenus();
	if(!$user->AsWebStatisticsAdministrator){
		$tpl=new templates();
		echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		exit;
	}
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}	
	if(isset($_GET["build-graph-js"])){build_graph_js();exit;}
	if(isset($_GET["build-graph"])){build_graph();exit;}
	
	if(isset($_GET["build-webfiltering"])){build_webfiltering();exit;}
	if(isset($_GET["webfiltering-1"])){build_webfiltering_chart1();exit;}
	if(isset($_GET["build-webfiltering-table1"])){build_webfiltering_table1();exit;}
	
	if(isset($_GET["webfiltering-3"])){build_webfiltering_chart2();exit;}
	if(isset($_GET["build-webfiltering-table2"])){build_webfiltering_table2();exit;}
	if(isset($_GET["identity-search"])){identity_search();exit;}

	
	
	
	if(isset($_GET["graph1"])){graph1();exit;}
	if(isset($_GET["graph2"])){graph2();exit;}
	if(isset($_GET["graph3"])){graph3();exit;}
	if(isset($_GET["table1"])){table1();exit;}
	if(isset($_GET["table2"])){table2();exit;}
	if(isset($_GET["build-nav"])){build_nav();exit;}
	if(isset($_GET["stats-dahsboard-title"])){stats_dahsboard_title();exit;}
	if(isset($_GET["build-chronology"])){build_chronology_table();exit;}
	if(isset($_GET["build-identity"])){build_identity();exit;}
	
	if(isset($_GET["build-websites"])){build_websites();exit;}
	if(isset($_GET["websites-search"])){build_websites_search();exit;}
	
	
	if(isset($_GET["chronos-table"])){build_chronology_table();exit;}
	if(isset($_GET["chronos-search"])){build_chronology_search();exit;}
	
	
	
build_query_js();


function build_nav(){
	$zmd5=$_GET["zmd5"];
	$page=CurrentPageName();
	$t=time();
	echo "
	<div id='bxslider-member-top' style='background-color: black;
    height: 36px;
    margin-left: -7px;
    margin-right: 0;
    margin-top: -6px;
    padding-right: 10px;
    padding-top: 8px;
    text-align: right;
    width: 100%'>&nbsp;</div>
	<div id='MAIN-STATISTICS-$t' style='background-color:white;width:1180px;height:1200px;margin-top:10px'></div>
 
	
	
	
	<script>
	
	function GotToUserGraph$t(){
		LoadAjaxRound('MAIN-STATISTICS-$t','$page?build-graph=yes&zmd5=$zmd5');
	}
	function GoToChronology$t(){
		LoadAjaxRound('MAIN-STATISTICS-$t','$page?build-chronology=yes&zmd5=$zmd5');
	}
	
	function GoToIdentity$t(){
		LoadAjaxRound('MAIN-STATISTICS-$t','$page?build-identity=yes&zmd5=$zmd5');
	}
	
	function GoToWebsites$t(){
		LoadAjaxRound('MAIN-STATISTICS-$t','$page?build-websites=yes&zmd5=$zmd5');
	}

	function GoToWebfiltering$t(){
		LoadAjaxRound('MAIN-STATISTICS-$t','$page?build-webfiltering=yes&zmd5=$zmd5');
	}	
	
	UnlockPage();
	LoadAjaxTiny('bxslider-member-top','$page?stats-dahsboard-title=yes&t=$t');
	GotToUserGraph$t();
	</script>";
		
}

function stats_dahsboard_title(){
	$t=$_GET["t"];
	$tpl=new templates();
	$style="style='font-size:20px;color:white;text-decoration:underline'";
	$tr[]="<a href=\"javascript:GotToUserGraph$t();\" $style>{category}</a>";
	
	$tr[]="<a href=\"javascript:GoToChronology$t();\" $style>{chronology}</a>";
	$tr[]="<a href=\"javascript:GoToWebsites$t();\" $style>{websites}</a>";
	$tr[]="<a href=\"javascript:GoToIdentity$t();\" $style>{users}</a>";
	
	echo $tpl->_ENGINE_parse_body(@implode("&nbsp;|&nbsp;", $tr));
	
}


function build_graph_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$q=new mysql_squid_builder();
	$md5=$_GET["zmd5"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT params FROM reports_cache WHERE `zmd5`='$md5'"));
	$params=unserialize($ligne["params"]);
	$from=$tpl->time_to_date($params["FROM"],true);
	$to=$tpl->time_to_date($params["TO"],true);
	$interval=$params["INTERVAL"];
	if($interval==0){$interval="1h";}
	$user=$params["USER"];
	$user_data=$params["SEARCH"];
	
	$title=$tpl->javascript_parse_text("{category}:$user_data From $from to $to $interval");
	echo "YahooWin2(1240,'$page?build-nav=yes&zmd5=$md5','$title')";
	
}



function build_query_js(){
	header("content-type: application/x-javascript");
	squid_stats_default_values();
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	
	if(isset($_GET["from-zmd5"])){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT params FROM reports_cache WHERE `zmd5`='{$_GET["from-zmd5"]}'"));
		$params=unserialize($ligne["params"]);
		$from=$params["FROM"];
		$to=$params["TO"];
		$interval=$params["INTERVAL"];
		if($interval==0){$interval="1h";}
		$USER_FIELD=$params["USER"];
		$USER_DATA=$_GET["USER_DATA"];
	}else{
		$zmd5=$_GET["zmd5"];
		$t=time();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT params FROM reports_cache WHERE `zmd5`='$zmd5'"));
		if(!$q->ok){
			echo "alert('".$tpl->javascript_parse_text("{$q->mysql_error}")."');";
			return;
		}
		
		$params=unserialize($ligne["params"]);
		$from=$params["FROM"];
		$to=$params["TO"];
		$interval=$params["INTERVAL"];
		if($interval==0){$interval="1h";}
		$USER_FIELD=$params["USER"];
		$USER_DATA=$_GET["USER_DATA"];
	}
	
	$nextFunction="Loadjs('$page?build-graph-js=yes&zmd5=$zmd5');";
	$nextFunction_encoded=urlencode(base64_encode($nextFunction));
	$q=new mysql_squid_builder();
	$q->CheckReportTable();
	
	
	$timetext1=$tpl->time_to_date(strtotime($from),true);
	$timetext2=$tpl->time_to_date(strtotime($to),true);

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ID,builded FROM reports_cache WHERE `zmd5`='$zmd5'"));
	if(intval($ligne["ID"])==0){
		$md5=md5("CATEGORY_UNIQ:$from$to$interval Category:$USER_DATA");
		$nextFunction="Loadjs('$page?build-graph-js=yes&zmd5=$md5');";
		$nextFunction_encoded=urlencode(base64_encode($nextFunction));
		$array["FROM"]=$from;
		$array["TO"]=$to;
		$array["INTERVAL"]=$interval;
		$array["USER"]=$USER_FIELD;
		$array["SEARCH"]=$USER_DATA;
		$serialize=mysql_escape_string2(serialize($array));
		$title="{category}: $timetext1 -$timetext2 - {$USER_DATA}";
		$sql="INSERT IGNORE INTO `reports_cache` (`zmd5`,`title`,`report_type`,`zDate`,`params`) VALUES
		('$md5','$title','CATEGORY_UNIQ',NOW(),'$serialize')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo "alert('". $tpl->javascript_parse_text($q->mysql_errror)."')";return;}
		echo "Loadjs('squid.statistics.progress.php?zmd5=$md5&NextFunction=$nextFunction_encoded')";
		return;
	}

	if(intval($ligne["builded"]==0)){
	echo "
		function Start$t(){
		Loadjs('squid.statistics.progress.php?zmd5=$md5&NextFunction=$nextFunction_encoded&t=$t');
	}

	LockPage();
	setTimeout('Start$t()',800);
	";
	return;
	}
	
	echo $nextFunction;

}

function build_graph(){
	$zmd5=$_GET["zmd5"];
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$q=new mysql_squid_builder();
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT params FROM reports_cache WHERE `zmd5`='$zmd5'"));
	$params=unserialize($ligne["params"]);
	
	$from=$tpl->time_to_date($params["FROM"],true);
	$to=$tpl->time_to_date($params["TO"],true);
	$interval=$params["INTERVAL"];
	if($interval==0){$interval="1h";}
	$user=$params["USER"];
	$user_data=$params["SEARCH"];
	$title="{user_report}: {from} $from {to} $to $interval {member}:$user/$user_data";
	
	$html="
	<center style='font-size:20px;margin-bottom:20px'>$title</center>
	<div style='width:1150px;height:550px;margin-bottom:10px' id='graph-$t'></div>
	
	
	<table style='width:100%'>
	<tr>
		<td width=90%>
			<div style='width:800px;height:550px' id='graph2-$t'></div>
		</td>
		<td style='width:5%;vertical-align:top'>
			<div id='table1-$t'></div>
		</td>
	</tr>
	<tr>
	<td colspan=2><p>&nbsp;</p></td>
	</tr>

	
	<tr>
		<td width=90%>
			<div style='width:800px;height:550px' id='graph4-$t'></div>
		</td>
		<td style='width:5%;vertical-align:top'>
			<div  id='table4-$t'></div>
		</td>
	</tr>	
	
	
	</table>
	<script>
		Loadjs('$page?graph1=yes&container=graph-$t&zmd5=$zmd5&t=$t');
	</script>
	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function build_identity(){
	$page=CurrentPageName();
	$tpl=new templates();
	$zmd5=$_GET["zmd5"];
	$tt=time();
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$zDate=$tpl->javascript_parse_text("{zDate}");
	$size=$tpl->javascript_parse_text("{size}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$chronology=$tpl->javascript_parse_text("{identity}");
	
	$html="
	<table class='TABLEZ-$zmd5' style='display: none' id='TABLEZ-$zmd5' style='width:100%'></table>
	<script>
	function Start$tt(){
	$('#TABLEZ-$zmd5').flexigrid({
	url: '$page?identity-search=yes&zmd5=$zmd5',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:18px>MAC</span>', name : 'mac', width : 514, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>USERID</span>', name : 'userid', width : 151, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$ipaddr</span>', name : 'ipaddr', width : 151, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$size</span>', name : 'size', width : 151, sortable : true, align: 'left'},
	
	],
	$buttons
	searchitems : [
	{display: 'mac', name : 'mac'},
	{display: 'userid', name : 'userid'},
	{display: '$ipaddr', name : 'ipaddr'},
	],
	sortname: 'size',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:20px>$chronology</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 600,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});
	}
	Start$tt();
	</script>";
	echo $html;
	
}

function identity_search(){
	$page=1;
	$zmd5=$_GET["zmd5"];
	$q=new postgres_sql();
	$table="{$zmd5}report";
	$MyPage=CurrentPageName();
	
	
	$table="(SELECT SUM(size) as size,ipaddr,mac,userid FROM \"$table\" GROUP by ipaddr,mac,userid) as t";
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexPostGresquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as tcount FROM $table WHERE $searchstring";
		$ligne=pg_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show($q->mysql_error,0);}
		$total = $ligne["tcount"];
	
	}else{
		$sql="SELECT COUNT(*) as tcount FROM \"$table\"";
		$ligne=pg_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show($q->mysql_error,0);}
		$total = $ligne["tcount"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $rp OFFSET $pageStart";
	
	$sql="SELECT *  FROM $table WHERE $searchstring $ORDER $limitSql";
	
	if(isset($_GET["verbose"])){echo "<hr><code>$sql</code></hr>";}
	$results = $q->QUERY_SQL($sql);
	
	if(!$q->ok){json_error_show($q->mysql_error,1);}
	
	if(pg_num_rows($results)==0){
		json_error_show("$table no data",1);
	}
	
	
	
	$fontsize="26px";
	$data = array();
	$data['page'] = 1;
	$data['total'] = $total;
	$data['rows'] = array();
	
	
	
	
	while($ligne=@pg_fetch_assoc($results)){
		
		$MAC=$ligne["mac"];
		$userid=$ligne["userid"];
		$ipaddr=$ligne["ipaddr"];
		$size=FormatBytes($ligne["size"]/1024);
			
		$data['rows'][] = array(
				'id' => md5(serialize($ligne)),
				'cell' => array(
						"<span style='font-size:16px'>$MAC</a></span>",
						"<span style='font-size:16px'>$userid</span>",
						"<span style='font-size:16px'>$ipaddr</span>",
						"<span style='font-size:16px'>$size</span>",
	
				)
		);
	}
	
	
	echo json_encode($data);	
	
}

function build_websites(){
	$page=CurrentPageName();
	$tpl=new templates();
	$zmd5=$_GET["zmd5"];
	$tt=time();
	$SITE=$tpl->javascript_parse_text("{website}");
	$zDate=$tpl->javascript_parse_text("{zDate}");
	$size=$tpl->javascript_parse_text("{size}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$chronology=$tpl->javascript_parse_text("{websites}");
		
	$html="
	<table class='TABLEW-$zmd5' style='display: none' id='TABLEW-$zmd5' style='width:100%'></table>
	<script>
	function Start$tt(){
	$('#TABLEW-$zmd5').flexigrid({
	url: '$page?websites-search=yes&zmd5=$zmd5',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:18px>$SITE</span>', name : 'familysite', width : 514, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$hits</span>', name : 'rqs', width : 151, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$size</span>', name : 'size', width : 151, sortable : true, align: 'left'},
	
	],
	$buttons
	searchitems : [
	{display: '$SITE', name : 'familysite'},
	],
	sortname: 'size',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:20px>$chronology</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 600,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});
	}
Start$tt();
</script>";
	echo $html;
}


function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}	



function build_webfiltering(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$zmd5=$_GET["zmd5"];
	$page=CurrentPageName();
	if($zmd5==null){echo "alert('no key sended');UnlockPage();";die();}
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `values` FROM reports_cache WHERE `zmd5`='$zmd5'"));
	$values=$ligne["values"];
	if(strlen($values)==0){echo "alert('NO data...{$ligne["values"]}');";$q->QUERY_SQL("DELETE FROM reports_cache WHERE `zmd5`='$zmd5'");return;}
	$MAIN=unserialize(base64_decode($values));	
	$webfiltering=$tpl->_ENGINE_parse_body("{webfiltering}");
	
	
	
	if(count($MAIN["WEBFILTERING_TOPCATZ"])==0){
		echo FATAL_ERROR_SHOW_128("{statistics_nowebfiltering_data}");
		return;
	}
	
	echo "
	<div style='font-size:30px;margin-bottom:20px'>$webfiltering</div>	

	<table style='width:100%'>
	<tr>
	<td valign='top' style='width:800px'>
		<div id='webfiltering-1-$zmd5' style='width:800px;height:550px'></div>
	</td>
	<td valign='top' style='width:380px'>
		<div id='webfiltering-2-$zmd5'></div>
	</td>
	</tr>
	<tr>
	<td valign='top' style='width:800px'>
		<div id='webfiltering-3-$zmd5' style='width:800px;height:550px'></div>
	</td>
	<td valign='top' style='width:380px'>
		<div id='webfiltering-4-$zmd5'></div>
	</td>
	</tr>	
	</table>
	
	<script>Loadjs('$page?webfiltering-1=yes&zmd5=$zmd5&t={$_GET["t"]}')</script>
	";
	
	
	
	
	
	
	
	
}




function graph1(){

	$q=new postgres_sql();
	$zmd5=$_GET["zmd5"];
	if($zmd5==null){echo "alert('no key sended');UnlockPage();";die();}
	$page=CurrentPageName();
	$time=time();
	$table="{$zmd5}report";

	$q=new postgres_sql();
	$sql="SELECT SUM(size) as size,zdate FROM \"$table\" GROUP BY zdate ORDER BY zdate";
	
	if($GLOBALS["VERBOSE"]){echo $sql."<br>\n";}
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){
		if($GLOBALS["VERBOSE"]){echo $q->mysql_error."<br>\n";}
	}
	
	
	while($ligne=@pg_fetch_assoc($results)){
		$size=$ligne["size"];
		$size=$size/1024;
		$xdata[]=$ligne["zdate"];
		$ydata[]=$size;
	}
	
	
	

	

	$title="{downloaded_flow} (MB)";
	$timetext=$_GET["interval"];
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title=$title;
	//$highcharts->subtitle="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.rtt.php')\" style='font-size:16px;text-decoration:underline'>{realtime_flow}</a>";
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="MB";
	$highcharts->xAxis_labels=true;

	$highcharts->LegendSuffix="MB";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();
	echo "\nLockPage();\nLoadjs('$page?graph2=yes&zmd5=$zmd5&t={$_GET["t"]}&container=graph2-{$_GET["t"]}')\n";
	

}
function graph2(){
	$page=CurrentPageName();
	$tpl=new templates();

	$q=new mysql_squid_builder();
	$zmd5=$_GET["zmd5"];
	
	
	$table="{$zmd5}report";
	
	$q=new postgres_sql();
	$sql="SELECT SUM(size) as size,familysite FROM \"$table\" GROUP BY familysite ORDER BY size DESC LIMIT 10";
	$results=$q->QUERY_SQL($sql);
	
	if($GLOBALS["VERBOSE"]){echo $sql."<br>\n";}
	
	while($ligne=@pg_fetch_assoc($results)){
		$size=$ligne["size"];
		$size=$size/1024;
		$size=round($size/1024);
		$familysite=$ligne["familysite"];
		$PieData[$familysite]=$size;
	}
	
	
	

	
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{websites}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{websites}/{size} (MB)");
	echo $highcharts->BuildChart();
	echo "LoadAjax('table1-{$_GET["t"]}','$page?table1=yes&zmd5=$zmd5&t={$_GET["t"]}');\n";


}
function graph3(){
	$page=CurrentPageName();
	$tpl=new templates();

	$q=new mysql_squid_builder();
	$zmd5=$_GET["zmd5"];


	$table="{$zmd5}report";

	$q=new postgres_sql();
	$sql="SELECT SUM(rqs) as size,familysite FROM \"$table\" GROUP BY familysite ORDER BY size DESC LIMIT 10";
	$results=$q->QUERY_SQL($sql);

	
	if(!$q->ok){echo "alert('".$q->mysql_error."');";return;}
	
	if($GLOBALS["VERBOSE"]){echo $sql."<br>\n";}

	while($ligne=@pg_fetch_assoc($results)){
		$size=$ligne["size"];
		$familysite=$ligne["familysite"];
		$PieData[$familysite]=$size;
	}





	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{websites}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{websites}/{hits}");
	echo $highcharts->BuildChart();
	echo "LoadAjax('table4-{$_GET["t"]}','$page?table2=yes&zmd5=$zmd5&t={$_GET["t"]}');\n";


}
function build_webfiltering_chart1(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$q=new mysql_squid_builder();
	$zmd5=$_GET["zmd5"];
	if($zmd5==null){echo "alert('no key sended');UnlockPage();";die();}
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `values` FROM reports_cache WHERE `zmd5`='$zmd5'"));
	$values=$ligne["values"];
	
	if(strlen($values)==0){echo "alert('NO data...{$ligne["values"]}');UnlockPage();";$q->QUERY_SQL("DELETE FROM reports_cache WHERE `zmd5`='$zmd5'");return;}
	$MAIN=unserialize(base64_decode($values));
	
	$PieData=$MAIN["WEBFILTERING_TOPCATZ"];
	$highcharts=new highcharts();
	$highcharts->container="webfiltering-1-$zmd5";
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{websites}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("TOP {category}/{hits}");
	echo $highcharts->BuildChart();
	
	echo "\nLoadAjaxRound('webfiltering-2-$zmd5','$page?build-webfiltering-table1=yes&zmd5=$zmd5');\n";
	
	
}

function build_webfiltering_chart2(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$q=new mysql_squid_builder();
	$zmd5=$_GET["zmd5"];
	if($zmd5==null){echo "alert('no key sended');UnlockPage();";die();}
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `values` FROM reports_cache WHERE `zmd5`='$zmd5'"));
	$values=$ligne["values"];
	
	if(strlen($values)==0){echo "alert('NO data...{$ligne["values"]}');UnlockPage();";$q->QUERY_SQL("DELETE FROM reports_cache WHERE `zmd5`='$zmd5'");return;}
	$MAIN=unserialize(base64_decode($values));
	
	$PieData=$MAIN["WEBFILTERING_TOPSITES"];
	$highcharts=new highcharts();
	$highcharts->container="webfiltering-3-$zmd5";
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{websites}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("TOP {websites}/{hits}");
	echo $highcharts->BuildChart();	
	echo "\nLoadAjaxRound('webfiltering-4-$zmd5','$page?build-webfiltering-table2=yes&zmd5=$zmd5');\n";
	
}

function build_webfiltering_table1(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$q=new mysql_squid_builder();
	$zmd5=$_GET["zmd5"];
	if($zmd5==null){echo "alert('no key sended');UnlockPage();";die();}
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `values` FROM reports_cache WHERE `zmd5`='$zmd5'"));
	$values=$ligne["values"];
	
	if(strlen($values)==0){echo "alert('NO data...{$ligne["values"]}');UnlockPage();";$q->QUERY_SQL("DELETE FROM reports_cache WHERE `zmd5`='$zmd5'");return;}
	$MAIN=unserialize(base64_decode($values));
	
	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]=$tpl->_ENGINE_parse_body("<th style='font-size:22px'>{categories}</th>");
	$html[]=$tpl->_ENGINE_parse_body("<th style='font-size:22px'>{hits}</th>");
	$html[]="</tr>";
	
	
	while (list ($site, $size) = each ($MAIN["WEBFILTERING_TOPCATZ"]) ){
		$size=FormatNumber($size);
		$html[]="<tr><td style='font-size:22px;padding:8px'>$site</td>
		<td style='font-size:22px' align='right'>$size</td></tr>";
	}
	
	$html[]="</table>";
	$html[]="<script>";
	$html[]="Loadjs('$page?webfiltering-3=yes&zmd5=$zmd5&t={$_GET["t"]}');";
	$html[]="</script>";
	echo @implode("", $html);
	
	
}
function build_webfiltering_table2(){
	$page=CurrentPageName();
	$tpl=new templates();

	$q=new mysql_squid_builder();
	$zmd5=$_GET["zmd5"];
	if($zmd5==null){echo "alert('no key sended');UnlockPage();";die();}
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `values` FROM reports_cache WHERE `zmd5`='$zmd5'"));
	$values=$ligne["values"];

	if(strlen($values)==0){echo "alert('NO data...{$ligne["values"]}');UnlockPage();";$q->QUERY_SQL("DELETE FROM reports_cache WHERE `zmd5`='$zmd5'");return;}
	$MAIN=unserialize(base64_decode($values));

	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]=$tpl->_ENGINE_parse_body("<th style='font-size:22px'>{websites}</th>");
	$html[]=$tpl->_ENGINE_parse_body("<th style='font-size:22px'>{hits}</th>");
	$html[]="</tr>";


	while (list ($site, $size) = each ($MAIN["WEBFILTERING_TOPSITES"]) ){
		$size=FormatNumber($size);
		$html[]="<tr><td style='font-size:22px;padding:8px'>$site</td>
		<td style='font-size:22px' align='right'>$size</td></tr>";
	}

	$html[]="</table>";
	$html[]="<script>";
	
	$html[]="</script>";
	echo @implode("", $html);


}
function table1(){
	$page=CurrentPageName();
	$q=new postgres_sql();
	$zmd5=$_GET["zmd5"];
	if($zmd5==null){echo "alert('no key sended');UnlockPage();";die();}
	
	$table="{$zmd5}report";
	
	$results=$q->QUERY_SQL("SELECT SUM(size) as size,familysite FROM \"$table\" GROUP BY familysite ORDER BY size DESC LIMIT 15");
	$html[]="<table style='width:100%'>";
	while($ligne=@pg_fetch_assoc($results)){
		$site=$ligne["familysite"];
		$size=FormatBytes($ligne["size"]/1024);
		$html[]="<tr><td style='font-size:18px;padding:8px'>$site</td>
		<td style='font-size:18px' nowrap>$size</td></tr>";
	}

	$html[]="</table>";
	$html[]="<script>";
	$html[]="\nLockPage();\nLoadjs('$page?graph3=yes&zmd5=$zmd5&t={$_GET["t"]}&container=graph4-{$_GET["t"]}')\n";
	$html[]="</script>";
	echo @implode("", $html);
}
function table2(){
	$page=CurrentPageName();
	$q=new postgres_sql();
	$zmd5=$_GET["zmd5"];
	if($zmd5==null){echo "alert('no key sended');UnlockPage();";die();}

	$table="{$zmd5}report";

	$results=$q->QUERY_SQL("SELECT SUM(rqs) as size,familysite FROM \"$table\" GROUP BY familysite ORDER BY size DESC LIMIT 15");
	$html[]="<table style='width:100%'>";
	while($ligne=@pg_fetch_assoc($results)){
		$site=$ligne["familysite"];
		$size=FormatNumber($ligne["size"]);
		$html[]="<tr><td style='font-size:18px;padding:8px'>$site</td>
		<td style='font-size:18px' nowrap>$size</td></tr>";
	}

	$html[]="</table>";
	$html[]="<script>";
	//$html[]="\nLockPage();\nLoadjs('$page?graph3=yes&zmd5=$zmd5&t={$_GET["t"]}&container=graph4-{$_GET["t"]}')\n";
	$html[]="</script>";
	echo @implode("", $html);
}
function build_chronology_table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$zmd5=$_GET["zmd5"];
	$tt=time();
	$SITE=$tpl->javascript_parse_text("{website}");
	$zDate=$tpl->javascript_parse_text("{zDate}");
	$size=$tpl->javascript_parse_text("{size}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$chronology=$tpl->javascript_parse_text("{chronology}");
		
	$html="
	<table class='TABLE-$zmd5' style='display: none' id='TABLE-$zmd5' style='width:100%'></table>
	<script>
	function Start$tt(){
	$('#TABLE-$zmd5').flexigrid({
	url: '$page?chronos-search=yes&zmd5=$zmd5',
	dataType: 'json',
	colModel : [
	
	{display: '<span style=font-size:18px>$zDate</span>', name : 'zdate', width :175, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$SITE</span>', name : 'familysite', width : 514, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$hits</span>', name : 'rqs', width : 151, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$size</span>', name : 'size', width : 151, sortable : true, align: 'left'},
	
	],
	$buttons
	searchitems : [
	{display: '$SITE', name : 'familysite'},
	],
	sortname: 'zDate',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:20px>$chronology</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 600,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});
	}
Start$tt();
</script>";
	echo $html;
}

function build_websites_search(){
	$page=1;
	$zmd5=$_GET["zmd5"];
	$q=new postgres_sql();
	$table="{$zmd5}report";
	$MyPage=CurrentPageName();
	
	
	$table="(SELECT SUM(size) as size, SUM(rqs) as rqs, familysite FROM \"$table\" GROUP by familysite ORDER by size desc) as t";
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexPostGresquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as tcount FROM $table WHERE $searchstring";
		$ligne=pg_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show($q->mysql_error,0);}
		$total = $ligne["tcount"];
	
	}else{
		$sql="SELECT COUNT(*) as tcount FROM \"$table\"";
		$ligne=pg_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show($q->mysql_error,0);}
		$total = $ligne["tcount"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $rp OFFSET $pageStart";
	
	$sql="SELECT *  FROM $table WHERE $searchstring $ORDER $limitSql";
	
	if(isset($_GET["verbose"])){echo "<hr><code>$sql</code></hr>";}
	$results = $q->QUERY_SQL($sql);
	
	if(!$q->ok){json_error_show($q->mysql_error,1);}
	
	if(pg_num_rows($results)==0){
		json_error_show("$table no data",1);
	}
	
	
	
	$fontsize="26px";
	$data = array();
	$data['page'] = 1;
	$data['total'] = $total;
	$data['rows'] = array();
	
	
	
	
	while($ligne=@pg_fetch_assoc($results)){
		$zDate=$ligne["zdate"];
		$BYTES=$ligne["size"];
		$RQS=$ligne["rqs"];
		$SITE=$ligne["familysite"];
		$RQS=FormatNumber($RQS);
		$BYTES=FormatBytes($BYTES/1024);
		$zDate=str_replace("00:00:00", "", $zDate);
			
		$data['rows'][] = array(
				'id' => md5(serialize($ligne)),
				'cell' => array(
						"<span style='font-size:16px'>$SITE</a></span>",
						"<span style='font-size:16px'>$RQS</span>",
						"<span style='font-size:16px'>$BYTES</span>",
	
				)
		);
	}
	
	
	echo json_encode($data);
	
	}

function build_chronology_search(){
	$page=1;
	$zmd5=$_GET["zmd5"];
	$q=new postgres_sql();
	$table="{$zmd5}report";
	$MyPage=CurrentPageName();

	

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	

	$searchstring=string_to_flexPostGresquery();
	
	
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as tcount FROM \"$table\" WHERE $searchstring";
		$ligne=pg_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show($q->mysql_error,0);}
		$total = $ligne["tcount"];

	}else{
		$sql="SELECT COUNT(*) as tcount FROM \"$table\"";
		$ligne=pg_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show($q->mysql_error,0);}
		$total = $ligne["tcount"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}

	if(isset($_POST['page'])) {$page = $_POST['page'];}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $rp OFFSET $pageStart";

	$sql="SELECT *  FROM \"$table\" WHERE $searchstring $ORDER $limitSql";
	//writelogs($sql,__FUNCTION__,__FILE__,__LINE__);

	if(isset($_GET["verbose"])){echo "<hr><code>$sql</code></hr>";}
	$results = $q->QUERY_SQL($sql);

	if(!$q->ok){json_error_show($q->mysql_error,1);}

	if(pg_num_rows($results)==0){
		json_error_show("$table no data",1);
	}



	$fontsize="26px";
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();




	while($ligne=@pg_fetch_assoc($results)){
		$zDate=$ligne["zdate"];
		$BYTES=$ligne["size"];
		$RQS=$ligne["rqs"];
		$SITE=$ligne["familysite"];
		$RQS=FormatNumber($RQS);
		$BYTES=FormatBytes($BYTES/1024);
		$zDate=str_replace("00:00:00", "", $zDate);
					
		$data['rows'][] = array(
				'id' => md5(serialize($ligne)),
				'cell' => array("<span style='font-size:16px'>$zDate</span>",
						"<span style='font-size:16px'>$SITE</a></span>",
						"<span style='font-size:16px'>$RQS</span>",
						"<span style='font-size:16px'>$BYTES</span>",

				)
		);
	}


	echo json_encode($data);

}



