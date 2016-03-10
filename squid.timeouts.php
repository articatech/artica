<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
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
	if(isset($_POST["dead_peer_timeout"])){save();exit;}
	js();
	
	
	
function js(){
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{timeouts}");
	$page=CurrentPageName();
	$html="YahooWin3('1036','$page?popup=yes','$title');";
	echo $html;	
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
	$DisableTCPEn=intval($sock->GET_INFO("DisableTCPEn"));
	$DisableTCPWindowScaling=intval($sock->GET_INFO("DisableTCPWindowScaling"));
	$SquidUploadTimeouts=intval($sock->GET_INFO("SquidUploadTimeouts"));
	$SquidConnectRetries=intval($sock->GET_INFO("SquidConnectRetries"));
	$SquidSimpleConfig=$sock->GET_INFO("SquidSimpleConfig");
	if(!is_numeric($SquidSimpleConfig)){$SquidSimpleConfig=1;}

	$t=time();
	
	$Upload_timedout[0]="{default}";
	$Upload_timedout[10]="10 {minutes}";
	$Upload_timedout[15]="15 {minutes}";
	$Upload_timedout[20]="20 {minutes}";
	$Upload_timedout[30]="30 {minutes}";
	$Upload_timedout[60]="1 {hour}";
	$Upload_timedout[120]="2 {hours}";
	$Upload_timedout[180]="3 {hours}";
	$Upload_timedout[240]="4 {hours}";
	
	
	
	for($i=0;$i<11;$i++){
		$SquidConnectRetriesZ[$i]=$i;
	}
	
	$SquidConnectRetriesZ[0]="{nottoretry}";
	
	$html="
	<div id='$t'></div>
	<table style='width:99%' class=form>
	
		<tr>
			<td align='right' class=legend style='font-size:22px;vertical-align:top'>". texttooltip("{DisableTCPEn}","{DisableTCPEn_explain}")."</strong>:</td>
			<td align='left' style='font-size:22px;vertical-align:middle'>" . 
			Field_checkbox_design("DisableTCPEn-$t",1,$DisableTCPEn)."&nbsp;</td>
		</tr>	
		<tr>
			<td align='right' class=legend style='font-size:22px;vertical-align:top'>". texttooltip("{DisableTCPWindowScaling}","{DisableTCPWindowScaling_explain}")."</strong>:</td>
			<td align='left' style='font-size:22px;vertical-align:middle'>" . 
			Field_checkbox_design("DisableTCPWindowScaling-$t",1,$DisableTCPWindowScaling)."&nbsp;</td>
			
		</tr>	
		<tr>
			<td align='right' class=legend style='font-size:22px;vertical-align:middle'>{uploads_timeout}</strong>:</td>
			<td align='left' style='font-size:22px;vertical-align:middle'>" . 
			Field_array_Hash($Upload_timedout,"SquidUploadTimeouts-$t",$SquidUploadTimeouts,"SquidUploadTimeouts$t()",null,0,"font-size:22px",false)."&nbsp;</td>
			
		</tr>
		<tr>
			<td align='right' class=legend style='font-size:22px;vertical-align:middle'>". texttooltip("{forward_max_tries}","{forward_max_tries_text}")."</strong>:</td>
			<td align='left' style='font-size:22px;vertical-align:middle'>" . 
			Field_text("forward_max_tries-$t",$squid->forward_max_tries,'width:60%;font-size:22px')."&nbsp;</td>
		</tr>
		<tr>
			<td align='right' class=legend style='font-size:22px;vertical-align:middle'>". texttooltip("{connect_retries}","{connect_retries_text}")."</strong>:</td>
			<td align='left' style='font-size:22px;vertical-align:middle'>" . 
			Field_array_Hash($SquidConnectRetriesZ,"SquidConnectRetries-$t",$SquidConnectRetries,"blur()",null,0,"font-size:22px",false)."&nbsp;</td>
			
		</tr>					
					
					
		<tr>
			<td align='right' class=legend style='font-size:22px;vertical-align:middle'>". texttooltip("{forward_timeout}","{forward_timeout_text}")."</strong>:</td>
			<td align='left' style='font-size:22px;vertical-align:middle'>" . Field_text("forward_timeout-$t",$squid->forward_timeout,'width:60%;font-size:22px')."&nbsp;{seconds}</td>
			
		</tr>					
					
		<tr>
			<td align='right' class=legend style='font-size:22px;vertical-align:middle'>". texttooltip("{client_lifetime}","{client_lifetime_text}")."</strong>:</td>
			<td align='left' style='font-size:22px;vertical-align:middle'>" . Field_text("client_lifetime-$t",$squid->client_lifetime,'width:60%;font-size:22px')."&nbsp;{seconds}</td>
			
		</tr>
		<tr>
			<td align='right' class=legend style='font-size:22px;vertical-align:middle'>". texttooltip("{shutdown_lifetime}","{shutdown_lifetime_text}")."</strong>:</td>
			<td align='left' style='font-size:22px;vertical-align:middle'>" . Field_text("shutdown_lifetime-$t",$squid->shutdown_lifetime,'width:60%;font-size:22px')."&nbsp;{seconds}</td>
			
		</tr>					
		<tr>
			<td align='right' class=legend style='font-size:22px;vertical-align:middle'>". texttooltip("{read_timeout}","{read_timeout_text}")."</strong>:</td>
			<td align='left' style='font-size:22px;vertical-align:middle'>" . Field_text("read_timeout-$t",$squid->read_timeout,'width:60%;font-size:22px')."&nbsp;{seconds}</td>
			
		</tr>	
					 	
					
		<tr>
			<td align='right' class=legend style='font-size:22px;vertical-align:middle'>". texttooltip("{dead_peer_timeout}","{dead_peer_timeout_text}")."</strong>:</td>
			<td align='left' style='font-size:22px;vertical-align:middle'>" . Field_text("dead_peer_timeout-$t",$squid->dead_peer_timeout,'width:60%;font-size:22px')."&nbsp;{seconds}</td>
			
		</tr>	
		<tr>
			<td align='right' class=legend style='font-size:22px;vertical-align:middle'>". texttooltip("{dns_timeout}","{dns_timeout_text}")."</strong>:</td>
			<td align='left' style='font-size:22px;vertical-align:middle'>" . Field_text("dns_timeout-$t",$squid->dns_timeout,'width:60%;font-size:22px')."&nbsp;{seconds}</td>
			
		</tr>		
		<tr>
			<td align='right' class=legend style='font-size:22px;vertical-align:middle'>". texttooltip("{connect_timeout}","{connect_timeout_text}")."</strong>:</td>
			<td align='left' style='font-size:22px;vertical-align:middle'>" . Field_text("connect_timeout-$t",$squid->connect_timeout,'width:60%;font-size:22px')."&nbsp;{seconds}</td>
			
		</tr>		
		<tr>
			<td align='right' class=legend style='font-size:22px;vertical-align:middle'>". texttooltip("{peer_connect_timeout}","{peer_connect_timeout_text}")."</strong>:</td>
			<td align='left' style='font-size:22px;vertical-align:middle'>" . Field_text("peer_connect_timeout-$t",$squid->peer_connect_timeout,'width:60%;font-size:22px')."&nbsp;{seconds}</td>
			
		</tr>
		<tr>
			<td align='right' class=legend style='font-size:22px;vertical-align:middle'>". texttooltip("{persistent_request_timeout}","{persistent_request_timeout_text}")."</strong>:</td>
			<td align='left' style='font-size:22px;vertical-align:middle'>" . Field_text("persistent_request_timeout-$t",$squid->persistent_request_timeout,'width:60%;font-size:22px')."&nbsp;{seconds}</td>
			
		</tr>
		<tr>
			<td align='right' class=legend style='font-size:22px;vertical-align:middle'>". texttooltip("{pconn_timeout}","{pconn_timeout_text}")."</strong>:</td>
			<td align='left' style='font-size:22px;vertical-align:middle'>" . Field_text("pconn_timeout-$t",$squid->pconn_timeout,'width:60%;font-size:22px')."&nbsp;{seconds}</td>
			
		</tr>	
		<tr>
			<td align='right' class=legend style='font-size:22px;vertical-align:middle'>". texttooltip("{incoming_rate}","{incoming_rate_text}")."</strong>:</td>
			<td align='left' style='font-size:22px;vertical-align:middle'>" . Field_text("incoming_rate-$t",$squid->incoming_rate,'width:60%;font-size:22px')."&nbsp;</td>
		</tr>
					 	
					
			
		<tr>
		<td align='right' colspan=2>
			<hr>". button("{apply}","SaveSNMP$t()",42)."
		</td>
		</tr>
	</table>
	
	<script>
	var x_SaveSNMP$t=function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
		Loadjs('squid.restart.php?prepare-js=yes');
		YahooWin3Hide();
	}	
	
	function SaveSNMP$t(){
		var lock=$EnableRemoteStatisticsAppliance;
		if(lock==1){Loadjs('squid.newbee.php?error-remote-appliance=yes');return;}	
		var XHR = new XHRConnection();
		var DisableTCPEn=0;
		var DisableTCPWindowScaling=0;
		if(document.getElementById('DisableTCPEn-$t').checked){ DisableTCPEn=1 }
		if(document.getElementById('DisableTCPWindowScaling-$t').checked){ DisableTCPWindowScaling=1 }
		
		XHR.appendData('DisableTCPWindowScaling',DisableTCPWindowScaling);
		XHR.appendData('DisableTCPEn',DisableTCPEn);
		XHR.appendData('dead_peer_timeout',document.getElementById('dead_peer_timeout-$t').value);
		XHR.appendData('dns_timeout',document.getElementById('dns_timeout-$t').value);
		XHR.appendData('connect_timeout',document.getElementById('connect_timeout-$t').value);
		XHR.appendData('peer_connect_timeout',document.getElementById('peer_connect_timeout-$t').value);
		XHR.appendData('client_lifetime',document.getElementById('client_lifetime-$t').value);
		XHR.appendData('read_timeout',document.getElementById('read_timeout-$t').value);
		XHR.appendData('shutdown_lifetime',document.getElementById('shutdown_lifetime-$t').value);
		XHR.appendData('persistent_request_timeout',document.getElementById('persistent_request_timeout-$t').value);
		XHR.appendData('incoming_rate',document.getElementById('incoming_rate-$t').value);
		XHR.appendData('pconn_timeout',document.getElementById('pconn_timeout-$t').value);
		XHR.appendData('forward_max_tries',document.getElementById('forward_max_tries-$t').value);
		XHR.appendData('forward_timeout',document.getElementById('forward_timeout-$t').value);
		XHR.appendData('SquidUploadTimeouts',document.getElementById('SquidUploadTimeouts-$t').value);
		XHR.appendData('SquidConnectRetries',document.getElementById('SquidConnectRetries-$t').value);
		XHR.sendAndLoad('$page', 'POST',x_SaveSNMP$t);	
		
	}	
	
	function SquidUploadTimeouts$t(){
		var SquidSimpleConfig=$SquidSimpleConfig;
		
		if(SquidSimpleConfig==1){
			document.getElementById('dead_peer_timeout-$t').disabled=true;
			document.getElementById('dns_timeout-$t').disabled=true;
			document.getElementById('connect_timeout-$t').disabled=true;
			document.getElementById('peer_connect_timeout-$t').disabled=true;
			document.getElementById('client_lifetime-$t').disabled=true;
			document.getElementById('read_timeout-$t').disabled=true;
			document.getElementById('persistent_request_timeout-$t').disabled=true;
			document.getElementById('incoming_rate-$t').disabled=true;
			document.getElementById('pconn_timeout-$t').disabled=true;
			document.getElementById('forward_timeout-$t').disabled=true;
			return;
		}
		
		
		document.getElementById('read_timeout-$t').disabled=false;
		document.getElementById('pconn_timeout-$t').disabled=false;
		document.getElementById('persistent_request_timeout-$t').disabled=false;
		
		
		if(document.getElementById('SquidUploadTimeouts-$t').value>0){
			document.getElementById('read_timeout-$t').disabled=true;
			document.getElementById('pconn_timeout-$t').disabled=true;
			document.getElementById('persistent_request_timeout-$t').disabled=true;
		}
	
	}
	SquidUploadTimeouts$t();
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);

}

function save(){
	$squid=new squidbee();
	$sock=new sockets();
	$sock->SET_INFO("DisableTCPEn", $_POST["DisableTCPEn"]);
	$sock->SET_INFO("DisableTCPWindowScaling", $_POST["DisableTCPWindowScaling"]);
	$sock->SET_INFO("SquidUploadTimeouts", $_POST["SquidUploadTimeouts"]);
	$sock->SET_INFO("SquidConnectRetries", $_POST["SquidConnectRetries"]);
	
	unset($_POST["SquidUploadTimeouts"]);
	unset($_POST["DisableTCPEn"]);
	unset($_POST["DisableTCPWindowScaling"]);
	while (list ($index, $line) = each ($_POST)){
		$squid->$index=$line;
		
	}
	
	$squid->SaveToLdap(true);
}