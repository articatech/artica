<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.status.inc');
include_once('ressources/class.artica.graphs.inc');

$users=new usersMenus();
if(!$users->AsWebStatisticsAdministrator){die();}

if(isset($_GET["search"])){search();exit;}
if(isset($_GET["new-js"])){report_new();exit;}
if(isset($_POST["report_new"])){report_new_save();exit;}
if(isset($_GET["report-id"])){report_js();exit;}
if(isset($_GET["report-tab"])){report_tab();exit;}
if(isset($_GET["report-main"])){report_main();exit;}
if(isset($_POST["report_name"])){report_main_save();exit;}

if(isset($_GET["run-id"])){report_run_js();exit;}
if(isset($_POST["run-id"])){report_run();exit;}
if(isset($_GET["report-download"])){report_download();exit;}
if(isset($_GET["report-csv"])){report_csv();exit;}



if(isset($_GET["delete-js"])){report_delete_js();exit;}
if(isset($_POST["delete-report"])){report_delete_perform();exit;}
if(isset($_GET["report-logs-js"])){report_log_js();exit;}
if(isset($_GET["report-logs-popup"])){report_log_popup();exit;}
page();


function report_csv(){
	$ID=$_GET["report-csv"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT report_name,report_csv,report_csv_ext FROM `squid_reports` WHERE ID='$ID'"));
	$title=utf8_encode($ligne["report_name"]);
	$ext=$ligne["report_csv_ext"];
	if($ext=="zip"){
		header('Content-type: application/zip');
	}
	if($ext=="tgz"){
		header('Content-type: application/x-gzip');
	}	
	
	
	$title=str_replace(" ", "-", $title);
	$title=$title.".$ext";
	
	header('Content-Transfer-Encoding: binary');
	header("Content-Disposition: attachment; filename=\"$title\"");
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
	
	$fsize = strlen($ligne["report_csv"]);
	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	echo $ligne["report_csv"];	
	
}

function report_log_popup(){
	$ID=$_GET["report-logs-popup"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT report_log FROM `squid_reports` WHERE ID='$ID'"));
	$array=explode("\n",$ligne["report_log"]);
	krsort($array);
	echo "<textarea style='margin-top:5px;font-family:Courier New;
font-weight:bold;width:98%;height:446px;border:5px solid #8E8E8E;
overflow:auto;font-size:11px' id='text-$t'>".@implode("\n", $array)."</textarea>";
	
}

function report_download(){
	$ID=$_GET["report-download"];
	header('Content-type: application/x-pdf');
	
	$q=new mysql_squid_builder();
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT report_name,report_bin FROM `squid_reports` WHERE ID='$ID'"));
	$title=utf8_encode($ligne["report_name"]);
	
	$title=str_replace(" ", "-", $title);
	$title=$title.".pdf";
	
	header('Content-Transfer-Encoding: binary');
	header("Content-Disposition: attachment; filename=\"$title\"");
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
	
	$fsize = strlen($ligne["report_bin"]);
	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	echo $ligne["report_bin"];
	
}

function report_delete_perform(){
	
	$report_id=$_POST["delete-report"];
	$database="squidreport_$report_id";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DROP DATABASE $database");
	
	$q->QUERY_SQL("DELETE FROM squid_reports_websites WHERE report_id='$report_id'");
	$q->QUERY_SQL("DELETE FROM squid_reports_categories WHERE report_id='$report_id'");
	$q->QUERY_SQL("DELETE FROM squid_reports_members WHERE report_id='$report_id'");
	$q->QUERY_SQL("DELETE FROM squid_reports WHERE ID='$report_id'");
	if(!$q->ok){echo $q->mysql_error;}
	
}

function report_run_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ID=$_GET["ID"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT report_name FROM `squid_reports` WHERE ID='$ID'"));
	$title=utf8_encode($ligne["report_name"]);
	$run=$tpl->javascript_parse_text("{run}");
	$t=time();
	
	$html="
	var xFunction$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#SQUID_MAIN_REPORTS').flexReload();
	}
	
	function Function$t(){
	var alias=confirm('$run $title ?');
	if(alias){
		var XHR = new XHRConnection();
		XHR.appendData('run-id','{$_GET["ID"]}');
		XHR.sendAndLoad('$page', 'POST',xFunction$t);
		}
	}
	
	Function$t();
	";
	echo $html;	
}

function report_log_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ID=$_GET["report-logs-js"];
	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT report_name FROM `squid_reports` WHERE ID='$ID'"));
	$title=utf8_encode($ligne["report_name"]);
	$delete=$tpl->javascript_parse_text("{events}");
	$t=time();
	
	echo "YahooWin2(990,'$page?report-logs-popup=$ID','$title:$delete')";
	
}

function report_delete_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ID=$_GET["ID"];

	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT report_name FROM `squid_reports` WHERE ID='$ID'"));
	$title=utf8_encode($ligne["report_name"]);
	$delete=$tpl->javascript_parse_text("{delete}");
	$t=time();
	
	$html="
	var xFunction$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#SQUID_MAIN_REPORTS').flexReload();
	}
	
	function Function$t(){
	var alias=confirm('$delete $title ?');
	if(alias){
	var XHR = new XHRConnection();
	XHR.appendData('delete-report','{$_GET["ID"]}');
	XHR.sendAndLoad('$page', 'POST',xFunction$t);
	}
	}
	
	Function$t();
	";
	echo $html;
	}

function report_run(){
	
	$sock=new sockets();
	$sock->getFrameWork("squid.php?report-run={$_POST["run-id"]}");
}


function report_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ID=$_GET["ID"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT report_name FROM `squid_reports` WHERE ID='$ID'"));
	$title=utf8_encode($ligne["report_name"]);
	echo "YahooWin('850','$page?report-tab=yes&ID=$ID','$title')";
}

function report_tab(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$array["report-main"]='{panel}';
	$array["report-categories"]='{categories}';
	$array["report-websites"]='{websites}';
	$array["report-users"]='{members}';
	
	
	$font="style='font-size:18px'";
	
	while (list ($num, $ligne) = each ($array) ){
	
		if($num=="panel-week"){
			$html[]= "<li $font><a href=\"squid.traffic.panel.php?$num\"><span>$ligne</span></a></li>\n";
			continue;
		}
		
		if($num=="report-categories"){
			$html[]= "<li $font><a href=\"squid.stats.reports.categories.php?ID={$_GET["ID"]}\"><span>$ligne</span></a></li>\n";
			continue;
		}
		
		if($num=="report-users"){
			$html[]= "<li $font><a href=\"squid.stats.reports.members.php?ID={$_GET["ID"]}\"><span>$ligne</span></a></li>\n";
			continue;
		}		
		
		if($num=="report-websites"){
			$html[]= "<li $font><a href=\"squid.stats.reports.websites.php?ID={$_GET["ID"]}\"><span>$ligne</span></a></li>\n";
			continue;
		}
	
		$html[]= "<li $font><a href=\"$page?$num=yes&ID={$_GET["ID"]}\"><span>$ligne</span></a></li>\n";
	}
	
	
	echo build_artica_tabs($html, "report_single_tab")."";
	
	}
	
function report_main(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$dans=new dansguardian_rules();
	
	$cats=$dans->LoadBlackListes();
	while (list ($num, $ligne) = each ($cats) ){$newcat[$num]=$num;}
	$newcat[null]="{none}";
	
	$sql="SELECT DATE_FORMAT(zDate,'%Y-%m-%d') as tdate FROM tables_day ORDER BY zDate LIMIT 0,1";
	$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));
	$mindate=$ligne2["tdate"];
	
	$sql="SELECT DATE_FORMAT(zDate,'%Y-%m-%d') as tdate FROM tables_day ORDER BY zDate DESC LIMIT 0,1";
	$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));
	$maxdate=$ligne2["tdate"];
	
	$ID=$_GET["ID"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM `squid_reports` WHERE ID='$ID'"));
	$t=time();
	
	
	$report_days[0]="{date_range}";
	$report_days[2]="2 {days}";
	$report_days[7]="1 {week}";
	$report_days[15]="2 {weeks}";
	$report_days[-1]="{current_month}";
	$report_days[30]="1 {month}";
	$report_days[60]="2 {months}";
	$report_days[90]="3 {months}";
	$report_days[180]="6 {months}";
	$report_days[365]="1 {year}";
	
	
	

	
	$report_build_time_start=$ligne["report_build_time_start"];
	$report_build_time_end=$ligne["report_build_time_end"];
	
	if($report_build_time_start==0){
		$report_build_time_start=$mindate;
	}else{
		$report_build_time_start=date("Y-m-d",$report_build_time_start);
	}
	if($report_build_time_end==0){
		$report_build_time_end=$maxdate;
		
	}else{
		$report_build_time_end=date("Y-m-d",$report_build_time_end);
	}
	
	
	if($ligne["report_name"]==null){$ligne["report_name"]="New report";}
	
	$html="<div style='width:98%' class=form>
	<table style='width:100%'>
	". 
	Field_text_table("report_name-$t", "{report}",utf8_encode($ligne["report_name"]),18,null,350).
	Field_text_table("description-$t", "{description}",utf8_encode($ligne["description"]),18,null,350).
	Field_list_table("report_type-$t", "{type}", $ligne["report_type"],18,$q->report_types).
	Field_list_table("report_days-$t", "{from_the_last_time}", $ligne["report_days"],18,$report_days,"ReportDaysCheck()").
	
	"<tr>
		<td style='font-size:18px' class=legend>{from_date}:</td>
		<td>". field_date("report_build_time_start-$t",$report_build_time_start,"font-size:18px;padding:3px;width:120px","mindate:$mindate;maxdate:$maxdate")."</td>
	</tr>
<tr>
		<td style='font-size:18px' class=legend>{to_date}:</td>
		<td>". field_date("report_build_time_end-$t",$report_build_time_end,"font-size:18px;padding:3px;width:120px","mindate:$mindate;maxdate:$maxdate")."</td>
	</tr>		".
	
	
	Field_checkbox_table("recategorize-$t", "{recategorize}",$ligne["recategorize"],18).
	Field_checkbox_table("categorize-$t", "{categorize}",$ligne["categorize"],18).
	Field_checkbox_table("report_not_categorized-$t", "{report_not_categorized}",$ligne["report_not_categorized"],18).
	
	
	
	
	Field_button_table_autonome("{apply}", "Save$t",26).
	
	
	"</table>
	</div>	
<script>

	var xSave$t= function (obj) {
		var res=obj.responseText;
		
		if (res.length>3){
			alert(res);
			return;
		}
		$('#SQUID_MAIN_REPORTS').flexReload();
		RefreshTab('report_single_tab');
	}	
	
	function ReportDaysCheck(){
		document.getElementById('report_build_time_start-$t').disabled=true;
		document.getElementById('report_build_time_end-$t').disabled=true;
		var report_days=document.getElementById('report_days-$t').value;
		if(report_days==0){
			document.getElementById('report_build_time_start-$t').disabled=false;
			document.getElementById('report_build_time_end-$t').disabled=false;		
		}
	}

	function Save$t(){
		var XHR = new XHRConnection();
		
		XHR.appendData('ID',$ID);
		XHR.appendData('report_name',encodeURIComponent(document.getElementById('report_name-$t').value));
		XHR.appendData('description',encodeURIComponent(document.getElementById('description-$t').value));
		
		XHR.appendData('report_build_time_start',document.getElementById('report_build_time_start-$t').value);
		XHR.appendData('report_build_time_end',document.getElementById('report_build_time_end-$t').value);
		
		XHR.appendData('report_type',document.getElementById('report_type-$t').value);
		XHR.appendData('report_days',document.getElementById('report_days-$t').value);
		if(document.getElementById('recategorize-$t').checked){XHR.appendData('recategorize',1);}else{XHR.appendData('recategorize',0);}
		if(document.getElementById('categorize-$t').checked){XHR.appendData('categorize',1);}else{XHR.appendData('categorize',0);}
		if(document.getElementById('report_not_categorized-$t').checked){XHR.appendData('report_not_categorized',1);}else{XHR.appendData('report_not_categorized',0);}
		
		
		
		XHR.sendAndLoad('$page', 'POST',xSave$t);	
	}
	
ReportDaysCheck();
</script>
			
			
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}


function report_main_save(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	
	$_POST["report_name"]=url_decode_special_tool($_POST["report_name"]);
	$_POST["description"]=url_decode_special_tool($_POST["description"]);
	
	$_POST["report_build_time_start"]=strtotime($_POST["report_build_time_start"]." 00:00:00");
	$_POST["report_build_time_end"]=strtotime($_POST["report_build_time_end"]." 00:00:00");
	
	
	$array=FORM_CONSTRUCT_SQL_FROM_POST("squid_reports","ID");
	$sql=$array[1];
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}	$report_days[2]="2 {days}";
	$report_days[7]="1 {week}";
	$report_days[15]="2 {weeks}";
	$report_days[-1]="{current_month}";
	$report_days[30]="1 {month}";
	$report_days[60]="2 {months}";
	$report_days[90]="3 {months}";
	$report_days[180]="6 {months}";
	$report_days[365]="1 {year}";
}


function report_new(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$txt=$tpl->javascript_parse_text("{add_report_text}");
	$t=time();

	$html="
var xFunction$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#SQUID_MAIN_REPORTS').flexReload();
}

function Function$t(){
	var alias=prompt('$txt','New report');
	if(alias){
	var XHR = new XHRConnection();
		XHR.appendData('report_new',alias);
		XHR.sendAndLoad('$page', 'POST',xFunction$t);
	}
}

Function$t();
";
echo $html;

}	$report_days[2]="2 {days}";
	$report_days[7]="1 {week}";
	$report_days[15]="2 {weeks}";
	$report_days[30]="1 {month}";
	$report_days[60]="2 {months}";
	$report_days[90]="3 {months}";
	$report_days[180]="6 {months}";
	$report_days[365]="1 {year}";

function report_new_save(){
	$q=new mysql_squid_builder();
	$q->CheckTables(null,true);
	$zmd5=md5(time());
	$_POST["report_new"]=mysql_escape_string2($_POST["report_new"]);
	$q->QUERY_SQL("INSERT IGNORE INTO squid_reports (report_name,zmd5) VALUES ('{$_POST["report_new"]}','$zmd5')");
	if(!$q->ok){echo $q->mysql_error;}
	
	
}

function page(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$t=time();
	$alias=$tpl->_ENGINE_parse_body("{aliases}");
	$new_alias=$tpl->_ENGINE_parse_body("{new_alias}");

	$delete=$tpl->javascript_parse_text("{delete}");
	$aliases=$tpl->javascript_parse_text("{aliases}");
	$about2=$tpl->_ENGINE_parse_body("{about2}");
	$new_report=$tpl->javascript_parse_text("{new_report}");
	$report=$tpl->javascript_parse_text("{report}");
	$title=$tpl->javascript_parse_text("{statistics}:: {reports_center}");
	$progress=$tpl->javascript_parse_text("{progress}");
	$run=$tpl->javascript_parse_text("{run}");
	$report=$tpl->javascript_parse_text("{report}");
	$q=new mysql_squid_builder();
	


	$buttons="
	buttons : [
	{name: '<strong style=font-size:16px >$new_report</strong>', bclass: 'add', onpress : NewReport$t},
	],";

	
	$html="
	<table class='SQUID_MAIN_REPORTS' style='display: none' id='SQUID_MAIN_REPORTS' style='width:100%'></table>
	<script>
	$(document).ready(function(){
	$('#SQUID_MAIN_REPORTS').flexigrid({
	url: '$page?search=yes',
	dataType: 'json',
	colModel : [
	{display: '$report', name : 'report_name', width : 630, sortable : true, align: 'left'},
	{display: '$progress;', name : 'report_progress', width : 70, sortable : false, align: 'center'},
	{display: '$run;', name : 'run', width : 70, sortable : false, align: 'center'},
	{display: '$report', name : 'report', width : 70, sortable : false, align: 'center'},
	{display: 'csv', name : 'csv', width : 70, sortable : false, align: 'center'},
	{display: 'LOG', name : 'log', width : 70, sortable : false, align: 'center'},
	{display: '$delete;', name : 'delete', width : 70, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$report', name : 'report'},
	{display: '$progress', name : 'report_progress'},
	],
	sortname: 'report_name',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:18px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: '350',
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
});


function NewReport$t(){
	Loadjs('$page?new-js=yes');
}

var xSave$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('servername',encodeURIComponent('{$_GET["servername"]}'));
	XHR.appendData('servername_pattern',encodeURIComponent(document.getElementById('servername_pattern-$t').value));
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
	";

	echo $tpl->_ENGINE_parse_body($html);


}
function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$table="squid_reports";
	$q=new mysql_squid_builder();
	$FORCE=1;
	$t=$_GET["t"];
	

	$total=0;
	if($q->COUNT_ROWS($table,"artica_backup")==0){json_error_show("no data [".__LINE__."]",0);}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}


	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];

	}else{
		if(strlen($FORCE)>2){
			$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
			$total = $ligne["TCOUNT"];
		}else{
			$total = $q->COUNT_ROWS($table, "artica_events");
		}
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}


	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT *  FROM `$table` WHERE $FORCE $searchstring $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql",0);}

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	$CurrentPage=CurrentPageName();

	if(mysql_num_rows($results)==0){json_error_show("no data");}
	$searchstring=string_to_flexquery();


	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}
	$q1=new mysql();
	$t=time();

	$fontsize=22;
	$EngineCategorization=EngineCategorization();
	$report=$tpl->javascript_parse_text("{report}");
	$date_range=$tpl->javascript_parse_text("{date_range}");
	$category=$tpl->javascript_parse_text("{category}");
	$from_the_last_time=$tpl->javascript_parse_text("{from_the_last_time}");
	$report_not_categorized_text=$tpl->javascript_parse_text("{report_not_categorized}");
	$error_engine_categorization=$tpl->javascript_parse_text("{error_engine_categorization}");
	
	$span="<span style='font-size:{$fontsize}px'>";
	
	$report_days[2]="2 {days}";
	$report_days[-1]="{current_month}";
	$report_days[7]="1 {week}";
	$report_days[15]="2 {weeks}";
	$report_days[30]="1 {month}";
	$report_days[60]="2 {months}";
	$report_days[90]="3 {months}";
	$report_days[180]="6 {months}";
	$report_days[365]="1 {year}";

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$ID=$ligne["ID"];
		$description_text=null;
		$report_type=null;
		$report_days_text=null;
		$report_cat=null;
		$report_not_categorized=null;
		$report_progress_text=null;
		$report_name=utf8_encode($ligne["report_name"]);
		//32-run-grey.png
		
		$run=imgsimple("32-run.png",null,"Loadjs('$MyPage?run-id=yes&ID={$ligne["ID"]}')");
		$report_icon="<a href=\"$MyPage?report-download={$ligne["ID"]}\"><img src='img/32-download.png'></a>";
		$report_csv="<a href=\"$MyPage?report-csv={$ligne["ID"]}\"><img src='img/csv-32.png'></a>";
		$report_logs="<center style='margin-top:8px'><a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('$MyPage?report-logs-js={$ligne["ID"]}')\"
		><img src='img/eye-32.png'></a></center>";
		
		
		
		
		$js="Loadjs('$MyPage?report-id=yes&ID={$ligne["ID"]}')";
		
		$description=utf8_encode($ligne["description"]);
		if($description<>null){$description_text="<br><i style='font-size:16px'>$description</i>";}
		
		$delete=imgsimple("delete-42.png",null,"Loadjs('$MyPage?delete-js=yes&ID=$ID')");
		$href="<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:{$fontsize}px;text-decoration:underline'>";

		if($ligne["report_type"]>0){
			$report_type="<br><strong>$report:</strong>".$tpl->javascript_parse_text($q->report_types[$ligne["report_type"]]);
			if($ligne["report_type"]==1){
				if(!$EngineCategorization){
					$description_text=$description_text."<br><strong style='font-size:14px;color:#FF978A'>$error_engine_categorization</strong>";
				}
			}
			
			if($ligne["report_type"]==2){
				$report_type=$report_type.report_type_websites($ligne["ID"])." ".report_type_members($ligne["ID"]);
			}
			if($ligne["report_type"]==1){
				$report_type=$report_type.report_type_categories($ligne["ID"])." ".report_type_members($ligne["ID"]);
			}			
			
		}
		
		if($ligne["report_type"]==0){
			$run=imgsimple("32-run-grey.png",null,null);
			$report_icon="&nbsp;";
			$report_csv="&nbsp;";
			$report_logs="&nbsp;";
		}
		if($ligne["report_progress"]==0){
			$report_icon="&nbsp;";
			$report_csv="&nbsp;";
			$report_logs="&nbsp;";
		}
		if($ligne["report_progress"]>100){
			$report_icon="&nbsp;";
			$report_csv="&nbsp;";
		}		
			
		
		if($ligne["report_days"]>-100){
			$report_days_text="<br><strong>$from_the_last_time:</strong>".$tpl->javascript_parse_text($report_days[$ligne["report_days"]]);
				
		}
		
		$report_build_time_start=$ligne["report_build_time_start"];
		$report_build_time_end=$ligne["report_build_time_end"];
		$report_build_time_start=date("Y-m-d",$report_build_time_start);
		$report_build_time_end=date("Y-m-d",$report_build_time_end);
		
		if($ligne["report_days"]==0){
			$report_days_text="<br><strong>$date_range:</strong> $report_build_time_start - $report_build_time_end";
		}
		
		if($ligne["report_cat"]<>null){
			$report_cat=", <strong>$category:</strong> {$ligne["report_cat"]}";
		}
		
		if($ligne["report_not_categorized"]==1){
			$report_not_categorized="<br><strong><i>$report_not_categorized_text</strong></i>";
		}
		
		if($ligne["report_progress_text"]<>null){
			$report_progress_text="<br><strong style='color:#008D30'><i>".$tpl->javascript_parse_text($ligne["report_progress_text"])."</strong></i>";
		}
		
		$data['rows'][] = array(
				'id' => $ID,
				'cell' => array(
						"$span$href$report_name</a></span>$description_text$report_type$report_cat
							$report_days_text$report_not_categorized$report_progress_text",
						"$span{$ligne["report_progress"]}%</span>",
						$run,$report_icon,$report_csv,$report_logs,
						$delete
				)
		);

	}
	echo json_encode($data);

}


function EngineCategorization(){
	$sock=new sockets();
	$SquidPerformance=$sock->GET_INFO("SquidPerformance");
	$RemoteUfdbCat=intval($sock->GET_INFO("RemoteUfdbCat"));
	$EnableLocalUfdbCatService=intval($sock->GET_INFO("EnableLocalUfdbCatService"));
	if($EnableLocalUfdbCatService){return true;}
	if($RemoteUfdbCat==1){return true;}
	if($SquidPerformance==0){return true;}
}

function report_type_websites($report_id){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$sql="SELECT COUNT(*) as tcount FROM squid_reports_websites WHERE report_id=$report_id";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if($ligne["tcount"]>0){
		return $tpl->_ENGINE_parse_body(", <strong>{websites}:</strong> {$ligne["tcount"]} {items}"); 
	}
	
}
function report_type_categories($report_id){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$sql="SELECT COUNT(*) as tcount FROM squid_reports_categories WHERE report_id=$report_id";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if($ligne["tcount"]>0){
		return $tpl->_ENGINE_parse_body(", <strong>{categories}:</strong> {$ligne["tcount"]} {items}");
	}

}
function report_type_members($report_id){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$sql="SELECT COUNT(*) as tcount FROM squid_reports_members WHERE report_id=$report_id";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if($ligne["tcount"]>0){
		return $tpl->_ENGINE_parse_body(", <strong>{members}:</strong> {$ligne["tcount"]} {items}");
	}

}