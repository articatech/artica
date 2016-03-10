<?php
include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.clamav.inc');
	include_once('ressources/class.artica.graphs.inc');
	include_once('ressources/class.mysql.inc');

	$user=new usersMenus();
	if($user->AsSystemAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["popup"])){scan_engine_settings();exit;}
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["ClamavStreamMaxLength"])){save();exit;}
	if(isset($_GET["clamd-graphs"])){clamd_graphs();exit;}
	if(isset($_GET["graph1"])){clamd_graphs1();exit;}
	if(isset($_GET["graph2"])){clamd_graphs2();exit;}
	if(isset($_GET["graph3"])){clamd_graphs3();exit;}
	if(isset($_GET["graph4"])){clamd_graphs4();exit;}
	
js();


function status(){
	$tpl=new templates();
	$page=CurrentPageName();
	$script=null;
	if(is_file("ressources/logs/global.status.ini")){
		
		$ini=new Bs_IniHandler("ressources/logs/global.status.ini");
	}else{
		writelogs("ressources/logs/global.status.ini no such file");
		$sock=new sockets();
		$datas=base64_decode($sock->getFrameWork('cmd.php?Global-Applications-Status=yes'));
		$ini=new Bs_IniHandler($datas);
	}
	
	$sock=new sockets();
	$datas=$sock->getFrameWork('cmd.php?refresh-status=yes');	
	$status=DAEMON_STATUS_ROUND("CLAMAV",$ini,null,1);
	
	$q=new mysql();
	if($q->TABLE_EXISTS("clamd_mem","artica_events")){
		if($q->COUNT_ROWS("clamd_mem", "artica_events")>1){
			$script="LoadAjax('clamd-graphs','$page?clamd-graphs=yes');";
		}
	}
	
	$html="
	<div style='width:100%'>$status</div>
	<center style='margin-top:10px' id='clamd-graphs'></center>
	
	<script>
		$script
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}


function js(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{APP_CLAMAV}");
	
	echo "YahooWin3('650','$page?tabs=yes','$title');";
	
}

function clamd_graphs(){
	$page=CurrentPageName();
	$ff=time();
	$html="
	<div id='graph1-$ff' style='width:99%;height:450px'></div>
	<div id='graph2-$ff' style='width:99%;height:450px'></div>
	<div id='graph3-$ff' style='width:99%;height:450px'></div>
	<div id='graph4-$ff' style='width:99%;height:450px'></div>
	</tr>
	</table>
	
	<script>
	AnimateDiv('graph-$ff');
	AnimateDiv('graph2-$ff');
	AnimateDiv('graph3-$ff');
	AnimateDiv('graph4-$ff');
	Loadjs('$page?graph1=yes&container=graph1-$ff');
	Loadjs('$page?graph2=yes&container=graph2-$ff');
	Loadjs('$page?graph3=yes&container=graph3-$ff');
	Loadjs('$page?graph4=yes&container=graph4-$ff');
	
	</script>";
	
	echo $html;
	
}

function clamd_graphs2(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql();
	$sql="SELECT AVG(rss) as rss,AVG(vm) as vm,MINUTE(zDate) as tdate,DATE_FORMAT(zDate,'%Y-%m-%d %H:%i') as tdar FROM clamd_mem WHERE zDate<DATE_FORMAT(NOW(),'%Y-%m-%d %H:%i') GROUP BY tdar";
	$results=$q->QUERY_SQL($sql,"artica_events");



	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$xdata[]=$ligne["tdate"];
		$xdata2[]=$ligne["tdate"];
		$ydata[]=$ligne["rss"];
		$ydata2[]=$ligne["vm"];
	}


	$title="{memory} VM";
	$timetext="{minutes}";
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="{minutes}";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=date("H")."h";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("{VM}"=>$ydata2);
	echo $highcharts->BuildChart();
	return;
}


function clamd_graphs1(){
	$tpl=new templates();	
	$page=CurrentPageName();
	$q=new mysql();	
	$sql="SELECT AVG(rss) as rss,AVG(vm) as vm,MINUTE(zDate) as tdate,DATE_FORMAT(zDate,'%Y-%m-%d %H:%i') as tdar FROM clamd_mem WHERE zDate<DATE_FORMAT(NOW(),'%Y-%m-%d %H:%i') GROUP BY tdar";
	$results=$q->QUERY_SQL($sql,"artica_events");
	
	

	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$xdata[]=$ligne["tdate"];
		$xdata2[]=$ligne["tdate"];
		$ydata[]=$ligne["rss"];
		$ydata2[]=$ligne["vm"];
	}
	
	
	$title="{memory} RSS";
	$timetext="{minutes}";
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="{minutes}";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=date("H")."h";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("{rss}"=>$ydata);
	echo $highcharts->BuildChart();
}

function clamd_graphs3(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql();
	

$sql="SELECT AVG(rss) as rss,AVG(vm) as vm,HOUR(zDate) as tdate,DATE_FORMAT(zDate,'%Y-%m-%d %H') as tdar FROM clamd_mem WHERE
	zDate=DATE_FORMAT(NOW(),'%Y-%m-%d') GROUP BY tdar";
$results=$q->QUERY_SQL($sql,"artica_events");

$xdata=array();
$xdata2=array();
$ydata=array();
$ydata2=array();

if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}
while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
	$xdata[]=$ligne["tdate"];
	$xdata2[]=$ligne["tdate"];
	$ydata[]=$ligne["rss"];
	$ydata2[]=$ligne["vm"];
}


$title="{memory} RSS";
$timetext="{hours}";
$highcharts=new highcharts();
$highcharts->container=$_GET["container"];
$highcharts->xAxis=$xdata;
$highcharts->Title=$title;
$highcharts->TitleFontSize="14px";
$highcharts->AxisFontsize="12px";
$highcharts->yAxisTtitle="{hours}";
$highcharts->xAxis_labels=false;
$highcharts->LegendPrefix=date("H")."h";
$highcharts->xAxisTtitle=$timetext;
$highcharts->datas=array("{rss}"=>$ydata);
echo $highcharts->BuildChart();

	
}

function clamd_graphs4(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql();


	$sql="SELECT AVG(rss) as rss,AVG(vm) as vm,HOUR(zDate) as tdate,DATE_FORMAT(zDate,'%Y-%m-%d %H') as tdar FROM clamd_mem WHERE
	zDate=DATE_FORMAT(NOW(),'%Y-%m-%d') GROUP BY tdar";
	$results=$q->QUERY_SQL($sql,"artica_events");

	$xdata=array();
	$xdata2=array();
	$ydata=array();
	$ydata2=array();

	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$xdata[]=$ligne["tdate"];
		$xdata2[]=$ligne["tdate"];
		$ydata[]=$ligne["rss"];
		$ydata2[]=$ligne["vm"];
	}


	$title="{memory} VM";
	$timetext="{hours}";
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="{hours}";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=date("H")."h";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("{vm}"=>$ydata);
	echo $highcharts->BuildChart();


}

function tabs(){
	$tpl=new templates();	
	$page=CurrentPageName();
	$GLOBALS["CLASS_SOCKETS"]=new sockets();
	$users=new usersMenus();
	
	$EnableClamavDaemon=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavDaemon");
	if(!is_numeric($EnableClamavDaemon)){$EnableClamavDaemon=0;}
	
	$EnableClamavDaemonForced=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavDaemonForced");
	if(!is_numeric($EnableClamavDaemonForced)){$EnableClamavDaemonForced=0;}
	if($EnableClamavDaemonForced==1){$EnableClamavDaemon=1;}
	$CicapEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapEnabled");
	$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
	if($SQUIDEnable==1){if($CicapEnabled==1){$EnableClamavDaemon=1;}}
	
	
	if($users->POSTFIX_INSTALLED){
		if($users->CLAMAV_MILTER_INSTALLED){
			$array["clammilt"]='{antivirus_for_messaging}';
		}
	}
	
	
	
	
	if($EnableClamavDaemon==1){
		$array["status"]='{status}';
	}
	$array["popup"]='{engine_parameters}';
	
	
	
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="clammilt"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"clamav.milter.php\"><span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}
		
		
		
		if($num=="clamav_unofficial"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"clamav.unofficial.php?popup=yes\"><span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span style='font-size:24px'>$ligne</span></a></li>\n");
	}
	
	
	echo build_artica_tabs($html, "main_config_clamav");
}



function scan_engine_settings(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$ClamavStreamMaxLength=$sock->GET_INFO("ClamavStreamMaxLength");
	$ClamavMaxRecursion=$sock->GET_INFO("ClamavMaxRecursion");
	$ClamavMaxFiles=$sock->GET_INFO("ClamavMaxFiles");
	$ClamavMaxFileSize=$sock->GET_INFO("ClamavMaxFileSize");
	$PhishingScanURLs=$sock->GET_INFO("PhishingScanURLs");
	$ClamavMaxScanSize=$sock->GET_INFO("ClamavMaxScanSize");
	$ClamavRefreshDaemonTime=intval($sock->GET_INFO("ClamavRefreshDaemonTime"));
	$ClamavRefreshDaemonMemory=intval($sock->GET_INFO("ClamavRefreshDaemonMemory"));

	
	if($ClamavStreamMaxLength==null){$ClamavStreamMaxLength="12";}
	if(!is_numeric($ClamavMaxRecursion)){$ClamavMaxRecursion="5";}
	if(!is_numeric($ClamavMaxFiles)){$ClamavMaxFiles="10000";}
	if(!is_numeric($PhishingScanURLs)){$PhishingScanURLs="1";}
	if(!is_numeric($ClamavMaxScanSize)){$ClamavMaxScanSize="15";}
	if(!is_numeric($ClamavMaxFileSize)){$ClamavMaxFileSize="20";}
	
	$hoursEX[0]="{never}";
	$hoursEX[15]="15 {minutes}";
	$hoursEX[30]="30 {minutes}";
	$hoursEX[60]="1 {hour}";
	$hoursEX[120]="2 {hours}";
	$hoursEX[180]="3 {hours}";
	$hoursEX[420]="4 {hours}";
	$hoursEX[480]="8 {hours}";	
	
	$html="
	
	
	
	<div id='ffmcc3' class=form style='width:95%'>
	<div style='text-align:right;font-size:18px;margin-top:22px'><a href=\"javascript:blur();\" 
				OnClick=\"javascript:GotoClamavUpdates();\" 
				style='font-size:22px;text-decoration:underline'>{also_see_update_databases}</div>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>".texttooltip("{srv_clamav.RefreshDaemon}","{srv_clamav.RefreshDaemon_text}").":</td>
		<td style=';font-size:22px'>" . Field_array_Hash($hoursEX,"ClamavRefreshDaemonTime",$ClamavRefreshDaemonTime,'style:font-size:22px;padding:3px')."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>".texttooltip("{refresh_daemon_MB}","{srv_clamav.ClamavRefreshDaemonMemory}").":</td>
		<td style=';font-size:22px'>" . Field_text("ClamavRefreshDaemonMemory",$ClamavRefreshDaemonMemory,'font-size:22px;padding:3px;width:110px')."&nbsp;MB</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:22px'>".texttooltip("{srv_clamav.StreamMaxLength}","{srv_clamav.StreamMaxLength_text}").":</td>
		<td style=';font-size:22px'>" . Field_text('ClamavStreamMaxLength',$ClamavStreamMaxLength,'width:110px;font-size:22px;padding:3px')."&nbsp;M</td>
		
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>".texttooltip("{srv_clamav.MaxObjectSize}","{srv_clamav.MaxObjectSize_text}").":</td>
		<td style=';font-size:22px'>" . Field_text('ClamavMaxFileSize',$ClamavMaxFileSize,'width:110px;font-size:22px;padding:3px')."&nbsp;M</td>
	</tr>
	
	<tr>
		<td class=legend style='font-size:22px'>".texttooltip("{srv_clamav.MaxScanSize}","{srv_clamav.MaxScanSize_text}").":</td>
		<td style=';font-size:22px'>" . Field_text('ClamavMaxScanSize',$ClamavMaxScanSize,'width:110px;font-size:22px;padding:3px')."&nbsp;M</td>
	</tr>	
	
	

	<tr>
		<td class=legend style='font-size:22px'>".texttooltip("{srv_clamav.ClamAvMaxFilesInArchive}","{srv_clamav.ClamAvMaxFilesInArchive}").":</td>
		<td style=';font-size:22px'>" . Field_text('ClamavMaxFiles',$ClamavMaxFiles,'width:150px;font-size:22px;padding:3px')."&nbsp;{files}</td>
		
	</tr>	
	
	<tr>
		<td class=legend style='font-size:22px'>".texttooltip("{srv_clamav.MaxFileSize}","{srv_clamav.ClamAvMaxFileSizeInArchive}").":</td>
		<td style=';font-size:22px'>" . Field_text('MaxFileSize',$ClamavMaxFileSize,
				'width:110px;font-size:22px;padding:3px')."&nbsp;M</td>
	</tr>

	<tr>
		<td class=legend style='font-size:22px'>".texttooltip("{srv_clamav.ClamAvMaxRecLevel}","{srv_clamav.ClamAvMaxRecLevel}").":</td>
		<td style=';font-size:22px'>" . Field_text('ClamavMaxRecursion',$ClamavMaxRecursion,'width:110px;font-size:22px;padding:3px')."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>".texttooltip("{srv_clamav.PhishingScanURLs}","{srv_clamav.PhishingScanURLs_text}").":</td>
		<td style=';font-size:22px'>" . Field_checkbox_design('PhishingScanURLs',1,$PhishingScanURLs)."</td>
	</tr>
	
	
	<tr>
		<td colspan=2 align='right'><hr>
		". button("{apply}","SaveClamdInfos()",26)."
			
		</td>
	</tr>
	</table>
	</div>
<script>

var X_SaveClamdInfos= function (obj) {
		var results=trim(obj.responseText);
		if(results.length>0){alert(results);}
		RefreshTab('main_config_clamav');
	}	

	function SaveClamdInfos(){
		var XHR=XHRParseElements('ffmcc3');
		XHR.sendAndLoad('$page', 'GET',X_SaveClamdInfos);
	
	}
	
</script>	
	
";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}


function save(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$sock->SET_INFO("ClamavStreamMaxLength",$_GET["ClamavStreamMaxLength"]);
	$sock->SET_INFO("ClamavMaxRecursion",$_GET["ClamavMaxRecursion"]);
	$sock->SET_INFO("ClamavMaxFiles",$_GET["ClamavMaxFiles"]);
	$sock->SET_INFO("ClamavMaxFileSize",$_GET["ClamavMaxFileSize"]);
	$sock->SET_INFO("PhishingScanURLs",$_GET["PhishingScanURLs"]);
	$sock->SET_INFO("ClamavMaxScanSize",$_GET["ClamavMaxScanSize"]);
	$sock->SET_INFO("ClamavRefreshDaemonTime",$_GET["ClamavRefreshDaemonTime"]);
	$sock->SET_INFO("ClamavRefreshDaemonMemory",$_GET["ClamavRefreshDaemonMemory"]);
	$sock->getFrameWork("cmd.php?clamd-reload=yes");
	$sock->getFrameWork("cmd.php?restart-artica-status=yes");		
	
	
	
}
