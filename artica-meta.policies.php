<?php
include_once('ressources/class.templates.inc');
include_once('ressources/class.mysql-meta.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.system.nics.inc');
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

$users=new usersMenus();
if(!$users->AsArticaMetaAdmin){
	$tpl=new templates();
	echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";die();

}

if(isset($_POST["ID"])){policy_save();exit;}

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["policy-js"])){policy_js();exit;}
if(isset($_GET["delete-js"])){policy_delete_js();exit;}
if(isset($_POST["policy-delete"])){policy_delete();exit;}
if(isset($_GET["policy-edit"])){policy_edit();exit;}
if(isset($_GET["policy-popup"])){policy_popup();exit;}


js();

function js(){
	header("content-type: application/x-javascript");
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{policies}");
	$function=urlencode($_GET["function"]);
	echo "YahooWin2('850','$page?table=yes&function=$function','$title');";
	
	
}

function policy_delete_js(){
	header("content-type: application/x-javascript");
	$ID=$_GET["ID"];
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{new_policy}");
	if($ID>0){
		$q=new mysql_meta();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT policy_name FROM policies WHERE ID='$ID'"));
		$title=$tpl->javascript_parse_text($ligne["policy_name"]);
	
	}
	
	$text=$tpl->javascript_parse_text("{policy}: $title - {delete} ?");
	
	$html="
var xcall$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#ARTICA_META_MAIN_TABLE').flexReload();
	$('#ARTICA_META_POLICY_TABLE').flexReload();
}
	
function xFunct$t(){
	if(!confirm('$text')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('policy-delete','$ID');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xcall$t);
}
	
	xFunct$t();
	";
	echo $html;	
	
}

function policy_js(){
	header("content-type: application/x-javascript");
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{new_policy}");
	
	if($ID>0){
		$q=new mysql_meta();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT policy_name FROM policies WHERE ID='$ID'"));
		$title=$tpl->javascript_parse_text($ligne["policy_name"]);
		
	}
	
	echo "YahooWin4('850','$page?policy-popup=yes&ID=$ID','$title');";
	
}

function policy_edit(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_meta();
	$t=time();
	$ID=$_GET["policy-id"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM policies WHERE ID='$ID'"));
	
	
	
	$html="
	<div style='width:95%' class=form>
	<table style='width:100%'>
	". Field_checkbox_table("enabled-$t", "{enabled}",$ligne["enabled"])."
	<tr>
		<td class=legend style='font-size:18px'>{name}:</td>
		<td>". Field_text("policy_name-$t",utf8_encode($ligne["policy_name"]),"font-size:18px")."</td>
	</tr>
				
	
	<tr>
	<td colspan=2 align='right'>". button("{apply}", "Save$t()",26)."</td>
	</tr>
	</table>
<script>
	var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#ARTICA_META_POLICY_TABLE').flexReload();
}
		
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ID','$ID');
	var enabled=0;
	XHR.appendData('policy_name',document.getElementById('policy_name-$t').value);
	if(document.getElementById('enabled-$t').checked){enabled=1;}
	XHR.appendData('enabled',enabled);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	}	


function policy_popup(){
	$ID=$_GET["ID"];
	if($ID==0){return policy_new();}
	$q=new mysql_meta();
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT policy_name,policy_type FROM policies WHERE ID='$ID'"));
	
	$page=CurrentPageName();
	$tpl=new templates();
	
	
	
	$policy_type=$ligne["policy_type"];
	$policy_name=utf8_encode($ligne["policy_name"]);
	
	if($policy_type==1){
		$array["apache"]=$q->policy_type[$policy_type];
	}
	if($policy_type==3){
		$array["update"]=$q->policy_type[$policy_type];
	}	
	if($policy_type==4){
		$array["smtpnotif"]=$q->policy_type[$policy_type];
	}
	
	$array["policy"]=$policy_name;
	$array["hosts"]="{hosts}";
	
	
	while (list ($num, $ligne) = each ($array) ){
	
		if($num=="apache"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"artica-meta.policies.apache.php?policy-id=$ID")."\"><span style='font-size:18px'>$ligne</span></a></li>\n";
			continue;
		}
		
		if($num=="update"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"artica-meta.policies.update.php?policy-id=$ID")."\"><span style='font-size:18px'>$ligne</span></a></li>\n";
			continue;
		}	

		if($num=="smtpnotif"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"artica-meta.policies.metanotifs.php?policy-id=$ID")."\"><span style='font-size:18px'>$ligne</span></a></li>\n";
			continue;
		}	

		if($num=="hosts"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"artica-meta.policies.hosts.php?policy-id=$ID")."\"><span style='font-size:18px'>$ligne</span></a></li>\n";
			continue;
		}
		
	
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?policy-edit=yes&policy-id=$ID\"><span style='font-size:18px'>$ligne</span></a></li>\n");
	}
	
	echo build_artica_tabs($html, "meta-policy-$ID");
	
	
	
}

function policy_save(){
	$q=new mysql_meta();
	if($_POST["ID"]==0){
		$sql=$q->SQL_ADD_FROM_POST("ID","policies");
	}else{
		$sql=$q->SQL_EDIT_FROM_POST("ID","policies");
	}
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
	
}

function policy_delete(){
	$q=new mysql_meta();
	$q->delete_policy($_POST["policy-delete"]);
}

function policy_new(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_meta();
	$t=time();
	$html="
	<div style='width:95%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{name}:</td>
		<td>". Field_text("policy_name-$t",null,"font-size:18px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{policy}:</td>
		<td>". Field_array_Hash($q->policy_type,"policy_type-$t",null,"style:font-size:18px")."</td>
	</tr>				
	<tr>
	<td colspan=2 align='right'>". button("{add}", "Save$t()",26)."</td>
	</tr>
	</table>
<script>
var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#ARTICA_META_POLICY_TABLE').flexReload();
	YahooWin4Hide();
}			
			
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ID','0');
	XHR.appendData('policy_name',document.getElementById('policy_name-$t').value);
	XHR.appendData('policy_type',document.getElementById('policy_type-$t').value);
	XHR.appendData('enabled',1);
	
	XHR.sendAndLoad('$page', 'POST',xSave$t);	
}
</script>	
";
	
	echo $tpl->_ENGINE_parse_body($html);
}



function table(){
	
	
	$page=CurrentPageName();
	$tpl=new templates();
	
	$t=time();
	$new_policy=$tpl->javascript_parse_text("{new_policy}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$memory=$tpl->javascript_parse_text("{memory}");
	$load=$tpl->javascript_parse_text("{load}");
	$version=$tpl->javascript_parse_text("{version}");
	$servername=$tpl->javascript_parse_text("{servername2}");
	$status=$tpl->javascript_parse_text("{status}");
	$events=$tpl->javascript_parse_text("{events}");
	$type=$tpl->javascript_parse_text("{type}");
	$policies=$tpl->javascript_parse_text("{policies}");
	$packages=$tpl->javascript_parse_text("{packages}");
	$policies=$tpl->javascript_parse_text("{policies}");
	$groups=$tpl->javascript_parse_text("{groups2}");
	$tablewith=691;
	$compilesize=35;
	$size_elemnts=50;
	$size_size=58;
	$delete=$tpl->javascript_parse_text("{delete}");
	$categorysize=387;
	$function=urlencode($_GET["function"]);
	$q=new mysql_meta();
	
	if($_GET["function"]<>null){
		$delete=$tpl->javascript_parse_text("{link}");
	}
	
	$q->CheckTables();
	
	$buttons="	buttons : [
	{name: '<strong style=font-size:18px>$new_policy</strong>', bclass: 'add', onpress : NewPolicy$t},
	
	],";
	
	
	$t=time();
	$html="
	
<table class='ARTICA_META_POLICY_TABLE' style='display: none' id='ARTICA_META_POLICY_TABLE' style='width:1200px'></table>
<script>
$(document).ready(function(){
	$('#ARTICA_META_POLICY_TABLE').flexigrid({
	url: '$page?search=yes&function=$function',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:18px>status</span>', name : 'icon1', width : 50, sortable : false, align: 'center'},
	{display: '<span style=font-size:18px>$policies</span>', name : 'policy_name', width : 250, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$type</span>', name : 'policy_type', width : 250, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$groups</span>', name : 'null1', width : 70, sortable : true, align: 'center'},
	{display: '<span style=font-size:18px>$delete</span>', name : 'delete', width : 60, sortable : true, align: 'center'},
	
	],
$buttons
	searchitems : [
	{display: '$policies', name : 'policy_name'},
	],
	sortname: 'policy_name',
	sortorder: 'asc',
	usepager: true,
	title: '<strong style=font-size:30px>Meta Server $policies</strong>',
	useRp: true,
	rpOptions: [10, 20, 30, 50,100,200],
	rp:50,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true
	
	});
	});

	
	
	function NewPolicy$t(){
		Loadjs('$page?policy-js=yes&ID=0');
	}
	
	function MetaProxyWhiteList(){
		Loadjs('squid.whitelist-meta.php');
	}
	
	function Policies$t(){
		Loadjs('artica-meta.policies.php');
	
	}
	
	function SwitchToArtica(){
	$('#dansguardian2-category-$t').flexOptions({url: '$page?category-search=yes&minisize={$_GET["minisize"]}&t=$t&artica=1'}).flexReload();
	}
	
	function SaveAllToDisk(){
	Loadjs('$page?compile-all-dbs-js=yes')
	
	}
	
	function LoadCategoriesSize(){
	Loadjs('dansguardian2.compilesize.php')
	}
	
	function CategoryDansSearchCheck(e){
	if(checkEnter(e)){CategoryDansSearch();}
	}
	
	function CategoryDansSearch(){
	var se=escape(document.getElementById('category-dnas-search').value);
	LoadAjax('dansguardian2-category-list','$page?category-search='+se,false);
	
	}
	
	function DansGuardianCompileDB(category){
	Loadjs('ufdbguard.compile.category.php?category='+category);
	}
	
	function CheckStatsApplianceC(){
	LoadAjax('CheckStatsAppliance','$page?CheckStatsAppliance=yes',false);
	}
	
	var X_PurgeCategoriesDatabase= function (obj) {
	var results=obj.responseText;
	if(results.length>2){alert(results);}
	RefreshAllTabs();
	}
	
	function PurgeCategoriesDatabase(){
	if(confirm('$purge_catagories_database_explain')){
	var XHR = new XHRConnection();
	XHR.appendData('PurgeCategoriesDatabase','yes');
	AnimateDiv('dansguardian2-category-list');
	XHR.sendAndLoad('$page', 'POST',X_PurgeCategoriesDatabase);
	}
	
	}
	
	var X_TableCategoryPurge= function (obj) {
	var results=obj.responseText;
	if(results.length>2){alert(results);}
	$('#dansguardian2-category-$t').flexReload();
	}
	
	function TableCategoryPurge(tablename){
	if(confirm('$purge_catagories_table_explain')){
	var XHR = new XHRConnection();
	XHR.appendData('PurgeCategoryTable',tablename);
	XHR.sendAndLoad('dansguardian2.databases.compiled.php', 'POST',X_TableCategoryPurge);
	}
	}
	
	function Packages$t(){
		Loadjs('artica-meta.packages.php');
	}
	
	
	
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function search(){
	$MyPage=CurrentPageName();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_meta();	
	$table="policies";
	
	
	$searchstring=string_to_flexquery();
	$page=1;

	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	if($searchstring<>null){
		$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: $q->mysql_error.<br>$sql",1);}
		$total = $ligne["tcount"];
		
	}else{
		$total = $q->COUNT_ROWS($table);
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	if(!is_numeric($rp)){$rp=50;}

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql ";	
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){if($q->mysql_error<>null){json_error_show(date("H:i:s")."<br>SORT:{$_POST["sortname"]}:<br>Mysql Error [L.".__LINE__."]: $q->mysql_error<br>$sql",1);}}
	if(mysql_num_rows($results)==0){json_error_show("no data",1);}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	$fontsize="18";
	
	
	$free_text=$tpl->javascript_parse_text("{free}");
	$computers=$tpl->javascript_parse_text("{computers}");
	$overloaded_text=$tpl->javascript_parse_text("{overloaded}");
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$icon_warning_32="warning32.png";
		$icon_red_32="32-red.png";
		$icon="ok-32.png";
		$icon_disabled="ok32-grey.png";
		$color="black";
		if($ligne["enabled"]==0){$icon=$icon_disabled;$color="#898989";}
		
		
		$style="<span style='font-size:{$fontsize}px;color:$color'>";


		$policy_name=utf8_encode($ligne["policy_name"]);
		$policy_type=$tpl->_ENGINE_parse_body($q->policy_type[$ligne["policy_type"]]);
		$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?delete-js=yes&ID={$ligne["ID"]}')");
		
		if($_GET["function"]<>null){
			$delete=imgsimple("arrow-right-32.png",null,"{$_GET["function"]}('{$ligne["ID"]}')");
			
		}
		
		if(trim($policy_type)==null){
			$policy_type="{policy_type} ID:{$ligne["policy_type"]}";
		}
		
		$ligneCount=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(zmd5) as tcount FROM metapolicies_link WHERE `policy-id`='{$ligne["ID"]}'"));
		$CountDeGroup=$ligneCount["tcount"];
		
		
		$js="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('$MyPage?policy-js=yes&ID={$ligne["ID"]}');\" 
		style='text-decoration:underline;color:$color'>";
		$cell=array();
		$cell[]="<img src=\"img/$icon\">";
		$cell[]="$style$js$policy_name</a></span>";
		$cell[]="$style$policy_type</span>";
		$cell[]="$style$CountDeGroup</span>";
		$cell[]="$style$delete</span>";
		
		
	$data['rows'][] = array(
		'id' => $ligne['uuid'],
		'cell' => $cell
		);
	}
	
	
echo json_encode($data);	
	
}

function time_diff_min($xtime){
	$data1 = $xtime;
	$data2 = time();
	$difference = ($data2 - $data1);
	$results=intval(round($difference/60));
	if($results<0){$results=1;}
	return $results;
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}