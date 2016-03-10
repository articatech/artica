<?php
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.iptables-chains.inc');
include_once('ressources/class.resolv.conf.inc');

$users=new usersMenus();
if(!$users->AsPostfixAdministrator){die();}

if(isset($_GET["search"])){search();exit;}
if(isset($_GET["popup-add-js"])){popup_js();exit;}
if(isset($_GET["popup-add"])){popup_add();exit;}
if(isset($_POST["items"])){SaveItems();exit;}
if(isset($_POST["ID"])){SaveItemID();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["remove"])){remove();exit;}
table();




function popup_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=intval($_GET["ID"]);
	if($ID==0){
		$title=$tpl->_ENGINE_parse_body("{new_item}");
		$html="YahooWin3(681,'$page?popup-add=yes','$title')";
		echo $html;
		return;
	}
	
	$q=new mysql();
	$sql="SELECT pattern FROM spamasssin_baddomains WHERE `ID`='{$_GET["ID"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$ligne["pattern"]=$tpl->javascript_parse_text($ligne["pattern"]);
	$html="YahooWin3(681,'$page?popup=yes&ID={$_GET["ID"]}','{$ligne["pattern"]}');";
	echo $html;
	
	

}

function popup_add(){
	
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$html="
	<div style='font-size:30px;margin-bottom:30px'>{new_item}</div>		
	<div class=explain style='font-size:18px'>{spamassassin_uris_explain}</div>
	<div style='width:98%' class=form>
		<center style='margin:10px'>
		<textarea id='text$t' style='font-family:Courier New;
		font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;
		overflow:auto;font-size:22px !important;width:99%;height:390px'></textarea>
		<hr>". button("{add}","Save$t()",30)."
		</center>
	</div>
<script>
	var xSave$t= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
		YahooWin3Hide();
		$('#SPAMASSASSIN_URIBL_TABLE').flexReload();
	}
	
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('items',encodeURIComponent(document.getElementById('text$t').value));
		XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
</script>
";
echo $tpl->_ENGINE_parse_body($html);	
}





function popup(){
	
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$sql="SELECT pattern FROM spamasssin_baddomains WHERE `ID`='{$_GET["ID"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	
	$html="
	<div style='font-size:30px;margin-bottom:30px'>{item}: {$_GET["ID"]}</div>		
	<div class=explain style='font-size:18px'>{spamassassin_uris_explain}</div>
	<div style='width:98%' class=form>
		<center style='margin:10px'>
		<textarea id='text$t' style='font-family:Courier New;
		font-weight:bold;width:100%;height:120px;border:5px solid #8E8E8E;
		overflow:auto;font-size:22px !important;width:99%'>{$ligne["pattern"]}</textarea>
		<hr>". button("{apply}","Save$t()",30)."
		</center>
	</div>
<script>
	var xSave$t= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
		YahooWin3Hide();
		$('#SPAMASSASSIN_URIBL_TABLE').flexReload();
	}
	
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('ID','{$_GET["ID"]}');
		XHR.appendData('item',encodeURIComponent(document.getElementById('text$t').value));
		XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
</script>
";
echo $tpl->_ENGINE_parse_body($html);						

	
}

function SaveItems(){
	
	$items=url_decode_special_tool($_POST["items"]);
	$items2=explode("\n",$items);
	
	while (list ($num, $ligne) = each ($items2) ){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		$ligne=mysql_escape_string2($ligne);
		$q=new mysql();
		$q->QUERY_SQL("INSERT IGNORE INTO spamasssin_baddomains (pattern,zdate) VALUE ('$ligne',NOW())","artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	
	

	
}

function SaveItemID(){
	$items=url_decode_special_tool($_POST["item"]);
	$items=mysql_escape_string2($items);
	$q=new mysql();
	$q->QUERY_SQL("UPDATE spamasssin_baddomains SET pattern='$items' WHERE ID='{$_POST["ID"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
}


function remove(){
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM spamasssin_baddomains WHERE ID='{$_POST["remove"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
}

function table(){
	$q=new mysql();
	$page=CurrentPageName();
	$tpl=new templates();
	$compile=$tpl->_ENGINE_parse_body("{compile}");
	$port=25;
	$t=time();
	$date=$tpl->_ENGINE_parse_body("{date}");
	$server=$tpl->_ENGINE_parse_body("{RHSBL}");
	$add=$tpl->_ENGINE_parse_body("{new_rule}");
	$add_websites=$tpl->_ENGINE_parse_body("{add}");
	$verify=$tpl->_ENGINE_parse_body("{analyze}");
	$log=$tpl->_ENGINE_parse_body("{log}");
	$enable=$tpl->_ENGINE_parse_body("{enable}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$delete_all=$tpl->_ENGINE_parse_body("{delete_all_items}");
	$pattern=$tpl->javascript_parse_text("{pattern}");
	$apply=$tpl->javascript_parse_text('{apply}');
	$title=$tpl->javascript_parse_text("{rules_on_urls}");
	if(!isset($_GET["hostname"])){$_GET["hostname"]="master";}
	if($_GET["hostname"]==null){$_GET["hostname"]="master";}
	
	
	$sql="CREATE TABLE IF NOT EXISTS `spamasssin_baddomains` (
			`ID` BIGINT( 100 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`pattern` VARCHAR(255) NOT NULL,
			 zdate TIMESTAMP NOT NULL,
			 KEY `zdate` (`zdate`),
			 UNIQUE KEY `pattern` (`pattern`)
			) ENGINE=MYISAM DEFAULT CHARSET=latin1;";
	
	$q->QUERY_SQL($sql,'artica_backup');
	
$buttons="
	buttons : [
		
		{name: '<strong style=font-size:18px>$add</strong>', bclass: 'Add', onpress : NewServer$t},
		{name: '<strong style=font-size:18px>$apply</strong>', bclass: 'apply', onpress : Apply$t},
		
	
		],";
	
		$html="
		<table class='SPAMASSASSIN_URIBL_TABLE' style='display: none' id='SPAMASSASSIN_URIBL_TABLE' style='width:100%'></table>
		<script>
		var xsite='';
		$(document).ready(function(){
		$('#SPAMASSASSIN_URIBL_TABLE').flexigrid({
		url: '$page?search=yes&hostname={$_GET["hostname"]}',
		dataType: 'json',
		colModel : [
		{display: '<span style=font-size:22px>ID</span>', name : 'ID', width : 100, sortable : false, align: 'center'},
		{display: '<span style=font-size:22px>$date</span>', name : 'date', width : 264, sortable : false, align: 'left'},
		{display: '<span style=font-size:22px>$pattern</span>', name : 'pattern', width : 914, sortable : false, align: 'left'},
		{display: '<span style=font-size:22px>$delete</span>', name : 'Del', width : 100, sortable : false, align: 'center'},
		],
		$buttons
		searchitems : [
		{display: '$pattern', name : 'pattern'},
		{display: 'ID', name : 'ID'},
		],
		sortname: 'ID',
		sortorder: 'desc',
		usepager: true,
		title: '<strong style=font-size:30px>$title</strong>',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: '99%',
		height: 550,
		singleSelect: true,
		rpOptions: [10, 20, 30, 50,100,200]
	
	});
	});
var xRemoveSpamAssassinURIBL= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	$('#SPAMASSASSIN_URIBL_TABLE').flexReload();
}

function NewServer$t(){
	Loadjs('$page?popup-add-js=yes&ID=0');
}

function Apply$t(){
	Loadjs('spamassassin.urls.progress.php');
}
	
	
function RemoveSpamAssassinURIBL(ID){
	var XHR = new XHRConnection();
	XHR.appendData('remove',ID);
	XHR.sendAndLoad('$page', 'POST',xRemoveSpamAssassinURIBL);
	}
	
</script>
	
	";
	echo $html;
}

function search(){
	$search='%';
	$page=1;
	
	$q=new mysql();
	$tpl=new templates();
	$MyPage=CurrentPageName();
	
	$sql_search=string_to_flexquery();
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}
		
	if($sql_search<>null){
	
	
		$sql="SELECT COUNT(*) AS TCOUNT FROM spamasssin_baddomains 1 $sql_search";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["tcount"];
	
	}else{
		$sql="SELECT COUNT(*) AS tcount FROM spamasssin_baddomains";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["tcount"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	
	$sql="SELECT * FROM spamasssin_baddomains WHERE 1 $sql_search $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){json_error_show($q->mysql_error,1);}
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("No data",1);}
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$pattern=$ligne["pattern"];
		$delete=imgsimple("delete-42.png",null,"RemoveSpamAssassinURIBL($ID)");
		$select="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?popup-add-js=yes&ID={$ligne["ID"]}');\"
		style='text-decoration:underline;font-size:26px'>";
		
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<center style='font-size:26px'>{$ligne["ID"]}</center>",
						"<strong style='font-size:26px'>{$ligne["zdate"]}</strong>",
						"<strong style='font-size:26px'>$select$pattern</a></strong>",
						"<center>$delete</center>")
		);
	
	
	}
	echo json_encode($data);
	
}


