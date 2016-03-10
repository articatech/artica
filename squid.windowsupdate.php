<?php
if($argv[1]=="--verbose"){echo __LINE__." verbose OK<br>\n";$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["verbose"])){echo __LINE__." verbose OK<br>\n";$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$GLOBALS["AS_ROOT"]=false;
include_once('ressources/class.templates.inc');
include_once('ressources/class.html.pages.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.artica.graphs.inc');
include_once('ressources/class.highcharts.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.mysql.squid.builder.php');
include_once('ressources/class.tcpip.inc');	
	
	if(isset($_GET["status"])){status();exit;}
	if(isset($_POST["WindowsUpdateCaching"])){Save();exit;}
	if(isset($_GET["wsus-status"])){wsus_status();exit;}
	if(isset($_GET["progresses"])){progresses();exit;}
	if(isset($_GET["FolderSize"])){FolderSize();exit;}
tabs();

function tabs(){
	
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	
	$fontsize=22;
	$array["status"]='{status}';
	$array["whitelist"]='{whitelisted}';
	$array["storage"]='{storage}';
	$array["events"]='{events}';
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
	
	
		if($num=="events"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"squid.windowsupdate.events.php\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
	
		}
		if($num=="whitelist"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"squid.windowsupdate.whitelist.php\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
		
		}
		if($num=="storage"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"squid.windowsupdate.storage.php\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
		
		}		
		
		if($num=="acls-size"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"squid.helpers.quotas.php\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
	
		}
	
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
	}
	
	$html=build_artica_tabs($html,'main_windowsupdate_tabs',1490)."<script>LeftDesign('webfiltering-white-256-opac20.png');</script>";
	echo $html;
}


function status(){
	
	//download.microsoft.com/download
	
	
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$jsFolder=null;
	$WindowsUpdateCaching=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateCaching"));
	$WindowsUpdateCachingOthers=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateCachingOthers"));
	
	
	$WindowsUpdateDenyIfNotExists=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateDenyIfNotExists"));
	$WindowsUpdateBandwidth=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateBandwidth"));
	$WindowsUpdateBandwidthPartial=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateBandwidthPartial"));
	
	if($WindowsUpdateBandwidthPartial==0){$WindowsUpdateBandwidthPartial=512;}
	
	$WindowsUpdateCachingDir=@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateCachingDir");
	if($WindowsUpdateCachingDir==null){$WindowsUpdateCachingDir="/home/squid/WindowsUpdate";}
	$WindowsUpdateDownTimeout=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateDownTimeout"));
	if($WindowsUpdateDownTimeout==0){$WindowsUpdateDownTimeout=600;}
	
	$WindowsUpdateMaxToPartialQueue=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateMaxToPartialQueue"));
	if($WindowsUpdateMaxToPartialQueue==0){$WindowsUpdateMaxToPartialQueue=350;}
	$WindowsUpdateUseLocalProxy=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateUseLocalProxy"));
	$WindowsUpdateBandwidthMaxFailed=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateBandwidthMaxFailed"));
	if($WindowsUpdateBandwidthMaxFailed==0){$WindowsUpdateBandwidthMaxFailed=50;}
	$WindowsUpdateInterface=@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateInterface");
	
	$WindowsUpdateMaxPartition=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateMaxPartition"));
	if($WindowsUpdateMaxPartition==0){$WindowsUpdateMaxPartition=80;}
	
	$WindowsUpdateInProduction=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateInProduction"));
	$WindowsUpdateMaxRetentionTime=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WindowsUpdateMaxRetentionTime"));
	
	
	if(is_file("/usr/share/artica-postfix/ressources/logs/web/WindowsUpdate.state")){
		$jsFolder="Loadjs('$page?FolderSize=yes')";
	
	}
	
	$ip=new networking();
	
	$interfaces=$ip->Local_interfaces();
	unset($interfaces["lo"]);
	
	$zInterfaces[null]="{all}";
	
	while (list ($eth, $none) = each ($interfaces) ){
		if(preg_match("#^gre#", $eth)){continue;}
		$nic=new system_nic($eth);
		$zInterfaces[$eth]="$eth $nic->IPADDR - $nic->NICNAME";

	
	}
	
	$browse=button("{browse}",
"Loadjs('browse-disk.php?field=WindowsUpdateCachingDir&replace-start-root=0');",16,0,30);
	$html="
	<div id='WINDOWSUPDATEPACK'>
	<div style='font-size:30px;margin-bottom:30px'>{windows_updates_cache_enforcement}</div>
	<table style='width:100%'>
	<tr>
		<td style='width:550px;vertical-align:top'>
				<div id='status-wsus'></div>
				<div id='windows-db-size' style='margin-top:15px;width:550px;height:550px'></div>
				<center style='margin-top:10px'>". button("{refresh}","Loadjs('squid.windowsupdate.partition.progress.php')",18)."</center>
		</td>	
		<td	style='width:940px;vertical-align:top'>
			<div style='width:98%' class=form>
			". Paragraphe_switch_img("{enable_windows_updates_cache_enforcement}", 
			"{enable_windows_updates_cache_enforcement_explain}","WindowsUpdateCaching",
			$WindowsUpdateCaching,null,878)."
					
			<table style='width:100%;margin-top:20px'>
			<tr>
				<td style='font-size:22px' class=legend>". texttooltip("{deny_if_not_cached}","{wsus_deny_if_not_cached}").":</td>
				<td>". Field_checkbox_design("WindowsUpdateDenyIfNotExists",1,$WindowsUpdateDenyIfNotExists)."</td>
			</tr>
			<tr>
				<td style='font-size:22px' class=legend>". texttooltip("{free_update_during_the_day}","No production = {from} 22h {to} 06h").":</td>
				<td>". Field_checkbox_design("WindowsUpdateInProduction",1,$WindowsUpdateInProduction)."</td>
			</tr>
			
			<tr>
				<td style='font-size:22px' class=legend>{storage_directory}:</td>
				<td style='vertical-align:middle'>". Field_text("WindowsUpdateCachingDir",$WindowsUpdateCachingDir,"font-size:22px;width:360px")."&nbsp;$browse</td>
			</tr>
			<tr>
				<td style='font-size:22px' class=legend>".texttooltip("{max_partition_size}","{wsus_max_part_size}").":</td>
				<td style='font-size:22px'>". Field_text("WindowsUpdateMaxPartition",$WindowsUpdateMaxPartition,"font-size:22px;width:62px")."&nbsp;%</td>
			</tr>
			<tr>
				<td style='font-size:22px' class=legend>".texttooltip("{MaxDaytoLive}","{WindowsUpdateMaxRetentionTime}").":</td>
				<td style='font-size:22px'>". Field_text("WindowsUpdateMaxRetentionTime",$WindowsUpdateMaxRetentionTime,"font-size:22px;width:62px")."&nbsp;{days}</td>
			</tr>
						
			
			<tr>
				<td style='font-size:22px' class=legend>". texttooltip("{WindowsUpdateCachingOthers}","{WindowsUpdateCachingOthers_explain}").":</td>
				<td align='left' style='font-size:22px'>" . Field_checkbox_design('WindowsUpdateCachingOthers',1,$WindowsUpdateCachingOthers )."</td>
			</tr>							
						
						
			<tr>
				<td style='font-size:22px' class=legend>". texttooltip("{download_timeout}","{download_timeout_explain}").":</td>
				<td align='left' style='font-size:22px'>" . Field_text('WindowsUpdateDownTimeout',$WindowsUpdateDownTimeout,'font-size:22px;padding:3px;width:150px' )."&nbsp;{minutes}</td>
			</tr>	
			<tr>
				<td class=legend style='font-size:22px'>{limit_bandwidth}:</strong></td>
				<td align='left' style='font-size:22px'>" . Field_text('WindowsUpdateBandwidth',$WindowsUpdateBandwidth,'font-size:22px;padding:3px;width:150px' )."&nbsp;kb/s</td>
			</tr>
			<tr style='height:80px'>
				<td colspan=2 style='font-size:26px'>{big_files}</td>
			</tr>						
			<tr>
				<td style='font-size:22px' class=legend>". texttooltip("{big_files}","{download_bigfiles_explain}").":</td>
				<td align='left' style='font-size:22px'>" . Field_text('WindowsUpdateMaxToPartialQueue',$WindowsUpdateMaxToPartialQueue,'font-size:22px;padding:3px;width:150px' )."&nbsp;MB</td>
			</tr>
			<tr>
				<td class=legend style='font-size:22px'>{limit_bandwidth}:</strong></td>
				<td align='left' style='font-size:22px'>" . Field_text('WindowsUpdateBandwidthPartial',$WindowsUpdateBandwidthPartial,'font-size:22px;padding:3px;width:150px' )."&nbsp;kb/s</td>
			</tr>
			<tr>
				<td class=legend style='font-size:22px'>". texttooltip("{max_failed}","{windows_update_max_failed}").":</strong></td>
				<td align='left' style='font-size:22px'>" . Field_text('WindowsUpdateBandwidthMaxFailed',$WindowsUpdateBandwidthMaxFailed,'font-size:22px;padding:3px;width:90px' )."&nbsp;{times}</td>
			</tr>						
			<tr>
				<td class=legend style='font-size:22px'>{use_local_proxy}:</strong></td>
				<td align='left' style='font-size:22px'>" . Field_checkbox_design('WindowsUpdateUseLocalProxy',1,$WindowsUpdateUseLocalProxy)."</td>
			</tr>
			<tr>
				<td class=legend style='font-size:22px'>{forward_interface}:</td>
				<td style='font-size:22px'>". Field_array_Hash($zInterfaces, "WindowsUpdateInterface",$WindowsUpdateInterface,"style:font-size:22px")."</td>
			</tr>												
		
			
				
			<tr>
				<td colspan=2 align='right'><hr>". button("{apply}", "Save$t()",40)."</td>
			</tr>
			</table>
			</div>
		</td>
	</tr>
</table>
</div>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	Loadjs('squid.windowsupdate.progress.php');
	
}

function Save$t(){
	var XHR = new XHRConnection();
	if(document.getElementById('WindowsUpdateDenyIfNotExists').checked){
	XHR.appendData('WindowsUpdateDenyIfNotExists','1');}
	else{XHR.appendData('WindowsUpdateDenyIfNotExists','0');}
	
	if(document.getElementById('WindowsUpdateUseLocalProxy').checked){
	XHR.appendData('WindowsUpdateUseLocalProxy','1');}
	else{XHR.appendData('WindowsUpdateUseLocalProxy','0');}
	
	if(document.getElementById('WindowsUpdateCachingOthers').checked){
	XHR.appendData('WindowsUpdateCachingOthers','1');}
	else{XHR.appendData('WindowsUpdateCachingOthers','0');}

	XHR.appendData('WindowsUpdateInProduction',document.getElementById('WindowsUpdateInProduction').value);
	XHR.appendData('WindowsUpdateBandwidth',document.getElementById('WindowsUpdateBandwidth').value);
	XHR.appendData('WindowsUpdateCachingDir',document.getElementById('WindowsUpdateCachingDir').value);
	XHR.appendData('WindowsUpdateCaching',document.getElementById('WindowsUpdateCaching').value);
	XHR.appendData('WindowsUpdateDownTimeout',document.getElementById('WindowsUpdateDownTimeout').value);
	XHR.appendData('WindowsUpdateMaxToPartialQueue',document.getElementById('WindowsUpdateMaxToPartialQueue').value);
	XHR.appendData('WindowsUpdateBandwidthPartial',document.getElementById('WindowsUpdateBandwidthPartial').value);
	XHR.appendData('WindowsUpdateInterface',document.getElementById('WindowsUpdateInterface').value);
	XHR.appendData('WindowsUpdateBandwidthMaxFailed',document.getElementById('WindowsUpdateBandwidthMaxFailed').value);
	XHR.appendData('WindowsUpdateMaxPartition',document.getElementById('WindowsUpdateMaxPartition').value);
	XHR.appendData('WindowsUpdateMaxRetentionTime',document.getElementById('WindowsUpdateMaxRetentionTime').value);
	
	
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
$jsFolder;
LoadAjax('status-wsus','$page?wsus-status=yes');

</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function Save(){
	
	$sock=new sockets();
	while (list ($key, $value) = each ($_POST)){
		$sock->SET_INFO($key, $value);
		
	}
	
}
function wsus_status(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	
	$html="
	<div id='progress1t-$t' style='font-size:14px;margin-bottom:10px'></div>			
	<div id='progress1-$t' style='height:50px'></div>
	<hr>
	<div id='progress2t-$t' style='font-size:14px;margin-bottom:10px'></div>			
	<div id='progress2-$t' style='height:50px'></div>	
	
	
	<script>
	Loadjs('$page?progresses=$t');
	</script>
	";
	echo $html;
}

function progresses(){
	$t=$_GET["progresses"];
	$cachefile="/usr/share/artica-postfix/ressources/logs/windowsupdateG.progress";
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$array=unserialize(@file_get_contents($cachefile));
	$prc=intval($array["POURC"]);
	$title=$tpl->javascript_parse_text($array["TEXT"]);
	$time=time();
	echo "if(document.getElementById('progress1t-$t')){\n";
	echo "document.getElementById('progress1t-$t').innerHTML='$title';\n}\n";
	echo "if(document.getElementById('progress1-$t')){\n";
	echo "$('#progress1-$t').progressbar({ value: $prc });\n}\n";
	
	
	

	
	
	$cachefile="/usr/share/artica-postfix/ressources/logs/windowsupdate.progress";
	$array=unserialize(@file_get_contents($cachefile));
	$title=$tpl->javascript_parse_text($array["TEXT"]);
	$prc=intval($array["POURC"]);
	if(is_numeric($array["TEXT"])){
		if($array["TEXT"]<>null){
			$prc=intval($array["TEXT"]);
			$title=$tpl->javascript_parse_text($array["POURC"]);
		}
	}
	
	echo "if(document.getElementById('progress2t-$t')){\n";
	echo "document.getElementById('progress2t-$t').innerHTML='$title';\n}\n";
	echo "if(document.getElementById('progress2-$t')){\n";
	echo "$('#progress2-$t').progressbar({ value: $prc });\n}\n";
	
	
	echo "function Start$time(){
		if(!document.getElementById('progress1t-$t')){return;}
		Loadjs('$page?progresses=$t');
	}
	";
	echo "setTimeout(\"Start$time()\",2000);\n
	";
	
	
	
}

function FolderSize(){

	$cacheFile="/usr/share/artica-postfix/ressources/logs/web/WindowsUpdate.state";
	$tpl=new templates();
	$directory=$tpl->javascript_parse_text("{directory}");
	$free=$tpl->javascript_parse_text("{free}");
	$ARRAY=unserialize(@file_get_contents($cacheFile));
	$ARRAY["PART"]=$ARRAY["PART"]/1024;
	$ARRAY["AIV"]=$ARRAY["AIV"]/1024;
	
	$ASoustraire=intval($ARRAY["SIZEKB"])+intval($ARRAY["AIV"]);

	$PART=intval($ARRAY["PART"])-$ASoustraire;

	$MAIN["Partition " .FormatBytes($ARRAY["PART"])]=$PART;
	$MAIN["$directory ".FormatBytes($ARRAY["SIZEKB"])]=$ARRAY["SIZEKB"];
	$MAIN["$free ".FormatBytes($ARRAY["AIV"])]=$ARRAY["AIV"];

	$PieData=$MAIN;
	$highcharts=new highcharts();
	$highcharts->container="windows-db-size";
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{directory_size}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{directory_size} ".FormatBytes($ARRAY["SIZEKB"]) ." (MB)");
	echo $highcharts->BuildChart();
}
