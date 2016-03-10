<?php
if($argv[1]=="--verbose"){echo __LINE__." verbose OK<br>\n";$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["verbose"])){echo __LINE__." verbose OK<br>\n";$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$GLOBALS["AS_ROOT"]=false;
if(function_exists("posix_getuid")){
	if(posix_getuid()==0){
	$GLOBALS["AS_ROOT"]=true;
	include_once(dirname(__FILE__).'/framework/class.unix.inc');
	include_once(dirname(__FILE__)."/framework/frame.class.inc");
	include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
	include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
	include_once(dirname(__FILE__)."/framework/class.settings.inc");
	include_once(dirname(__FILE__)."/ressources/class.mysql-meta.inc");
}}

include_once('ressources/class.templates.inc');
include_once('ressources/class.html.pages.inc');
include_once('ressources/class.highcharts.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["list"])){showlist();exit;}


js();

function js(){
	//callback
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	if(isset($_GET["callback"])){$callback="&callback={$_GET["callback"]}";}
	$title=$tpl->_ENGINE_parse_body("{proxy_clients}");
	$html="YahooWin6('850','$page?popup=yes$callback','$title')";
	echo $html;
	
	}

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();

	$zdate=$tpl->javascript_parse_text("{time}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$mac=$tpl->javascript_parse_text("{MAC}");
	$MAC=$tpl->javascript_parse_text("{MAC}");
	$members=$tpl->javascript_parse_text("{members}");
	$uid=$tpl->javascript_parse_text("{uid}");
	$hostname=$tpl->javascript_parse_text("{hostname}");
	// ipaddr        | familysite            | servername                                | uid               | MAC               | size
	$t=time();

	$servername=null;
	

	if(isset($_GET["callback"])){$callback="&callback={$_GET["callback"]}";}
	$title=$tpl->javascript_parse_text("{clients}::{MAC}");
	$html="
	<table class='flexRT$t' style='display:none' id='flexRT$t'></table>
	<script>
	function StartLogsSquidTable$t(){

	$('#flexRT$t').flexigrid({
	url: '$page?list=yes$callback',
	dataType: 'json',
	colModel : [
	{display: '$MAC', name : 'MAC', width : 134, sortable : true, align: 'left'},
	{display: '$ipaddr', name : 'ipaddr', width : 134, sortable : false, align: 'left'},
	{display: '$uid', name : 'uid', width : 143, sortable : false, align: 'left'},
	{display: '$hostname', name : 'hostname', width : 202, sortable : false, align: 'left'},
	{display: '&nbsp;', name : 'bnon', width : 80, sortable : false, align: 'center'},
	],

	searchitems : [
		
	{display: '$MAC', name : 'MAC'},
		
	],
	sortname: 'MAC',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:22px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500,1000,1500]

});

}

StartLogsSquidTable$t();
</script>
";
echo $html;
}
function showlist(){
	$page=1;
	$q=new mysql_squid_builder();
	$table="(SELECT MAC FROM UserAutDB GROUP BY MAC) as t";
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}


	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}


	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT *  FROM $table WHERE 1 $searchstring $ORDER $limitSql";

	if(isset($_GET["verbose"])){echo "<hr><code>$sql</code></hr>";}
	$results = $q->QUERY_SQL($sql,"artica_events");

	if(!$q->ok){json_error_show($q->mysql_error,1);}

	if(mysql_num_rows($results)==0){
		json_error_show("$table no data",1);
	}




	$data = array();
	$data['page'] = 1;
	$data['total'] = $total;
	$data['rows'] = array();

	
	while ($ligne = mysql_fetch_assoc($results)) {
		
		$mac=$ligne["MAC"];
		$ipaddr=ListIps($mac);
		$Listuid=Listuid($mac);
		$Listhostname=Listhostname($mac);
		$macser=urlencode($mac);
		$link="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('squid.nodes.php?node-infos-js=yes&MAC=$macser')\"
		style='font-size:16px;text-decoration:underline'>";
				
		
		if(isset($_GET["callback"])){
			$icon=imgtootltip("arrow-blue-left-24.png",null,"{$_GET["callback"]}('','$mac')");
		}
				
		$data['rows'][] = array(
				'id' => md5(serialize($ligne)),
				'cell' => array("<span style='font-size:16px'>$link$mac</a></span>",
				"<span style='font-size:16px'>$ipaddr</span>",
				"<span style='font-size:16px'>$Listuid</span>",
				"<span style='font-size:16px'>$Listhostname</span>",
				"<span style='font-size:16px'>$icon</span>"
						
						)
				);
}


echo json_encode($data);
}

function ListIps($MAC){
	$q=new mysql_squid_builder();
	$sql="SELECT ipaddr FROM UserAutDB WHERE MAC='$MAC' GROUP BY ipaddr";
	$results = $q->QUERY_SQL($sql,"artica_events");
	while ($ligne = mysql_fetch_assoc($results)) {
		if(trim($ligne["ipaddr"])==null){continue;}
		$f[]=$ligne["ipaddr"];
	}
	
	return @implode("<br>", $f);
}
function Listuid($MAC){
	$q=new mysql_squid_builder();
	$sql="SELECT uid FROM UserAutDB WHERE MAC='$MAC' GROUP BY uid";
	$results = $q->QUERY_SQL($sql,"artica_events");
	while ($ligne = mysql_fetch_assoc($results)) {
		if(trim($ligne["uid"])==null){continue;}
		$f[]=$ligne["uid"];
	}

	return @implode("<br>", $f);
}
function Listhostname($MAC){
	$q=new mysql_squid_builder();
	$sql="SELECT hostname FROM UserAutDB WHERE MAC='$MAC' GROUP BY hostname";
	$results = $q->QUERY_SQL($sql,"artica_events");
	while ($ligne = mysql_fetch_assoc($results)) {
		if(trim($ligne["hostname"])==null){continue;}
		$f[]=$ligne["hostname"];
	}

	return @implode("<br>", $f);
}
