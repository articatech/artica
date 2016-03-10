<?php

	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.system.nics.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once('ressources/class.tcpip.inc');
	$usersmenus=new usersMenus();
	if($usersmenus->AsSystemAdministrator==false){exit();}
	
	if(isset($_GET["params"])){params();exit;}
	if(isset($_POST["Enablentopng"])){save();exit;}
	if(isset($_GET["status"])){status();exit;}
page();	
	
function page(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$EnableIntelCeleron=intval($sock->GET_INFO("EnableIntelCeleron"));

	if($EnableIntelCeleron==1){
		echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{ERROR_FEATURE_CELERON}"));
		return;
		
		
	}
	
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	if($SquidPerformance>2){
		echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{software_is_disabled_performance}"));
		return;
		
	}
	$NTOPNgSize=intval($sock->GET_INFO("NTOPNgSize")*1024);
	if($NTOPNgSize>0){
		$NTOPNgSizeText="(".FormatBytes($NTOPNgSize).")";
	}
	
	$html="
	<div style='font-size:30px;margin-bottom:30px'>{APP_NTOPNG} $NTOPNgSizeText</div>
	<div style='width:98%' class=form>		
	<table style='width:100%'>
		<tr>
			<td style='width:350px;vertical-align:top'><div id='APP_NTOPNG_STATUS'></div></td>
			<td style='width:560px'><div id='APP_NTOPNG_PARAMS'></div></td>
		</tr>
	</table>	
	</div>
	<script>
		LoadAjax('APP_NTOPNG_PARAMS','$page?params=yes',false);
	</script>		
			
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function params(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$arrayConf=unserialize(base64_decode($sock->GET_INFO("ntopng")));
	$EnableBandwidthd=intval($sock->GET_INFO("EnableBandwidthd"));
	$BandwidthdInstalled=intval($sock->GET_INFO("BandwidthdInstalled"));
	
	$Enablentopng=$sock->GET_INFO("Enablentopng");
	if(!is_numeric($Enablentopng)){$Enablentopng=0;}
	if(!is_numeric($arrayConf["HTTP_PORT"])){$arrayConf["HTTP_PORT"]=3000;}
	if(!is_numeric($arrayConf["ENABLE_LOGIN"])){$arrayConf["ENABLE_LOGIN"]=0;}
	if(!is_numeric($arrayConf["MAX_DAYS"])){$arrayConf["MAX_DAYS"]=30;}
	if(!is_numeric($arrayConf["MAX_SIZE"])){$arrayConf["MAX_SIZE"]=5000;}
	
	
	
	$DisableNetDiscover=intval($sock->GET_INFO("DisableNetDiscover"));	
	if($DisableNetDiscover==0){$DisableNetDiscover=1;}else{$DisableNetDiscover=0;}
	$NetDiscoverSaved=$sock->GET_INFO("NetDiscoverSaved");
	if(!is_numeric($NetDiscoverSaved)){$DisableNetDiscover=1;}
	
	
	
	$days[1]="1 {day}";
	$days[2]="2 {days}";
	$days[5]="5 {days}";
	$days[10]="10 {days}";
	$days[15]="15 {days}";
	$days[30]="1 {month}";
	
	$maxsizes[500]="500MB";
	$maxsizes[1000]="1GB";
	$maxsizes[5000]="5GB";
	$maxsizes[10000]="10GB";
	$maxsizes[50000]="50GB";
	
	if($Enablentopng==1){
		$button="<div style='margin-top:20px;text-align:right;width:100%'>".button("{disable_service}","Loadjs('system.ntopng.disable.php')",28)."</div>";
		
	}
	
	$enable=Paragraphe_switch_img("{enable_ntopng}", "{enable_ntopng_text}","Enablentopng",
			$Enablentopng,null,850);
	
	
	$EnableBandwidthd_par=Paragraphe_switch_img("{EnableBandwidthd}", "{EnableBandwidthd_explain}","EnableBandwidthd",
			$EnableBandwidthd,null,850);
	
	
	if($BandwidthdInstalled==0){
		$EnableBandwidthd_par=Paragraphe_switch_disable("{EnableBandwidthd}", "{EnableBandwidthd_explain}","EnableBandwidthd",
				0,null,850);
	}
	
	
	$DisableBWMng=intval($sock->GET_INFO("DisableBWMng"));
	
	$INSTALLED=$sock->getFrameWork("system.php?ntopng-installed=yes");
	if($INSTALLED<>"TRUE"){
		$error=$tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{software_is_not_installed}"));
		$enable=null;

	}
	$xDisableBWMng=1;
	if($DisableBWMng==1){$xDisableBWMng=0;}
	
	$html="$error
	$EnableBandwidthd_par
	$enable 
	<div style='margin-top:5px;width:100%;text-align:right'>$button</div>		
	<br>".
	Paragraphe_switch_img("{enable_login}", "{enable_login_ntopng_explain}",
			"ENABLE_LOGIN",$arrayConf["ENABLE_LOGIN"],null,850).
	"<table style='width:100%'>
		<tr>
			<td valign='middle' class=legend style='font-size:22px'>{listen_port}:</td>
			<td>". Field_text("HTTP_PORT",$arrayConf["HTTP_PORT"],"font-size:22px;width:110px")."</td>
		</tr>
		<tr>
			<td valign='middle' class=legend style='font-size:22px'>{webconsole}:</td>
			<td><a href=\"http://{$_SERVER["SERVER_ADDR"]}:{$arrayConf["HTTP_PORT"]}/\" 
			style='font-size:22px;text-decoration:underline'
			target=_new>http://{$_SERVER["SERVER_ADDR"]}:{$arrayConf["HTTP_PORT"]}</a></td>
		</tr>
		".Field_list_table("MAX_SIZE", "{max_size}", $arrayConf["MAX_SIZE"],22,$maxsizes)."				
		<tr>
			<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",42)."</td>
		</tr>
	</table>
<script>
var xSave$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	RefreshTab('tabs_networktrack');
}
	
	function Save$t(){
		var XHR = new XHRConnection();
		if(document.getElementById('Enablentopng')){XHR.appendData('Enablentopng',document.getElementById('Enablentopng').value);}
		if(document.getElementById('Enablentopng')){XHR.appendData('EnableBandwidthd',document.getElementById('EnableBandwidthd').value);}
		
		
		XHR.appendData('ENABLE_LOGIN',document.getElementById('ENABLE_LOGIN').value);
		XHR.appendData('MAX_SIZE',document.getElementById('MAX_SIZE').value);
		XHR.appendData('HTTP_PORT',document.getElementById('HTTP_PORT').value);
		XHR.sendAndLoad('$page', 'POST',xSave$t);	
	
	}	

LoadAjax('APP_NTOPNG_STATUS','$page?status=yes',false);
</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}


function save(){
	$sock=new sockets();
	if(isset($_POST["Enablentopng"])){
		$sock->SET_INFO("Enablentopng", $_POST["Enablentopng"]);
		$sock->SaveConfigFile(base64_encode(serialize($_POST)), "ntopng");
		$sock->getFrameWork("system.php?ntopng-restart=yes");
	}
	
	if(isset($_POST["EnableBandwidthd"])){
		$sock->SET_INFO("EnableBandwidthd", $_POST["EnableBandwidthd"]);
	}
	

	
}
function status(){
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$ini->loadString(base64_decode($sock->getFrameWork("system.php?ntopng-status=yes")));
	$page=CurrentPageName();
	$tpl=new templates();
	
	$serv[]=DAEMON_STATUS_ROUND("APP_BANDWIDTHD",$ini,null,0);
	$serv[]=DAEMON_STATUS_ROUND("APP_NTOPNG",$ini,null,0);
	$serv[]=DAEMON_STATUS_ROUND("APP_REDIS_SERVER",$ini,null,0);
	$serv[]="<div style='text-align:right;margin-top:20px'>".imgtootltip("refresh-32.png","{refresh}","LoadAjax('APP_NTOPNG_STATUS','$page?status=yes',true);")."</div>";
	echo $tpl->_ENGINE_parse_body(@implode("<br>", $serv));
	
}