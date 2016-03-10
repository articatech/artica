<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.ini.inc');
	if(isset($_GET["verbose"])){
		ini_set('display_errors', 1);
		ini_set('error_reporting', E_ALL);
		ini_set('error_prepend_string',"<p class='text-error'>");
		ini_set('error_append_string',"</p>");
		$GLOBALS["VERBOSE"]=true;}
	
	$users=new usersMenus();
	if(!$users->AsAnAdministratorGeneric){header("content-type: application/x-javascript");echo "alert('No privileges');";die();}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["STATUSOF"])){STATUSOF();exit;}
	if(isset($_POST["LOGSOF"])){LOGSOF();exit;}
	if(isset($_GET["ifStopped"])){ifStopped();exit;}
	js();
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$appname=$_GET["APPNAME"];
	$action=$_GET["action"];
	$title=$tpl->javascript_parse_text("{{$appname}}::{{$action}}");
	$_GET["cmd"]=urlencode($_GET["cmd"]);
	$html="YahooWinBrowse('1036','$page?popup=yes&appname=$appname&action=$action&cmd={$_GET["cmd"]}&id={$_GET["id"]}&appcode={$_GET["appcode"]}','$title')";
	echo $html;
	
}	

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$appname=$_GET["appname"];
	$action=$_GET["action"];	
	$id=$_GET["id"];
	$t=time();
	$title=$tpl->_ENGINE_parse_body("{please_wait} {{$action}} {{$appname}}...");
	$_GET["cmd"]=urlencode($_GET["cmd"]);
	$sock=new sockets();
	$sock->getFrameWork("system.php?generic-start=yes&action={$_GET["action"]}&cmd={$_GET["cmd"]}&key=$t");
	$finish_text=$tpl->_ENGINE_parse_body("{{$action}} {{$appname}} {success}");
	$html="
		<center id='title-$t' style='font-size:18px'>$title</center><br>
		<center>
			<div id='Status$t' style='height:50px;margin-top:20px;margin-bottom:20px'></div>
		</center>
		<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:95%;height:520px;border:5px solid #8E8E8E;overflow:auto;font-size:11.5px'
	id='textarea$t'></textarea>
	<input type='hidden' id='stopall-$t' value='0'>
			
	<script>
		var timez$t=0;
		var idimg$t='$id';
		
		function step1$t() {
			if(!YahooWinBrowseOpen()){return;}
			timez$t=timez$t+1;
			
			var stopall=document.getElementById('stopall-$t').value;
			if(stopall==1){
				timez$t=110;
				$('#Status$t').progressbar({ value: 100 });
				Finish$t();
				return;
			}
			
			if(timez$t>100){
				document.getElementById('title-$t').innerHTML='';
				refreshidStatus$t();GetInfos$t();
				Finish$t();
				return;
			}
			if(timez$t==1){GetInfos$t();}
			if(timez$t==5){GetInfos$t();}
			if(timez$t==10){refreshidStatus$t();GetInfos$t();}
			if(timez$t==15){GetInfos$t();}
			if(timez$t==20){GetInfos$t();}
			if(timez$t==30){GetInfos$t();}
			if(timez$t==40){GetInfos$t();}
			if(timez$t==50){GetInfos$t();}			
			if(timez$t==60){refreshidStatus$t();}
			if(timez$t==70){GetInfos$t();}
			if(timez$t==80){GetInfos$t();}
			if(timez$t==90){GetInfos$t();}
			setTimeout(step2$t, 300);
		}
		
		function step2$t() {
			$('#Status$t').progressbar({ value: timez$t });
			step1$t();
		}
		
		function Finish$t(){
			document.getElementById('title-$t').innerHTML='$finish_text';
			if(document.getElementById('squid_main_svc')){RefreshTab('squid_main_svc');}
			if(document.getElementById('main_kav4proxy_config')){RefreshTab('main_kav4proxy_config');}
			if(document.getElementById('main_config_openssh')){RefreshTab('main_config_openssh');}
			if(document.getElementById('main_config_dnsmasqsub')){RefreshTab('main_config_dnsmasqsub');}
			if(document.getElementById('main_dansguardian_mainrules')){RefreshTab('main_dansguardian_mainrules');}
			if(document.getElementById('main_backup_fly')){RefreshTab('main_backup_fly');}
			if(document.getElementById('OPENDKIM_TABS')){RefreshTab('OPENDKIM_TABS');}
			if(document.getElementById('main_config_fetchmail')){RefreshTab('main_config_fetchmail');}
			if(document.getElementById('main_config_mgreylist')){RefreshTab('main_config_mgreylist');}
			if(document.getElementById('main_failover_tabs')){RefreshTab('main_failover_tabs');}
			if(document.getElementById('tabs_networktrack')){RefreshTab('tabs_networktrack');}
			if(document.getElementById('freshclam-status')){ LoadAjax('freshclam-status','clamav.updates.php?freshclam-status=yes'); }
			if(document.getElementById('health-monit-here')){ LoadAjaxRound('health-monit','system.monit.health.php'); }
			if(document.getElementById('admin_perso_tabs-ID')){
				var id=document.getElementById('admin_perso_tabs-ID').value;
				RefreshTab(id);
			}
			
			
			if( document.getElementById('sequence-proxy') ){
				LoadAjaxSilent('sequence-proxy','admin.dashboard.proxy.php?sequence-proxy=yes&nofw=yes&sequence=yes&ForceCache=yes');
				
			}
			
			
		}
		
	var X_refreshidStatus$t= function (obj) {
		var res=obj.responseText;
		if (res.length>3){
			if(document.getElementById('$id')){
				document.getElementById('$id').src='img/'+res;
			}
		}
		GetInfos$t();
	}		

		function refreshidStatus$t(){
			if(document.getElementById('$id')){
				var XHR = new XHRConnection();
				XHR.appendData('STATUSOF','{$_GET["appcode"]}');
				XHR.setLockOff();
				XHR.sendAndLoad('$page', 'POST',X_refreshidStatus$t);   
			}
		}
		
	var X_GetInfos$t= function (obj) {
		var res=obj.responseText;
		if (res.length>3){
			document.getElementById('textarea$t').value=res;
			Loadjs('$page?ifStopped={$_GET["appcode"]}&t=$t&action=$action');
		}
	}		
		
	function GetInfos$t(){
		var XHR = new XHRConnection();
		XHR.appendData('LOGSOF','$t');
		XHR.setLockOff();
		XHR.sendAndLoad('$page', 'POST',X_GetInfos$t);   
	}		
		
	
	step1$t();		
	";
	echo $tpl->_ENGINE_parse_body($html);
}
function STATUSOF(){
	$key=$_POST["STATUSOF"];
	$sock=new sockets();
	$datas=base64_decode($sock->getFrameWork("system.php?all-services=yes"));
	$bsini=new Bs_IniHandler();
	$bsini->loadFile("/usr/share/artica-postfix/ressources/logs/global.status.ini");
	$img="42-red.png";
	if($bsini->_params[$key]["running"]==1){
		$img="42-green.png";
	}
	echo $img;
}
function LOGSOF(){
	$key=$_POST["LOGSOF"];
	$file="/usr/share/artica-postfix/ressources/logs/web/$key.log";
	if(!is_file($file)){
		$tpl=new templates();
		echo $tpl->javascript_parse_text("{please_wait}...\n",1);
		return;
	}
	$t=explode("\n",@file_get_contents($file));
	@file_put_contents($file, @implode("\n", $t));
	
	
	krsort($t);
	echo @implode("\n", $t);
	
	
}

function ifStopped(){
	$key=$_GET["ifStopped"];
	if($GLOBALS["VERBOSE"]){echo "KEY: $key<br>\n";}
	$tTime=$_GET["t"];
	header("content-type: application/x-javascript");
	$file="/usr/share/artica-postfix/ressources/logs/web/$tTime.log";
	if($GLOBALS["VERBOSE"]){echo "Open $file<br>\n";}
	$action=$_GET["action"];
	if(!is_file($file)){return;}
	$t=explode("\n",@file_get_contents($file));
	
	while (list ($num, $ligne) = each ($t) ){
		if( ($action=="restart") OR ($action=="start") OR ($action=="reload") ){
			if(preg_match("#Starting.*?started with new PID#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;	
			}
			
			if(preg_match("#(already|success) running.*?pid\s+[0-9]+#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}
			if(preg_match("#Process was started#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}
			
			if(preg_match("#success with pid#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}
			
			if(preg_match("#Already instance running#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}
			
			if(preg_match("#please wait, installing#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}
			
			if(preg_match("#Success service reloaded#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}
			
			if(preg_match("#Success service started#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}
			
			if(preg_match("#service disabled#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}
			
			if(preg_match("#Starting.*?Success PID#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}
			
			if(preg_match("#Starting.*?failed#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}	

			if(preg_match("#Starting.*?Daemon Success#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}	
			
			if(preg_match("#Already Artica task running#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}
			
			if(preg_match("#started pid#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}
			
			if(preg_match("#already started#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}
			
			if(preg_match("#Already running.*?PID#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}
			
			if(preg_match("#already running pid#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}
			
			if(preg_match("#Service already started#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}
			
			if(preg_match("#Success service started pid#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}
			
			if(preg_match("#Success service reloaded#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}
			
			if(preg_match("#Transparent proxy done#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}
			
			if(preg_match("#Already running since#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}
			if(preg_match("#only restart each#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}
			
			if(preg_match("#already Artica Starting#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}
			
			if(preg_match("#Generator.*?success#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}
			
			if(preg_match("#linking to network success#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}
			
			
		
		}
		
		
		if( ($action=="stop")){
			
			if(preg_match("#Already stopped#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}

			if(preg_match("#Shutting down success#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}		
			
			if(preg_match("#success stopped#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}		

			if(preg_match("#Generator.*?success#i", $ligne)){
				echo "if(document.getElementById('stopall-$tTime')){document.getElementById('stopall-$tTime').value=1}\n";
				return;
			}
			
		}
		
		
		
	
	}
	
	
	
}
