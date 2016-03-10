<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;
	ini_set('html_errors',1);
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);}
	if($GLOBALS["VERBOSE"]){echo "- > VERBOSE -> TRUE\n";
	}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.auto-aliases.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.auto-aliases.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.ejabberd.inc');
	
	
	if(!VerifyRights()){
		if($GLOBALS["VERBOSE"]){echo "- > VerifyRights -> FALSE\n";}
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	
	if(isset($_GET["remote-domain-add-js"])){remote_domain_js();exit;}
	if(isset($_GET["remote-domain-popup"])){remote_domain_popup();exit;}
	if(isset($_GET["remote-domain-form"])){remote_domain_form();exit;}
	
	if(isset($_GET["organization-local-domain-list"])){echo DOMAINSLIST($_GET["organization-local-domain-list"]);exit;}
	if(isset($_GET["organization-local-domain-list-search"])){echo DOMAINSLIST_SEARCH();exit;}
	
	
	
	
	if(isset($_GET["organization-relay-domain-list"])){echo RELAY_DOMAINS_LIST($_GET["organization-relay-domain-list"]);exit;}
	if(isset($_GET["organization-relay-domain-list-search"])){echo RELAY_DOMAINS_LIST_SEARCH();exit;}
	
	if(isset($_GET["AddLocalDomain-Form-js"])){AddNewInternetDomainForm_js();exit;}
	if(isset($_GET["AddLocalDomain-Form-popup"])){AddNewInternetDomainForm_popup();exit;}
	
	
	
	
	if(isset($_GET["AddNewInternetDomainDomainName"])){AddNewInternetDomain();exit;}
	if(isset($_GET["AddNewRelayDomainName"])){AddNewRelayDomain();exit;}
	if(isset($_GET["DeleteInternetDomain"])){DeleteInternetDomain();exit;}
	if(isset($_GET["EditRelayDomainIP"])){EditRelayDomain();exit();}
	if(isset($_GET["DeleteRelayDomainName"])){DeleteRelayDomainName();exit;}
	if(isset($_GET["LocalDomainList"])){echo DOMAINSLIST($_GET["ou"]);exit;}
	if(isset($_GET["RelayDomainsList"])){echo RELAY_DOMAINS_LIST($_GET["ou"]);exit;}
	if(isset($_GET["EditInfosLocalDomain"])){echo EditInfosLocalDomain();exit;}
	if(isset($_GET["EditLocalDomain"])){EditLocalDomain();exit();}
	if(isset($_GET["duplicate_local_domain"])){COPY_DOMAINS_SAVE();exit;}
	
	if(isset($_GET["js"])){echo js_script();exit;}
	if(isset($_GET["js-all-localdomains"])){echo js_all_localdomains();exit;}
	if(isset($_GET["ajax"])){echo js_popup();exit;}
	
	if(isset($_GET["round-robin"])){round_robin_js();exit;}
	if(isset($_GET["roundrobin_ipaddress"])){round_robin_save();exit;}
	if(isset($_GET["round-robin-popup"])){round_robin_popup();exit;}
	if(isset($_GET["round-robin-list"])){echo round_robin_list();exit;}
	if(isset($_GET["round-robin-delete"])){round_robin_delete();exit;}
	js_popup();
	
	
function VerifyRights(){
	$usersmenus=new usersMenus();
	if($usersmenus->AllowChangeDomains){return true;}
	if($usersmenus->AsMessagingOrg){return true;}
	if(!$usersmenus->AllowChangeDomains){return true;}
}
	
function round_robin_js(){
	$page=CurrentPageName();
	//&ou=$ou&domain=$num
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{roundrobin}');
	
	$ou=$_GET["ou"];
	$ou_encrypted=base64_encode($ou);
	$domain=$_GET["domain"];
	
	$html="
	function DomainViewConfig(){
			YahooWin3(600,'$page?round-robin-popup=yes&domain=$domain&ou={$_GET["ou"]}','$title $domain');
		
		}
		
	var x_RoundRobinSave= function (obj) {
		var response=obj.responseText;
		AddRemoteDomain_form('$ou','$domain');
		if(response){alert(response);}
	    LoadAjax('hostDomainList','$page?round-robin-list=&domain=$domain&ou={$_GET["ou"]}');
	}		
	
	
	function RoundRobinSave(){
		var roundrobin_ipaddress=document.getElementById('roundrobin_ipaddress').value;
		var roundrobin_nameserver=document.getElementById('roundrobin_nameserver').value;
		AnimateDiv('hostDomainList');
		var XHR = new XHRConnection();
		XHR.appendData('roundrobin_ipaddress',roundrobin_ipaddress);
		XHR.appendData('roundrobin_nameserver',roundrobin_nameserver);
		XHR.appendData('ou','$ou');
		XHR.appendData('domain','$domain');
		XHR.sendAndLoad('$page', 'GET',x_RoundRobinSave);
	}
	
	function  RoundRobinDelete(num){
		var XHR = new XHRConnection();
		XHR.appendData('round-robin-delete',num);
		XHR.appendData('domain','$domain');
		AnimateDiv('hostDomainList');
		XHR.sendAndLoad('$page', 'GET',x_RoundRobinSave);
	}
		
	DomainViewConfig();
	
	
	
	";
	
	echo $html;
	
	
}

function round_robin_delete(){
	$domain=$_GET["domain"];
	$ini=new Bs_IniHandler();
	$sock=new sockets();
	$datas=$sock->GET_INFO('RoundRobinHosts');
	$ini->loadString($datas);
	$ips=explode(",",$ini->_params["$domain"]["IP"]);
	unset($ips[$_GET["round-robin-delete"]]);
	$ini->_params["$domain"]["IP"]=implode(",",$ips);
	$sock->SaveConfigFile($ini->toString(),"RoundRobinHosts");
	$sock->getfile("RoundRobinHosts");
	}

function round_robin_save(){
	
	$ou=$_GET["ou"];
	$tpl=new templates();
	$roundrobin_nameserver=$_GET["roundrobin_nameserver"];
	$roundrobin_ipaddress=$_GET["roundrobin_ipaddress"];
	$domain=$_GET["domain"];
	if(IsIPValid($roundrobin_nameserver)){
		echo $tpl->_ENGINE_parse_body("{servername}:\n$roundrobin_nameserver\n {error_cannot_be_ip_address}");
		exit;
	}
	
	if(!IsIPValid($roundrobin_ipaddress)){
		echo $tpl->_ENGINE_parse_body("{add_ip_address}:\n$roundrobin_ipaddress\n {error_must_be_ip_address}");
		exit;
	}
	
	$ini=new Bs_IniHandler();
	$sock=new sockets();
	$datas=$sock->GET_INFO('RoundRobinHosts');
	$ini->loadString($datas);
	$ips=explode(",",$ini->_params["$domain"]["IP"]);
	$ips[]=$roundrobin_ipaddress;
	
	$ini->_params["$domain"]["servername"]=$roundrobin_nameserver;
	$ini->_params["$domain"]["IP"]=implode(",",$ips);
	$sock->SaveConfigFile($ini->toString(),"RoundRobinHosts");
	$sock->getfile("RoundRobinHosts");
	
	$ldap=new clladp();
	$dn="cn=$domain,cn=transport_map,ou=$ou,dc=organizations,$ldap->suffix";
	$upd["transport"][0]="[$roundrobin_nameserver]";
	if(!$ldap->Ldap_modify($dn,$upd)){
		echo $ldap->ldap_last_error;
		exit;				
	}
	
	echo html_entity_decode($tpl->_ENGINE_parse_body('{success}'));
	
	
	
}

function round_robin_list(){
	$ini=new Bs_IniHandler();
	$sock=new sockets();
	$datas=$sock->GET_INFO('RoundRobinHosts');
	$ini->loadString($datas);
	$ips=explode(",",$ini->_params[$_GET["domain"]]["IP"]);
	$server=$ini->_params[$_GET["domain"]]["servername"];
	
	$html="<table style='width:99%' class=form>";
	while (list ($num, $ligne) = each ($ips) ){
		if(!IsIPValid($ligne)){continue;}
		$html=$html . "<tr>
			<td width=1%><img src='img/fw_bold.gif'></td>
			<td><strong style='font-size:14px'><code>$server&nbsp;&nbsp;==&nbsp;&nbsp;$ligne</code></strong></td>
			<td width=1%>" . imgtootltip('ed_delete.gif',"{delete}","RoundRobinDelete($num,'{$_GET["domain"]}')")."</td>
			</tr>";
		
	}
	
	$html=$html . "</table>";
	
	$tpl=new templates();
	return $tpl->_ENGINE_parse_body($html);
	
}

function round_robin_popup(){
	
$ldap=new clladp();	
$HashDomains=$ldap->Hash_relay_domains($_GET["ou"]);
$tools=new DomainsTools();
$arr=$tools->transport_maps_explode($HashDomains[$_GET["domain"]]);	
$roundrobin_nameserver=$arr[1];
$list=round_robin_list();
	$html="
	<H1>{roundrobin}: {$_GET["domain"]}</H1>
	<img src='img/roundrobin_bg.png' align='right' style='margin:3px'><p class=caption>{roundrobin_text}</p>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend>{servername}:</td>
		<td>" . Field_text('roundrobin_nameserver',$roundrobin_nameserver,'width:210px')."</td>
	</tr>
	<tr>
		<td class=legend>{add_ip_address}:</td>
		<td>" . Field_text('roundrobin_ipaddress',null,'width:90px')."</td>
	</tr>
	<tr><td colspan=2 align='right'><hR></td></tr>	
	<tr>
		<td colspan=2 align='right'><input type='button' OnClick=\"javascript:RoundRobinSave();\" value='{add}&nbsp;&raquo;'></td>
	</tr>
	</table>
	<div id='hostDomainList'>$list</div>	
	
	
	
	
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
	
}


function js_all_localdomains(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$title=$tpl->_ENGINE_parse_body("{localdomains}");
	$datas=file_get_contents("js/edit.localdomain.js");
	echo "$datas\nYahooWin5(750,'$page?ajax=yes&master-t={$_GET["master-t"]}','$title',true);";
	
}
function AddNewInternetDomainForm_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$title=$tpl->_ENGINE_parse_body("{new_domain}");
	
	echo "YahooWin6(600,'$page?AddLocalDomain-Form-popup={$_GET["master-t"]}','$title',true);";	
	
}

function AddNewInternetDomainForm_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$tt=time();
	$ldap=new clladp();
	$ous=$ldap->hash_get_ou(true);
	$t=time();
	$html="
	<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:16px'>{organization}:</td>
			<td>". Field_array_Hash($ous, "ou-$t",null,"style:font-size:16px;font-weight:bold")."</td>
		</tr>		
		<tr>
			<td class=legend style='font-size:16px'>{domain}:</td>
			<td>". Field_text("domain-$t", null,"font-size:16px")."</td>
		</tr>	
		<tr>
			<td colspan=2 align=right><hr>". button("{add}","Save$tt();",22)."</td>
		</tr>
		</table>
					
<script>
	var xSave$tt= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		$('#flexRT$t').flexReload();
		UnlockPage();
		if(document.getElementById('main_config_dhcpd')){RefreshTab('main_config_dhcpd');}
	}
function Save$t(){
		LockPage();
		var XHR = new XHRConnection();
		XHR.appendData('AddNewInternetDomain',document.getElementById('ou-$t').value);
		XHR.appendData('AddNewInternetDomainDomainName',document.getElementById('domain-$t').value);
		XHR.sendAndLoad('$page', 'GET',xSave$tt);
		}

</script>";			
	echo $tpl->_ENGINE_parse_body($html);
	
}


function js_script(){
	header("content-type: application/x-javascript");
	if(isset($_GET["encoded"])){$_GET["ou"]=base64_decode($_GET["ou"]);}
	if($_GET["ou"]==null){$_GET["ou"]=ORGANISTATION_FROM_USER();}
	$ou=$_GET["ou"];
	$ou_encrypted=base64_encode($ou);
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("$ou:&nbsp;{localdomains}");
	$datas=file_get_contents("js/edit.localdomain.js");
	$startup="LoadOuDOmainsIndex();";
	
	if(isset($_GET["in-front-ajax"])){
		$startup="LoadOuDOmainsIndexInFront();";
		$jsadd=remote_domain_js();
	}
	
	$html="
	var timeout=0;
	$datas
	
	function LoadOuDOmainsIndex(){
		YahooWin0(750,'$page?ajax=yes&ou=$ou_encrypted&master-t={$_GET["master-t"]}','$title',true);
		
	}
	
	function LoadOuDOmainsIndexInFront(){
		$('#BodyContent').load('$page?ajax=yes&ou=$ou_encrypted&in-front-ajax=yes');
	}	
	$jsadd
	$startup
	";
	
	
	echo $html;
	
}

function js_popup(){
	$tpl=new templates();
	$ou=$_GET["ou"];
	$page=CurrentPageName();
	$users=new usersMenus();
	if($GLOBALS["VERBOSE"]){echo "- > ORGANISTATION_FROM_USER\n";}
	if($ou==null){$ou=ORGANISTATION_FROM_USER();}
	$styleText="style='font-size:26px'";
	
	$LOCAL_MDA=false;
	if($users->cyrus_imapd_installed){$LOCAL_MDA=true;}
	if($users->ZARAFA_INSTALLED){$LOCAL_MDA=true;}
	
	if(isset($_GET["expand"])){$expand="&expand=yes";}
	
	if($LOCAL_MDA){$arr["organization-local-domain-list"]="{local_domains}";}
	
	if($users->POSTFIX_INSTALLED){
		$arr["organization-relay-domain-list"]="{remote_domains}";
	}
	
	if(count($arr)==0){
		$arr["organization-local-domain-list"]="{local_domains}";
	}
	
	while(list( $num, $ligne ) = each ($arr)){
			$ligne=$tpl->_ENGINE_parse_body($ligne);
			$toolbox [] = "<li><a href=\"$page?$num=$ou&master-t={$_GET["master-t"]}$expand\"><span $styleText>$ligne</span></a></li>";
	}
	

	
	
	echo build_artica_tabs($toolbox, "organization-domains-tabs",1490);
	
	
}



function js_popup_old(){
	$tpl=new templates();
	$ou=base64_decode($_GET["ou"]);
	$page=CurrentPageName();
	if($ou==null){$ou=ORGANISTATION_FROM_USER();}
	$title=$ou . ":&nbsp;{groups}";
	$users=new usersMenus();
	$sock=new sockets();
	$InternetDomainsAsOnlySubdomains=$sock->GET_INFO("InternetDomainsAsOnlySubdomains");
	if(!is_numeric($InternetDomainsAsOnlySubdomains)){$InternetDomainsAsOnlySubdomains=0;}
	$add_local_domain_form_text=$tpl->javascript_parse_text("{add_local_domain_form}");
	$POSTFIX_INSTALLED=$users->POSTFIX_INSTALLED;
	if($users->ZARAFA_INSTALLED){$users->cyrus_imapd_installed=true;}
	
	$add_local_domain=Paragraphe("64-localdomain-add.png",'{add_local_domain}','{add_local_domain_text}',
	"javascript:AddLocalDomain_form()","add_local_domain",210);
	$add_remote_domain=Paragraphe("64-remotedomain-add.png",'{add_relay_domain}','{add_relay_domain_text}',
	"javascript:AddRemoteDomain_form(\"$ou\",\"new domain\")","add_relay_domain",210);
	$local_js="LoadAjax('LocalDomainsList','$page?organization-local-domain-list=$ou');";
	$import_domains=Paragraphe("64-import.png",'{import_smtp_domains}','{import_smtp_domains_text}',
	"javascript:Loadjs('domains.import.domains.php?ou={$_GET["ou"]}')","{import_smtp_domains}",210);
	$local_part="<div id='LocalDomainsList' style='width:100%;overflow:auto'></div>";	
	
	$remote_part="<div id='RelayDomainsList' style='width:100%;overflow:auto'></div>";
	
	$remote_js="LoadAjax('RelayDomainsList','$page?organization-relay-domain-list=$ou');";
	
	if(!$POSTFIX_INSTALLED){$add_remote_domain="<p>&nbsp;</p>";$remote_part=null;$remote_js=null;}
	
	
		if(!$users->cyrus_imapd_installed){
			$add_local_domain_warn="<div class=explain>{no_backendmailbox_installed_explain}</div>";
		}
	
	
	$ouescape=urlencode($ou);
	$html="
	<input type='hidden' id='inputbox delete' value=\"{are_you_sure_to_delete}\">
	<input type='hidden' id='add_local_domain_form' value=\"{add_local_domain_form}\">
	<input type='hidden' id='ou' value='$ou'>		
	<div id='NavigationForms2'>$add_local_domain_warn
	$local_part
	<p>&nbsp;</p>
	$remote_part
	</div>
	<br>
	<center><table><tr><td>$add_local_domain</td><td>$add_remote_domain</td><td>$import_domains</td></tr></table></center>
<script>

	var x_AddLocalDomain_form= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		LoadAjax('LocalDomainsList','$page?LocalDomainList=yes&ou=$ou');
	}


function AddLocalDomain_form(){
	var InternetDomainsAsOnlySubdomains=$InternetDomainsAsOnlySubdomains;
	if(InternetDomainsAsOnlySubdomains==1){
		Loadjs('domains.add.localdomain.restricted.php?ou=$ouescape');
		return;
	}
	var domain=prompt('$add_local_domain_form_text');
	if(domain){
		var XHR = new XHRConnection();
		XHR.appendData('AddNewInternetDomain','$ouescape');
		XHR.appendData('AddNewInternetDomainDomainName',domain);		
		XHR.sendAndLoad('$page', 'GET',x_AddLocalDomain_form);
		}
	}
	$local_js
	$remote_js
</script>

";
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);
	
	
}



function ORGANISTATION_FROM_USER(){
	if($_SESSION["uid"]==-100){return;}
	$ldap=new clladp();
	$hash=$ldap->Hash_Get_ou_from_users($_SESSION["uid"],1);
	if(!is_array($hash)){header('location:domains.index.php');}
	return $hash[0];
	}
	
// ----------------------------------------------------------------------------------------------------------------------------------------------	
function EditInfosLocalDomain(){
	$num=$_GET["EditInfosLocalDomain"];
	$ou=$_GET["ou"];
	
	$autoalias=new AutoAliases($ou);
	if(strlen($autoalias->DomainsArray[$_GET["EditInfosLocalDomain"]])>0){$alias="1";}
	
	$users=new usersMenus();
	$users->LoadModulesEnabled();
	if($users->AMAVIS_INSTALLED){
		if($users->EnableAmavisDaemon==1){
			$amavis=COPY_DOMAINS($_GET["EditInfosLocalDomain"],$ou);
			if($users->AllowChangeAntiSpamSettings){
				$button_as_settings=Paragraphe("64-spam.png","{Anti-spam}","{change_antispam_domain_text}","javascript:Loadjs('domains.amavis.php?domain=$num')");
				//RoundedLightWhite("<input type='button' OnClick=\"javascript:Loadjs('domains.amavis.php?domain=$num');\" value='&nbsp;&nbsp;&nbsp;&nbsp;{Anti-spam}&nbsp;&raquo;&nbsp;&nbsp;&nbsp;&nbsp;'>");
			}
		}
	}
	
	if($alias=='yes'){$alias=1;}
	if($alias=='no'){$alias=0;}
	$autoalias_p=Paragraphe_switch_img("{autoaliases}","{autoaliases_text}","{$num}_autoaliases",$alias,'{enable_disable}',300);
	$amavis=RoundedLightWhite($amavis);
	$catchall=Paragraphe("64-catch-all.png","{catch_all}","{catch_all_mail_text}","javascript:Loadjs('domains.catchall.php?domain=$num&ou=$ou')");
	
	
	
	$html="
	<input type='hidden' id='ou' name='ou' value='$ou'>
	<H1>{domain}:&nbsp;&laquo;{$_GET["EditInfosLocalDomain"]}&raquo;</H1>
	<table style='width:100%'>
		<tr>
		<td valign='top' width=99%>
		<table style='width:100%'>
		<tr>
			<td valign='top' style='padding:4px'>
				$autoalias_p
				<hr>
				<div style='text-align:right'>
					<input type='button' value='{apply}&nbsp;&raquo;' OnClick=\"javascript:EditLocalDomain('$num');\">
				</div>				
				<br>
				$amavis
				<br>
				

				
				
				</td>
			<td valign='top' style='padding:4px'>
		$button_as_settings
		<br>$catchall
			</td>
		</tr>
		</table>
	</td>
	</tr>
	</table><br>
	
	";		
	
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}


function COPY_DOMAINS($domain,$ou){
	include_once("ressources/class.amavis.inc");
	$amavis=new amavis();
	$domain=strtolower($domain);
	$refresh="LoadAjax('LocalDomainsList','domains.edit.domains.php?LocalDomainList=yes&ou=$ou');";
	$html="<H3>{duplicate_domain}</H3>
	
	
	
	<p class=caption>{duplicate_domain_text}</p>
	<form name='ffmdup'>
	<input type='hidden'  name='duplicate_local_domain' id='duplicate_local_domain' value='$domain'>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend>{enable}:</td>
		<td>" . Field_numeric_checkbox_img('enable',$amavis->copy_to_domain_array[$domain]["enable"],'{enable_disable}')."</td>
	</tr>
	<tr>
		<td class=legend>{target_computer_name}:</td>
		<td>" .Field_text('duplicate_host',$amavis->copy_to_domain_array[$domain]["duplicate_host"],'width:160px')."</td>
	</tr>
	<tr>
		<td class=legend>{target_computer_port}:</td>
		<td>" .Field_text('duplicate_port',$amavis->copy_to_domain_array[$domain]["duplicate_port"],'width:30px')."</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'><input type='button' OnClick=\"javascript:ParseForm('ffmdup','domains.edit.domains.php',true);$refresh\" value='{apply}&nbsp;&raquo;'></td>
	</tr>
	</table>
	</form>
	";
	
	$tpl=new templates();
	return $tpl->_ENGINE_parse_body($html);
	
}

function COPY_DOMAINS_SAVE(){
include_once("ressources/class.amavis.inc");
	$amavis=new amavis();
	$domain=$_GET["duplicate_local_domain"];
	if(!is_numeric($_GET["duplicate_port"])){$_GET["duplicate_port"]=25;}
	$amavis->copy_to_domain_array[$domain]["enable"]=$_GET["enable"];
	$amavis->copy_to_domain_array[$domain]["duplicate_host"]=$_GET["duplicate_host"];
	$amavis->copy_to_domain_array[$domain]["duplicate_port"]=$_GET["duplicate_port"];
	$amavis->SaveCopyToDomains();
	$tpl=new templates();
	echo html_entity_decode($tpl->_ENGINE_parse_body("{enable}:{$_GET["enable"]}\n$domain -> {$_GET["duplicate_host"]}:{$_GET["duplicate_port"]}\n{success}"));
	
}

// ------------------------------------------------------ <<<
function EditLocalDomain(){
	$domain=$_GET["EditLocalDomain"];
	$ou=$_GET["ou"];
	
	//Save Autoaliases.
	$autoaliases=new AutoAliases($ou);
	if($_GET["autoaliases"]=="1"){$autoaliases->DomainsArray[$domain]=$domain;}else{unset($autoaliases->DomainsArray[$domain]);}
	$autoaliases->Save();
	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-transport-maps=yes");	
	
}


function remote_domain_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{relay_domain_map}');
	$ou=$_GET["ou"];
	$start="remote_domain_popup()";
	if(isset($_GET["in-front-ajax"])){$start=null;}
	if(!is_numeric($_GET["t"])){$_GET["t"]=0;}
	$index=base64_encode($_GET["index"]);
	$html="
		function remote_domain_popup(){
			YahooWin(650,'$page?remote-domain-popup=yes&add=yes&ou=$ou&index=$index&t={$_GET["t"]}','$title :: {$_GET["index"]}',true);	
		}
		
		
		function refresh_remote_domain_popup(){
			LoadAjax('remote_domain_popup','$page?remote-domain-form=yes&add=yes&ou=$ou&index=$index');
		}
		
		var x_AddRelayDomain= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			var t={$_GET["t"]};
			if(t>0){ $('#flexRT{$_GET["t"]}').flexReload(); }
			YahooWinHide();
			if(IsFunctionExists('FlexReloadRemoteDomainList')){FlexReloadRemoteDomainList();}
		}
	
function AddRelayDomain(){
	var XHR = new XHRConnection();
	var ou=document.getElementById('ou').value;
	XHR.appendData('AddNewRelayDomainIP',document.getElementById('AddNewRelayDomainIP').value);
	XHR.appendData('AddNewRelayDomainPort',document.getElementById('AddNewRelayDomainPort').value);
	XHR.appendData('AddNewRelayDomainName',document.getElementById('AddNewRelayDomainName').value);
	XHR.appendData('MX',document.getElementById('MX').value);
	memory_ou=document.getElementById('ou').value;
	XHR.appendData('ou','$ou');
	AnimateDiv('remote_domain_popup');
	XHR.sendAndLoad(\"domains.edit.domains.php\", 'GET',x_AddRelayDomain);	
	}

function EditRelayDomain(domain_name){
	var XHR = new XHRConnection();
	XHR.appendData('EditRelayDomainIP',document.getElementById(domain_name+'_IP').value);
	XHR.appendData('EditRelayDomainPort',document.getElementById(domain_name+'_PORT').value);
	XHR.appendData('EditRelayDomainName',domain_name);
	XHR.appendData('MX',document.getElementById(domain_name+'_MX').value);
	XHR.appendData('autoaliases',document.getElementById(domain_name+'_autoaliases').value);
	XHR.appendData('ou','$ou');
	AnimateDiv('remote_domain_popup');
	XHR.sendAndLoad(\"domains.edit.domains.php\", 'GET',x_AddRelayDomain);	
	}	
		
   $start;
	";
		
	echo $html;
}

function remote_domain_popup(){
	$page=CurrentPageName();
	$html="
	<div style='width:100%;' id='remote_domain_popup'>
	
	</div>
	<script>
		LoadAjax('remote_domain_popup','$page?remote-domain-form=yes&add=yes&ou={$_GET["ou"]}&index={$_GET["index"]}&t={$_GET["t"]}');
//		refresh_remote_domain_popup();
	</script>
	";
	echo $html;
}

function remote_domain_form(){
$_GET["index"]=base64_decode($_GET["index"]);	
$ldap=new clladp();	
$HashDomains=$ldap->Hash_relay_domains($_GET["ou"]);
$tools=new DomainsTools();
$arr=$tools->transport_maps_explode($HashDomains[$_GET["index"]]);
$page=CurrentPageName();
$autoalias=new AutoAliases($_GET["ou"]);
$users=new usersMenus();
$users->LoadModulesEnabled();

$num=$_GET["index"];
if(strlen($autoalias->DomainsArray[$num])>0){
	$alias="yes";
}
$button_as_settings=Paragraphe('64-buldo.png','{Anti-spam}','{antispam_text}',"javascript:Loadjs('domains.amavis.php?domain=$num');");
if(!$users->AMAVIS_INSTALLED){$button_as_settings=null;}
if($users->EnableAmavisDaemon<>1){$button_as_settings=null;}
if(!$users->AllowChangeAntiSpamSettings){$button_as_settings=null;}




if($_GET["index"]<>"new domain"){
	$dn="cn=@$num,cn=relay_recipient_maps,ou={$_GET["ou"]},dc=organizations,$ldap->suffix";
	$trusted_smtp_domain=0;
	if($ldap->ExistsDN($dn)){$trusted_smtp_domain=1;}
	$edit_button="<hr>". button("{apply}","EditRelayDomain('$num')","22px");
	
	$trusted=Paragraphe_switch_img("{trusted_smtp_domain}","{trusted_smtp_domain_text}","trusted_smtp_domain",$trusted_smtp_domain,"{enable_disable}",220);
	$roundrobin=Paragraphe('64-computer-alias.png','{roundrobin}','{roundrobin_text}',"javascript:Loadjs('$page?round-robin=yes&ou={$_GET["ou"]}&domain=$num');");
	$form="
	<table style='width:99%' class=form>
		<tr>
			<td><strong style='font-size:18px;color:black'>{domain_name}:</strong></td>
			<td align='right'><strong style='font-size:18px;color:black'>{$_GET["index"]}</strong></td>
		</tr>
		<tr>							
			<td nowrap><strong style='font-size:18px;color:black'>{target_computer_name}:&nbsp;</strong></td>
			<td align='right'>". Field_text("{$num}_IP",$arr[1],'width:256px;padding:10px;font-size:16px') ."</td>
		</tr>
			<td align='right' colspan=2><strong style='font-size:18px;color:black'>{port}:&nbsp;". 
				Field_text("{$num}_PORT",$arr[2],'width:50px;padding:3px;font-size:16px').
				"&nbsp;" . Field_yesno_checkbox_img("{$num}_MX",$arr[3],'{mx_look}')."&nbsp;".
				Field_yesno_checkbox_img("{$num}_autoaliases",$alias,'<b>{autoaliases}</b><br>{autoaliases_text}')."
			</td>
		</tr>
		
		<tr>
			<td align='right' colspan=2>$edit_button</td>
		</tr>
	</table>";
	
	
}else{

	$button_as_settings=null;
	$form="
	<table style='width:99%' class=form>
		<tr>
			<td class=legend><strong style='font-size:18px;color:black'>{domain_name}:</strong></td>
			<td align='right'>". Field_text('AddNewRelayDomainName',null,'width:256px;padding:10px;font-size:16px') ."</td>
		</tr>
		<tr>					
			<td nowrap class=legend><strong style='font-size:18px;color:black'>{target_computer_name}:&nbsp;</strong></td>
			<td align='right'>". Field_text('AddNewRelayDomainIP',null,'width:256px;padding:10px;font-size:16px') ."</td>
		</tr>
			<td align='right' colspan=2><strong style='font-size:18px;color:black'>{port}:&nbsp;". 
				Field_text('AddNewRelayDomainPort','25','width:50px;padding:3px;font-size:16px') .
				"&nbsp;" . Field_yesno_checkbox_img('MX','no','{mx_look}')."
			</td>
		</tr>
		<tr>
			<td colspan=2 align='right'><div style='float:right'>". 
				Paragraphe_switch_img("{trusted_smtp_domain}","{trusted_smtp_domain_text}",
				"trusted_smtp_domain",1,"{enable_disable}",520)."</div></td>
		</tr>		
		<tr>
			<td align='right' colspan=2>
			<hr>". button("{add}","AddRelayDomain()","22px")."</td>
		</tr>
	</table>";

}

$html="

<table style='width:100%'>
<tr>
	<td valign='top'>$form</td>
	<td valign='top' style='padding-left:5px'>$button_as_settings$roundrobin$trusted</td>
</tr>
<tr>
	<td colspan=2 align='right'>$edit_button</td>
</tr>
</table>

";

$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);
	
}

function RELAY_DOMAINS_LIST($ou){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$t=time();
	$domain=$tpl->_ENGINE_parse_body("{domain}");
	$are_you_sure_to_delete=$tpl->javascript_parse_text("{are_you_sure_to_delete}");
	$autoaliases=$tpl->javascript_parse_text("{autoaliases}");
	$disclaimer=$tpl->javascript_parse_text("{disclaimer}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$InternetDomainsAsOnlySubdomains=$sock->GET_INFO("InternetDomainsAsOnlySubdomains");
	if(!is_numeric($InternetDomainsAsOnlySubdomains)){$InternetDomainsAsOnlySubdomains=0;}
	$add_local_domain_form_text=$tpl->javascript_parse_text("{add_local_domain_form}");
	$POSTFIX_INSTALLED=$users->POSTFIX_INSTALLED;
	if($users->ZARAFA_INSTALLED){$users->cyrus_imapd_installed=true;}
	$add_local_domain=$tpl->_ENGINE_parse_body("{add_local_domain}");
	$add_relay_domain=$tpl->_ENGINE_parse_body("{add_relay_domain}");
	$ouescape=urlencode($ou);
	$destination=$tpl->javascript_parse_text("{destination}");
	$master_t=$_GET["master-t"];
	if(!is_numeric($master_t)){$master_t=0;}
	
	$add_remote_domain=Paragraphe("64-remotedomain-add.png",'{add_relay_domain}','{add_relay_domain_text}',
	"javascript:AddRemoteDomain_form(\"$ou\",\"new domain\")","add_relay_domain",210);

	$buttons="
	buttons : [
	{name: '<strong style=font-size:20px>$add_relay_domain</strong>', bclass: 'add', onpress : AddRelayDomain$t},
	],";	

$title=$tpl->_ENGINE_parse_body("{remote_domains}: &laquo;{$_GET["organization-relay-domain-list"]}&raquo;");
	
	
$html="
<center id='DOMAINLIST-$t' style='margin-bottom:5px'></center>		
<input type='hidden' id='ou' value='$ou'>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?organization-relay-domain-list-search=yes&ou={$_GET["organization-relay-domain-list"]}&t=$t$expand',
	dataType: 'json',
	colModel : [
		{display: '<span style=font-size:20px>$domain</span>', name : 'domain', width : 607, sortable : false, align: 'left'},
		{display: '<span style=font-size:20px>$autoaliases</span>', name : 'autoaliases', width : 205, sortable : false, align: 'left'},
		{display: '<span style=font-size:20px>$disclaimer</span>', name : 'dis', width : 199, sortable : false, align: 'left'},				
		{display: '<span style=font-size:20px>$destination</span>', name : 'description', width :225, sortable : true, align: 'left'},
		{display: '<span style=font-size:20px>$delete</span>', name : 'delete', width : 119, sortable : false, align: 'left'},
		],
	$buttons
	searchitems : [
		{display: '$domain', name : 'domain'},
		],
	sortname: 'domain',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

	function AddRelayDomain$t(){
		Loadjs('domains.edit.domains.php?remote-domain-add-js=yes&t=$t&ou={$_GET["organization-relay-domain-list"]}&index=new%20domain')
	}


		var x_DeleteRelayDomain$t= function (obj) {
			document.getElementById('DOMAINLIST-$t').innerHTML='';
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			FlexReloadRemoteDomainList();
		}	
	
		
		function DeleteRelayDomain$t(domain_name){
			var mytext='$are_you_sure_to_delete';
			if(confirm(mytext+' '+domain_name)){
				AnimateDiv('DOMAINLIST-$t');
				var XHR = new XHRConnection();
				XHR.appendData('DeleteRelayDomainName',domain_name);
				XHR.appendData('ou','$ou');
				XHR.sendAndLoad('domains.edit.domains.php', 'GET',x_DeleteRelayDomain$t);
			}
		
		}
	
		


	function FlexReloadRemoteDomainList(){
		$('#flexRT$t').flexReload();
		var mastert=$master_t;
		if(mastert>0){ $('#table-$master_t').flexReload(); }
		
	}
	

	


Loadjs('js/postfix-transport.js');
</script>

";	
	echo $html;	


}
	
	
function RELAY_DOMAINS_LIST_OLD($ou){
$ldap=new clladp();	
$tpl=new templates();
$amavis_oui=false;
writelogs("----------------> Hash_relay_domains",__FUNCTION__,__FILE__,__LINE__);	
$HashDomains=$ldap->Hash_relay_domains($ou);
$aliases=new AutoAliases($ou);


if(!is_array($HashDomains)){
	$titleerrrr=$tpl->_ENGINE_parse_body("<span style='font-size:12px'>{no_remote_domain_here}</span>");
	
}

$users=new usersMenus();
$users->LoadModulesEnabled();
if(!$users->POSTFIX_INSTALLED){writelogs("POSTFIX IS NOT INSTALLED",__FUNCTION__,__FILE__,__LINE__);return null;}
if($users->AMAVIS_INSTALLED){if($users->EnableAmavisDaemon==1){$amavis_oui=true;}}
$disclaimer=IS_DISCLAIMER();

$tools=new DomainsTools();


		$html="
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th colspan=4 style='font-size:14px'>{relay_domain_map}&nbsp;$titleerrrr</th>
	<tr>
	<th>". imgtootltip("plus-24.png","{add}","AddRemoteDomain_form('$ou','new domain')")."</th>
	<th>{domain}</th>
	<th>{relay}</th>
	<th>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>";
		
		
		
if(is_array($HashDomains)){		
		while (list ($num, $ligne) = each ($HashDomains) ){
			
	

			$html=$html."<tr class=$classtr>
						<td width=1% valign='top'>". imgtootltip("domain-32.png","{apply}",$js)."</td>
						<td><div style='font-size:16px;font-weight:bold'>". texttooltip("$num","{parameters}",$js,null,0,"font-size:18px")."</div></td>
						<td><div style='font-size:16px;font-weight:bold'>". texttooltip("$relay","{parameters}",$js,null,0,"font-size:18px")."</div></td>
						<td width=1%>".imgtootltip("delete-24.png",'{label_delete_transport}',"DeleteRelayDomain('$num')")."</td>
					</tr>";			
			
		}
	}
	
	
	$dn="cn=transport_map,ou=$ou,dc=organizations,$ldap->suffix";
	$hash=$ldap->Ldap_search($dn,'(objectclass=transportTable)',array());
	for($i=0;$i<$hash["count"];$i++){
			$transport=$hash[$i]["transport"][0];
			$domain=$hash[$i]["cn"][0];
			$arr=$tools->transport_maps_explode($transport);
			$relay="{$arr[1]}:{$arr[2]}";
			$js="PostfixAddRoutingTable('$domain')";
			if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
			$html=$html."<tr class=$classtr>
						<td width=1% valign='top'>". imgtootltip("domain-32.png","{apply}",$js)."</td>
						<td><div style='font-size:16px;font-weight:bold'>". texttooltip("$domain","{parameters}",$js,null,0,"font-size:18px")."</div>						</td>
						<td><div style='font-size:16px;font-weight:bold'>". texttooltip("$relay","{parameters}",$js,null,0,"font-size:18px")."</div>						</td>
						<td width=1%>&nbsp;</td>
					</tr>";			
	}
	
	
	
				
	
	
	$ou_ser=urlencode($ou);
	$html=$html."
	
	</table>
	
	<script>
	

	</script>
	";
	
	
return $tpl->_ENGINE_parse_body($html);
}

function IS_DISCLAIMER(){
	$disclaimer=true;
	$users=new usersMenus();
	$sock=new sockets();
	$users->LoadModulesEnabled();
	$POSTFIX_INSTALLED=$users->POSTFIX_INSTALLED;
	$ALTERMIME_INSTALLED=$users->ALTERMIME_INSTALLED;
	$EnableAlterMime=$sock->GET_INFO('EnableAlterMime');
	$EnableArticaSMTPFilter=$sock->GET_INFO("EnableArticaSMTPFilter");
	$EnableArticaSMTPFilter=0;
	
	
	$DisclaimerOrgOverwrite=$sock->GET_INFO("DisclaimerOrgOverwrite");
	if(!$POSTFIX_INSTALLED){$disclaimer=false;}
	if(!$ALTERMIME_INSTALLED){$disclaimer=false;}
	if($EnableAlterMime==1){
		if($EnableArticaSMTPFilter==0){$disclaimer=false;}
	}
	
	if($DisclaimerOrgOverwrite==0){$disclaimer=false;}	
	return $disclaimer;
}

function DOMAINSLIST($ou){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$t=time();
	$domain=$tpl->_ENGINE_parse_body("{domain}");
	$are_you_sure_to_delete=$tpl->javascript_parse_text("{are_you_sure_to_delete}");
	$autoaliases=$tpl->javascript_parse_text("{autoaliases}");
	$disclaimer=$tpl->javascript_parse_text("{disclaimer}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$InternetDomainsAsOnlySubdomains=$sock->GET_INFO("InternetDomainsAsOnlySubdomains");
	if(!is_numeric($InternetDomainsAsOnlySubdomains)){$InternetDomainsAsOnlySubdomains=0;}
	$add_local_domain_form_text=$tpl->javascript_parse_text("{add_local_domain_form}");
	$POSTFIX_INSTALLED=$users->POSTFIX_INSTALLED;
	if($users->ZARAFA_INSTALLED){$users->cyrus_imapd_installed=true;}
	$add_local_domain=$tpl->_ENGINE_parse_body("{add_local_domain}");
	$import_smtp_domains=$tpl->_ENGINE_parse_body("{import_smtp_domains}");
	$ouescape=urlencode($ou);
	$master_t=$_GET["master-t"];
	if(!is_numeric($master_t)){$master_t=0;}
	
	$DOMAIN_WITH=205;
	$TABLE_WIDTH=701;
	$TABLE_HEIGHT=350;
	if(isset($_GET["expand"])){
		$expand="&expand=yes";
		$TABLE_WIDTH=868;
		$TABLE_HEIGHT=500;
		$DOMAIN_WITH=365;
	}
	$ou=$_GET["organization-local-domain-list"];
	$addfo="AddLocalDomain_form$t";
	if(trim($ou)==null){
		$addfo="AddLocalDomain_form2$t";
	}
	$buttons="
	buttons : [
	{name: '$add_local_domain', bclass: 'add', onpress : $addfo},
	{name: '$import_smtp_domains', bclass: 'add', onpress : import_smtp_domains},
	],";		
		
	
	
$html="
<center id='DOMAINLIST-$t' style='margin-bottom:5px'></center>	
<input type='hidden' id='ou' value='$ou'>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?organization-local-domain-list-search=yes&ou={$_GET["organization-local-domain-list"]}$expand',
	dataType: 'json',
	colModel : [
		{display: '$domain', name : 'domain', width : $DOMAIN_WITH, sortable : false, align: 'left'},
		{display: '$autoaliases', name : 'autoaliases', width : 40, sortable : false, align: 'left'},
		{display: 'Anti-Spam', name : 'as', width : 72, sortable : false, align: 'center'},
		{display: '$disclaimer', name : 'dis', width : 40, sortable : false, align: 'left'},				
		{display: '&nbsp;', name : 'description', width :225, sortable : false, align: 'left'},
		{display: '$delete;', name : 'delete', width : 32, sortable : false, align: 'left'},
		],
	$buttons
	searchitems : [
		{display: '$domain', name : 'domain'},
		],
	sortname: 'domain',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TABLE_WIDTH,
	height: $TABLE_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});


	function FlexReloadLocalDomainList(){
		var mastert=$master_t;
		if(mastert>0){ $('#table-$master_t').flexReload(); }
		$('#flexRT$t').flexReload();
		
	}
	
	var x_DeleteInternetDomainInside= function (obj) {
		document.getElementById('DOMAINLIST-$t').innerHTML='';
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		FlexReloadLocalDomainList();
		return;
	}
	
	var x_AddLocalDomain_form$t= function (obj) {
		document.getElementById('DOMAINLIST-$t').innerHTML='';
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		FlexReloadLocalDomainList();
	}
	
function import_smtp_domains(){
	Loadjs('domains.import.domains.php?ou=$ou');
}

function AddLocalDomain_form2$t(){
	Loadjs('$page?AddLocalDomain-Form-js=yes&t=$t');
}

function AddLocalDomain_form$t(){
	var ou='$ouescape';
	if(ou.lenth==0){Loadjs('$page?AddLocalDomain-Form-js=yes&t=$t');return;}
	var InternetDomainsAsOnlySubdomains=$InternetDomainsAsOnlySubdomains;
	if(InternetDomainsAsOnlySubdomains==1){
		Loadjs('domains.add.localdomain.restricted.php?ou=$ouescape&t=$t');
		return;
	}
	var domain=prompt('$add_local_domain_form_text');
	if(domain){
		var XHR = new XHRConnection();
		XHR.appendData('AddNewInternetDomain','$ouescape');
		XHR.appendData('AddNewInternetDomainDomainName',domain);
		AnimateDiv('DOMAINLIST-$t');		
		XHR.sendAndLoad('$page', 'GET',x_AddLocalDomain_form$t);
		}
	}	



	function DeleteInternetDomainInside(num){
			var mytext='$are_you_sure_to_delete';
			if(confirm(mytext+' '+num)){
				var XHR = new XHRConnection();
				XHR.appendData('DeleteInternetDomain',num);
				XHR.appendData('ou','$ou');
				AnimateDiv('DOMAINLIST-$t');	
				XHR.sendAndLoad('$page', 'GET',x_DeleteInternetDomainInside);	
			}
			
		}		


</script>

";	
	echo $html;	
}

function RELAY_DOMAINS_LIST_SEARCH(){
	$ldap=new clladp();	
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$ou=$_GET["ou"];
	include_once("ressources/class.amavis.inc");
	$amavis=new amavis();
	$amavis_oui=false;
	$t=$_GET["t"];
	
	writelogs("----------------> Hash_relay_domains",__FUNCTION__,__FILE__,__LINE__);	
	$HashDomains=$ldap->Hash_relay_domains($ou);
	$aliases=new AutoAliases($ou);

	$users->LoadModulesEnabled();
	if($users->AMAVIS_INSTALLED){if($users->EnableAmavisDaemon==1){$amavis_oui=true;}}
	$disclaimer=IS_DISCLAIMER();
	$tools=new DomainsTools();
	$domainstyle="font-size:26px";

	
	if($_POST["query"]<>null){$search=str_replace("*", ".*?", $_POST["query"]);}
	
$data = array();
	$c=0;
	$count=0;
	while (list ($num, $ligne) = each ($HashDomains) ){
		
		if($search<>null){if(!preg_match("#$search#", $num)){continue;}}
		$c++;
		$autoalias="&nbsp;";
		$disclaimer_domain="&nbsp;";
		$amavis_infos="&nbsp;";
		$amavis_duplicate="&nbsp;";
		$delete=imgtootltip("delete-32.png",'{label_delete_transport}',"DeleteRelayDomain$t('$num')");
		if($amavis->copy_to_domain_array[strtolower($num)]["enable"]==1){$amavis_duplicate="<strong style='font-size:12px'>{$amavis->copy_to_domain_array[strtolower($num)]["duplicate_host"]}:{$amavis->copy_to_domain_array[strtolower($num)]["duplicate_port"]}";}		
		$autoalias=$tpl->_ENGINE_parse_body($autoalias);
		$arr=$tools->transport_maps_explode($ligne);
		$alreadyDomain[$num]=true;
		$count++;
		$js="Loadjs('domains.relay.domains.php?domain=$num&ou=$ou')";
		$relay="{$arr[1]}:{$arr[2]}";
		if(strlen($aliases->DomainsArray[$num])>0){$autoalias="<img src='img/20-check.png'>";}
		if($arr[3]=="yes"){$mx="{yes}";}else{$mx="{no}";}
		if($amavis_oui){$amavis_infos=imgtootltip("24-parameters.png","AS -> $num","Loadjs('domains.amavis.php?domain=$num')");}	
		if($disclaimer){$disclaimer_domain=imgtootltip("24-parameters.png","disclaimer -> $num","Loadjs('domains.disclaimer.php?domain=$num&ou=$ou')");}
			
		
		
		
	$data['rows'][] = array(
		'id' => "dom-$num",
		'cell' => array("
		<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='$domainstyle;font-weight:bold;text-decoration:underline'>$num</span>",
		"<center style='$domainstyle'>$autoalias</center>",
		"<center style='$domainstyle'>$disclaimer_domain</center>",
		"<span style='$domainstyle'>$relay</span>",
		"<center>$delete</center>" )
		);
	}
	
	
	$dn="cn=transport_map,ou=$ou,dc=organizations,$ldap->suffix";
	$hash=$ldap->Ldap_search($dn,'(objectclass=transportTable)',array());
	for($i=0;$i<$hash["count"];$i++){
		
		$transport=$hash[$i]["transport"][0];
		$domain=$hash[$i]["cn"][0];
		if(isset($alreadyDomain[$domain])){continue;}
		if($search<>null){if(!preg_match("#$search#", $domain)){continue;}}
		$arr=$tools->transport_maps_explode($transport);
		$relay="{$arr[1]}:{$arr[2]}";
		$js="PostfixAddRoutingTable('$domain')";
		$count++;
		$data['rows'][] = array(
			'id' => "dom-$domain",
			'cell' => array("
			<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:22px;font-weight:bold;text-decoration:underline'>$domain</span>",
			"<span style='font-size:22px'>&nbsp;</span>",
			"<span style='font-size:22px'>&nbsp;</span>",
			"<span style='font-size:22px'>&nbsp;</span>",
			"<span style='font-size:22px'>$relay</span>",
			"&nbsp;" )
			);		
		
	
	}
		
	
	
	$data['page'] = 1;
	$data['total'] = $c;
	echo json_encode($data);		
	
	
}


function DOMAINSLIST_SEARCH(){
	$ldap=new clladp();	
	$page=CurrentPageName();
	$tpl=new templates();
	$ou=$_GET["ou"];
	include_once("ressources/class.amavis.inc");
	$amavis=new amavis();
	$amavis_oui=false;
	$disclaimer=true;
	$users=new usersMenus();
	$users->LoadModulesEnabled();
	if($users->AMAVIS_INSTALLED){if($users->EnableAmavisDaemon==1){$amavis_oui=true;}}
	$POSTFIX_INSTALLED=$users->POSTFIX_INSTALLED;
	$sock=new sockets();
	$disclaimer=IS_DISCLAIMER();	
	$HashDomains=$ldap->Hash_associated_domains($ou);
	if($GLOBALS["VERBOSE"]){echo count($HashDomains)." domains for this ou = $ou\n";}
	$aliases=new AutoAliases($ou);
	$search=string_to_regex($_POST["query"]);
	
	$domainstyle="font-size:16px";
	if(isset($_GET["expand"])){
		$domainstyle="font-size:18px";
	}	
	
$data = array();
	$c=0;
	while (list ($num, $ligne) = each ($HashDomains) ){
		
		if($search<>null){if(!preg_match("#$search#", $num)){continue;}}
		$c++;
		$autoalias="&nbsp;";
		$disclaimer_domain="&nbsp;";
		$amavis_infos="&nbsp;";
		$amavis_duplicate="&nbsp;";
		$js="Loadjs('domains.relay.domains.php?domain=$num&ou=$ou&local=yes')";
		$delete=imgtootltip("delete-24.png",'{label_delete_transport}',"DeleteInternetDomainInside('$num')");
		if(strlen($aliases->DomainsArray[$num])>0){$autoalias="<img src='img/20-check.png'>";}
		if($amavis_oui){$amavis_infos=imgtootltip("24-parameters.png","AS -> $num","Loadjs('domains.amavis.php?domain=$num')");}
		if($amavis->copy_to_domain_array[strtolower($num)]["enable"]==1){$amavis_duplicate="<strong style='font-size:12px'>{$amavis->copy_to_domain_array[strtolower($num)]["duplicate_host"]}:{$amavis->copy_to_domain_array[strtolower($num)]["duplicate_port"]}";}		
		if($disclaimer){$disclaimer_domain=imgtootltip("24-parameters.png","disclaimer -> $num","Loadjs('domains.disclaimer.php?domain=$num&ou=$ou')");}
		
		$autoalias=$tpl->_ENGINE_parse_body($autoalias);
		
		
	$data['rows'][] = array(
		'id' => "dom-$num",
		'cell' => array("
		<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='$domainstyle;font-weight:bold;text-decoration:underline'>$num</span>",
		"<span style='font-size:14px'>$autoalias</span>",
		"<span style='font-size:14px'>$amavis_infos</span>",
		"<span style='font-size:14px'>$disclaimer_domain</span>",
		"<span style='font-size:14px'>$amavis_duplicate</span>",
		$delete )
		);
	}
	
	if($c==0){json_error_show("No Internet domain...");}
	
	$data['page'] = 1;
	$data['total'] = $c;
		
	
	
echo json_encode($data);		
	
	
}

	
	







function AddNewInternetDomain(){
	$usr=new usersMenus();
	$tpl=new templates();
	if($usr->AllowChangeDomains==false){echo $tpl->_ENGINE_parse_body('{no_privileges}');exit;}		
	$tpl=new templates();
	$ou=$_GET["AddNewInternetDomain"];
	$domain=trim(strtolower($_GET["AddNewInternetDomainDomainName"]));
	$ldap=new clladp();
	$sock=new sockets();
	$InternetDomainsAsOnlySubdomains=$sock->GET_INFO("InternetDomainsAsOnlySubdomains");
	if($InternetDomainsAsOnlySubdomains==1){
		if(!$usr->OverWriteRestrictedDomains){
			$domaintbl=explode(".",$domain);
			$subdomain=$domaintbl[0];
			unset($domaintbl[0]);
			$domainsuffix=@implode(".",$domaintbl);	
			$sql="SELECT domain FROM officials_domains WHERE domain='$domainsuffix'";
			$q=new mysql();
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
			if(!$q->ok){echo $q->mysql_error;return;}
			if($ligne["domain"]==null){
				echo $tpl->_ENGINE_parse_body("{please_choose_an_official_domain}");
				return;
			}
		}
	}
	
	$hashdoms=$ldap->hash_get_all_domains();
	writelogs("hashdoms[$domain]={$hashdoms[$domain]}",__FUNCTION__,__FILE__);
	
	if($hashdoms[$domain]<>null){
		echo $tpl->_ENGINE_parse_body('{error_domain_exists}');
		exit;
	}
	
	
	
	if(!$ldap->AddDomainEntity($ou,$domain)){
		echo $ldap->ldap_last_error;
		return;
	}
		
			

	

	
	
}
	
function DeleteInternetDomain(){
	$usr=new usersMenus();
	$tpl=new templates();
	if($usr->AllowChangeDomains==false){echo $tpl->_ENGINE_parse_body('{no_privileges}');exit;}		
	
	$domain=$_GET["DeleteInternetDomain"];
	$ou=$_GET["ou"];
	$tpl=new templates();
	$artica=new artica_general();
	$ldap=new clladp();
	if($artica->RelayType=="single"){$ldap->delete_VirtualDomainsMapsMTA($ou,$domain);}
	$ldap->DeleteLocadDomain($domain,$ou);
	$sql="DELETE FROM postfix_duplicate_maps WHERE pattern='$domain'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	$q->QUERY_SQL("DELETE FROM domains`name`='$domain'","powerdns");

	$jb=new ejabberd($domain);
	$jb->Delete();
	
	
	
}
function AddNewRelayDomain(){
	$ou=$_GET["ou"];
	$tpl=new templates();
	$relayIP=$_GET["AddNewRelayDomainIP"];
	
	if($relayIP=="127.0.0.1"){
		echo $tpl->javascript_parse_text("{NO_RELAY_TO_THIS_SERVER_EXPLAIN}");
		return;
	}

		$tc=new networking();
		$IPSAR=$tc->ALL_IPS_GET_ARRAY();	
	
	if(!preg_match("#[0-9]\.[0-9]+\.[0-9]+\.[0-9]+#",$relayIP)){
		$ip=gethostbyname($relayIP);
		while (list ($ip1, $ip2) = each ($IPSAR)){
			if($relayIP==$ip1){
				echo $tpl->javascript_parse_text("{NO_RELAY_TO_THIS_SERVER_EXPLAIN}");
				return;
			}
		}
		
	}else{
		while (list ($ip1, $ip2) = each ($IPSAR)){
			if($relayIP==$ip1){
				echo $tpl->javascript_parse_text("{NO_RELAY_TO_THIS_SERVER_EXPLAIN}");
				return;
			}
		}		
	}
	
	
	
	$relayPort=$_GET["AddNewRelayDomainPort"];
	$mx=$_GET["MX"];
	$domain_name=trim(strtolower($_GET["AddNewRelayDomainName"]));
	$ldap=new clladp();
	if(!$ldap->UseLdap){
		$sqlite=new lib_sqlite();
		$sqlite->AddRelayDomain($ou,$domain_name,$relayIP,$relayPort,$mx);
		return;
	}
	
	
	$tpl=new templates();
	
	$trusted_smtp_domain=$_GET["trusted_smtp_domain"];
	$dn="cn=relay_domains,ou=$ou,dc=organizations,$ldap->suffix";
	$upd=array();
	if(!$ldap->ExistsDN($dn)){
		$upd['cn'][0]="relay_domains";
		$upd['objectClass'][0]='PostFixStructuralClass';
		$upd['objectClass'][1]='top';
		$ldap->ldap_add($dn,$upd);
		unset($upd);		
		}
	
	$hashdoms=$ldap->hash_get_all_domains();
	if($hashdoms[$domain_name]<>null){
		echo $tpl->_ENGINE_parse_body('{error_domain_exists}');
		exit;
	}
	
	

	
	$dn="cn=$domain_name,cn=relay_domains,ou=$ou,dc=organizations,$ldap->suffix";	
	
	$upd['cn'][0]="$domain_name";
	$upd['objectClass'][0]='PostFixRelayDomains';
	$upd['objectClass'][1]='top';
	$ldap->ldap_add($dn,$upd);	
	
	$dn="cn=relay_recipient_maps,ou=$ou,dc=organizations,$ldap->suffix";
	if(!$ldap->ExistsDN($dn)){
		$upd['cn'][0]="relay_recipient_maps";
		$upd['objectClass'][0]='PostFixStructuralClass';
		$upd['objectClass'][1]='top';
		$ldap->ldap_add($dn,$upd);
		unset($upd);		
		}	
	
	if($trusted_smtp_domain==0){	
		$dn="cn=@$domain_name,cn=relay_recipient_maps,ou=$ou,dc=organizations,$ldap->suffix";
		$upd['cn'][0]="@$domain_name";
		$upd['objectClass'][0]='PostfixRelayRecipientMaps';
		$upd['objectClass'][1]='top';
		$ldap->ldap_add($dn,$upd);
	}		
	
	$dn="cn=transport_map,ou=$ou,dc=organizations,$ldap->suffix";
	if(!$ldap->ExistsDN($dn)){
		$upd['cn'][0]="transport_map";
		$upd['objectClass'][0]='PostFixStructuralClass';
		$upd['objectClass'][1]='top';
		$ldap->ldap_add($dn,$upd);
		unset($upd);		
		}	
if($relayIP<>null){
	if($mx=="no"){$relayIP="[$relayIP]";}
	$dn="cn=$domain_name,cn=transport_map,ou=$ou,dc=organizations,$ldap->suffix";
	$upd['cn'][0]="$domain_name";
	$upd['objectClass'][0]='transportTable';
	$upd['objectClass'][1]='top';
	$upd["transport"][]="relay:$relayIP:$relayPort";
	$ldap->ldap_add($dn,$upd);			
	}
	
	
	

			
}




function EditRelayDomain(){
	$relayIP=$_GET["EditRelayDomainIP"];
	$relayPort=$_GET["EditRelayDomainPort"];
	$domain_name=$_GET["EditRelayDomainName"];
	$MX=$_GET["MX"];
	$ldap=new clladp();
	$ou=$_GET["ou"];
	$autoaliases=$_GET["autoaliases"];
	$trusted_smtp_domain=$_GET["trusted_smtp_domain"];
	
	$auto=new AutoAliases($ou);
	if($autoaliases=="yes"){
		$auto->DomainsArray[$domain_name]=$domain_name;
	}else{
		unset($auto->DomainsArray[$domain_name]);
	}
	$auto->Save();
	writelogs("saving relay:$relayIP:$relayPort trusted_smtp_domain=$trusted_smtp_domain",__FUNCTION__,__FILE__,__LINE__);
	$dn="cn=transport_map,ou=$ou,dc=organizations,$ldap->suffix";
	if(!$ldap->ExistsDN($dn)){
		$upd=array();
		$upd['cn'][0]="transport_map";
		$upd['objectClass'][0]='PostFixStructuralClass';
		$upd['objectClass'][1]='top';
		$ldap->ldap_add($dn,$upd);
		unset($upd);		
		}	
if($MX=="no"){$relayIP="[$relayIP]";}

$dn="cn=$domain_name,cn=transport_map,ou=$ou,dc=organizations,$ldap->suffix";		
if($ldap->ExistsDN($dn)){$ldap->ldap_delete($dn);}


	writelogs("Create $dn",__FUNCTION__,__FILE__);	
	$upd=array();
	$upd['cn'][0]="$domain_name";
	$upd['objectClass'][0]='transportTable';
	$upd['objectClass'][1]='top';
	$upd["transport"][]="relay:$relayIP:$relayPort";
	if(!$ldap->ldap_add($dn,$upd)){
		echo "Error\n"."Line: ".__LINE__."\n$ldap->ldap_last_error";
		return;
	}
	unset($upd);			
	
	$dn="cn=relay_recipient_maps,ou=$ou,dc=organizations,$ldap->suffix";
	if(!$ldap->ExistsDN($dn)){
			$upd=array();
			$upd['cn'][0]="relay_recipient_maps";
			$upd['objectClass'][0]='PostFixStructuralClass';
			$upd['objectClass'][1]='top';
				if(!$ldap->ldap_add($dn,$upd)){
					echo "Error\n"."Line: ".__LINE__."\n$ldap->ldap_last_error";
					return;
				}
			unset($upd);		
			}
		
	
	
	$dn="cn=@$domain_name,cn=relay_recipient_maps,ou=$ou,dc=organizations,$ldap->suffix";
	if($ldap->ExistsDN($dn)){$ldap->ldap_delete($dn);}	
	if($trusted_smtp_domain==1){
		$upd=array();
		$upd['cn'][0]="@$domain_name";
		$upd['objectClass'][0]='PostfixRelayRecipientMaps';
		$upd['objectClass'][1]='top';
		if(!$ldap->ldap_add($dn,$upd)){
			echo "Error\n"."Line: ".__LINE__."\n$ldap->ldap_last_error";
			return;
		}
	}
	
	$sock=new sockets();
	$usr=new usersMenus();
	$sock->getFrameWork("cmd.php?postfix-transport-maps=yes");	
	
}
function DeleteRelayDomainName(){
	$ou=$_GET["ou"];
	$domain_name=$_GET["DeleteRelayDomainName"];
	$ldap=new clladp();
	$ldap->DeleteRemoteDomain($domain_name,$ou);
	

}
?>	
