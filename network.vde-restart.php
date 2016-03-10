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
js();

function js(){
	$t=$_GET["t"];
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{save_network_settings}");
	$please_wait_restarting_network=$tpl->javascript_parse_text("{please_wait_restarting_network}");
	@unlink(dirname(__FILE__)."/ressources/logs/web/vde.status.html");
	$compile_ask=$tpl->javascript_parse_text("{apply_network_configuration_warn}");
	
	
	
	if(!is_numeric($t)){$t=time();}
	$warn="if(!confirm('$compile_ask')){return;}";
	
	
	$page=CurrentPageName();
	$html="
	
	function StartLoadjs$t(){
		
			YahooWin3('998','$page?popup=yes&t=$t','$title');
		
		}
		
	function GetLogs$t(){
		Loadjs('$page?logs=yes&t=$t&setTimeout={$_GET["setTimeout"]}');
		if(document.getElementById('IMAGE_STATUS_INFO-$t')){
			Loadjs('admin.tabs.php?refresh-status-js=yes&nocache=yes');
		}
	}
	
	function Procedure2$t(){
		LoadAjax('procedure2-$t','$page?procedure2=yes&t=$t');
	}
	
	function Procedure3Error$t(){
		document.getElementById('procedure3-text$t').value=document.getElementById('procedure3-text$t').value+'\\n'+'Please wait...';
		setTimeout(\"Procedure3$t()\",1000);
	}
	
	function Procedure3$t(){
		document.getElementById('title-$t').innerHTML='$please_wait_restarting_network';
		LoadAjax('procedure3-$t','$page?procedure3=yes&t=$t','Procedure3Error$t()');
	}
	
	function finish$t(){
		if( document.getElementById('title-$t') ){
			document.getElementById('title-$t').innerHTML='';
		}
		if(document.getElementById('table-$t')){
			$('#table-$t').flexReload();
		}
		
		if(document.getElementById('tabs_listnics2')){
			RefreshTab('tabs_listnics2');
		}
	
	}
		
	StartLoadjs$t();";
	
	echo $html;
}


function popup(){
	$page=CurrentPageName();
	$t=$_GET["t"];
	$title="{PLEASE_WAIT_COMPILING_NETWORK_SCRIPT}";
	$html="
	<center style='font-size:16px;margin:10px'><div id='title-$t'>$title</div></center>
	<div style='margin:5px;padding:3px;border:1px solid #CCCCCC;width:97%;height:450px;overflow:auto' id='functi-restart-$t'>
	</div>
	
	<script>
		LoadAjax('functi-restart-$t','$page?execute=yes&t=$t');
	</script>
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}

function execute(){
	$page=CurrentPageName();
	$t=$_GET["t"];	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?virtuals-ip-reconfigure=yes");
	
	
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


$sock=new sockets();
$sock->getFrameWork("network.php?vde-restart=yes");
	
}

function procedure3(){
	$page=CurrentPageName();
	$t=$_GET["t"];
	$file=$GLOBALS["LOGFILE"];
	if(!is_file($file)){echo "<script>setTimeout(\"Procedure3Error$t()\",1000);</script>";return;}
	$data=@file_get_contents($file);
	if(strlen($data)<10){echo "<script>setTimeout(\"Procedure3Error$t()\",1000);</script>";return;}	
	
	$data2=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/vde.status.html");
	
	echo "
	<div id='procedure3-$t'>
	<center id='animate-$t'></center>
	<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:98%;height:446px;border:5px solid #8E8E8E;
	overflow:auto;font-size:11px' id='procedure3-text$t'>$data2\n$data\n</textarea>
	</div>
	<script>
		finish$t();
	</script>
	";	
}


function Filllogs(){
	$datas=explode("\n",@file_get_contents("ressources/logs/web/restart.squid"));
	krsort($datas);
	echo @implode("\n", $datas);
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




?>