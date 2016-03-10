<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	
	
	if(isset($_GET["items"])){items();exit;}
	if(isset($_GET["popup-ad-js"])){js_popup_ad();exit;}
	if(isset($_GET["popup-ad-tab"])){tab_ad();exit;}
	if(isset($_GET["popup-ad-popup"])){popup_ad();exit;}
	if(isset($_POST["hostname"])){popup_save();exit;}
	if(isset($_GET["popup-ad-groups"])){popup_groups();exit;}
	if(isset($_POST["groups"])){popup_groups_save();exit;}
	if(isset($_GET["delete-ad-js"])){delete_ad_js();exit;}
	if(isset($_POST["deletead"])){delete_ad();exit;}
table();



function js_popup_ad(){
	$tpl=new templates();
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$md5=$_GET["md5"];
	$ruleid=$_GET["ruleid"];
	$title=$tpl->javascript_parse_text("{new_connection}");
	if($_GET["md5"]<>null){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT hostname FROM hotspot_activedirectory WHERE zmd5='$md5'"));
		$title=$ligne["hostname"];
		$ruleid=$ligne["ruleid"];
	}
	
	$md5=urlencode($_GET["md5"]);
	echo "YahooWin4('750','$page?popup-ad-tab=yes&t={$_GET["t"]}&md5=$md5&ruleid=$ruleid','$title');";
	
}


function delete_ad_js(){
	header("content-type: application/x-javascript");
	$md5=$_GET["md5"];
	$t=$_GET["t"];
	$tt=time();
	$page=CurrentPageName();

echo "
var xSave$tt= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#flexRT{$_GET["t"]}').flexReload();
	
}


function Save$tt(){
	var XHR = new XHRConnection();
	XHR.appendData('deletead','$md5');	
	XHR.appendData('md5','$md5');
	XHR.sendAndLoad('$page', 'POST',xSave$tt);
	}
Save$tt();";	
	

	
}

function delete_ad(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$md5=$_POST["md5"];
	$sql="DELETE FROM hotspot_activedirectory WHERE zmd5='$md5'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("hotspot.php?remove-cache=yes");
}


function tab_ad(){
	$tpl=new templates();
	$page=CurrentPageName();
	$md5=$_GET["md5"];
	$ruleid=$_GET["ruleid"];
	$title=$tpl->javascript_parse_text("{new_connection}");
	$array["popup"]=$title;
	if($md5<>null){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM hotspot_activedirectory WHERE zmd5='$md5'"));
		$title=$ligne["hostname"];
		$array["popup"]=$title;
		$array["groups"]="{groups2}";
	}
	
	
	$fontsize=18;
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?popup-ad-$num=yes&t={$_GET["t"]}&md5=$md5&ruleid=$ruleid\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
	}
	
	echo build_artica_tabs($html, "hotspot_ad_tab$md5");
	
	
	
}

function popup_groups(){
	$tpl=new templates();
	$page=CurrentPageName();
	$md5=$_GET["md5"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groups FROM hotspot_activedirectory WHERE zmd5='$md5'"));
	$groups=$ligne["groups"];
	$t=time();
	
	$html="
	<div class=explain style='font-size:16px'>{hotspot_ad_groups_explain}</div>		
	<textarea style='width:100%;height:245px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
	Courier,monospace;background-color:white;color:black;font-weight:bold' id='groups-$t'>$groups</textarea>
	<center style='margin-top:20px'>". button("{apply}","Save$t()",28)."</div>
<script>
				
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#flexRT{$_GET["t"]}').flexReload();
	
}


function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('groups',document.getElementById('groups-$t').value);	
	XHR.appendData('md5','$md5');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
</script>			
";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function popup_groups_save(){
	$md5=$_POST["md5"];
	$groups=mysql_escape_string2($_POST["groups"]);
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("UPDATE hotspot_activedirectory set `groups`='$groups' WHERE zmd5='$md5'");
	if(!$q->ok){echo $q->mysql_error;}
	
}

function popup_ad(){
	$tpl=new templates();
	$page=CurrentPageName();
	$md5=$_GET["md5"];
	$title=$tpl->javascript_parse_text("{new_connection}");
	$btname="{add}";
	$close=1;
	$ruleid=$_GET["ruleid"];
	if($md5<>null){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM hotspot_activedirectory WHERE zmd5='$md5'"));
		$title=$ligne["hostname"];
		$ruleid=$ligne["ruleid"];
		$btname="{apply}";
		$close=0;
	}
	
	$Timez[0]="{unlimited}";
	$Timez[30]="30 {minutes}";
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
	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
	$t=time();
	$html="<div style='width:98%' class=form>
	<table style='width:100%'>".
	Field_text_table("hostname-$t", "{hostname}",$ligne["hostname"],22,null,350).
	Field_checkbox_table("enabled-$t","{enabled}",$ligne["enabled"],$fontsize=22,null).
	"<tr>
		<td colspan=2 align='right'><hr>".button($btname,"Save$t()",36)."</td>
	</tr>
	</table>
	</div>			
<script>
				
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	var close=$close;
	if(close==1){YahooWin4Hide();}
	$('#flexRT{$_GET["t"]}').flexReload();
	ExecuteByClassName('SearchFunction');
}


function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('hostname',document.getElementById('hostname-$t').value);
	XHR.appendData('ruleid','$ruleid');
	XHR.appendData('md5','$md5');
	if(document.getElementById('enabled-$t').checked){XHR.appendData('enabled',1); }else{XHR.appendData('enabled',0);}
	XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function popup_save(){
	$md5=$_POST["md5"];
	$q=new mysql_squid_builder();
	$hostname=$_POST["hostname"];
	$enabled=$_POST["enabled"];
	$ruleid=$_POST["ruleid"];
	$ttl=$_POST["ttl"];
	if($md5==null){
		$md5=md5("$hostname$ruleid");
		$sql="INSERT IGNORE INTO `hotspot_activedirectory` 
		(hostname,enabled,ruleid,zmd5) VALUES 
		('$hostname','1','$ruleid','$md5')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error." $sql";return;}
		return;
	}
	
	$q->QUERY_SQL("UPDATE `hotspot_activedirectory` SET hostname='$hostname',enabled=$enabled WHERE zmd5='$md5'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("hotspot.php?remove-cache=yes");
}


function table(){
	$today=date("Y-m-d");
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$TB_HEIGHT=500;
	$TB_WIDTH=710;
	$members=$tpl->javascript_parse_text("{members}");
	$MAC=$tpl->javascript_parse_text("{MAC}");
	$ttl=$tpl->javascript_parse_text("{ttl}");
	$finaltime=$tpl->javascript_parse_text("{re_authenticate_each}");
	$endtime=$tpl->javascript_parse_text("{endtime}");
	
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS("hotspot_activedirectory")){$q->check_hotspot_tables();}
	$q->QUERY_SQL("DELETE FROM `hotspot_activedirectory` WHERE ruleid=0");
	
	$hostname=$tpl->javascript_parse_text("{hostname}");
	$enabled=$tpl->javascript_parse_text("{enabled}");
	$new_connection=$tpl->javascript_parse_text("{new_connection}");
	$parameters=$tpl->javascript_parse_text("{parameters}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$created=$tpl->javascript_parse_text("{created}");
	
	$title=$tpl->javascript_parse_text("{hotspot_ad_title}");
	

	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$new_connection</strong>', bclass: 'Add', onpress : NewCNx$t},
	
	],";
	
	$html="
	<div id='query-explain'></div>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	<script>
	var mem$t='';
	$(document).ready(function(){
	$('#flexRT$t').flexigrid({
	url: '$page?items=yes&t=$t&ruleid={$_GET["ruleid"]}',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:18px>$hostname</span>', name : 'hostname', width :515, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$enabled</span>', name : 'enabled', width :98, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'none', width :55, sortable : true, align: 'center'},
	],
	$buttons
	
	searchitems : [
	{display: '$hostname', name : 'hostname'},
	
	],
	sortname: 'hostname',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:22px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});
	});


	
	function NewCNx$t(){
		Loadjs('$page?popup-ad-js=yes&md5=&t=$t&ruleid={$_GET["ruleid"]}');
	}
</script>";
	
			echo $html;
}

function items(){
	$myPage=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$table="hotspot_activedirectory";
	
	
	
	$t=$_GET["t"];
	$tm=array();
	$search='%';
	$page=1;
	
	
	$FORCE_FILTER="AND ruleid='{$_GET["ruleid"]}'";

	if(strpos($table, ",")==0){
		if(!$q->TABLE_EXISTS($table)){
			json_error_show("$table: No such table",0,true);
		}
	}

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show($q->mysql_error);}
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show($q->mysql_error);}
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}

	


	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql);

	if(!$q->ok){json_error_show($q->mysql_error);}
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	$unlimited=$tpl->_ENGINE_parse_body("{unlimited}");
	$minutes=$tpl->_ENGINE_parse_body("{minutes}");

	if(mysql_num_rows($results)==0){json_error_show("No data",0);}


	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$hostname=$ligne["hostname"];
		$zmd5=$ligne["zmd5"];
		$ttl=$ligne["ttl"];
		$delete=imgsimple("delete-48.png",null,"Loadjs('$myPage?delete-ad-js=yes&md5=$zmd5&t=$t')");
		$enabled=Field_checkbox("enable_$zmd5", 1,$ligne["enabled"],"Loadjs('$myPage?enable-ad-js=yes&md5=$zmd5&t=$t')");
	
		$js="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$myPage?popup-ad-js=yes&md5=$zmd5&t=$t');\"
		style='text-decoration:underline'>";
		
		$data['rows'][] = array(
				'id' => $zmd5,
				'cell' => array(
						"<span style='font-size:24px;color:$color'>$js$hostname</a></span>",
						"<span style='font-size:18px;color:$color'>$enabled</a></span>",
						"<center>$delete</a></center>",
							
				)
		);
}


echo json_encode($data);
}

