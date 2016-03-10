<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["enable_snmp"])){enable_snmp();exit;}
	if(isset($_GET["mib-js"])){mib_js();exit;}
	if(isset($_GET["mib-popup"])){mib_popup();exit;}
	js();
	
	
	
function js(){
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$title=$tpl->_ENGINE_parse_body("SNMP");
	$page=CurrentPageName();
	$html="YahooWin3('600','$page?popup=yes','$title');";
	echo $html;	
}

function mib_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$html="YahooWin4('700','$page?mib-popup=yes','mib.txt');";
	echo $html;		
	
}
function mib_popup(){
	$sock=new sockets();
	$datas=base64_decode($sock->getFrameWork("squid.php?mib=yes"));
echo "<textarea style='margin-top:5px;font-family:Courier New;
		font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;overflow:auto;font-size:11.5px' 
		id='mibtxt$t'>$datas</textarea>";
	
}


function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$squid=new squidbee();
	$sock=new sockets();
	$users=new usersMenus();	
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}		
	$DisableSquidSNMPAll=intval($sock->GET_INFO("DisableSquidSNMPAll"));
	$EnableProxyInSNMPD=intval($sock->GET_INFO("EnableProxyInSNMPD"));
	
	
	
	$SquidSNMPPort=intval($sock->GET_INFO("SquidSNMPPort"));
	$SquidSNMPComunity=$sock->GET_INFO("SquidSNMPComunity");
	if($SquidSNMPPort==0){$SquidSNMPPort=$squid->snmp_port;}
	if($SquidSNMPComunity==null){$SquidSNMPComunity=$squid->snmp_community;}
	if($SquidSNMPComunity==null){$SquidSNMPComunity="public";}
	
	
	$arrayParams=unserialize(base64_decode($sock->getFrameWork("squid.php?compile-list=yes")));
	$t=time();
	
	if(!isset($arrayParams["--enable-snmp"])){
		if($EnableWebProxyStatsAppliance==0){
			echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{error_squid_snmp_not_compiled}"));
			return;
		}
	}
	
	
	$html="
	<div id='$t' style='width:98%' class=form>
	<table style='width:100%' >
		<tr>
			<td colspan=2 style='font-size:30px'>
			". Paragraphe_switch_img("{monitor_proxy_service} (SNMP)", "{monitor_proxy_service_snmpd_explain}","EnableProxyInSNMPD",$EnableProxyInSNMPD,null,1140)."
			</td>
			
		</tr>
		<tr>
			<td class=legend style='font-size:30px'>{snmp_community}:</td>
			<td>". Field_text("snmp_community",$SquidSNMPComunity,"font-size:30px;width:300px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:30px'>{listen_port}:</td>
			<td>". Field_text("snmp_port",$SquidSNMPPort,"font-size:30px;width:150px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:30px'>{limit_access}:</td>
			<td>". Field_checkbox_design("DisableSquidSNMPAll",1,$DisableSquidSNMPAll,"DisableSquidSNMPAllCheck()")."</td>
		</tr>						
		<tr>
			<td class=legend style='font-size:30px'>{remote_snmp_console_ip}:</td>
			<td>". Field_text("snmp_access_ip",$squid->snmp_access_ip,"font-size:30px;width:300px")."</td>
		</tr>
		<tr>
			<td align='right' colspan=2 style='font-size:30px'><a href=\"javascript:blur();\" 
			OnClick=\"javascript:Loadjs('$page?mib-js=yes');\" 
			style='font-size:28px;text-decoration:underline'
			>mib.txt</a>		
			
			</td>
		</tr>				
	<tr>
		<td align='right' colspan=2><hr>". button("{apply}", "SaveSNMP$t()","40px")."</td>
	</tr>
	</table>
	</div>
	<script>
	var x_SaveSNMP$t=function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
		Loadjs('squid.compile.progress.php');
		YahooWin3Hide();
	}	
	
	function SaveSNMP$t(){
		var lock=$EnableRemoteStatisticsAppliance;
		if(lock==1){Loadjs('squid.newbee.php?error-remote-appliance=yes');return;}	
		var XHR = new XHRConnection();
		var enable_snmp=0;
		var DisableSquidSNMPAll=0;
		if(document.getElementById('DisableSquidSNMPAll').checked){DisableSquidSNMPAll=1;}
		XHR.appendData('enable_snmp',enable_snmp);
		XHR.appendData('DisableSquidSNMPAll',DisableSquidSNMPAll);		
		XHR.appendData('snmp_community',encodeURIComponent(document.getElementById('snmp_community').value));
		XHR.appendData('snmp_port',document.getElementById('snmp_port').value);
		XHR.appendData('snmp_access_ip',document.getElementById('snmp_access_ip').value);
		XHR.appendData('EnableProxyInSNMPD',document.getElementById('EnableProxyInSNMPD').value);
		
		if(document.getElementById('EnableSNMPD')){
			XHR.appendData('EnableSNMPD',document.getElementById('EnableSNMPD').value);
		}
		
		if(document.getElementById('SNMPDCommunity')){
			XHR.appendData('SNMPDCommunity',encodeURIComponent(document.getElementById('SNMPDCommunity').value));
		}
		
		if(document.getElementById('SNMPDNetwork')){
			XHR.appendData('SNMPDNetwork',document.getElementById('SNMPDNetwork').value);
		}
		
		
		XHR.sendAndLoad('$page', 'POST',x_SaveSNMP$t);	
		
	}	
	
	
	function DisableSquidSNMPAllCheck(){
		document.getElementById('snmp_access_ip').disabled=true;
		if(document.getElementById('DisableSquidSNMPAll').checked){	
			document.getElementById('snmp_access_ip').disabled=false;
		}
	
	
	}
	DisableSquidSNMPAllCheck();
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);

}

function enable_snmp(){
	$sock=new sockets();
	$squid=new squidbee();
	$sock=new sockets();
	if(isset($_POST["SNMPDCommunity"])){
		$_POST["SNMPDCommunity"]=url_decode_special_tool($_POST["SNMPDCommunity"]);
		$sock->SET_INFO("SNMPDCommunity", $_POST["SNMPDCommunity"]);
	}
	
	if(isset($_POST["EnableSNMPD"])){
		$sock->SET_INFO("EnableSNMPD", $_POST["EnableSNMPD"]);
	}
	if(isset($_POST["SNMPDNetwork"])){
		$sock->SET_INFO("SNMPDNetwork", $_POST["SNMPDNetwork"]);
	}
	
	
	
	$squid->snmp_enable=$_POST["enable_snmp"];
	$squid->snmp_community=url_decode_special_tool($_POST["snmp_community"]);
	$squid->snmp_port=$_POST["snmp_port"];
	$squid->snmp_access_ip=$_POST["snmp_access_ip"];
	$sock->SET_INFO("DisableSquidSNMPAll", $_POST["DisableSquidSNMPAll"]);
	$squid->SaveToLdap(true);
	$sock->getFrameWork("snmpd.php?restart=yes");
	
}