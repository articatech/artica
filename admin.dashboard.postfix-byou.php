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
	
	if(!isset($_GET["ou"])){
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}<br>No Orgnization set...");
		die();
	}
	
	
	$js_infrasection="LoadAjaxRound('infra-section','$page?infra-section=yes&ou={$_GET["ou"]}');";
	$js_filtersection="LoadAjaxRound('filter2-section','$page?filter2-section=yes&ou={$_GET["ou"]}');";
	$js_controlsection="LoadAjaxRound('control-section','$page?control-section=yes&ou={$_GET["ou"]}');";
	$js_monitorsection="LoadAjaxRound('monitor-section','$page?monitor-section=yes&ou={$_GET["ou"]}');";
	$js_debugsection="LoadAjaxRound('debug-section','$page?debug-section=yes&ou={$_GET["ou"]}');";
	$js_updatesection="LoadAjaxRound('update-section','$page?update-section=yes&ou={$_GET["ou"]}');";
	$EnableStopPostfix=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableStopPostfix"));
	if(!is_dir("/usr/share/artica-postfix/ressources/logs/web/cache")){@mkdir("/usr/share/artica-postfix/ressources/logs/web/cache",0755,true);}
	

	
	if($EnableStopPostfix==1){
		$messaging_stopped_explain="<p class=text-error style='font-size:18px'>{messaging_stopped_explain}</p>";
	}
	
	
	
	$infrasection_file="/usr/share/artica-postfix/ressources/logs/web/cache/".md5("postfix-{$_GET["ou"]}-INFRASECTION".$tpl->language.$_SESSION["uid"]);
	if(is_file($infrasection_file)){
		$infrasection_content=@file_get_contents($infrasection_file);
		if(trim($infrasection_content)<>null){
			$js_infrasection=null;
		}
	}
	
	$filter_file="/usr/share/artica-postfix/ressources/logs/web/cache/".md5("postfix-{$_GET["ou"]}-FILTERSECTION".$tpl->language.$_SESSION["uid"]);
	if(is_file($filter_file)){
		$filter_content=@file_get_contents($filter_file);
		if(trim($filter_content)<>null){
			$js_filtersection=null;
		}
	}	
	
	$control_file="/usr/share/artica-postfix/ressources/logs/web/cache/".md5("postfix-{$_GET["ou"]}-CONTROLSECTION".$tpl->language.$_SESSION["uid"]);
	if(is_file($control_file)){
		$control_content=@file_get_contents($control_file);
		if(trim($control_content)<>null){
			$js_controlsection=null;
		}
	}	
	
	$monitor_file="/usr/share/artica-postfix/ressources/logs/web/cache/".md5("postfix-{$_GET["ou"]}-MONITORSECTION".$tpl->language.$_SESSION["uid"]);
	if(is_file($monitor_file)){
		$monitor_content=@file_get_contents($monitor_file);
		if(trim($monitor_content)<>null){
			$js_monitorsection=null;
		}
	}	
	
	$debug_file="/usr/share/artica-postfix/ressources/logs/web/cache/".md5("postfix-{$_GET["ou"]}-DEBUGSECTION".$tpl->language.$_SESSION["uid"]);
	if(is_file($debug_file)){
		$debug_content=@file_get_contents($debug_file);
		if(trim($debug_content)<>null){
			$js_debugsection=null;
		}
	}
	
	$update_file="/usr/share/artica-postfix/ressources/logs/web/cache/".md5("postfix-{$_GET["ou"]}-UPDATESECTION".$tpl->language.$_SESSION["uid"]);
	if(is_file($update_file)){
		$update_content=@file_get_contents($update_file);
		if(trim($update_content)<>null){
			$js_updatesection=null;
		}
	}	
	
	$html="
	<input type='hidden' id='thisIsThePostfixDashBoard' value='1'>
	<div style='margin-top:30px;margin-bottom:30px;font-size:40px;passing-left:30px;'>{messaging} &laquo;&nbsp;
	{$_GET["ou"]}&nbsp;&raquo;</div>$messaging_stopped_explain
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
	$sock=new sockets();
	$tpl=new templates();	
	$users=new usersMenus();
	$tr[]="<table style='width:100%'>";
	
	
	$smtp_refused_icon="arrow-right-24.png";
	$smtp_refused_color="#000000";
	$smtp_refused_text=null;
	$smtp_refused_js="GotoSMTPRefused()";
	
	$quarantine_icon="arrow-right-24.png";
	$quarantine_color="#000000";
	$quarantine_text=null;
	$quarantine_js="GotoQuarantineMails()";
	$quarantine_text=null;
	
	$MimeDefangEnabled=trim($sock->GET_INFO("MimeDefangEnabled",true));
	$MimeDefangSpamAssassin=intval($sock->GET_INFO("MimeDefangSpamAssassin"));
	if(!$users->MIMEDEFANG_INSTALLED){$MimeDefangEnabled=0;}
	if($MimeDefangEnabled==0){$MimeDefangSpamAssassin=0;}
	
	if($MimeDefangSpamAssassin==0){
		$quarantine_icon="arrow-right-24-grey.png";
		$quarantine_color="#898989";
		$quarantine_text=" <span style='font-size:14px'>({disabled})</span>";
		$quarantine_js="blur()";
		
	}
	
	
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
	<td valign='middle' style='width:25px'>
	<img src='img/$smtp_refused_icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$smtp_refused_color'>".texttooltip("{refusedSMTP}$smtp_refused_text",
			"position:right:{refusedSMTP_explain}","$smtp_refused_js")."</td>
	</tr>";
	
	
	$tr[]="</table>";
	
	$tr2[]="<table style='width:100%'>";
	$tr2[]="
	<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$quarantine_icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$quarantine_color'>".texttooltip("{quarantine}$quarantine_text",
			"position:right:{quarantine}","$quarantine_js")."</td>
	</tr>";	
	
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$attachments_icon'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:$attachments_color'>
	".texttooltip("{attachments_monitor}",
			"position:top:{attachments_monitor}","$attachments_js")."</td>
	</tr>";	
	
	
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


function cache_section(){
	
	$ahref_caches="<a href=\"javascript:blur();\"
			OnClick=\"javascript:GoToCaches();\">";
	
}


function infra_section(){
	$users=new usersMenus();
	$sock=new sockets();
	$tpl=new templates();
	
	$icon="arrow-right-24.png";
	$SSLColor="#000000";
	$icon_ssl="arrow-right-24.png";
	$icon_ssl_enc="arrow-right-24.png";
	$ssl_enc_color="#000000";
	$GotoSSLEncrypt="GotoSSLEncrypt()";
	
	


	$domains="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon_ssl'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{domains}","{domains}","GoToDomainsOU('{$_GET["ou"]}')")."</td>
	</tr>";	
	

	



	
	
	$tr[]="<table style='width:100%'>
	<tr>
	<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold'>{infrastructure}:</td>
	</tr>";
	
	

	
		$tr[]=$domains;

		
		
	$tr[]="<tR><td colspan=2>&nbsp;</td></tr>";

	
	$tr[]="</table>";	
	
	$tr2[]="<table style='width:100%'>";
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
	$MimeDefangEnabled=trim($sock->GET_INFO("MimeDefangEnabled",true));
	$MimeDefangArchiver=intval($sock->GET_INFO("MimeDefangArchiver",true));
	if($MimeDefangEnabled==0){$MimeDefangArchiver=0;}
	

	
	if($MimeDefangArchiver==0){
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
	texttooltip("{backupemail_behavior}","{backupemail_behavior}$backup_text","GotoBackupMails()")."</td>
	</tr>";
	
	
	

	
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
	$users=new usersMenus();
	
	
	$OKQuota=true;
	
	
	$color_quota="black";
	$tr[]="<table style='width:100%'>";
	
	$disclaimer_icon="arrow-right-24.png";
	$disclaimer_color="#000000";
	$disclaimer_js="GotoMimeDefangDisclaimers()";
	$disclaimer_text=null;
	
	if(!$users->MIMEDEFANG_INSTALLED){$MimeDefangEnabled=0;}
	
	if($MimeDefangEnabled==0){$MimeDefangDisclaimer=0;}
	
	
	if($MimeDefangDisclaimer==0){
		$disclaimer_icon="arrow-right-24-grey.png";
		$disclaimer_color="#898989";
		$disclaimer_js="blur()";
		$disclaimer_text=" <span style='font-size:14px'>({disabled})</span>";
			
			
	}
	
	if(!$users->MIMEDEFANG_INSTALLED){
		$disclaimer_text=" <span style='font-size:14px'>({not_installed})</span>";
	
	}
	
	$tr[]="</table>";
	
	$tr2[]="<table style='width:100%'>";
	
	$tr2[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$disclaimer_icon'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:$disclaimer_color'>".texttooltip("{disclaimers}$disclaimer_text","position:right:{disclaimer_explain}",$disclaimer_js)."</td>
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
	$users=new usersMenus();
	$SpamAssMilterEnabled=intval($sock->GET_INFO("SpamAssMilterEnabled"));
	$SpamassassinDelegation=intval($sock->GET_INFO("SpamassassinDelegation"));
	$MimeDefangEnabled=intval($sock->GET_INFO('MimeDefangEnabled'));
	$MimeDefangAutoWhiteList=intval($sock->GET_INFO("MimeDefangAutoWhiteList"));
	$MimeDefangFilterExtensions=intval($sock->GET_INFO("MimeDefangFilterExtensions"));
	
	if($MimeDefangEnabled==1){$SpamAssMilterEnabled=1;}
	
	$TTDOMS=array();
	$OK_SPAMASS=false;
	if($SpamAssMilterEnabled==1){
		if($SpamassassinDelegation==1){
			$OK_SPAMASS=true;
		}
	}

	if(!$users->MIMEDEFANG_INSTALLED){$MimeDefangEnabled=0;}
	if($MimeDefangEnabled==0){
		$MimeDefangClamav=0;
		$MimeDefangSpamAssassin=0;
		$MimeDefangAutoWhiteList=0;
		$MimeDefangFilterExtensions=0;
	}
	
	$AutoWhite_icon="arrow-right-24.png";
	$AutoWhite_color="#000000";
	$AutoWhite_js="GotoAutoWhite()";
	$AutoWhite_text=null;
	
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
	}
	
	
	$ldap=new clladp();
	$domains=$ldap->hash_get_domains_ou($_SESSION["ou"]);
	
	$ouencoded=urlencode($_SESSION["ou"]);
	
	$TTDOMS[]="<table style='width:100%'>
	<tr>
	<td valign='middle' style='width:25px'><img src='img/arrow-right-24.png'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".
		texttooltip("{safety_standards}","position:right:{safety_standards}","GoToRFCDomain('$ouencoded')")."</td>
	</tr></table>";
	
	$TTDOMS[]="
	<table style='width:100%'>
		<tr>
			<td valign='middle' style='width:25px'><img src='img/$AutoWhite_icon'></td>
			<td valign='middle' style='font-size:18px;width:99%;color:$AutoWhite_color'>".texttooltip("{smtp_AutoWhiteList}$AutoWhite_text","position:right:{smtp_AutoWhiteList}",$AutoWhite_js)."</td>
		</tr>
	</table>";	
	
	
	$TTDOMS[]="
	<table style='width:100%'>
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$ExtCheck_icon'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:$ExtCheck_color'>".texttooltip("{title_mime}$ExtCheck_text","position:right:{mimedefang_attachments_text}",$ExtCheck_js)."</td>
		</tr>
	</table>";
	
			
			
	while (list ($num, $ligne) = each ($domains) ){
		
		$icon="arrow-right-24.png";
		$Color="#000000";
		$js="GoToAntiSpamsDomain('$num')";
		$text=null;
		
		if(!$OK_SPAMASS){
			$icon="arrow-right-24-grey.png";
			$js="blur()";
			$text=" <span style='font-size:14px'>({disabled})</span>";
			$Color="#898989";
			
		}
		
		
		$TTDOMS[]="<table style='width:100%'>
		<tr>
	<td valign='middle' style='width:25px'>
		<img src='img/$icon'>
	</td>
		<td valign='middle' style='font-size:18px;width:99%;color:$Color'>".texttooltip("Anti-Spam: $num$text","$num","$js")."</td>
	</tr>
	</table>";	
				

		
	}
	
	
	if(count($TTDOMS)>0){
		$final=CompileTr2($TTDOMS);
		
	}
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($final);

	
}

function update_section(){

}

function debug_section(){

}

