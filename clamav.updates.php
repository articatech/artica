<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.users.menus.inc');

if(!GetRigths()){
	$tpl=new templates();
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	die();exit();
}

if(isset($_GET["FreshClamOptions"])){FreshClamOptions();exit;}
if(isset($_POST["EnableFreshClam"])){EnableFreshClam();exit;}
if(isset($_GET["freshclam-status"])){fresh_clam_status();exit;}
popup();

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$users=new usersMenus();
	
	$sock->getFrameWork("clamav.php?sigtool=yes");
	$bases=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/interface-cache/ClamAVBases"));


	if(count($bases)>0){
		$DBS[]="<table style='width:100%; -webkit-border-radius: 4px;-moz-border-radius: 4px;border-radius: 4px;border:2px solid #CCCCCC'>
			<tr style='height:80px'>
						<td style='font-size:26px' colspan=2><strong>{clamav_antivirus_patterns_status}</td>
				</tr>
			</table>";
		while (list ($db, $MAIN) = each ($bases) ){
			$DBS[]="
			<table style='width:100%;margin-top:15px;-webkit-border-radius: 4px;-moz-border-radius: 4px;border-radius: 4px;border:2px solid #CCCCCC'>
			<tr>
				<td style='font-size:24px' colspan=2><strong>$db</td>
			</tr>
			<tr>
				<td style='font-size:22px' class=legend>{date}:</td>
				<td style='font-size:22px'><strong>{$MAIN["zDate"]}</strong></td>
			</tr>
			<tr>
				<td style='font-size:22px' class=legend>{version}:</td>
				<td style='font-size:22px'><strong>{$MAIN["version"]}</strong></td>
			</tr>
				<tr>
					<td style='font-size:22px' class=legend>{signatures}:</td>
					<td style='font-size:22px'><strong>". FormatNumber($MAIN["signatures"])."</strong></td>
			</tr>
			</table>
			";
		
		}
	}else{
		$DBS[]=FATAL_ERROR_SHOW_128("{missing_clamav_pattern_databases}");
		
	}
	
	$DBS[]="
	<table style='width:100%'>
	<tr  style='height:80px'>
		<td align='right'><hr>". button("{update_now}", "Loadjs('clamav.update.progress.php')",42)."</td>
	</tr>
	</table>";
	
	
	$html="
	<div style='font-size:30px;margin-bottom:20px'>{clamav_antivirus_databases}</div>		
	<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
			<td valign='top' style='width:600px'>
				<div id='FreshClamOptions'></div>
			</td>
			<td valign='top' style='width:600px'>
			".@implode("\n", $DBS).
			"</td>
		</tr>
	</table>
	</div>
	<script>
		LoadAjax('FreshClamOptions','$page?FreshClamOptions=yes');
	</script>				
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function FreshClamOptions(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$FreshClamCheckDay=intval($sock->GET_INFO("FreshClamCheckDay"));
	$FreshClamMaxAttempts=intval($sock->GET_INFO("FreshClamMaxAttempts"));
	$EnableFreshClam=intval($sock->GET_INFO("EnableFreshClam"));
	if($FreshClamCheckDay==0){$FreshClamCheckDay=16;}
	if($FreshClamMaxAttempts==0){$FreshClamMaxAttempts=5;}
	for($i=1;$i<25;$i++){
		
		$FreshClamCheckDayZ[$i]="$i {times}";
		
	}
	$t=time();
	$EnableClamavUnofficial=intval($sock->GET_INFO("EnableClamavUnofficial"));
	$SecuriteInfoCode=$sock->GET_INFO("SecuriteInfoCode");
	$MalwarePatrolCode=$sock->GET_INFO("MalwarePatrolCode");
	
	$html="
<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{service_status}:</td>
		<td><div id='freshclam-status'></div></td>
	</tr>			
	<tr>
		<td class=legend style='font-size:18px'>{enable_service}:</td>
		<td>" . Field_checkbox_design("EnableFreshClam", 1,$EnableFreshClam)."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>". texttooltip("{MaxAttempts}","{MaxAttempts_text}").":</td>
		<td>" . Field_text('FreshClamMaxAttempts',$FreshClamMaxAttempts,'width:150px;font-size:18px')."</td>
	</tr>			
	<tr>
		<td valign='top' class=legend style='font-size:18px'>". texttooltip("{check_times}","{FreshClamCheckDay}")."</td>
		<td>". Field_array_Hash($FreshClamCheckDayZ,"FreshClamCheckDay", $FreshClamCheckDay,"style:font-size:18px")."</td>
	</tr>			
			
	<tr>
		<td valign='top' class=legend style='font-size:18px'>". texttooltip("{clamav_unofficial}","<strong>{enable_clamav_unofficial}</strong>{clamav_unofficial_text}")."</td>
		<td>". Field_checkbox_design("EnableClamavUnofficial", 1,$EnableClamavUnofficial,"UnofficialCheck()")."</td>
	</tr>
	<tr>
		<td valign='top' class=legend style='font-size:18px'>". texttooltip("{securiteinfo_code}","{securiteinfo_code_explain}")."</td>
		<td>". Field_text("SecuriteInfoCode", $SecuriteInfoCode,"font-size:18px;width:400px")."</td>
	</tr>
	<tr>
		<td valign='top' class=legend style='font-size:18px'>". texttooltip("{MalwarePatrol}","{malwarepatrol_receipt_code}")."</td>
		<td>". Field_text("MalwarePatrolCode", $MalwarePatrolCode,"font-size:18px;width:400px")."</td>
	</tr>
	<tr style='height:80px'>
		<td colspan=2 align='right'>". button("{apply}", "Save$t()",26)."</td>
	</tr>
	</table>
<script>
var xSave$t= function (obj) {
	var response=obj.responseText;
	if(response){alert(response);}
    Loadjs('clamav.freshclam.progress.php');
	}	
	
function Save$t(){
	var XHR = new XHRConnection();
	if(document.getElementById('EnableClamavUnofficial').checked){
		XHR.appendData('EnableClamavUnofficial',1);
	}else{
		XHR.appendData('EnableClamavUnofficial',0);
	}
	
	
	if(document.getElementById('EnableFreshClam').checked){
		XHR.appendData('EnableFreshClam',1);
	}else{
		XHR.appendData('EnableFreshClam',0);
	}	
	XHR.appendData('FreshClamCheckDay',document.getElementById('FreshClamCheckDay').value);
	XHR.appendData('SecuriteInfoCode',document.getElementById('SecuriteInfoCode').value);
	XHR.appendData('MalwarePatrolCode',document.getElementById('MalwarePatrolCode').value);
	XHR.appendData('FreshClamMaxAttempts',document.getElementById('FreshClamMaxAttempts').value);
	
	XHR.sendAndLoad('$page', 'POST',xSave$t);	
	
	}
	
	function UnofficialCheck(){
		document.getElementById('SecuriteInfoCode').disabled=true;
		document.getElementById('MalwarePatrolCode').disabled=true;
		
		if(document.getElementById('EnableClamavUnofficial').checked){
			document.getElementById('SecuriteInfoCode').disabled=false;
			document.getElementById('MalwarePatrolCode').disabled=false;
		}
	}
	UnofficialCheck();
	LoadAjax('freshclam-status','$page?freshclam-status=yes');
	
</script>			
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function EnableFreshClam(){
	$sock=new sockets();
	while (list ($key, $value) = each ($_POST) ){
		$sock->SET_INFO($key, $value);
	}
}

function fresh_clam_status(){
	if($GLOBALS["VERBOSE"]){echo "<strong>fresh_clam_status()</strong><br>\n";}
	$tpl=new templates();
	$sock=new sockets();
	$sock->getFrameWork("clamav.php?freshclam-status=yes");
	$ini=new Bs_IniHandler();
	$ini->loadFile("/usr/share/artica-postfix/ressources/logs/web/freshclam.status");
	echo $tpl->_ENGINE_parse_body(DAEMON_STATUS_ROUND("FRESHCLAM",$ini,null,0));
	
}



function GetRigths(){
	$user=new usersMenus();
	if($user->AsPostfixAdministrator){return true;}
	if($user->AsSquidAdministrator){return true;}
	if($user->AsSambaAdministrator){return true;}
	return false;
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}