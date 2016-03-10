<?php
//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.artica.inc');
include_once('ressources/class.ini.inc');
include_once('ressources/class.milter.greylist.inc');
include_once('ressources/class.artica.graphs.inc');
include_once('ressources/class.maincf.multi.inc');
include_once('ressources/class.spamassassin.inc');


if(isset($_GET["popup-global-black-add"])){popup_add();exit;}
if(isset($_GET["delete-id-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_POST["BLK"])){Save();exit;}
table();

function delete_js(){
	header("content-type: application/x-javascript");
	$q=new mysql();
	$tpl=new templates();
	$sql="SELECT * FROM userpref WHERE `prefid`='{$_GET["delete-id-js"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$text=$tpl->javascript_parse_text("{delete} {$ligne["preference"]} {$ligne["value"]}");
	
	$page=CurrentPageName();
	$t=time();
	$html="
var xcall$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	$('#SPAMASSASSIN_DOMAIN_BLWL').flexReload();
}

function xFunct$t(){
	if(!confirm('$text')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete','{$_GET["delete-id-js"]}');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xcall$t);
}

xFunct$t();
";
	echo $html;

}

function delete(){
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM userpref WHERE `prefid`='{$_POST["delete"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
}


function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$add=$tpl->_ENGINE_parse_body("{add}");
	if($_GET["hostname"]==null){$_GET["hostname"]="master";}
	if($_GET["ou"]==null){$_GET["ou"]="master";}
	$explain=$tpl->_ENGINE_parse_body("{blacklist_global_explain}");
	$sender=$tpl->_ENGINE_parse_body("{sender}");
	$score=$tpl->_ENGINE_parse_body("{score}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$new_item=$tpl->_ENGINE_parse_body("{new_item}");
	$t=time();

	$popup_title=$tpl->_ENGINE_parse_body("{$_GET["domain"]}:{black list}/{white list}");
	$html="<table class='SPAMASSASSIN_DOMAIN_BLWL' style='display: none' id='SPAMASSASSIN_DOMAIN_BLWL' style='width:99%'></table>
	<script>
	var mem_$t='';
	var selected_id=0;
	$(document).ready(function(){
	$('#SPAMASSASSIN_DOMAIN_BLWL').flexigrid({
	url: '$page?search=yes&t=$t&domain={$_GET["domain"]}&ou={$_GET["ou"]}',
	dataType: 'json',
	colModel : [
	{display: '&nbsp;', name : 'none4', width : 110, sortable : false, align: 'left'},
	{display: '<span style=font-size:18px>$sender</span>', name : 'value', width : 959, sortable : false, align: 'left'},
	{display: '&nbsp;', name : 'none2', width : 110, sortable : false, align: 'left'},

	],
	buttons : [
	{name: '<strong style=font-size:18px>$new_item</strong>', bclass: 'add', onpress : GlobalBlackListAdd$t},
	],
	searchitems : [
	{display: '$sender', name : 'value'},
	],
	sortname: 'preference',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:30px>$popup_title</span>',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true

});
});

function GlobalBlackListAdd$t(){
YahooWin4('890','$page?popup-global-black-add=yes&domain={$_GET["domain"]}&ou={$_GET["ou"]}','{$_GET["domain"]}::$popup_title');
}

function GlobalBlackRefresh(){
$('#table-$t').flexReload();
}

var x_GlobalBlackDelete$t= function (obj) {
var tempvalue=obj.responseText;
if(tempvalue.length>3){alert(tempvalue);return;}
$('#row'+mem_$t).remove();
}

var x_GlobalBlackDisable= function (obj) {
var tempvalue=obj.responseText;
if(tempvalue.length>3){alert(tempvalue);}

}

function GlobalBlackDelete(key){
	var XHR = new XHRConnection();
	mem_$t=key;
	XHR.appendData('GlobalBlackDelete',key);
	XHR.appendData('hostname','{$_GET["hostname"]}');
	XHR.appendData('ou','{$_GET["ou"]}');
	XHR.sendAndLoad('$page', 'GET',x_GlobalBlackDelete$t);
}

function GlobalBlackDisable(ID){
	var XHR = new XHRConnection();
	XHR.appendData('ID',ID);
	XHR.appendData('hostname','{$_GET["hostname"]}');
	XHR.appendData('ou','{$_GET["ou"]}');
	if(document.getElementById('enabled_'+ID).checked){XHR.appendData('GlobalBlackDisable',1);}else{XHR.appendData('GlobalBlackDisable',0);}
	XHR.sendAndLoad('$page', 'GET',x_GlobalBlackDisable);
}

</script>
";

echo $html;

}

function popup_add(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$t=time();
	$domain=$_GET["domain"];
	$ARRAY[null]="{select}";
	$ARRAY["whitelist_from"]="{whitelist}";
	$ARRAY["blacklist_from"]="{blacklist}";

	$html="
	<div style='width:98%' class=form>
	<div style='font-size:18px;margin-bottom:20px' class=explain>{spamass_wbl_explain}</div>
	<table style='width:100%'>
	<tr>
		<td style='font-size:22px' class=legend>{type}:</td>
		<td style='font-size:22px'>". Field_array_Hash($ARRAY, "BLK-$t",null,"style:font-size:22px")."</td>
	</tr>			
	<tr>
		<td style='font-size:22px' class=legend>{pattern}:</td>
		<td style='font-size:22px'>". Field_text("pattern-$t",null,"font-size:22px;width:650px",null,null,null,false,"Check$t(event)")."</td>
	</tr>			
	<tr>
		<td colspan=2 align='right'>". button("{add}", "Save$t()",34)."</td>
	</tr>
	</table>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#SPAMASSASSIN_DOMAIN_BLWL').flexReload();
	YahooWin4Hide();
	
}
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('domain','$domain');
	XHR.appendData('BLK',document.getElementById('BLK-$t').value);
	XHR.appendData('pattern',document.getElementById('pattern-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t,true);

}

function Check$t(e){
	if(!checkEnter(e)){return;}
	Save$t();
	
}

</script>";
echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function Save(){
	
	if($_POST["BLK"]==null){return;}
	$spam=new spamassassin();
	$domain=trim($_POST["domain"]);
	$pattern=trim($_POST["pattern"]);
	if($pattern==null){return;}
	if($domain==null){return;}
	$xglobal="%$domain";
	$q=new mysql();
	$q->QUERY_SQL("INSERT INTO userpref (username,preference,value) VALUES ('$xglobal','{$_POST["BLK"]}','$pattern');","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
}

function search(){
	$tpl=new templates();
	$users=new usersMenus();
	$q=new mysql();

	$xglobal="%{$_GET["domain"]}";
	$search='%';
	$table="userpref";
	$page=1;
	$total=0;
	$MyPage=CurrentPageName();
	
	if(!$q->TestingConnection()){json_error_show("Connection to MySQL server failed");}
	if($q->COUNT_ROWS("userpref","artica_backup")==0){json_error_show("no data");}

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$table="(SELECT * FROM userpref WHERE (preference ='whitelist_from' AND `username`='$xglobal') 
	OR (preference='blacklist_from' AND `username`='$xglobal') ) as t";
	
	
	

	$searchstring=string_to_flexquery();
	$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!$q->ok){json_error_show($q->mysql_error." LINE:".__LINE__);}
	$total = $ligne["TCOUNT"];

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();


	$sql="SELECT *  FROM $table WHERE 1 $searchstring $ORDER $limitSql";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){json_error_show($q->mysql_error." LINE:".__LINE__);}
	if(mysql_num_rows($results)==0){json_error_show("no row $sql",1);}
	$score=0;
	
	$ARRAY["whitelist_from"]=$tpl->javascript_parse_text("{whitelist}");
	$ARRAY["blacklist_from"]=$tpl->javascript_parse_text("{blacklist}");
	
	$ARRAY2["whitelist_from"]="ok32.png";
	$ARRAY2["blacklist_from"]="okdanger32.png";

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$delete=imgsimple("delete-32.png","{delete}","Loadjs('$MyPage?delete-id-js={$ligne["prefid"]}')");
		$prefid=$ligne["prefid"];
		$type=$ligne["preference"];
		$icon=$ARRAY2[$type];
		$text=$ARRAY[$type];

		$data['rows'][] = array(
				'id' => $ligne['prefid'],
				'cell' => array(
						"<center><img src='img/$icon'></center>",
						"<span style='font-size:22px'>$text <code>{$ligne["value"]}</code></span>",
						"<center>$delete</center>" )
		);
	}


	echo json_encode($data);
}