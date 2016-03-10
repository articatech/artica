<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.iptables-chains.inc');
	include_once('ressources/class.resolv.conf.inc');
	

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["tab-parameters"])){popup_parameters();exit;}
if(isset($_GET["tab-iptables-rules"])){firewall_popup();exit;}
if(isset($_GET["tab-iptables-events"])){firewall_events();exit;}
if(isset($_GET["InstantIpTablesInLeftMenu"])){InstantIpTablesInLeftMenu();exit;}
if(isset($_GET["tab-iptables-stats"])){InstantIptablesStats();exit;}


if(isset($_GET["instantIptables-status"])){status_mysql();exit;}

if(isset($_GET["ban-servers"])){ban_servers_js();exit;}
if(isset($_GET["ban-servers-popup"])){ban_servers_popup();exit;}
if(isset($_POST["ban-servers-add"])){ban_servers_save();exit;}

if(isset($_GET["EnablePostfixAutoBlock"])){save();exit;}
if(isset($_GET["BlockDenyAddWhiteList"])){echo BlockDenyWhiteList();exit;}
if(isset($_GET["AutoBlockDenyAddWhiteList"])){AutoBlockDenyAddWhiteList();exit;}
if(isset($_GET["PostfixAutoBlockDenyDelWhiteList"])){PostfixAutoBlockDenyDelWhiteList();exit;}

if(isset($_GET["PostfixAutoBlockLoadFW"])){firewall_popup();exit;}
if(isset($_GET["PostfixAutoBlockLoadFWRules"])){echo firewall_rules();exit;}
if(isset($_GET["PostfixEnableFwRule"])){PostfixEnableFwRule();exit;}
if(isset($_GET["PostfixEnableLog"])){PostfixEnableLog();exit;}
if(isset($_GET["compile"])){PostfixAutoBlockCompile();exit;}
if(isset($_GET["compileCheck"])){PostfixAutoBlockCompileCheck();exit;}
if(isset($_GET["DeleteSMTPIptableRule"])){firewall_delete_rule();exit;}
if(isset($_GET["popup-white"])){popup_white();exit;}
if(isset($_GET["DeleteSMTPAllIptableRules"])){firewall_delete_all_rules();exit;}
if(isset($_GET["PostfixAutoBlockParameters"])){popup_parameters();exit;}
if(isset($_GET["PostfixAutoBlockParametersSave"])){popup_parameters_save();exit;}
if(isset($_GET["DeleteAllIpTablesRules"])){DeleteAllIpTablesRules();exit;}
if(isset($_GET["InstantIptablesEventAll"])){InstantIptablesEventAll();exit;}
if(isset($_GET["EventDisableIpTables"])){EventDisableIpTables();exit;}
if(isset($_GET["firewall-rules-list"])){firewall_rules();exit;}
if(isset($_GET["firewall-rules-list-search"])){firewall_rules_search();exit;}



if(isset($_GET["CompileSSHDRules"])){CompileSSHDRules();exit;}
js();


function InstantIpTablesInLeftMenu(){
	$sock=new sockets();
	$sock->SET_INFO("InstantIpTablesInLeftMenu",$_GET["InstantIpTablesInLeftMenu"]);
}

function firewall_delete_rule(){
	$users=new usersMenus();
	$tpl=new templates();
	if(!$users->AsPostfixAdministrator){
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "$error";
		die();
	}

	$iptables_chains=new iptables_chains();
	if(!$iptables_chains->deletePostfix_chain($_GET["DeleteSMTPIptableRule"])){
		echo $iptables_chains->error;
		return false;
	}
	
	unset($_SESSION["postfix_firewall_rules"]);
	$tpl=new templates();
	echo html_entity_decode($tpl->_ENGINE_parse_body("{success}\n{delete_not_forget_to_compile}"));
	
}

function firewall_delete_all_rules(){
$users=new usersMenus();
$tpl=new templates();
	if(!$users->AsPostfixAdministrator){
		$error=html_entity_decode($tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}"));
		echo "$error";
		die();
	}	
	
	$iptables_chains=new iptables_chains();
	
	if(!$iptables_chains->deleteAllPostfix_chains()){
			echo $iptables_chains->error;
			return false;
		}
	
	unset($_SESSION["postfix_firewall_rules"]);
	$tpl=new templates();
	echo html_entity_decode($tpl->_ENGINE_parse_body("{success}\n{delete_not_forget_to_compile}"));
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
	
	$title=$tpl->_ENGINE_parse_body('{postfix_autoblock}',"postfix.index.php");
	$title2=$tpl->_ENGINE_parse_body('{PostfixAutoBlockManageFW}',"postfix.index.php");
	$title_compile=$tpl->_ENGINE_parse_body('{PostfixAutoBlockCompileFW}',"postfix.index.php");
	$normal_start_js="YahooWin2(923,'$page?popup=yes','$title');";
	$PostfixAutoBlockParameters=$tpl->_ENGINE_parse_body("{PostfixAutoBlockParameters}");
	
	if(isset($_GET["in-front-ajax"])){$normal_start_js="LoadAjaxRound('postfix-instant-iptables','$page?popup=yes');";}
	
	
	
if(isset($_GET["white-js"])){
		$normal_start_js="YahooWin3(923,'$page?popup-white=yes','$title');";
	}
	
	$prefix="PostfixAutoBlockjs";
	$PostfixAutoBlockDenyAddWhiteList_explain=$tpl->_ENGINE_parse_body('{PostfixAutoBlockDenyAddWhiteList_explain}');
	$empty_table_confirm=html_entity_decode($tpl->_ENGINE_parse_body('{empty_table_confirm}'));
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
	
	
	function StartPostfixAutoBlockDeny(){
		$normal_start_js
	}
	
	function PostfixAutoBlockLoadFW(){
		YahooWin3(717,'$page?PostfixAutoBlockLoadFW=yes','$title2');
	}
	
	function PostfixAutoBlockCompileFW(){
		YahooWin4(500,'$page?compile=yes','$title_compile');
		setTimeout('PostfixAutoBlockStartCompile()',1000);		
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

	
	
	
	
var x_EnablePostfixAutoBlock= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	RefreshTab('instant_iptables_tabs');
}
	
	function EnablePostfixAutoBlockDeny(){
		var EnablePostfixAutoBlock=document.getElementById('EnablePostfixAutoBlock').value;
		var EnablePostfixAutoBlockWhiteListed=document.getElementById('EnablePostfixAutoBlockWhiteListed').value;
		var XHR = new XHRConnection();
		XHR.appendData('EnablePostfixAutoBlock',EnablePostfixAutoBlock);
		XHR.appendData('EnablePostfixAutoBlockWhiteListed',EnablePostfixAutoBlockWhiteListed);
		XHR.sendAndLoad('$page', 'GET',x_EnablePostfixAutoBlock);	
	
	}
	
var x_AutoBlockDenyAddWhiteList= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	LoadAjax('BlockDenyAddWhiteList','$page?BlockDenyAddWhiteList=yes');
}
	
	function PostfixAutoBlockDenyAddWhiteList(){
		var server=prompt('$PostfixAutoBlockDenyAddWhiteList_explain');
		if(server){
			var XHR = new XHRConnection();
			XHR.appendData('AutoBlockDenyAddWhiteList',server);
			XHR.sendAndLoad('$page', 'GET',x_AutoBlockDenyAddWhiteList);
		}
	}
	
	function PostfixAutoBlockDenyDelWhiteList(server){
		var XHR = new XHRConnection();
		XHR.appendData('PostfixAutoBlockDenyDelWhiteList',server);
		XHR.sendAndLoad('$page', 'GET',x_AutoBlockDenyAddWhiteList);
	
	}
	
	function PostfixAutoBlockParameters(){
		var XHR = new XHRConnection();
		XHR.appendData('PostfixAutoBlockDays',document.getElementById('PostfixAutoBlockDays').value);
		XHR.appendData('PostfixAutoBlockEvents',document.getElementById('PostfixAutoBlockEvents').value);
		XHR.appendData('PostfixAutoBlockPeriod',document.getElementById('PostfixAutoBlockPeriod').value);
		document.getElementById('PostfixAutoBlockParameters').innerHTML='<center style=\"width:100%\"><img src=img/wait_verybig.gif></center>';
		XHR.sendAndLoad('$page', 'GET',x_EnablePostfixAutoBlock);
	
	}	
	

		
	function DeleteAllIptablesPostfixRules(){
		if(confirm('$empty_table_confirm')){
			var XHR = new XHRConnection();
			XHR.appendData('DeleteSMTPAllIptableRules','yes');
			document.getElementById('iptables_postfix_rules').innerHTML='<center style=\"width:100%\"><img src=img/wait_verybig.gif></center>';
			XHR.sendAndLoad('$page', 'GET',x_PostfixIptableDelete);		
		}
	}
	
	

		
var x_PostfixEnableFwRule= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
	}		
		
			
	function PostfixEnableLog(ID){
		var XHR = new XHRConnection();
		XHR.appendData('ID',ID)
		if(document.getElementById('log_'+ID).checked){XHR.appendData('PostfixEnableLog',1);}else{XHR.appendData('PostfixEnableLog',0);}
		XHR.sendAndLoad('$page', 'GET',x_PostfixEnableFwRule);	
	}
	
	function FirewallDisableSMTPRUle(ID){
		var XHR = new XHRConnection();
		XHR.appendData('ID',ID);
		if(document.getElementById('enabled_'+ID).checked){XHR.appendData('PostfixEnableFwRule',0);}else{XHR.appendData('PostfixEnableFwRule',1);}
		XHR.sendAndLoad('$page', 'GET',x_PostfixEnableFwRule);
	}
	
	function PostfixAutoBlockParameters(){
		YahooWin4('550','$page?PostfixAutoBlockParameters=yes','$PostfixAutoBlockParameters');
	
	}
	
	
	StartPostfixAutoBlockDeny();
	";
	echo $html;
	}
	
function popup(){
	$users=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$EnablePostfixAutoBlockWhiteListed=intval($sock->GET_INFO("EnablePostfixAutoBlockWhiteListed"));
	
	
	if(!$users->AsPostfixAdministrator){
		$error=$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
		echo "<H3>$error<H3>";
		die();
	}
	
	$array["status"]='{status}';
	if($EnablePostfixAutoBlockWhiteListed==0){
		$array["tab-parameters"]='{PostfixAutoBlockParameters}';
		$array["tab-iptables-rules"]='{PostfixAutoBlockManageFW}';
	}
	
	if($EnablePostfixAutoBlockWhiteListed==0){
		$array["tab-iptables-stats"]='{statistics}';
		
	}
$array["tab-iptables-events"]='{events}';
	
	$fontsize=22;
	while (list ($num, $ligne) = each ($array) ){
		if($num=="tab-iptables-whlhosts"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"whitelists.admin.php?popup-hosts=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}
		
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
	}
	
	
	echo build_artica_tabs($html, "instant_iptables_tabs");
	
	
	
}

function status_mysql(){
	
	$q=new mysql();
	$sql="SELECT COUNT(*) as tcount FROM iptables WHERE local_port=25 AND flux='INPUT' AND community=1";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	$count_artica=$ligne["tcount"];
	$sql="SELECT COUNT(*) as tcount FROM iptables WHERE local_port=25 AND flux='INPUT' AND community IS NULL";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));	
	$count_local=$ligne["tcount"];	
	
	$html="
	<table style='width:99%' class=form>
	<tr>
		<td class=legend width=99%>{updated_from_community}:</td>
		<td width=1% nowrap><strong style='font-size:22px'>$count_artica {rules}</td>
	</tr>
	<tr>
		<td class=legend width=99%>{local}:</td>
		<td width=1% nowrap><strong style='font-size:22px'>$count_local {rules}</td>
	</tr>	
	</table>
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
	
}
	
	
function status(){
	$users=new usersMenus();
	$tpl=new templates();
	$DeleteAllipTablesRules_text=$tpl->javascript_parse_text("{DeleteAllipTablesRules_text}");
	$page=CurrentPageName();
	if(!$users->AsPostfixAdministrator){
		$error=$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
		echo "<H3>$error<H3>";
		die();
	}
	
	
	for($i=0;$i<91;$i++){
		$arr_day[$i]=$i;
		
	}
	
	
	
	$sock=new sockets();
	$EnablePostfixAutoBlock=$sock->GET_INFO("EnablePostfixAutoBlock");
	if(!is_numeric($EnablePostfixAutoBlock)){$EnablePostfixAutoBlock=1;}
	$InstantIpTablesInLeftMenu=$sock->GET_INFO("InstantIpTablesInLeftMenu");
	if($InstantIpTablesInLeftMenu==null){$InstantIpTablesInLeftMenu=1;}	
	$InstantIptablesEventAll=$sock->GET_INFO("InstantIptablesEventAll");
	if(!is_numeric($InstantIptablesEventAll)){$InstantIptablesEventAll=1;}
	$EnablePostfixAutoBlockWhiteListed=intval($sock->GET_INFO("EnablePostfixAutoBlockWhiteListed"));
	
	
	
	$form=Paragraphe_switch_img("{enable_postfix_autoblock}",
	"{enable_postfix_autoblock_text}<br>{postfix_autoblock_explain}",'EnablePostfixAutoBlock',$EnablePostfixAutoBlock,
			"{enable_disable}",1060);
	
	
	
	
	$form1=Paragraphe_switch_img("{white_listed_mode}",
			"{instant_iptables_whitelisted_explain}<br>{enable_white_listed_mode_text}",'EnablePostfixAutoBlockWhiteListed',$EnablePostfixAutoBlockWhiteListed,"{enable_disable}",1060);	
	
    $form="
    <div id='EnablePostfixAutoBlockDiv' class=form style='width:98%'>
			
			
		<table style='width:100%' >
		<tr>
			<td colspan=2>$form</td>
		</tr>
		<tr>
			<td colspan=2>$form1</td>
		</tr>		
		<tr>
			<td class=legend style=font-size:22px>{log_all_events}:</td>
			<td>". Field_checkbox_design("InstantIptablesEventAll",1,$InstantIptablesEventAll,"InstantIptablesEventAllSave()")."</td>
		</tr>	
		<tr>
			<td colspan=2 align='right'><hr>". button("{apply}","EnablePostfixAutoBlockDeny()",40)."</td>
		</tr>			
		</table>
	</div>";
	

	$PostfixAutoBlockDenyAddWhiteList=$tpl->_ENGINE_parse_body("{PostfixAutoBlockDenyAddWhiteList}","postfix.index.php");
	$add_whitelist=ParagrapheTEXT("32-bind9-add-zone.png","$PostfixAutoBlockDenyAddWhiteList","{PostfixAutoBlockDenyAddWhiteList_explain}",
	"javascript:PostfixAutoBlockDenyAddWhiteList();",null,337);
	
	$manage_fw=ParagrapheTEXT("folder-64-firewall.png","{PostfixAutoBlockManageFW}","{PostfixAutoBlockManageFW_text}",
	"javascript:PostfixAutoBlockLoadFW();");
	
	$compile=ParagrapheTEXT("system-32.png","{PostfixAutoBlockCompileFW}","{PostfixAutoBlockCompileFW_text}",
	"javascript:PostfixAutoBlockCompileFW();",null,337);
	
	$parameters=ParagrapheTEXT("32-parameters.png","{PostfixAutoBlockParameters}","{PostfixAutoBlockParameters_text}",
	"javascript:PostfixAutoBlockParameters();");	
	
	$addbann=ParagrapheTEXT("32-bann-server-auto.png","{bann_smtp_servers}","{bann_smtp_servers_text}",
	"javascript:Loadjs('postfix.iptables.php?ban-servers=yes')",null,337);		
	
	
	$deletallrules=ParagrapheTEXT("firewall-delete-32.png","{delete_all_rules}","{delete_all_rules_iptables_text}",
"javascript:DeleteAllRules()",null,337);		
	

	if($EnablePostfixAutoBlockWhiteListed==1){
		$addbann=ParagrapheTEXT_disabled("32-bann-server-auto.png","{bann_smtp_servers}","{bann_smtp_servers_text}");
		
	}
	
	
	
	//$parameters
	//$manage_fw
	
	$html="
		<table style='width:100%;'>
	<tr>
		<td valign='top'>
		<center><img src='img/bg_firewall.jpg'></center>
		<div style='width:100%;clear:left'>
			<div >$compile</div>
			<div >$add_whitelist</div>
			<div >$addbann</div>
			
		</div>				
		</td>
		<td valign='top'>
	
	<table style='width:100%'>
	<tr>
		<td valign='top'>$form<hr></td>
	</tr>
	<tr>
	<td valign='top'>
		
		<div id='instantIptables-status'></div>
	</td>
	</table>
</td>
</tr>
</table>

<script>
	var x_InstantIpTablesInLeftMenuSave= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		CacheOff();
	}	

	var x_DeleteAllRules= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		
	}	
		
	function InstantIpTablesInLeftMenuSave(){
		var XHR = new XHRConnection();
		if(document.getElementById('InstantIpTablesInLeftMenu').checked){XHR.appendData('InstantIpTablesInLeftMenu','1');}else{XHR.appendData('InstantIpTablesInLeftMenu','0');}
		XHR.sendAndLoad('$page', 'GET',x_InstantIpTablesInLeftMenuSave);	
	}	
	
	function InstantIptablesEventAllSave(){
		var XHR = new XHRConnection();
		if(document.getElementById('InstantIptablesEventAll').checked){XHR.appendData('InstantIptablesEventAll','1');}else{XHR.appendData('InstantIptablesEventAll','0');}
		XHR.sendAndLoad('$page', 'GET',x_DeleteAllRules);		
	}
	
	function DeleteAllRules(){
		if(confirm('$DeleteAllipTablesRules_text')){
			var XHR = new XHRConnection();
			XHR.appendData('DeleteAllIpTablesRules','yes');
			XHR.sendAndLoad('$page', 'GET',x_DeleteAllRules);	
		}
	
	}
	
	LoadAjax('instantIptables-status','$page?instantIptables-status=yes');
	
</script>
	";
	
	
	
	echo $tpl->_ENGINE_parse_body($html,"postfix.index.php");
}

function popup_parameters(){
	
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("PostfixAutoBlockParameters")));
	$page=CurrentPageName();
	
if($array["NAME_SERVICE_NOT_KNOWN"]==null){$array["NAME_SERVICE_NOT_KNOWN"]=10;}
if($array["SASL_LOGIN"]==null){$array["SASL_LOGIN"]=15;}
if($array["RBL"]==null){$array["RBL"]=5;}
if($array["USER_UNKNOWN"]==null){$array["USER_UNKNOWN"]=10;}
if($array["BLOCKED_SPAM"]==null){$array["BLOCKED_SPAM"]=5;}
if($array["SMTPHACK_TIMEOUT"]==null){$array["SMTPHACK_TIMEOUT"]=10;}
if($array["SMTPHACK_RESOLUTION_FAILURE"]==null){$array["SMTPHACK_RESOLUTION_FAILURE"]=2;}
if($array["SMTPHACK_TOO_MANY_ERRORS"]==null){$array["SMTPHACK_TOO_MANY_ERRORS"]=10;}

	$PostfixInstantIptablesLastDays=$sock->GET_INFO("PostfixInstantIptablesLastDays");
	$PostfixInstantIptablesMaxEvents=$sock->GET_INFO("PostfixInstantIptablesMaxEvents");
	if(!is_numeric($PostfixInstantIptablesLastDays)){$PostfixInstantIptablesLastDays=7;}
	if(!is_numeric($PostfixInstantIptablesMaxEvents)){$PostfixInstantIptablesMaxEvents=50;}

$html="
<div class=explain style='font-size:16px'>{PostfixAutoBlockParameters_text}<br>{SMTP_HACK_HOWTO}</div>

<div id='PostfixAutoBlockParameters_id'>
<table style='width:99%' class=form>
<tr>
	<td class=legend style='font-size:22px'>{SMTPHACK_NAME_SERVICE_NOT_KNOWN}:</td>
	<td>". Field_text("NAME_SERVICE_NOT_KNOWN",$array["NAME_SERVICE_NOT_KNOWN"],"font-size:22px;padding:3px;width:110px")."</td>
</tr>
<tr>
	<td class=legend style='font-size:22px'>{SMTPHACK_RESOLUTION_FAILURE}:</td>
	<td>". Field_text("SMTPHACK_RESOLUTION_FAILURE",$array["SMTPHACK_RESOLUTION_FAILURE"],"font-size:22px;padding:3px;width:110px")."</td>
</tr>
<tr>
	<td class=legend style='font-size:22px'>{SMTPHACK_TIMEOUTS}:</td>
	<td>". Field_text("SMTPHACK_TIMEOUT",$array["SMTPHACK_TIMEOUT"],"font-size:22px;padding:3px;width:110px")."</td>
</tr>
<tr>
	<td class=legend style='font-size:22px'>{SMTPHACK_TOO_MANY_ERRORS}:</td>
	<td>". Field_text("SMTPHACK_TOO_MANY_ERRORS",$array["SMTPHACK_TOO_MANY_ERRORS"],"font-size:22px;padding:3px;width:110px")."</td>
</tr>

<tr>
	<td class=legend style='font-size:22px'>{SMTPHACK_SASL_LOGIN}:</td>
	<td>". Field_text("SASL_LOGIN",$array["SASL_LOGIN"],"font-size:22px;padding:3px;width:110px")."</td>
</tr>
<tr>
	<td class=legend style='font-size:22px'>{SMTPHACK_RBL}:</td>
	<td>". Field_text("RBL",$array["RBL"],"font-size:22px;padding:3px;width:110px")."</td>
</tr>
<tr>
	<td class=legend style='font-size:22px'>{SMTPHACK_USER_UNKNOWN}:</td>
	<td>". Field_text("USER_UNKNOWN",$array["USER_UNKNOWN"],"font-size:22px;padding:3px;width:110px")."</td>
</tr>
<tr>
	<td class=legend style='font-size:22px'>{SMTPHACK_BLOCKED_SPAM}:</td>
	<td>". Field_text("BLOCKED_SPAM",$array["BLOCKED_SPAM"],"font-size:22px;padding:3px;width:110px")."</td>
</tr>
<tr>
	<td colspan=2 style='font-size:22px' align='right'><br><strong>{synthesis}:</strong><hr></td>
</tr>
<tr>
	<td class=legend style='font-size:22px'>{days}:</td>
	<td>". Field_text("PostfixInstantIptablesLastDays",$PostfixInstantIptablesLastDays,"font-size:22px;padding:3px;width:110px")."</td>
</tr>
<tr>
	<td class=legend style='font-size:22px'>{events}:</td>
	<td>". Field_text("PostfixInstantIptablesMaxEvents",$PostfixInstantIptablesMaxEvents,"font-size:22px;padding:3px;width:110px")."</td>
</tr>

<tr>
	<td colspan=2 align='right'><hr>". button("{apply}","PostfixAutoBlockParametersSave()",40)."</td>
</tr>
</table>
</div>
<script>
	var x_PostfixAutoBlockParametersSave= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			RefreshTab('instant_iptables_tabs');
	}			
		
function PostfixAutoBlockParametersSave(){
	var XHR = new XHRConnection();
	XHR.appendData('PostfixAutoBlockParametersSave','yes');
	XHR.appendData('NAME_SERVICE_NOT_KNOWN',document.getElementById('NAME_SERVICE_NOT_KNOWN').value);
	XHR.appendData('SMTPHACK_RESOLUTION_FAILURE',document.getElementById('SMTPHACK_RESOLUTION_FAILURE').value);
	XHR.appendData('SASL_LOGIN',document.getElementById('SASL_LOGIN').value);
	XHR.appendData('RBL',document.getElementById('RBL').value);	
	XHR.appendData('USER_UNKNOWN',document.getElementById('USER_UNKNOWN').value);
	XHR.appendData('BLOCKED_SPAM',document.getElementById('BLOCKED_SPAM').value);
	XHR.appendData('SMTPHACK_TIMEOUT',document.getElementById('SMTPHACK_TIMEOUT').value);
	XHR.appendData('SMTPHACK_TOO_MANY_ERRORS',document.getElementById('SMTPHACK_TOO_MANY_ERRORS').value);
	XHR.appendData('PostfixInstantIptablesLastDays',document.getElementById('PostfixInstantIptablesLastDays').value);
	XHR.appendData('PostfixInstantIptablesMaxEvents',document.getElementById('PostfixInstantIptablesMaxEvents').value);
	document.getElementById('PostfixAutoBlockParameters_id').innerHTML='<center style=\"margin:20px;padding:20px\"><img src=\"img/wait_verybig.gif\"></center>';
	XHR.sendAndLoad('$page', 'GET',x_PostfixAutoBlockParametersSave);	
	}
</script>

";

		$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}

function popup_parameters_save(){
	$sock=new sockets();
	$datas=base64_encode(serialize($_GET));
	$sock->SET_INFO("PostfixInstantIptablesLastDays",$_GET["PostfixInstantIptablesLastDays"]);
	$sock->SET_INFO("PostfixInstantIptablesMaxEvents",$_GET["PostfixInstantIptablesMaxEvents"]);
	$sock->SaveConfigFile($datas,"PostfixAutoBlockParameters");
	
	
	$sock->getFrameWork("cmd.php?smtp-hack-reconfigure=yes");
	
}


function popup_white(){
	
	$tpl=new templates();
	$PostfixAutoBlockDenyAddWhiteList=$tpl->_ENGINE_parse_body("{PostfixAutoBlockDenyAddWhiteList}","postfix.index.php");
	
		$add_whitelist=Paragraphe("64-bind9-add-zone.png","$PostfixAutoBlockDenyAddWhiteList","{PostfixAutoBlockDenyAddWhiteList_explain}",
		"javascript:PostfixAutoBlockDenyAddWhiteList();");
	
	$html="<H1>{PostfixAutoBlockDenyAddWhiteList}</H1>
	<table style='width:100%'>
	<tr>
	<td valign='top'>
	". RoundedLightWhite("<div style='width:100%;height:300px;overflow:auto' id='BlockDenyAddWhiteList'>".BlockDenyWhiteList()."</div>")."	
		
	</td>
	<td valign='top' width=2%>
	$add_whitelist
	</td>
	</tr>
	</table>
	
	";
	
	
	
	echo $tpl->_ENGINE_parse_body($html,"postfix.index.php");		
	
}

function firewall_popup(){
	unset($_SESSION["postfix_firewall_rules"]);
	$users=new usersMenus();
	$tpl=new templates();
	if(!$users->AsPostfixAdministrator){
		$error=$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
		echo "<H3>$error<H3>";
		die();
	}
	if(isset($_GET["sshd"])){$porttoadd="&sshd=yes";}$page=CurrentPageName();
	
	
$html="
<div id='iptables_postfix_rules'></div>
<hr>

<script>
	function PostfixIptablesSearchKey(e){
			if(checkEnter(e)){
				PostfixIptablesSearch();
			}
	}
	
	function PostfixIptablesSearch(){
		var pattern=document.getElementById('search_fw').value;
		LoadAjax('iptables_postfix_rules','$page?PostfixAutoBlockLoadFWRules=yes$porttoadd&search='+pattern);
		}
		
	var x_CompileSSHDRules= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
	}		
		

	function CompileSSHDRules(){
		var XHR = new XHRConnection();
		XHR.appendData('CompileSSHDRules','yes');
		XHR.sendAndLoad('$page', 'GET',x_CompileSSHDRules);
	}			



	LoadAjax('iptables_postfix_rules','$page?firewall-rules-list=yes$porttoadd');
</script>
";
	
//empty_table_confirm
echo $tpl->_ENGINE_parse_body($html,"postfix.index.php");
	
}

function firewall_rules(){
	$q=new mysql();
	$page=CurrentPageName();
	$tpl=new templates();
	$compile=$tpl->_ENGINE_parse_body("{compile}");	
	$port=25;
	if(isset($_GET["sshd"])){
		$port=22;
		$pToAdd="XHR.appendData('sshd','yes');";
		$complilessh="{name: '$compile', bclass: 'Down', onpress : CompileSSHDRules},";
		
		}

	$t=time();
	$date=$tpl->_ENGINE_parse_body("{date}");
	$server=$tpl->_ENGINE_parse_body("{server}");
	$add=$tpl->_ENGINE_parse_body("{add}");
	$add_websites=$tpl->_ENGINE_parse_body("{add}");
	$verify=$tpl->_ENGINE_parse_body("{analyze}");
	$log=$tpl->_ENGINE_parse_body("{log}");
	$enable=$tpl->_ENGINE_parse_body("{enable}");
	$import=$tpl->_ENGINE_parse_body("{import}");
	$delete_all=$tpl->_ENGINE_parse_body("{delete_all_items}");
	$import_catz_art_expl=$tpl->javascript_parse_text("{import_catz_art_expl}");
	$empty_table_confirm=$tpl->javascript_parse_text('{empty_table_confirm}');

	

	$buttons="
	buttons : [
	{name: '$delete_all', bclass: 'Delz', onpress : DeleteAllIptablesPostfixRules$t},
	$complilessh
	
	],";	
	
	$html="
<table class='$t' style='display: none' id='$t' style='width:100%'></table>
<script>
var xsite='';
$(document).ready(function(){
$('#$t').flexigrid({
	url: '$page?firewall-rules-list-search=yes&port=$port',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'ID', width : 31, sortable : true, align: 'center'},	
		{display: '$server', name : 'server', width : 651, sortable : false, align: 'left'},
		{display: '$enable', name : 'enable', width : 31, sortable : false, align: 'center'},
		{display: 'LOG', name : 'log', width : 31, sortable : false, align: 'center'},
		{display: 'DEL', name : 'Del', width : 31, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
		{display: '$server', name : 'server'},
		],
	sortname: 'ID',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

	var x_PostfixIptableDelete= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}	
		$('#$t').flexReload();
	}	
	
	function PostfixIptableDelete(key){
		var XHR = new XHRConnection();
		XHR.appendData('DeleteSMTPIptableRule',key);
		$pToAdd
		XHR.sendAndLoad('$page', 'GET',x_PostfixIptableDelete);
		}
		
	var x_PostfixEnableFwRule= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
	}		
		

	function FirewallDisableSMTPRUle(ID){
		var XHR = new XHRConnection();
		XHR.appendData('ID',ID);
		if(document.getElementById('enabled_'+ID).checked){XHR.appendData('PostfixEnableFwRule',0);}else{XHR.appendData('PostfixEnableFwRule',1);}
		XHR.sendAndLoad('$page', 'GET',x_PostfixEnableFwRule);
	}	
	
	function DeleteAllIptablesPostfixRules$t(){
		if(confirm('$empty_table_confirm')){
			var XHR = new XHRConnection();
			XHR.appendData('DeleteSMTPAllIptableRules','yes');
			XHR.sendAndLoad('$page', 'GET',x_PostfixIptableDelete);		
		}
	}
		
	
</script>

";
		echo $html;	
}

function firewall_rules_search(){
	$search='%';
	$page=1;	
	$port=$_GET["port"];
	$q=new mysql();
	$tpl=new templates();
	
	$_GET["search"]=$_POST["query"];
	if($_GET["search"]<>null){
		$_GET["search"]=$_GET["search"]."*";
		$_GET["search"]=str_replace("**","*",$_GET["search"]);
		$_GET["search"]=str_replace("*","%",$_GET["search"]);
		if(preg_match("#([a-zA-Z]+)#",$_GET["search"])){
			$sql_search="AND servername LIKE '{$_GET["search"]}' ";
		}else{
			$sql_search="AND serverip LIKE '{$_GET["search"]}' ";
		}
	}

	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	


	if($sql_search<>null){
	
		
		$sql="SELECT COUNT(*) AS TCOUNT FROM iptables WHERE local_port=$port AND flux='INPUT'{$sql_search}";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) AS tcount FROM iptables WHERE local_port=$port AND flux='INPUT'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	
	$sql="SELECT * FROM iptables WHERE local_port=$port AND flux='INPUT' {$sql_search} $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){json_error_show($q->mysql_error,1);}
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = mysql_num_rows($results);
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("No data",1);}
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
				
		if($ligne["servername"]==null){$ligne["servername"]=$ligne["serverip"];}
		
		$disable=Field_checkbox("enabled_{$ligne["ID"]}",0,$ligne["disable"],"FirewallDisableSMTPRUle('{$ligne["ID"]}')");
		$log=Field_checkbox("log_{$ligne["ID"]}",1,$ligne["log"],"PostfixEnableLog('{$ligne["ID"]}')");
		$delete=imgsimple("delete-32.png","{delete}","PostfixIptableDelete('{$ligne["rulemd5"]}')");
		$ligne["events_block"]="<div style=font-size:14px;margin:0px;padding:0px;>{$ligne["events_block"]}</div>";
		$icon="datasource-32.png";
		if($ligne["community"]==1){
			$icon="connect-32.png";
			$delete="<img src='img/delete-32-grey.png'>";
			$tooltip_add="<strong style=font-size:14px>{updated_from_community}</strong><br>";
		}
		$subtext="<div style='font-size:10px;margin:0px;padding:0px;'><i><span style='color:#660002;font-weight:bold'>{$ligne["serverip"]}</span> {added_on} {$ligne["saved_date"]}</i></div>";
		$ligne["events_block"]=$tpl->_ENGINE_parse_body($ligne["events_block"]);
		$tooltip_add=$tpl->_ENGINE_parse_body($tooltip_add);
		$subtext=$tpl->_ENGINE_parse_body($subtext);
		
	$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => array(
		"<img src='img/$icon'>",
		"<strong style='font-size:22px'><code>{$ligne["servername"]}</code></strong>
		<div style='font-size:10px;;margin:0px;padding:0px;'>$tooltip_add{$ligne["serverip"]}<div style='font-size:10px;;margin:0px;padding:0px;'>{$ligne["events_block"]}</div></div>$subtext",
		$disable,$log,
		$delete)
		);		
		

	}	
	echo json_encode($data);	
	
}


function firewall_rules_pold(){
	$q=new mysql();
	$page=CurrentPageName();
	$port=25;
	if(isset($_GET["sshd"])){
		$port=22;$pToAdd="XHR.appendData('sshd','yes');";
		}
	
	
	
	

	
	$sql_count="SELECT COUNT(*) AS tcount FROM iptables WHERE local_port=$port AND flux='INPUT'{$sql_search}";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql_count,"artica_backup"));
	$max=$ligne["tcount"];
	$limit=0;	
	
	
	$sql="SELECT * FROM iptables WHERE local_port=$port AND flux='INPUT' {$sql_search}ORDER BY ID DESC LIMIT $limit,200";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q=new mysql();	
	$results=$q->QUERY_SQL($sql,"artica_backup");
	
	
	$html="
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th>&nbsp;</th>
		<th>{server}</th>
		<th>{enable}</th>
		<th>{log}</th>
		<th>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>";		
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		if($ligne["servername"]==null){$ligne["servername"]=$ligne["serverip"];}
		
		$disable=Field_checkbox("enabled_{$ligne["ID"]}",0,$ligne["disable"],"FirewallDisableSMTPRUle('{$ligne["ID"]}')");
		$log=Field_checkbox("log_{$ligne["ID"]}",1,$ligne["log"],"PostfixEnableLog('{$ligne["ID"]}')");
		$delete=imgtootltip("delete-32.png","{delete}","PostfixIptableDelete('{$ligne["rulemd5"]}')");
		$ligne["events_block"]="<div style=font-size:14px>".nl2br($ligne["events_block"])."</div>";
		$icon="datasource-32.png";
		if($ligne["community"]==1){
			$icon="connect-32.png";
			$delete="<img src='img/delete-32-grey.png'>";
			$tooltip_add="<strong style=font-size:14px>{updated_from_community}</strong><br>";
		}
		$subtext="<div style='font-size:10px'><i><span style='color:#660002;font-weight:bold'>{$ligne["serverip"]}</span> {added_on} {$ligne["saved_date"]}</i></div>";
		
		
		$html=$html . "
		<tr  class=$classtr>
		<td width=1%><img src='img/$icon'></td>
		<td><strong style='font-size:22px'><code>". texttooltip("{$ligne["servername"]}","$tooltip_add{$ligne["serverip"]}<hr>{$ligne["events_block"]}",null,null,0,"font-size:14px")."</strong></code>$subtext</td>
		<td width=1%>$disable</td>
		<td width=1%>$log</td>
		<td width=1%>$delete</td>
		</td>
		</tr>";
		
	}
	$html=$html."</tbody>
	
	</table>
	
	<script>
	

	</script>
	
	
	";
	
		$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}


function save(){
	$sock=new sockets();
	$sock->SET_INFO('EnablePostfixAutoBlock',$_GET["EnablePostfixAutoBlock"]);
	if(isset($_GET["EnablePostfixAutoBlockWhiteListed"])){
		$sock->SET_INFO('EnablePostfixAutoBlockWhiteListed',$_GET["EnablePostfixAutoBlockWhiteListed"]);
	}
	
}

function BlockDenyWhiteList(){

	
	$q=new mysql();
	$sql="SELECT * FROM postfix_whitelist_con";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "$q->mysql_error\n";}
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){	
		$html=$html . "<tr ". CellRollOver().">
		<td width=1%><img src='img/fw_bold.gif'></td>
		<td><strong style='font-size:12px'><code>{$ligne["ipaddr"]} ({$ligne["hostname"]}</code></td>
		<td width=1%>" . imgtootltip("ed_delete.gif","{delete}","PostfixAutoBlockDenyDelWhiteList('{$ligne["ipaddr"]}')")."</td>
	</tr>";
		
		
	}
	$html=$html."</table>";
	$tpl=new templates();
	return $tpl->_ENGINE_parse_body($html);
	
	
}

function AutoBlockDenyAddWhiteList(){
	if($_GET["AutoBlockDenyAddWhiteList"]==null){
		echo "NULL VALUE";
		return null;}
	
	
	if(!preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#",$_GET["white-list-host"])){
		$ipaddr=gethostbyname($_GET["AutoBlockDenyAddWhiteList"]);
		$hostname=$_GET["AutoBlockDenyAddWhiteList"];
	}else{
		$ipaddr=$_GET["AutoBlockDenyAddWhiteList"];
		$hostname=gethostbyaddr($_GET["AutoBlockDenyAddWhiteList"]);
	}
	
	$sql="INSERT IGNORE INTO postfix_whitelist_con (ipaddr,hostname) VALUES('$ipaddr','$hostname')";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}		
		
$sock=new sockets();		
$sock->getFrameWork("cmd.php?smtp-whitelist=yes");

	
	
}

function PostfixAutoBlockDenyDelWhiteList(){
	$users=new usersMenus();
	$tpl=new templates();
	if(!$users->AsPostfixAdministrator){
		$error=$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
		echo "$error";
		die();
	}	
		
	$found=false;
	$server=$_GET["PostfixAutoBlockDenyDelWhiteList"];
	$sql="DELETE FROM postfix_whitelist_con WHERE ipaddr='$server'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	$sql="DELETE FROM postfix_whitelist_con WHERE hostname='$server'";
	$q->QUERY_SQL($sql,"artica_backup");
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?smtp-whitelist=yes");
	
}

function PostfixEnableFwRule(){
	
	$sql="UPDATE iptables SET disable={$_GET["PostfixEnableFwRule"]} WHERE ID='{$_GET["ID"]}'";
	writelogs($sql,__FUNCTION__,__FILE__);
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	unset($_SESSION["postfix_firewall_rules"]);
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-iptables-compile=yes");
	
}

function PostfixEnableLog(){
	$sql="UPDATE iptables SET log={$_GET["PostfixEnableLog"]} WHERE ID='{$_GET["ID"]}'";
	writelogs($sql,__FUNCTION__,__FILE__);
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	unset($_SESSION["postfix_firewall_rules"]);	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-iptables-compile=yes");	
	
}

function PostfixAutoBlockCompile(){
	
	$html="
	<p class=caption>{PostfixAutoBlockCompileFW_text}</p>
	<div id='PostfixAutoBlockCompileStatusCompile'>
	</div>";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html,'postfix.index.php');
	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-iptables-compile=yes");	
	
}

function PostfixAutoBlockCompileCheck(){
	
	$ini=new Bs_IniHandler();
	$ini->loadFile("ressources/logs/compile.iptables.progress");
	$pourc=$ini->get("PROGRESS","pourc");
	$text=$ini->get("PROGRESS","text");
	
	
$color="#5DD13D";	
$html="
<center>
<div style='width:96%;text-align:center;font-size:12px;font-weight:bold;margin:5px;background-color:white;padding:5px;border:1px solid #CCCCCC'>
	<div style='width:95%;text-align:center;font-size:12px;font-weight:bold;margin:5px'>$text</div>
	<div style='width:100%;border:1px dotted #CCCCCC'>
	<div style='width:{$pourc}%;text-align:center;color:white;padding-top:3px;padding-bottom:3px;background-color:$color;'>
		<strong style='color:#BCF3D6;font-size:12px;font-weight:bold'>{$pourc}%</strong></center>
	</div>
	</div>
</div>
</center>
";	
$html=RoundedLightWhite($html);
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html,"postfix.index.php");
}

function ban_servers_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{bann_smtp_servers}");
	echo "YahooWin3('550','$page?ban-servers-popup=yes','$title')";
	
}
function ban_servers_popup(){
	$tpl=new templates();
	$page=CurrentPageName();

	$html="
	<div id='ban-smtp-div'>
	<div class=explain>{bann_smtp_servers_explain}</div>
	<textarea id='ban-servers-container' style='width:100%;height:450px;overflow:auto;font-size:14px'></textarea>
	<div style='text-align:right'>". button("{add}","BannSmtpAdd()")."</div>
	</div>
	<script>
	
	var x_BannSmtpAdd= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		YahooWin3Hide();
	}			
		
	function BannSmtpAdd(){
		var XHR = new XHRConnection();
		XHR.appendData('ban-servers-add',document.getElementById('ban-servers-container').value);
		document.getElementById('ban-smtp-div').innerHTML='<center style=\"margin:20px;padding:20px\"><img src=\"img/wait_verybig.gif\"></center>';
		XHR.sendAndLoad('$page', 'POST',x_BannSmtpAdd);		
		}
	
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function ban_servers_save(){
	
	$ipchain=new iptables_chains();
	$tb=explode("\n",$_POST["ban-servers-add"]);
	if(!is_array($tb)){echo "No data";return;}
	
	while (list ($num, $ipaddressData) = each ($tb) ){
		if(!preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#",$ipaddressData)){
			$ip=gethostbyname($ipaddressData);
			$servername=$ipaddressData;
		}else{
			$ip=$ipaddressData;
			$servername=gethostbyaddr($ipaddressData);
		}
		
		$ipchain=new iptables_chains();
		$ipchain->servername=$servername;
		$ipchain->serverip=$ip;
		$ipchain->EventsToAdd="Manual rule";
		$ipchain->rule_string="iptables -A INPUT -s $ip -p tcp --destination-port 25 -j DROP -m comment --comment \"ArticaInstantPostfix\"";
		if(!$ipchain->addPostfix_chain()){
			echo "Failed $ip $servername\n";
			return;
		}
		
	}
	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-iptables-compile=yes");
}

function DeleteAllIpTablesRules(){
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?DeleteAllIpTablesRules=yes");	
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{success}");
}

function firewall_events(){
	$sock=new sockets();
	$EnablePostfixAutoBlockWhiteListed=intval($sock->GET_INFO("EnablePostfixAutoBlockWhiteListed"));
	
	exec("tail -n 300 ". dirname(__FILE__)."/ressources/logs/iptables-smtp-drop.log 2>&1",$f);
	$tpl=new templates();
	$page=CurrentPageName();
	
	$html="
	<div style='text-align:right'>". imgtootltip("refresh-32.png","{refresh}","RefreshTab('instant_iptables_tabs')")."</td>
	<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th>{date}</th>
		<th>{from}</th>
		<th>{to}</th>
		<th>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>";	
	
	 
	
	if(is_array($f)){
		krsort($f);
		$c=0;
		while (list ($num, $line) = each ($f) ){
			if(preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+).+?kernel:.+?SMTP DROP:.+?SRC=(.+?)\s+DST=(.+?)\s+#",$line,$re)){
			if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}	
			$c++;
			$disabled="<a href=\"javascript:blur();\" OnClick=\"javascript:EventDisableIpTables('{$re[4]}');\" style='font-size:14px;text-decoration:underline'>{disable}</a>";
			if($EnablePostfixAutoBlockWhiteListed==1){$disabled="&nbsp;";}
			
			$html=$html . "
			<tr  class=$classtr>
			<td width=1% style='font-size:14px;' nowrap>{$re[1]} {$re[2]} {$re[3]}</td>
			<td width=1% style='font-size:14px;' nowrap>{$re[4]}<br><i>". ipName($re[4])."</i></td>
			<td width=1% style='font-size:14px;' nowrap>{$re[5]}</td>
			<td width=1% style='font-size:14px;' nowrap>$disabled</td>
			</tr>";
			if($c>300){break;}	
			}
		}
	}
	
	$html=$html."</table>
	<script>
	
	var x_EventDisableIpTables= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		
	}			
		
	function EventDisableIpTables(ip){
		var XHR = new XHRConnection();
		XHR.appendData('EventDisableIpTables',ip);
		XHR.sendAndLoad('$page', 'GET',x_EventDisableIpTables);		
		}		
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function ipName($ip){
	if(isset($GLOBALS["IPMEM"][$ip])){return $GLOBALS["IPMEM"][$ip];}
	$sql="SELECT servername FROM iptables WHERE serverip='$ip'";
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if($ligne["servername"]==null){
		$ligne["servername"]=gethostbyaddr($ip);
	}
	
	$GLOBALS["IPMEM"][$ip]=$ligne["servername"];
	return $ligne["servername"];
}

function InstantIptablesEventAll(){
	$sock=new sockets();
	$sock->SET_INFO("InstantIptablesEventAll",$_GET["InstantIptablesEventAll"]);
	
}

function EventDisableIpTables(){
	$sql="UPDATE iptables SET disable=1 WHERE serverip='{$_GET["EventDisableIpTables"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-iptables-compile=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{$_GET["EventDisableIpTables"]} {disabled}");	
	
}



function InstantIptablesStats(){
	$tpl=new templates();
	echo "<CENTER><div style='font-size:16px'>Instant Iptables Month " .date("Y-m")."</div>";
	include_once('ressources/class.artica.graphs.inc');
	$sql="SELECT COUNT(ID) as tcount,DATE_FORMAT(saved_date,'%d') as tdate 
	FROM iptables WHERE local_port=25 AND disable=0 
	AND DATE_FORMAT(saved_date,'%Y-%m')=DATE_FORMAT(NOW(),'%Y-%m') GROUP BY tdate ORDER BY tdate";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	$gp=new artica_graphs(dirname(__FILE__)."/ressources/logs/web/instant-iptables-graphs.png",0);
	$time=time();
	
	$count=mysql_num_rows($results);
	
	writelogs($count." rows",__FUNCTION__,__FILE__,__LINE__);
	
	if(mysql_num_rows($results)==0){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("<H2>{NO_DATA_COME_BACK_LATER}</H2>");
		return;
		
	}	
	
	if(!$q->ok){echo $q->mysql_error;}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$xdata[]=$ligne["tdate"];
		$ydata[]=$ligne["tcount"];
		
	}
	
	$gp->width=700;
	$gp->height=550;
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;
	$gp->y_title="IP blocked";
	$gp->x_title="Days";
	$gp->title=null;
	$gp->Fillcolor="blue@0.9";
	$gp->color="146497";
	$gp->line_green();
	echo "<img src='ressources/logs/web/instant-iptables-graphs.png?$time'></CENTER>";	
}
function CompileSSHDRules(){
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?CompileSSHDRules=yes");
	
}




?>