<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
if(isset($_GET["verbose"])){$GLOBALS["OUTPUT"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.roundcube.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');

	
	$user=new usersMenus();
	if($user->AsMailBoxAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	if(isset($_GET["form"])){switch_forms();exit;}
	if(isset($_GET["RoundCubeHTTPEngineEnabled"])){main_settings_edit();exit;}
	if(isset($_GET["main"])){main_switch();exit;}
	if(isset($_GET["debug_level"])){main_save_roundcube_settings();exit;}
	if(isset($_GET["script"])){ajax_js();exit;}
	if(isset($_GET["ajax-pop"])){ajax_pop();exit;}
	if(isset($_GET["roundcubestatus"])){echo main_status();exit;}
	if(isset($_GET["roundcube-pluginv3-list"])){echo pluginv3_table();exit;}
	if(isset($_GET["enable-plugin"])){pluginv3_enable();exit;}
	if(isset($_GET["form1"])){form1_js();exit;}
	if(isset($_GET["form2"])){form2_js();exit;}
	if(isset($_GET["plugins"])){formplugins_js();exit;}
	if(isset($_GET["logslogs"])){echo main_rlogs_parse();exit;}
	
	if(isset($_GET["plugins-sieve"])){plugins_sieve_js();exit;}
	if(isset($_GET["plugin-sieve-popup"])){plugins_sieve_popup();exit;}
	if(isset($_GET["RoundCubeEnableSieve"])){plugins_sieve_save();exit;}
	
	if(isset($_GET["plugins-calendar"])){plugins_calendar_js();exit;}
	if(isset($_GET["plugin-calendar-popup"])){plugins_calendar_popup();exit;}
	if(isset($_GET["RoundCubeEnableCalendar"])){plugins_calendar_save();exit;}
	if(isset($_GET["roundcube-rebuild"])){RoundCube_restart();exit;}	
	if(isset($_GET["httpengines-form"])){httpengines_form();exit;}
	if(isset($_POST["EnableFreeWeb"])){httpengines_save();exit;}
	if(isset($_GET["mysql-status"])){mysql_status();exit;}
	
	
	
function ajax_pop(){
	
	if(!is_file("/usr/share/roundcube/index.php")){
		
		$html="
		<center>
		
		". FATAL_ERROR_SHOW_128("{ERROR_NOT_INSTALLED_REDIRECT}<p>{about_roundcube}</p>
				<div style='text-align:right'><hr>". button("{install_now}","Loadjs('roundcube.install.progress.php')",24)."</div")."
		</center>";
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body($html);
		return;
	}
	
	
	echo main_tabs();		
}

function form1_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{webserver_parameters}');
	echo "YahooWin2(800,'$page?form=form1','$title');";
	
}
	function form2_js(){
		header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{roundcube_parameters}');
	echo "YahooWin2(800,'$page?form=form2','$title');";
	
}

function plugins_sieve_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{plugin_sieve}');
	echo "
	function RoundCubeEnableSievePage(){
		YahooWin2(450,'$page?plugin-sieve-popup=yes','$title');
		}
	RoundCubeEnableSievePage();";	
}
function plugins_calendar_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{plugin_calendar}');
	echo "
	function RoundCubeEnableCalendarPage(){
		YahooWin2(450,'$page?plugin-calendar-popup=yes','$title');
		}
	RoundCubeEnableCalendarPage();";	
}

function formplugins_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{APP_ROUNDCUBE3}&nbsp;{plugins}');
	echo "YahooWin2(700,'$page?form=plugins','$title');";
	
}


function httpengines_form(){
	
	
$tpl=new templates();
	$page=CurrentPageName();	
	$sock=new sockets();
	$RoundCubeHTTPEngineEnabled=$sock->GET_INFO("RoundCubeHTTPEngineEnabled");
	$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
	
	
	
	$freeweb=Paragraphe_switch_img("{enable_freeweb}","{enable_freeweb_text}","EnableFreeWebB",
			$EnableFreeWeb,null,1090);
	$Roundcube=Paragraphe_switch_img("{enable_roundcubehttp}","{enable_enable_roundcubehttp_text}",
			"RoundCubeHTTPEngineEnabled",$RoundCubeHTTPEngineEnabled,null,1090);
	
	$form="
	<hr>
	<div style='text-align:right;width:100%'>". button("{apply}", "SaveRoundCubeWebEngine()",42)."</div>
	
	<script>
	
	
	var x_SaveRoundCubeWebEngine=function (obj) {
			var results=obj.responseText;
			RefreshTab('main_config_roundcube');
		}	
		
		function SaveRoundCubeWebEngine(){
			var XHR = new XHRConnection();
    		XHR.appendData('EnableFreeWeb',document.getElementById('EnableFreeWebB').value);
    		XHR.appendData('RoundCubeHTTPEngineEnabled',document.getElementById('RoundCubeHTTPEngineEnabled').value);
 			AnimateDiv('httpengines-form');
    		XHR.sendAndLoad('$page', 'POST',x_SaveRoundCubeWebEngine);
			
		}		
	
		
	</script>
	";
	
	$html="<div style='width:98%' class=form>$freeweb<hr>$Roundcube</div>$form";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function httpengines_save(){
	$sock=new sockets();
	$sock->SET_INFO("RoundCubeHTTPEngineEnabled",$_POST["RoundCubeHTTPEngineEnabled"]);
	$sock->SET_INFO("EnableFreeWeb",$_POST["EnableFreeWeb"]);
	
	$sock->SET_INFO("EnableFreeWeb",$_POST["EnableFreeWeb"]);
	$sock->SET_INFO("EnableApacheSystem",$_POST["EnableFreeWeb"]);	
	$sock->getFrameWork("cmd.php?freeweb-restart=yes");
	$sock->getFrameWork("cmd.php?roundcube-restart=yes");
}

function multiple_roundcube(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();	
	$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}
	if($EnableFreeWeb==0){
		echo "<H2>" .$tpl->_ENGINE_parse_body("<img src='img/error-128.png' align='left' style='margin:5px'>{ERROR_ROUNDCUBE_MULTIPLE_INSTANCES_FREEWEB}")."</H2>";
		return;
	}
	
	$html="<div id='roundcube-freeweb-div'></div>
	
	<script>
		LoadAjax('roundcube-freeweb-div','freeweb.php?popup=yes&force-groupware=ROUNDCUBE');
	</script>
	";
	
	
	echo $html;
	
}


function ajax_index(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$configure=Paragraphe("rebuild-64.png","{generate_config}","{rebuild_roundcube_parameters}","javascript:RoundCubeRebuild()",null,280);
	$RoundCubeHTTPEngineEnabled=$sock->GET_INFO("RoundCubeHTTPEngineEnabled");
	if(!is_numeric($RoundCubeHTTPEngineEnabled)){$RoundCubeHTTPEngineEnabled=0;}
	$apply_upgrade_help=$tpl->javascript_parse_text("{apply_upgrade_help}");
	if($RoundCubeHTTPEngineEnabled==0){$configure=null;}
	
	echo $tpl->_ENGINE_parse_body("
	<table style='width:100%'>
	<tr>
		<td valign='top'>
			<img src='img/roundcube-original-logo.png'><div class=explain style='font-size:18px'>{about_roundcube}<br>{ROUNDCUBE_HTTP_ENGINE_FORMS_EXPLAIN}</div>
			<div id='httpengines-form'></div>
			</td>
		<td valign='top'>
				<div id='roundcube_daemon_status'>$status</div>
				<br><div id='roundcube-rebuild-div'>$configure</div>
		</td>
	</tr>
	</table>
	<br>")."
	
	
	
	<script>
		RoundCubeStatus();
		
		var x_RoundCubeRebuild= function (obj) {
			alert('$apply_upgrade_help');
	 		RefreshTab('main_config_roundcube');
		}		
		
		function RoundCubeRebuild(){
			var XHR = new XHRConnection();
			XHR.appendData('roundcube-rebuild','yes');
			AnimateDiv('roundcube-rebuild-div');
			XHR.sendAndLoad('$page', 'GET',x_RoundCubeRebuild);	
		}
		
		LoadAjax('httpengines-form','$page?httpengines-form=yes');
	</script>
	";	
	
	 
}

function RoundCube_restart(){
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?roundcube-restart=yes");
	
	
}


function ajax_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$x=$tpl->javascript_parse_text('{confirm_rebuild}');
	$title=$tpl->_ENGINE_parse_body('{APP_ROUNDCUBE}');
	$prefix=str_replace(".","_",$page);
	$startfunc="LoadMainRoundCube();";
	
	if(isset($_GET["in-front-ajax"])){$startfunc="LoadInLineMainRoundCube();";}
	
	
	
	$html="

	function LoadMainRoundCube(){
		YahooWinS(745,'$page?ajax-pop=yes','$title');
	}
	
	function LoadInLineMainRoundCube(){
		$('#BodyContent').load('$page?ajax-pop=yes&newinterface={$_GET["newinterface"]}');
	}
	
	
	var x_RebuildTables= function (obj) {
	 	RefreshTab('main_config_roundcube');
	}		
	
	function RebuildTables(){
			var z=confirm(x);
			if (z){
				var XHR = new XHRConnection();
				XHR.appendData('main',mysql);
				XHR.appendData('rebuild','yes');
				XHR.sendAndLoad('$page', 'GET',x_RebuildTables);	
			}
		}
		
function RoundCubeStatus(){
		LoadAjax('roundcube_daemon_status','$page?roundcubestatus=yes');
	
	}
	
var x_RoundCubepluginv3Enable= function (obj) {
	var results=obj.responseText;
	alert(results);
	LoadAjax('rndcube3pluglist','$page?roundcube-pluginv3-list=yes');
	}		
	
function RoundCubepluginv3Enable(field){
	var XHR = new XHRConnection();
	XHR.appendData('enable-plugin',field);
	XHR.appendData('value',document.getElementById(field).value);
	document.getElementById('rndcube3pluglist').innerHTML='<center style=\"margin:20px;padding:20px\"><img src=\"img/wait_verybig.gif\"></center>';
	XHR.sendAndLoad('$page', 'GET',x_RoundCubepluginv3Enable);	
}
		
$startfunc
";
	
	echo $html;
	
	
	
	
}
function main_tabs(){
	
	$page=CurrentPageName();
	$array["index"]='{index}';
	$array["mysql"]='{mysql}';
	$array["mains"]='{settings}';
	$array["settings"]='{options}';
	//$array["rlogs"]='{rlogs}';
	
	//$array["multiple-roundcube"]='{multiple_webmail}';
	$tpl=new templates();
	
	$style="style='font-size:24px'";
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="mains"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"roundcube.params.php\"><span $style>$ligne</span></a></li>\n");
			continue;
		}
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?main=$num\"><span $style>$ligne</span></a></li>\n");
		
		}
	
	
	
	return build_artica_tabs($html, "main_config_roundcube",1490)."<script>
			
				function LoadMainRoundCube(){
		YahooWinS(745,'$page?ajax-pop=yes','$title');
	}
	
	function LoadInLineMainRoundCube(){
		$('#BodyContent').load('$page?ajax-pop=yes&newinterface={$_GET["newinterface"]}');
	}
	
	
	var x_RebuildTables= function (obj) {
	 	RefreshTab('main_config_roundcube');
	}		
	
	function RebuildTables(){
			var z=confirm(x);
			if (z){
				var XHR = new XHRConnection();
				XHR.appendData('main',mysql);
				XHR.appendData('rebuild','yes');
				XHR.sendAndLoad('$page', 'GET',x_RebuildTables);	
			}
		}
		
function RoundCubeStatus(){
		LoadAjax('roundcube_daemon_status','$page?roundcubestatus=yes');
	
	}
	
var x_RoundCubepluginv3Enable= function (obj) {
	var results=obj.responseText;
	alert(results);
	LoadAjax('rndcube3pluglist','$page?roundcube-pluginv3-list=yes');
	}		
	
function RoundCubepluginv3Enable(field){
	var XHR = new XHRConnection();
	XHR.appendData('enable-plugin',field);
	XHR.appendData('value',document.getElementById(field).value);
	document.getElementById('rndcube3pluglist').innerHTML='<center style=\"margin:20px;padding:20px\"><img src=\"img/wait_verybig.gif\"></center>';
	XHR.sendAndLoad('$page', 'GET',x_RoundCubepluginv3Enable);	
}
			</script>";
	
		
}

function plugins_sieve_popup(){
	$page=CurrentPageName();
	$sock=new sockets();
	$RoundCubeEnableSieve=$sock->GET_INFO("RoundCubeEnableSieve");
	$enable=Paragraphe_switch_img("{plugin_sieve_enable}","{plugin_sieve_text}","RoundCubeEnableSieve",$RoundCubeEnableSieve,279);
	
	$html="
	<div id='RoundCubeEnableSieveDiv'>
	<table style='width:100%'>
	<tr>
		<td valign='top'><img src='img/filter-128.png'></td>
		<td valign='top'>$enable</td>
	</tr>
	</table>
	</div>
	<div style='width:100%;text-align:right'>". button("{apply}","RoundCubeEnableSieve()",18)."</div>
	<script>
	
	var x_RoundCubeEnableSieve= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		RoundCubeEnableSievePage();
	}	
	
		function RoundCubeEnableSieve(){
			var XHR = new XHRConnection();
			XHR.appendData('RoundCubeEnableSieve',document.getElementById('RoundCubeEnableSieve').value);
			document.getElementById('RoundCubeEnableSieveDiv').innerHTML='<center style=\"margin:20px;padding:20px\"><img src=\"img/wait_verybig.gif\"></center>';
			XHR.sendAndLoad('$page', 'GET',x_RoundCubeEnableSieve);	
		}	
	
	</script>
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
}
function plugins_calendar_popup(){
	$page=CurrentPageName();
	$sock=new sockets();
	$RoundCubeEnableCalendar=$sock->GET_INFO("RoundCubeEnableCalendar");
	$enable=Paragraphe_switch_img("{plugin_calendar_enable}","{plugin_calendar_text}","RoundCubeEnableCalendar",$RoundCubeEnableCalendar,279);
	
	$html="<H1>{plugin_calendar}</H1>
	<div id='RoundCubeEnableSieveDiv'>
	<table style='width:100%'>
	<tr>
		<td valign='top'><img src='img/calendar-128.png'></td>
		<td valign='top'>$enable</td>
	</tr>
	</table>
	</div>
	<div style='width:100%;text-align:right'>". button("{apply}","RoundCubeEnableCalendar()",18)."</div>
	<script>
	
	var x_RoundCubeEnableCalendar= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		RoundCubeEnableCalendarPage();
	}	
	
		function RoundCubeEnableCalendar(){
			var XHR = new XHRConnection();
			XHR.appendData('RoundCubeEnableCalendar',document.getElementById('RoundCubeEnableCalendar').value);
			document.getElementById('RoundCubeEnableSieveDiv').innerHTML='<center style=\"margin:20px;padding:20px\"><img src=\"img/wait_verybig.gif\"></center>';
			XHR.sendAndLoad('$page', 'GET',x_RoundCubeEnableCalendar);	
		}	
	
	</script>
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
}

function plugins_sieve_save(){
	$sock=new sockets();
	$sock->SET_INFO("RoundCubeEnableSieve",$_GET["RoundCubeEnableSieve"]);
	$rnd=new roundcube();
	$rnd->Save();
}
function plugins_calendar_save(){
	$sock=new sockets();
	$sock->SET_INFO("RoundCubeEnableCalendar",$_GET["RoundCubeEnableCalendar"]);
	$rnd=new roundcube();
	$rnd->Save();
}

function form_tabs(){
	
	
	if(!isset($_GET["form"])){$_GET["form"]="form1";};
	$page=CurrentPageName();
	$users=new usersMenus();
	$array["form1"]='{page} 1';
	$array["form2"]='{page} 2';
	$tpl=new templates();
	if($users->roundcube_intversion>29){
		$main=base64_encode("MAIN_INSTANCE");
		
		$sieve=Paragraphe("filter-64.png",'{plugin_sieve}',"{plugin_sieve_text}","javascript:Loadjs('$page?plugins-sieve=yes')");
		$calendar=Paragraphe("calendar-64.png",'{plugin_calendar}',"{plugin_calendar_text}","javascript:Loadjs('$page?plugins-calendar=yes')");
		$globaladdressBook=Paragraphe("addressbook-64.png","{global_addressbook}","{global_addressbook_explain}",
		"javascript:Loadjs('roundcube.globaladdressbook.php?www=$main')");
	}
	
	
	$form1=Paragraphe("domain-main-64.png","{webserver_parameters}","{webserver_parameters_text}","javascript:Loadjs('$page?form1=yes')");
	$form2=Paragraphe("parameters-64.png","{roundcube_parameters}","{roundcube_parameters_text}","javascript:Loadjs('$page?form2=yes')");
	$Hacks=Paragraphe("Firewall-Secure-64.png","Anti-Hacks","{AntiHacks_roundcube_text}","javascript:Loadjs('roundcube.hacks.php')");
	
	$tr[]=$form1;
	$tr[]=$form2;
	$tr[]=$globaladdressBook;
	$tr[]=$sieve;
	$tr[]=$calendar;
	$tr[]=$Hacks;
	

	$html=CompileTr3($tr);
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
			
}



function main_switch(){
	
	switch ($_GET["main"]) {
		case "index":echo ajax_index();exit;break;
		case "conf":echo main_conf();exit;break;
		case "rlogs":echo main_rlogs();exit;break;
		case "nmap-add":echo main_form_add();exit;break;
		case "status":echo main_status();exit;break;
		case "mysql":echo main_mysql();exit;break;
		case "rlogss":echo main_rlogs_parse();exit;break;
		case "multiple-roundcube":echo multiple_roundcube();exit;break;
		
	
		
		default:main_settings();break;
	}
	
	
	
}

function  main_conf(){
	
	$round=new  roundcube();
	$tbl=explode("\n",$round->RoundCubeLightHTTPD);
	
	while (list ($num, $line) = each ($tbl)){
		if($line<>null){
			$line=htmlentities($line);
			$line=str_replace("\t","&nbsp;&nbsp;&nbsp;",$line);
			$html=$html."<div><code>$line</code></div>";
			
		}
		
	}
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("<div style='padding:20px;width:90%;height:400px;overflow:auto;background-color:white'>$html</div>");
	
}

function main_mysql(){
	$user=new usersMenus();
	$page=CurrentPageName();
	$roundcube=new roundcube();
	$t=time();
	if(isset($_GET["rebuild"])){
		$roundcube->RebuildMysql();
	}
	
	
	
	$status=$roundcube->ParseMysqlInstall();
	$html="
	<table style='width:100%'>
	<tr>
	<td style='vertical-align:top'>
			<div style='width:98%' class=form id='$t'></div>
			<div style='text-align:right'>". imgtootltip("refresh-32.png",null,"LoadAjax('$t','$page?mysql-status=yes');")."</div>
	</td>
	<td style='vertical-align:top'>

			<div style='width:98%' class=form>
			<table style='width:100%'>
			<tr>
				<td valign='top' nowrap align='right' class=legend style='font-size:20px' style='font-size:20px'>{RoundCubePath}:</strong></td>
				<td valign='top' nowrap align='left'><strong style='font-size:18px'>$user->roundcube_folder</td>
			</tr>
			<tr>
				<td valign='top' nowrap align='right' class=legend style='font-size:20px' style='font-size:20px'>{roundcube_mysql_sources}:</strong></td>
				<td valign='top' nowrap align='left'><strong style='font-size:18px'>$user->roundcube_mysql_sources</strong></td>
			</tr>	
			<tr>
				<td valign='top' nowrap align='right' class=legend style='font-size:20px' style='font-size:20px'>{database}:</strong></td>
				<td valign='top' nowrap align='left'><strong style='font-size:20px'>roundcubemail</strong></td>
			</tr>
			<tr>
				<td valign='top' nowrap align='right' class=legend style='font-size:20px' style='font-size:20px'>{database_status}:</strong></td>
				<td valign='top' nowrap align='left'><strong style='font-size:20px'>$status</strong></td>
			</tr>
			<tr>
			<td valign='top' nowrap align='right' colspan=2>
				<hr>".button("{rebuild}","RebuildTables()",18)."
			</td>
				
			</tr>													
		</table></div>
	</td>
</tr>
</table>
		
		<script>LoadAjax('$t','$page?mysql-status=yes');</script>
						
	
						
						
						
		";
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);	
}

function mysql_status(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$data=base64_decode($sock->getFrameWork('roundcube.php?status=yes'));
	
	$ini->loadString($data);
	$status1=DAEMON_STATUS_ROUND("ROUNDCUBE",$ini,null);
	$status2=DAEMON_STATUS_ROUND("APP_ROUNDCUBE_DB",$ini,null);
	
	$array[0]="{not_installed}";
	$array[3]="{server}";
	$array[4]="{client}";
	
	$RoundCubeDedicateMySQLServer=intval($sock->GET_INFO("RoundCubeDedicateMySQLServer"));
	$RoundCubeMySQLServiceType=$sock->GET_INFO("RoundCubeMySQLServiceType");
	if(!is_numeric($RoundCubeMySQLServiceType)){
		if($RoundCubeDedicateMySQLServer==1){
		$RoundCubeMySQLServiceType=3;}else{
			$RoundCubeMySQLServiceType=0;
		}
	}
	$RoundCubeMySQLServiceType_status=$array[$RoundCubeMySQLServiceType];
	
	
	if($RoundCubeMySQLServiceType==4){
	$username=$sock->GET_INFO("RoundCubeRemoteMySQLServerAdmin");
	$password=$sock->GET_INFO("RoundCubeRemoteMySQLServerPassword");
	$mysqlserver=$sock->GET_INFO("RoundCubeRemoteMySQLServer");
	$ListenPort=$sock->GET_INFO("RoundCubeRemoteMySQLServerPort");
		$exptr="
		<tr>
		<td style='font-size:20px' class=legend>{mysql_server}:</td>
		<td style='font-size:20px;font-weight:bold' class=legend>$mysqlserver:$ListenPort</td>
		</tr>		
				
		";
	
	}
	
	if($RoundCubeMySQLServiceType==3){
	$WORKDIR=$sock->GET_INFO("RoundCubeDedicateMySQLWorkDir");
	if($WORKDIR==null){$WORKDIR="/home/roundcube-db";}
	
	
	$TuningParameters=unserialize(base64_decode($sock->GET_INFO("RoundCubeTuningParameters")));
	$ListenPort=$TuningParameters["ListenPort"];
	$exptr="
	<tr>
	<td style='font-size:20px' class=legend>{listen_port}:</td>
	<td style='font-size:20px;font-weight:bold' class=legend>$ListenPort</td>
	</tr>
	<tr>
	<td style='font-size:20px' class=legend>{directory}:</td>
	<td style='font-size:20px;font-weight:bold' class=legend>$WORKDIR</td>
	</tr>	
	";
	
	}
	
	$html="
	<table style='width:100%'>
	<tr>
		<td style='font-size:20px' class=legend>{APP_ROUNDCUBE_DB}:</td>
		<td style='font-size:20px;font-weight:bold' class=legend>$RoundCubeMySQLServiceType_status</td>
	</tr>$exptr
	</table>
	
	<center style='margin:20px'>$status1<br>$status2".button("{run_wizard_install}", "Loadjs('RoundCubeDB.wizard.php')",18)."</center>";
	echo $tpl->_ENGINE_parse_body($html);
}


function main_errors(){
	
	if(!function_exists('mcrypt_module_open')){
		$error="<div style='color:#d32d2d'>mcrypt.so module is not loaded</div>";
		
		
	}
	if($error<>null){
		$error="<H5>Errors</H5>$error<hr>";
		
	}
	return $error;
}


function form1(){
	$page=CurrentPageName();
	$user=new usersMenus();
	$round=new roundcube();
	$artica=new artica_general();
	$sock=new sockets();
	$debug_levela=array(1=>"log",2=>"report",4=>"show",8=>"trace");
	$debug_level=Field_array_Hash($debug_levela,'debug_level',$round->roundCubeArray["debug_level"]);
	$tpl=new templates();
	$lighttp_max_load_per_proc=$tpl->_ENGINE_parse_body('{lighttp_max_load_per_proc}');
	if(strlen($lighttp_max_load_per_proc)>40){$lighttp_max_load_per_proc=texttooltip(substr($lighttp_max_load_per_proc,0,37)."...",$lighttp_max_load_per_proc);}
	$RoundCubeHTTPSPort=$sock->GET_INFO("RoundCubeHTTPSPort");
	if(!is_numeric($RoundCubeHTTPSPort)){$RoundCubeHTTPSPort=449;}
	
$html="
	<form name='FFM1'>
			<div id='wait'></div>
			<table style='width:99%' class=form>
			<tr>
				<td valign='top' nowrap align='right' class=legend style='font-size:20px'>{RoundCubePath}:</strong></td>
				<td valign='top' nowrap align='left'><strong style='font-size:20px'>$user->roundcube_folder</td>
			</tr>
			<tr>
				<td valign='top' nowrap align='right' class=legend style='font-size:20px'>{roundcube_web_folder}:</strong></td>
				<td valign='top' nowrap align='left'><strong style='font-size:20px'>$user->roundcube_web_folder</td>
			</tr>			

					
			
			<tr>
				<td valign='top' nowrap align='right' class=legend style='font-size:20px'>{RoundCubeHTTPEngineEnabled}:</strong></td>
				<td valign='top' nowrap align='left'>" . Field_checkbox('RoundCubeHTTPEngineEnabled',1,$round->RoundCubeHTTPEngineEnabled,'{enable_disable}')."</td>
			</tr>
			<tr>
				<td valign='top' nowrap align='right' class=legend style='font-size:20px'>{listen_port}:</strong></td>
				<td valign='top' nowrap align='left'>" . Field_text('https_port',$RoundCubeHTTPSPort,'width:50px;font-size:20px')."</td>
			</tr>
			<tr>
				<td valign='top' nowrap align='right' class=legend style='font-size:20px'>HTTPS:</strong></td>
				<td valign='top' nowrap align='left'>" . Field_checkbox('ssl_enabled',1,$round->roundCubeArray["ssl_enabled"])."</td>
			</tr>			
			
					
			<tr>
				<td align='right' class=legend style='font-size:20px'>{lighttp_max_proc}:</strong></td>
				<td>" . Field_text('lighttp_max_proc',trim($round->lighttp_max_proc),'width:50px;font-size:20px')."</td>
			</tr>
			<tr>
				<td align='right' class=legend style='font-size:20px'>{lighttp_min_proc}:</strong></td>
				<td>" . Field_text('lighttp_min_proc',trim($round->lighttp_min_proc),'width:50px;font-size:20px')."</td>
			</tr>
			<tr>
				<td align='right' class=legend style='font-size:20px'>$lighttp_max_load_per_proc:</strong></td>
				<td>" . Field_text('lighttp_max_load_per_proc',trim($round->lighttp_max_load_per_proc),'width:50px;font-size:20px')."</td>
			</tr>		
		
			<tr>
				<td align='right' class=legend style='font-size:20px'>{PHP_FCGI_CHILDREN}:</strong></td>
				<td>" . Field_text('PHP_FCGI_CHILDREN',trim($round->PHP_FCGI_CHILDREN),'width:50px;font-size:20px')."</td>
			</tr>	
			<tr>
				<td align='right' class=legend style='font-size:20px'>{PHP_FCGI_MAX_REQUESTS}:</strong></td>
				<td>" . Field_text('PHP_FCGI_MAX_REQUESTS',trim($round->PHP_FCGI_MAX_REQUESTS),'width:50px;font-size:20px')."</td>
			</tr>				
			<tr>
			<td colspan=2 align='right'>
				".button('{apply}','SaveRoundCubeForm1()',18)."
			</tr>
			</table>
			</form>		
			<script>
			
			var X_SaveRoundCubeForm1= function (obj) {
				document.getElementById('wait').innerHTML='';
				}			
			
			function SaveRoundCubeForm1(){
				var XHR = new XHRConnection();
				if(document.getElementById('RoundCubeHTTPEngineEnabled').checked){XHR.appendData('RoundCubeHTTPEngineEnabled','1');}else{XHR.appendData('RoundCubeHTTPEngineEnabled','0');}
				if(document.getElementById('ssl_enabled').checked){XHR.appendData('ssl_enabled','1');}else{XHR.appendData('ssl_enabled','0');}
				XHR.appendData('https_port',document.getElementById('https_port').value);
				XHR.appendData('lighttp_max_proc',document.getElementById('lighttp_max_proc').value);
				XHR.appendData('lighttp_min_proc',document.getElementById('lighttp_min_proc').value);
				XHR.appendData('lighttp_max_load_per_proc',document.getElementById('lighttp_max_load_per_proc').value);
				XHR.appendData('PHP_FCGI_CHILDREN',document.getElementById('PHP_FCGI_CHILDREN').value);
				XHR.appendData('PHP_FCGI_MAX_REQUESTS',document.getElementById('PHP_FCGI_MAX_REQUESTS').value);
				document.getElementById('wait').innerHTML='<center><img src=img/wait_verybig.gif></center>';
				XHR.sendAndLoad('$page', 'GET',X_SaveRoundCubeForm1);	
			}
			
			</script>
			
	
";

$tpl=new templates();
return $tpl->_ENGINE_parse_body($html);
	
}

function pluginv3_enable(){
	$round=new roundcube();	
	//if($_GET["value"]==1){$_GET["value"]=0;$TEXT="{disabled}";}else{$_GET["value"]=1;$TEXT="{enabled}";}
	$round->roundCubeArray[$_GET["enable-plugin"]]=$_GET["value"];
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($_GET["enable-plugin"]." $TEXT\n");
	$round->Save();
	
}

function pluginsv3(){
	$page=CurrentPageName();
	$user=new usersMenus();
	$round=new roundcube();	
	$plugins="<div id='rndcube3pluglist' style='width:100%;height:450px;overflow:auto'>".pluginv3_table()."</div>";
	$plugins=$plugins;
	
	
$html="$tab
<div class=explain>{APP_ROUNDCUBE3_PLUGINS_EXPLAIN}</div>
$plugins
";	
$tpl=new templates();
return $tpl->_ENGINE_parse_body($html);	
}

function pluginv3_table(){
	$round=new roundcube();	
	
$html="
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:98%'>
	<thead class='thead'>
		<tr>
		<th width=99% colspan=2>&nbsp;</th>
		<th width=1% colspan=2>&nbsp;</th>
		</tr>
	</thead>
	<tbody class='tbody'>";	
	
	
	if(is_array($round->roundcube_plugins_array)){
	while (list ($num, $line) = each ($round->roundcube_plugins_array)){
		if($num=="new_user_identity"){continue;}
		if($num=="autologon"){continue;}
		if($num=="example_addressbook"){continue;}
		if($num=="password"){continue;}
		if($num=="sieverules"){continue;}
		if($num=="calendar"){continue;}
	if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		if($round->roundCubeArray["plugin_$num"]==null){$round->roundCubeArray["plugin_$num"]=0;}
		$enable=Field_numeric_checkbox_img("plugin_$num",$round->roundCubeArray["plugin_$num"]);
		
		
		
		$html=$html."
		<tr class=$classtr>
			<td width=1% valign='top'><img src='img/24-nodes.png'></td>
			<td width=98% valign='top' style='font-size:13px'>$line</td>
			<td width=1% valign='top'>$enable</td>
			<td width=1% valign='top'>".button("{apply}","RoundCubepluginv3Enable('plugin_$num')",18)."</td>
			
		</tr>";
	}}
	
	$html=$html."</tbody></table>";
	
	$tpl=new templates();
	return $tpl->_ENGINE_parse_body($html);
	
}

function form2(){
	$page=CurrentPageName();
	$user=new usersMenus();
	$round=new roundcube();
	$artica=new artica_general();
	$debug_levela=array(1=>"log",2=>"report",4=>"show",8=>"trace");
	$debug_level=Field_array_Hash($debug_levela,'debug_level',$round->roundCubeArray["debug_level"],null,null,0,"font-size:20px");
	$tpl=new templates();
	$lighttp_max_load_per_proc=$tpl->_ENGINE_parse_body('{lighttp_max_load_per_proc}');
	if(strlen($lighttp_max_load_per_proc)>40){$lighttp_max_load_per_proc=texttooltip(substr($lighttp_max_load_per_proc,0,37)."...",$lighttp_max_load_per_proc);}
	$auto_create_user=$tpl->_ENGINE_parse_body('{auto_create_user}');
	if(strlen($auto_create_user)>70){$auto_create_user=texttooltip(substr($auto_create_user,0,67)."...",$auto_create_user);}
	
	$enable_caching=$tpl->_ENGINE_parse_body('{enable_caching}');
	if(strlen($enable_caching)>70){$enable_caching=texttooltip(substr($enable_caching,0,67)."...",$enable_caching);}
	
	
$html="<div id='wait'></div><table style='width:99%' class=form>
			<tr>
				<td valign='top' nowrap align='right' class=legend style='font-size:20px'>{user_link}:</strong></td>
				<td valign='top' nowrap align='left'>" . Field_text('user_link',$round->roundCubeArray["user_link"],'width:195px')."</td>
			</tr>
			<tr>
				<td valign='top' nowrap align='right' class=legend style='font-size:20px'>{roundcube_ldap_directory}:</strong></td>
				<td valign='top' nowrap align='left'>" . Field_checkbox('ldap_ok',1,$round->roundCubeArray["ldap_ok"])."</td>
			</tr>							
			<tr>
				<td valign='top' nowrap align='right' class=legend style='font-size:20px'>{debug_level}:</strong></td>
				<td valign='top' nowrap align='left'><strong>$debug_level</td>
			</tr>
			<tr>
				<td valign='top' nowrap align='right' class=legend style='font-size:20px'>$enable_caching:</strong></td>
				<td valign='top' nowrap align='left'>" . Field_TRUEFALSE_checkbox('enable_caching',$round->roundCubeArray["enable_caching"])."</td>
			</tr>
			<tr>
				<td valign='top' nowrap align='right' class=legend style='font-size:20px'>{upload_max_filesize}:</strong></td>
				<td valign='top' nowrap align='left' style='font-size:20px'>" . Field_text('upload_max_filesize',$round->roundCubeArray["upload_max_filesize"],'width:90px;font-size:20px')."M</td>
			</tr>
			
					
			<tr>
				<td valign='top' nowrap align='right' class=legend style='font-size:20px'>$auto_create_user:</strong></td>
				<td valign='top' nowrap align='left'>" . Field_TRUEFALSE_checkbox('auto_create_user',$round->roundCubeArray["auto_create_user"])."</td>
			</tr>
			<tr>
				<td align='right' class=legend style='font-size:20px'>{default_host}:</strong></td>
				<td>" . Field_text('default_host',trim($round->roundCubeArray["default_host"]),'width:230px;font-size:20px')."</td>
			</tr>
						
			<tr>
				<td valign='top' nowrap align='right' class=legend style='font-size:20px'>Sieve:</strong></td>
				<td valign='top' nowrap align='left' style='font-size:20px'>" . Field_text('sieve_port',$round->SieveListenIp.":".$round->roundCubeArray["sieve_port"],'width:190px;font-size:20px')."</td>
			</tr>						
						
			<tr>
				<td align='right' class=legend style='font-size:20px'>{locale_string}:</strong></td>
				<td>" . Field_text('locale_string',trim($round->roundCubeArray["locale_string"]),'width:60px;font-size:20px')."</td>
			</tr>		
		
			<tr>
				<td align='right' class=legend style='font-size:20px'>{product_name}:</strong></td>
				<td>" . Field_text('product_name',trim($round->roundCubeArray["product_name"]),'width:180px;font-size:20px')."</td>
			</tr>	
			<tr>
				<td align='right' class=legend style='font-size:20px'>{skip_deleted}:</strong></td>
				<td>" . Field_TRUEFALSE_checkbox('skip_deleted',$round->roundCubeArray["skip_deleted"])."</td>
			</tr>
			<tr>
				<td align='right' class=legend style='font-size:20px'>{flag_for_deletion}:</strong></td>
				<td style='padding-left:-3px'>
				<table style='width:100%;margin-left:-4px;padding:0px'>
				<tr>
				<td width=1%  valign='top' style='padding-left:-3px'>
				" . Field_TRUEFALSE_checkbox('flag_for_deletion',$round->roundCubeArray["flag_for_deletion"])."</td>
				<td valign='center' >".help_icon('{flag_for_deletion_text}',true)."</td>
				</tr>
				</table>
				</td>
			</tr>					
			<tr>
			<td colspan=2 align='right'>". button("{apply}","SaveRoundCubeForm2();",18)."
			
			</td>
			</tr>
			</table>
			<script>
			
			var X_SaveRoundCubeForm2= function (obj) {
				document.getElementById('wait').innerHTML='';
				}			
			
			function SaveRoundCubeForm2(){
				var XHR = new XHRConnection();
				if(document.getElementById('ldap_ok').checked){XHR.appendData('ldap_ok','1');}else{XHR.appendData('ldap_ok','0');}
				if(document.getElementById('enable_caching').checked){XHR.appendData('enable_caching','TRUE');}else{XHR.appendData('enable_caching','FALSE');}
				if(document.getElementById('auto_create_user').checked){XHR.appendData('auto_create_user','TRUE');}else{XHR.appendData('auto_create_user','FALSE');}
				if(document.getElementById('flag_for_deletion').checked){XHR.appendData('flag_for_deletion','TRUE');}else{XHR.appendData('flag_for_deletion','FALSE');}
				XHR.appendData('user_link',document.getElementById('user_link').value);
				XHR.appendData('debug_level',document.getElementById('debug_level').value);
				XHR.appendData('upload_max_filesize',document.getElementById('upload_max_filesize').value);
				XHR.appendData('default_host',document.getElementById('default_host').value);
				XHR.appendData('locale_string',document.getElementById('locale_string').value);
				XHR.appendData('sieve_port',document.getElementById('sieve_port').value);
				
				XHR.appendData('product_name',document.getElementById('product_name').value);
				XHR.appendData('skip_deleted',document.getElementById('skip_deleted').value);
				document.getElementById('wait').innerHTML='<center><img src=img/wait_verybig.gif></center>';
				XHR.sendAndLoad('$page', 'GET',X_SaveRoundCubeForm2);	
			}
			
			</script>			
			
			
			
			";
$tpl=new templates();
return $tpl->_ENGINE_parse_body($html);	
}

function switch_forms(){
	
	if($_GET["form"]=="form1"){$form=form1();}
	if($_GET["form"]=="form2"){$form=form2();}
	if($_GET["form"]=="plugins"){$form=pluginsv3();}
	
	echo $form;
	
}


function main_settings(){


	$html="
	<table style='width:100%'>
	<tr>
	<td valign='top'>
		
		<div class=explain style='font-size:16px'>{about_roundcube_engine}</div>".main_errors()."
				".form_tabs()."
		</td>
	</tr>
	</table>
	
	
	";
	
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);
}

function main_settings_edit(){

	$round=new roundcube();
	$sock=new sockets();
	
	if(preg_match("#(.+?):([0-9])+#", $_POST["sieve_port"],$re)){
		$_POST["sieve_port"]=$re[2];
		$sock->SET_INFO("SieveListenIp", $re[1]);
		
	}
	$sock->SET_INFO("RoundCubeHTTPSPort", $_GET["RoundCubeHTTPSPort"]);
	
	while (list ($num, $line) = each ($_GET)){
		$round->$num=$line;
		
	}
	$round->roundCubeArray["ssl_enabled"]=$_GET["ssl_enabled"];
	$round->Save();
	
	
	
	
}

function main_save_roundcube_settings(){
	$round=new roundcube();
	while (list ($num, $line) = each ($_GET)){
		$round->roundCubeArray[$num]=$line;
		
	}
	
	$round->Save();
	}

function  main_status(){
	$users=new usersMenus();
	$ini=new Bs_IniHandler();
	$sock=new sockets();
	$ini->loadString(base64_decode($sock->getFrameWork('roundcube.php?status=yes')));
	$sock=new sockets();
	$version=$sock->getFrameWork('roundcube.php?version=yes');
	
	
	$status=DAEMON_STATUS_ROUND("ROUNDCUBE",$ini,null)."<br>".DAEMON_STATUS_ROUND("APP_ROUNDCUBE_DB",$ini,null)."<br>";
	$tpl=new templates();
	return 
	"<div style='font-size:22px;text-align:right'>v.$version</div>".	$tpl->_ENGINE_parse_body($status)."
	
	<div id='freeweb-src-status'></div>
	<script>
		LoadAjax('freeweb-src-status','freeweb.php?apache-src-status=yes&withoutftp=yes');
	</script>
	
	";	
	
}

function main_rlogs(){
$tpl=new templates();
$page=CurrentPageName();
	echo $tpl->_ENGINE_parse_body(RoundedLightWhite("<div style='padding:20px;height:350px;overflow:auto' id='rlogs'></div>"))."
	<script>
		LoadAjax('rlogs','$page?logslogs=yes');
	</script>
	";
	
	
}


function main_rlogs_parse(){
	
	$datas=explode("\n",@file_get_contents('/usr/share/roundcube/logs/errors'));
	$datas=array_reverse($datas, TRUE);	
	$html="<table style='width:99%'>";
	while (list ($num, $line) = each ($datas)){
		$line=trim($line);
		if($line==null){continue;}
		$c=$c+1;
		if(!preg_match("#^\[(.+?)\]:(.+?):(.+)#",$line,$re)){continue;}
		
		if(preg_match("#(.+)\s+\+(.+)$#",$re[1],$ri)){$re[1]=$ri[1];}
		 if(strlen($re[1])>20){$re[1]=substr($re[1],0,17).'...';}
		 if(strlen($re[2])>15){$re[2]=substr($re[2],0,12).'...';}
		$html=$html ."<tr " . CellRollOver().">
			
			<td width=1% nowrap valign='top' style='border-bottom:1px solid #CCCCCC'>{$re[1]}</td>
			<td width=1% nowrap valign='top' style='border-bottom:1px solid #CCCCCC'><strong>{$re[2]}</strong></td>
			<td width=99% valign='top' style='border-bottom:1px solid #CCCCCC'><code>{$re[3]}</code></td>
			</tr>";
		if($c>50){break;}
		
	}
	$html=$html."</table>";
	return $html;
	
}

?>