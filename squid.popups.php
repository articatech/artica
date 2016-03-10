<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.tcpip.inc');
	
	$user=new usersMenus();

	if($user->SQUID_INSTALLED==false){
		if(!$user->WEBSTATS_APPLIANCE){
			$tpl=new templates();
			echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
			die();exit();
		}
	}
	
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	if(isset($_GET["x-save-plugins"])){x_save_plugins();exit;}
	if(isset($_POST["positive_dns_ttl"])){dns_popup_cache_save();exit;}
	if($_GET["script"]=="network"){echo network_js();exit;}
	if($_GET["script"]=="listen_port"){echo listen_port_js();exit;}
	if($_GET["script"]=="visible_hostname"){echo visible_hostname_js();exit;}
	if($_GET["script"]=="ldap"){echo ldap_js();exit;}
	if($_GET["script"]=="dns"){echo dns_js();exit;}
	
	
	//plugins
	if($_GET["script"]=="plugins"){echo plugins_js();exit;}
	if($_GET["content"]=="plugins"){echo plugins_popup();exit;}
	if(isset($_GET["enable_plugins"])){plugins_save();exit;}
	
	
	if($_GET["script"]=="url_regex"){echo url_regex_js();exit;}
	if(isset($_GET["url_regex_list"])){echo url_regex_popup_list();exit;}
	
	
	if($_GET["script"]=="user-agent-ban"){echo user_agent_ban_js();exit;}
	if(isset($_GET["user-agent-ban"])){echo user_agent_ban_popup();exit;}
	if(isset($_GET["user-agent-ban-db"])){echo user_agent_ban_index();exit;}		
	if(isset($_GET["user-agent-ban-search"])){user_agent_ban_search();exit;}
	if(isset($_GET["user-agent-ban-add"])){user_agent_ban_add();exit;}
	if(isset($_GET["user-agent-ban-list"])){user_agent_ban_list();exit;}
	if(isset($_GET["EnableUserAgentBanAll"])){user_agent_ban_enable();exit;}
	if(isset($_GET["UserAgentBanDeleteDB"])){user_agent_ban_delete();exit;}
	
	
	
	
	if($_GET["content"]=="dns"){echo dns_popup();exit;}
	if($_GET["content"]=="network"){echo network_popup();exit;}
	if($_GET["content"]=="listen_port"){echo listen_port_popup();exit;}
	if($_GET["content"]=="visible_hostname"){echo visible_hostname_popup();exit;}
	
	if($_GET["content"]=="listen_port_tab"){echo listen_port_popup_tabs();exit;}
	if(isset($_GET["browsers-setup"])){listen_port_browsers();exit;}
	
	if($_GET["content"]=="ldap_auth"){echo ldap_auth_index();exit;}
	if($_GET["content"]=="ldap_local"){echo ldap_auth_popup();exit;}
	if($_GET["content"]=="ldap_remote"){echo ldap_auth_remote();exit;}
	
	if($_GET["content"]=="url_regex"){echo url_regex_popup();exit;}
	if($_GET["content"]=="url_regex_list"){echo url_regex_popup_list();exit;}
	if($_GET["content"]=="url_regex_import"){url_regex_popup_import();exit;}
	
	
	if($_GET["blocksites"]=="deny"){url_regex_popup1();exit;}
	if($_GET["blocksites"]=="MalwarePatrol"){url_regex_MalwarePatrol_popup();exit;}
	if(isset($_GET["EnableMalwarePatrol"])){url_regex_MalwarePatrol_save();exit;}
	if(isset($_GET["malware-patrol-list"])){url_regex_MalwarePatrol_list();exit;}
	
	
	if($_GET["content"]=="auth-wl"){auth_whitelist_popup();exit;}
	if(isset($_GET["auth-wl-list"])){auth_whitelist_list();exit;}
	if(isset($_GET["auth-wl-add"])){auth_whitelist_add();exit;}
	if(isset($_GET["auth-wl-del"])){auth_whitelist_del();exit;}
	
	if($_GET["content"]=="auth-wl-useragents"){auth_whitelist_useragent_popup();exit;}
	if(isset($_GET["auth-wl-useragents-list"])){auth_whitelist_useragent_list();exit;}
	if(isset($_GET["auth-wl-add-useragents"])){auth_whitelist_useragent_add();exit;}
	if(isset($_GET["auth-wl-del-useragents"])){auth_whitelist_useragent_del();exit;}
	
	
	
	if(isset($_GET["addipfrom"])){CalculCDR();exit;}
	if(isset($_GET["add-ip-single"])){network_add_single();exit;}
	if(isset($_GET["SquidnetMaskCheckIP"])){network_calculate_cdir();exit;}
	
	
	
	if(isset($_GET["NetDelete"])){network_delete();exit;}
	if(isset($_GET["listenport"])){listen_port_save();exit;}
	if(isset($_GET["visible_hostname_save"])){visible_hostname_save();exit;}
	
	if(isset($_GET["ldap_auth"])){ldap_auth_save();exit;}
	if(isset($_GET["ntlm_auth"])){ldap_ntlm_auth_save();exit;}
	if(isset($_POST["EnableSquidExternalLDAP"])){ldap_external_auth_save();exit;}
	
	
	if(isset($_GET["nameserver"])){dns_add();exit();}
	if(isset($_GET["DnsDelete"])){dns_del();exit();}
	if(isset($_GET["standard_dns"])){dns_popup_index();exit;}
	if(isset($_GET["dns_cache"])){dns_popup_cache();exit;}
	
	
	
	
	
	
	
	
	if(isset($_GET["enable_plugins"])){plugins_save();exit;}
	if(isset($_GET["website_block"])){url_regex_save();exit;}
	if(isset($_GET["website_block_delete"])){url_regex_del();exit;}
	if(isset($_GET["force-upgrade-squid"])){force_upgrade_squid();exit;}
	if(isset($_POST["DenyWebSiteImportPerform"])){url_regex_popup_import_receive();exit;}
	if(isset($_GET["AllowAllNetworksInSquid"])){AllowAllNetworksInSquid_save();exit;}


	
	function network_js(){
		$page=CurrentPageName();
		$tpl=new templates();
		$your_network=$tpl->_ENGINE_parse_body("{your_network}");
		header("content-type: application/x-javascript");
		echo "				
		YahooWin2(500,'squid.network.php?popup=yes','$your_network','');
		
		
		var x_netadd= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);}
			YahooWin2(500,'$page?content=network','$your_network');
			if(document.getElementById('main_squid_quicklinks_config')){RefreshTab('main_squid_quicklinks_config');}
			if(document.getElementById('squid_main_config')){RefreshTab('squid_main_config');}
			
		}
		
		function netadd(){
			var XHR = new XHRConnection();
			XHR.appendData('addipfrom',document.getElementById('from_ip').value);
			XHR.appendData('addipto',document.getElementById('to_ip').value);
			document.getElementById('squid_network_id').innerHTML='<center style=\"width:100%\"><img src=img/wait_verybig.gif></center>';
			XHR.sendAndLoad('$page', 'GET',x_netadd);	
		}
		
		function NetDelete(num){
			var XHR = new XHRConnection();
			XHR.appendData('NetDelete',num);
			document.getElementById('squid_network_id').innerHTML='<center style=\"width:100%\"><img src=img/wait_verybig.gif></center>';
			XHR.sendAndLoad('$page', 'GET',x_netadd);	
		}
		
		function SquidnetaddCheck(e){
			if(checkEnter(e)){netadd();}
		}
		
		function SquidnetaddSingleCheck(e){
			if(checkEnter(e)){SquidnetaddSingle();}
		}
		
		function SquidnetaddSingle(){
			var XHR = new XHRConnection();
			XHR.appendData('add-ip-single',document.getElementById('FREE_FIELD').value);
			document.getElementById('squid_network_id').innerHTML='<center style=\"width:100%\"><img src=img/wait_verybig.gif></center>';
			XHR.sendAndLoad('$page', 'GET',x_netadd);	
		}		

		
		
		";
		
	}
	
	
function ldap_auth_save(){
		$squid=new squidbee();	
		$tpl=new templates();
		$squid->LDAP_AUTH=$_GET["ldap_auth"];

		
		
		
		if(isset($_GET["SquidLdapAuthBanner"])){
			$sock=new sockets();
			$sock->SET_INFO("SquidLdapAuthBanner", url_decode_special_tool($_GET["SquidLdapAuthBanner"]));
		}
		
		$squid->SquidLdapAuthEnableGroups=0;
		if(!$squid->SaveToLdap()){
			echo $squid->ldap_error;
			exit;
		}
	}
	
function ldap_external_auth_save(){
	if($_GET["EnableSquidExternalLDAP"]==1){$squid->LDAP_AUTH=1;}
	$squid=new squidbee();	
	$_POST["ldap_password"]=url_decode_special_tool($_POST["ldap_password"]);
	$squid->LDAP_EXTERNAL_AUTH=$_POST["EnableSquidExternalLDAP"];
	$squid->EXTERNAL_LDAP_AUTH_PARAMS=$_POST;
	$squid->SaveToLdap();
}

	
function ldap_ntlm_auth_save(){
		$squid=new squidbee();	
		$squid->NTLM_AUTH=$_GET["ntlm_auth"];
		if($squid->NTLM_AUTH==1){$squid->LDAP_AUTH=0;}
		if(!$squid->SaveToLdap()){
			echo $squid->ldap_error;
			return;
		}
}


function dns_popup_cache(){
	$tpl=new templates();
	$page=CurrentPageName();
	$squid=new squidbee();
	$SquidAppendDomain=1;
	$t=time();
	$sock=new sockets();
	$SquidAppendDomainDisabled=intval($sock->GET_INFO("SquidAppendDomainDisabled"));
	$SquidIpv6DNSPrio=intval($sock->GET_INFO("SquidIpv6DNSPrio"));
	if($SquidAppendDomainDisabled==1){$SquidAppendDomain=0;}
	$balance_on_multiple_ip=intval($sock->GET_INFO("balance_on_multiple_ip"));
	
	$AppendDomain=Paragraphe_switch_img("{enable_append_domain}", "{squid_enable_append_domain}",
			"SquidAppendDomain-$t",$SquidAppendDomain,null,1400);
	
	
	$IpV4Prio=1;
	if($SquidIpv6DNSPrio==1){
		$IpV4Prio=0;
	}
	$SquidIpv6DNSPrio=Paragraphe_switch_img("{squid_ipv4_dns_prio}", "{squid_ipv4_dns_prio_explain}",
			"SquidIpv6DNSPrio-$t",$IpV4Prio,null,1400);
	
	
	$balance_on_multiple_ip_p=Paragraphe_switch_img("{balance_on_multiple_ip}", "{balance_on_multiple_ip_text}",
			"balance_on_multiple_ip-$t",$balance_on_multiple_ip,null,1400);
	
	
	
	$SquidEnablePinger=intval($sock->GET_INFO("SquidEnablePinger"));
	
	$fqdncache_size=$squid->global_conf_array["fqdncache_size"];
	$ipcache_size=$squid->global_conf_array["ipcache_size"];
	$ipcache_low=$squid->global_conf_array["ipcache_low"];
	$ipcache_high=$squid->global_conf_array["ipcache_high"];
	$positive_dns_ttl=$squid->global_conf_array["positive_dns_ttl"];
	$negative_dns_ttl=$squid->global_conf_array["negative_dns_ttl"];
	
	if(preg_match("#([0-9]+)\s+#", $positive_dns_ttl,$re)){$positive_dns_ttl=$re[1];}
	if(preg_match("#([0-9]+)\s+#", $negative_dns_ttl,$re)){$negative_dns_ttl=$re[1];}
	
	$html="
	<div id='$t' class=form style='width:98%'>
	$AppendDomain<br>$SquidIpv6DNSPrio<br>$balance_on_multiple_ip_p
	<table style='width:100%' >
	<tbody>
	<tr>
		<td class=legend style='font-size:24px'>{enable_pinger_process}:</td>
		<td style='font-size:18px'>". Field_checkbox_design("SquidEnablePinger-$t",1,$SquidEnablePinger)."<td>
		<td style='font-size:18px' width=1%>". help_icon("{enable_pinger_process_text}")."<td>
	</tr>	
	<tr>
		<td class=legend style='font-size:24px'>{positive_dns_ttl}:</td>
		<td style='font-size:18px'>". Field_text("positive_dns_ttl-$t",$positive_dns_ttl,"font-size:24px;width:130px")."&nbsp;{hours}<td>
		<td style='font-size:18px' width=1%>". help_icon("{positive_dns_ttl_text}")."<td>
	</tr>	
	<tr>
		<td class=legend style='font-size:24px'>{negative_dns_ttl}:</td>
		<td style='font-size:18px'>". Field_text("negative_dns_ttl-$t",$negative_dns_ttl,"font-size:24px;width:130px")."&nbsp;{seconds}<td>
		<td style='font-size:18px' width=1%>". help_icon("{negative_dns_ttl_text}")."<td>
	</tr>	
	
	
	<tr>
		<td class=legend style='font-size:24px'>{fqdncache_size}:</td>
		<td style='font-size:18px'>". Field_text("fqdncache_size-$t",$fqdncache_size,"font-size:24px;width:130px")."&nbsp;{items}<td>
		<td style='font-size:18px' width=1%>". help_icon("{fqdncache_size_text}")."<td>
	</tr>
	<tr>
		<td class=legend style='font-size:24px'>{ipcache_low}:</td>
		<td style='font-size:18px'>". Field_text("ipcache_low-$t",$ipcache_low,"font-size:24px;width:130px")."&nbsp;%<td>
		<td style='font-size:18px' width=1%>". help_icon("{ipcache_low_text}")."<td>
	</tr>
	<tr>
		<td class=legend style='font-size:24px'>{ipcache_high}:</td>
		<td style='font-size:18px'>". Field_text("ipcache_high-$t",$ipcache_high,"font-size:24px;width:130px")."&nbsp;%<td>
		<td style='font-size:18px' width=1%>". help_icon("{ipcache_high_text}")."<td>
	</tr>			
	<tr>
		<td class=legend style='font-size:24px'>{ipcache_size}:</td>
		<td style='font-size:18px'>". Field_text("ipcache_size-$t",$ipcache_size,"font-size:24px;width:130px")."&nbsp;{items}<td>
		<td style='font-size:18px' width=1%>". help_icon("{ipcache_size_text}")."<td>
	</tr>	
	
	
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","SaveSquidDNSPerfs()",42)."</td>
	</tr>
	</tbody>
	</table>
	</div>
<script>
	var x_SaveSquidDNSPerfs=function (obj) {
		var tempvalue=obj.responseText;
		RefreshTab('main_config_squiddns{$_GET["t"]}');
		Loadjs('squid.restart.php?onlySquid=yes&ApplyConfToo=yes&ask=yes',true);
	}	
	
	function SaveSquidDNSPerfs(){
		var XHR = new XHRConnection();
		SquidEnablePinger=0;
		if( document.getElementById('SquidEnablePinger-$t').checked){SquidEnablePinger=1;}
		XHR.appendData('SquidIpv6DNSPrio',document.getElementById('SquidIpv6DNSPrio-$t').value);
		XHR.appendData('SquidAppendDomain',document.getElementById('SquidAppendDomain-$t').value);
		XHR.appendData('positive_dns_ttl',document.getElementById('positive_dns_ttl-$t').value);
		XHR.appendData('negative_dns_ttl',document.getElementById('negative_dns_ttl-$t').value);
		XHR.appendData('fqdncache_size',document.getElementById('fqdncache_size-$t').value);
		XHR.appendData('ipcache_low',document.getElementById('ipcache_low-$t').value);
		XHR.appendData('ipcache_high',document.getElementById('ipcache_high-$t').value);
		XHR.appendData('ipcache_size',document.getElementById('ipcache_size-$t').value);
		XHR.appendData('balance_on_multiple_ip',document.getElementById('balance_on_multiple_ip-$t').value);
		XHR.appendData('SquidEnablePinger',SquidEnablePinger);
		XHR.sendAndLoad('$page', 'POST',x_SaveSquidDNSPerfs);	
	}		
</script>	
";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function dns_popup_cache_save(){
	$squid=new squidbee();
	$sock=new sockets();
	
	
	
	
	$squid->global_conf_array["fqdncache_size"]=$_POST["fqdncache_size"];
	$squid->global_conf_array["ipcache_size"]=$_POST["ipcache_size"];
	$squid->global_conf_array["ipcache_low"]=$_POST["ipcache_low"];
	$squid->global_conf_array["ipcache_high"]=$_POST["ipcache_low"];
	$squid->global_conf_array["positive_dns_ttl"]=$_POST["positive_dns_ttl"]." hours";
	$squid->global_conf_array["negative_dns_ttl"]=$_POST["negative_dns_ttl"]." seconds";
	
	$sock->SET_INFO("SquidEnablePinger", $_POST["SquidEnablePinger"]);
	$sock->SET_INFO("balance_on_multiple_ip", $_POST["balance_on_multiple_ip"]);
	
	
	
	if($_POST["SquidIpv6DNSPrio"]==1){
		$sock->SET_INFO("SquidIpv6DNSPrio", 0);
	}else{
		$sock->SET_INFO("SquidIpv6DNSPrio", 1);
	}
	
	if($_POST["SquidAppendDomain"]==1){
		$sock->SET_INFO("SquidAppendDomainDisabled", 0);
	}else{
		$sock->SET_INFO("SquidAppendDomainDisabled", 1);
	}
	$squid->SaveToLdap();	
	
}
	
	

		
function dns_js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	echo "YahooWin2(750,'$page?content=dns','DNS servers...',true);";
}
		
function user_agent_ban_js(){
$page=CurrentPageName();
$tpl=new templates();
header("content-type: application/x-javascript");
$title=$tpl->_ENGINE_parse_body("{ban_browsers}");
echo "YahooWin2(600,'$page?user-agent-ban=yes','$title',true);";
}
		
		
		
function url_regex_js(){
		$page=CurrentPageName();
		$tpl=new templates();
		$import=$tpl->_ENGINE_parse_body("{import}");
		$title=$tpl->_ENGINE_parse_body("{deny_websites}");
		echo "
		function url_regex_js_start(){
			YahooWin2(650,'$page?content=url_regex','$title');
		}
		
		function url_regex_js_list(){
			var adduri='';
			if(document.getElementById('SearchDenyWebSitePattern')){
				adduri='&SearchDenyWebSitePattern='+document.getElementById('SearchDenyWebSitePattern').value
			}
			LoadAjax('squid-block-list','$page?content=url_regex_list'+adduri);
		}
		
		var x_DenyWebSiteAdd= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			url_regex_js_list();
			
		}
		
		function DenyWebSiteAdd(){
			var XHR = new XHRConnection();
			XHR.appendData('website_block',document.getElementById('website_block').value);
			if(document.getElementById('SquidAutoblock').checked){
			XHR.appendData('SquidAutoblock',1);}else{XHR.appendData('SquidAutoblock',0);}
			
			
			XHR.sendAndLoad('$page', 'GET',x_DenyWebSiteAdd);	
		}
		
		function DenyWebSiteDel(num){
			var XHR = new XHRConnection();
			XHR.appendData('website_block_delete',num);
			XHR.sendAndLoad('$page', 'GET',x_DenyWebSiteAdd);	
		}
		
		var x_EnableMalwarePatrol= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			RefreshTab('main_config_denywbl');
			}		
		
		function EnableMalwarePatrol(){
			var XHR = new XHRConnection();
			XHR.appendData('EnableMalwarePatrol',document.getElementById('EnableMalwarePatrol').value);
			document.getElementById('img_EnableMalwarePatrol').src='img/wait_verybig.gif';
			XHR.sendAndLoad('$page', 'GET',x_EnableMalwarePatrol);
		}
		
		function DenyWebSiteImport(){
			YahooWin3(550,'$page?content=url_regex_import','$import...','');
		}
		
		var x_DenyWebSiteImportPerform= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			RefreshTab('main_config_denywbl');
			YahooWin3Hide();
			}			
		
		function DenyWebSiteImportPerform(){
			var lisr=document.getElementById('url_regex_popup_import').value;
			var XHR = new XHRConnection();
			XHR.appendData('DenyWebSiteImportPerform',lisr);
			document.getElementById('url_regex_popup_import_div').innerHTML='<center style=\"width:100%\"><img src=img/wait_verybig.gif></center>';
			XHR.sendAndLoad('$page', 'POST',x_DenyWebSiteImportPerform);
		}
		
		function SearchDenyWebSitePatternEnter(e){
			if(!checkEnter(e)){return;}
			url_regex_js_list();
		}
		
		url_regex_js_start();";	
	
}


function url_regex_MalwarePatrol_save(){
	$sock=new sockets();
	$sock->SET_INFO("EnableMalwarePatrol",$_GET["EnableMalwarePatrol"]);
	$sock->getFrameWork("cmd.php?MalwarePatrol=yes");
	$squid=new squidbee();
	$squid->SaveToLdap();
	
}

function url_regex_MalwarePatrol_popup(){
	
	$page=CurrentPageName();
	$sock=new sockets();
	$EnableMalwarePatrol=$sock->GET_INFO("EnableMalwarePatrol");
	$MalwarePatrolDatabasesCount=$sock->getFrameWork("cmd.php?MalwarePatrolDatabasesCount=yes");
	
	$text="<hr><strong>{database_entries_number}:$MalwarePatrolDatabasesCount</strong>";
	
	$EnableMalwarePatrol=Paragraphe_switch_img("{EnableMalwarePatrol}","{MalwarePatrol_text}$text","EnableMalwarePatrol",$EnableMalwarePatrol,null,430);
	
	$html="
	<table style='width:100%'>
	<tr>
	<td >$EnableMalwarePatrol</td>
	<td valign='middle'>".button("{apply}","EnableMalwarePatrol()")."</td>
	</tr>
	</table>
	<hr>
	<table style='width:100%;margin-top:10px;'>
	<tr>
		<td class=legend style='font-size:16px'>{search}:</td>
		<td>". Field_text("MalwarePatrolSearch",null,"font-size:16px;padding:3px",null,null,null,false,"RefreshPatternPatrolListCheck(event)")."</td>
	</tr>
	</table>	
	
	<div style='width:100%;margin-top:10px;padding:3px;height:290px;overflow:auto' id='malwarepatroldb'></div>
	

	
	<script>
	function RefreshPatternPatrolListCheck(e){
		if(checkEnter(e)){RefreshPatternPatrolList();}
	}
	
	
	function RefreshPatternPatrolList(){
			var pattern=escape(document.getElementById('MalwarePatrolSearch').value);
			LoadAjax('malwarepatroldb','$page?malware-patrol-list=yes&pattern='+pattern);
		}
		
	RefreshPatternPatrolList();
	</script>";
	
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html,'squid.index.php');	
	
}

function  url_regex_MalwarePatrol_list(){
	$search=base64_encode($_GET["pattern"]);
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->getFrameWork("cmd.php?MalwarePatrol-list=yes&pattern=$search")));
	if(!is_array($datas)){return null;}
	
	while (list ($num, $ligne) = each ($datas) ){
	if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		
		$tr=$tr."
		<tr class=$classtr>
			<td width=1%><img src='img/fw_bold.gif'></td>
			<td><strong style='font-size:14px'>$ligne</td>
		</tr>
		";
		
	}	
	
$html= $html."
<table cellspacing='0' cellpadding='0' border='0' class='tableView'>
<thead class='thead'>
	<tr>
	
	<th colspan=2>{websites}</th>
	</tr>
</thead>
<tbody class='tbody'>$tr</tbody></table>";

$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);
	
	
}

function url_regex_popup_import(){
	
	$html="<p style='font-size:16px'>{url_regex_popup_import_explain}</p>
	<div id='url_regex_popup_import_div'>
	<textarea id='url_regex_popup_import' style='width:99%;height:450px;overflow:auto'></textarea>
	<div style='text-align:right'>
		<hr>
			". button("{import}","DenyWebSiteImportPerform()")."
	</div>
	</div>";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}

function url_regex_popup_import_receive(){
	$datas=explode("\n",$_POST["DenyWebSiteImportPerform"]);
	if(!is_array($datas)){return null;}
	$q=new mysql();
	while (list ($num, $ligne) = each ($datas) ){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		if(substr($ligne,0,1)=="#"){continue;}
		
		$sql="INSERT INTO squid_block(uri,task_type,zDate) VALUES('$ligne','admin',NOW());";
		$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n".$sql."\n";return ;}
	}

	$sock=new sockets();
	$sock->getFrameWork("cmd.php?squidnewbee=yes");		
	
}



function url_regex_popup_list(){
	if(trim($_GET["SearchDenyWebSitePattern"])<>null){
		$pattern=trim($_GET["SearchDenyWebSitePattern"])."%";
		$pattern=str_replace("*","%",$pattern);
		$pattern=str_replace("%%","%",$pattern);
		$pattern="WHERE uri LIKE '$pattern'";
		
	}
	$sql="SELECT * FROM squid_block $pattern ORDER BY uri LIMIT 0,50";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	
	$style=CellRollOver();
	$html="
	
	<table style='width:100%'>
	<tr>
		<td colspan=3>
			<table style='width:100%'>
			<tr>
				<td><strong>{search}:</strong></td>
				<td>
				". Field_text("SearchDenyWebSitePattern",$_GET["SearchDenyWebSitePattern"],
				"font-size:16px;padding:3px",
				null,null,null,false,"SearchDenyWebSitePatternEnter(event)")."
				</td>
			</tr>
		</table>
	<tr>
		<th>&nbsp;</th>
		<th>{website}</th>
		<th>&nbsp;</th>
	</tr>";
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
$tooltip="{$ligne["zDate"]}:<br>{$ligne["task_type"]}";
		
		$html=$html."
		<tr ".CellRollOver().">
			<td width=1%><img src='img/fw_bold.gif'></td>
			<td><strong style='font-size:12px'>".texttooltip("{$ligne["uri"]}",$tooltip)."</td>
			<td width=1%>". imgtootltip('ed_delete.gif','{delete}',"DenyWebSiteDel({$ligne["ID"]})")."</td>
		</tr>";
		
	}
	
	$html=$html . "</table>";
	$tpl=new templates();
	return $tpl->_ENGINE_parse_body($html);
	
}

function url_regex_popup(){
	$page=CurrentPageName();
	$array["deny"]='{deny_websites}';
	$array["MalwarePatrol"]='{MalwarePatrol}';
	$tpl=new templates();
	while (list ($num, $ligne) = each ($array) ){
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?blocksites=$num\"><span>$ligne</span></a></li>\n");
	}
	
	
	return "
	<div id=main_config_denywbl style='width:100%;height:550px;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_denywbl').tabs({
				    load: function(event, ui) {
				        $('a', ui.panel).click(function() {
				            $(ui.panel).load(this.href);
				            return false;
				        });
				    }
				});
			
			
			});
		</script>";		
	
}
function user_agent_ban_popup(){
	$page=CurrentPageName();
	$array["user-agent-ban-db"]='{database}';
	$array["user-agent-ban-list"]='{useragent}::{rule}';
	$tpl=new templates();
	while (list ($num, $ligne) = each ($array) ){
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span>$ligne</span></a></li>\n");
	}
	
	
	return "
	<div id=main_config_denyUagnt style='width:100%;height:550px;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_denyUagnt').tabs({
				    load: function(event, ui) {
				        $('a', ui.panel).click(function() {
				            $(ui.panel).load(this.href);
				            return false;
				        });
				    }
				});
			
			
			});
		</script>";		
	
}


function user_agent_ban_index(){
	$page=CurrentPageName();
	$squid=new squidbee();
	$html="
	
	<div class=explain>{ban_browsers_explain}</div>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:14px'>{enable_useragent_ban_rule}:</td>
		<td>". Field_checkbox("EnableUserAgentBanAll",1,$squid->EnableUserAgentBanAll,"EnableUserAgentBanAll()")."</td>
	</tr>
	</table>	
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:14px'>{search}:</td>
		<td>". Field_text("UserAgentSearch",null,"font-size:16px;width:100%;padding:3px",
	null,null,null,false,"UserAgentSearchPress(event)")."</td>
	</tr>
	</table>
	<hr>
	<div id='user_agent_ban_popup' style='width:100%;height:350px;overflow:auto'></div>
	
	
	<script>
		var mem_ban_key='';
		function UserAgentSearchPress(e){
			if(checkEnter(e)){
				UserAgentSearch();
			}
		}
		
		function UserAgentSearch(){
			var s=escape(document.getElementById('UserAgentSearch').value);
			LoadAjax('user_agent_ban_popup','$page?user-agent-ban-search='+s);
			
		}
		
		var x_AddBanUserAgent= function (obj) {
			var results=obj.responseText;
			if(results.length>0){
				alert(results);
				document.getElementById(mem_ban_key).checked=false;
				return;
			}
			document.getElementById('id_'+mem_ban_key).innerHTML='';
			}			
		
		function AddBanUserAgent(key){
			var XHR = new XHRConnection();
			mem_ban_key=key;
			if(document.getElementById(mem_ban_key).checked){
				XHR.appendData('user-agent-ban-add',key);
				XHR.sendAndLoad('$page', 'GET',x_AddBanUserAgent);
			}
		}
		
		var x_EnableUserAgentBanAll= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);return;}
		}		
		
		function EnableUserAgentBanAll(){
		  var XHR = new XHRConnection();
		  if(document.getElementById('EnableUserAgentBanAll').checked){
				XHR.appendData('EnableUserAgentBanAll',1);
			}else{
				XHR.appendData('EnableUserAgentBanAll',0);
			}
			XHR.sendAndLoad('$page', 'GET',x_EnableUserAgentBanAll);
		}
		
		
	UserAgentSearch();	
	</script>
	
	
	";
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body($html,'squid.index.php');		
	
}

	
function user_agent_ban_enable(){
	$squid=new squidbee();
	$squid->EnableUserAgentBanAll=$_GET["EnableUserAgentBanAll"];
	$squid->SaveToLdap();
	}
function user_agent_ban_delete(){
	$sql="DELETE FROM squid_white WHERE ID='{$_GET["UserAgentBanDeleteDB"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		echo $q->mysql_error;
		return ;
	}

	$squid=new squidbee();
	$squid->SaveToLdap();	
}

		
	

function user_agent_ban_add(){
	$key=$_GET["user-agent-ban-add"];
	$sql="SELECT `string` FROM UserAgents WHERE unique_key='{$key}'";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	if($ligne["string"]==null){
		echo "$key failed\n";
		return;
	}
	
	$sql="INSERT INTO squid_white(uri,task_type,zDate) VALUES('{$ligne["string"]}','USER_AGENT_BAN_WHITE',NOW())";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		echo $q->mysql_error;
		return ;
	}
	
	$squid=new squidbee();
	$squid->SaveToLdap();	
}

function user_agent_ban_list(){
	
	$page=CurrentPageName();
	$sql="SELECT * FROM squid_white WHERE task_type='USER_AGENT_BAN_WHITE' ORDER BY ID DESC";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	
	$html="
	
	<div class=explain>{ban_browsers_explain2}</div>
	<table class=tableView style='width:99%'>
				<thead class=thead>
				<tr>
					<th width=1% nowrap colspan=3>&nbsp;</td>
				</tr>
				</thead>";		
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($cl=="oddRow"){$cl=null;}else{$cl="oddRow";}
		
		$html=$html."
		<tr class=$cl>
			<td width=1% nowrap><code style='font-size:10px'><img src='img/fw_bold.gif'></code></td>
			<td width=99%><code style='font-size:10px'>{$ligne["uri"]}</td>
			<td width=1%>". imgtootltip("delete-24.png","{delete}","UserAgentBanDeleteDB('{$ligne["ID"]}')")."</td>
		</tr>";
		}
		
	$html=$html."</table>
	
	<script>
		var x_UserAgentBanDeleteDB= function (obj) {
			var results=obj.responseText;
			if(results.length>0){
				alert(results);
				return;
			}
			RefreshTab('main_config_denyUagnt');
		}
	
	
		function UserAgentBanDeleteDB(ID){
 			var XHR = new XHRConnection();
		  	XHR.appendData('UserAgentBanDeleteDB',ID);
			XHR.sendAndLoad('$page', 'GET',x_UserAgentBanDeleteDB);		
		}
	
	</script>
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
}
	
function user_agent_ban_search(){
	$query="*".$_GET["user-agent-ban-search"]."*";
	$query=str_replace("**","*",$query);
	$q=new mysql();
	$query=$q->mysql_real_escape_string2($query);
	$query=str_replace("*","%",$query);
	
	$limit=50;
	if(strlen($query)>2){$limit=150;}
	$sql="SELECT * FROM UserAgents WHERE string LIKE '$query' ORDER BY browser,string LIMIT 0,50";

	$results=$q->QUERY_SQL($sql,"artica_backup");
	
	$html="<table class=tableView style='width:99%'>
				<thead class=thead>
				<tr>
					<th width=1% nowrap colspan=3>&nbsp;</td>
				</tr>
				</thead>";		
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($cl=="oddRow"){$cl=null;}else{$cl="oddRow";}
		
		$html=$html."
		<tr class=$cl>
			<td width=1% nowrap><code style='font-size:10px'>{$ligne["browser"]}</code></td>
			<td width=99%><code style='font-size:10px'>{$ligne["string"]}</td>
			<td width=1%><span id='id_{$ligne["unique_key"]}'>". Field_checkbox("{$ligne["unique_key"]}",1,0,"AddBanUserAgent('{$ligne["unique_key"]}')")."</span></td>
		</tr>";
		}
		
	$html=$html."</table>";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}


function url_regex_popup1(){
		$sock=new sockets();
		$SquidAutoblock=$sock->GET_INFO("SquidAutoblock");
		if($SquidAutoblock==null){$SquidAutoblock=0;}
		$autoblock=
		$form="
		<table style='width:100%'>
			<tr>
			<td class=legend nowrap>{autoblock}:</td>
			<td>" .Field_checkbox("SquidAutoblock",1,$SquidAutoblock)."</td>
			</tr>
			<tr>
			<td class=legend nowrap>{deny_website_label}:</td>
			<td>" . Field_text('website_block',null,'width:100%;font-size:12px;padding:4px')."</td>
			</tr>			
			<tr>
			<td align='left'>". button("{import}","DenyWebSiteImport()")."</td>
			<td align='right'>
			". button("{add}","DenyWebSiteAdd();")."
		
			</tr>
		</table>";
		
		
		
		
		$html="
			<div class=explain>{deny_websites_explain}</div>
				$form
			<br>
			<div id='squid-block-list' style='with:100%;height:300px;overflow:auto'></div>
			<script>url_regex_js_list()</script>";	
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body($html,'squid.index.php');			
	
}

function url_regex_save(){
	
	$sock=new sockets();
	$sock->SET_INFO("SquidAutoblock",$_GET["SquidAutoblock"]);
	if($_GET["website_block"]==null){return;}
	$sql="INSERT INTO squid_block(uri,task_type,zDate)
	VALUES('{$_GET["website_block"]}','admin',NOW());";
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n".$sql."\n";return ;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?squidnewbee=yes");	
	}

function url_regex_del(){
	$num=$_GET["website_block_delete"];
	$sql="DELETE FROM squid_block WHERE ID=$num";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?squidnewbee=yes");
	}
	
	
function dns_popup_index(){
$tpl=new templates();
$t=time();	
		
		$html="
			<div id='$t'></div>
			<script>
				LoadAjax('$t','squid.dns.servers.php');
			</script>
			
			";
		
		
		echo $tpl->_ENGINE_parse_body($html,'squid.index.php');	
}






function dns_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	
	$array["standard_dns"]='{dns_nameservers}';
	$array["dns_cache"]='{settings}';
	$array["dns_status"]='{status}';
	
	$array["dns_query"]="{dns_query}";
	$t=time();

	while (list ($num, $ligne) = each ($array) ){
		if($num=="booster"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"squid.dnsmasq.php?popup=yes&t=$t\">
					<span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="dns_status"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"squid.dns.status.php?t=$t\"><span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}	

		if($num=="dn_entries"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"squid.dns.items.php?t=$t\"><span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}	

		if($num=="dns_query"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"system.dns.query.php?popup=yes&t=$t\"><span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}		
		
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&t=$t\"><span style='font-size:22px'>$ligne</span></a></li>\n");
	}
	
	echo build_artica_tabs($html, "main_config_squiddns$t",1490);
		
}





function ldap_js(){
		$page=CurrentPageName();
		$tpl=new templates();
		header("content-type: application/x-javascript");
		$title=$tpl->javascript_parse_text("{authenticate_users}");
		echo "
		function ldapauth_display(){
			YahooWin2(995,'$page?content=ldap_auth','$title');
		}
		
		
		var x_ldapauth= function (obj) {
			var results=trim(obj.responseText);
			if(results.length>0){alert(results);}
			ldapauth_display();
			Loadjs('squid.restart.php?onlySquid=yes&ask=yes');
		}
		
		function ldapauth(){
			var SquidLdapAuthEnableGroups=0;
			var XHR = new XHRConnection();
			XHR.appendData('ldap_auth',document.getElementById('ldap_auth').value);
			
			if( document.getElementById('SquidLdapAuthBanner') ) {
				var pp=encodeURIComponent(document.getElementById('SquidLdapAuthBanner').value);
				XHR.appendData('SquidLdapAuthBanner',pp);
			}
			
			
			XHR.sendAndLoad('$page', 'GET',x_ldapauth);	
		}
		
		function ntlmpauth(){
			var XHR = new XHRConnection();
			XHR.appendData('ntlm_auth',document.getElementById('ntlm_auth').value);
			XHR.sendAndLoad('$page', 'GET',x_ldapauth);			
		}
		

		
		function ForceUpgradeSquid(){
			var XHR = new XHRConnection();
			XHR.appendData('force-upgrade-squid','yes');
			XHR.sendAndLoad('$page', 'GET',x_ldapauth);		
		}

		ldapauth_display();
";}


function ldap_auth_index(){

	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	
	$array["ldap_local"]='{local_ldap}';
	$array["ldap_remote"]='{remote_database}';
//	$array["auth-wl"]='{whitelist}::{websites}';	
	//$array["auth-wl-useragents"]='{whitelist}::{useragent}';
	
	if($users->EnableManageUsersTroughActiveDirectory){unset($array["ldap_remote"]);}
	
	
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?content=$num\"><span style='font-size:18px'>$ligne</span></a></li>\n");
	}
	
	
	echo build_artica_tabs($html, "main_squid_auth");
	
}

function ldap_auth_remote(){
	$squid=new squidbee();
	$users=new usersMenus();	
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	
	if(trim($users->SQUID_LDAP_AUTH)==null){
		$form_ldap="	
			<table style='width:100%'>
				<tr>
				<td >" . Paragraphe_switch_disable("{authenticate_users}","{authenticate_users_no_binaries}",null,300)."</td>
				<td  ></td>
				</tr>
			</table>";
		echo $tpl->_ENGINE_parse_body($form_ldap);
		return;	
	}	
	
	$ldap_server=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_server"];
	$ldap_port=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_port"];
	$userdn=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_user"];
	$ldap_password=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_password"];
	$ldap_suffix=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_suffix"];
	$ldap_filter_users=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_users"];
	$ldap_filter_group=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_group"];
	$ldap_server=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_server"];
	$auth_banner=$squid->EXTERNAL_LDAP_AUTH_PARAMS["auth_banner"];		
	
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	$SquidLdapAuthBanner=$sock->GET_INFO("SquidLdapAuthBanner");
	if($SquidLdapAuthBanner==null){$SquidLdapAuthBanner="Basic credentials, Please logon...";}
	if($EnableKerbAuth==1){
		$error="<p class=text-error>{ldap_with_ad_explain}</p>";
	}
	
	$EnableSquidExternalLDAP=$squid->LDAP_EXTERNAL_AUTH;
	if($auth_banner==null){$auth_banner=$SquidLdapAuthBanner;}
	
	if($ldap_filter_users==null){$ldap_filter_users="sAMAccountName=%s";}
	if($ldap_filter_group==null){$ldap_filter_group="(&(objectclass=person)(sAMAccountName=%u)(memberof=*))";}
	
	if($ldap_port==null){$ldap_port=389;}
	$html="$error
	<div class=explain style='font-size:16px'>{SQUID_LDAP_AUTH_EXT}</div>
	
	<div id='ldap_ext_auth' style='width:98%' class=form>
		<table style='width:99%' class=TableRemove>
	<tr>
		<td  style='font-size:16px' class=legend>{activate}:</td>
		<td>". Field_checkbox("EnableSquidExternalLDAP",1,$EnableSquidExternalLDAP,"EnableSquidExternalLDAP()")."</td>
	</tr>		
	<tr>
		<td  style='font-size:16px' class=legend>{hostname}:</td>
		<td>". Field_text("ldap_server",$ldap_server,"font-size:16px;padding:3px")."</td>
	</tr>
	<tr>
		<td  style='font-size:16px' class=legend>{listen_port}:</td>
		<td>". Field_text("ldap_port",$ldap_port,"font-size:16px;padding:3px")."</td>
	</tr>	
	<tr>
		<td  style='font-size:16px' class=legend>{auth_banner}:</td>
		<td>". Field_text("auth_banner",$auth_banner,"font-size:16px;padding:3px")."</td>
	</tr>	
	
	<tr>
		<td  style='font-size:16px' class=legend>{userdn}:</td>
		<td>". Field_text("ldap_user",$userdn,"font-size:16px;padding:3px")."</td>
	</tr>
	<tr>
		<td  style='font-size:16px' class=legend>{ldap_password}:</td>
		<td>". Field_password("ldap_password",$ldap_password,"font-size:16px;padding:3px")."</td>
	</tr>
	<tr><td colspan=2><hr></tD></tr>
	<tr>
		<td  style='font-size:16px' class=legend>{ldap_suffix}:</td>
		<td>". Field_text("ldap_suffix",$ldap_suffix,"font-size:16px;padding:3px")."</td>
	</tr>		
	<tr>
		<td  style='font-size:16px' class=legend>{ldap_filter_users}:</td>
		<td>". Field_text("ldap_filter_users",$ldap_filter_users,"font-size:16px;padding:3px")."</td>
	</tr>	
	<tr>
		<td  style='font-size:16px' class=legend>{ldap_filter_group}:</td>
		<td>". Field_text("ldap_filter_group",$ldap_filter_group,"font-size:16px;padding:3px;width:600px")."</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'>
			<hr>
				". button("{apply}","SaveExternalLDAPSYS()",18)."</td>
	</tr>
	</table>
	</div>
	
	
	<script>
		function EnableSquidExternalLDAP(){
			var disabled=false;
			if(!document.getElementById('EnableSquidExternalLDAP').checked){disabled=true;}
			document.getElementById('ldap_server').disabled=disabled;
			document.getElementById('ldap_port').disabled=disabled;
			document.getElementById('ldap_user').disabled=disabled;
			document.getElementById('ldap_password').disabled=disabled;
			document.getElementById('ldap_suffix').disabled=disabled;
			document.getElementById('ldap_filter_users').disabled=disabled;
			document.getElementById('ldap_filter_group').disabled=disabled;
			document.getElementById('auth_banner').disabled=disabled;
			
			
			}
			
	var x_SaveExternalLDAPSYS= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		RefreshTab('main_squid_auth');
		Loadjs('squid.restart.php?onlySquid=yes&ask=yes');
	}				
			
		function SaveExternalLDAPSYS(){
			var XHR = new XHRConnection();
			var enable=1;
			if(!document.getElementById('EnableSquidExternalLDAP').checked){enable=0;}
			XHR.appendData('EnableSquidExternalLDAP',enable);
			XHR.appendData('ldap_server',document.getElementById('ldap_server').value);
			XHR.appendData('ldap_port',document.getElementById('ldap_port').value);
			XHR.appendData('ldap_user',document.getElementById('ldap_user').value);
			XHR.appendData('ldap_password',encodeURIComponent(document.getElementById('ldap_password').value));
			XHR.appendData('ldap_suffix',document.getElementById('ldap_suffix').value);
			XHR.appendData('ldap_filter_users',document.getElementById('ldap_filter_users').value);
			XHR.appendData('ldap_filter_group',document.getElementById('ldap_filter_group').value);
			XHR.appendData('auth_banner',document.getElementById('auth_banner').value);
			XHR.sendAndLoad('$page', 'POST',x_SaveExternalLDAPSYS);		
			}
			
	
		EnableSquidExternalLDAP();
	</script>
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

		
function ldap_auth_popup(){
	$squid=new squidbee();
	$users=new usersMenus();
	$sock=new sockets();
	$SquidLdapAuthEnableGroups=$sock->GET_INFO("SquidLdapAuthEnableGroups");
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	$SquidLdapAuthBanner=$sock->GET_INFO("SquidLdapAuthBanner");
	if($SquidLdapAuthBanner==null){$SquidLdapAuthBanner="Basic credentials, Please logon...";}
	if($EnableKerbAuth==1){
		$error="<p class=text-error>{ldap_with_ad_explain}</p>";
	}
	
	
	//SquidLdapAuthEnableGroups
	$form_ldap="	
			$error
		<div style='width:98%' class=form>
		<table style='width:99%' class=TableRemove>
			<tr>
			<td >" . Paragraphe_switch_img(
					"{authenticate_users}","{authenticate_users_explain}",
					'ldap_auth',$squid->LDAP_AUTH,'{enable_disable}',850)."
			</td>
			</tr>
			<tr>
			<td>
				<table style='width:100%' class=TableRemove>
				<tr>
					<td style='font-size:16px' class=legend>{banner}:</td>
					<td>". Field_text("SquidLdapAuthBanner", $SquidLdapAuthBanner,"font-size:16px;width:350px")."</td>
				</tr>							
				</table>
			</td>
			</tr>
			<tr>
				<td   align='right'><hr>". button("{apply}","ldapauth()",18)."</td>
			</tr>
		</table>			
		";
	
	
		
		

if(trim($users->SQUID_LDAP_AUTH)==null){
	$form_ldap="	
		<table style='width:100%'>
			<tr>
			<td >" . Paragraphe_switch_disable("{authenticate_users}","{authenticate_users_no_binaries}",null,300)."</td>
			<td  ></td>
			</tr>
		</table>";
	
}

	

$html="$form_ldap";
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html,'squid.index.php');		
}

function x_save_plugins(){
echo "Loadjs('squid.compile.progress.php');";	
	
}

function plugins_js(){
		$tpl=new templates();
		$title=$tpl->_ENGINE_parse_body("{activate_plugins}");
		$page=CurrentPageName();
		echo "
		YahooWin2(570,'$page?content=plugins','$title','');
		
		
		var x_save_plugins= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			Loadjs('$page?x-save-plugins=yes');
		}		
		
		function save_plugins(){
		  var XHR = new XHRConnection();
		  XHR.appendData('enable_plugins','yes');
		  
		  if(document.getElementById('enable_c_icap')){
		  	document.getElementById('img_enable_c_icap').src='img/wait_verybig.gif';
		  	XHR.appendData('enable_c_icap',document.getElementById('enable_c_icap').value);
		  }
		 if(document.getElementById('enable_kavproxy')){
		 	document.getElementById('img_enable_kavproxy').src='img/wait_verybig.gif';
		  	XHR.appendData('enable_kavproxy',document.getElementById('enable_kavproxy').value);
		  }		  
			
 			if(document.getElementById('enable_dansguardian')){
 				document.getElementById('img_enable_dansguardian').src='img/wait_verybig.gif';
				XHR.appendData('enable_dansguardian',document.getElementById('enable_dansguardian').value);
			}
			
	 		if(document.getElementById('enable_squidguard')){
 				document.getElementById('img_enable_squidguard').src='img/wait_verybig.gif';
				XHR.appendData('enable_squidguard',document.getElementById('enable_squidguard').value);
			}

		 	if(document.getElementById('enable_ufdbguardd')){
 				document.getElementById('img_enable_ufdbguardd').src='img/wait_verybig.gif';
				XHR.appendData('enable_ufdbguardd',document.getElementById('enable_ufdbguardd').value);
			}			
			
		 	if(document.getElementById('enable_adzapper')){
 				document.getElementById('img_enable_adzapper').src='img/wait_verybig.gif';
				XHR.appendData('enable_adzapper',document.getElementById('enable_adzapper').value);
			}

		 	if(document.getElementById('enable_squidclamav')){
 				document.getElementById('img_enable_squidclamav').src='img/wait_verybig.gif';
				XHR.appendData('enable_squidclamav',document.getElementById('enable_squidclamav').value);
			}	

		 	if(document.getElementById('enable_metascanner')){
 				document.getElementById('img_enable_metascanner').src='img/wait_verybig.gif';
				XHR.appendData('enable_metascanner',document.getElementById('enable_metascanner').value);
			}	

			
			
			
			
			
		XHR.sendAndLoad('$page', 'GET',x_save_plugins);				
 		}
	";
}
function NotifyServers(){
	$sock=new sockets();
	$users=new usersMenus();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}	
	if($EnableWebProxyStatsAppliance==1){
		$sock->getFrameWork("squid.php?notify-remote-proxy=yes");
	}	
	
}

function plugins_save(){
	$squid=new squidbee();
	$sock=new sockets();
	$tpl=new templates();
	$multiple=false;
	$users=new usersMenus();
	if(preg_match('#^([0-9]+)\.([0-9]+)#',$users->SQUID_VERSION,$re)){
		if($re[1]>=3){
			if($re[2]>=1){
				$multiple=true;
			}
		}
		
	}
	
		$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
		$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
		if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
		if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
		if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}		
	
	$tpl=new templates();
	if(isset($_GET["enable_kavproxy"])){
		if(!$multiple){
			if($_GET["enable_c_icap"]==1){
				echo $tpl->javascript_parse_text("{DISABLE_KAV_ENABLE_CICAP}");
				$_GET["enable_kavproxy"]=0;
			}
		}
		$squid->enable_kavproxy=$_GET["enable_kavproxy"];
	}
	
	if(isset($_GET["enable_dansguardian"])){
		if($_GET["enable_dansguardian"]==1){
			if($_GET["enable_ufdbguardd"]==1){echo $tpl->javascript_parse_text("{disable_ufdbguardd_dansguardian_enabled}\n");}
			$_GET["enable_ufdbguardd"]=0;
		}
	}
	
	writelogs("Save kavProxy {$_GET["enable_kavproxy"]}",__FUNCTION__,__FILE__);
	writelogs("Save c-icap {$_GET["enable_c_icap"]}",__FUNCTION__,__FILE__);
	writelogs("Save ufdbguard {$_GET["enable_ufdbguardd"]}",__FUNCTION__,__FILE__);
	writelogs("Save adzapper {$_GET["enable_adzapper"]}",__FUNCTION__,__FILE__);
	
	
	
	if(isset($_GET["enable_c_icap"])){
		writelogs("Save c-icap {$_GET["enable_c_icap"]}",__FUNCTION__,__FILE__);
		$squid->enable_cicap=$_GET["enable_c_icap"];
	}
	
//---------------------------------------------------------------------------------------------------------------------------------------
	if(isset($_GET["enable_ufdbguardd"])){
		if($EnableWebProxyStatsAppliance==1){$datas=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));}
		
		
		if($_GET["enable_ufdbguardd"]==0){
			$datas["UseRemoteUfdbguardService"]=0;
		}
		
		
		
		if($_GET["enable_ufdbguardd"]==1){
			$_GET["enable_squidguard"]=0;
			$datas["UseRemoteUfdbguardService"]=1;
			$sock->getFrameWork("cmd.php?reload-squidguard=yes");
		}
		
		if($EnableWebProxyStatsAppliance==1){
			$datas["remote_port"]=$datas["listen_port"];
			if(!is_numeric($datas["remote_port"])){$datas["remote_port"]=3977;}
			while (list ($key, $line) = each ($_POST) ){writelogs("SAVE $key = $line",__FUNCTION__,__FILE__,__LINE__);$datas[$key]=$line;}
			$sock->SaveConfigFile(base64_encode(serialize($datas)),"ufdbguardConfig");	
			$sock->getFrameWork("squid.php?notify-remote-proxy=yes");
		}				
		
		$sock->SET_INFO("EnableUfdbGuard", $_GET["enable_ufdbguardd"]);
		
		
	}

	
//---------------------------------------------------------------------------------------------------------------------------------------	
	
	
	if(isset($_GET["enable_adzapper"])){
		writelogs("Save enable_adzapper {$_GET["enable_adzapper"]}",__FUNCTION__,__FILE__);
		$squid->enable_adzapper=$_GET["enable_adzapper"];
	}
	
	
	if(isset($_GET["enable_squidguard"])){
		writelogs("Save enable_squidguard {$_GET["enable_squidguard"]}",__FUNCTION__,__FILE__);
		$squid->enable_squidguard=$_GET["enable_squidguard"];
		if($_GET["enable_squidguard"]==1){$_GET["enable_dansguardian"]=0;}
	}
	
	if(isset($_GET["enable_squidclamav"])){
		writelogs("Save enable_squidclamav {$_GET["enable_squidclamav"]}",__FUNCTION__,__FILE__);
		$squid->enable_squidclamav=$_GET["enable_squidclamav"];
		if($_GET["enable_squidclamav"]==1){$squid->enable_cicap=0;}
		
	}

	if(isset($_GET["enable_metascanner"])){
		writelogs("Save enable_metascanner {$_GET["enable_metascanner"]}",__FUNCTION__,__FILE__);
		$squid->enable_metascanner=$_GET["enable_metascanner"];
		if($_GET["enable_metascanner"]==1){$squid->enable_cicap=1;}
		
	}
	

	
	if(isset($_GET["enable_ecapav"])){
		writelogs("Save enable_ecapav {$_GET["enable_ecapav"]}",__FUNCTION__,__FILE__);
		$squid->enable_ecapav=$_GET["enable_ecapav"];
	}
	
	
	
	
	
	
	if(isset($_GET["enable_dansguardian"])){
		writelogs("Save dansguardian {$_GET["enable_dansguardian"]}",__FUNCTION__,__FILE__);
		$squid->enable_dansguardian=$_GET["enable_dansguardian"];
		include_once(dirname(__FILE__).'/ressources/class.dansguardian.inc');
		$dans=new dansguardian();
		$dans->SaveSettings();
	}

	if(!$squid->SaveToLdap()){
			if(trim($squid->ldap_error)<>null){echo $squid->ldap_error;}
			return;
			NotifyServers();
	}
	
	writelogs("Save kavProxy:Final $squid->enable_kavproxy",__FUNCTION__,__FILE__);
	writelogs("Save c-icap:Final $squid->enable_cicap",__FUNCTION__,__FILE__);	
	
	if($squid->enable_kavproxy==1){
		echo $tpl->javascript_parse_text("{KAVPROXY_WILLBEENABLED}");
		$sock->getFrameWork("squid.php?kav4proxy-configure=yes");
	}
	
	if($squid->enable_cicap==1){
		echo $tpl->javascript_parse_text("{CICAP_WILLBEENABLED}");
	}
	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?restart-artica-status=yes");
	
}


function plugins_popup(){
	$squid=new squidbee();
	$users=new usersMenus();
	
	
	
	if($users->KAV4PROXY_INSTALLED){
		if($squid->isicap()){
		$kav=Paragraphe_switch_img("{enable_kavproxy}","{enable_kavproxy_text}",'enable_kavproxy',$squid->enable_kavproxy,'{enable_disable}',250);
		}else{
		$kav=Paragraphe_switch_disable('{enable_kavproxy}',"{feature_not_installed}<br><strong style='color:#d32d2d'>$squid->kav_accept_why</strong>",'{feature_not_installed}');		
		}
	}
		
	if($users->C_ICAP_INSTALLED){
		if($users->SQUID_ICAP_ENABLED){
			$cicap=Paragraphe_switch_img("{enable_c_icap}","{enable_c_icap_text}",'enable_c_icap',$squid->enable_cicap,'{enable_disable}',250);
		}
	}

	
	if($users->DANSGUARDIAN_INSTALLED){
		$dans=Paragraphe_switch_img("{enable_dansguardian}","{enable_dansguardian_text}",'enable_dansguardian',$squid->enable_dansguardian,'{enable_disable}',250);
	}
	
	if($users->SQUIDGUARD_INSTALLED){
		$squidguard=Paragraphe_switch_img("{enable_squidguard}","{enable_squidguard_text}",'enable_squidguard',$squid->enable_squidguard,'{enable_disable}',250);
	}
	
	if($users->APP_UFDBGUARD_INSTALLED){
		$ufdbguardd=Paragraphe_switch_img("{enable_ufdbguardd}","{enable_ufdbguardd_text}",'enable_ufdbguardd',$squid->enable_UfdbGuard,'{enable_disable}',250);
	}
	
	if($users->ADZAPPER_INSTALLED){
		//$adzapper=Paragraphe_switch_img('{enable_adzapper}','{enable_adzapper_text}','enable_adzapper',$squid->enable_adzapper,'{enable_disable}',250);
	}
	
	if($users->APP_SQUIDCLAMAV_INSTALLED){
		$squidclamav=Paragraphe_switch_img('{enable_squidclamav}','{enable_squidclamav_text}','enable_squidclamav',$squid->enable_squidclamav,'{enable_disable}',250);
	}
	
	if($users->C_ICAP_INSTALLED){
		if($users->SQUID_ICAP_ENABLED){
			if($users->APP_KHSE_INSTALLED){
				$metascanner=Paragraphe_switch_img('{enable_metascanner}','{enable_metascanner_text}','enable_metascanner',$squid->enable_metascanner,'{enable_disable}',250);
			}
		}
		
	}
	
	
	if($users->KASPERSKY_WEB_APPLIANCE){
		$cicap=null;
		$squidclamav=null;
		$squidguard=null;
		$adzapper=null;
	}
	
	
	
	$tr[]=$squidclamav;
	$tr[]=$metascanner;
	$tr[]=$cicap;
	$tr[]=$kav;
	$tr[]=$adzapper;
	$tr[]=$squidguard;
	$tr[]=$ufdbguardd;
	$tr[]=$dans;
	
	
$tables[]="<table style='width:100%'><tr>";
$t=0;
while (list ($key, $line) = each ($tr) ){
		$line=trim($line);
		if($line==null){continue;}
		$t=$t+1;
		$tables[]="<td >$line</td>";
		if($t==2){$t=0;$tables[]="</tr><tr>";}
		}

if($t<2){
	for($i=0;$i<=$t;$i++){
		$tables[]="<td >&nbsp;</td>";				
	}
}	
	
	

	
	$form="<div id='div-poubelle'></div>
		 ".implode("\n",$tables)."
		</table> 	
			<div   style='text-align:right'><hr>". button("{apply}","save_plugins()")."</div>
					
		";
		

		
$html="$form";
		
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body($html,'squid.index.php');		
	
	
}

	
function listen_port_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{listen_port}");
	$t=time();
	header("content-type: application/x-javascript");
		echo "
		LoadWinORG(914,'$page?content=listen_port_tab&t=$t','$title');";	
}

function visible_hostname_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$title=$tpl->javascript_parse_text("{visible_hostname}");
		echo "YahooWin2(1024,'$page?content=visible_hostname','$title',true);";
}


function visible_hostname_popup(){
	$squid=new squidbee();
	$page=CurrentPageName();
	$t=time();
	$form="	
		<table style='width:100%'>
			<tr>
			<td class=legend nowrap style='font-size:32px'>{visible_hostname}:</td>
			<td>" . Field_text("visible_hostname_to_save-$t",$squid->visible_hostname,'width:95%;font-size:32px')."</td>
			</tr>
			<tr>
			<td colspan=2 align='right'><hr>". button("{apply}","visible_hostname$t();",45)."</td>
			</tr>
		</table>			
		";
		
		
		
$html="
			<div class=explain style='font-size:22px'>{visible_hostname_text}</div>
			<div style='width:98%' class=form>
				$form
			</div>
		
<script>

var x_visible_hostname$t= function (obj) {
	var results=obj.responseText;
	alert(results);
	LoadAjaxRound('main-dashboard-proxy','squid.dashboard.php');
	YahooWin2Hide();
	
}
		
function visible_hostname$t(){
	var XHR = new XHRConnection();
	XHR.appendData('visible_hostname_save',document.getElementById('visible_hostname_to_save-$t').value);
	XHR.sendAndLoad('$page', 'GET',x_visible_hostname$t);	
}
</script>";
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html,'squid.index.php');	
	
}

function visible_hostname_save(){
	
$squid=new squidbee();
		$squid->visible_hostname=$_GET["visible_hostname_save"];
		if(!$squid->SaveToLdap()){
			echo $squid->ldap_error;
			exit;
		}else{
			$tpl=new templates();
			echo $tpl->_ENGINE_parse_body('hostname:{success}');
		}	
	
}

function listen_port_popup_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$array["listen_port"]="{listen_ports}";
	
	$array["browsers"]="{browsers_setup}";
	$t=time();
	
	while (list ($num, $ligne) = each ($array) ){
	


		if($num=="browsers"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?browsers-setup=yes&t=$t\" style='font-size:14px'><span>$ligne</span></a></li>\n");
			continue;
		
		}		
	
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?content=listen_port\" style='font-size:14px'><span>$ligne</span></a></li>\n");
	}
	echo build_artica_tabs($html, "listen_port_popup_tabs");
	
}

function listen_port_browsers(){
	$squid=new squidbee();	
	$tpl=new templates();
	$sock=new sockets();
	$currentIP=$sock->GET_INFO("SquidBinIpaddr");
	if($currentIP==null){$currentIP=$_SERVER["REMOTE_ADDR"];}
	
	if($squid->hasProxyTransparent==1){
		
		echo $tpl->_ENGINE_parse_body("<center>
				<span style='font-size:24px'>FireFox - {transparent}/{gateway}:$currentIP</span>
				<img src='img/firefox-front2.png'>
			</center>
				
				
				");
		return;
	}
	
	$port=$squid->listen_port;
	
		echo $tpl->_ENGINE_parse_body("
				<center>
				<span style='font-size:24px'>FireFox</span>
				<div>
				<div style='position:absolute;top:236px;left:453px;font-size:13px'><strong>$currentIP</strong></div>
				<div style='position:absolute;top:236px;left:598px;font-size:13px'><strong>$port</strong></div>
				<img src='img/firefox-front.png'>
				</div>
			</center>");
	return;	
	
}


function listen_port_popup(){
	$q=new mysql();
	$squid=new squidbee();
	$users=new usersMenus();
	if(!is_numeric($squid->second_listen_port)){$squid->second_listen_port=0;}
	if(!is_numeric($squid->ssl_port)){$squid->ssl_port=0;}
	if($squid->isNGnx()){$users->SQUID_REVERSE_APPLIANCE=false;}
	$transparent=null;
	if($squid->hasProxyTransparent==1){
		$transparent="{transparent}";
	}
	
	$sock=new sockets();
	$EnableCNTLM=$sock->GET_INFO("EnableCNTLM");
	$CNTLMPort=$sock->GET_INFO("CnTLMPORT");
	$DisableSSLStandardPort=$sock->GET_INFO("DisableSSLStandardPort");
	if(!is_numeric($DisableSSLStandardPort)){$DisableSSLStandardPort=1;}
	if(!is_numeric($EnableCNTLM)){$EnableCNTLM=0;}
	if(!is_numeric($CNTLMPort)){$CNTLMPort=3155;}
	
	$arrayParams=unserialize(base64_decode($sock->getFrameWork("squid.php?compile-list=yes")));
	$SSL=1;
	if(!isset($arrayParams["--enable-ssl"])){$SSL=0;}
	
	include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");
	$squid_reverse=new squid_reverse();
	$sslcertificates=$squid_reverse->ssl_certificates_list();
	
	if($users->SQUID_REVERSE_APPLIANCE){
			$lock="lock();";
			if($SSL==1){
				$squid->ssl_port=443;
			}
	}
	
	$page=CurrentPageName();
	$t=time();
	$EnableTCPOptimize=$sock->GET_INFO("EnableTCPOptimize");
	if(!is_numeric($EnableTCPOptimize)){$EnableTCPOptimize=0;}
	
$form="<center id='animate-$t'></center>
		<div style='width:98%' class=form>
		<table style='width:100%' >
			<tr>
				<td class=legend nowrap style='font-size:16px;'>{EnableTCPOptimize}:</td>
				<td>" . Field_checkbox("EnableTCPOptimize-$t",1,$EnableTCPOptimize,'width:95px;font-size:16px;padding:5px')."</td>
				<td width=1% nowrap style='font-weight:bold;color:#C81717;font-size:14px !important'></td>
				<td width=1%>". help_icon("{EnableTCPOptimize_explain}")."</td>
			</tr>		
		
		
			<tr>
				<td class=legend nowrap style='font-size:16px;'>{listen_port}:</td>
				<td>" . Field_text("listen_port-$t",$squid->listen_port,'width:95px;font-size:16px;padding:5px')."</td>
				<td width=1% nowrap style='font-weight:bold;color:#C81717;font-size:14px !important'>$transparent</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td class=legend nowrap style='font-size:16px;'>{second_port}:</td>
				<td>" . Field_text("second_listen_port-$t",$squid->second_listen_port,'width:95px;font-size:16px;padding:5px')."</td>
				<td>&nbsp;</td>
				<td width=1%>". help_icon("{squid_second_port_explain}")."</td>
			</tr>	
			<tr>
				<td class=legend nowrap style='font-size:16px;'>{smartphones_port}:</td>
				<td>" . Field_text("smartphones_port-$t",$squid->smartphones_port,'width:95px;font-size:16px;padding:5px')."</td>
				<td>&nbsp;</td>
				<td width=1%>". help_icon("{smartphones_port_explain}")."</td>
			</tr>						
			<tr>
				<td class=legend nowrap style='font-size:16px;'>{cntlm_port}:</td>
				<td>" . Field_text("CNTLMPort-$t",$CNTLMPort,'width:95px;font-size:16px;padding:5px')."</td>
				<td>&nbsp;</td>
				<td width=1%>". help_icon("{CnTLMPORT_explain2}")."</td>
			</tr>								
			<tr>
				<td class=legend nowrap style='font-size:16px;'>{icp_port}:</td>
				<td>" . Field_text("icp_port-$t",$squid->ICP_PORT,'width:95px;font-size:16px;padding:5px')."</td>
				<td>&nbsp;</td>
				<td width=1%>". help_icon("{icp_port_explain}")."</td>
			</tr>	
			<tr>
				<td class=legend nowrap style='font-size:16px;'>{htcp_port}:</td>
				<td>" . Field_text("htcp_port-$t",$squid->HTCP_PORT,'width:95px;font-size:16px;padding:5px')."</td>
				<td>&nbsp;</td>
				<td width=1%>". help_icon("{htcp_port_explain}")."</td>
			</tr>									
			<tr>
				<td class=legend nowrap style='font-size:16px;'>{ssl_port}:</td>
				<td>" . Field_text("ssl_port-$t",$squid->ssl_port,'width:95px;font-size:16px;padding:5px')."</td>
				<td width=1% nowrap style='font-weight:bold;color:#C81717;font-size:14px !important'>$transparent</td>
				<td width=1%>". help_icon("{squid_ssl_port_explain}")."</td>
			</tr>
			<tr>
				<td class=legend nowrap style='font-size:16px;'>{certificate}:</td>
				<td colspan=3>". Field_array_Hash($sslcertificates, "certificate-$t",$squid->certificate_center,null,null,0,"font-size:16px")."</td>
			</tr>	
			<tr>
				<td class=legend nowrap style='font-size:16px;'>{DisableSSLStandardPort}:</td>
				<td >". Field_checkbox("DisableSSLStandardPort-$t", $DisableSSLStandardPort,1)."</td>
				<td>&nbsp;</td>
				<td width=1%>". help_icon("{DisableSSLStandardPort_explain}")."</td>						
			</tr>			
						
						
			<tr>
			<td colspan=4 align='right'><hr>". button("{apply}","listenport$t()",16)."</td>
			</tr>
		</table>
		</div>
		<script>
			function CheckSSLPort$t(){
				var SSL='$SSL';
				if(SSL==0){
					document.getElementById('ssl_port-$t').disabled=true;
					document.getElementById('certificate-$t').disabled=true;
					document.getElementById('ssl_port-$t').value=0;
				}	
							
			}
			
			function lock(){
				document.getElementById('listen_port-$t').disabled=true;
				document.getElementById('ssl_port-$t').disabled=true;
			}
			
			$lock
			
		var x_listenport$t= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);}
			document.getElementById('animate-$t').innerHTML='';
			Loadjs('squid.restart.php?ApplyConfToo=yes&ask=yes');
			
		}
		
		function listenport$t(){
			var XHR = new XHRConnection();
			XHR.appendData('listenport',document.getElementById('listen_port-$t').value);
			XHR.appendData('second_listen_port',document.getElementById('second_listen_port-$t').value);
			XHR.appendData('icp_port',document.getElementById('icp_port-$t').value);
			XHR.appendData('htcp_port',document.getElementById('htcp_port-$t').value);
			XHR.appendData('CNTLMPort',document.getElementById('CNTLMPort-$t').value);
			XHR.appendData('smartphones_port',document.getElementById('smartphones_port-$t').value);
			
			
			if( document.getElementById('EnableTCPOptimize-$t').checked){
				XHR.appendData('EnableTCPOptimize',1);
			}else{
				XHR.appendData('EnableTCPOptimize',0);
			}			
			
			if( document.getElementById('DisableSSLStandardPort-$t').checked){
				XHR.appendData('DisableSSLStandardPort',1);
			}else{
				XHR.appendData('DisableSSLStandardPort',0);
			}
			
			
			XHR.appendData('ssl_port',document.getElementById('ssl_port-$t').value);
			XHR.appendData('certificate_center',document.getElementById('certificate-$t').value);	
			AnimateDiv('animate-$t');	
			XHR.sendAndLoad('$page', 'GET',x_listenport$t);	
		}				
			
		</script>
		";

if($squid->enable_dansguardian==1){
$form="
		
		<table style='width:100%'>
			<tr>
			<td class=legend nowrap style='font-size:16px;'>DansGuardian {listen_port}:</td>
			<td><strong style='width:16px'>" . Field_text('listen_port',$squid->listen_port,'width:95px;;font-size:16px;padding:5px')."</strong></td>
			</tr>		
			<tr>
			<td class=legend nowrap>SQUID {listen_port}:</td>
			<td><strong style='font-size:16px;'>$squid->alt_listen_port</strong></td>
			</tr>
			<tr>
			<td colspan=2 align='right'><hr>". button("{apply}","listenport()")."
			</td>
			</tr>
		</table>";	
	
}
		
		
		
$html="
			<div class=explain style='font-size:14px;'>{listen_port_text}</div>
				$form
			<br>
			
		";
		
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body($html,'squid.index.php');		
			
	
}


function network_add_single(){
	$SIP=$_GET["add-ip-single"];
	$squid=new squidbee();
	$squid->network_array[]=$SIP;	
	
	if(!$squid->SaveToLdap()){
		echo $squid->ldap_error;
		exit;
	}	
}
	
	
function CalculCDR(){
	$ip=new IP();
	$ipfrom=$_GET["addipfrom"];
	$ipto=$_GET["addipto"];
	
	if(preg_match('#([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#',$ipfrom,$re)){
		$ipfrom="{$re[1]}.{$re[2]}.{$re[3]}.0";
	}
	
	if(preg_match('#([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#',$ipto,$re)){
		$ipto="{$re[1]}.{$re[2]}.{$re[3]}.255";
	}	
	
	
	$SIP=$ip->ip2cidr($ipfrom,$ipto);
	writelogs("Adding new CDIR $ipfrom -> $ipto\"$SIP\"",__FUNCTION__,__FILE__);
	if(trim($SIP)==null){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("Network:{failed}\n$ipfrom -> $ipto");
		exit;
	}
	
	$squid=new squidbee();
	$squid->network_array[]=$SIP;
	if(!$squid->SaveToLdap()){
		echo $squid->ldap_error;
		exit;
	}
	}

	
	function network_delete(){
		$squid=new squidbee();
		unset($squid->network_array[$_GET["NetDelete"]]);
		if(!$squid->SaveToLdap()){
			echo $squid->ldap_error;
			exit;
		}	
		
	}
	
function CheckTomcatPort($newport){
$users=new usersMenus();	
if(!$users->TOMCAT_INSTALLED){return false;}
$sock=new sockets();
$TomcatListenPort=$sock->GET_INFO($TomcatListenPort);
if(!is_numeric($TomcatListenPort)){$TomcatListenPort=8080;}				
$TomcatEnable=$sock->GET_INFO("TomcatEnable");
if(!is_numeric($TomcatEnable)){$TomcatEnable=1;}
if($TomcatEnable==1){
	if($TomcatListenPort==$newport){return true;}	
}
return false;
}
	
function listen_port_save(){
		if(!is_numeric($_GET["listenport"])){return null;}
		if(CheckTomcatPort($_GET["listenport"])){echo "Apache Tomcat use 8080 port try other port eg:3128 !";return;	}
		$sock=new sockets();
		$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
		if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
		
		
			$FreeWebListenSSLPort=$sock->GET_INFO("FreeWebListenSSLPort");
			$FreeWebListen=$sock->GET_INFO("FreeWebListen");
			if(!is_numeric($FreeWebListenSSLPort)){$FreeWebListenSSLPort=443;}
			if(!is_numeric($FreeWebListen)){$FreeWebListen=80;}
		
			if($_GET["listenport"]==$FreeWebListen){$sock->SET_INFO("FreeWebListen",$_GET["listenport"]+1);}	
			if($_GET["ssl_port"]==$FreeWebListenSSLPort){$sock->SET_INFO("FreeWebListenSSLPort",$_GET["ssl_port"]+1);}
				
		
		$squid=new squidbee();
		$squid->listen_port=$_GET["listenport"];
		$squid->second_listen_port=$_GET["second_listen_port"];
		$squid->ICP_PORT=$_GET["icp_port"];
		$squid->HTCP_PORT=$_GET["htcp_port"];
		$squid->ssl_port=$_GET["ssl_port"];
		$squid->certificate_center=$_GET["certificate_center"];
		$sock->SET_INFO("SquidOldHTTPPort",$squid->listen_port);
		$sock->SET_INFO("SquidOldSSLPort",$squid->ssl_port);
		$sock->SET_INFO("SquidOldHTTPPort2",$squid->second_listen_port);
		$sock->SET_INFO("CNTLMPort", $_GET["CNTLMPort"]);
		$sock->SET_INFO("DisableSSLStandardPort", $_GET["DisableSSLStandardPort"]);
		$sock->SET_INFO("smartphones_port", $_GET["smartphones_port"]);
		$sock->SET_INFO("EnableTCPOptimize", $_GET["EnableTCPOptimize"]);
		
		
		
		if(!$squid->SaveToLdap()){
			echo $squid->ldap_error;
			exit;
		}else{
			$tpl=new templates();
			echo $tpl->javascript_parse_text("{listen_port}:{$_GET["listenport"]}\n",1);
			echo $tpl->javascript_parse_text("{second_port}:{$_GET["second_listen_port"]}\n",1);
			echo $tpl->javascript_parse_text("{ssl_port}:{$_GET["ssl_port"]}\n",1);
			echo $tpl->javascript_parse_text("{icp_port}:{$_GET["icp_port"]}\n",1);
			echo $tpl->javascript_parse_text("{htcp_port}:{$_GET["htcp_port"]}\n",1);
			echo $tpl->javascript_parse_text("{cntlm_port}:{$_GET["cntlm_port"]}\n",1);
			echo $tpl->javascript_parse_text("{smartphones_port}:{$_GET["smartphones_port"]}\n",1);
			
			
			if($EnableWebProxyStatsAppliance==1){
				echo $tpl->javascript_parse_text("{proxy_clients_was_notified}\n",1);
			}
			
			echo $tpl->javascript_parse_text("{success}\n",1);
		}

		
		$sock->getFrameWork("services.php?KernelTuning=yes");
		$sock->getFrameWork("cmd.php?restart-apache-src=yes");		
		$sock->getFrameWork("squid.php?cntlm-restart=yes");
		
	}
	
	
function network_popup(){
	$page=CurrentPageName();
	$sock=new sockets();
	$AllowAllNetworksInSquid=$sock->GET_INFO("AllowAllNetworksInSquid");
	if(!is_numeric($AllowAllNetworksInSquid)){$AllowAllNetworksInSquid=1;}
	
	
		$color="color:black";
		$squid=new squidbee();
		
		if($AllowAllNetworksInSquid==1){
			$color="color:#CCCCCC";
			$list=$list . "
			<tr >
				<td width=1%><img src='img/network-1.gif'></td>
				<td><strong style='font-size:16px'>{AllowAllNetworks}</strong></td>
				<td width=1%>&nbsp;</td>
			</tr>
			<tr>
				<td colspan=3><hr></td>
			</tr>
			";	
			
		}
		
		while (list ($num, $ligne) = each ($squid->network_array) ){
			$list=$list . "
			<tr " . CellRollOver().">
				<td width=1%><img src='img/network-1.gif'></td>
				<td><strong style='font-size:16px;$color'>$ligne</strong></td>
				<td width=1%>" . imgtootltip('ed_delete.gif','{delete}',"NetDelete($num)")."</td>
			</tr>
			<tr>
				<td colspan=3><hr></td>
			</tr>
			";
			
			
		}
		
		
		$list="<table style='width:100%;'>$list</table>";
		
		
		$pattern_form="
		<table class=form>
		<tr>
			<td class=legend style='font-size:14px'>{pattern}:</td>
			<td>". Field_text("FREE_FIELD",null,"font-size:14px;padding:3px",null,null,null,false,"SquidnetaddSingleCheck(event)")."</td>
			<td width=1%>". help_icon("{SQUID_NETWORK_HELP}")."</td>
		</tr>
		</table>
		";
		
		$netcacl_form="
		<table class=form>
		<tr>
			<td class=legend style='font-size:14px' nowrap>{ip_address}:</td>
			<td>". Field_text("IP_NET_FIELD",null,"font-size:14px;padding:3px",null,null,null,false,"SquidnetMaskCheck(event)")."</td>
			<td width=1%></td>
		</tr>
		<tr>
			<td class=legend style='font-size:14px' nowrap>{netmask}:</td>
			<td>". Field_text("IP_NET_MASK",null,"font-size:14px;padding:3px",null,null,null,false,"SquidnetMaskCheck(event)")."</td>
			<td width=1%></td>
		</tr>	
		<tr>
			<td class=legend style='font-size:14px' nowrap>{results}:</td>
			<td style='font-size:16px'><input type='hidden' value='' id='IP_NET_CALC'><span id='IP_NET_CALC_TEXT'></span></td>
			<td width=1%>". imgtootltip("img_calc_icon-16.gif","{results}","SquidnetMaskCheck()")."</td>
		</tr>
		
		<tr>
			<td colspan=3 align='right'><hr>". button("{add}","SquidnetMaskAdd()")."</td>
		</table>
		
		
		";
		
		
		$form="
		<div id='squid_network_id'>
		<div class=explain style='font-size:16px'>{your_network_text}</div>
		<table style='width:100%'>
		<tr>
			<td class=legend>{AllowAllNetworks}:</td>
			<td>". Field_checkbox("AllowAllNetworksInSquid",1,$AllowAllNetworksInSquid,"AllowAllNetworksInSquidSave()")."</td>
		</tr>
		</table>
		
		
		<div style='font-size:16px;font-weight:bold'>{allow_network}:</div><br>
		<table style='width:100%'>
			<tr>
			<td  style='padding:4xp'>
			<div style='padding:2px;border:1px solid #CCCCCC;height:225px;overflow:auto'>$list</div></td>
			<td  style='padding:4xp'>
				<H3>{squid_net_simple}</H3>
				<table class=form>
					<tr>
					<td class=legend nowrap style='font-size:16px'>{from_ip}:</td>
					<td>" . Field_text('from_ip',null,'width:120px;font-size:16px;padding:3px',null,null,null,false,"SquidnetaddCheck(event)")."</td>
					</tr>
					<tr>
					<td class=legend style='font-size:16px'>{to_ip}:</td>
					<td>" . Field_text('to_ip',null,'width:120px;font-size:16px;padding:3px',null,null,null,false,"SquidnetaddCheck(event)")."</td>
					</tr>
					<tr>
					<td colspan=2 align='right'>
					<hr>
						". button("{add}","netadd()")."
					</tr>
					</table>	
					<hr>
					<H3>{squid_net_calc_mask}</H3>
					$netcacl_form
					<hr>
					<H3>{free_pattern}</H3>
					$pattern_form

				</td>		
			</tr>
		</table>
		</div>
		
		<script>
		var x_SquidnetMaskCheck=function(obj){
     		var tempvalue=obj.responseText;
      		if(tempvalue.length>3){
     			document.getElementById('IP_NET_CALC_TEXT').innerHTML=tempvalue;
     			document.getElementById('IP_NET_CALC').value=tempvalue;
			}
       }	

	function SquidnetMaskCheck(){
		var XHR = new XHRConnection();
		XHR.appendData('SquidnetMaskCheckIP',document.getElementById('IP_NET_FIELD').value);
		XHR.appendData('SquidnetMaskCheckMask',document.getElementById('IP_NET_MASK').value);
		AnimateDiv('IP_NET_CALC_TEXT');
		XHR.sendAndLoad('$page', 'GET',x_SquidnetMaskCheck);		
	
	}
	
	function SquidnetMaskAdd(){
		var XHR = new XHRConnection();
		XHR.appendData('add-ip-single',document.getElementById('IP_NET_CALC').value);
		AnimateDiv('squid_network_id');
		XHR.sendAndLoad('$page', 'GET',x_netadd);
	}
	
	function AllowAllNetworksInSquidSave(){
		var XHR = new XHRConnection();
		if(document.getElementById('AllowAllNetworksInSquid').checked){
			XHR.appendData('AllowAllNetworksInSquid',1);
		}else{
			XHR.appendData('AllowAllNetworksInSquid',0);
		}
		XHR.sendAndLoad('$page', 'GET',x_netadd);
	}
		
		
		</script>";
		$html=$form;
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body($html,'squid.index.php');
}

function network_calculate_cdir(){
	
	$ip=$_GET["SquidnetMaskCheckIP"];
	$mask=$_GET["SquidnetMaskCheckMask"];
	if(!preg_match("#([0-9]+)\.([0-9]+)\.([0-9]+)#",$ip)){return;}
	if(!preg_match("#([0-9]+)\.([0-9]+)\.([0-9]+).([0-9]+)#",$mask)){return;}
	
	
	$sock=new sockets();
	$calc=base64_encode("$ip/$mask");
	echo base64_decode($sock->getFrameWork("cmd.php?cdir-calc=$calc"));
	
	
	
}
function force_upgrade_squid(){
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?force-upgrade-squid=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{CONTROL_CENTER_UPGRADE_OK}");
	}
	
function auth_whitelist_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$website=$tpl->javascript_parse_text("{website}");
	$html="
	<div class=explain>{squid_auth_whitelist_why}</div>
	<div style='text-align:right;margin-bottom:8px'>".button('{add}','WhiteListAuthAdd()')."</div>
	<div style='width:100%;height:310px;overflow:auto' id='whitelist-auth-list'></div>
	
	<script>
		function WhiteListAuthList(){
			LoadAjax('whitelist-auth-list','$page?auth-wl-list=yes');
		}
	
		var x_WhiteListAuthAdd=function(obj){
     		var tempvalue=obj.responseText;
      		if(tempvalue.length>3){alert(tempvalue);}
			WhiteListAuthList();
		}	

	function WhiteListAuthAdd(){
		var uri=prompt('$website:');
		if(uri){
			var XHR = new XHRConnection();
			XHR.appendData('auth-wl-add',uri);
			document.getElementById('whitelist-auth-list').innerHTML='<center style=\"width:100%\"><img src=img/wait.gif></center>';
			XHR.sendAndLoad('$page', 'GET',x_WhiteListAuthAdd);	
			}	
	
	}		
	
	function WhiteListAuthDelete(ID){
		var XHR = new XHRConnection();
		XHR.appendData('auth-wl-del',ID);
		document.getElementById('whitelist-auth-list').innerHTML='<center style=\"width:100%\"><img src=img/wait.gif></center>';
		XHR.sendAndLoad('$page', 'GET',x_WhiteListAuthAdd);		
	}
		
	WhiteListAuthList();
	</script>
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
	
}


function auth_whitelist_useragent_popup(){
	$page=CurrentPageName();
	$html="<div class=explain>{squid_auth_whitelist_usergent_why}</div>
	
	<table style='width:100%'>
	<tr>
		<td class=legend nowrap>{display_only_saved}:</td>
		<td>". Field_checkbox("auth-wl-useragents-saved-only",1,0,"RefreshUserAgentListWBL()")."</td>
	</tr>
	</table>	
	<div id='UserAgentListWBL'></div>
	
	<script>
		
		function UserAgentWLEnable(md,name){
			var XHR = new XHRConnection();
			
			if(document.getElementById(md).checked){
				XHR.appendData('auth-wl-add-useragents',name);
			}else{
				XHR.appendData('auth-wl-del-useragents',name);
			}
			
			XHR.sendAndLoad('$page', 'GET');	
		}
		
		function RefreshUserAgentListWBL(){
			var OnlySavedData='';
			if(document.getElementById('auth-wl-useragents-saved-only').checked){
				OnlySavedData=1;
			}else{
				OnlySavedData=0;
			}
		
		
			LoadAjax('UserAgentListWBL','$page?auth-wl-useragents-list=yes&OnlySavedData='+OnlySavedData);
		}
		
		
		RefreshUserAgentListWBL();
		
		
	</script>
	";
	
		$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}
function auth_whitelist_useragent_list(){
$html="<table class=tableView style='width:95%'>
				<thead class=thead>
				<tr>
					<th width=1% nowrap colspan=3>{useragent}:</td>
				</tr>
				</thead>
	
	";


$OnlySavedData=$_GET["OnlySavedData"];

if($OnlySavedData==1){
$sql="SELECT uri FROM squid_white WHERE task_type='AUTH_WL_USERAGENTS'";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		if($cl=="oddRow"){$cl=null;}else{$cl="oddRow";}
		$arrayUserAgents[$ligne["uri"]]=1;
		$md=md5($ligne["uri"]);
		$html=$html."<tr class=$cl>
		<td width=1%>".Field_checkbox("$md",1,1,"UserAgentWLEnable('$md','{$ligne["uri"]}')")."</td>
		<td width=99%><code style='font-size:14px'>{$ligne["uri"]}</code></td>
		</tr>
		
		";		
	}

	$html=$html."</table>";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	return;
	
}

$sql="SELECT uri FROM squid_white WHERE task_type='AUTH_WL_USERAGENTS'";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$arrayUserAgents[$ligne["uri"]]=1;
	}


	
$sql="SELECT browser FROM `UserAgents` GROUP BY browser ORDER BY browser";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		if($cl=="oddRow"){$cl=null;}else{$cl="oddRow";}
		$md=md5($ligne["browser"]);
		$html=$html."
		<tr class=$cl>
		<td width=1%>".Field_checkbox("$md",1,$arrayUserAgents[$ligne["browser"]],"UserAgentWLEnable('$md','{$ligne["browser"]}')")."</td>
		<td width=99%><code style='font-size:14px'>{$ligne["browser"]}</code></td>
		</tr>
		
		";
		
	}
	
	$html=$html."</table>";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}

function auth_whitelist_useragent_add(){
$sql="INSERT INTO squid_white (uri,zDate,task_type) VALUES('{$_GET["auth-wl-add-useragents"]}',NOW(),'AUTH_WL_USERAGENTS')";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		echo $q->mysql_error;	
		return;
	}
$sock=new sockets();$sock->getFrameWork("squid.php?build-smooth=yes");	
}

function auth_whitelist_useragent_del(){
	$sql="DELETE FROM squid_white WHERE uri='{$_GET["auth-wl-del-useragents"]}' AND task_type='AUTH_WL_USERAGENTS'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		echo $q->mysql_error;	
		return;
	}
$sock=new sockets();$sock->getFrameWork("squid.php?build-smooth=yes");		
}

	


function auth_whitelist_add(){
	$www=trim($_GET["auth-wl-add"]);
	$www=str_replace("*","",$www);
	$www=str_replace("\"","",$www);
	if(strpos($www," ")>0){$tt=explode(" ",$www);}
	
	if(strpos($www,",")>0){$tt=explode(",",$www);}
	if(strpos($www,";")>0){$tt=explode(";",$www);}
	
	if(!is_array($tt)){$tt[]=$www;}
	$q=new mysql();
	while (list ($num, $website) = each ($tt) ){
		if(trim($website)==null){continue;}
		if(preg_match("#:\/\/(.+?)/#",$website,$re)){$$website=$re[1];}
		if(preg_match("#:\/\/(.+?)$#",$website,$re)){$website=$re[1];}	
		if(preg_match("#^(.+?)\/#",$website,$re)){$website=$re[1];}
		if(preg_match("#^www\.(.+)$#",$website,$re)){$website=".{$re[1]}";}
		$sql="INSERT INTO squid_white (uri,zDate,task_type) VALUES('$website',NOW(),'AUTH')";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	$s=new squidbee();
	$s->SaveToLdap();	
	
}
function auth_whitelist_del(){
	$sql="DELETE FROM squid_white WHERE ID={$_GET["auth-wl-del"]}";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		echo $q->mysql_error."\n\n$sql";
		return;	
	}
	
	$sock->getFrameWork("squid.php?build-smooth=yes");	
}

function AllowAllNetworksInSquid_Save(){
	$sock=new sockets();
	$sock->SET_INFO("AllowAllNetworksInSquid",$_GET["AllowAllNetworksInSquid"]);
	$sock->getFrameWork("squid.php?build-smooth=yes");	
	
}


function auth_whitelist_list(){
	$q=new mysql();
	$sql="SELECT * FROM squid_white WHERE task_type='AUTH' ORDER BY uri";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	
	$html="	<table class=tableView style='width:95%'>
				<thead class=thead>
				<tr>
					<th width=1% nowrap colspan=3>{websites}:</td>
				</tr>
				</thead>
			
			
			";	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($cl=="oddRow"){$cl=null;}else{$cl="oddRow";}
		$html=$html."
		<tr class=$cl>
			<td width=1%><img src='img/fw_bold.gif'></td>
			<td width=99%><code style='font-size:14px'>{$ligne["uri"]}</td>
			<td width=1%>". imgtootltip("delete-24.png","{delete}","WhiteListAuthDelete('{$ligne["ID"]}')")."</td>
		</tr>";
		}
		
		$html=$html."</table>";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}
	
	
