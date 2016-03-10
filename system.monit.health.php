<?php
if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.dansguardian.inc');
include_once('ressources/class.squid.inc');



$usersmenus=new usersMenus();
if(!$usersmenus->AsSystemAdministrator){
	$tpl=new templates();
	echo FATAL_ERROR_SHOW_128('{ERROR_NO_PRIVS}');
	die();
}

if(isset($_POST["MonitReportLoadVG1mn"])){Save();exit;}
if(isset($_GET["monit-status"])){status();exit;}

page();



function page(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$LOAD[0]="{no_monitoring}";
	$t=time();
	for($i=1;$i<151;$i++){
		
		$LOAD[$i]=$i;
		
	}
	
	$MINUTES[1]="{during} 1 {minute}";
	for($i=2;$i<121;$i++){
		$MINUTES[$i]="{during} $i {minutes}";
	}
	
	$CPU[0]="{no_monitoring}";
	for($i=50;$i<101;$i++){
	
		$CPU[$i]="{$i}%";
	
	}
	
	$MonitCPUUsage=intval($sock->GET_INFO("MonitCPUUsage"));
	$MonitCPUUsageCycles=intval($sock->GET_INFO("MonitCPUUsageCycles"));
	
	$MonitMemUsage=intval($sock->GET_INFO("MonitMemUsage"));
	$MonitMemUsageCycles=intval($sock->GET_INFO("MonitMemUsageCycles"));
	
	$MonitReportLoadVG1mn=intval($sock->GET_INFO("MonitReportLoadVG1mn"));
	$MonitReportLoadVG1mnCycles=intval($sock->GET_INFO("MonitReportLoadVG1mnCycles"));
	
	if($MonitReportLoadVG1mnCycles==0){$MonitReportLoadVG1mnCycles=5;}
	
	$MonitReportLoadVG5mn=intval($sock->GET_INFO("MonitReportLoadVG5mn"));
	$MonitReportLoadVG5mnCycles=intval($sock->GET_INFO("MonitReportLoadVG5mnCycles"));
	
	if($MonitReportLoadVG5mnCycles==0){$MonitReportLoadVG5mnCycles=15;}
	
	$MonitReportLoadVG15mn=intval($sock->GET_INFO("MonitReportLoadVG15mn"));
	$MonitReportLoadVG15mnCycles=intval($sock->GET_INFO("MonitReportLoadVG15mnCycles"));
	
	if($MonitReportLoadVG15mnCycles==0){$MonitReportLoadVG15mnCycles=60;}
	
	$MonitMemPurgeCache=intval($sock->GET_INFO("MonitMemPurgeCache"));
	$MonitMemPurgeCacheCycles=intval($sock->GET_INFO("MonitMemPurgeCacheCycles"));
	$MonitMemPurgeCacheLevel=intval($sock->GET_INFO("MonitMemPurgeCacheLevel"));
	$MonitMemPurgeCacheLevelCycles=intval($sock->GET_INFO("MonitMemPurgeCacheLevelCycles"));
	if($MonitMemUsageCycles==0){$MonitMemUsageCycles=5;}
	if($MonitMemPurgeCache==0){$MonitMemPurgeCache=70;}
	if($MonitMemPurgeCacheCycles==0){$MonitMemPurgeCacheCycles=5;}
	if($MonitMemPurgeCacheLevel==0){$MonitMemPurgeCacheLevel=3;}
	if($MonitMemPurgeCacheLevelCycles==0){$MonitMemPurgeCacheLevelCycles=15;}
	
	$EnableDNSPerfs=intval($sock->GET_INFO("EnableDNSPerfs"));
	
	if($MonitCPUUsage>0){
		if($MonitCPUUsage<50){
			$MonitCPUUsage=90;
		}
	}
	
	if($MonitMemUsage>0){
		if($MonitMemUsage<50){
			$MonitMemUsage=90;
		}
	}	
	
	if($MonitCPUUsageCycles==0){$MonitCPUUsageCycles=15;}
	
	
	$FREE[1]="pagecache";
	$FREE[2]="dentries & inodes";
	$FREE[3]="{all}";
	
$html="<div style='font-size:30px;margin-bottom:20px'>{system_health_checking}: {APP_MONIT}</div>
<div class=explain style='font-size:22px;margin-bottom:20px'>{system_health_checking_explain}</div>		
<table style='width:100%'>
<tr>
	<td valign='top' style='width:350px'><div id='health-monit-here'><div id='monit-status'></div></div></td>
	<td valign='top'>
		<div style='width:98%' class=form>
		
		
		
		
		
		<table style='width:100%'>
			<tr style='height:90px'>
				<td style='font-size:30px' colspan=3>{DNS_checking}:</td>
			</tr>		
			<tr>
				<td style='font-size:22px;' class=legend>{check_dns_performance}:</td>
				<td>". Field_checkbox_design("EnableDNSPerfs", 1,$EnableDNSPerfs)."</td>
				<td>&nbsp;</td>
			</tr>		
		
		
		
		<table style='width:100%'>
			<tr style='height:90px'>
				<td style='font-size:30px' colspan=3>{max_system_load}:</td>
			</tr>
			<tr>
				<td style='font-size:22px;' class=legend>{if_system_load_exceed}:(1 {minute})</td>
				<td>". Field_array_Hash($LOAD, "MonitReportLoadVG1mn",$MonitReportLoadVG1mn,"style:font-size:22px")."</td>
				<td>". Field_array_Hash($MINUTES, "MonitReportLoadVG1mnCycles",$MonitReportLoadVG1mnCycles,"style:font-size:22px")."</td>
			</tr>
			<tr><td colspan=3>&nbsp;</td></tr>
			<tr>
				<td style='font-size:22px;' class=legend>{if_system_load_exceed}:(5 {minutes})</td>
				<td>". Field_array_Hash($LOAD, "MonitReportLoadVG5mn",$MonitReportLoadVG5mn,"style:font-size:22px")."</td>
				<td>". Field_array_Hash($MINUTES, "MonitReportLoadVG5mnCycles",$MonitReportLoadVG5mnCycles,"style:font-size:22px")."</td>
			</tr>	
				<tr><td colspan=3>&nbsp;</td></tr>		
			<tr>
				<td style='font-size:22px;' class=legend>{if_system_load_exceed}:(15 {minutes})</td>
				<td>". Field_array_Hash($LOAD, "MonitReportLoadVG15mn",$MonitReportLoadVG15mn,"style:font-size:22px")."</td>
				<td>". Field_array_Hash($MINUTES, "MonitReportLoadVG15mnCycles",$MonitReportLoadVG15mnCycles,"style:font-size:22px")."</td>
			</tr>
			<tr><td colspan=3>&nbsp;</td></tr>		
		</table>
	</div>
	
	
		<div style='width:98%' class=form>
		<table style='width:100%'>
			<tr style='height:90px'>
				<td style='font-size:30px' colspan=3>CPU & {memory}:</td>
			</tr>
			<tr>
				<td style='font-size:22px;' class=legend>{if_system_cpu_exceed}:</td>
				<td>". Field_array_Hash($CPU, "MonitCPUUsage",$MonitCPUUsage,"style:font-size:22px")."</td>
				<td>". Field_array_Hash($MINUTES, "MonitCPUUsageCycles",$MonitCPUUsageCycles,"style:font-size:22px")."</td>
			</tr>
			<tr><td colspan=3>&nbsp;</td></tr>
			<tr>
				<td style='font-size:22px;' class=legend>{if_system_memory_exceed}:</td>
				<td>". Field_array_Hash($CPU, "MonitMemUsage",$MonitMemUsage,"style:font-size:22px")."</td>
				<td>". Field_array_Hash($MINUTES, "MonitMemUsageCycles",$MonitMemUsageCycles,"style:font-size:22px")."</td>
			</tr>
			<tr><td colspan=3>&nbsp;</td></tr>		
						
			<tr>
				<td style='font-size:22px;' class=legend>{purge_kernel_memory_cache_when_exceed}:</td>
				<td>". Field_array_Hash($CPU, "MonitMemPurgeCache",$MonitMemPurgeCache,"style:font-size:22px")."</td>
				<td>". Field_array_Hash($MINUTES, "MonitMemPurgeCacheCycles",$MonitMemPurgeCacheCycles,"style:font-size:22px")."</td>
			</tr>						
			<tr>
				<td style='font-size:22px;' class=legend>{purge}:</td>
				<td>". Field_array_Hash($FREE, "MonitMemPurgeCacheLevel",$MonitMemPurgeCacheLevel,"style:font-size:22px")."</td>
				<td>". Field_array_Hash($MINUTES, "MonitMemPurgeCacheLevelCycles",$MonitMemPurgeCacheLevelCycles,"style:font-size:22px")."</td>
			</tr>						
		</table>
	</div>	
	<div style='text-align:right;margin-top:20px'><hr>". button("{apply}", "Save$t()",30)."</div>
	
</td>
</tr>
</table>
	
	
<script>
var xSave$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;};
	Loadjs('monit.restart.progress.php');
}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('MonitReportLoadVG1mn',document.getElementById('MonitReportLoadVG1mn').value);
	XHR.appendData('MonitReportLoadVG1mnCycles',document.getElementById('MonitReportLoadVG1mnCycles').value);

	XHR.appendData('MonitReportLoadVG5mn',document.getElementById('MonitReportLoadVG5mn').value);
	XHR.appendData('MonitReportLoadVG5mnCycles',document.getElementById('MonitReportLoadVG5mnCycles').value);

	
	XHR.appendData('MonitReportLoadVG15mn',document.getElementById('MonitReportLoadVG15mn').value);
	XHR.appendData('MonitReportLoadVG15mnCycles',document.getElementById('MonitReportLoadVG15mnCycles').value);
	
	XHR.appendData('MonitCPUUsage',document.getElementById('MonitCPUUsage').value);
	XHR.appendData('MonitCPUUsageCycles',document.getElementById('MonitCPUUsageCycles').value);

	XHR.appendData('MonitMemUsage',document.getElementById('MonitMemUsage').value);
	XHR.appendData('MonitMemUsageCycles',document.getElementById('MonitMemUsageCycles').value);
	XHR.appendData('MonitMemPurgeCacheLevel',document.getElementById('MonitMemPurgeCacheLevel').value);	
	
	XHR.appendData('MonitMemPurgeCache',document.getElementById('MonitMemPurgeCache').value);
	XHR.appendData('MonitMemPurgeCacheCycles',document.getElementById('MonitMemPurgeCacheCycles').value);	
	XHR.appendData('MonitMemPurgeCacheCycles',document.getElementById('MonitMemPurgeCacheCycles').value);
	XHR.appendData('MonitMemPurgeCacheLevelCycles',document.getElementById('MonitMemPurgeCacheLevelCycles').value);
	
	if(document.getElementById('EnableDNSPerfs').checked){
		XHR.appendData('EnableDNSPerfs',1);
	}else{
		XHR.appendData('EnableDNSPerfs',0);
	}
	
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

LoadAjax('monit-status','$page?monit-status=yes');
</script>
";

echo $tpl->_ENGINE_parse_body($html);
}

function status(){
	$ini=new Bs_IniHandler();
	$users=new usersMenus();
	$sock=new sockets();
	$ini->loadString(base64_decode($sock->getFrameWork('cmd.php?monit-ini-status=yes')));
	$tpl=new templates();
	
	$status=DAEMON_STATUS_ROUND("APP_MONIT",$ini,null,1);
	echo $tpl->_ENGINE_parse_body($status);
}

function Save(){
	$sock=new sockets();
	while (list ($key, $value) = each ($_POST) ){
		$sock->SET_INFO($key, $value);
	}
}