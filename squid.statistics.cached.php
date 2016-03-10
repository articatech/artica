<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.artica.inc');
include_once('ressources/class.ini.inc');
include_once('ressources/class.squid.inc');
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
include(dirname(__FILE__)."/ressources/class.influx.inc");


	$user=new usersMenus();
	if(!$user->AsWebStatisticsAdministrator){
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		exit;
	}
	
	if(isset($_GET["main"])){main_page();exit;}
	if(isset($_GET["stats-requeteur"])){stats_requeteur();exit;}
	if(isset($_GET["requeteur-popup"])){requeteur_popup();exit;}
	if(isset($_GET["requeteur-js"])){requeteur_js();exit;}
	if(isset($_GET["remove-cache-js"])){remove_cache_js();exit;}
	if(isset($_GET["remove-cache"])){remove_cache_button();exit;}
	if(isset($_POST["remove-cache"])){remove_cache();exit;}
	if(isset($_GET["query-js"])){build_query_js();exit;}
	if(isset($_GET["graph-cache-1"])){graph1();exit;}
	if(isset($_GET["table1"])){table1();exit;}
	
	if(isset($_GET["graph2"])){graph2();exit;}
	if(isset($_GET["table2"])){table2();exit;}
	
	if(isset($_GET["table3"])){table3();exit;}
	if(isset($_GET["graph3"])){graph3();exit;}	


	

	
page();

function stats_requeteur(){
	$tpl=new templates();
	$page=CurrentPageName();

	$ahref_sys="<a href=\"javascript:blur();\"
	OnClick=\"javascript:Loadjs('$page?requeteur-js=yes&t={$_GET["t"]}')\">";
	echo $tpl->_ENGINE_parse_body("$ahref_sys{build_the_query}</a>");
}
function requeteur_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$build_the_query=$tpl->javascript_parse_text("{build_the_query}::{websites}");
	echo "YahooWin('670','$page?requeteur-popup=yes&t={$_GET["t"]}','$build_the_query');";
}


function build_query_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$from=strtotime("{$_GET["date1"]} {$_GET["time1"]}");
	$to=strtotime("{$_GET["date2"]} {$_GET["time2"]}");
	$interval=$_GET["interval"];
	$t=$_GET["t"];
	$user=$_GET["user"];
	$md5=md5("WEBSITES:$from$to$interval$user{$_GET["searchsites"]}{$_GET["searchuser"]}");
	$_SESSION["SQUID_STATS_DATE1"]=$_GET["date1"];
	$_SESSION["SQUID_STATS_TIME1"]=$_GET["time1"];
	
	$_SESSION["SQUID_STATS_DATE2"]=$_GET["date2"];
	$_SESSION["SQUID_STATS_TIME2"]=$_GET["time2"];
	
	
	$timetext1=$tpl->time_to_date(strtotime("{$_GET["date1"]} {$_GET["time1"]}"),true);
	$timetext2=$tpl->time_to_date(strtotime("{$_GET["date2"]} {$_GET["time2"]}"),true);
	
	
	
	$nextFunction="LoadAjax('WEBSITES_STATS_MAIN_GRAPH','$page?main=yes&zmd5=$md5&t=$t');";
	$nextFunction_encoded=urlencode(base64_encode($nextFunction));
	$q=new mysql_squid_builder();
	$q->CheckReportTable();
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ID,builded FROM reports_cache WHERE `zmd5`='$md5'"));
	if(intval($ligne["ID"])==0){
		$array["FROM"]=$from;
		$array["TO"]=$to;
		$array["INTERVAL"]=$interval;
		$array["USER"]=$user;
		$array["searchsites"]=$_GET["searchsites"];
		$array["searchuser"]=$_GET["searchuser"];
		
		$serialize=mysql_escape_string2(serialize($array));
		$title="{websites}: $timetext1 - {to} $timetext2 {websites} {like} {$_GET["searchsites"]} and $user {like} {$array["searchuser"]}";
		$sql="INSERT IGNORE INTO `reports_cache` (`zmd5`,`title`,`report_type`,`zDate`,`params`) VALUES 
		('$md5','$title','WEBSITES',NOW(),'$serialize')";
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

if(document.getElementById('graph-$t')){
	document.getElementById('graph-$t').innerHTML='<center><img src=img/loader-big.gif></center>';
}
LockPage();	
setTimeout('Start$t()',800);
";



return;
}
	
	echo $nextFunction;
	
}
function remove_cache_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$zmd5=$_GET["zmd5"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ID,`title` FROM `reports_cache` WHERE `zmd5`='$zmd5'"));
	if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."')";return;}
	$title=$tpl->javascript_parse_text("{delete} id {$ligne["ID"]} \"{$ligne["title"]}\" ($zmd5)");
	$page=CurrentPageName();
	
	
	$t=time();
echo "
var xLinkEdHosts$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){ alert(res); return; }
	
	
	if( document.getElementById('BROWSE_STATISTICS_CACHES2') ){
		$('#BROWSE_STATISTICS_CACHES2').flexReload();
	}	
	if( document.getElementById('BROWSE_STATISTICS_CACHES') ){
		$('#BROWSE_STATISTICS_CACHES').flexReload();
	}
	
	
}
	
	
function LinkEdHosts$t(){
	if(!confirm('$title ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('remove-cache','$zmd5');
	XHR.sendAndLoad('$page', 'POST',xLinkEdHosts$t);
}
LinkEdHosts$t();
" ;
}
function remove_cache(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM reports_cache WHERE `zmd5`='{$_POST["remove-cache"]}'");
	$tpl=new templates();
	
}

function requeteur_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	squid_stats_default_values();
	$t=$_GET["t"];
	$per["1m"]="{minute}";
	$per["5m"]="5 {minutes}";
	$per["10m"]="10 {minutes}";
	$per["1h"]="{hour}";
	$per["1d"]="{day}";
	
	
	$members["MAC"]="{MAC}";
	$members["USERID"]="{uid}";
	$members["IPADDR"]="{ipaddr}";
	
	
	$q=new postgres_sql();
	$Selectore=$q->fieldSelectore();
	
	
	$stylelegend="style='vertical-align:top;font-size:18px;padding-top:5px' nowrap";
	$html="<div style='width:98%;margin-bottom:20px' class=form>
	<table style='width:100%'>
	<tr>
		<td $stylelegend class=legend>{from_date}:</td>
		<td style='vertical-align:top;font-size:18px'>". field_date("from-date-$t",$_SESSION["SQUID_STATS_DATE1"],";font-size:18px;width:160px",$Selectore)."
		&nbsp;".Field_text("from-time-$t",$_SESSION["SQUID_STATS_TIME1"],";font-size:18px;width:82px")."</td>
		
	</tr>
		<td $stylelegend class=legend>{to_date}:</td>
		<td style='vertical-align:top;font-size:18px'>". field_date("to-date-$t",$_SESSION["SQUID_STATS_DATE2"],";font-size:18px;width:160px",$Selectore)."
		&nbsp;". Field_text("to-time-$t",$_SESSION["SQUID_STATS_TIME2"],";font-size:18px;width:82px")."</td>
	</tr>
				
	<tr>
		<td $stylelegend class=legend>{members}:</td>
		<td style='vertical-align:top;font-size:18px;'>". Field_array_Hash($members,"members-$t",$_SESSION["SQUID_STATS_MEMBER"],"blur()",null,0,"font-size:18px;")."</td>
	</tr>				
	<tr>
		<td $stylelegend class=legend>{members} {search}:</td>
		<td style='vertical-align:top;font-size:18px;'>". Field_text("members-search-$t","*","font-size:18px;")."</td>
	</tr>	
	<tr>
		<td $stylelegend class=legend>{websites} {search}:</td>
		<td style='vertical-align:top;font-size:18px;'>". Field_text("websites-search-$t","*","font-size:18px;")."</td>
	</tr>								
	<tr style='height:50px'>	
		<td style='vertical-align:top;font-size:18px;' colspan=2 align='right'>". button("{generate_statistics}","Run$t()",22)."</td>
	</tr>
	</table>
	</div>
<script>
function Run$t(){
	var date1=document.getElementById('from-date-$t').value;
	var time1=document.getElementById('from-time-$t').value;
	var date2=document.getElementById('to-date-$t').value
	var time2=document.getElementById('to-time-$t').value;
	var user=document.getElementById('members-$t').value;
	var interval='';
	var searchuser=encodeURIComponent(document.getElementById('members-search-$t').value);
	var searchsites=encodeURIComponent(document.getElementById('websites-search-$t').value);
	Loadjs('$page?query-js=yes&t=$t&container=graph-$t&searchsites='+searchsites+'&searchuser='+searchuser+'&date1='+date1+'&time1='+time1+'&date2='+date2+'&time2='+time2+'&interval='+interval+'&user='+user);

}
</script>
";	
	
echo $tpl->_ENGINE_parse_body($html);
	
	
}




function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$t=time();
	$title=$tpl->_ENGINE_parse_body("{cached_data}");
$html="<div style='font-size:30px'>$title</div>
	<div style='font-size:18px' class=explain>{cached_data_explain}</div>
	</div>	
	
	
	<table style='width:100%'>
	<tr>
		<td width='100%'>		
			<div style='width:1400px;height:550px' id='graph-cache-1'></div>
		</td>
	</tr>
	<tr>
		<td><p>&nbsp;</p></td>
	</tr>
			<tr>
		<td width='100%'>		
			<div style='width:1400px;height:550px' id='graph-cache-2'></div>
		</td>
	</tr>
		
</table>	
	
	
<script>
	Loadjs('$page?graph-cache-1=yes');
</script>";	
	
	echo $tpl->_ENGINE_parse_body($html);
}


function graph1(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	
	
	$filecache="{$GLOBALS["BASEDIR"]}/CACHED_ROW_DAY";
	
	$MAIN=unserialize(@file_get_contents($filecache));
	
	$xdata=$MAIN["xdata"];
	$ydata=$MAIN["ydata"];
	
	
	
	
	$title="{cached_data} (MB) / 30 {minutes}";
	$timetext=$_GET["interval"];
	$highcharts=new highcharts();
	$highcharts->container="graph-cache-1";
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
	echo "\nLockPage();\nLoadjs('$page?graph2=yes');\n";
	

}	

function graph2(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	
	$filecache="{$GLOBALS["BASEDIR"]}/CACHED_ROW_MONTH";
	if(!is_file($filecache)){die();}
	
	$MAIN=unserialize(@file_get_contents($filecache));
	
	$xdata=$MAIN["xdata"];
	$ydata=$MAIN["ydata"];
	
	
	
	
	$title="{cached_data} (MB) / {day}";
	$timetext=$_GET["interval"];
	$highcharts=new highcharts();
	$highcharts->container="graph-cache-2";
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
	
	
	
}


function table2(){
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$zmd5=$_GET["zmd5"];
	if($zmd5==null){echo "alert('no key sended');UnlockPage();";die();}
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `values` FROM reports_cache WHERE `zmd5`='$zmd5'"));
	$values=$ligne["values"];
	$MAIN=unserialize(base64_decode($values));
	
	$html[]="<table style='width:100%'>";
	$html[]=$tpl->_ENGINE_parse_body("<tr><th style='font-size:18px;padding:8px'>{websites}</td>
			<th style='font-size:18px'>{hits}</td></tr>");
	while (list ($site, $size) = each ($MAIN["TOP_WEBSITES_HITS"]) ){
		$size=FormatNumber($size);
		$html[]="<tr><td style='font-size:18px;padding:8px'>$site</a></td>
		<td style='font-size:18px'>$size</td></tr>";
	}
	
	$html[]="</table>";
	$html[]="<script>";
	$html[]="Loadjs('$page?graph3=yes&zmd5={$_GET["zmd5"]}&t={$_GET["t"]}')";
	$html[]="</script>";
	echo @implode("", $html);	
	
	
}

function remove_cache_button(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$button_browse=null;
	$button_empty=null;
	$sql="SELECT COUNT(ID) as tcount,report_type FROM `reports_cache` GROUP BY report_type HAVING `report_type`='FLOW'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo $q->mysql_error_html();}
	
	if(intval($ligne["tcount"])>0){
		$button_browse=$tpl->_ENGINE_parse_body(button("{browse_cache}",
				"Loadjs('squid.statistics.browse-cache.php?report_type=FLOW')",16));
	}
	
	$button_empty=$tpl->_ENGINE_parse_body(button("{empty_cache}","Loadjs('$page?remove-cache-js=yes&zmd5={$_GET["zmd5"]}')",16));
	echo "<table><tr><td nowrap>$button_browse</td><td>&nbsp;</td><td>$button_empty</td></tr></table>";
}

function graph3(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$q=new mysql_squid_builder();
	$zmd5=$_GET["zmd5"];
	if($zmd5==null){echo "alert('no key sended');UnlockPage();";die();}
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `values` FROM reports_cache WHERE `zmd5`='$zmd5'"));
	$values=$ligne["values"];
	
	if(strlen($values)==0){echo "alert('NO data...{$ligne["values"]}');UnlockPage();";$q->QUERY_SQL("DELETE FROM reports_cache WHERE `zmd5`='$zmd5'");return;}
	$MAIN=unserialize(base64_decode($values));
	
	$PieData=$MAIN["TOP_WEBSITES_MEMBERS"];
	$highcharts=new highcharts();
	$highcharts->container="graph3-$zmd5";
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{members}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("TOP {members}/{size} (KB)");
	echo $highcharts->BuildChart();
	echo "\n";
	echo "if(document.getElementById('websites-button-area')){document.getElementById('websites-button-area').innerHTML='';}\n";
	echo "LoadAjax('table3-$zmd5','$page?table3=yes&zmd5=$zmd5&t={$_GET["t"]}');\n";
	
}





function table3(){
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$zmd5=$_GET["zmd5"];
	if($zmd5==null){echo "alert('no key sended');UnlockPage();";die();}
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `values` FROM reports_cache WHERE `zmd5`='$zmd5'"));
	$values=$ligne["values"];
	$MAIN=unserialize(base64_decode($values));
	
	$html[]="<table style='width:100%'>";
	$html[]=$tpl->_ENGINE_parse_body("<tr><th style='font-size:18px;padding:8px'>{members}</td><th style='font-size:18px'>{size}</td></tr>");
	while (list ($site, $size) = each ($MAIN["TOP_WEBSITES_MEMBERS"]) ){
		$size=FormatBytes($size);
		$js="Loadjs('squid.statistics.report.member.php?from-zmd5=$zmd5&USER_DATA=".urlencode($site)."');";
		$href="<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:18px;text-decoration:underline'>";
		$html[]="<tr><td style='font-size:18px;padding:8px'>$href$site</a></td>
		<td style='font-size:18px'>$size</td></tr>";
	}
		
	$html[]="</table>";
	$html[]="<script>";
	//$html[]="Loadjs('$page?graph2=yes&zmd5={$_GET["zmd5"]}&t={$_GET["t"]}')";
	$html[]="</script>";
	echo @implode("", $html);
		
	
	
}

function table1(){
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$zmd5=$_GET["zmd5"];
	if($zmd5==null){echo "alert('no key sended');UnlockPage();";die();}
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `values` FROM reports_cache WHERE `zmd5`='$zmd5'"));
	$values=$ligne["values"];
	$MAIN=unserialize(base64_decode($values));
	
	$html[]="<table style='width:100%'>";
	$html[]=$tpl->_ENGINE_parse_body("<tr><th style='font-size:18px;padding:8px'>{websites}</td><th style='font-size:18px'>{size}</td></tr>");
	while (list ($site, $size) = each ($MAIN["TOP_WEBSITES_SIZE"]) ){
		$size=FormatBytes($size);
		$html[]="<tr><td style='font-size:18px;padding:8px'>$site</a></td>
		<td style='font-size:18px'>$size</td></tr>";
	}
		
	$html[]="</table>";
	$html[]="<script>";
	$html[]="Loadjs('$page?graph2=yes&zmd5={$_GET["zmd5"]}&t={$_GET["t"]}')";
	$html[]="</script>";
	echo @implode("", $html);
	
	
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}