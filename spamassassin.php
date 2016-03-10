<?php
	//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.milter.greylist.inc');
	include_once('ressources/class.artica.graphs.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once('ressources/class.spamassassin.inc');
	
	if(isset($_GET["hostname"])){if(trim($_GET["hostname"])==null){unset($_GET["hostname"]);}}
	
	$user=new usersMenus();
	if(!isset($_GET["hostname"])){
		if(!$user->AsPostfixAdministrator){FATAL_ERROR_SHOW_128("{$_GET["hostname"]}::{ERROR_NO_PRIVS}");die();}
	}else{
		if(!PostFixMultiVerifyRights()){FATAL_ERROR_SHOW_128("{$_GET["hostname"]}::{ERROR_NO_PRIVS}");die();}
		
	}
	if(isset($_POST["SpamAssMilterEnabled"])){SpamAssMilterEnabled();exit;}
	if(isset($_GET["config"])){config();exit;}
	if(isset($_GET["services-status"])){services_status();exit;}

tabs();

function tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$array["config"]='{parameters}';
	$array["template"]='{template}';
	$array["APP_SPF"]='{APP_SPF}';
	$font="style='font-size:24px'";

	$master=urlencode(base64_encode("master"));
	$suffix="&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}";
	while (list ($num, $ligne) = each ($array) ){
		if($num=="APP_SPF"){
			$html[]= $tpl->_ENGINE_parse_body("<li $font><a href=\"spamassassin.spf.php?popup=yes\"><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="template"){
			$html[]= $tpl->_ENGINE_parse_body("<li $font><a href=\"spamassassin.template.php\"><span>$ligne</span></a></li>\n");
			continue;
		}		
		
		$html[]= $tpl->_ENGINE_parse_body("<li $font><a href=\"$page?$num=yes&hostname=master&ou=$master\"><span>$ligne</span></a></li>\n");


	}


	echo build_artica_tabs($html, "main_config_milter_spamass",1490);

}

function config(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	$MimeDefangEnabled=intval($sock->GET_INFO("MimeDefangEnabled"));
	$MimeDefangMaxQuartime=intval($sock->GET_INFO("MimeDefangMaxQuartime"));
	if($MimeDefangMaxQuartime==0){$MimeDefangMaxQuartime=129600;}
	if(!$users->MIMEDEFANG_INSTALLED){$MimeDefangEnabled=0;}
	
	$times[10080]=$tpl->javascript_parse_text("7 {days}");
	$times[14400]=$tpl->javascript_parse_text("10 {days}");
	$times[21600]=$tpl->javascript_parse_text("15 {days}");
	$times[43200]=$tpl->javascript_parse_text("1 {month}");
	$times[129600]=$tpl->javascript_parse_text("3 {months}");
	
	$t=time();
	
	$required_score_field="{required_score}";
	
	$t=time();
	$spam=new spamassassin();
	$SpamAssMilterEnabled=intval($sock->GET_INFO("SpamAssMilterEnabled"));
	$SpamassassinDelegation=intval($sock->GET_INFO("SpamassassinDelegation"));
	$block_with_required_score=trim($sock->GET_INFO("SpamAssBlockWithRequiredScore"));
	if($block_with_required_score==null){$block_with_required_score=15;}
	$EnableSpamassassinWrongMX=intval($sock->GET_INFO("EnableSpamassassinWrongMX"));
	$EnableSpamassassinDnsEval=intval($sock->GET_INFO("EnableSpamassassinDnsEval"));
	$EnableSpamassassinURIDNSBL=intval($sock->GET_INFO("EnableSpamassassinURIDNSBL"));
	$EnableSpamAssassinFreeMail=intval($sock->GET_INFO("EnableSpamAssassinFreeMail"));
	$EnableDecodeShortURLs=intval($sock->GET_INFO("EnableDecodeShortURLs"));
	$enable_dkim_verification=intval($sock->GET_INFO("enable_dkim_verification"));
	$SpamAssassinUrlScore=intval($sock->GET_INFO("SpamAssassinUrlScore"));
	$SpamAssassinScrapScore=intval($sock->GET_INFO("SpamAssassinScrapScore"));
	$SpamAssassinSubjectsScore=intval($sock->GET_INFO("SpamAssassinSubjectsScore"));
	$NotTrustLocalNet=intval($sock->GET_INFO("NotTrustLocalNet"));
	if($SpamAssassinScrapScore==0){$SpamAssassinScrapScore=6;}
	if($SpamAssassinUrlScore==0){$SpamAssassinUrlScore=9;}
	if($SpamAssassinSubjectsScore==0){$SpamAssassinSubjectsScore=3;}
	
	$EnableSPF=intval($sock->GET_INFO("EnableSPF"));
	$enableSpamassassin="<td colspan=2>". Paragraphe_switch_img("{enable_spamasssin}", 
				"{enable_spamasssin_text}","SpamAssMilterEnabled",
				"$SpamAssMilterEnabled",null,1050)."</td>
		</tr>";
	
	
	$report_safe="		<tr>
			<td style='font-size:22px' class=legend>". texttooltip("{report_safe}","{report_safe_text}").":</strong></td>
			<td valign='top'>" . Field_checkbox_design("report_safe-$t",$spam->main_array["report_safe"])."</td>
		</tr>";
	
	if($MimeDefangEnabled==1){
		$required_score_field="{required_score_quarantine}";
		$report_safe=null;
		$quarantine="	 <tr>
	 	<td class=legend style='font-size:22px'>{retention} ({quarantine}):</td>
	 	<td style='font-size:22px'>". Field_array_Hash($times, "MimeDefangMaxQuartime-$t",$MimeDefangMaxQuartime,"style:font-size:22px")."</td>
	 </tr> ";
	}
	
	$html="
	<table style='width:100%'>
	<tr>
		<td valign='top' style='width:350px'>
			<div style='width:98%' class=form>
				<div style='font-size:30px;margin-bottom:20px'>{services_status}</div>
				<div id='SpamAssMilter-status'></div>
				<div style='text-align:right'>". imgtootltip("refresh-32.png","{refresh}",
						"LoadAjax('SpamAssMilter-status','$page?services-status=yes')")."</div>
			</div>
		
		
		</td>
		<td valign='top' style='padding-left:15px'>
	<div style='font-size:60px;margin-bottom:15px'>{APP_SPAMASSASSIN}</div>	
	<hr>	
	<div id='test-$t'></div>
	<p>&nbsp;</p>
	<div style='width:98%' class=form>
		<table style='width:100%'>
		<tr>

		<td colspan=2>". Paragraphe_switch_img("{enable_spamasssin_delegate}", 
				"{enable_spamasssin_delegate_text}","SpamassassinDelegation",
				"$SpamassassinDelegation",null,1050)."</td>
		</tr>
		<tr>
			<td style='font-size:22px' class=legend>". texttooltip("{NotTrustLocalNet}","{NotTrustLocalNet_explain}").":</strong></td>
			<td valign='top'>" . Field_checkbox_design("NotTrustLocalNet-$t",1,$NotTrustLocalNet)."</td>
		</tr>		
		
		
		
			$report_safe					

		<tr>
			<td style='font-size:22px' class=legend>". texttooltip("{use_bayes}","{use_bayes}").":</strong></td>
			<td valign='top'>" . Field_checkbox_design("use_bayes-$t",1,$spam->main_array["use_bayes"])."</td>
		</tr>	

					
		<tr>
			<td style='font-size:22px' class=legend>". texttooltip("{auto_learn}","{auto_learn}").":</strong></td>
			<td valign='top'>" . Field_checkbox_design("bayes_auto_learn-$t",1,$spam->main_array["bayes_auto_learn"])."</td>
		</tr>	
			<tr>
			<td style='font-size:22px' class=legend>". texttooltip("{SpamAssassinUrlScore}","{SpamAssassinUrlScore_text}").":</strong></td>
			<td valign='top' colspan=2>" . Field_text("SpamAssassinUrlScore-$t",$SpamAssassinUrlScore,'width:110px;font-size:22px',null,null)."</td>
		</tr>
		<tr>
			<td style='font-size:22px' class=legend>". texttooltip("{SpamAssassinScrapScore}","{SpamAssassinScrapScore_text}").":</strong></td>
			<td valign='top' colspan=2>" . Field_text("SpamAssassinScrapScore-$t",$SpamAssassinScrapScore,'width:110px;font-size:22px',null,null)."</td>
		</tr>					
		<tr>
			<td style='font-size:22px' class=legend>". texttooltip("{SpamAssassinSubjectsScore}","{SpamAssassinSubjectsScore_text}").":</strong></td>
			<td valign='top' colspan=2>" . Field_text("SpamAssassinSubjectsScore-$t",$SpamAssassinSubjectsScore,'width:110px;font-size:22px',null,null)."</td>
		</tr>
		<tr>
			<td style='font-size:22px' class=legend>". texttooltip($required_score_field,"{required_score_text}").":</strong></td>
			<td valign='top' colspan=2>" . Field_text("required_score-$t",$spam->main_array["required_score"],'width:110px;font-size:22px',null,null,'{required_score_text}')."</td>
		</tr>$quarantine	
				
		<tr>
			<td style='font-size:22px' class=legend>". texttooltip("{block_with_required_score}","{block_with_required_score_text}").":</strong></td>
			<td valign='top' colspan=2>" . Field_text("block_with_required_score-$t",$block_with_required_score,'width:110px;font-size:22px',null,null)."</td>
		</tr>
					
					
					
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{ACTIVATE_SPF}","{ACTIVATE_SPF_TEXT}<br>{APP_SPF_TEXT}").":</td>
		<td>". Field_checkbox_design("EnableSPF",1,$EnableSPF)."</td>
	</tr>												
		<tr>
			<td class=legend style='font-size:22px'>". texttooltip("WrongMX","{WrongMXPlugin}").":</td>
			<td>". Field_checkbox_design("EnableSpamassassinWrongMX",1,$EnableSpamassassinWrongMX)."</td>
		</tr>
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("URIDNSBL","{URIDNSBL_explain}").":</td>
		<td>". Field_checkbox_design("EnableSpamassassinURIDNSBL",1,$EnableSpamassassinURIDNSBL)."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("FreeMail","{EnableSpamAssassinFreeMail_explain}").":</td>
		<td>". Field_checkbox_design("EnableSpamAssassinFreeMail",1,$EnableSpamAssassinFreeMail)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{enable_DecodeShortURLs}","{DecodeShortURLs_explain}").":</td>
		<td>". Field_checkbox_design("EnableDecodeShortURLs",1,$EnableDecodeShortURLs)."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{enable_dkim_verification}","{dkim_about}<br>{dkim_about2}").":</td>
		<td>". Field_checkbox_design("enable_dkim_verification",1,$enable_dkim_verification)."</td>
	</tr>					
		<tr>
			<td colspan=2  align='right'><hr>". button("{apply}", "Save$t()","40px")."</td>
		</tr>
</table>
</div>
</td>
</tr>
</table>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	Loadjs('postfix.milters.progress.php');
	RefreshTab('main_config_milter_spamass');
}
function Save$t(){
	var XHR = new XHRConnection();
	if(document.getElementById('SpamAssMilterEnabled')){
		XHR.appendData('SpamAssMilterEnabled',document.getElementById('SpamAssMilterEnabled').value);
	}else{
		XHR.appendData('SpamAssMilterEnabled',0);
	}
	
	XHR.appendData('SpamassassinDelegation',document.getElementById('SpamassassinDelegation').value);
	
	
	XHR.appendData('block_with_required_score',document.getElementById('block_with_required_score-$t').value);
	XHR.appendData('required_score',document.getElementById('required_score-$t').value);
	XHR.appendData('SpamAssassinUrlScore',document.getElementById('SpamAssassinUrlScore-$t').value);
	XHR.appendData('SpamAssassinSubjectsScore',document.getElementById('SpamAssassinSubjectsScore-$t').value);
	XHR.appendData('SpamAssassinScrapScore',document.getElementById('SpamAssassinScrapScore-$t').value);
	
	if(document.getElementById('report_safe-$t')){
		if(document.getElementById('report_safe-$t').checked){XHR.appendData('report_safe',1);}else{XHR.appendData('report_safe',0);}
	}
	if(document.getElementById('MimeDefangMaxQuartime-$t')){
		XHR.appendData('MimeDefangMaxQuartime',document.getElementById('MimeDefangMaxQuartime-$t').value);
	}	
	if(document.getElementById('EnableSpamassassinWrongMX').checked){XHR.appendData('EnableSpamassassinWrongMX',1);}else{XHR.appendData('EnableSpamassassinWrongMX',0);}
	if(document.getElementById('EnableSpamassassinURIDNSBL').checked){XHR.appendData('EnableSpamassassinURIDNSBL',1);}else{XHR.appendData('EnableSpamassassinURIDNSBL',0);}
	if(document.getElementById('enable_dkim_verification').checked){XHR.appendData('enable_dkim_verification',1);}else{XHR.appendData('enable_dkim_verification',0);}
	if(document.getElementById('EnableDecodeShortURLs').checked){XHR.appendData('EnableDecodeShortURLs',1);}else{XHR.appendData('EnableDecodeShortURLs',0);}
	if(document.getElementById('EnableSpamAssassinFreeMail').checked){XHR.appendData('EnableSpamAssassinFreeMail',1);}else{XHR.appendData('EnableSpamAssassinFreeMail',0);}	
	if(document.getElementById('EnableSPF').checked){XHR.appendData('EnableSPF',1);}else{XHR.appendData('EnableSPF',0);}
	if(document.getElementById('use_bayes-$t').checked){XHR.appendData('use_bayes',1);}else{XHR.appendData('use_bayes',0);}
	if(document.getElementById('NotTrustLocalNet-$t').checked){XHR.appendData('NotTrustLocalNet',1);}else{XHR.appendData('NotTrustLocalNet',0);}
	
	
	if(document.getElementById('bayes_auto_learn-$t').checked){XHR.appendData('bayes_auto_learn',1);}else{XHR.appendData('bayes_auto_learn',0);}
	XHR.sendAndLoad('$page', 'POST',xSave$t,true);

}

function Check$t(){
	LoadAjax('SpamAssMilter-status','$page?services-status=yes');
	
}
Check$t();
</script>";
echo $tpl->_ENGINE_parse_body($html);
}

function SpamAssMilterEnabled(){
	$sock=new sockets();
	
	$SpamAssassinUrlScore=intval($sock->GET_INFO("SpamAssassinUrlScore"));
	
	$sock->SET_INFO("NotTrustLocalNet", $_POST["NotTrustLocalNet"]);
	$sock->SET_INFO("SpamAssassinUrlScore", $_POST["SpamAssassinUrlScore"]);
	$sock->SET_INFO("EnableSpamassassinWrongMX", $_POST["EnableSpamassassinWrongMX"]);
	$sock->SET_INFO("EnableSpamassassinURIDNSBL", $_POST["EnableSpamassassinURIDNSBL"]);
	$sock->SET_INFO("enable_dkim_verification", $_POST["enable_dkim_verification"]);
	$sock->SET_INFO("EnableDecodeShortURLs", $_POST["EnableDecodeShortURLs"]);
	$sock->SET_INFO("EnableSpamAssassinFreeMail", $_POST["EnableSpamAssassinFreeMail"]);
	$sock->SET_INFO("EnableSPF", $_POST["EnableSPF"]);
	
	
	$sock->SET_INFO("SpamAssMilterEnabled", $_POST["SpamAssMilterEnabled"]);
	$sock->SET_INFO("SpamassassinDelegation", $_POST["SpamassassinDelegation"]);
	$sock->SET_INFO("SpamAssBlockWithRequiredScore", $_POST["block_with_required_score"]);
	if(isset($_POST["MimeDefangMaxQuartime"])){
		$sock->SET_INFO("MimeDefangMaxQuartime", $_POST["MimeDefangMaxQuartime"]);
	}
	
	$spam=new spamassassin();
	$spam->block_with_required_score=$_POST["block_with_required_score"];
	
	$spam->SET_MYSQL("required_score", $_POST["required_score"]);
	if(isset($_POST["report_safe"])){$spam->SET_MYSQL("report_safe", $_POST["report_safe"]);}
	$spam->SET_MYSQL("use_bayes", $_POST["use_bayes"]);
	$spam->SET_MYSQL("bayes_auto_learn", $_POST["bayes_auto_learn"]);
	
	
	if($_POST["SpamAssassinUrlScore"]<>$SpamAssassinUrlScore){
		$sock->getFrameWork("milter-spamass.php?urls-database=yes");
	}
	
	$spam->main_array["required_score"]=$_POST["required_score"];
	$spam->required_score=$_POST["required_score"];
	$spam->report_safe=$_POST["report_safe"];
	$spam->use_bayes=$_POST["use_bayes"];
	$spam->bayes_auto_learn=$_POST["bayes_auto_learn"];
	$spam->SaveToLdap();
	
}
function services_status(){

	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$ini->loadString(base64_decode($sock->getFrameWork('milter-spamass.php?status=yes')));
	$APP_RDPPROXY=DAEMON_STATUS_ROUND("SPAMASS_MILTER",$ini,null,0);
	$SPAMASSASSIN=DAEMON_STATUS_ROUND("SPAMASSASSIN",$ini,null,0);


	$tr[]=$APP_RDPPROXY;
	$tr[]=$SPAMASSASSIN;

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body(@implode("<p>&nbsp;</p>", $tr));

}