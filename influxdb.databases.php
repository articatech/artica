<?php
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.influx.inc');
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		die();
	}
	
	if(isset($_GET["popup"])){table();exit;}
	if(isset($_GET["search"])){search();exit;}
	if(isset($_POST["new-db"])){create_db();exit;}
	if(isset($_REQUEST["delete-db"])){delete_db();exit;}
	if(isset($_GET["delete-db-js"])){delete_db_js();exit;}
js();



function js(){
	$t=time();
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{databases}");
	echo "YahooWin3(890,'$page?popup=yes','$title')";	
}

function delete_db_js(){
	$t=time();
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{delete} {$_GET["delete-db-js"]}");	
	echo "
var xSave$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	$('#INFLUX_DATABASES_TABLE').flexReload();
}


function Save$t(){
	if(!confirm('$title')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-db','{$_GET["delete-db-js"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

Save$t()";
	
}

function delete_db(){
	
	$DB=$_REQUEST["delete-db"];
	
	
	$postgres=new postgres_sql();
	$postgres->QUERY_SQL("SELECT pg_terminate_backend(pg_stat_activity.pid) FROM pg_stat_activity WHERE pg_stat_activity.datname = '$DB' AND pid <> pg_backend_pid();");
	$postgres->QUERY_SQL("DROP DATABASE $DB");
	
	
}
	

function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$t=time();
	$restore=$tpl->javascript_parse_text("{restore}");
	$groups=$tpl->javascript_parse_text("{groups2}");
	$memory=$tpl->javascript_parse_text("{memory}");
	$load=$tpl->javascript_parse_text("{load}");
	$version=$tpl->javascript_parse_text("{version}");
	$servername=$tpl->javascript_parse_text("{servername2}");
	$status=$tpl->javascript_parse_text("{status}");
	$events=$tpl->javascript_parse_text("{events}");
	$policies=$tpl->javascript_parse_text("{policies}");
	$databasename=$tpl->javascript_parse_text("{database_name}");
	$switch=$tpl->javascript_parse_text("{switch}");
	$browse=$tpl->javascript_parse_text("{browse}");
	$database=$tpl->javascript_parse_text("{database}");
	$new_database=$tpl->javascript_parse_text("{new_database}");
	$databases=$tpl->javascript_parse_text("{databases}");
	$tables=$tpl->javascript_parse_text("{tables}");
	$t=time();
	$delete="{display: 'delete', name : 'icon3', width : 35, sortable : false, align: 'left'},";
	
	
	
	
	$buttons="
	 buttons : [
	{name: '<strong style=font-size:18px>$new_database</strong>', bclass: 'add', onpress : New$t},
	],";
	
	
	
	
	$html="
	<input type='hidden' id='INFLUXDB_RESTORE_PATH' value=''>
	<table class='INFLUX_DATABASES_TABLE' style='display: none' id='INFLUX_DATABASES_TABLE'></table>
	<script>
	$(document).ready(function(){
	$('#INFLUX_DATABASES_TABLE').flexigrid({
	url: '$page?search=yes&t=$t',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:18px>$database</span>', name : 'datname', width : 500, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$tables</span>', name : 'tablesCount', width : 100, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'link', width : 190, sortable : false, align: 'right'},
	
	],
	$buttons
	searchitems : [
	{display: '$database', name : 'hostname'},

	
	],
	sortname: 'datname',
	sortorder: 'asc',
	usepager: true,
	title: '<strong style=font-size:26px>$databases</strong>',
	useRp: true,
	rpOptions: [10, 20, 30, 50,100,200],
	rp:50,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true
	
	});
	});
	
var xNew$t= function (obj) {
 	text=obj.responseText;
 	if(text.length>0){alert(text);return;}
 	$('#INFLUX_DATABASES_TABLE').flexReload();
}	
	
function New$t(){
	var text=prompt('$databasename');
	if(!text){return;}
	var XHR = new XHRConnection();
 	XHR.appendData('new-db',text);
    XHR.sendAndLoad('$page', 'POST',xNew$t);	
}

function ScanDir$t(){
	var text=document.getElementById('INFLUXDB_RESTORE_PATH').value;
    var XHR = new XHRConnection();
 	XHR.appendData('ScanDir',text);
    XHR.sendAndLoad('$page', 'POST',xScanDir$t);
              
 }
function xBrowseDir$t(){
	Loadjs('SambaBrowse.php?no-shares=yes&field=INFLUXDB_RESTORE_PATH&functionAfter=ScanDir$t');

}
function xrestore$t(){
	Loadjs('influxdb.restore.progress.php');
}


	</script>";
	echo $html;	
	
}

function create_db(){
	$tpl=new templates();
	$influx=new influx();
	$postgres=new postgres_sql();
	$postgres->CREATE_DATABASE(trim($_POST["new-db"]));
	if(!$postgres->ok){
		echo $postgres->mysql_error;
	}
	

}


function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$sock=new sockets();
	$q=new mysql();
	$postgres=new postgres_sql();
	
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	$table="pg_database";
	
	
	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery(null,true);
	
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table $searchstring";
		$ligne=pg_fetch_assoc($postgres->QUERY_SQL($sql,"artica_backup"));
		
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=pg_fetch_assoc($postgres->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}

	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $rp  OFFSET $pageStart";
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}
	
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = 0;
	$data['rows'] = array();
	$data['total'] =$total;
	
	$fontsize=20;
	$style="style='font-size:20px'";
	
	$c=0;
	$tpl=new templates();
	
	$curr=$tpl->javascript_parse_text("{current}");
	

	$sql="SELECT *  FROM $table $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results=$postgres->QUERY_SQL($sql);
	
	if(!$postgres->ok){
		json_error_show($postgres->mysql_error);
	}
	
	if(pg_num_rows($results)==0){
		json_error_show("no data - $sql");
	}
	
	while($ligne=@pg_fetch_assoc($results)){
		$c++;
		$current=null;
		$database=$ligne["datname"];
		$ms5=md5($database);
		$TABLES=$postgres->LIST_TABLES($database);
		
		$CountOfTables=count($TABLES);
		
		$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?delete-db-js=$database')");
		$color="black";
		
		$size=FormatBytes(intval($size)/1024);
		if(preg_match("#^template#", $database)){$delete="&nbsp;";}
		if(preg_match("#^postgres#", $database)){$delete="&nbsp;";}
		$data['rows'][] = array(
				'id' => $ms5,
				'cell' => array(
						"<span $style>{$database}</a></span>",
						"<center $style>{$CountOfTables}</a></center>",
						
						"<center>{$delete}</a></center>",
						
						
				)
		);

	}
	
	if($c==0){json_error_show("no data");}
	
	echo json_encode($data);

}
?>