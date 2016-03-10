<?php
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


$usersmenus=new usersMenus();
if(!$usersmenus->AsPostfixAdministrator){echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die();}

if(isset($_POST["PostFixMasterCertificate"])){Save();exit;}

main_ssl();

function main_ssl(){
	include_once(dirname(__FILE__))."/ressources/class.squid.reverse.inc";
	$sock=new sockets();
	$squid_reverse=new squid_reverse();
	$t=time();
	$tpl=new templates();
	$PostfixEnableMasterCfSSL=intval($sock->GET_INFO("PostfixEnableMasterCfSSL"));
	$PostFixMasterCertificate=$sock->GET_INFO("PostFixMasterCertificate");
	
	$sslcertificates=$squid_reverse->ssl_certificates_list();

	$ENABLE_SMTPS=Paragraphe_switch_img('{ENABLE_SMTPS}','{SMTPS_TEXT}','PostfixEnableMasterCfSSL',$PostfixEnableMasterCfSSL,null,1400);
	$page=CurrentPageName();
	
	$ENABLE_SMTPS_CERTIFICATE="
	<table style='width:100%'>
	<tr>
	<td colspan=2>$ENABLE_SMTPS</td>
	</tr>
	<tr>
		<td class=legend nowrap style='font-size:26px'>{use_certificate_from_certificate_center}:</td>
		<td>". Field_array_Hash($sslcertificates, "certificate-$t",$PostFixMasterCertificate,null,null,0,"font-size:26px")."</td>
	</tr>
	<tr>
		<td align='right' colspan=2><hr>". button("{save}","Save$t()",40)."</td>
	</table>";
	
	
	
	$html="
	<div id='smtps' style='width:98%' class=form>
	$ENABLE_SMTPS_CERTIFICATE
	</div>
	<script>
	var xSave$t= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		Loadjs('postfix.sasl.progress.php');
	}
	
	
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('PostFixMasterCertificate',document.getElementById('certificate-$t').value);
		XHR.appendData('PostfixEnableMasterCfSSL',document.getElementById('PostfixEnableMasterCfSSL').value);
		XHR.sendAndLoad('$page', 'POST',xSave$t);
	
	}
	</script>	
	
	
	
	";


	
	echo $tpl->_ENGINE_parse_body($html);
}
function Save(){
	$sock=new sockets();
	$sock->SET_INFO("PostFixMasterCertificate", $_POST["PostFixMasterCertificate"]);
	$sock->SET_INFO("PostfixEnableMasterCfSSL", $_POST["PostfixEnableMasterCfSSL"]);
	
}