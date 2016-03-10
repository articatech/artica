<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.system.nics.inc');
	include_once('ressources/class.ccurl.inc');
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["eth"])){step0_save();exit;}
	if(isset($_POST["ucarp_vid"])){step0_save();exit;}
	if(isset($_POST["second_ipaddr"])){step0_save();exit;}
	if(isset($_POST["SLAVE"])){step0_save();exit;}
	
	
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["step1"])){step1();exit;}
	if(isset($_GET["step2"])){step2();exit;}
	if(isset($_GET["step3"])){step3();exit;}
	if(isset($_GET["step4"])){step4();exit;}
	if(isset($_GET["step5"])){step5();exit;}

	if(isset($_GET["unlink-js"])){unlink_js();exit;}
	if(isset($_GET["unlink-step1"])){unlink_setp1();exit;}
	if(isset($_GET["unlink-step2"])){unlink_setp2();exit;}
	if(isset($_GET["unlink-notify-slave"])){unlink_notify_slave();exit;}
	if(isset($_GET["unlink-reconfigure"])){unlink_reconfigure();exit;}
	if(isset($_GET["unlink-step3"])){unlink_setp3();exit;}
	
	if(isset($_POST["StepR1"])){die();}
	
js();

function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{failover}");
	echo "YahooWin6('650','$page?popup=yes','$title')";

}

function unlink_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{welcome_php_failoverunlink_explain_2}");
	$t=time();

echo "
function start$t(){
	if(!confirm('$title')){return;}
	Loadjs('squid.failover.unlink.progress.php');
}
	
start$t();";
}


function status(){
	$page=CurrentPageName();
	$qs=new mysql();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	$ini=new Bs_IniHandler();
	
	if(!$users->CORP_LICENSE){
		$error="<p class=text-error>".$tpl->_ENGINE_parse_body("{this_feature_is_disabled_corp_license}")."</p>";
	}
	
	if(!$users->UCARP_INSTALLED){
		$error="<p class=text-error>".$tpl->_ENGINE_parse_body("{error_missing_software}")."</p>";
	}
	
	$sql="SELECT COUNT(*) as tcount FROM nics WHERE `ucarp-enable`=1";
	$ligne2=mysql_fetch_array($qs->QUERY_SQL($sql,"artica_backup"));
	
	
	$data=base64_decode($sock->getFrameWork('system.php?ucarp-status-service=yes'));
	$MAIN=unserialize(base64_decode($sock->GET_INFO("HASettings")));
	$array=unserialize(base64_decode($sock->getFrameWork('system.php?ucarp-isactive=yes')));
	
	if(isset($array["NIC"])){
		$l[]="[UCARP_NIC]";
		$l[]="service_name={$array["NIC"]}";
		$l[]="master_version={$array["IP"]}";
		$l[]="service_cmd=/etc/init.d/artica-failover";
		$l[]="service_disabled=1";
		$l[]="watchdog_features=1";
		$l[]="running=1";
		$l[]="master_pid=1";
		$l[]="processes_number=1";
		$l[]="";
		
		
	}else{
		$l[]="[UCARP_NIC]";
		$l[]="service_name={$MAIN["eth"]}";
		$l[]="master_version=0.0.0.0";
		$l[]="service_cmd=/etc/init.d/artica-failover";
		$l[]="service_disabled=1";
		$l[]="watchdog_features=1";
		$l[]="running=0";
		$l[]="master_pid=0";
		$l[]="processes_number=0";
		$l[]="";
		
	}
	
	$ini->loadString($data.@implode("\n", $l));
	
	$UCARP_MASTER=DAEMON_STATUS_ROUND("UCARP_MASTER",$ini,null,1);
	$UCARP_SLAVE=DAEMON_STATUS_ROUND("UCARP_SLAVE",$ini,null,1);
	$UCARP_NIC=DAEMON_STATUS_ROUND("UCARP_NIC",$ini,null,0);
	
	
	$SLAVE_IP=$MAIN["SLAVE"];
	$ucarp_vid=$MAIN["ucarp_vid"];
	
	
	
	
	
	
	
	

	
	if($SLAVE_IP<>null){
		$ip=new system_nic($MAIN["eth"]);
		$statTable=
		"<table style='width:99%;background-color:#D5EED9;padding:10px;border:1px solid #005447;margin:10px;border-radius:5px 5px 5px 5px'>
		<tr>
			<td class=legend style='font-size:18px'>{interface}:</td>
			<td style='font-size:18px'>{$MAIN["eth"]} - $ip->ucarp_vip</td>
		</tr>
		<tr>
			<td class=legend style='font-size:18px'>{second_ipaddr}:</td>
			<td style='font-size:18px'>{$MAIN["eth"]} - {$MAIN["second_ipaddr"]}</td>
		</tr>		
		
		<tr>
			<td class=legend style='font-size:18px'>{netzone}:</td>
			<td style='font-size:18px'>{$ucarp_vid}</td>
		</tr>			
		<tr>
			<td class=legend style='font-size:18px'>{slave}:</td>
			<td style='font-size:18px'>{$MAIN["SLAVE"]}</td>
		</tr>				
	</table>
	<br>
		<div style='width:90%;text-align:right;margin-bottom:20px;font-size:32px'>".
		button("{unlink}", "Loadjs('$page?unlink-js=yes')",32)."&nbsp;&nbsp;|&nbsp;&nbsp;".
		button("{unlink} - Force", "Loadjs('squid.failover.unlink.progress.php?force=yes');",32)."
				
		</div>
	";
		
		
		
		
	}else{
		
		$statTable="<div style='width:90%;text-align:center;margin:20px'>".
		button("{failover_wizard}", "Loadjs('$page')",42)."</div>";
	}
	

	
	
	
	
	$html="<div style='width:98%' class=form>
	<table style='width:99%'>
	<tr>
		<td style='text-align:left;vertical-align:top;width:30%'>
			<p>&nbsp;</p>$UCARP_MASTER<br>$UCARP_SLAVE<br>$UCARP_NIC
			
		<div style='width:100%;text-align:right'>". imgtootltip("refresh-32.png","{refresh}","RefreshTab('main_failover_tabs')")."</div>	
		</td>
		<td	style='text-align:left;vertical-align:top;width:70%'>
				<div style='font-size:45px;margin-bottom:15px'>{failover}</div>
				<div class=explain style='font-size:20px;'>{failover_explain}</div>
				$error
				$statTable
		</td>
	</tr>
	</table>
	</div>		
			
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function tabs(){
	
	
	
		$tpl=new templates();
		$page=CurrentPageName();
		$array["status"]='{status}';
		$array["events"]='{events}';

		while (list ($num, $ligne) = each ($array) ){
			
			if($num=="events"){
				$html[]= $tpl->_ENGINE_parse_body("<li>
						<a href=\"squid.watchdog-events.php?text-filter=failover\" 
						style='font-size:26px'><span>$ligne</span></a></li>\n");
				continue;
			}
			
			
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\" style='font-size:26px'><span>$ligne</span></a></li>\n");
			
		}
	
	
	
		echo build_artica_tabs($html, "main_failover_tabs",1490)."<script>LeftDesign('failover-256-opac20.png');</script>";
	
	
	
	
}


function popup(){
	$tpl=new templates();
	$sock=new sockets();
	$net=new networking();
	$page=CurrentPageName();
	$MAIN=unserialize(base64_decode($sock->GET_INFO("HASettings")));
	
	$interfaces=$net->Local_interfaces();
	while (list ($eth, $line) = each ($interfaces) ){
		if($eth=="lo"){continue;}
		$ip=new system_nic($eth);
		$array[$eth]=$ip->IPADDR." (" .$ip->NICNAME .")";
	}
	
	$t=time();
	
	$html="
	<div id='failover-title' style='font-size:22px'></div>
	<div id='failover-div'>		
	<div style='font-size:16px' class=explain>{welcome_php_failover_explain}</div>
	<p>&nbsp;</p>
	<div style='font-size:16px' class=explain>{welcome_php_failover_explain_1}</div>
	<p>&nbsp;</p>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px'>{interface}:</td>
		<td>". Field_array_Hash($array, "eth-$t",$MAIN["eth"],null,null,0,"font-size:16px")."</td>
	</tr>
	</table>
	<div style='text-align:right'><hr>". button("{next}", "Step0()",26)."</div>
	</div>
<script>
function xStep0(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	LoadAjax('failover-div','$page?step1=yes',true);

}			
			
function Step0(){
	var XHR = new XHRConnection();
	XHR.appendData('eth',document.getElementById('eth-$t').value);
	XHR.sendAndLoad('$page', 'POST',xStep0);	
}
			
			
</script>
			
	";
echo $tpl->_ENGINE_parse_body($html);
}






function step1(){
	$tpl=new templates();
	$sock=new sockets();
	$net=new networking();
	$page=CurrentPageName();
	$MAIN=unserialize(base64_decode($sock->GET_INFO("HASettings")));	
	$eth=$MAIN["eth"];
	$t=time();
	
	$nic=new system_nic($eth);
	for($i=1;$i<256;$i++){
		$ucarp_vids[$i]=$i;
	}
	$ip=new system_nic($MAIN["eth"]);
	if(!is_numeric($MAIN["ucarp_vid"])){$MAIN["ucarp_vid"]=3;}
	
	$html="
	<div id='failover-div'>
	<div style='font-size:16px' class=explain>{welcome_php_failover_explain_2}</div>
	<p>&nbsp;</p>
	<div style='width:98%' class=form>	
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px'>{interface}:</td>
		<td style='font-size:16px'>{$MAIN["eth"]} - $ip->IPADDR</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:16px'>{netzone}:</td>
		<td>". Field_array_Hash($ucarp_vids, "ucarp_vid-$t",$MAIN["ucarp_vid"],null,null,0,"font-size:16px")."</td>
	</tr>
	</table>
				
	<div style='text-align:right'><hr>". button("{next}", "Step1()",26)."</div>
	</div>
<script>
function xStep1(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	LoadAjax('failover-div','$page?step2=yes',true);

}			
			
function Step1(){
	var XHR = new XHRConnection();
	XHR.appendData('ucarp_vid',document.getElementById('ucarp_vid-$t').value);
	XHR.sendAndLoad('$page', 'POST',xStep1);	
}
			
			
</script>
";
	echo $tpl->_ENGINE_parse_body($html);
}
function step2(){
	$tpl=new templates();
	$sock=new sockets();
	$net=new networking();
	$page=CurrentPageName();
	$MAIN=unserialize(base64_decode($sock->GET_INFO("HASettings")));
	$eth=$MAIN["eth"];
	$t=time();

	$nic=new system_nic($eth);

	
	if(!is_numeric($MAIN["ucarp_vid"])){$MAIN["ucarp_vid"]=3;}
	
	$welcome_php_failover_explain_net=$tpl->_ENGINE_parse_body("{welcome_php_failover_explain_net}");
	$welcome_php_failover_explain_net=str_replace("%s", $ip->IPADDR, $welcome_php_failover_explain_net);
	$html="
	<div style='font-size:16px' class=explain>$welcome_php_failover_explain_net</div>
	<p>&nbsp;</p>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px'>{interface}:</td>
		<td style='font-size:16px'>{$MAIN["eth"]} - $nic->IPADDR</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:16px'>{netzone}:</td>
		<td style='font-size:16px'>{$MAIN["ucarp_vid"]}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{second_ipaddr}:</td>
		<td style='font-size:16px'>". field_ipv4("second_ipaddr-$t", $MAIN["second_ipaddr"],"font-size:16px")."</td>
	</tr>	
	</table>
	<table style='width:100%'>
		<tr>
			<td width=50%><div style='text-align:left'><hr>". button("{back}", "LoadAjax('failover-div','$page?step1=yes')",26)."</div></td>
			<td width=50%><div style='text-align:right'><hr>". button("{next}", "Step2()",26)."</div></td>
		</tr>
	</table>
<script>
function xStep1(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	LoadAjax('failover-div','$page?step3=yes',true);
}
				
function Step2(){
	var XHR = new XHRConnection();
	XHR.appendData('second_ipaddr',document.getElementById('second_ipaddr-$t').value);
	XHR.appendData('first_ipaddr','$nic->IPADDR');
	XHR.sendAndLoad('$page', 'POST',xStep1);
}
						
	
</script>
";
	echo $tpl->_ENGINE_parse_body($html);
}

function step3(){
	$tpl=new templates();
	$sock=new sockets();
	$net=new networking();
	$page=CurrentPageName();
	$MAIN=unserialize(base64_decode($sock->GET_INFO("HASettings")));	
	$eth=$MAIN["eth"];
	$t=time();
	
	if(!is_numeric($MAIN["ucarp_vid"])){$MAIN["ucarp_vid"]=3;}
	if(!is_numeric($MAIN["SLAVE_SSL"])){$MAIN["SLAVE_SSL"]=1;}
	if(!is_numeric($MAIN["SLAVE_PORT"])){$MAIN["SLAVE_PORT"]=9000;}
	$ip=new networking();
	
	while (list ($eth, $cip) = each ($ip->array_TCP) ){
		if($cip==null){continue;}
		$arrcp[$cip]=$cip;
	}
	
	$arrcp[null]="{default}";
	$nic=new system_nic($MAIN["eth"]);
	$WgetBindIpAddress=$sock->GET_INFO("WgetBindIpAddress");
	$WgetBindIpAddress=Field_array_Hash($arrcp,"WgetBindIpAddress",$WgetBindIpAddress,null,null,0,"font-size:19px;padding:3px;");
	
	$html="
	<div id='$t'>
	<div class=explain style='font-size:16px'>{welcome_php_failover_explain_3}</div>
	<div class=form style='width:95%'>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px'>{interface}:</td>
		<td style='font-size:16px'>{$MAIN["eth"]} - {$MAIN["first_ipaddr"]}</td>
		<td>&nbsp;</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:16px'>{netzone}:</td>
		<td style='font-size:16px'>{$MAIN["ucarp_vid"]}</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{second_ipaddr}:</td>
		<td style='font-size:16px'>{$MAIN["second_ipaddr"]}</td>
		<td>&nbsp;</td>
	</tr>		
	<tr>
	<td class=legend style='font-size:16px'>{WgetBindIpAddress}:</td>
	<td style='font-size:14px'>$WgetBindIpAddress</td>
	<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{hostname}/IP ({slave}):</td>
		<td style='font-size:14px'>". Field_text("SLAVE-$t",$MAIN["SLAVE"],"font-size:19px;font-weight:bold;width:200px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{listen_port}:</td>
		<td style='font-size:14px'>". Field_text("SLAVE_PORT-$t",$MAIN["SLAVE_PORT"],"font-size:19px;width:90px")."</td>
	   <td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{use_ssl}:</td>
		<td style='font-size:14px'>". Field_checkbox("SLAVE_SSL-$t",1,$MAIN["SLAVE_SSL"])."</td>
		<td>&nbsp;</td>
	</tr>
	</table>
	<table style='width:100%'>
		<tr>
			<td width=50%><div style='text-align:left'><hr>". button("{back}", "LoadAjax('failover-div','$page?step2=yes')",26)."</div></td>
			<td width=50%><div style='text-align:right'><hr>". button("{next}", "Step3()",26)."</div></td>
		</tr>
	</table>	
<script>
	var xStep3=function (obj) {
	var results=obj.responseText;
	if(results.length>10){alert(results);}
	LoadAjax('$t','$page?step4=yes&t=$t');
}
	
function Step3(){
	var XHR = new XHRConnection();
	if(document.getElementById('SLAVE_SSL-$t').checked){XHR.appendData('SLAVE_SSL','1');}else{XHR.appendData('SLAVE_SSL','0');}
	XHR.appendData('SLAVE',document.getElementById('SLAVE-$t').value);
	XHR.appendData('SLAVE_PORT',document.getElementById('SLAVE_PORT-$t').value);
	XHR.appendData('WgetBindIpAddress',document.getElementById('WgetBindIpAddress').value);
	AnimateDiv('$t');
	XHR.sendAndLoad('$page', 'POST',xStep3);
}
	

</script>";
echo $tpl->_ENGINE_parse_body($html);
}

function step4(){
	$tpl=new templates();
	$sock=new sockets();
	$net=new networking();
	$page=CurrentPageName();
	$MAIN=unserialize(base64_decode($sock->GET_INFO("HASettings")));
	$eth=$MAIN["eth"];
	$t=time();

	$nic=new system_nic($eth);
	$connecting=$tpl->_ENGINE_parse_body("{connecting_to}");
	
	if(!is_numeric($MAIN["ucarp_vid"])){$MAIN["ucarp_vid"]=3;}
$html="
	<div style='font-size:16px' class=explain>{welcome_php_failover_explain_4}</div>
	<p>&nbsp;</p>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px'>{interface}:</td>
		<td style='font-size:16px'>{$MAIN["eth"]} - {$MAIN["first_ipaddr"]}</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:16px'>{netzone}:</td>
		<td style='font-size:16px'>{$MAIN["ucarp_vid"]}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{second_ipaddr}:</td>
		<td style='font-size:16px'>{$MAIN["second_ipaddr"]}</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{hostname}/IP ({slave}):</td>
		<td style='font-size:16px'>{$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]}</td>
	</tr>
	</table>
	<table style='width:100%'>
		<tr>
			<td width=50%><div style='text-align:left'><hr>". button("{back}", "LoadAjax('failover-div','$page?step3=yes')",26)."</div></td>
			<td width=50%><div style='text-align:right'><hr>". button("{next}", "Step4()",26)."</div></td>
		</tr>
	</table>
<script>
function Step4(){
	document.getElementById('failover-title').innerHTML='$connecting {$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]}';
	Loadjs('squid.failover.progress.php');
}
						
	
</script>
";
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function step0_save(){
	$sock=new sockets();
	$MAIN=unserialize(base64_decode($sock->GET_INFO("HASettings")));
	while (list ($key, $val) = each ($_POST) ){
		$MAIN[$key]=$val;
	}
	//first_ipaddr
	$sock->SaveConfigFile(base64_encode(serialize($MAIN)), "HASettings");
}



function wizard_restart(){
	$page=CurrentPageName();
	return "<center style='margin:20px'>
			". button("{back}", "LoadAjax('failover-div','$page?step4=yes')",22)."
		  </center>";
}
function debug_curl($array){
	$t[]="<table style='width:100%'>";

	while (list ($num, $val) = each ($array) ){
		if(is_array($val)){
			while (list ($a, $b) = each ($val) ){$tt[]="<li>$a = $b</li>";}
			$val=null;
			$val=@implode("\n", $tt);
		}
		$val=wordwrap($val,90,"<br>");
		if(strtolower($num)=="url"){continue;}
		$t[]="<tr>
		<td class=legend style='font-size:14px'>$num:</td>
		<td style='font-size:14px'>$val</td>
		</tr>";

	}
	$t[]="</table>";

	return @implode("\n", $t);

}




