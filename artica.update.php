<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.system.network.inc');
	$usersmenus=new usersMenus();
	if($usersmenus->AsArticaAdministrator==false){header('location:users.index.php');exit;}	
	if(isset($_GET["main_artica_update"])){main_artica_update_switch();exit;}
	if(isset($_GET["CheckEveryMinutes"])){SaveConf();exit;}
	if(isset($_GET["ArticaUpdateInstallPackage"])){ArticaUpdateInstallPackage();exit;}
	if(isset($_GET["auto_update_perform"])){auto_update_perform();exit;}
	if(isset($_GET["ajax-events"])){main_artica_update_events_display();exit;}
	if(isset($_GET["webfiltering-tabs"])){webfiltering_tabs();exit;}
	
	if(isset($_GET["patchs-list"])){patchs_list();exit;}
	if(isset($_POST["UpdatePatchNow"])){patchs_update();exit;}
	if(isset($_POST["EnableDisableAllUpgradePackage"])){apt_EnableDisableAllUpgradePackage();exit;}
	if(isset($_POST["RebootAfterArticaUpgrade"])){RebootAfterArticaUpgradeSave();exit;}
	if(isset($_POST["pkg"])){apt_pkg();exit;}
	if(isset($_POST["pkg-upgrade"])){apt_upgrade();exit;}
	
	if(isset($_GET["sys-update-button"])){apt_sys_update_button();exit;}
	if(isset($_GET["status-versions"])){status_versions();exit;}
	if(isset($_GET["js"])){popup_js();exit;}
	if(isset($_GET["ajax-pop"])){popup();exit;}
	
	
	main_artica_update_page();
	
	
function popup_js(){
	$artica=new artica_general();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{artica_autoupdate}');
	$events=$tpl->_ENGINE_parse_body('{events}');
	
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$datas=file_get_contents('js/artica_settings.js');
	$PHP_VERSION=PHP_VERSION;
	$html="
	$datas
	YahooWin(972,'artica.update.php?ajax-pop=yes','$title V.$artica->ArticaVersion PHP: $PHP_VERSION');
	
	function ShowArticaUpdateEvents(file){
		YahooWin2('700','artica.update.php?ajax-events='+file,'$events V.$artica->ArticaVersion ');
		}
	

	
	";
	
	echo $html;
	
	
	
	
}

function status_versions(){
	$tpl=new templates();
	$sock=new sockets();
	$EnableArticaMetaClient=intval($sock->GET_INFO("EnableArticaMetaClient"));
	if($EnableArticaMetaClient==1){return;}
	$f="/usr/share/artica-postfix/ressources/index.ini";
	
	$ini=new Bs_IniHandler();
	$ini->loadFile($f);
	$Lastest_patch=trim(strtolower($ini->_params["NEXT"]["artica-patch"]));
	$Lastest=trim(strtolower($ini->_params["NEXT"]["artica"]));
	$nightly=trim(strtolower($ini->_params["NEXT"]["artica-nightly"]));
	if($Lastest==null){$Lastest="-";}
	if($Lastest_patch==null){$Lastest_patch="-";}
	if($Lastest==null){$Lastest="-";}
	$html="
	
	<table style='width:100%'>
		<tr>
			<td colspan=3 style='font-size:22px'>{available_versions}:</td>
		</tr>
			<tr>
				<td width=1% nowrap><img src=img/arrow-blue-left-32.png></td>
				<td class=legend style='font-size:18px' nowrap>{release}:</td>
				<td style='font-size:18px'><a href=\"http://www.artica.fr/releases.php\" target=_new>$Lastest</td>
			</tr>
			<tr>
				<td width=1% nowrap><img src=img/arrow-blue-left-32.png ></td>
				<td class=legend style='font-size:18px' nowrap>Nightly:</td>
				<td style='font-size:18px' width=99%><a href=\"http://www.artica.fr/nightly.php\" target=_new>$nightly</td>
			</tr>
			<tr>
				<td width=1% nowrap><img src=img/arrow-blue-left-32.png ></td>
				<td class=legend style='font-size:18px' nowrap>Patch:</td>
				<td style='font-size:18px' width=99%><a href=\"http://www.artica.fr/patch-p0.php\" target=_new>$Lastest_patch</td>
			</tr>			
			<tr><td colspan=3 style='font-size:22px'>&nbsp;</td></tr>
			
			
		</table>
";

	echo $tpl->_ENGINE_parse_body($html);
			
	
	
}


function popup(){
	$tpl=new templates();
	$html="
	<div id='main_artica_update'>
	";
	echo $tpl->_ENGINE_parse_body($html);
	echo main_artica_update_switch();
	echo "</div>";
	
	
	
	
	
}

	
function main_artica_update_page(){
$artica=new artica_general();
$page=CurrentPageName();

$html="
<div style='text-align:right'><H2>{current_version}:$artica->ArticaVersion</H2></div>
<div id='main_artica_update'></div>
		<script>LoadAjax('main_artica_update','$page?main_artica_update=config');</script>
	
	";
	$cfg["JS"][]="js/artica_settings.js";
	$tpl=new template_users('{artica_autoupdate}',$html,0,0,0,0,$cfg);
	echo $tpl->web_page;
		
	
	
}


function main_artica_update_config(){

	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
$sock=new sockets();	
$ini=new Bs_IniHandler();
$configDisk=trim($sock->GET_INFO('ArticaAutoUpdateConfig'));	
$cannot_schedule_update_without_schedule=$tpl->javascript_parse_text("{cannot_schedule_update_without_schedule}");
$ini->loadString($configDisk);	
$AUTOUPDATE=$ini->_params["AUTOUPDATE"];	
$EnableNightlyInFrontEnd=$sock->GET_INFO("EnableNightlyInFrontEnd");
$EnableRebootAfterUpgrade=$sock->GET_INFO("EnableRebootAfterUpgrade");
$EnableScheduleUpdates=$sock->GET_INFO("EnableScheduleUpdates");
$EnablePatchUpdates=$sock->GET_INFO("EnablePatchUpdates");
$ArticaScheduleUpdates=$sock->GET_INFO("ArticaScheduleUpdates");
$DisableInstantLDAPBackup=$sock->GET_INFO("DisableInstantLDAPBackup");
$EnableSystemUpdates=$sock->GET_INFO("EnableSystemUpdates");
$EnableArticaMetaClient=intval($sock->GET_INFO("EnableArticaMetaClient"));
$ArticaUpdateIntervalAllways=intval($sock->GET_INFO("ArticaUpdateIntervalAllways"));

if(!is_numeric($DisableInstantLDAPBackup)){$DisableInstantLDAPBackup=0;}
if(!is_numeric($EnableNightlyInFrontEnd)){$EnableNightlyInFrontEnd=1;}
if(!is_numeric($EnableScheduleUpdates)){$EnableScheduleUpdates=0;}
if(!is_numeric($EnableRebootAfterUpgrade)){$EnableRebootAfterUpgrade=0;}
if(!is_numeric($EnablePatchUpdates)){$EnablePatchUpdates=0;}
if(!is_numeric($EnableSystemUpdates)){$EnableSystemUpdates=0;}
//CURLOPT_MAX_RECV_SPEED_LARGE



writelogs("EnableScheduleUpdates = $EnableScheduleUpdates",__FUNCTION__,__FILE__,__LINE__);

if(trim($AUTOUPDATE["uri"])==null){$AUTOUPDATE["uri"]="http://articatech.net/auto.update.php";}
if(trim($AUTOUPDATE["enabled"])==null){$AUTOUPDATE["enabled"]="yes";}
if(trim($AUTOUPDATE["autoinstall"])==null){$AUTOUPDATE["autoinstall"]="yes";}
if(trim($AUTOUPDATE["CheckEveryMinutes"])==null){$AUTOUPDATE["CheckEveryMinutes"]="60";}
if(trim($AUTOUPDATE["front_page_notify"])==null){$AUTOUPDATE["front_page_notify"]="yes";}
if(trim($AUTOUPDATE["samba_notify"])==null){$AUTOUPDATE["samba_notify"]="no";}
if(trim($AUTOUPDATE["auto_apt"])==null){$AUTOUPDATE["auto_apt"]="no";}


$action_http_proxy="	<td valign='top' width=33%>
	". 	Paragraphe("proxy-64.png","{http_proxy}","{http_proxy_text}","javascript:Loadjs('artica.settings.php?js=yes&func-ProxyInterface=yes');",300,null,$nowrap=1)."
	</td>";

$action_update_now="	<td valign='top' width=33%>
	". Paragraphe('64-recycle.png','{update_now}','{perform_update_text}',
			"javascript:Loadjs('artica.update.progress.php',true)","{perform_update_text}",300,null,$nowrap=1)."
	</td>";

$action_refresh_index="<td colspan=2 align='right'>". button("{refresh_index_file}", "Loadjs('setup.index.php?TestConnection-js=yes')")."</td>";

if($EnableArticaMetaClient==1){
	$action_http_proxy=null;
	$action_update_now=null;
	$action_refresh_index=null;
}

$CURVER=@file_get_contents("VERSION");

if(preg_match("#^2\.#", $CURVER)){
	$UpgradeTov10=intval($sock->GET_INFO("UpgradeTov10"));
	if($UpgradeTov10==0){
		
		$html=FATAL_INFO_SHOW_128("{need_to_upgrade_to_v10}").
		"<center style='margin:50px'>".button("{perform_upgrade}","Loadjs('squid.upgradev10.progress.php')",42)."</center>";
		echo $tpl->_ENGINE_parse_body($html);
		return;
	}
	
}



$CUR_BRANCH=@file_get_contents("/usr/share/artica-postfix/MAIN_RELEASE");
	$html="
	<input type='hidden' id='perform_update_text' value='{perform_update_text}'>
	<table style='width:100%'>
	<tr>
	<td valign='top'>
	<td valign='top' width=33%>
	". 	Paragraphe("64-download.png","{manual_update}","{artica_manual_update_text}","javascript:Loadjs('artica.update-manu.php');",300,null,$nowrap=1)."
	</td>	
	</td>
	$action_http_proxy
	$action_update_now
	</tr>					
	</table>
	";
	
	
	
	if($AUTOUPDATE["nightlybuild"]=="yes"){$AUTOUPDATE["nightlybuild"]=1;}else{$AUTOUPDATE["nightlybuild"]=0;}
	if($AUTOUPDATE["front_page_notify"]=="yes"){$AUTOUPDATE["front_page_notify"]=1;}else{$AUTOUPDATE["front_page_notify"]=0;}
	
	if($AUTOUPDATE["autoinstall"]=="yes"){$AUTOUPDATE["autoinstall"]=1;}else{$AUTOUPDATE["autoinstall"]=0;}
	if($AUTOUPDATE["enabled"]=="yes"){$AUTOUPDATE["enabled"]=1;}else{$AUTOUPDATE["enabled"]=0;}
	if($AUTOUPDATE["samba_notify"]=="yes"){$AUTOUPDATE["samba_notify"]=1;}else{$AUTOUPDATE["samba_notify"]=0;}
	
	
	
	
	$form=
	Field_hidden("EnablePatchUpdates", $EnablePatchUpdates).
	Field_hidden("EnableSystemUpdates", $EnableSystemUpdates).
	"
			
	<div id='ArticaUpdateForm' class='form' style='width:95%'>
	
	<table style='width:100%'>
	<tr>
		<td style='width:30%' valign=middle><div id='status-versions'></div></td>
		<td style='width:70%'>
	<div class=explain style='font-size:16px'>
		<div style='margin-bottom:5px;text-align:right;padding-bottom:1px;border-bottom:1px solid #999999;width:97%'>
			<strong style='font-size:22px'>{current} Artica v.$CURVER Branch v.$CUR_BRANCH</strong>
		</div>{autoupdate_text}
	</div>
	</td>
	</tr>
	</tr>
		$action_refresh_index
	</tr>
	</table>
	<script>LoadAjax('status-versions','$page?status-versions=yes');</script>
	
	<form name='ffm1' >
	<table style='width:99%' >
	<tr>
		<td width=1% nowrap align='right' class=legend class=legend style='font-size:16px'>{enable_autoupdate}:</strong></td>
		<td align='left'>" . Field_checkbox_design('enabled',1,$AUTOUPDATE["enabled"])."</td>
	</tr>
	<tr>
		<td width=1% nowrap align='right' class=legend style='font-size:16px'>{enable_autoinstall}:</strong></td>
		<td align='left'>" . Field_checkbox_design('autoinstall',1,$AUTOUPDATE["autoinstall"])."</td>
	</tr>
	<tr>
		<td width=1% nowrap align='right' class=legend style='font-size:16px'>{enable_nightlybuild}:</strong></td>
		<td align='left'>" . Field_checkbox_design('nightlybuild',1,$AUTOUPDATE["nightlybuild"])."</td>
	</tr>
	<tr>
		<td width=1% nowrap align='right' class=legend style='font-size:16px'>{free_update_during_the_day}:</strong></td>
		<td align='left'>" . Field_checkbox_design('ArticaUpdateIntervalAllways',1,$ArticaUpdateIntervalAllways)."</td>
	</tr>				
	<tr>
		<td width=1% nowrap align='right' class=legend style='font-size:16px'>{EnableNightlyInFrontEnd}:</strong></td>
		<td align='left'>" . Field_checkbox_design('EnableNightlyInFrontEnd',1,$EnableNightlyInFrontEnd)."</td>
	</tr>

	<tr>
		<td width=1% nowrap align='right' class=legend style='font-size:16px'>{front_page_notify}:</strong></td>
		<td align='left'>" . Field_checkbox_design('front_page_notify',1,$AUTOUPDATE["front_page_notify"])."</td>
	</tr>";
	if($users->SAMBA_INSTALLED){
	$form=$form."<td width=1% nowrap align='right' class=legend style='font-size:16px'>{samba_notify}:</strong></td>
	<td align='left'>" . Field_checkbox_design('samba_notify',1,$AUTOUPDATE["samba_notify"])."</td>
	</tr>";
	}	
	
	
	$form=$form."
	<tr><td colspan=2>&nbsp;</td></tr>
	<tr>
		<td width=1% nowrap align='right' class=legend style='font-size:16px'>{DisableInstantLDAPBackup}:</strong></td>
		<td align='left'>" . Field_checkbox_design('DisableInstantLDAPBackup',1,$DisableInstantLDAPBackup)."</td>
	</tr>	
	
	";
	


	
	$ip=new networking();
	
	while (list ($eth, $cip) = each ($ip->array_TCP) ){
		if($cip==null){continue;}
		$arrcp[$cip]=$cip;
	}
	
	$arrcp[null]="{default}";
	
	$WgetBindIpAddress=$sock->GET_INFO("WgetBindIpAddress");
	$CurlBandwith=$sock->GET_INFO("CurlBandwith");
	
	$CurlTimeOut=$sock->GET_INFO("CurlTimeOut");
	if(!is_numeric($CurlBandwith)){$CurlBandwith=0;}
	if(!is_numeric($CurlTimeOut)){$CurlTimeOut=3600;}
	if($CurlTimeOut<720){$CurlTimeOut=3600;}
	
	$NoCheckSquid=$sock->GET_INFO("NoCheckSquid");
	if(!is_numeric($NoCheckSquid)){$NoCheckSquid=0;}
	
	$WgetBindIpAddress=Field_array_Hash($arrcp,"WgetBindIpAddress",$WgetBindIpAddress,null,null,0,"font-size:16px;padding:3px;");
	
	$RebootAfterArticaUpgrade=$sock->GET_INFO("RebootAfterArticaUpgrade");
	if(!is_numeric($RebootAfterArticaUpgrade)){$RebootAfterArticaUpgrade=0;}	
	
	$form=$form."
	<tr>
	<td width=1% nowrap align='right' class=legend style='font-size:16px'>{WgetBindIpAddress}:</strong></td>
	<td align='left'>$WgetBindIpAddress</td>
	</tr>			
	<tr>
	<td width=1% nowrap align='right' class=legend style='font-size:16px'>{CheckEveryMinutes}:</strong></td>
	<td align='left'>" . Field_text('CheckEveryMinutes',$AUTOUPDATE["CheckEveryMinutes"],'font-size:16px;padding:3px;width:90px' )."</td>
	</tr>	
	<tr>
		<td width=1% nowrap align='right' class=legend style='font-size:16px'>{NoCheckSquid}:</strong></td>
		<td align='left'>" . Field_checkbox_design('NoCheckSquid',1,$NoCheckSquid)."&nbsp;</td>
	</tr>
	<tr>
		<td width=1% nowrap align='right' class=legend style='font-size:16px'>{HTTP_TIMEOUT}:</strong></td>
		<td align='left' style='font-size:16px'>" . Field_text('CurlTimeOut',$CurlTimeOut,'font-size:16px;padding:3px;width:90px' )."&nbsp;{seconds}</td>
	</tr>	
	<tr>
		<td width=1% nowrap align='right' class=legend style='font-size:16px'>{limit_bandwidth}:</strong></td>
		<td align='left' style='font-size:16px'>" . Field_text('CurlBandwith',$CurlBandwith,'font-size:16px;padding:3px;width:90px' )."&nbsp;kb/s</td>
	</tr>										
	<tr>
		<td width=1% nowrap align='right' class=legend style='font-size:16px'>{EnableScheduleUpdates}:</strong></td>
		<td align='left'>" . Field_checkbox_design('EnableScheduleUpdates',1,$EnableScheduleUpdates,"CheckSchedules()" )."&nbsp;
		<a href=\"javascript:blur()\" OnClick=\"javascript:Loadjs('cron.php?field=ArticaScheduleUpdates&function2=SaveArticaUpdateForm')\" style='font-size:16px;text-decoration:underline;color:black' id='scheduleAID'>{schedule}</a>
	</td>
	<tr>
		<td width=1% nowrap align='right' class=legend style='font-size:16px'>{RebootAfterArticaUpgrade}:</strong></td>
		<td align='left'>" . Field_checkbox_design('RebootAfterArticaUpgrade',1,$RebootAfterArticaUpgrade,"RebootAfterArticaUpgradeCheck()" )."&nbsp;
	</tr>	
	
	
	
	</tr>	

	<tr>
	<td width=1% align='right' class=legend  style='font-size:16px;vertical-align:top' nowrap>{uri}:</strong></td>
	<td align='left'>
			" . Field_text('uri',$AUTOUPDATE["uri"],'font-size:16px;padding:3px;width:390px' )."
			
	</td>
	</tr>	
	<tr>
	<td colspan=2 align='right'>
	<hr>
	". button("{apply}","SaveArticaUpdateForm()",28)."
	</tr>			
	</table>
	</form>
	</div>
	<input type='hidden' id='ArticaScheduleUpdates' value='$ArticaScheduleUpdates'>
	<script>
		function CheckSchedules(){
			document.getElementById('CheckEveryMinutes').disabled=true;
			if(!document.getElementById('EnableScheduleUpdates').checked){
				document.getElementById('CheckEveryMinutes').disabled=false;
				document.getElementById('scheduleAID').style.color='#CCCCCC';
			}else{
				document.getElementById('scheduleAID').style.color='black';
			}
		
		}
	
	

	
	
	CheckSchedules();
	
	
var x_SaveArticaUpdateForm= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			RefreshTab('main_config_artica_update');
			}

			
	function RebootAfterArticaUpgradeCheck(){
		var XHR = new XHRConnection();
		if(document.getElementById('RebootAfterArticaUpgrade').checked){XHR.appendData('RebootAfterArticaUpgrade','1');}else{XHR.appendData('RebootAfterArticaUpgrade','0');}
		XHR.sendAndLoad('$page', 'POST');
	}
	
	
	function SaveArticaUpdateForm(){
		var XHR = new XHRConnection();
		
		if(document.getElementById('enabled')){
			if(document.getElementById('enabled').checked){XHR.appendData('enabled','yes');}else{XHR.appendData('enabled','no');}
		}
		
		if(document.getElementById('autoinstall')){
			if(document.getElementById('autoinstall').checked){XHR.appendData('autoinstall','yes');}else{XHR.appendData('autoinstall','no');}
		}
		
		if(document.getElementById('nightlybuild')){
			if(document.getElementById('nightlybuild').checked){
				XHR.appendData('nightlybuild','yes');}else{XHR.appendData('nightlybuild','no');}
		}		
		
		if(document.getElementById('front_page_notify')){
			if(document.getElementById('front_page_notify').checked){XHR.appendData('front_page_notify','yes');}else{XHR.appendData('front_page_notify','no');}
		}
		
		if(document.getElementById('EnableNightlyInFrontEnd')){
			if(document.getElementById('EnableNightlyInFrontEnd').checked){XHR.appendData('EnableNightlyInFrontEnd','1');}else{XHR.appendData('EnableNightlyInFrontEnd','0');}
		}
		
		if(document.getElementById('ArticaUpdateIntervalAllways')){
			if(document.getElementById('ArticaUpdateIntervalAllways').checked){XHR.appendData('ArticaUpdateIntervalAllways','1');}else{XHR.appendData('ArticaUpdateIntervalAllways','0');}
		}		
		
		
		if(document.getElementById('EnablePatchUpdates')){
			if(document.getElementById('EnablePatchUpdates').checked){XHR.appendData('EnablePatchUpdates','1');}else{XHR.appendData('EnablePatchUpdates','0');}
		}
		if(document.getElementById('EnableSystemUpdates')){
			if(document.getElementById('EnableSystemUpdates').checked){
			XHR.appendData('EnableSystemUpdates','1');
				}else{
			XHR.appendData('EnableSystemUpdates','0');
			}
		}		
		
		
		if(document.getElementById('EnableScheduleUpdates')){
			if(document.getElementById('EnableScheduleUpdates').checked){
				var ArticaScheduleUpdates=document.getElementById('ArticaScheduleUpdates').value;
				if(ArticaScheduleUpdates.length==0){
					alert('$cannot_schedule_update_without_schedule');
				}
				XHR.appendData('EnableScheduleUpdates','1');}
			else{XHR.appendData('EnableScheduleUpdates','0');}
		}
		
		if(document.getElementById('samba_notify')){if(document.getElementById('samba_notify').checked){XHR.appendData('samba_notify','yes');}else{XHR.appendData('samba_notify','no');}}
		
		
	
		if(document.getElementById('DisableInstantLDAPBackup')){
			if(document.getElementById('DisableInstantLDAPBackup').checked){XHR.appendData('DisableInstantLDAPBackup','1');}else{XHR.appendData('DisableInstantLDAPBackup','0');}
		}
		
		if(document.getElementById('ArticaScheduleUpdates')){
			XHR.appendData('ArticaScheduleUpdates',document.getElementById('ArticaScheduleUpdates').value);
		}			
		if(document.getElementById('WgetBindIpAddress')){
			XHR.appendData('WgetBindIpAddress',document.getElementById('WgetBindIpAddress').value);
		}
		if(document.getElementById('CheckEveryMinutes')){
    		XHR.appendData('CheckEveryMinutes',document.getElementById('CheckEveryMinutes').value);
    	}
    	if(document.getElementById('uri')){
    		XHR.appendData('uri',document.getElementById('uri').value);
    	}
		if(document.getElementById('CurlBandwith')){
    		XHR.appendData('CurlBandwith',document.getElementById('CurlBandwith').value);
    	}
		if(document.getElementById('CurlTimeOut')){
    		XHR.appendData('CurlTimeOut',document.getElementById('CurlTimeOut').value);
    	}  

    	if(document.getElementById('NoCheckSquid')){
    		if(document.getElementById('NoCheckSquid').checked){XHR.appendData('NoCheckSquid','1');}else{XHR.appendData('NoCheckSquid','0');}
    	}
    	
    	
    	AnimateDiv('ArticaUpdateForm');
    	XHR.sendAndLoad('$page', 'GET',x_SaveArticaUpdateForm);
		}	
	
	</script>
	";
	

	
	$tpl=new templates();
	return $tpl->_ENGINE_parse_body($html.$form);
	
	
}

function main_artica_apt_config(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$packagesNumber=$q->COUNT_ROWS("syspackages_updt", "artica_backup");
	$updatenowtext=$tpl->javascript_parse_text("{update_now}");
	
	
	if(!$q->ok){echo "<H2>$q->mysql_error</H2>";return;}
	
	if($packagesNumber==0){
		echo $tpl->_ENGINE_parse_body("<table style='width:100%'><tr><td width=1%><img src='img/ok64.png'></td><td><H2>{system_is_uptodate}</H2></td></tr></table>");
		return;
	}
	
	$html="<div style='font-size:16px'>$packagesNumber {system_packages_can_be_upgraded}</div>";
	
	
		$q=new mysql();
		$sql="SELECT COUNT(*) as tcount FROM syspackages_updt WHERE upgrade=1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
		$coche=$ligne["tcount"];
		if($coche==$packagesNumber){$coche=1;}else{$coche=0;}
		$refresh=imgtootltip('refresh-24.png',"{refresh}","RefreshTab('main_config_artica_update');");
		
		$sql="SELECT AVG(progress) AS tcount FROM syspackages_updt  WHERE upgrade=1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
		$global_progress=$ligne["tcount"];
		
	
$html="<center>
<div id='sys-update-button' style='text-align:center;margin:5px'></div>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th width=1%>". Field_checkbox("EnableDisableAllUpgradePackage", 1,$coche,"EnableDisableAllUpgradePackage()"). "</th>
		<th colspan=2>{packages}</th>
		<th style='background-color:white;margin:0;padding:0;border:1px solid black'>". pourcentage(round($global_progress))."</th>
		<th width=1% align='center'>$refresh</th>
	</tr>
</thead>
<tbody class='tbody'>";

		$q=new mysql();
		$sql="SELECT * FROM syspackages_updt ORDER BY package";
		
		$results=$q->QUERY_SQL($sql,"artica_backup");
		
		if(!$q->ok){echo "<H2>$q->mysql_error</H2>";}
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$progress="&nbsp;";
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$md=md5($ligne["package"]);
		$select=Field_checkbox("$md", 1,$ligne["upgrade"], "EnableDisableUpgradePackage('$md','{$ligne["package"]}')");
		if($ligne["upgrade"]==1){$progress=pourcentage($ligne["progress"]);}
		$color="black";
		$html=$html."
		<tr class=$classtr>
			<td width=1%>$select</td>
			<td style='font-size:16px;font-weight:bold;color:$color' width=99%>{$ligne["package"]}</td>
			<td width=1% colspan=3>$progress</td>
		</tr>
		";
	}	
	
	$html=$html."</tbody></table>
	
	<script>
	
	var x_EnableDisableAllUpgradePackage= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			RefreshTab('main_config_artica_update');
			}
	var x_EnableDisableUpgradePackage= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			sysbutton();
			}						
	
	
	function EnableDisableAllUpgradePackage(){
			var XHR = new XHRConnection();
			if(document.getElementById('EnableDisableAllUpgradePackage').checked){XHR.appendData('EnableDisableAllUpgradePackage',1);}else{XHR.appendData('EnableDisableAllUpgradePackage',0);}
			XHR.sendAndLoad('$page', 'POST',x_EnableDisableAllUpgradePackage);
    	}
    	
    function sysbutton(){
    	LoadAjaxTiny('sys-update-button','$page?sys-update-button=yes');
    
    }
    
    function EnableDisableUpgradePackage(id,pkg){
    	var XHR = new XHRConnection();
    	if(document.getElementById(id).checked){XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
    	XHR.appendData('pkg',pkg);
    	XHR.sendAndLoad('$page', 'POST',x_EnableDisableUpgradePackage);
    }
    
    function SysUpdateNow(){
    	if(confirm('$updatenowtext ?')){
     		var XHR = new XHRConnection();
    		XHR.appendData('pkg-upgrade','yes');
    		XHR.sendAndLoad('$page', 'POST',x_EnableDisableAllUpgradePackage);   	
    	}
    }
				
	sysbutton();
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function apt_EnableDisableAllUpgradePackage(){
	
	$q=new mysql();
	$q->QUERY_SQL("UPDATE syspackages_updt SET upgrade='{$_POST["EnableDisableAllUpgradePackage"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
}

function RebootAfterArticaUpgradeSave(){
	$sock=new sockets();
	$sock->SET_INFO("RebootAfterArticaUpgrade", $_POST["RebootAfterArticaUpgrade"]);
}

function apt_pkg(){
	$q=new mysql();
	$q->QUERY_SQL("UPDATE syspackages_updt SET upgrade='{$_POST["enabled"]}' WHERE `package`='{$_POST["pkg"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
}

function apt_upgrade(){
	$q=new mysql();
	$q->QUERY_SQL("UPDATE syspackages_updt SET progress='20' WHERE `upgrade`=1","artica_backup");
	$sock=new sockets();
	$sock->getFrameWork("services.php?pkg-upgrade=yes");
	
}

function apt_sys_update_button(){
		$q=new mysql();
		$sql="SELECT COUNT(*) as tcount FROM syspackages_updt WHERE upgrade=1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
		$count=$ligne["tcount"];
		if($count==0){return;}
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body(button("{update_now}","SysUpdateNow()",16));
	}


function main_artica_update_switch(){
	
	switch ($_GET["main_artica_update"]) {
		case "apt":echo main_artica_apt_config();exit;break;
		case "config":echo main_artica_update_config();exit;break;
		case "events":echo main_artica_update_events();exit;break;
		case "list":echo main_artica_update_updatelist();exit;break;
		case "patchs":echo patchs_start();exit;break;
		
		
		
		default:echo main_artica_update_tabs();exit;break;
	}
	
}

function webfiltering_tabs(){
	$array["databases"]='{databases}';
	$array["status"]='{parameters}';
	$array["schedule"]='{update_schedule}';
	$array["update"]='{update_events}';
	$array["categories"]='{categories}';
	$tpl=new templates();
	$fontsize=22;
	
	
	while (list ($num, $ligne) = each ($array) ){
		if($num=="status"){
			$html[]= "<li>
			<a href=\"dansguardian2.databases.php?statusDB=yes&from-ufdbguard={$_GET["from-ufdbguard"]}\">
			<span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}
		
		if($num=="databases"){
			$html[]= $tpl->_ENGINE_parse_body("<li>
					<a href=\"dansguardian2.databases.progress.php?from-ufdbguard={$_GET["from-ufdbguard"]}\">
					<span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="schedule"){
			$html[]= "<li>
			<a href=\"squid.databases.schedules.php?TaskType=1\">
			<span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}
		
		if($num=="articaufdb"){
			$html[]= "<li>
			<a href=\"dansguardian2.databases.compiled.php?articaufdb=yes\">
			<span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}
		if($num=="update"){
			$html[]= "<li>
			<a href=\"squid.articadb-events.php\">
			<span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}		
	}
	
	echo build_artica_tabs($html, "main_webfiltering_updates")."<script>LeftDesign('update-256-white-opac20.png');</script>";
	
}


function main_artica_update_tabs(){
	
	
	$sock=new sockets();
	$EnableSystemUpdates=$sock->GET_INFO("EnableSystemUpdates");
	$EnableArticaMetaServer=intval($sock->GET_INFO("EnableArticaMetaServer"));
	
	if(!is_numeric($EnableSystemUpdates)){$EnableSystemUpdates=0;}
	
	$users=new usersMenus();
	$page=CurrentPageName();
	if($EnableSystemUpdates==1){
		$array["apt"]='{system}';
	}
	$array["config"]='{parameters}';
	
	if($EnableArticaMetaServer==1){
		$array["webfilter"]="{webfilter_databases}";
		
	}
	
	$array["suricata"]="{IDS}";
	$array["haproxy"]="{load_balancer}";
	$array["proftpd"]="{APP_PROFTPD}";
	if($users->POSTFIX_INSTALLED){
		
		$array["MailSecurity"]="{security_package}";
	}
	//$array["influx"]='{APP_INFLUXDB}';
	$array["softwares"]='{softwares}';
	
	
	
	if($users->VMWARE_TOOLS_INSTALLED){
		$array["vmware"]='{APP_VMTOOLS}';
	}
	
	$array["events"]='{events}';
	
	
	
	
	$tpl=new templates();
	
	while (list ($num, $ligne) = each ($array) ){
		if($num=="events"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"update.admin.events.php\">
					<span style='font-size:22px'>$ligne</span></a></li>\n");	
			continue;
		}
		
		if($num=="webfilter"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"ufdb.categories.status.php\">
					<span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}		
		
		if($num=="influx"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"influx.update.php\">
					<span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="suricata"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"suricata.update.php\">
					<span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}	

		if($num=="haproxy"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"haproxy.update.php\">
					<span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}	
		if($num=="proftpd"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"proftpd.update.php\">
					<span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}	
		if($num=="MailSecurity"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"mailsecurity.update.php\">
					<span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}
		
		
		if($num=="vmware"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"VMWareTools.php?popup=yes\">
					<span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}		
		
		if($num=="config"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"artica.updatev10.php\">
					<span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}				
		
		if($num=="softwares"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"update.softwares.php\">
					<span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}
		if($num=="articadb2"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"dansguardian2.databases.php?status=yes&maximize=yes\">
					<span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="articadb"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?webfiltering-tabs=yes\">
					<span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}		
		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?main_artica_update=$num\">
				<span style='font-size:22px'>$ligne</span></a></li>\n");
	}
	
	echo build_artica_tabs($html, "main_config_artica_update",1490)."<script>LeftDesign('update-256-white-opac20.png');</script>";
	
	
}

function SaveConf(){
	writelogs("AUTOUPDATE -> SAVE",__FUNCTION__,__FILE__);
$sock=new sockets();	
$ini=new Bs_IniHandler();
$configDisk=trim($sock->GET_INFO('ArticaAutoUpdateConfig'));
$ini->loadString($configDisk);
	while (list ($num, $ligne) = each ($_GET) ){
		writelogs("AUTOUPDATE:: $num=$ligne",__FUNCTION__,__FILE__);
		$ini->_params["AUTOUPDATE"][$num]=$ligne;
	}
	
	$data=$ini->toString();
	$sock->SET_INFO("WgetBindIpAddress",$_GET["WgetBindIpAddress"]);
	$sock->SET_INFO("EnableNightlyInFrontEnd",$_GET["EnableNightlyInFrontEnd"]);
	$sock->SET_INFO("ArticaScheduleUpdates",$_GET["ArticaScheduleUpdates"]);
	$sock->SET_INFO("RebootAfterArticaUpgrade",$_GET["RebootAfterArticaUpgrade"]);
	$sock->SET_INFO("EnableSystemUpdates",$_GET["EnableSystemUpdates"]);
	$sock->SET_INFO("ArticaUpdateIntervalAllways",$_GET["ArticaUpdateIntervalAllways"]);
	
	
	
	
	if(isset($_GET["CurlBandwith"])){
		$sock->SET_INFO("CurlBandwith",$_GET["CurlBandwith"]);
	}
	
	if(isset($_GET["CurlTimeOut"])){
		$sock->SET_INFO("CurlTimeOut",$_GET["CurlTimeOut"]);
	}	
	
	if(isset($_GET["NoCheckSquid"])){
		$sock->SET_INFO("NoCheckSquid",$_GET["NoCheckSquid"]);
	}
	
	writelogs("EnableScheduleUpdates = {$_GET["EnableScheduleUpdates"]}, RebootAfterArticaUpgrade = {$_GET["RebootAfterArticaUpgrade"]}",__FUNCTION__,__FILE__,__LINE__);
	if(isset($_GET["EnableScheduleUpdates"])){$sock->SET_INFO("EnableScheduleUpdates",$_GET["EnableScheduleUpdates"]);}
	$sock->SaveConfigFile($data,"ArticaAutoUpdateConfig");
	if(isset($_GET["EnablePatchUpdates"])){$sock->SET_INFO("EnablePatchUpdates",$_GET["EnablePatchUpdates"]);}
	if(isset($_GET["EnableRebootAfterUpgrade"])){$sock->SET_INFO("EnableRebootAfterUpgrade", $_GET["EnableRebootAfterUpgrade"]);}
	if(isset($_GET["DisableInstantLDAPBackup"])){$sock->SET_INFO("DisableInstantLDAPBackup", $_GET["DisableInstantLDAPBackup"]);}
	
	
	
	$sock->getFrameWork("cmd.php?ForceRefreshLeft=yes");
	$sock->getFrameWork("services.php?artica-update-cron=yes");
	$sock->getFrameWork("services.php?artica-patchs=yes");
	$tpl=new templates();
		
}

function main_artica_update_events_display(){
	$table="<table style='width:100%'>";
	$file=$_GET["ajax-events"];
	
	$sock=new sockets();
	$tbl=unserialize(base64_decode($sock->getFrameWork("cmd.php?ReadArticaLogs=yes&file=$file")));
	$datenow=date("Y-m-d");
		
if(!is_array($tbl)){return null;}
	krsort($tbl);
while (list ($num, $ligne) = each ($tbl) ){
		if(trim($ligne)==null){continue;}
		  $color_blue=false;
		 if(preg_match('#curl --progress#i',$ligne)){continue;}
		  if(preg_match('#Couldn.+?t#i',$ligne)){$color=true;}
		  if(preg_match('#can.+?t#i',$ligne)){$color=true;}
		  if(preg_match('#didn\'t#i',$ligne)){$color=true;}
		  if(preg_match('#FATAL ERROR#i',$ligne)){$color=true;}
		  if(preg_match("#CheckAndInstall#",$ligne)){continue;}
		  if(preg_match('#nightly#i',$ligne)){$color_blue=true;}
		  
		  
		  
		  if($color){$colorw="color:#d32d2d";}
		  if($color_blue){$colorw="color:blue";}
		  
		  if(preg_match("#Downloading new version#",$ligne)){
		  	$colorw="color:#005447;font-weight:bold;";
		  }
		  $ligne=str_replace($datenow,"",$ligne);
		  
		  $table=$table . "
		  <tr>
		  <td width=1% valign='top'><img src='img/fw_bold.gif'></td>
		  <td><code style='font-size:11px;$colorw'>$ligne</code></td>
		  </tr>";
	  	$color=false;
	  	$colorw=null;
	  }
	  
	 $table=$table . "</table>";
	 $table=RoundedLightWhite($table);

	 $html="<H1>{events}</H1>
	 <div style='width:100%;height:300px;overflow:auto'>
	 $table
	 </div>
	 
	 ";
	 
	 $tpl=new templates();
	 echo $tpl->_ENGINE_parse_body($html);
	 
	
}

function main_artica_update_events(){
	$sock=new sockets();
	$tbl=unserialize(base64_decode($sock->getFrameWork("cmd.php?QueryArticaLogs=yes")));
	
	
	$table="<table style='width:100%'>";
	
	
	while (list ($num, $ligne) = each ($tbl) ){
	  if(!preg_match('#artica-update-([0-9]+)-([0-9]+)-([0-9]+)-([0-9]+).debug#',$ligne,$re)){continue;}
	  $date="{$re[1]}-{$re[2]}-{$re[3]} {$re[4]}:00:00";
	  $tbl2["{$re[1]}{$re[2]}{$re[3]}{$re[4]}{$re[5]}00"]=array("DATE"=>$date,"FILE"=>$ligne);
	}
	  
	if(is_array($tbl2)){
	  krsort($tbl2);
	  $maintenant=date('Y-m-d H:00:00');
	  $today=date('Y-m-d');
	  while (list ($num, $ligne) = each ($tbl2) ){
	  		if($ligne["DATE"]==$maintenant){$color="color:#d32d2d";$text="({today})";}else{$color=null;$text=null;}
	  		if(preg_match("#$today#",$ligne["DATE"])){$text="({today})";}else{$text=null;}
	  		
		  $table=$table . "<tr ". CellRollOver("ShowArticaUpdateEvents('{$ligne["FILE"]}')").">
		  <td width=1%><img src='img/fw_bold.gif'></td>
		  <td><code style='font-size:16px;$color'>{$ligne["DATE"]}</code>&nbsp;$text</td>
		  </tr>";
	  	}
	  }
	  
	 $table=$table . "</table>";
	 
	 
	 $html="<div style='width:100%;height:300px;overflow:auto'>$html$table</div>";
	 
	 $tpl=new templates();
	 $page="<H5>{events}</H5><br>$html";
	 return $tpl->_ENGINE_parse_body($page);
	}
	
function main_artica_update_updatelist(){
	$sock=new sockets();
	$datas=$sock->getfile('autoupdate_list');
	$tbl=explode("\n",$datas);
	
	if(!is_array($tbl)){
		if(strlen($datas)>0){$tbl[]=trim($datas);}
	}
	
	if(is_array($tbl)){
		krsort($tbl);
		$table="<table style='width:100%'>";
		while (list ($num, $ligne) = each ($tbl) ){
			if(trim($ligne)<>null){
			$table=$table.
			"<tr " . CellRollOver().">
			<td width=1%><img src='img/fw_bold.gif'></td>
			<td><strong>$ligne</strong></td>
			<td width=1%><input type='button' value='{install_package}' OnClick=\"javascript:ArticaUpdateInstallPackage('$ligne');\"></td>
			</tr>
			";}
			
		}
		$table=$table ."</table>";
		
	}
	
	 $table="<div style='width:100%;height:300px;overflow:auto'>$table</div>";
	
	$tpl=new templates();
	 $page=main_artica_update_tabs() . "
	 <input type='hidden' id='install_package_text' value='{install_package_text}'>
	 <br><H5>{update_list}</H5><br>$table";
	 return $tpl->_ENGINE_parse_body($page);	
	
	}
	
function ArticaUpdateInstallPackage(){
	$package=$_GET["ArticaUpdateInstallPackage"];
	$sock=new sockets();
	$sock->getfile('autoupdate_perform:'.$package);
	}
function auto_update_perform(){
	$sock=new sockets();
	$sock->getFrameWork('cmd.php?perform-autoupdate=yes');	
	}
	
function patchs_start(){
	$page=CurrentPageName();
	$tpl=new templates();
	$text=$tpl->javascript_parse_text("{update_patchnow_explain}");
	$html=
	
	"
	<div style='text-align:right;margin-bottom:10px'>". button("{update_now}","UpdatePatchNow()")."</div>
	<div id='patchs-div' style='width:100%;height:400px;overflow:auto'></div>
	
	
	<script>
	var x_UpdatePatchNow= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			RefreshTab('main_config_artica_update');
			}		
	
	
	function UpdatePatchNow(){
		if(confirm('$text')){
			var XHR = new XHRConnection();
			XHR.appendData('UpdatePatchNow','yes');
    		AnimateDiv('patchs-div');
    		XHR.sendAndLoad('$page', 'POST',x_UpdatePatchNow);
    		}
		}	
	
	
		LoadAjax('patchs-div','$page?patchs-list=yes');
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function patchs_list(){
	include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
	$q=new mysql();
	$page=CurrentPageName();
	$tpl=new templates();
	$sql="SELECT * FROM artica_patchs ORDER BY patch_number DESC";
	
$html="
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th>{version}</th>
		<th>{size}</th>
		<th>{updated}</th>
		<th width=99%>{description}</th>
	</tr>
</thead>
<tbody>";	

	$results=$q->QUERY_SQL($sql,"artica_backup");	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
	$ligne["size"]=FormatBytes($ligne["size"]/1024);
	if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
	if($ligne["updated"]==1){$ligne["updated"]="<img src='img/20-check.png'>";}else{$ligne["updated"]="&nbsp;";}
	$ligne["path_explain"]=htmlentities($ligne["path_explain"]);
	$ligne["path_explain"]=nl2br($ligne["path_explain"]);
	
	
$html=$html.
		"
		<tr class=$classtr>
			
			<td width=1%  style='font-size:12px' nowrap><strong>{$ligne["patch_number"]}</strong></td>
			<td  style='font-size:12px' nowrap width=1%><strong>{$ligne["size"]}</strong></td>
			<td  style='font-size:12px' nowrap width=1% align='center'><strong>{$ligne["updated"]}</strong></td>
			<td style='font-size:12px' width=99%>{$ligne["path_explain"]}</td>		
		</tr>
		";
		
		
	}
$html=$html."</table>
<div style='text-align:right'>". imgtootltip("refresh-24.png","{refresh}","LoadAjax('patchs-div','$page?patchs-list=yes')")."</div>

";

echo $tpl->_ENGINE_parse_body($html);
	
}

function patchs_update(){
	$sock=new sockets();
	$sock->getFrameWork("services.php?patchs-force=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{install_app}");
}
	
?>	