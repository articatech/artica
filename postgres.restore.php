<?php
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	include_once('ressources/class.system.network.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		die();
	}
	
	if(isset($_GET["popup"])){table();exit;}
	if(isset($_GET["search"])){search();exit;}
	if(isset($_POST["ScanDir"])){scandir_restore();exit;}
js();



function js(){
	$t=time();
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{restore}");
	echo "YahooWin3(890,'$page?popup=yes','$title')";	
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
	$packages=$tpl->javascript_parse_text("{packages}");
	$switch=$tpl->javascript_parse_text("{switch}");
	$browse=$tpl->javascript_parse_text("{browse}");
	$upload=$tpl->javascript_parse_text("{upload}");
	$backup=$tpl->javascript_parse_text("{backup}");
	$remote_server=$tpl->javascript_parse_text("{remote_server}");
	$t=time();
	$delete="{display: 'delete', name : 'icon3', width : 35, sortable : false, align: 'left'},";
	
	
	
	
	$buttons="
	 buttons : [
	{name: '<strong style=font-size:18px>$upload</strong>', bclass: 'add', onpress : upload$t},
	{name: '<strong style=font-size:18px>$browse</strong>', bclass: 'add', onpress : xBrowseDir$t},
	
	
	],";
	
	
	
	
	$html="
	<input type='hidden' id='INFLUXDB_RESTORE_PATH' value=''>
	<table class='INFLUX_RESTORE_TABLE' style='display: none' id='INFLUX_RESTORE_TABLE'></table>
	<script>
	$(document).ready(function(){
	$('#INFLUX_RESTORE_TABLE').flexigrid({
	url: '$page?search=yes&t=$t',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:18px>$backup</span>', name : 'hostname', width : 510, sortable : true, align: 'left'},
	{display: '&nbsp;', name : 'link', width : 190, sortable : false, align: 'right'},
	{display: '<span style=font-size:18px>$restore</span>', name : 'link', width : 90, sortable : false, align: 'center'},
	
	],
	$buttons
	searchitems : [
	{display: '$backup', name : 'hostname'},

	
	],
	sortname: 'hostname',
	sortorder: 'asc',
	usepager: true,
	title: '<strong style=font-size:22px>$restore</strong>',
	useRp: true,
	rpOptions: [10, 20, 30, 50,100,200],
	rp:50,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true
	
	});
	});
	
function upload$t(){
	Loadjs('influxdb.restore.upload.php');	
}

function xrestore2$t(){
	var server=prompt('$remote_server ?');
	if(!server){return;}
	Loadjs('influxdb.restore.remote.progress.php?server='+server);	
}


var xScanDir$t= function (obj) {
 	text=obj.responseText;
 	if(text.length>0){alert(text);return;}
 	$('#INFLUX_RESTORE_TABLE').flexReload();
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
	Loadjs('postgress.restore.progress.php');
}


	</script>";
	echo $html;	
	
}

function scandir_restore(){
	$ScanDir=urlencode($_POST["ScanDir"]);
	$sock=new sockets();
	$sock->getFrameWork("postgres.php?restore-scandir=$ScanDir");
	
}


function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$sock=new sockets();
	$q=new mysql();
	$database="artica_events";

	$t=$_GET["t"];
	$search='%';

	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	$table="last_boot";


	$data = array();
	$data['page'] = $page;
	$data['total'] = 0;
	$data['rows'] = array();

	
	$fontsize=20;
	$style="style='font-size:18px'";
	$dataX=unserialize($sock->GET_INFO("InfluxDBRestoreArray"));
	if(count($dataX)==0){
		$InFluxBackupDatabaseDir=@file_get_contents("/etc/artica-postfix/settings/Daemons/InFluxBackupDatabaseDir");
		if($InFluxBackupDatabaseDir==null){$InFluxBackupDatabaseDir="/home/artica/influx/backup";}
		$InFluxBackupDatabaseDir=urlencode($InFluxBackupDatabaseDir);
		$sock->getFrameWork("postgres.php?restore-scandir=$InFluxBackupDatabaseDir");
		$dataX=unserialize($sock->GET_INFO("InfluxDBRestoreArray"));
	}
	
	
	$c=0;
	$tpl=new templates();
	while (list ($path, $size) = each ($dataX)){
		$c++;
		$ms5=md5($path);
		$color="black";
		$pathURL=urlencode($path);
		$import=imgsimple("20-import.png",null,"Loadjs('postgress.restore.progress.php?filename=$pathURL')");
		
		$size=FormatBytes(intval($size)/1024);
		$data['rows'][] = array(
				'id' => $ms5,
				'cell' => array(
						"<span $style>{$path}</a></span>",
						"<span $style>$size</a></span>",
						"<center>$import</center>"
						
				)
		);

	}
	
	if($c==0){json_error_show("no data");}
	$data['total'] =$c;
	echo json_encode($data);

}
?>