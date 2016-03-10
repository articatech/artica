<?php
if(isset($_GET["verbose"])){echo __LINE__." verbose OK<br>\n";$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.user.inc');
include_once('ressources/class.langages.inc');
include_once('ressources/class.sockets.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.privileges.inc');
include_once('ressources/class.ChecksPassword.inc');
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__)."/ressources/class.influx.inc");

$users=new usersMenus();
if(!$users->AsWebStatisticsAdministrator){
	$tpl=new templates();
	echo "<script> alert('". $tpl->javascript_parse_text("`{$_SERVER['PHP_AUTH_USER']}/{$_SERVER['PHP_AUTH_PW']}` {ERROR_NO_PRIVS}")."'); </script>";
	die();
}


if(isset($_GET["events-list"])){events_search();exit;}

page();
function DATE_START(){
	$tpl=new templates();
	$q=new mysql_squid_builder();

	$table="dashboard_volume_day";
	$sql="SELECT MIN(TIME) as xmin, MAX(TIME) as xmax FROM $table ";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));


	$q=new mysql_squid_builder();

	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$time1=$tpl->time_to_date(strtotime($ligne["xmin"]),true);
	$time2=$tpl->time_to_date(strtotime($ligne["xmax"]),true);
	return $tpl->_ENGINE_parse_body("{date_start} $time1, {last_date} $time2");
}

function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	if($SquidPerformance>2){
		echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{artica_statistics_disabled}"));
		return;
	}
	$t=time();
	$events=$tpl->_ENGINE_parse_body("{events}");
	$zdate=$tpl->_ENGINE_parse_body("{hour}");
	$proto=$tpl->_ENGINE_parse_body("{proto}");
	$uri=$tpl->_ENGINE_parse_body("{website}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	if(function_exists("date_default_timezone_get")){$timezone=" - ".date_default_timezone_get();}
	$title=$tpl->_ENGINE_parse_body("{today}: {realtime_requests}");
	$zoom=$tpl->_ENGINE_parse_body("{zoom}");
	$button1="{name: 'Zoom', bclass: 'Search', onpress : ZoomSquidAccessLogs},";
	$stopRefresh=$tpl->javascript_parse_text("{stop_refresh}");
	$logs_container=$tpl->javascript_parse_text("{logs_container}");
	$refresh=$tpl->javascript_parse_text("{refresh}");

	$items=$tpl->_ENGINE_parse_body("{items}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$SaveToDisk=$tpl->_ENGINE_parse_body("{SaveToDisk}");
	$addCat=$tpl->_ENGINE_parse_body("{add} {category}");
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$task=$tpl->_ENGINE_parse_body("{task}");
	$new_schedule=$tpl->_ENGINE_parse_body("{new_rotate}");
	$explain=$tpl->_ENGINE_parse_body("{explain_squid_tasks}");
	$run=$tpl->_ENGINE_parse_body("{run}");
	$task=$tpl->_ENGINE_parse_body("{task}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$filename=$tpl->_ENGINE_parse_body("{filename}");
	$empty=$tpl->_ENGINE_parse_body("{empty}");
	$askdelete=$tpl->javascript_parse_text("{empty_store} ?");
	$files=$tpl->_ENGINE_parse_body("{files}");
	$ext=$tpl->_ENGINE_parse_body("{extension}");
	$back_to_events=$tpl->_ENGINE_parse_body("{back_to_events}");
	$Compressedsize=$tpl->_ENGINE_parse_body("{compressed_size}");
	$realsize=$tpl->_ENGINE_parse_body("{realsize}");
	$delete_file=$tpl->javascript_parse_text("{delete_file}");
	$rotate_logs=$tpl->javascript_parse_text("{rotate_logs}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$MAC=$tpl->_ENGINE_parse_body("{MAC}");
	$members=$tpl->_ENGINE_parse_body("{members}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$table_size=855;
	$url_row=505;
	$member_row=276;
	$table_height=420;
	$distance_width=230;
	$tableprc="100%";
	$margin="-10";
	$margin_left="-15";
	if(is_numeric($_GET["table-size"])){$table_size=$_GET["table-size"];}
	if(is_numeric($_GET["url-row"])){$url_row=$_GET["url-row"];}

	$q=new mysql_squid_builder();
	$title=$tpl->_ENGINE_parse_body(DATE_START());

	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$error=$tpl->javascript_parse_text("{error}");
	$sitename=$tpl->javascript_parse_text("{sitename}");
	$button3="{name: '<strong style=font-size:18px>$refresh</strong>', bclass: 'Reload', onpress : SquidRotate$t},";

	$html="
	<div id='SQUID_INFLUDB_TABLE_DIV'>
	<table class='SQUID_INFLUDB_TABLE' style='display: none' id='SQUID_INFLUDB_TABLE' style='width:$tableprc'></table>
	</div>
	<script>
	var mem$t='';
	function StartLogsSquidTable$t(){
	$('#SQUID_INFLUDB_TABLE').flexigrid({
	url: '$page?events-list=yes',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:18px>$zdate</span>', name : 'TIME', width :160, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$members</span>', name : 'USERID', width : 390, sortable : false, align: 'left'},
	{display: '<span style=font-size:18px>$uri</span>', name : 'FAMILYSITE', width : 414, sortable : false, align: 'left'},
	{display: '<span style=font-size:18px>$category</span>', name : 'CATEGORY', width : 185, sortable : false, align: 'left'},
	{display: '<span style=font-size:18px>$size</span>', name : 'SIZE', width : 110, sortable : true, align: 'right'},
	{display: '<span style=font-size:18px>$hits</span>', name : 'RQS', width : 110, sortable : true, align: 'right'},
	
	],
	buttons : [
	$button3
	],		

	searchitems : [
	{display: '$sitename', name : 'FAMILYSITE'},
	{display: '$category', name : 'CATEGORY'},
	{display: '$member', name : 'USERID'},
	{display: '$ipaddr', name : 'IPADDR'},
	{display: '$MAC', name : 'MAC'},
	],
	sortname: 'TIME',
	sortorder: 'DESC',
	usepager: true,
	title: '<span style=\"font-size:26px\">$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});

if(document.getElementById('SQUID_ACCESS_LOGS_DIV')){
	document.getElementById('SQUID_ACCESS_LOGS_DIV').innerHTML='';
}
}
setTimeout('StartLogsSquidTable$t()',800);

function SquidRotate$t(){
	Loadjs('squid.influx.log.progress.php');
}

</script>
";
	echo $html;

}
function events_search(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$rp=0;
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY `{$_POST["sortname"]}` {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	$pageStart = ($page-1)*$rp;
	if(is_numeric($rp)){$limitSql = "LIMIT $pageStart, $rp";}
	
	if(!$q->FIELD_EXISTS("dashboard_volume_day","CATEGORY")){
		$sql="ALTER TABLE `dashboard_volume_day` ADD `CATEGORY` VARCHAR(64), ADD INDEX(`CATEGORY`)";
		$q->QUERY_SQL($sql,"artica_backup");
	}
	
	$table="(SELECT SUM(SIZE) as SIZE, SUM(RQS) as RQS,`TIME`,CATEGORY,USERID,IPADDR,MAC,FAMILYSITE 
			FROM `dashboard_volume_day`  GROUP BY `TIME`,CATEGORY,USERID,IPADDR,MAC,FAMILYSITE)  as t";
	
			
	$searchstring=string_to_flexquery();
	$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){json_error_show($q->mysql_error);}
	$total =$ligne["TCOUNT"];



	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
			
			
	$sql="SELECT *  FROM  $table  WHERE 1 $searchstring $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql);
	
	
	
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}
	if(mysql_num_rows($results)==0){json_error_show("no data");}
	
	
	$tpl=new templates();
	
	$font_size="font-size:16px";
	while ($ligne = mysql_fetch_assoc($results)) {
		$CATEGORY=$ligne["CATEGORY"];
		$FAMILYSITE=$ligne["FAMILYSITE"];
		$IPADDR=$ligne["IPADDR"];
		$MAC=$ligne["MAC"];
		$RQS=$ligne["RQS"];
		$SITE=$ligne["FAMILYSITE"];
		$SIZE=$ligne["SIZE"];
		$USERID=$ligne["USERID"];
		$color="#000000";
		
		
		
		$ident=array();
		
		if(intval($SIZE)>=1024){
			$SIZE=FormatBytes(intval($SIZE)/1024);
		}else{
			$SIZE="{$SIZE}Bytes";
		}
		
		$RQS=FormatNumber($RQS);
		$md=md5(serialize($ligne));
		
		$time=$ligne["TIME"];
		
		
		
		
		
		$spanON="<span style='color:$color;$font_size'>";
		$spanOFF="</span>";
		$cached_text=null;
		
		
		
		
		
		if($USERID<>null){
			$ident[]="<a href=\"javascript:blur()\"
			OnClick=\"javascript:Loadjs('squid.nodes.php?node-infos-js=yes&uid=$USERID',true);\"
			style='text-decoration:underline;color:$color;$font_size'>$USERID</a>";
			
		}
		
		$ident[]="<a href=\"javascript:blur()\"
		OnClick=\"javascript:Loadjs('squid.nodes.php?node-infos-js=yes&ipaddr=$IPADDR',true);\"
		style='text-decoration:underline;color:$color;$font_size'>$IPADDR</a>";
	
		if($MAC<>null){
			$ident[]="<a href=\"javascript:blur()\"
			OnClick=\"javascript:Loadjs('squid.nodes.php?node-infos-js=yes&MAC=$MAC',true);\"
			style='text-decoration:underline;color:$color;$font_size'>$MAC</a>";
		
		}
		$colorDiv=$color;
		if($colorDiv=="black"){$colorDiv="transparent";}
		$identities=@implode("&nbsp;|&nbsp;", $ident);
		
		
		
		$data['rows'][] = array(
				'id' => $md,
				'cell' => array(
						"$spanON{$time}$spanOFF",
						"$spanON$identities$spanOFF",
						"$spanON$SITE$spanOFF",
						"$spanON$CATEGORY$spanOFF",
						"$spanON$SIZE$spanOFF",
						"$spanON$RQS$spanOFF",
						
					)
				);
		}


echo json_encode($data);
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}