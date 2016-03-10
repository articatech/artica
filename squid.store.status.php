<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	if(isset($_GET["prepare-popup"])){cachesave_popup();exit;}
	if(isset($_GET["prepare-1"])){cachesave_1();exit;}
	if(isset($_GET["prepare-2"])){cachesave_2();exit;}
	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["start"])){start();exit;}
	if(isset($_GET["logs"])){logs();exit;}
	if(isset($_POST["Filllogs"])){Filllogs();exit;}
js();


function js(){
	$t=time();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{APP_SQUID}::{refresh_status}");
	@unlink(dirname(__FILE__)."/ressources/logs/web/squid_stores_status.html");

	
	$page=CurrentPageName();
	$html="
	
	function squid_restart_proxy_load$t(){
			YahooWin3('998','$page?popup=yes&t=$t&setTimeout={$_GET["setTimeout"]}','$title');
		
		}
		
	function GetLogs$t(){
		if(!YahooWin3Open()){return;}
		Loadjs('$page?logs=yes&t=$t&setTimeout={$_GET["setTimeout"]}');
		if(document.getElementById('IMAGE_STATUS_INFO')){
			Loadjs('admin.tabs.php?refresh-status-js=yes&nocache=yes');
		}
	}
		
	squid_restart_proxy_load$t();";
	
	echo $html;
}


function popup(){
	
	
	$page=CurrentPageName();
	$t=$_GET["t"];
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{APP_SQUID}::{check_caches}");
	$html="
	<center style='font-size:16px;margin:10px'><div id='title-$t'>$title</div></center>
	<div style='margin:5px;padding:3px;border:1px solid #CCCCCC;width:97%;height:450px;' id='squid-store-status'>
	</div>
	
	<script>
		LoadAjax('squid-store-status','$page?start=yes&t=$t');
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function start(){
	
	$sock=new sockets();
	$t=$_GET["t"];
	$users=new usersMenus();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}	
	$sock->getFrameWork("squid.php?force-cache-status=yes");
	
	echo "
	<center id='animate-$t'>
				<img src=\"img/wait_verybig.gif\">
	</center>
	<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:98%;height:446px;border:5px solid #8E8E8E;
	overflow:auto;font-size:11px' id='textToParseCats-$t'></textarea>
	<script>
			setTimeout(\"GetLogs$t()\",3000);
	</script>";
	
	
}

function Filllogs(){
	$datas=explode("\n",@file_get_contents("ressources/logs/web/status.squid"));
	krsort($datas);
	echo @implode("\n", $datas);
}

function logs(){
	$page=CurrentPageName();
	$t=$_GET["t"];
	$tt=time();
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/status.squid";
	$datas=@file_get_contents($cachefile);
	if(strlen($datas)<10){
		header("content-type: application/x-javascript");
		echo "
		if(document.getElementById('textToParseCats-$t')){
			document.getElementById('textToParseCats-$t').value='Please wait...". strlen($datas)." bytes';
			if(YahooWin3Open()){
				Loadjs('$page?logs=yes&t=$t');
			}
		}";
		return;
	}
	$strlenOrg=$_GET["strlen"];
	if(!is_numeric($strlenOrg)){$strlenOrg=0;}
	$strlen=strlen($datas);
	
	if(is_numeric($_GET["setTimeout"])){
		$setTimeout="setTimeout('DefHide()','{$_GET["setTimeout"]}');";
	}
	
	if($strlenOrg<>$strlen){
		echo "
				
			function Refresh$tt(){
				if(!YahooWin3Open()){return;}
				Loadjs('$page?logs=yes&t=$t&strlen=$strlen&setTimeout={$_GET["setTimeout"]}');
			
			}
		
		
			var x_Fill$tt= function (obj) {
				var res=obj.responseText;
				if (res.length>3){
					document.getElementById('textToParseCats-$t').value=res;
						if(document.getElementById('squid-services')){
							LoadAjax('squid-services','squid.main.quicklinks.php?squid-services=yes');
						}
					
					}
					
				
			}				
		
		
			function Fill$tt(){
				if(!YahooWin3Open()){return;}
				document.getElementById('title-$t').innerHTML='';
				document.getElementById('animate-$t').innerHTML='';
				var XHR = new XHRConnection();
		   	 	XHR.appendData('Filllogs', 'yes');
			    XHR.sendAndLoad('$page', 'POST',x_Fill$tt); 
				setTimeout(\"Refresh$tt()\",5000);
			}
				
			Fill$tt();	
		";
	}else{

		echo "function Refresh$tt(){
				if(!YahooWin3Open()){return;}
				Loadjs('$page?logs=yes&t=$t&strlen=$strlen&setTimeout={$_GET["setTimeout"]}');
			
			}
			
			function DefHide(){
				YahooWin3Hide();
			}
			
			setTimeout(\"Refresh$tt()\",3000);\n$setTimeout";
			
		
		
	}
}




?>