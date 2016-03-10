<?php
	if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.ini.inc');
	
	$users=new usersMenus();
	if(!$users->AsSquidAdministrator){
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		die();
	}
	if(isset($_POST["BandwidthLimit"])){SaveRule();exit;}
	if(isset($_GET["id-js"])){ID_JS();exit;}
	if(isset($_GET["id-popup"])){ID_POPUP();exit;}
	if(isset($_GET["search"])){search();exit;}
	if(isset($_GET["delete-js"])){delete_js();exit;}
	if(isset($_POST["delete"])){delete();exit;}
	if(isset($_GET["button"])){genbutton();exit;}
	table();
	
	
function create_table(){
	$q=new mysql_squid_builder();
	
	$sql="CREATE TABLE IF NOT EXISTS `bandquotas_status` (
	`ID` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	 PatternGroup VARCHAR(90) NOT NULL,
	zDate datetime NOT NULL,
	ruleid BIGINT(10) NOT NULL,
	size BIGINT UNSIGNED NOT NULL,
	percent smallint(2) NOT NULL DEFAULT 0,
	freeze smallint(2) NOT NULL DEFAULT 0,
	KEY `ruleid` (`ruleid`),
	KEY `size` (`size`),
	KEY `percent` (`percent`),
	KEY `zDate` (`zDate`),
	KEY `freeze` (`freeze`)
	)  ENGINE = MYISAM;";
	$q->QUERY_SQL($sql);
	
	
	if(!$q->ok){echo $q->mysql_error_html();die();}	
	
	if(!$q->FIELD_EXISTS("bandquotas", "RuleName")){
		$sql="ALTER TABLE `bandquotas` ADD `RuleName` varchar(255) default NULL";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error_html();die();}
	}
	
	
}
function delete_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$zmd5=$_GET["zmd5"];
	$nic=$_GET["nic"];
	$xtable=$_GET["xtable"];
	echo "
var xAdd$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#SQUID_QUOTA_BDW_STATUS').flexReload();
}
function Add$t(){
	var XHR = new XHRConnection();
	XHR.appendData('delete', '{$_GET["delete-js"]}');
	XHR.sendAndLoad('$page', 'POST',xAdd$t);
}
Add$t();";



}
function delete(){
	$q=new mysql_squid_builder();
	
	$sql="SELECT freeze FROM bandquotas_status WHERE ID={$_POST["delete"]}";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	
	$freeze=$ligne["freeze"];
	if($freeze==1){$freeze=0;}else{$freeze=1;}
	
	$sql="UPDATE bandquotas_status SET `freeze`='$freeze' WHERE `ID`='{$_POST["delete"]}'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
}
function ID_JS(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$ID=$_GET["id-js"];

	if($ID>0){
		$title=$tpl->javascript_parse_text("{rule}:$ID");
	}else{
		$title=$tpl->javascript_parse_text("{new_rule}");
	}
	echo "YahooWin('990','$page?id-popup=yes&ID=$ID','$title')";


}

function table(){
	create_table();
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$TB_HEIGHT=450;
	$TABLE_WIDTH=920;
	$TB2_WIDTH=400;
	$ROW1_WIDTH=629;
	$ROW2_WIDTH=163;
	
	$new_rule=$tpl->_ENGINE_parse_body("{zDate}");
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$mode=$tpl->javascript_parse_text("{mode}");
	$member=$tpl->javascript_parse_text("{member}");
	$disable=$tpl->javascript_parse_text("{disable}");
	$bandwitdh=$tpl->javascript_parse_text("{bandwitdh}");
	$apply_firewall_rules=$tpl->javascript_parse_text("{refresh}");
	$size=$tpl->javascript_parse_text("{size}");
	$rule=$tpl->javascript_parse_text("{rule}");
	$title=$tpl->javascript_parse_text("{quotas_bandwidth} {status}");
	
	$buttons="
	buttons : [
		{name: '<strong style=font-size:18px>$apply_firewall_rules</strong>', bclass: 'Apply', onpress : FW$t},
	
	],	";
	$html="
	<table class='SQUID_QUOTA_BDW_STATUS' style='display: none' id='SQUID_QUOTA_BDW_STATUS' style='width:99%'></table>
<script>
$(document).ready(function(){
	$('#SQUID_QUOTA_BDW_STATUS').flexigrid({
	url: '$page?search=yes',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:18px>&nbsp;</span>', name : 'freeze', width :70, sortable : false, align: 'center'},
	{display: '<span style=font-size:18px>$rule</span>', name : 'ruleid', width :507, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$member</span>', name : 'PatternGroup', width :265, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$size</span>', name : 'QuotaSizeBytes', width :142, sortable : true, align: 'right'},
	{display: '<span style=font-size:18px>$bandwitdh</span>', name : 'percent', width :110, sortable : true, align: 'right'},
	{display: '<span style=font-size:18px>$disable</span>', name : 'freeze', width :70, sortable : false, align: 'center'},
	
	],
	$buttons
	
	searchitems : [
	{display: '$member', name : 'PatternGroup'},
	],
	
	sortname: 'ID',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true
	
	});
	});
	

function FW$t(){
	Loadjs('squid.quotasband.status.progress.php');
}
	
function NewRule$t(){
	Loadjs('$page?id-js=0');
}

	
</script>";
echo $html;

}


function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$database="squidlogs";
	$search='%';
	$table="bandquotas_status";
	$page=1;
	$ORDER=null;
	$allow=null;

	$total=0;
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("no data");;}


	$searchstring=string_to_flexquery();


	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}


	$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
	$total = $ligne["TCOUNT"];

	if(!isset($_POST['rp'])){$_POST['rp']=50;}
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}

	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT * FROM $table  WHERE 1 $searchstring $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show($q->mysql_error_html(),1);}
	if(mysql_num_rows($results)==0){json_error_show("no data $sql");}

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(!$q->ok){json_error_show($q->mysql_error);}
	$fontsize=18;


	$all=$tpl->javascript_parse_text("{all}");

	while ($ligne = mysql_fetch_assoc($results)) {
		$mouse="OnMouseOver=\"this.style.cursor='pointer'\" OnMouseOut=\"this.style.cursor='default'\"";
		$linkstyle="style='text-decoration:underline'";
		$color="black";
		$img="ok-32.png";
		$ruleid=$ligne["ruleid"];
		$PatternGroup=$ligne["PatternGroup"];
		

		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT RuleName FROM bandquotas WHERE ID=$ruleid"));
		$RuleName=utf8_encode($ligne2["RuleName"]);

		$percent=$ligne["percent"];
		$zflag=$ligne["zflag"];

		$link="<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('$MyPage?id-js={$ligne["ID"]}');\"
		style='text-decoration:underline;color:$color'>";

		if($RuleName==null){$RuleName="Rule ID $ruleid";}

		
		$delete=imgsimple("apply-config-32.png",null,"Loadjs('$MyPage?delete-js={$ligne["ID"]}')");

		/*{display: '<span style=font-size:18px>$rule</span>', name : 'ruleid', width :213, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$member</span>', name : 'PatternGroup', width :372, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$size</span>', name : 'QuotaSizeBytes', width :111, sortable : true, align: 'right'},
	{display: '<span style=font-size:18px>$bandwitdh</span>', name : 'percent', width :110, sortable : true, align: 'right'},
	{display: '<span style=font-size:18px>$disable</span>', name : 'freeze', width :70, sortable : false, align: 'center'},

	{*/

		
		if($zflag==1){$img="warning-panneau-32.png";}
		$QuotaSizeBytes=FormatBytes($ligne["size"]/1024);
		if($ligne["freeze"]==1){$color="#8a8a8a";$img="ok32-grey.png";}
		
		

		$data['rows'][] = array(
				'id' => $ligne["ID"],
				'cell' => array(
						"<center style='font-size:18px'><img src='img/$img'></center>",
						"<span style='font-size:18px;color:$color'>$RuleName</a></span>",
						"<span style='font-size:18px;color:$color'>$PatternGroup</a></span>",
						"<span style='font-size:18px;color:$color'>$QuotaSizeBytes</span>",
						"<span style='font-size:18px;color:$color'>{$percent}%</a></span>",
						"<center style='font-size:18px'>$delete</center>",

							

				)
		);
	}


	echo json_encode($data);

}