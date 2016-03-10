<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.tcpip.inc');
	
	$user=new usersMenus();

		
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		die();exit();
	}	
	
	if(isset($_POST["EnableSquidExternalLDAP"])){Save();exit;}

page();

function page(){

$squid=new squidbee();
$users=new usersMenus();
$sock=new sockets();
$tpl=new templates();
$page=CurrentPageName();
$SquidLdapAuthEnableGroups=$sock->GET_INFO("SquidLdapAuthEnableGroups");
$EnableKerbAuth=intval($sock->GET_INFO("EnableKerbAuth"));
$SquidLdapAuthBanner=$sock->GET_INFO("SquidLdapAuthBanner");
if($SquidLdapAuthBanner==null){$SquidLdapAuthBanner="Basic credentials, Please logon...";}
if($EnableKerbAuth==1){$error=FATAL_ERROR_SHOW_128("{ldap_with_ad_explain}");}

if(trim($users->SQUID_LDAP_AUTH)==null){
	echo FATAL_ERROR_SHOW_128("{authenticate_users_no_binaries}");return;
}
	$EnableOpenLDAP=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableOpenLDAP"));
	$please_choose_only_one_method=$tpl->javascript_parse_text("{please_choose_only_one_method}");
	$ldap_server=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_server"];
	$ldap_port=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_port"];
	$userdn=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_user"];
	$ldap_password=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_password"];
	$ldap_suffix=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_suffix"];
	$ldap_filter_users=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_users"];
	$ldap_filter_group=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_group"];
	
	$auth_banner=$squid->EXTERNAL_LDAP_AUTH_PARAMS["auth_banner"];
	$ldap_user_attribute=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_user_attribute"];
	$ldap_group_attribute=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_group_attribute"];
	$ldap_filter_search_group=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_search_group"];
	$ldap_filter_group_attribute=$squid->EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_group_attribute"];
	$SquidLdapAuthBanner=$sock->GET_INFO("SquidLdapAuthBanner");
	if($SquidLdapAuthBanner==null){$SquidLdapAuthBanner="Basic credentials, Please logon...";}
	$EnableSquidExternalLDAP=$squid->LDAP_EXTERNAL_AUTH;
	if($auth_banner==null){$auth_banner=$SquidLdapAuthBanner;}
	if($ldap_filter_users==null){$ldap_filter_users="sAMAccountName=%s";}
	if($ldap_filter_group==null){$ldap_filter_group="(&(objectclass=person)(sAMAccountName=%u)(memberof=*))";}
	if($ldap_port==null){$ldap_port=389;}
	$t=time();
	
	if($ldap_filter_users==null){$ldap_filter_users="sAMAccountName=%s";}
	if($ldap_user_attribute==null){$ldap_user_attribute="sAMAccountName";}
	if($ldap_filter_group==null){$ldap_filter_group="(&(objectclass=person)(sAMAccountName=%u)(memberof=*))";}
	if($ldap_filter_search_group==null){$ldap_filter_search_group="(&(objectclass=group)(sAMAccountName=%s))";}
	if($ldap_group_attribute==null){$ldap_group_attribute="sAMAccountName";}
	if($ldap_filter_group_attribute==null){$ldap_filter_group_attribute="memberof";}
	
	if(!is_numeric($squid->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_children"])){$squid->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_children"]=10;}
	if(!is_numeric($squid->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_startup"])){$squid->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_startup"]=3;}
	if(!is_numeric($squid->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_idle"])){$squid->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_idle"]=1;}
	if(!is_numeric($squid->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_cache"])){$squid->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_cache"]=360;}
	
	if($EnableSquidExternalLDAP==1){$squid->LDAP_AUTH=0;}
	$bt_SaveExternalLDAPSYS=button("{apply}","SaveExternalLDAPSYS()",32);
	$txt_SaveExternalLDAPSYS=null;
	
	if($EnableOpenLDAP==0){
		$squid->LDAP_AUTH=0;
		$bt_SaveExternalLDAPSYS=null;
		$txt_SaveExternalLDAPSYS="<p class=text-error style='font-size:18px'>{local_openldap_service_disabled}</p>";
	}
	
	

$html="$error
<div style='width:98%' class=form>
$txt_SaveExternalLDAPSYS
<table style='width:99%' class=TableRemove>
<tr>
	<td colspan=2 >" . Paragraphe_switch_img(
					"{authenticate_users_local_db}","{authenticate_users_explain}",
					"ldap_auth-$t",$squid->LDAP_AUTH,'{enable_disable}',1220,"LocalDBCheck$t()")."
	</td>
</tr>
<tr>
	<td style='font-size:22px' class=legend>{banner}:</td>
	<td>". Field_text("SquidLdapAuthBanner-$t", $SquidLdapAuthBanner,"font-size:22px;width:1060px")."</td>
</td>
</tr>
	<tr>
		<td colspan=2 align='right'>
			<hr>
				$bt_SaveExternalLDAPSYS
		</td>
	</tr>
</table>
</div>

<div id='ldap_ext_auth' style='width:98%;margin-top:20px' class=form>

<table style='width:99%' class=TableRemove>
<tr>
	<td colspan=2>" . Paragraphe_switch_img(
	"{authenticate_users_remote_db}","{SQUID_LDAP_AUTH_EXT}",
	"EnableSquidExternalLDAP",$EnableSquidExternalLDAP,'{enable_disable}',1220,"EnableSquidExternalLDAP()")."
	</td>					
</tr>
	<tr style='height:50px'><td colspan=2><span style='font-size:26px'>{openldap_server}</tD></tr>	
	<tr>
		<td  style='font-size:20px' class=legend>{hostname}:</td>
		<td>". Field_text("ldap_server",$ldap_server,"font-size:20px;padding:3px")."</td>
	</tr>
	<tr>
		<td  style='font-size:20px' class=legend>{listen_port}:</td>
		<td>". Field_text("ldap_port",$ldap_port,"font-size:20px;padding:3px;width:110px")."</td>
	</tr>	
	<tr>
		<td  style='font-size:18px' class=legend>{auth_banner}:</td>
		<td>". Field_text("auth_banner",$auth_banner,"font-size:20px;padding:3px")."</td>
	</tr>	
	
	<tr>
		<td  style='font-size:20px' class=legend>{userdn}:</td>
		<td>". Field_text("ldap_user",$userdn,"font-size:20px;padding:3px")."</td>
	</tr>
	<tr>
		<td  style='font-size:20px' class=legend>{ldap_password}:</td>
		<td>". Field_password("ldap_password",$ldap_password,"font-size:20px;padding:3px")."</td>
	</tr>
	
	<tr>
		<td  style='font-size:20px' class=legend>{ldap_suffix}:</td>
		<td>". Field_text("ldap_suffix",$ldap_suffix,"font-size:20px;padding:3px")."</td>
	</tr>
	<tr><td colspan=2><hr></tD></tr>
	<tr style='height:50px'><td colspan=2><span style='font-size:26px'>{members}</tD></tr>		
	<tr>
		<td  style='font-size:20px' class=legend>{ldap_filter_users}:</td>
		<td>". Field_text("ldap_filter_users",$ldap_filter_users,"font-size:20px;padding:3px")."</td>
	</tr>	
	<tr>
		<td  style='font-size:20px' class=legend>{ldap_user_attribute}:</td>
		<td>". Field_text("ldap_user_attribute",$ldap_user_attribute,"font-size:20px;padding:3px")."</td>
	</tr>
	
				
	<tr><td colspan=2><hr></tD></tr>
	<tr style='height:50px'><td colspan=2><span style='font-size:26px'>{groups2}</tD></tr>
	<tr>
		<td  style='font-size:20px' class=legend nowrap>{search_users_in_groups}:</td>
		<td>". Field_text("ldap_filter_group",$ldap_filter_group,"font-size:20px;padding:3px;width:600px")."</td>
	</tr>
	<tr>
		<td  style='font-size:20px' class=legend>{attribute}:</td>
		<td>". Field_text("ldap_filter_group_attribute",$ldap_filter_group_attribute,"font-size:20px;padding:3px;width:600px")."</td>
	</tr>													
	<tr>
		<td  style='font-size:20px' class=legend>{ldap_filter_search_groups}:</td>
		<td>". Field_text("ldap_filter_search_group",$ldap_filter_search_group,"font-size:20px;padding:3px;width:600px")."</td>
	</tr>
	<tr>
		<td  style='font-size:20px' class=legend>{ldap_group_attribute}:</td>
		<td>". Field_text("ldap_group_attribute",$ldap_group_attribute,"font-size:20px;padding:3px;")."</td>
	</tr>
	
	<tr><td colspan=2><hr></tD></tr>
	<tr style='height:50px'><td colspan=2><span style='font-size:26px'>{performance}</tD></tr>
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>{cache_time} {seconds}:</td>
		<td width=99%>". Field_text("external_acl_cache-$t",$squid->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_cache"],"font-size:22px;width:90px")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>{max_processes}:</td>
		<td width=99%>". Field_text("external_acl_children-$t",$squid->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_children"],"font-size:22px;width:90px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>{preload_processes}:</td>
		<td width=99%>". Field_text("external_acl_startup-$t",$squid->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_startup"],"font-size:22px;width:90px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' widht=1% nowrap>{prepare_processes}:</td>
		<td width=99%>". Field_text("external_acl_idle-$t",$squid->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_idle"],"font-size:22px;width:90px")."</td>
	</tr>	
				
				
	<tr>
		<td colspan=2 align='right'>
			<hr>
				". button("{apply}","SaveExternalLDAPSYS()",32)."
		</td>
	</tr>
	</table>
	</div>
	
	
	<script>
	
	
function LocalDBCheck$t(){

	document.getElementById('SquidLdapAuthBanner-$t').disabled=true;

	if(document.getElementById('ldap_auth-$t').value==1){
		document.getElementById('EnableSquidExternalLDAP').value=0;
		EnableSquidExternalLDAP();
		document.getElementById('SquidLdapAuthBanner-$t').disabled=false;
	}
	
	

}
	
		function EnableSquidExternalLDAP(){
			var EnableKerbAuth=$EnableKerbAuth;
		
			if(EnableKerbAuth==1){return;}
		
			var disabled=false;
			var EnableSquidExternalLDAP=document.getElementById('EnableSquidExternalLDAP').value;
			
			if(EnableSquidExternalLDAP==0){
				document.getElementById('SquidLdapAuthBanner-$t').disabled=false;
				disabled=true;}
			
			if(EnableSquidExternalLDAP==1){
				document.getElementById('ldap_auth-$t').value=0;
				document.getElementById('SquidLdapAuthBanner-$t').disabled=true;
			}
			
			document.getElementById('ldap_server').disabled=disabled;
			document.getElementById('ldap_port').disabled=disabled;
			document.getElementById('ldap_user').disabled=disabled;
			document.getElementById('ldap_password').disabled=disabled;
			document.getElementById('ldap_suffix').disabled=disabled;
			document.getElementById('ldap_filter_users').disabled=disabled;
			document.getElementById('ldap_filter_group').disabled=disabled;
			document.getElementById('auth_banner').disabled=disabled;
			document.getElementById('ldap_user_attribute').disabled=disabled;
			document.getElementById('ldap_filter_search_group').disabled=disabled;
			document.getElementById('ldap_group_attribute').disabled=disabled;
			document.getElementById('ldap_filter_group_attribute').disabled=disabled;
			
			document.getElementById('external_acl_children-$t').disabled=disabled;
			document.getElementById('external_acl_cache-$t').disabled=disabled;
			document.getElementById('external_acl_startup-$t').disabled=disabled;
			document.getElementById('external_acl_idle-$t').disabled=disabled;
			}
			
	var x_SaveExternalLDAPSYS= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		RefreshTab('main_squid_auth');
		Loadjs('squid.restart.php?onlySquid=yes&ask=yes');
	}				
			
		function SaveExternalLDAPSYS(){
			var XHR = new XHRConnection();
			var EnableSquidExternalLDAP=document.getElementById('EnableSquidExternalLDAP').value;
			var ldap_auth=document.getElementById('ldap_auth-$t').value;
			
			if(ldap_auth==1){
				if( EnableSquidExternalLDAP ==1){
					
					alert('$please_choose_only_one_method');
					return;
				
				}
			
			}
			
			if( EnableSquidExternalLDAP ==1 ){ ldap_auth = 0;}
			if(ldap_auth ==1 ){ EnableSquidExternalLDAP = 0;}
			
			XHR.appendData('ldap_auth',ldap_auth);
			XHR.appendData('SquidLdapAuthBanner',encodeURIComponent(document.getElementById('SquidLdapAuthBanner-$t').value));
			XHR.appendData('ldap_server',document.getElementById('ldap_server').value);
			XHR.appendData('EnableSquidExternalLDAP',EnableSquidExternalLDAP);
			XHR.appendData('ldap_server',encodeURIComponent(document.getElementById('ldap_server').value));
			XHR.appendData('ldap_port',encodeURIComponent(document.getElementById('ldap_port').value));
			XHR.appendData('ldap_user',encodeURIComponent(document.getElementById('ldap_user').value));
			XHR.appendData('ldap_password',encodeURIComponent(document.getElementById('ldap_password').value));
			XHR.appendData('ldap_suffix',encodeURIComponent(document.getElementById('ldap_suffix').value));
			XHR.appendData('ldap_filter_users',document.getElementById('ldap_filter_users').value);
			XHR.appendData('ldap_filter_group',encodeURIComponent(document.getElementById('ldap_filter_group').value));
			XHR.appendData('auth_banner',encodeURIComponent(document.getElementById('auth_banner').value));
			XHR.appendData('ldap_user_attribute',encodeURIComponent(document.getElementById('ldap_user_attribute').value));
			XHR.appendData('ldap_filter_search_group',encodeURIComponent(document.getElementById('ldap_filter_search_group').value));
			XHR.appendData('ldap_group_attribute',encodeURIComponent(document.getElementById('ldap_group_attribute').value));
			XHR.appendData('ldap_filter_group_attribute',encodeURIComponent(document.getElementById('ldap_filter_group_attribute').value));
			
			XHR.appendData('external_acl_children',document.getElementById('external_acl_children-$t').value);
			XHR.appendData('external_acl_cache',document.getElementById('external_acl_cache-$t').value);
			XHR.appendData('external_acl_startup',document.getElementById('external_acl_startup-$t').value);
			XHR.appendData('external_acl_idle',document.getElementById('external_acl_idle-$t').value);
			XHR.sendAndLoad('$page', 'POST',x_SaveExternalLDAPSYS);		
			}
			
	
		EnableSquidExternalLDAP();
	</script>
";


echo $tpl->_ENGINE_parse_body($html);

}

function Save(){
	$squid=new squidbee();
	$_POST["SquidLdapAuthBanner"]=url_decode_special_tool($_POST["SquidLdapAuthBanner"]);
	$_POST["auth_banner"]=url_decode_special_tool($_POST["auth_banner"]);
	$sock=new sockets();
	$sock->SaveConfigFile($_POST["SquidLdapAuthBanner"], "SquidLdapAuthBanner");
	
	if($_POST["EnableSquidExternalLDAP"]==1){$squid->LDAP_AUTH=1;}
	if($_POST["ldap_auth"]==1){$squid->LDAP_AUTH=1;}
	
	if($_POST["EnableSquidExternalLDAP"]==0){if($_POST["ldap_auth"]==0){$squid->LDAP_AUTH=0;}}
	
	while (list ($num, $ligne) = each ($_POST) ){
		$_POST[$num]=url_decode_special_tool($ligne);
		
	}
	$squid->LDAP_EXTERNAL_AUTH=$_POST["EnableSquidExternalLDAP"];
	$squid->EXTERNAL_LDAP_AUTH_PARAMS=$_POST;
	$squid->SaveToLdap();
	
}

