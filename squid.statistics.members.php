<?php
ini_set('memory_limit','1000M');
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
include(dirname(__FILE__)."/ressources/class.influx.inc");


	$user=new usersMenus();
	if(!$user->AsWebStatisticsAdministrator){
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		exit;
	}
	if(isset($_GET["stats-requeteur"])){stats_requeteur();exit;}
	if(isset($_GET["requeteur-popup"])){requeteur_popup();exit;}
	if(isset($_GET["requeteur-js"])){requeteur_js();exit;}	
	if(isset($_GET["query-js"])){build_query_js();exit;}
	if(isset($_GET["table1"])){table1();exit;}
	if(isset($_GET["build-table"])){build_table();exit;}
	
	

function stats_requeteur(){
	$tpl=new templates();
	$page=CurrentPageName();


}
function requeteur_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$build_the_query=$tpl->javascript_parse_text("{build_the_query}::{members}");
	echo "YahooWin('670','$page?requeteur-popup=yes&t={$_GET["t"]}','$build_the_query');";
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
	
	$html="<div style='width:98%;margin-bottom:20px' class=form>
	<table style='width:100%'>
	<tr>
			
		<td style='vertical-align:top;font-size:18px' class=legend>{members}:</td>
		<td style='vertical-align:top;font-size:18px;'>". Field_array_Hash($members,"members-$t",$_SESSION["SQUID_STATS_MEMBER"],"blur()",null,0,"font-size:18px;")."</td>
	</tr>
	<tr>			
	
		<td style='vertical-align:top;font-size:18px' class=legend>{from_date}:</td>
		<td style='vertical-align:top;font-size:18px'>". field_date("from-date-$t",$_SESSION["SQUID_STATS_DATE1"],";font-size:18px;width:160px",$Selectore)."
		&nbsp;". Field_text("from-time-$t",$_SESSION["SQUID_STATS_TIME1"],";font-size:18px;width:82px")."</td>
	</tr>
	<tr>
		<td style='vertical-align:top;font-size:18px' class=legend>{to_date}:</td>
		<td style='vertical-align:top;font-size:18px'>". field_date("to-date-$t",$_SESSION["SQUID_STATS_DATE2"],";font-size:18px;width:160px",$Selectore)."
		&nbsp;". Field_text("to-time-$t",$_SESSION["SQUID_STATS_TIME2"],";font-size:18px;width:82px")."</td>
		
	</tr>
	<tr>
		<td style='vertical-align:middle;font-size:18px' class=legend>{search}:</td>
		<td style='vertical-align:top;font-size:18px'>". Field_text("search-$t",$_SESSION["SQUID_STATS_MEMBER_SEARCH"],";font-size:18px;width:98%")."</td>
	</tr>
	<tr>
		<td style='vertical-align:top;font-size:18px;' colspan=2 align='right'><hr>". button("{generate_statistics}","Run$t()",18)."</td>
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
	var search=encodeURIComponent(document.getElementById('search-$t').value);
	var interval=0;
	

	
	Loadjs('$page?query-js=yes&t=$t&container=graph-$t&date1='+date1+'&time1='+time1+'&date2='+date2+'&time2='+time2+'&interval='+interval+'&user='+user+'&search='+search);
	
}
</script>
	";	
	echo $tpl->_ENGINE_parse_body($html);
}

function build_query_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$from=strtotime("{$_GET["date1"]} {$_GET["time1"]}");
	$to=strtotime("{$_GET["date2"]} {$_GET["time2"]}");
	$interval=$_GET["interval"];
	$search=url_decode_special_tool($_GET["search"]);
	$t=$_GET["t"];
	$user=$_GET["user"];
	$md5=md5("MEMBERS:$from$to$interval$user$search");
	$_SESSION["SQUID_STATS_DATE1"]=$_GET["date1"];
	$_SESSION["SQUID_STATS_TIME1"]=$_GET["time1"];

	$_SESSION["SQUID_STATS_DATE2"]=$_GET["date2"];
	$_SESSION["SQUID_STATS_TIME2"]=$_GET["time2"];
	
	$timetext1=$tpl->time_to_date(strtotime("{$_GET["date1"]} {$_GET["time1"]}"),true);
	$timetext2=$tpl->time_to_date(strtotime("{$_GET["date2"]} {$_GET["time2"]}"),true);


	$nextFunction="LoadAjax('table-$t','$page?table1=yes&zmd5=$md5');";
	$nextFunction_encoded=urlencode(base64_encode($nextFunction));
	$q=new mysql_squid_builder();
	$q->CheckReportTable();

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ID,builded FROM reports_cache WHERE `zmd5`='$md5'"));
	if(intval($ligne["ID"])==0){
		$array["FROM"]=$from;
		$array["TO"]=$to;
		$array["INTERVAL"]=$interval;
		$array["USER"]=$user;
		$array["SEARCH"]=$search;
		$serialize=mysql_escape_string2(serialize($array));
		$title="{members}: $timetext1 - $timetext2 - $user/$search";
		$sql="INSERT IGNORE INTO `reports_cache` (`zmd5`,`title`,`report_type`,`zDate`,`params`) VALUES
		('$md5','$title','MEMBERS',NOW(),'$serialize')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo "alert('". $tpl->javascript_parse_text($q->mysql_errror)."')";return;}
		echo "Loadjs('squid.statistics.progress.php?zmd5=$md5&NextFunction=$nextFunction_encoded')";
		return;
	}

	if(intval($ligne["builded"]==0)){
	echo "
	function Start$t(){
		if(document.getElementById('SQUID_STATISTICS_MEMBERS_MD5')){
			document.getElementById('SQUID_STATISTICS_MEMBERS_MD5').md5='$md5';
		}
	
	
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
	

	
page();



function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$title=null;
	$q=new mysql_squid_builder();
	$q->CheckReportTable();
	if($_GET["zmd5"]==null){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT title,zmd5 FROM reports_cache WHERE report_type='MEMBERS' ORDER BY zDate DESC LIMIT 0,1"));
		if(!$q->ok){echo $q->mysql_error_html();}
		$_GET["zmd5"]=$ligne["zmd5"];
	}
	
	$nextFunction="LoadAjax('table-$t','$page?table1=yes&zmd5={$_GET["zmd5"]}');";
	$content="<center><img src=img/loader-big.gif></center>";
	$html="$title<div style='width:99%;margin-bottom:10px' id='table-$t'>$content</div>	

	
	
<script>
	$nextFunction
</script>";
	
echo $tpl->_ENGINE_parse_body($html);
		
}


function table1(){
	$page=CurrentPageName();
	$tpl=new templates();

	
	$q=new mysql_squid_builder();
	$zmd5=$_GET["zmd5"];
	
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reports_cache WHERE `zmd5`='$zmd5'"));
	$params=unserialize($ligne["params"]);
	
	$from=$params["FROM"];
	$to=$params["TO"];
	$interval=$params["INTERVAL"];
	$USER_FIELD=strtolower($params["USER"]);
	$search=$params["SEARCH"];
	
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ComputerMacAddress=$tpl->_ENGINE_parse_body("{ComputerMacAddress}");
	$time=$tpl->_ENGINE_parse_body("{time}");
	$website=$tpl->_ENGINE_parse_body("{website}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$requests=$tpl->_ENGINE_parse_body("{requests}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$build_the_query=$tpl->_ENGINE_parse_body("{build_the_query}");
	$edit_report=$tpl->_ENGINE_parse_body("{edit_report}");
	$delete_report=$tpl->_ENGINE_parse_body("{delete_report}");
	$TB_WIDTH=570;
	
	$delete_reportbt="{name: '<strong style=font-size:18px>$delete_report</strong>', bclass: 'Delz', onpress : Delete$t},";
	
	if($zmd5<>null){
		$edit="{name: '<strong style=font-size:18px>$edit_report</strong>', bclass: 'Apply', onpress : edit$t},";
		
	
	}
	
	$buttons="
	buttons : [
	$edit
	{name: '<strong style=font-size:18px>$build_the_query</strong>', bclass: 'Apply', onpress : build_the_query},
	$delete_reportbt
	],";
	
	$title=$tpl->javascript_parse_text($ligne["title"]);
	
	
	$t=time();
	
	$html="
	<input type='hidden' id='SQUID_STATISTICS_MEMBERS_MD5' value='$zmd5'>
	<table class='SQUID_STATISTICS_MEMBERS' style='display: none' id='SQUID_STATISTICS_MEMBERS' style='width:99%'></table>
	<script>
	$(document).ready(function(){
	$('#SQUID_STATISTICS_MEMBERS').flexigrid({
	url: '$page?build-table=yes&zmd5=$zmd5&userfield=$USER_FIELD',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:22px>$member</span>', name : '$USER_FIELD', width : 930, sortable : true, align: 'left'},
	{display: '<span style=font-size:22px>$size</span>', name : 'size', width : 417, sortable : true, align: 'right'},
	],
	
	$buttons
	
	searchitems : [
	{display: '$member', name : '$USER_FIELD'},
	
	],
	sortname: 'size',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:26px>$title</span>',
	useRp: true,
	rp: 100,
	showTableToggleBtn: false,
	width: '99%',
	height: 600,
	singleSelect: true,
	rpOptions: [100,200,300,500]
	
	});
	});
	
	function edit$t(){
		Loadjs('squid.statistics.edit.report.php?zmd5=$zmd5&t=$t');
		}
	
	
	function build_the_query(){
		Loadjs('$page?requeteur-js=yes&t=$t');
	}
	
	function Delete$t(){
		Loadjs('squid.statistics.flow.php?remove-cache-js=yes&zmd5=$zmd5');
		
		
	}
	
	</script>	
";	
	

	echo $html;
}

function build_table(){
	$q=new mysql_squid_builder();
	$md5=$_GET["zmd5"];
	if($md5==null){json_error_show('no key sended');}
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reports_cache WHERE `zmd5`='$md5'"));
	$params=unserialize($ligne["params"]);
	
	$from=$params["FROM"];
	$to=$params["TO"];
	$interval=$params["INTERVAL"];
	$userfield=strtolower($params["USER"]);
	$search=$params["SEARCH"];
	$page=1;
	
	
	$q=new postgres_sql();
	$tpl=new templates();
	$searchstring=string_to_flexPostGresquery();
	
	$table="(SELECT SUM(size) AS size,$userfield FROM \"{$md5}report\" GROUP BY $userfield) as t";
	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	if(isset($_POST["sortname"])){
			if($_POST["sortname"]<>null){
				$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
			}
		}
	
	
		if($searchstring<>null){
			$sql="SELECT COUNT(*) AS tcount FROM $table WHERE $searchstring";
			$ligne=pg_fetch_assoc($q->QUERY_SQL($sql,"artica_backup"));
			$total = $ligne["tcount"];
	
		}else{
			$sql="SELECT COUNT(*) AS tcount FROM $table";
			$ligne=pg_fetch_assoc($q->QUERY_SQL($sql,"artica_backup"));
			$total = $ligne["tcount"];
		}
	
		if (isset($_POST['rp'])) {$rp = $_POST['rp'];}else{$rp=50;}
	
	
	
		$pageStart = ($page-1)*$rp;
		$limitSql = "LIMIT $rp OFFSET $pageStart";
	
	
	
		$sql="SELECT * FROM $table WHERE $searchstring $ORDER $limitSql";
		if($GLOBALS["VERBOSE"]){echo "$sql<br>\n";}
	
		$results = $q->QUERY_SQL($sql);
		if(!$q->ok){json_error_show("$q->mysql_error $sql",0);}
		
	
	
		$data = array();
		$data['page'] = $page;
		$data['total'] = $total;
		$data['rows'] = array();
		if(pg_num_rows($results)==0){json_error_show("No data",1);}
		$fontsize="22px";
	
		$c=1;
		while ($ligne = pg_fetch_assoc($results)) {
			$USER=trim($ligne[$userfield]);
			
			if(preg_match("#([0-9\.]+)\/[0-9]+#", $USER,$re)){$USER=$re[1];}
			$c++;
			$size=FormatBytes($ligne["size"]/1024);
		$js="Loadjs('squid.statistics.report.member.php?from-zmd5=$md5&USER_DATA=".urlencode($USER)."');";
		
		if($USER==null){$USER="Unknown";$js="blur();";}
		
		$href="<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:26px;text-decoration:underline'>";
		$data['rows'][] = array(
				'id' => $c,
				'cell' => array(
						"<span style='font-size:$fontsize'>$href{$USER}</a></span>",
						"<span style='font-size:$fontsize'>$size</a></span>",
		
				)
		);
		
	}
	
	$data['total'] = $c;
	echo json_encode($data);
	
}
