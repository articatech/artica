<?php
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.ini.inc');


$users=new usersMenus();
if(!$users->AsSquidAdministrator){
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	die();
}


if(isset($_GET["events-table"])){events_table();exit;}
if(isset($_GET["ShowID"])){ShowID();exit;}
if(isset($_GET["ShowID-js"])){ShowID_js();exit;}
if(isset($_POST["empty-table"])){empty_table();exit;}
popup();


function ShowID_js(){
	
	$id=$_GET["ShowID-js"];
	if(!is_numeric($id)){
		
		return;
	
	}$tpl=new templates();
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$sql="SELECT subject FROM postfix_admin_mysql WHERE ID=$id";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	
	$subject=$tpl->javascript_parse_text($ligne["subject"]);
	echo "YahooWin3('550','$page?ShowID=$id','$subject')";
	
}
function ShowID(){

$tpl=new templates();
$sql="SELECT content FROM postfix_admin_mysql WHERE ID={$_GET["ShowID"]}";
$q=new mysql();
$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));

$content=$tpl->_ENGINE_parse_body($ligne["content"]);
$content=nl2br($content);
echo "<p style='font-size:18px'>$content</p>";
}

function empty_table(){
	$q=new mysql();
	$q->QUERY_SQL("TRUNCATE TABLE postfix_admin_mysql","artica_events");
	$BaseWorkDir="/usr/share/artica-postfix/ressources/interface-cache";
	if ($handle = opendir($BaseWorkDir)) {
			
		while (false !== ($filename = readdir($handle))) {
			if($filename=="."){continue;}
			if($filename==".."){continue;}
			$targetFile="$BaseWorkDir/$filename";
			if(is_dir($targetFile)){continue;}
			@unlink($targetFile);
		}
			
			
	}
}

function popup(){

	$page=CurrentPageName();
	$tpl=new templates();
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$context=$tpl->_ENGINE_parse_body("{context}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$empty=$tpl->_ENGINE_parse_body("{empty}");
	$daemon=$tpl->_ENGINE_parse_body("{daemon}");
	$settings=$tpl->javascript_parse_text("{watchdog_squid_settings}");
	$empty_events_text_ask=$tpl->javascript_parse_text("{empty_events_text_ask}");
	$TB_HEIGHT=550;
	$TB_WIDTH=927;
	$TB2_WIDTH=801;
	$zdate_width=127;
	$all=$tpl->_ENGINE_parse_body("{all}");
	$t=time();
	$critical=null;
	if(isset($_GET["important-only"])){
		$critical="&critical=0&important-only=yes";
		$TB2_WIDTH="955";
		$zdate_width="256";
	}

	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$empty</strong>', bclass: 'Delz', onpress : EmptyEvents},
	{name: '<strong style=font-size:18px>Warn</strong>', bclass: 'Warn', onpress :  Warn$t},
	{name: '<strong style=font-size:18px>Info</strong>', bclass: 'Help', onpress :  info$t},
	{name: '<strong style=font-size:18px>Crit.</strong>', bclass: 'Err', onpress :  Err$t},
	{name: '<strong style=font-size:18px>$all</strong>', bclass: 'Statok', onpress :  All$t},
	
	

	],	";
	$html="
<table class='events-table-$t' style='display: none' id='events-table-$t' style='width:99%'></table>
	<script>

function BuildTable$t(){
	$('#events-table-$t').flexigrid({
		url: '$page?events-table=yes&text-filter={$_GET["text-filter"]}$critical',
		dataType: 'json',
		colModel : [
		{display: '', name : 'severity', width :31, sortable : true, align: 'center'},
		{display: '$date', name : 'zDate', width :$zdate_width, sortable : true, align: 'left'},
		{display: '$events', name : 'subject', width : $TB2_WIDTH, sortable : false, align: 'left'},
		{display: '$daemon', name : 'filename', width :185, sortable : true, align: 'left'},
		],
		$buttons
	
		searchitems : [
		{display: '$events', name : 'subject'},
		],
		sortname: 'zDate',
		sortorder: 'desc',
		usepager: true,
		title: '',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: '99%',
		height: $TB_HEIGHT,
		singleSelect: true,
		rpOptions: [10, 20, 30, 50,100,200,500]

	});
}

function articaShowEvent(ID){
	YahooWin6('750','$page?ShowID='+ID,'$title::'+ID);
}

var x_EmptyEvents= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#events-table-$t').flexReload();
	//$('#grid_list').flexOptions({url: 'newurl/'}).flexReload();
	// $('#fgAllPatients').flexOptions({ query: 'blah=qweqweqwe' }).flexReload();

}

function Warn$t(){
	$('#events-table-$t').flexOptions({url: '$page?events-table=yes&critical=1'}).flexReload(); 
}
function info$t(){
	$('#events-table-$t').flexOptions({url: '$page?events-table=yes&critical=2'}).flexReload(); 
}
function Err$t(){
	$('#events-table-$t').flexOptions({url: '$page?events-table=yes&critical=0'}).flexReload(); 
}
function All$t(){
	$('#events-table-$t').flexOptions({url: '$page?events-table=yes'}).flexReload(); 
}
function Params$t(){
	Loadjs('squid.proxy.watchdog.php');
}

function EmptyEvents(){
	if(!confirm('$empty_events_text_ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('empty-table','yes');
	XHR.sendAndLoad('$page', 'POST',x_EmptyEvents);
}
setTimeout(\" BuildTable$t()\",800);
</script>";

echo $html;

}

function events_table(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();

	$FORCE=1;
	$search='%';
	$table="postfix_admin_mysql";
	$page=1;
	$ORDER="ORDER BY zDate DESC";
	if(is_numeric($_GET["critical"])){
		$FORCE="severity={$_GET["critical"]}";
	}
	
	if($_GET["text-filter"]<>null){
		$FORCE=" subject LIKE '%{$_GET["text-filter"]}%'";
		if(is_numeric($_GET["critical"])){
			$FORCE=$FORCE." AND severity={$_GET["critical"]}";
		}
	}

	$total=0;
	if($q->COUNT_ROWS($table,"artica_events")==0){json_error_show("no data",1);}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$severity[0]="22-red.png";
	$severity[1]="22-warn.png";
	$severity[2]="22-infos.png";
	$currentdate=date("Y-m-d");

	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
		
		
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		if(!$q->ok){ if(preg_match("#marked as crashed#", $q->mysql_error)){ $q->QUERY_SQL("DROP TABLE `$table`","artica_events"); } }
		
		$total = $ligne["TCOUNT"];

	}else{
		if(strlen($FORCE)>2){
			$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
			if(!$q->ok){ if(preg_match("#marked as crashed#", $q->mysql_error)){ $q->QUERY_SQL("DROP TABLE `$table`","artica_events"); } }
			$total = $ligne["TCOUNT"];
		}else{
			$total = $q->COUNT_ROWS($table, "artica_events");
		}
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE $FORCE $searchstring $ORDER $limitSql";
	
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){ if(preg_match("#(marked as crashed|Incorrect key file for table)#", $q->mysql_error)){ $q->QUERY_SQL("DROP TABLE `$table`","artica_events"); } }
	if(!$q->ok){json_error_show($q->mysql_error." $sql",1);}

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	$CurrentPage=CurrentPageName();

	if(mysql_num_rows($results)==0){json_error_show("no data");}
	
	
	if(isset($_GET["important-only"])){
		$spanzdate="<span style='font-size:27px'>";
		$stylefontsize=";font-size:18px";
	}

	while ($ligne = mysql_fetch_assoc($results)) {
		
		$hostname=$ligne["hostname"];
		$ligne["zDate"]=str_replace($currentdate, "", $ligne["zDate"]);
		$severity_icon=$severity[$ligne["severity"]];
		$link="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('$CurrentPage?ShowID-js={$ligne["ID"]}')\" 
		style='text-decoration:underline$stylefontsize'>";
		$text=$link.$tpl->_ENGINE_parse_body($ligne["subject"]."</a>
		<div style='font-size:10px'>{host}:$hostname {function}:{$ligne["function"]}, {line}:{$ligne["line"]}</div>");
		
		
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<img src='img/$severity_icon'>",
						
						"$spanzdate{$ligne["zDate"]}</span>",$text,$ligne["filename"] )
		);
	}


	echo json_encode($data);

}