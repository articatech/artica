<?php
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.user.inc');
include_once('ressources/class.langages.inc');
include_once('ressources/class.sockets.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.privileges.inc');
include_once('ressources/class.ChecksPassword.inc');
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");

session_start();
if($_SESSION["uid"]==null){ AskPasswordAuth("{realtime_requests}"); }


$users=new usersMenus();
if(!$users->AsWebStatisticsAdministrator){
	$tpl=new templates();
	echo "<script> alert('". $tpl->javascript_parse_text("`{$_SERVER['PHP_AUTH_USER']}/{$_SERVER['PHP_AUTH_PW']}` {ERROR_NO_PRIVS}")."'); </script>";
	die();
}
//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);

if(isset($_GET["js"])){js();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["events-list"])){events_list();exit;}

page();


function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	if(isset($_GET["wpad"])){$wpad="&wpad=yes";}
	$title=$tpl->_ENGINE_parse_body("{realtime_requests}::{$_GET["SearchString"]}");
	$html="YahooWin('1200','$page?popup=yes&SearchString={$_GET["SearchString"]}&minsize=1','$title')";
	echo $html;
	
	
}

function page(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$hostname=trim($sock->GET_INFO("myhostname"));
	$events=$tpl->_ENGINE_parse_body("{realtime_requests}");
	$please_wait=$tpl->_ENGINE_parse_body("{please_wait}");
echo "<!DOCTYPE html>
<html lang=\"en\">
<head>
	<meta http-equiv=\"X-UA-Compatible\" content=\"IE=9; IE=8\">
	<meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-type\" />
	<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/artica-theme/jquery-ui.custom.css\" />
	<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/jquery.jgrowl.css\" />
	<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/jquery.cluetip.css\" />
	<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/jquery.treeview.css\" />
	<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/flexigrid.pack.css\" />
	<link rel=\"stylesheet\" type=\"text/css\" href=\"/fonts.css.php\" />
	
	<title>$hostname $events</title>
	<link rel=\"icon\" href=\"/ressources/templates/Squid/favicon.ico\" type=\"image/x-icon\" />
	<link rel=\"shortcut icon\" href=\"/ressources/templates/Squid/favicon.ico\" type=\"image/x-icon\" />
	<script type=\"text/javascript\" language=\"javascript\" src=\"/mouse.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/XHRConnection.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/cookies.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/default.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery-1.8.3.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery-ui-1.8.22.custom.min.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jqueryFileTree.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.simplemodal-1.3.3.min.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.tools.min.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/flexigrid.pack.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/ui.selectmenu.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.cookie.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.blockUI.js\"></script>
</head>

<body style='background-color:white;margin:0px;padding:0px'>
<div id='mainaccess'>
	<center style='font-size:50px'>$please_wait</center>
	</div>
<script>
	LoadAjax('mainaccess','$page?popup=yes',true);
</script>
</body>
</html>";

}


function popup(){
	
	$sock=new sockets();
	$SquidNoAccessLogs=intval($sock->GET_INFO("SquidNoAccessLogs"));
	if($SquidNoAccessLogs==1){
		
		echo FATAL_ERROR_SHOW_128("{FATAL_SQUID_ACCESS_LOG}");
		return;
		
	}
	
	
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$events=$tpl->_ENGINE_parse_body("{events}");
	$zdate=$tpl->_ENGINE_parse_body("{zDate}");
	$proto=$tpl->_ENGINE_parse_body("{proto}");
	$uri=$tpl->_ENGINE_parse_body("{url}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	if(function_exists("date_default_timezone_get")){$timezone=" - ".date_default_timezone_get();}
	$title=$tpl->_ENGINE_parse_body("{realtime_requests}");
	$zoom=$tpl->_ENGINE_parse_body("{zoom}");
	$button1="{name: 'Zoom', bclass: 'Search', onpress : ZoomSquidAccessLogs},";
	$stopRefresh=$tpl->javascript_parse_text("{stop_refresh}");
	$logs_container=$tpl->javascript_parse_text("{logs_container}");
	$refresh=$tpl->javascript_parse_text("{refresh}");
	
	$items=$tpl->_ENGINE_parse_body("{items}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$SaveToDisk=$tpl->_ENGINE_parse_body("{SaveToDisk}");
	$addCat=$tpl->_ENGINE_parse_body("{add} {category}");
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$task=$tpl->_ENGINE_parse_body("{task}");
	$new_schedule=$tpl->_ENGINE_parse_body("{new_rotate}");
	$explain=$tpl->_ENGINE_parse_body("{explain_squid_tasks}");
	$run=$tpl->_ENGINE_parse_body("{run}");
	$task=$tpl->_ENGINE_parse_body("{task}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$filename=$tpl->_ENGINE_parse_body("{filename}");
	$empty=$tpl->_ENGINE_parse_body("{empty}");
	$askdelete=$tpl->javascript_parse_text("{empty_store} ?");
	$duration=$tpl->_ENGINE_parse_body("{duration}");
	$ext=$tpl->_ENGINE_parse_body("{extension}");
	$back_to_events=$tpl->_ENGINE_parse_body("{back_to_events}");
	$Compressedsize=$tpl->_ENGINE_parse_body("{compressed_size}");
	$realsize=$tpl->_ENGINE_parse_body("{realsize}");
	$delete_file=$tpl->javascript_parse_text("{delete_file}");
	$proto=$tpl->javascript_parse_text("{proto}");
	$MAC=$tpl->_ENGINE_parse_body("{MAC}");
	$reload_proxy_service=$tpl->_ENGINE_parse_body("{reload_proxy_service}");
	$table_size=855;
	$url_row=505;
	$member_row=276;
	$table_height=420;
	$distance_width=230;
	$tableprc="100%";
	$margin="-10";
	$margin_left="-15";
	$ip_field=161;
	$date_field=139;
	$uri_field=650;
	$uri_field=233;
	$cod_field=233;
	$size_field=106;
	$duration_field=89;
	$proto_field=43;
	$link_field=37;
	
	if($_GET["minsize"]==1){
		$uri_field=450;
		$date_field=76;
		$ip_field=161;
		$cod_field=103;
		$size_field=86;
		$duration_field=66;
	}
	
	
	if(isset($_GET["bypopup"])){
		$table_size=1019;
		$uri_field=529;
		$ip_field=190;
		$cod_field=203;
		$member_row=333;
		$distance_width=352;
		$proto_field=43;
		$margin=0;
		$margin_left="-5";
		$tableprc="99%";
		$button1="{name: '<strong id=refresh-$t>$stopRefresh</stong>', bclass: 'Reload', onpress : StartStopRefresh$t},";
		$table_height=590;
		$Start="StartRefresh$t()";
	}
	
	$q=new mysql_squid_builder();
	$countContainers=$q->COUNT_ROWS("squid_storelogs");
	if($countContainers>0){
		$button2="{name: '<strong id=container-log-$t>$logs_container</stong>', bclass: 'SSQL', onpress : StartLogsContainer$t},";
		$button_container="{name: '<strong id=container-log-$t>$back_to_events</stong>', bclass: 'SSQL', onpress : StartLogsSquidTable$t},";
		$button_container_delall="{name: '$empty', bclass: 'Delz', onpress : EmptyStore$t},";
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(Compressedsize) as tsize FROM squid_storelogs"));
		$title_table_storage="$logs_container $countContainers $files (".FormatBytes($ligne["tsize"]/1024).")";
	}
	
	$categories=$tpl->javascript_parse_text("{categories}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$error=$tpl->javascript_parse_text("{error}");
	$sitename=$tpl->javascript_parse_text("{sitename}");
	$autorefresh=$tpl->javascript_parse_text("{autorefresh}");
	$hostnames=$tpl->javascript_parse_text("{hostnames}");
	
	$all=$tpl->javascript_parse_text("{all}");
	$button3="{name: '<strong id=container-log-$t>$rotate_logs</stong>', bclass: 'Reload', onpress : SquidRotate$t},";
	
	
	$buttons[]="{name: '<strong>$autorefresh OFF</stong>', bclass: 'Reload', onpress : AutoRefresh$t},";
	
	
	$html="
	<div id='SQUID_ACCESS_LOGS_DIV'>		
	<table class='SQUID_ACCESS_LOGS_RT' style='display: none' id='SQUID_ACCESS_LOGS_RT' style='width:99%'></table>
	<input type='hidden' id='refreshenabled$t' value='0'>
	<input type='hidden' id='categoriesenabled$t' value='0'>
	<input type='hidden' id='hostnamesenabled$t' value='0'>
	
	<input type='hidden' id='refresh$t' value='0'>
	</div>
	<script>
	var mem$t='';
	function StartLogsSquidTable$t(){
		$('#SQUID_ACCESS_LOGS_RT').flexigrid({
			url: '$page?events-list=yes&minsize={$_GET["minsize"]}&SearchString={$_GET["SearchString"]}',
			dataType: 'json',
			colModel : [
			
			{display: '<span style=font-size:18px>$zdate</span>', name : 'zDate', width :$date_field, sortable : true, align: 'left'},
			{display: '<span style=font-size:18px>$ipaddr</span>', name : 'events', width : $ip_field, sortable : false, align: 'left'},
			{display: '<span style=font-size:18px>&nbsp;</span>', name : 'code', width : $cod_field, sortable : false, align: 'left'},
			{display: '<span style=font-size:18px>$proto</span>', name : 'proto', width : $proto_field, sortable : false, align: 'left'},
			{display: '<span style=font-size:18px>$uri</span>', name : 'events', width : $uri_field, sortable : false, align: 'left'},
			{display: '<span style=font-size:18px>LINK</span>', name : 'size2', width : $link_field, sortable : false, align: 'left'},
			{display: '<span style=font-size:18px>$size</span>', name : 'size', width : $size_field, sortable : false, align: 'left'},
			{display: '<span style=font-size:18px>$duration</span>', name : 'duration', width : $duration_field, sortable : false, align: 'left'},
			],
				
			buttons : [
				{name: '<strong style=font-size:18px id=SQUIDLOGS_REFRESH_LABEL>$autorefresh OFF</strong>', bclass: 'Reload', onpress : AutoRefresh$t},
				{name: '<strong style=font-size:18px id=SQUIDLOGS_CATEGORIES_LABEL>$categories OFF</strong>', bclass: 'Reload', onpress : Categories$t},
				{name: '<strong style=font-size:18px id=SQUIDLOGS_HOSTNAMES_LABEL>$hostnames OFF</strong>', bclass: 'Reload', onpress : HostNamesEnabled$t},
			],
				
	
			searchitems : [
			{display: '$all', name : 'sitename'},
			
			],
			sortname: 'zDate',
			sortorder: 'desc',
			usepager: true,
			title: '<span style=\"font-size:22px\">$title {$_GET["SearchString"]}</span>',
			useRp: true,
			rp: 50,
			showTableToggleBtn: false,
			width: '98.5%',
			height: 500,
			singleSelect: true,
			rpOptions: [10, 20, 30, 50,100,200,500]
	
		});
		
	if(document.getElementById('SQUID_INFLUDB_TABLE_DIV')){
		document.getElementById('SQUID_INFLUDB_TABLE_DIV').innerHTML='';
	}		
	
	}
	
function AutoRefreshAction$t(){
	if(!document.getElementById('refreshenabled$t')){return;}
	var enabled$t=parseInt(document.getElementById('refreshenabled$t').value);
	if(enabled$t==0){
		setTimeout('AutoRefreshAction$t()',1000);
		return;
	}
	var Count=parseInt(document.getElementById('refresh$t').value);
	
	if(Count<5){
		Count=Count+1;
		document.getElementById('refresh$t').value=Count;
		setTimeout('AutoRefreshAction$t()',1000);
		return;
	}
	document.getElementById('refresh$t').value=0;
	$('#SQUID_ACCESS_LOGS_RT').flexReload();
	setTimeout('AutoRefreshAction$t()',1000);
	
}
	
function AutoRefresh$t(){
	var enabled=parseInt(document.getElementById('refreshenabled$t').value);
	if( enabled ==0){
		document.getElementById('refreshenabled$t').value=1;
		document.getElementById('SQUIDLOGS_REFRESH_LABEL').innerHTML='$autorefresh ON';
		}
	if( enabled ==1){
		document.getElementById('refreshenabled$t').value=0;
		document.getElementById('SQUIDLOGS_REFRESH_LABEL').innerHTML='$autorefresh OFF';
	}
}
function Categories$t(){
	var enabled=parseInt(document.getElementById('categoriesenabled$t').value);
	if( enabled ==0){
		document.getElementById('categoriesenabled$t').value=1;
		document.getElementById('SQUIDLOGS_CATEGORIES_LABEL').innerHTML='$categories ON';
		MakeQueries$t();
		}
	if( enabled ==1){
		document.getElementById('categoriesenabled$t').value=0;
		document.getElementById('SQUIDLOGS_CATEGORIES_LABEL').innerHTML='$categories OFF';
		MakeQueries$t();
	}
}
function HostNamesEnabled$t(){
	var enabled=parseInt(document.getElementById('hostnamesenabled$t').value);
	if( enabled ==0){
		document.getElementById('hostnamesenabled$t').value=1;
		document.getElementById('SQUIDLOGS_HOSTNAMES_LABEL').innerHTML='$hostnames ON';
		MakeQueries$t();
		}
	if( enabled ==1){
		document.getElementById('hostnamesenabled$t').value=0;
		document.getElementById('SQUIDLOGS_HOSTNAMES_LABEL').innerHTML='$hostnames OFF';
		MakeQueries$t();
	}
}

function MakeQueries$t(){

	var baseuri='$page?events-list=yes&minsize={$_GET["minsize"]}&SearchString={$_GET["SearchString"]}';

	var enabled=parseInt(document.getElementById('hostnamesenabled$t').value);
	if( enabled ==1){
		baseuri=baseuri+'&fqdn=yes';
	}
	enabled=parseInt(document.getElementById('categoriesenabled$t').value);
	if( enabled ==1){
		baseuri=baseuri+'&categories-scan=yes';
	}
	
	$('#SQUID_ACCESS_LOGS_RT').flexOptions({url: baseuri}).flexReload();
}




StartLogsSquidTable$t();
setTimeout('AutoRefreshAction$t()',1000);
</script>";
echo $html;
}

function events_list(){
	$sock=new sockets();
	$catz=new mysql_catz();
	$IPClass=new IP();
	
	$sock->getFrameWork("squid.php?access-real=yes&rp={$_POST["rp"]}&query=".urlencode($_POST["query"])."&SearchString={$_GET["SearchString"]}");
	$filename="/usr/share/artica-postfix/ressources/logs/access.log.tmp";
	$dataZ=explode("\n",@file_get_contents($filename));
	$tpl=new templates();
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($data);
	$data['rows'] = array();
	$today=date("Y-m-d");
	$tcp=new IP();
	
	$cachedT=$tpl->_ENGINE_parse_body("{cached}");
	$unknown=$tpl->javascript_parse_text("{unknown}");
	$c=0;
	
	if(count($dataZ)==0){json_error_show("no data");}
	$zcat=new squid_familysite();
	$logfileD=new logfile_daemon();
	krsort($dataZ);
	$IP=new IP();
	while (list ($num, $line) = each ($dataZ)){
		$TR=preg_split("/[\s]+/", $line);
		if(count($TR)<5){continue;}
		$c++;
		$color="black";
		$date=date("Y-m-d H:i:s",$TR[0]);
		$durationunit="s";
		$duration=$TR[1]/1000;
		if($duration<60){$duration=round($duration,2);}
		if($duration>60){$duration=round($duration/60,2);$durationunit="mn";}
		$ip=$TR[2];
		$zCode=explode("/",$TR[3]);
		$size=$TR[4];
		$PROTO=$TR[5];
		if($logfileD->CACHEDORNOT($zCode[0])){$color="#009223";}
		$codeToString=$logfileD->codeToString($zCode[1]);
		$port=null;
		$infos=null;
		$prefix=null;
		$query=null;
		$scheme=null;
		if($PROTO=="CONNECT"){$color="#BAB700";$PROTO="SSL";
		$scheme="https";}
		if($zCode[1]>399){$color="#D0080A";}
		if($zCode[1]==307){$color="#F59C44";}
		
		
		if(preg_match("#TCP_REDIRECT#", $zCode[0])){$color="#A01E1E";}
		if(preg_match("#TCP_DENIED#", $zCode[0])){$color="#D0080A";}
		
		
		
		$URL=$TR[6];
		$SOURCE_URL=$URL;
		
		$fontsize=14;
		if($_GET["minsize"]==1){
			$fontsize=12;
		}
		
		$user="{$TR[7]}";
		if($user=="-"){$user=null;}
		if($user<>null){$user="/<strong>$user</strong>";}
		
		if(!isset($parse["scheme"])){
			if($PROTO=="SSL"){
				$GET_URL="https://$SOURCE_URL";
			}
		}else{
			$GET_URL=$SOURCE_URL;
		}
				
		
		
		$parse=parse_url($URL);
		if($scheme==null){$scheme=$parse["scheme"];}
		
		
		$hostname=$parse["host"];
		if(preg_match("#(.+?):([0-9]+)#", $hostname,$re)){
			$hostname=$re[1];
			$port=$re[2];
		}
		if($IP->isValid($hostname)){
			$parse["query"]=null;
			$parse["path"]=null;
			
			$TT=explode(".",$hostname);
			$net=$TT[0].".".$TT[1].".".$TT[2];
			$infos="&nbsp;(<a href=\"http://www.tcpiputils.com/browse/ip-address/$hostname\" style='text-decoration:underline;color:black' target=_new>TCP Utils</a>&nbsp;|&nbsp<a href=\"https://db-ip.com/all/$net\" style='text-decoration:underline;color:black' target=_new>Subnet</a>)";
		}
		
		
		$path=$parse["path"];
		$query=$parse["query"];
		$familysite=$zcat->GetFamilySites($hostname);
		$familysite=str_replace("'", "`", $familysite);
		$familysiteEnc=urlencode($familysite);
		if($familysite<>$hostname){
			$prefix=str_replace(".$familysite", "", $hostname);
			if($prefix<>"www"){
				
				$prefix="<a href=\"javascript:blur();\"
				OnClick=\"javascript:Loadjs('squid.access.webfilter.tasks.php?familysite=$hostname')\"
				style='text-decoration:underline;font-size:{$fontsize}px;color:$color;font-weight:bold'>$prefix</a>";
			}
		}
		
		
		$familysite="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('squid.access.webfilter.tasks.php?familysite=$familysiteEnc')\"
		style='text-decoration:underline;font-size:{$fontsize}px;color:$color'>$familysite</a>";
		
		$URL="$scheme://";
		if($prefix<>null){$URL=$URL."$prefix.";}
		$URL=$URL."$familysite";
		if($port<>null){$URL=$URL.":$port";}
		if(!isset($_GET["categories-scan"])){
			if($path<>null){$URL=$URL.$path;}
			if($query<>null){$URL=$URL."?$query";}
		}else{
			$category=$catz->GET_CATEGORIES($hostname);
			if($category==null){$category=" ($unknown)";}else{$category=" ($category)";}
			$URL=$URL.$category;
		}
		$TR[6]=$URL;
		
		$link="<a href=\"$GET_URL\" target=_new><img src='img/icon-link.png'></a>";
		
		
		if(isset($_GET["fqdn"])){
			if($IPClass->isIPAddress($ip)){
				if(!isset($RESOLV[$ip])){$RESOLV[$ip]=gethostbyaddr($ip);}
				$ip=$RESOLV[$ip];
			}
			
		}
		
		
		
		if($size>1024){$size=FormatBytes($size/1024);}else{$size="$size Bytes";}
		$date=str_replace($today." ", "", $date);
		$data['rows'][] = array(
				'id' => md5($line),
				'cell' => array(
						"<span style='font-size:{$fontsize}px;color:$color'>$date</span>",
						"<span style='font-size:{$fontsize}px;color:$color'>$ip$user</span>",
						"<span style='font-size:{$fontsize}px;color:$color'>{$zCode[0]} - $codeToString</span>",
						"<span style='font-size:{$fontsize}px;color:$color'>{$PROTO}</span>",
						"<span style='font-size:{$fontsize}px;color:$color'>{$TR[6]}$infos</span>",
						"<center style='font-size:{$fontsize}px;color:$color'>$link</center>",
						"<span style='font-size:{$fontsize}px;color:$color'>$size</span>",
						"<span style='font-size:{$fontsize}px;color:$color'>{$duration}$durationunit</span>",
						"$ip"
				)
		);
		
	}
	
	
	$data['total'] = $c;
	echo json_encode($data);
	
}
