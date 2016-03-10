<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.backup.inc');
	include_once('ressources/class.os.system.inc');
	
	$users=new usersMenus();
	if(!$users->AsArticaAdministrator){die();}
	
	if(isset($_POST["InterfaceFonts"])){saveInterfaces();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["ProcessNice"])){save_process();exit;}
	if(isset($_POST["SyslogNgPref"])){save_process();exit;}
	if(isset($_POST["MysqlNice"])){save_process();exit;}
	if(isset($_GET["js"])){echo js_slider();exit;}
	if(isset($_GET["main"])){main_switch();exit;}
	if(isset($_GET["status"])){main_status();exit;}
	if(isset($_GET["MX_REQUESTS"])){save_mimedefang();exit;}
	if(isset($_GET["main_config_mysql"])){echo main_config_mysql();exit;}
	
	if(isset($_GET["DisableJGrowl"])){save_index_page();exit;}
	if(isset($_GET["cron-js"])){echo cron_js();exit;}
	if(isset($_GET["cron-popup"])){echo cron_popup();exit;}
	if(isset($_GET["cron-start"])){echo cron_start();exit;}
	if(isset($_GET["cron-apc"])){echo cron_apc();exit;}
	if(isset($_GET["cron-logon"])){cron_logon();exit;}
	if(isset($_GET["LANGUAGE_SELECTOR_REMOVE"])){cron_logon_save();exit;}
	
	if(isset($_GET["apc-cached-file-list"])){echo cron_apc_list();exit;}
	
	
	if(isset($_GET["cron-index-page"])){cron_index();exit;}
	
	if(isset($_GET["PoolCoverPageSchedule"])){cron_save();exit;}
	if(isset($_GET["MysqlTestsPerfs"])){mysql_test_perfs();exit;}
	
	js();
	
	
function popup(){
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body(main_tabs());
	
}

function saveInterfaces(){
	$sock=new sockets();
	$sock->SET_INFO("InterfaceFonts", stripslashes($_POST["InterfaceFonts"]));
	$sock->SET_INFO("ForceDefaultGreenColor", $_POST["ForceDefaultGreenColor"]);
	$sock->SET_INFO("ForceDefaultTopBarrColor", $_POST["ForceDefaultTopBarrColor"]);
	$sock->SET_INFO("ForceDefaultButtonColor", $_POST["ForceDefaultButtonColor"]);
	
}

function index(){

	
	//$content=main_config(1);
	
	$html="
	
	<table style='width:100%'>
	<tr>
	<td width=1% valign='top'><img src='img/bg_perf.jpg'></td>
	<td valign='top'><div id='artica_perfomances_services_status'></div></td>
	</tr>
	</table>
	<table style='width:100%'>
	<tr>
		<td colspan=2 valign='top'><br>
		<p style='font-size:14px'>{about_perf}</p>
		</td>
	</tr>
	</table>
	<script>ArticaProcessesChargeLogs()</script>	
	
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
		
}



function cron_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{index_page_settings}');	
		$idmd='ArticaPerformancesSchedule_';
		
$html="	
function {$idmd}StartPage(){
		YahooWin2(550,'$page?cron-start=yes','$title');
		}
		
var x_SaveArticaProcessesSchedule= function (obj) {
				var results=obj.responseText;
				if(results.length>0){alert(results);}
				{$idmd}StartPage();
			}		
	
	function SaveArticaProcessesSchedule(){
		var XHR = new XHRConnection();
		XHR.appendData('PoolCoverPageSchedule',document.getElementById('PoolCoverPageSchedule').value);
		XHR.appendData('RTMMailSchedule',document.getElementById('RTMMailSchedule').value);
		document.getElementById('articaschedulesdiv').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>';
		XHR.sendAndLoad('$page', 'GET',x_SaveArticaProcessesSchedule);
	
	}		
	

			
{$idmd}StartPage()";
	
echo $html;	
	
}

function save_index_page(){
	$sock=new sockets();
	unset($_SESSION["EnableWebPageDebugging"]);
	$sock->SET_INFO("DisableWarnNotif",$_GET["DisableWarnNotif"]);
	$sock->SET_INFO("DisableJGrowl",$_GET["DisableJGrowl"]);
	$sock->SET_INFO("jgrowl_no_clamav_update",$_GET["jgrowl_no_clamav_update"]);
	$sock->SET_INFO("jgrowl_no_kas_update",$_GET["jgrowl_no_kas_update"]);
	$sock->SET_INFO("DisableFrontEndArticaEvents",$_GET["DisableFrontEndArticaEvents"]);
	$sock->SET_INFO("AllowShutDownByInterface",$_GET["AllowShutDownByInterface"]);
	$sock->SET_INFO("jGrowlMaxEvents",$_GET["jGrowlMaxEvents"]);
	$sock->SET_INFO("DisableNoOrganization", $_GET["DisableNoOrganization"]);
	$sock->SET_INFO("DisableAPTNews", $_GET["DisableAPTNews"]);
	$sock->SET_INFO("DisableWarningCalculation", $_GET["DisableWarningCalculation"]);
	$sock->SET_INFO("DisableFrontBrowseComputers", $_GET["DisableFrontBrowseComputers"]);
	$sock->SET_INFO("ArticaMetaRemoveIndex", $_GET["DisableFrontArticaMeta"]);
	$sock->SET_INFO("DisableJqueryDropDown", $_GET["DisableJqueryDropDown"]);
	$sock->SET_INFO("DisableFreeWebToolBox", $_GET["DisableFreeWebToolBox"]);
	$sock->SET_INFO("DisableTimeCapsuleToolBox", $_GET["DisableTimeCapsuleToolBox"]);
	$sock->SET_INFO("EnableWebPageDebugging", $_GET["EnableWebPageDebugging"]);
	$sock->SET_INFO("ArticaTabsTimeout", $_GET["ArticaTabsTimeout"]);
	$sock->SET_INFO("DisableSpecialCharacters", $_GET["DisableSpecialCharacters"]);
	$sock->SET_INFO("DenyMiniWebFromStandardPort", $_GET["DenyMiniWebFromStandardPort"]);
	
	if(isset($_GET["DoNotutf8EncodeJS"])){
		$sock->SET_INFO("DoNotutf8EncodeJS", $_GET["DoNotutf8EncodeJS"]);
		$_SESSION["DoNotutf8EncodeJS"]=$_GET["DoNotutf8EncodeJS"];
	}
	
	
	
	if(is_numeric($_GET["ArticaTabsTimeout"])){
		unset($_SESSION["build_artica_tabs_timeout"]);
		$_SESSION["build_artica_tabs_timeout"]=$_GET["ArticaTabsTimeout"];}
	
	unset($_SESSION["DisableJqueryDropDown"]);
	
	$sock->getFrameWork("cmd.php?refresh-frontend=yes");
	}

function cron_save(){
	$sock=new sockets();
	$sock->SET_INFO("PoolCoverPageSchedule",$_GET["PoolCoverPageSchedule"]);
	$sock->SET_INFO("RTMMailSchedule",$_GET["RTMMailSchedule"]);
	}

function cron_index(){
	$users=new usersMenus();
	$sock=new sockets();
	$page=CurrentPageName();
	$InterfaceFonts=$sock->GET_INFO("InterfaceFonts");
	if($InterfaceFonts==null){$InterfaceFonts="'Lucida Grande',Arial, Helvetica, sans-serif";}
	$DisableWarnNotif=$sock->GET_INFO("DisableWarnNotif");
	$DisableJGrowl=$sock->GET_INFO("DisableJGrowl");
	$jgrowl_no_clamav_update=$sock->GET_INFO("jgrowl_no_clamav_update");
	$DisableFrontEndArticaEvents=$sock->GET_INFO("DisableFrontEndArticaEvents");
	$jgrowl_no_kas_update=$sock->GET_INFO("jgrowl_no_kas_update");
	$AllowShutDownByInterface=$sock->GET_INFO('AllowShutDownByInterface');
	$jGrowlMaxEvents=$sock->GET_INFO('jGrowlMaxEvents');
	$DisableToolTips=$_COOKIE["DisableToolTips"];
	$DisableHelpToolTips=$_COOKIE["DisableHelpToolTips"];
	$DisableNoOrganization=$sock->GET_INFO('DisableNoOrganization');
	$DisableAPTNews=$sock->GET_INFO('DisableAPTNews');
	$DisableWarningCalculation=$sock->GET_INFO('DisableWarningCalculation');
	$DisableFrontBrowseComputers=$sock->GET_INFO('DisableFrontBrowseComputers');
	$DisableFrontArticaMeta=$sock->GET_INFO('ArticaMetaRemoveIndex');
	$DisableJqueryDropDown=$sock->GET_INFO('DisableJqueryDropDown');
	$DisableFreeWebToolBox=$sock->GET_INFO('DisableFreeWebToolBox');
	$DisableTimeCapsuleToolBox=$sock->GET_INFO('DisableTimeCapsuleToolBox');
	$EnableWebPageDebugging=$sock->GET_INFO("EnableWebPageDebugging");
	$ArticaTabsTimeout=$sock->GET_INFO("ArticaTabsTimeout");
	$DoNotutf8EncodeJS=$sock->GET_INFO("DoNotutf8EncodeJS");
	if(!is_numeric($ArticaTabsTimeout)){$ArticaTabsTimeout=800;}
	
	//no_organization
	if(!is_numeric($DoNotutf8EncodeJS)){$DoNotutf8EncodeJS=0;}
	if(!is_numeric($DisableWarnNotif)){$DisableWarnNotif=0;}
	if($DisableJGrowl==null){$DisableJGrowl=0;}
	if($jgrowl_no_clamav_update==null){$jgrowl_no_clamav_update=0;}
	if($DisableFrontEndArticaEvents==null){$DisableFrontEndArticaEvents=0;}
	if($AllowShutDownByInterface==null){$AllowShutDownByInterface=0;}
	if($ArticaInCgroups==null){$ArticaInCgroups=0;}
	if($DisableFreeWebToolBox==null){$DisableFreeWebToolBox=0;}
	if($DisableTimeCapsuleToolBox==null){$DisableTimeCapsuleToolBox=0;}
	
	if(!is_numeric($DisableJqueryDropDown)){$DisableJqueryDropDown=0;}
	
	
	
	$DoNotutf8EncodeJS=Field_checkbox_design("DoNotutf8EncodeJS",1,$DoNotutf8EncodeJS);
	$DisableWarnNotif=Field_checkbox_design("DisableWarnNotif",1,$DisableWarnNotif);
	$DisableJGrowl=Field_checkbox_design("DisableJGrowl",1,$DisableJGrowl);
	$jgrowl_no_clamav_update=Field_checkbox_design("jgrowl_no_clamav_update",1,$jgrowl_no_clamav_update);
	$DisableFrontEndArticaEvents=Field_checkbox_design("DisableFrontEndArticaEvents",1,$DisableFrontEndArticaEvents);
	$jgrowl_no_kas_update=Field_checkbox_design("jgrowl_no_kas_update",1,$jgrowl_no_kas_update);
	$AllowShutDownByInterface=Field_checkbox_design("AllowShutDownByInterface",1,$AllowShutDownByInterface);
	$DisableNoOrganization=Field_checkbox_design("DisableNoOrganization",1,$DisableNoOrganization);
	$DisableAPTNews=Field_checkbox_design("DisableAPTNews",1,$DisableAPTNews);
	$DisableWarningCalculation=Field_checkbox_design("DisableWarningCalculation",1,$DisableWarningCalculation);
	$DisableFrontBrowseComputers=Field_checkbox_design("DisableFrontBrowseComputers", 1,$DisableFrontBrowseComputers);
	$DisableFrontArticaMeta=Field_checkbox_design("DisableFrontArticaMeta", 1,$DisableFrontArticaMeta);
	$DisableJqueryDropDown=Field_checkbox_design("DisableJqueryDropDown", 1,$DisableJqueryDropDown);
	$DisableFreeWebToolBox=Field_checkbox_design("DisableFreeWebToolBox", 1,$DisableFreeWebToolBox);
	$DisableTimeCapsuleToolBox=Field_checkbox_design("DisableTimeCapsuleToolBox", 1,$DisableTimeCapsuleToolBox);
	$DenyMiniWebFromStandardPort=$sock->GET_INFO("DenyMiniWebFromStandardPort");
	$DisableSpecialCharacters=$sock->GET_INFO("DisableSpecialCharacters");
	if(!is_numeric($EnableWebPageDebugging)){$EnableWebPageDebugging=0;}
	
	$ForceDefaultGreenColor=$sock->GET_INFO("ForceDefaultGreenColor");
	$ForceDefaultTopBarrColor=$sock->GET_INFO("ForceDefaultTopBarrColor");
	$ForceDefaultButtonColor=$sock->GET_INFO("ForceDefaultButtonColor");
	
	
	if($ForceDefaultGreenColor==null){$ForceDefaultGreenColor="005447";}
	if($ForceDefaultTopBarrColor==null){$ForceDefaultTopBarrColor="005447";}
	if($ForceDefaultButtonColor==null){$ForceDefaultButtonColor="5CB85C";}
	
	
	
	if(!is_numeric($DenyMiniWebFromStandardPort)){$DenyMiniWebFromStandardPort=0;}
	if(!is_numeric($DisableSpecialCharacters)){$DisableSpecialCharacters=0;}
	
	
	if($jGrowlMaxEvents==null){$jGrowlMaxEvents=50;}

	$jgrowl_no_kas_update="	<tr>
		<td class=legend style='font-size:22px'>{jgrowl_no_kas_update}:</td>
		<td valign='top'>$jgrowl_no_kas_update</tD>
	</tr>
	<tr><td colspan=2 style='border-top:1px solid #005447'>&nbsp;</td></tr>";
	
	if(!$users->kas_installed){
		$jgrowl_no_kas_update=null;
	}
	
	
	$noclamav="	<tr>
		<td class=legend style='font-size:22px'>{jgrowl_no_clamav_update}:</td>
		<td>$jgrowl_no_clamav_update</tD>
	</tr>
	<tr><td colspan=2 style='border-top:1px solid #005447'>&nbsp;</td></tr>";
	
	
	if($users->KASPERSKY_SMTP_APPLIANCE){
		$noclamav=null;
	}
	$t=time();
	$html="
	<div style='font-size:26px'>{frontend_disables_options_explain}</div>
	<div id='articaschedulesdiv'></div>
	<div id='$t'></div>
	<div style='width:98%' class=form>	
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>font:</td>
		<td valign='top'>".Field_text("InterfaceFonts",$InterfaceFonts,"font-size:22px;width:99%")."</tD>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{background_color}:</td>
		<td valign='top'>".Field_ColorPicker("ForceDefaultGreenColor",$ForceDefaultGreenColor,"font-size:22px;width:200px")."</tD>
	</tr>

				
	<tr>
		<td class=legend style='font-size:22px'>{top_barr_color}:</td>
		<td valign='top'>".Field_ColorPicker("ForceDefaultTopBarrColor",$ForceDefaultTopBarrColor,"font-size:22px;width:200px")."</tD>
	</tr>				
	<tr>
		<td class=legend style='font-size:22px'>{button_color}:</td>
		<td valign='top'>".Field_ColorPicker("ForceDefaultButtonColor",$ForceDefaultButtonColor,"font-size:22px;width:200px")."</tD>
	</tr>				
				
	<tr>			
	<td colspan=2 align='right'>
			<hr>". button("{apply}","SaveArticaIndexPage2()",28)."
				
		</td>
	</tr>	
	</table>
	</div>
	
<div style='width:98%' class=form>	
<table style='width:100%'>
<tr>
		<td class=legend style='font-size:22px'>{EnableWebPageDebugging}:</td>
		<td valign='top'>". Field_checkbox_design("EnableWebPageDebugging", 1,$EnableWebPageDebugging)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{disable}:{icon_artica_events_front_end}:</td>
		<td valign='top'>$DisableFrontEndArticaEvents</tD>
	</tr>
	
	<tr>
		<td class=legend style='font-size:22px'>{DoNotutf8EncodeJS}:</td>
		<td valign='top'>$DoNotutf8EncodeJS</tD>
	</tr>	
	
	<tr>
		<td class=legend style='font-size:22px'>{disable_jgrowl}:</td>
		<td valign='top'>$DisableJGrowl</tD>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{DisableJqueryDropDown}:</td>
		<td valign='top'>$DisableJqueryDropDown</tD>
			
	<tr>
		<td class=legend style='font-size:22px'>{disable}:{no_organization}:</td>
		<td valign='top'>$DisableNoOrganization</tD>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{DisableAPTNews}:</td>
		<td valign='top'>$DisableAPTNews</tD>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{DisableWarningCalculation}:</td>
		<td valign='top'>$DisableWarningCalculation</tD>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{disable}:{browse_computers}:</td>
		<td valign='top'>$DisableFrontBrowseComputers</tD>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{DisableSpecialCharacters}:</td>
		<td valign='top'>". Field_checkbox_design("DisableSpecialCharacters", 1,$DisableSpecialCharacters)."</td>
	</tr>
	
	<tr>
		<td class=legend style='font-size:22px'>{disable}:{meta-console}:</td>
		<td valign='top'>$DisableFrontArticaMeta</tD>
	</tr>		
	<tr>
		<td class=legend style='font-size:22px'>{deny_access_from_the_standard_port}:</td>
		<td>". Field_checkbox_design("DenyMiniWebFromStandardPort", 1,$DenyMiniWebFromStandardPort,"DenyMiniWebFromStandardPortCheck()")."</td>
	</tr>		
		 
	<tr>
		<td class=legend style='font-size:22px'>{ArticaTabsTimeout}:</td>
		<td valign='top' style='font-size:22px'>". Field_text("ArticaTabsTimeout",$ArticaTabsTimeout,"font-size:22px;width:110px")."&nbsp;Ms</tD>
	</tr>				
	<tr>
		<td class=legend style='font-size:22px'>{jGrowlMaxEvents}:</td>
		<td valign='top'>". Field_text("jGrowlMaxEvents",$jGrowlMaxEvents,"font-size:22px;width:110px")."</tD>
	</tr>	
	
$noclamav
$jgrowl_no_kas_update	
	
	<tr>
		<td class=legend style='font-size:22px'>{enable_shutdown_interface}:</td>
		<td valign='top'>$AllowShutDownByInterface</tD>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{disable_tooltips}:</td>
		<td valign='top'>".Field_checkbox_design("DisableToolTips",1,$DisableToolTips,"DisableToolTipsSave()")."</tD>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{disable_help_tooltips}:</td>
		<td valign='top'>".Field_checkbox_design("DisableHelpToolTips",1,$DisableHelpToolTips,"DisableToolTipsSave()")."</tD>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{disable_freewebs_toolbox}:</td>
		<td valign='top'>$DisableFreeWebToolBox</tD>
	</tr>		
	<tr>
		<td class=legend style='font-size:22px'>{disable_TimeCapsule_toolbox}:</td>
		<td valign='top'>$DisableTimeCapsuleToolBox</tD>
	</tr>	
	
	<tr>			
	<td colspan=2 align='right'>
			<hr>". button("{apply}","SaveArticaIndexPage$t()",36)."
				
		</td>
	</tr>
</table>
</div>


<script>
	function DisableToolTipsSave(){
		var DisableToolTips=0;
		var DisableHelpToolTips=0;
		if(document.getElementById('DisableToolTips').checked){ DisableToolTips=1;}
		if(document.getElementById('DisableHelpToolTips').checked){ DisableHelpToolTips=1;}
		Set_Cookie('DisableToolTips', DisableToolTips, '3600', '/', '', '');
		Set_Cookie('DisableHelpToolTips', DisableHelpToolTips, '3600', '/', '', '');
		CacheOff();
	
	}
	
	var x_SaveArticaIndexPage2= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		document.getElementById('$t').innerHTML='';
		reloadStylesheets();
	}	

	
	var xSaveArticaIndexPage$t=function (obj) {
		var results=obj.responseText;
		document.getElementById('$t').innerHTML='';
		if(results.length>0){alert(results);}
		CacheOff();
	}	
	
	 
	function SaveArticaIndexPage2(){
		var XHR = new XHRConnection();
		XHR.appendData('InterfaceFonts',document.getElementById('InterfaceFonts').value);
		XHR.appendData('ForceDefaultButtonColor',document.getElementById('ForceDefaultButtonColor').value);
		XHR.appendData('ForceDefaultGreenColor',document.getElementById('ForceDefaultGreenColor').value);
		XHR.appendData('ForceDefaultTopBarrColor',document.getElementById('ForceDefaultTopBarrColor').value);
		XHR.sendAndLoad('$page', 'POST',x_SaveArticaIndexPage2);
		
	}

function SaveArticaIndexPage$t(){
	var XHR = new XHRConnection();
	
	if(document.getElementById('DisableWarnNotif')){
		if(document.getElementById('DisableWarnNotif').checked){XHR.appendData('DisableWarnNotif',1);}else{XHR.appendData('DisableWarnNotif',0);}
	}
	if(document.getElementById('DisableJGrowl').checked){XHR.appendData('DisableJGrowl',1);}else{XHR.appendData('DisableJGrowl',0);}
	if(document.getElementById('DisableFrontEndArticaEvents').checked){XHR.appendData('DisableFrontEndArticaEvents',1);}else{XHR.appendData('DisableFrontEndArticaEvents',0);}
	if(document.getElementById('AllowShutDownByInterface').checked){XHR.appendData('AllowShutDownByInterface',1);}else{XHR.appendData('AllowShutDownByInterface',0);}	
	if(document.getElementById('DisableNoOrganization').checked){XHR.appendData('DisableNoOrganization',1);}else{XHR.appendData('DisableNoOrganization',0);}
	if(document.getElementById('DisableAPTNews').checked){XHR.appendData('DisableAPTNews',1);}else{XHR.appendData('DisableAPTNews',0);}
	if(document.getElementById('DisableWarningCalculation').checked){XHR.appendData('DisableWarningCalculation',1);}else{XHR.appendData('DisableWarningCalculation',0);}
	if(document.getElementById('DisableFrontBrowseComputers').checked){XHR.appendData('DisableFrontBrowseComputers',1);}else{XHR.appendData('DisableFrontBrowseComputers',0);}
	if(document.getElementById('DisableFrontArticaMeta').checked){XHR.appendData('DisableFrontArticaMeta',1);}else{XHR.appendData('DisableFrontArticaMeta',0);}
	if(document.getElementById('DisableJqueryDropDown').checked){XHR.appendData('DisableJqueryDropDown',1);}else{XHR.appendData('DisableJqueryDropDown',0);}
	if(document.getElementById('DisableTimeCapsuleToolBox').checked){XHR.appendData('DisableTimeCapsuleToolBox',1);}else{XHR.appendData('DisableTimeCapsuleToolBox',0);}
	if(document.getElementById('DisableFreeWebToolBox').checked){XHR.appendData('DisableFreeWebToolBox',1);}else{XHR.appendData('DisableFreeWebToolBox',0);}
	if(document.getElementById('EnableWebPageDebugging').checked){XHR.appendData('EnableWebPageDebugging',1);}else{XHR.appendData('EnableWebPageDebugging',0);}
	if(document.getElementById('DisableSpecialCharacters').checked){XHR.appendData('DisableSpecialCharacters',1);}else{XHR.appendData('DisableSpecialCharacters',0);}
	if(document.getElementById('DenyMiniWebFromStandardPort').checked){XHR.appendData('DenyMiniWebFromStandardPort',1);}else{XHR.appendData('DenyMiniWebFromStandardPort',0);}
	if(document.getElementById('DoNotutf8EncodeJS').checked){XHR.appendData('DoNotutf8EncodeJS',1);}else{XHR.appendData('DoNotutf8EncodeJS',0);}
	
	
	
	
	AnimateDiv('$t');

	

	
	if(document.getElementById('jgrowl_no_kas_update')){
		if(document.getElementById('jgrowl_no_kas_update').checked){
			XHR.appendData('jgrowl_no_kas_update',1);}else{
			XHR.appendData('jgrowl_no_kas_update',0);
		}
	}
	
	if(document.getElementById('jgrowl_no_clamav_update')){	
			if(document.getElementById('jgrowl_no_clamav_update').checked){
			XHR.appendData('jgrowl_no_clamav_update',1);}
			else{XHR.appendData('jgrowl_no_clamav_update',0);
			}
	}
	
	XHR.appendData('ArticaTabsTimeout',document.getElementById('ArticaTabsTimeout').value);
	XHR.appendData('jGrowlMaxEvents',document.getElementById('jGrowlMaxEvents').value);
	XHR.sendAndLoad('$page', 'GET',xSaveArticaIndexPage$t);
	
}
	
</script>


";
	
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html,"RTMMailConfig.php");	
}

function cron_start(){
	$array["cron-index-page"]="{index_page_settings}";
	$array["cron-popup"]="{ARTICA_PROCESS_SCHEDULE}";
	$array["cron-logon"]="{LOGON_PAGE}";
	
	if(function_exists("apc_cache_info")){
		$array["cron-apc"]="{APP_PHP_APC}";
	}
	
	$page=CurrentPageName();
	$tpl=new templates();
	while (list ($num, $ligne) = each ($array) ){
		
		$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:14px'><a href=\"$page?$num=yes\"><span>$ligne</span></a></li>\n");
		}
	
echo build_artica_tabs($html, "admin_index_settings");
	
}


function cron_popup(){
	
	
	for($i=2;$i<380;$i++){
		$Cover[$i]=$i;
		
	}
	$sock=new sockets();
	$PoolCoverPageSchedule=$sock->GET_INFO('PoolCoverPageSchedule');
	if($PoolCoverPageSchedule==null){$PoolCoverPageSchedule=20;}
	$PoolCoverPageSchedule=Field_array_Hash($Cover,'PoolCoverPageSchedule',$PoolCoverPageSchedule);
	
	$RTMMailSchedule=$sock->GET_INFO('RTMMailSchedule');
	if($RTMMailSchedule==null){$RTMMailSchedule=35;}
	$RTMMailSchedule=Field_array_Hash($Cover,'RTMMailSchedule',$RTMMailSchedule);	
	
	
	
	
	$html="
	<table style='width:100%'>
	<tr>
	<td valign='top' width=1%><img src='img/cron-128.png'></td>
	<td valign='top'>
	<div class=explain style='font-size:14px'>{ARTICA_PROCESS_SCHEDULE_EXPLAIN}</div>
	<div id='articaschedulesdiv' style='width:98%' class=form>
			<table style='width:100%'>
				<tr>
					<td class=legend style='font-size:16px'>{ADMIN_COVER_PAGE_STATUS}:</td>
					<td>$PoolCoverPageSchedule&nbsp;mn</tD>
				</tr>
				<tr>
					<td class=legend style='font-size:16px'>{RTMMail}:</td>
					<td>$RTMMailSchedule&nbsp;mn</tD>
				</tr>
				<tr>
					<td colspan=2 align='right'>
						<hr>". button("{apply}","SaveArticaProcessesSchedule()",16)."
							
					</td>
				</tr>
			</table>
		</div>	   
</td>
</tr>
</table>
";
	
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html,"RTMMailConfig.php");
}
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{tune_title}');
	$title_mysql=$tpl->_ENGINE_parse_body('{service_performances}');
	$idmd='ArticaPerformancesIndex_';
	
$html="var {$idmd}timerID  = null;
var {$idmd}timerID1  = null;
var {$idmd}tant=0;
var {$idmd}reste=0;

	function {$idmd}demarre(){
		if(!YahooWinOpen()){return false;}
		{$idmd}tant = {$idmd}tant+1;
		{$idmd}reste=10-{$idmd}tant;
		if ({$idmd}tant < 10 ) {                           
			{$idmd}timerID = setTimeout(\"{$idmd}demarre()\",3000);
	      } else {
			{$idmd}tant = 0;
			{$idmd}ChargeLogs();
			{$idmd}demarre();                                
	   }
	}
	
	
	function {$idmd}StartPage(){
		YahooWin(850,'$page?popup=yes','$title');
		setTimeout(\"{$idmd}ChargeLogs();\",1000);	
		setTimeout(\"{$idmd}demarre()\",1000);
	}	


	function {$idmd}ChargeLogs(){
		LoadAjax('artica_perfomances_services_status','$page?status=yes');
	}

	function ArticaProcessesChargeLogs(){
		{$idmd}ChargeLogs();
		setTimeout(\"{$idmd}demarre()\",1000);
	}
	
	function refresh_services(){
		{$idmd}ChargeLogs();
	}
	

	
	
function LoadAjaxLocal(ID,uri) {
		var XHR = new XHRConnection();
		XHR.setRefreshArea(ID);
		xID=ID;
		document.getElementById(ID).innerHTML='<center style=\"margin:20px;padding:20px\"><img src=\"img/wait.gif\"></center>';
		XHR.sendAndLoad(uri,\"GET\",x_ajax);
}

var x_ajax= function (obj) {
	var tempvalue=obj.responseText;
	document.getElementById(xID).innerHTML=tempvalue;
	StartSlider();
}

function setSliderVal(value){
document.getElementById('v').value=value;
ChargeLogs();
}
//0=>'{select}',1=>high,2=>medium,3=>low,4=>very_low

function mimedefang_macro(){
	var macro=document.getElementById('mimedefang_macro').value;
	if(macro=='1'){
		document.getElementById('MX_REQUESTS').value=1000;
		document.getElementById('MX_MINIMUM').value=5;
		document.getElementById('MX_MAXIMUM').value=50;		
		document.getElementById('MX_MAX_RSS').value=100000;				
		document.getElementById('MX_MAX_AS').value=300000;						
		}
	
	if(macro=='2'){
		document.getElementById('MX_REQUESTS').value=200;
		document.getElementById('MX_MINIMUM').value=2;
		document.getElementById('MX_MAXIMUM').value=10;		
		document.getElementById('MX_MAX_RSS').value=100000;				
		document.getElementById('MX_MAX_AS').value=500000;						
		}	

	if(macro=='3'){
		document.getElementById('MX_REQUESTS').value=100;
		document.getElementById('MX_MINIMUM').value=2;
		document.getElementById('MX_MAXIMUM').value=5;		
		document.getElementById('MX_MAX_RSS').value=100000;				
		document.getElementById('MX_MAX_AS').value=200000;						
		}
	if(macro=='4'){
		document.getElementById('MX_REQUESTS').value=50;
		document.getElementById('MX_MINIMUM').value=1;
		document.getElementById('MX_MAXIMUM').value=2;		
		document.getElementById('MX_MAX_RSS').value=90000;				
		document.getElementById('MX_MAX_AS').value=150000;						
		}				
}

{$idmd}StartPage();
	
";	
	
echo $html;

}

function main_tabs(){
	
	$page=CurrentPageName();
	
	$array["artica_process"]='{artica_process}';
	$array["optimize"]='{optimization}';
	//$array["cgroups"]='{APP_CGROUPS}';
	$style="style='font-size:18px'";
	
	while (list ($num, $ligne) = each ($array) ){
		if($num=="optimize"){$html[]= "<li><a href=\"artica.optimize.php\"><span $style>$ligne</span></a></li>\n";continue;}
		if($num=="cgroups"){$html[]= "<li><a href=\"cgroups.php\"><span $style>$ligne</span></a></li>\n";continue;}
		$html[]= "<li><a href=\"$page?main=$num&hostname=$hostname\"><span $style>$ligne</span></a></li>\n";
		
		}
	
	
	return build_artica_tabs($html, "main_config_articaproc");
}


	
	
function main_page(){
	

	
	$html=
	"<table style='width:100%'>
	<tr>
	<td width=1% valign='top'><img src='img/bg_perf.jpg'>	<p class=caption>{about_perf}</p></td>
	<td valign='top'><div id='services_status'></div></td>
	</tr>
	<tr>
		<td colspan=2 valign='top'><br>
			<div id='main_config'></div>
		</td>
	</tr>
	</table>
	<script>demarre();ChargeLogs();LoadAjaxLocal('main_config','$page?main=yes');</script>
	
	";
	//slider-thumb
	$tpl=new template_users('{tune_title}',$html,0,0,0,0,$cfg);
	echo $tpl->web_page;
	}


function main_switch(){
	
	switch ($_GET["main"]) {
		case "index":index();exit;break;
		case "artica_process":main_config();exit;break;
		default:
			break;
	}
	
	
}	

function main_config($return=0){
	$users=new usersMenus();
	$html=
	main_config_artica().
	main_warn_preload().
	main_config_syslogng().main_config_mimedefang();

	

	if($return==1){return $html;}

	echo $html;
	
}
	
function main_config_artica(){
	//ArticaPerformancesSettings
	$sock=new sockets();
	$users=new usersMenus();
	$page=CurrentPageName();
	
	$MaxtimeBackupMailSizeCalculate=trim($sock->GET_INFO("MaxtimeBackupMailSizeCalculate"));
	$systemForkProcessesNumber=intval($sock->GET_INFO("systemForkProcessesNumber"));
	if($systemForkProcessesNumber==0){$systemForkProcessesNumber=4;}
	$cpulimit=trim($sock->GET_INFO("cpulimit"));
	$cpuLimitEnabled=trim($sock->GET_INFO("cpuLimitEnabled"));
	$SystemV5CacheEnabled=trim($sock->GET_INFO("SystemV5CacheEnabled"));
	
	
	
	
	$systemMaxOverloaded=trim($sock->GET_INFO("systemMaxOverloaded"));
	
	$SystemLoadNotif=trim($sock->GET_INFO("SystemLoadNotif"));
	
	$DisableLoadAVGQueue=$sock->GET_INFO('DisableLoadAVGQueue');
	$oom_kill_allocating_task=$sock->GET_INFO("oom_kill_allocating_task");
	$SysTmpDir=$sock->GET_INFO("SysTmpDir");
	if($SysTmpDir==null){$SysTmpDir="/home/artica/tmp";}
	
	$cgroupsEnabled=$sock->GET_INFO("cgroupsEnabled");
	$CGROUPS_INSTALLED=0;
	if($users->CGROUPS_INSTALLED){$CGROUPS_INSTALLED=1;}
	

	if(strlen(trim($SystemV5CacheEnabled))==0){$SystemV5CacheEnabled=0;}
	
	
	if(!is_numeric($cpuLimitEnabled)){$sock->SET_INFO("cpuLimitEnabled",0);$cpuLimitEnabled=0;}
	
	if(!is_numeric($MaxtimeBackupMailSizeCalculate)){$MaxtimeBackupMailSizeCalculate=300;}
	if(!is_numeric($cpulimit)){$cpulimit=0;}
	if(!is_numeric($cgroupsEnabled)){$cgroupsEnabled=0;}
	if(!is_numeric($SystemLoadNotif)){$SystemLoadNotif=0;}
	if(!is_numeric($oom_kill_allocating_task)){$oom_kill_allocating_task=1;}
	
	if(!is_numeric($DisableLoadAVGQueue)){$DisableLoadAVGQueue=0;}
	


if($users->POSTFIX_INSTALLED){
	$backupmailsize="<tr>
	<td nowrap width=1% align='right' class=legend style='font-size:22px'>{MaxtimeBackupMailSizeCalculate}:</td>
	<td nowrap>". Field_text("MaxtimeBackupMailSizeCalculate",$MaxtimeBackupMailSizeCalculate,"width:110px;font-size:22px;padding:3px")."&nbsp;{minutes}</td>
	<td>" . help_icon("{MaxtimeBackupMailSizeCalculate_explain}")."</td>
</tr>";
}

$ini=new Bs_IniHandler();


$ini->loadString($sock->GET_INFO("ArticaPerformancesSettings"));
$page=CurrentPageName();

$arrp=array(10=>"{default}",-15=>"{high}",10=>"{medium}",12=>"{low}",19=>'{very_low}');
$cpulimit_array=array(
	0=>"{no_limit}",
	10=>"10%",
	20=>"20%",
	30=>"30%",
	35=>"35%",
	40=>"40%",
	45=>"45%",
	50=>"50%",
	55=>"55%",
	60=>"60%",
	65=>"65%",
	70=>"70%",
	75=>"75%",
	80=>"80%",
	85=>"85%",
	90=>"90%",
	95=>"95%",		
);

$SystemLoadNotif_array=array(
	0=>"{disabled}",
	1=>1,
	2=>2,
	3=>3,
	4=>4,
	5=>5,
	6=>6,
	7=>7,
	8=>8,
	10=>10,
	20=>20,
	30=>30,
	100=>100
);


$arrp=Field_array_Hash($arrp,'ProcessNice',$ini->_params["PERFORMANCES"]["ProcessNice"],null,null,0,"font-size:22px");
$cpulimit_f=Field_array_Hash($cpulimit_array,'cpulimit',$cpulimit,null,null,0,"font-size:22px");


	$arrp_mysql=array(null=>"{default}",0=>"{ISP_MODE}",1=>"{high}",2=>"{medium}",3=>"{low}",4=>'{very_low}');
	$mysql_nice=Field_array_Hash($arrp_mysql,'MysqlNice',$ini->_params["PERFORMANCES"]["MysqlNice"],null,null,0,"font-size:22px");
	$mysql_nice="		<tr>
			<td nowrap width=1% align='right' class=legend style='font-size:22px'>{mysql_server_consumption}:</td>
			<td>$mysql_nice</td>
			<td>" . help_icon("{mysql_server_text}")."</td>
		</tr>";
	$mysql_nice="<input type='hidden' id='MysqlNice' name='MysqlNice' value=''>";


if($ini->_params["PERFORMANCES"]["NoBootWithoutIP"]==null){$ini->_params["PERFORMANCES"]["NoBootWithoutIP"]=0;}
if($ini->_params["PERFORMANCES"]["useIonice"]==null){$ini->_params["PERFORMANCES"]["useIonice"]=1;}
$icon_schedule=Buildicon64("DEF_ICO_ARTICA_CRON_SCHEDULE");
$icon_phlisight=Paragraphe("philesight-64.png","{APP_PHILESIGHT}","{APP_PHILESIGHT_PARAMETERS}","javascript:Loadjs('philesight.php?js-settings=yes')");
$MaxMailEventsLogs=$sock->GET_INFO("MaxMailEventsLogs");	
if($MaxMailEventsLogs==null){$MaxMailEventsLogs=400000;}
if($MaxMailEventsLogs<100){$MaxMailEventsLogs=4000;}

$t=time();

$html="
<div id=ffm1 style='width:98%' class=form>
<table style='width:100%'>
		<tr>
			<td nowrap width=1% align='right' class=legend style='font-size:22px'>{artica_process}:</td>
			<td>$arrp</td>
			<td>" . help_icon("{artica_process_explain}")."</td>
		</tr>

			
		<tr>
			<td nowrap width=1% align='right' class=legend style='font-size:22px'>{cpuLimitEnabled}:</td>
			<td>" . Field_checkbox_design("cpuLimitEnabled",1,$cpuLimitEnabled,"CheckCPULimit()")."</td>
			<td>" . help_icon("{cpuLimitEnabled_explain}")."</td>
		</tr>			
		
		<tr>
			<td nowrap width=1% align='right' class=legend style='font-size:22px'>{cpulimit}:</td>
			<td>$cpulimit_f</td>
			<td>" . help_icon("{artica_cpulimit_explain}")."</td>
		</tr>
		<tr>
			<td nowrap width=1% align='right' class=legend style='font-size:22px'>{SystemLoadNotif}:</td>
			<td>" . Field_array_Hash($SystemLoadNotif_array,"SystemLoadNotif",$SystemLoadNotif,null,null,0,"font-size:22px")."</td>
			<td>&nbsp;</td>
		</tr>			
		
		<tr>
			<td nowrap width=1% align='right' class=legend style='font-size:22px'>{systemMaxOverloaded}:</td>
			<td nowrap style='font-size:22px'>". Field_text("systemMaxOverloaded",
					$systemMaxOverloaded,"width:110px;font-size:22px;padding:3px")."&nbsp;{load}</td>
			<td>" . help_icon("{systemMaxOverloaded_explain}")."</td>
		</tr>
		<tr>
			<td nowrap width=1% align='right' class=legend style='font-size:22px'>{systemForkProcessesNumber}:</td>
			<td nowrap style='font-size:22px'>". Field_text("systemForkProcessesNumber",
					$systemForkProcessesNumber,"width:110px;font-size:22px;padding:3px")."&nbsp;{processes}</td>
			<td>" . help_icon("{systemForkProcessesNumber_explain}")."</td>
		</tr>		
		$mysql_nice

		
		<tr>
			<td nowrap width=1% align='right' class=legend style='font-size:22px'>{SystemV5CacheEnabled}:</td>
			<td>" . Field_checkbox_design("SystemV5CacheEnabled",1,$SystemV5CacheEnabled)."</td>
			<td>" . help_icon("{SystemV5CacheEnabled_explain}")."</td>
		</tr>	
		
				
		<tr>
			<td nowrap width=1% align='right' class=legend style='font-size:22px'>{useIonice}:</td>
			<td>" . Field_checkbox_design("useIonice",1,$ini->_params["PERFORMANCES"]["useIonice"])."</td>
			<td>" . help_icon("{useIonice_explain}")."</td>
		</tr>
		
		<tr>
			<td nowrap width=1% align='right' class=legend style='font-size:22px'>{NoBootWithoutIP}:</td>
			<td>" . Field_checkbox_design("NoBootWithoutIP",1,$ini->_params["PERFORMANCES"]["NoBootWithoutIP"])."</td>
			<td>" . help_icon("{NoBootWithoutIP_explain}")."</td>
		</tr>
		<tr>
			<td nowrap width=1% align='right' class=legend style='font-size:22px'>{DisableFollowServiceHigerThan1G}:</td>
			<td>" . Field_checkbox_design("DisableFollowServiceHigerThan1G",1,$ini->_params["PERFORMANCES"]["DisableFollowServiceHigerThan1G"])."</td>
			<td>" . help_icon("{DisableFollowServiceHigerThan1G_explain}")."</td>
		</tr>
		<tr>
			<td nowrap width=1% align='right' class=legend style='font-size:22px'>{EnableArticaWatchDog}:</td>
			<td>" . Field_checkbox_design("EnableArticaWatchDog",1,$sock->GET_INFO('EnableArticaWatchDog'))."</td>
			<td>" . help_icon("{EnableArticaWatchDog_explain}")."</td>
		</tr>
		<tr>
			<td nowrap width=1% align='right' class=legend style='font-size:22px'>{DisableLoadAVGQueue}:</td>
			<td>" . Field_checkbox_design("DisableLoadAVGQueue",1,$DisableLoadAVGQueue)."</td>
			<td>" . help_icon("{DisableLoadAVGQueue_explain}")."</td>
		</tr>	
		<tr>
			<td nowrap width=1% align='right' class=legend style='font-size:22px'>{oom_kill_allocating_task}:</td>
			<td>" . Field_checkbox_design("oom_kill_allocating_task",1,$oom_kill_allocating_task)."</td>
			<td>
		</tr>

			
		$backupmailsize
		<tr>
			<td nowrap width=1% align='right' class=legend style='font-size:22px'>{MaxEventsInDatabase} (mail):</td>
			<td nowrap>". Field_text("MaxMailEventsLogs",$MaxMailEventsLogs,"width:150px;font-size:22px;padding:3px")."</td>
			<td></td>
		</tr>	
		<tr>
			<td nowrap width=1% align='right' class=legend style='font-size:22px'>{temp_dir}:</td>
			<td nowrap>". Field_text("SysTmpDir",$SysTmpDir,"width:320px;font-size:22px;padding:3px")."</td>
			<td></td>
		</tr>	
			<td colspan=3 align='right'><hr>". button("{apply}","SavePerformancesMasterForm()",34)."</td>
		</tr>
		</tbody>
		</table>
		</form>
</div>
		<div id='$t-reboot'></div>	

		



<script>
	var x_SavePerformancesMasterForm= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		RefreshTab('main_config_articaproc');
	}


	function SavePerformancesMasterForm(){
		var XHR=XHRParseElements('ffm1');
		XHR.sendAndLoad('$page', 'POST',x_SavePerformancesMasterForm);
	
	}




	
	
	function CheckCPULimit(){
		document.getElementById('cpulimit').disabled=true;
		if(document.getElementById('cpuLimitEnabled').disabled){return;}
		if(document.getElementById('cpuLimitEnabled').checked){
			document.getElementById('cpulimit').disabled=false;
		}
	}
	
	function LoadRebootSection(){
		LoadAjax('$t-reboot','artica.performances.reboot.php?t=$t');
	
	}
	

		
	


CheckCPULimit();
LoadRebootSection();

</script>

";
$tpl=new templates();
return  $tpl->_ENGINE_parse_body($html);
	
}


function main_config_mysql(){
	$ini=new Bs_IniHandler();
	$page=CurrentPageName();
	$sock=new sockets();
	$ini->loadString($sock->GET_INFO("ArticaPerformancesSettings"));
		
$tpl=new templates();
$title_perfs=$tpl->_ENGINE_parse_body('{service_performances}');
	$testperfs="javascript:YahooWin3(400,'artica.performances.php?MysqlTestsPerfs=yes','$title_perfs');";
	$users=new usersMenus();
	if(!$users->mysql_installed){return "no";}
	$arrp=array(null=>"{default}",0=>"{ISP_MODE}",1=>"{high}",2=>"{medium}",3=>"{low}",4=>'{very_low}');
	$arrp=Field_array_Hash($arrp,'MysqlNice',$ini->_params["PERFORMANCES"]["MysqlNice"]);
	$html="<H5>{mysql_server_consumption}</h5>
	<p class=caption>{mysql_server_text}</p>
<form name=ffmsql>
<table style='width:99%' class=form>
<tr>
	<td nowrap width=1% align='right'><strong>{mysql_server_consumption}:</strong></td>
	<td>$arrp</td>
	<td>" . help_icon("{mysql_server_text}")."</td>
</tr>
<tr>
	<td colspan=3 align='right'>". button("{apply}","ParseForm('ffmsql','$page',true);")."
</tr>
<tr>
	<td colspan=3 align='right'>". button("{service_performances}",$testperfs)."
</tr>
</table>
</form>	
	
	";
	
$tpl=new templates();
return $tpl->_ENGINE_parse_body($html);
	
}


function main_warn_preload(){
	return null;
	$users=new usersMenus();
	if($users->preload_installed){return null;}
	$html="<H5>{APP_PRELOAD_NOTINSTALLED}</h5>
	<p class=caption>{APP_PRELOAD_NOTINSTALLED_TEXT}</p>";
	
$tpl=new templates();
return "<div style='float:left;margin:4px;width:300px'>".RoundedLightGrey($tpl->_ENGINE_parse_body($html))."</div>";		
	
}

function main_config_syslogng(){
	$users=new usersMenus();
	if(!$users->syslogng_installed){return null;}
	$arrp=array(null=>'{select}',1=>"{all}",2=>"{only_mail}",3=>"{only_errors}",4=>'{no_sql_injection}');
	$page=CurrentPageName();

	$sock=new sockets();
	$performances=$sock->GET_INFO("ArticaPerformancesSettings");
	$ini=new Bs_IniHandler();
	$ini->loadString($performances);		
if($ini->_params["PERFORMANCES"]["SyslogNgPref"]==null){$ini->_params["PERFORMANCES"]["SyslogNgPref"]=1;}
if($ini->_params["PERFORMANCES"]["syslogng_log_fifo_size"]==null){$ini->_params["PERFORMANCES"]["syslogng_log_fifo_size"]=2048;}
if($ini->_params["PERFORMANCES"]["syslogng_sync"]==null){$ini->_params["PERFORMANCES"]["syslogng_sync"]=0;}
if($ini->_params["PERFORMANCES"]["syslogng_max_connections"]==null){$ini->_params["PERFORMANCES"]["syslogng_max_connections"]=50;}



	$arrp=Field_array_Hash($arrp,'SyslogNgPref',$ini->_params["PERFORMANCES"]["SyslogNgPref"]);
	$html="<H5>{syslog_server_consumption}</h5>
	<p class=caption>{syslog_server_consumption_text}</p>
<form name=ffmsyslog>
<table style='width:99%' class=form>
<tr>
	<td nowrap width=1% align='right'><strong>{syslog_server_consumption}:</strong></td>
	<td>$arrp</td>
	<td>" . help_icon('{syslogng_intro}')."</td>
</tr>
<tr>
	<td nowrap width=1% align='right'><strong>{log_fifo_size}:</strong></td>
	<td>" . Field_text('syslogng_log_fifo_size',$ini->_params["PERFORMANCES"]["syslogng_log_fifo_size"])."</td>
	<td>" . help_icon('{log_fifo_size_text}')."</td>
</tr>
<tr>
	<td nowrap width=1% align='right'><strong>{syslogng_sync}:</strong></td>
	<td>" . Field_text('syslogng_sync',$ini->_params["PERFORMANCES"]["syslogng_sync"])."</td>
	<td>" . help_icon('{syslogng_sync_text}')."</td>
</tr>
<tr>
	<td nowrap width=1% align='right'><strong>{syslogng_max_connections}:</strong></td>
	<td>" . Field_text('syslogng_max_connections',$ini->_params["PERFORMANCES"]["syslogng_max_connections"])."</td>
	<td>" . help_icon('{syslogng_max_connections_text}')."</td>
</tr>


<tr>
	<td colspan=2 align='right'><input type=button OnClick=\"javascript:ParseForm('ffmsyslog','$page',true);\" value='{apply}&nbsp;&raquo;'>
</tr>
</table>
</form>	
	
	";
	
$tpl=new templates();
return $tpl->_ENGINE_parse_body($html);	
	
}


function main_config_mimedefang(){
	$users=new usersMenus();
	if(!$users->MIMEDEFANG_INSTALLED){return null;}
	$users->LoadModulesEnabled();
	if($users->MimeDefangEnabled<>1){return null;}
	
$sock=new sockets();
$ini=new Bs_IniHandler();
$ini->loadString($sock->GET_INFO("ArticaPerformancesSettings"));	

$MX_REQUESTS=$ini->_params["MIMEDEFANG"]["MX_REQUESTS"];
if($MX_REQUESTS==null){$MX_REQUESTS=200;}

$MX_MINIMUM=$ini->_params["MIMEDEFANG"]["MX_MINIMUM"];
if($MX_MINIMUM==null){$MX_MINIMUM=2;}

$MX_MAXIMUM=$ini->_params["MIMEDEFANG"]["MX_MAXIMUM"];
if($MX_MAXIMUM==null){$MX_MAXIMUM=10;}

$MX_MAX_RSS=$ini->_params["MIMEDEFANG"]["MX_MAX_RSS"];
if($MX_MAX_RSS==null){$MX_MAX_RSS=30000;}

$MX_MAX_AS=$ini->_params["MIMEDEFANG"]["MX_MAX_AS"];
if($MX_MAX_AS==null){$MX_MAX_AS=90000;}

if($MX_REQUESTS>900){$mimedefang_macro=1;}
if($MX_REQUESTS<300){$mimedefang_macro=2;}
if($MX_REQUESTS<101){$mimedefang_macro=3;}
if($MX_REQUESTS<60){$mimedefang_macro=4;}



$arrp=array(0=>'{select}',1=>"{high}",2=>"{medium}",3=>"{low}",4=>'{very_low}');
$arrp=Field_array_Hash($arrp,'mimedefang_macro',$mimedefang_macro,"mimedefang_macro()");
	


$html="<H5>{mimedefang_consumption}</h5>
	<p class=caption>{mimedefang_consumption_text}</p>
	
	<table style='width:100%'>
		<tr>
		<td nowrap width=1% align='right'><strong>{mimedefang_macro}:</strong></td>
		<td>$arrp</td>	
		</tr>
	</table>
	
<form name=ffmmimedefang>
<table style='width:100%'>
<tr>
	<td nowrap width=1% align='right'><strong>{MX_REQUESTS}:</strong></td>
	<td>".Field_text('MX_REQUESTS',$MX_REQUESTS,'width:90px')."</td>
	<td nowrap width=1% align='left'>".help_icon('{MX_REQUESTS_TEXT}')."</td>
</tr>
<tr>
	<td nowrap width=1% align='right'><strong>{MX_MINIMUM}:</strong></td>
	<td>".Field_text('MX_MINIMUM',$MX_MINIMUM,'width:90px')."</td>
	<td nowrap width=1% align='left'>".help_icon('{MX_MINIMUM_TEXT}')."</td>
</tr>
<tr>
	<td nowrap width=1% align='right'><strong>{MX_MAXIMUM}:</strong></td>
	<td>".Field_text('MX_MAXIMUM',$MX_MAXIMUM,'width:90px')."</td>
	<td nowrap width=1% align='left'>".help_icon('{MX_MAXIMUM_TEXT}')."</td>
</tr>
<tr>
	<td nowrap width=1% align='right'><strong>{MX_MAX_RSS}:</strong></td>
	<td>".Field_text('MX_MAX_RSS',$MX_MAX_RSS,'width:90px')."</td>
	<td nowrap width=1% align='left'>".help_icon('{MX_MAX_RSS_TEXT}')."</td>
</tr>
<tr>
	<td nowrap width=1% align='right'><strong>{MX_MAX_AS}:</strong></td>
	<td>".Field_text('MX_MAX_AS',$MX_MAX_AS,'width:90px')."</td>
	<td nowrap width=1% align='left'>".help_icon('{MX_MAX_AS_TEXT}')."</td>
</tr>
<tr>
	<td colspan=2 align='right'><input type=button OnClick=\"javascript:ParseForm('ffmmimedefang','$page',true);\" value='{apply}&nbsp;&raquo;'>
</tr>
</table>
</form>	";	
$tpl=new templates();
return "<div style='float:left;margin:4px;width:300px'>".RoundedLightGrey($tpl->_ENGINE_parse_body($html))."</div>";	
	
}



function main_status(){
	include_once("ressources/class.os.system.tools.inc");
	$os=new os_system();
	$arraycpu=$os->cpu_info();
	$cpuspeed=round($arraycpu["cpuspeed"]/1000*100)/100; 
	
	$html="
	<table style='width:100%'>
	<tr>
	<td width=1% valign='top'><img src='img/64-computer.png'></td>
	<td valign='top'>
	<table style='width:100%'>
		<tr>
			<td width=1% nowrap align='right' valign='top'>cpu:</td>
			<td width=99%><strong>{$arraycpu["model"]}</strong></td>
		</tr>
		<tr>
			<td width=1% nowrap align='right' valign='top'>Cache:</td>
			<td width=99%><strong>{$arraycpu["cache"]}</strong></td>
		</tr>		
		<tr>
			<td width=1% nowrap align='right'>{cpu_number}:</td>
			<td width=99%>{$arraycpu["cpus"]}</td>
		</tr>
		<tr>
			<td width=1% nowrap align='right'>{status}:</td>
			<td width=99%>{$cpuspeed}GHz</td>
		</tr>					
	</table>
	</td>
	</tr>
	</table>
	";
	$mem=$os->html_Memory_usage();
	
	$mem=RoundedLightGreen($mem);
	
	$html=RoundedLightGreen($html)."<br>$mem";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
	

}


function save_process(){
$sock=new sockets();	
$ini=new Bs_IniHandler();
unset($_POST["MysqlNice"]);
$ini->loadString($sock->GET_INFO("ArticaPerformancesSettings"));
if(isset($_POST["cpuLimitEnabled"])){$sock->SET_INFO('cpuLimitEnabled',$_POST["cpuLimitEnabled"]);}
if(isset($_POST["systemMaxOverloaded"])){$sock->SET_INFO('systemMaxOverloaded',$_POST["systemMaxOverloaded"]);}
if(isset($_POST["systemForkProcessesNumber"])){$sock->SET_INFO('systemForkProcessesNumber',$_POST["systemForkProcessesNumber"]);}
if(isset($_POST["SystemV5CacheEnabled"])){$sock->SET_INFO('SystemV5CacheEnabled',$_POST["SystemV5CacheEnabled"]);}
if(isset($_POST["MaxMailEventsLogs"])){$sock->SET_INFO('MaxMailEventsLogs',$_POST["MaxMailEventsLogs"]);}
if(isset($_POST["DisableLoadAVGQueue"])){$sock->SET_INFO('DisableLoadAVGQueue',$_POST["DisableLoadAVGQueue"]);}
if(isset($_POST["SystemLoadNotif"])){$sock->SET_INFO('SystemLoadNotif',$_POST["SystemLoadNotif"]);}

if(isset($_POST["oom_kill_allocating_task"])){$sock->SET_INFO('oom_kill_allocating_task',$_POST["oom_kill_allocating_task"]);}
if(isset($_POST["SysTmpDir"])){$sock->SET_INFO("SysTmpDir",$_POST["SysTmpDir"]);}
	
	while (list ($num, $val) = each ($_POST) ){
		if(strpos($val, "javascript")>0){continue;}
		if(preg_match("#^Text_#", $num)){continue;}
		writelogs("Save $num == '$val'",__FUNCTION__,__FILE__,__LINE__);
		$ini->_params["PERFORMANCES"][$num]=$val;
		
	}
	
	
$sock->SaveConfigFile($ini->toString(),"ArticaPerformancesSettings");
$sock->getFrameWork('cmd.php?replicate-performances-config=yes');
$sock->getFrameWork('cmd.php?RestartDaemon=yes');
$sock->getFrameWork('services.php?restart-monit=yes');




/*SyslogNgPref	4
syslogng_log_fifo_size	2048
syslogng_sync	0
*/

if(isset($_POST["SyslogNgPref"])){
	$sock=new sockets();
	$sock->getfile('restartsyslogng');	
	$sock->getfile("restartmysqldependencies");
}

if(isset($_POST["MaxtimeBackupMailSizeCalculate"])){
	$sock=new sockets();
	if($_POST["MaxtimeBackupMailSizeCalculate"]<20){$_POST["MaxtimeBackupMailSizeCalculate"]=20;}
	$sock->SET_INFO("MaxtimeBackupMailSizeCalculate",$_POST["MaxtimeBackupMailSizeCalculate"]);
}
	
}

function save_mimedefang(){
$artica=new artica_general();
$ini=new Bs_IniHandler();
$ini->loadString($artica->ArticaPerformancesSettings);
	
	while (list ($num, $val) = each ($_GET) ){
		$ini->_params["MIMEDEFANG"][$num]=$val;
		
	}
$artica->ArticaPerformancesSettings=$ini->toString();
$artica->Save();
	$sock=new sockets();
	$sock->getfile('restartmimedefang');
		
	
}

function mysql_test_perfs(){
	
	$sock=new sockets();
	$q=new mysql();
	$time=$sock->getFrameWork("cmd.php?MySqlPerf=yes&username=$q->mysql_admin&pass=$q->mysql_password&host=$q->mysql_server&port=$q->mysql_port");

	$html="
	
	
	<div style='font-size:18px'>{service_performances}</div>
	<span style='font-size:14px;font-weight:bold;color:#d32d2d'>{benchmark_result}: <code>$time seconds</code></span>
	<H2>{others_benchmarks}</H2>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>Dual core 3Ghz / 4 Go Mem</td>
		<td><strong style='font-size:12px'>1.36 seconds</strong></td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>AMD 64 3200+</td>
		<td><strong style='font-size:12px'>4.92 seconds</strong></td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>Intel Pentium 4 Dual Core (3.20 GHz)</td>
		<td><strong style='font-size:12px'>3.76 seconds</strong></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>Intel Xeon x2 (3.00 GHz)</td>
		<td><strong style='font-size:12px'>3.43 seconds</strong></td>
	</tr>			
	<tr>
	<td class=legend style='font-size:14px'>AMD Athlon(tm) 64 X2 Dual Core Processor 4200+</td>
	<td><strong style='font-size:12px'>2.94 seconds</strong></td>
	</tr>
	<tR>
	<td class=legend style='font-size:14px'>Intel(R) Core(TM)2 Duo CPU E7200 @ 2.53GHz</td>
	<td><strong style='font-size:12px'>2.49 seconds</strong></td>
	</tr>
	<tR>
	<td class=legend style='font-size:14px'>Bi xeon 2.66 4 Go Mem</td>
	<td><strong style='font-size:12px'>1.59 seconds</strong></td>
	</tr>
	<tR>
	<td class=legend style='font-size:14px'>Intel C2D T7200 @2GHz, 3Go Mem 64bits</td>
	<td><strong style='font-size:12px'>1.96 seconds</strong></td>
	</tr>
	</table>
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function cron_apc(){
	$array=parsePHPModules();
	$array=$array["apc"];
	$page=CurrentPageName();
	$apc_cache_info=apc_cache_info();
	//print_r($apc_cache_info);
	
	while (list ($num, $val) = each ($apc_cache_info) ){
		if(is_array($val)){continue;}
		
		if($num=="file_upload_progress"){continue;}
		if($num=="start_time"){
			$val=date('M d D H:i:s',$val);
		}
		
		if($num=="mem_size"){
			$val=FormatBytes(($val/1024));
		}
		
		
		$html=$html."
		<tr>
			<td class=legend style='font-size:14px'>{{$num}}:</td>
			<td><strong>$val</strong></td>
		</tr>
		
		";
	}
	
	$html=$html."
		<tr>
			<td class=legend style='font-size:14px'>{cached_files_number}:</td>
			<td><strong>". count($apc_cache_info["cache_list"])."</strong></td>
		</tr>
	";
	$html="
	<H1>APC V.{$array["Version"]} {$array["Revision"]}</H1>
	<table style='width:100%'>
	$html
	</table>
	<div style='text-align:right'>". texttooltip("{cached_files}","{cached_files_list}","APCCachedFileList()")."</div>
	
	<script>
		function APCCachedFileList(){
			YahooWin3('650','$page?apc-cached-file-list=yes');
		}
	</script>";
	


	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}

function cron_logon(){
	$sock=new sockets();
	$logon_parameters=unserialize(base64_decode($sock->GET_INFO("LogonPageSettings")));
	$page=CurrentPageName();
	$lang["en"]="English";
	$lang["fr"]="Francais";
	$lang["po"]="Portugues";
	$lang["br"]="Brazilian";
	$lang["es"]="Espanol";
	$lang["it"]="Italiano";
	$lang["de"]="Deutsch";	
	$HTMLTitle=$sock->GET_INFO("HTMLTitle");
	if(trim($HTMLTitle)==null){$HTMLTitle="%s (%v)";}
	
	
	$html="
	<div id='cron-logon-div' style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:14px'>{remove_language_selector}</td>
		<td>". Field_checkbox("LANGUAGE_SELECTOR_REMOVE",1,
		$logon_parameters["LANGUAGE_SELECTOR_REMOVE"],"CronLogonApplySelector()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{default_language}</td>
		<td>". Field_array_Hash($lang,"DEFAULT_LANGUAGE",$logon_parameters["DEFAULT_LANGUAGE"],null,null,0,"font-size:14px;padding:3px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{title_pages}:</td>
		<td>". Field_text("HTMLTitle",$HTMLTitle,"font-size:14px;padding:3px;width:180px")."</td>
	</tr>	
	
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","CronLogonApply()",16)."</td>
	</tr>
	</table>
	</div>
	<script>
	var x_CronLogonApply= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		RefreshTab('admin_index_settings');
	}		
	
	function CronLogonApply(){
		var XHR = new XHRConnection();
		if(document.getElementById('LANGUAGE_SELECTOR_REMOVE').checked){XHR.appendData('LANGUAGE_SELECTOR_REMOVE',1);}else{XHR.appendData('LANGUAGE_SELECTOR_REMOVE',0);}
		XHR.appendData('DEFAULT_LANGUAGE',document.getElementById('DEFAULT_LANGUAGE').value);
		XHR.appendData('HTMLTitle',document.getElementById('HTMLTitle').value);
		AnimateDiv('cron-logon-div');
		XHR.sendAndLoad('$page', 'GET',x_CronLogonApply);
		
	
	}		
	
	function CronLogonApplySelector(){
		document.getElementById('DEFAULT_LANGUAGE').disabled=true;
		if(document.getElementById('LANGUAGE_SELECTOR_REMOVE').checked){
			document.getElementById('DEFAULT_LANGUAGE').disabled=false;
		}
	}
	CronLogonApplySelector();
	ChangeHTMLTitle();
	
</script>
	";
	
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function cron_logon_save(){
	$sock=new sockets();
	$logon_parameters=unserialize(base64_decode($sock->GET_INFO("LogonPageSettings")));
	
	while (list ($num, $val) = each ($_GET) ){
		$logon_parameters[$num]=$val;
	}
	
	if(isset($_GET["HTMLTitle"])){$sock->SET_INFO("HTMLTitle", $_GET["HTMLTitle"]);}
		
	$sock->SaveConfigFile(base64_encode(serialize($logon_parameters)),"LogonPageSettings");
	
}


function cron_apc_list(){
	$apc_cache_info=apc_cache_info();

	$html="
	<div style='height:500px;overflow:auto'>
	<table style='width:100%'>";
	while (list ($num, $array) = each ($apc_cache_info["cache_list"]) ){
		$filename=$array["filename"];
		$filename=str_replace(dirname(__FILE__)."/","",$filename);
		$mem_size=ParseBytes($array["mem_size"]/1024);
		$access_time=date('D H:i:s',$array["access_time"]);
		$html=$html."
			<tr ". CellRollOver().">
			<td width=1%><img src='img/fw_bold.gif'></td>
			<td><strong>$filename</strong></td>
			<td width=1% nowrap><strong>$mem_size</strong></td>
			<td width=1% nowrap><strong>$access_time</strong></td>
			</tr>";
		
	}
	
	$html=$html."</table></div";
	
	echo $html;
}




function parsePHPModules() {
 ob_start();
 phpinfo(INFO_MODULES);
 $s = ob_get_contents();
 ob_end_clean();

 $s = strip_tags($s,'<h2><th><td>');
 $s = preg_replace('/<th[^>]*>([^<]+)<\/th>/',"<info>\\1</info>",$s);
 $s = preg_replace('/<td[^>]*>([^<]+)<\/td>/',"<info>\\1</info>",$s);
 $vTmp = preg_split('/(<h2[^>]*>[^<]+<\/h2>)/',$s,-1,PREG_SPLIT_DELIM_CAPTURE);
 $vModules = array();
 for ($i=1;$i<count($vTmp);$i++) {
  if (preg_match('/<h2[^>]*>([^<]+)<\/h2>/',$vTmp[$i],$vMat)) {
   $vName = trim($vMat[1]);
   $vTmp2 = explode("\n",$vTmp[$i+1]);
   foreach ($vTmp2 AS $vOne) {
   $vPat = '<info>([^<]+)<\/info>';
   $vPat3 = "/$vPat\s*$vPat\s*$vPat/";
   $vPat2 = "/$vPat\s*$vPat/";
   if (preg_match($vPat3,$vOne,$vMat)) { // 3cols
     $vModules[$vName][trim($vMat[1])] = array(trim($vMat[2]),trim($vMat[3]));
   } elseif (preg_match($vPat2,$vOne,$vMat)) { // 2cols
     $vModules[$vName][trim($vMat[1])] = trim($vMat[2]);
   }
   }
  }
 }
 return $vModules;
}


?>	