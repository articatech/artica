<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if($_GET["byminiadm"]<>null){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',"<p class='text-error'>");ini_set('error_append_string',"</p>");}

$GLOBALS["AS_ROOT"]=false;
if(function_exists("posix_getuid")){
	if(posix_getuid()==0){
		$GLOBALS["AS_ROOT"]=true;
		include_once(dirname(__FILE__).'/framework/class.unix.inc');
		include_once(dirname(__FILE__)."/framework/frame.class.inc");
		include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
		include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
		include_once(dirname(__FILE__)."/framework/class.settings.inc");
	}}


include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');



if(isset($argv[1])){
	if($argv[1]=="--squid-status"){ 
		ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
		section_status(true);
		status_squid_left(true); 
		all_status(true);
		ptx_status(true);
		section_architecture_status(true);
		die(); 
	}
}


	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsSquidAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_POST["ReconfigureUfdb"])){ReconfigureUfdb();exit;}
if(isset($_GET["services"])){section_services();exit;}
if(isset($_GET["status"])){status_start();exit;}
if(isset($_GET["status-left"])){status_squid_left();exit;}
if(isset($_GET["squid-mem-status"])){squid_mem_status();exit;}
if(isset($_GET["squid-info-status"])){squid_info_status();exit;}




if(isset($_GET["squid-stores-status"])){squid_stores_status();exit;}
if(isset($_GET["squid-ntlmauth-status"])){squid_ntlmauth_status();exit;}
if(isset($_GET["db-status-infos"])){db_status_info_text();exit;}

if(isset($_GET["squid-services"])){all_status();exit;}
if(isset($_GET["architecture-tabs"])){section_architecture_tabs();exit;}
if(isset($_GET["architecture-status"])){section_architecture_status();exit;}
if(isset($_GET["architecture-content"])){section_architecture_content();exit;}
if(isset($_GET["architecture-adv"])){section_architecture_advanced();exit;}
if(isset($_GET["architecture-users"])){section_architecture_users();exit;}
if(isset($_GET["architecture-filters"])){section_architecture_filters();exit;}
if(isset($_GET["ptx-status"])){ptx_status();exit;}
if(isset($_GET["members-status"])){section_members_status();exit;}
if(isset($_GET["members-content"])){section_members_content();exit;}



//ini_set('display_errors', 1);
//ini_set('error_reporting', E_ALL);
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
if(!$users->AsAnAdministratorGeneric){die("Not autorized");}
if(isset($_GET["off"])){off();exit;}
if(function_exists($_GET["function"])){call_user_func($_GET["function"]);exit;}

$page=CurrentPageName();
$tpl=new templates();
$sock=new sockets();
$users=new usersMenus();
$SQUIDEnable=trim($sock->GET_INFO("SQUIDEnable"));
if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
$StatsPerfsSquidAnswered=$sock->GET_INFO("StatsPerfsSquidAnswered");
if(!is_numeric($StatsPerfsSquidAnswered)){$StatsPerfsSquidAnswered=0;}
	if(!$users->PROXYTINY_APPLIANCE){
		if($DisableArticaProxyStatistics==0){
			if(!$users->WEBSTATS_APPLIANCE){if($StatsPerfsSquidAnswered==0){$CPU=$users->CPU_NUMBER;$MEM=$users->MEM_TOTAL_INSTALLEE;if(($CPU<4) AND (($MEM<3096088))){WARN_SQUID_STATS();die();}}}
		}
	}



$statisticsAdded=false;
if($SQUIDEnable==1){
	$tr[]=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("service-check-48.png", "services_status","system_information_text", "QuickLinkSystems('section_status')"));
}
$tr[]=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("48-parameters.png", "proxy_parameters","section_security_text", "QuickLinkSystems('section_architecture')"));
if($SQUIDEnable==1){
	if($users->AsSquidAdministrator){$tr[]=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("48-tasks.png", "tasks","", "QuickLinkSystems('section_tasks')"));}
	$tr[]=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("web-filtering-48.png", "WEB_FILTERING","softwares_mangement_text", "QuickLinkSystems('section_webfiltering_dansguardian')"));
}


if($users->KAV4PROXY_INSTALLED){
	$tr[]=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("bigkav-48.png", "APP_KAV4PROXY","softwares_mangement_text", "QuickLinkSystems('section_kav4proxy')"));
	
}
if($SQUIDEnable==1){
	$tr[]=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("statistics-48.png", "SQUID_STATS","proxyquicktext", "SquidQuickLinksStatistics()"));
	$statisticsAdded=true;
}





$count=1;

while (list ($key, $line) = each ($tr) ){if($line==null){continue;}$tr2[]=$line;}

if(count($tr2)<6){
	if($SQUIDEnable==1){
		$tr2[]=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("48-logs.png", "PROXY_EVENTS","PROXY_EVENTS", "QuickLinkSystems('section_squid_rtmm')"));
	}
}

$tr2[]=$tpl->_ENGINE_parse_body(quicklinks_paragraphe("web-site-48.png", "main_interface","main_interface_back_interface_text", "QuickLinksHide()"));

while (list ($key, $line) = each ($tr2) ){
	if($line==null){continue;}
	$f[]="<li id='kwick1'>$line</li>";
	$count++;
	
}




while (list ($key, $line) = each ($GLOBALS["QUICKLINKS-ITEMS"]) ){
	
	$jsitems[]="\tif(document.getElementById('$line')){document.getElementById('$line').className='QuickLinkTable';}";
}

$start="		
LoadQuickTaskBar();
setTimeout('QuickLinkMemory()',800);
";

if(isset($_GET["NoStart"])){$start=null;}


	
	$html="
            
	
	
	<div id='BodyContent' style='width:100%'></div>
	
	
	<script>
		function LoadQuickTaskBar(){
			$(document).ready(function() {
				$('#QuickLinksTop .kwicks').kwicks({max: 205,spacing:  5});
			});
		}
		
		function QuickLinksSamba(){
			Set_Cookie('QuickLinkCache', 'quicklinks.fileshare.php', '3600', '/', '', '');
			LoadAjax('BodyContent','quicklinks.fileshare.php');
		}
		
		function QuickLinksProxy(){
			Set_Cookie('QuickLinkCache', 'quicklinks.proxy.php', '3600', '/', '', '');
			LoadAjax('BodyContent','quicklinks.proxy.php');		
		
		}
		
		function QuickLinksKav4Proxy(){
			Set_Cookie('QuickLinksKav4Proxy', 'kav4proxy.php?inline=yes', '3600', '/', '', '');
			LoadAjax('BodyContent','kav4proxy.php?inline=yes');		
		
		}		
		
		
		
		function QuickLinkSystems(sfunction){
			if(sfunction=='section_squid_rtmm'){
				s_PopUp('squid.accesslogs.php?external=yes',1024,768);
				return;
			}
			
			LoadAjax('BodyContent','$page?function='+sfunction);
		}
		
		function QuickLinkMemory(){
			QuickLinkSystems('section_status');
			return;
		}
		
		
$start
	</script>
	";
	
	
	


$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);





function section_tasks(){echo "<script>LoadAjax('BodyContent','squid.statistics.tasks.php');QuickLinkShow('quicklinks-tasks');</script>";}

function section_webfiltering_dansguardian(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$html="
	<div id='QuicklinksDansguardian'></div>
	<script>
		LoadAjax('QuicklinksDansguardian','dansguardian2.php');
		QuickLinkShow('quicklinks-WEB_FILTERING');
		
	</script>
	
	";	
	
	echo $tpl->_ENGINE_parse_body($html);
	}	
	



function section_kav4proxy(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$html="
	<div id='QuicklinksKav4proxy'></div>
	<script>
		LoadAjax('QuicklinksKav4proxy','kav4proxy.php?inline=yes');
		QuickLinkShow('quicklinks-APP_KAV4PROXY');
	</script>
	
	";	
	
	echo $tpl->_ENGINE_parse_body($html);
	}

function section_members(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$html="
	
	
	<table style='width:100%'>
	<tbody>
		<tr>
			<td style='width:1%' valign='top'><div id='members-status'></div></td>
			<td style='width:99%;padding-left:10px' valign='top'>
			<div class=explain>{squid_members_explain}</div>
			<div id='members-content' class=form style='width:99%'></div></td>
		</tr>
	</tbody>
	</table>
	<script>
		LoadAjax('members-status','$page?members-status=yes');
		QuickLinkShow('quicklinks-members');
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function section_architecture_filters(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$html="
	<div class=explain>{squid_basic_filters_explain}</div>
	<div id='basic_filters-content'></div>	
	<script>
		LoadAjax('basic_filters-content','$page?basic_filters-tabs=yes');
	</script>
	";
		echo $tpl->_ENGINE_parse_body($html);
	
}


function section_architecture(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	echo "<div id='squid-section-architecture-$t'></div>
		<script>
		LoadAjax('squid-section-architecture-$t','$page?architecture-tabs=yes');
		QuickLinkShow('quicklinks-parameters');
	</script>
	
	";

}	
	
function section_architecture_start(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$html="
	<div class=explain>{squid_architecture_explain}</div>
	
	<table style='width:100%'>
	<tbody>
		<tr>
			<td style='width:1%' valign='top'><div id='architecture-status'></div></td>
			<td style='width:99%' valign='top'><div id='architecture-content'></div></td>
		</tr>
	</tbody>
	</table>
	<script>
		LoadAjax('architecture-status','$page?architecture-status=yes');
		QuickLinkShow('quicklinks-parameters');
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}




function section_members_content(){
	$page=CurrentPageName();
	$sock=new sockets();
	$users=new usersMenus();
	
	$authenticate_users=Paragraphe('members-priv-64.png','{authenticate_users}','{authenticate_users_text}',"javascript:Loadjs('squid.popups.php?script=ldap')");	
	$APP_SQUIDKERAUTH=Paragraphe('wink3_bg.png','{APP_SQUIDKERAUTH}','{APP_SQUIDKERAUTH_TEXT}',"javascript:Loadjs('squid.adker.php')");
	$blackcomputer=Paragraphe("64-black-computer.png","{black_ip_group}",'{black_ip_group_text}',"javascript:Loadjs('dansguardian.bannediplist.php');");
	$whitecomputer=Paragraphe("64-white-computer.png","{white_ip_group}",'{white_ip_group_text}',"javascript:Loadjs('dansguardian.exceptioniplist.php');");

	if(!$users->MSKTUTIL_INSTALLED){
		$APP_SQUIDKERAUTH=Paragraphe('wink3_bg-grey.png','{APP_SQUIDKERAUTH}','{APP_SQUIDKERAUTH_TEXT}',"javascript:Loadjs('squid.adker.php')");
	}
	if(strlen($users->squid_kerb_auth_path)<2){
		$APP_SQUIDKERAUTH=Paragraphe('wink3_bg-grey.png','{APP_SQUIDKERAUTH}','{APP_SQUIDKERAUTH_TEXT}',"javascript:Loadjs('squid.adker.php')");
	}	
	
	
	$import_tools=Paragraphe('64-import.png','{TEXT_TO_CSV}','{TEXT_MEMBERS_TO_CSV}',"javascript:Loadjs('csvToLdap.php')");
	
	
	

	$tr[]=$APP_SQUIDKERAUTH;
	$tr[]=$authenticate_users;
	$tr[]=$import_tools;
	$tr[]=$blackcomputer;
	$tr[]=$whitecomputer;
	
	$html=CompileTr3($tr);
	$tpl=new templates();
	$html= $tpl->_ENGINE_parse_body($html,'squid.index.php');
	SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$html);
	echo $html;		
	
		
}

function section_architecture_tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$sock=new sockets();
	$SQUIDEnable=trim($sock->GET_INFO("SQUIDEnable"));
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	
	if($SQUIDEnable==0){
		echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{proxy_service_is_disabled}<hr>		<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.newbee.php?js_enable_disable_squid=yes')\" style='font-size:22px;text-decoration:underline'>
		{enable_squid_service}</a>"));
		return;
	}
	
	
	$AsSquidLoadBalancer=$sock->GET_INFO("AsSquidLoadBalancer");
	if(!is_numeric($AsSquidLoadBalancer)){$AsSquidLoadBalancer=0;}
	
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}
	if($EnableWebProxyStatsAppliance==1){$AsSquidLoadBalancer=0;}	
	
	if($_GET["byminiadm"]<>null){
		$array["infrastructure"]='{infrastructure}';
		
	}
	
	
	$array["architecture-content"]='{main_parameters}';
	
	if($_GET["byminiadm"]<>null){$array["caches"]='{caches}';}
	
	
	if($AsSquidLoadBalancer==1){
		$array["load-balance"]='{load_balancer}';
	}
	
	if($_GET["byminiadm"]==null){
		$array["architecture-users"]='{users_interactions}';
	}
	
	$array["architecture-wpad"]='{autoconfiguration}';
	$array["architecture-adv"]='{advanced_options}';
	
	if($_GET["byminiadm"]<>null){
		
		include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
		$mini=new boostrap_form();
		while (list ($num, $ligne) = each ($array) ){
			
			if($num=="infrastructure"){
				$MINA[$ligne]="miniadmin.proxy.infrastructure.php?tabs=yes";
				continue;
			}
			
			if($num=="architecture-wpad"){continue;}
			
			if($num=="caches"){
				$MINA[$ligne]="miniadmin.proxy.caches.php";
				continue;
			}
			if($num=="load-balance"){
				$MINA[$ligne]="squid.loadbalancer.main.php?byQuicklinks=yes&byminiadm=yes";
				continue;
			}

			
			$MINA[$ligne]="$page?$num=yes";
		}
		if(!$users->NGINX_INSTALLED){
			$MINA["{proxy_behavior}"]='miniadmin.proxy.php?architecture-behavior=yes';
		}
		echo $mini->build_tab($MINA);
		return;
	}
	
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="caches"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.caches.php?byQuicklinks=yes\" style='font-size:16px'><span>$ligne</span></a></li>\n");
			continue;
			
		}
		
		if($num=="load-balance"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.loadbalancer.main.php\" style='font-size:16px'><span>$ligne</span></a></li>\n");
			continue;
			
		}

		if($num=="architecture-wpad"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.autoconfiguration.main.php\" style='font-size:16px'><span>$ligne</span></a></li>\n");
			continue;
				
		}		
		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$time\" style='font-size:16px'><span>$ligne</span></a></li>\n");
	}
	
	echo build_artica_tabs($html, "main_squid_quicklinks_tabs",950)."<script>LeftDesign('settings-white-256-opac20.png');</script>";


}

function section_architecture_advanced(){
	$sock=new sockets();
	$users=new usersMenus();
	$squid=new squidbee();
	$SquidActHasReverse=$sock->GET_INFO("SquidActHasReverse");
	if(!is_numeric($SquidActHasReverse)){$SquidActHasReverse=0;}
	
	$squid_reverse_proxy=Paragraphe('squid-reverse-64.png','{squid_reverse_proxy}','{squid_reverse_proxy_text}',"javascript:Loadjs('squid.reverse.proxy.php')");
	$squid_advanced_parameters=Paragraphe('64-settings.png','{squid_advanced_parameters}','{squid_advanced_parameters_text}',"javascript:Loadjs('squid.advParameters.php')");
	$squid_conf=Paragraphe('script-view-64.png','{configuration_file}','{display_generated_configuration_file}',"javascript:Loadjs('squid.conf.php')");
	$performances_tuning=Paragraphe('performance-tuning-64.png','{tune_squid_performances}','{tune_squid_performances_text}',"javascript:Loadjs('squid.perfs.php')");
	$denywebistes=Paragraphe("folder-64-denywebistes.png","{deny_websites}","{deny_websites_text}","javascript:Loadjs('squid.popups.php?script=url_regex');");

	$AsSquidLoadBalancerIcon=Paragraphe("load-blancing-64.png","{load_balancer}","{squid_load_balancer_text}",
	"javascript:Loadjs('squid.loadblancer.php');");
	$AsSquidLoadBalancer=$sock->GET_INFO("AsSquidLoadBalancer");
	if(!is_numeric($AsSquidLoadBalancer)){$AsSquidLoadBalancer=0;}
	
	$changepaths=Paragraphe("directories.png","{directories}","{change_directories_paths_text}",
	"javascript:Loadjs('squid.directories.php');");
	
	
	
	$squid=new squidbee();
	if($squid->isNGnx()){
		$users->SQUID_REVERSE_APPLIANCE=false;
		$squid_reverse_proxy=null;
		$SquidActHasReverse=0;
		
	}
	
	if($users->SQUID_REVERSE_APPLIANCE){$squid_reverse_proxy=null;$SquidActHasReverse=1;}
	
	
	
	if($SquidActHasReverse==1){
		$AsSquidLoadBalancer=0;
    	$squid_accl_websites=Paragraphe('website-64.png','{squid_accel_websites}','{squid_accel_websites_text}',"javascript:Loadjs('squid.reverse.websites.php')");
    }
    
    $redirectors_options=Paragraphe('redirector-64.png','{squid_redirectors}','{squid_redirectors_text}',
    "javascript:Loadjs('squid.redirectors.php')");  

    
    $memory_option=Paragraphe('bg_memory-64.png','{cache_mem}','{cache_mem_text}',
    "javascript:Loadjs('squid.cache_mem.php')");  
    
    
    $dns_servers=Paragraphe('dns-64.png','{dns_servers}','{dns_servers_text}',"javascript:Loadjs('squid.popups.php?script=dns')");
    
   
    $syslogMAC=Paragraphe("syslog-64.png", "{ComputerMacAddress}", "{squid_ComputerMacAddress_text}","javascript:Loadjs('squid.macaddr.php')");
    $syslogFQDN=Paragraphe("syslog-64.png", "{log_hostnames}", "{log_hostnames_text}","javascript:Loadjs('squid.loghostname.php')");
    
    $sarg=Paragraphe('sarg-logo.png','{APP_SARG}','{APP_SARG_TXT}',"javascript:Loadjs('sarg.php')","{APP_SARG_TXT}");
    
    $disable_stats=Paragraphe('statistics-64.png','{ARTICA_STATISTICS}','{ARTICA_STATISTICS_TEXT}',
    		"javascript:Loadjs('squid.artica.statistics.php')","{ARTICA_STATISTICS_TEXT}");
    
    $anonym=Paragraphe("hide-64.png", "{anonymous_browsing}", "{anonymous_browsing_explain}","javascript:Loadjs('squid.anonymous.php')");
    
    $csvstats=Paragraphe("csv-64.png", "{squid_csv_logs}", "{squid_csv_logs_explain}","javascript:Loadjs('squid.csv.php')");
    

     
    $snmp=Paragraphe("64-snmp.png", "SNMP", "{squid_snmp_explain}",
    "javascript:Loadjs('squid.snmp.php')");
    
    
    $forwarded_for=Paragraphe("icon-html-64.png", "x-Forwarded-For", "{x-Forwarded-For_explain}",
    "javascript:Loadjs('squid.forwarded_for.php')");
    

    

    
    


    if($users->PROXYTINY_APPLIANCE){$disable_stats=null;}
    $denywebistes=null;
    if($SquidActHasReverse==1){
    	
    	$squid_parent_proxy=null;
    	$redirectors_options=null;
    	$loadbalancing=null;
    	$AsSquidLoadBalancer=null;
    }
    
    if($AsSquidLoadBalancer==1){
    	$loadbalancing=null;
    	$anonym=null;
    	$redirectors_options=null;
    	$squid_reverse_proxy=null;
    	$squid_parent_proxy=null;
    }
    
    if($users->SQUID_REVERSE_APPLIANCE){
    	$squid_accl_websites=null;
    }
    
    
    $tr[]=$squid_conf;
    $tr[]=$squid_advanced_parameters;
    $tr[]=$memory_option;
    $tr[]=$changepaths;
    $tr[]=$file_descriptors;
    $tr[]=$timeouts;
    $tr[]=$forwarded_for;
    $tr[]=$dns_servers;
    $tr[]=$performances_tuning;
    $tr[]=$AsSquidLoadBalancerIcon;
    $tr[]=$loadbalancing;
    $tr[]=$redirectors_options;
    $tr[]=$denywebistes;
    $tr[]=$anonym;
    $tr[]=$syslog;
    $tr[]=$syslogMAC;
    $tr[]=$syslogFQDN;
    $tr[]=$snmp;
    $tr[]=$disable_stats;
    $tr[]=$sarg;
    $tr[]=$csvstats;
    $tr[]=$squid_parent_proxy;
    $tr[]=$squid_reverse_proxy;
    $tr[]=$squid_accl_websites;
    $tr[]=$CacheManagement2;
    
    
    $html=CompileTr4($tr);
    
	
	$tpl=new templates();
	$html= $tpl->_ENGINE_parse_body($html,'squid.index.php');
	SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$html);
	echo $html;		
}



function section_architecture_users(){
	$sock=new sockets();
	$squid=new squidbee();
	$authenticate_users=Paragraphe('members-priv-64.png','{authenticate_users}','{authenticate_users_text}',"javascript:Loadjs('squid.popups.php?script=ldap')");	
	$APP_SQUIDKERAUTH=Paragraphe('wink3_bg.png','{APP_SQUIDKERAUTH}','{APP_SQUIDKERAUTH_TEXT}',"javascript:Loadjs('squid.adker.php')");
	//$blackcomputer=Paragraphe("64-black-computer.png","{black_ip_group}",'{black_ip_group_text}',"javascript:Loadjs('dansguardian.bannediplist.php');");
	//$whitecomputer=Paragraphe("64-white-computer.png","{white_ip_group}",'{white_ip_group_text}',"javascript:Loadjs('dansguardian.exceptioniplist.php');");
    
   
	$APP_SQUIDKERAUTH=Paragraphe('wink3_bg.png','{APP_SQUIDKERAUTH}','{APP_SQUIDKERAUTH_TEXT}',"javascript:Loadjs('squid.adker.php')");
	$forwarded_for=Paragraphe("icon-html-64.png", "x-Forwarded-For", "{x-Forwarded-For_explain}",
			"javascript:Loadjs('squid.forwarded_for.php')");
    $SquidActHasReverse=$sock->GET_INFO("SquidActHasReverse");
    if($squid->isNGnx()){$SquidActHasReverse=0;}
    if(!is_numeric($SquidActHasReverse)){$SquidActHasReverse=0;}
    $import_tools=Paragraphe('64-import.png','{TEXT_TO_CSV}','{TEXT_MEMBERS_TO_CSV}',"javascript:Loadjs('csvToLdap.php')");

    
    $SESSIONS_MANAGER=Paragraphe('64-smtp-auth.png','{APP_SQUID_SESSION_MANAGER}','{APP_SQUID_SESSION_MANAGER_TEXT}',"javascript:Loadjs('squid.sessions.php')");
	$SESSIONS_MANAGER=Paragraphe('64-smtp-auth-grey.png','{APP_SQUID_SESSION_MANAGER}','{APP_SQUID_SESSION_MANAGER_TEXT}',"");
	$ISP_MODE=Paragraphe('isp-64.png','{SQUID_ISP_MODE}','{SQUID_ISP_MODE_EXPLAIN}',"javascript:Loadjs('squid.isp.php')");

   // $WEB_AUTH=Paragraphe('webfilter-64.png','{HotSpot}','{HotSpot_text}',
  //  "javascript:Loadjs('squid.webauth.php')");
    
    if($SquidActHasReverse==1){
    	$proxy_pac=Paragraphe('user-script-64-grey.png','{proxy_pac}','{proxy_pac_text}');
    	
    	$APP_SQUIDKERAUTH=Paragraphe('wink3_bg-grey.png','{APP_SQUIDKERAUTH}','{APP_SQUIDKERAUTH_TEXT}',
    	"javascript:Loadjs('squid.adker.php')");
    	$WEB_AUTH=null;
    	$ISP_MODE=null;
    	
    }    
    
    $tr[]=$SESSIONS_MANAGER;
    $tr[]=$authenticate_users;
    $tr[]=$import_tools;
	$tr[]=$APP_SQUIDKERAUTH;
	//$tr[]=$WEB_AUTH;
	$tr[]=$ISP_MODE;
	$tr[]=$forwarded_for;
	
	
	$html=CompileTr3($tr);
	
	$t=time();
	echo "<div id='$t'></div>
	<script>
		LoadAjaxTiny('$t','squid.adker.php?status=yes&t=$t');
		QuickLinkShow('quicklinks-proxy_parameters');
	</script>
	
	";	
	
	$html=CompileTr4($tr);
	$tpl=new templates();
	$html= $tpl->_ENGINE_parse_body($html,'squid.index.php');
	SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$html);
	echo $html;		
}


function section_architecture_content(){
	
	if(GET_CACHED(__FILE__, __FUNCTION__,__FUNCTION__)){return;}
	
$page=CurrentPageName();
$sock=new sockets();
$users=new usersMenus();
$squid=new squidbee();
	$compilefile="ressources/logs/squid.compilation.params";
	if(!is_file($compilefile)){$sock->getFrameWork("squid.php?compil-params=yes");}
	$COMPILATION_PARAMS=unserialize(base64_decode(@file_get_contents($compilefile)));
	$SquidActHasReverse=$sock->GET_INFO("SquidActHasReverse");
	if(!is_numeric($SquidActHasReverse)){$SquidActHasReverse=0;}
	if($users->SQUID_REVERSE_APPLIANCE){$SquidActHasReverse=1;}
	if($squid->isNGnx()){$SquidActHasReverse=0;}
	
	$listen_port=Paragraphe('folder-network-64.png','{listen_port}','{listen_port_text}',"javascript:Loadjs('squid.popups.php?script=listen_port')");
	$listen_addr=Paragraphe('folder-network-64.png','{listen_address}','{squid_listen_text}',"javascript:Loadjs('squid.nic.php')");
	$visible_hostname=Paragraphe('64-work-station-linux.png','{visible_hostname}','{visible_hostname_intro}',"javascript:Loadjs('squid.popups.php?script=visible_hostname')");
	//$transparent_mode=Paragraphe('relayhost.png','{transparent_mode}','{transparent_mode_text}',"javascript:Loadjs('squid.newbee.php?squid-transparent-js=yes')");
	$your_network=Paragraphe('folder-realyrules-64.png','{your_network}','{your_network_text}',"javascript:Loadjs('squid.popups.php?script=network')");
    $stat_appliance=Paragraphe("64-dansguardian-stats.png","{STATISTICS_APPLIANCE}","{STATISTICS_APPLIANCE_TEXT}","javascript:Loadjs('squid.stats-appliance.php')");
	//$sslbump=Paragraphe('web-ssl-64.png','{squid_sslbump}','{squid_sslbump_text}',"javascript:Loadjs('squid.sslbump.php')");
	$watchdog=Paragraphe('service-check-64-grey.png','{squid_watchdog}','{squid_watchdog_text}',"");
	
	$syslogRemote=Paragraphe('syslog-64-client.png','{remote_statistics_server}','{remote_statistics_server_text}',"javascript:Loadjs('squid.remotestats.php')");
	
	
	$log_location=Paragraphe('syslog-64-client.png','{log_location}','{log_location_text}',
			"javascript:Loadjs('squid.varlog.php')");
	
	
	
	$ftp_user=Paragraphe('ftp-user-64.png','{squid_ftp_user}','{squid_ftp_user_text}',"javascript:Loadjs('squid.ftp.user.php')");
	$messengers=Paragraphe('messengers-64.png','{instant_messengers}','{squid_instant_messengers_text}',"javascript:Loadjs('squid.messengers.php')");	
		
	$enable_squid_service=Paragraphe('shutdown-green-64.png','{enable_squid_service}','{enable_squid_service_text}',"javascript:Loadjs('squid.newbee.php?js_enable_disable_squid=yes')");
    
    if(!isset($COMPILATION_PARAMS["enable-ssl"])){
    	$sslbump=Paragraphe('web-ssl-64-grey.png','{squid_sslbump}','{squid_sslbump_text}',"");
    }
    
    if($users->MONIT_INSTALLED){
    	$watchdog=Paragraphe('service-check-64.png','{squid_watchdog}','{squid_watchdog_text}',"javascript:Loadjs('squid.watchdog.php')");
 	}
 	
 	$booster=Paragraphe('perfs-64.png','{squid_booster}','{squid_booster_text}',
 			"javascript:Loadjs('squid.booster.php')");
 	
	$googlenossl=Paragraphe('google-64.png','{disable_google_ssl}','{disable_google_ssl_text}',
			"javascript:Loadjs('squid.google.ssl.php')");
 	
	if($SquidActHasReverse==1){
		$googlenossl=null;
		$messengers=null;
		$sslbump=null;
		$transparent_mode=null;
	}
	
	$sock=new sockets();
	$users=new usersMenus();
	$squid=new squidbee();
	$SquidActHasReverse=$sock->GET_INFO("SquidActHasReverse");
	if(!is_numeric($SquidActHasReverse)){$SquidActHasReverse=0;}
	
	$squid_reverse_proxy=Paragraphe('squid-reverse-64.png','{squid_reverse_proxy}','{squid_reverse_proxy_text}',"javascript:Loadjs('squid.reverse.proxy.php')");
	$squid_advanced_parameters=Paragraphe('64-settings.png','{squid_advanced_parameters}','{squid_advanced_parameters_text}',"javascript:Loadjs('squid.advParameters.php')");
	$squid_conf=Paragraphe('script-view-64.png','{configuration_file}','{display_generated_configuration_file}',"javascript:Loadjs('squid.conf.php')");
	$performances_tuning=Paragraphe('performance-tuning-64.png','{tune_squid_performances}','{tune_squid_performances_text}',"javascript:Loadjs('squid.perfs.php')");
	$denywebistes=Paragraphe("folder-64-denywebistes.png","{deny_websites}","{deny_websites_text}","javascript:Loadjs('squid.popups.php?script=url_regex');");
	
	$AsSquidLoadBalancerIcon=Paragraphe("load-blancing-64.png","{load_balancer}","{squid_load_balancer_text}",
			"javascript:Loadjs('squid.loadblancer.php');");
	$AsSquidLoadBalancer=$sock->GET_INFO("AsSquidLoadBalancer");
	if(!is_numeric($AsSquidLoadBalancer)){$AsSquidLoadBalancer=0;}
	
	
	$squid=new squidbee();
	if($squid->isNGnx()){
		$users->SQUID_REVERSE_APPLIANCE=false;
		$squid_reverse_proxy=null;
		$SquidActHasReverse=0;
	
	}
	
	if($users->SQUID_REVERSE_APPLIANCE){$squid_reverse_proxy=null;$SquidActHasReverse=1;}
	
	
	
	if($SquidActHasReverse==1){
		$AsSquidLoadBalancer=0;
		$squid_accl_websites=Paragraphe('website-64.png','{squid_accel_websites}','{squid_accel_websites_text}',"javascript:Loadjs('squid.reverse.websites.php')");
	}
	
	$redirectors_options=Paragraphe('redirector-64.png','{squid_redirectors}','{squid_redirectors_text}',
			"javascript:Loadjs('squid.redirectors.php')");
	
	
	$memory_option=Paragraphe('bg_memory-64.png','{cache_mem}','{cache_mem_text}',
			"javascript:Loadjs('squid.cache_mem.php')");
	
	
	$dns_servers=Paragraphe('dns-64.png','{dns_servers}','{dns_servers_text}',"javascript:Loadjs('squid.popups.php?script=dns')");
	
	$syslog=Paragraphe("syslog-64.png", "Syslog", "{squid_syslog_text}","javascript:Loadjs('squid.syslog.php')");
	$syslogMAC=Paragraphe("syslog-64.png", "{ComputerMacAddress}", "{squid_ComputerMacAddress_text}","javascript:Loadjs('squid.macaddr.php')");
	$syslogFQDN=Paragraphe("syslog-64.png", "{log_hostnames}", "{log_hostnames_text}","javascript:Loadjs('squid.loghostname.php')");
	
	
	$sarg=Paragraphe('sarg-logo.png','{APP_SARG}','{APP_SARG_TXT}',"javascript:Loadjs('sarg.php')","{APP_SARG_TXT}");
	
	$disable_stats=Paragraphe('statistics-64.png','{ARTICA_STATISTICS}','{ARTICA_STATISTICS_TEXT}',
			"javascript:Loadjs('squid.artica.statistics.php')","{ARTICA_STATISTICS_TEXT}");
	
	$anonym=Paragraphe("hide-64.png", "{anonymous_browsing}", "{anonymous_browsing_explain}","javascript:Loadjs('squid.anonymous.php')");
	
	$csvstats=Paragraphe("csv-64.png", "{squid_csv_logs}", "{squid_csv_logs_explain}","javascript:Loadjs('squid.csv.php')");
	

	 
	$snmp=Paragraphe("64-snmp.png", "SNMP", "{squid_snmp_explain}",
			"javascript:Loadjs('squid.snmp.php')");
	
	
	$forwarded_for=Paragraphe("icon-html-64.png", "x-Forwarded-For", "{x-Forwarded-For_explain}",
			"javascript:Loadjs('squid.forwarded_for.php')");
	

	
	
	$CacheManagement2=Paragraphe("web-site.png", "{CacheManagement2}", "{CacheManagement2_explain}",
			"javascript:Loadjs('squid.caches.ManagementChoose.php')");
	
	
	
	if($users->PROXYTINY_APPLIANCE){$disable_stats=null;}
	$denywebistes=null;
	if($SquidActHasReverse==1){
		 
		$squid_parent_proxy=null;
		$redirectors_options=null;
		$loadbalancing=null;
		$AsSquidLoadBalancer=null;
	}
	
	if($AsSquidLoadBalancer==1){
		$loadbalancing=null;
		$anonym=null;
		$redirectors_options=null;
		$squid_reverse_proxy=null;
		$squid_parent_proxy=null;
	}
	
	if($users->SQUID_REVERSE_APPLIANCE){
		$squid_accl_websites=null;
	}
	
	
	
	
	$tr[]=$your_network;
	$tr[]=$squid_conf;
	$tr[]=$squid_advanced_parameters;
	$tr[]=$visible_hostname;
	$tr[]=$log_location;
	$tr[]=$syslog;
	$tr[]=$syslogMAC;
	$tr[]=$syslogFQDN;
	
	$tr[]=$booster;
	$tr[]=$stat_appliance;
	$tr[]=$ftp_user;
	$tr[]=$messengers;
	$tr[]=$sslbump;
	$tr[]=$googlenossl;
	$tr[]=$enable_squid_service;
	
	$tr[]=$file_descriptors;
	$tr[]=$forwarded_for;
	$tr[]=$performances_tuning;
	$tr[]=$AsSquidLoadBalancerIcon;
	$tr[]=$loadbalancing;
	$tr[]=$redirectors_options;
	$tr[]=$denywebistes;
	$tr[]=$anonym;

	$tr[]=$snmp;
	$tr[]=$disable_stats;
	$tr[]=$csvstats;
	$tr[]=$squid_parent_proxy;
	$tr[]=$squid_reverse_proxy;
	$tr[]=$squid_accl_websites;
	$tr[]=$CacheManagement2;
	


	

	$html=CompileTr4($tr);
	
	
	
$tpl=new templates();
$html="<div id='architecture-status'></div>
$html
<script>
	LoadAjaxTiny('architecture-status','$page?architecture-status=yes');
	QuickLinkShow('quicklinks-proxy_parameters');
</script>";

$html=$tpl->_ENGINE_parse_body($html,'squid.index.php');
SET_CACHED(__FILE__,__FUNCTION__,__FUNCTION__,$html);
echo $html;	
	
}

function section_security(){
	
	$tr[]=kaspersky();
	$tr[]=statkaspersky();
	$tr[]=clamav();
	$tr[]=icon_troubleshoot();
	$tr[]=certificate();
	$tr[]=icon_externalports();
	$tr[]=incremental_backup();
$tables[]="<table style='width:99%' class=form><tr>";
$t=0;
while (list ($key, $line) = each ($tr) ){
		$line=trim($line);
		if($line==null){continue;}
		$t=$t+1;
		$tables[]="<td valign='top'>$line</td>";
		if($t==2){$t=0;$tables[]="</tr><tr>";}
		}

if($t<2){
	for($i=0;$i<=$t;$i++){
		$tables[]="<td valign='top'>&nbsp;</td>";				
	}
}	
	

$links=@implode("\n", $tables);
$heads=section_computer_header();
$html="
<table style='width:100%'>
<tr>
	<td valign='top'>$heads</td>
	<td valign='top'>$links</td>
</tr>
</table>
<script>
QuickLinkShow('quicklinks-services_status');
</script>
";

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function db_status_info_text(){
	
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	if($q->BD_CONNECT()){return;}
	$t=time();
		$img="status_postfix_bg_failed.png";
		$title="{MYSQL_ERROR}";
		$text_error_sql="<div style='width:93%' class=form>
		<table style='width:100%'>
		<tr>
		<td width=1% nowrap style='vertical-align:top'><img src='img/database-error-48.png'></td>
		<td style='color:#D70707;font-size:14px;font-weight:bold'>{APP_SQUID_DB}:<br>
		<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.mysql.php');\"
		style='font-size:14px;color:#D70707;text-decoration:underline'>$title</a><hr>
		$q->mysql_error
		</td>
		</tr>
		</table>
		<div style='text-align:right'>". imgtootltip("refresh-24.png","{refresh}","LoadAjax('db-status-infos','$page?db-status-infos=yes&force=true',true);")."</div>
		</div>";
		
		echo $tpl->_ENGINE_parse_body($text_error_sql);		
}

function status_squid_left($asroot=false){
	
	if(GET_CACHED(__FILE__, __FUNCTION__,__FUNCTION__)){return;}
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();

	include_once(dirname(__FILE__)."/ressources/class.status.inc");
	$sock=new sockets();
	$SquidBinIpaddr=$sock->GET_INFO("SquidBinIpaddr");
	if($SquidBinIpaddr==null){$SquidBinIpaddr="0.0.0.0";}
	$urgency_mode_img="20-check-grey.png";
	if($SquidBinIpaddr=="0.0.0.0"){$SquidBinIpaddr="{all}";}
	$CacheManagement2=$sock->GET_INFO("CacheManagement2");
	if(!is_numeric($CacheManagement2)){$CacheManagement2=0;}
	$squid=new squidbee();
	$q=new mysql();
	$master_version=$squid->SQUID_VERSION;
	$text_kavicap_error=null;
	$text_script=null;
	$cache_mem=$squid->global_conf_array["cache_mem"];	
	$users=new usersMenus();
	
	$As32=false;
	if(!isset($_GET["uuid"])){$_GET["uuid"]=$sock->getframework("cmd.php?system-unique-id=yes");}
	

	$EnableKavICAPRemote=$sock->GET_INFO("EnableKavICAPRemote");
	$KavICAPRemoteAddr=$sock->GET_INFO("KavICAPRemoteAddr");
	$KavICAPRemotePort=$sock->GET_INFO("KavICAPRemotePort");	
	if(!is_numeric($EnableKavICAPRemote)){$EnableKavICAPRemote=0;}
	
	
	if(!is_file("/usr/share/artica-postfix/ressources/interface-cache/CPU_NUMBER")){
		$sock=new sockets();
		$cpunum=intval($sock->getFrameWork("services.php?CPU-NUMBER=yes"));
	}else{
		$cpunum=intval(@file_get_contents("/usr/share/artica-postfix/ressources/interface-cache/CPU_NUMBER"));
	}
	
	$CPU_NUMBER=$cpunum;
	
	if($EnableKavICAPRemote==1){
		$fp=@fsockopen($KavICAPRemoteAddr, $KavICAPRemotePort, $errno, $errstr, 1);
			if(!$fp){
				$text_kavicap_error="<div>{kavicap_unavailable_text}<br><strong>
				<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.kavicap.php');\" style='font-size:12px;color:#D70707;text-decoration:underline'>$KavICAPRemoteAddr:$KavICAPRemotePort</a><br>$errstr</div>";				
			}
		
		@fclose($fp);			
	}
	
	$q=new mysql_squid_builder();
	
	$text_error_sql="<div id='db-status-infos'></div>
	<script>LoadAjax('db-status-infos','$page?db-status-infos=yes',true);</script>
	";
	
	
	
	$q=new mysql_squid_builder();
	$requests=$q->EVENTS_SUM();
	$requests=numberFormat($requests,0,""," ");
	
	
	$tableblock=date('Ymd')."_blocked";
	$ligneW=$q->COUNT_ROWS($tableblock);
	$blocked_today=numberFormat($ligneW,0,""," ")." {blocked_websites} {this_day}";
	
	$q=new mysql_squid_builder();
	$websitesnums=$q->COUNT_ROWS("dansguardian_sitesinfos","artica_backup");
	$websitesnums=numberFormat($websitesnums,0,""," ");	
	
	$q=new mysql_squid_builder();
	$categories=$q->COUNT_ROWS("dansguardian_community_categories");
	$categories=numberFormat($categories,0,""," ");		
	
	$sock=new sockets();
	$sock->SET_INFO("squidStatsCategoriesNum",$categories);
	$sock->SET_INFO("squidStatsWebSitesNum",$websitesnums);
	$sock->SET_INFO("squidStatsBlockedToday",$blocked_today);
	$sock->SET_INFO("squidStatsRequestNumber",$requests);
	$styleText="font-size:12px;font-weight:bold";
	$migration_pid=unserialize(base64_decode($sock->getFrameWork("squid.php?migration-stats=yes")));
	if(is_array($migration_pid)){
		$text_script="<span style='color:#B80000;font-size:13px'>{migration_script_run_text} PID:{$migration_pid[0]} {since}:{$migration_pid[1]}Mn</span>";
	}	
	
	
	$DisableSquidSNMPModeText="{disabled}";
	$DisableSquidSNMPModeCK="20-check-grey.png";
	$SquidEnableRockStoreCK="20-check-grey.png";
	if(preg_match("#^([0-9]+)\.([0-9]+)#", $master_version,$re)){
		$MAJOR=$re[1];
		$MINOR=$re[2];
		if($MAJOR>2){if($MINOR>1){$As32=true;}}
		$master_version_text="$MAJOR.$MINOR";
	}	
	
	if(preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)#", $master_version,$re)){
		$MAJOR=$re[1];
		$MINOR=$re[2];
		$REV=$re[3];
		$master_version_text="$MAJOR.$MINOR.$REV";
	}
	
	
	if($master_version_text==null){$master_version_text="Unknown";}
	
	
	
	if($As32){
		if($CPU_NUMBER>1){
			$SquidEnableRockStore=$sock->GET_INFO("SquidEnableRockStore");
			$SquidRockStoreSize=$sock->GET_INFO("SquidRockStoreSize");
			if(!is_numeric($SquidEnableRockStore)){$SquidEnableRockStore=0;}
			if(!is_numeric($SquidRockStoreSize)){$SquidRockStoreSize=2000;}
			
			
				$DisableSquidSNMPModeText=$CPU_NUMBER." cpu(s)";
				$DisableSquidSNMPModeCK="20-check.png";
			
			
			
			
			if($SquidEnableRockStore==1){
				$SquidRockStoreSize=FormatBytes($SquidRockStoreSize*1024);
				$SquidEnableRockStoreCK="20-check.png";
			}else{
				$SquidRockStoreSize="{disabled}";
			}
			
			$smptr="		
			<tr>
				<td width=1%><img src='img/$DisableSquidSNMPModeCK'></td>
				<td class=legend nowrap style='font-size:12px'>SMP:</td>
				<td style='font-size:14px'>
				<a href=\"javascript:blur();\"
				OnClick=\"javascript:Loadjs('squid.caches32.php?smp-js=yes&uuid={$_GET["uuid"]}');\"
				style='$styleText;text-decoration:underline'>$DisableSquidSNMPModeText</a></span></td>
			</tr>
			<tr>
				<td width=1%><img src='img/$SquidEnableRockStoreCK'></td>
				<td class=legend nowrap style='font-size:12px'>Rock store:</td>
				<td style='font-size:14px'>
				<a href=\"javascript:blur();\"
				OnClick=\"javascript:Loadjs('squid.rockstore.php');\"
				style='$styleText;text-decoration:underline'>$SquidRockStoreSize</a></td>
			</tr>";			
			
		}
	}
	
	
	$qs=new mysql();
	if(!$qs->FIELD_EXISTS("nics","ucarp-enable","artica_backup")){$qs->QUERY_SQL("ALTER TABLE `nics` ADD `ucarp-enable` smallint( 1 ) NULL DEFAULT '0'",'artica_backup'); }
	$sql="SELECT COUNT(*) as tcount FROM nics WHERE `ucarp-enable`=1";
	$ligne2=mysql_fetch_array($qs->QUERY_SQL($sql,"artica_backup"));
	$failover_icon="20-check-grey.png";
	if($ligne2["tcount"]==0){
		$failover_text="{disabled}";
	}else{
		$failover_text="{enabled}";
		$failover_icon="20-check.png";
	}
	if(!$users->UCARP_INSTALLED){
		$failover_text="-";
		$failover_icon="20-check-grey.png";
	}
	
	if($CacheManagement2==1){$smptr=null;}
	$DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
	if(!is_numeric($DisableAnyCache)){$DisableAnyCache=0;}
	$SquidCacheLevel=$sock->GET_INFO("SquidCacheLevel");
	if(!is_numeric($SquidCacheLevel)){$SquidCacheLevel=4;}
	if($SquidCacheLevel==0){$DisableAnyCache=1;}
	
	
	$hasProxyTransparent=$sock->GET_INFO("hasProxyTransparent");
	if(!is_numeric($hasProxyTransparent)){$hasProxyTransparent=0;}
	
	$hasProxyTransparentText="{disabled}";
	$hasProxyTransparentCheck="20-check-grey.png";
	
	$DisableAnyCacheText="{enabled}";
	$DisableAnyCacheCheck="20-check.png";
	
	if($hasProxyTransparent==1){
		$hasProxyTransparentText="{enabled}";
		$hasProxyTransparentCheck="20-check.png";
	}
	
	if($DisableAnyCache==1){
		$DisableAnyCacheText="{disabled}";
		$DisableAnyCacheCheck="20-check-grey.png";
		
	}else{
		$qN=new mysql();
		$ligne=mysql_fetch_array($qN->QUERY_SQL("SELECT SUM(cache_size) as size FROM squid_caches_center WHERE enabled=1","artica_backup"));
		$size=$ligne["size"];
		$DisableAnyCacheText=FormatBytes($size*1024);
	}	
	
	
	
	if(preg_match("#^([0-9]+)\s+#", $cache_mem)){
		$cache_mem2=$re[1];
		$cache_mem2=($cache_mem*1024);
		$cache_mem2=FormatBytes($cache_mem2);
	}
	$EnableCNTLM=$sock->GET_INFO("EnableCNTLM");
	$CNTLMPort=$sock->GET_INFO("CnTLMPORT");
	$EnableRDPProxy=$sock->GET_INFO("EnableRDPProxy");
	$SquidUrgency=$sock->GET_INFO("SquidUrgency");
	if(!is_numeric($SquidUrgency)){$SquidUrgency=0;}
	$urgency_mode_color=null;
	$urgency_text="{disabled}";
	
	if($SquidUrgency==1){
		$urgency_mode_color=";color:#BE0303";
		$urgency_mode_img="20-check-red.png";
		$urgency_text="{enabled}";
	}
	
	
	
	if(!is_numeric($EnableRDPProxy)){$EnableRDPProxy=0;}
	if(!is_numeric($EnableCNTLM)){$EnableCNTLM=0;}
	if(!is_numeric($CNTLMPort)){$CNTLMPort=3155;}
	
	$PP[]=$squid->listen_port;
	
	if(!is_numeric($squid->second_listen_port)){$squid->second_listen_port=0;}
	if($squid->second_listen_port>0){
		$PP[]=$squid->second_listen_port;
	}
	
	if($EnableCNTLM==0){
		if($CNTLMPort>0){
			$PP[]=$CNTLMPort;
		}
	}
	
	$transparent_mode="
		<tr>
			<td width=1%><img src='img/$hasProxyTransparentCheck'></td>
			<td class=legend nowrap style='font-size:12px'>{transparent}:</td>
			<td style='font-size:14px'>
			<a href=\"javascript:blur();\"
			OnClick=\"Loadjs('squid.newbee.php?squid-transparent-js=yes');\"
			style='$styleText;text-decoration:underline'>$hasProxyTransparentText</a></td>
		</tr>";	
	
	$DisableAnyCache="
		<tr>
			<td width=1%><img src='img/$DisableAnyCacheCheck'></td>
			<td class=legend nowrap style='font-size:12px'>{caches} {disk}:</td>
			<td style='font-size:14px'>
			<a href=\"javascript:blur();\"
			
			style='$styleText'>$DisableAnyCacheText</a></td>
		</tr>";	
	
	
	
	
	
	$CacheMemory="<tr>
			<td width=1%><img src='img/20-check.png'></td>
			<td class=legend nowrap style='font-size:12px'>{cache_memory}:</td>
			<td style='font-size:14px'><a href=\"javascript:blur();\" 
			OnClick=\"javascript:Loadjs('squid.cache_mem.php');\" 
			style='$styleText;text-decoration:underline'>{$cache_mem2}</a></td>
		</tr>";
	
	if($CacheManagement2==1){$CacheMemory=null;}
	$squidversion="	
	<center>
	<div class=form style='width:93%'>
	<table style='width:250px;margin-top:10px;' class='TableRemove TableMarged'>
	<tbody>
		<tr>
			<td colspan=3 style='font-size:14px;text-align:center;padding-bottom:10px'><a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('squid.popups.php?script=visible_hostname');\"
		style='font-size:14px;text-decoration:underline;'>$squid->visible_hostname</a>
			</td>
		</tr>	
	
	
		<tr>
			<td width=1%><img src='img/20-check.png'></td>
			<td class=legend nowrap style='font-size:12px'>Proxy {version}:</td>
			<td style='$styleText'><a href=\"javascript:blur();\" 
			OnClick=\"javascript:Loadjs('squid.compilation.status.php');\" 
			style='$styleText;text-decoration:underline'>$master_version_text</a></td>
		</tr>
		<tr>
			<td width=1%><img src='img/20-check.png'></td>
			<td class=legend nowrap style='font-size:12px'>{listen_addr}:</td>
			<td style='font-size:14px'><a href=\"javascript:blur();\" 
			OnClick=\"javascript:Loadjs('squid.nic.php');\" 
			style='$styleText;text-decoration:underline'>$SquidBinIpaddr</a></td>
		</tr>	

		<tr>
			<td width=1%><img src='img/$urgency_mode_img'></td>
			<td class=legend nowrap style='font-size:12px$urgency_mode_color'>{urgency_mode}:</td>
			<td style='$styleText'><a href=\"javascript:blur();\" 
			OnClick=\"javascript:Loadjs('squid.urgency.php');\" 
			style='$styleText;text-decoration:underline$urgency_mode_color'>$urgency_text</a></td>
		</tr>		
		
		$smptr
		$transparent_mode
		$DisableAnyCache
		$CacheMemory

		<tr>
			<td width=1%><img src='img/$failover_icon'></td>
			<td class=legend nowrap style='font-size:12px'>{failover2}:</td>
			<td style='font-size:14px'><a href=\"javascript:blur();\" 
			OnClick=\"javascript:Loadjs('squid.failover.php');\" 
			style='$styleText;text-decoration:underline'>{$failover_text}</a></td>
		</tr>	
		
		
		</tbody>
	</table>
	</div>
	</center>
	";
	
	if($users->WEBSTATS_APPLIANCE){$squidversion=null;}
	
	$design="
	$text_error_sql
	$text_script
	$text_kavicap_error
	$squidversion
	<div id='squid-plugins-activated'></div>
	<div style='width:100%;text-align:right'>". 
	imgtootltip("refresh-24.png","{refresh}",
			"LoadAjax('squid-status','squid.main.quicklinks.php?status=yes');")."
	</div>
	
	";
	
	$classform="class=form";
	$sock=new sockets();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
	if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}
	if($UnlockWebStats==1){$EnableRemoteStatisticsAppliance=0;}	
	
	
	if($EnableRemoteStatisticsAppliance==1){$classform=null;}	
	
	$html="
	$design
	<center>
	
		<div id='squid-status-stats' $classform style='width:90%'></div>
	</center>
	
	
	<script>
		
		LoadAjax('squid-services','$page?squid-services=yes');
		LoadAjax('squid-plugins-activated','dansguardian2.php?dansguardian-status=yes');
	</script>
	";
	

	if($asroot){
		SET_CACHED(__FILE__, __FUNCTION__, __FUNCTION__, $html);
		return;
	}
	
	$html=$tpl->_ENGINE_parse_body($html);
	SET_CACHED(__FILE__, __FUNCTION__, __FUNCTION__, $html);
	echo $html;
	
	
}


function status_start(){
	$page=CurrentPageName();	
	
	
	$html="
	
		<td width=1% valign='top' style='vertical-align:top;'><div id='squid-status'></div></td>
		<td width=99% valign='top' style='vertical-align:top;'>
		
	<table style='width:100%' class='TableRemove TableMarged'>
	<tr>
		<div id='squid-services'></div></td>
	</tr>
	</table>
	
	<script>
		LoadAjax('squid-status','$page?status-left=yes');
	</script>
	
	";
	
	echo $html;
	
	
	
}

function section_members_status(){
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();	
	$squid=new squidbee();
	$listen_port=$squid->listen_port;
	$ssl_port=$squid->ssl_port;
	$visible_hostname=$squid->visible_hostname;
	$hasProxyTransparent=$squid->hasProxyTransparent;
	if($hasProxyTransparent==1){$hasProxyTransparent="{yes}";}else{$hasProxyTransparent="{no}";}

	if($users->SQUID_REVERSE_APPLIANCE){
		$listen_port=80;
		$ssl_port=443;
	}
	
	if(!$squid->ACL_ARP_ENABLED){
		$arpinfos=
		"<table style='width:99%;margin-top:5px' class=form>
		<tbody>
		<tr>
			<td width:1% valign='top'><img src='img/warning-panneau-32.png'></td>
			<td><strong style='font-size:12px'>{no_acl_arp}</strong><br>
			<span style='font-size:11px'>{no_acl_arp_text}</span></td>
		</tr>
		</tbody>
		</table>";
		
		
	}else{
		
		$arpinfos=
		"<table style='width:99%' class=form>
		<tbody>
		<tr>
			<td width:1% valign='top'><img src='img/32-infos.png'></td>
			<td><strong style='font-size:12px'>{yes_acl_arp}</strong><br>
			<span style='font-size:11px'>{yes_acl_arp_text}</span></td>
		</tr>
		</tbody>
		</table>";		
		
		
	}

	if($squid->ICP_PORT>0){
		$icp_port="	<tr>
		<td class=legend nowrap style='font-size:12px'>{icp_port}:</td>
		<td>".texthref($icp_port,"Loadjs('squid.popups.php?script=listen_port')")."</td>
	</tr>";
	}
	
	if($squid->HTCP_PORT>0){
		$htcp_port="	<tr>
		<td class=legend nowrap style='font-size:12px'>{htcp_port}:</td>
		<td>".texthref($icp_port,"Loadjs('squid.popups.php?script=listen_port')")."</td>
	</tr>";
	}	
	
	if(strlen($visible_hostname)>10){$visible_hostname=substr($visible_hostname, 0,7)."...";}
	if($ssl_port>0){$listen_port="$listen_port/$ssl_port";}
	$html="<table style='width:99%' class=form>
	<tr>
		<td class=legend nowrap style='font-size:12px'>{version}:</td>
		<td>".texthref($squid->SQUID_VERSION,null)."</td>
	</tr>	
	<tr>
		<td class=legend nowrap style='font-size:12px'>{listen_port}:</td>
		<td>".texthref($listen_port,"Loadjs('squid.popups.php?script=listen_port')")."</td>
	</tr>$icp_port$htcp_port
	<tr>
		<td class=legend nowrap style='font-size:12px'>{visible_hostname}:</td>
		<td>".texthref($visible_hostname,"Loadjs('squid.popups.php?script=visible_hostname')")."</td>
	</tr>	
	<tr>
		<td class=legend nowrap style='font-size:12px'>{transparent_mode}:</td>
		<td>".texthref($hasProxyTransparent,"Loadjs('squid.newbee.php?squid-transparent-js=yes')")."</td>
	</tr>	
	
	</table>
	$arpinfos
	<script>
		LoadAjax('members-content','$page?members-content=yes');
	</script>
	";
	

	
	$html=$tpl->_ENGINE_parse_body($html);
	echo $html;
	
}


function section_architecture_status($asroot=false){
	
	$page=CurrentPageName();
	if(GET_CACHED(__FILE__, __FUNCTION__,__FUNCTION__)){return;}
	
	
	
	$page=CurrentPageName();
	$tpl=new templates();	
	$squid=new squidbee();
	$users=new usersMenus();
	$sock=new sockets();
	$listen_port=$squid->listen_port;
	$second_port=$squid->second_listen_port;
	$ssl_port=$squid->ssl_port;
	$visible_hostname=$squid->visible_hostname;
	$hasProxyTransparent=$squid->hasProxyTransparent;
	
	if($users->SQUID_REVERSE_APPLIANCE){
		$listen_port=80;
		$ssl_port=443;
	}
	
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
	if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}
	if($UnlockWebStats==1){$EnableRemoteStatisticsAppliance=0;}	
	
	$js1="Loadjs('squid.popups.php?script=listen_port')";
	$js2="Loadjs('squid.popups.php?script=visible_hostname')";
	$js3="Loadjs('squid.newbee.php?squid-transparent-js=yes')";
	
	if($EnableRemoteStatisticsAppliance==1){$js1=null;$js2=null;$js3=null;}
	
	$labelport="{listen_port}";
	if($hasProxyTransparent==1){$hasProxyTransparent="{yes}";}else{$hasProxyTransparent="{no}";}
	if($second_port>0){$second_port="/$second_port";$labelport="{listen_ports}";}else{$second_port=null;}
	
	if($squid->ICP_PORT>0){
		$second_port=$second_port."/icp:$squid->ICP_PORT";
	}
	
	if($squid->HTCP_PORT>0){
		$second_port=$second_port."/htcp:$squid->HTCP_PORT";
	}	
	
	if($ssl_port>0){$second_port="$second_port/$ssl_port&nbsp;(ssl)";}
	
	if(strlen($visible_hostname)>10){$visible_hostname=substr($visible_hostname, 0,7)."...";}
	
	$VER=$squid->SQUID_VERSION;
	if(preg_match("#([0-9\.]+)#", $VER,$re)){$VER=$re[1];}
	if($VER==null){$VER="Unknown";}
	$squid_version_text="<td class=legend nowrap style='font-size:12px'>{version}:</td>
		<td>".texthref($VER,"Loadjs('squid.compilation.status.php');")."</td>
		<td style='font-size:12px;font-weight:bold'>&nbsp;|&nbsp;</td>";
	
	$visible_hostname_text="<td class=legend nowrap style='font-size:12px;'>{visible_hostname}:</td>
		<td>".texthref($visible_hostname,"$js2","font-size:12px;text-decoration:underline;font-weight:bold")."</td>
		<td style='font-size:12px;font-weight:bold'>&nbsp;|&nbsp;</td>";
	
	if($users->WEBSTATS_APPLIANCE){$squid_version_text=null;$visible_hostname_text=null;}
	
	$html="
	<div style='width:98%' class=form>
	<table style='width:100%' class=TableRemove>
	<tr>
		$squid_version_text
		<td class=legend nowrap style='font-size:12px;'>$labelport:</td>
		<td style='font-size:12px;'>".texthref("$listen_port$second_port","$js1","font-size:12px;text-decoration:underline;font-weight:bold")."</td>
		<td style='font-size:12px;font-weight:bold'>&nbsp;|&nbsp;</td>
		$visible_hostname_text
		<td class=legend nowrap style='font-size:12px;'>{transparent_mode}:</td>
		<td style='font-size:12px;'>".texthref($hasProxyTransparent,"$js3","font-size:12px;text-decoration:underline;font-weight:bold")."</td>
	</tr>
	</table>
	</div>
	";
	
	SET_CACHED(__FILE__, __FUNCTION__,__FUNCTION__,$html);
if($asroot){

	return;
}
	
	$html=$tpl->_ENGINE_parse_body($html);
	echo $html;
	
}

function section_status($asroot=false){
	$page=CurrentPageName();
	if(GET_CACHED(__FILE__, __FUNCTION__,__FUNCTION__)){return;}
	$sock=new sockets();
	$users=new usersMenus();
	$q=new mysql_blackbox();
	$tpl=new templates();
	$language=$tpl->language;
	
	$DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
	if(!is_numeric($DisableAnyCache)){$DisableAnyCache=0;}	

	$SquidCacheLevel=$sock->GET_INFO("SquidCacheLevel");
	if(!is_numeric($SquidCacheLevel)){$SquidCacheLevel=4;}
	if($SquidCacheLevel==0){$DisableAnyCache=1;}
	
	
	$SQUIDEnable=trim($sock->GET_INFO("SQUIDEnable"));
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	$CacheManagement2=$sock->GET_INFO("CacheManagement2");
	if(!is_numeric($CacheManagement2)){$CacheManagement2=0;}
	$fontsize=14;
	$array["status"]="{services_status}";
	
	
	
	$array["architecture-content"]='{main_parameters}';
	$array["architecture-users"]='{users_interactions}';
	
	
// squid.timeouts.php
	
	
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	if($users->WEBSTATS_APPLIANCE){unset($array["events-squidcache"]);}
	
	//$array["graphs"]="{statistics}";
	
	
	$fontsize=18;
	
	
	
	if(isset($_GET["byminiadm"])){
		unset($array["events-squidaccess"]);
		unset($array["events-squidcache"]);
		unset($array["watchdog"]);
	}
	
	if($SQUIDEnable==0){
		echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{proxy_service_is_disabled}<hr>		<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.newbee.php?js_enable_disable_squid=yes')\" style='font-size:22px;text-decoration:underline'>
		{enable_squid_service}</a>"));
		return;
	}
	
	
	
	while (list ($num, $ligne) = each ($array) ){
		
		
		
		if($num=="squid-timeout"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'>
			<a href=\"squid.timeouts.php?popup=yes\"><span>$ligne</span></a></li>\n");
			continue;
		}	
		
		if($num=="listen-ports"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'>
			<a href=\"squid.ports.php\"><span>$ligne</span></a></li>\n");
			continue;			
		}
		

		if($num=="squid-dns"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'>
			<a href=\"squid.popups.php?content=dns\"><span>$ligne</span></a></li>\n");
			continue;
				
		}		
		
		if($num=="architecture-content"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'>
			<a href=\"squid.main.quicklinks.php?$num=yes\"><span>$ligne</span></a></li>\n");
			continue;
		}
		
		
		if($num=="architecture-users"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'>
			<a href=\"squid.main.quicklinks.php?$num=yes\"><span>$ligne</span></a></li>\n");
			continue;
		}
		
		
		if($num=="architecture-adv"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'>
			<a href=\"squid.main.quicklinks.php?$num=yes\"><span>$ligne</span></a></li>\n");
			continue;
		}
		
		
		
		if($num=="software-update"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'>
			<a href=\"squid.softwares.php\"><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="CacheManagement2"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'>
			<a href=\"squid.caches.rules.php?main-tabs=yes\"><span>$ligne</span></a></li>\n");
			continue;			
		}
		
		if($num=="events-squidaccess"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'>
				<a href=\"squid.accesslogs.php?table-size=942&url-row=555\">
					<span>$ligne</span></a></li>\n");
			continue;
		}		
		
		if($num=="watchdog"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"squid.watchdog-events.php\">
				<span>$ligne</span></a></li>\n");
			continue;
		}		
		
		if($num=="cached_items"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"squid.cached.itemps.php?hostid=localhost\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}		
		
		if($num=="graphs"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"squid.graphs.php\"><span>$ligne</span></a></li>\n");
			continue;
		}			
		
		if($num=="events-squidcache"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"squid.cachelogs.php\"><span>$ligne</span></a></li>\n");
			continue;
		}		
		
		$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"$page?$num=yes\">
				<span>$ligne</span></a></li>\n");
		//$html=$html . "<li><a href=\"javascript:LoadAjax('squid_main_config','$page?main=$num&hostname={$_GET["hostname"]}')\" $class>$ligne</a></li>\n";
			
		}
		
	$t=time();
	
	$html=build_artica_tabs($html, "squid_main_svc",1040)."
	<script>LeftDesign('proxy-white-256-opac20.png');</script>		
	";
	
	SET_CACHED(__FILE__, __FUNCTION__, __FUNCTION__, $html);
	
	if(!$asroot){
		echo $html;
		return;
	}
	
	
	
	
}

function squid_booster_smp(){
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->getFrameWork("squid.php?smp-booster-status=yes")));
	if(count($array)==0){return;}
	$html[]="
			<div style='min-height:115px'>
			<table>
			<tr><td colspan=2 style='font-size:14px;font-weight:bold'>Cache(s) Booster</td></tr>
			";
	while (list ($proc, $pourc) = each ($array)){
		$html[]="<tr>
				<td width=1% nowrap style='font-size:13px;font-weight:bold'>Proc #$proc</td><td width=1% nowrap>". pourcentage($pourc)."</td></tr>";
	}
	$html[]="</table></div>";
	
	return RoundedLightGreen(@implode("\n", $html));
}

function all_status($asroot=false){
	
	if($asroot){$GLOBALS["AS_ROOT"]=true;}
	
	if(!$GLOBALS["AS_ROOT"]){
		
		$sock=new sockets();
		$SquidUrgency=intval($sock->GET_INFO("SquidUrgency"));
		
		if($SquidUrgency==1){
			echo FATAL_ERROR_SHOW_128(
					"<div style='font-size:22px'>{proxy_in_emergency_mode}</div>
			<div style='font-size:18px'>{proxy_in_emergency_mode_explain}</div>
			<div style='text-align:right;margin-top:20px'><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.urgency.php?justbutton=yes');\"
			style='text-decoration:underline;font-size:26px'>{disable_emergency_mode}</a></div>
			");
		
		
		}
		
		
		
		if(is_file("/usr/share/artica-postfix/ressources/logs/web/squid.services.html")){
			$tpl=new templates();
			echo $tpl->_ENGINE_parse_body(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/squid.services.html"));
			return;
		}
	
	}
	
	if(GET_CACHED(__FILE__, __FUNCTION__,__FUNCTION__)){return;}
	
	$page=CurrentPageName();
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$tpl=new templates();
	$users=new usersMenus();
	$squid=new squidbee();
	$t=time();
	
	$APP_SAMBA_WINBIND=null;
	$winbind=null;
	$UseDynamicGroupsAclsTR=null;
	$ufdbbutt=null;
	$cicapButt=null;
	
	if(!isset($_GET["miniadmin"])){
	
	$SecondScript="
		function RefreshAdKer$t(){	
			LoadAjaxTiny('squid-adker-status','squid.adker.php?status=yes&t=squid-adker-status');
		}
		RefreshAdKer$t();
		setTimeout('RefreshAdKer$t()',2000);	
	";
	
	}
	
	



	$Authenticator_cacheFile="/usr/share/artica-postfix/ressources/logs/web/ntlmauthenticator.cache";
	$cacheSwap="/usr/share/artica-postfix/ressources/logs/web/squid_swap_status.html";
	$ini->loadString(base64_decode($sock->getFrameWork('cmd.php?squid-ini-status=yes')));
	
	
	
	$DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
	$SquidActHasReverse=$sock->GET_INFO("SquidActHasReverse");
	$AsSquidLoadBalancer=$sock->GET_INFO("AsSquidLoadBalancer");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric($DisableAnyCache)){$DisableAnyCache=0;}
	$SquidBoosterMem=$sock->GET_INFO("SquidBoosterMem");
	$WizardStatsApplianceSeen=$sock->GET_INFO("WizardStatsApplianceSeen");
	
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	if(!is_numeric($SquidBoosterMem)){$SquidBoosterMem=0;}
	if(!is_numeric($DisableAnyCache)){$DisableAnyCache=0;}
	if(!is_numeric($SquidActHasReverse)){$SquidActHasReverse=0;}	
	if(!is_numeric($AsSquidLoadBalancer)){$AsSquidLoadBalancer=0;}
	if(!is_numeric($AsSquidLoadBalancer)){$AsSquidLoadBalancer=0;}
	if(!is_numeric($WizardStatsApplianceSeen)){$WizardStatsApplianceSeen=0;}		
	$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
	if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}
	if($UnlockWebStats==1){$EnableRemoteStatisticsAppliance=0;}	
	
	$squid_status=DAEMON_STATUS_ROUND("SQUID",$ini,null,1);
	$dansguardian_status=DAEMON_STATUS_ROUND("DANSGUARDIAN",$ini,null,1);
	$kav=DAEMON_STATUS_ROUND("KAV4PROXY",$ini,null,1);
	$cicap=DAEMON_STATUS_ROUND("C-ICAP",$ini,null,1);
	$APP_PROXY_PAC=DAEMON_STATUS_ROUND("APP_PROXY_PAC",$ini,null,1);
	$APP_SQUIDGUARD_HTTP=DAEMON_STATUS_ROUND("APP_SQUIDGUARD_HTTP",$ini,null,1);
	$APP_UFDBGUARD=DAEMON_STATUS_ROUND("APP_UFDBGUARD",$ini,null,1);
	$APP_UFDBGUARD_CLIENT=DAEMON_STATUS_ROUND("APP_UFDBGUARD_CLIENT",$ini,null,1);
	$APP_UFDBCAT=DAEMON_STATUS_ROUND("APP_UFDBCAT",$ini,null,1);
	$APP_HYPERCACHE_WEB=DAEMON_STATUS_ROUND("APP_HYPERCACHE_WEB",$ini,null,1);
	$APP_FRESHCLAM=DAEMON_STATUS_ROUND("APP_FRESHCLAM",$ini,null,1);
	$APP_ARTICADB=DAEMON_STATUS_ROUND("APP_ARTICADB",$ini,null,1);
	$APP_SQUID_DB=DAEMON_STATUS_ROUND("APP_SQUID_DB",$ini,null,1);
	$APP_HAARP=DAEMON_STATUS_ROUND("APP_HAARP",$ini,null,1);
	$APP_CNTLM=DAEMON_STATUS_ROUND("APP_CNTLM",$ini,null,1);
	$APP_CNTLM_PARENT=DAEMON_STATUS_ROUND("APP_CNTLM_PARENT",$ini,null,1);
	$APP_SQUID_NAT=DAEMON_STATUS_ROUND("APP_SQUID_NAT",$ini,null,1);
	

	$CLAMAV=DAEMON_STATUS_ROUND("CLAMAV",$ini,null,1);
	$DNSCACHE=DAEMON_STATUS_ROUND("DNSMASQ_SQUID",$ini,null,1);
	$UCARP_MASTER=DAEMON_STATUS_ROUND("UCARP_MASTER",$ini,null,1);
	$UCARP_SLAVE=DAEMON_STATUS_ROUND("UCARP_SLAVE",$ini,null,1);
	$HOTSPOT_WWW=DAEMON_STATUS_ROUND("HOTSPOT_WWW",$ini,null,1);
	$HOTSPOT_FW=DAEMON_STATUS_ROUND("HOTSPOT_FW",$ini,null,1);
	$HOTSPOT_SERVICE=DAEMON_STATUS_ROUND("HOTSPOT_SERVICE",$ini,null,1);
	$APP_ZIPROXY=DAEMON_STATUS_ROUND("APP_ZIPROXY",$ini,null,1);
	$APP_SARG=DAEMON_STATUS_ROUND("APP_SARG",$ini,null,1);
	
	//$APP_CONNTRACKD=DAEMON_STATUS_ROUND("APP_CONNTRACKD",$ini,null,1);
	if($users->PROXYTINY_APPLIANCE){$APP_ARTICADB=null;}
	if($EnableRemoteStatisticsAppliance==1){$APP_ARTICADB=null;}
	$APP_FTP_PROXY=DAEMON_STATUS_ROUND("APP_FTP_PROXY",$ini,null,1);
	$CacheManagement2=$sock->GET_INFO("CacheManagement2");
	if(!is_numeric($CacheManagement2)){$CacheManagement2=0;}

	
	
	if($EnableKerbAuth==1){
		$APP_SAMBA_WINBIND=DAEMON_STATUS_ROUND("SAMBA_WINBIND",$ini,null,1);
	}	
	$tr[]="<div id='squid-mem-status'></div><script>LoadAjaxTiny('squid-mem-status','$page?squid-mem-status=yes');</script>";
	$tr[]="<div id='squid-stores-status'></div>";
	$tr[]="<div id='squid-info-status'></div>";
	
	
	
	if(is_file($cacheSwap)){
		$tr[]=@file_get_contents($cacheSwap);
	}
	
	
	if(is_file($Authenticator_cacheFile)){
		$tr[]="<div id='squid-ntlmauth-status'></div><script>LoadAjaxTiny('squid-ntlmauth-status','$page?squid-ntlmauth-status=yes');</script>";
	}
	
	$cacheFile="/usr/share/artica-postfix/ressources/logs/web/dnsperformances.cache";
	$data=@file_get_contents($cacheFile);
	if(strlen($data)>10){
		$tr[]=$data;
	}
	

	
	
	$md=md5(date('Ymhis'));
	if(!$users->WEBSTATS_APPLIANCE){
		$swappiness=intval($sock->getFrameWork("cmd.php?sysctl-value=yes&key=".base64_encode("vm.swappiness")));
		$sock=new sockets();
		$swappiness_saved=unserialize(base64_decode($sock->GET_INFO("kernel_values")));
		if(!is_numeric($swappiness_saved["swappiness"])){
			if($swappiness>30){
				$tr[]=DAEMON_STATUS_ROUND_TEXT("warning-panneau-42.png","{high_swap_value}",
				"{high_swap_value_text}","Loadjs('squid.perfs.php')");
			}
			
		}
		
		$q=new mysql();
		$SquidAsSeenCache=$sock->GET_INFO("SquidAsSeenCache");
		if(!is_numeric($SquidAsSeenCache)){$SquidAsSeenCache=0;}
		if($q->COUNT_ROWS("squid_speed", "artica_backup")==0){
			if($SquidAsSeenCache==0){
				$tr[]=DAEMON_STATUS_ROUND_TEXT("warning-panneau-42.png","{cached_rules_not_set}",
				"{cached_rules_not_set_explain}","Loadjs('squid.caches.rules.php')");
			}
				
		}
			
		$SquidAsSeenCacheCenter=$sock->GET_INFO("SquidAsSeenCacheCenter");
		if(!is_numeric($SquidAsSeenCacheCenter)){$SquidAsSeenCacheCenter=0;}
			
		if($CacheManagement2==0){
			if($SquidAsSeenCacheCenter==0){
				$tr[]=DAEMON_STATUS_ROUND_TEXT("48-infos.png","{CacheManagement2}",
						"{CacheManagement2_explain}","Loadjs('squid.caches.ManagementChoose.php')");
					
				}
				
			}
			
			
			if($WizardStatsApplianceSeen==0){
				$tr[]=DAEMON_STATUS_ROUND_TEXT("warning-panneau-42.png","{use_remote_server_stats}",
				"{use_remote_server_stats_explain}","Loadjs('squid.stats-appliance.php')");
				
			}
	}
	
	
	
	$CicapEnabled=0;
	if($users->C_ICAP_INSTALLED){
		$CicapEnabled=$sock->GET_INFO("CicapEnabled");
		if(!is_numeric($CicapEnabled)){$CicapEnabled=0;}
	}
	
	
	
		$squid_status=null;
		
		$ini=new Bs_IniHandler();
		if($GLOBALS["AS_ROOT"]){
			$unix=new unix();
			$php5=$unix->LOCATE_PHP5_BIN();
			exec("$php5 /usr/share/artica-postfix/exec.squid.smp.php --status 2>&1",$res);
			$ini->loadString(@implode("\n", $res));
			
		}else{
			$ini->loadString(base64_decode($sock->getFrameWork('squid.php?smp-status=yes')));
		}
		
		
		if(is_array($ini->_params)){
			while (list ($index, $line) = each ($ini->_params) ){
				if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::".__LINE__."::$index -> DAEMON_STATUS_ROUND<br>\n";}
				$tr[]=DAEMON_STATUS_ROUND($index,$ini,null,1);
				
			}
		}
		
	
	

	
	if($SquidBoosterMem>0){
		
			if($DisableAnyCache==0){
				$tr[]=squid_booster_smp();
			}
		
	}
	
	
	$tr[]=$squid_status;
	$tr[]=$APP_SQUID_NAT;
	$tr[]=$APP_HAARP;
	$tr[]=$APP_HYPERCACHE_WEB;
	$tr[]=$APP_SAMBA_WINBIND;
	$tr[]=$APP_CNTLM;
	$tr[]=$APP_CNTLM_PARENT;
	$tr[]=$dansguardian_status;
	$tr[]=$kav;
	$tr[]=$cicap;
	$tr[]=$DNSCACHE;
	$tr[]=$CLAMAV;
	$tr[]=$APP_PROXY_PAC;
	$tr[]=$APP_SQUIDGUARD_HTTP;
	$tr[]=$APP_SARG;
	$tr[]=$HOTSPOT_WWW;
	$tr[]=$HOTSPOT_SERVICE;
	
	$tr[]=$HOTSPOT_FW;
	$tr[]=$APP_ZIPROXY;
	$tr[]=$APP_UFDBGUARD;
	$tr[]=$APP_UFDBGUARD_CLIENT;
	$tr[]=$APP_UFDBCAT;
	$tr[]=$APP_ARTICADB;
	$tr[]=$APP_SQUID_DB;
	$tr[]=$APP_FTP_PROXY;

	$tr[]=$UCARP_MASTER;
	$tr[]=$UCARP_SLAVE;
	
	if(isset($_GET["miniadmin"])){
		echo $tpl->_ENGINE_parse_body(CompileTr3($tr,true));
		return;
		
	}
	$EnableUfdbGuard=intval($sock->EnableUfdbGuard());
	if(!is_numeric($EnableUfdbGuard)){$EnableUfdbGuard=0;}
	if(!$users->APP_UFDBGUARD_INSTALLED){$EnableUfdbGuard=0;}
	
	$tables[]="<div style='min-height:350px;'>
		<table style='width:100%' class='TableRemove TableMarged'><tr>";
	$t=0;
	while (list ($key, $line) = each ($tr) ){
			$line=trim($line);
			if($line==null){continue;}
			$t=$t+1;
			$tables[]="<td valign='top'>$line</td>";
			if($t==2){$t=0;$tables[]="</tr><tr>";}
			}
	
	if($t<2){
		for($i=0;$i<=$t;$i++){
			$tables[]="<td valign='top'>&nbsp;</td>";				
		}
	}
	

	

	$SquidBoosterMemText="
		<tr>
			<td width=1%><img src='img/memory-32.png'></td>
			<td><div id='ptx-status'></div></td>
		</tr>
	";
	
	
	
	if($EnableKerbAuth==1){	
		$winbind="
			<tr>
		<td width=1%><img src='img/32-logs.png'></td>
		<td nowrap><a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('winbindd.events.php');\"
		style='font-size:12px;text-decoration:underline'>{APP_SAMBA_WINBIND}</a></td>
		</tr>
	";

		$UseDynamicGroupsAcls=$sock->GET_INFO("UseDynamicGroupsAcls");
		if(!is_numeric($UseDynamicGroupsAcls)){$UseDynamicGroupsAcls=0;}
		
		if($UseDynamicGroupsAcls==1){
			$UseDynamicGroupsAclsTR="
			<tr>
		<td width=1%><img src='img/32-logs.png'></td>
		<td nowrap><a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('DynamicGroupsAcls.events.php');\"
		style='font-size:12px;text-decoration:underline'>{dynamicgroupsAcls_events}</a></td>
		</tr>
	";			
		}
		
		
	}
	
	if($EnableUfdbGuard==1){
		$ufdbbutt="
			<tr>
		<td width=1%><img src='img/service-check-32.png'></td>
		<td nowrap><a href=\"javascript:blur();\" 
		OnClick=\"javascript:ReconfigureUfdb();\" 
		style='font-size:12px;text-decoration:underline'>{reconfigure_webfilter_service}</a></td>
		</tr>	
	";
	}
	
	if($CicapEnabled==1){
		$cicapButt="
			<tr>
		<td width=1%><img src='img/icon-antivirus-32.png'></td>
		<td nowrap><a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('c-icap.index.php');\" 
		style='font-size:12px;text-decoration:underline'>{antivirus_parameters}</a></td>
		</tr>	
	";		
		
	}
	
	
	

$supportpckg="
			<tr>
		<td width=1%><img src='img/technical-support-32.png'></td>
		<td nowrap><a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('squid.support.package.php');\" 
		style='font-size:12px;text-decoration:underline'>{build_support_package}</a></td>
		</tr>	
	";	

$dns_query="
			<tr>
		<td width=1%><img src='img/dns-32.png' id='events-dns-32-squid'></td>
		<td nowrap><a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('system.dns.query.php?img=events-rotate-32-squid&src=events-dns-32-squid');\" 
		style='font-size:12px;text-decoration:underline'>{dns_query}</a></td>
		</tr>	
	";




$debug_compile="
			<tr>
		<td width=1%><img src='img/32-logs.png'></td>
		<td nowrap><a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('squid.debug.compile.php');\" 
		style='font-size:12px;text-decoration:underline'>{compile_in_debug}</a></td>
		</tr>	
	";	

$current_sessions="
			<tr>
		<td width=1%><img src='img/32-many-users.png'></td>
		<td nowrap><a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('squid.squidclient.clientlist.php');\" 
		style='font-size:12px;text-decoration:underline'>{display_current_sessions}</a></td>
		</tr>	
	";	

$squidconf="
			<tr>
		<td width=1%><img src='img/script-32.png'></td>
		<td nowrap><a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('squid.conf.php');\"
		style='font-size:12px;text-decoration:underline'>{configuration_file}</a></td>
		</tr>
	";




$performances="
			<tr>
		<td width=1%><img src='img/performance-tuning-32.png'></td>
		<td nowrap><a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('squid.squidclient.info.php');\" 
		style='font-size:12px;text-decoration:underline'>{display_performance_status}</a></td>
		</tr>	
	";	

$restart_all_services="	<tr>
		<td width=1%><img src='img/service-restart-32.png'></td>
		<td nowrap><a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('squid.restart.php');\" 
		style='font-size:12px;text-decoration:underline'>{restart_all_services}</a></td>
	</tr>
	";



$checkCaches="
	<tr>
		<td width=1%><img src='img/database-connect-32-2.png'></td>
		<td nowrap><a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('squid.restart.php?CheckCaches=yes');\"
		style='font-size:12px;text-decoration:underline'>{check_caches}</a></td>
	</tr>	";
	
$users=new usersMenus();

if($users->WEBSTATS_APPLIANCE){
	$squid_rotate=null;
	$debug_compile=null;
	$current_sessions=null;
	$restart_service_only=null;
	$performances=null;
	$SquidBoosterMemText=null;
	$supportpckg=null;
	$squidconf=null;
}

if($DisableAnyCache==1){
	$SquidBoosterMemText=null;
}

	$refresh=imgtootltip("refresh-32.png","{refresh}","LoadAjax('squid-services','$page?squid-services=yes&force=yes');");
	$tables[]="
	
	<div id='squid-adker-status'></div>
	</table>
	<div style='text-align:right;margin-top:-15px'>$refresh</div>
	</div>
	<table style='width:99%' class=form>
	<tr>
	<td valign='top' width='50%'>
		<table style='width:100%'>
	$squidconf
	$winbind
	$UseDynamicGroupsAclsTR
	$dns_query
	</table>
	</td>
	<td valign='top' width='50%'>
		<table style='width:100%'>
			
			
			$ufdbbutt
			$debug_compile
			$supportpckg		
			$cicapButt
			$current_sessions
			$performances
		</table>
	</td>
	</tr>
	</table>";
	
	if($asroot){
		$tables[]="<div style='width:100%;text-align:right'><i>". date("H:i:s")."</i></div>";
	}
	
	
	
	
	$html=@implode("\n", $tables)."
	<script>
	var x_ReconfigureUfdb= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		RefreshTab('squid_main_svc');
	}		
		
	function ReconfigureUfdb(){
			var XHR = new XHRConnection();
		    XHR.appendData('ReconfigureUfdb', 'yes');
		    AnimateDiv('squid-services');
		    XHR.sendAndLoad('$page', 'POST',x_ReconfigureUfdb); 
		
	}
	
	LoadAjaxTiny('ptx-status','$page?ptx-status=yes');

	$SecondScript
</script>	
";
	

	
if($GLOBALS["AS_ROOT"]){
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/squid.services.html", $html);
	@chmod("/usr/share/artica-postfix/ressources/logs/web/squid.services.html",0755);
	return;
}
	
	SET_CACHED(__FILE__, __FUNCTION__, __FUNCTION__, $html);
	if($asroot){ return; }		
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
	
function ptx_status($asroot=false){
	$page=CurrentPageName();
	if(GET_CACHED(__FILE__, __FUNCTION__,__FUNCTION__)){return;}
	
	
	$sock=new sockets();
	$DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
	if(!is_numeric($DisableAnyCache)){$DisableAnyCache=0;}
	
	$SquidCacheLevel=$sock->GET_INFO("SquidCacheLevel");
	if(!is_numeric($SquidCacheLevel)){$SquidCacheLevel=4;}
	if($SquidCacheLevel==0){$DisableAnyCache=1;}
	
	
	$SquidBoosterMem=$sock->GET_INFO("SquidBoosterMem");
	if(!is_numeric($SquidBoosterMem)){$SquidBoosterMem=0;}
		
		$ptxt="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('squid.booster.php');\" 
		style='font-size:12px;text-decoration:underline'>{squid_booster}</a>";
	
	if($SquidBoosterMem>0){
		if($DisableAnyCache==0){
			
				$pourc=$sock->getFrameWork("squid.php?boosterpourc=yes");
				$ptxt="
				<table>
					<tr>
						<td>$ptxt</td>
						<td>". pourcentage($pourc)."</td>
					</tr>
				</table>";
			}
		
		
	}	
	
	SET_CACHED(__FILE__, __FUNCTION__, __FUNCTION__, $ptxt."<script>UnlockPage();</script>");
	if($asroot){
		
		return;
	}
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($ptxt)."<script>UnlockPage();</script>";
	
}

function squid_ntlmauth_status(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$reboot=false;
	$cacheFile="/usr/share/artica-postfix/ressources/logs/web/ntlmauthenticator.cache";
	$ARRAY=unserialize(@file_get_contents($cacheFile));
	
	while (list ($CPU, $PRC) = each ($ARRAY) ){
		$TTR[]="<tr>
			<td style='font-weight:bold;font-size:12px' align='right'>CPU $CPU:</td>
			<td style='font-weight:bold;font-size:12px'><td>". pourcentage($PRC,10)."</td>
		</tr>
		<tr><td colspan=2>&nbsp;</td></tr>
		";
		
	}
	
	if(count($TTR)>0){
		echo $tpl->_ENGINE_parse_body(RoundedLightGreen("<div style='min-height:191px'>
		<table style='width:100%'>
				<tr><td colspan=3><span style='font-weight:bold;font-size:12px'>{ntlm_processes}</span></td></tr>
				".@implode($TTR, "\n")."</table></div>")."<br>");
	}
	
}

function dns_status(){
	
	
	
}

function squid_stores_status(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	

	
	
	$off="<script>
			UnlockPage();
			LoadAjaxTiny('squid-info-status','$page?squid-info-status=yes');
		</script>";
	$SquidUrgency=$sock->GET_INFO("SquidUrgency");
	if(!is_numeric($SquidUrgency)){$SquidUrgency=0;}
	if($SquidUrgency==1){
		$datas=RoundedLightGreen("<div style='min-height:147px'>
		<table style='width:100%'>
		<tR><td style='align:center'>
		<center>
		<center style='margin:10px;font-size:18px'>{urgency_mode}:{enabled}</center>
		
		</center>
		</td>
		</tr>
		</table></div>")."<br>$off";
		echo $tpl->_ENGINE_parse_body($datas);
		return;}
	
	$page=CurrentPageName();
	$reboot=false;	
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/squid_stores_status.html";
	if(!is_file($cachefile)){
		$datas=RoundedLightGreen("<div style='min-height:147px'>
		<table style='width:100%'>
		<tR><td style='align:center'>
		<center>		
		<center style='margin:10px;font-size:18px'>{caches_status}</center>
		". imgtootltip("64-refresh.png","{refresh}","Loadjs('squid.store.status.php',true)")."
		</center>
		</td>
		</tr>		
		</table></div>")."<br>$off";
		echo $tpl->_ENGINE_parse_body($datas);
		
		
		return;}
	echo $tpl->_ENGINE_parse_body(@file_get_contents($cachefile)).$off;
	
	
	
	
}

function squid_info_status(){
	
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/SQUID_MGR_INFO.DB";
	if(!is_file($cachefile)){return;}
	$array=unserialize(@file_get_contents($cachefile));
	if(count($array)<3){return;}
	
	$TOTAL_REQUESTS=FormatNumber($array["TOTAL_REQUESTS"]);
	$AVERAGE_REQUESTS=FormatNumber(round($array["AVERAGE_REQUESTS"]/60))."/s";
	$ALL_CACHES=FormatBytes($array["ALL_CACHES"]);
	$ALL_CACHES_PERC=$array["ALL_CACHES_PERC"];
	$MEM_POURC=$array["MEM_POURC"];
	$CPU_PERC=$array["CPU_PERC"];
	$html="
	<div style='min-height:191px'>
	<table style='width:100%'>
	<tr>
		<td style='font-weight:bold;font-size:12px' align='right'>{running}:</td>
		<td style='font-weight:bold;font-size:12px'>{since} {$array["D"]}</td>
	</tr>
	<tr>
		<td style='font-weight:bold;font-size:12px' align='right'>{total_requests}:</td>
		<td style='font-weight:bold;font-size:12px'>$TOTAL_REQUESTS&nbsp;($AVERAGE_REQUESTS)</td>
	</tr>			
			
	<tr>
		<td style='font-weight:bold;font-size:12px' align='right' nowrap>{caches}:</td>
		<td style='font-weight:bold;font-size:12px'>$ALL_CACHES</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>". pourcentage($ALL_CACHES_PERC)."</td>
	</tr>	
	<tr>
		<td style='font-weight:bold;font-size:12px' align='right' nowrap>{memory_cache}:</td>
		<td style='font-weight:bold;font-size:12px'>$MEM_POURC%</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>". pourcentage($MEM_POURC)."</td>
	</tr>			
	<tr>
		<td style='font-weight:bold;font-size:12px' align='right' nowrap>{cpu}:</td>
		<td style='font-weight:bold;font-size:12px'>$CPU_PERC</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>". pourcentage($CPU_PERC)."</td>
	</tr>	
	</table>
	<div style='text-align:right'><i style='text-align:right;font-size:11px'>{report}: {since}&nbsp;". distanceOfTimeInWords($array["F"],time())."</i></div>
	<div style='text-align:right'>". imgtootltip("20-refresh.png","{refresh}","LoadAjaxTiny('squid-info-status','$page?squid-info-status=yes');")."</div>			
	</div>";

	echo RoundedLightGreen($tpl->_ENGINE_parse_body($html));
	
	
	
}

function squid_mem_status(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/squid_mem_status.html";
	if(!is_file($cachefile)){
		echo "<script>LoadAjaxTiny('squid-stores-status','$page?squid-stores-status=yes');</script>";
		return;}
	echo $tpl->_ENGINE_parse_body(@file_get_contents($cachefile))."<script>LoadAjaxTiny('squid-stores-status','$page?squid-stores-status=yes');</script>";
	
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}

function ReconfigureUfdb(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?rebuild-filters=yes&force=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{apply_upgrade_help}");
	
}


function WARN_SQUID_STATS(){$t=time();$html="<div id='$t'></div><script>LoadAjax('$t','squid.warn.statistics.php');</script>";echo $html;}