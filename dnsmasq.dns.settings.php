<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.dnsmasq.inc');
	include_once('ressources/class.main_cf.inc');

	
	if(posix_getuid()<>0){
		$user=new usersMenus();
		if($user->AsDnsAdministrator==false){
			$tpl=new templates();
			echo $tpl->_ENGINE_parse_body("alert('{ERROR_NO_PRIVS}');");
			die();exit();
		}
	}	
	
if(isset($_GET["enable-page"])){page_enable();exit;}	
if(isset($_POST["EnableDNSMASQ"])){EnableDNSMASQ_save();exit;}
if(isset($_POST["SaveConf1"])){SaveConf1();exit;}
if(isset($_POST["restart-dnsmasq"])){restart_service();exit;}
if(isset($_GET["interfaces"])){interfaces();exit;}
if(isset($_GET["ffm1"])){main_form();exit;}
if(isset($_GET["addressesReload"])){echo Loadaddresses();exit;}
if(isset($_GET["DnsmasqDeleteInterface"])){DnsmasqDeleteInterface();exit;}

if(isset($_GET["listen_addresses"])){SaveListenAddress();exit;}
if(isset($_GET["DnsmasqDeleteListenAddress"])){DnsmasqDeleteListenAddress();exit;}
if(isset($_GET["EnableDNSMASQ"])){EnableDNSMASQSave();exit;}
if(isset($_POST["EnableDNSMASQOCSDB"])){EnableDNSMASQOCSDB();exit;}
if(isset($_GET["get-status"])){status();exit;}
if(isset($_GET["sub-status"])){page_status();exit;}
if(isset($_GET["sub-settings"])){page_settings();exit;}
if(isset($_GET["sub-events"])){page_events();exit;}


if(isset($_GET["localdomain-popup"])){page_localdomains_popup();exit;}
if(isset($_GET["localdomain-js"])){page_localdomains_js();exit;}
if(isset($_GET["sub-localdomains"])){page_localdomains();exit;}
if(isset($_GET["localdomain-search"])){page_localdomains_search();exit;}
if(isset($_POST["localdomain-enable"])){page_localdomains_enable();exit;}
if(isset($_POST["localdomain-del"])){page_localdomains_del();exit;}
if(isset($_POST["localdomain-add"])){page_localdomains_add();exit;}



if(isset($_GET["sub-wpad"])){wpad();exit;}
if(isset($_POST["wpad"])){wpad_save();exit;}




page();

function status(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$sock=new sockets();
	$datas=base64_decode($sock->getFrameWork('services.php?dnsmasq-status=yes'));
	$ini=new Bs_IniHandler();
	$ini->loadString($datas);
	$status=DAEMON_STATUS_ROUND("DNSMASQ",$ini,null);
	$status=$tpl->_ENGINE_parse_body($status);
	$status="
	<div id='dnsmasq-status-$t'>
		$status
		<div style='width:100%;text-align:right'>". 
			imgtootltip("refresh-24.png","{refresh}","LoadAjax('dnsmasq-status-$t','$page?get-status=yes')")."
		</div>
	</div>
	
	
	<script>RefreshMainForm()</script>
	
	";
	echo $tpl->_ENGINE_parse_body($status);

}

function restart_service(){
	$sock=new sockets();
	$sock->getFrameWork("dnsmasq.php?restart=yes");
}

function page_enable(){
	$sock=new sockets();
	$page=CurrentPageName();
	$t=time();
	$tpl=new templates();
	$EnableDNSMASQ=intval($sock->GET_INFO("EnableDNSMASQ"));
	$EnableDNSMASQLDAPDB=intval($sock->GET_INFO("EnableDNSMASQLDAPDB"));
	
	
	$pp=Paragraphe_switch_img("{enable_dns_service}", "{green_enable_dns_service_explain}",
			"EnableDNSMASQ-$t",$EnableDNSMASQ,null,750);
	$p1=Paragraphe_switch_img("{EnableDNSMASQLDAPDB}", "{EnableDNSMASQLDAPDB_explain}",
			"EnableDNSMASQLDAPDB-$t",$EnableDNSMASQLDAPDB,null,750);
	

	
	$html="
	<div style='width:98%' class=form>
		$pp<br>
		$p1<br>
		<hr>
		<div style='text-align:right;margin-bottom:30px'>". button("{apply}" ,"Save$t()",42)."</div>		
	</div>
<script>
	var xSave$t= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}	
		Loadjs('dnsmasq.restart.progress.php');
	}

	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('EnableDNSMASQ',document.getElementById('EnableDNSMASQ-$t').value);
		XHR.appendData('EnableDNSMASQLDAPDB',document.getElementById('EnableDNSMASQLDAPDB-$t').value);
		XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
</script>
				
				
";
	
echo $tpl->_ENGINE_parse_body($html);	
	
}

function EnableDNSMASQ_save(){
	$sock=new sockets();
	$sock->SET_INFO("EnableDNSMASQ", $_POST["EnableDNSMASQ"]);
	$sock->SET_INFO("EnableLocalDNSMASQ", $_POST["EnableDNSMASQ"]);
	$sock->SET_INFO("EnableDNSMASQLDAPDB",$_POST["EnableDNSMASQLDAPDB"]);
	
}


function main_form(){
	$cf=new dnsmasq();
	$page=CurrentPageName();
	$tpl=new templates();
	$sys=new systeminfos();
	$sys->array_interfaces[null]='{select}';
	$sys->array_tcp_addr[null]='{select}';
	$interfaces=Field_array_Hash($sys->array_interfaces,'interfaces',null,"style:font-size:16px;padding:3px;");
	$tcpaddr=Field_array_Hash($sys->array_tcp_addr,'listen_addresses',null,"style:font-size:16px;padding:3px;");
	$sock=new sockets();
	$EnableDNSMASQ=intval($sock->GET_INFO("EnableDNSMASQ"));
	$EnableDNSMASQOCSDB=intval($sock->GET_INFO("EnableDNSMASQOCSDB"));

	
	
	
$f[]="domain-needed";
$f[]="expand-hosts";
$f[]="bogus-priv";
$f[]="filterwin2k";
$f[]="strict-order";
$f[]="no-resolv";
$f[]="no-negcache";
$f[]="no-poll";
$f[]="log-queries";


while (list ($index, $key) = each ($f) ){
	if($cf->main_array[$key]=="yes"){$cf->main_array[$key]=1;}else{$cf->main_array[$key]=0;}
	$js[]="if(document.getElementById('$key').checked){XHR.appendData('$key','yes');	}else{XHR.appendData('$key','no');}";
}	
$html="
<div style='width:98%' class=form>		
<table style='width:100%'><tbody>

<tr>
	<td align='right' style='font-size:22px;vertical-align:middle;' class=legend>". texttooltip("{EnableDNSMASQOCSDB}","{EnableDNSMASQOCSDB_explain}").":</td>
	<td align='left' style='font-size:22px;vertical-align:middle;'>". Field_checkbox_design("EnableDNSMASQOCSDB",1,$EnableDNSMASQOCSDB,"EnableDNSMASQOCSDB()")."</td>
</tr>
<tr>
	<td align='right' style='font-size:22px;vertical-align:middle;' style='font-size:22px;vertical-align:middle' class=legend>". texttooltip("{domain-needed}","{domain-needed_text}").":</td>
	<td align='left' style='font-size:22px;vertical-align:middle;'>" . Field_checkbox_design('domain-needed',1,$cf->main_array["domain-needed"])."</td>

</tr>
<tr>
<td align='right' style='font-size:22px;vertical-align:middle;' style='font-size:22px;vertical-align:middle' class=legend>". texttooltip("{expand-hosts}","{expand-hosts_text}").":</td>
<td align='left' style='font-size:22px;vertical-align:middle;'   >" . Field_checkbox_design('expand-hosts',1,$cf->main_array["expand-hosts"])."</td>
</tr>


<tr>
<td align='right' style='font-size:22px;vertical-align:middle;' style='font-size:22px;vertical-align:middle' class=legend>". texttooltip("{bogus-priv}","{bogus-priv_text}").":</td>
<td align='left' style='font-size:22px;vertical-align:middle;' >" . Field_checkbox_design('bogus-priv',1,$cf->main_array["bogus-priv"])."</td>
</tr>
<tr>
<td align='right' style='font-size:22px;vertical-align:middle;'   style='font-size:22px;vertical-align:middle' class=legend>". texttooltip("{filterwin2k}","{filterwin2k_text}").":</td>
<td align='left' style='font-size:22px;vertical-align:middle;' >" . Field_checkbox_design('filterwin2k',1,$cf->main_array["filterwin2k"])."</td>

</tr>
<tr>
<td align='right' style='font-size:22px;vertical-align:middle;'   style='font-size:22px;vertical-align:middle' class=legend>". texttooltip("{strict-order}","{strict-order_text}").":</td>
<td align='left' style='font-size:22px;vertical-align:middle;' >" . Field_checkbox_design('strict-order',1,$cf->main_array["strict-order"])."</td>
</tr>

<tr>
<td align='right' style='font-size:22px;vertical-align:middle;'  style='font-size:22px;vertical-align:middle' class=legend>". texttooltip("{no-resolv}","{no-resolv_text}").":</td>
<td align='left' style='font-size:22px;vertical-align:middle;' >" . Field_checkbox_design('no-resolv',1,$cf->main_array["no-resolv"])."</td>

</tr>

<tr>
<td align='right' style='font-size:22px;vertical-align:middle' class=legend>". texttooltip("{no-negcache}","{no-negcache_text}").":</td>
<td align='left' style='font-size:22px;vertical-align:middle' >" . Field_checkbox_design('no-negcache',1,$cf->main_array["no-negcache"])."</td>
</tr>



<tr>
<td align='right' style='font-size:22px;vertical-align:middle' class=legend>". texttooltip("{no-poll}","{no-poll_text}").":</td>
<td align='left' style='font-size:22px;vertical-align:middle;' >" . Field_checkbox_design('no-poll',1,$cf->main_array["no-poll"])."</td>

</tr>

<tr>
<td align='right' style='font-size:22px;vertical-align:middle' class=legend>". texttooltip("{log-queries}","{log-queries_text}").":</td>
<td align='left' style='font-size:22px;vertical-align:middle' >" . Field_checkbox_design('log-queries',1,$cf->main_array["log-queries"])."</td>

</tr>


</tbody>
</table>
</div>
". Field_hidden("resolv-file", $cf->main_array["resolv-file"])."
<div style='width:98%' class=form>
<table style='width:100%'>
	<tbody>
		<tr>
			<td align='right' style='font-size:22px;vertical-align:middle;' nowrap class=legend>". texttooltip("{cache-size}","{cache-size_text}").":</td>
			<td align='left' style='font-size:22px;vertical-align:middle;'>" . Field_text('cache-size',$cf->main_array["cache-size"],"font-size:22px;padding:3px;width:120px")."</td>
			
		</tr>
		<tr>
			<td align='right' style='font-size:22px;vertical-align:middle;'nowrap class=legend>". texttooltip("{dns-forward-max}","{dns-forward-max_text}").":</td>
			<td align='left' style='font-size:22px;vertical-align:middle;'>" . Field_text('dns-forward-max',$cf->main_array["dns-forward-max"],"font-size:22px;padding:3px;width:120px")."</td>
			
		</tr>					
					
			
	
		<tr>
			<td align='right' style='font-size:22px;vertical-align:middle;'    nowrap class=legend>". texttooltip("{local_domain}","{dnsmasq_domain_explain}").":</td>
			<td align='left' style='font-size:22px;vertical-align:middle;'>" . Field_text('dnsmasq-domain',$cf->main_array["domain"],"font-size:22px;padding:3px;width:220px")."</td>
			
		</tr>
	
		<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","SaveDNSMASQMainConf();",32)."</td>
		</tr>
	</tbody>
</table></div>";	
	echo $tpl->_ENGINE_parse_body($html);
}

function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$instance=$_GET["instance"];
	$array["sub-status"]='{status}';
	$array["sub-settings"]='{settings}';
	$array["dns_nameservers"]='{dns_nameservers}';
	$array["sub-listen"]='{interface}';
	$array["sub-wpad"]='{wpad_title}';

	
	
	
	$fonctsize="font-size:24px";
	
		
	

	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="dns_nameservers"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.dns.php\"><span span style='$fonctsize'>$ligne</span></a></li>\n");
			continue;
			
		}
		
		if($num=="sub-listen"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"dnsmasq.interfaces.php\"><span span style='$fonctsize'>$ligne</span></a></li>\n");
			continue;
				
		}		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span span style='$fonctsize'>$ligne</span></a></li>\n");
	}
	
	
	echo build_artica_tabs($html,"main_config_dnsmasqsub")."<script>LeftDesign('dns-256-white-opac20.png');</script>";
	
	
	
	
}

function page_events(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=time();
	


echo $html;
	
}

function page_status(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=time();
	$html="
	<table style='width:100%'>
	<tr>
		<td style='font-size:18px;vertical-align:top;width:310px'><div id='get-status'></div></td>
		<td  style='font-size:18px;vertical-align:top;width:1140px'>
			<div class=explain id='dnsmaskrool' style='font-size:18px'>{dnsmasq_intro_settings}</div>
			<div id='enable-$t'></div>
			
			
				
	</td>
	</tr>
	</table>
	<script>
		LoadAjax('get-status','$page?get-status=yes');
		LoadAjax('enable-$t','$page?enable-page=yes');
	</script>
	";
	
echo $tpl->_ENGINE_parse_body($html);
	
}



function page_settings(){
	$t=time();
	$cf=new dnsmasq();
	$page=CurrentPageName();
	$tpl=new templates();
	$sys=new systeminfos();
	$sys->array_interfaces[null]='{select}';
	$sys->array_tcp_addr[null]='{select}';
	$interfaces=Field_array_Hash($sys->array_interfaces,'interfaces',null,"style:font-size:18px;padding:3px;");
	$tcpaddr=Field_array_Hash($sys->array_tcp_addr,'listen_addresses',null,"style:font-size:18px;padding:3px;");
	$sock=new sockets();
	$EnableDNSMASQ=$sock->GET_INFO("EnableDNSMASQ");
	if(!is_numeric($EnableDNSMASQ)){$EnableDNSMASQ=0;}


	
	$EnableDNSMASQOCSDB=$sock->GET_INFO("EnableDNSMASQOCSDB");
	if(!is_numeric($EnableDNSMASQOCSDB)){$EnableDNSMASQOCSDB=1;}	
	
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	$DNSMasqUseStatsAppliance=$sock->GET_INFO("DNSMasqUseStatsAppliance");
	if(!is_numeric($DNSMasqUseStatsAppliance)){$DNSMasqUseStatsAppliance=0;}	
	
	
	
	
$f[]="domain-needed";
$f[]="expand-hosts";
$f[]="bogus-priv";
$f[]="filterwin2k";
$f[]="strict-order";
$f[]="no-resolv";
$f[]="no-negcache";
$f[]="no-poll";
$f[]="log-queries";


while (list ($index, $key) = each ($f) ){
	if($cf->main_array[$key]=="yes"){$cf->main_array[$key]=1;}else{$cf->main_array[$key]=0;}
	$js[]="if(document.getElementById('$key').checked){XHR.appendData('$key','yes');	}else{XHR.appendData('$key','no');}";
}

	// kill -USR1 17226
$html="
<table style='width:100%'>
<tr>
	<td style='vertical-align:top;padding-right:10px;width:240px'><div id='status-$t'></div></td>
	<td width=70%>
<div id='ffm1$t'></div>

</td>
</tr>
</table>






<script>

	var x_SaveDNSMASQMainConf= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}	
		Loadjs('dnsmasq.restart.progress.php');
	}

	function SaveDNSMASQMainConf(){
	var XHR = new XHRConnection();
		XHR.appendData('resolv-file',document.getElementById('resolv-file').value);
		XHR.appendData('cache-size',document.getElementById('cache-size').value);
		XHR.appendData('domain',document.getElementById('dnsmasq-domain').value);
		XHR.appendData('dns-forward-max',document.getElementById('dnsmasq-domain').value);
		XHR.appendData('SaveConf1','yes');		
		". @implode("\n", $js)."
		AnimateDiv('ffm1$t');
		XHR.sendAndLoad('$page', 'POST',x_SaveDNSMASQMainConf);
		
	
	}


	var x_EnableDNSMASQSaveBack= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}	
		RefreshTab('main_config_dnsmasqsub');
		
	}		
	
	function EnableDNSMASQSave(key){
	
		var XHR = new XHRConnection();
		if(document.getElementById('EnableDNSMASQOCSDB').checked){XHR.appendData('EnableDNSMASQOCSDB',1);	}else{XHR.appendData('EnableDNSMASQOCSDB',0);}
		XHR.sendAndLoad('$page', 'GET',x_EnableDNSMASQSaveBack);
	}
	
	var xEnableDNSMASQOCSDB= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}	
		
		
	}		
	
	
	function EnableDNSMASQOCSDB(){
		var XHR = new XHRConnection();
		if(document.getElementById('EnableDNSMASQOCSDB').checked){XHR.appendData('EnableDNSMASQOCSDB',1);	}else{XHR.appendData('EnableDNSMASQOCSDB',0);}
		XHR.sendAndLoad('$page', 'POST',xEnableDNSMASQOCSDB);	
	
	}
	

	function RefreshMainForm(){
		LoadAjax('ffm1$t','$page?ffm1=yes');
	}

	LoadAjax('status-$t','$page?get-status=yes');
</script>

";
	
echo $tpl->_ENGINE_parse_body($html);
	
}







function wpad(){
	$sock=new sockets();
	$conf=new dnsmasq();
	$tpl=new templates();
	$page=CurrentPageName();
	$Params=$conf->ARTICA_ARRAY["WPAD"];
	$t=time();
	if(!is_numeric($Params["PORT"])){
		$SquidEnableProxyPac=$sock->GET_INFO("SquidEnableProxyPac");
		if($SquidEnableProxyPac==1){
			$listen_port=$sock->GET_INFO("SquidProxyPacPort");
			if(!is_numeric($listen_port)){$listen_port=8890;}
		}
		if(!is_numeric($listen_port)){$listen_port=80;}
		$Params["PORT"]=$listen_port;
		$Params["URI"]="proxy.pac";
	}
	
	if($Params["HOST"]==null){
		$Params["HOST"]="yourserver.yourdomain";
	}
	
	
	$html="
	<div id='div-$t' class=explain style='font-size:18px'>{dnsmasq_wpad_explain}</div>
	 <div style='width:98%' class=form>
	<table style='width:100%'>
	<tbody>
	<tr>
		<td style='font-size:24px;vertical-align:middle' class=legend valign='middle'>{enable}:</td>
		<td style='font-size:24px;vertical-align:middle'>". Field_checkbox_design("ENABLE-$t", 1,$Params["ENABLE"],"CheckWpadEnable()")."</td>
	</tr>	
	
	<tr>
		<td style='font-size:24px;vertical-align:middle' class=legend valign='middle'>{listen_port}:</td>
		<td style='font-size:24px;vertical-align:middle'>". Field_text("PORT-$t",$Params["PORT"],"font-size:24px;width:90px")."</td>
	</tr>
	<tr>
		<td style='font-size:24px;vertical-align:middle' class=legend valign='middle'>{ipaddr}:</td>
		<td style='font-size:24px;vertical-align:middle'>". field_ipv4("IP_ADDR-$t",$Params["IP_ADDR"],"font-size:24px;")."</td>
	</tr>	
	<tr>
		<td style='font-size:24px;vertical-align:middle' class=legend valign='middle'>{hostname}:</td>
		<td style='font-size:24px;vertical-align:middle'>wpad.". Field_text("HOST-$t",$Params["HOST"],"font-size:24px;width:560px")."</td>
	</tr>	
	<tr>
		<td style='font-size:24px;vertical-align:middle' class=legend>{url}:</td>
		<td style='font-size:24px;vertical-align:middle'>http://wpad.{$Params["HOST"]}:{$Params["PORT"]}/". Field_text("URI-$t",$Params["URI"],"font-size:24px;width:220px")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'>". button("{apply}", "SaveForm$t()",32)."</td>
	</tr>
	</table>
	</div>
	<script>
		
		var x_SaveForm$t= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			RefreshTab('main_config_dnsmasqsub');						
				
		}		
	
	
		function CheckWpadEnable(){
			document.getElementById('PORT-$t').disabled=true;
			document.getElementById('IP_ADDR-$t').disabled=true;
			document.getElementById('HOST-$t').disabled=true;
			document.getElementById('URI-$t').disabled=true;
			if(document.getElementById('ENABLE-$t').checked){
				document.getElementById('PORT-$t').disabled=false;
				document.getElementById('IP_ADDR-$t').disabled=false;
				document.getElementById('HOST-$t').disabled=false;
				document.getElementById('URI-$t').disabled=false;		
			}
		}
	
	
		function SaveForm$t(){
			var XHR = new XHRConnection();
			XHR.appendData('wpad','yes');
			XHR.appendData('PORT',document.getElementById('PORT-$t').value);
			XHR.appendData('IP_ADDR',document.getElementById('IP_ADDR-$t').value);
			XHR.appendData('HOST',document.getElementById('HOST-$t').value);
			XHR.appendData('URI',document.getElementById('URI-$t').value);
			AnimateDiv('div-$t');
			if(document.getElementById('ENABLE-$t').checked){XHR.appendData('ENABLE',1);	}else{XHR.appendData('ENABLE',0);}
			XHR.sendAndLoad('$page', 'POST',x_SaveForm$t);		
		
		}
		CheckWpadEnable();
	</script>
	
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function wpad_save(){
	$sock=new sockets();
	$conf=new dnsmasq();
	$page=CurrentPageName();
	$conf->ARTICA_ARRAY["WPAD"]=$_POST;
	$conf->SaveConfToServer();
	
}


function SaveConf1(){
	unset($_POST["SaveConf1"]);
	
	if($_POST["resolv-file"]=='/etc/resolv.conf'){$_POST["resolv-file"]="/etc/dnsmasq.resolv.conf";}
	
	$conf=new dnsmasq();
	while (list ($key, $line) = each ($_POST) ){
		if($line<>null){
			$conf->main_array[$key]=$line;	
		}else{unset($conf->main_array[$key]);}
		}
	$conf->SaveConf(); 
}


function DnsmasqDeleteInterface(){
	$conf=new dnsmasq();
	unset($conf->array_interface[$_GET["DnsmasqDeleteInterface"]]);
	$conf->SaveConf();
}



function SaveListenAddress(){
	$addr=$_GET["listen_addresses"];
	$conf=new dnsmasq();
	$conf->array_listenaddress[]=$addr;
	$conf->SaveConf();
}
function DnsmasqDeleteListenAddress(){
	$index=$_GET["DnsmasqDeleteListenAddress"];
	$conf=new dnsmasq();
	unset($conf->array_listenaddress[$index]);
	$conf->SaveConf();
	
}
function EnableDNSMASQOCSDB(){
	$sock=new sockets();
	$sock->SET_INFO("EnableDNSMASQOCSDB",$_POST["EnableDNSMASQOCSDB"]);
	
}

function EnableDNSMASQSave(){
	$sock=new sockets();
	
	$users=new usersMenus();
	$EnablePDNS=$sock->GET_INFO("EnablePDNS");
	if(!is_numeric($EnablePDNS)){$EnablePDNS=0;}

	
	if($_GET["EnableDNSMASQ"]==1){
		if($users->POWER_DNS_INSTALLED){
			if($EnablePDNS==1){
				$tpl=new templates();
				echo $tpl->javascript_parse_text("{COULD_NOT_PERF_OP_SOFT_ENABLED}:\n{APP_PDNS}");
				$sock->SET_INFO("EnableDNSMASQ",0);
				$sock->SET_INFO("EnableLocalDNSMASQ",0);
				return;
			}
		}

	}
	writelogs("Save EnableDNSMASQ: {$_GET["EnableDNSMASQ"]}",__FUNCTION__,__FILE__,__LINE__);
	$sock->SET_INFO("EnableDNSMASQ",$_GET["EnableDNSMASQ"]);
	$sock->SET_INFO("EnableLocalDNSMASQ",$_GET["EnableDNSMASQ"]);
	if($_GET["EnableDNSMASQ"]==1){
		$sock->SET_INFO("EnableDNSMASQ",1);
		$sock->SET_INFO("EnableLocalDNSMASQ",1);
	}
	
	
	$sock->getFrameWork("dnsmasq.php?restart=yes");
	$sock->getFrameWork("services.php?restart-artica-status=yes");
}

function page_localdomains_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{new_domain}");
	$html="YahooWin2(850,'$page?localdomain-popup&t={$_GET["t"]}','$title');";
	echo $html;	
	
}

function page_localdomains_popup(){
	$sock=new sockets();
	$conf=new dnsmasq();
	$tpl=new templates();
	$page=CurrentPageName();
	$t=$_GET["t"];
	$html="<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td valign='middle' style='font-size:22px' class=legend>{local_domain}:</td>
		<td>". Field_text("domain-$t",null,"font-size:22px;width:450px")."</td>
	</tr>		
	<tr>
		<td valign='middle' style='font-size:22px' class=legend>{dns_server}:</td>
		<td>". Field_text("DNS-$t",null,"font-size:22px;width:250px")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right' style='font-size:16px'><i>{dns_server_local_explain}</i></td>
	</tr>		
	<tr>
		<td colspan=2 align='right'><hr>". button("{add}","Save$t()",32)."</td>
	</tr>		
	</table>
	</div>
<script>
var xSave$t= function (obj) {
	$('#flexRT{$_GET["t"]}').flexReload();
	YahooWin2Hide();
}			
			
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('localdomain-add',document.getElementById('domain-$t').value);
	XHR.appendData('DNS',document.getElementById('DNS-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);	
}
</script>	
";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}


function page_localdomains(){
	$sock=new sockets();
	$conf=new dnsmasq();
	$tpl=new templates();
	$page=CurrentPageName();
	$Params=$conf->ARTICA_ARRAY["LOCALNET"];	
	$t=time();
	$domains=$tpl->_ENGINE_parse_body("{domains}");
	$dnsmasq_localdomains_explain=$tpl->javascript_parse_text("{dnsmasq_localdomains_explain}");
	$new_domain=$tpl->javascript_parse_text("{new_domain}");
	$about=$tpl->javascript_parse_text("{about2}");
	$blacklist=$tpl->_ENGINE_parse_body("{blacklist}");
	$appy=$tpl->_ENGINE_parse_body("{apply}");
	$html="
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?localdomain-search=yes',
	dataType: 'json',
	colModel : [
		{display: '$domains', name : 'domain', width : 719, sortable : false, align: 'left'},	
		{display: '&nbsp;', name : 'ItemsNumber', width :40, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'enabled', width : 50, sortable : false, align: 'center'},
		],
		
buttons : [
	{name: '<strong style=font-size:16px>$new_domain</strong>', bclass: 'add', onpress : AddDnsMasqDomain},
	{name: '<strong style=font-size:16px>$blacklist</strong>', bclass: 'Copy', onpress : BlackList$t},
	{name: '<strong style=font-size:16px>$appy</strong>', bclass: 'apply', onpress : Apply$t},
	{name: '$about', bclass: 'Help', onpress : About$t},
		],			
	
	searchitems : [
		{display: '$domains', name : 'domain'},
		],
	sortname: 'domain',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true,
	rpOptions: [200]
	
	});   
});	

function About$t(){
	alert('$dnsmasq_localdomains_explain');
}

function BlackList$t(){
	Loadjs('squid.dns.items.black.php');
}
function Apply$t(){
	Loadjs('dnsmasq.restart.progress.php');
}

	var x_DnsMasqLocalDomainEnable= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}	
		$('#flexRT$t').flexReload();
	}
	
	
	function DnsMasqLocalDomainEnable(domain,md5){
		var XHR = new XHRConnection();
		XHR.appendData('localdomain-enable', domain);
		if(document.getElementById(md5).checked){XHR.appendData('enable', 1);}else{XHR.appendData('enable', 0);}
		XHR.sendAndLoad('$page', 'POST',x_DnsMasqLocalDomainEnable); 	
	
	}
	
	function AddDnsMasqDomain(){
		Loadjs('$page?localdomain-js=yes&t=$t');
		
	}
	
	function DnsMasqLocalDomainDelete(domain){
		var XHR = new XHRConnection();
		XHR.appendData('localdomain-del', domain);
		XHR.sendAndLoad('$page', 'POST',x_DnsMasqLocalDomainEnable); 	
	
	}	
	
function Apply$t(){
	Loadjs('dnsmasq.restart.progress.php');
}	

</script>
	
	";
	
	echo $html;
	
}

function page_localdomains_enable(){
	$conf=new dnsmasq();
	$tpl=new templates();
	$page=CurrentPageName();	
	$conf->ARTICA_ARRAY["LOCALNET"][$_POST["localdomain-enable"]]=$_POST["enable"];
	$conf->SaveConf();
}



function page_localdomains_add(){
	$conf=new dnsmasq();
	$tpl=new templates();
	$page=CurrentPageName();	
	$conf->ARTICA_ARRAY["LOCALNET"][$_POST["localdomain-add"]]=1;
	if($_POST["DNS"]<>null){
		$conf->ARTICA_ARRAY["RRDNS"][$_POST["localdomain-add"]]=$_POST["DNS"];
	}
	
	$conf->SaveConf();		
	
}
function page_localdomains_del(){
	$conf=new dnsmasq();
	$tpl=new templates();
	$page=CurrentPageName();	
	unset($conf->ARTICA_ARRAY["LOCALNET"][$_POST["localdomain-del"]]);
	unset($conf->ARTICA_ARRAY["RRDNS"][$_POST["localdomain-del"]]);
	$conf->SaveConf();	
}

function page_localdomains_search(){
	$conf=new dnsmasq();
	$tpl=new templates();
	$page=CurrentPageName();
	$Params=$conf->ARTICA_ARRAY["LOCALNET"];	
	if(count($Params)==0){
		$ldap=new clladp();$hash=$ldap->AllDomains();
		$hash["localdomain"]="localdomain";
		$hash["localhost.localdomain"]="localhost.localdomain";
		while (list ($key, $line) = each ($hash) ){$conf->ARTICA_ARRAY["LOCALNET"][$key]=0;}$conf->SaveConf();
	}
	
	$data = array();
	$data['page'] = 0;
	$data['total'] = count($conf->ARTICA_ARRAY["LOCALNET"]);
	$data['rows'] = array();
	
	$search=null;
	ksort($conf->ARTICA_ARRAY["LOCALNET"]);
	$search=string_to_flexregex();
	

	while (list ($domain, $enabled) = each ($conf->ARTICA_ARRAY["LOCALNET"]) ){
		if($search<>null){if(!preg_match("#$search#", $domain)){continue;}}
		$domain_plus=null;
		$md5=md5($domain);
		$enable=Field_checkbox($md5,1,$enabled,"DnsMasqLocalDomainEnable('$domain','$md5')");	
		$delete=imgtootltip("delete-32.png","{delete} $domain","DnsMasqLocalDomainDelete('$domain')");
		$color="black";
		if($enabled==0){$color="#D0D0D0";}
		if(isset($conf->ARTICA_ARRAY["RRDNS"][$domain])){$domain_plus=" &raquo;&raquo;<i>{$conf->ARTICA_ARRAY["RRDNS"][$domain]}</i>";}
			
		
		
		
	$data['rows'][] = array(
		'id' => $md5,
		'cell' => array(
			"<span style='font-size:22px;color:$color'>$domain$domain_plus</span>",
			$enable,$delete )
		);
	}
	
	
echo json_encode($data);		

}	
	
	
	

	