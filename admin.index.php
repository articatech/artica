<?php
$GLOBALS["ICON_FAMILY"]="SYSTEM";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$GLOBALS["DEBUG_PRIVS"]=true;
include_once('ressources/class.templates.inc');
session_start();
include_once('ressources/class.html.pages.inc');
include_once('ressources/class.cyrus.inc');
include_once('ressources/class.main_cf.inc');
include_once('ressources/charts.php');
include_once('ressources/class.syslogs.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.os.system.inc');

//ini_set('display_errors', 1);
//ini_set('error_reporting', E_ALL);

if(isset($_GET["HideTips"])){HideTips();exit;}

$users=new usersMenus();
if(!$users->AsAnAdministratorGeneric){
	error_log("Redirect to miniadm.php in ".__FUNCTION__." file " .basename(__FILE__)." line ".__LINE__);
	writelogs("Redirect to miniadm.php",__FUNCTION__,__FILE__,__LINE__);header('location:miniadm.php');
	exit;
}

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
if($_GET["status"]=="left"){status_left();exit;}
if($_GET["status"]=="right"){status_right();exit;}



if(isset($_GET["postfix-status"])){POSTFIX_STATUS();exit;}
if(isset($_GET["AdminDeleteAllSqlEvents"])){warnings_delete_all();exit;}
if(isset($_GET["ShowFileLogs"])){ShowFileLogs();exit;}
if(isset($_GET["buildtables"])){CheckTables();exit;}
if(isset($_GET["CheckDaemon"])){CheckDaemon();exit;}
if(isset($_GET["EmergencyStart"])){EmergencyStart();exit;}
if(isset($_GET["memcomputer"])){status_computer();exit;}
if(isset($_GET["mem-dump"])){status_memdump();exit;}
if(isset($_GET["memory-status"])){status_memdump_js();exit;}
if(isset($_GET["artica-meta"])){artica_meta();exit;}
if(isset($_GET["admin-ajax"])){page($users);exit;}

page($users);


function HideTips(){
	$sock=new sockets();
	$sock->SET_INFO($_GET["HideTips"],1);
}

function warnings_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{$_GET["count"]} {warnings}");
	echo "YahooWinS('330','$page?warnings-popup=yes','$title');";
}
function json_error_js(){
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
		LoadAjax('StartStopService_popup','$page?StartStopService-perform=yes&cmd={$_GET["cmd"]}&typ={$_GET["typ"]}&apps={$_GET["apps"]}');
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
		$datas=$sock->getFrameWork("cmd.php?start-service-name=$cmd");
	}else{
		$datas=$sock->getFrameWork("cmd.php?stop-service-name=$cmd");
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
error_log(basename(__FILE__)." ".__FUNCTION__.'() line '. __LINE__);
$hash=$ldap->UserDatas($_SESSION["uid"]);
error_log(basename(__FILE__)." ".__FUNCTION__.'() line '. __LINE__);
if($hash["displayName"]==null){$hash["displayName"]="{Administrator}";}
$sock=new sockets();
$ou=$hash["ou"];
$users=new usersMenus();
error_log(basename(__FILE__)." ".__FUNCTION__.'() line '. __LINE__);

if(isset($_COOKIE["artica-template"])){
	if(is_file("ressources/templates/{$_COOKIE["artica-template"]}/JQUERY_UI")){
		$GLOBALS["JQUERY_UI"]=trim(@file_get_contents("ressources/templates/{$_COOKIE["artica-template"]}/JQUERY_UI"));
	}
}

if($users->KASPERSKY_SMTP_APPLIANCE){
	if($sock->GET_INFO("KasperskyMailApplianceWizardFinish")<>1){
		$wizard_kaspersky_mail_appliance="Loadjs('wizard.kaspersky.appliance.php');";
	}
}

	if($users->KASPERSKY_WEB_APPLIANCE){
		//$GLOBALS["CHANGE_TEMPLATE"]="squid.kav.html";
		//$GLOBALS["JQUERY_UI"]="kavweb";
	}

	if(isset($_GET["admin-ajax"])){$left_menus="LoadAjax('TEMPLATE_LEFT_MENUS','/admin.tabs.php?left-menus=yes');";}


$html="
<script>
	LoadAjax('middle','quicklinks.php');
	ChangeHTMLTitle();
</script>


";	
	
	
$tpl=new template_users($title,$html,$_SESSION,0,0,0,$cfg);
error_log(basename(__FILE__)." ".__FUNCTION__.'() line '. __LINE__);
$html=$tpl->web_page;
SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$html);
echo $html;	
return;	
	

$html="	
<script language=\"JavaScript\">       
var timerID  = null;
var timerID1  = null;
var tant=0;
var fire=0;
var loop=0;
var loop2=0;
var reste=0;
var mem_ossys=0;

function Loop(){
	loop = loop+1;
	loop2 = loop2+1;
	
	if(loop2>10){
		if(!IfWindowsOpen()){if(RunJgrowlCheck()){Loadjs('jGrowl.php');}}
		loop2=0;
	}
	
	
    fire=10-fire;
    if(loop<25){
    	setTimeout(\"Loop()\",5000);
    }else{
      loop=0;
      Loop();
    }
}

	function RunJgrowlCheck(){
		if(!document.getElementById('navigation')){return false;}
		if($('#jGrowl').size()==0){return true;}
		if($('#jGrowl').size()==1){return true;}
		return false;
	
	}

	function sysevents_query(){
		if(document.getElementById('q_daemons')){
			var q_daemons=document.getElementById('q_daemons').value;
			var q_lines=document.getElementById('q_lines').value;
			var q_search=document.getElementById('q_search').value;
			LoadAjax('events','$page?main=logs&q_daemons='+ q_daemons +'&q_lines=' + q_lines + '&q_search='+q_search+'&hostname={$_GET["hostname"]}');
			}
	
	}
	
	function LoadCadencee(){		
		Loadjs('jGrowl.php');	
		setTimeout(\"Loop()\",2000);
	}


	var x_{$idmd}ChargeLogs= function (obj) {
		var tempvalue=obj.responseText;
		document.getElementById('progression_js_left').innerHTML=tempvalue;
		}		
	
function LoadMemDump(){
		YahooWin(500,'$page?mem-dump=yes');
	}



function CheckDaemon(){
	var XHR = new XHRConnection();
	XHR.appendData('CheckDaemon','yes');
	XHR.sendAndLoad('$page', 'GET');
	}	


</script>	
	".main_admin_tabs()."
	
	<script>
	
		LoadCadencee();
		RTMMailHide();
		$wizard_kaspersky_mail_appliance
		$left_menus
		initMessagesTop();
	</script>
	{$arr[0]}
	";

$cfg["JS"][]=$arr[1];
$cfg["JS"][]="js/admin.js";

if(isset($_GET["admin-ajax"])){
	$tpl=new templates();
	$html=$tpl->_ENGINE_parse_body($html);
	SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__."-admin-ajax",$html);
	echo $html;
	exit;
}
error_log(basename(__FILE__)." ".__FUNCTION__.'() line '. __LINE__);
$tpl=new templates();
error_log(basename(__FILE__)." ".__FUNCTION__.'() line '. __LINE__);
$title=$tpl->_ENGINE_parse_body("<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('admin.chHostname.php');\" style='text-transform:lowercase;font-size:12px' >[<span id='hostnameInFront'>$usersmenus->hostname</span>]</a>&nbsp;{WELCOME} <span style='font-size:12px'>{$hash["displayName"]} </span>");

error_log(basename(__FILE__)." ".__FUNCTION__.'() line '. __LINE__);
if($users->KASPERSKY_SMTP_APPLIANCE){
	$title=$tpl->_ENGINE_parse_body("<span style='color:#005447'>{WELCOME}</span> <span style='font-size:13px;color:#005447'>For Kaspersky SMTP Appliance</span>&nbsp;|&nbsp;<span style='font-size:12px'>{$hash["displayName"]} - <span style='text-transform:lowercase'>$usersmenus->hostname</span></span>");
}
if($users->KASPERSKY_WEB_APPLIANCE){
	$title=$tpl->_ENGINE_parse_body("<span style='color:black'>{WELCOME}</span> <span style='font-size:13px;color:black'>For Kaspersky Web Appliance</span>&nbsp;|&nbsp;<span style='font-size:12px'>{$hash["displayName"]} - <span style='text-transform:lowercase'>$usersmenus->hostname</span></span>");
}
error_log(basename(__FILE__)." ".__FUNCTION__.'() line '. __LINE__);
if($users->ZARAFA_APPLIANCE){
	$title=$tpl->_ENGINE_parse_body("<span style='color:#005447'>{WELCOME}</span> <span style='font-size:13px;color:#005447'>For Zarafa Mail Appliance</span>&nbsp;|&nbsp;<span style='font-size:12px'>{$hash["displayName"]} - <span style='text-transform:lowercase'>$usersmenus->hostname</span></span>");
	
}

error_log(basename(__FILE__)." ".__FUNCTION__.'() line '. __LINE__);
$tpl=new template_users($title,$html,$_SESSION,0,0,0,$cfg);
error_log(basename(__FILE__)." ".__FUNCTION__.'() line '. __LINE__);
$html=$tpl->web_page;
SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$html);
echo $html;			
if($GLOBALS["VERBOSE"]){echo "<H1>Finish</H1>";}
error_log(basename(__FILE__)." ".__FUNCTION__.'() line '. __LINE__);	
	
}



function main_admin_tabs(){
	
	if(GET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__)){return null;}
	
	if($GLOBALS["VERBOSE"]){echo "<li>".__FUNCTION__." line:".__LINE__."</li>";}
	$array["t:frontend"]="{status}";
	$array["t:orgs"]="{organizations}";
	$users=new usersMenus();
	$sys=new syslogs();
	$artica=new artica_general();
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$array["t:graphs"]='{graphs}';	
	
	if($users->VPS_OPENVZ){
		$array["t:openvz"]='OpenVZ';	
	}
	
	if($artica->EnableMonitorix==1){$array["t:monitorix"]='{monitorix}';}
	if($users->WEBSTATS_APPLIANCE){$users->POSTFIX_INSTALLED=false;
	$array["t:remote-web-appliances"]='{appliances}';
	}
	
	
	if($users->POSTFIX_INSTALLED){
		$EnableArticaSMTPStatistics=$sock->GET_INFO("EnableArticaSMTPStatistics");
		if(!is_numeric($EnableArticaSMTPStatistics)){$EnableArticaSMTPStatistics=1;}
		$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
		if(!is_numeric($EnablePostfixMultiInstance)){$EnablePostfixMultiInstance=0;}		
		if($EnableArticaSMTPStatistics==1){	
			$array["t:emails_received"]="{emails_received}";
		}
		
		if($EnablePostfixMultiInstance==1){
			$array["t:multiple_instances"]="{multiple_instances}";
		}
		
		
	}

	
	$sock=new sockets();
	if($users->SQUID_INSTALLED){
		$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
		if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
		if($SQUIDEnable==1){
			$array["t:HTTP_FILTER_STATS"]="{MONITOR}";
			$array["t:HTTP_BLOCKED_STATS"]="{blocked_websites}";
		}
	}
	

if($users->KASPERSKY_SMTP_APPLIANCE){
	$array["t:kaspersky"]="Kaspersky";	
}else{
	$array["t:system"]="{webinterface}";
}	

if(count($array)<6){
	if($users->AsSystemAdministrator){$array["t:cnx"]="{connections}";}
}

$count=count($array);
//if($count<7){$array["add-tab"]="{add}&nbsp;&raquo;";}
$page=CurrentPageName();
$tpl=new templates();
$width="758px";
if(isset($_GET["tab-font-size"])){
	if($_GET["tab-font-size"]=="14px"){$_GET["tab-font-size"]="12px";}
	$style="style=font-size:{$_GET["tab-font-size"]}";
}
if(isset($_GET["tab-width"])){$width=$_GET["tab-width"];}
if(isset($_GET["newfrontend"])){$newfrontend="&newfrontend=yes";}
if(count($array)>7){$style="style=font-size:11px";}

	while (list ($num, $ligne) = each ($array) ){
		if(preg_match("#t:(.+)#",$num,$re)){
			$ligne=$tpl->javascript_parse_text($ligne);
			if(strlen($ligne)>15){$ligne=substr($ligne,0,12)."...";}
			
			if($re[1]=="cnx"){
				$html[]= "<li ><a href=\"admin.cnx.php?t=0$newfrontend\"><span $style>$ligne</span></a></li>\n";
				continue;
			}
			
			if($re[1]=="multiple_instances"){
				$html[]= "<li ><a href=\"postfix.multiple.instances.infos.php?iniline=yes$newfrontend\"><span $style>$ligne</span></a></li>\n";
				continue;
			}			
			
			if($re[1]=="remote-web-appliances"){
				$html[]= "<li ><a href=\"squid.statsappliance.clients.php\"><span $style>$ligne</span></a></li>\n";
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
	
	
$html= "
	<div id='mainlevel' style='width:$width;height:auto;'>
		<div id=admin_perso_tabs style='width:$width;height:auto;'>
			<ul>". implode("\n",$html)."</ul>
		</div>
	</div>
		<script>
		  $(document).ready(function() {
			$(\"#admin_perso_tabs\").tabs();});
		</script>";	

SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$html);
return $html;	
		
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

function status_computer_mysql_memory_check(){
	include_once('ressources/class.mysql-server.inc');
	$t=time();
	$instance_id=0;
	$page=CurrentPageName();
	$tpl=new templates();
	$mysql=new mysqlserver();
	$users=new usersMenus();
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
			<td valign='top'><a href=\"javascript:blur();\" OnClick=\"Loadjs('mysql.perfs.php');\" 
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
	$page=CurrentPageName();
	$newfrontend=false;if(isset($_GET["newfrontend"])){$newfrontend=true;}
	$sock=new sockets();
	$MySqlMemoryCheck=$sock->GET_INFO("MySqlMemoryCheck");
	if(!is_numeric($MySqlMemoryCheck)){$MySqlMemoryCheck=0;}
	if($MySqlMemoryCheck==0){
		$html=status_computer_mysql_memory_check();
		if($html<>null){echo $html;return;}
	}
	
	if(!$GLOBALS["VERBOSE"]){
		if(GET_CACHED(__FILE__, __FUNCTION__,"time",false,3)){return;}
		if(internal_load()>1.2){if(GET_CACHED(__FILE__, __FUNCTION__)){return;}}
	}
	
	if($newfrontend){
		$ajaxadd="&newfrontend=yes";
	}
	
	include_once("ressources/class.os.system.tools.inc");
	$html=status_mysql();
	$os=new os_system();
	$html=$html.RoundedLightGrey($os->html_Memory_usage())."<br>
	<script>
		LoadAjax('left_status','$page?status=left$ajaxadd');
	</script>
	
	
	";
	SET_CACHED(__FILE__, __FUNCTION__, $html);
	SET_CACHED(__FILE__, __FUNCTION__,"time", $html);
	echo $html;
}

function status_mysql(){
	$tpl=new templates();
	$q=new mysql();
	$sock=new sockets();
	
	
	$sql="SELECT count(*) FROM admin_cnx";
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){
		if(preg_match("#Access denied for user#",$q->mysql_error)){
			$error=urlencode(base64_encode("$q->mysql_error"));
			return "
			<script>
				Loadjs('admin.mysql.error.php?error=$error');
			</script>
			";return;
		}
		
		if(preg_match("#Unknown database.+?artica_.+?#",$q->mysql_error)){
			$q->BuildTables();
			$q=new mysql();
			$sql="SELECT count(*) FROM admin_cnx";
			$q->QUERY_SQL($sql,"artica_events");
			if(!$q->ok){
				return RoundedLightGrey($tpl->_ENGINE_parse_body(Paragraphe('danger64.png',"{mysql_error}","$q->mysql_error",null,"$q->mysql_error",330,80)))."<br>";
				return;
			}
		}
		
		if(preg_match("#table.+?admin_cnx.+?exist#",$q->mysql_error)){
			$q->BuildTables();
			$q=new mysql();
			$sql="SELECT count(*) FROM admin_cnx";
			$q->QUERY_SQL($sql,"artica_events");
			if(!$q->ok){
				return RoundedLightGrey($tpl->_ENGINE_parse_body(Paragraphe('danger64.png',"{mysql_error}","$q->mysql_error",null,"$q->mysql_error",330,80)))."<br>";
				return;
			}			
			
		}
		
		
		if(trim($q->mysql_error)<>null){
			return RoundedLightGrey($tpl->_ENGINE_parse_body(Paragraphe('danger64.png',"{mysql_error}","$q->mysql_error",null,"$q->mysql_error",330,80)))."<br>";
		}
		
	}	
	
}

function status_right_image(){
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();	
	if(!isset($_GET["status_right_image"])){if(GET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,false,2)){return null;}}
	include_once(dirname(__FILE__)."/ressources/class.browser.detection.inc");
	$users=new usersMenus();
	$tpl=new templates();
	$newfrontend=false;
	$sock=new sockets();
	
	$script="
	<script>
		LoadAjax('mem_status_computer','$page?memcomputer=yes');
	</script>
	";

	writelogs("Building status... ",__FUNCTION__,__FILE__,__LINE__);
	
	if(!$users->AsArticaAdministrator){
		if($GLOBALS["VERBOSE"]){writelogs("[DEBUG] -> Not an administrator, aborting !",__FUNCTION__,__FILE__,__LINE__);}
		die("<H2 style='color:red'>permission denied</H2>");}
		$page=CurrentPageName();
		if($GLOBALS["VERBOSE"]){echo "$page LINE:".__LINE__."\n";
	}

	if($users->WEBSTATS_APPLIANCE){
			$status=new status();
			$html=$tpl->_ENGINE_parse_body($status->WEBSTATS()).$script;
			echo $html."</div>";
			SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$html);
			return;	
		}
	
	
	if($users->ZARAFA_APPLIANCE){
		$status=new status();
		$html=$tpl->_ENGINE_parse_body($status->ZARAFA()).$script;
		SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$html);
		echo $html;
		return;
	}
	
	if($GLOBALS["VERBOSE"]){echo "[DEBUG] ". __FUNCTION__." $page LINE:".__LINE__."\n";}
	
	if($users->HAPRROXY_APPLIANCE){
		$status=new status();
		$html=$tpl->_ENGINE_parse_body($status->haproxy_status()).$script;
		SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$html);
		echo $html;
		return;
	}		
	
	if($users->LOAD_BALANCE_APPLIANCE){
		$status=new status();
		$html=$tpl->_ENGINE_parse_body($status->xr_status()).$script;
		SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$html);
		echo $html;
		return;
	}	
	
	if($GLOBALS["VERBOSE"]){echo "$page LINE:".__LINE__."\n";}
	
//if($GLOBALS["VERBOSE"]){writelogs("[DEBUG] -> Not an administrator, aborting !",__FUNCTION__,__FILE__,__LINE__);}
	
	
	if($users->POSTFIX_INSTALLED){
			if($GLOBALS["VERBOSE"]){echo "$page -> status_postfix() LINE:".__LINE__."\n";}
			$html= status_postfix().$script;
			SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$html);
			echo $html;	
			return null;
		}
	
	if($GLOBALS["VERBOSE"]){echo "$page LINE:".__LINE__."\n";}
		
	
	if($users->SQUID_INSTALLED){
		$SQUIDEnable=trim($sock->GET_INFO("SQUIDEnable"));
		if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
		if($SQUIDEnable==0){
			if($users->KASPERSKY_WEB_APPLIANCE){
				$html=status_kav4proxy().$script;
				SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$html);
				echo $html;	
				return null;
			}
			
		}
		
		if($users->KASPERSKY_WEB_APPLIANCE){echo status_squid_kav().$script;return;}
		$html=status_squid();
		SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$html);
		echo $html;				
		return null;
	}else{
		if($users->KASPERSKY_WEB_APPLIANCE){
			$html=status_kav4proxy().$script;
			SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$html);
			echo $html;	
			return;}
		
		
	}
	
	if($users->SAMBA_INSTALLED){
		$html=StatusSamba().$script;
		SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$html);
		echo $html;
		return;
	}
	
	
	if($users->APACHE_INSTALLED){
		$html=StatusApache().$script;
		SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$html);
		echo $html;
		return;}
}


function status_right(){
	$t=time();
	if(isset($_GET["newfrontend"])){$newfrontend=true;}
	$sock=new sockets();
	$sock->getFrameWork('cmd.php?ForceRefreshRight=yes');
	if($GLOBALS["VERBOSE"]){writelogs("[DEBUG] -> echo next scripts...",__FUNCTION__,__FILE__,__LINE__);}
	if(!$newfrontend){
		$infos="LoadAjaxTiny('right-status-infos','admin.left.php?part1=yes');";
	}else{
		$ajaxadd="&newfrontend=yes";
	}
	
	echo "
	<div id='mem_status_computer' style='text-align:center;width:100%;margin:10px'></div>
	\n
	<div id='right-status-infos'></div>
	<script>
		LoadAjax('mem_status_computer','$page?memcomputer=yes$ajaxadd');
	</script>
	<div id='IMAGE_STATUS_INFO' style='width:100%;min-height:295px' class=form>";
		status_right_image();
	echo "</div>";
}
	
	
function StatusSamba(){
	$page=CurrentPageName();
	$tpl=new templates();
	if($GLOBALS["VERBOSE"]){echo "$page LINE:".__LINE__."\n";}
	$status=new status();
	if($GLOBALS["VERBOSE"]){echo "$page LINE:".__LINE__."\n";}
	$html=$status->Samba_status();
	return $tpl->_ENGINE_parse_body($html);		
	
}


function StatusApache(){
	$page=CurrentPageName();
	$tpl=new templates();
	if($GLOBALS["VERBOSE"]){echo "$page LINE:".__LINE__."\n";}
	$status=new status();
	if($GLOBALS["VERBOSE"]){echo "$page LINE:".__LINE__."\n";}
	$html=$status->Apache_status();
	return $tpl->_ENGINE_parse_body($html);		
	
}


function status_kav4proxy(){
	$page=CurrentPageName();
	$tpl=new templates();
	if($GLOBALS["VERBOSE"]){echo "$page LINE:".__LINE__."\n";}
	$status=new status();
	if($GLOBALS["VERBOSE"]){echo "$page LINE:".__LINE__."\n";}
	$html=$status->kav4proxy_status();
	return $tpl->_ENGINE_parse_body($html);		
}

function status_squid(){
	$page=CurrentPageName();
	$tpl=new templates();
	if($GLOBALS["VERBOSE"]){echo "$page LINE:".__LINE__."\n";}
	$status=new status();
	if($GLOBALS["VERBOSE"]){echo "$page LINE:".__LINE__."\n";}
	$html=$status->Squid_status();
	return $tpl->_ENGINE_parse_body($html);	
}

function status_squid_kav(){
	$page=CurrentPageName();
	$tpl=new templates();
	if($GLOBALS["VERBOSE"]){echo "$page LINE:".__LINE__."\n";}
	$status=new status();
	if($GLOBALS["VERBOSE"]){echo "$page LINE:".__LINE__."\n";}
	$html=$status->Squid_status();
	return $tpl->_ENGINE_parse_body($html);	
}
	



function status_postfix(){
	$users=new usersMenus();
	
	$page=CurrentPageName();
	$tpl=new templates();

		$status=new status();
		$users=new usersMenus();
		$postfix=$status->Postfix_satus($users->ZARAFA_INSTALLED);	
	
	return $counter.$tpl->_ENGINE_parse_body($postfix)
	."<script>
		LoadAjax('mem_status_computer','$page?memcomputer=yes');
	</script>";
	
	
	;
	
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
	
	





function status_left(){
	$newfrontend=false;
	$t=time();
	if(isset($_GET["newfrontend"])){$newfrontend=true;}
	if($newfrontend){$ajaxadd="?$t=yes&newfrontend=yes";}
	$html="
	<div id='status-left'></div>
	<script>
		LoadAjax('status-left','admin.index.loadvg.php$ajaxadd');
		
		function ChargeLeftMenus$t(){
			var content=document.getElementById('admin-left-infos').innerHTML;
			if(content.length<50){
				LoadAjax('admin-left-infos','admin.index.status-infos.php$ajaxadd');
			}
		}
		function ChargeLeftMenus2$t(){
			LoadAjaxTiny('right-status-infos','admin.left.php?part1=yes');
		}
		setTimeout('ChargeLeftMenus$t()',1200);
		setTimeout('ChargeLeftMenus2$t()',5000);
	</script>
	
	";
	echo $html;
	
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
		$count=$q->COUNT_ROWS("smbstatus_users", "artica_events");
		if($count>0){
			$p1=ParagrapheTEXT("user-group-32.png", "$count {members_connected}", "{members_connected_samba_text}","javascript:Loadjs('samba.smbstatus.php')",null,300);
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