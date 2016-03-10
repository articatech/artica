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
include(dirname(__FILE__)."/ressources/class.influx.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");


	$user=new usersMenus();
	if(!$user->AsWebStatisticsAdministrator){
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		exit;
	}
	if(isset($_GET["stats-requeteur"])){stats_requeteur();exit;}
	if(isset($_GET["requeteur-popup"])){requeteur_popup();exit;}
	if(isset($_GET["requeteur-js"])){requeteur_js();exit;}	
	if(isset($_GET["query-js"])){build_query_js();exit;}
	if(isset($_GET["search"])){search();exit;}
	if(isset($_GET["graph-js"])){graph_js();exit;}
	if(isset($_GET["graph2-js"])){graph2_js();exit;}
	if(isset($_GET["graph-uid"])){graph_uid();exit;}
	if(isset($_GET["graph2-uid"])){graph2_uid();exit;}
	if(isset($_GET["graph-uid-1"])){graph_uid_1();exit;}
	if(isset($_GET["graph-uid-2"])){graph_uid_2();exit;}
	if(isset($_GET["graph-uid-3"])){graph_uid_3();exit;}
	if(isset($_GET["graph-uid-4"])){graph_uid_4();exit;}
	
	
	
	
table();


function graph_js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	echo "YahooWin4('1000','$page?graph-uid=yes&uid=".urlencode($_GET["uid"])."','{$_GET["uid"]}')";
	
}
function graph2_js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	echo "YahooWin4('1000','$page?graph2-uid=yes&uid=".urlencode($_GET["uid"])."','{$_GET["uid"]}')";

}
function graph2_uid(){
	$page=CurrentPageName();
	$id=md5($_GET["uid"]);
	$uienc=urlencode($_GET["uid"]);
	
	
	echo "
	<div style='width:985px;height:450px;' id='$id-3'></div>
	<p>&nbsp;</p>
	<div style='width:985px;height:450px;' id='$id-4'></div>
	
	
	<script>
	Loadjs('$page?graph-uid-3=yes&id=$id-3&uid=$uienc');
	Loadjs('$page?graph-uid-4=yes&id=$id-4&uid=$uienc');
	</script>
	";
	}


function graph_uid(){
	$page=CurrentPageName();
	$id=md5($_GET["uid"]);
	$uienc=urlencode($_GET["uid"]);
	
	
	echo "
	<div style='width:985px;height:450px;' id='$id-1'></div>
	<p>&nbsp;</p>
	<div style='width:985px;height:450px;' id='$id-2'></div>
	
	
	<script>
		Loadjs('$page?graph-uid-1=yes&id=$id-1&uid=$uienc');
		Loadjs('$page?graph-uid-2=yes&id=$id-2&uid=$uienc');
	</script>
	";
}


function DATE_START(){
	$tpl=new templates();
	$q=new mysql_squid_builder();

	$table="dashboard_user_day";
	if($q->COUNT_ROWS($table)==0){
		$table="dashboard_blocked_day";
	}


	$sql="SELECT MIN(TIME) as xmin, MAX(TIME) as xmax FROM $table ";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));


	$q=new mysql_squid_builder();

	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$time1=$tpl->time_to_date(strtotime($ligne["xmin"]),true);
	$time2=$tpl->time_to_date(strtotime($ligne["xmax"]),true);
	return $tpl->javascript_parse_text("{date_start} $time1, {last_date} $time2");
}

function graph_uid_1(){
	
	$q=new mysql_squid_builder();
	$sql="SELECT SUM(SIZE) as SIZE,TIME,USER FROM dashboard_user_day GROUP BY TIME,USER
			HAVING USER='{$_GET["uid"]}' ORDER BY TIME";
	$results=$q->QUERY_SQL($sql);
	
	
	if(!$q->ok){
		$q->mysql_error_jsdiv($_GET["id"]);
		die();
	}
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$zmd5=$ligne["zmd5"];
		$size=$ligne["SIZE"]/1024;
		$size=round($size/1024);
		$MAIN["xdata"][]=$ligne["TIME"];
		$MAIN["ydata"][]=$size;
	}
	
	
	
	$highcharts=new highcharts();
	$highcharts->container=$_GET["id"];
	$highcharts->xAxis=$MAIN["xdata"];
	$highcharts->Title="{downloaded_flow} MB - {$_GET["uid"]}";
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="MB";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=null;
	$highcharts->LegendSuffix="{size}";
	$highcharts->SQL_QUERY=$sql;
	$highcharts->xAxisTtitle="{downloaded_flow} MB - {$_GET["uid"]}";
	$highcharts->datas=array("{size}"=>$MAIN["ydata"]);
	echo $highcharts->BuildChart();
	
}
function graph_uid_2(){

	$q=new mysql_squid_builder();

	$results=$q->QUERY_SQL("SELECT SUM(RQS) as RQS,TIME,USER FROM dashboard_user_day GROUP BY TIME,USER
			HAVING USER='{$_GET["uid"]}' ORDER BY TIME");


	if(!$q->ok){
		$q->mysql_error_jsdiv($_GET["id"]);
		die();
	}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$zmd5=$ligne["zmd5"];
		$RQS=$ligne["RQS"];
		$MAIN["xdata"][]=$ligne["TIME"];
		$MAIN["ydata"][]=$RQS;
	}



	$highcharts=new highcharts();
	$highcharts->container=$_GET["id"];
	$highcharts->xAxis=$MAIN["xdata"];
	$highcharts->Title="{requests} - {$_GET["uid"]}";
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="MB";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=null;
	$highcharts->LegendSuffix="{requests}";
	$highcharts->xAxisTtitle="{requests} - {$_GET["uid"]}";
	$highcharts->datas=array("{requests}"=>$MAIN["ydata"]);
	echo $highcharts->BuildChart();

}

function graph_uid_3(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$sql="SELECT SUM(SIZE) as SIZE,FAMILYSITE FROM dashboard_memberwebsite_day  
			WHERE USER='{$_GET["uid"]}' GROUP BY FAMILYSITE ORDER BY SIZE DESC LIMIT 0,15";
	$results=$q->QUERY_SQL($sql);
	
	
			if(!$q->ok){
				$q->mysql_error_jsdiv($_GET["id"]);
				die();
			}
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$FAMILYSITE=$ligne["FAMILYSITE"];
				$SIZE=$ligne["SIZE"];
				$SIZE=$ligne["SIZE"]/1024;
				$SIZE=round($SIZE/1024);
				$MAIN[$FAMILYSITE]=$SIZE;
				
			}
	
	
			$highcharts=new highcharts();
			$highcharts->SQL_QUERY=$sql;
			$highcharts->container=$_GET["id"];
			$highcharts->PieDatas=$MAIN;
			$highcharts->ChartType="pie";
			$highcharts->PiePlotTitle="{$_GET["uid"]} - {top_websites_by_size}";
			$highcharts->Title=$tpl->_ENGINE_parse_body("{$_GET["uid"]} - {top_websites_by_size} (MB)");
			echo $highcharts->BuildChart();
	
	
}
function graph_uid_4(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$sql="SELECT SUM(RQS) as RQS,FAMILYSITE FROM dashboard_memberwebsite_day  
			WHERE USER='{$_GET["uid"]}' GROUP BY FAMILYSITE ORDER BY RQS DESC LIMIT 0,15";
	$results=$q->QUERY_SQL($sql);
	
	
			if(!$q->ok){
				$q->mysql_error_jsdiv($_GET["id"]);
				die();
			}
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$FAMILYSITE=$ligne["FAMILYSITE"];
				$SIZE=$ligne["RQS"];
				$MAIN[$FAMILYSITE]=$SIZE;
				
			}
	
	
			$highcharts=new highcharts();
			$highcharts->container=$_GET["id"];
			$highcharts->SQL_QUERY=$sql;
			$highcharts->PieDatas=$MAIN;
			$highcharts->ChartType="pie";
			$highcharts->PiePlotTitle="{$_GET["uid"]} - {top_websites_by_hits}";
			$highcharts->Title=$tpl->_ENGINE_parse_body("{$_GET["uid"]} - {top_websites_by_hits}");
			echo $highcharts->BuildChart();

}


function table(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$t=time();
	$members=$tpl->_ENGINE_parse_body("{members}");
	$add_member=$tpl->_ENGINE_parse_body("{add_member}");

	$delete=$tpl->javascript_parse_text("{delete}");
	$aliases=$tpl->javascript_parse_text("{aliases}");
	$about2=$tpl->_ENGINE_parse_body("{about2}");
	$new_report=$tpl->javascript_parse_text("{new_report}");
	$report=$tpl->javascript_parse_text("{report}");
	$title=$tpl->javascript_parse_text("{current_members}")." ". DATE_START();
	$progress=$tpl->javascript_parse_text("{progress}");
	$size=$tpl->javascript_parse_text("{size}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$computers=$tpl->javascript_parse_text("{computers}");
	$my_proxy_aliases=$tpl->javascript_parse_text("{my_proxy_aliases}");
	$q=new mysql_squid_builder();
	$graph=$tpl->javascript_parse_text("{graph}");
//current_members
	$t=time();
	$buttons="
	buttons : [
		{name: '<strong style=font-size:22px>$my_proxy_aliases</strong>', bclass: 'link', onpress : GoToProxyAliases$t},
		{name: '<strong style=font-size:22px>$computers</strong>', bclass: 'link', onpress : GotoNetworkBrowseComputers$t},
	],";

	
	
	$html="
	<table class='SQUID_MYSQL_MEMBERS' style='display: none' id='SQUID_MYSQL_MEMBERS' style='width:100%'></table>
	<script>
	$(document).ready(function(){
	$('#SQUID_MYSQL_MEMBERS').flexigrid({
	url: '$page?search=yes&ID={$_GET["ID"]}',
	dataType: 'json',
	colModel : [
	{display: '<strong style=font-size:18px>$members</strong>', name : 'USER', width : 418, sortable : true, align: 'left'},
	{display: '<strong style=font-size:18px>$hits</strong>', name : 'RQS', width : 228, sortable : true, align: 'right'},
	{display: '<strong style=font-size:18px>$size</strong>', name : 'SIZE', width : 228, sortable : true, align: 'right'},
	{display: '<strong style=font-size:18px>GRAPH</strong>', name : 'GRAPH', width : 70, sortable : false, align: 'center'},
	{display: '<strong style=font-size:18px>GRAPH</strong>', name : 'GRAPH', width : 70, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$members', name : 'USER'},
	
	],
	sortname: 'SIZE',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: '500',
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
});


function NewReport$t(){
	Loadjs('squid.browse-users.php?callback=Addcategory$t');
}

function GoToProxyAliases$t(){
	GoToProxyAliases();
}

function GotoNetworkBrowseComputers$t(){
	GotoNetworkBrowseComputers();
}

var xAddcategory$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#SQUID_MAIN_REPORTS').flexReload();
	$('#SQUID_MAIN_REPORTS_USERZ').flexReload();
}

function Addcategory$t(field,value){
	var XHR = new XHRConnection();
	XHR.appendData('ID','{$_GET["ID"]}');
	XHR.appendData('field',field);
	XHR.appendData('value',value);
	XHR.sendAndLoad('$page', 'POST',xAddcategory$t);
}
</script>
	";

	echo $tpl->_ENGINE_parse_body($html);


}

function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	
	$q=new mysql_squid_builder();
	$t=$_GET["t"];
	
	
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `FULL_USERS_DAY` (
			`user` varchar(128) NOT NULL,
			`hits` BIGINT UNSIGNED NOT NULL,
			`size` BIGINT UNSIGNED NOT NULL,
			PRIMARY KEY `user` (`user`),
			KEY `hits` (`hits`),
			KEY `size` (`size`)
	) ENGINE=MYISAM;");


	$data = array();
	
	
	
	if($q->COUNT_ROWS("FULL_USERS_DAY","artica_backup")==0){json_error_show("no data [".__LINE__."]",0);}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	
	$table="FULL_USERS_DAY";
	

	$searchstring=string_to_flexquery();

	

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=100;}

	$data['page'] = $page;
	$pageStart = ($page-1)*$rp;
	if($pageStart<0){$pageStart=0;}
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	if($searchstring==null){
		$data['total'] = $q->COUNT_ROWS("FULL_USERS_DAY");
		
	}else{
		$sql="SELECT COUNT(*) as tcount FROM FULL_USERS_DAY WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$data['total']=$ligne["tcount"];
	}
	

	
	
	$data['rows'] = array();
	$CurrentPage=CurrentPageName();
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $ORDER $limitSql";
	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(mysql_num_rows($results)==0){json_error_show("no data");}
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}
	
	$q1=new mysql();
	$t=time();
	$fontsize=22;

	$span="<span style='font-size:{$fontsize}px'>";
	$IPTCP=new IP();


	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$zmd5=$ligne["zmd5"];
		$member_value=trim($ligne["user"]);
		$hits=FormatNumber($ligne["hits"]);
		$size=FormatBytes($ligne["size"]/1024);
		$ahref=null;
		$member_assoc=null;
		
		
		$graph=imgtootltip("graph2-48.png","{statistics}","Loadjs('$MyPage?graph-js=yes&uid=".
				urlencode($member_value)."')");
		
		$graph2=imgtootltip("graphs-48.png","{statistics}","Loadjs('$MyPage?graph2-js=yes&uid=".
				urlencode($member_value)."')");
		
		
		if($IPTCP->IsvalidMAC($member_value)){
			$mac_encoded=urlencode($member_value);
			$uid=$q->MacToUid($member_value);
			if($uid<>null){$member_assoc="&nbsp; ($uid)";}
			$ahref="<a href=\"javascript:blur();\"
					OnClick=\"javascript:Loadjs('squid.nodes.php?node-infos-js=yes&MAC=$mac_encoded');\"
					style='font-size:$fontsize;text-decoration:underline'>";
			}

		$data['rows'][] = array(
				'id' => $member_value,
				'cell' => array(
						"$span$ahref$member_value</a>$member_assoc</span>",
						"$span$hits</a></span>",
						"$span$size</a></span>","<center>$graph</center>","<center>$graph2</center>",

				)
		);

	}
	echo json_encode($data);

}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}