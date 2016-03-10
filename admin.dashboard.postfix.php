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
include_once(dirname(__FILE__).'/ressources/class.postfix-multi.inc');

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
	$messaging_stopped_explain=null;
	$js_infrasection="LoadAjaxRound('infra-section','$page?infra-section=yes');";
	$js_filtersection="LoadAjaxRound('filter2-section','$page?filter2-section=yes');";
	$js_controlsection="LoadAjaxRound('control-section','$page?control-section=yes');";
	$js_monitorsection="LoadAjaxRound('monitor-section','$page?monitor-section=yes');";
	$js_debugsection="LoadAjaxRound('debug-section','$page?debug-section=yes');";
	$js_updatesection="LoadAjaxRound('update-section','$page?update-section=yes');";
	$EnableStopPostfix=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableStopPostfix"));
	if(!is_dir("/usr/share/artica-postfix/ressources/logs/web/cache")){@mkdir("/usr/share/artica-postfix/ressources/logs/web/cache",0755,true);}
	
	$sock=new sockets();
	$MyHostname=$sock->GET_INFO("myhostname");
	
	if($EnableStopPostfix==1){
		$messaging_stopped_explain="<p class=text-error style='font-size:18px'>{messaging_stopped_explain}</p>";
	}
	
	
	
	$monitor_file="/usr/share/artica-postfix/ressources/logs/web/cache/".md5("postfix-MONITORSECTION".$tpl->language.$_SESSION["uid"]);
	if(is_file($monitor_file)){
		$monitor_content=@file_get_contents($monitor_file);
		if(trim($monitor_content)<>null){
			$js_monitorsection=null;
		}
	}	
	
	$debug_file="/usr/share/artica-postfix/ressources/logs/web/cache/".md5("postfix-DEBUGSECTION".$tpl->language.$_SESSION["uid"]);
	if(is_file($debug_file)){
		$debug_content=@file_get_contents($debug_file);
		if(trim($debug_content)<>null){
			$js_debugsection=null;
		}
	}
	
	$update_file="/usr/share/artica-postfix/ressources/logs/web/cache/".md5("postfix-UPDATESECTION".$tpl->language.$_SESSION["uid"]);
	if(is_file($update_file)){
		$update_content=@file_get_contents($update_file);
		if(trim($update_content)<>null){
			$js_updatesection=null;
		}
	}	
	
	$html="
	<input type='hidden' id='thisIsThePostfixDashBoard' value='1'>
	<div style='margin-top:30px;margin-bottom:30px;font-size:40px;passing-left:30px;'>{messaging} &laquo;&nbsp;".
	texttooltip($MyHostname,"{myhostname}","Loadjs('postfix.myhostname.php')")."&nbsp;&raquo;</div>$messaging_stopped_explain
	<div style='padding-left:30px;padding-right:30px'>			
	<table style='width:100%'>
	<tr>
		<td style='width:50%;vertical-align:top'>
		<table style='width:100%'>
			<tr>
			<td valign='top' style='width:96px'><img src='img/filter-96.png' style='width:96px'></td>
			<td valign='top' style='width:99%'>
				<div style='font-size:30px;margin-bottom:20px'>{connections_filters}</div>
				<div id='filter2-section' style='padding-left:15px'>$filter_content</div>
			</td>
			</tr>
			</table>
		<td style='width:50%;vertical-align:top;border-left:4px solid #CCCCCC;padding-left:15px'>
			<table style='width:100%'>
			<tr>
			<td valign='top' style='width:96px'><img src='img/infrastructure-96.png' style='width:96px'></td>
			<td valign='top' style='width:99%'>
				<div style='font-size:30px;margin-bottom:20px'>{postfixinfra}</div>
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
				<div style='font-size:30px;margin-bottom:20px'>{monitor}</div>
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
	$MimeDefangEnabled=intval($sock->GET_INFO('MimeDefangEnabled'));
	if(!$users->MIMEDEFANG_INSTALLED){$MimeDefangEnabled=0;}

	
	$attachments_icon="arrow-right-24.png";
	$attachments_color="#000000";
	$attachments_js="GotoSMTPAttachments()";
	$attachments_text=null;
	
	if($MimeDefangEnabled==0){
		$attachments_icon="arrow-right-24-grey.png";
		$attachments_color="#898989";
		$attachments_text=" <span style='font-size:14px'>({disabled})</span>";
		$attachments_js="GotoMessagingSecurityUpdate()";
	}
	
	if(!$users->MIMEDEFANG_INSTALLED){
		$attachments_text=" <span style='font-size:14px'>({not_installed})</span>";
		
	}
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$attachments_icon'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:$attachments_color'>
		".texttooltip("{attachments_monitor}",
					"position:top:{attachments_monitor}","$attachments_js")."</td>
	</tr>";
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/arrow-right-24.png'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>
		".texttooltip("{queue_management}",
		 "position:top:{queue_management}","GotoPostfixQueues()")."</td>
	</tr>";	
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/arrow-right-24.png'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>
		".texttooltip("{watchdog_queue}",
					"position:top:{watchdog_queue_text}","Loadjs('postfix.postqueuep.php',true)")."</td>
	</tr>";
	
	
	
	$tr[]="</table>";
	$html=$tpl->_ENGINE_parse_body(@implode("\n", $tr));	
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
	$colornet="#000000";
	$GotoSSLEncrypt="GotoSSLEncrypt()";
	$iconnet="arrow-right-24.png";
	$main=new maincf_multi("master");
	$EnablePostfixHaProxy=intval($main->GET("EnablePostfixHaProxy"));
	$EnableFetchmail=intval($sock->GET_INFO("EnableFetchmail"));
	
	$main=new main_cf();
	$array_mynetworks=$main->array_mynetworks;

	if(count($array_mynetworks)==0){
		$iconnet="alert-24.png";
		$colornet="#D22C2C";
	}
	
	$network="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$iconnet'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$colornet'>".
		texttooltip("{postfix_network}","position:left:{postfix_network_text}","GoToPostfixNetworks()")."</td>
	</tr>";	
	
	$domains="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon_ssl'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{domains}","{domains}","GoToPostfixDomains()")."</td>
	</tr>";	
	
	
	$diffusion_lists="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon_ssl'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{diffusion_lists}","{mysql_routing_table_list_explain}","GotoPostfixDitribs()")."</td>
	</tr>";
	
	
	$transport="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon_ssl'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{transport_table}","{transport_table}","GoToPostfixRouting()")."</td>
	</tr>";
	
	
	$mailbox_agent="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon_ssl'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{mailbox_agent}","{mailbox_agent_text}","Loadjs('postfix.mailbox_transport.php?hostname=master&ou=master');")."</td>
	</tr>";	

	
	
	$tr[]="<table style='width:100%'>
	<tr>
	<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold'>{infrastructure}:</td>
	</tr>";
	
	

	$HaProxy=Paragraphe("64-computer-alias.png","{load_balancing_compatibility}","{load_balancing_compatibility_text}",
			"javascript:");
	

	
	
		$tr[]=$network;
		$tr[]=$domains;
		$tr[]=$transport;
		$tr[]=$diffusion_lists;
		$tr[]=$mailbox_agent;
		
		
		$fetchmail_text=null;
		$fetchmail_color="#000000";
		$fetchmail_icon="arrow-right-24.png";
		if($EnablePostfixHaProxy==0){
			$fetchmail_color="#898989";
			$fetchmail_icon="arrow-right-24-grey.png";
			$fetchmail_text=" <span style='font-size:14px'>({disabled})</span>";
		}
		
		if($EnableFetchmail==0){
			$fetchmail_color="#898989";
			$fetchmail_icon="arrow-right-24-grey.png";
			$fetchmail_text=" <span style='font-size:14px'>({disabled})</span>";			
			
		}
		
		if(!$usersmenus->fetchmail_installed){
			$fetchmail_color="#898989";
			$fetchmail_icon="arrow-right-24-grey.png";
			$fetchmail_text=" <span style='font-size:14px'>({not_installed})</span>";
			
		}

	
		$tr[]="<tr>
		<td valign='middle' style='width:25px'>
		<img src='img/$fetchmail_icon'>
		</td>
		<td valign='middle' style='font-size:18px;width:99%;color:$fetchmail_color'>".texttooltip("{APP_FETCHMAIL}$fetchmail_text",
				"position:left:{APP_FETCHMAIL}","GotoFetchMail()")."</td>
	</tr>";
		
		
		
		
		$HaProxy_text=null;
		$HaProxy_color="#000000";
		$HaProxy_icon="arrow-right-24.png";
		if($EnablePostfixHaProxy==0){
			$HaProxy_color="#898989";
			$HaProxy_icon="arrow-right-24-grey.png";
			$HaProxy_text=" <span style='font-size:14px'>({disabled})</span>";
		}
	
		$tr[]="<tr>
		<td valign='middle' style='width:25px'>
		<img src='img/$HaProxy_icon'>
		</td>
		<td valign='middle' style='font-size:18px;width:99%;color:$HaProxy_color'>".texttooltip("{load_balancing_compatibility}$HaProxy_text",
		"position:left:{load_balancing_compatibility_text}","Loadjs('postfix.haproxy.php?hostname=master&ou=master');")."</td>
	</tr>";
		
		
	$tr[]="<tR><td colspan=2>&nbsp;</td></tr>";
	$tr[]="<table style='width:100%'>
	<tr>
	<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold'>{parameters}:</td>
	</tr>";
	
	

	$tr[]="<tr>
		<td valign='middle' style='width:25px'>
		<img src='img/arrow-right-24.png'>
		</td>
		<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{SMTP_BANNER}",
					"position:left:{SMTP_BANNER_TEXT}","Loadjs('postfix.banner.php?hostname=master&ou=master')")."</td>
	</tr>";	
	
	$tr[]="<tr>
		<td valign='middle' style='width:25px'>
		<img src='img/arrow-right-24.png'>
		</td>
		<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{MIME_OPTIONS}",
					"position:left:{MIME_OPTIONS_TEXT}","Loadjs('postfix.mime.php?hostname=master&ou=master')")."</td>
	</tr>";	
	
	$tr[]="<tr>
		<td valign='middle' style='width:25px'>
		<img src='img/arrow-right-24.png'>
		</td>
		<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{performances_settings}",
		"position:left:{performances_settings_text}","Loadjs('postfix.performances.php')")."</td>
	</tr>";	

	
	
	
	$tr[]="<tr>
		<td valign='middle' style='width:25px'>
		<img src='img/arrow-right-24.png'>
		</td>
		<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{other_settings}",
					"position:left:{other_settings_text}","Loadjs('postfix.other.php')")."</td>
	</tr>";	
	
	$tr[]="</table>";	
	
// ***************************************************************************************************	
	
	$tr2[]="<table style='width:100%'>";
	
	
	if(AsMainOrgAdmin()){
	$tr2[]="
	<tr>
		<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold'>{organizations}:</td>
	</tr>";
	
	$tr2[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/arrow-right-24.png'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".
		texttooltip("{all_organizations}","{organizations}","GoToOrganizations()")."</td>
	</tr>";
	$tr2[]="
	<tr>
		<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold'>&nbsp;</td>
	</tr>";
	}
	
	if($users->ZARAFA_INSTALLED){
	$tr2[]="
	<tr>
		<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold'>{mailboxes}:</td>
	</tr>";
	
	
	$tr2[]="<tr>
	<td valign='middle' style='width:25px'><img src='img/arrow-right-24.png'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{APP_ZARAFA}",
	"{APP_ZARAFA_TEXT}","GoToZarafaMain()")."</td>
	</tr>";
	

	$tr2[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/arrow-right-24.png'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".
		texttooltip("{mailboxes}","{APP_ZARAFA_TEXT}","GoToZarafaMailboxes()")."
		</td>
	</tr>
	<tr><td colspan=2>		<div style='margin-left:20px'>
		<table style='width:100%'>
				<tr>
					<td valign='middle' style='width:25px'><img src=img/plus-16.png></td>
					<td valign='middle' style='font-size:14px;width:99%'>".	
					texttooltip("{new_mailbox}","{new_mailbox}","Loadjs('create-user.php')")."
					</td>
				</tr>
		</table>
		</div>
	</td>
	</tr>";
				
				
	
	
	$tr2[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/arrow-right-24.png'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".
		texttooltip("WebMail","{APP_ZARAFA_TEXT}","GoToZarafaWebMail()")."</td>
	</tr>";	
	$tr2[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/arrow-right-24.png'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".
		texttooltip("{smartphones}","{APP_ZARAFA_TEXT}","GoToZarafaZPush()")."</td>
	</tr>";
	
	
	
	
	$tr2[]="
	<tr>
		<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold'>&nbsp;</td>
	</tr>";
	
	}
	
	if($users->cyrus_imapd_installed){
		$tr2[]="
	<tr>
		<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold'>{mailboxes}:</td>
	</tr>";
		
		
	$tr2[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/arrow-right-24.png'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".
			texttooltip("{APP_CYRUS_IMAP}","{about_cyrus}","GotoCyrusManager()")."</td>
	</tr>";
	$tr2[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/arrow-right-24.png'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".
		texttooltip("WebMail","{APP_ROUNDCUBE_TEXT}","GoToRoundCube()")."</td>
	</tr>";
	

	$tr2[]="
	<tr>
		<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold'>&nbsp;</td>
	</tr>";
		
	}
	
	

	$tr2[]="
	<tr>
		<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold'>{backup}:</td>
	</tr>";
	
	
	
	$Color="black";
	$icon="Database24.png";
	
	
	$backup_text=null;
	$backup_color="#000000";
	$backup_icon="arrow-right-24.png";
	$backup_js="GoToBackupeMail()";
	$MimeDefangEnabled=intval($sock->GET_INFO("MimeDefangEnabled",true));
	$MimeDefangArchiver=intval($sock->GET_INFO("MimeDefangArchiver",true));
	if($MimeDefangEnabled==0){$MimeDefangArchiver=0;}
	
	if($MimeDefangEnabled==0){
		$backup_js="blur()";
		$backup_color="#898989";
		$backup_icon="arrow-right-24-grey.png";
		$backup_text=" <span style='font-size:14px'>({disabled})</span>";
	}
	
	if(!$users->MIMEDEFANG_INSTALLED){
		$backup_js="blur()";
		$backup_color="#898989";
		$backup_icon="arrow-right-24-grey.png";
		$backup_text=" <span style='font-size:14px'>({not_installed})</span>";
	}
	
	
	$tr2[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$backup_icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$backup_color'>".
			texttooltip("{backupemail_behavior}","{backupemail_behavior}$backup_text","GoToBackupeMail()")."</td>
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
	
	
	echo $final;
}

function control_section(){
	$sock=new sockets();
	$tpl=new templates();
	
	$PostfixEnableMasterCfSSL=intval($sock->GET_INFO("PostfixEnableMasterCfSSL"));
	$PostFixSmtpSaslEnable=intval($sock->GET_INFO("PostFixSmtpSaslEnable"));
	$MimeDefangEnabled=intval($sock->GET_INFO('MimeDefangEnabled'));
	$MimeDefangDisclaimer=intval($sock->GET_INFO('MimeDefangDisclaimer'));
	$MimeDefangAutoCompress=intval($sock->GET_INFO("MimeDefangAutoCompress"));
	$users=new usersMenus();
	
	
	$OKQuota=true;
	
	
	$color_quota="black";
	$tr[]="<table style='width:100%'>";
	
	
	
	
	$icon="arrow-right-24.png";
	$icon_quota=$icon;
	$js_quota="GotoSquidWebfilterQuotas()";
	if(!$OKQuota){
		$js_quota="blur()";
		$icon_quota="arrow-right-24-grey.png";
		$color_quota="#898989";
	}
	
	$tr[]="<table style='width:100%'>";
	
	
	
	$ad_icon="arrow-right-24.png";
	$ad_script="GoToActiveDirectory()";
	$ad_color="black";
	$ad_explain="{dashboard_activedirectory_explain}";
	
	$phpldapadm_icon="arrow-right-24.png";
	$phpldapadm_color="#898989";
	$phpldapadm_title="{APP_PHPLDAPADMIN}";
	$phpldapadm_explain="{APP_PHPLDAPADMIN_TEXT}";
	
	
	$remote_ldap_color="#898989";
	$remote_ldap_icon="arrow-right-24-grey.png";
	

	
	$phpldapadm_explain="{APP_PHPLDAPADMIN_TEXT}";
	$sock=new sockets();
	$phpldapadmin_installed=false;
	if(trim($sock->getFrameWork("system.php?phpldapadmin_installed=yes"))=="TRUE"){
		$phpldapadmin_installed=true;
	}
	
	if($phpldapadmin_installed){
			$phpldapadm_icon="arrow-right-24.png";
			$phpldapadm_color="#000000";
			$phpldapadm_title="{APP_PHPLDAPADMIN}";
			$phpldapadm_js="s_PopUpFull('/ldap',1024,768,'PHPLDAPADMIN')";
			
		}else{
			$phpldapadm_icon="info-24.png";
			$phpldapadm_title="{INSTALL_PHPLDAPADMIN}";
			$phpldapadm_js="Loadjs('phpldapadmin.progress.php')";
		}

	//<APP_PHPLDAPADMIN>phpLDAPadmin</APP_PHPLDAPADMIN>
	//<APP_PHPLDAPADMIN_TEXT>Browse the LDAP directory using phpLDAPAdmin front-end</APP_PHPLDAPADMIN_TEXT>

	
		$postfix_ssl_icon="arrow-right-24.png";
		$postfix_ssl_color="#000000";
		$postfix_ssl_text=null;
		
		$postfix_auth_icon="arrow-right-24.png";
		$postfix_auth_color="#000000";
		$postfix_auth_text=null;
		

	if($PostfixEnableMasterCfSSL==0){
		$postfix_ssl_icon="arrow-right-24-grey.png";
		$postfix_ssl_color="#898989";
		$postfix_ssl_text=" <span style='font-size:14px'>({disabled})</span>";
		
		
	}
	
	if($PostFixSmtpSaslEnable==0){
		$postfix_auth_icon="arrow-right-24-grey.png";
		$postfix_auth_color="#898989";
		$postfix_auth_text=" <span style='font-size:14px'>({disabled})</span>";
		
	}
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$postfix_ssl_icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$postfix_ssl_color'>".texttooltip("SMTP SSL$postfix_ssl_text",
				"position:right:{ENABLE_SMTPS}","GotoPostfixSSL()")."</td>
	</tr>";
		

	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$postfix_auth_icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$postfix_auth_color'>".texttooltip("{smtp_authentication}$postfix_auth_text",
				"position:right:{smtp_authentication}","GotoPostfixAuth()")."</td>
	</tr>";	
	
	
	/*$tr[]="
	<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$ad_icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$ad_color'>".
	texttooltip("Active Directory","position:right:$ad_explain",$ad_script)."</td>
	</tr>";
*/
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/arrow-right-24.png'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{postmaster}",
			"position:right:{postmaster_text}","GotoPostfixPostmaster()")."</td>
	</tr>";
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/arrow-right-24.png'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{POSTFIX_SMTP_NOTIFICATIONS}",
				"position:right:{POSTFIX_SMTP_NOTIFICATIONS}","GotoPostfixNotifications()")."</td>
	</tr>";
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/arrow-right-24.png'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{unknown_users}",
				"position:right:{postfix_unknown_users_tinytext}","Loadjs('postfix.luser_relay.php')")."</td>
	</tr>";	

	
	
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$phpldapadm_icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$phpldapadm_color'>".texttooltip($phpldapadm_title,
			"position:right:$phpldapadm_explain",$phpldapadm_js)."</td>
	</tr>";	
	
	

	$disclaimer_icon="arrow-right-24.png";
	$disclaimer_color="#000000";
	$disclaimer_js="GotoMimeDefangDisclaimers()";
	$disclaimer_text=null;
	
	$autcompress_icon="arrow-right-24.png";
	$autcompress_color="#000000";
	$autcompress_js="GotoMimeDefangAutocompress()";
	$autcompress_text=null;
	
	
	
	if($MimeDefangEnabled==0){$MimeDefangDisclaimer=0;$MimeDefangAutoCompress=0;}
	if(!$users->MIMEDEFANG_INSTALLED){$MimeDefangEnabled=0;$MimeDefangAutoCompress=0;}
	
	

	
	if($MimeDefangDisclaimer==0){
		$disclaimer_icon="arrow-right-24-grey.png";
		$disclaimer_color="#898989";
		$disclaimer_js="blur()";
		$disclaimer_text=" <span style='font-size:14px'>({disabled})</span>";
	}
	if($MimeDefangAutoCompress==0){
		$autcompress_icon="arrow-right-24-grey.png";
		$autcompress_color="#898989";
		$autcompress_js="blur()";
		$autcompress_text=" <span style='font-size:14px'>({disabled})</span>";
	}

	if(!$users->MIMEDEFANG_INSTALLED){
		$disclaimer_text=" <span style='font-size:14px'>({not_installed})</span>";
		$autcompress_text=" <span style='font-size:14px'>({not_installed})</span>";
		
	}
	
	$tr[]="</table>";
	
	$tr2[]="<table style='width:100%'>";

	$tr2[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$disclaimer_icon'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:$disclaimer_color'>".texttooltip("{disclaimers}$disclaimer_text","position:right:{disclaimer_explain}",$disclaimer_js)."</td>
	</tr>";
	
	$tr2[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$autcompress_icon'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:$autcompress_color'>".
			texttooltip("{automated_compression}$autcompress_text",
			"position:right:{auto-compress_text}",$autcompress_js)."</td>
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
	
	echo $final;
	
	
}

function filter2_section(){
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	$EnableIntelCeleron=intval($sock->GET_INFO("EnableIntelCeleron"));
	$EnablePolicydWeight=intval($sock->GET_INFO('EnablePolicydWeight'));
	$EnableDKFilter=intval($sock->GET_INFO('EnableDKFilter'));
	$EnablePOSTFWD2=intval($sock->GET_INFO('EnablePOSTFWD2'));
	$MilterGreyListEnabled=intval($sock->GET_INFO('MilterGreyListEnabled'));
	$MimeDefangEnabled=intval($sock->GET_INFO('MimeDefangEnabled'));
	$MimeDefangClamav=intval($sock->GET_INFO("MimeDefangClamav"));
	
	if(!$users->MIMEDEFANG_INSTALLED){$MimeDefangEnabled=0;}
	if($MimeDefangEnabled==0){
		$MimeDefangClamav=0;
	}
	
	
	$mimeav_color="#000000";
	$icon="arrow-right-24.png";
	$mimeav_icon="arrow-right-24.png";
	$clamav_icon="arrow-right-24.png";
	$mimeav_text=null;
	$mimeav_js="GotoMimeDefangAntivirus()";

	if($MimeDefangClamav==0){
		$mimeav_color="#898989";
		$mimeav_icon="arrow-right-24-grey.png";
		$mimeav_text=" <span style='font-size:14px'>({disabled})</span>";
		$mimeav_js="blur()";
		
	}
	
	$clamav_explain="{clamav_antivirus_databases_explain}";
	$bases=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/interface-cache/ClamAVBases"));
	if(count($bases)<2){
		$clamav_explain="{missing_clamav_pattern_databases}";
		$clamav_icon="alert-24.png";
	}
	
	if(!$users->MIMEDEFANG_INSTALLED){
		$mimeav_text=" <span style='font-size:14px'>({not_installed})</span>";
		$mimeav_js="blur()";
		
	}

	
	
	$explain_category="{your_categories_explain}";
	
	if(!$users->CORP_LICENSE){
		$icon_category="arrow-right-24-grey.png";
		$color_category="#898989";
		$explain_category="{this_feature_is_disabled_corp_license}";
	}
	
	$mgrey_js2="GotoMilterGreyListACLS();";
	
	$tr[]="<!-- ".__LINE__."  -->\n<table style='width:100%'>";
	
	$tr[]="<!-- ".__LINE__."  -->
	<tr>
		<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold;color:black'>{transport}:</td>
	</tr>";
	
	 
	
	$tr[]="
	<!-- ".__LINE__."  -->
	<tr>
		<td valign='middle' style='width:25px'><img src='img/arrow-right-24.png'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:black'>".
		texttooltip("{safety_standards}","position:right:{safety_standards}","GotoSMTPRFC()")."</td>
	</tr>";
	
	$tr[]="<!-- ".__LINE__."  -->
	<tr>
		<td valign='middle' style='width:25px'><img src='img/arrow-right-24.png'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:black'>".
		texttooltip("{acls}","position:right:{acls}",$mgrey_js2)."</td>
		</tr>";
	
	
	$DKFilter_icon="arrow-right-24.png";
	$DKFilter_text=null;
		
	if($EnableDKFilter==0){
		$DKFilter_icon="arrow-right-24-grey.png";;
		$DKFilter_text=" <span style='font-size:14px'>({disabled})</span>";
		$DKFilter_color="#898989";
	}
	
	$POSTFWD2_icon="arrow-right-24.png";;
	$POSTFWD2_text=null;
	if($EnablePOSTFWD2==0){
		$POSTFWD2_icon="arrow-right-24-grey.png";;
		$POSTFWD2_text=" <span style='font-size:14px'>({disabled})</span>";
		$POSTFWD2_color="#898989";
	}
	
	
	
	$tr[]="<!-- ".__LINE__."  -->
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$DKFilter_icon'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:$DKFilter_color'>".
		texttooltip("{APP_OPENDKIM}$DKFilter_text",
		"position:right:{APP_OPENDKIM_TEXT}","GoToOpenDKIM()")."</td>
	</tr>";
	
	$tr[]="<!-- ".__LINE__."  -->
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$POSTFWD2_icon'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:$POSTFWD2_color'>".
		texttooltip("{APP_POSTFWD2}$POSTFWD2_text",
		"position:right:{APP_POSTFWD2}","GotoPostfixPostfwd2()")."</td>
	</tr>";

	$tr[]="<tr><td colspan=2>&nbsp;</td></tr>";
	
	$tr[]="<!-- ".__LINE__."  -->
	<tr>
		<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold;color:black'>{APP_MILTERGREYLIST}:</td>
	</tr>";
	
	
	$mgrey_color="#000000";
	$mgrey_icon="arrow-right-24.png";
	$mgrey_js="GotoMilterGreyListMain()";

	
	$MilterGreyListEnabled=intval($sock->GET_INFO("MilterGreyListEnabled"));
	
	if(!$users->MILTERGREYLIST_INSTALLED){
		$mgrey_color="#898989";
		$mgrey_js=null;
		$mgrey_js2=null;
		$mgrey_js3=null;
		$mgrey_icon="arrow-right-24-grey.png";
	}
	
	if($MilterGreyListEnabled==0){
		$mgrey_color="#898989";
		$mgrey_icon="arrow-right-24-grey.png";
		
	}
	
		$tr[]="<!-- ".__LINE__."  -->
			<tr>
			<td valign='middle' style='width:25px'><img src='img/$mgrey_icon'></td>
			<td valign='middle' style='font-size:18px;width:99%;color:$mgrey_color'>".
					texttooltip("{main_settings}","position:right:{main_settings}",$mgrey_js)."</td>
			</tr>";
		
		
		
		$tr[]="<!-- ".__LINE__."  -->\n<tr><td colspan=2>&nbsp;</td></tr>";
		$tr[]="<!-- ".__LINE__."  -->
		<tr>
			<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold;color:black'>{antivirus_for_messaging}:</td>
		</tr>";
		
		
		$tr[]="<!-- ".__LINE__."  -->
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$clamav_icon'></td>
		<td valign='middle' style='font-size:18px;width:99%'>".
		texttooltip("{clamav_antivirus_databases}",
				"position:top:$clamav_explain","GotoClamavUpdates()")."</td>
				</tr>";
		
		
		$tr[]="<!-- ".__LINE__."  -->
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$mimeav_icon'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:$mimeav_color'>".
		texttooltip("{antivirus_rules}$mimeav_text",
				"position:top:{antivirus_rules}","$mimeav_js")."</td>
				</tr>";	
		
	
	$tr[]="<!-- ".__LINE__."  --></table>";
	
	
	$tr2[]="
	<!-- ".__LINE__."  -->\n<table style='width:100%'>
	<tr>
	<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold'>{content_filters}:</td>
	</tr>";
	if($users->AsPostfixAdministrator){
		$EnableMilterRegex=intval($sock->GET_INFO("EnableMilterRegex"));
		$SpamAssMilterEnabled=intval($sock->GET_INFO("SpamAssMilterEnabled"));
		$ClamavMilterEnabled=intval($sock->GET_INFO("ClamavMilterEnabled"));
		$MimeDefangClamav=intval($sock->GET_INFO("MimeDefangClamav"));
		$MimeDefangEnabled=intval($sock->GET_INFO("MimeDefangEnabled"));
		$MimeDefangSpamAssassin=intval($sock->GET_INFO("MimeDefangSpamAssassin"));
		$MimeDefangAutoWhiteList=intval($sock->GET_INFO("MimeDefangAutoWhiteList"));
		$MimeDefangFilterExtensions=intval($sock->GET_INFO("MimeDefangFilterExtensions"));
		
		
		if(!$users->MIMEDEFANG_INSTALLED){$MimeDefangEnabled=0;}
		if($MimeDefangEnabled==0){
				$MimeDefangClamav=0;
				$MimeDefangSpamAssassin=0;
				$MimeDefangAutoWhiteList=0;
				$MimeDefangFilterExtensions=0;
		}
		
		$milter_regex_icon="arrow-right-24.png";
		$milter_regex_color="#000000";
		$milter_regex_js="GotoPostfixMilterRegex()";
		
		$milter_spamass_icon="arrow-right-24.png";
		$milter_spamass_color="#000000";
		$milter_spamass_js="GotoMilterSpamass()";
		
		$milter_clamav_icon="arrow-right-24.png";
		$milter_clamav_color="#000000";
		$milter_clamav_js="GotoMilterClamav()";
		
		$mimedefang_icon="arrow-right-24.png";
		$mimedefang_color="#000000";
		$mimedefang_js="GotoMimeDefang()";
		
		$AutoWhite_icon="arrow-right-24.png";
		$AutoWhite_color="#000000";
		$AutoWhite_js="GotoAutoWhite()";
		$AutoWhite_text=null;
		
		$spamassdomains_icon="arrow-right-16.png";
		$spamassdomains_color="#000000";
		$spamassdomains_js="GotoSpamAssRulesDomains()";
		$spamassdomains_text=null;	

		$spamassescrap_icon="arrow-right-16.png";
		$spamassescrap_color="#000000";
		$spamassescrap_js="GotoSpamAssRulesEscrap()";
		$spamassescrap_text=null;
		
		$spamassesSubjects_icon="arrow-right-16.png";
		$spamassesSubjects_color="#000000";
		$spamassesSubjects_js="GotoSpamAssRulesSubjects()";
		$spamassesSubjects_text=null;
		
		
		
		$ExtCheck_icon="arrow-right-24.png";
		$ExtCheck_color="#000000";
		$ExtCheck_js="GotoMimeDefangExtensions()";
		$ExtCheck_text=null;
		
		
		if($MimeDefangAutoWhiteList==0){
			$AutoWhite_icon="arrow-right-24-grey.png";
			$AutoWhite_color="#898989";
			$AutoWhite_js="blur()";
			$AutoWhite_text=" <span style='font-size:14px'>({disabled})</span>";
			
			
		}
		
		if(!is_file("/usr/sbin/milter-regex")){
			$milter_regex_color="#898989";
			$milter_regex_icon="arrow-right-24-grey.png";
			$milter_regex_text=" <span style='font-size:14px'>({not_installed})</span>";
			$milter_regex_js="GotoMimeDefangExtensions()";
		}
		
		if($ClamavMilterEnabled==0){
			$milter_clamav_icon="arrow-right-24-grey.png";
			$milter_clamav_color="#898989";
			$milter_clamav_text=" <span style='font-size:14px'>({disabled})</span>";
			
		}
		
		if(!$users->CLAMAV_MILTER_INSTALLED){
			$milter_clamav_icon="arrow-right-24-grey.png";
			$milter_clamav_color="#898989";
			$milter_clamav_text=" <span style='font-size:14px'>({not_installed})</span>";
			$milter_clamav_js="GotoMessagingSecurityUpdate()";
			
		}
		
		if($MimeDefangClamav==1){
			$milter_clamav_icon="arrow-right-24.png";
			$milter_clamav_color="#000000";
			$milter_clamav_js=$mimedefang_js;
			$milter_clamav_text=" <span style='font-size:14px'>({enabled})</span>";
		}
		
		
		if($SpamAssMilterEnabled==0){
			$milter_spamass_icon="arrow-right-24-grey.png";
			$milter_spamass_color="#898989";
			$milter_spamass_text=" <span style='font-size:14px'>({disabled})</span>";
		}
		
		

		
		if($MimeDefangSpamAssassin==1){
			$milter_spamass_icon="arrow-right-24.png";
			$milter_spamass_color="#000000";
			$milter_spamass_js="GotoMilterSpamass()";
			$milter_spamass_text=null;
			
		}
		
		if($EnableMilterRegex==0){
			$milter_regex_color="#898989";
			$milter_regex_icon="arrow-right-24-grey.png";
			
		}
		
		if($MimeDefangEnabled==0){
			$mimedefang_icon="arrow-right-24-grey.png";
			$mimedefang_color="#898989";
			$mimedefang_text=" <span style='font-size:14px'>({disabled})</span>";
		}
		
		if($MimeDefangSpamAssassin==0){
			$milter_spamass_icon="arrow-right-24-grey.png";
			$milter_spamass_js="blur()";
			$milter_spamass_color="#898989";
			$milter_spamass_text=" <span style='font-size:14px'>({disabled})</span>";
			
			$spamassdomains_icon="arrow-right-16-grey.png";
			$spamassdomains_color="#898989";
			$spamassdomains_js=null;
			$spamassdomains_text=" <span style='font-size:12px'>({disabled})</span>";
			
			$spamassescrap_icon="arrow-right-16-grey.png";
			$spamassescrap_color="#898989";
			$spamassescrap_js=null;
			$spamassescrap_text=" <span style='font-size:12px'>({disabled})</span>";			
			
			$spamassesSubjects_icon="arrow-right-16-grey.png";
			$spamassesSubjects_color="#898989";
			$spamassesSubjects_js=null;
			$spamassesSubjects_text=" <span style='font-size:12px'>({disabled})</span>";			
		}
		
		if($MimeDefangFilterExtensions==0){
			$ExtCheck_icon="arrow-right-24-grey.png";
			$ExtCheck_color="#898989";
			$ExtCheck_js="blur()";
			$ExtCheck_text=" <span style='font-size:14px'>({disabled})</span>";
			
		}
		
		
		if(!$users->MIMEDEFANG_INSTALLED){
			$mimedefang_icon="arrow-right-24-grey.png";
			$mimedefang_color="#898989";
			$mimedefang_js="GotoMessagingSecurityUpdate()";
			$mimedefang_text=" <span style='font-size:14px'>({not_installed})</span>";
			$AutoWhite_text=" <span style='font-size:14px'>({not_installed})</span>";
			$ExtCheck_text=" <span style='font-size:14px'>({not_installed})</span>";
			$spamassdomains_text=" <span style='font-size:12px'>({not_installed})</span>";
			$spamassescrap_text=" <span style='font-size:12px'>({not_installed})</span>";
			$spamassesSubjects_text=" <span style='font-size:12px'>({not_installed})</span>";
		}
		
		$tr2[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$mimedefang_icon'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:$mimedefang_color'>".texttooltip("{APP_VALVUAD}$mimedefang_text","position:right:{APP_VALVUAD_TEXT}",$mimedefang_js)."</td>
		</tr>";
		
		$tr2[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$AutoWhite_icon'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:$AutoWhite_color'>".texttooltip("{smtp_AutoWhiteList}$AutoWhite_text","position:right:{smtp_AutoWhiteList}",$AutoWhite_js)."</td>
		</tr>";		
		
		$tr2[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$milter_regex_icon'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:$milter_regex_color'>".texttooltip("{milter_regex}$milter_regex_text","position:right:{milter_regex_explain}",$milter_regex_js)."</td>
		</tr>";	
		
		
		$tr2[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$ExtCheck_icon'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:$ExtCheck_color'>".texttooltip("{title_mime}$ExtCheck_text","position:right:{mimedefang_attachments_text}",$ExtCheck_js)."</td>
		</tr>";		

		$tr2[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$milter_spamass_icon'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:$milter_spamass_color'>".texttooltip("Anti-Spam$milter_spamass_text","position:right:{APP_SPAMASSASSIN_TEXT}",$milter_spamass_js)."</td>
		</tr>
		<tr>
			<td valign='middle' style='width:25px'>&nbsp;</td>
			<td valign='middle' style='width:100%'>
				<table style='width:100%'>
					<tr>
						<td valign='middle' style='width:25px'><img src='img/$spamassdomains_icon'></td>
						<td valign='middle' style='font-size:16px;width:99%;color:$spamassdomains_color'>".texttooltip("{rules_on_urls}$spamassdomains_text",
								"position:right:{rules_on_urls}",$spamassdomains_js)."</td>
					</tr>
					<tr>
						<td valign='middle' style='width:25px'><img src='img/$spamassescrap_icon'></td>
						<td valign='middle' style='font-size:16px;width:99%;color:$spamassescrap_color'>".texttooltip("{escrap_rules}$spamassescrap_text",
								"position:right:{escrap_rules}",$spamassescrap_js)."</td>
					</tr>
					<tr>
						<td valign='middle' style='width:25px'><img src='img/$spamassesSubjects_icon'></td>
						<td valign='middle' style='font-size:16px;width:99%;color:$spamassesSubjects_color'>".texttooltip("{subject_rules}$spamassesSubjects_text",
								"position:right:{subject_rules}",$spamassesSubjects_js)."</td>
					</tr>																					
				</table>
			</td>
		</tr>
		";		
		
		
	
		
		
		$tr2[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$icon'></td>
		<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{global_smtp_rules}","position:right:{global_smtp_rules}","GotoPostfixBodyChecks()")."</td>
		</tr>";		


		
		
		
		
		

		
		$postscreen_color="#000000";
		$postscreen_icon="arrow-right-24.png";
		$instantIptables_icon="arrow-right-24.png";
		$instantIptables_color="#000000";
		$APP_POLICYD_WEIGHT_icon="arrow-right-24.png";
		$APP_POLICYD_WEIGHT_color="#000000";
		$APP_POLICYD_WEIGHT_js="GotoPolicyDaemon()";
		
		$Valvuad_js="GotoValvuad()";
		$Valvuad_icon="arrow-right-24.png";
		$Valvuad_color="#000000";
		
		$main=new maincf_multi("master","master");
		$EnablePostScreen=intval($main->GET("EnablePostScreen"));
		$EnablePolicydWeight=intval($sock->GET_INFO("EnablePolicydWeight"));
		$EnablePostfixAutoBlock=$sock->GET_INFO("EnablePostfixAutoBlock");
		if(!is_numeric($EnablePostfixAutoBlock)){$EnablePostfixAutoBlock=1;}
		$ValvuladEnabled=intval($sock->GET_INFO("ValvuladEnabled"));
		$ValvuladInstalled=intval($sock->GET_INFO("ValvuladInstalled"));
		
		if($ValvuladInstalled==0){$ValvuladEnabled=0;}
		
		if($EnablePostScreen==0){
			$postscreen_color="#898989";
			$postscreen_icon="arrow-right-24-grey.png";
			$postscreen_text=" <span style='font-size:14px'>({disabled})</span>";
		}
		
		if($EnablePolicydWeight==0){
			$APP_POLICYD_WEIGHT_color="#898989";
			$APP_POLICYD_WEIGHT_text=" <span style='font-size:14px'>({disabled})</span>";
			$APP_POLICYD_WEIGHT_icon="arrow-right-24-grey.png";
		}
		if($EnablePostfixAutoBlock==0){
			$instantIptables_icon="arrow-right-24-grey.png";
			$instantIptables_text=" <span style='font-size:14px'>({disabled})</span>";
			$instantIptables_color="#898989";
		}
		
		if($ValvuladEnabled==0){
			$Valvuad_icon="arrow-right-24-grey.png";
			$Valvuad_text=" <span style='font-size:14px'>({disabled})</span>";
			$Valvuad_color="#898989";
		}
		if($ValvuladInstalled==0){
			$Valvuad_icon="arrow-right-24-grey.png";
			$Valvuad_js="blur()";
			$Valvuad_text=" <span style='font-size:14px'>({not_installed})</span>";
			$Valvuad_color="#898989";
		}		
		
		$tr2[]="
		<tr><td colspan=2>&nbsp;</td></tr>
		<tr>
		<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold;color:black'>{connections_filters}:</td>
		</tr>";
		
		$tr2[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$icon'></td>
		<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{hosts}:{white list}","position:right:{hosts}:{white list}","GotoPostfixWhiteListG()")."</td>
		</tr>";
		
		
		
		$tr2[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/arrow-right-24.png'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{dnsbl_service}",
						"position:right:{DNSBL_EXPLAIN}","GotoPostfixDNSBL()").
						"</td>
		</tr>";
		$tr2[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/arrow-right-24.png'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{RHSBL}",
						"position:right:{RHSBL_EXPLAIN}","GotoPostfixRHSBL()").
						"</td>
		</tr>";		
		
		
		
		$tr2[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$postscreen_icon'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:$postscreen_color'>".texttooltip("PostScreen$postscreen_text","position:right:{POSTSCREEN_TEXT}$postscreen_text","GotoPostScreen()")."</td>
		</tr>";
		

		

		$tr2[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$APP_POLICYD_WEIGHT_icon'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:$APP_POLICYD_WEIGHT_color'>".texttooltip("{APP_POLICYD_WEIGHT}$APP_POLICYD_WEIGHT_text","position:right:{APP_POLICYD_WEIGHT_ICON_TEXT}","$APP_POLICYD_WEIGHT_js")."</td>
		</tr>";		
		
		
		
		
		
		$tr2[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$instantIptables_icon'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:$instantIptables_color'>".
		texttooltip("{postfix_autoblock}","position:right:{postfix_autoblock_text}$instantIptables_text","GotoInstantIpTables()")."</td>
		</tr>";

		 
		
		
		

	}
	
	
	
	
	$tr2[]="</table>";
	
	$final="
	<table style='width:100%'>
	<tr>
		<td style='width:50%' valign='top'>".$tpl->_ENGINE_parse_body(@implode("\n", $tr))."</td>
		<td style='width:50%' valign='top'>".$tpl->_ENGINE_parse_body(@implode("\n", $tr2))."</td>
	</tr>
	</table>
	";
	
	echo $final;
	
}

function update_section(){
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$icon="arrow-right-24.png";
	$clamav_icon=$icon;
	$tr[]="<table style='width:100%'>";
	


	$rules_update_js="GotoMilterGreyListUpdate();";
	$rules_update_color="#000000";
	$rules_update_icon="arrow-right-24.png";
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$rules_update_icon'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:$rules_update_color'>".
	texttooltip("{rules_update}","position:right:{rules_update}",$rules_update_js)."</td>
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
	

	$debug=Paragraphe('syslog-64.png','{POSTFIX_DEBUG}','{POSTFIX_DEBUG_TEXT}',"",90);
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$icon'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{POSTFIX_DEBUG}","position:top:{POSTFIX_DEBUG_TEXT}","javascript:Loadjs('postfix.debug.php?hostname=master&ou=master');")."</td>
	</tr>";
	
	
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$icon'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{RemoteSMTPSyslog}","position:top:{RemoteSMTPSyslogText}","javascript:Loadjs('syslog.smtp-client.php');")."</td>
	</tr>";

	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$icon'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{move_the_spooldir}","position:top:{move_the_spooldir_text}","javascript:Loadjs('postfix.varspool.php?hostname=master&ou=master');")."</td>
	</tr>";	
	

	$EnableStopPostfix=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableStopPostfix"));
	$icon_stop=$icon;
	$stop_color="black";
	$stop_explain=null;
	if($EnableStopPostfix==1){
		$icon_stop="alert-24.png";
		$stop_color="#d32d2d";
		$stop_explain="&nbsp;&laquo;&nbsp;{stopped}&nbsp;&raquo;";
	}
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$icon_stop'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:$stop_color'>".texttooltip("{stop_messaging}$stop_explain",
			"position:top:{stop_messaging_text}","javascript:Loadjs('postfix.stop.php');")."</td>
	</tr>";
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$icon'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{remove_postfix_section}","position:top:{remove_postfix_section_text}","javascript:Loadjs('postfix.remove.php');")."</td>
	</tr>";	
	

	$tr[]="</table>";
		
		
		$html=$tpl->_ENGINE_parse_body(@implode("\n", $tr));
		$monitor_file="/usr/share/artica-postfix/ressources/logs/web/cache/".md5("DEBUGSECTION".$tpl->language.$_SESSION["uid"]);
		@file_put_contents($monitor_file, $html);
		echo $html;
	
	
}
function AsMainOrgAdmin(){
	$users=new usersMenus();
	if($users->AsSystemAdministrator){return true;}
	if($users->AsPostfixAdministrator){return true;}
	
	
	
}