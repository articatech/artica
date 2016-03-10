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
	if(isset($_POST["EnableSNMPD"])){save();exit;}
	if(isset($_GET["snmpd-service"])){snmpd_status();exit;}
	popup();
	
	
	
function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("SNMP");
	$page=CurrentPageName();
	$html="YahooWin3('905','$page?popup=yes','$title');";
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
	
	$installed=$sock->getFrameWork("snmpd.php?installed=yes");
	
	
	if($installed<>"TRUE"){
		$button=button("{manual_install}", "Loadjs('system.snmpd.install.php');",22);
		$data=FATAL_ERROR_SHOW_128("{SNMPD_NOT_INSTALLED}<center style='margin:10px'>$button</center>");
		echo $tpl->_ENGINE_parse_body($data);
		return;
	}
	
	
	$EnableSNMPD=$sock->GET_INFO("EnableSNMPD");
	if(!is_numeric($EnableSNMPD)){$EnableSNMPD=0;}
	$SNMPDCommunity=$sock->GET_INFO("SNMPDCommunity");
	if($SNMPDCommunity==null){$SNMPDCommunity="public";}
	$t=time();
	$SNMPDNetwork=$sock->GET_INFO("SNMPDNetwork");
	if($SNMPDNetwork==null){$SNMPDNetwork="default";}
	$js2=null;
	if($users->SQUID_INSTALLED){
		$js2="LoadAjax('squid-snmp','squid.snmp.php?popup=yes');";
		
	}
	
	
	
	$html="
	<div id='$t' style='width:100%'>
	<table style=width:100%>
	<tr>
	<td style='width:350px;vertical-align:top'><span id='snmpd-service'></span></td>
	<td valign='top'>
	<div style='width:98%' class=form>
	<table >
				<tr>
			<td colspan=2 style='font-size:30px;'><strong>{monitor_your_system} (SNMP)</strong><p>&nbsp</p></td>
			
		</tr>
		<tr>
			
			<td colspan=2>".Paragraphe_switch_img("{enable_snmp} - {system}", 
					"{enable_snmp_system}","EnableSNMPD",$EnableSNMPD,null,1140)."</td>
		</tr>
	
		<tr>
			<td class=legend style='font-size:30px'>{snmp_community}:</td>
			<td style='font-size:16px'>". Field_text("SNMPDCommunity",$SNMPDCommunity,"font-size:30px;width:300px")." SNMPv2c</td>
		</tr>
		<tr>
			<td class=legend style='font-size:30px'>{allowed_network}:</td>
			<td style='font-size:16px'>". Field_text("SNMPDNetwork",$SNMPDNetwork,"font-size:30px;width:300px")." SNMPv2c</td>
		</tr>					
		<tr>
		<td align='right' colspan=2><hr>". button("{apply}", "SaveSNMP$t()","40px")."</td>
	</tr>
	</table>
	</div>
	<div id='squid-snmp'></div>
	
	</td>
	</tr>
	</table>
	</div>
	<script>
	var x_SaveSNMP$t=function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
		CacheOff();
		LoadAjaxRound('system-snmp','system.snmp.php');
	}	
	
	function SaveSNMP$t(){
		
		var XHR = new XHRConnection();
		XHR.appendData('EnableSNMPD',document.getElementById('EnableSNMPD').value);
		XHR.appendData('SNMPDCommunity',encodeURIComponent(document.getElementById('SNMPDCommunity').value));
		XHR.appendData('SNMPDNetwork',document.getElementById('SNMPDNetwork').value);
		XHR.sendAndLoad('$page', 'POST',x_SaveSNMP$t);	
		
	}	
	
	LoadAjax('snmpd-service','$page?snmpd-service=yes');
	$js2
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);

}

function save(){
	$_POST["SNMPDCommunity"]=url_decode_special_tool($_POST["SNMPDCommunity"]);
	$sock=new sockets();
	$sock->SET_INFO("EnableSNMPD", $_POST["EnableSNMPD"]);
	$sock->SET_INFO("SNMPDCommunity", $_POST["SNMPDCommunity"]);
	$sock->SET_INFO("SNMPDNetwork", $_POST["SNMPDNetwork"]);
	$sock->getFrameWork("snmpd.php?restart=yes");
}

function snmpd_status(){
	$ini=new Bs_IniHandler();
	$tpl=new templates();
	$sock=new sockets();
	$page=CurrentPageName();
	$ini->loadString(base64_decode($sock->getFrameWork('snmpd.php?status=yes')));
	echo "<div style='width:300px;'>".$tpl->_ENGINE_parse_body(DAEMON_STATUS_ROUND("APP_SNMPD",$ini,null,1))."
			<div style='text-align:right'>". imgtootltip("refresh-24.png","{refresh}","LoadAjax('snmpd-service','$page?snmpd-service=yes');")."</div>
			</div>";
	
}
