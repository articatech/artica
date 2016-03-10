<?php
/*
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"");ini_set('error_append_string',"<br>\n");
$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_PROCESS"]=true;
$GLOBALS["VERBOSE_SYSLOG"]=true;
*/
if(function_exists("posix_getuid")){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
if(!$GLOBALS["AS_ROOT"]){session_start();unset($_SESSION["MINIADM"]);unset($_COOKIE["MINIADM"]);}
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
$GLOBALS["AS_ROOT"]=false;
$GLOBALS["VERBOSE"]=false;
if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',"");ini_set('error_append_string',"<br>\n");$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_PROCESS"]=true;$GLOBALS["VERBOSE_SYSLOG"]=true;}
if(isset($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}}
$GLOBALS["ICON_FAMILY"]="SYSTEM";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if($GLOBALS["VERBOSE"]){echo "Memory:(".__LINE__.") " .round(memory_get_usage(true)/1024)."Ko<br>\n";}
include_once("ressources/logs.inc");
include_once('ressources/class.templates.inc');
include_once('ressources/class.html.pages.inc');
include_once('ressources/class.cyrus.inc');
include_once('ressources/class.main_cf.inc');
include_once('ressources/charts.php');
include_once('ressources/class.syslogs.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.os.system.inc');
include_once('ressources/class.stats-appliance.inc');
if($GLOBALS["VERBOSE"]){echo "Memory:(".__LINE__.") " .round(memory_get_usage(true)/1024)."Ko<br>\n";}


if($GLOBALS["AS_ROOT"]){
	if($argv[1]=="--status-right"){
			status_right();
			if($GLOBALS["VERBOSE"]){echo " **** **** status_computer() **** ****\n";}
			status_computer();
			main_admin_tabs();
			exit;
	}
}

//ini_set('display_errors', 1);
//ini_set('error_reporting', E_ALL);

if(isset($_GET["HideTips"])){HideTips();exit;}

$users=new usersMenus();

if(!$users->AsAnAdministratorGeneric){
	writelogs("[{$_SESSION["uid"]}]:: privs = {$_SESSION["privileges"]}",__FUNCTION__,__FILE__,__LINE__);
	error_log("[{$_SESSION["uid"]}]::AsAnAdministratorGeneric =false in ".__FUNCTION__." file " .basename(__FILE__)." line ".__LINE__);
	writelogs("[{$_SESSION["uid"]}]::AsAnAdministratorGeneric =false in ",__FUNCTION__,__FILE__,__LINE__);
//	header('location:miniadm.php');
//	exit;
}

if(isset($_GET["CurrentTime"])){
	$t=time();
	$script="<script>
		function Clock$t(){ 
			
			IndexHorloge();
		  } 
			setTimeout('Clock$t()',10000);
	</script>";
	
	if(time()-$_SESSION["CurrentTime"]<9){$script=null;}
	
	if(function_exists("date_default_timezone_get")){$timezone=date_default_timezone_get();}
	
	echo date("H:i:s")." ($timezone)"
	.$script;	
	$_SESSION["CurrentTime"]=time();	
	die();
}


if(isset($_GET["admin-index-status-mysql"])){echo status_mysql();exit;}
if(isset($_GET["status_right_image"])){status_right_image();exit;}
if(isset($_GET["warnings"])){warnings_js();exit;}
if(isset($_GET["warnings-popup"])){warnings_popup();exit;}
if(isset($_GET["main_admin_tabs"])){echo main_admin_tabs();exit;}

if(isset($_GET["json-error-js"])){json_error_js();exit;}
if(isset($_GET["json-error-popup"])){json_error_popup();exit;}


if(isset($_GET["StartStopService-js"])){StartStopService_js();exit;}
if(isset($_GET["StartStopService-popup"])){StartStopService_popup();exit;}
if(isset($_GET["StartStopService-perform"])){StartStopService_perform();exit;}
if(isset($_GET["postfix-status-right"])){echo status_postfix();exit;}

if(isset($_GET["graph"])){graph();exit;}
if(isset($_GET["start-all-services"])){START_ALL_SERVICES();exit;}
if($_GET["status"]=="left"){die("<H1>Not supported</H1>");}
if($_GET["status"]=="right"){status_right();exit;}


if(isset($_GET["status"])){STATUS();exit;}
if(isset($_GET["postfix-status"])){POSTFIX_STATUS();exit;}
if(isset($_GET["AdminDeleteAllSqlEvents"])){warnings_delete_all();exit;}
if(isset($_GET["ShowFileLogs"])){ShowFileLogs();exit;}
if(isset($_GET["buildtables"])){CheckTables();exit;}
if(isset($_GET["CheckDaemon"])){CheckDaemon();exit;}
if(isset($_GET["EmergencyStart"])){EmergencyStart();exit;}
if(isset($_GET["memcomputer"])){status_computer();exit;}



if(isset($_GET["mem-dump-js"])){status_memdump_load();exit;}
if(isset($_GET["mem-dump"])){status_memdump();exit;}
if(isset($_GET["memory-status"])){status_memdump_js();exit;}
if(isset($_GET["artica-meta"])){artica_meta();exit;}
if(isset($_GET["admin-ajax"])){page($users);exit;}

page($users);


function HideTips(){
	$sock=new sockets();
	$sock->SET_INFO($_GET["HideTips"],1);
}

function status_memdump_load(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$processes_memory=$tpl->javascript_parse_text("{processes_memory}");
	echo "YahooWinBrowse(750,'$page?mem-dump=yes','$processes_memory',true)";
	
}

function status_right_image(){
	
	include("admin.index.right-image.php");
}

function warnings_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{$_GET["count"]} {warnings}");
	echo "YahooWinS('330','$page?warnings-popup=yes','$title');";
}
function json_error_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{error}");
	echo "YahooWinS('430','$page?json-error-popup={$_GET["json-error-js"]}','$title');";	
}

function warnings_popup(){
	$content=@file_get_contents("ressources/logs/status.warnings.html");
	$page=CurrentPageName();
	$tpl=new templates();	
	echo $tpl->_ENGINE_parse_body($content);
}

function json_error_popup(){
	$error=base64_decode($_GET["json-error-popup"]);
	
	echo "<tt>$error</tt>";
}

function StartStopService_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	if($_GET["typ"]==1){$title_pre='{starting}';}else{$title_pre="{stopping}";}
	$title_s=$tpl->_ENGINE_parse_body("$title_pre::{{$_GET["apps"]}}");
	$apps=base64_encode($_GET["apps"]);
	$html="
		function StartStopServiceStart(){
			YahooLogWatcher(550,'$page?StartStopService-popup=yes&cmd={$_GET["cmd"]}&typ={$_GET["typ"]}&apps=$apps','$title_s');
		}
	
	
	
	StartStopServiceStart()";
	
	echo $html;
}

function StartStopService_popup(){
	$page=CurrentPageName();
	
	$html="
	
	<div style='padding:3px;margin:3px;font-size:11px;width:100%;height:450px;overflow:auto' id='StartStopService_popup'>
	</div>
	
	<script>
		LoadAjax('StartStopService_popup','$page?StartStopService-perform=yes&cmd={$_GET["cmd"]}&typ={$_GET["typ"]}&apps={$_GET["apps"]}',true);
	</script>
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}

function StartStopService_perform(){
	$cmd=$_GET["cmd"];
	$typ=$_GET["typ"];
	$apps=base64_decode($_GET["apps"]);
	$sock=new sockets();
	if($typ==1){
		$datas=$sock->getFrameWork("cmd.php?start-service-name=$cmd&MyCURLTIMEOUT=300");
	}else{
		$datas=$sock->getFrameWork("cmd.php?stop-service-name=$cmd&MyCURLTIMEOUT=300");
	}
	
	$tbl=unserialize(base64_decode($datas));
	$html="
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
	<th colspan=2>{$apps}</th>
	</tr>
</thead>
<tbody class='tbody'>";	
	while (list ($num, $ligne) = each ($tbl) ){
			if(trim($ligne==null)){continue;}
			if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
			$html=$html . "
			<tr class=$classtr>
				<td width=1%><img src='img/fw_bold.gif'></td>
				<td ><code style='font-size:14px'>" . htmlentities($ligne)."</code></td>
			</tr>
			";
			
		
	}
	

	$tpl=new templates();
	$html=$tpl->_ENGINE_parse_body($html. "</tbody></table>");

	$html=$html."
	
	<script>
		if(document.getElementById('main_config_pptpd')){RefreshTab('main_config_pptpd');}
		if(document.getElementById('squid_main_config')){RefreshTab('squid_main_config');}
		if(document.getElementById('services_status')){RefreshTab('services_status');}
	</script>
	
	
	";
	
	
	echo $html;
	
	
	
}
function page($usersmenus){
	$left_menus=null;
if(isset($_GET["admin-ajax"])){
	echo "<script>LoadAjax('middle','quicklinks.php');</script>";
	return;
	
}else{	
	if(GET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__)){return null;}
}
$ldap=new clladp();
$page=CurrentPageName();
error_log(basename(__FILE__)." ".__FUNCTION__."() [{$_SESSION["uid"]}] line ". __LINE__);
$hash=$ldap->UserDatas($_SESSION["uid"]);
error_log(basename(__FILE__)." ".__FUNCTION__."() [{$_SESSION["uid"]}] line ". __LINE__);
if($hash["displayName"]==null){$hash["displayName"]="{Administrator}";}
$sock=new sockets();
$ou=$hash["ou"];
$users=new usersMenus();
error_log(basename(__FILE__)." ".__FUNCTION__."() [{$_SESSION["uid"]}] line ". __LINE__);

if(isset($_COOKIE["artica-template"])){
	if(is_file("ressources/templates/{$_COOKIE["artica-template"]}/JQUERY_UI")){
		$GLOBALS["JQUERY_UI"]=trim(@file_get_contents("ressources/templates/{$_COOKIE["artica-template"]}/JQUERY_UI"));
	}
}

if($users->KASPERSKY_SMTP_APPLIANCE){
	if($sock->GET_INFO("KasperskyMailApplianceWizardFinish")<>1){
		$wizard_kaspersky_mail_appliance="Loadjs('wizard.kaspersky.appliance.php',true);";
	}
}

	if($users->KASPERSKY_WEB_APPLIANCE){
		//$GLOBALS["CHANGE_TEMPLATE"]="squid.kav.html";
		//$GLOBALS["JQUERY_UI"]="kavweb";
	}

	if(isset($_GET["admin-ajax"])){$left_menus="LoadAjax('TEMPLATE_LEFT_MENUS','/admin.tabs.php?left-menus=yes',true);";}


$html="
<script>
	LoadAjax('middle','quicklinks.php');
	ChangeHTMLTitle();
</script>


";	
	
	
$tpl=new template_users($title,$html,$_SESSION,0,0,0,$cfg);
error_log(basename(__FILE__)." ".__FUNCTION__."() [{$_SESSION["uid"]}] line ". __LINE__);
$html=$tpl->web_page;
SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$html);
echo $html;	
return;	
	
}

function _milter_greylist_enabled(){
	$users=new usersMenus();
	$sock=new sockets();
	if(!$users->MILTERGREYLIST_INSTALLED){return 0;}
	$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
	$enabled=$sock->GET_INFO("MilterGreyListEnabled");
	$EnableASSP=$sock->GET_INFO('EnableASSP');
	if($enabled==null){$enabled=0;}
	if($EnablePostfixMultiInstance==1){$enabled=0;}
	if($EnableASSP==1){$enabled=0;}
	return $enabled;
}

function OnlyWebstats($users){
	
	if($users->AsSystemAdministrator){return false;}
	if($users->AsDansGuardianAdministrator){return false;}
	if($users->AsSquidAdministrator){return false;}
	if($users->ASDCHPAdmin){return false;}
	if($users->AsDnsAdministrator){return false;}
	if($users->AsArticaMetaAdmin){return false;}
	if($users->AsHotSpotManager){return false;}
	if($users->AsInventoryAdmin){return false;}
	if($users->AsProxyMonitor){return false;}
	if($users->AsSystemAdministrator){return false;}
	if($users->AsWebStatisticsAdministrator){return true;}
	
}

function main_admin_tabs(){
	if(!$GLOBALS["AS_ROOT"]){if(GET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__)){return null;}}
	$ldap=new clladp();
	$users=new usersMenus();
	$sys=new syslogs();
	$artica=new artica_general();
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	if($GLOBALS["VERBOSE"]){echo "<li>".__FUNCTION__." line:".__LINE__."</li>";}
	$OnlyWebstats=OnlyWebstats($users);
	$page=CurrentPageName();
	
	$array["t:frontend"]="{status}";
	
	$DisableMessaging=intval($sock->GET_INFO("DisableMessaging"));
	if($DisableMessaging==1){$users->POSTFIX_INSTALLED=false;}
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	
	
	$SQUIDEnable=trim($sock->GET_INFO("SQUIDEnable"));
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	if($SQUIDEnable==0){$users->SQUID_INSTALLED=false;}
	
	// 2 = Not statistics, 
	
	if($SquidPerformance<3){
		if(!$ldap->IsKerbAuth()){$array["t:orgs"]="{organizations}";}
	}
	
	
	
	$StatsApplianceReceivers=intval($sock->GET_INFO("StatsApplianceReceivers"));
	if($StatsApplianceReceivers>0){
		$array["t:STAS_APP"]="$StatsApplianceReceivers Proxy(s)";
		
	}
	
	if($users->SAMBA_APPLIANCE){$array["t:smbshares"]='{shared_folders}';}
	
	
	if($users->VPS_OPENVZ){$array["t:openvz"]='OpenVZ';}
	
	if($artica->EnableMonitorix==1){$array["t:monitorix"]='{monitorix}';}
	
	
	
	if($users->POSTFIX_INSTALLED){
		if($users->AsPostfixAdministrator){
			$EnableArticaSMTPStatistics=$sock->GET_INFO("EnableArticaSMTPStatistics");
			if(!is_numeric($EnableArticaSMTPStatistics)){$EnableArticaSMTPStatistics=1;}
			$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
			if(!is_numeric($EnablePostfixMultiInstance)){$EnablePostfixMultiInstance=0;}		
			if($EnableArticaSMTPStatistics==1){	
				$array["t:realtime-smtp"]="{realtime_monitor}";
				$array["t:emails_received"]="{emails_received}";
				$array["t:connections"]="{connections}";
				if(_milter_greylist_enabled()==1){
					$array["t:miltergreylist"]="{greylisting}";
				}
			}
			
			if($EnablePostfixMultiInstance==1){
				$array["t:multiple_instances"]="{multiple_instances}";
			}
		}
		
	}

	
	
	

if($users->KASPERSKY_SMTP_APPLIANCE){
	$array["t:kaspersky"]="Kaspersky";	
}else{
	if($users->AsSystemAdministrator){
		$array["t:system"]="{webinterface}";
	}
}	

if(count($array)<8){
	if($users->SQUID_INSTALLED){
		$array["t:starting-guide-squid"]="{starting_guide}";
	}
}

$build_artica_tabs_size=0;


$count=count($array);
//if($count<7){$array["add-tab"]="{add}&nbsp;&raquo;";}



		


$t=time();
if(isset($_GET["tab-font-size"])){
	if($_GET["tab-font-size"]=="14px"){$_GET["tab-font-size"]="12px";}
	$style="style=font-size:{$_GET["tab-font-size"]}";
}
if(isset($_GET["tab-width"])){$width=$_GET["tab-width"];}
if(isset($_GET["newfrontend"])){$newfrontend="&newfrontend=yes";}

if(count($array)>0){$style="style=font-size:22px";}
if(count($array)>4){$style="style=font-size:18px";}
if(count($array)>6){$style="style=font-size:16px";}
if(count($array)>7){$style="style=font-size:11px";}


//$style="style=font-size:18px";

	while (list ($num, $ligne) = each ($array) ){
		if(preg_match("#t:(.+)#",$num,$re)){
			$ligne=$tpl->javascript_parse_text($ligne);
			
			
	
			
			if($re[1]=="prxystatsimport"){
				$html[]= "<li ><a href=\"squid.statistics.import.php\"><span $style>$ligne</span></a></li>\n";
				continue;
			}			
			
			
			
			if($re[1]=="system"){
				$html[]= "<li ><a href=\"artica.webconsole.php\"><span $style>$ligne</span></a></li>\n";
				continue;
			}
			if($re[1]=="smbshares"){
				$html[]= "<li ><a href=\"samba.index.php?main=shared_folders\"><span $style>$ligne</span></a></li>\n";
				continue;
			}			
			
			
			
			
			if($re[1]=="TOP-WEB"){
				$html[]= "<li ><a href=\"admin.index.load.top-web.php\"><span $style>$ligne</span></a></li>\n";
				continue;
			}
			
			if($re[1]=="realtime-smtp"){
				$html[]= "<li ><a href=\"postfix.realtime.monitor.php\"><span $style>$ligne</span></a></li>\n";
				continue;
			}
			
			if($re[1]=="STAS_APP"){
				$html[]= "<li ><a href=\"stats-appliance.proxys.php\"><span $style>$ligne</span></a></li>\n";
				continue;
				
			}
			
			if($re[1]=="BANDWITH-STATS"){
				$html[]= "<li ><a href=\"admin.index.load.bandwith.php\"><span $style>$ligne</span></a></li>\n";
				continue;
			}			
			
			if($re[1]=="starting-guide-squid"){
				$html[]= "<li ><a href=\"admin.index.startingguide.squid.php\"><span $style>$ligne</span></a></li>\n";
				continue;
			}	
			if($re[1]=="connections"){
				if($users->AsPostfixAdministrator){
					$html[]= "<li ><a href=\"admin.postfix.connections.php\"><span $style>$ligne</span></a></li>\n";
					continue;
				}
			}
			if($re[1]=="miltergreylist"){
				if($users->AsPostfixAdministrator){
					$html[]= "<li ><a href=\"admin.miltergreylist.connections.php\"><span $style>$ligne</span></a></li>\n";
					continue;
				}
			}			
			
			
			
			
			if($re[1]=="cnx"){
				$html[]= "<li ><a href=\"admin.cnx.php?t=0$newfrontend\"><span $style>$ligne</span></a></li>\n";
				continue;
			}
			
			if($re[1]=="members"){
				$html[]= "<li ><a href=\"freeradius.users.php?t=0$newfrontend\"><span $style>$ligne</span></a></li>\n";
				continue;
			}			
			
			if($re[1]=="multiple_instances"){
				$html[]= "<li ><a href=\"postfix.multiple.instances.infos.php?iniline=yes$newfrontend\"><span $style>$ligne</span></a></li>\n";
				continue;
			}			
			
			if($re[1]=="remote-web-appliances"){
				$html[]= "<li ><a href=\"squid.statsappliance.clients.php?listonly=yes\"><span $style>$ligne</span></a></li>\n";
				continue;
			}				
			
			if($re[1]=="orgs"){
				$html[]= "<li ><a href=\"domains.index.php?inside-tab=yes$newfrontend\"><span $style>$ligne</span></a></li>\n";
				continue;
			}
			if($re[1]=="openvz"){
				$html[]= "<li ><a href=\"openvz.status.php\"><span $style>$ligne</span></a></li>\n";
				continue;
			}
			
			
			$html[]= "<li><a href=\"admin.tabs.php?main={$re[1]}$newfrontend\"><span $style>$ligne</span></a></li>\n";
			continue;
		}
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"admin.tabs.php?tab=$num$newfrontend\"><span $style>$ligne</span></a></li>\n");
		}
	
$t=time();	

return build_artica_tabs($html, "admin_perso_tabs-$t",$build_artica_tabs_size)."
		<input type=hidden id='admin_perso_tabs-ID' value='admin_perso_tabs-$t'>
		<script>LeftDesign('dashboard-256-opac20.png');</script>";

		
}

function main_admin_tabs_perso_tabs(){
	$uid=$_SESSION["uid"];
	if(!is_file("ressources/profiles/$uid.tabs")){return array();}
	$ini=new Bs_IniHandler("ressources/profiles/$uid.tabs");
	if(!is_array($ini->_params)){return array();}
	while (list ($num, $ligne) = each ($ini->_params) ){
		if($ligne["name"]==null){continue;}
		$array[$num]=$ligne["name"];
		
	}
	if(!is_array($array)){return array();}
	return $array;
}
function POSTFIX_STATUS(){
	$users=new usersMenus();
	$tpl=new templates();

	if($users->POSTFIX_INSTALLED){
			$status=new status();
			echo $tpl->_ENGINE_parse_body($status->Postfix_satus());
			exit;
	}	
}

function STATUS(){
	$tpl=new templates();
	$ini=new Bs_IniHandler();
	
	$SQUID["SQUID"]=true;
	$SQUID["APP_CNTLM"]=true;
	$SQUID["DANSGUARDIAN"]=true;
	$SQUID["KAV4PROXY"]=true;
	$SQUID["C-ICAP"]=true;
	$SQUID["APP_PROXY_PAC"]=true;
	$SQUID["APP_SQUIDGUARD_HTTP"]=true;
	$SQUID["APP_UFDBGUARD"];
	$SQUID["APP_FRESHCLAM"];
	$SQUID["APP_ARTICADB"];
	$SQUID["APP_HAARP"];
	$SQUID["APP_FTP_PROXY"];
	$SQUID["APP_CNTLM_PARENT"]=true;
	
	if(isset($SQUID[$_GET["KEY"]])){
		$sock=new sockets();
		$ini->loadString(base64_decode($sock->getFrameWork('cmd.php?squid-ini-status=yes')));
		echo $tpl->_ENGINE_parse_body(DAEMON_STATUS_ROUND($_GET["KEY"],$ini,null,0,0,1));
		return;
		
	}
	$ini->loadFile("/usr/share/artica-postfix/ressources/logs/global.status.ini");
	echo $tpl->_ENGINE_parse_body(DAEMON_STATUS_ROUND($_GET["KEY"],$ini,null,0,0,1));
}

function status_computer_mysql_memory_check(){
	include_once('ressources/class.mysql-server.inc');
	$t=time();
	$instance_id=0;
	$page=CurrentPageName();
	$tpl=new templates();
	$mysql=new mysqlserver();
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){return;}
	if($users->MEM_TOTAL_INSTALLEE<624288){return;}
	
	$serverMem=round(($users->MEM_TOTAL_INSTALLEE-300)/1024);	
	$color="black";
	$VARIABLES=$mysql->SHOW_VARIABLES();

	
	if(!is_numeric($mysql->main_array["max_connections"])){$mysql->main_array["max_connections"]=$VARIABLES["max_connections"];}
	$read_buffer_size=$mysql->main_array["read_buffer_size"];
	if(!is_numeric($read_buffer_size)){$read_buffer_size=($VARIABLES["read_buffer_size"]/1024)/1000;}
	
	$read_rnd_buffer_size=$mysql->main_array["read_rnd_buffer_size"];
	if(!is_numeric($read_rnd_buffer_size)){$read_rnd_buffer_size=($VARIABLES["read_rnd_buffer_size"]/1024)/1000;}
		
	$sort_buffer_size=$mysql->main_array["sort_buffer_size"];
	if(!is_numeric($sort_buffer_size)){$sort_buffer_size=($VARIABLES["sort_buffer_size"]/1024)/1000;}	

	$thread_stack=$mysql->main_array["thread_stack"];
	if(!is_numeric($thread_stack)){$thread_stack=($VARIABLES["thread_stack"]/1024)/1000;}	
	
	$join_buffer_size=$mysql->main_array["join_buffer_size"];
	if(!is_numeric($join_buffer_size)){$join_buffer_size=($VARIABLES["join_buffer_size"]/1024)/1000;}		
	
	
	$per_thread_buffers=$sort_buffer_size+$read_rnd_buffer_size+$sort_buffer_size+$thread_stack+$join_buffer_size;
	$Warn=false;
	$total_per_thread_buffers=$per_thread_buffers*$mysql->main_array["max_connections"];
	if($total_per_thread_buffers>$serverMem){$Warn=true;}
	
	
	$key_buffer_size=$mysql->main_array["key_buffer_size"];
	if(!is_numeric($key_buffer_size)){$key_buffer_size=($VARIABLES["key_buffer_size"]/1024)/1000;}		
	
	$max_tmp_table_size=$mysql->main_array["max_tmp_table_size"];
	if(!is_numeric($max_tmp_table_size)){$max_tmp_table_size=($VARIABLES["max_tmp_table_size"]/1024)/1000;}		
	
	$innodb_buffer_pool_size=$mysql->main_array["innodb_buffer_pool_size"];
	if(!is_numeric($innodb_buffer_pool_size)){$innodb_buffer_pool_size=($VARIABLES["innodb_buffer_pool_size"]/1024)/1000;}		
	
	$innodb_additional_mem_pool_size=$mysql->main_array["innodb_additional_mem_pool_size"];
	if(!is_numeric($innodb_additional_mem_pool_size)){$innodb_additional_mem_pool_size=($VARIABLES["innodb_additional_mem_pool_size"]/1024)/1000;}	
	
	$innodb_log_buffer_size=$mysql->main_array["innodb_log_buffer_size"];
	if(!is_numeric($innodb_log_buffer_size)){$innodb_log_buffer_size=($VARIABLES["innodb_log_buffer_size"]/1024)/1000;}		
	
	$query_cache_size=$mysql->main_array["query_cache_size"];
	if(!is_numeric($query_cache_size)){$query_cache_size=($VARIABLES["query_cache_size"]/1024)/1000;}		
	
	$server_buffers=$key_buffer_size+$max_tmp_table_size+$innodb_buffer_pool_size+$innodb_additional_mem_pool_size+$innodb_log_buffer_size+$query_cache_size;
	if($server_buffers>$serverMem){$Warn=true;}
	
	$max_used_memory=$server_buffers+$total_per_thread_buffers;
	if($max_used_memory>$serverMem){$Warn=true;}
	
	$UNIT="M";
	if($max_used_memory>1000){$max_used_memory=round(($max_used_memory/1000),2);$UNIT="G";}	
	
	$text=$tpl->_ENGINE_parse_body("{mysql_warn_must_tune}");
	$text=str_replace("%m", $serverMem, $text);
	
	if($Warn){
		echo"
		<table style='width:99%' class=form>
		<tr>
			<td valign='top'><img src='img/database-error-64.png'>
			<td valign='top'><a href=\"javascript:blur();\" OnClick=\"Loadjs('mysql.perfs.php',true);\" 
			style='font-size:12px;color:#C72727;text-decoration:underline'>$text</a>
			</td>
		</tr>
		</table>
		
		";
		return;
		
	}
	$sock=new sockets();
	$sock->SET_INFO("MySqlMemoryCheck", 1);
	
}


function status_computer(){
	if(!$GLOBALS["AS_ROOT"]){
		if(!$GLOBALS["VERBOSE"]){
			if(is_file("/usr/share/artica-postfix/ressources/logs/web/admin.index.memory.html")){
				$data=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/admin.index.memory.html");
				if(strlen($data)>45){
					$tpl=new templates();
					echo $tpl->_ENGINE_parse_body($data);
					return;
				}
			}
		}
	}
	
	
	$page=CurrentPageName();
	$sock=new sockets();
	$newfrontend=false;if(isset($_GET["newfrontend"])){$newfrontend=true;}
	
	
	
	if(!$GLOBALS["VERBOSE"]){
		if(!$GLOBALS["AS_ROOT"]){
			if(GET_CACHED(__FILE__, __FUNCTION__,"time",false,3)){return;}
			if(internal_load()>1.2){if(GET_CACHED(__FILE__, __FUNCTION__)){return;}}
		}
	}
	
	if($newfrontend){
		$ajaxadd="&newfrontend=yes";
	}
	
	include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
	$html="<div id='admin-index-status-mysql'></div>
	<script>LoadAjaxTiny('admin-index-status-mysql','$page?admin-index-status-mysql=yes');</script>";
	
	
	$os=new os_system();
	if($GLOBALS["VERBOSE"]){echo "os->html_Memory_usage()<br>\n";}
	$html=$html.RoundedLightGrey($os->html_Memory_usage())."<br>
	<script>
	if(document.getElementById('left_status')){
		var content=document.getElementById('left_status').innerHTML;
		if(content.length<5){
			LoadAjax('left_status','$page?status=left$ajaxadd',true);
		}
	}
	</script>
	
	
	";
	if($GLOBALS["AS_ROOT"]){
		include_once(dirname(__FILE__).'/framework/class.unix.inc');
		include_once(dirname(__FILE__).'/framework/frame.class.inc');		
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/admin.index.memory.html", $html);
		return;
	}
	
	SET_CACHED(__FILE__, __FUNCTION__, $html);
	SET_CACHED(__FILE__, __FUNCTION__,"time", $html);
	echo $html;
}



function status_mysql(){
	$tpl=new templates();
	$q=new mysql();
	$sock=new sockets();
	$page=CurrentPageName();
	include_once(dirname(__FILE__)."/ressources/class.mysql.syslogs.inc");
	$EnableSyslogDB=$sock->GET_INFO("EnableSyslogDB");
	if(!is_numeric($EnableSyslogDB)){$EnableSyslogDB=0;}
	$MySQLSyslogType=$sock->GET_INFO("MySQLSyslogType");
	
	
	$MySqlMemoryCheck=$sock->GET_INFO("MySqlMemoryCheck");
	if(!is_numeric($MySqlMemoryCheck)){$MySqlMemoryCheck=0;}
	if($MySqlMemoryCheck==0){
		$status_computer_mysql_memory_check=status_computer_mysql_memory_check();
		if($status_computer_mysql_memory_check<>null){$status_computer_mysql_memory_check=$status_computer_mysql_memory_check."<br>";}
	}
	

	
	if(is_file("ressources/logs/zarafa.notify.MySQLIssue")){
		echo "<center>".$tpl->_ENGINE_parse_body(
		Paragraphe('danger64.png',"{ZARAFA_ISSUE}",
		@file_get_contents("ressources/logs/zarafa.notify.MySQLIssue"),null,null,420,80))."</center>";
		
	}
	
	if(is_file("ressources/logs/zarafa.notify.licenseover")){
		echo "<center>".$tpl->_ENGINE_parse_body(
		Paragraphe('danger64.png',"{ZARAFA_ISSUE}",
		@file_get_contents("ressources/logs/zarafa.notify.licenseover"),null,null,420,80))."</center>";
	
	}	
	
	$sql="SELECT count(*) FROM admin_cnx";
	OutputDebugVerbose($sql);
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){
		if(preg_match("#Access denied for user#",$q->mysql_error)){
			$error=urlencode(base64_encode("$q->mysql_error"));
			return "
			$status_computer_mysql_memory_check
			<script>
				Loadjs('admin.mysql.error.php?error=$error',true);
			</script>
			";
			
		}
		
		if(preg_match("#Unknown database.+?artica_.+?#",$q->mysql_error)){
			$q->CREATE_DATABASE("artica_events");
			$q->QUERY_SQL($sql,"artica_events");
		}
			
		if(preg_match("#Unknown database.+?artica_.+?#",$q->mysql_error)){	
			$q->BuildTables();
			$q=new mysql();
			$sql="SELECT count(*) FROM admin_cnx";
			$q->QUERY_SQL($sql,"artica_events");
			if(!$q->ok){
				$t2=time();
				$error=urlencode(base64_encode($q->mysql_error));
				return $status_computer_mysql_memory_check."<center>".$tpl->_ENGINE_parse_body(
				Paragraphe('danger64.png',"{mysql_error} [".__LINE__."]",
				"$q->mysql_error","javascript:Loadjs('admin.mysql.restart.progress.php');","$q->mysql_error",420,80))."</center>
				<br>
				<script>
					function RefreshMySQL$t2(){ LoadAjaxTiny('admin-index-status-mysql','$page?admin-index-status-mysql=yes'); }
					setTimeout('RefreshMySQL$t2()',5000);
				</script>
				
				";
				return;
			}
		}
		
		if(preg_match("#table.+?admin_cnx.+?doesn.*?exist#i",$q->mysql_error)){
			$q=new mysql();
			$q->BuildTables();
			$sql="SELECT count(*) FROM admin_cnx";
			$q->QUERY_SQL($sql,"artica_events");
			if(!$q->ok){
				return $status_computer_mysql_memory_check."<center>".
				$tpl->_ENGINE_parse_body(Paragraphe('danger64.png',"{mysql_error} [".__LINE__."]",
						"$q->mysql_error","javascript:Loadjs('admin.mysql.restart.progress.php')","$q->mysql_error",420,80)).
				"</center><br>";
				return;
			}

			
			
			
			
			
		}
		
		
		if(trim($q->mysql_error)<>null){
			$t2=time();
			$error=urlencode(base64_encode($q->mysql_error));
			return $status_computer_mysql_memory_check."
				<center>".
				RoundedLightGrey($tpl->_ENGINE_parse_body(Paragraphe('danger64.png',"nowrap:{mysql_error} [".__LINE__."]","
						$q->mysql_error<br>{please_wait}...","javascript:Loadjs('admin.mysql.restart.progress.php');","$q->mysql_error",330,80)))."
				</center>
				<script>
					function RefreshMySQL$t2(){
						LoadAjaxTiny('admin-index-status-mysql','$page?admin-index-status-mysql=yes');
					}
					setTimeout('RefreshMySQL$t2()',5000);
				</script>
				<br>";
		}
		
	}

	return $status_computer_mysql_memory_check;
	
}




function status_right(){
	$t=time();
	$page=CurrentPageName();
	if(isset($_GET["newfrontend"])){$newfrontend=true;}
	if($GLOBALS["AS_ROOT"]){$newfrontend=true;}
	$tpl=new templates();
	if(!$GLOBALS["AS_ROOT"]){
		if(is_file("/usr/share/artica-postfix/ressources/logs/web/admin.index.status.html")){
			$time=file_time_min_Web("/usr/share/artica-postfix/ressources/logs/web/admin.index.status.html");
			if($time<5){
				$data=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/admin.index.status.html");
				if(strlen($data)>45){
					echo $tpl->_ENGINE_parse_body($data);
					return;
				}
			}
		}
		$sock=new sockets();
		$sock->getFrameWork('cmd.php?ForceRefreshRight=yes');
		if($GLOBALS["VERBOSE"]){writelogs("[DEBUG] -> echo next scripts...",__FUNCTION__,__FILE__,__LINE__);}
	}
	
	if(!$newfrontend){
		$infos="LoadAjaxTiny('right-status-infos','admin.left.php?part1=yes');";
	}else{
		$ajaxadd="&newfrontend=yes";
	}
	
	echo "
	<div id='mem_status_computer' style='text-align:center;width:100%;margin:10px;margin-top:0px'></div>
	<div id='right-status-infos'></div>
	<div id='IMAGE_STATUS_INFO' style='width:100%;min-height:405px' class=form></div>	
	<script>
	LoadAjax('mem_status_computer','$page?memcomputer=yes$ajaxadd',true);
	
	function IMAGE_STATUS_INFO_$t(){
		LoadAjax('IMAGE_STATUS_INFO','admin.index.right-image.php',true);
	}
	
	setTimeout('IMAGE_STATUS_INFO_$t()',1500);
	</script>
	
	";
	
	
	
}

function DateDiff($debut, $fin) {

	if(preg_match("#([0-9]+)-([0-9]+)-([0-9]+)\s+([0-9]+):([0-9]+):([0-9]+)#",$debut,$re)){
		$t1=mktime($re[4], $re[5],$re[6], $re[2], $re[3], $re[1]);
	}
	
	if(preg_match("#([0-9]+)-([0-9]+)-([0-9]+)\s+([0-9]+):([0-9]+):([0-9]+)#",$fin,$re)){
		$t2=mktime($re[4], $re[5],$re[6], $re[2], $re[3], $re[1]);
	}	
	

  $t=$t1-$t2;
  if($t==0){return 0;};
  
  
  
  $diff = $t2 - $t1;
  
  return (($diff/60)+1);

}

function status_memdump_js(){
	$page=CurrentPageName();
	$html="
		var x_MemoryStatus= function (obj) {
			var results=obj.responseText;
			document.getElementById('mem_status_computer').innerHTML=results
		
		}	
	
	
		function MemoryStatus(){
			if(!document.getElementById('mem_status_computer')){return;}
			var XHR = new XHRConnection();
			XHR.appendData('memcomputer','yes');
			XHR.sendAndLoad('$page', 'GET',x_MemoryStatus);
		
		}
	MemoryStatus();";
	
	echo $html;
	
}


function status_memdump(){
	$sock=new sockets();
	$datas=$sock->getFrameWork("cmd.php?mempy=yes");
	$tbl=explode("\n",$datas);
	
	rsort($tbl);
	
	$html="<table class=form>";
	
	while (list ($num, $val) = each ($tbl) ){
		if(trim($val)==null){continue;}
		if(preg_match("#=\s+([0-9\.]+)\s+(MiB)\s+(.+)#",$val,$re)){
			$color=CellRollOver();
			if(intval($re[1])>50){$color="style='background-color:#F7D0CC;color:black'";}
			
			$html=$html."<tr $color>
				<td valign='top' width=1%><img src='img/status_service_run.png'></td>
				<td><strong style='font-size:13px'>{$re[3]}</strong></td>
				<td valign='top' width=1% nowrap><strong style='font-size:13px'>{$re[1]} {$re[2]}</strong></td>
				</tr>";
		}
	}
	
	$html="<H1>{memory_use}</H1>".RoundedLightWhite("<div style='width:100%;height:400Px;overflow:auto'>$html.</table></div>");
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	}
	
	









function warnings_delete_all(){
	$sql="TRUNCATE `notify`";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_events");
	}














function ShowFileLogs(){
	$file="ressources/logs/{$_GET["ShowFileLogs"]}";
	$datas=file_get_contents($file);
	$datas=htmlentities($datas);
	$datas=nl2br($datas);
	$html="
	<H3>{service_info}</H3>
	<div style='overflow-y:auto'>
	<code style='font-size:10px'>$datas</code>
	</div>
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
	
}


FUNCTION CheckTables(){
	$sql=new mysql();
	$sql->BuildTables();	
	
}

FUNCTION CheckDaemon(){
	$sock=new sockets();
	$sock->getfile('CheckDaemon');
	
}

function START_ALL_SERVICES(){
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?start-all-services=yes");
	$tpl=new templates();
	echo "alert('".$tpl->javascript_parse_text("{start_all_services_perform}")."');";
}

function EmergencyStart(){
	include_once(dirname(__FILE__)."/ressources/class.status.logs.inc");
	$service_cmd=$_GET["EmergencyStart"];
	$sock=new sockets();
	$datas=$sock->getfile("EmergencyStart:$service_cmd");
	$tbl=explode("\n",$datas);
		$tbl=array_reverse ($tbl, TRUE);		
		while (list ($num, $val) = each ($tbl) ){
			if(trim($val)<>null){
				if($arr[md5($val)]==true){continue;}
				$img=statusLogs($val);
			$html=$html . "
			<div style='black;margin-bottom:1px;padding:2px;border-bottom:1px dotted #CCCCCC;border-left:5px solid #CCCCCC;width:98%;'>
			<table style='width:100%'>
			<tr>
			<td width=1%><img src='img/fw_bold.gif'></td><td><code style='font-size:10px'>$val</code></td>
			</tr>
			</table>
			</div>";
			$arr[md5($val)]=true;
			
			}
		}	
		
		echo "<div style='width:100%;height:400px;overflow:auto;'>$html</div>";
	
}

function isoqlog(){
	
	
	echo "<input type='hidden' id='switch' value='{$_GET["main"]}'>";
	include_once('isoqlog.php');
	
	
}

function artica_meta(){
	$users=new usersMenus();
	$sock=new sockets();
	$q=new mysql();
	$DisableFrontArticaMeta=$sock->GET_INFO("EnableArticaMeta");
	if(!is_numeric($DisableFrontArticaMeta)){$DisableFrontArticaMeta=0;}
	$EnableArtica=$sock->GET_INFO("EnableArticaMeta");
	$sock=new sockets();
	$SambaEnabled=$sock->GET_INFO("SambaEnabled");
	if(!is_numeric($SambaEnabled)){$SambaEnabled=1;}	
	
	$ArticaMetaRemoveIndex=$sock->GET_INFO("ArticaMetaRemoveIndex");
	$DisableArticaMetaAgentInformations=$sock->GET_INFO("DisableArticaMetaAgentInformations");
	if($EnableArtica==null){$EnableArtica=1;}
	if($EnableArtica==1){
		if($ArticaMetaRemoveIndex<>1){
			$p=ParagrapheTEXT("artica-meta-32.png","{meta-console}","{meta-console-text}","javascript:Loadjs('artica.meta.php')",null,300);
		}
	}
	
	if($DisableArticaMetaAgentInformations==1){$p=null;}
	if($users->SAMBA_INSTALLED){
		if($SambaEnabled==1){
			$count=$q->COUNT_ROWS("smbstatus_users", "artica_events");
			if($count>0){
				$p1=ParagrapheTEXT("user-group-32.png", "$count {members_connected}", "{members_connected_samba_text}","javascript:Loadjs('samba.smbstatus.php',true)",null,300);
			}
		}
	}
	
	
	
	
	$html="$p1$p
	<script>
		CheckSquid();
	</script>
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}





?>