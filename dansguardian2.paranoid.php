<?php
if(isset($_GET["VERBOSE"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_GET["VERBOSE"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}

	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.squid.familysites.inc');
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	
	
	$users=new usersMenus();
	
	
if(isset($_GET["settings"])){settings();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["js"])){js();}
if(isset($_GET["rule-js"])){rule_js();exit;}

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["categories"])){categories();exit;}
if(isset($_GET["search"])){items();exit;}

if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_GET["delete-all-js"])){delete_all_js();exit;}
if(isset($_GET["popup"])){popup();exit;}


if(isset($_POST["UfdbEnableParanoidMode"])){UfdbEnableParanoidMode();exit;}
if(isset($_POST["delete"])){delete();exit;}
if(isset($_POST["object"])){save();exit;}
if(isset($_POST["delete-all"])){delete_all();exit;}

tabs();

function delete_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$delete_personal_cat_ask=$tpl->javascript_parse_text("{delete} {$_GET["delete-js"]} ?");
	$t=time();
$html="

var xDelete$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;};
	$('#PARANOID_TABLE').flexReload();
	
}

function DeletePersonalCat$t(){
	if(!confirm('$delete_personal_cat_ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete','{$_GET["delete-js"]}');
	XHR.sendAndLoad('$page', 'POST',xDelete$t);
}

DeletePersonalCat$t();";
	echo $html;
}

function delete_all_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$delete_personal_cat_ask=$tpl->javascript_parse_text("{delete_all} ?");
	$t=time();
	$html="
	
var xDelete$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;};
	$('#PARANOID_TABLE').flexReload();
	Loadjs('squid.paranoid.progress.php');
}
	
function DeletePersonalCat$t(){
	if(!confirm('$delete_personal_cat_ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-all','yes');
	XHR.sendAndLoad('$page', 'POST',xDelete$t);
}
	
	DeletePersonalCat$t();";
	echo $html;	
	
}

function delete(){
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM webfilters_paranoid WHERE `pattern`='{$_POST["delete"]}'");
	if(!$q->ok){echo $q->mysql_error;}
	
}
function delete_all(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("TRUNCATE TABLE webfilters_paranoid");
	if(!$q->ok){echo $q->mysql_error;}	
}


function rule_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$widownsize=995;
	$value=$_GET["value"];
	if($value==null){
		$addCat=$tpl->javascript_parse_text("{new_rule}");
	}else{
		$addCat=$tpl->javascript_parse_text("$value");
	}
	$t=$_GET["t"];
	$valuenc=urlencode($_GET["value"]);
	$html="YahooWin5('$widownsize','$page?popup=yes&value=$valuenc&t=$t','$addCat');";
	echo $html;
}

function popup(){
	$q=new mysql_squid_builder();
	$bt="{add}";
	$t=time();
	if($_GET["value"]<>null){
		$sql="SELECT * FROM webfilters_paranoid WHERE `pattern`='{$_GET["value"]}'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$bt="{apply}";
		if($ligne["object"]=="dstdomain"){$ligne["website"]=$ligne["pattern"];}
		if($ligne["object"]=="src"){$ligne["ipaddr"]=$ligne["pattern"];}
		if($ligne["object"]=="dstdomainsrc"){
			$f=explode("/",$ligne["pattern"]);
			$ligne["website"]=$f[1];
			$ligne["ipaddr"]=$f[0];
		}
	}else{
		
	}
	$tpl=new templates();
	$page=CurrentPageName();
	
	$objects["dstdomain"]="{dstdomain}";
	$objects["src"]="{src}";
	$objects["dstdomainsrc"]="{dstdomainsrc}";

$html="<div style='width:98%' class=form>
<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{type}:</td>
		<td>". Field_array_Hash($objects, "object-$t",$ligne["object"],"CheckObject$t()",'',0,"font-size:22px;")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{ipaddr}:</td>
		<td>". field_ipv4("ipaddr-$t",$ligne["ipaddr"],"font-size:22px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{website}:</td>
		<td>". Field_text("website-$t",$ligne["website"],"font-size:22px;width:350px")."</td>
	</tr>
	<tr>
	<tr>
		<td colspan=2 align='right'><hr>". button($bt,"Save$t()",30)."</td>
	</tr>
</table>
</div>	
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;};
	$('#PARANOID_TABLE').flexReload();
	var value='{$_GET["value"]}';
	if(value.length==0){
		YahooWin5Hide();
	}

}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('object',document.getElementById('object-$t').value);
	XHR.appendData('ipaddr',document.getElementById('ipaddr-$t').value);
	XHR.appendData('website',document.getElementById('website-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

function CheckObject$t(){
	var type=document.getElementById('object-$t').value;
	document.getElementById('ipaddr-$t').disabled=true;
	document.getElementById('website-$t').disabled=true;
	if(type=='dstdomain'){
		document.getElementById('website-$t').disabled=false;
	}
	if(type=='src'){
		document.getElementById('ipaddr-$t').disabled=false;
	}
	if(type=='dstdomainsrc'){
		document.getElementById('ipaddr-$t').disabled=false;
		document.getElementById('website-$t').disabled=false;
	}	
	
}
CheckObject$t();
</script>
";
echo $tpl->_ENGINE_parse_body($html);

}
function save(){
	$type=$_POST["object"];
	
	if($_POST["website"]<>null){
		
		if(strpos($_POST["website"], "://")){
			$parse_url=parse_url($_POST["website"]);
			$_POST["website"]=$parse_url["host"];
		}
		
		$fam=new squid_familysite();
		$_POST["website"]=$fam->GetFamilySites($_POST["website"]);
		
	}
	
	if($type=="src"){$pattern=$_POST["ipaddr"];}
	if($type=="dstdomain"){$pattern=$_POST["website"];}
	if($type=="dstdomainsrc"){$pattern="{$_POST["ipaddr"]}/{$_POST["website"]}";}
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM webfilters_paranoid WHERE `pattern`='$pattern'");
	
	$sql="INSERT IGNORE INTO `webfilters_paranoid` (pattern,object,zDate) 
	VALUES ('$pattern','$type',NOW())";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
}



function settings(){
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$UfdbEnableParanoidMode=intval($sock->GET_INFO("UfdbEnableParanoidMode"));
	$UfdbEnableParanoidBlockW=intval($sock->GET_INFO("UfdbEnableParanoidBlockW"));
	$UfdbEnableParanoidBlockR=intval($sock->GET_INFO("UfdbEnableParanoidBlockR"));
	$UfdbEnableParanoidBlockC=intval($sock->GET_INFO("UfdbEnableParanoidBlockC"));
	$UfdbEnableParanoidBlockU=intval($sock->GET_INFO("UfdbEnableParanoidBlockU"));
	if($UfdbEnableParanoidBlockW<1000){$UfdbEnableParanoidBlockW=5000;}
	if($UfdbEnableParanoidBlockC<1000){$UfdbEnableParanoidBlockC=5000;}
	if($UfdbEnableParanoidBlockR==0){$UfdbEnableParanoidBlockR=24;}
	if($UfdbEnableParanoidBlockU==0){$UfdbEnableParanoidBlockU=100;}	
	
	
	
	$p=Paragraphe_switch_img("{paranoid_mode}", "{paranoid_squid_mode_explain}","UfdbEnableParanoidMode",$UfdbEnableParanoidMode,null,1400);
	
	$html="<div style='width:98%' class=form>
		$p
		<table style='width:100%'>	
		
		
		<tr>
			<td class=legend style='font-size:22px'>{events_number_to_deny_a_website_and_the_user}:</td>
			<td>". Field_text("UfdbEnableParanoidBlockU",$UfdbEnableParanoidBlockU,"font-size:22px;width:150px")."</td>
		</tr>		
		
		<tr>
			<td class=legend style='font-size:22px'>{events_number_to_deny_a_website}:</td>
			<td>". Field_text("UfdbEnableParanoidBlockW",$UfdbEnableParanoidBlockW,"font-size:22px;width:150px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>{events_number_to_deny_a_computer}:</td>
			<td>". Field_text("UfdbEnableParanoidBlockC",$UfdbEnableParanoidBlockC,"font-size:22px;width:150px")."</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:22px'>{remove_rules_after}:</td>
			<td style='font-size:22px'>". Field_text("UfdbEnableParanoidBlockR",$UfdbEnableParanoidBlockR,"font-size:22px;width:150px")."&nbsp;{hours}</td>
		</tr>							
		<tr>
			<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",30)."</td>
		</tr>
		</table>
		</div>
					
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;};
	Loadjs('squid.paranoid.progress.php');

}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('UfdbEnableParanoidMode',document.getElementById('UfdbEnableParanoidMode').value);
	XHR.appendData('UfdbEnableParanoidBlockW',document.getElementById('UfdbEnableParanoidBlockW').value);
	XHR.appendData('UfdbEnableParanoidBlockC',document.getElementById('UfdbEnableParanoidBlockC').value);
	XHR.appendData('UfdbEnableParanoidBlockR',document.getElementById('UfdbEnableParanoidBlockR').value);
	XHR.appendData('UfdbEnableParanoidBlockU',document.getElementById('UfdbEnableParanoidBlockU').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function UfdbEnableParanoidMode(){
	
	$sock=new sockets();
	while (list ($num, $ligne) = each ($_POST) ){
		$sock->SET_INFO("$num", $ligne);
	}
	$sock->getFrameWork("squid.php?ufdbguard-tail-restart=yes");	
}






function tabs(){
	
	
	
	$squid=new squidbee();
	$tpl=new templates();
	$WEBFILTERING_TOP_MENU=$tpl->_ENGINE_parse_body(WEBFILTERING_TOP_MENU());
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	$SquidHTTPTemplateLanguage=$sock->GET_INFO("SquidHTTPTemplateLanguage");
	if($SquidHTTPTemplateLanguage==null){$SquidHTTPTemplateLanguage="en-us";}

	$array["settings"]="{parameters}";
	$array["table"]="{generated_rules}";
	$array["template"]="{template}";
	
	
	

	$fontsize=22;
	
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="template"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.templates.skin.php?TEMPLATE_TAB=yes&TEMPLATE_TITLE=ERR_PARANOID&lang=$SquidHTTPTemplateLanguage\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
	}
	$html= "<div style='font-size:30px;margin-bottom:20px'>$WEBFILTERING_TOP_MENU</div>".build_artica_tabs($html,'main_squid_paranoid',1490);
	echo $html;

}

function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS("webfilters_paranoid")){
		
		$sql="CREATE TABLE IF NOT EXISTS `webfilters_paranoid` (
				`pattern` VARCHAR( 90 ) NOT NULL,
				`object` VARCHAR( 20 ) NOT NULL DEFAULT 'dstdomain',
				`zDate` datetime NOT NULL,
				PRIMARY KEY (`pattern`),
				KEY `object` (`object`),
				KEY `pattern` (`pattern`)
			 )  ENGINE = MYISAM;";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error_html();}
	}

	
	$purge_catagories_database_explain=$tpl->javascript_parse_text("{purge_catagories_database_explain}");
	$purge_catagories_table_explain=$tpl->javascript_parse_text("{purge_catagories_table_explain}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$SaveToDisk=$tpl->_ENGINE_parse_body("{SaveToDisk}");
	$date=$tpl->_ENGINE_parse_body("{date}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$addCat=$tpl->_ENGINE_parse_body("{new_rule}");
	$tablewith=691;
	$compilesize=35;
	$size_elemnts=50;
	$size_size=58;
	$title=$tpl->javascript_parse_text("{generated_rules}");
	$deletetext=$tpl->javascript_parse_text("{purge}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$delete_all=$tpl->javascript_parse_text("{delete_all}");
	$Apply=$tpl->javascript_parse_text("{apply}");
	$t=time();
	
	$add=$tpl->javascript_parse_text("{add}");

	$buttons="	buttons : [
		{name: '<strong style=font-size:18px>$addCat</strong>', bclass: 'add', onpress : NewParanoidRule},
		{name: '<strong style=font-size:18px>$Apply</strong>', bclass: 'Search', onpress : Apply$t},
		{name: '<strong style=font-size:18px>$delete_all</strong>', bclass: 'Delz', onpress : Del$t},
		
		
		
	],";
	
	$t=time();
	$html="
			
<table class='PARANOID_TABLE' style='display: none' id='PARANOID_TABLE' style='width:99%'></table>
<script>
$(document).ready(function(){
	$('#PARANOID_TABLE').flexigrid({
	url: '$page?search=yes',
	dataType: 'json',
	colModel : [
	{display: '<strong style=font-size:18px>$date</strong>', name : 'zDate', width : 243, sortable : false, align: 'left'},
	{display: '<strong style=font-size:18px>$items</strong>', name : 'pattern', width : 825, sortable : false, align: 'left'},
	{display: '<strong style=font-size:18px>$delete</strong>', name : 'icon2', width : 121, sortable : false, align: 'center'},
	

	],
	$buttons
	searchitems : [
	{display: '$items', name : 'pattern'},
	],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rpOptions: [10, 20, 30, 50,100,200],
	rp:50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true

});
});

function Apply$t(){
	Loadjs('squid.paranoid.progress.php');
}
function NewParanoidRule(){
	Loadjs('$page?rule-js=yes&value=');
}

function Del$t(){
	Loadjs('$page?delete-all-js=yes&value=');
}


</script>

";

echo $tpl->_ENGINE_parse_body($html);


}

function items(){
	$MyPage=CurrentPageName();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$dans=new dansguardian_rules();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	$t=$_GET["t"];
	$OnlyPersonal=0;
	$error_license=null;
	$users=new usersMenus();

	
	
	$table="webfilters_paranoid";
	if($_POST["sortname"]=="categorykey"){$_POST["sortname"]="category";}
	
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
			writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
			if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: $q->mysql_error.<br>$sql",1);}
			$total = $ligne["tcount"];
	
		}else{
			$total = $q->COUNT_ROWS($table);
		}
	
		if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
		$pageStart = ($page-1)*$rp;
		$limitSql = "LIMIT $pageStart, $rp";
	
		
	
		$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql ";
		$results = $q->QUERY_SQL($sql);
		if(!$q->ok){if($q->mysql_error<>null){json_error_show(date("H:i:s")."<br>SORT:{$_POST["sortname"]}:<br>Mysql Error [L.".__LINE__."]: $q->mysql_error<br>$sql",1);}}
		if(mysql_num_rows($results)==0){json_error_show("Not found...",1);}
	
	
		$data = array();
		$data['page'] = $page;
		$data['total'] = $total;
		$data['rows'] = array();
	
	
		$enc=new mysql_catz();
	
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$color="black";
			$pattern=$ligne["pattern"];
			$Date=$ligne["zDate"];
			$object=$tpl->javascript_parse_text("{{$ligne["object"]}}");
			
			$patternenc=urlencode($pattern);
			$delete=imgsimple("delete-48.png",null,"Loadjs('$MyPage?delete-js=$patternenc')");
			
			
	
			
		$cell=array();
		$cell[]="<span style='font-size:18px;padding-top:15px;font-weight:bold'>$Date</div>";
		$cell[]="<span style='font-size:18px;padding-top:15px;font-weight:bold'>$pattern - $object</div>";
		$cell[]="<center>$delete</center>";
	
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => $cell
		);
		}
	
	
		echo json_encode($data);
	
}

