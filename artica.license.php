<?php
	$GLOBALS["ICON_FAMILY"]="PARAMETERS";
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.os.system.inc');
	include_once('ressources/class.samba.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	
$usersmenus=new usersMenus();



if($usersmenus->AsArticaAdministrator==false){header('location:users.index.php');exit;}

if(isset($_GET["tabs-js"])){tab_js();exit;}
if(isset($_GET["remove-lock-lic-js"])){remove_lock_js();exit;}
if(isset($_POST["remove-lock-lic"])){remove_lock_save();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["lic-status"])){license_status();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["REGISTER"])){REGISTER();exit;}
tab_js();


function tab_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{artica_license}");
	$html="AnimateDiv('BodyContent');LoadAjax('BodyContent','$page?tabs=yes')";
	echo $html;	
	
}

function remove_lock_js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$t=time();
echo "
	var xStart$t= function (obj) {
		Loadjs('artica.license.progress.php');
	}
	
	function Start$t(){
		var XHR = new XHRConnection();
		XHR.appendData('remove-lock-lic', 'yes');
		XHR.setLockOff();
		XHR.sendAndLoad('$page', 'POST',xStart$t,false);
	}\nStart$t();\n";
}

function remove_lock_save(){
	$sock=new sockets();
	$LicenseInfos=unserialize(base64_decode($sock->GET_INFO("LicenseInfos")));
	unset($LicenseInfos["UNLOCKLIC"]);
	$sock->SaveConfigFile(base64_encode(serialize($LicenseInfos)), "LicenseInfos");
}
	


function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$q=new mysql();
	
	$array["popup"]='{artica_license}';
	
	$fontsize=18;
	while (list ($num, $ligne) = each ($array) ){

		$tab[]="<li><a href=\"$page?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			
	}
	
	
	
	$t=time();
	//
	
	echo build_artica_tabs($tab, "main_artica_license",1100)."<script>LeftDesign('key-256-opac20');</script>";
	
}


function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{artica_license}");
	$html="YahooWin3('650','$page?popup=yes','$title')";
	echo $html;
}
function popup(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));
	$LicenseInfos=unserialize(base64_decode($sock->GET_INFO("LicenseInfos")));
	$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
	if($LicenseInfos["COMPANY"]==null){$LicenseInfos["COMPANY"]=$WizardSavedSettings["company_name"];}
	if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}	
	if(!is_numeric($LicenseInfos["EMPLOYEES"])){$LicenseInfos["EMPLOYEES"]=$WizardSavedSettings["employees"];}
	$RegisterCloudBadEmail=intval($sock->GET_INFO("RegisterCloudBadEmail"));
	$RegisterCloudBadEmail_text=null;
	$t=time();
	$ASWEB=false;
	if($users->SQUID_INSTALLED){$ASWEB=true;}
	if($users->WEBSTATS_APPLIANCE){$ASWEB=true;}
	if($users->KASPERSKY_WEB_APPLIANCE){$ASWEB=true;}
	
	
	if($RegisterCloudBadEmail){
		$RegisterCloudBadEmail_text="<p class=text-error style='font-size:18px'>{incorrect_email_address_cloud}</p>";
	}
	
	$titleprice="<strong>{start_99_euros}</strong><hr>";
	if(!$users->CORP_LICENSE){

		if($ASWEB){
		$PAYPAL_TABLE["1 server 5 users"]="1 server 5 users : &99,00 EUR - annuel";
		$PAYPAL_TABLE["1 server < 10 users"]="1 server < 10 users : &200,00 EUR - annuel";
		$PAYPAL_TABLE["1 server 11 < 50 users"]="1 server 11 < 50 users : &600,00 EUR - annuel";
		$PAYPAL_TABLE["1 server  50 < 100"]="1 server  50 < 100 : &800,00 EUR - annuel";
		$PAYPAL_TABLE["1 server  100 < 2500 users"]="1 server  100 < 2500 users : &1 800,00 EUR - annuel";
		
		
		$paypalform="		
			<form action=\"https://www.paypal.com/cgi-bin/webscr\" method=\"post\" target=\"_top\">
			<input type=\"hidden\" name=\"cmd\" value=\"_s-xclick\">
			<input type=\"hidden\" name=\"hosted_button_id\" value=\"7VDNE34DF6MUU\">
			<div class=form style='width:95%'>
					
			
			<table style='width:100%'>
			<tr>
			 	<td class=legend>
					<input type=\"hidden\" name=\"on0\" value=\"Buy a License\" class=legend ><span style='font-size:16px'>{buy_a_license}:</span></td>
				<td >". Field_array_Hash($PAYPAL_TABLE, "os0","1 server 5 users",null,null,0,"font-size:16px")."</td>
			</tr>
			<tr>
				<td class=legend style='font-size:16px'>
						
						
				<input type=\"hidden\" name=\"on1\" value=\"Reseller\">{reseller_company}:</td>
				<td>". Field_text("os1",null,'font-size:16px')."
				<input type=\"hidden\" name=\"on5\" value=\"Company Name\">
				<input type=\"hidden\" name=\"on4\" value=\"email\">
				
				<input type=\"hidden\" name=\"on2\" value=\"Computer serial\">
				<input type=\"hidden\" name=\"on3\" value=\"Unique License\">
				
				
				<input type=\"hidden\" name=\"os2\" value=\"$uuid\">
				<input type=\"hidden\" name=\"os3\" value=\"{$LicenseInfos["license_number"]}\">
				<input type=\"hidden\" name=\"os4\" value=\"{$LicenseInfos["EMAIL"]}\">
				<input type=\"hidden\" name=\"os5\" value=\"{$LicenseInfos["COMPANY"]}\">
							
						
						
				</td>
			</tr>
			<tr>
			<td colspan=2 align='right'>
			<hr>
			<input type=\"hidden\" name=\"currency_code\" value=\"EUR\">
			<input type=\"image\" style='border:0px' src=\"https://www.paypalobjects.com/en_US/i/btn/btn_subscribeCC_LG_global.gif\" border=\"0\" name=\"submit\" alt=\"PayPal – The safer, easier way to pay online.\">
			<img alt=\"\" border=\"0\" src=\"https://www.paypalobjects.com/fr_FR/i/scr/pixel.gif\" width=\"1\" height=\"1\">
			</td>
			</tr>				
							
			</table>				
			
			</form>
			</div>";
		}
		
	if($ASWEB){
		$explain="{CORP_LICENSE_EXPLAIN}<br><a href=\"http://www.artica.fr/proxy.comparative.php\" target=_new style='font-weight:bold;text-decoration:underline'>{click_here_comparative}</a>";
		$quotation="<div style='font-size:16px;font-weight:bold;margin-top:15px'>{price_quote}:</div>
			
					<a href=\"javascript:blur();\" 
				OnClick=\"javascript:s_PopUpFull('http://www.proxy-appliance.org/index.php?cID=292','1024','900');\"
				style=\"font-size:14px;font-weight;bold;text-decoration:underline\">{click_here_price_quote}</a>
			</div>
		";
		}
	}
	
	$unlocklick="<td>". Field_text("UNLOCKLIC-$t",$LicenseInfos["UNLOCKLIC"],"font-size:16px;width:240px")."</td>";

	
	if($LicenseInfos["license_status"]==null){
		$step=1;
		$LicenseInfos["license_status"]="{waiting_registration}";
		$star="{explain_license_free}";
		$button_text="{request_a_quote}/{refresh}";
		$paypal=null;
	}else{
		$step=2;
		$step_text="{waiting_order}";
		$button_text="{update_the_request}";
		$star="{explain_license_order}";
		$paypal=$paypalform;
	}	
	
	if($LicenseInfos["license_status"]=="{license_active}"){
		$users->CORP_LICENSE=true;
		$titleprice=null;
		$unlocklick="<td style='font-size:16px;font-weight:bold'><input type='hidden' id='UNLOCKLIC-$t' value='{$LicenseInfos["UNLOCKLIC"]}'>{$LicenseInfos["UNLOCKLIC"]}</td>";
	}	
	
	if($users->CORP_LICENSE){
		$star=null;$titleprice=null;
		$step=3;
		$step_text="{license_active}";
	}
	
	if(is_numeric($LicenseInfos["TIME"])){
		$tt=distanceOfTimeInWords($LicenseInfos["TIME"],time());
		$last_access="
		<tr>
			<td class=legend style='font-size:16px'>{last_update}:</td>
			<td style='font-size:16px'>{since} $tt</td>
		</tr>";	
	}
	
	if($LicenseInfos["GoldKey"]<>null){
		$LicenseInfos["license_number"]=$LicenseInfos["GoldKey"];
	}
	
	
	if(trim($LicenseInfos["license_number"])<>null){
		
		$explain="{explain_license_order}";
		
	}
	
	
	$CORP_LICENSE=0;
	$textcolor="black";
	$bt="<hr>".button($button_text,"RegisterSave$t()",26);
	if($users->CORP_LICENSE){
		$CORP_LICENSE=1;
		$textcolor="#23A83E";
		$paypal=null;
		$explain=null;
	}

	if($explain<>null){
		$explain="<div style='font-size:16px' class=explain>$titleprice<br>$explain$quotation</div>";
	}	
	
	
	$html="
	$RegisterCloudBadEmail_text
	$explain
	$paypal
	
	<div id='$t' ></div>
	<script>LoadAjaxRound('$t','$page?lic-status=yes');</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function REGISTER(){
	$sock=new sockets();
	$LicenseInfos=unserialize(base64_decode($sock->GET_INFO("LicenseInfos")));
	$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
	
	while (list ($num, $ligne) = each ($_POST) ){
		$LicenseInfos[$num]=$ligne;
		$WizardSavedSettings[$num]=$ligne;
	}
	
	$sock->SaveConfigFile(base64_encode(serialize($WizardSavedSettings)), "WizardSavedSettings");
	$sock->SaveConfigFile(base64_encode(serialize($LicenseInfos)), "LicenseInfos");
	//
	
}


function license_status(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$license_number=null;
	$uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));
	$RegisterCloudBadEmail=intval($sock->GET_INFO("RegisterCloudBadEmail"));
	$LicenseInfos=unserialize(base64_decode($sock->GET_INFO("LicenseInfos")));
	$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
	if($LicenseInfos["COMPANY"]==null){$LicenseInfos["COMPANY"]=$WizardSavedSettings["company_name"];}
	if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}
	if(!is_numeric($LicenseInfos["EMPLOYEES"])){$LicenseInfos["EMPLOYEES"]=$WizardSavedSettings["employees"];}
	$licenseTime=null;
	$t=time();
	$ASWEB=false;
	if($users->SQUID_INSTALLED){$ASWEB=true;}
	if($users->WEBSTATS_APPLIANCE){$ASWEB=true;}
	if($users->KASPERSKY_WEB_APPLIANCE){$ASWEB=true;}
	$unlocklick_hidden=null;
	$your_email_address_color=null;
	$RegisterCloudBadEmail_text=null;
	if($RegisterCloudBadEmail==1){
		$RegisterCloudBadEmail_text="<p class=text-error style='font-size:18px'>{incorrect_email_address_cloud}</p>";
		$your_email_address_color=";color:#d32d2d !important";
	}
	
	
	
	
	$License_explain=
	"<div class=explain style='font-size:20px;margin:20px'>".
	$tpl->_ENGINE_parse_body("{artica_license_explain}")."</div>";
	
	$WhichLicense[null]="{select}";
	$WhichLicense["evaluation"]="{request_an_evaluation_license}";
	$WhichLicense["cotation"]="{request_an_corporate_license}";
	
	$LICENCE_REQUEST_ERROR=$tpl->javascript_parse_text("{LICENCE_REQUEST_ERROR}");
	if(!isset($LicenseInfos["LICENCE_REQUEST"])){$LicenseInfos["LICENCE_REQUEST"]=null;}
	
	$WhichLicense_field="<tr>
				<td class=legend style='font-size:24px'>{license_request}:</td>
				<td style='font-size:24px;font-weight:bold'>
				<input type='hidden' id='LICENCE_REQUEST-$t' value='evaluation'>
			{request_an_evaluation_license}</td>
			</tr>";
	

	$unlocklick="<td>". Field_text("UNLOCKLIC-$t",$LicenseInfos["UNLOCKLIC"],"font-size:24px;width:240px")."</td>";
	
	
	
	if($LicenseInfos["license_status"]==null){
		$step=1;
		$LicenseInfos["license_status"]="{waiting_registration}";
		$star="{explain_license_free}";
		$button_text="{request_an_evaluation_license}";
	}else{
		$step=2;
		$step_text="{waiting_order}";
		$button_text="{update_the_request}";
		$star="{explain_license_order}";
		if($LicenseInfos["LICENCE_REQUEST"]=="evaluation"){
			$step_text="{request_an_evaluation_license}";
		}
		
	}
	
	if($LicenseInfos["license_status"]=="{license_active}"){
		$users->CORP_LICENSE=true;
		$WhichLicense_field=null;
		$titleprice=null;
		$License_explain=null;
		if($LicenseInfos["UNLOCKLIC"]<>null){
		$unlocklick="
		<td style='font-size:24px;font-weight:bold'>
			<input type='hidden' id='UNLOCKLIC-$t' value='{$LicenseInfos["UNLOCKLIC"]}'>
			<div style='float:right'>". imgtootltip("delete-32.png","{delete}:{unlock_license}","Loadjs('$page?remove-lock-lic-js=yes')")."</div>
			<span style='font-size:24px'>{$LicenseInfos["UNLOCKLIC"]}</span>
		</td>";
		}else{
			$unlocklick=null;
		}
	}
	
	if($users->CORP_LICENSE){
		$star=null;$titleprice=null;
		$WhichLicense_field=null;
		$step=3;
		$step_text="{license_active}";
	}
	
	if(is_numeric($LicenseInfos["TIME"])){
		$tt=distanceOfTimeInWords($LicenseInfos["TIME"],time());
		$last_access="
			<tr>
				<td class=legend style='font-size:24px'>{last_update}:</td>
				<td style='font-size:24px'>{since} $tt</td>
			</tr>";
	}
	if($LicenseInfos["GoldKey"]<>null){
		$LicenseInfos["license_number"]=$LicenseInfos["GoldKey"];
	}
	
	
	if(trim($LicenseInfos["license_number"])<>null){
		$explain="{explain_license_order}";
	}
	
	
	$CORP_LICENSE=0;
	$textcolor="black";
	$bt="<hr>".button($button_text,"RegisterSave$t()",26);
	
	if($unlocklick<>null){
		$unlock_license="<tr>
				<td class=legend style='font-size:24px'>{unlock_license}:</td>
				$unlocklick
			</tr>";
	}

				
	if($users->CORP_LICENSE){
		$CORP_LICENSE=1;
		$textcolor="#23A83E";
		$paypal=null;
		$explain=null;
		$LicenseInfos["license_status"]="{license_active}";
	}
	
	if($explain<>null){
		$explain="<div style='font-size:16px' class=explain>$titleprice<br>$explain</div>";
	}
	
	$FINAL_TIME=0;
	
	if(isset($LicenseInfos["FINAL_TIME"])){$FINAL_TIME=intval($LicenseInfos["FINAL_TIME"]);}
	
	
	
	if($FINAL_TIME>0){
		$ExpiresSoon=intval(time_between_day_Web($FINAL_TIME));
		$distanceOfTimeInWords=distanceOfTimeInWords(time(),$FINAL_TIME);
		if($ExpiresSoon<7){
			$ExpiresSoon_text="<strong style='color:red;font-size:16px'>&nbsp;{ExpiresSoon}</strong>";
		}
		
		if($FINAL_TIME<time()){
			$ExpiresSoon_text="<strong style='color:red;font-size:16px'>&nbsp;{expired} <i>{license_expired_explain}</i></strong>";
			$distanceOfTimeInWords=null;
		}
		
		$licenseTime="
			<tr>
				<td class=legend style='font-size:24px'>{expiredate}:</td>
				<td style='font-size:24px'>". $tpl->time_to_date($FINAL_TIME)." ($distanceOfTimeInWords$ExpiresSoon_text)</td>
			</tr>";
		
	}
	
	if($LicenseInfos["license_number"]<>null){
	
	$license_number="<tr>
	<td class=legend style='font-size:24px'>{license_number}:</td>
	<td style='font-size:24px'>{$LicenseInfos["license_number"]}</td>
	</tr>";
	}

	
	$html="
$License_explain
<div  style='width:98%;min-height:620px' class=form>
$RegisterCloudBadEmail_text
<table style='width:100%'>
<tr>
	<td valign='top'>
		<table style='width:100%'>
			<tr>
				<td class=legend style='font-size:24px;vertical-align:middle;'>{step}:</td>
				<td style='font-size:28px;font-weight:bold;color:$textcolor'>$step: $step_text<br>

				</td>
			</tr>
			<tr>
				<td colspan=2 align=right'>
									<div style='text-align:right'><a href=\"javascript:blur();\" 
							OnClick=\"javascript:Loadjs('artica.settings.php?js=yes&func-ProxyInterface=yes');\"
				style='text-decoration:underline;font-size:16px'>{http_proxy}</a>
			</td>
			</tr>
			$last_access
			<tr>
				<td class=legend style='font-size:24px'>{uuid}:</td>
				<td style='font-size:24px'>$uuid</td>
			</tr>
			<tr>
				<td class=legend style='font-size:24px'>{company}:</td>
				<td>". Field_text("COMPANY-$t",$LicenseInfos["COMPANY"],"font-size:24px;width:450px")."</td>
			</tr>
			<tr>
				<td class=legend style='font-size:24px$your_email_address_color'>{your_email_address}:</td>
				<td>". Field_text("EMAIL-$t",$LicenseInfos["EMAIL"],"font-size:24px;width:450px$your_email_address_color")."</td>
			</tr>
			<tr>
				<td class=legend style='font-size:24px'>{nb_employees}:</td>
				<td>". Field_text("EMPLOYEES-$t",FormatNumber($LicenseInfos["EMPLOYEES"]),"font-size:24px;width:80px")."</td>
			</tr>
			
			$WhichLicense_field

			$unlock_license
			$unlocklick_hidden
		<tr>
			<td class=legend style='font-size:24px'>{license_status}:</td>
			<td style='font-size:28px;color:$textcolor'>{$LicenseInfos["license_status"]}</td>
		</tr>
		$licenseTime
		<tr>
			<td colspan=2 align='right'>$bt</td>
		</tr>
	</table>
</td>
</table>

	
<script>
var x_RegisterSave$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	Loadjs('artica.license.progress.php');
}
	
function RegisterSave$t(){
	var XHR = new XHRConnection();
	
	if(document.getElementById('LICENCE_REQUEST-$t')){
		var LICENCE_REQUEST=document.getElementById('LICENCE_REQUEST-$t').value;
		if(LICENCE_REQUEST==''){alert('$LICENCE_REQUEST_ERROR'); return;}
		XHR.appendData('LICENCE_REQUEST',LICENCE_REQUEST);
	}
	
	XHR.appendData('COMPANY',document.getElementById('COMPANY-$t').value);
	XHR.appendData('EMAIL',document.getElementById('EMAIL-$t').value);
	XHR.appendData('EMPLOYEES',document.getElementById('EMPLOYEES-$t').value);
	
	if( document.getElementById('UNLOCKLIC-$t') ){
		XHR.appendData('UNLOCKLIC',document.getElementById('UNLOCKLIC-$t').value);
	}
	XHR.appendData('REGISTER','1');
	XHR.sendAndLoad('$page', 'POST',x_RegisterSave$t);
}
	
function CheckCorpLic(){
	var lic=$CORP_LICENSE;
}
CheckCorpLic();
</script>
";
	
		echo $tpl->_ENGINE_parse_body($html);
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}