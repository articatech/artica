<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["ICON_FAMILY"]="POSTFIX";
	if(posix_getuid()==0){die();}
	session_start();
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');

	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if($_GET["organizations"]){organizations();exit;}
	if(isset($_GET["organization-list"])){organization_list();exit;}
	if(isset($_GET["ou-field"])){field_organization();exit;}
	if(isset($_GET["localdomain-js"])){localdomain_js();exit;}
	if(isset($_GET["localdomain-popup"])){localdomain_popup();exit;}
	if(isset($_GET["localdomain-delete-js"])){localdomain_delete_js();exit;}
	
	
	if(isset($_GET["relaydomain-js"])){relaydomain_js();exit;}
	if(isset($_GET["relaydomain-popup"])){relaydomain_popup();exit;}
	if(isset($_GET["relaydomain-delete-js"])){relaydomain_delete_js();exit;}	
	if(isset($_GET["all-domains-parameters"])){all_domains_parameters();exit;}
	if(isset($_POST["PostfixLocalDomainToRemote"])){PostfixLocalDomainToRemoteSave();exit;}
	main_tabs();
	
function main_tabs(){
	$users=new usersMenus();
	$tpl=new templates();
	$sock=new sockets();
	$hostname=$_GET["hostname"];
	if($hostname==null){$hostname="master";}
	$page=CurrentPageName();
	$fontsize="font-size:20px;";
	$array["organizations"]='{domains}';
	$POSTFIX_MAIN=base64_encode("POSTFIX_MAIN");
	
	$array["smtp_generic_maps"]="{smtp_generic_maps}";
	$array["relayhost"]="{relayhost_title}";
	$array["relay-sender-table"]='{senders}';
	$array["smtp-dest-table"]='{recipients}';
	$array["tls"]='{tls_table}';
	$array["mailboxes"]='{mailboxes}';
	$array["smtp-artica-sync"]='{artica_sync}';
	
	
	

	
	while (list ($num, $ligne) = each ($array) ){
		if($num=="relay-sender-table"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"postfix.routing.sender.table.php?hostname=$hostname\">
					<span style='$fontsize'>$ligne</span></a></li>\n");
			continue;
		}
		if($num=="smtp_generic_maps"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"postfix.smtp.generic.maps.php?ou=$POSTFIX_MAIN\"><span style='$fontsize'>$ligne</span></a></li>\n");
			continue;
		}
		if($num=="mailboxes"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"postfix.routing.lmtp.php?hostname=$hostname\"><span style='$fontsize'>$ligne</span></a></li>\n");
			continue;
		}	

		if($num=="relayhost"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"postfix.routing.relayhost.php?hostname=$hostname\"><span style='$fontsize'>$ligne</span></a></li>\n");
			continue;
		}		
		
		if($num=="smtp-artica-sync"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"postfix.routing.table.php?relay-recipient-table=yes&hostname=$hostname\"><span style='$fontsize'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="tls"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"postfix.tls.table.php?hostname=$hostname\"><span style='$fontsize'>$ligne</span></a></li>\n");
			continue;
		}		

		if($num=="smtp-dest-table"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"postfix.routing.recipient.php?hostname=$hostname\"><span style='$fontsize'>$ligne</span></a></li>\n");
			continue;
		}

		if($num=="diff-dest-table"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"postfix.routing.diffusion.php?hostname=$hostname\"><span style='$fontsize'>$ligne</span></a></li>\n");
			continue;
		}			
		
		
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&hostname=$hostname\"><span style='$fontsize'>$ligne</span></a></li>\n");
	}
	
	
	echo build_artica_tabs($html, "main_config_postfixrt_table",1450).
	"<script>LeftDesign('earth-256-transp-opac20.png');</script>";
			
}	
function relaydomain_js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$title=$_GET["domain"];
	if($_GET["domain"]==null){
		$title=$tpl->_ENGINE_parse_body("{add_relay_domain}");
		$html="YahooWin3(990,'$page?relaydomain-popup=yes&t={$_GET["t"]}','$title');";
	
	}else{
		$html="Loadjs('domains.edit.domains.php?remote-domain-add-js=yes&ou='+ou+'&index={$_GET["domain"]})";
	}
	echo $html;
}

function localdomain_js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$title=$_GET["domain"];
	if($_GET["domain"]==null){$title=$tpl->_ENGINE_parse_body("{add_local_domain}");}
	$html="YahooWin3(513,'$page?localdomain-popup=yes&t={$_GET["t"]}&callback={$_GET["callback"]}','$title');";
	echo $html;
}

function localdomain_delete_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ldap=new clladp();	
	$domain=$_GET["domain"];
	$ou=$ldap->organization_name_from_localdomain($domain);
	if($ou==null){echo "alert('".$tpl->javascript_parse_text("{no_organization_found_for_this_domain}")."');";return;}
	$confirm=$tpl->javascript_parse_text("{delete_this_domain} `$domain` {from} $ou ?");
	$t=$_GET["t"];
	$m5=md5($domain);
	
	$html=
	"
	var x_DelLocalDomain$t= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
		if(!document.getElementById('rowdom$m5')){alert('rowdom$m5 ni such id');}
		$('#rowdom$m5').remove();
	}	
	
	if(confirm('$confirm')){
		var XHR = new XHRConnection();
		XHR.appendData('DeleteInternetDomain','$domain');
		XHR.appendData('ou','$ou');		
		XHR.sendAndLoad('domains.edit.domains.php', 'GET',x_DelLocalDomain$t);
	}
	";
	echo $html;	
	
}

function relaydomain_delete_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ldap=new clladp();	
	$domain=$_GET["domain"];
	$ou=$ldap->organization_name_from_transporttable($domain);
	if($ou==null){echo "alert('".$tpl->javascript_parse_text("{no_organization_found_for_this_domain}")."');";return;}
	$confirm=$tpl->javascript_parse_text("{delete_this_domain} `$domain` {from} $ou ?");
	$t=$_GET["t"];
	$m5=md5($domain);
	
	$html=
	"
	var x_DelRDomain$t= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
		if(!document.getElementById('rowdom$m5')){alert('rowdom$m5 ni such id');}
		$('#rowdom$m5').remove();
	}	
	
	if(confirm('$confirm')){
		var XHR = new XHRConnection();
		XHR.appendData('DeleteRelayDomainName','$domain');
		XHR.appendData('ou','$ou');		
		XHR.sendAndLoad('domains.edit.domains.php', 'GET',x_DelRDomain$t);
	}
	";
	echo $html;		
	
}



function field_organization(){
	$t=$_GET["t"];
	$ldap=new clladp();
	$ORG=$ldap->hash_get_ou(true);
	$ORG[null]='{select}';
	$organization=Field_array_Hash($ORG,"org-$t",$orgfound,"style:font-size:22px;padding:3px");
	echo $organization;
}

function relaydomain_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ldap=new clladp();	
	
	$ORG[null]='{select}';
	ksort($ORG);	
	$t=$_GET["t"];
	$callback=null;
	$callback=null;
	$error_please_select_an_organization=$tpl->javascript_parse_text("{error_please_select_an_organization}");
	if(isset($_GET["callback"])){
		if(trim($_GET["callback"])<>null){ $callback="{$_GET["callback"]}();";}
	}	
	
	
	$add_new_organisation_text=$tpl->javascript_parse_text("{add_new_organisation_text}");
	$html="
	<div id='wiat-$t'></div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{organization}:</td>
		<td><span id='oufield-$t'></span></td>
		<td>". button("{create}", "TreeAddNewOrganisation$t()",16)."</td>
	<tr>
		<td class=legend style='font-size:22px'>{domain}:</td>
		<td colspan=2>". Field_text("domain-$t",null,"font-size:22px;width:550px;padding:3px;border:2px solid #717171",null,null,null,false,"AddRemoteDomainC$t(event)")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{destination}:</td>
		<td colspan=2>". Field_text("dest-$t",null,"font-size:22px;width:550px;padding:3px;border:2px solid #717171",null,null,null,false,"AddRemoteDomainC$t(event)")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{port}:</td>
		<td colspan=2>". Field_text("port-$t",25,"font-size:22px;width:110px;padding:3px;border:2px solid #717171",null,null,null,false,"AddRemoteDomainC$t(event)")."</td>
	</tr>	
	<tr>
		<td colspan=3 align='right'>
			<hr>". button("{add}","AddRemoteDomain$t()",30)."</td>
	</tr>
	</table>
	</div>
	<script>
	var x_AddRemote$t= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			YahooWin3Hide();
			$callback
			$('#flexRT$t').flexReload();
		}	
		
	function AddRemoteDomainC$t(e){
		if(checkEnter(e)){AddRemoteDomain$t();}
	}
	
	
	function AddRemoteDomain$t(){
		var domain=document.getElementById('domain-$t').value;
		var ou=document.getElementById('org-$t').value;
		var dest=document.getElementById('dest-$t').value;
		if(domain.length<2){return;}
		if(ou<2){
			alert('$error_please_select_an_organization');
			return;
		}
		if(dest<2){return;}
		AnimateDiv('wiat-$t');
		var XHR = new XHRConnection();
		XHR.appendData('ou',ou);
		XHR.appendData('AddNewRelayDomainName',domain);
		XHR.appendData('AddNewRelayDomainIP',document.getElementById('dest-$t').value);
		XHR.appendData('AddNewRelayDomainPort',document.getElementById('port-$t').value);
		XHR.sendAndLoad('domains.edit.domains.php', 'GET',x_AddRemote$t);
	}
	var x_TreeAddNewOrganisation$t= function (obj) {
		var response=obj.responseText;
		if(response){alert(response);}
		LoadAjaxSilent('oufield-$t','$page?ou-field=yes&t=$t');
	}	

	function TreeAddNewOrganisation$t(){
		var texte='$add_new_organisation_text'
				var org=prompt(texte,'');
		if(org){
			var XHR = new XHRConnection();
			XHR.appendData('TreeAddNewOrganisation',org);
			XHR.sendAndLoad('domains.php', 'GET',x_TreeAddNewOrganisation$t);
		}
	}	
	
LoadAjaxSilent('oufield-$t','$page?ou-field=yes&t=$t');
</script>
";
echo $tpl->_ENGINE_parse_body($html);
}

function localdomain_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ldap=new clladp();	
	$ORG=$ldap->hash_get_ou(true);
	$ORG[null]='{select}';
	ksort($ORG);	
	$t=$_GET["t"];
	$callback=null;
	if(isset($_GET["callback"])){
		if(trim($_GET["callback"])<>null){ $callback="{$_GET["callback"]}();";}
	}	
	$organization=Field_array_Hash($ORG,"org-$t",null,"style:font-size:18px;padding:3px");
	
	$html="
	<div id='wiat-$t'></div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{organization}:</td>
		<td>$organization</td>	
	<tr>
		<td class=legend style='font-size:18px'>{domain}:</td>
		<td>". Field_text("domain-$t",null,"font-size:18px;width:300px;padding:3px;border:2px solid #717171",null,null,null,false,"AddLocalDomainC$t(event)")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'>
			<hr>". button("{add}","AddLocalDomain$t()",24)."</td>
	</tr>
	</table>
	</div>
	<script>
	var x_AddLocalDomain$t= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			YahooWin3Hide();
			$callback
			$('#flexRT$t').flexReload();
		}	
		
	function AddLocalDomainC$t(e){
		if(checkEnter(e)){AddLocalDomain$t();}
	}
	
	
	function AddLocalDomain$t(){
		var domain=document.getElementById('domain-$t').value;
		var ou=document.getElementById('org-$t').value;
		if(domain.length<2){return;}
		if(ou<2){return;}
		AnimateDiv('wiat-$t');
		var XHR = new XHRConnection();
		XHR.appendData('AddNewInternetDomain',ou);
		XHR.appendData('AddNewInternetDomainDomainName',domain);		
		XHR.sendAndLoad('domains.edit.domains.php', 'GET',x_AddLocalDomain$t);
	}
</script>
";
echo $tpl->_ENGINE_parse_body($html);
}


function organizations(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$t=time();
	$ou=$_GET["ou"];
	$domain=$tpl->_ENGINE_parse_body("{domains}");
	$about=$tpl->javascript_parse_text("{about2}");
	$are_you_sure_to_delete=$tpl->javascript_parse_text("{are_you_sure_to_delete}");
	$autoaliases=$tpl->javascript_parse_text("{autoaliases}");
	$disclaimer=$tpl->javascript_parse_text("{disclaimer}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$InternetDomainsAsOnlySubdomains=$sock->GET_INFO("InternetDomainsAsOnlySubdomains");
	if(!is_numeric($InternetDomainsAsOnlySubdomains)){$InternetDomainsAsOnlySubdomains=0;}
	$add_local_domain_form_text=$tpl->javascript_parse_text("{add_local_domain_form}");
	$add_local_domain=$tpl->_ENGINE_parse_body("{add_local_domain}");
	$add_relay_domain=$tpl->_ENGINE_parse_body("{add_relay_domain}");
	$parameters=$tpl->_ENGINE_parse_body("{parameters}");
	$ouescape=urlencode($ou);
	$destination=$tpl->javascript_parse_text("{destination}");
	$hostname=$_GET["hostname"];
	$apply=$tpl->javascript_parse_text("{apply}");
	$add_local_domain=$tpl->_ENGINE_parse_body("{add_local_domain}");
	$add_remote_domain=Paragraphe("64-remotedomain-add.png",'{add_relay_domain}','{add_relay_domain_text}',
	"javascript:AddRemoteDomain_form(\"$ou\",\"new domain\")","add_relay_domain",210);
	
	
	$localdomainButton="{name: '<strong style=font-size:18px>$add_local_domain</strong>', bclass: 'add', onpress : add_local_domain$t},";
	$parametersButton="{name: '<strong style=font-size:18px>$parameters</strong>', bclass: 'Settings', onpress : parameters$t},";
	$aboutButton="{name: '<strong style=font-size:18px>$about</strong>', bclass: 'Help', onpress : About$t},";
	$applybutton="{name: '<strong style=font-size:18px>$apply</strong>', bclass: 'Reconf', onpress : Apply$t},";
	
	
	
	
	$LOCAL_MDA=false;
	if($users->cyrus_imapd_installed){$LOCAL_MDA=true;}
	if($users->ZARAFA_INSTALLED){$LOCAL_MDA=true;}
	if(!$LOCAL_MDA){$localdomainButton=null;}
	$buttons="
	buttons : [
	$localdomainButton
	{name: '<strong style=font-size:18px>$add_relay_domain</strong>', bclass: 'add', onpress : add_relay_domain$t},$parametersButton$applybutton$aboutButton
	],";		
		
	$explain=$tpl->javascript_parse_text("{postfix_transport_table_explain}");
	
$html="
<input type='hidden' id='ou' value='$ou'>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?organization-list=yes&hostname=$hostname&t=$t',
	dataType: 'json',
	colModel : [
		{display: '<span style=font-size:22px>$domain</span>', name : 'domain', width : 546, sortable : true, align: 'left'},
		{display: '<span style=font-size:22px>$destination</span>', name : 'description', width :572, sortable : true, align: 'left'},
		{display: '<span style=font-size:22px>$delete</span>', name : 'delete', width : 162, sortable : false, align: 'center'},
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
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

function About$t(){
	alert('$explain');
}
function Apply$t(){
	Loadjs('postfix.transport.progress.php');
}

	function add_relay_domain$t(){
		Loadjs('$page?relaydomain-js=yes&domain=&t=$t');
	}
	
	function add_local_domain$t(){
		Loadjs('$page?localdomain-js=yes&domain=&t=$t');
	}
	
	function DeleteLocalDomain$t(domain){
		Loadjs('$page?localdomain-delete-js=yes&domain='+domain+'&t=$t');
	}
	
	function DeleteTransportDomain$t(domain){
		Loadjs('$page?relaydomain-delete-js=yes&domain='+domain+'&t=$t');
	}
	
	function parameters$t(){
		YahooWin4('550','$page?all-domains-parameters=yes&t=$t','$parameters');
	}


</script>
";
	
	echo $html;
		
	
}

function organization_list(){
	$ldap=new clladp();
	$tpl=new templates();
	$tools=new DomainsTools();
	$t=$_GET["t"];
	$localdomains=$ldap->Hash_associated_domains();
	$relaydomains=$ldap->Hash_relay_domains();
	$sock=new sockets();
	$PostfixLocalDomainToRemote=$sock->GET_INFO("PostfixLocalDomainToRemote");
	if(!is_numeric($PostfixLocalDomainToRemote)){$PostfixLocalDomainToRemote=0;}
	$PostfixLocalDomainToRemoteAddr=$sock->GET_INFO("PostfixLocalDomainToRemoteAddr");	
	$forwared=$tpl->_ENGINE_parse_body("{forwarded}");
	
	while (list ($domain, $ligne) = each ($localdomains) ){
		$array[$domain]["DELETE"]="DeleteLocalDomain$t('$domain')";
		$ou=$ldap->organization_name_from_localdomain($domain);
		if($ou==null){$ou="{none}";}
		$array[$domain]["TEXT"]="<span style='font-size:18px'>{localdomain}</span><br><span style='font-size:16px'>{organization}: <strong>$ou</strong>";
		if($PostfixLocalDomainToRemote==1){
			$array[$domain]["TEXT"]="<span style='font-size:18px'>{localdomain}</span><br><span style='font-size:16px'>{organization}: <strong>$ou</strong></span><br>$forwared -&raquo; smtp:$PostfixLocalDomainToRemoteAddr ";
		}
		
	}
	
	while (list ($domain, $ligne) = each ($relaydomains) ){
			$arr=$tools->transport_maps_explode($ligne);
			$ou=$ldap->organization_name_from_transporttable($domain);
			$array[$domain]["TEXT"]="{$arr[1]}:{$arr[2]} ($ou)";
			$array[$domain]["OU"]=$ou;
			$array[$domain]["DELETE"]="DeleteTransportDomain$t('$domain')";
			
	}
		
	$data = array();
	if($_POST["query"]<>null){$search=string_to_regex($_POST["query"]);}
	$c=0;
	
	if($_POST["sortorder"]=="desc"){krsort($array);}else{ksort($array);}
	
	while (list ($domain, $ligne) = each ($array) ){
		if($search<>null){if(!preg_match("#$search#", $domain)){continue;}}
		$c++;
		$ligne["TEXT"]=$tpl->_ENGINE_parse_body($ligne["TEXT"]);
		$domainenc=urlencode($domain);
		$OuEnc=urlencode($ligne["OU"]);
		$delete=imgsimple("delete-48.png",'{label_delete_transport}',$ligne["DELETE"]);
		$m5=md5($domain);
		$js="Loadjs('domains.relay.domains.php?domain=$domainenc&ou=$OuEnc')";
		
	$data['rows'][] = array(
		'id' => "dom$m5",
		'cell' => array("
		<a href=\"javascript:blur();\" 
			OnClick=\"javascript:$js\" 
			style='font-size:24px;font-weight:bold;text-decoration:underline'>$domain</span>",
		"<span style='font-size:24px'>{$ligne["TEXT"]}</span>",
		"<center>$delete</center>" )
		);

		if($c>$_POST["rp"]){break;}
		
	}

	if($c==0){json_error_show("no data");}
	$data['page'] = 1;
	$data['total'] = $c;
	echo json_encode($data);	

}

function all_domains_parameters(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$PostfixLocalDomainToRemote=$sock->GET_INFO("PostfixLocalDomainToRemote");
	if(!is_numeric($PostfixLocalDomainToRemote)){$PostfixLocalDomainToRemote=0;}
	$PostfixLocalDomainToRemoteAddr=$sock->GET_INFO("PostfixLocalDomainToRemoteAddr");
	$html="
	<div id='wiat-$t'></div>
	<table style='width:99%' class=form>
	<tr>
	<td class=legend style='font-size:14px'>{localdomains_to_remote_server}:</td>
	<td>". Field_checkbox("PostfixLocalDomainToRemote", 1,$PostfixLocalDomainToRemote,"CheckPostfixLocalDomainToRemote()")."</td>
	</tr>
	<td class=legend style='font-size:14px'>{remote_server}:</td>
	<td>". Field_text("PostfixLocalDomainToRemoteAddr", $PostfixLocalDomainToRemoteAddr,"font-size:14px;width:220px")."</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","PostfixLocalDomainToRemoteSave()","16px")."</td>
	</tr>
	</table>
<script>
	var x_PostfixLocalDomainToRemoteSave$t= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			$('#flexRT$t').flexReload();
			YahooWin4Hide();
		}	
		
	function CheckPostfixLocalDomainToRemote(){
		var PostfixLocalDomainToRemote=0;
		if(document.getElementById('PostfixLocalDomainToRemote').checked){PostfixLocalDomainToRemote=1;}
		document.getElementById('PostfixLocalDomainToRemoteAddr').disabled=true;
		if(PostfixLocalDomainToRemote==1){
			document.getElementById('PostfixLocalDomainToRemoteAddr').disabled=false;
		}
	}
	
	
	function PostfixLocalDomainToRemoteSave(){
		var PostfixLocalDomainToRemote=0;
		if(document.getElementById('PostfixLocalDomainToRemote').checked){PostfixLocalDomainToRemote=1;}
		AnimateDiv('wiat-$t');
		var XHR = new XHRConnection();
		XHR.appendData('PostfixLocalDomainToRemote',PostfixLocalDomainToRemote);
		XHR.appendData('PostfixLocalDomainToRemoteAddr',document.getElementById('PostfixLocalDomainToRemoteAddr').value);
		XHR.sendAndLoad('$page', 'POST',x_PostfixLocalDomainToRemoteSave$t);
	}
	
	
	CheckPostfixLocalDomainToRemote();
</script>	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function PostfixLocalDomainToRemoteSave(){
	$sock=new sockets();
	$sock->SET_INFO("PostfixLocalDomainToRemote", $_POST["PostfixLocalDomainToRemote"]);
	$sock->SET_INFO("PostfixLocalDomainToRemoteAddr", $_POST["PostfixLocalDomainToRemoteAddr"]);
	
}