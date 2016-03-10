<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.ini.inc');
include_once('ressources/class.squid.inc');
include(dirname(__FILE__)."/ressources/class.influx.inc");


	$user=new usersMenus();
	if(!$user->AsWebStatisticsAdministrator){
		$tpl=new templates();
		echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."')";
		exit;
	}
	if(isset($_POST["zmd5"])){save();exit;}
	if(isset($_GET["popup"])){popup();exit;}

js();


function js(){
	header("content-type: application/x-javascript");
	$md5=$_GET["zmd5"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT title FROM reports_cache WHERE `zmd5`='$md5'"));
	
	
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text($ligne["title"]);
	echo "YahooWin6('950','$page?popup=yes&zmd5={$_GET["zmd5"]}&t={$_GET["t"]}','$title');";
}

function popup(){
	$page=CurrentPageName();
	
	$tpl=new templates();
	$t=time();

	$members["MAC"]="{MAC}";
	$members["USERID"]="{uid}";
	$members["IPADDR"]="{ipaddr}";
	
	$q=new postgres_sql();
	$Selectore=$q->fieldSelectore();
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT params,title,report_type FROM reports_cache WHERE `zmd5`='{$_GET["zmd5"]}'"));
	if(!$q->ok){echo $q->mysql_error_html();}
	
	$params=unserialize($ligne["params"]);
	
	
	
	$FROM_DATE=date("Y-m-d",$params["FROM"]);
	$FROM_TIME=date("H:i",$params["FROM"]);
	
	$TO_DATE=date("Y-m-d",$params["TO"]);
	$TO_TIME=date("H:i",$params["TO"]);
	$USER=$params["USER"];
	$searchsites=$params["searchsites"];
	$searchuser=$params["searchuser"];
	
	if($ligne["report_type"]=="WEBSITES"){
		$nextFunction="LoadAjax('WEBSITES_STATS_MAIN_GRAPH','squid.statistics.websites.php?main=yes&zmd5={$_GET["zmd5"]}&t={$_GET["t"]}');";
	}
	if($ligne["report_type"]=="FLOW"){
		$nextFunction="LoadAjax('WEBSITES_STATS_MAIN_GRAPH','squid.statistics.flow.php?graph1=yes&t={$_GET["t"]}&container=graph-$t&zmd5={$_GET["zmd5"]}&t={$_GET["t"]}');";
	}	
	if($ligne["report_type"]=="CATEGORIES"){
		$nextFunction="LoadAjax('CATEGORIES_STATS_MAIN_GRAPH','squid.statistics.categories.php?main=yes&zmd5={$_GET["zmd5"]}&t={$_GET["t"]}');";
	}
	if($ligne["report_type"]=="WEBFILTERING"){
		$nextFunction="LoadAjax('WEBFILTERING_STATS_MAIN_GRAPH','squid.statistics.webfiltering.php?main=yes&zmd5={$_GET["zmd5"]}&t={$_GET["t"]}');";
	}
	
	
	
	

	$nextFunction_encoded=urlencode(base64_encode($nextFunction));
$stylelegend="style='vertical-align:top;font-size:18px;padding-top:5px' nowrap";
$html="<div style='width:98%;margin-bottom:20px' class=form>
<table style='width:100%'>
<tr style='height:50px'>
	<td style='vertical-align:middle;font-size:18px;' class=legend>{type}:</td>
	<td style='vertical-align:middle;font-size:18px;font-weight:bold'>{$ligne["report_type"]}</td>
</tr>
<tr>
	<td $stylelegend class=legend>{title2}:</td>
	<td style='vertical-align:top;font-size:18px'>".
	Field_text("title-$t",utf8_encode($ligne["title"]),";font-size:18px;width:710px")."</td>
</tr>

<tr>
	<td $stylelegend class=legend>{from_date}:</td>
	<td style='vertical-align:top;font-size:18px'>". field_date("from-date-$t",$FROM_DATE,";font-size:18px;width:160px",$Selectore)."
	&nbsp;".Field_text("from-time-$t",$FROM_TIME,";font-size:18px;width:82px")."</td>
</tr>
<tr>	
	<td $stylelegend class=legend>{to_date}:</td>
	<td style='vertical-align:top;font-size:18px'>". field_date("to-date-$t",$TO_DATE,";font-size:18px;width:160px",$Selectore)."
	&nbsp;". Field_text("to-time-$t",$TO_TIME,";font-size:18px;width:82px")."</td>
</tr>

<tr>
	<td $stylelegend class=legend>{members}:</td>
	<td style='vertical-align:top;font-size:18px;'>". Field_array_Hash($members,"members-$t",$USER,"blur()",null,0,"font-size:18px;")."</td>
</tr>
<tr>
	<td $stylelegend class=legend>{members} {search}:</td>
	<td style='vertical-align:top;font-size:18px;'>". Field_text("members-search-$t","$searchuser","font-size:18px;width:350px")."</td>
</tr>
<tr>
	<td $stylelegend class=legend>{websites} {search}:</td>
	<td style='vertical-align:top;font-size:18px;'>". Field_text("websites-search-$t","$searchsites","font-size:18px;width:350px")."</td>
</tr>
<tr style='height:50px'>
	<td style='vertical-align:top;font-size:18px;' colspan=2 align='right'>". button("{apply}","Run$t()",36)."</td>
</tr>
</table>
</div>
<script>
var xRun$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	Loadjs('squid.statistics.progress.php?zmd5={$_GET["zmd5"]}&NextFunction=$nextFunction_encoded&t=$t');
}

function Run$t(){
	var date1=document.getElementById('from-date-$t').value;
	var time1=document.getElementById('from-time-$t').value;
	var date2=document.getElementById('to-date-$t').value
	var time2=document.getElementById('to-time-$t').value;
	var user=document.getElementById('members-$t').value;
	var searchuser=encodeURIComponent(document.getElementById('members-search-$t').value);
	var searchsites=encodeURIComponent(document.getElementById('websites-search-$t').value);
	var title=encodeURIComponent(document.getElementById('title-$t').value);
	var XHR = new XHRConnection();
	XHR.appendData('zmd5','{$_GET["zmd5"]}');
	XHR.appendData('date1',date1+' '+time1+':00');
	XHR.appendData('USER',user);
	XHR.appendData('date2',date2+' '+time2+':00');
	XHR.appendData('searchuser',searchuser);
	XHR.appendData('searchsites',searchsites);
	XHR.appendData('title',title);
	XHR.sendAndLoad('$page', 'POST',xRun$t);

}
</script>
";

echo $tpl->_ENGINE_parse_body($html);


}

function save(){
	
	$zmd5=$_POST["zmd5"];
	
	while (list ($key, $val) = each ($_POST) ){
		$_POST[$key]=mysql_escape_string2(url_decode_special_tool($val));
	}
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT params FROM reports_cache WHERE `zmd5`='$zmd5'"));
	if(!$q->ok){echo $q->mysql_error_html();}
	
	
	$params=unserialize($ligne["params"]);
	$params["FROM"]=strtotime($_POST["date1"]);
	$params["TO"]=strtotime($_POST["date2"]);
	$params["USER"]=$_POST["USER"];
	$params["searchsites"]=$_POST["searchsites"];
	$params["searchuser"]=$_POST["searchuser"];
	
	$paramsS=mysql_escape_string2(serialize($params));
	
	$sql="UPDATE reports_cache SET `title`='{$_POST["title"]}',`params`='$paramsS'
	WHERE `zmd5`='$zmd5'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
	
	
	
}
	
//	$sql="INSERT IGNORE INTO `reports_cache` (`zmd5`,`title`,`report_type`,`zDate`,`params`) VALUES
//('$md5','$title','WEBSITES',NOW(),'$serialize')";