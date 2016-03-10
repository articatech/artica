<?php
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
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
	$title=$tpl->javascript_parse_text("{incompatible_bigdata_engine}");
	echo "YahooWin3(890,'$page?popup=yes','$title')";	
}
	

function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$html="<div class=explain style='font-size:22px'>{influx_to_release_explain}</div>
			
	<center style='margin:20px'>
			<a href=\"http://artica-proxy.com/?p=1261\" style='font-size:20px;text-decoration:underline' target=_new>Import access.log in statistics database</a>
	</center>
	<center style='margin:20px'>". button("{install_new_database_engine}","Loadjs('influx.incompatiblev1.progress.php')",33)."</center>
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function scandir_restore(){
	$ScanDir=urlencode($_POST["ScanDir"]);
	$sock=new sockets();
	$sock->getFrameWork("influx.php?restore-scandir=$ScanDir");
	
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
	$c=0;
	$tpl=new templates();
	while (list ($path, $size) = each ($dataX)){
		$c++;
		$ms5=md5($path);
		$color="black";
		
		$size=FormatBytes(intval($size)/1024);
		$data['rows'][] = array(
				'id' => $ms5,
				'cell' => array(
						"<span $style>{$path}</a></span>",
						"<span $style>$size</a></span>",
						
				)
		);

	}
	
	if($c==0){json_error_show("no data");}
	$data['total'] =$c;
	echo json_encode($data);

}
?>