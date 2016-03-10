<?php
if(posix_getuid()<>0){session_start();if(!isset($_SESSION["uid"])){if(isset($_GET["js"])){echo "document.location.href='logoff.php';";die();}}}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
include_once(dirname(__FILE__) . "/ressources/class.mysql.inc");

if($GLOBALS["VERBOSE"]){echo @implode("\n", $_GET);}

if(isset($_GET["install_status"])){install_status();exit;}
if(posix_getuid()<>0){
	if(!isset($_SESSION["uid"])){if(!isset($_GET["js"])){echo "document.location.href='logoff.php';";die();}}
	$user=new usersMenus();
	if($user->AsSystemAdministrator==false){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("alert('{ERROR_NO_PRIVS}');");
		die();exit();
	}
}

	
	
if(isset($_GET["js"])){js();exit;}	
if(isset($_GET["events-js"])){events_js();exit;}
if(isset($_GET["events-list"])){events_list();exit;}
if(isset($_GET["events-list-list"])){events_list_list();exit;}
if(isset($_GET["download-logs"])){events_download();exit;}


if(isset($_GET["popup"])){popup();exit;}
if($_GET["main"]=="softwares-available"){software_available();exit;}
if($_GET["main"]=="samba-stables"){samba_stables_available();exit;}

if(isset($_GET["software-list-by-family"])){software_available_family();exit;}

if(isset($_GET["software-list"])){software_list_by_family();exit;}

if(isset($_GET["main-start"])){echo popup_main();exit;}

if(isset($_GET["mysqlstatus"])){echo mysql_status();exit;}
if(isset($_GET["main"])){echo mysql_main_switch();exit;}
if(isset($_GET["mysqlenable"])){echo mysql_enable();exit;}
if($_GET["script"]=="mysql_enabled"){echo js_mysql_enabled();exit;}
if($_GET["script"]=="mysql_save_account"){echo js_mysql_save_account();exit;}
if(isset($_GET["install_app"])){install_app();exit;}
if(isset($_GET["InstallLogs"])){GetLogsStatus();exit;}
if(isset($_GET["TestConnection-js"])){TestConnection_js();exit;}
if(isset($_GET["testConnection"])){testConnection();exit;}
if(isset($_GET["remove"])){remove();exit;}
if(isset($_GET["uninstall_app"])){remove_perform();exit;}
if(isset($_GET["remove-refresh"])){remove_refresh();exit;}
if(isset($_GET["ui-samba"])){install_remove_services();exit;}
if(isset($_GET["clear"])){clear();exit;}
if(isset($_GET["SynSysPackages"])){SynSysPackages();exit;}
if(isset($_GET["softwares-available"])){software_available();exit;}
if(isset($_GET["remove-app-js"])){remove_app_js();exit;}
if(isset($_POST["remove-app-perform"])){remove_app_perform();exit;}
if(isset($_GET["RefreshMysqlSetup"])){RefreshMysqlSetup();exit;}

if(posix_getuid()<>0){main_page();}

function events_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{events}::{{$_GET["CODE_NAME"]}}");
	echo "YahooWin5(770,'$page?events-list=yes&CODE_NAME={$_GET["CODE_NAME"]}','$title');";
	
}

function remove_app_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$ask=$tpl->javascript_parse_text("{remove_software_ask}:{{$_GET["app"]}} ?");
$html="
var x_SetupCenterRemove$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
}
	
function SetupCenterRemove$t(){
	if(confirm('$ask')){
		var XHR = new XHRConnection();
		XHR.appendData('remove-app-perform','{$_GET["app"]}');
		XHR.sendAndLoad('$page', 'POST',x_SetupCenterRemove$t);
	}
}
SetupCenterRemove$t();

";		
	
}


function remove_app_perform(){
	
	$sock=new sockets();
	$sock->getFrameWork("services.php?remove-app={$_POST["remove-app-perform"]}");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{remove_software_perform_text}");
	
	
	
}
function events_list(){
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$database=$tpl->_ENGINE_parse_body("{database}");
	$tables_number=$tpl->_ENGINE_parse_body("{tables_number}");
	$size=$tpl->_ENGINE_parse_body("{size}");	
	$perfrom_mysqlcheck=$tpl->javascript_parse_text("{perform_mysql_check}");
	$table=$tpl->_ENGINE_parse_body("{table}");
	$table_size=$tpl->_ENGINE_parse_body("{table_size}");
	$rows_number=$tpl->_ENGINE_parse_body("{rows_number}");	
	$empty=$tpl->_ENGINE_parse_body("{empty}");	
	$perfrom_empty=$tpl->javascript_parse_text("{perform_empty_ask}");
	$tables=$tpl->javascript_parse_text("{tables}");
	$rows=$tpl->_ENGINE_parse_body("{rows}");
	$download=$tpl->_ENGINE_parse_body("{downloadz}");
	$t=time();
	$bt_default_www="{name: '$add_default_www', bclass: 'add', onpress : FreeWebAddDefaultVirtualHost},";
	$bt_webdav="{name: '$WebDavPerUser', bclass: 'add', onpress : FreeWebWebDavPerUsers},";
	//$bt_rebuild="{name: '$rebuild_items', bclass: 'Reconf', onpress : RebuildFreeweb},";
	$bt_config=",{name: '$config_file', bclass: 'Search', onpress : config_file}";	

	if((is_numeric($_GET["instance-id"]) && $_GET["instance-id"]>0)){
		$q2=new mysql_multi($_GET["instance-id"]);
		$mmultiTitle="$q2->MyServer&raquo;";
	}
	//arrow-down-18.png
	if(!is_numeric($_GET["instance-id"])){$_GET["instance-id"]=0;}
	$title=$tpl->_ENGINE_parse_body("{{$_GET["CODE_NAME"]}}::{events}");
	$buttons="buttons : [
		{name: '$download', bclass: 'Down', onpress : SetupDownloadLog},
	],";
	
	$html="
	<div id='anim-$t'></div>
	<table class='install-$t' style='display: none' id='install-$t' style='width:100%;margin:-10px'></table>
<script>
memedb='';
$(document).ready(function(){
$('#install-$t').flexigrid({
	url: '$page?CODE_NAME={$_GET["CODE_NAME"]}&events-list-list=yes',
	dataType: 'json',

	colModel : [
		
		{display: '$rows', name : 'rows', width :723, sortable : true, align: 'left'},

		
		
		
	],	
	
	$buttons

	searchitems : [
		{display: '$rows', name : 'xTables'},
		
		],
	sortname: 'total_size',
	sortorder: 'desc',
	usepager: true,
	title: '$mmultiTitle&raquo;$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 756,
	height: 350,
	singleSelect: true
	
	});   
});

function SetupDownloadLog(){
	s_PopUp('$page?download-logs=yes&CODE_NAME={$_GET["CODE_NAME"]}',1,1);

}

</script>
";
	
echo $html;	
	
}

function events_list_list(){
	$myPage=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql();
	$sql="SELECT events FROM setup_center WHERE CODE_NAME='{$_GET["CODE_NAME"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!$q->ok){echo json_error_show("<code>$q->mysql_error</code>");}
	
	
		if(trim($ligne["events"])==null){
			if(is_file("ressources/install/{$_GET["CODE_NAME"]}.dbg")){
				$array=file("ressources/install/{$_GET["CODE_NAME"]}.dbg");
			}
		}else{
			$array=explode("\n",$ligne["events"]);
		}
	
	$data = array();
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	$rp=$_POST["rp"];
	$pageStart = ($page-1)*$rp;
	$pageEnd=$pageStart+$rp;
	//echo "<H2>$pageStart -> $pageEnd</H2>";
	$data['page'] = $page;
	$data['total'] = count($array);
	$data['rows'] = array();
	$search=$_POST["query"];
	$search=string_to_regex($search);	

	if($_POST["sortorder"]=="desc"){krsort($array);}
	
		$c=0;

		while (list ($num, $ligne) = each ($array) ){
			if(trim($ligne)==null){continue;}
			if(preg_match("#\s+[0-9\.]+%#", $ligne)){continue;}
			if(strpos($ligne, "###")>0){continue;}
			if($search<>null){if(!preg_match("#$search#", $ligne)){continue;}}
			$c++;
			if($pageStart>0){
				if($c<=$pageStart){continue;}
			}
			
			if($c>$pageEnd){
					break;
			}
			
			
			
		$data['rows'][] = array(
				'id' => md5($ligne),
				'cell' => array(
					"<code style='$style'>$ligne</code>",
					)
				);			
			
		}	
		
	
	echo json_encode($data);
	
}

function RefreshMysqlSetup(){
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->getFrameWork("services.php?refresh-setup-exe=yes")));
	echo "<div style='width:95%;height:550px;overflow:auto' class=form><table style='width:100%'>";
	while (list ($num, $ligne) = each ($datas) ){
		echo "<tr><td><code style='font-size:13px'>$ligne</code></td></tr>";
		
	}
	echo "</table></div>";
	
	
}

function events_download(){
	
	
	
	header('Content-type: application/octet-stream');
	header('Content-Transfer-Encoding: binary');
	header("Content-Disposition: attachment; filename=\"{$_GET["CODE_NAME"]}.log\"");	
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé	
	

	
	$filename=dirname(__FILE__)."/ressources/install/{$_GET["CODE_NAME"]}.dbg";
	if(is_file($filename)){
		$fsize = filesize($filename);
		writelogs("$filename / $fsize bytes : {$_GET["CODE_NAME"]}",__FUNCTION__,__FILE__,__LINE__); 
		header("Content-Length: ".$fsize); 
		ob_clean();
		flush();	
		readfile($filename);	
		die();
		}
		
		
		
	writelogs("$filename no such file... USE MYSQL: {$_GET["CODE_NAME"]}",__FUNCTION__,__FILE__,__LINE__);
		$q=new mysql();
		$sql="SELECT events FROM setup_center WHERE CODE_NAME='{$_GET["CODE_NAME"]}'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){echo json_error_show("<code>$q->mysql_error</code>");}		
		
		$data=$ligne["events"];
		if(strlen($data)>100){
			@file_put_contents($filename, $data);
			readfile("ressources/install/{$_GET["CODE_NAME"]}.dbg");
			$fsize = filesize($filename); 
			header("Content-Length: ".$fsize); 
			ob_clean();
			flush();			
			readfile($filename);	

		}			
		
	

}

function TestConnection_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{application_setup}');
	$page=CurrentPageName();
	echo "YahooWin('700','$page?testConnection=yes','$title',true);";

}


function js(){
if(posix_getuid()==0){return false;}	
if(GET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__)){return;}
$page=CurrentPageName();
$tpl=new templates();
$sock=new sockets();
$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
$ArticaMetaDisableSetupCenter=$sock->GET_INFO("ArticaMetaDisableSetupCenter");
$title=$tpl->_ENGINE_parse_body('{application_setup}');
$perform_operation_on_services_scheduled=$tpl->javascript_parse_text('{perform_operation_on_services_scheduled}');
$prefix="SetupControlCenter";
$html="
var {$prefix}timerID  = null;
var {$prefix}timerID1  = null;
var {$prefix}tant=0;
var {$prefix}reste=0;
var {$prefix}x_idname;
var {$prefix}x_product='';
var {$prefix}x_num=0;
var {$prefix}x_max=0;
var {$prefix}timeout=0;

function ChargeSetupControlCenter(){
	if(document.getElementById('QuickLinksTop')){
		LoadAjax('BodyContent','$page?popup=yes&QuickLinksTop=yes');
	
	}else{
		YahooSetupControl(910,'$page?popup=yes','$title');
	}
	YahooWinHide();
	YahooWin2Hide();
	YahooWin3Hide();
	YahooWin0Hide();
	YahooWinSHide();
	setTimeout(\"{$prefix}Launch()\",300);
	}

function {$prefix}demarre(){
	if(!YahooSetupControlOpen()){return false;}
	{$prefix}tant = {$prefix}tant+1;
		if ({$prefix}tant <25 ) {                           
			{$prefix}timerID = setTimeout(\"{$prefix}demarre()\",10000);
	      } else {
					if(!YahooSetupControlOpen()){return false;}
					{$prefix}tant = 0;
					{$prefix}ChargeLogs();
					{$prefix}demarre();
	   }
	}



function {$prefix}ChargeLogs(){
	var selected = $('#main_setup_config').tabs('option', 'selected');
	var ll=$('#main_setup_config').tabs('length')-1;
	if(selected>0){	
		if(selected!==ll){
			RefreshTab('main_setup_config');
			SetupCenterRemoveRefresh();
			}
	}
}

function SetupCenterRemove(cmdline,appli){
	YahooWin(550,'$page?remove=yes&cmdline='+cmdline+'&appli='+appli);
	}
	
var x_SetupCenterRemoveRefresh= function (obj) {
	var results=obj.responseText;
	document.getElementById('remove_software').innerHTML=results;
}
	
function SetupCenterRemoveRefresh(){
	if(!YahooWinOpen()){return;}
	if(!document.getElementById('remove-app')){return;}
	if(!document.getElementById('remove-refresh')){return;}
	if(document.getElementById('remove-refresh').value!=='yes'){return;}
	var XHR = new XHRConnection();
	XHR.appendData('remove-refresh',document.getElementById('remove-app').value);
	XHR.sendAndLoad('$page', 'GET',x_SetupCenterRemoveRefresh);
}	

var x_RemoveSoftwareConfirm= function (obj) {
	var results=obj.responseText;
	document.getElementById('remove_software').innerHTML=results;
}

function RemoveSoftwareConfirm(app,cmdline){
 	var XHR = new XHRConnection();
	XHR.appendData('uninstall_app',cmdline);
	XHR.appendData('application_name',app);
	document.getElementById('remove-refresh').value='yes';
	document.getElementById('remove_software').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>';
	XHR.sendAndLoad('$page', 'GET',x_RemoveSoftwareConfirm);
	}
	
var x_ApplyInstallUninstallServices= function (obj) {
	alert('$perform_operation_on_services_scheduled');
	RefreshTab('main_setup_config');
}	
	
function ApplyInstallUninstallServices(){
	var ArticaMetaDisableSetupCenter='$ArticaMetaDisableSetupCenter';
	if(ArticaMetaDisableSetupCenter=='1'){alert('$ERROR_NO_PRIVS');return;}
	var XHR = new XHRConnection();
	XHR.appendData('ui-samba',document.getElementById('samba').value);
	XHR.appendData('ui-postfix',document.getElementById('postfix').value);
	XHR.appendData('ui-squid',document.getElementById('squid').value);
	document.getElementById('img_postfix').src='img/wait_verybig.gif';
	document.getElementById('img_samba').src='img/wait_verybig.gif';
	document.getElementById('img_squid').src='img/wait_verybig.gif';
	XHR.sendAndLoad('$page', 'GET',x_ApplyInstallUninstallServices);
	
}

var x_InstallRefresh= function (obj) {
	RefreshTab('main_setup_config');
}	

function InstallRefresh(){
	var XHR = new XHRConnection();
	XHR.appendData('clear','yes');
	XHR.sendAndLoad('$page', 'GET',x_InstallRefresh);
}
	  
	
var x_ApplicationSetup= function (obj) {
	var results=obj.responseText;
	alert(results);
	{$prefix}ChargeLogs();
}
	
function ApplicationSetup(app){
    var XHR = new XHRConnection();
	XHR.appendData('install_app',app);
	XHR.sendAndLoad('$page', 'GET',x_ApplicationSetup);
	}
	
function InstallLogs(app){
	{$prefix}timeout=0;
	{$prefix}x_product=app;
	YahooWin('630','$page?InstallLogs='+ app,app);
	setTimeout(\"{$prefix}LoupeProgress()\",500);
	
}
function {$prefix}LoupeProgress(){
	{$prefix}timeout={$prefix}timeout+1;
	if({$prefix}timeout>50){alert('timeout');return;}
	
	if(!document.getElementById('loupe-logs')){
		setTimeout(\"{$prefix}LoupeProgress()\",500);
		return;
	}
	{$prefix}timeout=0;
	Loadjs('setup.index.progress.php?product='+{$prefix}x_product);
   
	}

	function TestConnection(){
		YahooWin('700','$page?testConnection=yes','$title',true);
	}



function {$prefix}Launch(){
	{$prefix}timeout={$prefix}timeout+1;
	if({$prefix}timeout>10){
		alert('timeout!');
		return;
	}
	
	if(!document.getElementById('main_setup_config')){
		setTimeout(\"{$prefix}Launch()\",800);
	}
	
	{$prefix}timeout=0;
	{$prefix}demarre();
	{$prefix}ChargeLogs();
}
	


ChargeSetupControlCenter();

";

SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$html);
echo $html;
	
	
}

function popup_main(){
$page=CurrentPageName();
$users=new usersMenus();
if($users->PROXYTINY_APPLIANCE){
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("<p class=text-error>{disabled_tiny_proxy}</p>");
	return;
	
}


 $html="<div id='main_start_{$_GET["main-start"]}'></div>
 
 <script>
 	LoadAjax('main_start_{$_GET["main-start"]}','$page?main={$_GET["main-start"]}');
 </script>
 
 ";
echo $html;	
}


function popup(){
$tpl=new templates();
echo $tpl->_ENGINE_parse_body(tabs());
	
}

function software_available(){

software_available_family();
}

function software_available_family(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql();	
	$users=new usersMenus();
	
	$sql="SELECT CODE_NAME,CODE_NAME_STRING  FROM setup_center WHERE (LENGTH(CODE_NAME_STRING)=0) OR (CODE_NAME_STRING LIKE '{%')";
	
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "<H2>$q->mysql_error</H2>";}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$productname=$tpl->_ENGINE_parse_body("{{$ligne["CODE_NAME"]}}");
		$q->QUERY_SQL("UPDATE setup_center SET CODE_NAME_STRING='".addslashes($productname)."' WHERE CODE_NAME='{$ligne["CODE_NAME"]}'","artica_backup");
		
	}
	
	$sql="SELECT FAMILY FROM setup_center GROUP BY FAMILY ORDER BY FAMILY";
	$q=new mysql();
	$page=CurrentPageName();
	$tpl=new templates();
	$results=$q->QUERY_SQL($sql,"artica_backup");	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$famName=$tpl->_ENGINE_parse_body("{SETUPCENT_{$ligne["FAMILY"]}}");
		$FAMS[$ligne["FAMILY"]]="\n\t{display: '$famName', name : '{$ligne["FAMILY"]}'}";
	}	
	
	if($users->KASPERSKY_WEB_APPLIANCE){
		unset($FAMS["FILESHARE"]);
		unset($FAMS["SMTP"]);
		unset($FAMS["VIRTUALIZATION"]);
		unset($FAMS["MAILBOX"]);
		unset($FAMS["WEB"]);
	}	
	
	$softwares=$tpl->_ENGINE_parse_body("{softwares}");
	$current=$tpl->_ENGINE_parse_body("{current}");
	$available=$tpl->_ENGINE_parse_body("{available}");
	$family=$tpl->_ENGINE_parse_body("{family}");
	$refresh=$tpl->_ENGINE_parse_body("{refresh}");
	$refreshTXT=$tpl->javascript_parse_text("{refresh}");
	$expand=$tpl->_ENGINE_parse_body("{expand}");
	while (list ($a, $b) = each ($FAMS)){
		$famfam[]=$b;
		
	}	
	$softwares_row=421;
	$table_width=877;
	$table_height=400;
	$margin_left="-15";
	if(isset($_GET["softwares-row"])){$softwares_row=$_GET["softwares-row"];}
	if(isset($_GET["table-width"])){$table_width=$_GET["table-width"];}
	if(isset($_GET["height-table"])){$table_height=$_GET["height-table"];}
	if(isset($_GET["margin-left"])){$margin_left=$_GET["margin-left"];}
	
	
	
	$buttons="buttons : [
		{name: '$refresh', bclass: 'Reconf', onpress : RefreshMysqlSetup},
		{name: '$expand', bclass: 'Down', onpress : Expandsetup_center_table},
	],";
	
	$t=time();
	$html="
	<div style='margin-left:{$margin_left}px'>
	<table class='setup_center_table' style='display: none' id='setup_center_table' style='width:99%'></table>
	</div>
<script>
var rowSquidTask='';
$(document).ready(function(){
$('#setup_center_table').flexigrid({
	url: '$page?software-list=yes&FAMILY={$_GET["FAMILY"]}',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'ID', width : 32, sortable : true, align: 'center'},
		{display: '$softwares', name : 'TaskType', width : $softwares_row, sortable : false, align: 'left'},
		{display: '$current', name : 'TimeDescription', width : 112, sortable : false, align: 'left'},
		{display: '$available', name : 'run', width : 112, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'run1', width : 32, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'enable', width : 32, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'delete', width : 32, sortable : false, align: 'center'}
	],
	$buttons
	searchitems : [
		{display: '$softwares', name : 'softwares'},". @implode(",", $famfam)."
		],
	sortname: 'CODE_NAME',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: $table_width,
	height: '400',
	singleSelect: true
	
	});   
});	

var x_ApplicationSetup= function (obj) {
	var results=obj.responseText;
	alert(results);
	$('#setup_center_table').flexReload();
}
	
function ApplicationSetup(app){
    var XHR = new XHRConnection();
	XHR.appendData('install_app',app);
	XHR.sendAndLoad('$page', 'GET',x_ApplicationSetup);
	}
	
function RefreshMysqlSetup(){
	YahooWinBrowse('550','$page?RefreshMysqlSetup=yes','$refreshTXT');
}
function Expandsetup_center_table(){
	$('.bDiv')[0].style.height = '800px';
	}

</script>

";
	
echo $html;
	
	
}



function software_list_by_family(){
	$sock=new sockets();
	$ARTICA_MAKE_STATUS=unserialize(base64_decode($sock->getFrameWork("services.php?ARTICA-MAKE=yes")));
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$search='%';
	$table="setup_center";
	$page=1;
	$ORDER="ORDER BY ID DESC";
	$database="artica_backup";
	if($_GET["FAMILY"]<>null){
		$_POST["qtype"]=$_GET["FAMILY"];
		if($_POST["query"]==null){$_POST["query"]="*";}
	}

	
	$SOFTWARE_REMOVABLE=array(
		
		"APP_POSTFIX"=>true,
		"APP_SCANNED_ONLY"=>true,
		"APP_SAMBA"=>true,
		"APP_SAMBA35"=>true,
		"APP_SAMBA36"=>true,
		"APP_DNSMASQ"=>true,
	);	
	
	$total=0;
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("No data",1);}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}	
	
		if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND ( (FAMILY='{$_POST["qtype"]}' AND CODE_NAME_STRING LIKE '$search') OR (FAMILY='{$_POST["qtype"]}' AND CODE_NAME LIKE '$search'))";
		if($_POST["qtype"]=="softwares"){$searchstring="AND ( (CODE_NAME_STRING LIKE '$search') OR (CODE_NAME LIKE '$search'))";}
		
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show("$q->mysql_error<hr>$sql",1);}	
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show("$q->mysql_error<hr>$sql",1);}	
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	
	
	
	$data = array();$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();	
	if(!$q->ok){json_error_show("$q->mysql_error<hr>$sql",1);}	
	$span="<span style='font-size:14px;font-weight:bold'>";
	$span18="<span style='font-size:18px;font-weight:bold'>";
	
	while ($ligne = mysql_fetch_assoc($results)) {
		if($ligne["CODE_NAME"]=="APP_OPENDKIM"){continue;}
		$productname=$ligne["CODE_NAME_STRING"];
		if(trim($productname)==null){$productname="{{$ligne["CODE_NAME"]}}";}
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$color="black";
		$events="&nbsp;";
		$progress=null;
		$install=imgsimple("32-install-soft.png","{install_upgrade}","ApplicationSetup('{$ligne["CODE_NAME"]}')");
		$ID=md5(serialize($ligne));
		if($ligne["nextversionstring"]==null){$ligne["nextversionstring"]="0.0.0";}
		$CODE_NAME_ABOUT="&nbsp;";
		if(strlen($ligne["CODE_NAME_ABOUT"])>2){
			$CODE_NAME_ABOUT="<div style='font-size:10px'>{{$ligne["CODE_NAME_ABOUT"]}}</div>";
		}			
		
		if($ligne["curversionstring"]==null){
			$color="#8a8a8a";
			$full=base64_encode($tpl->_ENGINE_parse_body("{product_not_installed_explain}"));
			$ligne["curversionstring"]=$tpl->_ENGINE_parse_body("<a href=\"javascript:blur();\" Onclick=\"javascript:Loadjs('admin.index.php?json-error-js=$full');\" 
			style=\"font-size:10px;font-weight:normal;nodiv;text-decoration:underline\">{product_not_installed_explain}</a>");
		}
	
		$full=base64_encode($tpl->_ENGINE_parse_body("{codename_setup_explain}&nbsp;{$ligne["CODE_NAME"]}"));
		$exp_codename=
		"<a href=\"javascript:blur();\" Onclick=\"javascript:Loadjs('admin.index.php?json-error-js=$full');\" 
		style=\"font-size:10px;font-weight:normal;nodiv;text-decoration:underline\">{$ligne["CODE_NAME"]}</a>";
		
		
		
		
		if($ligne["CODE_NAME"]=="APP_CYRUS_IMAP"){
			$install="<img src='img/32-install-soft-grey.png'>";
			
		}
		
		if($ligne["upgrade"]==1){
			$install=imgsimple("software-synchronize-32.png","{failed}:{install_upgrade}","ApplicationSetup('{$ligne["CODE_NAME"]}')");
			$progress="<div style='float:right'><strong style='color:#E14542'>{$ligne["progress"]}% - {$ligne["progress_text"]}</strong></div><div></div>";
		}
		
		if($ligne["progress"]>100){
			$install=imgsimple("software-remove-32.png","{failed}:{install_upgrade}","ApplicationSetup('{$ligne["CODE_NAME"]}')");
			
		}
		
		if(strlen($ligne["events"])>5){
			$events= imgsimple("events-32.png","{events}","Loadjs('$MyPage?events-js=yes&CODE_NAME={$ligne["CODE_NAME"]}')");
		}else{
			if(is_file("ressources/install/{$ligne["CODE_NAME"]}.dbg")){
				$events= imgsimple("events-32.png","{events}","Loadjs('$MyPage?events-js=yes&CODE_NAME={$ligne["CODE_NAME"]}')");
			}
		}
		
		$runningsinc=null;
		if(isset($ARTICA_MAKE_STATUS[$ligne["CODE_NAME"]])){
			$install="<img src=img/ajax-loader.gif>";
			$runningsinc="<div><i style='font-weight:bold;color:black'>{running}: {since}: {$ARTICA_MAKE_STATUS[$ligne["CODE_NAME"]]}</i></div>";
		}
		
		$uninstall=imgsimple("check-32-grey.png");
		
		if($SOFTWARE_REMOVABLE[$ligne["CODE_NAME"]]){
			$uninstall=imgsimple("software-remove-32.png",null,"Loadjs('setup.index.php?remove-app-js=yes&app={$ligne["CODE_NAME"]}");
		}
		
	$data['rows'][] = array(
		'id' =>$ID,
		'cell' => array(
		$install,
		$tpl->_ENGINE_parse_body("<span style='font-size:14px;font-weight:bold;color:$color'>$productname
		<div style='font-size:11px'><i style='font-size:10px;font-weight:normal'>{codename}:$exp_codename</i>&nbsp;|&nbsp;<i style='font-size:10px;font-weight:normal'>{SUBFAMILY_{$ligne["SUBFAMILY"]}}</i>$progress$runningsinc</div>
		</span>"),
		"$span18{$ligne["curversionstring"]}</a></span>",
		"$span18{$ligne["nextversionstring"]}</a></span>",
		$events,
		"$span$CODE_NAME_ABOUT</a></span>",
		$uninstall)
		);		
	}
echo json_encode($data);		
	

}



function remove(){
	
	$app=$_GET["appli"];
	$cmdline=$_GET["cmdline"];
	
	$html="
	<table style='width:100%'>
	<tr>
		<td valign='top'><img src='img/software-remove-128.png'></td>
		<td valign='top'>
		<center>
		<H3 style='font-size:22px;color:#005447'>{uninstall} {{$app}}</H3>
		<hr>
		<p style='font-size:14px'>{are_you_sure_to_delete} {{$app}} ???</p>
		<hr>
		
		</center>
		<input type='hidden' id='remove-refresh' value='no'>
		<input type='hidden' id='remove-app' value='$app'>
		<div id='remove_software' style='width:100%;height:300px;overflow:auto'>
		<center>". button("{uninstall} {{$app}}","RemoveSoftwareConfirm('$app','$cmdline')")."</center>
		</div>
		</td>
	</tr>
	</table>
		
	
	";
	
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);	
}

function remove_perform(){
	$cmdline=base64_encode($_GET["uninstall_app"]);
	$sock=new sockets();
	$datas= unserialize(base64_decode($sock->getFrameWork("cmd.php?uninstall-app=".$cmdline."&app={$_GET["application_name"]}")));
	if(is_array($datas)){
		while (list ($num, $ligne) = each ($datas) ){
			echo "<div><code style='font-size:11px'>$ligne</code></div>";
		}
	}
	
}

function remove_refresh(){
	
	$app=$_GET["remove-refresh"];
	$file="/usr/share/artica-postfix/ressources/logs/UNINSTALL_$app";
	$datas=explode("\n",@file_get_contents($file));
	if(is_array($datas)){
		while (list ($num, $ligne) = each ($datas) ){
			echo "<div><code style='font-size:11px'>$ligne</code></div>";
		}
	}	
}

function index(){
	if(GET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__)){return;}
	$page=CurrentPageName();
	$back=Paragraphe("setup-90-back.png","{back_system}","{back_system_text}","javascript:YahooSetupControlHide();Loadjs('system.index.php?js=yes&load-tab=services')");
	$prefix="SetupControlCenter";
	
	$synchro=Paragraphe("software-synchronize-64.png","{sync_packages}","{sync_packages_explain}","javascript:SynSysPackages()");
	$refresh=Paragraphe("64-recycle.png","{refresh_index_file}","{refresh_index_file}","javascript:TestConnection()");
	$additionals=Paragraphe("64-update-settings.png","{mandatories_packages}","{mandatories_packages_text}","javascript:Loadjs('setup-ubuntu.php')");
	
	// 
$intro="
<div class=explain style='font-size:14px'>{setup_index_explain}</div>	
<center>
<table style='width:565px' class=form>
	<tbody>
	<tr>
	<td width=1% valign='top'>
		<img src='img/software-install-256.png' style='margin-rigth:30px;margin-left:30px'>
	</td>
	<td valign='top'>
		
		$synchro
		$refresh
		$additionals
	</td>
	</tr>
	</tbody>
</table>
</center>
<input type='hidden' id='tabnum' name='tbanum' value='{$_GET["main"]}'>
<script>
	setTimeout(\"{$prefix}Launch()\",300);
	var x_SynSysPackages= function (obj) {
		var results=obj.responseText;
		if(results.length>2){alert(results);}
	}
	
function SynSysPackages(app){
    var XHR = new XHRConnection();
	XHR.appendData('SynSysPackages','yes');
	XHR.sendAndLoad('$page', 'GET',x_SynSysPackages);
	}	
</script>";


$html="$intro";
$tpl=new templates();
$html=$tpl->_ENGINE_parse_body($html);
SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$html);
echo $html;
}







	
function main_page(){
	$prefix="SetupControlCenter";	
$page=CurrentPageName();
	if($_GET["hostname"]==null){
		$user=new usersMenus();
		$_GET["hostname"]=$user->hostname;}
		$tpl=new templates();
		$title=$tpl->_ENGINE_parse_body('{refresh_index_file}');
	
	$html=
"<span id='scripts'><script type=\"text/javascript\" src=\"$page?script=load_functions\"></script></span>	
<script language=\"JavaScript\">       
".default_scripts()."
</script>		
	
	<table style='width:99%' class=form>
	<tr>
	<td width=1% valign='top'><img src='img/setup-256.png'style='margin-right:30px;margin-bottom:5px'></td>
	<td valign='top'>
		<div id='mysql_status'></div>
		<table style='width:100%'>
		<tr>
			<td valign='top'>
		<p class='caption'>{setup_index_explain}</p>
		</td>
		<td><td align='right'>" . Paragraphe("64-recycle.png","{refresh_index_file}","{refresh_index_file}","javascript:TestConnection()")."
		</td>
		</tr>
		</table>
	</td>
	</tr>
	<tr>
		<td colspan=2 valign='top'>
			<table style='width:100%'>	
			<tr>
			<td valign='top'>
				<div id='main_setup_config'></div>
			</td>
			</tr>
			</table>
		</td>
	</tr>
	</table>
	<script>{$prefix}{demarre();{$prefix}ChargeLogs();LoadAjax('main_setup_config','$page?main=$num&hostname={$_GET["hostname"]}');</script>
	
	
	";
	
	$tpl=new template_users('{application_setup}',$html,0,0,0,0,$cfg);
	
	$sock=new sockets();
	$sock->getFrameWork('cmd.php?SetupCenter=yes');
	
	echo $tpl->web_page;
	
	
	
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();	
	$array["index"]='{index}';
	$array["softwares-available"]='{softwares_database}';
	$array["samba-stables"]='{samba_stables_versions}';
	
	if(!is_file("ressources/old-samba.ini")){
		$sock=new sockets();
		$sock->getFrameWork("cmd.php?SetupIndexFile=yes");
	}
	
if(isset($_GET["QuickLinksTop"])){$margin="margin-top:10px";$fontsize="font-size:14px";}
	while (list ($num, $ligne) = each ($array) ){
		if($_GET["main"]==$num){$class="id=tab_current";}else{$class=null;}
		$ligne=$tpl->_ENGINE_parse_body($ligne);
		$ligne_text= html_entity_decode($ligne,ENT_QUOTES,"UTF-8");


		$html[]= "<li><a href=\"$page?main-start=$num\"><span style='$fontsize'>$ligne_text</span></a></li>\n";
		
			
		}
	$tpl=new templates();
	return build_artica_tabs($html, "main_setup_config");
	
}


function mysql_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$array["index"]='{index}';
	$sock=new sockets();
	if($users->SQUID_INSTALLED){
		$SQUIDEnable=trim($sock->GET_INFO("SQUIDEnable"));
		if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
		if($SQUIDEnable==0){$user->SQUID_INSTALLED=false;}	
	}
	$FreeWebLeftMenu=$sock->GET_INFO("FreeWebLeftMenu");
	$EnableGroupWareScreen=$sock->GET_INFO("EnableGroupWareScreen");
	
	
	if(!is_numeric($FreeWebLeftMenu)){$FreeWebLeftMenu=1;}	
	$ArticaMetaDisableSetupCenter=$sock->GET_INFO("ArticaMetaDisableSetupCenter");
	
	$KASPERSKY_APPLIANCE=FALSE;
	if($users->KASPERSKY_SMTP_APPLIANCE){$KASPERSKY_APPLIANCE=TRUE;}
	if($users->KASPERSKY_WEB_APPLIANCE){$KASPERSKY_APPLIANCE=TRUE;}
	if($users->OPENVPN_APPLIANCE){$OPENVPN_APPLIANCE=TRUE;}		
	
	
	if($users->POSTFIX_INSTALLED){
		$array["smtp_packages"]='{smtp_packages}';
	}
	$array["stat_packages"]='{stat_packages}';
	if(!$KASPERSKY_APPLIANCE){
		$array["web_packages"]='{web_packages}';
	}
	
	if($users->SQUID_INSTALLED){
		$array["proxy_packages"]='{proxy_packages}';
	}else{
		if($users->KAV4PROXY_INSTALLED){
			$array["proxy_packages"]='{proxy_packages}';
		}else{
			if($users->KASPERSKY_WEB_APPLIANCE){
				$array["proxy_packages"]='{proxy_packages}';
			}
		}
		
	}
	$array["system_packages"]='{setup_center_system}';
	
	if($users->SAMBA_INSTALLED){
		if(!$KASPERSKY_APPLIANCE){
			$array["samba_packages"]='{fileshare}';
		}
	}
	
	if(!$KASPERSKY_APPLIANCE){
		if($ArticaMetaDisableSetupCenter<>1){
			$array["service_family"]="{services_family}";
		}
	}
	
	if($KASPERSKY_APPLIANCE){
		unset($array["service_family"]);
		unset($array["samba_packages"]);
		unset($array["web_packages"]);
		unset($array["smtp_packages"]);
	}
	

	if($ArticaMetaDisableSetupCenter<>1){
		if(!$KASPERSKY_APPLIANCE){
			$array["vdi"]="{virtual_desktop_infr}";
		}
	}
	
	if($users->AS_VPS_CLIENT){unset($array["vdi"]);}
	
	if($OPENVPN_APPLIANCE){
		unset($array["service_family"]);
		unset($array["samba_packages"]);
		unset($array["smtp_packages"]);		
		unset($array["web_packages"]);
		unset($array["vdi"]);
	}
	
	if($users->ZARAFA_APPLIANCE){
		unset($array["web_packages"]);
		unset($array["vdi"]);
		unset($array["service_family"]);
	}	
	
	if($FreeWebLeftMenu==0){unset($array["web_packages"]);}
	
if(isset($_GET["QuickLinksTop"])){$margin="margin-top:10px";$fontsize="font-size:14px";}


if($users->LinuxDistriCode<>"DEBIAN"){if($users->LinuxDistriCode<>"UBUNTU"){unset($array["vdi"]);}}

	
	if(count($array)>7){$fontsize="font-size:12.5px";}

	while (list ($num, $ligne) = each ($array) ){
		if($_GET["main"]==$num){$class="id=tab_current";}else{$class=null;}
		$ligne=$tpl->_ENGINE_parse_body($ligne);
		$ligne_text= html_entity_decode($ligne,ENT_QUOTES,"UTF-8");
		if(!$OPENVPN_APPLIANCE){
			if(strlen($ligne_text)>17){
				$ligne_text=substr($ligne_text,0,14);
				$ligne_text=htmlspecialchars($ligne_text)."...";
				$ligne_text=texttooltip($ligne_text,$ligne,null,null,1);
				}
		}
		//$html=$html . "<li><a href=\"javascript:ChangeSetupTab('$num')\" $class>$ligne</a></li>\n";
		if($num=="vdi"){
			$html[]= "<li><a href=\"setup.vdi.php\"><span style='$fontsize'>$ligne_text</span></a></li>\n";
			continue;
		}
			
		$html[]= "<li><a href=\"$page?main-start=$num\"><span style='$fontsize'>$ligne_text</span></a></li>\n";
		
			
		}
	$tpl=new templates();
	
	
	return "
	<div id=main_setup_config style='width:100%;height:590px;overflow:auto;background-color:white;$margin'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_setup_config').tabs();
			

			});
		</script>";		
}



function mysql_status(){
	$tpl=new templates();
	$ini=new Bs_IniHandler();
	$sock=new sockets();
	$ini->loadString($sock->getfile('articamakestatus'));
	$status=DAEMON_STATUS_ROUND("ARTICA_MAKE",$ini,null);
	echo $tpl->_ENGINE_parse_body($status);
	}
function mysql_main_switch(){
	$tab=null;
	
	$users=new usersMenus();
	$sock=new sockets();
	$sock->getFrameWork('cmd.php?SetupCenter=yes');
	$GLOBALS["ArticaMetaDisableSetupCenter"]=$sock->GET_INFO("ArticaMetaDisableSetupCenter");
	
	if(!isset($_GET["refresh"])){
	 if($_GET["main"]<>"index"){
		echo "
			<input type='hidden' id='main_array_setup_install_selected' value='{$_GET["main"]}'>
			<div style='text-align:right'>". imgtootltip("refresh-24.png","{refresh}","InstallRefresh()")."</div>
			<div id='main_array_setup_install_{$_GET["main"]}'>";
		}
	}
	switch ($_GET["main"]) {
		case "index":echo index();break;
		case "smtp_packages":echo $tab.smtp_packages();break;
		case "stat_packages":echo $tab.stat_packages();break;
		case "web_packages":echo $tab.web_packages();break;
		case "proxy_packages":echo $tab.proxy_packages();break;
		case "samba_packages":echo $tab.samba_packages();break;
		case "system_packages":echo $tab.system_packages();break;
		case "xapian_packages":echo $tab.xapian_packages();break;
		case "service_family":echo services_family();break;
		
	
		default:
			if($users->POSTFIX_INSTALLED){
				echo $tab.smtp_packages();
				exit;
			}
			
			if($users->SQUID_INSTALLED){
				echo $tab.proxy_packages();
				exit;
			}

			if($users->SAMBA_INSTALLED){
				echo $tab.samba_packages();
				exit;
			}			
			echo $tab.system_packages();exit;
			
	}
	
		if(!isset($_GET["refresh"])){echo "</div>";}
}


function clear(){
	$sock=new sockets();
	$sock->SET_APC_STORE("GlobalApplicationsStatus",null);
	$sock->APC_CLEAN();
}

function smtp_packages(){
	


$users=new usersMenus();
$sock=new sockets();
$GlobalApplicationsStatus=$sock->APC_GET("GlobalApplicationsStatus",2);
$MEM_TOTAL_INSTALLEE=$users->MEM_TOTAL_INSTALLEE;
if($GlobalApplicationsStatus==null){
	$GlobalApplicationsStatus=base64_decode($sock->getFrameWork('cmd.php?Global-Applications-Status=yes'));
	$sock->APC_SAVE("GlobalApplicationsStatus",$GlobalApplicationsStatus);
	$GLOBALS["GlobalApplicationsStatus"]=$GlobalApplicationsStatus;
}

$html="

<br>
<table style='width:100%;padding:2px:margin:3px;border:1px solid #CCCCCC'>
<tr style='background-color:#CCCCCC'>
<td>&nbsp;</td>
<td style='font-size:13px'><strong>{software}</strong></td>
<td style='font-size:13px' nowrap><strong>{current_version}</strong></td>
<td style='font-size:13px' nowrap><strong>{available_version}</strong></td>
<td style='font-size:13px'>&nbsp;</td>
<td style='font-size:13px'><strong>{status}</strong></td>
</tr>";




if($users->POSTFIX_INSTALLED){
	
	
	$html=$html.spacer('{CORE_PRODUCTS}');
	$html=$html.BuildRows("APP_POSTFIX",$GlobalApplicationsStatus,"postfix");
	if(!$users->ZARAFA_APPLIANCE){
		if(!$users->ZARAFA_INSTALLED ){
			$html=$html.BuildRows("APP_CYRUS_IMAP",$GlobalApplicationsStatus,"cyrus-imapd",true);
			if($users->cyrus_imapd_installed){$html=$html.BuildRows("APP_ROUNDCUBE3",$GlobalApplicationsStatus,"roundcubemail3");}
		}
		
	}
	if($MEM_TOTAL_INSTALLEE>500000){
		$html=$html.BuildRows("APP_ZARAFA",$GlobalApplicationsStatus,"zarafa");
		$html=$html.BuildRows("APP_ZARAFA6",$GlobalApplicationsStatus,"zarafa6-i386");
	}
	
	
	$html=$html.spacer('{fetch_mails_family_products}');
	$html=$html.BuildRows("APP_FETCHMAIL",$GlobalApplicationsStatus,"fetchmail");
	$html=$html.BuildRows("APP_IMAPSYNC",$GlobalApplicationsStatus,"imapsync");
	$html=$html.BuildRows("APP_OFFLINEIMAP",$GlobalApplicationsStatus,"offlineimap");

		
	
	$html=$html.spacer('{CONNEXIONS_FILTERS_PRODUCTS}');
	$html=$html.BuildRows("APP_MILTERGREYLIST",$GlobalApplicationsStatus,"milter-greylist");
	if($MEM_TOTAL_INSTALLEE>700000){$html=$html.BuildRows("APP_CLUEBRINGER",$GlobalApplicationsStatus,"cluebringer");}
	if($MEM_TOTAL_INSTALLEE>700000){$html=$html.BuildRows("APP_OPENDKIM",$GlobalApplicationsStatus,"opendkim");}
	if($MEM_TOTAL_INSTALLEE>700000){$html=$html.BuildRows("APP_MILTER_DKIM",$GlobalApplicationsStatus,"dkim-milter");}
	if($MEM_TOTAL_INSTALLEE>700000){$html=$html.BuildRows("APP_CROSSROADS",$GlobalApplicationsStatus,"crossroads");}
	
	
	if(!$users->KASPERSKY_SMTP_APPLIANCE){
		if($MEM_TOTAL_INSTALLEE>700000){
			$html=$html.spacer('{CONTENTS_FILTERS_PRODUCTS}');
			$html=$html.BuildRows("APP_SPAMASSASSIN",$GlobalApplicationsStatus,"Mail-SpamAssassin");
			$html=$html.BuildRows("APP_AMAVISD_MILTER",$GlobalApplicationsStatus,"amavisd-milter");
			$html=$html.BuildRows("APP_AMAVISD_NEW",$GlobalApplicationsStatus,"amavisd-new");
			$html=$html.BuildRows("APP_ASSP",$GlobalApplicationsStatus,"assp");
			$html=$html.BuildRows("APP_CLAMAV_MILTER",$GlobalApplicationsStatus,"clamav");
		}
	}
	if($MEM_TOTAL_INSTALLEE>700000){
		$html=$html.spacer('{LICENSED_FILTERS_PRODUCTS}');
		$html=$html.BuildRows("APP_KAS3",$GlobalApplicationsStatus,"kas");
		$html=$html.BuildRows("APP_KAVMILTER",$GlobalApplicationsStatus,"kavmilter");
	}
	
	$html=$html.spacer('{MAIL_TOOLS}');
	
	if($users->ZARAFA_INSTALLED){
		$html=$html.BuildRows("APP_Z_PUSH",$GlobalApplicationsStatus,"z-push");
	}
	$html=$html.BuildRows("APP_OPENEMM",$GlobalApplicationsStatus,"OpenEMM");
	$html=$html.BuildRows("APP_OPENEMM_SENDMAIL",$GlobalApplicationsStatus,"sendmail");
	$html=$html.BuildRows("APP_ALTERMIME",$GlobalApplicationsStatus,"altermime");
	if(!$users->KASPERSKY_SMTP_APPLIANCE){$html=$html.BuildRows("APP_POMMO",$GlobalApplicationsStatus,"pommo");}
	$html=$html.BuildRows("APP_MSMTP",$GlobalApplicationsStatus,"msmtp");
	if($MEM_TOTAL_INSTALLEE>700000){$html=$html.BuildRows("APP_EMAILRELAY",$GlobalApplicationsStatus,"emailrelay");}
	$html=$html.BuildRows("APP_STUNNEL",$GlobalApplicationsStatus,"stunnel");
	
	
	$html=$html.spacer('{STATS_TOOLS}');
	$html=$html.BuildRows("APP_MAILSPY",$GlobalApplicationsStatus,"mailspy");
	$html=$html.BuildRows("APP_PFLOGSUMM",$GlobalApplicationsStatus,"pflogsumm");
	
	
	
	
	
	}

$html=$html."</table>";
	
if(posix_getuid()==0){
		file_put_contents(dirname(__FILE__)."/ressources/logs/setup.index.".__FUNCTION__.".html",$html);
		return null;
}

$tpl=new templates();
return $tpl->_ENGINE_parse_body($html);
	
}
function stat_packages(){


$sock=new sockets();
$users=new usersMenus();
if($users->KASPERSKY_SMTP_APPLIANCE){$KASPERSKY_APPLIANCE=TRUE;}
if($users->KASPERSKY_WEB_APPLIANCE){$KASPERSKY_APPLIANCE=TRUE;}
$GlobalApplicationsStatus=base64_decode($sock->getFrameWork('cmd.php?Global-Applications-Status=yes'));
$html="

<br>
<table style='width:100%;padding:2px:margin:3px;border:1px solid #CCCCCC'>
<tr style='background-color:#CCCCCC'>
<td>&nbsp;</td>
<td style='font-size:13px'><strong>{software}</strong></td>
<td style='font-size:13px'><strong>{current_version}</strong></td>
<td style='font-size:13px'><strong>{available_version}</strong></td>
<td style='font-size:13px'>&nbsp;</td>
<td style='font-size:13px'><strong>{status}</strong></td>
</tr>";
if(!$KASPERSKY_APPLIANCE){$html=$html.BuildRows("APP_AWSTATS",$GlobalApplicationsStatus,"awstats");}
if(!$KASPERSKY_APPLIANCE){$html=$html.BuildRows("APP_COLLECTD",$GlobalApplicationsStatus,"collectd");}
if(!$KASPERSKY_APPLIANCE){$html=$html.spacer('&nbsp;');}
$html=$html.BuildRows("APP_GNUPLOT",$GlobalApplicationsStatus,"gnuplot");
$html=$html.BuildRows("APP_DSTAT",$GlobalApplicationsStatus,"dstat");
$html=$html.BuildRows("APP_VNSTAT",$GlobalApplicationsStatus,"vnstat");
$html=$html.spacer('&nbsp;');

if($users->POSTFIX_INSTALLED){
	$html=$html.BuildRows("APP_ISOQLOG",$GlobalApplicationsStatus,"isoqlog");
}

$html=$html."</table>";

if(posix_getuid()==0){
		file_put_contents(dirname(__FILE__)."/ressources/logs/setup.index.".__FUNCTION__.".html",$html);
		return null;
}

$tpl=new templates();
return $tpl->_ENGINE_parse_body($html);
	
}

function web_packages(){

	
$sock=new sockets();
$users=new usersMenus();
$GlobalApplicationsStatus=$sock->APC_GET("GlobalApplicationsStatus",2);
if($GlobalApplicationsStatus==null){
	$GlobalApplicationsStatus=base64_decode($sock->getFrameWork('cmd.php?Global-Applications-Status=yes'));
	$sock->APC_SAVE("GlobalApplicationsStatus",$GlobalApplicationsStatus);
	$GLOBALS["GlobalApplicationsStatus"]=$GlobalApplicationsStatus;
}
$html="

<br>
<table style='width:100%;padding:2px:margin:3px;border:1px solid #CCCCCC'>
<tr style='background-color:#CCCCCC'>
<td>&nbsp;</td>
<td style='font-size:13px'><strong>{software}</strong></td>
<td style='font-size:13px'><strong>{current_version}</strong></td>
<td style='font-size:13px'><strong>{available_version}</strong></td>
<td style='font-size:13px'>&nbsp;</td>
<td style='font-size:13px'><strong>{status}</strong></td>
</tr>";

$html=$html.BuildRows("APP_PUREFTPD",$GlobalApplicationsStatus,"pure-ftpd");
$html=$html.BuildRows("APP_PHPMYADMIN",$GlobalApplicationsStatus,"phpMyAdmin");
$html=$html.BuildRows("APP_MOD_PAGESPEED",$GlobalApplicationsStatus,"mod-pagespeedDEBi386");


	$html=$html.spacer('Groupwares');
  if(!$users->KASPERSKY_SMTP_APPLIANCE){
		$html=$html.BuildRows("APP_DOTCLEAR",$GlobalApplicationsStatus,"dotclear");
		$html=$html.BuildRows("APP_LMB",$GlobalApplicationsStatus,"lmb");
		$html=$html.BuildRows("APP_OPENGOO",$GlobalApplicationsStatus,"opengoo");
		$html=$html.BuildRows("APP_GROUPOFFICE",$GlobalApplicationsStatus,"groupoffice-com");
		$html=$html.BuildRows("APP_DRUPAL",$GlobalApplicationsStatus,"drupal");
		$html=$html.BuildRows("APP_DRUPAL7",$GlobalApplicationsStatus,"drupal7");
		$html=$html.BuildRows("APP_DRUSH7",$GlobalApplicationsStatus,"drush7");
		$html=$html.BuildRows("APP_PIWIGO",$GlobalApplicationsStatus,"piwigo");
		$html=$html.BuildRows("APP_WORDPRESS",$GlobalApplicationsStatus,"wordpress");
		$html=$html.BuildRows("APP_SABNZBDPLUS",$GlobalApplicationsStatus,"sabnzbd");
		$html=$html.BuildRows("APP_OPENEMM",$GlobalApplicationsStatus,"OpenEMM");
		$html=$html.BuildRows("APP_OPENEMM_SENDMAIL",$GlobalApplicationsStatus,"sendmail");
		$html=$html.BuildRows("APP_PIWIK",$GlobalApplicationsStatus,"piwik");
	}
if($users->cyrus_imapd_installed){
	$html=$html.spacer('webmails');
	$html=$html.BuildRows("APP_ROUNDCUBE",$GlobalApplicationsStatus,"roundcubemail");
	$html=$html.BuildRows("APP_ROUNDCUBE3",$GlobalApplicationsStatus,"roundcubemail3");	
	
	
	$html=$html.BuildRows("APP_ATOPENMAIL",$GlobalApplicationsStatus,"atmailopen");
}
if(!$users->KASPERSKY_SMTP_APPLIANCE){
	$html=$html.spacer('{APP_SUGARCRM}');
	$html=$html.BuildRows("APP_SUGARCRM",$GlobalApplicationsStatus,"SugarCE");	

	$html=$html.spacer('{APP_JOOMLA}');
	$html=$html.BuildRows("APP_JOOMLA",$GlobalApplicationsStatus,"joomla");
	$html=$html.BuildRows("APP_JOOMLA17",$GlobalApplicationsStatus,"joomla17");
}
	//$html=$html.spacer('{optional}');
	//$html=$html.BuildRows("APP_GROUPWARE_APACHE",$GlobalApplicationsStatus,"httpd");
	//$html=$html.BuildRows("APP_GROUPWARE_PHP",$GlobalApplicationsStatus,"php");		
	
$html=$html."</table>";
	
if(posix_getuid()==0){
		file_put_contents(dirname(__FILE__)."/ressources/logs/setup.index.".__FUNCTION__.".html",$html);
		return null;
}
$tpl=new templates();
return $tpl->_ENGINE_parse_body($html);	
	
}

function proxy_packages(){

	
$sock=new sockets();
$users=new usersMenus();
$GlobalApplicationsStatus=$sock->APC_GET("GlobalApplicationsStatus",2);
$MEM_TOTAL_INSTALLEE=$users->MEM_TOTAL_INSTALLEE;
if($GlobalApplicationsStatus==null){
	$GlobalApplicationsStatus=base64_decode($sock->getFrameWork('cmd.php?Global-Applications-Status=yes'));
	$sock->APC_SAVE("GlobalApplicationsStatus",$GlobalApplicationsStatus);
	$GLOBALS["GlobalApplicationsStatus"]=$GlobalApplicationsStatus;
}
$html="

<br>
<table style='width:100%;padding:2px:margin:3px;border:1px solid #CCCCCC'>
<tr style='background-color:#CCCCCC'>
<td>&nbsp;</td>
<td style='font-size:13px'><strong>{software}</strong></td>
<td style='font-size:13px'><strong>{current_version}</strong></td>
<td style='font-size:13px'><strong>{available_version}</strong></td>
<td style='font-size:13px'>&nbsp;</td>
<td style='font-size:13px'><strong>{status}</strong></td>
</tr>";
	$html=$html.spacer('{CORE_PRODUCTS}');
	$html=$html.BuildRows("APP_SQUID",$GlobalApplicationsStatus,"squid3");
	$html=$html.BuildRows("APP_SARG",$GlobalApplicationsStatus,"sarg");
	$html=$html.BuildRows("APP_SAMBA",$GlobalApplicationsStatus,"samba");
	$html=$html.BuildRows("APP_MSKTUTIL",$GlobalApplicationsStatus,"msktutil");

	$html=$html.spacer('{CONTENTS_FILTERS_PRODUCTS}');
	$html=$html.BuildRows("APP_UFDBGUARD",$GlobalApplicationsStatus,"ufdbGuard");
	//$html=$html.BuildRows("APP_SQUIDGUARD",$GlobalApplicationsStatus,"squidGuard");
	
	
	
	if(!$users->KASPERSKY_WEB_APPLIANCE){
		if($MEM_TOTAL_INSTALLEE>700000){$html=$html.BuildRows("APP_SQUIDCLAMAV",$GlobalApplicationsStatus,"squidclamav");}
		if($MEM_TOTAL_INSTALLEE>700000){$html=$html.BuildRows("APP_CLAMAV",$GlobalApplicationsStatus,"clamav");}
		$html=$html.BuildRows("APP_C_ICAP",$GlobalApplicationsStatus,"c-icap");	
		
	}
	if($MEM_TOTAL_INSTALLEE>700000){
		$html=$html.spacer('{LICENSED_FILTERS_PRODUCTS}');
		$html=$html.BuildRows("APP_KAV4PROXY",$GlobalApplicationsStatus,"kav4proxy");
	}
$html=$html."</table>";
	
if(posix_getuid()==0){
		file_put_contents(dirname(__FILE__)."/ressources/logs/setup.index.".__FUNCTION__.".html",$html);
		return null;
}

$tpl=new templates();
return $tpl->_ENGINE_parse_body($html);	
	
}

function xapian_packages(){
	
$sock=new sockets();
$users=new usersMenus();
$GlobalApplicationsStatus=$sock->APC_GET("GlobalApplicationsStatus",2);
if($GlobalApplicationsStatus==null){
	$GlobalApplicationsStatus=base64_decode($sock->getFrameWork('cmd.php?Global-Applications-Status=yes'));
	$sock->APC_SAVE("GlobalApplicationsStatus",$GlobalApplicationsStatus);
	$GLOBALS["GlobalApplicationsStatus"]=$GlobalApplicationsStatus;
}
$html="

<br>
<table style='width:100%;padding:2px:margin:3px;border:1px solid #CCCCCC'>
<tr style='background-color:#CCCCCC'>
<td>&nbsp;</td>
<td style='font-size:13px'><strong>{software}</strong></td>
<td style='font-size:13px'><strong>{current_version}</strong></td>
<td style='font-size:13px'><strong>{available_version}</strong></td>
<td style='font-size:13px'>&nbsp;</td>
<td style='font-size:13px'><strong>{status}</strong></td>
</tr>";
	$html=$html.spacer('{CORE_PRODUCTS}');
	$html=$html.BuildRows("APP_XAPIAN",$GlobalApplicationsStatus,"xapian-core");
	$html=$html.BuildRows("APP_CUPS_DRV",$GlobalApplicationsStatus,"cups-drv");	
	$html=$html.spacer('{LICENSED_FILTERS_PRODUCTS}');
	$html=$html.BuildRows("APP_KAV4SAMBA",$GlobalApplicationsStatus,"kav4samba");
	
	
$html=$html."</table>";


if(posix_getuid()==0){
		file_put_contents(dirname(__FILE__)."/ressources/logs/setup.index.".__FUNCTION__.".html",$html);
		return null;
}

$tpl=new templates();
return $tpl->_ENGINE_parse_body($html);		
	


}

function samba_packages(){
	
$sock=new sockets();
$EnableKav4fsFeatures=$sock->GET_INFO("EnableKav4fsFeatures");
if($EnableKav4fsFeatures==null){$EnableKav4fsFeatures=0;}

$users=new usersMenus();
$GlobalApplicationsStatus=$sock->APC_GET("GlobalApplicationsStatus",2);
if($GlobalApplicationsStatus==null){
	$GlobalApplicationsStatus=base64_decode($sock->getFrameWork('cmd.php?Global-Applications-Status=yes'));
	$sock->APC_SAVE("GlobalApplicationsStatus",$GlobalApplicationsStatus);
	$GLOBALS["GlobalApplicationsStatus"]=$GlobalApplicationsStatus;
}
$html="

<br>
<table style='width:100%;padding:2px:margin:3px;border:1px solid #CCCCCC'>
<tr style='background-color:#CCCCCC'>
<td>&nbsp;</td>
<td style='font-size:13px'><strong>{software}</strong></td>
<td style='font-size:13px'><strong>{current_version}</strong></td>
<td style='font-size:13px'><strong>{available_version}</strong></td>
<td style='font-size:13px'>&nbsp;</td>
<td style='font-size:13px'><strong>{status}</strong></td>
</tr>";
	$html=$html.spacer('{CORE_PRODUCTS}');
	$html=$html.BuildRows("APP_SAMBA",$GlobalApplicationsStatus,"samba");
	$html=$html.BuildRows("APP_MSKTUTIL",$GlobalApplicationsStatus,"msktutil");
	$html=$html.BuildRows("APP_GLUSTER",$GlobalApplicationsStatus,"glusterfs");
	$html=$html.BuildRows("APP_GREYHOLE",$GlobalApplicationsStatus,"greyhole");
	
	
	$html=$html.BuildRows("APP_CUPS_DRV",$GlobalApplicationsStatus,"cups-drv");
	$html=$html.BuildRows("APP_CUPS_BROTHER",$GlobalApplicationsStatus,"brother-drivers");
	$html=$html.BuildRows("APP_HPINLINUX",$GlobalApplicationsStatus,"hpinlinux");
	$html=$html.BuildRows("APP_SCANNED_ONLY",$GlobalApplicationsStatus,"scannedonly");			
	$html=$html.BuildRows("APP_PUREFTPD",$GlobalApplicationsStatus,"pure-ftpd");
	$html=$html.BuildRows("APP_BACKUPPC",$GlobalApplicationsStatus,"BackupPC");
	$html=$html.BuildRows("APP_MLDONKEY",$GlobalApplicationsStatus,"mldonkey");
	$html=$html.BuildRows("APP_DROPBOX",$GlobalApplicationsStatus,"dropbox-32");
	
	
	
	$html=$html.spacer('{LICENSED_FILTERS_PRODUCTS}');
	$html=$html.BuildRows("APP_KAV4SAMBA",$GlobalApplicationsStatus,"kav4samba");
	if($EnableKav4fsFeatures==1){
		$html=$html.BuildRows("APP_KAV4FS",$GlobalApplicationsStatus,"kav4fs");
	}
	
	
	
$html=$html."</table>";
	
if(posix_getuid()==0){
		file_put_contents(dirname(__FILE__)."/ressources/logs/setup.index.".__FUNCTION__.".html",$html);
		return null;
}

$tpl=new templates();
return $tpl->_ENGINE_parse_body($html);	
	
	
}

function system_packages(){
	

	
$sock=new sockets();
$users=new usersMenus();
$KASPERSKY_APPLIANCE=FALSE;
if($users->KASPERSKY_SMTP_APPLIANCE){$KASPERSKY_APPLIANCE=TRUE;}
if($users->KASPERSKY_WEB_APPLIANCE){$KASPERSKY_APPLIANCE=TRUE;}
if($users->OPENVPN_APPLIANCE){$OPENVPN_APPLIANCE=TRUE;}		
$MEM_TOTAL_INSTALLEE=$users->MEM_TOTAL_INSTALLEE;





$GlobalApplicationsStatus=$sock->APC_GET("GlobalApplicationsStatus",2);
if($GlobalApplicationsStatus==null){
	$GlobalApplicationsStatus=base64_decode($sock->getFrameWork('cmd.php?Global-Applications-Status=yes'));
	$sock->APC_SAVE("GlobalApplicationsStatus",$GlobalApplicationsStatus);
}

$html="

<br>
<table style='width:100%;padding:2px:margin:3px;border:1px solid #CCCCCC'>
<tr style='background-color:#CCCCCC'>
<td>&nbsp;</td>
<td style='font-size:13px'><strong>{software}</strong></td>
<td style='font-size:13px'><strong>{current_version}</strong></td>
<td style='font-size:13px'><strong>{available_version}</strong></td>
<td style='font-size:13px'>&nbsp;</td>
<td style='font-size:13px'><strong>{status}</strong></td>
</tr>";

if($users->VMWARE_HOST){
	$html=$html.BuildRows("APP_VMTOOLS",$GlobalApplicationsStatus,"VMwareTools");
}

if($users->VIRTUALBOX_HOST){
	$html=$html.BuildRows("APP_VBOXADDITIONS",$GlobalApplicationsStatus,"VBoxLinuxAdditions-$users->ArchStruct");
}

if(($users->LinuxDistriCode=='DEBIAN') or ($users->LinuxDistriCode=='UBUNTU')){
	$html=$html.BuildRows("APP_OPENLDAP",$GlobalApplicationsStatus,"openldap");
}
			

	//if(!$users->AS_VPS_CLIENT){$html=$html.BuildRows("APP_MYSQL",$GlobalApplicationsStatus,"mysql-cluster-gpl");}
	
if(!$KASPERSKY_APPLIANCE){	
	if($MEM_TOTAL_INSTALLEE>700000){
		if(!$users->AS_VPS_CLIENT){
		$html=$html.BuildRows("APP_LXC",$GlobalApplicationsStatus,"lxc");	
		}
	}
}
	$html=$html.BuildRows("APP_PHPLDAPADMIN",$GlobalApplicationsStatus,"phpldapadmin");
	$html=$html.BuildRows("APP_MYSQL",$GlobalApplicationsStatus,"mysql-server");
	$html=$html.BuildRows("APP_PHPMYADMIN",$GlobalApplicationsStatus,"phpMyAdmin");
	if(!$KASPERSKY_APPLIANCE){$html=$html.BuildRows("APP_GREENSQL",$GlobalApplicationsStatus,"greensql-fw");}
	if(!$KASPERSKY_APPLIANCE){$html=$html.BuildRows("APP_TOMCAT",$GlobalApplicationsStatus,"apache-tomcat");}
		
	//$html=$html.BuildRows("APP_EACCELERATOR",$GlobalApplicationsStatus,"eaccelerator");
	if(!$KASPERSKY_APPLIANCE){
		$html=$html.spacer('{smtp_packages}');
		$html=$html.BuildRows("APP_MSMTP",$GlobalApplicationsStatus,"msmtp");
		if($MEM_TOTAL_INSTALLEE>500000){$html=$html.BuildRows("APP_EMAILRELAY",$GlobalApplicationsStatus,"emailrelay");}
	}
	
	
		
	$html=$html.spacer('{network_softwares}');
	if(!$KASPERSKY_APPLIANCE){$html=$html.BuildRows("APP_DHCP",$GlobalApplicationsStatus,"dhcp");}
	if(!$KASPERSKY_APPLIANCE){if($MEM_TOTAL_INSTALLEE>700000){$html=$html.BuildRows("APP_PDNS",$GlobalApplicationsStatus,"pdns");}}
	if(!$KASPERSKY_APPLIANCE){if($MEM_TOTAL_INSTALLEE>700000){$html=$html.BuildRows("APP_POWERADMIN",$GlobalApplicationsStatus,"poweradmin");}}
	if(!$KASPERSKY_APPLIANCE){$html=$html.BuildRows("APP_OPENVPN",$GlobalApplicationsStatus,"openvpn");}
	$html=$html.BuildRows("APP_IPTACCOUNT",$GlobalApplicationsStatus,"iptaccount");
	if(!$KASPERSKY_APPLIANCE){$html=$html.BuildRows("APP_AMACHI",$GlobalApplicationsStatus,"hamachi");}
	if(!$KASPERSKY_APPLIANCE){$html=$html.BuildRows("APP_PUREFTPD",$GlobalApplicationsStatus,"pure-ftpd");}
	if(!$KASPERSKY_APPLIANCE){if(!$OPENVPN_APPLIANCE){$html=$html.BuildRows("APP_MLDONKEY",$GlobalApplicationsStatus,"mldonkey");}}
	

	
	if(!$KASPERSKY_APPLIANCE){
		if(!$OPENVPN_APPLIANCE){
			$html=$html.spacer('{storagebakcup_softwares}');
			$html=$html.BuildRows("APP_AMANDA",$GlobalApplicationsStatus,"amanda");
			$html=$html.BuildRows("APP_DROPBOX",$GlobalApplicationsStatus,"dropbox-32");
			$html=$html.BuildRows("APP_FUSE",$GlobalApplicationsStatus,"fuse");
			$html=$html.BuildRows("APP_ZFS_FUSE",$GlobalApplicationsStatus,"zfs-fuse");
			$html=$html.BuildRows("APP_TOKYOCABINET",$GlobalApplicationsStatus,"tokyocabinet");
			$html=$html.BuildRows("APP_LESSFS",$GlobalApplicationsStatus,"lessfs");		
			if(!$KASPERSKY_APPLIANCE){$html=$html.BuildRows("APP_DAR",$GlobalApplicationsStatus,"dar");}
		}
		
	}
	
	
	
	
	
	

if(!$KASPERSKY_APPLIANCE){	
	$html=$html.spacer('{secuirty_softwares}');
 
 		if($MEM_TOTAL_INSTALLEE>700000){$html=$html.BuildRows("APP_CLAMAV",$GlobalApplicationsStatus,"clamav");}
 		$html=$html.BuildRows("APP_SNORT",$GlobalApplicationsStatus,"snort");	
 		$html=$html.BuildRows("APP_NMAP",$GlobalApplicationsStatus,"nmap");
 		$html=$html.BuildRows("APP_SMARTMONTOOLS",$GlobalApplicationsStatus,"smartmontools");
	
 		if(!$OPENVPN_APPLIANCE){
			$html=$html.spacer('{computers_management}');
			$html=$html.BuildRows("APP_WINEXE",$GlobalApplicationsStatus,"winexe-static");
			$html=$html.BuildRows("APP_OCSI",$GlobalApplicationsStatus,"OCSNG_UNIX_SERVER");
			$html=$html.BuildRows("APP_OCSI2",$GlobalApplicationsStatus,"OCSNG_UNIX_SERVER2");
 			$html=$html.BuildRows("APP_OCSI_LINUX_CLIENT",$GlobalApplicationsStatus,"OCSNG_LINUX_AGENT");
 		}		
	}
	
	
if(!$KASPERSKY_APPLIANCE){
	if(!$OPENVPN_APPLIANCE){
		$html=$html.spacer('{xapian_packages}');
		$html=$html.BuildRows("APP_XAPIAN",$GlobalApplicationsStatus,"xapian-core");
		$html=$html.BuildRows("APP_XAPIAN_OMEGA",$GlobalApplicationsStatus,"xapian-omega");
		$html=$html.BuildRows("APP_XAPIAN_PHP",$GlobalApplicationsStatus,"xapian-bindings");
		$html=$html.BuildRows("APP_XPDF",$GlobalApplicationsStatus,"xpdf");
		//$html=$html.BuildRows("APP_UNZIP",$GlobalApplicationsStatus,"unzip");
		$html=$html.BuildRows("APP_UNRTF",$GlobalApplicationsStatus,"unrtf");
		$html=$html.BuildRows("APP_CATDOC",$GlobalApplicationsStatus,"catdoc");		
		$html=$html.BuildRows("APP_ANTIWORD",$GlobalApplicationsStatus,"antiword");	
	}
}
	$html=$html."</table>";
	
if(posix_getuid()==0){
		file_put_contents(dirname(__FILE__)."/ressources/logs/setup.index.".__FUNCTION__.".html",$html);
		return null;
}	

$tpl=new templates();
return $tpl->_ENGINE_parse_body($html);	
	
	
}
function ParseAppli($status,$key){

if(!is_array($GLOBALS["GLOBAL_VERSIONS_CONF"])){BuildVersions();}
return $GLOBALS["GLOBAL_VERSIONS_CONF"][$key];	
}

function ParseUninstall($SockStatus,$appli){
	if($SockStatus==null){$SockStatus=$GLOBALS["GlobalApplicationsStatus"];}
	$ini=new Bs_IniHandler();
	$ini->loadString($SockStatus);

	if(is_array($ini->_params)){
	while (list ($num, $line) = each ($ini->_params) ){
		if($line["service_name"]==$appli){
			if($ini->_params[$num]["remove_cmd"]<>null){
				return $ini->_params[$num]["remove_cmd"];
				}
			}
	}	}

}



function BuildVersions(){
	if(is_file("ressources/logs/global.versions.conf")){
		$GlobalApplicationsStatus=@file_get_contents("ressources/logs/global.versions.conf");
	}else{
		if(is_file("ressources/logs/web/global.versions.conf")){
			$GlobalApplicationsStatus=@file_get_contents("ressources/logs/web/global.versions.conf");
		}
	}
	$tb=explode("\n",$GlobalApplicationsStatus);
	while (list ($num, $line) = each ($tb) ){
		if(preg_match('#\[(.+?)\]\s+"(.+?)"#',$line,$re)){
			$GLOBALS["GLOBAL_VERSIONS_CONF"][trim($re[1])]=trim($re[2]);
		}
		
	}
}


function spacer($text){
	
return "
<tr style='background-image:url(img/bg_row.jpg)'>
	<td colspan=6 style='padding-top:4px'><span style='font-size:13px;font-weight:bold;text-transform:capitalize;color:black'>$text</td>
</tr>
";
	
}


function BuildRows($appli,$SockStatus,$internetkey,$noupgrade=false){
	$ini=new Bs_IniHandler();
	if($GLOBALS["INDEXFF"]==null){$GLOBALS["INDEXFF"]=@file_get_contents(dirname(__FILE__). '/ressources/index.ini');}
	$ini->loadString($GLOBALS["INDEXFF"]);
	$tpl=new templates();
	$button_text=$tpl->_parse_body('{install_upgrade}');
	if(strlen($button_text)>27){$button_text=substr($button_text,0,24)."...";}
	$bgcolor="style='background-color:#DFFDD6'";
	$version=ParseAppli($SockStatus,$appli);
	$uninstall=ParseUninstall($SockStatus,$appli);
	if(($version=="0") OR (strlen($version)==0)){
		$version="{not_installed}";
		$bgcolor=null;
		$uninstall=null;
	}
	
	if(file_exists(dirname(__FILE__). "/ressources/install/$appli.dbg")){
		$dbg_exists=imgtootltip('22-logs.png',"{events}","InstallLogs('$appli')");
		$styledbg="background-color:yellow;border:1px solid black";
	
		}
		else{$dbg_exists="<img src='img/fw_bold.gif'>";
		}
		
	$appli_text=$tpl->_ENGINE_parse_body("{{$appli}}");
	$appli_text=replace_accents($appli_text);
	if(strlen($appli_text)>30){$appli_text=texttooltip(substr($appli_text,0,27)."...",$appli_text,null,null,1);}
	$button_install=button($button_text,"ApplicationSetup('$appli')");
	
	if($GLOBALS["ArticaMetaDisableSetupCenter"]==1){$button_install=null;$uninstall=null;}
	
	
	// UNINSTALL
	if($uninstall<>null){
		$version=
		"<table><tr><td style='font-size:13px' valign='middle'>$version</td>
			<td valign='middle'>".imgtootltip("ed_delete.gif","{uninstall} {{$appli}}","SetupCenterRemove('$uninstall','$appli')")."</td></tr></table>";
	}
	
	
	if($ini->_params["NEXT"]["$internetkey"]==null){
		writelogs("Unable to stat NEXT/$internetkey \"{$ini->_params["NEXT"]["$internetkey"]}\"",__FUNCTION__,__FILE__,__LINE__);
		$ini->_params["NEXT"]["$internetkey"]="<div style='color:#d32d2d'>{error_network}</div>";
		$button_install=null;
		}
	if($internetkey=="openldap"){
		$sock=new sockets();
		if($sock->GET_INFO("AllowUpgradeLdap")<>1){
			$button_install=null;
		}
	}		
	if($noupgrade){$button_install=null;}
	
	return "
	<tr $bgcolor>
		<td width=2% style=\"$styledbg\">$dbg_exists</td>
		<td style='font-size:13px' nowrap>$appli_text</td>
		<td style='font-size:13px'>$version</td>
		<td style='font-size:13px'>{$ini->_params["NEXT"]["$internetkey"]}</td>
		<td style='font-size:11px'>$button_install</td>
		<td style='font-size:13px'><div style='width:100px;height:22px;border:1px solid #CCCCCC' id='STATUS_$appli'>".install_status($appli)."</div></td>
	</tr>
	";	
	
}


function install_app(){
	$tpl=new templates();
	$q=new mysql();
	$sock=new sockets();
	$sql="SELECT upgrade FROM setup_center WHERE CODE_NAME='{$_GET["install_app"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	
	$q->QUERY_SQL("UPDATE setup_center SET upgrade=1,progress=5,progress_text='{scheduled}',events='' WHERE CODE_NAME='{$_GET["install_app"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock->getFrameWork("services.php?schedule-apps=yes");
	
	
	$install_app=$tpl->_ENGINE_parse_body("{install_app}");
	$echo="{{$_GET["install_app"]}}\n $install_app ";
	$echo=$tpl->javascript_parse_text($echo,1);
	echo $echo;
	
	
}

function install_status($appli){
	$appname=$appli;
	$ini=new Bs_IniHandler();
	$dbg_exists=false;
	if(file_exists(dirname(__FILE__). "/ressources/install/$appname.ini")){
	    $data=file_get_contents(dirname(__FILE__). "/ressources/install/$appname.ini");
		$ini->loadString($data);
		$status=$ini->_params["INSTALL"]["STATUS"];
		$text_info=$ini->_params["INSTALL"]["INFO"];
		writelogs("Loading ressources/install/$appname.ini; status:$status",__FUNCTION__,__FILE__);
		if(strlen($text_info)>0){$text_info="<span style='color:black;font-size:10px'>$text_info...</span>";}
		
	}else{
		//writelogs("Loading ressources/install/$appname.ini doesn't exists",__FUNCTION__,__FILE__);
	}
	
	if($status==null){$status=0;}
	if($status>100){$color="#D32D2D";$status=100;$text='{failed}';}else{$color="#5DD13D";$text=$status.'%';}
	$tpl=new templates();
	return  $tpl->_ENGINE_parse_body("
		<div style='width:{$status}px;text-align:center;color:white;padding-top:3px;padding-bottom:3px;background-color:$color'>
			<strong>{$text}&nbsp;$text_info</strong>
		</div>");
	
writelogs("Loading $appname status ($status) done",__FUNCTION__,__FILE__);	
}

function GetLogsStatus(){
			$sock=new sockets();
			$tb=unserialize(base64_decode($sock->getFrameWork("cmd.php?AppliCenterGetDebugInfos={$_GET["InstallLogs"]}")));	
			writelogs(count($tb). " lines number for {$_GET["InstallLogs"]}",__FUNCTION__,__FILE__);
			$start=0;
			if(count($tb)>200){$start=count($tb)-200;}
			if(is_array($tb)){
			for($i=$start;$i<count($tb);$i++){
				$count=$count=1;
				$line=$tb[$i];
				if(trim($line)==null){continue;}
					$line=htmlentities($line);
					if(substr($line,0,1)=="#"){continue;}
					if(strpos($line,"##")>0){continue;}
					if(preg_match('#[0-9]+\.[0-9]+\%#',$line)){continue;}
					$line=wordwrap($line, 70, " ", true);
					$ligne[]="<div style='border-bottom:1px dotted #CCCCCCC;font-size:10px'>$line</div>";
				
			}
			}
			if(is_array($ligne)){
			$html="<div style='width:600px;height:450px;padding:5px;border:1px solid #CCCCCC;overflow:auto;background-color:white' id='loupe-logs'>".implode("\n",$ligne)."</div>";
			}
			writelogs(count($tb). " lines number for {$_GET["InstallLogs"]} finish",__FUNCTION__,__FILE__);
			echo $html;
	
}

function testConnection(){
	$sock=new sockets();
	$datas=$sock->getFrameWork('cmd.php?SetupIndexFile=yes');
	

	
	$t=time();
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("
				<p>&nbsp;</p>
<textarea style='margin-top:5px;font-family:Courier New;
font-weight:bold;width:98%;height:446px;border:5px solid #8E8E8E;
overflow:auto;font-size:11px' id='text-$t'>$datas</textarea>
	<script>
			function Refresh$t(){
				if(document.getElementById('squid_main_svc')){ RefreshTab('squid_main_svc'); }
				if(document.getElementById('main_config_artica_update')){ RefreshTab('main_config_artica_update'); }
				YahooWinHide();
			}
			
			setTimeout(\"Refresh$t()\",8000);
			
	</script>
	");
	
}

function services_family(){
	// perform_operation_on_services_scheduled
	$users=new usersMenus();
	if($users->POSTFIX_INSTALLED){$postfix=1;}else{$postfix=0;}
	if($users->SAMBA_INSTALLED){$samba=1;}else{$samba=0;}
	if($users->SQUID_INSTALLED){$squid=1;}else{$squid=0;}
	$postfix=Paragraphe_switch_img("{messaging_service}","{messaging_service_text}","postfix",$postfix);
	$samba=Paragraphe_switch_img("{filesharing_service}","{filesharing_service_text}","samba",$samba);
	$squid=Paragraphe_switch_img("{webproxy_service}","{webproxy_service_text}","squid",$squid);				
	
	
	$html="
	<input type='hidden' id='tabfamily' value='no'>
	<p style='font-size:13px;font-weight:bold'>{services_family_text}</p>
	<table style='width:100%'>
	<tr>
		<td valign='top'>$postfix</td>
		<td valign='top'>$samba</td>
		<td valign='top'>$squid</td>
	</tr>
	</table>
	
	<div style='width:100%;text-align:right'>
		<hr>". button("{apply}","ApplyInstallUninstallServices()")."
	</div>
	
		
	";
	

	
	$tpl=new templates();
	return $tpl->_ENGINE_parse_body($html);
	
}

function install_remove_services(){
	$sock=new sockets();
	$users=new usersMenus();
	if($_GET["ui-postfix"]==0){
		$sock->getFrameWork("cmd.php?uninstall-app=". base64_encode("--postfix-remove")."&app=APP_POSTFIX");
		
	}
	if($_GET["ui-postfix"]==1){
		if(!$users->POSTFIX_INSTALLED){
			$sock->getFrameWork("cmd.php?services-install=". base64_encode("--check-postfix")."&app=APP_POSTFIX");
		}
		
	}
	
	if($_GET["ui-samba"]==0){
		$sock->getFrameWork("cmd.php?uninstall-app=". base64_encode("--samba-remove")."&app=APP_SAMBA");
		
	}	

	if($_GET["ui-samba"]==1){
		if(!$users->SAMBA_INSTALLED){
			$sock->getFrameWork("cmd.php?services-install=". base64_encode("--check-samba")."&app=APP_SAMBA");
		}
		
	}	

	if($_GET["ui-squid"]==0){
		$sock->getFrameWork("cmd.php?uninstall-app=". base64_encode("--squid-remove")."&app=APP_SQUID");
		
	}	

	if($_GET["ui-squid"]==1){
		if(!$users->SQUID_INSTALLED){
			$sock->getFrameWork("cmd.php?services-install=". base64_encode("--check-squid")."&app=APP_SAMBA");
		}
		
	}		
	
}


function SynSysPackages(){
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?sys-sync-paquages=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{UPDATE_ANTIVIRUS_DATABASE_PERFORMED}");
	
}

function samba_stables_available(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$error=false;
	$ArchStruct=$users->ArchStruct;
	if($ArchStruct=="32"){$ArchStruct="i386";}
	if($ArchStruct=="64"){$ArchStruct="amd64";}
	
	if($users->LinuxDistriCode<>"DEBIAN"){
		if($users->LinuxDistriCode<>"UBUNTU"){
			FATAL_ERROR_SHOW_128("{ERROR_OPERATING_SYSTEM_NOT_SUPPORTED}");
			$error=true;
		}
	}
	
	if(!is_file("ressources/old-samba.ini")){$sock->getFrameWork("cmd.php?SetupIndexFile=yes");}
	
	$ini=new Bs_IniHandler("ressources/old-samba.ini");
	$current=base64_decode($sock->getFrameWork("samba.php?current-version=yes"));
	$html[]="
	<div style='font-size:18px;margin-bottom:20px;text-align:right'>Samba v.$current</div>
	<div style='font-size:16px' class=explain>{samba_old_stable_explain}</div>
	<div style='width:95%;text-align:center' class=form >
	<table style='width:100%'>
	";
	
	
	while (list ($versions, $array) = each ($ini->_params) ){
	$filename=urlencode($array[$ArchStruct]);
	if($filename==null){continue;}
	
	$html[]="<tr style='height:50px'>
	<td style='font-size:32px' width=33%>$versions</td>
	<td style='font-size:18px' width=33%>{released_on} {$array["date"]}</td>";
	
		if(!$error){$html[]="
			<td width=33%>". button("{install_this_version}","Loadjs('samba.downgrade.php?file=$filename&ask=yes')",18)."</td>";
		}
		$html[]="</tr>";
	
		}
			$html[]="</table></div>";
			echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
		
}

?>
