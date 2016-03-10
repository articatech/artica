<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.wifidog.settings.inc');

$usersmenus=new usersMenus();
if(!$usersmenus->AsSquidAdministrator){echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die();}

if(isset($_POST["ruleid"])){Save();exit;}


Page();
function Page(){
	$ruleid=$_GET["ID"];
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	
	$sock=new wifidog_settings($ruleid);
	$ArticaHotSpotNowPassword=intval($sock->GET_INFO("ArticaHotSpotNowPassword"));
	$ENABLED_REDIRECT_LOGIN=intval($sock->GET_INFO("ENABLED_REDIRECT_LOGIN"));
	$ArticaSplashHotSpotEndTime=intval($sock->GET_INFO("ArticaSplashHotSpotEndTime"));
	$ENABLED_META_LOGIN=intval($sock->GET_INFO("ENABLED_META_LOGIN"));
	$USE_TERMS=intval($sock->GET_INFO("USE_TERMS"));
	$ArticaSplashHotSpotCacheAuth=intval($sock->GET_INFO("ArticaSplashHotSpotCacheAuth"));
	$USE_MYSQL=intval($sock->GET_INFO("USE_MYSQL"));
	$USE_ACTIVEDIRECTORY=intval($sock->GET_INFO("USE_ACTIVEDIRECTORY"));
	
	$Timez[0]="{unlimited}";
	$Timez[30]="30 {minutes}";
	$Timez[60]="1 {hour}";
	$Timez[120]="2 {hours}";
	$Timez[180]="3 {hours}";
	$Timez[360]="6 {hours}";
	$Timez[720]="12 {hours}";
	$Timez[1440]="1 {day}";
	$Timez[2880]="2 {days}";
	$Timez[10080]="1 {week}";
	$Timez[20160]="2 {weeks}";
	$Timez[40320]="1 {month}";
	
	$ENABLED_AUTO_LOGIN=intval($sock->GET_INFO("ENABLED_AUTO_LOGIN"));
	$USE_ACTIVEDIRECTORY=intval($sock->GET_INFO("USE_ACTIVEDIRECTORY"));
	$ALLOW_RECOVER_PASS=intval($sock->GET_INFO("ALLOW_RECOVER_PASS"));
	$DO_NOT_AUTENTICATE=intval($sock->GET_INFO("DO_NOT_AUTENTICATE"));
	$LIMIT_BY_SIZE=intval($sock->GET_INFO("LIMIT_BY_SIZE"));
	$LANDING_PAGE=$sock->GET_INFO("LANDING_PAGE");
	$LOST_LANDING_PAGE=$sock->GET_INFO("LOST_LANDING_PAGE");
	$MACWHITE=intval($sock->GET_INFO("MACWHITE"));
	$SMS_REGISTER=intval($sock->GET_INFO("SMS_REGISTER"));
	$BOUNCE_AUTH=intval($sock->GET_INFO("BOUNCE_AUTH"));
	$TOS_VALUE=intval($sock->GET_INFO("TOS_VALUE"));
	
	if($LOST_LANDING_PAGE==null){$LOST_LANDING_PAGE="http://articatech.net";}
	$ArticaSplashHotSpotRemoveAccount=intval($sock->GET_INFO("ArticaSplashHotSpotRemoveAccount"));
	
	if($ENABLED_AUTO_LOGIN==1){
		$ENABLED_SMTP=intval($sock->GET_INFO("ENABLED_SMTP"));
		if($ENABLED_SMTP==0){
			echo $tpl->_ENGINE_parse_body("<p class=text-error>{HOTSPOT_ENABLED_AUTO_LOGIN_SMTP_DISABLED}</p>");
		}else{
			$smtp_server_name=trim($sock->GET_INFO("smtp_server_name"));
			$smtp_server_port=intval(trim($sock->GET_INFO("smtp_server_port")));
			$smtp_sender=trim($sock->GET_INFO("smtp_sender"));
			if( ($smtp_server_name==null) OR ($smtp_sender==null) ){
				echo $tpl->_ENGINE_parse_body("<p class=text-error>{HOTSPOT_ENABLED_AUTO_LOGIN_SMTP_SETTINGS}</p>");
			}
		}
		
	}
	
	
	$ayDscp = array(0 => '{default}',8 => '0x20',10 => '0x28',12 => '0x30',14 => '0x38',16 => '0x40',18 => '0x48',20 => '0x50',22 => '0x58',24 => '0x60',26 => '0x68',28 => '0x70',30 => '0x78',32 => '0x80',34 => '0x88',36 => '0x90',38 => '0x98',40 => '0xA0',46 => '0xB8',48 => '0xC0',56 => '0xE0');
	$html="<div style='width:98%' class=form>
	<table style='width:100%'>
		
	<tr>
		<td class=legend style='font-size:22px'>{use_terme_of_use}:</td>
		<td>". Field_checkbox_design("USE_TERMS-$t",1,$USE_TERMS)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{send_accounts_to_meta_server}","{send_accounts_to_meta_server_explain}").":</td>
		<td>". Field_checkbox_design("ENABLED_META_LOGIN-$t",1,$ENABLED_META_LOGIN)."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{allow_recover_password}","{allow_recover_password_explain_hotspot}").":</td>
		<td>". Field_checkbox_design("ALLOW_RECOVER_PASS-$t",1,$ALLOW_RECOVER_PASS)."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{lost_landing_page}","{lost_landing_page_explain}").":</td>
		<td>". Field_text("LOST_LANDING_PAGE-$t",$LOST_LANDING_PAGE,"font-size:22px;width:350px")."</td>
	</tr>	

	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{landing_page}","{landing_page_hotspot_explain}").":</td>
		<td>". Field_text("LANDING_PAGE-$t",$LANDING_PAGE,"font-size:22px;width:350px")."</td>
	</tr>	
				
				
	<tr>
		<td class=legend style='font-size:22px;text-transform:capitalize'>{re_authenticate_each} ({default}):</td>
		<td style='font-size:18px'>". Field_array_Hash($Timez,"ArticaSplashHotSpotCacheAuth-$t",
					$ArticaSplashHotSpotCacheAuth,null,null,0,"font-size:22px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px;text-transform:capitalize'>".texttooltip("{re_authenticate_each}","{re_authenticate_each_hotspot_size}").":</td>
		<td style='font-size:18px'>". Field_text("LIMIT_BY_SIZE-$t",$LIMIT_BY_SIZE,"font-size:22px;width:120px")."&nbsp;MB</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>". texttooltip("{tcp_outgoing_tos}","{HOTSPOT_TOS_VALUE_EXPLAIN}").":</td>
		<td>". Field_array_Hash($ayDscp,"TOS_VALUE-$t",$TOS_VALUE,'style:font-size:22px;')."</td>
	</tr>
				
				
							
							
	<tr>
		<td class=legend style='font-size:22px;text-transform:capitalize'>".texttooltip("{disable_account_in} ({default})","{ArticaSplashHotSpotEndTime_explain}").":</td>
		<td style='font-size:18px'>". Field_array_Hash($Timez,"ArticaSplashHotSpotEndTime-$t",$ArticaSplashHotSpotEndTime,null,null,0,"font-size:22px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px;text-transform:capitalize'>".texttooltip("{remove_account_in} ({default})","{ArticaSplashHotSpotRemoveAccount_explain}").":</td>
		<td style='font-size:18px'>". Field_array_Hash($Timez,"ArticaSplashHotSpotRemoveAccount-$t",$ArticaSplashHotSpotRemoveAccount,null,null,0,"font-size:22px")."</td>
	</tr>

	<tr>
		<td class=legend style='font-size:22px;text-transform:capitalize'>".texttooltip("{bounce_if_already_authenticated}","{bounce_if_already_authenticated_hotspot}").":</td>
		<td>". Field_checkbox_design("BOUNCE_AUTH-$t",1,$BOUNCE_AUTH)."</td>
	</tr>				
				
	<tr>
		<td class=legend style='font-size:22px;text-transform:capitalize'>".texttooltip("{save_computer_in_whitelist}","{save_computer_in_whitelist_hotspot}").":</td>
		<td>". Field_checkbox_design("MACWHITE-$t",1,$MACWHITE)."</td>
	</tr>					
				

	<tr><td colspan=2 style='font-size:30px'>{authentication}</td></tr>					
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{DO_NOT_AUTENTICATE}","{DO_NOT_AUTENTICATE_HOTSPOT_EXPLAIN}").":</td>
		<td>". Field_checkbox_design("DO_NOT_AUTENTICATE-$t",1,$DO_NOT_AUTENTICATE)."</td>
	</tr>					
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{use_local_database}","{hotspot_use_local_database}").":</td>
		<td>". Field_checkbox_design("USE_MYSQL-$t",1,$USE_MYSQL)."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>". texttooltip("{use_active_directory}","{hotspot_use_active_directory}").":</td>
		<td>". Field_checkbox_design("USE_ACTIVEDIRECTORY-$t",1,$USE_ACTIVEDIRECTORY)."</td>
	</tr>				
				
	<tr><td colspan=2 style='font-size:30px'>{self_register}</td></tr>			
	
	<tr>
		<td class=legend style='font-size:22px;text-transform:capitalize'>". texttooltip("{enable_hotspot_autologin}","{enable_hotspot_autologin_explain}").":</td>
		<td>". Field_checkbox_design("ENABLED_AUTO_LOGIN-$t",1,intval($sock->GET_INFO("ENABLED_AUTO_LOGIN")))."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px;text-transform:capitalize'>". texttooltip("{enable_hotspot_sms}","{enable_hotspot_sms_explain}").":</td>
		<td>". Field_checkbox_design("SMS_REGISTER-$t",1,$SMS_REGISTER)."</td>
	</tr>
				
				
	<tr>
		<td class=legend style='font-size:22px;text-transform:capitalize'>". texttooltip("{enable_confirmation_establish_session}","{enable_confirmation_establish_session_explain}").":</td>
		<td>". Field_checkbox_design("ENABLED_REDIRECT_LOGIN-$t",1,$ENABLED_REDIRECT_LOGIN)."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px;text-transform:capitalize'>{remove_password_field}:</td>
		<td>". Field_checkbox_design("ArticaHotSpotNowPassword-$t",1,$ArticaHotSpotNowPassword)."</td>
	</tr>				

			

				
				
				
				
				
				
				
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","Save$t()","42px")."</td>
	</tr>
	</table>
	<script>
	
	var xSave$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		$('#HOSTPOT_RULES').flexReload();
		RefreshTab('HOTSPOT_TAB');
		
	}	
	
	function Save$t(){
		
		var XHR = new XHRConnection();
		XHR.appendData('ruleid',$ruleid);
		
		
		if(document.getElementById('ArticaHotSpotNowPassword-$t').checked){XHR.appendData('ArticaHotSpotNowPassword',1);}else{XHR.appendData('ArticaHotSpotNowPassword',0); }
		if(document.getElementById('USE_TERMS-$t').checked){XHR.appendData('USE_TERMS',1); }else{ XHR.appendData('USE_TERMS',0); }		
		if(document.getElementById('ENABLED_REDIRECT_LOGIN-$t').checked){XHR.appendData('ENABLED_REDIRECT_LOGIN',1); }else{ XHR.appendData('ENABLED_REDIRECT_LOGIN',0); }
		if(document.getElementById('ENABLED_AUTO_LOGIN-$t').checked){XHR.appendData('ENABLED_AUTO_LOGIN',1); }else{ XHR.appendData('ENABLED_AUTO_LOGIN',0); }
		if(document.getElementById('ENABLED_META_LOGIN-$t').checked){XHR.appendData('ENABLED_META_LOGIN',1); }else{ XHR.appendData('ENABLED_META_LOGIN',0); }
		if(document.getElementById('ALLOW_RECOVER_PASS-$t').checked){XHR.appendData('ALLOW_RECOVER_PASS',1); }else{ XHR.appendData('ALLOW_RECOVER_PASS',0); }
		if(document.getElementById('DO_NOT_AUTENTICATE-$t').checked){XHR.appendData('DO_NOT_AUTENTICATE',1); }else{ XHR.appendData('DO_NOT_AUTENTICATE',0); }
		if(document.getElementById('USE_MYSQL-$t').checked){XHR.appendData('USE_MYSQL',1); }else{ XHR.appendData('USE_MYSQL',0); }
		if(document.getElementById('USE_ACTIVEDIRECTORY-$t').checked){XHR.appendData('USE_ACTIVEDIRECTORY',1); }else{ XHR.appendData('USE_ACTIVEDIRECTORY',0); }
		if(document.getElementById('SMS_REGISTER-$t').checked){XHR.appendData('SMS_REGISTER',1); }else{ XHR.appendData('SMS_REGISTER',0); }
		if(document.getElementById('MACWHITE-$t').checked){XHR.appendData('MACWHITE',1); }else{ XHR.appendData('MACWHITE',0); }
		if(document.getElementById('BOUNCE_AUTH-$t').checked){XHR.appendData('BOUNCE_AUTH',1); }else{ XHR.appendData('BOUNCE_AUTH',0); }
		XHR.appendData('TOS_VALUE',document.getElementById('TOS_VALUE-$t').value);
		XHR.appendData('ArticaSplashHotSpotCacheAuth',document.getElementById('ArticaSplashHotSpotCacheAuth-$t').value);
		XHR.appendData('ArticaSplashHotSpotEndTime',document.getElementById('ArticaSplashHotSpotEndTime-$t').value);
		XHR.appendData('ArticaSplashHotSpotRemoveAccount',document.getElementById('ArticaSplashHotSpotRemoveAccount-$t').value);
		XHR.appendData('LIMIT_BY_SIZE',document.getElementById('LIMIT_BY_SIZE-$t').value);
		
		XHR.appendData('LOST_LANDING_PAGE',document.getElementById('LOST_LANDING_PAGE-$t').value);
		XHR.appendData('LANDING_PAGE',document.getElementById('LANDING_PAGE-$t').value);
		
		
		
		
		XHR.sendAndLoad('$page', 'POST',xSave$t);
		
	}
</script>";	
	
	echo $tpl->_ENGINE_parse_body($html);
}


function Save(){
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	$sock=new wifidog_settings($_POST["ruleid"]);
	unset($_POST["ruleid"]);
	while (list ($key, $value) = each ($_POST) ){
		$sock->SET_INFO($key, $value);
		
	}
	$sock=new sockets();
	$sock->getFrameWork("hotspot.php?remove-cache=yes");
	$sock->getFrameWork("squid.php?reconfigure-squid=yes");
}