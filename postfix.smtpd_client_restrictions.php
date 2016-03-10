<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');
	include_once('ressources/class.maincf.multi.inc');
	
	$users=new usersMenus();
$tpl=new templates();
if(!PostFixVerifyRights()){
		echo $tpl->javascript_parse_text("alert('{ERROR_NO_PRIVS}');");
		die();
	}	

if(isset($_GET["popup"])){smtpd_client_restrictions_popup();exit;}
if(isset($_GET["reject_unknown_client_hostname"])){smtpd_client_restrictions_save();exit;}


js();


function js_smtpd_client_restrictions_save(){
	$page=CurrentPageName();
	
	return "
	

	";
	
	
}


function smtpd_client_restrictions_popup(){
	

	$sock=new sockets();
	$users=new usersMenus();
	$EnablePostfixAntispamPack_value=$sock->GET_INFO('EnablePostfixAntispamPack');	
	$EnableGenericrDNSClients=$sock->GET_INFO("EnableGenericrDNSClients");
	$reject_forged_mails=$sock->GET_INFO('reject_forged_mails');	
	
	
	$EnablePostfixInternalDomainsCheck=$sock->GET_INFO('EnablePostfixInternalDomainsCheck');
	$RestrictToInternalDomains=$sock->GET_INFO('RestrictToInternalDomains');
	
	$reject_unknown_client_hostname=$sock->GET_INFO('reject_unknown_client_hostname');
	$reject_unknown_reverse_client_hostname=$sock->GET_INFO('reject_unknown_reverse_client_hostname');
	$reject_unknown_sender_domain=$sock->GET_INFO('reject_unknown_sender_domain');
	$reject_invalid_hostname=$sock->GET_INFO('reject_invalid_hostname');
	$reject_non_fqdn_sender=$sock->GET_INFO('reject_non_fqdn_sender');
	$disable_vrfy_command=$sock->GET_INFO('disable_vrfy_command');
	$enforce_helo_restrictions=intval($sock->GET_INFO('enforce_helo_restrictions'));
	
	if($EnablePostfixInternalDomainsCheck==null){$EnablePostfixInternalDomainsCheck=0;}
	
		
	
	
	
	
	
	
	
	$whitelists=Paragraphe("routing-domain-relay.png","{PostfixAutoBlockDenyAddWhiteList}","{PostfixAutoBlockDenyAddWhiteList_explain}","javascript:Loadjs('postfix.iptables.php?white-js=yes')");
	$rollover=CellRollOver();
	
	if(!$users->POSTFIX_PCRE_COMPLIANCE){
		$EnableGenericrDNSClients=0;
		$EnableGenericrDNSClientsDisabled=1;
		$EnableGenericrDNSClientsDisabledText="<br><i><span style='color:#d32d2d;font-size:11px'>{EnableGenericrDNSClientsDisabledText}</span></i>";
	}
	
	$t=time();
	$page=CurrentPageName();
$html="




	<div class=explain style='font-size:18px'>{smtpd_client_restrictions_text}</div>
	<input type='hidden' id='EnableGenericrDNSClientsDisabled' value='$EnableGenericrDNSClientsDisabled'>
	<div id='smtpd_client_restrictions_div' style='width:98%' class=form>
	
	".Paragraphe_switch_img("{disable_vrfy_command}", "{disable_vrfy_command_text}","disable_vrfy_command-$t",$disable_vrfy_command,null,1400)."
	".Paragraphe_switch_img("{reject_unknown_client_hostname}", "{reject_unknown_client_hostname_text}","reject_unknown_client_hostname-$t",$reject_unknown_client_hostname,null,1400)."
	".Paragraphe_switch_img("{reject_unknown_reverse_client_hostname}", "{reject_unknown_reverse_client_hostname_text}","reject_unknown_reverse_client_hostname-$t",$reject_unknown_reverse_client_hostname,null,1400)."
	".Paragraphe_switch_img("{reject_unknown_sender_domain}", "{reject_unknown_sender_domain_text}","reject_unknown_sender_domain-$t",$reject_unknown_sender_domain,null,1400)."
	".Paragraphe_switch_img("{reject_invalid_hostname}", "{reject_invalid_hostname_text}","reject_invalid_hostname-$t",$reject_invalid_hostname,null,1400)."
	".Paragraphe_switch_img("{reject_non_fqdn_sender}", "{reject_non_fqdn_sender_text}","reject_non_fqdn_sender-$t",$reject_non_fqdn_sender,null,1400)."
	
			
	".Paragraphe_switch_img("{enforce_helo_restrictions}", "{enforce_helo_restrictions_text}","enforce_helo_restrictions-$t",$enforce_helo_restrictions,null,1400)."
	".Paragraphe_switch_img("{reject_forged_mails}", "{reject_forged_mails_text}","reject_forged_mails-$t",$reject_forged_mails,null,1400)."
	".Paragraphe_switch_img("{EnablePostfixAntispamPack}", "{EnablePostfixAntispamPack_text}","EnablePostfixAntispamPack-$t",$EnablePostfixAntispamPack_value,null,1400)."
	".Paragraphe_switch_img("{EnableGenericrDNSClients}", "{EnableGenericrDNSClients_text}","EnableGenericrDNSClients-$t",$EnableGenericrDNSClients,null,1400)."
	".Paragraphe_switch_img("{EnablePostfixInternalDomainsCheck}", "{EnablePostfixInternalDomainsCheck_text}","EnablePostfixInternalDomainsCheck-$t",$EnablePostfixInternalDomainsCheck,null,1400)."
	".Paragraphe_switch_img("{RestrictToInternalDomains}", "{RestrictToInternalDomains_text}","RestrictToInternalDomains-$t",$RestrictToInternalDomains,null,1400)."
			
	
						
	</table>
	</div>

	<div style='width:100%;text-align:right'><hr>
	". button("{apply}","Save$t()",45)."
	
	</div>
<script>
var xSave$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	Loadjs('postfix.clients_restrictions.progress.php');
}
	
function Save$t(){
	var XHR = new XHRConnection();
	
		XHR.appendData('reject_unknown_client_hostname',document.getElementById('reject_unknown_client_hostname-$t').value);
		XHR.appendData('reject_unknown_reverse_client_hostname',document.getElementById('reject_unknown_reverse_client_hostname-$t').value);
		XHR.appendData('reject_unknown_sender_domain',document.getElementById('reject_unknown_sender_domain-$t').value);
		XHR.appendData('reject_invalid_hostname',document.getElementById('reject_invalid_hostname-$t').value);
		XHR.appendData('reject_non_fqdn_sender',document.getElementById('reject_non_fqdn_sender-$t').value);
		XHR.appendData('EnablePostfixAntispamPack',document.getElementById('EnablePostfixAntispamPack-$t').value);
		XHR.appendData('reject_forged_mails',document.getElementById('reject_forged_mails-$t').value);
		XHR.appendData('EnableGenericrDNSClients',document.getElementById('EnableGenericrDNSClients-$t').value);
		XHR.appendData('EnablePostfixInternalDomainsCheck',document.getElementById('EnablePostfixInternalDomainsCheck-$t').value);
		XHR.appendData('RestrictToInternalDomains',document.getElementById('RestrictToInternalDomains-$t').value);
		XHR.appendData('disable_vrfy_command',document.getElementById('disable_vrfy_command-$t').value);
		XHR.appendData('enforce_helo_restrictions',document.getElementById('enforce_helo_restrictions-$t').value);
		
		XHR.sendAndLoad('$page', 'GET',xSave$t);	
	}
</script>			
	";


//smtpd_client_connection_rate_limit = 100
//smtpd_client_recipient_rate_limit = 20
	

$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html,"postfix.index.php");
	
	
	
}


function smtpd_client_restrictions_save(){
	$sock=new sockets();
	
	$sock->SET_INFO("enforce_helo_restrictions", $_GET["enforce_helo_restrictions"]);
	$sock->SET_INFO('reject_unknown_client_hostname',$_GET["reject_unknown_client_hostname"]);
	$sock->SET_INFO('reject_unknown_reverse_client_hostname',$_GET["reject_unknown_reverse_client_hostname"]);
	$sock->SET_INFO('reject_unknown_sender_domain',$_GET["reject_unknown_sender_domain"]);
	$sock->SET_INFO('reject_invalid_hostname',$_GET["reject_invalid_hostname"]);
	$sock->SET_INFO('reject_non_fqdn_sender',$_GET["reject_non_fqdn_sender"]);
	$sock->SET_INFO('EnablePostfixAntispamPack',$_GET["EnablePostfixAntispamPack"]);
	$sock->SET_INFO('reject_forged_mails',$_GET["reject_forged_mails"]);
	$sock->SET_INFO('EnableGenericrDNSClients',$_GET["EnableGenericrDNSClients"]);
	$sock->SET_INFO('EnablePostfixInternalDomainsCheck',$_GET["EnablePostfixInternalDomainsCheck"]);
	$sock->SET_INFO('RestrictToInternalDomains',$_GET["RestrictToInternalDomains"]);	
	$sock->SET_INFO('disable_vrfy_command',$_GET["disable_vrfy_command"]);
	$sock->getFrameWork("cmd.php?postfix-smtpd-restrictions=yes");
			
		
	
}




function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$users=new usersMenus();
	if(!$users->AsPostfixAdministrator){
		$error=$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}
	
	$title=$tpl->_ENGINE_parse_body('{smtpd_client_restrictions_icon}',"postfix.index.php");
	$title2=$tpl->_ENGINE_parse_body('{PostfixAutoBlockManageFW}',"postfix.index.php");
	$title_compile=$tpl->_ENGINE_parse_body('{PostfixAutoBlockCompileFW}',"postfix.index.php");
	
	$prefix="smtpd_client_restriction";
	$PostfixAutoBlockDenyAddWhiteList_explain=$tpl->_ENGINE_parse_body('{PostfixAutoBlockDenyAddWhiteList_explain}');
	$html="
var {$prefix}timerID  = null;
var {$prefix}tant=0;
var {$prefix}reste=0;

	function {$prefix}demarre(){
		{$prefix}tant = {$prefix}tant+1;
		{$prefix}reste=20-{$prefix}tant;
		if(!YahooWin4Open()){return false;}
		if ({$prefix}tant < 5 ) {                           
		{$prefix}timerID = setTimeout(\"{$prefix}demarre()\",1000);
	      } else {
				{$prefix}tant = 0;
				{$prefix}CheckProgress();
				{$prefix}demarre();                                
	   }
	}	
	
	
	function {$prefix}StartPostfixPopup(){
		YahooWin2(650,'$page?popup=yes','$title');
	}
	
var x_smtpd_client_restrictions_save= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	{$prefix}StartPostfixPopup();
}		
	

	
	function PostfixAutoBlockStartCompile(){
		{$prefix}CheckProgress();
		{$prefix}demarre();       
	}
	
	var x_{$prefix}CheckProgress= function (obj) {
		var tempvalue=obj.responseText;
		document.getElementById('PostfixAutoBlockCompileStatusCompile').innerHTML=tempvalue;
	}	
	
	function {$prefix}CheckProgress(){
			var XHR = new XHRConnection();
			XHR.appendData('compileCheck','yes');
			XHR.sendAndLoad('$page', 'GET',x_{$prefix}CheckProgress);
	
	}

	
	function PostfixIptablesSearchKey(e){
			if(checkEnter(e)){
				PostfixIptablesSearch();
			}
	}

		
".js_smtpd_client_restrictions_save()."
	
	
	{$prefix}StartPostfixPopup();
	";
	echo $html;
	}
	
function PostFixVerifyRights(){
	$usersmenus=new usersMenus();
	if($usersmenus->AsPostfixAdministrator){return true;}
	if($usersmenus->AsMessagingOrg){return true;}
	}	
?>