<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
include_once(dirname(__FILE__).'/ressources/class.html.pages.inc');
include_once(dirname(__FILE__).'/ressources/class.cyrus.inc');
include_once(dirname(__FILE__).'/ressources/class.main_cf.inc');
include_once(dirname(__FILE__).'/ressources/charts.php');
include_once(dirname(__FILE__).'/ressources/class.syslogs.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.stats-appliance.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.tools.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.catz.inc');

if(isset($_GET["filter2-section"])){filter2_section();exit;}
if(isset($_GET["infra-section"])){infra_section();exit;}
if(isset($_GET["control-section"])){control_section();exit;}
if(isset($_GET["monitor-section"])){monitor_section();exit;}
if(isset($_GET["update-section"])){update_section();exit;}
if(isset($_GET["debug-section"])){debug_section();exit;}

page();



function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$js_infrasection="LoadAjaxRound('infra-section','$page?infra-section=yes');";
	$js_filtersection="LoadAjaxRound('filter2-section','$page?filter2-section=yes');";
	$js_controlsection="LoadAjaxRound('control-section','$page?control-section=yes');";
	$js_monitorsection="LoadAjaxRound('monitor-section','$page?monitor-section=yes');";
	$js_debugsection="LoadAjaxRound('debug-section','$page?debug-section=yes');";
	$js_updatesection="LoadAjaxRound('update-section','$page?update-section=yes');";
	if(!is_dir("/usr/share/artica-postfix/ressources/logs/web/cache")){@mkdir("/usr/share/artica-postfix/ressources/logs/web/cache",0755,true);}
	
	
	$infrasection_file="/usr/share/artica-postfix/ressources/logs/web/cache/".md5("INFRASECTION".$tpl->language.$_SESSION["uid"]);
	if(is_file($infrasection_file)){
		$infrasection_content=@file_get_contents($infrasection_file);
		if(trim($infrasection_content)<>null){
			$js_infrasection=null;
		}
	}
	
/*	$filter_file="/usr/share/artica-postfix/ressources/logs/web/cache/".md5("FILTERSECTION".$tpl->language.$_SESSION["uid"]);
	if(is_file($filter_file)){
		$filter_content=@file_get_contents($filter_file);
		if(trim($filter_content)<>null){
			$js_filtersection=null;
		}
	}	
*/
	
	$control_file="/usr/share/artica-postfix/ressources/logs/web/cache/".md5("CONTROLSECTION".$tpl->language.$_SESSION["uid"]);
	if(is_file($control_file)){
		$control_content=@file_get_contents($control_file);
		if(trim($control_content)<>null){
			$js_controlsection=null;
		}
	}	
	
	$monitor_file="/usr/share/artica-postfix/ressources/logs/web/cache/".md5("MONITORSECTION".$tpl->language.$_SESSION["uid"]);
	if(is_file($monitor_file)){
		$monitor_content=@file_get_contents($monitor_file);
		if(trim($monitor_content)<>null){
			$js_monitorsection=null;
		}
	}	
	
	$debug_file="/usr/share/artica-postfix/ressources/logs/web/cache/".md5("DEBUGSECTION".$tpl->language.$_SESSION["uid"]);
	if(is_file($debug_file)){
		$debug_content=@file_get_contents($debug_file);
		if(trim($debug_content)<>null){
			$js_debugsection=null;
		}
	}
	
	$update_file="/usr/share/artica-postfix/ressources/logs/web/cache/".md5("UPDATESECTION".$tpl->language.$_SESSION["uid"]);
	if(is_file($update_file)){
		$update_content=@file_get_contents($update_file);
		if(trim($update_content)<>null){
			$js_updatesection=null;
		}
	}	
	
	$ini=new Bs_IniHandler();
	$sock=new sockets();
	
	$ini->loadString($sock->GET_INFO("ArticaSquidParameters"));
	$visible_hostname=$ini->_params["NETWORK"]["visible_hostname"];
	if($visible_hostname==null){
		$visible_hostname=$sock->GET_INFO("myhostname");
		if($visible_hostname==null){$visible_hostname=$sock->getFrameWork("system.php?hostname-g=yes");}
	}
	
	
	$SquidVersion=@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidVersion");
	
	$html="
	<input type='hidden' id='thisIsTheSquidDashBoard' value='1'>
	<div style='margin-top:30px;margin-bottom:30px;font-size:45px;passing-left:30px;'>{your_proxy} &laquo;&nbsp;".texttooltip($visible_hostname,"{visible_hostname}", "Loadjs('squid.popups.php?script=visible_hostname')")."&nbsp;&raquo;
	&nbsp;".texttooltip("v$SquidVersion","{update_proxy_service}", "LoadProxyUpdate()")."
	
	
	
	</div>
	<div style='padding-left:30px;padding-right:30px'>			
	<table style='width:100%'>
	<tr>
		<td style='width:50%;vertical-align:top'>
		<table style='width:100%'>
			<tr>
			<td valign='top' style='width:96px'><img src='img/filter-96.png' style='width:96px'></td>
			<td valign='top' style='width:99%'>
				<div style='font-size:30px;margin-bottom:20px'>{filter2}</div>
				<div id='filter2-section' style='padding-left:15px'>$filter_content</div>
			</td>
			</tr>
			</table>
		<td style='width:50%;vertical-align:top;border-left:4px solid #CCCCCC;padding-left:15px'>
			<table style='width:100%'>
			<tr>
			<td valign='top' style='width:96px'><img src='img/infrastructure-96.png' style='width:96px'></td>
			<td valign='top' style='width:99%'>
				<div style='font-size:30px;margin-bottom:20px'>{cacheinfra}</div>
				<div id='infra-section' style='padding-left:15px'>$infrasection_content</div>
			</td>
			</tr>
			</table>
			
		</td>
	</tr>
	<tr style='height:30px'>
		<td style='width:50%;vertical-align:top'>&nbsp;</td>
		<td style='width:50%;vertical-align:top;border-left:4px solid #CCCCCC;padding-left:15px'>&nbsp;</td>
	</tr>
			

	<tr>
		<td style='width:50%;vertical-align:top'>
			<table style='width:100%'>
			<tr>
			<td valign='top' style='width:96px'><img src='img/users-96.png' style='width:96px'></td>
			<td valign='top' style='width:99%'>
				<div style='font-size:30px;margin-bottom:20px'>{control}</div>
				<div id='control-section' style='padding-left:15px'>$control_content</div>
			</td>
			
			</tr>
			</table>

		</td>
		<td style='width:50%;vertical-align:top;border-left:4px solid #CCCCCC;padding-left:15px'>
			<table style='width:100%'>
			<tr>
			<td valign='top' style='width:96px'><img src='img/graph-96.png' style='width:96px'></td>
			<td valign='top' style='width:99%'>
				<div style='font-size:30px;margin-bottom:20px'>{monitor} & {logs}</div>
				<div id='monitor-section' style='padding-left:15px'>$monitor_content</div>
			</td>
			</tr>
			</table>
			
		</td>
	</tr>	
		
	<tr style='height:30px'>
		<td style='width:50%;vertical-align:top'>&nbsp;</td>
		<td style='width:50%;vertical-align:top;border-left:4px solid #CCCCCC;padding-left:15px'>&nbsp;</td>
	</tr>		
	<tr>
		<td style='width:50%;vertical-align:top'>
			<table style='width:100%'>
			<tr>
			<td valign='top' style='width:96px'><img src='img/maintenance-96.png' style='width:96px'></td>
			<td valign='top' style='width:99%'>
				<div style='font-size:30px;margin-bottom:20px'>{update}</div>
				<div id='update-section' style='padding-left:15px'>$update_content</div>
			</td>
			
			</tr>
			</table>

		</td>
		<td style='width:50%;vertical-align:top;border-left:4px solid #CCCCCC;padding-left:15px'>
			<table style='width:100%'>
			<tr>
				<td valign='top' style='width:96px'><img src='img/technical-support-96.png' style='width:96px'></td>
				<td valign='top' style='width:99%'>
					<div style='font-size:30px;margin-bottom:20px'>{support_and_debug}</div>
					<div id='debug-section' style='padding-left:15px'>$debug_content</div>
				</td>
			</tr>
			</table>
		</td>
	</tr>		
	</table>
	</div>
	<script>
		$js_filtersection
		$js_infrasection
		$js_controlsection
		$js_monitorsection
		$js_debugsection
		$js_updatesection
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}


function monitor_section(){
	$users=new usersMenus();
	$sock=new sockets();
	$tpl=new templates();
	$OKStats=true;
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	$UfdbguardSMTPNotifs=unserialize(base64_decode($sock->GET_INFO("UfdbguardSMTPNotifs")));
	if(!isset($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"])){$UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]=0;}
	
	if($SquidPerformance>1){
		$OKStats=false;
	}
	$color="black";
	$icon="arrow-right-24.png";
	$icon_smtp="arrow-right-24.png";
	$color_smtp="black";
	$text_smtp=null;
	
	$icon_stats=$icon;
	$tr[]="<table style='width:100%'>";
	
	
	if(!$OKStats){
		$icon_stats="arrow-right-24-grey.png";
		$WebFiltering_text="&nbsp;({disabled})";
		$color="#898989";
		
	}
	
	if($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]==0){
		$icon_smtp="arrow-right-24-grey.png";
		$text_smtp="&nbsp;({disabled})";
		$color_smtp="#898989";
	}
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$icon_smtp'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:$color_smtp'>".texttooltip("{smtp_notifications}{$text_smtp}","position:top:{smtp_notifications}","GotoSquidSMTP()")."</td>
	</tr>";	
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$icon'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("SNMP","position:top:{squid_snmp_explain}","GotoSquidSNMP()")."</td>
	</tr>";	
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$icon'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>
		".texttooltip("{watchdog_parameters}",
		 "position:top:{watchdog_parameters_explain}","GotoWatchdogParameters()")."</td>
	</tr>";	
	
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$icon'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{performance_monitor}",null,"GotoSquidPerfMonitor()")."</td>
	</tr>";
	
	$tr[]="
	<tr style='height:20px'>
	<td colspan=2>&nbsp;</td>
	
	</tr>";
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$icon_stats'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:$color'>".texttooltip("{statistics_engine}$WebFiltering_text","position:top:{dashboard_statistics_options_explain}","LoadStatisticsOptions()")."</td>
	</tr>";

	
	
	
	
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$icon_stats'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:$color'>".texttooltip("{import_logs2}","position:top:{dashboard_statistics_import_logs_explain}","LoadStatisticsImport()")."</td>
	</tr>";
	
	$BackupMaxDays=intval($sock->GET_INFO("BackupMaxDays"));
	$SquidRotateClean=intval($sock->GET_INFO("SquidRotateClean"));
	if($BackupMaxDays==0){$BackupMaxDays=30;}
	$BackupMaxDays="{$BackupMaxDays} {days}";
	if($SquidRotateClean==0){$BackupMaxDays="{unlimited}";}
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$icon'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{legal_logs} <strong style='font-size:14px'>$BackupMaxDays</strong>","position:top:{squid_backuped_logs_explain}","LoadSquidRotate()")."</td>
	</tr>";	
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$icon'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("Syslog","position:top:{squid_syslog_text}","GotoSquidSyslog()")."</td>
	</tr>";
	

	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$icon'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{autoconfiguration}: {events}","position:top:{autoconfiguration}: {events}","ProxyPacEvents()")."</td>
	</tr>";	
	

	
	
	
	
	$tr[]="</table>";
	$html=$tpl->_ENGINE_parse_body(@implode("\n", $tr));	
	$monitor_file="/usr/share/artica-postfix/ressources/logs/web/cache/".md5("MONITORSECTION".$tpl->language.$_SESSION["uid"]);
	@file_put_contents($monitor_file, $html);
	echo $html;
	
				
	
}


function cache_section(){
	
	$ahref_caches="<a href=\"javascript:blur();\"
			OnClick=\"javascript:GoToCaches();\">";
	
}


function infra_section(){
	include_once(dirname(__FILE__)."/ressources/class.squid.inc");
	$users=new usersMenus();
	$sock=new sockets();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$icon="arrow-right-24.png";
	$SSLColor="#000000";
	$icon_ssl="arrow-right-24.png";
	$icon_ssl_enc="arrow-right-24.png";
	$ssl_enc_color="#000000";
	$GotoSSLEncrypt="GotoSSLEncrypt()";
	$squid=new squidbee();
	$EnableArticaHotSpot=intval($sock->GET_INFO("EnableArticaHotSpot"));
	$EnableIntelCeleron=intval($sock->GET_INFO("EnableIntelCeleron"));
	$COUNT_DE_CACHES=intval(@file_get_contents("{$GLOBALS["BASEDIR"]}/COUNT_DE_CACHES"));
	$COUNT_DE_MEMBERS=intval(@file_get_contents("{$GLOBALS["BASEDIR"]}/MEMBERS_COUNT"));
	$SquidBoosterEnable=intval($sock->GET_INFO("SquidBoosterEnable"));
	$SquidCacheLevel=$sock->GET_INFO("SquidCacheLevel");
	if(!is_numeric($SquidCacheLevel)){$SquidCacheLevel=4;}
	$HyperCacheStoreID=intval($sock->GET_INFO("HyperCacheStoreID"));
	$EnableSS5=intval($sock->GET_INFO("EnableSS5"));
	$EnableRDPProxy=intval($sock->GET_INFO("EnableRDPProxy"));
	$PrivoxyEnabled=intval($sock->GET_INFO("PrivoxyEnabled"));
	$EnableParentProxy=intval($sock->GET_INFO("EnableParentProxy"));

	if(!$q->FIELD_EXISTS("proxy_ports", "UseSSL")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `UseSSL` smallint(1) NOT NULL DEFAULT '0'");
		if(!$q->ok){echo $q->mysql_error_html();}
	}
	
	$sql="SELECT COUNT(*) as tcount FROM proxy_ports WHERE UseSSL=1 AND enabled=1";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo $q->mysql_error_html();}
	$CountOfSSL=intval($ligne["tcount"]);
	$ssl_rules_js="GotoSSLRules()";
	$WHY_SSL_RULES_DISABLED=null;
	if($CountOfSSL==0){
		$icon_ssl="arrow-right-24-grey.png";
		$icon_ssl_enc=$icon_ssl;
		
		$SSLColor="#898989";
		$ssl_enc_color=$SSLColor;
		$GotoSSLEncrypt="blur()";
		$ssl_rules_js="blur()";
		$WHY_SSL_RULES_DISABLED="<hr>{WHY_SSL_RULES_DISABLED}";
		
		$decrypted_ssl_websites="<tr>
		<td valign='middle' style='width:25px'>
		<img src='img/arrow-right-24-grey.png'>
		</td>
		<td valign='middle' style='font-size:18px;width:99%;color:$SSLColor'>{decrypted_ssl_websites}</td>
		</tr>";
		
		
		$ssl_rules="<tr>
		<td valign='middle' style='width:25px'>
		<img src='img/arrow-right-24-grey.png'>
		</td>
		<td valign='middle' style='font-size:18px;width:99%;color:$SSLColor'>{ssl_rules}</td>
		</tr>";
		
		
		
	}else{
		
		if($squid->SSL_BUMP_WHITE_LIST==1){
			$decrypted_ssl_websites="<tr>
			<td valign='middle' style='width:25px'>
				<img src='img/$icon_ssl'>
			</td>
			<td valign='middle' style='font-size:18px;width:99%;color:$SSLColor'>".texttooltip("{decrypted_ssl_websites}",null,"GotoSSLEncrypt()")."</td>
			</tr>";	
			
		}else{
			$decrypted_ssl_websites="<tr>
			<td valign='middle' style='width:25px'>
			<img src='img/arrow-right-24-grey.png'>
			</td>
			<td valign='middle' style='font-size:18px;width:99%;color:$SSLColor'>{decrypted_ssl_websites}</td>
			</tr>";
		}

		
	}
	
	$sslshitelist="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon_ssl'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$SSLColor'>".texttooltip("{ssl_whitelist}","position:left:{SSL_BUMP_WL}","GotoSquidSSLWL()")."</td>
	</tr>";	
	
	$ssl_rules="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon_ssl'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$SSLColor'>".texttooltip("{ssl_rules}","{ssl_rules}$WHY_SSL_RULES_DISABLED",$ssl_rules_js)."</td>
	</tr>";
	
	
	$tr[]="<table style='width:100%'>
	<tr>
	<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold'>{infrastructure}:</td>
	</tr>";
	
	
	
	
	$icon_ports="arrow-right-24.png";
	$explain_ports="{dashboard_listen_ports_explain}<br>{dashboard_listen_ports_explain2}";
	$js_ports="GotoSquidPorts()";
	$color_port="black";
	
	if($EnableArticaHotSpot==1){
		$icon_ports="arrow-right-24-grey.png";
		$explain_ports="{section_disabled_hotsport}";
		$js_ports="blur();";
		$color_port="#898989";
		
	}
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon_ports'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$color_port'>".texttooltip("{listen_ports}",
			"position:left:$explain_ports",$js_ports)."</td>
	</tr>";
	

	
	$ssl_options="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon_ssl'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$SSLColor'>".texttooltip("{ssl_options}",null,"Loadjs('squid.ssl.center.php?js=yes')")."</td>
	</tr>";	
	
	if(!$squid->IS_35){
		$tr[]=$decrypted_ssl_websites;
		$tr[]=$sslshitelist;
		$tr[]=$ssl_options;
	}else{
		$tr[]=$ssl_rules;
	}
	

	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/arrow-right-24.png'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{squid_templates_error}","{squid_templates_error_explain}","GotoSquidTemplatesErrors()")."</td>
	</tr>";	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>&nbsp;</td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>
			<table style='width:100%'>
			<tr>
			<td valign='middle' style='width:16px'>
			<img src='img/arrow-right-16.png'></td>
			<td valign='middle' style='font-size:16px;width:99%;color:black'>".texttooltip("{ftp_template}","{ftp_template_error_explain}","GotoSquidTemplateFTP()")."</td>
			</tr>
			</table>
			
			</td>
	</tr>";
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/arrow-right-24.png'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{timeouts}",null,"GotoSquidTimeOuts()")."</td>
	</tr>";
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/arrow-right-24.png'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{dns_settings}",null,"GotoSquidDNSsettings()")."</td>
	</tr>";	
	

	
	$tr[]="<tr><td colspan=2>&nbsp;</td></tr>";
	
	$squid_parent_proxy_color="black";
	$squid_parent_proxy_icon="arrow-right-24.png";
	$squid_parent_proxy_js="GotoSquidParentProxy()";
	
	if($EnableParentProxy==0){
		$squid_parent_proxy_icon="arrow-right-24-grey.png";
		$squid_parent_proxy_color="#898989";
		$squid_parent_proxy_text=" <span style='font-size:14px'>({disabled})</span>";
	}
	
	
	if($PrivoxyEnabled==1){
		$squid_parent_proxy_icon="arrow-right-24-grey.png";
		$squid_parent_proxy_color="#898989";
		$squid_parent_proxy_text=" <span style='font-size:14px'>({disabled} - {APP_PRIVOXY})</span>";		
		$squid_parent_proxy_js="blur()";
	}

	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$squid_parent_proxy_icon'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:$squid_parent_proxy_color'>".texttooltip("{squid_parent_proxy}$squid_parent_proxy_text","{squid_parent_proxy_explain}",$squid_parent_proxy_js)."</td>
	</tr>";	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/arrow-right-24.png'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("X-Forwarded-For","{follow_x_forwarded_for_explain}","GotoFollowXforwardedFor()")."</td>
	</tr>";
	
	
	$ecap_gzip_icon="arrow-right-24.png";
	$ecap_gzip_color="#000000";
	$ecap_gzip_explain=null;
	
	$EnableeCapGzip=intval($sock->GET_INFO("EnableeCapGzip"));
	if($EnableeCapGzip==0){
		$ecap_gzip_color="#898989";
		$ecap_gzip_icon="arrow-right-24-grey.png";
		$ecap_gzip_explain=" <span style='font-size:12px'>({disabled})</span>";
	}
	
	
	$tr[]="
	<tr>
		<td valign='middle' style='width:25px'>
			<img src='img/$ecap_gzip_icon'>
		</td>
		<td valign='middle' style='font-size:18px;width:99%;color:$ecap_gzip_color'>".texttooltip("{http_compression}$ecap_gzip_explain","{http_compression_explain}","GoToeCapGzip()")."</td>
	</tr>";	
	
	

	$SLAVE_IP=null;
	$icon_failover="arrow-right-24.png";
	$js_failover="GotoFailover()";
	$color_failover="black";
	$MAIN=unserialize(base64_decode($sock->GET_INFO("HASettings")));
	if(isset($MAIN["SLAVE"])){
		$SLAVE_IP=$MAIN["SLAVE"];
	}
	
	if($SLAVE_IP==null){
		$icon_failover="arrow-right-24-grey.png";
		$color_failover="#898989";
		$text_failover=" <span style='font-size:12px'>({disabled})</span>";
	}else{
		$text_failover=" <span style='font-size:12px'>({slave_ip} $SLAVE_IP)</span>";
	}
	
	if(!$users->CORP_LICENSE){
		$icon_failover="arrow-right-24-grey.png";
		$js_failover="blur()";
		$color_failover="#898989";
		$text_failover=" <span style='font-size:12px'>({no_license})</span>";
	}
	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon_failover'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:$color_failover'>".texttooltip("{failover}$text_failover",
			"{failover_explain}","$js_failover")."</td>
	</tr>";


	

	
	
	$img_hostpot="arrow-right-24.png";
	$explain_hostpot="{dashboard_hotspot}";
	$js_hostpot="GotoHostpotv3()";
	$color_hotspot="black";
	$TCP_LIST_NICS=TCP_LIST_NICS();
	
	
	if($TCP_LIST_NICS<2){
		$img_hostpot="arrow-right-24-grey.png";
		$explain_hostpot="<div style='background-color:white;color:#d32d2d;margin:10px;padding:10px'>{dashboard_hotspot_nonic}</div>";
		$js_hostpot="blur();";
		$color_hotspot="#898989";
	}
	
	if($EnableArticaHotSpot==0){
		$img_hostpot="arrow-right-24-grey.png";
		$color_hotspot="#898989";
	}
	
	
	
	$tr[]="
<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$img_hostpot'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:$color_hotspot'>".
		texttooltip("HotSpot",$explain_hostpot,$js_hostpot)."</td>
</tr>";

	
	
	
	
	$tr[]="</table>";	
	
// ***************************************************************************************************	
	
	$tr2[]="<table style='width:100%'>";
	$tr2[]="
	<table style='width:100%'>
	<tr>
	<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold'>{system_pcaches}:</td>
	</tr>";
	
	
	
	$Color="black";
	$icon="Database24.png";
	
	
	$hypercache_mirror_Color="black";
	$icon_center_cache_explain=null;
	$hypercache_mirror_icon="arrow-right-24.png";
	$js_cache_status="GoToCaches()";
	$icon_cache_status="Database24.png";
	$icon_cache_explain=null;
	$color_cache_status="black";
	

	
	$js="GoToCachesCenter()";
	if(!$users->CORP_LICENSE){
		$icon_center_cache_explain="<span style='font-size:12px'> ({no_license})</span>";
		$icon="Database24-grey.png";
		$Color="#898989";
		$js="blur();";
		}
		
		
	
	if($COUNT_DE_CACHES>0){if($COUNT_DE_MEMBERS>15){if($COUNT_DE_CACHES<20000){$icon="alert-24.png";} }}
	
	if($SquidCacheLevel==0){
		$icon="Database24-grey.png";
		$icon_cache_status=$icon;
		$hypercache_mirror_icon="arrow-right-24-grey.png";
		$icon_cache_explain="<span style='font-size:12px'> ({disabled})</span>";
		$icon_center_cache_explain="<span style='font-size:12px'> ({disabled})</span>";
		$js_cache_status="blur();";
		$Color="#898989";
		$color_cache_status=$Color;
		$hypercache_mirror_Color=$Color;
		$js="blur();";
		$SquidBoosterEnable=0;
		$EnableRockCache=0;
		$HyperCacheStoreID=0;
	}
	
	
	$tr2[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon_cache_status'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$color_cache_status'>".
	texttooltip("{caches_status}$icon_cache_explain","{your_proxy_caches_explain}",$js_cache_status)."</td>
	</tr>";
	
	
	$tr2[]="<tr>
		<td valign='middle' style='width:25px'>
		<img src='img/$icon'>
		</td>
		<td valign='middle' style='font-size:18px;width:99%;color:$Color'>".
			texttooltip("{caches_center}$icon_center_cache_explain","{caches_center_explain}","$js")."</td>
	</tr>";	
	
	
	
	$icon_booster_explain=null;
	$EnableRockCache=intval($sock->GET_INFO("EnableRockCache"));
	$icon_booster="Database24.png";
	$icon_rock="Database24.png";
	$js_booster="GotoProxyBooster()";
	$Color_booster="black";
	$Color_rock="black";
	$js_rock="GoToRock()";
	
	
	if($SquidBoosterEnable==0){
		$icon_booster="Database24-grey.png";
		$icon_booster_explain="<span style='font-size:12px'> ({disabled})</span>";
	}
	
	if(!$users->CORP_LICENSE){
		$icon_booster="Database24-grey.png";
		$icon_rock="Database24-grey.png";
		$js_booster="blur();";
		$Color_booster="#898989";
		$Color_rock="#898989";
		$EnableRockCache=0;
		$js_rock="blur()";
		$icon_booster_explain="<span style='font-size:12px'> ({no_license})</span>";
		$icon_rock_explain="<span style='font-size:12px'> ({no_license})</span>";
		$icon_caches_rules_explain="<span style='font-size:12px'> ({no_license})</span>";
	}
	
	
	
	$tr2[]="
	<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon_booster'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$Color_booster'>".
	texttooltip("{squid_booster}$icon_booster_explain",null,"GotoProxyBooster()")."</td>
	</tr>";
	
	
	
	
	if($users->CORP_LICENSE){
		if( ($EnableRockCache==0) OR ($SquidCacheLevel==0)) {
			$icon_rock="Database24-grey.png";
			$Color_rock="#898989";
			$icon_rock_explain="<span style='font-size:12px'> ({disabled})</span>";
			
			
		}
	}
	
	if($SquidCacheLevel==0){
		$icon_rock="Database24-grey.png";
		$Color_rock="#898989";
		$icon_rock_explain="<span style='font-size:12px'> ({disabled})</span>";
		$icon_caches_rules_explain="<span style='font-size:12px'> ({disabled})</span>";
		$icon_caches_rules="arrow-right-24-grey.png";
		$Color_caches_rules="#898989";
	}
	
	
	
	$tr2[]="
	<tr>
	<td valign='middle' style='width:25px'>
		<img src='img/$icon_rock'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$Color_rock'>".
		texttooltip("{rock_store}$icon_rock_explain",null,$js_rock)."</td>
	</tr>";	
	
	$tr2[]="<tR><td colspan=2>&nbsp;</td></tr>";
	
	$icon="arrow-right-24.png";
	$Color="black";
	$icon="arrow-right-24.png";
	

	$SquidVersion=@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidVersion");
	
	
	if(preg_match("#^3\.5#", $SquidVersion)){$HyperCacheCompatible=true;}
	if(preg_match("#^4\.#", $SquidVersion)){$HyperCacheCompatible=true;}
	

	if($HyperCacheStoreID==0){$icon="arrow-right-24-grey.png";$Color="#898989";}
	$explain="{HyperCache_explain}";
	$js_hypercache="GoToHyperCache()";
	
	
	if(!$HyperCacheCompatible){
		$icon="arrow-right-24-grey.png";
		$js_hypercache="blur()";
		$Color="#898989";
		$hypercache_explain="{ERROR_SQUID_MUST_35}";
	}
	
	if($EnableIntelCeleron==1){
		$icon="arrow-right-24-grey.png";
		$js_hypercache="blur()";
		$Color="#898989";
		$hypercache_explain="{ERROR_FEATURE_CELERON}";
	}
	
	
	$tr2[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/arrow-right-24.png'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{cache_level}",null,"GoToCachesLevel()")."</td>
	</tr>";
	
	$js_caches_rules="GotoSquidCachesRules()";
	$Color_caches_rules="black";
	$icon_caches_rules="arrow-right-24.png";
	
	
	if(!$users->CORP_LICENSE){
		$icon_caches_rules="arrow-right-24-grey.png";
		$Color_caches_rules="#898989";
		$js_caches_rules=null;
	}
	
	
	$tr2[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon_caches_rules'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$Color_caches_rules'>".
	texttooltip("{caches_rules}$icon_caches_rules_explain","{refresh_pattern_intro}","$js_caches_rules")."</td>
	</tr>";	
	
	$WindowsUpdateCacher_icon="arrow-right-24.png";
	$WindowsUpdateCacher_explain=null;
	$WindowsUpdateCacher_color="black";
	$EnableUfdbGuard=intval($sock->GET_INFO("EnableUfdbGuard"));
	$WindowsUpdateCaching=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateCaching"));
	if($EnableUfdbGuard==0){$WindowsUpdateCaching=0;}
	
	if($WindowsUpdateCaching==0){
		$WindowsUpdateCacher_icon="arrow-right-24-grey.png";
		$WindowsUpdateCacher_color="#898989";
		$WindowsUpdateCacher_explain="&nbsp;<span style='font-size:12px'> ({disabled})</span>";
	}
	
	$webcopy_color="black";
	$webcopy_icon="arrow-right-24.png";
	$webcopy_explain=null;
	$webcopy_js="GotoWebCopy()";
	
	$HTTrackInSquid=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/HTTrackInSquid"));
	
	
	if($HTTrackInSquid==0){
		$webcopy_icon="arrow-right-24-grey.png";
		$webcopy_color="#898989";
		$webcopy_explain="&nbsp;<span style='font-size:12px'> ({disabled})</span>";
				
		
	}
	
	if(!is_file("/usr/bin/httrack")){
		$webcopy_icon="arrow-right-24-grey.png";
		$webcopy_color="#898989";
		$webcopy_explain="&nbsp;<span style='font-size:12px'> ({not_installed})</span>";
		
	}
	
	$tr2[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$webcopy_icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$webcopy_color'>".
	texttooltip("WebCopy$webcopy_explain","{WebCopy_task_explain}",$webcopy_js)."</td>
	</tr>";	
	
	
	
	$tr2[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$WindowsUpdateCacher_icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$WindowsUpdateCacher_color'>".
	texttooltip("Windows Updates$WindowsUpdateCacher_explain","{enable_windows_updates_cache_enforcement_explain}","GotoWindowsUpdate()")."</td>
	</tr>";	
	
	
	
	$tr2[]="
	<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$Color'>".texttooltip("HyperCache$icon_cache_explain",
			"$hypercache_explain",$js_hypercache)."</td>
	</tr>";	
	
	

	$tr2[]="
	<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$hypercache_mirror_icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$hypercache_mirror_Color'>".texttooltip("HyperCache mirror$icon_cache_explain","{HyperCache_mirror_explain}","GoToHyperCacheMirror()")."</td>
	</tr>";	
	
	
	

	$tr2[]="<tR><td colspan=2>&nbsp;</td></tr>
	<tr>
	<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold'>{gateway_services}:</td>
	</tr>";
	
	

	$img_ss5="arrow-right-24.png";
	$explain_ss5="{APP_SS5_ABOUT}";
	$js_ss5="GoToSS5()";
	$color_ss5="black";
	
	$img_rdp="arrow-right-24.png";
	$explain_rdp="{APP_RDPPROXY}";
	$js_rdp="GotToRDPPROX()";
	$color_rdp="black";
	
	if($EnableSS5==0){
		$img_ss5="arrow-right-24-grey.png";
		$explain_ss5="{APP_SS5_ABOUT}";
		$error_ss5="&nbsp;<span style='font-size:12px'>{disabled}</span>";
		$js_ss5="GoToSS5()";
		$color_ss5="#898989";
	
	}
	
	if(!is_file("/usr/sbin/ss5")){
		$img_ss5="arrow-right-24-grey.png";
		$explain_ss5="{APP_SS5_ABOUT}";
		$error_ss5="&nbsp;<span style='font-size:12px'>{not_installed}</span>";
		$js_ss5=null;
		$color_ss5="#898989";
	}
	
	if(!is_file("/var/lib/ss5/ss5/mod_socks4.so")){
		$img_ss5="arrow-right-24-grey.png";
		$explain_ss5="{APP_SS5_ABOUT}";
		$error_ss5="&nbsp;<span style='font-size:12px'>{not_installed}</span>";
		$js_ss5=null;
		$color_ss5="#898989";
	}
	
	
	if($EnableRDPProxy==0){
		$img_rdp="arrow-right-24-grey.png";
		$explain_rdp="{APP_SS5_ABOUT}";
		$error_rdp="&nbsp;<span style='font-size:12px'>{disabled}</span>";
		$js_rdp="GotToRDPPROX()";
		$color_rdp="#898989";
	
	}
	
	if(!$users->RDPPROXY_INSTALLED){
		$img_rdp="arrow-right-24-grey.png";
		$explain_rdp="{APP_SS5_ABOUT}";
		$error_rdp="&nbsp;<span style='font-size:12px'>{not_installed}</span>";
		$js_rdp=null;
		$color_rdp="#898989";
	
	}
	
	$tr2[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$img_ss5'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:$color_ss5'>".
	texttooltip("{APP_SS5}$error_ss5",$explain_ss5,$js_ss5)."</td>
	</tr>";
	
	
	$tr2[]="<tr>
	<td valign='middle' style='width:25px'><img src='img/$img_rdp'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:$color_rdp'>".
	texttooltip("{APP_RDPPROXY}$error_rdp",
			"position:right:{APP_RDPPROXY}",$js_rdp)."</td>
	</tr>";
	
	
	
	
	$EnableSecureGateway=intval($sock->GET_INFO("EnableSecureGateway"));
	$icon_secure_gateway="arrow-right-24.png";
	$color_secure_gateway="black";
	$text_secure_gateway=null;
	
	if($EnableSecureGateway==0){
		$icon_secure_gateway="arrow-right-24-grey.png";
		$color_secure_gateway="#898989";
		$text_secure_gateway=" <span style='font-size:12px'>({disabled})</span>";
	}
	
	
	"arrow-right-24-grey.png";
	
	$tr2[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon_secure_gateway'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:$color_secure_gateway'>".texttooltip("{secure_gateway}$text_secure_gateway","{secure_gateway_explain}","GotoGatewaySecure()")."</td>
	</tr>";
	
	
	
	
	
	
	$Transmission_icon="arrow-right-24.png";
	$Transmission_Color="black";
	$Transmission_js="GoToTransmissionDaemon()";
	$EnableTransMissionDaemon=intval($sock->GET_INFO("EnableTransMissionDaemon"));
	$UpdateUtilityWizard=intval($sock->GET_INFO("UpdateUtilityWizard"));
	
	if(!is_file("/usr/bin/transmission-daemon")){
		$Transmission_icon="arrow-right-24-grey.png";
		$Transmission_Color="#898989";
		$Transmission_js="blur();";
	}else{
		if($EnableTransMissionDaemon==0){
			$Transmission_icon="arrow-right-24-grey.png";
			$Transmission_Color="#898989";
		}
		
	}
	
	$UpdateUtilityIcon="arrow-right-24.png";
	$UpdateUtilityColor="black";
	
	if($UpdateUtilityWizard==0){
		$UpdateUtilityIcon="arrow-right-24-grey.png";
		$UpdateUtilityColor="#898989";		
	}
	
	if(!is_file("/etc/UpdateUtility/UpdateUtility-Console")){
		$UpdateUtilityIcon="arrow-right-24-grey.png";
		$UpdateUtilityColor="#898989";
	}
	
	
	$tr2[]="
	<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$Transmission_icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$Transmission_Color'>".texttooltip("{bittorrent_service}","{bittorrent_service_explain}","GoToTransmissionDaemon()")."</td>
	</tr>";
	
	
	$tr2[]="
	<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$UpdateUtilityIcon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$UpdateUtilityColor'>".texttooltip("{APP_KASPERSKY_UPDATE_UTILITY}","{APP_KASPERSKY_UPDATE_UTILITY}","GoToUpdateUtility()")."</td>
	</tr>";
	
	$tr2[]="</table>";
	
	
	$final="
	<table style='width:100%'>
	<tr>
		<td style='width:50%' valign='top'>".$tpl->_ENGINE_parse_body(@implode("\n", $tr))."</td>
		<td style='width:50%' valign='top'>".$tpl->_ENGINE_parse_body(@implode("\n", $tr2))."</td>
	</tr>
	</table>
	";
	
	$filename="/usr/share/artica-postfix/ressources/logs/web/cache/".md5("INFRASECTION".$tpl->language.$_SESSION["uid"]);
	@file_put_contents($filename, $final);
	echo $final;
}

function control_section(){
	include_once(dirname(__FILE__)."/ressources/class.squid.inc");
	$sock=new sockets();
	$tpl=new templates();
	$EnableUfdbGuard=intval($sock->GET_INFO("EnableUfdbGuard"));
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	$EnableKerbAuth=intval($sock->GET_INFO("EnableKerbAuth"));
	$SquidAllow80Port=intval($sock->GET_INFO("SquidAllow80Port"));
	$EnableArticaHotSpot=intval($sock->GET_INFO("EnableArticaHotSpot"));
	$EnableSquidQuotasBandwidth=intval($sock->GET_INFO("EnableSquidQuotasBandwidth"));
	$InfluxUseRemote=intval($sock->GET_INFO("InfluxUseRemote"));
	$EnableYoutubeLocker=intval($sock->GET_INFO("EnableYoutubeLocker"));
	$InfluxSyslogRemote=0;
	if($InfluxUseRemote==1){$InfluxSyslogRemote=intval($sock->GET_INFO("InfluxSyslogRemote"));}
	
	$SquidDisableAllFilters=intval($sock->GET_INFO("SquidDisableAllFilters"));
	$SquidUrgency=intval($sock->GET_INFO("SquidUrgency"));
	$EnableeCapClamav=intval($sock->GET_INFO("EnableeCapClamav"));
	$EnableeCapGzip=intval($sock->GET_INFO("EnableeCapGzip"));
	$eCAPClamavMaxSize=intval($sock->GET_INFO("eCAPClamavMaxSize"));
	$eCAPClamavEmergency=intval($sock->GET_INFO("eCAPClamavEmergency"));
	
	
	
	$EnableIntelCeleron=$sock->EnableIntelCeleron;
	$squid=new squidbee();
	$users=new usersMenus();
	
	
	$OKQuota=true;
	
	
	$color_quota="black";
	$tr[]="<table style='width:100%'>";
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	if($EnableUfdbGuard==0){$OKQuota=false;}
	if($SquidPerformance>1){$OKQuota=false;}
	
	
	$icon="arrow-right-24.png";
	$icon_quota=$icon;
	$js_quota="GotoSquidWebfilterQuotas()";
	if(!$OKQuota){
		$js_quota="blur()";
		$icon_quota="arrow-right-24-grey.png";
		$color_quota="#898989";
	}
	
	$tr[]="<table style='width:100%'>";
	
	
	$ad_text=null;
	$ad_icon="arrow-right-24.png";
	$ad_analyze_icon="arrow-right-16.png";
	$ad_script="GoToActiveDirectory()";
	$ad_color="black";
	$ad_explain="{dashboard_activedirectory_explain}";
	
	$phpldapadm_icon="arrow-right-24.png";
	$phpldapadm_color="#898989";
	$phpldapadm_title="{APP_PHPLDAPADMIN}";
	$phpldapadm_explain="{APP_PHPLDAPADMIN_TEXT}";
	
	
	$remote_ldap_color="#898989";
	$remote_ldap_icon="arrow-right-24-grey.png";
	
	
	if($squid->LDAP_EXTERNAL_AUTH==1){
		$EnableKerbAuth=0;
		$ad_icon="arrow-right-24-grey.png";
		$remote_ldap_icon="arrow-right-24.png";
		$ad_script="blur()";
		$ad_color="#898989";
		$remote_ldap_color="black";
		$ad_explain="{dashboard_activedirectory_disabled_explain}";
	}else{
		if($EnableKerbAuth==0){
			$ad_icon="arrow-right-24-grey.png";
			$ad_color="#898989";
		}
	}
	
	$phpldapadm_explain="{APP_PHPLDAPADMIN_TEXT}";
	
	if($squid->LDAP_EXTERNAL_AUTH==1){
		
		if($users->phpldapadmin_installed){
			$phpldapadm_icon="arrow-right-24.png";
			$phpldapadm_color="#000000";
			$phpldapadm_title="{APP_PHPLDAPADMIN}";
			$phpldapadm_js="s_PopUpFull('/ldap',1024,768,'PHPLDAPADMIN')";
			
		}else{
			$phpldapadm_icon="info-24.png";
			$phpldapadm_title="{INSTALL_PHPLDAPADMIN}";
			$phpldapadm_js="Loadjs('phpldapadmin.progress.php')";
		}
		
	}else{
		$phpldapadm_icon="arrow-right-24-grey.png";
		$phpldapadm_title="{APP_PHPLDAPADMIN}";
		$phpldapadm_js="blur()";
	}
	
	if($squid->LDAP_AUTH==1){
		$remote_ldap_color="black";
		$ad_icon="arrow-right-24-grey.png";
		$remote_ldap_icon="arrow-right-24.png";
	}
	
	
	$youtubelocker_icon="arrow-right-24.png";
	$youtubelocker_color="black";
	$youtubelocker_text=null;	
	
	
	$QuotasBand_icon="arrow-right-24.png";
	$QuotasBand_color="black";
	$QuotasBand_text=null;
	
	if($EnableSquidQuotasBandwidth==0){
		$QuotasBand_icon="arrow-right-24-grey.png";
		$QuotasBand_color="#898989";
		$QuotasBand_text=" <span style='font-size:12px'>{disabled}</span>";
		
	}
	
	if($InfluxSyslogRemote==1){
		$QuotasBand_icon="arrow-right-24-grey.png";
		$QuotasBand_color="#898989";
		$QuotasBand_text=" <span style='font-size:12px'>{not_available}</span>";
	}
	
	if($EnableYoutubeLocker==0){
		$youtubelocker_icon="arrow-right-24-grey.png";
		$youtubelocker_color="#898989";
		$youtubelocker_text=" <span style='font-size:12px'>{disabled}</span>";
	}
	
	if($SquidDisableAllFilters==1){
		$youtubelocker_icon="arrow-right-24-grey.png";
		$youtubelocker_color="#898989";
		$youtubelocker_text=" <span style='font-size:12px'>{all_filters_are_disabled}</span>";
		
		$QuotasBand_icon="arrow-right-24-grey.png";
		$QuotasBand_color="#898989";
		$QuotasBand_text=" <span style='font-size:12px'>{all_filters_are_disabled}</span>";
		
	}
	
	if($EnableKerbAuth==0){
		$ad_text=" <span style='font-size:12px'>{disabled}</span>";
	}
	
	if($SquidUrgency==1){
		$youtubelocker_icon="alert-24.png";
		$youtubelocker_color="#898989";
		$youtubelocker_text=" <span style='font-size:12px'>{emergency_mode}</span>";
		
		$QuotasBand_icon="alert-24.png";
		$QuotasBand_color="#898989";
		$QuotasBand_text=" <span style='font-size:12px'>{emergency_mode}</span>";
		
		if($EnableKerbAuth==1){
			$ad_icon="alert-24.png";
			$ad_analyze_icon="error-16.png";
			$ad_text=" <span style='font-size:12px'>{emergency_mode}</span>";
		}
		
	}
	
	//<APP_PHPLDAPADMIN>phpLDAPadmin</APP_PHPLDAPADMIN>
	//<APP_PHPLDAPADMIN_TEXT>Browse the LDAP directory using phpLDAPAdmin front-end</APP_PHPLDAPADMIN_TEXT>
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$youtubelocker_icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$youtubelocker_color'>".texttooltip("{youtube_locker}$youtubelocker_text",
			"position:right:{youtube_locker_explain}","GotoYoutubeLocker()")."</td>
	</tr>";	
	
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{limit_rate}",
			"position:right:{limit_rate}","GotoSquidBandwG()")."</td>
	</tr>";
	
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$QuotasBand_icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:{$QuotasBand_color}'>".texttooltip("{quotas_bandwidth}$QuotasBand_text",
			"position:right:{quotas_bandwidth_explain}","GotoQuotasBand()")."</td>
	</tr>";	
	

	
	
if($EnableArticaHotSpot==0){
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$ad_icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$ad_color'>".
	texttooltip("Active Directory$ad_text","position:right:$ad_explain",$ad_script)."</td>
	</tr>";
	if($EnableKerbAuth==1){
		
		$tr[]="<tr>
		<td valign='middle' style='width:25px'>&nbsp;</td>
		<td><table style='width:100%'>
		<tr>
			<td valign='middle' style='width:25px'>&nbsp,</td>
			<td style='width:25px'><img src='img/$ad_analyze_icon'></td>
			<td style='font-size:16px;width:100%'> ".texttooltip("{analyze}","position:right:Active Directory {analyze}","Loadjs('squid.adker.php?test-js=yes')")."</td>
		</tr>
		</table>
		</td>
	</tr>";
		
	}
	
}

if($EnableArticaHotSpot==0){	
	if($EnableKerbAuth==0){	
		$tr[]="
		<tr>
		<td valign='middle' style='width:25px'>
		<img src='img/$remote_ldap_icon'>
		</td>
		<td valign='middle' style='font-size:18px;width:99%;color:$remote_ldap_color'>".texttooltip("{APP_LDAP_DB}",
				"position:right:{authenticate_users_ldap_text}","GotoOpenldap()")."</td>
		</tr>";	
	}
}	

	
	
	
	
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$phpldapadm_icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$phpldapadm_color'>".texttooltip($phpldapadm_title,
			"position:right:$phpldapadm_explain",$phpldapadm_js)."</td>
	</tr>";	
	
	
	$unlock_icon="arrow-right-24.png";
	$unlock_script="GotoUfdbUnlockPages()";
	$unlock_color="black";
	
	$itchart_icon="arrow-right-24.png";
	$itchart_script="GotoItChart()";
	$itchart_color="black";
	
	
	
	if($EnableUfdbGuard==0){
		$unlock_icon="arrow-right-24-grey.png";
		$unlock_script="blur()";
		$unlock_color="#898989";
		
		$itchart_icon="arrow-right-24-grey.png";
		$itchart_script="blur()";
		$itchart_color="#898989";		
		
	}
	
if($EnableArticaHotSpot==0){	
	$tr[]="
	<tr>
		<td valign='middle' style='width:25px'>
			<img src='img/$itchart_icon'>
		</td>
		<td valign='middle' style='font-size:18px;width:99%;color:$itchart_color'>".texttooltip("{it_charters}",
			"position:right:{IT_charter_explain}",$itchart_script)."</td>
	</tr>";	
	}


	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$unlock_icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$unlock_color'>".texttooltip("{unlock_rules}",
			"position:right:{unlock_rules_explain_text}",$unlock_script)."</td>
	</tr>";	
	

	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{my_proxy_aliases}",
			"position:right:{my_proxy_aliases_text}","GoToProxyAliases()")."</td>
	</tr>";	
	
	
	
	
	$icon_autoconfiguration=$icon;
	$text_autoconfiguration="{autoconfiguration_explain}";
	$color_autoconfiguration="#000000";
	$js_autoconfiguration="GoToProxyPac()";
	$mtext_autoconfiguration=null;
	
	if($SquidPerformance>2){
		$icon_autoconfiguration="arrow-right-24-grey.png";
		$text_autoconfiguration="<strong style='color:white'>{ERROR_FEATURE_MINIMAL_PERFORMANCES}</strong><br>{autoconfiguration_explain}";
		$color_autoconfiguration="#898989";
		$js_autoconfiguration="blur()";		
		$mtext_autoconfiguration=" <span style='font-size:14px'>{ERROR_FEATURE_MINIMAL_PERFORMANCES}</span>";
	
	}
	if($EnableIntelCeleron==1){
		$icon_autoconfiguration="arrow-right-24-grey.png";
		$text_autoconfiguration="<strong style='color:white'>{ERROR_FEATURE_CELERON}</strong><br>{autoconfiguration_explain}";
		$color_autoconfiguration="#898989";
		$js_autoconfiguration="blur()";
		$mtext_autoconfiguration=" <span style='font-size:14px'>{ERROR_FEATURE_CELERON}</span>";
	}
	
	if($SquidAllow80Port==1){
		$icon_autoconfiguration="arrow-right-24-grey.png";
		$color_autoconfiguration="#898989";
		$js_autoconfiguration="blur()";
		$mtext_autoconfiguration=" <span style='font-size:14px'>{allow_80443_port}</span>";
	}
	
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon_autoconfiguration'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$color_autoconfiguration'>".
			texttooltip("{autoconfiguration}$mtext_autoconfiguration",
			"position:right:$text_autoconfiguration",$js_autoconfiguration)."</td>
	</tr>";	
	
	
	
	$tr[]="<tr>
		<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold'>&nbsp;</td>
	</tr>";
	$tr[]="<tr>
		<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold'>{browsers_users}:</td>
	</tr>";
	
	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{restricted_members}","position:right:{restricted_members}","GoToSquidRestrictedMembers()")."</td>
	</tr>";	
	
/*	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon_quota'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$color_quota'>".texttooltip("{squid_quota_member}","position:right:{squid_quota_member_explain}","$js_quota")."</td>
	</tr>";	
*/	
	
	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{blocked_members}","position:right:{blocked_members}","GoToSquidBlockedMembers()")."</td>
	</tr>";	
	
	
	
	$tr[]="</table>";
	$html= $tpl->_ENGINE_parse_body(@implode("\n", $tr));	
	$control_file="/usr/share/artica-postfix/ressources/logs/web/cache/".md5("CONTROLSECTION".$tpl->language.$_SESSION["uid"]);
	@file_put_contents($control_file, $html);
	echo $html;
}

function filter2_section(){
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	$EnableUfdbGuard=intval($sock->GET_INFO("EnableUfdbGuard"));
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	$CicapEnabled=intval($sock->GET_INFO("CicapEnabled"));
	$EnableIntelCeleron=intval($sock->GET_INFO("EnableIntelCeleron"));
	$EnableeCapClamav=intval($sock->GET_INFO("EnableeCapClamav"));
	$SquidDisableAllFilters=intval($sock->GET_INFO("SquidDisableAllFilters"));
	$SquidUrgency=intval($sock->GET_INFO("SquidUrgency"));
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	$SquidUFDBUrgency=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidUFDBUrgency"));
	
	$OKStats=true;
	$icon="arrow-right-24.png";
	$icon_category=$icon;
	$color_category="black";
	$tr[]="<table style='width:100%'>";
	
	$explain_category="{your_categories_explain}";
	$js_categories="GotoYourcategories();";
	
	
	if($SquidPerformance>1){
		$OKStats=false;
	}
	
	
	if(!$OKStats){
		$icon_stats="arrow-right-24-grey.png";
		$stats_text=" ({disabled})";
		$color_stats="#898989";
		$icon_stats="arrow-right-24-grey.png";
		$stats_text=" ({disabled})";
		$color_stats="#898989";
	
	}else{
		$color_stats="#000000";
		$icon_stats="arrow-right-24.png";
		$stats_text="";
		
	}
	
	$ecap_clamav_icon="arrow-right-24.png";
	$ecap_clamav_color="#000000";
	$ecap_clamav_text=null;
	
	if($EnableeCapClamav==0){
		$ecap_clamav_icon="arrow-right-24-grey.png";
		$ecap_clamav_color="#898989";
		$ecap_clamav_text="({disabled})";
		
	}
	
	if($SquidDisableAllFilters==1){
		$ecap_clamav_icon="arrow-right-24-grey.png";
		$ecap_clamav_color="#898989";
		$ecap_clamav_text="({filters_are_disabled})";
		
	}
	

	
	
	
	
	if($EnableUfdbGuard==1){
		$color="#000000";
		$icon_ufdb="arrow-right-24.png";
		$WebFiltering_text="{enabled}";
		
		if($SquidUFDBUrgency==1){
			$icon_ufdb="alert-24.png";
		}
		
	}else{
		//$icon_category="arrow-right-24-grey.png";;
		$icon_ufdb="arrow-right-24-grey.png";
		$WebFiltering_text="{disabled}";
		$color="#898989";
		//$color_category="#898989";
	}
	
	$explain_av="{web_antivirus_explain}";
	$js_cicap="GoToCICAP()";
	
	
	if($CicapEnabled==1){
		$color_av="#000000";
		$icon_av="arrow-right-24.png";
		$av_textexplain=null;
	
	}else{
		$icon_av="arrow-right-24-grey.png";
		$av_textexplain=" <span style='font-size:12px'>({disabled})</span>";
		$color_av="#898989";
	}	
	
	if($EnableIntelCeleron==1){
		$icon_av="arrow-right-24-grey.png";
		$av_textexplain="<br><span style='font-size:12px'>(Celeron - {disabled})</span>";
		$color_av="#898989";
		$explain_av="{ERROR_FEATURE_CELERON}";
	}
	
	if($SquidPerformance>2){
		$icon_av="arrow-right-24-grey.png";
		$av_textexplain="<br><span style='font-size:12px'>({performance} - {disabled})</span>";
		$color_av="#898989";
		$explain_av="{proxy_performance_is_set_to_lowlevel}";
		
	}
	
	if(!$users->AsDansGuardianAdministrator){
		$icon_av="arrow-right-24-grey.png";
		$av_textexplain="<br><span style='font-size:12px'>({NO_PRIVS} {disabled})</span>";
		$color_av="#898989";
		$js_cicap="blur()";
		$explain_av="{NO_PRIVS}";
		
	}
	
	if(!$users->C_ICAP_INSTALLED){
		$color_av_title="#898989";
		$js_cicap="blur();";
		$icon_av="arrow-right-24-grey.png";
		$explain_av="{product_not_installed_explain}";
		$av_textexplain="<br><span style='font-size:12px'>({not_installed})</span>";
	}

	
	
	
	
	if(!$users->CORP_LICENSE){
		$icon_category="arrow-right-24-grey.png";
		$color_category="#898989";
		$explain_category="{this_feature_is_disabled_corp_license}";
		$js_categories="blur()";
	}
	
	if(!$users->AsDansGuardianAdministrator){
		$icon_category="arrow-right-24-grey.png";
		$color_category="#898989";
		$explain_category="{ERROR_NO_PRIVS}";
		$js_categories="blur()";
	}
	
	
	if($SquidUrgency==1){
		$ecap_clamav_icon="arrow-right-24-grey.png";
		$ecap_clamav_color="#898989";
		$ecap_clamav_text="({proxy_in_emergency_mode})";
	
		$icon_av="arrow-right-24-grey.png";
		$explain_av="{proxy_in_emergency_mode}";
		$av_textexplain="<br><span style='font-size:12px'>({proxy_in_emergency_mode})</span>";	

		$color="#898989";
		$icon_ufdb="arrow-right-24-grey.png";
		$WebFiltering_text="{proxy_in_emergency_mode}";
		
	}
	
	$EnableGoogleSafeSearch=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableGoogleSafeSearch"));
	$EnableGoogleSafeBrowsing=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableGoogleSafeBrowsing"));
	$EnableSquidPhishTank=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableSquidPhishTank"));
	$EnableHTTPSURBL=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableHTTPSURBL"));
	$WebFilteringRansomware=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WebFilteringRansomware"));
	
	$EnableGoogleSafeSearch_icon="arrow-right-16.png";
	$EnableGoogleSafeSearch_color="#000000";
	$EnableGoogleSafeBrowsing=null;	
	
	$EnableGoogleSafeBrowsing_icon="arrow-right-16.png";
	$EnableGoogleSafeBrowsing_color="#000000";
	$EnableGoogleSafeBrowsing_text=null;
	
	$EnableSquidPhishTank_icon="arrow-right-16.png";
	$EnableSquidPhishTank_color="#000000";
	$EnableSquidPhishTank_text=null;
	
	$EnableHTTPSURBL_icon="arrow-right-16.png";
	$EnableHTTPSURBL_color="#000000";
	$EnableHTTPSURBL_text=null;
	
	$WebFilteringRansomware_icon="arrow-right-16.png";
	$WebFilteringRansomware_color="#000000";
	$WebFilteringRansomware_text=null;
	
	
	if($EnableUfdbGuard==0){
		$EnableGoogleSafeSearch=0;
		$EnableGoogleSafeBrowsing=0;
		$EnableSquidPhishTank=0;
		$EnableHTTPSURBL=0;
		$WebFilteringRansomware=0;
	}
	
	if($EnableGoogleSafeSearch==0){
		$EnableGoogleSafeSearch_icon="arrow-right-16-grey.png";
		$EnableGoogleSafeSearch_color="#898989";
		$EnableGoogleSafeSearch_text=" <span style='font-size:12px'>({disabled})</span>";
	}
	
	if($EnableGoogleSafeBrowsing==0){
		$EnableGoogleSafeBrowsing_icon="arrow-right-16-grey.png";
		$EnableGoogleSafeBrowsing_color="#898989";
		$EnableGoogleSafeBrowsing_text=" <span style='font-size:12px'>({disabled})</span>";
	}	
	
	if($EnableSquidPhishTank==0){
		$EnableSquidPhishTank_icon="arrow-right-16-grey.png";
		$EnableSquidPhishTank_color="#898989";
		$EnableSquidPhishTank_text=" <span style='font-size:12px'>({disabled})</span>";
	}	
	if($EnableHTTPSURBL==0){
		$EnableHTTPSURBL_icon="arrow-right-16-grey.png";
		$EnableHTTPSURBL_color="#898989";
		$EnableHTTPSURBL_text=" <span style='font-size:12px'>({disabled})</span>";
	}	
	if($WebFilteringRansomware==0){
		$WebFilteringRansomware_icon="arrow-right-16-grey.png";
		$WebFilteringRansomware_color="#898989";
		$WebFilteringRansomware_text=" <span style='font-size:12px'>({disabled})</span>";
	}	
	
	
	$tr[]="
	<tr>
	<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold;color:$color'>{url_filtering}:</td>
	</tr>";

	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$icon_ufdb'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:$color'>".texttooltip("{service_status}: $WebFiltering_text","position:right:{dashboard_webfiltering_explain}","GoToUfdbguardMain()")."</td>
	</tr>
	<tr>
	<td valign='middle' style='width:25px'>&nbsp;</td>
	<td valign='middle'>
			<table style='width:100%'>
				<tr>
					<td valign='middle' style='width:16px'>
						<img src='img/$EnableGoogleSafeSearch_icon'>
					</td>
					<td valign='middle' style='font-size:14px;width:99%;color:$EnableGoogleSafeBrowsing_color'>".
						texttooltip("{EnableGoogleSafeSearch} $EnableGoogleSafeSearch_text",
						"position:right:Google SafeSearch","GoToUfdbguardMain()")."</td>
				</tr>
				<tr>
					<td valign='middle' style='width:14px'>
						<img src='img/$EnableGoogleSafeBrowsing_icon'>
					</td>
					<td valign='middle' style='font-size:14px;width:99%;color:$EnableGoogleSafeBrowsing_color'>".
						texttooltip("Google SafeBrowsing $EnableGoogleSafeBrowsing_text",
						"position:right:Google SafeBrowsing","GoToUfdbguardMain()")."</td>
				</tr>			
				<tr>
					<td valign='middle' style='width:14px'>
						<img src='img/$EnableSquidPhishTank_icon'>
					</td>
					<td valign='middle' style='font-size:14px;width:99%;color:$EnableSquidPhishTank_color'>".
						texttooltip("PhishTank $EnableSquidPhishTank_text",
						"position:right:PhishTank","GoToUfdbguardMain()")."</td>
				</tr>		
				<tr>
					<td valign='middle' style='width:14px'>
						<img src='img/$EnableHTTPSURBL_icon'>
					</td>
					<td valign='middle' style='font-size:14px;width:99%;color:$EnableHTTPSURBL_color'>".
						texttooltip("SURBLs $EnableHTTPSURBL_text",
						"position:right:SURBLs","GoToUfdbguardMain()")."</td>
				</tr>		
				<tr>
					<td valign='middle' style='width:14px'>
						<img src='img/$WebFilteringRansomware_icon'>
					</td>
					<td valign='middle' style='font-size:14px;width:99%;color:$WebFilteringRansomware_color'>".
						texttooltip("Ransomares $WebFilteringRansomware_text",
						"position:right:Ransomares","GoToUfdbguardMain()")."</td>
				</tr>	
				</table>
		</td>								
	</tr>			
			
";
	

	
	
	
	

	
	
	if($EnableUfdbGuard==1){
		$UfdbEnableParanoidMode=intval($sock->GET_INFO("UfdbEnableParanoidMode"));
		
		$icon_ParanoidMode="arrow-right-24.png";
		$colorParanoidMode="black";
		$js_ParanoidMode="GotoParanoidMode()";
		
		if($UfdbEnableParanoidMode==0){
			$icon_ParanoidMode="arrow-right-24-grey.png";
			$text_ParanoidMode=" <span style='font-size:12px'>({disabled})</span>";
			$colorParanoidMode="#898989";
			$explain_ParanoidMode="{paranoid_squid_mode_explain}";
		}
		
		
		
		$tr[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$icon_ufdb'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:$color'>".texttooltip("{service_parameters}","position:right:{dashboard_webfiltering_service_explain}","GotoUfdbServiceBehavior()")."</td>
		</tr>";
		
		$tr[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$icon_ufdb'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:$color'>".texttooltip("{webfiltering_rules}","position:right:{webfiltering_rules_text}","GoToUfdbguardRules()")."</td>
		</tr>";	
		
		$tr[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$icon_ParanoidMode'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:$colorParanoidMode'>".texttooltip("{paranoid_mode}$text_ParanoidMode","position:right:$explain_ParanoidMode","$js_ParanoidMode")."</td>
		</tr>";		

		$tr[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$icon_ufdb'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:$color'>".
		texttooltip("{webfiltering_groups}","position:right:{webfiltering_groups_text}","GotoUfdbGroups()")."</td>
		</tr>";
		
		
		
		$tr[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$icon_ufdb'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:$color'>".texttooltip("{rewrite_objects}","position:right:{rewrite_rules_fdb_explain}","GotoUfdbRewriteRules()")."</td>
		</tr>";	
		$tr[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$icon_ufdb'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:$color'>".texttooltip("{terms_groups}","position:right:{squid_tgroups_expressionL_explain}","GotoUfdbTermsGroups()")."</td>
		</tr>";	
		
		
		$tr[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$icon_ufdb'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:$color'>".texttooltip("{banned_page_webservice}","position:right:{deny_web_page_text}","GotoUfdbErrorPage()")."</td>
		</tr>";
		
		
		
		$tr[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$icon_category'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:$color_category'>".
		texttooltip("{your_categories}","position:right:$explain_category",$js_categories)."</td>
		</tr>";	
		
		
		
		$tr[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$icon_stats'></td>
			<td valign='middle' style='font-size:18px;width:99%;color:$color_stats'>".texttooltip("{statistics}$stats_text","position:right:{dashboard_webfilteringstats_explain}","GoToUfdb()")."</td>
		</tr>";
	}else{
		$tr[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$icon_category'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:$color_category'>".
		texttooltip("{your_categories}","position:right:$explain_category","$js_categories")."</td>
		</tr>";
	}
	
	
	$color_av_title="black";
	
	
	
	$tr[]="
		<tr>
		<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold'>&nbsp;</td>
	</tr>";
	
	$tr[]="
	<tr>
	<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold;color:black'>{antivirus_protection}:</td>
	</tr>";
	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
		<img src='img/$ecap_clamav_icon'>
	</td>
		<td valign='middle' style='font-size:18px;width:99%;color:$ecap_clamav_color'>".
			texttooltip("{integrated_antivirus} <span style='font-size:12px'>$ecap_clamav_text","position:right:{integrated_antivirus_explain}","GoToeCapClamav()")."</td>
	</tr>";	

	$tr[]="<tr>
		<td valign='middle' style='width:25px'>
			<img src='img/$icon_av'>
		</td>
		<td valign='middle' style='font-size:18px;width:99%;color:$color_av'>".
			texttooltip("{web_antivirus}$av_textexplain","position:right:$explain_av","$js_cicap")."</td>
		</tr>";	
		
				
	$color_kaspersky="#000000";
	$icon_kaspersky="kaspersky-24-green.png";
	if(!$users->KAV4PROXY_INSTALLED){
		$icon_kaspersky="kaspersky-24-grey.png";
		$color_kaspersky="#898989";
		$explain_kaspersky="{product_not_installed_explain}";
		$explain_kav="<br><span style='font-size:12px'>{not_installed}</span>";
	}

	
	

	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$icon_kaspersky'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:$color_kaspersky'>".texttooltip("{APP_KAV4PROXY}$explain_kav","$explain_kaspersky<br>{kav4proxy_about}","GoToKav4Proxy()")."</td>
	</tr>";
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/arrow-right-24.png'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{icap_center}","{icap_center_explain}","GotoICAPCenter()")."</td>
	</tr>";	
	
	
	$tr[]="</table>";
	
	
	$tr2[]="
	<table style='width:100%'>
	<tr>
	<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold'>{ACLS}:</td>
	</tr>";
	if($users->AsDansGuardianAdministrator){
		
		
		$tr2[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$icon'></td>
		<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{simple_acls}","position:right:{GLOBAL_ACCESS_CENTER_EXPLAIN}","GotoGlobalBLCenter()")."</td>
		</tr>";		
		
		$tr2[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$icon'></td>
			<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{complete_acls}","position:right:{access_rules_text}","GoToSquidAcls()")."</td>
		</tr>";
		

		
		$tr2[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$icon'></td>
		<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{browsers_rules}","position:right:{browsers_rules}","GoToSquidAclsBrowsers()")."</td>
		</tr>";
		
		$tr2[]="
		<tr>
			<td valign='middle' style='width:25px'><img src='img/$icon'></td>
			<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{permanent_authorizations}",null,"GoToSquidAclsOptions()")."</td>
		</tr>";
		
		$tr2[]="
		<tr style='height:40px'>
			<td valign='middle' colspan=2 style='font-size:18px;font-weight:bold;color:$color'>{proxy_objects}:</td>
		</tr>";
	
		$tr2[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$icon'></td>
		<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{proxy_objects}","position:right:{proxy_objects}","GoToSquidAclsGroups()")."</td>
		</tr>";	
		$tr2[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$icon'></td>
		<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{sessions_tracking_objects}","position:right:{sessions_tracking_objects}","GoToSquidSessionsObjects()")."</td>
		</tr>";

		
		$tr2[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$icon'></td>
		<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{quota_objects}","position:right:{quota_objects}","GotoProxyQuotasObjects()")."</td>
		</tr>";		
		

		
		$tr2[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$icon'></td>
		<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{bandwith_limitation_full}","position:right:{squid_bandwith_rules_explain}","GoToSquidAclsBandwidth()")."</td>
		</tr>";
		

	}
	
	$tr2[]="
		<tr>
		<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold'>&nbsp;</td>
	</tr>";
	
	
	$tr2[]="
	<tr>
	<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold;color:black'>{ads_protection}:</td>
	</tr>";
	
	
	$PrivoxyEnabled=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/PrivoxyEnabled"));
	$PrivoxyInstalled=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/PrivoxyInstalled"));
	
	$icon_privoxy="arrow-right-24.png";
	$colorprivoxy="black";
	$js_privoxy="GotoPrivoxy()";
	$text_privoxy=null;
	$explain_privoxy="{privoxy_explain}";
	
	if($PrivoxyEnabled==0){
		$icon_privoxy="arrow-right-24-grey.png";
		$text_privoxy=" <span style='font-size:12px'>({disabled})</span>";
		$colorprivoxy="#898989";
		
	}
	if($PrivoxyInstalled==0){
		$icon_privoxy="arrow-right-24-grey.png";
		$text_privoxy=" <span style='font-size:12px'>({not_installed})</span>";
		$js_privoxy="blur()";
		$colorprivoxy="#898989";		
	}
	
	$tr2[]="
	<tr>
		<td valign='middle' style='width:25px'><img src='img/$icon_privoxy'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:$colorprivoxy'>".texttooltip("{APP_PRIVOXY}$text_privoxy","position:right:$explain_privoxy","$js_privoxy")."</td>
	</tr>";
	
	$tr2[]="</table>";
	
	$final="
	<table style='width:100%'>
	<tr>
		<td style='width:50%' valign='top'>".$tpl->_ENGINE_parse_body(@implode("\n", $tr))."</td>
		<td style='width:50%' valign='top'>".$tpl->_ENGINE_parse_body(@implode("\n", $tr2))."</td>
	</tr>
	</table>
	";
	$filename="/usr/share/artica-postfix/ressources/logs/web/cache/".md5("FILTERSECTION".$tpl->language.$_SESSION["uid"]);
	@file_put_contents($filename, $final);
	echo $final;
	
}

function update_section(){
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$icon="arrow-right-24.png";
	$clamav_icon=$icon;
	$tr[]="<table style='width:100%'>";
	
	if($users->AsSquidAdministrator){
		$tr[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$icon'></td>
		<td valign='middle' style='font-size:18px;width:99%'>".
		texttooltip("{tasks}","position:right:{squid_tasks_explain}","javascript:GotoSquidTasks();")."</td>
		</tr>";
		
	}
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$icon'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{webfilter_databases}",
			"position:top:{webfilter_databases_update_explain}","GoToWebfilteringDBstatus()")."</td>
	</tr>";

	
	
	
	
	$clamav_explain="{clamav_antivirus_databases_explain}";
	$CicapEnabled=intval($sock->GET_INFO("CicapEnabled"));
	if($CicapEnabled==1){
		$bases=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/interface-cache/ClamAVBases"));
		if(count($bases)<2){
			$clamav_explain="{missing_clamav_pattern_databases}";
			$clamav_icon="alert-24.png";
		}
	}
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$icon'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{clamav_antivirus_databases}",
			"position:top:$clamav_explain","GotoClamavUpdates()")."</td>
	</tr>";	
	
	
	

	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$icon'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{update_proxy_engine}","position:top:{proxy_engine_available_explain}","javascript:LoadProxyUpdate();")."</td>
	</tr>";	
	
	$tr[]="</table>";
	$html =$tpl->_ENGINE_parse_body(@implode("\n", $tr));
	$update_file="/usr/share/artica-postfix/ressources/logs/web/cache/".md5("UPDATESECTION".$tpl->language.$_SESSION["uid"]);
	@file_put_contents($update_file, $html);
	echo $html;
	
}

function debug_section(){
	$tpl=new templates();
	$users=new usersMenus();
	$icon="arrow-right-24.png";
	$tr[]="<table style='width:100%'>";
	
	if($users->AsSquidAdministrator){
		$tr[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$icon'></td>
		<td valign='middle' style='font-size:18px;width:99%'>".
		texttooltip("{emergency_modes}","position:top:{emergency_modes_explain}",
				"javascript:Loadjs('squid.emergency.modes.php');")."</td>
		</tr>";
		
		$tr[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$icon'></td>
		<td valign='middle' style='font-size:18px;width:99%'>".
		texttooltip("{performance}","position:top:{performance_squid_explain}",
				"GotoSquidPerformances()")."</td>
		</tr>
		<tr>
			<td valign='middle' style='width:25px'>&nbsp;</td>
			<td valign='middle' style='font-size:16px;width:99%'>
			<table style='width:100%'>
				<tr>
					<td valign='middle' style='width:25px'><img src='img/arrow-right-16.png'></td>	
					<td valign='middle' style='font-size:16px;width:99%'>".		
						texttooltip("{file_descriptors}","position:top:{file_descriptors_squid_explain}",
				"javascript:Loadjs('squid.file_desc.php')")."</td>
				</tr>
				</table>
			</td>
		</tr>
		";
		
		
		$tr[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$icon'></td>
		<td valign='middle' style='font-size:18px;width:99%'>".
		texttooltip("{proxy_service}","position:top:{enable_squid_service_text}",
				"Loadjs('squid.newbee.php?reactivate-squid=yes')")."</td>
		</tr>";		
		
		
	
	}
	
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$icon'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{proxy_service_events}","position:top:{proxy_service_events}","javascript:GotoLogsCacheSquid();")."</td>
	</tr>";
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$icon'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{debug_tools}","position:right:{debug_tools}","javascript:LoadProxyDebug();")."</td>
	</tr>";
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$icon'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{old_status} 1.9x","position:right:{old_status_19_squid}","javascript:GotoSquidOldStatus();")."</td>
	</tr>";	
	
	
	$tr[]="</table>";
		
		
		$html=$tpl->_ENGINE_parse_body(@implode("\n", $tr));
		$monitor_file="/usr/share/artica-postfix/ressources/logs/web/cache/".md5("DEBUGSECTION".$tpl->language.$_SESSION["uid"]);
		@file_put_contents($monitor_file, $html);
		echo $html;
	
	
}

