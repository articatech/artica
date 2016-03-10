<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.ssl.certificate.inc');
	if(posix_getuid()==0){die();}
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["gen-certif-js"])){gen_certif_js();exit;}
	if(isset($_GET["gen-certif-popup"])){gen_certif_popup();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["certificate"])){certificate();exit;}
	if(isset($_GET["parameters"])){parameters_main();exit;}
	if(isset($_GET["popup-settings"])){parameters_main();exit;}
	if(isset($_POST["EnableSSLBump"])){parameters_enable_save();exit;}
	if(isset($_POST["EnableSquidSSLCRTD"])){parameters_certificate_save();exit;}
	if(isset($_GET["whitelist"])){whitelist_popup();exit;}
	if(isset($_GET["whitelist-list"])){whitelist_list();exit;}
	if(isset($_GET["website_ssl_wl"])){whitelist_add();exit;}
	if(isset($_GET["website_ssl_eble"])){whitelist_enabled();exit;}
	if(isset($_GET["website_ssl_del"])){whitelist_del();exit;}
	if(isset($_POST["AllowSquidSSLDropBox"])){AllowSquidSSLDropBox();exit;}
	if(isset($_POST["AllowSquidSSLSkype"])){AllowSquidSSLSkype();exit;}
	
	
	if(isset($_GET["add-params"])){parameters_main();exit;}
	if(isset($_GET["help"])){help();exit;}
	
	js();
	
	
function gen_certif_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{generate_certificate}");
	$page=CurrentPageName();	
	echo "RTMMail(800,'$page?gen-certif-popup=yes','$title');";
	
}
function gen_certif_popup(){
	$sock=new sockets();
	$data=base64_decode($sock->getFrameWork("squid.php?ssl-windows-gen=yes"));
	$tpl=new templates();
	
	$warn=$tpl->javascript_parse_text("{you_need_to_restart_proxy_service}");
	echo "	<center style='margin:10px'>
	<textarea id='text$t' style='font-family:Courier New;
	font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;
	overflow:auto;font-size:14px !important;width:99%;height:390px'>$data</textarea>
	
	<center style='margin:10px'>". $tpl->_ENGINE_parse_body(button("{restart}","Loadjs('squid.compile.progress.php?restart=yes')",18))."</center>
	</center>
	
	<script>
		if( document.getElementById('main-config-sslbump-id') ){
			RefreshTab(document.getElementById('main-config-sslbump-id').value);
		}
		
		alert('$warn');
		
	</script>
	
	";
}
	
	
function js() {
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{squid_sslbump}");
	$page=CurrentPageName();
	
	$start="SSLBUMP_START()";
	if(isset($_GET["in-front-ajax"])){$start="SSLBUMP_START2()";}
	
	$html="
	
	function SSLBUMP_START(){YahooWin2('650','$page?popup=yes','$title');}
	
	function SSLBUMP_START2(){
		$('#BodyContent').load('$page?popup=yes');}		
	

	
	$start;
	";
	
	echo $html;	
	
}

function help(){
	
	echo "<center style='margin:10px'>
			<iframe width=\"853\" 
			height=\"480\" src=\"//www.youtube.com/embed/k4sM-akxPwQ\" frameborder=\"0\" allowfullscreen></iframe>
			
			<p>&nbsp;</p>
			
			<iframe width=\"853\" 
			height=\"480\" src=\"//www.youtube.com/embed/EukUm1iS60E\" frameborder=\"0\" allowfullscreen></iframe>
			
			
		</center>";
	
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$array["parameters"]='{global_parameters}';
	$array["certificate"]='{certificate}';
	//$array["whitelist"]='{whitelist}';
	$array["http-safe-ports-ssl"]=$tpl->_ENGINE_parse_body('{http_safe_ports} (SSL)');
	$array["certificate-center"]=$tpl->_ENGINE_parse_body('{ssl_certificate}');
	$array["help"]=$tpl->_ENGINE_parse_body('{help}');
	//$array["popup-bandwith"]='{bandwith}';
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	
	$sock=new sockets();
	$SquidUrgency=intval($sock->GET_INFO("SquidUrgency"));
	$SquidSSLUrgency=intval($sock->GET_INFO("SquidSSLUrgency"));
	
	if($SquidSSLUrgency==1){
		echo FATAL_ERROR_SHOW_128(
				"<div style='font-size:22px'>{proxy_in_ssl_emergency_mode}</div>
			<div style='font-size:18px'>{proxy_in_ssl_emergency_mode_explain}</div>
			<div style='text-align:right;margin-top:20px'><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.urgency.php?ssl=yes');\"
			style='text-decoration:underline;font-size:26px'>{disable_emergency_mode}</a></div>
			");
	
	
	}
	
	if($SquidUrgency==1){
		echo FATAL_ERROR_SHOW_128(
				"<div style='font-size:22px'>{proxy_in_emergency_mode}</div>
			<div style='font-size:18px'>{proxy_in_emergency_mode_explain}</div>
			<div style='text-align:right;margin-top:20px'><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.urgency.php?justbutton=yes');\"
			style='text-decoration:underline;font-size:26px'>{disable_emergency_mode}</a></div>
			");
		return;
	
	}
	
	
	
	$fontsize=20;

	while (list ($num, $ligne) = each ($array) ){
		if($num=="http-safe-ports-ssl"){
			$html[]=$tpl->_ENGINE_parse_body("<li>
			<a href=\"squid.advParameters.php?http-safe-ports-ssl=yes&t=$t\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="certificate-center"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"certificates.center.php?popup=yes&t=$t\">
					<span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}		
		
		
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&t=$t\">
				<span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
	}
	
	
	echo 
	"<input type='hidden' id='main-config-sslbump-id' value='main_config_sslbump$t'>".
	build_artica_tabs($html, "main_config_sslbump$t");
}

function LoadCountryCodes(){
	$db=file_get_contents(dirname(__FILE__) . '/ressources/databases/ISO-3166-Codes-Countries.txt');
	$tbl=explode("\n",$db);
	while (list ($num, $ligne) = each ($tbl) ){
		if(preg_match('#(.+?);\s+([A-Z]{1,2})#',$ligne,$regs)){
			$regs[2]=trim($regs[2]);
			$regs[1]=trim($regs[1]);

			$array_country_codes[$regs[2]]=$regs[2];
			
			
		}
			
	}
	
	return $array_country_codes;
}

function certificate(){
	$tpl=new templates();
	$sock=new sockets();
	$squid=new squidbee();
	$master_version=$squid->SQUID_VERSION;
	$compilefile="ressources/logs/squid.compilation.params";
	if(!is_file($compilefile)){$sock->getFrameWork("squid.php?compil-params=yes");}
	$COMPILATION_PARAMS=unserialize(base64_decode(@file_get_contents($compilefile)));
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	$EnableSquidSSLCRTD=$sock->GET_INFO("EnableSquidSSLCRTD");
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$LicenseInfos=unserialize(base64_decode($sock->GET_INFO("LicenseInfos")));
	$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
	if($LicenseInfos["COMPANY"]==null){$LicenseInfos["COMPANY"]=$WizardSavedSettings["company_name"];}
	if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}
	if(!is_numeric($LicenseInfos["EMPLOYEES"])){$LicenseInfos["EMPLOYEES"]=$WizardSavedSettings["employees"];}
	
	$tr[]="<hr>{parameters}:<table>";
	$tr[]="<td width=1% nowrap><img src='img/arrow-right-16.png'></td>
	<td style='font-size:14px'>{version}: $master_version</td>
	</tR>";
	while (list ($key, $val) = each ($COMPILATION_PARAMS) ){
		$tr[]="<td width=1% nowrap><img src='img/arrow-right-16.png'></td>
		<td style='font-size:14px'>$key</td>
		</tR>";
	}
	$tr[]="</table>";
	$table_compile=@implode("\n", $tr);
	$tr=null;
	
	if(!isset($COMPILATION_PARAMS["enable-ssl"])){
		
		echo FATAL_ERROR_SHOW_128("{error_squid_ssl_not_compiled}".$table_compile);
		return;
	
	}
	
	if(!isset($COMPILATION_PARAMS["enable-ssl-crtd"])){
		echo $tpl->_ENGINE_parse_body("<p class=text-error>{enable-ssl-crtd-not-compiled}</p><p>.$table_compile</p>");
	}
	$array_country_codes=LoadCountryCodes();
	$ArticaSquidParameters=$sock->GET_INFO('ArticaSquidParameters');
	$ini=new Bs_IniHandler();
	$ini->loadString($ArticaSquidParameters);
	
	include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");
	$squid_reverse=new squid_reverse();
	$sslcertificates=$squid_reverse->ssl_certificates_list();
	
	$DefaultSSLParams=unserialize(base64_decode($sock->GET_INFO("DefaultSSLParams")));
	if($DefaultSSLParams["countryName"]==null){$DefaultSSLParams["countryName"]="US";}
	if($DefaultSSLParams["stateOrProvinceName"]==null){$DefaultSSLParams["stateOrProvinceName"]=$WizardSavedSettings["city"];}
	if($DefaultSSLParams["localityName"]==null){$DefaultSSLParams["localityName"]=$WizardSavedSettings["city"];}
	if($DefaultSSLParams["organizationName"]==null){$DefaultSSLParams["organizationName"]=$LicenseInfos["COMPANY"];}
	if($DefaultSSLParams["organizationalUnitName"]==null){$DefaultSSLParams["organizationalUnitName"]=$WizardSavedSettings["organization"];}
	if(!is_numeric($DefaultSSLParams["CertificateMaxDays"])){$DefaultSSLParams["CertificateMaxDays"]=730;}
	$page=CurrentPageName();
	
$html="
<div style='font-size:16px' class=explain>{squid_ssl_certificate_explain}</div>
<div style='width:98%' class=form>
<table style='width:100%'>		
<tr>
		<td class=legend nowrap style='font-size:18px'>{use_certificate_from_certificate_center}:</td>
		<td>". Field_array_Hash($sslcertificates, "certificate-$t",$ini->_params["NETWORK"]["certificate_center"],null,null,0,"font-size:18px")."</td>
	</tr>	
	<tr>
		<td style='font-size:18px' class=legend>{ssl_crtd}:</td>
		<td>". Field_checkbox("EnableSquidSSLCRTD-$t",1,$EnableSquidSSLCRTD)."</td>
	</tr>	
	<tr>
		<td style='font-size:18px' class=legend>{whitelist_all_domains}:</td>
		<td>". Field_checkbox("SSL_BUMP_WHITE_LIST-$t",1,$squid->SSL_BUMP_WHITE_LIST)."</td>
	</tr>
	<tr>
		<td style='font-size:18px' class=legend>{countryName}</strong>:</td>
		<td>". Field_array_Hash($array_country_codes, "countryName-$t",$DefaultSSLParams["countryName"],null,null,0,"font-size:18px")."</td>
	</tr>
	<tr>
		<td style='font-size:18px' class=legend>{stateOrProvinceName}:</strong></td>
		<td align='left'>" . Field_text("stateOrProvinceName-$t",$DefaultSSLParams["stateOrProvinceName"],"font-size:18px;width:150px;padding:3px")  . "</td>
	</tr>
	<tr>
		<td style='font-size:18px' class=legend>{localityName}:</strong></td>
		<td align='left'>" . Field_text("localityName-$t",$DefaultSSLParams["localityName"],"font-size:18px;width:150px;padding:3px")  . "</td>
	</tr>	
	
	<tr>
		<td style='font-size:18px' class=legend>{organizationName}:</strong></td>
		<td align='left'>" . Field_text("organizationName-$t",$DefaultSSLParams["organizationName"],"font-size:18px;width:210px;padding:3px")  . "</td>
	</tr>				
	<tr>
		<td style='font-size:18px' class=legend>{organizationalUnitName}:</strong></td>
		<td align='left'>" . Field_text("organizationalUnitName-$t",$DefaultSSLParams["organizationalUnitName"],"font-size:18px;width:150px;padding:3px")  . "</td>
	</tr>					
	<tr>
		<td style='font-size:18px' class=legend>{CertificateMaxDays}:</strong></td>
		<td align='left' style='font-size:16px;width:18px;padding:3px'>" . Field_text("CertificateMaxDays-$t",$DefaultSSLParams["CertificateMaxDays"],"font-size:18px;width:90px;padding:3px")  . "&nbsp;{days}</td>
	</tr>	
	<tr>
		<td colspan=2 align=right><hr>".button("{apply}","Save$t()",22)."</td>
	</tr>

	
	
</table>
</div>
<script>
var xSave$t=function(obj){
    var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);}
	Loadjs('$page?gen-certif-js=yes');
	
}	

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('certificate',document.getElementById('certificate-$t').value);
	XHR.appendData('stateOrProvinceName',document.getElementById('stateOrProvinceName-$t').value);
	XHR.appendData('localityName',document.getElementById('localityName-$t').value);
	XHR.appendData('countryName',document.getElementById('countryName-$t').value);
	XHR.appendData('organizationName',document.getElementById('organizationName-$t').value);
	XHR.appendData('organizationalUnitName',document.getElementById('organizationalUnitName-$t').value);
	XHR.appendData('CertificateMaxDays',document.getElementById('CertificateMaxDays-$t').value);
	if(document.getElementById('EnableSquidSSLCRTD-$t').checked){XHR.appendData('EnableSquidSSLCRTD',1);}else{XHR.appendData('EnableSquidSSLCRTD',0);}
	if(document.getElementById('SSL_BUMP_WHITE_LIST-$t')){
		if(document.getElementById('SSL_BUMP_WHITE_LIST-$t').checked){XHR.appendData('SSL_BUMP_WHITE_LIST',1);}else{XHR.appendData('SSL_BUMP_WHITE_LIST',0);}
	}
	XHR.sendAndLoad('$page', 'POST',xSave$t);		
}
</script>				
";	

echo $tpl->_ENGINE_parse_body($html);
	
}


function parameters_main(){
	
	// --enable-ssl-crtd
	$tpl=new templates();
	$sock=new sockets();
	$compilefile="ressources/logs/squid.compilation.params";
	if(!is_file($compilefile)){$sock->getFrameWork("squid.php?compil-params=yes");}
	$COMPILATION_PARAMS=unserialize(base64_decode(@file_get_contents($compilefile)));
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	$EnableSquidSSLCRTD=$sock->GET_INFO("EnableSquidSSLCRTD");
	$SSlBumpAllowLogon=intval($sock->GET_INFO("SSlBumpAllowLogon"));
	
	
	$LicenseInfos=unserialize(base64_decode($sock->GET_INFO("LicenseInfos")));
	$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
	if($LicenseInfos["COMPANY"]==null){$LicenseInfos["COMPANY"]=$WizardSavedSettings["company_name"];}
	if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}
	if(!is_numeric($LicenseInfos["EMPLOYEES"])){$LicenseInfos["EMPLOYEES"]=$WizardSavedSettings["employees"];}
	
	if(!is_numeric($EnableSquidSSLCRTD)){$EnableSquidSSLCRTD=1;}
	$DefaultSSLParams=unserialize(base64_decode($sock->GET_INFO("DefaultSSLParams")));
	
	$SquidSSLUrgency=intval($sock->GET_INFO("SquidSSLUrgency"));
	
	if($SquidSSLUrgency==1){
		echo FATAL_ERROR_SHOW_128(
				"<div style='font-size:22px'>{proxy_in_ssl_emergency_mode}</div>
			<div style='font-size:18px'>{proxy_in_ssl_emergency_mode_explain}</div>
			<div style='text-align:right;margin-top:20px'><a href=\"javascript:blur();\" 
				OnClick=\"javascript:Loadjs('squid.urgency.php?ssl=yes');\"
			style='text-decoration:underline;font-size:26px'>{disable_emergency_mode}</a></div>
			");
	
	
	}
	
	$squid=new squidbee();
	$page=CurrentPageName();
	$sslbumb=false;
	$users=new usersMenus();
	$t=$_GET["t"];
	
	if(!isset($COMPILATION_PARAMS["enable-ssl"])){
		echo FATAL_ERROR_SHOW_128("{error_squid_ssl_not_compiled}");
		return;
		
	}
	
	if(!isset($COMPILATION_PARAMS["enable-ssl-crtd"])){
		echo $tpl->_ENGINE_parse_body("<p class=text-error>{enable-ssl-crtd-not-compiled}</p>");
		
	
	}
	$array_country_codes=LoadCountryCodes();
	
	$DisableSSLStandardPort=$sock->GET_INFO("DisableSSLStandardPort");
	if(!is_numeric($DisableSSLStandardPort)){$DisableSSLStandardPort=1;}
	
	if($DisableSSLStandardPort==0){$StandardPortSSL=1;}else{$StandardPortSSL=0;}
	
	
	$DisableSSLStandardPort_warn=null;
	if($DisableSSLStandardPort==1){$DisableSSLStandardPort_warn="<div style='font-size:14px;font-style:italic'>{DisableSSLStandardPort_warn}</div>";}
	
	if(preg_match("#^([0-9]+)\.([0-9]+)#",$users->SQUID_VERSION,$re)){
		
	    	if($re[1]>=3){if($re[2]>=1){$sslbumb=true;}}}
		
		$enableSSLBump=Paragraphe_switch_img("{activate_ssl_bump}",
	"{activate_ssl_bump_text2}","EnableSSLBump-$t",$squid->SSL_BUMP,null,650);
		
		$FIELD_StandardPortSSL=Paragraphe_switch_img("{activate_ssl_on_standard_ports}",
	"{activate_ssl_on_standard_ports_text}$DisableSSLStandardPort_warn","StandardPortSSL-$t",$StandardPortSSL,null,650);
		
		$FIELD_certificate_login=Paragraphe_switch_img("{allow_download_certificate_public}",
				"{allow_download_certificate_public_explain}","SSlBumpAllowLogon-$t",$SSlBumpAllowLogon,null,650);		
		
    if(!is_numeric($squid->ssl_port)){$squid->ssl_port =$squid->listen_port+5;}		
	if($squid->ssl_port<3){$squid->ssl_port =$squid->listen_port+5;}	
	if($EnableRemoteStatisticsAppliance==0){
		if(!$sslbumb){
			echo FATAL_ERROR_SHOW_128("{wrong_squid_version}: &laquo;$users->SQUID_VERSION&raquo;<hr>{wrong_squid_version_feature_text}");
			return;
		}
			
	}
	
	include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");
	$squid_reverse=new squid_reverse();
	$sslcertificates=$squid_reverse->ssl_certificates_list();
	
	
	$sslproxy_versions[1]="{default}";
	$sslproxy_versions[2]="SSLv2 {only}";
	$sslproxy_versions[3]="SSLv3 {only}";
	$sslproxy_versions[4]="TLSv1.0 {only}";
	$sslproxy_versions[5]="TLSv1.1 {only}";
	$sslproxy_versions[6]="TLSv1.2 {only}";
	$sslproxy_version=intval($sock->GET_INFO("sslproxy_version"));
	if($sslproxy_version==0){$sslproxy_version=1;}
	


	if($EnableSquidSSLCRTD==1){
		if(is_file("/usr/share/artica-postfix/ressources/squid/certificate.der")){
			$DownloadDER="
			<div style='width:650px;border:1px solid #005447;border-radius: 4px 4px 4px 4px;margin-top:15px;margin-bottom:15px'>
			<table style='width:100%'>
					
			<tr>
				<td style='vertical-align:top;width:128px'>
					<img src='img/certificate-128.png' align='left'></td>
				</td>
				<td style='vertical-align:top;padding-top:15px;padding-left:15px'><a href='ressources/squid/certificate.der' 
					style='font-size:16px;color:#A91919;font-weight:bold;text-decoration:underline'>
					
			{certificate_to_deploy_explain}
			</a>
			</td>
			</tr>
			</table>
			</div>";
		}
	}
	
	$html="
	<div style='font-size:14px' id='sslbumpdiv$t'></div>
	
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td colspan=2>$enableSSLBump
			<table style='width:100%'>
			<tr>
				<td style='font-size:22px;vertical-align:middle' class=legend nowrap>{sslproxy_version}:</td>
				<td>". Field_array_Hash($sslproxy_versions,"sslproxy_version-$t",$sslproxy_version,"style:font-size:22px")."</td>
			</tr>
			</table>
			$FIELD_StandardPortSSL
			$DownloadDER
			<center style='margin:10px'>
				". button("{generate_certificate}","Loadjs('$page?gen-certif-js=yes')",18)."
			</center>
			
			$FIELD_certificate_login</td>
	</tr>
	<tr>
		<td style='font-size:26px' class=legend>{ssl_port}:</td>
		<td><a href=\"javascript:blur();\" 
			OnClick=\"javascript:Loadjs('squid.popups.php?script=listen_port')\"
			style='font-size:26px;font-weight:bold;text-decoration:underline'>
			$squid->ssl_port</td>
	</tr>
	
	
	<tr>
		<td colspan=2 align=right><hr>".button("{apply}","SaveEnableSSLDump$t()",42)."</td>
	</tr>

	</table>

	</div>
	<script>
var x_SaveEnableSSLDump$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);}
    Loadjs('squid.compile.progress.php?restart=yes&ask=yes');
    RefreshTab('main_config_sslbump$t');
}	

function SaveEnableSSLDump$t(){
	var XHR = new XHRConnection();
	if(!document.getElementById('EnableSSLBump-$t')){return;}
	
	XHR.appendData('sslproxy_version',document.getElementById('sslproxy_version-$t').value);
	XHR.appendData('EnableSSLBump',document.getElementById('EnableSSLBump-$t').value);
	XHR.appendData('SSlBumpAllowLogon',document.getElementById('SSlBumpAllowLogon-$t').value);
	XHR.appendData('StandardPortSSL',document.getElementById('StandardPortSSL-$t').value);
	
	if(document.getElementById('SSL_BUMP_WHITE_LIST-$t')){
		if(document.getElementById('SSL_BUMP_WHITE_LIST-$t').checked){XHR.appendData('SSL_BUMP_WHITE_LIST',1);}else{XHR.appendData('SSL_BUMP_WHITE_LIST',0);}
	}
	XHR.sendAndLoad('$page', 'POST',x_SaveEnableSSLDump$t);		
	
	}
</script>
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
}

function parameters_enable_save(){
	$squid=new squidbee();
	$tpl=new templates();
	$sock=new sockets();
	if(isset($_POST["EnableSquidSSLCRTD"])){
		$sock->SET_INFO("EnableSquidSSLCRTD", $_POST["EnableSquidSSLCRTD"]);
	}
	if(isset($_POST["DisableSSLStandardPort"])){
		$sock->SET_INFO("DisableSSLStandardPort", $_POST["DisableSSLStandardPort"]);
	}
	
	if(isset($_POST["StandardPortSSL"])){
		if($_POST["StandardPortSSL"]==1){
			$sock->SET_INFO("DisableSSLStandardPort",0);
		}else{
			$sock->SET_INFO("DisableSSLStandardPort",1);
		}
	}
	
	if(isset($_POST["SSlBumpAllowLogon"])){
		$sock->SET_INFO("SSlBumpAllowLogon", $_POST["SSlBumpAllowLogon"]);
	}
	
	$sock->SET_INFO("sslproxy_version", $_POST["sslproxy_version"]);
		
	if($squid->IS_33){
		$sock->SET_INFO("EnableSquidSSLCRTD", 1);
	}
	
	$squid->SSL_BUMP=$_POST["EnableSSLBump"];
	
	if($_POST["EnableSSLBump"]==1){
		if(!is_numeric($squid->ssl_port)){$squid->ssl_port=$squid->listen_port+10;}
		if($squid->ssl_port==443){$squid->ssl_port=$squid->listen_port+10;}	
	
	}
	if(isset($_POST["SSL_BUMP_WHITE_LIST"])){
		$squid->SSL_BUMP_WHITE_LIST=$_POST["SSL_BUMP_WHITE_LIST"];
	}
	$squid->SaveToLdap(true);

	
}

function parameters_certificate_save(){
	$squid=new squidbee();
	$tpl=new templates();
	$sock=new sockets();
	
	if(isset($_POST["EnableSquidSSLCRTD"])){
		$sock->SET_INFO("EnableSquidSSLCRTD", $_POST["EnableSquidSSLCRTD"]);
	}
	
	if(isset($_POST["StandardPortSSL"])){
		if($_POST["StandardPortSSL"]==1){
			$sock->SET_INFO("DisableSSLStandardPort",0);
		}else{
			$sock->SET_INFO("DisableSSLStandardPort",1);
		}
	}
	
	if(isset($_POST["SSlBumpAllowLogon"])){
		$sock->SET_INFO("SSlBumpAllowLogon", $_POST["SSlBumpAllowLogon"]);
	}
	
	
	if(isset($_POST["certificate"])){
		$ArticaSquidParameters=$sock->GET_INFO('ArticaSquidParameters');
		$ini=new Bs_IniHandler();
		$ini->loadString($ArticaSquidParameters);
		$ini->_params["NETWORK"]["certificate_center"]=$_POST["certificate"];
		$sock->SaveConfigFile($ini->toString(), "ArticaSquidParameters");
	}
	
	$DefaultSSLParams=unserialize(base64_decode($sock->GET_INFO("DefaultSSLParams")));
	$DefaultSSLParams["stateOrProvinceName"]=$_POST["stateOrProvinceName"];
	$DefaultSSLParams["localityName"]=$_POST["localityName"];
	$DefaultSSLParams["countryName"]=$_POST["countryName"];
	$DefaultSSLParams["organizationName"]=$_POST["organizationName"];
	$DefaultSSLParams["organizationalUnitName"]=$_POST["organizationalUnitName"];
	$DefaultSSLParams["CertificateMaxDays"]=$_POST["CertificateMaxDays"];
	$sock->SaveConfigFile(base64_encode(serialize($DefaultSSLParams)), "DefaultSSLParams");
	$sock->getFrameWork("squid.php?remove-ssl-cert-def=yes");
}


function whitelist_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$new_webiste=$tpl->_ENGINE_parse_body("{new_website}");
	$email=$tpl->_ENGINE_parse_body("{email}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$delete_this_member_ask=$tpl->javascript_parse_text("{delete_this_member_ask}");
	$SSL_BUMP_WL=$tpl->_ENGINE_parse_body("{SSL_BUMP_WL}");
	$website_ssl_wl_help=$tpl->javascript_parse_text("{website_ssl_wl_help}");
	$parameters=$tpl->javascript_parse_text("{parameters}");
	$website_name=$tpl->javascript_parse_text("{websites}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$squid=new squidbee();
	$title=$tpl->javascript_parse_text("{ssl_whitelist}");
	
	if($squid->hasProxyTransparent==1){
		$explain=$tpl->_ENGINE_parse_body("<div style='font-weight:bold;color:#BD0000'>{sslbum_wl_not_supported_transp}</div>");
	}
	
	//$q=new mysql_squid_builder();
	//$q->QUERY_SQL("ALTER TABLE `usersisp` ADD UNIQUE (`email`)");
	
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$new_webiste</strong>', bclass: 'Add', onpress : sslBumbAddwl},
	{name: '<strong style=font-size:18px>$apply</strong>', bclass: 'Apply', onpress : Apply$t}
	],";	
	
$html="
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
<script>
row_id='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?whitelist-list=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$website_name', name : 'website_name', width : 862, sortable : false, align: 'left'},	
		{display: '$enabled', name : 'enabled', width : 68, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'delete', width : 68, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
		{display: '$website_name', name : 'website_name'},
		],
	sortname: 'website_name',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '98%',
	height: 510,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

var x_sslBumbAddwl$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);}
    $('#flexRT$t').flexReload();
}	
      
function sslBumbAddwlCheck(e){
	if(checkEnter(e)){sslBumbAddwl();} 
}

function sslBumbAddwl(){
	var www=prompt('$website_ssl_wl_help');
	if(www){
		var XHR = new XHRConnection();
		XHR.appendData('website_ssl_wl',www);
		XHR.sendAndLoad('$page', 'GET',x_sslBumbAddwl$t);		
	}
}
	
var x_sslbumpEnableW=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);}
    if(row_id.length>0){ $('#row'+row_id).remove();}
}	
	
function sslbumpEnableW(idname){
	var XHR = new XHRConnection();
	if(document.getElementById(idname).checked){XHR.appendData('enable',1);}else{XHR.appendData('enable',0);}
	XHR.appendData('website_ssl_eble',idname);
	XHR.sendAndLoad('$page', 'GET',x_sslbumpEnableW);		
}

function Apply$t(){
	Loadjs('squid.compile.progress.php');
}

function sslbumpAllowSquidSSLDropBox(){
	var XHR = new XHRConnection();
	if(document.getElementById('AllowSquidSSLDropBox').checked){XHR.appendData('AllowSquidSSLDropBox',1);}else{XHR.appendData('AllowSquidSSLDropBox',0);}
	XHR.sendAndLoad('$page', 'POST',x_sslBumbAddwl$t);

}

function sslbumpAllowSquidSSLSkype(){
	var XHR = new XHRConnection();
	if(document.getElementById('AllowSquidSSLSkype').checked){XHR.appendData('AllowSquidSSLSkype',1);}else{XHR.appendData('AllowSquidSSLSkype',0);}
	XHR.sendAndLoad('$page', 'POST',x_sslBumbAddwl$t);
}
		
function sslBumSettings(){
		YahooWin3('550','$page?add-params=yes','$parameters');
	}
		
		
	function sslbumpDeleteW(ID,rowid){
			row_id=rowid;
			var XHR = new XHRConnection();
			XHR.appendData('website_ssl_del',ID);
			XHR.sendAndLoad('$page', 'GET',x_sslBumbAddwl$t);	
		}
		
	
</script>

";
	
	echo $html;
}




function whitelist_enabled(){
	if(preg_match("#ENABLE_([0-9]+)#",$_GET["website_ssl_eble"],$re)){
		$sql="UPDATE squid_ssl SET enabled={$_GET["enable"]} WHERE ID={$re[1]}";
		$q=new mysql();
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
		$s=new squidbee();
		$s->SaveToLdap();		
	}
}


function whitelist_add(){
	$_GET["website_ssl_wl"]=str_replace("https://","",$_GET["website_ssl_wl"]);
	if(preg_match("#^www\.(.+)#", $_GET["website_ssl_wl"],$re)){$_GET["website_ssl_wl"]=".".$re[1];}
	if(substr($_GET["website_ssl_wl"], 0,1)<>"."){$_GET["website_ssl_wl"]=".".$_GET["website_ssl_wl"];}
	$sql="INSERT INTO squid_ssl(website_name,enabled,`type`) VALUES('{$_GET["website_ssl_wl"]}',1,'ssl-bump-wl');";	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$s=new squidbee();
	$s->SaveToLdap();	
	}
function whitelist_del(){
	$sql="DELETE FROM squid_ssl WHERE ID={$_GET["website_ssl_del"]}";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
	$s=new squidbee();
	$s->SaveToLdap();
}

function whitelist_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$database="artica_backup";	
	$search='%';
	$table="squid_ssl";
	$page=1;
	$sock=new sockets();
	$FORCE_FILTER="AND `type`='ssl-bump-wl'";
	$squid=new squidbee();
	

	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	$results = $q->QUERY_SQL($sql,$database);
	
	$AllowSquidSSLDropBox=intval($sock->GET_INFO('AllowSquidSSLDropBox'));
	$AllowSquidSSLSkype=intval($sock->GET_INFO('AllowSquidSSLSkype'));
	$enable=Field_checkbox("AllowSquidSSLDropBox",1,$AllowSquidSSLDropBox,"sslbumpAllowSquidSSLDropBox()");
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = 2;
	
	$no_ssl_bump_dropbox=$tpl->_ENGINE_parse_body("{no_ssl_bump_dropbox}");
	$no_ssl_bump_skype=$tpl->_ENGINE_parse_body("{no_ssl_bump_skype}");
	$color="black";
	if($AllowSquidSSLDropBox==0){$color="#AFAFAF";}
	
	$data['rows'][] = array(
		'id' => 0,
		'cell' => array("<span style='font-size:20px;color:$color'>$no_ssl_bump_dropbox</span>"
		,$enable,"&nbsp;" )
		);
	
	$color="black";
	$enable=Field_checkbox("AllowSquidSSLSkype",1,$AllowSquidSSLSkype,"sslbumpAllowSquidSSLSkype()");
	if($AllowSquidSSLSkype==0){$color="#AFAFAF";}
	$data['rows'][] = array(
			'id' => 0,
			'cell' => array("<span style='font-size:20px;color:$color'>$no_ssl_bump_skype</span>"
					,$enable,"&nbsp;" )
	);
	
	if(!$q->ok){json_error_show($q->mysql_error);}
		
	
	if(mysql_num_rows($results)>0){
		
		$data['page'] = $page;
		$data['total'] = $total;
	while ($ligne = mysql_fetch_assoc($results)) {
		$id=md5(serialize($ligne));
		$color="black";	
		$delete="<a href=\"javascript:blur()\" OnClick=\"javascript:sslbumpDeleteW('{$ligne["ID"]}','$id');\"><img src='img/delete-24.png'></a>";   
		$enable=Field_checkbox("ENABLE_{$ligne["ID"]}",1,$ligne["enabled"],"sslbumpEnableW('ENABLE_{$ligne["ID"]}')");
		if($ligne["enabled"]==0){$color="#AFAFAF";}
		if($squid->SSL_BUMP_WHITE_LIST==1){$color="#AFAFAF";}
		
			
		
		$data['rows'][] = array(
			'id' => $id,
			'cell' => array("<span style='font-size:20px;color:$color'>{$ligne["website_name"]}</span>"
			,$enable,$delete )
			);
		}
	
	}
	
	
echo json_encode($data);		

}	

function AllowSquidSSLDropBox(){
	$sock=new sockets();
	$sock->SET_INFO("AllowSquidSSLDropBox", $_POST["AllowSquidSSLDropBox"]);
	
}
function AllowSquidSSLSkype(){
	$sock=new sockets();
	$sock->SET_INFO("AllowSquidSSLSkype", $_POST["AllowSquidSSLSkype"]);	
}



?>