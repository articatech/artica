<?php
	if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.ini.inc');
	
	$users=new usersMenus();
	if(!$users->AsSquidAdministrator){
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		die();
	}
	
	if(isset($_GET["settings"])){settings();exit;}
	if(isset($_POST["EnableSquidQuotasBandwidth"])){EnableSquidQuotasBandwidth();exit;}
tabs();


function tabs(){
	$tpl=new templates();
	$sock=new sockets();
	$InfluxUseRemote=intval($sock->GET_INFO("InfluxUseRemote"));
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	if($InfluxUseRemote==1){
		$InfluxSyslogRemote=intval($sock->GET_INFO("InfluxSyslogRemote"));
		if($InfluxSyslogRemote==1){
			echo FATAL_ERROR_SHOW_128("{feature_disabled_influxsyslog}");
			return;
		}
	}
	
	if($SquidPerformance>1){
		echo FATAL_ERROR_SHOW_128("{proxy_performance_is_set_to_lowlevel}<br>{artica_statistics_disabled}
		<br><div style='text-align:right'><span style='font-size:30px;font-weight:bold'>". texttooltip("{see}:{performance}","position:top:{performance_squid_explain}",
				"GotoSquidPerformances()")."</span></div>
				
		");
		return;
	}
	
	$fontsize=24;
	$page=CurrentPageName();

	$tpl=new templates();
	$array["settings"]="{parameters}";
	$array["rules"]="{rules}";
	$array["status"]="{status}";
	
	$array["events"]="{events}";




	while (list ($num, $ligne) = each ($array) ){

		if($num=="webevents"){
			$tab[]="<li><a href=\"UpdateUtility.events.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}
		
		if($num=="rules"){
			$tab[]="<li><a href=\"squid.quotasband.rules.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}		
		if($num=="status"){
			$tab[]="<li><a href=\"squid.quotasband.status.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}
		
		if($num=="events"){
			$tab[]="<li><a href=\"squid.quotasband.events.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}
		
		
		$tab[]="<li><a href=\"$page?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			
	}

	echo build_artica_tabs($tab, "main_squid_quotas_bandwidth_config",1490);


}

function settings(){
	$tpl=new templates();
	$sock=new sockets();
	$page=CurrentPageName();
	$EnableSquidQuotasBandwidth=intval($sock->GET_INFO("EnableSquidQuotasBandwidth"));
	$SquidQuotaBandwidthRefresh=intval($sock->GET_INFO("SquidQuotaBandwidthRefresh"));
	$t=time();
	
	if($SquidQuotaBandwidthRefresh==0){$SquidQuotaBandwidthRefresh=30;}
	$SquidQuotaBandwidthRefresh_array[15]="15 {minutes}";
	$SquidQuotaBandwidthRefresh_array[30]="30 {minutes}";
	$SquidQuotaBandwidthRefresh_array[60]="1 {hour}";
	$SquidQuotaBandwidthRefresh_array[120]="2 {hours}";
	
	$p=Paragraphe_switch_img("{quotas_bandwidth}", "{quotas_bandwidth_explain}","EnableSquidQuotasBandwidth",$EnableSquidQuotasBandwidth,null,1400);
	
	$html="<div style='width:98%' class=form>
	
	<p class=text-error style='font-size:22px'>Beta 1 Mode</p>
		$p
			
			
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{scan_each}:</td>
		<td>". Field_array_Hash($SquidQuotaBandwidthRefresh_array, "SquidQuotaBandwidthRefresh",$SquidQuotaBandwidthRefresh,"style:font-size:22px")."</td>
	</tr>
	</table>
	
	
	
		<div style='margin-top:20px;text-align:right'>". button("{apply}", "Save$t()",40)."</div>
		
				
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	Loadjs('squid.quotasband.progress.php');
	RefreshTab('main_squid_quotas_bandwidth_config');
}	

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('EnableSquidQuotasBandwidth',document.getElementById('EnableSquidQuotasBandwidth').value);
	XHR.appendData('SquidQuotaBandwidthRefresh',document.getElementById('SquidQuotaBandwidthRefresh').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);	
}
</script>
";
	
echo $tpl->_ENGINE_parse_body($html);	
	
}
function EnableSquidQuotasBandwidth(){
	$sock=new sockets();
	$sock->SET_INFO("EnableSquidQuotasBandwidth", $_POST["EnableSquidQuotasBandwidth"]);
	$sock->SET_INFO("SquidQuotaBandwidthRefresh", $_POST["SquidQuotaBandwidthRefresh"]);
	
}
