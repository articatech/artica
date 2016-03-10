<?php
	session_start();
	//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');

	
	

	$user=new usersMenus();
	if(!GetPrivs()){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	
if(isset($_GET["items"])){items();exit;}
if(isset($_POST["item-enable"])){item_enable();exit;}
if(isset($_GET["item-id"])){item_popup();exit;}
if(isset($_POST["ID"])){item_save();exit;}
if(isset($_POST["delete-item"])){item_delete();exit;}
if(isset($_POST["item-run"])){item_run();exit;}
if(isset($_POST["exec"])){execute();exit;}
if(isset($_GET["run-js"])){run_js();exit;}
if(isset($_GET["event-js"])){events_js();exit;}
if(isset($_GET["event-id"])){events_id();exit;}

table();
function GetPrivs(){
	$users=new usersMenus();
	if($users->AsSystemWebMaster){return true;}
}
function events_js(){
	$id=$_GET["ID"];
	if(!is_numeric($id)){return;}
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$sql="SELECT sitename FROM artica_caches_mirror WHERE ID=$id";
	$q=new mysql_squid_builder();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	
	$subject=$tpl->javascript_parse_text($ligne["sitename"]);
	echo "YahooWin6('890','$page?event-id=$id','$subject')";

}

function events_id(){

	$tpl=new templates();
	$sql="SELECT RunEvents FROM artica_caches_mirror WHERE ID={$_GET["event-id"]}";
	$q=new mysql_squid_builder();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));

	
	echo "<textarea style='margin-top:5px;
	font-weight:bold;width:98%;height:446px;border:5px solid #8E8E8E;overflow:auto;font-size:11px' id='textToParseCats-$t'>{$ligne["RunEvents"]}</textarea>";
}

function run_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT sitename FROM artica_caches_mirror WHERE ID='$ID'","artica_backup"));
	$ask=$tpl->javascript_parse_text("{execute} {$ligne["sitename"]}");
	$t=time();
	echo "
var xNewRule$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	$('#flexRT{$_GET["t"]}').flexReload();
}
	
function NewRule$t(){
	if(!confirm('$ask ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('exec','$ID');
	XHR.sendAndLoad('$page', 'POST',xNewRule$t);
}

NewRule$t();";


}


function execute(){
	$tpl=new templates();
	$sock=new sockets();
	$sock->getFrameWork("squid.php?hypercache-mirror-run={$_POST["exec"]}");
	echo $tpl->javascript_parse_text("{success}");
	
	
}


function table(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	if(!$users->HTTRACK_INSTALLED){echo FATAL_ERROR_SHOW_128("{ERROR_HTTRACK_NOT_INSTALLED}"); die(); }
	$TB_HEIGHT=400;
	$TB_WIDTH=790;

	$new_entry=$tpl->javascript_parse_text("{new_website}");
	$t=time();
	$run=$tpl->javascript_parse_text("{run}");
	$ev=$tpl->javascript_parse_text("{events}");
	$enable=$tpl->javascript_parse_text("{enable}");
	$website=$tpl->javascript_parse_text("{websites}");
	$size=$tpl->javascript_parse_text("{size}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$events=$tpl->javascript_parse_text("{events}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$enforce_rules=$tpl->javascript_parse_text("{enforce_rules}");
	$title="$enforce_rules: ".$tpl->javascript_parse_text("{mirror}");
	
	
	$buttons="
	buttons : [
	{name: '$new_entry', bclass: 'Add', onpress : ItemNew$t},
	{name: '$apply', bclass: 'ReConf', onpress : ItemExec$t},
	],	";
	
	
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?items=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$website', name : 'sitename', width :600, sortable : true, align: 'left'},
		{display: '$size', name : 'size', width :192, sortable : true, align: 'right'},
		{display: '$enable', name : 'enabled', width :60, sortable : true, align: 'center'},
		{display: '$run', name : 'enabled', width :60, sortable : false, align: 'center'},
		{display: '$ev', name : 'enabled', width :60, sortable : false, align: 'center'},
		{display: '$delete', name : 'delete', width :60, sortable : false, align: 'center'}
	],
	$buttons

	searchitems : [
		{display: '$website', name : 'sitename'},
	],
	sortname: 'sitename',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:18px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});
function ItemShow$t(id){
	YahooWin5('850','$page?item-id='+id+'&t=$t','$title:'+id);
}

function ItemEvents$t(){
	Loadjs('squid.update.events.php?table=system_admin_events&category=webcopy');
}
function ItemHelp$t(){
	s_PopUpFull('http://mail-appliance.org/index.php?cID=263','1024','900');
}

var x_ItemDelete$t=function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}	
	$('#row'+mem$t).remove();
}

function ItemDelete$t(id){
	mem$t=id;
	if(!confirm('$delete '+id+' ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-item',id);
    XHR.sendAndLoad('$page', 'POST',x_ItemDelete$t);	
	}
	

function ItemNew$t(){
	title='$new_entry';
	YahooWin5('850','$page?item-id=0&t=$t','$title:'+title);
}
var x_ItemExec$t=function (obj) {
	var results=obj.responseText;
	if (results.length>3){alert(results);return;}
	$('#flexRT$t').flexReload();
}
var x_ItemExec2$t=function (obj) {
	var results=obj.responseText;
	if (results.length>3){alert(results);}
	$('#flexRT$t').flexReload();
}
var x_ItemSilent$t=function (obj) {
	var results=obj.responseText;
	if (results.length>3){alert(results);return;}
	
}
function ItemExec$t(){
	Loadjs('squid.artica-rules.progress.php');
}
function ItemRun$t(ID,imgid){
	mem$t=imgid;
	var XHR = new XHRConnection();
	XHR.appendData('item-run',ID);
	document.getElementById(imgid).src='/ajax-menus-loader.gif';
    XHR.sendAndLoad('$page', 'POST',x_ItemExec2$t);	
}
function ItemEnable$t(id){
	var value=0;
	if(document.getElementById('enable-'+id).checked){value=1;}
	var XHR = new XHRConnection();
	XHR.appendData('item-enable',id);
	XHR.appendData('value',value);
    XHR.sendAndLoad('$page', 'POST',x_ItemSilent$t);
}
	
</script>";
	
	echo $html;		
}	

function build_tables(){
	
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS('artica_caches_mirror','artica_backup')){
		$sql="CREATE TABLE IF NOT EXISTS `artica_caches_mirror` (
			`ID` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`sitename` varchar( 255 ) NOT NULL ,
			`size` BIGINT UNSIGNED DEFAULT '0',
			`minrate` INT( 10 ) NOT NULL DEFAULT '512',
			`maxfilesize` INT( 10 ) NOT NULL DEFAULT '512',
			`maxsitesize` INT( 10 ) NOT NULL DEFAULT '5000',
			`TimeExec` SMALLINT( 10 ) NOT NULL DEFAULT '10080',
			`enabled` smallint(1) NOT NULL,
			 UNIQUE KEY `sitename` (`sitename`),
			INDEX ( `size` , `minrate`,`maxfilesize`,`maxsitesize`,`enabled`)
			)";
		$q->QUERY_SQL($sql,"artica_backup");
	}
	

	
}

function items(){
	
$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$t=$_GET["t"];
	
	$search='%';
	$table="artica_caches_mirror";
	$database='artica_backup';
	$page=1;
	$FORCE_FILTER="";
	
	build_tables();
	if(!$q->TABLE_EXISTS($table, $database)){json_error_show("$table, No such table...",0);}
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("No data...",0);}
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		if(!$q->ok){json_error_show($q->mysql_error,1);}
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show($q->mysql_error,1);}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show($q->mysql_error,1);}
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(mysql_num_rows($results)==0){json_error_show("no data");}
	
	$Timez[60]="1 {hour}";
	$Timez[120]="2 {hours}";
	$Timez[180]="3 {hours}";
	$Timez[360]="6 {hours}";
	$Timez[720]="12 {hours}";
	$Timez[1440]="1 {day}";
	$Timez[2880]="2 {days}";
	$Timez[10080]="1 {week}";
	$Timez[20160]="2 {weeks}";
	$Timez[40320]="1 {month}";	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$id=$ligne["ID"];
		$articasrv=null;
		$delete=imgsimple("delete-32.png",null,"ItemDelete$t('$id')");
		if($ligne["depth"]==0){$ligne["depth"]=$tpl->_ENGINE_parse_body("{unlimited}");}
		$ligne["maxsitesize"]=FormatBytes($ligne["maxsitesize"]);
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
		$enabled=Field_checkbox("enable-$id", 1,$ligne["enabled"],"ItemEnable$t($id)");
		$run=imgsimple("32-run.png", null,"Loadjs('$MyPage?run-js=yes&ID=$id&t=$t')");
		$ev=imgsimple("32-mailevents.png", null,"Loadjs('$MyPage?event-js=yes&ID=$id&t=$t')");
		$time=$tpl->javascript_parse_text("{update_each}:{$Timez[$ligne["TimeExec"]]}");
		$maxsitesize=FormatBytes($ligne["maxsitesize"]*1024);
		
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array(
		"<a href=\"javascript:blur();\" 
				OnClick=\"javascript:ItemShow$t($id);\" 
				style='font-size:22px;text-decoration:underline'>{$ligne["sitename"]}</a><br><i style='margin-top:10px;font-size:14px'>$time<br><span style='font-size:14px'>MAX:{$ligne["maxsitesize"]}</i>",
		"<span style='font-size:22px;'>{$ligne["size"]}/$maxsitesize</span>",
		
		$enabled,$run,$ev,
		$delete )
		);
	}
	
	
echo json_encode($data);		
	
}

function item_popup(){
	$ldap=new clladp();
	$tpl=new templates();
	$page=CurrentPageName();
	
	$id=$_GET["item-id"];
	if(!is_numeric($id)){$id=0;}
	$t=$_GET["t"];
	$bname="{add}";
	$browse=button("{browse}","javascript:Loadjs('browse-disk.php?field=workingdir-$t&replace-start-root=0');");
	$ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM=$tpl->javascript_parse_text("{ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM}");
	
	$q=new mysql_squid_builder();
	
	if($id>0){
		$bname="{apply}";
		$sql="SELECT * FROM artica_caches_mirror WHERE ID=$id";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$workingdir=$ligne["workingdir"];
		$sitename=$ligne["sitename"];
		$minrate=$ligne["minrate"];
		$maxfilesize=$ligne["maxfilesize"];
		$maxsitesize=$ligne["maxsitesize"];
		$lang=$ligne["lang"];
		$TimeExec=$ligne["TimeExec"];
		$browse=null;
	}
	
	
	
	if($lang==null){$lang="english";}
	if(!is_numeric($minrate)){$minrate=512;}
	if(!is_numeric($maxfilesize)){$maxfilesize=512;}
	if(!is_numeric($maxsitesize)){$maxsitesize=5000;}
	
	$Timez[60]="1 {hour}";
	$Timez[120]="2 {hours}";
	$Timez[180]="3 {hours}";
	$Timez[360]="6 {hours}";
	$Timez[720]="12 {hours}";
	$Timez[1440]="1 {day}";
	$Timez[2880]="2 {days}";
	$Timez[10080]="1 {week}";
	$Timez[20160]="2 {weeks}";
	$Timez[40320]="1 {month}";
	if(!is_numeric($TimeExec)){$TimeExec=10080;}


$html="		
<div id='anime-$t'></div>
<div style='width:98%' class=form>
<table style='width:100%'>
<tr>	
	<td class=legend style='font-size:22px' nowrap>{website}:</strong></td>
	<td align=left>". Field_text("sitename-$t",$sitename,"width:499px;font-size:22px","script:FormCheck$t(event)")."</strong></td>
	<td width=1%>&nbsp;</td>
</tr>
<tr>	
	<td class=legend style='font-size:22px' nowrap>{update_each}:</strong></td>
	<td align=left>". Field_array_Hash($Timez, "TimeExec-$t" ,$TimeExec,null,null,0,"font-size:22px")."</strong></td>
	<td width=1%>&nbsp;</td>
</tr>
<tr>
	<td class=legend style='font-size:22px' nowrap>{maxfilesize}:</strong></td>
	<td align=left style='font-size:22px'>". Field_text("maxfilesize-$t",$maxfilesize,"width:110px;font-size:22px","script:FormCheck$t(event)")."&nbsp;KB</strong></td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class=legend style='font-size:22px' nowrap>{maxsitesize}:</strong></td>
	<td align=left style='font-size:22px'>". Field_text("maxsitesize-$t",$maxsitesize,"width:110px;font-size:22px","script:FormCheck$t(event)")."&nbsp;KB</strong></td>
	<td>&nbsp;</td>
</tr>
<tr>	
	<td colspan=3 align='right'><hr>". button("$bname","SaveForm$t();","32")."</td>
<tr>
</table>
</div>
<script>

		function FormCheck$t(e){
			if(checkEnter(e)){SaveForm$t();return;}
		}
		

		var x_SaveForm$t=function (obj) {
			var results=obj.responseText;
			if (results.length>3){alert(results);return;}
			$('#flexRT$t').flexReload();
			var id='$id';
			if(id==0){ YahooWin5Hide();}
		}				
		
		function SaveForm$t(){
			var ok=1;
			var sitename=document.getElementById('sitename-$t').value;
			if(sitename.length==0){ok=0;}
			if(ok==0){alert('$ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM');return;}
			var XHR = new XHRConnection();
			XHR.appendData('ID','$id');
			XHR.appendData('sitename',document.getElementById('sitename-$t').value);
			XHR.appendData('maxfilesize',document.getElementById('maxfilesize-$t').value);
			XHR.appendData('maxsitesize',document.getElementById('maxsitesize-$t').value);
			XHR.appendData('TimeExec',document.getElementById('TimeExec-$t').value);
			
			
			
			XHR.sendAndLoad('$page', 'POST',x_SaveForm$t);
		
		}
		
		function FormCheckFields$t(){
			var ID=$id;
			if($id>0){
				document.getElementById('sitename-$t').disabled=true;
				
			}
		}
		FormCheckFields$t();
</script>

";	
					
					
	echo $tpl->_ENGINE_parse_body($html);	
}

function item_save(){
	$EXEC=false;
	$ID=$_POST["ID"];
	if(strpos($_POST["sitename"],"//")>0){
		$parsed_url=parse_url($_POST["sitename"]);
		if(isset($parsed_url['port'])){$port=":{$parsed_url['port']}";}
		$_POST["sitename"]="{$parsed_url["scheme"]}://{$parsed_url["host"]}$port";
	}else{
		$_POST["sitename"]="http://{$_POST["sitename"]}";
	}
		
	if($ID==0){
		$sql="INSERT IGNORE INTO artica_caches_mirror (sitename,maxfilesize,`maxsitesize`,enabled,`TimeExec`) 
		VALUES ('{$_POST["sitename"]}','{$_POST["maxfilesize"]}',
		'{$_POST["maxsitesize"]}',1,'{$_POST["TimeExec"]}')";
		$EXEC=true;
		
		
	}else{
		$sql="UPDATE artica_caches_mirror SET maxfilesize='{$_POST["maxfilesize"]}',
		maxsitesize='{$_POST["maxsitesize"]}',
		TimeExec='{$_POST["TimeExec"]}' 
		WHERE ID='$ID'";
		
		
	}

	$q=new mysql_squid_builder();
	
	if(!$q->FIELD_EXISTS("artica_caches_mirror","TimeExec")){
		$sql="ALTER TABLE `artica_caches_mirror` ADD `TimeExec` SMALLINT(10) NOT NULL DEFAULT '10080'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo trim("Mysql Error:".$q->mysql_error);}
		return;
	}
	if(!$q->FIELD_EXISTS("artica_caches_mirror","ToDelete")){
		$sql="ALTER TABLE `artica_caches_mirror` ADD `ToDelete` SMALLINT(1) NOT NULL DEFAULT '0',ADD INDEX(`ToDelete`)";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo trim("Mysql Error:".$q->mysql_error);}
		return;
	}	
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo trim("Mysql Error:".$q->mysql_error);return;}
	if($EXEC){
		$sock=new sockets();
		$sock->getFrameWork("squid.php?Hypercache-mirror=yes");
	}
	
	
}

function item_enable(){
	$sql="UPDATE artica_caches_mirror SET enabled='{$_POST["value"]}' WHERE ID='{$_POST["item-enable"]}'";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo trim("Mysql Error:".$q->mysql_error);}	
	
}

function item_delete(){
	$q=new mysql_squid_builder();
	$sock=new sockets();
	

	if(!$q->FIELD_EXISTS("artica_caches_mirror","ToDelete")){
		$sql="ALTER TABLE `artica_caches_mirror` ADD `ToDelete` SMALLINT(1) NOT NULL DEFAULT '0',ADD INDEX(`ToDelete`)";
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			echo $q->mysql_error;
			return;
		}
	
	}

	$q->QUERY_SQL("UPDATE artica_caches_mirror SET ToDelete=1 WHERE ID='{$_POST["delete-item"]}'","artica_backup");
	if(!$q->ok){echo trim("Mysql Error:".$q->mysql_error);return;}	
	$sock->getFrameWork("squid.php?hypercache-delete=yes");

}

?>

	
