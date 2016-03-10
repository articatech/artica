<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');
	include_once('ressources/class.maincf.multi.inc');
	
	$tpl=new templates();
	if(!PostFixVerifyRights()){
			echo $tpl->javascript_parse_text("alert('{ERROR_NO_PRIVS}');");
			die();
		}	

if(isset($_GET["popup"])){smtpd_client_restrictions_popup();exit;}
if(isset($_GET["reject_unknown_client_hostname"])){smtpd_client_restrictions_save();exit;}



smtpd_client_restrictions_popup();


function smtpd_client_restrictions_popup(){
	
	$ou=$_GET["ou"];
	$sock=new sockets();
	$users=new usersMenus();
		
	
	$q=new mysql();
	if(!$q->TABLE_EXISTS("smptd_client_access", "artica_backup")){$q->check_storage_table(true);}
	
	$sql="SELECT `configuration` FROM smptd_client_access WHERE `ou`='$ou'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	
	if(!$q->ok){echo $q->mysql_error_html();die();}
	
	$MAIN=unserialize(base64_decode($ligne["configuration"]));
	
	
	
	
	$reject_unknown_client_hostname=$MAIN['reject_unknown_client_hostname'];
	$reject_unknown_reverse_client_hostname=$MAIN['reject_unknown_reverse_client_hostname'];
	$reject_unknown_sender_domain=$MAIN['reject_unknown_sender_domain'];
	$reject_invalid_hostname=$MAIN['reject_invalid_hostname'];
	$reject_non_fqdn_sender=$MAIN['reject_non_fqdn_sender'];
	$disable_vrfy_command=$MAIN['disable_vrfy_command'];
	$enforce_helo_restrictions=intval($MAIN['enforce_helo_restrictions']);
	
	if(!$users->POSTFIX_PCRE_COMPLIANCE){
		$EnableGenericrDNSClients=0;
		$EnableGenericrDNSClientsDisabled=1;
		$EnableGenericrDNSClientsDisabledText="<br><i><span style='color:#d32d2d;font-size:11px'>{EnableGenericrDNSClientsDisabledText}</span></i>";
	}
	
	$t=time();
	$page=CurrentPageName();
$html="



	
	<div style='font-size:30px;margin-bottom:50px'>{safety_standards}</div>
	<div class=explain style='font-size:18px'>{smtpd_client_restrictions_text}</div>
	<div id='smtpd_client_restrictions_div' style='width:98%' class=form>
	".Paragraphe_switch_img("{reject_unknown_client_hostname}", "{reject_unknown_client_hostname_text}","reject_unknown_client_hostname-$t",$reject_unknown_client_hostname,null,1400)."
	".Paragraphe_switch_img("{reject_unknown_reverse_client_hostname}", "{reject_unknown_reverse_client_hostname_text}","reject_unknown_reverse_client_hostname-$t",$reject_unknown_reverse_client_hostname,null,1400)."
	".Paragraphe_switch_img("{reject_unknown_sender_domain}", "{reject_unknown_sender_domain_text}","reject_unknown_sender_domain-$t",$reject_unknown_sender_domain,null,1400)."
	".Paragraphe_switch_img("{reject_invalid_hostname}", "{reject_invalid_hostname_text}","reject_invalid_hostname-$t",$reject_invalid_hostname,null,1400)."
	".Paragraphe_switch_img("{reject_non_fqdn_sender}", "{reject_non_fqdn_sender_text}","reject_non_fqdn_sender-$t",$reject_non_fqdn_sender,null,1400)."
	</table>
	</div>

	<div style='width:100%;text-align:right'><hr>
	". button("{apply}","Save$t()",45)."
	
	</div>
<script>
var xSave$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	
}
	
function Save$t(){
	var XHR = new XHRConnection();
		XHR.appendData('ou','$ou');
		XHR.appendData('reject_unknown_client_hostname',document.getElementById('reject_unknown_client_hostname-$t').value);
		XHR.appendData('reject_unknown_reverse_client_hostname',document.getElementById('reject_unknown_reverse_client_hostname-$t').value);
		XHR.appendData('reject_unknown_sender_domain',document.getElementById('reject_unknown_sender_domain-$t').value);
		XHR.appendData('reject_invalid_hostname',document.getElementById('reject_invalid_hostname-$t').value);
		XHR.appendData('reject_non_fqdn_sender',document.getElementById('reject_non_fqdn_sender-$t').value);
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
	$ou=$_POST["ou"];
	if($ou==null){$ou=$_SESSION["ou"];}
	if($ou==null){echo "Organization is null!\n";return;}
	$q=new mysql();
	while (list ($num, $ligne) = each ($_POST) ){
		$MAIN[$num]=$ligne;
	}
	$q->QUERY_SQL("DELETE FROM smptd_client_access WHERE `ou`='$ou'");
	$DATA=mysql_escape_string2(base64_decode(serialize($MAIN)));
	$q->QUERY_SQL("INSERT IGNORE INTO smptd_client_access (ou, configuration) VALUES ('$ou','$DATA')","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock->getFrameWork("postfix.php?smtpd-recipient-restrictions=yes");
			
		
	
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
	
		if($usersmenus->AsOrgPostfixAdministrator){return true;}
		if($usersmenus->AllowEditOuSecurity){return true;}
		if($usersmenus->AllowChangeDomains){return true;}
		if($usersmenus->AsMessagingOrg){return true;}
	}	

?>