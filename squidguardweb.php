<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.reverse.inc');
	include_once('ressources/class.squidguard-msmtp.inc');
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	$user=new usersMenus();
	if(!$user->AsDansGuardianAdministrator){
		$tpl=new templates();
		echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}").");";
		exit;
		
	}
	
	if(isset($_GET["smtp-parameters-js"])){SMTP_PARAMETERS_JS();exit;}
	if(isset($_GET["smtp-parameters-popup"])){SMTP_PARAMETERS_POPUP();exit;}
	if(isset($_POST["smtp_notifications"])){SMTP_PARAMETERS_SAVE();exit;}
	if(isset($_GET["test-smtp-js"])){tests_smtp_js();exit;}
	if(isset($_GET["smtp_sendto"])){tests_smtp();exit;}
	
	
	if(isset($_POST["UfdbGuardHTTPAllowUnblock"])){UNLOCK_SAVE();exit;}
	if(isset($_GET["EnableSquidGuardHTTPService"])){save();exit;}
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["per-categories"])){per_category_main();exit;}
	if(isset($_GET["per-categories-settings"])){per_category_settings();exit;}
	if(isset($_POST["external_uri"])){per_category_settings_save();exit;}
	if(isset($_GET["skin"])){skin();exit;}
	if(isset($_POST["CATEGORY"])){SAVE_SKIN();exit;}
	if(isset($_GET["unlock"])){UNLOCK_SECTION();exit;}
	if(isset($_GET["skin-tabs"])){skin_tabs();exit;}
	if(isset($_GET["skin-logo"])){skin_logo();exit;}
js();	
	
function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{banned_page_webservice}");
	header("content-type: application/x-javascript");
	$html="
		YahooWin5('1071','$page?tabs=yes','$title');
	";
	echo $html;
		
		
}

function SMTP_PARAMETERS_JS(){
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{smtp_parameters}");
	header("content-type: application/x-javascript");
	$html="
	YahooWin4('990','$page?smtp-parameters-popup=yes','$title');
	";
	echo $html;
	
}

function tests_smtp_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{give_your_email_address}");
	header("content-type: application/x-javascript");
	
	
	
	$t=time();
	echo "
var xStart$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
}		
	
	
function Start$t(){
	var email=prompt('$title');
	if(!email){return;}
	YahooWin6('1024','$page?smtp_sendto='+email,email);
	
	
}
Start$t();";
}


function per_category_main(){
	$tpl=new templates();
	$dans=new dansguardian_rules();
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){
		$error="<p class=text-error>{MOD_TEMPLATE_ERROR_LICENSE}</p>";
	}
	$cats=$dans->LoadBlackListes();
	$page=CurrentPageName();
	$tpl=new templates();
	while (list ($num, $ligne) = each ($cats) ){$newcat[$num]=$num;}
	$t=time();
	$newcat[null]="{select}";
	$html="
	$error
	<div style='font-size:14px' class=explain>{ufdbguard_banned_perso_text}</div>
		<table style='width:99%' class=form>
	<tr>
		<td class=legend>{category}:</td>
		<td>". Field_array_Hash($newcat,$t,null,"catgorized_choosen()","style:font-size:16px")."</td>
	</tR>
	</table>
	<div id='free-category-form'></div>
	
	<script>
	function catgorized_choosen(){
		LoadAjaxTiny('free-category-form','$page?per-categories-settings='+escape(document.getElementById('$t').value));
	}
	
	catgorized_choosen();
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function per_category_settings_save(){
	$sock=new sockets();	
	$hash=unserialize(base64_decode($sock->GET_INFO("UfdbGuardRedirectCategories")));	
	$hash[$_POST["category"]]=$_POST;
	$newhash=base64_encode(serialize($hash));
	$sock->SaveConfigFile($newhash, "UfdbGuardRedirectCategories");
	$dans=new dansguardian_rules();
	$dans->RestartFilters();	
	
}

function per_category_settings(){
	$dans=new dansguardian_rules();
	$cats=$dans->LoadBlackListes();
	$category=$_GET["per-categories-settings"];
	if(trim($category)==null){die();}
	$explain=$cats[$category];
	$page=CurrentPageName();
	$sock=new sockets();	
	$hash=unserialize(base64_decode($sock->GET_INFO("UfdbGuardRedirectCategories")));
	$datas=$hash[$category];
	$tpl=new templates();
	$block=0;
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){
		$block=1;
		$MOD_TEMPLATE_ERROR_LICENSE=$tpl->javascript_parse_text("{MOD_TEMPLATE_ERROR_LICENSE}");
	}
	
	$t=time();
	$html="<div class=explain style='font-size:18px'>$explain</div>
	<div style='width:98%' class=form>
	<table>
	<tr>
		<td class=legend style='font-size:14px'>{enable}:</td>
		<td>". Field_checkbox("enable-$t",1,$datas["enable"],"enable_uri_check()")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{external_uri}:</td>
		<td>". Field_checkbox("external_uri",1,$datas["external_uri"],"external_uri_check()")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{redirect_url}:</td>
		<td>". Field_text("redirect_url",$datas["redirect_url"],"font-size:14px;width:99%")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{blank_page}:</td>
		<td>". Field_checkbox("blank_page",1,$datas["blank_page"],"blank_page_check()")."</td>
	</tr>
	
	<tr>
		<td colspan=2 align='center' style='font-size:16px'>{template}</td>
	</tr>
	<tr>
		<td colspan=2 align='center' style='font-size:16px'>
			<textarea style='width:100%;height:120px;overflow:auto;font-size:12px' id='template_data'>{$datas["template_data"]}</textarea></td>
	</tr>	
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}", "SavePerCatForm()",18)."</td>
	</tr>
	</tbody>
	</table>	
	</div>
	<script>
		var x_SavePerCatForm= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			catgorized_choosen();
		}		
	
	
		function SavePerCatForm(){
			var block=$block;
			if(block==1){
				alert('$MOD_TEMPLATE_ERROR_LICENSE');
				return;
			}
	      	var XHR = new XHRConnection();
	     	if(document.getElementById('external_uri').checked){XHR.appendData('external_uri',1);}else{XHR.appendData('external_uri',0);}
	    	if(document.getElementById('blank_page').checked){XHR.appendData('blank_page',1);}else{XHR.appendData('blank_page',0);}
	    	if(document.getElementById('enable-$t').checked){XHR.appendData('enable',1);}else{XHR.appendData('enable',0);}
	    	XHR.appendData('redirect_url',document.getElementById('redirect_url').value);
	    	XHR.appendData('template_data',document.getElementById('template_data').value);
	 		XHR.appendData('category','$category');
	 		
	     	AnimateDiv('free-category-form');
	     	XHR.sendAndLoad('$page', 'POST',x_SavePerCatForm);     	
		}
	
	
		function external_uri_check(){
			if(!document.getElementById('enable-$t').checked){return;}
			document.getElementById('redirect_url').disabled=true;
			document.getElementById('blank_page').disabled=true;
			document.getElementById('template_data').disabled=true;
			
			if(document.getElementById('external_uri').checked){
				document.getElementById('redirect_url').disabled=false;
			}else{
				document.getElementById('blank_page').disabled=false;
				document.getElementById('template_data').disabled=false;		
			}
			
			blank_page_check();
		
		}
		
		function blank_page_check(){
			if(!document.getElementById('enable-$t').checked){return;}
			if(document.getElementById('external_uri').checked){return;}
			document.getElementById('template_data').disabled=true;
			
			if(document.getElementById('blank_page').checked){
				document.getElementById('template_data').disabled=true;
			}else{
				document.getElementById('template_data').disabled=false;		
			}
		
		}

		function enable_uri_check(){
			document.getElementById('redirect_url').disabled=true;
			document.getElementById('blank_page').disabled=true;
			document.getElementById('template_data').disabled=true;
		
			document.getElementById('external_uri').disabled=true;
			if(document.getElementById('enable-$t').checked){
				document.getElementById('external_uri').disabled=false;
			}
			external_uri_check();
		
		}
		enable_uri_check();
		
	</script>
	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}


function tabs(){
	$tpl=new templates();
	$sock=new sockets();
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	if($SquidPerformance>2){
		echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{software_is_disabled_performance}"));
		return;
	
	}
	
	$array["popup"]='{banned_page_webservice}';
	$array["unlock"]='{unlock}';
	$array["rules"]='{unlock_rules}';
	$array["skins"]='{skins_rules}';
	$array["remote"]='{remote_webpage}';
	//$array["per-categories"]='{per_category}';
	
	$page=CurrentPageName();
	$tpl=new templates();

	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="remote"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squidguardweb.remotepage.php\">
					<span style='font-size:24px'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="service"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squidguardweb.service.php\">
					<span style='font-size:24px'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="rules"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squidguardweb.rules.php\">
					<span style='font-size:24px'>$ligne</span></a></li>\n");
			continue;
		}		
		if($num=="skins"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squidguardweb.skins.php\">
					<span style='font-size:24px'>$ligne</span></a></li>\n");
			continue;
		}
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\">
				<span style='font-size:24px'>$ligne</span></a></li>\n");
	}
	
	
	
	echo build_artica_tabs($html, "main_squidguardweb_error_pages");
	
	
}

function skin_tabs(){
	
	
	
}



function skin_tabs_2(){
	$tpl=new templates();
	$sock=new sockets();
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	if($SquidPerformance>2){
		echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{software_is_disabled_performance}"));
		return;
	
	}
	

	$array["skin"]='{skin}';
	$array["skin-logo"]='{logo}';
	//$array["per-categories"]='{per_category}';
	
	$page=CurrentPageName();
	$tpl=new templates();
	
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
	
		if($num=="skin-logo"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.templates.skin.php?skin-logo=yes\"><span style='font-size:18px'>$ligne</span></a></li>\n");
			continue;
		}
	
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span style='font-size:18px'>$ligne</span></a></li>\n");
	}
	
	
	
	echo build_artica_tabs($html, "main_squidguardweb_skin_tabs");	
	
}




function popup(){
	$page=CurrentPageName();
	$sock=new sockets();
	$users=new usersMenus();
	$t=time();
	$LICENSE=0;
	if($users->CORP_LICENSE){$LICENSE=1;}
	$EnableSquidGuardHTTPService=$sock->GET_INFO("EnableSquidGuardHTTPService");
	if(strlen(trim($EnableSquidGuardHTTPService))==0){$EnableSquidGuardHTTPService=1;}
	$EnableUfdbGuard=intval($sock->GET_INFO("EnableUfdbGuard"));
	
	$SquidGuardApacheSSLPort=$sock->GET_INFO("SquidGuardApacheSSLPort");
	$SquidGuardApachePort=intval($sock->GET_INFO("SquidGuardApachePort"));
	$SquidGuardWebBlankReferer=intval($sock->GET_INFO("SquidGuardWebBlankReferer"));
	$SquidGuardWebSSLCertificate=$sock->GET_INFO("SquidGuardWebSSLCertificate");
	
	$SquidGuardRedirectBehavior=$sock->GET_INFO("SquidGuardRedirectBehavior");
	$SquidGuardRedirectSSLBehavior=$sock->GET_INFO("SquidGuardRedirectSSLBehavior");
	$SquidGuardRedirectHTTPCode=intval($sock->GET_INFO("SquidGuardRedirectHTTPCode"));
	
	if($SquidGuardRedirectBehavior==null){$SquidGuardRedirectBehavior="url";}
	if($SquidGuardRedirectSSLBehavior==null){$SquidGuardRedirectSSLBehavior="url";}
	if(!is_numeric($SquidGuardRedirectHTTPCode)){$SquidGuardRedirectHTTPCode=302;}
	
	$HTTP_CODE[301]="{Moved_Permanently} (301)";
	$HTTP_CODE[302]="{Moved_Temporarily} (302)";
	$HTTP_CODE[303]="{http_code_see_other} (303)";
	$HTTP_CODE[307]="{Moved_Temporarily} (307)";
	
	if(!is_numeric($SquidGuardApacheSSLPort)){$SquidGuardApacheSSLPort=9025;}
	if($SquidGuardApachePort==0){$SquidGuardApachePort=9020;}
	$ALERT=null;
	$SquidGuardIPWeb=$sock->GET_INFO("SquidGuardIPWeb");
	$fulluri=$sock->GET_INFO("SquidGuardIPWeb");
	$SquidGuardWebFollowExtensions=$sock->GET_INFO("SquidGuardWebFollowExtensions");
	$SquidGuardWebUseExternalUri=intval($sock->GET_INFO("SquidGuardWebUseExternalUri"));
	$DisableSquidGuardHTTPCache=intval($sock->GET_INFO("DisableSquidGuardHTTPCache"));
	$EnableSquidGuardHTTPCache=1;
	if($DisableSquidGuardHTTPCache==1){$EnableSquidGuardHTTPCache=0;}
	$SquidGuardDenySSL=intval($sock->GET_INFO("SquidGuardDenySSL"));
	if($SquidGuardDenySSL==0){$SquidGuardSSL=1;}else{$SquidGuardSSL=0;}
	$SquidGuardServerName=$sock->GET_INFO("SquidGuardServerName");
	$EnableSquidGuardMicrosoftTPL=intval($sock->GET_INFO("EnableSquidGuardMicrosoftTPL"));
	$EnableSquidGuardSearchCategoryNone=intval($sock->GET_INFO("EnableSquidGuardSearchCategoryNone"));
	$SquidGuardServerName=$sock->GET_INFO("SquidGuardServerName");
	
	
	if($SquidGuardIPWeb==null){
			$SquidGuardIPWeb="http://".$_SERVER['SERVER_ADDR'].':'.$SquidGuardApachePort."/ufdbguardd.php";
			$SquidGuardIPWebSSL="https://".$_SERVER['SERVER_ADDR'].':'.$SquidGuardApacheSSLPort."/ufdbguardd.php";
			$fulluri="http://".$_SERVER['SERVER_ADDR'].':'.$SquidGuardApachePort."/ufdbguardd.php";
			$fulluriSSL="https://".$_SERVER['SERVER_ADDR'].':'.$SquidGuardApacheSSLPort."/ufdbguardd.php";
	}	
	$SquidGuardIPWeb=str_replace("http://",null,$SquidGuardIPWeb);
	$SquidGuardIPWeb=str_replace("https://",null,$SquidGuardIPWeb);
	$fulluriSSL=$SquidGuardIPWeb;
	
	if(preg_match("#\/(.+?):([0-9]+)\/#",$SquidGuardIPWeb,$re)){$SquidGuardIPWeb="{$re[1]}:{$re[2]}";}
	
	if(preg_match("#(.+?):([0-9]+)#",$SquidGuardIPWeb,$re)){
		$SquidGuardServerName=$re[1];
		$SquidGuardApachePort=$re[2];
	}	

	if(!is_numeric($SquidGuardWebFollowExtensions)){$SquidGuardWebFollowExtensions=1;}
	$fulluriSSL=str_replace($SquidGuardApachePort, $SquidGuardApacheSSLPort, $fulluriSSL);
	
	$squid_reverse=new squid_reverse();
	$sslcertificates=$squid_reverse->ssl_certificates_list();
	$button= button("{apply}","SaveSquidGuardHTTPService$t()",40);
	$p1=Paragraphe_switch_img("{enable_http_service}", 
				"{enable_http_service_squidguard}","EnableSquidGuardHTTPService",
			$EnableSquidGuardHTTPService,null,1024,"EnableSquidGuardHTTPService()");
	if($SquidGuardWebUseExternalUri==1){
		
		
		
		$p1=Paragraphe_switch_disable("{enable_http_service}",
				"<strong style='color:#d32d2d'>{service_disabled_because_external_webpage}<br></strong>{enable_http_service_squidguard}","EnableSquidGuardHTTPService",
				$EnableSquidGuardHTTPService,null,1024,"EnableSquidGuardHTTPService()");
		$button=null;
	}
	
	if($EnableUfdbGuard==0){
		$p1=Paragraphe_switch_disable("{enable_http_service}",
				"<strong style='color:#d32d2d'>{webfiltering_is_disabled}</strong><br>{enable_http_service_squidguard}",
				"EnableSquidGuardHTTPService",$EnableSquidGuardHTTPService,null,1024,"EnableSquidGuardHTTPService()");
		
	}
	
	$p4=Paragraphe_switch_img("{EnableSquidGuardSearchCategoryNone}",
			"{EnableSquidGuardSearchCategoryNone_explain}","EnableSquidGuardSearchCategoryNone",
			$EnableSquidGuardSearchCategoryNone,null,1024,"");
	
	
	
	
	$p2=Paragraphe_switch_img("{webfiltering_cache_page}",
			"{webfiltering_cache_page_explain}","EnableSquidGuardHTTPCache",
			$EnableSquidGuardHTTPCache,null,1024,"");
	
	
	$p3=Paragraphe_switch_img("{SquidTemplatesMicrosoft}",
	"{SquidTemplatesMicrosoft_explain}",
	"EnableSquidGuardMicrosoftTPL",$EnableSquidGuardMicrosoftTPL,null,1024);
	
	$redirect_behaviorA["url"]="{redirect_connexion}";
	$redirect_behaviorA["rewrite-url"]="{rewrite_url}";
	
	$html="
	<div id='EnableSquidGuardHTTPServiceDiv'>
	<div style='width:98%' class=form>
	
	<div class=explain style='font-size:20px'>{ufdbguard_redirect_ssl_explain}</div>
	
	<table style='width:100%'>
	<tr>
			
		<td style='vertical-align:top'><div id='squid-guard-http-status' style='margin-right:10px'></div></td>
		<td colspan=2>$p1".$p2.$p3.$p4.
				Paragraphe_switch_img("{FollowExtensions}",
				"{SquidGuardWebFollowExtensions_explain}","SquidGuardWebFollowExtensions",
						$SquidGuardWebFollowExtensions,null,1024,"EnableSquidGuardHTTPService()").
				
				Paragraphe_switch_img("{SquidGuardWebBlankReferer}",
				"{SquidGuardWebBlankReferer_explain}","SquidGuardWebBlankReferer",
						$SquidGuardWebBlankReferer,null,1024,"EnableSquidGuardHTTPService()").
						
					Paragraphe_switch_img("{redirect_ssl}",
						"{ufdb_redirect_ssl_explain}","SquidGuardSSL",
						$SquidGuardSSL,null,1024).
			"</td>	
							

	<tr>
		<td class=legend style='font-size:24px'>{listen_port}:</td>
		<td>". Field_text("listen_port_squidguard",$SquidGuardApachePort,"font-size:24px;padding:3px;width:110px",null,null,null,false,"")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:24px'>{listen_port} (SSL):</td>
		<td>". Field_text("listen_port_squidguard_ssl",$SquidGuardApacheSSLPort,"font-size:24px;padding:3px;width:110px",null,null,null,false,"")."</td>
		<td>&nbsp;</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:24px'>{certificate}:</td>
		<td>". Field_array_Hash($sslcertificates,"SquidGuardWebSSLCertificate",$SquidGuardWebSSLCertificate,
				"style:font-size:24px;padding:3px;width:75%",null,null,null,false,"")."</td>
		<td>&nbsp;</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:24px'>{redirect_behavior}:</td>
		<td>". Field_array_Hash($redirect_behaviorA,"SquidGuardRedirectBehavior",$SquidGuardRedirectBehavior,
				"style:font-size:24px;padding:3px;width:75%",null,null,null,false,"")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:24px'>{redirect_behavior} (ssl):</td>
		<td>". Field_array_Hash($redirect_behaviorA,"SquidGuardRedirectSSLBehavior",$SquidGuardRedirectSSLBehavior,
				"style:font-size:24px;padding:3px;width:75%",null,null,null,false,"")."</td>
		<td>&nbsp;</td>
	</tr>
						
	<tr>
		<td class=legend style='font-size:24px'>{redirect_code}:</td>
		<td>". Field_array_Hash($HTTP_CODE,"SquidGuardRedirectHTTPCode",$SquidGuardRedirectHTTPCode,
				"style:font-size:24px;padding:3px;width:75%",null,null,null,false,"")."</td>
		<td>&nbsp;</td>
	</tr>					
					
	
	<tr>
		<td class=legend style='font-size:24px'>{hostname}:</td>
		<td style='font-size:14px'>". Field_text("servername_squidguard",$SquidGuardServerName,
				"font-size:24px;padding:3px;width:380px",null,null,null,false,"")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:24px'>{fulluri}:</td>
		<td style='font-size:24px'>". Field_text("fulluri","$fulluri","font-size:22px;padding:3px;width:660px",null,null,null,false,"")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:24px'>{fulluri} (ssl):</td>
		<td style='font-size:24px'>". Field_text("fulluriSSL","$fulluriSSL","font-size:22px;padding:3px;width:660px",null,null,null,false,"")."</td>
		<td>&nbsp;</td>
	</tr>				
	<tr>
		<td colspan=3 align='right'><hr>".$button."</td>
	</tr>	
	</table>
	</div>
<script>
function EnableSquidGuardHTTPService(){
	var LICENSE=$LICENSE;
	 document.getElementById('listen_port_squidguard').disabled=true;
	 document.getElementById('listen_port_squidguard_ssl').disabled=true;
	 document.getElementById('servername_squidguard').disabled=true;
	 document.getElementById('fulluri').disabled=true;
	 document.getElementById('fulluriSSL').disabled=true;
	 document.getElementById('SquidGuardWebFollowExtensions').disabled=true;
	 document.getElementById('SquidGuardWebSSLCertificate').disabled=true;
	 document.getElementById('EnableSquidGuardHTTPCache').disabled=true;
	 document.getElementById('SquidGuardRedirectBehavior').disabled=true;
	 document.getElementById('SquidGuardRedirectHTTPCode').disabled=true;
	 document.getElementById('SquidGuardRedirectSSLBehavior').disabled=true;
			 
	if(document.getElementById('EnableSquidGuardHTTPService').value==1){
		document.getElementById('listen_port_squidguard').disabled=false;
		document.getElementById('listen_port_squidguard_ssl').disabled=false;
		document.getElementById('servername_squidguard').disabled=false;
		document.getElementById('SquidGuardWebFollowExtensions').disabled=false;
		document.getElementById('SquidGuardWebBlankReferer').disabled=false;
		document.getElementById('SquidGuardWebSSLCertificate').disabled=false;
		document.getElementById('EnableSquidGuardHTTPCache').disabled=false;
		document.getElementById('SquidGuardRedirectBehavior').disabled=false;
	 	document.getElementById('SquidGuardRedirectHTTPCode').disabled=false;
	 	document.getElementById('SquidGuardRedirectSSLBehavior').disabled=false;
	}
			 
			
}
		
var x_SaveSquidGuardHTTPService$t=function(obj){
	 	Loadjs('dansguardian2.compile.php');
}

function SaveSquidGuardHTTPService$t(){
     var XHR = new XHRConnection();
     
     XHR.appendData('EnableSquidGuardMicrosoftTPL',document.getElementById('EnableSquidGuardMicrosoftTPL').value);
  	 XHR.appendData('SquidGuardWebBlankReferer',document.getElementById('SquidGuardWebBlankReferer').value);
     XHR.appendData('SquidGuardWebFollowExtensions',document.getElementById('SquidGuardWebFollowExtensions').value);
     XHR.appendData('EnableSquidGuardHTTPService',document.getElementById('EnableSquidGuardHTTPService').value);
	 XHR.appendData('listen_port_squidguard',document.getElementById('listen_port_squidguard').value);
	 XHR.appendData('listen_port_squidguard_ssl',document.getElementById('listen_port_squidguard_ssl').value);
	 XHR.appendData('SquidGuardWebSSLCertificate',document.getElementById('SquidGuardWebSSLCertificate').value);
	 XHR.appendData('servername_squidguard',document.getElementById('servername_squidguard').value);
     XHR.appendData('fulluri',document.getElementById('fulluri').value);
     XHR.appendData('fulluriSSL',document.getElementById('fulluriSSL').value);
     XHR.appendData('EnableSquidGuardHTTPCache',document.getElementById('EnableSquidGuardHTTPCache').value);
     XHR.appendData('SquidGuardRedirectBehavior',document.getElementById('SquidGuardRedirectBehavior').value);
     XHR.appendData('SquidGuardRedirectHTTPCode',document.getElementById('SquidGuardRedirectHTTPCode').value);
     XHR.appendData('SquidGuardRedirectSSLBehavior',document.getElementById('SquidGuardRedirectSSLBehavior').value);
     XHR.appendData('EnableSquidGuardSearchCategoryNone',document.getElementById('EnableSquidGuardSearchCategoryNone').value);
     
     
     
     XHR.appendData('SquidGuardSSL',document.getElementById('SquidGuardSSL').value);
     
     
     XHR.sendAndLoad('$page', 'GET',x_SaveSquidGuardHTTPService$t);     	
}
	
EnableSquidGuardHTTPService();
LoadAjaxSilent('squid-guard-http-status','squidguardweb.service.php');
</script>";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function save(){
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	
	if(strpos($_GET["servername_squidguard"], "/")>0){
		$uri=parse_url($_GET["servername_squidguard"]);
		if(!isset($uri["host"])){
			echo "Unable to complete invalid value {$_GET["servername_squidguard"]}";
			return;
		}
		$_GET["servername_squidguard"]=$uri["host"];
	}
	
	$sock=new sockets();
	if($_GET["EnableSquidGuardHTTPService"]==0){
		$SquidGuardIPWeb=$_GET["fulluri"];
		$SquidGuardIPWebSSL=$_GET["fulluriSSL"];
	}else{
		$SquidGuardIPWeb="http://".$_GET["servername_squidguard"].":".$_GET["listen_port_squidguard"]."/ufdbguardd.php";
		$SquidGuardIPWebSSL="https://".$_GET["servername_squidguard"].":".$_GET["listen_port_squidguard_ssl"]."/ufdbguardd.php";
	}
	
	
	if($_POST["EnableSquidGuardHTTPCache"]==1){
		$sock->SET_INFO("DisableSquidGuardHTTPCache",0);
	}else{
		$sock->SET_INFO("DisableSquidGuardHTTPCache",1);
	}
	
	if($_GET["SquidGuardSSL"]==0){
		$sock->SET_INFO("SquidGuardDenySSL",1);
	}else{
		$sock->SET_INFO("SquidGuardDenySSL",0);
	}
	
	$sock->SET_INFO("SquidGuardServerName",$_GET["servername_squidguard"]);
	$sock->SET_INFO("SquidGuardWebSSLCertificate",$_GET["SquidGuardWebSSLCertificate"]);
	$sock->SET_INFO("SquidGuardWebFollowExtensions",$_GET["SquidGuardWebFollowExtensions"]);
	$sock->SET_INFO("SquidGuardApachePort",$_GET["listen_port_squidguard"]);
	$sock->SET_INFO("SquidGuardApacheSSLPort",$_GET["listen_port_squidguard_ssl"]);
	$sock->SET_INFO("EnableSquidGuardHTTPService",$_GET["EnableSquidGuardHTTPService"]);
	$sock->SET_INFO("SquidGuardWebBlankReferer",$_GET["SquidGuardWebBlankReferer"]);
	$sock->SET_INFO("SquidGuardRedirectHTTPCode",$_GET["SquidGuardRedirectHTTPCode"]);
	$sock->SET_INFO("SquidGuardRedirectBehavior",$_GET["SquidGuardRedirectBehavior"]);
	$sock->SET_INFO("SquidGuardRedirectSSLBehavior",$_GET["SquidGuardRedirectSSLBehavior"]);
	$sock->SET_INFO("SquidGuardIPWeb",$SquidGuardIPWeb);
	$sock->SET_INFO("SquidGuardIPWebSSL",$SquidGuardIPWebSSL);
	$sock->SET_INFO("EnableSquidGuardMicrosoftTPL", $_GET["EnableSquidGuardMicrosoftTPL"]);
	$sock->SET_INFO("EnableSquidGuardSearchCategoryNone", $_GET["EnableSquidGuardSearchCategoryNone"]);
	$sock->SET_INFO("EnableSquidGuardHTTPCache", $_GET["EnableSquidGuardHTTPCache"]);
	$sock->getFrameWork("cmd.php?reload-squidguardWEB=yes");
	$sock->getFrameWork("squid.php?weberror-cache-remove=yes");
	$sock->getFrameWork("ufdbguard.php?remove-sessions-caches=yes");
	
	
	
}



function skin(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	$error=null;
	$UFDBGUARD_TITLE_1=null;
	$UFDBGUARD_TITLE_2=null;
	$UFDBGUARD_PARA1=null;
	$UFDBGUARD_PARA2=null;
	$UfdbGuardHTTPNoVersion=0;
	$UfdbGuardHTTPBackgroundColor="#8c1919";
	$UfdbGuardHTTPFamily="Calibri, Candara, Segoe, \"Segoe UI\", Optima, Arial, sans-serif";
	$UfdbGuardHTTPFontColor="#FFFFFF";
	
	$UfdbGuardHTTPEnablePostmaster=1;
	$UfdbGuardHTTPNoVersion=intval($sock->GET_INFO("UfdbGuardHTTPNoVersion"));
	$UfdbGuardHTTPBackgroundColor=$sock->GET_INFO("UfdbGuardHTTPBackgroundColor");
	$UfdbGuardHTTPBackgroundColorBLK=$sock->GET_INFO("UfdbGuardHTTPBackgroundColorBLK");
	$UfdbGuardHTTPBackgroundColorBLKBT=$sock->GET_INFO("UfdbGuardHTTPBackgroundColorBLKBT");
	$UfdbGuardHTTPDisableHostname=intval($sock->GET_INFO("UfdbGuardHTTPDisableHostname"));
	
	
	#0300AC
	
	$UFDBGUARD_TITLE_1=$sock->GET_INFO("UFDBGUARD_TITLE_1");
	$UFDBGUARD_PARA1=$sock->GET_INFO("UFDBGUARD_PARA1");
	$UFDBGUARD_TITLE_2=$sock->GET_INFO("UFDBGUARD_TITLE_2");
	$UFDBGUARD_PARA2=$sock->GET_INFO("UFDBGUARD_PARA2");
	if($UFDBGUARD_TITLE_1==null){$UFDBGUARD_TITLE_1="{UFDBGUARD_TITLE_1}";}
	if($UFDBGUARD_PARA1==null){$UFDBGUARD_PARA1="{UFDBGUARD_PARA1}";}
	if($UFDBGUARD_PARA2==null){$UFDBGUARD_PARA2="{UFDBGUARD_PARA2}";}
	if($UFDBGUARD_TITLE_2==null){$UFDBGUARD_TITLE_2="{UFDBGUARD_TITLE_2}";}
	$UfdbGuardHTTPEnablePostmaster=$sock->GET_INFO("UfdbGuardHTTPEnablePostmaster");
	
	if(!is_numeric($UfdbGuardHTTPEnablePostmaster)){$UfdbGuardHTTPEnablePostmaster=1;}
	if($UfdbGuardHTTPBackgroundColor==null){$UfdbGuardHTTPBackgroundColor="#8c1919";}
	if($UfdbGuardHTTPBackgroundColorBLK==null){$UfdbGuardHTTPBackgroundColorBLK="#0300AC";}
	if($UfdbGuardHTTPBackgroundColorBLKBT==null){$UfdbGuardHTTPBackgroundColorBLKBT="#625FFD";}
	
	
	
	if($UfdbGuardHTTPFamily==null){$UfdbGuardHTTPFamily="Calibri, Candara, Segoe, \"Segoe UI\", Optima, Arial, sans-serif";}
	
	$t=time();
	$button="<hr>".button("{apply}", "Save$t()",32);
	
	if(!$users->CORP_LICENSE){
		$error="<p class=text-error>{MOD_TEMPLATE_ERROR_LICENSE}</p>";
		$button=null;
	}
	
	$html="$error<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px' width=1% nowrap>{remove_proxy_hostname}:</td>
		<td width=99%>". Field_checkbox("UfdbGuardHTTPDisableHostname-$t",1,$UfdbGuardHTTPDisableHostname)."</td>		
	</tr>	
	<tr>
		<td class=legend style='font-size:22px' width=1% nowrap>{remove_artica_version}:</td>
		<td width=99%>". Field_checkbox("UfdbGuardHTTPNoVersion-$t",1,$UfdbGuardHTTPNoVersion)."</td>		
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{add_webmaster}:</td>
		<td>". Field_checkbox("UfdbGuardHTTPEnablePostmaster-$t",1,$UfdbGuardHTTPEnablePostmaster)."</td>		
	</tr>				
	<tr>
		<td class=legend style='font-size:22px'>{background_color}:</td>
		<td>".Field_ColorPicker("UfdbGuardHTTPBackgroundColor-$t",$UfdbGuardHTTPBackgroundColor,"font-size:22px;width:150px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{background_color} Unlock Page:</td>
		<td>".Field_ColorPicker("UfdbGuardHTTPBackgroundColorBLK-$t",$UfdbGuardHTTPBackgroundColorBLK,"font-size:22px;width:150px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{background_color} Button - Unlock Page:</td>
		<td>".Field_ColorPicker("UfdbGuardHTTPBackgroundColorBLKBT-$t",$UfdbGuardHTTPBackgroundColorBLKBT,"font-size:22px;width:150px")."</td>
	</tr>	
	
	<tr>
		<td class=legend style='font-size:22px'>{font_family}:</td>
		<td><textarea 
			style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='UfdbGuardHTTPFamily-$t'>$UfdbGuardHTTPFamily</textarea>
		</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{font_color}:</td>
		<td>".Field_ColorPicker("UfdbGuardHTTPFontColor-$t",$UfdbGuardHTTPFontColor,"font-size:22px;width:150px")."</td>
	</tr>
	
	
	<tr>
		<td class=legend style='font-size:22px'>{titletext} 1:</td>
		<td><textarea 
			style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='UFDBGUARD_TITLE_1-$t'>$UFDBGUARD_TITLE_1</textarea>
		</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{parapgraph} 1:</td>
		<td><textarea 
			style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='UFDBGUARD_PARA1-$t'>$UFDBGUARD_PARA1</textarea>
		</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{titletext} 2:</td>
		<td><textarea 
			style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='UFDBGUARD_TITLE_2-$t'>$UFDBGUARD_TITLE_2</textarea>
		</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{parapgraph} 2:</td>
		<td><textarea 
			style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='UFDBGUARD_PARA2-$t'>$UFDBGUARD_PARA2</textarea>
		</td>
	</tr>	
	<tr>
	<td colspan=2 align='right'>$button</td>
	</tr>	
<script>
var xSave$t=function(obj){
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		RefreshTab('main_squidguardweb_error_pages'); 
	}

	function Save$t(){
     var XHR = new XHRConnection();
     XHR.appendData('CATEGORY','0');
     XHR.appendData('UFDBGUARD_TITLE_1',encodeURIComponent(document.getElementById('UFDBGUARD_TITLE_1-$t').value));
     XHR.appendData('UFDBGUARD_PARA1',encodeURIComponent(document.getElementById('UFDBGUARD_PARA1-$t').value));
     XHR.appendData('UFDBGUARD_PARA2',encodeURIComponent(document.getElementById('UFDBGUARD_PARA2-$t').value));
     XHR.appendData('UFDBGUARD_TITLE_2',encodeURIComponent(document.getElementById('UFDBGUARD_TITLE_2-$t').value));
     XHR.appendData('UfdbGuardHTTPFamily',document.getElementById('UfdbGuardHTTPFamily-$t').value);
     XHR.appendData('UfdbGuardHTTPBackgroundColor',document.getElementById('UfdbGuardHTTPBackgroundColor-$t').value);
     XHR.appendData('UfdbGuardHTTPBackgroundColorBLK',document.getElementById('UfdbGuardHTTPBackgroundColorBLK-$t').value);
     XHR.appendData('UfdbGuardHTTPBackgroundColorBLKBT',document.getElementById('UfdbGuardHTTPBackgroundColorBLKBT-$t').value);
     XHR.appendData('UfdbGuardHTTPDisableHostname',document.getElementById('UfdbGuardHTTPDisableHostname-$t').value);
     
     
     
     XHR.appendData('UfdbGuardHTTPFontColor',document.getElementById('UfdbGuardHTTPFontColor-$t').value);
	 if(document.getElementById('UfdbGuardHTTPNoVersion-$t').checked){XHR.appendData('UfdbGuardHTTPNoVersion',1);}else{XHR.appendData('UfdbGuardHTTPNoVersion',0);}
     if(document.getElementById('UfdbGuardHTTPEnablePostmaster-$t').checked){XHR.appendData('UfdbGuardHTTPEnablePostmaster',1);}else{XHR.appendData('UfdbGuardHTTPEnablePostmaster',0);}

     XHR.sendAndLoad('$page', 'POST',xSave$t);     	
	
	}
</script>	
";
	
echo $tpl->_ENGINE_parse_body($html);
	
}

function SAVE_SKIN(){
	if(substr($_POST["UfdbGuardHTTPFontColor"], 0,1)<>"#"){$_POST["UfdbGuardHTTPFontColor"]="#{$_POST["UfdbGuardHTTPFontColor"]}";}
	if(substr($_POST["UfdbGuardHTTPBackgroundColor"], 0,1)<>"#"){$_POST["UfdbGuardHTTPBackgroundColor"]="#{$_POST["UfdbGuardHTTPBackgroundColor"]}";}
	if(substr($_POST["UfdbGuardHTTPBackgroundColorBLK"], 0,1)<>"#"){$_POST["UfdbGuardHTTPBackgroundColorBLK"]="#{$_POST["UfdbGuardHTTPBackgroundColorBLK"]}";}
	if(substr($_POST["UfdbGuardHTTPBackgroundColorBLKBT"], 0,1)<>"#"){$_POST["UfdbGuardHTTPBackgroundColorBLKBT"]="#{$_POST["UfdbGuardHTTPBackgroundColorBLKBT"]}";}
	
	
	
	$sock=new sockets();
	while (list ($num, $ligne) = each ($_POST) ){
		$ligne=url_decode_special_tool($ligne);
		$ligne=utf8_encode($ligne);
		
		if(strlen($ligne)>50){
			$sock->SaveConfigFile($ligne, $num);
			continue;
		}
		$sock->SET_INFO($num, stripslashes($ligne));
		}
	
	
}

function UNLOCK_SECTION(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	$EnableSquidGuardHTTPService=$sock->GET_INFO("EnableSquidGuardHTTPService");
	if(!is_numeric($EnableSquidGuardHTTPService)){$EnableSquidGuardHTTPService=1;}
	if($EnableSquidGuardHTTPService==0){
		echo FATAL_ERROR_SHOW_128("{web_page_service_is_disabled}");
		die();
		
	}
	
	$UfdbGuardHTTPAllowSMTP=intval($sock->GET_INFO("UfdbGuardHTTPAllowSMTP"));
	$UfdbGuardHTTPAllowUnblock=intval($sock->GET_INFO("UfdbGuardHTTPAllowUnblock"));
	$UfdbGuardHTTPAllowNoCreds=intval($sock->GET_INFO("UfdbGuardHTTPAllowNoCreds"));
	$UfdbGuardHTTPUnbblockMaxTime=intval($sock->GET_INFO("UfdbGuardHTTPUnbblockMaxTime"));
	$UfdbGuardHTTPUnbblockText1=$sock->GET_INFO("UfdbGuardHTTPUnbblockText1");
	$UfdbGuardHTTPUnbblockText2=$sock->GET_INFO("UfdbGuardHTTPUnbblockText2");
	
	if($UfdbGuardHTTPUnbblockText1==null){
		$UfdbGuardHTTPUnbblockText1="If you estimate that this Internet web site is important and must be displayed click on the link bellow in order to continue.";
	}
	
	if($UfdbGuardHTTPUnbblockText2==null){
		$UfdbGuardHTTPUnbblockText2="Give your username and password here.<br>\nAfter successfully logged you will have free access to %WEBSITE% during %TIME%";
		
	}
	
	
	
	
	if($UfdbGuardHTTPUnbblockMaxTime==0){$UfdbGuardHTTPUnbblockMaxTime=30;}
	
	$Timez[5]="5 {minutes}";
	$Timez[10]="10 {minutes}";
	$Timez[15]="15 {minutes}";
	$Timez[30]="30 {minutes}";
	$Timez[60]="1 {hour}";
	$Timez[120]="2 {hours}";
	$Timez[240]="4 {hours}";
	$Timez[720]="12 {hours}";
	$Timez[2880]="2 {days}";
	
	
$t=time();
	
	
$html="<div class=form style='width:98%'>
		".Paragraphe_switch_img("{UFDBGAURD_ALLOW_UNBLOCK}", "{UFDBGUARD_UNLOCK_EXPLAIN}","UfdbGuardHTTPAllowUnblock",
				$UfdbGuardHTTPAllowUnblock,null,996)."
		".Paragraphe_switch_img("{no_credentials}", "{no_credentials_explain}",
				"UfdbGuardHTTPAllowNoCreds",$UfdbGuardHTTPAllowNoCreds,null,996)."".
		 Paragraphe_switch_img("{smtp_complain}", "{squidguard_smtp_complain}
		 		<div style='text-align:right;text-decoration:underline'>
		 			<a href=\"javascript:blur();\" 
		 			OnClick=\"javascript:Loadjs('$page?smtp-parameters-js=yes')\"
		 			style='font-size:22px'>{smtp_parameters}</a></div>
		 		
		 		",
		 		"UfdbGuardHTTPAllowSMTP",$UfdbGuardHTTPAllowSMTP,null,996)."
<table style='width:100%'>
<tr>
	<td style='font-size:22px' class=legend >{unlock_during}:</td>
	<td>". Field_array_Hash($Timez, "UfdbGuardHTTPUnbblockMaxTime",$UfdbGuardHTTPUnbblockMaxTime,"style:font-size:22px")."</td>
</tr>
	<tr>
		<td class=legend style='font-size:22px'>{text_in_block_page}:</td>
		<td style='width:620px'><textarea 
			style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='UfdbGuardHTTPUnbblockText1'>{$UfdbGuardHTTPUnbblockText1}</textarea>
		</td>
</tr>
<tr>
	<td class=legend style='font-size:22px'>{text_in_form_page}:</td>
	<td style='width:620px'><textarea 
			style='width:100%;height:150px;font-size:18px !important;border:4px solid #CCCCCC;font-family:\"Courier New\",
			Courier,monospace;background-color:white;color:black' id='UfdbGuardHTTPUnbblockText2'>{$UfdbGuardHTTPUnbblockText2}</textarea>
		</td>
</tr>
<tr>
	<td colspan=2 align=right><hr>". button("{apply}","Save$t()",30)."</td>
</tr>
</table>
	<script>
	
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	Loadjs('squid.compile.progress.php');
}	
	
function Save$t(){
	var XHR = new XHRConnection();
	
	XHR.appendData('UfdbGuardHTTPAllowSMTP',document.getElementById('UfdbGuardHTTPAllowSMTP').value);
	XHR.appendData('UfdbGuardHTTPAllowNoCreds',document.getElementById('UfdbGuardHTTPAllowNoCreds').value);
	XHR.appendData('UfdbGuardHTTPAllowUnblock',document.getElementById('UfdbGuardHTTPAllowUnblock').value);
	XHR.appendData('UfdbGuardHTTPUnbblockMaxTime',document.getElementById('UfdbGuardHTTPUnbblockMaxTime').value);
	XHR.appendData('UfdbGuardHTTPUnbblockText1',document.getElementById('UfdbGuardHTTPUnbblockText1').value);
	XHR.appendData('UfdbGuardHTTPUnbblockText2',document.getElementById('UfdbGuardHTTPUnbblockText2').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
	}			
</script>
";
	
echo $tpl->_ENGINE_parse_body($html);	
}

function UNLOCK_SAVE(){
	
	$sock=new sockets();
	
	
	while (list ($num, $ligne) = each ($_POST) ){
		if(strlen($ligne)>20){
			$sock->SaveConfigFile(utf8_encode($ligne),$num);
			continue;
		}
		$sock->SET_INFO($num,utf8_encode($ligne));
	
	}	
	
}

function SMTP_PARAMETERS_POPUP(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	$t=time();
	
	for($i=1;$i<25;$i++){
		$MaxError[$i]="$i {times}";
	}
	
	$SquidGuardWebSMTP=unserialize(base64_decode($sock->GET_INFO("SquidGuardWebSMTP")));
	if(!is_numeric($SquidGuardWebSMTP["MaxError"])){$SquidGuardWebSMTP["MaxError"]=5;}
	
$html="
<div style='width:98%' class=form>
<table style='width:100%'>
	<tr>
	<td nowrap class=legend style='font-size:22px'>{smtp_server_name}:</strong></td>
	<td>" . Field_text("smtp_server_name-$t",trim($SquidGuardWebSMTP["smtp_server_name"]),'font-size:22px;padding:3px;width:250px')."</td>
	</tr>
	<tr>
	<td nowrap class=legend style='font-size:22px'>{smtp_server_port}:</strong></td>
	<td>" . Field_text("smtp_server_port-$t",trim($SquidGuardWebSMTP["smtp_server_port"]),'font-size:22px;padding:3px;width:90px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:22px'>{smtp_sender}:</strong></td>
		<td>" . Field_text("smtp_sender-$t",trim($SquidGuardWebSMTP["smtp_sender"]),'font-size:22px;padding:3px;width:290px')."</td>
	</tr>			
	<tr>
		<td nowrap class=legend style='font-size:22px'>{smtp_recipient}:</strong></td>
		<td>" . Field_text("smtp_recipient-$t",trim($SquidGuardWebSMTP["smtp_recipient"]),'font-size:22px;padding:3px;width:290px')."</td>
	</tr>			
	<tr>
	<td nowrap class=legend style='font-size:22px'>{smtp_auth_user}:</strong></td>
	<td>" . Field_text("smtp_auth_user-$t",trim($SquidGuardWebSMTP["smtp_auth_user"]),'font-size:22px;padding:3px;width:290px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:22px'>{smtp_auth_passwd}:</strong></td>
		<td>" . Field_password("smtp_auth_passwd-$t",trim($SquidGuardWebSMTP["smtp_auth_passwd"]),'font-size:22px;padding:3px;width:290px')."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:22px'>{tls_enabled}:</strong></td>
		<td>" . Field_checkbox_design("tls_enabled-$t",1,$SquidGuardWebSMTP["tls_enabled"])."</td>
	</tr>
	<tr>
		<td nowrap class=legend style='font-size:22px'>{retry}:</strong></td>
		<td>" . Field_array_Hash($MaxError,"MaxError-$t",$SquidGuardWebSMTP["MaxError"],"style:font-size:22px")."</td>
	</tr>		
	<tr><td align='right' colspan=2><p>&nbsp;</p></td></tr>		
	<tr>
	<td align='right' colspan=2>
	
	".button('{test}',"TestSMTP$t();",32)."&nbsp;".button('{apply}',"SaveArticaSMTPNotifValues$t();",32)."</td>
	</tr>
	</table>
	</div>
	<script>
	
	function TestSMTP$t(){
		SaveArticaSMTPNotifValues$t();
		Loadjs('$page?test-smtp-js=yes');
	}
	
	
	var x_SaveArticaSMTPNotifValues$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
	}
	
	function SaveArticaSMTPNotifValues$t(){
		var XHR = new XHRConnection();
		var pp=encodeURIComponent(document.getElementById('smtp_auth_passwd-$t').value);
		if(document.getElementById('tls_enabled-$t').checked){XHR.appendData('tls_enabled',1);}else {XHR.appendData('tls_enabled',0);}
		XHR.appendData('smtp_server_name',document.getElementById('smtp_server_name-$t').value);
		XHR.appendData('smtp_server_port',document.getElementById('smtp_server_port-$t').value);
		XHR.appendData('smtp_recipient',document.getElementById('smtp_recipient-$t').value);
		XHR.appendData('smtp_sender',document.getElementById('smtp_sender-$t').value);
		XHR.appendData('smtp_auth_user',document.getElementById('smtp_auth_user-$t').value);
		XHR.appendData('MaxError',document.getElementById('MaxError-$t').value);
		XHR.appendData('smtp_auth_passwd',pp);
		XHR.appendData('smtp_notifications','yes');
		XHR.sendAndLoad('$page', 'POST',x_SaveArticaSMTPNotifValues$t);
	}
	
	
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);

	
}
function SMTP_PARAMETERS_SAVE(){
	
	if(isset($_POST["smtp_auth_passwd"])){
		$_POST["smtp_auth_passwd"]=url_decode_special_tool($_POST["smtp_auth_passwd"]);
	}
	$sock=new sockets();
	
	$SquidGuardWebSMTP=unserialize(base64_decode($sock->GET_INFO("SquidGuardWebSMTP")));
	
	while (list ($num, $ligne) = each ($_POST) ){
		$SquidGuardWebSMTP[$num]=utf8_encode($ligne);
	
	}
	$sock->SaveConfigFile(base64_encode(serialize($SquidGuardWebSMTP)), "SquidGuardWebSMTP");
}
function tests_smtp(){
	echo "<textarea style='width:100%;height:275px;font-size:14px !important;border:4px solid #CCCCCC;
	font-family:\"Courier New\",
	Courier,monospace;color:black' id='subtitle'>";
	include_once(dirname(__FILE__).'/ressources/smtp/smtp.php');
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	$tpl=new templates();
	$sock=new sockets();
	$SquidGuardWebSMTP=unserialize(base64_decode($sock->GET_INFO("SquidGuardWebSMTP")));
	$GLOBALS["VERBOSE"]=true;

	
	$smtp_sender=$_GET["smtp_sendto"];
	$recipient=$SquidGuardWebSMTP["smtp_recipient"];
	$smtp_senderTR=explode("@",$_GET["smtp_sendto"]);
	$instance=$smtp_senderTR[1];

	$random_hash = md5(date('r', time()));
	
	$body[]="Return-Path: <$smtp_sender>";
	$body[]="Date: ". date("D, d M Y H:i:s"). " +0100 (CET)";
	$body[]="From: $smtp_sender";
	$body[]="To: $recipient";
	$body[]="Subject: Test notification from Web interface";
	
	$body[]="";
	$body[]="";
	$body[]="Here, the message from the robot...";
	$body[]="";
	$body[]="";
	
	$finalbody=@implode("\r\n", $body);
	
	
	$msmtp=new squidguard_msmtp($smtp_sender,$finalbody);
	if($msmtp->Send()){
		echo $msmtp->logs;
		echo "</textarea><script>";
		echo "alert('".$tpl->javascript_parse_text("Test Message\nTo $recipient: {success}")."');</script>";
		return;
	}
	
		
	echo $msmtp->logs."\n";
	
	
	
	

	$smtp=new smtp();
	$smtp->debug=true;
	if($SquidGuardWebSMTP["smtp_auth_user"]<>null){
		$params["auth"]=true;
		$params["user"]=$SquidGuardWebSMTP["smtp_auth_user"];
		$params["pass"]=$SquidGuardWebSMTP["smtp_auth_passwd"];
	}
	$params["host"]=$SquidGuardWebSMTP["smtp_server_name"];
	$params["port"]=$SquidGuardWebSMTP["smtp_server_port"];
	if(!$smtp->connect($params)){
		echo "</textarea><script>";
		echo "alert('".$tpl->javascript_parse_text("{error_while_sending_message} {error} $smtp->error_number $smtp->error_text")."');</script>";
		
		return;
	}


	if(!$smtp->send(array("from"=>$smtp_sender,"recipients"=>$recipient,"body"=>$finalbody,"headers"=>null))){
		$smtp->quit();
		echo "</textarea><script>";
		echo "alert('".$tpl->javascript_parse_text("{error_while_sending_message} {error}\\n $smtp->error_number $smtp->error_text")."');</script>";
		return;
	}

	echo "</textarea><script>";
	echo "alert('".$tpl->javascript_parse_text("Test Message\nTo $recipient: {success}")."');</script>";
	$smtp->quit();
	
}

?>