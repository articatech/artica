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
	$GLOBALS["LOGFILE"]="/usr/share/artica-postfix/ressources/logs/web/exec.virtuals-ip.php.html";
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	

	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["execute"])){execute();exit;}
	if(isset($_GET["logs"])){logs();exit;}
	if(isset($_POST["Filllogs"])){Filllogs();exit;}
	if(isset($_GET["procedure2"])){procedure2();exit;}
	if(isset($_GET["procedure3"])){procedure3();exit;}
	if(isset($_GET["ApplyNetWorkFinal"])){ApplyNetWorkFinal();exit;}
	if(isset($_GET["ApplyNetWorkFinal-tests"])){ApplyNetWorkFinal_tests();exit;}
js();

function js(){
	$t=$_GET["t"];
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{save_network_settings}");
	$please_wait_building_network=$tpl->javascript_parse_text("{please_wait_building_network}");
	$please_wait_restarting_network=$tpl->javascript_parse_text("{please_wait_restarting_network}");
	$success=$tpl->javascript_parse_text("{save_network_settings} - {done} -");
	
	
	@unlink(dirname(__FILE__)."/ressources/logs/web/squid.status.html");
	$compile_ask=$tpl->javascript_parse_text("{apply_network_configuration_warn}");
	
	if(isset($_GET["OnlyRoutes"])){$OnlyRoutes="&OnlyRoutes=yes";}
	
	if(!is_numeric($t)){$t=time();}
	$warn="if(!confirm('$compile_ask')){return;}";
	
	
	$page=CurrentPageName();
	$html="
	var TIMER$t=0;
	function StartLoadjs$t(){
		
			YahooWin3('998','$page?popup=yes&t=$t$OnlyRoutes','$title');
		
		}
		
	function GetLogs$t(){
		Loadjs('$page?logs=yes&t=$t&setTimeout={$_GET["setTimeout"]}');
		if(document.getElementById('IMAGE_STATUS_INFO-$t')){
			Loadjs('admin.tabs.php?refresh-status-js=yes&nocache=yes');
		}
	}
	
	function Procedure2$t(){
		LoadAjax('procedure2-$t','$page?procedure2=yes&t=$t$OnlyRoutes');
	}
	
	function Procedure3Error$t(){
		document.getElementById('procedure3-text$t').value=document.getElementById('procedure3-text$t').value+'\\n'+'Please wait...';
		setTimeout(\"Procedure3$t()\",1000);
	}
	
	function Procedure3$t(){
		document.getElementById('title-$t').innerHTML='$please_wait_building_network';
		LoadAjax('procedure3-$t','$page?procedure3=yes&t=$t$OnlyRoutes','Procedure3Error$t()');
	}
	
	function finish$t(){
		if(document.getElementById('table-$t')){
			$('#table-$t').flexReload();
		}
		
		if(document.getElementById('tabs_listnics2')){
			RefreshTab('tabs_listnics2');
		}
		
		
		if(document.getElementById('SYSTEM_NICS_VIRTUALS_LIST')){
			$('#SYSTEM_NICS_VIRTUALS_LIST').flexReload();
		}
		
		if(document.getElementById('nics-infos-system')){
			LoadAjaxRound('nics-infos-system','admin.dashboard.system.php?nics-infos=yes');
		}
		
		document.getElementById('title-$t').innerHTML='$success';
	
	}
	
	function ApplyNetworkFinalShow1$t(){
		LoadAjax('ApplyNetWorkFinal-$t','$page?ApplyNetWorkFinal-tests=yes&t=$t$OnlyRoutes');
		
	}
	
	
	function ApplyNetworkFinalShow$t(){
		if(TIMER$t==0){
			document.getElementById('title-$t').innerHTML='$please_wait_restarting_network';
			setTimeout(\"ApplyNetworkFinalShow1$t()\",5000);
		}
	
	}
		
	StartLoadjs$t();";
	
	echo $html;
}


function popup(){
	$page=CurrentPageName();
	$t=$_GET["t"];
	$tpl=new templates();
	if(isset($_GET["OnlyRoutes"])){$OnlyRoutes="&OnlyRoutes=yes";}
	$title=$tpl->_ENGINE_parse_body("{PLEASE_WAIT_COMPILING_NETWORK_SCRIPT}");
	$html="
	<center style='font-size:16px;margin:10px'><div id='title-$t' style='font-size:30px'>$title</div></center>
	<div style='margin:5px;padding:3px;border:1px solid #CCCCCC;width:97%;' id='functi-restart-$t'>
	</div>
	
	<script>
		LoadAjax('functi-restart-$t','$page?execute=yes&t=$t$OnlyRoutes');
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function execute(){
	$page=CurrentPageName();
	$t=$_GET["t"];	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?virtuals-ip-reconfigure=yes&stay=yes");
	
	
	echo "
	<div id='procedure2-$t'>
		<center id='animate-$t'><img src=\"img/wait_verybig.gif\"></center>
	</div>
	<script>
			setTimeout(\"Procedure2$t()\",1000);
	</script>";
	
	
}

function procedure2(){
	$page=CurrentPageName();
	$t=$_GET["t"];
	$file=$GLOBALS["LOGFILE"];
	if(!is_file($file)){echo "<script>setTimeout(\"Procedure2$t()\",1000);</script>";return;}
	$data=@file_get_contents($file);
	if(strlen($data)<10){echo "<script>setTimeout(\"Procedure2$t()\",1000);</script>";return;}
	$data=str_replace('�','',$data);
echo "
	<div id='procedure3-$t'>
	<center id='animate-$t'>
				<img src=\"img/wait_verybig.gif\">
	</center>
	<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:98%;height:446px;border:5px solid #8E8E8E;
	overflow:auto;font-size:11px' id='procedure3-text$t'>$data</textarea>
	</div>
	<script>
			setTimeout(\"Procedure3$t()\",1000);
	</script>";
}

function procedure3(){
	$page=CurrentPageName();
	$t=$_GET["t"];
	$file=$GLOBALS["LOGFILE"];
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{network_script_builded}");
	$button=$tpl->_ENGINE_parse_body("{restart_network}");
	
	if(isset($_GET["OnlyRoutes"])){
		$button=$tpl->_ENGINE_parse_body("{build_routes}");
		$OnlyRoutes="&OnlyRoutes=yes";
	}
	$sock=new sockets();
	$datas=base64_decode($sock->getFrameWork("network.php?artica-ifup-content=yes"));
	
	$html= "
	<div id='procedure4-$t'>
	<div class=explain style='font-size:18px;margin-bottom:20px'>{init_script_net_explain}</div>
	<center><hr style='border:1px solid'>". button($button,"ApplyNetWorkFinal$t()",22)."</center>
	
		<textarea style='margin-top:5px;font-family:Courier New;
		font-weight:bold;width:98%;height:446px;border:5px solid #8E8E8E;
		overflow:auto;font-size:11px' id='procedure3-text$t'>$datas</textarea>
	</div>
	
	
	<script>
		if(document.getElementById('title-$t')){
			document.getElementById('title-$t').innerHTML='$title';
		}
	
	function ApplyNetWorkFinal$t(){
		LoadAjax('procedure4-$t','$page?ApplyNetWorkFinal=yes&t=$t$OnlyRoutes');
	}
		
		
	</script>
	";	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}


function Filllogs(){
	$datas=explode("\n",@file_get_contents("ressources/logs/web/restart.squid"));
	krsort($datas);
	$data=@implode("\n", $datas);
	$data=str_replace('�','',$data);
	echo $data;
}

function logs(){
	$page=CurrentPageName();
	$t=$_GET["t"];
	$tt=time();
	$datas=@file_get_contents("ressources/logs/web/restart.squid");
	if(strlen($datas)<10){
		echo "Loadjs('$page?logs=yes&t=$t');";
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

function ApplyNetWorkFinal(){
	$t=$_GET["t"];
	
	echo "
	<div id='ApplyNetWorkFinal-$t'></div>		
	<script>ApplyNetworkFinalShow$t()</script>";
	
	
	$sock=new sockets();
	
	if(isset($_GET["OnlyRoutes"])){
		$sock->getFrameWork("services.php?apply-routes=yes");
		return;
		
	}
	
	
	
	$sock->getFrameWork("services.php?restart-network=yes");
	
}
function ApplyNetWorkFinal_tests(){
	$t=$_GET["t"];
	$datas=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/exec.virtuals-ip.php.html");
	if(strlen($datas)<50){return;}
	$sock=new sockets();
	$data2=base64_decode($sock->getFrameWork("services.php?netstart-log=yes"));
	$datas=$datas."\n".$data2;
echo "
<textarea style='margin-top:5px;font-family:Courier New; font-weight:bold;width:98%;height:446px;border:5px solid #8E8E8E; overflow:auto;font-size:11px' id='procedure3-text$t'>$datas</textarea>
<script>
	TIMER$t=1;
	finish$t();
</script>";
	
}

?>