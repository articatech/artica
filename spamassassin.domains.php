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

	if(isset($_POST["use_bayes"])){SpamAssMilterEnabled();exit;}
	if(isset($_GET["config"])){config();exit;}
	if(isset($_GET["services-status"])){services_status();exit;}

tabs();

function tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$array["config"]='{parameters}';
	$array["blwl"]='{white_black_smtp}';
	$font="style='font-size:24px'";

	
	$domMD5=md5($_GET["domain"]);
	$master=urlencode(base64_encode("master"));
	$suffix="&domain={$_GET["domain"]}&ou={$_SESSION["ou"]}";
	while (list ($num, $ligne) = each ($array) ){
		if($num=="blwl"){
			$html[]= $tpl->_ENGINE_parse_body("<li $font><a href=\"spamassassin.domains.bwl.php?yes=yes$suffix\"><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="template"){
			$html[]= $tpl->_ENGINE_parse_body("<li $font><a href=\"spamassassin.template.php\"><span>$ligne</span></a></li>\n");
			continue;
		}		
		
		$html[]= $tpl->_ENGINE_parse_body("<li $font><a href=\"$page?$num=yes$suffix\"><span>$ligne</span></a></li>\n");


	}


	echo build_artica_tabs($html, "main_config_milter_spamass_$domMD5",1490);

}

function config(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	$t=time();
	
	
	$domMD5=md5($_GET["domain"]);
	$domain=$_GET["domain"];
	
	$t=time();
	$spam=new spamassassin();
	
	$required_score_default=$spam->main_array["required_score"];
	
	$SpamAssMilterEnabled=intval($sock->GET_INFO("SpamAssMilterEnabled"));
	$SpamassassinDelegation=intval($sock->GET_INFO("SpamassassinDelegation"));
	$MimeDefangEnabled=intval($sock->GET_INFO("MimeDefangEnabled"));
	$MimeDefangMaxQuartime=intval($sock->GET_INFO("MimeDefangMaxQuartime"));
	if($MimeDefangMaxQuartime==0){$MimeDefangMaxQuartime=129600;}
	if(!$users->MIMEDEFANG_INSTALLED){$MimeDefangEnabled=0;}
	
	$init_subject_string=$spam->GET_DOMAIN($domain,"rewrite_header");
	$required_score=$spam->GET_DOMAIN($domain,"required_score");
	if($required_score==null){$required_score=$required_score_default;}
	$init_subject_string = str_replace("Subject", "", $init_subject_string);
	$init_subject_string = trim($init_subject_string);
	
	$block_with_required_score=trim($sock->GET_INFO("SpamAssBlockWithRequiredScore"));
	if($block_with_required_score==null){$block_with_required_score=15;}
	$block_with_required_score_field=$block_with_required_score;
	
	
	$report_safe="<tr>
			<td style='font-size:22px' class=legend>". texttooltip("{report_safe}","{report_safe_text}").":</strong></td>
			<td valign='top'>" . Field_checkbox_design("report_safe-$t",1,$spam->GET_DOMAIN($domain, "report_safe"))."</td>
		</tr>";
	
	
	$rewrite_header="
		<tr>
			<td style='font-size:22px' class=legend>". texttooltip("{subject_rewrite}","{subject_rewrite_explain_spamass}").":</strong></td>
			<td valign='top' colspan=2>" . Field_text("rewrite_header-$t",$init_subject_string,'width:650px;font-size:22px',null,null,'{subject_rewrite_explain_spamass}')."</td>
		</tr>";
	
	
	if($MimeDefangEnabled==1){
		$required_score_field="{required_score_quarantine}";
		$report_safe=null;
		$rewrite_header=null;
		
		$sqlSpamAssBlockWithRequiredScore=$spam->GET_DOMAIN($domain, "BlockWithRequiredScore");
		if($sqlSpamAssBlockWithRequiredScore==null){$sqlSpamAssBlockWithRequiredScore=$block_with_required_score;}
		
		$block_with_required_score_field=
		Field_text("BlockWithRequiredScore-$t",$sqlSpamAssBlockWithRequiredScore,
				'width:110px;font-size:22px',null,null,'{required_score_text}');
		
	
	}
	
	

	
	$html="
	<div style='font-size:60px;margin-bottom:15px'>Anti-Spam {$_GET["domain"]}</div>	
	<div style='width:98%' class=form>
		<table style='width:100%'>
	
		$report_safe
		<tr>
			<td style='font-size:22px' class=legend>". texttooltip("{use_bayes}","{use_bayes}").":</strong></td>
			<td valign='top'>" . Field_checkbox_design("use_bayes-$t",1,$spam->GET_DOMAIN($domain, "use_bayes"))."</td>
		</tr>			
		<tr>
			<td style='font-size:22px' class=legend>". texttooltip("{auto_learn}","{auto_learn}").":</strong></td>
			<td valign='top'>" . Field_checkbox_design("bayes_auto_learn-$t",1,$spam->GET_DOMAIN($domain,"bayes_auto_learn"))."</td>
		</tr>	
	
		<tr>
			<td style='font-size:22px' class=legend>". texttooltip($required_score_field,"{required_score_text}").":</strong></td>
			<td valign='top' colspan=2>" . Field_text("required_score-$t",$required_score,'width:110px;font-size:22px',null,null,'{required_score_text}')."</td>
		</tr>			
		<tr>
			<td style='font-size:22px' class=legend>". texttooltip("{block_with_required_score}","{block_with_required_score_text}").":</strong></td>
			<td valign='top' style='font-size:22px' colspan=2>$block_with_required_score_field</td>
		</tr>	
		$rewrite_header						
						
		<tr>
			<td colspan=2  align='right'><hr>". button("{apply}", "Save$t()","40px")."</td>
		</tr>
</table>
</div>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	RefreshTab('main_config_milter_spamass_{$domMD5}');
}
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('domain','$domain');
	XHR.appendData('required_score',document.getElementById('required_score-$t').value);
	
	if(document.getElementById('rewrite_header-$t')){
		XHR.appendData('rewrite_header',document.getElementById('rewrite_header-$t').value);
	}
	
	if(document.getElementById('BlockWithRequiredScore-$t')){
		XHR.appendData('BlockWithRequiredScore',document.getElementById('BlockWithRequiredScore-$t').value);
	}
	
	
	if(document.getElementById('report_safe-$t')){
		if(document.getElementById('report_safe-$t').checked){XHR.appendData('report_safe',1);}else{XHR.appendData('report_safe',0);}
	}
	if(document.getElementById('use_bayes-$t').checked){XHR.appendData('use_bayes',1);}else{XHR.appendData('use_bayes',0);}
	if(document.getElementById('bayes_auto_learn-$t').checked){XHR.appendData('bayes_auto_learn',1);}else{XHR.appendData('bayes_auto_learn',0);}
	
	XHR.sendAndLoad('$page', 'POST',xSave$t,true);

}

function Check$t(){
	LoadAjax('SpamAssMilter-status','$page?services-status=yes');
	
}

</script>";
echo $tpl->_ENGINE_parse_body($html);
}

function SpamAssMilterEnabled(){
	$spam=new spamassassin();
	
	$domain=$_POST["domain"];
	$spam->SET_DOMAIN($domain,"required_score", $_POST["required_score"]);
	$spam->SET_DOMAIN($domain,"report_safe", $_POST["report_safe"]);
	$spam->SET_DOMAIN($domain,"use_bayes", $_POST["use_bayes"]);
	$spam->SET_DOMAIN($domain,"bayes_auto_learn", $_POST["bayes_auto_learn"]);
	
	if(isset($_POST["BlockWithRequiredScore"])){
		$spam->SET_DOMAIN($domain,"BlockWithRequiredScore", $_POST["BlockWithRequiredScore"]);
		
	}
	
	
	if(isset($_POST["rewrite_header"])){
		if($_POST["rewrite_header"]<>null){
			$spam->SET_DOMAIN($domain,"rewrite_header", "Subject ".$_POST["rewrite_header"]);
		
		}else{
			$spam->REMOVE_DOMAIN($domain,"rewrite_header");
		}
	}
	
	

	
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