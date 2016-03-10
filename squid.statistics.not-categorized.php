<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.postgres.inc');
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";


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
	if(isset($_GET["export"])){export();exit;}
	if(isset($_GET["export-popup"])){export_popup();exit;}
	
table();


function table(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$t=time();
	$websites=$tpl->_ENGINE_parse_body("{websites}");
	$add_member=$tpl->_ENGINE_parse_body("{add_member}");

	$delete=$tpl->javascript_parse_text("{delete}");
	$aliases=$tpl->javascript_parse_text("{aliases}");
	$about2=$tpl->_ENGINE_parse_body("{about2}");
	$new_report=$tpl->javascript_parse_text("{new_report}");
	$report=$tpl->javascript_parse_text("{report}");
	$title=$tpl->javascript_parse_text("{websites}: {not_categorized}");
	$progress=$tpl->javascript_parse_text("{progress}");
	$size=$tpl->javascript_parse_text("{size}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$computers=$tpl->javascript_parse_text("{computers}");
	$launch=$tpl->javascript_parse_text("{analyze}");
	$export=$tpl->javascript_parse_text("{export}");
	$q=new mysql_squid_builder();
	$NOT_CATEGORIZED_TIME=intval(@file_get_contents("{$GLOBALS["BASEDIR"]}/NOT_CATEGORIZED_TIME"));
	$lastscan=null;
	if($NOT_CATEGORIZED_TIME>0){
		$lastscan=$tpl->javascript_parse_text("{last_scan} ".distanceOfTimeInWords($NOT_CATEGORIZED_TIME,time()));
	}
	
//current_members
	$t=time();
	$buttons="
	buttons : [
		{name: '<strong style=font-size:22px>$launch</strong>', bclass: 'link', onpress : Launch$t},
		{name: '<strong style=font-size:22px>$export</strong>', bclass: 'link', onpress : export$t},
	],";

	
	
	$html="
	<table class='SQUID_NOT_CATEGORIZED_TABLE' style='display: none' id='SQUID_NOT_CATEGORIZED_TABLE' style='width:100%'></table>
	<script>
	$(document).ready(function(){
	$('#SQUID_NOT_CATEGORIZED_TABLE').flexigrid({
	url: '$page?search=yes&ID={$_GET["ID"]}',
	dataType: 'json',
	colModel : [
	{display: '<strong style=font-size:18px>$websites</strong>', name : 'familysite', width : 418, sortable : true, align: 'left'},
	{display: '<strong style=font-size:18px>$hits</strong>', name : 'hits', width : 228, sortable : true, align: 'right'},
	{display: '<strong style=font-size:18px>$size</strong>', name : 'size', width : 228, sortable : false, align: 'right'},
	],
	$buttons
	searchitems : [
	{display: '$websites', name : 'familysite'},
	
	],
	sortname: 'size',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:30px>$title $lastscan</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: '500',
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
});


function Launch$t(){
	Loadjs('squid.statistics.not-categorized.progress.php');
}

function export$t(){
	Loadjs('$page?export=yes');
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

function export(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$filename=dirname(__FILE__)."/ressources/logs/web/notcatgorized.csv";
	@mkdir("ressources/logs/web");
	@chmod("ressources/logs/web",0777);
	$q=new postgres_sql();
	$export=$tpl->javascript_parse_text("{export}:notcatgorized.csv");
	$q->QUERY_SQL("COPY (SELECT * from \"not_categorized\") To '$filename' with CSV HEADER;");
	
	if(!$q->ok){
		echo "alert('$filename\\n$q->mysql_error');";return;
		
	}
	if(!is_file("$filename")){echo "alert('Failed');";return;}
	
	echo "YahooWin(550,'$page?export-popup=yes','$export')";
	
	
	
	//echo "s_PopUp('ressources/logs/web/notcatgorized.csv',0,0,'');";
}

function export_popup(){
	$tpl=new templates();
	
		$html="<center style='padding:50px;width:78%' class=form>
			<a href='ressources/logs/web/notcatgorized.csv'>
			<img src='img/csv-256.png'>
			<br>
			<span style='font-size:28px;text-decoration:underline'>notcatgorized.csv</span>
			</a>
			
	</center>";
	echo $html;
	
}

function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$table="not_categorized";
	$q=new postgres_sql();
	$t=$_GET["t"];


	$total=0;
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}


	$searchstring=string_to_flexPostGresquery();
	$sql="SELECT COUNT(*) as tcount FROM $table WHERE $searchstring";
	$ligne=pg_fetch_assoc($q->QUERY_SQL($sql));
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql",0);}
	$total = $ligne["tcount"];

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=100;}


	$pageStart = ($page-1)*$rp;
	if($pageStart<0){$pageStart=0;}
	$limitSql = "LIMIT $rp OFFSET $pageStart";

	$sql="SELECT *  FROM $table WHERE $searchstring $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql",0);}

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	$CurrentPage=CurrentPageName();

	if(pg_num_rows($results)==0){json_error_show("no data");}
	
	$t=time();
	$fontsize=22;
	$span="<span style='font-size:{$fontsize}px'>";
	$IPTCP=new IP();


	while ($ligne = pg_fetch_assoc($results)) {
		$zmd5=$ligne["zmd5"];
		$familysite=trim($ligne["familysite"]);
		$hits=FormatNumber($ligne["rqs"]);
		$size=FormatBytes($ligne["size"]/1024);
		$ahref=null;
	

		$data['rows'][] = array(
				'id' => $familysite,
				'cell' => array(
						"$span$ahref$familysite</a></span>",
						"$span$hits</a></span>",
						"$span$size</a></span>",

				)
		);

	}
	echo json_encode($data);

}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}