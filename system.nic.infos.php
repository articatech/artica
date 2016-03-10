<?php
if(isset($_GET["verbose"])){echo __LINE__." verbose OK<br>\n";$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.system.nics.inc');
	
//info=proc	
$usersmenus=new usersMenus();
if($usersmenus->AsSystemAdministrator==false){exit;}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["proc"])){popup_proc();exit;}
if(isset($_POST["ModeProbeAlx"])){ModeProbeAlx();exit;}
if(isset($_GET["eth-hour"])){eth_hour();exit;}
if(isset($_GET["eth-week"])){eth_week();exit;}

js();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$tile=$tpl->_ENGINE_parse_body('{network_hardware_infos}');
	$start="NicinfosStart()";
	
	if($_GET["info"]=='proc'){$start="ProcinfosStart()";}
	$html="
	
		function NicinfosStart(){
			YahooWin2('600','$page?popup=yes','$tile');
		}
		
		function ProcinfosStart(){
			YahooWin2('600','$page?proc=yes','$tile');
		}		
		
		$start;
	
	";
	
	echo $html;
}


function popup(){
	$infos=infos();
	$sock=new sockets();
	$page=CurrentPageName();
	$alx=$sock->getFrameWork("system.php?modinfo=alx");
	$ModeProbeAlx=intval($sock->GET_INFO("ModeProbeAlx"));
	$q=new mysql();
	$DIVS=array();
	$js=array();
	if($q->TABLE_EXISTS("RXTX_HOUR", "artica_events")){
		$results=$q->QUERY_SQL("SELECT ETH FROM RXTX_HOUR GROUP BY ETH","artica_events");
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$ETH=$ligne["ETH"];
			
			$nic=new system_nic($ETH);
			$DIVS[]="<hr><div style='font-size:30px;margin-top:20px'>$ETH [$nic->IPADDR] - $nic->NICNAME - $nic->netzone</div>";
			$DIVS[]="<div style='width:1460px;height:350px' id='$ETH-RX-hour' class=form></div>";
			$DIVS[]="<div style='width:1460px;height:350px' id='$ETH-TX-hour' class=form></div>";
			$DIVS[]="<div style='width:1460px;height:350px' id='$ETH-RX-week' class=form></div>";
			$DIVS[]="<div style='width:1460px;height:350px' id='$ETH-TX-week' class=form></div>";
			
			$js[]="Loadjs('$page?eth-hour=yes&type=RX&ETH=$ETH')";
			$js[]="Loadjs('$page?eth-hour=yes&type=TX&ETH=$ETH')";
			$js[]="Loadjs('$page?eth-week=yes&type=RX&ETH=$ETH')";
			$js[]="Loadjs('$page?eth-week=yes&type=TX&ETH=$ETH')";
		}
		
	}
	
	$t=time();
	if($alx=="TRUE"){
		
		$alxform=Paragraphe_switch_img("{qualcomm_atheros}", 
		"{qualcomm_atheros_explain}","ModeProbeAlx",$ModeProbeAlx,null,1450);
		
	}else{
		$alxform=Paragraphe_switch_disable("{qualcomm_atheros}", 
		"{qualcomm_atheros_explain}","ModeProbeAlx",0,null,1450);
	}
	
	//$gateway=Paragraphe('relayhost.png','{APP_ARTICA_GAYTEWAY}','{APP_ARTICA_GAYTEWAY_TEXT}',"javascript:Loadjs('index.gateway.php?script=yes')");
	
	
	
	$html="
	<div style='width:98%' class=form>
	$alxform		
	$gateway
	<div style='margin-top:20px;text-align:right'><hr>". button("{apply}", "Save$t()",32)."</div>
	</div>
	
	<div style='width:98%' class=form>
	<div style='font-size:26px;font-weight:bold'>{network_hardware_infos_text}</div>
	<br>
	<div style='width:98%;height:300px;overflow:auto;'>$infos</div>
	</div>
	".@implode("\n", $DIVS)."
<script>
var xSave$t=function (obj) {
	Loadjs('network.restart.php');
}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ModeProbeAlx',document.getElementById('ModeProbeAlx').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
".@implode("\n", $js)."
</script>	
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}

function ModeProbeAlx(){
	$sock=new sockets();
	$sock->SET_INFO("ModeProbeAlx", $_POST["ModeProbeAlx"]);
	
}

function popup_proc(){
	$infos=infos_proc();
	$html="<H1>{proc_hardware_infos_text}</H1>
	<br>
	<div style='width:100%;height:400px;overflow:auto'>$infos</div>
	
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
}

function infos(){
	
	if(!is_file("ressources/logs/LSHW.NET.HTML")){
		$error=@file_get_contents("ressources/logs/LSHW.ERROR.TXT");
		return "<H2 style='color:#d32d2d'>{could_not_open_infos} ($error)</H2>";}
	$datas=file_get_contents("ressources/logs/LSHW.NET.HTML");
	
	return transform_datas($datas);
	
}



function infos_proc(){
	
	if(!is_file("ressources/logs/LSHW.PROC.HTML")){return "<H2 style='color:#d32d2d'>{could_not_open_infos}</H2>";}
	$datas=file_get_contents("ressources/logs/LSHW.PROC.HTML");
	return transform_datas($datas);
	
}

function transform_datas($datas){
	if(preg_match("#<body>(.+?)</body>#is",$datas,$re)){$datas=$re[1];}
	$datas=str_replace("class=\"first\"","class=legend valign='top' style='font-size:16px'",$datas);
	$datas=str_replace("class=\"second\"","style='font-size:16px;font-weight:bold'",$datas);
	$datas=str_replace("class=\"node\"","style='margin-top:25px;border-top:2px solid #CCCCCC'",$datas);
	$datas=str_replace("class=\"node-disabled\"","style='margin-top:25px;border-top:2px solid #CCCCCC'",$datas);
	$datas=str_replace("class=\"sub-first\"","class=legend style='font-size:14px'",$datas);
	$datas=str_replace("class=\"id\""," style='font-size:16px' class=legend",$datas);
	$datas=str_replace(">width: <","nowrap>32/64 capabilities:<",$datas);
	$datas=str_replace("<td>","<td style='font-size:14px'>",$datas);
	$datas=str_replace("class=\"indented\"","style='width:98%'",$datas);
	return $datas;	
}
function eth_hour(){
	
	if(!class_exists("highcharts")){return ;}
	$tpl=new templates();
	$title="{$_GET["ETH"]} {NIC_{$_GET["type"]}} {today}";
	$timetext="{minutes}";
	
	$q=new mysql();
	$results=$q->QUERY_SQL("SELECT ZDATE,{$_GET["type"]} FROM RXTX_HOUR WHERE ETH='{$_GET["ETH"]}' ORDER BY ZDATE","artica_events");
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$SIZE=$ligne[$_GET["type"]];
		$SIZE=$SIZE/1024;
		$SIZE=round($SIZE/1024,2);
		$time=$ligne["ZDATE"];
		$xdata[]=$time;
		$ydata[]=$SIZE;
	}
	
	

	$highcharts=new highcharts();
	$highcharts->container="{$_GET["ETH"]}-{$_GET["type"]}-hour";
	$highcharts->xAxis=$xdata;
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->Title=$title;
	$highcharts->yAxisTtitle="{size} (MB)";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=null;
	$highcharts->LegendSuffix="MB";
	//$highcharts->subtitle="<a href=\"javascript:Loadjs('squid.sizegraphs.php')\" style='text-decoration:underline'>{more_details}</a>";
	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();
}

function eth_week(){
	if(!class_exists("highcharts")){return ;}
	$tpl=new templates();
	$title="{$_GET["ETH"]} {NIC_{$_GET["type"]}} {this_week}";
	$timetext="{week}";
	
	$q=new mysql();
	$results=$q->QUERY_SQL("SELECT ZDATE,{$_GET["type"]} FROM RXTX_WEEK WHERE ETH='{$_GET["ETH"]}' ORDER BY ZDATE","artica_events");
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$SIZE=$ligne[$_GET["type"]];
		$SIZE=$SIZE/1024;
		$SIZE=round($SIZE/1024,2);
		$time=$ligne["ZDATE"];
		$xdata[]=$time;
		$ydata[]=$SIZE;
	}
	
	
	
	$highcharts=new highcharts();
	$highcharts->container="{$_GET["ETH"]}-{$_GET["type"]}-week";
	$highcharts->xAxis=$xdata;
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->Title=$title;
	$highcharts->yAxisTtitle="{size} (MB)";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=null;
	$highcharts->LegendSuffix="MB";
	//$highcharts->subtitle="<a href=\"javascript:Loadjs('squid.sizegraphs.php')\" style='text-decoration:underline'>{more_details}</a>";
	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();
	}

?>