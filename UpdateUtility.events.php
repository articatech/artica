<?php
if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.updateutility2.inc');
include_once('ressources/class.ini.inc');
include_once('ressources/class.system.network.inc');

$users=new usersMenus();
if(!$users->AsSystemAdministrator){
	$tpl=new templates();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	echo "alert('$ERROR_NO_PRIVS');";return;
}

if(isset($_GET["ShowTime"])){ShowTime();exit;}
if(isset($_GET["events-table"])){events_table();exit;}
if(isset($_POST["empty-table"])){empty_table();exit;}

//updateutilityev
//		$q->QUERY_SQL("INSERT INTO updateutilityev (`zDate`,`filesize`,`filesnum`,`details`,`isSuccess`) 
//VALUES ('$date','$files','$size','$details','$isSuccess')","artica_events");
page();

function page(){

$page=CurrentPageName();
$tpl=new templates();
$date=$tpl->_ENGINE_parse_body("{zDate}");
$description=$tpl->_ENGINE_parse_body("{description}");
$filenum=$tpl->_ENGINE_parse_body("{files}");
$size=$tpl->_ENGINE_parse_body("{size}");
$empty=$tpl->_ENGINE_parse_body("{empty}");
$empty_events_text_ask=$tpl->javascript_parse_text("{empty_events_text_ask}");

$t=time();

$buttons="
buttons : [
	{name: '<strong style=font-size:18px>$empty</strong>', bclass: 'Delz', onpress : EmptyEvents$t},

],";

$html="
<table class='events-table-$t' style='display: none' id='events-table-$t' style='width:99%'></table>
<script>
	$(document).ready(function(){
		$('#events-table-$t').flexigrid({
			url: '$page?events-table=yes',
			dataType: 'json',
			colModel : [
			{display: '&nbsp;', name : 'isSuccess', width :50, sortable : true, align: 'center'},
			{display: '$date', name : 'zDate', width :626, sortable : true, align: 'left'},
			],
			$buttons
			
			searchitems : [
			{display: '$date', name : 'zDate'},
			{display: '$size', name : 'filesize'},
			],
			sortname: 'zDate',
			sortorder: 'desc',
			usepager: true,
			title: '',
			useRp: true,
			rp: 50,
			showTableToggleBtn: false,
			width: '99%',
			height: 550,
			singleSelect: true,
			rpOptions: [10, 20, 30, 50,100,200,500]
			
		});
	});

	function UpdateUtilityZoom(ID){
		YahooWin6('750','$page?ShowTime='+ID,'Zoom::'+ID);
	}
	
	var x_EmptyEvents= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);return;}
			$('#events-table-$t').flexReload();
			//$('#grid_list').flexOptions({url: 'newurl/'}).flexReload();
			// $('#fgAllPatients').flexOptions({ query: 'blah=qweqweqwe' }).flexReload();
	
	}

	function EmptyEvents$t(){
		if(confirm('$empty_events_text_ask')){
		var XHR = new XHRConnection();
		XHR.appendData('empty-table','yes');
		XHR.sendAndLoad('$page', 'POST',x_EmptyEvents);
		}
	}

</script>";

		echo $html;

}
function empty_table(){
	$q=new mysql();
	$q->QUERY_SQL("TRUNCATE TABLE updateutilityev","artica_events");
	if(!$q->ok){echo $q->mysql_error;}
}

function events_table(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();


	$search='%';
	$table="updateutilityev";
	$page=1;
	$ORDER="ORDER BY zDate DESC";

	$total=0;
	if($q->COUNT_ROWS($table,"artica_events")==0){json_error_show("No data",1);}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){json_error_show("$q->mysql_error",1);}


	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();


	while ($ligne = mysql_fetch_assoc($results)) {
		$md5=md5(serialize($ligne));
		$isSuccess="ok32.png";
		$time=strtotime("{$ligne["zDate"]}");
		$ligne["zDate"]=$tpl->_ENGINE_parse_body(date("{l} {F} d H:i:s",$time));
		//updateutilityev
		//		$q->QUERY_SQL("INSERT INTO updateutilityev (`zDate`,`filesize`,`filesnum`,`details`,`isSuccess`)
		//VALUES ('$date','$files','$size','$details','$isSuccess')","artica_events");		
		$ligne["filesize"]=FormatBytes($ligne["filesize"]/1024);
		if($ligne["isSuccess"]==0){$isSuccess="okdanger32.png";}
		$ligne["filesnum"]=ABSFormatNumber($ligne["filesnum"],0);
		$data['rows'][] = array(
				'id' => $md5,
				'cell' => array("<center><img src='img/$isSuccess'></center>",
				"<a href=\"javascript:UpdateUtilityZoom('$time')\" style='font-size:22px;text-decoration:underline'>{$ligne["zDate"]}</a>"
				)
		);
	}


	echo json_encode($data);

}

function ShowTime(){
	$time=$_GET["ShowTime"];
	$tpl=new templates();
	$zDate=date("Y-m-d H:i:s",$time);
	$zDateT=$tpl->_ENGINE_parse_body(date("{l} {F} d H:i:s",$time));
	$sql="SELECT `details` FROM updateutilityev WHERE zDate='$zDate'";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	
	$date=
	$html="		
	<div style='font-size:16px'>$zDateT ($zDate)</div>
	<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:100%;height:446px;border:5px solid #8E8E8E;
	overflow:auto;font-size:11px' id='$t'>{$ligne["details"]}</textarea>";
	echo $html;
	
}

function ABSFormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}
