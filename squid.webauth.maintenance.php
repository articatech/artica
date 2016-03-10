<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.system.network.inc');

if(isset($_GET["popup"])){popup();exit;}




js();
function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$YahooWin=2;
	if(isset($_GET["YahooWin"])){$YahooWin=$_GET["YahooWin"];$YahooWinUri="&YahooWin={$_GET["YahooWin"]}";}
	$title=$tpl->_ENGINE_parse_body("{HotSpot} V3");
	$html="YahooWin('950','$page?popup=yes','$title')";
	echo $html;
}


function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$ArticaHotSpotEmergency=intval($sock->GET_INFO("ArticaHotSpotEmergency"));
	$ArticaHotSpotPort=$sock->GET_INFO("ArticaHotSpotPort");
	$ArticaSSLHotSpotPort=$sock->GET_INFO("ArticaSSLHotSpotPort");
	$ArticaSplashHotSpotPort=$sock->GET_INFO("ArticaSplashHotSpotPort");
	$ArticaSplashHotSpotPortSSL=$sock->GET_INFO("ArticaSplashHotSpotPortSSL");
	if(!is_numeric($ArticaHotSpotPort)){$ArticaHotSpotPort=0;}
	if(!is_numeric($ArticaSplashHotSpotPort)){$ArticaSplashHotSpotPort=16080;}
	if(!is_numeric($ArticaSplashHotSpotPortSSL)){$ArticaSplashHotSpotPortSSL=16443;}
	$HotSpotGatewayAddr=$sock->GET_INFO("HotSpotGatewayAddr");
	$HotSpotGatewayAddr_org=$HotSpotGatewayAddr;
	
	$HotSpotGatewayAddrZ=explode(".",$HotSpotGatewayAddr);
	$HotSpotGatewayAddrz[3]=rand(1, 254);
	$HotSpotGatewayAddr=@implode(".", $HotSpotGatewayAddrz);
	
	$t=time();
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	
	
	$emergency_bt="<center style='margin:30px'>". button("{global_urgency_mode}","Loadjs('squid.hostspot.emergency.enable.progress.php')",40)."</center>";
	

	if($ArticaHotSpotEmergency==1){
		$emergency_bt="<center style='margin:30px'>". button("{disable_emergency_mode}","Loadjs('squid.hostspot.emergency.disable.progress.php')",40)."</center>";
		$error=FATAL_ERROR_SHOW_128("{hotspot_in_emergency_mode_explain}");
	}
	
	
	
	
	$html="
	<inpuyt type='hidden' id='layer-hotspot-maintenance' value='1'>
	$error
	<div style='width:98%' class=form>
	<center style='margin:30px'>". button("{restart_web_service}","Loadjs('squid.hostspot.restart.web.progress.php')",40)."</center>
	$emergency_bt
	
			
			
	</div>
	<script>

	</script>				
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}